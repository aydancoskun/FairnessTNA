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
 * @package Modules\KPI
 */
class UserReviewControlListFactory extends UserReviewControlFactory implements IteratorAggregate
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


    public function getByUserId($user_id, $order = null)
    {
        if ($user_id == '') {
            return false;
        }

        $ph = array(
            'user_id' => (int)$user_id,
        );

        $query = '
					select	*
					from	' . $this->getTable() . '
					where	user_id = ?
						AND deleted = 0';
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByIdAndUserId($id, $user_id, $where = null, $order = null)
    {
        if ($id == '') {
            return false;
        }

        $this->rs = $this->getCache($id . $user_id);
        if ($this->rs === false) {
            $ph = array(
                'id' => (int)$id,
                'user_id' => (int)$user_id,
            );

            $query = '
						select	*
						from	' . $this->getTable() . '
						where	id = ?
							AND user_id = ?
							AND deleted = 0';
            $query .= $this->getWhereSQL($where);
            $query .= $this->getSortSQL($order);

            $this->ExecuteSQL($query, $ph);

            $this->saveCache($this->rs, $id . $user_id);
        }

        return $this;
    }

    public function getByCompanyId($company_id, $limit = null, $page = null, $where = null, $order = null)
    {
        if ($company_id == '') {
            return false;
        }

        if ($order == null) {
            $order = array('a.due_date' => 'desc');
            $strict = false;
        } else {
            $strict = true;
        }

        $uf = new UserFactory();

        $ph = array(
            'company_id' => (int)$company_id
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a
						LEFT JOIN  ' . $uf->getTable() . ' as u ON ( a.user_id = u.id AND u.deleted = 0 )
					where	u.company_id = ?
						AND a.deleted = 0';

        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict);

        $this->ExecuteSQL($query, $ph, $limit, $page);

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
					from	' . $this->getTable() . ' as a
						LEFT JOIN  ' . $uf->getTable() . ' as u ON ( a.user_id = u.id AND u.deleted = 0 )
					where	a.id = ?
						AND u.company_id = ?
						AND a.deleted = 0';
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

        if (isset($filter_data['user_review_control_status_id'])) {
            $filter_data['status_id'] = $filter_data['user_review_control_status_id'];
        }
        if (isset($filter_data['user_review_control_type_id'])) {
            $filter_data['type_id'] = $filter_data['user_review_control_type_id'];
        }
        if (isset($filter_data['include_user_id'])) {
            $filter_data['user_id'] = $filter_data['include_user_id'];
        }
        if (isset($filter_data['exclude_user_id'])) {
            $filter_data['exclude_id'] = $filter_data['exclude_user_id'];
        }
        if (isset($filter_data['include_reviewer_user_id'])) {
            $filter_data['reviewer_user_id'] = $filter_data['include_reviewer_user_id'];
        }
        if (isset($filter_data['review_tag'])) {
            $filter_data['tag'] = $filter_data['review_tag'];
        }

        if (!is_array($order)) {
            //Use Filter Data ordering if its set.
            if (isset($filter_data['sort_column']) and $filter_data['sort_order']) {
                $order = array(Misc::trimSortPrefix($filter_data['sort_column']) => $filter_data['sort_order']);
            }
        }

        $additional_order_fields = array('uf.first_name', 'u.first_name', 'status_id', 'type_id', 'term_id', 'severity_id');

        $sort_column_aliases = array(
            'type' => 'type_id',
            'status' => 'status_id',
            'term' => 'term_id',
            'severity' => 'severity_id',
        );

        $order = $this->getColumnsFromAliases($order, $sort_column_aliases);

        if ($order == null) {
            $order = array('uf.first_name' => 'asc');
            $strict = false;
        } else {
            if (isset($order['user'])) {
                $order['uf.first_name'] = $order['user'];
                unset($order['user']);
            }
            if (isset($order['first_name'])) {
                $order['uf.first_name'] = $order['first_name'];
                unset($order['first_name']);
            }
            if (isset($order['last_name'])) {
                $order['uf.last_name'] = $order['last_name'];
                unset($order['last_name']);
            }
            if (isset($order['reviewer_user'])) {
                $order['u.first_name'] = $order['reviewer_user'];
                unset($order['reviewer_user']);
            }
            $strict = true;
        }
        //Debug::Arr($order, 'Order Data:', __FILE__, __LINE__, __METHOD__, 10);
        //Debug::Arr($filter_data, 'Filter Data:', __FILE__, __LINE__, __METHOD__, 10);

        $uf = new UserFactory();
        $urf = new UserReviewFactory();

        $ph = array(
            'company_id' => (int)$company_id,
        );

        $query = '
					select	distinct a.*,
							uf.first_name as user_first_name,
							uf.last_name as user_last_name,
							u.first_name as reviewer_user_first_name,
							u.last_name as reviewer_user_last_name,

							y.first_name as created_by_first_name,
							y.middle_name as created_by_middle_name,
							y.last_name as created_by_last_name,
							z.first_name as updated_by_first_name,
							z.middle_name as updated_by_middle_name,
							z.last_name as updated_by_last_name
					from	' . $this->getTable() . ' as a
						LEFT JOIN ' . $urf->getTable() . ' as urf ON ( urf.user_review_control_id = a.id AND urf.deleted = 0 )
						LEFT JOIN ' . $uf->getTable() . ' as uf ON ( a.user_id = uf.id AND uf.deleted = 0)
						LEFT JOIN ' . $uf->getTable() . ' as u ON ( a.reviewer_user_id = u.id AND u.deleted = 0 )
						LEFT JOIN ' . $uf->getTable() . ' as y ON ( a.created_by = y.id AND y.deleted = 0 )
						LEFT JOIN ' . $uf->getTable() . ' as z ON ( a.updated_by = z.id AND z.deleted = 0 )
					where	uf.company_id = ? ';

        $query .= (isset($filter_data['permission_children_ids'])) ? $this->getWhereClauseSQL('a.user_id', $filter_data['permission_children_ids'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['user_id'])) ? $this->getWhereClauseSQL('a.user_id', $filter_data['user_id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['id'])) ? $this->getWhereClauseSQL('a.id', $filter_data['id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['exclude_id'])) ? $this->getWhereClauseSQL('a.user_id', $filter_data['exclude_id'], 'not_numeric_list', $ph) : null;

        if (isset($filter_data['status']) and !is_array($filter_data['status']) and trim($filter_data['status']) != '' and !isset($filter_data['status_id'])) {
            $filter_data['status_id'] = Option::getByFuzzyValue($filter_data['status'], $this->getOptions('status'));
        }
        if (isset($filter_data['type']) and !is_array($filter_data['type']) and trim($filter_data['type']) != '' and !isset($filter_data['type_id'])) {
            $filter_data['type_id'] = Option::getByFuzzyValue($filter_data['type'], $this->getOptions('type'));
        }
        if (isset($filter_data['term']) and !is_array($filter_data['term']) and trim($filter_data['term']) != '' and !isset($filter_data['term_id'])) {
            $filter_data['term_id'] = Option::getByFuzzyValue($filter_data['term'], $this->getOptions('term'));
        }
        if (isset($filter_data['severity']) and !is_array($filter_data['severity']) and trim($filter_data['severity']) != '' and !isset($filter_data['severity_id'])) {
            $filter_data['severity_id'] = Option::getByFuzzyValue($filter_data['severity'], $this->getOptions('severity'));
        }
        $query .= (isset($filter_data['reviewer_user_id'])) ? $this->getWhereClauseSQL('a.reviewer_user_id', $filter_data['reviewer_user_id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['reviewer_user'])) ? $this->getWhereClauseSQL(array('a.reviewer_user_id', 'uf.first_name', 'uf.last_name'), $filter_data['reviewer_user'], 'user_id_or_name', $ph) : null;

        $query .= (isset($filter_data['user'])) ? $this->getWhereClauseSQL(array('a.user_id', 'uf.first_name', 'uf.last_name'), $filter_data['user'], 'user_id_or_name', $ph) : null;
        $query .= (isset($filter_data['exclude_reviewer_user_id'])) ? $this->getWhereClauseSQL('a.reviewer_user_id', $filter_data['exclude_reviewer_user_id'], 'not_numeric_list', $ph) : null;
        $query .= (isset($filter_data['status_id'])) ? $this->getWhereClauseSQL('a.status_id', $filter_data['status_id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['type_id'])) ? $this->getWhereClauseSQL('a.type_id', $filter_data['type_id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['term_id'])) ? $this->getWhereClauseSQL('a.term_id', $filter_data['term_id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['severity_id'])) ? $this->getWhereClauseSQL('a.severity_id', $filter_data['severity_id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['tag'])) ? $this->getWhereClauseSQL('a.id', array('company_id' => (int)$company_id, 'object_type_id' => 320, 'tag' => $filter_data['tag']), 'tag', $ph) : null;
        $query .= (isset($filter_data['due_date'])) ? $this->getWhereClauseSQL('a.due_date', $filter_data['due_date'], 'date_range', $ph) : null;
        $query .= (isset($filter_data['kpi_id'])) ? $this->getWhereClauseSQL('urf.kpi_id', $filter_data['kpi_id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['rating'])) ? $this->getWhereClauseSQL('a.rating', $filter_data['rating'], 'numeric', $ph) : null;
        $query .= (isset($filter_data['note'])) ? $this->getWhereClauseSQL('a.note', $filter_data['note'], 'text', $ph) : null;

        if (isset($filter_data['time_period'])) {
            $query .= (isset($filter_data['start_date'])) ? $this->getWhereClauseSQL('a.due_date', $filter_data['start_date'], 'start_date', $ph) : null;
            $query .= (isset($filter_data['end_date'])) ? $this->getWhereClauseSQL('a.due_date', $filter_data['end_date'], 'end_date', $ph) : null;
            /*
            if ( isset($filter_data['start_date']) AND !is_array($filter_data['start_date']) AND trim($filter_data['start_date']) != '' ) {
                $ph[] = (int)$filter_data['start_date'];
                $query	.=	' AND a.due_date >= ?';
            }
            if ( isset($filter_data['end_date']) AND !is_array($filter_data['end_date']) AND trim($filter_data['end_date']) != '' ) {
                $ph[] = (int)$filter_data['end_date'];
                $query	.=	' AND a.due_date <= ?';
            }
            */
        } else {
            $query .= (isset($filter_data['start_date'])) ? $this->getWhereClauseSQL('a.start_date', $filter_data['start_date'], 'date_range', $ph) : null;
            $query .= (isset($filter_data['end_date'])) ? $this->getWhereClauseSQL('a.end_date', $filter_data['end_date'], 'date_range', $ph) : null;
        }

        $query .= (isset($filter_data['created_date'])) ? $this->getWhereClauseSQL('a.created_date', $filter_data['created_date'], 'date_range', $ph) : null;
        $query .= (isset($filter_data['updated_date'])) ? $this->getWhereClauseSQL('a.updated_date', $filter_data['updated_date'], 'date_range', $ph) : null;
        $query .= (isset($filter_data['created_by'])) ? $this->getWhereClauseSQL(array('a.created_by', 'y.first_name', 'y.last_name'), $filter_data['created_by'], 'user_id_or_name', $ph) : null;
        $query .= (isset($filter_data['updated_by'])) ? $this->getWhereClauseSQL(array('a.updated_by', 'z.first_name', 'z.last_name'), $filter_data['updated_by'], 'user_id_or_name', $ph) : null;

        $query .= ' AND a.deleted = 0 ';

        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict, $additional_order_fields);

        $this->ExecuteSQL($query, $ph, $limit, $page);

        //Debug::Arr($ph, 'Query: '. $query, __FILE__, __LINE__, __METHOD__, 10);

        return $this;
    }
}
