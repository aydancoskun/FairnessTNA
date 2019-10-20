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

/*
CREATE TABLE department_branch (
	id serial NOT NULL,
	branch_id integer DEFAULT 0 NOT NULL,
	department_id integer DEFAULT 0 NOT NULL
) WITHOUT OIDS;
*/

/**
 * @package Modules\Department
 */
class DepartmentBranchFactory extends Factory {
	protected $table = 'department_branch';
	protected $pk_sequence_name = 'department_branch_id_seq'; //PK Sequence name

	/**
	 * @return mixed
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
	 * @return mixed
	 */
	function getBranch() {
		return $this->getGenericDataValue( 'branch_id' );
	}

	/**
	 * @param string $value UUID
	 * @return bool
	 */
	function setBranch( $value) {
		$value = TTUUID::castUUID( $value );
		return $this->setGenericDataValue( 'branch_id', $value );
	}
	/**
	 * @return bool
	 */
	function Validate() {
		//
		// BELOW: Validation code moved from set*() functions.
		//
		// Department
		if ( $this->getDepartment() == TTUUID::getZeroID() ) {
			$dlf = TTnew( 'DepartmentListFactory' ); /** @var DepartmentListFactory $dlf */
			$this->Validator->isResultSetWithRows(	'company',
															$dlf->getByID($this->getDepartment()),
															TTi18n::gettext('Company is invalid')
														);
		}
		// Branch
		if ( $this->getBranch() == TTUUID::getZeroID() ) {
			$blf = TTnew( 'BranchListFactory' ); /** @var BranchListFactory $blf */
			$this->Validator->isResultSetWithRows(	'company',
															$blf->getByID($this->getBranch()),
															TTi18n::gettext('Company is invalid')
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
