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
 * @package Modules\Holiday
 */
class HolidayListFactory extends HolidayFactory implements IteratorAggregate
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

    public function getByCompanyId($company_id, $where = null, $order = null)
    {
        if ($company_id == '') {
            return false;
        }

        $hpf = new HolidayPolicyFactory();

        $ph = array('company_id' => (int)$company_id,
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a
						LEFT JOIN ' . $hpf->getTable() . ' as b ON a.holiday_policy_id = b.id
					where	b.company_id = ?
						AND ( a.deleted = 0 AND b.deleted = 0 ) ';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByIDAndCompanyId($id, $company_id, $where = null, $order = null)
    {
        if ($company_id == '') {
            return false;
        }

        if ($id == '') {
            return false;
        }

        if ($order == null) {
            $order = array('date_stamp' => 'desc');
            $strict = false;
        } else {
            $strict = true;
        }

        $hpf = new HolidayPolicyFactory();

        $ph = array('company_id' => (int)$company_id,
            'id' => (int)$id
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a
						LEFT JOIN ' . $hpf->getTable() . ' as b ON a.holiday_policy_id = b.id
					where	b.company_id = ?
						AND a.id = ?
						AND ( a.deleted = 0 AND b.deleted = 0 ) ';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByIdAndHolidayPolicyID($id, $holiday_policy_id, $where = null, $order = null)
    {
        if ($id == '') {
            return false;
        }

        $ph = array(
            'id' => (int)$id,
            'holiday_policy_id' => (int)$holiday_policy_id,
        );

        $query = '
					select	*
					from	' . $this->getTable() . '
					where	id = ?
						AND holiday_policy_id = ?
						AND deleted = 0';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByHolidayPolicyId($id, $where = null, $order = null)
    {
        if ($id == '') {
            return false;
        }

        if ($order == null) {
            $order = array('date_stamp' => 'desc');
            $strict = false;
        } else {
            $strict = true;
        }

        $ph = array();

        $query = '
					select	*
					from	' . $this->getTable() . ' as a
					where	holiday_policy_id in (' . $this->getListSQL($id, $ph, 'int') . ')
						AND deleted = 0';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByHolidayPolicyIdAndStartDateAndEndDate($holiday_policy_id, $start_date, $end_date, $where = null, $order = null)
    {
        if ($holiday_policy_id == '') {
            return false;
        }

        if ($start_date == '') {
            return false;
        }

        if ($end_date == '') {
            return false;
        }

        if ($order == null) {
            $order = array('a.holiday_policy_id' => 'asc', 'a.date_stamp' => 'desc');
            $strict = false;
        } else {
            $strict = true;
        }

        $hpf = new HolidayPolicyFactory();

        $ph = array(
            'start_date' => $this->db->BindDate($start_date),
            'end_date' => $this->db->BindDate($end_date),
        );

        $query = '
					select	a.*
					from 	' . $this->getTable() . ' as a
					LEFT JOIN ' . $hpf->getTable() . ' as b ON ( a.holiday_policy_id = b.id )
					where
						a.date_stamp >= ?
						AND a.date_stamp <= ?
						AND b.id in (' . $this->getListSQL($holiday_policy_id, $ph, 'int') . ')
						AND ( a.deleted = 0 AND b.deleted=0 )
						';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict);

        $this->ExecuteSQL($query, $ph);

        //Debug::Arr($ph, 'Query: '. $query, __FILE__, __LINE__, __METHOD__, 10);

        return $this;
    }

    public function getByCompanyIdAndHolidayPolicyId($company_id, $id, $where = null, $order = null)
    {
        if ($company_id == '') {
            return false;
        }

        if ($id == '') {
            return false;
        }

        if ($order == null) {
            $order = array('date_stamp' => 'desc');
            $strict = false;
        } else {
            $strict = true;
        }

        $hpf = new HolidayPolicyFactory();

        $ph = array('company_id' => (int)$company_id,);

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a
						LEFT JOIN ' . $hpf->getTable() . ' as b ON a.holiday_policy_id = b.id
					where	b.company_id = ?
						AND a.holiday_policy_id in (' . $this->getListSQL($id, $ph, 'int') . ')
						AND ( a.deleted = 0 AND b.deleted = 0) ';
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
        $hpf = new HolidayPolicyFactory();
        $cgmf = new CompanyGenericMapFactory();


        $ph = array(
            'user_id' => (int)$user_id,
        );

        $query = '
					select	d.*
					from	' . $pguf->getTable() . ' as a,
							' . $pgf->getTable() . ' as b,
							' . $hpf->getTable() . ' as c,
							' . $cgmf->getTable() . ' as z,
							' . $this->getTable() . ' as d
					where	a.policy_group_id = b.id
						AND ( b.id = z.object_id AND z.company_id = b.company_id AND z.object_type_id = 180)
						AND z.map_id = d.holiday_policy_id
						AND d.holiday_policy_id = c.id
						AND a.user_id = ?
						AND ( c.deleted = 0 AND d.deleted = 0 AND b.deleted = 0 )
						';

        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByPolicyGroupUserIdAndDate($user_id, $date, $where = null, $order = null)
    {
        if ($user_id == '') {
            return false;
        }

        if ($date == '') {
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
        $hpf = new HolidayPolicyFactory();
        $cgmf = new CompanyGenericMapFactory();


        $ph = array(
            'user_id' => (int)$user_id,
            'date' => $this->db->BindDate($date),
        );

        $query = '
					select	d.*
					from	' . $pguf->getTable() . ' as a,
							' . $pgf->getTable() . ' as b,
							' . $hpf->getTable() . ' as c,
							' . $cgmf->getTable() . ' as z,
							' . $this->getTable() . ' as d
					where	a.policy_group_id = b.id
						AND ( b.id = z.object_id AND z.company_id = b.company_id AND z.object_type_id = 180)
						AND z.map_id = d.holiday_policy_id
						AND d.holiday_policy_id = c.id
						AND a.user_id = ?
						AND d.date_stamp = ?
						AND ( c.deleted = 0 AND d.deleted = 0 AND b.deleted = 0 )
						';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByCompanyIdAndStartDateAndEndDate($company_id, $start_date, $end_date, $where = null, $order = null)
    {
        if ($company_id == '') {
            return false;
        }

        if ($start_date == '') {
            return false;
        }

        if ($end_date == '') {
            return false;
        }

        if ($order == null) {
            $order = array('d.date_stamp' => 'desc');
            $strict = false;
        } else {
            $strict = true;
        }

        $pgf = new PolicyGroupFactory();
        $pguf = new PolicyGroupUserFactory();
        $hpf = new HolidayPolicyFactory();
        $cgmf = new CompanyGenericMapFactory();


        $ph = array(
            'company_id' => (int)$company_id,
            'start_date' => $this->db->BindDate($start_date),
            'end_date' => $this->db->BindDate($end_date),
        );

        //Query was: distinct(d.*) but MySQL doesnt like that.
        $query = '
					select	distinct d.*, a.policy_group_id
					from	' . $pguf->getTable() . ' as a,
							' . $pgf->getTable() . ' as b,
							' . $hpf->getTable() . ' as c,
							' . $cgmf->getTable() . ' as z,
							' . $this->getTable() . ' as d
					where	a.policy_group_id = b.id
						AND ( b.id = z.object_id AND z.company_id = b.company_id AND z.object_type_id = 180)
						AND z.map_id = d.holiday_policy_id
						AND d.holiday_policy_id = c.id
						AND b.company_id = ?
						AND d.date_stamp >= ?
						AND d.date_stamp <= ?
						AND ( c.deleted = 0 AND d.deleted=0 )
						';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getArrayByPolicyGroupUserId($user_id, $start_date, $end_date)
    {
        $hlf = new HolidayListFactory();
        $hlf->getByPolicyGroupUserIdAndStartDateAndEndDate($user_id, $start_date, $end_date);

        $list = array();
        if ($hlf->getRecordCount() > 0) {
            foreach ($hlf as $h_obj) {
                $list[$h_obj->getDateStamp()] = $h_obj->getName();
            }

            return $list;
        }

        return false;
    }

    public function getByPolicyGroupUserIdAndStartDateAndEndDate($user_id, $start_date, $end_date, $where = null, $order = null)
    {
        if ($user_id == '') {
            return false;
        }

        if ($start_date == '') {
            return false;
        }

        if ($end_date == '') {
            return false;
        }

        if ($order == null) {
            $order = array('d.date_stamp' => 'desc');
            $strict = false;
        } else {
            $strict = true;
        }

        $pgf = new PolicyGroupFactory();
        $pguf = new PolicyGroupUserFactory();
        $hpf = new HolidayPolicyFactory();
        $cgmf = new CompanyGenericMapFactory();


        $ph = array(
            'start_date' => $this->db->BindDate($start_date),
            'end_date' => $this->db->BindDate($end_date),
        );

        //Query was: distinct(d.*) but MySQL doesnt like that.
        $query = '
					select	distinct d.*
					from	' . $pguf->getTable() . ' as a,
							' . $pgf->getTable() . ' as b,
							' . $hpf->getTable() . ' as c,
							' . $cgmf->getTable() . ' as z,
							' . $this->getTable() . ' as d
					where	a.policy_group_id = b.id
						AND ( b.id = z.object_id AND z.company_id = b.company_id AND z.object_type_id = 180)
						AND z.map_id = d.holiday_policy_id
						AND d.holiday_policy_id = c.id
						AND d.date_stamp >= ?
						AND d.date_stamp <= ?
						AND a.user_id in (' . $this->getListSQL($user_id, $ph, 'int') . ')
						AND ( c.deleted = 0 AND d.deleted=0 )
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

        $additional_order_fields = array();

        $sort_column_aliases = array();

        $order = $this->getColumnsFromAliases($order, $sort_column_aliases);

        if ($order == null) {
            $order = array('date_stamp' => 'desc', 'name' => 'asc');
            $strict = false;
        } else {
            if (!isset($order['date_stamp'])) {
                $order = Misc::prependArray(array('date_stamp' => 'desc'), $order);
            }
            $strict = true;
        }
        //Debug::Arr($order, 'Order Data:', __FILE__, __LINE__, __METHOD__, 10);
        //Debug::Arr($filter_data, 'Filter Data:', __FILE__, __LINE__, __METHOD__, 10);

        $uf = new UserFactory();
        $pgf = new PolicyGroupFactory();
        $pguf = new PolicyGroupUserFactory();
        $hpf = new HolidayPolicyFactory();
        $cgmf = new CompanyGenericMapFactory();

        $ph = array(
            'company_id' => (int)$company_id,
        );

        $query = '
					select	distinct a.*,
							y.first_name as created_by_first_name,
							y.middle_name as created_by_middle_name,
							y.last_name as created_by_last_name,
							z.first_name as updated_by_first_name,
							z.middle_name as updated_by_middle_name,
							z.last_name as updated_by_last_name
					from	' . $this->getTable() . ' as a
						LEFT JOIN ' . $hpf->getTable() . ' as hpf ON ( a.holiday_policy_id = hpf.id AND hpf.deleted = 0 )
						LEFT JOIN ' . $cgmf->getTable() . ' as cgmf ON ( cgmf.company_id = hpf.company_id AND cgmf.object_type_id = 180 AND cgmf.map_id = a.holiday_policy_id )
						LEFT JOIN ' . $pgf->getTable() . ' as pgf ON ( pgf.id = cgmf.object_id AND pgf.deleted = 0 )
						LEFT JOIN ' . $pguf->getTable() . ' as pguf ON ( pguf.policy_group_id = pgf.id AND pgf.deleted = 0 )
						LEFT JOIN ' . $uf->getTable() . ' as y ON ( a.created_by = y.id AND y.deleted = 0 )
						LEFT JOIN ' . $uf->getTable() . ' as z ON ( a.updated_by = z.id AND z.deleted = 0 )
					where	hpf.company_id = ?
					';

        $query .= (isset($filter_data['permission_children_ids'])) ? $this->getWhereClauseSQL('a.created_by', $filter_data['permission_children_ids'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['id'])) ? $this->getWhereClauseSQL('a.id', $filter_data['id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['exclude_id'])) ? $this->getWhereClauseSQL('a.id', $filter_data['exclude_id'], 'not_numeric_list', $ph) : null;

        $query .= (isset($filter_data['holiday_policy_id'])) ? $this->getWhereClauseSQL('a.holiday_policy_id', $filter_data['holiday_policy_id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['user_id'])) ? $this->getWhereClauseSQL('pguf.user_id', $filter_data['user_id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['name'])) ? $this->getWhereClauseSQL('a.name', $filter_data['name'], 'text', $ph) : null;

        if (isset($filter_data['start_date']) and !is_array($filter_data['start_date']) and trim($filter_data['start_date']) != '') {
            $ph[] = $this->db->BindDate((int)$filter_data['start_date']);
            $query .= ' AND a.date_stamp >= ?';
        }
        if (isset($filter_data['end_date']) and !is_array($filter_data['end_date']) and trim($filter_data['end_date']) != '') {
            $ph[] = $this->db->BindDate((int)$filter_data['end_date']);
            $query .= ' AND a.date_stamp <= ?';
        }

        $query .= (isset($filter_data['created_by'])) ? $this->getWhereClauseSQL(array('a.created_by', 'y.first_name', 'y.last_name'), $filter_data['created_by'], 'user_id_or_name', $ph) : null;
        $query .= (isset($filter_data['updated_by'])) ? $this->getWhereClauseSQL(array('a.updated_by', 'z.first_name', 'z.last_name'), $filter_data['updated_by'], 'user_id_or_name', $ph) : null;

        $query .= ' AND a.deleted = 0 ';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict, $additional_order_fields);

        $this->ExecuteSQL($query, $ph, $limit, $page);

        return $this;
    }
}
