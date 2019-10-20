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
 * @package Modules\PayStub
 */
class PayStubEntryAccountLinkFactory extends Factory {
	protected $table = 'pay_stub_entry_account_link';
	protected $pk_sequence_name = 'pay_stub_entry_account_link_id_seq'; //PK Sequence name

	var $company_obj = NULL;

	/**
	 * @return null
	 */
	function getCompanyObject() {
		if ( is_object($this->company_obj) ) {
			return $this->company_obj;
		} else {
			$clf = TTnew( 'CompanyListFactory' ); /** @var CompanyListFactory $clf */
			$this->company_obj = $clf->getById( $this->getCompany() )->getCurrent();

			return $this->company_obj;
		}
	}

	/**
	 * @param string $company_id UUID
	 * @param string $id UUID
	 * @return bool
	 */
	function isUnique( $company_id, $id) {
		$ph = array(
					'company_id' => TTUUID::castUUID($company_id),
					'id' => TTUUID::castUUID($id),
					);

		$query = 'select id from '. $this->getTable() .' where company_id = ? AND id != ? AND deleted=0';
		$id = $this->db->GetOne($query, $ph);
		Debug::Arr($company_id, 'Company ID: '. $company_id .' ID: '. $id, __FILE__, __LINE__, __METHOD__, 10);

		if ( $id === FALSE ) {
			return TRUE;
		} else {
			if ($id == $this->getId() ) {
				return TRUE;
			}
		}

		return FALSE;
	}

	/**
	 * @return bool|mixed
	 */
	function getCompany() {
		return $this->getGenericDataValue( 'company_id' );
	}

	/**
	 * @param string $value UUID
	 * @return bool
	 */
	function setCompany( $value) {
		$value = TTUUID::castUUID( $value );
		Debug::Text('Company ID: '. $value, __FILE__, __LINE__, __METHOD__, 10);
		return $this->setGenericDataValue( 'company_id', $value );
	}

	/**
	 * @return bool|mixed
	 */
	function getTotalGross() {
		return $this->getGenericDataValue( 'total_gross' );
	}

	/**
	 * @param string $value UUID
	 * @return bool
	 */
	function setTotalGross( $value) {
		$value = TTUUID::castUUID( $value );
		Debug::Text('ID: '. $value, __FILE__, __LINE__, __METHOD__, 10);
		return $this->setGenericDataValue( 'total_gross', $value );
	}

	/**
	 * @return bool|mixed
	 */
	function getTotalEmployeeDeduction() {
		return $this->getGenericDataValue( 'total_employee_deduction' );
	}

	/**
	 * @param string $value UUID
	 * @return bool
	 */
	function setTotalEmployeeDeduction( $value) {
		$value = TTUUID::castUUID( $value );

		Debug::Text('ID: '. $value, __FILE__, __LINE__, __METHOD__, 10);
		return $this->setGenericDataValue( 'total_employee_deduction', $value );
	}

	/**
	 * @return bool|mixed
	 */
	function getTotalEmployerDeduction() {
		return $this->getGenericDataValue( 'total_employer_deduction' );
	}

	/**
	 * @param string $value UUID
	 * @return bool
	 */
	function setTotalEmployerDeduction( $value) {
		$value = TTUUID::castUUID( $value );

		Debug::Text('ID: '. $value, __FILE__, __LINE__, __METHOD__, 10);
		return $this->setGenericDataValue( 'total_employer_deduction', $value );
	}

	/**
	 * @return bool|mixed
	 */
	function getTotalNetPay() {
		return $this->getGenericDataValue( 'total_net_pay' );
	}

	/**
	 * @param string $value UUID
	 * @return bool
	 */
	function setTotalNetPay( $value) {
		$value = TTUUID::castUUID( $value );

		Debug::Text('ID: '. $value, __FILE__, __LINE__, __METHOD__, 10);
		return $this->setGenericDataValue( 'total_net_pay', $value );
	}

	/**
	 * @return bool|mixed
	 */
	function getRegularTime() {
		return $this->getGenericDataValue( 'regular_time' );
	}

	/**
	 * @param string $value UUID
	 * @return bool
	 */
	function setRegularTime( $value) {
		$value = TTUUID::castUUID( $value );

		Debug::Text('ID: '. $value, __FILE__, __LINE__, __METHOD__, 10);
		return $this->setGenericDataValue( 'regular_time', $value );
	}

	/**
	 * DEPRECATED: UserDeductionFactory determines this from the CompanyDeduction record instead now.
	 * @return bool|mixed
	 */
	function getEmployeeCPP() {
		return $this->getGenericDataValue( 'employee_cpp' );
	}

	/**
	 * @param string $value UUID
	 * @return bool
	 */
	function setEmployeeCPP( $value) {
		$value = TTUUID::castUUID( $value );

		Debug::Text('ID: '. $value, __FILE__, __LINE__, __METHOD__, 10);
		return $this->setGenericDataValue( 'employee_cpp', $value );
	}

	/**
	 * DEPRECATED: UserDeductionFactory determines this from the CompanyDeduction record instead now.
	 * @return bool|mixed
	 */
	function getEmployeeEI() {
		return $this->getGenericDataValue( 'employee_ei' );
	}

	/**
	 * @param string $value UUID
	 * @return bool
	 */
	function setEmployeeEI( $value) {
		$value = TTUUID::castUUID( $value );
		Debug::Text('ID: '. $value, __FILE__, __LINE__, __METHOD__, 10);
		return $this->setGenericDataValue( 'employee_ei', $value );
	}

	/**
	 * @return bool|mixed
	 */
	function getMonthlyAdvance() {
		return $this->getGenericDataValue( 'monthly_advance' );
	}

	/**
	 * @param string $value UUID
	 * @return bool
	 */
	function setMonthlyAdvance( $value) {
		$value = TTUUID::castUUID( $value );

		Debug::Text('ID: '. $value, __FILE__, __LINE__, __METHOD__, 10);
		return $this->setGenericDataValue( 'monthly_advance', $value );
	}

	/**
	 * @return bool|mixed
	 */
	function getMonthlyAdvanceDeduction() {
		return $this->getGenericDataValue( 'monthly_advance_deduction' );
	}

	/**
	 * @param string $value UUID
	 * @return bool
	 */
	function setMonthlyAdvanceDeduction( $value) {
		$value = TTUUID::castUUID( $value );

		Debug::Text('ID: '. $value, __FILE__, __LINE__, __METHOD__, 10);
		$psealf = TTnew( 'PayStubEntryAccountListFactory' ); /** @var PayStubEntryAccountListFactory $psealf */
		return $this->setGenericDataValue( 'monthly_advance_deduction', $value );
	}

	/**
	 * @return array
	 */
	function getPayStubEntryAccountIDToTypeIDMap() {
		$retarr = array(
						$this->getTotalGross() => 10,
						$this->getTotalEmployeeDeduction() => 20,
						$this->getTotalEmployerDeduction() => 30,
						);

		return $retarr;
	}
	/**
	 * @return bool
	 */
	function Validate() {
		//
		// BELOW: Validation code moved from set*() functions.
		//
		// Company
		$clf = TTnew( 'CompanyListFactory' ); /** @var CompanyListFactory $clf */
		$this->Validator->isResultSetWithRows(	'company',
														$clf->getByID($this->getCompany()),
														TTi18n::gettext('Company is invalid')
													);
		if ( $this->Validator->isError('company') == FALSE ) {
			$this->Validator->isTrue(			'company',
														$this->isUnique( $this->getCompany(), $this->getID() ),
														TTi18n::gettext('Pay Stub Account Links for this company already exist')
													);
		}
		// Pay Stub Account
		if ( $this->getTotalGross() != TTUUID::getZeroID() ) {
			$psealf = TTnew( 'PayStubEntryAccountListFactory' ); /** @var PayStubEntryAccountListFactory $psealf */
			$this->Validator->isResultSetWithRows(	'total_gross',
															$psealf->getByID($this->getTotalGross()),
															TTi18n::gettext('Pay Stub Account is invalid')
														);
		}
		if ( $this->getTotalEmployeeDeduction() != TTUUID::getZeroID() ) {
			$psealf = TTnew( 'PayStubEntryAccountListFactory' ); /** @var PayStubEntryAccountListFactory $psealf */
			$this->Validator->isResultSetWithRows(	'total_employee_deduction',
															$psealf->getByID($this->getTotalEmployeeDeduction()),
															TTi18n::gettext('Pay Stub Account is invalid')
														);
		}
		if ( $this->getTotalEmployerDeduction() != TTUUID::getZeroID() ) {
			$psealf = TTnew( 'PayStubEntryAccountListFactory' ); /** @var PayStubEntryAccountListFactory $psealf */
			$this->Validator->isResultSetWithRows(	'total_employer_deduction',
															$psealf->getByID($this->getTotalEmployerDeduction()),
															TTi18n::gettext('Pay Stub Account is invalid')
														);
		}
		if ( $this->getTotalNetPay() != TTUUID::getZeroID() ) {
			$psealf = TTnew( 'PayStubEntryAccountListFactory' ); /** @var PayStubEntryAccountListFactory $psealf */
			$this->Validator->isResultSetWithRows(	'total_net_pay',
															$psealf->getByID($this->getTotalNetPay()),
															TTi18n::gettext('Pay Stub Account is invalid')
														);
		}
		if ( $this->getRegularTime() != TTUUID::getZeroID() ) {
			$psealf = TTnew( 'PayStubEntryAccountListFactory' ); /** @var PayStubEntryAccountListFactory $psealf */
			$this->Validator->isResultSetWithRows(	'regular_time',
															$psealf->getByID($this->getRegularTime()),
															TTi18n::gettext('Pay Stub Account is invalid')
														);
		}
		if ( $this->getEmployeeCPP() !== FALSE AND $this->getEmployeeCPP() != TTUUID::getZeroID() ) {
			$psealf = TTnew( 'PayStubEntryAccountListFactory' ); /** @var PayStubEntryAccountListFactory $psealf */
			$this->Validator->isResultSetWithRows(	'employee_cpp',
															$psealf->getByID($this->getEmployeeCPP()),
															TTi18n::gettext('Pay Stub Account is invalid')
														);
		}
		if ( $this->getEmployeeEI() !== FALSE AND $this->getEmployeeEI() != TTUUID::getZeroID() ) {
			$psealf = TTnew( 'PayStubEntryAccountListFactory' ); /** @var PayStubEntryAccountListFactory $psealf */
			$this->Validator->isResultSetWithRows(	'employee_ei',
															$psealf->getByID($this->getEmployeeEI()),
															TTi18n::gettext('Pay Stub Account is invalid')
														);
		}

		//These are deprecated fields and no longer used.
//		if ( $this->getMonthlyAdvance() != TTUUID::getZeroID() ) {
//			$psealf = TTnew( 'PayStubEntryAccountListFactory' );
//			$this->Validator->isResultSetWithRows(	'monthly_advance',
//															$psealf->getByID($this->getMonthlyAdvance()),
//															TTi18n::gettext('Pay Stub Account is invalid')
//														);
//		}
//		if ( $this->getMonthlyAdvanceDeduction() != TTUUID::getZeroID() ) {
//			$this->Validator->isResultSetWithRows(	'monthly_advance_deduction',
//															$psealf->getByID($this->getMonthlyAdvanceDeduction()),
//															TTi18n::gettext('Pay Stub Account is invalid')
//														);
//		}

		//
		// ABOVE: Validation code moved from set*() functions.
		//
		return TRUE;
	}

	/**
	 * @return bool
	 */
	function postSave() {
		$this->removeCache( $this->getCompanyObject()->getId() );

		return TRUE;
	}

	/**
	 * @param $log_action
	 * @return bool
	 */
	function addLog( $log_action ) {
		return TTLog::addEntry( $this->getId(), $log_action, TTi18n::getText('Pay Stub Account Links'), NULL, $this->getTable() );
	}
}
?>
