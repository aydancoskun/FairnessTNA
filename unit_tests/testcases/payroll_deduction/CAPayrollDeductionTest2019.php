<?php

/**
 * @group CAPayrollDeductionTest2019
 */
class CAPayrollDeductionTest2019 extends PHPUnit\Framework\TestCase {
	public $company_id = NULL;

	public function setUp(): void {
		Debug::text('Running setUp(): ', __FILE__, __LINE__, __METHOD__, 10);

		require_once( Environment::getBasePath().'/classes/payroll_deduction/PayrollDeduction.class.php');

		$this->tax_table_file = dirname(__FILE__).'/CAPayrollDeductionTest2019.csv';

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
	// January 2019
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
						$this->assertLessThan( 3.5, $amount_diff_percent ); //Should be slightly higher than inflation.
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
					if ( $amount_diff > 5.50 ) {
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

	function testCA_2019a_Example() {
		Debug::text('CA - Example Beginning of 01-Jan-2019: ', __FILE__, __LINE__, __METHOD__, 10);

		$pd_obj = new PayrollDeduction('CA', 'AB');
		$pd_obj->setDate(strtotime('01-Jan-2019'));
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
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '75.48' );
		$this->assertEquals( $this->mf( $pd_obj->getProvincialPayPeriodDeductions() ), '65.83' );
	}

	function testCA_2019a_Example1() {
		Debug::text('CA - Example1 - Beginning of 01-Jan-2019: ', __FILE__, __LINE__, __METHOD__, 10);

		$pd_obj = new PayrollDeduction('CA', 'AB');
		$pd_obj->setDate(strtotime('01-Jan-2019'));
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
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '87.95' );
		$this->assertEquals( $this->mf( $pd_obj->getProvincialPayPeriodDeductions() ), '7.98' );
	}

	function testCA_2019a_Example2() {
		Debug::text('CA - Example2 - Beginning of 01-Jan-2019: ', __FILE__, __LINE__, __METHOD__, 10);

		$pd_obj = new PayrollDeduction('CA', 'AB');
		$pd_obj->setDate(strtotime('01-Jan-2019'));
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
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '273.23' );
		$this->assertEquals( $this->mf( $pd_obj->getProvincialPayPeriodDeductions() ), '141.62' );
	}

	function testCA_2019a_Example3() {
		Debug::text('CA - Example3 - Beginning of 01-Jan-2019: ', __FILE__, __LINE__, __METHOD__, 10);

		$pd_obj = new PayrollDeduction('CA', 'AB');
		$pd_obj->setDate(strtotime('01-Jan-2019'));
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
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '450.12' );
		$this->assertEquals( $this->mf( $pd_obj->getProvincialPayPeriodDeductions() ), '205.81' );
	}

	function testCA_2019a_Example4() {
		Debug::text('CA - Example1 - Beginning of 01-Jan-2019: ', __FILE__, __LINE__, __METHOD__, 10);

		$pd_obj = new PayrollDeduction('CA', 'BC');
		$pd_obj->setDate(strtotime('01-Jan-2019'));
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
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '142.63' );
		$this->assertEquals( $this->mf( $pd_obj->getProvincialPayPeriodDeductions() ), '53.19' );
	}

	function testCA_2019a_NS_Example1() {
		Debug::text('CA - Example1 - Beginning of 01-Jan-2019: ', __FILE__, __LINE__, __METHOD__, 10);

		$pd_obj = new PayrollDeduction('CA', 'NS');
		$pd_obj->setDate(strtotime('01-Jan-2019'));
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
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '20.84' );
		$this->assertEquals( $this->mf( $pd_obj->getProvincialPayPeriodDeductions() ), '16.35' );
	}

	function testCA_2019a_NS_Example2() {
		Debug::text('CA - Example1 - Beginning of 01-Jan-2019: ', __FILE__, __LINE__, __METHOD__, 10);

		$pd_obj = new PayrollDeduction('CA', 'NS');
		$pd_obj->setDate(strtotime('01-Jan-2019'));
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
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '268.04' );
		$this->assertEquals( $this->mf( $pd_obj->getProvincialPayPeriodDeductions() ), '172.87' );
	}

	function testCA_2019a_NS_Example3() {
		Debug::text('CA - Example1 - Beginning of 01-Jan-2019: ', __FILE__, __LINE__, __METHOD__, 10);

		$pd_obj = new PayrollDeduction('CA', 'NS');
		$pd_obj->setDate(strtotime('01-Jan-2019'));
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
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '2348.77' );
		$this->assertEquals( $this->mf( $pd_obj->getProvincialPayPeriodDeductions() ), '1637.39' );
	}

	function testCA_2019a_GovExample1() {
		Debug::text('CA - Example1 - Beginning of 01-Jan-2019: ', __FILE__, __LINE__, __METHOD__, 10);

		$pd_obj = new PayrollDeduction('CA', 'AB');
		$pd_obj->setDate(strtotime('01-Jan-2019'));
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
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '112.58' );
		$this->assertEquals( $this->mf( $pd_obj->getProvincialPayPeriodDeductions() ), '59.19' );
	}

	function testCA_2019a_GovExample2() {
		Debug::text('CA - Example1 - Beginning of 01-Jan-2019: ', __FILE__, __LINE__, __METHOD__, 10);

		$pd_obj = new PayrollDeduction('CA', 'BC');
		$pd_obj->setDate(strtotime('01-Jan-2019'));
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
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '112.58' );
		$this->assertEquals( $this->mf( $pd_obj->getProvincialPayPeriodDeductions() ), '44.92' );
	}

	function testCA_2019a_GovExample3() {
		Debug::text('CA - Example1 - Beginning of 01-Jan-2019: ', __FILE__, __LINE__, __METHOD__, 10);

		$pd_obj = new PayrollDeduction('CA', 'ON');
		$pd_obj->setDate(strtotime('01-Jan-2019'));
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
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '112.58' );
		$this->assertEquals( $this->mf( $pd_obj->getProvincialPayPeriodDeductions() ), '57.58' );
	}

	function testCA_2019a_GovExample4() {
		Debug::text('CA - Example1 - Beginning of 01-Jan-2019: ', __FILE__, __LINE__, __METHOD__, 10);

		$pd_obj = new PayrollDeduction('CA', 'PE');
		$pd_obj->setDate(strtotime('01-Jan-2019'));
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
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '112.58' );
		$this->assertEquals( $this->mf( $pd_obj->getProvincialPayPeriodDeductions() ), '93.85' );
	}

	//
	// CPP/ EI
	//
	function testCA_2019a_BiWeekly_CPP_LowIncome() {
		Debug::text('CA - BiWeekly - CPP - Beginning of 01-Jan-2019: ', __FILE__, __LINE__, __METHOD__, 10);

		$pd_obj = new PayrollDeduction('CA', 'BC');
		$pd_obj->setDate(strtotime('01-Jan-2019'));
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
		$this->assertEquals( $this->mf( $pd_obj->getEmployeeCPP() ), '22.99' );
		$this->assertEquals( $this->mf( $pd_obj->getEmployerCPP() ), '22.99' );
	}

	function testCA_2019a_SemiMonthly_CPP_LowIncome() {
		Debug::text('CA - BiWeekly - CPP - Beginning of 01-Jan-2019: ', __FILE__, __LINE__, __METHOD__, 10);

		$pd_obj = new PayrollDeduction('CA', 'BC');
		$pd_obj->setDate(strtotime('01-Jan-2019'));
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
		$this->assertEquals( $this->mf( $pd_obj->getEmployeeCPP() ), '22.41' );
		$this->assertEquals( $this->mf( $pd_obj->getEmployerCPP() ), '22.41' );
	}

	function testCA_2019a_SemiMonthly_MAXCPP_LowIncome() {
		Debug::text('CA - BiWeekly - MAXCPP - Beginning of 2019 01-Jan-2019: ', __FILE__, __LINE__, __METHOD__, 10);

		$pd_obj = new PayrollDeduction('CA', 'BC');
		$pd_obj->setDate(strtotime('01-Jan-2019'));
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

	function testCA_2019a_EI_LowIncome() {
		Debug::text('CA - EI - Beginning of 01-Jan-2019: ', __FILE__, __LINE__, __METHOD__, 10);

		$pd_obj = new PayrollDeduction('CA', 'BC');
		$pd_obj->setDate(strtotime('01-Jan-2019'));
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
		$this->assertEquals( $this->mf( $pd_obj->getEmployeeEI() ), '9.52' );
		$this->assertEquals( $this->mf( $pd_obj->getEmployerEI() ), '13.33' );
	}

	function testCA_2019a_MAXEI_LowIncome() {
		Debug::text('CA - MAXEI - Beginning of 01-Jan-2019: ', __FILE__, __LINE__, __METHOD__, 10);

		$pd_obj = new PayrollDeduction('CA', 'BC');
		$pd_obj->setDate(strtotime('01-Jan-2019'));
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


	function testCA_2019a_MAXEI_MAXCPPa() {
		Debug::text('CA - MAXEI/MAXCPP - Beginning of 01-Jan-2019: ', __FILE__, __LINE__, __METHOD__, 10);

		$pd_obj = new PayrollDeduction('CA', 'BC');
		$pd_obj->setDate(strtotime('10-Nov-2019'));
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
		$this->assertEquals( $this->mf( $pd_obj->getEmployeeCPP() ), '124.16' );
		$this->assertEquals( $this->mf( $pd_obj->getEmployerCPP() ), '124.16' );
		$this->assertEquals( $this->mf( $pd_obj->getEmployeeEI() ), '41.62' );
		$this->assertEquals( $this->mf( $pd_obj->getEmployerEI() ), '58.27' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '328.42' );
		$this->assertEquals( $this->mf( $pd_obj->getProvincialPayPeriodDeductions() ), '128.67' );
	}

	function testCA_2019a_MAXEI_MAXCPPb() {
		Debug::text('CA - MAXEI/MAXCPP - Beginning of 01-Jan-2019: ', __FILE__, __LINE__, __METHOD__, 10);

		$pd_obj = new PayrollDeduction('CA', 'BC');
		$pd_obj->setDate(strtotime('10-Nov-2019'));
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
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '328.42' );
		$this->assertEquals( $this->mf( $pd_obj->getProvincialPayPeriodDeductions() ), '128.67' );
	}

	function testCA_2019a_MAXEI_MAXCPPc() {
		Debug::text('CA - MAXEI/MAXCPP - Beginning of 01-Jan-2019: ', __FILE__, __LINE__, __METHOD__, 10);

		$pd_obj = new PayrollDeduction('CA', 'BC');
		$pd_obj->setDate(strtotime('10-Nov-2019'));
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
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '328.42' );
		$this->assertEquals( $this->mf( $pd_obj->getProvincialPayPeriodDeductions() ), '128.67' );
	}

	function testCA_2019a_MAXEI_MAXCPPd() {
		Debug::text('CA - MAXEI/MAXCPP - Beginning of 01-Jan-2019: ', __FILE__, __LINE__, __METHOD__, 10);

		$pd_obj = new PayrollDeduction('CA', 'BC');
		$pd_obj->setDate(strtotime('10-Nov-2019'));
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
		$this->assertEquals( $this->mf( $pd_obj->getEmployeeCPP() ), '90.03' );
		$this->assertEquals( $this->mf( $pd_obj->getEmployerCPP() ), '90.03' );
		$this->assertEquals( $this->mf( $pd_obj->getEmployeeEI() ), '30.78' );
		$this->assertEquals( $this->mf( $pd_obj->getEmployerEI() ), '43.09' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '193.93' );
		$this->assertEquals( $this->mf( $pd_obj->getProvincialPayPeriodDeductions() ), '78.05' );
	}

	function testCA_2019a_RRSP() {
		Debug::text('CA - RRSP Contribution - Beginning of 01-Jan-2019: ', __FILE__, __LINE__, __METHOD__, 10);

		$pd_obj = new PayrollDeduction('CA', 'ON');
		$pd_obj->setDate(strtotime('01-Jan-2019'));
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
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '143.73' );
		$this->assertEquals( $this->mf( $pd_obj->getProvincialPayPeriodDeductions() ), '70.96' );
	}
}
?>