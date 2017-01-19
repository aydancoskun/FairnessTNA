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
 * @package Modules\PayStub
 */
class PayStubEntryAccountLinkFactory extends Factory {
	protected $table = 'pay_stub_entry_account_link';
	protected $pk_sequence_name = 'pay_stub_entry_account_link_id_seq'; //PK Sequence name

	var $company_obj = NULL;
	function getCompanyObject() {
		if ( is_object($this->company_obj) ) {
			return $this->company_obj;
		} else {
			$clf = TTnew( 'CompanyListFactory' );
			$this->company_obj = $clf->getById( $this->getCompany() )->getCurrent();

			return $this->company_obj;
		}
	}

	function isUnique($company_id, $id) {
		$ph = array(
					'company_id' => (int)$company_id,
					'id' => (int)$id,
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

	function getCompany() {
		if ( isset($this->data['company_id']) ) {
			return (int)$this->data['company_id'];
		}

		return FALSE;
	}
	function setCompany($id) {
		$id = trim($id);

		Debug::Text('Company ID: '. $id, __FILE__, __LINE__, __METHOD__, 10);
		$clf = TTnew( 'CompanyListFactory' );
		if (		$this->Validator->isResultSetWithRows(	'company',
													$clf->getByID($id),
													TTi18n::gettext('Company is invalid')
													)
					AND
					$this->Validator->isTrue(			'company',
														$this->isUnique( $id, $this->getID() ),
														TTi18n::gettext('Pay Stub Account Links for this company already exist')
														)
			) {

			$this->data['company_id'] = $id;

			return TRUE;
		}

		return FALSE;
	}

	function getTotalGross() {
		if ( isset($this->data['total_gross']) ) {
			return $this->data['total_gross'];
		}

		return FALSE;
	}
	function setTotalGross($id) {
		$id = trim($id);

		Debug::Text('ID: '. $id, __FILE__, __LINE__, __METHOD__, 10);
		$psealf = TTnew( 'PayStubEntryAccountListFactory' );

		if (
				( $id == '' OR $id == 0 )
				OR
				$this->Validator->isResultSetWithRows(	'total_gross',
														$psealf->getByID($id),
														TTi18n::gettext('Pay Stub Account is invalid')
													) ) {

			$this->data['total_gross'] = $id;

			return TRUE;
		}

		return FALSE;
	}

	function getTotalEmployeeDeduction() {
		if ( isset($this->data['total_employee_deduction']) ) {
			return $this->data['total_employee_deduction'];
		}

		return FALSE;
	}
	function setTotalEmployeeDeduction($id) {
		$id = trim($id);

		Debug::Text('ID: '. $id, __FILE__, __LINE__, __METHOD__, 10);
		$psealf = TTnew( 'PayStubEntryAccountListFactory' );

		if (
				( $id == '' OR $id == 0 )
				OR
				$this->Validator->isResultSetWithRows(	'total_employee_deduction',
														$psealf->getByID($id),
														TTi18n::gettext('Pay Stub Account is invalid')
													) ) {

			$this->data['total_employee_deduction'] = $id;

			return TRUE;
		}

		return FALSE;
	}

	function getTotalEmployerDeduction() {
		if ( isset($this->data['total_employer_deduction']) ) {
			return $this->data['total_employer_deduction'];
		}

		return FALSE;
	}
	function setTotalEmployerDeduction($id) {
		$id = trim($id);

		Debug::Text('ID: '. $id, __FILE__, __LINE__, __METHOD__, 10);
		$psealf = TTnew( 'PayStubEntryAccountListFactory' );

		if (
				( $id == '' OR $id == 0 )
				OR
				$this->Validator->isResultSetWithRows(	'total_employer_deduction',
														$psealf->getByID($id),
														TTi18n::gettext('Pay Stub Account is invalid')
													) ) {

			$this->data['total_employer_deduction'] = $id;

			return TRUE;
		}

		return FALSE;
	}

	function getTotalNetPay() {
		if ( isset($this->data['total_net_pay']) ) {
			return $this->data['total_net_pay'];
		}

		return FALSE;
	}
	function setTotalNetPay($id) {
		$id = trim($id);

		Debug::Text('ID: '. $id, __FILE__, __LINE__, __METHOD__, 10);
		$psealf = TTnew( 'PayStubEntryAccountListFactory' );

		if (
				( $id == '' OR $id == 0 )
				OR
				$this->Validator->isResultSetWithRows(	'total_net_pay',
														$psealf->getByID($id),
														TTi18n::gettext('Pay Stub Account is invalid')
													) ) {

			$this->data['total_net_pay'] = $id;

			return TRUE;
		}

		return FALSE;
	}

	function getRegularTime() {
		if ( isset($this->data['regular_time']) ) {
			return $this->data['regular_time'];
		}

		return FALSE;
	}
	function setRegularTime($id) {
		$id = trim($id);

		Debug::Text('ID: '. $id, __FILE__, __LINE__, __METHOD__, 10);
		$psealf = TTnew( 'PayStubEntryAccountListFactory' );

		if (
				( $id == '' OR $id == 0 )
				OR
				$this->Validator->isResultSetWithRows(	'regular_time',
														$psealf->getByID($id),
														TTi18n::gettext('Pay Stub Account is invalid')
													) ) {

			$this->data['regular_time'] = $id;

			return TRUE;
		}

		return FALSE;
	}

	function getEmployeeCPP() {
		if ( isset($this->data['employee_cpp']) ) {
			return $this->data['employee_cpp'];
		}

		return FALSE;
	}
	function setEmployeeCPP($id) {
		$id = trim($id);

		Debug::Text('ID: '. $id, __FILE__, __LINE__, __METHOD__, 10);
		$psealf = TTnew( 'PayStubEntryAccountListFactory' );

		if (
				( $id == '' OR $id == 0 )
				OR
				$this->Validator->isResultSetWithRows(	'employee_cpp',
														$psealf->getByID($id),
														TTi18n::gettext('Pay Stub Account is invalid')
													) ) {

			$this->data['employee_cpp'] = $id;

			return TRUE;
		}

		return FALSE;
	}

	function getEmployeeEI() {
		if ( isset($this->data['employee_ei']) ) {
			return $this->data['employee_ei'];
		}

		return FALSE;
	}
	function setEmployeeEI($id) {
		$id = trim($id);

		Debug::Text('ID: '. $id, __FILE__, __LINE__, __METHOD__, 10);
		$psealf = TTnew( 'PayStubEntryAccountListFactory' );

		if (
				( $id == '' OR $id == 0 )
				OR
				$this->Validator->isResultSetWithRows(	'employee_ei',
														$psealf->getByID($id),
														TTi18n::gettext('Pay Stub Account is invalid')
													) ) {

			$this->data['employee_ei'] = $id;

			return TRUE;
		}

		return FALSE;
	}

	function getMonthlyAdvance() {
		if ( isset($this->data['monthly_advance']) ) {
			return $this->data['monthly_advance'];
		}

		return FALSE;
	}
	function setMonthlyAdvance($id) {
		$id = trim($id);

		Debug::Text('ID: '. $id, __FILE__, __LINE__, __METHOD__, 10);
		$psealf = TTnew( 'PayStubEntryAccountListFactory' );

		if (
				( $id == '' OR $id == 0 )
				OR
				$this->Validator->isResultSetWithRows(	'monthly_advance',
														$psealf->getByID($id),
														TTi18n::gettext('Pay Stub Account is invalid')
													) ) {

			$this->data['monthly_advance'] = $id;

			return TRUE;
		}

		return FALSE;
	}

	function getMonthlyAdvanceDeduction() {
		if ( isset($this->data['monthly_advance_deduction']) ) {
			return $this->data['monthly_advance_deduction'];
		}

		return FALSE;
	}
	function setMonthlyAdvanceDeduction($id) {
		$id = trim($id);

		Debug::Text('ID: '. $id, __FILE__, __LINE__, __METHOD__, 10);
		$psealf = TTnew( 'PayStubEntryAccountListFactory' );

		if (
				( $id == '' OR $id == 0 )
				OR
				$this->Validator->isResultSetWithRows(	'monthly_advance_deduction',
														$psealf->getByID($id),
														TTi18n::gettext('Pay Stub Account is invalid')
													) ) {

			$this->data['monthly_advance_deduction'] = $id;

			return TRUE;
		}

		return FALSE;
	}

	function getPayStubEntryAccountIDToTypeIDMap() {
		$retarr = array(
						$this->getTotalGross() => 10,
						$this->getTotalEmployeeDeduction() => 20,
						$this->getTotalEmployerDeduction() => 30,
						);

		return $retarr;
	}

	function postSave() {
		$this->removeCache( $this->getCompanyObject()->getId() );

		return TRUE;
	}

	function addLog( $log_action ) {
		return TTLog::addEntry( $this->getId(), $log_action, TTi18n::getText('Pay Stub Account Links'), NULL, $this->getTable() );
	}
}
?>
