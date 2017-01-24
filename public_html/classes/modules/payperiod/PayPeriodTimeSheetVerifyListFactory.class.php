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
 * @package Modules\PayPeriod
 */
class PayPeriodTimeSheetVerifyListFactory extends PayPeriodTimeSheetVerifyFactory implements IteratorAggregate
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

    public function getByPayPeriodIdAndUserId($pay_period_id, $user_id, $where = null, $order = null)
    {
        if ($pay_period_id == '') {
            return false;
        }

        if ($user_id == '') {
            return false;
        }

        $ph = array(
            'pay_period_id' => (int)$pay_period_id,
            'user_id' => (int)$user_id,
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a
					where
						a.pay_period_id = ?
						AND a.user_id = ?
						AND ( a.deleted = 0 )
						';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByPayPeriodIdAndUserIdAndCompanyId($pay_period_id, $user_id, $company_id, $where = null, $order = null)
    {
        if ($pay_period_id == '') {
            return false;
        }

        if ($user_id == '') {
            return false;
        }

        if ($company_id == '') {
            return false;
        }

        $uf = new UserFactory();

        $ph = array(
            'pay_period_id' => (int)$pay_period_id,
            'user_id' => (int)$user_id,
            'company_id' => (int)$company_id,
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a,
							' . $uf->getTable() . ' as b
					where	a.user_id = b.id
						AND a.pay_period_id = ?
						AND a.user_id = ?
						AND b.company_id = ?
						AND ( a.deleted = 0 AND b.deleted = 0 )
						';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByPayPeriodIdAndCompanyId($pay_period_id, $company_id, $where = null, $order = null)
    {
        if ($pay_period_id == '') {
            return false;
        }

        if ($company_id == '') {
            return false;
        }

        $uf = new UserFactory();

        $ph = array(
            //'pay_period_id' => (int)$pay_period_id,
            'company_id' => (int)$company_id,
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a,
							' . $uf->getTable() . ' as b
					where	a.user_id = b.id
						AND b.company_id = ?
						AND a.pay_period_id in (' . $this->getListSQL($pay_period_id, $ph, 'int') . ')
						AND ( a.deleted = 0 AND b.deleted = 0 )
						';
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

        $uf = new UserFactory();

        $ph = array(
            'id' => (int)$id,
            'company_id' => (int)$company_id
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a,
							' . $uf->getTable() . ' as b
					where	a.user_id = b.id
						AND a.id = ?
						AND b.company_id = ?
						AND ( a.deleted = 0 AND b.deleted = 0 )
						';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByCompanyId($id, $where = null, $order = null)
    {
        if ($id == '') {
            return false;
        }

        $ppf = new PayPeriodFactory();

        $ph = array(
            'company_id' => (int)$id
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a
					LEFT JOIN ' . $ppf->getTable() . ' as b ON a.pay_period_id = b.id
					where	b.company_id = ?
						AND ( a.deleted = 0 AND b.deleted = 0 )';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByUserIdListAndStatusAndLevelAndMaxLevelAndNotAuthorized($ids, $status, $level, $max_level, $limit = null, $page = null, $where = null, $order = null)
    {
        if ($ids == '') {
            return false;
        }

        if ($status == '') {
            return false;
        }


        if ($level == '') {
            return false;
        }

        if ($max_level == '') {
            return false;
        }

        $additional_sort_fields = array('start_date', 'user_id');

        $strict_order = true;
        if ($order == null) {
            $order = array('a.user_id' => 'asc', 'b.start_date' => 'asc');
            $strict_order = false;
        }


        $ppf = new PayPeriodFactory();
        //$udf = new UserDateFactory();

        $ph = array(
            'status' => $status,
            'level' => $level,
            'max_level' => $max_level,
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a,
							' . $ppf->getTable() . ' as b

					where	a.pay_period_id = b.id
						AND	a.status_id = ?
						AND a.authorized = 0
						AND ( a.authorization_level = ? OR a.authorization_level > ? )
						AND a.user_id in (' . $this->getListSQL($ids, $ph) . ')
						AND ( a.deleted = 0 AND b.deleted = 0 )
				';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict_order, $additional_sort_fields);

        $this->ExecuteSQL($query, $ph, $limit, $page);

        return $this;
    }

    public function getByHierarchyLevelMapAndStatusAndNotAuthorized($hierarchy_level_map, $status, $limit = null, $page = null, $where = null, $order = null)
    {
        if ($hierarchy_level_map == '') {
            return false;
        }

        if ($status == '') {
            return false;
        }

        $additional_sort_fields = array('start_date', 'user_id');

        $strict_order = true;
        if ($order == null) {
            $order = array('a.user_id' => 'asc', 'b.start_date' => 'asc');
            $strict_order = false;
        }

        $ppf = new PayPeriodFactory();
        $huf = new HierarchyUserFactory();

        $ph = array(
            'status' => $status,
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a,
							' . $ppf->getTable() . ' as b,
							' . $huf->getTable() . ' as z
					where	a.pay_period_id = b.id
						AND a.user_id = z.user_id
						AND	a.status_id = ?
						AND a.authorized = 0
						AND ( ' . HierarchyLevelFactory::convertHierarchyLevelMapToSQL($hierarchy_level_map) . ' )
						AND ( a.deleted = 0 AND b.deleted = 0 )
				';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict_order, $additional_sort_fields);

        $this->ExecuteSQL($query, $ph, $limit, $page);

        return $this;
    }

    //This is used just for MyAccount -> TimeSheet Authorization, as its more complicated than a normal query.
    public function getAPIAuthorizationSearchByCompanyIdAndArrayCriteria($company_id, $filter_data, $limit = null, $page = null, $where = null, $order = null)
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

        if (isset($filter_data['include_user_id'])) {
            $filter_data['user_id'] = $filter_data['include_user_id'];
        }
        if (isset($filter_data['exclude_user_id'])) {
            $filter_data['exclude_id'] = $filter_data['exclude_user_id'];
        }

        $additional_order_fields = array('start_date', 'end_date', 'transaction_date', 'user_status_id', 'last_name', 'first_name', 'default_branch', 'default_department', 'user_group', 'title');

        $sort_column_aliases = array(
            'status' => 'status_id',
        );
        $order = $this->getColumnsFromAliases($order, $sort_column_aliases);

        if ($order == null) {
            $order = array('status_id' => 'asc', 'start_date' => 'desc',);
            $strict = false;
        } else {
            $strict = true;
        }
        //Debug::Arr($order, 'Order Data:', __FILE__, __LINE__, __METHOD__, 10);
        //Debug::Arr($filter_data, 'Filter Data:', __FILE__, __LINE__, __METHOD__, 10);

        $uf = new UserFactory();
        $bf = new BranchFactory();
        $df = new DepartmentFactory();
        $ugf = new UserGroupFactory();
        $utf = new UserTitleFactory();
        $huf = new HierarchyUserFactory();
        $ppf = new PayPeriodFactory();
        $ppsf = new PayPeriodScheduleFactory();
        $ppsuf = new PayPeriodScheduleUserFactory();

        $epoch = time();

        $ph = array(
            'company_id' => (int)$company_id,
        );

        //Need to make this return DISTINCT records only, because if the same child is assigned to multiple hierarchies,
        //the join to table HUF will force it to return one row for each hierarchy they are a child of. This prevents that.
        $query = '	SELECT  DISTINCT
							pptsvf.*,
							uf.first_name as first_name,
							uf.last_name as last_name,
							uf.country as country,
							uf.province as province,

							ppf.start_date as start_date,
							ppf.end_date as end_date,
							ppf.transaction_date as transaction_date,

							pptsvf.pay_period_window_start_date as window_start_date,
							pptsvf.pay_period_window_end_date as window_end_date,

							bf.id as default_branch_id,
							bf.name as default_branch,
							df.id as default_department_id,
							df.name as default_department,
							ugf.id as user_group_id,
							ugf.name as user_group,
							utf.id as title_id,
							utf.name as title
					FROM (
							SELECT
								CASE WHEN pptsvf.id IS NOT NULL THEN pptsvf.id ELSE -1 END as id,
								uf.id as user_id,
								ppf.id as pay_period_id,
								CASE WHEN pptsvf.id IS NOT NULL THEN pptsvf.status_id ELSE ( CASE WHEN ppsf.timesheet_verify_type_id IN (20,40) THEN 45 ELSE 30 END) END as status_id,
								CASE WHEN pptsvf.id IS NOT NULL THEN pptsvf.user_verified ELSE 0 END as user_verified,
								CASE WHEN pptsvf.id IS NOT NULL THEN pptsvf.user_verified_date ELSE 0 END as user_verified_date,
								CASE WHEN pptsvf.id IS NOT NULL THEN pptsvf.authorized ELSE 0 END as authorized,
								CASE WHEN pptsvf.id IS NOT NULL THEN pptsvf.authorization_level ELSE 99 END as authorization_level,
								CASE WHEN pptsvf.id IS NOT NULL THEN pptsvf.created_by ELSE NULL END as created_by,
								CASE WHEN pptsvf.id IS NOT NULL THEN pptsvf.created_date ELSE NULL END as created_date,
								CASE WHEN pptsvf.id IS NOT NULL THEN pptsvf.updated_by ELSE NULL END as updated_by,
								CASE WHEN pptsvf.id IS NOT NULL THEN pptsvf.updated_date ELSE NULL END as updated_date,
								CASE WHEN pptsvf.id IS NOT NULL THEN pptsvf.deleted ELSE 0 END as deleted,
								( ' . $this->getSQLToEpochFunction('ppf.end_date') . ' - ppsf.timesheet_verify_before_end_date ) as pay_period_window_start_date,
								( ' . $this->getSQLToEpochFunction('ppf.transaction_date') . ' - ppsf.timesheet_verify_before_transaction_date ) as pay_period_window_end_date
							FROM ' . $uf->getTable() . ' as uf
								LEFT JOIN ' . $ppsuf->getTable() . ' as ppsuf ON ( uf.id = ppsuf.user_id )
								LEFT JOIN ' . $ppf->getTable() . ' as ppf ON ( ppf.pay_period_schedule_id = ppsuf.pay_period_schedule_id AND ppf.status_id != 20 )
								LEFT JOIN ' . $ppsf->getTable() . ' as ppsf ON ( ppf.pay_period_schedule_id = ppsf.id AND ppsf.timesheet_verify_type_id != 10 AND ppsf.deleted = 0 )
								LEFT JOIN ' . $this->getTable() . ' as pptsvf ON ( uf.id = pptsvf.user_id AND ppf.id = pptsvf.pay_period_id AND pptsvf.deleted = 0 )
							WHERE uf.company_id = ' . (int)$company_id . '
								AND (
										( ( uf.status_id = 10 AND uf.termination_date IS NULL ) OR ( uf.termination_date IS NOT NULL AND uf.termination_date >= ' . $this->getSQLToEpochFunction('ppf.start_date') . ' ) )
										AND
										( uf.hire_date IS NULL OR ( uf.hire_date IS NOT NULL AND uf.hire_date <= ' . $this->getSQLToEpochFunction('ppf.end_date') . ' ) )
									) ';
        $query .= '
								AND ( ' . $this->getSQLToEpochFunction('ppf.end_date') . ' - ppsf.timesheet_verify_before_end_date ) <= ' . (int)$epoch . '
								AND ( ' . $this->getSQLToEpochFunction('ppf.transaction_date') . ' - ppsf.timesheet_verify_before_transaction_date ) >= ' . (int)$epoch . '
								AND ( uf.deleted = 0 AND ppf.deleted = 0 )
						) as pptsvf
						LEFT JOIN ' . $uf->getTable() . ' as uf ON ( pptsvf.user_id = uf.id AND uf.deleted = 0 )
						LEFT JOIN ' . $ppf->getTable() . ' as ppf ON ( pptsvf.pay_period_id = ppf.id AND ppf.deleted = 0 )
						LEFT JOIN ' . $ppsf->getTable() . ' as ppsf ON ( ppf.pay_period_schedule_id = ppsf.id AND ppsf.deleted = 0 )
						LEFT JOIN ' . $huf->getTable() . ' as huf ON ( pptsvf.user_id = huf.user_id )
						LEFT JOIN ' . $bf->getTable() . ' as bf ON ( uf.default_branch_id = bf.id AND bf.deleted = 0)
						LEFT JOIN ' . $df->getTable() . ' as df ON ( uf.default_department_id = df.id AND df.deleted = 0)
						LEFT JOIN ' . $ugf->getTable() . ' as ugf ON ( uf.group_id = ugf.id AND ugf.deleted = 0 )
						LEFT JOIN ' . $utf->getTable() . ' as utf ON ( uf.title_id = utf.id AND utf.deleted = 0 )
						LEFT JOIN ' . $uf->getTable() . ' as y ON ( pptsvf.created_by = y.id AND y.deleted = 0 )
						LEFT JOIN ' . $uf->getTable() . ' as z ON ( pptsvf.updated_by = z.id AND z.deleted = 0 )
					WHERE uf.company_id = ? 
							AND ppsf.timesheet_verify_type_id in ( 30, 40 )'; //Only show when pay period schedule timesheet verify settings actually allow authorization.

        $query .= (isset($filter_data['permission_children_ids'])) ? $this->getWhereClauseSQL('uf.id', $filter_data['permission_children_ids'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['id'])) ? $this->getWhereClauseSQL('pptsvf.id', $filter_data['id'], 'numeric_list', $ph) : null;

        $query .= (isset($filter_data['user_id'])) ? $this->getWhereClauseSQL('uf.id', $filter_data['user_id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['exclude_id'])) ? $this->getWhereClauseSQL('uf.id', $filter_data['exclude_id'], 'not_numeric_list', $ph) : null;

        $query .= (isset($filter_data['status_id'])) ? $this->getWhereClauseSQL('pptsvf.status_id', $filter_data['status_id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['pay_period_id'])) ? $this->getWhereClauseSQL('ppf.id', $filter_data['pay_period_id'], 'numeric_list', $ph) : null;

        $query .= (isset($filter_data['group_id'])) ? $this->getWhereClauseSQL('uf.group_id', $filter_data['group_id'], 'numeric_list', $ph) : null;

        $query .= (isset($filter_data['default_branch_id'])) ? $this->getWhereClauseSQL('uf.default_branch_id', $filter_data['default_branch_id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['default_department_id'])) ? $this->getWhereClauseSQL('uf.default_department_id', $filter_data['default_department_id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['title_id'])) ? $this->getWhereClauseSQL('uf.title_id', $filter_data['title_id'], 'numeric_list', $ph) : null;

        $query .= (isset($filter_data['country'])) ? $this->getWhereClauseSQL('uf.country', $filter_data['country'], 'upper_text_list', $ph) : null;
        $query .= (isset($filter_data['province'])) ? $this->getWhereClauseSQL('uf.province', $filter_data['province'], 'upper_text_list', $ph) : null;

        $query .= (isset($filter_data['authorized'])) ? $this->getWhereClauseSQL('pptsvf.authorized', $filter_data['authorized'], 'numeric_list', $ph) : null;

        if (isset($filter_data['hierarchy_level_map']) and is_array($filter_data['hierarchy_level_map'])) {
            $query .= ' AND  huf.id IS NOT NULL '; //Make sure the user maps to a hierarchy.
            $hierarchy_level_sql = HierarchyLevelFactory::convertHierarchyLevelMapToSQL($filter_data['hierarchy_level_map'], 'pptsvf.', 'huf.');
            if ($hierarchy_level_sql != '') {
                $query .= ' AND ( ' . $hierarchy_level_sql . ' )';
            }
        } elseif (isset($filter_data['hierarchy_level_map']) and $filter_data['hierarchy_level_map'] == false) {
            //If hierarchy_level_map is not an array, don't return any requests.
            $query .= ' AND  huf.id = -1 '; //Make sure the user maps to a hierarchy.
        }

        $query .= (isset($filter_data['created_by'])) ? $this->getWhereClauseSQL(array('pptsvf.created_by', 'y.first_name', 'y.last_name'), $filter_data['created_by'], 'user_id_or_name', $ph) : null;
        $query .= (isset($filter_data['updated_by'])) ? $this->getWhereClauseSQL(array('pptsvf.updated_by', 'z.first_name', 'z.last_name'), $filter_data['updated_by'], 'user_id_or_name', $ph) : null;

        $query .= ' AND ( pptsvf.deleted = 0 )';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict, $additional_order_fields);

        $this->ExecuteSQL($query, $ph, $limit, $page);

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

        if (isset($filter_data['include_user_id'])) {
            $filter_data['user_id'] = $filter_data['include_user_id'];
        }
        if (isset($filter_data['exclude_user_id'])) {
            $filter_data['exclude_id'] = $filter_data['exclude_user_id'];
        }

        $additional_order_fields = array('start_date', 'end_date', 'transaction_date', 'user_status_id', 'last_name', 'first_name', 'default_branch', 'default_department', 'user_group', 'title');

        $sort_column_aliases = array(
            'status' => 'status_id',
        );
        $order = $this->getColumnsFromAliases($order, $sort_column_aliases);

        if ($order == null) {
            $order = array('status_id' => 'asc', 'start_date' => 'desc',);
            $strict = false;
        } else {
            //Always sort by last name, first name after other columns
            /*
            if ( !isset($order['effective_date']) ) {
                    $order['effective_date'] = 'desc';
            }
            */
            $strict = true;
        }
        //Debug::Arr($order, 'Order Data:', __FILE__, __LINE__, __METHOD__, 10);
        //Debug::Arr($filter_data, 'Filter Data:', __FILE__, __LINE__, __METHOD__, 10);

        $uf = new UserFactory();
        $bf = new BranchFactory();
        $df = new DepartmentFactory();
        $ugf = new UserGroupFactory();
        $utf = new UserTitleFactory();
        $huf = new HierarchyUserFactory();
        $ppf = new PayPeriodFactory();

        $ph = array(
            'company_id' => (int)$company_id,
        );

        //Need to make this return DISTINCT records only, because if the same child is assigned to multiple hierarchies,
        //the join to table HUF will force it to return one row for each hierarchy they are a child of. This prevents that.
        $query = '
								select  DISTINCT
												a.*,
												b.first_name as first_name,
												b.last_name as last_name,
												b.country as country,
												b.province as province,

												ppf.start_date as start_date,
												ppf.end_date as end_date,
												ppf.transaction_date as transaction_date,

												c.id as default_branch_id,
												c.name as default_branch,
												d.id as default_department_id,
												d.name as default_department,
												e.id as user_group_id,
												e.name as user_group,
												f.id as title_id,
												f.name as title
								from    ' . $this->getTable() . ' as a
										LEFT JOIN ' . $ppf->getTable() . ' as ppf ON ( a.pay_period_id = ppf.id AND ppf.deleted = 0 )
										LEFT JOIN ' . $uf->getTable() . ' as b ON ( a.user_id = b.id AND b.deleted = 0 )

										LEFT JOIN ' . $huf->getTable() . ' as huf ON ( a.user_id = huf.user_id )

										LEFT JOIN ' . $bf->getTable() . ' as c ON ( b.default_branch_id = c.id AND c.deleted = 0)
										LEFT JOIN ' . $df->getTable() . ' as d ON ( b.default_department_id = d.id AND d.deleted = 0)
										LEFT JOIN ' . $ugf->getTable() . ' as e ON ( b.group_id = e.id AND e.deleted = 0 )
										LEFT JOIN ' . $utf->getTable() . ' as f ON ( b.title_id = f.id AND f.deleted = 0 )
										LEFT JOIN ' . $uf->getTable() . ' as y ON ( a.created_by = y.id AND y.deleted = 0 )
										LEFT JOIN ' . $uf->getTable() . ' as z ON ( a.updated_by = z.id AND z.deleted = 0 )

								where   b.company_id = ?
								';

        $query .= (isset($filter_data['permission_children_ids'])) ? $this->getWhereClauseSQL('a.user_id', $filter_data['permission_children_ids'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['id'])) ? $this->getWhereClauseSQL('a.id', $filter_data['id'], 'numeric_list', $ph) : null;

        $query .= (isset($filter_data['user_id'])) ? $this->getWhereClauseSQL('a.user_id', $filter_data['user_id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['exclude_id'])) ? $this->getWhereClauseSQL('a.user_id', $filter_data['exclude_id'], 'not_numeric_list', $ph) : null;

        $query .= (isset($filter_data['status_id'])) ? $this->getWhereClauseSQL('a.status_id', $filter_data['status_id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['pay_period_id'])) ? $this->getWhereClauseSQL('a.pay_period_id', $filter_data['pay_period_id'], 'numeric_list', $ph) : null;

        $query .= (isset($filter_data['group_id'])) ? $this->getWhereClauseSQL('b.group_id', $filter_data['group_id'], 'numeric_list', $ph) : null;

        $query .= (isset($filter_data['default_branch_id'])) ? $this->getWhereClauseSQL('b.default_branch_id', $filter_data['default_branch_id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['default_department_id'])) ? $this->getWhereClauseSQL('b.default_department_id', $filter_data['default_department_id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['title_id'])) ? $this->getWhereClauseSQL('b.title_id', $filter_data['title_id'], 'numeric_list', $ph) : null;

        $query .= (isset($filter_data['country'])) ? $this->getWhereClauseSQL('b.country', $filter_data['country'], 'upper_text_list', $ph) : null;
        $query .= (isset($filter_data['province'])) ? $this->getWhereClauseSQL('b.province', $filter_data['province'], 'upper_text_list', $ph) : null;

        $query .= (isset($filter_data['authorized'])) ? $this->getWhereClauseSQL('a.authorized', $filter_data['authorized'], 'numeric_list', $ph) : null;

        if (isset($filter_data['hierarchy_level_map']) and is_array($filter_data['hierarchy_level_map'])) {
            $query .= ' AND  huf.id IS NOT NULL '; //Make sure the user maps to a hierarchy.
            //$query        .= ' AND ( '. HierarchyLevelFactory::convertHierarchyLevelMapToSQL( $filter_data['hierarchy_level_map'], 'a.', 'huf.' ) .' )';
            $hierarchy_level_sql = HierarchyLevelFactory::convertHierarchyLevelMapToSQL($filter_data['hierarchy_level_map'], 'a.', 'huf.');
            if ($hierarchy_level_sql != '') {
                $query .= ' AND ( ' . $hierarchy_level_sql . ' )';
            }
        } elseif (isset($filter_data['hierarchy_level_map']) and $filter_data['hierarchy_level_map'] == false) {
            //If hierarchy_level_map is not an array, don't return any requests.
            $query .= ' AND  huf.id = -1 '; //Make sure the user maps to a hierarchy.
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
