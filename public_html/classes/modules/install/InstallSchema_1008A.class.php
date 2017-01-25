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
class InstallSchema_1008A extends InstallSchema_Base
{
    public function preInstall()
    {
        Debug::text('preInstall: ' . $this->getVersion(), __FILE__, __LINE__, __METHOD__, 9);

        return true;
    }


    public function postInstall()
    {
        Debug::text('postInstall: ' . $this->getVersion(), __FILE__, __LINE__, __METHOD__, 9);

        $clf = TTnew('CompanyListFactory');
        $clf->StartTransaction();
        $clf->getAll();
        if ($clf->getRecordCount() > 0) {
            foreach ($clf as $c_obj) {
                if ($c_obj->getStatus() == 10) {
                    $ulf = TTnew('UserListFactory');

                    $ulf->getHighestEmployeeNumberByCompanyId($c_obj->getId());
                    if ($ulf->getRecordCount() > 0) {
                        $next_available_employee_number = ($ulf->getCurrent()->getEmployeeNumber() + 1);
                    } else {
                        $next_available_employee_number = 1;
                    }

                    $ulf->getByCompanyId($c_obj->getId(), null, null, null, array('hire_date' => 'asc'));
                    if ($ulf->getRecordCount() > 0) {
                        foreach ($ulf as $u_obj) {
                            if ($u_obj->getEmployeeNumber() == '') {
                                Debug::text('Setting Employee Number to: ' . $next_available_employee_number . ' for ' . $u_obj->getUserName(), __FILE__, __LINE__, __METHOD__, 9);

                                $u_obj->setEmployeeNumber($next_available_employee_number);
                                if ($u_obj->isValid()) {
                                    $u_obj->Save();
                                    $next_available_employee_number++;
                                }
                            } else {
                                Debug::text('NOT Setting Employee Number for ' . $u_obj->getUserName() . ' already set to: ' . $u_obj->getEmployeeNumber(), __FILE__, __LINE__, __METHOD__, 9);
                            }
                        }
                    }
                }
            }
        }
        //$clf->FailTransaction();
        $clf->CommitTransaction();

        return true;
    }
}
