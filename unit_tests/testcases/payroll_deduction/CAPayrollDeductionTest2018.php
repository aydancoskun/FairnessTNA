<?php

/**
 * @group CAPayrollDeductionTest2018
 */
class CAPayrollDeductionTest2018 extends PHPUnit\Framework\TestCase {
	public $company_id = NULL;

	public function setUp(): void {
		Debug::text('Running setUp(): ', __FILE__, __LINE__, __METHOD__, 10);

		require_once( Environment::getBasePath().'/classes/payroll_deduction/PayrollDeduction.class.php');

		$this->tax_table_file = dirname(__FILE__).'/CAPayrollDeductionTest2018.csv';

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
	// January 2018
	//
	function testCSVFile() {
		$this->assertEquals( file_exists($this->tax_table_file), TRUE);

		$test_rows = Misc::parseCSV( $this->tax_table_file, TRUE );

		$total_rows = ( count($test_rows) + 1 );
		$i = 2;
		foreach( $test_rows as $row ) {
			//Debug::text('Province: '. $row['province'] .' Income: '. $row['gross_income'], __FILE__, __LINE__, __METHOD__, 10);
			if ( isset($row['gross_income']) AND isset($row['low_income']) AND isset($row['high_income'])
					AND $row['gross_income'] == '' AND $row['low_income'] != '' AND $row['high_income'] != '' ) {
				$row['gross_income'] = ( $row['low_income'] + ( ($row['high_income'] - $row['low_income']) / 2 ) );
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
		$this->assertEquals( $total_rows, ( $i - 1 ));
	}

	//Test that the tax changes from one year to the next are without a specified threshold.
	function testCompareWithLastYearCSVFile() {
		$this->assertEquals( file_exists($this->tax_table_file), TRUE);

		$test_rows = Misc::parseCSV( $this->tax_table_file, TRUE );

		$total_rows = ( count($test_rows) + 1 );
		$i = 2;
		foreach( $test_rows as $row ) {
			//Debug::text('Province: '. $row['province'] .' Income: '. $row['gross_income'], __FILE__, __LINE__, __METHOD__, 10);
			if ( isset($row['gross_income']) AND isset($row['low_income']) AND isset($row['high_income'])
					AND $row['gross_income'] == '' AND $row['low_income'] != '' AND $row['high_income'] != '' ) {
				$row['gross_income'] = ( $row['low_income'] + ( ($row['high_income'] - $row['low_income']) / 2 ) );
			}
			if ( $row['country'] != '' AND $row['gross_income'] != '' ) {
				//echo $i.'/'.$total_rows.'. Testing Province: '. $row['province'] .' Income: '. $row['gross_income'] ."\n";

				$pd_obj = new PayrollDeduction( $row['country'], $row['province'] );
				$pd_obj->setDate( strtotime('-1 year', strtotime( $row['date'] ) ) ); //Get the same date only last year.
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
					//$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), $this->mf( $row['federal_deduction'] ) );
					$amount_diff = 0;
					$amount_diff_percent = 0;
					if ( $row['federal_deduction'] > 0 ) {
						$amount_diff = abs( ( $pd_obj->getFederalPayPeriodDeductions() - $row['federal_deduction'] ) );
						$amount_diff_percent = ( ( $amount_diff / $row['federal_deduction'] ) * 100 );
					}

					//Debug::text($i.'. Amount: This Year: '. $row['federal_deduction'] .' Last Year: '. $pd_obj->getFederalPayPeriodDeductions() .' Diff Amount: '. $amount_diff .' Percent: '. $amount_diff_percent .'%', __FILE__, __LINE__, __METHOD__, 10);
					if ( $amount_diff > 1.5 ) {
						$this->assertLessThan( 3, $amount_diff_percent ); //Should be slightly higher than inflation.
						$this->assertGreaterThan( 0, $amount_diff_percent );
					}
				}
				if ( $row['provincial_deduction'] != '' ) {
					//$this->assertEquals( $this->mf( $pd_obj->getProvincialPayPeriodDeductions() ), $this->mf( $row['provincial_deduction'] ) );
					$amount_diff = 0;
					$amount_diff_percent = 0;
					if ( $row['provincial_deduction'] > 0 AND $pd_obj->getProvincialPayPeriodDeductions() > 0 ) {
						$amount_diff = abs( ( $pd_obj->getProvincialPayPeriodDeductions() - $row['provincial_deduction'] ) );
						$amount_diff_percent = ( ( $amount_diff / $row['provincial_deduction'] ) * 100 );
					}

					Debug::text($i.'. Amount: This Year: '. $row['provincial_deduction'] .' Last Year: '. $pd_obj->getProvincialPayPeriodDeductions() .' Diff Amount: '. $amount_diff .' Percent: '. $amount_diff_percent .'%', __FILE__, __LINE__, __METHOD__, 10);
					if ( $amount_diff > 3 ) {
						$this->assertLessThan( 26, $amount_diff_percent ); //Reasonable margin of error.
						$this->assertGreaterThan( 0, $amount_diff_percent );
					}
				}
			}

			$i++;
		}

		//Make sure all rows are tested.
		$this->assertEquals( $total_rows, ( $i - 1 ));
	}

	function testCA_2018a_Example() {
		Debug::text('CA - Example Beginning of 01-Jan-2018: ', __FILE__, __LINE__, __METHOD__, 10);

		$pd_obj = new PayrollDeduction('CA', 'AB');
		$pd_obj->setDate(strtotime('01-Jan-2018'));
		$pd_obj->setEnableCPPAndEIDeduction(TRUE); //Deduct CPP/EI.
		$pd_obj->setAnnualPayPeriods( 52 );

		$pd_obj->setFederalTotalClaimAmount( 29721.00 );
		$pd_obj->setProvincialTotalClaimAmount( 17593 );
		$pd_obj->setWCBRate( 0.18 );

		$pd_obj->setEIExempt( FALSE );
		$pd_obj->setCPPExempt( FALSE );

		$pd_obj->setFederalTaxExempt( FALSE );
		$pd_obj->setProvincialTaxExempt( FALSE );

		$pd_obj->setYearToDateCPPContribution( 0 );
		$pd_obj->setYearToDateEIContribution( 0 );

		$pd_obj->setGrossPayPeriodIncome( 1100 );

		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '1100.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '77.07' );
		$this->assertEquals( $this->mf( $pd_obj->getProvincialPayPeriodDeductions() ), '66.99' );
	}

	function testCA_2018a_Example1() {
		Debug::text('CA - Example1 - Beginning of 01-Jan-2018: ', __FILE__, __LINE__, __METHOD__, 10);

		$pd_obj = new PayrollDeduction('CA', 'AB');
		$pd_obj->setDate(strtotime('01-Jan-2018'));
		$pd_obj->setEnableCPPAndEIDeduction(TRUE); //Deduct CPP/EI.
		$pd_obj->setAnnualPayPeriods( 12 );

		$pd_obj->setFederalTotalClaimAmount( 11327 );
		$pd_obj->setProvincialTotalClaimAmount( 18214 );
		$pd_obj->setWCBRate( 0.18 );

		$pd_obj->setEIExempt( FALSE );
		$pd_obj->setCPPExempt( FALSE );

		$pd_obj->setFederalTaxExempt( FALSE );
		$pd_obj->setProvincialTaxExempt( FALSE );

		$pd_obj->setYearToDateCPPContribution( 0 );
		$pd_obj->setYearToDateEIContribution( 0 );

		$pd_obj->setGrossPayPeriodIncome( 1800 );

		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '1800.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '91.77' );
		$this->assertEquals( $this->mf( $pd_obj->getProvincialPayPeriodDeductions() ), '11.92' );
	}

	function testCA_2018a_Example2() {
		Debug::text('CA - Example2 - Beginning of 01-Jan-2018: ', __FILE__, __LINE__, __METHOD__, 10);

		$pd_obj = new PayrollDeduction('CA', 'AB');
		$pd_obj->setDate(strtotime('01-Jan-2018'));
		$pd_obj->setEnableCPPAndEIDeduction(TRUE); //Deduct CPP/EI.
		$pd_obj->setAnnualPayPeriods( 26 );

		$pd_obj->setFederalTotalClaimAmount( 11327 );
		$pd_obj->setProvincialTotalClaimAmount( 18214 );
		$pd_obj->setWCBRate( 0.18 );

		$pd_obj->setEIExempt( FALSE );
		$pd_obj->setCPPExempt( FALSE );

		$pd_obj->setFederalTaxExempt( FALSE );
		$pd_obj->setProvincialTaxExempt( FALSE );

		$pd_obj->setYearToDateCPPContribution( 0 );
		$pd_obj->setYearToDateEIContribution( 0 );

		$pd_obj->setGrossPayPeriodIncome( 2300 );

		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '2300.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '277.98' );
		$this->assertEquals( $this->mf( $pd_obj->getProvincialPayPeriodDeductions() ), '143.97' );
	}

	function testCA_2018a_Example3() {
		Debug::text('CA - Example3 - Beginning of 01-Jan-2018: ', __FILE__, __LINE__, __METHOD__, 10);

		$pd_obj = new PayrollDeduction('CA', 'AB');
		$pd_obj->setDate(strtotime('01-Jan-2018'));
		$pd_obj->setEnableCPPAndEIDeduction(TRUE); //Deduct CPP/EI.
		$pd_obj->setAnnualPayPeriods( 52 );

		$pd_obj->setFederalTotalClaimAmount( 11327 );
		$pd_obj->setProvincialTotalClaimAmount( 18214 );
		$pd_obj->setWCBRate( 0.18 );

		$pd_obj->setEIExempt( FALSE );
		$pd_obj->setCPPExempt( FALSE );

		$pd_obj->setFederalTaxExempt( FALSE );
		$pd_obj->setProvincialTaxExempt( FALSE );

		$pd_obj->setYearToDateCPPContribution( 0 );
		$pd_obj->setYearToDateEIContribution( 0 );

		$pd_obj->setGrossPayPeriodIncome( 2500 );

		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '2500.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '454.65' );
		$this->assertEquals( $this->mf( $pd_obj->getProvincialPayPeriodDeductions() ), '207.70' );
	}

	function testCA_2018a_Example4() {
		Debug::text('CA - Example1 - Beginning of 01-Jan-2018: ', __FILE__, __LINE__, __METHOD__, 10);

		$pd_obj = new PayrollDeduction('CA', 'BC');
		$pd_obj->setDate(strtotime('01-Jan-2018'));
		$pd_obj->setEnableCPPAndEIDeduction(TRUE); //Deduct CPP/EI.
		$pd_obj->setAnnualPayPeriods( 26 );

		$pd_obj->setFederalTotalClaimAmount( 11809 );
		$pd_obj->setProvincialTotalClaimAmount( 9938 );
		$pd_obj->setWCBRate( 0 );

		$pd_obj->setEIExempt( FALSE );
		$pd_obj->setCPPExempt( FALSE );

		$pd_obj->setFederalTaxExempt( FALSE );
		$pd_obj->setProvincialTaxExempt( FALSE );

		$pd_obj->setYearToDateCPPContribution( 0 );
		$pd_obj->setYearToDateEIContribution( 0 );

		$pd_obj->setGrossPayPeriodIncome( 1560 );

		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '1560.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '144.51' );
		$this->assertEquals( $this->mf( $pd_obj->getProvincialPayPeriodDeductions() ), '54.71' );
	}

	function testCA_2018a_NS_Example1() {
		Debug::text('CA - Example1 - Beginning of 01-Jan-2018: ', __FILE__, __LINE__, __METHOD__, 10);

		$pd_obj = new PayrollDeduction('CA', 'NS');
		$pd_obj->setDate(strtotime('01-Jan-2018'));
		$pd_obj->setEnableCPPAndEIDeduction(TRUE); //Deduct CPP/EI.
		$pd_obj->setAnnualPayPeriods( 26 );

		$pd_obj->setFederalTotalClaimAmount( 0 );
		$pd_obj->setProvincialTotalClaimAmount( 0 ); //Claim amount of 0 is always considered $0 no matter what.
		$pd_obj->setWCBRate( 0 );

		$pd_obj->setEIExempt( FALSE );
		$pd_obj->setCPPExempt( FALSE );

		$pd_obj->setFederalTaxExempt( FALSE );
		$pd_obj->setProvincialTaxExempt( FALSE );

		$pd_obj->setYearToDateCPPContribution( 0 );
		$pd_obj->setYearToDateEIContribution( 0 );

		$pd_obj->setGrossPayPeriodIncome( 192 ); //Low income, where the full claim amount is used.

		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '192.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '21.00' );
		$this->assertEquals( $this->mf( $pd_obj->getProvincialPayPeriodDeductions() ), '16.35' );
	}

	function testCA_2018a_NS_Example2() {
		Debug::text('CA - Example1 - Beginning of 01-Jan-2018: ', __FILE__, __LINE__, __METHOD__, 10);

		$pd_obj = new PayrollDeduction('CA', 'NS');
		$pd_obj->setDate(strtotime('01-Jan-2018'));
		$pd_obj->setEnableCPPAndEIDeduction(TRUE); //Deduct CPP/EI.
		$pd_obj->setAnnualPayPeriods( 26 );

		$pd_obj->setFederalTotalClaimAmount( 0 );
		$pd_obj->setProvincialTotalClaimAmount( 100 );
		$pd_obj->setWCBRate( 0 );

		$pd_obj->setEIExempt( FALSE );
		$pd_obj->setCPPExempt( FALSE );

		$pd_obj->setFederalTaxExempt( FALSE );
		$pd_obj->setProvincialTaxExempt( FALSE );

		$pd_obj->setYearToDateCPPContribution( 0 );
		$pd_obj->setYearToDateEIContribution( 0 );

		$pd_obj->setGrossPayPeriodIncome( 1923 ); //Medium income, where part of the claim amount is reduced.

		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '1923.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '270.68' );
		$this->assertEquals( $this->mf( $pd_obj->getProvincialPayPeriodDeductions() ), '173.04' );
	}

	function testCA_2018a_NS_Example3() {
		Debug::text('CA - Example1 - Beginning of 01-Jan-2018: ', __FILE__, __LINE__, __METHOD__, 10);

		$pd_obj = new PayrollDeduction('CA', 'NS');
		$pd_obj->setDate(strtotime('01-Jan-2018'));
		$pd_obj->setEnableCPPAndEIDeduction(TRUE); //Deduct CPP/EI.
		$pd_obj->setAnnualPayPeriods( 26 );

		$pd_obj->setFederalTotalClaimAmount( 0 );
		$pd_obj->setProvincialTotalClaimAmount( 11481 );
		$pd_obj->setWCBRate( 0 );

		$pd_obj->setEIExempt( FALSE );
		$pd_obj->setCPPExempt( FALSE );

		$pd_obj->setFederalTaxExempt( FALSE );
		$pd_obj->setProvincialTaxExempt( FALSE );

		$pd_obj->setYearToDateCPPContribution( 0 );
		$pd_obj->setYearToDateEIContribution( 0 );

		$pd_obj->setGrossPayPeriodIncome( 9615 ); //High income, over the threshold that reduces the claim amount.

		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '9615.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '2366.99' );
		$this->assertEquals( $this->mf( $pd_obj->getProvincialPayPeriodDeductions() ), '1637.92' );
	}

	function testCA_2018a_GovExample1() {
		Debug::text('CA - Example1 - Beginning of 01-Jan-2018: ', __FILE__, __LINE__, __METHOD__, 10);

		$pd_obj = new PayrollDeduction('CA', 'AB');
		$pd_obj->setDate(strtotime('01-Jan-2018'));
		$pd_obj->setEnableCPPAndEIDeduction(TRUE); //Deduct CPP/EI.
		$pd_obj->setAnnualPayPeriods( 52 );

		$pd_obj->setFederalTotalClaimAmount( 11327 );
		$pd_obj->setProvincialTotalClaimAmount( 18214 );
		$pd_obj->setWCBRate( 0 );

		$pd_obj->setEIExempt( FALSE );
		$pd_obj->setCPPExempt( FALSE );

		$pd_obj->setFederalTaxExempt( FALSE );
		$pd_obj->setProvincialTaxExempt( FALSE );

		$pd_obj->setYearToDateCPPContribution( 0 );
		$pd_obj->setYearToDateEIContribution( 0 );

		$pd_obj->setGrossPayPeriodIncome( 1030 ); //Take Gross income minus RPP and Union Dues.

		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '1030.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '114.73' );
		$this->assertEquals( $this->mf( $pd_obj->getProvincialPayPeriodDeductions() ), '60.21' );
	}

	function testCA_2018a_GovExample2() {
		Debug::text('CA - Example1 - Beginning of 01-Jan-2018: ', __FILE__, __LINE__, __METHOD__, 10);

		$pd_obj = new PayrollDeduction('CA', 'BC');
		$pd_obj->setDate(strtotime('01-Jan-2018'));
		$pd_obj->setEnableCPPAndEIDeduction(TRUE); //Deduct CPP/EI.
		$pd_obj->setAnnualPayPeriods( 52 );

		$pd_obj->setFederalTotalClaimAmount( 11327 );
		$pd_obj->setProvincialTotalClaimAmount( 9938 );
		$pd_obj->setWCBRate( 0 );

		$pd_obj->setEIExempt( FALSE );
		$pd_obj->setCPPExempt( FALSE );

		$pd_obj->setFederalTaxExempt( FALSE );
		$pd_obj->setProvincialTaxExempt( FALSE );

		$pd_obj->setYearToDateCPPContribution( 0 );
		$pd_obj->setYearToDateEIContribution( 0 );

		$pd_obj->setGrossPayPeriodIncome( 1030 ); //Take Gross income minus RPP and Union Dues.

		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '1030.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '114.73' );
		$this->assertEquals( $this->mf( $pd_obj->getProvincialPayPeriodDeductions() ), '45.80' );
	}

	function testCA_2018a_GovExample3() {
		Debug::text('CA - Example1 - Beginning of 01-Jan-2018: ', __FILE__, __LINE__, __METHOD__, 10);

		$pd_obj = new PayrollDeduction('CA', 'ON');
		$pd_obj->setDate(strtotime('01-Jan-2018'));
		$pd_obj->setEnableCPPAndEIDeduction(TRUE); //Deduct CPP/EI.
		$pd_obj->setAnnualPayPeriods( 52 );

		$pd_obj->setFederalTotalClaimAmount( 11327 );
		$pd_obj->setProvincialTotalClaimAmount( 9863 );
		$pd_obj->setWCBRate( 0 );

		$pd_obj->setEIExempt( FALSE );
		$pd_obj->setCPPExempt( FALSE );

		$pd_obj->setFederalTaxExempt( FALSE );
		$pd_obj->setProvincialTaxExempt( FALSE );

		$pd_obj->setYearToDateCPPContribution( 0 );
		$pd_obj->setYearToDateEIContribution( 0 );

		$pd_obj->setGrossPayPeriodIncome( 1030 ); //Take Gross income minus RPP and Union Dues.

		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '1030.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '114.73' );
		$this->assertEquals( $this->mf( $pd_obj->getProvincialPayPeriodDeductions() ), '58.62' );
	}

	function testCA_2018a_GovExample4() {
		Debug::text('CA - Example1 - Beginning of 01-Jan-2018: ', __FILE__, __LINE__, __METHOD__, 10);

		$pd_obj = new PayrollDeduction('CA', 'PE');
		$pd_obj->setDate(strtotime('01-Jan-2018'));
		$pd_obj->setEnableCPPAndEIDeduction(TRUE); //Deduct CPP/EI.
		$pd_obj->setAnnualPayPeriods( 52 );

		$pd_obj->setFederalTotalClaimAmount( 11327 );
		$pd_obj->setProvincialTotalClaimAmount( 7708 );
		$pd_obj->setWCBRate( 0 );

		$pd_obj->setEIExempt( FALSE );
		$pd_obj->setCPPExempt( FALSE );

		$pd_obj->setFederalTaxExempt( FALSE );
		$pd_obj->setProvincialTaxExempt( FALSE );

		$pd_obj->setYearToDateCPPContribution( 0 );
		$pd_obj->setYearToDateEIContribution( 0 );

		$pd_obj->setGrossPayPeriodIncome( 1030 ); //Take Gross income minus RPP and Union Dues.

		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '1030.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '114.73' );
		$this->assertEquals( $this->mf( $pd_obj->getProvincialPayPeriodDeductions() ), '95.88' );
	}

	//
	// CPP/ EI
	//
	function testCA_2018a_BiWeekly_CPP_LowIncome() {
		Debug::text('CA - BiWeekly - CPP - Beginning of 01-Jan-2018: ', __FILE__, __LINE__, __METHOD__, 10);

		$pd_obj = new PayrollDeduction('CA', 'BC');
		$pd_obj->setDate(strtotime('01-Jan-2018'));
		$pd_obj->setEnableCPPAndEIDeduction(TRUE); //Deduct CPP/EI.
		$pd_obj->setAnnualPayPeriods( 26 );

		$pd_obj->setFederalTotalClaimAmount( 11809 );
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

	function testCA_2018a_SemiMonthly_CPP_LowIncome() {
		Debug::text('CA - BiWeekly - CPP - Beginning of 01-Jan-2018: ', __FILE__, __LINE__, __METHOD__, 10);

		$pd_obj = new PayrollDeduction('CA', 'BC');
		$pd_obj->setDate(strtotime('01-Jan-2018'));
		$pd_obj->setEnableCPPAndEIDeduction(TRUE); //Deduct CPP/EI.
		$pd_obj->setAnnualPayPeriods( 24 );

		$pd_obj->setFederalTotalClaimAmount( 11809 );
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

	function testCA_2018a_SemiMonthly_MAXCPP_LowIncome() {
		Debug::text('CA - BiWeekly - MAXCPP - Beginning of 2018 01-Jan-2018: ', __FILE__, __LINE__, __METHOD__, 10);

		$pd_obj = new PayrollDeduction('CA', 'BC');
		$pd_obj->setDate(strtotime('01-Jan-2018'));
		$pd_obj->setEnableCPPAndEIDeduction(TRUE); //Deduct CPP/EI.
		$pd_obj->setAnnualPayPeriods( 24 );

		$pd_obj->setFederalTotalClaimAmount( 11809 );
		$pd_obj->setProvincialTotalClaimAmount( 0 );
		$pd_obj->setWCBRate( 0.18 );

		$pd_obj->setEIExempt( FALSE );
		$pd_obj->setCPPExempt( FALSE );

		$pd_obj->setFederalTaxExempt( FALSE );
		$pd_obj->setProvincialTaxExempt( FALSE );

		$pd_obj->setYearToDateCPPContribution( ( $pd_obj->getCPPEmployeeMaximumContribution() - 1 ) ); //2544.30 - 1.00
		$pd_obj->setYearToDateEIContribution( 0 );

		$pd_obj->setGrossPayPeriodIncome( 587.00 );

		//var_dump($pd_obj->getArray());

		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '587.00' );
		$this->assertEquals( $this->mf( $pd_obj->getEmployeeCPP() ), '1.00' );
		$this->assertEquals( $this->mf( $pd_obj->getEmployerCPP() ), '1.00' );
	}

	function testCA_2018a_EI_LowIncome() {
		Debug::text('CA - EI - Beginning of 01-Jan-2018: ', __FILE__, __LINE__, __METHOD__, 10);

		$pd_obj = new PayrollDeduction('CA', 'BC');
		$pd_obj->setDate(strtotime('01-Jan-2018'));
		$pd_obj->setEnableCPPAndEIDeduction(TRUE); //Deduct CPP/EI.
		$pd_obj->setAnnualPayPeriods( 26 );

		$pd_obj->setFederalTotalClaimAmount( 11809 );
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
		$this->assertEquals( $this->mf( $pd_obj->getEmployeeEI() ), '9.76' );
		$this->assertEquals( $this->mf( $pd_obj->getEmployerEI() ), '13.66' );
	}

	function testCA_2018a_MAXEI_LowIncome() {
		Debug::text('CA - MAXEI - Beginning of 01-Jan-2018: ', __FILE__, __LINE__, __METHOD__, 10);

		$pd_obj = new PayrollDeduction('CA', 'BC');
		$pd_obj->setDate(strtotime('01-Jan-2018'));
		$pd_obj->setEnableCPPAndEIDeduction(TRUE); //Deduct CPP/EI.
		$pd_obj->setAnnualPayPeriods( 26 );

		$pd_obj->setFederalTotalClaimAmount( 11809 );
		$pd_obj->setProvincialTotalClaimAmount( 0 );
		$pd_obj->setWCBRate( 0.18 );

		$pd_obj->setEIExempt( FALSE );
		$pd_obj->setCPPExempt( FALSE );

		$pd_obj->setFederalTaxExempt( FALSE );
		$pd_obj->setProvincialTaxExempt( FALSE );

		$pd_obj->setYearToDateCPPContribution( 0 );
		$pd_obj->setYearToDateEIContribution( ( $pd_obj->getEIEmployeeMaximumContribution() - 1 ) );

		$pd_obj->setGrossPayPeriodIncome( 587.00 );

		//var_dump($pd_obj->getArray());

		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '587.00' );
		$this->assertEquals( $this->mf( $pd_obj->getEmployeeEI() ), '1.00' );
		$this->assertEquals( $this->mf( $pd_obj->getEmployerEI() ), '1.40' );
	}


	function testCA_2018a_MAXEI_MAXCPPa() {
		Debug::text('CA - MAXEI/MAXCPP - Beginning of 01-Jan-2018: ', __FILE__, __LINE__, __METHOD__, 10);

		$pd_obj = new PayrollDeduction('CA', 'BC');
		$pd_obj->setDate(strtotime('10-Nov-2018'));
		$pd_obj->setEnableCPPAndEIDeduction(TRUE); //Deduct CPP/EI.
		$pd_obj->setAnnualPayPeriods( 26 );

		$pd_obj->setFederalTotalClaimAmount( 11474 );
		$pd_obj->setProvincialTotalClaimAmount( 10027 );
		$pd_obj->setWCBRate( 0.18 );

		$pd_obj->setEIExempt( FALSE );
		$pd_obj->setCPPExempt( FALSE );

		$pd_obj->setFederalTaxExempt( FALSE );
		$pd_obj->setProvincialTaxExempt( FALSE );

		$pd_obj->setYearToDateCPPContribution( 0 );
		$pd_obj->setYearToDateEIContribution( 0 );

		$pd_obj->setGrossPayPeriodIncome( 2569.21 );

		//var_dump($pd_obj->getArray());

		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '2569.21' );
		$this->assertEquals( $this->mf( $pd_obj->getEmployeeCPP() ), '120.51' );
		$this->assertEquals( $this->mf( $pd_obj->getEmployerCPP() ), '120.51' );
		$this->assertEquals( $this->mf( $pd_obj->getEmployeeEI() ), '42.65' );
		$this->assertEquals( $this->mf( $pd_obj->getEmployerEI() ), '59.71' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '333.17' );
		$this->assertEquals( $this->mf( $pd_obj->getProvincialPayPeriodDeductions() ), '130.58' );
	}

	function testCA_2018a_MAXEI_MAXCPPb() {
		Debug::text('CA - MAXEI/MAXCPP - Beginning of 01-Jan-2018: ', __FILE__, __LINE__, __METHOD__, 10);

		$pd_obj = new PayrollDeduction('CA', 'BC');
		$pd_obj->setDate(strtotime('10-Nov-2018'));
		$pd_obj->setEnableCPPAndEIDeduction(TRUE); //Deduct CPP/EI.
		$pd_obj->setAnnualPayPeriods( 26 );

		$pd_obj->setFederalTotalClaimAmount( 11474 );
		$pd_obj->setProvincialTotalClaimAmount( 10027 );
		$pd_obj->setWCBRate( 0.18 );

		$pd_obj->setEIExempt( FALSE );
		$pd_obj->setCPPExempt( FALSE );

		$pd_obj->setFederalTaxExempt( FALSE );
		$pd_obj->setProvincialTaxExempt( FALSE );

		$pd_obj->setYearToDateCPPContribution( ( $pd_obj->getCPPEmployeeMaximumContribution() - 20 ) ); //2544.30 - 20.00
		$pd_obj->setYearToDateEIContribution( ( $pd_obj->getEIEmployeeMaximumContribution() - 20 ) ); //955.04 - 20.00

		$pd_obj->setGrossPayPeriodIncome( 2569.21 );

		//var_dump($pd_obj->getArray());

		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '2569.21' );
		$this->assertEquals( $this->mf( $pd_obj->getEmployeeCPP() ), '20.00' );
		$this->assertEquals( $this->mf( $pd_obj->getEmployerCPP() ), '20.00' );
		$this->assertEquals( $this->mf( $pd_obj->getEmployeeEI() ), '20.00' );
		$this->assertEquals( $this->mf( $pd_obj->getEmployerEI() ), '28.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '333.17' );
		$this->assertEquals( $this->mf( $pd_obj->getProvincialPayPeriodDeductions() ), '130.58' );
	}

	function testCA_2018a_MAXEI_MAXCPPc() {
		Debug::text('CA - MAXEI/MAXCPP - Beginning of 01-Jan-2018: ', __FILE__, __LINE__, __METHOD__, 10);

		$pd_obj = new PayrollDeduction('CA', 'BC');
		$pd_obj->setDate(strtotime('10-Nov-2018'));
		$pd_obj->setEnableCPPAndEIDeduction(TRUE); //Deduct CPP/EI.
		$pd_obj->setAnnualPayPeriods( 26 );

		$pd_obj->setFederalTotalClaimAmount( 11474 );
		$pd_obj->setProvincialTotalClaimAmount( 10027 );
		$pd_obj->setWCBRate( 0.18 );

		$pd_obj->setEIExempt( FALSE );
		$pd_obj->setCPPExempt( FALSE );

		$pd_obj->setFederalTaxExempt( FALSE );
		$pd_obj->setProvincialTaxExempt( FALSE );

		$pd_obj->setYearToDateCPPContribution( ( $pd_obj->getCPPEmployeeMaximumContribution() - 1 ) ); //2544.30 - 1.00
		$pd_obj->setYearToDateEIContribution( ( $pd_obj->getEIEmployeeMaximumContribution() - 1 ) ); //955.04 - 1.00

		$pd_obj->setGrossPayPeriodIncome( 2569.21 );

		//var_dump($pd_obj->getArray());

		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '2569.21' );
		$this->assertEquals( $this->mf( $pd_obj->getEmployeeCPP() ), '1.00' );
		$this->assertEquals( $this->mf( $pd_obj->getEmployerCPP() ), '1.00' );
		$this->assertEquals( $this->mf( $pd_obj->getEmployeeEI() ), '1.00' );
		$this->assertEquals( $this->mf( $pd_obj->getEmployerEI() ), '1.40' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '333.17' );
		$this->assertEquals( $this->mf( $pd_obj->getProvincialPayPeriodDeductions() ), '130.58' );
	}

	function testCA_2018a_MAXEI_MAXCPPd() {
		Debug::text('CA - MAXEI/MAXCPP - Beginning of 01-Jan-2018: ', __FILE__, __LINE__, __METHOD__, 10);

		$pd_obj = new PayrollDeduction('CA', 'BC');
		$pd_obj->setDate(strtotime('10-Nov-2018'));
		$pd_obj->setEnableCPPAndEIDeduction(TRUE); //Deduct CPP/EI.
		$pd_obj->setAnnualPayPeriods( 26 );

		$pd_obj->setFederalTotalClaimAmount( 11474 );
		$pd_obj->setProvincialTotalClaimAmount( 10027 );
		$pd_obj->setWCBRate( 0.18 );

		$pd_obj->setEIExempt( FALSE );
		$pd_obj->setCPPExempt( FALSE );

		$pd_obj->setFederalTaxExempt( FALSE );
		$pd_obj->setProvincialTaxExempt( FALSE );

		$pd_obj->setYearToDateCPPContribution( 0 );
		$pd_obj->setYearToDateEIContribution( 0 );

		$pd_obj->setGrossPayPeriodIncome( 1900.00 ); //Less than EI/CPP maximum earnings for the year.

		//var_dump($pd_obj->getArray());

		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '1900.00' );
		$this->assertEquals( $this->mf( $pd_obj->getEmployeeCPP() ), '87.39' );
		$this->assertEquals( $this->mf( $pd_obj->getEmployerCPP() ), '87.39' );
		$this->assertEquals( $this->mf( $pd_obj->getEmployeeEI() ), '31.54' );
		$this->assertEquals( $this->mf( $pd_obj->getEmployerEI() ), '44.16' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '198.06' );
		$this->assertEquals( $this->mf( $pd_obj->getProvincialPayPeriodDeductions() ), '79.75' );
	}

	function testCA_2018a_RRSP() {
		Debug::text('CA - RRSP Contribution - Beginning of 01-Jan-2018: ', __FILE__, __LINE__, __METHOD__, 10);

		$pd_obj = new PayrollDeduction('CA', 'ON');
		$pd_obj->setDate(strtotime('01-Jan-2018'));
		$pd_obj->setEnableCPPAndEIDeduction(TRUE); //Deduct CPP/EI.
		$pd_obj->setAnnualPayPeriods( 26 );

		$pd_obj->setFederalTotalClaimAmount( 11809 );
		$pd_obj->setProvincialTotalClaimAmount( 10171 );
		//$pd_obj->setWCBRate( 0.18 );

		$pd_obj->setEIExempt( FALSE );
		$pd_obj->setCPPExempt( FALSE );

		$pd_obj->setFederalTaxExempt( FALSE );
		$pd_obj->setProvincialTaxExempt( FALSE );

		$pd_obj->setYearToDateCPPContribution( 0 );
		$pd_obj->setYearToDateEIContribution( 0 );


		//Gross=1600, RRSP=32.00
		$pd_obj->setGrossPayPeriodIncome( 1568 ); //Less the RRSP deduction of $32.

		$pd_obj->setEmployeeCPPForPayPeriod( 72.54 ); //Force CPP amount based on $1600 gross
		$pd_obj->setEmployeeEIForPayPeriod( 26.08 ); //Force EI amount based on $1600 gross

		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '1568.00' );
		$this->assertEquals( $this->mf( $pd_obj->getEmployeeCPP() ), '72.54' );
		$this->assertEquals( $this->mf( $pd_obj->getEmployeeEI() ), '26.08' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '145.38' );
		$this->assertEquals( $this->mf( $pd_obj->getProvincialPayPeriodDeductions() ), '71.40' );
	}
}
?>