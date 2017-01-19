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
 * $Revision: 8371 $
 * $Id: HolidayPolicyRecurringHolidayFactory.class.php 8371 2012-11-22 21:18:57Z ipso $
 * $Date: 2012-11-22 13:18:57 -0800 (Thu, 22 Nov 2012) $
 */

/**
 * @package Modules\Policy
 */
class HolidayPolicyRecurringHolidayFactory extends Factory {
	protected $table = 'holiday_policy_recurring_holiday';
	protected $pk_sequence_name = 'holiday_policy_recurring_holiday_id_seq'; //PK Sequence name

	protected $recurring_holiday_obj = NULL;

	function getRecurringHolidayObject() {
		if ( is_object($this->recurring_holiday_obj) ) {
			return $this->recurring_holiday_obj;
		} else {
			$lf = TTnew( 'RecurringHolidayListFactory' );
			$lf->getById( $this->getRecurringHoliday() );
			if ( $lf->getRecordCount() == 1 ) {
				$this->recurring_holiday_obj = $lf->getCurrent();
				return $this->recurring_holiday_obj;
			}

			return FALSE;
		}
	}

	function getHolidayPolicy() {
		if ( isset($this->data['holiday_policy_id']) ) {
			return $this->data['holiday_policy_id'];
		}

		return FALSE;
	}
	function setHolidayPolicy($id) {
		$id = trim($id);

		$hplf = TTnew( 'HolidayPolicyListFactory' );

		if (
			  $this->Validator->isNumeric(	'holiday_policy',
											$id,
											TTi18n::gettext('Holiday Policy is invalid')

			/*
			  $this->Validator->isResultSetWithRows(	'holiday_policy',
													$hplf->getByID($id),
													TTi18n::gettext('Holiday Policy is invalid')
			 */
															) ) {
			$this->data['holiday_policy_id'] = $id;

			return TRUE;
		}

		return FALSE;
	}

	function getRecurringHoliday() {
		if ( isset($this->data['recurring_holiday_id']) ) {
			return $this->data['recurring_holiday_id'];
		}
	}
	function setRecurringHoliday($id) {
		$id = trim($id);

		$rhlf = TTnew( 'RecurringHolidayListFactory' );

		if ( $id != 0
				AND $this->Validator->isResultSetWithRows(	'recurring_holiday',
															$rhlf->getByID($id),
															TTi18n::gettext('Selected Recurring Holiday is invalid')
															)
			) {

			$this->data['recurring_holiday_id'] = $id;

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
		$obj = $this->getRecurringHolidayObject();
		if ( is_object($obj) ) {
			return TTLog::addEntry( $this->getHolidayPolicy(), $log_action,  TTi18n::getText('Recurring Holiday').': '. $obj->getName(), NULL, $this->getTable() );
		}
	}
}
?>
