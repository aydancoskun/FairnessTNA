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
class AbsencePolicyListFactory extends AbsencePolicyFactory implements IteratorAggregate
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
            'company_id' => (int)$company_id
        );

        $query = '
					select	*
					from	' . $this->getTable() . '
					where 	company_id = ?
						AND id in (' . $this->getListSQL($id, $ph, 'int') . ')
						AND deleted = 0';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByCompanyIdAndPayCodeId($company_id, $id, $limit = null, $where = null, $order = null)
    {
        if ($id == '') {
            return false;
        }

        if ($company_id == '') {
            return false;
        }

        if ($order == null) {
            $order = array('name' => 'asc');
            $strict = false;
        } else {
            $strict = true;
        }

        $ph = array(
            'company_id' => (int)$company_id,
        );

        $query = '
					select	*
					from	' . $this->getTable() . '
					where
						company_id = ?
						AND pay_code_id in (' . $this->getListSQL($id, $ph, 'int') . ')
						AND deleted = 0';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict);

        $this->ExecuteSQL($query, $ph, $limit);

        return $this;
    }

    public function getByCompanyIdAndPayFormulaPolicyId($id, $pay_formula_policy_id, $where = null, $order = null)
    {
        if ($id == '') {
            return false;
        }

        if ($pay_formula_policy_id == '') {
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
						AND pay_formula_policy_id in (' . $this->getListSQL($pay_formula_policy_id, $ph, 'int') . ')
						AND deleted = 0';
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

        $additional_order_fields = array('type_id', 'in_use');

        $sort_column_aliases = array(
            'type' => 'type_id',
        );

        $order = $this->getColumnsFromAliases($order, $sort_column_aliases);

        if ($order == null) {
            $order = array('type_id' => 'asc', 'name' => 'asc');
            $strict = false;
        } else {
            //Always try to order by type
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
        $apf = new AccrualPolicyFactory();
        $cgmf = new CompanyGenericMapFactory();
        $pgf = new PolicyGroupFactory();
        $pguf = new PolicyGroupUserFactory();

        $ph = array(
            'company_id' => (int)$company_id,
        );

        $query = '
					select	DISTINCT a.*,
							apf.name as accrual_policy,
							(
								CASE WHEN EXISTS ( select 1 from ' . $cgmf->getTable() . ' as w, ' . $pgf->getTable() . ' as v where w.company_id = a.company_id AND w.object_type_id = 170 AND w.map_id = a.id AND w.object_id = v.id AND v.deleted = 0 ) THEN 1 ELSE 0 END
							) as in_use,
							y.first_name as created_by_first_name,
							y.middle_name as created_by_middle_name,
							y.last_name as created_by_last_name,
							z.first_name as updated_by_first_name,
							z.middle_name as updated_by_middle_name,
							z.last_name as updated_by_last_name
					from	' . $this->getTable() . ' as a
						LEFT JOIN ' . $cgmf->getTable() . ' as cgmf ON ( a.id = cgmf.map_id AND cgmf.object_type_id = 170 )
						LEFT JOIN ' . $pgf->getTable() . ' as pgf ON ( cgmf.object_id = pgf.id AND pgf.deleted = 0 )
						LEFT JOIN ' . $pguf->getTable() . ' as pguf ON ( pguf.policy_group_id = pgf.id )
						LEFT JOIN ' . $apf->getTable() . ' as apf ON ( a.accrual_policy_id = apf.id AND apf.deleted = 0 )
						LEFT JOIN ' . $uf->getTable() . ' as y ON ( a.created_by = y.id AND y.deleted = 0 )
						LEFT JOIN ' . $uf->getTable() . ' as z ON ( a.updated_by = z.id AND z.deleted = 0 )
					where	a.company_id = ?
					';

        $query .= (isset($filter_data['permission_children_ids'])) ? $this->getWhereClauseSQL('a.created_by', $filter_data['permission_children_ids'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['id'])) ? $this->getWhereClauseSQL('a.id', $filter_data['id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['exclude_id'])) ? $this->getWhereClauseSQL('a.id', $filter_data['exclude_id'], 'not_numeric_list', $ph) : null;

        $query .= (isset($filter_data['user_id'])) ? $this->getWhereClauseSQL('pguf.user_id', $filter_data['user_id'], 'numeric_list', $ph) : null;

        $query .= (isset($filter_data['name'])) ? $this->getWhereClauseSQL('a.name', $filter_data['name'], 'text', $ph) : null;
        $query .= (isset($filter_data['description'])) ? $this->getWhereClauseSQL('a.description', $filter_data['description'], 'text', $ph) : null;

        $query .= (isset($filter_data['pay_code_id'])) ? $this->getWhereClauseSQL('a.pay_code_id', $filter_data['pay_code_id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['pay_formula_policy_id'])) ? $this->getWhereClauseSQL('a.pay_formula_policy_id', $filter_data['pay_formula_policy_id'], 'numeric_list', $ph) : null;

        $query .= (isset($filter_data['created_by'])) ? $this->getWhereClauseSQL(array('a.created_by', 'y.first_name', 'y.last_name'), $filter_data['created_by'], 'user_id_or_name', $ph) : null;
        $query .= (isset($filter_data['updated_by'])) ? $this->getWhereClauseSQL(array('a.updated_by', 'z.first_name', 'z.last_name'), $filter_data['updated_by'], 'user_id_or_name', $ph) : null;

        $query .= '
						AND a.deleted = 0
					';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict, $additional_order_fields);
        $this->ExecuteSQL($query, $ph, $limit, $page);

        return $this;
    }

    public function getByCompanyIdArray($company_id, $include_blank = true)
    {
        $aplf = new AbsencePolicyListFactory();
        $aplf->getByCompanyId($company_id);

        $list = array();
        if ($include_blank == true) {
            $list[0] = '--';
        }

        foreach ($aplf as $ap_obj) {
            $list[$ap_obj->getID()] = $ap_obj->getName();
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
            $order = array('a.type_id' => 'asc', 'a.name' => 'asc');
            $strict = false;
        } else {
            $strict = true;
        }

        $ph = array(
            'company_id' => (int)$id,
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a
					where	a.company_id = ?
						AND a.deleted = 0';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getArrayByListFactory($lf, $include_blank = true)
    {
        if (!is_object($lf)) {
            return false;
        }

        $list = array();
        if ($include_blank == true) {
            $list[0] = '--';
        }

        foreach ($lf as $obj) {
            $list[$obj->getID()] = $obj->getName();
        }

        if (empty($list) == false) {
            return $list;
        }

        return false;
    }
}
