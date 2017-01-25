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
 * @package Modules\Install
 */
class InstallSchema_1014A extends InstallSchema_Base
{
    protected $permission_groups = null;
    protected $permission_group_users = null;

    public function preInstall()
    {
        Debug::text('preInstall: ' . $this->getVersion(), __FILE__, __LINE__, __METHOD__, 9);

        /*
            Permission System Upgrade.
                - Use direct query to get current permission data and store in memory for postInstall.
        */
        $query = 'select company_id, user_id, section, name, value from permission where deleted = 0 order by company_id, user_id, section, name';
        $rs = $this->getDatabaseConnection()->Execute($query);
        $user_permission_data = array();
        foreach ($rs as $row) {
            $user_permission_data[$row['company_id']][$row['user_id']][$row['section']][$row['name']] = $row['value'];

            //If employee has Punch In/Out permission enabled, add individual punch permission too.
            if ($row['section'] == 'punch'
                and $row['name'] == 'punch_in_out'
                and $row['value'] == 1
            ) {
                $user_permission_data[$row['company_id']][$row['user_id']]['punch']['edit_transfer'] = 1;
                $user_permission_data[$row['company_id']][$row['user_id']]['punch']['edit_branch'] = 1;
                $user_permission_data[$row['company_id']][$row['user_id']]['punch']['edit_department'] = 1;
                $user_permission_data[$row['company_id']][$row['user_id']]['punch']['edit_note'] = 1;
                $user_permission_data[$row['company_id']][$row['user_id']]['punch']['edit_other_id1'] = 1;
                $user_permission_data[$row['company_id']][$row['user_id']]['punch']['edit_other_id2'] = 1;
                $user_permission_data[$row['company_id']][$row['user_id']]['punch']['edit_other_id3'] = 1;
                $user_permission_data[$row['company_id']][$row['user_id']]['punch']['edit_other_id4'] = 1;
                $user_permission_data[$row['company_id']][$row['user_id']]['punch']['edit_other_id5'] = 1;

                if (isset($user_permission_data[$row['company_id']][$row['user_id']]['job']['enabled'])
                    and $user_permission_data[$row['company_id']][$row['user_id']]['job']['enabled'] == 1
                ) {
                    $user_permission_data[$row['company_id']][$row['user_id']]['punch']['edit_job'] = 1;
                    $user_permission_data[$row['company_id']][$row['user_id']]['punch']['edit_job_item'] = 1;
                    $user_permission_data[$row['company_id']][$row['user_id']]['punch']['edit_quantity'] = 1;
                    $user_permission_data[$row['company_id']][$row['user_id']]['punch']['edit_bad_quantity'] = 1;
                }
            }

            $user_permission_data[$row['company_id']][$row['user_id']]['absence']['enabled'] = 1;
            $user_permission_data[$row['company_id']][$row['user_id']]['absence']['view_own'] = 1;

            //We added the "Absence" permission section, so we need to copy punch permissions to this.
            if ($row['section'] == 'punch' and $row['name'] == 'view_child' and $row['value'] == 1) {
                $user_permission_data[$row['company_id']][$row['user_id']]['absence']['view_child'] = 1;
            }
            if ($row['section'] == 'punch' and $row['name'] == 'edit_child' and $row['value'] == 1) {
                $user_permission_data[$row['company_id']][$row['user_id']]['absence']['edit_child'] = 1;
                $user_permission_data[$row['company_id']][$row['user_id']]['absence']['add'] = 1;
            }
            if ($row['section'] == 'punch' and $row['name'] == 'delete_child' and $row['value'] == 1) {
                $user_permission_data[$row['company_id']][$row['user_id']]['absence']['delete_child'] = 1;
            }

            if ($row['section'] == 'punch' and $row['name'] == 'view' and $row['value'] == 1) {
                $user_permission_data[$row['company_id']][$row['user_id']]['absence']['view'] = 1;
            }
            if ($row['section'] == 'punch' and $row['name'] == 'edit' and $row['value'] == 1) {
                $user_permission_data[$row['company_id']][$row['user_id']]['absence']['edit'] = 1;
                $user_permission_data[$row['company_id']][$row['user_id']]['absence']['add'] = 1;
            }
            if ($row['section'] == 'punch' and $row['name'] == 'delete' and $row['value'] == 1) {
                $user_permission_data[$row['company_id']][$row['user_id']]['absence']['delete'] = 1;
            }
        }

        //Group Permissions together
        if (empty($user_permission_data) == false) {
            foreach ($user_permission_data as $company_id => $user_ids) {
                //Get default employee permissions to start from.
                if (isset($user_permission_data[$company_id]['-1'])) {
                    $this->permission_groups[$company_id]['default'] = $user_permission_data[$company_id]['-1'];
                } else {
                    $this->permission_groups[$company_id]['default'] = array();
                }
                unset($user_permission_data[$company_id]['-1']);

                $x = 1;
                foreach ($user_ids as $user_id => $permission_user_data) {
                    $permission_no_differences_found = false;

                    foreach ($this->permission_groups[$company_id] as $group_name => $permission_group_data) {
                        Debug::text('Company ID: ' . $company_id . ' Checking Permission Differences Between User ID: ' . $user_id . ' AND Group: ' . $group_name, __FILE__, __LINE__, __METHOD__, 10);

                        //Need to diff the arrays both directions, because the diff function only checks in one direction on its own.
                        $forward_permission_diff_arr = Misc::arrayDiffAssocRecursive($permission_user_data, $permission_group_data);
                        $reverse_permission_diff_arr = Misc::arrayDiffAssocRecursive($permission_group_data, $permission_user_data);
                        Debug::text('Permission User Data Count: ' . count($permission_user_data, COUNT_RECURSIVE) . ' Permission Group Data Count: ' . count($permission_group_data, COUNT_RECURSIVE), __FILE__, __LINE__, __METHOD__, 10);
                        if ($forward_permission_diff_arr == false and $reverse_permission_diff_arr == false) {
                            Debug::text('No Differences Found in Permissions! ', __FILE__, __LINE__, __METHOD__, 10);
                            $permission_no_differences_found = true;
                            if ($user_id != -1) {
                                $this->permission_group_users[$company_id][$group_name][] = $user_id;
                            }
                            break;
                        } else {
                            Debug::text('Differences Found in Permissions! ', __FILE__, __LINE__, __METHOD__, 10);
                            //Debug::Arr($forward_permission_diff_arr, 'Forward Permission Differences:', __FILE__, __LINE__, __METHOD__, 10);
                            //Debug::Arr($reverse_permission_diff_arr, 'Reverse Permission Differences:', __FILE__, __LINE__, __METHOD__, 10);
                        }
                    }
                    unset($forward_permission_diff_arr, $reverse_permission_diff_arr);

                    if ($permission_no_differences_found == false) {
                        Debug::text('Creating New Permission Group...: ' . $x, __FILE__, __LINE__, __METHOD__, 10);

                        $pf = TTnew('PermissionFactory');
                        $preset_arr = array(10, 18, 20, 30, 40);
                        $preset_match = array();
                        foreach ($preset_arr as $preset) {
                            $tmp_preset_permissions = $pf->getPresetPermissions($preset, array());
                            $preset_permission_diff_arr = Misc::arrayDiffAssocRecursive($permission_user_data, $tmp_preset_permissions);

                            $preset_permission_diff_count = count($preset_permission_diff_arr, COUNT_RECURSIVE);
                            Debug::text('Preset Permission Diff Count...: ' . $preset_permission_diff_count . ' Preset ID: ' . $preset, __FILE__, __LINE__, __METHOD__, 10);
                            $preset_match[$preset] = $preset_permission_diff_count;
                        }
                        unset($preset_arr, $tmp_preset_permissions);

                        krsort($preset_match);
                        //Flip the array so if there are more then one preset with the same match_count, we use the smallest preset value.
                        $preset_match = array_flip($preset_match);
                        //Flip the array back so the key is the match_preset again.
                        $preset_match = array_flip($preset_match);
                        foreach ($preset_match as $best_match_preset => $match_value) {
                            break;
                        }
                        //Debug::Arr($preset_match, 'zPreset Match Array: ', __FILE__, __LINE__, __METHOD__, 10);

                        //Create new group name, based on closest preset match
                        $preset_name_options = $pf->getOptions('preset');
                        if (isset($preset_name_options[$best_match_preset])) {
                            $group_name = $preset_name_options[$best_match_preset] . ' (' . $match_value . ') #' . $x;
                        } else {
                            $group_name = 'Group #' . $x;
                        }
                        Debug::text('Group Name: ' . $group_name, __FILE__, __LINE__, __METHOD__, 10);
                        $this->permission_groups[$company_id][$group_name] = $permission_user_data;

                        if ($user_id != -1) {
                            $this->permission_group_users[$company_id][$group_name][] = $user_id;
                        }
                        unset($pf, $best_match_preset, $match_value);

                        $x++;
                    }
                }
                ksort($this->permission_group_users[$company_id]);
                ksort($this->permission_groups[$company_id]);
                unset($permission_user_data, $permission_group_data, $group_name, $company_id, $user_id);
            }
            unset($user_permission_data);
        }

        return true;
    }

    public function postInstall()
    {
        // @codingStandardsIgnoreStart
        global $cache;
        //assumed used elsewhere
        // @codingStandardsIgnoreEnd
        Debug::text('postInstall: ' . $this->getVersion(), __FILE__, __LINE__, __METHOD__, 9);

        Debug::text('l: ' . $this->getVersion(), __FILE__, __LINE__, __METHOD__, 9);

        /*
            Take permission groups we put into memory from preInstall and create them now,
            after schema has been updated.
        */
        if (isset($this->permission_groups) and is_array($this->permission_groups)) {
            //Create permission groups and assign proper employees to each.
            //Debug::Arr($this->permission_groups, 'All Permission Groups: ', __FILE__, __LINE__, __METHOD__, 9);
            foreach ($this->permission_groups as $company_id => $permission_group_data) {
                //Get all active users for this company, so we can assign them
                //to the default permission group.
                $ulf = TTnew('UserListFactory');
                $ulf->getByCompanyId($company_id);
                $all_user_ids = array_keys((array)$ulf->getArrayByListFactory($ulf, false, true));
                $assigned_user_ids = array();
                foreach ($permission_group_data as $group_name => $permission_data) {
                    Debug::text('zGroup Name: ' . $group_name, __FILE__, __LINE__, __METHOD__, 10);

                    $pcf = TTnew('PermissionControlFactory');
                    $pcf->StartTransaction();
                    $pcf->setCompany($company_id);
                    $pcf->setName(ucfirst($group_name));
                    $pcf->setDescription('Automatically Created By Installer');

                    if ($pcf->isValid()) {
                        $pcf->Save(false);

                        if (strtolower($group_name) == 'default') {
                            //Assign all unassigned users to this permission group.
                            $tmp_user_ids = array_merge((array)$this->permission_group_users[$company_id][$group_name], array_diff($all_user_ids, $assigned_user_ids));
                            //Debug::Arr($all_user_ids, 'Default Group All User IDs:', __FILE__, __LINE__, __METHOD__, 10);
                            //Debug::Arr($assigned_user_ids, 'Default Group All User IDs:', __FILE__, __LINE__, __METHOD__, 10);
                            //Debug::Arr($tmp_user_ids, 'Default Group User IDs:', __FILE__, __LINE__, __METHOD__, 10);
                            $pcf->setUser($tmp_user_ids);
                            unset($tmp_user_ids);
                        } else {
                            if (isset($this->permission_group_users[$company_id][$group_name]) and is_array($this->permission_group_users[$company_id][$group_name])) {
                                $pcf->setUser($this->permission_group_users[$company_id][$group_name]);
                                $assigned_user_ids = array_merge($assigned_user_ids, $this->permission_group_users[$company_id][$group_name]);
                            }
                        }

                        if (is_array($permission_data)) {
                            $pcf->setPermission($permission_data);
                        }
                    }
                    //$pcf->FailTransaction();
                    $pcf->CommitTransaction();
                }
                unset($all_user_ids, $assigned_user_ids);
            }
        }

        return true;
    }
}
