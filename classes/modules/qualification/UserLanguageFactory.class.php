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
class UserLanguageFactory extends Factory {
	protected $table = 'user_language';
	protected $pk_sequence_name = 'user_language_id_seq'; //PK Sequence name
	protected $qualification_obj = NULL;

	/**
	 * @param $name
	 * @param null $parent
	 * @return array|null
	 */
	function _getFactoryOptions( $name, $parent = NULL ) {

		$retval = NULL;
		switch( $name ) {
			case 'fluency':
				$retval = array(
										10 => TTi18n::gettext('Speaking'),
										20 => TTi18n::gettext('Writing'),
										30 => TTi18n::gettext('Reading'),
									);
				break;
			case 'competency':
				$retval = array(
										10 => TTi18n::gettext('Native Language'),
										20 => TTi18n::gettext('Good'),
										30 => TTi18n::gettext('Basic'),
										40 => TTi18n::gettext('Poor'),
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
										'-2050-qualification' => TTi18n::gettext('Language'),
										'-2040-group' => TTi18n::gettext('Group'),
										'-4010-fluency' => TTi18n::gettext('Fluency'),
										'-4020-competency' => TTi18n::gettext('Competency'),
										'-1040-description' => TTi18n::getText('Description'),
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
								'fluency',
								'competency',
								'description',
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
										'fluency_id' => 'Fluency',
										'fluency' => FALSE,

										'competency_id' => 'Competency',
										'competency' => FALSE,

										'description' => 'Description',
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
	 * @param $value
	 * @return bool
	 */
	function setUser( $value) {
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
	 * @param $value
	 * @return bool
	 */
	function setQualification( $value ) {
		$value = TTUUID::castUUID( $value );
		return $this->setGenericDataValue( 'qualification_id', $value );
	}


	/**
	 * @return bool|int
	 */
	function getFluency() {
		return $this->getGenericDataValue( 'fluency_id' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setFluency( $value ) {
		$value = (int)trim( $value );
		return $this->setGenericDataValue( 'fluency_id', $value );
	}


	/**
	 * @return bool|int
	 */
	function getCompetency() {
		return $this->getGenericDataValue( 'competency_id' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setCompetency( $value ) {
		$value = (int)trim( $value );
		return $this->setGenericDataValue( 'competency_id', $value );
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
	 * @return bool|string
	 */
	function getTag() {
		//Check to see if any temporary data is set for the tags, if not, make a call to the database instead.
		//postSave() needs to get the tmp_data.
		$value = $this->getGenericTempDataValue( 'tags' );
		if ( $value !== FALSE ) {
			return $value;
		} elseif ( is_object( $this->getQualificationObject() )
					AND TTUUID::isUUID( $this->getQualificationObject()->getCompany() ) AND $this->getQualificationObject()->getCompany() != TTUUID::getZeroID() AND $this->getQualificationObject()->getCompany() != TTUUID::getNotExistID()
					AND TTUUID::isUUID( $this->getID() ) AND $this->getID() != TTUUID::getZeroID() AND $this->getID() != TTUUID::getNotExistID() ) {
			return CompanyGenericTagMapListFactory::getStringByCompanyIDAndObjectTypeIDAndObjectID( $this->getQualificationObject()->getCompany(), 254, $this->getID() );
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
															TTi18n::gettext('Language must be specified')
														);
		}
		// Fluency
		if ( $this->getFluency() !== FALSE ) {
			$this->Validator->inArrayKey( 'fluency_id',
												$this->getFluency(),
												TTi18n::gettext( 'Fluency is invalid' ),
												$this->getOptions( 'fluency' )
											);
		}
		// Competency
		if ( $this->getCompetency() !== FALSE ) {
			$this->Validator->inArrayKey( 'competency_id',
												$this->getCompetency(),
												TTi18n::gettext( 'Competency is invalid' ),
												$this->getOptions( 'competency' )
											);
		}
		// Description
		if ( $this->getDescription() != '' ) {
			$this->Validator->isLength( 'description',
												$this->getDescription(),
												TTi18n::gettext('Description is invalid'),
												2, 255
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
			CompanyGenericTagMapFactory::setTags( $this->getQualificationObject()->getCompany(), 254, $this->getID(), $this->getTag() );
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
						case 'fluency':
						case 'competency':
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
		return TTLog::addEntry( $this->getId(), $log_action, TTi18n::getText('Language'), NULL, $this->getTable(), $this );
	}

}
?>
