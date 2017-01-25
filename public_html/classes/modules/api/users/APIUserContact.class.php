<?php
/**********************************************************************************
 * This file is part of "FairnessTNA", a Payroll and Time Management program.
 * FairnessTNA is copyright 2013-2017 Aydan Coskun (aydan.ayfer.coskun@gmail.com)
 * others. For full attribution and copyrights details see the COPYRIGHT file.
 *
 * FairnessTNA is free software; you can redistribute it and/or modify it under the
 * terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation, either version 3 of the License, or (at you option )
 * any later version.
 *
 * FairnessTNA is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE.  See the GNU Affero General Public License for more
 * details.
 *
 * You should have received a copy of the GNU Affero General Public License along
 * with this program; if not, see http://www.gnu.org/licenses or write to the Free
 * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301 USA.
 *********************************************************************************/


/**
 * @package API\Users
 */
class APIUserContact extends APIFactory
{
    protected $main_class = 'UserContactFactory';

    public function __construct()
    {
        parent::__construct(); //Make sure parent constructor is always called.

        return true;
    }

    /**
     * Get options for dropdown boxes.
     * @param string $name Name of options to return, ie: 'columns', 'type', 'status'
     * @param mixed $parent Parent name/ID of options to return if data is in hierarchical format. (ie: Province)
     * @return array
     */
    public function getOptions($name = false, $parent = null)
    {
        if ($name == 'columns'
            and (!$this->getPermissionObject()->Check('user_contact', 'enabled')
                or !($this->getPermissionObject()->Check('user_contact', 'view') or $this->getPermissionObject()->Check('user_contact', 'view_own') or $this->getPermissionObject()->Check('user_contact', 'view_child')))
        ) {
            $name = 'list_columns';
        }

        return parent::getOptions($name, $parent);
    }

    /**
     * Get default user data for creating new users.
     * @return array
     */
    public function getUserContactDefaultData()
    {

        //Allow getting default data from other companies, so it makes it easier to create the first employee of a company.
        $company_id = $this->getCurrentCompanyObject()->getId();
        Debug::Text('Getting user contact default data for Company ID: ' . $company_id, __FILE__, __LINE__, __METHOD__, 10);

        //Get New Hire Defaults.
        $udlf = TTnew('UserDefaultListFactory');
        $udlf->getByCompanyId($company_id);
        if ($udlf->getRecordCount() > 0) {
            Debug::Text('Using User Defaults, as they exist...', __FILE__, __LINE__, __METHOD__, 10);
            $udf_obj = $udlf->getCurrent();

            $data = array(
                'country' => $udf_obj->getCountry(),
                'province' => $udf_obj->getProvince(),
            );
        }

        if (!isset($data['country'])) {
            $data['country'] = 'US';
        }

        return $this->returnHandler($data);
    }

    /**
     * Export data to csv
     * @param array $data filter data
     * @param string $format file format (csv)
     * @return array
     */
    public function exportUserContact($format = 'csv', $data = null, $disable_paging = true)
    {
        $result = $this->stripReturnHandler($this->getUserContact($data, $disable_paging));
        return $this->exportRecords($format, 'export_employee_contacts', $result, ((isset($data['filter_columns'])) ? $data['filter_columns'] : null));
    }

    /**
     * Get user data for one or more users.
     * @param array $data filter data
     * @param boolean $disable_paging disables paging and returns all records.
     * @return array
     */
    public function getUserContact($data = null, $disable_paging = false)
    {
        if (!$this->getPermissionObject()->Check('user_contact', 'enabled')
            or !($this->getPermissionObject()->Check('user_contact', 'view') or $this->getPermissionObject()->Check('user_contact', 'view_own') or $this->getPermissionObject()->Check('user_contact', 'view_child'))
        ) {
            return $this->getPermissionObject()->PermissionDenied();
        }
        $data = $this->initializeFilterAndPager($data, $disable_paging);

        //We need to take into account different permissions, ie: punch->view, view_child, view_own when displaying the dropdown
        //box in the TimeSheet view and other views as well. Allow the caller of this function to pass a "permission_section"
        //that can be used to determine this.
        if (isset($data['permission_section']) and $data['permission_section'] != '') {
            $permission_section = trim(strtolower($data['permission_section']));
        } else {
            $permission_section = 'user_contact';
        }

        //Get Permission Hierarchy Children first, as this can be used for viewing, or editing.
        //$data['filter_data']['permission_children_ids'] = $this->getPermissionObject()->getPermissionChildren( 'user', 'view' );
        $data['filter_data']['permission_children_ids'] = $this->getPermissionObject()->getPermissionChildren($permission_section, 'view');
        Debug::Arr($data['filter_data']['permission_children_ids'], 'Permission Section: ' . $permission_section . ' Child IDs: ', __FILE__, __LINE__, __METHOD__, 10);

        //Allow getting users from other companies, so we can change admin contacts when using the master company.
        if (isset($data['filter_data']['company_id'])
            and $data['filter_data']['company_id'] > 0
            and ($this->getPermissionObject()->Check('company', 'enabled') and $this->getPermissionObject()->Check('company', 'edit'))
        ) {
            $company_id = $data['filter_data']['company_id'];
        } else {
            $company_id = $this->getCurrentCompanyObject()->getId();
        }
        $uclf = TTnew('UserContactListFactory');
        $uclf->getAPISearchByCompanyIdAndArrayCriteria($company_id, $data['filter_data'], $data['filter_items_per_page'], $data['filter_page'], null, $data['filter_sort']);
        Debug::Text('Record Count: ' . $uclf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
        if ($uclf->getRecordCount() > 0) {
            $this->getProgressBarObject()->start($this->getAMFMessageID(), $uclf->getRecordCount());

            $this->setPagerObject($uclf);

            $retarr = array();
            foreach ($uclf as $uc_obj) {
                $user_data = $uc_obj->getObjectAsArray($data['filter_columns'], $data['filter_data']['permission_children_ids']);

                //Hide SIN if user doesn't have permissions to see it.
                if (isset($user_data['sin']) and $user_data['sin'] != '' and $this->getPermissionObject()->Check('user_contact', 'view_sin') == false) {
                    $user_data['sin'] = $uc_obj->getSecureSIN();
                }

                $retarr[] = $user_data;

                $this->getProgressBarObject()->set($this->getAMFMessageID(), $uclf->getCurrentRow());
            }

            $this->getProgressBarObject()->stop($this->getAMFMessageID());

            return $this->returnHandler($retarr);
        }

        return $this->returnHandler(true); //No records returned.
    }

    /**
     * Get only the fields that are common across all records in the search criteria. Used for Mass Editing of records.
     * @param array $data filter data
     * @return array
     */
    public function getCommonUserContactData($data)
    {
        return Misc::arrayIntersectByRow($this->stripReturnHandler($this->getUserContact($data, true)));
    }

    /**
     * Validate user data for one or more users.
     * @param array $data user data
     * @return array
     */
    public function validateUserContact($data)
    {
        return $this->setUserContact($data, true);
    }

    /**
     * Set user data for one or more users.
     * @param array $data user data
     * @return array
     */
    public function setUserContact($data, $validate_only = false, $ignore_warning = true)
    {
        $validate_only = (bool)$validate_only;
        $ignore_warning = (bool)$ignore_warning;

        if (!is_array($data)) {
            return $this->returnHandler(false);
        }
        if (!$this->getPermissionObject()->Check('user_contact', 'enabled')
            or !($this->getPermissionObject()->Check('user_contact', 'edit') or $this->getPermissionObject()->Check('user_contact', 'edit_own') or $this->getPermissionObject()->Check('user_contact', 'edit_child') or $this->getPermissionObject()->Check('user_contact', 'add'))
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
        Debug::Text('Received data for: ' . $total_records . ' Users', __FILE__, __LINE__, __METHOD__, 10);
        //Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

        $validator_stats = array('total_records' => $total_records, 'valid_records' => 0);
        $validator = $save_result = false;
        if (is_array($data) and $total_records > 0) {
            $this->getProgressBarObject()->start($this->getAMFMessageID(), $total_records);

            foreach ($data as $key => $row) {
                $primary_validator = new Validator();
                $lf = TTnew('UserContactListFactory');
                $lf->StartTransaction();
                if (isset($row['id']) and $row['id'] > 0) {
                    //Modifying existing object.
                    //Get user object, so we can only modify just changed data for specific records if needed.
                    $lf->getByIdAndCompanyId($row['id'], $this->getCurrentCompanyObject()->getId());
                    if ($lf->getRecordCount() == 1) {
                        //Object exists, check edit permissions
                        //Debug::Text('User ID: '. $row['id'] .' Created By: '. $lf->getCurrent()->getCreatedBy() .' Is Owner: '. (int)$this->getPermissionObject()->isOwner( $lf->getCurrent()->getCreatedBy(), $lf->getCurrent()->getID() ) .' Is Child: '. (int)$this->getPermissionObject()->isChild( $lf->getCurrent()->getId(), $permission_children_ids ), __FILE__, __LINE__, __METHOD__, 10);
                        if (
                            $validate_only == true
                            or
                            (
                                $this->getPermissionObject()->Check('user_contact', 'edit')
                                or ($this->getPermissionObject()->Check('user_contact', 'edit_own') and $this->getPermissionObject()->isOwner($lf->getCurrent()->getCreatedBy(), $lf->getCurrent()->getUser()) === true)
                                or ($this->getPermissionObject()->Check('user_contact', 'edit_child') and $this->getPermissionObject()->isChild($lf->getCurrent()->getUser(), $permission_children_ids) === true)
                            )
                        ) {
                            Debug::Text('Row Exists, getting current data: ', $row['id'], __FILE__, __LINE__, __METHOD__, 10);
                            $lf = $lf->getCurrent(); //Make the current $lf variable the current object, so we can ignore some fields if needed.
                            $row = array_merge($lf->getObjectAsArray(), $row);
                        } else {
                            $primary_validator->isTrue('permission', false, TTi18n::gettext('Edit permission denied'));
                        }
                    } else {
                        //Object doesn't exist.
                        $primary_validator->isTrue('id', false, TTi18n::gettext('Edit permission denied, record does not exist'));
                    }
                } else {
                    //Adding new object, check ADD permissions.
                    if (!($validate_only == true
                        or
                        ($this->getPermissionObject()->Check('user_contact', 'add')
                            and
                            (
                                $this->getPermissionObject()->Check('user_contact', 'edit')
                                or (isset($row['user_id']) and $this->getPermissionObject()->Check('user_contact', 'edit_own') and $this->getPermissionObject()->isOwner(false, $row['user_id']) === true) //We don't know the created_by of the user at this point, but only check if the user is assigned to the logged in person.
                                or (isset($row['user_id']) and $this->getPermissionObject()->Check('user_contact', 'edit_child') and $this->getPermissionObject()->isChild($row['user_id'], $permission_children_ids) === true)
                            )
                        )
                    )
                    ) {
                        $primary_validator->isTrue('permission', false, TTi18n::gettext('Add permission denied'));
                    }
                }

                //Debug::Arr($row, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

                $is_valid = $primary_validator->isValid($ignore_warning);
                if ($is_valid == true) { //Check to see if all permission checks passed before trying to save data.
                    Debug::Text('Attempting to save data... AMF Message ID: ' . $this->getAMFMessageID(), __FILE__, __LINE__, __METHOD__, 10);

                    //If the user doesn't have permissions to change the hierarchy_control, unset that data.

                    //Force Company ID to current company.
                    if (!isset($row['company_id']) or !$this->getPermissionObject()->Check('company', 'add')) {
                        //$lf->setCompany( $this->getCurrentCompanyObject()->getId() );
                        $row['company_id'] = $this->getCurrentCompanyObject()->getId();
                    }

                    $lf->setObjectFromArray($row);

                    //Force Company ID to current company.
                    //$lf->setCompany( $this->getCurrentCompanyObject()->getId() );

                    $lf->Validator->setValidateOnly($validate_only);

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
                    //Always fail transaction when valididate only is used, as	is saved to different tables immediately.
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
     * Delete one or more users.
     * @param array $data user data
     * @return array
     */
    public function deleteUserContact($data)
    {
        if (is_numeric($data)) {
            $data = array($data);
        }

        if (!is_array($data)) {
            return $this->returnHandler(false);
        }

        if (!$this->getPermissionObject()->Check('user_contact', 'enabled')
            or !($this->getPermissionObject()->Check('user_contact', 'delete') or $this->getPermissionObject()->Check('user_contact', 'delete_own') or $this->getPermissionObject()->Check('user_contact', 'delete_child'))
        ) {
            return $this->getPermissionObject()->PermissionDenied();
        }

        //Get Permission Hierarchy Children first, as this can be used for viewing, or editing.
        $permission_children_ids = $this->getPermissionChildren();

        Debug::Text('Received data for: ' . count($data) . ' Users', __FILE__, __LINE__, __METHOD__, 10);
        Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

        $total_records = count($data);
        $validator = $save_result = false;
        $validator_stats = array('total_records' => $total_records, 'valid_records' => 0);
        if (is_array($data) and $total_records > 0) {
            $this->getProgressBarObject()->start($this->getAMFMessageID(), $total_records);

            foreach ($data as $key => $id) {
                $primary_validator = new Validator();
                $lf = TTnew('UserContactListFactory');
                $lf->StartTransaction();
                if (is_numeric($id)) {
                    //Modifying existing object.
                    //Get user object, so we can only modify just changed data for specific records if needed.
                    $lf->getByIdAndCompanyId($id, $this->getCurrentCompanyObject()->getId());
                    if ($lf->getRecordCount() == 1) {
                        //Object exists, check edit permissions
                        //Debug::Text('User ID: '. $user['id'] .' Created By: '. $lf->getCurrent()->getCreatedBy() .' Is Owner: '. (int)$this->getPermissionObject()->isOwner( $lf->getCurrent()->getCreatedBy(), $lf->getCurrent()->getID() ) .' Is Child: '. (int)$this->getPermissionObject()->isChild( $lf->getCurrent()->getId(), $permission_children_ids ), __FILE__, __LINE__, __METHOD__, 10);
                        if ($this->getPermissionObject()->Check('user_contact', 'delete')
                            or ($this->getPermissionObject()->Check('user_contact', 'delete_own') and $this->getPermissionObject()->isOwner($lf->getCurrent()->getCreatedBy(), $lf->getCurrent()->getUser()) === true)
                            or ($this->getPermissionObject()->Check('user_contact', 'delete_child') and $this->getPermissionObject()->isChild($lf->getCurrent()->getUser(), $permission_children_ids) === true)
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

    /**
     * Copy one or more users.
     * @param array $data user data
     * @return array
     */
    public function copyUserContact($data)
    {
        //Can only Copy as New, not just a regular copy, as too much data needs to be changed,
        //such as username, password, employee_number, SIN, first/last name address...
        return $this->returnHandler(false);
    }
}
