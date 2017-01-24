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
class APIUserWage extends APIFactory
{
    protected $main_class = 'UserWageFactory';

    public function __construct()
    {
        parent::__construct(); //Make sure parent constructor is always called.

        return true;
    }

    /**
     * Get default wage data for creating new wagees.
     * @return array
     */
    public function getUserWageDefaultData($user_id = null)
    {
        $company_obj = $this->getCurrentCompanyObject();

        Debug::Text('Getting wage default data...', __FILE__, __LINE__, __METHOD__, 10);

        //If user_id is passed, check for other wage entries, if none, default to the employees hire date.
        if ($user_id > 0) {
            //Check for existing wage entries.
            $uwlf = TTnew('UserWageListFactory');
            $uwlf->getLastWageByUserId($user_id);
            if ($uwlf->getRecordCount() == 1) {
                Debug::Text('Previous wage entry already exists...', __FILE__, __LINE__, __METHOD__, 10);
                $effective_date = time();
            } else {
                Debug::Text('Trying to use hire date...', __FILE__, __LINE__, __METHOD__, 10);
                $ulf = TTnew('UserListFactory');
                $ulf->getByIdAndCompanyId($user_id, $this->getCurrentCompanyObject()->getId());
                if ($ulf->getRecordCount() > 0) {
                    $effective_date = $ulf->getCurrent()->getHireDate();
                }
            }
        } else {
            Debug::Text('No user specified...', __FILE__, __LINE__, __METHOD__, 10);
            $effective_date = time();
        }

        $data = array(
            'company_id' => $company_obj->getId(),
            'type_id' => 10,
            'wage' => '0.00',
            'hourly_rate' => '0.00',
            'effective_date' => TTDate::getAPIDate('DATE', $effective_date),
            'labor_burden_percent' => 0,
            'weekly_time' => (3600 * 40), //40hrs/week
        );

        return $this->returnHandler($data);
    }

    /**
     * Export wage data to csv
     * @param array $data filter data
     * @param string $format file format (csv)
     * @return array
     */
    public function exportUserWage($format = 'csv', $data = null, $disable_paging = true)
    {
        $result = $this->stripReturnHandler($this->getUserWage($data, $disable_paging));
        return $this->exportRecords($format, 'export_wage', $result, ((isset($data['filter_columns'])) ? $data['filter_columns'] : null));
    }

    /**
     * Get wage data for one or more wagees.
     * @param array $data filter data
     * @return array
     */
    public function getUserWage($data = null, $disable_paging = false, $last_user_wage_only = false)
    {
        if (!$this->getPermissionObject()->Check('wage', 'enabled')
            or !($this->getPermissionObject()->Check('wage', 'view') or $this->getPermissionObject()->Check('wage', 'view_own') or $this->getPermissionObject()->Check('wage', 'view_child'))
        ) {
            return $this->getPermissionObject()->PermissionDenied();
        }
        $data = $this->initializeFilterAndPager($data, $disable_paging);

        //Get Permission Hierarchy Children first, as this can be used for viewing, or editing.
        $data['filter_data']['permission_children_ids'] = $this->getPermissionObject()->getPermissionChildren('wage', 'view');

        $blf = TTnew('UserWageListFactory');
        if ($last_user_wage_only == true) {
            Debug::Text('Using APILastWageSearch...', __FILE__, __LINE__, __METHOD__, 10);
            $blf->getAPILastWageSearchByCompanyIdAndArrayCriteria($this->getCurrentCompanyObject()->getId(), $data['filter_data'], $data['filter_items_per_page'], $data['filter_page'], null, $data['filter_sort']);
        } else {
            $blf->getAPISearchByCompanyIdAndArrayCriteria($this->getCurrentCompanyObject()->getId(), $data['filter_data'], $data['filter_items_per_page'], $data['filter_page'], null, $data['filter_sort']);
        }
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

            return $this->returnHandler($retarr);
        }

        return $this->returnHandler(true); //No records returned.
    }

    /**
     * Get only the fields that are common across all records in the search criteria. Used for Mass Editing of records.
     * @param array $data filter data
     * @return array
     */
    public function getCommonUserWageData($data)
    {
        return Misc::arrayIntersectByRow($this->stripReturnHandler($this->getUserWage($data, true)));
    }

    /**
     * Validate wage data for one or more wagees.
     * @param array $data wage data
     * @return array
     */
    public function validateUserWage($data)
    {
        return $this->setUserWage($data, true, false);
    }

    /**
     * Set wage data for one or more wagees.
     * @param array $data wage data
     * @return array
     */
    public function setUserWage($data, $validate_only = false, $ignore_warning = true)
    {
        $validate_only = (bool)$validate_only;
        $ignore_warning = (bool)$ignore_warning;

        if (!is_array($data)) {
            return $this->returnHandler(false);
        }

        if (!$this->getPermissionObject()->Check('wage', 'enabled')
            or !($this->getPermissionObject()->Check('wage', 'edit') or $this->getPermissionObject()->Check('wage', 'edit_own') or $this->getPermissionObject()->Check('wage', 'edit_child') or $this->getPermissionObject()->Check('wage', 'add'))
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
        Debug::Text('Received data for: ' . $total_records . ' UserWages', __FILE__, __LINE__, __METHOD__, 10);
        Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

        $validator_stats = array('total_records' => $total_records, 'valid_records' => 0);
        $validator = $save_result = false;
        if (is_array($data) and $total_records > 0) {
            $this->getProgressBarObject()->start($this->getAMFMessageID(), $total_records);

            foreach ($data as $key => $row) {
                $primary_validator = new Validator();
                $lf = TTnew('UserWageListFactory');
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
                                $this->getPermissionObject()->Check('wage', 'edit')
                                or ($this->getPermissionObject()->Check('wage', 'edit_own') and $this->getPermissionObject()->isOwner($lf->getCurrent()->getCreatedBy(), $lf->getCurrent()->getUser()) === true)
                                or ($this->getPermissionObject()->Check('wage', 'edit_child') and $this->getPermissionObject()->isChild($lf->getCurrent()->getUser(), $permission_children_ids) === true)
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
                    if (!($validate_only == true
                        or
                        ($this->getPermissionObject()->Check('wage', 'add')
                            and
                            (
                                $this->getPermissionObject()->Check('wage', 'edit')
                                or (isset($row['user_id']) and $this->getPermissionObject()->Check('wage', 'edit_own') and $this->getPermissionObject()->isOwner(false, $row['user_id']) === true) //We don't know the created_by of the user at this point, but only check if the user is assigned to the logged in person.
                                or (isset($row['user_id']) and $this->getPermissionObject()->Check('wage', 'edit_child') and $this->getPermissionObject()->isChild($row['user_id'], $permission_children_ids) === true)
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
                    Debug::Text('Setting object data...', __FILE__, __LINE__, __METHOD__, 10);

                    $lf->setObjectFromArray($row);

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
     * Delete one or more wages.
     * @param array $data wage data
     * @return array
     */
    public function deleteUserWage($data)
    {
        if (is_numeric($data)) {
            $data = array($data);
        }

        if (!is_array($data)) {
            return $this->returnHandler(false);
        }

        if (!$this->getPermissionObject()->Check('wage', 'enabled')
            or !($this->getPermissionObject()->Check('wage', 'delete') or $this->getPermissionObject()->Check('wage', 'delete_own') or $this->getPermissionObject()->Check('wage', 'delete_child'))
        ) {
            return $this->getPermissionObject()->PermissionDenied();
        }

        //Get Permission Hierarchy Children first, as this can be used for viewing, or editing.
        $permission_children_ids = $this->getPermissionChildren();

        Debug::Text('Received data for: ' . count($data) . ' UserWages', __FILE__, __LINE__, __METHOD__, 10);
        Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

        $total_records = count($data);
        $validator = $save_result = false;
        $validator_stats = array('total_records' => $total_records, 'valid_records' => 0);
        if (is_array($data) and $total_records > 0) {
            $this->getProgressBarObject()->start($this->getAMFMessageID(), $total_records);

            foreach ($data as $key => $id) {
                $primary_validator = new Validator();
                $lf = TTnew('UserWageListFactory');
                $lf->StartTransaction();
                if (is_numeric($id)) {
                    //Modifying existing object.
                    //Get wage object, so we can only modify just changed data for specific records if needed.
                    $lf->getByIdAndCompanyId($id, $this->getCurrentCompanyObject()->getId());
                    if ($lf->getRecordCount() == 1) {
                        //Object exists, check edit permissions
                        if ($this->getPermissionObject()->Check('wage', 'delete')
                            or ($this->getPermissionObject()->Check('wage', 'delete_own') and $this->getPermissionObject()->isOwner($lf->getCurrent()->getCreatedBy(), $lf->getCurrent()->getUser()) === true)
                            or ($this->getPermissionObject()->Check('wage', 'delete_child') and $this->getPermissionObject()->isChild($lf->getCurrent()->getUser(), $permission_children_ids) === true)
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
     * Copy one or more wagees.
     * @param array $data wage IDs
     * @return array
     */
    public function copyUserWage($data)
    {
        if (is_numeric($data)) {
            $data = array($data);
        }

        if (!is_array($data)) {
            return $this->returnHandler(false);
        }

        Debug::Text('Received data for: ' . count($data) . ' UserWages', __FILE__, __LINE__, __METHOD__, 10);
        Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

        $src_rows = $this->stripReturnHandler($this->getUserWage(array('filter_data' => array('id' => $data)), true));
        if (is_array($src_rows) and count($src_rows) > 0) {
            Debug::Arr($src_rows, 'SRC Rows: ', __FILE__, __LINE__, __METHOD__, 10);
            foreach ($src_rows as $key => $row) {
                unset($src_rows[$key]['id']); //Clear fields that can't be copied
            }
            unset($row); //code standards
            //Debug::Arr($src_rows, 'bSRC Rows: ', __FILE__, __LINE__, __METHOD__, 10);

            return $this->setUserWage($src_rows); //Save copied rows
        }

        return $this->returnHandler(false);
    }

    /**
     * Calculate salaried employees hourly rate based on wage and weekly hours.
     * @param float $wage Wage
     * @param float $weekly_hours Weekly Hours
     * @param int $wage_type_id Wage Type ID
     * @return float
     */
    public function getHourlyRate($wage, $weekly_hours, $wage_type_id = 10)
    {
        if ($wage == '') {
            return '0.00';
        }

        if ($weekly_hours == '') {
            return '0.00';
        }

        if ($wage_type_id == '') {
            return '0.00';
        }

        //FIXME: Pass user_id and/or currency_id so we can properly round to the right number of decimals.
        $uwf = TTnew('UserWageFactory');
        $uwf->setType($wage_type_id);
        //$uwf->setWage( TTi18n::parseFloat( $wage ) );
        $uwf->setWage($wage);
        $uwf->setWeeklyTime(TTDate::parseTimeUnit($weekly_hours));
        //$hourly_rate = TTi18n::formatNumber( $uwf->calcHourlyRate(), TRUE, 2, 4 );
        $hourly_rate = $uwf->calcHourlyRate();

        return $this->returnHandler($hourly_rate);
    }
}
