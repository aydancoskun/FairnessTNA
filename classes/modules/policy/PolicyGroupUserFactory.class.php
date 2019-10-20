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
class PolicyGroupUserFactory extends Factory {
	protected $table = 'policy_group_user';
	protected $pk_sequence_name = 'policy_group_user_id_seq'; //PK Sequence name

	var $user_obj = NULL;

	/**
	 * @return bool|mixed
	 */
	function getPolicyGroup() {
		return $this->getGenericDataValue( 'policy_group_id' );
	}

	/**
	 * @param string $value UUID
	 * @return bool
	 */
	function setPolicyGroup( $value) {
		$value = TTUUID::castUUID( $value );
		return $this->setGenericDataValue( 'policy_group_id', $value );
	}

	/**
	 * @return bool|null
	 */
	function getUserObject() {
		if ( is_object($this->user_obj) ) {
			return $this->user_obj;
		} else {
			$ulf = TTnew( 'UserListFactory' ); /** @var UserListFactory $ulf */
			$ulf->getById( $this->getUser() );
			if ( $ulf->getRecordCount() == 1 ) {
				$this->user_obj = $ulf->getCurrent();
				return $this->user_obj;
			}

			return FALSE;
		}
	}

	/**
	 * @param string $id UUID
	 * @return bool
	 */
	function isUniqueUser( $id) {
		$pglf = TTnew( 'PolicyGroupListFactory' ); /** @var PolicyGroupListFactory $pglf */

		$ph = array(
					'id' => TTUUID::castUUID($id),
					);


		$query = 'select a.id from '. $this->getTable() .' as a, '. $pglf->getTable() .' as b where a.policy_group_id = b.id AND a.user_id = ? AND b.deleted=0';
		$user_id = $this->db->GetOne($query, $ph);
		//Debug::Arr($user_id, 'Unique User ID: '. $user_id, __FILE__, __LINE__, __METHOD__, 10);

		if ( $user_id === FALSE ) {
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * @return mixed
	 */
	function getUser() {
		return $this->getGenericDataValue( 'user_id' );
	}

	/**
	 * @param string $value UUID
	 * @return bool
	 */
	function setUser( $value) {
		$value = TTUUID::castUUID( $value );
		return $this->setGenericDataValue( 'user_id', $value );
	}
	/**
	 * @return bool
	 */
	function Validate() {
		//
		// BELOW: Validation code moved from set*() functions.
		//
		// Policy Group
		if (  $this->getPolicyGroup() == TTUUID::getZeroID() ) {
			$pglf = TTnew( 'PolicyGroupListFactory' ); /** @var PolicyGroupListFactory $pglf */
			$this->Validator->isResultSetWithRows(	'policy_group',
															$pglf->getByID($this->getPolicyGroup()),
															TTi18n::gettext('Policy Group is invalid')
														);
		}
		// Employee
		if ( $this->getUser() != TTUUID::getZeroID() ) {
			$ulf = TTnew( 'UserListFactory' ); /** @var UserListFactory $ulf */
			$this->Validator->isResultSetWithRows(	'user',
															$ulf->getByID($this->getUser()),
															TTi18n::gettext('Selected Employee is invalid')
														);
			if ( $this->Validator->isError('user') == FALSE ) {
				$this->Validator->isTrue(		'user',
														$this->isUniqueUser($this->getUser()),
														TTi18n::gettext('Selected Employee is already assigned to another Policy Group')
													);
			}
		}


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
	 * @param $log_action
	 * @return bool
	 */
	function addLog( $log_action ) {
		$u_obj = $this->getUserObject();
		if ( is_object($u_obj) ) {
			return TTLog::addEntry( $this->getPolicyGroup(), $log_action, TTi18n::getText('Employee').': '. $u_obj->getFullName( FALSE, TRUE ), NULL, $this->getTable() );
		}

		return FALSE;
	}
}
?>
