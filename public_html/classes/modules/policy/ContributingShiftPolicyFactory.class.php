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
 * @package Modules\Policy
 */
class ContributingShiftPolicyFactory extends Factory
{
    protected $table = 'contributing_shift_policy';
    protected $pk_sequence_name = 'contributing_shift_policy_id_seq'; //PK Sequence name

    protected $company_obj = null;
    protected $contributing_time_policy_obj = null;
    protected $branch_map = null;
    protected $department_map = null;
    protected $job_group_map = null;
    protected $job_map = null;
    protected $job_item_group_map = null;
    protected $job_item_map = null;

    public function _getFactoryOptions($name, $parent = null)
    {
        $retval = null;
        switch ($name) {
            case 'include_schedule_shift_type':
                $retval = array(
                    10 => TTi18n::gettext('Schedules have no effect'),
                    20 => TTi18n::gettext('Only Scheduled Shifts'),
                    30 => TTi18n::gettext('Never Scheduled Shifts'),
                );
                break;

            //alter table contributing_shift_policy  add column include_shift_type_id integer DEFAULT 100;
            case 'include_shift_type':
                $retval = array(
                    //If shift meets below criteria, only the part that meets it is included.
                    100 => TTi18n::gettext('Split Shift (Partial)'), //Splits the worked time to the filter Start/End time.
                    //110 => TTi18n::gettext('Partial Shift (Shift Must Start)'), //Normal Punch In between Start/End Time
                    //120 => TTi18n::gettext('Partial Shift (Shift Must End)'), //Normal Punch Out between Start/End Time
                    //130 => TTi18n::gettext('Partial Shift (Majority of Shift)'), //Majority of shift falls between Start/End time


                    //If shift meets below criteria, the entire shift is included.
                    200 => TTi18n::gettext('Full Shift (Must Start & End)'), //Does not split worked time to the Start/End time. Full shift must fall within filter times.
                    210 => TTi18n::gettext('Full Shift (Must Start)'), //Normal Punch In between filter Start/End Time
                    220 => TTi18n::gettext('Full Shift (Must End)'), //Normal Punch Out between filter Start/End Time
                    230 => TTi18n::gettext('Full Shift (Majority of Shift)'), //Majority of shift falls between filter Start/End time. Tie breaker (50/50%) goes to start time.
                    //232 => TTi18n::gettext('Full Shift (Majority of Shift [Start])'), //Majority of shift falls between Start/End time. Using Start time as tie breaker.
                    //234 => TTi18n::gettext('Full Shift (Majority of Shift [End])'), //Majority of shift falls between Start/End time. Using End time as tie breaker.


                    //FIXME: In future, perhaps add types to be based on the schedule time, not the worked time.
                    //Differential is paid on what they work, but determined (rate of pay) by what they were supposed to work (schedule).
                );

                break;
            case 'include_holiday_type':
                $retval = array(
                    10 => TTi18n::gettext('Have no effect'),
                    20 => TTi18n::gettext('Always on Holidays'), //Eligible or not.
                    25 => TTi18n::gettext('Always on Eligible Holidays'), //Only Eligible
                    30 => TTi18n::gettext('Never on Holidays'),
                );
                break;
            case 'branch_selection_type':
                $retval = array(
                    10 => TTi18n::gettext('All Branches'),
                    20 => TTi18n::gettext('Only Selected Branches'),
                    30 => TTi18n::gettext('All Except Selected Branches'),
                );
                break;
            case 'department_selection_type':
                $retval = array(
                    10 => TTi18n::gettext('All Departments'),
                    20 => TTi18n::gettext('Only Selected Departments'),
                    30 => TTi18n::gettext('All Except Selected Departments'),
                );
                break;
            case 'job_group_selection_type':
                $retval = array(
                    10 => TTi18n::gettext('All Job Groups'),
                    20 => TTi18n::gettext('Only Selected Job Groups'),
                    30 => TTi18n::gettext('All Except Selected Job Groups'),
                );
                break;
            case 'job_selection_type':
                $retval = array(
                    10 => TTi18n::gettext('All Jobs'),
                    20 => TTi18n::gettext('Only Selected Jobs'),
                    30 => TTi18n::gettext('All Except Selected Jobs'),
                );
                break;
            case 'job_item_group_selection_type':
                $retval = array(
                    10 => TTi18n::gettext('All Task Groups'),
                    20 => TTi18n::gettext('Only Selected Task Groups'),
                    30 => TTi18n::gettext('All Except Selected Task Groups'),
                );
                break;
            case 'job_item_selection_type':
                $retval = array(
                    10 => TTi18n::gettext('All Tasks'),
                    20 => TTi18n::gettext('Only Selected Tasks'),
                    30 => TTi18n::gettext('All Except Selected Tasks'),
                );
                break;
            case 'columns':
                $retval = array(
                    '-1010-name' => TTi18n::gettext('Name'),
                    '-1020-description' => TTi18n::gettext('Description'),

                    '-1900-in_use' => TTi18n::gettext('In Use'),

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
                    'name',
                    'description',
                    'updated_date',
                    'updated_by',
                );
                break;
            case 'unique_columns': //Columns that are unique, and disabled for mass editing.
                $retval = array(
                    'name',
                );
                break;
            case 'linked_columns': //Columns that are linked together, mainly for Mass Edit, if one changes, they all must.
                $retval = array();
                break;

        }

        return $retval;
    }

    public function _getVariableToFunctionMap($data)
    {
        $variable_function_map = array(
            'id' => 'ID',
            'company_id' => 'Company',
            'name' => 'Name',
            'description' => 'Description',

            'contributing_pay_code_policy_id' => 'ContributingPayCodePolicy',

            'filter_start_date' => 'FilterStartDate',
            'filter_end_date' => 'FilterEndDate',
            'filter_start_time' => 'FilterStartTime',
            'filter_end_time' => 'FilterEndTime',
            'filter_minimum_time' => 'FilterMinimumTime',
            'filter_maximum_time' => 'FilterMaximumTime',
            'include_shift_type_id' => 'IncludeShiftType',

            'branch' => 'Branch',
            'branch_selection_type_id' => 'BranchSelectionType',
            'branch_selection_type' => false,
            'exclude_default_branch' => 'ExcludeDefaultBranch',
            'department' => 'Department',
            'department_selection_type_id' => 'DepartmentSelectionType',
            'department_selection_type' => false,
            'exclude_default_department' => 'ExcludeDefaultDepartment',
            'job_group' => 'JobGroup',
            'job_group_selection_type_id' => 'JobGroupSelectionType',
            'job_group_selection_type' => false,
            'job' => 'Job',
            'job_selection_type_id' => 'JobSelectionType',
            'job_selection_type' => false,
            'exclude_default_job' => 'ExcludeDefaultJob',
            'job_item_group' => 'JobItemGroup',
            'job_item_group_selection_type_id' => 'JobItemGroupSelectionType',
            'job_item_group_selection_type' => false,
            'job_item' => 'JobItem',
            'job_item_selection_type_id' => 'JobItemSelectionType',
            'job_item_selection_type' => false,
            'exclude_default_job_item' => 'ExcludeDefaultJobItem',

            'sun' => 'Sun',
            'mon' => 'Mon',
            'tue' => 'Tue',
            'wed' => 'Wed',
            'thu' => 'Thu',
            'fri' => 'Fri',
            'sat' => 'Sat',

            'include_holiday_type_id' => 'IncludeHolidayType',
            'holiday_policy' => 'HolidayPolicy',

            'in_use' => false,
            'deleted' => 'Deleted',
        );
        return $variable_function_map;
    }

    public function getCompanyObject()
    {
        return $this->getGenericObject('CompanyListFactory', $this->getCompany(), 'company_obj');
    }

    public function getCompany()
    {
        if (isset($this->data['company_id'])) {
            return (int)$this->data['company_id'];
        }

        return false;
    }

    public function getContributingPayCodePolicyObject()
    {
        return $this->getGenericObject('ContributingPayCodePolicyListFactory', $this->getContributingPayCodePolicy(), 'contributing_pay_code_policy_obj');
    }

    public function getContributingPayCodePolicy()
    {
        if (isset($this->data['contributing_pay_code_policy_id'])) {
            return (int)$this->data['contributing_pay_code_policy_id'];
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

    public function setName($name)
    {
        $name = trim($name);
        if ($this->Validator->isLength('name',
                $name,
                TTi18n::gettext('Name is too short or too long'),
                2, 75)
            and
            $this->Validator->isTrue('name',
                $this->isUniqueName($name),
                TTi18n::gettext('Name is already in use'))
        ) {
            $this->data['name'] = $name;

            return true;
        }

        return false;
    }

    public function isUniqueName($name)
    {
        $name = trim($name);
        if ($name == '') {
            return false;
        }

        $ph = array(
            'company_id' => (int)$this->getCompany(),
            'name' => TTi18n::strtolower($name),
        );

        $query = 'select id from ' . $this->getTable() . ' where company_id = ? AND lower(name) = ? AND deleted=0';
        $id = $this->db->GetOne($query, $ph);
        Debug::Arr($id, 'Unique: ' . $name, __FILE__, __LINE__, __METHOD__, 10);

        if ($id === false) {
            return true;
        } else {
            if ($id == $this->getId()) {
                return true;
            }
        }

        return false;
    }

    public function getDescription()
    {
        if (isset($this->data['description'])) {
            return $this->data['description'];
        }

        return false;
    }

    public function setDescription($description)
    {
        $description = trim($description);

        if ($description == ''
            or $this->Validator->isLength('description',
                $description,
                TTi18n::gettext('Description is invalid'),
                1, 250)
        ) {
            $this->data['description'] = $description;

            return true;
        }

        return false;
    }

    public function setContributingPayCodePolicy($id)
    {
        $id = trim($id);

        $cpcplf = TTnew('ContributingPayCodePolicyListFactory');

        if (
        $this->Validator->isResultSetWithRows('contributing_pay_code_policy_id',
            $cpcplf->getByID($id),
            TTi18n::gettext('Contributing Pay Code Policy is invalid')
        )
        ) {
            $this->data['contributing_pay_code_policy_id'] = $id;

            return true;
        }

        return false;
    }

    public function setFilterStartDate($epoch)
    {
        $epoch = (!is_int($epoch)) ? trim($epoch) : $epoch; //Dont trim integer values, as it changes them to strings.

        if ($epoch == '') {
            $epoch = null;
        }

        if (
            $epoch == null
            or
            $this->Validator->isDate('filter_start_date',
                $epoch,
                TTi18n::gettext('Incorrect start date'))
        ) {
            $this->data['filter_start_date'] = $epoch;

            return true;
        }

        return false;
    }

    public function setFilterEndDate($epoch)
    {
        $epoch = (!is_int($epoch)) ? trim($epoch) : $epoch; //Dont trim integer values, as it changes them to strings.

        if ($epoch == '') {
            $epoch = null;
        }

        if ($epoch == null
            or
            $this->Validator->isDate('filter_end_date',
                $epoch,
                TTi18n::gettext('Incorrect end date'))
        ) {
            $this->data['filter_end_date'] = $epoch;

            return true;
        }

        return false;
    }

    public function setFilterStartTime($epoch)
    {
        $epoch = (!is_int($epoch)) ? trim($epoch) : $epoch; //Dont trim integer values, as it changes them to strings.

        if ($epoch == ''
            or
            $this->Validator->isDate('filter_start_time',
                $epoch,
                TTi18n::gettext('Incorrect Start time'))
        ) {
            $this->data['filter_start_time'] = $epoch;

            return true;
        }

        return false;
    }

    public function setFilterEndTime($epoch)
    {
        $epoch = (!is_int($epoch)) ? trim($epoch) : $epoch; //Dont trim integer values, as it changes them to strings.

        if ($epoch == ''
            or
            $this->Validator->isDate('filter_end_time',
                $epoch,
                TTi18n::gettext('Incorrect End time'))
        ) {
            $this->data['filter_end_time'] = $epoch;

            return true;
        }

        return false;
    }

    public function getFilterMinimumTime()
    {
        if (isset($this->data['filter_minimum_time'])) {
            return (int)$this->data['filter_minimum_time'];
        }

        return false;
    }

    public function setFilterMinimumTime($int)
    {
        $int = trim($int);

        if (empty($int)) {
            $int = 0;
        }

        if ($this->Validator->isNumeric('filter_minimum_time',
            $int,
            TTi18n::gettext('Incorrect Minimum Time'))
        ) {
            $this->data['filter_minimum_time'] = $int;

            return true;
        }

        return false;
    }

    public function getFilterMaximumTime()
    {
        if (isset($this->data['filter_maximum_time'])) {
            return (int)$this->data['filter_maximum_time'];
        }

        return false;
    }

    public function setFilterMaximumTime($int)
    {
        $int = trim($int);

        if (empty($int)) {
            $int = 0;
        }

        if ($this->Validator->isNumeric('filter_maximum_time',
            $int,
            TTi18n::gettext('Incorrect Maximum Time'))
        ) {
            $this->data['filter_maximum_time'] = $int;

            return true;
        }

        return false;
    }

    public function setIncludeShiftType($value)
    {
        $value = trim($value);

        if ($this->Validator->inArrayKey('include_shift_type_id',
            $value,
            TTi18n::gettext('Incorrect Shift Type'),
            $this->getOptions('include_shift_type'))
        ) {
            $this->data['include_shift_type_id'] = $value;

            return true;
        }

        return false;
    }

    public function setExcludeDefaultBranch($bool)
    {
        $this->data['exclude_default_branch'] = $this->toBool($bool);

        return true;
    }

    public function setBranch($ids)
    {
        Debug::text('Setting Branch IDs : ', __FILE__, __LINE__, __METHOD__, 10);
        return CompanyGenericMapFactory::setMapIDs($this->getCompany(), 610, $this->getID(), (array)$ids);
    }

    public function setExcludeDefaultDepartment($bool)
    {
        $this->data['exclude_default_department'] = $this->toBool($bool);

        return true;
    }

    public function setDepartment($ids)
    {
        Debug::text('Setting Department IDs : ', __FILE__, __LINE__, __METHOD__, 10);
        return CompanyGenericMapFactory::setMapIDs($this->getCompany(), 620, $this->getID(), (array)$ids);
    }

    public function setJobGroup($ids)
    {
        Debug::text('Setting Job Group IDs : ', __FILE__, __LINE__, __METHOD__, 10);
        return CompanyGenericMapFactory::setMapIDs($this->getCompany(), 640, $this->getID(), (array)$ids);
    }

    public function setJob($ids)
    {
        Debug::text('Setting Job IDs : ', __FILE__, __LINE__, __METHOD__, 10);
        return CompanyGenericMapFactory::setMapIDs($this->getCompany(), 630, $this->getID(), (array)$ids);
    }

    /*

    Branch/Department/Job/Task filter functions

    */

    public function setExcludeDefaultJob($bool)
    {
        $this->data['exclude_default_job'] = $this->toBool($bool);

        return true;
    }

    public function setJobItemGroup($ids)
    {
        Debug::text('Setting Task Group IDs : ', __FILE__, __LINE__, __METHOD__, 10);
        return CompanyGenericMapFactory::setMapIDs($this->getCompany(), 660, $this->getID(), (array)$ids);
    }

    public function setJobItem($ids)
    {
        Debug::text('Setting Task IDs : ', __FILE__, __LINE__, __METHOD__, 10);
        return CompanyGenericMapFactory::setMapIDs($this->getCompany(), 650, $this->getID(), (array)$ids);
    }

    public function setExcludeDefaultJobItem($bool)
    {
        $this->data['exclude_default_job_item'] = $this->toBool($bool);

        return true;
    }

    public function setSun($bool)
    {
        $this->data['sun'] = $this->toBool($bool);

        return true;
    }

    public function setMon($bool)
    {
        $this->data['mon'] = $this->toBool($bool);

        return true;
    }

    public function setTue($bool)
    {
        $this->data['tue'] = $this->toBool($bool);

        return true;
    }

    public function setWed($bool)
    {
        $this->data['wed'] = $this->toBool($bool);

        return true;
    }

    public function setThu($bool)
    {
        $this->data['thu'] = $this->toBool($bool);

        return true;
    }

    public function setFri($bool)
    {
        $this->data['fri'] = $this->toBool($bool);

        return true;
    }

    public function setSat($bool)
    {
        $this->data['sat'] = $this->toBool($bool);

        return true;
    }

    public function getIncludeScheduleShiftType()
    {
        if (isset($this->data['include_schedule_shift_type_id'])) {
            return (int)$this->data['include_schedule_shift_type_id'];
        }

        return false;
    }

    public function setIncludeScheduleShiftType($value)
    {
        $value = trim($value);

        if ($this->Validator->inArrayKey('include_schedule_shift_type_id',
            $value,
            TTi18n::gettext('Incorrect Include Schedule Shift Type'),
            $this->getOptions('include_schedule_shift_type'))
        ) {
            $this->data['include_schedule_shift_type_id'] = $value;

            return true;
        }

        return false;
    }

    public function setIncludeHolidayType($value)
    {
        $value = trim($value);

        if ($this->Validator->inArrayKey('include_holiday_type_id',
            $value,
            TTi18n::gettext('Incorrect Include Holiday Type'),
            $this->getOptions('include_holiday_type'))
        ) {
            $this->data['include_holiday_type_id'] = $value;

            return true;
        }

        return false;
    }

    public function setHolidayPolicy($ids)
    {
        Debug::text('Setting Holiday Policy IDs : ', __FILE__, __LINE__, __METHOD__, 10);
        return CompanyGenericMapFactory::setMapIDs($this->getCompany(), 690, $this->getID(), (array)$ids);
    }

    public function isTimeRestricted()
    {
        //If time restrictions account for over 23.5 hours, then we assume
        //that this policy is not time restricted at all.
        //The above is flawed, as a time restriction of 6AM to 6AM the next day is perfectly valid.
        if ($this->getFilterStartTime() != '' and $this->getFilterEndTime() != '') {
            Debug::text('IS time restricted...', __FILE__, __LINE__, __METHOD__, 10);
            return true;
        }

        Debug::text('NOT time restricted...Filter Start Time: ' . TTDate::getDate('DATE+TIME', $this->getFilterStartTime()) . ' End Time: ' . TTDate::getDate('DATE+TIME', $this->getFilterEndTime()), __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function getFilterStartTime($raw = false)
    {
        if (isset($this->data['filter_start_time'])) {
            if ($raw === true) {
                return $this->data['filter_start_time'];
            } else {
                return TTDate::strtotime($this->data['filter_start_time']);
            }
        }

        return false;
    }

    public function getFilterEndTime($raw = false)
    {
        if (isset($this->data['filter_end_time'])) {
            if ($raw === true) {
                return $this->data['filter_end_time'];
            } else {
                return TTDate::strtotime($this->data['filter_end_time']);
            }
        }

        return false;
    }

    public function calculateShiftDataOverlapFilterTime($filter_start_time_stamp, $filter_end_time_stamp, $shift_data, $calculate_policy_obj = null)
    {
        if (is_array($shift_data)) {
            if (isset($shift_data['user_date_total_keys'])) {
                foreach ($shift_data['user_date_total_keys'] as $udt_key) {
                    if (isset($calculate_policy_obj->user_date_total[$udt_key]) and is_object($calculate_policy_obj->user_date_total[$udt_key])) {
                        $udt_obj = $calculate_policy_obj->user_date_total[$udt_key];

                        //Debug::Text(' UDT Start: '. TTDate::getDate('DATE+TIME', $udt_obj->getStartTimeStamp() ) .' End: '. TTDate::getDate('DATE+TIME', $udt_obj->getEndTimeStamp() ) .' Filter: Start: '. TTDate::getDate('DATE+TIME', $filter_start_time_stamp ) .' End: '. TTDate::getDate('DATE+TIME', $filter_end_time_stamp ), __FILE__, __LINE__, __METHOD__, 10);
                        $time_overlap_arr = TTDate::getTimeOverLap($udt_obj->getStartTimeStamp(), $udt_obj->getEndTimeStamp(), $filter_start_time_stamp, $filter_end_time_stamp);
                        if (is_array($time_overlap_arr)) {
                            $time_overlap = ($time_overlap_arr['end_date'] - $time_overlap_arr['start_date']);
                            if (!isset($shift_data['total_time_filter_overlap'])) {
                                $shift_data['total_time_filter_overlap'] = 0;
                            }
                            $shift_data['total_time_filter_overlap'] += $time_overlap;
                        }
                    }
                }
            }

            return $shift_data;
        }

        return false;
    }

    public function isActiveFilterTime($in_epoch, $out_epoch, $udt_key = null, $shift_data = null, $calculate_policy_obj = null)
    {
        //Debug::text(' Checking for Active Time with: In: '. TTDate::getDate('DATE+TIME', $in_epoch) .' Out: '. TTDate::getDate('DATE+TIME', $out_epoch), __FILE__, __LINE__, __METHOD__, 10);
        if ($in_epoch == '' or $out_epoch == '') {
            //Debug::text(' Empty time stamps, returning TRUE.', __FILE__, __LINE__, __METHOD__, 10);
            return true;
        }

        //Debug::text(' PP Raw Start TimeStamp('.$this->getFilterStartTime(TRUE).'): '. TTDate::getDate('DATE+TIME', $this->getFilterStartTime() ) .' Raw End TimeStamp: '. TTDate::getDate('DATE+TIME', $this->getFilterEndTime() ), __FILE__, __LINE__, __METHOD__, 10);
        $start_time_stamp = TTDate::getTimeLockedDate($this->getFilterStartTime(), $in_epoch); //Base the end time on day of the in_epoch.
        $end_time_stamp = TTDate::getTimeLockedDate($this->getFilterEndTime(), $in_epoch); //Base the end time on day of the in_epoch.

        //Check if end timestamp is before start, if it is, move end timestamp to next day.
        if ($end_time_stamp < $start_time_stamp) {
            Debug::text(' Moving End TimeStamp to next day.', __FILE__, __LINE__, __METHOD__, 10);
            $end_time_stamp = TTDate::getTimeLockedDate($this->getFilterEndTime(), (TTDate::getMiddleDayEpoch($end_time_stamp) + 86400)); //Due to DST, jump ahead 1.5 days, then jump back to the time locked date.
        }

        //Debug::text(' Start TimeStamp: '. TTDate::getDate('DATE+TIME', $start_time_stamp) .' End TimeStamp: '. TTDate::getDate('DATE+TIME', $end_time_stamp), __FILE__, __LINE__, __METHOD__, 10);
        //Check to see if start/end time stamps are not set or are equal, we always return TRUE if they are.
        if ($this->getIncludeHolidayType() == 10
            and ($start_time_stamp == '' or $end_time_stamp == '' or $start_time_stamp == $end_time_stamp)
        ) {
            //Debug::text(' Start/End time not set, assume it always matches.', __FILE__, __LINE__, __METHOD__, 10);
            return true;
        } else {
            //If the premium policy start/end time spans midnight, there could be multiple windows to check
            //where the premium policy applies, make sure we check all windows.
            for ($i = (TTDate::getMiddleDayEpoch($start_time_stamp) - 86400); $i <= (TTDate::getMiddleDayEpoch($end_time_stamp) + 86400); $i += 86400) {
                $tmp_start_time_stamp = TTDate::getTimeLockedDate($this->getFilterStartTime(), $i);
                $next_i = ($tmp_start_time_stamp + ($end_time_stamp - $start_time_stamp)); //Get next date to base the end_time_stamp on, and to calculate if we need to adjust for DST.
                $tmp_end_time_stamp = TTDate::getTimeLockedDate($end_time_stamp, ($next_i + (TTDate::getDSTOffset($tmp_start_time_stamp, $next_i) * -1))); //Use $end_time_stamp as it can be modified above due to being near midnight. Also adjust for DST by reversing it.
                if ($this->isActive($tmp_start_time_stamp, $tmp_start_time_stamp, $tmp_end_time_stamp, $udt_key, $shift_data, $calculate_policy_obj) == true) {
                    Debug::text(' Checking against Start TimeStamp: ' . TTDate::getDate('DATE+TIME', $tmp_start_time_stamp) . '(' . $tmp_start_time_stamp . ') End TimeStamp: ' . TTDate::getDate('DATE+TIME', $tmp_end_time_stamp) . '(' . $tmp_end_time_stamp . ')', __FILE__, __LINE__, __METHOD__, 10);
                    if ($this->getIncludeShiftType() == 100 and TTDate::isTimeOverLap($in_epoch, $out_epoch, $tmp_start_time_stamp, $tmp_end_time_stamp) == true) { //100=Partial Shift
                        //When dealing with partial punches, any overlap whatsoever activates the policy.
                        Debug::text(' Partial Punch Within Active Time!', __FILE__, __LINE__, __METHOD__, 10);
                        return true;
                    } elseif ($this->getIncludeShiftType() == 200 and $in_epoch >= $tmp_start_time_stamp and $out_epoch <= $tmp_end_time_stamp
                        and $this->isActiveDayOfWeekOrHoliday($tmp_start_time_stamp) and $this->isActiveDayOfWeekOrHoliday($tmp_end_time_stamp)
                    ) { //200=Full Shift (Must Start & End)
                        //Non partial punches, they must punch in AND out (entire shift) within the time window.
                        Debug::text(' Within Active Time!', __FILE__, __LINE__, __METHOD__, 10);
                        return true;
                    } elseif (($start_time_stamp == '' or $end_time_stamp == '' or $start_time_stamp == $end_time_stamp)) { //Must go AFTER the above IF statements.
                        //When IncludeHolidayType != 10 this trigger here.
                        Debug::text(' No Start/End Date/Time!', __FILE__, __LINE__, __METHOD__, 10);
                        return true;
                    }
//					else {
//						Debug::text( ' No match...', __FILE__, __LINE__, __METHOD__, 10 );
//					}
                } else {
                    Debug::text(' Not Active on this day: Start: ' . TTDate::getDate('DATE+TIME', $tmp_start_time_stamp) . ' End: ' . TTDate::getDate('DATE+TIME', $tmp_end_time_stamp), __FILE__, __LINE__, __METHOD__, 10);
                }
            }
        }

        Debug::text(' NOT Within Active Time!', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function getIncludeHolidayType()
    {
        if (isset($this->data['include_holiday_type_id'])) {
            return (int)$this->data['include_holiday_type_id'];
        }

        return false;
    }

    public function isActive($date_epoch, $in_epoch = null, $out_epoch = null, $udt_key = null, $shift_data = null, $calculate_policy_obj = null)
    {
        //Debug::text(' Date Epoch: '. $date_epoch .' In: '. $in_epoch .' Out: '. $out_epoch, __FILE__, __LINE__, __METHOD__, 10);
        //Make sure date_epoch is always specified so we can still determine isActive even if in_epoch/out_epoch are not specified themselves.
        if ($date_epoch == '' and $in_epoch == '') {
            Debug::text(' ERROR: Date/In epoch not specified...', __FILE__, __LINE__, __METHOD__, 10);
            return false;
        }

        //If we're including Full Shift types, try to use the shift start/end time rather than just the start/end time of the UDT record.
        //Otherwise a shift that spans midnight with daily overtime (being in the next day only) and evening premium set to only include the first day, the premium won't be calculated as it won't match the date.
        if ($udt_key != '' and $this->getIncludeShiftType() >= 200 and isset($shift_data['user_date_total_key_map'][$udt_key])) {
            $udt_shift_data = (isset($shift_data['user_date_total_key_map'][$udt_key])) ? $shift_data[$shift_data['user_date_total_key_map'][$udt_key]] : false;
            if (is_array($udt_shift_data) and isset($udt_shift_data['first_in']) and isset($udt_shift_data['last_out'])) {
                $date_epoch = $calculate_policy_obj->user_date_total[$udt_shift_data['first_in']]->getDateStamp();
                $in_epoch = $calculate_policy_obj->user_date_total[$udt_shift_data['first_in']]->getStartTimeStamp();
                $out_epoch = $calculate_policy_obj->user_date_total[$udt_shift_data['last_out']]->getEndTimeStamp();
            }
        }

        if ($date_epoch != '' and $in_epoch == '') {
            $in_epoch = $date_epoch;
        }

        if ($out_epoch == '') {
            $out_epoch = $in_epoch;
        }

        //Debug::text(' In: '. TTDate::getDate('DATE+TIME', $in_epoch) .' Out: '. TTDate::getDate('DATE+TIME', $out_epoch), __FILE__, __LINE__, __METHOD__, 10);
        $i = $in_epoch;
        $last_iteration = 0;
        //Make sure we loop on the in_epoch, out_epoch and every day inbetween. $last_iteration allows us to always hit the out_epoch.
        while ($i <= $out_epoch and $last_iteration <= 1) {
            //Debug::text(' I: '. TTDate::getDate('DATE+TIME', $i) .' Include Holiday Type: '. $this->getIncludeHolidayType(), __FILE__, __LINE__, __METHOD__, 10);
            $tmp_retval = $this->isActiveDayOfWeekOrHoliday($i, $calculate_policy_obj);
            if ($tmp_retval == true) {
                return true;
            }

            //If there is more than one day between $i and $out_epoch, add one day to $i.
            if ($i < ($out_epoch - 86400)) {
                $i += 86400;
            } else {
                //When less than one day until $out_epoch, skip to $out_epoch and loop once more.
                $i = $out_epoch;
                $last_iteration++;
            }
        }

        //Debug::text('NOT Active Date/DayOfWeek: '. TTDate::getDate('DATE+TIME', $i), __FILE__, __LINE__, __METHOD__, 10);

        return false;
    }

    public function getIncludeShiftType()
    {
        if (isset($this->data['include_shift_type_id'])) {
            return (int)$this->data['include_shift_type_id'];
        }

        return false;
    }

    public function isActiveDayOfWeekOrHoliday($date_epoch, $calculate_policy_obj = null)
    {
        Debug::text(' Date: ' . TTDate::getDate('DATE+TIME', $date_epoch) . ' Include Holiday Type: ' . $this->getIncludeHolidayType(), __FILE__, __LINE__, __METHOD__, 10);
        if ($this->getIncludeHolidayType() > 10 and is_object($calculate_policy_obj)) {
            $is_holiday = $this->isHoliday(TTDate::getMiddleDayEpoch($date_epoch), $calculate_policy_obj);
        } else {
            $is_holiday = false;
        }

        if (($this->getIncludeHolidayType() == 10 and $this->isActiveFilterDate($date_epoch) == true and $this->isActiveFilterDayOfWeek($date_epoch) == true)
            or (($this->getIncludeHolidayType() == 20 or $this->getIncludeHolidayType() == 25) and (($this->isActiveFilterDate($date_epoch) == true and $this->isActiveFilterDayOfWeek($date_epoch) == true) or $is_holiday == true))
            or ($this->getIncludeHolidayType() == 30 and (($this->isActiveFilterDate($date_epoch) == true and $this->isActiveFilterDayOfWeek($date_epoch) == true) and $is_holiday == false))
        ) {
            Debug::text('Active Date/DayOfWeek: ' . TTDate::getDate('DATE+TIME', $date_epoch), __FILE__, __LINE__, __METHOD__, 10);

            return true;
        }

        return false;
    }

    public function isHoliday($epoch, $calculate_policy_obj)
    {
        if ($epoch == '' or !is_object($calculate_policy_obj)) {
            return false;
        }

        if ($this->isHolidayRestricted() == true) {
            //Get holidays from all holiday policies assigned to this contributing shift policy
            $holiday_policy_ids = $this->getHolidayPolicy();
            if (is_array($holiday_policy_ids) and count($holiday_policy_ids) > 0) {
                foreach ($holiday_policy_ids as $holiday_policy_id) {
                    if (isset($calculate_policy_obj->holiday_policy[$holiday_policy_id])) {
                        $holiday_obj = $calculate_policy_obj->filterHoliday($epoch, $calculate_policy_obj->holiday_policy[$holiday_policy_id], null);
                        if (is_object($holiday_obj)) {
                            Debug::text(' Is Holiday: User ID: ' . $calculate_policy_obj->getUserObject()->getID() . ' Date: ' . TTDate::getDate('DATE', $epoch), __FILE__, __LINE__, __METHOD__, 10);

                            //Check if its only eligible holidays or all holidays.
                            if ($this->getIncludeHolidayType() == 20 or $this->getIncludeHolidayType() == 30) {
                                Debug::text(' Active for all Holidays', __FILE__, __LINE__, __METHOD__, 10);
                                return true;
                            } elseif ($this->getIncludeHolidayType() == 25 and $calculate_policy_obj->isEligibleForHoliday($epoch, $calculate_policy_obj->holiday_policy[$holiday_policy_id]) == true) {
                                Debug::text(' Is Eligible for Holiday', __FILE__, __LINE__, __METHOD__, 10);
                                return true;
                            }
                        }
                    }
                }
            }
        }

        Debug::text(' Not Holiday: User ID: ' . $calculate_policy_obj->getUserObject()->getID() . ' Date: ' . TTDate::getDate('DATE', $epoch), __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function isHolidayRestricted()
    {
        if ($this->getIncludeHolidayType() == 20 or $this->getIncludeHolidayType() == 25 or $this->getIncludeHolidayType() == 30) {
            return true;
        }

        return false;
    }

    public function getHolidayPolicy()
    {
        return CompanyGenericMapListFactory::getArrayByCompanyIDAndObjectTypeIDAndObjectID($this->getCompany(), 690, $this->getID());
    }

    public function isActiveFilterDate($epoch)
    {
        //Debug::text(' Checking for Active Date: '. TTDate::getDate('DATE+TIME', $epoch), __FILE__, __LINE__, __METHOD__, 10);
        $epoch = TTDate::getBeginDayEpoch($epoch);

        if ($this->getFilterStartDate() == '' and $this->getFilterEndDate() == '') {
            return true;
        }

        if ($epoch >= (int)$this->getFilterStartDate()
            and ($epoch <= (int)$this->getFilterEndDate() or $this->getFilterEndDate() == '')
        ) {
            return true;
        }

        Debug::text(' Not active FilterDate!', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function getFilterStartDate($raw = false)
    {
        if (isset($this->data['filter_start_date'])) {
            if ($raw === true) {
                return $this->data['filter_start_date'];
            } else {
                return TTDate::strtotime($this->data['filter_start_date']);
            }
        }

        return false;
    }

    public function getFilterEndDate($raw = false)
    {
        if (isset($this->data['filter_end_date'])) {
            if ($raw === true) {
                return $this->data['filter_end_date'];
            } else {
                return TTDate::strtotime($this->data['filter_end_date']);
            }
        }

        return false;
    }

    public function isActiveFilterDayOfWeek($epoch)
    {
        //Debug::Arr($epoch, ' Checking for Active Day of Week: '. $epoch, __FILE__, __LINE__, __METHOD__, 10);
        $day_of_week = strtolower(date('D', $epoch));

        switch ($day_of_week) {
            case 'sun':
                if ($this->getSun() == true) {
                    return true;
                }
                break;
            case 'mon':
                if ($this->getMon() == true) {
                    return true;
                }
                break;
            case 'tue':
                if ($this->getTue() == true) {
                    return true;
                }
                break;
            case 'wed':
                if ($this->getWed() == true) {
                    return true;
                }
                break;
            case 'thu':
                if ($this->getThu() == true) {
                    return true;
                }
                break;
            case 'fri':
                if ($this->getFri() == true) {
                    return true;
                }
                break;
            case 'sat':
                if ($this->getSat() == true) {
                    return true;
                }
                break;
        }

        Debug::text(' Not active FilterDayOfWeek!', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function getSun()
    {
        if (isset($this->data['sun'])) {
            return $this->fromBool($this->data['sun']);
        }

        return false;
    }

    public function getMon()
    {
        if (isset($this->data['mon'])) {
            return $this->fromBool($this->data['mon']);
        }

        return false;
    }

    public function getTue()
    {
        if (isset($this->data['tue'])) {
            return $this->fromBool($this->data['tue']);
        }

        return false;
    }

    public function getWed()
    {
        if (isset($this->data['wed'])) {
            return $this->fromBool($this->data['wed']);
        }

        return false;
    }

    public function getThu()
    {
        if (isset($this->data['thu'])) {
            return $this->fromBool($this->data['thu']);
        }

        return false;
    }

    public function getFri()
    {
        if (isset($this->data['fri'])) {
            return $this->fromBool($this->data['fri']);
        }

        return false;
    }

    public function getSat()
    {
        if (isset($this->data['sat'])) {
            return $this->fromBool($this->data['sat']);
        }

        return false;
    }

    public function getPartialUserDateTotalObject($udt_obj, $udt_key, $calculate_policy_obj = null)
    {
        if (!is_object($udt_obj)) {
            return false;
        }

        Debug::text(' Checking for Active Time for ' . $this->getName() . ': In: ' . TTDate::getDate('DATE+TIME', $udt_obj->getStartTimeStamp()) . ' Out: ' . TTDate::getDate('DATE+TIME', $udt_obj->getEndTimeStamp()), __FILE__, __LINE__, __METHOD__, 10);
        if ($udt_obj->getStartTimeStamp() == '' or $udt_obj->getEndTimeStamp() == '') {
            Debug::text(' Empty time stamps, returning object untouched...', __FILE__, __LINE__, __METHOD__, 10);
            return array($udt_key => $udt_obj);
        }

        $filter_start_time_stamp = TTDate::getTimeLockedDate($this->getFilterStartTime(), $udt_obj->getStartTimeStamp()); //Base the end time on day of the in_epoch.
        $filter_end_time_stamp = TTDate::getTimeLockedDate($this->getFilterEndTime(), $udt_obj->getStartTimeStamp()); //Base the end time on day of the in_epoch.
        //Debug::text(' bChecking for Active Time with: In: '. TTDate::getDate('DATE+TIME', $filter_start_time_stamp ) .' Out: '. TTDate::getDate('DATE+TIME', $filter_end_time_stamp ), __FILE__, __LINE__, __METHOD__, 10);

        //Check if end timestamp is before start, if it is, move end timestamp to next day.
        if ($filter_end_time_stamp < $filter_start_time_stamp) {
            Debug::text(' Moving End TimeStamp to next day.', __FILE__, __LINE__, __METHOD__, 10);
            $filter_end_time_stamp = TTDate::getTimeLockedDate($this->getFilterEndTime(), (TTDate::getMiddleDayEpoch($filter_end_time_stamp) + 86400)); //Due to DST, jump ahead 1.5 days, then jump back to the time locked date.
        }

        //Handle the last second of the day, so punches that span midnight like 11:00PM to 6:00AM get a full 1 hour for the time before midnight, rather than 59mins and 59secs.
        if (TTDate::getHour($filter_end_time_stamp) == 23 and TTDate::getMinute($filter_end_time_stamp) == 59) {
            $filter_end_time_stamp = (TTDate::getEndDayEpoch($filter_end_time_stamp) + 1);
            Debug::text(' End time stamp is within the last minute of day, make sure we include the last second of the day as well.', __FILE__, __LINE__, __METHOD__, 10);
        }

        if ($filter_start_time_stamp == $filter_end_time_stamp) {
            Debug::text(' Start/End time filters match, nothing to do...', __FILE__, __LINE__, __METHOD__, 10);
            return array($udt_key => $udt_obj);
        }

        if ($udt_obj->getStartTimeStamp() == $udt_obj->getEndTimeStamp()) {
            Debug::text(' Start/End time match, nothing to do...', __FILE__, __LINE__, __METHOD__, 10);
            return array($udt_key => $udt_obj);
        }

        $split_udt_time_stamps = TTDate::splitDateRangeAtMidnight($udt_obj->getStartTimeStamp(), $udt_obj->getEndTimeStamp(), $filter_start_time_stamp, $filter_end_time_stamp);
        if (is_array($split_udt_time_stamps) and count($split_udt_time_stamps) > 0) {
            $i = 0;
            foreach ($split_udt_time_stamps as $split_udt_time_stamp) {
                $tmp_udt_obj = clone $udt_obj; //Make sure we clone the object so we don't modify the original record for all subsequent accesses.

                if ($i > 0) {
                    $udt_key = $calculate_policy_obj->user_date_total_insert_id;
                    $tmp_udt_obj->setId(0); //Reset the object ID to 0 for all but the first record, so it can be inserted as new rather than update/overwrite existing records.
                }

                $tmp_udt_obj->setStartTimeStamp($split_udt_time_stamp['start_time_stamp']);
                $tmp_udt_obj->setEndTimeStamp($split_udt_time_stamp['end_time_stamp']);

                //In cases where auto-deduct meal policies exist, the total time may be negative, and without digging into the source object we may never be able to determine that.
                //So when splitting records, if the total time is already negative, keep it as such.
                $total_time = $tmp_udt_obj->calcTotalTime();
                if ($tmp_udt_obj->getTotalTime() < 0) {
                    $total_time = ($total_time * -1);
                    Debug::text(' Total Time was negative, maintain minus... Total Time: Before: ' . $tmp_udt_obj->getTotalTime() . ' After: ' . $total_time, __FILE__, __LINE__, __METHOD__, 10);
                }
                $tmp_udt_obj->setTotalTime($total_time);
                $tmp_udt_obj->setIsPartialShift(true);
                $tmp_udt_obj->setEnableCalcSystemTotalTime(false);
                if ($tmp_udt_obj->isValid()) {
                    $tmp_udt_obj->preSave(); //Call this so TotalTime, TotalTimeAmount is calculated immediately, as we don't save these records until later.
                }

                $retarr[$udt_key] = $tmp_udt_obj;
                $calculate_policy_obj->user_date_total_insert_id--;

                $i++;
            }

            return $retarr;
        }

        Debug::text(' Nothing to split, returning original UDT record...', __FILE__, __LINE__, __METHOD__, 10);
        return array($udt_key => $udt_obj);
    }

    public function getName()
    {
        if (isset($this->data['name'])) {
            return $this->data['name'];
        }

        return false;
    }

    public function isActiveDifferential($udt_obj, $user_obj)
    {
        //Debug::Arr( array( $this->getBranchSelectionType(), (int)$this->getExcludeDefaultBranch(), $udt_obj->getBranch(), $user_obj->getDefaultBranch() ), ' Branch Selection: ', __FILE__, __LINE__, __METHOD__, 10);

        $retval = false;

        //Optimization if all selection types are set to "All".
        if ($this->getBranchSelectionType() == 10 and $this->getDepartmentSelectionType() == 10 and $this->getJobGroupSelectionType() == 10 and $this->getJobSelectionType() == 10 and $this->getJobItemGroupSelectionType() == 10 and $this->getJobItemSelectionType() == 10
            and $this->getExcludeDefaultBranch() == false and $this->getExcludeDefaultDepartment() == false and $this->getExcludeDefaultJob() == false and $this->getExcludeDefaultJobItem() == false
        ) {
            return true;
        }

        if ($this->checkIndividualDifferentialCriteria($this->getBranchSelectionType(), $this->getExcludeDefaultBranch(), $udt_obj->getBranch(), $this->getBranch(), $user_obj->getDefaultBranch())) {
            //Debug::text(' Shift Differential... Meets Branch Criteria! Select Type: '. $this->getBranchSelectionType() .' Exclude Default Branch: '. (int)$this->getExcludeDefaultBranch() .' Default Branch: '.  $user_obj->getDefaultBranch(), __FILE__, __LINE__, __METHOD__, 10);

            if ($this->checkIndividualDifferentialCriteria($this->getDepartmentSelectionType(), $this->getExcludeDefaultDepartment(), $udt_obj->getDepartment(), $this->getDepartment(), $user_obj->getDefaultDepartment())) {
                //Debug::text(' Shift Differential... Meets Department Criteria! Select Type: '. $this->getDepartmentSelectionType() .' Exclude Default Department: '. (int)$this->getExcludeDefaultDepartment() .' Default Department: '.  $user_obj->getDefaultDepartment(), __FILE__, __LINE__, __METHOD__, 10);

                $job_group = (is_object($udt_obj->getJobObject())) ? $udt_obj->getJobObject()->getGroup() : null;
                if ($this->checkIndividualDifferentialCriteria($this->getJobGroupSelectionType(), null, $job_group, $this->getJobGroup())) {
                    //Debug::text(' Shift Differential... Meets Job Group Criteria! Select Type: '. $this->getJobGroupSelectionType(), __FILE__, __LINE__, __METHOD__, 10);

                    if ($this->checkIndividualDifferentialCriteria($this->getJobSelectionType(), $this->getExcludeDefaultJob(), $udt_obj->getJob(), $this->getJob(), $user_obj->getDefaultJob())) {
                        //Debug::text(' Shift Differential... Meets Job Criteria! Select Type: '. $this->getJobSelectionType() .' Exclude Default Job: '. (int)$this->getExcludeDefaultJob() .' Default Job: '.  $user_obj->getDefaultJob(), __FILE__, __LINE__, __METHOD__, 10);

                        $job_item_group = (is_object($udt_obj->getJobItemObject())) ? $udt_obj->getJobItemObject()->getGroup() : null;
                        if ($this->checkIndividualDifferentialCriteria($this->getJobItemGroupSelectionType(), null, $job_item_group, $this->getJobItemGroup())) {
                            //Debug::text(' Shift Differential... Meets Task Group Criteria! Select Type: '. $this->getJobItemGroupSelectionType(), __FILE__, __LINE__, __METHOD__, 10);

                            if ($this->checkIndividualDifferentialCriteria($this->getJobItemSelectionType(), $this->getExcludeDefaultJobItem(), $udt_obj->getJobItem(), $this->getJobItem(), $user_obj->getDefaultJobItem())) {
                                //Debug::text(' Shift Differential... Meets Task Criteria! Select Type: '. $this->getJobSelectionType() .' Exclude Default Task: '. (int)$this->getExcludeDefaultJobItem() .' Default Task: '.  $user_obj->getDefaultJobItem(), __FILE__, __LINE__, __METHOD__, 10);
                                $retval = true;
                            }
                        }
                    }
                }
            }
        }
        unset($job_group, $job_item_group);

        //Debug::text(' Active Shift Differential Result: '. (int)$retval, __FILE__, __LINE__, __METHOD__, 10);
        return $retval;
    }

    public function getBranchSelectionType()
    {
        if (isset($this->data['branch_selection_type_id'])) {
            return (int)$this->data['branch_selection_type_id'];
        }

        return false;
    }

    public function getDepartmentSelectionType()
    {
        if (isset($this->data['department_selection_type_id'])) {
            return (int)$this->data['department_selection_type_id'];
        }

        return false;
    }

    public function getJobGroupSelectionType()
    {
        if (isset($this->data['job_group_selection_type_id'])) {
            return (int)$this->data['job_group_selection_type_id'];
        }

        return false;
    }

    public function getJobSelectionType()
    {
        if (isset($this->data['job_selection_type_id'])) {
            return (int)$this->data['job_selection_type_id'];
        }

        return false;
    }

    public function getJobItemGroupSelectionType()
    {
        if (isset($this->data['job_item_group_selection_type_id'])) {
            return (int)$this->data['job_item_group_selection_type_id'];
        }

        return false;
    }

    public function getJobItemSelectionType()
    {
        if (isset($this->data['job_item_selection_type_id'])) {
            return (int)$this->data['job_item_selection_type_id'];
        }

        return false;
    }

    public function getExcludeDefaultBranch()
    {
        if (isset($this->data['exclude_default_branch'])) {
            return $this->fromBool($this->data['exclude_default_branch']);
        }

        return false;
    }

    public function getExcludeDefaultDepartment()
    {
        if (isset($this->data['exclude_default_department'])) {
            return $this->fromBool($this->data['exclude_default_department']);
        }

        return false;
    }

    public function getExcludeDefaultJob()
    {
        if (isset($this->data['exclude_default_job'])) {
            return $this->fromBool($this->data['exclude_default_job']);
        }

        return false;
    }

    public function getExcludeDefaultJobItem()
    {
        if (isset($this->data['exclude_default_job_item'])) {
            return $this->fromBool($this->data['exclude_default_job_item']);
        }

        return false;
    }

    public function checkIndividualDifferentialCriteria($selection_type, $exclude_default_item, $current_item, $allowed_items, $default_item = null)
    {
        //Debug::Arr($allowed_items, '    Allowed Items: Selection Type: '. $selection_type .' Current Item: '. $current_item, __FILE__, __LINE__, __METHOD__, 10);

        //Used to use AND ( $allowed_items === FALSE OR ( is_array( $allowed_items ) AND in_array( $current_item, $allowed_items ) ) ) )
        // But checking $allowed_items === FALSE  makes it so if $selection_type = 20 and no selection is made it will still be accepted,
        // which is the exact opposite of what we want.
        // If $selection_type = (20,30) a selection must be made for it to match.
        if (($selection_type == 10
                and ($exclude_default_item == false
                    or ($exclude_default_item == true and $current_item != $default_item)))

            or ($selection_type == 20
                and (is_array($allowed_items) and in_array($current_item, $allowed_items)))
            and ($exclude_default_item == false
                or ($exclude_default_item == true and $current_item != $default_item))

            or ($selection_type == 30
                and (is_array($allowed_items) and !in_array($current_item, $allowed_items)))
            and ($exclude_default_item == false
                or ($exclude_default_item == true and $current_item != $default_item))

        ) {
            return true;
        }

        //Debug::text('    Returning FALSE!', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function getBranch()
    {
        return $this->getCompanyGenericMapData($this->getCompany(), 610, $this->getID(), 'branch_map');
    }

    public function getDepartment()
    {
        return $this->getCompanyGenericMapData($this->getCompany(), 620, $this->getID(), 'department_map');
    }

    //Check if this premium policy is restricted by time.
    //If its not, we can apply it to non-punched hours.

    public function getJobGroup()
    {
        return $this->getCompanyGenericMapData($this->getCompany(), 640, $this->getID(), 'job_group_map');
    }

    public function getJob()
    {
        return $this->getCompanyGenericMapData($this->getCompany(), 630, $this->getID(), 'job_map');
    }

    public function getJobItemGroup()
    {
        return $this->getCompanyGenericMapData($this->getCompany(), 660, $this->getID(), 'job_item_group_map');
    }

    public function getJobItem()
    {
        return $this->getCompanyGenericMapData($this->getCompany(), 650, $this->getID(), 'job_item_map');
    }

    //Check if this time is within the start/end time.

    public function Validate($ignore_warning = true)
    {
        if ($this->getDeleted() != true and $this->Validator->getValidateOnly() == false) { //Don't check the below when mass editing.
            if ($this->getName() == '') {
                $this->Validator->isTRUE('name',
                    false,
                    TTi18n::gettext('Please specify a name'));
            }
        }

        if ($this->getDeleted() == true) {
            $rtplf = TTNew('RegularTimePolicyListFactory');
            $rtplf->getByCompanyIdAndContributingShiftPolicyId($this->getCompany(), $this->getId());
            if ($rtplf->getRecordCount() > 0) {
                $this->Validator->isTRUE('in_use',
                    false,
                    TTi18n::gettext('This contributing shift policy is currently in use') . ' ' . TTi18n::gettext('by regular time policies'));
            }

            $otplf = TTNew('OverTimePolicyListFactory');
            $otplf->getByCompanyIdAndContributingShiftPolicyId($this->getCompany(), $this->getId());
            if ($otplf->getRecordCount() > 0) {
                $this->Validator->isTRUE('in_use',
                    false,
                    TTi18n::gettext('This contributing shift policy is currently in use') . ' ' . TTi18n::gettext('by overtime policies'));
            }

            $pplf = TTNew('PremiumPolicyListFactory');
            $pplf->getByCompanyIdAndContributingShiftPolicyId($this->getCompany(), $this->getId());
            if ($pplf->getRecordCount() > 0) {
                $this->Validator->isTRUE('in_use',
                    false,
                    TTi18n::gettext('This contributing shift policy is currently in use') . ' ' . TTi18n::gettext('by premium policies'));
            }

            $hplf = TTNew('HolidayPolicyListFactory');
            $hplf->getByCompanyIdAndContributingShiftPolicyId($this->getCompany(), $this->getId());
            if ($hplf->getRecordCount() > 0) {
                $this->Validator->isTRUE('in_use',
                    false,
                    TTi18n::gettext('This contributing shift policy is currently in use') . ' ' . TTi18n::gettext('by holiday policies'));
            }

            $aplf = TTNew('AccrualPolicyListFactory');
            $aplf->getByCompanyIdAndContributingShiftPolicyId($this->getCompany(), $this->getId());
            if ($aplf->getRecordCount() > 0) {
                $this->Validator->isTRUE('in_use',
                    false,
                    TTi18n::gettext('This contributing shift policy is currently in use') . ' ' . TTi18n::gettext('by accrual policies'));
            }

            $pfplf = TTNew('PayFormulaPolicyListFactory');
            $pfplf->getByCompanyIdAndContributingShiftPolicyId($this->getCompany(), $this->getId());
            if ($pfplf->getRecordCount() > 0) {
                $this->Validator->isTRUE('in_use',
                    false,
                    TTi18n::gettext('This contributing shift policy is currently in use') . ' ' . TTi18n::gettext('by pay formula policies'));
            }
        }

        return true;
    }

    //Check if this date is within the effective date range

    public function preSave()
    {
        if ($this->getBranchSelectionType() === false or $this->getBranchSelectionType() < 10) {
            $this->setBranchSelectionType(10); //All
        }
        if ($this->getDepartmentSelectionType() === false or $this->getDepartmentSelectionType() < 10) {
            $this->setDepartmentSelectionType(10); //All
        }
        if ($this->getJobGroupSelectionType() === false or $this->getJobGroupSelectionType() < 10) {
            $this->setJobGroupSelectionType(10); //All
        }
        if ($this->getJobSelectionType() === false or $this->getJobSelectionType() < 10) {
            $this->setJobSelectionType(10); //All
        }
        if ($this->getJobItemGroupSelectionType() === false or $this->getJobItemGroupSelectionType() < 10) {
            $this->setJobItemGroupSelectionType(10); //All
        }
        if ($this->getJobItemSelectionType() === false or $this->getJobItemSelectionType() < 10) {
            $this->setJobItemSelectionType(10); //All
        }

        return true;
    }

    //Check if this day of the week is active

    public function setBranchSelectionType($value)
    {
        $value = (int)trim($value);

        if ($value == 0
            or $this->Validator->inArrayKey('branch_selection_type_id',
                $value,
                TTi18n::gettext('Incorrect Branch Selection Type'),
                $this->getOptions('branch_selection_type'))
        ) {
            $this->data['branch_selection_type_id'] = $value;

            return true;
        }

        return false;
    }

    public function setDepartmentSelectionType($value)
    {
        $value = (int)trim($value);

        if ($value == 0
            or $this->Validator->inArrayKey('department_selection_type_id',
                $value,
                TTi18n::gettext('Incorrect Department Selection Type'),
                $this->getOptions('department_selection_type'))
        ) {
            $this->data['department_selection_type_id'] = $value;

            return true;
        }

        return false;
    }

    public function setJobGroupSelectionType($value)
    {
        $value = (int)trim($value);

        if ($value == 0
            or $this->Validator->inArrayKey('job_group_selection_type_id',
                $value,
                TTi18n::gettext('Incorrect Job Group Selection Type'),
                $this->getOptions('job_group_selection_type'))
        ) {
            $this->data['job_group_selection_type_id'] = $value;

            return true;
        }

        return false;
    }

    public function setJobSelectionType($value)
    {
        $value = (int)trim($value);

        if ($value == 0
            or $this->Validator->inArrayKey('job_selection_type_id',
                $value,
                TTi18n::gettext('Incorrect Job Selection Type'),
                $this->getOptions('job_selection_type'))
        ) {
            $this->data['job_selection_type_id'] = $value;

            return true;
        }

        return false;
    }

    public function setJobItemGroupSelectionType($value)
    {
        $value = (int)trim($value);

        if ($value == 0
            or $this->Validator->inArrayKey('job_item_group_selection_type_id',
                $value,
                TTi18n::gettext('Incorrect Task Group Selection Type'),
                $this->getOptions('job_item_group_selection_type'))
        ) {
            $this->data['job_item_group_selection_type_id'] = $value;

            return true;
        }

        return false;
    }

    public function setJobItemSelectionType($value)
    {
        $value = (int)trim($value);

        if ($value == 0
            or $this->Validator->inArrayKey('job_item_selection_type_id',
                $value,
                TTi18n::gettext('Incorrect Task Selection Type'),
                $this->getOptions('job_item_selection_type'))
        ) {
            $this->data['job_item_selection_type_id'] = $value;

            return true;
        }

        return false;
    }

    public function postSave()
    {
        $this->removeCache($this->getId());

        return true;
    }

    public function setObjectFromArray($data)
    {
        if (is_array($data)) {
            $variable_function_map = $this->getVariableToFunctionMap();
            foreach ($variable_function_map as $key => $function) {
                if (isset($data[$key])) {
                    $function = 'set' . $function;
                    switch ($key) {
                        case 'filter_start_date':
                        case 'filter_end_date':
                        case 'filter_start_time':
                        case 'filter_end_time':
                            if (method_exists($this, $function)) {
                                $this->$function(TTDate::parseDateTime($data[$key]));
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

            $this->setCreatedAndUpdatedColumns($data);

            return true;
        }

        return false;
    }

    public function getObjectAsArray($include_columns = null)
    {
        $data = array();
        $variable_function_map = $this->getVariableToFunctionMap();
        if (is_array($variable_function_map)) {
            foreach ($variable_function_map as $variable => $function_stub) {
                if ($include_columns == null or (isset($include_columns[$variable]) and $include_columns[$variable] == true)) {
                    $function = 'get' . $function_stub;
                    switch ($variable) {
                        case 'in_use':
                            $data[$variable] = $this->getColumn($variable);
                            break;
                        case 'filter_start_date':
                        case 'filter_end_date':
                            if (method_exists($this, $function)) {
                                $data[$variable] = TTDate::getAPIDate('DATE', $this->$function());
                            }
                            break;
                        case 'filter_start_time':
                        case 'filter_end_time':
                            if (method_exists($this, $function)) {
                                $data[$variable] = TTDate::getAPIDate('TIME', $this->$function());
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
            $this->getCreatedAndUpdatedColumns($data, $include_columns);
        }

        return $data;
    }

    public function addLog($log_action)
    {
        return TTLog::addEntry($this->getId(), $log_action, TTi18n::getText('Contributing Shift Policy'), null, $this->getTable(), $this);
    }
}
