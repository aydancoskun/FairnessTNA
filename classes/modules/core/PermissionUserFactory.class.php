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
class PermissionUserFactory extends Factory {
	protected $table = 'permission_user';
	protected $pk_sequence_name = 'permission_user_id_seq'; //PK Sequence name

	var $user_obj = NULL;
	var $permission_control_obj = NULL;

	/**
	 * @return bool
	 */
	function getPermissionControlObject() {
		return $this->getGenericObject( 'PermissionControlListFactory', $this->getPermissionControl(), 'permission_control_obj' );
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
	function getPermissionControl() {
		return TTUUID::castUUID($this->getGenericDataValue( 'permission_control_id' ));
	}

	/**
	 * @param string $value UUID
	 * @return bool
	 */
	function setPermissionControl( $value) {
		$value = TTUUID::castUUID( $value );
		return $this->setGenericDataValue( 'permission_control_id', $value );
	}

	/**
	 * @param string $id UUID
	 * @return bool
	 */
	function isUniqueUser( $id) {
		$pclf = TTnew( 'PermissionControlListFactory' ); /** @var PermissionControlListFactory $pclf */

		$ph = array(
					'id' => TTUUID::castUUID($id),
					);

		$query = 'select a.id from '. $this->getTable() .' as a, '. $pclf->getTable() .' as b where a.permission_control_id = b.id AND a.user_id = ? AND b.deleted = 0';
		$user_id = $this->db->GetOne($query, $ph);
		Debug::Arr($user_id, 'Unique User ID: '. $user_id, __FILE__, __LINE__, __METHOD__, 10);

		if ( $user_id === FALSE ) {
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * @return mixed
	 */
	function getUser() {
		return TTUUID::castUUID($this->getGenericDataValue( 'user_id' ));
	}

	/**
	 * @param string $value UUID
	 * @return bool
	 */
	function setUser( $value) {
		$value = TTUUID::castUUID( $value );
		if ( $value != TTUUID::getZeroID() ) {
			return $this->setGenericDataValue( 'user_id', $value );
		}
		return FALSE;
	}

	/**
	 * This table doesn't have any of these columns, so overload the functions.
	 * @return bool
	 */
	function Validate() {
		//
		// BELOW: Validation code moved from set*() functions.
		//

		// Permission Group
		if ( $this->getPermissionControl() == TTUUID::getZeroID() ) {
			$pclf = TTnew( 'PermissionControlListFactory' ); /** @var PermissionControlListFactory $pclf */
			$this->Validator->isResultSetWithRows( 'permission_control',
															$pclf->getByID($this->getPermissionControl()),
															TTi18n::gettext('Permission Group is invalid')
														);
		}
		// Employee
		if ( $this->getUser() !== FALSE AND $this->getUser() != TTUUID::getZeroID() ) {
			$ulf = TTnew( 'UserListFactory' ); /** @var UserListFactory $ulf */
			$this->Validator->isResultSetWithRows(	'user',
															$ulf->getByID($this->getUser()),
															TTi18n::gettext('Selected Employee is invalid')
														);
			if ( $this->Validator->isError('user') == FALSE ) {
				$this->Validator->isTrue(		'user',
														$this->isUniqueUser($this->getUser()),
														TTi18n::gettext('Selected Employee is already assigned to another Permission Group')
													);
			}
		}

		//
		// ABOVE: Validation code moved from set*() functions.
		//
		return TRUE;
	}
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
	 * @param $log_action
	 * @return bool
	 */
	function addLog( $log_action ) {
		$u_obj = $this->getUserObject();
		if ( is_object($u_obj) ) {
			return TTLog::addEntry( $this->getPermissionControl(), $log_action, TTi18n::getText('Employee').': '. $u_obj->getFullName( FALSE, TRUE ), NULL, $this->getTable() );
		}

		return FALSE;
	}
}
?>
