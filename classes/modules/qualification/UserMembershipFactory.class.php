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
 * @package Modules\Qualification
 */
class UserMembershipFactory extends Factory {
	protected $table = 'user_membership';
	protected $pk_sequence_name = 'user_membership_id_seq'; //PK Sequence name
	protected $qualification_obj = NULL;

	/**
	 * @param $name
	 * @param null $parent
	 * @return array|null
	 */
	function _getFactoryOptions( $name, $parent = NULL ) {

		$retval = NULL;
		switch( $name ) {
			case 'ownership':
				$retval = array(
										10 => TTi18n::gettext('Company'),
										20 => TTi18n::gettext('Individual'),
									);
				break;
			case 'source_type':
				$qf = TTnew('QualificationFactory'); /** @var QualificationFactory $qf */
				$retval = $qf->getOptions( $name );
				break;
			case 'columns':
				$retval = array(
										'-1010-first_name' => TTi18n::gettext('First Name'),
										'-1020-last_name' => TTi18n::gettext('Last Name'),
										'-2050-qualification' => TTi18n::gettext('Membership'),
										'-2040-group' => TTi18n::gettext('Group'),
										'-4030-ownership' => TTi18n::gettext('Ownership'),
										'-1060-amount' => TTi18n::gettext('Amount'),
										'-2500-currency' => TTi18n::gettext('Currency'),
										'-1080-start_date' => TTi18n::gettext('Start Date'),
										'-4040-renewal_date' => TTi18n::gettext('Renewal Date'),
										'-1300-tag' => TTi18n::gettext('Tags'),

										'-1090-title' => TTi18n::gettext('Title'),
										'-1099-user_group' => TTi18n::gettext('Employee Group'),
										'-1100-default_branch' => TTi18n::gettext('Branch'),
										'-1110-default_department' => TTi18n::gettext('Department'),

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
								'qualification',
								'ownership',
								'amount',
								'currency',
								'start_date',
								'renewal_date',
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
										'qualification_id' => 'Qualification',
										'qualification' => FALSE,
										'group' => FALSE,
										'ownership_id' => 'Ownership',
										'ownership' => FALSE,
										'amount' => 'Amount',
										'currency_id' => 'Currency',
										'currency' => FALSE,

										'start_date' => 'StartDate',

										'renewal_date' => 'RenewalDate',

										'tag' => 'Tag',
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
	function getQualificationObject() {

		return $this->getGenericObject( 'QualificationListFactory', $this->getQualification(), 'qualification_obj' );
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
	function getQualification() {
		return $this->getGenericDataValue( 'qualification_id' );
	}

	/**
	 * @param string $value UUID
	 * @return bool
	 */
	function setQualification( $value ) {
		$value = TTUUID::castUUID( $value );
		return $this->setGenericDataValue( 'qualification_id', $value );
	}


	/**
	 * @return bool|int
	 */
	function getOwnership() {
		return $this->getGenericDataValue( 'ownership_id' );
	}

	/**
	 * @param string $value UUID
	 * @return bool
	 */
	function setOwnership ( $value ) {
		$value = (int)trim( $value );
		return $this->setGenericDataValue( 'ownership_id', $value );
	}


	/**
	 * @return bool|mixed
	 */
	function getCurrency() {
		return $this->getGenericDataValue( 'currency_id' );
	}

	/**
	 * @param string $value UUID
	 * @return bool
	 */
	function setCurrency( $value ) {
		$value = TTUUID::castUUID( $value );
		Debug::Text('Currency ID: '. $value, __FILE__, __LINE__, __METHOD__, 10);
		return $this->setGenericDataValue( 'currency_id', $value );
	}

	/*
	function getAmount() {
		return $this->getGenericDataValue( 'amount' );
	}

	function setAmount($int) {
		$int = trim($int);

		if	( empty($int) ) {
			$int = 0;
		}

		if	(	$this->Validator->isNumeric(		'amount',
													$int,
													TTi18n::gettext('Incorrect Amount'))
				) {

			$this->setGenericDataValue( 'amount', $int );

			return TRUE;
		}

		return FALSE;
	}
	*/

	/**
	 * @return bool|string
	 */
	function getAmount() {
		return Misc::MoneyFormat( $this->getGenericDataValue( 'amount' ), FALSE );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setAmount( $value) {
		$value = trim($value);
		//Pull out only digits and periods.
		$value = $this->Validator->stripNonFloat($value);
		return $this->setGenericDataValue( 'amount', Misc::MoneyFormat( $value, FALSE ) );
	}


	/**
	 * @return bool
	 */
	function getStartDate() {
		return $this->getGenericDataValue( 'start_date' );
	}

	/**
	 * @param int $value EPOCH
	 * @return bool
	 */
	function setStartDate( $value) {
		$value = ( !is_int($value) ) ? trim($value) : $value; //Dont trim integer values, as it changes them to strings.
		return $this->setGenericDataValue( 'start_date', $value );
	}


	/**
	 * @return bool
	 */
	function getRenewalDate() {
		return $this->getGenericDataValue( 'renewal_date' );
	}

	/**
	 * @param int $value EPOCH
	 * @return bool
	 */
	function setRenewalDate( $value) {
		$value = ( !is_int($value) ) ? trim($value) : $value; //Dont trim integer values, as it changes them to strings.
		return $this->setGenericDataValue( 'renewal_date', $value );
	}

	/**
	 * @return bool|string
	 */
	function getTag() {
		//Check to see if any temporary data is set for the tags, if not, make a call to the database instead.
		//postSave() needs to get the tmp_data.
		$value = $this->getGenericTempDataValue( 'tags' );
		if ( $value !== FALSE ) {
			return $value;
		} elseif ( is_object( $this->getQualificationObject() ) AND $this->getQualificationObject()->getCompany() > 0 AND $this->getID() > 0 ) {
			return CompanyGenericTagMapListFactory::getStringByCompanyIDAndObjectTypeIDAndObjectID( $this->getQualificationObject()->getCompany(), 255, $this->getID() );
		}

		return FALSE;
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setTag( $value ) {
		$value = trim($value);
		//Save the tags in temporary memory to be committed in postSave()
		return $this->setGenericTempDataValue( 'tags', $value );
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
		if ( $this->getUser() !== FALSE ) {
			$ulf = TTnew( 'UserListFactory' ); /** @var UserListFactory $ulf */
			$this->Validator->isResultSetWithRows(	'user_id',
															$ulf->getByID($this->getUser() ),
															TTi18n::gettext('Employee must be specified')
														);
		}
		// Qualification
		if ( $this->getQualification() !== FALSE ) {
			$qlf = TTnew( 'QualificationListFactory' ); /** @var QualificationListFactory $qlf */
			$this->Validator->isResultSetWithRows( 'qualification_id',
															$qlf->getById( $this->getQualification() ),
															TTi18n::gettext('Membership must be specified')
														);
		}
		// Ownership
		if ( $this->getOwnership() !== FALSE ) {
			$this->Validator->inArrayKey( 'ownership_id',
												$this->getOwnership(),
												TTi18n::gettext( 'Ownership is invalid' ),
												$this->getOptions( 'ownership' )
											);
		}
		// Currency
		if ( $this->getCurrency() !== FALSE ) {
			$culf = TTnew( 'CurrencyListFactory' ); /** @var CurrencyListFactory $culf */
			$this->Validator->isResultSetWithRows(	'currency_id',
															$culf->getByID($this->getCurrency()),
															TTi18n::gettext('Currency must be specified')
														);
		}
		// Amount
		if ( $this->getAmount() == '' ) {
			$this->Validator->isTrue(	'amount',
										  FALSE,
										  TTi18n::gettext('Amount must be specified')
			);
		} else {
			$this->Validator->isFloat( 'amount',
									   $this->getAmount(),
									   TTi18n::gettext( 'Invalid Amount, Must be a numeric value' )
			);
		}

		// Start date
		if (  $this->getStartDate() !== FALSE AND $this->getStartDate() != '' ) {
			$this->Validator->isDate(  'start_date',
												$this->getStartDate(),
												TTi18n::gettext('Start date is invalid')
											);
		}
		// Renewal date
		if ( $this->getRenewalDate() !== FALSE AND $this->getRenewalDate() != '' ) {
			$this->Validator->isDate(  'renewal_date',
												$this->getRenewalDate(),
												TTi18n::gettext('Renewal date is invalid')
											);
		}


		//
		// ABOVE: Validation code moved from set*() functions.
		//
		//$this->setProvince( $this->getProvince() ); //Not sure why this was there, but it causes duplicate errors if the province is incorrect.

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
		$this->removeCache( $this->getUser().$this->getQualification() );

		if ( $this->getDeleted() == FALSE ) {
			Debug::text('Setting Tags...', __FILE__, __LINE__, __METHOD__, 10);
			CompanyGenericTagMapFactory::setTags( $this->getQualificationObject()->getCompany(), 255, $this->getID(), $this->getTag() );
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
						case 'start_date':
							$this->setStartDate( TTDate::parseDateTime( $data['start_date'] ) );
							break;
						case 'renewal_date':
							$this->setRenewalDate( TTDate::parseDateTime( $data['renewal_date'] ) );
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
	function getObjectAsArray( $include_columns = NULL, $permission_children_ids = FALSE  ) {
		$data = array();
		$variable_function_map = $this->getVariableToFunctionMap();
		if ( is_array( $variable_function_map ) ) {
			foreach( $variable_function_map as $variable => $function_stub ) {
				if ( $include_columns == NULL OR ( isset($include_columns[$variable]) AND $include_columns[$variable] == TRUE ) ) {

					$function = 'get'.$function_stub;

					switch( $variable ) {
						case 'qualification':
						case 'group':
						case 'currency':
						case 'first_name':
						case 'last_name':
						case 'title':
						case 'user_group':
						case 'default_branch':
						case 'default_department':
							$data[$variable] = $this->getColumn( $variable );
							break;
						case 'ownership':
							$function = 'get'.$variable;
							if ( method_exists( $this, $function ) ) {
								$data[$variable] = Option::getByKey( $this->$function(), $this->getOptions( $variable ) );
							}
							break;
						case 'start_date':
							$data[$variable] = TTDate::getAPIDate( 'DATE', $this->getStartDate() );
							break;
						case 'renewal_date':
							$data['renewal_date'] = TTDate::getAPIDate( 'DATE', $this->getRenewalDate() );
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
		return TTLog::addEntry( $this->getId(), $log_action, TTi18n::getText('Membership'), NULL, $this->getTable(), $this );
	}

}
?>
