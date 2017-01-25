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
 * @package Modules\Company
 */
class CompanyDeductionPayStubEntryAccountFactory extends Factory
{
    protected $table = 'company_deduction_pay_stub_entry_account';
    protected $pk_sequence_name = 'company_deduction_pay_stub_entry_account_id_seq'; //PK Sequence name

    protected $pay_stub_entry_account_obj = null;

    public function _getFactoryOptions($name, $parent = null)
    {
        $retval = null;
        switch ($name) {
            case 'type':
                $retval = array(
                    10 => TTi18n::gettext('Include'),
                    20 => TTi18n::gettext('Exclude'),
                );
                break;

        }

        return $retval;
    }

    public function setCompanyDeduction($id)
    {
        $id = trim($id);

        Debug::Text('ID: ' . $id, __FILE__, __LINE__, __METHOD__, 10);
        $cdlf = TTnew('CompanyDeductionListFactory');

        if ($id != 0
            or
            $this->Validator->isResultSetWithRows('company_deduction',
                $cdlf->getByID($id),
                TTi18n::gettext('Tax / Deduction is invalid')
            )
        ) {
            $this->data['company_deduction_id'] = $id;

            return true;
        }

        return false;
    }

    public function setType($type)
    {
        $type = trim($type);

        if ($this->Validator->inArrayKey('type',
            $type,
            TTi18n::gettext('Incorrect Type'),
            $this->getOptions('type'))
        ) {
            $this->data['type_id'] = $type;

            return true;
        }

        return false;
    }

    public function setPayStubEntryAccount($id)
    {
        $id = trim($id);

        Debug::Text('ID: ' . $id, __FILE__, __LINE__, __METHOD__, 10);
        $psealf = TTnew('PayStubEntryAccountListFactory');

        if (
        $this->Validator->isResultSetWithRows('pay_stub_entry_account',
            $psealf->getByID($id),
            TTi18n::gettext('Pay Stub Account is invalid')
        )
        ) {
            $this->data['pay_stub_entry_account_id'] = $id;

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

    public function setCreatedDate($epoch = null)
    {
        return false;
    }

    //This table doesn't have any of these columns, so overload the functions.

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
        $obj = $this->getPayStubEntryAccountObject();
        if (is_object($obj)) {
            $type = Option::getByKey($this->getType(), Misc::TrimSortPrefix($this->getOptions('type')));
            return TTLog::addEntry($this->getCompanyDeduction(), $log_action, $type . ' ' . TTi18n::getText('Pay Stub Account') . ': ' . $obj->getName(), null, $this->getTable());
        }
    }

    public function getPayStubEntryAccountObject()
    {
        if (is_object($this->pay_stub_entry_account_obj)) {
            return $this->pay_stub_entry_account_obj;
        } else {
            $psealf = TTnew('PayStubEntryAccountListFactory');
            $psealf->getById($this->getPayStubEntryAccount());
            if ($psealf->getRecordCount() > 0) {
                $this->pay_stub_entry_account_obj = $psealf->getCurrent();
                return $this->pay_stub_entry_account_obj;
            }

            return false;
        }
    }

    public function getPayStubEntryAccount()
    {
        if (isset($this->data['pay_stub_entry_account_id'])) {
            return (int)$this->data['pay_stub_entry_account_id'];
        }

        return false;
    }

    public function getType()
    {
        if (isset($this->data['type_id'])) {
            return (int)$this->data['type_id'];
        }

        return false;
    }

    public function getCompanyDeduction()
    {
        if (isset($this->data['company_deduction_id'])) {
            return (int)$this->data['company_deduction_id'];
        }

        return false;
    }
}
