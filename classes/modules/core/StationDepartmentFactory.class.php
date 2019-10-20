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
class StationDepartmentFactory extends Factory {
	protected $table = 'station_department';
	protected $pk_sequence_name = 'station_department_id_seq'; //PK Sequence name

	var $department_obj = NULL;

	/**
	 * @return mixed
	 */
	function getStation() {
		return $this->getGenericDataValue( 'station_id' );
	}

	/**
	 * @param string $value UUID
	 * @return bool
	 */
	function setStation( $value) {
		$value = TTUUID::castUUID( $value );
		return $this->setGenericDataValue( 'station_id', $value );
	}

	/**
	 * @return bool|null
	 */
	function getDepartmentObject() {
		if ( is_object($this->department_obj) ) {
			return $this->department_obj;
		} else {
			$dlf = TTnew( 'DepartmentListFactory' ); /** @var DepartmentListFactory $dlf */
			$dlf->getById( $this->getDepartment() );
			if ( $dlf->getRecordCount() == 1 ) {
				$this->department_obj = $dlf->getCurrent();
				return $this->department_obj;
			}

			return FALSE;
		}
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
		// Station
		if ( $this->getStation() != TTUUID::getZeroID() ) {
			$this->Validator->isUUID(	'station',
												$this->getStation(),
												TTi18n::gettext('Selected Station is invalid')
											/*
															$this->Validator->isResultSetWithRows(	'station',
																								$slf->getByID($id),
																								TTi18n::gettext('Selected Station is invalid')
											*/
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
	 * This table doesn't have any of these columns, so overload the functions.
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
		$d_obj = $this->getDepartmentObject();
		if ( is_object($d_obj) ) {
			return TTLog::addEntry( $this->getStation(), $log_action, TTi18n::getText('Department').': '. $d_obj->getName(), NULL, $this->getTable() );
		}

		return FALSE;
	}
}
?>
