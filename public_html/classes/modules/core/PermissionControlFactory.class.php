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
class PermissionControlFactory extends Factory
{
    protected $table = 'permission_control';
    protected $pk_sequence_name = 'permission_control_id_seq'; //PK Sequence name

    protected $company_obj = null;
    protected $tmp_previous_user_ids = array();

    public function _getFactoryOptions($name, $parent = null)
    {
        $retval = null;
        switch ($name) {
            case 'preset':
                $pf = TTnew('PermissionFactory');
                $retval = $pf->getOptions('preset');
                break;
            case 'level':
                $retval = array(
                    1 => 1,
                    2 => 2,
                    3 => 3,
                    4 => 4,
                    5 => 5,
                    6 => 6,
                    7 => 7,
                    8 => 8,
                    9 => 9,
                    10 => 10,
                    11 => 11,
                    12 => 12,
                    13 => 13,
                    14 => 14,
                    15 => 15,
                    16 => 16,
                    17 => 17,
                    18 => 18,
                    19 => 19,
                    20 => 20,
                    21 => 21,
                    22 => 22,
                    23 => 23,
                    24 => 24,
                    25 => 25,
                );
                break;
            case 'columns':
                $retval = array(
                    '-1000-name' => TTi18n::gettext('Name'),
                    '-1010-description' => TTi18n::gettext('Description'),
                    '-1020-level' => TTi18n::gettext('Level'),
                    '-1030-total_users' => TTi18n::gettext('Employees'),

                    '-2000-created_by' => TTi18n::gettext('Created By'),
                    '-2010-created_date' => TTi18n::gettext('Created Date'),
                    '-2020-updated_by' => TTi18n::gettext('Updated By'),
                    '-2030-updated_date' => TTi18n::gettext('Updated Date'),
                );
                break;
            case 'list_columns':
                $retval = Misc::arrayIntersectByKey(array('name', 'description', 'level'), Misc::trimSortPrefix($this->getOptions('columns')));
                break;
            case 'default_display_columns': //Columns that are displayed by default.
                $retval = array(
                    'name',
                    'description',
                    'level',
                    'total_users',
                    'updated_by',
                    'updated_date',
                );
                break;
            case 'unique_columns': //Columns that are unique, and disabled for mass editing.
                $retval = array(
                    'name',
                );
                break;
        }

        return $retval;
    }

    public function _getVariableToFunctionMap($data)
    {
        $variable_function_map = array(
            'id' => 'ID',
            'company_id' => 'Company',
            'name' => 'Name',
            'description' => 'Description',
            'level' => 'Level',
            'total_users' => false,
            'user' => 'User',
            'permission' => 'Permission',
            'deleted' => 'Deleted',
        );
        return $variable_function_map;
    }

    public function setCompany($id)
    {
        $id = trim($id);

        $clf = TTnew('CompanyListFactory');

        if ($this->Validator->isResultSetWithRows('company',
            $clf->getByID($id),
            TTi18n::gettext('Company is invalid')
        )
        ) {
            $this->data['company_id'] = $id;

            return true;
        }

        return false;
    }

    public function setName($name)
    {
        $name = trim($name);

        if ($this->Validator->isLength('name',
                $name,
                TTi18n::gettext('Name is invalid'),
                2, 50)
            and $this->Validator->isTrue('name',
                $this->isUniqueName($name),
                TTi18n::gettext('Name is already in use')
            )
        ) {
            $this->data['name'] = $name;

            return true;
        }

        return false;
    }

    public function isUniqueName($name)
    {
        $name = trim($name);
        if ($name == '') {
            return false;
        }

        $ph = array(
            'company_id' => (int)$this->getCompany(),
            'name' => TTi18n::strtolower($name),
        );

        $query = 'select id from ' . $this->getTable() . ' where company_id = ? AND lower(name) = ? AND deleted=0';
        $permission_control_id = $this->db->GetOne($query, $ph);
        Debug::Arr($permission_control_id, 'Unique Permission Control ID: ' . $permission_control_id, __FILE__, __LINE__, __METHOD__, 10);

        if ($permission_control_id === false) {
            return true;
        } else {
            if ($permission_control_id == $this->getId()) {
                return true;
            }
        }

        return false;
    }

    public function getCompany()
    {
        if (isset($this->data['company_id'])) {
            return (int)$this->data['company_id'];
        }

        return false;
    }

    public function getDescription()
    {
        return $this->data['description'];
    }

    public function setDescription($description)
    {
        $description = trim($description);

        if ($description == ''
            or $this->Validator->isLength('description',
                $description,
                TTi18n::gettext('Description is invalid'),
                1, 255)
        ) {
            $this->data['description'] = $description;

            return true;
        }

        return false;
    }

    public function setUser($ids)
    {
        Debug::text('Setting User IDs : ', __FILE__, __LINE__, __METHOD__, 10);
        if (is_array($ids) and count($ids) > 0) {
            global $current_user;

            //Remove any of the selected employees from other permission control objects first.
            //So there we can switch employees from one group to another in a single action.
            $pulf = TTnew('PermissionUserListFactory');
            $pulf->getByCompanyIdAndUserIdAndNotPermissionControlId($this->getCompany(), $ids, (int)$this->getId());
            if ($pulf->getRecordCount() > 0) {
                Debug::text('Found User IDs assigned to another Permission Group, unassigning them!', __FILE__, __LINE__, __METHOD__, 10);
                foreach ($pulf as $pu_obj) {
                    if (!is_object($current_user) or (is_object($current_user) and $current_user->getID() != $pu_obj->getUser())) { //Not Acting on currently logged in user.
                        $pu_obj->Delete();
                    }
                }
            }
            unset($pulf, $pu_obj);

            $tmp_ids = array();

            if (!$this->isNew()) {
                //If needed, delete mappings first.
                $pulf = TTnew('PermissionUserListFactory');
                $pulf->getByPermissionControlId($this->getId());
                foreach ($pulf as $obj) {
                    $id = $obj->getUser();
                    Debug::text('Permission Control ID: ' . $obj->getPermissionControl() . ' ID: ' . $id, __FILE__, __LINE__, __METHOD__, 10);

                    //Delete users that are not selected.
                    if (!in_array($id, $ids)) {
                        if (is_object($current_user) and $current_user->getID() == $id) { //Not Acting on currently logged in user.
                            $this->Validator->isTrue('user',
                                false,
                                TTi18n::gettext('Unable to remove your own record from a permission group'));
                        } else {
                            Debug::text('Deleting: ' . $id, __FILE__, __LINE__, __METHOD__, 10);
                            $this->tmp_previous_user_ids[] = $id;
                            $obj->Delete();
                        }
                    } else {
                        //Save ID's that need to be updated.
                        Debug::text('NOT Deleting : ' . $id, __FILE__, __LINE__, __METHOD__, 10);
                        $tmp_ids[] = $id;
                    }
                }
                unset($id, $obj);
            }

            //Insert new mappings.
            $ulf = TTnew('UserListFactory');

            foreach ($ids as $id) {
                if (isset($ids) and !in_array($id, $tmp_ids)) {
                    //Remove users from any other permission control object
                    //first, otherwise there is a gap where an employee has
                    //no permissions, this is especially bad for administrators
                    //who are currently logged in.
                    $puf = TTnew('PermissionUserFactory');
                    $puf->setPermissionControl($this->getId());
                    $puf->setUser($id);

                    $obj = $ulf->getById($id)->getCurrent();

                    if ($this->Validator->isTrue('user',
                        $puf->Validator->isValid(),
                        TTi18n::gettext('Selected employee is invalid, or already assigned to another permission group') . ' (' . $obj->getFullName() . ')')
                    ) {
                        $puf->save();
                    }
                }
            }

            return true;
        }

        Debug::text('No User IDs to set.', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function setPermission($permission_arr, $old_permission_arr = array())
    {
        if ($this->getId() == false) {
            return false;
        }

        if ($this->Validator->getValidateOnly() == true) {
            return true;
        }

        global $profiler, $config_vars;
        $profiler->startTimer('setPermission');

        //Since implementing the HTML5 Install Wizard, which uses the API, we have to check to see if the installer is enabled, and if so skip this next block of code.
        if (defined('FAIRNESS_API') and FAIRNESS_API == true
            and (isset($config_vars['other']['installer_enabled']) and $config_vars['other']['installer_enabled'] == 0)
        ) {
            //When creating a new permission group this causes it to be really slow as it creates a record for every permission that is set to DENY.

            //If we do the permission diff it messes up the HTML interface.
            if (!is_array($old_permission_arr) or (is_array($old_permission_arr) and count($old_permission_arr) == 0)) {
                $old_permission_arr = $this->getPermission();
                //Debug::Text(' Old Permissions: '. count($old_permission_arr), __FILE__, __LINE__, __METHOD__, 10);
            }

            $permission_options = $this->getPermissionOptions();
            //Debug::Arr($permission_options, ' Permission Options: '. count($permission_options), __FILE__, __LINE__, __METHOD__, 10);

            $permission_arr = Misc::arrayMergeRecursiveDistinct((array)$permission_options, (array)$permission_arr);
            //Debug::Text(' New Permissions: '. count($permission_arr), __FILE__, __LINE__, __METHOD__, 10);
            //Debug::Arr($permission_arr, ' Final Permissions: '. count($permission_arr), __FILE__, __LINE__, __METHOD__, 10);
        }
        $pf = TTnew('PermissionFactory');

        //Don't Delete all previous permissions, do that in the Permission class.
        if (isset($permission_arr) and is_array($permission_arr) and count($permission_arr) > 0) {
            foreach ($permission_arr as $section => $permissions) {
                //Debug::Text('	 Section: '. $section, __FILE__, __LINE__, __METHOD__, 10);

                foreach ($permissions as $name => $value) {
                    //Debug::Text('		Name: '. $name .' - Value: '. $value, __FILE__, __LINE__, __METHOD__, 10);
                    if ((
                            !isset($old_permission_arr[$section][$name])
                            or (isset($old_permission_arr[$section][$name]) and $value != $old_permission_arr[$section][$name])
                        )
                        and $pf->isIgnore($section, $name) == false
                    ) {
                        if ($value == 0 or $value == 1) {
                            Debug::Text('	 Modifying/Adding Section: ' . $section . ' Permission: ' . $name . ' - Value: ' . $value, __FILE__, __LINE__, __METHOD__, 10);
                            $tmp_pf = TTnew('PermissionFactory');
                            $tmp_pf->setCompany($this->getCompanyObject()->getId());
                            $tmp_pf->setPermissionControl($this->getId());
                            $tmp_pf->setSection($section, true); //Disable error checking for performance optimization.
                            $tmp_pf->setName($name, true); //Disable error checking for performance optimization.
                            $tmp_pf->setValue((int)$value);
                            if ($tmp_pf->isValid()) {
                                $tmp_pf->save();
                            }
                        }
                    } //else { //Debug::Text('	   Permission didnt change... Skipping', __FILE__, __LINE__, __METHOD__, 10);
                }
            }
        }

        $profiler->stopTimer('setPermission');

        return true;
    }

    public function getPermission()
    {
        $plf = TTnew('PermissionListFactory');
        $plf->getByCompanyIdAndPermissionControlId($this->getCompany(), $this->getId());
        if ($plf->getRecordCount() > 0) {
            $current_permissions = array();
            Debug::Text('Found Permissions: ' . $plf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
            foreach ($plf as $p_obj) {
                $current_permissions[$p_obj->getSection()][$p_obj->getName()] = $p_obj->getValue();
            }

            return $current_permissions;
        }

        return false;
    }

    public function getPermissionOptions()
    {
        $retval = array();

        $pf = TTnew('PermissionFactory');
        $sections = $pf->getOptions('section');
        $names = $pf->getOptions('name');
        if (is_array($names)) {
            foreach ($names as $section => $permission_arr) {
                if (($pf->isIgnore($section, null) == false)) {
                    foreach ($permission_arr as $name => $display_name) {
                        if ($pf->isIgnore($section, $name) == false) {
                            if (isset($sections[$section])) {
                                $retval[$section][$name] = 0;
                            }
                        }
                    }
                    unset($display_name); //code standards
                }
            }
        }

        return $retval;
    }

    public function getCompanyObject()
    {
        return $this->getGenericObject('CompanyListFactory', $this->getCompany(), 'company_obj');
    }

    public function touchUpdatedByAndDate($permission_control_id = null)
    {
        global $current_user;

        if (is_object($current_user)) {
            $user_id = $current_user->getID();
        } else {
            return false;
        }

        $ph = array(
            'updated_date' => TTDate::getTime(),
            'updated_by' => $user_id,
            'id' => ($permission_control_id == '') ? (int)$this->getID() : (int)$permission_control_id,
        );

        $query = 'update ' . $this->getTable() . ' set updated_date = ?, updated_by = ? where id = ?';

        try {
            $this->db->Execute($query, $ph);
        } catch (Exception $e) {
            throw new DBError($e);
        }
    }

    public function preSave()
    {
        if ($this->getLevel() == '' or $this->getLevel() == 0) {
            $this->setLevel(1);
        }

        return true;
    }

    public function getLevel()
    {
        if (isset($this->data['level'])) {
            return (int)$this->data['level'];
        }

        return false;
    }

    public function setLevel($value)
    {
        $value = trim($value);

        if ($this->Validator->inArrayKey('level',
            $value,
            TTi18n::gettext('Incorrect Level'),
            $this->getOptions('level'))
        ) {
            $this->data['level'] = $value;

            return true;
        }

        return false;
    }

    //Quick way to touch the updated_date, updated_by when adding/removing employees from the UserFactory.

    public function postSave()
    {
        $pf = TTnew('PermissionFactory');

        $clear_cache_user_ids = array_merge((array)$this->getUser(), (array)$this->tmp_previous_user_ids);
        foreach ($clear_cache_user_ids as $user_id) {
            $pf->clearCache($user_id, $this->getCompany());
        }
    }

    public function getUser()
    {
        $pulf = TTnew('PermissionUserListFactory');
        $pulf->getByPermissionControlId($this->getId());

        $list = array();
        foreach ($pulf as $obj) {
            $list[] = $obj->getUser();
        }

        if (empty($list) == false) {
            return $list;
        }

        return false;
    }

    public function setObjectFromArray($data)
    {
        if (is_array($data)) {
            $variable_function_map = $this->getVariableToFunctionMap();
            foreach ($variable_function_map as $key => $function) {
                if (isset($data[$key])) {
                    $function = 'set' . $function;
                    switch ($key) {
                        default:
                            if (method_exists($this, $function)) {
                                $this->$function($data[$key]);
                            }
                            break;
                    }
                }
            }

            $this->setCreatedAndUpdatedColumns($data);

            return true;
        }

        return false;
    }

    //Support setting created_by, updated_by especially for importing data.
    //Make sure data is set based on the getVariableToFunctionMap order.

    public function getObjectAsArray($include_columns = null)
    {
        $data = array();
        $variable_function_map = $this->getVariableToFunctionMap();
        if (is_array($variable_function_map)) {
            foreach ($variable_function_map as $variable => $function_stub) {
                if ($include_columns == null or (isset($include_columns[$variable]) and $include_columns[$variable] == true)) {
                    $function = 'get' . $function_stub;
                    switch ($variable) {
                        case 'total_users':
                            $data[$variable] = $this->getColumn($variable);
                            break;
                        default:
                            if (method_exists($this, $function)) {
                                $data[$variable] = $this->$function();
                            }
                            break;
                    }
                }
            }
            $this->getCreatedAndUpdatedColumns($data, $include_columns);
        }

        return $data;
    }

    public function addLog($log_action)
    {
        return TTLog::addEntry($this->getId(), $log_action, TTi18n::getText('Permission Group') . ': ' . $this->getName(), null, $this->getTable(), $this);
    }

    public function getName()
    {
        return $this->data['name'];
    }
}
