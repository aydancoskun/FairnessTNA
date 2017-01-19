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
class RoundIntervalPolicyFactory extends Factory {
	protected $table = 'round_interval_policy';
	protected $pk_sequence_name = 'round_interval_policy_id_seq'; //PK Sequence name

	protected $company_obj = NULL;

	//Just need relations for each actual Punch Type
	function _getFactoryOptions( $name, $parent = NULL ) {

		$retval = NULL;
		switch( $name ) {
			case 'round_type':
				$retval = array(
										10 => TTi18n::gettext('Down'),
										20 => TTi18n::gettext('Average'),
										25 => TTi18n::gettext('Average (Partial Min. Down)'),
										27 => TTi18n::gettext('Average (Partial Min. Up)'),
										30 => TTi18n::gettext('Up')
									);
				break;
			case 'punch_type':
				$retval = array(
										10 => TTi18n::gettext('All Punches'),
										20 => TTi18n::gettext('All In (incl. Lunch/Break)'),
										30 => TTi18n::gettext('All Out (incl. Lunch/Break)'),
										40 => TTi18n::gettext('Normal - In'),
										50 => TTi18n::gettext('Normal - Out'),
										60 => TTi18n::gettext('Lunch - In'),
										70 => TTi18n::gettext('Lunch - Out'),
										80 => TTi18n::gettext('Break - In'),
										90 => TTi18n::gettext('Break - Out'),
										100 => TTi18n::gettext('Lunch Total'),
										110 => TTi18n::gettext('Break Total'),
										120 => TTi18n::gettext('Day Total'),
									);
				break;
			case 'punch_type_relation':
				$retval = array(
										40 => array(10, 20),
										50 => array(10, 30, 120),
										60 => array(10, 20, 100),
										70 => array(10, 30),
										80 => array(10, 20, 110),
										90 => array(10, 30),
									);
				break;
			case 'condition_type':
				$retval = array(
										0 => TTi18n::gettext('Disabled'),
										10 => TTi18n::gettext('Scheduled Time'),
										20 => TTi18n::gettext('Scheduled Time or Not Scheduled'),
										30 => TTi18n::gettext('Static Time'), //For specific time of day, ie: 8AM
										40 => TTi18n::gettext('Static Total Time'), //For Day/Lunch/Break total.
									);
				break;
			case 'columns':
				$retval = array(
										'-1010-punch_type' => TTi18n::gettext('Punch Type'),
										'-1020-round_type' => TTi18n::gettext('Round Type'),
										'-1030-name' => TTi18n::gettext('Name'),
										'-1035-description' => TTi18n::gettext('Description'),

										'-1040-round_interval' => TTi18n::gettext('Interval'),

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
								'punch_type',
								'round_type',
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
										'name' => 'Name',
										'description' => 'Description',
										'round_type_id' => 'RoundType',
										'round_type' => FALSE,
										'punch_type_id' => 'PunchType',
										'punch_type' => FALSE,
										'round_interval' => 'Interval',
										'grace' => 'Grace',
										'strict' => 'Strict',

										'condition_type_id' => 'ConditionType',
										'condition_static_time' => 'ConditionStaticTime',
										'condition_static_total_time' => 'ConditionStaticTotalTime',
										'condition_start_window' => 'ConditionStartWindow',
										'condition_stop_window' => 'ConditionStopWindow',

										'in_use' => FALSE,
										'deleted' => 'Deleted',
										);
		return $variable_function_map;
	}

	function getCompanyObject() {
		return $this->getGenericObject( 'CompanyListFactory', $this->getCompany(), 'company_obj' );
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

	function getPunchTypeFromPunchStatusAndType($status, $type) {
		if ( $status == '' ) {
			return FALSE;
		}

		if ( $type == '' ) {
			return FALSE;
		}

		switch($type) {
			case 10: //Normal
				if ( $status == 10 ) { //In
					$punch_type = 40;
				} else {
					$punch_type = 50;
				}
				break;
			case 20: //Lunch
				if ( $status == 10 ) { //In
					$punch_type = 60;
				} else {
					$punch_type = 70;
				}
				break;
			case 30: //Break
				if ( $status == 10 ) { //In
					$punch_type = 80;
				} else {
					$punch_type = 90;
				}
				break;
		}

		return $punch_type;
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

	function getRoundType() {
		if ( isset($this->data['round_type_id']) ) {
			return (int)$this->data['round_type_id'];
		}

		return FALSE;
	}
	function setRoundType($value) {
		$value = trim($value);

		if ( $this->Validator->inArrayKey(	'round_type',
											$value,
											TTi18n::gettext('Incorrect Round Type'),
											$this->getOptions('round_type')) ) {

			$this->data['round_type_id'] = $value;

			return TRUE;
		}

		return FALSE;
	}

	function getPunchType() {
		if ( isset($this->data['punch_type_id']) ) {
			return (int)$this->data['punch_type_id'];
		}

		return FALSE;
	}
	function setPunchType($value) {
		$value = trim($value);

		if ( $this->Validator->inArrayKey(	'punch_type',
											$value,
											TTi18n::gettext('Incorrect Punch Type'),
											$this->getOptions('punch_type')) ) {

			$this->data['punch_type_id'] = $value;

			return TRUE;
		}

		return FALSE;
	}

	function getInterval() {
		if ( isset($this->data['round_interval']) ) {
			return $this->data['round_interval'];
		}

		return FALSE;
	}
	function setInterval($value) {
		$value = trim($value);

		if	(	$this->Validator->isNumeric(		'interval',
													$value,
													TTi18n::gettext('Incorrect Interval')) ) {

			//If someone is using hour parse format ie: 0.12 we need to round to the nearest
			//minute other wise it'll be like 7mins and 23seconds messing up rounding.
			//$this->data['round_interval'] = $value;
			$this->data['round_interval'] = TTDate::roundTime($value, 60, 20);


			return TRUE;
		}

		return FALSE;
	}

	function getGrace() {
		if ( isset($this->data['grace']) ) {
			return $this->data['grace'];
		}

		return FALSE;
	}
	function setGrace($value) {
		$value = trim($value);

		if	(	$this->Validator->isNumeric(		'grace',
													$value,
													TTi18n::gettext('Incorrect grace value')) ) {

			//If someone is using hour parse format ie: 0.12 we need to round to the nearest
			//minute other wise it'll be like 7mins and 23seconds messing up rounding.
			//$this->data['grace'] = $value;
			$this->data['grace'] = TTDate::roundTime($value, 60, 20);

			return TRUE;
		}

		return FALSE;
	}

	function getStrict() {
		return $this->fromBool( $this->data['strict'] );
	}
	function setStrict($bool) {
		$this->data['strict'] = $this->toBool($bool);

		return TRUE;
	}

	function inConditionWindow( $epoch, $window_epoch ) {
		if (
				$epoch >= ( $window_epoch - $this->getConditionStartWindow() )
				AND
				$epoch <= ( $window_epoch + $this->getConditionStopWindow() )
			) {
			return TRUE;
		}

		Debug::Text('Not in Condition Window... Epoch: '. TTDate::getDate('DATE+TIME', $epoch ) .' Window Epoch: '. TTDate::getDate('DATE+TIME', $window_epoch ) .' Window Start: '. $this->getConditionStartWindow() .' Stop: '. $this->getConditionStopWindow(), __FILE__, __LINE__, __METHOD__, 10);
		return FALSE;
	}
	function isConditionTrue( $epoch, $schedule_time ) {
			return TRUE;
	}
	function getConditionType() {
		if ( isset($this->data['condition_type_id']) ) {
			return (int)$this->data['condition_type_id'];
		}

		return FALSE;
	}
	function setConditionType($value) {
		$value = trim($value);

		if ( $this->Validator->inArrayKey(	'condition_type',
											$value,
											TTi18n::gettext('Incorrect Condition Type'),
											$this->getOptions('condition_type')) ) {

			$this->data['condition_type_id'] = $value;

			return TRUE;
		}

		return FALSE;
	}

	function getConditionStaticTime( $raw = FALSE ) {
		if ( isset($this->data['condition_static_time']) ) {
			if ( $raw === TRUE) {
				return $this->data['condition_static_time'];
			} else {
				return TTDate::strtotime( $this->data['condition_static_time'] );
			}
		}

		return FALSE;
	}
	function setConditionStaticTime($epoch) {
		$epoch = ( !is_int($epoch) ) ? trim($epoch) : $epoch; //Dont trim integer values, as it changes them to strings.

		if	(	$epoch == ''
				OR
				$this->Validator->isDate(		'condition_static_time',
												$epoch,
												TTi18n::gettext('Incorrect Static time'))
			) {

			$this->data['condition_static_time'] = $epoch;

			return TRUE;
		}

		return FALSE;
	}

	function getConditionStaticTotalTime() {
		if ( isset($this->data['condition_static_total_time']) ) {
			return $this->data['condition_static_total_time'];
		}

		return FALSE;
	}
	function setConditionStaticTotalTime($value) {
		$value = trim($value);

		if	(	$this->Validator->isNumeric(		'condition_static_total_time',
													$value,
													TTi18n::gettext('Incorrect Static Total Time')) ) {

			//If someone is using hour parse format ie: 0.12 we need to round to the nearest
			//minute other wise it'll be like 7mins and 23seconds messing up rounding.
			//$this->data['round_interval'] = $value;
			$this->data['condition_static_total_time'] = TTDate::roundTime($value, 60, 20);


			return TRUE;
		}

		return FALSE;
	}

	function getConditionStartWindow() {
		if ( isset($this->data['condition_start_window']) ) {
			return $this->data['condition_start_window'];
		}

		return FALSE;
	}
	function setConditionStartWindow($value) {
		$value = trim($value);

		if	(	$this->Validator->isNumeric(		'condition_start_window',
													$value,
													TTi18n::gettext('Incorrect Start Window')) ) {

			//If someone is using hour parse format ie: 0.12 we need to round to the nearest
			//minute other wise it'll be like 7mins and 23seconds messing up rounding.
			//$this->data['round_interval'] = $value;
			$this->data['condition_start_window'] = TTDate::roundTime($value, 60, 20);


			return TRUE;
		}

		return FALSE;
	}

	function getConditionStopWindow() {
		if ( isset($this->data['condition_stop_window']) ) {
			return $this->data['condition_stop_window'];
		}

		return FALSE;
	}
	function setConditionStopWindow($value) {
		$value = trim($value);

		if	(	$this->Validator->isNumeric(		'condition_stop_window',
													$value,
													TTi18n::gettext('Incorrect Stop Window')) ) {

			//If someone is using hour parse format ie: 0.12 we need to round to the nearest
			//minute other wise it'll be like 7mins and 23seconds messing up rounding.
			//$this->data['round_interval'] = $value;
			$this->data['condition_stop_window'] = TTDate::roundTime($value, 60, 20);


			return TRUE;
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
		}

		if ( $this->getDeleted() == TRUE ) {
			//Check to make sure nothing else references this policy, so we can be sure its okay to delete it.
			$pglf = TTnew( 'PolicyGroupListFactory' );
			$pglf->getAPISearchByCompanyIdAndArrayCriteria( $this->getCompany(), array('round_interval_policy' => $this->getId() ), 1 );
			if ( $pglf->getRecordCount() > 0 ) {
				$this->Validator->isTRUE( 'in_use',
										  FALSE,
										  TTi18n::gettext( 'This policy is currently in use' ) . ' ' . TTi18n::gettext( 'by policy groups' ) );
			}
		}

		return TRUE;
	}

	function preSave() {
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
						case 'condition_static_time':
							$this->$function( TTDate::parseDateTime( $data[$key] ) );
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
						case 'punch_type':
						case 'round_type':
							$function = 'get'.str_replace('_', '', $variable);
							if ( method_exists( $this, $function ) ) {
								$data[$variable] = Option::getByKey( $this->$function(), $this->getOptions( $variable ) );
							}
							break;
						case 'condition_static_time':
							$data[$variable] = ( defined('FAIRNESS_API') ) ? TTDate::getAPIDate( 'TIME', TTDate::strtotime( $this->$function() ) ) : $this->$function();
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
		return TTLog::addEntry( $this->getId(), $log_action, TTi18n::getText('Round Interval Policy'), NULL, $this->getTable(), $this );
	}
}
?>
