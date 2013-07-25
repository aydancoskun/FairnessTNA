<?php
/*********************************************************************************
 * This file is part of "Fairness", a Payroll and Time Management program.
 * Fairness is Copyright 2013 Aydan Coscun (aydan.ayfer.coskun@gmail.com)
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
 * $Revision: 3351 $
 * $Id: MessageFactory.class.php 3351 2010-02-18 17:22:09Z ipso $
 * $Date: 2010-02-18 09:22:09 -0800 (Thu, 18 Feb 2010) $
 */

/**
 * @package Modules\Message
 */
class MessageRecipientFactory extends Factory {
	protected $table = 'message_recipient';
	protected $pk_sequence_name = 'message_recipient_id_seq'; //PK Sequence name
	protected $obj_handler = NULL;

	function _getFactoryOptions( $name ) {

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

	function getUser() {
		return $this->data['user_id'];
	}
	function setUser($id) {
		$id = trim($id);

		$ulf = TTnew( 'UserListFactory' );

		if ( $id == 0
				OR $this->Validator->isResultSetWithRows(	'user',
															$ulf->getByID($id),
															TTi18n::gettext('Invalid Employee')
															) ) {
			$this->data['user_id'] = $id;

			return TRUE;
		}

		return FALSE;
	}

	function getMessageSender() {
		if ( isset($this->data['message_sender_id']) ) {
			return $this->data['message_sender_id'];
		}

		return FALSE;
	}
	function setMessageSender($id) {
		$id = trim($id);

		$mslf = TTnew( 'MessageSenderListFactory' );

		if ( $this->Validator->isResultSetWithRows(	'message_sender_id',
													$mslf->getByID($id),
													TTi18n::gettext('Message Sender is invalid')
													) ) {
			$this->data['message_sender_id'] = $id;

			return TRUE;
		}

		return FALSE;
	}

	function getMessageControl() {
		if ( isset($this->data['message_control_id']) ) {
			return $this->data['message_control_id'];
		}

		return FALSE;
	}
	function setMessageControl($id) {
		$id = trim($id);

		$mclf = TTnew( 'MessageControlListFactory' );

		if ( $this->Validator->isResultSetWithRows(	'message_control_id',
													$mclf->getByID($id),
													TTi18n::gettext('Message Control is invalid')
													) ) {
			$this->data['message_control_id'] = $id;

			return TRUE;
		}

		return FALSE;
	}

	function getStatus() {
		if ( isset($this->data['status_id']) ) {
			return $this->data['status_id'];
		}

		return FALSE;
	}
	function setStatus($status) {
		$status = trim($status);

		$key = Option::getByValue($status, $this->getOptions('status') );
		if ($key !== FALSE) {
			$status = $key;
		}

		if ( $this->Validator->inArrayKey(	'status',
											$status,
											TTi18n::gettext('Incorrect Status'),
											$this->getOptions('status')) ) {

			$this->setStatusDate();

			$this->data['status_id'] = $status;

			return FALSE;
		}

		return FALSE;
	}

	function getStatusDate() {
		if ( isset($this->data['status_date']) ) {
			return $this->data['status_date'];
		}

		return FALSE;
	}
	function setStatusDate($epoch = NULL) {
		$epoch = trim($epoch);

		if ($epoch == NULL) {
			$epoch = TTDate::getTime();
		}

		if 	(	$this->Validator->isDate(		'status_date',
												$epoch,
												TTi18n::gettext('Incorrect Date')) ) {

			$this->data['status_date'] = $epoch;

			return TRUE;
		}

		return FALSE;

	}

	function isAck() {
		if ($this->getRequireAck() == TRUE AND $this->getAckDate() == '' ) {
			return FALSE;
		}

		return TRUE;
	}

	function getAck() {
		return $this->fromBool( $this->data['ack'] );
	}
	function setAck($bool) {
		$this->data['ack'] = $this->toBool($bool);

		if ( $this->getAck() == TRUE ) {
			$this->setAckDate();
			$this->setAckBy();
		}

		return true;
	}

	function getAckDate() {
		if ( isset($this->data['ack_date']) ) {
			return $this->data['ack_date'];
		}

		return FALSE;
	}
	function setAckDate($epoch = NULL) {
		$epoch = trim($epoch);

		if ($epoch == NULL) {
			$epoch = TTDate::getTime();
		}

		if 	(	$this->Validator->isDate(		'ack_date',
												$epoch,
												TTi18n::gettext('Invalid Acknowledge Date') ) ) {

			$this->data['ack_date'] = $epoch;

			return TRUE;
		}

		return FALSE;

	}

	function preSave() {
		if ( $this->getStatus() == FALSE ) {
			$this->setStatus( 10 ); //UNREAD
		}
		return TRUE;
	}
	function postSave() {
		return TRUE;
	}
}
?>
