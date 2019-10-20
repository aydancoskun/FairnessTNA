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
class BankAccountFactory extends Factory {
	protected $table = 'bank_account';
	protected $pk_sequence_name = 'bank_account_id_seq'; //PK Sequence name

	protected $user_obj = NULL;

	/**
	 * @param $name
	 * @param null $parent
	 * @return array|null
	 */
	function _getFactoryOptions( $name, $parent = NULL ) {

		$retval = NULL;
		switch( $name ) {
			case 'ach_transaction_type': //ACH transactions require a transaction code that matches the bank account.
				$retval = array(
								22 => TTi18n::getText('Checking'),
								32 => TTi18n::getText('Savings'),
								);
				break;
			case 'columns':
				$retval = array(

										'-1010-first_name' => TTi18n::gettext('First Name'),
										'-1020-last_name' => TTi18n::gettext('Last Name'),

										'-1090-title' => TTi18n::gettext('Title'),
										'-1099-user_group' => TTi18n::gettext('Group'),
										'-1100-default_branch' => TTi18n::gettext('Branch'),
										'-1110-default_department' => TTi18n::gettext('Department'),

										'-5010-transit' => TTi18n::gettext('Transit/Routing'),
										'-5020-account' => TTi18n::gettext('Account'),
										'-5030-institution' => TTi18n::gettext('Institution'),

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
								'account',
								'institution',
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
										'user_id' => 'User',
										'first_name' => FALSE,
										'last_name' => FALSE,

										'institution' => 'Institution',
										'transit' => 'Transit',
										'account' => 'Account',

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
	 * @return mixed
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
	 * @return bool
	 */
	function isUnique() {
		if ( $this->getCompany() == FALSE ) {
			return FALSE;
		}

		if ( TTUUID::isUUID( $this->getUser() ) AND $this->getUser() != TTUUID::getZeroID() AND $this->getUser() != TTUUID::getNotExistID() ) {
			$ph = array(
						'company_id' => TTUUID::castUUID($this->getCompany()),
						'user_id' => TTUUID::castUUID($this->getUser()),
						);

			$query = 'select id from '. $this->getTable() .' where company_id = ? AND user_id = ? AND deleted = 0';
		} else {
			$ph = array(
						'company_id' => TTUUID::castUUID($this->getCompany()),
						);

			$query = 'select id from '. $this->getTable() .' where company_id = ? AND user_id is NULL AND deleted = 0';
		}
		$id = $this->db->GetOne($query, $ph);
		Debug::Arr($ph, 'Unique ID: '. $id .' Query: '. $query, __FILE__, __LINE__, __METHOD__, 10);

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
	function getInstitution() {
		return $this->getGenericDataValue( 'institution' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setInstitution( $value) {
		$value = trim($value);
		return $this->setGenericDataValue( 'institution', $value );
	}

	/**
	 * @return bool|mixed
	 */
	function getTransit() {
		return $this->getGenericDataValue( 'transit' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setTransit( $value) {
		$value = trim($value);
		return $this->setGenericDataValue( 'transit', $value );
	}

	/**
	 * @param null $value
	 * @return mixed
	 */
	function getSecureAccount( $value = NULL ) {
		if ( $value == '' ) {
			$value = $this->getAccount();
		}

		//Replace the middle digits leaving only 2 digits on each end, or just 1 digit on each end if the account is too short.
		$replace_length = ( (strlen($value) - 4) >= 4 ) ? ( strlen($value) - 4 ) : 3;
		$start_digit = ( strlen($value) >= 7 ) ? 2 : 1;

		$account = str_replace( substr($value, $start_digit, $replace_length), str_repeat('X', $replace_length), $value );
		return $account;
	}

	/**
	 * @return bool|mixed
	 */
	function getAccount() {
		return $this->getGenericDataValue( 'account' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setAccount( $value) {
		//If *'s are in the account number, skip setting it
		//This allows them to change other data without seeing the account number.
		if ( stripos( $value, 'X') !== FALSE  ) {
			return FALSE;
		}
		$value = $this->Validator->stripNonNumeric( trim($value) );
		return $this->setGenericDataValue( 'account', $value );
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
		// Employee
		if ( $this->getUser() != TTUUID::getZeroID() ) {
			$ulf = TTnew( 'UserListFactory' ); /** @var UserListFactory $ulf */
			$this->Validator->isResultSetWithRows(	'user',
														$ulf->getByID($this->getUser()),
														TTi18n::gettext('Invalid Employee')
													);
		}
		// Institution
		if ( $this->getInstitution() != '' ) {
			$this->Validator->isNumeric(	'institution',
													$this->getInstitution(),
													TTi18n::gettext('Invalid institution number, must be digits only')
												);
			if ( $this->Validator->isError('institution') == FALSE ) {
				$this->Validator->isLength(		'institution',
														$this->getInstitution(),
														TTi18n::gettext('Invalid institution number length'),
														2,
														3
													);
			}
		}
		// Transit
		$this->Validator->isNumeric(	'transit',
												$this->getTransit(),
												TTi18n::gettext('Invalid transit number, must be digits only')
											);
		if ( $this->Validator->isError('transit') == FALSE ) {
			$this->Validator->isLength(		'transit',
													$this->getTransit(),
													TTi18n::gettext('Invalid transit number length'),
													2,
													15
												);
		}
		// Account
		$this->Validator->isLength(		'account',
												$this->getAccount(),
												TTi18n::gettext('Invalid account number length'),
												3,
												20
											);


		//
		// ABOVE: Validation code moved from set*() functions.
		//
		if ( $this->getAccount() == FALSE ) {
			$this->Validator->isTRUE(		'account',
											FALSE,
											TTi18n::gettext('Bank account not specified') );
		}

		//Make sure this entry is unique.
		if ( $this->getDeleted() == FALSE AND $this->isUnique() == FALSE ) {
			$this->Validator->isTRUE(		'user_id',
											FALSE,
											TTi18n::gettext('Bank account already exists for this employee') );

			return FALSE;
		}

		return TRUE;
	}

	/**
	 * @return bool
	 */
	function preSave() {
		if ( $this->getUser() == FALSE ) {
			Debug::Text('Clearing User value, because this is strictly a company record', __FILE__, __LINE__, __METHOD__, 10);
			//$this->setUser( TTUUID::getZeroID() ); //COMPANY record.
		}

		//PGSQL has a NOT NULL constraint on Instituion number prior to schema v1014A.
		if ( $this->getInstitution() == FALSE ) {
			$this->setInstitution( '000' );
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
						case 'account':
							$data[$variable] = $this->getSecureAccount();
							break;
						case 'first_name':
						case 'last_name':
						case 'title':
						case 'user_group':
						case 'currency':
						case 'default_branch':
						case 'default_department':
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
		if ( $this->getUser() == '' ) {
			$log_description = TTi18n::getText('Company');
		} else {
			$log_description = TTi18n::getText('Employee');

			$u_obj = $this->getUserObject();
			if ( is_object($u_obj) ) {
				$log_description .= ': '. $u_obj->getFullName(FALSE, TRUE);
			}
		}
		return TTLog::addEntry( $this->getId(), $log_action, TTi18n::getText('Bank Account') .' - '. $log_description, NULL, $this->getTable(), $this );
	}

}
?>
