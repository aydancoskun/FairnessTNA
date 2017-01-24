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
 * @group CAPayrollDeductionTest2014
 */
class CAPayrollDeductionTest2014 extends PHPUnit_Framework_TestCase
{
    public $company_id = null;

    public function setUp()
    {
        Debug::text('Running setUp(): ', __FILE__, __LINE__, __METHOD__, 10);

        require_once(Environment::getBasePath() . '/classes/payroll_deduction/PayrollDeduction.class.php');

        $this->tax_table_file = dirname(__FILE__) . '/CAPayrollDeductionTest2014.csv';

        $this->company_id = PRIMARY_COMPANY_ID;

        TTDate::setTimeZone('Etc/GMT+8'); //Force to non-DST timezone. 'PST' isnt actually valid.

        return true;
    }

    public function tearDown()
    {
        Debug::text('Running tearDown(): ', __FILE__, __LINE__, __METHOD__, 10);
        return true;
    }

    public function testCSVFile()
    {
        $this->assertEquals(file_exists($this->tax_table_file), true);

        $test_rows = Misc::parseCSV($this->tax_table_file, true);

        $total_rows = (count($test_rows) + 1);
        $i = 2;
        foreach ($test_rows as $row) {
            //Debug::text('Province: '. $row['province'] .' Income: '. $row['gross_income'], __FILE__, __LINE__, __METHOD__, 10);
            if (isset($row['gross_income']) and isset($row['low_income']) and isset($row['high_income'])
                and $row['gross_income'] == '' and $row['low_income'] != '' and $row['high_income'] != ''
            ) {
                $row['gross_income'] = ($row['low_income'] + (($row['high_income'] - $row['low_income']) / 2));
            }
            if ($row['country'] != '' and $row['gross_income'] != '') {
                //echo $i.'/'.$total_rows.'. Testing Province: '. $row['province'] .' Income: '. $row['gross_income'] ."\n";

                $pd_obj = new PayrollDeduction($row['country'], $row['province']);
                $pd_obj->setDate(strtotime($row['date']));
                $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
                $pd_obj->setAnnualPayPeriods($row['pay_periods']);

                $pd_obj->setFederalTotalClaimAmount($row['federal_claim']); //Amount from 2005, Should use amount from 2007 automatically.
                $pd_obj->setProvincialTotalClaimAmount($row['provincial_claim']);
                //$pd_obj->setWCBRate( 0.18 );

                $pd_obj->setEIExempt(false);
                $pd_obj->setCPPExempt(false);

                $pd_obj->setFederalTaxExempt(false);
                $pd_obj->setProvincialTaxExempt(false);

                $pd_obj->setYearToDateCPPContribution(0);
                $pd_obj->setYearToDateEIContribution(0);

                $pd_obj->setGrossPayPeriodIncome($this->mf($row['gross_income']));

                //var_dump($pd_obj->getArray());

                $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), $this->mf($row['gross_income']));
                if ($row['federal_deduction'] != '') {
                    $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), $this->mf($row['federal_deduction']));
                }
                if ($row['provincial_deduction'] != '') {
                    $this->assertEquals($this->mf($pd_obj->getProvincialPayPeriodDeductions()), $this->mf($row['provincial_deduction']));
                }
            }

            $i++;
        }

        //Make sure all rows are tested.
        $this->assertEquals($total_rows, ($i - 1));
    }

    //
    // January 2014
    //

    public function mf($amount)
    {
        return Misc::MoneyFormat($amount, false);
    }

    public function testCA_ClaimAmountAdjustmentA()
    {
        $pd_obj = new PayrollDeduction('CA', 'BC');
        $pd_obj->setDate(strtotime('01-Jan-2013'));
        $pd_obj->setFederalTotalClaimAmount(11038);
        $pd_obj->setProvincialTotalClaimAmount(10276);

        $this->assertEquals($this->mf($pd_obj->getFederalTotalClaimAmount()), '11038');
        $this->assertEquals($this->mf($pd_obj->getProvincialTotalClaimAmount()), '10276');
    }

    //Test claim amount set lower than the minimum.
    public function testCA_ClaimAmountAdjustmentB()
    {
        $pd_obj = new PayrollDeduction('CA', 'BC');
        $pd_obj->setDate(strtotime('01-Jan-2013'));
        $pd_obj->setFederalTotalClaimAmount(10000);
        $pd_obj->setProvincialTotalClaimAmount(10000);

        $this->assertEquals($this->mf($pd_obj->getFederalTotalClaimAmount()), '11038');
        $this->assertEquals($this->mf($pd_obj->getProvincialTotalClaimAmount()), '10276');
    }

    //Test claim amount at 0.
    public function testCA_ClaimAmountAdjustmentC()
    {
        $pd_obj = new PayrollDeduction('CA', 'BC');
        $pd_obj->setDate(strtotime('01-Jan-2013'));
        $pd_obj->setFederalTotalClaimAmount(0);
        $pd_obj->setProvincialTotalClaimAmount(0);

        $this->assertEquals($this->mf($pd_obj->getFederalTotalClaimAmount()), '0');
        $this->assertEquals($this->mf($pd_obj->getProvincialTotalClaimAmount()), '0');
    }

    //Test claim amount higher than minimum.
    public function testCA_ClaimAmountAdjustmentD()
    {
        $pd_obj = new PayrollDeduction('CA', 'BC');
        $pd_obj->setDate(strtotime('01-Jan-2013'));
        $pd_obj->setFederalTotalClaimAmount(11040);
        $pd_obj->setProvincialTotalClaimAmount(10280);

        $this->assertEquals($this->mf($pd_obj->getFederalTotalClaimAmount()), '11040');
        $this->assertEquals($this->mf($pd_obj->getProvincialTotalClaimAmount()), '10280');
    }

    //Test claim amount at next years values, should be reverted back to current year.
    public function testCA_ClaimAmountAdjustmentE()
    {
        $pd_obj = new PayrollDeduction('CA', 'BC');
        $pd_obj->setDate(strtotime('01-Jan-2013'));
        $pd_obj->setFederalTotalClaimAmount(11138);
        $pd_obj->setProvincialTotalClaimAmount(9869);

        $this->assertEquals($this->mf($pd_obj->getFederalTotalClaimAmount()), '11038');
        $this->assertEquals($this->mf($pd_obj->getProvincialTotalClaimAmount()), '10276');
    }

    public function testCA_2014a_Example()
    {
        Debug::text('CA - Example Beginning of 01-Jan-2014: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'AB');
        $pd_obj->setDate(strtotime('01-Jan-2014'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(52);

        $pd_obj->setFederalTotalClaimAmount(28964.50);
        $pd_obj->setProvincialTotalClaimAmount(17593);
        $pd_obj->setWCBRate(0.18);

        $pd_obj->setEIExempt(false);
        $pd_obj->setCPPExempt(false);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setYearToDateCPPContribution(0);
        $pd_obj->setYearToDateEIContribution(0);

        $pd_obj->setGrossPayPeriodIncome(1100);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1100');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '86.39');
        $this->assertEquals($this->mf($pd_obj->getProvincialPayPeriodDeductions()), '69.37');
    }

    public function testCA_2014a_Example1()
    {
        Debug::text('CA - Example1 - Beginning of 01-Jan-2014: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'AB');
        $pd_obj->setDate(strtotime('01-Jan-2014'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(12);

        $pd_obj->setFederalTotalClaimAmount(11038);
        $pd_obj->setProvincialTotalClaimAmount(17593);
        $pd_obj->setWCBRate(0.18);

        $pd_obj->setEIExempt(false);
        $pd_obj->setCPPExempt(false);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setYearToDateCPPContribution(0);
        $pd_obj->setYearToDateEIContribution(0);

        $pd_obj->setGrossPayPeriodIncome(1800);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1800');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '100.41');
        $this->assertEquals($this->mf($pd_obj->getProvincialPayPeriodDeductions()), '20.92');
    }

    public function testCA_2014a_Example2()
    {
        Debug::text('CA - Example2 - Beginning of 01-Jan-2014: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'AB');
        $pd_obj->setDate(strtotime('01-Jan-2014'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(26);

        $pd_obj->setFederalTotalClaimAmount(11038);
        $pd_obj->setProvincialTotalClaimAmount(17593);
        $pd_obj->setWCBRate(0.18);

        $pd_obj->setEIExempt(false);
        $pd_obj->setCPPExempt(false);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setYearToDateCPPContribution(0);
        $pd_obj->setYearToDateEIContribution(0);

        $pd_obj->setGrossPayPeriodIncome(2300);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '2300');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '297.63');
        $this->assertEquals($this->mf($pd_obj->getProvincialPayPeriodDeductions()), '148.75');
    }

    public function testCA_2014a_Example3()
    {
        Debug::text('CA - Example3 - Beginning of 01-Jan-2014: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'AB');
        $pd_obj->setDate(strtotime('01-Jan-2014'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(52);

        $pd_obj->setFederalTotalClaimAmount(11038);
        $pd_obj->setProvincialTotalClaimAmount(17593);
        $pd_obj->setWCBRate(0.18);

        $pd_obj->setEIExempt(false);
        $pd_obj->setCPPExempt(false);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setYearToDateCPPContribution(0);
        $pd_obj->setYearToDateEIContribution(0);

        $pd_obj->setGrossPayPeriodIncome(2500);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '2500');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '478.20');
        $this->assertEquals($this->mf($pd_obj->getProvincialPayPeriodDeductions()), '209.37');
    }

    public function testCA_2014a_Example4()
    {
        Debug::text('CA - Example1 - Beginning of 01-Jan-2014: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'BC');
        $pd_obj->setDate(strtotime('01-Jan-2014'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(26);

        $pd_obj->setFederalTotalClaimAmount(11038);
        $pd_obj->setProvincialTotalClaimAmount(10276);
        $pd_obj->setWCBRate(0);

        $pd_obj->setEIExempt(false);
        $pd_obj->setCPPExempt(false);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setYearToDateCPPContribution(0);
        $pd_obj->setYearToDateEIContribution(0);

        $pd_obj->setGrossPayPeriodIncome(1560);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1560');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '148.26');
        $this->assertEquals($this->mf($pd_obj->getProvincialPayPeriodDeductions()), '57.67');
    }

    public function testCA_2014a_GovExample1()
    {
        Debug::text('CA - Example1 - Beginning of 01-Jan-2014: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'AB');
        $pd_obj->setDate(strtotime('01-Jan-2014'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(52);

        $pd_obj->setFederalTotalClaimAmount(11138);
        $pd_obj->setProvincialTotalClaimAmount(17787);
        $pd_obj->setWCBRate(0);

        $pd_obj->setEIExempt(false);
        $pd_obj->setCPPExempt(false);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setYearToDateCPPContribution(0);
        $pd_obj->setYearToDateEIContribution(0);

        $pd_obj->setGrossPayPeriodIncome(1030); //Take Gross income minus RPP and Union Dues.

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1030');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '122.41');
        $this->assertEquals($this->mf($pd_obj->getProvincialPayPeriodDeductions()), '62.37');
    }

    public function testCA_2014a_GovExample2()
    {
        Debug::text('CA - Example1 - Beginning of 01-Jan-2014: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'BC');
        $pd_obj->setDate(strtotime('01-Jan-2014'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(52);

        $pd_obj->setFederalTotalClaimAmount(11138);
        $pd_obj->setProvincialTotalClaimAmount(9869);
        $pd_obj->setWCBRate(0);

        $pd_obj->setEIExempt(false);
        $pd_obj->setCPPExempt(false);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setYearToDateCPPContribution(0);
        $pd_obj->setYearToDateEIContribution(0);

        $pd_obj->setGrossPayPeriodIncome(1030); //Take Gross income minus RPP and Union Dues.

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1030');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '122.41');
        $this->assertEquals($this->mf($pd_obj->getProvincialPayPeriodDeductions()), '47.36');
    }

    public function testCA_2014a_GovExample3()
    {
        Debug::text('CA - Example1 - Beginning of 01-Jan-2014: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'ON');
        $pd_obj->setDate(strtotime('01-Jan-2014'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(52);

        $pd_obj->setFederalTotalClaimAmount(11138);
        $pd_obj->setProvincialTotalClaimAmount(9670);
        $pd_obj->setWCBRate(0);

        $pd_obj->setEIExempt(false);
        $pd_obj->setCPPExempt(false);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setYearToDateCPPContribution(0);
        $pd_obj->setYearToDateEIContribution(0);

        $pd_obj->setGrossPayPeriodIncome(1030); //Take Gross income minus RPP and Union Dues.

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1030');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '122.41');
        $this->assertEquals($this->mf($pd_obj->getProvincialPayPeriodDeductions()), '61.51');
    }

    public function testCA_2014a_GovExample4()
    {
        Debug::text('CA - Example1 - Beginning of 01-Jan-2014: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'PE');
        $pd_obj->setDate(strtotime('01-Jan-2014'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(52);

        $pd_obj->setFederalTotalClaimAmount(11138);
        $pd_obj->setProvincialTotalClaimAmount(7708);
        $pd_obj->setWCBRate(0);

        $pd_obj->setEIExempt(false);
        $pd_obj->setCPPExempt(false);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setYearToDateCPPContribution(0);
        $pd_obj->setYearToDateEIContribution(0);

        $pd_obj->setGrossPayPeriodIncome(1030); //Take Gross income minus RPP and Union Dues.

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1030');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '122.41');
        $this->assertEquals($this->mf($pd_obj->getProvincialPayPeriodDeductions()), '96.72');
    }

    //
    // CPP/ EI
    //
    public function testCA_2014a_BiWeekly_CPP_LowIncome()
    {
        Debug::text('CA - BiWeekly - CPP - Beginning of 01-Jan-2014: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'BC');
        $pd_obj->setDate(strtotime('01-Jan-2014'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(26);

        $pd_obj->setFederalTotalClaimAmount(11038);
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

    public function testCA_2014a_SemiMonthly_CPP_LowIncome()
    {
        Debug::text('CA - BiWeekly - CPP - Beginning of 01-Jan-2014: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'BC');
        $pd_obj->setDate(strtotime('01-Jan-2014'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(24);

        $pd_obj->setFederalTotalClaimAmount(11038);
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

    public function testCA_2014a_SemiMonthly_MAXCPP_LowIncome()
    {
        Debug::text('CA - BiWeekly - MAXCPP - Beginning of 2014 01-Jan-2014: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'BC');
        $pd_obj->setDate(strtotime('01-Jan-2014'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(24);

        $pd_obj->setFederalTotalClaimAmount(11038);
        $pd_obj->setProvincialTotalClaimAmount(0);
        $pd_obj->setWCBRate(0.18);

        $pd_obj->setEIExempt(false);
        $pd_obj->setCPPExempt(false);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setYearToDateCPPContribution(2424.50); //2425.50 - 1.00
        $pd_obj->setYearToDateEIContribution(0);

        $pd_obj->setGrossPayPeriodIncome(587.00);

        //var_dump($pd_obj->getArray());

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '587.00');
        $this->assertEquals($this->mf($pd_obj->getEmployeeCPP()), '1.00');
        $this->assertEquals($this->mf($pd_obj->getEmployerCPP()), '1.00');
    }

    public function testCA_2014a_EI_LowIncome()
    {
        Debug::text('CA - EI - Beginning of 01-Jan-2014: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'BC');
        $pd_obj->setDate(strtotime('01-Jan-2014'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(26);

        $pd_obj->setFederalTotalClaimAmount(11038);
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
        $this->assertEquals($this->mf($pd_obj->getEmployeeEI()), '11.05');
        $this->assertEquals($this->mf($pd_obj->getEmployerEI()), '15.47');
    }

    public function testCA_2014a_MAXEI_LowIncome()
    {
        Debug::text('CA - MAXEI - Beginning of 2006 01-Jan-2014: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'BC');
        $pd_obj->setDate(strtotime('01-Jan-2014'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(26);

        $pd_obj->setFederalTotalClaimAmount(11038);
        $pd_obj->setProvincialTotalClaimAmount(0);
        $pd_obj->setWCBRate(0.18);

        $pd_obj->setEIExempt(false);
        $pd_obj->setCPPExempt(false);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setYearToDateCPPContribution(0);
        $pd_obj->setYearToDateEIContribution(912.68); //913.68 - 1.00

        $pd_obj->setGrossPayPeriodIncome(587.00);

        //var_dump($pd_obj->getArray());

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '587.00');
        $this->assertEquals($this->mf($pd_obj->getEmployeeEI()), '1.00');
        $this->assertEquals($this->mf($pd_obj->getEmployerEI()), '1.40');
    }
}
