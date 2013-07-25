<?php
/*********************************************************************************
 * This file is part of "Fairness", a Payroll and Time Management program.
 * Fairness is Copyright 2013 Aydan Coscun (aydan.ayfer.coskun@gmail.com)
 * Portions of this software are Copyright (C) 2003 - 2013 TimeTrex Software Inc.
 * because Fairness is a fork of "TimeTrex Workforce Management" Software.
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
/*
 * $Revision: 5089 $
 * $Id: StationUserGroupFactory.class.php 5089 2011-08-06 19:51:55Z ipso $
 * $Date: 2011-08-06 12:51:55 -0700 (Sat, 06 Aug 2011) $
 */

/**
 * @package Core
 */
class StationUserGroupFactory extends Factory {
	protected $table = 'station_user_group';
	protected $pk_sequence_name = 'station_user_group_id_seq'; //PK Sequence name

	var $group_obj = NULL;

	function getStation() {
		if ( isset($this->data['station_id']) ) {
			return $this->data['station_id'];
		}
	}
	function setStation($id) {
		$id = trim($id);

		$slf = TTnew( 'StationListFactory' );

		if (	$id == 0
				OR
				$this->Validator->isNumeric(	'station',
													$id,
													TTi18n::gettext('Selected Station is invalid')
/*
				$this->Validator->isResultSetWithRows(	'station',
													$slf->getByID($id),
													TTi18n::gettext('Selected Station is invalid')
*/
															)
			) {

			$this->data['station_id'] = $id;

			return TRUE;
		}

		return FALSE;
	}

	function getGroupObject() {
		if ( is_object($this->group_obj) ) {
			return $this->group_obj;
		} else {
			$uglf = TTnew( 'UserGroupListFactory' );
			$uglf->getById( $this->getGroup() );
			if ( $uglf->getRecordCount() == 1 ) {
				$this->group_obj = $uglf->getCurrent();
				return $this->group_obj;
			}

			return FALSE;
		}
	}
	function getGroup() {
		if ( isset($this->data['group_id']) ) {
			return $this->data['group_id'];
		}

		return FALSE;
	}
	function setGroup($id) {
		$id = trim($id);

		$uglf = TTnew( 'UserGroupListFactory' );

		if ( $id == 0
				OR $this->Validator->isResultSetWithRows(	'group',
													$uglf->getByID($id),
													TTi18n::gettext('Selected Group is invalid')
													) ) {
			$this->data['group_id'] = $id;

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

	function addLog( $log_action ) {
		$g_obj = $this->getGroupObject();
		if ( is_object($g_obj) ) {
			return TTLog::addEntry( $this->getStation(), $log_action, TTi18n::getText('Group').': '. $g_obj->getName() , NULL, $this->getTable() );
		}

		return FALSE;
	}
}
?>
