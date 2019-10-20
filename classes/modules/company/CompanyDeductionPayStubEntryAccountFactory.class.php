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
 * @package Modules\Company
 */
class CompanyDeductionPayStubEntryAccountFactory extends Factory {
	protected $table = 'company_deduction_pay_stub_entry_account';
	protected $pk_sequence_name = 'company_deduction_pay_stub_entry_account_id_seq'; //PK Sequence name

	protected $pay_stub_entry_account_obj = NULL;

	/**
	 * @param $name
	 * @param null $parent
	 * @return array|null
	 */
	function _getFactoryOptions( $name, $parent = NULL ) {

		$retval = NULL;
		switch( $name ) {
			case 'type':
				$retval = array(
										10 => TTi18n::gettext('Include'),
										20 => TTi18n::gettext('Exclude'),
									);
				break;

		}

		return $retval;
	}

	/**
	 * @return bool|null
	 */
	function getPayStubEntryAccountObject() {
		if ( is_object($this->pay_stub_entry_account_obj) ) {
			return $this->pay_stub_entry_account_obj;
		} else {
			$psealf = TTnew( 'PayStubEntryAccountListFactory' ); /** @var PayStubEntryAccountListFactory $psealf */
			$psealf->getById( $this->getPayStubEntryAccount() );
			if ( $psealf->getRecordCount() > 0 ) {
				$this->pay_stub_entry_account_obj = $psealf->getCurrent();
				return $this->pay_stub_entry_account_obj;
			}

			return FALSE;
		}
	}

	/**
	 * @return bool|mixed
	 */
	function getCompanyDeduction() {
		return $this->getGenericDataValue( 'company_deduction_id' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setCompanyDeduction( $value) {
		$value = TTUUID::castUUID( $value );
		Debug::Text('ID: '. $value, __FILE__, __LINE__, __METHOD__, 10);
		if ( $value != TTUUID::getZeroID() ) {
			return $this->setGenericDataValue( 'company_deduction_id', $value );
		}
		return FALSE;
	}

	/**
	 * @return int
	 */
	function getType() {
		return $this->getGenericDataValue('type_id' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setType( $value) {
		$value = (int)trim($value);
		return $this->setGenericDataValue( 'type_id', $value );
	}


	/**
	 * @return bool|mixed
	 */
	function getPayStubEntryAccount() {
		return $this->getGenericDataValue( 'pay_stub_entry_account_id' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setPayStubEntryAccount( $value) {
		$value = TTUUID::castUUID( $value );
		Debug::Text('ID: '. $value, __FILE__, __LINE__, __METHOD__, 10);
		return $this->setGenericDataValue( 'pay_stub_entry_account_id', $value );
	}

	/**
	 * @return bool
	 */
	function Validate() {
		// Tax / Deduction

		//Because this is a child class, don't validate the parent record here as it may be not be saved yet.
//		if ( $this->getCompanyDeduction() !== FALSE AND $this->getCompanyDeduction() != TTUUID::getZeroID() ) {
//			$cdlf = TTnew( 'CompanyDeductionListFactory' );
//			$this->Validator->isResultSetWithRows(	'company_deduction',
//															$cdlf->getByID($this->getCompanyDeduction()),
//															TTi18n::gettext('Tax / Deduction is invalid')
//														);
//		}
		//
		// BELOW: Validation code moved from set*() functions.
		//
		// Type
		$this->Validator->inArrayKey(	'type',
												$this->getType(),
												TTi18n::gettext('Incorrect Type'),
												$this->getOptions('type')
											);

		// Pay Stub Account
		$psealf = TTnew( 'PayStubEntryAccountListFactory' ); /** @var PayStubEntryAccountListFactory $psealf */
		$this->Validator->isResultSetWithRows(	'pay_stub_entry_account',
														$psealf->getByID($this->getPayStubEntryAccount()),
														TTi18n::gettext('Pay Stub Account is invalid')
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
		$obj = $this->getPayStubEntryAccountObject();
		if ( is_object($obj) ) {
			$type = Option::getByKey($this->getType(), Misc::TrimSortPrefix( $this->getOptions('type') ) );
			return TTLog::addEntry( $this->getCompanyDeduction(), $log_action, $type .' '. TTi18n::getText('Pay Stub Account').': '. $obj->getName(), NULL, $this->getTable() );
		}

		return FALSE;
	}
}
?>
