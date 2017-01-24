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
 * @package Modules\Policy
 */
class PremiumPolicyDepartmentFactory extends Factory
{
    protected $table = 'premium_policy_department';
    protected $pk_sequence_name = 'premium_policy_department_id_seq'; //PK Sequence name

    protected $department_obj = null;

    public function setPremiumPolicy($id)
    {
        $id = trim($id);

        if ($id == 0
            or
            $this->Validator->isNumeric('premium_policy',
                $id,
                TTi18n::gettext('Selected Premium Policy is invalid')
            /*
                            $this->Validator->isResultSetWithRows(	'premium_policy',
                                                                $pplf->getByID($id),
                                                                TTi18n::gettext('Selected Premium Policy is invalid')
            */
            )
        ) {
            $this->data['premium_policy_id'] = $id;

            return true;
        }

        return false;
    }

    public function setDepartment($id)
    {
        $id = trim($id);

        $dlf = TTnew('DepartmentListFactory');

        if ($this->Validator->isResultSetWithRows('department',
            $dlf->getByID($id),
            TTi18n::gettext('Selected Department is invalid')
        )
        ) {
            $this->data['department_id'] = $id;

            return true;
        }

        return false;
    }

    public function postSave()
    {
        $this->removeCache('premium_policy-' . $this->getPremiumPolicy());
        return true;
    }

    public function getPremiumPolicy()
    {
        if (isset($this->data['premium_policy_id'])) {
            return (int)$this->data['premium_policy_id'];
        }
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
        $obj = $this->getDepartmentObject();
        if (is_object($obj)) {
            return TTLog::addEntry($this->getPremiumPolicy(), $log_action, TTi18n::getText('Department') . ': ' . $obj->getName(), null, $this->getTable());
        }
    }

    public function getDepartmentObject()
    {
        if (is_object($this->department_obj)) {
            return $this->department_obj;
        } else {
            $lf = TTnew('DepartmentListFactory');
            $lf->getById($this->getDepartment());
            if ($lf->getRecordCount() == 1) {
                $this->department_obj = $lf->getCurrent();
                return $this->department_obj;
            }

            return false;
        }
    }

    public function getDepartment()
    {
        if (isset($this->data['department_id'])) {
            return (int)$this->data['department_id'];
        }

        return false;
    }
}
