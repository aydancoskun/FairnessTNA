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
 * @package Modules\Department
 */
class DepartmentBranchListFactory extends DepartmentBranchFactory implements IteratorAggregate
{
    public function getAll($limit = null, $page = null, $where = null, $order = null)
    {
        $query = '
					select	*
					from	' . $this->getTable() . '
				';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, null, $limit, $page);

        return $this;
    }

    public function getById($id, $where = null, $order = null)
    {
        if ($id == '') {
            return false;
        }

        $ph = array(
            'id' => (int)$id,
        );


        $query = '
					select	*
					from	' . $this->getTable() . '
					where	id = ?
				';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByCompanyId($company_id, $where = null, $order = null)
    {
        if ($company_id == '') {
            return false;
        }

        $df = new DepartmentFactory();

        $ph = array(
            'company_id' => (int)$company_id,
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a
					LEFT JOIN ' . $df->getTable() . ' as df ON a.department_id = df.id
					where	df.company_id = ?
					AND df.deleted = 0';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByBranchId($id, $where = null, $order = null)
    {
        if ($id == '') {
            return false;
        }

        $ph = array(
            'id' => (int)$id,
        );


        $query = '
					select	*
					from	' . $this->getTable() . '
					where	branch_id = ?
				';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByIdAndBranchId($id, $branch_id, $order = null)
    {
        if ($id == '') {
            return false;
        }

        if ($branch_id == '') {
            return false;
        }

        $ph = array(
            'branch_id' => (int)$branch_id,
            'id' => (int)$id,
        );

        $query = '
					select	*
					from	' . $this->getTable() . '
					where	branch_id = ?
						AND	id = ?
					';
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByDepartmentId($id, $where = null, $order = null)
    {
        if ($id == '') {
            return false;
        }

        $ph = array(
            'id' => (int)$id,
        );


        $query = '
					select	*
					from	' . $this->getTable() . '
					where	department_id = ?
				';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByIdAndDepartmentId($id, $department_id, $order = null)
    {
        if ($id == '') {
            return false;
        }

        if ($department_id == '') {
            return false;
        }

        $ph = array(
            'department_id' => (int)$department_id,
            'id' => (int)$id,
        );

        $query = '
					select	*
					from	' . $this->getTable() . '
					where	department_id = ?
						AND	id = ?
					';
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByDepartmentIdAndBranchId($department_id, $branch_id, $order = null)
    {
        if ($department_id == '') {
            return false;
        }

        if ($branch_id == '') {
            return false;
        }

        $ph = array(
            'department_id' => (int)$department_id,
            'branch_id' => (int)$branch_id,
        );

        $query = '
					select	*
					from	' . $this->getTable() . '
					where	department_id = ?
						AND	branch_id = ?
					';
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    /*
        function getByBranchIdArray($branch_id) {
    
            $blf = new BranchListFactory();
            $blf->getByCompanyId($company_id);
    
            $branch_list[0] = '--';
    
            foreach ($blf as $branch) {
                $branch_list[$branch->getID()] = $branch->getName();
            }
    
            return $branch_list;
        }
    */
}
