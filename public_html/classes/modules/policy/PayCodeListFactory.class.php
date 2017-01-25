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
class PayCodeListFactory extends PayCodeFactory implements IteratorAggregate
{
    public static function getPayCodeTypeMap($company_id)
    {
        $pclf = TTnew('PayCodeListFactory');
        $pclf->getByCompanyId($company_id);
        if ($pclf->getRecordCount() > 0) {
            $pay_codes = array();
            foreach ($pclf as $pc_obj) {
                //Collect PAID pay codes so we can create PAID TIME columns.
                if ($pc_obj->isPaid() == true) {
                    $pay_codes['paid'][] = $pc_obj->getID();
                }
            }

            if (empty($pay_codes) == false) {
                return $pay_codes;
            }
        }

        return false;
    }

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
						AND id in (' . $this->getListSQL($id, $ph, 'int') . ')
						AND deleted = 0';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict);

        $this->ExecuteSQL($query, $ph);

        return $this;
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

        $pgf = new PolicyGroupFactory();
        $cgmf = new CompanyGenericMapFactory();
        $spf = new SchedulePolicyFactory();

        $ph = array(
            'id' => (int)$id,
        );


        $query = '
					select	a.*,
							(
								( select count(*) from ' . $cgmf->getTable() . ' as w, ' . $pgf->getTable() . ' as v where w.company_id = a.company_id AND w.object_type_id = 110 AND w.map_id = a.id AND w.object_id = v.id AND v.deleted = 0)+
								( select count(*) from ' . $spf->getTable() . ' as z where z.over_time_policy_id = a.id and z.deleted = 0)
							) as assigned_policy_groups
					from	' . $this->getTable() . ' as a
					where	a.company_id = ?
						AND a.deleted = 0';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict);

        $this->ExecuteSQL($query, $ph);

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

        $additional_order_fields = array('in_use');

        $sort_column_aliases = array();

        $order = $this->getColumnsFromAliases($order, $sort_column_aliases);

        if ($order == null) {
            $order = array('name' => 'asc');
            $strict = false;
        } else {
            //Always sort by name after other columns
            if (!isset($order['name'])) {
                $order['name'] = 'asc';
            }
            $strict = true;
        }
        //Debug::Arr($order, 'Order Data:', __FILE__, __LINE__, __METHOD__, 10);
        //Debug::Arr($filter_data, 'Filter Data:', __FILE__, __LINE__, __METHOD__, 10);

        $uf = new UserFactory();
        $rtpf = new RegularTimePolicyFactory();
        $otpf = new OverTimePolicyFactory();
        $ppf = new PremiumPolicyFactory();
        $apf = new AbsencePolicyFactory();
        $mpf = new MealPolicyFactory();
        $bpf = new BreakPolicyFactory();
        $cgmf = new CompanyGenericMapFactory();
        $cppf = new ContributingPayCodePolicyFactory();

        $ph = array(
            'company_id' => (int)$company_id,
        );

        $query = '
					select	a.*,
							_ADODB_COUNT
							(
								CASE WHEN EXISTS
									( select 1 from ' . $rtpf->getTable() . ' as x where x.pay_code_id = a.id and x.deleted = 0)
								THEN 1
								ELSE
									CASE WHEN EXISTS
										( select 1 from ' . $otpf->getTable() . ' as x where x.pay_code_id = a.id and x.deleted = 0)
									THEN 1
									ELSE
										CASE WHEN EXISTS
											( select 1 from ' . $ppf->getTable() . ' as y where y.pay_code_id = a.id and y.deleted = 0)
										THEN 1
										ELSE
											CASE WHEN EXISTS
												( select 1 from ' . $apf->getTable() . ' as z where z.pay_code_id = a.id and z.deleted = 0)
											THEN 1
											ELSE
												CASE WHEN EXISTS
													( select 1 from ' . $cgmf->getTable() . ' as w, ' . $cppf->getTable() . ' as v where w.company_id = a.company_id AND w.object_type_id = 90 AND w.map_id = a.id AND w.object_id = v.id AND v.deleted = 0 )
												THEN 1
												ELSE
													CASE WHEN EXISTS
														( select 1 from ' . $mpf->getTable() . ' as x where x.pay_code_id = a.id and x.deleted = 0)
													THEN 1
													ELSE
														CASE WHEN EXISTS
															( select 1 from ' . $bpf->getTable() . ' as x where x.pay_code_id = a.id and x.deleted = 0)
														THEN 1
														ELSE 0
														END
													END
												END
											END
										END
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
        $query .= (isset($filter_data['type_id'])) ? $this->getWhereClauseSQL('a.type_id', $filter_data['type_id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['exclude_id'])) ? $this->getWhereClauseSQL('a.id', $filter_data['exclude_id'], 'not_numeric_list', $ph) : null;

        $query .= (isset($filter_data['name'])) ? $this->getWhereClauseSQL('a.name', $filter_data['name'], 'text', $ph) : null;
        $query .= (isset($filter_data['description'])) ? $this->getWhereClauseSQL('a.description', $filter_data['description'], 'text', $ph) : null;

        $query .= (isset($filter_data['pay_formula_policy_id'])) ? $this->getWhereClauseSQL('a.pay_formula_policy_id', $filter_data['pay_formula_policy_id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['pay_stub_entry_account_id'])) ? $this->getWhereClauseSQL('a.pay_stub_entry_account_id', $filter_data['pay_stub_entry_account_id'], 'numeric_list', $ph) : null;

        $query .= (isset($filter_data['created_by'])) ? $this->getWhereClauseSQL(array('a.created_by', 'y.first_name', 'y.last_name'), $filter_data['created_by'], 'user_id_or_name', $ph) : null;
        $query .= (isset($filter_data['updated_by'])) ? $this->getWhereClauseSQL(array('a.updated_by', 'z.first_name', 'z.last_name'), $filter_data['updated_by'], 'user_id_or_name', $ph) : null;

        $query .= ' AND a.deleted = 0 ';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict, $additional_order_fields);

        $this->ExecuteSQL($query, $ph, $limit, $page);

        return $this;
    }
}
