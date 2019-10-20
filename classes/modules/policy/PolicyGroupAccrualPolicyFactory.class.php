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
class PolicyGroupAccrualPolicyFactory extends Factory {
	protected $table = 'policy_group_accrual_policy';
	protected $pk_sequence_name = 'policy_group_accrual_policy_id_seq'; //PK Sequence name

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
	 * @return mixed
	 */
	function getAccrualPolicy() {
		return $this->getGenericDataValue( 'accrual_policy_id' );
	}

	/**
	 * @param string $value UUID
	 * @return bool
	 */
	function setAccrualPolicy( $value) {
		$value = TTUUID::castUUID( $value );
		return $this->setGenericDataValue( 'accrual_policy_id', $value );
	}
	/**
	 * @return bool
	 */
	function Validate() {
		//
		// BELOW: Validation code moved from set*() functions.
		//
		// Policy Group
		$pglf = TTnew( 'PolicyGroupListFactory' ); /** @var PolicyGroupListFactory $pglf */
		$this->Validator->isResultSetWithRows(	'policy_group',
														$pglf->getByID($this->getPolicyGroup()),
														TTi18n::gettext('Policy Group is invalid')
													);
		// Accrual Policy
		if ( $this->getAccrualPolicy() != TTUUID::getZeroID() ) {
			$aplf = TTnew( 'AccrualPolicyListFactory' ); /** @var AccrualPolicyListFactory $aplf */
			$this->Validator->isResultSetWithRows(	'over_time_policy',
														$aplf->getByID($this->getAccrualPolicy()),
														TTi18n::gettext('Selected Accrual Policy is invalid')
													);
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
}
?>
