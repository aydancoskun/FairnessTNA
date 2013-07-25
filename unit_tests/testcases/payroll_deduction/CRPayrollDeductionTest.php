<?php
/*********************************************************************************
 * This file is part of "Fairness", a Payroll and Time Management program.
 * Fairness is Copyright 2013 Aydan Coscun (aydan.ayfer.coskun@gmail.com)
 * Portions of this software are Copyright (C) 2003 - 2013 TimeTrex Software Inc.
 * because Fairness is a fork of "TimeTrex Workforce Management" Software.
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
/*
 * $Revision: 1665 $
 * $Id: CTPayrollDeductionTest.php 1665 2008-01-18 23:34:53Z ipso $
 * $Date: 2008-01-18 15:34:53 -0800 (Fri, 18 Jan 2008) $
 */
require_once('PHPUnit/Framework/TestCase.php');

class CRPayrollDeductionTest extends PHPUnit_Framework_TestCase {

    public $company_id = 1;

    public function __construct() {
        global $db, $cache;

		require_once( Environment::getBasePath().'/classes/payroll_deduction/PayrollDeduction.class.php');

		TTDate::setTimeZone('PST');
    }

    public function setUp() {
        Debug::text('Running setUp(): ', __FILE__, __LINE__, __METHOD__,10);
        return TRUE;
    }

    public function tearDown() {
        Debug::text('Running tearDown(): ', __FILE__, __LINE__, __METHOD__,10);
        return TRUE;
    }

	public function mf($amount) {
		return Misc::MoneyFormat($amount, FALSE);
	}

	//
	//
	//
	// 2007
	//
	//
	//

	function testCR_2007a_BiWeekly_Single_LowIncome() {
		Debug::text('CR - BiWeekly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__,10);

		$pd_obj = new PayrollDeduction('CR',NULL);
		$pd_obj->setDate(strtotime('01-Jan-07'));
		$pd_obj->setAnnualPayPeriods( 26 ); //Bi-Weekly

		$pd_obj->setFederalFilingStatus( 10 ); //Single
		$pd_obj->setFederalAllowance( 1 );
		//$pd_obj->setUserCurrency('CRC');

		$pd_obj->setGrossPayPeriodIncome( 260000.00 );

		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '260000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '3993.85' ); //100.73
	}
/*
	function testCR_2007a_BiWeekly_Married_LowIncome() {
		Debug::text('CR - BiWeekly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__,10);

		$pd_obj = new PayrollDeduction('CR',NULL);
		$pd_obj->setDate(strtotime('01-Jan-07'));
		$pd_obj->setAnnualPayPeriods( 26 ); //Bi-Weekly

		$pd_obj->setFederalFilingStatus( 20 ); //Married
		$pd_obj->setFederalAllowance( 1 );
		//$pd_obj->setUserCurrency('CRC');

		$pd_obj->setGrossPayPeriodIncome( 260000.00 );

		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '260000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '3389.23' );
	}

	function testCR_2007a_BiWeekly_Married_LowIncomeB() {
		Debug::text('CR - BiWeekly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__,10);

		$pd_obj = new PayrollDeduction('CR',NULL);
		$pd_obj->setDate(strtotime('01-Jan-07'));
		$pd_obj->setAnnualPayPeriods( 26 ); //Bi-Weekly

		$pd_obj->setFederalFilingStatus( 20 ); //Married
		$pd_obj->setFederalAllowance( 3 );
		//$pd_obj->setUserCurrency('CRC');

		$pd_obj->setGrossPayPeriodIncome( 260000.00 );

		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '260000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '2576.92' );
	}

	function testCR_2007a_SemiMonthly_Single_LowIncome() {
		Debug::text('CR - SemiMonthly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__,10);

		$pd_obj = new PayrollDeduction('CR',NULL);
		$pd_obj->setDate(strtotime('01-Jan-07'));
		$pd_obj->setAnnualPayPeriods( 24 ); //Semi-Monthly

		$pd_obj->setFederalFilingStatus( 10 ); //Single
		$pd_obj->setFederalAllowance( 1 );
		//$pd_obj->setUserCurrency('CRC');

		$pd_obj->setGrossPayPeriodIncome( 260000.00 );

		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '260000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '2160.00' );
	}

	function testCR_2007a_SemiMonthly_Married_LowIncome() {
		Debug::text('CR - SemiMonthly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__,10);

		$pd_obj = new PayrollDeduction('CR',NULL);
		$pd_obj->setDate(strtotime('01-Jan-07'));
		$pd_obj->setAnnualPayPeriods( 24 ); //Semi-Monthly

		$pd_obj->setFederalFilingStatus( 20 ); //Married
		$pd_obj->setFederalAllowance( 1 );
		//$pd_obj->setUserCurrency('CRC');

		$pd_obj->setGrossPayPeriodIncome( 260000.00 );

		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '260000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '1505.00' );
	}

	function testCR_2007a_SemiMonthly_Single_HighIncome() {
		Debug::text('CR - SemiMonthly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__,10);

		$pd_obj = new PayrollDeduction('CR',NULL);
		$pd_obj->setDate(strtotime('01-Jan-07'));
		$pd_obj->setAnnualPayPeriods( 24 ); //Semi-Monthly

		$pd_obj->setFederalFilingStatus( 10 ); //Single
		$pd_obj->setFederalAllowance( 1 );
		//$pd_obj->setUserCurrency('CRC');

		$pd_obj->setGrossPayPeriodIncome( 450000.00 );

		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '450000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '26110.00' );
	}

	function testCR_2007a_SemiMonthly_Single_LowIncome_3Allowances() {
		Debug::text('CR - SemiMonthly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__,10);

		$pd_obj = new PayrollDeduction('CR',NULL);
		$pd_obj->setDate(strtotime('01-Jan-07'));
		$pd_obj->setAnnualPayPeriods( 24 ); //Semi-Monthly

		$pd_obj->setFederalFilingStatus( 10 ); //Single
		$pd_obj->setFederalAllowance( 3 );
		//$pd_obj->setUserCurrency('CRC');

		$pd_obj->setGrossPayPeriodIncome( 260000.00 );

		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '260000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '1280.00' );
	}

	function testCR_2007a_SemiMonthly_Single_LowIncome_5Allowances() {
		Debug::text('CR - SemiMonthly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__,10);

		$pd_obj = new PayrollDeduction('CR',NULL);
		$pd_obj->setDate(strtotime('01-Jan-07'));
		$pd_obj->setAnnualPayPeriods( 24 ); //Semi-Monthly

		$pd_obj->setFederalFilingStatus( 10 ); //Single
		$pd_obj->setFederalAllowance( 5 );
		//$pd_obj->setUserCurrency('CRC');

		$pd_obj->setGrossPayPeriodIncome( 260000.00 );

		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '260000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '400.00' );
	}

	function testCR_2007a_SemiMonthly_Single_LowIncome_8AllowancesA() {
		Debug::text('CR - SemiMonthly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__,10);

		$pd_obj = new PayrollDeduction('CR',NULL);
		$pd_obj->setDate(strtotime('01-Jan-07'));
		$pd_obj->setAnnualPayPeriods( 24 ); //Semi-Monthly

		$pd_obj->setFederalFilingStatus( 10 ); //Single
		$pd_obj->setFederalAllowance( 8 );
		//$pd_obj->setUserCurrency('CRC');

		$pd_obj->setGrossPayPeriodIncome( 260000.00 );

		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '260000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '0.00' );
	}

	function testCR_2007a_SemiMonthly_Single_HighIncome_8AllowancesA() {
		Debug::text('CR - SemiMonthly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__,10);

		$pd_obj = new PayrollDeduction('CR',NULL);
		$pd_obj->setDate(strtotime('01-Jan-07'));
		$pd_obj->setAnnualPayPeriods( 24 ); //Semi-Monthly

		$pd_obj->setFederalFilingStatus( 10 ); //Single
		$pd_obj->setFederalAllowance( 8 );
		//$pd_obj->setUserCurrency('CRC');

		$pd_obj->setGrossPayPeriodIncome( 450000.00 );

		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '450000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '23030.00' );
	}
*/
}
?>