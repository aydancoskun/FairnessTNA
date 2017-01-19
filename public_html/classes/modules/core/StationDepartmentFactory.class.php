<?php
/*********************************************************************************
 * This file is part of "Fairness", a Payroll and Time Management program.
 * Fairness is Copyright 2013 Aydan Coskun (aydan.ayfer.coskun@gmail.com)
 * Portions of this software are Copyright of T i m e T r e x Software Inc.
 * Fairness is a fork of "T i m e T r e x Workforce Management" Software.
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


/**
 * @package Core
 */
class StationDepartmentFactory extends Factory {
	protected $table = 'station_department';
	protected $pk_sequence_name = 'station_department_id_seq'; //PK Sequence name

	var $department_obj = NULL;

	function getStation() {
		if ( isset($this->data['station_id']) ) {
			return (int)$this->data['station_id'];
		}
	}
	function setStation($id) {
		$id = trim($id);

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

	function getDepartmentObject() {
		if ( is_object($this->department_obj) ) {
			return $this->department_obj;
		} else {
			$dlf = TTnew( 'DepartmentListFactory' );
			$dlf->getById( $this->getDepartment() );
			if ( $dlf->getRecordCount() == 1 ) {
				$this->department_obj = $dlf->getCurrent();
				return $this->department_obj;
			}

			return FALSE;
		}
	}
	function getDepartment() {
		if ( isset($this->data['department_id']) ) {
			return (int)$this->data['department_id'];
		}

		return FALSE;
	}
	function setDepartment($id) {
		$id = trim($id);

		$dlf = TTnew( 'DepartmentListFactory' );

		if ( $this->Validator->isResultSetWithRows(	'department',
													$dlf->getByID($id),
													TTi18n::gettext('Selected Department is invalid')
													) ) {
			$this->data['department_id'] = $id;

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
		$d_obj = $this->getDepartmentObject();
		if ( is_object($d_obj) ) {
			return TTLog::addEntry( $this->getStation(), $log_action, TTi18n::getText('Department').': '. $d_obj->getName(), NULL, $this->getTable() );
		}

		return FALSE;
	}
}
?>
