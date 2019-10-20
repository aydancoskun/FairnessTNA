<?php

/**
 * @group CRPayrollDeductionTest
 */
class CRPayrollDeductionTest extends PHPUnit\Framework\TestCase {
	public $company_id = NULL;

	public function setUp(): void {
		Debug::text('Running setUp(): ', __FILE__, __LINE__, __METHOD__, 10);

		require_once( Environment::getBasePath().'/classes/payroll_deduction/PayrollDeduction.class.php');

		$this->company_id = PRIMARY_COMPANY_ID;

		TTDate::setTimeZone('Etc/GMT+8'); //Force to non-DST timezone. 'PST' isnt actually valid.
	}

	public function tearDown(): void {
		Debug::text('Running tearDown(): ', __FILE__, __LINE__, __METHOD__, 10);
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
		Debug::text('CR - BiWeekly - Beginning of 2007 01-Jan-07: ', __FILE__, __LINE__, __METHOD__, 10);

		$pd_obj = new PayrollDeduction('CR', NULL);
		$pd_obj->setDate(strtotime('01-Jan-07'));
		$pd_obj->setAnnualPayPeriods( 26 ); //Bi-Weekly

		$pd_obj->setFederalFilingStatus( 10 ); //Single
		$pd_obj->setFederalAllowance( 1 );
		//$pd_obj->setUserCurrency('CRC');

		$pd_obj->setGrossPayPeriodIncome( 260000.00 );

		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '260000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '3993.85' ); //100.73
	}
}
?>