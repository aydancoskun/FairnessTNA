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
class AccrualPolicyMilestoneFactory extends Factory {
	protected $table = 'accrual_policy_milestone';
	protected $pk_sequence_name = 'accrual_policy_milestone_id_seq'; //PK Sequence name

	protected $accrual_policy_obj = NULL;

	protected $length_of_service_multiplier = array(
										0  => 0,
										10 => 1,
										20 => 7,
										30 => 30.4167,
										40 => 365.25,
										50 => 0.04166666666666666667, // 1/24th of a day.
									);

	/**
	 * @param $name
	 * @param null $parent
	 * @return array|null
	 */
	function _getFactoryOptions( $name, $parent = NULL ) {

		$retval = NULL;
		switch( $name ) {
			case 'length_of_service_unit':
				$retval = array(
										10 => TTi18n::gettext('Day(s)'),
										20 => TTi18n::gettext('Week(s)'),
										30 => TTi18n::gettext('Month(s)'),
										40 => TTi18n::gettext('Year(s)'),
										50 => TTi18n::gettext('Hour(s)'),
									);
				break;
			case 'columns':
				$retval = array(
										'-1010-length_of_service' => TTi18n::gettext('Length Of Service'),
										'-1020-length_of_service_unit' => TTi18n::gettext('Units'),
										'-1030-accrual_rate' => TTi18n::gettext('Accrual Rate'),
										'-1050-maximum_time' => TTi18n::gettext('Maximum Time'),
										'-1050-rollover_time' => TTi18n::gettext('Rollover Time'),

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
								'length_of_service',
								'length_of_service_unit',
								'accrual_rate',
								'maximum_time',
								'rollover_time',
								);
				break;
			case 'unique_columns': //Columns that are unique, and disabled for mass editing.
				$retval = array();
				break;
			case 'linked_columns': //Columns that are linked together, mainly for Mass Edit, if one changes, they all must.
				$retval = array();
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
											'accrual_policy_id' => 'AccrualPolicy',
											'length_of_service_days' => 'LengthOfServiceDays',
											'length_of_service' => 'LengthOfService',
											'length_of_service_unit_id' => 'LengthOfServiceUnit',
											//'length_of_service_unit' => FALSE,
											'accrual_rate' => 'AccrualRate',
											'annual_maximum_time' => 'AnnualMaximumTime',
											'maximum_time' => 'MaximumTime',
											'rollover_time' => 'RolloverTime',
											'deleted' => 'Deleted',
											);
			return $variable_function_map;
	}

	/**
	 * @return bool|null
	 */
	function getAccrualPolicyObject() {
		if ( is_object($this->accrual_policy_obj) ) {
			return $this->accrual_policy_obj;
		} else {
			$aplf = TTnew( 'AccrualPolicyListFactory' ); /** @var AccrualPolicyListFactory $aplf */
			$aplf->getById( $this->getAccrualPolicyID() );
			if ( $aplf->getRecordCount() > 0 ) {
				$this->accrual_policy_obj = $aplf->getCurrent();
				return $this->accrual_policy_obj;
			}

			return FALSE;
		}
	}

	/**
	 * @return bool|mixed
	 */
	function getAccrualPolicy() {
		return $this->getGenericDataValue( 'accrual_policy_id' );
	}

	/**
	 * @param string $value UUID
	 * @return bool
	 */
	function setAccrualPolicy( $value) {
		$value = TTUUID::castUUID( $value );
		return $this->setGenericDataValue( 'accrual_policy_id', $value );
	}

	/**
	 * If we just base LengthOfService on days, leap years and such can cause off-by-one errors.
	 * So we need to determine the exact dates when the milestones rollover and base it on that instead.
	 * @param int $milestone_rollover_date EPOCH
	 * @return bool|false|int
	 */
	function getLengthOfServiceDate( $milestone_rollover_date ) {
		switch ( $this->getLengthOfServiceUnit() ) {
			case 10: //Days
				$unit_str = 'Days';
				break;
			case 20: //Weeks
				$unit_str = 'Weeks';
				break;
			case 30: //Months
				$unit_str = 'Months';
				break;
			case 40: //Years
				$unit_str = 'Years';
				break;
		}

		if ( isset($unit_str) ) {
			//There appears to be a bug in PHP strtotime() where '+10.00 years' does not work, but '+10 years' or '+10.01 years' does.
			//Therefore to work around this issue always cast the length of service to a float.
			$retval = TTDate::getBeginDayEpoch( strtotime( '+'. (float)$this->getLengthOfService() .' '. $unit_str, $milestone_rollover_date ) );
			Debug::text('MileStone Rollover Days based on Length Of Service: '. TTDate::getDate('DATE+TIME', $retval), __FILE__, __LINE__, __METHOD__, 10);
			return $retval;
		}

		return FALSE;
	}

	/**
	 * @return bool|int
	 */
	function getLengthOfServiceDays() {
		return $this->getGenericDataValue( 'length_of_service_days' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setLengthOfServiceDays( $value) {
		$value = (int)trim($value);
		Debug::text('aLength of Service Days: '. $value, __FILE__, __LINE__, __METHOD__, 10);
		if ( $value >= 0 ) {
			$this->setGenericDataValue( 'length_of_service_days', (int)$this->Validator->stripNon32bitInteger( bcmul( $value, $this->length_of_service_multiplier[$this->getLengthOfServiceUnit()], 4) ) );
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * @return bool|int
	 */
	function getLengthOfService() {
		$value = $this->getGenericDataValue( 'length_of_service' );
		if ( $value !== FALSE ) {
			return Misc::removeTrailingZeros( (float)$value, 0 );
		}

		return FALSE;
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setLengthOfService( $value) {
		$value = (float)trim($value);

		Debug::text('bLength of Service: '. $value, __FILE__, __LINE__, __METHOD__, 10);
		if ( $value >= 0 ) {
			$this->setGenericDataValue( 'length_of_service', $value );
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * @return bool|int
	 */
	function getLengthOfServiceUnit() {
		return $this->getGenericDataValue( 'length_of_service_unit_id' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setLengthOfServiceUnit( $value) {
		$value = (int)trim($value);
		return $this->setGenericDataValue( 'length_of_service_unit_id', $value );
	}

	/**
	 * @return bool|mixed
	 */
	function getAccrualRate() {
		return $this->getGenericDataValue( 'accrual_rate' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setAccrualRate( $value) {
		$value = (float)trim($value);
		return $this->setGenericDataValue( 'accrual_rate', $value );
	}

	/**
	 * @return bool|int
	 */
	function getAnnualMaximumTime() {
		return $this->getGenericDataValue( 'annual_maximum_time' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setAnnualMaximumTime( $value) {
		$value = (int)trim($value);
		return $this->setGenericDataValue( 'annual_maximum_time', (int)$this->Validator->stripNon32bitInteger( $value ) );
	}

	/**
	 * @return bool|int
	 */
	function getMaximumTime() {
		return $this->getGenericDataValue( 'maximum_time' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setMaximumTime( $value) {
		$value = (int)trim($value);
		return $this->setGenericDataValue( 'maximum_time', (int)$this->Validator->stripNon32bitInteger( $value ) );
	}

	/**
	 * @return bool|int
	 */
	function getRolloverTime() {
		return $this->getGenericDataValue( 'rollover_time' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setRolloverTime( $value) {
		$value = (int)trim($value);
		return $this->setGenericDataValue( 'rollover_time', (int)$this->Validator->stripNon32bitInteger( $value ) );
	}

	/**
	 * @return bool
	 */
	function Validate() {
		//
		// BELOW: Validation code moved from set*() functions.
		//
		// Accrual Policy
		if ( $this->getAccrualPolicy() !== FALSE ) {
			$aplf = TTnew( 'AccrualPolicyListFactory' ); /** @var AccrualPolicyListFactory $aplf */
			$this->Validator->isResultSetWithRows(	'accrual_policy',
															$aplf->getByID($this->getAccrualPolicy()),
															TTi18n::gettext('Accrual Policy is invalid')
														);
		}
		// Length of service
		if ( $this->getLengthOfServiceDays() !== FALSE AND $this->getLengthOfServiceDays() >= 0 ) {
			$this->Validator->isFloat(			'length_of_service'.$this->getLabelID(),
														$this->getLengthOfServiceDays(),
														TTi18n::gettext('Length of service is invalid')
													);
		}
		// Length of service
		if ( $this->getLengthOfService() !== FALSE AND $this->getLengthOfService() >= 0 ) {
			$this->Validator->isFloat(			'length_of_service'.$this->getLabelID(),
														$this->getLengthOfService(),
														TTi18n::gettext('Length of service is invalid')
													);
		}
		// Length of service unit
		$this->Validator->inArrayKey(	'length_of_service_unit_id'.$this->getLabelID(),
												$this->getLengthOfServiceUnit(),
												TTi18n::gettext('Incorrect Length of service unit'),
												$this->getOptions('length_of_service_unit')
											);
		// Accrual Rate
		if ( $this->getAccrualRate() !== FALSE  ) {
			$this->Validator->isNumeric(		'accrual_rate'.$this->getLabelID(),
														$this->getAccrualRate(),
														TTi18n::gettext('Incorrect Accrual Rate')
													);
		}
		// Accrual Annual Maximum
		if ( $this->getAnnualMaximumTime() != '' ) {
			$this->Validator->isNumeric(		'annual_maximum_time'.$this->getLabelID(),
														$this->getAnnualMaximumTime(),
														TTi18n::gettext('Incorrect Accrual Annual Maximum')
													);
		}
		// Maximum Balance
		if ( $this->getMaximumTime() != '' ) {
			$this->Validator->isNumeric(		'maximum_time'.$this->getLabelID(),
														$this->getMaximumTime(),
														TTi18n::gettext('Incorrect Maximum Balance')
													);
		}
		//  Rollover Time
		if ( $this->getRolloverTime() != '' ) {
			$this->Validator->isNumeric(		'rollover_time'.$this->getLabelID(),
														$this->getRolloverTime(),
														TTi18n::gettext('Incorrect Rollover Time')
													);
		}

		//
		// ABOVE: Validation code moved from set*() functions.
		//
		if ( $this->Validator->getValidateOnly() == FALSE AND $this->getAccrualPolicy() == FALSE ) {
			$this->Validator->isTRUE(	'accrual_policy_id'.$this->getLabelID(),
										FALSE,
										TTi18n::gettext('Accrual Policy is invalid') );
		}

		return TRUE;
	}

	/**
	 * @return bool
	 */
	function preSave() {
		//Set Length of service in days.
		$this->setLengthOfServiceDays( $this->getLengthOfService() );

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
						/*
						//This is not displayed anywhere that needs it in text rather then from the options.
						case 'length_of_service_unit':
							//$function = 'getLengthOfServiceUnit';
							if ( method_exists( $this, $function ) ) {
								$data[$variable] = Option::getByKey( $this->getLengthOfServiceUnit(), $this->getOptions( $variable ) );
							}
							break;
						*/
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
		return TTLog::addEntry( $this->getAccrualPolicy(), $log_action, TTi18n::getText('Accrual Policy Milestone') .' (ID: '. $this->getID() .')', NULL, $this->getTable(), $this );
	}
}
?>
