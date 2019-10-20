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
 * @package Core
 */
class CompanySettingFactory extends Factory {
	protected $table = 'company_setting';
	protected $pk_sequence_name = 'company_setting_id_seq'; //PK Sequence name

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
								10 => TTi18n::gettext('Public'),
								20 => TTi18n::gettext('Private'),
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
										'value' => 'Value',
										'deleted' => 'Deleted',
										);
		return $variable_function_map;
	}

	/**
	 * @param $name
	 * @return bool
	 */
	function isUniqueName( $name) {
		Debug::Arr($this->getCompany(), 'Company: ', __FILE__, __LINE__, __METHOD__, 10);
		if ( $this->getCompany() == FALSE ) {
			return FALSE;
		}

		$name = trim($name);
		if ( $name == '' ) {
			return FALSE;
		}

		$ph = array(
					'company_id' => TTUUID::castUUID($this->getCompany()),
					'name' => TTi18n::strtolower($name),
					);

		$query = 'select id from '. $this->getTable() .'
					where company_id = ?
						AND lower(name) = ?
						AND deleted = 0';
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
	function getCompany() {
		return $this->getGenericDataValue( 'company_id' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setCompany( $value) {
		$value = TTUUID::castUUID( $value );
		return $this->setGenericDataValue( 'company_id', $value );
	}

	/**
	 * @return int
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
	function getValue() {
		return $this->getGenericDataValue( 'value' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setValue( $value) {
		$value = trim($value);
		return $this->setGenericDataValue( 'value', $value );
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
		$this->removeCache( $this->getCompany().$this->getName() );
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
		$variable_function_map = $this->getVariableToFunctionMap();
		$data = array();
		if ( is_array( $variable_function_map ) ) {
			foreach( $variable_function_map as $variable => $function_stub ) {
				if ( $include_columns == NULL OR ( isset($include_columns[$variable]) AND $include_columns[$variable] == TRUE ) ) {

					$function = 'get'.$function_stub;
					switch( $variable ) {
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

	/**
	 * @return bool
	 */
	function Validate() {
		//
		// BELOW: Validation code moved from set*() functions.
		//
		// Company
		if ( $this->getCompany() != TTUUID::getZeroID() ) {
			$clf = TTnew( 'CompanyListFactory' ); /** @var CompanyListFactory $clf */
			$this->Validator->isResultSetWithRows(	'company',
															$clf->getByID($this->getCompany()),
															TTi18n::gettext('Company is invalid')
														);
		}
		// Type
		$this->Validator->inArrayKey(	'type',
												$this->getType(),
												TTi18n::gettext('Incorrect Type'),
												$this->getOptions('type')
											);
		// Name
		$this->Validator->isLength(	'name',
											$this->getName(),
											TTi18n::gettext('Name is too short or too long'),
											1, 250
										);
		if ( $this->Validator->isError('name') == FALSE ) {
			$this->Validator->isTrue(		'name',
													$this->isUniqueName($this->getName()),
													TTi18n::gettext('Name already exists')
												);
		}
		// Value
		$this->Validator->isLength(	'value',
											$this->getValue(),
											TTi18n::gettext('Value is too short or too long'),
											1, 4096
										);
		//
		// ABOVE: Validation code moved from set*() functions.
		//
		return TRUE;
	}

	/**
	 * @param $log_action
	 * @return bool
	 */
	function addLog( $log_action ) {
		return TTLog::addEntry( $this->getId(), $log_action, TTi18n::getText('Company Setting - Name').': '. $this->getName() .' '. TTi18n::getText('Value').': '. $this->getValue(), NULL, $this->getTable() );
	}

	/**
	 * @param string $company_id UUID
	 * @param $name
	 * @return bool
	 */
	static function getCompanySettingObjectByName( $company_id, $name ) {
		$cslf = new CompanySettingListFactory();
		$cslf->getByCompanyIdAndName( $company_id, $name );
		if ( $cslf->getRecordCount() == 1 ) {
			$cs_obj = $cslf->getCurrent();
			return $cs_obj;
		}

		return FALSE;
	}

	/**
	 * @param string $company_id UUID
	 * @param $name
	 * @return bool
	 */
	static function getCompanySettingArrayByName( $company_id, $name ) {
		$cs_obj = self::getCompanySettingObjectByName( $company_id, $name );
		if ( is_object( $cs_obj ) ) {
			return $cs_obj->getObjectAsArray();
		}

		return FALSE;
	}

	/**
	 * @param string $company_id UUID
	 * @param $name
	 * @return null
	 */
	static function getCompanySettingValueByName( $company_id, $name ) {
		$cs_obj = self::getCompanySettingObjectByName( $company_id, $name );
		if ( is_object( $cs_obj ) ) {
			return $cs_obj->getValue();
		}

		return NULL;
	}

	/**
	 * @param string $company_id UUID
	 * @param $name
	 * @param $value
	 * @param int $type_id
	 * @return bool
	 */
	static function setCompanySetting( $company_id, $name, $value, $type_id = 10 ) {
		$row = array(
			'company_id' => $company_id,
			'name' => $name,
			'value' => $value,
			'type_id' => $type_id
		);
		$cslf = new CompanySettingListFactory();
		$cslf->getByCompanyIdAndName( $company_id, $name );
		if ( $cslf->getRecordCount() == 1 ) {
			$csf = $cslf->getCurrent();
			$row = array_merge( $csf->getObjectAsArray(), $row );
		} else {
			$csf = new CompanySettingFactory();
		}

		Debug::Arr($row, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);
		$csf->setObjectFromArray( $row );
		if ( $csf->isValid() ) {
			$csf->Save();
		}

		return FALSE;

	}

	/**
	 * @param string $company_id UUID
	 * @param $name
	 * @return bool
	 */
	static function deleteCompanySetting( $company_id, $name ) {
		$cslf = new CompanySettingListFactory();
		$cslf->getByCompanyIdAndName( $company_id, $name );
		if ( $cslf->getRecordCount() == 1 ) {
			$csf = $cslf->getCurrent();
			$csf->setDeleted(TRUE);
			if ( $csf->isValid() ) {
				$csf->Save();
			}
		}

		return FALSE;
	}
}
?>
