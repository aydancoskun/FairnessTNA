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
class UserDeductionListFactory extends UserDeductionFactory implements IteratorAggregate
{
    public function getAll($limit = null, $page = null, $where = null, $order = null)
    {
        $query = '
					select	*
					from	' . $this->getTable() . '
					WHERE deleted = 0
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

    public function getByIdAndCompanyId($id, $company_id, $order = null)
    {
        return $this->getByCompanyIdAndId($company_id, $id, $order);
    }

    public function getByCompanyIdAndId($company_id, $id, $where = null, $order = null)
    {
        if ($company_id == '') {
            return false;
        }

        if ($id == '') {
            return false;
        }

        $uf = new UserFactory();

        $ph = array(
            'company_id' => (int)$company_id,
            'id' => (int)$id,
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a,
							' . $uf->getTable() . ' as b
					where
						a.user_id = b.id
						AND b.company_id = ?
						AND a.id = ?
						AND a.deleted = 0
					';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByCompanyDeductionId($deduction_id, $where = null, $order = null)
    {
        if ($deduction_id == '') {
            return false;
        }

        $uf = new UserFactory();

        $ph = array(
            'deduction_id' => (int)$deduction_id,
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a,
							' . $uf->getTable() . ' as b
					where
						a.user_id = b.id
						AND a.company_deduction_id = ?
						AND ( a.deleted = 0 AND b.deleted = 0 )
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

        $uf = new UserFactory();
        $cdf = new CompanyDeductionFactory();

        $ph = array(
            'company_id' => (int)$company_id,
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a,
							' . $uf->getTable() . ' as b,
							' . $cdf->getTable() . ' as c
					where
						a.user_id = b.id
						AND a.company_deduction_id = c.id
						AND b.company_id = ?
						AND a.deleted = 0
					ORDER BY c.calculation_order
					';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByCompanyIdAndCompanyDeductionId($company_id, $deduction_id, $where = null, $order = null)
    {
        if ($company_id == '') {
            return false;
        }

        if ($deduction_id == '') {
            return false;
        }

        $uf = new UserFactory();

        $ph = array(
            'company_id' => (int)$company_id,
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a,
							' . $uf->getTable() . ' as b
					where
						a.user_id = b.id
						AND b.company_id = ?
						AND a.company_deduction_id in (' . $this->getListSQL($deduction_id, $ph, 'int') . ')
						AND ( a.deleted = 0 AND b.deleted = 0 )
					';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByUserIdAndCompanyDeductionId($user_id, $deduction_id, $where = null, $order = null)
    {
        if ($user_id == '') {
            return false;
        }

        if ($deduction_id == '') {
            return false;
        }

        $uf = new UserFactory();

        $ph = array(
            'deduction_id' => (int)$deduction_id,
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a,
							' . $uf->getTable() . ' as b
					where
						a.user_id = b.id
						AND a.company_deduction_id = ?
						AND a.user_id in (' . $this->getListSQL($user_id, $ph, 'int') . ')
						AND (a.deleted = 0 AND b.deleted = 0)
					';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByUserIdAndCountryID($user_id, $country_id, $where = null, $order = null)
    {
        if ($user_id == '') {
            return false;
        }

        if ($country_id == '') {
            return false;
        }

        //$uf = new UserFactory();
        $cdf = new CompanyDeductionFactory();

        $ph = array(
            'user_id' => (int)$user_id,
            'country_id' => (int)$country_id,
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a,
							' . $cdf->getTable() . ' as b
					where
						a.company_deduction_id = b.id
						AND a.user_id = ?
						AND b.country = ?
						AND ( a.deleted = 0 AND b.deleted = 0 )
					';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByUserIdAndPayStubEntryAccountID($user_id, $pse_account_id, $where = null, $order = null)
    {
        if ($user_id == '') {
            return false;
        }

        if ($pse_account_id == '') {
            return false;
        }

        //$uf = new UserFactory();
        $cdf = new CompanyDeductionFactory();

        $ph = array(
            'user_id' => (int)$user_id,
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a,
							' . $cdf->getTable() . ' as b
					where
						a.company_deduction_id = b.id
						AND a.user_id = ?
						AND b.pay_stub_entry_account_id in (' . $this->getListSQL($pse_account_id, $ph, 'int') . ')
						AND ( a.deleted = 0 AND b.deleted = 0 )
					';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByCompanyIdAndUserId($company_id, $user_id, $where = null, $order = null)
    {
        if ($company_id == '') {
            return false;
        }

        if ($user_id == '') {
            return false;
        }

        if ($order == null) {
            $order = array('cdf.status_id' => 'asc', 'cdf.calculation_order' => 'asc', 'cdf.id' => 'asc');
            $strict = false;
        } else {
            $strict = true;
        }

        $uf = new UserFactory();
        $cdf = new CompanyDeductionFactory();

        $ph = array(
            'company_id' => (int)$company_id,
        );

        $query = '
					SELECT	a.id,
							a.user_id,
							a.company_deduction_id,

							CASE WHEN a.length_of_service_date IS NULL THEN ' . $this->getSQLToTimeStampFunction() . '( uf.hire_date ) ELSE a.length_of_service_date END as length_of_service_date,
							CASE WHEN a.start_date IS NULL THEN cdf.start_date ELSE a.start_date END as start_date,
							CASE WHEN a.end_date IS NULL THEN cdf.end_date ELSE a.end_date END as end_date,

							a.user_value1,
							a.user_value2,
							a.user_value3,
							a.user_value4,
							a.user_value5,
							a.user_value6,
							a.user_value7,
							a.user_value8,
							a.user_value9,
							a.user_value10,

							a.created_date as created_date,
							a.created_by as created_by,
							a.updated_date as updated_date,
							a.updated_by as updated_by,
							a.deleted_date as deleted_date,
							a.deleted_by as deleted_by,
							a.deleted as deleted
					FROM	' . $this->getTable() . ' as a
					LEFT JOIN ' . $uf->getTable() . ' as uf ON ( a.user_id = uf.id AND uf.deleted = 0 )
					LEFT JOIN ' . $cdf->getTable() . ' as cdf ON ( a.company_deduction_id = cdf.id AND cdf.deleted = 0 )
					WHERE
						a.user_id = uf.id
						AND a.company_deduction_id = cdf.id
						AND uf.company_id = ?
						AND a.user_id in (' . $this->getListSQL($user_id, $ph, 'int') . ')
						AND (a.deleted = 0 AND cdf.deleted = 0)
					';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByCompanyIdAndUserIdAndId($company_id, $user_id, $id, $where = null, $order = null)
    {
        if ($company_id == '') {
            return false;
        }

        if ($user_id == '') {
            return false;
        }

        if ($id == '') {
            return false;
        }

        $uf = new UserFactory();
        $cdf = new CompanyDeductionFactory();

        $ph = array(
            'company_id' => (int)$company_id,
            'user_id' => (int)$user_id,
            'id' => (int)$id,
        );

        $query = '
					SELECT	a.id,
							a.user_id,
							a.company_deduction_id,

							CASE WHEN a.length_of_service_date IS NULL THEN ' . $this->getSQLToTimeStampFunction() . '( uf.hire_date ) ELSE a.length_of_service_date END as length_of_service_date,
							CASE WHEN a.start_date IS NULL THEN cdf.start_date ELSE a.start_date END as start_date,
							CASE WHEN a.end_date IS NULL THEN cdf.end_date ELSE a.end_date END as end_date,

							a.user_value1,
							a.user_value2,
							a.user_value3,
							a.user_value4,
							a.user_value5,
							a.user_value6,
							a.user_value7,
							a.user_value8,
							a.user_value9,
							a.user_value10,

							a.created_date as created_date,
							a.created_by as created_by,
							a.updated_date as updated_date,
							a.updated_by as updated_by,
							a.deleted_date as deleted_date,
							a.deleted_by as deleted_by,
							a.deleted as deleted
					FROM	' . $this->getTable() . ' as a
					LEFT JOIN ' . $uf->getTable() . ' as uf ON ( a.user_id = uf.id AND uf.deleted = 0 )
					LEFT JOIN ' . $cdf->getTable() . ' as cdf ON ( a.company_deduction_id = cdf.id AND cdf.deleted = 0 )
					WHERE
						uf.company_id = ?
						AND a.user_id = ?
						AND a.id = ?
						AND a.deleted = 0
					';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

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

        $additional_order_fields = array();
        if ($order == null) {
            $order = array('uf.status_id' => 'asc', 'uf.last_name' => 'asc', 'uf.first_name' => 'asc');
            $strict = false;
        } else {
            //Always sort by status, last name, first name after other columns
            if (!isset($order['uf.status_id'])) {
                $order['uf.status_id'] = 'asc';
            }
            if (!isset($order['uf.last_name'])) {
                $order['uf.last_name'] = 'asc';
            }
            if (!isset($order['uf.first_name'])) {
                $order['uf.first_name'] = 'asc';
            }
            $strict = true;
        }
        //Debug::Arr($order, 'Order Data:', __FILE__, __LINE__, __METHOD__, 10);
        //Debug::Arr($filter_data, 'Filter Data:', __FILE__, __LINE__, __METHOD__, 10);

        $uf = new UserFactory();
        $cdf = new CompanyDeductionFactory();

        $ph = array(
            'company_id' => (int)$company_id,
        );

        $query = '
					select	a.id,
							a.user_id,
							a.company_deduction_id,

							CASE WHEN a.length_of_service_date IS NULL THEN ' . $this->getSQLToTimeStampFunction() . '( uf.hire_date ) ELSE a.length_of_service_date END as length_of_service_date,
							CASE WHEN a.start_date IS NULL THEN cdf.start_date ELSE a.start_date END as start_date,
							CASE WHEN a.end_date IS NULL THEN cdf.end_date ELSE a.end_date END as end_date,

							a.user_value1,
							a.user_value2,
							a.user_value3,
							a.user_value4,
							a.user_value5,
							a.user_value6,
							a.user_value7,
							a.user_value8,
							a.user_value9,
							a.user_value10,

							uf.first_name as first_name,
							uf.last_name as last_name,
							uf.country as country,
							uf.province as province,
							
							cdf.name as name,
							cdf.status_id as status_id,
							cdf.type_id as type_id,
							cdf.calculation_id as calculation_id,

							a.created_date as created_date,
							a.created_by as created_by,
							a.updated_date as updated_date,
							a.updated_by as updated_by,
							a.deleted_date as deleted_date,
							a.deleted_by as deleted_by,
							a.deleted as deleted,

							y.first_name as created_by_first_name,
							y.middle_name as created_by_middle_name,
							y.last_name as created_by_last_name,
							z.first_name as updated_by_first_name,
							z.middle_name as updated_by_middle_name,
							z.last_name as updated_by_last_name
					from	' . $this->getTable() . ' as a
						LEFT JOIN ' . $uf->getTable() . ' as uf ON ( a.user_id = uf.id AND uf.deleted = 0 )
						LEFT JOIN ' . $cdf->getTable() . ' as cdf ON ( a.company_deduction_id = cdf.id AND cdf.deleted = 0 )
						LEFT JOIN ' . $uf->getTable() . ' as y ON ( a.created_by = y.id AND y.deleted = 0 )
						LEFT JOIN ' . $uf->getTable() . ' as z ON ( a.updated_by = z.id AND z.deleted = 0 )
					where	uf.company_id = ?
					';

        $query .= (isset($filter_data['permission_children_ids'])) ? $this->getWhereClauseSQL('a.user_id', $filter_data['permission_children_ids'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['id'])) ? $this->getWhereClauseSQL('a.id', $filter_data['id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['exclude_id'])) ? $this->getWhereClauseSQL('a.id', $filter_data['exclude_id'], 'not_numeric_list', $ph) : null;

        $query .= (isset($filter_data['status_id'])) ? $this->getWhereClauseSQL('cdf.status_id', $filter_data['status_id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['type_id'])) ? $this->getWhereClauseSQL('cdf.type_id', $filter_data['type_id'], 'numeric_list', $ph) : null;

        $query .= (isset($filter_data['user_id'])) ? $this->getWhereClauseSQL('a.user_id', $filter_data['user_id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['company_deduction_id'])) ? $this->getWhereClauseSQL('a.company_deduction_id', $filter_data['company_deduction_id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['calculation_id'])) ? $this->getWhereClauseSQL('cdf.calculation_id', $filter_data['calculation_id'], 'numeric_list', $ph) : null;

        $query .= (isset($filter_data['created_by'])) ? $this->getWhereClauseSQL(array('a.created_by', 'y.first_name', 'y.last_name'), $filter_data['created_by'], 'user_id_or_name', $ph) : null;
        $query .= (isset($filter_data['updated_by'])) ? $this->getWhereClauseSQL(array('a.updated_by', 'z.first_name', 'z.last_name'), $filter_data['updated_by'], 'user_id_or_name', $ph) : null;

        $query .= '
						AND a.deleted = 0
					';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict, $additional_order_fields);

        $this->ExecuteSQL($query, $ph, $limit, $page);

        //Debug::Arr($ph, 'Query: '. $query, __FILE__, __LINE__, __METHOD__, 10);

        return $this;
    }
}
