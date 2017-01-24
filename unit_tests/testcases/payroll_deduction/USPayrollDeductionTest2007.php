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
 * @group USPayrollDeductionTest2007
 */
class USPayrollDeductionTest2007 extends PHPUnit_Framework_TestCase
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

    public function testUS_2007a_BiWeekly_Single_LowIncome()
    {
        Debug::text('US - BiWeekly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'MO');
        $pd_obj->setDate(strtotime('01-Jan-07'));
        $pd_obj->setAnnualPayPeriods(26); //Bi-Weekly

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(1);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(1000.00);

        //var_dump($pd_obj->getArray());

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1000.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '100.73'); //100.73
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

    public function testUS_2007a_BiWeekly_Married_LowIncome()
    {
        Debug::text('US - BiWeekly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'MO');
        $pd_obj->setDate(strtotime('01-Jan-07'));
        $pd_obj->setAnnualPayPeriods(26); //Bi-Weekly

        $pd_obj->setFederalFilingStatus(20); //Married
        $pd_obj->setFederalAllowance(1);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(1000.00);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1000.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '56.15'); //56.15
    }


    public function testUS_2007a_BiWeekly_Married_LowIncomeB()
    {
        Debug::text('US - BiWeekly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'MO');
        $pd_obj->setDate(strtotime('01-Jan-07'));
        $pd_obj->setAnnualPayPeriods(26); //Bi-Weekly

        $pd_obj->setFederalFilingStatus(20); //Married
        $pd_obj->setFederalAllowance(3);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(1000.00);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1000.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '30.00');
    }

    public function testUS_2007a_SemiMonthly_Single_LowIncome()
    {
        Debug::text('US - SemiMonthly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'MO');
        $pd_obj->setDate(strtotime('01-Jan-07'));
        $pd_obj->setAnnualPayPeriods(24); //Semi-Monthly

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(1);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(1000.00);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1000.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '96.63'); //96.63
    }

    public function testUS_2007a_SemiMonthly_Married_LowIncome()
    {
        Debug::text('US - SemiMonthly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'MO');
        $pd_obj->setDate(strtotime('01-Jan-07'));
        $pd_obj->setAnnualPayPeriods(24); //Semi-Monthly

        $pd_obj->setFederalFilingStatus(20); //Married
        $pd_obj->setFederalAllowance(1);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(1000.00);

        //var_dump($pd_obj->getArray());

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1000.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '52.50'); //52.50
    }

    public function testUS_2007a_SemiMonthly_Single_MedIncome()
    {
        Debug::text('US - SemiMonthly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'MO');
        $pd_obj->setDate(strtotime('01-Jan-07'));
        $pd_obj->setAnnualPayPeriods(24); //Semi-Monthly

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(1);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(2000.00);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '2000.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '292.79'); //292.72
    }

    public function testUS_2007a_SemiMonthly_Single_HighIncome()
    {
        Debug::text('US - SemiMonthly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'MO');
        $pd_obj->setDate(strtotime('01-Jan-07'));
        $pd_obj->setAnnualPayPeriods(24); //Semi-Monthly

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(1);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(4000.00);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '4000.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '812.20'); //812.20
    }

    public function testUS_2007a_SemiMonthly_Single_LowIncome_3Allowances()
    {
        Debug::text('US - SemiMonthly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'MO');
        $pd_obj->setDate(strtotime('01-Jan-07'));
        $pd_obj->setAnnualPayPeriods(24); //Semi-Monthly

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(3);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(1000.00);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1000.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '54.13'); //54.13
    }

    public function testUS_2007a_SemiMonthly_Single_LowIncome_5Allowances()
    {
        Debug::text('US - SemiMonthly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'MO');
        $pd_obj->setDate(strtotime('01-Jan-07'));
        $pd_obj->setAnnualPayPeriods(24); //Semi-Monthly

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(5);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(1000.00);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1000.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '18.13'); //18.13
    }

    public function testUS_2007a_SemiMonthly_Single_LowIncome_8AllowancesA()
    {
        Debug::text('US - SemiMonthly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'MO');
        $pd_obj->setDate(strtotime('01-Jan-07'));
        $pd_obj->setAnnualPayPeriods(24); //Semi-Monthly

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(8);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(1000.00);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1000.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '0.00'); //0.00
    }

    public function testUS_2007a_SemiMonthly_Single_LowIncome_8AllowancesB()
    {
        Debug::text('US - SemiMonthly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'MO');
        $pd_obj->setDate(strtotime('01-Jan-07'));
        $pd_obj->setAnnualPayPeriods(24); //Semi-Monthly

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(8);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(1300.00);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1300.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '5.63'); //5.63
    }

    //
    // OK
    //
    public function testOK_2007a_BiWeekly_Single_LowIncome()
    {
        Debug::text('US - BiWeekly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'OK');
        $pd_obj->setDate(strtotime('01-Jan-07'));
        $pd_obj->setAnnualPayPeriods(26); //Bi-Weekly

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(1);

        $pd_obj->setStateFilingStatus(10); //Single
        $pd_obj->setStateAllowance(0);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(1000.00);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1000.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '100.73'); //100.73
        $this->assertEquals($this->mf($pd_obj->getStatePayPeriodDeductions()), '41.00'); //41.00
    }

    public function testOK_2007a_BiWeekly_Married_LowIncome()
    {
        Debug::text('US - BiWeekly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'OK');
        $pd_obj->setDate(strtotime('01-Jan-07'));
        $pd_obj->setAnnualPayPeriods(26); //Bi-Weekly

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(1);

        $pd_obj->setStateFilingStatus(20); //Married
        $pd_obj->setStateAllowance(1);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(1000.00);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1000.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '100.73'); //100.73
        $this->assertEquals($this->mf($pd_obj->getStatePayPeriodDeductions()), '25.00'); //25.00
    }

    public function testOK_2007a_SemiMonthly_Married_HighIncome_8Allowances()
    {
        Debug::text('US - SemiMonthly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'OK');
        $pd_obj->setDate(strtotime('01-Jan-07'));
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
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '812.20'); //812.20
        $this->assertEquals($this->mf($pd_obj->getStatePayPeriodDeductions()), '175.00'); //175.00
    }

    //
    // NM
    //
    public function testNM_2007a_BiWeekly_Single_LowIncome()
    {
        Debug::text('US - BiWeekly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'NM');
        $pd_obj->setDate(strtotime('01-Jan-07'));
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
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '100.73'); //100.73
        $this->assertEquals($this->mf($pd_obj->getStatePayPeriodDeductions()), '35.92'); //35.92
    }

    public function testNM_2007a_BiWeekly_Married_LowIncome()
    {
        Debug::text('US - BiWeekly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'NM');
        $pd_obj->setDate(strtotime('01-Jan-07'));
        $pd_obj->setAnnualPayPeriods(26); //Bi-Weekly

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(1);

        $pd_obj->setStateFilingStatus(20); //Married
        $pd_obj->setStateAllowance(1);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(1000.00);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1000.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '100.73'); //100.73
        $this->assertEquals($this->mf($pd_obj->getStatePayPeriodDeductions()), '14.22'); //14.22
    }

    public function testNM_2007a_SemiMonthly_Married_HighIncome_8Allowances()
    {
        Debug::text('US - SemiMonthly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'NM');
        $pd_obj->setDate(strtotime('01-Jan-07'));
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
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '812.20'); //812.20
        $this->assertEquals($this->mf($pd_obj->getStatePayPeriodDeductions()), '114.04'); //114.04
    }

    //
    // NE
    //
    /*
        function testNE_2007a_BiWeekly_Single_LowIncome() {
            Debug::text('US - BiWeekly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__,10);
    
            $pd_obj = new PayrollDeduction('US','NE');
            $pd_obj->setDate(strtotime('01-Jan-07'));
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
            $this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '100.73' ); //100.73
            $this->assertEquals( $this->mf( $pd_obj->getStatePayPeriodDeductions() ), '38.97' ); //38.97
        }
    
        function testNE_2007a_BiWeekly_Married_LowIncome() {
            Debug::text('US - BiWeekly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__,10);
    
            $pd_obj = new PayrollDeduction('US','NE');
            $pd_obj->setDate(strtotime('01-Jan-07'));
            $pd_obj->setAnnualPayPeriods( 26 ); //Bi-Weekly
    
            $pd_obj->setFederalFilingStatus( 10 ); //Single
            $pd_obj->setFederalAllowance( 1 );
    
            $pd_obj->setStateFilingStatus( 20 ); //Married
            $pd_obj->setStateAllowance( 1 );
    
            $pd_obj->setFederalTaxExempt( FALSE );
            $pd_obj->setProvincialTaxExempt( FALSE );
    
            $pd_obj->setGrossPayPeriodIncome( 1000.00 );
    
            $this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '1000.00' );
            $this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '100.73' ); //100.73
            $this->assertEquals( $this->mf( $pd_obj->getStatePayPeriodDeductions() ), '25.33' ); //25.33
        }
    
        function testNE_2007a_SemiMonthly_Married_HighIncome_8Allowances() {
            Debug::text('US - SemiMonthly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__,10);
    
            $pd_obj = new PayrollDeduction('US','NE');
            $pd_obj->setDate(strtotime('01-Jan-07'));
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
            $this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '812.20' ); //812.20
            $this->assertEquals( $this->mf( $pd_obj->getStatePayPeriodDeductions() ), '189.98' ); //189.98
        }
    */
    //
    // MN
    //
    public function testMN_2007a_BiWeekly_Single_LowIncome()
    {
        Debug::text('US - BiWeekly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'MN');
        $pd_obj->setDate(strtotime('01-Jan-07'));
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
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '100.73'); //100.73
        $this->assertEquals($this->mf($pd_obj->getStatePayPeriodDeductions()), '51.28'); //51.28
    }

    public function testMN_2007a_BiWeekly_Married_LowIncome()
    {
        Debug::text('US - BiWeekly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'MN');
        $pd_obj->setDate(strtotime('01-Jan-07'));
        $pd_obj->setAnnualPayPeriods(26); //Bi-Weekly

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(1);

        $pd_obj->setStateFilingStatus(20); //Married
        $pd_obj->setStateAllowance(1);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(1000.00);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1000.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '100.73'); //100.73
        $this->assertEquals($this->mf($pd_obj->getStatePayPeriodDeductions()), '31.48'); //31.48
    }

    public function testMN_2007a_SemiMonthly_Married_HighIncome_8Allowances()
    {
        Debug::text('US - SemiMonthly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'MN');
        $pd_obj->setDate(strtotime('01-Jan-07'));
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
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '812.20'); //812.20
        $this->assertEquals($this->mf($pd_obj->getStatePayPeriodDeductions()), '158.59'); //158.59
    }

    //
    // HI
    //
    public function testHI_2007a_BiWeekly_Single_LowIncome()
    {
        Debug::text('US - BiWeekly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'HI');
        $pd_obj->setDate(strtotime('01-Jan-07'));
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
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '100.73'); //100.73
        $this->assertEquals($this->mf($pd_obj->getStatePayPeriodDeductions()), '57.92'); //57.92
    }

    public function testHI_2007a_BiWeekly_Married_LowIncome()
    {
        Debug::text('US - BiWeekly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'HI');
        $pd_obj->setDate(strtotime('01-Jan-07'));
        $pd_obj->setAnnualPayPeriods(26); //Bi-Weekly

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(1);

        $pd_obj->setStateFilingStatus(20); //Married
        $pd_obj->setStateAllowance(1);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(1000.00);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1000.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '100.73'); //100.73
        $this->assertEquals($this->mf($pd_obj->getStatePayPeriodDeductions()), '42.99'); //42.99
    }

    public function testHI_2007a_SemiMonthly_Married_HighIncome_8Allowances()
    {
        Debug::text('US - SemiMonthly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'HI');
        $pd_obj->setDate(strtotime('01-Jan-07'));
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
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '812.20'); //812.20
        $this->assertEquals($this->mf($pd_obj->getStatePayPeriodDeductions()), '238.45'); //238.45
    }

    //
    // CO
    //
    public function testCO_2007a_BiWeekly_Single_LowIncome()
    {
        Debug::text('US - BiWeekly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'CO');
        $pd_obj->setDate(strtotime('01-Jan-07'));
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
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '100.73'); //100.73
        $this->assertEquals($this->mf($pd_obj->getStatePayPeriodDeductions()), '43.00'); //42.92
    }

    public function testCO_2007a_BiWeekly_Married_LowIncome()
    {
        Debug::text('US - BiWeekly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'CO');
        $pd_obj->setDate(strtotime('01-Jan-07'));
        $pd_obj->setAnnualPayPeriods(26); //Bi-Weekly

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(1);

        $pd_obj->setStateFilingStatus(20); //Married
        $pd_obj->setStateAllowance(1);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(1000.00);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1000.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '100.73'); //100.73
        $this->assertEquals($this->mf($pd_obj->getStatePayPeriodDeductions()), '27.00'); //27.42
    }

    public function testCO_2007a_SemiMonthly_Married_HighIncome_8Allowances()
    {
        Debug::text('US - SemiMonthly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'CO');
        $pd_obj->setDate(strtotime('01-Jan-07'));
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
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '812.20'); //812.20
        $this->assertEquals($this->mf($pd_obj->getStatePayPeriodDeductions()), '119.00'); //118.84
    }

    //
    // MI
    //
    public function testMI_2007a_BiWeekly_LowIncome()
    {
        Debug::text('US - BiWeekly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'MI');
        $pd_obj->setDate(strtotime('01-Jan-07'));
        $pd_obj->setAnnualPayPeriods(26); //Bi-Weekly

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(1);

        $pd_obj->setStateAllowance(1);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(1000.00);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1000.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '100.73'); //100.73
        $this->assertEquals($this->mf($pd_obj->getStatePayPeriodDeductions()), '33.90'); //33.90
    }

    public function testMI_2007a_BiWeekly_LowIncomeB()
    {
        Debug::text('US - BiWeekly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'MI');
        $pd_obj->setDate(strtotime('01-Jan-07'));
        $pd_obj->setAnnualPayPeriods(26); //Bi-Weekly

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(1);

        $pd_obj->setStateAllowance(3);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(1000.00);

        //var_dump($pd_obj->getArray());

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1000.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '100.73'); //100.73
        $this->assertEquals($this->mf($pd_obj->getStatePayPeriodDeductions()), '23.70'); //23.70
    }

    //
    // CA
    //
    public function testCA_2007a_BiWeekly_Single_LowIncome()
    {
        Debug::text('US - BiWeekly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'CA');
        $pd_obj->setDate(strtotime('01-Jan-07'));
        $pd_obj->setAnnualPayPeriods(26); //Bi-Weekly

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(1);

        $pd_obj->setStateFilingStatus(10); //Single
        $pd_obj->setStateAllowance(1);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(1000.00);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1000.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '100.73'); //100.73
        $this->assertEquals($this->mf($pd_obj->getStatePayPeriodDeductions()), '16.63'); //16.63
    }

    public function testCA_2007a_BiWeekly_Married_LowIncome()
    {
        Debug::text('US - BiWeekly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'CA');
        $pd_obj->setDate(strtotime('01-Jan-07'));
        $pd_obj->setAnnualPayPeriods(26); //Bi-Weekly

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(1);

        $pd_obj->setStateFilingStatus(30); //Married, one person working
        $pd_obj->setStateAllowance(1);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(1000.00);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1000.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '100.73'); //100.73
        $this->assertEquals($this->mf($pd_obj->getStatePayPeriodDeductions()), '8.78'); //8.78
    }

    public function testCA_2007a_SemiMonthly_Married_HighIncome_8Allowances()
    {
        Debug::text('US - SemiMonthly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'CA');
        $pd_obj->setDate(strtotime('01-Jan-07'));
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
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '812.20'); //812.20
        $this->assertEquals($this->mf($pd_obj->getStatePayPeriodDeductions()), '137.85'); //137.85
    }

    //
    // KY
    //
    public function testKY_2007a_BiWeekly_LowIncome()
    {
        Debug::text('US - BiWeekly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'KY');
        $pd_obj->setDate(strtotime('01-Jan-07'));
        $pd_obj->setAnnualPayPeriods(26); //Bi-Weekly

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(1);

        $pd_obj->setStateAllowance(0);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(346.00);

        //var_dump($pd_obj->getArray());

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '346.00');
        $this->assertEquals($this->mf($pd_obj->getStatePayPeriodDeductions()), '8.74'); //8.74
    }

    public function testKY_2007a_BiWeekly_LowIncomeB()
    {
        Debug::text('US - BiWeekly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'KY');
        $pd_obj->setDate(strtotime('01-Jan-07'));
        $pd_obj->setAnnualPayPeriods(26); //Bi-Weekly

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(1);

        $pd_obj->setStateAllowance(0);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(1000.00);

        //var_dump($pd_obj->getArray());

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1000.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '100.73'); //100.73
        $this->assertEquals($this->mf($pd_obj->getStatePayPeriodDeductions()), '46.35'); //46.35
    }

    public function testKY_2007a_SemiMonthly_HighIncome()
    {
        Debug::text('US - BiWeekly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'KY');
        $pd_obj->setDate(strtotime('01-Jan-07'));
        $pd_obj->setAnnualPayPeriods(24);

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(1);

        $pd_obj->setStateAllowance(1);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(4000.00);

        //var_dump($pd_obj->getArray());

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '4000.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '812.20'); //812.20
        $this->assertEquals($this->mf($pd_obj->getStatePayPeriodDeductions()), '220.13'); //220.13
    }

    //
    // MO
    //
    public function testMO_2007a_BiWeekly_Single_LowIncome()
    {
        Debug::text('US - BiWeekly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'MO');
        $pd_obj->setDate(strtotime('01-Jan-07'));
        $pd_obj->setAnnualPayPeriods(26); //Bi-Weekly

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(1);

        $pd_obj->setStateFilingStatus(10); //Single
        $pd_obj->setStateAllowance(1);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(1000.00);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1000.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '100.73'); //100.73
        $this->assertEquals($this->mf($pd_obj->getStatePayPeriodDeductions()), '30.00'); //30.00
    }

    public function testMO_2007a_BiWeekly_Married_LowIncome()
    {
        Debug::text('US - BiWeekly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'MO');
        $pd_obj->setDate(strtotime('01-Jan-07'));
        $pd_obj->setAnnualPayPeriods(26); //Bi-Weekly

        $pd_obj->setFederalFilingStatus(20); //Married
        $pd_obj->setFederalAllowance(1);

        $pd_obj->setStateFilingStatus(20); //Married
        $pd_obj->setStateAllowance(1);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(1000.00);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1000.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '56.15'); //56.15
        $this->assertEquals($this->mf($pd_obj->getStatePayPeriodDeductions()), '33.00'); //33.00
    }

    public function testMO_2007a_SemiMonthly_Married_HighIncome()
    {
        Debug::text('US - SemiMonthly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'MO');
        $pd_obj->setDate(strtotime('01-Jan-07'));
        $pd_obj->setAnnualPayPeriods(24); //Semi-Monthly

        $pd_obj->setFederalFilingStatus(20); //Married
        $pd_obj->setFederalAllowance(1);

        $pd_obj->setStateFilingStatus(20); //Married
        $pd_obj->setStateAllowance(1);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(4000.00);

        //var_dump($pd_obj->getArray());

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '4000.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '588.02'); //588.02
        $this->assertEquals($this->mf($pd_obj->getStatePayPeriodDeductions()), '202.00'); //202.00
    }

    //
    // NC
    //
    public function testNC_2007a_BiWeekly_Single_LowIncome()
    {
        Debug::text('US - BiWeekly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'NC');
        $pd_obj->setDate(strtotime('01-Jan-07'));
        $pd_obj->setAnnualPayPeriods(26); //Bi-Weekly

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(1);

        $pd_obj->setStateFilingStatus(10); //Single
        $pd_obj->setStateAllowance(1);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(1000.00);

        //var_dump($pd_obj->getArray());

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1000.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '100.73'); //100.73
        $this->assertEquals($this->mf($pd_obj->getStatePayPeriodDeductions()), '50.00'); //50.00
    }

    public function testNC_2007a_BiWeekly_Married_LowIncome()
    {
        Debug::text('US - BiWeekly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'NC');
        $pd_obj->setDate(strtotime('01-Jan-07'));
        $pd_obj->setAnnualPayPeriods(26); //Bi-Weekly

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(1);

        $pd_obj->setStateFilingStatus(20); //Married
        $pd_obj->setStateAllowance(1);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(1000.00);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1000.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '100.73'); //100.73
        $this->assertEquals($this->mf($pd_obj->getStatePayPeriodDeductions()), '51.00'); //51.00
    }

    public function testNC_2007a_SemiMonthly_Married_HighIncome_8Allowances()
    {
        Debug::text('US - SemiMonthly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'NC');
        $pd_obj->setDate(strtotime('01-Jan-07'));
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
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '812.20'); //812.20
        $this->assertEquals($this->mf($pd_obj->getStatePayPeriodDeductions()), '229.00'); //229.00
    }

    //
    // ND
    //
    public function testND_2007a_BiWeekly_Single_LowIncome()
    {
        Debug::text('US - BiWeekly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'ND');
        $pd_obj->setDate(strtotime('01-Jan-07'));
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
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '100.73'); //100.73
        $this->assertEquals($this->mf($pd_obj->getStatePayPeriodDeductions()), '18.09'); //18.09
    }

    public function testND_2007a_BiWeekly_Married_LowIncome()
    {
        Debug::text('US - BiWeekly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'ND');
        $pd_obj->setDate(strtotime('01-Jan-07'));
        $pd_obj->setAnnualPayPeriods(26); //Bi-Weekly

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(1);

        $pd_obj->setStateFilingStatus(20); //Married
        $pd_obj->setStateAllowance(1);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(1000.00);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1000.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '100.73'); //100.73
        $this->assertEquals($this->mf($pd_obj->getStatePayPeriodDeductions()), '11.15'); //11.15
    }

    public function testND_2007a_SemiMonthly_Married_HighIncome_8Allowances()
    {
        Debug::text('US - SemiMonthly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'ND');
        $pd_obj->setDate(strtotime('01-Jan-07'));
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
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '812.20'); //812.20
        $this->assertEquals($this->mf($pd_obj->getStatePayPeriodDeductions()), '59.02'); //59.02
    }

    //
    // OR
    //
    public function testOR_2007a_BiWeekly_Single_LowIncome()
    {
        Debug::text('US - BiWeekly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'OR');
        $pd_obj->setDate(strtotime('01-Jan-07'));
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
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '100.73'); //100.73
        $this->assertEquals($this->mf($pd_obj->getStatePayPeriodDeductions()), '66.79'); //66.77
    }

    public function testOR_2007a_BiWeekly_Single_LowIncomeB()
    {
        Debug::text('US - BiWeekly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'OR');
        $pd_obj->setDate(strtotime('01-Jan-07'));
        $pd_obj->setAnnualPayPeriods(26); //Bi-Weekly

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(1);

        $pd_obj->setStateFilingStatus(10); //Single
        $pd_obj->setStateAllowance(2); //3 - Should switch to married tax tables.

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(1000.00);

        //var_dump($pd_obj->getArray());

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1000.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '100.73'); //100.73
        $this->assertEquals($this->mf($pd_obj->getStatePayPeriodDeductions()), '54.10'); //54.08
    }

    public function testOR_2007a_BiWeekly_Single_LowIncomeC()
    {
        Debug::text('US - BiWeekly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'OR');
        $pd_obj->setDate(strtotime('01-Jan-07'));
        $pd_obj->setAnnualPayPeriods(26); //Bi-Weekly

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(1);

        $pd_obj->setStateFilingStatus(10); //Single
        $pd_obj->setStateAllowance(3); //3 - Should switch to married tax tables.

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(1000.00);

        //var_dump($pd_obj->getArray());

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1000.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '100.73'); //100.73
        $this->assertEquals($this->mf($pd_obj->getStatePayPeriodDeductions()), '40.04'); //40.04
    }

    public function testOR_2007a_BiWeekly_Married_LowIncome()
    {
        Debug::text('US - BiWeekly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'OR');
        $pd_obj->setDate(strtotime('01-Jan-07'));
        $pd_obj->setAnnualPayPeriods(26); //Bi-Weekly

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(1);

        $pd_obj->setStateFilingStatus(20); //Married
        $pd_obj->setStateAllowance(1);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(1000.00);

        //var_dump($pd_obj->getArray());

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1000.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '100.73'); //100.73
        $this->assertEquals($this->mf($pd_obj->getStatePayPeriodDeductions()), '46.26'); //46.26
    }

    public function testOR_2007a_SemiMonthly_Married_HighIncome_8Allowances()
    {
        Debug::text('US - SemiMonthly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'OR');
        $pd_obj->setDate(strtotime('01-Jan-07'));
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
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '812.20'); //812.20
        $this->assertEquals($this->mf($pd_obj->getStatePayPeriodDeductions()), '253.68'); //253.68
    }

    //
    // RI
    //
    public function testRI_2007a_BiWeekly_Single_LowIncome()
    {
        Debug::text('US - BiWeekly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'RI');
        $pd_obj->setDate(strtotime('01-Jan-07'));
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
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '100.73'); //100.73
        $this->assertEquals($this->mf($pd_obj->getStatePayPeriodDeductions()), '33.68'); //33.68
    }

    public function testRI_2007a_BiWeekly_Single_LowIncomeB()
    {
        Debug::text('US - BiWeekly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'RI');
        $pd_obj->setDate(strtotime('01-Jan-07'));
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
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '129.37'); //129.37
        $this->assertEquals($this->mf($pd_obj->getStatePayPeriodDeductions()), '30.99'); //30.98
    }

    public function testRI_2007a_BiWeekly_Married_LowIncome()
    {
        Debug::text('US - BiWeekly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'RI');
        $pd_obj->setDate(strtotime('01-Jan-07'));
        $pd_obj->setAnnualPayPeriods(26); //Bi-Weekly

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(1);

        $pd_obj->setStateFilingStatus(20); //Married
        $pd_obj->setStateAllowance(1);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(1000.00);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1000.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '100.73'); //100.73
        $this->assertEquals($this->mf($pd_obj->getStatePayPeriodDeductions()), '23.29'); //23.58
    }

    public function testRI_2007a_SemiMonthly_Married_HighIncome_8Allowances()
    {
        Debug::text('US - SemiMonthly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'RI');
        $pd_obj->setDate(strtotime('01-Jan-07'));
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
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '812.20'); //812.20
        $this->assertEquals($this->mf($pd_obj->getStatePayPeriodDeductions()), '111.10'); //121.11
    }

    //
    // VT
    //
    public function testVT_2007a_BiWeekly_Single_LowIncome()
    {
        Debug::text('US - BiWeekly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'VT');
        $pd_obj->setDate(strtotime('01-Jan-07'));
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
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '100.73'); //100.73
        $this->assertEquals($this->mf($pd_obj->getStatePayPeriodDeductions()), '32.33'); //32.33
    }

    public function testVT_2007a_BiWeekly_Married_LowIncome()
    {
        Debug::text('US - BiWeekly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'VT');
        $pd_obj->setDate(strtotime('01-Jan-07'));
        $pd_obj->setAnnualPayPeriods(26); //Bi-Weekly

        $pd_obj->setFederalFilingStatus(10); //Single
        $pd_obj->setFederalAllowance(1);

        $pd_obj->setStateFilingStatus(20); //Married
        $pd_obj->setStateAllowance(1);

        $pd_obj->setFederalTaxExempt(false);
        $pd_obj->setProvincialTaxExempt(false);

        $pd_obj->setGrossPayPeriodIncome(1000.00);

        $this->assertEquals($this->mf($pd_obj->getGrossPayPeriodIncome()), '1000.00');
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '100.73'); //100.73
        $this->assertEquals($this->mf($pd_obj->getStatePayPeriodDeductions()), '20.22'); //20.22
    }

    public function testVT_2007a_SemiMonthly_Married_HighIncome_8Allowances()
    {
        Debug::text('US - SemiMonthly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

        $pd_obj = new PayrollDeduction('US', 'VT');
        $pd_obj->setDate(strtotime('01-Jan-07'));
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
        $this->assertEquals($this->mf($pd_obj->getFederalPayPeriodDeductions()), '812.20'); //812.20
        $this->assertEquals($this->mf($pd_obj->getStatePayPeriodDeductions()), '106.05'); //106.05
    }

    //
    // AL
    //
    /*
        function testAL_2007a_BiWeekly_Single_LowIncome() {
            Debug::text('US - BiWeekly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__,10);
    
            $pd_obj = new PayrollDeduction('US','AL');
            $pd_obj->setDate(strtotime('01-Jan-07'));
            $pd_obj->setAnnualPayPeriods( 26 ); //Bi-Weekly
    
            $pd_obj->setFederalFilingStatus( 10 ); //Single
            $pd_obj->setFederalAllowance( 0 );
    
            $pd_obj->setStateFilingStatus( 10 ); // State "S"
            $pd_obj->setUserValue2( 1 );
    
            $pd_obj->setFederalTaxExempt( FALSE );
            $pd_obj->setProvincialTaxExempt( FALSE );
    
            $pd_obj->setGrossPayPeriodIncome( 1000 );
    
            //var_dump($pd_obj->getArray());
    
            $this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '1000.00' );
            $this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '120.35' ); //120.35
            $this->assertEquals( $this->mf( $pd_obj->getStatePayPeriodDeductions() ), '35.33' ); //34.37
        }
    
        function testAL_2007a_BiWeekly_Single_MediumIncome() {
            Debug::text('US - BiWeekly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__,10);
    
            $pd_obj = new PayrollDeduction('US','AL');
            $pd_obj->setDate(strtotime('01-Jan-07'));
            $pd_obj->setAnnualPayPeriods( 12 ); //Monthly
            $pd_obj->setFederalFilingStatus( 10 ); //Single
            $pd_obj->setFederalAllowance( 0 );
    
            $pd_obj->setStateFilingStatus( 10 ); //Single
            $pd_obj->setUserValue2( 0 );
    
            $pd_obj->setFederalTaxExempt( FALSE );
            $pd_obj->setProvincialTaxExempt( FALSE );
    
            $pd_obj->setGrossPayPeriodIncome( 2083 );
    
            $this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '2083.00' );
            $this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '248.20' );
            $this->assertEquals( $this->mf( $pd_obj->getStatePayPeriodDeductions() ), '74.87' );
        }
    
        function testAL_2007a_BiWeekly_Married_LowIncome() {
            Debug::text('US - BiWeekly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__,10);
    
            $pd_obj = new PayrollDeduction('US','AL');
            $pd_obj->setDate(strtotime('01-Jan-07'));
            $pd_obj->setAnnualPayPeriods( 26 ); //Bi-Weekly
    
            $pd_obj->setFederalFilingStatus( 10 ); //Single
            $pd_obj->setFederalAllowance( 0 );
    
            $pd_obj->setStateFilingStatus( 20 ); //Married
            $pd_obj->setUserValue2( 1 );
    
            $pd_obj->setFederalTaxExempt( FALSE );
            $pd_obj->setProvincialTaxExempt( FALSE );
    
            $pd_obj->setGrossPayPeriodIncome( 1000.00 );
    
            $this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '1000.00' );
            $this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '120.35' ); //120.35
            $this->assertEquals( $this->mf( $pd_obj->getStatePayPeriodDeductions() ), '25.71' ); //23.79
        }
    
        function testAL_2007a_BiWeekly_Married_MediumIncome() {
            Debug::text('US - BiWeekly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__,10);
    
            $pd_obj = new PayrollDeduction('US','AL');
            $pd_obj->setDate(strtotime('01-Jan-07'));
            $pd_obj->setAnnualPayPeriods( 12 ); //Monthly
            $pd_obj->setFederalFilingStatus( 10 ); //Single
            $pd_obj->setFederalAllowance( 0 );
    
            $pd_obj->setStateFilingStatus( 20 ); //Married
            $pd_obj->setUserValue2( 0 );
    
            $pd_obj->setFederalTaxExempt( FALSE );
            $pd_obj->setProvincialTaxExempt( FALSE );
    
            $pd_obj->setGrossPayPeriodIncome( 2083 );
    
            $this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '2083.00' );
            $this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '248.20' );
            $this->assertEquals( $this->mf( $pd_obj->getStatePayPeriodDeductions() ), '52.78' );
        }
    
        function testAL_2007a_SemiMonthly_Married_HighIncome_8Allowances() {
            Debug::text('US - SemiMonthly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__,10);
    
            $pd_obj = new PayrollDeduction('US','AL');
            $pd_obj->setDate(strtotime('01-Jan-07'));
            $pd_obj->setAnnualPayPeriods( 24 ); //Semi-Monthly
    
            $pd_obj->setFederalFilingStatus( 10 ); //Single
            $pd_obj->setFederalAllowance( 1 );
    
            $pd_obj->setStateFilingStatus( 20 ); //Married
            $pd_obj->setUserValue2( 8 );
    
            $pd_obj->setFederalTaxExempt( FALSE );
            $pd_obj->setProvincialTaxExempt( FALSE );
    
            $pd_obj->setGrossPayPeriodIncome( 4000.00 );
    
            //var_dump($pd_obj->getArray());
    
            $this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '4000.00' );
            $this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '812.20' ); //812.20
            $this->assertEquals( $this->mf( $pd_obj->getStatePayPeriodDeductions() ), '135.22' ); //133.14
        }
    
        function testAL_2007a_SemiMonthly_Married_HighIncome_2Allowances() {
            Debug::text('US - SemiMonthly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__,10);
    
            $pd_obj = new PayrollDeduction('US','AL');
            $pd_obj->setDate(strtotime('01-Jan-07'));
            $pd_obj->setAnnualPayPeriods( 52 ); //Weekly
    
            $pd_obj->setFederalFilingStatus( 20 ); //Married
            $pd_obj->setFederalAllowance( 2 );
    
            $pd_obj->setStateFilingStatus( 20 ); //Married
            $pd_obj->setUserValue2( 2 );
    
            $pd_obj->setFederalTaxExempt( FALSE );
            $pd_obj->setProvincialTaxExempt( FALSE );
    
            $pd_obj->setGrossPayPeriodIncome( 435.00 );
    
            $this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '435.00' );
            $this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '15.04' ); //15.04
            $this->assertEquals( $this->mf( $pd_obj->getStatePayPeriodDeductions() ), '10.37' ); //9.41
        }
    */
}
