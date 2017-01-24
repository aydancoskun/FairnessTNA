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
class PremiumPolicyFactory extends Factory
{
    protected $table = 'premium_policy';
    protected $pk_sequence_name = 'premium_policy_id_seq'; //PK Sequence name

    protected $company_obj = null;
    protected $contributing_shift_policy_obj = null;
    protected $pay_code_obj = null;

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
            case 'type':
                $retval = array(
                    10 => TTi18n::gettext('Date/Time'),
                    20 => TTi18n::gettext('Shift Differential'),
                    30 => TTi18n::gettext('Meal/Break'),
                    40 => TTi18n::gettext('Callback'),
                    50 => TTi18n::gettext('Minimum Shift Time'),
                    90 => TTi18n::gettext('Holiday'),
                    100 => TTi18n::gettext('Advanced'),
                );
                break;
            case 'pay_type':
                //How to calculate flat rate. Base it off the DIFFERENCE between there regular hourly rate
                //and the premium. So the PS Account could be postitive or negative amount
                $retval = array(
                    10 => TTi18n::gettext('Pay Multiplied By Factor'),
                    20 => TTi18n::gettext('Pay + Premium'), //This is the same a Flat Hourly Rate (Absolute)
                    30 => TTi18n::gettext('Flat Hourly Rate (Relative to Wage)'), //This is a relative rate based on their hourly rate.
                    32 => TTi18n::gettext('Flat Hourly Rate'), //NOT relative to their default rate.
                    40 => TTi18n::gettext('Minimum Hourly Rate (Relative to Wage)'), //Pays whichever is greater, this rate or the employees original rate.
                    42 => TTi18n::gettext('Minimum Hourly Rate'), //Pays whichever is greater, this rate or the employees original rate.
                );
                break;
            case 'include_holiday_type':
                $retval = array(
                    10 => TTi18n::gettext('Have no effect'),
                    20 => TTi18n::gettext('Always on Holidays'),
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
                    '-1010-type' => TTi18n::gettext('Type'),
                    '-1030-name' => TTi18n::gettext('Name'),
                    '-1035-description' => TTi18n::gettext('Description'),

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

            'pay_type_id' => 'PayType',
            'pay_type' => false,

            'start_date' => 'StartDate',
            'end_date' => 'EndDate',
            'start_time' => 'StartTime',
            'end_time' => 'EndTime',
            'daily_trigger_time' => 'DailyTriggerTime',
            'maximum_daily_trigger_time' => 'MaximumDailyTriggerTime',
            'weekly_trigger_time' => 'WeeklyTriggerTime',
            'maximum_weekly_trigger_time' => 'MaximumWeeklyTriggerTime',
            'sun' => 'Sun',
            'mon' => 'Mon',
            'tue' => 'Tue',
            'wed' => 'Wed',
            'thu' => 'Thu',
            'fri' => 'Fri',
            'sat' => 'Sat',
            'include_holiday_type_id' => 'IncludeHolidayType',
            'include_partial_punch' => 'IncludePartialPunch',
            'maximum_no_break_time' => 'MaximumNoBreakTime',
            'minimum_break_time' => 'MinimumBreakTime',
            'minimum_time_between_shift' => 'MinimumTimeBetweenShift',
            'minimum_first_shift_time' => 'MinimumFirstShiftTime',
            'minimum_shift_time' => 'MinimumShiftTime',
            'minimum_time' => 'MinimumTime',
            'maximum_time' => 'MaximumTime',
            'include_meal_policy' => 'IncludeMealPolicy',
            'include_break_policy' => 'IncludeBreakPolicy',

            'contributing_shift_policy_id' => 'ContributingShiftPolicy',
            'contributing_shift_policy' => false,
            'pay_code_id' => 'PayCode',
            'pay_code' => false,
            'pay_formula_policy_id' => 'PayFormulaPolicy',
            'pay_formula_policy' => false,

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
            'job_item_group' => 'JobItemGroup',
            'job_item_group_selection_type_id' => 'JobItemGroupSelectionType',
            'job_item_group_selection_type' => false,
            'job_item' => 'JobItem',
            'job_item_selection_type_id' => 'JobItemSelectionType',
            'job_item_selection_type' => false,
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

    public function setContributingShiftPolicy($id)
    {
        $id = trim($id);

        $csplf = TTnew('ContributingShiftPolicyListFactory');

        if (
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

    public function setStartDate($epoch)
    {
        $epoch = (!is_int($epoch)) ? trim($epoch) : $epoch; //Dont trim integer values, as it changes them to strings.

        if ($epoch == '') {
            $epoch = null;
        }

        if (
            $epoch == null
            or
            $this->Validator->isDate('start_date',
                $epoch,
                TTi18n::gettext('Incorrect start date'))
        ) {
            $this->data['start_date'] = $epoch;

            return true;
        }

        return false;
    }

    public function setEndDate($epoch)
    {
        $epoch = (!is_int($epoch)) ? trim($epoch) : $epoch; //Dont trim integer values, as it changes them to strings.

        if ($epoch == '') {
            $epoch = null;
        }

        if ($epoch == null
            or
            $this->Validator->isDate('end_date',
                $epoch,
                TTi18n::gettext('Incorrect end date'))
        ) {
            $this->data['end_date'] = $epoch;

            return true;
        }

        return false;
    }

    public function setDailyTriggerTime($int)
    {
        $int = trim($int);

        if (empty($int)) {
            $int = 0;
        }

        if ($this->Validator->isNumeric('daily_trigger_time',
            $int,
            TTi18n::gettext('Incorrect daily trigger time'))
        ) {
            $this->data['daily_trigger_time'] = $int;

            return true;
        }

        return false;
    }

    public function setWeeklyTriggerTime($int)
    {
        $int = trim($int);

        if (empty($int)) {
            $int = 0;
        }

        if ($this->Validator->isNumeric('weekly_trigger_time',
            $int,
            TTi18n::gettext('Incorrect weekly trigger time'))
        ) {
            $this->data['weekly_trigger_time'] = $int;

            return true;
        }

        return false;
    }

    public function setMaximumDailyTriggerTime($int)
    {
        $int = trim($int);

        if (empty($int)) {
            $int = 0;
        }

        if ($this->Validator->isNumeric('daily_trigger_time',
            $int,
            TTi18n::gettext('Incorrect maximum daily trigger time'))
        ) {
            $this->data['maximum_daily_trigger_time'] = $int;

            return true;
        }

        return false;
    }

    public function setMaximumWeeklyTriggerTime($int)
    {
        $int = trim($int);

        if (empty($int)) {
            $int = 0;
        }

        if ($this->Validator->isNumeric('weekly_trigger_time',
            $int,
            TTi18n::gettext('Incorrect maximum weekly trigger time'))
        ) {
            $this->data['maximum_weekly_trigger_time'] = $int;

            return true;
        }

        return false;
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

    public function setIncludePartialPunch($bool)
    {
        $this->data['include_partial_punch'] = $this->toBool($bool);

        return true;
    }

    public function getMaximumNoBreakTime()
    {
        if (isset($this->data['maximum_no_break_time'])) {
            return (int)$this->data['maximum_no_break_time'];
        }

        return false;
    }

    public function setMaximumNoBreakTime($int)
    {
        $int = trim($int);

        if (empty($int)) {
            $int = 0;
        }

        if ($int == 0
            or $this->Validator->isNumeric('maximum_no_break_time',
                $int,
                TTi18n::gettext('Incorrect Maximum Time Without Break'))
        ) {
            $this->data['maximum_no_break_time'] = $int;

            return true;
        }

        return false;
    }

    public function getMinimumBreakTime()
    {
        if (isset($this->data['minimum_break_time'])) {
            return (int)$this->data['minimum_break_time'];
        }

        return false;
    }

    public function setMinimumBreakTime($int)
    {
        $int = trim($int);

        if (empty($int)) {
            $int = 0;
        }

        if ($int == 0
            or $this->Validator->isNumeric('minimum_break_time',
                $int,
                TTi18n::gettext('Incorrect Minimum Break Time'))
        ) {
            $this->data['minimum_break_time'] = $int;

            return true;
        }

        return false;
    }

    public function getMinimumTimeBetweenShift()
    {
        if (isset($this->data['minimum_time_between_shift'])) {
            return (int)$this->data['minimum_time_between_shift'];
        }

        return false;
    }

    public function setMinimumTimeBetweenShift($int)
    {
        $int = trim($int);

        if (empty($int)) {
            $int = 0;
        }

        if ($int == 0
            or $this->Validator->isNumeric('minimum_time_between_shift',
                $int,
                TTi18n::gettext('Incorrect Minimum Time Between Shifts'))
        ) {
            $this->data['minimum_time_between_shift'] = $int;

            return true;
        }

        return false;
    }

    public function getMinimumFirstShiftTime()
    {
        if (isset($this->data['minimum_first_shift_time'])) {
            return (int)$this->data['minimum_first_shift_time'];
        }

        return false;
    }

    public function setMinimumFirstShiftTime($int)
    {
        $int = trim($int);

        if (empty($int)) {
            $int = 0;
        }

        if ($int == 0
            or $this->Validator->isNumeric('minimum_first_shift_time',
                $int,
                TTi18n::gettext('Incorrect Minimum First Shift Time'))
        ) {
            $this->data['minimum_first_shift_time'] = $int;

            return true;
        }

        return false;
    }

    public function getMinimumShiftTime()
    {
        if (isset($this->data['minimum_shift_time'])) {
            return (int)$this->data['minimum_shift_time'];
        }

        return false;
    }

    public function setMinimumShiftTime($int)
    {
        $int = trim($int);

        if (empty($int)) {
            $int = 0;
        }

        if ($int == 0
            or $this->Validator->isNumeric('minimum_shift_time',
                $int,
                TTi18n::gettext('Incorrect Minimum Shift Time'))
        ) {
            $this->data['minimum_shift_time'] = $int;

            return true;
        }

        return false;
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

    public function getIncludeMealPolicy()
    {
        if (isset($this->data['include_meal_policy'])) {
            return $this->fromBool($this->data['include_meal_policy']);
        }

        return false;
    }

    public function setIncludeMealPolicy($bool)
    {
        $this->data['include_meal_policy'] = $this->toBool($bool);

        return true;
    }

    public function getIncludeBreakPolicy()
    {
        if (isset($this->data['include_break_policy'])) {
            return $this->fromBool($this->data['include_break_policy']);
        }

        return false;
    }

    public function setIncludeBreakPolicy($bool)
    {
        $this->data['include_break_policy'] = $this->toBool($bool);

        return true;
    }

    public function setIncludeHolidayType($value)
    {
        $value = trim($value);

        if ($this->Validator->inArrayKey('include_holiday_type',
            $value,
            TTi18n::gettext('Incorrect Include Holiday Type'),
            $this->getOptions('include_holiday_type'))
        ) {
            $this->data['include_holiday_type_id'] = $value;

            return true;
        }

        return false;
    }

    public function setPayCode($id)
    {
        $id = trim($id);

        if ($id == '' or empty($id)) {
            $id = 0;
        }

        $pclf = TTnew('PayCodeListFactory');

        if ($id == 0
            or
            $this->Validator->isResultSetWithRows('pay_code_id',
                $pclf->getById($id),
                TTi18n::gettext('Invalid Pay Code')
            )
        ) {
            $this->data['pay_code_id'] = $id;

            return true;
        }

        return false;
    }

    public function setPayFormulaPolicy($id)
    {
        $id = trim($id);

        if ($id == '' or empty($id)) {
            $id = 0;
        }

        $pfplf = TTnew('PayFormulaPolicyListFactory');

        if ($id == 0
            or
            $this->Validator->isResultSetWithRows('pay_formula_policy_id',
                $pfplf->getByID($id),
                TTi18n::gettext('Pay Formula Policy is invalid')
            )
        ) {
            $this->data['pay_formula_policy_id'] = $id;

            return true;
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

    public function setExcludeDefaultBranch($bool)
    {
        $this->data['exclude_default_branch'] = $this->toBool($bool);

        return true;
    }

    public function getBranch()
    {
        if ($this->getId() > 0 and isset($this->branch_map[$this->getId()])) {
            return $this->branch_map[$this->getId()];
        } else {
            $lf = TTnew('PremiumPolicyBranchListFactory');
            $lf->getByPremiumPolicyId($this->getId());
            $list = array();
            foreach ($lf as $obj) {
                $list[] = $obj->getBranch();
            }

            if (empty($list) == false) {
                $this->branch_map[$this->getId()] = $list;
                return $this->branch_map[$this->getId()];
            }
        }

        return false;
    }

    public function setBranch($ids)
    {
        Debug::text('Setting IDs...', __FILE__, __LINE__, __METHOD__, 10);
        //Debug::Arr($ids, 'Setting Branch IDs...', __FILE__, __LINE__, __METHOD__, 10);
        if (is_array($ids)) {
            $tmp_ids = array();

            if (!$this->isNew()) {
                //If needed, delete mappings first.
                $lf_a = TTnew('PremiumPolicyBranchListFactory');
                $lf_a->getByPremiumPolicyId($this->getId());

                foreach ($lf_a as $obj) {
                    $id = $obj->getBranch();
                    Debug::text('Branch ID: ' . $obj->getBranch() . ' ID: ' . $id, __FILE__, __LINE__, __METHOD__, 10);

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
            $lf_b = TTnew('BranchListFactory');

            foreach ($ids as $id) {
                if (isset($ids) and $id > 0 and !in_array($id, $tmp_ids)) {
                    $f = TTnew('PremiumPolicyBranchFactory');
                    $f->setPremiumPolicy($this->getId());
                    $f->setBranch($id);

                    $obj = $lf_b->getById($id)->getCurrent();

                    if ($this->Validator->isTrue('branch',
                        $f->Validator->isValid(),
                        TTi18n::gettext('Selected Branch is invalid') . ' (' . $obj->getName() . ')')
                    ) {
                        $f->save();
                    }
                }
            }

            return true;
        }

        Debug::text('No IDs to set.', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function getExcludeDefaultDepartment()
    {
        if (isset($this->data['exclude_default_department'])) {
            return $this->fromBool($this->data['exclude_default_department']);
        }

        return false;
    }

    public function setExcludeDefaultDepartment($bool)
    {
        $this->data['exclude_default_department'] = $this->toBool($bool);

        return true;
    }

    public function getDepartment()
    {
        if ($this->getId() > 0 and isset($this->department_map[$this->getId()])) {
            return $this->department_map[$this->getId()];
        } else {
            $lf = TTnew('PremiumPolicyDepartmentListFactory');
            $lf->getByPremiumPolicyId($this->getId());
            $list = array();
            foreach ($lf as $obj) {
                $list[] = $obj->getDepartment();
            }

            if (empty($list) == false) {
                $this->department_map[$this->getId()] = $list;
                return $this->department_map[$this->getId()];
            }
        }
        return false;
    }

    public function setDepartment($ids)
    {
        Debug::text('Setting IDs...', __FILE__, __LINE__, __METHOD__, 10);
        if (is_array($ids)) {
            $tmp_ids = array();

            if (!$this->isNew()) {
                //If needed, delete mappings first.
                $lf_a = TTnew('PremiumPolicyDepartmentListFactory');
                $lf_a->getByPremiumPolicyId($this->getId());

                foreach ($lf_a as $obj) {
                    $id = $obj->getDepartment();
                    Debug::text('Department ID: ' . $obj->getDepartment() . ' ID: ' . $id, __FILE__, __LINE__, __METHOD__, 10);

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
            $lf_b = TTnew('DepartmentListFactory');

            foreach ($ids as $id) {
                if (isset($ids) and $id > 0 and !in_array($id, $tmp_ids)) {
                    $f = TTnew('PremiumPolicyDepartmentFactory');
                    $f->setPremiumPolicy($this->getId());
                    $f->setDepartment($id);

                    $obj = $lf_b->getById($id)->getCurrent();

                    if ($this->Validator->isTrue('department',
                        $f->Validator->isValid(),
                        TTi18n::gettext('Selected Department is invalid') . ' (' . $obj->getName() . ')')
                    ) {
                        $f->save();
                    }
                }
            }

            return true;
        }

        Debug::text('No IDs to set.', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function getJobGroup()
    {
        return false;
    }

    public function setJobGroup($ids)
    {
        return false;
    }

    public function getExcludeDefaultJob()
    {
        if (isset($this->data['exclude_default_job'])) {
            return $this->fromBool($this->data['exclude_default_job']);
        }

        return false;
    }

    public function setExcludeDefaultJob($bool)
    {
        $this->data['exclude_default_job'] = $this->toBool($bool);

        return true;
    }

    public function getJob()
    {
        return false;
    }

    public function setJob($ids)
    {
        return false;
    }

    public function getExcludeDefaultJobItem()
    {
        if (isset($this->data['exclude_default_job_item'])) {
            return $this->fromBool($this->data['exclude_default_job_item']);
        }

        return false;
    }

    public function setExcludeDefaultJobItem($bool)
    {
        $this->data['exclude_default_job_item'] = $this->toBool($bool);

        return true;
    }

    public function getJobItemGroup()
    {
        return false;
    }

    public function setJobItemGroup($ids)
    {
        return false;
    }

    public function getJobItem()
    {
        return false;
    }

    public function setJobItem($ids)
    {
        return false;
    }

    public function isTimeRestricted()
    {
        //If time restrictions account for over 23.5 hours, then we assume
        //that this policy is not time restricted at all.
        $time_diff = abs($this->getEndTime() - $this->getStartTime());
        if ($time_diff > 0 and $time_diff < (23.5 * 3600)) {
            return true;
        }

        return false;
    }

    public function getEndTime($raw = false)
    {
        if (isset($this->data['end_time'])) {
            if ($raw === true) {
                return $this->data['end_time'];
            } else {
                return TTDate::strtotime($this->data['end_time']);
            }
        }

        return false;
    }

    public function getStartTime($raw = false)
    {
        if (isset($this->data['start_time'])) {
            if ($raw === true) {
                return $this->data['start_time'];
            } else {
                return TTDate::strtotime($this->data['start_time']);
            }
        }

        return false;
    }

    public function isHourRestricted()
    {
        if ($this->getDailyTriggerTime() > 0 or $this->getWeeklyTriggerTime() > 0 or $this->getMaximumDailyTriggerTime() > 0 or $this->getMaximumWeeklyTriggerTime() > 0) {
            return true;
        }

        return false;
    }

    public function getDailyTriggerTime()
    {
        if (isset($this->data['daily_trigger_time'])) {
            return (int)$this->data['daily_trigger_time'];
        }

        return false;
    }

    /*

    Branch/Department/Job/Task differential functions

    */

    public function getWeeklyTriggerTime()
    {
        if (isset($this->data['weekly_trigger_time'])) {
            return (int)$this->data['weekly_trigger_time'];
        }

        return false;
    }

    public function getMaximumDailyTriggerTime()
    {
        if (isset($this->data['maximum_daily_trigger_time'])) {
            return (int)$this->data['maximum_daily_trigger_time'];
        }

        return false;
    }

    public function getMaximumWeeklyTriggerTime()
    {
        if (isset($this->data['maximum_weekly_trigger_time'])) {
            return (int)$this->data['maximum_weekly_trigger_time'];
        }

        return false;
    }

    public function getPartialPunchTotalTime($in_epoch, $out_epoch, $total_time, $calculate_policy_obj = null)
    {
        $retval = $total_time;

        //If a premium policy only activates on say Sat, but the Start/End times are blank/0,
        //it won't calculate just the time on Sat if an employee works from Fri 8:00PM to Sat 3:00AM.
        //So check for StartTime/EndTime > 0 OR isDayOfWeekRestricted()
        //Then if no StartTime/EndTime is set, force it to cover the entire 24hr period.
        if ($this->isActiveTime($in_epoch, $out_epoch, $calculate_policy_obj)
            and $this->getIncludePartialPunch() == true
            and (
                ($this->getStartTime() > 0 or $this->getEndTime() > 0)
                or $this->isDayOfWeekRestricted() == true
            )
        ) {
            if ($this->getStartTime() == '') {
                $this->setStartTime(strtotime('12:00 AM'));
            }
            if ($this->getEndTime() == '') {
                $this->setEndTime(strtotime('11:59 PM'));
            }

            Debug::text(' Checking for Active Time with: In: ' . TTDate::getDate('DATE+TIME', $in_epoch) . ' Out: ' . TTDate::getDate('DATE+TIME', $out_epoch), __FILE__, __LINE__, __METHOD__, 10);

            Debug::text(' Raw Start TimeStamp(' . $this->getStartTime(true) . '): ' . TTDate::getDate('DATE+TIME', $this->getStartTime()) . ' Raw End TimeStamp(' . $this->getEndTime(true) . '): ' . TTDate::getDate('DATE+TIME', $this->getEndTime()), __FILE__, __LINE__, __METHOD__, 10);
            $start_time_stamp = TTDate::getTimeLockedDate($this->getStartTime(), $in_epoch);
            $end_time_stamp = TTDate::getTimeLockedDate($this->getEndTime(), $in_epoch);

            //Check if end timestamp is before start, if it is, move end timestamp to next day.
            if ($end_time_stamp < $start_time_stamp) {
                Debug::text(' Moving End TimeStamp to next day.', __FILE__, __LINE__, __METHOD__, 10);
                $end_time_stamp = TTDate::getTimeLockedDate($this->getEndTime(), (TTDate::getMiddleDayEpoch($end_time_stamp) + 86400)); //Due to DST, jump ahead 1.5 days, then jump back to the time locked date.
            }

            //Handle the last second of the day, so punches that span midnight like 11:00PM to 6:00AM get a full 1 hour for the time before midnight, rather than 59mins and 59secs.
            if (TTDate::getHour($end_time_stamp) == 23 and TTDate::getMinute($end_time_stamp) == 59) {
                $end_time_stamp = (TTDate::getEndDayEpoch($end_time_stamp) + 1);
                Debug::text(' End time stamp is within the last minute of day, make sure we include the last second of the day as well.', __FILE__, __LINE__, __METHOD__, 10);
            }

            $retval = 0;
            for ($i = (TTDate::getMiddleDayEpoch($start_time_stamp) - 86400); $i <= (TTDate::getMiddleDayEpoch($end_time_stamp) + 86400); $i += 86400) {
                //Due to DST, we need to make sure we always lock time of day so its the exact same. Without this it can walk by one hour either way.
                $tmp_start_time_stamp = TTDate::getTimeLockedDate($this->getStartTime(), $i);
                $next_i = ($tmp_start_time_stamp + ($end_time_stamp - $start_time_stamp)); //Get next date to base the end_time_stamp on, and to calculate if we need to adjust for DST.

                //$tmp_end_time_stamp = TTDate::getTimeLockedDate( $end_time_stamp, ( $next_i + ( TTDate::getDSTOffset( $tmp_start_time_stamp, $next_i ) * -1 ) ) ); //Use $end_time_stamp as it can be modified above due to being near midnight. Also adjust for DST by reversing it.
                $tmp_end_time_stamp = TTDate::getTimeLockedDate($end_time_stamp, $next_i); //Use $end_time_stamp as it can be modified above due to being near midnight.
                if ($this->isActiveTime($tmp_start_time_stamp, $tmp_end_time_stamp, $calculate_policy_obj) == true) {
                    $retval += TTDate::getTimeOverLapDifference($tmp_start_time_stamp, $tmp_end_time_stamp, $in_epoch, $out_epoch);
                    Debug::text(' Calculating partial time against Start TimeStamp: ' . TTDate::getDate('DATE+TIME', $tmp_start_time_stamp) . ' End TimeStamp: ' . TTDate::getDate('DATE+TIME', $tmp_end_time_stamp) . ' Total: ' . $retval, __FILE__, __LINE__, __METHOD__, 10);
                } else {
                    Debug::text(' Not Active on this day: ' . TTDate::getDate('DATE+TIME', $i), __FILE__, __LINE__, __METHOD__, 10);
                }
            }
        } else {
            Debug::text('   Not calculating partial punch, just using total time...', __FILE__, __LINE__, __METHOD__, 10);
        }

        Debug::text(' Partial Punch Total Time: ' . $retval, __FILE__, __LINE__, __METHOD__, 10);
        return $retval;
    }

    public function isActiveTime($in_epoch, $out_epoch, $calculate_policy_obj = null)
    {
        Debug::text(' Checking for Active Time with: In: ' . TTDate::getDate('DATE+TIME', $in_epoch) . ' Out: ' . TTDate::getDate('DATE+TIME', $out_epoch), __FILE__, __LINE__, __METHOD__, 10);

        Debug::text(' PP Raw Start TimeStamp(' . $this->getStartTime(true) . '): ' . TTDate::getDate('DATE+TIME', $this->getStartTime()) . ' Raw End TimeStamp: ' . TTDate::getDate('DATE+TIME', $this->getEndTime()), __FILE__, __LINE__, __METHOD__, 10);
        $start_time_stamp = TTDate::getTimeLockedDate($this->getStartTime(), $in_epoch); //Base the end time on day of the in_epoch.
        $end_time_stamp = TTDate::getTimeLockedDate($this->getEndTime(), $in_epoch); //Base the end time on day of the in_epoch.

        //Check if end timestamp is before start, if it is, move end timestamp to next day.
        if ($end_time_stamp < $start_time_stamp) {
            Debug::text(' Moving End TimeStamp to next day.', __FILE__, __LINE__, __METHOD__, 10);
            $end_time_stamp = TTDate::getTimeLockedDate($this->getEndTime(), (TTDate::getMiddleDayEpoch($end_time_stamp) + 86400)); //Due to DST, jump ahead 1.5 days, then jump back to the time locked date.
        }

        Debug::text(' Start TimeStamp: ' . TTDate::getDate('DATE+TIME', $start_time_stamp) . ' End TimeStamp: ' . TTDate::getDate('DATE+TIME', $end_time_stamp), __FILE__, __LINE__, __METHOD__, 10);
        //Check to see if start/end time stamps are not set or are equal, we always return TRUE if they are.
        if ($this->getIncludeHolidayType() == 10
            and ($start_time_stamp == '' or $end_time_stamp == '' or $start_time_stamp == $end_time_stamp)
        ) {
            Debug::text(' Start/End time not set, assume it always matches.', __FILE__, __LINE__, __METHOD__, 10);
            return true;
        } else {
            //If the premium policy start/end time spans midnight, there could be multiple windows to check
            //where the premium policy applies, make sure we check all windows.
            for ($i = (TTDate::getMiddleDayEpoch($start_time_stamp) - 86400); $i <= (TTDate::getMiddleDayEpoch($end_time_stamp) + 86400); $i += 86400) {
                $tmp_start_time_stamp = TTDate::getTimeLockedDate($this->getStartTime(), $i);
                $next_i = ($tmp_start_time_stamp + ($end_time_stamp - $start_time_stamp)); //Get next date to base the end_time_stamp on, and to calculate if we need to adjust for DST.
                $tmp_end_time_stamp = TTDate::getTimeLockedDate($end_time_stamp, ($next_i + (TTDate::getDSTOffset($tmp_start_time_stamp, $next_i) * -1))); //Use $end_time_stamp as it can be modified above due to being near midnight. Also adjust for DST by reversing it.
                if ($this->isActive($tmp_start_time_stamp, $tmp_end_time_stamp, $calculate_policy_obj) == true) {
                    Debug::text(' Checking against Start TimeStamp: ' . TTDate::getDate('DATE+TIME', $tmp_start_time_stamp) . '(' . $tmp_start_time_stamp . ') End TimeStamp: ' . TTDate::getDate('DATE+TIME', $tmp_end_time_stamp) . '(' . $tmp_end_time_stamp . ')', __FILE__, __LINE__, __METHOD__, 10);
                    if ($this->getIncludePartialPunch() == true and TTDate::isTimeOverLap($in_epoch, $out_epoch, $tmp_start_time_stamp, $tmp_end_time_stamp) == true) {
                        //When dealing with partial punches, any overlap whatsoever activates the policy.
                        Debug::text(' Partial Punch Within Active Time!', __FILE__, __LINE__, __METHOD__, 10);
                        return true;
                    } elseif ($in_epoch >= $tmp_start_time_stamp and $out_epoch <= $tmp_end_time_stamp) {
                        //Non partial punches, they must punch in AND out (entire shift) within the time window.
                        Debug::text(' Within Active Time!', __FILE__, __LINE__, __METHOD__, 10);
                        return true;
                    } elseif (($start_time_stamp == '' or $end_time_stamp == '' or $start_time_stamp == $end_time_stamp)) { //Must go AFTER the above IF statements.
                        //When IncludeHolidayType != 10 this trigger here.
                        Debug::text(' No Start/End Date/Time!', __FILE__, __LINE__, __METHOD__, 10);
                        return true;
                    } else {
                        Debug::text(' No match...', __FILE__, __LINE__, __METHOD__, 10);
                    }
                } else {
                    Debug::text(' Not Active on this day: Start: ' . TTDate::getDate('DATE+TIME', $tmp_start_time_stamp) . ' End: ' . TTDate::getDate('DATE+TIME', $tmp_end_time_stamp), __FILE__, __LINE__, __METHOD__, 10);
                }
            }
        }

        Debug::text(' NOT Within Active Time!', __FILE__, __LINE__, __METHOD__, 10);

        return false;
    }

    public function isActive($in_epoch, $out_epoch = null, $calculate_policy_obj = null)
    {
        if ($out_epoch == '') {
            $out_epoch = $in_epoch;
        }

        //Debug::text(' In: '. TTDate::getDate('DATE+TIME', $in_epoch) .' Out: '. TTDate::getDate('DATE+TIME', $out_epoch), __FILE__, __LINE__, __METHOD__, 10);
        $i = $in_epoch;
        $last_iteration = 0;
        //Make sure we loop on the in_epoch, out_epoch and every day inbetween. $last_iteration allows us to always hit the out_epoch.
        while ($i <= $out_epoch and $last_iteration <= 1) {
            //Debug::text(' I: '. TTDate::getDate('DATE+TIME', $i), __FILE__, __LINE__, __METHOD__, 10);
            if ($this->getIncludeHolidayType() > 10 and is_object($calculate_policy_obj)) {
                //$is_holiday = $this->isHoliday( $i, $user_id );
                $is_holiday = ($calculate_policy_obj->filterHoliday($i) !== false) ? true : false;
            } else {
                $is_holiday = false;
            }

            if (($this->getIncludeHolidayType() == 10 and $this->isActiveDate($i) == true and $this->isActiveDayOfWeek($i) == true)
                or ($this->getIncludeHolidayType() == 20 and (($this->isActiveDate($i) == true and $this->isActiveDayOfWeek($i) == true) or $is_holiday == true))
                or ($this->getIncludeHolidayType() == 30 and (($this->isActiveDate($i) == true and $this->isActiveDayOfWeek($i) == true) and $is_holiday == false))
            ) {
                Debug::text('Active Date/DayOfWeek: ' . TTDate::getDate('DATE+TIME', $i), __FILE__, __LINE__, __METHOD__, 10);

                return true;
            }

            //If there is more than one day between $i and $out_epoch, add one day to $i.
            if ($i < ($out_epoch - 86400)) {
                $i += 86400;
            } else {
                //When less than one day untl $out_epoch, skip to $out_epoch and loop once more.
                $i = $out_epoch;
                $last_iteration++;
            }
        }

        Debug::text('NOT Active Date/DayOfWeek: ' . TTDate::getDate('DATE+TIME', $i), __FILE__, __LINE__, __METHOD__, 10);

        return false;
    }

    public function getIncludeHolidayType()
    {
        if (isset($this->data['include_holiday_type_id'])) {
            return (int)$this->data['include_holiday_type_id'];
        }

        return false;
    }

    public function isActiveDate($epoch, $maximum_shift_time = 0)
    {
        //Debug::text(' Checking for Active Date: '. TTDate::getDate('DATE+TIME', $epoch) .' PP Start Date: '. TTDate::getDate('DATE+TIME', $this->getStartDate()) .' Maximum Shift Time: '. $maximum_shift_time, __FILE__, __LINE__, __METHOD__, 10);
        $epoch = TTDate::getBeginDayEpoch($epoch);

        if ($this->getStartDate() == '' and $this->getEndDate() == '') {
            return true;
        }

        if ($epoch >= (TTDate::getBeginDayEpoch((int)$this->getStartDate()) - (int)$maximum_shift_time)
            and ($epoch <= (TTDate::getEndDayEpoch((int)$this->getEndDate()) + (int)$maximum_shift_time) or $this->getEndDate() == '')
        ) {
            return true;
        }

        return false;
    }

    public function getStartDate($raw = false)
    {
        if (isset($this->data['start_date'])) {
            if ($raw === true) {
                return $this->data['start_date'];
            } else {
                return TTDate::strtotime($this->data['start_date']);
            }
        }

        return false;
    }

    public function getEndDate($raw = false)
    {
        if (isset($this->data['end_date'])) {
            if ($raw === true) {
                return $this->data['end_date'];
            } else {
                return TTDate::strtotime($this->data['end_date']);
            }
        }

        return false;
    }

    public function isActiveDayOfWeek($epoch)
    {
        //Debug::text(' Checking for Active Day of Week.', __FILE__, __LINE__, __METHOD__, 10);
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

    public function getIncludePartialPunch()
    {
        if (isset($this->data['include_partial_punch'])) {
            return $this->fromBool($this->data['include_partial_punch']);
        }

        return false;
    }

    public function isDayOfWeekRestricted()
    {
        if ($this->getSun() == false or $this->getMon() == false or $this->getTue() == false or $this->getWed() == false or $this->getThu() == false or $this->getFri() == false or $this->getSat() == false) {
            return true;
        }

        return false;
    }

    public function setStartTime($epoch)
    {
        $epoch = (!is_int($epoch)) ? trim($epoch) : $epoch; //Dont trim integer values, as it changes them to strings.

        if ($epoch == ''
            or
            $this->Validator->isDate('start_time',
                $epoch,
                TTi18n::gettext('Incorrect Start time'))
        ) {
            $this->data['start_time'] = $epoch;

            return true;
        }

        return false;
    }

    public function setEndTime($epoch)
    {
        $epoch = (!is_int($epoch)) ? trim($epoch) : $epoch; //Dont trim integer values, as it changes them to strings.

        if ($epoch == ''
            or
            $this->Validator->isDate('end_time',
                $epoch,
                TTi18n::gettext('Incorrect End time'))
        ) {
            $this->data['end_time'] = $epoch;

            return true;
        }

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

            if ($this->getPayCode() == 0) {
                $this->Validator->isTRUE('pay_code_id',
                    false,
                    TTi18n::gettext('Please choose a Pay Code'));
            }

            //Make sure Pay Formula Policy is defined somewhere.
            if ($this->getPayFormulaPolicy() == 0 and $this->getPayCode() > 0 and (!is_object($this->getPayCodeObject()) or (is_object($this->getPayCodeObject()) and $this->getPayCodeObject()->getPayFormulaPolicy() == 0))) {
                $this->Validator->isTRUE('pay_formula_policy_id',
                    false,
                    TTi18n::gettext('Selected Pay Code does not have a Pay Formula Policy defined'));
            }
        }

        if ($this->getDeleted() == true) {
            //Check to make sure nothing else references this policy, so we can be sure its okay to delete it.
            $pglf = TTnew('PolicyGroupListFactory');
            $pglf->getAPISearchByCompanyIdAndArrayCriteria($this->getCompany(), array('premium_policy' => $this->getId()), 1);
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

    public function getPayCode()
    {
        if (isset($this->data['pay_code_id'])) {
            return (int)$this->data['pay_code_id'];
        }

        return false;
    }

    public function getPayFormulaPolicy()
    {
        if (isset($this->data['pay_formula_policy_id'])) {
            return (int)$this->data['pay_formula_policy_id'];
        }

        return false;
    }

    public function getPayCodeObject()
    {
        return $this->getGenericObject('PayCodeListFactory', $this->getPayCode(), 'pay_code_obj');
    }

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

        if ($this->getPayType() === false) {
            $this->setPayType(10);
        }

        $this->data['rate'] = 0; //This is required until the schema removes the NOT NULL constraint.

        return true;
    }

    public function getBranchSelectionType()
    {
        if (isset($this->data['branch_selection_type_id'])) {
            return (int)$this->data['branch_selection_type_id'];
        }

        return false;
    }

    public function setBranchSelectionType($value)
    {
        $value = (int)trim($value);

        if ($value == 0
            or $this->Validator->inArrayKey('branch_selection_type',
                $value,
                TTi18n::gettext('Incorrect Branch Selection Type'),
                $this->getOptions('branch_selection_type'))
        ) {
            $this->data['branch_selection_type_id'] = $value;

            return true;
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

    public function setDepartmentSelectionType($value)
    {
        $value = (int)trim($value);

        if ($value == 0
            or $this->Validator->inArrayKey('department_selection_type',
                $value,
                TTi18n::gettext('Incorrect Department Selection Type'),
                $this->getOptions('department_selection_type'))
        ) {
            $this->data['department_selection_type_id'] = $value;

            return true;
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

    //Check if this premium policy is restricted by time.
    //If its not, we can apply it to non-punched hours.

    public function setJobGroupSelectionType($value)
    {
        $value = (int)trim($value);

        if ($value == 0
            or $this->Validator->inArrayKey('job_group_selection_type',
                $value,
                TTi18n::gettext('Incorrect Job Group Selection Type'),
                $this->getOptions('job_group_selection_type'))
        ) {
            $this->data['job_group_selection_type_id'] = $value;

            return true;
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

    public function setJobSelectionType($value)
    {
        $value = (int)trim($value);

        if ($value == 0
            or $this->Validator->inArrayKey('job_selection_type',
                $value,
                TTi18n::gettext('Incorrect Job Selection Type'),
                $this->getOptions('job_selection_type'))
        ) {
            $this->data['job_selection_type_id'] = $value;

            return true;
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

    //Check if this time is within the start/end time.

    public function setJobItemGroupSelectionType($value)
    {
        $value = (int)trim($value);

        if ($value == 0
            or $this->Validator->inArrayKey('job_item_group_selection_type',
                $value,
                TTi18n::gettext('Incorrect Task Group Selection Type'),
                $this->getOptions('job_item_group_selection_type'))
        ) {
            $this->data['job_item_group_selection_type_id'] = $value;

            return true;
        }

        return false;
    }
    /*
        function isHoliday( $epoch, $user_id ) {
            if ( $epoch == '' OR $user_id == '' ) {
                return FALSE;
            }
    
            $hlf = TTnew( 'HolidayListFactory' );
            $hlf->getByPolicyGroupUserIdAndDate( $user_id, $epoch );
            if ( $hlf->getRecordCount() > 0 ) {
                $holiday_obj = $hlf->getCurrent();
                Debug::text(' Found Holiday: '. $holiday_obj->getName(), __FILE__, __LINE__, __METHOD__, 10);
    
                if ( $holiday_obj->getHolidayPolicyObject()->getForceOverTimePolicy() == TRUE
                        OR $holiday_obj->isEligible( $user_id ) ) {
                    Debug::text(' Is Eligible for Holiday: '. $holiday_obj->getName(), __FILE__, __LINE__, __METHOD__, 10);
    
                    return TRUE;
                } else {
                    Debug::text(' Not Eligible for Holiday: '. $holiday_obj->getName(), __FILE__, __LINE__, __METHOD__, 10);
                    return FALSE; //Skip to next policy
                }
            } else {
                Debug::text(' Not Holiday: User ID: '. $user_id .' Date: '. TTDate::getDate('DATE', $epoch), __FILE__, __LINE__, __METHOD__, 10);
                return FALSE; //Skip to next policy
            }
            unset($hlf, $holiday_obj);
    
            return FALSE;
        }
    */
    //Check if this date is within the effective date range
    //Need to take into account shifts that span midnight too.

    public function getJobItemSelectionType()
    {
        if (isset($this->data['job_item_selection_type_id'])) {
            return (int)$this->data['job_item_selection_type_id'];
        }

        return false;
    }

    //Check if this day of the week is active

    public function setJobItemSelectionType($value)
    {
        $value = (int)trim($value);

        if ($value == 0
            or $this->Validator->inArrayKey('job_item_selection_type',
                $value,
                TTi18n::gettext('Incorrect Task Selection Type'),
                $this->getOptions('job_item_selection_type'))
        ) {
            $this->data['job_item_selection_type_id'] = $value;

            return true;
        }

        return false;
    }

    public function getPayType()
    {
        if (isset($this->data['pay_type_id'])) {
            return (int)$this->data['pay_type_id'];
        }

        return false;
    }

    public function setPayType($value)
    {
        $value = trim($value);

        if ($this->Validator->inArrayKey('pay_type_id',
            $value,
            TTi18n::gettext('Incorrect Pay Type'),
            $this->getOptions('pay_type'))
        ) {
            $this->data['pay_type_id'] = $value;

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
                        case 'start_date':
                        case 'end_date':
                        case 'start_time':
                        case 'end_time':
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
                        case 'type':
                        case 'pay_type':
                        case 'branch_selection_type':
                        case 'department_selection_type':
                        case 'job_group_selection_type':
                        case 'job_selection_type':
                        case 'job_item_group_selection_type':
                        case 'job_item_selection_type':
                            $function = 'get' . str_replace('_', '', $variable);
                            if (method_exists($this, $function)) {
                                $data[$variable] = Option::getByKey($this->$function(), $this->getOptions($variable));
                            }
                            break;
                        case 'start_date':
                        case 'end_date':
                            if (method_exists($this, $function)) {
                                $data[$variable] = TTDate::getAPIDate('DATE', $this->$function());
                            }
                            break;
                        case 'start_time':
                        case 'end_time':
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
        return TTLog::addEntry($this->getId(), $log_action, TTi18n::getText('Premium Policy'), null, $this->getTable(), $this);
    }
}
