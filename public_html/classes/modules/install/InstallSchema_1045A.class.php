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
class InstallSchema_1045A extends InstallSchema_Base
{
    public function preInstall()
    {
        Debug::text('preInstall: ' . $this->getVersion(), __FILE__, __LINE__, __METHOD__, 9);

        return true;
    }

    public function postInstall()
    {
        Debug::text('postInstall: ' . $this->getVersion(), __FILE__, __LINE__, __METHOD__, 9);

        //Go through each permission group, and enable absence/schedule edit field permissions for anyone who can edit absence/schedules.
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
                            $plf->getByCompanyIdAndPermissionControlIdAndSectionAndNameAndValue($c_obj->getId(), $pc_obj->getId(), 'absence', array('edit', 'edit_own', 'edit_child'), 1);
                            if ($plf->getRecordCount() > 0) {
                                Debug::text('Found permission group with Edit Absence enabled: ' . $plf->getCurrent()->getValue(), __FILE__, __LINE__, __METHOD__, 9);
                                $pc_obj->setPermission(
                                    array('absence' => array(
                                        'edit_branch' => true,
                                        'edit_department' => true,
                                    )
                                    )
                                );
                            } else {
                                Debug::text('Permission group does NOT have absences enabled...', __FILE__, __LINE__, __METHOD__, 9);
                            }

                            $plf->getByCompanyIdAndPermissionControlIdAndSectionAndNameAndValue($c_obj->getId(), $pc_obj->getId(), 'schedule', array('edit', 'edit_own', 'edit_child'), 1);
                            if ($plf->getRecordCount() > 0) {
                                Debug::text('Found permission group with Edit Schedule enabled: ' . $plf->getCurrent()->getValue(), __FILE__, __LINE__, __METHOD__, 9);
                                $pc_obj->setPermission(
                                    array('schedule' => array(
                                        'edit_branch' => true,
                                        'edit_department' => true,
                                        'edit_job' => true,
                                        'edit_job_item' => true,
                                    )
                                    )
                                );
                            } else {
                                Debug::text('Permission group does NOT have schedules enabled...', __FILE__, __LINE__, __METHOD__, 9);
                            }
                        }
                    }
                }
            }
        }

        return true;
    }
}
