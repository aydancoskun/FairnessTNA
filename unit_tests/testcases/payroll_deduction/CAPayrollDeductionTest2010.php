<?php

/**
 * @group CAPayrollDeductionTest2010
 */
class CAPayrollDeductionTest2010 extends PHPUnit\Framework\TestCase {
	public $company_id = NULL;

	public function setUp(): void {
		Debug::text('Running setUp(): ', __FILE__, __LINE__, __METHOD__, 10);

		require_once( Environment::getBasePath().'/classes/payroll_deduction/PayrollDeduction.class.php');

		$this->tax_table_file = dirname(__FILE__).'/CAPayrollDeductionTest2010.csv';

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
	// January 2010
	//

	//
	// Don't forget to update the Federal Employment Credit in CA.class.php.
	//
	function testCSVFile() {
		$this->assertEquals( file_exists($this->tax_table_file), TRUE);

		$test_rows = Misc::parseCSV( $this->tax_table_file, TRUE );

		$total_rows = (count($test_rows) + 1);
		$i = 2;
		foreach( $test_rows as $row ) {
			//Debug::text('Province: '. $row['province'] .' Income: '. $row['gross_income'], __FILE__, __LINE__, __METHOD__,10);
			if ( $row['gross_income'] == '' AND $row['low_income'] != '' AND $row['high_income'] != '' ) {
				$row['gross_income'] = ($row['low_income'] + ( ($row['high_income'] - $row['low_income']) / 2 ));
			}
			if ( $row['country'] != '' AND $row['gross_income'] != '' ) {
				//echo $i.'/'.$total_rows.'. Testing Province: '. $row['province'] .' Income: '. $row['gross_income'] ."\n";

				$pd_obj = new PayrollDeduction( $row['country'], $row['province'] );
				$pd_obj->setDate( strtotime( $row['date'] ) );
				$pd_obj->setEnableCPPAndEIDeduction(TRUE); //Deduct CPP/EI.
				$pd_obj->setAnnualPayPeriods( $row['pay_periods'] );

				$pd_obj->setFederalTotalClaimAmount( $row['federal_claim'] ); //Amount from 2005, Should use amount from 2007 automatically.
				$pd_obj->setProvincialTotalClaimAmount( $row['provincial_claim'] );
				//$pd_obj->setWCBRate( 0.18 );

				$pd_obj->setEIExempt( FALSE );
				$pd_obj->setCPPExempt( FALSE );

				$pd_obj->setFederalTaxExempt( FALSE );
				$pd_obj->setProvincialTaxExempt( FALSE );

				$pd_obj->setYearToDateCPPContribution( 0 );
				$pd_obj->setYearToDateEIContribution( 0 );

				$pd_obj->setGrossPayPeriodIncome( $this->mf( $row['gross_income'] ) );

				//var_dump($pd_obj->getArray());

				$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), $this->mf( $row['gross_income'] ) );
				if ( $row['federal_deduction'] != '' ) {
					$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), $this->mf( $row['federal_deduction'] ) );
				}
				if ( $row['provincial_deduction'] != '' ) {
					$this->assertEquals( $this->mf( $pd_obj->getProvincialPayPeriodDeductions() ), $this->mf( $row['provincial_deduction'] ) );
				}
			}

			$i++;
		}

		//Make sure all rows are tested.
		$this->assertEquals( $total_rows, ($i - 1));
	}

	//
	// CPP/ EI
	//
	function testCA_2009a_BiWeekly_CPP_LowIncome() {
		Debug::text('CA - BiWeekly - CPP - Beginning of 01-Jan-2010: ', __FILE__, __LINE__, __METHOD__, 10);

		$pd_obj = new PayrollDeduction('CA', 'BC');
		$pd_obj->setDate(strtotime('01-Jan-2010'));
		$pd_obj->setEnableCPPAndEIDeduction(TRUE); //Deduct CPP/EI.
		$pd_obj->setAnnualPayPeriods( 26 );

		$pd_obj->setFederalTotalClaimAmount( 10100 );
		$pd_obj->setProvincialTotalClaimAmount( 0 );
		$pd_obj->setWCBRate( 0.18 );

		$pd_obj->setEIExempt( FALSE );
		$pd_obj->setCPPExempt( FALSE );

		$pd_obj->setFederalTaxExempt( FALSE );
		$pd_obj->setProvincialTaxExempt( FALSE );

		$pd_obj->setYearToDateCPPContribution( 0 );
		$pd_obj->setYearToDateEIContribution( 0 );

		$pd_obj->setGrossPayPeriodIncome( 585.32 );

		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '585.32' );
		$this->assertEquals( $this->mf( $pd_obj->getEmployeeCPP() ), '22.31' );
		$this->assertEquals( $this->mf( $pd_obj->getEmployerCPP() ), '22.31' );
	}

	function testCA_2009a_SemiMonthly_CPP_LowIncome() {
		Debug::text('CA - BiWeekly - CPP - Beginning of 01-Jan-2010: ', __FILE__, __LINE__, __METHOD__, 10);

		$pd_obj = new PayrollDeduction('CA', 'BC');
		$pd_obj->setDate(strtotime('01-Jan-2010'));
		$pd_obj->setEnableCPPAndEIDeduction(TRUE); //Deduct CPP/EI.
		$pd_obj->setAnnualPayPeriods( 24 );

		$pd_obj->setFederalTotalClaimAmount( 10100 );
		$pd_obj->setProvincialTotalClaimAmount( 0 );
		$pd_obj->setWCBRate( 0.18 );

		$pd_obj->setEIExempt( FALSE );
		$pd_obj->setCPPExempt( FALSE );

		$pd_obj->setFederalTaxExempt( FALSE );
		$pd_obj->setProvincialTaxExempt( FALSE );

		$pd_obj->setYearToDateCPPContribution( 0 );
		$pd_obj->setYearToDateEIContribution( 0 );

		$pd_obj->setGrossPayPeriodIncome( 585.23 );

		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '585.23' );
		$this->assertEquals( $this->mf( $pd_obj->getEmployeeCPP() ), '21.75' );
		$this->assertEquals( $this->mf( $pd_obj->getEmployerCPP() ), '21.75' );
	}

	function testCA_2009a_EI_LowIncome() {
		Debug::text('CA - EI - Beginning of 01-Jan-2010: ', __FILE__, __LINE__, __METHOD__, 10);

		$pd_obj = new PayrollDeduction('CA', 'BC');
		$pd_obj->setDate(strtotime('01-Jan-2010'));
		$pd_obj->setEnableCPPAndEIDeduction(TRUE); //Deduct CPP/EI.
		$pd_obj->setAnnualPayPeriods( 26 );

		$pd_obj->setFederalTotalClaimAmount( 10100 );
		$pd_obj->setProvincialTotalClaimAmount( 0 );
		$pd_obj->setWCBRate( 0.18 );

		$pd_obj->setEIExempt( FALSE );
		$pd_obj->setCPPExempt( FALSE );

		$pd_obj->setFederalTaxExempt( FALSE );
		$pd_obj->setProvincialTaxExempt( FALSE );

		$pd_obj->setYearToDateCPPContribution( 0 );
		$pd_obj->setYearToDateEIContribution( 0 );

		$pd_obj->setGrossPayPeriodIncome( 587.76 );

		//var_dump($pd_obj->getArray());

		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '587.76' );
		$this->assertEquals( $this->mf( $pd_obj->getEmployeeEI() ), '10.17' );
		$this->assertEquals( $this->mf( $pd_obj->getEmployerEI() ), '14.24' );
	}
}
?>