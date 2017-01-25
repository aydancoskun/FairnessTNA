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
class APIUserTitle extends APIFactory
{
    protected $main_class = 'UserTitleFactory';

    public function __construct()
    {
        parent::__construct(); //Make sure parent constructor is always called.

        return true;
    }

    /**
     * Get default UserTitle data for creating new UserTitlees.
     * @return array
     */
    public function getUserTitleDefaultData()
    {
        $company_obj = $this->getCurrentCompanyObject();

        Debug::Text('Getting UserTitle default data...', __FILE__, __LINE__, __METHOD__, 10);

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
    public function exportUserTitle($format = 'csv', $data = null, $disable_paging = true)
    {
        $result = $this->stripReturnHandler($this->getUserTitle($data, $disable_paging));
        return $this->exportRecords($format, 'export_employee_title', $result, ((isset($data['filter_columns'])) ? $data['filter_columns'] : null));
    }

    /**
     * Get UserTitle data for one or more UserTitlees.
     * @param array $data filter data
     * @return array
     */
    public function getUserTitle($data = null, $disable_paging = false)
    {
        if (!$this->getPermissionObject()->Check('user', 'enabled')
            or !($this->getPermissionObject()->Check('user', 'view') or $this->getPermissionObject()->Check('user', 'view_own') or $this->getPermissionObject()->Check('user', 'view_child'))
        ) {
            $data['filter_columns'] = $this->handlePermissionFilterColumns((isset($data['filter_columns'])) ? $data['filter_columns'] : null, Misc::trimSortPrefix($this->getOptions('list_columns')));
        }
        $data = $this->initializeFilterAndPager($data, $disable_paging);

        //Allow supervisor (subordinates only) to see all job titles.
        //$data['filter_data']['permission_children_ids'] = $this->getPermissionObject()->getPermissionChildren( 'user', 'view' );

        //Allow getting users from other companies, so we can change admin contacts when using the master company.
        if (isset($data['filter_data']['company_id'])
            and $data['filter_data']['company_id'] > 0
            and ($this->getPermissionObject()->Check('company', 'enabled') and $this->getPermissionObject()->Check('company', 'view'))
        ) {
            $company_id = $data['filter_data']['company_id'];
        } else {
            $company_id = $this->getCurrentCompanyObject()->getId();
        }

        $utlf = TTnew('UserTitleListFactory');
        $utlf->getAPISearchByCompanyIdAndArrayCriteria($company_id, $data['filter_data'], $data['filter_items_per_page'], $data['filter_page'], null, $data['filter_sort']);
        Debug::Text('Record Count: ' . $utlf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
        if ($utlf->getRecordCount() > 0) {
            $this->setPagerObject($utlf);

            $retarr = array();
            foreach ($utlf as $ut_obj) {
                $retarr[] = $ut_obj->getObjectAsArray($data['filter_columns']);
            }

            return $this->returnHandler($retarr);
        }

        return $this->returnHandler(true); //No records returned.
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
            and (!$this->getPermissionObject()->Check('user', 'enabled')
                or !($this->getPermissionObject()->Check('user', 'view') or $this->getPermissionObject()->Check('user', 'view_own') or $this->getPermissionObject()->Check('user', 'view_child')))
        ) {
            $name = 'list_columns';
        }

        return parent::getOptions($name, $parent);
    }

    /**
     * Get only the fields that are common across all records in the search criteria. Used for Mass Editing of records.
     * @param array $data filter data
     * @return array
     */
    public function getCommonUserTitleData($data)
    {
        return Misc::arrayIntersectByRow($this->stripReturnHandler($this->getUserTitle($data, true)));
    }

    /**
     * Validate UserTitle data for one or more UserTitlees.
     * @param array $data UserTitle data
     * @return array
     */
    public function validateUserTitle($data)
    {
        return $this->setUserTitle($data, true);
    }

    /**
     * Set UserTitle data for one or more UserTitlees.
     * @param array $data UserTitle data
     * @return array
     */
    public function setUserTitle($data, $validate_only = false, $ignore_warning = true)
    {
        $validate_only = (bool)$validate_only;
        $ignore_warning = (bool)$ignore_warning;

        if (!is_array($data)) {
            return $this->returnHandler(false);
        }

        if (!$this->getPermissionObject()->Check('user', 'enabled')
            or !($this->getPermissionObject()->Check('user', 'edit') or $this->getPermissionObject()->Check('user', 'edit_own') or $this->getPermissionObject()->Check('user', 'edit_child') or $this->getPermissionObject()->Check('user', 'add'))
        ) {
            return $this->getPermissionObject()->PermissionDenied();
        }

        if ($validate_only == true) {
            Debug::Text('Validating Only!', __FILE__, __LINE__, __METHOD__, 10);
        }

        extract($this->convertToMultipleRecords($data));
        Debug::Text('Received data for: ' . $total_records . ' UserTitles', __FILE__, __LINE__, __METHOD__, 10);
        Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

        $validator_stats = array('total_records' => $total_records, 'valid_records' => 0);
        $validator = $save_result = false;
        if (is_array($data) and $total_records > 0) {
            foreach ($data as $key => $row) {
                $primary_validator = new Validator();
                $lf = TTnew('UserTitleListFactory');
                $lf->StartTransaction();
                if (isset($row['id']) and $row['id'] > 0) {
                    //Modifying existing object.
                    //Get UserTitle object, so we can only modify just changed data for specific records if needed.
                    $lf->getByIdAndCompanyId($row['id'], $this->getCurrentCompanyObject()->getId());
                    if ($lf->getRecordCount() == 1) {
                        //Object exists, check edit permissions
                        if (
                            $validate_only == true
                            or
                            (
                                $this->getPermissionObject()->Check('user', 'edit')
                                or ($this->getPermissionObject()->Check('user', 'edit_own') and $this->getPermissionObject()->isOwner($lf->getCurrent()->getCreatedBy(), $lf->getCurrent()->getID()) === true)
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
                } else {
                    //Adding new object, check ADD permissions.
                    $primary_validator->isTrue('permission', $this->getPermissionObject()->Check('user', 'add'), TTi18n::gettext('Add permission denied'));
                }
                Debug::Arr($row, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

                $is_valid = $primary_validator->isValid($ignore_warning);
                if ($is_valid == true) { //Check to see if all permission checks passed before trying to save data.
                    Debug::Text('Setting object data...', __FILE__, __LINE__, __METHOD__, 10);

                    //Force Company ID to current company.
                    $row['company_id'] = $this->getCurrentCompanyObject()->getId();

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
            }

            return $this->handleRecordValidationResults($validator, $validator_stats, $key, $save_result);
        }

        return $this->returnHandler(false);
    }

    /**
     * Delete one or more UserTitles.
     * @param array $data UserTitle data
     * @return array
     */
    public function deleteUserTitle($data)
    {
        if (is_numeric($data)) {
            $data = array($data);
        }

        if (!is_array($data)) {
            return $this->returnHandler(false);
        }

        if (!$this->getPermissionObject()->Check('user', 'enabled')
            or !($this->getPermissionObject()->Check('user', 'delete') or $this->getPermissionObject()->Check('user', 'delete_own') or $this->getPermissionObject()->Check('user', 'delete_child'))
        ) {
            return $this->getPermissionObject()->PermissionDenied();
        }

        Debug::Text('Received data for: ' . count($data) . ' UserTitles', __FILE__, __LINE__, __METHOD__, 10);
        Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

        $total_records = count($data);
        $validator = $save_result = false;
        $validator_stats = array('total_records' => $total_records, 'valid_records' => 0);
        if (is_array($data) and $total_records > 0) {
            foreach ($data as $key => $id) {
                $primary_validator = new Validator();
                $lf = TTnew('UserTitleListFactory');
                $lf->StartTransaction();
                if (is_numeric($id)) {
                    //Modifying existing object.
                    //Get UserTitle object, so we can only modify just changed data for specific records if needed.
                    $lf->getByIdAndCompanyId($id, $this->getCurrentCompanyObject()->getId());
                    if ($lf->getRecordCount() == 1) {
                        //Object exists, check edit permissions
                        if ($this->getPermissionObject()->Check('user', 'delete')
                            or ($this->getPermissionObject()->Check('user', 'delete_own') and $this->getPermissionObject()->isOwner($lf->getCurrent()->getCreatedBy(), $lf->getCurrent()->getID()) === true)
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
            }

            return $this->handleRecordValidationResults($validator, $validator_stats, $key, $save_result);
        }

        return $this->returnHandler(false);
    }

    /**
     * Copy one or more UserTitlees.
     * @param array $data UserTitle IDs
     * @return array
     */
    public function copyUserTitle($data)
    {
        if (is_numeric($data)) {
            $data = array($data);
        }

        if (!is_array($data)) {
            return $this->returnHandler(false);
        }

        Debug::Text('Received data for: ' . count($data) . ' UserTitles', __FILE__, __LINE__, __METHOD__, 10);
        Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

        $src_rows = $this->stripReturnHandler($this->getUserTitle(array('filter_data' => array('id' => $data)), true));
        if (is_array($src_rows) and count($src_rows) > 0) {
            Debug::Arr($src_rows, 'SRC Rows: ', __FILE__, __LINE__, __METHOD__, 10);
            foreach ($src_rows as $key => $row) {
                unset($src_rows[$key]['id'], $src_rows[$key]['manual_id']); //Clear fields that can't be copied
                $src_rows[$key]['name'] = Misc::generateCopyName($row['name']); //Generate unique name
            }
            //Debug::Arr($src_rows, 'bSRC Rows: ', __FILE__, __LINE__, __METHOD__, 10);

            return $this->setUserTitle($src_rows); //Save copied rows
        }

        return $this->returnHandler(false);
    }
}
