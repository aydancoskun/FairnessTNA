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
 * @package Modules\Other
 */
class ROEListFactory extends ROEFactory implements IteratorAggregate
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

        $ph = array();

        $query = '
					select	*
					from	' . $this->getTable() . '
					where	id in (' . $this->getListSQL($id, $ph, 'int') . ')
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

        $uf = new UserFactory();

        $ph = array(
            'company_id' => (int)$company_id
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a,
							' . $uf->getTable() . ' as b
					where	a.user_id = b.id
						AND b.company_id = ?
						AND a.deleted = 0 AND b.deleted = 0';
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

        $uf = new UserFactory();

        $ph = array(
            'company_id' => (int)$company_id
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a,
							' . $uf->getTable() . ' as b
					where	a.user_id = b.id
						AND b.company_id = ?
						AND a.id in (' . $this->getListSQL($id, $ph, 'int') . ')
						AND a.deleted = 0 AND b.deleted = 0';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);
        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByUserId($id, $limit = null, $page = null, $where = null, $order = null)
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
					where	user_id = ?
						AND deleted = 0';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph, $limit, $page);

        return $this;
    }

    public function getLastROEByUserId($id, $limit = null, $page = null, $where = null, $order = null)
    {
        if ($id == '') {
            return false;
        }

        if ($order == null) {
            $order = array('last_date' => 'desc');
            $strict = false;
        } else {
            $strict = true;
        }

        if ($limit == null) {
            $limit = 1;
        }

        $ph = array(
            'id' => (int)$id,
        );


        $query = '
					select	*
					from	' . $this->getTable() . '
					where	user_id = ?
						AND deleted = 0';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict);

        $this->ExecuteSQL($query, $ph, $limit, $page);

        return $this;
    }

    public function getByUserIdAndStartDateAndEndDate($id, $start_date, $end_date, $where = null, $order = null)
    {
        if ($id == '') {
            return false;
        }

        if ($start_date == '') {
            return false;
        }

        if ($end_date == '') {
            return false;
        }


        $ph = array(
            'id' => (int)$id,
            'start_date' => $start_date,
            'end_date' => $end_date,
        );

        $query = '
					select	*
					from	' . $this->getTable() . '
					where	user_id = ?
						AND last_date >= ?
						AND last_date <= ?
						AND deleted = 0';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getIsModifiedByUserIdAndStartDateAndEndDateAndDate($user_id, $start_date, $end_date, $date, $where = null, $order = null)
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

        if ($date == '') {
            return false;
        }

        $ph = array(
            'user_id' => (int)$user_id,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'created_date' => $date,
            'updated_date' => $date,
        );

        //INCLUDE Deleted rows in this query.
        $query = '
					select	*
					from	' . $this->getTable() . '
					where
							user_id = ?
						AND last_date >= ?
						AND last_date <= ?
						AND
							( created_date >= ? OR updated_date >= ? )
					LIMIT 1
					';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);
        if ($this->getRecordCount() > 0) {
            Debug::text('ROE rows have been modified: ' . $this->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);

            return true;
        }
        Debug::text('ROE rows have NOT been modified', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function getAPISearchByCompanyIdAndArrayCriteria($company_id, $filter_data, $limit = null, $page = null, $where = null, $order = null)
    {
        if ($company_id == '') {
            return false;
        }
        if (isset($filter_data['roe_id'])) {
            $filter_data['id'] = $filter_data['roe_id'];
            unset($filter_data['roe_id']);
        }
        if (!is_array($order)) {
            //Use Filter Data ordering if its set.
            if (isset($filter_data['sort_column']) and $filter_data['sort_order']) {
                $order = array(Misc::trimSortPrefix($filter_data['sort_column']) => $filter_data['sort_order']);
            }
        }

        $additional_order_fields = array('uf.first_name', 'uf.last_name', 'code_id', 'pay_period_type_id');

        $sort_column_aliases = array(
            'code' => 'code_id',
            'pay_period_type' => 'pay_period_type_id',
        );

        $order = $this->getColumnsFromAliases($order, $sort_column_aliases);
        if ($order == null) {
            $order = array('last_date' => 'desc'); //Order ROEs by last date for which paid.
            $strict = false;
        } else {
            if (isset($order['first_name'])) {
                $order['uf.first_name'] = $order['first_name'];
                unset($order['first_name']);
            }
            if (isset($order['last_name'])) {
                $order['uf.last_name'] = $order['last_name'];
                unset($order['last_name']);
            }
            $strict = true;
        }
        //Debug::Arr($order, 'Order Data:', __FILE__, __LINE__, __METHOD__, 10);
        //Debug::Arr($filter_data, 'Filter Data:', __FILE__, __LINE__, __METHOD__, 10);

        $uf = new UserFactory();
        //$ppsf = new PayPeriodScheduleFactory();

        $ph = array(
            'company_id' => (int)$company_id,
        );

        $query = '
					select	a.*,
							uf.first_name as first_name,
							uf.last_name as last_name,
							y.first_name as created_by_first_name,
							y.middle_name as created_by_middle_name,
							y.last_name as created_by_last_name,
							z.first_name as updated_by_first_name,
							z.middle_name as updated_by_middle_name,
							z.last_name as updated_by_last_name
					from	' . $this->getTable() . ' as a
						LEFT JOIN ' . $uf->getTable() . ' as uf ON ( a.user_id = uf.id AND uf.deleted = 0 )
						LEFT JOIN ' . $uf->getTable() . ' as y ON ( a.created_by = y.id AND y.deleted = 0 )
						LEFT JOIN ' . $uf->getTable() . ' as z ON ( a.updated_by = z.id AND z.deleted = 0 )
					where	uf.company_id = ?';

        $query .= (isset($filter_data['permission_children_ids'])) ? $this->getWhereClauseSQL('a.user_id', $filter_data['permission_children_ids'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['id'])) ? $this->getWhereClauseSQL('a.id', $filter_data['id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['exclude_id'])) ? $this->getWhereClauseSQL('a.user_id', $filter_data['exclude_id'], 'not_numeric_list', $ph) : null;

        if (isset($filter_data['code']) and !is_array($filter_data['code']) and trim($filter_data['code']) != '' and !isset($filter_data['code_id'])) {
            $filter_data['code_id'] = Option::getByFuzzyValue($filter_data['code'], $this->getOptions('code'));
        }
        $query .= (isset($filter_data['first_name'])) ? $this->getWhereClauseSQL('uf.first_name', $filter_data['first_name'], 'text_metaphone', $ph) : null;
        $query .= (isset($filter_data['last_name'])) ? $this->getWhereClauseSQL('uf.last_name', $filter_data['last_name'], 'text_metaphone', $ph) : null;

        $query .= (isset($filter_data['user_id'])) ? $this->getWhereClauseSQL('a.user_id', $filter_data['user_id'], 'numeric_list', $ph) : null;

        $query .= (isset($filter_data['code_id'])) ? $this->getWhereClauseSQL('a.code_id', $filter_data['code_id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['pay_period_type_id'])) ? $this->getWhereClauseSQL('a.pay_period_type_id', $filter_data['pay_period_type_id'], 'numeric_list', $ph) : null;

        $query .= (isset($filter_data['first_date'])) ? $this->getWhereClauseSQL('a.first_date', $filter_data['first_date'], 'date_range', $ph) : null;

        $query .= (isset($filter_data['last_date'])) ? $this->getWhereClauseSQL('a.last_date', $filter_data['last_date'], 'date_range', $ph) : null;
        $query .= (isset($filter_data['pay_period_end_date'])) ? $this->getWhereClauseSQL('a.pay_period_end_date', $filter_data['pay_period_end_date'], 'date_range', $ph) : null;
        $query .= (isset($filter_data['recall_date'])) ? $this->getWhereClauseSQL('a.recall_date', $filter_data['recall_date'], 'date_range', $ph) : null;

        $query .= (isset($filter_data['serial'])) ? $this->getWhereClauseSQL('a.serial', $filter_data['serial'], 'text', $ph) : null;
        $query .= (isset($filter_data['comments'])) ? $this->getWhereClauseSQL('a.comments', $filter_data['comments'], 'text', $ph) : null;

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
