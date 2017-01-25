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
 * @package Core
 */
class Permission
{
    private $cached_permissions = array();
    private $cached_permission_children_ids = array();

    public static function getPermissionIsChildIsOwnerSQL($id, $inner_column)
    {
        $query = '
				CASE WHEN phc.is_child is NOT NULL THEN 1 ELSE 0 END as is_child,
				CASE WHEN ' . $inner_column . ' = ' . (int)$id . ' THEN 1 ELSE 0 END as is_owner,
				';
        return $query;
    }

    public static function getPermissionHierarchySQL($company_id, $user_id, $outer_column)
    {
        $hlf = new HierarchyLevelFactory();
        $huf = new HierarchyUserFactory();
        $hotf = new HierarchyObjectTypeFactory();
        $hcf = new HierarchyControlFactory();

        $query = '
						LEFT JOIN (
							select phc_huf.user_id as user_id, 1 as is_child
							from ' . $huf->getTable() . ' as phc_huf
							LEFT JOIN ' . $hlf->getTable() . ' as phc_hlf ON phc_huf.hierarchy_control_id = phc_hlf.hierarchy_control_id
							LEFT JOIN ' . $hotf->getTable() . ' as phc_hotf ON phc_huf.hierarchy_control_id = phc_hotf.hierarchy_control_id
							LEFT JOIN ' . $hcf->getTable() . ' as phc_hcf ON phc_huf.hierarchy_control_id = phc_hcf.id
							WHERE
								phc_hlf.user_id = ' . (int)$user_id . '
								AND phc_hcf.company_id = ' . (int)$company_id . '
								AND phc_hotf.object_type_id = 100
								AND phc_huf.user_id != phc_hlf.user_id
								AND ( phc_hlf.deleted = 0 AND phc_hcf.deleted = 0 )
						) as phc ON ' . $outer_column . ' = phc.user_id
					';

        return $query;
    }

    public static function getPermissionIsChildIsOwnerFilterSQL($filter_data, $outer_column_name)
    {
        $query = array();
        if (isset($filter_data['permission_is_own']) and $filter_data['permission_is_own'] == true and isset($filter_data['permission_current_user_id'])) {
            $query[] = $outer_column_name . ' = ' . (int)$filter_data['permission_current_user_id'];
        }
        if (isset($filter_data['permission_is_child']) and $filter_data['permission_is_child'] == true) {
            $query[] = 'phc.is_child = 1';
        }

        if (empty($query) == false) {
            return ' AND ( ' . implode(' OR ', $query) . ') ';
        }

        return false;
    }

    public function getLevel($user_id = null, $company_id = null)
    {
        //Use Cache_Lite class once we need performance.
        if ($user_id == null or $user_id == '') {
            global $current_user;
            if (is_object($current_user)) {
                $user_id = $current_user->getId();
            } else {
                return false;
            }
        }

        if ($company_id == null or $company_id == '') {
            global $current_company;
            $company_id = $current_company->getId();
        }

        $permission_arr = $this->getPermissions($user_id, $company_id);

        if (isset($permission_arr['_system']['level'])) {
            return $permission_arr['_system']['level'];
        }

        return 1; //Lowest level.
    }

    public function getPermissions($user_id, $company_id)
    {
        //When Permission->Check() is used in a tight loop, even getCache() can be slow as it has to load a large array.
        //So cache the permissions in even faster access memory when possible.
        if (isset($this->cached_permissions[$user_id][$company_id]) and $this->cached_permissions[$user_id][$company_id] != false) {
            return $this->cached_permissions[$user_id][$company_id];
        }

        $plf = TTnew('PermissionListFactory');

        $cache_id = 'permission_all' . $user_id . $company_id;
        $perm_arr = $plf->getCache($cache_id);
        //Debug::Arr($perm_arr, 'Cached Perm Arr:', __FILE__, __LINE__, __METHOD__, 9);
        if ($perm_arr === false) {
            $plf->getAllPermissionsByCompanyIdAndUserId($company_id, $user_id);
            if ($plf->getRecordCount() > 0) {
                //Debug::Text('Found Permissions in DB!', __FILE__, __LINE__, __METHOD__, 9);
                $perm_arr['_system']['last_updated_date'] = null;
                foreach ($plf as $p_obj) {
                    //Debug::Text('Perm -  Section: '. $p_obj->getSection() .' Name: '. $p_obj->getName() .' Value: '. (int)$p_obj->getValue(), __FILE__, __LINE__, __METHOD__, 9);
                    if ($p_obj->getUpdatedDate() > $perm_arr['_system']['last_updated_date']) {
                        $perm_arr['_system']['last_updated_date'] = $p_obj->getUpdatedDate();
                    }
                    $perm_arr[$p_obj->getSection()][$p_obj->getName()] = $p_obj->getValue();
                }
                //Last iteration, grab the permission level.
                $perm_arr['_system']['level'] = $p_obj->getColumn('level');

                $plf->saveCache($perm_arr, $cache_id);
            }
        }

        $this->cached_permissions[$user_id][$company_id] = $perm_arr; //Populate local cache.
        return $perm_arr;
    }

    public function Redirect($result)
    {
        if ($result !== true) {
            Redirect::Page(URLBuilder::getURL(null, Environment::getBaseURL() . '/permission/PermissionDenied.php'));
        }

        return true;
    }

    //Checks if the row_object_id is created by the current user

    public function PermissionDenied($result = false, $description = 'Permission Denied')
    {
        if ($result !== true) {
            Debug::Text('Permission Denied! Description: ' . $description, __FILE__, __LINE__, __METHOD__, 10);
            $af = TTnew('APIPermission');
            return $af->returnHandler(false, 'PERMISSION', $description);
        }

        return true;
    }

    //Checks if the row_object_id is in the src_object_list array,

    public function Query($section, $name, $user_id = null, $company_id = null)
    {
        Debug::Text('Permission Query!', __FILE__, __LINE__, __METHOD__, 9);
        if ($user_id == null or $user_id == '') {
            global $current_user;
            if (is_object($current_user)) {
                $user_id = $current_user->getId();
            } else {
                return false;
            }
        }

        if ($company_id == null or $company_id == '') {
            global $current_company;
            $company_id = $current_company->getId();
        }

        $plf = TTnew('PermissionListFactory');

        return $plf->getBySectionAndNameAndUserIdAndCompanyId($section, $name, $user_id, $company_id)->getCurrent();
    }

    public function isOwner($object_created_by, $object_assigned_to = null, $current_user_id = null)
    {
        if ($current_user_id == null or $current_user_id == '') {
            global $current_user;
            if (is_object($current_user)) {
                $current_user_id = $current_user->getId();
            } else {
                return false;
            }
        }

        //Allow object_assigned_to to be an array, then make sure *all* records in the array match.
        if (($object_created_by != '' and $object_created_by == $current_user_id)
            or ($object_assigned_to != '' and $object_assigned_to == $current_user_id)
            or (is_array($object_assigned_to) and count(array_unique($object_assigned_to)) == 1 and $object_assigned_to[0] == $current_user_id)
        ) {
            return true;
        }

        return false;
    }

    public function isChild($row_object_id, $src_object_list, $current_user_id = null)
    {
        if (!is_numeric($row_object_id) and !is_array($row_object_id)) {
            return false;
        }

        if ($current_user_id == null or $current_user_id == '') {
            global $current_user;
            if (is_object($current_user)) {
                $current_user_id = $current_user->getId();
            } else {
                return false;
            }
        }
        //Can never be a child of themselves, so remove the current user from the child list.
        if ($row_object_id == $current_user_id) {
            return false;
        }

        if (!is_array($src_object_list) and $src_object_list != '') {
            $src_object_list = array($src_object_list);
        }

        //If row_object_id is an array (ie: a subordinate list), then they must *all* match the src_object_list for this to be valid.
        //This is used by recurring_schedule for supervisor (subordinates only)
        if (is_array($row_object_id) and is_array($src_object_list)) {
            foreach ($row_object_id as $tmp_row_object_id) {
                if (!in_array($tmp_row_object_id, $src_object_list)) {
                    return false;
                }
            }
            //All items match, return TRUE.
            return true;
        } elseif (is_array($src_object_list) and in_array($row_object_id, $src_object_list)) {
            return true;
        }

        return false;
    }

    public function getPermissionFilterData($section, $name, $user_id = null, $company_id = null)
    {
        //Use Cache_Lite class once we need performance.
        if ($user_id == null or $user_id == '') {
            global $current_user;
            if (is_object($current_user)) {
                $user_id = $current_user->getId();
            } else {
                return false;
            }
        }

        if ($company_id == null or $company_id == '') {
            global $current_company;
            $company_id = $current_company->getId();
        }

        /*
            permission_children_ids
            permission_current_user_id
            permission_is_child = 1
            permission_is_own = 1
        */
        $retarr = array();
        $retarr['permission_current_user_id'] = $user_id;
        if ($this->Check($section, $name) == false) {
            if ($this->Check($section, $name . '_child')) {
                $retarr['permission_is_child'] = true;
            }
            if ($this->Check($section, $name . '_own')) {
                $retarr['permission_is_own'] = true; //Return user_id so we can match that specifically
            }
        }

        if (empty($retarr) == false) {
            return $retarr;
        }

        return array();
    }

    public function Check($section, $name, $user_id = null, $company_id = null)
    {
        //Use Cache_Lite class once we need performance.
        if ($user_id == null or $user_id == '') {
            global $current_user;
            if (is_object($current_user)) {
                $user_id = $current_user->getId();
            } else {
                return false;
            }
        }

        if ($company_id == null or $company_id == '') {
            global $current_company;
            $company_id = $current_company->getId();
        }

        //Debug::Text('Permission Check - Section: '. $section .' Name: '. $name .' User ID: '. $user_id .' Company ID: '. $company_id, __FILE__, __LINE__, __METHOD__, 9);
        $permission_arr = $this->getPermissions($user_id, $company_id);

        if (isset($permission_arr[$section][$name])) {
            //Debug::Text('Permission is Set!', __FILE__, __LINE__, __METHOD__, 9);
            $result = $permission_arr[$section][$name];
        } else {
            //Debug::Text('Permission is NOT Set!', __FILE__, __LINE__, __METHOD__, 9);
            $result = false;
        }

        return $result;
    }

    public function getPermissionChildren($section, $name, $user_id = null, $company_id = null)
    {
        //Use Cache_Lite class once we need performance.
        if ($user_id == null or $user_id == '') {
            global $current_user;
            if (is_object($current_user)) {
                $user_id = $current_user->getId();
            } else {
                return false;
            }
        }

        if ($company_id == null or $company_id == '') {
            global $current_company;
            $company_id = $current_company->getId();
        }

        //If 'view' is FALSE, we are not returning children to check for edit/delete permissions, so those are denied.
        //This can be tested with 'users', 'view', 'users', 'edit_subordinate' allowed. They won't be able to edit subordinates.
        if ($this->Check($section, $name, $user_id, $company_id) == false) {
            if ($this->Check($section, $name . '_child', $user_id, $company_id) == true) {
                $retarr = $this->getPermissionHierarchyChildren($company_id, $user_id);
            }
            //Why are we including the current user in the "child" list, if they can view their own records.
            //This essentially makes edit_child permissions include edit_own as well. Which for the editing punches
            //there may be cases where they can edit subordinates but not themselves.
            //Because in the SQL query, we restrict to just the child_ids.
            //Its different view view_own/view_child as compared to edit_own/edit_child.
            //	So we need to include the current user if they can only view their own, but exclude the current user when doing is_child checks above.
            //Another way we could handle this is to return an array of children and owner separately, then in SQL queries combine them together.
            if ($this->Check($section, $name . '_own', $user_id, $company_id) == true) {
                $retarr[] = (int)$user_id;
            }

            //If they don't have permissions to view anything, make sure we return a blank array, so all in_array() or isPermissionChild() returns FALSE.
            if (!isset($retarr)) {
                $retarr = array();
            }
        } else {
            //This must return TRUE, otherwise the SQL query will restrict returned records just to the children.
            //Used to be NULL, but isset() doesn't work on NULL. Using TRUE though caused is_array() to always return TRUE too, which may not be a bad thing.
            //   However using TRUE causing other PHP warnings when trying to add values to it as if it were an array.
            $retarr = null;
        }

        return $retarr;
    }

    public function getPermissionHierarchyChildren($company_id, $user_id)
    {
        if (isset($this->cached_permission_children_ids[$company_id][$user_id])) {
            return $this->cached_permission_children_ids[$company_id][$user_id];
        } else {
            Debug::Text('  Getting hierarchy children for User ID: ' . $user_id, __FILE__, __LINE__, __METHOD__, 10);
            $hlf = TTnew('HierarchyListFactory');
            $this->cached_permission_children_ids[$company_id][$user_id] = $hlf->getHierarchyChildrenByCompanyIdAndUserIdAndObjectTypeID($company_id, $user_id, 100);
            //Debug::Arr($this->cached_permission_children_ids[$company_id][$user_id], 'Permission Child IDs: ', __FILE__, __LINE__, __METHOD__, 10);

            return $this->cached_permission_children_ids[$company_id][$user_id];
        }
    }

    public function isPermissionChild($user_id, $permission_children_ids)
    {
        if ($permission_children_ids === null or in_array((int)$user_id, (array)$permission_children_ids, true)) { //Make sure we do a STRICT in_array() match, so $user_id=TRUE isn't matched.
            return true;
        }

        return false;
    }

    public function getLastUpdatedDate($user_id = null, $company_id = null)
    {
        //Use Cache_Lite class once we need performance.
        if ($user_id == null or $user_id == '') {
            global $current_user;
            if (isset($current_user)) {
                $user_id = $current_user->getId();
            } else {
                return false;
            }
        }

        if ($company_id == null or $company_id == '') {
            global $current_company;
            $company_id = $current_company->getId();
        }

        //Debug::Text('Permission Check - Section: '. $section .' Name: '. $name .' User ID: '. $user_id .' Company ID: '. $company_id, __FILE__, __LINE__, __METHOD__, 9);
        $permission_arr = $this->getPermissions($user_id, $company_id);

        if (isset($permission_arr['_system']['last_updated_date'])) {
            return $permission_arr['_system']['last_updated_date'];
        }

        return false;
    }
}
