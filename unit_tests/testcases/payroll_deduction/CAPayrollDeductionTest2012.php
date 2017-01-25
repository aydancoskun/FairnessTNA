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
 * @group CAPayrollDeductionTest2012
 */
class CAPayrollDeductionTest2012 extends PHPUnit_Framework_TestCase
{
    public $company_id = null;

    public function setUp()
    {
        Debug::text('Running setUp(): ', __FILE__, __LINE__, __METHOD__, 10);

        require_once(Environment::getBasePath() . '/classes/payroll_deduction/PayrollDeduction.class.php');

        $this->tax_table_file = dirname(__FILE__) . '/CAPayrollDeductionTest2012.csv';

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
            //Debug::text('Province: '. $row['province'] .' Income: '. $row['gross_income'], __FILE__, __LINE__, __METHOD__,10);
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
    // January 2012
    //

    //
    // Don't forget to update the Federal Employment Credit in CA.class.php.
    //

    public function mf($amount)
    {
        return Misc::MoneyFormat($amount, false);
    }

    public function testCA_2012a_Example()
    {
        Debug::text('CA - Example Beginning of 01-Jan-2012: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'AB');
        $pd_obj->setDate(strtotime('01-Jan-2012'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(52);

        $pd_obj->setFederalTotalClaimAmount(28217);
        $pd_obj->setProvincialTotalClaimAmount(17282);
        $pd_obj->setWCBRate(0.18);

        $pd_obj->setEIExempt(false);
        $pd_obj->setCPPExempt(false);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setYearToDateCPPContribution(0);
        $pd_obj->setYearToDateEIContribution(0);

        $pd_obj->setGrossPayPeriodIncome(1100);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1100');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '90.89');
        $this->assertEquals($this->mf($pd_obj->getProvincialPayPeriodDeductions()), '70.71');
    }

    public function testCA_2012a_Example1()
    {
        Debug::text('CA - Example1 - Beginning of 01-Jan-2012: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'AB');
        $pd_obj->setDate(strtotime('01-Jan-2012'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(12);

        $pd_obj->setFederalTotalClaimAmount(10822);
        $pd_obj->setProvincialTotalClaimAmount(17282);
        $pd_obj->setWCBRate(0.18);

        $pd_obj->setEIExempt(false);
        $pd_obj->setCPPExempt(false);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setYearToDateCPPContribution(0);
        $pd_obj->setYearToDateEIContribution(0);

        $pd_obj->setGrossPayPeriodIncome(1800);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1800');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '104.90');
        $this->assertEquals($this->mf($pd_obj->getProvincialPayPeriodDeductions()), '25.22');
    }

    public function testCA_2012a_Example2()
    {
        Debug::text('CA - Example2 - Beginning of 01-Jan-2012: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'AB');
        $pd_obj->setDate(strtotime('01-Jan-2012'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(26);

        $pd_obj->setFederalTotalClaimAmount(10822);
        $pd_obj->setProvincialTotalClaimAmount(17282);
        $pd_obj->setWCBRate(0.18);

        $pd_obj->setEIExempt(false);
        $pd_obj->setCPPExempt(false);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setYearToDateCPPContribution(0);
        $pd_obj->setYearToDateEIContribution(0);

        $pd_obj->setGrossPayPeriodIncome(2300);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '2300');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '304.13');
        $this->assertEquals($this->mf($pd_obj->getProvincialPayPeriodDeductions()), '151.43');
    }

    public function testCA_2012a_Example3()
    {
        Debug::text('CA - Example3 - Beginning of 01-Jan-2012: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'AB');
        $pd_obj->setDate(strtotime('01-Jan-2012'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(52);

        $pd_obj->setFederalTotalClaimAmount(10822);
        $pd_obj->setProvincialTotalClaimAmount(17282);
        $pd_obj->setWCBRate(0.18);

        $pd_obj->setEIExempt(false);
        $pd_obj->setCPPExempt(false);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setYearToDateCPPContribution(0);
        $pd_obj->setYearToDateEIContribution(0);

        $pd_obj->setGrossPayPeriodIncome(2500);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '2500');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '483.35');
        $this->assertEquals($this->mf($pd_obj->getProvincialPayPeriodDeductions()), '210.71');
    }

    //
    // CPP/ EI
    //
    public function testCA_2012a_BiWeekly_CPP_LowIncome()
    {
        Debug::text('CA - BiWeekly - CPP - Beginning of 01-Jan-2012: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'BC');
        $pd_obj->setDate(strtotime('01-Jan-2012'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(26);

        $pd_obj->setFederalTotalClaimAmount(10100);
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

    public function testCA_2012a_SemiMonthly_CPP_LowIncome()
    {
        Debug::text('CA - BiWeekly - CPP - Beginning of 01-Jan-2012: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'BC');
        $pd_obj->setDate(strtotime('01-Jan-2012'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(24);

        $pd_obj->setFederalTotalClaimAmount(10100);
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

    public function testCA_2012a_EI_LowIncome()
    {
        Debug::text('CA - EI - Beginning of 01-Jan-2012: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'BC');
        $pd_obj->setDate(strtotime('01-Jan-2012'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(26);

        $pd_obj->setFederalTotalClaimAmount(10100);
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
        $this->assertEquals($this->mf($pd_obj->getEmployeeEI()), '10.76');
        $this->assertEquals($this->mf($pd_obj->getEmployerEI()), '15.06');
    }

    public function testCA_2012a_Example4()
    {
        Debug::text('CA - Example1 - Beginning of 01-Jan-2012: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('CA', 'BC');
        $pd_obj->setDate(strtotime('01-Jan-2012'));
        $pd_obj->setEnableCPPAndEIDeduction(true); //Deduct CPP/EI.
        $pd_obj->setAnnualPayPeriods(26);

        $pd_obj->setFederalTotalClaimAmount(10822);
        $pd_obj->setProvincialTotalClaimAmount(11354);
        $pd_obj->setWCBRate(0);

        $pd_obj->setEIExempt(false);
        $pd_obj->setCPPExempt(false);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setYearToDateCPPContribution(0);
        $pd_obj->setYearToDateEIContribution(0);

        $pd_obj->setGrossPayPeriodIncome(1560);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1560');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '150.38');
        $this->assertEquals($this->mf($pd_obj->getProvincialPayPeriodDeductions()), '55.43');
    }
}
