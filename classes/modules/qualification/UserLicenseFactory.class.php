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
class UserLicenseFactory extends Factory {
	protected $table = 'user_license';
	protected $pk_sequence_name = 'user_license_id_seq'; //PK Sequence name
	protected $qualification_obj = NULL;

	protected $license_number_validator_regex = '/^[A-Z_\/:;\-\.\ 0-9]{1,250}$/i';

	/**
	 * @param $name
	 * @param null $parent
	 * @return array|null
	 */
	function _getFactoryOptions( $name, $parent = NULL ) {

		$retval = NULL;
		switch( $name ) {
			case 'source_type':
				$qf = TTnew('QualificationFactory'); /** @var QualificationFactory $qf */
				$retval = $qf->getOptions( $name );
				break;
			case 'columns':
				$retval = array(
										'-1010-first_name' => TTi18n::gettext('First Name'),
										'-1020-last_name' => TTi18n::gettext('Last Name'),
										'-2050-qualification' => TTi18n::gettext('License Type'),
										'-2040-group' => TTi18n::gettext('Group'),
										'-3080-license_number' => TTi18n::gettext('License Number'),
										'-3090-license_issued_date' => TTi18n::gettext('Issued Date'),
										'-4000-license_expiry_date' => TTi18n::gettext('Expiry Date'),

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
								'license_number',
								'license_issued_date',
								'license_expiry_date',
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

										'license_number' => 'LicenseNumber',
										'license_issued_date' => 'LicenseIssuedDate',
										'license_expiry_date' => 'LicenseExpiryDate',

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
	 * @return bool
	 */
	function getLicenseNumber() {
		return $this->getGenericDataValue( 'license_number' );
	}


	/**
	 * @param $value
	 * @return bool
	 */
	function setLicenseNumber( $value ) {
		$value = trim($value);
		return $this->setGenericDataValue( 'license_number', $value );
	}


	/**
	 * @return bool|int
	 */
	function getLicenseIssuedDate() {
		return (int)$this->getGenericDataValue( 'license_issued_date' );
	}

	/**
	 * @param int $value EPOCH
	 * @return bool
	 */
	function setLicenseIssuedDate( $value) {
		$value = ( !is_int($value) ) ? trim($value) : $value; //Dont trim integer values, as it changes them to strings.
		return $this->setGenericDataValue( 'license_issued_date', $value );
	}

	/**
	 * @return bool|int
	 */
	function getLicenseExpiryDate() {
		return (int)$this->getGenericDataValue( 'license_expiry_date' );
	}

	/**
	 * @param int $value EPOCH
	 * @return bool
	 */
	function setLicenseExpiryDate( $value) {
		$value = ( !is_int($value) ) ? trim($value) : $value; //Dont trim integer values, as it changes them to strings.
		return $this->setGenericDataValue( 'license_expiry_date', $value );
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
		} elseif ( is_object( $this->getQualificationObject() ) AND
				TTUUID::isUUID( $this->getQualificationObject()->getCompany() ) AND $this->getQualificationObject()->getCompany() != TTUUID::getZeroID() AND $this->getQualificationObject()->getCompany() != TTUUID::getNotExistID() AND
				TTUUID::isUUID( $this->getID() ) AND $this->getID() != TTUUID::getZeroID() AND $this->getID() != TTUUID::getNotExistID() ) {
			return CompanyGenericTagMapListFactory::getStringByCompanyIDAndObjectTypeIDAndObjectID( $this->getQualificationObject()->getCompany(), 253, $this->getID() );
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
															$ulf->getByID($this->getUser()),
															TTi18n::gettext('Employee must be specified')
														);
		}
		// Qualification
		if ( $this->getQualification() !== FALSE ) {
			$qlf = TTnew( 'QualificationListFactory' ); /** @var QualificationListFactory $qlf */
			$this->Validator->isResultSetWithRows( 'qualification_id',
															$qlf->getById( $this->getQualification() ),
															TTi18n::gettext('License must be specified')
														);
		}
		// License number
		if ( $this->getLicenseNumber() != '' ) {
			$this->Validator->isRegEx(		'license_number',
													$this->getLicenseNumber(),
													TTi18n::gettext('License number is invalid'),
													$this->license_number_validator_regex
												);
		}
		// License issued date
		if ( $this->getLicenseIssuedDate() != '' ) {
			$this->Validator->isDate(		'license_issued_date',
													$this->getLicenseIssuedDate(),
													TTi18n::gettext('Incorrect license issued date')
												);
		}
		// License expiry date
		if ( $this->getLicenseExpiryDate() != '' ) {
			$this->Validator->isDate(		'license_expiry_date',
													$this->getLicenseExpiryDate(),
													TTi18n::gettext('Incorrect license expiry date')
												);
		}

		//
		// ABOVE: Validation code moved from set*() functions.
		//

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
			CompanyGenericTagMapFactory::setTags( $this->getQualificationObject()->getCompany(), 253, $this->getID(), $this->getTag() );
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
						case 'license_issued_date':
							$this->setLicenseIssuedDate( TTDate::parseDateTime( $data['license_issued_date'] ) );
							break;
						case 'license_expiry_date':
							$this->setLicenseExpiryDate( TTDate::parseDateTime( $data['license_expiry_date'] ) );
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
						case 'first_name':
						case 'last_name':
						case 'title':
						case 'user_group':
						case 'default_branch':
						case 'default_department':
							$data[$variable] = $this->getColumn( $variable );
							break;
						case 'license_issued_date':
							$data['license_issued_date'] = TTDate::getAPIDate( 'DATE', $this->getLicenseIssuedDate() );
							break;
						case 'license_expiry_date':
							$data['license_expiry_date'] = TTDate::getAPIDate( 'DATE', $this->getLicenseExpiryDate() );
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
		return TTLog::addEntry( $this->getId(), $log_action, TTi18n::getText('License'), NULL, $this->getTable(), $this );
	}

}
?>
