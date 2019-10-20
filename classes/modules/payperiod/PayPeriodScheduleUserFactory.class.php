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
 * @package Modules\PayPeriod
 */
class PayPeriodScheduleUserFactory extends Factory {
	protected $table = 'pay_period_schedule_user';
	protected $pk_sequence_name = 'pay_period_schedule_user_id_seq'; //PK Sequence name

	protected $user_obj = NULL;
	protected $pay_period_schedule_obj = NULL;

	/**
	 * @return bool
	 */
	function getUserObject() {
		return $this->getGenericObject( 'UserListFactory', $this->getUser(), 'user_obj' );
	}

	/**
	 * @return bool
	 */
	function getPayPeriodScheduleObject() {
		return $this->getGenericObject( 'PayPeriodScheduleListFactory', $this->getPayPeriodSchedule(), 'pay_period_schedule_obj' );
	}

	/**
	 * @return mixed
	 */
	function getPayPeriodSchedule() {
		return $this->getGenericDataValue( 'pay_period_schedule_id' );
	}

	/**
	 * @param string $value UUID
	 * @return bool
	 */
	function setPayPeriodSchedule( $value) {
		$value = TTUUID::castUUID( $value );
		return $this->setGenericDataValue( 'pay_period_schedule_id', $value );
	}

	/**
	 * @param string $id UUID
	 * @return bool
	 */
	function isUniqueUser( $id) {
		$ppslf = TTnew( 'PayPeriodScheduleListFactory' ); /** @var PayPeriodScheduleListFactory $ppslf */

		$ph = array(
					'id' => TTUUID::castUUID($id),
					);

		$query = 'select a.id from '. $this->getTable() .' as a, '. $ppslf->getTable() .' as b where a.pay_period_schedule_id = b.id AND a.user_id = ? AND b.deleted=0';
		$user_id = $this->db->GetOne($query, $ph);
		Debug::Arr($user_id, 'Unique User ID: '. $user_id, __FILE__, __LINE__, __METHOD__, 10);

		if ( $user_id === FALSE ) {
			return TRUE;
		}

		return FALSE;
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
		// Pay Period Schedule
		if ( $this->getPayPeriodSchedule() == TTUUID::getZeroID() ) {
			$ppslf = TTnew( 'PayPeriodScheduleListFactory' ); /** @var PayPeriodScheduleListFactory $ppslf */
			$this->Validator->isResultSetWithRows(	'pay_period_schedule',
															$ppslf->getByID($this->getPayPeriodSchedule()),
															TTi18n::gettext('Pay Period Schedule is invalid')
														);
		}
		// Selected Employee
		if ( $this->getUser() != TTUUID::getZeroID() ) {
			$ulf = TTnew( 'UserListFactory' ); /** @var UserListFactory $ulf */
			$this->Validator->isResultSetWithRows(	'user',
														$ulf->getByID($this->getUser()),
														TTi18n::gettext('Selected Employee is invalid')
													);
			if ( $this->Validator->isError('user') == FALSE ) {
				$this->Validator->isTrue(		'user',
														$this->isUniqueUser($this->getUser()),
														TTi18n::gettext('Selected Employee is already assigned to another Pay Period Schedule')
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
			return TTLog::addEntry( $this->getPayPeriodSchedule(), $log_action, TTi18n::getText('Employee').': '. $u_obj->getFullName( FALSE, TRUE ), NULL, $this->getTable() );
		}

		return FALSE;
	}
}
?>
