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
 * @package Modules\Users
 */
class UserWageFactory extends Factory {
	protected $table = 'user_wage';
	protected $pk_sequence_name = 'user_wage_id_seq'; //PK Sequence name

	var $user_obj = NULL;
	var $labor_standard_obj = NULL;
	var $holiday_obj = NULL;
	var $wage_group_obj = NULL;


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
											10	=> TTi18n::gettext('Hourly'),
											12	=> TTi18n::gettext('Salary (Weekly)'),
											13	=> TTi18n::gettext('Salary (Bi-Weekly)'),
											15	=> TTi18n::gettext('Salary (Monthly)'),
											20	=> TTi18n::gettext('Salary (Annual)'),
//											30	=> TTi18n::gettext('Min. Wage + Bonus (Salary)')
									);
				break;
			case 'columns':
				$retval = array(

										'-1010-first_name' => TTi18n::gettext('First Name'),
										'-1020-last_name' => TTi18n::gettext('Last Name'),

										'-1030-wage_group' => TTi18n::gettext('Wage Group'),
										'-1040-type' => TTi18n::gettext('Type'),
										'-1050-wage' => TTi18n::gettext('Wage'),
										'-1060-effective_date' => TTi18n::gettext('Effective Date'),

										'-1070-hourly_rate' => TTi18n::gettext('Hourly Rate'),
										'-1070-labor_burden_percent' => TTi18n::gettext('Labor Burden Percent'),
										'-1080-weekly_time' => TTi18n::gettext('Average Time/Week'),

										'-1090-title' => TTi18n::gettext('Title'),
										'-1099-user_group' => TTi18n::gettext('Group'),
										'-1100-default_branch' => TTi18n::gettext('Branch'),
										'-1110-default_department' => TTi18n::gettext('Department'),

										'-1290-note' => TTi18n::gettext('Note'),

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
								'first_name',
								'last_name',
								'wage_group',
								'type',
								'wage',
								'effective_date',
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
											'user_id' => 'User',
											'first_name' => FALSE,
											'last_name' => FALSE,
											'wage_group_id' => 'WageGroup',
											'wage_group' => FALSE,
											'type_id' => 'Type',
											'type' => FALSE,
											'currency_symbol' => FALSE,
											'wage' => 'Wage',
											'hourly_rate' => 'HourlyRate',
											'labor_burden_hourly_rate' => 'LaborBurdenHourlyRate',
											'weekly_time' => 'WeeklyTime',
											'labor_burden_percent' => 'LaborBurdenPercent',
											'effective_date' => 'EffectiveDate',
											'note' => 'Note',

											'default_branch' => FALSE,
											'default_department' => FALSE,
											'user_group' => FALSE,
											'title' => FALSE,

											'deleted' => 'Deleted',
											);
			return $variable_function_map;
	}

	/**
	 * @return bool
	 */
	function getUserObject() {
		return $this->getGenericObject( 'UserListFactory', $this->getUser(), 'user_obj' );
	}

	/**
	 * @return bool|null
	 */
	function getWageGroupObject() {
		if ( is_object($this->wage_group_obj) ) {
			return $this->wage_group_obj;
		} else {

			$wglf = TTnew( 'WageGroupListFactory' ); /** @var WageGroupListFactory $wglf */
			$wglf->getById( $this->getWageGroup() );

			if ( $wglf->getRecordCount() == 1 ) {
				$this->wage_group_obj = $wglf->getCurrent();

				return $this->wage_group_obj;
			}

			return FALSE;
		}
	}

	/**
	 * @return bool|mixed
	 */
	function getUser() {
		return $this->getGenericDataValue( 'user_id' );
	}

	/**
	 * @param string $value UUID
	 * @return bool
	 */
	function setUser( $value ) {
		$value = TTUUID::castUUID( $value );
		return $this->setGenericDataValue( 'user_id', $value );
	}

	/**
	 * @return bool|mixed
	 */
	function getWageGroup() {
		return $this->getGenericDataValue( 'wage_group_id' );
	}

	/**
	 * @param string $value UUID
	 * @return bool
	 */
	function setWageGroup( $value ) {
		$value = TTUUID::castUUID( $value );
		Debug::Text('Wage Group ID: '. $value, __FILE__, __LINE__, __METHOD__, 10);
		return $this->setGenericDataValue( 'wage_group_id', $value );
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
	function setType( $value ) {
		$value = (int)trim($value);
		return $this->setGenericDataValue( 'type_id', $value );
	}

	/**
	 * @return bool|float
	 */
	function getWage() {
		return (float)$this->getGenericDataValue( 'wage' ); //Needs to return float so TTi18n::NumberFormat() can always handle it properly.
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setWage( $value ) {
		//Pull out only digits and periods.
		$value = $this->Validator->stripNonFloat($value);
		return $this->setGenericDataValue( 'wage', $value );
	}

	/**
	 * @return bool|float
	 */
	function getHourlyRate() {
		return (float)$this->getGenericDataValue( 'hourly_rate' ); //Needs to return float so TTi18n::NumberFormat() can always handle it properly.
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setHourlyRate( $value ) {
		//Pull out only digits and periods.
		$value = $this->Validator->stripNonFloat($value);
		return $this->setGenericDataValue( 'hourly_rate', $value );
	}

	/**
	 * @return bool
	 */
	function getWeeklyTime() {
		return $this->getGenericDataValue( 'weekly_time' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setWeeklyTime( $value) {
		return $this->setGenericDataValue( 'weekly_time', $value );
	}

	/**
	 * @return bool|float
	 */
	function getLaborBurdenPercent() {
		return (float)$this->getGenericDataValue( 'labor_burden_percent' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setLaborBurdenPercent( $value) {
		//Pull out only digits and periods.
		$value = $this->Validator->stripNonFloat($value);
		return $this->setGenericDataValue( 'labor_burden_percent', $value );
	}


	/**
	 * @param int $epoch EPOCH
	 * @return bool
	 */
	function isValidEffectiveDate( $epoch) {
		//Check to see if this is the first default wage entry, or if we are editing the first record.
		if ( $this->getWageGroup() != TTUUID::getZeroID() ) { //If we aren't the default wage group, return valid always.
			return TRUE;
		}

		$must_validate = FALSE;

		$uwlf = TTnew( 'UserWageListFactory' ); /** @var UserWageListFactory $uwlf */
		$uwlf->getByUserIdAndGroupIDAndBeforeDate( $this->getUser(), TTUUID::getZeroID(), $epoch, 1, NULL, NULL, array('effective_date' => 'asc') );
		Debug::text(' Total Rows: '. $uwlf->getRecordCount() .' User: '. $this->getUser() .' Epoch: '. $epoch, __FILE__, __LINE__, __METHOD__, 10);

		if ( $uwlf->getRecordCount() <= 1 ) {
			//If it returns one row, we need to check to see if the returned row is the current record.
			if ( $uwlf->getRecordCount() == 0 ) {
				$must_validate = TRUE;
			} elseif ( $uwlf->getRecordCount() == 1 AND $this->isNew() == FALSE ) {
				//Check to see if we are editing the current record.
				if ( is_object( $uwlf->getCurrent() ) AND $this->getId() == $uwlf->getCurrent()->getId() ) {
					$must_validate = TRUE;
				} else {
					$must_validate = FALSE;
				}
			}
		}

		if ( $must_validate == TRUE ) {
			if ( is_object( $this->getUserObject() ) AND $this->getUserObject()->getHireDate() != '' ) {
				//User has hire date, make sure its before or equal to the first wage effective date.
				if ( $epoch <= $this->getUserObject()->getHireDate() ) {
					return TRUE;
				} else {
					return FALSE;
				}
			}
		}

		return TRUE;
	}

	/**
	 * @param int $effective_date EPOCH
	 * @return bool
	 */
	function isUniqueEffectiveDate( $effective_date) {
		$ph = array(
					'user_id' => TTUUID::castUUID($this->getUser()),
					'wage_group_id' => TTUUID::castUUID($this->getWageGroup()),
					'effective_date' => $this->db->BindDate( $effective_date )
					);

		$query = 'select id from '. $this->getTable() .' where user_id = ? AND wage_group_id = ? AND effective_date = ? AND deleted = 0';
		$id = $this->db->GetOne($query, $ph);
		Debug::Arr($id, 'Unique Wage Entry: Effective Date: '. $effective_date, __FILE__, __LINE__, __METHOD__, 10);

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
	 * @param bool $raw
	 * @return bool|int
	 */
	function getEffectiveDate( $raw = FALSE ) {
		$value = $this->getGenericDataValue( 'effective_date' );
		if ( $value !== FALSE ) {
			if ( $raw === TRUE ) {
				return $value;
			} else {
				return TTDate::strtotime( $value );
			}
		}

		return FALSE;
	}

	/**
	 * @param int $value EPOCH
	 * @return bool
	 */
	function setEffectiveDate( $value ) {
		return $this->setGenericDataValue( 'effective_date', TTDate::getISODateStamp( $value ) );
	}

	/**
	 * @return bool|mixed
	 */
	function getNote() {
		return $this->getGenericDataValue( 'note' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setNote( $value) {
		$value = trim($value);
		return $this->setGenericDataValue( 'note', $value );
	}

	/**
	 * @param bool $rate
	 * @return float
	 */
	function getLaborBurdenHourlyRate( $rate = FALSE ) {
		if ( $rate == '' ) {
			$rate = $this->getHourlyRate();
		}
		$hourly_wage = bcmul( $rate, bcadd( bcdiv( $this->getLaborBurdenPercent(), 100 ), 1) );

		$retval = Misc::MoneyRound( $hourly_wage, 2, ( ( is_object( $this->getUserObject() ) AND is_object( $this->getUserObject()->getCurrencyObject() ) ) ? $this->getUserObject()->getCurrencyObject() : NULL ) );

		//return Misc::MoneyFormat($hourly_wage, FALSE);
		//Format in APIUserWage() instead, as this gets passed back into setHourlyRate() and if in a locale that use comma decimal symbol, it will fail.

		return $retval;
	}

	/**
	 * @param $rate
	 * @return bool|float|int
	 */
	function getBaseCurrencyHourlyRate( $rate ) {
		if ( $rate == '' ) {
			return FALSE;
		}

		if ( !is_object( $this->getUserObject() ) ) {
			return FALSE;
		}

		$clf = TTnew( 'CurrencyListFactory' ); /** @var CurrencyListFactory $clf */
		$clf->getByCompanyIdAndBase( $this->getUserObject()->getCompany(), TRUE );
		if ( $clf->getRecordCount() > 0 ) {
			$base_currency_obj = $clf->getCurrent();

			//If current currency is the base currency, just return the rate.
			if ( $base_currency_obj->getId() == $this->getUserObject()->getCurrency() ) {
				return $rate;
			} else {
				//Debug::text(' Base Currency Rate: '. $base_currency_obj->getConversionRate() .' Hourly Rate: '. $rate, __FILE__, __LINE__, __METHOD__, 10);
				return CurrencyFactory::convertCurrency( $this->getUserObject()->getCurrency(), $base_currency_obj->getId(), $rate );
			}
		}

		return FALSE;
	}

	/**
	 * @return bool|int|string
	 */
	function getAnnualWage() {
		$annual_wage = 0;

		//Debug::text(' Type: '. $this->getType() .' Wage: '. $this->getWage(), __FILE__, __LINE__, __METHOD__, 10);
		switch ( $this->getType() ) {
			case 10: //Hourly
				//Hourly wage type, can't have an annual wage.
				$annual_wage = 0;
				break;
			case 12: //Salary (Weekly)
				$annual_wage = bcmul( $this->getWage(), 52 );
				break;
			case 13: //Salary (Bi-Weekly)
				$annual_wage = bcmul( $this->getWage(), 26 );
				break;
			case 15: //Salary (Monthly)
				$annual_wage = bcmul( $this->getWage(), 12 );
				break;
			case 20: //Salary (Annual)
				$annual_wage = $this->getWage();
				break;
		}

		return $annual_wage;
	}

	/**
	 * @param bool $epoch
	 * @param bool $accurate_calculation
	 * @return float
	 */
	function calcHourlyRate( $epoch = FALSE, $accurate_calculation = FALSE ) {
		$hourly_wage = 0;
		if ( $this->getType() == 10 ) {
			$hourly_wage = $this->getWage();
		} else {
			$hourly_wage = $this->getAnnualHourlyRate( $this->getAnnualWage(), $epoch, $accurate_calculation );
		}

		$retval = (float)Misc::MoneyRound( $hourly_wage, 2, ( ( is_object( $this->getUserObject() ) AND is_object( $this->getUserObject()->getCurrencyObject() ) ) ? $this->getUserObject()->getCurrencyObject() : NULL ) );

		//return Misc::MoneyFormat($hourly_wage, FALSE);
		//Format in APIUserWage() instead, as this gets passed back into setHourlyRate() and if in a locale that use comma decimal symbol, it will fail.

		return $retval;
	}

	/**
	 * @param $annual_wage
	 * @param bool $epoch
	 * @param bool $accurate_calculation
	 * @return bool|int|string
	 */
	function getAnnualHourlyRate( $annual_wage, $epoch = FALSE, $accurate_calculation = FALSE ) {
		if ( $epoch == FALSE ) {
			$epoch = TTDate::getTime();
		}

		if( $annual_wage == '' ) {
			return FALSE;
		}

		if ( $accurate_calculation == TRUE ) {
			Debug::text('EPOCH: '. $epoch, __FILE__, __LINE__, __METHOD__, 10);

			$annual_week_days = TTDate::getAnnualWeekDays( $epoch );
			Debug::text('Annual Week Days: '. $annual_week_days, __FILE__, __LINE__, __METHOD__, 10);

			//Calculate weeks from adjusted annual weekdays
			//We could use just 52 weeks in a year, but that isn't as accurate.
			$annual_work_weeks = bcdiv( $annual_week_days, 5);
			Debug::text('Adjusted annual work weeks : '. $annual_work_weeks, __FILE__, __LINE__, __METHOD__, 10);
		} else {
			$annual_work_weeks = 52;
		}

		$average_weekly_hours = TTDate::getHours( $this->getWeeklyTime() );
		//Debug::text('Average Weekly Hours: '. $average_weekly_hours, __FILE__, __LINE__, __METHOD__, 10);

		if ( $average_weekly_hours == 0 ) {
			//No default schedule, can't pay them.
			$hourly_wage = 0;
		} else {
			//Divide by average hours/day from default schedule?
			$hours_per_year = bcmul($annual_work_weeks, $average_weekly_hours);
			if ( $hours_per_year > 0 ) {
				$hourly_wage = bcdiv( $annual_wage, $hours_per_year );
			}
			unset($hours_per_year);
		}
		//Debug::text('User Wage: '. $this->getWage(), __FILE__, __LINE__, __METHOD__, 10);
		//Debug::text('Annual Hourly Rate: '. $hourly_wage, __FILE__, __LINE__, __METHOD__, 10);

		return $hourly_wage;
	}

	/**
	 * @param $salary
	 * @param int $wage_effective_date EPOCH
	 * @param int $prev_wage_effective_date EPOCH
	 * @param int $pp_start_date EPOCH
	 * @param int $pp_end_date EPOCH
	 * @param bool $hire_date
	 * @param bool $termination_date
	 * @return int|string
	 */
	static function proRateSalary( $salary, $wage_effective_date, $prev_wage_effective_date, $pp_start_date, $pp_end_date, $hire_date = FALSE, $termination_date = FALSE ) {
		$pro_rate_dates_arr = self::proRateSalaryDates( $wage_effective_date, $prev_wage_effective_date, $pp_start_date, $pp_end_date, $hire_date, $termination_date );
		if ( is_array($pro_rate_dates_arr) ) {
			Debug::text('Salary: '. $salary .' Total Pay Period Days: '. $pro_rate_dates_arr['total_pay_period_days'] .' Wage Effective Days: '. $pro_rate_dates_arr['total_wage_effective_days'], __FILE__, __LINE__, __METHOD__, 10);
			$pro_rate_salary = bcmul( $salary, bcdiv( $pro_rate_dates_arr['total_wage_effective_days'], $pro_rate_dates_arr['total_pay_period_days'] ) );
		}

		//Final sanaity checks.
		if ( $pro_rate_salary < 0 ) {
			$pro_rate_salary = 0;
		} elseif ( $pro_rate_salary > $salary ) {
			$pro_rate_salary = $salary;
		}
		Debug::text('Pro Rate Salary: '. $pro_rate_salary, __FILE__, __LINE__, __METHOD__, 10);

		return $pro_rate_salary;
	}

	/**
	 * @param int $wage_effective_date EPOCH
	 * @param int $prev_wage_effective_date EPOCH
	 * @param int $pp_start_date EPOCH
	 * @param int $pp_end_date EPOCH
	 * @param bool $hire_date
	 * @param bool $termination_date
	 * @return array
	 */
	static function proRateSalaryDates( $wage_effective_date, $prev_wage_effective_date, $pp_start_date, $pp_end_date, $hire_date = FALSE, $termination_date = FALSE ) {
		$prev_wage_effective_date = (int)$prev_wage_effective_date;

		if ( $wage_effective_date < $pp_start_date ) {
			$wage_effective_date = $pp_start_date;
		}

		if ( $wage_effective_date < $hire_date ) {
			$wage_effective_date = TTDate::getBeginDayEpoch( $hire_date );
		}

		$total_pay_period_days = ceil( TTDate::getDayDifference( $pp_start_date, $pp_end_date) );

		$retarr = array();

		$retarr['total_pay_period_days'] = $total_pay_period_days;
		if ( $prev_wage_effective_date == 0 ) {
			//ProRate salary to termination date if its in the middle of a pay period. Be sure to assume termination date is at the end of the day (inclusive), not beginning.
			if ( $termination_date != '' AND $termination_date > 0 AND TTDate::getMiddleDayEpoch( $termination_date ) < TTDate::getMiddleDayEpoch( $pp_end_date ) ) {
				//Debug::text(' Setting PP end date to Termination Date: '. TTDate::GetDate('DATE', $termination_date), __FILE__, __LINE__, __METHOD__, 10);
				$pp_end_date = TTDate::getEndDayEpoch( $termination_date );
			}
			$total_wage_effective_days = ceil( TTDate::getDayDifference( $wage_effective_date, $pp_end_date) );

			//Debug::text(' Using Pay Period End Date: '. TTDate::GetDate('DATE', $pp_end_date), __FILE__, __LINE__, __METHOD__, 10);
			$retarr['start_date'] = $wage_effective_date;
			$retarr['end_date'] = $pp_end_date;
		} else {
			$total_wage_effective_days = ceil( TTDate::getDayDifference( $wage_effective_date, $prev_wage_effective_date ) );

			//Debug::text(' Using Prev Effective Date: '. TTDate::GetDate('DATE', $prev_wage_effective_date ), __FILE__, __LINE__, __METHOD__, 10);
			$retarr['start_date'] = $wage_effective_date;
			$retarr['end_date'] = $prev_wage_effective_date;
		}
		$retarr['total_wage_effective_days'] = $total_wage_effective_days;

		if ( $retarr['start_date'] > $pp_start_date OR $retarr['end_date'] < $pp_end_date ) {
			$retarr['percent'] = Misc::removeTrailingZeros( round( bcmul( bcdiv($total_wage_effective_days, $total_pay_period_days), 100), 2), 0 );
		} else {
			$retarr['percent'] = 100;
		}

		//Always need to return an array of dates so proRateSalary() above can use them. However in order to know if any prorating is done or not, we need to return 'percent' = 100 or not.
		return $retarr;
	}

	/**
	 * @param int $date EPOCH
	 * @param $wage_arr
	 * @return bool|mixed
	 */
	static function getWageFromArray( $date, $wage_arr ) {
		if ( !is_array($wage_arr) ) {
			return FALSE;
		}

		if ( $date == '' ) {
			return FALSE;
		}

		//Debug::Arr($wage_arr, 'Wage Array: ', __FILE__, __LINE__, __METHOD__, 10);

		foreach( $wage_arr as $effective_date => $wage ) {
			if ( $effective_date <= $date ) {
				Debug::Text('Effective Date: '. TTDate::getDate('DATE+TIME', $effective_date) .' Is Less Than: '. TTDate::getDate('DATE+TIME', $date), __FILE__, __LINE__, __METHOD__, 10);
				return $wage;
			}
		}

		return FALSE;
	}

	/**
	 * Takes the employees
	 * @param string $company_id UUID
	 * @param string $user_id UUID
	 * @return bool|string
	 */
	static function calculateLaborBurdenPercent( $company_id, $user_id ) {
		if ( $company_id == '' ) {
			return FALSE;
		}
		if ( $user_id == '' ) {
			return FALSE;
		}

		$end_epoch = TTDate::getTime();
		$start_epoch = ( TTDate::getTime() - (86400 * 180) ); //6mths

		$retval = FALSE;

		$pseallf = TTnew( 'PayStubEntryAccountLinkListFactory' ); /** @var PayStubEntryAccountLinkListFactory $pseallf */
		$pseallf->getByCompanyID( $company_id );
		if ( $pseallf->getRecordCount() > 0 ) {
			$pself = TTnew( 'PayStubEntryListFactory' ); /** @var PayStubEntryListFactory $pself */
			$total_gross = $pself->getAmountSumByUserIdAndEntryNameIdAndStartDateAndEndDate($user_id, $pseallf->getCurrent()->getTotalGross(), $start_epoch, $end_epoch );
			$total_employer_deductions = $pself->getAmountSumByUserIdAndEntryNameIdAndStartDateAndEndDate($user_id, $pseallf->getCurrent()->getTotalEmployerDeduction(), $start_epoch, $end_epoch );

			if ( isset($total_employer_deductions['amount']) AND isset($total_gross['amount']) ) {
				$retval = bcmul( bcdiv( $total_employer_deductions['amount'], $total_gross['amount']), 100, 2);
			}
		}

		return $retval;
	}

	/**
	 * @return bool
	 */
	function preSave() {
		if ( $this->getType() == 10 ) { //Hourly
			$this->setWeeklyTime( NULL );
			$this->setHourlyRate( $this->getWage() ); //Match hourly rate to wage.
		}

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
		// Employee
		if ( $this->Validator->getValidateOnly() == FALSE ) { //Don't check the below when mass editing, but must check when adding a new record..
			if ( $this->getUser() == '' OR $this->getUser() == TTUUID::getZeroID() ) {
				$this->Validator->isTRUE(	'user_id',
											FALSE,
											TTi18n::gettext('No employee specified')
				);
			}
		}
		if ( $this->getUser() !== FALSE ) {
			if ( $this->Validator->isError('user_id') == FALSE ) {
				$ulf = TTnew( 'UserListFactory' ); /** @var UserListFactory $ulf */
				$this->Validator->isResultSetWithRows(	'user_id',
																$ulf->getByID($this->getUser()),
																TTi18n::gettext('Invalid Employee')
															);
			}
		}
		// Group
		if ( $this->getWageGroup() !== FALSE AND $this->getWageGroup() != TTUUID::getZeroID() ) {
			$wglf = TTnew( 'WageGroupListFactory' ); /** @var WageGroupListFactory $wglf */
			$this->Validator->isResultSetWithRows(	'wage_group_id',
														$wglf->getByID($this->getWageGroup()),
														TTi18n::gettext('Group is invalid')
													);
		}
		// Type
		if ( $this->Validator->getValidateOnly() == FALSE OR $this->getType() !== FALSE ) {
			$this->Validator->inArrayKey(	'type_id',
													$this->getType(),
													TTi18n::gettext('Incorrect Type'),
													$this->getOptions('type')
												);
		}

		// Wage
		$this->Validator->isFloat(	'wage',
											$this->getWage(),
											TTi18n::gettext('Incorrect Wage')
										);

		if ( $this->Validator->isError('wage') == FALSE ) {
			$this->Validator->isLength(	'wage',
												$this->getWage(),
												TTi18n::gettext('Wage has too many digits'),
												0,
												21
											); //Need to include decimal.
		}
		if ( $this->Validator->isError('wage') == FALSE ) {
			$this->Validator->isLengthBeforeDecimal(	'wage',
																$this->getWage(),
																TTi18n::gettext('Wage has too many digits before the decimal'),
																0,
																16
															);
		}
		if ( $this->Validator->isError('wage') == FALSE ) {
			$this->Validator->isLengthAfterDecimal(	'wage',
															$this->getWage(),
															TTi18n::gettext('Wage has too many digits after the decimal'),
															0,
															4
														);
		}
		// Hourly Rate
		if ( $this->getHourlyRate() != '' ) {
			$this->Validator->isFloat(	'hourly_rate',
												$this->getHourlyRate(),
												TTi18n::gettext('Incorrect Hourly Rate')
											);
		}
		// Weekly Time
		if ( $this->getWeeklyTime() != '' ) {
			$this->Validator->isNumeric(	'weekly_time',
													$this->getWeeklyTime(),
													TTi18n::gettext('Incorrect Weekly Time')
												);
		}
		// Labor Burden Percent
		$this->Validator->isFloat(	'labor_burden_percent',
											$this->getLaborBurdenPercent(),
											TTi18n::gettext('Incorrect Labor Burden Percent')
										);
		// Effective Date
		if ( $this->Validator->getValidateOnly() == FALSE OR $this->getEffectiveDate() !== FALSE ) { //Ensure an effective date is always specified, but handle mass editing properly too.
			$this->Validator->isDate(		'effective_date',
													$this->getEffectiveDate(),
													TTi18n::gettext('Incorrect Effective Date')
												);
			if ( $this->Validator->isError('effective_date') == FALSE ) {
				$this->Validator->isTrue(		'effective_date',
														$this->isUniqueEffectiveDate($this->getEffectiveDate()),
														TTi18n::gettext('Employee already has a wage entry on this date for the same wage group. Try using a different date instead')
													);
			}
		}

		// Note
		if ( $this->getNote() != '' ) {
			$this->Validator->isLength(		'note',
													$this->getNote(),
													TTi18n::gettext('Note is too long'),
													1,
													2048
												);
		}

		//
		// ABOVE: Validation code moved from set*() functions.
		//
		if ( $ignore_warning == FALSE AND $this->Validator->getValidateOnly() == FALSE ) { //Don't check the below when mass editing, but must check when adding a new record..
			if ( $this->getWage() <= 1 ) {
				$this->Validator->Warning( 'wage', TTi18n::gettext('Wage may be too low') );
			}

			if ( $this->getType() != 10 ) { //Salary
				//Make sure they won't put 0 or 1hr for the weekly time, as that is almost certainly wrong.
				if ( $this->getWeeklyTime() <= 3601 ) {
					$this->Validator->Warning( 'weekly_time', TTi18n::gettext('Average Time / Week may be too low, a proper estimated time is critical even for salary wages') );
				}

				//Make sure the weekly total time is within reason and hourly rates aren't 1000+/hr.
				if ( $this->getHourlyRate() <= 1 ) {
					$this->Validator->Warning( 'hourly_rate', TTi18n::gettext('Annual Hourly Rate may be too low, a proper hourly rate is critical even for salary wages') );
				}
				if ( is_object( $this->getUserObject() )
					AND is_object( $this->getUserObject()->getCurrencyObject() )
					AND in_array( $this->getUserObject()->getCurrencyObject()->getISOCode(), array('USD', 'CAD', 'EUR') )
					AND $this->getHourlyRate() > 500 ) {
					$this->Validator->Warning( 'hourly_rate', TTi18n::gettext('Annual Hourly Rate may be too high, a proper hourly rate is critical even for salary wages') );
				}
			}

			//If the wage record is added at noon on the hire date, and the employee has already punched in/out and finished their shift, still need to show this warning.
			if ( TTDate::getMiddleDayEpoch( $this->getEffectiveDate() ) <= TTDate::getMiddleDayEpoch( time() ) ) {
				$this->Validator->Warning( 'effective_date', TTi18n::gettext('When changing wages retroactively, you may need to recalculate this employees timesheet for the affected pay period(s)') );
			}
		}
		if ( $this->getDeleted() == FALSE ) {
			if ( is_object( $this->getUserObject() ) AND $this->getUserObject()->getHireDate() ) {
				$hire_date = $this->getUserObject()->getHireDate();
			} else {
				$hire_date = NULL;
			}

			$this->Validator->isTrue(		'effective_date',
											$this->isValidEffectiveDate( $this->getEffectiveDate() ),
											TTi18n::gettext('An employees first wage entry must be effective on or before the employees hire date').' ('. TTDate::getDate('DATE', $hire_date) .')');
		}

		return TRUE;
	}

	/**
	 * @return bool
	 */
	function postSave() {
		$this->removeCache( $this->getId() );
		$this->removeCache( $this->getId().$this->getUser() ); //Used in some reports.

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
						case 'effective_date':
							if ( method_exists( $this, $function ) ) {
								$this->$function( TTDate::parseDateTime( $data[$key] ) );
							}
							break;
						case 'hourly_rate':
						case 'wage':
						case 'labor_burden_percent':
							$this->$function( TTi18n::parseFloat( $data[$key] ) );
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

	/**
	 * @param null $include_columns
	 * @param bool $permission_children_ids
	 * @return array
	 */
	function getObjectAsArray( $include_columns = NULL, $permission_children_ids = FALSE ) {
		$data = array();
		$variable_function_map = $this->getVariableToFunctionMap();
		if ( is_array( $variable_function_map ) ) {
			foreach( $variable_function_map as $variable => $function_stub ) {
				if ( $include_columns == NULL OR ( isset($include_columns[$variable]) AND $include_columns[$variable] == TRUE ) ) {

					$function = 'get'.$function_stub;
					switch( $variable ) {
						case 'type':
							$function = 'get'.$variable;
							if ( method_exists( $this, $function ) ) {
								$data[$variable] = Option::getByKey( $this->$function(), $this->getOptions( $variable ) );
							}
							break;
						case 'wage_group':
						case 'first_name':
						case 'last_name':
						case 'title':
						case 'user_group':
						case 'currency':
						case 'default_branch':
						case 'default_department':
							$data[$variable] = $this->getColumn( $variable );
							break;
						case 'currency_symbol':
							$data[$variable] = TTi18n::getCurrencySymbol( $this->getColumn( 'iso_code' ) );
							break;
						case 'effective_date':
							if ( method_exists( $this, $function ) ) {
								$data[$variable] = TTDate::getAPIDate( 'DATE', $this->$function() );
							}
							break;
						case 'hourly_rate':
						case 'wage':
							//$data[$variable] = TTi18n::formatNumber( $this->$function(), TRUE, 2, 4 ); //Don't format numbers here, as it could break scripts using the API.
							$data[$variable] = Misc::removeTrailingZeros( $this->$function(), 2 );
							break;
						case 'labor_burden_percent':
							//$data[$variable] = TTi18n::formatNumber( $this->$function(), TRUE, 0, 4 ); //Don't format numbers here, as it could break scripts using the API.
							$data[$variable] = Misc::removeTrailingZeros( $this->$function(), 0 );
							break;
						default:
							if ( method_exists( $this, $function ) ) {
								$data[$variable] = $this->$function();
							}
							break;
					}
				}
			}
			$this->getPermissionColumns( $data, $this->getUser(), $this->getCreatedBy(), $permission_children_ids, $include_columns );
			$this->getCreatedAndUpdatedColumns( $data, $include_columns );
		}

		return $data;
	}

	/**
	 * @param $log_action
	 * @return bool
	 */
	function addLog( $log_action ) {
		$u_obj = $this->getUserObject();
		if ( is_object($u_obj) ) {
			return TTLog::addEntry( $this->getId(), $log_action, TTi18n::getText('Employee Wage') .': '. $u_obj->getFullName(FALSE, TRUE), NULL, $this->getTable(), $this );
		}

		return FALSE;
	}
}
?>
