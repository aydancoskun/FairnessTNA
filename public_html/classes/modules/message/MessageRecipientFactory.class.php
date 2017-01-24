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
 * @package Modules\Message
 */
class MessageRecipientFactory extends Factory
{
    protected $table = 'message_recipient';
    protected $pk_sequence_name = 'message_recipient_id_seq'; //PK Sequence name
    protected $obj_handler = null;

    public function _getFactoryOptions($name, $parent = null)
    {
        $retval = null;
        switch ($name) {
            case 'status':
                $retval = array(
                    10 => TTi18n::gettext('UNREAD'),
                    20 => TTi18n::gettext('READ')
                );
                break;
        }

        return $retval;
    }

    public function getUser()
    {
        return (int)$this->data['user_id'];
    }

    public function setUser($id)
    {
        $id = trim($id);

        $ulf = TTnew('UserListFactory');

        if ($id == 0
            or $this->Validator->isResultSetWithRows('user',
                $ulf->getByID($id),
                TTi18n::gettext('Invalid Employee')
            )
        ) {
            $this->data['user_id'] = $id;

            return true;
        }

        return false;
    }

    public function getMessageSender()
    {
        if (isset($this->data['message_sender_id'])) {
            return (int)$this->data['message_sender_id'];
        }

        return false;
    }

    public function setMessageSender($id)
    {
        $id = trim($id);

        $mslf = TTnew('MessageSenderListFactory');

        if ($this->Validator->isResultSetWithRows('message_sender_id',
            $mslf->getByID($id),
            TTi18n::gettext('Message Sender is invalid')
        )
        ) {
            $this->data['message_sender_id'] = $id;

            return true;
        }

        return false;
    }

    public function getMessageControl()
    {
        if (isset($this->data['message_control_id'])) {
            return (int)$this->data['message_control_id'];
        }

        return false;
    }

    public function setMessageControl($id)
    {
        $id = trim($id);

        $mclf = TTnew('MessageControlListFactory');

        if ($this->Validator->isResultSetWithRows('message_control_id',
            $mclf->getByID($id),
            TTi18n::gettext('Message Control is invalid')
        )
        ) {
            $this->data['message_control_id'] = $id;

            return true;
        }

        return false;
    }

    public function getStatusDate()
    {
        if (isset($this->data['status_date'])) {
            return $this->data['status_date'];
        }

        return false;
    }

    public function isAck()
    {
        if ($this->getRequireAck() == true and $this->getAckDate() == '') {
            return false;
        }

        return true;
    }

    public function getAckDate()
    {
        if (isset($this->data['ack_date'])) {
            return $this->data['ack_date'];
        }

        return false;
    }

    public function setAck($bool)
    {
        $this->data['ack'] = $this->toBool($bool);

        if ($this->getAck() == true) {
            $this->setAckDate();
            $this->setAckBy();
        }

        return true;
    }

    public function getAck()
    {
        return $this->fromBool($this->data['ack']);
    }

    public function setAckDate($epoch = null)
    {
        $epoch = (!is_int($epoch)) ? trim($epoch) : $epoch; //Dont trim integer values, as it changes them to strings.

        if ($epoch == null) {
            $epoch = TTDate::getTime();
        }

        if ($this->Validator->isDate('ack_date',
            $epoch,
            TTi18n::gettext('Invalid Acknowledge Date'))
        ) {
            $this->data['ack_date'] = $epoch;

            return true;
        }

        return false;
    }

    public function preSave()
    {
        if ($this->getStatus() == false) {
            $this->setStatus(10); //UNREAD
        }
        return true;
    }

    public function getStatus()
    {
        if (isset($this->data['status_id'])) {
            return (int)$this->data['status_id'];
        }

        return false;
    }

    public function setStatus($status)
    {
        $status = trim($status);

        if ($this->Validator->inArrayKey('status',
            $status,
            TTi18n::gettext('Incorrect Status'),
            $this->getOptions('status'))
        ) {
            $this->setStatusDate();

            $this->data['status_id'] = $status;

            return true;
        }

        return false;
    }

    public function setStatusDate($epoch = null)
    {
        $epoch = (!is_int($epoch)) ? trim($epoch) : $epoch; //Dont trim integer values, as it changes them to strings.

        if ($epoch == null) {
            $epoch = TTDate::getTime();
        }

        if ($this->Validator->isDate('status_date',
            $epoch,
            TTi18n::gettext('Incorrect Date'))
        ) {
            $this->data['status_date'] = $epoch;

            return true;
        }

        return false;
    }

    public function postSave()
    {
        return true;
    }
}
