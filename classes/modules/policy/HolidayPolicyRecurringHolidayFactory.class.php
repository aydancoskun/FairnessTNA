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
class HolidayPolicyRecurringHolidayFactory extends Factory {
	protected $table = 'holiday_policy_recurring_holiday';
	protected $pk_sequence_name = 'holiday_policy_recurring_holiday_id_seq'; //PK Sequence name

	protected $recurring_holiday_obj = NULL;

	/**
	 * @return bool|null
	 */
	function getRecurringHolidayObject() {
		if ( is_object($this->recurring_holiday_obj) ) {
			return $this->recurring_holiday_obj;
		} else {
			$lf = TTnew( 'RecurringHolidayListFactory' ); /** @var RecurringHolidayListFactory $lf */
			$lf->getById( $this->getRecurringHoliday() );
			if ( $lf->getRecordCount() == 1 ) {
				$this->recurring_holiday_obj = $lf->getCurrent();
				return $this->recurring_holiday_obj;
			}

			return FALSE;
		}
	}

	/**
	 * @return bool|mixed
	 */
	function getHolidayPolicy() {
		return $this->getGenericDataValue( 'holiday_policy_id' );
	}

	/**
	 * @param string $value UUID
	 * @return bool
	 */
	function setHolidayPolicy( $value) {
		$value = TTUUID::castUUID( $value );
		return $this->setGenericDataValue( 'holiday_policy_id', $value );
	}

	/**
	 * @return mixed
	 */
	function getRecurringHoliday() {
		return $this->getGenericDataValue( 'recurring_holiday_id' );
	}

	/**
	 * @param string $value UUID
	 * @return bool
	 */
	function setRecurringHoliday( $value) {
		$value = TTUUID::castUUID( $value );
		return $this->setGenericDataValue( 'recurring_holiday_id', $value );
	}

	/**
	 * @return bool
	 */
	function Validate() {
		//
		// BELOW: Validation code moved from set*() functions.
		//

		// Holiday Policy
		if ( $this->getHolidayPolicy() == '' OR $this->getHolidayPolicy() == TTUUID::getZeroID() ) {
			$hplf = TTnew( 'HolidayPolicyListFactory' ); /** @var HolidayPolicyListFactory $hplf */
			$this->Validator->isResultSetWithRows(	'holiday_policy',
													  $hplf->getByID($this->getHolidayPolicy()),
													  TTi18n::gettext('Invalid Holiday Policy')
			);
		}

		// Selected Recurring Holiday
		$rhlf = TTnew( 'RecurringHolidayListFactory' ); /** @var RecurringHolidayListFactory $rhlf */
		$this->Validator->isResultSetWithRows(	'recurring_holiday',
														$rhlf->getByID($this->getRecurringHoliday()),
														TTi18n::gettext('Selected Recurring Holiday is invalid')
													);

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
		$obj = $this->getRecurringHolidayObject();
		if ( is_object($obj) ) {
			return TTLog::addEntry( $this->getHolidayPolicy(), $log_action, TTi18n::getText('Recurring Holiday').': '. $obj->getName(), NULL, $this->getTable() );
		}

		return FALSE;
	}
}
?>
