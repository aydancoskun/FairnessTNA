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
 * @package Modules\Message
 */
class MessageSenderFactory extends Factory {
	protected $table = 'message_sender';
	protected $pk_sequence_name = 'message_sender_id_seq'; //PK Sequence name
	protected $obj_handler = NULL;

	/**
	 * @return mixed
	 */
	function getUser() {
		return $this->getGenericDataValue( 'user_id' );
	}

	/**
	 * @param string $value UUID
	 * @return bool
	 */
	function setUser( $value) {
		$value = TTUUID::castUUID( $value );
		return $this->setGenericDataValue( 'user_id', $value );
	}

	/**
	 * @return bool|mixed
	 */
	function getParent() {
		return $this->getGenericDataValue( 'parent_id' );
	}

	/**
	 * @param string $value UUID
	 * @return bool
	 */
	function setParent( $value) {
		$value = TTUUID::castUUID( $value );
		return $this->setGenericDataValue( 'parent_id', $value );
	}

	/**
	 * @return bool|mixed
	 */
	function getMessageControl() {
		return $this->getGenericDataValue( 'message_control_id' );
	}

	/**
	 * @param string $value UUID
	 * @return bool
	 */
	function setMessageControl( $value) {
		$value = TTUUID::castUUID( $value );
		return $this->setGenericDataValue( 'message_control_id', $value );
	}
	/**
	 * @return bool
	 */
	function Validate() {
		//
		// BELOW: Validation code moved from set*() functions.
		//
		// Employee
		if ( $this->getUser() != TTUUID::getZeroID() ) {
			$ulf = TTnew( 'UserListFactory' ); /** @var UserListFactory $ulf */
			$this->Validator->isResultSetWithRows(	'user',
															$ulf->getByID($this->getUser()),
															TTi18n::gettext('Invalid Employee')
														);
		}
		// Parent
		if ( $this->getParent() != TTUUID::getZeroID() ) {
			$mslf = TTnew( 'MessageSenderListFactory' ); /** @var MessageSenderListFactory $mslf */
			$this->Validator->isResultSetWithRows(	'parent',
															$mslf->getByID($this->getParent()),
															TTi18n::gettext('Parent is invalid')
														);
		}
		// Message Control
		$mclf = TTnew( 'MessageControlListFactory' ); /** @var MessageControlListFactory $mclf */
		$this->Validator->isResultSetWithRows(	'message_control_id',
														$mclf->getByID($this->getMessageControl()),
														TTi18n::gettext('Message Control is invalid')
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
		return TRUE;
	}
}
?>
