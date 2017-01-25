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
 * @package API\Core
 */
class APIUserDateTotal extends APIFactory
{
    protected $main_class = 'UserDateTotalFactory';

    public function __construct()
    {
        parent::__construct(); //Make sure parent constructor is always called.

        return true;
    }

    /**
     * Get default user_date_total data for creating new user_date_totales.
     * @return array
     */
    public function getUserDateTotalDefaultData($user_id = null, $date = null)
    {
        $company_obj = $this->getCurrentCompanyObject();

        Debug::Text('Getting user_date_total default data...', __FILE__, __LINE__, __METHOD__, 10);

        $data = array(
            'currency_id' => $this->getCurrentUserObject()->getCurrency(),
            'branch_id' => $this->getCurrentUserObject()->getDefaultBranch(),
            'department_id' => $this->getCurrentUserObject()->getDefaultDepartment(),
            'total_time' => 0,
            'base_hourly_rate' => 0,
            'hourly_rate' => 0,
            'override' => true,
        );

        //If user_id is specified, use their default branch/department.
        $ulf = TTnew('UserListFactory');
        $ulf->getByIdAndCompanyId($user_id, $company_obj->getID());
        if ($ulf->getRecordCount() == 1) {
            $user_obj = $ulf->getCurrent();

            $data['user_id'] = $user_obj->getID();
            $data['branch_id'] = $user_obj->getDefaultBranch();
            $data['department_id'] = $user_obj->getDefaultDepartment();
            $data['job_id'] = $user_obj->getDefaultJob();
            $data['job_item_id'] = $user_obj->getDefaultJobItem();

            $uwlf = TTnew('UserWageListFactory');
            $uwlf->getByUserIdAndGroupIDAndBeforeDate($user_id, 0, TTDate::parseDateTime($date), 1);
            if ($uwlf->getRecordCount() > 0) {
                foreach ($uwlf as $uw_obj) {
                    $data['base_hourly_rate'] = $data['hourly_rate'] = $uw_obj->getHourlyRate();
                }
            }
            unset($uwlf, $uw_obj);
        }
        unset($ulf, $user_obj);

        Debug::Arr($data, 'Default data: ', __FILE__, __LINE__, __METHOD__, 10);
        return $this->returnHandler($data);
    }

    /**
     * Get combined recurring user_date_total and committed user_date_total data for one or more user_date_totales.
     * @param array $data filter data
     * @return array
     */
    public function getCombinedUserDateTotal($data = null)
    {
        if (!$this->getPermissionObject()->Check('punch', 'enabled')
            or !($this->getPermissionObject()->Check('punch', 'view') or $this->getPermissionObject()->Check('punch', 'view_child'))
        ) {
            return $this->getPermissionObject()->PermissionDenied();
        }

        $data = $this->initializeFilterAndPager($data);

        $sf = TTnew('UserDateTotalFactory');
        $retarr = $sf->getUserDateTotalArray($data['filter_data']);

        Debug::Arr($retarr, 'RetArr: ', __FILE__, __LINE__, __METHOD__, 10);

        return $this->returnHandler($retarr);
    }

    /**
     * Get only the fields that are common across all records in the search criteria. Used for Mass Editing of records.
     * @param array $data filter data
     * @return array
     */
    public function getCommonUserDateTotalData($data)
    {
        return Misc::arrayIntersectByRow($this->stripReturnHandler($this->getUserDateTotal($data, true)));
    }

    /**
     * Get user_date_total data for one or more user_date_totales.
     * @param array $data filter data
     * @return array
     */
    public function getUserDateTotal($data = null, $disable_paging = false)
    {
        //if ( !$this->getPermissionObject()->Check('punch', 'enabled')
        //OR !( $this->getPermissionObject()->Check('punch', 'view') OR $this->getPermissionObject()->Check('punch', 'view_child')	) ) {

        //Regular employees with permissions to edit their own absences need this.
        if (!$this->getPermissionObject()->Check('punch', 'enabled')
            or !($this->getPermissionObject()->Check('punch', 'view') or $this->getPermissionObject()->Check('punch', 'view_own') or $this->getPermissionObject()->Check('punch', 'view_child'))
        ) {
            return $this->getPermissionObject()->PermissionDenied();
        }

        $data = $this->initializeFilterAndPager($data, $disable_paging);

        //Parse date string sent by HTML5 interface for searching.
        if (isset($data['filter_data']['date_stamp'])) {
            $data['filter_data']['date_stamp'] = TTDate::getMiddleDayEpoch(TTDate::parseDateTime($data['filter_data']['date_stamp']));
        }

        if (isset($data['filter_data']['start_date'])) {
            $data['filter_data']['start_date'] = TTDate::parseDateTime($data['filter_data']['start_date']);
        }

        if (isset($data['filter_data']['end_date'])) {
            $data['filter_data']['end_date'] = TTDate::parseDateTime($data['filter_data']['end_date']);
        }

        //This can be used to edit Absences as well, how do we differentiate between them?
        $data['filter_data']['permission_children_ids'] = $this->getPermissionObject()->getPermissionChildren('punch', 'view');

        $blf = TTnew('UserDateTotalListFactory');
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

            return $this->returnHandler($retarr);
        }

        return $this->returnHandler(true); //No records returned.
    }

    /**
     * Validate user_date_total data for one or more user_date_totales.
     * @param array $data user_date_total data
     * @return array
     */
    public function validateUserDateTotal($data)
    {
        return $this->setUserDateTotal($data, true);
    }

    /**
     * Set user_date_total data for one or more user_date_totales.
     * @param array $data user_date_total data
     * @return array
     */
    public function setUserDateTotal($data, $validate_only = false, $ignore_warning = true)
    {
        $validate_only = (bool)$validate_only;
        $ignore_warning = (bool)$ignore_warning;

        if (!is_array($data)) {
            return $this->returnHandler(false);
        }

        if (!($this->getPermissionObject()->Check('punch', 'enabled') or $this->getPermissionObject()->Check('absence', 'enabled'))
            or !($this->getPermissionObject()->Check('punch', 'edit') or $this->getPermissionObject()->Check('punch', 'edit_own') or $this->getPermissionObject()->Check('punch', 'edit_child') or $this->getPermissionObject()->Check('punch', 'add'))
            or !($this->getPermissionObject()->Check('absence', 'edit') or $this->getPermissionObject()->Check('absence', 'edit_own') or $this->getPermissionObject()->Check('absence', 'edit_child') or $this->getPermissionObject()->Check('absence', 'add'))
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
        Debug::Text('Received data for: ' . $total_records . ' UserDateTotals', __FILE__, __LINE__, __METHOD__, 10);
        Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

        $validator_stats = array('total_records' => $total_records, 'valid_records' => 0);
        $validator = $save_result = false;
        if (is_array($data) and $total_records > 0) {
            $this->getProgressBarObject()->start($this->getAMFMessageID(), $total_records);

            //Wrap entire batch in the transaction.
            $lf = TTnew('UserDateTotalListFactory');
            $lf->StartTransaction();

            $recalculate_user_date_stamp = false;
            foreach ($data as $key => $row) {
                $primary_validator = new Validator();
                $lf = TTnew('UserDateTotalListFactory');
                //$lf->StartTransaction();
                if (isset($row['id']) and $row['id'] > 0) {
                    //Modifying existing object.
                    //Get user_date_total object, so we can only modify just changed data for specific records if needed.
                    $lf->getByIdAndCompanyId($row['id'], $this->getCurrentCompanyObject()->getId());
                    if ($lf->getRecordCount() == 1) {
                        //Object exists, check edit permissions
                        if (
                            $validate_only == true
                            or
                            (
                                $this->getPermissionObject()->Check('punch', 'edit')
                                or ($this->getPermissionObject()->Check('punch', 'edit_own') and $this->getPermissionObject()->isOwner($lf->getCurrent()->getCreatedBy(), $lf->getCurrent()->getUser()) === true)
                                or ($this->getPermissionObject()->Check('punch', 'edit_child') and $this->getPermissionObject()->isChild($lf->getCurrent()->getUser(), $permission_children_ids) === true)
                            )
                            or
                            (
                                $this->getPermissionObject()->Check('absence', 'edit')
                                or ($this->getPermissionObject()->Check('absence', 'edit_own') and $this->getPermissionObject()->isOwner($lf->getCurrent()->getCreatedBy(), $lf->getCurrent()->getUser()) === true)
                                or ($this->getPermissionObject()->Check('absence', 'edit_child') and $this->getPermissionObject()->isChild($lf->getCurrent()->getUser(), $permission_children_ids) === true)
                            )
                        ) {
                            Debug::Text('Row Exists, getting current data: ', $row['id'], __FILE__, __LINE__, __METHOD__, 10);
                            $lf = $lf->getCurrent();

                            //When editing a record if the date changes, we need to recalculate the old date.
                            //This must occur before we merge the data together.
                            if ((isset($row['user_id'])
                                    and $lf->getUser() != $row['user_id'])
                                or
                                (isset($row['date_stamp'])
                                    and $lf->getDateStamp()
                                    and TTDate::parseDateTime($row['date_stamp']) != $lf->getDateStamp())
                            ) {
                                Debug::Text('Date has changed, recalculate old date... New: [ Date: ' . $row['date_stamp'] . ' ] UserID: ' . $lf->getUser(), __FILE__, __LINE__, __METHOD__, 10);
                                $recalculate_user_date_stamp[$lf->getUser()][] = TTDate::getMiddleDayEpoch($lf->getDateStamp()); //Help avoid confusion with different timezones/DST.
                            }

                            //Since switching to batch calculation mode, need to store every possible date to recalculate.
                            if (isset($row['user_id']) and $row['user_id'] != '' and isset($row['date_stamp']) and $row['date_stamp'] != '') {
                                //Since switching to batch calculation mode, need to store every possible date to recalculate.
                                $recalculate_user_date_stamp[(int)$row['user_id']][] = TTDate::getMiddleDayEpoch(TTDate::parseDateTime($row['date_stamp'])); //Help avoid confusion with different timezones/DST.
                            }
                            $recalculate_user_date_stamp[$lf->getUser()][] = TTDate::getMiddleDayEpoch($lf->getDateStamp()); //Help avoid confusion with different timezones/DST.

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
                        ($this->getPermissionObject()->Check('punch', 'add')
                            and
                            (
                                $this->getPermissionObject()->Check('punch', 'edit')
                                or (isset($row['user_id']) and $this->getPermissionObject()->Check('punch', 'edit_own') and $this->getPermissionObject()->isOwner(false, $row['user_id']) === true) //We don't know the created_by of the user at this point, but only check if the user is assigned to the logged in person.
                                or (isset($row['user_id']) and $this->getPermissionObject()->Check('punch', 'edit_child') and $this->getPermissionObject()->isChild($row['user_id'], $permission_children_ids) === true)
                            )
                        )
                        or
                        ($this->getPermissionObject()->Check('absence', 'add')
                            and
                            (
                                $this->getPermissionObject()->Check('absence', 'edit')
                                or (isset($row['user_id']) and $this->getPermissionObject()->Check('absence', 'edit_own') and $this->getPermissionObject()->isOwner(false, $row['user_id']) === true) //We don't know the created_by of the user at this point, but only check if the user is assigned to the logged in person.
                                or (isset($row['user_id']) and $this->getPermissionObject()->Check('absence', 'edit_child') and $this->getPermissionObject()->isChild($row['user_id'], $permission_children_ids) === true)
                            )
                        )
                    )
                    ) {
                        $primary_validator->isTrue('permission', false, TTi18n::gettext('Add permission denied'));
                    } else {
                        if (isset($row['user_id']) and $row['user_id'] != '' and isset($row['date_stamp']) and $row['date_stamp'] != '') {
                            //Since switching to batch calculation mode, need to store every possible date to recalculate.
                            $recalculate_user_date_stamp[(int)$row['user_id']][] = TTDate::getMiddleDayEpoch(TTDate::parseDateTime($row['date_stamp'])); //Help avoid confusion with different timezones/DST.
                        }
                    }
                }
                Debug::Arr($row, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

                $is_valid = $primary_validator->isValid($ignore_warning);
                if ($is_valid == true) { //Check to see if all permission checks passed before trying to save data.
                    Debug::Text('Setting object data...', __FILE__, __LINE__, __METHOD__, 10);

                    //If the currently logged in user is timezone GMT, and he edits an absence for a user in timezone PST
                    //it can cause confusion as to which date needs to be recalculated, the GMT or PST date?
                    //Try to avoid this by using getMiddleDayEpoch() as much as possible.
                    $lf->setObjectFromArray($row);

                    $is_valid = $lf->isValid($ignore_warning);
                    if ($is_valid == true) {
                        Debug::Text('Saving data...', __FILE__, __LINE__, __METHOD__, 10);
                        if ($validate_only == true) {
                            $save_result[$key] = true;
                        } else {
                            $lf->setEnableTimeSheetVerificationCheck(true); //Unverify timesheet if its already verified.

                            //Before batch calculation mode was enabled...
                            //$lf->setEnableCalcSystemTotalTime( TRUE );
                            //$lf->setEnableCalcWeeklySystemTotalTime( TRUE );
                            //$lf->setEnableCalcException( TRUE );
                            $lf->setEnableCalcSystemTotalTime(false);
                            $lf->setEnableCalcWeeklySystemTotalTime(false);
                            $lf->setEnableCalcException(false);

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
                //$lf->CommitTransaction();

                $this->getProgressBarObject()->set($this->getAMFMessageID(), $key);
            }

            if ($is_valid == true and $validate_only == false) {
                if (is_array($recalculate_user_date_stamp) and count($recalculate_user_date_stamp) > 0) {
                    Debug::Arr($recalculate_user_date_stamp, 'Recalculating other dates...', __FILE__, __LINE__, __METHOD__, 10);
                    foreach ($recalculate_user_date_stamp as $user_id => $date_arr) {
                        $ulf = TTNew('UserListFactory');
                        $ulf->getByIdAndCompanyId($user_id, $this->getCurrentCompanyObject()->getId());
                        if ($ulf->getRecordCount() > 0) {
                            $cp = TTNew('CalculatePolicy');
                            $cp->setUserObject($ulf->getCurrent());
                            $cp->addPendingCalculationDate($date_arr);
                            $cp->calculate(); //This sets timezone itself.
                            $cp->Save();
                        }
                    }
                } else {
                    Debug::Text('aNot recalculating batch...', __FILE__, __LINE__, __METHOD__, 10);
                }
            } else {
                Debug::Text('bNot recalculating batch...', __FILE__, __LINE__, __METHOD__, 10);
            }

            $lf->CommitTransaction();

            $this->getProgressBarObject()->stop($this->getAMFMessageID());

            return $this->handleRecordValidationResults($validator, $validator_stats, $key, $save_result);
        }

        return $this->returnHandler(false);
    }

    /**
     * Delete one or more user_date_totals.
     * @param array $data user_date_total data
     * @return array
     */
    public function deleteUserDateTotal($data)
    {
        if (is_numeric($data)) {
            $data = array($data);
        }

        if (!is_array($data)) {
            return $this->returnHandler(false);
        }

        if (!($this->getPermissionObject()->Check('punch', 'enabled') or $this->getPermissionObject()->Check('absence', 'enabled'))
            or !($this->getPermissionObject()->Check('punch', 'edit') or $this->getPermissionObject()->Check('punch', 'edit_own') or $this->getPermissionObject()->Check('punch', 'edit_child') or $this->getPermissionObject()->Check('punch', 'add'))
            or !($this->getPermissionObject()->Check('absence', 'edit') or $this->getPermissionObject()->Check('absence', 'edit_own') or $this->getPermissionObject()->Check('absence', 'edit_child') or $this->getPermissionObject()->Check('absence', 'add'))
        ) {
            return $this->getPermissionObject()->PermissionDenied();
        }

        //Get Permission Hierarchy Children first, as this can be used for viewing, or editing.
        $permission_children_ids = $this->getPermissionChildren();

        Debug::Text('Received data for: ' . count($data) . ' UserDateTotals', __FILE__, __LINE__, __METHOD__, 10);
        Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

        $total_records = count($data);
        $validator = $save_result = false;
        $validator_stats = array('total_records' => $total_records, 'valid_records' => 0);
        if (is_array($data) and $total_records > 0) {
            $this->getProgressBarObject()->start($this->getAMFMessageID(), $total_records);

            $lf = TTnew('UserDateTotalListFactory');
            $lf->StartTransaction();

            $recalculate_user_date_stamp = false;
            foreach ($data as $key => $id) {
                $primary_validator = new Validator();
                $lf = TTnew('UserDateTotalListFactory');
                //$lf->StartTransaction();
                if (is_numeric($id)) {
                    //Modifying existing object.
                    //Get user_date_total object, so we can only modify just changed data for specific records if needed.
                    $lf->getByIdAndCompanyId($id, $this->getCurrentCompanyObject()->getId());
                    if ($lf->getRecordCount() == 1) {
                        //Object exists, check edit permissions
                        if ((
                                $this->getPermissionObject()->Check('punch', 'delete')
                                or ($this->getPermissionObject()->Check('punch', 'delete_own') and $this->getPermissionObject()->isOwner($lf->getCurrent()->getCreatedBy(), $lf->getCurrent()->getUser()) === true)
                                or ($this->getPermissionObject()->Check('punch', 'delete_child') and $this->getPermissionObject()->isChild($lf->getCurrent()->getUser(), $permission_children_ids) === true)
                            )
                            or
                            (
                                $this->getPermissionObject()->Check('absence', 'delete')
                                or ($this->getPermissionObject()->Check('absence', 'delete_own') and $this->getPermissionObject()->isOwner($lf->getCurrent()->getCreatedBy(), $lf->getCurrent()->getUser()) === true)
                                or ($this->getPermissionObject()->Check('absence', 'delete_child') and $this->getPermissionObject()->isChild($lf->getCurrent()->getUser(), $permission_children_ids) === true)
                            )
                        ) {
                            Debug::Text('Record Exists, deleting record: ' . $id, __FILE__, __LINE__, __METHOD__, 10);
                            $lf = $lf->getCurrent();

                            $recalculate_user_date_stamp[$lf->getUser()][] = TTDate::getMiddleDayEpoch($lf->getDateStamp()); //Help avoid confusion with different timezones/DST.
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
                        $lf->setEnableTimeSheetVerificationCheck(true); //Unverify timesheet if its already verified.

                        //Before batch calculation mode was enabled...
                        //$lf->setEnableCalcSystemTotalTime( TRUE );
                        //$lf->setEnableCalcWeeklySystemTotalTime( TRUE );
                        //$lf->setEnableCalcException( TRUE );
                        $lf->setEnableCalcSystemTotalTime(false);
                        $lf->setEnableCalcWeeklySystemTotalTime(false);
                        $lf->setEnableCalcException(false);

                        $save_result[$key] = $lf->Save();
                        $validator_stats['valid_records']++;
                    }
                }

                if ($is_valid == false) {
                    Debug::Text('Data is Invalid...', __FILE__, __LINE__, __METHOD__, 10);

                    $lf->FailTransaction(); //Just rollback this single record, continue on to the rest.

                    $validator[$key] = $this->setValidationArray($primary_validator, $lf);
                }

                //$lf->CommitTransaction();

                $this->getProgressBarObject()->set($this->getAMFMessageID(), $key);
            }

            if ($is_valid == true) {
                if (is_array($recalculate_user_date_stamp) and count($recalculate_user_date_stamp) > 0) {
                    Debug::Arr($recalculate_user_date_stamp, 'Recalculating other dates...', __FILE__, __LINE__, __METHOD__, 10);
                    foreach ($recalculate_user_date_stamp as $user_id => $date_arr) {
                        $ulf = TTNew('UserListFactory');
                        $ulf->getByIdAndCompanyId($user_id, $this->getCurrentCompanyObject()->getId());
                        if ($ulf->getRecordCount() > 0) {
                            $cp = TTNew('CalculatePolicy');
                            $cp->setUserObject($ulf->getCurrent());
                            $cp->addPendingCalculationDate($date_arr);
                            $cp->calculate(); //This sets timezone itself.
                            $cp->Save();
                        }
                    }
                } else {
                    Debug::Text('aNot recalculating batch...', __FILE__, __LINE__, __METHOD__, 10);
                }
            } else {
                Debug::Text('bNot recalculating batch...', __FILE__, __LINE__, __METHOD__, 10);
            }

            $lf->CommitTransaction();

            $this->getProgressBarObject()->stop($this->getAMFMessageID());

            return $this->handleRecordValidationResults($validator, $validator_stats, $key, $save_result);
        }

        return $this->returnHandler(false);
    }

    /**
     * Copy one or more user_date_totales.
     * @param array $data user_date_total IDs
     * @return array
     */
    public function copyUserDateTotal($data)
    {
        if (is_numeric($data)) {
            $data = array($data);
        }

        if (!is_array($data)) {
            return $this->returnHandler(false);
        }

        Debug::Text('Received data for: ' . count($data) . ' UserDateTotals', __FILE__, __LINE__, __METHOD__, 10);
        Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

        $src_rows = $this->stripReturnHandler($this->getUserDateTotal(array('filter_data' => array('id' => $data)), true));
        if (is_array($src_rows) and count($src_rows) > 0) {
            Debug::Arr($src_rows, 'SRC Rows: ', __FILE__, __LINE__, __METHOD__, 10);
            foreach ($src_rows as $key => $row) {
                unset($src_rows[$key]['id'], $src_rows[$key]['manual_id']); //Clear fields that can't be copied
                $src_rows[$key]['name'] = Misc::generateCopyName($row['name']); //Generate unique name
            }
            //Debug::Arr($src_rows, 'bSRC Rows: ', __FILE__, __LINE__, __METHOD__, 10);

            return $this->setUserDateTotal($src_rows); //Save copied rows
        }

        return $this->returnHandler(false);
    }

    public function getAccumulatedUserDateTotal($data, $disable_paging = false)
    {
        return UserDateTotalFactory::calcAccumulatedTime($this->getUserDateTotal($data, true));
    }

    public function getTotalAccumulatedUserDateTotal($data, $disable_paging = false)
    {
        $retarr = UserDateTotalFactory::calcAccumulatedTime($this->getUserDateTotal($data, true));
        if (isset($retarr['total'])) {
            return $retarr['total'];
        }

        return false;
    }
}
