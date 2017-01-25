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
class InstallSchema_1060A extends InstallSchema_Base
{
    public function preInstall()
    {
        Debug::text('preInstall: ' . $this->getVersion(), __FILE__, __LINE__, __METHOD__, 9);

        return true;
    }

    public function postInstall()
    {
        Debug::text('postInstall: ' . $this->getVersion(), __FILE__, __LINE__, __METHOD__, 9);

        //Go through each permission group, and enable affordable_care report for for anyone who can view W2's.
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
                            $plf->getByCompanyIdAndPermissionControlIdAndSectionAndNameAndValue($c_obj->getId(), $pc_obj->getId(), 'report', 'view_formW2', 1);
                            if ($plf->getRecordCount() > 0) {
                                Debug::text('Found permission group with view_formW2 enabled: ' . $plf->getCurrent()->getValue(), __FILE__, __LINE__, __METHOD__, 9);
                                $pc_obj->setPermission(
                                    array('report' => array(
                                        'view_affordable_care' => true,
                                    )
                                    )
                                );
                            } else {
                                Debug::text('Permission group does NOT have view_formW2 enabled...', __FILE__, __LINE__, __METHOD__, 9);
                            }
                        }
                    }
                }
            }
        }

        //Go through all stations and disable ones that don't have any employees activated for them.
        //This greatly speeds up station checks, as most stations never have employees activated.
        $query = 'update station set status_id = 10
					where status_id = 20
					AND
					(
						( user_group_selection_type_id = 20 AND NOT EXISTS( select b.id from station_user_group as b WHERE id = b.station_id ) )
						AND
						( branch_selection_type_id = 20 AND NOT EXISTS( select c.id from station_branch as c WHERE id = c.station_id ) )
						AND
						( department_selection_type_id = 20 AND NOT EXISTS( select d.id from station_department as d WHERE id = d.station_id ) )
						AND
						NOT EXISTS( select f.id from station_exclude_user as f WHERE id = f.station_id )
						AND
						NOT EXISTS( select e.id from station_include_user as e WHERE id = e.station_id )
					)
					AND ( deleted = 0 )';
        $this->getDatabaseConnection()->Execute($query);

        return true;
    }
}
