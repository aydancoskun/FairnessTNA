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
 * @package Modules\Policy
 */
class HolidayPolicyRecurringHolidayFactory extends Factory
{
    protected $table = 'holiday_policy_recurring_holiday';
    protected $pk_sequence_name = 'holiday_policy_recurring_holiday_id_seq'; //PK Sequence name

    protected $recurring_holiday_obj = null;

    public function setHolidayPolicy($id)
    {
        $id = trim($id);

        if (
        $this->Validator->isNumeric('holiday_policy',
            $id,
            TTi18n::gettext('Holiday Policy is invalid')

        /*
        $this->Validator->isResultSetWithRows(	'holiday_policy',
                                                $hplf->getByID($id),
                                                TTi18n::gettext('Holiday Policy is invalid')
         */
        )
        ) {
            $this->data['holiday_policy_id'] = $id;

            return true;
        }

        return false;
    }

    public function setRecurringHoliday($id)
    {
        $id = trim($id);

        $rhlf = TTnew('RecurringHolidayListFactory');

        if ($id != 0
            and $this->Validator->isResultSetWithRows('recurring_holiday',
                $rhlf->getByID($id),
                TTi18n::gettext('Selected Recurring Holiday is invalid')
            )
        ) {
            $this->data['recurring_holiday_id'] = $id;

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
        $obj = $this->getRecurringHolidayObject();
        if (is_object($obj)) {
            return TTLog::addEntry($this->getHolidayPolicy(), $log_action, TTi18n::getText('Recurring Holiday') . ': ' . $obj->getName(), null, $this->getTable());
        }
    }

    public function getRecurringHolidayObject()
    {
        if (is_object($this->recurring_holiday_obj)) {
            return $this->recurring_holiday_obj;
        } else {
            $lf = TTnew('RecurringHolidayListFactory');
            $lf->getById($this->getRecurringHoliday());
            if ($lf->getRecordCount() == 1) {
                $this->recurring_holiday_obj = $lf->getCurrent();
                return $this->recurring_holiday_obj;
            }

            return false;
        }
    }

    public function getRecurringHoliday()
    {
        if (isset($this->data['recurring_holiday_id'])) {
            return (int)$this->data['recurring_holiday_id'];
        }
    }

    public function getHolidayPolicy()
    {
        if (isset($this->data['holiday_policy_id'])) {
            return (int)$this->data['holiday_policy_id'];
        }

        return false;
    }
}
