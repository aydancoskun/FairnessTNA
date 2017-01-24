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
 * @package Modules\Policy
 */
class HolidayPolicyFactory extends Factory
{
    protected $table = 'holiday_policy';
    protected $pk_sequence_name = 'holiday_policy_id_seq'; //PK Sequence name

    protected $company_obj = null;
    protected $round_interval_policy_obj = null;
    protected $absence_policy_obj = null;
    protected $contributing_shift_policy_obj = null;
    protected $eligible_contributing_shift_policy_obj = null;

    public function _getFactoryOptions($name, $parent = null)
    {
        $retval = null;
        switch ($name) {
            case 'default_schedule_status':
                $sf = TTnew('ScheduleFactory');
                $retval = $sf->getOptions('status');
                break;
            case 'type':
                $retval = array(
                    10 => TTi18n::gettext('Standard'),
                    20 => TTi18n::gettext('Advanced: Fixed'),
                    30 => TTi18n::gettext('Advanced: Average'),
                );
                break;
            case 'scheduled_day':
                $retval = array(
                    0 => TTi18n::gettext('Calendar Days'),
                    1 => TTi18n::gettext('Scheduled Days'),
                    2 => TTi18n::gettext('Holiday Week Days'),
                );
                break;
            case 'shift_on_holiday_type':

                //Label: On the Holiday the Employee:
                $retval = array(
                    0 => TTi18n::gettext('May Work or May Not Work'),
                    10 => TTi18n::gettext('Must Always Work'),
                    20 => TTi18n::gettext('Must Never Work'),
                    30 => TTi18n::gettext('Must Work (Only if Scheduled)'), //If scheduled to work, they must work. Otherwise if not scheduled (or scheduled absent) and they don't work its fine too.
                    40 => TTi18n::gettext('Must Not Work (Only if Scheduled Absent)'), //If scheduled absent, they must not work. Otherwise if not scheduled, or scheduled to work and they work that is fine too.
                    //50 => TTi18n::gettext('Must Work (if Scheduled), May Work if Not Scheduled)'), //If scheduled to work, they must work, otherwise if not scheduled (or scheduled absent) they don't work its fine too.
                    //60 => TTi18n::gettext('Must Not Work (if Scheduled Absent), May Work if Not Scheduled)'), //If scheduled absent, they must not work.
                );
                break;
            case 'columns':
                $retval = array(
                    '-1010-type' => TTi18n::gettext('Type'),
                    '-1020-name' => TTi18n::gettext('Name'),
                    '-1025-description' => TTi18n::gettext('Description'),

                    '-1030-default_schedule_status' => TTi18n::gettext('Default Schedule Status'),
                    '-1040-minimum_employed_days' => TTi18n::gettext('Minimum Employed Days'),

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
                    'type',
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
            'type_id' => 'Type',
            'type' => false,
            'name' => 'Name',
            'description' => 'Description',
            'default_schedule_status_id' => 'DefaultScheduleStatus',
            'default_schedule_status' => false,
            'minimum_employed_days' => 'MinimumEmployedDays',
            'minimum_worked_period_days' => 'MinimumWorkedPeriodDays',
            'minimum_worked_days' => 'MinimumWorkedDays',
            'worked_scheduled_days' => 'WorkedScheduledDays',
            'shift_on_holiday_type_id' => 'ShiftOnHolidayType',
            'minimum_worked_after_period_days' => 'MinimumWorkedAfterPeriodDays',
            'minimum_worked_after_days' => 'MinimumWorkedAfterDays',
            'worked_after_scheduled_days' => 'WorkedAfterScheduledDays',
            'average_time_days' => 'AverageTimeDays',
            'average_days' => 'AverageDays',
            'average_time_worked_days' => 'AverageTimeWorkedDays',
            'minimum_time' => 'MinimumTime',
            'maximum_time' => 'MaximumTime',
            'round_interval_policy_id' => 'RoundIntervalPolicyID',
            //'time' => 'Time',
            'paid_absence_as_worked' => 'PaidAbsenceAsWorked',
            'force_over_time_policy' => 'ForceOverTimePolicy',

            'contributing_shift_policy_id' => 'ContributingShiftPolicy',
            'contributing_shift_policy' => false,
            'eligible_contributing_shift_policy_id' => 'EligibleContributingShiftPolicy',
            'eligible_contributing_shift_policy' => false,

            'include_over_time' => 'IncludeOverTime',
            'include_paid_absence_time' => 'IncludePaidAbsenceTime',
            'absence_policy_id' => 'AbsencePolicyID',
            'recurring_holiday_id' => 'RecurringHoliday',
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

    public function getRoundIntervalPolicyObject()
    {
        return $this->getGenericObject('RoundIntervalPolicyListFactory', $this->getRoundIntervalPolicyID(), 'round_interval_policy_obj');
    }

    public function getRoundIntervalPolicyID()
    {
        if (isset($this->data['round_interval_policy_id'])) {
            return (int)$this->data['round_interval_policy_id'];
        }

        return false;
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

    public function getContributingShiftPolicyObject()
    {
        return $this->getGenericObject('ContributingShiftPolicyListFactory', $this->getContributingShiftPolicy(), 'contributing_shift_policy_obj');
    }

    public function getContributingShiftPolicy()
    {
        if (isset($this->data['contributing_shift_policy_id'])) {
            return (int)$this->data['contributing_shift_policy_id'];
        }

        return false;
    }

    public function getEligibleContributingShiftPolicyObject()
    {
        return $this->getGenericObject('ContributingShiftPolicyListFactory', $this->getEligibleContributingShiftPolicy(), 'eligible_contributing_shift_policy_obj');
    }

    public function getEligibleContributingShiftPolicy()
    {
        if (isset($this->data['eligible_contributing_shift_policy_id'])) {
            return (int)$this->data['eligible_contributing_shift_policy_id'];
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

    public function getType()
    {
        if (isset($this->data['type_id'])) {
            return (int)$this->data['type_id'];
        }

        return false;
    }

    public function setType($value)
    {
        $value = trim($value);

        if ($this->Validator->inArrayKey('type',
            $value,
            TTi18n::gettext('Incorrect Type'),
            $this->getOptions('type'))
        ) {
            $this->data['type_id'] = $value;

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
                2, 50)
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

    public function getDefaultScheduleStatus()
    {
        if (isset($this->data['default_schedule_status_id'])) {
            return (int)$this->data['default_schedule_status_id'];
        }

        return false;
    }

    public function setDefaultScheduleStatus($value)
    {
        $value = trim($value);

        $sf = TTnew('ScheduleFactory');

        if ($this->Validator->inArrayKey('default_schedule_status',
            $value,
            TTi18n::gettext('Incorrect Default Schedule Status'),
            $sf->getOptions('status'))
        ) {
            $this->data['default_schedule_status_id'] = $value;

            return true;
        }

        return false;
    }

    public function getMinimumEmployedDays()
    {
        if (isset($this->data['minimum_employed_days'])) {
            return (int)$this->data['minimum_employed_days'];
        }

        return false;
    }

    public function setMinimumEmployedDays($int)
    {
        $int = trim($int);

        if (empty($int)) {
            $int = 0;
        }

        if ($this->Validator->isNumeric('minimum_employed_days',
            $int,
            TTi18n::gettext('Incorrect Minimum Employed days'))
        ) {
            $this->data['minimum_employed_days'] = $int;

            return true;
        }

        return false;
    }

    public function getMinimumWorkedPeriodDays()
    {
        if (isset($this->data['minimum_worked_period_days'])) {
            return (int)$this->data['minimum_worked_period_days'];
        }

        return false;
    }

    public function setMinimumWorkedPeriodDays($int)
    {
        $int = trim($int);

        if (empty($int)) {
            $int = 0;
        }

        if ($this->Validator->isNumeric('minimum_worked_period_days',
            $int,
            TTi18n::gettext('Incorrect Minimum Worked Period days'))
        ) {
            $this->data['minimum_worked_period_days'] = $int;

            return true;
        }

        return false;
    }

    public function getMinimumWorkedDays()
    {
        if (isset($this->data['minimum_worked_days'])) {
            return (int)$this->data['minimum_worked_days'];
        }

        return false;
    }

    public function setMinimumWorkedDays($int)
    {
        $int = trim($int);

        if (empty($int)) {
            $int = 0;
        }

        if ($this->Validator->isNumeric('minimum_worked_days',
            $int,
            TTi18n::gettext('Incorrect Minimum Worked days'))
        ) {
            $this->data['minimum_worked_days'] = $int;

            return true;
        }

        return false;
    }

    public function getWorkedScheduledDays()
    {
        if (isset($this->data['worked_scheduled_days'])) {
            return (int)$this->data['worked_scheduled_days'];
        }

        return true;
    }

    public function setWorkedScheduledDays($int)
    {
        $int = trim($int);

        if (empty($int)) {
            $int = 0;
        }

        if ($this->Validator->isNumeric('minimum_worked_period_days',
            $int,
            TTi18n::gettext('Incorrect Eligibility Type'))
        ) {
            $this->data['worked_scheduled_days'] = $int;

            return true;
        }

        return false;
    }

    public function getShiftOnHolidayType()
    {
        if (isset($this->data['shift_on_holiday_type_id'])) {
            return (int)$this->data['shift_on_holiday_type_id'];
        }

        return true;
    }

    public function setShiftOnHolidayType($int)
    {
        $int = trim($int);

        if (empty($int)) {
            $int = 0;
        }

        if ($this->Validator->isNumeric('shift_on_holiday_type_id',
            $int,
            TTi18n::gettext('Incorrect On Holiday Eligibility Type'))
        ) {
            $this->data['shift_on_holiday_type_id'] = $int;

            return true;
        }

        return false;
    }

    public function getMinimumWorkedAfterPeriodDays()
    {
        if (isset($this->data['minimum_worked_after_period_days'])) {
            return (int)$this->data['minimum_worked_after_period_days'];
        }

        return false;
    }

    public function setMinimumWorkedAfterPeriodDays($int)
    {
        $int = trim($int);

        if (empty($int)) {
            $int = 0;
        }

        if ($this->Validator->isNumeric('minimum_worked_after_period_days',
            $int,
            TTi18n::gettext('Incorrect Minimum Worked After Period days'))
        ) {
            $this->data['minimum_worked_after_period_days'] = $int;

            return true;
        }

        return false;
    }

    public function getMinimumWorkedAfterDays()
    {
        if (isset($this->data['minimum_worked_after_days'])) {
            return (int)$this->data['minimum_worked_after_days'];
        }

        return false;
    }

    public function setMinimumWorkedAfterDays($int)
    {
        $int = trim($int);

        if (empty($int)) {
            $int = 0;
        }

        if ($this->Validator->isNumeric('minimum_worked_after_days',
            $int,
            TTi18n::gettext('Incorrect Minimum Worked After days'))
        ) {
            $this->data['minimum_worked_after_days'] = $int;

            return true;
        }

        return false;
    }

    public function getWorkedAfterScheduledDays()
    {
        if (isset($this->data['worked_after_scheduled_days'])) {
            return (int)$this->data['worked_after_scheduled_days'];
        }

        return true;
    }

    //This is the divisor in the time averaging formula, as some provinces total time over 30 days and divide by 20 days.

    public function setWorkedAfterScheduledDays($int)
    {
        $int = trim($int);

        if (empty($int)) {
            $int = 0;
        }

        if ($this->Validator->isNumeric('minimum_worked_after_period_days',
            $int,
            TTi18n::gettext('Incorrect Eligibility Type'))
        ) {
            $this->data['worked_after_scheduled_days'] = $int;

            return true;
        }

        return false;
    }

    public function getAverageTimeDays()
    {
        if (isset($this->data['average_time_days'])) {
            return (int)$this->data['average_time_days'];
        }

        return false;
    }

    //If true, uses only worked days to average time over.
    //If false, always uses the above average days to average time over.

    public function setAverageTimeDays($int)
    {
        $int = trim($int);

        if (empty($int)) {
            $int = 0;
        }

        if ($this->Validator->isNumeric('average_time_days',
            $int,
            TTi18n::gettext('Incorrect Days to Total Time over'))
        ) {
            $this->data['average_time_days'] = $int;

            return true;
        }

        return false;
    }

    public function getAverageDays()
    {
        if (isset($this->data['average_days'])) {
            return (int)$this->data['average_days'];
        }

        return false;
    }

    public function setAverageDays($int)
    {
        $int = trim($int);

        if (empty($int)) {
            $int = 0;
        }

        if ($this->Validator->isNumeric('average_days',
            $int,
            TTi18n::gettext('Incorrect Days to Average Time over'))
        ) {
            $this->data['average_days'] = $int;

            return true;
        }

        return false;
    }

    public function getAverageTimeWorkedDays()
    {
        return $this->fromBool($this->data['average_time_worked_days']);
    }

    public function setAverageTimeWorkedDays($bool)
    {
        $this->data['average_time_worked_days'] = $this->toBool($bool);

        return true;
    }

    public function getMinimumTime()
    {
        if (isset($this->data['minimum_time'])) {
            return (int)$this->data['minimum_time'];
        }

        return false;
    }

    public function setMinimumTime($int)
    {
        $int = trim($int);

        if (empty($int)) {
            $int = 0;
        }

        if ($this->Validator->isNumeric('minimum_time',
            $int,
            TTi18n::gettext('Incorrect Minimum Time'))
        ) {
            $this->data['minimum_time'] = $int;

            return true;
        }

        return false;
    }

    public function getMaximumTime()
    {
        if (isset($this->data['maximum_time'])) {
            return (int)$this->data['maximum_time'];
        }

        return false;
    }

    /*
        function getTime() {
            if ( isset($this->data['time']) ) {
                return (int)$this->data['time'];
            }
    
            return FALSE;
        }
        function setTime($int) {
            $int = trim($int);
    
            if	( empty($int) ) {
                $int = 0;
            }
    
            if	(	$this->Validator->isNumeric(		'time',
                                                        $int,
                                                        TTi18n::gettext('Incorrect Time')) ) {
                $this->data['time'] = $int;
    
                return TRUE;
            }
    
            return FALSE;
        }
    */

    public function setMaximumTime($int)
    {
        $int = trim($int);

        if (empty($int)) {
            $int = 0;
        }

        if ($this->Validator->isNumeric('maximum_time',
            $int,
            TTi18n::gettext('Incorrect Maximum Time'))
        ) {
            $this->data['maximum_time'] = $int;

            return true;
        }

        return false;
    }

    public function setRoundIntervalPolicyID($id)
    {
        $id = trim($id);

        if ($id == '' or empty($id)) {
            $id = null;
        }

        $riplf = TTnew('RoundIntervalPolicyListFactory');

        if ($id == null
            or
            $this->Validator->isResultSetWithRows('round_interval_policy',
                $riplf->getByID($id),
                TTi18n::gettext('Rounding Policy is invalid')
            )
        ) {
            $this->data['round_interval_policy_id'] = $id;

            return true;
        }

        return false;
    }

    public function setEligibleContributingShiftPolicy($id)
    {
        $id = trim($id);

        if ($id == 0 or $id == '') {
            $id = null;
        }

        $csplf = TTnew('ContributingShiftPolicyListFactory');

        if ($id == null
            or
            $this->Validator->isResultSetWithRows('eligible_contributing_shift_policy_id',
                $csplf->getByID($id),
                TTi18n::gettext('Eligible Contributing Shift Policy is invalid')
            )
        ) {
            $this->data['eligible_contributing_shift_policy_id'] = $id;

            return true;
        }

        return false;
    }

    public function setContributingShiftPolicy($id)
    {
        $id = trim($id);

        if ($id == 0 or $id == '') {
            $id = null;
        }

        $csplf = TTnew('ContributingShiftPolicyListFactory');

        if ($id == null
            or
            $this->Validator->isResultSetWithRows('contributing_shift_policy_id',
                $csplf->getByID($id),
                TTi18n::gettext('Contributing Shift Policy is invalid')
            )
        ) {
            $this->data['contributing_shift_policy_id'] = $id;

            return true;
        }

        return false;
    }

    //Count all paid absence time as worked time.
    public function getPaidAbsenceAsWorked()
    {
        return $this->fromBool($this->data['paid_absence_as_worked']);
    }

    public function setPaidAbsenceAsWorked($bool)
    {
        $this->data['paid_absence_as_worked'] = $this->toBool($bool);

        return true;
    }

    //Always applies over time policy even if they are not eligible for the holiday.
    public function getForceOverTimePolicy()
    {
        return $this->fromBool($this->data['force_over_time_policy']);
    }

    public function setForceOverTimePolicy($bool)
    {
        $this->data['force_over_time_policy'] = $this->toBool($bool);

        return true;
    }

    public function getIncludeOverTime()
    {
        return $this->fromBool($this->data['include_over_time']);
    }

    public function setIncludeOverTime($bool)
    {
        $this->data['include_over_time'] = $this->toBool($bool);

        return true;
    }

    public function getIncludePaidAbsenceTime()
    {
        return $this->fromBool($this->data['include_paid_absence_time']);
    }

    public function setIncludePaidAbsenceTime($bool)
    {
        $this->data['include_paid_absence_time'] = $this->toBool($bool);

        return true;
    }

    public function setAbsencePolicyID($id)
    {
        $id = trim($id);

        if ($id == '' or empty($id)) {
            $id = 0;
        }

        $aplf = TTnew('AbsencePolicyListFactory');

        if ($id == 0
            or
            $this->Validator->isResultSetWithRows('absence_policy_id',
                $aplf->getByID($id),
                TTi18n::gettext('Absence Policy is invalid')
            )
        ) {
            $this->data['absence_policy_id'] = $id;

            return true;
        }

        return false;
    }

    public function getRecurringHoliday()
    {
        $hprhlf = TTnew('HolidayPolicyRecurringHolidayListFactory');
        $hprhlf->getByHolidayPolicyId($this->getId());
        Debug::text('Found Recurring Holidays Attached to this Policy: ' . $hprhlf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);

        $list = array();
        foreach ($hprhlf as $obj) {
            $list[] = $obj->getRecurringHoliday();
        }

        if (empty($list) == false) {
            return $list;
        }

        return false;
    }

    public function setRecurringHoliday($ids)
    {
        Debug::text('Setting Recurring Holiday IDs : ', __FILE__, __LINE__, __METHOD__, 10);
        if (is_array($ids) and count($ids) > 0) {
            $tmp_ids = array();
            if (!$this->isNew()) {
                //If needed, delete mappings first.
                $hprhlf = TTnew('HolidayPolicyRecurringHolidayListFactory');
                $hprhlf->getByHolidayPolicyId($this->getId());

                foreach ($hprhlf as $obj) {
                    $id = $obj->getRecurringHoliday();
                    Debug::text('Policy ID: ' . $obj->getHolidayPolicy() . ' ID: ' . $id, __FILE__, __LINE__, __METHOD__, 10);

                    //Delete users that are not selected.
                    if (!in_array($id, $ids)) {
                        Debug::text('Deleting: ' . $id, __FILE__, __LINE__, __METHOD__, 10);
                        $obj->Delete();
                    } else {
                        //Save ID's that need to be updated.
                        Debug::text('NOT Deleting : ' . $id, __FILE__, __LINE__, __METHOD__, 10);
                        $tmp_ids[] = $id;
                    }
                }
                unset($id, $obj);
            }

            //Insert new mappings.
            $rhlf = TTnew('RecurringHolidayListFactory');

            foreach ($ids as $id) {
                if (isset($ids) and !in_array($id, $tmp_ids) and $id > 0) {
                    $hprhf = TTnew('HolidayPolicyRecurringHolidayFactory');
                    $hprhf->setHolidayPolicy($this->getId());
                    $hprhf->setRecurringHoliday($id);

                    $obj = $rhlf->getById($id)->getCurrent();

                    if ($this->Validator->isTrue('recurring_holiday',
                        $hprhf->Validator->isValid(),
                        TTi18n::gettext('Selected Recurring Holiday is invalid') . ' (' . $obj->getName() . ')')
                    ) {
                        $hprhf->save();
                    }
                }
            }

            return true;
        }

        Debug::text('No User IDs to set.', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function Validate($ignore_warning = true)
    {
        if ($this->getDeleted() != true and $this->Validator->getValidateOnly() == false) { //Don't check the below when mass editing.
            if ($this->getName() == '') {
                $this->Validator->isTRUE('name',
                    false,
                    TTi18n::gettext('Please specify a name'));
            }

            if ($this->getAbsencePolicyID() == 0) {
                $this->Validator->isTrue('absence_policy_id',
                    false,
                    TTi18n::gettext('Please specify an Absence Policy'));
            }
        }

        if ($this->getDeleted() == true) {
            //Check to make sure nothing else references this policy, so we can be sure its okay to delete it.
            $pglf = TTnew('PolicyGroupListFactory');
            $pglf->getAPISearchByCompanyIdAndArrayCriteria($this->getCompany(), array('holiday_policy' => $this->getId()), 1);
            if ($pglf->getRecordCount() > 0) {
                $this->Validator->isTRUE('in_use',
                    false,
                    TTi18n::gettext('This policy is currently in use') . ' ' . TTi18n::gettext('by policy groups'));
            }
        }

        return true;
    }

    public function getName()
    {
        if (isset($this->data['name'])) {
            return $this->data['name'];
        }

        return false;
    }

    public function preSave()
    {
        return true;
    }

    public function postSave()
    {
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
                        case 'type':
                        case 'default_schedule_status':
                            $function = 'get' . str_replace('_', '', $variable);
                            if (method_exists($this, $function)) {
                                $data[$variable] = Option::getByKey($this->$function(), $this->getOptions($variable));
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
        return TTLog::addEntry($this->getId(), $log_action, TTi18n::getText('Holiday Policy'), null, $this->getTable(), $this);
    }
}
