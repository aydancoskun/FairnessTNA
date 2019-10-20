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
class StationBranchFactory extends Factory {
	protected $table = 'station_branch';
	protected $pk_sequence_name = 'station_branch_id_seq'; //PK Sequence name

	var $branch_obj = NULL;

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
	function getBranchObject() {
		if ( is_object($this->branch_obj) ) {
			return $this->branch_obj;
		} else {
			$blf = TTnew( 'BranchListFactory' ); /** @var BranchListFactory $blf */
			$blf->getById( $this->getBranch() );
			if ( $blf->getRecordCount() == 1 ) {
				$this->branch_obj = $blf->getCurrent();
				return $this->branch_obj;
			}

			return FALSE;
		}
	}

	/**
	 * @return bool|mixed
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
	 * This table doesn't have any of these columns, so overload the functions.
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
		// Branch
		$blf = TTnew( 'BranchListFactory' ); /** @var BranchListFactory $blf */
		$this->Validator->isResultSetWithRows(	'branch',
														$blf->getByID($this->getBranch()),
														TTi18n::gettext('Selected Branch is invalid')
													);

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
		$b_obj = $this->getBranchObject();
		if ( is_object($b_obj) ) {
			return TTLog::addEntry( $this->getStation(), $log_action, TTi18n::getText('Branch').': '. $b_obj->getName(), NULL, $this->getTable() );
		}

		return FALSE;
	}
}
?>
