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
 * @package Modules\Schedule
 */
class ScheduleFactory extends Factory
{
    protected $table = 'schedule';
    protected $pk_sequence_name = 'schedule_id_seq'; //PK Sequence name

    protected $user_date_obj = null;
    protected $schedule_policy_obj = null;
    protected $absence_policy_obj = null;
    protected $branch_obj = null;
    protected $department_obj = null;
    protected $pay_period_schedule_obj = null;

    public static function addPunchFromScheduleObject($rs_obj)
    {
        //Make sure they are working for Auto-fill to kickin.
        Debug::text('Adding punch from schedule object...', __FILE__, __LINE__, __METHOD__, 10);

        $commit_punch_transaction = false;

        $pf_in = new PunchFactory();

        if ($rs_obj->getUser() > 0) {
            $pf_in->StartTransaction();

            $pf_in->setUser($rs_obj->getUser());
            $pf_in->setType(10); //Normal
            $pf_in->setStatus(10); //In
            $pf_in->setTimeStamp($rs_obj->getStartTime(), true);
            $pf_in->setPunchControlID($pf_in->findPunchControlID());
            $pf_in->setActualTimeStamp($pf_in->getTimeStamp());
            $pf_in->setOriginalTimeStamp($pf_in->getTimeStamp());

            if ($pf_in->isValid()) {
                Debug::text('Punch In: Valid!', __FILE__, __LINE__, __METHOD__, 10);
                $pf_in->setEnableCalcTotalTime(false);
                $pf_in->setEnableCalcSystemTotalTime(false);
                $pf_in->setEnableCalcUserDateTotal(false);
                $pf_in->setEnableCalcException(false);

                $pf_in->Save(false);
            } else {
                Debug::text('Punch In: InValid!', __FILE__, __LINE__, __METHOD__, 10);
            }

            Debug::text('Punch Out: ' . TTDate::getDate('DATE+TIME', $rs_obj->getEndTime()), __FILE__, __LINE__, __METHOD__, 10);
            $pf_out = new PunchFactory();
            $pf_out->setUser($rs_obj->getUser());
            $pf_out->setType(10); //Normal
            $pf_out->setStatus(20); //Out
            $pf_out->setTimeStamp($rs_obj->getEndTime(), true);
            $pf_out->setPunchControlID($pf_in->findPunchControlID()); //Use the In punch object to find the punch_control_id.
            $pf_out->setActualTimeStamp($pf_out->getTimeStamp());
            $pf_out->setOriginalTimeStamp($pf_out->getTimeStamp());

            if ($pf_out->isValid()) {
                Debug::text('Punch Out: Valid!', __FILE__, __LINE__, __METHOD__, 10);
                $pf_out->setEnableCalcTotalTime(true);
                $pf_out->setEnableCalcSystemTotalTime(true);
                $pf_out->setEnableCalcUserDateTotal(true);
                $pf_out->setEnableCalcException(true);

                $pf_out->Save(false);
            } else {
                Debug::text('Punch Out: InValid!', __FILE__, __LINE__, __METHOD__, 10);
            }

            if ($pf_in->isValid() == true or $pf_out->isValid() == true) {
                Debug::text('Punch In and Out succeeded, saving punch control!', __FILE__, __LINE__, __METHOD__, 10);

                $pcf = new PunchControlFactory();
                $pcf->setId($pf_in->getPunchControlID());

                if ($pf_in->isValid() == true) {
                    $pcf->setPunchObject($pf_in);
                } elseif ($pf_out->isValid() == true) {
                    $pcf->setPunchObject($pf_out);
                }

                $pcf->setBranch((int)$rs_obj->getBranch());
                $pcf->setDepartment((int)$rs_obj->getDepartment());
                $pcf->setJob((int)$rs_obj->getJob());
                $pcf->setJobItem((int)$rs_obj->getJobItem());

                $pcf->setEnableStrictJobValidation(true);
                $pcf->setEnableCalcUserDateID(true);
                $pcf->setEnableCalcTotalTime(true);
                $pcf->setEnableCalcSystemTotalTime(true);
                $pcf->setEnableCalcUserDateTotal(true);
                $pcf->setEnableCalcException(true);
                $pcf->setEnablePreMatureException(false); //Disable pre-mature exceptions at this point.

                if ($pcf->isValid()) {
                    $pcf->Save(true, true);

                    $commit_punch_transaction = true;
                }
            } else {
                Debug::text('Punch In and Out failed, not saving punch control!', __FILE__, __LINE__, __METHOD__, 10);
            }

            if ($commit_punch_transaction == true) {
                Debug::text('Committing Punch Transaction!', __FILE__, __LINE__, __METHOD__, 10);
                $pf_in->CommitTransaction();
            } else {
                Debug::text('Rolling Back Punch Transaction!', __FILE__, __LINE__, __METHOD__, 10);
                $pf_in->FailTransaction();
                $pf_in->CommitTransaction();
                return false;
            }

            unset($pf_in, $pf_out, $pcf);
        } else {
            Debug::text('Skipping... User id is invalid.', __FILE__, __LINE__, __METHOD__, 10);
            return false;
        }
        return true;
    }

    public function _getFactoryOptions($name, $parent = null)
    {
        $retval = null;
        switch ($name) {
            case 'type':
                $retval = array(

                    //10  => TTi18n::gettext('OPEN'), //Available to be covered/overridden.
                    //20 => TTi18n::gettext('Manual'),
                    //30 => TTi18n::gettext('Recurring')
                    //90  => TTi18n::gettext('Replaced'), //Replaced by another shift. Set replaced_id

                    //Not displayed on schedules, used to overwrite recurring schedule if we want to change a 8AM-5PM recurring schedule
                    //with a 6PM-11PM schedule? Although this can be done with an absence shift as well...
                    //100 => TTi18n::gettext('Hidden'),
                );
                break;
            case 'status':
                $retval = array(
                    //If user_id = 0 then the schedule is assumed to be open. That way its easy to assign recurring schedules
                    //to user_id=0 for open shifts too.
                    10 => TTi18n::gettext('Working'),
                    20 => TTi18n::gettext('Absent'),
                );
                break;
            case 'columns':
                $retval = array(
                    '-1000-first_name' => TTi18n::gettext('First Name'),
                    '-1002-last_name' => TTi18n::gettext('Last Name'),
                    '-1005-user_status' => TTi18n::gettext('Employee Status'),
                    '-1010-title' => TTi18n::gettext('Title'),
                    '-1039-group' => TTi18n::gettext('Group'),
                    '-1040-default_branch' => TTi18n::gettext('Default Branch'),
                    '-1050-default_department' => TTi18n::gettext('Default Department'),
                    '-1160-branch' => TTi18n::gettext('Branch'),
                    '-1170-department' => TTi18n::gettext('Department'),
                    '-1200-status' => TTi18n::gettext('Status'),
                    '-1210-schedule_policy' => TTi18n::gettext('Schedule Policy'),
                    '-1212-absence_policy' => TTi18n::gettext('Absence Policy'),
                    '-1215-date_stamp' => TTi18n::gettext('Date'),
                    '-1220-start_time' => TTi18n::gettext('Start Time'),
                    '-1230-end_time' => TTi18n::gettext('End Time'),
                    '-1240-total_time' => TTi18n::gettext('Total Time'),
                    '-1250-note' => TTi18n::gettext('Note'),

                    '-2000-created_by' => TTi18n::gettext('Created By'),
                    '-2010-created_date' => TTi18n::gettext('Created Date'),
                    '-2020-updated_by' => TTi18n::gettext('Updated By'),
                    '-2030-updated_date' => TTi18n::gettext('Updated Date'),
                );
                break;
            case 'list_columns':
                $retval = Misc::arrayIntersectByKey($this->getOptions('default_display_columns'), Misc::trimSortPrefix($this->getOptions('columns')));
                break;
            case 'default_display_columns': //Columns that are displayed by default.
                $retval = array(
                    'first_name',
                    'last_name',
                    'status',
                    'date_stamp',
                    'start_time',
                    'end_time',
                    'total_time',
                );
                break;
            case 'unique_columns': //Columns that are unique, and disabled for mass editing.
                $retval = array();
                break;
            case 'linked_columns': //Columns that are linked together, mainly for Mass Edit, if one changes, they all must.
                $retval = array();
                break;
            case 'group_columns': //Columns available for grouping on the schedule.
                $retval = array(
                    'title',
                    'group',
                    'default_branch',
                    'default_department',
                    'branch',
                    'department',
                );
                break;
        }

        return $retval;
    }

    public function _getVariableToFunctionMap($data)
    {
        $variable_function_map = array(
            'id' => 'ID',
            'company_id' => 'Company',
            'user_id' => 'User',
            'date_stamp' => 'DateStamp',
            'pay_period_id' => 'PayPeriod',
            'replaced_id' => 'ReplacedId',

            //'user_id' => FALSE,
            'first_name' => false,
            'last_name' => false,
            'user_status_id' => false,
            'user_status' => false,
            'group_id' => false,
            'group' => false,
            'title_id' => false,
            'title' => false,
            'default_branch_id' => false,
            'default_branch' => false,
            'default_department_id' => false,
            'default_department' => false,

            //'date_stamp' => FALSE,
            'start_date_stamp' => false,
            'status_id' => 'Status',
            'status' => false,
            'start_date' => false,
            'end_date' => false,
            'start_time_stamp' => false,
            'end_time_stamp' => false,
            'start_time' => 'StartTime',
            'end_time' => 'EndTime',
            'schedule_policy_id' => 'SchedulePolicyID',
            'schedule_policy' => false,
            'absence_policy_id' => 'AbsencePolicyID',
            'absence_policy' => false,
            'branch_id' => 'Branch',
            'branch' => false,
            'department_id' => 'Department',
            'department' => false,
            'job_id' => 'Job',
            'job' => false,
            'job_item_id' => 'JobItem',
            'job_item' => false,
            'total_time' => 'TotalTime',

            'other_id1' => 'OtherID1',
            'other_id2' => 'OtherID2',
            'other_id3' => 'OtherID3',
            'other_id4' => 'OtherID4',
            'other_id5' => 'OtherID5',

            'recurring_schedule_template_control_id' => 'RecurringScheduleTemplateControl',

            'note' => 'Note',

            'deleted' => 'Deleted',
        );
        return $variable_function_map;
    }

    public function getAbsencePolicyObject()
    {
        return $this->getGenericObject('AbsencePolicyListFactory', $this->getAbsencePolicyID(), 'absence_policy_obj');
    }

    public function getAbsencePolicyID()
    {
        if (isset($this->data['absence_policy_id'])) {
            return (int)$this->data['absence_policy_id'];
        }

        return false;
    }

    public function getBranchObject()
    {
        return $this->getGenericObject('BranchListFactory', $this->getBranch(), 'branch_obj');
    }

    public function getBranch()
    {
        if (isset($this->data['branch_id'])) {
            return (int)$this->data['branch_id'];
        }

        return false;
    }

    public function getDepartmentObject()
    {
        return $this->getGenericObject('DepartmentListFactory', $this->getDepartment(), 'department_obj');
    }

    public function getDepartment()
    {
        if (isset($this->data['department_id'])) {
            return (int)$this->data['department_id'];
        }

        return false;
    }

    public function setCompany($id)
    {
        $id = trim($id);

        Debug::Text('Company ID: ' . $id, __FILE__, __LINE__, __METHOD__, 10);
        $clf = TTnew('CompanyListFactory');

        if ($this->Validator->isResultSetWithRows('company',
            $clf->getByID($id),
            TTi18n::gettext('Company is invalid')
        )
        ) {
            $this->data['company_id'] = $id;

            return true;
        }

        return false;
    }

    public function setStartTime($epoch)
    {
        $epoch = (int)$epoch;

        if ($this->Validator->isDate('start_time',
            $epoch,
            TTi18n::gettext('Incorrect start time'))
        ) {
            $this->data['start_time'] = $epoch;

            return true;
        }

        return false;
    }

    public function getRecurringScheduleTemplateControl()
    {
        if (isset($this->data['recurring_schedule_template_control_id'])) {
            return (int)$this->data['recurring_schedule_template_control_id'];
        }

        return false;
    }

    public function setRecurringScheduleTemplateControl($id)
    {
        $id = trim($id);

        $rstclf = TTnew('RecurringScheduleTemplateControlListFactory');

        //Need to be able to support user_id=0 for open shifts. But this can cause problems with importing punches with user_id=0.
        if ($id == 0
            or
            $this->Validator->isResultSetWithRows('recurring_schedule_template_control_id',
                $rstclf->getByID($id),
                TTi18n::gettext('Invalid Recurring Schedule Template')
            )
        ) {
            $this->data['recurring_schedule_template_control_id'] = $id;

            return true;
        }

        return false;
    }

    public function getNote()
    {
        if (isset($this->data['note'])) {
            return $this->data['note'];
        }

        return false;
    }

    public function setNote($val)
    {
        $val = trim($val);

        if ($val == ''
            or
            $this->Validator->isLength('note',
                $val,
                TTi18n::gettext('Note is too short or too long'),
                0,
                1024)
        ) {
            $this->data['note'] = $val;

            return true;
        }

        return false;
    }

    public function getOtherID1()
    {
        if (isset($this->data['other_id1'])) {
            return $this->data['other_id1'];
        }

        return false;
    }

    public function setOtherID1($value)
    {
        $value = trim($value);

        if ($value == ''
            or
            $this->Validator->isLength('other_id1',
                $value,
                TTi18n::gettext('Other ID 1 is invalid'),
                1, 255)
        ) {
            $this->data['other_id1'] = $value;

            return true;
        }

        return false;
    }

    public function getOtherID2()
    {
        if (isset($this->data['other_id2'])) {
            return $this->data['other_id2'];
        }

        return false;
    }

    public function setOtherID2($value)
    {
        $value = trim($value);

        if ($value == ''
            or
            $this->Validator->isLength('other_id2',
                $value,
                TTi18n::gettext('Other ID 2 is invalid'),
                1, 255)
        ) {
            $this->data['other_id2'] = $value;

            return true;
        }

        return false;
    }

    //
    //FIXME: The problem with assigning schedules to other dates than what they start on, is that employees can get confused
    //		 as to what day their shift actually starts on, especially when looking at iCal schedules, or printed schedules.
    //		 It can even be different for some employees if they are assigned to other pay period schedules.
    //		 However its likely they may already know this anyways, due to internal termination, if they call a Monday shift one that starts Sunday night for example.

    public function getOtherID3()
    {
        if (isset($this->data['other_id3'])) {
            return $this->data['other_id3'];
        }

        return false;
    }

    public function setOtherID3($value)
    {
        $value = trim($value);

        if ($value == ''
            or
            $this->Validator->isLength('other_id3',
                $value,
                TTi18n::gettext('Other ID 3 is invalid'),
                1, 255)
        ) {
            $this->data['other_id3'] = $value;

            return true;
        }

        return false;
    }

    public function getOtherID4()
    {
        if (isset($this->data['other_id4'])) {
            return $this->data['other_id4'];
        }

        return false;
    }

    public function setOtherID4($value)
    {
        $value = trim($value);

        if ($value == ''
            or
            $this->Validator->isLength('other_id4',
                $value,
                TTi18n::gettext('Other ID 4 is invalid'),
                1, 255)
        ) {
            $this->data['other_id4'] = $value;

            return true;
        }

        return false;
    }

    public function getOtherID5()
    {
        if (isset($this->data['other_id5'])) {
            return $this->data['other_id5'];
        }

        return false;
    }

    public function setOtherID5($value)
    {
        $value = trim($value);

        if ($value == ''
            or
            $this->Validator->isLength('other_id5',
                $value,
                TTi18n::gettext('Other ID 5 is invalid'),
                1, 255)
        ) {
            $this->data['other_id5'] = $value;

            return true;
        }

        return false;
    }

    public function inScheduleDifference($epoch, $status_id = false)
    {
        $retval = false;
        if ($epoch >= $this->getStartTime() and $epoch <= $this->getEndTime()) {
            Debug::text('aWithin Schedule: ' . $epoch, __FILE__, __LINE__, __METHOD__, 10);

            $retval = 0; //Within schedule start/end time, no difference.
        } else {
            if (($status_id == false or $status_id == 10) and $epoch < $this->getStartTime() and $this->inStartWindow($epoch)) {
                $retval = ($this->getStartTime() - $epoch);
            } elseif (($status_id == false or $status_id == 20) and $epoch > $this->getEndTime() and $this->inStopWindow($epoch)) {
                $retval = ($epoch - $this->getEndTime());
            } else {
                $retval = false; //Not within start/stop window at all, return FALSE.
            }
        }

        //Debug::text('Difference from schedule: "'. $retval .'" Epoch: '. $epoch .' Status: '. $status_id, __FILE__, __LINE__, __METHOD__, 10);
        return $retval;
    }

    public function getStartTime($raw = false)
    {
        if (isset($this->data['start_time'])) {
            return TTDate::strtotime($this->data['start_time']);
        }

        return false;
    }

    public function getEndTime($raw = false)
    {
        if (isset($this->data['end_time'])) {
            return TTDate::strtotime($this->data['end_time']);
        }

        return false;
    }

    public function inStartWindow($epoch)
    {
        //Debug::text(' Epoch: '. $epoch, __FILE__, __LINE__, __METHOD__, 10);

        if ($epoch == '') {
            return false;
        }

        $start_stop_window = $this->getStartStopWindow();
        if ($epoch >= ($this->getStartTime() - $start_stop_window) and $epoch <= ($this->getStartTime() + $start_stop_window)) {
            Debug::text(' Within Start/Stop window: ' . $start_stop_window, __FILE__, __LINE__, __METHOD__, 10);
            return true;
        }

        //Debug::text(' NOT Within Start window. Epoch: '. $epoch .' Window: '. $start_stop_window, __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function getStartStopWindow()
    {
        if (is_object($this->getSchedulePolicyObject())) {
            $start_stop_window = (int)$this->getSchedulePolicyObject()->getStartStopWindow();
        } else {
            $start_stop_window = (3600 * 2); //Default to 2hr to help avoid In Late exceptions when they come in too early.
        }

        return $start_stop_window;
    }

    public function getSchedulePolicyObject()
    {
        return $this->getGenericObject('SchedulePolicyListFactory', $this->getSchedulePolicyID(), 'schedule_policy_obj');
    }

    public function getSchedulePolicyID()
    {
        if (isset($this->data['schedule_policy_id'])) {
            return (int)$this->data['schedule_policy_id'];
        }

        return false;
    }

    public function inStopWindow($epoch)
    {
        //Debug::text(' Epoch: '. $epoch, __FILE__, __LINE__, __METHOD__, 10);

        if ($epoch == '') {
            return false;
        }

        $start_stop_window = $this->getStartStopWindow();
        if ($epoch >= ($this->getEndTime() - $start_stop_window) and $epoch <= ($this->getEndTime() + $start_stop_window)) {
            Debug::text(' Within Start/Stop window: ' . $start_stop_window, __FILE__, __LINE__, __METHOD__, 10);
            return true;
        }

        //Debug::text(' NOT Within Stop window. Epoch: '. $epoch .' Window: '. $start_stop_window, __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function inSchedule($epoch)
    {
        if ($epoch >= $this->getStartTime() and $epoch <= $this->getEndTime()) {
            Debug::text('aWithin Schedule: ' . $epoch, __FILE__, __LINE__, __METHOD__, 10);

            return true;
        } elseif ($this->inStartWindow($epoch) or $this->inStopWindow($epoch)) {
            Debug::text('bWithin Schedule: ' . $epoch, __FILE__, __LINE__, __METHOD__, 10);

            return true;
        }

        return false;
    }

    public function mergeScheduleArray($schedule_shifts, $recurring_schedule_shifts)
    {
        //Debug::text('Merging Schedule, and Recurring Schedule Shifts: ', __FILE__, __LINE__, __METHOD__, 10);

        $ret_arr = $schedule_shifts;

        //Debug::Arr($schedule_shifts, '(c) Schedule Shifts: ', __FILE__, __LINE__, __METHOD__, 10);

        if (is_array($recurring_schedule_shifts) and count($recurring_schedule_shifts) > 0) {
            foreach ($recurring_schedule_shifts as $date_stamp => $day_shifts_arr) {
                //Debug::text('----------------------------------', __FILE__, __LINE__, __METHOD__, 10);
                //Debug::text('Date Stamp: '. TTDate::getDate('DATE+TIME', $date_stamp). ' Epoch: '. $date_stamp, __FILE__, __LINE__, __METHOD__, 10);
                //Debug::Arr($schedule_shifts[$date_stamp], 'Date Arr: ', __FILE__, __LINE__, __METHOD__, 10);
                foreach ($day_shifts_arr as $shift_arr) {
                    if (isset($ret_arr[$date_stamp])) {
                        //Debug::text('Already Schedule Shift on this day: '. TTDate::getDate('DATE', $date_stamp), __FILE__, __LINE__, __METHOD__, 10);

                        //Loop through each shift on this day, and check for overlaps
                        //Only include the recurring shift if ALL times DO NOT overlap
                        $overlap = 0;
                        foreach ($ret_arr[$date_stamp] as $tmp_shift_arr) {
                            if (TTDate::isTimeOverLap($shift_arr['start_time'], $shift_arr['end_time'], $tmp_shift_arr['start_time'], $tmp_shift_arr['end_time'])) {
                                //Debug::text('Times OverLap: '. TTDate::getDate('DATE+TIME', $shift_arr['start_time']), __FILE__, __LINE__, __METHOD__, 10);
                                $overlap++;
                            } //else { //Debug::text('Times DO NOT OverLap: '. TTDate::getDate('DATE+TIME', $shift_arr['start_time']), __FILE__, __LINE__, __METHOD__, 10);
                        }

                        if ($overlap == 0) {
                            //Debug::text('NO Times OverLap, using recurring schedule: '. TTDate::getDate('DATE+TIME', $shift_arr['start_time']), __FILE__, __LINE__, __METHOD__, 10);
                            $ret_arr[$date_stamp][] = $shift_arr;
                        }
                    } else {
                        //Debug::text('No Schedule Shift on this day: '. TTDate::getDate('DATE', $date_stamp), __FILE__, __LINE__, __METHOD__, 10);
                        $ret_arr[$date_stamp][] = $shift_arr;
                    }
                }
            }
        }

        return $ret_arr;
    }

    public function getScheduleArray($filter_data, $permission_children_ids = null)
    {
        global $current_user;

        //Get all schedule data by general filter criteria.
        //Debug::Arr($filter_data, 'Filter Data: ', __FILE__, __LINE__, __METHOD__, 10);

        if (!isset($filter_data['start_date']) or $filter_data['start_date'] == '') {
            return false;
        }

        if (!isset($filter_data['end_date']) or $filter_data['end_date'] == '') {
            return false;
        }

        $filter_data['start_date'] = TTDate::getBeginDayEpoch($filter_data['start_date']);
        $filter_data['end_date'] = TTDate::getEndDayEpoch($filter_data['end_date']);

        $pcf = TTnew('PayCodeFactory');
        $absence_policy_paid_type_options = $pcf->getOptions('paid_type');

        $max_i = 0;

        $slf = TTnew('ScheduleListFactory');
        if (isset($filter_data['filter_items_per_page'])) {
            if (!isset($filter_data['filter_page'])) {
                $filter_data['filter_page'] = 1;
            }
            $slf->getSearchByCompanyIdAndArrayCriteria($current_user->getCompany(), $filter_data, $filter_data['filter_items_per_page'], $filter_data['filter_page']);
        } else {
            $slf->getSearchByCompanyIdAndArrayCriteria($current_user->getCompany(), $filter_data);
        }
        Debug::text('Found Scheduled Rows: ' . $slf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
        //Debug::Arr($absence_policy_paid_type_options, 'Paid Absences: ', __FILE__, __LINE__, __METHOD__, 10);
        $scheduled_user_ids = array();
        if ($slf->getRecordCount() > 0) {
            $this->getProgressBarObject()->start($this->getAMFMessageID(), $slf->getRecordCount(), null, TTi18n::getText('Processing Committed Shifts...'));

            $schedule_shifts = array();
            $i = 0;
            foreach ($slf as $s_obj) {
                if ((int)$s_obj->getUser() == 0) {
                    continue;
                }

                //Debug::text('Schedule ID: '. $s_obj->getId() .' User ID: '. $s_obj->getUser() .' Start Time: '. $s_obj->getStartTime(), __FILE__, __LINE__, __METHOD__, 10);
                if ($s_obj->getAbsencePolicyID() > 0) {
                    $absence_policy_name = $s_obj->getColumn('absence_policy');
                } else {
                    $absence_policy_name = null; //Must be NULL for it to appear as "N/A" in legacy interface.
                }

                $hourly_rate = Misc::MoneyFormat($s_obj->getColumn('user_wage_hourly_rate'), false);

                if ($s_obj->getStatus() == 20 //Absence
                    and
                    (
                        $s_obj->getAbsencePolicyID() == 0
                        or
                        (
                            $s_obj->getAbsencePolicyID() > 0
                            and is_object($s_obj->getAbsencePolicyObject())
                            and is_object($s_obj->getAbsencePolicyObject()->getPayCodeObject())
                            and in_array($s_obj->getAbsencePolicyObject()->getPayCodeObject()->getType(), $absence_policy_paid_type_options) == false
                        )
                    )
                ) {
                    //UnPaid Absence.
                    $total_time_wage = Misc::MoneyFormat(0);
                } else {
                    $total_time_wage = Misc::MoneyFormat(bcmul(TTDate::getHours($s_obj->getColumn('total_time')), $hourly_rate), false);
                }

                $iso_date_stamp = TTDate::getISODateStamp($s_obj->getDateStamp());
                $schedule_shifts[$iso_date_stamp][$i] = array(
                    'id' => (int)$s_obj->getID(),
                    'replaced_id' => (int)$s_obj->getReplacedID(),
                    'recurring_schedule_id' => (int)$s_obj->getColumn('recurring_schedule_id'),
                    'pay_period_id' => (int)$s_obj->getColumn('pay_period_id'),
                    'user_id' => (int)$s_obj->getUser(),
                    'user_created_by' => (int)$s_obj->getColumn('user_created_by'),
                    'user_full_name' => ($s_obj->getUser() > 0) ? Misc::getFullName($s_obj->getColumn('first_name'), null, $s_obj->getColumn('last_name'), false, false) : TTi18n::getText('OPEN'),
                    'first_name' => ($s_obj->getUser() > 0) ? $s_obj->getColumn('first_name') : TTi18n::getText('OPEN'),
                    'last_name' => $s_obj->getColumn('last_name'),
                    'title_id' => (int)$s_obj->getColumn('title_id'),
                    'title' => $s_obj->getColumn('title'),
                    'group_id' => (int)$s_obj->getColumn('group_id'),
                    'group' => $s_obj->getColumn('group'),
                    'default_branch_id' => (int)$s_obj->getColumn('default_branch_id'),
                    'default_branch' => $s_obj->getColumn('default_branch'),
                    'default_department_id' => (int)$s_obj->getColumn('default_department_id'),
                    'default_department' => $s_obj->getColumn('default_department'),
                    'default_job_id' => (int)$s_obj->getColumn('default_job_id'),
                    'default_job' => $s_obj->getColumn('default_job'),
                    'default_job_item_id' => (int)$s_obj->getColumn('default_job_item_id'),
                    'default_job_item' => $s_obj->getColumn('default_job_item'),

                    'job_id' => (int)$s_obj->getColumn('job_id'),
                    'job' => $s_obj->getColumn('job'),
                    'job_status_id' => (int)$s_obj->getColumn('job_status_id'),
                    'job_manual_id' => (int)$s_obj->getColumn('job_manual_id'),
                    'job_branch_id' => (int)$s_obj->getColumn('job_branch_id'),
                    'job_department_id' => (int)$s_obj->getColumn('job_department_id'),
                    'job_group_id' => (int)$s_obj->getColumn('job_group_id'),

                    'job_address1' => $s_obj->getColumn('job_address1'),
                    'job_address2' => $s_obj->getColumn('job_address2'),
                    'job_city' => $s_obj->getColumn('job_city'),
                    'job_country' => $s_obj->getColumn('job_country'),
                    'job_province' => $s_obj->getColumn('job_province'),
                    'job_postal_code' => $s_obj->getColumn('job_postal_code'),
                    'job_longitude' => $s_obj->getColumn('job_longitude'),
                    'job_latitude' => $s_obj->getColumn('job_latitude'),
                    'job_location_note' => $s_obj->getColumn('job_location_note'),

                    'job_item_id' => (int)$s_obj->getColumn('job_item_id'),
                    'job_item' => $s_obj->getColumn('job_item'),

                    'type_id' => 10, //Committed
                    'status_id' => (int)$s_obj->getStatus(),

                    'date_stamp' => TTDate::getAPIDate('DATE', $s_obj->getDateStamp()), //Date the schedule is displayed on
                    'start_date_stamp' => TTDate::getAPIDate('DATE', $s_obj->getStartTime()), //Date the schedule starts on.
                    'start_date' => TTDate::getAPIDate('DATE+TIME', $s_obj->getStartTime()),
                    'end_date' => TTDate::getAPIDate('DATE+TIME', $s_obj->getEndTime()),
                    'end_date_stamp' => TTDate::getAPIDate('DATE', $s_obj->getEndTime()), //Date the schedule ends on.
                    'start_time' => TTDate::getAPIDate('TIME', $s_obj->getStartTime()),
                    'end_time' => TTDate::getAPIDate('TIME', $s_obj->getEndTime()),

                    'start_time_stamp' => $s_obj->getStartTime(),
                    'end_time_stamp' => $s_obj->getEndTime(),

                    'total_time' => $s_obj->getTotalTime(),

                    'hourly_rate' => $hourly_rate,
                    'total_time_wage' => $total_time_wage,

                    'note' => $s_obj->getColumn('note'),

                    'schedule_policy_id' => (int)$s_obj->getSchedulePolicyID(),
                    'absence_policy_id' => (int)$s_obj->getAbsencePolicyID(),
                    'absence_policy' => $absence_policy_name,
                    'branch_id' => (int)$s_obj->getBranch(),
                    'branch' => $s_obj->getColumn('branch'),
                    'department_id' => (int)$s_obj->getDepartment(),
                    'department' => $s_obj->getColumn('department'),

                    'recurring_schedule_template_control_id' => $s_obj->getRecurringScheduleTemplateControl(),

                    'created_by_id' => (int)$s_obj->getCreatedBy(),
                    'created_date' => $s_obj->getCreatedDate(),
                    'updated_date' => $s_obj->getUpdatedDate(),
                );

                //Make sure we add in permission columns.
                $this->getPermissionColumns($schedule_shifts[$iso_date_stamp][$i], (int)$s_obj->getUser(), $s_obj->getCreatedBy(), $permission_children_ids);

                unset($absence_policy_name);

                if (isset($filter_data['include_all_users']) and $filter_data['include_all_users'] == true) {
                    $scheduled_user_ids[] = (int)$s_obj->getUser(); //Used below if
                }

                $this->getProgressBarObject()->set($this->getAMFMessageID(), $slf->getCurrentRow());

                $i++;
            }
            $max_i = $i;
            unset($i);

            $this->getProgressBarObject()->stop($this->getAMFMessageID());

            //Debug::Arr($schedule_shifts, 'Committed Schedule Shifts: ', __FILE__, __LINE__, __METHOD__, 10);
            Debug::text('Processed Scheduled Rows: ' . $slf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
        } else {
            $schedule_shifts = array();
        }
        unset($slf);

        //Include employees without scheduled shifts.
        if (isset($filter_data['include_all_users']) and $filter_data['include_all_users'] == true) {
            if (!isset($filter_data['exclude_id'])) {
                $filter_data['exclude_id'] = array();
            }

            //If the user is searching for scheduled branch/departments, convert that to default branch/departments when Show All Employees is enabled.
            if (isset($filter_data['branch_ids']) and !isset($filter_data['default_branch_ids'])) {
                $filter_data['default_branch_ids'] = $filter_data['branch_ids'];
            }
            if (isset($filter_data['department_ids']) and !isset($filter_data['default_department_ids'])) {
                $filter_data['default_department_ids'] = $filter_data['department_ids'];
            }

            $scheduled_user_ids = (empty($scheduled_user_ids) == false) ? array_unique($scheduled_user_ids) : array();
            $filter_data['exclude_id'] = array_merge($filter_data['exclude_id'], $scheduled_user_ids);
            if (isset($filter_data['exclude_id'])) {
                //Debug::Arr($filter_data['exclude_id'], 'Including all employees. Excluded User Ids: ', __FILE__, __LINE__, __METHOD__, 10);
                //Debug::Arr($filter_data, 'All Filter Data: ', __FILE__, __LINE__, __METHOD__, 10);

                //Only include active employees without any scheduled shifts.
                $filter_data['status_id'] = 10;

                $ulf = TTnew('UserListFactory');
                $ulf->getAPISearchByCompanyIdAndArrayCriteria($current_user->getCompany(), $filter_data);
                Debug::text('Found blank employees: ' . $ulf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
                if ($ulf->getRecordCount() > 0) {
                    $this->getProgressBarObject()->start($this->getAMFMessageID(), $ulf->getRecordCount(), null, TTi18n::getText('Processing Employees...'));

                    $i = $max_i;
                    foreach ($ulf as $u_obj) {
                        //Create dummy shift arrays with no start/end time.
                        //$schedule_shifts[TTDate::getISODateStamp( $filter_data['start_date'] )][$u_obj->getID().TTDate::getBeginDayEpoch($filter_data['start_date'])] = array(
                        $schedule_shifts[TTDate::getISODateStamp($filter_data['start_date'])][$i] = array(
                            //'id' => (int)$u_obj->getID(),
                            'pay_period_id' => false,
                            'user_id' => (int)$u_obj->getID(),
                            'user_created_by' => (int)$u_obj->getCreatedBy(),
                            'user_full_name' => Misc::getFullName($u_obj->getFirstName(), null, $u_obj->getLastName(), false, false),
                            'first_name' => $u_obj->getFirstName(),
                            'last_name' => $u_obj->getLastName(),
                            'title_id' => $u_obj->getTitle(),
                            'title' => $u_obj->getColumn('title'),
                            'group_id' => $u_obj->getColumn('group_id'),
                            'group' => $u_obj->getColumn('group'),
                            'default_branch_id' => $u_obj->getColumn('default_branch_id'),
                            'default_branch' => $u_obj->getColumn('default_branch'),
                            'default_department_id' => $u_obj->getColumn('default_department_id'),
                            'default_department' => $u_obj->getColumn('default_department'),

                            'branch_id' => (int)$u_obj->getDefaultBranch(),
                            'branch' => $u_obj->getColumn('default_branch'),
                            'department_id' => (int)$u_obj->getDefaultDepartment(),
                            'department' => $u_obj->getColumn('default_department'),

                            'created_by_id' => $u_obj->getCreatedBy(),
                            'created_date' => $u_obj->getCreatedDate(),
                            'updated_date' => $u_obj->getUpdatedDate(),
                        );

                        //Make sure we add in permission columns.
                        $this->getPermissionColumns($schedule_shifts[TTDate::getISODateStamp($filter_data['start_date'])][$i], (int)$u_obj->getID(), $u_obj->getCreatedBy(), $permission_children_ids);

                        $this->getProgressBarObject()->set($this->getAMFMessageID(), $ulf->getCurrentRow());

                        $i++;
                    }

                    $this->getProgressBarObject()->stop($this->getAMFMessageID());
                }
            }
            //Debug::Arr($schedule_shifts, 'Final Scheduled Shifts: ', __FILE__, __LINE__, __METHOD__, 10);
        }

        if (isset($schedule_shifts)) {
            return $schedule_shifts;
        }

        return false;
    }

    public function setEnableTimeSheetVerificationCheck($bool)
    {
        $this->timesheet_verification_check = $bool;

        return true;
    }

    public function getSchedule($filter_data, $start_week_day = 0, $group_schedule = false)
    {
        global $current_user;

        //Individual is one schedule per employee, or all on one schedule.
        if (!is_array($filter_data)) {
            return false;
        }

        $current_epoch = time();

        //Debug::Text('Start Date: '. TTDate::getDate('DATE', $start_date) .' End Date: '. TTDate::getDate('DATE', $end_date), __FILE__, __LINE__, __METHOD__, 10);
        Debug::text(' Start Date: ' . TTDate::getDate('DATE+TIME', $filter_data['start_date']) . ' End Date: ' . TTDate::getDate('DATE+TIME', $filter_data['end_date']) . ' Start Week Day: ' . $start_week_day, __FILE__, __LINE__, __METHOD__, 10);

        $pdf = new TTPDF('L', 'pt', 'LETTER');

        $left_margin = 20;
        $top_margin = 20;
        $pdf->setMargins($left_margin, $top_margin);
        $pdf->SetAutoPageBreak(true, 30);
        //$pdf->SetAutoPageBreak(FALSE);
        $pdf->SetFont('freesans', '', 10);

        $border = 0;
        $raw_schedule_shifts = array();
        $schedule_shift_totals = array();
        $max_week_data = array();
        $week_date_stamps = array();
        if ($group_schedule == false) {
            $valid_schedules = 0;

            $sf = TTnew('ScheduleFactory');
            $tmp_schedule_shifts = $sf->getScheduleArray($filter_data);
            //Re-arrange array by user_id->date
            if (is_array($tmp_schedule_shifts)) {
                foreach ($tmp_schedule_shifts as $day_epoch => $day_schedule_shifts) {
                    foreach ($day_schedule_shifts as $day_schedule_shift) {
                        $raw_schedule_shifts[$day_schedule_shift['user_id']][$day_epoch][] = $day_schedule_shift;
                    }
                }
            }
            unset($tmp_schedule_shifts);
            //Debug::Arr($raw_schedule_shifts, 'Raw Schedule Shifts: ', __FILE__, __LINE__, __METHOD__, 10);

            if (empty($raw_schedule_shifts) == false) {
                foreach ($raw_schedule_shifts as $user_id => $day_schedule_shifts) {
                    foreach ($day_schedule_shifts as $day_epoch => $day_schedule_shifts) {
                        foreach ($day_schedule_shifts as $day_schedule_shift) {
                            //Debug::Arr($day_schedule_shift, 'aDay Schedule Shift: ', __FILE__, __LINE__, __METHOD__, 10);
                            $tmp_schedule_shifts[$day_epoch][$day_schedule_shift['branch']][$day_schedule_shift['department']][] = $day_schedule_shift;

                            if (isset($schedule_shift_totals[$day_epoch]['total_shifts'])) {
                                $schedule_shift_totals[$day_epoch]['total_shifts']++;
                            } else {
                                $schedule_shift_totals[$day_epoch]['total_shifts'] = 1;
                            }

                            //$week_of_year = TTDate::getWeek( strtotime($day_epoch) );
                            $week_of_year = TTDate::getWeek(strtotime($day_epoch), $start_week_day);
                            if (!isset($schedule_shift_totals[$day_epoch]['labels'])) {
                                $schedule_shift_totals[$day_epoch]['labels'] = 0;
                            }
                            if ($day_schedule_shift['branch'] != '--'
                                and !isset($schedule_shift_totals[$day_epoch]['branch'][$day_schedule_shift['branch']])
                            ) {
                                $schedule_shift_totals[$day_epoch]['branch'][$day_schedule_shift['branch']] = true;
                                $schedule_shift_totals[$day_epoch]['labels']++;
                            }
                            if ($day_schedule_shift['department'] != '--'
                                and !isset($schedule_shift_totals[$day_epoch]['department'][$day_schedule_shift['branch']][$day_schedule_shift['department']])
                            ) {
                                $schedule_shift_totals[$day_epoch]['department'][$day_schedule_shift['branch']][$day_schedule_shift['department']] = true;
                                $schedule_shift_totals[$day_epoch]['labels']++;
                            }

                            if (!isset($max_week_data[$week_of_year]['shift'])) {
                                Debug::text('Date: ' . $day_epoch . ' Week: ' . $week_of_year . ' Setting Max Week shift to 0', __FILE__, __LINE__, __METHOD__, 10);
                                $max_week_data[$week_of_year]['shift'] = 1;
                                $max_week_data[$week_of_year]['labels'] = 0;
                            }

                            if (isset($max_week_data[$week_of_year]['shift'])
                                and ($schedule_shift_totals[$day_epoch]['total_shifts'] + $schedule_shift_totals[$day_epoch]['labels']) > ($max_week_data[$week_of_year]['shift'] + $max_week_data[$week_of_year]['labels'])
                            ) {
                                Debug::text('Date: ' . $day_epoch . ' Week: ' . $week_of_year . ' Setting Max Week shift to: ' . $schedule_shift_totals[$day_epoch]['total_shifts'] . ' Labels: ' . $schedule_shift_totals[$day_epoch]['labels'], __FILE__, __LINE__, __METHOD__, 10);
                                $max_week_data[$week_of_year]['shift'] = $schedule_shift_totals[$day_epoch]['total_shifts'];
                                $max_week_data[$week_of_year]['labels'] = $schedule_shift_totals[$day_epoch]['labels'];
                            }

                            //Debug::Arr($schedule_shift_totals, ' Schedule Shift Totals: ', __FILE__, __LINE__, __METHOD__, 10);
                            //Debug::Arr($max_week_data, ' zMaxWeekData: ', __FILE__, __LINE__, __METHOD__, 10);
                        }
                    }

                    if (isset($tmp_schedule_shifts)) {
                        //Sort Branches/Departments first
                        foreach ($tmp_schedule_shifts as $day_epoch => $day_tmp_schedule_shift) {
                            ksort($day_tmp_schedule_shift);
                            $tmp_schedule_shifts[$day_epoch] = $day_tmp_schedule_shift;

                            foreach ($day_tmp_schedule_shift as $branch => $department_schedule_shifts) {
                                ksort($tmp_schedule_shifts[$day_epoch][$branch]);
                            }
                        }

                        //Sort each department by start time.
                        foreach ($tmp_schedule_shifts as $day_epoch => $day_tmp_schedule_shift) {
                            foreach ($day_tmp_schedule_shift as $branch => $department_schedule_shifts) {
                                foreach ($department_schedule_shifts as $department => $department_schedule_shift) {
                                    $department_schedule_shift = Sort::multiSort($department_schedule_shift, 'start_time');

                                    $this->schedule_shifts[$day_epoch][$branch][$department] = $department_schedule_shift;
                                }
                            }
                        }
                    }
                    unset($day_tmp_schedule_shift, $department_schedule_shifts, $department_schedule_shift, $tmp_schedule_shifts, $branch, $department);

                    $calendar_array = TTDate::getCalendarArray($filter_data['start_date'], $filter_data['end_date'], $start_week_day);
                    //var_dump($calendar_array);

                    if (!is_array($calendar_array) or !isset($this->schedule_shifts) or !is_array($this->schedule_shifts)) {
                        continue; //Skip to next user.
                    }

                    $ulf = TTnew('UserListFactory');
                    $ulf->getByIdAndCompanyId($user_id, $current_user->getCompany());
                    if ($ulf->getRecordCount() != 1) {
                        continue;
                    } else {
                        $user_obj = $ulf->getCurrent();

                        $pdf->AddPage();

                        $pdf->setXY(670, $top_margin);
                        $pdf->SetFont('freesans', '', 10);
                        $pdf->Cell(100, 15, TTDate::getDate('DATE+TIME', $current_epoch), $border, 0, 'R');

                        $pdf->setXY($left_margin, $top_margin);
                        $pdf->SetFont('freesans', 'B', 25);
                        $pdf->Cell(0, 25, $user_obj->getFullName() . ' - ' . TTi18n::getText('Schedule'), $border, 0, 'C');
                        $pdf->Ln();
                    }

                    $pdf->SetFont('freesans', 'B', 16);
                    $pdf->Cell(0, 15, TTDate::getDate('DATE', $filter_data['start_date']) . ' - ' . TTDate::getDate('DATE', $filter_data['end_date']), $border, 0, 'C');
                    //$pdf->Ln();
                    $pdf->Ln();
                    $pdf->Ln();

                    $pdf->SetFont('freesans', '', 8);

                    $cell_width = floor(($pdf->GetPageWidth() - ($left_margin * 2)) / 7);

                    $i = 0;
                    $border = 1;
                    foreach ($calendar_array as $calendar) {
                        if ($i == 0) {
                            //Calendar Header
                            $pdf->SetFont('freesans', 'B', 8);
                            $calendar_header = TTDate::getDayOfWeekArrayByStartWeekDay($start_week_day);

                            foreach ($calendar_header as $header_name) {
                                $pdf->Cell($cell_width, 15, $header_name, 1, 0, 'C');
                            }

                            $pdf->Ln();
                            unset($calendar_header, $header_name);
                        }

                        $month_name = null;
                        if ($i == 0 or $calendar['isNewMonth'] == true) {
                            $month_name = $calendar['month_name'];
                        }

                        if (($i > 0 and $i % 7 == 0)) {
                            $this->writeWeekSchedule($pdf, $cell_width, $week_date_stamps, $max_week_data, $left_margin, $group_schedule, $start_week_day);
                            unset($week_date_stamps);
                        }

                        $pdf->SetFont('freesans', 'B', 8);
                        $pdf->Cell(($cell_width / 2), 15, $month_name, 'LT', 0, 'L');
                        $pdf->Cell(($cell_width / 2), 15, $calendar['day_of_month'], 'RT', 0, 'R');

                        $week_date_stamps[] = $calendar['date_stamp'];

                        $i++;
                    }

                    $this->writeWeekSchedule($pdf, $cell_width, $week_date_stamps, $max_week_data, $left_margin, $group_schedule, $start_week_day, true);

                    $valid_schedules++;

                    unset($this->schedule_shifts, $calendar_array, $week_date_stamps, $max_week_data, $day_epoch, $day_schedule_shifts, $day_schedule_shift, $schedule_shift_totals);
                }
            }
            unset($raw_schedule_shifts);
        } else {
            $valid_schedules = 1;

            $sf = TTnew('ScheduleFactory');
            $raw_schedule_shifts = $sf->getScheduleArray($filter_data);
            if (empty($raw_schedule_shifts) == false) {
                foreach ($raw_schedule_shifts as $day_epoch => $day_schedule_shifts) {
                    foreach ($day_schedule_shifts as $day_schedule_shift) {
                        //Debug::Arr($day_schedule_shift, 'bDay Schedule Shift: ', __FILE__, __LINE__, __METHOD__, 10);
                        $tmp_schedule_shifts[$day_epoch][$day_schedule_shift['branch']][$day_schedule_shift['department']][] = $day_schedule_shift;

                        if (isset($schedule_shift_totals[$day_epoch]['total_shifts'])) {
                            $schedule_shift_totals[$day_epoch]['total_shifts']++;
                        } else {
                            $schedule_shift_totals[$day_epoch]['total_shifts'] = 1;
                        }

                        //$week_of_year = TTDate::getWeek( strtotime($day_epoch) );
                        $week_of_year = TTDate::getWeek(strtotime($day_epoch), $start_week_day);
                        Debug::text(' Date: ' . TTDate::getDate('DATE', strtotime($day_epoch)) . ' Week: ' . $week_of_year . ' TMP: ' . TTDate::getWeek(strtotime('20070721'), $start_week_day), __FILE__, __LINE__, __METHOD__, 10);
                        if (!isset($schedule_shift_totals[$day_epoch]['labels'])) {
                            $schedule_shift_totals[$day_epoch]['labels'] = 0;
                        }
                        if ($day_schedule_shift['branch'] != '--'
                            and !isset($schedule_shift_totals[$day_epoch]['branch'][$day_schedule_shift['branch']])
                        ) {
                            $schedule_shift_totals[$day_epoch]['branch'][$day_schedule_shift['branch']] = true;
                            $schedule_shift_totals[$day_epoch]['labels']++;
                        }
                        if ($day_schedule_shift['department'] != '--'
                            and !isset($schedule_shift_totals[$day_epoch]['department'][$day_schedule_shift['branch']][$day_schedule_shift['department']])
                        ) {
                            $schedule_shift_totals[$day_epoch]['department'][$day_schedule_shift['branch']][$day_schedule_shift['department']] = true;
                            $schedule_shift_totals[$day_epoch]['labels']++;
                        }

                        if (!isset($max_week_data[$week_of_year]['shift'])) {
                            Debug::text('Date: ' . $day_epoch . ' Week: ' . $week_of_year . ' Setting Max Week shift to 0', __FILE__, __LINE__, __METHOD__, 10);
                            $max_week_data[$week_of_year]['shift'] = 1;
                            $max_week_data[$week_of_year]['labels'] = 0;
                        }

                        if (isset($max_week_data[$week_of_year]['shift'])
                            and ($schedule_shift_totals[$day_epoch]['total_shifts'] + $schedule_shift_totals[$day_epoch]['labels']) > ($max_week_data[$week_of_year]['shift'] + $max_week_data[$week_of_year]['labels'])
                        ) {
                            Debug::text('Date: ' . $day_epoch . ' Week: ' . $week_of_year . ' Setting Max Week shift to: ' . $schedule_shift_totals[$day_epoch]['total_shifts'] . ' Labels: ' . $schedule_shift_totals[$day_epoch]['labels'], __FILE__, __LINE__, __METHOD__, 10);
                            $max_week_data[$week_of_year]['shift'] = $schedule_shift_totals[$day_epoch]['total_shifts'];
                            $max_week_data[$week_of_year]['labels'] = $schedule_shift_totals[$day_epoch]['labels'];
                        }
                    }
                }
            }
            //print_r($tmp_schedule_shifts);
            //print_r($max_week_data);

            if (isset($tmp_schedule_shifts)) {
                //Sort Branches/Departments first
                foreach ($tmp_schedule_shifts as $day_epoch => $day_tmp_schedule_shift) {
                    ksort($day_tmp_schedule_shift);
                    $tmp_schedule_shifts[$day_epoch] = $day_tmp_schedule_shift;

                    foreach ($day_tmp_schedule_shift as $branch => $department_schedule_shifts) {
                        ksort($tmp_schedule_shifts[$day_epoch][$branch]);
                    }
                }

                //Sort each department by start time.
                foreach ($tmp_schedule_shifts as $day_epoch => $day_tmp_schedule_shift) {
                    foreach ($day_tmp_schedule_shift as $branch => $department_schedule_shifts) {
                        foreach ($department_schedule_shifts as $department => $department_schedule_shift) {
                            $department_schedule_shift = Sort::multiSort($department_schedule_shift, 'last_name');
                            $this->schedule_shifts[$day_epoch][$branch][$department] = $department_schedule_shift;
                        }
                    }
                }
            }
            //Debug::Arr($this->schedule_shifts, 'Schedule Shifts: ', __FILE__, __LINE__, __METHOD__, 10);

            $calendar_array = TTDate::getCalendarArray($filter_data['start_date'], $filter_data['end_date'], $start_week_day);
            //var_dump($calendar_array);

            if (!is_array($calendar_array) or !isset($this->schedule_shifts) or !is_array($this->schedule_shifts)) {
                return false;
            }

            $pdf->AddPage();

            $pdf->setXY(670, $top_margin);
            $pdf->SetFont('freesans', '', 10);
            $pdf->Cell(100, 15, TTDate::getDate('DATE+TIME', $current_epoch), $border, 0, 'R');

            $pdf->setXY($left_margin, $top_margin);

            $pdf->SetFont('freesans', 'B', 25);
            $pdf->Cell(0, 25, 'Employee Schedule', $border, 0, 'C');
            $pdf->Ln();

            $pdf->SetFont('freesans', 'B', 10);
            $pdf->Cell(0, 15, TTDate::getDate('DATE', $filter_data['start_date']) . ' - ' . TTDate::getDate('DATE', $filter_data['end_date']), $border, 0, 'C');
            $pdf->Ln();
            $pdf->Ln();

            $pdf->SetFont('freesans', '', 8);

            $cell_width = floor(($pdf->GetPageWidth() - ($left_margin * 2)) / 7);

            $i = 0;
            $border = 1;
            foreach ($calendar_array as $calendar) {
                if ($i == 0) {
                    //Calendar Header
                    $pdf->SetFont('freesans', 'B', 8);
                    $calendar_header = TTDate::getDayOfWeekArrayByStartWeekDay($start_week_day);

                    foreach ($calendar_header as $header_name) {
                        $pdf->Cell($cell_width, 15, $header_name, 1, 0, 'C');
                    }

                    $pdf->Ln();
                    unset($calendar_header, $header_name);
                }

                $month_name = null;
                if ($i == 0 or $calendar['isNewMonth'] == true) {
                    $month_name = $calendar['month_name'];
                }

                if (($i > 0 and $i % 7 == 0)) {
                    $this->writeWeekSchedule($pdf, $cell_width, $week_date_stamps, $max_week_data, $left_margin, $group_schedule, $start_week_day);
                    unset($week_date_stamps);
                }

                $pdf->SetFont('freesans', 'B', 8);
                $pdf->Cell(($cell_width / 2), 15, $month_name, 'LT', 0, 'L');
                $pdf->Cell(($cell_width / 2), 15, $calendar['day_of_month'], 'RT', 0, 'R');

                $week_date_stamps[] = $calendar['date_stamp'];

                $i++;
            }

            $this->writeWeekSchedule($pdf, $cell_width, $week_date_stamps, $max_week_data, $left_margin, $group_schedule, $start_week_day, true);
        }

        if ($valid_schedules > 0) {
            $output = $pdf->Output('', 'S');
            return $output;
        }

        return false;
    }

    public function writeWeekSchedule($pdf, $cell_width, $week_date_stamps, $max_week_data, $left_margin, $group_schedule, $start_week_day = 0, $bottom_border = false)
    {
        $week_of_year = TTDate::getWeek(strtotime($week_date_stamps[0]), $start_week_day);
        //Debug::Text('Max Week Shifts: '. (int)$max_week_data[$week_of_year]['shift'], __FILE__, __LINE__, __METHOD__, 10);
        //Debug::Text('Max Week Branches: '. count($max_week_data[$week_of_year]['branch']), __FILE__, __LINE__, __METHOD__, 10);
        //Debug::Text('Max Week Departments: '. count($max_week_data[$week_of_year]['department']), __FILE__, __LINE__, __METHOD__, 10);
        Debug::Text('Week Of Year: ' . $week_of_year, __FILE__, __LINE__, __METHOD__, 10);
        Debug::Arr($max_week_data, 'max_week_data: ', __FILE__, __LINE__, __METHOD__, 10);

        $week_data_array = null;

        if (!isset($max_week_data[$week_of_year]['labels'])) {
            $max_week_data[$week_of_year]['labels'] = 0;
        }

        if ($group_schedule == true) {
            $min_rows_multiplier = 2;
        } else {
            $min_rows_multiplier = 1;
        }

        if (isset($max_week_data[$week_of_year]['shift'])) {
            $min_rows_per_day = (($max_week_data[$week_of_year]['shift'] * $min_rows_multiplier) + $max_week_data[$week_of_year]['labels']);
            Debug::Text('Shift Total: ' . $max_week_data[$week_of_year]['shift'], __FILE__, __LINE__, __METHOD__, 10);
        } else {
            $min_rows_per_day = ($min_rows_multiplier + $max_week_data[$week_of_year]['labels']);
        }
        Debug::Text('aMin Rows Per Day: ' . $min_rows_per_day . ' Labels: ' . $max_week_data[$week_of_year]['labels'], __FILE__, __LINE__, __METHOD__, 10);
        //print_r($this->schedule_shifts);

        //Prepare data so we can write it out line by line, left to right.
        $shift_counter = 0;
        $tmp_week_data_array = array();
        foreach ($week_date_stamps as $week_date_stamp) {
            Debug::Text('Week Date Stamp: (' . $week_date_stamp . ')' . TTDate::getDate('DATE+TIME', strtotime($week_date_stamp)), __FILE__, __LINE__, __METHOD__, 10);

            $rows_per_day = 0;
            if (isset($this->schedule_shifts[$week_date_stamp])) {
                foreach ($this->schedule_shifts[$week_date_stamp] as $branch => $department_schedule_shifts) {
                    if ($branch != '--') {
                        $tmp_week_data_array[$week_date_stamp][] = array('type' => 'branch', 'date_stamp' => $week_date_stamp, 'label' => $branch);
                        $rows_per_day++;
                    }

                    foreach ($department_schedule_shifts as $department => $tmp_schedule_shifts) {
                        if ($department != '--') {
                            $tmp_week_data_array[$week_date_stamp][] = array('type' => 'department', 'label' => $department);
                            $rows_per_day++;
                        }

                        foreach ($tmp_schedule_shifts as $schedule_shift) {
                            if ($group_schedule == true) {
                                $tmp_week_data_array[$week_date_stamp][] = array('type' => 'user_name', 'label' => $schedule_shift['user_full_name'], 'shift' => $shift_counter);
                                if ($schedule_shift['status_id'] == 10) {
                                    $tmp_week_data_array[$week_date_stamp][] = array('type' => 'shift', 'label' => TTDate::getDate('TIME', $schedule_shift['start_time']) . ' - ' . TTDate::getDate('TIME', $schedule_shift['end_time']), 'shift' => $shift_counter);
                                } else {
                                    $tmp_week_data_array[$week_date_stamp][] = array('type' => 'absence', 'label' => $schedule_shift['absence_policy'], 'shift' => $shift_counter);
                                }
                                $rows_per_day += 2;
                            } else {
                                if ($schedule_shift['status_id'] == 10) {
                                    $tmp_week_data_array[$week_date_stamp][] = array('type' => 'shift', 'label' => TTDate::getDate('TIME', $schedule_shift['start_time']) . ' - ' . TTDate::getDate('TIME', $schedule_shift['end_time']), 'shift' => $shift_counter);
                                } else {
                                    $tmp_week_data_array[$week_date_stamp][] = array('type' => 'absence', 'label' => $schedule_shift['absence_policy'], 'shift' => $shift_counter);
                                }
                                $rows_per_day++;
                            }
                            $shift_counter++;
                        }
                    }
                }
            }

            if ($rows_per_day < $min_rows_per_day) {
                for ($z = $rows_per_day; $z < $min_rows_per_day; $z++) {
                    $tmp_week_data_array[$week_date_stamp][] = array('type' => 'blank', 'label' => null);
                }
            }
        }
        //print_r($tmp_week_data_array);

        for ($x = 0; $x < $min_rows_per_day; $x++) {
            foreach ($week_date_stamps as $week_date_stamp) {
                if (isset($tmp_week_data_array[$week_date_stamp][0])) {
                    $week_data_array[] = $tmp_week_data_array[$week_date_stamp][0];
                    array_shift($tmp_week_data_array[$week_date_stamp]);
                }
            }
        }
        unset($tmp_week_data_array);
        //print_r($week_data_array);

        //Render PDF here
        $border = 'LR';
        $i = 0;
        $total_cells = count($week_data_array);

        foreach ($week_data_array as $data) {
            if (($i % 7) == 0) {
                $pdf->Ln();
            }

            $pdf->setTextColor(0, 0, 0); //Black
            switch ($data['type']) {
                case 'branch':
                    $pdf->setFillColor(200, 200, 200);
                    $pdf->SetFont('freesans', 'B', 8);
                    break;
                case 'department':
                    $pdf->setFillColor(220, 220, 220);
                    $pdf->SetFont('freesans', 'B', 8);
                    break;
                case 'user_name':
                    if (($data['shift'] % 2) == 0) {
                        $pdf->setFillColor(240, 240, 240);
                    } else {
                        $pdf->setFillColor(255, 255, 255);
                    }
                    $pdf->SetFont('freesans', 'B', 8);
                    break;
                case 'shift':
                    if (($data['shift'] % 2) == 0) {
                        $pdf->setFillColor(240, 240, 240);
                    } else {
                        $pdf->setFillColor(255, 255, 255);
                    }
                    $pdf->SetFont('freesans', '', 8);
                    break;
                case 'absence':
                    $pdf->setTextColor(255, 0, 0);
                    if (($data['shift'] % 2) == 0) {
                        $pdf->setFillColor(240, 240, 240);
                    } else {
                        $pdf->setFillColor(255, 255, 255);
                    }
                    $pdf->SetFont('freesans', 'I', 8);
                    break;
                case 'blank':
                    $pdf->setFillColor(255, 255, 255);
                    $pdf->SetFont('freesans', '', 8);
                    break;
            }

            if ($bottom_border == true and $i >= ($total_cells - 7)) {
                $border = 'LRB';
            }

            $pdf->Cell($cell_width, 15, $data['label'], $border, 0, 'C', 1);
            $pdf->setTextColor(0, 0, 0); //Black

            $i++;
        }

        $pdf->Ln();

        return true;
    }

    public function Validate($ignore_warning = true)
    {
        Debug::Text('User ID: ' . $this->getUser() . ' DateStamp: ' . $this->getDateStamp(), __FILE__, __LINE__, __METHOD__, 10);

        $this->handleDayBoundary();

        $this->findUserDate();
        Debug::Text('User ID: ' . $this->getUser() . ' DateStamp: ' . $this->getDateStamp(), __FILE__, __LINE__, __METHOD__, 10);

        if ($this->getUser() === false) { //Use === so we still allow OPEN shifts (user_id=0)
            $this->Validator->isTRUE('user_id',
                false,
                TTi18n::gettext('Employee is not specified'));
        }

        //Check to make sure EnableOverwrite isn't enabled when editing an existing record.
        if ($this->isNew() == false and $this->getEnableOverwrite() == true) {
            Debug::Text('Overwrite enabled when editing existing record, disabling overwrite.', __FILE__, __LINE__, __METHOD__, 10);
            $this->setEnableOverwrite(false);
        }

        if ($this->getCompany() == false) {
            $this->Validator->isTrue('company_id',
                false,
                TTi18n::gettext('Company is invalid'));
        }

        if ($this->getDateStamp() == false) {
            Debug::Text('DateStamp is INVALID! ID: ' . $this->getDateStamp(), __FILE__, __LINE__, __METHOD__, 10);
            $this->Validator->isTrue('date_stamp',
                false,
                TTi18n::gettext('Date/Time is incorrect, or pay period does not exist for this date. Please create a pay period schedule and assign this employee to it if you have not done so already'));
        }

        if ($this->getDateStamp() != false and $this->getStartTime() == '') {
            $this->Validator->isTrue('start_time',
                false,
                TTi18n::gettext('In Time not specified'));
        }
        if ($this->getDateStamp() != false and $this->getEndTime() == '') {
            $this->Validator->isTrue('end_time',
                false,
                TTi18n::gettext('Out Time not specified'));
        }

        if ($this->getDeleted() == false and $this->getDateStamp() != false and is_object($this->getUserObject())) {
            if ($this->getUserObject()->getHireDate() != '' and TTDate::getBeginDayEpoch($this->getDateStamp()) < TTDate::getBeginDayEpoch($this->getUserObject()->getHireDate())) {
                $this->Validator->isTRUE('date_stamp',
                    false,
                    TTi18n::gettext('Shift is before employees hire date'));
            }

            if ($this->getUserObject()->getTerminationDate() != '' and TTDate::getEndDayEpoch($this->getDateStamp()) > TTDate::getEndDayEpoch($this->getUserObject()->getTerminationDate())) {
                $this->Validator->isTRUE('date_stamp',
                    false,
                    TTi18n::gettext('Shift is after employees termination date'));
            }

            if ($this->getStatus() == 20 and $this->getAbsencePolicyID() != false and ($this->getDateStamp() != false and $this->getUser() > 0)) {
                $pglf = TTNew('PolicyGroupListFactory');
                $pglf->getAPISearchByCompanyIdAndArrayCriteria($this->getUserObject()->getCompany(), array('user_id' => array($this->getUser()), 'absence_policy' => array($this->getAbsencePolicyID())));
                if ($pglf->getRecordCount() == 0) {
                    $this->Validator->isTRUE('absence_policy_id',
                        false,
                        TTi18n::gettext('This absence policy is not available for this employee'));
                }
            }
        }

        //Ignore conflicting time check when EnableOverwrite is set, as we will just be deleting any conflicting shift anyways.
        //Also ignore when setting OPEN shifts to allow for multiple.
        if ($this->getEnableOverwrite() == false and $this->getDeleted() == false and ($this->getDateStamp() != false and $this->getUser() > 0)) {
            $this->Validator->isTrue('start_time',
                !$this->isConflicting(), //Reverse the boolean.
                TTi18n::gettext('Conflicting start/end time, schedule already exists for this employee'));
        } else {
            Debug::text('Not checking for conflicts... DateStamp: ' . (int)$this->getDateStamp(), __FILE__, __LINE__, __METHOD__, 10);
        }

        if ($ignore_warning == false) {
            //Warn users if they are trying to insert schedules too far in the future.
            if ($this->getDateStamp() != false and $this->getDateStamp() > (time() + (86400 * 366))) {
                $this->Validator->Warning('date_stamp', TTi18n::gettext('Date is more than one year in the future'));
            }

            if ($this->getDateStamp() != false
                and is_object($this->getPayPeriodObject())
                and is_object($this->getPayPeriodObject()->getPayPeriodScheduleObject())
                and $this->getPayPeriodObject()->getPayPeriodScheduleObject()->getTimeSheetVerifyType() != 10
            ) {
                //Find out if timesheet is verified or not.
                $pptsvlf = TTnew('PayPeriodTimeSheetVerifyListFactory');
                $pptsvlf->getByPayPeriodIdAndUserId($this->getPayPeriod(), $this->getUser());
                if ($pptsvlf->getRecordCount() > 0) {
                    $this->Validator->Warning('date_stamp', TTi18n::gettext('Pay period is already verified, saving these changes will require it to be reverified'));
                }
            }
        }
        return true;
    }

    public function getUser()
    {
        if (isset($this->data['user_id'])) {
            return (int)$this->data['user_id'];
        }

        return false;
    }

    public function getDateStamp($raw = false)
    {
        if (isset($this->data['date_stamp'])) {
            if ($raw === true) {
                return $this->data['date_stamp'];
            } else {
                return TTDate::strtotime($this->data['date_stamp']);
            }
        }

        return false;
    }

    public function handleDayBoundary()
    {
        //Debug::Text('Start Time '.TTDate::getDate('DATE+TIME', $this->getStartTime()) .'('.$this->getStartTime().')  End Time: '. TTDate::getDate('DATE+TIME', $this->getEndTime()).'('.$this->getEndTime().')', __FILE__, __LINE__, __METHOD__, 10);

        //This used to be done in Validate, but needs to be done in preSave too.
        //Allow 12:00AM to 12:00AM schedules for a total of 24hrs.
        if ($this->getStartTime() != '' and $this->getEndTime() != '' and $this->getEndTime() <= $this->getStartTime()) {
            //Since the initial end time is the same date as the start time, we need to see if DST affects between that end time and one day later. NOT the start time.
            //Due to DST, always pay the employee based on the time they actually worked,
            //which is handled automatically by simple epoch math.
            //Therefore in fall they get paid one hour more, and spring one hour less.
            //$this->setEndTime( $this->getEndTime() + ( 86400 + (TTDate::getDSTOffset( $this->getEndTime(), ($this->getEndTime() + 86400) ) ) ) ); //End time spans midnight, add 24hrs.
            $this->setEndTime(strtotime('+1 day', $this->getEndTime())); //Using strtotime handles DST properly, whereas adding 86400 causes strange behavior.
            Debug::Text('EndTime spans midnight boundary! Bump to next day... New End Time: ' . TTDate::getDate('DATE+TIME', $this->getEndTime()) . '(' . $this->getEndTime() . ')', __FILE__, __LINE__, __METHOD__, 10);
        }

        return true;
    }

    public function setEndTime($epoch)
    {
        $epoch = (int)$epoch;

        if ($this->Validator->isDate('end_time',
            $epoch,
            TTi18n::gettext('Incorrect end time'))
        ) {
            $this->data['end_time'] = $epoch;

            return true;
        }

        return false;
    }

    public function findUserDate()
    {
        //Must allow user_id=0 for open shifts.

        /*
        This needs to be able to run before Validate is called, so we can validate the pay period schedule.
        */
        if ($this->getDateStamp() == false) {
            $this->setDateStamp($this->getStartTime());
        }

        //Debug::Text(' Finding User Date ID: '. TTDate::getDate('DATE+TIME', $this->getStartTime() ) .' User: '. $this->getUser(), __FILE__, __LINE__, __METHOD__, 10);
        if (is_object($this->getPayPeriodScheduleObject())) {
            $user_date_epoch = $this->getPayPeriodScheduleObject()->getShiftAssignedDate($this->getStartTime(), $this->getEndTime(), $this->getPayPeriodScheduleObject()->getShiftAssignedDay());
        } else {
            $user_date_epoch = $this->getStartTime();
        }

        if (isset($user_date_epoch) and $user_date_epoch > 0) {
            //Debug::Text('Found DateStamp: '. $user_date_epoch .' Based On: '. TTDate::getDate('DATE+TIME', $user_date_epoch ), __FILE__, __LINE__, __METHOD__, 10);

            return $this->setDateStamp($user_date_epoch);
        }

        Debug::Text('Not using timestamp only: ' . TTDate::getDate('DATE+TIME', $this->getStartTime()), __FILE__, __LINE__, __METHOD__, 10);
        return true;
    }

    public function setDateStamp($epoch)
    {
        $epoch = (int)$epoch;
        if ($epoch > 0) {
            $epoch = TTDate::getMiddleDayEpoch($epoch);
        }

        if ($this->Validator->isDate('date_stamp',
            $epoch,
            TTi18n::gettext('Incorrect date') . '(a)')
        ) {
            if ($epoch > 0) {
                if ($this->getDateStamp() !== $epoch and $this->getOldDateStamp() != $this->getDateStamp()) {
                    Debug::Text(' Setting Old DateStamp... Current Old DateStamp: ' . (int)$this->getOldDateStamp() . ' Current DateStamp: ' . (int)$this->getDateStamp(), __FILE__, __LINE__, __METHOD__, 10);
                    $this->setOldDateStamp($this->getDateStamp());
                }

                //Debug::Text(' Setting DateStamp to: '. (int)$epoch, __FILE__, __LINE__, __METHOD__, 10);
                $this->data['date_stamp'] = $epoch;

                $this->setPayPeriod(); //Force pay period to be set as soon as the date is.
                return true;
            } else {
                $this->Validator->isTRUE('date_stamp',
                    false,
                    TTi18n::gettext('Incorrect date') . '(b)');
            }
        }

        return false;
    }

    public function getOldDateStamp()
    {
        if (isset($this->tmp_data['old_date_stamp'])) {
            return $this->tmp_data['old_date_stamp'];
        }

        return false;
    }

    public function setOldDateStamp($date_stamp)
    {
        Debug::Text(' Setting Old DateStamp: ' . TTDate::getDate('DATE', $date_stamp), __FILE__, __LINE__, __METHOD__, 10);
        $this->tmp_data['old_date_stamp'] = TTDate::getMiddleDayEpoch($date_stamp);

        return true;
    }

    public function setPayPeriod($id = null)
    {
        $id = trim($id);

        if ($id == null and $this->getUser() > 0) { //Don't attempt to find pay period if user_id is not specified.
            $id = (int)PayPeriodListFactory::findPayPeriod($this->getUser(), $this->getDateStamp());
        }

        $pplf = TTnew('PayPeriodListFactory');

        //Allow NULL pay period, incase its an absence or something in the future.
        //Cron will fill in the pay period later.
        if (
            $id == 0
            or
            $this->Validator->isResultSetWithRows('pay_period',
                $pplf->getByID($id),
                TTi18n::gettext('Invalid Pay Period')
            )
        ) {
            $this->data['pay_period_id'] = $id;

            return true;
        }

        return false;
    }

    public function getPayPeriodScheduleObject()
    {
        if (is_object($this->pay_period_schedule_obj)) {
            return $this->pay_period_schedule_obj;
        } else {
            if ($this->getUser() > 0) {
                $ppslf = TTnew('PayPeriodScheduleListFactory');
                $ppslf->getByUserId($this->getUser());
                if ($ppslf->getRecordCount() == 1) {
                    $this->pay_period_schedule_obj = $ppslf->getCurrent();
                    return $this->pay_period_schedule_obj;
                }
            } elseif ($this->getUser() == 0 and $this->getCompany() > 0) {
                //OPEN SHIFT, try to find pay period schedule for the company
                $ppslf = TTnew('PayPeriodScheduleListFactory');
                $ppslf->getByCompanyId($this->getCompany());
                if ($ppslf->getRecordCount() == 1) {
                    Debug::Text('Using Company ID: ' . $this->getCompany(), __FILE__, __LINE__, __METHOD__, 10);
                    $this->pay_period_schedule_obj = $ppslf->getCurrent();
                    return $this->pay_period_schedule_obj;
                }
            }

            return false;
        }
    }

    public function getCompany()
    {
        if (isset($this->data['company_id'])) {
            return (int)$this->data['company_id'];
        }

        return false;
    }

    public function getEnableOverwrite()
    {
        if (isset($this->overwrite)) {
            return $this->overwrite;
        }

        return false;
    }

    public function setEnableOverwrite($bool)
    {
        $this->overwrite = $bool;

        return true;
    }

    public function getUserObject()
    {
        return $this->getGenericObject('UserListFactory', $this->getUser(), 'user_obj');
    }

    public function getStatus()
    {
        if (isset($this->data['status_id'])) {
            return (int)$this->data['status_id'];
        }

        return false;
    }

    public function isConflicting()
    {
        Debug::Text('User ID: ' . $this->getUser() . ' DateStamp: ' . $this->getDateStamp(), __FILE__, __LINE__, __METHOD__, 10);
        //Make sure we're not conflicting with any other schedule shifts.
        $slf = TTnew('ScheduleListFactory');
        $slf->getConflictingByUserIdAndStartDateAndEndDate($this->getUser(), $this->getStartTime(), $this->getEndTime(), (int)$this->getID());
        if ($slf->getRecordCount() > 0) {
            foreach ($slf as $conflicting_schedule_shift_obj) {
                if ($conflicting_schedule_shift_obj->isNew() === false
                    and $conflicting_schedule_shift_obj->getId() != $this->getId()
                ) {
                    Debug::text('Conflicting Schedule Shift ID: ' . $conflicting_schedule_shift_obj->getId() . ' Schedule Shift ID: ' . $this->getId(), __FILE__, __LINE__, __METHOD__, 10);
                    return true;
                }
            }
        }

        return false;
    }

    public function getPayPeriodObject()
    {
        return $this->getGenericObject('PayPeriodListFactory', $this->getPayPeriod(), 'pay_period_obj');
    }

    public function getPayPeriod()
    {
        if (isset($this->data['pay_period_id'])) {
            return (int)$this->data['pay_period_id'];
        }

        return false;
    }

    public function preSave()
    {
        if ($this->getSchedulePolicyID() === false) {
            $this->setSchedulePolicyID(0);
        }

        if ($this->getAbsencePolicyID() === false) {
            $this->setAbsencePolicyID(0);
        }

        if ($this->getBranch() === false) {
            $this->setBranch(0);
        }

        if ($this->getDepartment() === false) {
            $this->setDepartment(0);
        }

        if ($this->getJob() === false) {
            $this->setJob(0);
        }

        if ($this->getJobItem() === false) {
            $this->setJobItem(0);
        }

        $this->handleDayBoundary();
        $this->findUserDate();

        if ($this->getPayPeriod() == false) {
            $this->setPayPeriod();
        }

        if ($this->getTotalTime() == false) {
            $this->setTotalTime($this->calcTotalTime());
        }

        if ($this->getStatus() == 10) {
            $this->setAbsencePolicyID(null);
        } elseif ($this->getStatus() == false) {
            $this->setStatus(10); //Default to working.
        }

        if ($this->getEnableOverwrite() == true and $this->isNew() == true) {
            //Delete any conflicting schedule shift before saving.
            $slf = TTnew('ScheduleListFactory');
            $slf->getConflictingByUserIdAndStartDateAndEndDate($this->getUser(), $this->getStartTime(), $this->getEndTime());
            if ($slf->getRecordCount() > 0) {
                Debug::Text('Found Conflicting Shift!!', __FILE__, __LINE__, __METHOD__, 10);
                //Delete shifts.
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
        }

        //Since Add Request icon was added to Attendance -> Schedule, a user could request to fill a *committed* open shift, and once the request is authorized, that open shift will still be there.
        //The same thing could happen if adding a new shift that was identical to the OPEN shift just with an employee assigned to it.
        //  So instead of deleting or overwriting the original OPEN shift, simply set "replaced_id" of the current shift to the OPEN shift ID, so we know it was replaced and therefore won't be displayed anymore.
        //    Now if the shift is deleted, the original OPEN shift will reappear, just like what would happen if it was a OPEN recurring schedule.
        //However, there is still the case of the user editing an OPEN shift and simply changing the employee to someone else, in this case the original OPEN shift would not be preseverd.
        //  Also need to handle the case of filling an OPEN shift, then editing the filled shift to change the start/end times or branch/department/job/task, that should no longer fill the OPEN shift.
        // 		But if they are changed back, it should refill the shift, because this acts the most similar to existing recurring schedule open shifts.
        if ($this->getDeleted() == false and $this->Validator->getValidateOnly() == false and $this->getUser() > 0) { //Don't check for conflicting OPEN shifts when editing/saving an OPEN shift.
            $slf = TTnew('ScheduleListFactory');
            $slf->getConflictingOpenShiftSchedule($this->getCompany(), $this->getStartTime(), $this->getEndTime(), $this->getBranch(), $this->getDepartment(), $this->getJob(), $this->getJobItem(), $this->getReplacedId(), 1); //Limit 1;
            if ($slf->getRecordCount() > 0) {
                Debug::Text('Found Conflicting OPEN Shift!!', __FILE__, __LINE__, __METHOD__, 10);
                foreach ($slf as $s_obj) {
                    if ($s_obj->getUser() == 0) {
                        Debug::Text('Replacing Schedule OPEN Shift ID: ' . $s_obj->getId(), __FILE__, __LINE__, __METHOD__, 10);
                        $this->setReplacedId($s_obj->getId());
                    } else {
                        Debug::Text('ERROR: Returned conflicting shift that is not OPEN! ID: ' . $s_obj->getId(), __FILE__, __LINE__, __METHOD__, 10);
                    }
                }
            } else {
                Debug::Text('NO Conflicting OPEN Shift found...', __FILE__, __LINE__, __METHOD__, 10);
                $this->setReplacedId(0);
            }
        } elseif ($this->getUser() == 0) {
            $this->setReplacedId(0); //Force this whenever its an OPEN shift.
        }

        return true;
    }

    public function setSchedulePolicyID($id)
    {
        $id = trim($id);

        if ($id == false or $id == 0 or $id == '') {
            $id = 0;
        }

        $splf = TTnew('SchedulePolicyListFactory');

        if ($id == 0
            or
            $this->Validator->isResultSetWithRows('schedule_policy',
                $splf->getByID($id),
                TTi18n::gettext('Schedule Policy is invalid')
            )
        ) {
            $this->data['schedule_policy_id'] = $id;

            return true;
        }

        return false;
    }

    //Find the difference between $epoch and the schedule time, so we can determine the best schedule that fits.
    //**This returns FALSE when it doesn't match, so make sure you do an exact comparison using ===

    public function setAbsencePolicyID($id)
    {
        $id = trim($id);

        if ($id == false or $id == 0 or $id == '') {
            $id = 0;
        }

        $aplf = TTnew('AbsencePolicyListFactory');

        if ($id == 0
            or
            $this->Validator->isResultSetWithRows('absence_policy',
                $aplf->getByID($id),
                TTi18n::gettext('Invalid Absence Policy')
            )
        ) {
            $this->data['absence_policy_id'] = $id;

            return true;
        }

        return false;
    }

    public function setBranch($id)
    {
        $id = trim($id);

        if ($id == false or $id == 0 or $id == '') {
            $id = 0;
        }

        if ($this->getUser() != '' and is_object($this->getUserObject()) and $id == -1) { //Find default
            $id = $this->getUserObject()->getDefaultBranch();
            Debug::Text('Using Default Branch: ' . $id, __FILE__, __LINE__, __METHOD__, 10);
        }

        $blf = TTnew('BranchListFactory');

        if ($id == 0
            or
            $this->Validator->isResultSetWithRows('branch',
                $blf->getByID($id),
                TTi18n::gettext('Branch does not exist')
            )
        ) {
            $this->data['branch_id'] = $id;

            return true;
        }

        return false;
    }

    public function setDepartment($id)
    {
        $id = trim($id);

        if ($id == false or $id == 0 or $id == '') {
            $id = 0;
        }

        if ($this->getUser() != '' and is_object($this->getUserObject()) and $id == -1) { //Find default
            $id = $this->getUserObject()->getDefaultDepartment();
            Debug::Text('Using Default Department: ' . $id, __FILE__, __LINE__, __METHOD__, 10);
        }

        $dlf = TTnew('DepartmentListFactory');

        if ($id == 0
            or
            $this->Validator->isResultSetWithRows('department',
                $dlf->getByID($id),
                TTi18n::gettext('Department does not exist')
            )
        ) {
            $this->data['department_id'] = $id;

            return true;
        }

        return false;
    }

    public function getJob()
    {
        if (isset($this->data['job_id'])) {
            return (int)$this->data['job_id'];
        }

        return false;
    }

    public function setJob($id)
    {
        $id = trim($id);

        if ($id == false or $id == 0 or $id == '') {
            $id = 0;
        }

        if ($this->getUser() != '' and is_object($this->getUserObject()) and $id == -1) { //Find default
            $id = $this->getUserObject()->getDefaultJob();
            Debug::Text('Using Default Job: ' . $id, __FILE__, __LINE__, __METHOD__, 10);
        }

        $id = 0;


        if ($id == 0
            or
            $this->Validator->isResultSetWithRows('job',
                $jlf->getByID($id),
                TTi18n::gettext('Job does not exist')
            )
        ) {
            $this->data['job_id'] = $id;

            return true;
        }

        return false;
    }

    public function getJobItem()
    {
        if (isset($this->data['job_item_id'])) {
            return (int)$this->data['job_item_id'];
        }

        return false;
    }

    public function setJobItem($id)
    {
        $id = trim($id);

        if ($id == false or $id == 0 or $id == '') {
            $id = 0;
        }

        if ($this->getUser() != '' and is_object($this->getUserObject()) and $id == -1) { //Find default
            $id = $this->getUserObject()->getDefaultJobItem();
            Debug::Text('Using Default Job Item: ' . $id, __FILE__, __LINE__, __METHOD__, 10);
        }

        $id = 0;

        if ($id == 0
            or
            $this->Validator->isResultSetWithRows('job_item',
                $jilf->getByID($id),
                TTi18n::gettext('Task does not exist')
            )
        ) {
            $this->data['job_item_id'] = $id;

            return true;
        }

        return false;
    }

    public function getTotalTime()
    {
        if (isset($this->data['total_time'])) {
            return (int)$this->data['total_time'];
        }
        return false;
    }

    public function setTotalTime($int)
    {
        $int = (int)$int;

        if ($this->Validator->isNumeric('total_time',
            $int,
            TTi18n::gettext('Incorrect total time'))
        ) {
            $this->data['total_time'] = $int;

            return true;
        }

        return false;
    }

    public function calcTotalTime()
    {
        if ($this->getStartTime() > 0 and $this->getEndTime() > 0) {
            $total_time = $this->calcRawTotalTime();

            $total_time += $this->getMealPolicyDeductTime($total_time);
            $total_time += $this->getBreakPolicyDeductTime($total_time);

            return $total_time;
        }

        return false;
    }

    public function calcRawTotalTime()
    {
        if ($this->getStartTime() > 0 and $this->getEndTime() > 0) {
            //Due to DST, always pay the employee based on the time they actually worked,
            //which is handled automatically by simple epoch math.
            //Therefore in fall they get paid one hour more, and spring one hour less.
            $total_time = ($this->getEndTime() - $this->getStartTime()); // + TTDate::getDSTOffset( $this->getStartTime(), $this->getEndTime() );
            //Debug::Text('Start Time '.TTDate::getDate('DATE+TIME', $this->getStartTime()) .'('.$this->getStartTime().')  End Time: '. TTDate::getDate('DATE+TIME', $this->getEndTime()).'('.$this->getEndTime().') Total Time: '. TTDate::getHours( $total_time ), __FILE__, __LINE__, __METHOD__, 10);

            return $total_time;
        }

        return false;
    }

    public function getMealPolicyDeductTime($day_total_time, $filter_type_id = false)
    {
        $total_time = 0;

        $mplf = TTnew('MealPolicyListFactory');
        if (is_object($this->getSchedulePolicyObject()) and $this->getSchedulePolicyObject()->isUsePolicyGroupMealPolicy() == false) {
            $policy_group_meal_policy_ids = $this->getSchedulePolicyObject()->getMealPolicy();
            $mplf->getByIdAndCompanyId($policy_group_meal_policy_ids, $this->getCompany());
        } else {
            $mplf->getByPolicyGroupUserId($this->getUser());
        }

        //Debug::Text('Meal Policy Record Count: '. $mplf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
        if ($mplf->getRecordCount() > 0) {
            foreach ($mplf as $meal_policy_obj) {
                if (($filter_type_id == false and ($meal_policy_obj->getType() == 10 or $meal_policy_obj->getType() == 20))
                    or
                    ($filter_type_id == $meal_policy_obj->getType())
                ) {
                    if ($day_total_time > $meal_policy_obj->getTriggerTime()) {
                        $total_time = $meal_policy_obj->getAmount(); //Only consider a single meal policy per shift, so don't add here.
                    }
                }
            }
        }

        $total_time = ($total_time * -1);
        Debug::Text('Meal Policy Deduct Time: ' . $total_time, __FILE__, __LINE__, __METHOD__, 10);

        return $total_time;
    }

    public function getBreakPolicyDeductTime($day_total_time, $filter_type_id = false)
    {
        $total_time = 0;

        $bplf = TTnew('BreakPolicyListFactory');
        if (is_object($this->getSchedulePolicyObject()) and $this->getSchedulePolicyObject()->isUsePolicyGroupBreakPolicy() == false) {
            $policy_group_break_policy_ids = $this->getSchedulePolicyObject()->getBreakPolicy();
            $bplf->getByIdAndCompanyId($policy_group_break_policy_ids, $this->getCompany());
        } else {
            $bplf->getByPolicyGroupUserId($this->getUser());
        }

        //Debug::Text('Break Policy Record Count: '. $bplf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
        if ($bplf->getRecordCount() > 0) {
            foreach ($bplf as $break_policy_obj) {
                if (($filter_type_id == false and ($break_policy_obj->getType() == 10 or $break_policy_obj->getType() == 20))
                    or
                    ($filter_type_id == $break_policy_obj->getType())
                ) {
                    if ($day_total_time > $break_policy_obj->getTriggerTime()) {
                        $total_time += $break_policy_obj->getAmount();
                    }
                }
            }
        }

        $total_time = ($total_time * -1);
        Debug::Text('Break Policy Deduct Time: ' . $total_time, __FILE__, __LINE__, __METHOD__, 10);

        return $total_time;
    }

    public function setStatus($status)
    {
        $status = (int)$status;

        if ($this->Validator->inArrayKey('status',
            $status,
            TTi18n::gettext('Incorrect Status'),
            $this->getOptions('status'))
        ) {
            $this->data['status_id'] = $status;

            return true;
        }

        return false;
    }

    //Write all the schedules shifts for a given week.

    public function getReplacedId()
    {
        if (isset($this->data['replaced_id'])) {
            return (int)$this->data['replaced_id'];
        }

        return false;
    }

    //function getSchedule( $company_id, $user_ids, $start_date, $end_date, $start_week_day = 0, $group_schedule = FALSE ) {

    public function setReplacedId($id)
    {
        $id = trim($id);

        if ($id == false or $id == 0 or $id == '') {
            $id = 0;
        }

        $slf = TTnew('ScheduleListFactory');

        if ($id == 0
            or
            (
                $this->getID() != $id //Make sure we don't replace ourselves.
                and
                $this->Validator->isResultSetWithRows('date_stamp',
                    $slf->getByID($id),
                    TTi18n::gettext('Scheduled Shift to replace does not exist.')
                )
            )
        ) {
            $this->data['replaced_id'] = $id;

            return true;
        }

        return false;
    }

    public function postSave()
    {
        if ($this->getEnableTimeSheetVerificationCheck()) {
            //Check to see if schedule is verified, if so unverify it on modified punch.
            //Make sure exceptions are calculated *after* this so TimeSheet Not Verified exceptions can be triggered again.
            if ($this->getDateStamp() != false
                and is_object($this->getPayPeriodObject())
                and is_object($this->getPayPeriodObject()->getPayPeriodScheduleObject())
                and $this->getPayPeriodObject()->getPayPeriodScheduleObject()->getTimeSheetVerifyType() != 10
            ) {
                //Find out if timesheet is verified or not.
                $pptsvlf = TTnew('PayPeriodTimeSheetVerifyListFactory');
                $pptsvlf->getByPayPeriodIdAndUserId($this->getPayPeriod(), $this->getUser());
                if ($pptsvlf->getRecordCount() > 0) {
                    //Pay period is verified, delete all records and make log entry.
                    //These can be added during the maintenance jobs, so the audit records are recorded as user_id=0, check those first.
                    Debug::text('Pay Period is verified, deleting verification records: ' . $pptsvlf->getRecordCount() . ' User ID: ' . $this->getUser() . ' Pay Period ID: ' . $this->getPayPeriod(), __FILE__, __LINE__, __METHOD__, 10);
                    foreach ($pptsvlf as $pptsv_obj) {
                        TTLog::addEntry($pptsv_obj->getId(), 500, TTi18n::getText('Schedule Modified After Verification') . ': ' . UserListFactory::getFullNameById($this->getUser()) . ' ' . TTi18n::getText('Schedule') . ': ' . TTDate::getDate('DATE', $this->getStartTime()), null, $pptsvlf->getTable());
                        $pptsv_obj->setDeleted(true);
                        if ($pptsv_obj->isValid()) {
                            $pptsv_obj->Save();
                        }
                    }
                }
            }
        }

        if ($this->getEnableReCalculateDay() == true) {
            //Calculate total time. Mainly for docked.
            //Calculate entire week as Over Schedule (Weekly) OT policy needs to be reapplied if the schedule changes.
            if ($this->getDateStamp() != false and is_object($this->getUserObject())) {
                //When shifts are assigned to different days, we need to calculate both days the schedule touches, as the shift could be assigned to either of them.
                UserDateTotalFactory::reCalculateDay($this->getUserObject(), array($this->getDateStamp(), $this->getOldDateStamp(), $this->getStartTime(), $this->getEndTime()), true, false);
            }
        }

        return true;
    }

    public function getEnableTimeSheetVerificationCheck()
    {
        if (isset($this->timesheet_verification_check)) {
            return $this->timesheet_verification_check;
        }

        return false;
    }

    public function getEnableReCalculateDay()
    {
        if (isset($this->recalc_day)) {
            return $this->recalc_day;
        }

        return false;
    }

    public function setObjectFromArray($data)
    {
        if (is_array($data)) {
            /*
 *			//Use date_stamp is determined from StartTime and EndTime now automatically, due to schedules honoring the "assign shifts to" setting
            //We need to set the UserDate as soon as possible.
            //Consider mass editing shifts, where user_id is not sent but user_date_id is. We need to prevent the shifts from being assigned to the OPEN user.
            if ( isset($data['user_id']) AND ( $data['user_id'] !== '' AND $data['user_id'] !== FALSE )
                    AND isset($data['date_stamp']) AND $data['date_stamp'] != ''
                    AND isset($data['start_time']) AND $data['start_time'] != '' ) {
                Debug::text('Setting User Date ID based on User ID:'. $data['user_id'] .' Date Stamp: '. $data['date_stamp'] .' Start Time: '. $data['start_time'], __FILE__, __LINE__, __METHOD__, 10);
                $this->setUserDate( $data['user_id'], TTDate::parseDateTime( $data['date_stamp'].' '.$data['start_time'] ) );
            } elseif ( isset( $data['user_date_id'] ) AND $data['user_date_id'] >= 0 ) {
                Debug::text(' Setting UserDateID: '. $data['user_date_id'], __FILE__, __LINE__, __METHOD__, 10);
                $this->setUserDateID( $data['user_date_id'] );
            } else {
                Debug::text(' NOT CALLING setUserDate or setUserDateID!', __FILE__, __LINE__, __METHOD__, 10);
            }
*/
            if (isset($data['overwrite'])) {
                $this->setEnableOverwrite(true);
            }

            $variable_function_map = $this->getVariableToFunctionMap();
            foreach ($variable_function_map as $key => $function) {
                if (isset($data[$key])) {
                    $function = 'set' . $function;
                    switch ($key) {
                        case 'user_id':
                            //Make sure getUser() returns the proper user_id, otherwise mass edit will always assign shifts to OPEN employee.
                            //We have to make sure the 'user_id' function map is FALSE as well, so we don't get a SQL error when getting the empty record set.
                            $this->setUser($data[$key]);
                            break;
                        case 'user_date_id': //Ignore explicitly set user_date_id here as its set above.
                        case 'total_time': //If they try to specify total time, just skip it, as it gets calculated later anyways.
                            break;
                        case 'date_stamp':
                            $this->$function(TTDate::parseDateTime($data[$key]));
                            break;
                        case 'start_time':
                            if (method_exists($this, $function)) {
                                Debug::text('..Setting start time from EPOCH: "' . $data[$key] . '"', __FILE__, __LINE__, __METHOD__, 10);

                                if (isset($data['start_date_stamp']) and $data['start_date_stamp'] != '' and isset($data[$key]) and $data[$key] != '') {
                                    Debug::text(' aSetting start time... "' . $data['start_date_stamp'] . ' ' . $data[$key] . '"', __FILE__, __LINE__, __METHOD__, 10);
                                    $this->$function(TTDate::parseDateTime($data['start_date_stamp'] . ' ' . $data[$key])); //Prefix date_stamp onto start_time
                                } elseif (isset($data[$key]) and $data[$key] != '') {
                                    //When start_time is provided as a full timestamp. Happens with audit log detail.
                                    Debug::text(' aaSetting start time...: ' . $data[$key], __FILE__, __LINE__, __METHOD__, 10);
                                    $this->$function(TTDate::parseDateTime($data[$key]));
                                    //} elseif ( is_object( $this->getUserDateObject() ) ) {
                                    //	Debug::text(' aaaSetting start time...: '. $this->getUserDateObject()->getDateStamp(), __FILE__, __LINE__, __METHOD__, 10);
                                    //	$this->$function( TTDate::parseDateTime( TTDate::getDate('DATE', $this->getUserDateObject()->getDateStamp() ) .' '. $data[$key] ) );
                                } else {
                                    Debug::text(' Not setting start time...', __FILE__, __LINE__, __METHOD__, 10);
                                }
                            }
                            break;
                        case 'end_time':
                            if (method_exists($this, $function)) {
                                Debug::text('..xSetting end time from EPOCH: "' . $data[$key] . '"', __FILE__, __LINE__, __METHOD__, 10);

                                if (isset($data['start_date_stamp']) and $data['start_date_stamp'] != '' and isset($data[$key]) and $data[$key] != '') {
                                    Debug::text(' aSetting end time... "' . $data['start_date_stamp'] . ' ' . $data[$key] . '"', __FILE__, __LINE__, __METHOD__, 10);
                                    $this->$function(TTDate::parseDateTime($data['start_date_stamp'] . ' ' . $data[$key])); //Prefix date_stamp onto end_time
                                } elseif (isset($data[$key]) and $data[$key] != '') {
                                    Debug::text(' aaSetting end time...: ' . $data[$key], __FILE__, __LINE__, __METHOD__, 10);
                                    //When end_time is provided as a full timestamp. Happens with audit log detail.
                                    $this->$function(TTDate::parseDateTime($data[$key]));
                                    //} elseif ( is_object( $this->getUserDateObject() ) ) {
                                    //	Debug::text(' bbbSetting end time... "'. TTDate::getDate('DATE', $this->getUserDateObject()->getDateStamp() ) .' '. $data[$key]	 .'"', __FILE__, __LINE__, __METHOD__, 10);
                                    //	$this->$function( TTDate::parseDateTime( TTDate::getDate('DATE', $this->getUserDateObject()->getDateStamp() ) .' '. $data[$key] ) );
                                } else {
                                    Debug::text(' Not setting end time...', __FILE__, __LINE__, __METHOD__, 10);
                                }
                            }
                            break;
                        default:
                            if (method_exists($this, $function)) {
                                $this->$function($data[$key]);
                            }
                            break;
                    }
                }
            }

            $this->handleDayBoundary(); //Make sure we handle day boundary before calculating total time.
            $this->setTotalTime($this->calcTotalTime()); //Calculate total time immediately after. This is required for proper audit logging too.
            $this->setEnableReCalculateDay(true); //This is needed for Absence schedules to carry over to the timesheet.
            $this->setCreatedAndUpdatedColumns($data);

            return true;
        }

        return false;
    }

    public function setUser($id)
    {
        $id = trim($id);

        $ulf = TTnew('UserListFactory');

        //Need to be able to support user_id=0 for open shifts. But this can cause problems with importing punches with user_id=0.
        if ($id == 0
            or
            $this->Validator->isResultSetWithRows('user',
                $ulf->getByID($id),
                TTi18n::gettext('Invalid User')
            )
        ) {
            $this->data['user_id'] = $id;

            return true;
        }

        return false;
    }

    public function setEnableReCalculateDay($bool)
    {
        $this->recalc_day = $bool;

        return true;
    }

    public function getObjectAsArray($include_columns = null, $permission_children_ids = false)
    {
        $uf = TTnew('UserFactory');
        $data = array();
        $variable_function_map = $this->getVariableToFunctionMap();
        if (is_array($variable_function_map)) {
            foreach ($variable_function_map as $variable => $function_stub) {
                if ($include_columns == null or (isset($include_columns[$variable]) and $include_columns[$variable] == true)) {
                    $function = 'get' . $function_stub;
                    switch ($variable) {
                        case 'first_name':
                        case 'last_name':
                            if ($this->getColumn('user_id') > 0) {
                                $data[$variable] = $this->getColumn($variable);
                            } else {
                                $data[$variable] = TTi18n::getText('OPEN');
                            }
                            break;
                        case 'user_id':
                            //Make sure getUser() returns the proper user_id, otherwise mass edit will always assign shifts to OPEN employee.
                            //We have to make sure the 'user_id' function map is FALSE as well, so we don't get a SQL error when getting the empty record set.
                            $data[$variable] = $this->tmp_data['user_id'] = (int)$this->getColumn($variable);
                            break;
                        case 'user_status_id':
                        case 'group_id':
                        case 'title_id':
                        case 'default_branch_id':
                        case 'default_department_id':
                            $data[$variable] = (int)$this->getColumn($variable);
                            break;
                        case 'group':
                        case 'title':
                        case 'default_branch':
                        case 'default_department':
                        case 'schedule_policy':
                        case 'absence_policy':
                        case 'branch':
                        case 'department':
                        case 'job':
                        case 'job_item':
                            $data[$variable] = $this->getColumn($variable);
                            break;
                        case 'status':
                            $function = 'get' . $variable;
                            if (method_exists($this, $function)) {
                                $data[$variable] = Option::getByKey($this->$function(), $this->getOptions($variable));
                            }
                            break;
                        case 'user_status':
                            $data[$variable] = Option::getByKey((int)$this->getColumn('user_status_id'), $uf->getOptions('status'));
                            break;
                        case 'date_stamp':
                            $data[$variable] = TTDate::getAPIDate('DATE', $this->getDateStamp());
                            break;
                        case 'start_date_stamp':
                            $data[$variable] = TTDate::getAPIDate('DATE', $this->getStartTime()); //Include both date+time
                            break;
                        case 'start_date':
                            $data[$variable] = TTDate::getAPIDate('DATE+TIME', $this->getStartTime()); //Include both date+time
                            break;
                        case 'end_date':
                            $data[$variable] = TTDate::getAPIDate('DATE+TIME', $this->getEndTime()); //Include both date+time
                            break;
                        case 'start_time_stamp':
                            $data[$variable] = $this->getStartTime(); //Include start date/time in epoch format for sorting...
                            break;
                        case 'end_time_stamp':
                            $data[$variable] = $this->getEndTime(); //Include end date/time in epoch format for sorting...
                            break;
                        case 'start_time':
                        case 'end_time':
                            if (method_exists($this, $function)) {
                                $data[$variable] = TTDate::getAPIDate('TIME', $this->$function()); //Just include time, so Mass Edit sees similar times without dates
                            }
                            break;
                        default:
                            if (method_exists($this, $function)) {
                                $data[$variable] = $this->$function();
                            }
                            break;
                    }
                }
            }
            $this->getPermissionColumns($data, $this->getColumn('user_id'), $this->getCreatedBy(), $permission_children_ids, $include_columns);
            $this->getCreatedAndUpdatedColumns($data, $include_columns);
        }

        return $data;
    }

    public function addLog($log_action)
    {
        return TTLog::addEntry($this->getId(), $log_action, TTi18n::getText('Schedule - Employee') . ': ' . UserListFactory::getFullNameById($this->getUser()) . ' ' . TTi18n::getText('Start Time') . ': ' . TTDate::getDate('DATE+TIME', $this->getStartTime()) . ' ' . TTi18n::getText('End Time') . ': ' . TTDate::getDate('DATE+TIME', $this->getEndTime()), null, $this->getTable(), $this);
    }
}
