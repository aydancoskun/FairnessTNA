<?php
/*********************************************************************************
 * FairnessTNA is a Workforce Management program forked from TimeTrex in 2013,
 * copyright Aydan Coskun. Original code base is copyright TimeTrex Software Inc.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * You can contact Aydan Coskun via issue tracker on github.com/aydancoskun
 ********************************************************************************/


/**
 * @package Modules\Policy
 */
class AccrualPolicyFactory extends Factory {
	protected $table = 'accrual_policy';
	protected $pk_sequence_name = 'accrual_policy_id_seq'; //PK Sequence name

	protected $company_obj = NULL;
	protected $milestone_objs = NULL;
	protected $contributing_shift_policy_obj = NULL;
	protected $user_modifier_obj = NULL;
	protected $length_of_service_contributing_pay_code_policy_obj = NULL;

	/**
	 * @param $name
	 * @param null $parent
	 * @return array|null
	 */
	function _getFactoryOptions( $name, $parent = NULL ) {
		$retval = NULL;
		switch( $name ) {
			case 'type':
				$retval = array(
										//10 => TTi18n::gettext('Standard'), //No longer required after v8.0
										20 => TTi18n::gettext('Calendar Based'),
										30 => TTi18n::gettext('Hour Based'),
									);
				break;
			case 'apply_frequency':
				$retval = array(
										10 => TTi18n::gettext('each Pay Period'),
										20 => TTi18n::gettext('Annually'),
										25 => TTi18n::gettext('Quarterly'),
										30 => TTi18n::gettext('Monthly'),
										40 => TTi18n::gettext('Weekly'),
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
								'type',
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
				$retval = array(
								);
				break;
		}

		return $retval;
	}

	/**
	 * @param $data
	 * @return array
	 */
	function _getVariableToFunctionMap( $data ) {
			$variable_function_map = array(
											'id' => 'ID',
											'company_id' => 'Company',
											'type_id' => 'Type',
											'type' => FALSE,
											'accrual_policy_account_id' => 'AccrualPolicyAccount',
											'accrual_policy_account' => FALSE,
											'contributing_shift_policy_id' => 'ContributingShiftPolicy',
											'contributing_shift_policy' => FALSE,
											'length_of_service_contributing_pay_code_policy_id' => 'LengthOfServiceContributingPayCodePolicy',
											'length_of_service_contributing_pay_code_policy' => FALSE,
											'name' => 'Name',
											'description' => 'Description',
											'enable_pay_stub_balance_display' => 'EnablePayStubBalanceDisplay',
											'minimum_time' => 'MinimumTime',
											'maximum_time' => 'MaximumTime',
											'apply_frequency' => 'ApplyFrequency',
											'apply_frequency_id' => 'ApplyFrequency', //Must go after apply_frequency, so its set last.
											'apply_frequency_month' => 'ApplyFrequencyMonth',
											'apply_frequency_day_of_month' => 'ApplyFrequencyDayOfMonth',
											'apply_frequency_day_of_week' => 'ApplyFrequencyDayOfWeek',
											'apply_frequency_quarter_month' => 'ApplyFrequencyQuarterMonth',
											'apply_frequency_hire_date' => 'ApplyFrequencyHireDate',
											'enable_opening_balance' => 'EnableOpeningBalance',
											'enable_pro_rate_initial_period' => 'EnableProRateInitialPeriod',
											'milestone_rollover_hire_date' => 'MilestoneRolloverHireDate',
											'milestone_rollover_month' => 'MilestoneRolloverMonth',
											'milestone_rollover_day_of_month' => 'MilestoneRolloverDayOfMonth',
											'minimum_employed_days' => 'MinimumEmployedDays',
											'in_use' => FALSE,
											'deleted' => 'Deleted',
											);
			return $variable_function_map;
	}

	/**
	 * @return bool
	 */
	function getCompanyObject() {
		return $this->getGenericObject( 'CompanyListFactory', $this->getCompany(), 'company_obj' );
	}

	/**
	 * @return bool
	 */
	function getContributingShiftPolicyObject() {
		return $this->getGenericObject( 'ContributingShiftPolicyListFactory', $this->getContributingShiftPolicy(), 'contributing_shift_policy_obj' );
	}

	/**
	 * @return bool
	 */
	function getLengthOfServiceContributingPayCodePolicyObject() {
		return $this->getGenericObject( 'ContributingPayCodePolicyListFactory', $this->getLengthOfServiceContributingPayCodePolicy(), 'length_of_service_contributing_pay_code_policy_obj' );
	}

	/**
	 * @return bool|mixed
	 */
	function getCompany() {
		return $this->getGenericDataValue( 'company_id' );
	}

	/**
	 * @param string $value UUID
	 * @return bool
	 */
	function setCompany( $value) {
		$value = TTUUID::castUUID( $value );

		Debug::Text('Company ID: '. $value, __FILE__, __LINE__, __METHOD__, 10);
		return $this->setGenericDataValue( 'company_id', $value );
	}

	/**
	 * @return bool|int
	 */
	function getType() {
		return $this->getGenericDataValue( 'type_id' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setType( $value) {
		$value = (int)trim($value);
		return $this->setGenericDataValue( 'type_id', $value );
	}


	/**
	 * @return bool|mixed
	 */
	function getAccrualPolicyAccount() {
		return $this->getGenericDataValue( 'accrual_policy_account_id' );
	}

	/**
	 * @param string $value UUID
	 * @return bool
	 */
	function setAccrualPolicyAccount( $value) {
		$value = TTUUID::castUUID( $value );
		return $this->setGenericDataValue( 'accrual_policy_account_id', $value );
	}

	/**
	 * This is the contributing shifts used for Hour Based accrual policies.
	 * @return bool|mixed
	 */
	function getContributingShiftPolicy() {
		return $this->getGenericDataValue( 'contributing_shift_policy_id' );
	}

	/**
	 * @param string $value UUID
	 * @return bool
	 */
	function setContributingShiftPolicy( $value) {
		$value = TTUUID::castUUID( $value );
		return $this->setGenericDataValue( 'contributing_shift_policy_id', $value );
	}

	/**
	 * This is strictly used to determine milestones with active after X hours.
	 * @return bool|mixed
	 */
	function getLengthOfServiceContributingPayCodePolicy() {
		return $this->getGenericDataValue( 'length_of_service_contributing_pay_code_policy_id' );
	}

	/**
	 * @param string $value UUID
	 * @return bool
	 */
	function setLengthOfServiceContributingPayCodePolicy( $value) {
		$value = TTUUID::castUUID( $value );
		return $this->setGenericDataValue( 'length_of_service_contributing_pay_code_policy_id', $value );
	}

	/**
	 * @param $name
	 * @return bool
	 */
	function isUniqueName( $name) {
		$name = trim($name);
		if ( $name == '' ) {
			return FALSE;
		}

		$ph = array(
					'company_id' => TTUUID::castUUID($this->getCompany()),
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

	/**
	 * @return bool|mixed
	 */
	function getName() {
		return $this->getGenericDataValue( 'name' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setName( $value) {
		$value = trim($value);
		return $this->setGenericDataValue( 'name', $value );
	}

	/**
	 * @return bool|mixed
	 */
	function getDescription() {
		return $this->getGenericDataValue( 'description' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setDescription( $value) {
		$value = trim($value);
		return $this->setGenericDataValue( 'description', $value );
	}

	/**
	 * @return bool
	 */
	function getEnablePayStubBalanceDisplay() {
		return $this->fromBool( $this->getGenericDataValue( 'enable_pay_stub_balance_display' ) );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setEnablePayStubBalanceDisplay( $value) {
		return $this->setGenericDataValue( 'enable_pay_stub_balance_display', $this->toBool($value) );
	}

	/**
	 * @return bool|int
	 */
	function getMinimumTime() {
		return (int)$this->getGenericDataValue( 'minimum_time' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setMinimumTime( $value) {
		$value = trim($value);

		if	( empty($value) ) {
			$value = 0;
		}
		return $this->setGenericDataValue( 'minimum_time', $value );
	}

	/**
	 * @return bool|int
	 */
	function getMaximumTime() {
		return (int)$this->getGenericDataValue( 'maximum_time' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setMaximumTime( $value) {
		$value = trim($value);

		if	( empty($value) ) {
			$value = 0;
		}
		return $this->setGenericDataValue( 'maximum_time', $value );
	}

	//
	// Calendar
	//

	/**
	 * @return bool|int
	 */
	function getApplyFrequency() {
		return $this->getGenericDataValue( 'apply_frequency_id' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setApplyFrequency( $value) {
		$value = (int)trim($value);
		return $this->setGenericDataValue( 'apply_frequency_id', $value );
	}

	/**
	 * @return bool|mixed
	 */
	function getApplyFrequencyMonth() {
		return $this->getGenericDataValue( 'apply_frequency_month' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setApplyFrequencyMonth( $value) {
		$value = trim($value);
		return $this->setGenericDataValue( 'apply_frequency_month', $value );
	}

	/**
	 * @return bool|mixed
	 */
	function getApplyFrequencyDayOfMonth() {
		return $this->getGenericDataValue( 'apply_frequency_day_of_month' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setApplyFrequencyDayOfMonth( $value) {
		$value = trim($value);
		return $this->setGenericDataValue( 'apply_frequency_day_of_month', $value );
	}

	/**
	 * @return bool|mixed
	 */
	function getApplyFrequencyDayOfWeek() {
		return $this->getGenericDataValue( 'apply_frequency_day_of_week' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setApplyFrequencyDayOfWeek( $value) {
		$value = trim($value);
		return $this->setGenericDataValue( 'apply_frequency_day_of_week', $value );
	}

	/**
	 * @return bool|mixed
	 */
	function getApplyFrequencyQuarterMonth() {
		return $this->getGenericDataValue( 'apply_frequency_quarter_month' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setApplyFrequencyQuarterMonth( $value) {
		$value = trim($value);
		return $this->setGenericDataValue( 'apply_frequency_quarter_month', $value );
	}

	/**
	 * @return bool
	 */
	function getApplyFrequencyHireDate() {
		return $this->fromBool( $this->getGenericDataValue( 'apply_frequency_hire_date' ) );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setApplyFrequencyHireDate( $value) {
		return $this->setGenericDataValue( 'apply_frequency_hire_date', $this->toBool($value) );
	}

	/**
	 * @return bool
	 */
	function getEnableProRateInitialPeriod() {
		return $this->fromBool( $this->getGenericDataValue( 'enable_pro_rate_initial_period' ) );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setEnableProRateInitialPeriod( $value) {
		return $this->setGenericDataValue('enable_pro_rate_initial_period', $this->toBool($value) );
	}

	/**
	 * @return bool
	 */
	function getEnableOpeningBalance() {
		return $this->fromBool( $this->getGenericDataValue( 'enable_opening_balance' ) );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setEnableOpeningBalance( $value) {
		return $this->setGenericDataValue( 'enable_opening_balance', $this->toBool($value) );
	}

	/**
	 * @return bool
	 */
	function getMilestoneRolloverHireDate() {
		return $this->fromBool( $this->getGenericDataValue( 'milestone_rollover_hire_date' ) );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setMilestoneRolloverHireDate( $value) {
		return $this->setGenericDataValue( 'milestone_rollover_hire_date', $this->toBool($value) );
	}

	/**
	 * @return bool|mixed
	 */
	function getMilestoneRolloverMonth() {
		return $this->getGenericDataValue( 'milestone_rollover_month' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setMilestoneRolloverMonth( $value) {
		$value = trim($value);
		return $this->setGenericDataValue( 'milestone_rollover_month', $value );
	}

	/**
	 * @return bool|mixed
	 */
	function getMilestoneRolloverDayOfMonth() {
		return $this->getGenericDataValue( 'milestone_rollover_day_of_month' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setMilestoneRolloverDayOfMonth( $value) {
		$value = trim($value);
		return $this->setGenericDataValue( 'milestone_rollover_day_of_month', $value );
	}

	/**
	 * @return bool|int
	 */
	function getMinimumEmployedDays() {
		return $this->getGenericDataValue( 'minimum_employed_days' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setMinimumEmployedDays( $value ) {
		$value = (int)trim($value);
		return $this->setGenericDataValue( 'minimum_employed_days', (int)$this->Validator->stripNon32bitInteger( $value ) );
	}

	/**
	 * @param object $u_obj
	 * @param object $modifier_obj
	 * @return bool
	 */
	function getModifiedHireDate( $u_obj, $modifier_obj = NULL ) {
		if ( !is_object($u_obj) ) {
			return FALSE;
		}

		if ( !is_object( $modifier_obj ) ) {
			$modifier_obj = $this->getAccrualPolicyUserModifierObject( $u_obj );
		}

		if ( is_object($modifier_obj) AND method_exists( $modifier_obj, 'getLengthOfServiceDate' ) AND $modifier_obj->getLengthOfServiceDate() != '' ) {
			$user_hire_date = $modifier_obj->getLengthOfServiceDate();
			//Debug::Text('Using Modifier LengthOfService Date: '. TTDate::getDate('DATE+TIME', $user_hire_date ) .' Hire Date: '. TTDate::getDate('DATE+TIME', $u_obj->getHireDate() ), __FILE__, __LINE__, __METHOD__, 10);
		} else {
			$user_hire_date = $u_obj->getHireDate();
			//Debug::Text('Hire Date: '. TTDate::getDate('DATE+TIME', $user_hire_date ), __FILE__, __LINE__, __METHOD__, 10);
		}

		return $user_hire_date;
	}

	/**
	 * @param object $u_obj
	 * @param object $modifier_obj
	 * @return bool|false|int
	 */
	function getMilestoneRolloverDate( $u_obj = NULL, $modifier_obj = NULL ) {
		if ( !is_object($u_obj) ) {
			return FALSE;
		}

		$user_hire_date = $this->getModifiedHireDate( $u_obj, $modifier_obj );

		if ( $this->getMilestoneRolloverHireDate() == TRUE ) {
			$retval = $user_hire_date;
		} else {
			$user_hire_date_arr = getdate( $user_hire_date );
			$retval = mktime( $user_hire_date_arr['hours'], $user_hire_date_arr['minutes'], $user_hire_date_arr['seconds'], $this->getMilestoneRolloverMonth(), $this->getMilestoneRolloverDayOfMonth(), $user_hire_date_arr['year'] );
		}

		Debug::Text('Milestone Rollover Date: '. TTDate::getDate('DATE+TIME', $retval) .' Hire Date: '. TTDate::getDate('DATE+TIME', $user_hire_date), __FILE__, __LINE__, __METHOD__, 10);
		return TTDate::getBeginDayEpoch( $retval ); //Some hire dates might be at noon, so make sure they are all at midnight.
	}

	/**
	 * @param int $epoch EPOCH
	 * @param object $u_obj
	 * @param bool $use_previous_year_date
	 * @return bool|false|int
	 */
	function getCurrentMilestoneRolloverDate( $epoch, $u_obj = NULL, $use_previous_year_date = FALSE ) {
		if ( !is_object($u_obj) ) {
			return FALSE;
		}

		$user_hire_date = $this->getModifiedHireDate( $u_obj );

		$base_rollover_date = $this->getMilestoneRolloverDate( $u_obj );
		$rollover_date = mktime( 0, 0, 0, TTDate::getMonth( $base_rollover_date ), TTDate::getDayOfMonth( $base_rollover_date ), TTDate::getYear( $epoch ) );

		if ( $rollover_date < $user_hire_date ) {
			$rollover_date = $user_hire_date;
		}

		//If milestone rollover date comes after the current epoch, back date it by one year.
		if ( $use_previous_year_date == TRUE AND $rollover_date > $epoch ) {
			$rollover_date = mktime( 0, 0, 0, TTDate::getMonth($rollover_date), TTDate::getDayOfMonth($rollover_date), (TTDate::getYear($epoch) - 1) );
		}

		Debug::Text('Current Milestone Rollover Date: '. TTDate::getDate('DATE+TIME', $rollover_date) .' Hire Date: '. TTDate::getDate('DATE+TIME', $user_hire_date), __FILE__, __LINE__, __METHOD__, 10);
		return $rollover_date;
	}

	/**
	 * @param $accrual_rate
	 * @param null $annual_pay_periods
	 * @return bool|float|string
	 */
	function getAccrualRatePerTimeFrequency( $accrual_rate, $annual_pay_periods = NULL ) {
		$retval = FALSE;
		switch( $this->getApplyFrequency() ) {
			case 10: //Pay Period
				if ( $annual_pay_periods == '' ) {
					return FALSE;
				}
				$retval = bcdiv( $accrual_rate, $annual_pay_periods, 0);
				break;
			case 20: //Year
				$retval = $accrual_rate;
				break;
			case 25: //Quarter
				$retval = bcdiv( $accrual_rate, 4, 0);
				break;
			case 30: //Month
				$retval = bcdiv( $accrual_rate, 12, 0);
				break;
			case 40: //Week
				$retval = bcdiv( $accrual_rate, 52, 0);
				break;
		}

		//Round to nearest minute, or 15mins?
		//Well, if they accrue 99hrs/year on a weekly basis, rounding to the nearest minute means 98.8hrs/year...
		//Should round to the nearest second instead then.
		//$retval = TTDate::roundTime( $retval, 60, 20 );
		$retval = round($retval, 0);

		Debug::Text('Accrual Rate Per Frequency: '. $retval .' Accrual Rate: '. $accrual_rate .' Pay Periods: '. $annual_pay_periods, __FILE__, __LINE__, __METHOD__, 10);

		return $retval;
	}

	/**
	 * @param int $current_epoch EPOCH
	 * @param $offset
	 * @param object $u_obj
	 * @param int $pay_period_start_date EPOCH
	 * @return bool
	 */
	function inRolloverFrequencyWindow( $current_epoch, $offset, $u_obj, $pay_period_start_date = NULL ) {
		//Use current_epoch mainly for Yearly cases where the rollover date is 01-Nov and the hire date is always right after it, 10-Nov in the next year.
		$rollover_date = $this->getCurrentMilestoneRolloverDate( $current_epoch, $u_obj, FALSE );
		Debug::Text('Rollover Date: '. TTDate::getDate('DATE+TIME', $rollover_date ) .' Current Epoch: '. TTDate::getDate('DATE+TIME', $current_epoch ) .' Hire Date: '. TTDate::getDate( 'DATE+TIME', $u_obj->getHireDate() ), __FILE__, __LINE__, __METHOD__, 10);

		if ( $rollover_date >= ($current_epoch - $offset) AND $rollover_date <= $current_epoch ) {
			//Don't consider the employees (first) hire date to be in the rollover frequency window
			// This should avoid cases where the employee is hired, accrues time on their hire date by working and being assigned to a hour-based accrual policy
			// then the rollover occurs on their hire date and zeros out that time.
			// We still need to calculate other accruals on hire dates though, just not rollover.
			if ( TTDate::getBeginDayEpoch( $rollover_date ) > TTDate::getBeginDayEpoch( $u_obj->getHireDate() ) ) {
				Debug::Text('In rollover frequency window...', __FILE__, __LINE__, __METHOD__, 10);

				return TRUE;
			} else {
				Debug::Text('In rollover frequency window, but on user first hire date, skipping...', __FILE__, __LINE__, __METHOD__, 10);
			}
		}

		Debug::Text('NOT in rollover frequency window...', __FILE__, __LINE__, __METHOD__, 10);
		return FALSE;
	}

	/**
	 * @param int $current_epoch EPOCH
	 * @param $offset
	 * @param int $pay_period_dates EPOCH
	 * @param object $u_obj
	 * @return array|bool
	 */
	function getApplyFrequencyWindowDates( $current_epoch, $offset, $pay_period_dates = NULL, $u_obj = NULL ) {
		$hire_date = $this->getMilestoneRolloverDate( $u_obj );

		$retval = FALSE;
		switch( $this->getApplyFrequency() ) {
			case 10: //Pay Period
				$retval = array('start_date' => $pay_period_dates['start_date'], 'end_date' => $pay_period_dates['end_date'] );
				break;
			case 20: //Year
				if ( $this->getApplyFrequencyHireDate() == TRUE ) {
					Debug::Text('Hire Date: '. TTDate::getDate('DATE', $hire_date), __FILE__, __LINE__, __METHOD__, 10);
					$year_epoch = mktime( 0, 0, 0, TTDate::getMonth( $hire_date ), TTDate::getDayOfMonth( $hire_date ), TTDate::getYear( $current_epoch ) );
				} else {
					Debug::Text('Static Date', __FILE__, __LINE__, __METHOD__, 10);
					$year_epoch = mktime( 0, 0, 0, $this->getApplyFrequencyMonth(), $this->getApplyFrequencyDayOfMonth(), TTDate::getYear( $current_epoch ) );
					if ( TTDate::getMiddleDayEpoch( $year_epoch ) < TTDate::getMiddleDayEpoch( $u_obj->getHireDate() ) ) {
						$year_epoch = strtotime('+1 year', $year_epoch);
					}
				}
				Debug::Text('Year EPOCH: '. TTDate::getDate('DATE+TIME', $year_epoch), __FILE__, __LINE__, __METHOD__, 10);

				$retval = array('start_date' => strtotime('-1 year', $year_epoch), 'end_date' => $year_epoch );
				break;
			case 25: //Quarter
				$apply_frequency_day_of_month = $this->getApplyFrequencyDayOfMonth();

				//Make sure if they specify the day of month to be 31, that is still works for months with 30, or 28-29 days, assuming 31 basically means the last day of the month
				if ( $apply_frequency_day_of_month > TTDate::getDaysInMonth( $current_epoch ) ) {
					$apply_frequency_day_of_month = TTDate::getDaysInMonth( $current_epoch );
					Debug::Text('Apply frequency day of month exceeds days in this month, using last day of the month instead: '. $apply_frequency_day_of_month, __FILE__, __LINE__, __METHOD__, 10);
				}

				$tmp_epoch = TTDate::getBeginDayEpoch( $current_epoch - $offset );
				$month_offset = ( $this->getApplyFrequencyQuarterMonth() - 1 );
				$year_quarters = array_reverse( TTDate::getYearQuarters( $tmp_epoch, NULL, $apply_frequency_day_of_month ), TRUE );
				foreach( $year_quarters as $quarter => $quarter_dates ) {
					$tmp_quarter_end_date = ( TTDate::getEndDayEpoch( $quarter_dates['end'] ) + 1 );
					//Debug::Text('Quarter: '. $quarter .' Month Offset: '. $month_offset .' Start: '. TTDate::getDate('DATE+TIME', $quarter_dates['start']) .' End: '. TTDate::getDate('DATE+TIME', $tmp_quarter_end_date), __FILE__, __LINE__, __METHOD__, 10);
					if ( $tmp_epoch >= $quarter_dates['start'] AND $tmp_epoch <= $tmp_quarter_end_date ) {
						$start_date_month_epoch = mktime(0, 0, 0, ( TTDate::getMonth( $quarter_dates['start'] ) - $month_offset ), 1, TTDate::getYear( $quarter_dates['start'] ) );
						$end_date_month_epoch = mktime(0, 0, 0, ( TTDate::getMonth( $tmp_quarter_end_date ) - $month_offset ), 1, TTDate::getYear( $tmp_quarter_end_date ) );

						$retval = array('start_date' => mktime(0, 0, 0, ( TTDate::getMonth( $start_date_month_epoch )), ( $this->getApplyFrequencyDayOfMonth() > TTDate::getDaysInMonth( $start_date_month_epoch ) ) ? TTDate::getDaysInMonth( $start_date_month_epoch ) : $this->getApplyFrequencyDayOfMonth(), TTDate::getYear( $start_date_month_epoch ) ),
										'end_date' => mktime(0, 0, 0, ( TTDate::getMonth( $end_date_month_epoch ) ), ( $this->getApplyFrequencyDayOfMonth() > TTDate::getDaysInMonth( $end_date_month_epoch ) ) ? TTDate::getDaysInMonth( $end_date_month_epoch ) : $this->getApplyFrequencyDayOfMonth(), TTDate::getYear( $end_date_month_epoch ) ) );
						unset($start_date_month_epoch, $end_date_month_epoch);
						break;
					}
				}
				break;
			case 30: //Month
				$apply_frequency_day_of_month = $this->getApplyFrequencyDayOfMonth();

				//Make sure if they specify the day of month to be 31, that is still works for months with 30, or 28-29 days, assuming 31 basically means the last day of the month
				if ( $apply_frequency_day_of_month > TTDate::getDaysInMonth( $current_epoch ) ) {
					$apply_frequency_day_of_month = TTDate::getDaysInMonth( $current_epoch );
					Debug::Text('Apply frequency day of month exceeds days in this month, using last day of the month instead: '. $apply_frequency_day_of_month, __FILE__, __LINE__, __METHOD__, 10);
				}

				$month_epoch = mktime( 0, 0, 0, TTDate::getMonth( $current_epoch ), $apply_frequency_day_of_month, TTDate::getYear( $current_epoch ) );
				if ( TTDate::getMiddleDayEpoch( $month_epoch ) < TTDate::getMiddleDayEpoch( $u_obj->getHireDate() ) ) {
					$month_epoch = strtotime('+1 month', $month_epoch);
				}

				Debug::Text('Month EPOCH: '. TTDate::getDate('DATE+TIME', $month_epoch) .'('. $month_epoch .')', __FILE__, __LINE__, __METHOD__, 10);
				$retval = array('start_date' => strtotime('-1 month', $month_epoch), 'end_date' => $month_epoch );
				break;
			case 40: //Week
				$week_epoch = strtotime('this '. TTDate::getDayOfWeekByInt( $this->getApplyFrequencyDayOfWeek() ), ( $current_epoch ) );
				Debug::Text('Current Day Of Week: '. TTDate::getDayOfWeekByInt( TTDate::getDayOfWeek($current_epoch) ) .' Accrual Day of Week: '. TTDate::getDayOfWeekByInt( $this->getApplyFrequencyDayOfWeek() ), __FILE__, __LINE__, __METHOD__, 10);
				$retval = array('start_date' => strtotime('-1 week', $week_epoch), 'end_date' => $week_epoch );
				break;
		}

		if ( is_array($retval) ) {
			Debug::Text('Epoch: '. TTDate::getDate('DATE+TIME', $current_epoch ) .' Window Start Date: '. TTDate::getDate('DATE+TIME', $retval['start_date'] ) .' End Date: '. TTDate::getDate('DATE+TIME', $retval['end_date'] ) .' Offset: '. $offset, __FILE__, __LINE__, __METHOD__, 10);
		} else {
			Debug::Text('Start Date: FALSE End Date: FALSE Offset: '. $offset, __FILE__, __LINE__, __METHOD__, 10);
		}

		return $retval;
	}

	/**
	 * @param $input_amount
	 * @param int $current_epoch EPOCH
	 * @param $offset
	 * @param int $pay_period_dates EPOCH
	 * @param object $u_obj
	 * @return float
	 */
	function getProRateInitialFrequencyWindow( $input_amount, $current_epoch, $offset, $pay_period_dates = NULL, $u_obj = NULL ) {
		$apply_frequency_dates = $this->getApplyFrequencyWindowDates( $current_epoch, $offset, $pay_period_dates, $u_obj );
		if ( isset($apply_frequency_dates['start_date']) AND isset($apply_frequency_dates['end_date']) ) {
			Debug::Text('ProRate Based On: Start Date: '. TTDate::getDate('DATE+TIME', $apply_frequency_dates['start_date'] ) .' End Date: '. TTDate::getDate('DATE+TIME', $apply_frequency_dates['end_date'] ) .' Hire Date: '. TTDate::getDate('DATE+TIME', $u_obj->getHireDate() ), __FILE__, __LINE__, __METHOD__, 10);
			$pro_rate_multiplier = ( ( TTDate::getMiddleDayEpoch( $apply_frequency_dates['end_date'] ) - TTDate::getMiddleDayEpoch( $u_obj->getHireDate() ) ) / ( TTDate::getMiddleDayEpoch( $apply_frequency_dates['end_date'] ) - TTDate::getMiddleDayEpoch( $apply_frequency_dates['start_date'] ) ) );
			if ( $pro_rate_multiplier <= 0 ) {
				$pro_rate_multiplier = 1;
			}
			$amount = round( $input_amount * $pro_rate_multiplier ); //Round to nearest second.
			Debug::Text('ProRated Amount: '. $amount .' ProRate Multiplier: '. $pro_rate_multiplier .' Input Amount: '. $input_amount, __FILE__, __LINE__, __METHOD__, 10);
			return $amount;
		}

		return $input_amount;
	}

	/**
	 * @param int $current_epoch EPOCH
	 * @param $offset
	 * @param int $pay_period_dates EPOCH
	 * @param object $u_obj
	 * @return bool
	 */
	function isInitialApplyFrequencyWindow( $current_epoch, $offset, $pay_period_dates = NULL, $u_obj = NULL ) {
		$apply_frequency_dates = $this->getApplyFrequencyWindowDates( $current_epoch, $offset, $pay_period_dates, $u_obj );
		if ( isset($apply_frequency_dates['start_date']) AND isset($apply_frequency_dates['end_date']) ) {
			if ( is_object($u_obj) AND TTDate::getMiddleDayEpoch( $u_obj->getHireDate() ) >= TTDate::getMiddleDayEpoch( $apply_frequency_dates['start_date'] ) AND TTDate::getMiddleDayEpoch( $u_obj->getHireDate() ) <= TTDate::getMiddleDayEpoch( $apply_frequency_dates['end_date'] ) ) {
				Debug::Text('Initial apply frequency window...', __FILE__, __LINE__, __METHOD__, 10);
				return TRUE;
			}
		}

		Debug::Text('Not initial apply frequency window...', __FILE__, __LINE__, __METHOD__, 10);
		return FALSE;
	}

	/**
	 * @param int $current_epoch EPOCH
	 * @param $offset
	 * @param int $pay_period_dates EPOCH
	 * @param object $u_obj
	 * @return bool
	 */
	function inApplyFrequencyWindow( $current_epoch, $offset, $pay_period_dates = NULL, $u_obj = NULL ) {
		$apply_frequency_dates = $this->getApplyFrequencyWindowDates( $current_epoch, $offset, $pay_period_dates, $u_obj );
		if ( isset($apply_frequency_dates['start_date']) AND isset($apply_frequency_dates['end_date']) ) {
			//If the users timezone differs from system timezone, we need to account for the timezone offset differences on either side of end_date.
			//  This should prevent a timezone switch from force us out of the frequency window.
			$before_offset = 0;
			$after_offset = $offset;

			global $config_vars;
			if ( isset($config_vars['other']['system_timezone']) AND $config_vars['other']['system_timezone'] != TTDate::getTimeZone() ) {
				$old_time_zone = TTDate::getTimeZone();
				TTDate::setTimeZone( $config_vars['other']['system_timezone'] );
				$system_time_zone_offset = TTDate::getTimeZoneOffset();
				TTDate::setTimeZone( $old_time_zone );

				$before_offset = abs( TTDate::getTimeZoneOffset() - $system_time_zone_offset );
				$after_offset -= $before_offset;
				Debug::Text('Timezone is different: System: '. $config_vars['other']['system_timezone'] .' Current: '. TTDate::getTimeZone() .' System TZ Offset: '. $system_time_zone_offset .' Offset: Before: '. $before_offset.' After: '. $after_offset, __FILE__, __LINE__, __METHOD__, 10);
				unset($old_time_zone, $system_time_zone_offset);
			}
			Debug::Text('Epoch: '. TTDate::getDate('DATE+TIME', $current_epoch) .' Start: '. TTDate::getDate('DATE+TIME', ( $apply_frequency_dates['end_date'] - $before_offset ) ) .' End: '. TTDate::getDate('DATE+TIME', ( $apply_frequency_dates['end_date'] + $after_offset ) ), __FILE__, __LINE__, __METHOD__, 10);

			//if ( $apply_frequency_dates['end_date'] >= ($current_epoch - $offset) AND $apply_frequency_dates['end_date'] <= $current_epoch ) {
			if ( $current_epoch >= ( $apply_frequency_dates['end_date'] - $before_offset ) AND $current_epoch <= ( $apply_frequency_dates['end_date'] + $after_offset ) ) {
				//Make sure that if enable opening balance is FALSE, we never apply on the hire date.
				//  What if the frequency is monthly on the 1st and the hire date is also the 1st (employee record was created in advance, hire date post dated to the 1st?)
				//  However to make this work we need to ensure that if the employee record is created on the 1st with the 1st being the hire date, it still accrues when the maintenance jobs run the next day.
				//I think we should accrue on all frequency dates as long as the criteria (ie: minimum employed days) is met.
				//  If they don't want to accrue on the hire date in this case they could just set the minimum employed days to 1.
				//  Unfortunately in the opposite case, where they want to accure on the hire date if its a normal frequency date, there is no work-around.
				//  See #2334
				Debug::Text('    In Apply Frequency...', __FILE__, __LINE__, __METHOD__, 10);
				return TRUE;

//				if ( $this->getEnableOpeningBalance() == FALSE
//					AND ( is_object($u_obj) AND TTDate::getMiddleDayEpoch( $u_obj->getHireDate() ) == TTDate::getMiddleDayEpoch( $current_epoch ) )
//					AND $this->isInitialApplyFrequencyWindow( $current_epoch, $offset, $pay_period_dates, $u_obj ) == TRUE ) {
//					return FALSE;
//				} else {
//					return TRUE;
//				}
			}
		}

		Debug::Text('    NOT In Apply Frequency...', __FILE__, __LINE__, __METHOD__, 10);

		return FALSE;
	}

	/**
	 * @param string $user_id UUID
	 * @param int $start_date EPOCH
	 * @param int $end_date EPOCH
	 * @return bool|int
	 */
	function getWorkedTimeByUserIdAndEndDate( $user_id, $start_date = NULL, $end_date = NULL ) {
		if ( $user_id == '' ) {
			return FALSE;
		}

		if ( $start_date == '' ) {
			$start_date = 1; //Default to beginning of time if hire date is not specified.
		}

		if ( $end_date == '' ) {
			return FALSE;
		}

		$retval = 0;

		$pay_code_policy_obj = $this->getLengthOfServiceContributingPayCodePolicyObject();
		if ( is_object( $pay_code_policy_obj ) ) {
			$udtlf = TTnew( 'UserDateTotalListFactory' ); /** @var UserDateTotalListFactory $udtlf */
			$retval = $udtlf->getTotalTimeSumByUserIDAndPayCodeIDAndStartDateAndEndDate( $user_id, $pay_code_policy_obj->getPayCode(), $start_date, $end_date );
		}

		Debug::Text('Worked Seconds: '. (int)$retval .' Before: '. TTDate::getDate('DATE+TIME', $end_date), __FILE__, __LINE__, __METHOD__, 10);

		return $retval;
	}

	/**
	 * Determine if any milestones have an hour based length of service.
	 * @return bool
	 */
	function isHourBasedLengthOfService() {
		//Cache milestones to speed up getting projected balances.
		if ( !isset($this->milestone_objs[$this->getID()]) ) {
			$this->milestone_objs[$this->getID()] = TTnew( 'AccrualPolicyMilestoneListFactory' );
			$this->milestone_objs[$this->getID()]->getByAccrualPolicyId($this->getId(), NULL, array('length_of_service_days' => 'desc' ) );
		}
		Debug::Text('  Total Accrual Policy Milestones: '. (int)$this->milestone_objs[$this->getID()]->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
		if ( $this->milestone_objs[$this->getID()]->getRecordCount() > 0 ) {
			foreach( $this->milestone_objs[$this->getID()] as $apm_obj ) {
				if ( $apm_obj->getLengthOfServiceUnit() == 50 AND $apm_obj->getLengthOfService() > 0 ) {
					Debug::Text('  Milestone is in Hours...', __FILE__, __LINE__, __METHOD__, 10);
					return TRUE;
				}
			}
		}

		Debug::Text('  No HourBased length of service Milestones...', __FILE__, __LINE__, __METHOD__, 10);
		return FALSE;
	}

	/**
	 * @param object $u_obj
	 * @return bool|null
	 */
	function getAccrualPolicyUserModifierObject( $u_obj ) {
		if ( !is_object( $u_obj ) ) {
			return FALSE;
		}

		if ( getTTProductEdition() >= TT_PRODUCT_PROFESSIONAL ) {
			if ( isset($this->user_modifier_obj) AND is_object($this->user_modifier_obj) AND $this->user_modifier_obj->getUser() == $u_obj->getID() AND $this->user_modifier_obj->getAccrualPolicy() == $this->getID() ) {
				return $this->user_modifier_obj;
			} else {
				$apumlf = TTNew('AccrualPolicyUserModifierListFactory'); /** @var AccrualPolicyUserModifierListFactory $apumlf */
				$apumlf->getByUserIdAndAccrualPolicyId( $u_obj->getId(), $this->getId() );
				if ( $apumlf->getRecordCount() == 1 ) {
					$this->user_modifier_obj = $apumlf->getCurrent();
					Debug::Text('  Found Accrual Policy User Modifier: Length of Service: '. TTDate::getDate('DATE+TIME', $this->user_modifier_obj->getLengthOfServiceDate() )  .' Accrual Rate: '. $this->user_modifier_obj->getAccrualRateModifier(), __FILE__, __LINE__, __METHOD__, 10);
					return $this->user_modifier_obj;
				}
			}
		}

		return FALSE;
	}

	/**
	 * @param object $u_obj
	 * @param int $epoch EPOCH
	 * @param int $worked_time
	 * @param bool $modifier_obj
	 * @return object|bool
	 */
	function getActiveMilestoneObject( $u_obj, $epoch = NULL, $worked_time = 0, $modifier_obj = FALSE ) {
		if ( !is_object( $u_obj ) ) {
			return FALSE;
		}

		if ( $epoch == '' ) {
			$epoch = TTDate::getTime();
		}

		$milestone_obj = FALSE;

		if ( !is_object( $modifier_obj ) ) {
			$modifier_obj = $this->getAccrualPolicyUserModifierObject( $u_obj );
		}

		//Cache milestones to speed up getting projected balances.
		if ( !isset($this->milestone_objs[$this->getID()]) ) {
			$this->milestone_objs[$this->getID()] = TTnew( 'AccrualPolicyMilestoneListFactory' );
			$this->milestone_objs[$this->getID()]->getByAccrualPolicyId($this->getId(), NULL, array('length_of_service_days' => 'desc' ) );
		}
		Debug::Text('  Total Accrual Policy MileStones: '. (int)$this->milestone_objs[$this->getID()]->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
		if ( $this->milestone_objs[$this->getID()]->getRecordCount() > 0 ) {
			$worked_time = NULL;
			$milestone_rollover_date = NULL;

			foreach( $this->milestone_objs[$this->getID()] as $apm_obj ) {
				if ( is_object($modifier_obj) ) {
					$apm_obj = $modifier_obj->getAccrualPolicyMilestoneObjectAfterModifier( $apm_obj );
				}

				if ( $apm_obj->getLengthOfServiceUnit() == 50 AND $apm_obj->getLengthOfService() > 0 ) {
					Debug::Text('  MileStone is in Hours...', __FILE__, __LINE__, __METHOD__, 10);
					//Hour based
					if ( $worked_time == NULL ) {
						//Get users worked time.
						$worked_time = TTDate::getHours( $this->getWorkedTimeByUserIdAndEndDate( $u_obj->getId(), $apm_obj->getLengthOfService(), $epoch ) );
						Debug::Text('  Worked Time: '. $worked_time .'hrs', __FILE__, __LINE__, __METHOD__, 10);
					}

					if ( $worked_time >= $apm_obj->getLengthOfService() ) {
						Debug::Text('  bLength Of Service: '. $apm_obj->getLengthOfService() .'hrs', __FILE__, __LINE__, __METHOD__, 10);
						$milestone_obj = $apm_obj;
						break;
					} else {
						Debug::Text('  Skipping Milestone...', __FILE__, __LINE__, __METHOD__, 10);
					}
				} else {
					Debug::Text('  MileStone is in Days...', __FILE__, __LINE__, __METHOD__, 10);
					//Calendar based
					$milestone_rollover_date = $apm_obj->getLengthOfServiceDate( $this->getMilestoneRolloverDate( $u_obj, $modifier_obj ) );

					//When a milestone first rolls-over, the Maximum rollover won't apply in many cases as it uses the new milestone rollover
					//at that time which often has a higher rollover amount. This only happens the first time the milestone rolls-over.
					//We could avoid this by using just ">" comparison below, but then that affects annual accruals as it will take two years
					//to see the milestone rollover after one year, so that won't work either.
					//if ( $length_of_service_days >= $apm_obj->getLengthOfServiceDays() ) {
					if ( $epoch >= $milestone_rollover_date ) {
						$milestone_obj = $apm_obj;
						Debug::Text('  Using MileStone due to Active After Days: '. $apm_obj->getLengthOfServiceDays() .' or Date: '. TTDate::getDate('DATE+TIME', $milestone_rollover_date ), __FILE__, __LINE__, __METHOD__, 10);
						break;
					} else {
						Debug::Text('  Skipping MileStone...', __FILE__, __LINE__, __METHOD__, 10);
					}
				}
			}
		}
		unset($apm_obj);

		return $milestone_obj;
	}

	/**
	 * @param string $user_id UUID
	 * @param string $accrual_policy_account_id UUID
	 * @return bool|int
	 */
	function getCurrentAccrualBalance( $user_id, $accrual_policy_account_id = NULL ) {
		if ( $user_id == '' ) {
			return FALSE;
		}

		if ( $accrual_policy_account_id == '' ) {
			$accrual_policy_account_id = $this->getAccrualPolicyAccount();
		}

		//Check min/max times of accrual policy.
		$ablf = TTnew( 'AccrualBalanceListFactory' ); /** @var AccrualBalanceListFactory $ablf */
		$ablf->getByUserIdAndAccrualPolicyAccount( $user_id, $accrual_policy_account_id );
		if ( $ablf->getRecordCount() > 0 ) {
			$accrual_balance = $ablf->getCurrent()->getBalance();
		} else {
			$accrual_balance = 0;
		}

		Debug::Text('  Current Accrual Balance: '. $accrual_balance, __FILE__, __LINE__, __METHOD__, 10);

		return $accrual_balance;
	}

	/**
	 * @param object $milestone_obj
	 * @param $total_time
	 * @param $annual_pay_periods
	 * @return bool|float|int|string
	 */
	function calcAccrualAmount( $milestone_obj, $total_time, $annual_pay_periods ) {
		if ( !is_object( $milestone_obj ) ) {
			return FALSE;
		}

		$accrual_amount = 0;
		if ( $this->getType() == 30 AND $total_time > 0 ) {
			//Calculate the fixed amount based off the rate.
			$accrual_amount = bcmul( $milestone_obj->getAccrualRate(), $total_time, 4);
		} elseif ( $this->getType() == 20 ) {
			$accrual_amount = $this->getAccrualRatePerTimeFrequency( $milestone_obj->getAccrualRate(), $annual_pay_periods );
		}
		Debug::Text('  Accrual Amount: '. $accrual_amount .' Total Time: '. $total_time .' Rate: '. $milestone_obj->getAccrualRate() .' Annual Pay Periods: '. $annual_pay_periods, __FILE__, __LINE__, __METHOD__, 10);

		return $accrual_amount;
	}

	/**
	 * Returns an array of pay period start/end dates between a given start/end date.
	 * @param object $pps_obj
	 * @param object $u_obj
	 * @param int $start_epoch EPOCH
	 * @param int $end_epoch EPOCH
	 * @return array
	 */
	function getPayPeriodArray( $pps_obj, $u_obj, $start_epoch, $end_epoch ) {
		$retarr = array();

		$pp_end_date = $end_epoch;

		$pplf = TTNew('PayPeriodListFactory'); /** @var PayPeriodListFactory $pplf */
		$pplf->getByUserIdAndOverlapStartDateAndEndDate( $u_obj->getId(), $start_epoch, $end_epoch);
		if ( $pplf->getRecordCount() > 0 ) {
			foreach( $pplf as $pp_obj ) {
				$retarr[] = array('start_date' => $pp_obj->getStartDate(), 'end_date' => $pp_obj->getEndDate() );
				$pp_end_date = $pp_obj->getEndDate();
			}
		}

		Debug::Text('Last already created Pay Period End Date: '.  TTDate::getDate('DATE+TIME', $pp_end_date ), __FILE__, __LINE__, __METHOD__, 10);

		//$end_epoch is in the future, so continue to try and find pay period schedule dates.
		if ( $pp_end_date <= $end_epoch ) {
			//$pps_obj->setPayPeriodTimeZone();
			while ( $pp_end_date < $end_epoch ) {
				$pps_obj->getNextPayPeriod($pp_end_date);
				$retarr[] = array('start_date' => $pps_obj->getNextStartDate(), 'end_date' => $pps_obj->getNextEndDate() );
				$pp_end_date = $pps_obj->getNextEndDate();
			}
			//$pps_obj->setOriginalTimeZone();
		}

		//Debug::Arr($retarr, 'Pay Period array between Start: '.  TTDate::getDate('DATE+TIME', $start_epoch ) .' End: '.  TTDate::getDate('DATE+TIME', $end_epoch ), __FILE__, __LINE__, __METHOD__, 10);

		return $retarr;
	}

	/**
	 * @param $pay_period_arr
	 * @param int $epoch EPOCH
	 * @return bool|mixed
	 */
	function getPayPeriodDatesFromArray( $pay_period_arr, $epoch ) {
		if ( is_array($pay_period_arr) ) {
			foreach( $pay_period_arr as $pp_dates ) {
				if ( $epoch >= $pp_dates['start_date'] AND $epoch <= $pp_dates['end_date']) {
					return $pp_dates;
				}
			}
		}

		return FALSE;
	}

	/**
	 * $current_amount is the amount of time currently being entered.
	 * $previous_amount is the old amount that is currently be edited.
	 * @param object $u_obj
	 * @param int $epoch EPOCH
	 * @param $current_time
	 * @param int $previous_time
	 * @param bool $other_policy_balance_arr
	 * @return array
	 */
	function getAccrualBalanceWithProjection( $u_obj, $epoch, $current_time, $previous_time = 0, $other_policy_balance_arr = FALSE ) {
		// Available Balance:			   10hrs
		// Current Time:					8hrs
		// Remaining Balance:				2hrs
		//
		// Projected Balance by 01-Jul-12: 15hrs
		// Projected Remaining Balance:		7hrs

		//Debug::Arr($other_policy_balance_arr, 'Current Time: '. TTDate::getHours( $current_time ) .' Previous Time: '. TTDate::getHours( $previous_time ) .' Other Policy Balance Arr: ', __FILE__, __LINE__, __METHOD__, 10);

		//Now that multiple Accrual Policies can deposit to the same account, we need to loop through all accrual policies that affect
		//any given account and add the projected balances together.
		//  Make sure we account for the available balance which is calculated on the first pass.
		$other_policy_projected_balance = 0;
		if ( is_array($other_policy_balance_arr) AND isset($other_policy_balance_arr['projected_balance']) ) {
			$other_policy_projected_balance = ( $other_policy_balance_arr['projected_balance'] - $other_policy_balance_arr['available_balance'] );
			Debug::Text('Other Policy Projected Balance: '. TTDate::getHours( $other_policy_projected_balance ), __FILE__, __LINE__, __METHOD__, 10);

		}

		//Previous time is time already taken into account in the balance, so subtract it here (opposite of adding lower down in remaining balance)
		$available_balance = ( $this->getCurrentAccrualBalance( $u_obj->getID(), $this->getAccrualPolicyAccount() ) - $previous_time );
		$projected_accrual = ( ( $this->getProjectedAccrualAmount( $u_obj, time(), $epoch ) ) + $other_policy_projected_balance );

		$retarr = array(
						'available_balance' => $available_balance,
						'current_time' => $current_time,
						'remaining_balance' => ( $available_balance + $current_time ),
						'projected_balance' => $projected_accrual,
						'projected_remaining_balance' => ( $projected_accrual + ( $current_time - $previous_time ) ),
						);

		Debug::Arr($retarr, 'Projected Accrual Arr: ', __FILE__, __LINE__, __METHOD__, 10);
		Debug::Text('Remaining Balance: '. TTDate::getHours( $retarr['remaining_balance']) .' Projected Remaining Balance: '. TTDate::getHours( $retarr['projected_remaining_balance']), __FILE__, __LINE__, __METHOD__, 10);

		return $retarr;

	}

	/**
	 * @param object $u_obj
	 * @param int $start_epoch EPOCH
	 * @param int $end_epoch EPOCH
	 * @return bool|float|int|string
	 */
	function getProjectedAccrualAmount( $u_obj, $start_epoch, $end_epoch ) {
		$start_epoch = TTDate::getMiddleDayEpoch( $start_epoch );
		$end_epoch = TTDate::getMiddleDayEpoch( $end_epoch );

		$offset = 79200;

		$accrual_amount = 0;

		Debug::Text('Start Date '.	TTDate::getDate('DATE+TIME', $start_epoch ) .' End Date: '.	 TTDate::getDate('DATE+TIME', $end_epoch ), __FILE__, __LINE__, __METHOD__, 10);

		$ppslf = TTNew('PayPeriodScheduleListFactory'); /** @var PayPeriodScheduleListFactory $ppslf */
		$ppslf->getByCompanyIdAndUserId($u_obj->getCompany(), $u_obj->getId() );
		if ( $ppslf->getRecordCount() > 0 ) {
			$pps_obj = $ppslf->getCurrent();

			$initial_accrual_balance = $this->getCurrentAccrualBalance( $u_obj->getID(), $this->getAccrualPolicyAccount() );

			$pay_period_arr = array();
			if ( $this->getApplyFrequency() == 10 ) {
				$pay_period_arr = $this->getPayPeriodArray( $pps_obj, $u_obj, $start_epoch, $end_epoch );
			}

			$accrual_amount = $initial_accrual_balance; //Make the first accrual_amount match the initial accrual balance.
			for( $epoch = $start_epoch; $epoch <= $end_epoch; $epoch += 86400) {
				$epoch = ( TTDate::getBeginDayEpoch( $epoch ) + 7200) ; //This is required because the epoch has to be slightly AFTER the pay period end date, which is 11:59PM.

				//Make sure we pass the returned accrual_amount back into calcAccrualPolicyTime() as the new balance so rollover/maximum balances are all properly handled.
				$accrual_amount += $this->calcAccrualPolicyTime( $u_obj, $epoch, $offset, $pps_obj, $pay_period_arr, $accrual_amount, FALSE );
			}

			Debug::Text('Projected Accrual Amount: '. TTDate::getHours( $accrual_amount ), __FILE__, __LINE__, __METHOD__, 10);
		}

		return $accrual_amount;
	}

	/**
	 * Calculate the accrual amount based on a given user/time.
	 * @param object $u_obj
	 * @param int $epoch EPOCH
	 * @param $offset
	 * @param object $pps_obj
	 * @param $pay_period_arr
	 * @param float $accrual_balance
	 * @param bool $update_records
	 * @return bool|float|int|string
	 */
	function calcAccrualPolicyTime( $u_obj, $epoch, $offset, $pps_obj, $pay_period_arr, $accrual_balance, $update_records = TRUE ) {
		$retval = 0;

		Debug::Text('User: '. $u_obj->getFullName() .' Status: '. $u_obj->getStatus() .' Epoch: '. TTDate::getDate('DATE+TIME', $epoch) .' Hire Date: '. TTDate::getDate('DATE+TIME', $u_obj->getHireDate() ), __FILE__, __LINE__, __METHOD__, 10);
		//Make sure only active employees accrue time *after* their hire date.
		//Will this negatively affect Employees who may be on leave?
		if ( $u_obj->getStatus() == 10
				AND TTDate::getMiddleDayEpoch( $epoch ) >= TTDate::getMiddleDayEpoch( $u_obj->getHireDate() )
				AND ( $this->getMinimumEmployedDays() == 0
					OR TTDate::getDays( ( TTDate::getMiddleDayEpoch( $epoch ) - TTDate::getMiddleDayEpoch( $u_obj->getHireDate() ) ) ) >= $this->getMinimumEmployedDays() ) ) {
			Debug::Text('  User is active and has been employed long enough.', __FILE__, __LINE__, __METHOD__, 10);

			$annual_pay_periods = $pps_obj->getAnnualPayPeriods();
			$in_apply_frequency_window = FALSE;
			$in_apply_rollover_window = FALSE;
			$pay_period_start_date = NULL;
			$accrual_amount = 0;
			if ( $this->getType() == 30 ) {
				Debug::Text('  Accrual policy is hour based, real-time window.', __FILE__, __LINE__, __METHOD__, 10);

				//Hour based, apply frequency is real-time.
				$in_apply_frequency_window = TRUE;
			} else {
				$pay_period_dates = FALSE;
				if ( $this->getApplyFrequency() == 10 ) {
					$pay_period_dates = $this->getPayPeriodDatesFromArray( $pay_period_arr, ( $epoch - $offset ) );
					if ( is_array( $pay_period_dates ) ) {
						Debug::Text('   Pay Period Start Date: '. TTDate::getDate('DATE+TIME', $pay_period_dates['start_date'] ) .' End Date: '. TTDate::getDate('DATE+TIME', $pay_period_dates['end_date'] ), __FILE__, __LINE__, __METHOD__, 10);
						if ( $this->inApplyFrequencyWindow( $epoch, $offset, $pay_period_dates ) == TRUE ) {
							$in_apply_frequency_window = TRUE;

							$pay_period_start_date = $pay_period_dates['start_date']; //Used for inRolloverFrequencyWindow
						} else {
							Debug::Text('  User not in Apply Frequency Window: ', __FILE__, __LINE__, __METHOD__, 10);
						}
					} else {
						Debug::Arr($pay_period_dates, '   No Pay Period Dates Found.', __FILE__, __LINE__, __METHOD__, 10);
					}
				} elseif ( $this->inApplyFrequencyWindow( $epoch, $offset, NULL, $u_obj ) == TRUE ) {
					Debug::Text('  User IS in NON-PayPeriod Apply Frequency Window.', __FILE__, __LINE__, __METHOD__, 10);
					$in_apply_frequency_window = TRUE;
				} else {
					//Debug::Text('  User is not in Apply Frequency Window.', __FILE__, __LINE__, __METHOD__, 10);
					$in_apply_frequency_window = FALSE;
				}

				if ( $in_apply_frequency_window == FALSE
					AND $this->getEnableOpeningBalance() == TRUE
					AND TTDate::getMiddleDayEpoch( $u_obj->getHireDate() ) == TTDate::getMiddleDayEpoch( $epoch )
					AND $this->isInitialApplyFrequencyWindow( $epoch, $offset, $pay_period_dates, $u_obj ) == TRUE ) {
					Debug::Text('  Epoch is users hire date, and opening balances is enabled...', __FILE__, __LINE__, __METHOD__, 10);
					$in_apply_frequency_window = TRUE;
				}
			}

			if ( $this->inRolloverFrequencyWindow( $epoch, $offset, $u_obj, $pay_period_start_date ) ) {
				Debug::Text('   In rollover window...', __FILE__, __LINE__, __METHOD__, 10);
				$in_apply_rollover_window = TRUE;
			}

			if ( $in_apply_frequency_window == TRUE OR $in_apply_rollover_window == TRUE ) {
				$milestone_obj = $this->getActiveMilestoneObject( $u_obj, $epoch );
			}

			if ( $in_apply_rollover_window == TRUE AND ( isset($milestone_obj) AND is_object( $milestone_obj ) ) ) {
				//Handle maximum rollover adjustments before continuing.
				if ( $accrual_balance > $milestone_obj->getRolloverTime() ) {
					$rollover_accrual_adjustment = bcsub( $milestone_obj->getRolloverTime(), $accrual_balance, 0);
					Debug::Text('   Adding rollover adjustment of: '. $rollover_accrual_adjustment, __FILE__, __LINE__, __METHOD__, 10);

					//Check to make sure there isn't an identical entry already made.
					//Ignore rollover adjustment is another adjustment of any amount has been made on the same day.
					$alf = TTnew( 'AccrualListFactory' ); /** @var AccrualListFactory $alf */
					if ( $update_records == TRUE ) {
						$alf->getByCompanyIdAndUserIdAndAccrualPolicyAccountAndTypeIDAndTimeStamp( $u_obj->getCompany(), $u_obj->getID(), $this->getAccrualPolicyAccount(), 60, TTDate::getMiddleDayEpoch( $epoch ) );
					}
					if ( $alf->getRecordCount() == 0 ) {
						//Get effective date, try to use the current milestone rollover date to make things more clear.
						$current_milestone_rollover_date = $this->getCurrentMilestoneRolloverDate( $epoch, $u_obj, TRUE ); //If milestone rollover date comes after the current epoch, back date it by one year.

						if ( $update_records == TRUE ) {
							//Don't round to the nearest minute, as that can cause too much error on weekly frequencies.
							$af = TTnew( 'AccrualFactory' ); /** @var AccrualFactory $af */
							$af->setUser( $u_obj->getID() );
							$af->setType( 60 ); //Rollover Adjustment
							$af->setAccrualPolicyAccount( $this->getAccrualPolicyAccount() );
							$af->setAccrualPolicy( $this->getId() );
							$af->setAmount( $rollover_accrual_adjustment );
							$af->setTimeStamp( TTDate::getMiddleDayEpoch( $current_milestone_rollover_date ) );
							$af->setEnableCalcBalance( TRUE );

							if ( $af->isValid() ) {
								$af->Save();
							}
						} else {
							Debug::Text('   NOT UPDATING RECORDS...', __FILE__, __LINE__, __METHOD__, 10);
							$retval = $rollover_accrual_adjustment;
						}

						//Make sure we get updated balance after rollover adjustment was made.
						$accrual_balance += $rollover_accrual_adjustment;

						unset($current_milestone_rollover_date);
					} else {
						Debug::Text('   Found duplicate rollover accrual entry, skipping...', __FILE__, __LINE__, __METHOD__, 10);
					}
				} else {
					Debug::Text('   Balance hasnt exceeded rollover adjustment... Balance: '. $accrual_balance .' Milestone Rollover Time: '. $milestone_obj->getRolloverTime(), __FILE__, __LINE__, __METHOD__, 10);
				}
				unset($rollover_accrual_adjustment, $alf, $af);
			}

			if ( $in_apply_frequency_window === TRUE ) {
				if ( isset($milestone_obj) AND is_object( $milestone_obj ) ) {
					Debug::Text('  Found Matching Milestone, Accrual Rate: (ID: '. $milestone_obj->getId() .') '. $milestone_obj->getAccrualRate() .'/year', __FILE__, __LINE__, __METHOD__, 10);

					//Make sure we get updated balance after rollover adjustment was made.
					if ( $accrual_balance < $milestone_obj->getMaximumTime() ) {
						$accrual_amount = $this->calcAccrualAmount( $milestone_obj, 0, $annual_pay_periods);

						//Check if this is the initial period and pro-rate the accrual amount.
						if (	$this->getType() == 20 //Calendar based only
								AND
								(
									( $this->getEnableOpeningBalance() == FALSE AND $this->getEnableProRateInitialPeriod() == TRUE AND $this->isInitialApplyFrequencyWindow( $epoch, $offset, $pay_period_dates, $u_obj ) == TRUE )
									OR
									( $this->getEnableOpeningBalance() == TRUE AND $this->getEnableProRateInitialPeriod() == TRUE AND TTDate::getMiddleDayEpoch( $u_obj->getHireDate() ) == TTDate::getMiddleDayEpoch( $epoch ) )
								)
							) {
							$accrual_amount = $this->getProRateInitialFrequencyWindow( $accrual_amount, $epoch, $offset, $pay_period_dates, $u_obj );
						}

						if ( $accrual_amount > 0 ) {
							$new_accrual_balance = bcadd( $accrual_balance, $accrual_amount);

							//If Maximum time is set to 0, make that unlimited.
							if ( $milestone_obj->getMaximumTime() > 0 AND $new_accrual_balance > $milestone_obj->getMaximumTime() ) {
								$accrual_amount = bcsub( $milestone_obj->getMaximumTime(), $accrual_balance, 0 );
							}
							Debug::Text('   Min/Max Adjusted Accrual Amount: '. $accrual_amount .' Limits: Max: '. $milestone_obj->getMaximumTime(), __FILE__, __LINE__, __METHOD__, 10);

							//Check to make sure there isn't an identical entry already made.
							$alf = TTnew( 'AccrualListFactory' ); /** @var AccrualListFactory $alf */
							if ( $update_records == TRUE ) {
								$alf->getByCompanyIdAndUserIdAndAccrualPolicyAccountAndTimeStampAndAmount( $u_obj->getCompany(), $u_obj->getID(), $this->getAccrualPolicyAccount(), TTDate::getMiddleDayEpoch( $epoch ), $accrual_amount );
							}
							if ( $alf->getRecordCount() == 0 ) {
								if ( $update_records == TRUE ) {
									Debug::Text('   UPDATING RECORDS...', __FILE__, __LINE__, __METHOD__, 10);
									//Round to nearest 1min
									$af = TTnew( 'AccrualFactory' ); /** @var AccrualFactory $af */
									$af->setUser( $u_obj->getID() );
									$af->setType( 75 ); //Accrual Policy
									$af->setAccrualPolicyAccount( $this->getAccrualPolicyAccount() );
									$af->setAccrualPolicy( $this->getId() );
									$af->setAmount( $accrual_amount );
									$af->setTimeStamp( TTDate::getMiddleDayEpoch( $epoch ) );
									$af->setEnableCalcBalance( TRUE );

									if ( $af->isValid() ) {
										$af->Save();
									}
								} else {
									Debug::Text('   NOT UPDATING RECORDS...', __FILE__, __LINE__, __METHOD__, 10);
									$retval += $accrual_amount;
								}
							} else {
								Debug::Text('   Found duplicate accrual entry, skipping...', __FILE__, __LINE__, __METHOD__, 10);
							}
							unset($accrual_amount, $accrual_balance, $new_accrual_balance);
						} else {
							Debug::Text('   Accrual Amount is 0...', __FILE__, __LINE__, __METHOD__, 10);
						}
					} else {
						Debug::Text('   Accrual Balance is outside Milestone Range. Skipping...', __FILE__, __LINE__, __METHOD__, 10);
					}
				} else {
					Debug::Text('  DID NOT Find Matching Milestone.', __FILE__, __LINE__, __METHOD__, 10);
				}
				unset($milestone_obj);
			} else {
				Debug::Text('  NOT in apply frequency window...', __FILE__, __LINE__, __METHOD__, 10);
			}
		} else {
			Debug::Text('  User is not active (Status: '. $u_obj->getStatus() .') or has only been employed: '. TTDate::getDays( ($epoch - $u_obj->getHireDate()) ) .' Days, not enough. Hire Date: '. TTDate::getDATE( 'DATE+TIME', $u_obj->getHireDate() ), __FILE__, __LINE__, __METHOD__, 10);
		}

		if ( $update_records == TRUE ) {
			return TRUE;
		} else {
			Debug::Text('Retval: '. $retval, __FILE__, __LINE__, __METHOD__, 10);
			return $retval;
		}
	}

	/**
	 * @param int $epoch EPOCH
	 * @param int $offset 79200 = 22hr offset
	 * @param bool $user_ids
	 * @return bool
	 */
	function addAccrualPolicyTime( $epoch = NULL, $offset = 79200, $user_ids = FALSE ) {
		if ( $epoch == '' ) {
			$epoch = TTDate::getTime();
		}

		Debug::Text('Accrual Policy ID: '. $this->getId() .' Current EPOCH: '. TTDate::getDate('DATE+TIME', $epoch), __FILE__, __LINE__, __METHOD__, 10);

		$pglf = TTnew( 'PolicyGroupListFactory' ); /** @var PolicyGroupListFactory $pglf */

		$pglf->StartTransaction();

		$pglf->getAPISearchByCompanyIdAndArrayCriteria( $this->getCompany(), array( 'accrual_policy_id' => array( $this->getId() ) ) );
		if ( $pglf->getRecordCount() > 0 ) {
			foreach( $pglf as $pg_obj ) {
				Debug::Text('Found Policy Group: '. $pg_obj->getName() .' Company ID: '. $pg_obj->getCompany(), __FILE__, __LINE__, __METHOD__, 10);
				//Get all users assigned to this policy group.
				if ( is_array($user_ids) AND count($user_ids) > 0 AND !in_array( TTUUID::getNotExistID(), $user_ids ) ) {
					Debug::Text('Using users passed in by filter...', __FILE__, __LINE__, __METHOD__, 10);
					$policy_group_users = array_intersect( (array)$pg_obj->getUser(), (array)$user_ids );
				} else {
					Debug::Text('Using users assigned to policy group...', __FILE__, __LINE__, __METHOD__, 10);
					$policy_group_users = $pg_obj->getUser();
				}
				if ( is_array($policy_group_users) AND count($policy_group_users) > 0 ) {
					Debug::Text('Found Policy Group Users: '. count($policy_group_users), __FILE__, __LINE__, __METHOD__, 10);
					foreach( $policy_group_users as $user_id ) {
						Debug::Text('Policy Group User ID: '. $user_id, __FILE__, __LINE__, __METHOD__, 10);

						//Get User Object
						$ulf = TTnew( 'UserListFactory' ); /** @var UserListFactory $ulf */
						$ulf->getByIDAndCompanyID( $user_id, $this->getCompany() );
						if ( $ulf->getRecordCount() == 1 ) {
							$u_obj = $ulf->getCurrent();

							//This is an optimization to detect inactive employees sooner.
							if ( $u_obj->getStatus() != 10 ) {
								Debug::Text('  Employee is not active, skipping...', __FILE__, __LINE__, __METHOD__, 10);
								continue;
							}

							//Switch to users timezone so rollover adjustments are handled on the proper date.
							$user_obj_prefs = $u_obj->getUserPreferenceObject();
							if ( is_object( $user_obj_prefs ) ) {
								$user_obj_prefs->setTimeZonePreferences();
							} else {
								//Use system timezone.
								TTDate::setTimeZone();
							}

							//Optmization to make sure we can quickly skip days outside the employment period.
							if ( $u_obj->getHireDate() != '' AND TTDate::getBeginDayEpoch( $epoch ) < TTDate::getBeginDayEpoch( $u_obj->getHireDate() ) ) {
								Debug::Text('  Before employees hire date, skipping...', __FILE__, __LINE__, __METHOD__, 10);
								continue;
							}
							if ( $u_obj->getTerminationDate() != '' AND TTDate::getBeginDayEpoch( $epoch ) > TTDate::getBeginDayEpoch( $u_obj->getTerminationDate() ) ) {
								Debug::Text('  After employees termination date, skipping...', __FILE__, __LINE__, __METHOD__, 10);
								continue;
							}

							$ppslf = TTNew('PayPeriodScheduleListFactory'); /** @var PayPeriodScheduleListFactory $ppslf */
							$ppslf->getByCompanyIdAndUserId( $u_obj->getCompany(), $u_obj->getId() );
							if ( $ppslf->getRecordCount() > 0 ) {
								$pps_obj = $ppslf->getCurrent();

								$accrual_balance = $this->getCurrentAccrualBalance( $u_obj->getID(), $this->getAccrualPolicyAccount() );

								$pay_period_arr = array();
								if ( $this->getApplyFrequency() == 10 ) {
									$pay_period_arr = $this->getPayPeriodArray( $pps_obj, $u_obj, ($epoch - $offset), ($epoch - $offset) );
								}

								$this->calcAccrualPolicyTime( $u_obj, $epoch, $offset, $pps_obj, $pay_period_arr, $accrual_balance, TRUE );
							}
						} else {
							Debug::Text('No User Found. Company ID: '. $this->getCompany(), __FILE__, __LINE__, __METHOD__, 10);
						}
					}
				}
			}
		}

		$pglf->CommitTransaction();

		return TRUE;
	}

	/**
	 * @param bool $ignore_warning
	 * @return bool
	 */
	function Validate( $ignore_warning = TRUE ) {
		//
		// BELOW: Validation code moved from set*() functions.
		//
		// Company
		$clf = TTnew( 'CompanyListFactory' ); /** @var CompanyListFactory $clf */
		$this->Validator->isResultSetWithRows(	'company',
														$clf->getByID($this->getCompany()),
														TTi18n::gettext('Company is invalid')
													);
		// Type
		if ( $this->getType() !== FALSE ) {
			$this->Validator->inArrayKey(	'type',
													$this->getType(),
													TTi18n::gettext('Incorrect Type'),
													$this->getOptions('type')
												);
		}
		// Accrual Account
		if ( $this->getAccrualPolicyAccount() !== FALSE ) {
			$apaplf = TTnew( 'AccrualPolicyAccountListFactory' ); /** @var AccrualPolicyAccountListFactory $apaplf */
			$this->Validator->isResultSetWithRows(	'accrual_policy_account_id',
															$apaplf->getByID($this->getAccrualPolicyAccount()),
															TTi18n::gettext('Accrual Account is invalid')
														);
		}
		// Contributing Shift Policy
		if ( $this->getContributingShiftPolicy() !== FALSE AND $this->getContributingShiftPolicy() != TTUUID::getZeroID() ) {
			$csplf = TTnew( 'ContributingShiftPolicyListFactory' ); /** @var ContributingShiftPolicyListFactory $csplf */
			$this->Validator->isResultSetWithRows(	'contributing_shift_policy_id',
															$csplf->getByID($this->getContributingShiftPolicy()),
															TTi18n::gettext('Contributing Shift Policy is invalid')
														);
		}
		// Contributing Pay Code Policy
		if ( $this->getLengthOfServiceContributingPayCodePolicy() !== FALSE AND $this->getLengthOfServiceContributingPayCodePolicy() != TTUUID::getZeroID() ) {
			$csplf = TTnew( 'ContributingPayCodePolicyListFactory' ); /** @var ContributingPayCodePolicyListFactory $csplf */
			$this->Validator->isResultSetWithRows(	'length_of_service_contributing_pay_code_policy_id',
															$csplf->getByID($this->getLengthOfServiceContributingPayCodePolicy()),
															TTi18n::gettext('Contributing Pay Code Policy is invalid')
														);
		}
		// Name
		if ( $this->Validator->getValidateOnly() == FALSE ) { //Don't check the below when mass editing, but must check when adding a new record..
			if ( $this->getName() == '' ) {
				$this->Validator->isTRUE( 'name',
										  FALSE,
										  TTi18n::gettext( 'Please specify a name' ) );
			}
		}

		if ( $this->getName() !== FALSE ) {
			if ( $this->getName() != '' AND $this->Validator->isError('name') == FALSE ) {
				$this->Validator->isLength( 'name',
											$this->getName(),
											TTi18n::gettext( 'Name is too short or too long' ),
											2, 50
				);
			}
			if ( $this->getName() != '' AND $this->Validator->isError('name') == FALSE ) {
				$this->Validator->isTrue(	'name',
											 $this->isUniqueName($this->getName()),
											 TTi18n::gettext('Name is already in use')
				);
			}
		}
		// Description
		if ( $this->getDescription() != '' ) {
			$this->Validator->isLength(	'description',
												$this->getDescription(),
												TTi18n::gettext('Description is invalid'),
												1, 250
											);
		}
		// Minimum Time
		$this->Validator->isNumeric(		'minimum_time',
													$this->getMinimumTime(),
													TTi18n::gettext('Incorrect Minimum Time')
												);
		// Maximum Time
		$this->Validator->isNumeric(		'maximum_time',
													$this->getMaximumTime(),
													TTi18n::gettext('Incorrect Maximum Time')
												);
		// Frequency
		if ( $this->getApplyFrequency() != '' ) {
			$this->Validator->inArrayKey(	'apply_frequency_id',
													$this->getApplyFrequency(),
													TTi18n::gettext('Incorrect frequency'),
													$this->getOptions('apply_frequency')
												);
		}

		if ( $this->getDeleted() == FALSE ) {
			// Frequency month
			if ( $this->getApplyFrequencyMonth() != '' ) {
				$this->Validator->inArrayKey( 'apply_frequency_month',
											  $this->getApplyFrequencyMonth(),
											  TTi18n::gettext( 'Incorrect frequency month' ),
											  TTDate::getMonthOfYearArray()
				);
			}
			// Frequency day of month
			if ( $this->getApplyFrequencyDayOfMonth() != '' ) {
				$this->Validator->inArrayKey( 'apply_frequency_day_of_month',
											  $this->getApplyFrequencyDayOfMonth(),
											  TTi18n::gettext( 'Incorrect frequency day of month' ),
											  TTDate::getDayOfMonthArray()
				);
			}
			// Frequency day of week
			if ( $this->getApplyFrequencyDayOfWeek() != '' ) {
				$this->Validator->inArrayKey( 'apply_frequency_day_of_week',
											  $this->getApplyFrequencyDayOfWeek(),
											  TTi18n::gettext( 'Incorrect frequency day of week' ),
											  TTDate::getDayOfWeekArray()
				);
			}
			// Frequency quarter month
			if ( $this->getApplyFrequencyQuarterMonth() != '' ) {
				$this->Validator->isGreaterThan( 'apply_frequency_quarter_month',
												 $this->getApplyFrequencyQuarterMonth(),
												 TTi18n::gettext( 'Incorrect frequency quarter month' ),
												 1
				);
				if ( $this->Validator->isError( 'apply_frequency_quarter_month' ) == FALSE ) {
					$this->Validator->isLessThan( 'apply_frequency_quarter_month',
												  $this->getApplyFrequencyQuarterMonth(),
												  TTi18n::gettext( 'Incorrect frequency quarter month' ),
												  3
					);
				}
			}
			// Milestone rollover month
			if ( $this->getMilestoneRolloverMonth() != '' ) {
				$this->Validator->inArrayKey( 'milestone_rollover_month',
											  $this->getMilestoneRolloverMonth(),
											  TTi18n::gettext( 'Incorrect milestone rollover month' ),
											  TTDate::getMonthOfYearArray()
				);
			}
			// Milestone rollover day of month
			if ( $this->getMilestoneRolloverDayOfMonth() != '' ) {
				$this->Validator->inArrayKey( 'milestone_rollover_day_of_month',
											  $this->getMilestoneRolloverDayOfMonth(),
											  TTi18n::gettext( 'Incorrect milestone rollover day of month' ),
											  TTDate::getDayOfMonthArray()
				);
			}
		}
		// Minimum Employed days
		if ( $this->getMinimumEmployedDays() !== FALSE ) {
			$this->Validator->isNumeric(		'minimum_employed_days',
														$this->getMinimumEmployedDays(),
														TTi18n::gettext('Incorrect Minimum Employed days')
													);
		}

		//
		// ABOVE: Validation code moved from set*() functions.
		//

		if ( $this->getEnableOpeningBalance() == TRUE AND $this->getMinimumEmployedDays() != 0 ) {
			$this->Validator->isTRUE(	'minimum_employed_days',
										 FALSE,
										 TTi18n::gettext('Minimum Employed Days must be set to 0 when Opening Balance is Enabled') );
		}

		/*
		//They need to be able to delete accrual policies while still keeping records originally created by the accrual policy.
		if ( $this->getDeleted() == TRUE ) {
			//Check to make sure there are no hours using this accrual policy.
			$alf = TTnew( 'AccrualListFactory' );
			$alf->getByAccrualPolicyID( $this->getId(), 1 ); //Limit 1
			if ( $alf->getRecordCount() > 0 ) {
				$this->Validator->isTRUE(	'in_use',
											FALSE,
											TTi18n::gettext('This accrual policy is in use'));

			}
		}
		*/

		if ( $this->getDeleted() == TRUE ) {
			//Check to make sure nothing else references this policy, so we can be sure its okay to delete it.
			$pglf = TTnew( 'PolicyGroupListFactory' ); /** @var PolicyGroupListFactory $pglf */
			$pglf->getAPISearchByCompanyIdAndArrayCriteria( $this->getCompany(), array('accrual_policy' => $this->getId() ), 1 );
			if ( $pglf->getRecordCount() > 0 ) {
				$this->Validator->isTRUE( 'in_use',
										  FALSE,
										  TTi18n::gettext( 'This policy is currently in use' ) . ' ' . TTi18n::gettext( 'by policy groups' ) );
			}
		}

		return TRUE;
	}

	/**
	 * @return bool
	 */
	function preSave() {
		return TRUE;
	}

	/**
	 * @return bool
	 */
	function postSave() {
		$this->removeCache( $this->getId() );

		if ( $this->getDeleted() == TRUE ) {
			Debug::Text('UnAssign Accruals records from Policy: '. $this->getId(), __FILE__, __LINE__, __METHOD__, 10);
			$af = TTnew( 'AccrualFactory' ); /** @var AccrualFactory $af */

			$query = 'update '. $af->getTable() .' set accrual_policy_id = \''. TTUUID::getZeroID() .'\' where accrual_policy_id = \''. TTUUID::castUUID($this->getId()) .'\'';
			$this->ExecuteSQL($query);
		}

		return TRUE;
	}

	/**
	 * @param $data
	 * @return bool
	 */
	function setObjectFromArray( $data ) {
		if ( is_array( $data ) ) {
			$variable_function_map = $this->getVariableToFunctionMap();
			foreach( $variable_function_map as $key => $function ) {
				if ( isset($data[$key]) ) {

					$function = 'set'.$function;
					switch( $key ) {
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

	/**
	 * @param null $include_columns
	 * @return array
	 */
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
							$function = 'get'.$variable;
							if ( method_exists( $this, $function ) ) {
								$data[$variable] = Option::getByKey( $this->$function(), $this->getOptions( $variable ) );
							}
							break;
						case 'apply_frequency':
							if ( method_exists( $this, $function ) ) {
								$data[$variable] = Option::getByKey( $this->$function(), $this->getOptions( $variable ) );
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

	/**
	 * @param $log_action
	 * @return bool
	 */
	function addLog( $log_action ) {
		return TTLog::addEntry( $this->getId(), $log_action, TTi18n::getText('Accrual Policy'), NULL, $this->getTable(), $this );
	}
}
?>
