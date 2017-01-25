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
class ExceptionPolicyListFactory extends ExceptionPolicyFactory implements IteratorAggregate
{
    public function getAll($limit = null, $page = null, $where = null, $order = null)
    {
        $query = '
					select	*
					from	' . $this->getTable() . '
					WHERE deleted = 0';
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
						AND deleted = 0';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByIdAndCompanyId($id, $company_id, $where = null, $order = null)
    {
        if ($company_id == '') {
            return false;
        }

        if ($id == '') {
            return false;
        }

        $epcf = new ExceptionPolicyControlFactory();

        $ph = array(
            'company_id' => (int)$company_id,
            'id' => (int)$id,
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a,
							' . $epcf->getTable() . ' as b
					where
						a.exception_policy_control_id = b.id
						AND b.company_id = ?
						AND a.id = ?
						AND ( a.deleted = 0 AND b.deleted=0 )
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

        $epcf = new ExceptionPolicyControlFactory();

        $ph = array(
            'company_id' => (int)$company_id,
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a,
							' . $epcf->getTable() . ' as b
					where
						a.exception_policy_control_id = b.id
						AND b.company_id = ?
						AND ( a.deleted = 0 AND b.deleted=0 )
						';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByExceptionPolicyControlId($id, $where = null, $order = null)
    {
        if ($id == '') {
            return false;
        }

        if ($order == null) {
            $order = array('type_id' => 'asc');
            $strict = false;
        } else {
            $strict = true;
        }

        $ph = array(
            'id' => (int)$id,
        );


        $query = '
					select	*
					from	' . $this->getTable() . ' as a
					where	exception_policy_control_id = ?
						AND deleted = 0';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByPolicyGroupUserIdAndTypeAndActive($user_id, $type_id, $active = true, $where = null, $order = null)
    {
        if ($user_id == '') {
            return false;
        }

        if ($type_id == '') {
            return false;
        }

        if ($order == null) {
            //$order = array( 'c.type_id' => 'asc', 'c.trigger_time' => 'desc' );
            $strict = false;
        } else {
            $strict = true;
        }

        $pguf = new PolicyGroupUserFactory();
        $pgf = new PolicyGroupFactory();
        $epcf = new ExceptionPolicyControlFactory();

        $ph = array(
            'user_id' => (int)$user_id,
            'active' => $this->toBool($active),
        );

        $query = '
					select	d.*
					from	' . $pguf->getTable() . ' as a,
							' . $pgf->getTable() . ' as b,
							' . $epcf->getTable() . ' as c,
							' . $this->getTable() . ' as d
					where	a.policy_group_id = b.id
						AND b.exception_policy_control_id = c.id
						AND c.id = d.exception_policy_control_id
						AND a.user_id = ?
						AND d.active = ?
						AND d.type_id in (' . $this->getListSQL($type_id, $ph, 'int') . ')
						AND ( b.deleted = 0 AND c.deleted=0 AND d.deleted=0)
						';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByPolicyGroupUserIdAndActive($user_id, $active = true, $where = null, $order = null)
    {
        if ($user_id == '') {
            return false;
        }

        if ($order == null) {
            //$order = array( 'c.type_id' => 'asc', 'c.trigger_time' => 'desc' );
            $strict = false;
        } else {
            $strict = true;
        }

        $pguf = new PolicyGroupUserFactory();
        $pgf = new PolicyGroupFactory();
        $epcf = new ExceptionPolicyControlFactory();

        $ph = array(
            'user_id' => (int)$user_id,
            'active' => $this->toBool($active),
        );

        $query = '
					select	d.*
					from	' . $pguf->getTable() . ' as a,
							' . $pgf->getTable() . ' as b,
							' . $epcf->getTable() . ' as c,
							' . $this->getTable() . ' as d
					where	a.policy_group_id = b.id
						AND b.exception_policy_control_id = c.id
						AND c.id = d.exception_policy_control_id
						AND a.user_id = ?
						AND d.active = ?
						AND ( b.deleted = 0 AND c.deleted=0 AND d.deleted=0)
						';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getAPISearchByCompanyIdAndArrayCriteria($company_id, $filter_data, $limit = null, $page = null, $where = null, $order = null)
    {
        if ($company_id == '') {
            return false;
        }

        if (!is_array($order)) {
            //Use Filter Data ordering if its set.
            if (isset($filter_data['sort_column']) and $filter_data['sort_order']) {
                $order = array(Misc::trimSortPrefix($filter_data['sort_column']) => $filter_data['sort_order']);
            }
        }

        $additional_order_fields = array('type_id');

        $sort_column_aliases = array(
            'type' => 'type_id',
        );

        $order = $this->getColumnsFromAliases($order, $sort_column_aliases);

        if ($order == null) {
            $order = array('type_id' => 'asc');
            $strict = false;
        } else {
            //Always try to order by status first so INACTIVE employees go to the bottom.
            if (!isset($order['type_id'])) {
                $order = Misc::prependArray(array('type_id' => 'asc'), $order);
            }
            //Always sort by last name, first name after other columns
            if (!isset($order['name'])) {
                $order['name'] = 'asc';
            }
            $strict = true;
        }
        //Debug::Arr($order, 'Order Data:', __FILE__, __LINE__, __METHOD__, 10);
        //Debug::Arr($filter_data, 'Filter Data:', __FILE__, __LINE__, __METHOD__, 10);

        $uf = new UserFactory();
        $epcf = new ExceptionPolicyControlFactory();

        $ph = array(
            'company_id' => (int)$company_id,
        );

        $query = '
					select	a.*,
							y.first_name as created_by_first_name,
							y.middle_name as created_by_middle_name,
							y.last_name as created_by_last_name,
							z.first_name as updated_by_first_name,
							z.middle_name as updated_by_middle_name,
							z.last_name as updated_by_last_name
					from	' . $this->getTable() . ' as a
						LEFT JOIN ' . $uf->getTable() . ' as y ON ( a.created_by = y.id AND y.deleted = 0 )
						LEFT JOIN ' . $uf->getTable() . ' as z ON ( a.updated_by = z.id AND z.deleted = 0 )
						LEFT JOIN ' . $epcf->getTable() . ' as b ON ( a.exception_policy_control_id = b.id AND b.deleted = 0 )
					where	b.company_id = ?
					';

        $query .= (isset($filter_data['permission_children_ids'])) ? $this->getWhereClauseSQL('a.created_by', $filter_data['permission_children_ids'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['id'])) ? $this->getWhereClauseSQL('a.id', $filter_data['id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['exclude_id'])) ? $this->getWhereClauseSQL('a.id', $filter_data['exclude_id'], 'not_numeric_list', $ph) : null;

        $query .= (isset($filter_data['name'])) ? $this->getWhereClauseSQL('b.name', $filter_data['name'], 'text', $ph) : null;

        $query .= (isset($filter_data['type_id'])) ? $this->getWhereClauseSQL('a.type_id', $filter_data['type_id'], 'text_list', $ph) : null;
        $query .= (isset($filter_data['exception_policy_control_id'])) ? $this->getWhereClauseSQL('a.exception_policy_control_id', $filter_data['exception_policy_control_id'], 'numeric_list', $ph) : null;

        $query .= (isset($filter_data['created_by'])) ? $this->getWhereClauseSQL(array('a.created_by', 'y.first_name', 'y.last_name'), $filter_data['created_by'], 'user_id_or_name', $ph) : null;
        $query .= (isset($filter_data['updated_by'])) ? $this->getWhereClauseSQL(array('a.updated_by', 'z.first_name', 'z.last_name'), $filter_data['updated_by'], 'user_id_or_name', $ph) : null;

        $query .= ' AND a.deleted = 0 ';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict, $additional_order_fields);

        $this->ExecuteSQL($query, $ph, $limit, $page);

        return $this;
    }
}
