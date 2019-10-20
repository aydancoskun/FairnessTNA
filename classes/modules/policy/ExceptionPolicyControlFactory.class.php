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
class ExceptionPolicyControlFactory extends Factory {
	protected $table = 'exception_policy_control';
	protected $pk_sequence_name = 'exception_policy_control_id_seq'; //PK Sequence name

	protected $company_obj = NULL;

	/**
	 * @param $name
	 * @param null $parent
	 * @return array|null
	 */
	function _getFactoryOptions( $name, $parent = NULL ) {

		$retval = NULL;
		switch( $name ) {
			case 'columns':
				$retval = array(
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
										'name' => 'Name',
										'description' => 'Description',
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
		// Name
		if ( $this->Validator->getValidateOnly() == FALSE ) { //Don't check the below when mass editing.
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
		//
		// ABOVE: Validation code moved from set*() functions.
		//
		if ( $this->getDeleted() == TRUE ) {
			//Check to make sure nothing else references this policy, so we can be sure its okay to delete it.
			$pglf = TTnew( 'PolicyGroupListFactory' ); /** @var PolicyGroupListFactory $pglf */
			$pglf->getAPISearchByCompanyIdAndArrayCriteria( $this->getCompany(), array('exception_policy_control' => $this->getId() ), 1 );
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
		return TTLog::addEntry( $this->getId(), $log_action, TTi18n::getText('Exception Policy'), NULL, $this->getTable(), $this );
	}
}
?>
