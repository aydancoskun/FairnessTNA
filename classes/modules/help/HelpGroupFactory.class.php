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
 * @package Modules\Help
 */
class HelpGroupFactory extends Factory {
	protected $table = 'help_group';
	protected $pk_sequence_name = 'help_group_id_seq'; //PK Sequence name

	/**
	 * @return mixed
	 */
	function getHelpGroupControl() {
		return $this->getGenericDataValue( 'help_group_control_id' );
	}

	/**
	 * @param string $value UUID
	 * @return bool
	 */
	function setHelpGroupControl( $value) {
		$value = TTUUID::castUUID( $value );
		return $this->setGenericDataValue( 'help_group_control_id', $value );
	}

	/**
	 * @return mixed
	 */
	function getHelp() {
		return $this->getGenericDataValue( 'help_id' );
	}

	/**
	 * @param string $value UUID
	 * @return bool
	 */
	function setHelp( $value) {
		$value = TTUUID::castUUID( $value );
		return $this->setGenericDataValue( 'help_id', $value );
	}

	/**
	 * @return mixed
	 */
	function getOrder() {
		return $this->getGenericDataValue( 'order_value' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setOrder( $value) {
		$value = trim($value);
		return $this->setGenericDataValue( 'order_value', $value );
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
	 * @return bool
	 */
	function Validate() {
		//
		// BELOW: Validation code moved from set*() functions.
		//
		// Help Group Control
		$hgclf = TTnew( 'HelpGroupControlListFactory' ); /** @var HelpGroupControlListFactory $hgclf */
		$this->Validator->isResultSetWithRows(	'help_group_control',
														$hgclf->getByID($this->getHelpGroupControl()),
														TTi18n::gettext('Help Group Control is invalid')
													);
		// Help Entry
		$hlf = TTnew( 'HelpListFactory' ); /** @var HelpListFactory $hlf */
		$this->Validator->isResultSetWithRows(	'help',
														$hlf->getByID($this->getHelp()),
														TTi18n::gettext('Help Entry is invalid')
													);
		// Order
		$this->Validator->isNumeric(	'order',
												$this->getOrder(),
												TTi18n::gettext('Order is invalid')
											);


		//
		// ABOVE: Validation code moved from set*() functions.
		//
		return TRUE;
	}
}
?>
