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
class MessageRecipientFactory extends Factory {
	protected $table = 'message_recipient';
	protected $pk_sequence_name = 'message_recipient_id_seq'; //PK Sequence name
	protected $obj_handler = NULL;

	/**
	 * @param $name
	 * @param null $parent
	 * @return array|null
	 */
	function _getFactoryOptions( $name, $parent = NULL ) {

		$retval = NULL;
		switch( $name ) {
			case 'status':
				$retval = array(
										10 => TTi18n::gettext('UNREAD'),
										20 => TTi18n::gettext('READ')
									);
				break;
		}

		return $retval;
	}

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
	function getMessageSender() {
		return $this->getGenericDataValue( 'message_sender_id' );
	}

	/**
	 * @param string $value UUID
	 * @return bool
	 */
	function setMessageSender( $value) {
		$value = TTUUID::castUUID( $value );
		return $this->setGenericDataValue( 'message_sender_id', $value );
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
	 * @return bool|int
	 */
	function getStatus() {
		return $this->getGenericDataValue( 'status_id' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setStatus( $value) {
		$value = (int)trim($value);
		$this->setStatusDate();
		return $this->setGenericDataValue( 'status_id', $value );
	}

	/**
	 * @return bool|mixed
	 */
	function getStatusDate() {
		return $this->getGenericDataValue( 'status_date' );
	}

	/**
	 * @param int $value EPOCH
	 * @return bool
	 */
	function setStatusDate( $value = NULL) {
		$value = ( !is_int($value) ) ? trim($value) : $value; //Dont trim integer values, as it changes them to strings.
		if ($value == NULL) {
			$value = TTDate::getTime();
		}
		return $this->setGenericDataValue( 'status_date', $value );
	}

	/**
	 * @return bool
	 */
	function isAck() {
		if ($this->getRequireAck() == TRUE AND $this->getAckDate() == '' ) {
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * @return bool
	 */
	function getAck() {
		return $this->fromBool( $this->getGenericDataValue( 'ack' ) );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setAck( $value) {
		$this->setGenericDataValue( 'ack', $this->toBool($value) );
		if ( $this->getAck() == TRUE ) {
			$this->setAckDate();
			$this->setAckBy();
		}

		return TRUE;
	}

	/**
	 * @return bool|mixed
	 */
	function getAckDate() {
		return $this->getGenericDataValue( 'ack_date' );
	}

	/**
	 * @param int $value EPOCH
	 * @return bool
	 */
	function setAckDate( $value = NULL) {
		$value = ( !is_int($value) ) ? trim($value) : $value; //Dont trim integer values, as it changes them to strings.
		if ($value == NULL) {
			$value = TTDate::getTime();
		}
		return $this->setGenericDataValue( 'ack_date', $value );

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
		// Message Sender
		if ( $this->isNew() == TRUE ) { //If the sender deletes their sent message, this validation will fail if the receiving tries to view/mark the message as read.
			if ( $this->getMessageSender() !== FALSE ) {
				$mslf = TTnew( 'MessageSenderListFactory' ); /** @var MessageSenderListFactory $mslf */
				$this->Validator->isResultSetWithRows( 'message_sender_id',
													   $mslf->getByID( $this->getMessageSender() ),
													   TTi18n::gettext( 'Message Sender is invalid' )
				);
			}
		}

		// Message Control
		if ( $this->getMessageControl() !== FALSE ) {
			$mclf = TTnew( 'MessageControlListFactory' ); /** @var MessageControlListFactory $mclf */
			$this->Validator->isResultSetWithRows(	'message_control_id',
															$mclf->getByID($this->getMessageControl()),
															TTi18n::gettext('Message Control is invalid')
														);
		}
		// Status
		if ( $this->getStatus() !== FALSE ) {
			$this->Validator->inArrayKey(	'status',
													$this->getStatus(),
													TTi18n::gettext('Incorrect Status'),
													$this->getOptions('status')
												);
		}
		// Date
		if ( $this->getStatusDate() !== FALSE ) {
			$this->Validator->isDate(		'status_date',
													$this->getStatusDate(),
													TTi18n::gettext('Incorrect Date')
												);
		}
		// Acknowledge Date
		if ( $this->getAckDate() !== FALSE ) {
			$this->Validator->isDate(		'ack_date',
													$this->getAckDate(),
													TTi18n::gettext('Invalid Acknowledge Date')
												);
		}
		//
		// ABOVE: Validation code moved from set*() functions.
		//

		return TRUE;
	}
	/**
	 * @return bool
	 */
	function preSave() {
		if ( $this->getStatus() == FALSE ) {
			$this->setStatus( 10 ); //UNREAD
		}
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
