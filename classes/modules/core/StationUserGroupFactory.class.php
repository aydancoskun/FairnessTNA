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
class StationUserGroupFactory extends Factory {
	protected $table = 'station_user_group';
	protected $pk_sequence_name = 'station_user_group_id_seq'; //PK Sequence name

	var $group_obj = NULL;

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
	function getGroupObject() {
		if ( is_object($this->group_obj) ) {
			return $this->group_obj;
		} else {
			$uglf = TTnew( 'UserGroupListFactory' ); /** @var UserGroupListFactory $uglf */
			$uglf->getById( $this->getGroup() );
			if ( $uglf->getRecordCount() == 1 ) {
				$this->group_obj = $uglf->getCurrent();
				return $this->group_obj;
			}

			return FALSE;
		}
	}

	/**
	 * @return bool|mixed
	 */
	function getGroup() {
		return $this->getGenericDataValue( 'group_id' );
	}

	/**
	 * @param string $value UUID
	 * @return bool
	 */
	function setGroup( $value) {
		$value = TTUUID::castUUID( $value );
		return $this->setGenericDataValue( 'group_id', $value );
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
		// Group
		if ( $this->getGroup() != TTUUID::getZeroID() ) {
			$uglf = TTnew( 'UserGroupListFactory' ); /** @var UserGroupListFactory $uglf */
			$this->Validator->isResultSetWithRows(	'group',
															$uglf->getByID($this->getGroup()),
															TTi18n::gettext('Selected Group is invalid')
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

	/**
	 * @param $log_action
	 * @return bool
	 */
	function addLog( $log_action ) {
		$g_obj = $this->getGroupObject();
		if ( is_object($g_obj) ) {
			return TTLog::addEntry( $this->getStation(), $log_action, TTi18n::getText('Group').': '. $g_obj->getName(), NULL, $this->getTable() );
		}

		return FALSE;
	}
}
?>
