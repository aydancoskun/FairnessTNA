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
 * @package Modules\Schedule
 */
class RecurringScheduleUserFactory extends Factory
{
    public $user_obj = null;
        protected $table = 'recurring_schedule_user'; //PK Sequence name
protected $pk_sequence_name = 'recurring_schedule_user_id_seq';

    public function setRecurringScheduleControl($id)
    {
        $id = trim($id);

        if (
        $this->Validator->isNumeric('recurring_schedule_control_id',
            $id,
            TTi18n::gettext('Recurring Schedule is invalid')
        /*
        $this->Validator->isResultSetWithRows(			'recurring_schedule',
                                                        $rsclf->getByID($id),
                                                        TTi18n::gettext('Recurring Schedule is invalid')
        */
        )
        ) {
            $this->data['recurring_schedule_control_id'] = $id;

            return true;
        }

        return false;
    }

    public function setUser($id)
    {
        $id = trim($id);

        $ulf = TTnew('UserListFactory');

        if ($id == 0
            or $this->Validator->isResultSetWithRows('user',
                $ulf->getByID($id),
                TTi18n::gettext('Selected Employee is invalid')
            )
        ) {
            $this->data['user_id'] = $id;

            return true;
        }

        return false;
    }

    public function getDeleted()
    {
        return false;
    }

    public function setDeleted($bool)
    {
        return false;
    }

    public function getCreatedDate()
    {
        return false;
    }

    //This table doesn't have any of these columns, so overload the functions.

    public function setCreatedDate($epoch = null)
    {
        return false;
    }

    public function getCreatedBy()
    {
        return false;
    }

    public function setCreatedBy($id = null)
    {
        return false;
    }

    public function getUpdatedDate()
    {
        return false;
    }

    public function setUpdatedDate($epoch = null)
    {
        return false;
    }

    public function getUpdatedBy()
    {
        return false;
    }

    public function setUpdatedBy($id = null)
    {
        return false;
    }

    public function getDeletedDate()
    {
        return false;
    }

    public function setDeletedDate($epoch = null)
    {
        return false;
    }

    public function getDeletedBy()
    {
        return false;
    }

    public function setDeletedBy($id = null)
    {
        return false;
    }

    public function addLog($log_action)
    {
        $u_obj = $this->getUserObject();
        if (is_object($u_obj)) {
            return TTLog::addEntry($this->getRecurringScheduleControl(), $log_action, TTi18n::getText('Employee') . ': ' . $u_obj->getFullName(false, true), null, $this->getTable());
        }

        return false;
    }

    public function getUserObject()
    {
        if (is_object($this->user_obj)) {
            return $this->user_obj;
        } else {
            $ulf = TTnew('UserListFactory');
            $ulf->getById($this->getUser());
            if ($ulf->getRecordCount() == 1) {
                $this->user_obj = $ulf->getCurrent();
                return $this->user_obj;
            }

            return false;
        }
    }

    public function getUser()
    {
        if (isset($this->data['user_id'])) {
            return (int)$this->data['user_id'];
        }
    }

    public function getRecurringScheduleControl()
    {
        if (isset($this->data['recurring_schedule_control_id'])) {
            return (int)$this->data['recurring_schedule_control_id'];
        }

        return false;
    }
}
