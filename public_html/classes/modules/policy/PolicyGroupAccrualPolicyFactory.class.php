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
class PolicyGroupAccrualPolicyFactory extends Factory
{
    protected $table = 'policy_group_accrual_policy';
    protected $pk_sequence_name = 'policy_group_accrual_policy_id_seq'; //PK Sequence name

    public function getPolicyGroup()
    {
        if (isset($this->data['policy_group_id'])) {
            return (int)$this->data['policy_group_id'];
        }

        return false;
    }

    public function setPolicyGroup($id)
    {
        $id = trim($id);

        $pglf = TTnew('PolicyGroupListFactory');

        if ($this->Validator->isResultSetWithRows('policy_group',
            $pglf->getByID($id),
            TTi18n::gettext('Policy Group is invalid')
        )
        ) {
            $this->data['policy_group_id'] = $id;

            return true;
        }

        return false;
    }

    public function getAccrualPolicy()
    {
        if (isset($this->data['accrual_policy_id'])) {
            return (int)$this->data['accrual_policy_id'];
        }
    }

    public function setAccrualPolicy($id)
    {
        $id = trim($id);

        $aplf = TTnew('AccrualPolicyListFactory');

        if ($id == 0
            or
            $this->Validator->isResultSetWithRows('over_time_policy',
                $aplf->getByID($id),
                TTi18n::gettext('Selected Accrual Policy is invalid')
            )
        ) {
            $this->data['accrual_policy_id'] = $id;

            return true;
        }

        return false;
    }

    //This table doesn't have any of these columns, so overload the functions.
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
}
