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
class SystemSettingFactory extends Factory {
	protected $table = 'system_setting';
	protected $pk_sequence_name = 'system_setting_id_seq'; //PK Sequence name

	/**
	 * @param $name
	 * @return bool
	 */
	function isUniqueName( $name) {
		$ph = array(
					'name' => $name,
					);

		$query = 'select id from '. $this->getTable() .' where name = ?';
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
	function Validate() {
		//
		// BELOW: Validation code moved from set*() functions.
		//
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

	//This table doesn't have any of these columns, so overload the functions.

	/**
	 * @return bool
	 */
	function getDeleted() {
		return FALSE;
	}

	/**
	 * @param $bool
	 * @return bool
	 */
	function setDeleted( $bool) {
		return FALSE;
	}

	/**
	 * @return bool
	 */
	function getCreatedDate() {
		return FALSE;
	}

	/**
	 * @param int $epoch EPOCH
	 * @return bool
	 */
	function setCreatedDate( $epoch = NULL) {
		return FALSE;
	}

	/**
	 * @return bool
	 */
	function getCreatedBy() {
		return FALSE;
	}

	/**
	 * @param string $id UUID
	 * @return bool
	 */
	function setCreatedBy( $id = NULL) {
		return FALSE;
	}

	/**
	 * @return bool
	 */
	function getUpdatedDate() {
		return FALSE;
	}

	/**
	 * @param int $epoch EPOCH
	 * @return bool
	 */
	function setUpdatedDate( $epoch = NULL) {
		return FALSE;
	}

	/**
	 * @return bool
	 */
	function getUpdatedBy() {
		return FALSE;
	}

	/**
	 * @param string $id UUID
	 * @return bool
	 */
	function setUpdatedBy( $id = NULL) {
		return FALSE;
	}


	/**
	 * @return bool
	 */
	function getDeletedDate() {
		return FALSE;
	}

	/**
	 * @param int $epoch EPOCH
	 * @return bool
	 */
	function setDeletedDate( $epoch = NULL) {
		return FALSE;
	}

	/**
	 * @return bool
	 */
	function getDeletedBy() {
		return FALSE;
	}

	/**
	 * @param string $id UUID
	 * @return bool
	 */
	function setDeletedBy( $id = NULL) {
		return FALSE;
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
		$this->removeCache( 'all' );
		$this->removeCache( $this->getName() );
		return TRUE;
	}

	/**
	 * @param $key
	 * @param $value
	 * @return bool|int|string
	 */
	static function setSystemSetting( $key, $value ) {
		$sslf = new SystemSettingListFactory();
		$sslf->getByName( $key );
		if ( $sslf->getRecordCount() == 1 ) {
			$obj = $sslf->getCurrent();
		} else {
			$obj = new SystemSettingListFactory();
		}
		$obj->setName( $key );
		$obj->setValue( $value );
		if ( $obj->isValid() ) {
			Debug::Text('Key: '. $key .' Value: '. $value .' isNew: '. (int)$obj->isNew(), __FILE__, __LINE__, __METHOD__, 10);
			return $obj->Save();
		}

		return FALSE;
	}

	/**
	 * @param $key
	 * @return bool
	 */
	static function getSystemSettingValueByKey( $key ) {
		$sslf = new SystemSettingListFactory();
		$sslf->getByName( $key );
		if ( $sslf->getRecordCount() == 1 ) {
			$obj = $sslf->getCurrent();
			return $obj->getValue();
		} elseif ( $sslf->getRecordCount() > 1 ) {
			Debug::Text('ERROR: '. $sslf->getRecordCount() .' SystemSetting record(s) exists with key: '. $key, __FILE__, __LINE__, __METHOD__, 10);
		}

		return FALSE;
	}

	/**
	 * @param $key
	 * @return bool|mixed
	 */
	static function getSystemSettingObjectByKey( $key ) {
		$sslf = new SystemSettingListFactory();
		$sslf->getByName( $key );
		if ( $sslf->getRecordCount() == 1 ) {
			return $sslf->getCurrent();
		}

		return FALSE;
	}

	/**
	 * @param $log_action
	 * @return bool
	 */
	function addLog( $log_action ) {
		return TTLog::addEntry( $this->getId(), $log_action, TTi18n::getText('System Setting - Name').': '. $this->getName() .' '. TTi18n::getText('Value').': '. $this->getValue(), NULL, $this->getTable() );
	}
}
?>
