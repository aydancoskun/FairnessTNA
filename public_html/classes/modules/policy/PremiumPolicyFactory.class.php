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
class PremiumPolicyFactory extends Factory {
	protected $table = 'premium_policy';
	protected $pk_sequence_name = 'premium_policy_id_seq'; //PK Sequence name

	protected $company_obj = NULL;
	protected $contributing_shift_policy_obj = NULL;
	protected $pay_code_obj = NULL;

	protected $branch_map = NULL;
	protected $department_map = NULL;
	protected $job_group_map = NULL;
	protected $job_map = NULL;
	protected $job_item_group_map = NULL;
	protected $job_item_map = NULL;

	function _getFactoryOptions( $name, $parent = NULL ) {

		$retval = NULL;
		switch( $name ) {
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
				$retval = Misc::arrayIntersectByKey( $this->getOptions('default_display_columns'), Misc::trimSortPrefix( $this->getOptions('columns') ) );
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
				$retval = array(
								);
				break;

		}


		return $retval;
	}

	function _getVariableToFunctionMap( $data ) {
		$variable_function_map = array(
										'id' => 'ID',
										'company_id' => 'Company',
										'type_id' => 'Type',
										'type' => FALSE,
										'name' => 'Name',
										'description' => 'Description',

										'pay_type_id' => 'PayType',
										'pay_type' => FALSE,

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
										'contributing_shift_policy' => FALSE,
										'pay_code_id' => 'PayCode',
										'pay_code' => FALSE,
										'pay_formula_policy_id' => 'PayFormulaPolicy',
										'pay_formula_policy' => FALSE,

										'branch' => 'Branch',
										'branch_selection_type_id' => 'BranchSelectionType',
										'branch_selection_type' => FALSE,
										'exclude_default_branch' => 'ExcludeDefaultBranch',
										'department' => 'Department',
										'department_selection_type_id' => 'DepartmentSelectionType',
										'department_selection_type' => FALSE,
										'exclude_default_department' => 'ExcludeDefaultDepartment',
										'job_group' => 'JobGroup',
										'job_group_selection_type_id' => 'JobGroupSelectionType',
										'job_group_selection_type' => FALSE,
										'job' => 'Job',
										'job_selection_type_id' => 'JobSelectionType',
										'job_selection_type' => FALSE,
										'job_item_group' => 'JobItemGroup',
										'job_item_group_selection_type_id' => 'JobItemGroupSelectionType',
										'job_item_group_selection_type' => FALSE,
										'job_item' => 'JobItem',
										'job_item_selection_type_id' => 'JobItemSelectionType',
										'job_item_selection_type' => FALSE,
										'in_use' => FALSE,
										'deleted' => 'Deleted',
										);
		return $variable_function_map;
	}

	function getCompanyObject() {
		return $this->getGenericObject( 'CompanyListFactory', $this->getCompany(), 'company_obj' );
	}

	function getContributingShiftPolicyObject() {
		return $this->getGenericObject( 'ContributingShiftPolicyListFactory', $this->getContributingShiftPolicy(), 'contributing_shift_policy_obj' );
	}

	function getPayCodeObject() {
		return $this->getGenericObject( 'PayCodeListFactory', $this->getPayCode(), 'pay_code_obj' );
	}

	function getCompany() {
		if ( isset($this->data['company_id']) ) {
			return (int)$this->data['company_id'];
		}

		return FALSE;
	}
	function setCompany($id) {
		$id = trim($id);

		Debug::Text('Company ID: '. $id, __FILE__, __LINE__, __METHOD__, 10);
		$clf = TTnew( 'CompanyListFactory' );

		if ( $this->Validator->isResultSetWithRows(	'company',
													$clf->getByID($id),
													TTi18n::gettext('Company is invalid')
													) ) {

			$this->data['company_id'] = $id;

			return TRUE;
		}

		return FALSE;
	}

	function getType() {
		if ( isset($this->data['type_id']) ) {
			return (int)$this->data['type_id'];
		}

		return FALSE;
	}
	function setType($value) {
		$value = trim($value);

		if ( $this->Validator->inArrayKey(	'type',
											$value,
											TTi18n::gettext('Incorrect Type'),
											$this->getOptions('type')) ) {

			$this->data['type_id'] = $value;

			return TRUE;
		}

		return FALSE;
	}

	function isUniqueName($name) {
		$name = trim($name);
		if ( $name == '' ) {
			return FALSE;
		}

		$ph = array(
					'company_id' => (int)$this->getCompany(),
					'name' => TTi18n::strtolower($name),
					);

		$query = 'select id from '. $this->getTable() .' where company_id = ? AND lower(name) = ? AND deleted=0';
		$id = $this->db->GetOne($query, $ph);
		Debug::Arr($id, 'Unique: '. $name, __FILE__, __LINE__, __METHOD__, 10);

		if ( $id === FALSE ) {
			return TRUE;
		} else {
			if ($id == $this->getId() ) {
				return TRUE;
			}
		}

		return FALSE;
	}
	function getName() {
		if ( isset($this->data['name']) ) {
			return $this->data['name'];
		}

		return FALSE;
	}
	function setName($name) {
		$name = trim($name);
		if (	$this->Validator->isLength(	'name',
											$name,
											TTi18n::gettext('Name is too short or too long'),
											2, 50)
				AND
				$this->Validator->isTrue(	'name',
											$this->isUniqueName($name),
											TTi18n::gettext('Name is already in use') )
						) {

			$this->data['name'] = $name;

			return TRUE;
		}

		return FALSE;
	}

	function getDescription() {
		if ( isset($this->data['description']) ) {
			return $this->data['description'];
		}

		return FALSE;
	}
	function setDescription($description) {
		$description = trim($description);

		if (	$description == ''
				OR $this->Validator->isLength(	'description',
												$description,
												TTi18n::gettext('Description is invalid'),
												1, 250) ) {

			$this->data['description'] = $description;

			return TRUE;
		}

		return FALSE;
	}

	function getContributingShiftPolicy() {
		if ( isset($this->data['contributing_shift_policy_id']) ) {
			return (int)$this->data['contributing_shift_policy_id'];
		}

		return FALSE;
	}
	function setContributingShiftPolicy($id) {
		$id = trim($id);

		$csplf = TTnew( 'ContributingShiftPolicyListFactory' );

		if (
				$this->Validator->isResultSetWithRows(	'contributing_shift_policy_id',
													$csplf->getByID($id),
													TTi18n::gettext('Contributing Shift Policy is invalid')
													) ) {

			$this->data['contributing_shift_policy_id'] = $id;

			return TRUE;
		}

		return FALSE;
	}

	function getPayType() {
		if ( isset($this->data['pay_type_id']) ) {
			return (int)$this->data['pay_type_id'];
		}

		return FALSE;
	}
	function setPayType($value) {
		$value = trim($value);

		if ( $this->Validator->inArrayKey(	'pay_type_id',
											$value,
											TTi18n::gettext('Incorrect Pay Type'),
											$this->getOptions('pay_type')) ) {

			$this->data['pay_type_id'] = $value;

			return TRUE;
		}

		return FALSE;
	}

	function getStartDate( $raw = FALSE ) {
		if ( isset($this->data['start_date']) ) {
			if ( $raw === TRUE ) {
				return $this->data['start_date'];
			} else {
				return TTDate::strtotime( $this->data['start_date'] );
			}
		}

		return FALSE;
	}
	function setStartDate($epoch) {
		$epoch = ( !is_int($epoch) ) ? trim($epoch) : $epoch; //Dont trim integer values, as it changes them to strings.

		if ( $epoch == '' ) {
			$epoch = NULL;
		}

		if	(
				$epoch == NULL
				OR
				$this->Validator->isDate(		'start_date',
												$epoch,
												TTi18n::gettext('Incorrect start date'))
			) {

			$this->data['start_date'] = $epoch;

			return TRUE;
		}

		return FALSE;
	}

	function getEndDate( $raw = FALSE ) {
		if ( isset($this->data['end_date']) ) {
			if ( $raw === TRUE ) {
				return $this->data['end_date'];
			} else {
				return TTDate::strtotime( $this->data['end_date'] );
			}
		}

		return FALSE;
	}
	function setEndDate($epoch) {
		$epoch = ( !is_int($epoch) ) ? trim($epoch) : $epoch; //Dont trim integer values, as it changes them to strings.

		if ( $epoch == '' ) {
			$epoch = NULL;
		}

		if	(	$epoch == NULL
				OR
				$this->Validator->isDate(		'end_date',
												$epoch,
												TTi18n::gettext('Incorrect end date'))
			) {

			$this->data['end_date'] = $epoch;

			return TRUE;
		}

		return FALSE;
	}

	function getStartTime( $raw = FALSE ) {
		if ( isset($this->data['start_time']) ) {
			if ( $raw === TRUE) {
				return $this->data['start_time'];
			} else {
				return TTDate::strtotime( $this->data['start_time'] );
			}
		}

		return FALSE;
	}
	function setStartTime($epoch) {
		$epoch = ( !is_int($epoch) ) ? trim($epoch) : $epoch; //Dont trim integer values, as it changes them to strings.

		if	(	$epoch == ''
				OR
				$this->Validator->isDate(		'start_time',
												$epoch,
												TTi18n::gettext('Incorrect Start time'))
			) {

			$this->data['start_time'] = $epoch;

			return TRUE;
		}

		return FALSE;
	}

	function getEndTime( $raw = FALSE ) {
		if ( isset($this->data['end_time']) ) {
			if ( $raw === TRUE) {
				return $this->data['end_time'];
			} else {
				return TTDate::strtotime( $this->data['end_time'] );
			}
		}

		return FALSE;
	}
	function setEndTime($epoch) {
		$epoch = ( !is_int($epoch) ) ? trim($epoch) : $epoch; //Dont trim integer values, as it changes them to strings.

		if	(	$epoch == ''
				OR
				$this->Validator->isDate(		'end_time',
												$epoch,
												TTi18n::gettext('Incorrect End time'))
			) {

			$this->data['end_time'] = $epoch;

			return TRUE;
		}

		return FALSE;
	}

	function getDailyTriggerTime() {
		if ( isset($this->data['daily_trigger_time']) ) {
			return (int)$this->data['daily_trigger_time'];
		}

		return FALSE;
	}
	function setDailyTriggerTime($int) {
		$int = trim($int);

		if	( empty($int) ) {
			$int = 0;
		}

		if	(	$this->Validator->isNumeric(		'daily_trigger_time',
													$int,
													TTi18n::gettext('Incorrect daily trigger time')) ) {
			$this->data['daily_trigger_time'] = $int;

			return TRUE;
		}

		return FALSE;
	}

	function getWeeklyTriggerTime() {
		if ( isset($this->data['weekly_trigger_time']) ) {
			return (int)$this->data['weekly_trigger_time'];
		}

		return FALSE;
	}
	function setWeeklyTriggerTime($int) {
		$int = trim($int);

		if	( empty($int) ) {
			$int = 0;
		}

		if	(	$this->Validator->isNumeric(		'weekly_trigger_time',
													$int,
													TTi18n::gettext('Incorrect weekly trigger time')) ) {
			$this->data['weekly_trigger_time'] = $int;

			return TRUE;
		}

		return FALSE;
	}

	function getMaximumDailyTriggerTime() {
		if ( isset($this->data['maximum_daily_trigger_time']) ) {
			return (int)$this->data['maximum_daily_trigger_time'];
		}

		return FALSE;
	}
	function setMaximumDailyTriggerTime($int) {
		$int = trim($int);

		if	( empty($int) ) {
			$int = 0;
		}

		if	(	$this->Validator->isNumeric(		'daily_trigger_time',
													$int,
													TTi18n::gettext('Incorrect maximum daily trigger time')) ) {
			$this->data['maximum_daily_trigger_time'] = $int;

			return TRUE;
		}

		return FALSE;
	}

	function getMaximumWeeklyTriggerTime() {
		if ( isset($this->data['maximum_weekly_trigger_time']) ) {
			return (int)$this->data['maximum_weekly_trigger_time'];
		}

		return FALSE;
	}
	function setMaximumWeeklyTriggerTime($int) {
		$int = trim($int);

		if	( empty($int) ) {
			$int = 0;
		}

		if	(	$this->Validator->isNumeric(		'weekly_trigger_time',
													$int,
													TTi18n::gettext('Incorrect maximum weekly trigger time')) ) {
			$this->data['maximum_weekly_trigger_time'] = $int;

			return TRUE;
		}

		return FALSE;
	}

	function getSun() {
		if ( isset($this->data['sun']) ) {
			return $this->fromBool( $this->data['sun'] );
		}

		return FALSE;
	}
	function setSun($bool) {
		$this->data['sun'] = $this->toBool($bool);

		return TRUE;
	}

	function getMon() {
		if ( isset($this->data['mon']) ) {
			return $this->fromBool( $this->data['mon'] );
		}

		return FALSE;
	}
	function setMon($bool) {
		$this->data['mon'] = $this->toBool($bool);

		return TRUE;
	}
	function getTue() {
		if ( isset($this->data['tue']) ) {
			return $this->fromBool( $this->data['tue'] );
		}

		return FALSE;
	}
	function setTue($bool) {
		$this->data['tue'] = $this->toBool($bool);

		return TRUE;
	}
	function getWed() {
		if ( isset($this->data['wed']) ) {
			return $this->fromBool( $this->data['wed'] );
		}

		return FALSE;
	}
	function setWed($bool) {
		$this->data['wed'] = $this->toBool($bool);

		return TRUE;
	}
	function getThu() {
		if ( isset($this->data['thu']) ) {
			return $this->fromBool( $this->data['thu'] );
		}

		return FALSE;
	}
	function setThu($bool) {
		$this->data['thu'] = $this->toBool($bool);

		return TRUE;
	}
	function getFri() {
		if ( isset($this->data['fri']) ) {
			return $this->fromBool( $this->data['fri'] );
		}

		return FALSE;
	}
	function setFri($bool) {
		$this->data['fri'] = $this->toBool($bool);

		return TRUE;
	}
	function getSat() {
		if ( isset($this->data['sat']) ) {
			return $this->fromBool( $this->data['sat'] );
		}

		return FALSE;
	}
	function setSat($bool) {
		$this->data['sat'] = $this->toBool($bool);

		return TRUE;
	}


	function getIncludePartialPunch() {
		if ( isset($this->data['include_partial_punch']) ) {
			return $this->fromBool( $this->data['include_partial_punch'] );
		}

		return FALSE;
	}
	function setIncludePartialPunch($bool) {
		$this->data['include_partial_punch'] = $this->toBool($bool);

		return TRUE;
	}

	function getMaximumNoBreakTime() {
		if ( isset($this->data['maximum_no_break_time']) ) {
			return (int)$this->data['maximum_no_break_time'];
		}

		return FALSE;
	}
	function setMaximumNoBreakTime($int) {
		$int = trim($int);

		if	( empty($int) ) {
			$int = 0;
		}

		if	( $int == 0
				OR $this->Validator->isNumeric(		'maximum_no_break_time',
													$int,
													TTi18n::gettext('Incorrect Maximum Time Without Break')) ) {
			$this->data['maximum_no_break_time'] = $int;

			return TRUE;
		}

		return FALSE;
	}

	function getMinimumBreakTime() {
		if ( isset($this->data['minimum_break_time']) ) {
			return (int)$this->data['minimum_break_time'];
		}

		return FALSE;
	}
	function setMinimumBreakTime($int) {
		$int = trim($int);

		if	( empty($int) ) {
			$int = 0;
		}

		if	(	$int == 0
				OR $this->Validator->isNumeric(		'minimum_break_time',
													$int,
													TTi18n::gettext('Incorrect Minimum Break Time')) ) {
			$this->data['minimum_break_time'] = $int;

			return TRUE;
		}

		return FALSE;
	}

	function getMinimumTimeBetweenShift() {
		if ( isset($this->data['minimum_time_between_shift']) ) {
			return (int)$this->data['minimum_time_between_shift'];
		}

		return FALSE;
	}
	function setMinimumTimeBetweenShift($int) {
		$int = trim($int);

		if	( empty($int) ) {
			$int = 0;
		}

		if	( $int == 0
				OR $this->Validator->isNumeric(		'minimum_time_between_shift',
													$int,
													TTi18n::gettext('Incorrect Minimum Time Between Shifts')) ) {
			$this->data['minimum_time_between_shift'] = $int;

			return TRUE;
		}

		return FALSE;
	}

	function getMinimumFirstShiftTime() {
		if ( isset($this->data['minimum_first_shift_time']) ) {
			return (int)$this->data['minimum_first_shift_time'];
		}

		return FALSE;
	}
	function setMinimumFirstShiftTime($int) {
		$int = trim($int);

		if	( empty($int) ) {
			$int = 0;
		}

		if	(	$int == 0
				OR $this->Validator->isNumeric(		'minimum_first_shift_time',
													$int,
													TTi18n::gettext('Incorrect Minimum First Shift Time')) ) {
			$this->data['minimum_first_shift_time'] = $int;

			return TRUE;
		}

		return FALSE;
	}

	function getMinimumShiftTime() {
		if ( isset($this->data['minimum_shift_time']) ) {
			return (int)$this->data['minimum_shift_time'];
		}

		return FALSE;
	}
	function setMinimumShiftTime($int) {
		$int = trim($int);

		if	( empty($int) ) {
			$int = 0;
		}

		if	(	$int == 0
				OR $this->Validator->isNumeric(		'minimum_shift_time',
													$int,
													TTi18n::gettext('Incorrect Minimum Shift Time')) ) {
			$this->data['minimum_shift_time'] = $int;

			return TRUE;
		}

		return FALSE;
	}


	function getMinimumTime() {
		if ( isset($this->data['minimum_time']) ) {
			return (int)$this->data['minimum_time'];
		}

		return FALSE;
	}
	function setMinimumTime($int) {
		$int = trim($int);

		if	( empty($int) ) {
			$int = 0;
		}

		if	(	$this->Validator->isNumeric(		'minimum_time',
													$int,
													TTi18n::gettext('Incorrect Minimum Time')) ) {
			$this->data['minimum_time'] = $int;

			return TRUE;
		}

		return FALSE;
	}

	function getMaximumTime() {
		if ( isset($this->data['maximum_time']) ) {
			return (int)$this->data['maximum_time'];
		}

		return FALSE;
	}
	function setMaximumTime($int) {
		$int = trim($int);

		if	( empty($int) ) {
			$int = 0;
		}

		if	(	$this->Validator->isNumeric(		'maximum_time',
													$int,
													TTi18n::gettext('Incorrect Maximum Time')) ) {
			$this->data['maximum_time'] = $int;

			return TRUE;
		}

		return FALSE;
	}

	function getIncludeMealPolicy() {
		if ( isset($this->data['include_meal_policy']) ) {
			return $this->fromBool( $this->data['include_meal_policy'] );
		}

		return FALSE;
	}
	function setIncludeMealPolicy($bool) {
		$this->data['include_meal_policy'] = $this->toBool($bool);

		return TRUE;
	}

	function getIncludeBreakPolicy() {
		if ( isset($this->data['include_break_policy']) ) {
			return $this->fromBool( $this->data['include_break_policy'] );
		}

		return FALSE;
	}
	function setIncludeBreakPolicy($bool) {
		$this->data['include_break_policy'] = $this->toBool($bool);

		return TRUE;
	}

	function getIncludeHolidayType() {
		if ( isset($this->data['include_holiday_type_id']) ) {
			return (int)$this->data['include_holiday_type_id'];
		}

		return FALSE;
	}
	function setIncludeHolidayType($value) {
		$value = trim($value);

		if ( $this->Validator->inArrayKey(	'include_holiday_type',
											$value,
											TTi18n::gettext('Incorrect Include Holiday Type'),
											$this->getOptions('include_holiday_type')) ) {

			$this->data['include_holiday_type_id'] = $value;

			return TRUE;
		}

		return FALSE;
	}

	function getPayCode() {
		if ( isset($this->data['pay_code_id']) ) {
			return (int)$this->data['pay_code_id'];
		}

		return FALSE;
	}
	function setPayCode($id) {
		$id = trim($id);

		if ( $id == '' OR empty($id) ) {
			$id = 0;
		}

		$pclf = TTnew( 'PayCodeListFactory' );

		if (	$id == 0
				OR
				$this->Validator->isResultSetWithRows(	'pay_code_id',
														$pclf->getById($id),
														TTi18n::gettext('Invalid Pay Code')
														) ) {
			$this->data['pay_code_id'] = $id;

			return TRUE;
		}

		return FALSE;
	}

	function getPayFormulaPolicy() {
		if ( isset($this->data['pay_formula_policy_id']) ) {
			return (int)$this->data['pay_formula_policy_id'];
		}

		return FALSE;
	}
	function setPayFormulaPolicy($id) {
		$id = trim($id);

		if ( $id == '' OR empty($id) ) {
			$id = 0;
		}

		$pfplf = TTnew( 'PayFormulaPolicyListFactory' );

		if ( $id == 0
				OR
				$this->Validator->isResultSetWithRows(	'pay_formula_policy_id',
													$pfplf->getByID($id),
													TTi18n::gettext('Pay Formula Policy is invalid')
													) ) {

			$this->data['pay_formula_policy_id'] = $id;

			return TRUE;
		}

		return FALSE;
	}

	/*

	Branch/Department/Job/Task differential functions

	*/
	function getBranchSelectionType() {
		if ( isset($this->data['branch_selection_type_id']) ) {
			return (int)$this->data['branch_selection_type_id'];
		}

		return FALSE;
	}
	function setBranchSelectionType($value) {
		$value = (int)trim($value);

		if ( $value == 0
				OR $this->Validator->inArrayKey(	'branch_selection_type',
											$value,
											TTi18n::gettext('Incorrect Branch Selection Type'),
											$this->getOptions('branch_selection_type')) ) {

			$this->data['branch_selection_type_id'] = $value;

			return TRUE;
		}

		return FALSE;
	}

	function getExcludeDefaultBranch() {
		if ( isset($this->data['exclude_default_branch']) ) {
			return $this->fromBool( $this->data['exclude_default_branch'] );
		}

		return FALSE;
	}
	function setExcludeDefaultBranch($bool) {
		$this->data['exclude_default_branch'] = $this->toBool($bool);

		return TRUE;
	}

	function getBranch() {
		if ( $this->getId() > 0 AND isset($this->branch_map[$this->getId()]) ) {
			return $this->branch_map[$this->getId()];
		} else {
			$lf = TTnew( 'PremiumPolicyBranchListFactory' );
			$lf->getByPremiumPolicyId( $this->getId() );
			$list = array();
			foreach ($lf as $obj) {
				$list[] = $obj->getBranch();
			}

			if ( empty($list) == FALSE) {
				$this->branch_map[$this->getId()] = $list;
				return $this->branch_map[$this->getId()];
			}
		}

		return FALSE;
	}

	function setBranch($ids) {
		Debug::text('Setting IDs...', __FILE__, __LINE__, __METHOD__, 10);
		//Debug::Arr($ids, 'Setting Branch IDs...', __FILE__, __LINE__, __METHOD__, 10);
		if ( is_array($ids) ) {
			$tmp_ids = array();

			if ( !$this->isNew() ) {
				//If needed, delete mappings first.
				$lf_a = TTnew( 'PremiumPolicyBranchListFactory' );
				$lf_a->getByPremiumPolicyId( $this->getId() );

				foreach ($lf_a as $obj) {
					$id = $obj->getBranch();
					Debug::text('Branch ID: '. $obj->getBranch() .' ID: '. $id, __FILE__, __LINE__, __METHOD__, 10);

					//Delete users that are not selected.
					if ( !in_array($id, $ids) ) {
						Debug::text('Deleting: '. $id, __FILE__, __LINE__, __METHOD__, 10);
						$obj->Delete();
					} else {
						//Save ID's that need to be updated.
						Debug::text('NOT Deleting : '. $id, __FILE__, __LINE__, __METHOD__, 10);
						$tmp_ids[] = $id;
					}
				}
				unset($id, $obj);
			}

			//Insert new mappings.
			$lf_b = TTnew( 'BranchListFactory' );

			foreach ($ids as $id) {
				if ( isset($ids) AND $id > 0 AND !in_array($id, $tmp_ids) ) {
					$f = TTnew( 'PremiumPolicyBranchFactory' );
					$f->setPremiumPolicy( $this->getId() );
					$f->setBranch( $id );

					$obj = $lf_b->getById( $id )->getCurrent();

					if ($this->Validator->isTrue(		'branch',
														$f->Validator->isValid(),
														TTi18n::gettext('Selected Branch is invalid').' ('. $obj->getName() .')' )) {
						$f->save();
					}
				}
			}

			return TRUE;
		}

		Debug::text('No IDs to set.', __FILE__, __LINE__, __METHOD__, 10);
		return FALSE;
	}

	function getDepartmentSelectionType() {
		if ( isset($this->data['department_selection_type_id']) ) {
			return (int)$this->data['department_selection_type_id'];
		}

		return FALSE;
	}
	function setDepartmentSelectionType($value) {
		$value = (int)trim($value);

		if ( $value == 0
				OR $this->Validator->inArrayKey(	'department_selection_type',
											$value,
											TTi18n::gettext('Incorrect Department Selection Type'),
											$this->getOptions('department_selection_type')) ) {

			$this->data['department_selection_type_id'] = $value;

			return TRUE;
		}

		return FALSE;
	}

	function getExcludeDefaultDepartment() {
		if ( isset($this->data['exclude_default_department']) ) {
			return $this->fromBool( $this->data['exclude_default_department'] );
		}

		return FALSE;
	}
	function setExcludeDefaultDepartment($bool) {
		$this->data['exclude_default_department'] = $this->toBool($bool);

		return TRUE;
	}

	function getDepartment() {
		if ( $this->getId() > 0 AND isset($this->department_map[$this->getId()]) ) {
			return $this->department_map[$this->getId()];
		} else {
			$lf = TTnew( 'PremiumPolicyDepartmentListFactory' );
			$lf->getByPremiumPolicyId( $this->getId() );
			$list = array();
			foreach ($lf as $obj) {
				$list[] = $obj->getDepartment();
			}

			if ( empty($list) == FALSE ) {
				$this->department_map[$this->getId()] = $list;
				return $this->department_map[$this->getId()];
			}
		}
		return FALSE;
	}

	function setDepartment($ids) {
		Debug::text('Setting IDs...', __FILE__, __LINE__, __METHOD__, 10);
		if ( is_array($ids) ) {
			$tmp_ids = array();

			if ( !$this->isNew() ) {
				//If needed, delete mappings first.
				$lf_a = TTnew( 'PremiumPolicyDepartmentListFactory' );
				$lf_a->getByPremiumPolicyId( $this->getId() );

				foreach ($lf_a as $obj) {
					$id = $obj->getDepartment();
					Debug::text('Department ID: '. $obj->getDepartment() .' ID: '. $id, __FILE__, __LINE__, __METHOD__, 10);

					//Delete users that are not selected.
					if ( !in_array($id, $ids) ) {
						Debug::text('Deleting: '. $id, __FILE__, __LINE__, __METHOD__, 10);
						$obj->Delete();
					} else {
						//Save ID's that need to be updated.
						Debug::text('NOT Deleting : '. $id, __FILE__, __LINE__, __METHOD__, 10);
						$tmp_ids[] = $id;
					}
				}
				unset($id, $obj);
			}

			//Insert new mappings.
			$lf_b = TTnew( 'DepartmentListFactory' );

			foreach ($ids as $id) {
				if ( isset($ids) AND $id > 0 AND !in_array($id, $tmp_ids) ) {
					$f = TTnew( 'PremiumPolicyDepartmentFactory' );
					$f->setPremiumPolicy( $this->getId() );
					$f->setDepartment( $id );

					$obj = $lf_b->getById( $id )->getCurrent();

					if ($this->Validator->isTrue(		'department',
														$f->Validator->isValid(),
														TTi18n::gettext('Selected Department is invalid').' ('. $obj->getName() .')' )) {
						$f->save();
					}
				}
			}

			return TRUE;
		}

		Debug::text('No IDs to set.', __FILE__, __LINE__, __METHOD__, 10);
		return FALSE;
	}

	function getJobGroupSelectionType() {
		if ( isset($this->data['job_group_selection_type_id']) ) {
			return (int)$this->data['job_group_selection_type_id'];
		}

		return FALSE;
	}
	function setJobGroupSelectionType($value) {
		$value = (int)trim($value);

		if ( $value == 0
				OR $this->Validator->inArrayKey(	'job_group_selection_type',
											$value,
											TTi18n::gettext('Incorrect Job Group Selection Type'),
											$this->getOptions('job_group_selection_type')) ) {

			$this->data['job_group_selection_type_id'] = $value;

			return TRUE;
		}

		return FALSE;
	}

	function getJobGroup() {
			return FALSE;
	}
	function setJobGroup($ids) {
		return FALSE;
	}

	function getJobSelectionType() {
		if ( isset($this->data['job_selection_type_id']) ) {
			return (int)$this->data['job_selection_type_id'];
		}

		return FALSE;
	}
	function setJobSelectionType($value) {
		$value = (int)trim($value);

		if ( $value == 0
				OR $this->Validator->inArrayKey(	'job_selection_type',
											$value,
											TTi18n::gettext('Incorrect Job Selection Type'),
											$this->getOptions('job_selection_type')) ) {

			$this->data['job_selection_type_id'] = $value;

			return TRUE;
		}

		return FALSE;
	}

	function getExcludeDefaultJob() {
		if ( isset($this->data['exclude_default_job']) ) {
			return $this->fromBool( $this->data['exclude_default_job'] );
		}

		return FALSE;
	}
	function setExcludeDefaultJob($bool) {
		$this->data['exclude_default_job'] = $this->toBool($bool);

		return TRUE;
	}

	function getJob() {
		return FALSE;
	}
	function setJob($ids) {
		return FALSE;
	}

	function getJobItemGroupSelectionType() {
		if ( isset($this->data['job_item_group_selection_type_id']) ) {
			return (int)$this->data['job_item_group_selection_type_id'];
		}

		return FALSE;
	}
	function setJobItemGroupSelectionType($value) {
		$value = (int)trim($value);

		if ( $value == 0
				OR $this->Validator->inArrayKey(	'job_item_group_selection_type',
											$value,
											TTi18n::gettext('Incorrect Task Group Selection Type'),
											$this->getOptions('job_item_group_selection_type')) ) {

			$this->data['job_item_group_selection_type_id'] = $value;

			return TRUE;
		}

		return FALSE;
	}

	function getExcludeDefaultJobItem() {
		if ( isset($this->data['exclude_default_job_item']) ) {
			return $this->fromBool( $this->data['exclude_default_job_item'] );
		}

		return FALSE;
	}
	function setExcludeDefaultJobItem($bool) {
		$this->data['exclude_default_job_item'] = $this->toBool($bool);

		return TRUE;
	}

	function getJobItemGroup() {
		return FALSE;
	}

	function setJobItemGroup($ids) {
		return FALSE;
	}

	function getJobItemSelectionType() {
		if ( isset($this->data['job_item_selection_type_id']) ) {
			return (int)$this->data['job_item_selection_type_id'];
		}

		return FALSE;
	}
	function setJobItemSelectionType($value) {
		$value = (int)trim($value);

		if ( $value == 0
				OR $this->Validator->inArrayKey(	'job_item_selection_type',
											$value,
											TTi18n::gettext('Incorrect Task Selection Type'),
											$this->getOptions('job_item_selection_type')) ) {

			$this->data['job_item_selection_type_id'] = $value;

			return TRUE;
		}

		return FALSE;
	}

	function getJobItem() {
		return FALSE;
	}

	function setJobItem($ids) {
		return FALSE;
	}

	function isActive( $in_epoch, $out_epoch = NULL, $calculate_policy_obj = NULL ) {
		if ( $out_epoch == '' ) {
			$out_epoch = $in_epoch;
		}

		//Debug::text(' In: '. TTDate::getDate('DATE+TIME', $in_epoch) .' Out: '. TTDate::getDate('DATE+TIME', $out_epoch), __FILE__, __LINE__, __METHOD__, 10);
		$i = $in_epoch;
		$last_iteration = 0;
		//Make sure we loop on the in_epoch, out_epoch and every day inbetween. $last_iteration allows us to always hit the out_epoch.
		while( $i <= $out_epoch AND $last_iteration <= 1 ) {
			//Debug::text(' I: '. TTDate::getDate('DATE+TIME', $i), __FILE__, __LINE__, __METHOD__, 10);
			if ( $this->getIncludeHolidayType() > 10 AND is_object( $calculate_policy_obj ) ) {
				//$is_holiday = $this->isHoliday( $i, $user_id );
				$is_holiday = ( $calculate_policy_obj->filterHoliday( $i ) !== FALSE ) ? TRUE : FALSE;
			} else {
				$is_holiday = FALSE;
			}

			if ( ( $this->getIncludeHolidayType() == 10 AND $this->isActiveDate($i) == TRUE AND $this->isActiveDayOfWeek($i) == TRUE )
					OR ( $this->getIncludeHolidayType() == 20 AND ( ( $this->isActiveDate($i) == TRUE AND $this->isActiveDayOfWeek($i) == TRUE ) OR $is_holiday == TRUE ) )
					OR ( $this->getIncludeHolidayType() == 30 AND ( ( $this->isActiveDate($i) == TRUE AND $this->isActiveDayOfWeek($i) == TRUE ) AND $is_holiday == FALSE ) )
				) {
				Debug::text('Active Date/DayOfWeek: '. TTDate::getDate('DATE+TIME', $i), __FILE__, __LINE__, __METHOD__, 10);

				return TRUE;
			}

			//If there is more than one day between $i and $out_epoch, add one day to $i.
			if ( $i < ( $out_epoch - 86400 ) ) {
				$i += 86400;
			} else {
				//When less than one day untl $out_epoch, skip to $out_epoch and loop once more.
				$i = $out_epoch;
				$last_iteration++;
			}
		}

		Debug::text('NOT Active Date/DayOfWeek: '. TTDate::getDate('DATE+TIME', $i), __FILE__, __LINE__, __METHOD__, 10);

		return FALSE;
	}

	//Check if this premium policy is restricted by time.
	//If its not, we can apply it to non-punched hours.
	function isTimeRestricted() {
		//If time restrictions account for over 23.5 hours, then we assume
		//that this policy is not time restricted at all.
		$time_diff = abs( $this->getEndTime() - $this->getStartTime() );
		if ( $time_diff > 0 AND $time_diff < (23.5 * 3600) ) {
			return TRUE;
		}

		return FALSE;
	}

	function isHourRestricted() {
		if ( $this->getDailyTriggerTime() > 0 OR $this->getWeeklyTriggerTime() > 0 OR $this->getMaximumDailyTriggerTime() > 0 OR $this->getMaximumWeeklyTriggerTime() > 0 ) {
			return TRUE;
		}

		return FALSE;
	}

	function isDayOfWeekRestricted() {
		if ( $this->getSun() == FALSE OR $this->getMon() == FALSE OR $this->getTue() == FALSE OR $this->getWed() == FALSE OR $this->getThu() == FALSE OR $this->getFri() == FALSE OR $this->getSat() == FALSE ) {
			return TRUE;
		}

		return FALSE;
	}

	function getPartialPunchTotalTime( $in_epoch, $out_epoch, $total_time, $calculate_policy_obj = NULL ) {
		$retval = $total_time;

		//If a premium policy only activates on say Sat, but the Start/End times are blank/0,
		//it won't calculate just the time on Sat if an employee works from Fri 8:00PM to Sat 3:00AM.
		//So check for StartTime/EndTime > 0 OR isDayOfWeekRestricted()
		//Then if no StartTime/EndTime is set, force it to cover the entire 24hr period.
		if ( $this->isActiveTime( $in_epoch, $out_epoch, $calculate_policy_obj )
				AND $this->getIncludePartialPunch() == TRUE
				AND (
						( $this->getStartTime() > 0 OR $this->getEndTime() > 0 )
						OR $this->isDayOfWeekRestricted() == TRUE
					)
				) {
			if ( $this->getStartTime() == '' ) {
				$this->setStartTime( strtotime( '12:00 AM' ) );
			}
			if ( $this->getEndTime() == '' ) {
				$this->setEndTime( strtotime( '11:59 PM' ) );
			}

			Debug::text(' Checking for Active Time with: In: '. TTDate::getDate('DATE+TIME', $in_epoch) .' Out: '. TTDate::getDate('DATE+TIME', $out_epoch), __FILE__, __LINE__, __METHOD__, 10);

			Debug::text(' Raw Start TimeStamp('.$this->getStartTime(TRUE).'): '. TTDate::getDate('DATE+TIME', $this->getStartTime() ) .' Raw End TimeStamp('.$this->getEndTime(TRUE).'): '. TTDate::getDate('DATE+TIME', $this->getEndTime() ), __FILE__, __LINE__, __METHOD__, 10);
			$start_time_stamp = TTDate::getTimeLockedDate( $this->getStartTime(), $in_epoch);
			$end_time_stamp = TTDate::getTimeLockedDate( $this->getEndTime(), $in_epoch);

			//Check if end timestamp is before start, if it is, move end timestamp to next day.
			if ( $end_time_stamp < $start_time_stamp ) {
				Debug::text(' Moving End TimeStamp to next day.', __FILE__, __LINE__, __METHOD__, 10);
				$end_time_stamp = TTDate::getTimeLockedDate( $this->getEndTime(), ( TTDate::getMiddleDayEpoch($end_time_stamp) + 86400 ) ); //Due to DST, jump ahead 1.5 days, then jump back to the time locked date.
			}

			//Handle the last second of the day, so punches that span midnight like 11:00PM to 6:00AM get a full 1 hour for the time before midnight, rather than 59mins and 59secs.
			if ( TTDate::getHour( $end_time_stamp ) == 23 AND TTDate::getMinute( $end_time_stamp ) == 59 ) {
				$end_time_stamp = ( TTDate::getEndDayEpoch( $end_time_stamp ) + 1 );
				Debug::text(' End time stamp is within the last minute of day, make sure we include the last second of the day as well.', __FILE__, __LINE__, __METHOD__, 10);
			}

			$retval = 0;
			for( $i = (TTDate::getMiddleDayEpoch($start_time_stamp) - 86400); $i <= (TTDate::getMiddleDayEpoch($end_time_stamp) + 86400); $i += 86400 ) {
				//Due to DST, we need to make sure we always lock time of day so its the exact same. Without this it can walk by one hour either way.
				$tmp_start_time_stamp = TTDate::getTimeLockedDate( $this->getStartTime(), $i);
				$next_i = ( $tmp_start_time_stamp + ($end_time_stamp - $start_time_stamp) ); //Get next date to base the end_time_stamp on, and to calculate if we need to adjust for DST.

				//$tmp_end_time_stamp = TTDate::getTimeLockedDate( $end_time_stamp, ( $next_i + ( TTDate::getDSTOffset( $tmp_start_time_stamp, $next_i ) * -1 ) ) ); //Use $end_time_stamp as it can be modified above due to being near midnight. Also adjust for DST by reversing it.
				$tmp_end_time_stamp = TTDate::getTimeLockedDate( $end_time_stamp, $next_i ); //Use $end_time_stamp as it can be modified above due to being near midnight.
				if ( $this->isActiveTime( $tmp_start_time_stamp, $tmp_end_time_stamp, $calculate_policy_obj ) == TRUE ) {
					$retval += TTDate::getTimeOverLapDifference( $tmp_start_time_stamp, $tmp_end_time_stamp, $in_epoch, $out_epoch );
					Debug::text(' Calculating partial time against Start TimeStamp: '. TTDate::getDate('DATE+TIME', $tmp_start_time_stamp) .' End TimeStamp: '. TTDate::getDate('DATE+TIME', $tmp_end_time_stamp) .' Total: '. $retval, __FILE__, __LINE__, __METHOD__, 10);
				} else {
					Debug::text(' Not Active on this day: '. TTDate::getDate('DATE+TIME', $i), __FILE__, __LINE__, __METHOD__, 10);
				}
			}
		} else {
			Debug::text('   Not calculating partial punch, just using total time...', __FILE__, __LINE__, __METHOD__, 10);
		}

		Debug::text(' Partial Punch Total Time: '. $retval, __FILE__, __LINE__, __METHOD__, 10);
		return $retval;
	}

	//Check if this time is within the start/end time.
	function isActiveTime( $in_epoch, $out_epoch, $calculate_policy_obj = NULL ) {
		Debug::text(' Checking for Active Time with: In: '. TTDate::getDate('DATE+TIME', $in_epoch) .' Out: '. TTDate::getDate('DATE+TIME', $out_epoch), __FILE__, __LINE__, __METHOD__, 10);

		Debug::text(' PP Raw Start TimeStamp('.$this->getStartTime(TRUE).'): '. TTDate::getDate('DATE+TIME', $this->getStartTime() ) .' Raw End TimeStamp: '. TTDate::getDate('DATE+TIME', $this->getEndTime() ), __FILE__, __LINE__, __METHOD__, 10);
		$start_time_stamp = TTDate::getTimeLockedDate( $this->getStartTime(), $in_epoch); //Base the end time on day of the in_epoch.
		$end_time_stamp = TTDate::getTimeLockedDate( $this->getEndTime(), $in_epoch); //Base the end time on day of the in_epoch.

		//Check if end timestamp is before start, if it is, move end timestamp to next day.
		if ( $end_time_stamp < $start_time_stamp ) {
			Debug::text(' Moving End TimeStamp to next day.', __FILE__, __LINE__, __METHOD__, 10);
			$end_time_stamp = TTDate::getTimeLockedDate( $this->getEndTime(), ( TTDate::getMiddleDayEpoch($end_time_stamp) + 86400 ) ); //Due to DST, jump ahead 1.5 days, then jump back to the time locked date.
		}

		Debug::text(' Start TimeStamp: '. TTDate::getDate('DATE+TIME', $start_time_stamp) .' End TimeStamp: '. TTDate::getDate('DATE+TIME', $end_time_stamp), __FILE__, __LINE__, __METHOD__, 10);
		//Check to see if start/end time stamps are not set or are equal, we always return TRUE if they are.
		if ( $this->getIncludeHolidayType() == 10
				AND ( $start_time_stamp == '' OR $end_time_stamp == '' OR $start_time_stamp == $end_time_stamp ) ) {
			Debug::text(' Start/End time not set, assume it always matches.', __FILE__, __LINE__, __METHOD__, 10);
			return TRUE;
		} else {
			//If the premium policy start/end time spans midnight, there could be multiple windows to check
			//where the premium policy applies, make sure we check all windows.
			for( $i = (TTDate::getMiddleDayEpoch($start_time_stamp) - 86400); $i <= (TTDate::getMiddleDayEpoch($end_time_stamp) + 86400); $i += 86400 ) {
				$tmp_start_time_stamp = TTDate::getTimeLockedDate( $this->getStartTime(), $i);
				$next_i = ( $tmp_start_time_stamp + ($end_time_stamp - $start_time_stamp) ); //Get next date to base the end_time_stamp on, and to calculate if we need to adjust for DST.
				$tmp_end_time_stamp = TTDate::getTimeLockedDate( $end_time_stamp, ( $next_i + ( TTDate::getDSTOffset( $tmp_start_time_stamp, $next_i ) * -1 ) ) ); //Use $end_time_stamp as it can be modified above due to being near midnight. Also adjust for DST by reversing it.
				if ( $this->isActive( $tmp_start_time_stamp, $tmp_end_time_stamp, $calculate_policy_obj ) == TRUE ) {
					Debug::text(' Checking against Start TimeStamp: '. TTDate::getDate('DATE+TIME', $tmp_start_time_stamp) .'('.$tmp_start_time_stamp.') End TimeStamp: '. TTDate::getDate('DATE+TIME', $tmp_end_time_stamp) .'('.$tmp_end_time_stamp.')', __FILE__, __LINE__, __METHOD__, 10);
					if ( $this->getIncludePartialPunch() == TRUE AND TTDate::isTimeOverLap( $in_epoch, $out_epoch, $tmp_start_time_stamp, $tmp_end_time_stamp) == TRUE ) {
						//When dealing with partial punches, any overlap whatsoever activates the policy.
						Debug::text(' Partial Punch Within Active Time!', __FILE__, __LINE__, __METHOD__, 10);
						return TRUE;
					} elseif ( $in_epoch >= $tmp_start_time_stamp AND $out_epoch <= $tmp_end_time_stamp ) {
						//Non partial punches, they must punch in AND out (entire shift) within the time window.
						Debug::text(' Within Active Time!', __FILE__, __LINE__, __METHOD__, 10);
						return TRUE;
					} elseif ( ( $start_time_stamp == '' OR $end_time_stamp == '' OR $start_time_stamp == $end_time_stamp ) ) { //Must go AFTER the above IF statements.
						//When IncludeHolidayType != 10 this trigger here.
						Debug::text(' No Start/End Date/Time!', __FILE__, __LINE__, __METHOD__, 10);
						return TRUE;
					} else {
						Debug::text(' No match...', __FILE__, __LINE__, __METHOD__, 10);
					}
				} else {
					Debug::text(' Not Active on this day: Start: '. TTDate::getDate('DATE+TIME', $tmp_start_time_stamp) .' End: '. TTDate::getDate('DATE+TIME', $tmp_end_time_stamp), __FILE__, __LINE__, __METHOD__, 10);
				}
			}
		}

		Debug::text(' NOT Within Active Time!', __FILE__, __LINE__, __METHOD__, 10);

		return FALSE;
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
	function isActiveDate( $epoch, $maximum_shift_time = 0 ) {
		//Debug::text(' Checking for Active Date: '. TTDate::getDate('DATE+TIME', $epoch) .' PP Start Date: '. TTDate::getDate('DATE+TIME', $this->getStartDate()) .' Maximum Shift Time: '. $maximum_shift_time, __FILE__, __LINE__, __METHOD__, 10);
		$epoch = TTDate::getBeginDayEpoch( $epoch );

		if ( $this->getStartDate() == '' AND $this->getEndDate() == '') {
			return TRUE;
		}

		if ( $epoch >= ( TTDate::getBeginDayEpoch( (int)$this->getStartDate() ) - (int)$maximum_shift_time )
				AND ( $epoch <= ( TTDate::getEndDayEpoch( (int)$this->getEndDate() ) + (int)$maximum_shift_time ) OR $this->getEndDate() == '' ) ) {
			return TRUE;
		}

		return FALSE;
	}

	//Check if this day of the week is active
	function isActiveDayOfWeek($epoch) {
		//Debug::text(' Checking for Active Day of Week.', __FILE__, __LINE__, __METHOD__, 10);
		$day_of_week = strtolower(date('D', $epoch));

		switch ($day_of_week) {
			case 'sun':
				if ( $this->getSun() == TRUE ) {
					return TRUE;
				}
				break;
			case 'mon':
				if ( $this->getMon() == TRUE ) {
					return TRUE;
				}
				break;
			case 'tue':
				if ( $this->getTue() == TRUE ) {
					return TRUE;
				}
				break;
			case 'wed':
				if ( $this->getWed() == TRUE ) {
					return TRUE;
				}
				break;
			case 'thu':
				if ( $this->getThu() == TRUE ) {
					return TRUE;
				}
				break;
			case 'fri':
				if ( $this->getFri() == TRUE ) {
					return TRUE;
				}
				break;
			case 'sat':
				if ( $this->getSat() == TRUE ) {
					return TRUE;
				}
				break;
		}

		return FALSE;
	}

	function Validate( $ignore_warning = TRUE ) {
		if ( $this->getDeleted() != TRUE AND $this->Validator->getValidateOnly() == FALSE ) { //Don't check the below when mass editing.
			if ( $this->getName() == '' ) {
				$this->Validator->isTRUE(	'name',
											FALSE,
											TTi18n::gettext('Please specify a name') );
			}

			if ( $this->getPayCode() == 0 ) {
				$this->Validator->isTRUE(	'pay_code_id',
											FALSE,
											TTi18n::gettext('Please choose a Pay Code') );
			}

			//Make sure Pay Formula Policy is defined somewhere.
			if ( $this->getPayFormulaPolicy() == 0 AND $this->getPayCode() > 0 AND ( !is_object( $this->getPayCodeObject() ) OR ( is_object( $this->getPayCodeObject() ) AND $this->getPayCodeObject()->getPayFormulaPolicy() == 0 ) ) ) {
					$this->Validator->isTRUE(	'pay_formula_policy_id',
												FALSE,
												TTi18n::gettext('Selected Pay Code does not have a Pay Formula Policy defined'));
			}
		}

		if ( $this->getDeleted() == TRUE ) {
			//Check to make sure nothing else references this policy, so we can be sure its okay to delete it.
			$pglf = TTnew( 'PolicyGroupListFactory' );
			$pglf->getAPISearchByCompanyIdAndArrayCriteria( $this->getCompany(), array('premium_policy' => $this->getId() ), 1 );
			if ( $pglf->getRecordCount() > 0 ) {
				$this->Validator->isTRUE( 'in_use',
										  FALSE,
										  TTi18n::gettext( 'This policy is currently in use' ) . ' ' . TTi18n::gettext( 'by policy groups' ) );
			}
		}

		return TRUE;
	}

	function preSave() {
		if ( $this->getBranchSelectionType() === FALSE OR $this->getBranchSelectionType() < 10 ) {
			$this->setBranchSelectionType(10); //All
		}
		if ( $this->getDepartmentSelectionType() === FALSE OR $this->getDepartmentSelectionType() < 10 ) {
			$this->setDepartmentSelectionType(10); //All
		}
		if ( $this->getJobGroupSelectionType() === FALSE OR $this->getJobGroupSelectionType() < 10 ) {
			$this->setJobGroupSelectionType(10); //All
		}
		if ( $this->getJobSelectionType() === FALSE OR $this->getJobSelectionType() < 10 ) {
			$this->setJobSelectionType(10); //All
		}
		if ( $this->getJobItemGroupSelectionType() === FALSE OR $this->getJobItemGroupSelectionType() < 10 ) {
			$this->setJobItemGroupSelectionType(10); //All
		}
		if ( $this->getJobItemSelectionType() === FALSE OR $this->getJobItemSelectionType() < 10 ) {
			$this->setJobItemSelectionType(10); //All
		}

		if ( $this->getPayType() === FALSE ) {
			$this->setPayType( 10 );
		}

		$this->data['rate'] = 0; //This is required until the schema removes the NOT NULL constraint.

		return TRUE;
	}

	function postSave() {
		$this->removeCache( $this->getId() );

		return TRUE;
	}

	function setObjectFromArray( $data ) {
		if ( is_array( $data ) ) {
			$variable_function_map = $this->getVariableToFunctionMap();
			foreach( $variable_function_map as $key => $function ) {
				if ( isset($data[$key]) ) {

					$function = 'set'.$function;
					switch( $key ) {
						case 'start_date':
						case 'end_date':
						case 'start_time':
						case 'end_time':
							if ( method_exists( $this, $function ) ) {
								$this->$function( TTDate::parseDateTime( $data[$key] ) );
							}
							break;
						default:
							if ( method_exists( $this, $function ) ) {
								$this->$function( $data[$key] );
							}
							break;
					}
				}
			}

			$this->setCreatedAndUpdatedColumns( $data );

			return TRUE;
		}

		return FALSE;
	}

	function getObjectAsArray( $include_columns = NULL ) {
		$data = array();
		$variable_function_map = $this->getVariableToFunctionMap();
		if ( is_array( $variable_function_map ) ) {
			foreach( $variable_function_map as $variable => $function_stub ) {
				if ( $include_columns == NULL OR ( isset($include_columns[$variable]) AND $include_columns[$variable] == TRUE ) ) {

					$function = 'get'.$function_stub;
					switch( $variable ) {
						case 'in_use':
							$data[$variable] = $this->getColumn( $variable );
							break;
						case 'type':
						case 'pay_type':
						case 'branch_selection_type':
						case 'department_selection_type':
						case 'job_group_selection_type':
						case 'job_selection_type':
						case 'job_item_group_selection_type':
						case 'job_item_selection_type':
							$function = 'get'. str_replace('_', '', $variable);
							if ( method_exists( $this, $function ) ) {
								$data[$variable] = Option::getByKey( $this->$function(), $this->getOptions( $variable ) );
							}
							break;
						case 'start_date':
						case 'end_date':
							if ( method_exists( $this, $function ) ) {
								$data[$variable] = TTDate::getAPIDate( 'DATE', $this->$function() );
							}
							break;
						case 'start_time':
						case 'end_time':
							if ( method_exists( $this, $function ) ) {
								$data[$variable] = TTDate::getAPIDate( 'TIME', $this->$function() );
							}
							break;
						default:
							if ( method_exists( $this, $function ) ) {
								$data[$variable] = $this->$function();
							}
							break;
					}

				}
			}
			$this->getCreatedAndUpdatedColumns( $data, $include_columns );
		}

		return $data;
	}

	function addLog( $log_action ) {
		return TTLog::addEntry( $this->getId(), $log_action, TTi18n::getText('Premium Policy'), NULL, $this->getTable(), $this );
	}
}
?>
