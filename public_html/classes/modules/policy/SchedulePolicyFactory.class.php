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
class SchedulePolicyFactory extends Factory {
	protected $table = 'schedule_policy';
	protected $pk_sequence_name = 'schedule_policy_id_seq'; //PK Sequence name

	protected $company_obj = NULL;
	protected $meal_policy_obj = NULL;
	protected $break_policy_obj = NULL;
	protected $full_shift_absence_policy_obj = NULL;
	protected $partial_shift_absence_policy_obj = NULL;

	function _getFactoryOptions( $name, $parent = NULL ) {

		$retval = NULL;
		switch( $name ) {
			case 'columns':
				$retval = array(
										'-1020-name' => TTi18n::gettext('Name'),
										'-1025-description' => TTi18n::gettext('Description'),
										'-1040-full_shift_absence_policy' => TTi18n::gettext('Full Shift Undertime Absence Policy'),
										'-1041-partial_shift_absence_policy' => TTi18n::gettext('Partial Shift Undertime Absence Policy'),
										'-1060-start_stop_window' => TTi18n::gettext('Window'),

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
								'start_stop_window',
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

										'full_shift_absence_policy_id' => 'FullShiftAbsencePolicyID',
										'full_shift_absence_policy' => FALSE,
										'partial_shift_absence_policy_id' => 'PartialShiftAbsencePolicyID',
										'partial_shift_absence_policy' => FALSE,

										'meal_policy' => 'MealPolicy',
										'break_policy' => 'BreakPolicy',

										'include_regular_time_policy' => 'IncludeRegularTimePolicy',
										'exclude_regular_time_policy' => 'ExcludeRegularTimePolicy',
										'include_over_time_policy' => 'IncludeOverTimePolicy',
										'exclude_over_time_policy' => 'ExcludeOverTimePolicy',
										'include_premium_policy' => 'IncludePremiumPolicy',
										'exclude_premium_policy' => 'ExcludePremiumPolicy',

										'start_stop_window' => 'StartStopWindow',
										'deleted' => 'Deleted',
										);
		return $variable_function_map;
	}

	function getCompanyObject() {
		return $this->getGenericObject( 'CompanyListFactory', $this->getCompany(), 'company_obj' );
	}

	function getFullShiftAbsencePolicyObject() {
		return $this->getGenericObject( 'AbsencePolicyListFactory', $this->getFullShiftAbsencePolicyID(), 'full_shift_absence_policy_obj' );
	}
	function getPartialShiftAbsencePolicyObject() {
		return $this->getGenericObject( 'AbsencePolicyListFactory', $this->getPartialShiftAbsencePolicyID(), 'partial_shift_absence_policy_obj' );
	}

	function getMealPolicyObject( $meal_policy_id ) {
		if ( $meal_policy_id == '' ) {
			return FALSE;
		}

		Debug::Text('Meal Policy ID: '. $meal_policy_id .' Schedule Policy ID: '. $this->getId(), __FILE__, __LINE__, __METHOD__, 10);

		if ( isset($this->meal_policy_obj[$meal_policy_id])
			AND is_object($this->meal_policy_obj[$meal_policy_id]) ) {
			return $this->meal_policy_obj[$meal_policy_id];
		} else {
			$bplf = TTnew( 'MealPolicyListFactory' );
			$bplf->getById( $meal_policy_id );
			if ( $bplf->getRecordCount() > 0 ) {
				$this->meal_policy_obj[$meal_policy_id] = $bplf->getCurrent();
				return $this->meal_policy_obj[$meal_policy_id];
			}

			return FALSE;
		}
	}

	function getBreakPolicyObject( $break_policy_id ) {
		if ( $break_policy_id == '' ) {
			return FALSE;
		}

		Debug::Text('Break Policy ID: '. $break_policy_id .' Schedule Policy ID: '. $this->getId(), __FILE__, __LINE__, __METHOD__, 10);

		if ( isset($this->break_policy_obj[$break_policy_id])
			AND is_object($this->break_policy_obj[$break_policy_id]) ) {
			return $this->break_policy_obj[$break_policy_id];
		} else {
			$bplf = TTnew( 'BreakPolicyListFactory' );
			$bplf->getById( $break_policy_id );
			if ( $bplf->getRecordCount() > 0 ) {
				$this->break_policy_obj[$break_policy_id] = $bplf->getCurrent();
				return $this->break_policy_obj[$break_policy_id];
			}

			return FALSE;
		}
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

	//Checks to see if we need to revert to the meal policies defined in the policy group, or use the ones defined in the schedule policy.
	function isUsePolicyGroupMealPolicy() {
		if ( in_array( 0, (array)$this->getMealPolicy() ) ) {
			return TRUE;
		}
		return FALSE;
	}
	function getMealPolicy() {
		$retarr = CompanyGenericMapListFactory::getArrayByCompanyIDAndObjectTypeIDAndObjectID( $this->getCompany(), 155, $this->getID() );

		//Check if no CompanyGenericMap is *not* set at all, if so assume No Meal (-1)
		if ( $retarr === FALSE ) {
			$retarr = array( -1 );
		}

		return $retarr;
	}
	function setMealPolicy($ids) {
		//If NONE(-1) or Use Policy Group(0) are defined, unset all other ids.
		if ( is_array( $ids ) ) {
			if ( in_array( 0, $ids )  ) {
				$ids = array(0);
			} elseif ( in_array( -1, $ids ) ) {
				$ids = array(-1);
			}
		}
		return CompanyGenericMapFactory::setMapIDs( $this->getCompany(), 155, $this->getID(), $ids, FALSE, TRUE ); //Use relaxed ID range.
	}

	//Checks to see if we need to revert to the break policies defined in the policy group, or use the ones defined in the schedule policy.
	function isUsePolicyGroupBreakPolicy() {
		if ( in_array( 0, (array)$this->getBreakPolicy() ) ) {
			return TRUE;
		}
		return FALSE;
	}
	function getBreakPolicy() {
		$retarr = CompanyGenericMapListFactory::getArrayByCompanyIDAndObjectTypeIDAndObjectID( $this->getCompany(), 165, $this->getID() );

		//Check if no CompanyGenericMap is *not* set at all, if so assume No Break (-1)
		if ( $retarr === FALSE ) {
			$retarr = array( -1 );
		}

		return $retarr;

	}
	function setBreakPolicy($ids) {
		//If NONE(-1) or Use Policy Group (0) are defined, unset all other ids.
		if ( is_array( $ids ) ) {
			if ( in_array( 0, $ids )  ) {
				$ids = array(0);
			} elseif ( in_array( -1, $ids ) ) {
				$ids = array(-1);
			}
		}
		return CompanyGenericMapFactory::setMapIDs( $this->getCompany(), 165, $this->getID(), $ids, FALSE, TRUE ); //Use relaxed ID range.
	}

	function getFullShiftAbsencePolicyID() {
		if ( isset($this->data['full_shift_absence_policy_id']) ) {
			return (int)$this->data['full_shift_absence_policy_id'];
		}

		return FALSE;
	}
	function setFullShiftAbsencePolicyID($id) {
		$id = trim($id);

		if ( $id == '' OR empty($id) ) {
			$id = NULL;
		}

		$aplf = TTnew( 'AbsencePolicyListFactory' );

		if (
				$id == NULL
				OR
				$this->Validator->isResultSetWithRows(	'full_shift_absence_policy',
														$aplf->getByID($id),
														TTi18n::gettext('Invalid Full Shift Absence Policy')
														) ) {
			$this->data['full_shift_absence_policy_id'] = $id;

			return TRUE;
		}

		return FALSE;
	}

	function getPartialShiftAbsencePolicyID() {
		if ( isset($this->data['partial_shift_absence_policy_id']) ) {
			return (int)$this->data['partial_shift_absence_policy_id'];
		}

		return FALSE;
	}
	function setPartialShiftAbsencePolicyID($id) {
		$id = trim($id);

		if ( $id == '' OR empty($id) ) {
			$id = NULL;
		}

		$aplf = TTnew( 'AbsencePolicyListFactory' );

		if (
				$id == NULL
				OR
				$this->Validator->isResultSetWithRows(	'partial_shift_absence_policy',
														$aplf->getByID($id),
														TTi18n::gettext('Invalid Partial Shift Absence Policy')
														) ) {
			$this->data['partial_shift_absence_policy_id'] = $id;

			return TRUE;
		}

		return FALSE;
	}

	function getIncludeRegularTimePolicy() {
		return CompanyGenericMapListFactory::getArrayByCompanyIDAndObjectTypeIDAndObjectID( $this->getCompany(), 105, $this->getID() );
	}
	function setIncludeRegularTimePolicy($ids) {
		Debug::text('Setting Include Regular Time Policy IDs : ', __FILE__, __LINE__, __METHOD__, 10);
		return CompanyGenericMapFactory::setMapIDs( $this->getCompany(), 105, $this->getID(), $ids );
	}
	function getExcludeRegularTimePolicy() {
		return CompanyGenericMapListFactory::getArrayByCompanyIDAndObjectTypeIDAndObjectID( $this->getCompany(), 106, $this->getID() );
	}
	function setExcludeRegularTimePolicy($ids) {
		Debug::text('Setting Exclude Regular Time Policy IDs : ', __FILE__, __LINE__, __METHOD__, 10);
		return CompanyGenericMapFactory::setMapIDs( $this->getCompany(), 106, $this->getID(), $ids );
	}

	function getIncludeOverTimePolicy() {
		return CompanyGenericMapListFactory::getArrayByCompanyIDAndObjectTypeIDAndObjectID( $this->getCompany(), 115, $this->getID() );
	}
	function setIncludeOverTimePolicy($ids) {
		Debug::text('Setting Include Over Time Policy IDs : ', __FILE__, __LINE__, __METHOD__, 10);
		return CompanyGenericMapFactory::setMapIDs( $this->getCompany(), 115, $this->getID(), $ids );
	}
	function getExcludeOverTimePolicy() {
		return CompanyGenericMapListFactory::getArrayByCompanyIDAndObjectTypeIDAndObjectID( $this->getCompany(), 116, $this->getID() );
	}
	function setExcludeOverTimePolicy($ids) {
		Debug::text('Setting Exclude Over Time Policy IDs : ', __FILE__, __LINE__, __METHOD__, 10);
		return CompanyGenericMapFactory::setMapIDs( $this->getCompany(), 116, $this->getID(), $ids );
	}

	function getIncludePremiumPolicy() {
		return CompanyGenericMapListFactory::getArrayByCompanyIDAndObjectTypeIDAndObjectID( $this->getCompany(), 125, $this->getID() );
	}
	function setIncludePremiumPolicy($ids) {
		Debug::text('Setting Include Premium Policy IDs : ', __FILE__, __LINE__, __METHOD__, 10);
		return CompanyGenericMapFactory::setMapIDs( $this->getCompany(), 125, $this->getID(), $ids );
	}
	function getExcludePremiumPolicy() {
		return CompanyGenericMapListFactory::getArrayByCompanyIDAndObjectTypeIDAndObjectID( $this->getCompany(), 126, $this->getID() );
	}
	function setExcludePremiumPolicy($ids) {
		Debug::text('Setting Exclude Premium Policy IDs : ', __FILE__, __LINE__, __METHOD__, 10);
		return CompanyGenericMapFactory::setMapIDs( $this->getCompany(), 126, $this->getID(), $ids );
	}

	function getStartStopWindow() {
		if ( isset($this->data['start_stop_window']) ) {
			return (int)$this->data['start_stop_window'];
		}
		return FALSE;
	}
	function setStartStopWindow($int) {
		$int = (int)$int;

		if	(	$this->Validator->isNumeric(		'start_stop_window',
													$int,
													TTi18n::gettext('Incorrect Start/Stop window')) ) {
			$this->data['start_stop_window'] = $int;

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

		return TRUE;
	}

	function preSave() {
		return TRUE;
	}

	function postSave() {
		if ( $this->getDeleted() == TRUE ) {
			Debug::Text('UnAssign Schedule Policy from Schedule/Recurring Schedules...'. $this->getId(), __FILE__, __LINE__, __METHOD__, 10);
			$sf = TTnew( 'ScheduleFactory' );
			$rstf = TTnew( 'RecurringScheduleTemplateFactory' );

			$query = 'update '. $sf->getTable() .' set schedule_policy_id = 0 where schedule_policy_id = '. (int)$this->getId();
			$this->db->Execute($query);

			$query = 'update '. $rstf->getTable() .' set schedule_policy_id = 0 where schedule_policy_id = '. (int)$this->getId();
			$this->db->Execute($query);
		}

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
						case 'full_shift_absence_policy':
						case 'partial_shift_absence_policy':
							$data[$variable] = $this->getColumn($variable);
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
		return TTLog::addEntry( $this->getId(), $log_action, TTi18n::getText('Schedule Policy'), NULL, $this->getTable(), $this );
	}
}
?>
