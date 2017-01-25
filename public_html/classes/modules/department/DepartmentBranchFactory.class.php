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

/*
CREATE TABLE department_branch (
    id serial NOT NULL,
    branch_id integer DEFAULT 0 NOT NULL,
    department_id integer DEFAULT 0 NOT NULL
) WITHOUT OIDS;
*/

/**
 * @package Modules\Department
 */
class DepartmentBranchFactory extends Factory
{
    protected $table = 'department_branch';
    protected $pk_sequence_name = 'department_branch_id_seq'; //PK Sequence name

    public function getDepartment()
    {
        return (int)$this->data['department_id'];
    }

    public function setDepartment($id)
    {
        $id = trim($id);

        $dlf = TTnew('DepartmentListFactory');

        if ($id != 0
            or $this->Validator->isResultSetWithRows('company',
                $dlf->getByID($id),
                TTi18n::gettext('Company is invalid')
            )
        ) {
            $this->data['department_id'] = $id;

            return true;
        }

        return false;
    }

    public function getBranch()
    {
        return (int)$this->data['branch_id'];
    }

    public function setBranch($id)
    {
        $id = trim($id);

        $blf = TTnew('BranchListFactory');

        if ($id != 0
            or $this->Validator->isResultSetWithRows('company',
                $blf->getByID($id),
                TTi18n::gettext('Company is invalid')
            )
        ) {
            $this->data['branch_id'] = $id;

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
