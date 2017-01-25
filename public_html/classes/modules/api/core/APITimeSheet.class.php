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
class APITimeSheet extends APIFactory
{
    protected $main_class = false;

    public function __construct()
    {
        parent::__construct(); //Make sure parent constructor is always called.

        return true;
    }

    /**
     * Get all data for displaying the timesheet.
     * @return array
     */
    public function getTimeSheetTotalData($user_id, $base_date)
    {
        $timesheet_data = $this->stripReturnHandler($this->getTimeSheetData($user_id, $base_date));

        if (is_array($timesheet_data)) {
            $retarr = array(
                'timesheet_dates' => $timesheet_data['timesheet_dates'],
                'pay_period_data' => $timesheet_data['pay_period_data'],

                'accumulated_user_date_total_data' => $timesheet_data['accumulated_user_date_total_data'],
                'pay_period_accumulated_user_date_total_data' => $timesheet_data['pay_period_accumulated_user_date_total_data'],
                'timesheet_verify_data' => $timesheet_data['timesheet_verify_data'],
            );
        }

        //Debug::Arr($retarr, 'TimeSheet Total Data: ', __FILE__, __LINE__, __METHOD__, 10);

        return $this->returnHandler($retarr);
    }

    /**
     * Get all data for displaying the timesheet.
     * @return array
     */
    public function getTimeSheetData($user_id, $base_date, $data = false)
    {
        if ($user_id == '' or !is_numeric($user_id)) {
            //This isn't really permission issue, but in cases where the user can't see any employees timesheets, we want to display an error to them at least.
            //return $this->returnHandler( FALSE );
            return $this->getPermissionObject()->PermissionDenied();
        }
        $user_id = (int)$user_id;

        if ($base_date == '') {
            return $this->returnHandler(false);
        }

        $profile_start = microtime(true);

        if (!$this->getPermissionObject()->Check('punch', 'enabled')
            or !($this->getPermissionObject()->Check('punch', 'view') or $this->getPermissionObject()->Check('punch', 'view_child') or $this->getPermissionObject()->Check('punch', 'view_own'))
        ) {
            return $this->getPermissionObject()->PermissionDenied();
        }

        //Get Permission Hierarchy Children first, as this can be used for viewing, or editing.
        //Check for ===FALSE on permission_children_ids, as that means their are no children assigned to them and they don't have view all permissions.
        $data['filter_data']['permission_children_ids'] = $this->getPermissionObject()->getPermissionChildren('punch', 'view');
        if ($data['filter_data']['permission_children_ids'] === false or (is_array($data['filter_data']['permission_children_ids']) and !in_array($user_id, $data['filter_data']['permission_children_ids']))) {
            return $this->getPermissionObject()->PermissionDenied();
        }

        //
        //Get timesheet start/end dates.
        //
        $timesheet_dates = $this->getTimesheetDates($base_date);

        //Include all dates within the timesheet range.
        $timesheet_dates['pay_period_date_map'] = array(); //Add array containing date => pay_period_id pairs.

        //
        //Get PayPeriod information
        //
        $pplf = TTnew('PayPeriodListFactory');

        $pplf->StartTransaction();
        //Make sure we all pay periods that fall within the start/end date, so we can properly display the timesheet range at the top.
        $primary_pay_period_id = 0;
        $pay_period_ids = array();
        $pplf->getByUserIdAndOverlapStartDateAndEndDate($user_id, $timesheet_dates['start_date'], $timesheet_dates['end_date']);
        if ($pplf->getRecordCount() > 0) {
            foreach ($pplf as $pp_obj) {
                $pay_period_ids[] = $pp_obj->getId();
                if ($pp_obj->getStartDate() <= $timesheet_dates['base_date'] and $pp_obj->getEndDate() >= $timesheet_dates['base_date']) {
                    $primary_pay_period_id = $pp_obj->getId();
                }
                $timesheet_dates['pay_period_date_map'] += (array)$pp_obj->getPayPeriodDates($timesheet_dates['start_date'], $timesheet_dates['end_date'], true);
            }
            unset($pp_obj);
        }
        //Debug::Text('Pay Periods: '. $pplf->getRecordCount() .' Primary Pay Period: '. $primary_pay_period_id, __FILE__, __LINE__, __METHOD__, 10);
        //Debug::Arr($timesheet_dates, 'TimeSheet Dates: ', __FILE__, __LINE__, __METHOD__, 10);

        //
        //Get punches
        //
        $punch_data = array();
        $filter_data = $this->initializeFilterAndPager(array('filter_data' => array('start_date' => $timesheet_dates['start_date'], 'end_date' => $timesheet_dates['end_date'], 'user_id' => $user_id)), true);

        //Carry over timesheet filter options.
        if (isset($data['filter_data']['branch_id'])) {
            $filter_data['filter_data']['branch_id'] = $data['filter_data']['branch_id'];
        }
        if (isset($data['filter_data']['department_id'])) {
            $filter_data['filter_data']['department_id'] = $data['filter_data']['department_id'];
        }
        if (isset($data['filter_data']['job_id'])) {
            $filter_data['filter_data']['job_id'] = $data['filter_data']['job_id'];
        }
        if (isset($data['filter_data']['job_item_id'])) {
            $filter_data['filter_data']['job_item_id'] = $data['filter_data']['job_item_id'];
        }

        $plf = TTnew('PunchListFactory');
        $plf->getAPISearchByCompanyIdAndArrayCriteria($this->getCurrentCompanyObject()->getId(), $filter_data['filter_data'], $filter_data['filter_items_per_page'], $filter_data['filter_page'], null, $filter_data['filter_sort']);
        Debug::Text('Punch Record Count: ' . $plf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
        if ($plf->getRecordCount() > 0) {
            //Reduces data transfer by about half.
            $punch_columns = array(
                'id' => true,
                'user_id' => true,
                'transfer' => true,
                'type_id' => true,
                'type' => true,
                'status_id' => true,
                'status' => true,
                'time_stamp' => true,
                'punch_date' => true,
                'punch_time' => true,
                'punch_control_id' => true,
                'longitude' => true,
                'latitude' => true,
                'position_accuracy' => true,
                'date_stamp' => true,
                'pay_period_id' => true,
                'note' => true,
                'tainted' => true,
                'has_image' => true,
                'branch_id' => true,
                'department_id' => true,
                'job_id' => true,
                'job_item_id' => true,
            );

            foreach ($plf as $p_obj) {
                //$punch_data[] = $p_obj->getObjectAsArray( NULL, $data['filter_data']['permission_children_ids'] );
                //Don't need to pass permission_children_ids, as Flex uses is_owner/is_child from the timesheet user record instead, not the punch record.
                $punch_data[] = $p_obj->getObjectAsArray($punch_columns);
            }
        }
        $meal_and_break_total_data = PunchFactory::calcMealAndBreakTotalTime($punch_data, true);
        if ($meal_and_break_total_data === false) {
            $meal_and_break_total_data = array();
        }

        //Get Wage Permission Hierarchy Children first, as this can be used for viewing, or editing.
        if ($this->getPermissionObject()->Check('wage', 'view') == true) {
            $wage_permission_children_ids = true;
        } elseif ($this->getPermissionObject()->Check('wage', 'view') == false) {
            if ($this->getPermissionObject()->Check('wage', 'view_child') == false) {
                $wage_permission_children_ids = array();
            }
            if ($this->getPermissionObject()->Check('wage', 'view_own')) {
                $wage_permission_children_ids[] = $this->getCurrentUserObject()->getID();
            }
        }

        //
        //Get total time for day/pay period
        //
        $user_date_total_data = array();
        $absence_user_date_total_data = array();
        $udt_filter_data = $this->initializeFilterAndPager(array('filter_data' => array('start_date' => $timesheet_dates['start_date'], 'end_date' => $timesheet_dates['end_date'], 'user_id' => $user_id)), true);

        //Carry over timesheet filter options.
        if (isset($data['filter_data']['branch_id'])) {
            $udt_filter_data['filter_data']['branch_id'] = $data['filter_data']['branch_id'];
        }
        if (isset($data['filter_data']['department_id'])) {
            $udt_filter_data['filter_data']['department_id'] = $data['filter_data']['department_id'];
        }
        if (isset($data['filter_data']['job_id'])) {
            $udt_filter_data['filter_data']['job_id'] = $data['filter_data']['job_id'];
        }
        if (isset($data['filter_data']['job_item_id'])) {
            $udt_filter_data['filter_data']['job_item_id'] = $data['filter_data']['job_item_id'];
        }

        $udtlf = TTnew('UserDateTotalListFactory');
        $udtlf->getAPISearchByCompanyIdAndArrayCriteria($this->getCurrentCompanyObject()->getId(), $udt_filter_data['filter_data'], $udt_filter_data['filter_items_per_page'], $udt_filter_data['filter_page'], null, $udt_filter_data['filter_sort']);
        Debug::Text('User Date Total Record Count: ' . $udtlf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
        if ($udtlf->getRecordCount() > 0) {
            //Specifying the columns is about a 30% speed up for large timesheets.
            $udt_columns = array(
                'id' => true,
                'user_id' => true,
                'date_stamp' => true,

                //'status_id' => TRUE,
                //'type_id' => TRUE,

                'object_type_id' => true,
                'src_object_id' => true,
                'pay_code_id' => true,
                'policy_name' => true,
                'name' => true,

                'branch_id' => true,
                'branch' => true,
                'department_id' => true,
                'department' => true,
                'job_id' => true,
                'job' => true,
                'job_item_id' => true,
                'job_item' => true,

                'total_time' => true,

                'pay_period_id' => true,

                'override' => true,
                'note' => true,
            );

            if (isset($wage_permission_children_ids) and ($wage_permission_children_ids === true or in_array($user_id, (array)$wage_permission_children_ids))) {
                $udt_columns['total_time_amount'] = true;
                $udt_columns['hourly_rate'] = true;
            }

            foreach ($udtlf as $udt_obj) {
                //Don't need to pass permission_children_ids, as Flex uses is_owner/is_child from the timesheet user record instead, not the punch record.
                //$user_date_total = $udt_obj->getObjectAsArray( NULL, $data['filter_data']['permission_children_ids'] );
                $user_date_total = $udt_obj->getObjectAsArray($udt_columns);
                $user_date_total_data[] = $user_date_total;

                //Extract just absence records so we can send those to the user, rather than all UDT rows as only absences are used.
                if ($user_date_total['object_type_id'] == 50) {
                    $absence_user_date_total_data[] = $user_date_total;
                }

                //Get all pay periods that have total time assigned to them.
                $timesheet_dates['pay_period_date_map'][$user_date_total['date_stamp']] = $pay_period_ids[] = $user_date_total['pay_period_id'];

                //Adjust primary pay period if the pay period schedules were changed mid-way through perhaps.
                if ($timesheet_dates['base_display_date'] == $user_date_total['date_stamp'] and $timesheet_dates['pay_period_date_map'][$user_date_total['date_stamp']] != $primary_pay_period_id) {
                    $primary_pay_period_id = $user_date_total['pay_period_id'];
                    Debug::Text('Changing primary pay period to: ' . $primary_pay_period_id, __FILE__, __LINE__, __METHOD__, 10);
                }
            }
            unset($user_date_total);
        }
        Debug::Arr($timesheet_dates['pay_period_date_map'], 'Date/Pay Period IDs. Primary Pay Period ID: ' . $primary_pay_period_id, __FILE__, __LINE__, __METHOD__, 10);

        $accumulated_user_date_total_data = UserDateTotalFactory::calcAccumulatedTime($user_date_total_data);
        if ($accumulated_user_date_total_data === false) {
            $accumulated_user_date_total_data = array();
        }
        unset($user_date_total_data);

        //Get data for all pay periods
        $pay_period_data = array();
        $pplf->getByIDList($pay_period_ids);
        if ($pplf->getRecordCount() > 0) {
            $pp_columns = array(
                'id' => true,
                'status_id' => true,
                //'status' => TRUE,
                'type_id' => true,
                //'type' => TRUE,
                //'pay_period_schedule_id' => TRUE,
                'start_date' => true,
                'end_date' => true,
                'transaction_date' => true,
            );

            foreach ($pplf as $pp_obj) {
                $pay_period_data[$pp_obj->getId()] = $pp_obj->getObjectAsArray($pp_columns);
                $pay_period_data[$pp_obj->getId()]['timesheet_verify_type_id'] = $pp_obj->getTimeSheetVerifyType();
                $pay_period_data[$pp_obj->getId()]['start_date_epoch'] = $pp_obj->getStartDate();
                $pay_period_data[$pp_obj->getId()]['end_date_epoch'] = $pp_obj->getEndDate();
            }
        }
        unset($pp_obj);

        //Fill in payperiod gaps in timesheet primarily for migrating from one schedule to another or for new hires in middle of a pay period
        $calendar_dates = TTDate::getCalendarArray($timesheet_dates['start_date'], $timesheet_dates['end_date'], 0, false);
        foreach ($calendar_dates as $tmp_date) {
            if (!isset($timesheet_dates['pay_period_date_map'][TTDate::getDate('DATE', $tmp_date['epoch'])])) {
                foreach ($pay_period_data as $tmp_pp_data) {
                    if ($tmp_date['epoch'] >= $tmp_pp_data['start_date_epoch'] and $tmp_date['epoch'] <= $tmp_pp_data['end_date_epoch']) {
                        $timesheet_dates['pay_period_date_map'][TTDate::getDate('DATE', $tmp_date['epoch'])] = $tmp_pp_data['id'];
                    }
                }
            }
        }
        ksort($timesheet_dates['pay_period_date_map']);
        unset($calendar_dates, $tmp_date, $tmp_pp_data);

        $pp_user_date_total_data = array();
        $pay_period_accumulated_user_date_total_data = array();
        if (isset($primary_pay_period_id) and $primary_pay_period_id > 0) {
            $pp_udt_filter_data = $this->initializeFilterAndPager(array('filter_data' => array('pay_period_id' => $primary_pay_period_id, 'user_id' => $user_id)), true);

            //Carry over timesheet filter options.
            if (isset($data['filter_data']['branch_id'])) {
                $pp_udt_filter_data['filter_data']['branch_id'] = $data['filter_data']['branch_id'];
            }
            if (isset($data['filter_data']['department_id'])) {
                $pp_udt_filter_data['filter_data']['department_id'] = $data['filter_data']['department_id'];
            }
            if (isset($data['filter_data']['job_id'])) {
                $pp_udt_filter_data['filter_data']['job_id'] = $data['filter_data']['job_id'];
            }
            if (isset($data['filter_data']['job_item_id'])) {
                $pp_udt_filter_data['filter_data']['job_item_id'] = $data['filter_data']['job_item_id'];
            }

            $udtlf = TTnew('UserDateTotalListFactory');
            $udtlf->getAPISearchByCompanyIdAndArrayCriteria($this->getCurrentCompanyObject()->getId(), $pp_udt_filter_data['filter_data'], $pp_udt_filter_data['filter_items_per_page'], $pp_udt_filter_data['filter_page'], null, $pp_udt_filter_data['filter_sort']);
            Debug::Text('PP User Date Total Record Count: ' . $udtlf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
            if ($udtlf->getRecordCount() > 0) {
                //Specifying the columns is about a 30% speed up for large timesheets.
                //This is only needed for calcAccumulatedTime().
                $udt_columns = array(
                    'object_type_id' => true,
                    'date_stamp' => true,
                    'name' => true,
                    'pay_code_id' => true,
                    'total_time' => true,

                    'branch_id' => true,
                    'branch' => true,
                    'department_id' => true,
                    'department' => true,
                    'job_id' => true,
                    'job' => true,
                    'job_item_id' => true,
                    'job_item' => true,
                );

                if (isset($wage_permission_children_ids) and ($wage_permission_children_ids === true or in_array($user_id, (array)$wage_permission_children_ids))) {
                    $udt_columns['total_time_amount'] = true;
                    $udt_columns['hourly_rate'] = true;
                }

                foreach ($udtlf as $udt_obj) {
                    $pp_user_date_total_data[] = $udt_obj->getObjectAsArray($udt_columns);
                }

                $pay_period_accumulated_user_date_total_data = UserDateTotalFactory::calcAccumulatedTime($pp_user_date_total_data, false);
                if (isset($pay_period_accumulated_user_date_total_data['total'])) {
                    $pay_period_accumulated_user_date_total_data = $pay_period_accumulated_user_date_total_data['total'];
                } else {
                    $pay_period_accumulated_user_date_total_data = array();
                }
            }
        }
        unset($pp_user_date_total_data);


        //
        //Get Exception data, use the same filter data as punches.
        //
        $exception_data = array();

        $elf = TTnew('ExceptionListFactory');
        $elf->getAPISearchByCompanyIdAndArrayCriteria($this->getCurrentCompanyObject()->getId(), $filter_data['filter_data'], $filter_data['filter_items_per_page'], $filter_data['filter_page'], null, $filter_data['filter_sort']);
        Debug::Text('Exception Record Count: ' . $elf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
        if ($elf->getRecordCount() > 0) {
            //Reduces data transfer.
            $exception_columns = array(
                'id' => true,
                'date_stamp' => true,
                'exception_policy_id' => true,
                'punch_control_id' => true,
                'punch_id' => true,
                'type_id' => true,
                'type' => true,
                'severity_id' => true,
                'severity' => true,
                'exception_color' => true,
                'exception_background_color' => true,
                'exception_policy_type_id' => true,
                'exception_policy_type' => true,
                'pay_period_id' => true,
            );

            foreach ($elf as $e_obj) {
                $exception_data[] = $e_obj->getObjectAsArray($exception_columns);
            }
        }
        unset($elf, $e_obj, $exception_columns);

        //
        //Get request data, so authorized/pending can be shown in a request row for each day.
        //If there are two requests for both authorized and pending, the pending is displayed.
        //
        $request_data = array();

        $rlf = TTnew('RequestListFactory');
        $rlf->getAPISearchByCompanyIdAndArrayCriteria($this->getCurrentCompanyObject()->getId(), $filter_data['filter_data'], $filter_data['filter_items_per_page'], $filter_data['filter_page'], null, $filter_data['filter_sort']);
        Debug::Text('Request Record Count: ' . $rlf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
        if ($rlf->getRecordCount() > 0) {
            $request_columns = array(
                'id' => true,
                'user_id' => true,
                'type_id' => true,
                'status_id' => true,
                'status' => true,
                'date_stamp' => true,
                'authorized' => true,
            );

            foreach ($rlf as $r_obj) {
                $request_data[] = $r_obj->getObjectAsArray($request_columns);
            }
        }
        unset($rlf, $r_obj, $request_columns);

        //
        //Get timesheet verification information.
        //
        $timesheet_verify_data = array();
        if (isset($primary_pay_period_id) and $primary_pay_period_id > 0) {
            $pptsvlf = TTnew('PayPeriodTimeSheetVerifyListFactory');
            $pptsvlf->getByPayPeriodIdAndUserId($primary_pay_period_id, $user_id);

            if ($pptsvlf->getRecordCount() > 0) {
                $pptsv_obj = $pptsvlf->getCurrent();
                $pptsv_obj->setCurrentUser($this->getCurrentUserObject()->getId());
            } else {
                $pptsv_obj = $pptsvlf;
                $pptsv_obj->setCurrentUser($this->getCurrentUserObject()->getId());
                $pptsv_obj->setUser($user_id);
                $pptsv_obj->setPayPeriod($primary_pay_period_id);
                //$pptsv_obj->setStatus( 45 ); //Pending Verification
            }

            $verification_window_dates = $pptsv_obj->getVerificationWindowDates();
            if (is_array($verification_window_dates)) {
                $verification_window_dates['start'] = TTDate::getAPIDate('DATE', $verification_window_dates['start']);
                $verification_window_dates['end'] = TTDate::getAPIDate('DATE', $verification_window_dates['end']);
            }


            $timesheet_verify_data = array(
                'id' => $pptsv_obj->getId(),
                'user_verified' => $pptsv_obj->getUserVerified(),
                'user_verified_date' => $pptsv_obj->getUserVerifiedDate(),
                'status_id' => $pptsv_obj->getStatus(),
                'status' => Option::getByKey($pptsv_obj->getStatus(), $pptsv_obj->getOptions('status')),
                'pay_period_id' => $pptsv_obj->getPayPeriod(),
                'user_id' => $pptsv_obj->getUser(),
                'authorized' => $pptsv_obj->getAuthorized(),
                'authorized_users' => $pptsv_obj->getAuthorizedUsers(),
                'is_hierarchy_superior' => $pptsv_obj->isHierarchySuperior(),
                'display_verify_button' => $pptsv_obj->displayVerifyButton(),
                'verification_box_color' => $pptsv_obj->getVerificationBoxColor(),
                'verification_status_display' => $pptsv_obj->getVerificationStatusDisplay(),
                'previous_pay_period_verification_display' => $pptsv_obj->displayPreviousPayPeriodVerificationNotice(),
                'verification_confirmation_message' => $pptsv_obj->getVerificationConfirmationMessage(),
                'verification_window_dates' => $verification_window_dates,

                'created_date' => $pptsv_obj->getCreatedDate(),
                'created_by' => $pptsv_obj->getCreatedBy(),
                'updated_date' => $pptsv_obj->getUpdatedDate(),
                'updated_by' => $pptsv_obj->getUpdatedBy(),
                //'deleted_date' => $pptsv_obj->getDeletedDate(),
                //'deleted_by' => $pptsv_obj->getDeletedBy()
            );
            unset($pptsvlf, $pptsv_obj, $verification_window_dates);

            if (isset($pay_period_data[$primary_pay_period_id])) {
                $timesheet_verify_data['pay_period_verify_type_id'] = $pay_period_data[$primary_pay_period_id]['timesheet_verify_type_id'];
            }
        }

        //
        //Get holiday data.
        //
        $holiday_data = array();
        $hlf = TTnew('HolidayListFactory');
        $hlf->getAPISearchByCompanyIdAndArrayCriteria($this->getCurrentCompanyObject()->getId(), array('start_date' => $timesheet_dates['start_date'], 'end_date' => $timesheet_dates['end_date'], 'user_id' => $user_id), $filter_data['filter_items_per_page'], $filter_data['filter_page'], null, $filter_data['filter_sort']);
        Debug::Text('Holiday Record Count: ' . $hlf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
        if ($hlf->getRecordCount() > 0) {
            $holiday_columns = array(
                'id' => true,
                'holiday_policy_id' => true,
                'date_stamp' => true,
                'name' => true,
            );

            foreach ($hlf as $h_obj) {
                $holiday_data[] = $h_obj->getObjectAsArray($holiday_columns);
            }
        }
        unset($hlf, $h_obj, $holiday_columns);

        $pplf->CommitTransaction();

        $retarr = array(
            'timesheet_dates' => $timesheet_dates,
            'pay_period_data' => $pay_period_data,

            'punch_data' => $punch_data,

            'holiday_data' => $holiday_data,

            'user_date_total_data' => $absence_user_date_total_data, //Currently just absence records, as those are the only ones used.
            'accumulated_user_date_total_data' => $accumulated_user_date_total_data,
            'pay_period_accumulated_user_date_total_data' => $pay_period_accumulated_user_date_total_data,
            'meal_and_break_total_data' => $meal_and_break_total_data,

            'exception_data' => $exception_data,
            'request_data' => $request_data,
            'timesheet_verify_data' => $timesheet_verify_data,
        );

        //Debug::Arr($retarr, 'TimeSheet Data: ', __FILE__, __LINE__, __METHOD__, 10);
        Debug::Text('TimeSheet Data: User ID:' . $user_id . ' Base Date: ' . $base_date . ' in: ' . (microtime(true) - $profile_start) . 's', __FILE__, __LINE__, __METHOD__, 10);

        return $this->returnHandler($retarr);
    }

    /**
     * Get all necessary dates for building the TimeSheet in a single call, this is mainly as a performance optimization.
     * @param array $data filter data
     * @return array
     */
    public function getTimeSheetDates($base_date)
    {
        $epoch = TTDate::parseDateTime($base_date);

        if ($epoch == '') {
            $epoch = TTDate::getTime();
        }

        $start_date = TTDate::getBeginWeekEpoch($epoch, $this->getCurrentUserPreferenceObject()->getStartWeekDay());
        $end_date = TTDate::getEndWeekEpoch($epoch, $this->getCurrentUserPreferenceObject()->getStartWeekDay());

        $retarr = array(
            'base_date' => $epoch,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'base_display_date' => TTDate::getAPIDate('DATE', $epoch),
            'start_display_date' => TTDate::getAPIDate('DATE', $start_date),
            'end_display_date' => TTDate::getAPIDate('DATE', $end_date),
        );

        return $retarr;
    }

    /**
     * ReCalculate timesheet/policies
     * @return bool
     */
    public function reCalculateTimeSheet($pay_period_ids, $user_ids = null)
    {
        //Debug::text('Recalculating Employee Timesheet: User ID: '. $user_ids .' Pay Period ID: '. $pay_period_ids, __FILE__, __LINE__, __METHOD__, 10);
        //Debug::setVerbosity(11);

        if (!$this->getPermissionObject()->Check('punch', 'enabled')
            or !($this->getPermissionObject()->Check('punch', 'edit') or $this->getPermissionObject()->Check('punch', 'edit_child'))
        ) {
            return $this->getPermissionObject()->PermissionDenied();
        }

        if (Misc::isSystemLoadValid() == false) { //Check system load before anything starts.
            Debug::Text('ERROR: System load exceeded, preventing new recalculation processes from starting...', __FILE__, __LINE__, __METHOD__, 10);
            return $this->returnHandler(false);
        }

        //Make sure pay period is not CLOSED.
        //We can re-calc on locked though.
        $pplf = TTnew('PayPeriodListFactory');
        $pplf->getByIdList($pay_period_ids, null, array('start_date' => 'asc'));
        if ($pplf->getRecordCount() > 0) {
            foreach ($pplf as $pp_obj) {
                Debug::Text('Recalculating Pay Period: ' . $pp_obj->getId() . ' Start Date: ' . TTDate::getDate('DATE', $pp_obj->getStartDate()), __FILE__, __LINE__, __METHOD__, 10);
                if ($pp_obj->getStatus() != 20) {
                    $recalculate_company = false;

                    $ulf = TTnew('UserListFactory');
                    if (is_array($user_ids) and count($user_ids) > 0
                        and isset($user_ids[0]) and $user_ids[0] > 0
                    ) {
                        $ulf->getByIdAndCompanyId($user_ids, $this->getCurrentCompanyObject()->getId());
                    } elseif ($this->getPermissionObject()->Check('punch', 'edit') == true) { //Make sure they have the permissions to recalculate all employees.
                        TTLog::addEntry($this->getCurrentCompanyObject()->getId(), 500, TTi18n::gettext('Recalculating Company TimeSheet'), $this->getCurrentUserObject()->getId(), 'user_date_total');
                        $ulf->getByCompanyId($this->getCurrentCompanyObject()->getId());
                        $recalculate_company = true;
                    } else {
                        return $this->getPermissionObject()->PermissionDenied();
                    }

                    if ($ulf->getRecordCount() > 0) {
                        $start_date = $pp_obj->getStartDate();
                        $end_date = $pp_obj->getEndDate();
                        Debug::text('Found users to re-calculate: ' . $ulf->getRecordCount() . ' Start: ' . TTDate::getDate('DATE', $start_date) . ' End: ' . TTDate::getDate('DATE', $end_date), __FILE__, __LINE__, __METHOD__, 10);

                        $this->getProgressBarObject()->start($this->getAMFMessageID(), $ulf->getRecordCount(), null, TTi18n::getText('ReCalculating Pay Period Ending') . ': ' . TTDate::getDate('DATE', $pp_obj->getEndDate()));

                        $x = 1;
                        foreach ($ulf as $u_obj) {
                            if (Misc::isSystemLoadValid() == false) { //Check system load as the user could ask to calculate decades worth at a time.
                                Debug::Text('ERROR: System load exceeded, stopping recalculation...', __FILE__, __LINE__, __METHOD__, 10);
                                break;
                            }

                            //Ignore terminated employees when recalculating company. However allow all employees to be recalculated if they are selected individually.
                            if ($recalculate_company == false
                                or
                                (
                                    $recalculate_company == true
                                    and ($u_obj->getStatus() == 10
                                        or
                                        (
                                            $u_obj->getStatus() != 10
                                            and
                                            ($u_obj->getTerminationDate() == '' or TTDate::getMiddleDayEpoch($u_obj->getTerminationDate()) > TTDate::getMiddleDayEpoch($start_date))
                                        )
                                    )
                                )
                            ) {
                                TTLog::addEntry($u_obj->getId(), 500, TTi18n::gettext('Recalculating Employee TimeSheet') . ': ' . $u_obj->getFullName() . ' ' . TTi18n::gettext('From') . ': ' . TTDate::getDate('DATE', $start_date) . ' ' . TTi18n::gettext('To') . ': ' . TTDate::getDate('DATE', $end_date), $this->getCurrentUserObject()->getId(), 'user_date_total');

                                $retry_max_attempts = 3;
                                $sleep = 5;
                                $retry_attempts = 0;
                                do {
                                    try {
                                        $cp = TTNew('CalculatePolicy');
                                        $cp->setUserObject($u_obj);
                                        $cp->addPendingCalculationDate($start_date, $end_date);
                                        $cp->calculate(); //This sets timezone itself.
                                        $cp->Save();
                                    } catch (Exception $e) {
                                        Debug::text('WARNING: SQL query failed, likely due to transaction isolation: Retry Attempt: ' . $retry_attempts . ' Sleep: ' . $sleep, __FILE__, __LINE__, __METHOD__, 10);
                                        $retry_attempts++;
                                        sleep($sleep);
                                        continue;
                                    }
                                    break;
                                } while ($retry_attempts <= $retry_max_attempts);

                                if ($retry_attempts == $retry_max_attempts) { //Allow retry_max_attempst to be set at 0 to prevent any retries and fail without an error.
                                    Debug::text('ERROR: SQL query failed after max attempts: ' . $retry_attempts, __FILE__, __LINE__, __METHOD__, 10);
                                    throw new DBError($e);
                                }
                            }
                            //else {
                            //	Debug::text('Skipping inactive or terminated user: '. $u_obj->getID(), __FILE__, __LINE__, __METHOD__, 10);
                            //}

                            $this->getProgressBarObject()->set($this->getAMFMessageID(), $x);

                            $x++;
                        }

                        $this->getProgressBarObject()->stop($this->getAMFMessageID());
                    } else {
                        Debug::text('No Users to calculate!', __FILE__, __LINE__, __METHOD__, 10);
                    }
                } else {
                    Debug::text('Pay Period is CLOSED: ', __FILE__, __LINE__, __METHOD__, 10);
                }
            }
        }

        return $this->returnHandler(true);
    }

    /**
     * Verify/Authorize timesheet
     * @param integer $user_id User ID of the timesheet that is being verified.
     * @param integer $pay_period_id Pay Period ID of the timesheet that is being verified.
     * @return bool
     */
    public function verifyTimeSheet($user_id, $pay_period_id)
    {
        if ($user_id > 0 and $pay_period_id > 0) {
            Debug::text('Verifying Pay Period TimeSheet ', __FILE__, __LINE__, __METHOD__, 10);

            $pptsvlf = TTnew('PayPeriodTimeSheetVerifyListFactory');
            $pptsvlf->StartTransaction();
            $pptsvlf->getByPayPeriodIdAndUserId($pay_period_id, $user_id);
            if ($pptsvlf->getRecordCount() == 0) {
                Debug::text('Timesheet NOT verified by employee yet.', __FILE__, __LINE__, __METHOD__, 10);
                $pptsvf = TTnew('PayPeriodTimeSheetVerifyFactory');
            } else {
                Debug::text('Timesheet re-verified by employee, or superior...', __FILE__, __LINE__, __METHOD__, 10);
                $pptsvf = $pptsvlf->getCurrent();
            }

            $pptsvf->setCurrentUser($this->getCurrentUserObject()->getId());
            $pptsvf->setUser($user_id);
            $pptsvf->setPayPeriod($pay_period_id);

            if ($pptsvf->isValid()) {
                $pptsvf->Save(false);
                $pptsvlf->CommitTransaction();

                //return $this->returnHandler( TRUE );
                return $this->returnHandler($pptsvf->getId());
            } else {
                $pptsvlf->FailTransaction();
                return $this->returnHandler(false, 'VALIDATION', TTi18n::getText('INVALID DATA'), $pptsvf->Validator->getErrorsArray(), array('total_records' => 1, 'valid_records' => 0));
            }
        }

        return $this->returnHandler(false);
    }
}
