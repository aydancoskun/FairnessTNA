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
class MessageSenderFactory extends Factory
{
    protected $table = 'message_sender';
    protected $pk_sequence_name = 'message_sender_id_seq'; //PK Sequence name
    protected $obj_handler = null;

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

    public function getParent()
    {
        if (isset($this->data['parent_id'])) {
            return (int)$this->data['parent_id'];
        }

        return false;
    }

    public function setParent($id)
    {
        $id = trim($id);

        if (empty($id)) {
            $id = 0;
        }

        $mslf = TTnew('MessageSenderListFactory');

        if ($id == 0
            or $this->Validator->isResultSetWithRows('parent',
                $mslf->getByID($id),
                TTi18n::gettext('Parent is invalid')
            )
        ) {
            $this->data['parent_id'] = $id;

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

    public function postSave()
    {
        return true;
    }
}
