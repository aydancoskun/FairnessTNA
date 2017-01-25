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
 * @group CAPayrollDeductionTest2008
 */
class CAPayrollDeductionTest2008 extends PHPUnit_Framework_TestCase
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

    public function testBC_2008b_BiWeekly_Claim1_MedIncome()
    {
        Debug::text('BC - BiWeekly - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'BC');
        $pd_obj->setDate(strtotime('01-Jul-08'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(26);

        $pd_obj->setFederalTotalClaimAmount(9600);
        $pd_obj->setProvincialTotalClaimAmount(9189);
        $pd_obj->setWCBRate(0.18);

        $pd_obj->setEIExempt(false);
        $pd_obj->setCPPExempt(false);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setYearToDateCPPContribution(0);
        $pd_obj->setYearToDateEIContribution(0);

        $pd_obj->setGrossPayPeriodIncome(2774.00);


        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '2774.00');
        Debug::text('Prov Ded: ' . $pd_obj->getProvincialPayPeriodDeductions(), __FILE__, __LINE__, __METHOD__, 10);
        $this->assertEquals($this->mf($pd_obj->getProvincialPayPeriodDeductions()), '159.15');
    }

    //
    // July 2008
    //
    //
    // BC - Provincial Taxes
    //

    public function mf($amount)
    {
        return Misc::MoneyFormat($amount, false);
    }

    public function testBC_2008b_BiWeekly_Claim5_MedIncome()
    {
        Debug::text('BC - BiWeekly - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'BC');
        $pd_obj->setDate(strtotime('01-Jul-08'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(26);

        $pd_obj->setFederalTotalClaimAmount(9600);
        $pd_obj->setProvincialTotalClaimAmount(16427.01);
        $pd_obj->setWCBRate(0.18);

        $pd_obj->setEIExempt(false);
        $pd_obj->setCPPExempt(false);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setYearToDateCPPContribution(0);
        $pd_obj->setYearToDateEIContribution(0);

        $pd_obj->setGrossPayPeriodIncome(2774.00);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '2774.00');
        Debug::text('Prov Ded: ' . $pd_obj->getProvincialPayPeriodDeductions(), __FILE__, __LINE__, __METHOD__, 10);
        $this->assertEquals($this->mf($pd_obj->getProvincialPayPeriodDeductions()), '144.87');
    }

    //
    // January 2008
    //
    public function testCA_2008a_BasicClaimAmount()
    {
        Debug::text('CA - BiWeekly - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'BC');
        $pd_obj->setDate(strtotime('01-Jan-08'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(26);

        $pd_obj->setFederalTotalClaimAmount(8128); //Amount from 2005, Should use amount from 2007 automatically.
        $pd_obj->setProvincialTotalClaimAmount(8858);
        $pd_obj->setWCBRate(0.18);

        $pd_obj->setEIExempt(false);
        $pd_obj->setCPPExempt(false);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setYearToDateCPPContribution(0);
        $pd_obj->setYearToDateEIContribution(0);

        $pd_obj->setGrossPayPeriodIncome(2770.00);

        //var_dump($pd_obj->getArray());

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '2770.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '430.21');
        $this->assertEquals($this->mf($pd_obj->getProvincialPayPeriodDeductions()), '165.26');
    }

    public function testCA_2008a_BasicClaimAmountB()
    {
        Debug::text('CA - BiWeekly - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'BC');
        $pd_obj->setDate(strtotime('01-Jan-08'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(26);

        $pd_obj->setFederalTotalClaimAmount(9600);
        $pd_obj->setProvincialTotalClaimAmount(9189);
        $pd_obj->setWCBRate(0.18);

        $pd_obj->setEIExempt(false);
        $pd_obj->setCPPExempt(false);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setYearToDateCPPContribution(0);
        $pd_obj->setYearToDateEIContribution(0);

        $pd_obj->setGrossPayPeriodIncome(2770.00);

        //var_dump($pd_obj->getArray());

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '2770.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '430.21');
        $this->assertEquals($this->mf($pd_obj->getProvincialPayPeriodDeductions()), '165.26');
    }

    public function testCA_2008a_BiWeekly_Claim1_LowIncome()
    {
        Debug::text('CA - BiWeekly - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'BC');
        $pd_obj->setDate(strtotime('01-Jan-08'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(26);

        $pd_obj->setFederalTotalClaimAmount(9600);
        $pd_obj->setProvincialTotalClaimAmount(0);
        $pd_obj->setWCBRate(0.18);

        $pd_obj->setEIExempt(false);
        $pd_obj->setCPPExempt(false);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setYearToDateCPPContribution(0);
        $pd_obj->setYearToDateEIContribution(0);

        $pd_obj->setGrossPayPeriodIncome(589.00);

        //var_dump($pd_obj->getArray());

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '589.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '22.18');
    }

    public function testCA_2008a_BiWeekly_Claim1_MedIncome()
    {
        Debug::text('CA - BiWeekly - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'BC');
        $pd_obj->setDate(strtotime('01-Jan-08'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(26);

        $pd_obj->setFederalTotalClaimAmount(9600);
        $pd_obj->setProvincialTotalClaimAmount(0);
        $pd_obj->setWCBRate(0.18);

        $pd_obj->setEIExempt(false);
        $pd_obj->setCPPExempt(false);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setYearToDateCPPContribution(0);
        $pd_obj->setYearToDateEIContribution(0);

        $pd_obj->setGrossPayPeriodIncome(2407.00);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '2407.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '350.35');
    }

    public function testCA_2008a_BiWeekly_Claim1_HighIncome()
    {
        Debug::text('CA - BiWeekly - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'BC');
        $pd_obj->setDate(strtotime('01-Jan-08'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(26);

        $pd_obj->setFederalTotalClaimAmount(9600);
        $pd_obj->setProvincialTotalClaimAmount(0);
        $pd_obj->setWCBRate(0.18);

        $pd_obj->setEIExempt(false);
        $pd_obj->setCPPExempt(false);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setYearToDateCPPContribution(0);
        $pd_obj->setYearToDateEIContribution(0);

        $pd_obj->setGrossPayPeriodIncome(7199.00);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '7199.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '1649.83');
    }

    public function testCA_2008a_BiWeekly_Claim5_LowIncome()
    {
        Debug::text('CA - BiWeekly - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'BC');
        $pd_obj->setDate(strtotime('01-Jan-08'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(26);

        $pd_obj->setFederalTotalClaimAmount(16334.01); //Claim Code5 midpoint
        $pd_obj->setProvincialTotalClaimAmount(0);
        $pd_obj->setWCBRate(0.18);

        $pd_obj->setEIExempt(false);
        $pd_obj->setCPPExempt(false);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setYearToDateCPPContribution(0);
        $pd_obj->setYearToDateEIContribution(0);

        $pd_obj->setGrossPayPeriodIncome(815.00);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '815.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '14.97');
    }

    public function testCA_2008a_BiWeekly_Claim5_HighIncome()
    {
        Debug::text('CA - BiWeekly - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'BC');
        $pd_obj->setDate(strtotime('01-Jan-08'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(26);

        $pd_obj->setFederalTotalClaimAmount(16334.01); //Claim Code5
        $pd_obj->setProvincialTotalClaimAmount(0);
        $pd_obj->setWCBRate(0.18);

        $pd_obj->setEIExempt(false);
        $pd_obj->setCPPExempt(false);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setYearToDateCPPContribution(0);
        $pd_obj->setYearToDateEIContribution(0);

        $pd_obj->setGrossPayPeriodIncome(7199.00);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '7199.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '1610.98');
    }

    public function testCA_2008a_SemiMonthly_Claim1_LowIncome()
    {
        Debug::text('CA - SemiMonthly - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'BC');
        $pd_obj->setDate(strtotime('01-Jan-08'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(24);

        $pd_obj->setFederalTotalClaimAmount(9600);
        $pd_obj->setProvincialTotalClaimAmount(0);
        $pd_obj->setWCBRate(0.18);

        $pd_obj->setEIExempt(false);
        $pd_obj->setCPPExempt(false);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setYearToDateCPPContribution(0);
        $pd_obj->setYearToDateEIContribution(0);

        $pd_obj->setGrossPayPeriodIncome(615.00);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '615.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '20.80'); //One penny off
    }

    public function testCA_2008a_SemiMonthly_Claim1_MedIncome()
    {
        Debug::text('CA - SemiMonthly - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'BC');
        $pd_obj->setDate(strtotime('01-Jan-08'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(24);

        $pd_obj->setFederalTotalClaimAmount(9600);
        $pd_obj->setProvincialTotalClaimAmount(0);
        $pd_obj->setWCBRate(0.18);

        $pd_obj->setEIExempt(false);
        $pd_obj->setCPPExempt(false);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setYearToDateCPPContribution(0);
        $pd_obj->setYearToDateEIContribution(0);

        $pd_obj->setGrossPayPeriodIncome(2720.00);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '2720.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '404.28'); //One penny off
    }

    public function testCA_2008a_SemiMonthly_Claim1_HighIncome()
    {
        Debug::text('CA - SemiMonthly - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'BC');
        $pd_obj->setDate(strtotime('01-Jan-08'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(24);

        $pd_obj->setFederalTotalClaimAmount(9600);
        $pd_obj->setProvincialTotalClaimAmount(0);
        $pd_obj->setWCBRate(0.18);

        $pd_obj->setEIExempt(false);
        $pd_obj->setCPPExempt(false);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setYearToDateCPPContribution(0);
        $pd_obj->setYearToDateEIContribution(0);

        $pd_obj->setGrossPayPeriodIncome(7781.00);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '7781.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '1782.12'); //One penny off
    }

    //
    // CPP/ EI
    //
    public function testCA_2008a_BiWeekly_CPP_LowIncome()
    {
        Debug::text('CA - BiWeekly - CPP - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'BC');
        $pd_obj->setDate(strtotime('01-Jan-08'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(26);

        $pd_obj->setFederalTotalClaimAmount(9600);
        $pd_obj->setProvincialTotalClaimAmount(0);
        $pd_obj->setWCBRate(0.18);

        $pd_obj->setEIExempt(false);
        $pd_obj->setCPPExempt(false);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setYearToDateCPPContribution(0);
        $pd_obj->setYearToDateEIContribution(0);

        $pd_obj->setGrossPayPeriodIncome(585.32);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '585.32');
        $this->assertEquals($this->mf($pd_obj->getEmployeeCPP()), '22.31');
        $this->assertEquals($this->mf($pd_obj->getEmployerCPP()), '22.31');
    }

    public function testCA_2008a_SemiMonthly_CPP_LowIncome()
    {
        Debug::text('CA - BiWeekly - CPP - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'BC');
        $pd_obj->setDate(strtotime('01-Jan-08'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(24);

        $pd_obj->setFederalTotalClaimAmount(9600);
        $pd_obj->setProvincialTotalClaimAmount(0);
        $pd_obj->setWCBRate(0.18);

        $pd_obj->setEIExempt(false);
        $pd_obj->setCPPExempt(false);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setYearToDateCPPContribution(0);
        $pd_obj->setYearToDateEIContribution(0);

        $pd_obj->setGrossPayPeriodIncome(585.23);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '585.23');
        $this->assertEquals($this->mf($pd_obj->getEmployeeCPP()), '21.75');
        $this->assertEquals($this->mf($pd_obj->getEmployerCPP()), '21.75');
    }

    public function testCA_2008a_EI_LowIncome()
    {
        Debug::text('CA - EI - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'BC');
        $pd_obj->setDate(strtotime('01-Jan-08'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(26);

        $pd_obj->setFederalTotalClaimAmount(9600);
        $pd_obj->setProvincialTotalClaimAmount(0);
        $pd_obj->setWCBRate(0.18);

        $pd_obj->setEIExempt(false);
        $pd_obj->setCPPExempt(false);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setYearToDateCPPContribution(0);
        $pd_obj->setYearToDateEIContribution(0);

        $pd_obj->setGrossPayPeriodIncome(587.76);

        //var_dump($pd_obj->getArray());

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '587.76');
        $this->assertEquals($this->mf($pd_obj->getEmployeeEI()), '10.17');
        $this->assertEquals($this->mf($pd_obj->getEmployerEI()), '14.24');
    }

    //
    // BC - Provincial Taxes
    //
    public function testBC_2008a_BiWeekly_Claim1_MedIncome()
    {
        Debug::text('BC - BiWeekly - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'BC');
        $pd_obj->setDate(strtotime('01-Jan-08'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(26);

        $pd_obj->setFederalTotalClaimAmount(9600);
        $pd_obj->setProvincialTotalClaimAmount(9189);
        $pd_obj->setWCBRate(0.18);

        $pd_obj->setEIExempt(false);
        $pd_obj->setCPPExempt(false);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setYearToDateCPPContribution(0);
        $pd_obj->setYearToDateEIContribution(0);

        $pd_obj->setGrossPayPeriodIncome(2774.00);


        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '2774.00');
        Debug::text('Prov Ded: ' . $pd_obj->getProvincialPayPeriodDeductions(), __FILE__, __LINE__, __METHOD__, 10);
        $this->assertEquals($this->mf($pd_obj->getProvincialPayPeriodDeductions()), '165.68');
    }

    public function testBC_2008a_BiWeekly_Claim5_MedIncome()
    {
        Debug::text('BC - BiWeekly - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'BC');
        $pd_obj->setDate(strtotime('01-Jan-08'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(26);

        $pd_obj->setFederalTotalClaimAmount(9600);
        $pd_obj->setProvincialTotalClaimAmount(16427.01);
        $pd_obj->setWCBRate(0.18);

        $pd_obj->setEIExempt(false);
        $pd_obj->setCPPExempt(false);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setYearToDateCPPContribution(0);
        $pd_obj->setYearToDateEIContribution(0);

        $pd_obj->setGrossPayPeriodIncome(2774.00);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '2774.00');
        Debug::text('Prov Ded: ' . $pd_obj->getProvincialPayPeriodDeductions(), __FILE__, __LINE__, __METHOD__, 10);
        $this->assertEquals($this->mf($pd_obj->getProvincialPayPeriodDeductions()), '150.79');
    }

    //
    // AB - Provincial Taxes
    //
    public function testAB_2008a_BiWeekly_Claim0_LowIncome()
    {
        Debug::text('AB - BiWeekly - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'AB');
        $pd_obj->setDate(strtotime('01-Jan-08'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(26);

        $pd_obj->setFederalTotalClaimAmount(9600);
        $pd_obj->setProvincialTotalClaimAmount(0);
        $pd_obj->setWCBRate(0.18);

        $pd_obj->setEIExempt(false);
        $pd_obj->setCPPExempt(false);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setYearToDateCPPContribution(0);
        $pd_obj->setYearToDateEIContribution(0);

        $pd_obj->setGrossPayPeriodIncome(1422.00);

        Debug::text('Fed Ded: ' . $pd_obj->getFederalPayPeriodDeductions(), __FILE__, __LINE__, __METHOD__, 10);
        Debug::text('Prov Ded: ' . $pd_obj->getProvincialPayPeriodDeductions(), __FILE__, __LINE__, __METHOD__, 10);
        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1422.00');
        $this->assertEquals($this->mf($pd_obj->getProvincialPayPeriodDeductions()), '133.37'); //133.37
    }

    public function testAB_2008a_BiWeekly_Claim1_LowIncome()
    {
        Debug::text('AB - BiWeekly - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'AB');
        $pd_obj->setDate(strtotime('01-Jan-08'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(26);

        $pd_obj->setFederalTotalClaimAmount(9600);
        $pd_obj->setProvincialTotalClaimAmount(16161);
        $pd_obj->setWCBRate(0.18);

        $pd_obj->setEIExempt(false);
        $pd_obj->setCPPExempt(false);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setYearToDateCPPContribution(0);
        $pd_obj->setYearToDateEIContribution(0);

        $pd_obj->setGrossPayPeriodIncome(1422.00);

        Debug::text('Fed Ded: ' . $pd_obj->getFederalPayPeriodDeductions(), __FILE__, __LINE__, __METHOD__, 10);
        Debug::text('Prov Ded: ' . $pd_obj->getProvincialPayPeriodDeductions(), __FILE__, __LINE__, __METHOD__, 10);
        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1422.00');
        $this->assertEquals($this->mf($pd_obj->getProvincialPayPeriodDeductions()), '71.21'); //Should be 71.21
    }

    public function testAB_2008a_BiWeekly_Claim5_LowIncome()
    {
        Debug::text('AB - BiWeekly - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'AB');
        $pd_obj->setDate(strtotime('01-Jan-08'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(26);

        $pd_obj->setFederalTotalClaimAmount(9600);
        $pd_obj->setProvincialTotalClaimAmount(24435);
        $pd_obj->setWCBRate(0.18);

        $pd_obj->setEIExempt(false);
        $pd_obj->setCPPExempt(false);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setYearToDateCPPContribution(0);
        $pd_obj->setYearToDateEIContribution(0);

        $pd_obj->setGrossPayPeriodIncome(1422.00);

        Debug::text('Prov Ded: ' . $pd_obj->getProvincialPayPeriodDeductions(), __FILE__, __LINE__, __METHOD__, 10);
        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1422.00');
        $this->assertEquals($this->mf($pd_obj->getProvincialPayPeriodDeductions()), '39.39'); //Should be 39.39
    }

    //
    // SK - Provincial Taxes
    //
    public function testSK_2008a_BiWeekly_Claim1_MedIncome()
    {
        Debug::text('SK - BiWeekly - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'SK');
        $pd_obj->setDate(strtotime('01-Jan-08'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(26);

        $pd_obj->setFederalTotalClaimAmount(9600);
        $pd_obj->setProvincialTotalClaimAmount(8945.00);
        $pd_obj->setWCBRate(0.18);

        $pd_obj->setEIExempt(false);
        $pd_obj->setCPPExempt(false);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setYearToDateCPPContribution(0);
        $pd_obj->setYearToDateEIContribution(0);

        $pd_obj->setGrossPayPeriodIncome(2840.00);


        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '2840.00');
        Debug::text('Prov Ded: ' . $pd_obj->getProvincialPayPeriodDeductions(), __FILE__, __LINE__, __METHOD__, 10);
        $this->assertEquals($this->mf($pd_obj->getProvincialPayPeriodDeductions()), '289.56');
    }

    public function testSK_2008a_SemiMonthly_Claim1_MedIncome()
    {
        Debug::text('SK - SemiMonthly - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'SK');
        $pd_obj->setDate(strtotime('01-Jan-08'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(24);

        $pd_obj->setFederalTotalClaimAmount(9600);
        $pd_obj->setProvincialTotalClaimAmount(8945.00);
        $pd_obj->setWCBRate(0.18);

        $pd_obj->setEIExempt(false);
        $pd_obj->setCPPExempt(false);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setYearToDateCPPContribution(0);
        $pd_obj->setYearToDateEIContribution(0);

        $pd_obj->setGrossPayPeriodIncome(2824.00);


        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '2824.00');
        Debug::text('Prov Ded: ' . $pd_obj->getProvincialPayPeriodDeductions(), __FILE__, __LINE__, __METHOD__, 10);
        $this->assertEquals($this->mf($pd_obj->getProvincialPayPeriodDeductions()), '280.85');
    }

    //
    // MB - Provincial Taxes
    //
    public function testMB_2008a_BiWeekly_Claim1_MedIncome()
    {
        Debug::text('MB - BiWeekly - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'MB');
        $pd_obj->setDate(strtotime('01-Jan-08'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(26);

        $pd_obj->setFederalTotalClaimAmount(9600);
        $pd_obj->setProvincialTotalClaimAmount(8034);
        $pd_obj->setWCBRate(0.18);

        $pd_obj->setEIExempt(false);
        $pd_obj->setCPPExempt(false);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setYearToDateCPPContribution(0);
        $pd_obj->setYearToDateEIContribution(0);

        $pd_obj->setGrossPayPeriodIncome(2754.00);


        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '2754.00');
        Debug::text('Prov Ded: ' . $pd_obj->getProvincialPayPeriodDeductions(), __FILE__, __LINE__, __METHOD__, 10);
        $this->assertEquals($this->mf($pd_obj->getProvincialPayPeriodDeductions()), '294.17');
    }

    public function testMB_2008a_SemiMonthly_Claim1_MedIncome()
    {
        Debug::text('MB - SemiMonthly - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'MB');
        $pd_obj->setDate(strtotime('01-Jan-08'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(24);

        $pd_obj->setFederalTotalClaimAmount(9600);
        $pd_obj->setProvincialTotalClaimAmount(8034);
        $pd_obj->setWCBRate(0.18);

        $pd_obj->setEIExempt(false);
        $pd_obj->setCPPExempt(false);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setYearToDateCPPContribution(0);
        $pd_obj->setYearToDateEIContribution(0);

        $pd_obj->setGrossPayPeriodIncome(2705.00);


        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '2705.00');
        Debug::text('Prov Ded: ' . $pd_obj->getProvincialPayPeriodDeductions(), __FILE__, __LINE__, __METHOD__, 10);
        $this->assertEquals($this->mf($pd_obj->getProvincialPayPeriodDeductions()), '272.32');
    }

    //
    // ON - Provincial Taxes
    //
    public function testON_2008a_BiWeekly_Claim1_MedIncome()
    {
        Debug::text('ON - BiWeekly - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'ON');
        $pd_obj->setDate(strtotime('01-Jan-08'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(26);

        $pd_obj->setFederalTotalClaimAmount(9600);
        $pd_obj->setProvincialTotalClaimAmount(8681);
        $pd_obj->setWCBRate(0.18);

        $pd_obj->setEIExempt(false);
        $pd_obj->setCPPExempt(false);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setYearToDateCPPContribution(0);
        $pd_obj->setYearToDateEIContribution(0);

        $pd_obj->setGrossPayPeriodIncome(2749.00);


        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '2749.00');
        Debug::text('Prov Ded: ' . $pd_obj->getProvincialPayPeriodDeductions(), __FILE__, __LINE__, __METHOD__, 10);
        $this->assertEquals($this->mf($pd_obj->getProvincialPayPeriodDeductions()), '209.40');
    }

    public function testON_2008a_SemiMonthly_Claim1_MedIncome()
    {
        Debug::text('ON - SemiMonthly - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'ON');
        $pd_obj->setDate(strtotime('01-Jan-08'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(24);

        $pd_obj->setFederalTotalClaimAmount(9600);
        $pd_obj->setProvincialTotalClaimAmount(8681);
        $pd_obj->setWCBRate(0.18);

        $pd_obj->setEIExempt(false);
        $pd_obj->setCPPExempt(false);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setYearToDateCPPContribution(0);
        $pd_obj->setYearToDateEIContribution(0);

        $pd_obj->setGrossPayPeriodIncome(2830.00);


        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '2830.00');
        Debug::text('Prov Ded: ' . $pd_obj->getProvincialPayPeriodDeductions(), __FILE__, __LINE__, __METHOD__, 10);
        $this->assertEquals($this->mf($pd_obj->getProvincialPayPeriodDeductions()), '210.59'); //214.00
    }


    //
    // PEI - Provincial Taxes
    //
    public function testPE_2008a_BiWeekly_Claim1_MedIncome()
    {
        Debug::text('PE - BiWeekly - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'PE');
        $pd_obj->setDate(strtotime('01-Jan-08'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(26);

        $pd_obj->setFederalTotalClaimAmount(9600);
        $pd_obj->setProvincialTotalClaimAmount(14254);
        $pd_obj->setWCBRate(0.18);

        $pd_obj->setEIExempt(false);
        $pd_obj->setCPPExempt(false);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setYearToDateCPPContribution(0);
        $pd_obj->setYearToDateEIContribution(0);

        $pd_obj->setGrossPayPeriodIncome(2060);


        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '2060.00');
        Debug::text('Prov Ded: ' . $pd_obj->getProvincialPayPeriodDeductions(), __FILE__, __LINE__, __METHOD__, 10);
        $this->assertEquals($this->mf($pd_obj->getProvincialPayPeriodDeductions()), '170.96');
    }


    public function testPE_2008a_BiWeekly_Claim1_HighIncome()
    {
        Debug::text('PE - BiWeekly - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'PE');
        $pd_obj->setDate(strtotime('01-Jan-08'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(26);

        $pd_obj->setFederalTotalClaimAmount(9600);
        $pd_obj->setProvincialTotalClaimAmount(7708);
        $pd_obj->setWCBRate(0.18);

        $pd_obj->setEIExempt(false);
        $pd_obj->setCPPExempt(false);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setYearToDateCPPContribution(0);
        $pd_obj->setYearToDateEIContribution(0);

        $pd_obj->setGrossPayPeriodIncome(2759);


        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '2759.00');
        Debug::text('Prov Ded: ' . $pd_obj->getProvincialPayPeriodDeductions(), __FILE__, __LINE__, __METHOD__, 10);
        $this->assertEquals($this->mf($pd_obj->getProvincialPayPeriodDeductions()), '300.76'); //298.75
    }

    public function testPE_2008a_BiWeekly_Claim1_MedIncomeB()
    {
        Debug::text('PE - BiWeekly - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'PE');
        $pd_obj->setDate(strtotime('01-Jan-08'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(24);

        $pd_obj->setFederalTotalClaimAmount(9600);
        $pd_obj->setProvincialTotalClaimAmount(7708);
        $pd_obj->setWCBRate(0.18);

        $pd_obj->setEIExempt(false);
        $pd_obj->setCPPExempt(false);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setYearToDateCPPContribution(0);
        $pd_obj->setYearToDateEIContribution(0);

        $pd_obj->setGrossPayPeriodIncome(2725);


        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '2725.00');
        Debug::text('Prov Ded: ' . $pd_obj->getProvincialPayPeriodDeductions(), __FILE__, __LINE__, __METHOD__, 10);
        $this->assertEquals($this->mf($pd_obj->getProvincialPayPeriodDeductions()), '281.75'); //281.74
    }

    public function testPE_2008a_SemiMonthly_Claim1_MedIncome()
    {
        Debug::text('PE - SemiMonthly - Beginning of 2008 01-Jan-08: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'PE');
        $pd_obj->setDate(strtotime('01-Jan-08'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(24);

        $pd_obj->setFederalTotalClaimAmount(9600);
        $pd_obj->setProvincialTotalClaimAmount(7708);
        $pd_obj->setWCBRate(0.18);

        $pd_obj->setEIExempt(false);
        $pd_obj->setCPPExempt(false);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setYearToDateCPPContribution(0);
        $pd_obj->setYearToDateEIContribution(0);

        $pd_obj->setGrossPayPeriodIncome(2763.00);


        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '2763.00');
        Debug::text('Prov Ded: ' . $pd_obj->getProvincialPayPeriodDeductions(), __FILE__, __LINE__, __METHOD__, 10);
        $this->assertEquals($this->mf($pd_obj->getProvincialPayPeriodDeductions()), '288.09'); //285.90
    }
}
