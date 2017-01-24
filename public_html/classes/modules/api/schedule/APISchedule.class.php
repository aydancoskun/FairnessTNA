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
 * @package API\Schedule
 */
class APISchedule extends APIFactory
{
    protected $main_class = 'ScheduleFactory';

    public function __construct()
    {
        parent::__construct(); //Make sure parent constructor is always called.

        return true;
    }

    /**
     * Get default schedule data for creating new schedulees.
     * @return array
     */
    public function getScheduleDefaultData($data = null)
    {
        $company_obj = $this->getCurrentCompanyObject();

        Debug::Text('Getting schedule default data...', __FILE__, __LINE__, __METHOD__, 10);

        $retarr = array(
            'status_id' => 10,
            //'user_id' => ($user_id != '' ) ? $user_id : $this->getCurrentUserObject()->getID(),
            'start_time' => TTDate::getAPIDate('TIME', strtotime('8:00 AM')),
            'end_time' => TTDate::getAPIDate('TIME', strtotime('5:00 PM')),
            'schedule_policy_id' => 0,

            //JS will figure out these values based on selected cells.
            'branch_id' => -1,
            'department_id' => -1,
            'job_id' => -1,
            'job_item_id' => -1,
        );

        //Get all user_ids.
        $user_ids = array();
        if (is_array($data)) {
            $first_date_stamp = $last_date_stamp = false;

            foreach ($data as $row) {
                $user_ids[] = (isset($row['user_id'])) ? $row['user_id'] : $this->getCurrentUserObject()->getId();

                //$retarr['branch_id'] = ( isset($row['branch_id']) ) ? $row['branch_id'] : 0;
                //if ( $retarr['branch_id'] != FALSE AND $prev_branch_id != FALSE AND $retarr['branch_id'] != $prev_branch_id ) {
                //	$retarr['branch_id'] = -1; //More than one item selected, so use defaults.
                //}

                $date_stamp = (isset($row['date_stamp'])) ? TTDate::parseDateTime($row['date_stamp']) : time();
                if ($date_stamp < $first_date_stamp or $first_date_stamp == false) {
                    $first_date_stamp = $date_stamp;
                }
                if ($date_stamp > $last_date_stamp or $last_date_stamp == false) {
                    $last_date_stamp = $date_stamp;
                }
            }

            Debug::Arr($user_ids, 'First Date Stamp: ' . $first_date_stamp . ' Last: ' . $last_date_stamp . ' User Ids: ', __FILE__, __LINE__, __METHOD__, 10);
        } else {
            $retarr['date_stamp'] = TTDate::getDate('DATE', time());
            Debug::Text('No input data to base defaults on...', __FILE__, __LINE__, __METHOD__, 10);

            return $retarr;
        }

        //Try to determine most common start/end times to use by default.
        $slf = TTnew('ScheduleListFactory');
        $most_common_data = $slf->getMostCommonScheduleDataByCompanyIdAndUserAndStartDateAndEndDate($company_obj->getID(), $user_ids, TTDate::getBeginWeekEpoch($first_date_stamp), TTDate::getEndWeekEpoch($last_date_stamp));
        //Use array_key_exists() instead of isset() as the array keys are always returned as NULL if no data exists.
        if (array_key_exists('start_time', $most_common_data) and array_key_exists('end_time', $most_common_data) and $most_common_data['start_time'] == null and $most_common_data['end_time'] == null) { //Extend the date range to find some value.
            Debug::Text('No schedules to get default default from, extend range back one more week...', __FILE__, __LINE__, __METHOD__, 10);
            $most_common_data = $slf->getMostCommonScheduleDataByCompanyIdAndUserAndStartDateAndEndDate($company_obj->getId(), $user_ids, TTDate::getBeginWeekEpoch((TTDate::getMiddleDayEpoch($first_date_stamp) - (86400 * 7))), TTDate::getEndWeekEpoch($last_date_stamp));
        }

        if (isset($most_common_data['start_time']) and isset($most_common_data['end_time'])) {
            $retarr['start_time'] = TTDate::getAPIDate('TIME', TTDate::getTimeLockedDate(TTDate::strtotime($most_common_data['start_time']), $first_date_stamp));
            $retarr['end_time'] = TTDate::getAPIDate('TIME', TTDate::getTimeLockedDate(TTDate::strtotime($most_common_data['end_time']), $first_date_stamp));
            $retarr['schedule_policy_id'] = $most_common_data['schedule_policy_id'];
            Debug::Text('  Common Data... Start Time: ' . TTDate::getDATE('DATE+TIME', $retarr['start_time']) . '(' . $retarr['start_time'] . ') End Time: ' . TTDate::getDATE('DATE+TIME', $retarr['end_time']) . '(' . $retarr['end_time'] . ')', __FILE__, __LINE__, __METHOD__, 10);
        }

        Debug::Arr($retarr, 'Default data...', __FILE__, __LINE__, __METHOD__, 10);

        return $this->returnHandler($retarr);
    }

    /**
     * Get combined recurring schedule and committed schedule data for one or more schedulees.
     * @param array $data filter data
     * @return array
     */
    public function getCombinedSchedule($data = null, $base_date = null, $type = null, $strict = null)
    {
        if (!$this->getPermissionObject()->Check('schedule', 'enabled')
            or !($this->getPermissionObject()->Check('schedule', 'view') or $this->getPermissionObject()->Check('schedule', 'view_own') or $this->getPermissionObject()->Check('schedule', 'view_child'))
        ) {
            Debug::Text('aPermission Denied!...', __FILE__, __LINE__, __METHOD__, 10);
            return $this->getPermissionObject()->PermissionDenied();
        }

        //Get Permission Hierarchy Children first, as this can be used for viewing, or editing.
        $data['filter_data']['permission_children_ids'] = $this->getPermissionObject()->getPermissionChildren('schedule', 'view');

        //Get Permission Hierarchy Children for wages first, as this can be used for viewing, or editing.
        $data['filter_data']['wage_permission_children_ids'] = $this->getPermissionObject()->getPermissionChildren('wage', 'view');

        if ($base_date != '') {
            $schedule_dates = $this->getScheduleDates($base_date, $type, $strict);
            $data['filter_data']['start_date'] = $schedule_dates['start_date'];
            $data['filter_data']['end_date'] = $schedule_dates['end_date'];
        }

        //If we don't have permissions to view open shifts, exclude user_id = 0;
        if ($this->getPermissionObject()->Check('schedule', 'view_open') == false) {
            $data['filter_data']['exclude_id'] = array(0);
        } elseif (count($data['filter_data']['permission_children_ids']) > 0) {
            //If schedule, view_open is allowed but they are also only allowed to see their subordinates (which they have some of), add "open" employee as if they are a subordinate.
            $data['filter_data']['permission_children_ids'][] = 0;
        }

        //Pass items per page through to getScheduleArray()
        //This must come before initializeFilterAndPager()
        if (isset($data['filter_items_per_page'])) {
            $data['filter_data']['filter_items_per_page'] = $data['filter_items_per_page'];
        }

        $data = $this->initializeFilterAndPager($data);

        $sf = TTnew('ScheduleFactory');

        $sf->setAMFMessageID($this->getAMFMessageID());

        $retarr = $sf->getScheduleArray($data['filter_data'], $data['filter_data']['permission_children_ids']);
        //Hide wages if the user doesn't have permission to see them.
        if (is_array($retarr)) {
            foreach ($retarr as $date_stamp => $shifts) {
                foreach ($shifts as $key => $row) {
                    //Hide wages if the user doesn't have permission to see them.
                    if (!($this->getPermissionObject()->Check('wage', 'view') == true
                        or ($this->getPermissionObject()->Check('wage', 'view_own') == true and $this->getPermissionObject()->isOwner(false, $row['user_id']) == true)
                        or ($this->getPermissionObject()->Check('wage', 'view_child') == true and $this->getPermissionObject()->isChild($row['user_id'], $data['filter_data']['wage_permission_children_ids']) == true)
                    )
                    ) {
                        $retarr[$date_stamp][$key]['hourly_rate'] = $retarr[$date_stamp][$key]['total_time_wage'] = 0;
                    }
                    $sf->getPermissionColumns($retarr[$date_stamp][$key], $row['user_id'], $row['created_by_id'], $data['filter_data']['permission_children_ids'], $data['filter_columns']);
                }
            }
        }

        if (isset($schedule_dates)) {
            //
            //Get holiday data.
            //
            $holiday_data = array();
            $hlf = TTnew('HolidayListFactory');
            $hlf->getAPISearchByCompanyIdAndArrayCriteria($this->getCurrentCompanyObject()->getId(), array('start_date' => $schedule_dates['start_date'], 'end_date' => $schedule_dates['end_date']), $data['filter_items_per_page'], $data['filter_page'], null, $data['filter_sort']);
            Debug::Text('Holiday Record Count: ' . $hlf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
            if ($hlf->getRecordCount() > 0) {
                foreach ($hlf as $h_obj) {
                    $holiday_data[] = $h_obj->getObjectAsArray();
                }
            }
            unset($hlf, $h_obj);

            $retarr = array(
                'schedule_dates' => $schedule_dates,
                'holiday_data' => $holiday_data,
                'schedule_data' => $retarr,
            );
        }

        //Debug::Arr($retarr, 'RetArr: ', __FILE__, __LINE__, __METHOD__, 10);
        return $this->returnHandler($retarr);
    }

    /**
     * Get all necessary dates for building the schedule in a single call, this is mainly as a performance optimization.
     * @param array $data filter data
     * @return array
     */
    public function getScheduleDates($base_date, $type, $strict = true)
    {
        $epoch = TTDate::parseDateTime($base_date);

        if ($epoch == '') {
            $epoch = TTDate::getTime();
        }

        if ($type == '') {
            $type = 'week';
        }

        switch (strtolower($type)) {
            case 'day':
                if ($strict == true) {
                    $start_date = TTDate::getBeginDayEpoch($epoch);
                    $end_date = TTDate::getEndDayEpoch($epoch);
                } else {
                    $start_date = TTDate::getBeginDayEpoch($epoch);
                    //$end_date = TTDate::getBeginDayEpoch( ( TTDate::getMiddleDayEpoch( $epoch ) + 86400 ) );
                    $end_date = TTDate::getEndDayEpoch((TTDate::getMiddleDayEpoch($epoch) + 86400));
                }
                break;
            case 'week':
                if ($strict == true) {
                    $start_date = TTDate::getBeginWeekEpoch($epoch, $this->getCurrentUserPreferenceObject()->getStartWeekDay());
                    $end_date = TTDate::getEndWeekEpoch($epoch, $this->getCurrentUserPreferenceObject()->getStartWeekDay());
                } else {
                    $start_date = TTDate::getBeginDayEpoch($epoch);
                    //$end_date = TTDate::getBeginDayEpoch( ( TTDate::getMiddleDayEpoch( $epoch ) + (6 * 86400) ) );
                    $end_date = TTDate::getEndDayEpoch((TTDate::incrementDate($epoch, 1, 'week') - 3600));
                }
                break;
            case 'month':
                if ($strict == true) {
                    $start_date = TTDate::getBeginWeekEpoch(TTDate::getBeginMonthEpoch($epoch), $this->getCurrentUserPreferenceObject()->getStartWeekDay());
                    $end_date = TTDate::getEndWeekEpoch(TTDate::getEndMonthEpoch($epoch), $this->getCurrentUserPreferenceObject()->getStartWeekDay());
                } else {
                    //This should be 5 weeks from the base date.
                    $start_date = TTDate::getBeginDayEpoch($epoch);
                    $end_date = TTDate::getEndDayEpoch(TTDate::incrementDate($epoch, 5, 'week'));
                    //$end_date = TTDate::getEndWeekEpoch( ( TTDate::getMiddleDayEpoch( $epoch ) + (30 * 86400) ), TTDate::getDayOfWeek( $epoch, $this->getCurrentUserPreferenceObject()->getStartWeekDay() ) ) + 1;
                }
                break;
            case 'year':
                if ($strict == true) {
                    $start_date = TTDate::getBeginWeekEpoch(TTDate::getBeginMonthEpoch($epoch), $this->getCurrentUserPreferenceObject()->getStartWeekDay());
                    $end_date = TTDate::getEndWeekEpoch(TTDate::getEndMonthEpoch((TTDate::getEndMonthEpoch($epoch) + (86400 * 2))), $this->getCurrentUserPreferenceObject()->getStartWeekDay());
                } else {
                    //This should be 2 months from the base date.
                    $start_date = TTDate::getBeginDayEpoch($epoch);
                    //$end_date = TTDate::getBeginDayEpoch( ( TTDate::getMiddleDayEpoch( $epoch ) + (62 * 86400) ), TTDate::getDayOfWeek( $epoch, $this->getCurrentUserPreferenceObject()->getStartWeekDay() ) ) + 1;
                    $end_date = TTDate::getEndDayEpoch(TTDate::incrementDate($epoch, 2, 'month'));
                }
                break;
        }

        $retarr = array(
            'base_date' => $epoch,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'base_display_date' => TTDate::getAPIDate('DATE', $epoch),
            'start_display_date' => TTDate::getAPIDate('DATE', $start_date),
            'end_display_date' => TTDate::getAPIDate('DATE', $end_date),
        );

        Debug::Arr($retarr, 'Schedule Dates: Base Date: ' . $base_date . ' Type: ' . $type . ' Strict: ' . (int)$strict, __FILE__, __LINE__, __METHOD__, 10);
        return $retarr;
    }

    /**
     * Export wage data to csv
     * @param array $data filter data
     * @param string $format file format (csv)
     * @return array
     */
    public function exportSchedule($format = 'csv', $data = null, $disable_paging = true)
    {
        $result = $this->stripReturnHandler($this->getSchedule($data, $disable_paging));
        return $this->exportRecords($format, 'export_schedule', $result, ((isset($data['filter_columns'])) ? $data['filter_columns'] : null));
    }

    /**
     * Get schedule data for one or more schedulees.
     * @param array $data filter data
     * @return array
     */
    public function getSchedule($data = null, $disable_paging = false)
    {
        if (!$this->getPermissionObject()->Check('schedule', 'enabled')
            or !($this->getPermissionObject()->Check('schedule', 'view') or $this->getPermissionObject()->Check('schedule', 'view_own') or $this->getPermissionObject()->Check('schedule', 'view_child'))
        ) {
            return $this->getPermissionObject()->PermissionDenied();
        }

        $data = $this->initializeFilterAndPager($data, $disable_paging);

        //No filter data, restrict to last pay period as a performance optimization when hundreds of thousands of schedules exist.
        //The issue with this though is that the API doesn't know what the filter criteria is, so it can't display this to the user.
        //if ( count($data['filter_data']) == 0 ) {
        if (!isset($data['filter_data']['id']) and !isset($data['filter_data']['pay_period_ids']) and !isset($data['filter_data']['pay_period_id']) and (!isset($data['filter_data']['start_date']) and !isset($data['filter_data']['end_date']))) {
            Debug::Text('Adding default filter data...', __FILE__, __LINE__, __METHOD__, 10);
            $pplf = TTnew('PayPeriodListFactory');
            $pplf->getByCompanyId($this->getCurrentCompanyObject()->getId());
            $pay_period_ids = array_keys((array)$pplf->getArrayByListFactory($pplf, false, false));
            if (isset($pay_period_ids[0]) and isset($pay_period_ids[1])) {
                $data['filter_data']['pay_period_ids'] = array($pay_period_ids[0], $pay_period_ids[1]);
            }
            unset($pplf, $pay_period_ids);
        }

        //Get Permission Hierarchy Children first, as this can be used for viewing, or editing.
        $data['filter_data']['permission_children_ids'] = $this->getPermissionObject()->getPermissionChildren('schedule', 'view');

        //If we don't have permissions to view open shifts, exclude user_id = 0;
        if ($this->getPermissionObject()->Check('schedule', 'view_open') == false) {
            $data['filter_data']['exclude_id'] = array(0);
        } elseif (count($data['filter_data']['permission_children_ids']) > 0) {
            //If schedule, view_open is allowed but they are also only allowed to see their subordinates (which they have some of), add "open" employee as if they are a subordinate.
            $data['filter_data']['permission_children_ids'][] = 0;
        }

        $blf = TTnew('ScheduleListFactory');
        $blf->getAPISearchByCompanyIdAndArrayCriteria($this->getCurrentCompanyObject()->getId(), $data['filter_data'], $data['filter_items_per_page'], $data['filter_page'], null, $data['filter_sort']);
        Debug::Text('Record Count: ' . $blf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
        if ($blf->getRecordCount() > 0) {
            $this->getProgressBarObject()->start($this->getAMFMessageID(), $blf->getRecordCount());

            $this->setPagerObject($blf);

            //Make sure if hourly_rates are ever exposed in ScheduleFactory that the proper permissions are checked here.
            $retarr = array();
            foreach ($blf as $b_obj) {
                $retarr[] = $b_obj->getObjectAsArray($data['filter_columns'], $data['filter_data']['permission_children_ids']);

                $this->getProgressBarObject()->set($this->getAMFMessageID(), $blf->getCurrentRow());
            }

            $this->getProgressBarObject()->stop($this->getAMFMessageID());
            //Debug::Arr($retarr, 'Schedule Data: ', __FILE__, __LINE__, __METHOD__, 10);

            return $this->returnHandler($retarr);
        }

        return $this->returnHandler(true); //No records returned.
    }

    /**
     * Get only the fields that are common across all records in the search criteria. Used for Mass Editing of records.
     * @param array $data filter data
     * @return array
     */
    public function getCommonScheduleData($data)
    {
        //Trying to Mass Edit only recurring schedule shifts (gray font) will cause an empty 'id' array to be passed and all schedules to be returned, causing things to really slow down.
        //This can be removed after the HTML5 bug is fixed to avoid sending the bogus filter data. 01-Jul-15.
        if (isset($data['filter_data']['id']) and is_array($data['filter_data']['id']) and count($data['filter_data']['id']) == 0) {
            return $this->returnHandler(true); //No records returned.
        }

        return Misc::arrayIntersectByRow($this->stripReturnHandler($this->getSchedule($data, true)));
    }

    /**
     * Validate schedule data for one or more schedulees.
     * @param array $data schedule data
     * @return array
     */
    public function validateSchedule($data)
    {
        return $this->setSchedule($data, true);
    }

    /**
     * Set schedule data for one or more schedulees.
     * @param array $data schedule data
     * @return array
     */
    public function setSchedule($data, $validate_only = false, $overwrite = false, $ignore_warning = true)
    {
        $validate_only = (bool)$validate_only;
        $ignore_warning = (bool)$ignore_warning;

        if (!is_array($data)) {
            return $this->returnHandler(false);
        }


        if ($validate_only == true) {
            Debug::Text('Validating Only!', __FILE__, __LINE__, __METHOD__, 10);
            $permission_children_ids = false;
        } else {
            //Get Permission Hierarchy Children first, as this can be used for viewing, or editing.
            $permission_children_ids = $this->getPermissionChildren();
        }

        //If they have permissions to view open shifts, assume "0" is one of their subordinates.
        if ($this->getPermissionObject()->Check('schedule', 'view_open') == true) {
            $permission_children_ids[] = 0;
        }

        extract($this->convertToMultipleRecords($data));
        Debug::Text('Received data for: ' . $total_records . ' Schedules', __FILE__, __LINE__, __METHOD__, 10);
        //Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

        $validator_stats = array('total_records' => $total_records, 'valid_records' => 0);
        $validator = $save_result = false;
        if (is_array($data) and $total_records > 0) {
            $this->getProgressBarObject()->start($this->getAMFMessageID(), $total_records);

            foreach ($data as $key => $row) {
                $primary_validator = new Validator();
                $lf = TTnew('ScheduleListFactory');
                $lf->StartTransaction();
                if (isset($row['id']) and $row['id'] > 0) {
                    //Modifying existing object.
                    //Get schedule object, so we can only modify just changed data for specific records if needed.
                    $lf->getByIdAndCompanyId($row['id'], $this->getCurrentCompanyObject()->getId());
                    if ($lf->getRecordCount() == 1) {
                        //Object exists, check edit permissions
                        if (
                            $validate_only == true
                            or
                            (
                                $this->getPermissionObject()->Check('schedule', 'edit')
                                or ($this->getPermissionObject()->Check('schedule', 'edit_own') and $this->getPermissionObject()->isOwner($lf->getCurrent()->getCreatedBy(), $lf->getCurrent()->getUser()) === true)
                                or ($this->getPermissionObject()->Check('schedule', 'edit_child') and $this->getPermissionObject()->isChild($lf->getCurrent()->getUser(), $permission_children_ids) === true)
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
                        ($this->getPermissionObject()->Check('schedule', 'add')
                            and
                            (
                                $this->getPermissionObject()->Check('schedule', 'edit')
                                or (isset($row['user_id']) and $this->getPermissionObject()->Check('schedule', 'edit_own') and $this->getPermissionObject()->isOwner(false, $row['user_id']) === true) //We don't know the created_by of the user at this point, but only check if the user is assigned to the logged in person.
                                or (isset($row['user_id']) and $this->getPermissionObject()->Check('schedule', 'edit_child') and $this->getPermissionObject()->isChild($row['user_id'], $permission_children_ids) === true)
                                or (isset($row['user_id']) and $this->getPermissionObject()->Check('schedule', 'view_open') and (int)$row['user_id'] == 0)
                            )
                        )
                    )
                    ) {
                        $primary_validator->isTrue('permission', false, TTi18n::gettext('Add permission denied'));
                    }
                }
                Debug::Arr($row, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

                $is_valid = $primary_validator->isValid($ignore_warning);
                if ($is_valid == true) { //Check to see if all permission checks passed before trying to save data.

                    if ($overwrite == true and isset($row['user_id']) and isset($row['start_time']) and isset($row['end_time'])) {
                        Debug::Text('Overwriting Existing Shifts Enabled...', __FILE__, __LINE__, __METHOD__, 10);
                        $slf = TTnew('ScheduleListFactory');
                        $slf->getConflictingByUserIdAndStartDateAndEndDate($row['user_id'], $row['start_time'], $row['end_time']);
                        if ($slf->getRecordCount() > 0) {
                            Debug::Text('Found Conflicting Shift!!', __FILE__, __LINE__, __METHOD__, 10);
                            foreach ($slf as $s_obj) {
                                Debug::Text('Deleting Schedule Shift ID: ' . $s_obj->getId(), __FILE__, __LINE__, __METHOD__, 10);
                                $s_obj->setDeleted(true);
                                if ($s_obj->isValid()) {
                                    $s_obj->Save();
                                }
                            }
                        } else {
                            Debug::Text('NO Conflicting Shift found...', __FILE__, __LINE__, __METHOD__, 10);
                        }
                        unset($slf, $s_obj);
                    }

                    Debug::Text('Setting object data...', __FILE__, __LINE__, __METHOD__, 10);

                    //This is important when adding/editing a scheduled shift, without it there can be issues calculating exceptions
                    //because if a specific schedule was modified that caused the day to change, smartReCalculate
                    //may only be able to recalculate a single day, instead of both.
                    $old_date_stamp = $lf->getDateStamp();
                    if ($old_date_stamp != 0) {
                        $lf->setOldDateStamp($old_date_stamp);
                    }

                    $row['company_id'] = $this->getCurrentCompanyObject()->getId();     //This prevents a validation error if company_id is FALSE.
                    $lf->setObjectFromArray($row);

                    $lf->Validator->setValidateOnly($validate_only);

                    $is_valid = $lf->isValid($ignore_warning);
                    if ($is_valid == true) {
                        Debug::Text('Saving data...', __FILE__, __LINE__, __METHOD__, 10);
                        $lf->setEnableTimeSheetVerificationCheck(true); //Unverify timesheet if its already verified.
                        $lf->setEnableReCalculateDay(true); //Need to recalculate absence time when editing a schedule, in case schedule policy changed.

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
     * Delete one or more schedules.
     * @param array $data schedule data
     * @return array
     */
    public function deleteSchedule($data)
    {
        if (is_numeric($data)) {
            $data = array($data);
        }

        if (!is_array($data)) {
            return $this->returnHandler(false);
        }

        if (!$this->getPermissionObject()->Check('schedule', 'enabled')
            or !($this->getPermissionObject()->Check('schedule', 'delete') or $this->getPermissionObject()->Check('schedule', 'delete_own') or $this->getPermissionObject()->Check('schedule', 'delete_child'))
        ) {
            return $this->getPermissionObject()->PermissionDenied();
        }

        //Get Permission Hierarchy Children first, as this can be used for viewing, or editing.
        $permission_children_ids = $this->getPermissionChildren();
        //If they have permissions to view open shifts, assume "0" is one of their subordinates.
        if ($this->getPermissionObject()->Check('schedule', 'view_open') == true) {
            $permission_children_ids[] = 0;
        }

        Debug::Text('Received data for: ' . count($data) . ' Schedules', __FILE__, __LINE__, __METHOD__, 10);
        Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

        $total_records = count($data);
        $validator = $save_result = false;
        $validator_stats = array('total_records' => $total_records, 'valid_records' => 0);
        if (is_array($data) and $total_records > 0) {
            $this->getProgressBarObject()->start($this->getAMFMessageID(), $total_records);

            foreach ($data as $key => $id) {
                $primary_validator = new Validator();
                $lf = TTnew('ScheduleListFactory');
                $lf->StartTransaction();
                if (is_numeric($id)) {
                    //Modifying existing object.
                    //Get schedule object, so we can only modify just changed data for specific records if needed.
                    $lf->getByIdAndCompanyId($id, $this->getCurrentCompanyObject()->getId());
                    if ($lf->getRecordCount() == 1) {
                        //Object exists, check edit permissions
                        if ($this->getPermissionObject()->Check('schedule', 'delete')
                            or ($this->getPermissionObject()->Check('schedule', 'delete_own') and $this->getPermissionObject()->isOwner($lf->getCurrent()->getCreatedBy(), $lf->getCurrent()->getUser()) === true)
                            or ($this->getPermissionObject()->Check('schedule', 'delete_child') and $this->getPermissionObject()->isChild($lf->getCurrent()->getUser(), $permission_children_ids) === true)
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
                    Debug::Arr($lf->data, 'Current Data: ', __FILE__, __LINE__, __METHOD__, 10);
                    $lf->setDeleted(true);

                    $is_valid = $lf->isValid();
                    if ($is_valid == true) {
                        $lf->setEnableReCalculateDay(true); //Need to remove absence time when deleting a schedule.

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
     * Get schedule total time
     * @param integer $start Start date epoch
     * @param integer $end End date epoch
     * @param integer $schedule_policy_id Schedule policy ID
     * @return array
     */
    public function getScheduleTotalTime($start, $end, $schedule_policy_id = null, $user_id = null)
    {
        Debug::text('Calculating total time for scheduled shift... Start: ' . $start . ' End: ' . $end, __FILE__, __LINE__, __METHOD__, 10);

        if ($start == '') {
            return false;
        }

        if ($end == '') {
            return false;
        }

        $sf = TTnew('ScheduleFactory');

        //This helps calculate the schedule total time based on schedule policy or policy groups.
        $sf->setCompany($this->getCurrentCompanyObject()->getId());
        if (!is_array($user_id) and $user_id > 0) {
            $sf->setUser($user_id);
        }


        //Prefix the current date to the template, this avoids issues with parsing 24hr clock only, ie: 0600
        //Flex was only sending the times before, so the above worked, but if date is being sent too then it fails.
        //$date_epoch = time();
        //$sf->setStartTime( TTDate::parseDateTime( TTDate::getDate('DATE', $date_epoch ).' '. $start) );
        //$sf->setEndTime( TTDate::parseDateTime( TTDate::getDate('DATE', $date_epoch ).' '. $end) );
        $sf->setStartTime(TTDate::parseDateTime($start));
        $sf->setEndTime(TTDate::parseDateTime($end));

        $sf->setSchedulePolicyId($schedule_policy_id);

        $sf->Validator->setValidateOnly(true);

        $sf->preSave();

        return $this->returnHandler($sf->getTotalTime());
    }

    /**
     * Swap schedules with one another. This doesn't work with recurring schedules, and is not used by Flex currently.
     * @param array $src_ids Source schedule IDs
     * @param array $dst_ids Destination schedule IDs
     * @return array
     */
    public function swapSchedule($src_ids, $dst_ids)
    {
        $src_ids = (array)$src_ids;
        $dst_ids = (array)$dst_ids;

        $data = array_merge($src_ids, $dst_ids);
        if (!is_array($data)) {
            return $this->returnHandler(false);
        }

        Debug::Text('Received data for: ' . count($data) . ' Schedules', __FILE__, __LINE__, __METHOD__, 10);
        Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

        $src_rows = $this->stripReturnHandler($this->getSchedule(array('filter_data' => array('id' => $data)), true));
        if (is_array($src_rows) and count($src_rows) == count($data)) {
            //Debug::Arr($src_rows, 'SRC Rows: ', __FILE__, __LINE__, __METHOD__, 10);

            $id_to_row_map = array();
            //Map returned rows to ids so we can reference them directly.
            foreach ($src_rows as $key => $row) {
                $id_to_row_map[$row['id']] = $key;
            }
            Debug::Arr($id_to_row_map, 'ID to Row Map: ', __FILE__, __LINE__, __METHOD__, 10);

            //Handle swapping several schedules all at once.
            //Loop through each src ID and swap it with the same dst_id.
            $dst_rows = array();
            foreach ($src_ids as $src_key => $src_id) {
                $dst_id = $dst_ids[$src_key];

                $src_row_key = $id_to_row_map[$src_id];
                $dst_row_key = $id_to_row_map[$dst_id];
                Debug::Text('SRC Key: ' . $src_key . ' SRC ID: ' . $src_id . ' DST ID: ' . $dst_id, __FILE__, __LINE__, __METHOD__, 10);

                //Leave IDs in tact, so the audit trail reflects an edit. Basically we are just swapping the date_stamp, start/end, branch, department, policy fields.
                $dst_rows[$src_row_key] = $src_rows[$dst_row_key];
                $dst_rows[$src_row_key]['id'] = $src_rows[$src_row_key]['id'];
                $dst_rows[$src_row_key]['user_id'] = $src_rows[$src_row_key]['user_id'];
                //Need to set columns like user_date_id to NULL so its not overridden in setScheduel().
                $dst_rows[$src_row_key]['start_date'] = $dst_rows[$src_row_key]['end_date'] = $dst_rows[$src_row_key]['date_stamp'] = $dst_rows[$src_row_key]['pay_period_id'] = null;

                $dst_rows[$dst_row_key] = $src_rows[$src_row_key];
                $dst_rows[$dst_row_key]['id'] = $src_rows[$dst_row_key]['id'];
                $dst_rows[$dst_row_key]['user_id'] = $src_rows[$dst_row_key]['user_id'];
                //Need to set columns like user_date_id to NULL so its not overridden in setScheduel().
                $dst_rows[$dst_row_key]['start_date'] = $dst_rows[$dst_row_key]['end_date'] = $dst_rows[$dst_row_key]['date_stamp'] = $dst_rows[$dst_row_key]['pay_period_id'] = null;
            }

            //Debug::Arr($dst_rows, 'DST Rows: ', __FILE__, __LINE__, __METHOD__, 10);
            if (is_array($dst_rows)) {
                return $this->setSchedule($dst_rows);
            }
        }

        return $this->returnHandler(false);
    }

    public function addPunchesFromScheduledShifts($schedule_arr)
    {
        return $this->returnHandler(true);
    }
}
