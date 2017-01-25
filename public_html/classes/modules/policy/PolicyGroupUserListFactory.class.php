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
class PolicyGroupUserListFactory extends PolicyGroupUserFactory implements IteratorAggregate
{
    public function getAll($limit = null, $page = null, $where = null, $order = null)
    {
        $query = '
					select	*
					from	' . $this->getTable();
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

        $pgf = new PolicyGroupFactory();

        $ph = array(
            'company_id' => (int)$company_id
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a
					LEFT JOIN ' . $pgf->getTable() . ' as pgf ON a.policy_group_id = pgf.id
					where	pgf.company_id = ?
						AND ( pgf.deleted = 0 )';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByPolicyGroupId($id, $where = null, $order = null)
    {
        if ($id == '') {
            return false;
        }

        $pgf = new PolicyGroupFactory();

        $ph = array(
            'id' => (int)$id,
        );


        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a,
							' . $pgf->getTable() . ' as b
					where	b.id = a.policy_group_id
						AND a.policy_group_id = ?
					';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getTotalByPolicyGroupId($id, $where = null, $order = null)
    {
        if ($id == '') {
            return false;
        }

        $pgf = new PolicyGroupFactory();

        $ph = array(
            'id' => (int)$id,
        );


        $query = '
					select	count(*)
					from	' . $this->getTable() . ' as a,
							' . $pgf->getTable() . ' as b
					where	b.id = a.policy_group_id
						AND a.policy_group_id = ?
					';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        //$this->ExecuteSQL( $query, $ph );
        return (int)$this->db->getOne($query, $ph);
    }

    public function getByUserId($id, $where = null, $order = null)
    {
        if ($id == '') {
            return false;
        }

        $uf = new UserFactory();

        $ph = array();

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a,
							' . $uf->getTable() . ' as b
					where	b.id = a.user_id
						AND a.user_id in (' . $this->getListSQL($id, $ph, 'int') . ')
					';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByPolicyGroupIdAndUserId($id, $user_id, $where = null, $order = null)
    {
        if ($id == '') {
            return false;
        }

        if ($user_id == '') {
            return false;
        }

        $pgf = new PolicyGroupFactory();

        $ph = array(
            'id' => (int)$id,
            'user_id' => (int)$user_id,
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a,
							' . $pgf->getTable() . ' as b
					where	b.id = a.policy_group_id
						AND a.policy_group_id = ?
						AND a.user_id = ?
					';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByPolicyGroupIdArray($id)
    {
        $pgotplf = new PolicyGroupOverTimePolicyListFactory();

        $pgotplf->getByPolicyGroupId($id);

        $list = array();
        foreach ($pgotplf as $obj) {
            $list[$obj->getOverTimePolicy()] = null;
        }

        if (empty($list) == false) {
            return $list;
        }

        return array();
    }
}
