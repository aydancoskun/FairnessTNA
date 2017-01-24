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
 * @package Modules\Company
 */
class CompanyDeductionListFactory extends CompanyDeductionFactory implements IteratorAggregate
{
    public function getAll($limit = null, $page = null, $where = null, $order = null)
    {
        $query = '
					select	*
					from	' . $this->getTable() . '
					WHERE deleted = 0
					ORDER BY calculation_order ASC';
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

        if (is_array($id)) {
            $this->rs = false;
        } else {
            $this->rs = $this->getCache($id);
        }

        if ($this->rs === false) {
            $ph = array();

            $query = '
						select	*
						from	' . $this->getTable() . '
						where	id in (' . $this->getListSQL($id, $ph, 'int') . ')
							AND deleted = 0';
            $query .= $this->getWhereSQL($where);
            $query .= $this->getSortSQL($order);

            $this->ExecuteSQL($query, $ph);

            if (!is_array($id)) {
                $this->saveCache($this->rs, $id);
            }
        }

        return $this;
    }

    public function getByCompanyId($company_id, $where = null, $order = null)
    {
        if ($company_id == '') {
            return false;
        }

        if ($order == null) {
            $order = array('status_id' => 'asc', 'calculation_order' => 'asc');
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
					where	company_id = ?
						AND deleted = 0
					';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByCompanyIdAndName($company_id, $name, $where = null, $order = null)
    {
        if ($company_id == '') {
            return false;
        }

        if ($name == '') {
            return false;
        }

        $ph = array(
            'company_id' => (int)$company_id,
            'name' => $name,
        );

        $query = '
					select	*
					from	' . $this->getTable() . '
					where	company_id = ?
						AND lower(name) LIKE lower(?)
						AND deleted = 0
					ORDER BY calculation_order ASC';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        //Debug::Text('Query: '. $query, __FILE__, __LINE__, __METHOD__, 10);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByIdAndCompanyId($ids, $company_id, $where = null, $order = null)
    {
        if ($company_id == '') {
            return false;
        }

        if ($ids == '') {
            return false;
        }

        $ph = array(
            'company_id' => (int)$company_id,
        );

        $query = '
					select	*
					from	' . $this->getTable() . '
					where	company_id = ?
						AND id in (' . $this->getListSQL($ids, $ph) . ')
						AND deleted = 0
					';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByCompanyIdAndId($company_id, $ids, $where = null, $order = null)
    {
        if ($company_id == '') {
            return false;
        }

        if ($ids == '') {
            return false;
        }

        $ph = array(
            'company_id' => (int)$company_id,
        );

        $query = '
					select	*
					from	' . $this->getTable() . '
					where	company_id = ?
						AND id in (' . $this->getListSQL($ids, $ph) . ')
						AND deleted = 0
					';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByTypeId($type_id, $where = null, $order = null)
    {
        if ($type_id == '') {
            return false;
        }

        $ph = array();

        $query = '
					select	*
					from	' . $this->getTable() . '
					where	type_id in (' . $this->getListSQL($type_id, $ph, 'int') . ')
						AND deleted = 0
					ORDER BY calculation_order ASC';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByCompanyIdAndTypeId($company_id, $type_id, $where = null, $order = null)
    {
        if ($company_id == '') {
            return false;
        }

        if ($type_id == '') {
            return false;
        }

        $ph = array(
            'company_id' => (int)$company_id,
        );

        $query = '
					select	*
					from	' . $this->getTable() . '
					where	company_id = ?
						AND type_id in (' . $this->getListSQL($type_id, $ph, 'int') . ')
						AND deleted = 0
					ORDER BY calculation_order ASC';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByCompanyIdAndStatusIdAndTypeId($company_id, $status_id, $type_id, $where = null, $order = null)
    {
        if ($company_id == '') {
            return false;
        }

        if ($status_id == '') {
            return false;
        }

        if ($type_id == '') {
            return false;
        }

        if ($order == null) {
            $order = array('calculation_order' => 'asc');
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
					where	company_id = ?
						AND status_id in (' . $this->getListSQL($status_id, $ph, 'int') . ')
						AND type_id in (' . $this->getListSQL($type_id, $ph, 'int') . ')
						AND deleted = 0';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByCompanyIdAndContributingPayCodePolicyId($company_id, $pay_code_id, $where = null, $order = null)
    {
        if ($company_id == '') {
            return false;
        }

        if ($pay_code_id == '') {
            return false;
        }

        $ph = array(
            'company_id' => (int)$company_id,
        );

        $query = '
					select	*
					from	' . $this->getTable() . '
					where	company_id = ?
						AND length_of_service_contributing_pay_code_policy_id in (' . $this->getListSQL($pay_code_id, $ph, 'int') . ')
						AND deleted = 0
					ORDER BY calculation_order ASC';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByCompanyIDAndUserIdAndCalculationIdAndPayStubEntryAccountID($company_id, $user_id, $calculation_id, $pse_account_id, $where = null, $order = null)
    {
        if ($company_id == '') {
            return false;
        }

        if ($user_id == '') {
            return false;
        }

        if ($calculation_id == '') {
            return false;
        }

        if ($pse_account_id == '') {
            return false;
        }

        $udf = new UserDeductionFactory();

        $ph = array(
            'company_id' => (int)$company_id,
            'user_id' => (int)$user_id,
            'calculation_id' => (int)$calculation_id,
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a,
							' . $udf->getTable() . ' as b
					where
						a.company_id = ?
						AND a.id = b.company_deduction_id
						AND b.user_id = ?
						AND a.calculation_id = ?
						AND a.pay_stub_entry_account_id in (' . $this->getListSQL($pse_account_id, $ph, 'int') . ')
						AND ( a.deleted = 0 AND b.deleted = 0 )
					ORDER BY calculation_order
					';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByIdArray($id, $include_blank = true)
    {
        if ($id == '') {
            return false;
        }

        $psenlf = new PayStubEntryNameListFactory();
        $psenlf->getById($id);

        $entry_name_list = array();
        if ($include_blank == true) {
            $entry_name_list[0] = '--';
        }

        $type_options = $this->getOptions('type');

        foreach ($psenlf as $entry_name) {
            $entry_name_list[$entry_name->getID()] = $type_options[$entry_name->getType()] . ' - ' . $entry_name->getDescription();
        }

        return $entry_name_list;
    }

    public function getByCompanyIdAndStatusIdArray($company_id, $status_id, $include_blank = true)
    {
        if ($status_id == '') {
            return false;
        }

        $cdlf = new CompanyDeductionListFactory();
        $cdlf->getByCompanyIdAndStatusId($company_id, $status_id);
        //$psenlf->getByTypeId($type_id);

        $list = array();
        if ($include_blank == true) {
            $list[0] = '--';
        }

        foreach ($cdlf as $obj) {
            $list[$obj->getID()] = $obj->getName();
        }

        return $list;
    }

    public function getByCompanyIdAndStatusId($company_id, $status_id, $where = null, $order = null)
    {
        if ($company_id == '') {
            return false;
        }

        if ($status_id == '') {
            return false;
        }

        $ph = array(
            'company_id' => (int)$company_id,
        );

        $query = '
					select	*
					from	' . $this->getTable() . '
					where	company_id = ?
						AND status_id in (' . $this->getListSQL($status_id, $ph, 'int') . ')
						AND deleted = 0
					ORDER BY calculation_order ASC';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getArrayByListFactory($lf, $include_blank = true, $sort_prefix = false)
    {
        if (!is_object($lf)) {
            return false;
        }

        $list = array();
        if ($include_blank == true) {
            $list[0] = '--';
        }

        foreach ($lf as $obj) {
            $list[$obj->getId()] = $obj->getName();
        }

        if (empty($list) == false) {
            return $list;
        }

        return false;
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

        $additional_order_fields = array('status_id');

        $sort_column_aliases = array(
            'status' => 'status_id',
            'type' => 'type_id',
            'calculation' => 'calculation_id',
        );

        $order = $this->getColumnsFromAliases($order, $sort_column_aliases);

        if ($order == null) {
            $order = array('status_id' => 'asc', 'type_id' => 'asc', 'name' => 'asc');
            $strict = false;
        } else {
            //Always try to order by status first so INACTIVE employees go to the bottom.
            if (!isset($order['status_id'])) {
                $order = Misc::prependArray(array('status_id' => 'asc'), $order);
            }
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
					where	a.company_id = ?
					';

        //$query .= ( isset($filter_data['permission_children_ids']) ) ? $this->getWhereClauseSQL( 'a.created_by', $filter_data['permission_children_ids'], 'numeric_list', $ph ) : NULL;
        if (isset($filter_data['permission_children_ids'])) {
            //Return rows that ONLY have this user assigned to them.
            $udf = new UserDeductionFactory();
            $query .= ' AND a.id IN ( select company_deduction_id from ' . $udf->getTable() . ' as udf where udf.user_id in (' . $this->getListSQL($filter_data['permission_children_ids'], $ph, 'int') . ') AND udf.deleted = 0 )';
        }

        $query .= (isset($filter_data['id'])) ? $this->getWhereClauseSQL('a.id', $filter_data['id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['exclude_id'])) ? $this->getWhereClauseSQL('a.id', $filter_data['exclude_id'], 'not_numeric_list', $ph) : null;

        if (isset($filter_data['include_user_id'])) {
            //Return rows that ONLY have this user assigned to them.
            $udf = new UserDeductionFactory();
            $query .= ' AND a.id IN ( select company_deduction_id from ' . $udf->getTable() . ' as udf where udf.user_id in (' . $this->getListSQL($filter_data['include_user_id'], $ph, 'int') . ') AND udf.deleted = 0 )';
        }

        if (isset($filter_data['exclude_user_id'])) {
            //Return rows that DO NOT have this user assigned to them.
            $udf = new UserDeductionFactory();
            $query .= ' AND a.id NOT IN ( select company_deduction_id from ' . $udf->getTable() . ' as udf where udf.user_id in (' . $this->getListSQL($filter_data['exclude_user_id'], $ph, 'int') . ') AND udf.deleted = 0 )';
        }

        if (isset($filter_data['status']) and !is_array($filter_data['status']) and trim($filter_data['status']) != '' and !isset($filter_data['status_id'])) {
            $filter_data['status_id'] = Option::getByFuzzyValue($filter_data['status'], $this->getOptions('status'));
        }
        $query .= (isset($filter_data['status_id'])) ? $this->getWhereClauseSQL('a.status_id', $filter_data['status_id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['type_id'])) ? $this->getWhereClauseSQL('a.type_id', $filter_data['type_id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['calculation_id'])) ? $this->getWhereClauseSQL('a.calculation_id', $filter_data['calculation_id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['pay_stub_entry_name_id'])) ? $this->getWhereClauseSQL('a.pay_stub_entry_account_id', $filter_data['pay_stub_entry_name_id'], 'numeric_list', $ph) : null;

        $query .= (isset($filter_data['name'])) ? $this->getWhereClauseSQL('a.name', $filter_data['name'], 'text', $ph) : null;
        $query .= (isset($filter_data['country'])) ? $this->getWhereClauseSQL('a.country', $filter_data['country'], 'upper_text_list', $ph) : null;
        $query .= (isset($filter_data['province'])) ? $this->getWhereClauseSQL('a.province', $filter_data['province'], 'upper_text_list', $ph) : null;

        $query .= (isset($filter_data['created_by'])) ? $this->getWhereClauseSQL(array('a.created_by', 'y.first_name', 'y.last_name'), $filter_data['created_by'], 'user_id_or_name', $ph) : null;
        $query .= (isset($filter_data['updated_by'])) ? $this->getWhereClauseSQL(array('a.updated_by', 'z.first_name', 'z.last_name'), $filter_data['updated_by'], 'user_id_or_name', $ph) : null;

        $query .= '
						AND a.deleted = 0
					';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict, $additional_order_fields);

        //Debug::Arr($ph, 'Query: '. $query, __FILE__, __LINE__, __METHOD__, 10);

        $this->ExecuteSQL($query, $ph, $limit, $page);

        return $this;
    }
}
