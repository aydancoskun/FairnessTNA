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
 * @package Modules\Hierarchy
 */
class HierarchyControlListFactory extends HierarchyControlFactory implements IteratorAggregate
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

    public function getByCompanyId($id, $limit = null, $page = null, $where = null, $order = null)
    {
        if ($id == '') {
            return false;
        }

        $strict_order = true;
        if ($order == null) {
            $order = array('name' => 'asc');
            $strict_order = false;
        }

        $ph = array(
            'id' => (int)$id,
        );


        $query = '
					select	*
					from	' . $this->getTable() . '
					where
						company_id = ?
						AND deleted = 0
				';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict_order);

        $this->ExecuteSQL($query, $ph, $limit, $page);

        return $this;
    }

    public function getArrayByListFactory($lf, $include_blank = true, $object_sorted_array = false, $include_name = true)
    {
        if (!is_object($lf)) {
            return false;
        }

        $list = array();
        if ($object_sorted_array == false and $include_blank == true) {
            $list[0] = '--';
        }

        //Make sure we always ensure that we return valid object_types for the product edition.
        $valid_object_type_ids = $this->getOptions('object_type');

        foreach ($lf as $obj) {
            if (isset($valid_object_type_ids[$obj->getColumn('object_type_id')])) {
                if ($object_sorted_array == true) {
                    if ($include_blank == true and !isset($list[$obj->getColumn('object_type_id')][0])) {
                        $list[$obj->getColumn('object_type_id')][0] = '--';
                    }

                    if ($include_name == true) {
                        $list[$obj->getColumn('object_type_id')][$obj->getID()] = $obj->getName();
                    } else {
                        $list[$obj->getColumn('object_type_id')] = $obj->getID();
                    }
                } else {
                    $list[$obj->getID()] = $obj->getName();
                }
            }
        }

        if (empty($list) == false) {
            return $list;
        }

        return false;
    }

    public function getObjectTypeAppendedListByCompanyID($company_id, $where = null, $order = null)
    {
        if ($company_id == '') {
            return false;
        }

        if ($order == null) {
            $order = array('name' => 'asc', 'description' => 'asc');
            $strict = false;
        } else {
            //Always sort by last name, first name after other columns
            if (!isset($order['name'])) {
                $order['name'] = 'asc';
            }
            $strict = true;
        }

        $hotf = new HierarchyObjectTypeFactory();

        $ph = array(
            'company_id' => (int)$company_id,
        );

        $query = '
					select	a.*,
							b.object_type_id
					from ' . $this->getTable() . ' as a
					LEFT JOIN ' . $hotf->getTable() . ' as b ON a.id = b.hierarchy_control_id
					where	a.company_id = ?
							AND a.deleted = 0
				';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getObjectTypeAppendedListByCompanyIDAndUserID($company_id, $user_id, $where = null, $order = null)
    {
        if ($company_id == '') {
            return false;
        }

        if ($user_id == '') {
            return false;
        }

        $hotf = new HierarchyObjectTypeFactory();
        $huf = new HierarchyUserFactory();

        $ph = array(
            'company_id' => (int)$company_id,
            'user_id' => (int)$user_id,
        );

        $query = '
					select	a.*,
							b.object_type_id
					from ' . $this->getTable() . ' as a
					LEFT JOIN ' . $hotf->getTable() . ' as b ON a.id = b.hierarchy_control_id
					LEFT JOIN ' . $huf->getTable() . ' as c ON a.id = c.hierarchy_control_id
					where	a.company_id = ?
							AND c.user_id = ?
							AND a.deleted = 0
				';
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
            $order = array('name' => 'asc', 'description' => 'asc');
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
        $hlf = new HierarchyLevelFactory();
        $huf = new HierarchyUserFactory();
        $hotf = new HierarchyObjectTypeFactory();

        $ph = array(
            'company_id' => (int)$company_id,
        );

        //Count total users in HierarchyControlFactory factory, so we can disable it when needed. That way it doesn't slow down Hierarchy dropdown boxes.
        //(select count(*) from '. $hlf->getTable().' as hlf WHERE a.id = hlf.hierarchy_control_id AND hlf.deleted = 0 AND a.deleted = 0) as superiors,
        //(select count(*) from '. $huf->getTable().' as hulf WHERE a.id = hulf.hierarchy_control_id AND a.deleted = 0 ) as subordinates,
        $query = '
					select	distinct a.*,
							y.first_name as created_by_first_name,
							y.middle_name as created_by_middle_name,
							y.last_name as created_by_last_name,
							z.first_name as updated_by_first_name,
							z.middle_name as updated_by_middle_name,
							z.last_name as updated_by_last_name
					from	' . $this->getTable() . ' as a
						LEFT JOIN ' . $hlf->getTable() . ' as hlf ON ( a.id = hlf.hierarchy_control_id AND hlf.deleted = 0 )
						LEFT JOIN ' . $huf->getTable() . ' as huf ON ( a.id = huf.hierarchy_control_id )
						LEFT JOIN ' . $hotf->getTable() . ' as hotf ON ( a.id = hotf.hierarchy_control_id )
						LEFT JOIN ' . $uf->getTable() . ' as y ON ( a.created_by = y.id AND y.deleted = 0 )
						LEFT JOIN ' . $uf->getTable() . ' as z ON ( a.updated_by = z.id AND z.deleted = 0 )
					where	a.company_id = ?
					';

        $query .= (isset($filter_data['permission_children_ids'])) ? $this->getWhereClauseSQL('a.created_by', $filter_data['permission_children_ids'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['id'])) ? $this->getWhereClauseSQL('a.id', $filter_data['id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['exclude_id'])) ? $this->getWhereClauseSQL('a.id', $filter_data['exclude_id'], 'not_numeric_list', $ph) : null;

        $query .= (isset($filter_data['name'])) ? $this->getWhereClauseSQL('a.name', $filter_data['name'], 'text', $ph) : null;
        $query .= (isset($filter_data['description'])) ? $this->getWhereClauseSQL('a.description', $filter_data['description'], 'text', $ph) : null;

        $query .= (isset($filter_data['object_type'])) ? $this->getWhereClauseSQL('hotf.object_type_id', $filter_data['object_type'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['superior_user_id'])) ? $this->getWhereClauseSQL('hlf.user_id', $filter_data['superior_user_id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['user_id'])) ? $this->getWhereClauseSQL('huf.user_id', $filter_data['user_id'], 'numeric_list', $ph) : null;

        $query .= (isset($filter_data['created_by'])) ? $this->getWhereClauseSQL(array('a.created_by', 'y.first_name', 'y.last_name'), $filter_data['created_by'], 'user_id_or_name', $ph) : null;
        $query .= (isset($filter_data['updated_by'])) ? $this->getWhereClauseSQL(array('a.updated_by', 'z.first_name', 'z.last_name'), $filter_data['updated_by'], 'user_id_or_name', $ph) : null;

        //Don't filter hlf.deleted=0 here as that will not shown hierarchies without any superiors assigned to them. Do the filter on the JOIN instead.
        $query .= ' AND ( a.deleted = 0 ) ';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict, $additional_order_fields);

        $this->ExecuteSQL($query, $ph, $limit, $page);

        return $this;
    }
}
