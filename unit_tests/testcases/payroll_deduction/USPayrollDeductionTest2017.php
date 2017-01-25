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
 * @group USPayrollDeductionTest2017
 */
class USPayrollDeductionTest2017 extends PHPUnit_Framework_TestCase
{
    public $company_id = null;

    public function setUp()
    {
        Debug::text('Running setUp(): ', __FILE__, __LINE__, __METHOD__, 10);

        require_once(Environment::getBasePath() . '/classes/payroll_deduction/PayrollDeduction.class.php');

        $this->tax_table_file = dirname(__FILE__) . '/USPayrollDeductionTest2017.csv';

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
            if ($row['gross_income'] == '' and isset($row['low_income']) and $row['low_income'] != '' and isset($row['high_income']) and $row['high_income'] != '') {
                $row['gross_income'] = ($row['low_income'] + (($row['high_income'] - $row['low_income']) / 2));
            }
            if ($row['country'] != '' and $row['gross_income'] != '') {
                //echo $i.'/'.$total_rows.'. Testing Province: '. $row['province'] .' Income: '. $row['gross_income'] ."\n";

                $pd_obj = new PayrollDeduction($row['country'], $row['province']);
                $pd_obj->setDate(strtotime($row['date']));
                $pd_obj->setAnnualPayPeriods($row['pay_periods']);

                $pd_obj->setFederalFilingStatus($row['filing_status']);
                $pd_obj->setFederalAllowance($row['allowance']);

                $pd_obj->setStateFilingStatus($row['filing_status']);
                $pd_obj->setStateAllowance($row['allowance']);

                //Some states use other values for allowance/deductions.
                switch ($row['province']) {
                    case 'GA':
                        Debug::text('Setting UserValue3: ' . $row['allowance'], __FILE__, __LINE__, __METHOD__, 10);
                        $pd_obj->setUserValue3($row['allowance']);
                        break;
                    case 'IN':
                    case 'IL':
                    case 'VA':
                        Debug::text('Setting UserValue1: ' . $row['allowance'], __FILE__, __LINE__, __METHOD__, 10);
                        $pd_obj->setUserValue1($row['allowance']);
                        break;
                }

                $pd_obj->setFederalTaxExempt(false);
                $pd_obj->setProvincialTaxExempt(false);

                $pd_obj->setGrossPayPeriodIncome($this->mf($row['gross_income']));

                //var_dump($pd_obj->getArray());

                $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), $this->mf($row['gross_income']));
                if ($row['federal_deduction'] != '') {
                    $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), $this->MatchWithinMarginOfError($this->mf($row['federal_deduction']), $this->mf($pd_obj->getFederalPayPeriodDeductions()), 0.01));
                }
                if ($row['provincial_deduction'] != '') {
                    $this->assertEquals($this->mf($pd_obj->getStatePayPeriodDeductions()), $this->mf($row['provincial_deduction']));
                }
            }

            $i++;
        }

        //Make sure all rows are tested.
        $this->assertEquals($total_rows, ($i - 1));
    }

    public function mf($amount)
    {
        return Misc::MoneyFormat($amount, false);
    }

    //
    // January 2017
    //

    public function MatchWithinMarginOfError($source, $destination, $error = 0)
    {
        //Source: 125.01
        //Destination: 125.00
        //Source: 124.99
        $high_water_mark = bcadd($destination, $error);
        $low_water_mark = bcsub($destination, $error);

        if ($source <= $high_water_mark and $source >= $low_water_mark) {
            return $destination;
        }

        return $source;
    }

    public function testCompareWithLastYearCSVFile()
    {
        $this->assertEquals(file_exists($this->tax_table_file), true);

        $test_rows = Misc::parseCSV($this->tax_table_file, true);

        $total_rows = (count($test_rows) + 1);
        $i = 2;
        foreach ($test_rows as $row) {
            //Debug::text('Province: '. $row['province'] .' Income: '. $row['gross_income'], __FILE__, __LINE__, __METHOD__, 10);
            if ($row['gross_income'] == '' and isset($row['low_income']) and $row['low_income'] != '' and isset($row['high_income']) and $row['high_income'] != '') {
                $row['gross_income'] = ($row['low_income'] + (($row['high_income'] - $row['low_income']) / 2));
            }
            if ($row['country'] != '' and $row['gross_income'] != '') {
                //echo $i.'/'.$total_rows.'. Testing Province: '. $row['province'] .' Income: '. $row['gross_income'] ."\n";

                $pd_obj = new PayrollDeduction($row['country'], $row['province']);
                $pd_obj->setDate(strtotime('-1 year', strtotime($row['date']))); //Get the same date only last year.
                $pd_obj->setAnnualPayPeriods($row['pay_periods']);

                $pd_obj->setFederalFilingStatus($row['filing_status']);
                $pd_obj->setFederalAllowance($row['allowance']);

                $pd_obj->setStateFilingStatus($row['filing_status']);
                $pd_obj->setStateAllowance($row['allowance']);

                //Some states use other values for allowance/deductions.
                switch ($row['province']) {
                    case 'GA':
                        Debug::text('Setting UserValue3: ' . $row['allowance'], __FILE__, __LINE__, __METHOD__, 10);
                        $pd_obj->setUserValue3($row['allowance']);
                        break;
                    case 'IN':
                    case 'IL':
                    case 'VA':
                        Debug::text('Setting UserValue1: ' . $row['allowance'], __FILE__, __LINE__, __METHOD__, 10);
                        $pd_obj->setUserValue1($row['allowance']);
                        break;
                }

                $pd_obj->setFederalTaxExempt(false);
                $pd_obj->setProvincialTaxExempt(false);

                $pd_obj->setGrossPayPeriodIncome($this->mf($row['gross_income']));

                //var_dump($pd_obj->getArray());

                $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), $this->mf($row['gross_income']));
                if ($row['federal_deduction'] != '') {
                    //$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), $this->MatchWithinMarginOfError( $this->mf( $row['federal_deduction'] ), $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), 0.01 ) );
                    $amount_diff = 0;
                    $amount_diff_percent = 0;
                    if ($row['federal_deduction'] > 0) {
                        $amount_diff = abs(($pd_obj->getFederalPayPeriodDeductions() - $row['federal_deduction']));
                        $amount_diff_percent = (($amount_diff / $row['federal_deduction']) * 100);
                    }

                    //Debug::text($i.'. Amount: This Year: '. $row['federal_deduction'] .' Last Year: '. $pd_obj->getFederalPayPeriodDeductions() .' Diff Amount: '. $amount_diff .' Percent: '. $amount_diff_percent .'%', __FILE__, __LINE__, __METHOD__, 10);
                    if ($amount_diff > 1.5) {
                        $this->assertLessThan(3, $amount_diff_percent); //Should be slightly higher than inflation.
                        $this->assertGreaterThan(0, $amount_diff_percent);
                    }
                }
                if ($row['provincial_deduction'] != '') {
                    //$this->assertEquals( $this->mf( $pd_obj->getStatePayPeriodDeductions() ), $this->mf( $row['provincial_deduction'] ) );
                    $amount_diff = 0;
                    $amount_diff_percent = 0;
                    if ($row['provincial_deduction'] > 0 and $pd_obj->getStatePayPeriodDeductions() > 0) {
                        $amount_diff = abs(($pd_obj->getStatePayPeriodDeductions() - $row['provincial_deduction']));
                        $amount_diff_percent = (($amount_diff / $row['provincial_deduction']) * 100);
                    }

                    Debug::text($i . '. Amount: This Year: ' . $row['provincial_deduction'] . ' Last Year: ' . $pd_obj->getStatePayPeriodDeductions() . ' Diff Amount: ' . $amount_diff . ' Percent: ' . $amount_diff_percent . '%', __FILE__, __LINE__, __METHOD__, 10);
                    if ($row['province'] != 'ME' and $row['province'] != 'NE' and $amount_diff > 5) { //ME had significant changes.
                        $this->assertLessThan(31, $amount_diff_percent); //Reasonable margin of error.
                        $this->assertGreaterThan(0, $amount_diff_percent);
                    }
                }
            }

            $i++;
        }

        //Make sure all rows are tested.
        $this->assertEquals($total_rows, ($i - 1));
    }

    public function testUS_2017a_Test1()
    {
        Debug::text('US - SemiMonthly - Beginning of 2017 01-Jan-2017: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'OR');
        $pd_obj->setDate(strtotime('01-Jan-2017'));
        $pd_obj->setAnnualPayPeriods(26); //Semi-Monthly

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(0);

        $pd_obj->setStateFilingStatus(10); //Single
        $pd_obj->setStateAllowance(0);

        $pd_obj->setYearToDateSocialSecurityContribution(0);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(576.923);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '576.92');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '55.34');
        $this->assertEquals($this->mf($pd_obj->getStatePayPeriodDeductions()), '37.84');
        $this->assertEquals($this->mf($pd_obj->getEmployeeSocialSecurity()), '35.77');
    }

    //
    // US Social Security
    //
    public function testUS_2017a_SocialSecurity()
    {
        Debug::text('US - SemiMonthly - Beginning of 2017 01-Jan-2017: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'MO');
        $pd_obj->setDate(strtotime('01-Jan-2017'));
        $pd_obj->setAnnualPayPeriods(24); //Semi-Monthly

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(0);

        $pd_obj->setYearToDateSocialSecurityContribution(0);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(1000.00);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1000.00');
        $this->assertEquals($this->mf($pd_obj->getEmployeeSocialSecurity()), '62.00');
        $this->assertEquals($this->mf($pd_obj->getEmployerSocialSecurity()), '62.00');
    }

    public function testUS_2017a_SocialSecurity_Max()
    {
        Debug::text('US - SemiMonthly - Beginning of 2017 01-Jan-2017: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'MO');
        $pd_obj->setDate(strtotime('01-Jan-2017'));
        $pd_obj->setAnnualPayPeriods(24); //Semi-Monthly

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(0);


        $pd_obj->setYearToDateSocialSecurityContribution(($pd_obj->getSocialSecurityMaximumContribution() - 1)); //7347

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(1000.00);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1000.00');
        $this->assertEquals($this->mf($pd_obj->getEmployeeSocialSecurity()), '1.00');
        $this->assertEquals($this->mf($pd_obj->getEmployerSocialSecurity()), '1.00');
    }

    public function testUS_2017a_Medicare()
    {
        Debug::text('US - SemiMonthly - Beginning of 2017 01-Jan-2017: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'MO');
        $pd_obj->setDate(strtotime('01-Jan-2017'));
        $pd_obj->setAnnualPayPeriods(24); //Semi-Monthly

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(0);

        $pd_obj->setYearToDateSocialSecurityContribution(0);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(1000.00);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1000.00');
        $this->assertEquals($this->mf($pd_obj->getEmployeeMedicare()), '14.50');
        $this->assertEquals($this->mf($pd_obj->getEmployerMedicare()), '14.50');
    }

    public function testUS_2017a_Additional_MedicareA()
    {
        Debug::text('US - SemiMonthly - Beginning of 2017 01-Jan-2017: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'MO');
        $pd_obj->setDate(strtotime('01-Jan-2017'));
        $pd_obj->setAnnualPayPeriods(24); //Semi-Monthly

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(0);

        $pd_obj->setYearToDateSocialSecurityContribution(0);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setMedicareFilingStatus(10); //Single
        $pd_obj->setYearToDateGrossIncome(199000.00);
        $pd_obj->setGrossPayPeriodIncome(1000.00);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1000.00');
        $this->assertEquals($this->mf($pd_obj->getEmployeeMedicare()), '14.50');
        $this->assertEquals($this->mf($pd_obj->getEmployerMedicare()), '14.50');
    }

    public function testUS_2017a_Additional_MedicareB()
    {
        Debug::text('US - SemiMonthly - Beginning of 2017 01-Jan-2017: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'MO');
        $pd_obj->setDate(strtotime('01-Jan-2017'));
        $pd_obj->setAnnualPayPeriods(24); //Semi-Monthly

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(0);

        $pd_obj->setYearToDateSocialSecurityContribution(0);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setMedicareFilingStatus(10); //Single
        $pd_obj->setYearToDateGrossIncome(199500.00);
        $pd_obj->setGrossPayPeriodIncome(1000.00);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1000.00');
        $this->assertEquals($this->mf($pd_obj->getEmployeeMedicare()), '19.00');
        $this->assertEquals($this->mf($pd_obj->getEmployerMedicare()), '14.50');
    }

    public function testUS_2017a_Additional_MedicareC()
    {
        Debug::text('US - SemiMonthly - Beginning of 2017 01-Jan-2017: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'MO');
        $pd_obj->setDate(strtotime('01-Jan-2017'));
        $pd_obj->setAnnualPayPeriods(24); //Semi-Monthly

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(0);

        $pd_obj->setYearToDateSocialSecurityContribution(0);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setMedicareFilingStatus(10); //Single
        $pd_obj->setYearToDateGrossIncome(500000.00);
        $pd_obj->setGrossPayPeriodIncome(1000.00);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1000.00');
        $this->assertEquals($this->mf($pd_obj->getEmployeeMedicare()), '23.50');
        $this->assertEquals($this->mf($pd_obj->getEmployerMedicare()), '14.50');
    }

    public function testUS_2017a_Additional_MedicareD()
    {
        Debug::text('US - SemiMonthly - Beginning of 2017 01-Jan-2017: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'MO');
        $pd_obj->setDate(strtotime('01-Jan-2017'));
        $pd_obj->setAnnualPayPeriods(24); //Semi-Monthly

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(0);

        $pd_obj->setYearToDateSocialSecurityContribution(0);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setMedicareFilingStatus(10); //Single
        $pd_obj->setYearToDateGrossIncome(0);
        $pd_obj->setGrossPayPeriodIncome(500000.00);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '500000.00');
        $this->assertEquals($this->mf($pd_obj->getEmployeeMedicare()), '9950.00');
        $this->assertEquals($this->mf($pd_obj->getEmployerMedicare()), '7250.00');
    }

    public function testUS_2017a_FederalUI_NoState()
    {
        Debug::text('US - SemiMonthly - Beginning of 2017 01-Jan-2017: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'MO');
        $pd_obj->setDate(strtotime('01-Jan-2017'));
        $pd_obj->setAnnualPayPeriods(24); //Semi-Monthly

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(0);

        $pd_obj->setYearToDateSocialSecurityContribution(0);
        $pd_obj->setYearToDateFederalUIContribution(0);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(1000.00);

        //var_dump($pd_obj->getArray());

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1000.00');
        $this->assertEquals($this->mf($pd_obj->getFederalEmployerUI()), '60.00');
    }

    public function testUS_2017a_FederalUI_NoState_Max()
    {
        Debug::text('US - SemiMonthly - Beginning of 2017 01-Jan-2017: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'MO');
        $pd_obj->setDate(strtotime('01-Jan-2017'));
        $pd_obj->setAnnualPayPeriods(24); //Semi-Monthly

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(0);

        $pd_obj->setStateUIRate(0);
        $pd_obj->setStateUIWageBase(0);

        $pd_obj->setYearToDateSocialSecurityContribution(0);
        $pd_obj->setYearToDateFederalUIContribution(419); //420
        $pd_obj->setYearToDateStateUIContribution(0);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(1000.00);

        //var_dump($pd_obj->getArray());

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1000.00');
        $this->assertEquals($this->mf($pd_obj->getFederalEmployerUI()), '1.00');
    }

    public function testUS_2017a_FederalUI_State_Max()
    {
        Debug::text('US - SemiMonthly - Beginning of 2017 01-Jan-2017: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'MO');
        $pd_obj->setDate(strtotime('01-Jan-2017'));
        $pd_obj->setAnnualPayPeriods(24); //Semi-Monthly

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(0);

        $pd_obj->setStateUIRate(3.51);
        $pd_obj->setStateUIWageBase(11000);

        $pd_obj->setYearToDateSocialSecurityContribution(0);
        $pd_obj->setYearToDateFederalUIContribution(173.30); //174.30
        $pd_obj->setYearToDateStateUIContribution(0);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(1000.00);

        //var_dump($pd_obj->getArray());

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1000.00');
        $this->assertEquals($this->mf($pd_obj->getFederalEmployerUI()), '1.00');
    }
}
