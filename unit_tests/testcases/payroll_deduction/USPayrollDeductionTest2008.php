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
 * @group USPayrollDeductionTest2008
 */
class USPayrollDeductionTest2008 extends PHPUnit_Framework_TestCase
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

    public function testUS_2008a_BiWeekly_Single_LowIncome_EIC()
    {
        Debug::text('US - BiWeekly - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'MO');
        $pd_obj->setDate(strtotime('01-Jan-08'));
        $pd_obj->setAnnualPayPeriods(26); //Bi-Weekly

        $pd_obj->setEICFilingStatus(10); //Single
        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(1);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(300);

        //var_dump($pd_obj->getArray());

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '300.00');
        $this->assertEquals($this->mf($pd_obj->getEIC()), '-61.20');
    }

    //
    //
    //
    // 2008
    //
    //
    //

    public function mf($amount)
    {
        return Misc::MoneyFormat($amount, false);
    }

    public function testUS_2008a_BiWeekly_Single_MedIncome_EIC()
    {
        Debug::text('US - BiWeekly - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'MO');
        $pd_obj->setDate(strtotime('01-Jan-08'));
        $pd_obj->setAnnualPayPeriods(26); //Bi-Weekly

        $pd_obj->setEICFilingStatus(10); //Single
        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(1);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(460);

        //var_dump($pd_obj->getArray());

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '460.00');
        $this->assertEquals($this->mf($pd_obj->getEIC()), '-67.31');
    }

    public function testUS_2008a_BiWeekly_Single_HighIncome_EIC()
    {
        Debug::text('US - BiWeekly - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'MO');
        $pd_obj->setDate(strtotime('01-Jan-08'));
        $pd_obj->setAnnualPayPeriods(26); //Bi-Weekly

        $pd_obj->setEICFilingStatus(10); //Single
        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(1);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(1000);

        //var_dump($pd_obj->getArray());

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1000.00');
        $this->assertEquals($this->mf($pd_obj->getEIC()), '-29.47');
    }

    public function testUS_2008a_BiWeekly_Single_HighIncome_EICB()
    {
        Debug::text('US - BiWeekly - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'MO');
        $pd_obj->setDate(strtotime('01-Jan-08'));
        $pd_obj->setAnnualPayPeriods(26); //Bi-Weekly

        $pd_obj->setEICFilingStatus(10); //Single
        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(1);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(2000);

        //var_dump($pd_obj->getArray());

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '2000.00');
        $this->assertEquals($this->mf($pd_obj->getEIC()), '0');
    }

    public function testUS_2008a_BiWeekly_Married_HighIncome_EIC()
    {
        Debug::text('US - BiWeekly - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'MO');
        $pd_obj->setDate(strtotime('01-Jan-08'));
        $pd_obj->setAnnualPayPeriods(26); //Bi-Weekly

        $pd_obj->setEICFilingStatus(20); //Single
        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(1);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(1000);

        //var_dump($pd_obj->getArray());

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1000.00');
        $this->assertEquals($this->mf($pd_obj->getEIC()), '-40.54');
    }

    public function testUS_2008a_BiWeekly_Single_LowIncome()
    {
        Debug::text('US - BiWeekly - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'MO');
        $pd_obj->setDate(strtotime('01-Jan-08'));
        $pd_obj->setAnnualPayPeriods(26); //Bi-Weekly

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(1);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(1010.00);

        //var_dump($pd_obj->getArray());

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1010.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '101.31'); //101.31
    }

    public function testUS_2008a_BiWeekly_Married_LowIncome()
    {
        Debug::text('US - BiWeekly - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'MO');
        $pd_obj->setDate(strtotime('01-Jan-08'));
        $pd_obj->setAnnualPayPeriods(26); //Bi-Weekly

        $pd_obj->setFederalFilingStatus(20); //Married
        $pd_obj->setFederalAllowance(1);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(1000.00);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1000.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '55.77'); //55.77
    }

    public function testUS_2008a_BiWeekly_Married_LowIncomeB()
    {
        Debug::text('US - BiWeekly - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'MO');
        $pd_obj->setDate(strtotime('01-Jan-08'));
        $pd_obj->setAnnualPayPeriods(26); //Bi-Weekly

        $pd_obj->setFederalFilingStatus(20); //Married
        $pd_obj->setFederalAllowance(3);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(1000.00);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1000.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '28.85'); //28.85
    }

    public function testUS_2008a_SemiMonthly_Single_LowIncome()
    {
        Debug::text('US - SemiMonthly - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'MO');
        $pd_obj->setDate(strtotime('01-Jan-08'));
        $pd_obj->setAnnualPayPeriods(24); //Semi-Monthly

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(1);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(1000.00);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1000.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '95.63'); //95.63
    }

    public function testUS_2008a_SemiMonthly_Married_LowIncome()
    {
        Debug::text('US - SemiMonthly - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'MO');
        $pd_obj->setDate(strtotime('01-Jan-08'));
        $pd_obj->setAnnualPayPeriods(24); //Semi-Monthly

        $pd_obj->setFederalFilingStatus(20); //Married
        $pd_obj->setFederalAllowance(1);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(1000.00);

        //var_dump($pd_obj->getArray());

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1000.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '52.08'); //52.08
    }

    public function testUS_2008a_SemiMonthly_Single_MedIncome()
    {
        Debug::text('US - SemiMonthly - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'MO');
        $pd_obj->setDate(strtotime('01-Jan-08'));
        $pd_obj->setAnnualPayPeriods(24); //Semi-Monthly

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(1);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(2000.00);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '2000.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '289.54'); //289.54
    }

    public function testUS_2008a_SemiMonthly_Single_HighIncome()
    {
        Debug::text('US - SemiMonthly - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'MO');
        $pd_obj->setDate(strtotime('01-Jan-08'));
        $pd_obj->setAnnualPayPeriods(24); //Semi-Monthly

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(1);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(4000.00);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '4000.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '805.51'); //805.51
    }

    public function testUS_2008a_SemiMonthly_Single_LowIncome_3Allowances()
    {
        Debug::text('US - SemiMonthly - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'MO');
        $pd_obj->setDate(strtotime('01-Jan-08'));
        $pd_obj->setAnnualPayPeriods(24); //Semi-Monthly

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(3);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(1000.00);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1000.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '51.88'); //51.88
    }

    public function testUS_2008a_SemiMonthly_Single_LowIncome_5Allowances()
    {
        Debug::text('US - SemiMonthly - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'MO');
        $pd_obj->setDate(strtotime('01-Jan-08'));
        $pd_obj->setAnnualPayPeriods(24); //Semi-Monthly

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(5);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(1000.00);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1000.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '16.04'); //16.04
    }

    public function testUS_2008a_SemiMonthly_Single_LowIncome_8AllowancesA()
    {
        Debug::text('US - SemiMonthly - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'MO');
        $pd_obj->setDate(strtotime('01-Jan-08'));
        $pd_obj->setAnnualPayPeriods(24); //Semi-Monthly

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(8);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(1000.00);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1000.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '0.00'); //0.00
    }

    public function testUS_2008a_SemiMonthly_Single_LowIncome_8AllowancesB()
    {
        Debug::text('US - SemiMonthly - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'MO');
        $pd_obj->setDate(strtotime('01-Jan-08'));
        $pd_obj->setAnnualPayPeriods(24); //Semi-Monthly

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(8);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(1300.00);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1300.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '2.29'); //2.29
    }

    //
    // CA
    //
    public function testCA_2008a_BiWeekly_Single_LowIncome()
    {
        Debug::text('US - BiWeekly - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'CA');
        $pd_obj->setDate(strtotime('01-Jan-08'));
        $pd_obj->setAnnualPayPeriods(26); //Bi-Weekly

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(1);

        $pd_obj->setStateFilingStatus(10); //Single
        $pd_obj->setStateAllowance(1);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(1000.00);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1000.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '99.81'); //99.81
        $this->assertEquals($this->mf($pd_obj->getStatePayPeriodDeductions()), '15.90');
    }

    public function testCA_2008a_BiWeekly_Married_LowIncome()
    {
        Debug::text('US - BiWeekly - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'CA');
        $pd_obj->setDate(strtotime('01-Jan-08'));
        $pd_obj->setAnnualPayPeriods(26); //Bi-Weekly

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(1);

        $pd_obj->setStateFilingStatus(30); //Married, one person working
        $pd_obj->setStateAllowance(1);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(1000.00);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1000.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '99.81'); //99.81
        $this->assertEquals($this->mf($pd_obj->getStatePayPeriodDeductions()), '8.43');
    }

    public function testCA_2008a_SemiMonthly_Married_HighIncome_8Allowances()
    {
        Debug::text('US - SemiMonthly - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'CA');
        $pd_obj->setDate(strtotime('01-Jan-08'));
        $pd_obj->setAnnualPayPeriods(24); //Semi-Monthly

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(1);

        $pd_obj->setStateFilingStatus(30); //Married, one person working
        $pd_obj->setStateAllowance(8);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(4000.00);

        //var_dump($pd_obj->getArray());

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '4000.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '805.51'); //805.51
        $this->assertEquals($this->mf($pd_obj->getStatePayPeriodDeductions()), '130.89');
    }

    //
    // KY
    //
    public function testKY_2008a_BiWeekly_LowIncome()
    {
        Debug::text('US - BiWeekly - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'KY');
        $pd_obj->setDate(strtotime('01-Jan-08'));
        $pd_obj->setAnnualPayPeriods(26); //Bi-Weekly

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(1);

        $pd_obj->setStateAllowance(0);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(346.00);

        //var_dump($pd_obj->getArray());

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '346.00');
        $this->assertEquals($this->mf($pd_obj->getStatePayPeriodDeductions()), '8.65');
    }

    public function testKY_2008a_BiWeekly_LowIncomeB()
    {
        Debug::text('US - BiWeekly - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'KY');
        $pd_obj->setDate(strtotime('01-Jan-08'));
        $pd_obj->setAnnualPayPeriods(26); //Bi-Weekly

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(1);

        $pd_obj->setStateAllowance(0);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(1000.00);

        //var_dump($pd_obj->getArray());

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1000.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '99.81'); //99.81
        $this->assertEquals($this->mf($pd_obj->getStatePayPeriodDeductions()), '46.24');
    }

    public function testKY_2008a_SemiMonthly_HighIncome()
    {
        Debug::text('US - BiWeekly - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'KY');
        $pd_obj->setDate(strtotime('01-Jan-08'));
        $pd_obj->setAnnualPayPeriods(24);

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(1);

        $pd_obj->setStateAllowance(1);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(4000.00);

        //var_dump($pd_obj->getArray());

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '4000.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '805.51'); //805.51
        $this->assertEquals($this->mf($pd_obj->getStatePayPeriodDeductions()), '220.00');
    }

    //
    // MN
    //
    public function testMN_2008a_BiWeekly_Single_LowIncome()
    {
        Debug::text('US - BiWeekly - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'MN');
        $pd_obj->setDate(strtotime('01-Jan-08'));
        $pd_obj->setAnnualPayPeriods(26); //Bi-Weekly

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(1);

        $pd_obj->setStateFilingStatus(10); //Single
        $pd_obj->setStateAllowance(0);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(1000.00);

        //var_dump($pd_obj->getArray());

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1000.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '99.81'); //99.81
        $this->assertEquals($this->mf($pd_obj->getStatePayPeriodDeductions()), '50.96');
    }

    public function testMN_2008a_BiWeekly_Married_LowIncome()
    {
        Debug::text('US - BiWeekly - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'MN');
        $pd_obj->setDate(strtotime('01-Jan-08'));
        $pd_obj->setAnnualPayPeriods(26); //Bi-Weekly

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(1);

        $pd_obj->setStateFilingStatus(20); //Married
        $pd_obj->setStateAllowance(1);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(1000.00);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1000.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '99.81'); //99.81
        $this->assertEquals($this->mf($pd_obj->getStatePayPeriodDeductions()), '31.07');
    }

    public function testMN_2008a_SemiMonthly_Married_HighIncome_8Allowances()
    {
        Debug::text('US - SemiMonthly - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'MN');
        $pd_obj->setDate(strtotime('01-Jan-08'));
        $pd_obj->setAnnualPayPeriods(24); //Semi-Monthly

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(1);

        $pd_obj->setStateFilingStatus(20); //Married
        $pd_obj->setStateAllowance(8);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(4000.00);

        //var_dump($pd_obj->getArray());

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '4000.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '805.51'); //805.51
        $this->assertEquals($this->mf($pd_obj->getStatePayPeriodDeductions()), '155.45');
    }

    //
    // NE
    //
    /*
        function testNE_2008a_BiWeekly_Single_LowIncome() {
            Debug::text('US - BiWeekly - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__,10);
    
            $pd_obj = new PayrollDeduction('US','NE');
            $pd_obj->setDate(strtotime('01-Jan-08'));
            $pd_obj->setAnnualPayPeriods( 26 ); //Bi-Weekly
    
            $pd_obj->setFederalFilingStatus( 10 ); //Single
            $pd_obj->setFederalAllowance( 1 );
    
            $pd_obj->setStateFilingStatus( 10 ); //Single
            $pd_obj->setStateAllowance( 0 );
    
            $pd_obj->setFederalTaxExempt( FALSE );
            $pd_obj->setProvincialTaxExempt( FALSE );
    
            $pd_obj->setGrossPayPeriodIncome( 1000.00 );
    
            //var_dump($pd_obj->getArray());
    
            $this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '1000.00' );
            $this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '99.81' ); //99.81
            $this->assertEquals( $this->mf( $pd_obj->getStatePayPeriodDeductions() ), '37.70' );
        }
    
        function testNE_2008a_BiWeekly_Married_LowIncome() {
            Debug::text('US - BiWeekly - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__,10);
    
            $pd_obj = new PayrollDeduction('US','NE');
            $pd_obj->setDate(strtotime('01-Jan-08'));
            $pd_obj->setAnnualPayPeriods( 26 ); //Bi-Weekly
    
            $pd_obj->setFederalFilingStatus( 10 ); //Single
            $pd_obj->setFederalAllowance( 1 );
    
            $pd_obj->setStateFilingStatus( 20 ); //Married
            $pd_obj->setStateAllowance( 1 );
    
            $pd_obj->setFederalTaxExempt( FALSE );
            $pd_obj->setProvincialTaxExempt( FALSE );
    
            $pd_obj->setGrossPayPeriodIncome( 1000.00 );
    
            $this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '1000.00' );
            $this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '99.81' ); //99.81
            $this->assertEquals( $this->mf( $pd_obj->getStatePayPeriodDeductions() ), '21.76' );
        }
    
        function testNE_2008a_SemiMonthly_Married_HighIncome_8Allowances() {
            Debug::text('US - SemiMonthly - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__,10);
    
            $pd_obj = new PayrollDeduction('US','NE');
            $pd_obj->setDate(strtotime('01-Jan-08'));
            $pd_obj->setAnnualPayPeriods( 24 ); //Semi-Monthly
    
            $pd_obj->setFederalFilingStatus( 10 ); //Single
            $pd_obj->setFederalAllowance( 1 );
    
            $pd_obj->setStateFilingStatus( 20 ); //Married
            $pd_obj->setStateAllowance( 8 );
    
            $pd_obj->setFederalTaxExempt( FALSE );
            $pd_obj->setProvincialTaxExempt( FALSE );
    
            $pd_obj->setGrossPayPeriodIncome( 4000.00 );
    
            //var_dump($pd_obj->getArray());
    
            $this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '4000.00' );
            $this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '805.51' ); //805.51
            $this->assertEquals( $this->mf( $pd_obj->getStatePayPeriodDeductions() ), '176.54' );
        }
    */
    //
    // NM
    //
    public function testNM_2008a_BiWeekly_Single_LowIncome()
    {
        Debug::text('US - BiWeekly - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'NM');
        $pd_obj->setDate(strtotime('01-Jan-08'));
        $pd_obj->setAnnualPayPeriods(26); //Bi-Weekly

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(1);

        $pd_obj->setStateFilingStatus(10); //Single
        $pd_obj->setStateAllowance(0);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(1000.00);

        //var_dump($pd_obj->getArray());

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1000.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '99.81'); //99.81
        $this->assertEquals($this->mf($pd_obj->getStatePayPeriodDeductions()), '34.67');
    }

    public function testNM_2008a_BiWeekly_Married_LowIncome()
    {
        Debug::text('US - BiWeekly - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'NM');
        $pd_obj->setDate(strtotime('01-Jan-08'));
        $pd_obj->setAnnualPayPeriods(26); //Bi-Weekly

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(1);

        $pd_obj->setStateFilingStatus(20); //Married
        $pd_obj->setStateAllowance(1);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(1000.00);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1000.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '99.81'); //99.81
        $this->assertEquals($this->mf($pd_obj->getStatePayPeriodDeductions()), '14.22'); //14.22
    }

    public function testNM_2008a_SemiMonthly_Married_HighIncome_8Allowances()
    {
        Debug::text('US - SemiMonthly - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'NM');
        $pd_obj->setDate(strtotime('01-Jan-08'));
        $pd_obj->setAnnualPayPeriods(24); //Semi-Monthly

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(1);

        $pd_obj->setStateFilingStatus(20); //Married
        $pd_obj->setStateAllowance(8);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(4000.00);

        //var_dump($pd_obj->getArray());

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '4000.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '805.51'); //805.51
        $this->assertEquals($this->mf($pd_obj->getStatePayPeriodDeductions()), '107.85');
    }

    //
    // ND
    //
    public function testND_2008a_BiWeekly_Single_LowIncome()
    {
        Debug::text('US - BiWeekly - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'ND');
        $pd_obj->setDate(strtotime('01-Jan-08'));
        $pd_obj->setAnnualPayPeriods(26); //Bi-Weekly

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(1);

        $pd_obj->setStateFilingStatus(10); //Single
        $pd_obj->setStateAllowance(0);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(1000.00);

        //var_dump($pd_obj->getArray());

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1000.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '99.81'); //99.81
        $this->assertEquals($this->mf($pd_obj->getStatePayPeriodDeductions()), '18.01');
    }

    public function testND_2008a_BiWeekly_Married_LowIncome()
    {
        Debug::text('US - BiWeekly - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'ND');
        $pd_obj->setDate(strtotime('01-Jan-08'));
        $pd_obj->setAnnualPayPeriods(26); //Bi-Weekly

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(1);

        $pd_obj->setStateFilingStatus(20); //Married
        $pd_obj->setStateAllowance(1);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(1000.00);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1000.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '99.81'); //99.81
        $this->assertEquals($this->mf($pd_obj->getStatePayPeriodDeductions()), '10.90');
    }

    public function testND_2008a_SemiMonthly_Married_HighIncome_8Allowances()
    {
        Debug::text('US - SemiMonthly - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'ND');
        $pd_obj->setDate(strtotime('01-Jan-08'));
        $pd_obj->setAnnualPayPeriods(24); //Semi-Monthly

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(1);

        $pd_obj->setStateFilingStatus(20); //Married
        $pd_obj->setStateAllowance(8);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(4000.00);

        //var_dump($pd_obj->getArray());

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '4000.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '805.51'); //805.51
        $this->assertEquals($this->mf($pd_obj->getStatePayPeriodDeductions()), '56.48');
    }

    //
    // OH
    //
    public function testOH_2008a_BiWeekly_LowIncome()
    {
        Debug::text('US - BiWeekly - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'OH');
        $pd_obj->setDate(strtotime('01-Jan-08'));
        $pd_obj->setAnnualPayPeriods(26); //Bi-Weekly

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(1);

        $pd_obj->setStateAllowance(1);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(1000.00);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1000.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '99.81');
        $this->assertEquals($this->mf($pd_obj->getStatePayPeriodDeductions()), '23.80');
    }

    public function testOH_2008a_BiWeekly_LowIncomeB()
    {
        Debug::text('US - BiWeekly - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'OH');
        $pd_obj->setDate(strtotime('01-Jan-08'));
        $pd_obj->setAnnualPayPeriods(26); //Bi-Weekly

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(1);

        $pd_obj->setStateAllowance(3);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(1000.00);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1000.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '99.81');
        $this->assertEquals($this->mf($pd_obj->getStatePayPeriodDeductions()), '21.78');
    }

    public function testOH_2008a_SemiMonthly_HighIncome()
    {
        Debug::text('US - BiWeekly - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'OH');
        $pd_obj->setDate(strtotime('01-Jan-08'));
        $pd_obj->setAnnualPayPeriods(24);

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(1);

        $pd_obj->setStateAllowance(3);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(4000.00);

        //var_dump($pd_obj->getArray());

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '4000.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '805.51');
        $this->assertEquals($this->mf($pd_obj->getStatePayPeriodDeductions()), '160.24');
    }


    //
    // RI
    //
    public function testRI_2008a_BiWeekly_Single_LowIncome()
    {
        Debug::text('US - BiWeekly - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'RI');
        $pd_obj->setDate(strtotime('01-Jan-08'));
        $pd_obj->setAnnualPayPeriods(26); //Bi-Weekly

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(1);

        $pd_obj->setStateFilingStatus(10); //Single
        $pd_obj->setStateAllowance(0);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(1000.00);

        //var_dump($pd_obj->getArray());

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1000.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '99.81'); //99.81
        $this->assertEquals($this->mf($pd_obj->getStatePayPeriodDeductions()), '33.68'); //33.68
    }

    public function testRI_2008a_BiWeekly_Single_LowIncomeB()
    {
        Debug::text('US - BiWeekly - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'RI');
        $pd_obj->setDate(strtotime('01-Jan-08'));
        $pd_obj->setAnnualPayPeriods(52); //Weekly

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(1);

        $pd_obj->setStateFilingStatus(10); //Single
        $pd_obj->setStateAllowance(2);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(900.00);

        //var_dump($pd_obj->getArray());

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '900.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '127.87'); //127.87
        $this->assertEquals($this->mf($pd_obj->getStatePayPeriodDeductions()), '30.10');
    }

    public function testRI_2008a_BiWeekly_Married_LowIncome()
    {
        Debug::text('US - BiWeekly - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'RI');
        $pd_obj->setDate(strtotime('01-Jan-08'));
        $pd_obj->setAnnualPayPeriods(26); //Bi-Weekly

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(1);

        $pd_obj->setStateFilingStatus(20); //Married
        $pd_obj->setStateAllowance(1);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(1000.00);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1000.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '99.81'); //99.81
        $this->assertEquals($this->mf($pd_obj->getStatePayPeriodDeductions()), '23.15');
    }

    public function testRI_2008a_SemiMonthly_Married_HighIncome_8Allowances()
    {
        Debug::text('US - SemiMonthly - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'RI');
        $pd_obj->setDate(strtotime('01-Jan-08'));
        $pd_obj->setAnnualPayPeriods(24); //Semi-Monthly

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(1);

        $pd_obj->setStateFilingStatus(20); //Married
        $pd_obj->setStateAllowance(8);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(4000.00);

        //var_dump($pd_obj->getArray());

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '4000.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '805.51'); //805.51
        $this->assertEquals($this->mf($pd_obj->getStatePayPeriodDeductions()), '107.01');
    }

    //
    // UT
    //
    public function testUT_2008a_BiWeekly_Single_LowIncome()
    {
        Debug::text('US - BiWeekly - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'UT');
        $pd_obj->setDate(strtotime('01-Jan-08'));
        $pd_obj->setAnnualPayPeriods(26); //Bi-Weekly

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(1);

        $pd_obj->setStateFilingStatus(10); //Single
        $pd_obj->setStateAllowance(0);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(1000.00);

        //var_dump($pd_obj->getArray());

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1000.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '99.81'); //99.81
        $this->assertEquals($this->mf($pd_obj->getStatePayPeriodDeductions()), '47.38'); //50.00
    }

    public function testUT_2008b_BiWeekly_Single_LowIncome()
    {
        Debug::text('US - BiWeekly - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'UT');
        $pd_obj->setDate(strtotime('01-Jan-08'));
        $pd_obj->setAnnualPayPeriods(26); //Bi-Weekly

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(1);

        $pd_obj->setStateFilingStatus(10); //Single
        $pd_obj->setStateAllowance(5);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(250.00);

        //var_dump($pd_obj->getArray());

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '250.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '1.35');
        $this->assertEquals($this->mf($pd_obj->getStatePayPeriodDeductions()), '0.00');
    }

    public function testUT_2008b_BiWeekly_Single_HighIncome()
    {
        Debug::text('US - BiWeekly - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'UT');
        $pd_obj->setDate(strtotime('01-Jan-08'));
        $pd_obj->setAnnualPayPeriods(26); //Bi-Weekly

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(1);

        $pd_obj->setStateFilingStatus(10); //Single
        $pd_obj->setStateAllowance(5);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(4000.00);

        //var_dump($pd_obj->getArray());

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '4000.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '829.70');
        $this->assertEquals($this->mf($pd_obj->getStatePayPeriodDeductions()), '200.00');
    }

    public function testUT_2008a_BiWeekly_Married_LowIncome()
    {
        Debug::text('US - BiWeekly - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'UT');
        $pd_obj->setDate(strtotime('01-Jan-08'));
        $pd_obj->setAnnualPayPeriods(26); //Bi-Weekly

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(1);

        $pd_obj->setStateFilingStatus(20); //Married
        $pd_obj->setStateAllowance(1);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(1000.00);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1000.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '99.81'); //99.81
        $this->assertEquals($this->mf($pd_obj->getStatePayPeriodDeductions()), '34.77');
    }

    public function testUT_2008a_SemiMonthly_Married_HighIncome_8Allowances()
    {
        Debug::text('US - SemiMonthly - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'UT');
        $pd_obj->setDate(strtotime('01-Jan-08'));
        $pd_obj->setAnnualPayPeriods(24); //Semi-Monthly

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(1);

        $pd_obj->setStateFilingStatus(20); //Married
        $pd_obj->setStateAllowance(8);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(4000.00);

        //var_dump($pd_obj->getArray());

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '4000.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '805.51'); //805.51
        $this->assertEquals($this->mf($pd_obj->getStatePayPeriodDeductions()), '184.96');
    }


    //
    // VT
    //
    public function testVT_2008a_BiWeekly_Single_LowIncome()
    {
        Debug::text('US - BiWeekly - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'VT');
        $pd_obj->setDate(strtotime('01-Jan-08'));
        $pd_obj->setAnnualPayPeriods(26); //Bi-Weekly

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(1);

        $pd_obj->setStateFilingStatus(10); //Single
        $pd_obj->setStateAllowance(0);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(1000.00);

        //var_dump($pd_obj->getArray());

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1000.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '99.81'); //99.81
        $this->assertEquals($this->mf($pd_obj->getStatePayPeriodDeductions()), '32.33'); //32.33
    }

    public function testVT_2008a_BiWeekly_Married_LowIncome()
    {
        Debug::text('US - BiWeekly - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'VT');
        $pd_obj->setDate(strtotime('01-Jan-08'));
        $pd_obj->setAnnualPayPeriods(26); //Bi-Weekly

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(1);

        $pd_obj->setStateFilingStatus(20); //Married
        $pd_obj->setStateAllowance(1);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(1000.00);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1000.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '99.81'); //99.81
        $this->assertEquals($this->mf($pd_obj->getStatePayPeriodDeductions()), '20.08');
    }

    public function testVT_2008a_SemiMonthly_Married_HighIncome_8Allowances()
    {
        Debug::text('US - SemiMonthly - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'VT');
        $pd_obj->setDate(strtotime('01-Jan-08'));
        $pd_obj->setAnnualPayPeriods(24); //Semi-Monthly

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(1);

        $pd_obj->setStateFilingStatus(20); //Married
        $pd_obj->setStateAllowance(8);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(4000.00);

        //var_dump($pd_obj->getArray());

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '4000.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '805.51'); //805.51
        $this->assertEquals($this->mf($pd_obj->getStatePayPeriodDeductions()), '101.70');
    }
}
