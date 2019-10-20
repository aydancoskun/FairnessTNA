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
 * @package Modules\Users
 */
class UserDefaultCompanyDeductionFactory extends Factory {
	protected $table = 'user_default_company_deduction';
	protected $pk_sequence_name = 'user_default_company_deduction_id_seq'; //PK Sequence name

	/**
	 * @return bool|mixed
	 */
	function getUserDefault() {
		return $this->getGenericDataValue( 'user_default_id' );
	}

	/**
	 * @param string $value UUID
	 * @return bool
	 */
	function setUserDefault( $value ) {
		$value = TTUUID::castUUID( $value );
		Debug::Text('ID: '. $value, __FILE__, __LINE__, __METHOD__, 10);
		return $this->setGenericDataValue( 'user_default_id', $value );
	}

	/**
	 * @return bool|mixed
	 */
	function getCompanyDeduction() {
		return $this->getGenericDataValue( 'company_deduction_id' );
	}

	/**
	 * @param string $value UUID
	 * @return bool
	 */
	function setCompanyDeduction( $value ) {
		$value = TTUUID::castUUID( $value );
		Debug::Text('ID: '. $value, __FILE__, __LINE__, __METHOD__, 10);
		return $this->setGenericDataValue( 'company_deduction_id', $value );
	}
	/**
	 * @return bool
	 */
	function Validate() {
		//
		// BELOW: Validation code moved from set*() functions.
		//
		// Employee Default settings
		$udlf = TTnew( 'UserDefaultListFactory' ); /** @var UserDefaultListFactory $udlf */
		$this->Validator->isResultSetWithRows(	'user_default',
														$udlf->getByID($this->getUserDefault()),
														TTi18n::gettext('Employee Default settings is invalid')
													);
		// Deduction
		$cdlf = TTnew( 'CompanyDeductionListFactory' ); /** @var CompanyDeductionListFactory $cdlf */
		$this->Validator->isResultSetWithRows(	'company_deduction',
														$cdlf->getByID($this->getCompanyDeduction()),
														TTi18n::gettext('Deduction is invalid')
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
}
?>
