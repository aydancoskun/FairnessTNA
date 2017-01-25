<?php
/**********************************************************************************
 * This file is part of "FairnessTNA", a Payroll and Time Management program.
 * FairnessTNA is copyright 2013-2017 Aydan Coskun (aydan.ayfer.coskun@gmail.com)
 * others. For full attribution and copyrights details see the COPYRIGHT file.
 *
 * FairnessTNA is free software; you can redistribute it and/or modify it under the
 * terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation, either version 3 of the License, or (at you option )
 * any later version.
 *
 * FairnessTNA is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE.  See the GNU Affero General Public License for more
 * details.
 *
 * You should have received a copy of the GNU Affero General Public License along
 * with this program; if not, see http://www.gnu.org/licenses or write to the Free
 * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301 USA.
 *********************************************************************************/


/**
 * @package Modules\PayPeriod
 */
class PayPeriodScheduleUserFactory extends Factory
{
    protected $table = 'pay_period_schedule_user';
    protected $pk_sequence_name = 'pay_period_schedule_user_id_seq'; //PK Sequence name

    protected $user_obj = null;
    protected $pay_period_schedule_obj = null;

    public function getPayPeriodScheduleObject()
    {
        return $this->getGenericObject('PayPeriodScheduleListFactory', $this->getPayPeriodSchedule(), 'pay_period_schedule_obj');
    }

    public function getPayPeriodSchedule()
    {
        return (int)$this->data['pay_period_schedule_id'];
    }

    public function setPayPeriodSchedule($id)
    {
        $id = trim($id);

        $ppslf = TTnew('PayPeriodScheduleListFactory');

        if ($id != 0
            or $this->Validator->isResultSetWithRows('pay_period_schedule',
                $ppslf->getByID($id),
                TTi18n::gettext('Pay Period Schedule is invalid')
            )
        ) {
            $this->data['pay_period_schedule_id'] = $id;

            return true;
        }

        return false;
    }

    public function setUser($id)
    {
        $id = trim($id);

        $ulf = TTnew('UserListFactory');

        if ($id != 0
            and $this->Validator->isResultSetWithRows('user',
                $ulf->getByID($id),
                TTi18n::gettext('Selected Employee is invalid')
            )
            and $this->Validator->isTrue('user',
                $this->isUniqueUser($id),
                TTi18n::gettext('Selected Employee is already assigned to another Pay Period Schedule')
            )
        ) {
            $this->data['user_id'] = $id;

            return true;
        }

        return false;
    }

    public function isUniqueUser($id)
    {
        $ppslf = TTnew('PayPeriodScheduleListFactory');

        $ph = array(
            'id' => (int)$id,
        );

        $query = 'select a.id from ' . $this->getTable() . ' as a, ' . $ppslf->getTable() . ' as b where a.pay_period_schedule_id = b.id AND a.user_id = ? AND b.deleted=0';
        $user_id = $this->db->GetOne($query, $ph);
        Debug::Arr($user_id, 'Unique User ID: ' . $user_id, __FILE__, __LINE__, __METHOD__, 10);

        if ($user_id === false) {
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

    //This table doesn't have any of these columns, so overload the functions.

    public function getCreatedDate()
    {
        return false;
    }

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
            return TTLog::addEntry($this->getPayPeriodSchedule(), $log_action, TTi18n::getText('Employee') . ': ' . $u_obj->getFullName(false, true), null, $this->getTable());
        }

        return false;
    }

    public function getUserObject()
    {
        return $this->getGenericObject('UserListFactory', $this->getUser(), 'user_obj');
    }

    public function getUser()
    {
        if (isset($this->data['user_id'])) {
            return (int)$this->data['user_id'];
        }

        return false;
    }
}
