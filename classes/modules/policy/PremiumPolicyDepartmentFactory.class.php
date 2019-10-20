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
class PremiumPolicyDepartmentFactory extends Factory {
	protected $table = 'premium_policy_department';
	protected $pk_sequence_name = 'premium_policy_department_id_seq'; //PK Sequence name

	protected $department_obj = NULL;

	/**
	 * @return bool|null
	 */
	function getDepartmentObject() {
		if ( is_object($this->department_obj) ) {
			return $this->department_obj;
		} else {
			$lf = TTnew( 'DepartmentListFactory' ); /** @var DepartmentListFactory $lf */
			$lf->getById( $this->getDepartment() );
			if ( $lf->getRecordCount() == 1 ) {
				$this->department_obj = $lf->getCurrent();
				return $this->department_obj;
			}

			return FALSE;
		}
	}

	/**
	 * @return mixed
	 */
	function getPremiumPolicy() {
		return $this->getGenericDataValue( 'premium_policy_id' );
	}

	/**
	 * @param string $value UUID
	 * @return bool
	 */
	function setPremiumPolicy( $value) {
		$value = TTUUID::castUUID( $value );
		return $this->setGenericDataValue( 'premium_policy_id', $value );
	}

	/**
	 * @return bool|mixed
	 */
	function getDepartment() {
		return $this->getGenericDataValue( 'department_id' );
	}

	/**
	 * @param string $value UUID
	 * @return bool
	 */
	function setDepartment( $value) {
		$value = TTUUID::castUUID( $value );
		return $this->setGenericDataValue( 'department_id', $value );
	}
	/**
	 * @return bool
	 */
	function Validate() {
		//
		// BELOW: Validation code moved from set*() functions.
		//
		// Premium Policy
		if ( $this->getPremiumPolicy() != TTUUID::getZeroID() ) {
			$this->Validator->isUUID(	'premium_policy',
												$this->getPremiumPolicy(),
												TTi18n::gettext('Selected Premium Policy is invalid')
											);
		}
		// Department
		$dlf = TTnew( 'DepartmentListFactory' ); /** @var DepartmentListFactory $dlf */
		$this->Validator->isResultSetWithRows(	'department',
													$dlf->getByID($this->getDepartment()),
													TTi18n::gettext('Selected Department is invalid')
												);


		//
		// ABOVE: Validation code moved from set*() functions.
		//
		return TRUE;
	}

	/**
	 * @return bool
	 */
	function postSave() {
		$this->removeCache( 'premium_policy-'. $this->getPremiumPolicy() );
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
		$obj = $this->getDepartmentObject();
		if ( is_object($obj) ) {
			return TTLog::addEntry( $this->getPremiumPolicy(), $log_action, TTi18n::getText('Department').': '. $obj->getName(), NULL, $this->getTable() );
		}

		return FALSE;
	}
}
?>
