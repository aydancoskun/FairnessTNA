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
class HierarchyLevelListFactory extends HierarchyLevelFactory implements IteratorAggregate
{
    public function getAll($limit = null, $page = null, $where = null, $order = null)
    {
        $query = '
					select	*
					from	' . $this->getTable() . '
					where	deleted = 0
				';
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
						AND deleted = 0
				';
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

        $hcf = new HierarchyControlFactory();

        $ph = array(
            'company_id' => (int)$company_id
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a
					LEFT JOIN ' . $hcf->getTable() . ' as b ON a.hierarchy_control_id = b.id
					where	 b.company_id = ?
						AND a.deleted = 0
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

        $hcf = new HierarchyControlFactory();

        $ph = array(
            'id' => (int)$id,
            'company_id' => (int)$company_id
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a
					LEFT JOIN ' . $hcf->getTable() . ' as b ON a.hierarchy_control_id = b.id
					where	a.id = ?
						AND b.company_id = ?
						AND a.deleted = 0
				';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByHierarchyControlId($id, $where = null, $order = null)
    {
        if ($id == '') {
            return false;
        }

        $strict_order = true;
        if ($order == null) {
            $order = array('level' => 'asc', 'user_id' => 'asc');
            $strict_order = false;
        }

        $ph = array(
            'id' => (int)$id,
        );


        $query = '
					select	*
					from	' . $this->getTable() . '
					where	hierarchy_control_id = ?
						AND deleted = 0
				';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict_order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByHierarchyControlIdAndUserId($id, $user_id, $where = null, $order = null)
    {
        if ($id == '') {
            return false;
        }

        $strict_order = true;
        if ($order == null) {
            $order = array('level' => 'asc', 'user_id' => 'asc');
            $strict_order = false;
        }

        $ph = array(
            'id' => (int)$id,
            'user_id' => (int)$user_id,
        );

        $query = '
					select	*
					from	' . $this->getTable() . '
					where	hierarchy_control_id = ?
						AND user_id = ?
						AND deleted = 0
				';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict_order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByHierarchyControlIdAndUserIdAndExcludeID($id, $user_id, $exclude_id, $where = null, $order = null)
    {
        if ($id == '') {
            return false;
        }

        $strict_order = true;
        if ($order == null) {
            $order = array('level' => 'asc', 'user_id' => 'asc');
            $strict_order = false;
        }

        $ph = array(
            'id' => (int)$id,
            'user_id' => (int)$user_id,
            'exclude_id' => (int)$exclude_id
        );

        $query = '
					select	*
					from	' . $this->getTable() . '
					where	hierarchy_control_id = ?
						AND user_id = ?
						AND id != ?
						AND deleted = 0
				';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict_order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getLevelsByHierarchyControlIdAndUserId($id, $user_id)
    {
        if ($id == '') {
            return false;
        }

        if ($user_id == '') {
            return false;
        }

        $ph = array(
            'id' => (int)$id,
            'idb' => $id,
            'user_id' => (int)$user_id,
        );

        $query = '
					select	distinct(level)
					from	' . $this->getTable() . '
					where	hierarchy_control_id = ?
						AND level >= (
										select	level
										from	' . $this->getTable() . '
										where	hierarchy_control_id = ?
											AND user_id = ?
											AND deleted = 0
										LIMIT 1
									)
						AND deleted = 0
					ORDER BY level ASC
				';

        $retarr = $this->db->GetCol($query, $ph);

        return $retarr;
    }

    // 50 = Requests
    public function getLevelsByUserIdAndObjectTypeID($user_id, $object_type_id = 50)
    {
        if ($user_id == '') {
            return false;
        }

        if ($object_type_id == '') {
            return false;
        }

        $hotf = new HierarchyObjectTypeFactory();
        $hcf = new HierarchyControlFactory();

        $ph = array(
            'user_id' => (int)$user_id,
        );

        $query = '
				select	distinct (x.level) as level
				from	' . $this->getTable() . ' as x,
						' . $hcf->getTable() . ' as y,
					(
								select	a.hierarchy_control_id, a.level
								from	' . $this->getTable() . ' as a
									LEFT JOIN ' . $hotf->getTable() . ' as b ON a.hierarchy_control_id = b.hierarchy_control_id
								where a.user_id = ?
									AND b.object_type_id in (' . $this->getListSQL($object_type_id, $ph, 'int') . ')
									AND a.deleted = 0
					) as z
				where
					x.hierarchy_control_id = y.id
					AND x.hierarchy_control_id = z.hierarchy_control_id
					AND x.level >= z.level
					AND ( x.deleted = 0 AND y.deleted = 0 )
				ORDER BY x.level asc
				';

        $rs = $this->db->Execute($query, $ph);
        //Debug::Text(' Rows: '. $rs->RecordCount(), __FILE__, __LINE__, __METHOD__, 10);

        if ($rs->RecordCount() > 0) {
            //The retarr key is the value that will be displayed to the user when switching levels on the authorization page,
            //so we need to start that from 1 and increasing sequentially, regardless of what the actual hierarchy level is.
            $i = 1;
            $retarr = array();
            foreach ($rs as $row) {
                $retarr[$i] = $row['level'];
                $i++;
            }

            return $retarr;
        }

        return false;
    }

    public function getObjectTypeAndHierarchyAppendedListByCompanyIDAndUserID($company_id, $user_id, $where = null, $order = null)
    {
        if ($company_id == '') {
            return false;
        }

        if ($user_id == '') {
            return false;
        }

        $additional_order_fields = array('object_type_id', 'hierarchy_control_name');
        if ($order == null) {
            $order = array('object_type_id' => 'asc', 'hierarchy_control_name' => 'asc', 'level' => 'asc', 'user_id' => 'asc');
            $strict = false;
        } else {
            $strict = true;
        }

        $hcf = new HierarchyControlFactory();
        $hotf = new HierarchyObjectTypeFactory();
        $huf = new HierarchyUserFactory();

        $ph = array(
            'company_id' => (int)$company_id,
            'user_id' => (int)$user_id,
        );

        $query = '
					select	hlf.*,
							hcf.name as hierarchy_control_name,
							hotf.object_type_id
					from ' . $this->getTable() . ' as hlf
					LEFT JOIN ' . $hcf->getTable() . ' as hcf ON hcf.id = hlf.hierarchy_control_id
					LEFT JOIN ' . $hotf->getTable() . ' as hotf ON hcf.id = hotf.hierarchy_control_id
					LEFT JOIN ' . $huf->getTable() . ' as huf ON hcf.id = huf.hierarchy_control_id
					where	hcf.company_id = ?
							AND huf.user_id = ?
							AND ( hlf.deleted = 0 AND hcf.deleted = 0 )
				';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict, $additional_order_fields);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getLevelsAndHierarchyControlIDsByUserIdAndObjectTypeID($user_id, $object_type_id = 50)
    {
        if ($user_id == '') {
            return false;
        }

        if ($object_type_id == '') {
            return false;
        }

        $hotf = new HierarchyObjectTypeFactory();
        $hcf = new HierarchyControlFactory();

        $ph = array(
            'user_id' => (int)$user_id,
        );

        //Include object_type_ids for each hierarchy_control_id, because we need to do additional filtering by hierarchy_control_id, level, object_type_ids
        $query = '
				select
						x.hierarchy_control_id as hierarchy_control_id,
						x.level as level,
						z.object_type_id as object_type_id
				from	' . $this->getTable() . ' as x,
						' . $hcf->getTable() . ' as y,
					(
								select	a.hierarchy_control_id, a.level, b.object_type_id
								from	' . $this->getTable() . ' as a
									LEFT JOIN ' . $hotf->getTable() . ' as b ON a.hierarchy_control_id = b.hierarchy_control_id
								where a.user_id = ?
									AND b.object_type_id in (' . $this->getListSQL($object_type_id, $ph, 'int') . ')
									AND a.deleted = 0
					) as z
				where
					x.hierarchy_control_id = y.id
					AND x.hierarchy_control_id = z.hierarchy_control_id
					AND x.level >= z.level
					AND ( x.deleted = 0 AND y.deleted = 0 )
				ORDER BY x.level asc
				';

        $rs = $this->db->Execute($query, $ph);
        //Debug::Text(' Rows: '. $rs->RecordCount(), __FILE__, __LINE__, __METHOD__, 10);

        $hierarchy_to_level_map = array();
        $hierarchy_to_object_type_map = array();
        if ($rs->RecordCount() > 0) {
            foreach ($rs as $row) {
                $hierarchy_to_level_map[$row['hierarchy_control_id']][] = (int)$row['level'];
                $hierarchy_to_object_type_map[$row['hierarchy_control_id']][] = (int)$row['object_type_id'];
            }
            //Debug::Arr($hierarchy_to_level_map, ' Hierarchy To Level Map: ', __FILE__, __LINE__, __METHOD__, 10);
            //Debug::Arr($hierarchy_to_object_type_map, ' Hierarchy To Object Type Map: ', __FILE__, __LINE__, __METHOD__, 10);

            //Take each hierarchy_control and level element and convert it into virtual levels, where the first level (regardless of what it is in the actual hierarchy)
            //is always virtual_level 1, so the supervisor can see all necessary requests that are waiting on them at level 1. Dropping down any other levels
            //is looking and requests waiting on OTHER supervisors.
            //Track the last level for each hierarchy, so we know when to include all requests that may be higher than that level, so if the hierarchy is changed
            //and levels are taken out, requests don't sit in limbo forever.
            $retarr = array();
            foreach ($hierarchy_to_level_map as $hierarchy_control_id => $level_arr) {
                //Unique each level arr so we don't start creating extra virtual levels when multiple superiors are at the same level.
                //This fixes a bug where if there were 5 superiors at the same level, 5 virtual levels would be created.
                $level_arr = array_unique($level_arr);

                $i = 1;
                foreach ($level_arr as $level) {
                    if ($level == end($hierarchy_to_level_map[$hierarchy_control_id])) {
                        $last_level = true;
                    } else {
                        $last_level = false;
                    }

                    $retarr[$i][] = array('hierarchy_control_id' => (int)$hierarchy_control_id, 'level' => $level, 'last_level' => $last_level, 'object_type_id' => array_unique($hierarchy_to_object_type_map[$hierarchy_control_id]));

                    $i++;
                }
            }

            //Debug::Arr($retarr, ' Final Hierarchy To Level Map: ', __FILE__, __LINE__, __METHOD__, 10);
            return $retarr;
        }

        return false;
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
            $order = array('level' => 'asc');
            $strict = false;
        } else {
            //Always sort by last name, first name after other columns
            if (!isset($order['level'])) {
                $order['level'] = 'asc';
            }
            $strict = true;
        }
        //Debug::Arr($order, 'Order Data:', __FILE__, __LINE__, __METHOD__, 10);
        //Debug::Arr($filter_data, 'Filter Data:', __FILE__, __LINE__, __METHOD__, 10);

        $uf = new UserFactory();
        $hcf = new HierarchyControlFactory();

        $ph = array(
            'company_id' => (int)$company_id,
        );

        $query = '
					select	a.*,
							y.first_name as created_by_first_name,
							y.middle_name as created_by_middle_name,
							y.last_name as created_by_last_name,
							z.first_name as updated_by_first_name,
							z.middle_name as updated_by_middle_name,
							z.last_name as updated_by_last_name
					from	' . $this->getTable() . ' as a
						LEFT JOIN ' . $hcf->getTable() . ' as b ON ( a.hierarchy_control_id = b.id AND b.deleted = 0 )
						LEFT JOIN ' . $uf->getTable() . ' as y ON ( a.created_by = y.id AND y.deleted = 0 )
						LEFT JOIN ' . $uf->getTable() . ' as z ON ( a.updated_by = z.id AND z.deleted = 0 )
					where	b.company_id = ?
					';

        $query .= (isset($filter_data['permission_children_ids'])) ? $this->getWhereClauseSQL('a.created_by', $filter_data['permission_children_ids'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['id'])) ? $this->getWhereClauseSQL('a.id', $filter_data['id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['exclude_id'])) ? $this->getWhereClauseSQL('a.id', $filter_data['exclude_id'], 'not_numeric_list', $ph) : null;

        $query .= (isset($filter_data['hierarchy_control_id'])) ? $this->getWhereClauseSQL('a.hierarchy_control_id', $filter_data['hierarchy_control_id'], 'numeric_list', $ph) : null;

        $query .= (isset($filter_data['created_by'])) ? $this->getWhereClauseSQL(array('a.created_by', 'y.first_name', 'y.last_name'), $filter_data['created_by'], 'user_id_or_name', $ph) : null;
        $query .= (isset($filter_data['updated_by'])) ? $this->getWhereClauseSQL(array('a.updated_by', 'z.first_name', 'z.last_name'), $filter_data['updated_by'], 'user_id_or_name', $ph) : null;

        $query .= ' AND a.deleted = 0 ';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict, $additional_order_fields);

        $this->ExecuteSQL($query, $ph, $limit, $page);

        return $this;
    }
}
