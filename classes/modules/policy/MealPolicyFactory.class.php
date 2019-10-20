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
class MealPolicyFactory extends Factory {
	protected $table = 'meal_policy';
	protected $pk_sequence_name = 'meal_policy_id_seq'; //PK Sequence name

	protected $company_obj = NULL;
	protected $pay_code_obj = NULL;


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
										10 => TTi18n::gettext('Auto-Deduct'),
										15 => TTi18n::gettext('Auto-Add'),
										20 => TTi18n::gettext('Normal')
									);
				break;
			case 'auto_detect_type':
				$retval = array(
										10 => TTi18n::gettext('Time Window'),
										20 => TTi18n::gettext('Punch Time'),
									);
				break;
			case 'columns':
				$retval = array(
										'-1010-type' => TTi18n::gettext('Type'),
										'-1020-name' => TTi18n::gettext('Name'),
										'-1025-description' => TTi18n::gettext('Description'),
										'-1030-amount' => TTi18n::gettext('Meal Time'),
										'-1040-trigger_time' => TTi18n::gettext('Active After'),

										'-1050-auto_detect_type' => TTi18n::gettext('Auto Detect Meals By'),
										//'-1060-start_window' => TTi18n::gettext('Start Window'),
										//'-1070-window_length' => TTi18n::gettext('Window Length'),
										//'-1080-minimum_punch_time' => TTi18n::gettext('Minimum Punch Time'),
										//'-1090-maximum_punch_time' => TTi18n::gettext('Maximum Punch Time'),

										'-1100-include_lunch_punch_time' => TTi18n::gettext('Include Lunch Punch'),

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
								'amount',
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
										'name' => 'Name',
										'description' => 'Description',
										'trigger_time' => 'TriggerTime',
										'amount' => 'Amount',
										'auto_detect_type_id' => 'AutoDetectType',
										'auto_detect_type' => FALSE,
										'start_window' => 'StartWindow',
										'window_length' => 'WindowLength',
										'minimum_punch_time' => 'MinimumPunchTime',
										'maximum_punch_time' => 'MaximumPunchTime',
										'include_lunch_punch_time' => 'IncludeLunchPunchTime',

										'pay_code_id' => 'PayCode',
										'pay_code' => FALSE,
										'pay_formula_policy_id' => 'PayFormulaPolicy',
										'pay_formula_policy' => FALSE,

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
	function getPayCodeObject() {
		return $this->getGenericObject( 'PayCodeListFactory', $this->getPayCode(), 'pay_code_obj' );
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
	 * @return bool|int
	 */
	function getTriggerTime() {
		return $this->getGenericDataValue( 'trigger_time' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setTriggerTime( $value) {
		$value = (int)trim($value);
		return $this->setGenericDataValue( 'trigger_time', $value );
	}

	/**
	 * @return bool|mixed
	 */
	function getAmount() {
		return $this->getGenericDataValue( 'amount' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setAmount( $value) {
		$value = trim($value);
		return $this->setGenericDataValue( 'amount', $value );
	}

	/**
	 * @return bool|int
	 */
	function getAutoDetectType() {
		return $this->getGenericDataValue( 'auto_detect_type_id' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setAutoDetectType( $value) {
		$value = (int)trim($value);
		return $this->setGenericDataValue( 'auto_detect_type_id', $value );
	}

	/**
	 * @return bool|mixed
	 */
	function getStartWindow() {
		return $this->getGenericDataValue( 'start_window' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setStartWindow( $value) {
		$value = (int)trim($value);
		return $this->setGenericDataValue( 'start_window', $value );
	}

	/**
	 * @return bool|mixed
	 */
	function getWindowLength() {
		return $this->getGenericDataValue( 'window_length' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setWindowLength( $value) {
		$value = (int)trim($value);
		return $this->setGenericDataValue( 'window_length', $value );
	}

	/**
	 * @return bool|mixed
	 */
	function getMinimumPunchTime() {
		return $this->getGenericDataValue( 'minimum_punch_time' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setMinimumPunchTime( $value) {
		$value = (int)trim($value);
		return $this->setGenericDataValue( 'minimum_punch_time', $value );
	}

	/**
	 * @return bool|mixed
	 */
	function getMaximumPunchTime() {
		return $this->getGenericDataValue( 'maximum_punch_time' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setMaximumPunchTime( $value) {
		$value = (int)trim($value);
		return $this->setGenericDataValue( 'maximum_punch_time', $value );
	}

	/*
		This takes into account any lunch punches when calculating the meal policy.
		If enabled for:
			Auto-Deduct:	It will only deduct the amount that is not taken in lunch time.
							So if they auto-deduct 60mins, and an employee takes 30mins of lunch,
							it will deduct the remaining 30mins to equal 60mins. If they don't
							take any lunch, it deducts the full 60mins.
			Auto-Include:	It will include the amount taken in lunch time, up to the amount given.
							So if they auto-include 30mins and an employee takes a 60min lunch
							only 30mins will be included, and 30mins is automatically deducted
							as a regular lunch punch.
							If they don't take a lunch, it doesn't include any time.

		If not enabled for:
		Auto-Deduct: Always deducts the amount.
		Auto-Inlcyde: Always includes the amount.
	*/
	/**
	 * @return bool
	 */
	function getIncludeLunchPunchTime() {
		return $this->fromBool( $this->getGenericDataValue( 'include_lunch_punch_time' ) );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setIncludeLunchPunchTime( $value) {
		return $this->setGenericDataValue( 'include_lunch_punch_time', $this->toBool($value) );
	}

	/**
	 * @return bool|mixed
	 */
	function getPayCode() {
		return $this->getGenericDataValue( 'pay_code_id' );
	}

	/**
	 * @param string $value UUID
	 * @return bool
	 */
	function setPayCode( $value) {
		$value = TTUUID::castUUID( $value );
		return $this->setGenericDataValue( 'pay_code_id', $value );
	}

	/**
	 * @return bool|mixed
	 */
	function getPayFormulaPolicy() {
		return $this->getGenericDataValue( 'pay_formula_policy_id' );
	}

	/**
	 * @param string $value UUID
	 * @return bool
	 */
	function setPayFormulaPolicy( $value) {
		$value = TTUUID::castUUID( $value );
		return $this->setGenericDataValue( 'pay_formula_policy_id', $value );
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
		// Name
		if ( $this->Validator->getValidateOnly() == FALSE ) {
			if ( $this->getName() == '' ) {
				$this->Validator->isTRUE(	'name',
											FALSE,
											TTi18n::gettext('Please specify a name') );
									}
		}
		if ( $this->getName() != '' AND $this->Validator->isError('name') == FALSE ) {
			$this->Validator->isLength(	'name',
												$this->getName(),
												TTi18n::gettext('Name is too short or too long'),
												2, 50
											);
		}
		if ( $this->getName() != '' AND $this->Validator->isError('name') == FALSE ) {
			$this->Validator->isTrue(	'name',
												$this->isUniqueName($this->getName()),
												TTi18n::gettext('Name is already in use')
											);
		}
		// Description
		if ( $this->getDescription() != '' ) {
			$this->Validator->isLength(	'description',
												$this->getDescription(),
												TTi18n::gettext('Description is invalid'),
												1, 250
											);
		}
		// Trigger Time
		if ( $this->getTriggerTime() !== FALSE ) {
			$this->Validator->isNumeric(		'trigger_time',
														$this->getTriggerTime(),
														TTi18n::gettext('Incorrect Trigger Time')
													);
		}
		// Deduction Amount
		if ( $this->getAmount() !== FALSE ) {
			$this->Validator->isNumeric(		'amount',
														$this->getAmount(),
														TTi18n::gettext('Incorrect Deduction Amount')
													);
		}
		// Auto-Detect Type
		if ( $this->getAutoDetectType() !== FALSE ) {
			$this->Validator->inArrayKey(	'auto_detect_type',
													$this->getAutoDetectType(),
													TTi18n::gettext('Incorrect Auto-Detect Type'),
													$this->getOptions('auto_detect_type')
												);
		}
		// Start Window
		if ( $this->getStartWindow() != '' ) {
			$this->Validator->isNumeric(		'start_window',
														$this->getStartWindow(),
														TTi18n::gettext('Incorrect Start Window')
													);
		}
		// Window Length
		if ( $this->getWindowLength() != '' ) {
			$this->Validator->isNumeric(		'window_length',
														$this->getWindowLength(),
														TTi18n::gettext('Incorrect Window Length')
													);
		}
		// Minimum Punch Time
		if ( $this->getMinimumPunchTime() != '' ) {
			$this->Validator->isNumeric(		'minimum_punch_time',
														$this->getMinimumPunchTime(),
														TTi18n::gettext('Incorrect Minimum Punch Time')
													);
		}
		// Maximum Punch Time
		if ( $this->getMaximumPunchTime() != '' ) {
			$this->Validator->isNumeric(		'maximum_punch_time',
														$this->getMaximumPunchTime(),
														TTi18n::gettext('Incorrect Maximum Punch Time')
													);
		}
		// Pay Code
		if ( $this->getPayCode() !== FALSE AND $this->getPayCode() != TTUUID::getZeroID() ) {
			$pclf = TTnew( 'PayCodeListFactory' ); /** @var PayCodeListFactory $pclf */
			$this->Validator->isResultSetWithRows(	'pay_code_id',
														$pclf->getById($this->getPayCode()),
														TTi18n::gettext('Invalid Pay Code')
													);
		}
		// Pay Formula Policy
		if ( $this->getPayFormulaPolicy() !== FALSE AND $this->getPayFormulaPolicy() != TTUUID::getZeroID() ) {
			$pfplf = TTnew( 'PayFormulaPolicyListFactory' ); /** @var PayFormulaPolicyListFactory $pfplf */
			$this->Validator->isResultSetWithRows(	'pay_formula_policy_id',
															$pfplf->getByID($this->getPayFormulaPolicy()),
															TTi18n::gettext('Pay Formula Policy is invalid')
														);
		}

		//
		// ABOVE: Validation code moved from set*() functions.
		//


		if ( $this->getDeleted() != TRUE AND $this->Validator->getValidateOnly() == FALSE ) { //Don't check the below when mass editing.
			if ( $this->getPayCode() == TTUUID::getZeroID() ) {
				$this->Validator->isTRUE(	'pay_code_id',
											FALSE,
											TTi18n::gettext('Please choose a Pay Code') );
			}

			//Make sure Pay Formula Policy is defined somewhere.
			if ( $this->getPayFormulaPolicy() == TTUUID::getZeroID()
					AND ( TTUUID::isUUID( $this->getPayCode() ) AND $this->getPayCode() != TTUUID::getZeroID() AND $this->getPayCode() != TTUUID::getNotExistID() )
					AND ( !is_object( $this->getPayCodeObject() ) OR ( is_object( $this->getPayCodeObject() ) AND $this->getPayCodeObject()->getPayFormulaPolicy() == TTUUID::getZeroID() ) ) ) {
					$this->Validator->isTRUE(	'pay_formula_policy_id',
												FALSE,
												TTi18n::gettext('Selected Pay Code does not have a Pay Formula Policy defined'));
			}
		}

		if ( $this->getDeleted() == TRUE ) {
			//Check to make sure nothing else references this policy, so we can be sure its okay to delete it.
			$pglf = TTnew( 'PolicyGroupListFactory' ); /** @var PolicyGroupListFactory $pglf */
			$pglf->getAPISearchByCompanyIdAndArrayCriteria( $this->getCompany(), array('meal_policy' => $this->getId() ), 1 );
			if ( $pglf->getRecordCount() > 0 ) {
				$this->Validator->isTRUE( 'in_use',
										  FALSE,
										  TTi18n::gettext( 'This policy is currently in use' ) . ' ' . TTi18n::gettext( 'by policy groups' ) );
			}

			$splf = TTnew( 'SchedulePolicyListFactory' ); /** @var SchedulePolicyListFactory $splf */
			$splf->getAPISearchByCompanyIdAndArrayCriteria( $this->getCompany(), array('meal_policy_id' => $this->getId() ), 1 );
			if ( $splf->getRecordCount() > 0 ) {
				$this->Validator->isTRUE( 'in_use',
										  FALSE,
										  TTi18n::gettext( 'This policy is currently in use' ) . ' ' . TTi18n::gettext( 'by schedule policies' ) );
			}
		}

		return TRUE;
	}

	/**
	 * @return bool
	 */
	function preSave() {
		if ( $this->getAutoDetectType() == FALSE ) {
			$this->setAutoDetectType( 10 );
		}
		return TRUE;
	}

	/**
	 * @return bool
	 */
	function postSave() {
		$this->removeCache( $this->getId() );

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
						case 'auto_detect_type':
							$function = 'get'.str_replace('_', '', $variable);
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
		return TTLog::addEntry( $this->getId(), $log_action, TTi18n::getText('Meal Policy'), NULL, $this->getTable(), $this );
	}
}
?>
