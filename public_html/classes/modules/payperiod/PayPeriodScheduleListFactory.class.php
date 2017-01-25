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
 * @package Modules\PayPeriod
 */
class PayPeriodScheduleListFactory extends PayPeriodScheduleFactory implements IteratorAggregate
{
    public function getAll($limit = null, $page = null, $where = null, $order = null)
    {
        $query = '
					select	*
					from	' . $this->getTable() . '
						WHERE deleted=0';
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
							AND deleted=0';
            $query .= $this->getWhereSQL($where);
            $query .= $this->getSortSQL($order);

            $this->ExecuteSQL($query, $ph);

            $this->saveCache($this->rs, $id);
        }

        return $this;
    }

    public function getByUserId($user_id, $where = null, $order = null)
    {
        if ($user_id == '') {
            return false;
        }

        $ppsulf = new PayPeriodScheduleUserListFactory();

        $ph = array(
            'user_id' => (int)$user_id,
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a,
							' . $ppsulf->getTable() . ' as b
					where	a.id = b.pay_period_schedule_id
						AND b.user_id = ?
						AND a.deleted=0';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByCompanyIdAndUserId($company_id, $user_ids, $where = null, $order = null)
    {
        if ($company_id == '') {
            return false;
        }

        if ($user_ids == '') {
            return false;
        }
        /*
                if ( $order == NULL ) {
                    $order = array( 'type_id' => 'asc' );
                    $strict = FALSE;
                } else {
                    $strict = TRUE;
                }
        */
        $ppsuf = new PayPeriodScheduleUserFactory();

        $ph = array('company_id' => (int)$company_id,);

        $query = '
					select	a.*,
							b.user_id as user_id
					from	' . $this->getTable() . ' as a,
							' . $ppsuf->getTable() . ' as b
					where	a.id = b.pay_period_schedule_id
						AND a.company_id = ? ';

        if (isset($user_ids) and ($user_ids != '' or (is_array($user_ids) and isset($user_ids[0])))) {
            $query .= ' AND b.user_id in (' . $this->getListSQL($user_ids, $ph, 'int') . ') ';
        }

        $query .= '	AND a.deleted = 0';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByIdAndCompanyId($id, $company_id, $order = null)
    {
        if ($id == '') {
            return false;
        }

        if ($company_id == '') {
            return false;
        }

        $ph = array(
            'company_id' => (int)$company_id,
            'id' => (int)$id,
        );

        $query = '
					select	*
					from	' . $this->getTable() . '
					where	company_id = ?
						AND	id = ?
						AND deleted=0';
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByPayPeriodIdAndCompanyId($id, $company_id, $limit = null, $page = null, $where = null, $order = null)
    {
        if ($id == '') {
            return false;
        }

        if ($company_id == '') {
            return false;
        }

        $ph = array(
            'company_id' => (int)$company_id,
        );

        $ppf = new PayPeriodFactory();

        $query = '
					select	_ADODB_COUNT
						*
						_ADODB_COUNT
					from	' . $this->getTable() . '
					where	company_id = ?
						AND	id in ( select ppf.pay_period_schedule_id from ' . $ppf->getTable() . ' as ppf where ppf.id in (' . $this->getListSQL($id, $ph, 'int') . ') )
						AND deleted=0';
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph, $limit, $page);

        return $this;
    }

    public function getByCompanyIdArray($company_id, $include_blank = true)
    {
        $ppslf = new PayPeriodScheduleListFactory();
        $ppslf->getByCompanyId($company_id);

        $list = array();
        if ($include_blank == true) {
            $list[0] = '--';
        }

        foreach ($ppslf as $pps_obj) {
            $list[$pps_obj->getID()] = $pps_obj->getName();
        }

        if (empty($list) == false) {
            return $list;
        }

        return false;
    }

    public function getByCompanyId($id, $limit = null, $page = null, $where = null, $order = null)
    {
        if ($id == '') {
            return false;
        }

        $ph = array(
            'id' => (int)$id,
        );


        $query = '
					select	*
					from	' . $this->getTable() . ' as a
					where	company_id = ?
						AND deleted=0';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph, $limit, $page);

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

    public function getUserToPayPeriodScheduleMapArrayByListFactory($lf)
    {
        if (!is_object($lf)) {
            return false;
        }

        $retarr = array();
        foreach ($lf as $obj) {
            $retarr[$obj->getColumn('user_id')] = $obj->getId();
        }

        if (empty($retarr) == false) {
            return $retarr;
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

        $additional_order_fields = array('total_users');

        if ($order == null) {
            $order = array('name' => 'asc');
            $strict = false;
        } else {
            //Always sort by last name, first name after other columns
            if (!isset($order['name'])) {
                $order['name'] = 'asc';
            }
            $strict = true;
        }
        //Debug::Arr($order, 'Order Data:', __FILE__, __LINE__, __METHOD__, 10);
        //Debug::Arr($filter_data, 'Filter Data:', __FILE__, __LINE__, __METHOD__, 10);

        $uf = new UserFactory();
        $ppsuf = new PayPeriodScheduleUserFactory();

        $ph = array(
            'company_id' => (int)$company_id,
        );

        $query = '
					select
							_ADODB_COUNT
							a.*,
							(select count(*) from ' . $ppsuf->getTable() . ' as ppsuf_tmp where ppsuf_tmp.pay_period_schedule_id = a.id ) as total_users,
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

        if (isset($filter_data['type']) and !is_array($filter_data['type']) and trim($filter_data['type']) != '' and !isset($filter_data['type_id'])) {
            $filter_data['type_id'] = Option::getByFuzzyValue($filter_data['type'], $this->getOptions('type'));
        }
        $query .= (isset($filter_data['type_id'])) ? $this->getWhereClauseSQL('a.type_id', $filter_data['type_id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['name'])) ? $this->getWhereClauseSQL('a.name', $filter_data['name'], 'text', $ph) : null;
        $query .= (isset($filter_data['description'])) ? $this->getWhereClauseSQL('a.description', $filter_data['description'], 'text', $ph) : null;

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
}
