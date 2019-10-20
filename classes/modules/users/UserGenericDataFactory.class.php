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
class UserGenericDataFactory extends Factory {
	protected $table = 'user_generic_data';
	protected $pk_sequence_name = 'user_generic_data_id_seq'; //PK Sequence name

	/**
	 * @param $data
	 * @return array
	 */
	function _getVariableToFunctionMap( $data ) {
		$variable_function_map = array(
										'id' => 'ID',
										'company_id' => 'Company',
										'user_id' => 'User',
										'script' => 'Script',
										'name' => 'Name',
										'is_default' => 'Default',
										'data' => 'Data',
										'deleted' => 'Deleted',
										);
		return $variable_function_map;
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
	function setCompany( $value ) {
		$value = TTUUID::castUUID( $value );
		return $this->setGenericDataValue( 'company_id', $value );
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
	function getScript() {
		return $this->getGenericDataValue( 'script' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setScript( $value) {
		//Strip out double slashes, as sometimes those occur and they cause the saved settings to not appear.
		$value = self::handleScriptName( trim($value) );
		return $this->setGenericDataValue( 'script', $value );
	}

	/**
	 * @param $name
	 * @return bool
	 */
	function isUniqueName( $name) {
		if ( $this->getCompany() == FALSE ) {
			return FALSE;
		}

		//Allow no user_id to be set yet, as that would be company generic data.

		if ( $this->getScript() == FALSE ) {
			return FALSE;
		}

		$name = trim($name);
		if ( $name == '' ) {
			return FALSE;
		}

		$ph = array(
					'company_id' => TTUUID::castUUID($this->getCompany()),
					'script' => $this->getScript(),
					'name' => TTi18n::strtolower( $name ),
					);

		$query = 'select id from '. $this->getTable() .'
					where
						company_id = ?
						AND script = ?
						AND lower(name) = ? ';
		if (  $this->getUser() != '' ) {
			$query .= ' AND user_id = \''. TTUUID::castUUID($this->getUser()) .'\'';
		} else {
			$query .= ' AND ( user_id = \''. TTUUID::getZeroID() .'\' OR user_id is NULL )';
		}
		$query .= ' AND deleted = 0';

		$name_id = $this->db->GetOne($query, $ph);
		Debug::Arr($name_id, 'Unique Name: '. $name, __FILE__, __LINE__, __METHOD__, 10);

		if ( $name_id === FALSE ) {
			return TRUE;
		} else {
			if ($name_id == $this->getId() ) {
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
	function setName( $value ) {
		$value = trim( $value );
		return $this->setGenericDataValue( 'name', $value );
	}

	/**
	 * @return bool
	 */
	function getDefault() {
		return $this->fromBool( $this->getGenericDataValue( 'is_default' ) );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setDefault( $value ) {
		return $this->setGenericDataValue( 'is_default', $this->toBool($value)  );
	}

	/**
	 * @return bool|mixed
	 */
	function getData() {
		$retval = @unserialize( $this->getGenericDataValue( 'data' ) ); //If the data is corrupted, stop any PHP warning.
		if ( $retval !== FALSE ) {
			return $retval;
		}

		Debug::Text('Failed to unserialize data: "'. $this->getGenericDataValue( 'data' ) .'"', __FILE__, __LINE__, __METHOD__, 10);
		return FALSE;
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setData( $value) {
		$value = serialize($value);

		$this->setGenericDataValue( 'data', $value );

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

		if ( $this->getDeleted() == FALSE ) {
			// Company
			$clf = TTnew( 'CompanyListFactory' ); /** @var CompanyListFactory $clf */
			$this->Validator->isResultSetWithRows( 'company',
												   $clf->getByID( $this->getCompany() ),
												   TTi18n::gettext( 'Invalid Company' )
			);
			// User
			if ( $this->getUser() != '' AND $this->getUser() != TTUUID::getZeroID() ) {
				$ulf = TTnew( 'UserListFactory' ); /** @var UserListFactory $ulf */
				$this->Validator->isResultSetWithRows( 'user',
													   $ulf->getByID( $this->getUser() ),
													   TTi18n::gettext( 'Invalid Employee' )
				);
			}
			// Script
			$this->Validator->isLength( 'script',
										$this->getScript(),
										TTi18n::gettext( 'Invalid script' ),
										1, 250
			);
			// Name
			$this->Validator->isLength( 'name',
										$this->getName(),
										TTi18n::gettext( 'Invalid name' ),
										1, 100
			);
			if ( $this->Validator->isError( 'name' ) == FALSE ) {
				$this->Validator->isTrue( 'name',
										  $this->isUniqueName( $this->getName() ),
										  TTi18n::gettext( 'Name already exists' )
				);
			}

			//
			// ABOVE: Validation code moved from set*() functions.
			//
			if ( $this->getName() == '' ) {
				$this->Validator->isTRUE( 'name',
										  FALSE,
										  TTi18n::gettext( 'Invalid name' ) );
			}
		}

		return TRUE;
	}

	/**
	 * @return bool
	 */
	function preSave() {
		if ( $this->getUser() == '' ) {
			$this->setUser( TTUUID::getZeroID() ); //Use 0 instead of NULL;
		}

		if ( $this->getDefault() == TRUE ) {
			//Remove default flag from all other entries.
			$ugdlf = TTnew( 'UserGenericDataListFactory' ); /** @var UserGenericDataListFactory $ugdlf */
			if ( $this->getUser() == TTUUID::getZeroID() OR $this->getUser() == '' ) {
				$ugdlf->getByCompanyIdAndScriptAndDefault( $this->getCompany(), $this->getScript(), TRUE );
			} else {
				$ugdlf->getByUserIdAndScriptAndDefault( $this->getUser(), $this->getScript(), TRUE );
			}
			if ( $ugdlf->getRecordCount() > 0 ) {
				foreach( $ugdlf as $ugd_obj ) {
					if ( $ugd_obj->getId() != $this->getId() ) { //Don't remove default flag from ourselves when editing an existing record.
						Debug::Text( '  Removing Default Flag From: ' . $ugd_obj->getId(), __FILE__, __LINE__, __METHOD__, 10 );
						$ugd_obj->setDefault( FALSE );
						if ( $ugd_obj->isValid() ) {
							$ugd_obj->Save();
						}
					}
				}
			}
		}

		return TRUE;
	}
/*
	//Disable this for now, as it bombards the log with messages that are mostly useless.
	function addLog( $log_action ) {
		if ( $this->getUser() == FALSE AND $this->getDefault() == TRUE ) {
			//Bypass logging on Company Default Save.
			return TRUE;
		}

		return TTLog::addEntry( $this->getId(), $log_action, TTi18n::getText('Employee/Company Generic Data'), NULL, $this->getTable() );
	}
*/

	/**
	 * @param $script_name
	 * @return mixed
	 */
	static function handleScriptName( $script_name ) {
		return str_replace('//', '/', $script_name);
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

}
?>
