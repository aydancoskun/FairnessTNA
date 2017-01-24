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
 * @package Modules\Install
 */
class InstallSchema_1031A extends InstallSchema_Base
{
    public function preInstall()
    {
        Debug::text('preInstall: ' . $this->getVersion(), __FILE__, __LINE__, __METHOD__, 9);

        if (strncmp($this->db->databaseType, 'mysql', 5) == 0) {
            $this->db->GenID('company_generic_map_id_seq'); //Make sure the sequence exists so it can be updated.
        }

        return true;
    }

    public function postInstall()
    {
        Debug::text('postInstall: ' . $this->getVersion(), __FILE__, __LINE__, __METHOD__, 9);

        //Go through all pay period schedules and update the annual pay period column
        $ppslf = TTnew('PayPeriodScheduleListFactory');
        $ppslf->getAll();
        if ($ppslf->getRecordCount() > 0) {
            foreach ($ppslf as $pps_obj) {
                $pps_obj->setAnnualPayPeriods($pps_obj->calcAnnualPayPeriods());
                if ($pps_obj->isValid()) {
                    $pps_obj->Save();
                }
            }
        }

        //Go through all employee wages and update HourlyRate to the accurate annual hourly rate.
        //**Handle this in 1034A postInstall() instead, as it needs to handle incorrect effective_dates properly.
        /*
        $uwlf = TTnew( 'UserWageListFactory' );
        $uwlf->getAll();
        if ( $uwlf->getRecordCount() > 0 ) {
            foreach( $uwlf as $uw_obj ) {
                $uw_obj->setHourlyRate( $uw_obj->calcHourlyRate( time(), TRUE ) );
                if ( $uw_obj->isValid() ) {
                    $uw_obj->Save();
                }
            }
        }
        */

        //Upgrade to new hierarchy format.
        $clf = TTnew('CompanyListFactory');
        $clf->getAll();
        if ($clf->getRecordCount() > 0) {
            $object_types = array();
            foreach ($clf as $c_obj) {
                if ($c_obj->getStatus() != 30) {
                    $company_id = $c_obj->getId();
                    Debug::Text(' Company ID: ' . $company_id, __FILE__, __LINE__, __METHOD__, 10);

                    $hclf = TTnew('HierarchyControlListFactory');
                    $hclf->StartTransaction();
                    $hclf->getByCompanyId($company_id);
                    if ($hclf->getRecordCount() > 0) {
                        foreach ($hclf as $hc_obj) {
                            $paths_to_root = array();
                            $hierarchy_id = $hc_obj->getId();

                            $hlf = TTnew('HierarchyListFactory');
                            $hierarchy_users = $hlf->getByCompanyIdAndHierarchyControlId($company_id, $hierarchy_id);
                            if (is_array($hierarchy_users) and count($hierarchy_users) > 0) {
                                $hotlf = TTnew('HierarchyObjectTypeListFactory');
                                $hotlf->getByHierarchyControlId($hierarchy_id);
                                if ($hotlf->getRecordCount() > 0) {
                                    foreach ($hotlf as $hot_obj) {
                                        //When upgrading from pre. v3.1ish to post 3.5 causing hierarchies to not get carried over due to new object types.
                                        //Replace object_type_id = 50 with new ones in the 1000 range.
                                        if ($hot_obj->getObjectType() == 50) {
                                            $object_types[$hierarchy_id][] = 1010;
                                            $object_types[$hierarchy_id][] = 1020;
                                            $object_types[$hierarchy_id][] = 1030;
                                            $object_types[$hierarchy_id][] = 1040;
                                            $object_types[$hierarchy_id][] = 1100;
                                        } else {
                                            $object_types[$hierarchy_id][] = $hot_obj->getObjectType();
                                        }
                                    }
                                }

                                //Not all hierarchies can be converted, and if one error occurs then all hierarchy conversions are rolled back
                                //to prevent an invalid hierarchy from being created and possibly becoming a security risk.
                                $parent_groups = array();
                                foreach ($hierarchy_users as $hierarchy_user_arr) {
                                    Debug::Text(' Checking User ID: ' . $hierarchy_user_arr['id'], __FILE__, __LINE__, __METHOD__, 10);
                                    $id = $hierarchy_user_arr['id'];

                                    $tmp_id = $id;
                                    $i = 0;
                                    do {
                                        Debug::Text(' Iteration...', __FILE__, __LINE__, __METHOD__, 10);
                                        $hlf_b = TTnew('HierarchyListFactory');
                                        $parents = $hlf_b->getParentLevelIdArrayByHierarchyControlIdAndUserId($hierarchy_id, $tmp_id);
                                        sort($parents);

                                        $level = ($hlf_b->getFastTreeObject()->getLevel($tmp_id) - 1);

                                        if (is_array($parents) and count($parents) > 0) {
                                            $parent_users = array();
                                            foreach ($parents as $user_id) {
                                                $parent_users[] = $user_id;
                                            }

                                            $parent_groups[$level] = $parent_users;
                                            unset($parent_users);
                                        }

                                        if (isset($parents[0])) {
                                            $tmp_id = $parents[0];
                                        }

                                        $i++;
                                    } while (is_array($parents) and count($parents) > 0 and $i < 100);

                                    if (isset($parent_groups)) {
                                        $serialized_path = serialize($parent_groups);
                                        $paths_to_root[$serialized_path][] = $id;
                                        unset($serialized_path);
                                    }
                                    unset($parent_groups, $parents);
                                }
                            }
                            Debug::Arr($paths_to_root, ' Paths To Root: ', __FILE__, __LINE__, __METHOD__, 10);

                            //Decode path_to_root array
                            if (isset($paths_to_root) and count($paths_to_root) > 0) {
                                $decoded_paths = array();
                                foreach ($paths_to_root as $serialized_path => $children) {
                                    $path_arr = unserialize($serialized_path);
                                    $decoded_paths[] = array('hierarchy_control_id' => $hierarchy_id, 'path' => $path_arr, 'children' => $children);
                                }
                                unset($path_arr, $children);

                                Debug::Arr($decoded_paths, ' Decoded Paths: ', __FILE__, __LINE__, __METHOD__, 10);

                                if (empty($decoded_paths) == false) {
                                    foreach ($decoded_paths as $decoded_path) {
                                        Debug::Text(' Company ID: ' . $company_id, __FILE__, __LINE__, __METHOD__, 10);

                                        //Create new hierarchy_control
                                        $hcf = TTnew('HierarchyControlFactory');
                                        $hcf->setID($hcf->getNextInsertId());
                                        $hcf->setCompany($company_id);
                                        $hcf->setObjectType($object_types[$decoded_path['hierarchy_control_id']]);

                                        //Generate meaningful name
                                        $name = false;
                                        if (isset($decoded_path['path']) and is_array($decoded_path['path'])) {
                                            ksort($decoded_path['path']); //Sort by level.

                                            foreach ($decoded_path['path'] as $level => $superior_ids) {
                                                foreach ($superior_ids as $superior_id) {
                                                    $ulf = TTnew('UserListFactory');
                                                    $ulf->getById($superior_id);

                                                    if ($ulf->getRecordCount() > 0) {
                                                        $name[] = $level . '. ' . $ulf->getCurrent()->getFullName();
                                                    }
                                                }
                                            }
                                            unset($level, $superior_ids, $superior_id);
                                        }

                                        if (isset($name)) {
                                            $name = $hc_obj->getName() . ' ' . implode(', ', $name) . ' (#' . rand(1000, 9999) . ')';
                                        } else {
                                            $name = $hc_obj->getName() . ' (#' . rand(1000, 9999) . ')';
                                        }
                                        Debug::Text('Hierarchy Control ID: ' . $hcf->getID() . ' Name: ' . $name, __FILE__, __LINE__, __METHOD__, 10);

                                        $hcf->setName(substr($name, 0, 249));
                                        $hcf->setDescription(TTi18n::getText('Automatically created by Fairness'));

                                        Debug::Text('zHierarchy Control ID: ' . $hcf->getID(), __FILE__, __LINE__, __METHOD__, 10);
                                        $hcf->setUser($decoded_path['children']);

                                        if (isset($decoded_path['path']) and is_array($decoded_path['path'])) {
                                            foreach ($decoded_path['path'] as $level => $superior_ids) {
                                                foreach ($superior_ids as $superior_id) {
                                                    $hlf = TTnew('HierarchyLevelFactory');
                                                    $hlf->setHierarchyControl($hcf->getID());
                                                    $hlf->setLevel($level);
                                                    $hlf->setUser($superior_id);

                                                    if ($hlf->isValid()) {
                                                        $hlf->Save();
                                                        Debug::Text('Saving Level Row ID... User ID: ' . $superior_id, __FILE__, __LINE__, __METHOD__, 10);
                                                    }
                                                }
                                            }
                                            unset($level, $superior_ids, $superior_id);
                                        }

                                        if ($hcf->isValid()) {
                                            $hcf->Save(true, true);
                                        }
                                    }
                                }
                                unset($decoded_paths);
                            }

                            //Delete existing hierarchy control.
                            $hc_obj->setDeleted(true);
                            if ($hc_obj->isValid() == true) {
                                $hc_obj->Save();
                            }
                        }
                    }

                    //$hclf->FailTransaction();
                    $hclf->CommitTransaction();
                }
            }
        }

        //Go through each permission group, and enable break policies for anyone who can see meal policies
        $clf = TTnew('CompanyListFactory');
        $clf->getAll();
        if ($clf->getRecordCount() > 0) {
            foreach ($clf as $c_obj) {
                Debug::text('Company: ' . $c_obj->getName(), __FILE__, __LINE__, __METHOD__, 9);
                if ($c_obj->getStatus() != 30) {
                    $pclf = TTnew('PermissionControlListFactory');
                    $pclf->getByCompanyId($c_obj->getId(), null, null, null, array('name' => 'asc')); //Force order to prevent references to columns that haven't been created yet.
                    if ($pclf->getRecordCount() > 0) {
                        foreach ($pclf as $pc_obj) {
                            Debug::text('Permission Group: ' . $pc_obj->getName(), __FILE__, __LINE__, __METHOD__, 9);
                            $plf = TTnew('PermissionListFactory');
                            $plf->getByCompanyIdAndPermissionControlIdAndSectionAndNameAndValue($c_obj->getId(), $pc_obj->getId(), 'meal_policy', 'enabled', 1);
                            if ($plf->getRecordCount() > 0) {
                                Debug::text('Found permission group with meal policy enabled: ' . $plf->getCurrent()->getValue(), __FILE__, __LINE__, __METHOD__, 9);
                                $pc_obj->setPermission(
                                    array('break_policy' => array(
                                        'enabled' => true,
                                        'view' => true,
                                        'add' => true,
                                        'edit' => true,
                                        'delete' => true,
                                    )
                                    )

                                );
                            } else {
                                Debug::text('Permission group does NOT have meal policy enabled...', __FILE__, __LINE__, __METHOD__, 9);
                            }
                        }
                    }
                }
            }
        }

        //This used to be handled in 1016T postInstall function, but when upgrading really old versions (ie: 2.2.22) of Fairness to newer
        //ones (ie: 3.3.2) it would fail because new columns have been added to the company_deduction table in schema 1031A.
        //Migrate to completely separate Tax / Deductions for social security, as the employee and employer rates are different now.
        $clf = TTnew('CompanyListFactory');
        $clf->getAll();
        if ($clf->getRecordCount() > 0) {
            foreach ($clf as $c_obj) {
                Debug::text('Company: ' . $c_obj->getName(), __FILE__, __LINE__, __METHOD__, 9);
                if ($c_obj->getStatus() != 30 and $c_obj->getCountry() == 'US') {
                    //Get PayStub Link accounts
                    $pseallf = TTnew('PayStubEntryAccountLinkListFactory');
                    $pseallf->getByCompanyId($c_obj->getID());
                    if ($pseallf->getRecordCount() > 0) {
                        $psea_obj = $pseallf->getCurrent();
                    } else {
                        Debug::text('Failed getting PayStubEntryLink for Company ID: ' . $company_id, __FILE__, __LINE__, __METHOD__, 10);
                        continue;
                    }

                    $include_pay_stub_accounts = false;
                    $exclude_pay_stub_accounts = false;

                    $cdlf = TTnew('CompanyDeductionListFactory');
                    $cdlf->getByCompanyIdAndName($c_obj->getID(), 'Social Security - Employee');
                    if ($cdlf->getRecordCount() == 1) {
                        $cd_obj = $cdlf->getCurrent();
                        Debug::text('Found SS Employee Tax / Deduction, ID: ' . $c_obj->getID() . ' Percent: ' . $cd_obj->getUserValue1() . ' Wage Base: ' . $cd_obj->getUserValue2(), __FILE__, __LINE__, __METHOD__, 9);
                        if ($cd_obj->getCalculation() == 15 and $cd_obj->getUserValue1() == 6.2 and $cd_obj->getUserValue2() <= 106800) {
                            Debug::text('SS Employee Tax / Deduction Matches... Adjusting for 2011...', __FILE__, __LINE__, __METHOD__, 9);
                            $cd_obj->setUserValue1(4.2);
                            $cd_obj->setUserValue2(106800);
                            $include_pay_stub_accounts = $cd_obj->getIncludePayStubEntryAccount();
                            $exclude_pay_stub_accounts = $cd_obj->getExcludePayStubEntryAccount();

                            $cd_obj->ignore_column_list = true; //Prevents SQL errors due to new columns being added later on.
                            if ($cd_obj->isValid()) {
                                $cd_obj->Save();
                            }
                        }
                    } else {
                        Debug::text('Failed to find SS Employee Tax / Deduction for Company: ' . $c_obj->getName(), __FILE__, __LINE__, __METHOD__, 9);
                    }
                    unset($cdlf, $cd_obj);

                    $cdlf = TTnew('CompanyDeductionListFactory');
                    $cdlf->getByCompanyIdAndName($c_obj->getID(), 'Social Security - Employer');
                    if ($cdlf->getRecordCount() == 1) {
                        $cd_obj = $cdlf->getCurrent();
                        Debug::text('Found SS Employer Tax / Deduction, ID: ' . $c_obj->getID() . ' Percent: ' . $cd_obj->getUserValue1(), __FILE__, __LINE__, __METHOD__, 9);
                        if ($cd_obj->getCalculation() == 10 and $cd_obj->getUserValue1() == 100) {
                            Debug::text('SS Employer Tax / Deduction Matches... Adjusting for 2011...', __FILE__, __LINE__, __METHOD__, 9);
                            $cd_obj->setCalculation(15);
                            $cd_obj->setUserValue1(6.2);
                            $cd_obj->setUserValue2(106800);
                            if ($include_pay_stub_accounts !== false) {
                                Debug::text('Matching Include/Exclude accounts with SS Employee Entry...', __FILE__, __LINE__, __METHOD__, 9);
                                //Match include accounts with SS employee entry.
                                $cd_obj->setIncludePayStubEntryAccount($include_pay_stub_accounts);
                                $cd_obj->setExcludePayStubEntryAccount($exclude_pay_stub_accounts);
                            } else {
                                Debug::text('NOT Matching Include/Exclude accounts with SS Employee Entry...', __FILE__, __LINE__, __METHOD__, 9);
                                $cd_obj->setIncludePayStubEntryAccount(array($psea_obj->getTotalGross()));
                            }

                            $cd_obj->ignore_column_list = true; //Prevents SQL errors due to new columns being added later on.
                            if ($cd_obj->isValid()) {
                                $cd_obj->Save();
                            }
                        }
                    } else {
                        Debug::text('Failed to find SS Employer Tax / Deduction for Company: ' . $c_obj->getName(), __FILE__, __LINE__, __METHOD__, 9);
                    }
                }
            }
        }

        //Add MiscDaily cronjob to database.
        $cjf = TTnew('CronJobFactory');
        $cjf->setName('MiscDaily');
        $cjf->setMinute(55);
        $cjf->setHour(1);
        $cjf->setDayOfMonth('*');
        $cjf->setMonth('*');
        $cjf->setDayOfWeek('*');
        $cjf->setCommand('MiscDaily.php');
        $cjf->Save();

        //Add MiscWeekly cronjob to database.
        $cjf = TTnew('CronJobFactory');
        $cjf->setName('MiscWeekly');
        $cjf->setMinute(55);
        $cjf->setHour(1);
        $cjf->setDayOfMonth('*');
        $cjf->setMonth('*');
        $cjf->setDayOfWeek('0'); //Sunday morning.
        $cjf->setCommand('MiscWeekly.php');
        $cjf->Save();

        return true;
    }
}
