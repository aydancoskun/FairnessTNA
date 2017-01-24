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
class UserContactListFactory extends UserContactFactory implements IteratorAggregate
{
    public static function getFullNameById($id)
    {
        if ($id == '') {
            return false;
        }

        $ulf = new UserListFactory();
        $ulf = $ulf->getById($id);
        if ($ulf->getRecordCount() > 0) {
            $u_obj = $ulf->getCurrent();
            return $u_obj->getFullName();
        }

        return false;
    }

    public function getAll($limit = null, $page = null, $where = null, $order = null)
    {
        if ($order == null) {
            $order = array();
            $strict = false;
        } else {
            $strict = true;
        }

        $query = '
					select	*
					from	' . $this->getTable() . '
					WHERE deleted = 0';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict);

        $this->ExecuteSQL($query, null, $limit, $page);

        return $this;
    }

    public function getByStatus($status, $where = null, $order = null)
    {
        $ph = array(
            'status_id' => $status,
        );

        $query = '
					select	*
					from	' . $this->getTable() . '
					where
						status_id = ?
						AND deleted = 0';

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByCompanyIdAndStatus($company_id, $status, $where = null, $order = null)
    {
        $uf = TTnew('UserFactory');

        $ph = array(
            'company_id' => (int)$company_id,
            'status_id' => $status,
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a
					LEFT JOIN ' . $uf->getTable() . ' as u ON ( u.id = a.user_id AND u.deleted = 0 )
					where
						u.company_id = ?
						AND a.status_id = ?
						AND a.deleted = 0';

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getById($id)
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

            $this->ExecuteSQL($query, $ph);

            $this->saveCache($this->rs, $id);
        }

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
            $order = array('status_id' => 'asc', 'last_name' => 'asc');
            $strict = false;
        } else {
            $strict = true;
        }

        $uf = TTnew('UserFactory');
        $ph = array(
            'company_id' => (int)$company_id,
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a
					LEFT JOIN ' . $uf->getTable() . ' as u ON	 ( u.id = a.user_id AND u.deleted = 0 )
					where	u.company_id = ?
						AND	a.id in (' . $this->getListSQL($id, $ph, 'int') . ')
						AND a.deleted = 0';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict);

        $this->ExecuteSQL($query, $ph, $limit, $page);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }


    public function getByHomeEmailOrWorkEmail($email)
    {
        $email = trim(strtolower($email));

        if ($email == '') {
            return false;
        }

        if ($this->Validator->isEmail('email', $email) == false) {
            return false;
        }

        $ph = array(
            'home_email' => $email,
            'work_email' => $email,
        );

        $query = '
					select	*
					from	' . $this->getTable() . '
					where
						( lower(home_email) = ?
							OR lower(work_email) = ? )
						AND deleted = 0';

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByIdAndStatus($id, $status, $where = null, $order = null)
    {
        if ($id == '') {
            return false;
        }

        $ph = array(
            'id' => (int)$id,
            'status' => $status,
        );

        $query = '
					select	*
					from	' . $this->getTable() . '
					where	id = ?
						AND status_id = ?
						AND deleted = 0';

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByCompanyIdArray($company_id, $include_blank = true, $include_disabled = true, $last_name_first = true)
    {
        $uclf = new UserContactListFactory();
        $uclf->getByCompanyId($company_id);

        $user_list = array();
        if ($include_blank == true) {
            $user_list[0] = '--';
        }

        foreach ($uclf as $user) {
            if ($user->getStatus() > 10) { //ENABLE
                $status = '(' . Option::getByKey($user->getStatus(), $user->getOptions('status')) . ') ';
            } else {
                $status = null;
            }

            if ($include_disabled == true or ($include_disabled == false and $user->getStatus() == 10)) {
                $user_list[$user->getID()] = $status . $user->getFullName($last_name_first);
            }
        }

        if (empty($user_list) == false) {
            return $user_list;
        }

        return false;
    }

    public function getByCompanyId($company_id, $limit = null, $page = null, $where = null, $order = null)
    {
        if ($company_id == '') {
            return false;
        }

        if ($order == null) {
            $order = array('status_id' => 'asc', 'last_name' => 'asc');
            $strict = false;
        } else {
            $strict = true;
        }

        $uf = TTnew('UserFactory');
        $ph = array(
            'company_id' => (int)$company_id,
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a
					LEFT JOIN ' . $uf->getTable() . ' as u ON ( u.id = a.user_id AND u.deleted = 0  )
					where	u.company_id = ?
						AND a.deleted = 0';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict);

        $this->ExecuteSQL($query, $ph, $limit, $page);

        return $this;
    }

    public function getArrayByListFactory($lf, $include_blank = true, $include_disabled = true)
    {
        if (!is_object($lf)) {
            return false;
        }

        $list = array();
        if ($include_blank == true) {
            $list[0] = '--';
        }
        $status_options = array();
        foreach ($lf as $obj) {
            if (!isset($status_options)) {
                $status_options = $obj->getOptions('status');
            }

            if ($obj->getStatus() > 10) { //ENABLE
                $status = '(' . Option::getByKey($obj->getStatus(), $status_options) . ') ';
            } else {
                $status = null;
            }

            if ($include_disabled == true or ($include_disabled == false and $obj->getStatus() == 10)) {
                $list[$obj->getID()] = $status . $obj->getFullName(true);
            }
        }

        if (empty($list) == false) {
            return $list;
        }

        return false;
    }

    public function getDeletedByCompanyIdAndDate($company_id, $date, $limit = null, $page = null, $where = null, $order = null)
    {
        if ($company_id == '') {
            return false;
        }

        if ($date == '') {
            return false;
        }
        $uf = TTnew('UserFactory');
        $ph = array(
            'company_id' => (int)$company_id,
            'created_date' => $date,
            'updated_date' => $date,
            'deleted_date' => $date,
        );

        //INCLUDE Deleted rows in this query.
        $query = '
					select	a.*
					from	' . $this->getTable() . ' as	a
					LEFT JOIN ' . $uf->getTable() . ' as	u ON ( u.id = a.user_id AND u.deleted = 0 )
					where
							u.company_id = ?
						AND
							( a.created_date >= ? OR a.updated_date >= ? OR a.deleted_date >= ? )
						AND a.deleted = 1
					';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

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

        $additional_order_fields = array('employee_first_name', 'employee_last_name', 'title', 'user_group', 'default_branch', 'default_department', 'type_id', 'sex_id', 'status_id');

        $sort_column_aliases = array(
            'type' => 'type_id',
            'status' => 'status_id',
            'sex' => 'sex_id',
        );

        $order = $this->getColumnsFromAliases($order, $sort_column_aliases);

        if ($order == null) {
            $order = array('employee_first_name' => 'asc', 'employee_last_name' => 'asc', 'last_name' => 'asc', 'first_name' => 'asc');
            $strict = false;
        } else {
            if (!isset($order['last_name'])) {
                $order['last_name'] = 'asc';
            }
            if (!isset($order['first_name'])) {
                $order['first_name'] = 'asc';
            }

            $strict = true;
        }

        //Debug::Arr($order, 'Order Data:', __FILE__, __LINE__, __METHOD__, 10);
        //Debug::Arr($filter_data, 'Filter Data:', __FILE__, __LINE__, __METHOD__, 10);

        $uf = TTnew('UserFactory');
        $bf = new BranchFactory();
        $df = new DepartmentFactory();
        $ugf = new UserGroupFactory();
        $utf = new UserTitleFactory();

        $ph = array(
            'company_id' => (int)$company_id,
        );

        $query = '
					select
							a.*,
							u.first_name as employee_first_name,
							u.last_name as employee_last_name,

							bf.id as default_branch_id,
							bf.name as default_branch,
							df.id as default_department_id,
							df.name as default_department,
							ugf.id as group_id,
							ugf.name as user_group,
							utf.id as title_id,
							utf.name as title,

							y.first_name as created_by_first_name,
							y.middle_name as created_by_middle_name,
							y.last_name as created_by_last_name,
							z.first_name as updated_by_first_name,
							z.middle_name as updated_by_middle_name,
							z.last_name as updated_by_last_name
					from	' . $this->getTable() . ' as a
						LEFT JOIN ' . $uf->getTable() . ' as u ON ( u.id = a.user_id AND u.deleted = 0 )
						LEFT JOIN ' . $bf->getTable() . ' as bf ON ( u.default_branch_id = bf.id AND bf.deleted = 0)
						LEFT JOIN ' . $df->getTable() . ' as df ON ( u.default_department_id = df.id AND df.deleted = 0)
						LEFT JOIN ' . $ugf->getTable() . ' as ugf ON ( u.group_id = ugf.id AND ugf.deleted = 0 )
						LEFT JOIN ' . $utf->getTable() . ' as utf ON ( u.title_id = utf.id AND utf.deleted = 0 )
						LEFT JOIN ' . $uf->getTable() . ' as y ON ( a.created_by = y.id AND y.deleted = 0 )
						LEFT JOIN ' . $uf->getTable() . ' as z ON ( a.updated_by = z.id AND z.deleted = 0 )
						where u.company_id = ?
						';

        $query .= (isset($filter_data['permission_children_ids'])) ? $this->getWhereClauseSQL('a.user_id', $filter_data['permission_children_ids'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['id'])) ? $this->getWhereClauseSQL('a.id', $filter_data['id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['user_id'])) ? $this->getWhereClauseSQL('a.user_id', $filter_data['user_id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['exclude_id'])) ? $this->getWhereClauseSQL('a.id', $filter_data['exclude_id'], 'not_numeric_list', $ph) : null;

        if (isset($filter_data['status']) and !is_array($filter_data['status']) and trim($filter_data['status']) != '' and !isset($filter_data['status_id'])) {
            $filter_data['status_id'] = Option::getByFuzzyValue($filter_data['status'], $this->getOptions('status'));
        }
        $query .= (isset($filter_data['status_id'])) ? $this->getWhereClauseSQL('a.status_id', $filter_data['status_id'], 'numeric_list', $ph) : null;

        if (isset($filter_data['type']) and !is_array($filter_data['type']) and trim($filter_data['type']) != '' and !isset($filter_data['status_id'])) {
            $filter_data['type_id'] = Option::getByFuzzyValue($filter_data['type'], $this->getOptions('type'));
        }
        $query .= (isset($filter_data['type_id'])) ? $this->getWhereClauseSQL('a.type_id', $filter_data['type_id'], 'numeric_list', $ph) : null;

        if (isset($filter_data['sex']) and !is_array($filter_data['sex']) and trim($filter_data['sex']) != '' and !isset($filter_data['sex_id'])) {
            $filter_data['sex_id'] = Option::getByFuzzyValue($filter_data['sex'], $this->getOptions('sex'));
        }
        $query .= (isset($filter_data['sex_id'])) ? $this->getWhereClauseSQL('a.sex_id', $filter_data['sex_id'], 'numeric_list', $ph) : null;

        $query .= (isset($filter_data['first_name'])) ? $this->getWhereClauseSQL('a.first_name', $filter_data['first_name'], 'text_metaphone', $ph) : null;
        $query .= (isset($filter_data['last_name'])) ? $this->getWhereClauseSQL('a.last_name', $filter_data['last_name'], 'text_metaphone', $ph) : null;
        $query .= (isset($filter_data['full_name'])) ? $this->getWhereClauseSQL('a.last_name', $filter_data['full_name'], 'text_metaphone', $ph) : null;
        $query .= (isset($filter_data['home_phone'])) ? $this->getWhereClauseSQL('a.home_phone', $filter_data['home_phone'], 'phone', $ph) : null;
        $query .= (isset($filter_data['work_phone'])) ? $this->getWhereClauseSQL('a.work_phone', $filter_data['work_phone'], 'phone', $ph) : null;
        $query .= (isset($filter_data['country'])) ? $this->getWhereClauseSQL('a.country', $filter_data['country'], 'upper_text_list', $ph) : null;
        $query .= (isset($filter_data['province'])) ? $this->getWhereClauseSQL('a.province', $filter_data['province'], 'upper_text_list', $ph) : null;
        $query .= (isset($filter_data['city'])) ? $this->getWhereClauseSQL('a.city', $filter_data['city'], 'text', $ph) : null;
        $query .= (isset($filter_data['address1'])) ? $this->getWhereClauseSQL('a.address1', $filter_data['address1'], 'text', $ph) : null;
        $query .= (isset($filter_data['address2'])) ? $this->getWhereClauseSQL('a.address2', $filter_data['address2'], 'text', $ph) : null;
        $query .= (isset($filter_data['postal_code'])) ? $this->getWhereClauseSQL('a.postal_code', $filter_data['postal_code'], 'text', $ph) : null;
        $query .= (isset($filter_data['sin'])) ? $this->getWhereClauseSQL('a.sin', $filter_data['sin'], 'numeric_string', $ph) : null;

        $query .= (isset($filter_data['work_email'])) ? $this->getWhereClauseSQL('a.work_email', $filter_data['work_email'], 'text', $ph) : null;
        $query .= (isset($filter_data['home_email'])) ? $this->getWhereClauseSQL('a.home_email', $filter_data['home_email'], 'text', $ph) : null;

        $query .= (isset($filter_data['tag'])) ? $this->getWhereClauseSQL('a.id', array('company_id' => (int)$company_id, 'object_type_id' => 230, 'tag' => $filter_data['tag']), 'tag', $ph) : null;

        if (isset($filter_data['created_date']) and !is_array($filter_data['created_date']) and trim($filter_data['created_date']) != '') {
            $date_filter = $this->getDateRangeSQL($filter_data['created_date'], 'a.created_date');
            if ($date_filter != false) {
                $query .= ' AND ' . $date_filter;
            }
            unset($date_filter);
        }
        if (isset($filter_data['updated_date']) and !is_array($filter_data['updated_date']) and trim($filter_data['updated_date']) != '') {
            $date_filter = $this->getDateRangeSQL($filter_data['updated_date'], 'a.updated_date');
            if ($date_filter != false) {
                $query .= ' AND ' . $date_filter;
            }
            unset($date_filter);
        }

        $query .= (isset($filter_data['created_by'])) ? $this->getWhereClauseSQL(array('a.created_by', 'y.first_name', 'y.last_name'), $filter_data['created_by'], 'user_id_or_name', $ph) : null;
        $query .= (isset($filter_data['updated_by'])) ? $this->getWhereClauseSQL(array('a.updated_by', 'z.first_name', 'z.last_name'), $filter_data['updated_by'], 'user_id_or_name', $ph) : null;

        $query .= ' AND ( a.deleted = 0 ) ';

        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict, $additional_order_fields);

        $this->ExecuteSQL($query, $ph, $limit, $page);

        return $this;
    }
}
