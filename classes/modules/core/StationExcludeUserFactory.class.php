<?php
/*********************************************************************************
 * This file is part of "Fairness", a Payroll and Time Management program.
 * Fairness is Copyright 2013 Aydan Coskun (aydan.ayfer.coskun@gmail.com)
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
 * $Id: StationExcludeUserFactory.class.php 5089 2011-08-06 19:51:55Z ipso $
 * $Date: 2011-08-06 12:51:55 -0700 (Sat, 06 Aug 2011) $
 */

/**
 * @package Core
 */
class StationExcludeUserFactory extends Factory {
	protected $table = 'station_exclude_user';
	protected $pk_sequence_name = 'station_exclude_user_id_seq'; //PK Sequence name

	var $user_obj = NULL;

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

	function getUserObject() {
		if ( is_object($this->user_obj) ) {
			return $this->user_obj;
		} else {
			$ulf = TTnew( 'UserListFactory' );
			$ulf->getById( $this->getExcludeUser() );
			if ( $ulf->getRecordCount() == 1 ) {
				$this->user_obj = $ulf->getCurrent();
				return $this->user_obj;
			}

			return FALSE;
		}
	}

	function getExcludeUser() {
		if ( isset($this->data['user_id']) ) {
			return $this->data['user_id'];
		}

		return FALSE;
	}
	function setExcludeUser($id) {
		$id = trim($id);

		$ulf = TTnew( 'UserListFactory' );

		if ( $this->Validator->isResultSetWithRows(	'exclude_user',
													$ulf->getByID($id),
													TTi18n::gettext('Selected User is invalid')
													) ) {
			$this->data['user_id'] = $id;

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
		$u_obj = $this->getUserObject();
		if ( is_object($u_obj) ) {
			return TTLog::addEntry( $this->getStation(), $log_action, TTi18n::getText('Employee').': '. $u_obj->getFullName( FALSE, TRUE ) , NULL, $this->getTable() );
		}

		return FALSE;
	}
}
?>
