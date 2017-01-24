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
 * @group CAPayrollDeductionTest2007
 */
class CAPayrollDeductionTest2007 extends PHPUnit_Framework_TestCase
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

    public function testCA_2007a_BasicClaimAmount()
    {
        Debug::text('CA - BiWeekly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'BC');
        //$pd_obj->setDate(strtotime('01-Jan-07 12:00:00 PST'));
        $pd_obj->setDate(strtotime('01-Jan-07'));
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
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '441.09');
        $this->assertEquals($this->mf($pd_obj->getProvincialPayPeriodDeductions()), '188.28');
    }

    //
    // January 2007
    //

    public function mf($amount)
    {
        return Misc::MoneyFormat($amount, false);
    }

    public function testCA_2007a_BasicClaimAmountB()
    {
        Debug::text('CA - BiWeekly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'BC');
        $pd_obj->setDate(strtotime('01-Jan-07'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(26);

        $pd_obj->setFederalTotalClaimAmount(8929);
        $pd_obj->setProvincialTotalClaimAmount(9027);
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
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '441.09');
        $this->assertEquals($this->mf($pd_obj->getProvincialPayPeriodDeductions()), '188.28');
    }

    public function testCA_2007a_BiWeekly_Claim1_LowIncome()
    {
        Debug::text('CA - BiWeekly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'BC');
        $pd_obj->setDate(strtotime('01-Jan-07'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(26);

        $pd_obj->setFederalTotalClaimAmount(8929);
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
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '26.97');
    }

    public function testCA_2007a_BiWeekly_Claim1_MedIncome()
    {
        Debug::text('CA - BiWeekly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'BC');
        $pd_obj->setDate(strtotime('01-Jan-07'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(26);

        $pd_obj->setFederalTotalClaimAmount(8929);
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
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '361.23');
    }

    public function testCA_2007a_BiWeekly_Claim1_HighIncome()
    {
        Debug::text('CA - BiWeekly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'BC');
        $pd_obj->setDate(strtotime('01-Jan-07'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(26);

        $pd_obj->setFederalTotalClaimAmount(8929);
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
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '1665.56');
    }

    public function testCA_2007a_BiWeekly_Claim5_LowIncome()
    {
        Debug::text('CA - BiWeekly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'BC');
        $pd_obj->setDate(strtotime('01-Jan-07'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(26);

        $pd_obj->setFederalTotalClaimAmount(15537.00); //Claim Code5
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
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '20.24'); //One Penny off...
    }

    public function testCA_2007a_BiWeekly_Claim5_HighIncome()
    {
        Debug::text('CA - BiWeekly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'BC');
        $pd_obj->setDate(strtotime('01-Jan-07'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(26);

        $pd_obj->setFederalTotalClaimAmount(15537.00); //Claim Code5
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
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '1626.16'); //One Penny off...
    }

    public function testCA_2007a_SemiMonthly_Claim1_LowIncome()
    {
        Debug::text('CA - SemiMonthly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'BC');
        $pd_obj->setDate(strtotime('01-Jan-07'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(24);

        $pd_obj->setFederalTotalClaimAmount(8929);
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
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '25.88'); //One penny off
    }

    public function testCA_2007a_SemiMonthly_Claim1_MedIncome()
    {
        Debug::text('CA - SemiMonthly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'BC');
        $pd_obj->setDate(strtotime('01-Jan-07'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(24);

        $pd_obj->setFederalTotalClaimAmount(8929);
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
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '416.07'); //One penny off
    }

    public function testCA_2007a_SemiMonthly_Claim1_HighIncome()
    {
        Debug::text('CA - SemiMonthly - Beginning of 2006 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'BC');
        $pd_obj->setDate(strtotime('01-Jan-07'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(24);

        $pd_obj->setFederalTotalClaimAmount(8929);
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
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '1799.16'); //One penny off
    }

    //
    // CPP/ EI
    //
    public function testCA_2007a_BiWeekly_CPP_LowIncome()
    {
        Debug::text('CA - BiWeekly - CPP - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'BC');
        $pd_obj->setDate(strtotime('01-Jan-07'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(26);

        $pd_obj->setFederalTotalClaimAmount(8929);
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

    public function testCA_2007a_SemiMonthly_CPP_LowIncome()
    {
        Debug::text('CA - BiWeekly - CPP - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'BC');
        $pd_obj->setDate(strtotime('01-Jan-07'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(24);

        $pd_obj->setFederalTotalClaimAmount(8929);
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

    public function testCA_2007a_EI_LowIncome()
    {
        Debug::text('CA - EI - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'BC');
        $pd_obj->setDate(strtotime('01-Jan-07'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(26);

        $pd_obj->setFederalTotalClaimAmount(8929);
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
        $this->assertEquals($this->mf($pd_obj->getEmployeeEI()), '10.58');
        $this->assertEquals($this->mf($pd_obj->getEmployerEI()), '14.81');
    }

    //
    // BC - Provincial Taxes
    //
    public function testBC_2007b_BiWeekly_Claim1_MedIncome()
    {
        Debug::text('BC - BiWeekly - Beginning of 2007 01-Jul-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'BC');
        $pd_obj->setDate(strtotime('01-Jul-07'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(26);

        $pd_obj->setFederalTotalClaimAmount(8929);
        $pd_obj->setProvincialTotalClaimAmount(9027.00);
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
        $this->assertEquals($this->mf($pd_obj->getProvincialPayPeriodDeductions()), '167.89');
    }

    public function testBC_2007b_BiWeekly_Claim5_MedIncome()
    {
        Debug::text('BC - BiWeekly - Beginning of 2007 01-Jul-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'BC');
        $pd_obj->setDate(strtotime('01-Jul-07'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(26);

        $pd_obj->setFederalTotalClaimAmount(8929);
        $pd_obj->setProvincialTotalClaimAmount(16135.50);
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
        $this->assertEquals($this->mf($pd_obj->getProvincialPayPeriodDeductions()), '153.26');
    }

    public function testBC_2007a_BiWeekly_Claim1_MedIncome()
    {
        Debug::text('BC - BiWeekly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'BC');
        $pd_obj->setDate(strtotime('01-Jan-07'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(26);

        $pd_obj->setFederalTotalClaimAmount(8929);
        $pd_obj->setProvincialTotalClaimAmount(9027.00);
        $pd_obj->setWCBRate(0.18);

        $pd_obj->setEIExempt(false);
        $pd_obj->setCPPExempt(false);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setYearToDateCPPContribution(0);
        $pd_obj->setYearToDateEIContribution(0);

        $pd_obj->setGrossPayPeriodIncome(2770.00);


        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '2770.00');
        Debug::text('Prov Ded: ' . $pd_obj->getProvincialPayPeriodDeductions(), __FILE__, __LINE__, __METHOD__, 10);
        $this->assertEquals($this->mf($pd_obj->getProvincialPayPeriodDeductions()), '188.28');
    }

    public function testBC_2007a_BiWeekly_Claim5_MedIncome()
    {
        Debug::text('BC - BiWeekly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'BC');
        $pd_obj->setDate(strtotime('01-Jan-07'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(26);

        $pd_obj->setFederalTotalClaimAmount(8929);
        $pd_obj->setProvincialTotalClaimAmount(16135.50);
        $pd_obj->setWCBRate(0.18);

        $pd_obj->setEIExempt(false);
        $pd_obj->setCPPExempt(false);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setYearToDateCPPContribution(0);
        $pd_obj->setYearToDateEIContribution(0);

        $pd_obj->setGrossPayPeriodIncome(2770.00);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '2770.00');
        Debug::text('Prov Ded: ' . $pd_obj->getProvincialPayPeriodDeductions(), __FILE__, __LINE__, __METHOD__, 10);
        $this->assertEquals($this->mf($pd_obj->getProvincialPayPeriodDeductions()), '171.74');
    }

    //
    // AB - Provincial Taxes
    //
    public function testAB_2007a_BiWeekly_Claim1_LowIncome()
    {
        Debug::text('AB - BiWeekly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'AB');
        $pd_obj->setDate(strtotime('01-Jan-07'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(26);

        $pd_obj->setFederalTotalClaimAmount(8929);
        $pd_obj->setProvincialTotalClaimAmount(15535);
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
        $this->assertEquals($this->mf($pd_obj->getProvincialPayPeriodDeductions()), '73.52');
    }

    public function testAB_2007a_BiWeekly_Claim5_LowIncome()
    {
        Debug::text('AB - BiWeekly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'AB');
        $pd_obj->setDate(strtotime('01-Jan-07'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(26);

        $pd_obj->setFederalTotalClaimAmount(8929);
        $pd_obj->setProvincialTotalClaimAmount(23338);
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
        $this->assertEquals($this->mf($pd_obj->getProvincialPayPeriodDeductions()), '43.51');
    }

    //
    // SK - Provincial Taxes
    //
    public function testSK_2007a_BiWeekly_Claim1_MedIncome()
    {
        Debug::text('SK - BiWeekly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'SK');
        //$pd_obj = new PayrollDeduction();
        //$pd_obj->setCountry('CA');
        //$pd_obj->setProvince('BC');
        $pd_obj->setDate(strtotime('01-Jan-07'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(26);

        $pd_obj->setFederalTotalClaimAmount(8929);
        $pd_obj->setProvincialTotalClaimAmount(8778.00);
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
        $this->assertEquals($this->mf($pd_obj->getProvincialPayPeriodDeductions()), '291.06');
    }

    public function testSK_2007a_SemiMonthly_Claim1_MedIncome()
    {
        Debug::text('SK - SemiMonthly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'SK');
        $pd_obj->setDate(strtotime('01-Jan-07'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(24);

        $pd_obj->setFederalTotalClaimAmount(8929);
        $pd_obj->setProvincialTotalClaimAmount(8778.00);
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
        $this->assertEquals($this->mf($pd_obj->getProvincialPayPeriodDeductions()), '282.47');
    }

    //
    // MB - Provincial Taxes
    //
    public function testMB_2007a_BiWeekly_Claim1_MedIncome()
    {
        Debug::text('MB - BiWeekly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'MB');
        $pd_obj->setDate(strtotime('01-Jan-07'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(26);

        $pd_obj->setFederalTotalClaimAmount(8929);
        $pd_obj->setProvincialTotalClaimAmount(7834);
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
        $this->assertEquals($this->mf($pd_obj->getProvincialPayPeriodDeductions()), '300.34');
    }

    public function testMB_2007a_SemiMonthly_Claim1_MedIncome()
    {
        Debug::text('MB - SemiMonthly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'MB');
        $pd_obj->setDate(strtotime('01-Jan-07'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(24);

        $pd_obj->setFederalTotalClaimAmount(8929);
        $pd_obj->setProvincialTotalClaimAmount(7834);
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
        $this->assertEquals($this->mf($pd_obj->getProvincialPayPeriodDeductions()), '277.05');
    }

    //
    // ON - Provincial Taxes
    //
    public function testON_2007a_BiWeekly_Claim1_MedIncome()
    {
        Debug::text('ON - BiWeekly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'ON');
        $pd_obj->setDate(strtotime('01-Jan-07'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(26);

        $pd_obj->setFederalTotalClaimAmount(8929);
        $pd_obj->setProvincialTotalClaimAmount(8553);
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
        $this->assertEquals($this->mf($pd_obj->getProvincialPayPeriodDeductions()), '211.60');
    }

    public function testON_2007a_SemiMonthly_Claim1_MedIncome()
    {
        Debug::text('ON - SemiMonthly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'ON');
        //$pd_obj = new PayrollDeduction();
        //$pd_obj->setCountry('CA');
        //$pd_obj->setProvince('BC');
        $pd_obj->setDate(strtotime('01-Jan-07'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(24);

        $pd_obj->setFederalTotalClaimAmount(8929);
        $pd_obj->setProvincialTotalClaimAmount(8553);
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
        $this->assertEquals($this->mf($pd_obj->getProvincialPayPeriodDeductions()), '212.50'); //214.00
    }
}
