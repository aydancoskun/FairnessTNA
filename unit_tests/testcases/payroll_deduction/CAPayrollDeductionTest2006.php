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
 * @group CAPayrollDeductionTest2006
 */
class CAPayrollDeductionTest2006 extends PHPUnit_Framework_TestCase
{
    public $company_id = null;

    public function setUp()
    {
        Debug::text('Running setUp(): ', __FILE__, __LINE__, __METHOD__, 10);

        require_once(Environment::getBasePath() . '/classes/payroll_deduction/PayrollDeduction.class.php');

        $this->company_id = PRIMARY_COMPANY_ID;

        TTDate::setTimeZone('Etc/GMT+8'); //Force to non-DST timezone. 'PST' isnt actually valid.
        TTi18n::setLocale('en_US', LC_ALL, true); //Due to being a singleton and PHPUnit resetting the state, always force the timezone to be set.

        return true;
    }

    public function tearDown()
    {
        Debug::text('Running tearDown(): ', __FILE__, __LINE__, __METHOD__, 10);
        return true;
    }

    public function testCA_2006b_BiWeekly_Claim1_LowIncome()
    {
        Debug::text('CA - BiWeekly - Beginning of 2006 01-Jul-06: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'BC');
        $pd_obj->setDate(strtotime('01-Jul-06'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(26);

        $pd_obj->setFederalTotalClaimAmount(8639);
        $pd_obj->setProvincialTotalClaimAmount(0);
        $pd_obj->setWCBRate(0.18);

        $pd_obj->setEIExempt(false);
        $pd_obj->setCPPExempt(false);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setYearToDateCPPContribution(0);
        $pd_obj->setYearToDateEIContribution(0);

        $pd_obj->setGrossPayPeriodIncome(585.00);

        //var_dump($pd_obj->getArray());
        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '585.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '31.04');
    }

    //
    //
    //
    //	July 07-2006
    //
    //
    //

    public function mf($amount)
    {
        return Misc::MoneyFormat($amount, false);
    }

    public function testCA_2006b_BiWeekly_Claim1_MedIncome()
    {
        Debug::text('CA - BiWeekly - Beginning of 2006 01-Jul-06: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'BC');
        $pd_obj->setDate(strtotime('01-Jul-06'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(26);

        $pd_obj->setFederalTotalClaimAmount(8639);
        $pd_obj->setProvincialTotalClaimAmount(0);
        $pd_obj->setWCBRate(0.18);

        $pd_obj->setEIExempt(false);
        $pd_obj->setCPPExempt(false);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setYearToDateCPPContribution(0);
        $pd_obj->setYearToDateEIContribution(0);

        $pd_obj->setGrossPayPeriodIncome(2399.00);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '2399.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '366.60');
    }

    public function testCA_2006b_BiWeekly_Claim1_HighIncome()
    {
        Debug::text('CA - BiWeekly - Beginning of 2006 01-Jul-06: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'BC');
        $pd_obj->setDate(strtotime('01-Jul-06'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(26);

        $pd_obj->setFederalTotalClaimAmount(8639);
        $pd_obj->setProvincialTotalClaimAmount(0);
        $pd_obj->setWCBRate(0.18);

        $pd_obj->setEIExempt(false);
        $pd_obj->setCPPExempt(false);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setYearToDateCPPContribution(0);
        $pd_obj->setYearToDateEIContribution(0);

        $pd_obj->setGrossPayPeriodIncome(7167.00);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '7167.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '1668.86');
    }

    public function testCA_2006b_BiWeekly_Claim5_LowIncome()
    {
        Debug::text('CA - BiWeekly - Beginning of 2006 01-Jul-06: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'BC');
        $pd_obj->setDate(strtotime('01-Jul-06'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(26);

        $pd_obj->setFederalTotalClaimAmount(15103.50); //Claim Code5
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
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '25.72'); //Two Penny off...
    }

    public function testCA_2006b_BiWeekly_Claim5_HighIncome()
    {
        Debug::text('CA - BiWeekly - Beginning of 2006 01-Jul-06: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'BC');
        $pd_obj->setDate(strtotime('01-Jul-06'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(26);

        $pd_obj->setFederalTotalClaimAmount(15103.50); //Claim Code5
        $pd_obj->setProvincialTotalClaimAmount(0);
        $pd_obj->setWCBRate(0.18);

        $pd_obj->setEIExempt(false);
        $pd_obj->setCPPExempt(false);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setYearToDateCPPContribution(0);
        $pd_obj->setYearToDateEIContribution(0);

        $pd_obj->setGrossPayPeriodIncome(7167.00);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '7167.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '1630.32'); //Two Penny off...
    }

    public function testCA_2006b_SemiMonthly_Claim1_LowIncome()
    {
        Debug::text('CA - SemiMonthly - Beginning of 2006 01-Jul-06: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'BC');
        $pd_obj->setDate(strtotime('01-Jul-06'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(24);

        $pd_obj->setFederalTotalClaimAmount(8639);
        $pd_obj->setProvincialTotalClaimAmount(0);
        $pd_obj->setWCBRate(0.18);

        $pd_obj->setEIExempt(false);
        $pd_obj->setCPPExempt(false);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setYearToDateCPPContribution(0);
        $pd_obj->setYearToDateEIContribution(0);

        $pd_obj->setGrossPayPeriodIncome(612.00);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '612.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '30.49'); //One penny off
    }

    public function testCA_2006b_SemiMonthly_Claim1_MedIncome()
    {
        Debug::text('CA - SemiMonthly - Beginning of 2006 01-Jul-06: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'BC');
        $pd_obj->setDate(strtotime('01-Jul-06'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(24);

        $pd_obj->setFederalTotalClaimAmount(8639);
        $pd_obj->setProvincialTotalClaimAmount(0);
        $pd_obj->setWCBRate(0.18);

        $pd_obj->setEIExempt(false);
        $pd_obj->setCPPExempt(false);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setYearToDateCPPContribution(0);
        $pd_obj->setYearToDateEIContribution(0);

        $pd_obj->setGrossPayPeriodIncome(2711.00);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '2711.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '421.81'); //One penny off
    }

    public function testCA_2006b_SemiMonthly_Claim1_HighIncome()
    {
        Debug::text('CA - SemiMonthly - Beginning of 2006 01-Jul-06: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'BC');
        $pd_obj->setDate(strtotime('01-Jul-06'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(24);

        $pd_obj->setFederalTotalClaimAmount(8639);
        $pd_obj->setProvincialTotalClaimAmount(0);
        $pd_obj->setWCBRate(0.18);

        $pd_obj->setEIExempt(false);
        $pd_obj->setCPPExempt(false);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setYearToDateCPPContribution(0);
        $pd_obj->setYearToDateEIContribution(0);

        $pd_obj->setGrossPayPeriodIncome(7746.00);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '7746.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '1802.64'); //One penny off
    }

    //
    //	January 01-2006
    //
    //  In July 2006 they introduced K4, which makes it so these tests all fail.
    //
    //
    //
    public function testCA_2006a_BiWeekly_Claim1_LowIncome()
    {
        Debug::text('CA - BiWeekly - Beginning of 2006 01-Jan-06: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'BC');
        $pd_obj->setDate(strtotime('01-Jan-06'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(26);

        $pd_obj->setFederalTotalClaimAmount(9039);
        $pd_obj->setProvincialTotalClaimAmount(0);
        $pd_obj->setWCBRate(0.18);

        $pd_obj->setEIExempt(false);
        $pd_obj->setCPPExempt(false);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setYearToDateCPPContribution(0);
        $pd_obj->setYearToDateEIContribution(0);

        $pd_obj->setGrossPayPeriodIncome(587.00);

        //var_dump($pd_obj->getArray());

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '587.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '30.90');
    }

    public function testCA_2006a_BiWeekly_Claim1_MedIncome()
    {
        Debug::text('CA - BiWeekly - Beginning of 2006 01-Jan-06: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'BC');
        $pd_obj->setDate(strtotime('01-Jan-06'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(26);

        $pd_obj->setFederalTotalClaimAmount(9039);
        $pd_obj->setProvincialTotalClaimAmount(0);
        $pd_obj->setWCBRate(0.18);

        $pd_obj->setEIExempt(false);
        $pd_obj->setCPPExempt(false);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setYearToDateCPPContribution(0);
        $pd_obj->setYearToDateEIContribution(0);

        $pd_obj->setGrossPayPeriodIncome(2395.00);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '2395.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '361.60');
    }

    public function testCA_2006a_BiWeekly_Claim1_HighIncome()
    {
        Debug::text('CA - BiWeekly - Beginning of 2006 01-Jan-06: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'BC');
        $pd_obj->setDate(strtotime('01-Jan-06'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(26);

        $pd_obj->setFederalTotalClaimAmount(9039);
        $pd_obj->setProvincialTotalClaimAmount(0);
        $pd_obj->setWCBRate(0.18);

        $pd_obj->setEIExempt(false);
        $pd_obj->setCPPExempt(false);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setYearToDateCPPContribution(0);
        $pd_obj->setYearToDateEIContribution(0);

        $pd_obj->setGrossPayPeriodIncome(7163.00);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '7163.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '1663.55');
    }

    public function testCA_2006a_BiWeekly_Claim5_LowIncome()
    {
        Debug::text('CA - BiWeekly - Beginning of 2006 01-Jan-06: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'BC');
        $pd_obj->setDate(strtotime('01-Jan-06'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(26);

        $pd_obj->setFederalTotalClaimAmount(15503.50); //Claim Code5
        $pd_obj->setProvincialTotalClaimAmount(0);
        $pd_obj->setWCBRate(0.18);

        $pd_obj->setEIExempt(false);
        $pd_obj->setCPPExempt(false);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setYearToDateCPPContribution(0);
        $pd_obj->setYearToDateEIContribution(0);

        $pd_obj->setGrossPayPeriodIncome(811.00);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '811.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '24.91'); //One Penny off...
    }

    public function testCA_2006a_BiWeekly_Claim5_HighIncome()
    {
        Debug::text('CA - BiWeekly - Beginning of 2006 01-Jan-06: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'BC');
        $pd_obj->setDate(strtotime('01-Jan-06'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(26);

        $pd_obj->setFederalTotalClaimAmount(15503.50); //Claim Code5
        $pd_obj->setProvincialTotalClaimAmount(0);
        $pd_obj->setWCBRate(0.18);

        $pd_obj->setEIExempt(false);
        $pd_obj->setCPPExempt(false);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setYearToDateCPPContribution(0);
        $pd_obj->setYearToDateEIContribution(0);

        $pd_obj->setGrossPayPeriodIncome(7163.00);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '7163.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '1626.25'); //One Penny off...
    }

    public function testCA_2006a_SemiMonthly_Claim1_LowIncome()
    {
        Debug::text('CA - SemiMonthly - Beginning of 2006 01-Jan-06: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'BC');
        $pd_obj->setDate(strtotime('01-Jan-06'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(24);

        $pd_obj->setFederalTotalClaimAmount(9039);
        $pd_obj->setProvincialTotalClaimAmount(0);
        $pd_obj->setWCBRate(0.18);

        $pd_obj->setEIExempt(false);
        $pd_obj->setCPPExempt(false);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setYearToDateCPPContribution(0);
        $pd_obj->setYearToDateEIContribution(0);

        $pd_obj->setGrossPayPeriodIncome(611.00);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '611.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '29.99'); //One penny off
    }

    public function testCA_2006a_SemiMonthly_Claim1_MedIncome()
    {
        Debug::text('CA - SemiMonthly - Beginning of 2006 01-Jan-06: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'BC');
        $pd_obj->setDate(strtotime('01-Jan-06'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(24);

        $pd_obj->setFederalTotalClaimAmount(9039);
        $pd_obj->setProvincialTotalClaimAmount(0);
        $pd_obj->setWCBRate(0.18);

        $pd_obj->setEIExempt(false);
        $pd_obj->setCPPExempt(false);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setYearToDateCPPContribution(0);
        $pd_obj->setYearToDateEIContribution(0);

        $pd_obj->setGrossPayPeriodIncome(2706.00);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '2706.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '416.24'); //One penny off
    }

    public function testCA_2006a_SemiMonthly_Claim1_HighIncome()
    {
        Debug::text('CA - SemiMonthly - Beginning of 2006 01-Jan-06: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'BC');
        $pd_obj->setDate(strtotime('01-Jan-06'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(24);

        $pd_obj->setFederalTotalClaimAmount(9039);
        $pd_obj->setProvincialTotalClaimAmount(0);
        $pd_obj->setWCBRate(0.18);

        $pd_obj->setEIExempt(false);
        $pd_obj->setCPPExempt(false);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setYearToDateCPPContribution(0);
        $pd_obj->setYearToDateEIContribution(0);

        $pd_obj->setGrossPayPeriodIncome(7741.00);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '7741.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '1796.69'); //One penny off
    }

    //
    // BC - Provincial Taxes
    //
    public function testBC_2006a_BiWeekly_Claim1_MedIncome()
    {
        Debug::text('BC - BiWeekly - Beginning of 2006 01-Jan-06: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'BC');
        $pd_obj->setDate(strtotime('01-Jan-06'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(26);

        $pd_obj->setFederalTotalClaimAmount(9039.00);
        $pd_obj->setProvincialTotalClaimAmount(8858.00);
        $pd_obj->setWCBRate(0.18);

        $pd_obj->setEIExempt(false);
        $pd_obj->setCPPExempt(false);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setYearToDateCPPContribution(0);
        $pd_obj->setYearToDateEIContribution(0);

        $pd_obj->setGrossPayPeriodIncome(2758.00);


        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '2758.00');
        Debug::text('Prov Ded: ' . $pd_obj->getProvincialPayPeriodDeductions(), __FILE__, __LINE__, __METHOD__, 10);
        $this->assertEquals($this->mf($pd_obj->getProvincialPayPeriodDeductions()), '189.47'); //189.45
    }

    public function testBC_2006a_BiWeekly_Claim5_MedIncome()
    {
        Debug::text('BC - BiWeekly - Beginning of 2006 01-Jan-06: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'BC');
        $pd_obj->setDate(strtotime('01-Jan-06'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(26);

        $pd_obj->setFederalTotalClaimAmount(9039.00);
        $pd_obj->setProvincialTotalClaimAmount(15833.50); //15833.50
        $pd_obj->setWCBRate(0.18);

        $pd_obj->setEIExempt(false);
        $pd_obj->setCPPExempt(false);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setYearToDateCPPContribution(0);
        $pd_obj->setYearToDateEIContribution(0);

        $pd_obj->setGrossPayPeriodIncome(2758.00);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '2758.00');
        Debug::text('Prov Ded: ' . $pd_obj->getProvincialPayPeriodDeductions(), __FILE__, __LINE__, __METHOD__, 10);
        $this->assertEquals($this->mf($pd_obj->getProvincialPayPeriodDeductions()), '173.24'); //173.25
    }

    //
    // AB - Provincial Taxes
    //
    public function testAB_2006a_BiWeekly_Claim1_LowIncome()
    {
        Debug::text('AB - BiWeekly - Beginning of 2006 01-Jan-06: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'AB');
        //$pd_obj = new PayrollDeduction();
        //$pd_obj->setCountry('CA');
        //$pd_obj->setProvince('BC');
        $pd_obj->setDate(strtotime('01-Jan-06'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(26);

        $pd_obj->setFederalTotalClaimAmount(9039.00);
        $pd_obj->setProvincialTotalClaimAmount(14799.00);
        $pd_obj->setWCBRate(0.18);

        $pd_obj->setEIExempt(false);
        $pd_obj->setCPPExempt(false);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setYearToDateCPPContribution(0);
        $pd_obj->setYearToDateEIContribution(0);

        $pd_obj->setGrossPayPeriodIncome(1428.00);

        Debug::text('Prov Ded: ' . $pd_obj->getProvincialPayPeriodDeductions(), __FILE__, __LINE__, __METHOD__, 10);
        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1428.00');
        $this->assertEquals($this->mf($pd_obj->getProvincialPayPeriodDeductions()), '76.81');
    }

    public function testAB_2006a_BiWeekly_Claim5_LowIncome()
    {
        Debug::text('AB - BiWeekly - Beginning of 2006 01-Jan-06: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'AB');
        //$pd_obj = new PayrollDeduction();
        //$pd_obj->setCountry('CA');
        //$pd_obj->setProvince('BC');
        $pd_obj->setDate(strtotime('01-Jan-06'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(26);

        $pd_obj->setFederalTotalClaimAmount(9039.00);
        $pd_obj->setProvincialTotalClaimAmount(22429.00); //22429
        $pd_obj->setWCBRate(0.18);

        $pd_obj->setEIExempt(false);
        $pd_obj->setCPPExempt(false);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setYearToDateCPPContribution(0);
        $pd_obj->setYearToDateEIContribution(0);

        $pd_obj->setGrossPayPeriodIncome(1428.00);

        Debug::text('Prov Ded: ' . $pd_obj->getProvincialPayPeriodDeductions(), __FILE__, __LINE__, __METHOD__, 10);
        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1428.00');
        $this->assertEquals($this->mf($pd_obj->getProvincialPayPeriodDeductions()), '47.46');
    }

    //
    // SK - Provincial Taxes
    //
    public function testSK_2006a_BiWeekly_Claim1_MedIncome()
    {
        Debug::text('SK - BiWeekly - Beginning of 2006 01-Jan-06: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'SK');
        //$pd_obj = new PayrollDeduction();
        //$pd_obj->setCountry('CA');
        //$pd_obj->setProvince('BC');
        $pd_obj->setDate(strtotime('01-Jan-06'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(26);

        $pd_obj->setFederalTotalClaimAmount(9039.00);
        $pd_obj->setProvincialTotalClaimAmount(8589.00);
        $pd_obj->setWCBRate(0.18);

        $pd_obj->setEIExempt(false);
        $pd_obj->setCPPExempt(false);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setYearToDateCPPContribution(0);
        $pd_obj->setYearToDateEIContribution(0);

        $pd_obj->setGrossPayPeriodIncome(2832.00);


        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '2832.00');
        Debug::text('Prov Ded: ' . $pd_obj->getProvincialPayPeriodDeductions(), __FILE__, __LINE__, __METHOD__, 10);
        $this->assertEquals($this->mf($pd_obj->getProvincialPayPeriodDeductions()), '291.73'); //291.75
    }

    public function testSK_2006a_SemiMonthly_Claim1_MedIncome()
    {
        Debug::text('SK - SemiMonthly - Beginning of 2006 01-Jan-06: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'SK');
        //$pd_obj = new PayrollDeduction();
        //$pd_obj->setCountry('CA');
        //$pd_obj->setProvince('BC');
        $pd_obj->setDate(strtotime('01-Jan-06'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(24);

        $pd_obj->setFederalTotalClaimAmount(9039.00);
        $pd_obj->setProvincialTotalClaimAmount(8589.00);
        $pd_obj->setWCBRate(0.18);

        $pd_obj->setEIExempt(false);
        $pd_obj->setCPPExempt(false);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setYearToDateCPPContribution(0);
        $pd_obj->setYearToDateEIContribution(0);

        $pd_obj->setGrossPayPeriodIncome(2816.00);


        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '2816.00');
        Debug::text('Prov Ded: ' . $pd_obj->getProvincialPayPeriodDeductions(), __FILE__, __LINE__, __METHOD__, 10);
        $this->assertEquals($this->mf($pd_obj->getProvincialPayPeriodDeductions()), '283.28'); //283.30
    }

    //
    // MB - Provincial Taxes
    //
    public function testMB_2006a_BiWeekly_Claim1_MedIncome()
    {
        Debug::text('MB - BiWeekly - Beginning of 2006 01-Jan-06: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'MB');
        //$pd_obj = new PayrollDeduction();
        //$pd_obj->setCountry('CA');
        //$pd_obj->setProvince('BC');
        $pd_obj->setDate(strtotime('01-Jan-06'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(26);

        $pd_obj->setFederalTotalClaimAmount(9039.00);
        $pd_obj->setProvincialTotalClaimAmount(7734.00);
        $pd_obj->setWCBRate(0.18);

        $pd_obj->setEIExempt(false);
        $pd_obj->setCPPExempt(false);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setYearToDateCPPContribution(0);
        $pd_obj->setYearToDateEIContribution(0);

        $pd_obj->setGrossPayPeriodIncome(2750.00);


        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '2750.00');
        Debug::text('Prov Ded: ' . $pd_obj->getProvincialPayPeriodDeductions(), __FILE__, __LINE__, __METHOD__, 10);
        $this->assertEquals($this->mf($pd_obj->getProvincialPayPeriodDeductions()), '306.97'); //306.95
    }

    public function testMB_2006a_SemiMonthly_Claim1_MedIncome()
    {
        Debug::text('MB - SemiMonthly - Beginning of 2006 01-Jan-06: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'MB');
        //$pd_obj = new PayrollDeduction();
        //$pd_obj->setCountry('CA');
        //$pd_obj->setProvince('BC');
        $pd_obj->setDate(strtotime('01-Jan-06'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(24);

        $pd_obj->setFederalTotalClaimAmount(9039.00);
        $pd_obj->setProvincialTotalClaimAmount(7734.00);
        $pd_obj->setWCBRate(0.18);

        $pd_obj->setEIExempt(false);
        $pd_obj->setCPPExempt(false);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setYearToDateCPPContribution(0);
        $pd_obj->setYearToDateEIContribution(0);

        $pd_obj->setGrossPayPeriodIncome(2702.00);


        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '2702.00');
        Debug::text('Prov Ded: ' . $pd_obj->getProvincialPayPeriodDeductions(), __FILE__, __LINE__, __METHOD__, 10);
        $this->assertEquals($this->mf($pd_obj->getProvincialPayPeriodDeductions()), '284.57'); //284.55
    }

    //
    // ON - Provincial Taxes
    //
    public function testON_2006a_BiWeekly_Claim1_MedIncome()
    {
        Debug::text('ON - BiWeekly - Beginning of 2006 01-Jan-06: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'ON');
        //$pd_obj = new PayrollDeduction();
        //$pd_obj->setCountry('CA');
        //$pd_obj->setProvince('BC');
        $pd_obj->setDate(strtotime('01-Jan-06'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(26);

        $pd_obj->setFederalTotalClaimAmount(9039.00);
        $pd_obj->setProvincialTotalClaimAmount(8377.00);
        $pd_obj->setWCBRate(0.18);

        $pd_obj->setEIExempt(false);
        $pd_obj->setCPPExempt(false);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setYearToDateCPPContribution(0);
        $pd_obj->setYearToDateEIContribution(0);

        $pd_obj->setGrossPayPeriodIncome(2740.00);


        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '2740.00');
        Debug::text('Prov Ded: ' . $pd_obj->getProvincialPayPeriodDeductions(), __FILE__, __LINE__, __METHOD__, 10);
        $this->assertEquals($this->mf($pd_obj->getProvincialPayPeriodDeductions()), '214.13'); //214.15
    }

    public function testON_2006a_SemiMonthly_Claim1_MedIncome()
    {
        Debug::text('ON - SemiMonthly - Beginning of 2006 01-Jan-06: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'ON');
        //$pd_obj = new PayrollDeduction();
        //$pd_obj->setCountry('CA');
        //$pd_obj->setProvince('BC');
        $pd_obj->setDate(strtotime('01-Jan-06'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(24);

        $pd_obj->setFederalTotalClaimAmount(9039.00);
        $pd_obj->setProvincialTotalClaimAmount(8377.00);
        $pd_obj->setWCBRate(0.18);

        $pd_obj->setEIExempt(false);
        $pd_obj->setCPPExempt(false);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setYearToDateCPPContribution(0);
        $pd_obj->setYearToDateEIContribution(0);

        $pd_obj->setGrossPayPeriodIncome(2820.00);


        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '2820.00');
        Debug::text('Prov Ded: ' . $pd_obj->getProvincialPayPeriodDeductions(), __FILE__, __LINE__, __METHOD__, 10);
        $this->assertEquals($this->mf($pd_obj->getProvincialPayPeriodDeductions()), '213.99'); //214.00
    }

    //
    // CPP / EI
    //
    public function testCA_2006a_BiWeekly_CPP_LowIncome()
    {
        Debug::text('CA - BiWeekly - CPP - Beginning of 2006 01-Jan-06: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'BC');
        $pd_obj->setDate(strtotime('01-Jan-06'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(26);

        $pd_obj->setFederalTotalClaimAmount(9039);
        $pd_obj->setProvincialTotalClaimAmount(0);
        $pd_obj->setWCBRate(0.18);

        $pd_obj->setEIExempt(false);
        $pd_obj->setCPPExempt(false);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setYearToDateCPPContribution(0);
        $pd_obj->setYearToDateEIContribution(0);

        $pd_obj->setGrossPayPeriodIncome(587.00);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '587.00');
        $this->assertEquals($this->mf($pd_obj->getEmployeeCPP()), '22.39');
        $this->assertEquals($this->mf($pd_obj->getEmployerCPP()), '22.39');
    }

    public function testCA_2006a_SemiMonthly_CPP_LowIncome()
    {
        Debug::text('CA - BiWeekly - CPP - Beginning of 2006 01-Jan-06: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'BC');
        $pd_obj->setDate(strtotime('01-Jan-06'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(24);

        $pd_obj->setFederalTotalClaimAmount(9039);
        $pd_obj->setProvincialTotalClaimAmount(0);
        $pd_obj->setWCBRate(0.18);

        $pd_obj->setEIExempt(false);
        $pd_obj->setCPPExempt(false);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setYearToDateCPPContribution(0);
        $pd_obj->setYearToDateEIContribution(0);

        $pd_obj->setGrossPayPeriodIncome(587.00);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '587.00');
        $this->assertEquals($this->mf($pd_obj->getEmployeeCPP()), '21.84');
        $this->assertEquals($this->mf($pd_obj->getEmployerCPP()), '21.84');
    }


    public function testCA_2006a_SemiMonthly_MAXCPP_LowIncome()
    {
        Debug::text('CA - BiWeekly - MAXCPP - Beginning of 2006 01-Jan-06: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'BC');
        $pd_obj->setDate(strtotime('01-Jan-06'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(24);

        $pd_obj->setFederalTotalClaimAmount(9039);
        $pd_obj->setProvincialTotalClaimAmount(0);
        $pd_obj->setWCBRate(0.18);

        $pd_obj->setEIExempt(false);
        $pd_obj->setCPPExempt(false);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setYearToDateCPPContribution(1909.70); //1910.70
        $pd_obj->setYearToDateEIContribution(0);

        $pd_obj->setGrossPayPeriodIncome(587.00);

        //var_dump($pd_obj->getArray());

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '587.00');
        $this->assertEquals($this->mf($pd_obj->getEmployeeCPP()), '1.00');
        $this->assertEquals($this->mf($pd_obj->getEmployerCPP()), '1.00');
    }

    public function testCA_2006a_EI_LowIncome()
    {
        Debug::text('CA - EI - Beginning of 2006 01-Jan-06: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'BC');
        $pd_obj->setDate(strtotime('01-Jan-06'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(26);

        $pd_obj->setFederalTotalClaimAmount(9039);
        $pd_obj->setProvincialTotalClaimAmount(0);
        $pd_obj->setWCBRate(0.18);

        $pd_obj->setEIExempt(false);
        $pd_obj->setCPPExempt(false);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setYearToDateCPPContribution(0);
        $pd_obj->setYearToDateEIContribution(0);

        $pd_obj->setGrossPayPeriodIncome(587.00);

        //var_dump($pd_obj->getArray());

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '587.00');
        $this->assertEquals($this->mf($pd_obj->getEmployeeEI()), '10.98');
        $this->assertEquals($this->mf($pd_obj->getEmployerEI()), '15.37');
    }

    public function testCA_2006a_MAXEI_LowIncome()
    {
        Debug::text('CA - MAXEI - Beginning of 2006 01-Jan-06: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'BC');
        $pd_obj->setDate(strtotime('01-Jan-06'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(26);

        $pd_obj->setFederalTotalClaimAmount(9039);
        $pd_obj->setProvincialTotalClaimAmount(0);
        $pd_obj->setWCBRate(0.18);

        $pd_obj->setEIExempt(false);
        $pd_obj->setCPPExempt(false);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setYearToDateCPPContribution(0);
        $pd_obj->setYearToDateEIContribution(728.30); //729.30 - 1.00

        $pd_obj->setGrossPayPeriodIncome(587.00);

        //var_dump($pd_obj->getArray());

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '587.00');
        $this->assertEquals($this->mf($pd_obj->getEmployeeEI()), '1.00');
        $this->assertEquals($this->mf($pd_obj->getEmployerEI()), '1.40');
    }
}
