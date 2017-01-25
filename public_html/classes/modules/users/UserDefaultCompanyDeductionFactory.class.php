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
 * @package Modules\Users
 */
class UserDefaultCompanyDeductionFactory extends Factory
{
    protected $table = 'user_default_company_deduction';
    protected $pk_sequence_name = 'user_default_company_deduction_id_seq'; //PK Sequence name

    public function getUserDefault()
    {
        if (isset($this->data['user_default_id'])) {
            return (int)$this->data['user_default_id'];
        }

        return false;
    }

    public function setUserDefault($id)
    {
        $id = trim($id);

        Debug::Text('ID: ' . $id, __FILE__, __LINE__, __METHOD__, 10);
        $udlf = TTnew('UserDefaultListFactory');

        if (
        $this->Validator->isResultSetWithRows('user_default',
            $udlf->getByID($id),
            TTi18n::gettext('Employee Default settings is invalid')
        )
        ) {
            $this->data['user_default_id'] = $id;

            return true;
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

    public function setCompanyDeduction($id)
    {
        $id = trim($id);

        Debug::Text('ID: ' . $id, __FILE__, __LINE__, __METHOD__, 10);
        $cdlf = TTnew('CompanyDeductionListFactory');

        if (
        $this->Validator->isResultSetWithRows('company_deduction',
            $cdlf->getByID($id),
            TTi18n::gettext('Deduction is invalid')
        )
        ) {
            $this->data['company_deduction_id'] = $id;

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
