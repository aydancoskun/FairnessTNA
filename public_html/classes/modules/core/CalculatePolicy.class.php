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
 * @package Core
 */
class CalculatePolicy
{

    //Default option flags.
    public $user_date_total_insert_id = -1;
    public $user_date_total = null;
public $holiday_policy = null;
    private $flags = array(
        'meal' => true,
        'undertime_absence' => true,
        'break' => true,
        'holiday' => true,
        'schedule_absence' => true,
        'absence' => true,
        'regular' => true,
        'overtime' => true,
        'premium' => true,
        'accrual' => true,
        'exception' => true,

        //Exception options
        'exception_premature' => false, //Calculates premature exceptions
        'exception_future' => true, //Calculates exceptions in the future.

        //Calculate policies for future dates.
        'future_dates' => true, //Calculates dates in the future.
        'past_dates' => false, //Calculates dates in the past. This is only needed when Pay Formulas that use averaging are enabled?
    );
    private $user_obj = null;
    private $original_time_zone = null;
    private $pay_periods = null;
        private $pay_period_schedules = null; //Cache the pay period schedule start_week_day_id.

    //Array of dates that data has been obtained, pending calculation or already calculated.
    private $pay_period_obj = null;
    private $pay_period_schedule_obj = null;
private $start_week_day_id = 0;
    private $dates = array('data' => array(), 'pending_calculation' => array(), 'calculated' => array());
    private $delete_system_total_time_already_run = false;
    private $currency_rates = null;
        private $user_wages = null; //Used to assign hour based accruals to.
    private $new_user_date_total_ids = null;
private $new_system_user_date_total_id = array();
    private $schedule = null;
    private $exception = null;
    private $punch = null;
        private $schedule_policy_rs = null; //0 Seconds.
private $schedule_policy_max_start_stop_window = 0;
    private $meal_time_policy = null;
    private $schedule_policy_meal_time_policy = null;
    private $break_time_policy = null;
    private $schedule_policy_break_time_policy = null;
    private $undertime_absence_policy = null;
    private $exception_policy = null;
    private $accrual_time_exclusivity_map = null;
    private $regular_time_policy = null;
    private $regular_time_exclusivity_map = null;
    private $over_time_policy = null;
    private $over_time_trigger_time_exclusivity_map = null;
        private $over_time_recurse_map = null; //Used for creating timestamps for manual timesheet entries.
    private $prev_user_date_total_start_time_stamp = null; //Used for creating timestamps for manual timesheet entries.
private $prev_user_date_total_end_time_stamp = null;
    private $schedule_over_time_policy_ids = null;
    private $premium_time_policy = null;
    private $schedule_premium_time_policy_ids = null;
    private $accrual_policy = null; //Needs to be public so ContributingShiftPolicyFactory can read it.
    private $is_eligible_holiday_description = null;
    private $holiday = null;
    private $policy_group_holiday_policy_ids = null; //Holiday Policies associated with contributing shifts only.

    private $contributing_shift_policy = null;
    private $contributing_pay_code_policy = null;
    private $contributing_pay_codes_by_policy_id = null; //PolicyID -> Pay Code map.

    private $pay_codes = null;
    private $pay_formula_policy = null;


    //Determine pay period based on the date that is being calculated.
    public function __construct($user_obj = null)
    {
        if (is_object($user_obj)) {
            $this->setUserObject($user_obj);
        }

        return true;
    }

    public function setUserObject($obj)
    {
        if (is_object($obj)) {
            $this->user_obj = $obj;

            //If the currently logged in administrator is timezone GMT, and he edits an absence for a user in timezone PST
            //the date_stamp in epoch format is set in GMT timezone, then here the timezone switches to PST
            //and changes the date that is calculated.

            //Need to set the timezone as soon as the user object is specified, so when addPendingDates() is called they are in the proper timezone too.
            $this->setTimeZone();

            $this->setFlag('past_dates', $this->isPastDateCalculationRequired());

            return true;
        }

        return false;
    }

    public function setTimeZone()
    {
        //IMPORTANT: Make sure the timezone is set to the users timezone, prior to calculating policies,
        //as that will affect when date/time premium policies apply
        //Its also important that the timezone gets set back after calculating multiple punches in a batch as this can prevent other employees
        //from using the wrong timezone.
        //FIXME: How do we handle the employee moving between stations that themselves are in different timezones from the users default timezone?
        //How do we apply time based premium policies in that case?
        if (is_object($this->getUserObject()) and is_object($this->getUserObject()->getUserPreferenceObject())) {
            //If setTimeZone() is called multiple times for some reason, the original timezone will be incorrect.
            //So make sure we only set it once, as even if recalculating many employees at once the entire CalculatePolicy object should be re-initialized.
            //The bug can be replicated by setting current user to PST8PDT, then Mass Edit two IN punches for a user in MST7MDT timezone, and first punch time will be correct, the 2nd punch incorrect.
            if (!isset($this->original_time_zone) or $this->original_time_zone == '') { //original_time_zone defaults to NULL which causes isset() to be FALSE.
                $this->original_time_zone = TTDate::getTimeZone();
            }

            return TTDate::setTimeZone($this->getUserObject()->getUserPreferenceObject()->getTimeZone());
        }

        return false;
    }

    public function getUserObject()
    {
        return $this->user_obj;
    }

    public function setFlag($key, $value = true)
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->flags[$k] = $v;
            }
        } else {
            $this->flags[$key] = $value;
        }
        return true;
    }

    public function isPastDateCalculationRequired()
    {
        $pfplf = TTnew('PayFormulaPolicyListFactory');
        $pfplf->getByCompanyIdAndPayTypeId($this->getUserObject()->getCompany(), 30);
        if ($pfplf->getRecordCount() > 0) {
            Debug::Text('Past date calculation is required...', __FILE__, __LINE__, __METHOD__, 10);
            return true;
        }

        Debug::Text('Past date calculation is NOT required...', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function getPayPeriodScheduleObject($id)
    {
        if ($id > 0) {
            if (isset($this->pay_period_schedules[$id]) and is_object($this->pay_period_schedules[$id]) and $id == $this->pay_period_schedules[$id]->getID()) {
                return $this->pay_period_schedules[$id];
            } else {
                $lf = TTnew('PayPeriodScheduleListFactory');
                $lf->getById($id);
                if ($lf->getRecordCount() == 1) {
                    $this->pay_period_schedules[$id] = $lf->getCurrent();
                    return $this->pay_period_schedules[$id];
                }

                return false;
            }
        }

        return false;
    }

    public function getSumScheduledDays($schedule_arr)
    {
        $sum = 0;
        if (is_array($schedule_arr)) {
            foreach ($schedule_arr as $s_obj) {
                if ($s_obj->getStatus() == 10 and $s_obj->getTotalTime() > 0) {
                    $sum++;
                }
            }
        }
        Debug::text('Scheduled Days Total: ' . $sum, __FILE__, __LINE__, __METHOD__, 10);
        return $sum;
    }

    public function sortScheduleByDateASC($a, $b)
    {
        if ($a->getDateStamp() == $b->getDateStamp()) {
            return 0;
        }

        return ($a->getDateStamp() < $b->getDateStamp()) ? (-1) : 1;
    }

    //Check if past date calculation is required.
    //This is based on PayFormulas that use average calculations.

    public function sortScheduleByDateDESC($a, $b)
    {
        if ($a->getDateStamp() == $b->getDateStamp()) {
            return 0;
        }

        return ($a->getDateStamp() > $b->getDateStamp()) ? (-1) : 1;
    }

    //Since handling auto-deduct meal policies (negative total time) is virtually impossible to handle by itself
    //when it comes to overtime/premium policies that adjust the total time themselves,
    //this function will roll the meal/break policy time into the source record before being calculated for Reg/OT/Prem.
    //This way Reg/OT/Prem. calculation functions don't need to worry about negative total times at all.

    public function compactUserDateTotalDataBasedOnTimeStamps($udtlf)
    {
        if (is_array($udtlf) and count($udtlf) > 0) {
            $prev_udt_obj = false;
            $prev_udt_key = false;
            foreach ($udtlf as $udt_key => $udt_obj) {
                //Debug::text( 'UDT Date Stamp: ' . TTDate::getDate( 'DATE', $udt_obj->getDateStamp() ) . ' Pay Code ID: ' . $udt_obj->getPayCode() . ' Object Type: ' . $udt_obj->getObjectType() . ' Total Time: ' . $udt_obj->getTotalTime() . ' Override: ' . (int)$udt_obj->getOverride(), __FILE__, __LINE__, __METHOD__, 10 );
                if (is_object($prev_udt_obj)) {
                    //Match current UDT with previous UDT record to see if they match.
                    if (
                        $udt_obj->getObjectType() == $prev_udt_obj->getObjectType()
                        and $udt_obj->getPayCode() == $prev_udt_obj->getPayCode()
                        and $udt_obj->getPunchControlID() == $prev_udt_obj->getPunchControlID()
                        and $udt_obj->getStartTimeStamp() == $prev_udt_obj->getEndTimeStamp()
                        and $udt_obj->getHourlyRate() == $prev_udt_obj->getHourlyRate()
                        and TTDate::getHour($udt_obj->getStartTimeStamp()) == 0 and TTDate::getMinute($udt_obj->getStartTimeStamp()) == 0 //Only merge at midnight for now, as we would need to adjust many unit tests otherwise.
                    ) {
                        Debug::text('  Found UDT object to compact... Key: ' . $udt_key . ' Prev Key: ' . $prev_udt_key . 'z: ' . TTDate::getHour($udt_obj->getStartTimeStamp()), __FILE__, __LINE__, __METHOD__, 10);

                        //Merge current UDT record into previous one.
                        $prev_udt_obj->setEndTimeStamp($udt_obj->getEndTimeStamp());
                        $prev_udt_obj->setTotalTime($prev_udt_obj->calcTotalTime()); //FIXME: Make sure it handles negatives properly, like lunch auto-deduct. See ContributingShiftPolicyFactory->getPartialUserDateTotalObject() for more info.
                        $prev_udt_obj->setEnableCalcSystemTotalTime(false);
                        if ($prev_udt_obj->isValid()) {
                            $prev_udt_obj->preSave(); //Call this so TotalTime, TotalTimeAmount is calculated immediately, as we don't save these records until later.
                        }

                        unset($udtlf[$udt_key]); //Remove current UDT record now that it has been merged.

                        continue; //Don't continue any further, as we don't want to set a new prev_udt_obj, since the current UDT record essentially never existed.
                    }
                }

                $prev_udt_obj = $udt_obj;
                $prev_udt_key = $udt_key;
            }
        }

        return $udtlf;
    }

    public function BreakTimePolicySortByTriggerTimeAsc($a, $b)
    {
        if ($a->getTriggerTime() == $b->getTriggerTime()) {
            return 0;
        }

        return ($a->getTriggerTime() < $b->getTriggerTime()) ? (-1) : 1;
    }

    //Find existing UDT records that have override=TRUE and other fields matching, so we don't try to insert new UDT records.

    public function RegularTimePolicySortByCalculationOrderAsc($a, $b)
    {
        if ($a->getCalculationOrder() == $b->getCalculationOrder()) {
            return 0;
        }

        return ($a->getCalculationOrder() < $b->getCalculationOrder()) ? (-1) : 1;
    }

    //Remove UserDateTotalObjects that cancel each other out, such as a +1800 total time and -1800 total time for the same pay_code_id.
    //This is required for some premium policies with auto-deduct lunches and such.

    public function getWeeklyOverTimePolicyPayCodes()
    {
        $weekly_over_time_pay_code_ids = array();
        $weekly_over_time_policies = $this->filterWeeklyOverTimePolicy();
        if (is_array($weekly_over_time_policies) and count($weekly_over_time_policies) > 0) {
            foreach ($weekly_over_time_policies as $otp_obj) {
                $weekly_over_time_pay_code_ids[] = $otp_obj->getPayCode();
            }
        }
        unset($weekly_over_time_policies, $otp_obj);

        return $weekly_over_time_pay_code_ids;
    }

    public function filterWeeklyOverTimePolicy()
    {
        $otplf = $this->over_time_policy;
        if (is_array($otplf) and count($otplf) > 0) {
            foreach ($otplf as $otp_obj) {
                if (in_array($otp_obj->getType(), array(20, 30, 210))) {
                    $retarr[$otp_obj->getId()] = $otp_obj;
                }
            }

            if (isset($retarr)) {
                Debug::text('Found overtime policies that apply on date: ' . count($retarr), __FILE__, __LINE__, __METHOD__, 10);
                return $retarr;
            }
        }

        Debug::text('No overtime policies apply on date...', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function getAverageHourlyRate($date_stamp, $hour_contributing_shift_policy_obj, $hour_object_type_ids, $wage_contributing_shift_policy_obj)
    {
        //To determine average rate we need to seperate what hours are included and what dollars are included.

        $total_time = 0;
        $total_wages = 0;

        $start_date = TTDate::getBeginWeekEpoch($date_stamp, $this->start_week_day_id);
        $end_date = TTDate::getEndWeekEpoch($date_stamp, $this->start_week_day_id);

        $this->getRequiredData($end_date); //Use the end of the week date stamp.
        $this->addPendingCalculationDate($start_date, $end_date);

        //Get total hours.
        //Don't include Meal/Break time though, as its already calculated in the Regular Time.
        $hour_udt_rows = $this->filterUserDateTotalDataByContributingShiftPolicy($start_date, $end_date, $hour_contributing_shift_policy_obj, array(20, 25, 30, 40));
        if (is_array($hour_udt_rows) and count($hour_udt_rows) > 0) {
            foreach ($hour_udt_rows as $udt_obj) {
                $total_time += $udt_obj->getTotalTime();
            }
        }
        //Debug::text('Total Time: '. $total_time, __FILE__, __LINE__, __METHOD__, 10);
        unset($hour_udt_rows, $udt_obj);

        if ($total_time != 0) { //Handle average wages for negative values too.
            //Get total wages. Normally this will include almost all object types.
            //Don't include Meal/Break time though, as its already calculated in the Regular Time.
            $wage_udt_rows = $this->filterUserDateTotalDataByContributingShiftPolicy($start_date, $end_date, $wage_contributing_shift_policy_obj, array(20, 25, 30, 40));
            if (is_array($wage_udt_rows) and count($wage_udt_rows) > 0) {
                foreach ($wage_udt_rows as $udt_obj) {
                    if ($udt_obj->getObjectType() == 30) { //Overtime.
                        Debug::text('Overtime, using base hourly rate: ' . $udt_obj->getBaseHourlyRate() . ' Total Time: ' . $udt_obj->getTotalTime(), __FILE__, __LINE__, __METHOD__, 10);
                        $tmp_wages = ($udt_obj->getBaseHourlyRate() * TTDate::getHours($udt_obj->getTotalTime()));
                    } else {
                        $tmp_wages = $udt_obj->getTotalTimeAmount();
                    }

                    //Debug::text('Adding wages: '. $tmp_wages, __FILE__, __LINE__, __METHOD__, 10);
                    $total_wages += $tmp_wages;
                }
            }
            //Debug::text('Total Wages: '. $total_wages, __FILE__, __LINE__, __METHOD__, 10);
            unset($wage_udt_rows, $udt_obj);

            $average_hourly_rate = ($total_wages / TTDate::getHours($total_time));
            Debug::text('Total Time: ' . $total_time . ' Wages: ' . $total_wages . ' Average Hourly Rate: ' . $average_hourly_rate . ' DateStamp: ' . TTDate::getDate('DATE', $date_stamp), __FILE__, __LINE__, __METHOD__, 10);
            return $average_hourly_rate;
        }

        return false;
    }

    //Calculates schedule absence time (exclusive to holiday absence time, or manually entered time on the timesheet)

    public function getRequiredData($date_stamp, $enable_recalculate_holiday = true)
    {
        if (!is_object($this->pay_period_schedule_obj)) { //HolidayFactory calls this without calling Calculate(), so always make sure a pay period schedule is defined
            $this->setPayPeriodFromDate($date_stamp);
        }

        $date_arr = $this->getDatesToObtainDataFor($date_stamp);
        if (count($date_arr) > 0) {
            $date_range = $this->getDateRangeFromDateArray($date_arr);
            Debug::text('Date Range: Start: ' . TTDate::getDate('DATE+TIME', $date_range['start_date']) . ' End: ' . TTDate::getDate('DATE+TIME', $date_range['end_date']), __FILE__, __LINE__, __METHOD__, 10);

            $this->getSchedulePolicy(); //Must come before getScheduleData() so we can get the maximum start/stop window for getScheduleData().
            $this->getScheduleData($date_range['start_date'], $date_range['end_date']);

            if ($this->getFlag('meal') == true or $this->getFlag('break') == true or $this->getFlag('regular') == true or $this->getFlag('overtime') == true or $this->getFlag('premium') == true or $this->getFlag('accrual') == true or $this->getFlag('holiday') == true or $this->getFlag('undertime_absence') == true) {
                $this->getUserWageData($date_range['start_date'], $date_range['end_date']);
                $this->getCurrencyRateData($date_range['start_date'], $date_range['end_date']);
            }

            if ($this->getFlag('meal') == true or $this->getFlag('break') == true or $this->getFlag('regular') == true or $this->getFlag('overtime') == true or $this->getFlag('premium') == true or $this->getFlag('accrual') == true or $this->getFlag('holiday') == true or $this->getFlag('undertime_absence') == true or $this->getFlag('exception') == true) {
                $this->getUserDateTotalData($date_range['start_date'], $date_range['end_date']);
            }

            if ($this->getFlag('undertime_absence') == true) {
                $this->getUnderTimeAbsenceTimePolicy();
            }

            if ($this->getFlag('meal') == true or $this->getFlag('exception') == true) {
                $this->getMealTimePolicy();
            }
            if ($this->getFlag('break') == true or $this->getFlag('exception') == true) {
                $this->getBreakTimePolicy();
            }
            if ($this->getFlag('regular') == true) {
                $this->getRegularTimePolicy();
            }
            if ($this->getFlag('overtime') == true) {
                $this->getOverTimePolicy();
            }
            if ($this->getFlag('premium') == true) {
                $this->getPremiumTimePolicy();
            }

            //Must go before getContributingShiftPolicy() below.
            //If deleteSystemTotalTime() is called, that will delete any accruals, so we have to calculate accruals again whenever this happens.
            if ($this->getFlag('meal') == true or $this->getFlag('undertime_absence') == true or $this->getFlag('break') == true or $this->getFlag('regular') == true or $this->getFlag('overtime') == true or $this->getFlag('premium') == true or $this->getFlag('accrual') == true or $this->getFlag('holiday') == true or $this->getFlag('schedule_absence') == true) {
                $this->getAccrualPolicy();
            }

            //Hour based accrual policies require Contributing Shifts and such, so if accruals are calculated we need to run below code too.
            if ($this->getFlag('meal') == true or $this->getFlag('undertime_absence') == true or $this->getFlag('break') == true or $this->getFlag('regular') == true or $this->getFlag('overtime') == true or $this->getFlag('premium') == true or $this->getFlag('accrual') == true or $this->getFlag('holiday') == true or $this->getFlag('schedule_absence') == true) {
                $this->getPayCode(); //Needs to come before getContributingShiftPolicy, but after Reg/OT/Prem policies are obtained.
                $this->getPayFormulaPolicy();

                $this->getHolidayPolicy(); //Must come before getContributingShiftPolicy() as it adds additional contributing shift policies to the list.
                $this->getHolidayData($date_range['start_date'], $date_range['end_date'], $enable_recalculate_holiday); //This uses date_stamp as we need to find holidays in the past/future. Must come after getHolidayPolicy()

                $this->getContributingShiftPolicy(); //This adds additional HolidayPolicies to the list... But it can't come before getHolidayPolicy()
                $this->getContributingPayCodePolicy();
            }

            if ($this->getFlag('exception') == true) {
                $this->getExceptionPolicy();
                $this->getPunchData($date_range['start_date'], $date_range['end_date']);
                $this->getExceptionData($date_range['start_date'], $date_range['end_date']);
            }
        } else {
            Debug::text('No dates to get required data for...', __FILE__, __LINE__, __METHOD__, 10);
        }

        Debug::text('Done collecting required data...', __FILE__, __LINE__, __METHOD__, 10);

        return true;
    }

    public function setPayPeriodFromDate($date_stamp)
    {
        $pay_period_id = PayPeriodListFactory::findPayPeriod($this->getUserObject()->getId(), $date_stamp);
        if ($pay_period_id > 0) {
            $this->pay_period_obj = $this->getPayPeriodObject($pay_period_id);
            $this->pay_period_schedule_obj = $this->pay_period_obj->getPayPeriodScheduleObject();
        } else {
            $this->pay_period_obj = null;
            $ppslf = TTNew('PayPeriodScheduleListFactory');
            $ppslf->getByUserId($this->getUserObject()->getId());
            if ($ppslf->getRecordCount() == 1) {
                $this->pay_period_schedule_obj = $ppslf->getCurrent();
            } else {
                Debug::text('Pay Period Object not found for user: ' . $this->getUserObject()->getId(), __FILE__, __LINE__, __METHOD__, 10);
                $this->pay_period_schedule_obj = TTnew('PayPeriodScheduleFactory');
            }
            unset($ppslf);
        }
        $this->start_week_day_id = $this->pay_period_schedule_obj->getStartWeekDay();

        return true;
    }

    public function getPayPeriodObject($id)
    {
        if ($id > 0) {
            if (isset($this->pay_periods[$id]) and is_object($this->pay_periods[$id]) and $id == $this->pay_periods[$id]->getID()) {
                return $this->pay_periods[$id];
            } else {
                $lf = TTnew('PayPeriodListFactory');
                $lf->getById($id);
                if ($lf->getRecordCount() == 1) {
                    $this->pay_periods[$id] = $lf->getCurrent();
                    return $this->pay_periods[$id];
                }

                return false;
            }
        }

        return false;
    }

    public function getDatesToObtainDataFor($date_stamp)
    {
        $retarr = array();

        //Always get data for the entire week, since we need the date earlier than $date_stamp for calculations (ie: Weekly Overtime) later in the week
        //and when changing $date_stamp we have to recalculate all dates proceeding it until the end of the week anyways.
        $start_date = TTDate::getBeginWeekEpoch($date_stamp, $this->start_week_day_id);

        if ($this->getFlag('future_dates') == true or $this->getFlag('exception_future') == true) {
            $end_date = TTDate::getEndWeekEpoch($date_stamp, $this->start_week_day_id);
        } else {
            $end_date = $this->getLastPendingDate();
            if ($end_date == '') {
                //$end_date = $start_date;
                //If we use $start_date, we won't get data for days between the beginning of the week and $date_stamp.
                //Specifically if $date_stamp = 31-Oct-14 and start_date = 26-Oct-14, we need the data for 26-Oct to 31-Oct.
                $end_date = $date_stamp;
            }
        }

        Debug::text('Start: ' . TTDate::getDATE('DATE+TIME', $start_date) . ' End: ' . TTDate::getDATE('DATE+TIME', $end_date) . '(' . $end_date . ')', __FILE__, __LINE__, __METHOD__, 10);

        $date_arr = TTDate::getDateArray($start_date, $end_date);
        foreach ($date_arr as $tmp_date_stamp) {
            if (!isset($this->dates['data'][$tmp_date_stamp])) {
                $retarr[] = $tmp_date_stamp;
                $this->dates['data'][$tmp_date_stamp] = true;
                Debug::text('Found date without data: ' . TTDate::getDATE('DATE+TIME', $tmp_date_stamp), __FILE__, __LINE__, __METHOD__, 10);
            }
            //else {
            //Debug::text('Already have data for date: '. TTDate::getDATE('DATE+TIME', $tmp_date_stamp ), __FILE__, __LINE__, __METHOD__, 10);
            //}
        }

        return $retarr;
    }

    public function getFlag($key)
    {
        if (isset($this->flags[$key])) {
            return $this->flags[$key];
        }

        return false;
    }

    public function getLastPendingDate()
    {
        end($this->dates['pending_calculation']);
        $retval = key($this->dates['pending_calculation']);
        Debug::Text('Last Pending Date: ' . TTDate::getDate('DATE+TIME', $retval), __FILE__, __LINE__, __METHOD__, 10);
        if ($retval != '') {
            return $retval;
        }

        return false;
    }

    public function getDateRangeFromDateArray($date_arr)
    {
        sort($date_arr);

        $retarr['start_date'] = reset($date_arr);
        $retarr['end_date'] = end($date_arr);

        Debug::text('Found Date Range: Start: ' . TTDate::getDATE('DATE', $retarr['start_date']) . ' End: ' . TTDate::getDATE('DATE', $retarr['end_date']), __FILE__, __LINE__, __METHOD__, 10);

        return $retarr;
    }

    public function getSchedulePolicy()
    {
        //Get all schedule policies so we can figure out the maximum start/stop window
        //which we then use to get schedules. So this has to be called before getSchedule().
        $splf = TTnew('SchedulePolicyListFactory');
        $splf->getByCompanyId($this->getUserObject()->getCompany());
        if ($splf->getRecordCount() > 0) {
            foreach ($splf as $sp_obj) {
                $this->schedule_policy_rs[$sp_obj->getId()] = $sp_obj;
                if ($sp_obj->getStartStopWindow() > $this->schedule_policy_max_start_stop_window) {
                    $this->schedule_policy_max_start_stop_window = $sp_obj->getStartStopWindow();
                }
            }

            Debug::text('Maximum Schedule Policy Start/Stop Window: ' . $this->schedule_policy_max_start_stop_window, __FILE__, __LINE__, __METHOD__, 10);
            return true;
        } else {
            Debug::text('aNo schedule policy rows...', __FILE__, __LINE__, __METHOD__, 10);
        }

        Debug::text('bNo schedule policy rows...', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    //Filter scheduled shifts based on worked shift times.

    public function getScheduleData($start_date = null, $end_date = null, $status_ids = null, $limit = null, $order = null)
    {
        $slf = TTNew('ScheduleListFactory');
        $filter_data = array(
            'user_id' => $this->getUserObject()->getId(),
            'start_date' => ($start_date - $this->schedule_policy_max_start_stop_window),
            'end_date' => ($end_date + $this->schedule_policy_max_start_stop_window),
            'status_id' => $status_ids,
            'exclude_id' => array_keys((array)$this->schedule),
        );
        Debug::text('Getting Schedule Data for Start Date: ' . TTDate::getDate('DATE+TIME', $filter_data['start_date']) . ' End: ' . TTDate::getDate('DATE+TIME', $filter_data['end_date']), __FILE__, __LINE__, __METHOD__, 10);
        $slf->getAPISearchByCompanyIdAndArrayCriteria($this->getUserObject()->getCompany(), $filter_data, $limit, null, null, $order);
        if ($slf->getRecordCount() > 0) {
            Debug::text('Found schedule rows: ' . $slf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
            foreach ($slf as $s_obj) {
                $this->schedule[$s_obj->getID()] = $s_obj;
            }

            return true;
        }

        Debug::text('No schedule rows...', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function getUserWageData($start_date, $end_date)
    {
        $uwlf = TTnew('UserWageListFactory');
        $uwlf->getByUserIdAndStartDateAndEndDate($this->getUserObject()->getId(), $start_date, $end_date);
        if ($uwlf->getRecordCount() > 0) {
            foreach ($uwlf as $uw_obj) {
                $this->user_wages[$uw_obj->getId()] = $uw_obj;
            }

            //Because wage entries can be added as different dates are calculated, the order isn't guaranteed.
            //Therefore manually sort the entries again each time new data is retrieved.
            uasort($this->user_wages, array($this, 'UserWageSortByEffectiveDateDesc'));

            Debug::Text('User wage rows: ' . count($this->user_wages), __FILE__, __LINE__, __METHOD__, 10);
            return true;
        }

        Debug::text('No user wage rows...', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    //Filter schedule policies to only those that affect a specific shift.

    public function getCurrencyRateData($start_date, $end_date)
    {
        $crlf = TTnew('CurrencyRateListFactory');
        $crlf->getByCurrencyIdAndStartDateAndEndDate($this->getUserObject()->getCurrency(), $start_date, $end_date);
        if ($crlf->getRecordCount() > 0) {
            foreach ($crlf as $cr_obj) {
                $this->currency_rates[$cr_obj->getDateStamp()] = $cr_obj;
            }
            Debug::Text('Currency Rates rows before gaps filled: ' . count($this->currency_rates), __FILE__, __LINE__, __METHOD__, 10);

            //Loop through all days and fill in any currency gaps.
            for ($x = TTDate::getBeginDayEpoch($start_date); $x <= TTDate::getBeginDayEpoch($end_date); $x += 86400) {
                if (!isset($this->currency_rates[$x])) {
                    Debug::Text(' Filling in gap: Date: ' . TTDate::getDate('DATE', $x) . ' with Rate: 1', __FILE__, __LINE__, __METHOD__, 10);

                    $crf = TTnew('CurrencyRateFactory');
                    $crf->setCurrency($this->getUserObject()->getCurrency());
                    $crf->setDateStamp($x);
                    $crf->setConversionRate(1);
                    $this->currency_rates[$crf->getDateStamp()] = $crf;
                }
            }

            Debug::Text('Currency Rates rows after gaps filled: ' . count($this->currency_rates), __FILE__, __LINE__, __METHOD__, 10);
            return true;
        }

        Debug::text('No currency rate rows...', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    //Get all possible schedule policies.

    public function getUserDateTotalData($start_date = null, $end_date = null)
    {
        $udtlf = TTNew('UserDateTotalListFactory');
        $filter_data = array(
            'user_id' => $this->getUserObject()->getId(),
            'start_date' => $start_date,
            'end_date' => $end_date,
            //'date' => $date_stamps,

            //This could be called several times, but exclude already obtained rows each time.
            'exclude_id' => array_keys((array)$this->user_date_total),
        );

        //If SORT order is changed, also change it in: sortUserDateTotalDataByDateAndObjectTypeAndStartTimeStampAndID()
        $udtlf->getAPISearchByCompanyIdAndArrayCriteria($this->getUserObject()->getCompany(), $filter_data, null, null, null, array('a.date_stamp' => 'asc', 'a.object_type_id' => 'asc', 'a.start_time_stamp' => 'asc', 'a.id' => 'asc'));
        if ($udtlf->getRecordCount() > 0) {
            Debug::text('Found UserDateTotal rows: ' . $udtlf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
            foreach ($udtlf as $udt_obj) {
                $this->user_date_total[$udt_obj->getId()] = $udt_obj;
            }

            return true;
        }

        Debug::text('No UserDateTotal rows... Start Date: ' . TTDate::getDate('DATE', $start_date) . ' End Date: ' . TTDate::getDate('DATE', $end_date), __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function getUnderTimeAbsenceTimePolicy()
    {
        $this->schedule_undertime_absence_policy_ids = array();

        $splf = $this->schedule_policy_rs;
        if (is_array($splf) and count($splf) > 0) {
            Debug::text('Found schedule policy rows: ' . count($splf), __FILE__, __LINE__, __METHOD__, 10);
            foreach ($splf as $sp_obj) {
                $this->schedule_undertime_absence_policy_ids = array_merge($this->schedule_undertime_absence_policy_ids, (array)$sp_obj->getFullShiftAbsencePolicyID(), (array)$sp_obj->getPartialShiftAbsencePolicyID());
            }
            unset($sp_obj);
        }

        if (count($this->schedule_undertime_absence_policy_ids) > 0) {
            $aplf = TTnew('AbsencePolicyListFactory');
            $aplf->getByIdAndCompanyId($this->schedule_undertime_absence_policy_ids, $this->getUserObject()->getCompany());
            if ($aplf->getRecordCount() > 0) {
                Debug::text('Found undertime absence policy rows: ' . $aplf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
                foreach ($aplf as $ap_obj) {
                    $this->undertime_absence_policy[$ap_obj->getId()] = $ap_obj;
                }

                return true;
            }
        }

        Debug::text('No undertime absence policy rows...', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    //FIXME: This can be activated later on with more testing, specifically used in cases where Contributing Shifts split on certain times of day.

    public function getMealTimePolicy()
    {
        $this->schedule_meal_time_policy_ids = array();

        $splf = $this->schedule_policy_rs;
        if (is_array($splf) and count($splf) > 0) {
            Debug::text('Found schedule policy rows: ' . count($splf), __FILE__, __LINE__, __METHOD__, 10);
            foreach ($splf as $sp_obj) {
                if (is_array($sp_obj->getMealPolicy()) and count($sp_obj->getMealPolicy()) > 0) {
                    $this->schedule_meal_time_policy_ids = array_merge($this->schedule_meal_time_policy_ids, (array)$sp_obj->getMealPolicy());
                }
            }
            unset($sp_obj);
        }

        $mplf = TTnew('MealPolicyListFactory');
        $mplf->getByPolicyGroupUserIdOrId($this->getUserObject()->getId(), $this->schedule_meal_time_policy_ids);
        if ($mplf->getRecordCount() > 0) {
            Debug::text('Found meal policy rows: ' . $mplf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
            foreach ($mplf as $mp_obj) {
                if ($mp_obj->getColumn('from_policy_group') == 1) {
                    $this->meal_time_policy[$mp_obj->getId()] = $mp_obj;
                } else {
                    $this->schedule_policy_meal_time_policy[$mp_obj->getId()] = $mp_obj;
                }
            }

            Debug::text('Found schedule policy meal policy rows: ' . count($this->schedule_policy_meal_time_policy) . ' Policy Group: ' . count($this->meal_time_policy), __FILE__, __LINE__, __METHOD__, 10);
            return true;
        }

        Debug::text('No meal policy rows...', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    //Compacts UDT records based on override, so calcualteSystemTotalTime() is correct
    //  even when new UDT records are added (override=1) and some are modified directly.

    public function getBreakTimePolicy()
    {
        $this->schedule_break_time_policy_ids = array();

        $splf = $this->schedule_policy_rs;
        if (is_array($splf) and count($splf) > 0) {
            foreach ($splf as $sp_obj) {
                $this->schedule_break_time_policy_ids = array_merge($this->schedule_break_time_policy_ids, (array)$sp_obj->getBreakPolicy());
            }
            unset($sp_obj);
        }

        $bplf = TTnew('BreakPolicyListFactory');
        $bplf->getByPolicyGroupUserIdOrId($this->getUserObject()->getId(), $this->schedule_break_time_policy_ids);
        if ($bplf->getRecordCount() > 0) {
            Debug::text('Found break policy rows: ' . $bplf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
            foreach ($bplf as $bp_obj) {
                if ($bp_obj->getColumn('from_policy_group') == 1) {
                    $this->break_time_policy[$bp_obj->getId()] = $bp_obj;
                } else {
                    $this->schedule_policy_break_time_policy[$bp_obj->getId()] = $bp_obj;
                }
            }

            return true;
        }

        Debug::text('No break policy rows...', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function getRegularTimePolicy()
    {
        $this->schedule_regular_time_policy_ids = array();

        $splf = $this->schedule_policy_rs;
        if (is_array($splf) and count($splf) > 0) {
            foreach ($splf as $sp_obj) {
                //Don't handle excludeRegularTimePolicy() here, as we need to get all possible policy IDs that could come into play, then just ignore them in filterRegularTimePolicy()
                if (is_array($sp_obj->getIncludeRegularTimePolicy()) and count($sp_obj->getIncludeRegularTimePolicy()) > 0) {
                    $this->schedule_regular_time_policy_ids = array_merge($this->schedule_regular_time_policy_ids, (array)$sp_obj->getIncludeRegularTimePolicy());
                }
            }
            unset($sp_obj);
        }

        $rtplf = TTnew('RegularTimePolicyListFactory');
        $rtplf->getByPolicyGroupUserIdOrId($this->getUserObject()->getId(), $this->schedule_regular_time_policy_ids);
        if ($rtplf->getRecordCount() > 0) {
            Debug::text('Found regular time policy rows: ' . $rtplf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
            foreach ($rtplf as $rtp_obj) {
                $this->regular_time_policy[$rtp_obj->getId()] = $rtp_obj;
            }

            return true;
        }

        Debug::text('No regular time policy rows...', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function getOverTimePolicy()
    {
        $this->schedule_over_time_policy_ids = array();

        $splf = $this->schedule_policy_rs;
        if (is_array($splf) and count($splf) > 0) {
            foreach ($splf as $sp_obj) {
                if (is_array($sp_obj->getIncludeOverTimePolicy()) and count($sp_obj->getIncludeOverTimePolicy()) > 0) {
                    $this->schedule_over_time_policy_ids = array_merge($this->schedule_over_time_policy_ids, (array)$sp_obj->getIncludeOverTimePolicy());
                }
            }
            unset($sp_obj);
        }

        $otplf = TTnew('OverTimePolicyListFactory');
        $otplf->getByPolicyGroupUserIdOrId($this->getUserObject()->getId(), $this->schedule_over_time_policy_ids);
        if ($otplf->getRecordCount() > 0) {
            Debug::text('Found overtime policy rows: ' . $otplf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
            //$this->over_time_policy_rs = $otplf;
            foreach ($otplf as $otp_obj) {
                $this->over_time_policy[$otp_obj->getId()] = $otp_obj;
            }

            return true;
        }

        Debug::text('No overtime policy rows...', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function getPremiumTimePolicy()
    {
        $this->schedule_premium_time_policy_ids = array();

        $splf = $this->schedule_policy_rs;
        if (is_array($splf) and count($splf) > 0) {
            foreach ($splf as $sp_obj) {
                if (is_array($sp_obj->getIncludePremiumPolicy()) and count($sp_obj->getIncludePremiumPolicy()) > 0) {
                    $this->schedule_premium_time_policy_ids = array_merge($this->schedule_premium_time_policy_ids, (array)$sp_obj->getIncludePremiumPolicy());
                }
            }
            unset($sp_obj);
        }

        $pplf = TTnew('PremiumPolicyListFactory');
        $pplf->getByPolicyGroupUserIdOrId($this->getUserObject()->getId(), $this->schedule_premium_time_policy_ids);
        if ($pplf->getRecordCount() > 0) {
            Debug::text('Found premium policy rows: ' . $pplf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
            foreach ($pplf as $pp_obj) {
                $this->premium_time_policy[$pp_obj->getId()] = $pp_obj;
            }

            return true;
        }

        Debug::text('No premium policy rows...', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function getAccrualPolicy()
    {
        $aplf = TTnew('AccrualPolicyListFactory');
        $aplf->getByPolicyGroupUserIdAndType($this->getUserObject()->getId(), 30); //Hour based only.
        if ($aplf->getRecordCount() > 0) {
            Debug::text('Found accrual policy rows: ' . $aplf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
            foreach ($aplf as $ap_obj) {
                $this->accrual_policy[$ap_obj->getId()] = $ap_obj;
            }

            return true;
        }

        Debug::text('No accrual policy rows...', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    //Get all overtime policies that could possibly apply, including from schedule policies.

    public function getPayCode()
    {
        $pclf = TTnew('PayCodeListFactory');
        $pclf->getByCompanyId($this->getUserObject()->getCompany());
        if ($pclf->getRecordCount() > 0) {
            foreach ($pclf as $pc_obj) {
                $this->pay_codes[$pc_obj->getId()] = $pc_obj;
            }

            Debug::Text('Pay code rows: ' . count($this->pay_codes), __FILE__, __LINE__, __METHOD__, 10);
            return true;
        }

        Debug::text('No pay code rows...', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function getPayFormulaPolicy()
    {
        $pfplf = TTnew('PayFormulaPolicyListFactory');
        $pfplf->getByCompanyId($this->getUserObject()->getCompany());
        if ($pfplf->getRecordCount() > 0) {
            foreach ($pfplf as $pfp_obj) {
                $this->pay_formula_policy[$pfp_obj->getId()] = $pfp_obj;
            }

            Debug::Text('Pay Formula Policy rows: ' . count($this->pay_formula_policy), __FILE__, __LINE__, __METHOD__, 10);
            return true;
        }

        Debug::text('No pay formula policy rows...', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function getHolidayPolicy()
    {
        //Get Holiday policies and determine how many days we need to look ahead/behind in order
        //to recalculate the holiday eligilibility/time.
        $this->holiday_before_days = 0;
        $this->holiday_after_days = 0;

        //Need to be able to get holiday policies included in just contributing shift policies.
        //But we also need to be able to know if the policies are assigned to policy groups or not, as only those ones are calculated for absence time.
        //We can't get holiday policies until we get all contributing shifts, and we can't get contributing shifts until we get all holiday policies...
        $hplf = TTnew('HolidayPolicyListFactory');
        //$hplf->getByPolicyGroupUserId( $this->getUserObject()->getId() );
        $hplf->getByPolicyGroupCompanyIdAndUserIdOrAssignedToContributingShiftPolicy($this->getUserObject()->getCompany(), $this->getUserObject()->getID());
        if ($hplf->getRecordCount() > 0) {
            Debug::text('Found holiday policy rows: ' . $hplf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
            foreach ($hplf as $hp_obj) {
                //Debug::text('  Holiday Policy: '. $hp_obj->getName() .' Minimum Worked Period Days: '. $hp_obj->getMinimumWorkedPeriodDays() .' After Days: '. $hp_obj->getMinimumWorkedAfterPeriodDays() .' Average Time Days: '. $hp_obj->getAverageTimeDays() , __FILE__, __LINE__, __METHOD__, 10);
                $this->holiday_policy[$hp_obj->getId()] = $hp_obj;

                if ($hp_obj->getMinimumWorkedPeriodDays() > $this->holiday_before_days) {
                    $this->holiday_before_days = $hp_obj->getMinimumWorkedPeriodDays();
                }
                if ($hp_obj->getAverageTimeDays() > $this->holiday_before_days) {
                    $this->holiday_before_days = $hp_obj->getAverageTimeDays();
                }
                if ($hp_obj->getMinimumWorkedAfterPeriodDays() > $this->holiday_after_days) {
                    $this->holiday_after_days = $hp_obj->getMinimumWorkedAfterPeriodDays();
                }

                if ($hp_obj->getColumn('assigned_to_policy_group') == 1) {
                    $this->policy_group_holiday_policy_ids[$hp_obj->getID()] = true;
                }
            }

            return true;
        }

        Debug::text('No holiday time policy rows...', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    //Get all meal policies that could possibly apply, including from schedule policies.

    public function getHolidayData($start_date, $end_date, $enable_recalculate_holiday = true)
    {
        if (count($this->holiday_policy) == 0) {
            Debug::text('No holiday policies, not checking for holidays...', __FILE__, __LINE__, __METHOD__, 10);
            return false;
        }
        Debug::text('Holiday Initial: Search Start date: ' . TTDate::getDate('DATE', $start_date) . ' End date: ' . TTDate::getDate('DATE', $end_date) . ' Holiday Before Days: ' . $this->holiday_before_days . ' After Days: ' . $this->holiday_after_days, __FILE__, __LINE__, __METHOD__, 10);

        //Keep in mind that when recalculating days, we typically search for holidays in the *future*
        //So the holiday_before_days settings defines the end date, and holiday_after_days defines the start date.
        // Because if we are recaluating Dec 1st to Dec 7th, we need to find the Dec 25th holiday that may take into account 30 days in the past.
        // So the holiday search start/end range should be Start=$date - $holiday_after_days End=$date + $holiday_before_days
        $tmp_end_date = $end_date;
        if ($this->holiday_before_days > 0) {
            //$tmp_end_date = TTDate::getBeginWeekEpoch( ( $end_date + ( $this->holiday_before_days * 86400 ) ), $this->start_week_day_id );
            $tmp_end_date = TTDate::getEndDayEpoch(($end_date + ($this->holiday_before_days * 86400)));
        }

        //Don't look past the current real-time date, as we don't want to be recalculating holidays way into the future that haven't occurred yet.
        //For example Sept 1st Holiday (Labor Day) could cause holidays to be recalculated all the way to January 1st in the case of Alberta and 5 of 9 week day calculation.
        if ($tmp_end_date > time()) {
            Debug::text('Limiting Holiday search to current date...', __FILE__, __LINE__, __METHOD__, 10);
            $tmp_end_date = time();
        }

        $tmp_start_date = $start_date;
        if ($this->holiday_after_days > 0) {
            //$tmp_start_date = TTDate::getEndWeekEpoch( ( TTDate::getEndDayEpoch( $start_date ) - ( $this->holiday_before_days * 86400 ) + 3601 ), $this->start_week_day_id );
            $tmp_start_date = TTDate::getBeginDayEpoch((TTDate::getMiddleDayEpoch($start_date) - ($this->holiday_after_days * 86400)));
        }

        if ($tmp_start_date < $start_date) {
            $start_date = $tmp_start_date;
        }
        if ($tmp_end_date > $end_date) {
            $end_date = $tmp_end_date;
        }

        //When shifts span midnight, we need to extend the start/end dates by the maximum shift time.
        //That way if the employee works May 24th 9PM to 7AM on the last day of the week (and pay period), and May 25th is the Holiday, we include the holiday.
        $maximum_shift_time = (int)$this->pay_period_schedule_obj->getMaximumShiftTime();
        $start_date = ($start_date - $maximum_shift_time);
        $end_date = ($end_date + $maximum_shift_time);

        Debug::text('Holiday Search: Start date: ' . TTDate::getDate('DATE', $start_date) . ' End date: ' . TTDate::getDate('DATE', $end_date) . ' Maximum Shift Time: ' . $maximum_shift_time, __FILE__, __LINE__, __METHOD__, 10);

        //We make sure there are holiday policies at the top of this function.
        $hlf = TTnew('HolidayListFactory');
        $hlf->getByHolidayPolicyIdAndStartDateAndEndDate(array_keys($this->holiday_policy), $start_date, $end_date);
        if ($hlf->getRecordCount() > 0) {
            Debug::text('Found holiday rows: ' . $hlf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
            foreach ($hlf as $h_obj) {
                //Allow duplicate holidays with the same date to be assigned to this array
                //As there may be cases where multiple holiday policies are assigned to the employee (through contributing shifts and policy groups)
                // where some are active  and some are not. If we don't allow duplicates it may always be denied or accepted when it shouldn't be.
                //$this->holiday[$h_obj->getDateStamp()] = $h_obj;
                $this->holiday[] = $h_obj;

                if ($enable_recalculate_holiday == true and $this->getFlag('holiday') == true) { //Don't add holidays to pending dates if we aren't calculating them to begin with.
                    $this->addPendingCalculationDate($h_obj->getDateStamp()); //Add each holiday to the pending calculation list.
                }
            }

            return true;
        }

        Debug::text('No holiday rows...', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function addPendingCalculationDate($start_date, $end_date = null)
    {
        if ($start_date == '' and $end_date == '') {
            return false;
        }

        if ($end_date == '') {
            if (is_array($start_date)) {
                $pending_dates = array_unique($start_date);
            } else {
                $pending_dates = array($start_date);
            }
        } else {
            $pending_dates = TTDate::getDateArray($start_date, $end_date);
        }

        if (count($pending_dates) == 1) {
            Debug::Text('Add Pending Date: ' . TTDate::getDate('DATE', $pending_dates[0]), __FILE__, __LINE__, __METHOD__, 10);
        } else {
            Debug::Text('Add Pending Dates: ' . count($pending_dates), __FILE__, __LINE__, __METHOD__, 10);
            //Debug::Arr($pending_dates, 'Add Pending Dates: '. count($pending_dates), __FILE__, __LINE__, __METHOD__, 10);
        }

        if (is_array($pending_dates)) {
            foreach ($pending_dates as $tmp_date) {
                $tmp_date = TTDate::getBeginDayEpoch($tmp_date);

                //Make sure we don't calculate dates twice in the same run.
                //  As when handling averaging or other holidays its possible they may get re-added.
                if (!isset($this->dates['calculated'][$tmp_date])) {
                    $this->dates['pending_calculation'][$tmp_date] = true;
                    //Debug::Text('  Added Pending Date: '. TTDate::getDate('DATE', $tmp_date ) .'('. $tmp_date .')', __FILE__, __LINE__, __METHOD__, 10);
                }
            }
        }

        //Always sort pending dates so they are in chronological order.
        ksort($this->dates['pending_calculation']);

        return true;
    }

    public function getContributingShiftPolicy()
    {
        $this->contributing_shift_policy_ids = array();

        $rtplf = $this->regular_time_policy;
        if (is_array($rtplf) and count($rtplf) > 0) {
            foreach ($rtplf as $rtp_obj) {
                $this->contributing_shift_policy_ids[] = $rtp_obj->getContributingShiftPolicy();
            }
        }
        unset($rtplf, $rtp_obj);

        $otplf = $this->over_time_policy;
        if (is_array($otplf) and count($otplf) > 0) {
            foreach ($otplf as $otp_obj) {
                $this->contributing_shift_policy_ids[] = $otp_obj->getContributingShiftPolicy();
                $this->contributing_shift_policy_ids[] = $otp_obj->getTriggerTimeAdjustContributingShiftPolicy();
            }
        }
        unset($otplf, $otp_obj);

        $hplf = $this->holiday_policy;
        if (is_array($hplf) and count($hplf) > 0) {
            foreach ($hplf as $hp_obj) {
                $this->contributing_shift_policy_ids[] = $hp_obj->getContributingShiftPolicy();
                $this->contributing_shift_policy_ids[] = $hp_obj->getEligibleContributingShiftPolicy();
            }
        }
        unset($hplf, $hp_obj);

        $pplf = $this->premium_time_policy;
        if (is_array($pplf) and count($pplf) > 0) {
            foreach ($pplf as $pp_obj) {
                $this->contributing_shift_policy_ids[] = $pp_obj->getContributingShiftPolicy();
            }
        }
        unset($pplf, $pp_obj);

        $pfplf = $this->pay_formula_policy;
        if (is_array($pfplf) and count($pfplf) > 0) {
            foreach ($pfplf as $pfp_obj) {
                if ($pfp_obj->getWageSourceContributingShiftPolicy() > 0) {
                    $this->contributing_shift_policy_ids[] = $pfp_obj->getWageSourceContributingShiftPolicy();
                    $this->contributing_shift_policy_ids[] = $pfp_obj->getTimeSourceContributingShiftPolicy();
                }
            }
        }
        unset($pfplf, $pfp_obj);

        $aplf = $this->accrual_policy;
        if (is_array($aplf) and count($aplf) > 0) {
            foreach ($aplf as $ap_obj) {
                $this->contributing_shift_policy_ids[] = $ap_obj->getContributingShiftPolicy();
            }
        }
        unset($aplf, $ap_obj);

        $this->contributing_shift_policy_ids = array_unique($this->contributing_shift_policy_ids);
        if (count($this->contributing_shift_policy_ids) > 0) {
            $csplf = TTnew('ContributingShiftPolicyListFactory');
            $csplf->getByIdAndCompanyId($this->contributing_shift_policy_ids, $this->getUserObject()->getCompany());
            if ($csplf->getRecordCount() > 0) {
                Debug::text('Found contributing shift policy rows: ' . $csplf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);

                //$this->contributing_shift_policy_rs = $csplf;
                foreach ($csplf as $csp_obj) {
                    $this->contributing_shift_policy[$csp_obj->getId()] = $csp_obj;
                }

                //Debug::Arr($this->contributing_shift_policy, 'Contributing shift policy rows...', __FILE__, __LINE__, __METHOD__, 10);

                return true;
            }
        }

        Debug::text('No contributing shift policy rows...', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function getContributingPayCodePolicy()
    {
        $csplf = $this->contributing_shift_policy;
        if (is_array($csplf) and count($csplf) > 0) {
            foreach ($csplf as $csp_obj) {
                $this->contributing_pay_code_policy_ids[] = $csp_obj->getContributingPayCodePolicy();
            }
            unset($csp_obj);

            if (count($this->contributing_pay_code_policy_ids) > 0) {
                $cpcplf = TTnew('ContributingPayCodePolicyListFactory');
                $cpcplf->getByIdAndCompanyId($this->contributing_pay_code_policy_ids, $this->getUserObject()->getCompany());
                if ($cpcplf->getRecordCount() > 0) {
                    Debug::text('Found contributing pay code policy rows: ' . $cpcplf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
                    //$this->contributing_pay_code_policy_rs = $cpcplf;

                    foreach ($cpcplf as $cpcp_obj) {
                        $this->contributing_pay_code_policy[$cpcp_obj->getId()] = $cpcp_obj;
                        $this->contributing_pay_codes_by_policy_id[$cpcp_obj->getId()] = $cpcp_obj->getPayCode();
                    }

                    //Debug::Arr($this->contributing_pay_codes_by_policy_id, 'Contributing pay code policy rows...', __FILE__, __LINE__, __METHOD__, 10);
                    return true;
                }
            }
        }

        Debug::text('No contributing pay code policy rows...', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    //Get all absence policies that could possibly apply, including from schedule policies.

    public function getExceptionPolicy()
    {
        $eplf = TTnew('ExceptionPolicyListFactory');
        $eplf->getByPolicyGroupUserIdAndActive($this->getUserObject()->getId(), true);
        if ($eplf->getRecordCount() > 0) {
            Debug::text(' Found Active Exceptions: ' . $eplf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
            foreach ($eplf as $ep_obj) {
                $this->exception_policy[$ep_obj->getId()] = $ep_obj;
            }

            return true;
        }

        Debug::text('No exception policy rows...', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function getPunchData($start_date, $end_date)
    {
        if (is_object($this->pay_period_schedule_obj)) {
            $maximum_shift_time = $this->pay_period_schedule_obj->getMaximumShiftTime();
        } else {
            $maximum_shift_time = (16 * 3600);
        }

        $plf = TTnew('PunchListFactory');
        //We need to double the maximum shift time when searching for punches.
        //Assuming a maximum punch time of 14hrs:
        // In: 10:00AM Out: 2:00PM
        // In: 6:00PM Out: 6:00AM (next day)
        // The above scenario when adding the last 6:00AM punch on the next day will only look back 14hrs and not find the first
        // punch pair, therefore allowing more than 14hrs on the same day.
        // So we need to extend the maximum shift time just when searching for punches and let getShiftData() sort out the proper maximum shift time itself.
        //
        //Make sure we use begin/end day epochs, because otherwise if middle day epoch is used, with a 12hr maximum shift time,
        //  if the last day of the week is Sun Dec 26-2015, and the punches are: In: 9:00PM Out Lunch: 3:30AM In Lunch: 4:15AM Out: 6:00AM
        //  the last punch pair won't be included in the filter, since Dec 26 @ 12:00PM + 12hrs is only Dec 27 @ 12:00AM.
        //
        //  If the employee punches out for lunch at 2PM and back in at 3PM, if the 2nd punch pair is only returned and not the first, it can cause M3 exceptions that are invalid.
        //    Therefore always return all punches on the entire day.

        //$plf->getShiftPunchesByUserIDAndEpoch( $user_id, $epoch, $punch_control_id, ( $maximum_shift_time * 2 ) );
        $plf->getShiftPunchesByUserIDAndStartDateAndEndDate($this->getUserObject()->getId(), TTDate::getBeginDayEpoch($start_date), TTDate::getEndDayEpoch($end_date), 0, ($maximum_shift_time * 2));
        if ($plf->getRecordCount() > 0) {
            Debug::text('Found punch rows: ' . $plf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
            foreach ($plf as $p_obj) {
                $this->punch[$p_obj->getID()] = $p_obj;
            }

            return true;
        }

        Debug::text('No punch rows...', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function getExceptionData($start_date = null, $end_date = null)
    {
        $elf = TTNew('ExceptionListFactory');
        $elf->getByCompanyIDAndUserIdAndStartDateAndEndDate($this->getUserObject()->getCompany(), $this->getUserObject()->getId(), $start_date, $end_date);
        if ($elf->getRecordCount() > 0) {
            Debug::text('Found existing exception rows: ' . $elf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
            foreach ($elf as $e_obj) {
                $this->exception[$e_obj->getID()] = $e_obj;
            }

            return true;
        }

        Debug::text('No exception rows...', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function filterUserDateTotalDataByContributingShiftPolicy($start_date, $end_date, $contributing_shift_policy_obj, $object_type_ids = null, $additional_pay_code_ids = array(), $additional_src_object_ids = array())
    {
        if (!is_object($contributing_shift_policy_obj)) {
            Debug::text('ERROR: Contributing Shift Policy is not an object!', __FILE__, __LINE__, __METHOD__, 10);
            return false;
        }

        $udtlf = $this->user_date_total;
        if (is_array($udtlf) and count($udtlf) > 0) {
            Debug::text('Filtering user date total rows: ' . count($udtlf), __FILE__, __LINE__, __METHOD__, 10);

            //Optimization, to avoid doing it in a loop.
            if (!is_array($start_date)) {
                $start_date = TTDate::getMiddleDayEpoch($start_date);
            }
            if ($end_date != '') {
                $end_date = TTDate::getMiddleDayEpoch($end_date);
            }

            $pay_code_ids = null;
            if (isset($this->contributing_pay_codes_by_policy_id[$contributing_shift_policy_obj->getContributingPayCodePolicy()])) {
                $pay_code_ids = (array)$this->contributing_pay_codes_by_policy_id[$contributing_shift_policy_obj->getContributingPayCodePolicy()];
            }

            if (is_array($additional_pay_code_ids) and count($additional_pay_code_ids) > 0) {
                //Debug::Arr($additional_pay_code_ids, 'Adding additional Pay Code Ids: ', __FILE__, __LINE__, __METHOD__, 10);
                $pay_code_ids = array_merge($pay_code_ids, (array)$additional_pay_code_ids);
            }

            if (is_array($additional_src_object_ids) and count($additional_src_object_ids) > 0) {
                $additional_src_object_type_id = key($additional_src_object_ids);
                $additional_src_object_ids = (isset($additional_src_object_ids[$additional_src_object_type_id])) ? $additional_src_object_ids[$additional_src_object_type_id] : array();
            }

            //If object_type_ids includes worked time, we need to automatically add pay_code_id=0 so "AND" can be used on the matching below.
            //if ( $object_type_ids == NULL OR ( is_array( $object_type_ids ) AND in_array( 10, $object_type_ids ) ) ) { //Worked time.
            if (is_array($object_type_ids) and in_array(10, $object_type_ids)) { //Worked time.
                $pay_code_ids[] = 0;
            }

            $retarr = array();
            $already_split_key = array();

            //Debug::Arr($object_type_ids, 'Object Type IDs: ', __FILE__, __LINE__, __METHOD__, 10);
            //Debug::Arr($pay_code_ids, 'Pay Code IDs: ', __FILE__, __LINE__, __METHOD__, 10);
            //Debug::Arr( array( $additional_src_object_type_id, $additional_src_object_ids ), 'Additional SRC Object IDs: ', __FILE__, __LINE__, __METHOD__, 10);

            $udtlf = new ArrayIterator(array_keys($udtlf)); //Create Iterator so when splitting UDT records, we can continue to loop over them.
            foreach ($udtlf as $udt_key) {
                $udt_obj = $this->user_date_total[$udt_key];
                $udt_obj_date_stamp = TTDate::getMiddleDayEpoch($udt_obj->getDateStamp()); //Optimization - Move outside loop.

                if (($object_type_ids == null or in_array($udt_obj->getObjectType(), $object_type_ids))) {
                    if (($pay_code_ids == null or in_array($udt_obj->getPayCode(), $pay_code_ids))
                        or (isset($additional_src_object_type_id) and is_array($additional_src_object_ids) and $udt_obj->getObjectType() == $additional_src_object_type_id and in_array($udt_obj->getSourceObject(), $additional_src_object_ids))
                    ) {
                        if (
                        (
                            (!is_array($start_date) and $udt_obj_date_stamp >= $start_date and $udt_obj_date_stamp <= $end_date)
                            or
                            (is_array($start_date) and in_array(TTDate::getBeginDayEpoch($udt_obj->getDateStamp()), $start_date))
                        )
                        ) {

                            //FIXME: For some ./run.sh --filter MealBreakPolicyTest::testAutoAddMultipleBreakPolicyE
                            //Creates UDT rows with no start timestamp but with a end time stamp, which causes problems.
                            //if ( $udt_obj->getStartTimeStamp() == FALSE AND $udt_obj->getEndTimeStamp() != FALSE ) {
                            //	Debug::Text('ID: '. $udt_obj->getID() .' Start: '. TTDate::getDate('DATE+TIME', $udt_obj->getStartTimeStamp() ) .' End: '. TTDate::getDate('DATE+TIME', $udt_obj->getEndTimeStamp() ), __FILE__, __LINE__, __METHOD__, 10);
                            //}

                            if (!isset($shift_data)) {
                                $shift_data = $this->getShiftData($this->filterUserDateTotalDataByPayCodeIDs($start_date, $end_date, $pay_code_ids), null, 'all_with_map'); //Don't filter any shifts, as we need to check against them all in isActiveFilterTime below.
                            }

//							Debug::text('Checking: Key: '. $udt_key .' UDT ID: '. $udt_obj->getID() .' Date Stamp: '. TTDate::getDate('DATE', $udt_obj->getDateStamp() ) .' Start Time: '. TTDate::getDate('DATE+TIME', $udt_obj->getStartTimeStamp() ) .' End Time: '. TTDate::getDate('DATE+TIME', $udt_obj->getEndTimeStamp() ) .' Pay Code ID: '. $udt_obj->getPayCode() .' Total Time: '. $udt_obj->getTotalTime() .'($'. (float)$udt_obj->getTotalTimeAmount().' Base Rate: '.(float)$udt_obj->getBaseHourlyRate().') Object Type ID: '. $udt_obj->getObjectType() .' SRC Object ID: '. $udt_obj->getSourceObject() .' ID: '. $udt_obj->getID(), __FILE__, __LINE__, __METHOD__, 10);
                            //Handle contributing shift filters here.

                            //Debug::text('Found: UDT ID: '. $udt_obj->getID() .' Date Stamp: '. TTDate::getDate('DATE', $udt_obj->getDateStamp() ) .' Start Time: '. TTDate::getDate('DATE+TIME', $udt_obj->getStartTimeStamp() ) .' End Time: '. TTDate::getDate('DATE+TIME', $udt_obj->getEndTimeStamp() ) .' Pay Code ID: '. $udt_obj->getPayCode() .' Total Time: '. $udt_obj->getTotalTime() .'($'. (float)$udt_obj->getTotalTimeAmount().' Base Rate: '.(float)$udt_obj->getBaseHourlyRate().') Object Type ID: '. $udt_obj->getObjectType() .' SRC Object ID: '. $udt_obj->getSourceObject() .' ID: '. $udt_obj->getID(), __FILE__, __LINE__, __METHOD__, 10);
                            //Handle partial shifts here.
                            if (!isset($already_split_key[$udt_key]) and $contributing_shift_policy_obj->getIncludeShiftType() == 100) {
                                //Handle partial shifts here.
                                $split_udt_obj_arr = $contributing_shift_policy_obj->getPartialUserDateTotalObject($udt_obj, $udt_key, $this);
                                foreach ($split_udt_obj_arr as $tmp_udt_key => $tmp_udt_obj) {
                                    //Debug::text('Split: Key: '. $tmp_udt_key .' UDT ID: '. $tmp_udt_obj->getID() .' Date Stamp: '. TTDate::getDate('DATE', $tmp_udt_obj->getDateStamp() ) .' Start Time: '. TTDate::getDate('DATE+TIME', $tmp_udt_obj->getStartTimeStamp() ) .' End Time: '. TTDate::getDate('DATE+TIME', $tmp_udt_obj->getEndTimeStamp() ) .'  Pay Code ID: '. $tmp_udt_obj->getPayCode() .' Total Time: '. $tmp_udt_obj->getTotalTime() .'($'. (float)$tmp_udt_obj->getTotalTimeAmount().' Base Rate: '.(float)$tmp_udt_obj->getBaseHourlyRate().') Object Type ID: '. $tmp_udt_obj->getObjectType() .' SRC Object ID: '. $tmp_udt_obj->getSourceObject() .' ID: '. $tmp_udt_obj->getID(), __FILE__, __LINE__, __METHOD__, 10);

                                    $this->user_date_total[$tmp_udt_key] = $tmp_udt_obj;

                                    $udtlf->append($tmp_udt_key); //Append split UDT records so they can be looped on later.

                                    $already_split_key[$tmp_udt_key] = true;
                                }
                                unset($split_udt_obj_arr, $tmp_udt_key, $tmp_udt_obj);
                            } else {
                                $retarr[$udt_key] = $udt_obj;
                            }
                        }
                        //else {
                        //	Debug::text('Skipping, due to date. UDT Date Stamp: '. TTDate::getDate('DATE', $udt_obj_date_stamp ) .' Pay Code ID: '. $udt_obj->getPayCode() .' Total Time: '. $udt_obj->getTotalTime() .' Object Type: '. $udt_obj->getObjectType() .' Filter: Start Date: '. TTDate::getDate('DATE', $start_date ) .' End Date: '. TTDate::getDate('DATE', $end_date ), __FILE__, __LINE__, __METHOD__, 10);
                        //}
                    }
                    //else {
                    //	Debug::Text('Skipping, due to pay_code_id. UDT Date Stamp: '. TTDate::getDate('DATE', $udt_obj_date_stamp ) .' Pay Code ID: '. $udt_obj->getPayCode() .' Total Time: '. $udt_obj->getTotalTime() .' Object Type: '. $udt_obj->getObjectType() .' Filter: Start Date: '. TTDate::getDate('DATE', $start_date ) .' End Date: '. TTDate::getDate('DATE', $end_date ) .' Object Type ID: '. $udt_obj->getObjectType() .' SRC Object ID: '. $udt_obj->getSourceObject(), __FILE__, __LINE__, __METHOD__, 10);
                    //}
                }
                //else {
                //	Debug::Text('Skipping, due to object_type_id. UDT Date Stamp: '. TTDate::getDate('DATE', $udt_obj_date_stamp ) .' Pay Code ID: '. $udt_obj->getPayCode() .' Total Time: '. $udt_obj->getTotalTime() .' Object Type: '. $udt_obj->getObjectType() .' Filter: Start Date: '. TTDate::getDate('DATE', $start_date ) .' End Date: '. TTDate::getDate('DATE', $end_date ), __FILE__, __LINE__, __METHOD__, 10);
                //}
            }

            if (isset($retarr) and count($retarr) > 0) {
                Debug::text('Found UserDateTotal rows matched filter: ' . count($retarr), __FILE__, __LINE__, __METHOD__, 10);

                return $retarr;
            } else {
                //Debug::Arr($pay_code_ids, 'Pay Code IDs: ', __FILE__, __LINE__, __METHOD__, 10);
                Debug::text('No UserDateTotal rows matched filter... Start Date: ' . TTDate::getDate('DATE', $start_date) . ' End Date: ' . TTDate::getDate('DATE', $end_date), __FILE__, __LINE__, __METHOD__, 10);
            }
        } else {
            Debug::text('No UserDateTotal rows available for matching...', __FILE__, __LINE__, __METHOD__, 10);
        }

        return false;
    }

    public function getShiftData($user_date_total_arr, $epoch = null, $filter = null, $maximum_shift_time = null, $new_shift_trigger_time = null)
    {
        //EPOCH can be NULL when we just want to get all shift data without any filter
        if ($epoch == '' and ($filter != '' and $filter != 'all_with_map')) {
            return false;
        }

        if ($maximum_shift_time === null) {
            $maximum_shift_time = $this->pay_period_schedule_obj->getMaximumShiftTime();
        }

        //Debug::text('User Date ID: '. $user_date_id .' User ID: '. $user_id .' TimeStamp: '. TTDate::getDate('DATE+TIME', $epoch), __FILE__, __LINE__, __METHOD__, 10);
        if ($new_shift_trigger_time === null) {
            $new_shift_trigger_time = $this->pay_period_schedule_obj->getNewDayTriggerTime();
        }

        Debug::text('UDT Rows: ' . count($user_date_total_arr) . ' Date: ' . TTDate::getDate('DATE+TIME', $epoch) . '(' . $epoch . ') MaximumShiftTime: ' . $maximum_shift_time . ' New Shift Trigger: ' . $new_shift_trigger_time . ' Filter: ' . $filter, __FILE__, __LINE__, __METHOD__, 10);
        if (is_array($user_date_total_arr)) {
            $shift = 0;
            $i = 0;
            $nearest_shift_id = 0;
            $nearest_punch_difference = false;

            $user_date_total_arr = $this->sortUserDateTotalData($user_date_total_arr, 'sortUserDateTotalDataByTimeStampAndAndObjectTypeAndID'); //Needs to be sorted always to avoid cases where the records are out of order causing the shifts first_in/last_out to be incorrect.
            foreach ($user_date_total_arr as $udt_key => $udt_obj) {
                Debug::text('  Shift: ' . $shift . ' UDT ID: ' . $udt_obj->getID() . ' Key: ' . $udt_key . ' Object Type: ' . $udt_obj->getObjectType() . ' Start: ' . TTDate::getDate('DATE+TIME', $udt_obj->getStartTimeStamp()) . ' End: ' . TTDate::getDate('DATE+TIME', $udt_obj->getEndTimeStamp()), __FILE__, __LINE__, __METHOD__, 10);

                //Can't use PunchControl object total_time because the record may not be saved yet when editing
                //an already existing punch.
                //When editing, simply pass the existing PunchControl object to this function so we can
                //use it instead of the one in the database perhaps?
                $total_time = $udt_obj->getTotalTime();

                if ($i > 0 and isset($shift_data[$shift]['last_out'])) {
                    Debug::text('  Checking for new shift... This UDT ID: ' . $udt_obj->getID() . ' Last Out Time: ' . TTDate::getDate('DATE+TIME', $user_date_total_arr[$shift_data[$shift]['last_out']]->getEndTimeStamp()), __FILE__, __LINE__, __METHOD__, 10);

                    //Assume that if two punches are assigned to the same punch_control_id are the same shift, even if the time between
                    //them exceeds the new_shift_trigger_time. This helps fix the bug where you could add a In punch then add a Out
                    //punch BEFORE the In punch as long as it was more than the Maximum Shift Time before the In Punch.
                    //ie: Add: In Punch 10-Dec-09 @ 8:00AM, Add: Out Punch 09-Dec-09 @ 5:00PM.
                    //Basically it just helps the validation checks to determine the error.
                    //
                    //It used to be that if shifts are split at midnight, new_shift_trigger_time must be 0, so the "split" punch can occur at midnight.
                    //However we have since added a check to see if punches span midnight and trigger a new shift based on that, regardless of the new shift trigger time.
                    //As the new_shift_trigger_time of 0 also affected lunch/break automatic detection by Punch Time, since an Out punch and a In punch of any time
                    //would trigger a new shift, and it wouldn't be detected as lunch/break.
                    //
                    //What happens when the employee takes lunch/break over midnight? Lunch out at 11:30PM Lunch IN at 12:30AM
                    //	We need to split those into two lunches, or two breaks? But then that can affect those policies if they are only allowed one break.
                    //	Or do we not split the shift at all when this occurs? Currently we don't split at all.
                    if ((
                        (
                            //Make sure the two timestamps aren't at the exact same time, as switching from regular to overtime shouldn't cause a new shift to trigger.
                            ($udt_obj->getStartTimeStamp() - $user_date_total_arr[$shift_data[$shift]['last_out']]->getEndTimeStamp()) > 0
                            and ($udt_obj->getStartTimeStamp() - $user_date_total_arr[$shift_data[$shift]['last_out']]->getEndTimeStamp()) >= $new_shift_trigger_time
                            and $udt_obj->getStartType() == 10 //Make sure only normal punches can trigger new shifts.
                        )
                        or
                        (
                            $this->pay_period_schedule_obj->getShiftAssignedDay() == 40
                            //Only split shifts on NORMAL punches.
                            and $udt_obj->getStartType() == 10
                            and $user_date_total_arr[$shift_data[$shift]['last_out']]->getEndType() == 10
                            and TTDate::doesRangeSpanMidnight($user_date_total_arr[$shift_data[$shift]['last_out']]->getEndTimeStamp(), $udt_obj->getStartTimeStamp(), true) == true
                        )
                    )
                    ) {
                        Debug::text('New Shift....', __FILE__, __LINE__, __METHOD__, 10);
                        $shift++;
                    }
                } elseif ($i > 0
                    and isset($prev_punch_arr['time_stamp'])
                    and abs(($prev_punch_arr['time_stamp'] - $udt_obj->getStartTimeStamp())) > $maximum_shift_time
                ) {
                    Debug::text('	 New shift because two punch_control records exist and punch timestamp exceed maximum shift time.', __FILE__, __LINE__, __METHOD__, 10);
                    $shift++;
                }

                if (!isset($shift_data[$shift]['total_time'])) {
                    $shift_data[$shift]['total_time'] = 0;
                }

                $punch_day_epoch = TTDate::getBeginDayEpoch($udt_obj->getStartTimeStamp());
                if (!isset($shift_data[$shift]['total_time_per_day'][$punch_day_epoch])) {
                    $shift_data[$shift]['total_time_per_day'][$punch_day_epoch] = 0;
                }

                //Determine which shift is closest to the given epoch.
                if ($filter == 'nearest_shift') {
                    $punch_difference_from_epoch = abs(($epoch - $udt_obj->getStartTimeStamp()));
                    if ($nearest_punch_difference === false or $punch_difference_from_epoch <= $nearest_punch_difference) {
                        Debug::text('Nearest Shift Determined to be: ' . $shift . ' Nearest Punch Diff: ' . (int)$nearest_punch_difference . ' Punch Diff: ' . $punch_difference_from_epoch, __FILE__, __LINE__, __METHOD__, 10);

                        //If two punches have the same timestamp, use the shift that matches the passed punch control object, which is usually the one we are currently editing...
                        //This is for splitting shifts at exactly midnight.
                        if ($punch_difference_from_epoch != $nearest_punch_difference
                            or ($punch_difference_from_epoch == $nearest_punch_difference)
                        ) {
                            Debug::text('Setting nearest shift...', __FILE__, __LINE__, __METHOD__, 10);
                            $nearest_shift_id = $shift;
                            $nearest_punch_difference = $punch_difference_from_epoch;
                        }
                    }
                }

                //$shift_data[$shift]['user_date_total_ids'][] = $udt_obj->getID();
                $shift_data[$shift]['user_date_total_keys'][] = $udt_key;

                //Create a mapping of UDT total keys to shift, so we can quickly determine exact which shift a UDT record is assigned to.
                if ($filter == 'all_with_map') {
                    $shift_data['user_date_total_key_map'][$udt_key] = $shift;
                }

                if ($udt_obj->getDateStamp() != false) {
                    $shift_data[$shift]['date_stamps'][] = $udt_obj->getDateStamp();
                }

                if (!isset($shift_data[$shift]['span_midnight'])) {
                    $shift_data[$shift]['span_midnight'] = false;
                }

                if (!isset($shift_data[$shift]['first_in']) and $udt_obj->getStartType() == 10) {
                    //Debug::text('First In -- Punch ID: '. $udt_obj->getID() .' Punch Control ID: '. $udt_obj->getPunchControlID() .' TimeStamp: '. TTDate::getDate('DATE+TIME', $udt_obj->getTimeStamp() ), __FILE__, __LINE__, __METHOD__, 10);
                    $shift_data[$shift]['first_in'] = $udt_key;
                }

                //Since UDT rows have both IN and OUT timestamps, need to handle both first_in and last_out in the same record.
                if ($udt_obj->getEndTimeStamp() != '') {
                    //Debug::text('Last Out -- Punch ID: '. $udt_obj->getID() .' Punch Control ID: '. $udt_obj->getPunchControlID() .' TimeStamp: '. TTDate::getDate('DATE+TIME', $udt_obj->getTimeStamp() ), __FILE__, __LINE__, __METHOD__, 10);

                    $shift_data[$shift]['last_out'] = $udt_key;

                    //Debug::text('Total Time: '. $total_time, __FILE__, __LINE__, __METHOD__, 10);
                    $shift_data[$shift]['total_time'] += $total_time;

                    //Check to see if the previous punch was on a different day then the current punch.
                    if (TTDate::doesRangeSpanMidnight($udt_obj->getStartTimeStamp(), $udt_obj->getEndTimeStamp()) == true) {
                        Debug::text('Punch PAIR DOES span midnight', __FILE__, __LINE__, __METHOD__, 10);
                        $shift_data[$shift]['span_midnight'] = true;

                        $total_time_for_each_day_arr = TTDate::calculateTimeOnEachDayBetweenRange($udt_obj->getStartTimeStamp(), $udt_obj->getEndTimeStamp());
                        if (is_array($total_time_for_each_day_arr)) {
                            foreach ($total_time_for_each_day_arr as $begin_day_epoch => $day_total_time) {
                                if (!isset($shift_data[$shift]['total_time_per_day'][$begin_day_epoch])) {
                                    $shift_data[$shift]['total_time_per_day'][$begin_day_epoch] = 0;
                                }
                                $shift_data[$shift]['total_time_per_day'][$begin_day_epoch] += $day_total_time;
                            }
                        }
                        unset($total_time_for_each_day_arr, $begin_day_epoch, $day_total_time, $prev_day_total_time);
                    } else {
                        $shift_data[$shift]['total_time_per_day'][$punch_day_epoch] += $total_time;
                    }
                }

                $i++;
            }
            //Debug::Arr($shift_data, 'aShift Data:', __FILE__, __LINE__, __METHOD__, 10);

            if (isset($shift_data)) {
                Debug::text('Filtering if necessary...', __FILE__, __LINE__, __METHOD__, 10);

                //Loop through each shift to determine the day with the most time.
                foreach ($shift_data as $tmp_shift_key => $tmp_shift_data) {
                    if ($tmp_shift_key === 'user_date_total_key_map') {
                        continue;
                    }
                    krsort($shift_data[$tmp_shift_key]['total_time_per_day']); //Sort by day first
                    arsort($shift_data[$tmp_shift_key]['total_time_per_day']); //Sort by total time per day.
                    reset($shift_data[$tmp_shift_key]['total_time_per_day']);
                    $shift_data[$tmp_shift_key]['day_with_most_time'] = key($shift_data[$tmp_shift_key]['total_time_per_day']);

                    //$shift_data[$tmp_shift_key]['user_date_total_ids'] = array_unique( $shift_data[$tmp_shift_key]['user_date_total_ids'] );
                    $shift_data[$tmp_shift_key]['user_date_total_keys'] = array_unique($shift_data[$tmp_shift_key]['user_date_total_keys']);
                    if (isset($shift_data[$tmp_shift_key]['date_stamps'])) {
                        $shift_data[$tmp_shift_key]['date_stamps'] = array_unique($shift_data[$tmp_shift_key]['date_stamps']);
                    }
                }
                unset($tmp_shift_key, $tmp_shift_data);

                if ($filter == 'first_shift') {
                    //Only return first shift.
                    $shift_data = $shift_data[0];
                } elseif ($filter == 'last_shift') {
                    //Only return last shift.
                    $shift_data = $shift_data[$shift];
                } elseif ($filter == 'nearest_shift') {
                    $shift_data = $shift_data[$nearest_shift_id];
                    //Check to make sure the nearest shift is within the new shift trigger time of EPOCH.
                    if (isset($shift_data['first_in'])) {
                        $first_in = $shift_data['first_in'];
                    } elseif (isset($shift_data['last_out'])) {
                        $first_in = $shift_data['last_out'];
                    }

                    if (isset($shift_data['last_out'])) {
                        $last_out = $shift_data['last_out'];
                    } elseif (isset($shift_data['first_in'])) {
                        $last_out = $shift_data['first_in'];
                    }

                    //The check below must occur so if the user attempts to add an In punch that occurs AFTER the Out punch, this function
                    //still returns the shift data, so the validation checks can occur in PunchControl factory.
                    if ($user_date_total_arr[$first_in]->getStartTimeStamp() > $user_date_total_arr[$last_out]->getEndTimeStamp()) {
                        Debug::Text('WARNING: This should never occur with properly paired punches!', __FILE__, __LINE__, __METHOD__, 10);
                        //It appears that the first in punch has occurred after the OUT punch, so swap first_in and last_out, so we don't return FALSE in this case.
                        //list( $user_date_total_arr[$first_in]->getStartTimeStamp(), $user_date_total_arr[$last_out]->getEndTimeStamp() ) = array( $user_date_total_arr[$last_out]->getEndTimeStamp(), $user_date_total_arr[$first_in]->getStartTimeStamp() );
                        list($first_in, $last_out) = array($last_out, $first_in);
                    }

                    if (TTDate::isTimeOverLap($epoch, $epoch, ($user_date_total_arr[$first_in]->getStartTimeStamp() - $new_shift_trigger_time), ($user_date_total_arr[$last_out]->getEndTimeStamp() + $new_shift_trigger_time)) == false) {
                        Debug::Text('Nearest shift is outside the new shift trigger time... Epoch: ' . $epoch . ' First In: ' . $first_in . ' Last Out: ' . $last_out . ' New Shift Trigger: ' . $new_shift_trigger_time, __FILE__, __LINE__, __METHOD__, 10);

                        return false;
                    }
                    unset($first_in, $last_out);
                }

                //Debug::Arr($shift_data, 'bShift Data:', __FILE__, __LINE__, __METHOD__, 10);
                return $shift_data;
            }
        }

        return false;
    }

    public function sortUserDateTotalData($udtlf, $sort_function_name = 'sortUserDateTotalDataByDateAndObjectTypeAndStartTimeStampAndID')
    {
        if (is_array($udtlf) and $sort_function_name != '') {
            uasort($udtlf, array($this, $sort_function_name));
        }

        return $udtlf;
    }

    //Get all regulartime policies that could possibly apply, including from schedule policies.

    public function filterUserDateTotalDataByPayCodeIDs($start_date, $end_date, $pay_code_ids = null)
    {
        $udtlf = $this->user_date_total;
        if (is_array($udtlf) and count($udtlf) > 0) {
            $start_date = TTDate::getMiddleDayEpoch($start_date); //Optimization - Move outside loop.
            $end_date = TTDate::getMiddleDayEpoch($end_date); //Optimization - Move outside loop.

            foreach ($udtlf as $udt_key => $udt_obj) {
                $udt_obj_date_stamp = TTDate::getMiddleDayEpoch($udt_obj->getDateStamp()); //Optimization - Move outside loop.
                if ($udt_obj_date_stamp >= $start_date
                    and $udt_obj_date_stamp <= $end_date
                    and ($pay_code_ids == null or in_array($udt_obj->getPayCode(), (array)$pay_code_ids))
                ) {
                    $retarr[$udt_key] = $udt_obj;
                }
            }

            if (isset($retarr)) {
                Debug::text('Found user_date_total rows matched filter: ' . count($retarr), __FILE__, __LINE__, __METHOD__, 10);
                return $retarr;
            }
        }

        Debug::Arr($pay_code_ids, 'No user_date_total rows match filter...', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    //This must be done after all other policies are calculated so any average calculations can include premium time wages.

    public function UserWageSortByEffectiveDateDesc($a, $b)
    {
        if ($a->getEffectiveDate() == $b->getEffectiveDate()) {
            //Compare updated dates instead, so hopefully in cases where two wage entries on the same date exist we will pick the newest one.
            return ($a->getUpdatedDate() < $b->getUpdatedDate()) ? 1 : (-1);
        }
        return ($a->getEffectiveDate() < $b->getEffectiveDate()) ? 1 : (-1);
    }

    //Overtime policies need to be nested...
    //Since they are basically like saying: Any time after Xhrs/day goes to this OT policy. If some time is filtered out, it simply applies to the next OT policy.
    //So the first OT policy should have almost all time applied to it, then the next policy simply moves time from the prior OT policy into itself, rinse and repeat...

    public function sortUserDateTotalDataByObjectTypeDescAndID($a, $b)
    {
        //Sort order obtained from: getUserDateTotalData(), if changes are needed, change there too.
        //array( 'a.date_stamp' => 'asc', 'a.object_type_id' => 'asc', 'a.start_time_stamp' => 'asc', 'a.id' => 'asc' )
        if ($a->getObjectType() == $b->getObjectType()) {
            return ($a->getID() < $b->getID()) ? (-1) : 1;
        } else {
            return ($a->getObjectType() > $b->getObjectType()) ? (-1) : 1;
        }
    }

    public function sortUserDateTotalDataByDateAndObjectTypeAndStartTimeStampAndID($a, $b)
    {
        //Sort order obtained from: getUserDateTotalData(), if changes are needed, change there too.
        //array( 'a.date_stamp' => 'asc', 'a.object_type_id' => 'asc', 'a.start_time_stamp' => 'asc', 'a.id' => 'asc' )
        if ($a->getDateStamp() == $b->getDateStamp()) {

            //Treat 25 (Absence) like its regular time, so its sorted by timestamp with the regular time policies.
            //This makes it so absences without timestamps come before regular time with timestamps, and greatly affects how overtime policies are calculated.
            $a_object_type_id = ($a->getObjectType() == 25) ? 20 : $a->getObjectType();
            $b_object_type_id = ($b->getObjectType() == 25) ? 20 : $b->getObjectType();

            if ($a_object_type_id == $b_object_type_id) {
                if ($a->getStartTimeStamp() == $b->getStartTimeStamp()) {
                    return ($a->getID() < $b->getID()) ? (-1) : 1;
                } else {
                    return ($a->getStartTimeStamp() < $b->getStartTimeStamp()) ? (-1) : 1;
                }
            } else {
                return ($a_object_type_id < $b_object_type_id) ? (-1) : 1;
            }
        } else {
            return ($a->getDateStamp() < $b->getDateStamp()) ? (-1) : 1;
        }
    }

    public function sortUserDateTotalDataByTimeStampAndAndObjectTypeAndID($a, $b)
    {
        //This is needed for getShiftData() so the first_in/last_out are always correct. As there seems to be cases wher auto-deduct meal policies may come last.
        if ($a->getStartTimeStamp() == $b->getStartTimeStamp()) {
            //Treat 25 (Absence) like its regular time, so its sorted by timestamp with the regular time policies.
            //This makes it so absences without timestamps come before regular time with timestamps, and greatly affects how overtime policies are calculated.
            $a_object_type_id = ($a->getObjectType() == 25) ? 20 : $a->getObjectType();
            $b_object_type_id = ($b->getObjectType() == 25) ? 20 : $b->getObjectType();

            if ($a_object_type_id == $b_object_type_id) {
                return ($a->getID() < $b->getID()) ? (-1) : 1;
            } else {
                return ($a_object_type_id < $b_object_type_id) ? (-1) : 1;
            }
        } else {
            return ($a->getStartTimeStamp() < $b->getStartTimeStamp()) ? (-1) : 1;
        }
    }

    public function getFirstPendingDate()
    {
        reset($this->dates['pending_calculation']);
        $retval = key($this->dates['pending_calculation']);
        Debug::Text('First Pending Date: ' . TTDate::getDate('DATE+TIME', $retval), __FILE__, __LINE__, __METHOD__, 10);
        if ($retval != '') {
            return $retval;
        }

        return false;
    }

    public function calculate($date_stamp = false)
    {
        //Debug::Arr( Debug::backTrace(), 'Calculate: ', __FILE__, __LINE__, __METHOD__, 10);

        if ($date_stamp == '') {
            $date_stamp = $this->getNextPendingDate();
        }

        if (is_array($date_stamp) or $date_stamp == '') {
            Debug::Arr($date_stamp, 'Invalid DateStamp: ', __FILE__, __LINE__, __METHOD__, 10);
            return false;
        }

        if (!is_object($this->getUserObject())) {
            Debug::Arr($this->getUserObject(), 'Invalid UserObject: ', __FILE__, __LINE__, __METHOD__, 10);
            return false;
        }

        //This is set in setUserObject(), and shouldn't need to be called twice. See setTimeZone() for more information.
        //$this->setTimeZone(); //Set timezone to users timezone so dates/times are all calculated in the users timezone.

        //Start transaction to keep data consistent during the entire calculation process.
        //This may cause deadlocks if the date ranges are too long though.
        $this->getUserObject()->setTransactionMode('REPEATABLE READ');
        $this->getUserObject()->StartTransaction();
        $this->getUserObject()->getTransactionMode();

        $i = 0;
        do {
            //Use a while loop to avoid nested function call limitations.
            Debug::text('I: ' . $i . ' Calculating DateStamp: ' . TTDate::getDate('DATE+TIME', $date_stamp), __FILE__, __LINE__, __METHOD__, 10);
            $date_stamp = $this->_calculate($date_stamp);
            $i++;
        } while ($date_stamp !== false and $i <= 366); //Don't exceed one year.

        if ($i >= 366) {
            Debug::text(' ERROR: Attempted to recalculate more than one year and reached limit!', __FILE__, __LINE__, __METHOD__, 10);
        }

        //Make sure reverTimeZone() and Commit transaction are in the Save() function below, so we don't revert the timezone before we save the records.

        return true;
    }

    //Get list of all weekly overtime policies so they can be included when calculating weekly time.

    public function getNextPendingDate()
    {
        //Debug::Arr($this->dates['pending_calculation'], 'Dates pending calculation still: ', __FILE__, __LINE__, __METHOD__, 10);

        reset($this->dates['pending_calculation']);
        $retval = key($this->dates['pending_calculation']);
        if ($retval != '') {
            Debug::Text('Next Pending Date: ' . TTDate::getDate('DATE+TIME', $retval), __FILE__, __LINE__, __METHOD__, 10);
            unset($this->dates['pending_calculation'][$retval]);
            return $retval;
        }

        return false;
    }

    //Get all overtime policies that could possibly apply, including from schedule policies.

    private function _calculate($date_stamp)
    {
        $this->setPayPeriodFromDate($date_stamp);
        if (is_object($this->pay_period_schedule_obj)
            and ($this->pay_period_obj == null
                or (is_object($this->pay_period_obj) and $this->pay_period_obj->getStatus() != 20))
        ) { //Check if pay period is closed.

            //Only deleteSystemTotalTime() if we can properly calculate it and add it back, which means other policies need to be calculated too.
            if ($this->getFlag('meal') == true or $this->getFlag('undertime_absence') == true or $this->getFlag('break') == true or $this->getFlag('regular') == true or $this->getFlag('overtime') == true or $this->getFlag('premium') == true or $this->getFlag('holiday') == true or $this->getFlag('schedule_absence') == true) {
                $this->deleteSystemTotalTime($date_stamp);
            }

            //Add date to the list of calculated dates. Do this before other policies (ie: OT) can add the same date back to the list.
            $this->getRequiredData($date_stamp);

            //This removes $date_stamp from the pending calculation list, which needs to be done *after* data is obtained, otherwise things like getLastPendingData() will be off by a day.
            //This specifically happens when addPendingCalculationDate() adds several days, then Calculate( $last_day ) is called with the last date that was added to the pending calculation list.
            $this->addCalculatedDate($date_stamp);

            //Add all days remaining in the week to be recalculated.
            if ($this->getFlag('future_dates') == true or $this->getFlag('exception_future') == true) {
                $this->addPendingCalculationDate(TTDate::getBeginDayEpoch((TTDate::getMiddleDayEpoch($date_stamp) + 86400)), TTDate::getEndWeekEpoch($date_stamp, $this->start_week_day_id));
            }

            if ($this->getFlag('meal') == true or $this->getFlag('undertime_absence') == true or $this->getFlag('break') == true or $this->getFlag('regular') == true or $this->getFlag('overtime') == true or $this->getFlag('premium') == true or $this->getFlag('accrual') == true or $this->getFlag('holiday') == true or $this->getFlag('schedule_absence') == true) {

                //Meal/Break calculation must go before any other policy, even before we get worked time, as this affects worked time.
                //We also need to calculate just the daily worked time for them.
                //This also needs to go before we get $maximum_daily_total_time, otherwise it will be off by the auto-deducted/auto-added time.
                //**Keep in mind regular time policies can include/exclude meal/break time depending on they want the time to be calculated.
                if ($this->getFlag('meal') == true) {
                    $this->calculateMealTimePolicy($date_stamp);
                }

                if ($this->getFlag('break') == true) {
                    $this->calculateBreakTimePolicy($date_stamp);
                }

                //Calculate holiday time before absences/regular time and maximum_daily_total as it creates absence time.
                if ($this->getFlag('absence') == true or $this->getFlag('undertime_absence') == true or $this->getFlag('schedule_absence') == true or $this->getFlag('holiday') == true) {
                    $this->calculateHolidayPolicy($date_stamp);
                }

                //Calculate absence schedules after holidays, so they can be exclusive to one another.
                if ($this->getFlag('absence') == true or $this->getFlag('undertime_absence') == true or $this->getFlag('schedule_absence') == true or $this->getFlag('holiday') == true) {
                    $this->calculateScheduleAbsence($date_stamp);
                }

                //This must be before maximum_daily_total_time is calculated, in cases where they don't work at all on a day, undertime will still work.
                if ($this->getFlag('absence') == true or $this->getFlag('undertime_absence') == true or $this->getFlag('schedule_absence') == true or $this->getFlag('holiday') == true) {
                    $this->calculateUnderTimeAbsencePolicy($date_stamp);
                }

                //Get worked time+meal/break+absence as the total amount of time that can be split between Regular and Overtime as the maximum daily total time.
                //This has to include Absence Taken (50) rather than Absence (25) as it hasn't been calculated yet.
                //UndertimeAbsence creates object_type_id=25 records, so we do need to include those here.
                //$maximum_daily_total_time = $this->getSumUserDateTotalData( $this->filterUserDateTotalDataByObjectTypeIDs( $date_stamp, $date_stamp, array( 10, 25, 50, 100, 110 ) ) );

                //Since we support override records now, and prior to this we delete all system time, we should include regular/overtime in this total.
                //When override records exist, its virtually impossible to calculate a proper total time,
                //  since we don't know if they are override punch time, or meal/break time, or what.
                //  So if the user punched in for 8hrs, then an override record adds another 1hr, the total should be 9hrs.
                //  However if they instead override the 8hr regular time record and make it 9hrs, it should also be 9hrs. But they still have 8hrs of punch time too and 9hrs of override regular time.
                //  We also don't know if they are taking auto-add/deduction meal/break policies into account for the overrides.
                $maximum_daily_total_time = $this->getSumUserDateTotalData($this->filterUserDateTotalDataByObjectTypeIDs($date_stamp, $date_stamp, array(10, 20, 25, 30, 50, 100, 110)));
                //$maximum_daily_total_time = $this->getSumUserDateTotalData( $this->compactUserDateTotalDataBasedOnOverride( $this->filterUserDateTotalDataByObjectTypeIDs( $date_stamp, $date_stamp, array( 10, 20, 25, 30, 50, 100, 110 ) ) ) );
                Debug::text('Maximum Daily Total Time: ' . $maximum_daily_total_time, __FILE__, __LINE__, __METHOD__, 10);

                if ($maximum_daily_total_time > 0) {
                    //Calculate absence time before regular time, as Regular Time is exclusive to Absence time.
                    //Undertime absence above deletes absence records, so we always need to recalculate absences if undertime absences are calculated.
                    if ($this->getFlag('absence') == true or $this->getFlag('undertime_absence') == true or $this->getFlag('schedule_absence') == true or $this->getFlag('holiday') == true) {
                        $this->calculateAbsenceTimePolicy($date_stamp);
                    }

                    if ($this->getFlag('regular') == true) {
                        $this->calculateRegularTimePolicy($date_stamp, $maximum_daily_total_time);
                        $this->calculateRegularTimeExclusivity();
                    }

                    if ($this->getFlag('overtime') == true) {
                        //  Once the first OT policy starts everything is OT after that in the remaining period (daily/weekly) until it resets again.
                        $this->calculateOverTimePolicy($date_stamp, $this->processTriggerTimeArray($date_stamp, $this->getOverTimeTriggerArray($date_stamp)), $maximum_daily_total_time);
                    }

                    if ($this->getFlag('premium') == true) {
                        //Needs to go before overtime, so average wages can be obtained that include premiums.
                        //However then premiums can't include overtime?
                        $this->calculatePremiumTimePolicy($date_stamp, $maximum_daily_total_time);
                    }

                    if ($this->getFlag('meal') == true or $this->getFlag('undertime_absence') == true or $this->getFlag('break') == true or $this->getFlag('regular') == true or $this->getFlag('overtime') == true or $this->getFlag('premium') == true or $this->getFlag('holiday') == true or $this->getFlag('schedule_absence') == true) {
                        Debug::text('bMaximum Daily Total Time: ' . $maximum_daily_total_time, __FILE__, __LINE__, __METHOD__, 10);
                        $this->calculateSystemTotalTime($date_stamp, $maximum_daily_total_time);
                    }

                    if ($this->getFlag('overtime') == true) {
                        //Make sure we do this after all policies have been calculated.
                        $this->calculateOverTimeHourlyRates($this->user_date_total);
                    }
                } else {
                    Debug::text('Maximum Daily Total Time is 0, skipping Regular/OT/Premium policies...', __FILE__, __LINE__, __METHOD__, 10);

                    //Need to have system total time row even if it is 0.
                    if ($this->getFlag('meal') == true or $this->getFlag('undertime_absence') == true or $this->getFlag('break') == true or $this->getFlag('regular') == true or $this->getFlag('overtime') == true or $this->getFlag('premium') == true or $this->getFlag('holiday') == true or $this->getFlag('schedule_absence') == true) {
                        $this->calculateSystemTotalTime($date_stamp, $maximum_daily_total_time);
                    }
                }
            } else {
                Debug::text('Not calculating any time related policies due to flags...', __FILE__, __LINE__, __METHOD__, 10);
            }

            if ($this->getFlag('exception') == true) {
                $this->calculateExceptionPolicy($date_stamp);
            }
        } else {
            if (!(is_object($this->pay_period_obj) and $this->pay_period_obj->getStatus() != 20)) {
                Debug::text('Pay Period is Closed! Status: ' . $this->pay_period_obj->getStatus(), __FILE__, __LINE__, __METHOD__, 10);
            } else {
                Debug::text('No Pay Period Object!', __FILE__, __LINE__, __METHOD__, 10);
            }
        }

        //Calculate pending dates even if pay period doesn't exist or maximum daily time is 0 on some days.
        $next_pending_date_stamp = $this->getNextPendingDate();
        if ($next_pending_date_stamp != '') {
            Debug::Text('Next Pending Date: ' . TTDate::getDate('DATE+TIME', $next_pending_date_stamp), __FILE__, __LINE__, __METHOD__, 10);
        } else {
            Debug::Text('No more Pending Dates...', __FILE__, __LINE__, __METHOD__, 10);
        }
        return $next_pending_date_stamp;
    }

    public function deleteSystemTotalTime($date_stamp)
    {
        //Delete everything that is not overrided.
        $udtlf = TTnew('UserDateTotalListFactory');

        //Optimize for a direct delete query.
        //Due to a MySQL gotcha: http://dev.mysql.com/doc/refman/5.0/en/subquery-errors.html
        //We need to wrap the subquery in a subquery of itself to hide it from MySQL
        //So it doesn't complain about updating a table and selecting from it at the same time.
        //MySQL v5.0.22 DOES NOT like this query, it takes 10+ seconds to run and seems to cause a deadlock.
        //Switch back to a select then a bulkDelete instead. Still fast enough I think.

        //Only delete rows for policies we are actually recalculating.
        //This prevents calcQuickExceptions maintenance job from deleting/recalcuting UDT rows when it doesn't need to.
        //However if we add any more Flags we need to set them to FALSE in calcQuickExceptions.
        $object_type_ids = array();
        if ($this->getFlag('meal') == true) {
            $object_type_ids = array_merge($object_type_ids, array(100, 101));
        }

        if ($this->getFlag('break') == true) {
            $object_type_ids = array_merge($object_type_ids, array(110, 111));
        }

        if ($this->getFlag('regular') == true) {
            $object_type_ids = array_merge($object_type_ids, array(20));
        }

        if ($this->getFlag('absence') == true
            or $this->getFlag('undertime_absence') == true
            or $this->getFlag('schedule_absence') == true
            or $this->getFlag('holiday') == true
        ) {
            $object_type_ids = array_merge($object_type_ids, array(25, 50));
        }

        if ($this->getFlag('overtime') == true) {
            $object_type_ids = array_merge($object_type_ids, array(30));
        }

        if ($this->getFlag('premium') == true) {
            $object_type_ids = array_merge($object_type_ids, array(40));
        }

        if (count($object_type_ids) > 0) {
            $object_type_ids = array_merge($object_type_ids, array(5)); //System
        }

        if (is_array($object_type_ids) and count($object_type_ids) > 0) {
            //Debug::Arr( $object_type_ids, 'Deleting UDT rows based on total object_type_ids: '. count($object_type_ids), __FILE__, __LINE__, __METHOD__, 10);
            //$udtlf->getByUserIdAndDateStampAndObjectTypeAndOverrideAndMisMatchPunchControlDateStamp( $this->getUserObject()->getId(), $date_stamp, $object_type_ids, FALSE ); //System totals
            //$udtlf->bulkDelete( $udtlf->getIDSByListFactory( $udtlf ) );
            $udtlf->deleteByUserIdAndDateStampAndObjectTypeAndOverrideAndMisMatchPunchControlDateStamp($this->getUserObject()->getId(), $date_stamp, $object_type_ids, false); //System totals
            unset($this->new_system_user_date_total_id[TTDate::getMiddleDayEpoch($date_stamp)]); //Reset this when deleting records, so it can be set again when we insert them later on.
            $this->delete_system_total_time_already_run = true; //This allows insertUserDateTotal to run later on without inserting duplicated records.
        } else {
            Debug::text('NOT Deleting UDT rows based on total object_type_ids: ' . count($object_type_ids), __FILE__, __LINE__, __METHOD__, 10);
        }

        //Regardless if there are any accrual policies to calculate, we need to delete orphan records
        //in cases where we are deleting a manually added absence entry (override=1)
        //Do this immediately after we delete the UDT rows, as thats when orphans are created.
        AccrualFactory::deleteOrphans($this->getUserObject()->getId(), $date_stamp);

        //Also delete records in memory for this date so they can be recalculated.
        $udtlf = $this->filterUserDateTotalDataByObjectTypeIDs($date_stamp, $date_stamp, $object_type_ids);
        if (is_array($udtlf) and count($udtlf) > 0) {
            foreach ($udtlf as $key => $udt_obj) {
                if ($udt_obj->getOverride() == false) { //Ensure we don't delete overridden rows.
                    Debug::text('Removing UDT row from memory: ' . $key, __FILE__, __LINE__, __METHOD__, 10);
                    unset($this->user_date_total[$key]);
                }
            }
        }

        return true;
    }

    public function filterUserDateTotalDataByObjectTypeIDs($start_date, $end_date, $object_type_ids = null)
    {
        $udtlf = $this->user_date_total;
        if (is_array($udtlf) and count($udtlf) > 0) {
            $start_date = TTDate::getMiddleDayEpoch($start_date); //Optimization - Move outside loop.
            $end_date = TTDate::getMiddleDayEpoch($end_date); //Optimization - Move outside loop.

            foreach ($udtlf as $udt_key => $udt_obj) {
                $udt_obj_date_stamp = TTDate::getMiddleDayEpoch($udt_obj->getDateStamp()); //Optimization - Move outside loop.
                if ($udt_obj_date_stamp >= $start_date
                    and $udt_obj_date_stamp <= $end_date
                    and ($object_type_ids == null or in_array($udt_obj->getObjectType(), $object_type_ids))
                ) {
                    $retarr[$udt_key] = $udt_obj;
                }
                //else {
                //	Debug::text('Skipping, due to filter date,object_type_id... UDT Date Stamp: '. TTDate::getDate('DATE', $udt_obj_date_stamp ) .' Pay Code ID: '. $udt_obj->getPayCode() .' Object Type: '. $udt_obj->getObjectType() .' Total Time: '. $udt_obj->getTotalTime() .' Filter: Start Date: '. TTDate::getDate('DATE', TTDate::getMiddleDayEpoch( $start_date ) ) .' End Date: '. TTDate::getDate('DATE', TTDate::getMiddleDayEpoch( $end_date ) ), __FILE__, __LINE__, __METHOD__, 10);
                //}
            }

            if (isset($retarr)) {
                Debug::text('Found user_date_total rows matched filter: ' . count($retarr), __FILE__, __LINE__, __METHOD__, 10);
                return $retarr;
            }
        }

        Debug::text('No user_date_total rows match filter...', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function addCalculatedDate($date_stamp)
    {
        $date_stamp = TTDate::getBeginDayEpoch($date_stamp);
        //Remove date from pending calculation first, then add it to the calculated date.
        if (isset($this->dates['pending_calculation'][$date_stamp])) {
            unset($this->dates['pending_calculation'][$date_stamp]);
        }

        $this->dates['calculated'][$date_stamp] = true;

        return true;
    }

    public function calculateMealTimePolicy($date_stamp)
    {
        //Calculate meal time taken even if no meal policies exist.
        $lunch_total_time = 0;

        $last_punch_in_timestamp = false;

        $plf = $this->filterUserDateTotalDataByPunchTypeIDs($date_stamp, $date_stamp, array(20)); //Lunch rows only.
        if (is_array($plf) and count($plf) > 0) {
            $pair = 0;
            $x = 0;
            $out_for_lunch = false;
            foreach ($plf as $p_obj) {
                if ($out_for_lunch == false and $p_obj->getEndType() == 20) {
                    $lunch_out_timestamp = $p_obj->getEndTimeStamp();
                    $out_for_lunch = true;
                } elseif ($out_for_lunch == true and $p_obj->getStartType() == 20) {
                    $lunch_punch_arr[$pair][20] = $lunch_out_timestamp;
                    $lunch_punch_arr[$pair][10] = $p_obj->getStartTimeStamp();
                    $out_for_lunch = false;
                    $pair++;
                    unset($lunch_out_timestamp);

                    if ($p_obj->getStartType() == 20 and $p_obj->getEndType() == 20) {
                        $lunch_out_timestamp = $p_obj->getEndTimeStamp();
                        $out_for_lunch = true;
                    }
                } else {
                    $out_for_lunch = false;
                }
                $x++;
            }

            if (isset($lunch_punch_arr)) {
                foreach ($lunch_punch_arr as $punch_control_id => $time_stamp_arr) {
                    if (isset($time_stamp_arr[10]) and isset($time_stamp_arr[20])) {
                        //Insert UDT row for each lunch taken, with the start/end times.
                        if (!isset($this->user_date_total[$this->user_date_total_insert_id])) {
                            $udtf = TTnew('UserDateTotalFactory');
                            $udtf->setUser($this->getUserObject()->getId());
                            $udtf->setDateStamp($date_stamp);
                            $udtf->setObjectType(101); //Lunch (Taken)
                            $udtf->setPayCode(0);

                            $udtf->setBranch((int)$this->getUserObject()->getDefaultBranch());
                            $udtf->setDepartment((int)$this->getUserObject()->getDefaultDepartment());
                            $udtf->setJob((int)$this->getUserObject()->getDefaultJob());
                            $udtf->setJobItem((int)$this->getUserObject()->getDefaultJobItem());

                            $udtf->setStartType(20); //Lunch
                            $udtf->setStartTimeStamp($time_stamp_arr[20]);
                            $udtf->setEndType(20); //Lunch
                            $udtf->setEndTimeStamp($time_stamp_arr[10]);

                            $udtf->setQuantity(0); //Use this to count total lunches taken?
                            $udtf->setBadQuantity(0);
                            $udtf->setTotalTime(($time_stamp_arr[10] - $time_stamp_arr[20]));

                            $udtf->setEnableCalcSystemTotalTime(false);
                            $udtf->preSave(); //Call this so TotalTimeAmount is calculated immediately, as we don't save these records until later.

                            //Don't save the record, just add it to the existing array, so it can be included in other calculations.
                            //We will save these records at the end.
                            $this->user_date_total[$this->user_date_total_insert_id] = $udtf;

                            Debug::text('   Adding UDT row for Meal (Taken) Total Time: ' . $lunch_total_time, __FILE__, __LINE__, __METHOD__, 10);
                            $this->user_date_total_insert_id--;

                            $lunch_total_time = ($lunch_total_time + $udtf->getTotalTime());
                        } else {
                            Debug::text('ERROR: Duplicate starting ID for some reason! ' . $this->user_date_total_insert_id, __FILE__, __LINE__, __METHOD__, 10);
                        }
                    } else {
                        Debug::text(' Lunch Punches not paired... Skipping!', __FILE__, __LINE__, __METHOD__, 10);
                    }
                }

                //Get the last punch in timestamp so we start auto-add/auto-deduct timestamps from this.
                $last_punch_in_timestamp = $time_stamp_arr[10];
            } else {
                Debug::text(' No Lunch Punches found, or none are paired.', __FILE__, __LINE__, __METHOD__, 10);
            }
        }
        unset($plf, $udtf, $pair, $x, $out_for_lunch, $lunch_out_timestamp, $lunch_punch_arr, $lunch_pair_total_time, $time_stamp_arr);


        $udtlf = $this->filterUserDateTotalDataByObjectTypeIDs($date_stamp, $date_stamp, array(10));
        if (is_array($udtlf) and count($udtlf) > 0) {
            $day_total_time = 0;
            foreach ($udtlf as $udt_obj) {
                $udt_arr[$udt_obj->getId()] = $udt_obj->getTotalTime();

                $day_total_time += $udt_obj->getTotalTime();
            }
            Debug::text('Day Total Time: ' . $day_total_time, __FILE__, __LINE__, __METHOD__, 10);

            //Loop over all regular time policies calculating the pay codes, up until $maximum_time is reached.
            $meal_time_policies = $this->filterMealTimePolicy($date_stamp, $day_total_time);
            if ($day_total_time > 0 and is_array($meal_time_policies) and count($meal_time_policies) > 0) {
                $meal_policy_time = 0;

                foreach ($meal_time_policies as $mp_obj) {
                    Debug::text('Meal Policy: ' . $mp_obj->getName() . '(' . $mp_obj->getId() . ') Type ID: ' . $mp_obj->getType() . ' Amount: ' . $mp_obj->getAmount() . ' Trigger Time: ' . $mp_obj->getTriggerTime() . ' Day Total Time: ' . $day_total_time, __FILE__, __LINE__, __METHOD__, 10);

                    if ($last_punch_in_timestamp == false) {
                        if (!isset($shift_udt_objs)) {
                            $shift_udt_objs = $this->getShiftStartAndEndUserDateTotal($date_stamp, $date_stamp);
                        }
                        if (is_array($shift_udt_objs) and isset($shift_udt_objs['start'])) {
                            $last_punch_in_timestamp = ($shift_udt_objs['start']->getStartTimeStamp() + $mp_obj->getTriggerTime());
                        }
                    }

                    Debug::text(' Lunch Total Time: ' . $lunch_total_time, __FILE__, __LINE__, __METHOD__, 10);
                    switch ($mp_obj->getType()) {
                        case 10: //Auto-Deduct
                            Debug::text(' Lunch AutoDeduct.', __FILE__, __LINE__, __METHOD__, 10);
                            if ($mp_obj->getIncludeLunchPunchTime() == true) {
                                $meal_policy_time = (bcsub($mp_obj->getAmount(), $lunch_total_time) * -1);
                                //If they take more then their alloted lunch, zero it out so time isn't added.
                                if ($meal_policy_time > 0) {
                                    $meal_policy_time = 0;
                                }
                            } else {
                                $meal_policy_time = ($mp_obj->getAmount() * -1);
                            }
                            break;
                        case 15: //Auto-Add
                            Debug::text(' Lunch AutoAdd.', __FILE__, __LINE__, __METHOD__, 10);
                            if ($mp_obj->getIncludeLunchPunchTime() == true) {
                                if ($lunch_total_time > $mp_obj->getAmount()) {
                                    $meal_policy_time = $mp_obj->getAmount();
                                } else {
                                    $meal_policy_time = $lunch_total_time;
                                }
                            } else {
                                $meal_policy_time = $mp_obj->getAmount();
                            }
                            break;
                    }

                    Debug::text(' Meal Policy Total Time: ' . $meal_policy_time, __FILE__, __LINE__, __METHOD__, 10);
                    if ($meal_policy_time != 0) {
                        if (is_array($udt_arr) and $day_total_time > 0) {
                            $remainder = 0;
                            foreach ($udt_arr as $udt_id => $total_time) {
                                //Make sure we use bcmath() functions here to avoid floating point imprecision issues.
                                $udt_raw_meal_policy_time = bcmul(bcdiv($total_time, $day_total_time), $meal_policy_time);
                                if ($meal_policy_time > 0) {
                                    $rounded_udt_raw_meal_policy_time = floor($udt_raw_meal_policy_time);
                                    $remainder = bcadd($remainder, bcsub($udt_raw_meal_policy_time, $rounded_udt_raw_meal_policy_time));
                                } else {
                                    $rounded_udt_raw_meal_policy_time = ceil($udt_raw_meal_policy_time);
                                    $remainder = bcadd($remainder, bcsub($udt_raw_meal_policy_time, $rounded_udt_raw_meal_policy_time));
                                }

                                $worked_time_meal_policy_adjustments[$udt_id] = (int)$rounded_udt_raw_meal_policy_time;
                                Debug::text('UserDateTotal Row ID: ' . $udt_id . ' UDT Total Time: ' . $total_time . ' Raw Meal Policy Time: ' . $udt_raw_meal_policy_time . '(' . $rounded_udt_raw_meal_policy_time . ') Remainder: ' . $remainder, __FILE__, __LINE__, __METHOD__, 10);
                            }

                            //Add remainder rounded to the nearest second to the last row.
                            if ($meal_policy_time > 0) {
                                $remainder = ceil($remainder);
                            } else {
                                $remainder = floor($remainder);
                            }
                            $worked_time_meal_policy_adjustments[$udt_id] = (int)($worked_time_meal_policy_adjustments[$udt_id] + $remainder);

                            Debug::Arr($worked_time_meal_policy_adjustments, 'UserDateTotal Adjustments: Final Remainder: ' . $remainder, __FILE__, __LINE__, __METHOD__, 10);
                        }

                        //Create a UDT row for each meal policy adjustment element, so other policies can include/exclude the meal/break time on its own.
                        foreach ($worked_time_meal_policy_adjustments as $udt_id => $worked_time_meal_policy_adjustment) {
                            if (isset($this->user_date_total[$udt_id])) {
                                if (!isset($this->user_date_total[$this->user_date_total_insert_id])) {
                                    $udtf = TTnew('UserDateTotalFactory');
                                    $udtf->setUser($this->getUserObject()->getId());
                                    $udtf->setDateStamp($date_stamp);
                                    $udtf->setObjectType(100); //Lunch
                                    $udtf->setSourceObject((int)$mp_obj->getId());
                                    $udtf->setPayCode((int)$mp_obj->getPayCode());

                                    $udtf->setBranch((int)$this->user_date_total[$udt_id]->getBranch());
                                    $udtf->setDepartment((int)$this->user_date_total[$udt_id]->getDepartment());
                                    $udtf->setJob((int)$this->user_date_total[$udt_id]->getJob());
                                    $udtf->setJobItem((int)$this->user_date_total[$udt_id]->getJobItem());

                                    $udtf->setStartType(20); //Lunch
                                    $udtf->setEndType(20); //Lunch
                                    if ($mp_obj->getType() == 15) { //Auto_Include
                                        if ($last_punch_in_timestamp != '') { //If the first punch is a Lunch IN and only a Normal OUT, $last_punch_in_timestamp will be NULL.
                                            $udtf->setStartTimeStamp($last_punch_in_timestamp - abs($worked_time_meal_policy_adjustment));
                                            $udtf->setEndTimeStamp($last_punch_in_timestamp);
                                            $last_punch_in_timestamp = $udtf->getStartTimeStamp();
                                        }
                                    } else { //Auto-Deduct
                                        if ($last_punch_in_timestamp != '') { //If the first punch is a Lunch IN and only a Normal OUT, $last_punch_in_timestamp will be NULL.
                                            $udtf->setStartTimeStamp($last_punch_in_timestamp);
                                            $udtf->setEndTimeStamp($last_punch_in_timestamp + abs($worked_time_meal_policy_adjustment));
                                            $last_punch_in_timestamp = $udtf->getEndTimeStamp();
                                        }
                                    }

                                    $udtf->setQuantity(0);
                                    $udtf->setBadQuantity(0);
                                    $udtf->setTotalTime($worked_time_meal_policy_adjustment);

                                    //Base hourly rate on the regular wage
                                    $udtf->setBaseHourlyRate($this->getBaseHourlyRate($mp_obj->getPayFormulaPolicy(), $udtf->getPayCode(), $date_stamp));
                                    $udtf->setHourlyRate($this->getHourlyRate($mp_obj->getPayFormulaPolicy(), $udtf->getPayCode(), $date_stamp, $udtf->getBaseHourlyRate()));
                                    $udtf->setHourlyRateWithBurden($this->getHourlyRateWithBurden($mp_obj->getPayFormulaPolicy(), $udtf->getPayCode(), $date_stamp, $udtf->getHourlyRate()));

                                    $udtf->setEnableCalcSystemTotalTime(false);
                                    $udtf->preSave(); //Call this so TotalTimeAmount is calculated immediately, as we don't save these records until later.

                                    if ($this->isOverriddenUserDateTotalObject($udtf) == false) {
                                        //Don't save the record, just add it to the existing array, so it can be included in other calculations.
                                        //We will save these records at the end.
                                        $this->user_date_total[$this->user_date_total_insert_id] = $udtf;

                                        Debug::text(' Adding UDT row for Meal Policy Total Time: ' . $worked_time_meal_policy_adjustment, __FILE__, __LINE__, __METHOD__, 10);
                                        $this->user_date_total_insert_id--;
                                    }
                                } else {
                                    Debug::text('ERROR: Duplicate starting ID for some reason! ' . $this->user_date_total_insert_id, __FILE__, __LINE__, __METHOD__, 10);
                                }
                            } else {
                                Debug::text(' ERROR: UDT ID does not exist: ' . $udt_id, __FILE__, __LINE__, __METHOD__, 10);
                            }
                        }
                    }
                }

                Debug::text('Total Meal Policy Time: ' . $meal_policy_time, __FILE__, __LINE__, __METHOD__, 10);

                $this->user_date_total = $this->sortUserDateTotalData($this->user_date_total); //Sort UDT records once done modifying them. This should help avoid having to sort them everytime we get/filter them.

                return true;
            }
        } else {
            Debug::text('No UserDateTotal rows...', __FILE__, __LINE__, __METHOD__, 10);
        }

        Debug::text('No meal policies to calculate...', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function filterUserDateTotalDataByPunchTypeIDs($start_date, $end_date, $punch_type_ids = null)
    {
        $udtlf = $this->user_date_total;
        if (is_array($udtlf) and count($udtlf) > 0) {
            $start_date = TTDate::getMiddleDayEpoch($start_date); //Optimization - Move outside loop.
            $end_date = TTDate::getMiddleDayEpoch($end_date); //Optimization - Move outside loop.

            foreach ($udtlf as $udt_key => $udt_obj) {
                $udt_obj_date_stamp = TTDate::getMiddleDayEpoch($udt_obj->getDateStamp()); //Optimization - Move outside loop.
                //Debug::text('ID: '. $udt_obj->getID() .' Punch Control ID: '. $udt_obj->getPunchControlID() .' StartType: '. $udt_obj->getStartType() .' End Type: '. $udt_obj->getEndType(), __FILE__, __LINE__, __METHOD__, 10);
                if ($udt_obj_date_stamp >= $start_date
                    and $udt_obj_date_stamp <= $end_date
                    and (
                        ($punch_type_ids == null or in_array($udt_obj->getStartType(), $punch_type_ids))
                        or
                        ($punch_type_ids == null or in_array($udt_obj->getEndType(), $punch_type_ids))
                    )
                ) {
                    $retarr[$udt_key] = $udt_obj;
                }
            }

            if (isset($retarr)) {
                Debug::text('Found user_date_total rows matched filter: ' . count($retarr), __FILE__, __LINE__, __METHOD__, 10);
                return $retarr;
            }
        }

        Debug::text('No user_date_total rows match filter...', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function filterMealTimePolicy($date_stamp, $daily_total_time = null, $type_id = null, $always_return_at_least_one = false)
    {
        if (($daily_total_time > 0 or $always_return_at_least_one == true)
            and (
                (is_array($this->meal_time_policy) and count($this->meal_time_policy) > 0)
                or
                (is_array($this->schedule_policy_meal_time_policy) and count($this->schedule_policy_meal_time_policy) > 0)
            )
        ) {
            $schedule_policy_meal_time_policy_ids = array();
            $schedule_policy_arr = $this->filterSchedulePolicyByDate($date_stamp);
            if (is_array($schedule_policy_arr)) {
                foreach ($schedule_policy_arr as $sp_obj) {
                    if (is_array($sp_obj->getMealPolicy()) and count($sp_obj->getMealPolicy()) > 0) {
                        $schedule_policy_meal_time_policy_ids = array_merge($schedule_policy_meal_time_policy_ids, (array)$sp_obj->getMealPolicy());
                    }
                }
                Debug::Arr($schedule_policy_meal_time_policy_ids, 'Meal Policies that apply to: ' . TTDate::getDate('DATE', $date_stamp) . ' from schedule policies: ', __FILE__, __LINE__, __METHOD__, 10);
            }
            unset($schedule_policy_arr);

            //When meal policies are defined in a schedule policy, they completely override the policy group meal policies.
            //Meal Policy ID: -1 == No Meal
            //Meal Policy ID: 0 == Defined By Policy Group
            if (count($schedule_policy_meal_time_policy_ids) > 0 and !in_array(0, $schedule_policy_meal_time_policy_ids)) {
                //Only use meal policies from schedule policy
                if (in_array(-1, $schedule_policy_meal_time_policy_ids)) {
                    Debug::text('Using NO meal policies...', __FILE__, __LINE__, __METHOD__, 10);
                    $mplf = array(); //No meal policies.
                } else {
                    Debug::text('Using Schedule Policy meal policy: ' . $schedule_policy_meal_time_policy_ids[0], __FILE__, __LINE__, __METHOD__, 10);
                    $mplf = Misc::arrayIntersectByKey($schedule_policy_meal_time_policy_ids, $this->schedule_policy_meal_time_policy);
                }
            } else {
                //Only use meal policies from policy group
                Debug::text('Using Policy Group meal policies...', __FILE__, __LINE__, __METHOD__, 10);
                $mplf = $this->meal_time_policy;
            }

            if (is_array($mplf) and count($mplf) > 0) {
                foreach ($mplf as $mp_obj) {
                    if ($daily_total_time >= $mp_obj->getTriggerTime() and ($type_id == null or in_array($mp_obj->getType(), (array)$type_id))) {
                        $retarr[$mp_obj->getId()] = $mp_obj;
                        Debug::text('  Found Meal policy matching trigger time: ' . $mp_obj->getTriggerTime() . ' ID: ' . $mp_obj->getID(), __FILE__, __LINE__, __METHOD__, 10);
                        break; //Only return one meal policy.
                    } else {
                        Debug::text('  Meal policy does not match type or trigger time: ' . $mp_obj->getTriggerTime(), __FILE__, __LINE__, __METHOD__, 10);
                    }
                }

                if (isset($retarr)) {
                    Debug::text('Found meal policies that apply on date: ' . count($retarr), __FILE__, __LINE__, __METHOD__, 10);
                    return $retarr;
                } elseif ($always_return_at_least_one == true and isset($mp_obj)) {
                    Debug::text('Forced to always return at least one...', __FILE__, __LINE__, __METHOD__, 10);
                    return array($mp_obj); //This is used by calculateExceptionPolicy() so we can *not* trigger No Lunch exception when the user has worked less than trigger time.
                }
            } elseif (is_array($mplf) and count($mplf) == 0) {
                return array(); //Return a blank array so we know no meal policies apply to this day, but there were some, or -1 (No Meal) was used instead.
            }
        }

        Debug::text('No meal policies apply on date...', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function filterSchedulePolicyByDate($date_stamp, $force_all_scheduled_shifts = false)
    {
        if ($force_all_scheduled_shifts == true) {
            Debug::text('Returning all scheduled shifts for the day...', __FILE__, __LINE__, __METHOD__, 10);
            $slf = $this->filterScheduleDataByStatus($date_stamp, $date_stamp, array(10));
        } else {
            $shift_udt_objs = $this->getShiftStartAndEndUserDateTotal($date_stamp, $date_stamp);
            //Make sure we handle cases where the shift hasn't ended yet, but we still need to get the proper (or closest) schedule policy for it.
            // ie: when doing real-time punching and going for lunch.
            // So if start/end records don't exist, just pass FALSE so we still have a hope of matching schedule data.
            if (is_array($shift_udt_objs) and (isset($shift_udt_objs['start']) or isset($shift_udt_objs['end']))) {
                $slf = $this->filterScheduleDataByShiftStartAndEnd(((isset($shift_udt_objs['start'])) ? $shift_udt_objs['start']->getStartTimeStamp() : false), ((isset($shift_udt_objs['end'])) ? $shift_udt_objs['end']->getEndTimeStamp() : false));
            }
        }

        if (isset($slf) and is_array($slf) and count($slf) > 0) {
            foreach ($slf as $s_obj) {
                if ($s_obj->getSchedulePolicyID() > 0) {
                    if (isset($this->schedule_policy_rs[$s_obj->getSchedulePolicyID()])) {
                        $retarr[$s_obj->getSchedulePolicyID()] = $this->schedule_policy_rs[$s_obj->getSchedulePolicyID()];
                    } else {
                        Debug::text('ERROR: Schedule policy that should exist does not: ' . $s_obj->getSchedulePolicyID(), __FILE__, __LINE__, __METHOD__, 10);
                    }
                }
            }

            if (isset($retarr)) {
                Debug::text('Found scheduled shifts for this date: ' . TTDate::getDATE('DATE', $date_stamp) . ' Total: ' . count($retarr), __FILE__, __LINE__, __METHOD__, 10);
                return $retarr;
            }
        }

        Debug::text('No scheduled shifts for this date: ' . TTDate::getDATE('DATE', $date_stamp), __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function filterScheduleDataByStatus($start_date, $end_date, $status_ids = null)
    {
        $slf = $this->schedule;
        Debug::text('Start Date: ' . TTDate::getDate('DATE', $start_date) . ' End Date: ' . TTDate::getDate('DATE', $end_date), __FILE__, __LINE__, __METHOD__, 10);
        if (is_array($slf) and count($slf) > 0) {
            $start_date = TTDate::getMiddleDayEpoch($start_date); //Optimization - Move outside loop.
            $end_date = TTDate::getMiddleDayEpoch($end_date); //Optimization - Move outside loop.
            foreach ($slf as $s_obj) {
                $s_obj_date_stamp = TTDate::getMiddleDayEpoch($s_obj->getDateStamp()); //Optimization - Move outside loop.
                if ($s_obj_date_stamp >= $start_date
                    and $s_obj_date_stamp <= $end_date
                    and in_array($s_obj->getStatus(), (array)$status_ids)
                ) {
                    $retarr[$s_obj->getId()] = $s_obj;
                }
                //else {
                //	Debug::text('Scheduled shift does not match filter: '. $s_obj->getID() .' DateStamp: '. TTDate::getDate('DATE', $s_obj->getDateStamp() ) .' Status: '. $s_obj->getStatus(), __FILE__, __LINE__, __METHOD__, 10);
                //}
            }

            if (isset($retarr)) {
                Debug::text('Found schedule rows matched filter: ' . count($retarr), __FILE__, __LINE__, __METHOD__, 10);
                return $retarr;
            }
        }

        Debug::text('No schedule rows match filter...', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function getShiftStartAndEndUserDateTotal($start_date, $end_date)
    {
        $udtlf = $this->user_date_total;
        if (is_array($udtlf) and count($udtlf) > 0) {
            $start_date = TTDate::getMiddleDayEpoch($start_date); //Optimization - Move outside loop.
            $end_date = TTDate::getMiddleDayEpoch($end_date); //Optimization - Move outside loop.

            $first_in = false;
            $last_out = false;
            foreach ($udtlf as $udt_key => $udt_obj) {
                $udt_obj_date_stamp = TTDate::getMiddleDayEpoch($udt_obj->getDateStamp()); //Optimization - Move outside loop.
                if ($udt_obj_date_stamp >= $start_date
                    and $udt_obj_date_stamp <= $end_date
                    and ($udt_obj->getObjectType() == 10 or $udt_obj->getObjectType() == 25) //Worked time and Absence Time (so we can apply schedule policies to absence shifts)
                    and ($udt_obj->getStartType() == 10 or $udt_obj->getEndType() == 10)
                ) {
                    Debug::text('UDT ID: ' . $udt_obj->getID() . ' Start: ' . TTDate::getDate('DATE+TIME', $udt_obj->getStartTimeStamp()) . ' End: ' . TTDate::getDate('DATE+TIME', $udt_obj->getEndTimeStamp()), __FILE__, __LINE__, __METHOD__, 10);
                    if ($udt_obj->getStartType() == 10 and ($first_in == false or $udt_obj->getStartTimeStamp() < $first_in)) {
                        $first_in = $udt_obj->getStartTimeStamp();
                        $retarr['start'] = $udt_obj;
                    }
                    if ($udt_obj->getEndType() == 10 and ($last_out == false or $udt_obj->getEndTimeStamp() > $last_out)) {
                        $last_out = $udt_obj->getEndTimeStamp();
                        $retarr['end'] = $udt_obj;
                    }
                }
            }

            if (isset($retarr)) {
                Debug::text('Shift Start: ' . TTDate::getDate('DATE+TIME', $first_in) . ' End: ' . TTDate::getDate('DATE+TIME', $last_out), __FILE__, __LINE__, __METHOD__, 10);
                return $retarr;
            }
        }

        Debug::text('No user_date_total rows match filter...', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function filterScheduleDataByShiftStartAndEnd($start_time, $end_time, $schedule_status_id = false)
    {
        $slf = $this->schedule;
        Debug::text('Start Date: ' . TTDate::getDate('DATE+TIME', $start_time) . ' End Date: ' . TTDate::getDate('DATE+TIME', $end_time), __FILE__, __LINE__, __METHOD__, 10);
        if (is_array($slf) and count($slf) > 0) {
            $best_diff = false;
            foreach ($slf as $s_obj) {
                Debug::text('  Schedule Status: ' . $s_obj->getStatus() . ' Start Time: ' . TTDate::getDate('DATE+TIME', $s_obj->getStartTime()) . ' End Time: ' . TTDate::getDate('DATE+TIME', $s_obj->getEndTime()), __FILE__, __LINE__, __METHOD__, 10);
                if ($schedule_status_id == false or (int)$s_obj->getStatus() == (int)$schedule_status_id) {
                    //Only return the nearest schedule to the shift times.
                    //  If we return multiple schedule shifts, then there can be confusion if the
                    //  the employee has one shift from say 8:00AM to 5:00PM and an absent shift from 5:30PM - 11:00PM, and one has a meal policy and the other doesn't.
                    //  The one that doesn't will take precedence.

                    //If the Start/Stop window is large (ie: 6-8hrs) we need to find the closest schedule.
                    $in_schedule_diff = $s_obj->inScheduleDifference($start_time, 10); //In
                    $out_schedule_diff = $s_obj->inScheduleDifference($end_time, 20); //Out
                    if ($in_schedule_diff !== false or $out_schedule_diff !== false) {
                        //If In is outside start/stop window penalize it with the diff of whatever the start/stop window is.
                        if ($in_schedule_diff === false) {
                            $in_schedule_diff = $s_obj->getStartStopWindow();
                        }
                        //If Out is outside start/stop window penalize it with the diff of whatever the start/stop window is.
                        if ($out_schedule_diff === false) {
                            $out_schedule_diff = $s_obj->getStartStopWindow();
                        }

                        $schedule_diff = (int)($in_schedule_diff + $out_schedule_diff);
                        if ($schedule_diff === 0) {
                            Debug::text(' Within schedule times. ', __FILE__, __LINE__, __METHOD__, 10);
                            unset($retarr);
                            $retarr[$s_obj->getId()] = $s_obj;
                            break;
                        } else {
                            if ($schedule_diff > 0 and ($best_diff === false or $schedule_diff < $best_diff)) {
                                Debug::text(' Within schedule start/stop time by: ' . $schedule_diff . ' Prev Best Diff: ' . $best_diff, __FILE__, __LINE__, __METHOD__, 10);
                                $best_diff = $schedule_diff;
                                unset($retarr);
                                $retarr[$s_obj->getId()] = $s_obj;
                            }
                        }
                    } else {
                        Debug::text(' NOT Within schedule times at all...', __FILE__, __LINE__, __METHOD__, 10);
                    }
                }

                /*
                //This returns all schedules that fall within the schedule start/stop window.
                if ( $s_obj->inSchedule( $start_time ) OR $s_obj->inSchedule( $end_time ) ) {
                    $retarr[$s_obj->getId()] = $s_obj;
                }
                */
            }

            if (isset($retarr)) {
                Debug::text('Found schedule rows matched filter: ' . count($retarr), __FILE__, __LINE__, __METHOD__, 10);
                return $retarr;
            }
        }

        Debug::text('No schedule rows match filter...', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function getBaseHourlyRate($pay_formula_policy_id, $pay_code_id, $date_stamp, $contributing_pay_code_hourly_rate = false, $contributing_shift_policy_obj = null, $object_type_ids = null)
    {
        $pay_code_id = (int)$pay_code_id;
        $pay_formula_policy_id = (int)$pay_formula_policy_id;

        Debug::text('Pay Code ID: ' . $pay_code_id . ' DateStamp: ' . $date_stamp . ' Contributing Pay Code Hourly Rate: ' . $contributing_pay_code_hourly_rate, __FILE__, __LINE__, __METHOD__, 10);
        $hourly_rate = 0;
        $tmp_hourly_rate = 0;

        $pay_formula_policy_obj = $this->getPayFormulaPolicyObjectByPayFormulaIdOrPayCodeId($pay_formula_policy_id, $pay_code_id);
        if (!is_object($pay_formula_policy_obj)) {
            Debug::text('  No Pay Formula Policy to use...', __FILE__, __LINE__, __METHOD__, 10);
            return 0;
        }

        switch ($pay_formula_policy_obj->getWageSourceType()) {
            case 10: //Wage Group
                $uw_obj = $this->filterUserWage($pay_formula_policy_obj->getWageGroup(), $date_stamp);
                if (is_object($uw_obj)) {
                    $tmp_hourly_rate = $uw_obj->getHourlyRate();
                }
                break;
            case 20: //Contributing Pay Code
                $tmp_hourly_rate = $contributing_pay_code_hourly_rate;
                break;
            case 30: //Average Contributing Pay Codes
                break;
        }
        $hourly_rate = $tmp_hourly_rate;

        Debug::text('  Base Hourly Rate: ' . $hourly_rate, __FILE__, __LINE__, __METHOD__, 10);
        return $hourly_rate;
    }

    public function getPayFormulaPolicyObjectByPayFormulaIdOrPayCodeId($pay_formula_policy_id, $pay_code_id)
    {
        if (isset($this->pay_formula_policy[$pay_formula_policy_id])) {
            $pay_formula_policy_obj = $this->pay_formula_policy[$pay_formula_policy_id];
        } elseif (isset($this->pay_codes[$pay_code_id]) and $this->pay_codes[$pay_code_id]->getPayFormulaPolicy() > 0 and isset($this->pay_formula_policy[$this->pay_codes[$pay_code_id]->getPayFormulaPolicy()])) {
            $pay_formula_policy_obj = $this->pay_formula_policy[$this->pay_codes[$pay_code_id]->getPayFormulaPolicy()];
        }

        if (isset($pay_formula_policy_obj)) {
            return $pay_formula_policy_obj;
        }

        Debug::text('  No Pay Formula Policy to use...', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    //Need to get all pay codes referenced by policies and all pay codes used by contributing shift policies too.
    //So we may as well just get them all.

    public function filterUserWage($wage_group_id, $date_stamp)
    {
        $uwlf = $this->user_wages;
        if (is_array($uwlf) and count($uwlf) > 0) {
            $date_stamp = TTDate::getMiddleDayEpoch($date_stamp); //Optimization - Move outside loop.
            foreach ($uwlf as $uw_obj) {
                if ($uw_obj->getWageGroup() == $wage_group_id and TTDate::getMiddleDayEpoch($uw_obj->getEffectiveDate()) <= $date_stamp) {
                    Debug::text('User wage DOES match filter... ID: ' . $uw_obj->getID() . ' Date: ' . TTDate::getDate('DATE', $date_stamp), __FILE__, __LINE__, __METHOD__, 10);
                    return $uw_obj;
                }
                //else {
                //	Debug::text('User wage does not match filter... ID: '. $uw_obj->getID() .' Date: '. TTDate::getDate('DATE', $date_stamp ), __FILE__, __LINE__, __METHOD__, 10);
                //}
            }
        }

        Debug::text('No user wage rows match filter... Date: ' . TTDate::getDate('DATE', $date_stamp), __FILE__, __LINE__, __METHOD__, 10);

        return false;
    }

    public function getHourlyRate($pay_formula_policy_id, $pay_code_id, $date_stamp, $base_hourly_rate)
    {
        $pay_code_id = (int)$pay_code_id;
        $pay_formula_policy_id = (int)$pay_formula_policy_id;

        Debug::text('Pay Formula ID: ' . $pay_formula_policy_id . ' Pay Code ID: ' . $pay_code_id . ' Base Hourly Rate: ' . $base_hourly_rate, __FILE__, __LINE__, __METHOD__, 10);
        $hourly_rate = 0;

        $pay_formula_policy_obj = $this->getPayFormulaPolicyObjectByPayFormulaIdOrPayCodeId($pay_formula_policy_id, $pay_code_id);
        if (!is_object($pay_formula_policy_obj)) {
            Debug::text('  No Pay Formula Policy to use...', __FILE__, __LINE__, __METHOD__, 10);
            return 0;
        }

        if (is_object($pay_formula_policy_obj)) {
            $tmp_hourly_rate = 0;
            switch ($pay_formula_policy_obj->getWageSourceType()) {
                case 10: //Wage Group: Since this is based on a static rate, always fill that rate in, regardless of what $base_hourly_rate says. This is required to have a OT policy that uses a Pay Formula that in turn specifies a secondary wage group.
                    $uw_obj = $this->filterUserWage($pay_formula_policy_obj->getWageGroup(), $date_stamp);
                    if (is_object($uw_obj)) {
                        $tmp_hourly_rate = $uw_obj->getHourlyRate();
                    }
                    break;
                case 20: //Contributing Pay Code
                    $tmp_hourly_rate = $base_hourly_rate;
                    break;
                case 30: //Average Contributing Pay Codes
                    $tmp_hourly_rate = $base_hourly_rate;
                    break;
            }

            $hourly_rate = $pay_formula_policy_obj->getHourlyRate($tmp_hourly_rate);
        }

        if (isset($this->pay_codes[$pay_code_id]) and $this->pay_codes[$pay_code_id]->getType() == 30 and $hourly_rate > 0) { //Dock Pay
            $hourly_rate = ($hourly_rate * -1);
        }

        Debug::text('  Hourly Rate: ' . $hourly_rate, __FILE__, __LINE__, __METHOD__, 10);
        return $hourly_rate;
    }

    public function getHourlyRateWithBurden($pay_formula_policy_id, $pay_code_id, $date_stamp, $base_hourly_rate = 0)
    {
        $pay_code_id = (int)$pay_code_id;
        $pay_formula_policy_id = (int)$pay_formula_policy_id;

        $hourly_rate = 0;
        $pay_formula_policy_obj = $this->getPayFormulaPolicyObjectByPayFormulaIdOrPayCodeId($pay_formula_policy_id, $pay_code_id);
        if (!is_object($pay_formula_policy_obj)) {
            Debug::text('  No Pay Formula Policy to use...', __FILE__, __LINE__, __METHOD__, 10);
            return 0;
        }

        $uw_obj = $this->filterUserWage((int)$pay_formula_policy_obj->getWageGroup(), $date_stamp);
        if (is_object($uw_obj)) {
            $hourly_rate = ($base_hourly_rate * (($uw_obj->getLaborBurdenPercent() / 100) + 1));
        }

        if (isset($this->pay_codes[$pay_code_id]) and $this->pay_codes[$pay_code_id]->getType() == 30 and $hourly_rate > 0) { //Dock Pay
            $hourly_rate = ($hourly_rate * -1);
        }

        Debug::text('  Hourly Rate w/Burden: ' . $hourly_rate . ' Based on Base Rate of: ' . $base_hourly_rate, __FILE__, __LINE__, __METHOD__, 10);
        return $hourly_rate;
    }

    public function isOverriddenUserDateTotalObject($udt_obj)
    {
        if (is_array($this->user_date_total)) {
            Debug::Text('Search based on UDT: ID: ' . (int)$udt_obj->getID() . ' Object Type: ' . $udt_obj->getObjectType() . ' Pay Code: ' . $udt_obj->getPayCode() . ' Date: ' . TTDate::getDate('DATE', $udt_obj->getDateStamp()) . ' Total Time: ' . $udt_obj->getTotalTime() . ' Branch: ' . $udt_obj->getBranch() . ' Department: ' . $udt_obj->getDepartment() . ' Job: ' . $udt_obj->getJob() . ' Task: ' . $udt_obj->getJobItem(), __FILE__, __LINE__, __METHOD__, 10);
            foreach ($this->user_date_total as $key => $tmp_udt_obj) {
                if ($key > 0 //Found positive time record, only positive ones can be overridden anyways.
                    and $this->testOverriddenUserDateTotalObject($udt_obj, $tmp_udt_obj)
                ) {
                    Debug::Text('Found override UDT Object key: ' . $key . ' ID: ' . (int)$udt_obj->getID() . ' Object Type: ' . $tmp_udt_obj->getObjectType() . ' Pay Code: ' . $tmp_udt_obj->getPayCode() . ' Date: ' . TTDate::getDate('DATE', $tmp_udt_obj->getDateStamp()) . ' Total Time: ' . $tmp_udt_obj->getTotalTime() . ' Branch: ' . $tmp_udt_obj->getBranch() . ' Department: ' . $tmp_udt_obj->getDepartment() . ' Job: ' . $tmp_udt_obj->getJob() . ' Task: ' . $tmp_udt_obj->getJobItem(), __FILE__, __LINE__, __METHOD__, 10);
                    return true;
                }
                //else {
                //	Debug::Text('Skipping UDT object key: '. $key .' Object Type: '. $tmp_udt_obj->getObjectType() .' Pay Code: '. $tmp_udt_obj->getPayCode() .' Date: '. TTDate::getDate('DATE', $tmp_udt_obj->getDateStamp() ) .' Branch: '. $tmp_udt_obj->getBranch() .' Department: '. $tmp_udt_obj->getDepartment() .' Job: '. $tmp_udt_obj->getJob() .' Task: '. $tmp_udt_obj->getJobItem() .' Override: '. $tmp_udt_obj->getOverride(), __FILE__, __LINE__, __METHOD__, 10);
                //}
            }
        }

        //Debug::Text('No override UDT records...', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function testOverriddenUserDateTotalObject($udt_obj, $tmp_udt_obj)
    {
        if (
            $tmp_udt_obj->getOverride() == true
            and $udt_obj->getObjectType() == $tmp_udt_obj->getObjectType()
            and $udt_obj->getPayCode() == $tmp_udt_obj->getPayCode()
            and TTDate::getMiddleDayEpoch($udt_obj->getDateStamp()) == TTDate::getMiddleDayEpoch($tmp_udt_obj->getDateStamp())
            and $udt_obj->getBranch() == $tmp_udt_obj->getBranch()
            and $udt_obj->getDepartment() == $tmp_udt_obj->getDepartment()
            and $udt_obj->getJob() == $tmp_udt_obj->getJob()
            and $udt_obj->getJobItem() == $tmp_udt_obj->getJobItem()
        ) {
            Debug::Text('Found override UDT Object ID: ' . (int)$udt_obj->getID() . ' Object Type: ' . $tmp_udt_obj->getObjectType() . ' Pay Code: ' . $tmp_udt_obj->getPayCode() . ' Date: ' . TTDate::getDate('DATE', $tmp_udt_obj->getDateStamp()) . ' Total Time: ' . $tmp_udt_obj->getTotalTime() . ' Branch: ' . $tmp_udt_obj->getBranch() . ' Department: ' . $tmp_udt_obj->getDepartment() . ' Job: ' . $tmp_udt_obj->getJob() . ' Task: ' . $tmp_udt_obj->getJobItem(), __FILE__, __LINE__, __METHOD__, 10);
            return true;
        }

        return false;
    }

    //Need to get all pay formula policies referenced by policies and all pay codes too.
    //So we may as well just get them all.

    public function calculateBreakTimePolicy($date_stamp)
    {
        //Calculate break time taken even if no break policies exist.
        $break_policy_total_time = 0;
        $break_overall_total_time = 0;

        $last_punch_in_timestamp = false;

        $plf = $this->filterUserDateTotalDataByPunchTypeIDs($date_stamp, $date_stamp, array(30)); //Break rows only.
        if (is_array($plf) and count($plf) > 0) {
            $break_total_time_arr = array();

            $pair = 0;
            $x = 0;
            $out_for_break = false;
            foreach ($plf as $p_obj) {
                if ($out_for_break == false and $p_obj->getEndType() == 30) {
                    $break_out_timestamp = $p_obj->getEndTimeStamp();
                    $out_for_break = true;
                } elseif ($out_for_break == true and $p_obj->getStartType() == 30) {
                    $break_punch_arr[$pair][20] = $break_out_timestamp;
                    $break_punch_arr[$pair][10] = $p_obj->getStartTimeStamp();

                    $out_for_break = false;
                    $pair++;
                    unset($break_out_timestamp);

                    if ($p_obj->getStartType() == 30 and $p_obj->getEndType() == 30) {
                        $break_out_timestamp = $p_obj->getEndTimeStamp();
                        $out_for_break = true;
                    }
                } else {
                    $out_for_break = false;
                }
                $x++;
            }

            if (isset($break_punch_arr)) {
                //Debug::Arr($break_punch_arr, 'Break Array: ', __FILE__, __LINE__, __METHOD__, 10);

                foreach ($break_punch_arr as $punch_control_id => $time_stamp_arr) {
                    if (isset($time_stamp_arr[10]) and isset($time_stamp_arr[20])) {
                        if (!isset($this->user_date_total[$this->user_date_total_insert_id])) {
                            $udtf = TTnew('UserDateTotalFactory');
                            $udtf->setUser($this->getUserObject()->getId());
                            $udtf->setDateStamp($date_stamp);
                            $udtf->setObjectType(111); //Break (Taken)
                            $udtf->setPayCode(0);

                            $udtf->setBranch((int)$this->getUserObject()->getDefaultBranch());
                            $udtf->setDepartment((int)$this->getUserObject()->getDefaultDepartment());
                            $udtf->setJob((int)$this->getUserObject()->getDefaultJob());
                            $udtf->setJobItem((int)$this->getUserObject()->getDefaultJobItem());

                            $udtf->setStartType(30); //Break
                            $udtf->setStartTimeStamp($time_stamp_arr[20]);
                            $udtf->setEndType(30); //Break
                            $udtf->setEndTimeStamp($time_stamp_arr[10]);

                            $udtf->setQuantity(count($break_punch_arr)); //Use this to count total lunches taken?
                            $udtf->setBadQuantity(0);
                            $udtf->setTotalTime(bcsub($time_stamp_arr[10], $time_stamp_arr[20]));

                            $udtf->setEnableCalcSystemTotalTime(false);
                            $udtf->preSave(); //Call this so TotalTimeAmount is calculated immediately, as we don't save these records until later.

                            //Don't save the record, just add it to the existing array, so it can be included in other calculations.
                            //We will save these records at the end.
                            $this->user_date_total[$this->user_date_total_insert_id] = $udtf;

                            Debug::text('   Adding UDT row for Break (Taken) Total Time: ' . $udtf->getTotalTime(), __FILE__, __LINE__, __METHOD__, 10);
                            $this->user_date_total_insert_id--;
                        } else {
                            Debug::text('ERROR: Duplicate starting ID for some reason! ' . $this->user_date_total_insert_id, __FILE__, __LINE__, __METHOD__, 10);
                        }

                        $break_overall_total_time = bcadd($break_overall_total_time, bcsub($time_stamp_arr[10], $time_stamp_arr[20]));
                        $break_total_time_arr[] = bcsub($time_stamp_arr[10], $time_stamp_arr[20]);
                    } else {
                        Debug::text(' Break Punches not paired... Skipping!', __FILE__, __LINE__, __METHOD__, 10);
                    }
                }

                //Get the last punch in timestamp so we start auto-add/auto-deduct timestamps from this.
                $last_punch_in_timestamp = $time_stamp_arr[10];
            } else {
                Debug::text(' No Break Punches found, or none are paired.', __FILE__, __LINE__, __METHOD__, 10);
            }
        }
        unset($plf, $pair, $x, $out_for_break, $break_out_timestamp, $break_punch_arr, $break_pair_total_time, $time_stamp_arr);

        $udtlf = $this->filterUserDateTotalDataByObjectTypeIDs($date_stamp, $date_stamp, array(10));
        if (is_array($udtlf) and count($udtlf) > 0) {
            $day_total_time = 0;
            foreach ($udtlf as $udt_obj) {
                $udt_arr[$udt_obj->getId()] = $udt_obj->getTotalTime();

                $day_total_time += $udt_obj->getTotalTime();
            }
            Debug::text('Day Total Time: ' . $day_total_time, __FILE__, __LINE__, __METHOD__, 10);

            //Loop over all regular time policies calculating the pay codes, up until $maximum_time is reached.
            $break_time_policies = $this->filterBreakTimePolicy($date_stamp, $day_total_time);
            if ($day_total_time > 0 and is_array($break_time_policies) and count($break_time_policies) > 0) {
                $remaining_break_time = $break_overall_total_time;

                $i = 0;
                foreach ($break_time_policies as $bp_obj) {
                    if ($last_punch_in_timestamp == false) {
                        if (!isset($shift_udt_objs)) {
                            $shift_udt_objs = $this->getShiftStartAndEndUserDateTotal($date_stamp, $date_stamp);
                        }
                        if (is_array($shift_udt_objs) and isset($shift_udt_objs['start'])) {
                            $last_punch_in_timestamp = ($shift_udt_objs['start']->getStartTimeStamp() + $bp_obj->getTriggerTime());
                        }
                    }

                    $break_policy_time = 0;
                    if (!isset($break_total_time_arr[$i])) {
                        $break_total_time_arr[$i] = 0; //Prevent PHP warnings.
                    }

                    //This is the time that can be considered for the break.
                    if ($bp_obj->getIncludeMultipleBreaks() == true) {
                        //If only one break policy is defined (say 30min auto-add after 0hrs w/include punch time)
                        //and the employee punches out for two breaks, one for 10mins and one for 15mins, only the first break will be added back in.
                        //Because FairnessTNA tries to match each break to a specific break policy.
                        //getIncludeMultipleBreaks(): is the flag that ignores how many breaks there are in total,
                        //and just combines any breaks together that fall within the active after time.
                        //So it doesn't matter if the employee takes 1 break or 30, they are all combined into one after the active_after time.
                        //FIXME: Handle cases where one break policy includes multiples and another one does not. Currently the break time may be doubled up in this case.
                        $eligible_break_total_time = array_sum($break_total_time_arr);
                        Debug::text(' Including multiple breaks...', __FILE__, __LINE__, __METHOD__, 10);
                    } else {
                        $eligible_break_total_time = $break_total_time_arr[$i];
                    }

                    Debug::text('Break Policy ID: ' . $bp_obj->getId() . ' Type ID: ' . $bp_obj->getType() . ' Break Total Time: ' . $eligible_break_total_time . ' Amount: ' . $bp_obj->getAmount() . ' Day Total Time: ' . $day_total_time, __FILE__, __LINE__, __METHOD__, 10);
                    switch ($bp_obj->getType()) {
                        case 10: //Auto-Deduct
                            Debug::text(' Break AutoDeduct...', __FILE__, __LINE__, __METHOD__, 10);
                            if ($bp_obj->getIncludeBreakPunchTime() == true) {
                                $break_policy_time = (bcsub($bp_obj->getAmount(), $eligible_break_total_time) * -1);
                                //If they take more then their alloted break, zero it out so time isn't added.
                                if ($break_policy_time > 0) {
                                    $break_policy_time = 0;
                                }
                            } else {
                                $break_policy_time = ($bp_obj->getAmount() * -1);
                            }
                            break;
                        case 15: //Auto-Add
                            Debug::text(' Break AutoAdd...', __FILE__, __LINE__, __METHOD__, 10);
                            if ($bp_obj->getIncludeBreakPunchTime() == true) {
                                if ($eligible_break_total_time > $bp_obj->getAmount()) {
                                    $break_policy_time = $bp_obj->getAmount();
                                } else {
                                    $break_policy_time = $eligible_break_total_time;
                                }
                            } else {
                                $break_policy_time = $bp_obj->getAmount();
                            }
                            break;
                    }

                    if ($bp_obj->getIncludeBreakPunchTime() == true and $break_policy_time > $remaining_break_time) {
                        $break_policy_time = $remaining_break_time;
                    }
                    if ($bp_obj->getIncludeBreakPunchTime() == true) { //Handle cases where some break policies include punch time, and others don't.
                        $remaining_break_time -= $break_policy_time;
                    }

                    Debug::text('  bBreak Policy Total Time: ' . $break_policy_time . ' Break Policy ID: ' . $bp_obj->getId() . ' Remaining Time: ' . $remaining_break_time, __FILE__, __LINE__, __METHOD__, 10);

                    if ($break_policy_time != 0) {
                        $break_policy_total_time = bcadd($break_policy_total_time, $break_policy_time);

                        if (is_array($udt_arr) and $day_total_time > 0) {
                            $remainder = 0;
                            foreach ($udt_arr as $udt_id => $total_time) {
                                //Make sure we use bcmath() functions here to avoid floating point imprecision issues.
                                $udt_raw_break_policy_time = bcmul(bcdiv($total_time, $day_total_time), $break_policy_time);
                                if ($break_policy_time > 0) {
                                    $rounded_udt_raw_break_policy_time = floor($udt_raw_break_policy_time);
                                    $remainder = bcadd($remainder, bcsub($udt_raw_break_policy_time, $rounded_udt_raw_break_policy_time));
                                } else {
                                    $rounded_udt_raw_break_policy_time = ceil($udt_raw_break_policy_time);
                                    $remainder = bcadd($remainder, bcsub($udt_raw_break_policy_time, $rounded_udt_raw_break_policy_time));
                                }

                                $worked_time_break_policy_adjustments[$udt_id] = (int)$rounded_udt_raw_break_policy_time;
                                Debug::text('UserDateTotal Row ID: ' . $udt_id . ' UDT Total Time: ' . $total_time . ' Raw Break Policy Time: ' . $udt_raw_break_policy_time . '(' . $rounded_udt_raw_break_policy_time . ') Remainder: ' . $remainder, __FILE__, __LINE__, __METHOD__, 10);
                            }

                            //Add remainder rounded to the nearest second to the last row.
                            if ($break_policy_time > 0) {
                                $remainder = ceil($remainder);
                            } else {
                                $remainder = floor($remainder);
                            }
                            $worked_time_break_policy_adjustments[$udt_id] = (int)($worked_time_break_policy_adjustments[$udt_id] + $remainder);

                            Debug::Arr($worked_time_break_policy_adjustments, 'UserDateTotal Adjustments: Final Remainder: ' . $remainder, __FILE__, __LINE__, __METHOD__, 10);
                        }

                        //Create a UDT row for each break policy adjustment element, so other policies can include/exclude the break/break time on its own.
                        foreach ($worked_time_break_policy_adjustments as $udt_id => $worked_time_break_policy_adjustment) {
                            if (isset($this->user_date_total[$udt_id])) {
                                if (!isset($this->user_date_total[$this->user_date_total_insert_id])) {
                                    $udtf = TTnew('UserDateTotalFactory');
                                    $udtf->setUser($this->getUserObject()->getId());
                                    $udtf->setDateStamp($date_stamp);
                                    $udtf->setObjectType(110); //Break
                                    $udtf->setSourceObject((int)$bp_obj->getId());
                                    $udtf->setPayCode((int)$bp_obj->getPayCode());

                                    $udtf->setBranch((int)$this->user_date_total[$udt_id]->getBranch());
                                    $udtf->setDepartment((int)$this->user_date_total[$udt_id]->getDepartment());
                                    $udtf->setJob((int)$this->user_date_total[$udt_id]->getJob());
                                    $udtf->setJobItem((int)$this->user_date_total[$udt_id]->getJobItem());

                                    $udtf->setStartType(30); //Break
                                    $udtf->setEndType(30); //Break
                                    if ($bp_obj->getType() == 15) { //Auto-Add
                                        if ($last_punch_in_timestamp != '') { //If the first punch is a Break IN and only a Normal OUT, $last_punch_in_timestamp will be NULL.
                                            $udtf->setStartTimeStamp($last_punch_in_timestamp - abs($worked_time_break_policy_adjustment));
                                            $udtf->setEndTimeStamp($last_punch_in_timestamp);
                                            $last_punch_in_timestamp = $udtf->getStartTimeStamp();
                                        }
                                    } else { //Auto-Deduct
                                        if ($last_punch_in_timestamp != '') { //If the first punch is a Break IN and only a Normal OUT, $last_punch_in_timestamp will be NULL.
                                            $udtf->setStartTimeStamp($last_punch_in_timestamp);
                                            $udtf->setEndTimeStamp($last_punch_in_timestamp + abs($worked_time_break_policy_adjustment));
                                            $last_punch_in_timestamp = $udtf->getEndTimeStamp();
                                        }
                                    }

                                    $udtf->setQuantity(0);
                                    $udtf->setBadQuantity(0);
                                    $udtf->setTotalTime($worked_time_break_policy_adjustment);

                                    //Base hourly rate on the regular wage
                                    $udtf->setBaseHourlyRate($this->getBaseHourlyRate($bp_obj->getPayFormulaPolicy(), $udtf->getPayCode(), $date_stamp));
                                    $udtf->setHourlyRate($this->getHourlyRate($bp_obj->getPayFormulaPolicy(), $udtf->getPayCode(), $date_stamp, $udtf->getBaseHourlyRate()));
                                    $udtf->setHourlyRateWithBurden($this->getHourlyRateWithBurden($bp_obj->getPayFormulaPolicy(), $udtf->getPayCode(), $date_stamp, $udtf->getHourlyRate()));

                                    $udtf->setEnableCalcSystemTotalTime(false);
                                    $udtf->preSave(); //Call this so TotalTimeAmount is calculated immediately, as we don't save these records until later.

                                    if ($this->isOverriddenUserDateTotalObject($udtf) == false) {
                                        //Don't save the record, just add it to the existing array, so it can be included in other calculations.
                                        //We will save these records at the end.
                                        $this->user_date_total[$this->user_date_total_insert_id] = $udtf;
                                        Debug::text(' Adding UDT row for Break Policy Total Time: ' . $worked_time_break_policy_adjustment, __FILE__, __LINE__, __METHOD__, 10);
                                        $this->user_date_total_insert_id--;
                                    }
                                } else {
                                    Debug::text('ERROR: Duplicate starting ID for some reason! ' . $this->user_date_total_insert_id, __FILE__, __LINE__, __METHOD__, 10);
                                }
                            } else {
                                Debug::text(' ERROR: UDT ID does not exist: ' . $udt_id, __FILE__, __LINE__, __METHOD__, 10);
                            }
                        }
                    }

                    Debug::text('  cBreak Policy Total Time: ' . $break_policy_time . ' Break Policy ID: ' . $bp_obj->getId() . ' Remaining Time: ' . $remaining_break_time, __FILE__, __LINE__, __METHOD__, 10);

                    $i++;
                }

                Debug::text('Total Break Policy Time: ' . $break_policy_time, __FILE__, __LINE__, __METHOD__, 10);

                $this->user_date_total = $this->sortUserDateTotalData($this->user_date_total); //Sort UDT records once done modifying them. This should help avoid having to sort them everytime we get/filter them.

                return true;
            }
        } else {
            Debug::text('No UserDateTotal rows...', __FILE__, __LINE__, __METHOD__, 10);
        }

        Debug::text('No break policies to calculate...', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function filterBreakTimePolicy($date_stamp, $daily_total_time = null, $type_id = null, $always_return_at_least_one = false)
    {
        if (($daily_total_time > 0 or $always_return_at_least_one == true)
            and (
                (is_array($this->break_time_policy) and count($this->break_time_policy) > 0)
                or
                (is_array($this->schedule_policy_break_time_policy) and count($this->schedule_policy_break_time_policy) > 0)
            )
        ) {
            $schedule_policy_break_time_policy_ids = array();
            $schedule_policy_arr = $this->filterSchedulePolicyByDate($date_stamp);
            if (is_array($schedule_policy_arr)) {
                foreach ($schedule_policy_arr as $sp_obj) {
                    if (is_array($sp_obj->getBreakPolicy())) {
                        $schedule_policy_break_time_policy_ids = array_merge($schedule_policy_break_time_policy_ids, (array)$sp_obj->getBreakPolicy());
                    }
                }
                Debug::Arr($schedule_policy_break_time_policy_ids, 'Break Policies that apply to: ' . TTDate::getDate('DATE', $date_stamp) . ' from schedule policies: ', __FILE__, __LINE__, __METHOD__, 10);
            }
            unset($schedule_policy_arr);

            //When break policies are defined in a schedule policy, they completely override the policy group break policies.
            //Break Policy ID: -1 == No Break
            //Break Policy ID: 0 == Defined By Policy Group
            if (count($schedule_policy_break_time_policy_ids) > 0 and !in_array(0, $schedule_policy_break_time_policy_ids)) {
                //Only use break policies from schedule policy
                if (in_array(-1, $schedule_policy_break_time_policy_ids)) {
                    Debug::text('Using NO break policies...', __FILE__, __LINE__, __METHOD__, 10);
                    $bplf = array(); //No break policies.
                } else {
                    Debug::text('Using Schedule Policy break policies...', __FILE__, __LINE__, __METHOD__, 10);
                    $bplf = Misc::arrayIntersectByKey($schedule_policy_break_time_policy_ids, $this->schedule_policy_break_time_policy);
                }
            } else {
                //Only use break policies from policy group
                Debug::text('Using Policy Group break policies...', __FILE__, __LINE__, __METHOD__, 10);
                $bplf = $this->break_time_policy;
            }

            if (is_array($bplf) and count($bplf) > 0) {
                foreach ($bplf as $bp_obj) {
                    if ($daily_total_time >= $bp_obj->getTriggerTime() and ($type_id == null or in_array($bp_obj->getType(), (array)$type_id))) {
                        $retarr[$bp_obj->getId()] = $bp_obj;
                        Debug::text('  Found Break policy matching trigger time: ' . $bp_obj->getTriggerTime() . ' Name: ' . $bp_obj->getName() . ' ID: ' . $bp_obj->getID(), __FILE__, __LINE__, __METHOD__, 10);
                    } else {
                        Debug::text('  Break policy does not match trigger time: ' . $bp_obj->getTriggerTime(), __FILE__, __LINE__, __METHOD__, 10);
                    }
                }

                if (isset($retarr)) {
                    Debug::text('Found break policies that apply on date: ' . count($retarr), __FILE__, __LINE__, __METHOD__, 10);

                    //Since we have included/excluded additional policies, we need to resort them again so they are in the proper order.
                    uasort($retarr, array($this, 'BreakTimePolicySortByTriggerTimeAsc'));

                    return $retarr;
                } elseif ($always_return_at_least_one == true and isset($bp_obj)) {
                    Debug::text('Forced to always return at least one...', __FILE__, __LINE__, __METHOD__, 10);
                    return array($bp_obj); //This is used by calculateExceptionPolicy() so we can *not* trigger No Lunch exception when the user has worked less than trigger time.
                }
            } elseif (is_array($bplf) and count($bplf) == 0) {
                return array(); //Return a blank array so we know no meal policies apply to this day, but there were some, or -1 (No Meal) was used instead.
            }
        }

        Debug::text('No break policies apply on date...', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function calculateHolidayPolicy($date_stamp)
    {
        $holiday_obj = $this->filterHoliday($date_stamp, null, true); //Only consider holiday policies assigned to policy groups. There may be different holiday policies assigned to contributing shift policies
        if (is_object($holiday_obj)) {
            Debug::text(' Found Holiday: ' . $holiday_obj->getName(), __FILE__, __LINE__, __METHOD__, 10);

            //Check for conflicting/overridden records, so we don't double up on the time.
            //This policy could calculate 9.52hrs, but the user could override it to 9hrs, so if that happens simply skip calculating the holiday time again.
            if (is_object($holiday_obj->getHolidayPolicyObject())
                and $holiday_obj->getHolidayPolicyObject()->getAbsencePolicyID() != false
                and is_object($holiday_obj->getHolidayPolicyObject()->getAbsencePolicyObject())
                and $this->isConflictingUserDateTotal($date_stamp, array(25, 50), (int)$holiday_obj->getHolidayPolicyObject()->getAbsencePolicyObject()->getPayCode()) == false
            ) {
                $holiday_time = 0;
                if ($this->isEligibleForHoliday($date_stamp, $holiday_obj->getHolidayPolicyObject())) {
                    Debug::text(' User is Eligible for Holiday: ' . $holiday_obj->getName(), __FILE__, __LINE__, __METHOD__, 10);

                    $holiday_time = $this->getHolidayTime($date_stamp, $holiday_obj->getHolidayPolicyObject());
                    Debug::text(' User average time for Holiday: ' . TTDate::getHours($holiday_time), __FILE__, __LINE__, __METHOD__, 10);
                } else {
                    Debug::text(' User is not eligible for holiday (adding record with 0 time)...', __FILE__, __LINE__, __METHOD__, 10);
                }

                //Need to still record if holiday_time=0 as the user could be scheduled for 8hrs of Stat Holiday
                //but they aren't eligible to receive any holiday time, if we don't create UDT record with total_time=0
                //then the scheduled time of 8hrs will be used instead, which is incorrect.
                //This won't actually get saved, its just used to cause calculateScheduleTime() to ignore this day instead.
                if ($holiday_time >= 0) {
                    //Try to get the start/end time of the scheduled shift for the holiday, so we can use that as the start/end time for the UDT record.
                    //This helps us match the schedule to the UDT records and apply any Schedule Policy's (which they themselves may contain premium policies).
                    $slf = $this->filterScheduleDataByStatus($date_stamp, $date_stamp, 20);
                    if (is_array($slf) and count($slf) > 0) {
                        foreach ($slf as $key => $s_obj) {
                            if ($s_obj->getAbsencePolicyID() == $holiday_obj->getHolidayPolicyObject()->getAbsencePolicyID()) {
                                Debug::text('   Found Scheduled Shift with the same Absence Policy to match to the UDT record... ID: ' . $s_obj->getID() . ' Start: ' . TTDate::getDate('DATE+TIME', $s_obj->getStartTime()) . ' End: ' . TTDate::getDate('DATE+TIME', $s_obj->getEndTime()), __FILE__, __LINE__, __METHOD__, 10);
                                $scheduled_shifts[] = $s_obj;
                                break;
                            }
                        }
                    }
                    unset($slf);

                    Debug::text(' Adding Holiday hours: ' . TTDate::getHours($holiday_time) . '(' . $holiday_time . ')', __FILE__, __LINE__, __METHOD__, 10);
                    if (!isset($this->user_date_total[$this->user_date_total_insert_id])) {
                        $udtf = TTnew('UserDateTotalFactory');
                        $udtf->setUser($this->getUserObject()->getId());
                        $udtf->setDateStamp($date_stamp);
                        $udtf->setObjectType(50); //Absence
                        $udtf->setSourceObject($holiday_obj->getHolidayPolicyObject()->getAbsencePolicyID());
                        $udtf->setPayCode((int)$holiday_obj->getHolidayPolicyObject()->getAbsencePolicyObject()->getPayCode());

                        $udtf->setBranch((int)$this->getUserObject()->getDefaultBranch());
                        $udtf->setDepartment((int)$this->getUserObject()->getDefaultDepartment());
                        $udtf->setTotalTime($holiday_time);

                        //See above comments when we get the scheduled shift objects.
                        if (isset($scheduled_shifts[0])) {
                            $udtf->setStartType(10); //Normal
                            $udtf->setStartTimeStamp($scheduled_shifts[0]->getStartTime());
                            $udtf->setEndType(10); //Normal
                            $udtf->setEndTimeStamp(($scheduled_shifts[0]->getStartTime() + $holiday_time));
                        }

                        $udtf->setBaseHourlyRate($this->getBaseHourlyRate($holiday_obj->getHolidayPolicyObject()->getAbsencePolicyObject()->getPayFormulaPolicy(), $udtf->getPayCode(), $date_stamp));
                        $udtf->setHourlyRate($this->getHourlyRate($holiday_obj->getHolidayPolicyObject()->getAbsencePolicyObject()->getPayFormulaPolicy(), $udtf->getPayCode(), $date_stamp, $udtf->getBaseHourlyRate()));
                        $udtf->setHourlyRateWithBurden($this->getHourlyRateWithBurden($holiday_obj->getHolidayPolicyObject()->getAbsencePolicyObject()->getPayFormulaPolicy(), $udtf->getPayCode(), $date_stamp, $udtf->getHourlyRate()));

                        if ($holiday_time == 0 and $this->is_eligible_holiday_description != '') { //Include note explaining why they are not receiving holiday time.
                            $udtf->setNote(TTi18n::getText('Not Eligible for holiday') . ' - ' . $this->is_eligible_holiday_description . '.');
                        }

                        $udtf->setEnableCalcSystemTotalTime(false);
                        $udtf->preSave(); //Call this so TotalTimeAmount is calculated immediately, as we don't save these records until later.

                        if ($this->isOverriddenUserDateTotalObject($udtf) == false) {
                            //Don't save the record, just add it to the existing array, so it can be included in other calculations.
                            //We will save these records at the end.
                            $this->user_date_total[$this->user_date_total_insert_id] = $udtf;
                            $this->user_date_total_insert_id--;
                        }
                    } else {
                        Debug::text('ERROR: Duplicate starting ID for some reason! ' . $this->user_date_total_insert_id, __FILE__, __LINE__, __METHOD__, 10);
                    }
                    unset($scheduled_shifts);
                } else {
                    Debug::text('No holiday time to utilize...', __FILE__, __LINE__, __METHOD__, 10);
                }
            } else {
                Debug::text('Overridden holiday time, skipping policy calculation...', __FILE__, __LINE__, __METHOD__, 10);
            }

            $this->user_date_total = $this->sortUserDateTotalData($this->user_date_total); //Sort UDT records once done modifying them. This should help avoid having to sort them everytime we get/filter them.

            return true;
        }

        Debug::text('No holiday policies to calculate...', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function filterHoliday($date_stamp, $holiday_policy_obj = null, $assigned_to_policy_group = null)
    {
        $hlf = $this->holiday;
        if (is_array($hlf) and count($hlf) > 0) {
            $date_stamp = TTDate::getMiddleDayEpoch($date_stamp); //Optimization - Move outside loop.

            foreach ($hlf as $h_obj) {
                if (TTDate::getMiddleDayEpoch($h_obj->getDateStamp()) == $date_stamp) {
                    if (
                        (
                            $assigned_to_policy_group == null
                            or ($assigned_to_policy_group == true and isset($this->policy_group_holiday_policy_ids[$h_obj->getHolidayPolicyID()]))
                            or ($assigned_to_policy_group == false and !isset($this->policy_group_holiday_policy_ids[$h_obj->getHolidayPolicyID()]))
                        )
                        and
                        (
                            $holiday_policy_obj == null or (is_object($holiday_policy_obj) and $h_obj->getHolidayPolicyID() == $holiday_policy_obj->getId())
                        )
                    ) {
                        $retarr = $h_obj; //Can only be one holiday per day. So once we find one break out of the loop.
                        break;
                    }
                }
                //else {
                //	Debug::text('Holiday date does not match date parameter. Holiday: '.  TTDate::getDate('DATE+TIME', $h_obj->getDateStamp() ) .' DateStamp: '.  TTDate::getDate('DATE+TIME', $date_stamp ), __FILE__, __LINE__, __METHOD__, 10);
                //}
            }

            if (isset($retarr)) {
                Debug::text('Found holidays that apply on date: ' . count($retarr), __FILE__, __LINE__, __METHOD__, 10);
                return $retarr;
            }
        }

        //Debug::text('No holidays apply on date: '. TTDate::getDate('DATE+TIME', $date_stamp ), __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function isConflictingUserDateTotal($date_stamp, $object_type_id, $pay_code_id = null, $branch_id = null, $department_id = null, $job_id = null, $job_item_id = null)
    {
        if (is_array($this->user_date_total)) {
            $date_stamp = TTDate::getMiddleDayEpoch($date_stamp); //Optimization - Move outside of loop.
            foreach ($this->user_date_total as $udt_key => $udt_obj) {
                if (TTDate::getMiddleDayEpoch($udt_obj->getDateStamp()) == $date_stamp
                    and in_array($udt_obj->getObjectType(), (array)$object_type_id)
                ) {
                    if (
                        ($pay_code_id === null or $udt_obj->getPayCode() == $pay_code_id)
                        and
                        ($branch_id === null or $udt_obj->getBranch() == $branch_id)
                        and
                        ($department_id === null or $udt_obj->getDepartment() == $department_id)
                        and
                        ($job_id === null or $udt_obj->getJob() == $job_id)
                        and
                        ($job_item_id === null or $udt_obj->getJobItem() == $job_item_id)

                    ) {
                        Debug::text('Found conflicting UserDateTotal row: ' . $udt_key, __FILE__, __LINE__, __METHOD__, 10);
                        return true;
                    }
                }
            }
        }

        Debug::text('No conflicting UserDateTotal rows...', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function isEligibleForHoliday($date_stamp, $holiday_policy_obj, $ignore_after_eligibility = false)
    {
        //Make sure the employee has been employed long enough according to labor standards
        //Also make sure that the employee hasn't been terminated on or before the holiday.
        $this->is_eligible_holiday_description = null;

        $current_employed_days = TTDate::getDays($date_stamp - $this->getUserObject()->getHireDate());

        $date_stamp = TTDate::getMiddleDayEpoch($date_stamp); //Optimization - Move outside loop.

        //if ( $this->getUserObject()->getHireDate() <= ( $date_stamp - ( $holiday_policy_obj->getMinimumEmployedDays() * 86400 ) )
        if ($current_employed_days >= $holiday_policy_obj->getMinimumEmployedDays()
            and ($this->getUserObject()->getTerminationDate() == '' or ($this->getUserObject()->getTerminationDate() != '' and $this->getUserObject()->getTerminationDate() > $date_stamp))
        ) {
            Debug::text('Employee has been employed long enough! Holiday Policy ID: ' . $holiday_policy_obj->getID(), __FILE__, __LINE__, __METHOD__, 10);

            if ($holiday_policy_obj->getType() == 20 or $holiday_policy_obj->getType() == 30) {
                //One of the primary use-cases for this is to make sure the employee worked on the holiday when scheduled. (Showed up for their shift).
                //Or to disable the holiday policy when the employee does work so overtime policies can just be used instead.
                if ($holiday_policy_obj->getShiftOnHolidayType() > 0) {
                    $shift_on_holiday = count($this->getDayArrayUserDateTotalData($this->filterUserDateTotalDataByContributingShiftPolicy($date_stamp, $date_stamp, $this->contributing_shift_policy[$holiday_policy_obj->getEligibleContributingShiftPolicy()], array(10, 20, 25, 30))));
                    $scheduled_working_on_holiday = 0;
                    $scheduled_absent_on_holiday = 0;
                    if ($holiday_policy_obj->getShiftOnHolidayType() == 30) {
                        $scheduled_working_on_holiday = count($this->getScheduleDates($this->filterScheduleDataByStatus($date_stamp, $date_stamp, array(10))));
                    } elseif ($holiday_policy_obj->getShiftOnHolidayType() == 40) {
                        $scheduled_absent_on_holiday = count($this->getScheduleDates($this->filterScheduleDataByStatus($date_stamp, $date_stamp, array(20))));
                    }
                    Debug::text('ON HOLIDAY: Type: ' . $holiday_policy_obj->getShiftOnHolidayType() . ' Shift: ' . $shift_on_holiday . ' Schedule: Working: ' . $scheduled_working_on_holiday . ' Absent: ' . $scheduled_absent_on_holiday, __FILE__, __LINE__, __METHOD__, 10);

                    if ($holiday_policy_obj->getShiftOnHolidayType() == 10) { //Must work on Holiday
                        if ($shift_on_holiday > 0) {
                            Debug::text('Employee has worked on the holiday! Success.', __FILE__, __LINE__, __METHOD__, 10);
                        } else {
                            $this->is_eligible_holiday_description = TTi18n::getText('Did not work on the holiday');
                            Debug::text('Employee has NOT worked on the holiday!', __FILE__, __LINE__, __METHOD__, 10);
                            return false;
                        }
                    } elseif ($holiday_policy_obj->getShiftOnHolidayType() == 20) { //Must NOT work on Holiday
                        if ($shift_on_holiday == 0) {
                            Debug::text('Employee has NOT worked on the holiday! Success.', __FILE__, __LINE__, __METHOD__, 10);
                        } else {
                            //$this->is_eligible_holiday_description = TTi18n::getText('Worked on holiday'); //Hide this, as this case can often be used to disable holiday policy so overtime policy can be used instead.
                            Debug::text('Employee has worked on the holiday!', __FILE__, __LINE__, __METHOD__, 10);
                            return false;
                        }
                    } elseif ($holiday_policy_obj->getShiftOnHolidayType() == 30) { //If scheduled to work, they must work. Otherwise if not scheduled (or scheduled absent) and they don't work its fine too.
                        if ($shift_on_holiday > 0 and $scheduled_working_on_holiday > 0) {
                            Debug::text('Employee has worked on the holiday when scheduled! Success.', __FILE__, __LINE__, __METHOD__, 10);
                        } elseif ($scheduled_working_on_holiday == 0) {
                            Debug::text('Employee is not scheduled to work! Success.', __FILE__, __LINE__, __METHOD__, 10);
                        } else {
                            $this->is_eligible_holiday_description = TTi18n::getText('Not worked on holiday when scheduled');
                            Debug::text('Employee has NOT worked on the holiday when scheduled!', __FILE__, __LINE__, __METHOD__, 10);
                            return false;
                        }
                    } elseif ($holiday_policy_obj->getShiftOnHolidayType() == 40) { //If scheduled absent, they must not work. Otherwise if not scheduled, or scheduled to work and they work that is fine too.
                        if ($shift_on_holiday == 0 and $scheduled_absent_on_holiday > 0) {
                            Debug::text('Employee has NOT worked on the holiday when scheduled absent! Success.', __FILE__, __LINE__, __METHOD__, 10);
                        } elseif ($scheduled_absent_on_holiday == 0) {
                            Debug::text('Employee is not scheduled absent on the holiday! Success.', __FILE__, __LINE__, __METHOD__, 10);
                        } else {
                            $this->is_eligible_holiday_description = TTi18n::getText('Worked holiday when scheduled absent');
                            Debug::text('Employee has worked on the holiday or not scheduled absent!', __FILE__, __LINE__, __METHOD__, 10);
                            return false;
                        }
                    }
                    unset($shift_on_holiday, $scheduled_working_on_holiday, $scheduled_absent_on_holiday);
                }

                if ($holiday_policy_obj->getMinimumWorkedDays() > 0 and $holiday_policy_obj->getMinimumWorkedPeriodDays() > 0) {
                    if ($holiday_policy_obj->getWorkedScheduledDays() == 1) { //Scheduled Days
                        Debug::text('BEFORE: Using scheduled days!', __FILE__, __LINE__, __METHOD__, 10);

                        //Use 365days as the upper limit.
                        $this->getScheduleData(($date_stamp - (86400 * 365)), ($date_stamp - 86400), 10, $holiday_policy_obj->getMinimumWorkedPeriodDays(), array('a.date_stamp' => 'desc'));

                        $scheduled_date_stamps_before = $this->getScheduleDates($this->filterScheduleDataByDateAndDirection($date_stamp, 10, 'desc', $holiday_policy_obj->getMinimumWorkedPeriodDays()));
                        //Debug::Arr( (array)$scheduled_date_stamps_before, 'Scheduled DateStamps Before: ', __FILE__, __LINE__, __METHOD__, 10);
                        Debug::Text('Scheduled DateStamps Before: ' . count((array)$scheduled_date_stamps_before), __FILE__, __LINE__, __METHOD__, 10);

                        //Get the date range from the schedules dates that we found.
                        $calendar_date_range = $this->getDateRangeFromDateArray((array)$scheduled_date_stamps_before);
                    } elseif ($holiday_policy_obj->getWorkedScheduledDays() == 2) { //Holiday Week Days
                        Debug::Text('Holiday Week Days Before: ' . $holiday_policy_obj->getMinimumWorkedPeriodDays(), __FILE__, __LINE__, __METHOD__, 10);
                        //Need to switch to weeks rather than days.
                        $calendar_date_range = array('start_date' => ($date_stamp - (86400 * (7 * $holiday_policy_obj->getMinimumWorkedPeriodDays()))), 'end_date' => ($date_stamp - 86400));
                    } else { //Calendar Days
                        $calendar_date_range = array('start_date' => ($date_stamp - (86400 * $holiday_policy_obj->getMinimumWorkedPeriodDays())), 'end_date' => ($date_stamp - 86400));
                    }

                    //Always get UserDateTotal data for the same date range so we can determine if they worked.
                    Debug::text('BEFORE: Getting data for calendar days! Start: ' . TTDate::getDate('DATE', $calendar_date_range['start_date']) . ' End: ' . TTDate::getDate('DATE', $calendar_date_range['end_date']), __FILE__, __LINE__, __METHOD__, 10);
                    $this->getUserDateTotalData($calendar_date_range['start_date'], $calendar_date_range['end_date']);
                    unset($calendar_date_range);
                }

                if ($holiday_policy_obj->getMinimumWorkedAfterDays() > 0 and $holiday_policy_obj->getMinimumWorkedAfterPeriodDays() > 0) {
                    if ($holiday_policy_obj->getWorkedAfterScheduledDays() == 1) { //Scheduled Days
                        Debug::text('AFTER: Using scheduled days!', __FILE__, __LINE__, __METHOD__, 10);

                        //Use 365days as the upper limit.
                        $this->getScheduleData(($date_stamp + 86400), time(), 10, $holiday_policy_obj->getMinimumWorkedAfterPeriodDays(), array('a.date_stamp' => 'asc'));

                        $scheduled_date_stamps_after = $this->getScheduleDates($this->filterScheduleDataByDateAndDirection($date_stamp, 10, 'asc', $holiday_policy_obj->getMinimumWorkedAfterPeriodDays()));
                        //Debug::Arr( (array)$scheduled_date_stamps_after, 'Scheduled DateStamps After: ', __FILE__, __LINE__, __METHOD__, 10);
                        Debug::Text('Scheduled DateStamps After: ' . count((array)$scheduled_date_stamps_after), __FILE__, __LINE__, __METHOD__, 10);

                        //Get the date range from the schedules dates that we found.
                        $calendar_date_range = $this->getDateRangeFromDateArray((array)$scheduled_date_stamps_after);
                    } elseif ($holiday_policy_obj->getWorkedScheduledDays() == 2) { //Holiday Week Days
                        //Need to switch to weeks rather than days.
                        $calendar_date_range = array('start_date' => ($date_stamp + 86400), 'end_date' => ($date_stamp + (86400 * (7 * $holiday_policy_obj->getMinimumWorkedAfterPeriodDays()))));
                    } else { //Calendar days
                        $calendar_date_range = array('start_date' => ($date_stamp + 86400), 'end_date' => ($date_stamp + (86400 * $holiday_policy_obj->getMinimumWorkedAfterPeriodDays())));
                    }

                    //Always get UserDateTotal data for the same date range so we can determine if they worked.
                    Debug::text('AFTER: Getting data for calendar days!', __FILE__, __LINE__, __METHOD__, 10);
                    $this->getUserDateTotalData($calendar_date_range['start_date'], $calendar_date_range['end_date']);
                    unset($calendar_date_range);
                }

                $worked_before_days_count = 0;
                if ($holiday_policy_obj->getMinimumWorkedDays() > 0 and $holiday_policy_obj->getMinimumWorkedPeriodDays() > 0 and isset($this->contributing_shift_policy[$holiday_policy_obj->getEligibleContributingShiftPolicy()])) {
                    if (isset($scheduled_date_stamps_before) and $holiday_policy_obj->getWorkedScheduledDays() == 1) { //Scheduled Days
                        $worked_before_days_count = count($this->getDayArrayUserDateTotalData($this->filterUserDateTotalDataByContributingShiftPolicy($scheduled_date_stamps_before, false, $this->contributing_shift_policy[$holiday_policy_obj->getEligibleContributingShiftPolicy()])));
                    } elseif ($holiday_policy_obj->getWorkedScheduledDays() == 2) {  //Holiday Week Days
                        //Start/End date should reflect weeks, no days here.
                        $worked_before_days_count = count($this->getDayArrayUserDateTotalData($this->filterUserDateTotalDataByContributingShiftPolicy(TTDate::getDateArray(($date_stamp - (($holiday_policy_obj->getMinimumWorkedPeriodDays() * 7) * 86400)), ($date_stamp - 86400), TTDate::getDayOfWeek($date_stamp)), false, $this->contributing_shift_policy[$holiday_policy_obj->getEligibleContributingShiftPolicy()])));
                    } else { //Calendar Days
                        $worked_before_days_count = count($this->getDayArrayUserDateTotalData($this->filterUserDateTotalDataByContributingShiftPolicy(($date_stamp - ($holiday_policy_obj->getMinimumWorkedPeriodDays() * 86400)), ($date_stamp - 86400), $this->contributing_shift_policy[$holiday_policy_obj->getEligibleContributingShiftPolicy()])));
                    }
                }
                Debug::text('Employee has worked the prior: ' . $worked_before_days_count . ' days (Must be at least: ' . $holiday_policy_obj->getMinimumWorkedDays() . ')', __FILE__, __LINE__, __METHOD__, 10);

                $worked_after_days_count = 0;
                if ($ignore_after_eligibility == true) {
                    $worked_after_days_count = $holiday_policy_obj->getMinimumWorkedAfterDays();
                    Debug::text('Ignoring worked after criteria...', __FILE__, __LINE__, __METHOD__, 10);
                } else {
                    if ($holiday_policy_obj->getMinimumWorkedAfterDays() > 0 and $holiday_policy_obj->getMinimumWorkedAfterPeriodDays() > 0 and isset($this->contributing_shift_policy[$holiday_policy_obj->getEligibleContributingShiftPolicy()])) {
                        if (isset($scheduled_date_stamps_after) and $holiday_policy_obj->getWorkedAfterScheduledDays() == 1) { //Scheduled Days
                            $worked_after_days_count = count($this->getDayArrayUserDateTotalData($this->filterUserDateTotalDataByContributingShiftPolicy($scheduled_date_stamps_after, false, $this->contributing_shift_policy[$holiday_policy_obj->getEligibleContributingShiftPolicy()])));
                        } elseif ($holiday_policy_obj->getWorkedScheduledDays() == 2) {  //Holiday Week Days
                            //Start/End date should reflect weeks, no days here.
                            $worked_after_days_count = count($this->getDayArrayUserDateTotalData($this->filterUserDateTotalDataByContributingShiftPolicy(TTDate::getDateArray(($date_stamp + 86400), ($date_stamp + (($holiday_policy_obj->getMinimumWorkedPeriodDays() * 7) * 86400)), TTDate::getDayOfWeek($date_stamp)), false, $this->contributing_shift_policy[$holiday_policy_obj->getEligibleContributingShiftPolicy()])));
                        } else { //Calendar Days
                            $worked_after_days_count = count($this->getDayArrayUserDateTotalData($this->filterUserDateTotalDataByContributingShiftPolicy(($date_stamp + 86400), ($date_stamp + ($holiday_policy_obj->getMinimumWorkedAfterPeriodDays() * 86400)), $this->contributing_shift_policy[$holiday_policy_obj->getEligibleContributingShiftPolicy()])));
                        }
                    }
                    Debug::text('Employee has worked the following: ' . $worked_after_days_count . ' days (Must be at least: ' . $holiday_policy_obj->getMinimumWorkedAfterDays() . ')', __FILE__, __LINE__, __METHOD__, 10);
                }

                //Make sure employee has worked for a portion of those days.
                if ($worked_before_days_count >= $holiday_policy_obj->getMinimumWorkedDays()
                    and $worked_after_days_count >= $holiday_policy_obj->getMinimumWorkedAfterDays()
                ) {
                    Debug::text('Employee has worked enough prior and following days!', __FILE__, __LINE__, __METHOD__, 10);
                    return true;
                } else {
                    Debug::text('Employee has NOT worked enough days prior or following the holiday!', __FILE__, __LINE__, __METHOD__, 10);
                    if ($worked_after_days_count < $holiday_policy_obj->getMinimumWorkedAfterDays()) {
                        $this->is_eligible_holiday_description = TTi18n::getText('Only worked %1 of the required %2 %3 following the holiday', array($worked_after_days_count, $holiday_policy_obj->getMinimumWorkedAfterDays(), strtolower(Option::getByKey($holiday_policy_obj->getWorkedAfterScheduledDays(), $holiday_policy_obj->getOptions('scheduled_day')))));
                    }
                    if ($worked_before_days_count < $holiday_policy_obj->getMinimumWorkedDays()) {
                        $this->is_eligible_holiday_description = TTi18n::getText('Only worked %1 of the required %2 %3 prior to the holiday', array($worked_before_days_count, $holiday_policy_obj->getMinimumWorkedDays(), strtolower(Option::getByKey($holiday_policy_obj->getWorkedScheduledDays(), $holiday_policy_obj->getOptions('scheduled_day')))));
                    }
                }
            } else {
                Debug::text('Standard Holiday Policy type, returning TRUE', __FILE__, __LINE__, __METHOD__, 10);
                return true;
            }
        } else {
            Debug::text('Employee has NOT been employed long enough!', __FILE__, __LINE__, __METHOD__, 10);
            $this->is_eligible_holiday_description = TTi18n::getText('Only employed %1 of the required %2 days', array(floor($current_employed_days), $holiday_policy_obj->getMinimumEmployedDays()));
        }

        Debug::text('Not eligible for holiday: ' . TTDate::getDate('DATE', $date_stamp), __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function getDayArrayUserDateTotalData($user_date_total_arr)
    {
        $days = array();
        if (is_array($user_date_total_arr)) {
            foreach ($user_date_total_arr as $udt_obj) {
                if ($udt_obj->getTotalTime() > 0) {
                    $days[] = $udt_obj->getDateStamp();
                }
            }
        }

        $days = array_unique($days);
        //Debug::Arr($days, 'Days with time: '. count($days), __FILE__, __LINE__, __METHOD__, 10);
        Debug::Text('Days with time: ' . count($days), __FILE__, __LINE__, __METHOD__, 10);
        return $days;
    }

    public function getScheduleDates($schedule_arr)
    {
        $retarr = array();
        if (is_array($schedule_arr)) {
            foreach ($schedule_arr as $s_obj) {
                $retarr[] = $s_obj->getDateStamp();
            }
        }
        Debug::text('Schedule Dates: ' . count($retarr), __FILE__, __LINE__, __METHOD__, 10);
        return $retarr;
    }

    public function filterScheduleDataByDateAndDirection($pivot_date = null, $status_ids = null, $direction = null, $limit = null)
    {
        $slf = $this->schedule;
        Debug::text('Pivot Date: ' . TTDate::getDate('DATE', $pivot_date) . ' Direction: ' . $direction . ' Limit: ' . $limit, __FILE__, __LINE__, __METHOD__, 10);
        if (is_array($slf) and count($slf) > 0) {
            $direction = strtolower($direction);

            if ($direction == 'desc') {
                uasort($slf, array($this, 'sortScheduleByDateDESC'));
            } else {
                uasort($slf, array($this, 'sortScheduleByDateASC'));
            }

            $pivot_date = TTDate::getMiddleDayEpoch($pivot_date); //Optimization - Move outside loop.
            foreach ($slf as $s_obj) {
                $s_obj_date_stamp = TTDate::getMiddleDayEpoch($s_obj->getDateStamp()); //Optimization - Move outside loop.
                if (($direction == 'desc' and $s_obj_date_stamp < $pivot_date)
                    or ($direction == 'asc' and $s_obj_date_stamp > $pivot_date)
                ) {
                    $retarr[$s_obj->getId()] = $s_obj;
                } else {
                    Debug::text('Scheduled shift does not match filter: ' . $s_obj->getID() . ' DateStamp: ' . TTDate::getDate('DATE', $s_obj->getDateStamp()) . ' Status: ' . $s_obj->getStatus(), __FILE__, __LINE__, __METHOD__, 10);
                }
            }

            if (isset($retarr)) {
                Debug::text('Found schedule rows matched filter: ' . count($retarr), __FILE__, __LINE__, __METHOD__, 10);
                return $retarr;
            }
        }

        Debug::text('No schedule rows match filter...', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function getHolidayTime($date_stamp, $holiday_policy_obj)
    {
        if ($holiday_policy_obj->getType() == 30 and isset($this->contributing_shift_policy[$holiday_policy_obj->getContributingShiftPolicy()])) { //Average
            if ($holiday_policy_obj->getMinimumTime() > 0
                and $holiday_policy_obj->getMaximumTime() > 0
                and $holiday_policy_obj->getMinimumTime() == $holiday_policy_obj->getMaximumTime()
            ) {
                Debug::text('Min and Max times are equal.', __FILE__, __LINE__, __METHOD__, 10);
                return $holiday_policy_obj->getMinimumTime();
            }

            $date_stamp = TTDate::getMiddleDayEpoch($date_stamp); //Optimization - Move outside loop.

            //Make sure we get all UserDateTotal data going back to the number of days to average the time over.
            $this->getUserDateTotalData(($date_stamp - (86400 * $this->holiday_before_days)), ($date_stamp - 86400));

            //Debug::text('Start Date: '. TTDate::getDate('DATE', ( $date_stamp - ( $holiday_policy_obj->getAverageTimeDays() * 86400) ) ) .' End: '. TTDate::getDate('DATE', ( $date_stamp - 86400 ) ), __FILE__, __LINE__, __METHOD__, 10);
            if ($holiday_policy_obj->getAverageTimeWorkedDays() == true) {
                $last_days_worked_count = count($this->getDayArrayUserDateTotalData($this->filterUserDateTotalDataByContributingShiftPolicy(($date_stamp - ($holiday_policy_obj->getAverageTimeDays() * 86400)), ($date_stamp - 86400), $this->contributing_shift_policy[$holiday_policy_obj->getContributingShiftPolicy()], array(20, 25, 30, 40, 100, 110)))); //Don't include Absence, Lunch, Break (Taken).
            } else {
                $last_days_worked_count = abs($holiday_policy_obj->getAverageDays()); //Allow -1 for getting time worked on the current day.
            }
            Debug::text('Average time over days: ' . $last_days_worked_count, __FILE__, __LINE__, __METHOD__, 10);

            if ($holiday_policy_obj->getAverageTimeDays() >= 0) {
                $total_seconds_worked = $this->getSumUserDateTotalData($this->filterUserDateTotalDataByContributingShiftPolicy(($date_stamp - ($holiday_policy_obj->getAverageTimeDays() * 86400)), ($date_stamp - 86400), $this->contributing_shift_policy[$holiday_policy_obj->getContributingShiftPolicy()], array(20, 25, 30, 40, 100, 110)));
            } elseif ($holiday_policy_obj->getAverageTimeDays() < 0) { //Allow setting a "Total Time Over" value to -1 along with Average Time Over: -1 to return the time worked on the holiday day itself.
                $total_seconds_worked = $this->getSumUserDateTotalData($this->filterUserDateTotalDataByContributingShiftPolicy($date_stamp, ($date_stamp + ((abs($holiday_policy_obj->getAverageTimeDays()) - 1) * 86400)), $this->contributing_shift_policy[$holiday_policy_obj->getContributingShiftPolicy()], array(10, 20, 25, 30, 40, 100, 110))); //Must include 10=Worked Time, as this happens before RegularTime/OverTime is calculated.
            }

            if ($last_days_worked_count > 0) {
                $avg_seconds_worked_per_day = bcdiv($total_seconds_worked, $last_days_worked_count);
                Debug::text('AVG hours worked per day: ' . TTDate::getHours($avg_seconds_worked_per_day), __FILE__, __LINE__, __METHOD__, 10);
            } else {
                $avg_seconds_worked_per_day = 0;
            }

            if ($holiday_policy_obj->getMaximumTime() > 0
                and $avg_seconds_worked_per_day > $holiday_policy_obj->getMaximumTime()
            ) {
                $avg_seconds_worked_per_day = $holiday_policy_obj->getMaximumTime();
                Debug::text('AVG hours worked per day exceeds maximum regulars hours per day, setting to:' . (($avg_seconds_worked_per_day / 60) / 60), __FILE__, __LINE__, __METHOD__, 10);
            }

            if ($avg_seconds_worked_per_day < $holiday_policy_obj->getMinimumTime()) {
                $avg_seconds_worked_per_day = $holiday_policy_obj->getMinimumTime();
                Debug::text('AVG hours worked per day is less then minimum regulars hours per day, setting to:' . (($avg_seconds_worked_per_day / 60) / 60), __FILE__, __LINE__, __METHOD__, 10);
            }

            //Round to nearest 15mins.
            if ((int)$holiday_policy_obj->getRoundIntervalPolicyID() != 0
                and is_object($holiday_policy_obj->getRoundIntervalPolicyObject())
            ) {
                $avg_seconds_worked_per_day = TTDate::roundTime($avg_seconds_worked_per_day, $holiday_policy_obj->getRoundIntervalPolicyObject()->getInterval(), $holiday_policy_obj->getRoundIntervalPolicyObject()->getRoundType());
                Debug::text('Rounding Stat Time To: ' . $avg_seconds_worked_per_day, __FILE__, __LINE__, __METHOD__, 10);
            } else {
                Debug::text('NOT Rounding Stat Time!', __FILE__, __LINE__, __METHOD__, 10);
            }

            return $avg_seconds_worked_per_day;
        } else {
            return $holiday_policy_obj->getMinimumTime();
        }
    }

    public function getSumUserDateTotalData($user_date_total_arr)
    {
        $sum = 0;
        if (is_array($user_date_total_arr)) {
            foreach ($user_date_total_arr as $udt_obj) {
                $sum += $udt_obj->getTotalTime();
            }
        }
        Debug::text('Sum Total: ' . $sum, __FILE__, __LINE__, __METHOD__, 10);
        return $sum;
    }

    public function calculateScheduleAbsence($date_stamp)
    {
        $slf = $this->filterScheduleDataByStatus($date_stamp, $date_stamp, 20);
        if (is_array($slf) and count($slf) > 0) {
            foreach ($slf as $key => $s_obj) {
                if ($s_obj->getStatus() == 20 and $s_obj->getAbsencePolicyID() > 0 and is_object($s_obj->getAbsencePolicyObject())) {
                    //Check for conflicting/overridden records, so we don't double up on the time.
                    //This is to allow users to enter a schedule shift for absence time, then override it to smaller number of hours.
                    //Only consider records using the same pay code though, so a user could have different absences on the same day
                    //like a "No Show/No Call" on a Stat holiday and still receive stat holiday time and the absence time.
                    if ($this->isConflictingUserDateTotal($date_stamp, array(25, 50), (int)$s_obj->getAbsencePolicyObject()->getPayCode())) {
                        continue;
                    }

                    if (!isset($this->user_date_total[$this->user_date_total_insert_id])) {
                        $udtf = TTnew('UserDateTotalFactory');
                        $udtf->setUser($this->getUserObject()->getId());
                        $udtf->setDateStamp($date_stamp);
                        $udtf->setObjectType(50); //Absence
                        $udtf->setSourceObject($s_obj->getAbsencePolicyID());
                        if (is_object($s_obj->getAbsencePolicyObject()) and is_object($s_obj->getAbsencePolicyObject()->getPayCode())) {
                            $udtf->setPayCode((int)$s_obj->getAbsencePolicyObject()->getPayCode());
                        }

                        $udtf->setBranch((int)$s_obj->getBranch());
                        $udtf->setDepartment((int)$s_obj->getDepartment());

                        $udtf->setTotalTime($s_obj->getTotalTime());

                        $udtf->setStartType(10); //Normal
                        $udtf->setEndType(10); //Normal

                        //Need to adjust the EndTimeStamp to account for auto-deducted meal/breaks, in a similar way as compactMealAndBreakUserDateTotalObjects() handles it.
                        //This is affects overtime calculations as they are strictly based on Start/End Times.
                        $udtf->setStartTimeStamp($s_obj->getStartTime());
                        $udtf->setEndTimeStamp(($s_obj->getStartTime() + $s_obj->getTotalTime()));

                        $udtf->setBaseHourlyRate($this->getBaseHourlyRate($s_obj->getAbsencePolicyObject()->getPayFormulaPolicy(), $udtf->getPayCode(), $date_stamp));
                        $udtf->setHourlyRate($this->getHourlyRate($s_obj->getAbsencePolicyObject()->getPayFormulaPolicy(), $udtf->getPayCode(), $date_stamp, $udtf->getBaseHourlyRate()));
                        $udtf->setHourlyRateWithBurden($this->getHourlyRateWithBurden($s_obj->getAbsencePolicyObject()->getPayFormulaPolicy(), $udtf->getPayCode(), $date_stamp, $udtf->getHourlyRate()));

                        $udtf->setEnableCalcSystemTotalTime(false);
                        $udtf->preSave(); //Call this so TotalTimeAmount is calculated immediately, as we don't save these records until later.

                        if ($this->isOverriddenUserDateTotalObject($udtf) == false) {
                            //Don't save the record, just add it to the existing array, so it can be included in other calculations.
                            //We will save these records at the end.
                            $this->user_date_total[$this->user_date_total_insert_id] = $udtf;
                            $this->user_date_total_insert_id--;
                        }
                        Debug::text('Found scheduled absence... Total Time: ' . $s_obj->getTotalTime(), __FILE__, __LINE__, __METHOD__, 10);
                    } else {
                        Debug::text('ERROR: Duplicate starting ID for some reason! ' . $this->user_date_total_insert_id, __FILE__, __LINE__, __METHOD__, 10);
                    }
                } else {
                    Debug::text('No absence policy specified in schedule.', __FILE__, __LINE__, __METHOD__, 10);
                }
            }

            $this->user_date_total = $this->sortUserDateTotalData($this->user_date_total); //Sort UDT records once done modifying them. This should help avoid having to sort them everytime we get/filter them.

            return true;
        }

        Debug::text('No scheduled absences to calculate...', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function calculateUnderTimeAbsencePolicy($date_stamp)
    {
        $undertime_absence_policy_arr = $this->filterUnderTimeAbsencePolicy($date_stamp);
        if (is_array($undertime_absence_policy_arr) and count($undertime_absence_policy_arr) > 0) {
            $schedule_daily_total_time = $this->getSumScheduleTime($this->filterScheduleDataByStatus($date_stamp, $date_stamp, array(10)));
            $worked_daily_total_time = $this->getSumUserDateTotalData($this->filterUserDateTotalDataByObjectTypeIDs($date_stamp, $date_stamp, array(10, 100, 110))); //Make sure we include paid/unpaid lunches/breaks.

            $total_under_time = ($schedule_daily_total_time - $worked_daily_total_time);
            Debug::text('Schedule Daily Total Time: ' . $schedule_daily_total_time . ' Worked Time: ' . $worked_daily_total_time . ' Total Under Time: ' . $total_under_time . ' Date: ' . TTDate::getDATE('DATE', $date_stamp), __FILE__, __LINE__, __METHOD__, 10);

            if ($worked_daily_total_time == 0 and isset($undertime_absence_policy_arr['full'])) {
                $ap_obj = $undertime_absence_policy_arr['full'];
                Debug::text(' Using Full Shift Undertime Absence Policy...', __FILE__, __LINE__, __METHOD__, 10);
            } elseif ($worked_daily_total_time > 0 and isset($undertime_absence_policy_arr['partial'])) {
                $ap_obj = $undertime_absence_policy_arr['partial'];
                Debug::text(' Using Partial Shift Undertime Absence Policy...', __FILE__, __LINE__, __METHOD__, 10);
            }

            if (isset($ap_obj) and is_object($ap_obj)) {
                //Make sure they have at least some worked time, as without any punch we can't match to what schedules should be applied. (Why do we need to match though?)
                //  If worked_time is 0, make sure date_stamp is after today before recording undertime absences,
                //    so they don't appear in the future when schedules are created, or on the current date before the employee has had a chance to punch in/out
                //  However if worked_time is 0, we need to have maintenance jobs calculate undertime absences at leach night, in cases where override schedules are entered in the future
                //    and no undertime is calculated immediately, then todays date catches up and undertime should now be calculated, only the maintenance job can trigger that.
                //
                //  Need to have a switch in the Schedule Policy that determines if we calculate undertime for Full Shifts Only, Partial Shifts Only, or Both Full and Partial Shifts.
                if ($total_under_time > 0
                    and (
                        $worked_daily_total_time > 0
                        or
                        ($worked_daily_total_time == 0 and TTDate::getMiddleDayEpoch($date_stamp) < TTDate::getMiddleDayEpoch(time()))
                    )
                ) {
                    //Check for conflicting/overridden records, so the user can override undertime absences and zero them out.
                    if ($this->isConflictingUserDateTotal($date_stamp, array(25, 50), (int)$ap_obj->getPayCode()) == false) {
                        if (!isset($this->user_date_total[$this->user_date_total_insert_id])) {
                            $udtf = TTnew('UserDateTotalFactory');
                            $udtf->setUser($this->getUserObject()->getId());
                            $udtf->setDateStamp($date_stamp);

                            //This was originally set to object_type_id=25, however that was inconsistent with holiday policies (50)
                            //and caused confusion from old versions that also showed undertime absences in the Absence section at the bottom
                            //Changed back to object_type_id=50 on 10-Apr-15 to be consistent with other policy absences.
                            //It also makes it easier to override it without having to use Accumulated Time functionality.
                            $udtf->setObjectType(50);
                            $udtf->setSourceObject((int)$ap_obj->getId());
                            $udtf->setPayCode((int)$ap_obj->getPayCode());

                            $udtf->setBranch($this->getUserObject()->getDefaultBranch());
                            $udtf->setDepartment($this->getUserObject()->getDefaultDepartment());
                            $udtf->setJob($this->getUserObject()->getDefaultJob());
                            $udtf->setJobItem($this->getUserObject()->getDefaultJobItem());

                            $udtf->setQuantity(0);
                            $udtf->setBadQuantity(0);
                            $udtf->setTotalTime($total_under_time);

                            //Base hourly rate on the regular wage
                            $udtf->setBaseHourlyRate($this->getBaseHourlyRate($ap_obj->getPayFormulaPolicy(), $udtf->getPayCode(), $date_stamp));
                            $udtf->setHourlyRate($this->getHourlyRate($ap_obj->getPayFormulaPolicy(), $udtf->getPayCode(), $date_stamp, $udtf->getBaseHourlyRate()));
                            $udtf->setHourlyRateWithBurden($this->getHourlyRateWithBurden($ap_obj->getPayFormulaPolicy(), $udtf->getPayCode(), $date_stamp, $udtf->getHourlyRate()));

                            $udtf->setEnableCalcSystemTotalTime(false);
                            $udtf->preSave(); //Call this so TotalTimeAmount is calculated immediately, as we don't save these records until later.

                            if ($this->isOverriddenUserDateTotalObject($udtf) == false) {
                                //Don't save the record, just add it to the existing array, so it can be included in other calculations.
                                //We will save these records at the end.
                                $this->user_date_total[$this->user_date_total_insert_id] = $udtf;

                                Debug::text(' Adding UDT row for UnderTime Absence Policy Time: ' . $total_under_time, __FILE__, __LINE__, __METHOD__, 10);
                                $this->user_date_total_insert_id--;
                            }

                            $this->user_date_total = $this->sortUserDateTotalData($this->user_date_total); //Sort UDT records once done modifying them. This should help avoid having to sort them everytime we get/filter them.

                            return true;
                        } else {
                            Debug::text('ERROR: Duplicate starting ID for some reason! ' . $this->user_date_total_insert_id, __FILE__, __LINE__, __METHOD__, 10);
                        }
                    } else {
                        Debug::text('Found absence taken that conflicts with undertime policy, skipping...', __FILE__, __LINE__, __METHOD__, 10);
                    }
                }
            }
        }

        Debug::text('No undertime absence policies to calculate...', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function filterUnderTimeAbsencePolicy($date_stamp)
    {
        if ((is_array($this->undertime_absence_policy) and count($this->undertime_absence_policy) > 0)) {
            $schedule_policy_arr = $this->filterSchedulePolicyByDate($date_stamp, true); //Force all scheduled shifts to be returned.
            if (is_array($schedule_policy_arr)) {
                foreach ($schedule_policy_arr as $sp_obj) {
                    if (!isset($retarr['partial']) and isset($this->undertime_absence_policy[$sp_obj->getPartialShiftAbsencePolicyID()])) {
                        Debug::text('  Found partial shift undertime absence policy...', __FILE__, __LINE__, __METHOD__, 10);
                        $retarr['partial'] = $this->undertime_absence_policy[$sp_obj->getPartialShiftAbsencePolicyID()];
                    }

                    if (!isset($retarr['full']) and isset($this->undertime_absence_policy[$sp_obj->getFullShiftAbsencePolicyID()])) {
                        Debug::text('  Found full shift undertime absence policy...', __FILE__, __LINE__, __METHOD__, 10);
                        $retarr['full'] = $this->undertime_absence_policy[$sp_obj->getFullShiftAbsencePolicyID()];
                    }

                    if (isset($retarr['partial']) and isset($retarr['full'])) {
                        break;
                    }
                }
            }
        }

        if (isset($retarr)) {
            return $retarr;
        }

        Debug::text('No partial/full shift undertime absence policies apply on date...', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function getSumScheduleTime($schedule_arr)
    {
        $sum = 0;
        if (is_array($schedule_arr)) {
            foreach ($schedule_arr as $s_obj) {
                $sum += $s_obj->getTotalTime();
            }
        }
        Debug::text('Sum Total: ' . $sum, __FILE__, __LINE__, __METHOD__, 10);
        return $sum;
    }

    public function calculateAbsenceTimePolicy($date_stamp)
    {
        $user_date_total_rows = $this->filterUserDateTotalDataByObjectTypeIDs($date_stamp, $date_stamp, array(50));
        if (is_array($user_date_total_rows)) {
            foreach ($user_date_total_rows as $udt_key => $udt_obj) {
                $ap_obj = $udt_obj->getSourceObjectObject();

                Debug::text('Generating UserDateTotal object from Absence Time Policy, ID: ' . $this->user_date_total_insert_id . ' Object Type ID: ' . 25 . ' Pay Code ID: ' . (int)$udt_obj->getPayCode() . ' Total Time: ' . $udt_obj->getTotalTime() . ' UDT Key: ' . $udt_key, __FILE__, __LINE__, __METHOD__, 10);
                if (is_object($ap_obj) and !isset($this->user_date_total[$this->user_date_total_insert_id])) {
                    $udtf = TTnew('UserDateTotalFactory');
                    $udtf->setUser($this->getUserObject()->getId());
                    $udtf->setDateStamp($date_stamp);
                    $udtf->setObjectType(25); //Absence Time
                    $udtf->setSourceObject((int)$udt_obj->getSourceObject());
                    $udtf->setPayCode($udt_obj->getPayCode());

                    $udtf->setBranch((int)$udt_obj->getBranch());
                    $udtf->setDepartment((int)$udt_obj->getDepartment());
                    $udtf->setJob((int)$udt_obj->getJob());
                    $udtf->setJobItem((int)$udt_obj->getJobItem());

                    $udtf->setQuantity(0);
                    $udtf->setBadQuantity(0);
                    $udtf->setTotalTime($udt_obj->getTotalTime());

                    //Need to carry-over start/end times from Absences taken, so getShiftStartAndEndUserDateTotal() can return absence shifts.
                    //and thereby apply schedule policies on just absence scheduled shifts.
                    if ($udt_obj->getStartTimeStamp() != '' and $udt_obj->getEndTimeStamp() != '') {
                        $udtf->setStartType($udt_obj->getStartType());
                        $udtf->setStartTimeStamp($udt_obj->getStartTimeStamp());
                        $udtf->setEndType($udt_obj->getEndType());
                        $udtf->setEndTimeStamp($udt_obj->getEndTimeStamp());
                    } else {
                        //Manual timesheet entry, set bogus start/end times that don't overlap any punches that may exist.
                        $udt_time_stamps = $this->calculateUserDateTotalStartAndEndTimeStamps($udt_obj);
                        $udtf->setStartType(10); //Normal
                        $udtf->setStartTimeStamp($udt_time_stamps['start_time_stamp']);
                        $udtf->setEndType(10); //Normal
                        $udtf->setEndTimeStamp($udt_time_stamps['end_time_stamp']);
                        unset($udt_time_stamps);
                        Debug::text('        No Start/End TimeStamps specified, setting temporary ones: Start: ' . TTDate::getDate('DATE+TIME', $udtf->getStartTimeStamp()) . ' End: ' . TTDate::getDate('DATE+TIME', $udtf->getEndTimeStamp()), __FILE__, __LINE__, __METHOD__, 10);
                    }

                    //Base hourly rate on the regular wage
                    $udtf->setBaseHourlyRate($this->getBaseHourlyRate($ap_obj->getPayFormulaPolicy(), $udt_obj->getPayCode(), $date_stamp));
                    $udtf->setHourlyRate($this->getHourlyRate($ap_obj->getPayFormulaPolicy(), $udtf->getPayCode(), $date_stamp, $udtf->getBaseHourlyRate()));
                    $udtf->setHourlyRateWithBurden($this->getHourlyRateWithBurden($ap_obj->getPayFormulaPolicy(), $udtf->getPayCode(), $date_stamp, $udtf->getHourlyRate()));

                    $udtf->setEnableCalcSystemTotalTime(false);
                    $udtf->preSave(); //Call this so TotalTimeAmount is calculated immediately, as we don't save these records until later.

                    if ($this->isOverriddenUserDateTotalObject($udtf) == false) {
                        //Don't save the record, just add it to the existing array, so it can be included in other calculations.
                        //We will save these records at the end.
                        $this->user_date_total[$this->user_date_total_insert_id] = $udtf;

                        //If the source Absence record (object_type_id=50) is linked to an accrual, exclude all other UDT records calculated
                        //from this one from also affecting the accrual.
                        $pay_formula_policy_obj = $this->getPayFormulaPolicyObjectByPolicyObject($ap_obj);
                        if ($this->isPayFormulaAccruing($pay_formula_policy_obj) == true) {
                            Debug::text('  Adding UDT Insert ID: ' . $this->user_date_total_insert_id . ' to Accrual Time Exclusivity Map...', __FILE__, __LINE__, __METHOD__, 10);
                            $this->accrual_time_exclusivity_map[$this->user_date_total_insert_id] = true;
                        }

                        $udt_used_keys[] = $udt_key;
                        $this->user_date_total_insert_id--;
                    }
                } else {
                    Debug::text('ERROR: Duplicate starting ID for some reason! ' . $this->user_date_total_insert_id, __FILE__, __LINE__, __METHOD__, 10);
                }
            }

            Debug::text('Done with absence time policies to calculate...', __FILE__, __LINE__, __METHOD__, 10);

            $this->user_date_total = $this->sortUserDateTotalData($this->user_date_total); //Sort UDT records once done modifying them. This should help avoid having to sort them everytime we get/filter them.

            return true;
        }

        Debug::text('No absence time policies to calculate...', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function calculateUserDateTotalStartAndEndTimeStamps($udt_obj)
    {
        //Try to fit time stamps in with schedule and existing punches as best as possible.
        if ($udt_obj->getObjectType() == 50) {
            //Absences with punches should be at the beginning of the day to avoid them always being OT.
            if ($this->prev_user_date_total_start_time_stamp == null) {
                $plf = $this->filterPunchDataByDateAndTypeAndStatus($udt_obj->getDateStamp());
                if (is_array($plf) and count($plf) > 0) {
                    //Get first punch timestamp and start counting backwards from there.
                    foreach ($plf as $p_obj) {
                        $first_punch_time_stamp = $p_obj->getTimeStamp();
                        break;
                    }

                    Debug::text('    First Punch TimeStamp: ' . TTDate::getDate('DATE+TIME', $first_punch_time_stamp), __FILE__, __LINE__, __METHOD__, 10);
                    $end_time_stamp = $first_punch_time_stamp;
                    $start_time_stamp = ($end_time_stamp - $udt_obj->getTotalTime());
                } else {
                    //If no punches exist, try to match the timestamps up with their first scheduled shift.
                    $slf = $this->filterScheduleDataByStatus($udt_obj->getDateStamp(), $udt_obj->getDateStamp(), 10); //Working
                    if (is_array($slf) and count($slf) > 0) {
                        foreach ($slf as $s_obj) {
                            $first_schedule_start_time_stamp = $s_obj->getStartTime();
                            break;
                        }

                        Debug::text('    First Schedule TimeStamp: ' . TTDate::getDate('DATE+TIME', $first_schedule_start_time_stamp), __FILE__, __LINE__, __METHOD__, 10);
                        $end_time_stamp = $first_schedule_start_time_stamp;
                        $start_time_stamp = ($end_time_stamp - $udt_obj->getTotalTime());
                    } else {
                        $start_time_stamp = TTDate::getBeginDayEpoch($udt_obj->getDateStamp());
                        $end_time_stamp = ($start_time_stamp + $udt_obj->getTotalTime());
                    }
                }
            } else {
                $end_time_stamp = $this->prev_user_date_total_start_time_stamp;
                $start_time_stamp = ($end_time_stamp - $udt_obj->getTotalTime());
            }

            $this->prev_user_date_total_start_time_stamp = $start_time_stamp;
        } else {
            //Non-absences (manual timesheet entries) should go at the end of the day, after punches.
            if ($this->prev_user_date_total_end_time_stamp == null) {
                $plf = $this->filterPunchDataByDateAndTypeAndStatus($udt_obj->getDateStamp());
                if (is_array($plf) and count($plf) > 0) {
                    //Get last punch timestamp and start counting from there.
                    foreach ($plf as $p_obj) {
                        $last_punch_time_stamp = $p_obj->getTimeStamp();
                    }

                    Debug::text('    Last Punch TimeStamp: ' . TTDate::getDate('DATE+TIME', $last_punch_time_stamp), __FILE__, __LINE__, __METHOD__, 10);
                    $start_time_stamp = $last_punch_time_stamp;
                    $end_time_stamp = ($start_time_stamp + $udt_obj->getTotalTime());
                } else {
                    //If no punches exist, try to match the timestamps up with their first scheduled shift.
                    $slf = $this->filterScheduleDataByStatus($udt_obj->getDateStamp(), $udt_obj->getDateStamp(), 10); //Working
                    if (is_array($slf) and count($slf) > 0) {
                        foreach ($slf as $s_obj) {
                            $first_schedule_start_time_stamp = $s_obj->getStartTime();
                            break;
                        }

                        Debug::text('    First Schedule TimeStamp: ' . TTDate::getDate('DATE+TIME', $first_schedule_start_time_stamp), __FILE__, __LINE__, __METHOD__, 10);
                        $start_time_stamp = $first_schedule_start_time_stamp;
                        $end_time_stamp = ($start_time_stamp + $udt_obj->getTotalTime());
                    } else {
                        $start_time_stamp = TTDate::getBeginDayEpoch($udt_obj->getDateStamp());
                        $end_time_stamp = ($start_time_stamp + $udt_obj->getTotalTime());
                    }
                }
            } else {
                $start_time_stamp = $this->prev_user_date_total_end_time_stamp;
                $end_time_stamp = ($start_time_stamp + $udt_obj->getTotalTime());
            }

            $this->prev_user_date_total_end_time_stamp = $end_time_stamp;
        }

        $retarr = array('start_time_stamp' => $start_time_stamp, 'end_time_stamp' => $end_time_stamp);
        Debug::text('  Start: ' . TTDate::getDate('DATE+TIME', $start_time_stamp) . ' End: ' . TTDate::getDate('DATE+TIME', $end_time_stamp) . ' Object Type: ' . $udt_obj->getObjectType(), __FILE__, __LINE__, __METHOD__, 10);

        return $retarr;
    }

    public function filterPunchDataByDateAndTypeAndStatus($date_stamp, $type_ids = null, $status_ids = null)
    {
        $plf = $this->punch;
        Debug::text('Date: ' . TTDate::getDate('DATE', $date_stamp), __FILE__, __LINE__, __METHOD__, 10);
        if (is_array($plf) and count($plf) > 0) {
            $date_stamp = TTDate::getMiddleDayEpoch($date_stamp); //Optimization - Move outside loop.
            foreach ($plf as $p_obj) {
                //TTDate::getMiddleDayEpoch( $p_obj->getTimeStamp() ) == TTDate::getMiddleDayEpoch( $date_stamp )
                if (TTDate::getMiddleDayEpoch(TTDate::strtotime($p_obj->getColumn('date_stamp'))) == $date_stamp
                    and ($type_ids == null or in_array($p_obj->getType(), (array)$type_ids))
                    and ($status_ids == null or in_array($p_obj->getStatus(), (array)$status_ids))
                ) {
                    $retarr[$p_obj->getId()] = $p_obj;
                }
                //else {
                //Debug::text('Punch does not match filter: '. $p_obj->getID() .' DateStamp: '. TTDate::getDate('DATE', $p_obj->getTimeStamp() ) .' Status: '. $p_obj->getStatus() .' Type: '. $p_obj->getType(), __FILE__, __LINE__, __METHOD__, 10);
                //}
            }

            if (isset($retarr)) {
                Debug::text('Found punch rows matched filter: ' . count($retarr), __FILE__, __LINE__, __METHOD__, 10);
                return $retarr;
            }
        }

        Debug::text('No punch rows match filter...', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function getPayFormulaPolicyObjectByPolicyObject($obj)
    {
        if (is_object($obj)) {
            if (method_exists($obj, 'getPayFormulaPolicy') and $obj->getPayFormulaPolicy() > 0 and isset($this->pay_formula_policy[$obj->getPayFormulaPolicy()])) {
                return $this->pay_formula_policy[$obj->getPayFormulaPolicy()];
            } elseif (method_exists($obj, 'getPayCode') and $obj->getPayCode() > 0 and isset($this->pay_codes[$obj->getPayCode()]) and isset($this->pay_formula_policy[$this->pay_codes[$obj->getPayCode()]->getPayFormulaPolicy()])) {
                return $this->pay_formula_policy[$this->pay_codes[$obj->getPayCode()]->getPayFormulaPolicy()];
            } elseif ($obj->getID() > 0 and isset($this->pay_codes[$obj->getID()]) and isset($this->pay_formula_policy[$this->pay_codes[$obj->getID()]->getPayFormulaPolicy()])) {
                //This if the user is editing UDT records directly and no src_object_id is specified, but a pay code is, so the object is PayCodeFactory or PayCodeListFactory.
                return $this->pay_formula_policy[$this->pay_codes[$obj->getID()]->getPayFormulaPolicy()];
            }
        }

        Debug::text('No pay formula policy assigned...', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function isPayFormulaAccruing($pay_formula_policy_obj)
    {
        if (is_object($pay_formula_policy_obj) and $pay_formula_policy_obj->getAccrualPolicyAccount() > 0 and $pay_formula_policy_obj->getAccrualRate() != 0) {
            return true;
        }

        return false;
    }

    public function calculateRegularTimePolicy($date_stamp, $maximum_daily_total_time = null)
    {
        //Since other policies such as OT need to be able to calculate hourly rates based on regular time.
        //We need to assign *all* worked time+meal/break+absence to regular time policies.
        //  Then when OT policies are calculated, they don't need to use worked time at all, and can reduce the regular time policy time by whatever is converted to OT.

        //Loop over all regular time policies calculating the pay codes, up until $maximum_time is reached.
        $regular_time_policies = $this->filterRegularTimePolicy($date_stamp);
        $total_regular_time_policies = count($regular_time_policies);
        if (is_array($regular_time_policies) and $total_regular_time_policies > 0) {
            //Don't set an upper limit on the regular time, as we have to account for worked, absence time, but not always in every situation
            //So we can't reliably calculate the upper limit. As long as we don't calculate policies on source UDT rows multiple times we should be fine.
            //$maximum_time = $maximum_daily_total_time;
            $maximum_time = 0;
            Debug::text('Maximum Possible Regular Time: ' . $maximum_time, __FILE__, __LINE__, __METHOD__, 10);

            $udt_used_keys = array();

            $covered_time = 0;
            $break_loop = false;
            for ($i = 0; $i <= $total_regular_time_policies; $i++) {
                if ($i == $total_regular_time_policies) {
                    //Don't set an upper limit on the regular time, as we have to account for worked, absence time, but not always in every situation
                    //So we can't reliably calculate the upper limit. As long as we don't calculate policies on source UDT rows multiple times we should be fine.
                    //continue;

                    Debug::text('Reached last row, apply catch all: ' . $maximum_time . ' I: ' . $i . ' Total Reg Policies: ' . $total_regular_time_policies, __FILE__, __LINE__, __METHOD__, 10);
                    $rtp_obj = end($regular_time_policies);

                    if (!isset($this->contributing_shift_policy[$rtp_obj->getContributingShiftPolicy()])) {
                        Debug::text(' ERROR: Contributing Shift Policy for RegularTime Policy: ' . $rtp_obj->getName() . ' does not exist...', __FILE__, __LINE__, __METHOD__, 10);
                        continue;
                    }

                    //Haven't used all worked time, so use all worked time as the input for the last policy, regardless of what the contributing shift policy says.
                    //Should this be the regular time policy with the highest calculation order assigned to this employee/day, or highest for the entire company?
                    //Should the time go to pay_code_id = 0 using a special name like "Regular Time (Catch All)" with 0 rate of pay? That way its easy to figure out the issue
                    //  and correct by simply making a regular time policy that includes all worked time? (which would be there by default anyways)
                    // Don't include Absence Time (25) in here, otherwise it will always override absence time as its exclusive.
                    //$user_date_total_rows = $this->filterUserDateTotalDataByObjectTypeIDs( $date_stamp, $date_stamp, array( 10, 100, 110 ) );

                    //Still need to include meal/break policy time, in case they want to include it in regular time or not.
                    $user_date_total_rows = $this->compactMealAndBreakUserDateTotalObjects($this->filterUserDateTotalDataByContributingShiftPolicy($date_stamp, $date_stamp, $this->contributing_shift_policy[$rtp_obj->getContributingShiftPolicy()], array(10, 100, 110)));
                } else {
                    $rtp_obj = current($regular_time_policies);

                    if (!isset($this->contributing_shift_policy[$rtp_obj->getContributingShiftPolicy()])) {
                        Debug::text(' ERROR: Contributing Shift Policy for RegularTime Policy: ' . $rtp_obj->getName() . ' does not exist...', __FILE__, __LINE__, __METHOD__, 10);
                        continue;
                    }

                    //Do we include just Worked Time in the calculation, or do we somehow handle Contributing Shift Policies?
                    //Regular Time should be exclusive to itself, so we can't calculate regular time on top of regular time.
                    //Only include worked time in regular time calculation, then overtime and premium will not include regular time.
                    $user_date_total_rows = $this->compactMealAndBreakUserDateTotalObjects($this->filterUserDateTotalDataByContributingShiftPolicy($date_stamp, $date_stamp, $this->contributing_shift_policy[$rtp_obj->getContributingShiftPolicy()], array(10, 25, 100, 110)));
                }
                Debug::text('I: ' . $i . ' Regular Time Policy: ' . $rtp_obj->getName() . ' Pay Code: ' . $rtp_obj->getPayCode(), __FILE__, __LINE__, __METHOD__, 10);

                if (is_array($user_date_total_rows) and count($user_date_total_rows) > 0) {
                    foreach ($user_date_total_rows as $udt_key => $udt_obj) {
                        Debug::text('Regular Time Policy: ' . $rtp_obj->getName() . ' ID: ' . $udt_obj->getID() . ' Time: ' . $udt_obj->getTotalTime() . ' Pay Code: ' . $rtp_obj->getPayCode() . ' Quantity: ' . $udt_obj->getQuantity() . ' Bad Quantity: ' . $udt_obj->getBadQuantity() . ' Used Regular Time: ' . $covered_time . ' Maximum Time: ' . $maximum_time, __FILE__, __LINE__, __METHOD__, 10);
                        if (in_array($udt_key, $udt_used_keys)) {
                            Debug::text('UDT row already used in another regular time policy! ID: ' . $udt_key, __FILE__, __LINE__, __METHOD__, 10);
                            continue;
                        }

                        $create_udt_record = true;

                        if ($create_udt_record == true) {
                            //No need to pro-rate regular time, as calculating regular/overtime exclusivity will handle this.
                            if ($maximum_time > 0 and ($covered_time + $udt_obj->getTotalTime()) > $maximum_time) {
                                $total_time = ($maximum_time - $covered_time);

                                $total_time_percent = ($total_time / $udt_obj->getTotalTime());
                                $quantity = round(($udt_obj->getQuantity() * $total_time_percent), 2);
                                $bad_quantity = round(($udt_obj->getBadQuantity() * $total_time_percent), 2);
                                $break_loop = true;
                                Debug::text('  Reached maximum time, calculate percent of quantities... Used Regular Time: ' . $covered_time . ' Percent: ' . $total_time_percent, __FILE__, __LINE__, __METHOD__, 10);
                                Debug::text('  Percent Calculated: ID: ' . $udt_obj->getID() . ' Time: ' . $total_time . ' Pay Code: ' . $rtp_obj->getPayCode() . ' Quantity: ' . $quantity . ' Bad Quantity: ' . $bad_quantity, __FILE__, __LINE__, __METHOD__, 10);

                                unset($total_time_percent);
                            } else {
                                $total_time = $udt_obj->getTotalTime();
                                $quantity = $udt_obj->getQuantity();
                                $bad_quantity = $udt_obj->getBadQuantity();
                            }

                            //Can't compact the data here, as that won't allow us to reference (pyramid) the time as each policy total time is calculated.
                            //We will need to create the UserDateTotal objects, then compact them just before inserting...
                            Debug::text('Generating UserDateTotal object from Regular Time Policy, ID: ' . $this->user_date_total_insert_id . ' Object Type ID: ' . 20 . ' Pay Code ID: ' . (int)$rtp_obj->getPayCode() . ' Total Time: ' . $total_time, __FILE__, __LINE__, __METHOD__, 10);
                            if (!isset($this->user_date_total[$this->user_date_total_insert_id])) {
                                $udtf = TTnew('UserDateTotalFactory');
                                $udtf->setUser($this->getUserObject()->getId());
                                $udtf->setDateStamp($date_stamp);
                                $udtf->setObjectType(20); //Regular Time
                                $udtf->setSourceObject((int)$rtp_obj->getId());
                                if ($i == $total_regular_time_policies) {
                                    $udtf->setPayCode(0);
                                } else {
                                    $udtf->setPayCode((int)$rtp_obj->getPayCode());
                                }

                                $udtf->setBranch((int)$udt_obj->getBranch());
                                $udtf->setDepartment((int)$udt_obj->getDepartment());
                                $udtf->setJob((int)$udt_obj->getJob());
                                $udtf->setJobItem((int)$udt_obj->getJobItem());

                                if ($udt_obj->getStartTimeStamp() != '' and $udt_obj->getEndTimeStamp() != '') {
                                    $udtf->setStartType($udt_obj->getStartType());
                                    $udtf->setStartTimeStamp($udt_obj->getStartTimeStamp());
                                    $udtf->setEndType($udt_obj->getEndType());
                                    $udtf->setEndTimeStamp($udt_obj->getEndTimeStamp());
                                } else {
                                    //Manual timesheet entry, set bogus start/end times that don't overlap any punches that may exist.
                                    $udt_time_stamps = $this->calculateUserDateTotalStartAndEndTimeStamps($udt_obj);
                                    $udtf->setStartType(10); //Normal
                                    $udtf->setStartTimeStamp($udt_time_stamps['start_time_stamp']);
                                    $udtf->setEndType(10); //Normal
                                    $udtf->setEndTimeStamp($udt_time_stamps['end_time_stamp']);
                                    unset($udt_time_stamps);
                                    Debug::text('        No Start/End TimeStamps specified, setting temporary ones: Start: ' . TTDate::getDate('DATE+TIME', $udtf->getStartTimeStamp()) . ' End: ' . TTDate::getDate('DATE+TIME', $udtf->getEndTimeStamp()), __FILE__, __LINE__, __METHOD__, 10);
                                }

                                $udtf->setQuantity($quantity);
                                $udtf->setBadQuantity($bad_quantity);
                                $udtf->setTotalTime($total_time);

                                $udtf->setBaseHourlyRate($this->getBaseHourlyRate($rtp_obj->getPayFormulaPolicy(), $udtf->getPayCode(), $date_stamp, $udt_obj->getHourlyRate()));
                                $udtf->setHourlyRate($this->getHourlyRate($rtp_obj->getPayFormulaPolicy(), $udtf->getPayCode(), $date_stamp, $udtf->getBaseHourlyRate()));
                                $udtf->setHourlyRateWithBurden($this->getHourlyRateWithBurden($rtp_obj->getPayFormulaPolicy(), $udtf->getPayCode(), $date_stamp, $udtf->getHourlyRate()));

                                $udtf->setEnableCalcSystemTotalTime(false);
                                $udtf->preSave(); //Call this so TotalTimeAmount is calculated immediately, as we don't save these records until later.

                                if ($this->isOverriddenUserDateTotalObject($udtf) == false) {
                                    //Don't save the record, just add it to the existing array, so it can be included in other calculations.
                                    //We will save these records at the end.
                                    $this->user_date_total[$this->user_date_total_insert_id] = $udtf;

                                    //Track the regular/absence exclusivity adjustments by linking the two records together.
                                    //Then once all regular time is calculated the absence time can be reduced accordingly.
                                    $this->regular_time_exclusivity_map[] = array($udt_key => $this->user_date_total_insert_id);
                                    Debug::text('        Queuing reduction of Absence UDT Key: ' . $udt_key . '(' . $this->user_date_total_insert_id . ') from: ' . $udt_obj->getTotalTime() . ' to: ' . ($udt_obj->getTotalTime() - $total_time) . ' Total: ' . $udtf->getTotalTime(), __FILE__, __LINE__, __METHOD__, 10);

                                    //Check to see if the source UDT record will already be calculating accruals, if so, skip calculating any accruals for this UDT record too.
                                    if (isset($this->accrual_time_exclusivity_map[$udt_key])) {
                                        Debug::text('  Adding UDT Insert ID: ' . $this->user_date_total_insert_id . ' to Accrual Time Exclusivity Map...', __FILE__, __LINE__, __METHOD__, 10);
                                        $this->accrual_time_exclusivity_map[$this->user_date_total_insert_id] = true;
                                    }

                                    $this->user_date_total_insert_id--;
                                }
                                $udt_used_keys[] = $udt_key; //Always run this to prevent getting to the catch-all when override records exist.
                            } else {
                                Debug::text('ERROR: Duplicate starting ID for some reason! ' . $this->user_date_total_insert_id, __FILE__, __LINE__, __METHOD__, 10);
                            }

                            $covered_time += $total_time;

                            if ($break_loop === true) {
                                break 2;
                            }
                        }
                    }
                }

                next($regular_time_policies);
            }

            $this->user_date_total = $this->sortUserDateTotalData($this->user_date_total); //Sort UDT records once done modifying them. This should help avoid having to sort them everytime we get/filter them.

            return true;
        }

        Debug::text('No regular time policies to calculate...', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function filterRegularTimePolicy($date_stamp)
    {
        $rtplf = $this->regular_time_policy;
        if (is_array($rtplf) and count($rtplf) > 0) {
            $schedule_policy_regular_time_policy_ids = array();
            $schedule_policy_exclude_regular_time_policy_ids = array();
            $schedule_policy_arr = $this->filterSchedulePolicyByDate($date_stamp);
            if (is_array($schedule_policy_arr)) {
                foreach ($schedule_policy_arr as $sp_obj) {
                    if (is_array($sp_obj->getIncludeRegularTimePolicy()) and count($sp_obj->getIncludeRegularTimePolicy()) > 0) {
                        $schedule_policy_regular_time_policy_ids = array_merge($schedule_policy_regular_time_policy_ids, (array)$sp_obj->getIncludeRegularTimePolicy());
                    }
                    if (is_array($sp_obj->getExcludeRegularTimePolicy()) and count($sp_obj->getExcludeRegularTimePolicy()) > 0) {
                        $schedule_policy_exclude_regular_time_policy_ids = array_merge($schedule_policy_exclude_regular_time_policy_ids, (array)$sp_obj->getExcludeRegularTimePolicy());
                    }
                }
                Debug::Arr($schedule_policy_regular_time_policy_ids, 'Regular Time Policies that apply to: ' . TTDate::getDate('DATE', $date_stamp) . ' from schedule policies: ', __FILE__, __LINE__, __METHOD__, 10);
            }

            foreach ($rtplf as $rtp_obj) {
                //FIXME: Check contributing shift start/end date so we can quickly filter out regular time policies that may never apply. Similar to what we do with premium policies.
                //FIXME: There is a bug that if a Schedule Policy includes the same Regular Time policy that is included in the Policy Group (included twice essentially), even if its not used in the employees scheduled shift, it will prevent that Regular Time policy from being used.
                //       because is_policy_group=0 and it won't be in the schedule_policy_regular_time_policy_ids.
                if (
                (
                    ((int)$rtp_obj->getColumn('is_policy_group') == 1 and !in_array($rtp_obj->getId(), $schedule_policy_exclude_regular_time_policy_ids))
                    or
                    ((int)$rtp_obj->getColumn('is_policy_group') == 0 and in_array($rtp_obj->getId(), $schedule_policy_regular_time_policy_ids))
                )
                ) {
                    $retarr[$rtp_obj->getId()] = $rtp_obj;
                }
            }

            if (isset($retarr)) {
                //Since we have included/excluded additional policies, we need to resort them again so they are in the proper order.
                uasort($retarr, array($this, 'RegularTimePolicySortByCalculationOrderAsc'));

                Debug::text('Found regular time policies that apply on date: ' . count($retarr), __FILE__, __LINE__, __METHOD__, 10);

                return $retarr;
            }
        }

        Debug::text('No regular time policies apply on date...', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function compactMealAndBreakUserDateTotalObjects($user_date_total_rows)
    {
        if (is_array($user_date_total_rows)) {
            $tmp_user_date_total_rows = $user_date_total_rows;
            Debug::Text('Total Records: ' . count($user_date_total_rows), __FILE__, __LINE__, __METHOD__, 10);

            $processed_keys = array();
            $cloned_keys = array();

            //Check for Meal/Break object_types (100, 110)
            //Each record should correspond directly with another different object type, find that record and adjust it accordingly.
            //
            //Need to make sure cases where meal and breaks are auto-deducted from the same single Regular Time record.
            //
            foreach ($user_date_total_rows as $key => $udt_obj) {
                if ($udt_obj->getObjectType() == 100 or $udt_obj->getObjectType() == 110) {
                    Debug::Text('Found Meal/Break record... Key: ' . $key . ' Total Time: ' . $udt_obj->getTotalTime(), __FILE__, __LINE__, __METHOD__, 10);
                    foreach ($tmp_user_date_total_rows as $tmp_key => $tmp_udt_obj) {
                        if (!isset($processed_keys[$udt_obj->getSourceObject()][$tmp_key])
                            and !in_array($tmp_udt_obj->getObjectType(), array(100, 101, 110, 111))
                            and $udt_obj->getBranch() == $tmp_udt_obj->getBranch()
                            and $udt_obj->getDepartment() == $tmp_udt_obj->getDepartment()
                            and $udt_obj->getJob() == $tmp_udt_obj->getJob()
                            and $udt_obj->getJobItem() == $tmp_udt_obj->getJobItem()
                            and ($udt_obj->getTotalTime() < 0 and $tmp_udt_obj->getTotalTime() >= abs($udt_obj->getTotalTime()))
                        ) {
                            Debug::Text('  Found Corresponding record: ' . $tmp_key . ' Object Type: ' . $tmp_udt_obj->getObjectType() . ' Pay Code: ' . $tmp_udt_obj->getPayCode() . ' Total Time: ' . $tmp_udt_obj->getTotalTime(), __FILE__, __LINE__, __METHOD__, 10);

                            //Adjust corresponding record

                            //Don't clone the object if its already been cloned once,
                            //because we need to handle cases where both meal and breaks are auto-deducted from the same Regular Time record
                            //This can be replicated with having a single punch pair and meal&break auto-deduct policies applying.
                            if (!isset($cloned_keys[$tmp_key])) {
                                $user_date_total_rows[$tmp_key] = clone $tmp_udt_obj; //Clone the object so we don't modify the original one.
                                $cloned_keys[$tmp_key] = true;
                            }
                            $user_date_total_rows[$tmp_key]->setTotalTime(($user_date_total_rows[$tmp_key]->getTotalTime() + $udt_obj->getTotalTime()));
                            if ($user_date_total_rows[$tmp_key]->getEndTimeStamp() != '') {
                                $user_date_total_rows[$tmp_key]->setEndTimeStamp(($user_date_total_rows[$tmp_key]->getEndTimeStamp() + $udt_obj->getTotalTime()));
                            }

                            Debug::Text('  New Total Time: ' . $user_date_total_rows[$tmp_key]->getTotalTime() . ' New End Stamp: ' . TTDate::getDate('DATE+TIME', $user_date_total_rows[$tmp_key]->getEndTimeStamp()), __FILE__, __LINE__, __METHOD__, 10);

                            //Remove original meal/break row.
                            unset($user_date_total_rows[$key]);

                            //Mark record as processed so we don't process it again.
                            $processed_keys[$udt_obj->getSourceObject()][$tmp_key] = true;
                            break;
                        } else {
                            Debug::Text('  Skipping UDT row, likely because it has already been processed... Key: ' . $tmp_key, __FILE__, __LINE__, __METHOD__, 10);
                        }
                    }
                }
                //else {
                //	Debug::Text('  Skipping non-Meal/Break row... Key: '. $key, __FILE__, __LINE__, __METHOD__, 10);
                //}
            }

            //Debug::Text('Done compacting... Total Records: '. count($user_date_total_rows), __FILE__, __LINE__, __METHOD__, 10);
            unset($tmp_user_date_total_rows, $udt_obj, $tmp_udt_obj, $processed_keys);
            return $user_date_total_rows;
        }

        Debug::Text('No data to compact...', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function calculateRegularTimeExclusivity()
    {
        //Loop through the exclusivity map and reduce regular time records by the amount of the linked absence record.
        if (is_array($this->regular_time_exclusivity_map) and count($this->regular_time_exclusivity_map) > 0) {
            foreach ($this->regular_time_exclusivity_map as $exclusivity_data) {
                foreach ($exclusivity_data as $regular_udt_key => $reg_udt_key) {
                    //Debug::text('Regular UDT Key '. $regular_udt_key .' Absence Key: '. $reg_udt_key, __FILE__, __LINE__, __METHOD__, 10);
                    if (isset($this->user_date_total[$regular_udt_key]) and isset($this->user_date_total[$reg_udt_key])) {
                        $udt_obj = $this->user_date_total[$regular_udt_key];
                        $reg_udt_obj = $this->user_date_total[$reg_udt_key];

                        Debug::text('Absence UDT Total Time: ' . $udt_obj->getTotalTime() . '(' . $regular_udt_key . ') Regular Total Time: ' . $reg_udt_obj->getTotalTime() . '(' . $reg_udt_key . ')', __FILE__, __LINE__, __METHOD__, 10);
                        if ($udt_obj->getObjectType() == 25) { //Regular Time or Absence
                            $udt_obj->setTotalTime(($udt_obj->getTotalTime() - $reg_udt_obj->getTotalTime()));
                            $udt_obj->setQuantity(($udt_obj->getQuantity() - $reg_udt_obj->getQuantity()));
                            $udt_obj->setBadQuantity(($udt_obj->getBadQuantity() - $reg_udt_obj->getBadQuantity()));

                            if ($udt_obj->getEndTimeStamp() != '') {
                                $udt_obj->setEndTimeStamp(($udt_obj->getEndTimeStamp() - $reg_udt_obj->getTotalTime()));
                            }

                            $udt_obj->preSave(); //Calculate TotalTimeAmount.
                            Debug::text('  Reducing Absence Time to: ' . $udt_obj->getTotalTime(), __FILE__, __LINE__, __METHOD__, 10);
                        } else {
                            Debug::text('WARNING: UDT Records isnt absence time, unable to adjust for exclusivity. Object Type: ' . $udt_obj->getObjectType() . ' Pay Code: ' . $udt_obj->getPayCode(), __FILE__, __LINE__, __METHOD__, 10);
                        }
                    } else {
                        Debug::text('ERROR: UDT Records dont exist, unable to adjust for exclusivity.', __FILE__, __LINE__, __METHOD__, 10);
                    }
                }
            }
            unset($udt_obj, $reg_udt_obj);

            $this->regular_time_exclusivity_map = null; //Make sure this reset each time this is run.

            return true;
        }

        Debug::text('No exclusivity records to calculate!', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    //Returns shift data according to the pay period schedule criteria for use in determining which day punches belong to.

    public function calculateOverTimePolicy($date_stamp, $trigger_time_arr, $maximum_daily_total_time = null)
    {
        //1. Loop through each trigger_time_arr, as that will contain all the overtime policies that should apply to this date.
        $total_over_time_policies = count($trigger_time_arr);
        if ($total_over_time_policies > 0 and is_array($trigger_time_arr) and count($trigger_time_arr)) {
            $prev_policy_data = false;

            $this->over_time_trigger_time_exclusivity_map = array();
            $this->over_time_recurse_map = array();
            $trigger_time_arr_keys = array_keys($trigger_time_arr);

            //Determine if there are any OT policies with differential criteria. This will help to optimize things by reducing calls to calculateOverTimePolicyForTriggerTime()
            $is_differential_criteria = false;
            foreach ($trigger_time_arr_keys as $key => $trigger_time_arr_trigger_time) {
                $current_trigger_time_arr_trigger_time = $trigger_time_arr_keys[$key];
                foreach ($trigger_time_arr[$current_trigger_time_arr_trigger_time] as $key_b => $current_trigger_time_arr) {
                    if ($current_trigger_time_arr['is_differential_criteria'] == true) {
                        $is_differential_criteria = true;
                        break;
                    }
                }
            }
            Debug::text('Maximum Possible Over Time: ' . $maximum_daily_total_time . ' Is Differential Criteria: ' . (int)$is_differential_criteria, __FILE__, __LINE__, __METHOD__, 10);

            foreach ($trigger_time_arr_keys as $key => $trigger_time_arr_trigger_time) {
                $current_trigger_time_arr_trigger_time = $trigger_time_arr_keys[$key];
                foreach ($trigger_time_arr[$current_trigger_time_arr_trigger_time] as $key_b => $current_trigger_time_arr) {
                    //Filter based on combined_rate/calculation_order here rather than in processTriggerTimeArray() due to differential criterias.
                    //Only once a OT policy has actually been used do we take it into account and require the next policy to have a higher rate AND lower calculation order.
                    //That way OT policies with differential criteria that never apply aren't disrupting things.

                    //Move these checks into calculateOverTimePolicyForTriggerTime() so we can better handle differential criteria and hourly rate decisions when differential criterias are used.
                    if ($prev_policy_data == false
                        or (
                            $is_differential_criteria == true
                            or
                            $current_trigger_time_arr['combined_rate'] > $prev_policy_data['combined_rate']
                            or
                            $current_trigger_time_arr['combined_rate'] == $prev_policy_data['combined_rate'] and $current_trigger_time_arr['calculation_order'] <= $prev_policy_data['calculation_order']
                        )
                    ) {
                        $calculate_retval = $this->calculateOverTimePolicyForTriggerTime($date_stamp, $current_trigger_time_arr, $prev_policy_data);

                        //The policies must be calculated in lowest combined_rate order first, otherwise once the highest rate is calculated all others are ignored.

                        //Only when the above retval==TRUE do we consider this policy to be used and set the prev_policy_data.
                        //However if we are processing policies at the same trigger time (ie: 2x Daily OT >8 with different differential criteria),
                        //we can't update prev_policy_data until all policies are processed.
                        //  So policies with the lowest combined rate must be provided to this function first, to avoid the highest rate policy being calculated and all others being ignored.
                        if ($calculate_retval == true) {
                            $prev_policy_data = $current_trigger_time_arr;
                        }
                    } else {
                        Debug::text(' Skipping Overtime Policy due to lower Combined Rate: ' . $current_trigger_time_arr['name'] . ' Current Rate: ' . $current_trigger_time_arr['combined_rate'] . ' Order: ' . $current_trigger_time_arr['calculation_order'] . ' Prev Rate: ' . $prev_policy_data['combined_rate'] . ' Order: ' . $prev_policy_data['calculation_order'], __FILE__, __LINE__, __METHOD__, 10);
                    }
                }
            }

            return true;
        }

        Debug::text('No over time policies to calculate...', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function calculateOverTimePolicyForTriggerTime($date_stamp, $current_trigger_time_arr, $prev_policy_data = false)
    {
        $retval = false;
        if (isset($this->over_time_policy[$current_trigger_time_arr['over_time_policy_id']])) {
            $current_trigger_time_arr_trigger_time = $current_trigger_time_arr['trigger_time'];

            $otp_obj = $this->over_time_policy[$current_trigger_time_arr['over_time_policy_id']];
            Debug::text('OverTime Policy: ' . $otp_obj->getName() . ' Pay Code: ' . $otp_obj->getPayCode() . ' Trigger Time: ' . $current_trigger_time_arr_trigger_time . ' Combined Rate: ' . $current_trigger_time_arr['combined_rate'], __FILE__, __LINE__, __METHOD__, 10);

            if (!isset($this->contributing_shift_policy[$otp_obj->getContributingShiftPolicy()])) {
                Debug::text('  ERROR: Contributing Shift Policy for OverTime Policy: ' . $otp_obj->getName() . ' does not exist...', __FILE__, __LINE__, __METHOD__, 10);
                return false;
            }

            $user_date_total_rows = $this->compactMealAndBreakUserDateTotalObjects($this->filterUserDateTotalDataByContributingShiftPolicy($date_stamp, $date_stamp, $this->contributing_shift_policy[$otp_obj->getContributingShiftPolicy()], array(20, 25, 100, 110)));
            //$user_date_total_rows = $this->sortUserDateTotalData( $user_date_total_rows ); //Sort UDT records once done modifying them. This should help avoid having to sort them everytime we get/filter them.

            //Because some overtime policies may or may not include absence time, we need to recalculate the "maximum" time
            //As the maximum time passed into this function always includes absence time.
            $maximum_daily_total_time = $this->getSumUserDateTotalData($user_date_total_rows);
            Debug::text('  bMaximum Possible Over Time: ' . $maximum_daily_total_time, __FILE__, __LINE__, __METHOD__, 10);

            if (is_array($user_date_total_rows) and count($user_date_total_rows) > 0) {
                //Set the start/end timestamps for each OT policy. This needs to be done on every OT policy/trigger_time_arr element.
                //As the contributing shifts can differ for each.
                //So these can change with each loop, which may seem confusing, but the trigger_time itself won't change.
                //  In case there is an absence record that contributes to overtime and its the only record, make sure we use a start time of midnight on the current date.
                //$current_trigger_time_arr['start_time_stamp'] = ( $user_date_total_rows[key($user_date_total_rows)]->getStartTimeStamp() + $current_trigger_time_arr['trigger_time'] );
                $current_trigger_time_arr['start_time_stamp'] = ((($user_date_total_rows[key($user_date_total_rows)]->getStartTimeStamp() != '') ? $user_date_total_rows[key($user_date_total_rows)]->getStartTimeStamp() : TTDate::getBeginDayEpoch($user_date_total_rows[key($user_date_total_rows)]->getDateStamp())) + $current_trigger_time_arr['trigger_time']);
                Debug::text('  Current Trigger TimeStamp: ' . TTDate::getDate('DATE+TIME', $current_trigger_time_arr['start_time_stamp']), __FILE__, __LINE__, __METHOD__, 10);

                Debug::text('  Total UDT Rows: ' . count($user_date_total_rows), __FILE__, __LINE__, __METHOD__, 10);
                $next_start_time_stamp = false;
                foreach ($user_date_total_rows as $udt_key => $udt_obj) {
                    //Debug::text('  UDT Row KEY: '. $udt_key .' ID: '. $udt_obj->getId() .' Object Type ID: '. $udt_obj->getObjectType() .' Pay Code: '. $udt_obj->getPayCode() .' Start Time: '. TTDate::getDate('DATE+TIME', $udt_obj->getStartTimeStamp() ), __FILE__, __LINE__, __METHOD__, 10);

                    //Detect gap between UDT end -> start timestamps so we can adjust accordingly.
                    //Make sure there is a StartTimeStamp already set on this record, otherwise in cases of creating an absence directly on the timesheet,
                    //  it may not have any timestamps, and if its the only record, it will would be set incorrectly and may apply OT when it shouldnt.
                    //  Example may be Daily OT>8hrs, and creating a 07:45 absence on a day with no other entries that uses the Regular Time pay code.
                    if (isset($prev_udt_obj)
                        and $udt_obj->getStartTimeStamp() != ''
                        and $prev_udt_obj->getStartTimeStamp() != $udt_obj->getStartTimeStamp() //Make sure its not the same record.
                        and $prev_udt_obj->getEndTimeStamp() != $udt_obj->getStartTimeStamp()
                    ) {
                        $current_trigger_time_arr['start_time_stamp'] += ($udt_obj->getStartTimeStamp() - $prev_udt_obj->getEndTimeStamp());
                        Debug::text('    Found gap between UDT records, either a split shift, lunch or break, adjusting next start time... Prev End: ' . TTDate::getDate('DATE+TIME', $prev_udt_obj->getEndTimeStamp()) . ' Current Start: ' . TTDate::getDate('DATE+TIME', $udt_obj->getStartTimeStamp()), __FILE__, __LINE__, __METHOD__, 10);
                    }

                    //This must be below the gap detection/adjustment above.
                    if (isset($this->over_time_recurse_map[$udt_key])) {
                        Debug::text('  Found recursive key, swapping UDT record... UDT Row KEY: ' . $udt_key . ' ID: ' . $udt_obj->getId() . ' New ID: ' . $this->over_time_recurse_map[$udt_key], __FILE__, __LINE__, __METHOD__, 10);
                        $udt_obj = $this->user_date_total[$this->over_time_recurse_map[$udt_key]];
                    }

                    //If the BaseRate is the average regular rate, each time its calculated it will continue to increase.
                    //So we need to make an exception for overtime policies where the base rate is the base regular time rate instead.
                    //  Also take into account multiple policies on the same day, like >8 @ 1.5 and >12 @2.0, it should be 2.0 the base rate, not the 2.0 the last OT rate which itself is 1.5x the base rate.
                    if ($udt_obj->getObjectType() == 30) {
                        $base_hourly_rate = $udt_obj->getBaseHourlyRate();
                    } else {
                        $base_hourly_rate = $udt_obj->getHourlyRate();
                    }
                    $hourly_rate = $this->getHourlyRate($otp_obj->getPayFormulaPolicy(), (int)$otp_obj->getPayCode(), $date_stamp, $base_hourly_rate);

                    //This must be below the $udt_obj assignment above.
                    //Even if the combined_rate is lower, if the input UDT record is not overtime, then we know some UDT records were skipped over likely due to differential criteria OT policies that only matched the middle of a shift.
                    //  See Unit Test: OvertimePolicy_testDifferentialDailyOverTimePolicyE1
                    if ($current_trigger_time_arr['combined_rate'] > $prev_policy_data['combined_rate']
                        or
                        $current_trigger_time_arr['combined_rate'] == $prev_policy_data['combined_rate'] and $current_trigger_time_arr['calculation_order'] <= $prev_policy_data['calculation_order']
                        or
                        //This may cause problems with banked time, as there wouldn't be an hourly rate associated with it, but there would be a combined_rate (accrual rate)
                        $hourly_rate >= $udt_obj->getHourlyRate()
                    ) {
                        $udt_overlap_time = TTDate::getTimeOverLapDifference($udt_obj->getStartTimeStamp(), $udt_obj->getEndTimeStamp(), $current_trigger_time_arr['start_time_stamp'], $udt_obj->getEndTimeStamp());
                        Debug::text('    aID: ' . (int)$udt_obj->getID() . ' Total Time: ' . $udt_obj->getTotalTime() . ' Quantity: ' . $udt_obj->getQuantity() . ' Bad Quantity: ' . $udt_obj->getBadQuantity() . ' Overlap Time: ' . $udt_overlap_time . ' Start Time: ' . TTDate::getDate('DATE+TIME', $udt_obj->getStartTimeStamp()) . ' End Time: ' . TTDate::getDate('DATE+TIME', $udt_obj->getEndTimeStamp()), __FILE__, __LINE__, __METHOD__, 10);

                        if ($udt_overlap_time > 0) {
                            Debug::text('      UDT Overlaps with Trigger Time...', __FILE__, __LINE__, __METHOD__, 10);

                            $create_udt_record = true;

                            if ($create_udt_record == true) {
                                if (!isset($this->over_time_trigger_time_exclusivity_map[$current_trigger_time_arr_trigger_time]) or (isset($this->over_time_trigger_time_exclusivity_map[$current_trigger_time_arr_trigger_time]) and in_array($udt_key, $this->over_time_trigger_time_exclusivity_map[$current_trigger_time_arr_trigger_time]) == false)) {
                                    //Pro-Rate quantities based on overlap time.
                                    //Calculate percent that applies to overtime initially.
                                    $udt_total_time_percent = ($udt_obj->getTotalTime() > 0) ? ($udt_overlap_time / $udt_obj->getTotalTime()) : 1; //Make sure we avoid a division by 0 here, it may happen during drag&drop.
                                    $udt_quantity = round(($udt_obj->getQuantity() * $udt_total_time_percent), 2);
                                    $udt_bad_quantity = round(($udt_obj->getBadQuantity() * $udt_total_time_percent), 2);
                                    Debug::text('        Split user_date_total when overlapping overtime: Time: ' . $udt_overlap_time . ' Percent: ' . $udt_total_time_percent . ' Quantity: ' . $udt_quantity . ' Bad Quantity: ' . $udt_bad_quantity, __FILE__, __LINE__, __METHOD__, 10);
                                    unset($udt_total_time_percent);

                                    //Can't compact the data here, as that won't allow us to reference (pyramid) the time as each policy total time is calculated.
                                    //We will need to create the UserDateTotal objects, then compact them just before inserting...
                                    Debug::text('          Generating UserDateTotal object from OverTime Policy, ID: ' . $this->user_date_total_insert_id . ' Object Type ID: ' . 30 . ' Pay Code ID: ' . (int)$otp_obj->getPayCode() . ' Total Time: ' . $udt_overlap_time . ' Original Hourly Rate: ' . $udt_obj->getHourlyRate(), __FILE__, __LINE__, __METHOD__, 10);
                                    if (!isset($this->user_date_total[$this->user_date_total_insert_id])) {
                                        $udtf = TTnew('UserDateTotalFactory');
                                        $udtf->setUser($this->getUserObject()->getId());
                                        $udtf->setDateStamp($date_stamp);
                                        $udtf->setObjectType(30); //Overtime
                                        $udtf->setSourceObject($otp_obj->getId());
                                        $udtf->setPayCode((int)$otp_obj->getPayCode());

                                        $udtf->setBranch((int)$udt_obj->getBranch());
                                        $udtf->setDepartment((int)$udt_obj->getDepartment());
                                        $udtf->setJob((int)$udt_obj->getJob());
                                        $udtf->setJobItem((int)$udt_obj->getJobItem());

                                        //Make sure we set start/end timestamps for overtime, so its easier to diagnose problems.
                                        //However when including absences in OT it makes that difficult.
                                        //This is required to properly handle Premium Policy that is based on Date/Times (ie: Evening shifts, when multiple overtime policies are applied like on a holiday.)
                                        if ($udt_obj->getStartTimeStamp() != '' and $udt_obj->getEndTimeStamp() != '') {
                                            $udtf->setStartType($udt_obj->getStartType());
                                            $udtf->setEndType($udt_obj->getEndType());

                                            $udt_overlap_arr = TTDate::getTimeOverLap($current_trigger_time_arr['start_time_stamp'], $udt_obj->getEndTimeStamp(), $udt_obj->getStartTimeStamp(), $udt_obj->getEndTimeStamp());
                                            $udtf->setStartTimeStamp($udt_overlap_arr['start_date']);
                                            $udtf->setEndTimeStamp($udt_overlap_arr['end_date']);
                                            //Debug::text('        Current Start Time Stamp: '. TTDate::getDate('DATE+TIME', $udt_obj->getStartTimeStamp() ) .' End: '.  TTDate::getDate('DATE+TIME', $udt_obj->getEndTimeStamp() ) .' Covered Time: '. $covered_time .' Adjust: '. $adjust_covered_time .' Overlap: '. $udt_overlap_time, __FILE__, __LINE__, __METHOD__, 10);
                                            //Debug::text('        OT Start Time Stamp: '. TTDate::getDate('DATE+TIME', $udtf->getStartTimeStamp() ) .' End: '.  TTDate::getDate('DATE+TIME', $udtf->getEndTimeStamp() ), __FILE__, __LINE__, __METHOD__, 10);
                                            unset($udt_overlap_arr);
                                        }

                                        $udtf->setQuantity($udt_quantity);
                                        $udtf->setBadQuantity($udt_bad_quantity);
                                        $udtf->setTotalTime($udt_overlap_time);

                                        $udtf->setBaseHourlyRate($base_hourly_rate); //Calculate this above so we can better determine when to apply the OT policy

                                        //Calculate HourlyRate/HourlyRateWithBurden so Premium Policies can be based off these amounts.
                                        //If they happen to be averaging rates, we will recalculate those later in calculateOverTimeHourlyRates().
                                        $udtf->setHourlyRate($hourly_rate); //Calculate this above so we can better determine when to apply the OT policy
                                        $udtf->setHourlyRateWithBurden($this->getHourlyRateWithBurden($otp_obj->getPayFormulaPolicy(), $udtf->getPayCode(), $date_stamp, $udtf->getHourlyRate()));

                                        $udtf->setEnableCalcSystemTotalTime(false);
                                        $udtf->preSave(); //Call this so TotalTimeAmount is calculated immediately, as we don't save these records until later.

                                        if ($this->isOverriddenUserDateTotalObject($udtf) == false) {
                                            //Don't save the record, just add it to the existing array, so it can be included in other calculations.
                                            //We will save these records at the end.
                                            $this->user_date_total[$this->user_date_total_insert_id] = $udtf;

                                            if ((int)$udt_obj->getID() == 0 and ($udt_obj->getObjectType() == 20 or $udt_obj->getObjectType() == 25 or $udt_obj->getObjectType() == 30)) {
                                                //Since we reduce the source UDT record immediately here, we need a pointer to it rather than a copy.
                                                //If we didn't get a pointer to it near the top of this loop, get it here.
                                                if (!isset($this->over_time_recurse_map[$udt_key])) {
                                                    $udt_obj = $this->user_date_total[$udt_key];
                                                }

                                                $this->over_time_recurse_map[$udt_key] = $this->user_date_total_insert_id;
                                                Debug::text('        Reducing source recursive UDT row... ID: ' . (int)$udt_obj->getId() . ' KEY: ' . $udt_key . ' row EndTimeStamp: ' . TTDate::getDate('DATE+TIME', $udt_obj->getEndTimeStamp()) . '/' . TTDate::getDate('DATE+TIME', ($udt_obj->getEndTimeStamp() - $udt_overlap_time)) . ' TotalTime: ' . $udt_obj->getTotalTime() . '/' . ($udt_obj->getTotalTime() - $udt_overlap_time) . ' Object Type: ' . $udt_obj->getObjectType(), __FILE__, __LINE__, __METHOD__, 10);

                                                $udt_obj->setEndTimeStamp(($udt_obj->getEndTimeStamp() - $udt_overlap_time));
                                                $udt_obj->setQuantity(($udt_obj->getQuantity() - $udt_quantity));
                                                $udt_obj->setBadQuantity(($udt_obj->getBadQuantity() - $udt_bad_quantity));
                                                $udt_obj->setTotalTime(($udt_obj->getTotalTime() - $udt_overlap_time));
                                                $udt_obj->setEnableCalcSystemTotalTime(false);
                                                $udt_obj->preSave(); //Call this so TotalTimeAmount is calculated immediately, as we don't save these records until later.
                                            }

                                            //Due to differential criterias and calculating OT policies in reverse calculation order (Weekly OT first, then Daily OT)
                                            //We can't check the exclusivity map anymore as that will result in incorrect calculations.
                                            //See comments in processTriggerTimeArray()
                                            //$this->over_time_trigger_time_exclusivity_map[$current_trigger_time_arr_trigger_time][] = $udt_key;

                                            //Check to see if the source UDT record will already be calculating accruals, if so, skip calculating any accruals for this UDT record too.
                                            if (isset($this->accrual_time_exclusivity_map[$udt_key])) {
                                                Debug::text('  Adding UDT Insert ID: ' . $this->user_date_total_insert_id . ' to Accrual Time Exclusivity Map...', __FILE__, __LINE__, __METHOD__, 10);
                                                $this->accrual_time_exclusivity_map[$this->user_date_total_insert_id] = true;
                                            }

                                            $this->user_date_total_insert_id--;

                                            $retval = true;
                                        }
                                    } else {
                                        Debug::text('      ERROR: Duplicate starting ID for some reason! ' . $this->user_date_total_insert_id, __FILE__, __LINE__, __METHOD__, 10);
                                    }
                                } else {
                                    Debug::text('      Skipping UDT row due to trigger time exclusivity...', __FILE__, __LINE__, __METHOD__, 10);
                                }
                            } else {
                                Debug::text('      Skipping UDT row due to Differential Criteria...', __FILE__, __LINE__, __METHOD__, 10);
                            }
                        } else {
                            Debug::text('      UDT does NOT Overlap with Trigger Time, not on last policy of same trigger time...', __FILE__, __LINE__, __METHOD__, 10);
                        }
                    } else {
                        Debug::text('      Skipping UDT row due to previous OT policy rate being higher... Current Rate: ' . $udt_obj->getHourlyRate() . ' New Rate: ' . $hourly_rate, __FILE__, __LINE__, __METHOD__, 10);
                    }

                    $prev_udt_obj = $udt_obj;
                }
            }

            if ($retval == true) {
                $this->user_date_total = $this->sortUserDateTotalData($this->user_date_total); //Sort UDT records once done modifying them. This should help avoid having to sort them everytime we get/filter them.
            }
        } else {
            Debug::text('ERROR: Unable to find over time policy ID: ' . $current_trigger_time_arr['over_time_policy_id'], __FILE__, __LINE__, __METHOD__, 10);
        }

        return $retval;
    }

    public function processTriggerTimeArray($date_stamp, $trigger_time_arr)
    {
        if (is_array($trigger_time_arr) == false or count($trigger_time_arr) == 0) {
            return false;
        }

        //Debug::Arr($trigger_time_arr, 'Source Trigger Arr: ', __FILE__, __LINE__, __METHOD__, 10);

        //Convert all OT policies to daily before applying.
        //For instance, 40+hrs/week policy if they are currently at 35hrs is a 5hr daily policy.
        //For weekly OT policies, they MUST include regular time + other WEEKLY over time rules, otherwise they can't stack weekly OT like >40, >50, etc...
        //  However, if they use the same pay code for both Daily and Weekly OT, it could cause problems, so we need to base it on the src_object_id instead.
        //  *Make sure we specify the object_type_id that it must match as well, so we don't confuse a over_time_policy_id=9 with absence_policy_id=9.
        $weekly_over_time_policy_ids = array(30 => (array)$this->getWeeklyOverTimePolicyIDs());
        //Debug::Arr($weekly_over_time_policy_ids, 'Weekly OT Policy IDs: ', __FILE__, __LINE__, __METHOD__, 10);

        //Create a duplicate trigger_time_arr that we can sort so we know the
        //first trigger time is always the first in the array.
        //We don't want to use this array in the loop though, because it throws off other ordering.
        $tmp_trigger_time_arr = Sort::multiSort($trigger_time_arr, 'trigger_time');
        $first_trigger_time = $tmp_trigger_time_arr[0]['trigger_time']; //Get first trigger time.
        //Debug::Arr($tmp_trigger_time_arr, 'Trigger Time After Sort: ', __FILE__, __LINE__, __METHOD__, 10);
        Debug::text('First Trigger Time: ' . $first_trigger_time, __FILE__, __LINE__, __METHOD__, 10);
        unset($tmp_trigger_time_arr);

        //Sort trigger_time array by calculation order before looping over it.
        //  'is_differential_criteria' => SORT_ASC helps calculate OT policies with differential criterias first, so the "catch all" OT policy doesn't need to explicity have exclude criteria set on it. This greatly simplifies OT policies with differential criteria.
        //$trigger_time_arr = Sort::multiSort( $trigger_time_arr, 'calculation_order', 'trigger_time', 'asc', 'desc' );
        $trigger_time_arr = Sort::arrayMultiSort($trigger_time_arr, array('calculation_order' => SORT_ASC, 'trigger_time' => SORT_DESC, 'is_differential_criteria' => SORT_ASC, 'combined_rate' => SORT_DESC));
        //Debug::Arr($trigger_time_arr, 'Source Trigger Arr After Calculation Order Sort: ', __FILE__, __LINE__, __METHOD__, 10);

        $weekly_overtime_policy_type_ids = array(20, 30, 503, 504, 505, 506, 507, 508, 509, 510, 511, 512, 210);

        $date_stamp = TTDate::getMiddleDayEpoch($date_stamp); //Optimization - Move outside loop.

        //We need to calculate regular time as early as possible so we can adjust the trigger time
        //of weekly overtime policies and re-sort the array.
        $tmp_trigger_time_arr = array();
        foreach ($trigger_time_arr as $key => $trigger_time_data) {
            if (in_array($trigger_time_data['over_time_policy_type_id'], $weekly_overtime_policy_type_ids)) {
                //Get weekly total time for this contributing shift id.
                $weekly_total_time = 0;
                if (isset($this->contributing_shift_policy[$trigger_time_data['contributing_shift_policy_id']])) {
                    if (TTDate::getBeginWeekEpoch($date_stamp, $this->start_week_day_id) == TTDate::getBeginDayEpoch($date_stamp)) {
                        Debug::Text('Current day is start of the week, no need to collect weekly total time...', __FILE__, __LINE__, __METHOD__, 10);
                    } else {
                        $weekly_total_time = $this->getSumUserDateTotalData($this->filterUserDateTotalDataByContributingShiftPolicy(TTDate::getBeginWeekEpoch($date_stamp, $this->start_week_day_id), ($date_stamp - 86400), $this->contributing_shift_policy[$trigger_time_data['contributing_shift_policy_id']], array(20, 25, 30, 100, 110), null, $weekly_over_time_policy_ids)); //Don't include object_type_id=50 as that often is duplicated with ID: 25.
                    }
                } else {
                    Debug::Text('  Unable to find Contributing Shift Policy ID: ' . $trigger_time_data['contributing_shift_policy_id'], __FILE__, __LINE__, __METHOD__, 10);
                }
                Debug::Text('  Weekly Total Time: ' . $weekly_total_time . ' as of: ' . TTDate::getDate('DATE', $date_stamp) . ' Trigger Time: ' . $trigger_time_data['trigger_time'], __FILE__, __LINE__, __METHOD__, 10);

                if (is_numeric($weekly_total_time)
                    and $weekly_total_time > 0
                    and $weekly_total_time >= $trigger_time_data['trigger_time']
                ) {
                    //Worked more then weekly trigger time already.
                    Debug::Text('  Worked more then weekly trigger time...', __FILE__, __LINE__, __METHOD__, 10);
                    $tmp_trigger_time = 0;
                } else {
                    //Haven't worked more then the weekly trigger time yet.
                    $tmp_trigger_time = ($trigger_time_data['trigger_time'] - $weekly_total_time);
                    Debug::Text('  NOT Worked more then weekly trigger time... TMP Trigger Time: ' . $tmp_trigger_time, __FILE__, __LINE__, __METHOD__, 10);
                }
                $trigger_time_arr[$key]['trigger_time'] = $tmp_trigger_time;
            } else {
                //Debug::Text('  NOT special (weekly/biweekly) overtime policy...', __FILE__, __LINE__, __METHOD__, 10);
                $tmp_trigger_time = $trigger_time_data['trigger_time'];
            }

            Debug::Text('  Key: ' . $key . ' Trigger Time: ' . $tmp_trigger_time . ' OverTime Policy Id: ' . $trigger_time_data['over_time_policy_id'] . ' Name: ' . $trigger_time_data['name'] . ' Order: ' . $trigger_time_data['calculation_order'], __FILE__, __LINE__, __METHOD__, 10);

            //Make sure we add all trigger times to the array, even if no differential criteria is defined
            // as differential criteria ones may cause the non-differential crtieria to never be included.
            $trigger_time_data['trigger_time'] = $tmp_trigger_time;
            $retval[$tmp_trigger_time][] = $trigger_time_data;

            $tmp_trigger_time_arr[] = $trigger_time_arr[$key]['trigger_time'];
        }
        unset($trigger_time_arr, $tmp_trigger_time_arr, $trigger_time_data);

        //If there are multiple policies at the same trigger time (usually caused Weekly >40, Weekly >44, or differential criteria)
        //Sort them in reverse calculation order then combined rate, so in differential cases the lowest rate is calculated first.
        //  Otherwise the higher rate will be calculated and all other lower rates will be ignored.
        // Reverse calculation order is required because we no longer consider OT exclusive to itself in calculateOverTimePolicyForTriggerTime(), so if two policies are triggered at the same time
        // and at the same rate, they will both be calculated, therefore the last one calculated is what is saved to the DB, so it should be the one with the lowest calculation order.
        foreach ($retval as $tmp_trigger_time => $tmp_policy_data) {
            if (count($tmp_policy_data) > 0) {
                $retval[$tmp_trigger_time] = Sort::arrayMultiSort($retval[$tmp_trigger_time], array('calculation_order' => SORT_DESC, 'is_differential_criteria' => SORT_ASC, 'combined_rate' => SORT_ASC));
            }
        }
        ksort($retval);

        //
        //***Due to differential criteria, we can't filter overtime policies until they have actually been applied. So handle that in calculateOverTimePolicy() instead.
        //

        unset($key, $tmp_trigger_time, $overtime_policies, $policy_data);
        Debug::Arr($retval, 'Final OverTime Trigger Arr: ', __FILE__, __LINE__, __METHOD__, 10);

        return $retval;
    }

    public function getWeeklyOverTimePolicyIDs()
    {
        $weekly_over_time_ids = array();
        $weekly_over_time_policies = $this->filterWeeklyOverTimePolicy();
        if (is_array($weekly_over_time_policies) and count($weekly_over_time_policies) > 0) {
            foreach ($weekly_over_time_policies as $otp_obj) {
                $weekly_over_time_ids[] = $otp_obj->getID();
            }
        }
        unset($weekly_over_time_policies, $otp_obj);

        return $weekly_over_time_ids;
    }

    public function getOverTimeTriggerArray($date_stamp)
    {
        //Loop over each overtime policy that applies to this day.
        $over_time_policies = $this->filterOverTimePolicy($date_stamp);
        if (is_array($over_time_policies)) {
            //$weekly_over_time_pay_code_ids = $this->getWeeklyOverTimePolicyPayCodes();
            $weekly_over_time_src_object_ids = array(30 => $this->getWeeklyOverTimePolicyIDs()); //Force to only include other OverTime ObjectTypeIDs.

            $date_stamp = TTDate::getMiddleDayEpoch($date_stamp); //Optimization - Move outside loop.

            foreach ($over_time_policies as $otp_obj) {
                if (!isset($otp_calculation_order)) {
                    $otp_calculation_order = $otp_obj->getOptions('calculation_order');
                }

                Debug::text('  Checking Against Policy: ' . $otp_obj->getName() . '(' . $otp_obj->getID() . ') Trigger Time: ' . $otp_obj->getTriggerTime(), __FILE__, __LINE__, __METHOD__, 10);
                $trigger_time = null;
                switch ($otp_obj->getType()) {
                    case 10: //Daily
                        $trigger_time = $otp_obj->getTriggerTime();
                        $trigger_time -= $this->getOverTimeTriggerTimeAdjustAmount(TTDate::getBeginDayEpoch($date_stamp), TTDate::getEndDayEpoch($date_stamp), $otp_obj);
                        Debug::text('   Daily Trigger Time: ' . $trigger_time, __FILE__, __LINE__, __METHOD__, 10);
                        break;
                    case 20: //Weekly
                        $trigger_time = $otp_obj->getTriggerTime();
                        $trigger_time -= $this->getOverTimeTriggerTimeAdjustAmount(TTDate::getBeginWeekEpoch($date_stamp, $this->start_week_day_id), TTDate::getEndWeekEpoch($date_stamp, $this->start_week_day_id), $otp_obj);
                        Debug::text('   Weekly Trigger Time: ' . $trigger_time, __FILE__, __LINE__, __METHOD__, 10);
                        break;
                    case 30: //Bi-Weekly (Switch this to Every X Weeks code below once more testing is done)
                        if (is_object($this->pay_period_schedule_obj)) {
                            $is_second_week = $this->isSecondBiWeeklyOverTimeWeek($date_stamp, $this->pay_period_schedule_obj->getFirstPayPeriodStartDate(), $this->start_week_day_id);
                        } else {
                            $is_second_week = false;
                        }

                        //First/Second week dates must be outside below if() statement so OverTimeTriggerTimeAdjustAmount can use them.
                        $first_week_start_date = TTDate::getBeginWeekEpoch(($date_stamp - (86400 * 7)), $this->start_week_day_id);
                        $first_week_end_date = TTDate::getEndWeekEpoch($first_week_start_date, $this->start_week_day_id);

                        $second_week_start_date = TTDate::getBeginWeekEpoch(($date_stamp + (86400 * 7)), $this->start_week_day_id);
                        $second_week_end_date = (TTDate::getEndWeekEpoch($second_week_start_date, $this->start_week_day_id));

                        $first_week_total = 0;
                        if ($is_second_week == true) {
                            //$udtlf->getWeekRegularTimeSumByUserIDAndEpochAndStartWeekEpoch() uses "< $epoch" so the current day is ignored, but in this
                            //case we want to include the last day of the week, so we need to add one day to this argument.
                            //The above caused problems around March 9th due to DST, so just use the beginning of the current week and the beginning of the last week instead.

                            //Get data for first week if we haven't already.
                            Debug::text('   Getting data for first week: Start: ' . TTDate::getDate('DATE', $first_week_start_date) . ' End: ' . TTDate::getDate('DATE', $first_week_end_date), __FILE__, __LINE__, __METHOD__, 10);
                            $this->getRequiredData($first_week_end_date, false); //Optimization: Prevents holidays in the first week from causing the first week to be calculated fully.

                            //$first_week_total = $this->getSumUserDateTotalData( $this->filterUserDateTotalDataByContributingShiftPolicy( $first_week_start_date, $first_week_end_date, $this->contributing_shift_policy[$otp_obj->getContributingShiftPolicy()], array( 20, 25, 30, 100, 110 ), $weekly_over_time_pay_code_ids ) ); //Don't include object_type_id=50 as that often is duplicated with ID: 25.
                            //Filter based on src_object_ids rather than pay_code_ids, because if pay_code_ids are shared between policies (ie: Daily >8 and Weekly>40 go to same pay_code)
                            //It will include too many hours.
                            $first_week_total = $this->getSumUserDateTotalData($this->filterUserDateTotalDataByContributingShiftPolicy($first_week_start_date, $first_week_end_date, $this->contributing_shift_policy[$otp_obj->getContributingShiftPolicy()], array(20, 25, 30, 100, 110), null, $weekly_over_time_src_object_ids)); //Don't include object_type_id=50 as that often is duplicated with ID: 25.
                            Debug::text('   Week modifiers differ, calculate total time for the first week: ' . $first_week_total, __FILE__, __LINE__, __METHOD__, 10);
                        } else {
                            Debug::text('   Calculating OT for second week: Date: ' . TTDate::getDate('DATE+TIME', $date_stamp) . ' Start: ' . TTDate::getDate('DATE', $second_week_start_date) . ' End: ' . TTDate::getDate('DATE', $second_week_end_date), __FILE__, __LINE__, __METHOD__, 10);

                            $this->addPendingCalculationDate($second_week_start_date, $second_week_end_date);
                        }

                        $trigger_time = ($otp_obj->getTriggerTime() - $first_week_total);
                        if ($trigger_time < 0) {
                            $trigger_time = 0;
                        }

                        $trigger_time -= $this->getOverTimeTriggerTimeAdjustAmount($first_week_start_date, $second_week_end_date, $otp_obj);

                        Debug::text('   BiWeekly Trigger Time: ' . $trigger_time, __FILE__, __LINE__, __METHOD__, 10);
                        unset($first_week_total, $week_modifier, $current_week_modifier, $first_week_start_date, $first_week_end_date, $second_week_start_date, $second_week_end_date);
                        break;
                    //case 30: //Bi-Weekly -- BiWeekly Code can be switched to here after more testing.
                    case 503: //Every 3 Weeks
                    case 504: //Every 4 Weeks
                    case 505: //Every 5 Weeks
                    case 506: //Every 6 Weeks
                    case 507: //Every 7 Weeks
                    case 508: //Every 8 Weeks
                    case 509: //Every 9 Weeks
                    case 510: //Every 10 Weeks
                    case 511: //Every 11 Weeks
                    case 512: //Every 12 Weeks
                        //Get Start/End date of the OT period (ie: 2 week  period, 3 week period, etc...)
                        //Get Start/End date of the current week that $date_stamp is in.
                        //Sum the hours from period start date to current week start date.
                        //Recalculate days from current week end, to OT period end.
                        $week_arr = array(30 => 2, 503 => 3, 504 => 4, 505 => 5, 506 => 6, 507 => 7, 508 => 8, 509 => 9, 510 => 10, 511 => 11, 512 => 12);
                        $weeks = $week_arr[$otp_obj->getType()];

                        $period_dates = $this->getOverTimePeriodDates($date_stamp, $weeks, $this->pay_period_schedule_obj->getFirstPayPeriodStartDate(), $this->start_week_day_id);

                        $before_current_week_total = 0;
                        if ($period_dates['is_first_week'] == false) {
                            //If we are not in the first week, get all hours from the before the current week.
                            Debug::text('   Getting data for before the current week: Start: ' . TTDate::getDate('DATE', $period_dates['before_current_week_start_date']) . ' End: ' . TTDate::getDate('DATE', $period_dates['before_current_week_end_date']), __FILE__, __LINE__, __METHOD__, 10);
                            $this->getRequiredData($period_dates['before_current_week_end_date'], false); //Optimization: Prevents holidays in the first week from causing the first week to be calculated fully.

                            $before_current_week_total = $this->getSumUserDateTotalData($this->filterUserDateTotalDataByContributingShiftPolicy($period_dates['before_current_week_start_date'], $period_dates['before_current_week_end_date'], $this->contributing_shift_policy[$otp_obj->getContributingShiftPolicy()], array(20, 25, 30, 100, 110), null, $weekly_over_time_src_object_ids)); //Don't include object_type_id=50 as that often is duplicated with ID: 25.
                            Debug::text('   Total Time for Before Current Week: ' . $before_current_week_total, __FILE__, __LINE__, __METHOD__, 10);
                        }

                        if ($period_dates['is_last_week'] == false) {
                            //If we are not in the last week, make sure we recalculate all future days.
                            $this->addPendingCalculationDate($period_dates['after_current_week_start_date'], $period_dates['after_current_week_end_date']);
                        }

                        $trigger_time = ($otp_obj->getTriggerTime() - $before_current_week_total);
                        if ($trigger_time < 0) {
                            $trigger_time = 0;
                        }

                        $trigger_time -= $this->getOverTimeTriggerTimeAdjustAmount($period_dates['before_current_week_start_date'], $period_dates['after_current_week_end_date'], $otp_obj);
                        Debug::text('   Every X-Weeks Trigger Time: ' . $trigger_time, __FILE__, __LINE__, __METHOD__, 10);
                        unset($weeks_arr, $weeks, $period_dates, $before_current_week_total);
                        break;
                    case 40: //Sunday
                        if (date('w', $date_stamp) == 0) {
                            $trigger_time = $otp_obj->getTriggerTime();
                            $trigger_time -= $this->getOverTimeTriggerTimeAdjustAmount(TTDate::getBeginDayEpoch($date_stamp), TTDate::getEndDayEpoch($date_stamp), $otp_obj);
                            Debug::text('   DayOfWeek OT for Sat ... Daily Trigger Time: ' . $trigger_time, __FILE__, __LINE__, __METHOD__, 10);
                        } else {
                            Debug::text('   NOT DayOfWeek OT for Sat...', __FILE__, __LINE__, __METHOD__, 10);
                            continue;
                        }
                        break;
                    case 50: //Monday
                        if (date('w', $date_stamp) == 1) {
                            $trigger_time = $otp_obj->getTriggerTime();
                            $trigger_time -= $this->getOverTimeTriggerTimeAdjustAmount(TTDate::getBeginDayEpoch($date_stamp), TTDate::getEndDayEpoch($date_stamp), $otp_obj);
                            Debug::text('   DayOfWeek OT for Sat ... Daily Trigger Time: ' . $trigger_time, __FILE__, __LINE__, __METHOD__, 10);
                        } else {
                            Debug::text('   NOT DayOfWeek OT for Sat...', __FILE__, __LINE__, __METHOD__, 10);
                            continue;
                        }
                        break;
                    case 60: //Tuesday
                        if (date('w', $date_stamp) == 2) {
                            $trigger_time = $otp_obj->getTriggerTime();
                            $trigger_time -= $this->getOverTimeTriggerTimeAdjustAmount(TTDate::getBeginDayEpoch($date_stamp), TTDate::getEndDayEpoch($date_stamp), $otp_obj);
                            Debug::text('   DayOfWeek OT for Sat ... Daily Trigger Time: ' . $trigger_time, __FILE__, __LINE__, __METHOD__, 10);
                        } else {
                            Debug::text('   NOT DayOfWeek OT for Sat...', __FILE__, __LINE__, __METHOD__, 10);
                            continue;
                        }
                        break;
                    case 70: //Wed
                        if (date('w', $date_stamp) == 3) {
                            $trigger_time = $otp_obj->getTriggerTime();
                            $trigger_time -= $this->getOverTimeTriggerTimeAdjustAmount(TTDate::getBeginDayEpoch($date_stamp), TTDate::getEndDayEpoch($date_stamp), $otp_obj);
                            Debug::text('   DayOfWeek OT for Sat ... Daily Trigger Time: ' . $trigger_time, __FILE__, __LINE__, __METHOD__, 10);
                        } else {
                            Debug::text('   NOT DayOfWeek OT for Sat...', __FILE__, __LINE__, __METHOD__, 10);
                            continue;
                        }
                        break;
                    case 80: //Thu
                        if (date('w', $date_stamp) == 4) {
                            $trigger_time = $otp_obj->getTriggerTime();
                            $trigger_time -= $this->getOverTimeTriggerTimeAdjustAmount(TTDate::getBeginDayEpoch($date_stamp), TTDate::getEndDayEpoch($date_stamp), $otp_obj);
                            Debug::text('   DayOfWeek OT for Sat ... Daily Trigger Time: ' . $trigger_time, __FILE__, __LINE__, __METHOD__, 10);
                        } else {
                            Debug::text('   NOT DayOfWeek OT for Sat...', __FILE__, __LINE__, __METHOD__, 10);
                            continue;
                        }
                        break;
                    case 90: //Fri
                        if (date('w', $date_stamp) == 5) {
                            $trigger_time = $otp_obj->getTriggerTime();
                            $trigger_time -= $this->getOverTimeTriggerTimeAdjustAmount(TTDate::getBeginDayEpoch($date_stamp), TTDate::getEndDayEpoch($date_stamp), $otp_obj);
                            Debug::text('   DayOfWeek OT for Sat ... Daily Trigger Time: ' . $trigger_time, __FILE__, __LINE__, __METHOD__, 10);
                        } else {
                            Debug::text('   NOT DayOfWeek OT for Sat...', __FILE__, __LINE__, __METHOD__, 10);
                            continue;
                        }
                        break;
                    case 100: //Sat
                        if (date('w', $date_stamp) == 6) {
                            $trigger_time = $otp_obj->getTriggerTime();
                            $trigger_time -= $this->getOverTimeTriggerTimeAdjustAmount(TTDate::getBeginDayEpoch($date_stamp), TTDate::getEndDayEpoch($date_stamp), $otp_obj);
                            Debug::text('   DayOfWeek OT for Sat ... Daily Trigger Time: ' . $trigger_time, __FILE__, __LINE__, __METHOD__, 10);
                        } else {
                            Debug::text('   NOT DayOfWeek OT for Sat...', __FILE__, __LINE__, __METHOD__, 10);
                            continue;
                        }
                        break;
                    case 150: //2-day/week Consecutive
                    case 151: //3-day/week Consecutive
                    case 152: //4-day/week Consecutive
                    case 153: //5-day/week Consecutive
                    case 154: //6-day/week Consecutive
                    case 155: //7-day/week Consecutive
                        switch ($otp_obj->getType()) {
                            case 150:
                                $minimum_days_worked = 2;
                                break;
                            case 151:
                                $minimum_days_worked = 3;
                                break;
                            case 152:
                                $minimum_days_worked = 4;
                                break;
                            case 153:
                                $minimum_days_worked = 5;
                                break;
                            case 154:
                                $minimum_days_worked = 6;
                                break;
                            case 155:
                                $minimum_days_worked = 7;
                                break;
                        }

                        //This always resets on the week boundary.
                        //$days_worked_arr = (array)$udtlf->getDaysWorkedByUserIDAndStartDateAndEndDate( $this->getUserObject()->getId(), TTDate::getBeginWeekEpoch($date_stamp, $start_week_day_id), $date_stamp );
                        //$days_worked_arr = (array)$this->getDayArrayUserDateTotalData( $this->filterUserDateTotalDataByContributingShiftPolicy( TTDate::getBeginWeekEpoch( $date_stamp, $this->start_week_day_id ), $date_stamp, $this->contributing_shift_policy[$otp_obj->getContributingShiftPolicy()], NULL, $weekly_over_time_pay_code_ids ) );
                        $days_worked_arr = (array)$this->getDayArrayUserDateTotalData($this->filterUserDateTotalDataByContributingShiftPolicy(TTDate::getBeginWeekEpoch($date_stamp, $this->start_week_day_id), $date_stamp, $this->contributing_shift_policy[$otp_obj->getContributingShiftPolicy()], null, null, $weekly_over_time_src_object_ids));

                        $weekly_days_worked = count($days_worked_arr);
                        Debug::text('   Weekly Days Worked: ' . $weekly_days_worked . ' Minimum Required: ' . $minimum_days_worked, __FILE__, __LINE__, __METHOD__, 10);

                        if ($weekly_days_worked >= $minimum_days_worked and TTDate::isConsecutiveDays($days_worked_arr) == true) {
                            $trigger_time = $otp_obj->getTriggerTime();
                            $trigger_time -= $this->getOverTimeTriggerTimeAdjustAmount(TTDate::getBeginWeekEpoch($date_stamp, $this->start_week_day_id), $date_stamp, $otp_obj);
                            Debug::text('   After Days Consecutive... Daily Trigger Time: ' . $trigger_time, __FILE__, __LINE__, __METHOD__, 10);
                        } else {
                            Debug::text('   NOT After Days Consecutive Worked...', __FILE__, __LINE__, __METHOD__, 10);
                            continue;
                        }
                        unset($days_worked_arr, $weekly_days_worked, $minimum_days_worked);

                        break;
                    case 300: //2-day Consecutive
                    case 301: //3-day Consecutive
                    case 302: //4-day Consecutive
                    case 303: //5-day Consecutive
                    case 304: //6-day Consecutive
                    case 305: //7-day Consecutive
                        switch ($otp_obj->getType()) {
                            case 300:
                                $minimum_days_worked = 2;
                                break;
                            case 301:
                                $minimum_days_worked = 3;
                                break;
                            case 302:
                                $minimum_days_worked = 4;
                                break;
                            case 303:
                                $minimum_days_worked = 5;
                                break;
                            case 304:
                                $minimum_days_worked = 6;
                                break;
                            case 305:
                                $minimum_days_worked = 7;
                                break;
                        }

                        //This does not reset on the week boundary.
                        //$days_worked_arr = (array)$udtlf->getDaysWorkedByUserIDAndStartDateAndEndDate( $this->getUserObject()->getId(), ( $date_stamp - (86400 * $minimum_days_worked) ), $date_stamp );
                        //$days_worked_arr = (array)$this->getDayArrayUserDateTotalData( $this->filterUserDateTotalDataByContributingShiftPolicy( ( $date_stamp - (86400 * $minimum_days_worked) ), $date_stamp, $this->contributing_shift_policy[$otp_obj->getContributingShiftPolicy()], NULL, $weekly_over_time_pay_code_ids ) );
                        $days_worked_arr = (array)$this->getDayArrayUserDateTotalData($this->filterUserDateTotalDataByContributingShiftPolicy(($date_stamp - (86400 * $minimum_days_worked)), $date_stamp, $this->contributing_shift_policy[$otp_obj->getContributingShiftPolicy()], null, null, $weekly_over_time_src_object_ids));

                        $weekly_days_worked = count($days_worked_arr);
                        Debug::text('   Weekly Days Worked: ' . $weekly_days_worked . ' Minimum Required: ' . $minimum_days_worked, __FILE__, __LINE__, __METHOD__, 10);

                        //Since these can span overtime weeks, we need to calculate the future week as well.
                        //UserDateTotalFactory::setEnableCalcFutureWeek(TRUE);

                        if ($weekly_days_worked >= $minimum_days_worked and TTDate::isConsecutiveDays($days_worked_arr) == true) {
                            $trigger_time = $otp_obj->getTriggerTime();
                            $trigger_time -= $this->getOverTimeTriggerTimeAdjustAmount(($date_stamp - (86400 * $minimum_days_worked)), $date_stamp, $otp_obj);
                            Debug::text('   After Days Consecutive... Daily Trigger Time: ' . $trigger_time, __FILE__, __LINE__, __METHOD__, 10);
                        } else {
                            Debug::text('   NOT After Days Consecutive Worked...', __FILE__, __LINE__, __METHOD__, 10);
                            continue;
                        }
                        unset($days_worked_arr, $weekly_days_worked, $minimum_days_worked);
                        break;
                    case 350: //2nd Consecutive Day
                    case 351: //3rd Consecutive Day
                    case 352: //4th Consecutive Day
                    case 353: //5th Consecutive Day
                    case 354: //6th Consecutive Day
                    case 355: //7th Consecutive Day
                        switch ($otp_obj->getType()) {
                            case 350:
                                $minimum_days_worked = 2;
                                break;
                            case 351:
                                $minimum_days_worked = 3;
                                break;
                            case 352:
                                $minimum_days_worked = 4;
                                break;
                            case 353:
                                $minimum_days_worked = 5;
                                break;
                            case 354:
                                $minimum_days_worked = 6;
                                break;
                            case 355:
                                $minimum_days_worked = 7;
                                break;
                        }

                        //Why is this checking for previous day with overtime worked? Because thats how it knows when to restart the consec. day count
                        //Based on when the last overtime was calculated?
                        $range_start_date = ($date_stamp - (86400 * $minimum_days_worked));

                        //FIXME: This checks for any other time assigned to the pay code, but if they assigned multiple overtime policies to the same pay code
                        //       they may not get the expected results. In order to this fix this we need to track src_object_id for all UDT records and not compact it out.
                        $previous_day_with_overtime_result = $this->getPreviousDayByUserTotalData($this->filterUserDateTotalDataByPayCodeIDs($range_start_date, $date_stamp, $otp_obj->getPayCode()), $date_stamp);
                        if ($previous_day_with_overtime_result !== false) {
                            $previous_day_with_overtime = TTDate::getMiddleDayEpoch($previous_day_with_overtime_result);
                            Debug::text('   Previous Day with OT: ' . TTDate::getDate('DATE', $previous_day_with_overtime) . ' Start Date: ' . TTDate::getDate('DATE', $range_start_date) . ' End Date: ' . TTDate::getDate('DATE', $date_stamp), __FILE__, __LINE__, __METHOD__, 10);
                        }

                        if (isset($previous_day_with_overtime) and $previous_day_with_overtime >= $range_start_date) {
                            $range_start_date = (TTDate::getMiddleDayEpoch($previous_day_with_overtime) + 86400);
                            Debug::text('   bPrevious Day with OT: ' . TTDate::getDate('DATE', $previous_day_with_overtime) . ' Start Date: ' . TTDate::getDate('DATE', $range_start_date) . ' End Date: ' . TTDate::getDate('DATE', $date_stamp), __FILE__, __LINE__, __METHOD__, 10);
                        }

                        //This does not reset on the week boundary.
                        //$days_worked_arr = (array)$udtlf->getDaysWorkedByUserIDAndStartDateAndEndDate( $this->getUserObject()->getId(), $range_start_date, $date_stamp );
                        //$days_worked_arr = (array)$this->getDayArrayUserDateTotalData( $this->filterUserDateTotalDataByContributingShiftPolicy( $range_start_date, $date_stamp, $this->contributing_shift_policy[$otp_obj->getContributingShiftPolicy()], NULL, $weekly_over_time_pay_code_ids ) );
                        $days_worked_arr = (array)$this->getDayArrayUserDateTotalData($this->filterUserDateTotalDataByContributingShiftPolicy($range_start_date, $date_stamp, $this->contributing_shift_policy[$otp_obj->getContributingShiftPolicy()], null, null, $weekly_over_time_src_object_ids));
                        sort($days_worked_arr);

                        $weekly_days_worked = count($days_worked_arr);
                        Debug::text('   Weekly Days Worked: ' . $weekly_days_worked . ' Minimum Required: ' . $minimum_days_worked, __FILE__, __LINE__, __METHOD__, 10);

                        //Since these can span overtime weeks, we need to calculate the future week as well.
                        //UserDateTotalFactory::setEnableCalcFutureWeek(TRUE);

                        $days_worked_arr_key = ($minimum_days_worked - 1);
                        if ($weekly_days_worked >= $minimum_days_worked
                            and TTDate::isConsecutiveDays($days_worked_arr) == true
                            and isset($days_worked_arr[$days_worked_arr_key])
                            and TTDate::getMiddleDayEpoch($days_worked_arr[$days_worked_arr_key]) == $date_stamp
                        ) {
                            $trigger_time = $otp_obj->getTriggerTime();
                            $trigger_time -= $this->getOverTimeTriggerTimeAdjustAmount($range_start_date, $date_stamp, $otp_obj);
                            Debug::text('   After Days Consecutive... Daily Trigger Time: ' . $trigger_time, __FILE__, __LINE__, __METHOD__, 10);
                        } else {
                            Debug::text('   NOT After Days Consecutive Worked...', __FILE__, __LINE__, __METHOD__, 10);
                            continue;
                        }
                        unset($range_start_date, $previous_day_with_overtime, $previous_day_with_overtime, $days_worked_arr, $weekly_days_worked, $minimum_days_worked);
                        break;
                    case 400: //2-day/week Consecutive
                    case 401: //3-day/week Consecutive
                    case 402: //4-day/week Consecutive
                    case 403: //5-day/week Consecutive
                    case 404: //6-day/week Consecutive
                    case 405: //7-day/week Consecutive
                        switch ($otp_obj->getType()) {
                            case 400:
                                $minimum_days_worked = 2;
                                break;
                            case 401:
                                $minimum_days_worked = 3;
                                break;
                            case 402:
                                $minimum_days_worked = 4;
                                break;
                            case 403:
                                $minimum_days_worked = 5;
                                break;
                            case 404:
                                $minimum_days_worked = 6;
                                break;
                            case 405:
                                $minimum_days_worked = 7;
                                break;
                        }

                        //This always resets on the week boundary.
                        //$days_worked_arr = (array)$udtlf->getDaysWorkedByUserIDAndStartDateAndEndDate( $this->getUserObject()->getId(), TTDate::getBeginWeekEpoch($date_stamp, $start_week_day_id), $date_stamp );
                        //$days_worked_arr = (array)$this->getDayArrayUserDateTotalData( $this->filterUserDateTotalDataByContributingShiftPolicy( TTDate::getBeginWeekEpoch( $date_stamp, $this->start_week_day_id ), $date_stamp, $this->contributing_shift_policy[$otp_obj->getContributingShiftPolicy()], NULL, $weekly_over_time_pay_code_ids ) );
                        $days_worked_arr = (array)$this->getDayArrayUserDateTotalData($this->filterUserDateTotalDataByContributingShiftPolicy(TTDate::getBeginWeekEpoch($date_stamp, $this->start_week_day_id), $date_stamp, $this->contributing_shift_policy[$otp_obj->getContributingShiftPolicy()], null, $weekly_over_time_src_object_ids));

                        $weekly_days_worked = count($days_worked_arr);
                        Debug::text('   Weekly Days Worked: ' . $weekly_days_worked . ' Minimum Required: ' . $minimum_days_worked, __FILE__, __LINE__, __METHOD__, 10);

                        if ($weekly_days_worked >= $minimum_days_worked) {
                            $trigger_time = $otp_obj->getTriggerTime();
                            $trigger_time -= $this->getOverTimeTriggerTimeAdjustAmount(TTDate::getBeginWeekEpoch($date_stamp, $this->start_week_day_id), TTDate::getEndDayEpoch($date_stamp), $otp_obj);
                            Debug::text('   After Days... Daily Trigger Time: ' . $trigger_time, __FILE__, __LINE__, __METHOD__, 10);
                        } else {
                            Debug::text('   NOT After Days Worked...', __FILE__, __LINE__, __METHOD__, 10);
                            continue;
                        }
                        unset($days_worked_arr, $weekly_days_worked, $minimum_days_worked);
                        break;
                    case 180: //Holiday
                        $holiday_obj = $this->filterHoliday($date_stamp);
                        if (is_object($holiday_obj) and isset($this->holiday_policy[$holiday_obj->getHolidayPolicyID()])) {
                            Debug::text('   Found Holiday: ' . $holiday_obj->getName(), __FILE__, __LINE__, __METHOD__, 10);
                            if ($this->holiday_policy[$holiday_obj->getHolidayPolicyID()]->getForceOverTimePolicy() == true
                                or $this->isEligibleForHoliday($date_stamp, $this->holiday_policy[$holiday_obj->getHolidayPolicyID()])
                            ) {
                                $trigger_time = $otp_obj->getTriggerTime();
                                $trigger_time -= $this->getOverTimeTriggerTimeAdjustAmount(TTDate::getBeginDayEpoch($date_stamp), TTDate::getEndDayEpoch($date_stamp), $otp_obj);
                                Debug::text('   Is Eligible for Holiday: ' . $holiday_obj->getName() . ' Daily Trigger Time: ' . $trigger_time, __FILE__, __LINE__, __METHOD__, 10);
                            } else {
                                Debug::text('   Not Eligible for Holiday: ' . $holiday_obj->getName(), __FILE__, __LINE__, __METHOD__, 10);
                                continue 2; //Skip to next policy
                            }
                        } else {
                            Debug::text('   Not Holiday...', __FILE__, __LINE__, __METHOD__, 10);
                            continue 2; //Skip to next policy
                        }
                        unset($holiday_obj);
                        break;
                    case 200: //Over schedule (Daily) / No Schedule. Have trigger time extend the schedule time.
                        $schedule_daily_total_time = $this->getSumScheduleTime($this->filterScheduleDataByStatus($date_stamp, $date_stamp, array(10)));
                        Debug::text('   Schedule Daily Total Time: ' . $schedule_daily_total_time, __FILE__, __LINE__, __METHOD__, 10);

                        $trigger_time = ($schedule_daily_total_time + $otp_obj->getTriggerTime());
                        $trigger_time -= $this->getOverTimeTriggerTimeAdjustAmount(TTDate::getBeginDayEpoch($date_stamp), TTDate::getEndDayEpoch($date_stamp), $otp_obj);
                        Debug::text('   Over Schedule/No Schedule Trigger Time: ' . $trigger_time, __FILE__, __LINE__, __METHOD__, 10);
                        unset($schedule_daily_total_time);
                        break;
                    case 210: //Over Schedule (Weekly) / No Schedule
                        //Get schedule time for the entire week, and add the Active After time to that.
                        //$schedule_weekly_total_time = $slf->getWeekWorkTimeSumByUserIDAndEpochAndStartWeekEpoch( $this->getUserObject()->getId(), TTDate::getEndWeekEpoch($date_stamp, $start_week_day_id), TTDate::getBeginWeekEpoch($date_stamp, $start_week_day_id) );
                        $schedule_weekly_total_time = $this->getSumScheduleTime($this->filterScheduleDataByStatus(TTDate::getBeginWeekEpoch($date_stamp, $this->start_week_day_id), TTDate::getEndWeekEpoch($date_stamp, $this->start_week_day_id), array(10)));
                        Debug::text('   Schedule Weekly Total Time: ' . $schedule_weekly_total_time, __FILE__, __LINE__, __METHOD__, 10);

                        $trigger_time = ($schedule_weekly_total_time + $otp_obj->getTriggerTime());
                        $trigger_time -= $this->getOverTimeTriggerTimeAdjustAmount(TTDate::getBeginWeekEpoch($date_stamp, $this->start_week_day_id), TTDate::getEndWeekEpoch($date_stamp, $this->start_week_day_id), $otp_obj);
                        unset($schedule_weekly_total_time);
                        break;
                }

                if (is_numeric($trigger_time) and $trigger_time < 0) {
                    $trigger_time = 0;
                }

                if (is_numeric($trigger_time)) {
                    $pay_formula_obj = $this->getPayFormulaPolicyObjectByPolicyObject($otp_obj);
                    if (is_object($pay_formula_obj)) {
                        $trigger_time_arr[] = array('name' => $otp_obj->getName(), 'calculation_order' => $otp_calculation_order[$otp_obj->getType()], 'trigger_time' => $trigger_time, 'is_differential_criteria' => $otp_obj->isDifferentialCriteriaDefined(), 'over_time_policy_id' => $otp_obj->getId(), 'over_time_policy_type_id' => $otp_obj->getType(), 'contributing_shift_policy_id' => $otp_obj->getContributingShiftPolicy(), 'pay_code_id' => $otp_obj->getPayCode(), 'combined_rate' => ($pay_formula_obj->getRate() + $pay_formula_obj->getAccrualRate()));
                        //Debug::Arr($trigger_time_arr, 'Trigger Time Array: ', __FILE__, __LINE__, __METHOD__, 10);
                    } else {
                        Debug::Arr(array_keys((array)$this->pay_codes), 'Pay Formula Policy not found! OT Policy ID: ' . $otp_obj->getID(), __FILE__, __LINE__, __METHOD__, 10);
                    }
                }
                unset($trigger_time);
            }

            if (isset($trigger_time_arr)) {
                return $trigger_time_arr;
            }

            return true;
        }

        Debug::text('No over time policies to build trigger array from...', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function filterOverTimePolicy($date_stamp)
    {
        $otplf = $this->over_time_policy;
        if (is_array($otplf) and count($otplf) > 0) {
            $schedule_policy_over_time_policy_ids = array();
            $schedule_policy_exclude_over_time_policy_ids = array();
            $schedule_policy_arr = $this->filterSchedulePolicyByDate($date_stamp);
            if (is_array($schedule_policy_arr)) {
                foreach ($schedule_policy_arr as $sp_obj) {
                    if (is_array($sp_obj->getIncludeOverTimePolicy()) and count($sp_obj->getIncludeOverTimePolicy()) > 0) {
                        $schedule_policy_over_time_policy_ids = array_merge($schedule_policy_over_time_policy_ids, (array)$sp_obj->getIncludeOverTimePolicy());
                    }
                    if (is_array($sp_obj->getExcludeOverTimePolicy()) and count($sp_obj->getExcludeOverTimePolicy()) > 0) {
                        $schedule_policy_exclude_over_time_policy_ids = array_merge($schedule_policy_exclude_over_time_policy_ids, (array)$sp_obj->getExcludeOverTimePolicy());
                    }
                }
                Debug::Arr($schedule_policy_over_time_policy_ids, 'OverTime Policies that apply to: ' . TTDate::getDate('DATE', $date_stamp) . ' from schedule policies: ', __FILE__, __LINE__, __METHOD__, 10);
            }

            foreach ($otplf as $otp_obj) {
                if (
                (
                    ((int)$otp_obj->getColumn('is_policy_group') == 1 and !in_array($otp_obj->getId(), $schedule_policy_exclude_over_time_policy_ids))
                    or
                    ((int)$otp_obj->getColumn('is_policy_group') == 0 and in_array($otp_obj->getId(), $schedule_policy_over_time_policy_ids))
                )
                ) {
                    $retarr[$otp_obj->getId()] = $otp_obj;
                }
            }

            if (isset($retarr)) {
                Debug::text('Found overtime policies that apply on date: ' . count($retarr), __FILE__, __LINE__, __METHOD__, 10);
                return $retarr;
            }
        }

        Debug::text('No overtime policies apply on date...', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function getOverTimeTriggerTimeAdjustAmount($start_date, $end_date, $otp_obj)
    {
        return 0;
    }

    public function isSecondBiWeeklyOverTimeWeek($date_stamp, $first_pay_period_start_date, $start_week_day_id = 0)
    {
        //This must be based on an "anchor" date, or first_pay_period_start_date, otherwise when there are 53 weeks in a year
        //it will throw off the odd/even calculation.
        //FIXME: What happens if they transition from one pay period schedule to another, and the first pay period is a shorter pay period.
        //  For example from Semi-Monthly to Bi-Weekly, and the first BiWeekly PP is 01-Jan-2014 to 05-Jan-215, then it continues as regular biweekly PPs after that.
        //  I think for now they will just need to modify their pay period dates so the pay period always starts on the 1st week, not the 2nd week.

        if ($first_pay_period_start_date == '') {
            $first_pay_period_start_date = 788947200; //Sun, 01-Jan-1995... Used to calculate weeks from.
            Debug::text('ERROR: First PP Start Date is invalid, use reference date instead!', __FILE__, __LINE__, __METHOD__, 10);
        }

        //Based on first pay period start date, get beginning week epoch.
        $reference_date = TTDate::getMiddleDayEpoch(TTDate::getBeginWeekEpoch($first_pay_period_start_date, $start_week_day_id));

        //Figure out how many days we are from the first pay period start date, then figure out the number of weeks, to determine odd/even essentially.
        //$days_diff = TTDate::getDays( ( TTDate::getMiddleDayEpoch( $date_stamp ) - $reference_date ) );
        $days_diff = TTDate::getDayDifference($reference_date, TTDate::getMiddleDayEpoch($date_stamp));
        $weekly_period_diff = ($days_diff / 7);

        $retval = ($weekly_period_diff % 2);

        Debug::text(' Date: ' . TTDate::getDate('DATE', $date_stamp) . ' First PP Start Date: ' . TTDate::getDate('DATE+TIME', $first_pay_period_start_date) . ' Days: ' . $days_diff . ' Weeks: ' . $weekly_period_diff . ' Retval: ' . (int)$retval, __FILE__, __LINE__, __METHOD__, 10);

        if ($retval == 1) {
            return true;
        }

        return false;
    }

    public function getOverTimePeriodDates($date_stamp, $weeks, $first_pay_period_start_date, $start_week_day_id = 0)
    {
        //Weeks is the number of weeks. ie: 2, 3, 6, ...

        //This must be based on an "anchor" date, or first_pay_period_start_date, otherwise when there are 53 weeks in a year
        //it will throw off the odd/even calculation.
        //FIXME: What happens if they transition from one pay period schedule to another, and the first pay period is a shorter pay period.
        //  For example from Semi-Monthly to Bi-Weekly, and the first BiWeekly PP is 01-Jan-2014 to 05-Jan-215, then it continues as regular biweekly PPs after that.
        //  I think for now they will just need to modify their pay period dates so the pay period always starts on the 1st week, not the 2nd week.

        if ($first_pay_period_start_date == '') {
            $first_pay_period_start_date = 788947200; //Sun, 01-Jan-1995... Used to calculate weeks from.
            Debug::text('ERROR: First PP Start Date is invalid, use reference date instead!', __FILE__, __LINE__, __METHOD__, 10);
        }

        //Based on first pay period start date, get beginning week epoch.
        $reference_date = TTDate::getMiddleDayEpoch(TTDate::getBeginWeekEpoch($first_pay_period_start_date, $start_week_day_id));
        $begin_week_date = TTDate::getMiddleDayEpoch(TTDate::getBeginWeekEpoch($date_stamp, $start_week_day_id));
        //Debug::text('Reference Date: '. TTDate::getDate('DATE+TIME', $reference_date ) .' Begin Week Date: '. TTDate::getDate('DATE+TIME', $begin_week_date ), __FILE__, __LINE__, __METHOD__, 10);

        //Figure out how many days we are from the first pay period start date, then figure out the number of weeks, to determine odd/even essentially.
        //$days_diff = round( TTDate::getDays( ( $begin_week_date - $reference_date ) ) );
        $days_diff = TTDate::getDayDifference($reference_date, $begin_week_date);
        $weekly_period_diff = ($days_diff / 7);

        $week_in_period = ($weekly_period_diff % $weeks);

        $period_start_date = TTDate::getBeginWeekEpoch(($begin_week_date - (86400 * (7 * $week_in_period))), $start_week_day_id);
        $period_end_date = TTDate::getEndWeekEpoch((($period_start_date - 86400) + (86400 * (7 * $weeks))), $start_week_day_id);
        //Debug::text('Period Start Date: '. TTDate::getDate('DATE+TIME', $period_start_date ) .' End Date: '. TTDate::getDate('DATE+TIME', $period_end_date ), __FILE__, __LINE__, __METHOD__, 10);

        $current_week_start_date = TTDate::getBeginWeekEpoch($date_stamp, $start_week_day_id);
        $current_week_end_date = TTDate::getEndWeekEpoch($date_stamp, $start_week_day_id);

        $before_current_week_start_date = $period_start_date;
        $before_current_week_end_date = ($current_week_start_date != $period_start_date) ? ($current_week_start_date - 1) : $current_week_end_date;
        //Debug::text('   Before Current Week: Start: '. TTDate::getDate('DATE', $before_current_week_start_date ) .' End: '. TTDate::getDate('DATE', $before_current_week_end_date ), __FILE__, __LINE__, __METHOD__, 10);

        $after_current_week_start_date = ($current_week_end_date != $period_end_date) ? ($current_week_end_date + 1) : $current_week_start_date;
        $after_current_week_end_date = $period_end_date;
        //Debug::text('   After Current Week: Start: '. TTDate::getDate('DATE', $after_current_week_start_date ) .' End: '. TTDate::getDate('DATE', $after_current_week_end_date ), __FILE__, __LINE__, __METHOD__, 10);

        $is_first_week = false;
        if (TTDate::isTimeOverLap($date_stamp, $date_stamp, $before_current_week_start_date, $before_current_week_end_date) == true) {
            $is_first_week = true;
        }

        $is_last_week = false;
        if (TTDate::isTimeOverLap($date_stamp, $date_stamp, $after_current_week_start_date, $after_current_week_end_date) == true) {
            $is_last_week = true;
        }

        Debug::text(' Date: ' . TTDate::getDate('DATE', $date_stamp) . ' First PP Start Date: ' . TTDate::getDate('DATE+TIME', $first_pay_period_start_date) . ' Days: ' . $days_diff . ' Weeks: ' . $weekly_period_diff . ' Week In Period: ' . (int)$week_in_period . ' (Interval: ' . $weeks . ')', __FILE__, __LINE__, __METHOD__, 10);

        $retarr = array('start_date' => $period_start_date,
            'end_date' => $period_end_date,
            'is_first_week' => $is_first_week,
            'is_last_week' => $is_last_week,
            'current_week_start_date' => $current_week_start_date,
            'current_week_end_date' => $current_week_end_date,
            'before_current_week_start_date' => $before_current_week_start_date,
            'before_current_week_end_date' => $before_current_week_end_date,
            'after_current_week_start_date' => $after_current_week_start_date,
            'after_current_week_end_date' => $after_current_week_end_date,
        );

        //Debug::Arr( $retarr, 'OverTimePeriod Dates: Start: '. TTDate::getDate('DATE+TIME', $period_start_date ) .' End: '. TTDate::getDate('DATE+TIME', $period_end_date ), __FILE__, __LINE__, __METHOD__, 10);
        return $retarr;
    }

    public function getPreviousDayByUserTotalData($user_date_total_arr, $date_stamp)
    {
        $day_arr = $this->getDayArrayUserDateTotalData($user_date_total_arr);
        sort($day_arr);

        $retval = false;
        foreach ($day_arr as $day) {
            if ($day < $date_stamp) {
                $retval = $day;
            }
        }

        Debug::Text('Find day prior to: ' . $date_stamp . ' Retval: ' . $retval, __FILE__, __LINE__, __METHOD__, 10);
        return $retval;
    }

    public function calculatePremiumTimePolicy($date_stamp, $maximum_daily_total_time = null)
    {
        //Loop over all premium time policies calculating the pay codes
        $premium_time_policies = $this->sortPolicyByPayCodeDependancy($this->filterPremiumTimePolicy($date_stamp));
        if (is_array($premium_time_policies) and count($premium_time_policies) > 0) {
            foreach ($premium_time_policies as $pp_obj) {
                Debug::text('Found Premium Policy: Name: ' . $pp_obj->getName() . '(' . $pp_obj->getId() . ') Type: ' . $pp_obj->getType() . ' DateStamp: ' . TTDate::getDate('DATE', $date_stamp), __FILE__, __LINE__, __METHOD__, 10);

                if (!isset($this->contributing_shift_policy[$pp_obj->getContributingShiftPolicy()])) {
                    Debug::text(' ERROR: Contributing Shift Policy for Premium Policy: ' . $pp_obj->getName() . ' does not exist...', __FILE__, __LINE__, __METHOD__, 10);
                    continue;
                }

                //Include object_type_id=40 (premium time), so when calculating multiple WCB rates, the WCB premium policies can include evening/weekend shift differentials too.
                //However order matters in this case, so we have to calculate the proper order in filterPremiumTimePolicy()
                $user_date_total_rows = $this->compactMealAndBreakUserDateTotalObjects($this->filterUserDateTotalDataByContributingShiftPolicy($date_stamp, $date_stamp, $this->contributing_shift_policy[$pp_obj->getContributingShiftPolicy()], array(20, 25, 30, 40, 100, 110)));
                $user_date_total_rows_count = count($user_date_total_rows);
                if (is_array($user_date_total_rows) and $user_date_total_rows_count > 0) {
                    switch ($pp_obj->getType()) {
                        case 10: //Date/Time
                        case 100: //Advanced
                        case 90: //Holiday (coverts to Date/Time policy automatically)
                            if (is_object($this->pay_period_schedule_obj)) {
                                $maximum_shift_time = $this->pay_period_schedule_obj->getMaximumShiftTime();
                            }
                            if (!isset($maximum_shift_time) or $maximum_shift_time < 86400) {
                                $maximum_shift_time = 86400;
                            }

                            if ($pp_obj->getType() == 90) {
                                Debug::text(' Holiday Premium Policy...', __FILE__, __LINE__, __METHOD__, 10);
                                //Determine if the employee is eligible for holiday premium.
                                $holiday_obj = $this->filterHoliday($date_stamp);
                                if (!is_object($holiday_obj)) {
                                    $holiday_obj = $this->filterHoliday(($date_stamp - $maximum_shift_time));
                                    if (!is_object($holiday_obj)) {
                                        $holiday_obj = $this->filterHoliday(($date_stamp + $maximum_shift_time));
                                    }
                                }

                                if (is_object($holiday_obj)) {
                                    Debug::text(' Found Holiday: ' . $holiday_obj->getName() . ' Date: ' . TTDate::getDate('DATE', $holiday_obj->getDateStamp()) . ' Current Date: ' . TTDate::getDate('DATE', $date_stamp), __FILE__, __LINE__, __METHOD__, 10);
                                    if ($this->holiday_policy[$holiday_obj->getHolidayPolicyID()]->getForceOverTimePolicy() == true
                                        or $this->isEligibleForHoliday($date_stamp, $this->holiday_policy[$holiday_obj->getHolidayPolicyID()])
                                    ) {
                                        Debug::text(' User is Eligible for Holiday: ' . $holiday_obj->getName(), __FILE__, __LINE__, __METHOD__, 10);

                                        //Modify the premium policy in memory to make it like a date/time policy
                                        $pp_obj->setStartDate($holiday_obj->getDateStamp());
                                        $pp_obj->setEndDate($holiday_obj->getDateStamp());
                                        $pp_obj->setStartTime(TTDate::getBeginDayEpoch($holiday_obj->getDateStamp()));
                                        $pp_obj->setEndTime(TTDate::getEndDayEpoch($holiday_obj->getDateStamp()));
                                        $pp_obj->setSun(true);
                                        $pp_obj->setMon(true);
                                        $pp_obj->setTue(true);
                                        $pp_obj->setWed(true);
                                        $pp_obj->setThu(true);
                                        $pp_obj->setFri(true);
                                        $pp_obj->setSat(true);
                                        $pp_obj->setDailyTriggerTime(0);
                                        $pp_obj->setWeeklyTriggerTime(0);
                                    }
                                } else {
                                    //If a Date/Time premium was created first, with all days activated, then switched to a holiday type,
                                    //its still calculated on all days, even when its not a holiday.
                                    $pp_obj->setSun(false);
                                    $pp_obj->setMon(false);
                                    $pp_obj->setTue(false);
                                    $pp_obj->setWed(false);
                                    $pp_obj->setThu(false);
                                    $pp_obj->setFri(false);
                                    $pp_obj->setSat(false);
                                }
                                unset($holiday_obj);
                            }

                            //Make sure this is a valid day
                            //Take into account shifts that span midnight though, where one half of the shift is eligible for premium time.
                            //ie: Premium Policy starts 7AM to 7PM on Sat/Sun. Punches in at 9PM Friday and out at 9AM Sat, we need to check if both days are valid.
                            if ($pp_obj->isActive(($date_stamp - $maximum_shift_time), ($date_stamp + $maximum_shift_time), $this)) {
                                Debug::text(' Premium Policy Is Active On OR Around This Day.', __FILE__, __LINE__, __METHOD__, 10);

                                $total_daily_time_used = 0;
                                $daily_trigger_time = 0;
                                $maximum_daily_trigger_time = false;

                                if ($pp_obj->isHourRestricted() == true) {
                                    if ($pp_obj->getWeeklyTriggerTime() > 0 or $pp_obj->getMaximumWeeklyTriggerTime() > 0) {
                                        //Get Pay Period Schedule info
                                        if (is_object($this->pay_period_schedule_obj)) {
                                            $start_week_day_id = $this->pay_period_schedule_obj->getStartWeekDay();
                                        } else {
                                            $start_week_day_id = 0;
                                        }
                                        Debug::text('Start Week Day ID: ' . $start_week_day_id, __FILE__, __LINE__, __METHOD__, 10);

                                        $weekly_total_time = $this->getSumUserDateTotalData($this->filterUserDateTotalDataByContributingShiftPolicy(TTDate::getBeginWeekEpoch($date_stamp, $this->start_week_day_id), ($date_stamp - 86400), $this->contributing_shift_policy[$pp_obj->getContributingShiftPolicy()], array(20, 25, 30, 100, 110)));
                                        if ($weekly_total_time > $pp_obj->getWeeklyTriggerTime()) {
                                            $daily_trigger_time = 0;
                                        } else {
                                            $daily_trigger_time = ($pp_obj->getWeeklyTriggerTime() - $weekly_total_time);
                                        }
                                        Debug::text(' Weekly Trigger Time: ' . $daily_trigger_time . ' Raw Weekly Time: ' . $weekly_total_time, __FILE__, __LINE__, __METHOD__, 10);
                                    }

                                    if ($pp_obj->getDailyTriggerTime() > 0 and $pp_obj->getDailyTriggerTime() > $daily_trigger_time) {
                                        $daily_trigger_time = $pp_obj->getDailyTriggerTime();
                                    }

                                    if ($pp_obj->getMaximumDailyTriggerTime() > 0 or $pp_obj->getMaximumWeeklyTriggerTime() > 0) {
                                        $maximum_daily_trigger_time = ($pp_obj->getMaximumDailyTriggerTime() > 0) ? ($pp_obj->getMaximumDailyTriggerTime()) : false;
                                        $maximum_weekly_trigger_time = (isset($weekly_total_time) and $pp_obj->getMaximumWeeklyTriggerTime() > 0) ? ($pp_obj->getMaximumWeeklyTriggerTime() - $weekly_total_time) : false;

                                        Debug::text(' Maximum Daily: ' . $maximum_daily_trigger_time . ' Weekly: ' . $maximum_weekly_trigger_time . ' Daily Total Time Used: ' . $total_daily_time_used . ' Daily Trigger Time: ' . $daily_trigger_time, __FILE__, __LINE__, __METHOD__, 10);
                                        if ($maximum_daily_trigger_time > 0 and ($maximum_weekly_trigger_time === false or $maximum_daily_trigger_time < $maximum_weekly_trigger_time)) {
                                            $pp_obj->setMaximumTime($maximum_daily_trigger_time); //Temporarily set the maximum time in memory so it doesn't exceed the maximum daily trigger time.
                                            Debug::text(' Set Daily Maximum Time to: ' . $pp_obj->getMaximumTime(), __FILE__, __LINE__, __METHOD__, 10);
                                        } else {
                                            if ($maximum_weekly_trigger_time !== false and ($maximum_weekly_trigger_time <= 0 or ($maximum_weekly_trigger_time < $daily_trigger_time))) {
                                                Debug::text(' Exceeded Weekly Maximum Time to: ' . $pp_obj->getMaximumTime() . ' Skipping...', __FILE__, __LINE__, __METHOD__, 10);
                                                continue;
                                            }

                                            if ($maximum_weekly_trigger_time < $pp_obj->getMaximumTime()) {
                                                $pp_obj->setMaximumTime($maximum_weekly_trigger_time); //Temporarily set the maximum time in memory so it doesn't exceed the maximum daily trigger time.
                                            }
                                            Debug::text(' Set Weekly Maximum Time to: ' . $pp_obj->getMaximumTime(), __FILE__, __LINE__, __METHOD__, 10);
                                            $maximum_daily_trigger_time = $maximum_weekly_trigger_time;
                                        }
                                        unset($maximum_weekly_trigger_time);
                                    }
                                }
                                Debug::text(' Daily Trigger Time: ' . $daily_trigger_time . ' Max: ' . $maximum_daily_trigger_time, __FILE__, __LINE__, __METHOD__, 10);

                                $i = 1;
                                foreach ($user_date_total_rows as $udt_key => $udt_obj) {
                                    Debug::text('UserDateTotal ID: ' . $udt_obj->getID() . ' Total Time: ' . $udt_obj->getTotalTime() . ' I: ' . $i, __FILE__, __LINE__, __METHOD__, 10);

                                    //Ignore incomplete punches
                                    if ($udt_obj->getTotalTime() == 0) {
                                        $i++; //Be sure to incrememnt the counter so minimum time can be applied properly if one record is total_time=0 and the other is not.
                                        continue;
                                    }

                                    //How do we handle actual shifts for premium time?
                                    //So if premium policy starts at 1PM for shifts, to not
                                    //include employees who return from lunch at 1:30PM.
                                    //Create a function that takes all punches for a day, and returns
                                    //the first in and last out time for a given shift when taking
                                    //into account minimum time between shifts, as well as the total time for that shift.
                                    //We can then use that time for ActiveTime on premium policies, and determine if a
                                    //punch falls within the active time, then we add it to the total.
                                    if (($pp_obj->getIncludePartialPunch() == true or $pp_obj->isTimeRestricted() == true)) {
                                        Debug::text('Time Restricted Premium Policy... Using Start/End timestamps...', __FILE__, __LINE__, __METHOD__, 10);

                                        //Do this outside the user_date_total_rows loop
                                        if ($pp_obj->getIncludePartialPunch() == false) {
                                            $shift_data = $this->getShiftData($user_date_total_rows, $udt_obj->getStartTimeStamp(), 'nearest_shift', null, $pp_obj->getMinimumTimeBetweenShift());
                                        }

                                        if ($pp_obj->getIncludePartialPunch() == true) {
                                            $punch_times['in'] = $udt_obj->getStartTimeStamp();
                                            $punch_times['out'] = $udt_obj->getEndTimeStamp();
                                        } elseif (isset($shift_data) and is_array($shift_data) and isset($shift_data['first_in']) and isset($user_date_total_rows[$shift_data['first_in']])) {
                                            $punch_times['in'] = $user_date_total_rows[$shift_data['first_in']]->getStartTimeStamp();
                                            $punch_times['out'] = $user_date_total_rows[$shift_data['last_out']]->getEndTimeStamp();
                                        } else {
                                            Debug::text('ERROR: No punch times...', __FILE__, __LINE__, __METHOD__, 10);
                                        }

                                        //How do we handle "shifts" when we can include absence pay codes?
                                        //When its time restricted we ignore absences or any record without in/out times.
                                        $punch_total_time = 0;
                                        if (isset($punch_times) and count($punch_times) == 2
                                            and $punch_times['in'] != '' and $punch_times['out'] != ''
                                        ) {
                                            if (($pp_obj->isActiveDate($punch_times['in']) == true or $pp_obj->isActiveDate($punch_times['out']) == true)
                                                and ($pp_obj->isActive($punch_times['in'], $punch_times['out'], $this) == true)
                                                and $pp_obj->isActiveTime($punch_times['in'], $punch_times['out'], $this) == true
                                            ) {
                                                //Debug::Arr($punch_times, 'Punch Times: ', __FILE__, __LINE__, __METHOD__, 10);
                                                $punch_total_time = $pp_obj->getPartialPunchTotalTime($punch_times['in'], $punch_times['out'], $udt_obj->getTotalTime(), $this);
                                                Debug::text('Valid Punch pair in active time, Partial Punch Total Time: ' . $punch_total_time, __FILE__, __LINE__, __METHOD__, 10);
                                            } else {
                                                Debug::text('InValid Punch Pair or outside Active Time...', __FILE__, __LINE__, __METHOD__, 10);
                                            }
                                        } else {
                                            Debug::text('No timestamps...', __FILE__, __LINE__, __METHOD__, 10);
                                        }
                                        unset($punch_times);
                                    } elseif ($pp_obj->isActive($udt_obj->getDateStamp(), null, $this) == true) {
                                        $punch_total_time = $udt_obj->getTotalTime();
                                    } else {
                                        $punch_total_time = 0;
                                    }

                                    //Why is $tmp_punch_total_time not just $punch_total_time? Are the partial punches somehow separate from the meal/break calculation?
                                    //Yes, because tmp_punch_total_time is the DAILY total time used, whereas punch_total_time can be a partial shift. Without this the daily trigger time won't work.
                                    $tmp_punch_total_time = $udt_obj->getTotalTime();
                                    Debug::text('aPunch Total Time: ' . $punch_total_time . ' TMP Punch Total Time: ' . $tmp_punch_total_time, __FILE__, __LINE__, __METHOD__, 10);

                                    $total_daily_time_used += $tmp_punch_total_time;
                                    Debug::text('Daily Total Time Used: ' . $total_daily_time_used . ' Maximum Trigger Time: ' . $maximum_daily_trigger_time . ' This Record: ' . ($total_daily_time_used - $tmp_punch_total_time), __FILE__, __LINE__, __METHOD__, 10);
                                    Debug::text('Daily Trigger Time: ' . $daily_trigger_time . ' TMP: ' . $tmp_punch_total_time, __FILE__, __LINE__, __METHOD__, 10);

                                    //That way if the policy is active after 7.5hrs, punch time of exactly 7.5hrs will still
                                    //activate the policy, rather then requiring 7.501hrs+
                                    //Make sure we allow for UDT records that are just negative values for lunch/break audo-deduct.
                                    //  How to handle auto-deduct/auto-add meal polcies for this and Shift Differential/Callback premiums?
                                    if (
                                    (
                                        (
                                            ($punch_total_time > 0 and $total_daily_time_used > $daily_trigger_time)
                                            or
                                            ($punch_total_time < 0 and ($total_daily_time_used - abs($tmp_punch_total_time)) > $daily_trigger_time)
                                        )
                                        and ($maximum_daily_trigger_time === false or ($maximum_daily_trigger_time !== false and ($total_daily_time_used - abs($tmp_punch_total_time)) < $maximum_daily_trigger_time))
                                    )
                                    ) {
                                        /*
                                    //The below helped deal with negative time entries, before compactin meal/break policy function was added.
                                    if (
                                            (
                                                $punch_total_time > 0
                                                AND $total_daily_time_used > $daily_trigger_time
                                                AND ( $maximum_daily_trigger_time === FALSE OR ( $maximum_daily_trigger_time !== FALSE AND ($total_daily_time_used - abs($tmp_punch_total_time) ) < $maximum_daily_trigger_time ) )
                                            )
                                            OR
                                            (
                                                (
                                                    $punch_total_time < 0 AND $daily_trigger_time == 0
                                                    AND $total_daily_time_used > $daily_trigger_time
                                                    AND ( $maximum_daily_trigger_time === FALSE OR ( $maximum_daily_trigger_time !== FALSE AND ($total_daily_time_used ) < $maximum_daily_trigger_time ) )
                                                )
                                                OR
                                                (
                                                    $punch_total_time < 0 AND $daily_trigger_time > 0
                                                    AND ( $total_daily_time_used - $tmp_punch_total_time ) > $daily_trigger_time
                                                )
                                            )
                                        ) {
                                        */
                                        Debug::text('Past Trigger Time!! ' . ($total_daily_time_used - $tmp_punch_total_time), __FILE__, __LINE__, __METHOD__, 10);

                                        //Calculate how far past trigger time we are.
                                        $past_trigger_time = ($total_daily_time_used - $daily_trigger_time);
                                        if ($punch_total_time > $past_trigger_time) {
                                            //if ( $past_trigger_time > 0 AND $daily_trigger_time > 0 AND $punch_total_time > $past_trigger_time ) { //This helped deal with negative time entries, before compactin meal/break policy function was added.
                                            $punch_total_time = $past_trigger_time;
                                            Debug::text('Using Past Trigger Time as punch total time: ' . $past_trigger_time, __FILE__, __LINE__, __METHOD__, 10);
                                        } else {
                                            Debug::text('NOT Using Past Trigger Time as punch total time: ' . $past_trigger_time, __FILE__, __LINE__, __METHOD__, 10);
                                        }

                                        //If we are close to exceeding the maximum daily/weekly time, just use the remaining time.
                                        if ($maximum_daily_trigger_time > 0 and $total_daily_time_used > $maximum_daily_trigger_time) {
                                            Debug::text('Using New Maximum Trigger Time as punch total time: ' . $maximum_daily_trigger_time . '(' . $total_daily_time_used . ')', __FILE__, __LINE__, __METHOD__, 10);
                                            $punch_total_time = ($punch_total_time - ($total_daily_time_used - $maximum_daily_trigger_time));
                                        } else {
                                            Debug::text('NOT Using New Maximum Trigger Time as punch total time: ' . $maximum_daily_trigger_time . '(' . $total_daily_time_used . ')', __FILE__, __LINE__, __METHOD__, 10);
                                        }

                                        $total_time = $punch_total_time;
                                        if ($pp_obj->getMinimumTime() > 0 or $pp_obj->getMaximumTime() > 0) {
                                            $premium_policy_daily_total_time = $this->getSumUserDateTotalData($this->filterUserDateTotalDataByPayCodeIDs($date_stamp, $date_stamp, $pp_obj->getPayCode()));
                                            Debug::text(' Premium Policy Daily Total Time: ' . $premium_policy_daily_total_time . ' Minimum Time: ' . $pp_obj->getMinimumTime() . ' Maximum Time: ' . $pp_obj->getMaximumTime() . ' Total Time: ' . $total_time, __FILE__, __LINE__, __METHOD__, 10);

                                            if ($pp_obj->getMinimumTime() > 0) {
                                                //FIXME: Split the minimum time up between all the punches somehow.
                                                //Apply the minimum time on the last punch, otherwise if there are two punch pairs of 15min each
                                                //and a 1hr minimum time, if the minimum time is applied to the first, it will be 1hr and 15min
                                                //for the day. If its applied to the last it will be just 1hr.
                                                //Min & Max time is based on the shift time, rather then per punch pair time.
                                                //FIXME: If there is a minimum time set to say 9hrs, and the punches go like this:
                                                // In: 7:00AM Out: 3:00:PM, Out: 3:30PM (missing 2nd In Punch), the minimum time won't be calculated due to the invalid punch pair.
                                                if ($i == $user_date_total_rows_count and bcadd($premium_policy_daily_total_time, $total_time) < $pp_obj->getMinimumTime()) {
                                                    $total_time = bcsub($pp_obj->getMinimumTime(), $premium_policy_daily_total_time);
                                                }
                                            }

                                            Debug::text(' Total Time After Minimum is applied: ' . $total_time, __FILE__, __LINE__, __METHOD__, 10);
                                            if ($pp_obj->getMaximumTime() > 0) {
                                                //Min & Max time is based on the shift time, rather then per punch pair time.
                                                if (bcadd($premium_policy_daily_total_time, $total_time) > $pp_obj->getMaximumTime()) {
                                                    Debug::text(' bMore than Maximum Time...', __FILE__, __LINE__, __METHOD__, 10);
                                                    $total_time = bcsub($total_time, bcsub(bcadd($premium_policy_daily_total_time, $total_time), $pp_obj->getMaximumTime()));
                                                }
                                            }
                                        }

                                        Debug::text(' Premium Punch Total Time: ' . $total_time, __FILE__, __LINE__, __METHOD__, 10);
                                        if ($total_time != 0) { //Need to handle negative values too for things like lunch auto-deduct.
                                            Debug::text(' Applying Premium Time!: ' . $total_time, __FILE__, __LINE__, __METHOD__, 10);

                                            $create_udt_record = false;
                                            if ($pp_obj->getType() == 100) {
                                                //Check Shift Differential criteria *AFTER* calculatating daily/weekly time, as the shift differential
                                                //applies to the resulting time calculation, not the daily/weekly time calculation. Daily/Weekly should always include all time.
                                                //This is fundamentally different than the Shift Differential premium policy type.
                                                if ($this->contributing_shift_policy[$pp_obj->getContributingShiftPolicy()]->checkIndividualDifferentialCriteria($pp_obj->getBranchSelectionType(), $pp_obj->getExcludeDefaultBranch(), $udt_obj->getBranch(), $pp_obj->getBranch(), $this->getUserObject()->getDefaultBranch())) {
                                                    //Debug::text(' Shift Differential... Meets Branch Criteria! Select Type: '. $pp_obj->getBranchSelectionType() .' Exclude Default Branch: '. (int)$pp_obj->getExcludeDefaultBranch() .' Default Branch: '.  $this->getUserObject()->getDefaultBranch(), __FILE__, __LINE__, __METHOD__, 10);
                                                    if ($this->contributing_shift_policy[$pp_obj->getContributingShiftPolicy()]->checkIndividualDifferentialCriteria($pp_obj->getDepartmentSelectionType(), $pp_obj->getExcludeDefaultDepartment(), $udt_obj->getDepartment(), $pp_obj->getDepartment(), $this->getUserObject()->getDefaultDepartment())) {
                                                        //Debug::text(' Shift Differential... Meets Department Criteria! Select Type: '. $pp_obj->getDepartmentSelectionType() .' Exclude Default Department: '. (int)$pp_obj->getExcludeDefaultDepartment() .' Default Department: '.  $this->getUserObject()->getDefaultDepartment(), __FILE__, __LINE__, __METHOD__, 10);
                                                        $job_group = (is_object($udt_obj->getJobObject())) ? $udt_obj->getJobObject()->getGroup() : null;
                                                        if ($this->contributing_shift_policy[$pp_obj->getContributingShiftPolicy()]->checkIndividualDifferentialCriteria($pp_obj->getJobGroupSelectionType(), null, $job_group, $pp_obj->getJobGroup())) {
                                                            //Debug::text(' Shift Differential... Meets Job Group Criteria! Select Type: '. $pp_obj->getJobGroupSelectionType(), __FILE__, __LINE__, __METHOD__, 10);
                                                            if ($this->contributing_shift_policy[$pp_obj->getContributingShiftPolicy()]->checkIndividualDifferentialCriteria($pp_obj->getJobSelectionType(), $pp_obj->getExcludeDefaultJob(), $udt_obj->getJob(), $pp_obj->getJob(), $this->getUserObject()->getDefaultJob())) {
                                                                //Debug::text(' Shift Differential... Meets Job Criteria! Select Type: '. $pp_obj->getJobSelectionType(), __FILE__, __LINE__, __METHOD__, 10);
                                                                $job_item_group = (is_object($udt_obj->getJobItemObject())) ? $udt_obj->getJobItemObject()->getGroup() : null;
                                                                if ($this->contributing_shift_policy[$pp_obj->getContributingShiftPolicy()]->checkIndividualDifferentialCriteria($pp_obj->getJobItemGroupSelectionType(), null, $job_item_group, $pp_obj->getJobItemGroup())) {
                                                                    //Debug::text(' Shift Differential... Meets Task Group Criteria! Select Type: '. $pp_obj->getJobItemGroupSelectionType(), __FILE__, __LINE__, __METHOD__, 10);
                                                                    if ($this->contributing_shift_policy[$pp_obj->getContributingShiftPolicy()]->checkIndividualDifferentialCriteria($pp_obj->getJobItemSelectionType(), $pp_obj->getExcludeDefaultJobItem(), $udt_obj->getJobItem(), $pp_obj->getJobItem(), $this->getUserObject()->getDefaultJobItem())) {
                                                                        //Debug::text(' Shift Differential... Meets Task Criteria! Select Type: '. $pp_obj->getJobSelectionType(), __FILE__, __LINE__, __METHOD__, 10);
                                                                        $create_udt_record = true;
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            } else {
                                                $create_udt_record = true;
                                            }

                                            if ($create_udt_record == true) {
                                                Debug::text('Generating UserDateTotal object from Premium Time Policy, ID: ' . $this->user_date_total_insert_id . ' Object Type ID: ' . 40 . ' Pay Code ID: ' . (int)$pp_obj->getPayCode() . ' Total Time: ' . $total_time, __FILE__, __LINE__, __METHOD__, 10);
                                                if (!isset($this->user_date_total[$this->user_date_total_insert_id])) {
                                                    $udtf = TTnew('UserDateTotalFactory');
                                                    $udtf->setUser($this->getUserObject()->getId());
                                                    $udtf->setDateStamp($date_stamp);
                                                    $udtf->setObjectType(40); //Premium Time
                                                    $udtf->setSourceObject((int)$pp_obj->getId());
                                                    $udtf->setPayCode((int)$pp_obj->getPayCode());

                                                    $udtf->setBranch((int)$udt_obj->getBranch());
                                                    $udtf->setDepartment((int)$udt_obj->getDepartment());
                                                    $udtf->setJob((int)$udt_obj->getJob());
                                                    $udtf->setJobItem((int)$udt_obj->getJobItem());

                                                    if ($udt_obj->getStartTimeStamp() != '' and $udt_obj->getEndTimeStamp() != '') {
                                                        $udtf->setStartType($udt_obj->getStartType());
                                                        $udtf->setEndType($udt_obj->getEndType());
                                                        $udtf->setStartTimeStamp(($udt_obj->getEndTimeStamp() - $total_time));
                                                        $udtf->setEndTimeStamp(($udtf->getStartTimeStamp() + $total_time));
                                                        //Debug::text('        Current Start Time Stamp: '. TTDate::getDate('DATE+TIME', $udt_obj->getStartTimeStamp() ) .' End: '.  TTDate::getDate('DATE+TIME', $udt_obj->getEndTimeStamp() ) .' Covered Time: '. $covered_time .' Adjust: '. $adjust_covered_time, __FILE__, __LINE__, __METHOD__, 10);
                                                        //Debug::text('        Premium Start Time Stamp: '. TTDate::getDate('DATE+TIME', $udtf->getStartTimeStamp() ) .' End: '.  TTDate::getDate('DATE+TIME', $udtf->getEndTimeStamp() ), __FILE__, __LINE__, __METHOD__, 10);
                                                    }

                                                    $udtf->setQuantity($udt_obj->getQuantity());
                                                    $udtf->setBadQuantity($udt_obj->getBadQuantity());
                                                    $udtf->setTotalTime($total_time);

                                                    $udtf->setBaseHourlyRate($this->getBaseHourlyRate($pp_obj->getPayFormulaPolicy(), $udtf->getPayCode(), $date_stamp, $udt_obj->getHourlyRate()));
                                                    $udtf->setHourlyRate($this->getHourlyRate($pp_obj->getPayFormulaPolicy(), $udtf->getPayCode(), $date_stamp, $udtf->getBaseHourlyRate()));
                                                    $udtf->setHourlyRateWithBurden($this->getHourlyRateWithBurden($pp_obj->getPayFormulaPolicy(), $udtf->getPayCode(), $date_stamp, $udtf->getHourlyRate()));

                                                    $udtf->setEnableCalcSystemTotalTime(false);
                                                    $udtf->preSave(); //Call this so TotalTimeAmount is calculated immediately, as we don't save these records until later.

                                                    if ($this->isOverriddenUserDateTotalObject($udtf) == false) {
                                                        //Don't save the record, just add it to the existing array, so it can be included in other calculations.
                                                        //We will save these records at the end.
                                                        $this->user_date_total[$this->user_date_total_insert_id] = $udtf;
                                                        $this->user_date_total_insert_id--;
                                                    }
                                                } else {
                                                    Debug::text('ERROR: Duplicate starting ID for some reason! ' . $this->user_date_total_insert_id, __FILE__, __LINE__, __METHOD__, 10);
                                                }
                                            }
                                        } else {
                                            Debug::text(' Premium Punch Total Time is 0...', __FILE__, __LINE__, __METHOD__, 10);
                                        }
                                    } else {
                                        Debug::text('Not Past Trigger Time Yet or Punch Time is 0...', __FILE__, __LINE__, __METHOD__, 10);
                                    }

                                    $i++;
                                }
                            }
                            unset($udtlf, $udt_obj);
                            break;
                        case 20: //Differential
                            Debug::text(' Differential Premium Policy...', __FILE__, __LINE__, __METHOD__, 10);

                            $i = 1;
                            foreach ($user_date_total_rows as $udt_key => $udt_obj) {
                                //Ignore incomplete punches
                                if ($udt_obj->getTotalTime() == 0) {
                                    continue;
                                }

                                if ($this->contributing_shift_policy[$pp_obj->getContributingShiftPolicy()]->checkIndividualDifferentialCriteria($pp_obj->getBranchSelectionType(), $pp_obj->getExcludeDefaultBranch(), $udt_obj->getBranch(), $pp_obj->getBranch(), $this->getUserObject()->getDefaultBranch())) {
                                    //Debug::text(' Shift Differential... Meets Branch Criteria! Select Type: '. $pp_obj->getBranchSelectionType() .' Exclude Default Branch: '. (int)$pp_obj->getExcludeDefaultBranch() .' Default Branch: '.  $this->getUserObject()->getDefaultBranch(), __FILE__, __LINE__, __METHOD__, 10);
                                    if ($this->contributing_shift_policy[$pp_obj->getContributingShiftPolicy()]->checkIndividualDifferentialCriteria($pp_obj->getDepartmentSelectionType(), $pp_obj->getExcludeDefaultDepartment(), $udt_obj->getDepartment(), $pp_obj->getDepartment(), $this->getUserObject()->getDefaultDepartment())) {
                                        //Debug::text(' Shift Differential... Meets Department Criteria! Select Type: '. $pp_obj->getDepartmentSelectionType() .' Exclude Default Department: '. (int)$pp_obj->getExcludeDefaultDepartment() .' Default Department: '.  $this->getUserObject()->getDefaultDepartment(), __FILE__, __LINE__, __METHOD__, 10);
                                        $job_group = (is_object($udt_obj->getJobObject())) ? $udt_obj->getJobObject()->getGroup() : null;
                                        if ($this->contributing_shift_policy[$pp_obj->getContributingShiftPolicy()]->checkIndividualDifferentialCriteria($pp_obj->getJobGroupSelectionType(), null, $job_group, $pp_obj->getJobGroup())) {
                                            //Debug::text(' Shift Differential... Meets Job Group Criteria! Select Type: '. $pp_obj->getJobGroupSelectionType(), __FILE__, __LINE__, __METHOD__, 10);
                                            if ($this->contributing_shift_policy[$pp_obj->getContributingShiftPolicy()]->checkIndividualDifferentialCriteria($pp_obj->getJobSelectionType(), $pp_obj->getExcludeDefaultJob(), $udt_obj->getJob(), $pp_obj->getJob(), $this->getUserObject()->getDefaultJob())) {
                                                //Debug::text(' Shift Differential... Meets Job Criteria! Select Type: '. $pp_obj->getJobSelectionType(), __FILE__, __LINE__, __METHOD__, 10);
                                                $job_item_group = (is_object($udt_obj->getJobItemObject())) ? $udt_obj->getJobItemObject()->getGroup() : null;
                                                if ($this->contributing_shift_policy[$pp_obj->getContributingShiftPolicy()]->checkIndividualDifferentialCriteria($pp_obj->getJobItemGroupSelectionType(), null, $job_item_group, $pp_obj->getJobItemGroup())) {
                                                    //Debug::text(' Shift Differential... Meets Task Group Criteria! Select Type: '. $pp_obj->getJobItemGroupSelectionType(), __FILE__, __LINE__, __METHOD__, 10);
                                                    if ($this->contributing_shift_policy[$pp_obj->getContributingShiftPolicy()]->checkIndividualDifferentialCriteria($pp_obj->getJobItemSelectionType(), $pp_obj->getExcludeDefaultJobItem(), $udt_obj->getJobItem(), $pp_obj->getJobItem(), $this->getUserObject()->getDefaultJobItem())) {
                                                        //Debug::text(' Shift Differential... Meets Task Criteria! Select Type: '. $pp_obj->getJobSelectionType(), __FILE__, __LINE__, __METHOD__, 10);

                                                        $premium_policy_daily_total_time = 0;
                                                        $punch_total_time = $udt_obj->getTotalTime();
                                                        $total_time = 0;

                                                        $total_time = $punch_total_time;
                                                        if ($pp_obj->getMinimumTime() > 0 or $pp_obj->getMaximumTime() > 0) {
                                                            $premium_policy_daily_total_time = $this->getSumUserDateTotalData($this->filterUserDateTotalDataByPayCodeIDs($date_stamp, $date_stamp, $pp_obj->getPayCode()));
                                                            Debug::text(' Premium Policy Daily Total Time: ' . $premium_policy_daily_total_time . ' Minimum Time: ' . $pp_obj->getMinimumTime() . ' Maximum Time: ' . $pp_obj->getMaximumTime(), __FILE__, __LINE__, __METHOD__, 10);

                                                            if ($pp_obj->getMinimumTime() > 0) {
                                                                //FIXME: Split the minimum time up between all the punches somehow.
                                                                if ($i == $user_date_total_rows_count and bcadd($premium_policy_daily_total_time, $total_time) < $pp_obj->getMinimumTime()) {
                                                                    $total_time = bcsub($pp_obj->getMinimumTime(), $premium_policy_daily_total_time);
                                                                }
                                                            } else {
                                                                $total_time = $punch_total_time;
                                                            }

                                                            Debug::text(' Total Time After Minimum is applied: ' . $total_time, __FILE__, __LINE__, __METHOD__, 10);
                                                            if ($pp_obj->getMaximumTime() > 0) {
                                                                //Min & Max time is based on the shift time, rather then per punch pair time.
                                                                if (bcadd($premium_policy_daily_total_time, $total_time) > $pp_obj->getMaximumTime()) {
                                                                    $total_time = bcsub($total_time, bcsub(bcadd($premium_policy_daily_total_time, $total_time), $pp_obj->getMaximumTime()));
                                                                    Debug::text(' bMore than Maximum Time... new Total Time: ' . $total_time, __FILE__, __LINE__, __METHOD__, 10);
                                                                }
                                                            }
                                                        } else {
                                                            $total_time = $punch_total_time;
                                                        }

                                                        Debug::text(' Premium Punch Total Time: ' . $total_time, __FILE__, __LINE__, __METHOD__, 10);
                                                        if ($total_time != 0) { //Need to handle negative values too for things like lunch auto-deduct.
                                                            Debug::text('Generating UserDateTotal object from Premium Time Policy, ID: ' . $this->user_date_total_insert_id . ' Object Type ID: ' . 40 . ' Pay Code ID: ' . (int)$pp_obj->getPayCode() . ' Total Time: ' . $total_time . ' UDT ObjectType: ' . $udt_obj->getObjectType(), __FILE__, __LINE__, __METHOD__, 10);
                                                            if (!isset($this->user_date_total[$this->user_date_total_insert_id])) {
                                                                $udtf = TTnew('UserDateTotalFactory');
                                                                $udtf->setUser($this->getUserObject()->getId());
                                                                $udtf->setDateStamp($date_stamp);
                                                                $udtf->setObjectType(40); //Premium Time
                                                                $udtf->setSourceObject((int)$pp_obj->getId());
                                                                $udtf->setPayCode((int)$pp_obj->getPayCode());

                                                                $udtf->setBranch((int)$udt_obj->getBranch());
                                                                $udtf->setDepartment((int)$udt_obj->getDepartment());
                                                                $udtf->setJob((int)$udt_obj->getJob());
                                                                $udtf->setJobItem((int)$udt_obj->getJobItem());

                                                                if ($udt_obj->getStartTimeStamp() != '' and $udt_obj->getEndTimeStamp() != '') {
                                                                    $udtf->setStartType($udt_obj->getStartType());
                                                                    $udtf->setEndType($udt_obj->getEndType());
                                                                    $udtf->setStartTimeStamp($udt_obj->getStartTimeStamp());
                                                                    $udtf->setEndTimeStamp(($udtf->getStartTimeStamp() + $total_time));
                                                                }

                                                                $udtf->setQuantity($udt_obj->getQuantity());
                                                                $udtf->setBadQuantity($udt_obj->getBadQuantity());
                                                                $udtf->setTotalTime($total_time);

                                                                $udtf->setBaseHourlyRate($this->getBaseHourlyRate($pp_obj->getPayFormulaPolicy(), $udtf->getPayCode(), $date_stamp, $udt_obj->getHourlyRate()));
                                                                $udtf->setHourlyRate($this->getHourlyRate($pp_obj->getPayFormulaPolicy(), $udtf->getPayCode(), $date_stamp, $udtf->getBaseHourlyRate()));
                                                                $udtf->setHourlyRateWithBurden($this->getHourlyRateWithBurden($pp_obj->getPayFormulaPolicy(), $udtf->getPayCode(), $date_stamp, $udtf->getHourlyRate()));

                                                                $udtf->setEnableCalcSystemTotalTime(false);
                                                                $udtf->preSave(); //Call this so TotalTimeAmount is calculated immediately, as we don't save these records until later.

                                                                if ($this->isOverriddenUserDateTotalObject($udtf) == false) {
                                                                    //Don't save the record, just add it to the existing array, so it can be included in other calculations.
                                                                    //We will save these records at the end.
                                                                    $this->user_date_total[$this->user_date_total_insert_id] = $udtf;
                                                                    $this->user_date_total_insert_id--;
                                                                }
                                                            } else {
                                                                Debug::text('ERROR: Duplicate starting ID for some reason! ' . $this->user_date_total_insert_id, __FILE__, __LINE__, __METHOD__, 10);
                                                            }
                                                        } else {
                                                            Debug::text(' Premium Punch Total Time is 0...', __FILE__, __LINE__, __METHOD__, 10);
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }

                                    $i++;
                                }
                            }
                            break;
                        case 30: //Meal/Break
                            Debug::text(' Meal/Break Premium Policy...', __FILE__, __LINE__, __METHOD__, 10);
                            if ($pp_obj->getDailyTriggerTime() == 0
                                or ($pp_obj->getDailyTriggerTime() > 0 and $maximum_daily_total_time >= $pp_obj->getDailyTriggerTime())
                            ) {
                                $prev_punch_timestamp = null;
                                $maximum_time_worked_without_break = 0;

                                foreach ($user_date_total_rows as $udt_key => $udt_obj) {
                                    //Ignore incomplete punches
                                    if ($udt_obj->getTotalTime() == 0) {
                                        continue;
                                    }

                                    //Debug::text(' UDT Start Time: '. TTDate::getDate('DATE+TIME', $udt_obj->getStartTimeStamp() ) .' End Time: '. TTDate::getDate('DATE+TIME', $udt_obj->getEndTimeStamp() ), __FILE__, __LINE__, __METHOD__, 10);

                                    //Total Punch Time
                                    $total_punch_pair_time = ($udt_obj->getEndTimeStamp() - $udt_obj->getStartTimeStamp());
                                    $maximum_time_worked_without_break += $total_punch_pair_time;
                                    Debug::text('Total Punch Pair Time: ' . $total_punch_pair_time . ' Maximum No Break Time: ' . $maximum_time_worked_without_break, __FILE__, __LINE__, __METHOD__, 10);

                                    if ($prev_punch_timestamp !== null) {
                                        $break_time = ($udt_obj->getStartTimeStamp() - $prev_punch_timestamp);
                                        if ($break_time >= $pp_obj->getMinimumBreakTime()) { //Use >= here, so they can use 10mins as minimum break time, and if someone takes 10mins, its still considered a break.
                                            Debug::text('Exceeded Minimum Break Time: ' . $break_time . ' Minimum: ' . $pp_obj->getMinimumBreakTime(), __FILE__, __LINE__, __METHOD__, 10);
                                            $maximum_time_worked_without_break = 0;
                                        }
                                    }

                                    if ($maximum_time_worked_without_break > $pp_obj->getMaximumNoBreakTime()) {
                                        Debug::text('Exceeded maximum no break time!', __FILE__, __LINE__, __METHOD__, 10);

                                        if ($pp_obj->getMaximumTime() > $pp_obj->getMinimumTime()) {
                                            $total_time = $pp_obj->getMaximumTime();
                                        } else {
                                            $total_time = $pp_obj->getMinimumTime();
                                        }

                                        if ($total_time != 0) { //Need to handle negative values too for things like lunch auto-deduct.
                                            Debug::text('Generating UserDateTotal object from Premium Time Policy, ID: ' . $this->user_date_total_insert_id . ' Object Type ID: ' . 40 . ' Pay Code ID: ' . (int)$pp_obj->getPayCode() . ' Total Time: ' . $total_time, __FILE__, __LINE__, __METHOD__, 10);
                                            if (!isset($this->user_date_total[$this->user_date_total_insert_id])) {
                                                $udtf = TTnew('UserDateTotalFactory');
                                                $udtf->setUser($this->getUserObject()->getId());
                                                $udtf->setDateStamp($date_stamp);
                                                $udtf->setObjectType(40); //Premium Time
                                                $udtf->setSourceObject((int)$pp_obj->getId());
                                                $udtf->setPayCode((int)$pp_obj->getPayCode());

                                                $udtf->setBranch((int)$udt_obj->getBranch());
                                                $udtf->setDepartment((int)$udt_obj->getDepartment());
                                                $udtf->setJob((int)$udt_obj->getJob());
                                                $udtf->setJobItem((int)$udt_obj->getJobItem());

                                                if ($udt_obj->getStartTimeStamp() != '' and $udt_obj->getEndTimeStamp() != '') {
                                                    $udtf->setStartType($udt_obj->getStartType());
                                                    $udtf->setEndType($udt_obj->getEndType());
                                                    $udtf->setStartTimeStamp($udt_obj->getStartTimeStamp());
                                                    $udtf->setEndTimeStamp(($udtf->getStartTimeStamp() + $total_time));
                                                }

                                                $udtf->setQuantity($udt_obj->getQuantity());
                                                $udtf->setBadQuantity($udt_obj->getBadQuantity());
                                                $udtf->setTotalTime($total_time);

                                                $udtf->setBaseHourlyRate($this->getBaseHourlyRate($pp_obj->getPayFormulaPolicy(), $udtf->getPayCode(), $date_stamp, $udt_obj->getHourlyRate()));
                                                $udtf->setHourlyRate($this->getHourlyRate($pp_obj->getPayFormulaPolicy(), $udtf->getPayCode(), $date_stamp, $udtf->getBaseHourlyRate()));
                                                $udtf->setHourlyRateWithBurden($this->getHourlyRateWithBurden($pp_obj->getPayFormulaPolicy(), $udtf->getPayCode(), $date_stamp, $udtf->getHourlyRate()));

                                                $udtf->setEnableCalcSystemTotalTime(false);
                                                $udtf->preSave(); //Call this so TotalTimeAmount is calculated immediately, as we don't save these records until later.

                                                if ($this->isOverriddenUserDateTotalObject($udtf) == false) {
                                                    //Don't save the record, just add it to the existing array, so it can be included in other calculations.
                                                    //We will save these records at the end.
                                                    $this->user_date_total[$this->user_date_total_insert_id] = $udtf;
                                                    $this->user_date_total_insert_id--;
                                                }
                                            } else {
                                                Debug::text('ERROR: Duplicate starting ID for some reason! ' . $this->user_date_total_insert_id, __FILE__, __LINE__, __METHOD__, 10);
                                            }

                                            break; //Stop looping through punches.
                                        }
                                    } else {
                                        Debug::text('Did not exceed maximum no break time yet... Time: ' . $maximum_time_worked_without_break, __FILE__, __LINE__, __METHOD__, 10);
                                    }

                                    $prev_punch_timestamp = $udt_obj->getEndTimeStamp();
                                }
                            } else {
                                Debug::text(' Not within Daily Total Time: ' . $maximum_daily_total_time . ' Trigger Time: ' . $pp_obj->getDailyTriggerTime(), __FILE__, __LINE__, __METHOD__, 10);
                            }
                            break;
                        case 40: //Callback
                            Debug::text(' Callback Premium Policy...', __FILE__, __LINE__, __METHOD__, 10);
                            Debug::text(' Minimum Time Between Shifts: ' . $pp_obj->getMinimumTimeBetweenShift() . ' Minimum First Shift Time: ' . $pp_obj->getMinimumFirstShiftTime(), __FILE__, __LINE__, __METHOD__, 10);

                            $shift_data = $this->getShiftData($user_date_total_rows, TTDate::getMiddleDayEpoch($date_stamp), null, null, $pp_obj->getMinimumTimeBetweenShift());
                            Debug::Arr($shift_data, ' Shift Data...', __FILE__, __LINE__, __METHOD__, 10);

                            //Only calculate if their are at least two shifts
                            if (count($shift_data) >= 2) {
                                Debug::text(' Found at least two shifts...', __FILE__, __LINE__, __METHOD__, 10);

                                $prev_key = false;
                                foreach ($shift_data as $key => $data) {
                                    Debug::Arr($data, ' Shift Data for Shift: ' . $key, __FILE__, __LINE__, __METHOD__, 10);

                                    //Check if previous shift is greater than minimum first shift time.
                                    $prev_key = ($key - 1);

                                    if (isset($shift_data[$prev_key]) and isset($shift_data[$prev_key]['total_time']) and $shift_data[$prev_key]['total_time'] >= $pp_obj->getMinimumFirstShiftTime()) {
                                        Debug::text(' Previous shift exceeds minimum first shift time... Shift Total Time: ' . $shift_data[$prev_key]['total_time'], __FILE__, __LINE__, __METHOD__, 10);

                                        //Get last out time of the previous shift.
                                        if (isset($shift_data[$prev_key]['last_out'])) {

                                            //$previous_shift_last_out_epoch = $shift_data[$prev_key]['last_out']['time_stamp'];
                                            $previous_shift_last_out_epoch = $user_date_total_rows[$shift_data[$prev_key]['last_out']]->getEndTimeStamp();
                                            $current_shift_cutoff = ($previous_shift_last_out_epoch + $pp_obj->getMinimumTimeBetweenShift());
                                            Debug::text(' Previous Shift Last Out: ' . TTDate::getDate('DATE+TIME', $previous_shift_last_out_epoch) . '(' . $previous_shift_last_out_epoch . ') Current Shift Cutoff: ' . TTDate::getDate('DATE+TIME', $current_shift_cutoff) . '(' . $previous_shift_last_out_epoch . ')', __FILE__, __LINE__, __METHOD__, 10);

                                            $x = 1;
                                            foreach ($user_date_total_rows as $udt_key => $udt_obj) {
                                                //Debug::text('X: '. $x .'/'. $udtlf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);

                                                //Ignore incomplete punches
                                                if ($udt_obj->getTotalTime() == 0) {
                                                    continue;
                                                }

                                                if ($udt_obj->getStartTimeStamp() != '' and $udt_obj->getEndTimeStamp() != '') {
                                                    Debug::text(' Found valid UDT KEY: ' . $udt_key, __FILE__, __LINE__, __METHOD__, 10);
                                                    Debug::text(' First Punch: ' . TTDate::getDate('DATE+TIME', $udt_obj->getStartTimeStamp()) . ' Last Punch: ' . TTDate::getDate('DATE+TIME', $udt_obj->getEndTimeStamp()), __FILE__, __LINE__, __METHOD__, 10);

                                                    //The upper limit has to be the new shift trigger time, as once we start a new shift its no longer considered a callback.
                                                    if ($x == 1 and ($udt_obj->getStartTimeStamp() - $previous_shift_last_out_epoch) > $this->pay_period_schedule_obj->getNewDayTriggerTime()) {
                                                        Debug::text(' Greater than NewDayTrigger time, skipping...', __FILE__, __LINE__, __METHOD__, 10);
                                                        continue;
                                                    }

                                                    $punch_total_time = 0;
                                                    $force_minimum_time_calculation = false;

                                                    //Make sure all punches are after the cutoff time, so we only include time considered to be "callback"/
                                                    if ($udt_obj->getStartTimeStamp() >= $current_shift_cutoff) {
                                                        Debug::text(' Both punches are AFTER the cutoff time...', __FILE__, __LINE__, __METHOD__, 10);
                                                        //$punch_total_time = bcsub( $punch_pairs[$udt_obj->getPunchControlID()][1]['time_stamp'], $punch_pairs[$udt_obj->getPunchControlID()][0]['time_stamp']);
                                                        $punch_total_time = $udt_obj->getTotalTime();
                                                    } else {
                                                        Debug::text(' Both punches are BEFORE the cutoff time... Skipping...', __FILE__, __LINE__, __METHOD__, 10);
                                                        $punch_total_time = 0;
                                                    }
                                                    Debug::text(' Punch Total Time: ' . $punch_total_time, __FILE__, __LINE__, __METHOD__, 10);

                                                    $premium_policy_daily_total_time = 0;
                                                    if ($pp_obj->getMinimumTime() > 0 or $pp_obj->getMaximumTime() > 0) {
                                                        $premium_policy_daily_total_time = $this->getSumUserDateTotalData($this->filterUserDateTotalDataByPayCodeIDs($date_stamp, $date_stamp, $pp_obj->getPayCode()));
                                                        Debug::text('X: ' . $x . '/' . $user_date_total_rows_count . ' Premium Policy Daily Total Time: ' . $premium_policy_daily_total_time . ' Minimum Time: ' . $pp_obj->getMinimumTime() . ' Maximum Time: ' . $pp_obj->getMaximumTime(), __FILE__, __LINE__, __METHOD__, 10);

                                                        if ($pp_obj->getMinimumTime() > 0) {
                                                            //FIXME: Split the minimum time up between all the punches somehow.
                                                            //Apply the minimum time on the last punch, otherwise if there are two punch pairs of 15min each
                                                            //and a 1hr minimum time, if the minimum time is applied to the first, it will be 1hr and 15min
                                                            //for the day. If its applied to the last it will be just 1hr.
                                                            //Min & Max time is based on the shift time, rather then per punch pair time.
                                                            if (($force_minimum_time_calculation == true or $x == $user_date_total_rows_count) and bcadd($premium_policy_daily_total_time, $punch_total_time) < $pp_obj->getMinimumTime()) {
                                                                $total_time = bcsub($pp_obj->getMinimumTime(), $premium_policy_daily_total_time);
                                                            } else {
                                                                $total_time = $punch_total_time;
                                                            }
                                                        } else {
                                                            $total_time = $punch_total_time;
                                                        }

                                                        Debug::text(' Total Time After Minimum is applied: ' . $total_time, __FILE__, __LINE__, __METHOD__, 10);

                                                        if ($pp_obj->getMaximumTime() > 0) {
                                                            //Min & Max time is based on the shift time, rather then per punch pair time.
                                                            if (bcadd($premium_policy_daily_total_time, $total_time) > $pp_obj->getMaximumTime()) {
                                                                Debug::text(' bMore than Maximum Time...', __FILE__, __LINE__, __METHOD__, 10);
                                                                $total_time = bcsub($total_time, bcsub(bcadd($premium_policy_daily_total_time, $total_time), $pp_obj->getMaximumTime()));
                                                            }
                                                        }
                                                    } else {
                                                        $total_time = $punch_total_time;
                                                    }

                                                    Debug::text(' Total Punch Control Time: ' . $total_time, __FILE__, __LINE__, __METHOD__, 10);
                                                    if ($total_time != 0) { //Need to handle negative values too for things like lunch auto-deduct.
                                                        //Debug::text(' Applying	Premium Time!: '. $total_time, __FILE__, __LINE__, __METHOD__, 10);

                                                        Debug::text('Generating UserDateTotal object from Premium Time Policy, ID: ' . $this->user_date_total_insert_id . ' Object Type ID: ' . 40 . ' Pay Code ID: ' . (int)$pp_obj->getPayCode() . ' Total Time: ' . $total_time, __FILE__, __LINE__, __METHOD__, 10);
                                                        if (!isset($this->user_date_total[$this->user_date_total_insert_id])) {
                                                            $udtf = TTnew('UserDateTotalFactory');
                                                            $udtf->setUser($this->getUserObject()->getId());
                                                            $udtf->setDateStamp($date_stamp);
                                                            $udtf->setObjectType(40); //Premium Time
                                                            $udtf->setSourceObject((int)$pp_obj->getId());
                                                            $udtf->setPayCode((int)$pp_obj->getPayCode());

                                                            $udtf->setBranch((int)$udt_obj->getBranch());
                                                            $udtf->setDepartment((int)$udt_obj->getDepartment());
                                                            $udtf->setJob((int)$udt_obj->getJob());
                                                            $udtf->setJobItem((int)$udt_obj->getJobItem());

                                                            if ($udt_obj->getStartTimeStamp() != '' and $udt_obj->getEndTimeStamp() != '') {
                                                                $udtf->setStartType($udt_obj->getStartType());
                                                                $udtf->setEndType($udt_obj->getEndType());
                                                                $udtf->setStartTimeStamp($udt_obj->getStartTimeStamp());
                                                                $udtf->setEndTimeStamp(($udtf->getStartTimeStamp() + $total_time));
                                                            }

                                                            $udtf->setQuantity($udt_obj->getQuantity());
                                                            $udtf->setBadQuantity($udt_obj->getBadQuantity());
                                                            $udtf->setTotalTime($total_time);

                                                            $udtf->setBaseHourlyRate($this->getBaseHourlyRate($pp_obj->getPayFormulaPolicy(), $udtf->getPayCode(), $date_stamp, $udt_obj->getHourlyRate()));
                                                            $udtf->setHourlyRate($this->getHourlyRate($pp_obj->getPayFormulaPolicy(), $udtf->getPayCode(), $date_stamp, $udtf->getBaseHourlyRate()));
                                                            $udtf->setHourlyRateWithBurden($this->getHourlyRateWithBurden($pp_obj->getPayFormulaPolicy(), $udtf->getPayCode(), $date_stamp, $udtf->getHourlyRate()));

                                                            $udtf->setEnableCalcSystemTotalTime(false);
                                                            $udtf->preSave(); //Call this so TotalTimeAmount is calculated immediately, as we don't save these records until later.

                                                            if ($this->isOverriddenUserDateTotalObject($udtf) == false) {
                                                                //Don't save the record, just add it to the existing array, so it can be included in other calculations.
                                                                //We will save these records at the end.
                                                                $this->user_date_total[$this->user_date_total_insert_id] = $udtf;
                                                                $this->user_date_total_insert_id--;
                                                            }
                                                        } else {
                                                            Debug::text('ERROR: Duplicate starting ID for some reason! ' . $this->user_date_total_insert_id, __FILE__, __LINE__, __METHOD__, 10);
                                                        }
                                                    }
                                                } else {
                                                    Debug::text(' Skipping invalid Punch Control ID: ' . $udt_obj->getPunchControlID(), __FILE__, __LINE__, __METHOD__, 10);
                                                }

                                                $x++;
                                            }
                                        }
                                    }
                                }
                            }
                            unset($shift_data, $x, $udtf, $udt_obj);
                            break;
                        case 50: //Minimum shift time
                            Debug::text(' Minimum Shift Time Premium Policy... Minimum Shift Time: ' . $pp_obj->getMinimumShiftTime() . ' User ID: ' . $this->getUserObject()->getId() . ' DateStamp: ' . $date_stamp, __FILE__, __LINE__, __METHOD__, 10);

                            foreach ($user_date_total_rows as $udt_key => $udt_obj) {
                                //Ignore incomplete punches
                                if ($udt_obj->getTotalTime() == 0) {
                                    continue;
                                }

                                if ($this->contributing_shift_policy[$pp_obj->getContributingShiftPolicy()]->checkIndividualDifferentialCriteria($pp_obj->getBranchSelectionType(), $pp_obj->getExcludeDefaultBranch(), $udt_obj->getBranch(), $pp_obj->getBranch(), $this->getUserObject()->getDefaultBranch())) {
                                    //Debug::text(' Shift Differential... Meets Branch Criteria! Select Type: '. $pp_obj->getBranchSelectionType() .' Exclude Default Branch: '. (int)$pp_obj->getExcludeDefaultBranch() .' Default Branch: '.  $this->getUserObject()->getDefaultBranch(), __FILE__, __LINE__, __METHOD__, 10);
                                    if ($this->contributing_shift_policy[$pp_obj->getContributingShiftPolicy()]->checkIndividualDifferentialCriteria($pp_obj->getDepartmentSelectionType(), $pp_obj->getExcludeDefaultDepartment(), $udt_obj->getDepartment(), $pp_obj->getDepartment(), $this->getUserObject()->getDefaultDepartment())) {
                                        //Debug::text(' Shift Differential... Meets Department Criteria! Select Type: '. $pp_obj->getDepartmentSelectionType() .' Exclude Default Department: '. (int)$pp_obj->getExcludeDefaultDepartment() .' Default Department: '.  $this->getUserObject()->getDefaultDepartment(), __FILE__, __LINE__, __METHOD__, 10);
                                        $job_group = (is_object($udt_obj->getJobObject())) ? $udt_obj->getJobObject()->getGroup() : null;
                                        if ($this->contributing_shift_policy[$pp_obj->getContributingShiftPolicy()]->checkIndividualDifferentialCriteria($pp_obj->getJobGroupSelectionType(), null, $job_group, $pp_obj->getJobGroup())) {
                                            //Debug::text(' Shift Differential... Meets Job Group Criteria! Select Type: '. $pp_obj->getJobGroupSelectionType(), __FILE__, __LINE__, __METHOD__, 10);
                                            if ($this->contributing_shift_policy[$pp_obj->getContributingShiftPolicy()]->checkIndividualDifferentialCriteria($pp_obj->getJobSelectionType(), $pp_obj->getExcludeDefaultJob(), $udt_obj->getJob(), $pp_obj->getJob(), $this->getUserObject()->getDefaultJob())) {
                                                //Debug::text(' Shift Differential... Meets Job Criteria! Select Type: '. $pp_obj->getJobSelectionType(), __FILE__, __LINE__, __METHOD__, 10);
                                                $job_item_group = (is_object($udt_obj->getJobItemObject())) ? $udt_obj->getJobItemObject()->getGroup() : null;
                                                if ($this->contributing_shift_policy[$pp_obj->getContributingShiftPolicy()]->checkIndividualDifferentialCriteria($pp_obj->getJobItemGroupSelectionType(), null, $job_item_group, $pp_obj->getJobItemGroup())) {
                                                    //Debug::text(' Shift Differential... Meets Task Group Criteria! Select Type: '. $pp_obj->getJobItemGroupSelectionType(), __FILE__, __LINE__, __METHOD__, 10);
                                                    if ($this->contributing_shift_policy[$pp_obj->getContributingShiftPolicy()]->checkIndividualDifferentialCriteria($pp_obj->getJobItemSelectionType(), $pp_obj->getExcludeDefaultJobItem(), $udt_obj->getJobItem(), $pp_obj->getJobItem(), $this->getUserObject()->getDefaultJobItem())) {
                                                        //Debug::text(' Shift Differential... Meets Task Criteria! Select Type: '. $pp_obj->getJobSelectionType(), __FILE__, __LINE__, __METHOD__, 10);
                                                        $tmp_user_date_total_rows[] = $udt_obj;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }

                            if (isset($tmp_user_date_total_rows)) {
                                //This used to have differential criteria that could be specified as well, but now that contributing shifts exist, use those instead.
                                $shift_data = $this->getShiftData($tmp_user_date_total_rows, TTDate::getMiddleDayEpoch($date_stamp), null, null, $pp_obj->getMinimumTimeBetweenShift());
                                Debug::Arr($shift_data, ' Shift Data...', __FILE__, __LINE__, __METHOD__, 10);

                                if (is_array($shift_data)) {
                                    $total_shifts = count($shift_data);
                                    $x = 1;
                                    foreach ($shift_data as $shift_data_arr) {
                                        $total_time = 0;
                                        $punch_total_time = $shift_data_arr['total_time'];
                                        if ($punch_total_time == 0) { //Skip shift if its not complete.
                                            continue;
                                        }

                                        if ($punch_total_time > $pp_obj->getMinimumShiftTime()) {
                                            Debug::text(' Shift exceeds minimum shift time...', __FILE__, __LINE__, __METHOD__, 10);
                                            continue;
                                        } else {
                                            $punch_total_time = bcsub($pp_obj->getMinimumShiftTime(), $punch_total_time);
                                        }

                                        $premium_policy_daily_total_time = 0;
                                        if ($pp_obj->getMinimumTime() > 0 or $pp_obj->getMaximumTime() > 0) {
                                            $premium_policy_daily_total_time = $this->getSumUserDateTotalData($this->filterUserDateTotalDataByPayCodeIDs($date_stamp, $date_stamp, $pp_obj->getPayCode()));
                                            Debug::text('X: ' . $x . '/' . $total_shifts . ' Premium Policy Daily Total Time: ' . $premium_policy_daily_total_time . ' Minimum Time: ' . $pp_obj->getMinimumTime() . ' Maximum Time: ' . $pp_obj->getMaximumTime(), __FILE__, __LINE__, __METHOD__, 10);

                                            if ($pp_obj->getMinimumTime() > 0) {
                                                //FIXME: Split the minimum time up between all the punches somehow.
                                                //Apply the minimum time on the last punch, otherwise if there are two punch pairs of 15min each
                                                //and a 1hr minimum time, if the minimum time is applied to the first, it will be 1hr and 15min
                                                //for the day. If its applied to the last it will be just 1hr.
                                                //Min & Max time is based on the shift time, rather then per punch pair time.
                                                if ($x == $total_shifts and bcadd($premium_policy_daily_total_time, $punch_total_time) < $pp_obj->getMinimumTime()) {
                                                    $total_time = bcsub($pp_obj->getMinimumTime(), $premium_policy_daily_total_time);
                                                } else {
                                                    $total_time = $punch_total_time;
                                                }
                                            } else {
                                                $total_time = $punch_total_time;
                                            }

                                            Debug::text(' Total Time After Minimum is applied: ' . $total_time, __FILE__, __LINE__, __METHOD__, 10);

                                            if ($pp_obj->getMaximumTime() > 0) {
                                                //Min & Max time is based on the shift time, rather then per punch pair time.
                                                if (bcadd($premium_policy_daily_total_time, $total_time) > $pp_obj->getMaximumTime()) {
                                                    Debug::text(' bMore than Maximum Time...', __FILE__, __LINE__, __METHOD__, 10);
                                                    $total_time = bcsub($total_time, bcsub(bcadd($premium_policy_daily_total_time, $total_time), $pp_obj->getMaximumTime()));
                                                }
                                            }
                                        } else {
                                            $total_time = $punch_total_time;
                                        }

                                        Debug::text(' Total Punch Control Time: ' . $total_time, __FILE__, __LINE__, __METHOD__, 10);
                                        if ($total_time != 0) { //Need to handle negative values too for things like lunch auto-deduct.
                                            Debug::text(' Applying	Premium Time!: ' . $total_time, __FILE__, __LINE__, __METHOD__, 10);

                                            //Find branch, department, job, task of last punch_control_id in shift.
                                            if (isset($shift_data_arr['last_out']) and isset($tmp_user_date_total_rows[$shift_data_arr['last_out']])) {
                                                $udt_obj = $tmp_user_date_total_rows[$shift_data_arr['last_out']];

                                                Debug::text('Generating UserDateTotal object from Premium Time Policy, ID: ' . $this->user_date_total_insert_id . ' Object Type ID: ' . 40 . ' Pay Code ID: ' . (int)$pp_obj->getPayCode() . ' Total Time: ' . $total_time, __FILE__, __LINE__, __METHOD__, 10);
                                                if (!isset($this->user_date_total[$this->user_date_total_insert_id])) {
                                                    $udtf = TTnew('UserDateTotalFactory');
                                                    $udtf->setUser($this->getUserObject()->getId());
                                                    $udtf->setDateStamp($date_stamp);
                                                    $udtf->setObjectType(40); //Premium Time
                                                    $udtf->setSourceObject((int)$pp_obj->getId());
                                                    $udtf->setPayCode((int)$pp_obj->getPayCode());

                                                    $udtf->setBranch((int)$udt_obj->getBranch());
                                                    $udtf->setDepartment((int)$udt_obj->getDepartment());
                                                    $udtf->setJob((int)$udt_obj->getJob());
                                                    $udtf->setJobItem((int)$udt_obj->getJobItem());

                                                    if ($udt_obj->getStartTimeStamp() != '' and $udt_obj->getEndTimeStamp() != '') {
                                                        $udtf->setStartType($udt_obj->getStartType());
                                                        $udtf->setEndType($udt_obj->getEndType());
                                                        $udtf->setStartTimeStamp($udt_obj->getEndTimeStamp());
                                                        $udtf->setEndTimeStamp(($udtf->getStartTimeStamp() + $total_time));
                                                    }

                                                    $udtf->setQuantity($udt_obj->getQuantity());
                                                    $udtf->setBadQuantity($udt_obj->getBadQuantity());
                                                    $udtf->setTotalTime($total_time);

                                                    $udtf->setBaseHourlyRate($this->getBaseHourlyRate($pp_obj->getPayFormulaPolicy(), $udtf->getPayCode(), $date_stamp, $udt_obj->getHourlyRate()));
                                                    $udtf->setHourlyRate($this->getHourlyRate($pp_obj->getPayFormulaPolicy(), $udtf->getPayCode(), $date_stamp, $udtf->getBaseHourlyRate()));
                                                    $udtf->setHourlyRateWithBurden($this->getHourlyRateWithBurden($pp_obj->getPayFormulaPolicy(), $udtf->getPayCode(), $date_stamp, $udtf->getHourlyRate()));

                                                    $udtf->setEnableCalcSystemTotalTime(false);
                                                    $udtf->preSave(); //Call this so TotalTimeAmount is calculated immediately, as we don't save these records until later.

                                                    //Don't save the record, just add it to the existing array, so it can be included in other calculations.
                                                    //We will save these records at the end.
                                                    $this->user_date_total[$this->user_date_total_insert_id] = $udtf;
                                                    $this->user_date_total_insert_id--;
                                                } else {
                                                    Debug::text('ERROR: Duplicate starting ID for some reason! ' . $this->user_date_total_insert_id, __FILE__, __LINE__, __METHOD__, 10);
                                                }
                                            }
                                        }
                                        $x++;
                                    }
                                }
                            } else {
                                Debug::text('  Differential Criteria filtered out all UDT rows...', __FILE__, __LINE__, __METHOD__, 10);
                            }

                            unset($tmp_user_date_total_rows, $shift_data, $total_shifts, $udtf, $udt_obj);
                            break;
                    }
                } else {
                    Debug::text('No matching UserDateTotal rows...', __FILE__, __LINE__, __METHOD__, 10);
                }
            }

            $this->user_date_total = $this->sortUserDateTotalData($this->user_date_total); //Sort UDT records once done modifying them. This should help avoid having to sort them everytime we get/filter them.

            return true;
        }

        Debug::text('No premium time policies to calculate...', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    //Grabs all user_date total data from DB for each date specified.

    public function sortPolicyByPayCodeDependancy($policy_arr)
    {
        //Loop through all policies, getting the input ContributingPayCodes and output Pay Code to create dependancy tree.
        //If only one policy exists, no point in sorting it.
        if (is_array($policy_arr) and count($policy_arr) > 1) {
            $dependency_tree = new DependencyTree();

            foreach ($policy_arr as $policy_obj) {
                //Debug::text('Policy Name: '. $policy_obj->getName() .' ID: '. $policy_obj->getID(), __FILE__, __LINE__, __METHOD__, 10);
                $global_id = substr(get_class($policy_obj), 0, 1) . $policy_obj->getId();

                $policy_order_arr[$global_id] = $policy_obj;

                if (isset($this->contributing_shift_policy[$policy_obj->getContributingShiftPolicy()])) {
                    $contributing_shift_policy_obj = $this->contributing_shift_policy[$policy_obj->getContributingShiftPolicy()];

                    $require_pay_codes = array();
                    if (isset($this->contributing_pay_codes_by_policy_id[$contributing_shift_policy_obj->getContributingPayCodePolicy()])) {
                        $require_pay_codes = (array)$this->contributing_pay_codes_by_policy_id[$contributing_shift_policy_obj->getContributingPayCodePolicy()];
                    }

                    $affect_pay_codes = (array)$policy_obj->getPayCode();

                    if (get_class($policy_obj) == 'PremiumPolicyListFactory') {
                        $order = (int)(40 . str_pad(9999, 5, 0, STR_PAD_LEFT) . str_pad(substr($policy_obj->getID(), -2), 2, 0, STR_PAD_LEFT));
                    } elseif (get_class($policy_obj) == 'RegularPolicyListFactory') {
                        $order = (int)(20 . str_pad($policy_obj->getCalculationOrder(), 5, 0, STR_PAD_LEFT) . str_pad(substr($policy_obj->getID(), -2), 2, 0, STR_PAD_LEFT));
                    } elseif (get_class($policy_obj) == 'OvertimePolicyListFactory') {
                        $order = (int)(30 . str_pad(9999, 5, 0, STR_PAD_LEFT) . str_pad(substr($policy_obj->getID(), -2), 2, 0, STR_PAD_LEFT));
                    } else {
                        $order = (int)(99 . str_pad(9999, 5, 0, STR_PAD_LEFT) . str_pad(substr($policy_obj->getID(), -2), 2, 0, STR_PAD_LEFT));
                    }

                    //Debug::Arr( array( $require_pay_codes, $affect_pay_codes ), 'Policy Name: '. $policy_obj->getName() .' Order: '. $order .' Requires/Affects Pay Codes', __FILE__, __LINE__, __METHOD__, 10);
                    $dependency_tree->addNode($global_id, $require_pay_codes, $affect_pay_codes, $order);
                }
                unset($global_id, $contributing_shift_policy_obj, $require_pay_codes, $affect_pay_codes, $order);
            }

            $sorted_policy_ids = $dependency_tree->getAllNodesInOrder();
            unset($dependency_tree);

            Debug::Arr($sorted_policy_ids, 'Sorted Policy IDs Array: ', __FILE__, __LINE__, __METHOD__, 10);
            if (is_array($sorted_policy_ids)) {
                foreach ($sorted_policy_ids as $tmp => $global_id) {
                    //Debug::text('Final Sorted Policy Name: '. $policy_order_arr[$global_id]->getName() .' ID: '. $policy_order_arr[$global_id]->getID(), __FILE__, __LINE__, __METHOD__, 10);
                    $retarr[$policy_order_arr[$global_id]->getId()] = $policy_order_arr[$global_id];
                }

                if (isset($retarr)) {
                    return $retarr;
                }
            }
        }

        return $policy_arr;
    }

    //Return dates that we have not already obtained data for.

    public function filterPremiumTimePolicy($date_stamp)
    {
        $pplf = $this->premium_time_policy;
        if (is_array($pplf) and count($pplf) > 0) {
            $date_stamp = TTDate::getMiddleDayEpoch($date_stamp); //Optimization - Move outside loop.

            $schedule_policy_premium_time_policy_ids = array();
            $schedule_policy_exclude_premium_time_policy_ids = array();
            $schedule_policy_arr = $this->filterSchedulePolicyByDate($date_stamp);
            if (is_array($schedule_policy_arr)) {
                foreach ($schedule_policy_arr as $sp_obj) {
                    if (is_array($sp_obj->getIncludePremiumPolicy()) and count($sp_obj->getIncludePremiumPolicy()) > 0) {
                        $schedule_policy_premium_time_policy_ids = array_merge($schedule_policy_premium_time_policy_ids, (array)$sp_obj->getIncludePremiumPolicy());
                    }
                    if (is_array($sp_obj->getExcludePremiumPolicy()) and count($sp_obj->getExcludePremiumPolicy()) > 0) {
                        $schedule_policy_exclude_premium_time_policy_ids = array_merge($schedule_policy_exclude_premium_time_policy_ids, (array)$sp_obj->getExcludePremiumPolicy());
                    }
                }
                Debug::Arr($schedule_policy_premium_time_policy_ids, 'Premium Policies that apply to: ' . TTDate::getDate('DATE', $date_stamp) . ' from schedule policies: ', __FILE__, __LINE__, __METHOD__, 10);
            }

            foreach ($pplf as $pp_obj) {
                //Filter out premium policies that aren't within the active start/end dates.
                //This can help significantly when many premium policies exist.
                if (
                    (
                        ((int)$pp_obj->getColumn('is_policy_group') == 1 and !in_array($pp_obj->getId(), $schedule_policy_exclude_premium_time_policy_ids))
                        or
                        ((int)$pp_obj->getColumn('is_policy_group') == 0 and in_array($pp_obj->getId(), $schedule_policy_premium_time_policy_ids))
                    )
                    and
                    (
                        $pp_obj->getType() == 90 //If its a Holiday Premium policy we always need to include it due to different shift times.
                        or
                        $pp_obj->isActiveDate($date_stamp, 86400) == true //Need to handle shifts that span midnight, so filter policies active on the day before, current date, and day after as well.
                    )
                ) {
                    $retarr[$pp_obj->getId()] = $pp_obj;
                } else {
                    Debug::text('  Skipping premium time policies that apply on date: ' . $pp_obj->getName() . ' INT: ' . $pp_obj->getColumn('is_policy_group'), __FILE__, __LINE__, __METHOD__, 10);
                }
            }

            if (isset($retarr)) {
                Debug::text('Found premium time policies that apply on date: ' . count($retarr), __FILE__, __LINE__, __METHOD__, 10);
                return $retarr;
            }
        }

        Debug::text('No premium time policies apply on date: ' . TTDate::getDate('DATE', $date_stamp), __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function calculateSystemTotalTime($date_stamp, $tmp_system_total_time)
    {
        $system_total_time = $this->getSumUserDateTotalData($this->compactUserDateTotalDataBasedOnOverride($this->filterUserDateTotalDataByObjectTypeIDs($date_stamp, $date_stamp, array(20, 30, 25))));
        Debug::text('System Total Time: ' . $system_total_time . ' vs. System Total Time Param: ' . $tmp_system_total_time, __FILE__, __LINE__, __METHOD__, 10);

        //Handle cases where there is no regular time policies, or any other policies applied, but the user did have punches/worked time.
        if ($system_total_time == 0) {
            $system_total_time = $tmp_system_total_time;
        }

        $this->user_date_total_insert_id--;
        if (!isset($this->user_date_total[$this->user_date_total_insert_id])) {
            $udtf = TTnew('UserDateTotalFactory');
            $udtf->setUser($this->getUserObject()->getId());
            $udtf->setDateStamp($date_stamp);
            $udtf->setObjectType(5); //System Total
            $udtf->setTotalTime($system_total_time);

            $shift_udt_objs = $this->getShiftStartAndEndUserDateTotal($date_stamp, $date_stamp);
            if (is_array($shift_udt_objs) and isset($shift_udt_objs['start']) and isset($shift_udt_objs['end'])) {
                $udtf->setStartType(10); //Normal
                $udtf->setEndType(10); //Normal
                $udtf->setStartTimeStamp($shift_udt_objs['start']->getStartTimeStamp());
                $udtf->setEndTimeStamp($shift_udt_objs['end']->getEndTimeStamp());
            }

            $udtf->setEnableCalcSystemTotalTime(false);
            $udtf->preSave(); //Call this so TotalTimeAmount is calculated immediately, as we don't save these records until later.

            //Don't save the record, just add it to the existing array, so it can be included in other calculations.
            //We will save these records at the end.
            $this->user_date_total[$this->user_date_total_insert_id] = $udtf;
            $this->user_date_total_insert_id--;
            return true;
        } else {
            Debug::text('ERROR: Duplicate starting ID for some reason! ' . $this->user_date_total_insert_id, __FILE__, __LINE__, __METHOD__, 10);
        }

        return false;
    }

    //Gathers all required data to perform the calculations.

    public function compactUserDateTotalDataBasedOnOverride($udtlf)
    {
        if (is_array($udtlf) and count($udtlf) > 0) {
            $tmp_udtlf = $udtlf;
            foreach ($udtlf as $udt_key => $udt_obj) {
                Debug::text('UDT Date Stamp: ' . TTDate::getDate('DATE', $udt_obj->getDateStamp()) . ' Pay Code ID: ' . $udt_obj->getPayCode() . ' Object Type: ' . $udt_obj->getObjectType() . ' Total Time: ' . $udt_obj->getTotalTime() . ' Override: ' . (int)$udt_obj->getOverride(), __FILE__, __LINE__, __METHOD__, 10);

                if ($udt_obj->getOverride() == true) {
                    foreach ($tmp_udtlf as $tmp_udt_key => $tmp_udt_obj) {
                        Debug::text('  UDT Date Stamp: ' . TTDate::getDate('DATE', $tmp_udt_obj->getDateStamp()) . ' Pay Code ID: ' . $tmp_udt_obj->getPayCode() . ' Object Type: ' . $tmp_udt_obj->getObjectType() . ' Total Time: ' . $tmp_udt_obj->getTotalTime() . ' Override: ' . (int)$tmp_udt_obj->getOverride(), __FILE__, __LINE__, __METHOD__, 10);

                        if ($udt_key != $tmp_udt_key and $udt_obj->getID() !== $tmp_udt_obj->getID() and $tmp_udt_obj->getOverride() == false and $this->testOverriddenUserDateTotalObject($tmp_udt_obj, $udt_obj) == true) {
                            Debug::text('   Found override, excluding this record... Key: ' . $tmp_udt_key, __FILE__, __LINE__, __METHOD__, 10);
                            unset($udtlf[$tmp_udt_key]);
                        }
                    }
                }
            }
        }

        return $udtlf;
    }

    public function calculateOverTimeHourlyRates($user_date_total_records)
    {
        if (is_array($user_date_total_records) and count($user_date_total_records) > 0) {
            Debug::text('Calculating Overtime Hourly Rates...', __FILE__, __LINE__, __METHOD__, 10);
            foreach ($user_date_total_records as $key => $udt_obj) {
                if ($key < 0 and $udt_obj->getTotalTime() > 0 and $udt_obj->getObjectType() == 30) {
                    //Debug::text('  Calculating UserDateTotal Entry!', __FILE__, __LINE__, __METHOD__, 10);

                    //Only recalculate rates if we are actually using averaging. Otherwise we calculate them in calculateOverTime() instead.
                    if ($this->isPayFormulaPolicyAveraging($this->over_time_policy[$udt_obj->getSourceObject()]->getPayFormulaPolicy(), $udt_obj->getPayCode())) {
                        $udt_obj->setHourlyRate($this->getHourlyRate($this->over_time_policy[$udt_obj->getSourceObject()]->getPayFormulaPolicy(), $udt_obj->getPayCode(), $udt_obj->getDateStamp(), $this->getBaseHourlyRate($this->over_time_policy[$udt_obj->getSourceObject()]->getPayFormulaPolicy(), $udt_obj->getPayCode(), $udt_obj->getDateStamp(), $udt_obj->getBaseHourlyRate(), $this->contributing_shift_policy[$this->over_time_policy[$udt_obj->getSourceObject()]->getContributingShiftPolicy()], array(20, 25, 100, 110))));
                        $udt_obj->setHourlyRateWithBurden($this->getHourlyRateWithBurden($this->over_time_policy[$udt_obj->getSourceObject()]->getPayFormulaPolicy(), $udt_obj->getPayCode(), $udt_obj->getDateStamp(), $udt_obj->getHourlyRate()));

                        $udt_obj->setEnableCalcSystemTotalTime(false);
                        $udt_obj->preSave(); //Call this so TotalTimeAmount is calculated immediately, as we don't save these records until later.
                    }
                }
                //else {
                //Debug::text('  Skipping... UserDateTotal Entry! Key: '. $key .' ObjectType: '. $udt_obj->getObjectType(), __FILE__, __LINE__, __METHOD__, 10);
                //}
            }

            return true;
        }

        Debug::text('No UserDateTotal entries to calculate wages for...', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function isPayFormulaPolicyAveraging($pay_formula_policy_id, $pay_code_id)
    {
        $pay_formula_policy_obj = $this->getPayFormulaPolicyObjectByPayFormulaIdOrPayCodeId($pay_formula_policy_id, $pay_code_id);
        if (!is_object($pay_formula_policy_obj)) {
            Debug::text('  No Pay Formula Policy to use...', __FILE__, __LINE__, __METHOD__, 10);
            return false;
        }

        if ($pay_formula_policy_obj->getWageSourceType() == 30) {
            Debug::text('  Pay Formula is averaging...', __FILE__, __LINE__, __METHOD__, 10);
            return true;
        }

        return false;
    }

    public function calculateExceptionPolicy($date_stamp)
    {
        //Make sure passed date_stamp is middleDayEpoch() to match the existing exceptions.
        $date_stamp = TTDate::getMiddleDayEpoch($date_stamp); //Optimization - Move outside loop.
        $existing_exceptions = array();
        $elf = $this->exception;
        if (is_array($elf) and count($elf) > 0) {
            foreach ($elf as $e_obj) {
                $e_obj_date_stamp = TTDate::getMiddleDayEpoch($e_obj->getDateStamp()); //Optimization - Move outside loop.
                //Because the exception diff. function compares on what exists vs whats new, we can only pass exceptions from the current date to it.
                if ($e_obj_date_stamp == $date_stamp) {
                    $existing_exceptions[$e_obj->getId()] = array(
                        'id' => $e_obj->getId(),
                        'user_id' => $e_obj->getUser(),
                        'date_stamp' => $e_obj_date_stamp,
                        'exception_policy_id' => $e_obj->getExceptionPolicyID(),
                        'type_id' => $e_obj->getType(),
                        'punch_id' => $e_obj->getPunchID(),
                        'punch_control_id' => $e_obj->getPunchControlID(),
                    );
                }
            }
        }
        unset($elf, $e_obj);

        if (is_array($this->exception_policy)) {
            $enable_premature_exceptions = $this->getFlag('exception_premature');
            $enable_future_exceptions = $this->getFlag('exception_future');

            Debug::text(' DateStamp: ' . TTDate::getDATE('DATE+TIME', $date_stamp), __FILE__, __LINE__, __METHOD__, 10);
            $current_epoch = time();

            if ($enable_future_exceptions == false
                and $date_stamp > TTDate::getEndDayEpoch($current_epoch)
            ) {
                return false;
            }

            if (is_object($this->pay_period_schedule_obj)) {
                $premature_delay = $this->pay_period_schedule_obj->getMaximumShiftTime();
                $start_week_day_id = $this->pay_period_schedule_obj->getStartWeekDay();
            } else {
                $premature_delay = 57600;
                $start_week_day_id = 0;
            }
            Debug::text(' Setting preMature Exception delay to maximum shift time: ' . $premature_delay . ' Enable PreMature Exceptions: ' . (int)$enable_premature_exceptions, __FILE__, __LINE__, __METHOD__, 10);

            $user_id = $this->getUserObject()->getId();

            $current_exceptions = array(); //Array holding current exception data.

            $slf = $this->filterScheduleDataByStatus($date_stamp, $date_stamp, array(10));
            $plf = $this->filterPunchDataByDateAndTypeAndStatus($date_stamp);

            foreach ($this->exception_policy as $ep_obj) {
                //Only allow pre-mature exceptions to be enabled if we are calculating no further back than 2 days from the current time.
                if ($enable_premature_exceptions == true and $ep_obj->isPreMature($ep_obj->getType()) == true
                    and $date_stamp >= (TTDate::getMiddleDayEpoch($current_epoch) - $premature_delay)
                ) {
                    $type_id = 5; //Pre-Mature
                } else {
                    $type_id = 50; //Active
                }

                Debug::text('---Calculating Exception: ' . $ep_obj->getType() . ' Type ID: ' . $type_id, __FILE__, __LINE__, __METHOD__, 10);
                switch (strtolower($ep_obj->getType())) {
                    case 's1':    //Unscheduled Absence... Anytime they are scheduled and have not punched in.
                        //Ignore these exceptions if the schedule is after today (not including today),
                        //so if a supervisors schedules an employee two days in advance they don't get a unscheduled
                        //absence appearing right away.
                        //Since we now trigger In Late/Out Late exceptions immediately after schedule time, only trigger this exception after
                        //the schedule end time has passed.
                        //**We also need to handle shifts that start at 11:00PM on one day, end at 8:00AM the next day, and they are assigned to the day where
                        //the most time is worked (ie: the next day).
                        //Handle split shifts too...
                        //- This has a side affect that if the schedule policy start/stop time is set to 0, it will trigger both a UnScheduled Absence
                        //	and a Not Scheduled exception for the same schedule/punch.

                        //Loop through all schedules, then find punches to match.
                        if (is_array($slf) and count($slf) > 0) {
                            foreach ($slf as $s_obj) {
                                if ($s_obj->getStatus() == 10 and ($current_epoch >= $s_obj->getEndTime())) {
                                    $add_exception = true;

                                    //FIXME: If no punches match, find punches that fall within this schedule time including start/stop window.
                                    //  In case the punches got assigned to a different day due to some other shift running long.
                                    //  ie: Current Schedule 8A-5P, the employee punched 8A-5P, but the previous day was 11P to 6A, so both shifts get assigned to the previous day.

                                    //Debug::text(' Found Schedule: Start Time: '. TTDate::getDate('DATE+TIME', $s_obj->getStartTime() ), __FILE__, __LINE__, __METHOD__, 10);
                                    //Find punches that fall within this schedule time including start/stop window.
                                    if (TTDate::doesRangeSpanMidnight($s_obj->getStartTime(), $s_obj->getEndTime())) {
                                        //Get punches from both days.
                                        $plf_tmp = TTnew('PunchListFactory');
                                        //$plf_tmp->getShiftPunchesByUserIDAndEpoch( $user_date_obj->getUser(), $s_obj->getStartTime(), 0, $user_date_obj->getPayPeriodObject()->getPayPeriodScheduleObject()->getMaximumShiftTime() );
                                        $plf_tmp->getShiftPunchesByUserIDAndEpoch($user_id, $s_obj->getStartTime(), 0, $premature_delay);
                                        Debug::text(' Schedule spans midnight... Found rows from expanded search: ' . $plf_tmp->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
                                        if ($plf_tmp->getRecordCount() > 0) {
                                            foreach ($plf_tmp as $p_obj_tmp) {
                                                if ($s_obj->inSchedule($p_obj_tmp->getTimeStamp())) {
                                                    Debug::text(' aFound punch for schedule...', __FILE__, __LINE__, __METHOD__, 10);
                                                    $add_exception = false;
                                                    break;
                                                }
                                            }
                                        }
                                        unset($plf_tmp, $p_obj_tmp);
                                    } else {
                                        if (is_array($plf) and count($plf) > 0) {
                                            //Get punches from just this day.
                                            foreach ($plf as $p_obj) {
                                                if ($s_obj->inSchedule($p_obj->getTimeStamp())) {
                                                    //Debug::text(' bFound punch for schedule...', __FILE__, __LINE__, __METHOD__, 10);
                                                    $add_exception = false;
                                                    break;
                                                }
                                            }
                                        }
                                    }

                                    if ($add_exception == true) {
                                        //Debug::text(' Adding S1 exception...', __FILE__, __LINE__, __METHOD__, 10);
                                        $current_exceptions[] = array(
                                            'user_id' => $user_id,
                                            'date_stamp' => $date_stamp,
                                            'exception_policy_id' => $ep_obj->getId(),
                                            'type_id' => $type_id,
                                            'punch_id' => false,
                                            'punch_control_id' => false,
                                            'schedule_obj' => $s_obj,
                                        );
                                    }
                                }
                            }
                        }
                        unset($s_obj, $add_exception);
                        break;
                    case 's2': //Not Scheduled
                        //**We also need to handle shifts that start at 11:00PM on one day, end at 8:00AM the next day, and they are assigned to the day where
                        //the most time is worked (ie: the next day).
                        //Handle split shifts too...
                        if (is_array($plf) and count($plf) > 0) { //Make sure at least two punche exist.
                            //Loop through each punch, find out if they are scheduled, and if they are in early
                            $prev_punch_time_stamp = false;
                            foreach ($plf as $p_obj) {
                                //Ignore punches that have the exact same timestamp, as they are likely transfer punches.
                                if ($prev_punch_time_stamp != $p_obj->getTimeStamp() and $p_obj->getType() == 10 and $p_obj->getStatus() == 10) { //Normal In
                                    if (!isset($scheduled_id_cache[$p_obj->getID()])) {
                                        $scheduled_id_cache[$p_obj->getID()] = $p_obj->findScheduleID(null, $user_id);
                                    }

                                    //Check if no schedule exists, or an absent schedule exists. If they work when not scheduled (no schedule) or schedule absent, both should trigger this.
                                    if ($p_obj->setScheduleID($scheduled_id_cache[$p_obj->getID()]) == false
                                        or (is_object($p_obj->getScheduleObject()) and $p_obj->getScheduleObject()->getStatus() == 20)
                                    ) {
                                        //Debug::text(' Worked when wasnt scheduled', __FILE__, __LINE__, __METHOD__, 10);
                                        $current_exceptions[] = array(
                                            'user_id' => $user_id,
                                            'date_stamp' => $date_stamp,
                                            'exception_policy_id' => $ep_obj->getId(),
                                            'type_id' => $type_id,
                                            'punch_id' => $p_obj->getID(),
                                            'punch_control_id' => false,
                                        );
                                    } else {
                                        Debug::text('	 Schedule Found', __FILE__, __LINE__, __METHOD__, 10);
                                    }
                                }
                                $prev_punch_time_stamp = $p_obj->getTimeStamp();
                            }
                        }
                        unset($scheduled_id_cache, $prev_punch_time_stamp, $p_obj);
                        break;
                    case 's3': //In Early
                        if (is_array($plf) and count($plf) > 0) {
                            //Loop through each punch, find out if they are scheduled, and if they are in early
                            $prev_punch_time_stamp = false;
                            foreach ($plf as $p_obj) {
                                //Ignore punches that have the exact same timestamp, as they are likely transfer punches.
                                if ($prev_punch_time_stamp != $p_obj->getTimeStamp() and $p_obj->getType() == 10 and $p_obj->getStatus() == 10) { //Normal In
                                    if (!isset($scheduled_id_cache[$p_obj->getID()])) {
                                        $scheduled_id_cache[$p_obj->getID()] = $p_obj->findScheduleID(null, $user_id);
                                    }
                                    if ($p_obj->setScheduleID($scheduled_id_cache[$p_obj->getID()]) == true) {
                                        if ($p_obj->getScheduleObject()->getStatus() == 10 and $p_obj->getTimeStamp() < $p_obj->getScheduleObject()->getStartTime()) { //Be sure to ignore Absence scheduled shifts.
                                            if (TTDate::inWindow($p_obj->getTimeStamp(), $p_obj->getScheduleObject()->getStartTime(), $ep_obj->getGrace()) == true) {
                                                Debug::text('	 Within Grace time, IGNORE EXCEPTION: ', __FILE__, __LINE__, __METHOD__, 10);
                                            } elseif (TTDate::inWindow($p_obj->getTimeStamp(), $p_obj->getScheduleObject()->getStartTime(), $ep_obj->getWatchWindow()) == true) {
                                                Debug::text('	 NOT Within Grace time, SET EXCEPTION: ', __FILE__, __LINE__, __METHOD__, 10);
                                                $current_exceptions[] = array(
                                                    'user_id' => $user_id,
                                                    'date_stamp' => $date_stamp,
                                                    'exception_policy_id' => $ep_obj->getId(),
                                                    'type_id' => $type_id,
                                                    'punch_id' => $p_obj->getID(),
                                                    'punch_control_id' => false,
                                                    'punch_obj' => $p_obj,
                                                    'schedule_obj' => $p_obj->getScheduleObject(),
                                                );
                                            }
                                        } else {
                                            Debug::text('	 NO Working Schedule Found', __FILE__, __LINE__, __METHOD__, 10);
                                        }
                                    } else {
                                        Debug::text('	 NO Schedule Found', __FILE__, __LINE__, __METHOD__, 10);
                                    }
                                }
                                $prev_punch_time_stamp = $p_obj->getTimeStamp();
                            }
                        }
                        break;
                    case 's4': //In Late
                        if (is_array($plf) and count($plf) > 0) {
                            $prev_punch_time_stamp = false;
                            foreach ($plf as $p_obj) {
                                //Debug::text('	 In Late. Punch: '. TTDate::getDate('DATE+TIME', $p_obj->getTimeStamp() ), __FILE__, __LINE__, __METHOD__, 10);
                                //Ignore punches that have the exact same timestamp and/or punches with the transfer flag, as they are likely transfer punches.
                                if ($prev_punch_time_stamp != $p_obj->getTimeStamp() and $p_obj->getTransfer() == false and $p_obj->getType() == 10 and $p_obj->getStatus() == 10) { //Normal In
                                    if (!isset($scheduled_id_cache[$p_obj->getID()])) {
                                        $scheduled_id_cache[$p_obj->getID()] = $p_obj->findScheduleID(null, $user_id);
                                    }
                                    if ($p_obj->setScheduleID($scheduled_id_cache[$p_obj->getID()]) == true) {
                                        if ($p_obj->getScheduleObject()->getStatus() == 10 and $p_obj->getTimeStamp() > $p_obj->getScheduleObject()->getStartTime()) { //Be sure to ignore Absence scheduled shifts.
                                            if (TTDate::inWindow($p_obj->getTimeStamp(), $p_obj->getScheduleObject()->getStartTime(), $ep_obj->getGrace()) == true) {
                                                Debug::text('	 Within Grace time, IGNORE EXCEPTION: ', __FILE__, __LINE__, __METHOD__, 10);
                                            } elseif (TTDate::inWindow($p_obj->getTimeStamp(), $p_obj->getScheduleObject()->getStartTime(), $ep_obj->getWatchWindow()) == true) {
                                                Debug::text('	 NOT Within Grace time, SET EXCEPTION: ', __FILE__, __LINE__, __METHOD__, 10);
                                                $current_exceptions[] = array(
                                                    'user_id' => $user_id,
                                                    'date_stamp' => $date_stamp,
                                                    'exception_policy_id' => $ep_obj->getId(),
                                                    'type_id' => $type_id,
                                                    'punch_id' => $p_obj->getID(),
                                                    'punch_control_id' => false,
                                                    'punch_obj' => $p_obj,
                                                    'schedule_obj' => $p_obj->getScheduleObject(),
                                                );
                                            }
                                        } else {
                                            Debug::text('	 NO Working Schedule Found', __FILE__, __LINE__, __METHOD__, 10);
                                        }
                                    } else {
                                        Debug::text('	 NO Schedule Found', __FILE__, __LINE__, __METHOD__, 10);
                                    }
                                }
                                $prev_punch_time_stamp = $p_obj->getTimeStamp();
                            }
                        }
                        unset($scheduled_id_cache);

                        //Late Starting their shift, with no punch yet, trigger exception if:
                        //	- Schedule is found
                        //	- Current time is after schedule start time and before schedule end time.
                        //	- Current time is after exception grace time
                        //Make sure we take into account split shifts.
                        Debug::text('	 Checking Late Starting Shift exception... Current time: ' . TTDate::getDate('DATE+TIME', $current_epoch), __FILE__, __LINE__, __METHOD__, 10);
                        if (is_array($slf) and count($slf) > 0) {
                            foreach ($slf as $s_obj) {
                                if ($s_obj->getStatus() == 10 and ($current_epoch >= $s_obj->getStartTime() and $current_epoch <= $s_obj->getEndTime())) {
                                    if (TTDate::inWindow($current_epoch, $s_obj->getStartTime(), $ep_obj->getGrace()) == true) {
                                        Debug::text('	 Within Grace time, IGNORE EXCEPTION: ', __FILE__, __LINE__, __METHOD__, 10);
                                    } else {
                                        //See if we can find a punch within the schedule time, if so assume we already created the exception above.
                                        //Make sure we take into account the schedule policy start/stop window.
                                        //However in the case where a single schedule shift and just one punch exists, if an employee comes in really
                                        //early (1AM) before the schedule start/stop window it will trigger an In Late exception.
                                        //This could still be correct though if they only come in for an hour, then come in late for their shift later.
                                        //Schedule start/stop time needs to be correct.
                                        //Also need to take into account shifts that span midnight, ie: 10:30PM to 6:00AM, as its important the schedules/punches match up properly.

                                        $add_exception = true;
                                        Debug::text(' Found Schedule: Start Time: ' . TTDate::getDate('DATE+TIME', $s_obj->getStartTime()), __FILE__, __LINE__, __METHOD__, 10);
                                        //Find punches that fall within this schedule time including start/stop window.
                                        if (TTDate::doesRangeSpanMidnight($s_obj->getStartTime(), $s_obj->getEndTime())) {
                                            //Get punches from both days.
                                            $plf_tmp = TTnew('PunchListFactory');
                                            $plf_tmp->getShiftPunchesByUserIDAndEpoch($user_id, $s_obj->getStartTime(), 0, $premature_delay);
                                            Debug::text(' Schedule spans midnight... Found rows from expanded search: ' . $plf_tmp->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
                                            if ($plf_tmp->getRecordCount() > 0) {
                                                foreach ($plf_tmp as $p_obj_tmp) {
                                                    if ($s_obj->inSchedule($p_obj_tmp->getTimeStamp())) {
                                                        Debug::text('	 Found punch for this schedule, skipping schedule...', __FILE__, __LINE__, __METHOD__, 10);
                                                        $add_exception = false;
                                                        continue 2; //Skip to next schedule without creating exception.
                                                    }
                                                }
                                            }
                                            unset($plf_tmp, $p_obj_tmp);
                                        } else {
                                            //Get punches from just this day.
                                            if (is_array($plf) and count($plf) > 0) {
                                                foreach ($plf as $p_obj) {
                                                    if ($s_obj->inSchedule($p_obj->getTimeStamp())) {
                                                        Debug::text(' bFound punch for schedule...', __FILE__, __LINE__, __METHOD__, 10);
                                                        $add_exception = false;
                                                        break;
                                                    }
                                                }
                                            }
                                        }

                                        if ($add_exception == true) {
                                            Debug::text('	 NOT Within Grace time, SET EXCEPTION: ', __FILE__, __LINE__, __METHOD__, 10);
                                            $current_exceptions[] = array(
                                                'user_id' => $user_id,
                                                'date_stamp' => $date_stamp,
                                                'exception_policy_id' => $ep_obj->getId(),
                                                'type_id' => $type_id,
                                                'punch_id' => false,
                                                'punch_control_id' => false,
                                                'schedule_obj' => $s_obj,
                                            );
                                        }
                                    }
                                }
                            }
                        } else {
                            Debug::text('	 NO Schedules Found', __FILE__, __LINE__, __METHOD__, 10);
                        }
                        break;
                    case 's5': //Out Early
                        if (is_array($plf) and count($plf) > 0) {
                            //Loop through each punch, find out if they are scheduled, and if they are in early
                            $prev_punch_time_stamp = false;
                            $total_punches = count($plf);
                            $x = 1;
                            foreach ($plf as $p_obj) {
                                //Ignore punches that have the exact same timestamp and/or punches with the transfer flag, as they are likely transfer punches.
                                //For Out Early, we have to wait until we are at the last punch, or there is a subsequent punch
                                // to see if it matches the exact same time (transfer)
                                //Therefore we need a two step confirmation before this exception can be triggered. Current punch, then next punch if it exists.
                                if ($p_obj->getTransfer() == false and $p_obj->getType() == 10 and $p_obj->getStatus() == 20) { //Normal Out
                                    if (!isset($scheduled_id_cache[$p_obj->getID()])) {
                                        $scheduled_id_cache[$p_obj->getID()] = $p_obj->findScheduleID(null, $user_id);
                                    }
                                    if ($p_obj->setScheduleID($scheduled_id_cache[$p_obj->getID()]) == true) {
                                        if ($p_obj->getScheduleObject()->getStatus() == 10 and $p_obj->getTimeStamp() < $p_obj->getScheduleObject()->getEndTime()) { //Be sure to ignore Absence scheduled shifts.
                                            if (TTDate::inWindow($p_obj->getTimeStamp(), $p_obj->getScheduleObject()->getEndTime(), $ep_obj->getGrace()) == true) {
                                                Debug::text('	 Within Grace time, IGNORE EXCEPTION: ', __FILE__, __LINE__, __METHOD__, 10);
                                            } elseif (TTDate::inWindow($p_obj->getTimeStamp(), $p_obj->getScheduleObject()->getEndTime(), $ep_obj->getWatchWindow()) == true) {
                                                Debug::text('	 NOT Within Grace time, SET EXCEPTION: ', __FILE__, __LINE__, __METHOD__, 10);

                                                $tmp_exception = array(
                                                    'user_id' => $user_id,
                                                    'date_stamp' => $date_stamp,
                                                    'exception_policy_id' => $ep_obj->getId(),
                                                    'type_id' => $type_id,
                                                    'punch_id' => $p_obj->getID(),
                                                    'punch_control_id' => false,
                                                    'punch_obj' => $p_obj,
                                                    'schedule_obj' => $p_obj->getScheduleObject(),
                                                );

                                                if ($x == $total_punches) { //Trigger exception if we're the last punch.
                                                    $current_exceptions[] = $tmp_exception;
                                                } //else { //Save exception to be triggered if the next punch doesn't match the same time.
                                            }
                                        } else {
                                            Debug::text('	 NO Working Schedule Found', __FILE__, __LINE__, __METHOD__, 10);
                                        }
                                    } else {
                                        Debug::text('	 NO Schedule Found', __FILE__, __LINE__, __METHOD__, 10);
                                    }
                                } elseif ($p_obj->getType() == 10 and $p_obj->getStatus() == 10) { //Normal In
                                    //This comes after an OUT punch, so we need to check if there are two punches
                                    //in a row with the same timestamp, if so ignore the exception.
                                    if (isset($tmp_exception) and $p_obj->getTimeStamp() == $prev_punch_time_stamp) {
                                        unset($tmp_exception);
                                    } elseif (isset($tmp_exception)) {
                                        $current_exceptions[] = $tmp_exception; //Set exception.
                                    }
                                }
                                $prev_punch_time_stamp = $p_obj->getTimeStamp();

                                $x++;
                            }
                        }
                        unset($tmp_exception, $x, $prev_punch_time_stamp);
                        break;
                    case 's6': //Out Late
                        if (is_array($plf) and count($plf) > 0) {
                            $prev_punch_time_stamp = false;
                            foreach ($plf as $p_obj) {
                                $punch_pairs[$p_obj->getPunchControlID()][] = array('status_id' => $p_obj->getStatus(), 'punch_control_id' => $p_obj->getPunchControlID(), 'time_stamp' => $p_obj->getTimeStamp());

                                //Ignore transfer punches to optimize cases where many punches exist.
                                if ($p_obj->getTransfer() == false and $prev_punch_time_stamp != $p_obj->getTimeStamp() and $p_obj->getType() == 10 and $p_obj->getStatus() == 20) { //Normal Out
                                    if (!isset($scheduled_id_cache[$p_obj->getID()])) {
                                        $scheduled_id_cache[$p_obj->getID()] = $p_obj->findScheduleID(null, $user_id);
                                    }
                                    if ($p_obj->setScheduleID($scheduled_id_cache[$p_obj->getID()]) == true) {
                                        if ($p_obj->getScheduleObject()->getStatus() == 10 and $p_obj->getTimeStamp() > $p_obj->getScheduleObject()->getEndTime()) { //Be sure to ignore Absence scheduled shifts.
                                            if (TTDate::inWindow($p_obj->getTimeStamp(), $p_obj->getScheduleObject()->getEndTime(), $ep_obj->getGrace()) == true) {
                                                Debug::text('	 Within Grace time, IGNORE EXCEPTION: ', __FILE__, __LINE__, __METHOD__, 10);
                                            } elseif (TTDate::inWindow($p_obj->getTimeStamp(), $p_obj->getScheduleObject()->getEndTime(), $ep_obj->getWatchWindow()) == true) {
                                                Debug::text('	 NOT Within Grace time, SET EXCEPTION: ', __FILE__, __LINE__, __METHOD__, 10);
                                                $current_exceptions[] = array(
                                                    'user_id' => $user_id,
                                                    'date_stamp' => $date_stamp,
                                                    'exception_policy_id' => $ep_obj->getId(),
                                                    'type_id' => $type_id,
                                                    'punch_id' => $p_obj->getID(),
                                                    'punch_control_id' => false,
                                                    'punch_obj' => $p_obj,
                                                    'schedule_obj' => $p_obj->getScheduleObject(),
                                                );
                                            }
                                        } else {
                                            Debug::text('	 NO Working Schedule Found', __FILE__, __LINE__, __METHOD__, 10);
                                        }
                                    } else {
                                        Debug::text('	 NO Schedule Found', __FILE__, __LINE__, __METHOD__, 10);
                                    }
                                }
                                $prev_punch_time_stamp = $p_obj->getTimeStamp();
                            }

                            //Trigger exception if no out punch and we have passed schedule out time.
                            //	- Schedule is found
                            //	- Make sure the user is missing an OUT punch.
                            //	- Current time is after schedule end time
                            //	- Current time is after exception grace time
                            //	- Current time is before schedule end time + maximum shift time.
                            if (isset($punch_pairs) and is_array($slf) and count($slf) > 0) {
                                foreach ($punch_pairs as $punch_control_id => $punch_pair) {
                                    if (count($punch_pair) != 2) {
                                        Debug::text('aFound Missing Punch: ', __FILE__, __LINE__, __METHOD__, 10);

                                        if ($punch_pair[0]['status_id'] == 10) { //Missing Out Punch
                                            Debug::text('bFound Missing Out Punch: ', __FILE__, __LINE__, __METHOD__, 10);

                                            foreach ($slf as $s_obj) {
                                                Debug::text('Punch: ' . TTDate::getDate('DATE+TIME', $punch_pair[0]['time_stamp']) . ' Schedule Start Time: ' . TTDate::getDate('DATE+TIME', $s_obj->getStartTime()) . ' End Time: ' . TTDate::getDate('DATE+TIME', $s_obj->getEndTime()), __FILE__, __LINE__, __METHOD__, 10);
                                                //Because this is just an IN punch, make sure the IN punch is before the schedule end time
                                                //So we can eliminate split shift schedules.
                                                if ($punch_pair[0]['time_stamp'] <= $s_obj->getEndTime()
                                                    and $current_epoch >= $s_obj->getEndTime() and $current_epoch <= ($s_obj->getEndTime() + $premature_delay)
                                                ) {
                                                    if (TTDate::inWindow($current_epoch, $s_obj->getEndTime(), $ep_obj->getGrace()) == true) {
                                                        Debug::text('	 Within Grace time, IGNORE EXCEPTION: ', __FILE__, __LINE__, __METHOD__, 10);
                                                    } else {
                                                        Debug::text('	 NOT Within Grace time, SET EXCEPTION: ', __FILE__, __LINE__, __METHOD__, 10);
                                                        $current_exceptions[] = array(
                                                            'user_id' => $user_id,
                                                            'date_stamp' => $date_stamp,
                                                            'exception_policy_id' => $ep_obj->getId(),
                                                            'type_id' => $type_id,
                                                            'punch_id' => false,
                                                            'punch_control_id' => $punch_pair[0]['punch_control_id'],
                                                            'schedule_obj' => $s_obj,
                                                        );
                                                    }
                                                }
                                            }
                                        }
                                    }
                                    //else {
                                    //	Debug::text('No Missing Punches...', __FILE__, __LINE__, __METHOD__, 10);
                                    //}
                                }
                            }
                            unset($punch_pairs, $punch_pair);
                        }
                        break;
                    case 'sb': //Not Scheduled Branch/Department
                    case 'sc': //Not Scheduled Job/Task
                        if (is_array($plf) and count($plf) > 0) {
                            $prev_punch_time_stamp = false;
                            foreach ($plf as $p_obj) {
                                $punch_pairs[$p_obj->getPunchControlID()][] = array('status_id' => $p_obj->getStatus(), 'punch_control_id' => $p_obj->getPunchControlID(), 'time_stamp' => $p_obj->getTimeStamp());

                                //How do we handle transfer punches? Should we just check Normal IN punches that aren't transfers punches?
                                //For now consider all IN punches, even transfer punches.
                                //if ( $prev_punch_time_stamp != $p_obj->getTimeStamp() AND $p_obj->getType() == 10 AND $p_obj->getStatus() == 10 ) {
                                if ($p_obj->getStatus() == 10) {
                                    if (!isset($scheduled_id_cache[$p_obj->getID()])) {
                                        $scheduled_id_cache[$p_obj->getID()] = $p_obj->findScheduleID(null, $user_id);
                                    }
                                    if ($p_obj->setScheduleID($scheduled_id_cache[$p_obj->getID()]) == true) {
                                        if (is_object($p_obj->getPunchControlObject())
                                            and (
                                                (strtolower($ep_obj->getType()) == 'sb' and $p_obj->getScheduleObject()->getBranch() > 0 and $p_obj->getPunchControlObject()->getBranch() != $p_obj->getScheduleObject()->getBranch())
                                                or
                                                (strtolower($ep_obj->getType()) == 'sb' and $p_obj->getScheduleObject()->getDepartment() > 0 and $p_obj->getPunchControlObject()->getDepartment() != $p_obj->getScheduleObject()->getDepartment())
                                            )
                                        ) {
                                            Debug::text('	 Punch Branch/Department does not match scheduled branch/department: ', __FILE__, __LINE__, __METHOD__, 10);
                                            $current_exceptions[] = array(
                                                'user_id' => $user_id,
                                                'date_stamp' => $date_stamp,
                                                'exception_policy_id' => $ep_obj->getId(),
                                                'type_id' => $type_id,
                                                'punch_id' => $p_obj->getID(),
                                                'punch_control_id' => false,
                                                'punch_obj' => $p_obj,
                                                'schedule_obj' => $p_obj->getScheduleObject(),
                                            );
                                        }
                                    } else {
                                        Debug::text('	 NO Schedule Found', __FILE__, __LINE__, __METHOD__, 10);
                                    }
                                }
                                $prev_punch_time_stamp = $p_obj->getTimeStamp();
                            }
                            unset($punch_pairs, $punch_pair, $prev_punch_time_stamp);
                        }
                        break;
                    case 'g1':
                        if (is_array($plf) and count($plf) > 0) {
                            foreach ($plf as $p_obj) {
                                if ($p_obj->getLongitude() != false and $p_obj->getLatitude() != false) {
                                    $glf = TTnew('GEOFenceListFactory');
                                    $glf->getByCompanyIdAndGEOLocationAndBranchAndDepartmentAndJobAndTask($this->getUserObject()->getCompany(), $p_obj->getLatitude(), $p_obj->getLongitude(), $p_obj->getPositionAccuracy(), $p_obj->getPunchControlObject()->getBranch(), $p_obj->getPunchControlObject()->getDepartment(), $p_obj->getPunchControlObject()->getJob(), $p_obj->getPunchControlObject()->getJobItem(), 1);
                                    Debug::Text('GEO Fences found: ' . $glf->getRecordCount() . ' Punch Latitude: ' . $p_obj->getLatitude() . ' Punch Longitude: ' . $p_obj->getLongitude() . ' Punch Radius: ' . $p_obj->getPositionAccuracy() . ' ID: ' . $p_obj->getID(), __FILE__, __LINE__, __METHOD__, 10);
                                    if ($glf->getRecordCount() == 0) {
                                        $current_exceptions[] = array(
                                            'user_id' => $user_id,
                                            'date_stamp' => $date_stamp,
                                            'exception_policy_id' => $ep_obj->getId(),
                                            'type_id' => $type_id,
                                            'punch_id' => $p_obj->getId(),
                                            'punch_control_id' => $p_obj->getPunchControlID(),
                                        );
                                    }
                                    unset($geo_location, $glf);
                                }
                            }
                        }
                        break;
                    case 'm1': //Missing In Punch
                        if (is_array($plf) and count($plf) > 0) {
                            foreach ($plf as $p_obj) {
                                //Debug::text(' Punch: Status: '. $p_obj->getStatus() .' Punch Control ID: '. $p_obj->getPunchControlID() .' Punch ID: '. $p_obj->getId() .' TimeStamp: '. $p_obj->getTimeStamp(), __FILE__, __LINE__, __METHOD__, 10);
                                if ($type_id == 5 and $p_obj->getTimeStamp() < ($current_epoch - $premature_delay)) {
                                    $type_id = 50;
                                }

                                $punch_pairs[$p_obj->getPunchControlID()][] = array('status_id' => $p_obj->getStatus(), 'punch_control_id' => $p_obj->getPunchControlID(), 'punch_id' => $p_obj->getId());
                            }

                            if (isset($punch_pairs)) {
                                foreach ($punch_pairs as $punch_control_id => $punch_pair) {
                                    //Debug::Arr($punch_pair, 'Punch Pair for Control ID:'. $punch_control_id, __FILE__, __LINE__, __METHOD__, 10);

                                    if (count($punch_pair) != 2) {
                                        Debug::text('a1Found Missing Punch: ', __FILE__, __LINE__, __METHOD__, 10);

                                        if ($punch_pair[0]['status_id'] == 20) { //Missing In Punch
                                            Debug::text('b1Found Missing In Punch: ', __FILE__, __LINE__, __METHOD__, 10);
                                            $current_exceptions[] = array(
                                                'user_id' => $user_id,
                                                'date_stamp' => $date_stamp,
                                                'exception_policy_id' => $ep_obj->getId(),
                                                'type_id' => $type_id,
                                                'punch_id' => false,
                                                'punch_control_id' => $punch_pair[0]['punch_control_id'],
                                            );
                                        }
                                    }
                                    //else {
                                    //	Debug::text('No Missing Punches...', __FILE__, __LINE__, __METHOD__, 10);
                                    //}
                                }
                            }
                            unset($punch_pairs, $punch_pair);
                        }
                        break;
                    case 'm2': //Missing Out Punch
                        if (is_array($plf) and count($plf) > 0) {
                            foreach ($plf as $p_obj) {
                                //Debug::text(' Punch: Status: '. $p_obj->getStatus() .' Punch Control ID: '. $p_obj->getPunchControlID() .' Punch ID: '. $p_obj->getId() .' TimeStamp: '. TTDate::getDATE('DATE+TIME', $p_obj->getTimeStamp() ), __FILE__, __LINE__, __METHOD__, 10);

                                //This causes the exception to trigger if the first punch pair is more than the Maximum Shift time away from the current punch,
                                //ie: In: 1:00AM, Out: 2:00AM, In 3:00PM (Maximum Shift Time less than 12hrs). The missing punch exception will be triggered immediately upon the 3:00PM punch.
                                //if ( $type_id == 5 AND $p_obj->getTimeStamp() < ($current_epoch - $premature_delay) ) {
                                //	$type_id = 50;
                                //}

                                $punch_pairs[$p_obj->getPunchControlID()][] = array('status_id' => $p_obj->getStatus(), 'punch_control_id' => $p_obj->getPunchControlID(), 'time_stamp' => $p_obj->getTimeStamp());
                            }

                            if (isset($punch_pairs)) {
                                foreach ($punch_pairs as $punch_control_id => $punch_pair) {
                                    if (count($punch_pair) != 2) {
                                        Debug::text('a2Found Missing Punch: ', __FILE__, __LINE__, __METHOD__, 10);

                                        if ($punch_pair[0]['status_id'] == 10) { //Missing Out Punch
                                            Debug::text('b2Found Missing Out Punch: ', __FILE__, __LINE__, __METHOD__, 10);

                                            //Make sure we are at least MaximumShift Time from the matching In punch before trigging this exception.
                                            //Even when an supervisor is entering punches for today, make missing out punch pre-mature if the maximum shift time isn't exceeded.
                                            //This will prevent timesheet recalculations from having missing punches for everyone today.
                                            //if ( $type_id == 5 AND $punch_pair[0]['time_stamp'] < ($current_epoch - $premature_delay) ) {
                                            if ($punch_pair[0]['time_stamp'] < ($current_epoch - $premature_delay)) {
                                                $type_id = 50;
                                            } else {
                                                $type_id = 5;
                                            }

                                            $current_exceptions[] = array(
                                                'user_id' => $user_id,
                                                'date_stamp' => $date_stamp,
                                                'exception_policy_id' => $ep_obj->getId(),
                                                'type_id' => $type_id,
                                                'punch_id' => false,
                                                'punch_control_id' => $punch_pair[0]['punch_control_id'],
                                            );
                                        }
                                    }
                                    //else {
                                    //	Debug::text('No Missing Punches...', __FILE__, __LINE__, __METHOD__, 10);
                                    //}
                                }
                            }
                            unset($punch_pairs, $punch_pair);
                        }
                        break;
                    case 'm3': //Missing Lunch In/Out punch
                        if (is_array($plf) and count($plf) > 0) {
                            //We need to account for cases where they may punch IN from lunch first, then Out. (reverse order)
                            //As well as just a Lunch In punch and nothing else.
                            foreach ($plf as $p_obj) {
                                $punches[] = $p_obj; //Collect punches so we can easily check prev/next punches below.
                            }

                            if (isset($punches) and is_array($punches)) {
                                foreach ($punches as $key => $p_obj) {
                                    if ($p_obj->getType() == 20) { //Lunch
                                        Debug::text(' Punch: Status: ' . $p_obj->getStatus() . ' Type: ' . $p_obj->getType() . ' Punch Control ID: ' . $p_obj->getPunchControlID() . ' TimeStamp: ' . $p_obj->getTimeStamp(), __FILE__, __LINE__, __METHOD__, 10);
                                        if ($p_obj->getStatus() == 10) {
                                            //This is a Lunch IN punch, Make sure previous punch is Lunch/Out
                                            if (!isset($punches[($key - 1)])
                                                or (isset($punches[($key - 1)]) and is_object($punches[($key - 1)])
                                                    and ($punches[($key - 1)]->getType() != 20
                                                        or $punches[($key - 1)]->getStatus() != 20))
                                            ) {
                                                //Invalid punch
                                                $invalid_punches[] = array('punch_id' => $p_obj->getId(), 'type_id' => 50); //This is always an ACTIVE exception, as there should always be a previous punch.
                                            }
                                        } else {
                                            //This is a Lunch OUT punch, Make sure next punch is Lunch/In
                                            if (!isset($punches[($key + 1)]) or (isset($punches[($key + 1)]) and is_object($punches[($key + 1)]) and ($punches[($key + 1)]->getType() != 20 or $punches[($key + 1)]->getStatus() != 10))) {
                                                //Invalid punch

                                                //Check if there is a punch immediately after.
                                                //  if there is, then this should always be an active exception.
                                                //  If there isn't, make sure its less than the premature delay and make it active.
                                                if (isset($punches[($key + 1)]) or ($p_obj->getTimeStamp() < ($current_epoch - $premature_delay))) {
                                                    $type_id = 50; //Active
                                                } else {
                                                    $type_id = 5; //PreMature
                                                }

                                                $invalid_punches[] = array('punch_id' => $p_obj->getId(), 'type_id' => $type_id);
                                            }
                                        }
                                    }
                                }
                                unset($punches, $key, $p_obj);

                                if (isset($invalid_punches) and count($invalid_punches) > 0) {
                                    foreach ($invalid_punches as $invalid_punch_arr) {
                                        Debug::text('Found Missing Lunch In/Out Punch: ', __FILE__, __LINE__, __METHOD__, 10);
                                        $current_exceptions[] = array(
                                            'user_id' => $user_id,
                                            'date_stamp' => $date_stamp,
                                            'exception_policy_id' => $ep_obj->getId(),
                                            'type_id' => $invalid_punch_arr['type_id'],
                                            'punch_id' => $invalid_punch_arr['punch_id'],
                                            'punch_control_id' => false,
                                        );
                                    }
                                    unset($invalid_punch_arr);
                                } else {
                                    Debug::text('Lunch Punches match up.', __FILE__, __LINE__, __METHOD__, 10);
                                }
                                unset($invalid_punches);
                            }
                        }
                        break;
                    case 'm4': //Missing Break In/Out punch
                        if (is_array($plf) and count($plf) > 0) {
                            //We need to account for cases where they may punch IN from break first, then Out. (reverse order)
                            //As well as just a break In punch and nothing else.
                            foreach ($plf as $p_obj) {
                                $punches[] = $p_obj;
                            }

                            if (isset($punches) and is_array($punches)) {
                                foreach ($punches as $key => $p_obj) {
                                    if ($p_obj->getType() == 30) { //Break
                                        Debug::text(' Punch: Status: ' . $p_obj->getStatus() . ' Type: ' . $p_obj->getType() . ' Punch Control ID: ' . $p_obj->getPunchControlID() . ' TimeStamp: ' . $p_obj->getTimeStamp(), __FILE__, __LINE__, __METHOD__, 10);
                                        if ($p_obj->getStatus() == 10) {
                                            //Make sure previous punch is Break/Out
                                            if (!isset($punches[($key - 1)])
                                                or (isset($punches[($key - 1)]) and is_object($punches[($key - 1)])
                                                    and ($punches[($key - 1)]->getType() != 30
                                                        or $punches[($key - 1)]->getStatus() != 20))
                                            ) {
                                                //Invalid punch
                                                $invalid_punches[] = array('punch_id' => $p_obj->getId(), 'type_id' => 50); //This is always an ACTIVE exception, as there should always be a previous punch.
                                            }
                                        } else {
                                            //Make sure next punch is Break/In
                                            if (!isset($punches[($key + 1)]) or (isset($punches[($key + 1)]) and is_object($punches[($key + 1)]) and ($punches[($key + 1)]->getType() != 30 or $punches[($key + 1)]->getStatus() != 10))) {
                                                //Invalid punch

                                                //Check if there is a punch immediately after.
                                                //  if there is, then this should always be an active exception.
                                                //  If there isn't, make sure its less than the premature delay and make it active.
                                                if (isset($punches[($key + 1)]) or ($p_obj->getTimeStamp() < ($current_epoch - $premature_delay))) {
                                                    $type_id = 50; //Active
                                                } else {
                                                    $type_id = 5; //PreMature
                                                }

                                                $invalid_punches[] = array('punch_id' => $p_obj->getId(), 'type_id' => $type_id);
                                            }
                                        }
                                    }
                                }
                                unset($punches, $key, $p_obj);

                                if (isset($invalid_punches) and count($invalid_punches) > 0) {
                                    foreach ($invalid_punches as $invalid_punch_arr) {
                                        Debug::text('Found Missing Break In/Out Punch: ', __FILE__, __LINE__, __METHOD__, 10);
                                        $current_exceptions[] = array(
                                            'user_id' => $user_id,
                                            'date_stamp' => $date_stamp,
                                            'exception_policy_id' => $ep_obj->getId(),
                                            'type_id' => $invalid_punch_arr['type_id'],
                                            'punch_id' => $invalid_punch_arr['punch_id'],
                                            'punch_control_id' => false,
                                        );
                                    }
                                    unset($invalid_punch_arr);
                                } else {
                                    Debug::text('Break Punches match up.', __FILE__, __LINE__, __METHOD__, 10);
                                }
                                unset($invalid_punches);
                            }
                        }
                        break;
                    case 'c1': //Missed Check-in
                        //Use grace period and make sure the employee punches within that period of time (usually a transfer punch, but break/lunch should work too)
                        if (is_array($plf) and count($plf) > 0 and $ep_obj->getGrace() > 0) {
                            $prev_punch_time_stamp = false;
                            $prev_punch_obj = false;

                            $x = 1;
                            foreach ($plf as $p_obj) {
                                Debug::text('	Missed Check-In Punch: ' . TTDate::getDate('DATE+TIME', $p_obj->getTimeStamp()) . ' Delay: ' . $premature_delay . ' Current Epoch: ' . $current_epoch, __FILE__, __LINE__, __METHOD__, 10);

                                //Handle punch pairs below. Only trigger on OUT punches.
                                //This is handle cases where they went too long without checking in, but still punched out.
                                if (is_object($prev_punch_obj) and $prev_punch_obj->getStatus() == 10
                                    and $p_obj->getStatus() == 20 and ($p_obj->getTimeStamp() - $prev_punch_time_stamp) > $ep_obj->getGrace()
                                ) { //Only check OUT punches when paired.
                                    Debug::text('	Triggering exception as employee missed check-in within: ' . ($p_obj->getTimeStamp() - $prev_punch_time_stamp), __FILE__, __LINE__, __METHOD__, 10);
                                    $current_exceptions[] = array(
                                        'user_id' => $user_id,
                                        'date_stamp' => $date_stamp,
                                        'exception_policy_id' => $ep_obj->getId(),
                                        'type_id' => $type_id,
                                        'punch_id' => $p_obj->getID(), //When paired, only attach to the out punch.
                                        'punch_control_id' => false,
                                        'punch_obj' => $p_obj,
                                        'schedule_obj' => $p_obj->getScheduleObject(),
                                    );
                                } elseif ($prev_punch_time_stamp !== false) {
                                    Debug::text('	Employee Checked-In within: ' . ($p_obj->getTimeStamp() - $prev_punch_time_stamp), __FILE__, __LINE__, __METHOD__, 10);
                                }

                                //Handle cases where there is a IN punch but no OUT punch yet.
                                //However ignore cases where there is a OUT punch but no IN punch.
                                if ($x == count($plf)
                                    and $p_obj->getStatus() == 10
                                    and ($current_epoch - $p_obj->getTimeStamp()) > $ep_obj->getGrace()
                                    and $p_obj->getTimeStamp() > ($current_epoch - $premature_delay)
                                ) {
                                    Debug::text('	Triggering excepetion as employee hasnt checked in yet, within: ' . ($current_epoch - $prev_punch_time_stamp), __FILE__, __LINE__, __METHOD__, 10);
                                    $current_exceptions[] = array(
                                        'user_id' => $user_id,
                                        'date_stamp' => $date_stamp,
                                        'exception_policy_id' => $ep_obj->getId(),
                                        'type_id' => $type_id,
                                        'punch_id' => false,
                                        'punch_control_id' => $p_obj->getPunchControlID(), //When not paired, attach to the punch control.
                                        'punch_obj' => $p_obj,
                                        'schedule_obj' => $p_obj->getScheduleObject(),
                                    );
                                }

                                $prev_punch_time_stamp = $p_obj->getTimeStamp();
                                $prev_punch_obj = $p_obj;
                                $x++;
                            }
                        }
                        unset($prev_punch_obj, $prev_punch_time_stamp, $x);
                        break;
                    case 'd1': //No Branch or Department
                        if (is_array($plf) and count($plf) > 0) {
                            foreach ($plf as $p_obj) {
                                $add_exception = false;

                                //In punches only
                                if ($p_obj->getStatus() == 10 and is_object($p_obj->getPunchControlObject())) {
                                    //If no Branches are setup, ignore checking them.
                                    if ($p_obj->getPunchControlObject()->getBranch() == ''
                                        or $p_obj->getPunchControlObject()->getBranch() == 0
                                        or $p_obj->getPunchControlObject()->getBranch() == false
                                    ) {
                                        //Make sure at least one task exists before triggering exception.
                                        $blf = TTNew('BranchListFactory');
                                        $blf->getByCompanyID($this->getUserObject()->getCompany(), 1); //Limit to just 1 record.
                                        if ($blf->getRecordCount() > 0) {
                                            $add_exception = true;
                                        }
                                    }

                                    //If no Departments are setup, ignore checking them.
                                    if ($p_obj->getPunchControlObject()->getDepartment() == ''
                                        or $p_obj->getPunchControlObject()->getDepartment() == 0
                                        or $p_obj->getPunchControlObject()->getDepartment() == false
                                    ) {
                                        //Make sure at least one task exists before triggering exception.
                                        $dlf = TTNew('DepartmentListFactory');
                                        $dlf->getByCompanyID($this->getUserObject()->getCompany(), 1); //Limit to just 1 record.
                                        if ($dlf->getRecordCount() > 0) {
                                            $add_exception = true;
                                        }
                                    }

                                    if ($add_exception === true) {
                                        $current_exceptions[] = array(
                                            'user_id' => $user_id,
                                            'date_stamp' => $date_stamp,
                                            'exception_policy_id' => $ep_obj->getId(),
                                            'type_id' => $type_id,
                                            'punch_id' => $p_obj->getId(),
                                            'punch_control_id' => $p_obj->getPunchControlId(),
                                        );
                                    }
                                }
                            }
                        }
                        break;
                    case 's7': //Over Scheduled Hours
                        if (is_array($plf) and count($plf) > 0) {
                            //FIXME: Assign this exception to the last punch of the day, so it can be related back to a punch branch/department?
                            //This ONLY takes in to account WORKED hours, not paid absence hours.
                            //FIXME: Do we want to trigger this before their last out punch?
                            $schedule_total_time = 0;

                            if (is_array($slf) and count($slf) > 0) {
                                //Check for schedule policy
                                foreach ($slf as $s_obj) {
                                    Debug::text(' Schedule Total Time: ' . $s_obj->getTotalTime(), __FILE__, __LINE__, __METHOD__, 10);
                                    $schedule_total_time += $s_obj->getTotalTime();
                                }

                                $daily_total_time = 0;
                                if ($schedule_total_time > 0) {
                                    //Get daily total time.
                                    //Take into account auto-deduct/add meal policies, but not paid absences.
                                    $udtlf = $this->filterUserDateTotalDataByObjectTypeIDs($date_stamp, $date_stamp, array(10, 100, 110)); //Worked time only.
                                    if (is_array($udtlf) and count($udtlf) > 0) {
                                        foreach ($udtlf as $udt_obj) {
                                            $daily_total_time += $udt_obj->getTotalTime();
                                        }
                                    }
                                    unset($udtlf, $udt_obj);
                                    Debug::text(' Daily Total Time: ' . $daily_total_time . ' Schedule Total Time: ' . $schedule_total_time, __FILE__, __LINE__, __METHOD__, 10);

                                    if ($daily_total_time > 0 and $daily_total_time > ($schedule_total_time + $ep_obj->getGrace())) {
                                        Debug::text(' Worked Over Scheduled Hours', __FILE__, __LINE__, __METHOD__, 10);

                                        $current_exceptions[] = array(
                                            'user_id' => $user_id,
                                            'date_stamp' => $date_stamp,
                                            'exception_policy_id' => $ep_obj->getId(),
                                            'type_id' => $type_id,
                                            'punch_id' => false,
                                            'punch_control_id' => false,
                                        );
                                    } else {
                                        Debug::text(' DID NOT Work Over Scheduled Hours', __FILE__, __LINE__, __METHOD__, 10);
                                    }
                                }
                            } else {
                                Debug::text(' Not Scheduled', __FILE__, __LINE__, __METHOD__, 10);
                            }
                        }
                        break;
                    case 's8': //Under Scheduled Hours
                        if (is_array($plf) and count($plf) > 0) {
                            //FIXME: Assign this exception to the last punch of the day, so it can be related back to a punch branch/department?
                            //This ONLY takes in to account WORKED hours, not paid absence hours.
                            $schedule_total_time = 0;

                            if (is_array($slf) and count($slf) > 0) {
                                //Check for schedule policy
                                foreach ($slf as $s_obj) {
                                    Debug::text(' Schedule Total Time: ' . $s_obj->getTotalTime(), __FILE__, __LINE__, __METHOD__, 10);
                                    $schedule_total_time += $s_obj->getTotalTime();
                                }

                                $daily_total_time = 0;
                                if ($schedule_total_time > 0) {
                                    //Get daily total time.
                                    //Take into account auto-deduct/add meal policies
                                    $udtlf = $this->filterUserDateTotalDataByObjectTypeIDs($date_stamp, $date_stamp, array(10, 100, 110)); //Worked time only.
                                    if (is_array($udtlf) and count($udtlf) > 0) {
                                        foreach ($udtlf as $udt_obj) {
                                            $daily_total_time += $udt_obj->getTotalTime();
                                        }
                                    }
                                    unset($udtlf, $udt_obj);
                                    Debug::text(' Daily Total Time: ' . $daily_total_time . ' Schedule Total Time: ' . $schedule_total_time, __FILE__, __LINE__, __METHOD__, 10);

                                    if ($daily_total_time < ($schedule_total_time - $ep_obj->getGrace())) {
                                        Debug::text(' Worked Under Scheduled Hours', __FILE__, __LINE__, __METHOD__, 10);

                                        if ($type_id == 5 and $date_stamp < TTDate::getBeginDayEpoch(($current_epoch - $premature_delay))) {
                                            $type_id = 50;
                                        }

                                        $current_exceptions[] = array(
                                            'user_id' => $user_id,
                                            'date_stamp' => $date_stamp,
                                            'exception_policy_id' => $ep_obj->getId(),
                                            'type_id' => $type_id,
                                            'punch_id' => false,
                                            'punch_control_id' => false,
                                        );
                                    } else {
                                        Debug::text(' DID NOT Work Under Scheduled Hours', __FILE__, __LINE__, __METHOD__, 10);
                                    }
                                }
                            } else {
                                Debug::text(' Not Scheduled', __FILE__, __LINE__, __METHOD__, 10);
                            }
                        }
                        break;
                    case 'o1': //Over Daily Time.
                        if (is_array($plf) and count($plf) > 0) {
                            //FIXME: Assign this exception to the last punch of the day, so it can be related back to a punch branch/department?
                            //This ONLY takes in to account WORKED hours, not paid absence hours.
                            //FIXME: Do we want to trigger this before their last out punch?
                            $daily_total_time = 0;

                            //Get daily total time.
                            //Take into account auto-deduct/add meal policies
                            $udtlf = $this->filterUserDateTotalDataByObjectTypeIDs($date_stamp, $date_stamp, array(10, 100, 110)); //Worked time only.
                            if (is_array($udtlf) and count($udtlf) > 0) {
                                foreach ($udtlf as $udt_obj) {
                                    $daily_total_time += $udt_obj->getTotalTime();
                                }
                            }
                            unset($udtlf, $udt_obj);
                            Debug::text(' Daily Total Time: ' . $daily_total_time . ' Watch Window: ' . $ep_obj->getWatchWindow() . ' User ID: ' . $user_id, __FILE__, __LINE__, __METHOD__, 10);

                            if ($daily_total_time > 0 and $daily_total_time > $ep_obj->getWatchWindow()) {
                                Debug::text(' Worked Over Daily Hours', __FILE__, __LINE__, __METHOD__, 10);

                                $current_exceptions[] = array(
                                    'user_id' => $user_id,
                                    'date_stamp' => $date_stamp,
                                    'exception_policy_id' => $ep_obj->getId(),
                                    'type_id' => $type_id,
                                    'punch_id' => false,
                                    'punch_control_id' => false,
                                );
                            } else {
                                Debug::text(' DID NOT Work Over Scheduled Hours', __FILE__, __LINE__, __METHOD__, 10);
                            }
                        }
                        break;
                    case 'o2': //Over Weekly Time.
                    case 's9': //Over Weekly Scheduled Time.
                        if (is_array($plf) and count($plf) > 0) {
                            //FIXME: Assign this exception to the last punch of the day, so it can be related back to a punch branch/department?
                            //Get Pay Period Schedule info
                            //FIXME: Do we want to trigger this before their last out punch?
                            Debug::text('Start Week Day ID: ' . $start_week_day_id, __FILE__, __LINE__, __METHOD__, 10);

                            $weekly_scheduled_total_time = 0;

                            //Currently we only consider committed scheduled shifts. We may need to change this to take into account
                            //recurring scheduled shifts that haven't been committed yet as well.
                            //In either case though we should take into account the entires week worth of scheduled time even if we are only partially through
                            //the week, that way we won't be triggering s9 exceptions on a Wed and a Fri or something, it will only occur on the last days of the week.
                            if (strtolower($ep_obj->getType()) == 's9') {
                                $tmp_slf = TTnew('ScheduleListFactory');
                                $tmp_slf->getByUserIdAndStartDateAndEndDate($user_id, TTDate::getBeginWeekEpoch($date_stamp, $start_week_day_id), TTDate::getEndWeekEpoch($date_stamp, $start_week_day_id));
                                if ($tmp_slf->getRecordCount() > 0) {
                                    foreach ($tmp_slf as $s_obj) {
                                        if ($s_obj->getStatus() == 10) { //Only working shifts.
                                            $weekly_scheduled_total_time += $s_obj->getTotalTime();
                                        }
                                    }
                                }
                                unset($tmp_slf, $s_obj);
                            }

                            //This ONLY takes in to account WORKED hours, not paid absence hours.
                            $weekly_total_time = 0;

                            //Get daily total time.
                            $udtlf = TTnew('UserDateTotalListFactory');
                            $weekly_total_time = $udtlf->getWorkedTimeSumByUserIDAndStartDateAndEndDate($user_id, TTDate::getBeginWeekEpoch($date_stamp, $start_week_day_id), $date_stamp);

                            Debug::text(' Weekly Total Time: ' . $weekly_total_time . ' Weekly Scheduled Total Time: ' . $weekly_scheduled_total_time . ' Watch Window: ' . $ep_obj->getWatchWindow() . ' Grace: ' . $ep_obj->getGrace() . ' User ID: ' . $user_id, __FILE__, __LINE__, __METHOD__, 10);
                            //Don't trigger either of these exceptions unless both the worked and scheduled time is greater than 0. If they aren't scheduled at all
                            //it should trigger a Unscheduled Absence exception instead of a over weekly scheduled time exception.
                            if ((strtolower($ep_obj->getType()) == 'o2' and $weekly_total_time > 0 and $weekly_total_time > $ep_obj->getWatchWindow())
                                or (strtolower($ep_obj->getType()) == 's9' and $weekly_scheduled_total_time > 0 and $weekly_total_time > 0 and $weekly_total_time > ($weekly_scheduled_total_time + $ep_obj->getGrace()))
                            ) {
                                Debug::text(' Worked Over Weekly Hours', __FILE__, __LINE__, __METHOD__, 10);
                                $current_exceptions[] = array(
                                    'user_id' => $user_id,
                                    'date_stamp' => $date_stamp,
                                    'exception_policy_id' => $ep_obj->getId(),
                                    'type_id' => $type_id,
                                    'punch_id' => false,
                                    'punch_control_id' => false,
                                );
                            } else {
                                Debug::text(' DID NOT Work Over Scheduled Hours', __FILE__, __LINE__, __METHOD__, 10);
                            }
                        }
                        break;
                    case 'l1': //Long Lunch
                    case 'l2': //Short Lunch
                        if (is_array($plf) and count($plf) > 0) {
                            //Get all lunch punches.
                            $pair = 0;
                            $x = 0;
                            $out_for_lunch = false;
                            foreach ($plf as $p_obj) {
                                if ($p_obj->getStatus() == 20 and $p_obj->getType() == 20) {
                                    $lunch_out_timestamp = $p_obj->getTimeStamp();
                                    $lunch_punch_arr[$pair]['punch_id'] = $p_obj->getId();
                                    $out_for_lunch = true;
                                } elseif ($out_for_lunch == true and $p_obj->getStatus() == 10 and $p_obj->getType() == 20) {
                                    $lunch_punch_arr[$pair][20] = $lunch_out_timestamp;
                                    $lunch_punch_arr[$pair][10] = $p_obj->getTimeStamp();
                                    $out_for_lunch = false;
                                    $pair++;
                                    unset($lunch_out_timestamp);
                                } else {
                                    $out_for_lunch = false;
                                }
                            }

                            if (isset($lunch_punch_arr)) {
                                //Debug::Arr($lunch_punch_arr, 'Lunch Punch Array: ', __FILE__, __LINE__, __METHOD__, 10);

                                $daily_total_time = 0;
                                $udtlf = $this->filterUserDateTotalDataByObjectTypeIDs($date_stamp, $date_stamp, array(10)); //Worked time only.
                                if (is_array($udtlf) and count($udtlf) > 0) {
                                    foreach ($udtlf as $udt_obj) {
                                        $daily_total_time += $udt_obj->getTotalTime();
                                    }
                                }
                                unset($udtlf, $udt_obj);
                                Debug::text(' Daily Total Time: ' . $daily_total_time . ' User ID: ' . $user_id, __FILE__, __LINE__, __METHOD__, 10);

                                foreach ($lunch_punch_arr as $pair => $time_stamp_arr) {
                                    if (isset($time_stamp_arr[10]) and isset($time_stamp_arr[20])) {
                                        $lunch_total_time = bcsub($time_stamp_arr[10], $time_stamp_arr[20]);
                                        Debug::text(' Lunch Total Time: ' . $lunch_total_time, __FILE__, __LINE__, __METHOD__, 10);

                                        $meal_time_policies = $this->filterMealTimePolicy($date_stamp, $daily_total_time);
                                        if (is_array($meal_time_policies) and count($meal_time_policies) > 0) {
                                            reset($meal_time_policies);
                                            $mp_obj = $meal_time_policies[key($meal_time_policies)];
                                        }

                                        if (isset($mp_obj) and is_object($mp_obj)) {
                                            $meal_policy_lunch_time = $mp_obj->getAmount();
                                            Debug::text('Meal Policy Time: ' . $meal_policy_lunch_time, __FILE__, __LINE__, __METHOD__, 10);

                                            $add_exception = false;
                                            if (strtolower($ep_obj->getType()) == 'l1'
                                                and $meal_policy_lunch_time > 0
                                                and $lunch_total_time > 0
                                                and $lunch_total_time > ($meal_policy_lunch_time + $ep_obj->getGrace())
                                            ) {
                                                $add_exception = true;
                                            } elseif (strtolower($ep_obj->getType()) == 'l2'
                                                and $meal_policy_lunch_time > 0
                                                and $lunch_total_time > 0
                                                and $lunch_total_time < ($meal_policy_lunch_time - $ep_obj->getGrace())
                                            ) {
                                                $add_exception = true;
                                            }

                                            if ($add_exception == true) {
                                                Debug::text('Adding Exception!', __FILE__, __LINE__, __METHOD__, 10);

                                                if (isset($time_stamp_arr['punch_id'])) {
                                                    $punch_id = $time_stamp_arr['punch_id'];
                                                } else {
                                                    $punch_id = false;
                                                }

                                                $current_exceptions[] = array(
                                                    'user_id' => $user_id,
                                                    'date_stamp' => $date_stamp,
                                                    'exception_policy_id' => $ep_obj->getId(),
                                                    'type_id' => $type_id,
                                                    'punch_id' => $punch_id,
                                                    'punch_control_id' => false,
                                                );
                                                unset($punch_id);
                                            } else {
                                                Debug::text('Not Adding Exception!', __FILE__, __LINE__, __METHOD__, 10);
                                            }
                                        }
                                    } else {
                                        Debug::text(' Lunch Punches not paired... Skipping!', __FILE__, __LINE__, __METHOD__, 10);
                                    }
                                }
                            } else {
                                Debug::text(' No Lunch Punches found, or none are paired.', __FILE__, __LINE__, __METHOD__, 10);
                            }
                        }
                        break;
                    case 'l3': //No Lunch
                        if (is_array($plf) and count($plf) > 0) {
                            //If they are scheduled or not, we can check for a meal policy and base our
                            //decision off that. We don't want a No Lunch exception on a 3hr short shift though.
                            //Also ignore this exception if the lunch is auto-deduct.
                            //**Try to assign this exception to a specific punch control id, so we can do searches based on punch branch.

                            $daily_total_time = 0;
                            $udtlf = $this->filterUserDateTotalDataByObjectTypeIDs($date_stamp, $date_stamp, array(10)); //Worked time only.
                            if (is_array($udtlf) and count($udtlf) > 0) {
                                foreach ($udtlf as $udt_obj) {
                                    $daily_total_time += $udt_obj->getTotalTime();
                                    $punch_control_total_time[$udt_obj->getPunchControlID()] = $udt_obj->getTotalTime();
                                }
                            }
                            unset($udtlf, $udt_obj);
                            Debug::text(' Daily Total Time: ' . $daily_total_time . ' User ID: ' . $user_id, __FILE__, __LINE__, __METHOD__, 10);
                            //Debug::Arr($punch_control_total_time, 'Punch Control Total Time: ', __FILE__, __LINE__, __METHOD__, 10);

                            //Find meal policy
                            //Use scheduled meal policy first.
                            $meal_policy_obj = null;

                            //Enable $always_return_at_least_one=TRUE so no matter what at least one meal policy is returned if it exists.
                            //This allows us to *not* trigger this exception when the user works less than the meal policy trigger time.
                            $meal_time_policies = $this->filterMealTimePolicy($date_stamp, $daily_total_time, array(15, 20), true); //Exclude auto-deduct meal policies.
                            if (is_array($meal_time_policies) and count($meal_time_policies) > 0) {
                                reset($meal_time_policies);
                                $meal_policy_obj = $meal_time_policies[key($meal_time_policies)]; //Get first
                            } elseif (is_array($meal_time_policies) and count($meal_time_policies) == 0) {
                                $meal_policy_obj = null; //Schedule defined, but no meal policy applies.
                            } else {
                                //There is no  meal policy or schedule policy with a meal policy assigned to it
                                //With out this we could still apply No meal exceptions, but they will happen even on
                                //a 2minute shift.
                                Debug::text('No Lunch policy, applying No meal exception.', __FILE__, __LINE__, __METHOD__, 10);
                                $meal_policy_obj = true;
                            }

                            if (is_object($meal_policy_obj) or $meal_policy_obj === true) {
                                $punch_control_id = false;

                                //Check meal policy type again here, as any meal policy type can be returned given the above $always_return_at_least_one=TRUE
                                if ($daily_total_time > 0 and ($meal_policy_obj === true or ($daily_total_time > $meal_policy_obj->getTriggerTime() and in_array($meal_policy_obj->getType(), array(15, 20))))) {
                                    //Check for meal punch.
                                    $meal_punch = false;
                                    $tmp_punch_total_time = 0;
                                    $tmp_punch_control_ids = array();
                                    foreach ($plf as $p_obj) {
                                        if ($p_obj->getType() == 20) { //20 = Lunch
                                            Debug::text('Found meal Punch: ' . $p_obj->getTimeStamp(), __FILE__, __LINE__, __METHOD__, 10);
                                            $meal_punch = true;
                                            break;
                                        }

                                        if (isset($punch_control_total_time[$p_obj->getPunchControlID()]) and !isset($tmp_punch_control_ids[$p_obj->getPunchControlID()])) {
                                            $tmp_punch_total_time += $punch_control_total_time[$p_obj->getPunchControlID()];
                                            if ($punch_control_id === false and ($meal_policy_obj === true or $tmp_punch_total_time > $meal_policy_obj->getTriggerTime())) {
                                                Debug::text('Found punch control for exception: ' . $p_obj->getPunchControlID() . ' Total Time: ' . $tmp_punch_total_time, __FILE__, __LINE__, __METHOD__, 10);
                                                $punch_control_id = $p_obj->getPunchControlID();
                                                //Don't meal the loop here, as we have to continue on and check for other meals.
                                            }
                                        }
                                        $tmp_punch_control_ids[$p_obj->getPunchControlID()] = true;
                                    }

                                    //If the last punch is before the premature delay, make this a mature exception instead.
                                    //However if the employee has punched out for the end of their shift (Normal Out), then we don't need to mark it as pre-mature anymore.
                                    //  FIXME: This can cause situations where the No Lunch exception is triggered,
                                    //  then the employee comes back to start a split shift and takes a lunch in that 2nd half of the shift,
                                    //  once that happens the exception will clear itself, but the supervisor would have already received the email.
                                    //  - Also have to take into account detecting lunch/break punches by punch time, as they appear as a Normal Out punch first
                                    //    then later get changed to Lunch Out and Lunch In punches once the employee returns. So we can't trigger this exception too soon in those cases.
                                    if ($type_id == 5
                                        and (
                                            (
                                                $p_obj->getType() == 10 and $p_obj->getStatus() == 20 and $p_obj->getTransfer() == false
                                                and
                                                ($meal_policy_obj === true
                                                    or (is_object($meal_policy_obj)
                                                        and (
                                                            $meal_policy_obj->getAutoDetectType() == 10 //Time Window policy detection.
                                                            or
                                                            ($meal_policy_obj->getAutoDetectType() == 20 and $p_obj->getTimeStamp() < ($current_epoch - $meal_policy_obj->getMaximumPunchTime()))) //Punch Time policy detection.
                                                    )
                                                )
                                            )
                                            or
                                            ($p_obj->getTimeStamp() < ($current_epoch - $premature_delay))
                                        )
                                    ) {
                                        $type_id = 50;
                                    }

                                    unset($tmp_punch_total_time, $tmp_punch_control_ids);

                                    if ($meal_punch == false) {
                                        Debug::text('Triggering No Lunch exception!', __FILE__, __LINE__, __METHOD__, 10);
                                        $current_exceptions[] = array(
                                            'user_id' => $user_id,
                                            'date_stamp' => $date_stamp,
                                            'exception_policy_id' => $ep_obj->getId(),
                                            'type_id' => $type_id,
                                            'punch_id' => false,
                                            'punch_control_id' => $punch_control_id,
                                        );
                                    }
                                }
                            }
                            unset($meal_time_policies, $meal_policy_obj, $tmp_punch_control_ids, $punch_control_total_time);
                        }
                        break;
                    case 'b1': //Long Break
                    case 'b2': //Short Break
                        if (is_array($plf) and count($plf) > 0) {
                            //Get all break punches.
                            $pair = 0;
                            $x = 0;
                            $out_for_break = false;
                            foreach ($plf as $p_obj) {
                                if ($p_obj->getStatus() == 20 and $p_obj->getType() == 30) {
                                    $break_out_timestamp = $p_obj->getTimeStamp();
                                    $break_punch_arr[$pair]['punch_id'] = $p_obj->getId();
                                    $out_for_break = true;
                                } elseif ($out_for_break == true and $p_obj->getStatus() == 10 and $p_obj->getType() == 30) {
                                    $break_punch_arr[$pair][20] = $break_out_timestamp;
                                    $break_punch_arr[$pair][10] = $p_obj->getTimeStamp();
                                    $out_for_break = false;
                                    $pair++;
                                    unset($break_out_timestamp);
                                } else {
                                    $out_for_break = false;
                                }
                            }
                            unset($pair);

                            if (isset($break_punch_arr)) {
                                //Debug::Arr($break_punch_arr, 'Break Punch Array: ', __FILE__, __LINE__, __METHOD__, 10);

                                //Get daily total time.
                                $daily_total_time = 0;
                                $udtlf = $this->filterUserDateTotalDataByObjectTypeIDs($date_stamp, $date_stamp, array(10, 100, 110)); //Worked time only.
                                if (is_array($udtlf) and count($udtlf) > 0) {
                                    foreach ($udtlf as $udt_obj) {
                                        $daily_total_time += $udt_obj->getTotalTime();
                                    }
                                }
                                unset($udtlf, $udt_obj);
                                Debug::text(' Daily Total Time: ' . $daily_total_time . ' User ID: ' . $user_id, __FILE__, __LINE__, __METHOD__, 10);

                                foreach ($break_punch_arr as $pair => $time_stamp_arr) {
                                    if (isset($time_stamp_arr[10]) and isset($time_stamp_arr[20])) {
                                        $break_total_time = bcsub($time_stamp_arr[10], $time_stamp_arr[20]);
                                        Debug::text(' Break Total Time: ' . $break_total_time, __FILE__, __LINE__, __METHOD__, 10);

                                        if (!isset($scheduled_id_cache[$p_obj->getID()])) {
                                            $scheduled_id_cache[$p_obj->getID()] = $p_obj->findScheduleID(null, $user_id);
                                        }

                                        //Check to see if they have a schedule policy
                                        $bplf = $this->filterBreakTimePolicy($date_stamp, $daily_total_time);
                                        if (is_array($bplf) and count($bplf) > 0) {
                                            Debug::text('Found Break Policy(ies) to apply: ' . count($bplf) . ' Pair: ' . $pair, __FILE__, __LINE__, __METHOD__, 10);

                                            foreach ($bplf as $bp_obj) {
                                                $bp_objs[] = $bp_obj;
                                            }
                                            unset($bplf, $bp_obj);

                                            if (isset($bp_objs[$pair]) and is_object($bp_objs[$pair])) {
                                                $bp_obj = $bp_objs[$pair];

                                                $break_policy_break_time = $bp_obj->getAmount();
                                                Debug::text('Break Policy Time: ' . $break_policy_break_time . ' ID: ' . $bp_obj->getID(), __FILE__, __LINE__, __METHOD__, 10);

                                                $add_exception = false;
                                                if (strtolower($ep_obj->getType()) == 'b1'
                                                    and $break_policy_break_time > 0
                                                    and $break_total_time > 0
                                                    and $break_total_time > ($break_policy_break_time + $ep_obj->getGrace())
                                                ) {
                                                    $add_exception = true;
                                                } elseif (strtolower($ep_obj->getType()) == 'b2'
                                                    and $break_policy_break_time > 0
                                                    and $break_total_time > 0
                                                    and $break_total_time < ($break_policy_break_time - $ep_obj->getGrace())
                                                ) {
                                                    $add_exception = true;
                                                }

                                                if ($add_exception == true) {
                                                    Debug::text('Adding Exception! ' . $ep_obj->getType(), __FILE__, __LINE__, __METHOD__, 10);

                                                    if (isset($time_stamp_arr['punch_id'])) {
                                                        $punch_id = $time_stamp_arr['punch_id'];
                                                    } else {
                                                        $punch_id = false;
                                                    }

                                                    $current_exceptions[] = array(
                                                        'user_id' => $user_id,
                                                        'date_stamp' => $date_stamp,
                                                        'exception_policy_id' => $ep_obj->getId(),
                                                        'type_id' => $type_id,
                                                        'punch_id' => $punch_id,
                                                        'punch_control_id' => false,
                                                    );
                                                    unset($punch_id);
                                                } else {
                                                    Debug::text('Not Adding Exception!', __FILE__, __LINE__, __METHOD__, 10);
                                                }

                                                unset($bp_obj);
                                            }
                                            unset($bp_objs);
                                        }
                                    } else {
                                        Debug::text(' Break Punches not paired... Skipping!', __FILE__, __LINE__, __METHOD__, 10);
                                    }
                                }
                            } else {
                                Debug::text(' No Break Punches found, or none are paired.', __FILE__, __LINE__, __METHOD__, 10);
                            }
                        }
                        break;
                    case 'b3': //Too Many Breaks
                    case 'b4': //Too Few Breaks
                        if (is_array($plf) and count($plf) > 0) {
                            //Get all break punches.
                            $pair = 0;
                            $x = 0;
                            $out_for_break = false;
                            foreach ($plf as $p_obj) {
                                if ($p_obj->getStatus() == 20 and $p_obj->getType() == 30) {
                                    $break_out_timestamp = $p_obj->getTimeStamp();
                                    $break_punch_arr[$pair]['punch_id'] = $p_obj->getId();
                                    $out_for_break = true;
                                } elseif ($out_for_break == true and $p_obj->getStatus() == 10 and $p_obj->getType() == 30) {
                                    $break_punch_arr[$pair][20] = $break_out_timestamp;
                                    $break_punch_arr[$pair][10] = $p_obj->getTimeStamp();
                                    $out_for_break = false;
                                    $pair++;
                                    unset($break_out_timestamp);
                                } else {
                                    $out_for_break = false;
                                }
                            }
                            unset($pair);

                            //Make sure we take into account how long they have currently worked, so we don't
                            //say too few breaks for 3hr shift that they employee took one break on.
                            //Trigger this exception if the employee doesn't take a break at all?
                            if (isset($break_punch_arr)) {
                                //Get daily total time.
                                $daily_total_time = 0;
                                $udtlf = $this->filterUserDateTotalDataByObjectTypeIDs($date_stamp, $date_stamp, array(10, 100, 110)); //Worked time only.
                                if (is_array($udtlf) and count($udtlf) > 0) {
                                    foreach ($udtlf as $udt_obj) {
                                        $daily_total_time += $udt_obj->getTotalTime();
                                    }
                                }
                                unset($udtlf, $udt_obj);
                                Debug::text(' Daily Total Time: ' . $daily_total_time . ' User ID: ' . $user_id, __FILE__, __LINE__, __METHOD__, 10);

                                //Check to see if they have a schedule policy
                                $bplf = $this->filterBreakTimePolicy($date_stamp, $daily_total_time);
                                $break_policy_uses_punch_time_detection = false;
                                if (is_array($bplf)) {
                                    foreach ($bplf as $bp_obj) {
                                        if ($bp_obj->getAutoDetectType() == 20) {
                                            $break_policy_uses_punch_time_detection = true;
                                            break;
                                        }
                                    }
                                }
                                $allowed_breaks = count($bplf);
                                unset($bplf, $bp_obj);

                                Debug::text(' Break Policy uses Punch Time Detection: ' . (int)$break_policy_uses_punch_time_detection, __FILE__, __LINE__, __METHOD__, 10);

                                //If the last punch is before the premature delay, make this a mature exception instead.
                                //However if the employee has punched out for the end of their shift (Normal Out), then we don't need to mark it as pre-mature anymore.
                                //FIXME: Trigger too many break exception sooner, as that one can be immediate.
                                //  - Also have to take into account detecting lunch/break punches by punch time, as they appear as a Normal Out punch first
                                //    then later get changed to Lunch Out and Lunch In punches once the employee returns. So we can't trigger this exception too soon in those cases.
                                if ($type_id == 5
                                    and (
                                        (
                                            $p_obj->getType() == 10 and $p_obj->getStatus() == 20 and $p_obj->getTransfer() == false
                                            and
                                            $break_policy_uses_punch_time_detection == false
                                        )
                                        or
                                        ($p_obj->getTimeStamp() < ($current_epoch - $premature_delay))
                                    )
                                ) {
                                    $type_id = 50;
                                }
                                $total_breaks = count($break_punch_arr);

                                foreach ($break_punch_arr as $pair => $time_stamp_arr) {
                                    if (isset($time_stamp_arr[10]) and isset($time_stamp_arr[20])) {
                                        $break_total_time = bcsub($time_stamp_arr[10], $time_stamp_arr[20]);
                                        Debug::text(' Break Total Time: ' . $break_total_time, __FILE__, __LINE__, __METHOD__, 10);

                                        if (!isset($scheduled_id_cache[$p_obj->getID()])) {
                                            $scheduled_id_cache[$p_obj->getID()] = $p_obj->findScheduleID(null, $user_id);
                                        }

                                        $add_exception = false;
                                        if (strtolower($ep_obj->getType()) == 'b3' and $total_breaks > $allowed_breaks) {
                                            Debug::text(' Too many breaks taken...', __FILE__, __LINE__, __METHOD__, 10);
                                            $add_exception = true;
                                            $type_id = 50; //This can be triggered immediately.
                                        } elseif (strtolower($ep_obj->getType()) == 'b4' and $total_breaks < $allowed_breaks) {
                                            Debug::text(' Too few breaks taken...', __FILE__, __LINE__, __METHOD__, 10);
                                            $add_exception = true;
                                        } else {
                                            Debug::text(' Proper number of breaks taken...', __FILE__, __LINE__, __METHOD__, 10);
                                        }

                                        if ($add_exception == true
                                            and (strtolower($ep_obj->getType()) == 'b4'
                                                or (strtolower($ep_obj->getType()) == 'b3' and $pair > ($allowed_breaks - 1)))
                                        ) {
                                            Debug::text('Adding Exception! ' . $ep_obj->getType(), __FILE__, __LINE__, __METHOD__, 10);

                                            if (isset($time_stamp_arr['punch_id']) and strtolower($ep_obj->getType()) == 'b3') {
                                                $punch_id = $time_stamp_arr['punch_id'];
                                            } else {
                                                $punch_id = false;
                                            }

                                            $current_exceptions[] = array(
                                                'user_id' => $user_id,
                                                'date_stamp' => $date_stamp,
                                                'exception_policy_id' => $ep_obj->getId(),
                                                'type_id' => $type_id,
                                                'punch_id' => $punch_id,
                                                'punch_control_id' => false,
                                            );
                                            unset($punch_id);
                                        } else {
                                            Debug::text('Not Adding Exception!', __FILE__, __LINE__, __METHOD__, 10);
                                        }
                                    }
                                }
                            }
                        }
                        break;
                    case 'b5': //No Break
                        if (is_array($plf) and count($plf) > 0) {
                            //If they are scheduled or not, we can check for a break policy and base our
                            //decision off that. We don't want a No Break exception on a 3hr short shift though.
                            //Also ignore this exception if the break is auto-deduct.
                            //**Try to assign this exception to a specific punch control id, so we can do searches based on punch branch.

                            $daily_total_time = 0;
                            $udtlf = $this->filterUserDateTotalDataByObjectTypeIDs($date_stamp, $date_stamp, array(10)); //Worked time only.
                            if (is_array($udtlf) and count($udtlf) > 0) {
                                foreach ($udtlf as $udt_obj) {
                                    $daily_total_time += $udt_obj->getTotalTime();
                                    $punch_control_total_time[$udt_obj->getPunchControlID()] = $udt_obj->getTotalTime();
                                }
                            }
                            unset($udtlf, $udt_obj);
                            Debug::text(' Daily Total Time: ' . $daily_total_time . ' User ID: ' . $user_id, __FILE__, __LINE__, __METHOD__, 10);

                            //Find break policy
                            //Use scheduled break policy first.
                            $break_policy_obj = null;

                            //Enable $always_return_at_least_one=TRUE so no matter what at least one break policy is returned if it exists.
                            //This allows us to *not* trigger this exception when the user works less than the break policy trigger time.
                            $break_time_policies = $this->filterBreakTimePolicy($date_stamp, $daily_total_time, array(15, 20), true); //Exclude auto-deduct break policies.
                            if (is_array($break_time_policies) and count($break_time_policies) > 0) {
                                reset($break_time_policies);
                                $break_policy_obj = $break_time_policies[key($break_time_policies)]; //Get first
                            } elseif (is_array($break_time_policies) and count($break_time_policies) == 0) {
                                $break_policy_obj = null; //Schedule defined, but no break policy applies.
                            } else {
                                //There is no  break policy or schedule policy with a break policy assigned to it
                                //With out this we could still apply No break exceptions, but they will happen even on
                                //a 2minute shift.
                                Debug::text('No Break policy, applying No break exception.', __FILE__, __LINE__, __METHOD__, 10);
                                $break_policy_obj = true;
                            }
                            unset($break_time_policies);

                            if (is_object($break_policy_obj) or $break_policy_obj === true) {
                                $punch_control_id = false;

                                Debug::text('Day Total Time: ' . $daily_total_time, __FILE__, __LINE__, __METHOD__, 10);
                                //Debug::Arr($punch_control_total_time, 'Punch Control Total Time: ', __FILE__, __LINE__, __METHOD__, 10);

                                if ($daily_total_time > 0 and ($break_policy_obj === true or $daily_total_time > $break_policy_obj->getTriggerTime())) {
                                    //Check for break punch.
                                    $break_punch = false;
                                    $tmp_punch_total_time = 0;
                                    $tmp_punch_control_ids = array();
                                    foreach ($plf as $p_obj) {
                                        if ($p_obj->getType() == 30) { //30 = Break
                                            Debug::text('Found break Punch: ' . $p_obj->getTimeStamp(), __FILE__, __LINE__, __METHOD__, 10);
                                            $break_punch = true;
                                            break;
                                        }

                                        if (isset($punch_control_total_time[$p_obj->getPunchControlID()]) and !isset($tmp_punch_control_ids[$p_obj->getPunchControlID()])) {
                                            $tmp_punch_total_time += $punch_control_total_time[$p_obj->getPunchControlID()];
                                            if ($punch_control_id === false and ($break_policy_obj === true or $tmp_punch_total_time > $break_policy_obj->getTriggerTime())) {
                                                Debug::text('Found punch control for exception: ' . $p_obj->getPunchControlID(), __FILE__, __LINE__, __METHOD__, 10);
                                                $punch_control_id = $p_obj->getPunchControlID();
                                                //Don't break the loop here, as we have to continue on and check for other breaks.
                                            }
                                        }
                                        $tmp_punch_control_ids[$p_obj->getPunchControlID()] = true;
                                    }
                                    unset($tmp_punch_total_time, $tmp_punch_control_ids);

                                    //If the last punch is before the premature delay, make this a mature exception instead.
                                    //However if the employee has punched out for the end of their shift (Normal Out), then we don't need to mark it as pre-mature anymore.
                                    //  - Also have to take into account detecting lunch/break punches by punch time, as they appear as a Normal Out punch first
                                    //    then later get changed to Lunch Out and Lunch In punches once the employee returns. So we can't trigger this exception too soon in those cases.
                                    if ($type_id == 5
                                        and (
                                            (
                                                $p_obj->getType() == 10 and $p_obj->getStatus() == 20 and $p_obj->getTransfer() == false
                                                and
                                                ($break_policy_obj === true
                                                    or (is_object($break_policy_obj)
                                                        and (
                                                            $break_policy_obj->getAutoDetectType() == 10 //Time Window policy detection.
                                                            or
                                                            ($break_policy_obj->getAutoDetectType() == 20 and $p_obj->getTimeStamp() < ($current_epoch - $break_policy_obj->getMaximumPunchTime()))) //Punch Time policy detection.
                                                    )
                                                )
                                            )
                                            or
                                            ($p_obj->getTimeStamp() < ($current_epoch - $premature_delay))
                                        )
                                    ) {
                                        $type_id = 50;
                                    }

                                    if ($break_punch == false) {
                                        Debug::text('Triggering No Break exception!', __FILE__, __LINE__, __METHOD__, 10);
                                        $current_exceptions[] = array(
                                            'user_id' => $user_id,
                                            'date_stamp' => $date_stamp,
                                            'exception_policy_id' => $ep_obj->getId(),
                                            'type_id' => $type_id,
                                            'punch_id' => false,
                                            'punch_control_id' => $punch_control_id,
                                        );
                                    }
                                }
                            }
                            unset($break_time_policies, $break_policy_obj, $tmp_punch_control_ids, $udtlf);
                        }
                        break;
                    case 'v1': //TimeSheet Not Verified
                        //Get pay period schedule data, determine if timesheet verification is even enabled.
                        if (is_object($this->pay_period_obj)
                            and is_object($this->pay_period_schedule_obj)
                            and $this->pay_period_schedule_obj->getTimeSheetVerifyType() > 10
                        ) {
                            Debug::text('Verification enabled... Window Start: ' . TTDate::getDate('DATE+TIME', $this->pay_period_obj->getTimeSheetVerifyWindowStartDate()) . ' Grace Time: ' . $ep_obj->getGrace(), __FILE__, __LINE__, __METHOD__, 10);

                            //*Only* trigger this exception on the last day of the pay period, because when the pay period is verified it has to force the last day to be recalculated.
                            //Ignore timesheets without any time, (worked and absence). Or we could use the Watch Window to specify the minimum time required on
                            //a timesheet to trigger this instead?
                            //Make sure we are after the timesheet window start date + the grace period.
                            if ($this->pay_period_obj->getStatus() != 50
                                and $current_epoch >= ($this->pay_period_obj->getTimeSheetVerifyWindowStartDate() + $ep_obj->getGrace())
                                and TTDate::getBeginDayEpoch($date_stamp) == TTDate::getBeginDayEpoch($this->pay_period_obj->getEndDate())
                            ) {

                                //Get pay period total time, include worked and paid absence time.
                                $udtlf = TTnew('UserDateTotalListFactory');
                                $total_time = $udtlf->getTimeSumByUserIDAndPayPeriodId($user_id, $this->pay_period_obj->getID());
                                if ($total_time > 0) {
                                    //Check to see if pay period has been verified or not yet.
                                    $pptsvlf = TTnew('PayPeriodTimeSheetVerifyListFactory');
                                    $pptsvlf->getByPayPeriodIdAndUserId($this->pay_period_obj->getId(), $user_id);

                                    $pay_period_verified = false;
                                    if ($pptsvlf->getRecordCount() > 0) {
                                        $pay_period_verified = $pptsvlf->getCurrent()->getAuthorized();
                                    }

                                    if ($pay_period_verified == false) {
                                        //Always allow for emailing this exception because it can be triggered after a punch is modified and
                                        //any supervisor would need to be notified to verify the timesheet again.
                                        $current_exceptions[] = array(
                                            'user_id' => $user_id,
                                            'date_stamp' => $date_stamp,
                                            'exception_policy_id' => $ep_obj->getId(),
                                            'type_id' => $type_id,
                                            'punch_id' => false,
                                            'punch_control_id' => false,
                                            'enable_email_notification' => true,
                                        );
                                    } else {
                                        Debug::text('TimeSheet has already been authorized!', __FILE__, __LINE__, __METHOD__, 10);
                                    }
                                } else {
                                    Debug::text('Timesheet does not have any worked or paid absence time...', __FILE__, __LINE__, __METHOD__, 10);
                                }
                                unset($udtlf, $total_time);
                            } else {
                                Debug::text('Not within timesheet verification window, or not after grace time.', __FILE__, __LINE__, __METHOD__, 10);
                            }
                        } else {
                            Debug::text('No Pay Period Schedule or TimeSheet Verificiation disabled...', __FILE__, __LINE__, __METHOD__, 10);
                        }
                        break;
                    case 'j1': //Not Allowed on Job
                        break;
                    case 'j2': //Not Allowed on Task
                        break;
                    case 'j3': //Job already completed
                        break;
                    case 'j4': //No Job or Task
                        break;
                    default:
                        Debug::text('BAD, should never get here: ' . $ep_obj->getType(), __FILE__, __LINE__, __METHOD__, 10);
                        break;
                }
            }

            $exceptions = $ep_obj->diffExistingAndCurrentExceptions($existing_exceptions, $current_exceptions);
            if (is_array($exceptions)) {
                if (isset($exceptions['create_exceptions']) and is_array($exceptions['create_exceptions']) and count($exceptions['create_exceptions']) > 0) {
                    Debug::text('Creating new exceptions... Total: ' . count($exceptions['create_exceptions']), __FILE__, __LINE__, __METHOD__, 10);
                    foreach ($exceptions['create_exceptions'] as $tmp_exception) {
                        $ef = TTnew('ExceptionFactory');
                        $ef->setUser($tmp_exception['user_id']);
                        $ef->setDateStamp($tmp_exception['date_stamp']);
                        $ef->setExceptionPolicyID($tmp_exception['exception_policy_id']);
                        $ef->setType($tmp_exception['type_id']);
                        if (isset($tmp_exception['punch_control_id']) and $tmp_exception['punch_control_id'] != '') {
                            $ef->setPunchControlId($tmp_exception['punch_control_id']);
                        }
                        if (isset($tmp_exception['punch_id']) and $tmp_exception['punch_id'] != '') {
                            $ef->setPunchId($tmp_exception['punch_id']);
                        }
                        $ef->setEnableDemerits(true);
                        if ($ef->isValid()) {
                            $ef->Save(false); //Save exception prior to emailing it, otherwise we can't save audit logs.
                            if ($enable_premature_exceptions == true or (isset($tmp_exception['enable_email_notification']) and $tmp_exception['enable_email_notification'] == true)) {
                                $eplf = TTnew('ExceptionPolicyListFactory');
                                $eplf->getById($tmp_exception['exception_policy_id']);
                                if ($eplf->getRecordCount() == 1) {
                                    $ep_obj = $eplf->getCurrent();
                                    $ef->emailException($this->getUserObject(), $date_stamp, (isset($tmp_exception['punch_obj'])) ? $tmp_exception['punch_obj'] : null, (isset($tmp_exception['schedule_obj'])) ? $tmp_exception['schedule_obj'] : null, $ep_obj);
                                }
                            } else {
                                Debug::text('Not emailing new exception: User ID: ' . $tmp_exception['user_id'] . ' Date Stamp: ' . $tmp_exception['date_stamp'] . ' Type ID: ' . $tmp_exception['type_id'] . ' Enable PreMature: ' . (int)$enable_premature_exceptions, __FILE__, __LINE__, __METHOD__, 10);
                            }
                        }
                        unset($ef);
                    }
                }

                if (isset($exceptions['delete_exceptions']) and is_array($exceptions['delete_exceptions']) and count($exceptions['delete_exceptions']) > 0) {
                    $ef = TTnew('ExceptionFactory');
                    $ef->bulkDelete($exceptions['delete_exceptions']);
                }
            }
        } elseif (is_array($existing_exceptions) and count($existing_exceptions) > 0) { //No exception policy, so delete all existing exceptions that may exist.
            Debug::text('Deleting all existing exceptions...', __FILE__, __LINE__, __METHOD__, 10);
            $ef = TTnew('ExceptionFactory');
            $ef->bulkDelete(array_keys($existing_exceptions));
        }

        Debug::text('No exception policies to calculate...', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function Save()
    {
        if (!is_object($this->getUserObject())) { //If the user object isn't set properly, Save() is often still called, so make sure we don't cause a PHP error.
            Debug::Arr($this->getUserObject(), 'Invalid UserObject: ', __FILE__, __LINE__, __METHOD__, 10);
            return false;
        }

        Debug::text('Saving data...', __FILE__, __LINE__, __METHOD__, 10);
        //return $this->insertCompactUserDateTotal( $this->compactOutstandingUserDateTotalObjects() );
        $this->removeRedundantUserDateTotalObjects();

        $this->insertUserDateTotal($this->user_date_total);

        if ($this->getFlag('meal') == true or $this->getFlag('undertime_absence') == true or $this->getFlag('break') == true or $this->getFlag('regular') == true or $this->getFlag('overtime') == true or $this->getFlag('premium') == true or $this->getFlag('accrual') == true or $this->getFlag('holiday') == true or $this->getFlag('schedule_absence') == true) {
            //This needs to reference inserted UDT rows, so it must go last.
            $this->calculateAccrualPolicy();
        }

        $this->getUserObject()->CommitTransaction();
        $this->getUserObject()->setTransactionMode(); //Back to default isolation level.

        $this->revertTimeZone(); //Revert timezone back to the original.

        return true;
    }

    public function removeRedundantUserDateTotalObjects()
    {
        if (is_array($this->user_date_total)) {
            foreach ($this->user_date_total as $key => $udt_obj) {
                if ($key < 0 and $udt_obj->getTotalTime() < 0) { //Found negative time record.
                    foreach ($this->user_date_total as $tmp_key => $tmp_udt_obj) {
                        if (($udt_obj->getTotalTime() + $tmp_udt_obj->getTotalTime()) == 0
                            and $udt_obj->getDateStamp() == $tmp_udt_obj->getDateStamp()
                            and $udt_obj->getObjectType() == $tmp_udt_obj->getObjectType()
                            and $udt_obj->getPayCode() == $tmp_udt_obj->getPayCode()
                            and ($udt_obj->getTotalTimeAmount() + $tmp_udt_obj->getTotalTimeAmount()) == 0
                        ) {
                            Debug::Text('Removing redundant UDT object keys: 1: ' . $key . ' 2: ' . $tmp_key . ' Object Type: ' . $udt_obj->getObjectType() . ' Pay Code: ' . $udt_obj->getPayCode() . ' Total Time: ' . $udt_obj->getTotalTime(), __FILE__, __LINE__, __METHOD__, 10);
                            unset($this->user_date_total[$key], $this->user_date_total[$tmp_key]);
                            continue 2;
                        }
                    }
                }
            }

            return true;
        }

        Debug::Text('No data to process...', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function insertUserDateTotal($user_date_total_records)
    {
        if (is_array($user_date_total_records) and count($user_date_total_records) > 0) {
            //Debug::Arr($user_date_total_records, 'Inserting UserDateTotal entries...', __FILE__, __LINE__, __METHOD__, 10);

            if ($this->delete_system_total_time_already_run == false) {
                Debug::text('System total time was not deleted before trying to insert it again... This is either an error or the pay period is locked.', __FILE__, __LINE__, __METHOD__, 10);
                return false;
            }

            $this->getUserObject()->StartTransaction();
            $inserted_records = 0;
            foreach ($user_date_total_records as $key => $udt_obj) {
                //Insert new rows as long as total_time != 0.
                //  We want to have total time rows even if they are zero.
                //  However rows with total_time=0 account for about 40% of all rows, so removing them will save a lot of space.
                //  We also need to re-save UDT rows that have override=TRUE, so we can handle overtime exclusivity and such.
                //     This allows the user to override Regular Time to 10hrs, and have 2hrs still go into OT.
                //  Don't resave override absence entries, this caused a bug where UDT rows would switch to different dates when calcExceptions was run.
                //  *If a note is specified, always insert the record, this way we can give the user notes as to why they arent eligible for holiday time for example.
                //  Since we implemented getPartialUserDateTotalObject() to split UDT records, it might split records of ObjectTypeID=10, which causes the KEY to be < 0 introducing a bug where this was inserting it into the DB and corrupting the data. Added check for this: $udt_obj->getObjectType() != 10 (5 must still be allowed)
                if (($key < 0 and $udt_obj->getObjectType() != 10 and ($udt_obj->getTotalTime() != 0 or $udt_obj->getTotalTimeAmount() != 0 or $udt_obj->getNote() != ''))
                    or ($key > 0 and $udt_obj->getObjectType() != 50 and $udt_obj->getOverride() == true)
                ) {
                    //Debug::text('    Currency ID: '. $this->getUserObject()->getCurrency() .' Rate: '. $this->filterCurrencyRate( $udt_obj->getDateStamp() )->getReverseConversionRate(), __FILE__, __LINE__, __METHOD__, 10);
                    //Handle currency rates here, just before the record is saved.
                    $udt_obj->setCurrency((int)$this->getUserObject()->getCurrency(), true); //Disable automatic rate lookup.
                    $udt_obj->setCurrencyRate($this->filterCurrencyRate($udt_obj->getDateStamp())->getReverseConversionRate());

                    //Debug::Arr($udt_obj->data, 'Inserting UserDateTotal entry... Key: '. $key, __FILE__, __LINE__, __METHOD__, 10);
                    if ($udt_obj->isValid()) {
                        $udt_obj->Save(false);
                        $inserted_records++;

                        //Remove pre-saved object and replace it with saved object and proper ID.
                        unset($this->user_date_total[$key]);
                        $this->user_date_total[$udt_obj->getID()] = $udt_obj;
                        $this->new_user_date_total_ids[] = $udt_obj->getID();

                        //Remap keys for AccrualTime Exclusivity map, since accruals are handled after these records are inserted.
                        if (isset($this->accrual_time_exclusivity_map[$key])) {
                            $this->accrual_time_exclusivity_map[$udt_obj->getID()] = $this->accrual_time_exclusivity_map[$key];
                            unset($this->accrual_time_exclusivity_map[$key]);
                        }

                        if ($udt_obj->getObjectType() == 5) {
                            $this->new_system_user_date_total_id[TTDate::getMiddleDayEpoch($udt_obj->getDateStamp())] = $udt_obj->getId();
                        }
                    } else {
                        Debug::text('ERROR: Invalid UserDateTotal Entry!', __FILE__, __LINE__, __METHOD__, 10);
                    }
                }
                //else {
                //	Debug::text('Skipping UserDateTotal entry... Key: '. $key, __FILE__, __LINE__, __METHOD__, 10);
                //}
            }
            Debug::text('UserDateTotal records inserted: ' . $inserted_records, __FILE__, __LINE__, __METHOD__, 10);

            //$this->getUserObject()->FailTransaction();
            $this->getUserObject()->CommitTransaction();

            return true;
        }

        Debug::text('No UserDateTotal entries to insert...', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function filterCurrencyRate($date_stamp)
    {
        //Punches can happen before the currency rate is specified (which normally happens around noon PST/EST), especially in other timezones, so always use the currency rate from the day before.
        $currency_date_stamp = TTDate::getMiddleDayEpoch((TTDate::getMiddleDayEpoch($date_stamp) - 86400)); //Optimization - Move outside loop.

        $crlf = $this->currency_rates;
        if (is_array($crlf) and count($crlf) > 0) {
            foreach ($crlf as $cr_obj) {
                if (TTDate::getMiddleDayEpoch($cr_obj->getDateStamp()) == $currency_date_stamp) {
                    //Debug::text('Currency Rate DOES match filter... ID: '. $cr_obj->getID(), __FILE__, __LINE__, __METHOD__, 10);
                    return $cr_obj;
                }
                //else {
                //Debug::text('Currency rate does not match filter... ID: '. $cr_obj->getID(), __FILE__, __LINE__, __METHOD__, 10);
                //}
            }
        }

        Debug::text('No currency rate rows match filter...', __FILE__, __LINE__, __METHOD__, 10);

        $crf = TTnew('CurrencyRateFactory');
        $crf->setCurrency($this->getUserObject()->getCurrency());
        $crf->setDateStamp($currency_date_stamp);
        $crf->setConversionRate(1);

        return $crf;
    }

    public function calculateAccrualPolicy()
    {
        $calculated_date_range = $this->getCalculatedDateRange();
        if (is_array($calculated_date_range)) {
            $first_date_stamp = TTDate::getMiddleDayEpoch($calculated_date_range['start_date']); //Optimization - Move outside loop.
            $last_date_stamp = TTDate::getMiddleDayEpoch($calculated_date_range['end_date']); //Optimization - Move outside loop.
            Debug::Text('  First Date Stamp: ' . TTDate::getDate('DATE', $first_date_stamp) . ' Last Date Stamp: ' . TTDate::getDate('DATE', $last_date_stamp), __FILE__, __LINE__, __METHOD__, 10);
        }

        $alf = TTnew('AccrualListFactory');
        $aplf = $this->accrual_policy;
        if (is_array($aplf) and count($aplf) > 0) {
            foreach ($aplf as $ap_obj) {
                Debug::text('Hour-Based Accrual Policy: ' . $ap_obj->getName(), __FILE__, __LINE__, __METHOD__, 10);

                if (!isset($this->contributing_shift_policy[$ap_obj->getContributingShiftPolicy()])) {
                    Debug::text('No contributing shift policy defined for accrual policy, skipping...', __FILE__, __LINE__, __METHOD__, 10);
                    continue;
                }

                if (isset($first_date_stamp) and isset($last_date_stamp)) {
                    foreach ($this->dates['calculated'] as $date_stamp => $tmp) {
                        $date_stamp = TTDate::getMiddleDayEpoch($date_stamp); //Optimization - Move outside loop.

                        if ($ap_obj->getMinimumEmployedDays() == 0
                            or TTDate::getDays(($date_stamp - $ap_obj->getModifiedHireDate($this->getUserObject()))) >= $ap_obj->getMinimumEmployedDays()
                        ) {
                            Debug::Text('  User has been employed long enough.', __FILE__, __LINE__, __METHOD__, 10);

                            $inception_total_time = false;
                            if ($ap_obj->isHourBasedLengthOfService() == true) {
                                //For hour based length of services, we need to get all time that matches contributing shift policy back to their hire date.
                                if ($inception_total_time == false) { //Try to only call to the DB once for the entire range.
                                    $this->getUserDateTotalData($ap_obj->getModifiedHireDate($this->getUserObject()), $last_date_stamp);

                                    //As an optimization, calculate inception total time from the hire date to the first date we calculated.
                                    //Then we can just add time from the first date to the current date being calcluated.
                                    $base_inception_total_time = $this->getSumUserDateTotalData($this->filterUserDateTotalDataByContributingShiftPolicy($ap_obj->getModifiedHireDate($this->getUserObject()), ($first_date_stamp - 86400), $this->contributing_shift_policy[$ap_obj->getContributingShiftPolicy()]));
                                }
                                $additional_inception_total_time = $this->getSumUserDateTotalData($this->filterUserDateTotalDataByContributingShiftPolicy($first_date_stamp, $date_stamp, $this->contributing_shift_policy[$ap_obj->getContributingShiftPolicy()]));
                                $inception_total_time = ($base_inception_total_time + $additional_inception_total_time);
                                Debug::Text('  Inception Total Time: ' . $inception_total_time . ' Base: ' . $base_inception_total_time . ' Additional: ' . $additional_inception_total_time, __FILE__, __LINE__, __METHOD__, 10);
                            }

                            $user_date_total_rows = $this->filterUserDateTotalDataByContributingShiftPolicy($date_stamp, $date_stamp, $this->contributing_shift_policy[$ap_obj->getContributingShiftPolicy()]);
                            if (is_array($user_date_total_rows) and count($user_date_total_rows) > 0) {
                                foreach ($user_date_total_rows as $udt_key => $udt_obj) {
                                    //Since object_type_id=50 (absence taken) creates object_type_id=25 records, ignore hour based accruals on object_type_id=50.
                                    //This allows for cases where absence time creates overtime and they may want accruals calculated on OT.
                                    if ($udt_obj->getObjectType() == 50) {
                                        continue;
                                    }
                                    //Need to check milestone after every UDT row so we can detect switching milestones quickly.
                                    //FIXME: Handle switching milestones at exactly the right second, even mid-UDT row.
                                    $milestone_obj = $ap_obj->getActiveMilestoneObject($this->getUserObject(), $date_stamp, $inception_total_time);

                                    //If Maximum time is set to 0, make that unlimited.
                                    if (is_object($milestone_obj)) {
                                        //Since we compact accrual records now, we need to calculate the balance that is stored in memory and not yet committed to DB, and add it to the DB balance.
                                        if (isset($accrual_compact_arr[(int)$ap_obj->getAccrualPolicyAccount()][(int)$ap_obj->getId()])) {
                                            $tmp_accrual_balance = array_sum($accrual_compact_arr[(int)$ap_obj->getAccrualPolicyAccount()][(int)$ap_obj->getId()]);
                                        } else {
                                            $tmp_accrual_balance = 0;
                                        }
                                        $accrual_balance = ($tmp_accrual_balance + $ap_obj->getCurrentAccrualBalance($this->getUserObject()->getId(), $ap_obj->getAccrualPolicyAccount()));

                                        if ($milestone_obj->getAnnualMaximumTime() > 0) {
                                            $annual_accrued_amount = ($tmp_accrual_balance + $alf->getSumByUserIdAndAccrualPolicyAccountAndTypeAndStartDateAndEndDate($this->getUserObject()->getId(), $ap_obj->getAccrualPolicyAccount(), array(76), $ap_obj->getCurrentMilestoneRolloverDate($date_stamp, $this->getUserObject(), true), $date_stamp));
                                        } else {
                                            $annual_accrued_amount = false;
                                        }
                                        Debug::Text('	Current Balance: ' . $accrual_balance . ' Accrual Policy: ' . $ap_obj->getName() . '(' . $ap_obj->getId() . ') Annual Accrued Amount: ' . $annual_accrued_amount, __FILE__, __LINE__, __METHOD__, 10);

                                        if (($milestone_obj->getMaximumTime() == 0 and $milestone_obj->getAnnualMaximumTime() == 0)
                                            or ($accrual_balance < $milestone_obj->getMaximumTime()
                                                and ($annual_accrued_amount === false or $annual_accrued_amount < $milestone_obj->getAnnualMaximumTime()))
                                        ) {
                                            $accrual_amount = $ap_obj->calcAccrualAmount($milestone_obj, $udt_obj->getTotalTime(), 0);

                                            if ($accrual_amount > 0) {
                                                //If Annual Maximum time is set to 0, make that unlimited.
                                                if ($milestone_obj->getAnnualMaximumTime() > 0 and ($annual_accrued_amount + $accrual_amount) > $milestone_obj->getAnnualMaximumTime()) {
                                                    $accrual_amount = bcsub($milestone_obj->getAnnualMaximumTime(), $annual_accrued_amount, 4);
                                                }

                                                //If Maximum time is set to 0, make that unlimited.
                                                if ($milestone_obj->getMaximumTime() > 0 and ($accrual_balance + $accrual_amount) > $milestone_obj->getMaximumTime()) {
                                                    $accrual_amount = bcsub($milestone_obj->getMaximumTime(), $accrual_balance, 4);
                                                }

                                                //Debug::Text('	UDT Key: '. $udt_key .' Object Type ID: '. $udt_obj->getObjectType(), __FILE__, __LINE__, __METHOD__, 10);
                                                Debug::Text('	Min/Max Adjusted Accrual Amount: ' . $accrual_amount . ' Limits: Max Balance: ' . $milestone_obj->getMaximumTime() . ' Annual Max: ' . $milestone_obj->getAnnualMaximumTime(), __FILE__, __LINE__, __METHOD__, 10);

                                                //It would be nice to find a way to compact these accrual records,
                                                //as right now there could be many (hundreds) per day and it makes viewing the accrual balance difficult.
                                                //Not sure if that is really possible though, as we won't be able to link directly to UserDateTotalID's then
                                                //and that will make it impossible to figure out orphaned records.
                                                //Solution is to link to the object_type_id=5 (system total time) record for each day.
                                                //However we need to add these amounts to the current balance so we can stop adding more even after the maximum balance has been reached.
                                                if (isset($accrual_compact_arr[(int)$ap_obj->getAccrualPolicyAccount()][(int)$ap_obj->getId()][$date_stamp])) {
                                                    $accrual_compact_arr[(int)$ap_obj->getAccrualPolicyAccount()][(int)$ap_obj->getId()][$date_stamp] += $accrual_amount;
                                                } else {
                                                    $accrual_compact_arr[(int)$ap_obj->getAccrualPolicyAccount()][(int)$ap_obj->getId()][$date_stamp] = $accrual_amount;
                                                }
                                                unset($accrual_amount, $accrual_balance, $new_accrual_balance);
                                            } else {
                                                Debug::Text('	Accrual Amount is 0...', __FILE__, __LINE__, __METHOD__, 10);
                                            }
                                        } else {
                                            Debug::Text('	Accrual Balance or Annual Maximum is outside Milestone Range. Or no milestone found. Skipping...', __FILE__, __LINE__, __METHOD__, 10);
                                        }
                                    } else {
                                        Debug::Text('	No milestone found. Skipping...', __FILE__, __LINE__, __METHOD__, 10);
                                    }
                                }
                            }
                        } else {
                            Debug::Text('  User has only been employed: ' . TTDate::getDays(($date_stamp - $ap_obj->getModifiedHireDate($this->getUserObject()))) . ' Days, not enough.', __FILE__, __LINE__, __METHOD__, 10);
                        }

                        //Handled by deleteSystemTotalTime() instead, in case there are no accrual policies assigned anymore.
                        //AccrualFactory::deleteOrphans( $this->getUserObject()->getId(), $date_stamp );
                    }
                }
            }

            //Insert compacted Accrual records.
            if (isset($accrual_compact_arr) and is_array($accrual_compact_arr) and count($accrual_compact_arr) > 0) {
                foreach ($accrual_compact_arr as $accrual_policy_account_id => $data1) {
                    foreach ($data1 as $accrual_policy_id => $data2) {
                        foreach ($data2 as $date_stamp => $total_time) {
                            $af = TTnew('AccrualFactory');
                            $af->setUser($this->getUserObject()->getId());
                            $af->setType(76); //Hour-Based Accrual Policy
                            $af->setAccrualPolicyAccount($accrual_policy_account_id);
                            $af->setAccrualPolicy($accrual_policy_id);
                            $af->setUserDateTotalID($this->new_system_user_date_total_id[TTDate::getMiddleDayEpoch($date_stamp)]); //Link hour based accruals to just the system total time for each day.
                            $af->setAmount($total_time);
                            $af->setTimeStamp($date_stamp);
                            $af->setEnableCalcBalance(true);
                            if ($af->isValid()) {
                                $insert_id = $af->Save();
                                Debug::Text('	Adding Accrual Record, ID: ' . $insert_id, __FILE__, __LINE__, __METHOD__, 10);
                            }
                        }
                    }
                }
            }
            unset($accrual_compact_arr, $data1, $data2, $accrual_policy_account_id, $accrual_policy_id, $date_stamp, $total_time);
        } else {
            Debug::text('No hour-based accrual policies to calculate...', __FILE__, __LINE__, __METHOD__, 10);
        }

        //Calculate non-hour based accrual policies, those attached to pay formulas.
        if (isset($first_date_stamp) and isset($last_date_stamp)) {
            //Merge object_type_id=50 rows with new_user_date_total_ids so they can both be handled together.
            //See below comments regarding object_type_id=50 records and how they need to be handled in a special manner.
            $new_and_absent_user_date_total_ids = array_unique(array_merge((array)$this->new_user_date_total_ids, array_keys((array)$this->filterUserDateTotalDataByObjectTypeIDs($first_date_stamp, $last_date_stamp, array(50)))));

            if (isset($new_and_absent_user_date_total_ids) and count($new_and_absent_user_date_total_ids) > 0) {
                foreach ($new_and_absent_user_date_total_ids as $new_user_date_total_id) {
                    if (isset($this->user_date_total[$new_user_date_total_id])) {
                        $udt_obj = $this->user_date_total[$new_user_date_total_id];

                        //Skip System records as they never have accruals calculated.
                        if ($udt_obj->getObjectType() != 5) {
                            //Debug::text('aUDT ID: '. $udt_obj->getID() .' Object Type ID: '. $udt_obj->getObjectType() .' Date: '. TTDate::getDate('DATE', $udt_obj->getDateStamp() ), __FILE__, __LINE__, __METHOD__, 10);
                            $tmp_user_date_total[$new_user_date_total_id] = $udt_obj;
                        }
                    }
                }
                unset($new_and_absent_user_date_total_ids, $new_user_date_total_id, $udt_obj);

                if (isset($tmp_user_date_total)) {
                    //Debug::Arr($this->accrual_time_exclusivity_map, 'Accrual Time Exclusive Records: ', __FILE__, __LINE__, __METHOD__, 10);

                    //Sort by ObjectTypeID so object_type_id=50 come before any other policy,
                    // that way we can handle accruals on them first and not duplicate accruals on other policies that are triggered by the result.
                    $tmp_user_date_total = $this->sortUserDateTotalData($tmp_user_date_total, 'sortUserDateTotalDataByObjectTypeDescAndID');
                    foreach ($tmp_user_date_total as $udt_key => $udt_obj) {
                        //Debug::text('bKey: '. $udt_key .' UDT ID: '. $udt_obj->getID() .' Object Type ID: '. $udt_obj->getObjectType() .' Date: '. TTDate::getDate('DATE', $udt_obj->getDateStamp() ), __FILE__, __LINE__, __METHOD__, 10);

                        //We have to skip absence taken so when the user enters in an absence schedule, it will create a object_type_id=50 record first
                        //then a object_type_id=25 record, both are considered new and would duplicate the accrual entry otherwise.
                        //The above wouldn't happen if you just entered in absence time directly on the timesheet, as the object_type_id=50 record
                        //is already created by the user and not by CalculatePolicy, so it would naturally be skipped in that case.
                        // **If we don't calculate accruals on object_type_id=50, then when absence time rolls into OT, it won't reduce their accrual by
                        //   the full amount, just the non-OT amount. So instead we skip object_type_id=25 records and always include object_type_id=50 records
                        //   in the $new_and_absent_user_date_total_ids array, so they are calculated everytime.
                        //
                        // **We need to consider both object_type_id=25 AND 50, since undertime absence policies will just create object_type_id=25 records and not object_type_id=50 records.
                        //   so they wouldn't affect linked accruals in that case.
                        //  **When considering object_type_id=25, there can be multiple punch pairs (ie: transfer punches) all using the same source_object_id,pay_code_id, etc...
                        //    which are all going into overtime and thereby into a time bank too.
                        //      We handle this by using accrual_time_exclusivity_map, so when Accruals are calculated from an initial object_type_id=50 record,
                        //      it excludes them and any other UDT records based on them from accruing later on.
                        //
                        // **IF THIS CHANGES, YOU MUST UPDATE AccrualListFactory->getOrphansByUserIdAndDate() so orphan detection is handled properly too.

                        //Use SourceObject by default if its defined, as an Absence Policy may have an override Pay Formula which accrues instead of the pay code itself.
                        //If SourceObject is not defined, then fall back to the PayCode pay formula. For cases where the user adds an override UDT record directly.
                        $policy_object = false;
                        if ($udt_obj->getSourceObject() > 0) {
                            $policy_object = $udt_obj->getSourceObjectObject();
                        } else {
                            $policy_object = $udt_obj->getPayCodeObject();
                        }
                        $pay_formula_policy_obj = $this->getPayFormulaPolicyObjectByPolicyObject($policy_object);
                        if ($this->isPayFormulaAccruing($pay_formula_policy_obj) == true) {
                            if (isset($this->accrual_time_exclusivity_map[$udt_key])) {
                                Debug::text('  WARNING: Accrual already calculated on this ObjecType->PayCode->SourceObject combination, skipping...', __FILE__, __LINE__, __METHOD__, 10);
                            } else {
                                $af = TTnew('AccrualFactory');
                                $af->setUser($this->getUserObject()->getID());
                                $af->setAccrualPolicyAccount($pay_formula_policy_obj->getAccrualPolicyAccount());
                                $af->setTimeStamp($udt_obj->getDateStamp());
                                $af->setUserDateTotalID($udt_obj->getID());

                                $accrual_amount = bcmul($udt_obj->getTotalTime(), $pay_formula_policy_obj->getAccrualRate());
                                if ($accrual_amount > 0) {
                                    $af->setType(10); //Banked
                                } else {
                                    $af->setType(20); //Used
                                }
                                $af->setAmount($accrual_amount);
                                $af->setEnableCalcBalance(true);

                                Debug::text('Adding Accrual Entry for: ' . $accrual_amount . ' Based on UDT key: ' . $udt_obj->getId() . ' Pay Code: ' . $udt_obj->getPayCode() . ' Source Object: ' . $udt_obj->getSourceObject(), __FILE__, __LINE__, __METHOD__, 10);
                                if ($af->isValid()) {
                                    $af->Save();
                                }
                            }
                        }
                        // else {
                        //	Debug::text('Pay Formula doesnt have accrual policy or rate specified...', __FILE__, __LINE__, __METHOD__, 10);
                        //}
                        unset($policy_object);
                    }
                    unset($accrual_exclusivity_map, $af);
                }
            } else {
                Debug::text('No non-hour based accrual policies to calculate...', __FILE__, __LINE__, __METHOD__, 10);
            }
        }

        return true;
    }

    //Allow calculating up to one week at a time, as we always recalculate the remaining week anyways.
    //Allow no date_stamp to be passed so we just start from the first pending date instead.

    public function getCalculatedDateRange()
    {
        if (isset($this->dates['calculated']) and count($this->dates['calculated']) > 0) {

            //Always sort calculated dates so they are in chronological order.
            ksort($this->dates['calculated']);

            $retarr = array(
                'start_date' => key(array_slice($this->dates['calculated'], 0, 1, true)),
                'end_date' => key(array_slice($this->dates['calculated'], -1, 1, true)),
            );

            //Debug::Text('  First Date Stamp: '. TTDate::getDate('DATE', $retarr['start_date']) .' Last Date Stamp: '. TTDate::getDate('DATE', $retarr['end_date'] ), __FILE__, __LINE__, __METHOD__, 10);

            return $retarr;
        }

        return false;
    }

    //Keep saving all data in a separate function so we can do in-memory calculations if necessary.

    public function revertTimeZone()
    {
        if (isset($this->original_time_zone) and $this->original_time_zone != '') {
            return TTDate::setTimeZone($this->original_time_zone);
        }

        return false;
    }
}
