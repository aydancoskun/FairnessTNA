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
class PremiumPolicyBranchFactory extends Factory
{
    protected $table = 'premium_policy_branch';
    protected $pk_sequence_name = 'premium_policy_branch_id_seq'; //PK Sequence name

    protected $branch_obj = null;

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

    public function setBranch($id)
    {
        $id = trim($id);

        $blf = TTnew('BranchListFactory');

        if ($this->Validator->isResultSetWithRows('branch',
            $blf->getByID($id),
            TTi18n::gettext('Selected Branch is invalid')
        )
        ) {
            $this->data['branch_id'] = $id;

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
        $obj = $this->getBranchObject();
        if (is_object($obj)) {
            return TTLog::addEntry($this->getPremiumPolicy(), $log_action, TTi18n::getText('Branch') . ': ' . $obj->getName(), null, $this->getTable());
        }
    }

    public function getBranchObject()
    {
        if (is_object($this->branch_obj)) {
            return $this->branch_obj;
        } else {
            $lf = TTnew('BranchListFactory');
            $lf->getById($this->getBranch());
            if ($lf->getRecordCount() == 1) {
                $this->branch_obj = $lf->getCurrent();
                return $this->branch_obj;
            }

            return false;
        }
    }

    public function getBranch()
    {
        if (isset($this->data['branch_id'])) {
            return (int)$this->data['branch_id'];
        }

        return false;
    }
}
