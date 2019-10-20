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
class CompanyUserCountFactory extends Factory {
	protected $table = 'company_user_count';
	protected $pk_sequence_name = 'company_user_count_id_seq'; //PK Sequence name

	/**
	 * @return mixed
	 */
	function getCompany() {
		return $this->getGenericDataValue( 'company_id' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setCompany( $value) {
		$value = TTUUID::castUUID( $value );
		return $this->setGenericDataValue( 'company_id', $value );
	}

	/**
	 * @param bool $raw
	 * @return bool|int|mixed
	 */
	function getDateStamp( $raw = FALSE ) {
		$value = $this->getGenericDataValue( 'date_stamp' );
		if ( $value !== FALSE ) {
			if ( $raw === TRUE ) {
				return $value;
			} else {
				return TTDate::strtotime( $value );
			}
		}

		return FALSE;
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setDateStamp( $value ) {
		$value = ( !is_int($value) ) ? trim($value) : $value; //Dont trim integer values, as it changes them to strings.
		return $this->setGenericDataValue( 'date_stamp', $value );
	}

	/**
	 * @return bool|mixed
	 */
	function getActiveUsers() {
		return $this->getGenericDataValue( 'active_users' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setActiveUsers( $value) {
		$value = (int)trim($value);
		return $this->setGenericDataValue( 'active_users', $value );
	}

	/**
	 * @return bool|mixed
	 */
	function getInActiveUsers() {
		return $this->getGenericDataValue( 'inactive_users' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setInActiveUsers( $value) {
		$value = (int)trim($value);
		return $this->setGenericDataValue( 'inactive_users', $value );
	}

	/**
	 * @return bool|mixed
	 */
	function getDeletedUsers() {
		return $this->getGenericDataValue( 'deleted_users' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setDeletedUsers( $value) {
		$value = (int)trim($value);
		return $this->setGenericDataValue( 'deleted_users', $value );
	}

	/**
	 * @return bool
	 */
	function Validate() {
		//
		// BELOW: Validation code moved from set*() functions.
		//
		// Company
		if ( $this->getCompany() != TTUUID::getZeroID() ) {
			$clf = TTnew( 'CompanyListFactory' ); /** @var CompanyListFactory $clf */
			$this->Validator->isResultSetWithRows(	'company',
															$clf->getByID($this->getCompany()),
															TTi18n::gettext('Company is invalid')
														);
		}
		// Date
		$this->Validator->isDate(		'date_stamp',
												$this->getDateStamp(),
												TTi18n::gettext('Incorrect date')
											);
		if ( $this->Validator->isError('date_stamp') == FALSE ) {
			if ( $this->getDateStamp() <= 0 ) {
				$this->Validator->isTRUE(		'date_stamp',
												FALSE,
												TTi18n::gettext('Incorrect date')
											);
			}
		}
		// Active users
		$this->Validator->isNumeric(	'active_users',
												$this->getActiveUsers(),
												TTi18n::gettext('Incorrect value')
											);
		// Inactive users
		$this->Validator->isNumeric(	'inactive_users',
												$this->getInActiveUsers(),
												TTi18n::gettext('Incorrect value')
											);
		// Deleted Users
		$this->Validator->isNumeric(	'deleted_users',
												$this->getDeletedUsers(),
												TTi18n::gettext('Incorrect value')
											);
		//
		// ABOVE: Validation code moved from set*() functions.
		//
		return TRUE;
	}

	/**
	 * @return bool
	 */
	function postSave() {
		//$this->removeCache( $this->getId() );

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
