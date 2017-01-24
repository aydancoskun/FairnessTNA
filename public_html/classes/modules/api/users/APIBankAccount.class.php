<?php
/*********************************************************************************
 * This file is part of "Fairness", a Payroll and Time Management program.
 * Fairness is Copyright 2013 Aydan Coskun (aydan.ayfer.coskun@gmail.com)
 * Portions of this software are Copyright of T i m e T r e x Software Inc.
 * Fairness is a fork of "T i m e T r e x Workforce Management" Software.
 *
 * Fairness is free software; you can redistribute it and/or modify it under the
 * terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation, either version 3 of the License, or (at you option )
 * any later version.
 *
 * Fairness is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE.  See the GNU Affero General Public License for more
 * details.
 *
 * You should have received a copy of the GNU Affero General Public License along
 * with this program; if not, see http://www.gnu.org/licenses or write to the Free
 * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301 USA.
 ********************************************************************************/


/**
 * @package API\Users
 */
class APIBankAccount extends APIFactory
{
    protected $main_class = 'BankAccountFactory';

    public function __construct()
    {
        parent::__construct(); //Make sure parent constructor is always called.

        return true;
    }

    /**
     * Get default branch data for creating new branches.
     * @return array
     */
    public function getBankAccountDefaultData($user_id = null)
    {
        $company_obj = $this->getCurrentCompanyObject();

        Debug::Text('Getting wage default data...', __FILE__, __LINE__, __METHOD__, 10);

        //If user_id is passed, check for other wage entries, if none, default to the employees hire date.

        $data = array(
            'company_id' => $company_obj->getId(),
        );

        return $this->returnHandler($data);
    }

    /**
     * Export data to csv
     * @param array $data filter data
     * @param string $format file format (csv)
     * @return array
     */
    public function exportBankAccount($format = 'csv', $data = null, $disable_paging = true)
    {
        $result = $this->stripReturnHandler($this->getBankAccount($data, $disable_paging));
        return $this->exportRecords($format, 'export_bank_account', $result, ((isset($data['filter_columns'])) ? $data['filter_columns'] : null));
    }

    /**
     * Get branch data for one or more branches.
     * @param array $data filter data
     * @return array
     */
    public function getBankAccount($data = null, $disable_paging = false)
    {
        if (!$this->getPermissionObject()->Check('user', 'enabled')
            or !($this->getPermissionObject()->Check('user', 'edit_bank') or $this->getPermissionObject()->Check('user', 'edit_own_bank') or $this->getPermissionObject()->Check('user', 'edit_child_bank'))
        ) {
            return $this->getPermissionObject()->PermissionDenied();
        }
        $data = $this->initializeFilterAndPager($data, $disable_paging);

        //Get Permission Hierarchy Children first, as this can be used for viewing, or editing.
        if ($this->getPermissionObject()->Check('user', 'edit_bank') == true) {
            //Don't set permission_children_ids.
            $data['filter_data']['permission_children_ids'] = null;
        } else {
            if ($this->getPermissionObject()->Check('user', 'edit_child_bank') == true) {
                //Manually handle the permission checks here because edit_child_bank doesn't fit with getPermissionChildren() appending "_own" or "_child" on the end.
                $data['filter_data']['permission_children_ids'] = $this->getPermissionObject()->getPermissionHierarchyChildren($this->getCurrentCompanyObject()->getId(), $this->getCurrentUserObject()->getId());
            }

            if ($this->getPermissionObject()->Check('user', 'edit_own_bank') == true) {
                $data['filter_data']['permission_children_ids'][] = $this->getCurrentUserObject()->getId();
            }
            Debug::Arr($data['filter_data']['permission_children_ids'], 'Permission Children: ', __FILE__, __LINE__, __METHOD__, 10);
        }

        $blf = TTnew('BankAccountListFactory');
        $blf->getAPISearchByCompanyIdAndArrayCriteria($this->getCurrentCompanyObject()->getId(), $data['filter_data'], $data['filter_items_per_page'], $data['filter_page'], null, $data['filter_sort']);
        Debug::Text('Record Count: ' . $blf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
        if ($blf->getRecordCount() > 0) {
            $this->getProgressBarObject()->start($this->getAMFMessageID(), $blf->getRecordCount());

            $this->setPagerObject($blf);

            $retarr = array();
            foreach ($blf as $b_obj) {
                $retarr[] = $b_obj->getObjectAsArray($data['filter_columns'], $data['filter_data']['permission_children_ids']);

                $this->getProgressBarObject()->set($this->getAMFMessageID(), $blf->getCurrentRow());
            }

            $this->getProgressBarObject()->stop($this->getAMFMessageID());

            Debug::Arr($retarr, 'Record Count: ' . $blf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
            return $this->returnHandler($retarr);
        }

        return $this->returnHandler(true); //No records returned.
    }

    /**
     * Get only the fields that are common across all records in the search criteria. Used for Mass Editing of records.
     * @param array $data filter data
     * @return array
     */
    public function getCommonBankAccountData($data)
    {
        return Misc::arrayIntersectByRow($this->stripReturnHandler($this->getBankAccount($data, true)));
    }

    /**
     * Validate branch data for one or more branches.
     * @param array $data branch data
     * @return array
     */
    public function validateBankAccount($data)
    {
        return $this->setBankAccount($data, true);
    }

    /**
     * Set branch data for one or more branches.
     * @param array $data branch data
     * @return array
     */
    public function setBankAccount($data, $validate_only = false, $ignore_warning = true)
    {
        $validate_only = (bool)$validate_only;
        $ignore_warning = (bool)$ignore_warning;

        if (!is_array($data)) {
            return $this->returnHandler(false);
        }

        if (!$this->getPermissionObject()->Check('user', 'enabled')
            or !($this->getPermissionObject()->Check('user', 'edit_bank') or $this->getPermissionObject()->Check('user', 'edit_own_bank') or $this->getPermissionObject()->Check('user', 'edit_child_bank'))
        ) {
            return $this->getPermissionObject()->PermissionDenied();
        }

        if ($validate_only == true) {
            Debug::Text('Validating Only!', __FILE__, __LINE__, __METHOD__, 10);
            $permission_children_ids = false;
        } else {
            //Get Permission Hierarchy Children first, as this can be used for viewing, or editing.
            $permission_children_ids = $this->getPermissionChildren();
        }

        extract($this->convertToMultipleRecords($data));
        Debug::Text('Received data for: ' . $total_records . ' BankAccounts', __FILE__, __LINE__, __METHOD__, 10);
        Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

        $validator_stats = array('total_records' => $total_records, 'valid_records' => 0);
        $validator = $save_result = false;
        if (is_array($data) and $total_records > 0) {
            $this->getProgressBarObject()->start($this->getAMFMessageID(), $total_records);

            foreach ($data as $key => $row) {
                $primary_validator = new Validator();
                $lf = TTnew('BankAccountListFactory');
                $lf->StartTransaction();
                if (isset($row['id']) and $row['id'] > 0) {
                    //Modifying existing object.
                    //Get wage object, so we can only modify just changed data for specific records if needed.
                    $lf->getByIdAndCompanyId($row['id'], $this->getCurrentCompanyObject()->getId());
                    if ($lf->getRecordCount() == 1) {
                        //Object exists, check edit permissions
                        if (
                            $validate_only == true
                            or
                            (
                                $this->getPermissionObject()->Check('user', 'edit_bank')
                                or ($this->getPermissionObject()->Check('user', 'edit_own_bank') and $this->getPermissionObject()->isOwner($lf->getCurrent()->getCreatedBy(), $lf->getCurrent()->getUser()) === true)
                                or ($this->getPermissionObject()->Check('user', 'edit_child_bank') and $this->getPermissionObject()->isChild($lf->getCurrent()->getUser(), $permission_children_ids) === true)
                            )
                        ) {
                            Debug::Text('Row Exists, getting current data: ', $row['id'], __FILE__, __LINE__, __METHOD__, 10);
                            $lf = $lf->getCurrent();
                            $row = array_merge($lf->getObjectAsArray(), $row);
                        } else {
                            $primary_validator->isTrue('permission', false, TTi18n::gettext('Edit permission denied'));
                        }
                    } else {
                        //Object doesn't exist.
                        $primary_validator->isTrue('id', false, TTi18n::gettext('Edit permission denied, record does not exist'));
                    }
                } //else {
                //Adding new object, check ADD permissions.
                //$primary_validator->isTrue( 'permission', $this->getPermissionObject()->Check('user', 'add'), TTi18n::gettext('Add permission denied') );
                //}
                //Debug::Arr($row, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

                $is_valid = $primary_validator->isValid($ignore_warning);
                if ($is_valid == true) { //Check to see if all permission checks passed before trying to save data.
                    Debug::Text('Setting object data...', __FILE__, __LINE__, __METHOD__, 10);

                    if (($row['user_id'] != '' and $row['user_id'] == $this->getCurrentUserObject()->getId()) and $row['company_id'] == '' and $this->getPermissionObject()->Check('user', 'edit_own_bank')) {
                        Debug::Text('Current User/Company', __FILE__, __LINE__, __METHOD__, 10);
                        //Current user
                        $row['company_id'] = $this->getCurrentCompanyObject()->getId();
                        $row['user_id'] = $this->getCurrentUserObject()->getId();
                    } elseif ($row['user_id'] != '' and ($row['company_id'] == '' or $row['company_id'] == $this->getCurrentCompanyObject()->getId()) and $this->getPermissionObject()->Check('user', 'edit_child_bank')) {
                        Debug::Text('Specified Child User', __FILE__, __LINE__, __METHOD__, 10);
                        //Specified User
                        $row['company_id'] = $this->getCurrentCompanyObject()->getId();
                    } elseif ($row['user_id'] != '' and ($row['company_id'] == '' or $row['company_id'] == $this->getCurrentCompanyObject()->getId()) and $this->getPermissionObject()->Check('user', 'edit_bank')) {
                        Debug::Text('Specified User', __FILE__, __LINE__, __METHOD__, 10);
                        //Specified User
                        $row['company_id'] = $this->getCurrentCompanyObject()->getId();
                    } elseif ($row['company_id'] != '' and $row['user_id'] == '' and $this->getPermissionObject()->Check('company', 'edit_own_bank')) {
                        Debug::Text('Specified Company', __FILE__, __LINE__, __METHOD__, 10);
                        //Company bank.
                        $row['company_id'] = $this->getCurrentCompanyObject()->getId();
                    } else {
                        Debug::Text('No Company or User ID specified...', __FILE__, __LINE__, __METHOD__, 10);
                        //Assume its always the currently logged in users account.
                        $row['company_id'] = $this->getCurrentCompanyObject()->getId();

                        if (!isset($row['user_id']) or $this->getPermissionObject()->Check('company', 'edit_bank') == false) {
                            $row['user_id'] = $this->getCurrentUserObject()->getId();
                        }
                    }

                    $lf->setObjectFromArray($row);

                    $is_valid = $lf->isValid($ignore_warning);
                    if ($is_valid == true) {
                        Debug::Text('Saving data...', __FILE__, __LINE__, __METHOD__, 10);
                        if ($validate_only == true) {
                            $save_result[$key] = true;
                        } else {
                            $save_result[$key] = $lf->Save();
                        }
                        $validator_stats['valid_records']++;
                    }
                }

                if ($is_valid == false) {
                    Debug::Text('Data is Invalid...', __FILE__, __LINE__, __METHOD__, 10);

                    $lf->FailTransaction(); //Just rollback this single record, continue on to the rest.

                    $validator[$key] = $this->setValidationArray($primary_validator, $lf);
                } elseif ($validate_only == true) {
                    $lf->FailTransaction();
                }


                $lf->CommitTransaction();

                $this->getProgressBarObject()->set($this->getAMFMessageID(), $key);
            }

            $this->getProgressBarObject()->stop($this->getAMFMessageID());

            return $this->handleRecordValidationResults($validator, $validator_stats, $key, $save_result);
        }

        return $this->returnHandler(false);
    }

    /**
     * Delete one or more branchs.
     * @param array $data branch data
     * @return array
     */
    public function deleteBankAccount($data)
    {
        if (is_numeric($data)) {
            $data = array($data);
        }

        if (!is_array($data)) {
            return $this->returnHandler(false);
        }

        if (!$this->getPermissionObject()->Check('user', 'enabled')
            or !($this->getPermissionObject()->Check('user', 'edit_bank') or $this->getPermissionObject()->Check('user', 'edit_own_bank') or $this->getPermissionObject()->Check('user', 'edit_child_bank'))
        ) {
            return $this->getPermissionObject()->PermissionDenied();
        }

        //Get Permission Hierarchy Children first, as this can be used for viewing, or editing.
        $permission_children_ids = $this->getPermissionChildren();

        Debug::Text('Received data for: ' . count($data) . ' BankAccounts', __FILE__, __LINE__, __METHOD__, 10);
        Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

        $total_records = count($data);
        $validator = $save_result = false;
        $validator_stats = array('total_records' => $total_records, 'valid_records' => 0);
        if (is_array($data) and $total_records > 0) {
            $this->getProgressBarObject()->start($this->getAMFMessageID(), $total_records);

            foreach ($data as $key => $id) {
                $primary_validator = new Validator();
                $lf = TTnew('BankAccountListFactory');
                $lf->StartTransaction();
                if (is_numeric($id)) {
                    //Modifying existing object.
                    //Get branch object, so we can only modify just changed data for specific records if needed.
                    $lf->getByIdAndCompanyId($id, $this->getCurrentCompanyObject()->getId());
                    if ($lf->getRecordCount() == 1) {
                        //Object exists, check edit permissions
                        if ($this->getPermissionObject()->Check('user', 'edit_bank')
                            or ($this->getPermissionObject()->Check('user', 'edit_own_bank') and $this->getPermissionObject()->isOwner($lf->getCurrent()->getCreatedBy(), $lf->getCurrent()->getUser()) === true)
                            or ($this->getPermissionObject()->Check('user', 'edit_child_bank') and $this->getPermissionObject()->isChild($lf->getCurrent()->getId(), $permission_children_ids) === true)
                        ) {
                            Debug::Text('Record Exists, deleting record: ', $id, __FILE__, __LINE__, __METHOD__, 10);
                            $lf = $lf->getCurrent();
                        } else {
                            $primary_validator->isTrue('permission', false, TTi18n::gettext('Delete permission denied'));
                        }
                    } else {
                        //Object doesn't exist.
                        $primary_validator->isTrue('id', false, TTi18n::gettext('Delete permission denied, record does not exist'));
                    }
                } else {
                    $primary_validator->isTrue('id', false, TTi18n::gettext('Delete permission denied, record does not exist'));
                }

                //Debug::Arr($lf, 'AData: ', __FILE__, __LINE__, __METHOD__, 10);

                $is_valid = $primary_validator->isValid();
                if ($is_valid == true) { //Check to see if all permission checks passed before trying to save data.
                    Debug::Text('Attempting to delete record...', __FILE__, __LINE__, __METHOD__, 10);
                    $lf->setDeleted(true);

                    $is_valid = $lf->isValid();
                    if ($is_valid == true) {
                        Debug::Text('Record Deleted...', __FILE__, __LINE__, __METHOD__, 10);
                        $save_result[$key] = $lf->Save();
                        $validator_stats['valid_records']++;
                    }
                }

                if ($is_valid == false) {
                    Debug::Text('Data is Invalid...', __FILE__, __LINE__, __METHOD__, 10);

                    $lf->FailTransaction(); //Just rollback this single record, continue on to the rest.

                    $validator[$key] = $this->setValidationArray($primary_validator, $lf);
                }

                $lf->CommitTransaction();

                $this->getProgressBarObject()->set($this->getAMFMessageID(), $key);
            }

            $this->getProgressBarObject()->stop($this->getAMFMessageID());

            return $this->handleRecordValidationResults($validator, $validator_stats, $key, $save_result);
        }

        return $this->returnHandler(false);
    }
}
