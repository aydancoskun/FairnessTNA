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
 * @package Modules\Schedule
 */
class RecurringScheduleControlListFactory extends RecurringScheduleControlFactory implements IteratorAggregate
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

    public function getByIdAndCompanyId($id, $company_id, $where = null, $order = null)
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
					from	' . $this->getTable() . ' as a
					where	company_id = ?
						AND id = ?
						AND deleted = 0';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByCompanyId($id, $limit = null, $page = null, $where = null, $order = null)
    {
        if ($id == '') {
            return false;
        }

        if ($order == null) {
            $order = array('last_name' => 'asc');
            $strict = false;
        } else {
            $strict = true;
        }

        $additional_sort_fields = array('name', 'description', 'last_name');

        $rsuf = new RecurringScheduleUserFactory();
        $rstcf = new RecurringScheduleTemplateControlFactory();
        $uf = new UserFactory();

        $ph = array(
            'id' => (int)$id,
        );


        $query = '
					select	a.*,
							b.name as name,
							b.description as description,
							c.user_id as user_id,
							d.last_name as last_name
					from	' . $this->getTable() . ' as a,
							' . $rstcf->getTable() . ' as b,
							' . $rsuf->getTable() . ' as c,
							' . $uf->getTable() . ' as d
					where	a.recurring_schedule_template_control_id = b.id
						AND a.id = c.recurring_schedule_control_id
						AND c.user_id = d.id
						AND a.company_id = ?
						AND ( a.deleted = 0 AND b.deleted=0 AND d.deleted=0 )
					';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict, $additional_sort_fields);

        $this->ExecuteSQL($query, $ph, $limit, $page);

        return $this;
    }

    public function getByUserIDAndStartDateAndEndDate($user_id, $start_date, $end_date, $where = null, $order = null)
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
            //$order = array( 'type_id' => 'asc' );
            $strict = false;
        } else {
            $strict = true;
        }

        $start_date_stamp = $this->db->BindDate($start_date);
        $end_date_stamp = $this->db->BindDate($end_date);

        $rsuf = new RecurringScheduleUserFactory();
        $rstcf = new RecurringScheduleTemplateControlFactory();

        $ph = array(
            'user_id' => (int)$user_id,
            'start_date1' => $start_date_stamp,
            'end_date1' => $end_date_stamp,
            'start_date2' => $start_date_stamp,
            'start_date3' => $start_date_stamp,
            'end_date3' => $end_date_stamp,
            'start_date4' => $start_date_stamp,
            'end_date4' => $end_date_stamp,
            'start_date5' => $start_date_stamp,
            'end_date5' => $end_date_stamp,
            'start_date6' => $start_date_stamp,
            'end_date6' => $end_date_stamp,
        );
        /*

                            from	'. $this->getTable() .' as a,
                                    '. $rsuf->getTable() .' as b
                            where	a.id = b.recurring_schedule_control_id

        */
        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a
						LEFT JOIN ' . $rstcf->getTable() . ' as b ON a.recurring_schedule_template_control_id = b.id
						LEFT JOIN ' . $rsuf->getTable() . ' as c ON a.id = c.recurring_schedule_control_id
					WHERE c.user_id = ?
						AND
						(
							(a.start_date >= ? AND a.start_date <= ? AND a.end_date IS NULL )
							OR
							(a.start_date <= ? AND a.end_date IS NULL )
							OR
							(a.start_date >= ? AND a.end_date <= ? )
							OR
							(a.start_date >= ? AND a.start_date <= ? )
							OR
							(a.end_date >= ? AND a.end_date <= ? )
							OR
							(a.start_date <= ? AND a.end_date >= ? )
						)
						AND ( a.deleted = 0 AND b.deleted = 0 )
					';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByCompanyIdAndEndDate($company_id, $end_date, $where = null, $order = null)
    {
        if ($end_date == '') {
            return false;
        }

        if ($order == null) {
            $order = array('a.company_id' => 'asc');
            $strict = false;
        } else {
            $strict = true;
        }

        $ph = array(
            'company_id' => (int)$company_id,
            'end_date' => $this->db->BindDate($end_date),
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a
					where	 a.company_id = ?
						AND ( a.end_date IS NOT NULL AND a.end_date <= ? )
						AND ( a.deleted = 0 )
					';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getMostCommonDisplayWeeksByCompanyId($company_id, $where = null)
    {
        $ph = array(
            'company_id' => (int)$company_id,
        );

        $rstcf = new RecurringScheduleTemplateControlFactory();

        $query = '
					SELECT	a.display_weeks as display_weeks
					FROM	' . $this->getTable() . ' as a
					LEFT JOIN ' . $rstcf->getTable() . ' as b ON a.recurring_schedule_template_control_id = b.id
					WHERE	 a.company_id = ?
						AND ( a.deleted = 0 AND b.deleted = 0 )
					GROUP BY a.display_weeks
					ORDER BY count(*) DESC
					LIMIT 1
					';

        $query .= $this->getWhereSQL($where);

        $result = $this->db->GetOne($query, $ph);

        return $result;
    }

    public function getByCompanyIdAndStartDateAndEndDate($company_id, $start_date, $end_date, $where = null, $order = null)
    {
        if ($start_date == '') {
            return false;
        }

        if ($end_date == '') {
            return false;
        }

        if ($order == null) {
            $order = array('a.company_id' => 'asc');
            $strict = false;
        } else {
            $strict = true;
        }

        $start_date_stamp = $this->db->BindDate($start_date);
        $end_date_stamp = $this->db->BindDate($end_date);

        //$rsuf = new RecurringScheduleUserFactory();
        $rstcf = new RecurringScheduleTemplateControlFactory();

        $ph = array(
            'company_id' => (int)$company_id,
            'start_date1' => $start_date_stamp,
            'end_date1' => $end_date_stamp,
            'start_date2' => $start_date_stamp,
            'start_date3' => $start_date_stamp,
            'end_date3' => $end_date_stamp,
            'start_date4' => $start_date_stamp,
            'end_date4' => $end_date_stamp,
            'start_date5' => $start_date_stamp,
            'end_date5' => $end_date_stamp,
            'start_date6' => $start_date_stamp,
            'end_date6' => $end_date_stamp,
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a
						LEFT JOIN ' . $rstcf->getTable() . ' as b ON a.recurring_schedule_template_control_id = b.id
					where	 a.company_id = ?
						AND
						(
							(a.start_date >= ? AND a.start_date <= ? AND a.end_date IS NULL )
							OR
							(a.start_date <= ? AND a.end_date IS NULL )
							OR
							(a.start_date >= ? AND a.end_date <= ? )
							OR
							(a.start_date >= ? AND a.start_date <= ? )
							OR
							(a.end_date >= ? AND a.end_date <= ? )
							OR
							(a.start_date <= ? AND a.end_date >= ? )
						)
						AND ( a.deleted = 0 AND b.deleted = 0 )
					';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByCompanyIdAndIDAndStartDateAndEndDate($company_id, $id, $start_date, $end_date, $where = null, $order = null)
    {
        if ($start_date == '') {
            return false;
        }

        if ($end_date == '') {
            return false;
        }

        if ($order == null) {
            $order = array('a.company_id' => 'asc');
            $strict = false;
        } else {
            $strict = true;
        }

        $start_date_stamp = $this->db->BindDate($start_date);
        $end_date_stamp = $this->db->BindDate($end_date);

        //$rsuf = new RecurringScheduleUserFactory();
        $rstcf = new RecurringScheduleTemplateControlFactory();

        $ph = array(
            'company_id' => (int)$company_id,
            //'id' => $id,
            'start_date1' => $start_date_stamp,
            'end_date1' => $end_date_stamp,
            'start_date2' => $start_date_stamp,
            'start_date3' => $start_date_stamp,
            'end_date3' => $end_date_stamp,
            'start_date4' => $start_date_stamp,
            'end_date4' => $end_date_stamp,
            'start_date5' => $start_date_stamp,
            'end_date5' => $end_date_stamp,
            'start_date6' => $start_date_stamp,
            'end_date6' => $end_date_stamp,
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a
						LEFT JOIN ' . $rstcf->getTable() . ' as b ON a.recurring_schedule_template_control_id = b.id
					where	 a.company_id = ?
						AND
						(
							(a.start_date >= ? AND a.start_date <= ? AND a.end_date IS NULL )
							OR
							(a.start_date <= ? AND a.end_date IS NULL )
							OR
							(a.start_date >= ? AND a.end_date <= ? )
							OR
							(a.start_date >= ? AND a.start_date <= ? )
							OR
							(a.end_date >= ? AND a.end_date <= ? )
							OR
							(a.start_date <= ? AND a.end_date >= ? )
						)
						AND a.id in (' . $this->getListSQL($id, $ph, 'int') . ')						
						AND ( a.deleted = 0 AND b.deleted = 0 )
					';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByCompanyIdAndTemplateID($company_id, $id, $where = null, $order = null)
    {
        if ($order == null) {
            $order = array('a.company_id' => 'asc', 'a.start_date' => 'asc', 'a.id' => 'asc');
            $strict = false;
        } else {
            $strict = true;
        }

        $rstcf = new RecurringScheduleTemplateControlFactory();

        $ph = array(
            'company_id' => (int)$company_id,
            'id' => (int)$id,
        );

        //Don't filter on b.deleted=0, as this is mainly called when deleting a RecurringScheduleTemplateControl record
        //at which point the record is already deleted and therefore this won't return any data, causing things to break.
        //This shouldn't be called from any other function most likely.
        //AND b.deleted = 0
        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a
						LEFT JOIN ' . $rstcf->getTable() . ' as b ON a.recurring_schedule_template_control_id = b.id
					where	 a.company_id = ?
						AND a.recurring_schedule_template_control_id = ?
						AND ( a.deleted = 0 )
					';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getSearchByCompanyIdAndArrayCriteria($company_id, $filter_data, $limit = null, $page = null, $where = null, $order = null)
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
        //Debug::Arr($order, 'aOrder Data:', __FILE__, __LINE__, __METHOD__, 10);

        $additional_order_fields = array('name', 'description', 'last_name', 'template_id');
        if ($order == null) {
            $order = array('last_name' => 'asc', 'd.id' => 'asc', 'a.start_date' => 'desc');
            $strict = false;
        } else {
            //Always try to order by status first so UNPAID employees go to the bottom.

            if (isset($order['last_name'])) {
                $order['d.last_name'] = $order['last_name'];
                unset($order['last_name']);
            }
            if (isset($order['first_name'])) {
                $order['d.first_name'] = $order['first_name'];
                unset($order['first_name']);
            }
            if (isset($order['template_id'])) {
                $order['b.id'] = $order['template_id'];
                unset($order['template_id']);
            }

            $strict = true;
        }
        //Debug::Arr($order, 'bOrder Data:', __FILE__, __LINE__, __METHOD__, 10);
        //Debug::Arr($filter_data, 'Filter Data:', __FILE__, __LINE__, __METHOD__, 10);

        $uf = new UserFactory();
        $rsuf = new RecurringScheduleUserFactory();
        $rstcf = new RecurringScheduleTemplateControlFactory();

        $ph = array(
            'company_id' => (int)$company_id,
        );

        $query = '
					select	a.*,
							b.name as name,
							b.description as description,
							c.user_id as user_id,
							d.last_name as last_name
					from	' . $this->getTable() . ' as a
						LEFT JOIN ' . $rstcf->getTable() . ' as b ON a.recurring_schedule_template_control_id = b.id
						LEFT JOIN ' . $rsuf->getTable() . ' as c ON a.id = c.recurring_schedule_control_id
						LEFT JOIN ' . $uf->getTable() . ' as d ON ( c.user_id = d.id AND d.deleted = 0 )
					where	a.company_id = ?
					';

        if (isset($filter_data['id']) and isset($filter_data['id'][0]) and !in_array(-1, (array)$filter_data['id'])) {
            $query .= ' AND a.id in (' . $this->getListSQL($filter_data['id'], $ph) . ') ';
        }
        if (isset($filter_data['permission_children_ids']) and isset($filter_data['permission_children_ids'][0]) and !in_array(-1, (array)$filter_data['permission_children_ids'])) {
            $query .= ' AND d.id in (' . $this->getListSQL($filter_data['permission_children_ids'], $ph) . ') ';
        }
        if (isset($filter_data['user_id']) and isset($filter_data['user_id'][0]) and !in_array(-1, (array)$filter_data['user_id'])) {
            $query .= ' AND d.id in (' . $this->getListSQL($filter_data['user_id'], $ph) . ') ';
        }
        if (isset($filter_data['template_id']) and isset($filter_data['template_id'][0]) and !in_array(-1, (array)$filter_data['template_id'])) {
            $query .= ' AND b.id in (' . $this->getListSQL($filter_data['template_id'], $ph) . ') ';
        }

        if (isset($filter_data['status_id']) and isset($filter_data['status_id'][0]) and !in_array(-1, (array)$filter_data['status_id'])) {
            $query .= ' AND d.status_id in (' . $this->getListSQL($filter_data['status_id'], $ph) . ') ';
        }
        if (isset($filter_data['group_id']) and isset($filter_data['group_id'][0]) and !in_array(-1, (array)$filter_data['group_id'])) {
            if (isset($filter_data['include_subgroups']) and (bool)$filter_data['include_subgroups'] == true) {
                $uglf = new UserGroupListFactory();
                $filter_data['group_id'] = $uglf->getByCompanyIdAndGroupIdAndSubGroupsArray($company_id, $filter_data['group_id'], true);
            }
            $query .= ' AND d.group_id in (' . $this->getListSQL($filter_data['group_id'], $ph) . ') ';
        }
        if (isset($filter_data['default_branch_id']) and isset($filter_data['default_branch_id'][0]) and !in_array(-1, (array)$filter_data['default_branch_id'])) {
            $query .= ' AND d.default_branch_id in (' . $this->getListSQL($filter_data['default_branch_id'], $ph) . ') ';
        }
        if (isset($filter_data['default_department_id']) and isset($filter_data['default_department_id'][0]) and !in_array(-1, (array)$filter_data['default_department_id'])) {
            $query .= ' AND d.default_department_id in (' . $this->getListSQL($filter_data['default_department_id'], $ph) . ') ';
        }

        if (isset($filter_data['title_id']) and isset($filter_data['title_id'][0]) and !in_array(-1, (array)$filter_data['title_id'])) {
            $query .= ' AND d.title_id in (' . $this->getListSQL($filter_data['title_id'], $ph) . ') ';
        }


        if (isset($filter_data['start_date']) and !is_array($filter_data['start_date']) and trim($filter_data['start_date']) != '') {
            $ph[] = $this->db->BindDate($filter_data['start_date']);
            $query .= ' AND a.start_date >= ?';
        }
        if (isset($filter_data['end_date']) and !is_array($filter_data['end_date']) and trim($filter_data['end_date']) != '') {
            $ph[] = $this->db->BindDate($filter_data['end_date']);
            $query .= ' AND a.start_date <= ?';
        }

        $query .= '
						AND ( a.deleted = 0 AND b.deleted = 0 )
					';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict, $additional_order_fields);

        //Debug::Arr($ph, 'Query: '. $query, __FILE__, __LINE__, __METHOD__, 10);

        $this->ExecuteSQL($query, $ph, $limit, $page);

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

        $additional_order_fields = array('recurring_schedule_template_control', 'recurring_schedule_template_control_description');
        if ($order == null) {
            $order = array('recurring_schedule_template_control_id' => 'asc',);
            $strict = false;
        } else {
            $strict = true;
        }
        //Debug::Arr($order, 'Order Data:', __FILE__, __LINE__, __METHOD__, 10);
        //Debug::Arr($filter_data, 'Filter Data:', __FILE__, __LINE__, __METHOD__, 10);

        $rsuf = new RecurringScheduleUserFactory();
        $rstcf = new RecurringScheduleTemplateControlFactory();

        $ph = array(
            'company_id' => (int)$company_id,
        );

        $query = '
					select	distinct a.*,
							ab.name as recurring_schedule_template_control,
							ab.description as recurring_schedule_template_control_description
					from	' . $this->getTable() . ' as a
						LEFT JOIN ' . $rstcf->getTable() . ' as ab ON ( a.recurring_schedule_template_control_id = ab.id AND ab.deleted = 0 )
						LEFT JOIN ' . $rsuf->getTable() . ' as rsuf ON ( a.id = rsuf. recurring_schedule_control_id )

					where	a.company_id = ?
					';

        //Make sure supervisor (subordinates only) can see/edit any recurring schedule assigned to their subordinates
        $query .= (isset($filter_data['permission_children_ids'])) ? $this->getWhereClauseSQL('rsuf.user_id', $filter_data['permission_children_ids'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['id'])) ? $this->getWhereClauseSQL('a.id', $filter_data['id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['exclude_id'])) ? $this->getWhereClauseSQL('a.id', $filter_data['exclude_id'], 'not_numeric_list', $ph) : null;

        $query .= (isset($filter_data['recurring_schedule_template_control_id'])) ? $this->getWhereClauseSQL('a.recurring_schedule_template_control_id', $filter_data['recurring_schedule_template_control_id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['created_by'])) ? $this->getWhereClauseSQL('a.created_by', $filter_data['created_by'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['updated_by'])) ? $this->getWhereClauseSQL('a.updated_by', $filter_data['updated_by'], 'numeric_list', $ph) : null;

        $query .= ' AND a.deleted = 0 ';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict, $additional_order_fields);

        //Debug::Arr($ph, 'Query: '. $query, __FILE__, __LINE__, __METHOD__, 10);

        $this->ExecuteSQL($query, $ph, $limit, $page);

        return $this;
    }

    public function getAPIExpandedSearchByCompanyIdAndArrayCriteria($company_id, $filter_data, $limit = null, $page = null, $where = null, $order = null)
    {
        if ($company_id == '') {
            return false;
        }

        if (isset($filter_data['user_status_id'])) {
            $filter_data['status_id'] = $filter_data['user_status_id'];
            unset($filter_data['user_status_id']);
        }

        if (!is_array($order)) {
            //Use Filter Data ordering if its set.
            if (isset($filter_data['sort_column']) and $filter_data['sort_order']) {
                $order = array(Misc::trimSortPrefix($filter_data['sort_column']) => $filter_data['sort_order']);
            }
        }

        $additional_order_fields = array('first_name', 'last_name', 'title', 'user_group', 'default_branch', 'default_department', 'recurring_schedule_template_control', 'recurring_schedule_template_control_description');
        if ($order == null) {
            $order = array('recurring_schedule_template_control_id' => 'asc',);
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
        $rsuf = new RecurringScheduleUserFactory();
        $rstcf = new RecurringScheduleTemplateControlFactory();

        $ph = array(
            'company_id' => (int)$company_id,
        );

        $query = '
					select	a.*,
							ac.user_id as user_id,
							ab.name as recurring_schedule_template_control,
							ab.description as recurring_schedule_template_control_description,
							b.first_name as first_name,
							b.last_name as last_name,
							b.country as country,
							b.province as province,

							c.id as default_branch_id,
							c.name as default_branch,
							d.id as default_department_id,
							d.name as default_department,
							e.id as group_id,
							e.name as user_group,
							f.id as title_id,
							f.name as title
					from	' . $this->getTable() . ' as a
						LEFT JOIN ' . $rstcf->getTable() . ' as ab ON ( a.recurring_schedule_template_control_id = ab.id )
						LEFT JOIN ' . $rsuf->getTable() . ' as ac ON a.id = ac.recurring_schedule_control_id
						LEFT JOIN ' . $uf->getTable() . ' as b ON ( ac.user_id = b.id AND b.deleted = 0 )
						LEFT JOIN ' . $bf->getTable() . ' as c ON ( b.default_branch_id = c.id AND c.deleted = 0)
						LEFT JOIN ' . $df->getTable() . ' as d ON ( b.default_department_id = d.id AND d.deleted = 0)
						LEFT JOIN ' . $ugf->getTable() . ' as e ON ( b.group_id = e.id AND e.deleted = 0 )
						LEFT JOIN ' . $utf->getTable() . ' as f ON ( b.title_id = f.id AND f.deleted = 0 )
						LEFT JOIN ' . $uf->getTable() . ' as y ON ( a.created_by = y.id AND y.deleted = 0 )
						LEFT JOIN ' . $uf->getTable() . ' as z ON ( a.updated_by = z.id AND z.deleted = 0 )
					where	a.company_id = ?
					';

        $query .= (isset($filter_data['permission_children_ids'])) ? $this->getWhereClauseSQL('ac.user_id', $filter_data['permission_children_ids'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['id'])) ? $this->getWhereClauseSQL('a.id', $filter_data['id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['exclude_id'])) ? $this->getWhereClauseSQL('ac.user_id', $filter_data['exclude_id'], 'not_numeric_list', $ph) : null;

        $query .= (isset($filter_data['user_id'])) ? $this->getWhereClauseSQL('ac.user_id', $filter_data['user_id'], 'numeric_list', $ph) : null;

        $query .= (isset($filter_data['recurring_schedule_template_control_id'])) ? $this->getWhereClauseSQL('a.recurring_schedule_template_control_id', $filter_data['recurring_schedule_template_control_id'], 'numeric_list', $ph) : null;

        $query .= (isset($filter_data['status_id'])) ? $this->getWhereClauseSQL('b.status_id', $filter_data['status_id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['group_id'])) ? $this->getWhereClauseSQL('b.group_id', $filter_data['group_id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['default_branch_id'])) ? $this->getWhereClauseSQL('b.default_branch_id', $filter_data['default_branch_id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['default_department_id'])) ? $this->getWhereClauseSQL('b.default_department_id', $filter_data['default_department_id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['title_id'])) ? $this->getWhereClauseSQL('b.title_id', $filter_data['title_id'], 'numeric_list', $ph) : null;

        $query .= (isset($filter_data['country'])) ? $this->getWhereClauseSQL('b.country', $filter_data['country'], 'upper_text_list', $ph) : null;
        $query .= (isset($filter_data['province'])) ? $this->getWhereClauseSQL('b.province', $filter_data['province'], 'upper_text_list', $ph) : null;

        $query .= (isset($filter_data['created_by'])) ? $this->getWhereClauseSQL(array('a.created_by', 'y.first_name', 'y.last_name'), $filter_data['created_by'], 'user_id_or_name', $ph) : null;
        $query .= (isset($filter_data['updated_by'])) ? $this->getWhereClauseSQL(array('a.updated_by', 'z.first_name', 'z.last_name'), $filter_data['updated_by'], 'user_id_or_name', $ph) : null;

        $query .= ' AND ( a.deleted = 0 AND ab.deleted = 0 ) ';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict, $additional_order_fields);

        //Debug::Arr($ph, 'Query: '. $query, __FILE__, __LINE__, __METHOD__, 10);

        $this->ExecuteSQL($query, $ph, $limit, $page);

        return $this;
    }
}
