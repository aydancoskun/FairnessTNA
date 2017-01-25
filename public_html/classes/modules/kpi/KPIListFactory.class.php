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
class KPIListFactory extends KPIFactory implements IteratorAggregate
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
            'id' => (int)$id,
            'company_id' => (int)$company_id,
        );

        $query = '
					select	*
					from	' . $this->getTable() . '
					where	id = ?
						AND company_id = ?
						AND deleted = 0';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByCompanyIDAndGroupID($company_id, $id, $where = null, $order = null)
    {
        if ($company_id == '') {
            return false;
        }

        if ($id == '') {
            return false;
        }

        $cgmf = new CompanyGenericMapFactory();

        $ph = array(
            'company_id' => (int)$company_id,
            'map_id' => (int)$id,
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a
					LEFT JOIN ' . $cgmf->getTable() . ' as b ON ( a.id = b.object_id AND b.company_id = a.company_id AND b.object_type_id = 2020 )
					where	a.company_id = ?
						AND b.map_id = ?
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

        if (isset($filter_data['kpi_id'])) {
            $filter_data['id'] = $filter_data['kpi_id'];
        }
        if (isset($filter_data['kpi_status_id'])) {
            $filter_data['status_id'] = $filter_data['kpi_status_id'];
        }
        if (isset($filter_data['kpi_type_id'])) {
            $filter_data['type_id'] = $filter_data['kpi_type_id'];
        }
        if (isset($filter_data['kpi_group_id'])) {
            $filter_data['group_id'] = $filter_data['kpi_group_id'];
        }

        if (!is_array($order)) {
            //Use Filter Data ordering if its set.
            if (isset($filter_data['sort_column']) and $filter_data['sort_order']) {
                $order = array(Misc::trimSortPrefix($filter_data['sort_column']) => $filter_data['sort_order']);
            }
        }

        $additional_order_fields = array('type_id', 'status_id');

        $sort_column_aliases = array(
            'type' => 'type_id',
            'status' => 'status_id',
        );

        $order = $this->getColumnsFromAliases($order, $sort_column_aliases);

        if ($order == null) {
            $order = array('name' => 'asc');
            $strict = false;
        } else {
            $strict = true;
        }

        //Debug::Arr($order, 'Order Data:', __FILE__, __LINE__, __METHOD__, 10);
        //Debug::Arr($filter_data, 'Filter Data:', __FILE__, __LINE__, __METHOD__, 10);

        $uf = new UserFactory();
        $cgmf = new CompanyGenericMapFactory();
        //$kgf = new KPIGroupFactory();
        $ph = array(
            'company_id' => (int)$company_id,
        );

        $query = '
					select DISTINCT a.*, 
					y.first_name as created_by_first_name, 
					y.middle_name as created_by_middle_name, 
					y.last_name as created_by_last_name, 
					z.first_name as updated_by_first_name, 
					z.middle_name as updated_by_middle_name, 
					z.last_name as updated_by_last_name
					from	' . $this->getTable() . ' as a
						LEFT JOIN ' . $cgmf->getTable() . ' as b ON ( a.id = b.object_id AND b.company_id = a.company_id AND b.object_type_id = 2020 )
						LEFT JOIN ' . $uf->getTable() . ' as y ON ( a.created_by = y.id AND y.deleted = 0 )
						LEFT JOIN ' . $uf->getTable() . ' as z ON ( a.updated_by = z.id AND z.deleted = 0 )
					where a.company_id = ?
					';
        $query .= (isset($filter_data['permission_children_ids'])) ? $this->getWhereClauseSQL('a.created_by', $filter_data['permission_children_ids'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['id'])) ? $this->getWhereClauseSQL('a.id', $filter_data['id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['name'])) ? $this->getWhereClauseSQL('a.name', $filter_data['name'], 'text', $ph) : null;
        $query .= (isset($filter_data['description'])) ? $this->getWhereClauseSQL('a.description', $filter_data['description'], 'text', $ph) : null;
        $query .= (isset($filter_data['minimum_rate'])) ? $this->getWhereClauseSQL('a.minimum_rate', $filter_data['minimum_rate'], 'numeric', $ph) : null;
        $query .= (isset($filter_data['maximum_rate'])) ? $this->getWhereClauseSQL('a.maximum_rate', $filter_data['maximum_rate'], 'numeric', $ph) : null;

        if (isset($filter_data['status']) and !is_array($filter_data['status']) and trim($filter_data['status']) != '' and !isset($filter_data['status_id'])) {
            $filter_data['status_id'] = Option::getByFuzzyValue($filter_data['status'], $this->getOptions('status'));
        }
        $query .= (isset($filter_data['status_id'])) ? $this->getWhereClauseSQL('a.status_id', $filter_data['status_id'], 'numeric_list', $ph) : null;

        if (isset($filter_data['type']) and !is_array($filter_data['type']) and trim($filter_data['type']) != '' and !isset($filter_data['type_id'])) {
            $filter_data['type_id'] = Option::getByFuzzyValue($filter_data['type'], $this->getOptions('type'));
        }
        $query .= (isset($filter_data['type_id'])) ? $this->getWhereClauseSQL('a.type_id', $filter_data['type_id'], 'numeric_list', $ph) : null;

        //This is special in that there is an "--ALL--" group that KPIs must actually be assigned to (in addition to any other group if needed), in order to appear when a review is created using the "--ALL--" group.
        $query .= (isset($filter_data['group_id'])) ? $this->getWhereClauseSQL('b.map_id', $filter_data['group_id'], 'numeric_list_with_all', $ph) : null;
        $query .= (isset($filter_data['tag'])) ? $this->getWhereClauseSQL('a.id', array('company_id' => (int)$company_id, 'object_type_id' => 310, 'tag' => $filter_data['tag']), 'tag', $ph) : null;

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

        $query .= ' AND a.deleted = 0 ';

        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict, $additional_order_fields);

        $this->ExecuteSQL($query, $ph, $limit, $page);

        return $this;
    }

    public function getByCompanyIdArray($company_id, $include_blank = true)
    {
        $klf = new KPIListFactory();
        $klf->getByCompanyId($company_id);

        $list = array();
        if ($include_blank == true) {
            $list[0] = '--';
        }

        foreach ($klf as $kpi_obj) {
            $list[$kpi_obj->getID()] = $kpi_obj->getName();
        }

        if (empty($list) == false) {
            return $list;
        }

        return false;
    }

    public function getByCompanyId($id, $where = null, $order = null)
    {
        if ($id == '') {
            return false;
        }

        if ($order == null) {
            $order = array('name' => 'asc');
            $strict = false;
        } else {
            $strict = true;
        }

        $ph = array(
            'company_id' => (int)$id,
        );

        $query = '
					select	*
					from	' . $this->getTable() . '
					where	company_id = ?
						AND deleted = 0';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict);

        $this->ExecuteSQL($query, $ph);

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
}
