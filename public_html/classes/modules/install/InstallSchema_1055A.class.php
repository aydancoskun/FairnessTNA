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
class InstallSchema_1055A extends InstallSchema_Base
{
    public function preInstall()
    {
        Debug::text('preInstall: ' . $this->getVersion(), __FILE__, __LINE__, __METHOD__, 9);

        return true;
    }

    public function postInstall()
    {
        Debug::text('postInstall: ' . $this->getVersion(), __FILE__, __LINE__, __METHOD__, 9);

        //Make sure Medicare Employer uses the same include/exclude accounts as Medicare Employee.
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
                        // @codingStandardsIgnoreStart
                        Debug::text('Failed getting PayStubEntryLink for Company ID: ' . $company_id, __FILE__, __LINE__, __METHOD__, 10);
                        //leaving  debugging in place.
                        // @codingStandardsIgnoreEnd
                        continue;
                    }

                    $include_pay_stub_accounts = false;
                    $exclude_pay_stub_accounts = false;

                    $cdlf = TTnew('CompanyDeductionListFactory');
                    $cdlf->getByCompanyIdAndName($c_obj->getID(), 'Medicare - Employee');
                    if ($cdlf->getRecordCount() == 1) {
                        $cd_obj = $cdlf->getCurrent();
                        Debug::text('Found Medicare Employee Tax / Deduction, ID: ' . $c_obj->getID(), __FILE__, __LINE__, __METHOD__, 9);
                        $include_pay_stub_accounts = $cd_obj->getIncludePayStubEntryAccount();
                        $exclude_pay_stub_accounts = $cd_obj->getExcludePayStubEntryAccount();
                    } else {
                        Debug::text('Failed to find Medicare Employee Tax / Deduction for Company: ' . $c_obj->getName(), __FILE__, __LINE__, __METHOD__, 9);
                    }
                    unset($cdlf, $cd_obj);

                    //Debug::Arr($include_pay_stub_accounts, 'Include Pay Stub Accounts: '. $c_obj->getName(), __FILE__, __LINE__, __METHOD__, 9);
                    //Debug::Arr($exclude_pay_stub_accounts, 'Exclude Pay Stub Accounts: '. $c_obj->getName(), __FILE__, __LINE__, __METHOD__, 9);

                    $cdlf = TTnew('CompanyDeductionListFactory');
                    $cdlf->getByCompanyIdAndName($c_obj->getID(), 'Medicare - Employer');
                    if ($cdlf->getRecordCount() == 1) {
                        $cd_obj = $cdlf->getCurrent();
                        Debug::text('Found Medicare Employer Tax / Deduction, ID: ' . $c_obj->getID(), __FILE__, __LINE__, __METHOD__, 9);

                        Debug::text('Medicare Employer Tax / Deduction Matches... Adjusting specific formula Percent...', __FILE__, __LINE__, __METHOD__, 9);
                        if ($include_pay_stub_accounts !== false) {
                            Debug::text('Matching Include/Exclude accounts with Medicare Employee Entry...', __FILE__, __LINE__, __METHOD__, 9);
                            //Match include accounts with employee entry.
                            $cd_obj->setIncludePayStubEntryAccount($include_pay_stub_accounts);
                            $cd_obj->setExcludePayStubEntryAccount($exclude_pay_stub_accounts);
                        } else {
                            Debug::text('NOT Matching Include/Exclude accounts with Medicare Employee Entry...', __FILE__, __LINE__, __METHOD__, 9);
                            $cd_obj->setIncludePayStubEntryAccount(array($psea_obj->getTotalGross()));
                        }

                        $cd_obj->ignore_column_list = true; //Prevents SQL errors due to new columns being added later on.
                        if ($cd_obj->isValid()) {
                            $cd_obj->Save();
                        }
                    } else {
                        Debug::text('Failed to find Medicare Employer Tax / Deduction for Company: ' . $c_obj->getName(), __FILE__, __LINE__, __METHOD__, 9);
                    }
                }
            }
        }

        return true;
    }
}
