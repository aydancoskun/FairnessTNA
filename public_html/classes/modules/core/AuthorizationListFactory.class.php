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
 * @package Core
 */
class AuthorizationListFactory extends AuthorizationFactory implements IteratorAggregate
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
					from	' . $this->table . '
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

        $uf = new UserFactory();

        $ph = array(
            'company_id' => (int)$company_id,
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a
					LEFT JOIN ' . $uf->getTable() . ' as uf ON (a.created_by = uf.id )
					where	uf.company_id = ?
						AND ( a.deleted = 0 AND uf.deleted = 0 )';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByIdAndCompanyId($id, $company_id, $limit = null, $page = null, $where = null, $order = null)
    {
        if ($id == '') {
            return false;
        }

        if ($company_id == '') {
            return false;
        }

        if ($order == null) {
            $order = array('created_date' => 'asc');
            $strict = false;
        } else {
            $strict = true;
        }

        $uf = new UserFactory();

        $ph = array(
            'company_id' => (int)$company_id,
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a
					LEFT JOIN ' . $uf->getTable() . ' as uf ON (a.created_by = uf.id )
					where	uf.company_id = ?
						AND	a.id in (' . $this->getListSQL($id, $ph, 'int') . ')
						AND ( a.deleted = 0 AND uf.deleted = 0 )';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict);

        $this->ExecuteSQL($query, $ph, $limit, $page);

        return $this;
    }

    public function getByObjectTypeAndObjectId($object_type_id, $object_id, $where = null, $order = null)
    {
        if ($object_type_id == '') {
            return false;
        }

        if ($object_id == '') {
            return false;
        }

        $ph = array(
            'object_type_id' => (int)$object_type_id,
            'object_id' => (int)$object_id,
        );

        $query = '
					select	*
					from	' . $this->table . '
					where	object_type_id = ?
						AND object_id = ?
						AND deleted = 0';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByObjectTypeAndObjectIdAndCreatedBy($object_type_id, $object_id, $created_by, $where = null, $order = null)
    {
        if ($object_type_id == '') {
            return false;
        }

        if ($object_id == '') {
            return false;
        }

        if ($created_by == '') {
            return false;
        }

        $ph = array(
            'object_type_id' => (int)$object_type_id,
            'object_id' => (int)$object_id,
            'created_by' => $created_by,
        );

        $query = '
					select	*
					from	' . $this->table . '
					where	object_type_id = ?
						AND object_id = ?
						AND created_by = ?
						AND deleted = 0';
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

        $sort_column_aliases = array();

        $order = $this->getColumnsFromAliases($order, $sort_column_aliases);
        if ($order == null) {
            $order = array('created_date' => 'desc');
            $strict = false;
        } else {
            //Always try to order by status first so INACTIVE employees go to the bottom.
            if (!isset($order['created_date'])) {
                $order = Misc::prependArray(array('created_date' => 'desc'), $order);
            }
            $strict = true;
        }
        //Debug::Arr($order, 'Order Data:', __FILE__, __LINE__, __METHOD__, 10);
        //Debug::Arr($filter_data, 'Filter Data:', __FILE__, __LINE__, __METHOD__, 10);

        $uf = new UserFactory();
        $rf = new RequestFactory();
        $pptsvf = new PayPeriodTimeSheetVerifyListFactory();

        $ph = array(
            'company_id' => (int)$company_id,
        );

        $query = '
					select	a.*,
							CASE WHEN a.object_type_id = 90 THEN pptsvf.user_id ELSE rf.user_id END as user_id,
							y.first_name as created_by_first_name,
							y.middle_name as created_by_middle_name,
							y.last_name as created_by_last_name,
							z.first_name as updated_by_first_name,
							z.middle_name as updated_by_middle_name,
							z.last_name as updated_by_last_name
					from	' . $this->getTable() . ' as a
						LEFT JOIN ' . $rf->getTable() . ' as rf ON ( a.object_type_id in (1010, 1020, 1030, 1040, 1100) AND a.object_id = rf.id )
						LEFT JOIN ' . $pptsvf->getTable() . ' as pptsvf ON ( a.object_type_id = 90 AND a.object_id = pptsvf.id ) ';

        $query .= '		LEFT JOIN ' . $uf->getTable() . ' as y ON ( a.created_by = y.id AND y.deleted = 0 )
						LEFT JOIN ' . $uf->getTable() . ' as z ON ( a.updated_by = z.id AND z.deleted = 0 )
					where	y.company_id = ?';

        $user_id_column = 'a.created_by';
        if (isset($filter_data['object_type_id']) and in_array($filter_data['object_type_id'], array(1010, 1020, 1030, 1040, 1100))) { //Requests
            $user_id_column = 'rf.user_id';
        } elseif (isset($filter_data['object_type_id']) and in_array($filter_data['object_type_id'], array(90))) { //TimeSheet
            $user_id_column = 'pptsvf.user_id';
        } elseif (isset($filter_data['object_type_id']) and in_array($filter_data['object_type_id'], array(200))) { //Expense
            $user_id_column = 'uef.user_id';
        }
        $query .= (isset($filter_data['permission_children_ids'])) ? $this->getWhereClauseSQL($user_id_column, $filter_data['permission_children_ids'], 'numeric_list', $ph) : null;

        $query .= (isset($filter_data['id'])) ? $this->getWhereClauseSQL('a.id', $filter_data['id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['exclude_id'])) ? $this->getWhereClauseSQL('a.id', $filter_data['exclude_id'], 'not_numeric_list', $ph) : null;

        $query .= (isset($filter_data['object_type_id'])) ? $this->getWhereClauseSQL('a.object_type_id', $filter_data['object_type_id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['object_id'])) ? $this->getWhereClauseSQL('a.object_id', $filter_data['object_id'], 'numeric_list', $ph) : null;

        if (isset($filter_data['object_type_id']) and in_array($filter_data['object_type_id'], array(90))) { //TimeSheet
            $query .= (isset($filter_data['pay_period_id'])) ? $this->getWhereClauseSQL('pptsvf.pay_period_id', $filter_data['pay_period_id'], 'numeric_list', $ph) : null;
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
