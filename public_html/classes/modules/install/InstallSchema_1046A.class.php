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
class InstallSchema_1046A extends InstallSchema_Base
{
    public function preInstall()
    {
        Debug::text('preInstall: ' . $this->getVersion(), __FILE__, __LINE__, __METHOD__, 9);

        return true;
    }

    public function postInstall()
    {
        Debug::text('postInstall: ' . $this->getVersion(), __FILE__, __LINE__, __METHOD__, 9);

        //Allow edit password/phone password permissions for all permission groups.
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
                            $plf->getByCompanyIdAndPermissionControlIdAndSectionAndNameAndValue($c_obj->getId(), $pc_obj->getId(), 'user', 'edit_own', 1);
                            if ($plf->getRecordCount() > 0) {
                                Debug::text('Found permission group with user edit own enabled: ' . $plf->getCurrent()->getValue(), __FILE__, __LINE__, __METHOD__, 9);
                                $pc_obj->setPermission(array('user' => array('edit_own_password' => true, 'edit_own_phone_password' => true)));
                            } else {
                                Debug::text('Permission group does NOT have user edit own report enabled...', __FILE__, __LINE__, __METHOD__, 9);
                            }
                        }
                    }
                }
            }
        }

        //Metaphoneize data
        $ulf = TTnew('UserListFactory');
        $ulf->getAll();
        if ($ulf->getRecordCount() > 0) {
            foreach ($ulf as $u_obj) {
                $ph = array(
                    'first_name_metaphone' => $u_obj->getFirstNameMetaphone($u_obj->setFirstNameMetaphone($u_obj->getFirstName())),
                    'last_name_metaphone' => $u_obj->getLastNameMetaphone($u_obj->setLastNameMetaphone($u_obj->getLastName())),
                    'id' => (int)$u_obj->getId(),
                );
                $query = 'update ' . $ulf->getTable() . ' set first_name_metaphone = ?, last_name_metaphone = ? where id = ?';
                $this->db->Execute($query, $ph);
            }
        }

        $clf = TTnew('CompanyListFactory');
        $clf->getAll();
        if ($clf->getRecordCount() > 0) {
            foreach ($clf as $c_obj) {
                $ph = array(
                    'name_metaphone' => $c_obj->getNameMetaphone($c_obj->setNameMetaphone($c_obj->getName())),
                    'id' => (int)$c_obj->getId(),
                );
                $query = 'update ' . $clf->getTable() . ' set name_metaphone = ? where id = ?';
                $this->db->Execute($query, $ph);
            }
        }

        $blf = TTnew('BranchListFactory');
        $blf->getAll();
        if ($blf->getRecordCount() > 0) {
            foreach ($blf as $b_obj) {
                $ph = array(
                    'name_metaphone' => $b_obj->getNameMetaphone($b_obj->setNameMetaphone($b_obj->getName())),
                    'id' => (int)$b_obj->getId(),
                );
                $query = 'update ' . $blf->getTable() . ' set name_metaphone = ? where id = ?';
                $this->db->Execute($query, $ph);
            }
        }

        $dlf = TTnew('DepartmentListFactory');
        $dlf->getAll();
        if ($dlf->getRecordCount() > 0) {
            foreach ($dlf as $d_obj) {
                $ph = array(
                    'name_metaphone' => $d_obj->getNameMetaphone($d_obj->setNameMetaphone($d_obj->getName())),
                    'id' => (int)$d_obj->getId(),
                );
                $query = 'update ' . $dlf->getTable() . ' set name_metaphone = ? where id = ?';
                $this->db->Execute($query, $ph);
            }
        }


        //Add GeoCode cronjob to database to run every morning.
        $cjf = TTnew('CronJobFactory');
        $cjf->setName('GeoCode');
        $cjf->setMinute('15');
        $cjf->setHour('2');
        $cjf->setDayOfMonth('*');
        $cjf->setMonth('*');
        $cjf->setDayOfWeek('*');
        $cjf->setCommand('GeoCode.php');
        $cjf->Save();

        return true;
    }
}
