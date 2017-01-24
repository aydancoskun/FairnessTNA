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
 * @package Modules\Users
 */
class UserPreferenceListFactory extends UserPreferenceFactory implements IteratorAggregate
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

    public function getUniqueLanguageByCompanyId($id, $where = null, $order = null)
    {
        if ($id == '') {
            return false;
        }

        $uf = new UserFactory();

        $ph = array(
            'id' => (int)$id,
        );


        $query = '
					select	distinct a.language
					from	' . $this->getTable() . ' as a,
							' . $uf->getTable() . ' as b
					where	a.user_id = b.id
						AND b.company_id = ?
						AND ( a.deleted = 0 AND b.deleted = 0 )';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        return $this->db->GetCol($query, $ph);
    }

    public function getByUserId($id, $where = null, $order = null)
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
						where	user_id in (' . $this->getListSQL($id, $ph, 'int') . ')
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

    public function getByUserIdAndStatus($id, $status_id = 10, $where = null, $order = null)
    {
        if ($id == '') {
            return false;
        }

        if ($status_id == '') {
            return false;
        }

        if (is_array($id)) {
            $this->rs = false;
        } else {
            $this->rs = $this->getCache($id);
        }

        if ($this->rs === false) {
            $uf = new UserFactory();

            $ph = array();

            $query = '
						SELECT	*
						FROM	' . $this->getTable() . ' as a
						LEFT JOIN ' . $uf->getTable() . ' as b ON ( a.user_id = b.id )
						WHERE	a.user_id in (' . $this->getListSQL($id, $ph, 'int') . ')
							AND b.status_id in (' . $this->getListSQL($status_id, $ph, 'int') . ')
							AND ( a.deleted = 0 AND b.deleted = 0 ) ';
            $query .= $this->getWhereSQL($where);
            $query .= $this->getSortSQL($order);

            $this->ExecuteSQL($query, $ph);

            if (!is_array($id)) {
                $this->saveCache($this->rs, $id);
            }
        }

        return $this;
    }

    public function getByCompanyId($company_id, $order = null)
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
					from	' . $this->getTable() . ' as a,
							' . $uf->getTable() . ' as b
					where	a.user_id = b.id
						AND	b.company_id = ?
						AND ( a.deleted = 0 AND b.deleted = 0 )';
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

        $uf = new UserFactory();

        $ph = array(
            'company_id' => (int)$company_id,
            'id' => (int)$id,
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a,
							' . $uf->getTable() . ' as b
					where	a.user_id = b.id
						AND	b.company_id = ?
						AND	a.id = ?
						AND a.deleted = 0';
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByUserIDAndCompanyID($id, $company_id, $where = null, $order = null)
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
            'company_id' => (int)$company_id,
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a,
							' . $uf->getTable() . ' as b
					where	a.user_id = b.id
						AND	a.user_id = ?
						AND b.company_id = ?
						AND (a.deleted = 0 AND b.deleted = 0)';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getIsModifiedByCompanyIdAndDate($company_id, $date, $where = null, $order = null)
    {
        if ($company_id == '') {
            return false;
        }

        if ($date == '') {
            return false;
        }

        $ph = array(
            'company_id' => (int)$company_id,
            'created_date' => $date,
            'updated_date' => $date,
            'deleted_date' => $date,
        );

        $uf = new UserFactory();

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a
					LEFT JOIN ' . $uf->getTable() . ' as b ON a.user_id = b.id
					where
							b.company_id = ?
						AND
							( a.created_date >= ? OR a.updated_date >= ? OR ( a.deleted = 1 AND a.deleted_date >= ? ) )
					';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->rs = $this->db->SelectLimit($query, 1, -1, $ph);
        if ($this->getRecordCount() > 0) {
            Debug::text('User preference rows have been modified: ' . $this->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);

            return true;
        }

        Debug::text('User preference rows have NOT been modified', __FILE__, __LINE__, __METHOD__, 10);

        return false;
    }

    public function getByCompanyIdAndDateAndValidUserIDs($company_id, $date = null, $valid_user_ids = array(), $order = null)
    {
        if ($company_id == '') {
            return false;
        }

        if ($date == '') {
            $date = 0;
        }

        if ($order == null) {
            $order = array('a.user_id' => 'asc');
            $strict = false;
        } else {
            $strict = true;
        }

        $uf = new UserFactory();

        $ph = array(
            'company_id' => (int)$company_id,
        );

        //If the user record is modified, we have to consider the identification record to be modified as well,
        //otherwise a terminated employee re-hired will not have their old prox/fingerprint records put back on the clock.
        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a,
							' . $uf->getTable() . ' as b
					where	a.user_id = b.id
						AND	b.company_id = ?
						AND b.status_id = 10
				';

        if ((isset($date) and $date > 0) or (isset($valid_user_ids) and is_array($valid_user_ids) and count($valid_user_ids) > 0)) {
            $query .= ' AND ( ';

            //When the Mobile App/TimeClock are doing a reload database, $date should always be 0. That forces the query to just send data for $valid_user_ids.
            //  All other cases it will send data for all current users always, or records that were recently created/updated.
            if (isset($date) and $date > 0) {
                //Append the same date twice for created and updated.
                $ph[] = (int)$date;
                $ph[] = (int)$date;
                $ph[] = (int)$date;
                $ph[] = (int)$date;
                $query .= '	( ( a.created_date >= ? OR a.updated_date >= ? ) OR ( b.created_date >= ? OR b.updated_date >= ? ) ) ';
            }

            //Valid USER IDs is an "OR", so if any IDs are specified they should *always* be included, regardless of the $date variable.
            if (isset($valid_user_ids) and is_array($valid_user_ids) and count($valid_user_ids) > 0) {
                if (isset($date) and $date > 0) {
                    $query .= ' OR ';
                }
                $query .= ' a.user_id in (' . $this->getListSQL($valid_user_ids, $ph, 'int') . ') ';
            }

            $query .= ' ) ';
        }

        $query .= ' AND ( a.deleted = 0 AND b.deleted = 0 )';

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

        if (isset($filter_data['include_user_id'])) {
            $filter_data['user_id'] = $filter_data['include_user_id'];
        }
        if (isset($filter_data['exclude_user_id'])) {
            $filter_data['exclude_id'] = $filter_data['exclude_user_id'];
        }

        $additional_order_fields = array('user_status_id', 'last_name', 'first_name', 'default_branch', 'default_department', 'user_group', 'title', 'city', 'province', 'country');

        $sort_column_aliases = array(
            'user_status' => 'user_status_id',
            'type' => 'type_id',
            'language_display' => 'language',
            'start_week_day_display' => 'start_week_day',
            'date_format_display' => 'date_format',
            'time_format_display' => 'time_format',
            'time_unit_format_display' => 'time_unit_format',
            'distance_format_display' => 'distance_format',
            'time_zone_display' => 'time_zone',
        );
        $order = $this->getColumnsFromAliases($order, $sort_column_aliases);
        if ($order == null) {
            $order = array('last_name' => 'asc');
            $strict = false;
        } else {
            //Always sort by last name, first name after other columns
            if (!isset($order['last_name'])) {
                $order['last_name'] = 'asc';
            }
            $strict = true;
        }
        //Debug::Arr($order, 'Order Data:', __FILE__, __LINE__, __METHOD__, 10);
        //Debug::Arr($filter_data, 'Filter Data:', __FILE__, __LINE__, __METHOD__, 10);

        $uf = new UserFactory();
        $bf = new BranchFactory();
        $df = new DepartmentFactory();
        $ugf = new UserGroupFactory();
        $utf = new UserTitleFactory();

        $ph = array(
            'company_id' => (int)$company_id,
        );

        $query = '
					select	a.*,
							b.first_name as first_name,
							b.last_name as last_name,
							b.user_name as user_name,
							b.status_id as user_status_id,

							b.default_branch_id as default_branch_id,
							bf.name as default_branch,
							b.default_department_id as default_department_id,
							df.name as default_department,
							b.group_id as group_id,
							ugf.name as user_group,
							b.title_id as title_id,
							utf.name as title,

							b.city as city,
							b.province as province,
							b.country as country,

							y.first_name as created_by_first_name,
							y.middle_name as created_by_middle_name,
							y.last_name as created_by_last_name,
							z.first_name as updated_by_first_name,
							z.middle_name as updated_by_middle_name,
							z.last_name as updated_by_last_name
					from	' . $this->getTable() . ' as a
						LEFT JOIN ' . $uf->getTable() . ' as b ON ( a.user_id = b.id AND b.deleted = 0 )
						LEFT JOIN ' . $bf->getTable() . ' as bf ON ( b.default_branch_id = bf.id AND bf.deleted = 0)
						LEFT JOIN ' . $df->getTable() . ' as df ON ( b.default_department_id = df.id AND df.deleted = 0)
						LEFT JOIN ' . $ugf->getTable() . ' as ugf ON ( b.group_id = ugf.id AND ugf.deleted = 0 )
						LEFT JOIN ' . $utf->getTable() . ' as utf ON ( b.title_id = utf.id AND utf.deleted = 0 )

						LEFT JOIN ' . $uf->getTable() . ' as y ON ( a.created_by = y.id AND y.deleted = 0 )
						LEFT JOIN ' . $uf->getTable() . ' as z ON ( a.updated_by = z.id AND z.deleted = 0 )
					where	b.company_id = ?';

        $query .= (isset($filter_data['permission_children_ids'])) ? $this->getWhereClauseSQL('b.id', $filter_data['permission_children_ids'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['user_id'])) ? $this->getWhereClauseSQL('b.id', $filter_data['user_id'], 'numeric_list', $ph) : null;

        $query .= (isset($filter_data['id'])) ? $this->getWhereClauseSQL('a.id', $filter_data['id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['exclude_id'])) ? $this->getWhereClauseSQL('a.id', $filter_data['exclude_id'], 'not_numeric_list', $ph) : null;

        if (isset($filter_data['status']) and !is_array($filter_data['status']) and trim($filter_data['status']) != '' and !isset($filter_data['status_id'])) {
            $filter_data['status_id'] = Option::getByFuzzyValue($filter_data['status'], $this->getOptions('status'));
        }
        $query .= (isset($filter_data['status_id'])) ? $this->getWhereClauseSQL('b.status_id', $filter_data['status_id'], 'numeric_list', $ph) : null;

        if (isset($filter_data['include_subgroups']) and (bool)$filter_data['include_subgroups'] == true) {
            $uglf = new UserGroupListFactory();
            $filter_data['group_id'] = $uglf->getByCompanyIdAndGroupIdAndSubGroupsArray($company_id, $filter_data['group_id'], true);
        }
        $query .= (isset($filter_data['group_id'])) ? $this->getWhereClauseSQL('b.group_id', $filter_data['group_id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['group'])) ? $this->getWhereClauseSQL('ugf.name', $filter_data['group'], 'text', $ph) : null;

        $query .= (isset($filter_data['default_branch_id'])) ? $this->getWhereClauseSQL('b.default_branch_id', $filter_data['default_branch_id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['default_branch'])) ? $this->getWhereClauseSQL('bf.name', $filter_data['default_branch'], 'text', $ph) : null;

        $query .= (isset($filter_data['default_department_id'])) ? $this->getWhereClauseSQL('b.default_department_id', $filter_data['default_department_id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['default_department'])) ? $this->getWhereClauseSQL('df.name', $filter_data['default_department'], 'text', $ph) : null;

        $query .= (isset($filter_data['title_id'])) ? $this->getWhereClauseSQL('b.title_id', $filter_data['title_id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['title'])) ? $this->getWhereClauseSQL('utf.name', $filter_data['title'], 'text', $ph) : null;

        if (isset($filter_data['sex']) and !is_array($filter_data['sex']) and trim($filter_data['sex']) != '' and !isset($filter_data['sex_id'])) {
            $filter_data['sex_id'] = Option::getByFuzzyValue($filter_data['sex'], $this->getOptions('sex'));
        }
        $query .= (isset($filter_data['sex_id'])) ? $this->getWhereClauseSQL('b.sex_id', $filter_data['sex_id'], 'numeric_list', $ph) : null;

        $query .= (isset($filter_data['first_name'])) ? $this->getWhereClauseSQL('b.first_name', $filter_data['first_name'], 'text_metaphone', $ph) : null;
        $query .= (isset($filter_data['last_name'])) ? $this->getWhereClauseSQL('b.last_name', $filter_data['last_name'], 'text_metaphone', $ph) : null;
        $query .= (isset($filter_data['home_phone'])) ? $this->getWhereClauseSQL('b.home_phone', $filter_data['home_phone'], 'phone', $ph) : null;
        $query .= (isset($filter_data['work_phone'])) ? $this->getWhereClauseSQL('b.work_phone', $filter_data['work_phone'], 'phone', $ph) : null;
        $query .= (isset($filter_data['country'])) ? $this->getWhereClauseSQL('b.country', $filter_data['country'], 'upper_text_list', $ph) : null;
        $query .= (isset($filter_data['province'])) ? $this->getWhereClauseSQL('b.province', $filter_data['province'], 'upper_text_list', $ph) : null;
        $query .= (isset($filter_data['city'])) ? $this->getWhereClauseSQL('b.city', $filter_data['city'], 'text', $ph) : null;
        $query .= (isset($filter_data['address1'])) ? $this->getWhereClauseSQL('b.address1', $filter_data['address1'], 'text', $ph) : null;
        $query .= (isset($filter_data['address2'])) ? $this->getWhereClauseSQL('b.address2', $filter_data['address2'], 'text', $ph) : null;
        $query .= (isset($filter_data['postal_code'])) ? $this->getWhereClauseSQL('b.postal_code', $filter_data['postal_code'], 'text', $ph) : null;
        $query .= (isset($filter_data['employee_number'])) ? $this->getWhereClauseSQL('b.employee_number', $filter_data['employee_number'], 'numeric', $ph) : null;
        $query .= (isset($filter_data['user_name'])) ? $this->getWhereClauseSQL('b.user_name', $filter_data['user_name'], 'text', $ph) : null;
        $query .= (isset($filter_data['sin'])) ? $this->getWhereClauseSQL('b.sin', $filter_data['sin'], 'numeric_string', $ph) : null;

        $query .= (isset($filter_data['work_email'])) ? $this->getWhereClauseSQL('b.work_email', $filter_data['work_email'], 'text', $ph) : null;
        $query .= (isset($filter_data['home_email'])) ? $this->getWhereClauseSQL('b.home_email', $filter_data['home_email'], 'text', $ph) : null;

        $query .= (isset($filter_data['created_by'])) ? $this->getWhereClauseSQL(array('a.created_by', 'y.first_name', 'y.last_name'), $filter_data['created_by'], 'user_id_or_name', $ph) : null;
        $query .= (isset($filter_data['updated_by'])) ? $this->getWhereClauseSQL(array('a.updated_by', 'z.first_name', 'z.last_name'), $filter_data['updated_by'], 'user_id_or_name', $ph) : null;

        $query .= ' AND a.deleted = 0 ';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict, $additional_order_fields);

        $this->ExecuteSQL($query, $ph, $limit, $page);

        return $this;
    }
}
