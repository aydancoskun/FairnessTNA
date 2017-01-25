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
class UserReviewListFactory extends UserReviewFactory implements IteratorAggregate
{
    public function getAll($limit = null, $page = null, $where = null, $order = null)
    {
        $urcf = new UserReviewControlFactory();
        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a
					LEFT JOIN ' . $urcf->getTable() . ' as urcf ON ( a.user_review_control_id = urcf.id )
					WHERE a.deleted = 0 AND urcf.deleted = 0';
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
        $urcf = new UserReviewControlFactory();
        $this->rs = $this->getCache($id);
        if ($this->rs === false) {
            $ph = array('id' => (int)$id,);
            $query = '
						select	a.*
						from	' . $this->getTable() . ' as a
						LEFT JOIN ' . $urcf->getTable() . ' as urcf ON ( a.user_review_control_id = urcf.id )
						where	a.id = ?
							AND a.deleted = 0 AND urcf.deleted = 0';
            $query .= $this->getWhereSQL($where);
            $query .= $this->getSortSQL($order);
            $this->ExecuteSQL($query, $ph);
            $this->saveCache($this->rs, $id);
        }

        return $this;
    }

    public function getByUserReviewControlId($id, $order = null)
    {
        if ($id == '') {
            return false;
        }
        $urcf = new UserReviewControlFactory();
        $ph = array('user_review_control_id' => (int)$id,);
        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a
					LEFT JOIN ' . $urcf->getTable() . ' as urcf ON ( a.user_review_control_id = urcf.id )
					where	a.user_review_control_id = ?
						AND a.deleted = 0 AND urcf.deleted = 0';
        $query .= $this->getSortSQL($order);
        $this->ExecuteSQL($query, $ph);

        return $this;
    }


    public function getByKpiId($id, $order = null)
    {
        if ($id == '') {
            return false;
        }
        $urcf = new UserReviewControlFactory();
        $ph = array('kpi_id' => (int)$id,);
        $query = '
                    select  *
                    from    ' . $this->getTable() . ' as a
                    LEFT JOIN ' . $urcf->getTable() . ' as urcf ON ( a.user_review_control_id = urcf.id )
                    where  a.kpi_id = ?
                        AND a.deleted = 0 AND urcf.deleted = 0';
        $query .= $this->getSortSQL($order);
        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByCompanyId($company_id, $where = null, $order = null)
    {
        if ($company_id == '') {
            return false;
        }
        $kf = new KPIFactory();
        $urcf = new UserReviewControlFactory();
        $ph = array('company_id' => (int)$company_id,);
        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a
					LEFT JOIN  ' . $kf->getTable() . ' as k ON ( a.kpi_id = k.id AND k.deleted = 0 )
					LEFT JOIN ' . $urcf->getTable() . ' as  urcf ON ( a.user_review_control_id = urcf.id )
					where	k.company_id = ?
						AND a.deleted = 0 AND urcf.deleted = 0';
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
        $kf = new KPIFactory();
        $urcf = new UserReviewControlFactory();
        $ph = array('id' => (int)$id, 'company_id' => (int)$company_id,);
        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a
						LEFT JOIN  ' . $kf->getTable() . ' as k ON ( a.kpi_id = k.id AND k.deleted = 0 )
						LEFT JOIN ' . $urcf->getTable() . ' as urcf ON ( a.user_review_control_id = urcf.id )
					where	a.id = ?
						AND k.company_id = ?
						AND a.deleted = 0 AND urcf.deleted = 0';
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
            $order = array('kf.name' => 'asc');
            $strict = false;
        } else {
            $strict = true;
        }
        //Debug::Arr($order, 'Order Data:', __FILE__, __LINE__, __METHOD__, 10);
        //Debug::Arr($filter_data, 'Filter Data:', __FILE__, __LINE__, __METHOD__, 10);

        $uf = new UserFactory();
        $kf = new KPIFactory();
        $urcf = new UserReviewControlFactory();
        $ph = array('company_id' => (int)$company_id,);
        $query = '
					select	a.*,
							kf.name,
							kf.type_id,
							kf.status_id,
							kf.minimum_rate,
							kf.maximum_rate,
							kf.description,
							y.first_name as created_by_first_name,
							y.middle_name as created_by_middle_name,
							y.last_name as created_by_last_name,
							z.first_name as updated_by_first_name,
							z.middle_name as updated_by_middle_name,
							z.last_name as updated_by_last_name
					from	' . $this->getTable() . ' as a
						LEFT JOIN ' . $kf->getTable() . ' as kf ON ( a.kpi_id = kf.id AND kf.deleted = 0 )
						LEFT JOIN ' . $urcf->getTable() . ' as urcf ON ( a.user_review_control_id = urcf.id )
						LEFT JOIN ' . $uf->getTable() . ' as y ON ( a.created_by = y.id AND y.deleted = 0 )
						LEFT JOIN ' . $uf->getTable() . ' as z ON ( a.updated_by = z.id AND z.deleted = 0 )
					where	kf.company_id = ? AND urcf.deleted = 0';
        $query .= (isset($filter_data['permission_children_ids'])) ? $this->getWhereClauseSQL('a.created_by', $filter_data['permission_children_ids'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['id'])) ? $this->getWhereClauseSQL('a.id', $filter_data['id'], 'numeric_list', $ph) : null;
        //$query .= ( isset($filter_data['exclude_id']) ) ? $this->getWhereClauseSQL( 'a.id', $filter_data['exclude_id'], 'not_numeric_list', $ph ) : NULL;
        $query .= (isset($filter_data['user_review_control_id'])) ? $this->getWhereClauseSQL('a.user_review_control_id', $filter_data['user_review_control_id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['kpi_id'])) ? $this->getWhereClauseSQL('a.kpi_id', $filter_data['kpi_id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['term_id'])) ? $this->getWhereClauseSQL('urcf.term_id', $filter_data['term_id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['severity_id'])) ? $this->getWhereClauseSQL('urcf.severity_id', $filter_data['severity_id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['rating'])) ? $this->getWhereClauseSQL('a.rating', $filter_data['rating'], 'numeric', $ph) : null;
        $query .= (isset($filter_data['note'])) ? $this->getWhereClauseSQL('a.note', $filter_data['note'], 'text', $ph) : null;
        $query .= (isset($filter_data['tag'])) ? $this->getWhereClauseSQL('a.id', array('company_id' => (int)$company_id, 'object_type_id' => 330, 'tag' => $filter_data['tag']), 'tag', $ph) : null;

        $query .= (isset($filter_data['created_date'])) ? $this->getWhereClauseSQL('a.created_date', $filter_data['created_date'], 'date_range', $ph) : null;
        $query .= (isset($filter_data['updated_date'])) ? $this->getWhereClauseSQL('a.updated_date', $filter_data['updated_date'], 'date_range', $ph) : null;
        $query .= (isset($filter_data['created_by'])) ? $this->getWhereClauseSQL(array('a.created_by', 'y.first_name', 'y.last_name'), $filter_data['created_by'], 'user_id_or_name', $ph) : null;
        $query .= (isset($filter_data['updated_by'])) ? $this->getWhereClauseSQL(array('a.updated_by', 'z.first_name', 'z.last_name'), $filter_data['updated_by'], 'user_id_or_name', $ph) : null;

        $query .= ' AND a.deleted = 0 ';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict, $additional_order_fields);
        $this->ExecuteSQL($query, $ph, $limit, $page);

        return $this;
    }
}
