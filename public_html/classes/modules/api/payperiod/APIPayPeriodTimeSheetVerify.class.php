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
 * @package API\PayPeriod
 */
class APIPayPeriodTimeSheetVerify extends APIFactory
{
    protected $main_class = 'PayPeriodTimeSheetVerifyFactory';

    public function __construct()
    {
        parent::__construct(); //Make sure parent constructor is always called.

        return true;
    }

    /**
     * Get default pay_period_timesheet_verify data for creating new pay_period_timesheet_verifyes.
     * @return array
     */
    public function getPayPeriodTimeSheetVerifyDefaultData()
    {
        Debug::Text('Getting pay_period_timesheet_verify default data...', __FILE__, __LINE__, __METHOD__, 10);

        $data = array();

        return $this->returnHandler($data);
    }

    /**
     * Get hierarchy_level and hierarchy_control_ids for authorization list.
     * @param integer $object_type_id hierarchy object_type_id
     * @return array
     */
    public function getHierarchyLevelOptions($type_id = null)
    {
        //Ignore type_id argument, as there is only one for this.

        $hl = new APIHierarchyLevel();
        return $hl->getHierarchyLevelOptions(array(90));
    }

    /**
     * @param string $format
     * @param null $data
     * @param bool $disable_paging
     * @return array|bool
     */
    public function exportPayPeriodTimeSheetVerify($format = 'csv', $data = null, $disable_paging = true)
    {
        $result = $this->stripReturnHandler($this->getPayPeriodTimeSheetVerify($data, $disable_paging));
        return $this->exportRecords($format, 'export_timesheet_verify', $result, ((isset($data['filter_columns'])) ? $data['filter_columns'] : null));
    }

    /**
     * Get pay_period_timesheet_verify data for one or more pay_period_timesheet_verifyes.
     * @param array $data filter data
     * @return array
     */
    public function getPayPeriodTimeSheetVerify($data = null, $disable_paging = false)
    {
        if (!$this->getPermissionObject()->Check('punch', 'enabled')
            or !($this->getPermissionObject()->Check('punch', 'view') or $this->getPermissionObject()->Check('punch', 'view_own') or $this->getPermissionObject()->Check('punch', 'view_child'))
        ) {
            return $this->getPermissionObject()->PermissionDenied();
        }
        $data = $this->initializeFilterAndPager($data, $disable_paging);

        //If type_id and hierarchy_level is passed, assume we are in the authorization view.
        if (isset($data['filter_data']['hierarchy_level'])
            and ($this->getPermissionObject()->Check('authorization', 'enabled')
                and $this->getPermissionObject()->Check('authorization', 'view')
                and $this->getPermissionObject()->Check('punch', 'authorize'))
        ) {

            //FIXME: If type_id = -1 (ANY) is used, it may show more requests then if type_id is specified to a specific ID.
            //This is because if the hierarchy objects are changed when pending requests exist, the ANY type_id will catch them and display them,
            //But if you filter on type_id = <specific value> as well a specific hierarchy level, it may exclude them.

            $hllf = TTnew('HierarchyLevelListFactory');
            $hierarchy_level_arr = $hllf->getLevelsAndHierarchyControlIDsByUserIdAndObjectTypeID($this->getCurrentUserObject()->getId(), 90);
            //Debug::Arr( $hierarchy_level_arr, 'Hierarchy Levels: ', __FILE__, __LINE__, __METHOD__, 10);

            $data['filter_data']['hierarchy_level_map'] = false;
            if (isset($data['filter_data']['hierarchy_level']) and isset($hierarchy_level_arr[$data['filter_data']['hierarchy_level']])) {
                $data['filter_data']['hierarchy_level_map'] = $hierarchy_level_arr[$data['filter_data']['hierarchy_level']];
            } elseif (isset($hierarchy_level_arr[1])) {
                $data['filter_data']['hierarchy_level_map'] = $hierarchy_level_arr[1];
            }
            unset($hierarchy_level_arr);

            //Force other filter settings for authorization view.
            $data['filter_data']['authorized'] = array(0);
            //$data['filter_data']['status_id'] = array(30);
        } else {
            Debug::Text('Not using authorization criteria...', __FILE__, __LINE__, __METHOD__, 10);
        }

        //Is this to too restrictive when authorizing requests, as they have to be in the permission hierarchy as well as the request hierarchy?
        $data['filter_data']['permission_children_ids'] = $this->getPermissionObject()->getPermissionChildren('punch', 'view');

        $blf = TTnew('PayPeriodTimeSheetVerifyListFactory');
        $blf->getAPIAuthorizationSearchByCompanyIdAndArrayCriteria($this->getCurrentCompanyObject()->getId(), $data['filter_data'], $data['filter_items_per_page'], $data['filter_page'], null, $data['filter_sort']);
        Debug::Text('Record Count: ' . $blf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
        if ($blf->getRecordCount() > 0) {
            $this->getProgressBarObject()->start($this->getAMFMessageID(), $blf->getRecordCount());

            $this->setPagerObject($blf);

            $retarr = array();
            foreach ($blf as $b_obj) {
                $retarr[] = $b_obj->getObjectAsArray($data['filter_columns']);

                $this->getProgressBarObject()->set($this->getAMFMessageID(), $blf->getCurrentRow());
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
    public function getCommonPayPeriodTimeSheetVerifyData($data)
    {
        return Misc::arrayIntersectByRow($this->stripReturnHandler($this->getPayPeriodTimeSheetVerify($data, true)));
    }

    /**
     * Validate pay_period_timesheet_verify data for one or more pay_period_timesheet_verifyes.
     * @param array $data pay_period_timesheet_verify data
     * @return array
     */
    public function validatePayPeriodTimeSheetVerify($data)
    {
        return $this->setPayPeriodTimeSheetVerify($data, true);
    }

    /**
     * Set pay_period_timesheet_verify data for one or more pay_period_timesheet_verifyes.
     * @param array $data pay_period_timesheet_verify data
     * @return array
     */
    public function setPayPeriodTimeSheetVerify($data, $validate_only = false, $ignore_warning = true)
    {
        $validate_only = (bool)$validate_only;
        $ignore_warning = (bool)$ignore_warning;

        if (!is_array($data)) {
            return $this->returnHandler(false);
        }

        if (!$this->getPermissionObject()->Check('punch', 'enabled')
            or !($this->getPermissionObject()->Check('punch', 'edit') or $this->getPermissionObject()->Check('punch', 'edit_own') or $this->getPermissionObject()->Check('punch', 'edit_child') or $this->getPermissionObject()->Check('punch', 'add'))
        ) {
            return $this->getPermissionObject()->PermissionDenied();
        }

        if ($validate_only == true) {
            Debug::Text('Validating Only!', __FILE__, __LINE__, __METHOD__, 10);
        }

        extract($this->convertToMultipleRecords($data));
        Debug::Text('Received data for: ' . $total_records . ' PayPeriodTimeSheetVerifys', __FILE__, __LINE__, __METHOD__, 10);
        Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

        $validator_stats = array('total_records' => $total_records, 'valid_records' => 0);
        $validator = $save_result = false;
        if (is_array($data) and $total_records > 0) {
            $this->getProgressBarObject()->start($this->getAMFMessageID(), $total_records);

            foreach ($data as $key => $row) {
                $primary_validator = new Validator();
                $lf = TTnew('PayPeriodTimeSheetVerifyListFactory');
                $lf->StartTransaction();
                if (isset($row['id']) and $row['id'] > 0) {
                    //Modifying existing object.
                    //Get pay_period_timesheet_verify object, so we can only modify just changed data for specific records if needed.
                    $lf->getByIdAndCompanyId($row['id'], $this->getCurrentCompanyObject()->getId());
                    if ($lf->getRecordCount() == 1) {
                        //Object exists, check edit permissions
                        if (
                            $validate_only == true
                            or
                            (
                                $this->getPermissionObject()->Check('punch', 'edit')
                                or ($this->getPermissionObject()->Check('punch', 'edit_own') and $this->getPermissionObject()->isOwner($lf->getCurrent()->getCreatedBy(), $lf->getCurrent()->getID()) === true)
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
                    $primary_validator->isTrue('permission', $this->getPermissionObject()->Check('punch', 'add'), TTi18n::gettext('Add permission denied'));
                }
                Debug::Arr($row, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

                $is_valid = $primary_validator->isValid($ignore_warning);
                if ($is_valid == true) { //Check to see if all permission checks passed before trying to save data.
                    Debug::Text('Setting object data...', __FILE__, __LINE__, __METHOD__, 10);

                    $lf->setObjectFromArray($row);

                    //Force current user.
                    $lf->setCurrentUser($this->getCurrentUserObject()->getID());

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
     * Delete one or more pay_period_timesheet_verifys.
     * @param array $data pay_period_timesheet_verify data
     * @return array
     */
    public function deletePayPeriodTimeSheetVerify($data)
    {
        if (is_numeric($data)) {
            $data = array($data);
        }

        if (!is_array($data)) {
            return $this->returnHandler(false);
        }

        if (!$this->getPermissionObject()->Check('punch', 'enabled')
            or !($this->getPermissionObject()->Check('punch', 'delete') or $this->getPermissionObject()->Check('punch', 'delete_own') or $this->getPermissionObject()->Check('punch', 'delete_child'))
        ) {
            return $this->getPermissionObject()->PermissionDenied();
        }

        Debug::Text('Received data for: ' . count($data) . ' PayPeriodTimeSheetVerifys', __FILE__, __LINE__, __METHOD__, 10);
        Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

        $total_records = count($data);
        $validator = $save_result = false;
        $validator_stats = array('total_records' => $total_records, 'valid_records' => 0);
        if (is_array($data) and $total_records > 0) {
            $this->getProgressBarObject()->start($this->getAMFMessageID(), $total_records);

            foreach ($data as $key => $id) {
                $primary_validator = new Validator();
                $lf = TTnew('PayPeriodTimeSheetVerifyListFactory');
                $lf->StartTransaction();
                if (is_numeric($id)) {
                    //Modifying existing object.
                    //Get pay_period_timesheet_verify object, so we can only modify just changed data for specific records if needed.
                    $lf->getByIdAndCompanyId($id, $this->getCurrentCompanyObject()->getId());
                    if ($lf->getRecordCount() == 1) {
                        //Object exists, check edit permissions
                        if ($this->getPermissionObject()->Check('punch', 'delete')
                            or ($this->getPermissionObject()->Check('punch', 'delete_own') and $this->getPermissionObject()->isOwner($lf->getCurrent()->getCreatedBy(), $lf->getCurrent()->getID()) === true)
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
     * Copy one or more pay_period_timesheet_verifyes.
     * @param array $data pay_period_timesheet_verify IDs
     * @return array
     */
    public function copyPayPeriodTimeSheetVerify($data)
    {
        if (is_numeric($data)) {
            $data = array($data);
        }

        if (!is_array($data)) {
            return $this->returnHandler(false);
        }

        Debug::Text('Received data for: ' . count($data) . ' PayPeriodTimeSheetVerifys', __FILE__, __LINE__, __METHOD__, 10);
        Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

        $src_rows = $this->stripReturnHandler($this->getPayPeriodTimeSheetVerify(array('filter_data' => array('id' => $data)), true));
        if (is_array($src_rows) and count($src_rows) > 0) {
            Debug::Arr($src_rows, 'SRC Rows: ', __FILE__, __LINE__, __METHOD__, 10);
            foreach ($src_rows as $key => $row) {
                unset($src_rows[$key]['id'], $src_rows[$key]['manual_id']); //Clear fields that can't be copied
                $src_rows[$key]['name'] = Misc::generateCopyName($row['name']); //Generate unique name
            }
            //Debug::Arr($src_rows, 'bSRC Rows: ', __FILE__, __LINE__, __METHOD__, 10);

            return $this->setPayPeriodTimeSheetVerify($src_rows); //Save copied rows
        }

        return $this->returnHandler(false);
    }
}
