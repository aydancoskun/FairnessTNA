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
class AbsencePolicyFactory extends Factory {
	protected $table = 'absence_policy';
	protected $pk_sequence_name = 'absence_policy_id_seq'; //PK Sequence name

	protected $company_obj = NULL;
	protected $pay_code_obj = NULL;
	protected $pay_formula_policy_obj = NULL;

	function _getFactoryOptions( $name, $parent = NULL ) {

		$retval = NULL;
		switch( $name ) {
/*
			case 'type':
				$retval = array(
										10 => TTi18n::gettext('Paid'),
										12 => TTi18n::gettext('Paid (Above Salary)'),
										20 => TTi18n::gettext('Unpaid'),
										30 => TTi18n::gettext('Dock'),
									);
				break;
			case 'paid_type': //Types that are considered paid.
				$retval = array(10, 12);
				break;
*/
			case 'columns':
				$retval = array(
										'-1020-name' => TTi18n::gettext('Name'),
										'-1025-description' => TTi18n::gettext('Description'),

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
											//'type_id' => 'Type',
											//'type' => FALSE,
											'name' => 'Name',
											'description' => 'Description',

											'pay_code_id' => 'PayCode',
											'pay_code' => FALSE,
											'pay_formula_policy_id' => 'PayFormulaPolicy',
											'pay_formula_policy' => FALSE,

											'in_use' => FALSE,
											'deleted' => 'Deleted',
											);
			return $variable_function_map;
	}

	function getCompanyObject() {
		return $this->getGenericObject( 'CompanyListFactory', $this->getCompany(), 'company_obj' );
	}

	function getPayCodeObject() {
		return $this->getGenericObject( 'PayCodeListFactory', $this->getPayCode(), 'pay_code_obj' );
	}

	function getPayFormulaPolicyObject( $id = FALSE ) {
		if ( $id == FALSE ) {
			$id = $this->getPayFormulaPolicy();
			if ( $id == 0 ) {
				$pc_obj = $this->getPayCodeObject();
				if ( is_object( $pc_obj ) ) {
					$id = $pc_obj->getPayFormulaPolicy();
				}
			}
		}
		return $this->getGenericObject( 'PayFormulaPolicyListFactory', $id, 'pay_formula_policy_obj' );
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

	function getPayCode() {
		if ( isset($this->data['pay_code_id']) ) {
			return (int)$this->data['pay_code_id'];
		}

		return FALSE;
	}
	function setPayCode($id) {
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
			$pglf->getAPISearchByCompanyIdAndArrayCriteria( $this->getCompany(), array('absence_policy' => $this->getId() ), 1 );
			if ( $pglf->getRecordCount() > 0 ) {
				$this->Validator->isTRUE( 'in_use',
										  FALSE,
										  TTi18n::gettext( 'This policy is currently in use' ) . ' ' . TTi18n::gettext( 'by policy groups' ) );
			}

			$hplf = TTnew( 'HolidayPolicyListFactory' );
			$hplf->getAPISearchByCompanyIdAndArrayCriteria( $this->getCompany(), array('absence_policy' => $this->getId() ), 1 );
			if ( $hplf->getRecordCount() > 0 ) {
				$this->Validator->isTRUE( 'in_use',
										  FALSE,
										  TTi18n::gettext( 'This policy is currently in use' ) . ' ' . TTi18n::gettext( 'by holiday policies' ) );
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
						case 'accrual_policy':
							$data[$variable] = $this->getColumn( $variable );
							break;
						case 'type':
							$function = 'get'.$variable;
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

	function addLog( $log_action ) {
		return TTLog::addEntry( $this->getId(), $log_action, TTi18n::getText('Absence Policy'), NULL, $this->getTable(), $this );
	}
}
?>
