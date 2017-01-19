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
 * @package Core
 */
class SystemSettingFactory extends Factory {
	protected $table = 'system_setting';
	protected $pk_sequence_name = 'system_setting_id_seq'; //PK Sequence name
	function isUniqueName($name) {
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
	function getName() {
		if ( isset($this->data['name']) ) {
			return $this->data['name'];
		}

		return FALSE;
	}
	function setName($value) {
		$value = trim($value);
		if (	$this->Validator->isLength(	'name',
											$value,
											TTi18n::gettext('Name is too short or too long'),
											1, 250)
				AND
						$this->Validator->isTrue(		'name',
														$this->isUniqueName($value),
														TTi18n::gettext('Name already exists')
														)

						) {

			$this->data['name'] = $value;

			return TRUE;
		}

		return FALSE;
	}

	function getValue() {
		if ( isset($this->data['value']) ) {
			return $this->data['value'];
		}

		return FALSE;
	}
	function setValue($value) {
		$value = trim($value);
		if (	$this->Validator->isLength(	'value',
											$value,
											TTi18n::gettext('Value is too short or too long'),
											1, 4096)
						) {

			$this->data['value'] = $value;

			return TRUE;
		}

		return FALSE;
	}

	//This table doesn't have any of these columns, so overload the functions.
	function getDeleted() {
		return FALSE;
	}
	function setDeleted($bool) {
		return FALSE;
	}

	function getCreatedDate() {
		return FALSE;
	}
	function setCreatedDate($epoch = NULL) {
		return FALSE;
	}
	function getCreatedBy() {
		return FALSE;
	}
	function setCreatedBy($id = NULL) {
		return FALSE;
	}

	function getUpdatedDate() {
		return FALSE;
	}
	function setUpdatedDate($epoch = NULL) {
		return FALSE;
	}
	function getUpdatedBy() {
		return FALSE;
	}
	function setUpdatedBy($id = NULL) {
		return FALSE;
	}


	function getDeletedDate() {
		return FALSE;
	}
	function setDeletedDate($epoch = NULL) {
		return FALSE;
	}
	function getDeletedBy() {
		return FALSE;
	}
	function setDeletedBy($id = NULL) {
		return FALSE;
	}

	function preSave() {
		return TRUE;
	}

	function postSave() {
		$this->removeCache( 'all' );
		$this->removeCache( $this->getName() );
		return TRUE;
	}

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

	static function getSystemSettingValueByKey( $key ) {
		$sslf = new SystemSettingListFactory();
		$sslf->getByName( $key );
		if ( $sslf->getRecordCount() == 1 ) {
			$obj = $sslf->getCurrent();
			return $obj->getValue();
		}

		return FALSE;
	}

	static function getSystemSettingObjectByKey( $key ) {
		$sslf = new SystemSettingListFactory();
		$sslf->getByName( $key );
		if ( $sslf->getRecordCount() == 1 ) {
			return $sslf->getCurrent();
		}

		return FALSE;
	}
	
	function addLog( $log_action ) {
		return TTLog::addEntry( $this->getId(), $log_action, TTi18n::getText('System Setting - Name').': '. $this->getName() .' '. TTi18n::getText('Value').': '. $this->getValue(), NULL, $this->getTable() );
	}
}
?>
