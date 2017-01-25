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
 * @group CRPayrollDeductionTest
 */
class CRPayrollDeductionTest extends PHPUnit_Framework_TestCase
{
    public $company_id = null;

    public function setUp()
    {
        Debug::text('Running setUp(): ', __FILE__, __LINE__, __METHOD__, 10);

        require_once(Environment::getBasePath() . '/classes/payroll_deduction/PayrollDeduction.class.php');

        $this->company_id = PRIMARY_COMPANY_ID;

        TTDate::setTimeZone('Etc/GMT+8'); //Force to non-DST timezone. 'PST' isnt actually valid.

        return true;
    }

    public function tearDown()
    {
        Debug::text('Running tearDown(): ', __FILE__, __LINE__, __METHOD__, 10);
        return true;
    }

    public function testCR_2007a_BiWeekly_Single_LowIncome()
    {
        Debug::text('CR - BiWeekly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CR', null);
        $pd_obj->setDate(strtotime('01-Jan-07'));
        $pd_obj->setAnnualPayPeriods(26); //Bi-Weekly

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(1);
        //$pd_obj->setUserCurrency('CRC');

        $pd_obj->setGrossPayPeriodIncome(260000.00);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '260000.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '3993.85'); //100.73
    }

    //
    //
    //
    // 2007
    //
    //
    //

    public function mf($amount)
    {
        return Misc::MoneyFormat($amount, false);
    }
}
