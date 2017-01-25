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
class RoundIntervalPolicyListFactory extends RoundIntervalPolicyFactory implements IteratorAggregate
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

        $this->rs = $this->getCache($id);
        if ($this->rs === false) {
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

            $this->saveCache($this->rs, $id);
        }

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
            'company_id' => (int)$company_id
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

    public function getByCompanyIdArray($company_id, $include_blank = true)
    {
        $riplf = new RoundIntervalPolicyListFactory();
        $riplf->getByCompanyId($company_id);

        $list = array();
        if ($include_blank == true) {
            $list[0] = '--';
        }

        foreach ($riplf as $rip_obj) {
            $list[$rip_obj->getID()] = $rip_obj->getName();
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
            $order = array('a.punch_type_id' => 'asc', 'a.name' => 'asc');
            $strict = false;
        } else {
            $strict = true;
        }

        $pgf = new PolicyGroupFactory();
        $cgmf = new CompanyGenericMapFactory();
        $hpf = new HolidayPolicyFactory();

        $ph = array(
            'id' => (int)$id,
        );


        $query = '
					select	a.*,
							(
								( select count(*) from ' . $cgmf->getTable() . ' as w, ' . $pgf->getTable() . ' as v where w.company_id = a.company_id AND w.object_type_id = 130 AND w.map_id = a.id AND w.object_id = v.id AND v.deleted = 0)+
								( select count(*) from ' . $hpf->getTable() . ' as z where z.round_interval_policy_id = a.id and z.deleted = 0 )
							) as assigned_policy_groups
					from	' . $this->getTable() . ' as a
					where	a.company_id = ?
						AND a.deleted = 0';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByPolicyGroupUserIdAndTypeId($user_id, $type_id, $where = null, $order = null)
    {
        if ($user_id == '') {
            return false;
        }

        if ($type_id == '') {
            return false;
        }

        if ($order == null) {
            //Always sort by puncy_type_id, then condition_type_id in the hopes that conditional punches apply first, then generic non-conditional apply after.
            //  This should allow cases where they want to apply some rounding only on conditions, then fall back to a global average 15min rounding outside of that.
            $order = array('d.punch_type_id' => 'desc', 'd.condition_type_id' => 'desc');
            $strict = false;
        } else {
            $strict = true;
        }

        $pgf = new PolicyGroupFactory();
        $pguf = new PolicyGroupUserFactory();
        $cgmf = new CompanyGenericMapFactory();
        $ripf = new RoundIntervalPolicyFactory();

        $punch_type_relation_options = $ripf->getOptions('punch_type_relation');
        if (isset($punch_type_relation_options[$type_id])) {
            $punch_type_ids = $punch_type_relation_options[$type_id];
            $punch_type_ids[] = $type_id;
        } else {
            return false;
        }

        $ph = array(
            'user_id' => (int)$user_id,
        );

        $query = '
					select	d.*
					from	' . $pguf->getTable() . ' as a,
							' . $pgf->getTable() . ' as b,
							' . $cgmf->getTable() . ' as c,
							' . $this->getTable() . ' as d
					where	a.policy_group_id = b.id
						AND ( b.id = c.object_id AND b.company_id = c.company_id AND c.object_type_id = 130 )
						AND c.map_id = d.id
						AND a.user_id = ?
						AND d.punch_type_id in ( ' . $this->getListSQL($punch_type_ids, $ph, 'int') . ')
						AND ( b.deleted = 0 AND d.deleted = 0 )
						';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict);
        //$query .= ' LIMIT 1'; //Don't limit to 1 now that we have conditional rounding.

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

        $additional_order_fields = array('punch_type_id', 'round_type_id', 'in_use');

        $sort_column_aliases = array(
            'punch_type' => 'punch_type_id',
            'round_type' => 'round_type_id',
        );

        $order = $this->getColumnsFromAliases($order, $sort_column_aliases);

        if ($order == null) {
            $order = array('punch_type_id' => 'asc', 'name' => 'asc');
            $strict = false;
        } else {
            //Always try to order by type first.
            if (!isset($order['punch_type_id'])) {
                $order['punch_type_id'] = 'asc';
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
        $hpf = new HolidayPolicyFactory();

        $ph = array(
            'company_id' => (int)$company_id,
        );

        $query = '
					select	a.*,
							_ADODB_COUNT
							(
								CASE WHEN EXISTS 
									( select 1 from ' . $cgmf->getTable() . ' as w, ' . $pgf->getTable() . ' as v where w.company_id = a.company_id AND w.object_type_id = 130 AND w.map_id = a.id AND w.object_id = v.id AND v.deleted = 0 ) 
									THEN 1 
									ELSE
										CASE WHEN EXISTS
											( select 1 from ' . $hpf->getTable() . ' as z2 where z2.round_interval_policy_id = a.id and z2.deleted = 0)
										THEN 1
										ELSE 0
										END
									END
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

        $query .= (isset($filter_data['name'])) ? $this->getWhereClauseSQL('a.name', $filter_data['name'], 'text', $ph) : null;

        $query .= (isset($filter_data['punch_type_id'])) ? $this->getWhereClauseSQL('a.punch_type_id', $filter_data['punch_type_id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['round_type_id'])) ? $this->getWhereClauseSQL('a.round_type_id', $filter_data['round_type_id'], 'numeric_list', $ph) : null;

        $query .= (isset($filter_data['created_by'])) ? $this->getWhereClauseSQL(array('a.created_by', 'y.first_name', 'y.last_name'), $filter_data['created_by'], 'user_id_or_name', $ph) : null;
        $query .= (isset($filter_data['updated_by'])) ? $this->getWhereClauseSQL(array('a.updated_by', 'z.first_name', 'z.last_name'), $filter_data['updated_by'], 'user_id_or_name', $ph) : null;

        $query .= ' AND a.deleted = 0 ';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict, $additional_order_fields);

        $this->ExecuteSQL($query, $ph, $limit, $page);

        return $this;
    }
}
