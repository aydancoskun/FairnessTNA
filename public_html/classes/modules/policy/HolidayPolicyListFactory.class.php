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
class HolidayPolicyListFactory extends HolidayPolicyFactory implements IteratorAggregate
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
        if ($id == '') {
            return false;
        }

        if ($company_id == '') {
            return false;
        }

        $ph = array(
            'id' => (int)$id,
            'company_id' => (int)$company_id,
        );

        $query = '
					select	*
					from	' . $this->getTable() . '
					where	id = ?
						AND company_id = ?
						AND deleted = 0';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByCompanyIdAndContributingShiftPolicyId($id, $contributing_shift_policy_id, $where = null, $order = null)
    {
        if ($id == '') {
            return false;
        }

        if ($contributing_shift_policy_id == '') {
            return false;
        }

        if ($order == null) {
            $order = array('a.name' => 'asc');
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
					where	company_id = ?
						AND ( 	contributing_shift_policy_id in (' . $this->getListSQL($contributing_shift_policy_id, $ph, 'int') . ')
								OR eligible_contributing_shift_policy_id in (' . $this->getListSQL($contributing_shift_policy_id, $ph, 'int') . ')
							)
						AND deleted = 0';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByPolicyGroupUserId($user_id, $where = null, $order = null)
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

        $pgf = new PolicyGroupFactory();
        $pguf = new PolicyGroupUserFactory();
        $cgmf = new CompanyGenericMapFactory();

        $ph = array(
            'user_id' => (int)$user_id,
        );

        $query = '
					select	distinct d.*
					from	' . $pguf->getTable() . ' as a,
							' . $pgf->getTable() . ' as b,
							' . $cgmf->getTable() . ' as c,
							' . $this->getTable() . ' as d
					where	a.policy_group_id = b.id
						AND ( b.id = c.object_id AND c.company_id = b.company_id AND c.object_type_id = 180)
						AND c.map_id = d.id
						AND a.user_id = ?
						AND ( b.deleted = 0 AND d.deleted = 0 )
						';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByPolicyGroupCompanyIdAndUserIdOrAssignedToContributingShiftPolicy($company_id, $user_id, $where = null, $order = null)
    {
        if ($company_id == '') {
            return false;
        }

        if ($user_id == '') {
            return false;
        }

        if ($order == null) {
            //$order = array( 'c.type_id' => 'asc', 'c.trigger_time' => 'desc' );
            $strict = false;
        } else {
            $strict = true;
        }

        $pgf = new PolicyGroupFactory();
        $pguf = new PolicyGroupUserFactory();
        $cgmf = new CompanyGenericMapFactory();
        $cspf = new ContributingShiftPolicyFactory();

        $ph = array(
            'company_id' => (int)$company_id,
            'user_id' => (int)$user_id,
        );

        //ContributingShiftPolicy holidays must come first as policy group ones overwrite them.
        $query = '
					select distinct d.*, 0 as assigned_to_policy_group
					from
							' . $cgmf->getTable() . ' as c,
							' . $cspf->getTable() . ' as b,
							' . $this->getTable() . ' as d
					where
						( b.id = c.object_id AND c.company_id = b.company_id AND c.object_type_id = 690)
						AND c.map_id = d.id
						AND b.company_id = ?
						AND ( b.deleted = 0 AND d.deleted = 0 )
					UNION ALL
					select	distinct d.*, 1 as assigned_to_policy_group
					from	' . $pguf->getTable() . ' as a,
							' . $pgf->getTable() . ' as b,
							' . $cgmf->getTable() . ' as c,
							' . $this->getTable() . ' as d
					where	a.policy_group_id = b.id
						AND ( b.id = c.object_id AND c.company_id = b.company_id AND c.object_type_id = 180)
						AND c.map_id = d.id
						AND a.user_id = ?
						AND ( b.deleted = 0 AND d.deleted = 0 )

						';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict);

        $this->ExecuteSQL($query, $ph);

        //Debug::Arr($ph, 'Query: '. $query, __FILE__, __LINE__, __METHOD__, 10);
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

        $additional_order_fields = array('type_id', 'in_use');

        $sort_column_aliases = array(
            'type' => 'type_id',
        );

        $order = $this->getColumnsFromAliases($order, $sort_column_aliases);

        if ($order == null) {
            $order = array('type_id' => 'asc', 'name' => 'asc');
            $strict = false;
        } else {
            //Always try to order by type.
            if (!isset($order['type_id'])) {
                $order['type_id'] = 'asc';
            }
            //Always sort by name after other columns
            if (!isset($order['name'])) {
                $order['name'] = 'asc';
            }
            $strict = true;
        }
        //Debug::Arr($order, 'Order Data:', __FILE__, __LINE__, __METHOD__, 10);
        //Debug::Arr($filter_data, 'Filter Data:', __FILE__, __LINE__, __METHOD__, 10);

        $uf = new UserFactory();
        $pgf = new PolicyGroupFactory();
        $cgmf = new CompanyGenericMapFactory();

        $ph = array(
            'company_id' => (int)$company_id,
        );

        $query = '
					select	a.*,
							_ADODB_COUNT
							(
								CASE WHEN EXISTS ( select 1 from ' . $cgmf->getTable() . ' as w, ' . $pgf->getTable() . ' as v where w.company_id = a.company_id AND w.object_type_id = 180 AND w.map_id = a.id AND w.object_id = v.id AND v.deleted = 0 ) THEN 1 ELSE 0 END
							) as in_use,
							y.first_name as created_by_first_name,
							y.middle_name as created_by_middle_name,
							y.last_name as created_by_last_name,
							z.first_name as updated_by_first_name,
							z.middle_name as updated_by_middle_name,
							z.last_name as updated_by_last_name
							_ADODB_COUNT
					from	' . $this->getTable() . ' as a
						LEFT JOIN ' . $uf->getTable() . ' as y ON ( a.created_by = y.id AND y.deleted = 0 )
						LEFT JOIN ' . $uf->getTable() . ' as z ON ( a.updated_by = z.id AND z.deleted = 0 )
					where	a.company_id = ?
					';

        $query .= (isset($filter_data['permission_children_ids'])) ? $this->getWhereClauseSQL('a.created_by', $filter_data['permission_children_ids'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['id'])) ? $this->getWhereClauseSQL('a.id', $filter_data['id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['exclude_id'])) ? $this->getWhereClauseSQL('a.id', $filter_data['exclude_id'], 'not_numeric_list', $ph) : null;

        $query .= (isset($filter_data['type_id'])) ? $this->getWhereClauseSQL('a.type_id', $filter_data['type_id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['name'])) ? $this->getWhereClauseSQL('a.name', $filter_data['name'], 'text', $ph) : null;
        $query .= (isset($filter_data['description'])) ? $this->getWhereClauseSQL('a.description', $filter_data['description'], 'text', $ph) : null;

        $query .= (isset($filter_data['contributing_shift_policy_id'])) ? $this->getWhereClauseSQL('a.contributing_shift_policy_id', $filter_data['contributing_shift_policy_id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['eligible_contributing_shift_policy_id'])) ? $this->getWhereClauseSQL('a.eligible_contributing_shift_policy_id', $filter_data['eligible_contributing_shift_policy_id'], 'numeric_list', $ph) : null;

        $query .= (isset($filter_data['absence_policy'])) ? $this->getWhereClauseSQL('a.absence_policy_id', $filter_data['absence_policy'], 'numeric_list', $ph) : null;

        $query .= (isset($filter_data['created_by'])) ? $this->getWhereClauseSQL(array('a.created_by', 'y.first_name', 'y.last_name'), $filter_data['created_by'], 'user_id_or_name', $ph) : null;
        $query .= (isset($filter_data['updated_by'])) ? $this->getWhereClauseSQL(array('a.updated_by', 'z.first_name', 'z.last_name'), $filter_data['updated_by'], 'user_id_or_name', $ph) : null;

        $query .= ' AND a.deleted = 0 ';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict, $additional_order_fields);

        $this->ExecuteSQL($query, $ph, $limit, $page);

        return $this;
    }

    public function getByCompanyIdArray($company_id, $include_blank = true)
    {
        $hplf = new HolidayPolicyListFactory();
        $hplf->getByCompanyId($company_id);

        $list = array();
        if ($include_blank == true) {
            $list[0] = '--';
        }

        foreach ($hplf as $hp_obj) {
            $list[$hp_obj->getID()] = $hp_obj->getName();
        }

        if (empty($list) == false) {
            return $list;
        }

        return false;
    }

    public function getByCompanyId($id, $where = null, $order = null)
    {
        if ($id == '') {
            return false;
        }

        if ($order == null) {
            $order = array('a.name' => 'asc');
            $strict = false;
        } else {
            $strict = true;
        }

        $cgmf = new CompanyGenericMapFactory();

        $ph = array(
            'id' => (int)$id,
        );


        $query = '
					select	a.*,
							(select count(*) from ' . $cgmf->getTable() . ' as z where z.company_id = a.company_id AND z.object_type_id = 180 AND z.map_id = a.id) as assigned_policy_groups

					from	' . $this->getTable() . ' as a
					where	a.company_id = ?
						AND a.deleted = 0';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }
}
