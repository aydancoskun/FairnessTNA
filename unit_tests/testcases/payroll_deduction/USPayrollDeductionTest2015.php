<?php

/**
 * @group USPayrollDeductionTest2015
 */
class USPayrollDeductionTest2015 extends PHPUnit\Framework\TestCase {
	public $company_id = NULL;

	public function setUp(): void {
		Debug::text('Running setUp(): ', __FILE__, __LINE__, __METHOD__, 10);

		require_once( Environment::getBasePath().'/classes/payroll_deduction/PayrollDeduction.class.php');

		$this->tax_table_file = dirname(__FILE__).'/USPayrollDeductionTest2015.csv';

		$this->company_id = PRIMARY_COMPANY_ID;

		TTDate::setTimeZone('Etc/GMT+8'); //Force to non-DST timezone. 'PST' isnt actually valid.
	}

	public function tearDown(): void {
		Debug::text('Running tearDown(): ', __FILE__, __LINE__, __METHOD__, 10);
	}

	public function mf($amount) {
		return Misc::MoneyFormat($amount, FALSE);
	}

	public function MatchWithinMarginOfError( $source, $destination, $error = 0) {
		//Source: 125.01
		//Destination: 125.00
		//Source: 124.99
		$high_water_mark = bcadd($destination, $error);
		$low_water_mark = bcsub($destination, $error);

		if (  $source <= $high_water_mark AND $source >= $low_water_mark ) {
			return $destination;
		}

		return $source;
	}

	//
	// January 2015
	//
	function testCSVFile() {
		$this->assertEquals( file_exists($this->tax_table_file), TRUE);

		$test_rows = Misc::parseCSV( $this->tax_table_file, TRUE );

		$total_rows = (count($test_rows) + 1);
		$i = 2;
		foreach( $test_rows as $row ) {
			//Debug::text('Province: '. $row['province'] .' Income: '. $row['gross_income'], __FILE__, __LINE__, __METHOD__,10);
			if ( $row['gross_income'] == '' AND isset($row['low_income']) AND $row['low_income'] != '' AND isset($row['high_income']) AND $row['high_income'] != '' ) {
				$row['gross_income'] = ($row['low_income'] + ( ($row['high_income'] - $row['low_income']) / 2 ));
			}
			if ( $row['country'] != '' AND $row['gross_income'] != '' ) {
				//echo $i.'/'.$total_rows.'. Testing Province: '. $row['province'] .' Income: '. $row['gross_income'] ."\n";

				$pd_obj = new PayrollDeduction( $row['country'], $row['province'] );
				$pd_obj->setDate( strtotime( $row['date'] ) );
				$pd_obj->setAnnualPayPeriods( $row['pay_periods'] );

				$pd_obj->setFederalFilingStatus( $row['filing_status'] );
				$pd_obj->setFederalAllowance( $row['allowance'] );

				$pd_obj->setStateFilingStatus( $row['filing_status'] );
				$pd_obj->setStateAllowance( $row['allowance'] );

				//Some states use other values for allowance/deductions.
				switch ($row['province']) {
					case 'GA':
						Debug::text('Setting UserValue3: '. $row['allowance'], __FILE__, __LINE__, __METHOD__, 10);
						$pd_obj->setUserValue3( $row['allowance'] );
						break;
					case 'IN':
					case 'IL':
					case 'VA':
						Debug::text('Setting UserValue1: '. $row['allowance'], __FILE__, __LINE__, __METHOD__, 10);
						$pd_obj->setUserValue1( $row['allowance'] );
						break;
				}

				$pd_obj->setFederalTaxExempt( FALSE );
				$pd_obj->setProvincialTaxExempt( FALSE );

				$pd_obj->setGrossPayPeriodIncome( $this->mf( $row['gross_income'] ) );

				//var_dump($pd_obj->getArray());

				$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), $this->mf( $row['gross_income'] ) );
				if ( $row['federal_deduction'] != '' ) {
					$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), $this->MatchWithinMarginOfError( $this->mf( $row['federal_deduction'] ), $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), 0.01 ) );
				}
				if ( $row['provincial_deduction'] != '' ) {
					$this->assertEquals( $this->mf( $pd_obj->getStatePayPeriodDeductions() ), $this->mf( $row['provincial_deduction'] ) );
				}
			}

			$i++;
		}

		//Make sure all rows are tested.
		$this->assertEquals( $total_rows, ($i - 1));
	}


	function testUS_2015a_Test1() {
		Debug::text('US - SemiMonthly - Beginning of 2015 01-Jan-2015: ', __FILE__, __LINE__, __METHOD__, 10);

		$pd_obj = new PayrollDeduction('US', 'OR');
		$pd_obj->setDate(strtotime('01-Jan-2015'));
		$pd_obj->setAnnualPayPeriods( 26 ); //Semi-Monthly

		$pd_obj->setFederalFilingStatus( 10 ); //Single
		$pd_obj->setFederalAllowance( 0 );

		$pd_obj->setStateFilingStatus( 10 ); //Single
		$pd_obj->setStateAllowance( 0 );

		$pd_obj->setYearToDateSocialSecurityContribution( 0 );

		$pd_obj->setFederalTaxExempt( FALSE );
		$pd_obj->setProvincialTaxExempt( FALSE );

		$pd_obj->setGrossPayPeriodIncome( 576.923 );

		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '576.92' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '55.53' );
		$this->assertEquals( $this->mf( $pd_obj->getStatePayPeriodDeductions() ), '37.92' );
		$this->assertEquals( $this->mf( $pd_obj->getEmployeeSocialSecurity() ), '35.77' );
	}

	//
	// US Social Security
	//
	function testUS_2015a_SocialSecurity() {
		Debug::text('US - SemiMonthly - Beginning of 2015 01-Jan-2015: ', __FILE__, __LINE__, __METHOD__, 10);

		$pd_obj = new PayrollDeduction('US', 'MO');
		$pd_obj->setDate(strtotime('01-Jan-2015'));
		$pd_obj->setAnnualPayPeriods( 24 ); //Semi-Monthly

		$pd_obj->setFederalFilingStatus( 10 ); //Single
		$pd_obj->setFederalAllowance( 0 );

		$pd_obj->setYearToDateSocialSecurityContribution( 0 );

		$pd_obj->setFederalTaxExempt( FALSE );
		$pd_obj->setProvincialTaxExempt( FALSE );

		$pd_obj->setGrossPayPeriodIncome( 1000.00 );

		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '1000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getEmployeeSocialSecurity() ), '62.00' );
		$this->assertEquals( $this->mf( $pd_obj->getEmployerSocialSecurity() ), '62.00' );
	}

	function testUS_2015a_SocialSecurity_Max() {
		Debug::text('US - SemiMonthly - Beginning of 2015 01-Jan-2015: ', __FILE__, __LINE__, __METHOD__, 10);

		$pd_obj = new PayrollDeduction('US', 'MO');
		$pd_obj->setDate(strtotime('01-Jan-2015'));
		$pd_obj->setAnnualPayPeriods( 24 ); //Semi-Monthly

		$pd_obj->setFederalFilingStatus( 10 ); //Single
		$pd_obj->setFederalAllowance( 0 );

		$pd_obj->setYearToDateSocialSecurityContribution( 7346.00 ); //7347

		$pd_obj->setFederalTaxExempt( FALSE );
		$pd_obj->setProvincialTaxExempt( FALSE );

		$pd_obj->setGrossPayPeriodIncome( 1000.00 );

		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '1000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getEmployeeSocialSecurity() ), '1.00' );
		$this->assertEquals( $this->mf( $pd_obj->getEmployerSocialSecurity() ), '1.00' );
	}

	function testUS_2015a_Medicare() {
		Debug::text('US - SemiMonthly - Beginning of 2015 01-Jan-2015: ', __FILE__, __LINE__, __METHOD__, 10);

		$pd_obj = new PayrollDeduction('US', 'MO');
		$pd_obj->setDate(strtotime('01-Jan-2015'));
		$pd_obj->setAnnualPayPeriods( 24 ); //Semi-Monthly

		$pd_obj->setFederalFilingStatus( 10 ); //Single
		$pd_obj->setFederalAllowance( 0 );

		$pd_obj->setYearToDateSocialSecurityContribution( 0 );

		$pd_obj->setFederalTaxExempt( FALSE );
		$pd_obj->setProvincialTaxExempt( FALSE );

		$pd_obj->setGrossPayPeriodIncome( 1000.00 );

		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '1000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getEmployeeMedicare() ), '14.50' );
		$this->assertEquals( $this->mf( $pd_obj->getEmployerMedicare() ), '14.50' );
	}
	function testUS_2015a_Additional_MedicareA() {
		Debug::text('US - SemiMonthly - Beginning of 2015 01-Jan-2015: ', __FILE__, __LINE__, __METHOD__, 10);

		$pd_obj = new PayrollDeduction('US', 'MO');
		$pd_obj->setDate(strtotime('01-Jan-2015'));
		$pd_obj->setAnnualPayPeriods( 24 ); //Semi-Monthly

		$pd_obj->setFederalFilingStatus( 10 ); //Single
		$pd_obj->setFederalAllowance( 0 );

		$pd_obj->setYearToDateSocialSecurityContribution( 0 );

		$pd_obj->setFederalTaxExempt( FALSE );
		$pd_obj->setProvincialTaxExempt( FALSE );


		$pd_obj->setYearToDateGrossIncome( 199000.00 );
		$pd_obj->setGrossPayPeriodIncome( 1000.00 );

		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '1000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getEmployeeMedicare() ), '14.50' );
		$this->assertEquals( $this->mf( $pd_obj->getEmployerMedicare() ), '14.50' );
	}
	function testUS_2015a_Additional_MedicareB() {
		Debug::text('US - SemiMonthly - Beginning of 2015 01-Jan-2015: ', __FILE__, __LINE__, __METHOD__, 10);

		$pd_obj = new PayrollDeduction('US', 'MO');
		$pd_obj->setDate(strtotime('01-Jan-2015'));
		$pd_obj->setAnnualPayPeriods( 24 ); //Semi-Monthly

		$pd_obj->setFederalFilingStatus( 10 ); //Single
		$pd_obj->setFederalAllowance( 0 );

		$pd_obj->setYearToDateSocialSecurityContribution( 0 );

		$pd_obj->setFederalTaxExempt( FALSE );
		$pd_obj->setProvincialTaxExempt( FALSE );


		$pd_obj->setYearToDateGrossIncome( 199500.00 );
		$pd_obj->setGrossPayPeriodIncome( 1000.00 );

		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '1000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getEmployeeMedicare() ), '19.00' );
		$this->assertEquals( $this->mf( $pd_obj->getEmployerMedicare() ), '14.50' );
	}

	function testUS_2015a_Additional_MedicareC() {
		Debug::text('US - SemiMonthly - Beginning of 2015 01-Jan-2015: ', __FILE__, __LINE__, __METHOD__, 10);

		$pd_obj = new PayrollDeduction('US', 'MO');
		$pd_obj->setDate(strtotime('01-Jan-2015'));
		$pd_obj->setAnnualPayPeriods( 24 ); //Semi-Monthly

		$pd_obj->setFederalFilingStatus( 10 ); //Single
		$pd_obj->setFederalAllowance( 0 );

		$pd_obj->setYearToDateSocialSecurityContribution( 0 );

		$pd_obj->setFederalTaxExempt( FALSE );
		$pd_obj->setProvincialTaxExempt( FALSE );


		$pd_obj->setYearToDateGrossIncome( 500000.00 );
		$pd_obj->setGrossPayPeriodIncome( 1000.00 );

		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '1000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getEmployeeMedicare() ), '23.50' );
		$this->assertEquals( $this->mf( $pd_obj->getEmployerMedicare() ), '14.50' );
	}
	function testUS_2015a_Additional_MedicareD() {
		Debug::text('US - SemiMonthly - Beginning of 2015 01-Jan-2015: ', __FILE__, __LINE__, __METHOD__, 10);

		$pd_obj = new PayrollDeduction('US', 'MO');
		$pd_obj->setDate(strtotime('01-Jan-2015'));
		$pd_obj->setAnnualPayPeriods( 24 ); //Semi-Monthly

		$pd_obj->setFederalFilingStatus( 10 ); //Single
		$pd_obj->setFederalAllowance( 0 );

		$pd_obj->setYearToDateSocialSecurityContribution( 0 );

		$pd_obj->setFederalTaxExempt( FALSE );
		$pd_obj->setProvincialTaxExempt( FALSE );


		$pd_obj->setYearToDateGrossIncome( 0 );
		$pd_obj->setGrossPayPeriodIncome( 500000.00 );

		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '500000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getEmployeeMedicare() ), '9950.00' );
		$this->assertEquals( $this->mf( $pd_obj->getEmployerMedicare() ), '7250.00' );
	}

	function testUS_2015a_FederalUI_NoState() {
		Debug::text('US - SemiMonthly - Beginning of 2015 01-Jan-2015: ', __FILE__, __LINE__, __METHOD__, 10);

		$pd_obj = new PayrollDeduction('US', 'MO');
		$pd_obj->setDate(strtotime('01-Jan-2015'));
		$pd_obj->setAnnualPayPeriods( 24 ); //Semi-Monthly

		$pd_obj->setFederalFilingStatus( 10 ); //Single
		$pd_obj->setFederalAllowance( 0 );

		$pd_obj->setYearToDateSocialSecurityContribution( 0 );
		$pd_obj->setYearToDateFederalUIContribution( 0 );

		$pd_obj->setFederalTaxExempt( FALSE );
		$pd_obj->setProvincialTaxExempt( FALSE );

		$pd_obj->setGrossPayPeriodIncome( 1000.00 );

		//var_dump($pd_obj->getArray());

		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '1000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalEmployerUI() ), '60.00' );
	}

	function testUS_2015a_FederalUI_NoState_Max() {
		Debug::text('US - SemiMonthly - Beginning of 2015 01-Jan-2015: ', __FILE__, __LINE__, __METHOD__, 10);

		$pd_obj = new PayrollDeduction('US', 'MO');
		$pd_obj->setDate(strtotime('01-Jan-2015'));
		$pd_obj->setAnnualPayPeriods( 24 ); //Semi-Monthly

		$pd_obj->setFederalFilingStatus( 10 ); //Single
		$pd_obj->setFederalAllowance( 0 );

		$pd_obj->setStateUIRate( 0 );
		$pd_obj->setStateUIWageBase( 0 );

		$pd_obj->setYearToDateSocialSecurityContribution( 0 );
		$pd_obj->setYearToDateFederalUIContribution( 419 ); //420
		$pd_obj->setYearToDateStateUIContribution( 0 );

		$pd_obj->setFederalTaxExempt( FALSE );
		$pd_obj->setProvincialTaxExempt( FALSE );

		$pd_obj->setGrossPayPeriodIncome( 1000.00 );

		//var_dump($pd_obj->getArray());

		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '1000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalEmployerUI() ), '1.00' );
	}

	function testUS_2015a_FederalUI_State_Max() {
		Debug::text('US - SemiMonthly - Beginning of 2015 01-Jan-2015: ', __FILE__, __LINE__, __METHOD__, 10);

		$pd_obj = new PayrollDeduction('US', 'MO');
		$pd_obj->setDate(strtotime('01-Jan-2015'));
		$pd_obj->setAnnualPayPeriods( 24 ); //Semi-Monthly

		$pd_obj->setFederalFilingStatus( 10 ); //Single
		$pd_obj->setFederalAllowance( 0 );

		$pd_obj->setStateUIRate( 3.51 );
		$pd_obj->setStateUIWageBase( 11000 );

		$pd_obj->setYearToDateSocialSecurityContribution( 0 );
		$pd_obj->setYearToDateFederalUIContribution( 173.30 ); //174.30
		$pd_obj->setYearToDateStateUIContribution( 0 );

		$pd_obj->setFederalTaxExempt( FALSE );
		$pd_obj->setProvincialTaxExempt( FALSE );

		$pd_obj->setGrossPayPeriodIncome( 1000.00 );

		//var_dump($pd_obj->getArray());

		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '1000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalEmployerUI() ), '1.00' );
	}


	//
	//Test Periodic vs NonPeriodic formulas.
	//
	function testUS_2015_Federal_Periodic_FormulaA() {
		Debug::text('US - SemiMonthly - Beginning of 2015 01-Jan-2015: ', __FILE__, __LINE__, __METHOD__, 10);

		$pd_obj = new PayrollDeduction('US', 'MO');
		$pd_obj->setAnnualPayPeriods( 12 ); //Monthly
		$pd_obj->setFormulaType( 10 ); //Periodic
		$pd_obj->setFederalFilingStatus( 10 ); //Single
		$pd_obj->setFederalAllowance( 0 );
		$pd_obj->setFederalTaxExempt( FALSE );
		$pd_obj->setProvincialTaxExempt( FALSE );

		$current_pay_period = 1;
		$ytd_gross_income = 0;
		$ytd_deduction = 0;

		//PP1
		$pd_obj->setDate(strtotime('01-Jan-2015'));
		$pd_obj->setCurrentPayPeriod( $current_pay_period );
		$pd_obj->setYearToDateGrossIncome( $ytd_gross_income );
		$pd_obj->setYearToDateDeduction( $ytd_deduction );
		$pd_obj->setGrossPayPeriodIncome( 5000.00 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '5000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '851.56' );
		$current_pay_period++;
		$ytd_gross_income += $pd_obj->getGrossPayPeriodIncome();
		$ytd_deduction += $pd_obj->getFederalPayPeriodDeductions();

		//PP2
		$pd_obj->setDate(strtotime('01-Feb-2015'));
		$pd_obj->setCurrentPayPeriod( $current_pay_period );
		$pd_obj->setYearToDateGrossIncome( $ytd_gross_income );
		$pd_obj->setYearToDateDeduction( $ytd_deduction );
		$pd_obj->setGrossPayPeriodIncome( 5000.00 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '5000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '851.56' );
		$current_pay_period++;
		$ytd_gross_income += $pd_obj->getGrossPayPeriodIncome();
		$ytd_deduction += $pd_obj->getFederalPayPeriodDeductions();

		//PP3
		$pd_obj->setDate(strtotime('01-Mar-2015'));
		$pd_obj->setCurrentPayPeriod( $current_pay_period );
		$pd_obj->setYearToDateGrossIncome( $ytd_gross_income );
		$pd_obj->setYearToDateDeduction( $ytd_deduction );
		$pd_obj->setGrossPayPeriodIncome( 5000.00 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '5000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '851.56' );
		$current_pay_period++;
		$ytd_gross_income += $pd_obj->getGrossPayPeriodIncome();
		$ytd_deduction += $pd_obj->getFederalPayPeriodDeductions();

		//PP4
		$pd_obj->setDate(strtotime('01-Apr-2015'));
		$pd_obj->setCurrentPayPeriod( $current_pay_period );
		$pd_obj->setYearToDateGrossIncome( $ytd_gross_income );
		$pd_obj->setYearToDateDeduction( $ytd_deduction );
		$pd_obj->setGrossPayPeriodIncome( 5000.00 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '5000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '851.56' );
		$current_pay_period++;
		$ytd_gross_income += $pd_obj->getGrossPayPeriodIncome();
		$ytd_deduction += $pd_obj->getFederalPayPeriodDeductions();

		//PP5
		$pd_obj->setDate(strtotime('01-May-2015'));
		$pd_obj->setCurrentPayPeriod( $current_pay_period );
		$pd_obj->setYearToDateGrossIncome( $ytd_gross_income );
		$pd_obj->setYearToDateDeduction( $ytd_deduction );
		$pd_obj->setGrossPayPeriodIncome( 5000.00 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '5000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '851.56' );
		$current_pay_period++;
		$ytd_gross_income += $pd_obj->getGrossPayPeriodIncome();
		$ytd_deduction += $pd_obj->getFederalPayPeriodDeductions();

		//PP6
		$pd_obj->setDate(strtotime('01-Jun-2015'));
		$pd_obj->setCurrentPayPeriod( $current_pay_period );
		$pd_obj->setYearToDateGrossIncome( $ytd_gross_income );
		$pd_obj->setYearToDateDeduction( $ytd_deduction );
		$pd_obj->setGrossPayPeriodIncome( 5000.00 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '5000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '851.56' );
		$current_pay_period++;
		$ytd_gross_income += $pd_obj->getGrossPayPeriodIncome();
		$ytd_deduction += $pd_obj->getFederalPayPeriodDeductions();

		//PP7
		$pd_obj->setDate(strtotime('01-Jul-2015'));
		$pd_obj->setCurrentPayPeriod( $current_pay_period );
		$pd_obj->setYearToDateGrossIncome( $ytd_gross_income );
		$pd_obj->setYearToDateDeduction( $ytd_deduction );
		$pd_obj->setGrossPayPeriodIncome( 5000.00 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '5000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '851.56' );
		$current_pay_period++;
		$ytd_gross_income += $pd_obj->getGrossPayPeriodIncome();
		$ytd_deduction += $pd_obj->getFederalPayPeriodDeductions();

		//PP8
		$pd_obj->setDate(strtotime('01-Aug-2015'));
		$pd_obj->setCurrentPayPeriod( $current_pay_period );
		$pd_obj->setYearToDateGrossIncome( $ytd_gross_income );
		$pd_obj->setYearToDateDeduction( $ytd_deduction );
		$pd_obj->setGrossPayPeriodIncome( 5000.00 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '5000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '851.56' );
		$current_pay_period++;
		$ytd_gross_income += $pd_obj->getGrossPayPeriodIncome();
		$ytd_deduction += $pd_obj->getFederalPayPeriodDeductions();

		//PP9
		$pd_obj->setDate(strtotime('01-Sep-2015'));
		$pd_obj->setCurrentPayPeriod( $current_pay_period );
		$pd_obj->setYearToDateGrossIncome( $ytd_gross_income );
		$pd_obj->setYearToDateDeduction( $ytd_deduction );
		$pd_obj->setGrossPayPeriodIncome( 5000.00 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '5000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '851.56' );
		$current_pay_period++;
		$ytd_gross_income += $pd_obj->getGrossPayPeriodIncome();
		$ytd_deduction += $pd_obj->getFederalPayPeriodDeductions();

		//PP10
		$pd_obj->setDate(strtotime('01-Oct-2015'));
		$pd_obj->setCurrentPayPeriod( $current_pay_period );
		$pd_obj->setYearToDateGrossIncome( $ytd_gross_income );
		$pd_obj->setYearToDateDeduction( $ytd_deduction );
		$pd_obj->setGrossPayPeriodIncome( 5000.00 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '5000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '851.56' );
		$current_pay_period++;
		$ytd_gross_income += $pd_obj->getGrossPayPeriodIncome();
		$ytd_deduction += $pd_obj->getFederalPayPeriodDeductions();

		//PP11
		$pd_obj->setDate(strtotime('01-Nov-2015'));
		$pd_obj->setCurrentPayPeriod( $current_pay_period );
		$pd_obj->setYearToDateGrossIncome( $ytd_gross_income );
		$pd_obj->setYearToDateDeduction( $ytd_deduction );
		$pd_obj->setGrossPayPeriodIncome( 5000.00 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '5000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '851.56' );
		$current_pay_period++;
		$ytd_gross_income += $pd_obj->getGrossPayPeriodIncome();
		$ytd_deduction += $pd_obj->getFederalPayPeriodDeductions();

		//PP12
		$pd_obj->setDate(strtotime('01-Dec-2015'));
		$pd_obj->setCurrentPayPeriod( $current_pay_period );
		$pd_obj->setYearToDateGrossIncome( $ytd_gross_income );
		$pd_obj->setYearToDateDeduction( $ytd_deduction );
		$pd_obj->setGrossPayPeriodIncome( 5000.00 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '5000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '851.56' );
		$current_pay_period++;
		$ytd_gross_income += $pd_obj->getGrossPayPeriodIncome();
		$ytd_deduction += $pd_obj->getFederalPayPeriodDeductions();

		$this->assertEquals( $ytd_gross_income, 60000 );
		$this->assertEquals( $ytd_deduction, 10218.75 );

		//Actual Income/Deductions for the year.
		$pd_obj->setDate(strtotime('01-Dec-2015'));
		$pd_obj->setAnnualPayPeriods( 1 );
		$pd_obj->setCurrentPayPeriod( 1 );
		$pd_obj->setYearToDateGrossIncome( 0 );
		$pd_obj->setYearToDateDeduction( 0 );
		$pd_obj->setGrossPayPeriodIncome( 60000 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '60000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '10218.75' );
	}

	function testUS_2015_Federal_NonPeriodic_FormulaA() {
		Debug::text('US - SemiMonthly - Beginning of 2015 01-Jan-2015: ', __FILE__, __LINE__, __METHOD__, 10);

		$pd_obj = new PayrollDeduction('US', 'MO');
		$pd_obj->setAnnualPayPeriods( 12 ); //Monthly
		$pd_obj->setFormulaType( 20 ); //Periodic
		$pd_obj->setFederalFilingStatus( 10 ); //Single
		$pd_obj->setFederalAllowance( 0 );
		$pd_obj->setFederalTaxExempt( FALSE );
		$pd_obj->setProvincialTaxExempt( FALSE );

		$current_pay_period = 1;
		$ytd_gross_income = 0;
		$ytd_deduction = 0;

		//PP1
		$pd_obj->setDate(strtotime('01-Jan-2015'));
		$pd_obj->setCurrentPayPeriod( $current_pay_period );
		$pd_obj->setYearToDateGrossIncome( $ytd_gross_income );
		$pd_obj->setYearToDateDeduction( $ytd_deduction );
		$pd_obj->setGrossPayPeriodIncome( 5000.00 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '5000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '851.56' );
		$current_pay_period++;
		$ytd_gross_income += $pd_obj->getGrossPayPeriodIncome();
		$ytd_deduction += $pd_obj->getFederalPayPeriodDeductions();

		//PP2
		$pd_obj->setDate(strtotime('01-Feb-2015'));
		$pd_obj->setCurrentPayPeriod( $current_pay_period );
		$pd_obj->setYearToDateGrossIncome( $ytd_gross_income );
		$pd_obj->setYearToDateDeduction( $ytd_deduction );
		$pd_obj->setGrossPayPeriodIncome( 5000.00 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '5000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '851.56' );
		$current_pay_period++;
		$ytd_gross_income += $pd_obj->getGrossPayPeriodIncome();
		$ytd_deduction += $pd_obj->getFederalPayPeriodDeductions();

		//PP3
		$pd_obj->setDate(strtotime('01-Mar-2015'));
		$pd_obj->setCurrentPayPeriod( $current_pay_period );
		$pd_obj->setYearToDateGrossIncome( $ytd_gross_income );
		$pd_obj->setYearToDateDeduction( $ytd_deduction );
		$pd_obj->setGrossPayPeriodIncome( 5000.00 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '5000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '851.56' );
		$current_pay_period++;
		$ytd_gross_income += $pd_obj->getGrossPayPeriodIncome();
		$ytd_deduction += $pd_obj->getFederalPayPeriodDeductions();

		//PP4
		$pd_obj->setDate(strtotime('01-Apr-2015'));
		$pd_obj->setCurrentPayPeriod( $current_pay_period );
		$pd_obj->setYearToDateGrossIncome( $ytd_gross_income );
		$pd_obj->setYearToDateDeduction( $ytd_deduction );
		$pd_obj->setGrossPayPeriodIncome( 5000.00 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '5000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '851.56' );
		$current_pay_period++;
		$ytd_gross_income += $pd_obj->getGrossPayPeriodIncome();
		$ytd_deduction += $pd_obj->getFederalPayPeriodDeductions();

		//PP5
		$pd_obj->setDate(strtotime('01-May-2015'));
		$pd_obj->setCurrentPayPeriod( $current_pay_period );
		$pd_obj->setYearToDateGrossIncome( $ytd_gross_income );
		$pd_obj->setYearToDateDeduction( $ytd_deduction );
		$pd_obj->setGrossPayPeriodIncome( 5000.00 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '5000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '851.56' );
		$current_pay_period++;
		$ytd_gross_income += $pd_obj->getGrossPayPeriodIncome();
		$ytd_deduction += $pd_obj->getFederalPayPeriodDeductions();

		//PP6
		$pd_obj->setDate(strtotime('01-Jun-2015'));
		$pd_obj->setCurrentPayPeriod( $current_pay_period );
		$pd_obj->setYearToDateGrossIncome( $ytd_gross_income );
		$pd_obj->setYearToDateDeduction( $ytd_deduction );
		$pd_obj->setGrossPayPeriodIncome( 5000.00 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '5000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '851.56' );
		$current_pay_period++;
		$ytd_gross_income += $pd_obj->getGrossPayPeriodIncome();
		$ytd_deduction += $pd_obj->getFederalPayPeriodDeductions();

		//PP7
		$pd_obj->setDate(strtotime('01-Jul-2015'));
		$pd_obj->setCurrentPayPeriod( $current_pay_period );
		$pd_obj->setYearToDateGrossIncome( $ytd_gross_income );
		$pd_obj->setYearToDateDeduction( $ytd_deduction );
		$pd_obj->setGrossPayPeriodIncome( 5000.00 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '5000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '851.56' );
		$current_pay_period++;
		$ytd_gross_income += $pd_obj->getGrossPayPeriodIncome();
		$ytd_deduction += $pd_obj->getFederalPayPeriodDeductions();

		//PP8
		$pd_obj->setDate(strtotime('01-Aug-2015'));
		$pd_obj->setCurrentPayPeriod( $current_pay_period );
		$pd_obj->setYearToDateGrossIncome( $ytd_gross_income );
		$pd_obj->setYearToDateDeduction( $ytd_deduction );
		$pd_obj->setGrossPayPeriodIncome( 5000.00 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '5000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '851.56' );
		$current_pay_period++;
		$ytd_gross_income += $pd_obj->getGrossPayPeriodIncome();
		$ytd_deduction += $pd_obj->getFederalPayPeriodDeductions();

		//PP9
		$pd_obj->setDate(strtotime('01-Sep-2015'));
		$pd_obj->setCurrentPayPeriod( $current_pay_period );
		$pd_obj->setYearToDateGrossIncome( $ytd_gross_income );
		$pd_obj->setYearToDateDeduction( $ytd_deduction );
		$pd_obj->setGrossPayPeriodIncome( 5000.00 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '5000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '851.56' );
		$current_pay_period++;
		$ytd_gross_income += $pd_obj->getGrossPayPeriodIncome();
		$ytd_deduction += $pd_obj->getFederalPayPeriodDeductions();

		//PP10
		$pd_obj->setDate(strtotime('01-Oct-2015'));
		$pd_obj->setCurrentPayPeriod( $current_pay_period );
		$pd_obj->setYearToDateGrossIncome( $ytd_gross_income );
		$pd_obj->setYearToDateDeduction( $ytd_deduction );
		$pd_obj->setGrossPayPeriodIncome( 5000.00 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '5000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '851.56' );
		$current_pay_period++;
		$ytd_gross_income += $pd_obj->getGrossPayPeriodIncome();
		$ytd_deduction += $pd_obj->getFederalPayPeriodDeductions();

		//PP11
		$pd_obj->setDate(strtotime('01-Nov-2015'));
		$pd_obj->setCurrentPayPeriod( $current_pay_period );
		$pd_obj->setYearToDateGrossIncome( $ytd_gross_income );
		$pd_obj->setYearToDateDeduction( $ytd_deduction );
		$pd_obj->setGrossPayPeriodIncome( 5000.00 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '5000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '851.56' );
		$current_pay_period++;
		$ytd_gross_income += $pd_obj->getGrossPayPeriodIncome();
		$ytd_deduction += $pd_obj->getFederalPayPeriodDeductions();

		//PP12
		$pd_obj->setDate(strtotime('01-Dec-2015'));
		$pd_obj->setCurrentPayPeriod( $current_pay_period );
		$pd_obj->setYearToDateGrossIncome( $ytd_gross_income );
		$pd_obj->setYearToDateDeduction( $ytd_deduction );
		$pd_obj->setGrossPayPeriodIncome( 5000.00 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '5000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '851.56' );
		$current_pay_period++;
		$ytd_gross_income += $pd_obj->getGrossPayPeriodIncome();
		$ytd_deduction += $pd_obj->getFederalPayPeriodDeductions();

		$this->assertEquals( $ytd_gross_income, 60000 );
		$this->assertEquals( $ytd_deduction, 10218.75 );

		//Actual Income/Deductions for the year.
		$pd_obj->setDate(strtotime('01-Dec-2015'));
		$pd_obj->setAnnualPayPeriods( 1 );
		$pd_obj->setCurrentPayPeriod( 1 );
		$pd_obj->setYearToDateGrossIncome( 0 );
		$pd_obj->setYearToDateDeduction( 0 );
		$pd_obj->setGrossPayPeriodIncome( 60000 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '60000' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '10218.75' );
	}

	function testUS_2015_Federal_Periodic_FormulaB() {
		Debug::text('US - SemiMonthly - Beginning of 2015 01-Jan-2015: ', __FILE__, __LINE__, __METHOD__, 10);

		$pd_obj = new PayrollDeduction('US', 'MO');
		$pd_obj->setAnnualPayPeriods( 12 ); //Monthly
		$pd_obj->setFormulaType( 10 ); //Periodic
		$pd_obj->setFederalFilingStatus( 10 ); //Single
		$pd_obj->setFederalAllowance( 0 );
		$pd_obj->setFederalTaxExempt( FALSE );
		$pd_obj->setProvincialTaxExempt( FALSE );

		$current_pay_period = 1;
		$ytd_gross_income = 0;
		$ytd_deduction = 0;

		//PP1
		$pd_obj->setDate(strtotime('01-Jan-2015'));
		$pd_obj->setCurrentPayPeriod( $current_pay_period );
		$pd_obj->setYearToDateGrossIncome( $ytd_gross_income );
		$pd_obj->setYearToDateDeduction( $ytd_deduction );
		$pd_obj->setGrossPayPeriodIncome( 5000.00 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '5000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '851.56' );
		$current_pay_period++;
		$ytd_gross_income += $pd_obj->getGrossPayPeriodIncome();
		$ytd_deduction += $pd_obj->getFederalPayPeriodDeductions();

		//PP2
		$pd_obj->setDate(strtotime('01-Feb-2015'));
		$pd_obj->setCurrentPayPeriod( $current_pay_period );
		$pd_obj->setYearToDateGrossIncome( $ytd_gross_income );
		$pd_obj->setYearToDateDeduction( $ytd_deduction );
		$pd_obj->setGrossPayPeriodIncome( 1000.00 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '1000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '82.81' );
		$current_pay_period++;
		$ytd_gross_income += $pd_obj->getGrossPayPeriodIncome();
		$ytd_deduction += $pd_obj->getFederalPayPeriodDeductions();

		//PP3
		$pd_obj->setDate(strtotime('01-Mar-2015'));
		$pd_obj->setCurrentPayPeriod( $current_pay_period );
		$pd_obj->setYearToDateGrossIncome( $ytd_gross_income );
		$pd_obj->setYearToDateDeduction( $ytd_deduction );
		$pd_obj->setGrossPayPeriodIncome( 5000.00 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '5000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '851.56' );
		$current_pay_period++;
		$ytd_gross_income += $pd_obj->getGrossPayPeriodIncome();
		$ytd_deduction += $pd_obj->getFederalPayPeriodDeductions();

		//PP4
		$pd_obj->setDate(strtotime('01-Apr-2015'));
		$pd_obj->setCurrentPayPeriod( $current_pay_period );
		$pd_obj->setYearToDateGrossIncome( $ytd_gross_income );
		$pd_obj->setYearToDateDeduction( $ytd_deduction );
		$pd_obj->setGrossPayPeriodIncome( 1000.00 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '1000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '82.81' );
		$current_pay_period++;
		$ytd_gross_income += $pd_obj->getGrossPayPeriodIncome();
		$ytd_deduction += $pd_obj->getFederalPayPeriodDeductions();

		//PP5
		$pd_obj->setDate(strtotime('01-May-2015'));
		$pd_obj->setCurrentPayPeriod( $current_pay_period );
		$pd_obj->setYearToDateGrossIncome( $ytd_gross_income );
		$pd_obj->setYearToDateDeduction( $ytd_deduction );
		$pd_obj->setGrossPayPeriodIncome( 5000.00 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '5000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '851.56' );
		$current_pay_period++;
		$ytd_gross_income += $pd_obj->getGrossPayPeriodIncome();
		$ytd_deduction += $pd_obj->getFederalPayPeriodDeductions();

		//PP6
		$pd_obj->setDate(strtotime('01-Jun-2015'));
		$pd_obj->setCurrentPayPeriod( $current_pay_period );
		$pd_obj->setYearToDateGrossIncome( $ytd_gross_income );
		$pd_obj->setYearToDateDeduction( $ytd_deduction );
		$pd_obj->setGrossPayPeriodIncome( 1000.00 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '1000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '82.81' );
		$current_pay_period++;
		$ytd_gross_income += $pd_obj->getGrossPayPeriodIncome();
		$ytd_deduction += $pd_obj->getFederalPayPeriodDeductions();

		//PP7
		$pd_obj->setDate(strtotime('01-Jul-2015'));
		$pd_obj->setCurrentPayPeriod( $current_pay_period );
		$pd_obj->setYearToDateGrossIncome( $ytd_gross_income );
		$pd_obj->setYearToDateDeduction( $ytd_deduction );
		$pd_obj->setGrossPayPeriodIncome( 5000.00 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '5000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '851.56' );
		$current_pay_period++;
		$ytd_gross_income += $pd_obj->getGrossPayPeriodIncome();
		$ytd_deduction += $pd_obj->getFederalPayPeriodDeductions();

		//PP8
		$pd_obj->setDate(strtotime('01-Aug-2015'));
		$pd_obj->setCurrentPayPeriod( $current_pay_period );
		$pd_obj->setYearToDateGrossIncome( $ytd_gross_income );
		$pd_obj->setYearToDateDeduction( $ytd_deduction );
		$pd_obj->setGrossPayPeriodIncome( 1000.00 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '1000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '82.81' );
		$current_pay_period++;
		$ytd_gross_income += $pd_obj->getGrossPayPeriodIncome();
		$ytd_deduction += $pd_obj->getFederalPayPeriodDeductions();

		//PP9
		$pd_obj->setDate(strtotime('01-Sep-2015'));
		$pd_obj->setCurrentPayPeriod( $current_pay_period );
		$pd_obj->setYearToDateGrossIncome( $ytd_gross_income );
		$pd_obj->setYearToDateDeduction( $ytd_deduction );
		$pd_obj->setGrossPayPeriodIncome( 5000.00 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '5000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '851.56' );
		$current_pay_period++;
		$ytd_gross_income += $pd_obj->getGrossPayPeriodIncome();
		$ytd_deduction += $pd_obj->getFederalPayPeriodDeductions();

		//PP10
		$pd_obj->setDate(strtotime('01-Oct-2015'));
		$pd_obj->setCurrentPayPeriod( $current_pay_period );
		$pd_obj->setYearToDateGrossIncome( $ytd_gross_income );
		$pd_obj->setYearToDateDeduction( $ytd_deduction );
		$pd_obj->setGrossPayPeriodIncome( 1000.00 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '1000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '82.81' );
		$current_pay_period++;
		$ytd_gross_income += $pd_obj->getGrossPayPeriodIncome();
		$ytd_deduction += $pd_obj->getFederalPayPeriodDeductions();

		//PP11
		$pd_obj->setDate(strtotime('01-Nov-2015'));
		$pd_obj->setCurrentPayPeriod( $current_pay_period );
		$pd_obj->setYearToDateGrossIncome( $ytd_gross_income );
		$pd_obj->setYearToDateDeduction( $ytd_deduction );
		$pd_obj->setGrossPayPeriodIncome( 5000.00 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '5000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '851.56' );
		$current_pay_period++;
		$ytd_gross_income += $pd_obj->getGrossPayPeriodIncome();
		$ytd_deduction += $pd_obj->getFederalPayPeriodDeductions();

		//PP12
		$pd_obj->setDate(strtotime('01-Dec-2015'));
		$pd_obj->setCurrentPayPeriod( $current_pay_period );
		$pd_obj->setYearToDateGrossIncome( $ytd_gross_income );
		$pd_obj->setYearToDateDeduction( $ytd_deduction );
		$pd_obj->setGrossPayPeriodIncome( 1000.00 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '1000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '82.81' );
		$current_pay_period++;
		$ytd_gross_income += $pd_obj->getGrossPayPeriodIncome();
		$ytd_deduction += $pd_obj->getFederalPayPeriodDeductions();

		//Total Income/Deductions in the year.
		$this->assertEquals( $ytd_gross_income, 36000 );
		$this->assertEquals( $ytd_deduction, 5606.25 );

		//Actual Income/Deductions for the year.
		$pd_obj->setDate(strtotime('01-Dec-2015'));
		$pd_obj->setAnnualPayPeriods( 1 );
		$pd_obj->setCurrentPayPeriod( 1 );
		$pd_obj->setYearToDateGrossIncome( 0 );
		$pd_obj->setYearToDateDeduction( 0 );
		$pd_obj->setGrossPayPeriodIncome( 36000 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '36000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '4593.75' );
	}

	function testUS_2015_Federal_NonPeriodic_FormulaB() {
		Debug::text('US - SemiMonthly - Beginning of 2015 01-Jan-2015: ', __FILE__, __LINE__, __METHOD__, 10);

		$pd_obj = new PayrollDeduction('US', 'MO');
		$pd_obj->setAnnualPayPeriods( 12 ); //Monthly
		$pd_obj->setFormulaType( 20 ); //Periodic
		$pd_obj->setFederalFilingStatus( 10 ); //Single
		$pd_obj->setFederalAllowance( 0 );
		$pd_obj->setFederalTaxExempt( FALSE );
		$pd_obj->setProvincialTaxExempt( FALSE );

		$current_pay_period = 1;
		$ytd_gross_income = 0;
		$ytd_deduction = 0;

		//PP1
		$pd_obj->setDate(strtotime('01-Jan-2015'));
		$pd_obj->setCurrentPayPeriod( $current_pay_period );
		$pd_obj->setYearToDateGrossIncome( $ytd_gross_income );
		$pd_obj->setYearToDateDeduction( $ytd_deduction );
		$pd_obj->setGrossPayPeriodIncome( 5000.00 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '5000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '851.56' );
		$current_pay_period++;
		$ytd_gross_income += $pd_obj->getGrossPayPeriodIncome();
		$ytd_deduction += $pd_obj->getFederalPayPeriodDeductions();

		//PP2
		$pd_obj->setDate(strtotime('01-Feb-2015'));
		$pd_obj->setCurrentPayPeriod( $current_pay_period );
		$pd_obj->setYearToDateGrossIncome( $ytd_gross_income );
		$pd_obj->setYearToDateDeduction( $ytd_deduction );
		$pd_obj->setGrossPayPeriodIncome( 1000.00 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '1000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '0.00' );
		$current_pay_period++;
		$ytd_gross_income += $pd_obj->getGrossPayPeriodIncome();
		$ytd_deduction += $pd_obj->getFederalPayPeriodDeductions();

		//PP3
		$pd_obj->setDate(strtotime('01-Mar-2015'));
		$pd_obj->setCurrentPayPeriod( $current_pay_period );
		$pd_obj->setYearToDateGrossIncome( $ytd_gross_income );
		$pd_obj->setYearToDateDeduction( $ytd_deduction );
		$pd_obj->setGrossPayPeriodIncome( 5000.00 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '5000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '703.13' );
		$current_pay_period++;
		$ytd_gross_income += $pd_obj->getGrossPayPeriodIncome();
		$ytd_deduction += $pd_obj->getFederalPayPeriodDeductions();

		//PP4
		$pd_obj->setDate(strtotime('01-Apr-2015'));
		$pd_obj->setCurrentPayPeriod( $current_pay_period );
		$pd_obj->setYearToDateGrossIncome( $ytd_gross_income );
		$pd_obj->setYearToDateDeduction( $ytd_deduction );
		$pd_obj->setGrossPayPeriodIncome( 1000.00 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '1000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '0.00' );
		$current_pay_period++;
		$ytd_gross_income += $pd_obj->getGrossPayPeriodIncome();
		$ytd_deduction += $pd_obj->getFederalPayPeriodDeductions();

		//PP5
		$pd_obj->setDate(strtotime('01-May-2015'));
		$pd_obj->setCurrentPayPeriod( $current_pay_period );
		$pd_obj->setYearToDateGrossIncome( $ytd_gross_income );
		$pd_obj->setYearToDateDeduction( $ytd_deduction );
		$pd_obj->setGrossPayPeriodIncome( 5000.00 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '5000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '703.12' );
		$current_pay_period++;
		$ytd_gross_income += $pd_obj->getGrossPayPeriodIncome();
		$ytd_deduction += $pd_obj->getFederalPayPeriodDeductions();

		//PP6
		$pd_obj->setDate(strtotime('01-Jun-2015'));
		$pd_obj->setCurrentPayPeriod( $current_pay_period );
		$pd_obj->setYearToDateGrossIncome( $ytd_gross_income );
		$pd_obj->setYearToDateDeduction( $ytd_deduction );
		$pd_obj->setGrossPayPeriodIncome( 1000.00 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '1000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '39.06' );
		$current_pay_period++;
		$ytd_gross_income += $pd_obj->getGrossPayPeriodIncome();
		$ytd_deduction += $pd_obj->getFederalPayPeriodDeductions();

		//PP7
		$pd_obj->setDate(strtotime('01-Jul-2015'));
		$pd_obj->setCurrentPayPeriod( $current_pay_period );
		$pd_obj->setYearToDateGrossIncome( $ytd_gross_income );
		$pd_obj->setYearToDateDeduction( $ytd_deduction );
		$pd_obj->setGrossPayPeriodIncome( 5000.00 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '5000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '682.81' );
		$current_pay_period++;
		$ytd_gross_income += $pd_obj->getGrossPayPeriodIncome();
		$ytd_deduction += $pd_obj->getFederalPayPeriodDeductions();

		//PP8
		$pd_obj->setDate(strtotime('01-Aug-2015'));
		$pd_obj->setCurrentPayPeriod( $current_pay_period );
		$pd_obj->setYearToDateGrossIncome( $ytd_gross_income );
		$pd_obj->setYearToDateDeduction( $ytd_deduction );
		$pd_obj->setGrossPayPeriodIncome( 1000.00 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '1000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '82.81' );
		$current_pay_period++;
		$ytd_gross_income += $pd_obj->getGrossPayPeriodIncome();
		$ytd_deduction += $pd_obj->getFederalPayPeriodDeductions();

		//PP9
		$pd_obj->setDate(strtotime('01-Sep-2015'));
		$pd_obj->setCurrentPayPeriod( $current_pay_period );
		$pd_obj->setYearToDateGrossIncome( $ytd_gross_income );
		$pd_obj->setYearToDateDeduction( $ytd_deduction );
		$pd_obj->setGrossPayPeriodIncome( 5000.00 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '5000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '682.81' );
		$current_pay_period++;
		$ytd_gross_income += $pd_obj->getGrossPayPeriodIncome();
		$ytd_deduction += $pd_obj->getFederalPayPeriodDeductions();

		//PP10
		$pd_obj->setDate(strtotime('01-Oct-2015'));
		$pd_obj->setCurrentPayPeriod( $current_pay_period );
		$pd_obj->setYearToDateGrossIncome( $ytd_gross_income );
		$pd_obj->setYearToDateDeduction( $ytd_deduction );
		$pd_obj->setGrossPayPeriodIncome( 1000.00 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '1000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '82.81' );
		$current_pay_period++;
		$ytd_gross_income += $pd_obj->getGrossPayPeriodIncome();
		$ytd_deduction += $pd_obj->getFederalPayPeriodDeductions();

		//PP11
		$pd_obj->setDate(strtotime('01-Nov-2015'));
		$pd_obj->setCurrentPayPeriod( $current_pay_period );
		$pd_obj->setYearToDateGrossIncome( $ytd_gross_income );
		$pd_obj->setYearToDateDeduction( $ytd_deduction );
		$pd_obj->setGrossPayPeriodIncome( 5000.00 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '5000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '682.81' );
		$current_pay_period++;
		$ytd_gross_income += $pd_obj->getGrossPayPeriodIncome();
		$ytd_deduction += $pd_obj->getFederalPayPeriodDeductions();

		//PP12
		$pd_obj->setDate(strtotime('01-Dec-2015'));
		$pd_obj->setCurrentPayPeriod( $current_pay_period );
		$pd_obj->setYearToDateGrossIncome( $ytd_gross_income );
		$pd_obj->setYearToDateDeduction( $ytd_deduction );
		$pd_obj->setGrossPayPeriodIncome( 1000.00 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '1000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '82.81' );
		$current_pay_period++;
		$ytd_gross_income += $pd_obj->getGrossPayPeriodIncome();
		$ytd_deduction += $pd_obj->getFederalPayPeriodDeductions();

		//Total Income/Deductions in the year.
		$this->assertEquals( $ytd_gross_income, 36000 );
		$this->assertEquals( $ytd_deduction, 4593.75 );

		//Actual Income/Deductions for the year.
		$pd_obj->setDate(strtotime('01-Dec-2015'));
		$pd_obj->setAnnualPayPeriods( 1 );
		$pd_obj->setCurrentPayPeriod( 1 );
		$pd_obj->setYearToDateGrossIncome( 0 );
		$pd_obj->setYearToDateDeduction( 0 );
		$pd_obj->setGrossPayPeriodIncome( 36000 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '36000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getFederalPayPeriodDeductions() ), '4593.75' );
	}



	function testUS_2015_State_Periodic_FormulaA() {
		Debug::text('US - SemiMonthly - Beginning of 2015 01-Jan-2015: ', __FILE__, __LINE__, __METHOD__, 10);

		$pd_obj = new PayrollDeduction('US', 'MO');
		$pd_obj->setAnnualPayPeriods( 12 ); //Monthly
		$pd_obj->setFormulaType( 10 ); //Periodic
		$pd_obj->setStateFilingStatus( 10 ); //Single
		$pd_obj->setStateAllowance( 0 );
		$pd_obj->setProvincialTaxExempt( FALSE );

		$current_pay_period = 1;
		$ytd_gross_income = 0;
		$ytd_deduction = 0;

		//PP1
		$pd_obj->setDate(strtotime('01-Jan-2015'));
		$pd_obj->setCurrentPayPeriod( $current_pay_period );
		$pd_obj->setYearToDateGrossIncome( $ytd_gross_income );
		$pd_obj->setYearToDateDeduction( $ytd_deduction );
		$pd_obj->setGrossPayPeriodIncome( 5000.00 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '5000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getStatePayPeriodDeductions() ), '225.00' );
		$current_pay_period++;
		$ytd_gross_income += $pd_obj->getGrossPayPeriodIncome();
		$ytd_deduction += $pd_obj->getStatePayPeriodDeductions();

		//PP2
		$pd_obj->setDate(strtotime('01-Feb-2015'));
		$pd_obj->setCurrentPayPeriod( $current_pay_period );
		$pd_obj->setYearToDateGrossIncome( $ytd_gross_income );
		$pd_obj->setYearToDateDeduction( $ytd_deduction );
		$pd_obj->setGrossPayPeriodIncome( 5000.00 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '5000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getStatePayPeriodDeductions() ), '225.00' );
		$current_pay_period++;
		$ytd_gross_income += $pd_obj->getGrossPayPeriodIncome();
		$ytd_deduction += $pd_obj->getStatePayPeriodDeductions();

		//PP3
		$pd_obj->setDate(strtotime('01-Mar-2015'));
		$pd_obj->setCurrentPayPeriod( $current_pay_period );
		$pd_obj->setYearToDateGrossIncome( $ytd_gross_income );
		$pd_obj->setYearToDateDeduction( $ytd_deduction );
		$pd_obj->setGrossPayPeriodIncome( 5000.00 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '5000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getStatePayPeriodDeductions() ), '225.00' );
		$current_pay_period++;
		$ytd_gross_income += $pd_obj->getGrossPayPeriodIncome();
		$ytd_deduction += $pd_obj->getStatePayPeriodDeductions();

		//PP4
		$pd_obj->setDate(strtotime('01-Apr-2015'));
		$pd_obj->setCurrentPayPeriod( $current_pay_period );
		$pd_obj->setYearToDateGrossIncome( $ytd_gross_income );
		$pd_obj->setYearToDateDeduction( $ytd_deduction );
		$pd_obj->setGrossPayPeriodIncome( 5000.00 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '5000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getStatePayPeriodDeductions() ), '225.00' );
		$current_pay_period++;
		$ytd_gross_income += $pd_obj->getGrossPayPeriodIncome();
		$ytd_deduction += $pd_obj->getStatePayPeriodDeductions();

		//PP5
		$pd_obj->setDate(strtotime('01-May-2015'));
		$pd_obj->setCurrentPayPeriod( $current_pay_period );
		$pd_obj->setYearToDateGrossIncome( $ytd_gross_income );
		$pd_obj->setYearToDateDeduction( $ytd_deduction );
		$pd_obj->setGrossPayPeriodIncome( 5000.00 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '5000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getStatePayPeriodDeductions() ), '225.00' );
		$current_pay_period++;
		$ytd_gross_income += $pd_obj->getGrossPayPeriodIncome();
		$ytd_deduction += $pd_obj->getStatePayPeriodDeductions();

		//PP6
		$pd_obj->setDate(strtotime('01-Jun-2015'));
		$pd_obj->setCurrentPayPeriod( $current_pay_period );
		$pd_obj->setYearToDateGrossIncome( $ytd_gross_income );
		$pd_obj->setYearToDateDeduction( $ytd_deduction );
		$pd_obj->setGrossPayPeriodIncome( 5000.00 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '5000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getStatePayPeriodDeductions() ), '225.00' );
		$current_pay_period++;
		$ytd_gross_income += $pd_obj->getGrossPayPeriodIncome();
		$ytd_deduction += $pd_obj->getStatePayPeriodDeductions();

		//PP7
		$pd_obj->setDate(strtotime('01-Jul-2015'));
		$pd_obj->setCurrentPayPeriod( $current_pay_period );
		$pd_obj->setYearToDateGrossIncome( $ytd_gross_income );
		$pd_obj->setYearToDateDeduction( $ytd_deduction );
		$pd_obj->setGrossPayPeriodIncome( 5000.00 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '5000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getStatePayPeriodDeductions() ), '225.00' );
		$current_pay_period++;
		$ytd_gross_income += $pd_obj->getGrossPayPeriodIncome();
		$ytd_deduction += $pd_obj->getStatePayPeriodDeductions();

		//PP8
		$pd_obj->setDate(strtotime('01-Aug-2015'));
		$pd_obj->setCurrentPayPeriod( $current_pay_period );
		$pd_obj->setYearToDateGrossIncome( $ytd_gross_income );
		$pd_obj->setYearToDateDeduction( $ytd_deduction );
		$pd_obj->setGrossPayPeriodIncome( 5000.00 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '5000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getStatePayPeriodDeductions() ), '225.00' );
		$current_pay_period++;
		$ytd_gross_income += $pd_obj->getGrossPayPeriodIncome();
		$ytd_deduction += $pd_obj->getStatePayPeriodDeductions();

		//PP9
		$pd_obj->setDate(strtotime('01-Sep-2015'));
		$pd_obj->setCurrentPayPeriod( $current_pay_period );
		$pd_obj->setYearToDateGrossIncome( $ytd_gross_income );
		$pd_obj->setYearToDateDeduction( $ytd_deduction );
		$pd_obj->setGrossPayPeriodIncome( 5000.00 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '5000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getStatePayPeriodDeductions() ), '225.00' );
		$current_pay_period++;
		$ytd_gross_income += $pd_obj->getGrossPayPeriodIncome();
		$ytd_deduction += $pd_obj->getStatePayPeriodDeductions();

		//PP10
		$pd_obj->setDate(strtotime('01-Oct-2015'));
		$pd_obj->setCurrentPayPeriod( $current_pay_period );
		$pd_obj->setYearToDateGrossIncome( $ytd_gross_income );
		$pd_obj->setYearToDateDeduction( $ytd_deduction );
		$pd_obj->setGrossPayPeriodIncome( 5000.00 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '5000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getStatePayPeriodDeductions() ), '225.00' );
		$current_pay_period++;
		$ytd_gross_income += $pd_obj->getGrossPayPeriodIncome();
		$ytd_deduction += $pd_obj->getStatePayPeriodDeductions();

		//PP11
		$pd_obj->setDate(strtotime('01-Nov-2015'));
		$pd_obj->setCurrentPayPeriod( $current_pay_period );
		$pd_obj->setYearToDateGrossIncome( $ytd_gross_income );
		$pd_obj->setYearToDateDeduction( $ytd_deduction );
		$pd_obj->setGrossPayPeriodIncome( 5000.00 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '5000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getStatePayPeriodDeductions() ), '225.00' );
		$current_pay_period++;
		$ytd_gross_income += $pd_obj->getGrossPayPeriodIncome();
		$ytd_deduction += $pd_obj->getStatePayPeriodDeductions();

		//PP12
		$pd_obj->setDate(strtotime('01-Dec-2015'));
		$pd_obj->setCurrentPayPeriod( $current_pay_period );
		$pd_obj->setYearToDateGrossIncome( $ytd_gross_income );
		$pd_obj->setYearToDateDeduction( $ytd_deduction );
		$pd_obj->setGrossPayPeriodIncome( 5000.00 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '5000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getStatePayPeriodDeductions() ), '225.00' );
		$current_pay_period++;
		$ytd_gross_income += $pd_obj->getGrossPayPeriodIncome();
		$ytd_deduction += $pd_obj->getStatePayPeriodDeductions();

		$this->assertEquals( $ytd_gross_income, 60000 );
		$this->assertEquals( $ytd_deduction, 2700.00 );

		//Actual Income/Deductions for the year.
		$pd_obj->setDate(strtotime('01-Dec-2015'));
		$pd_obj->setAnnualPayPeriods( 1 );
		$pd_obj->setCurrentPayPeriod( 1 );
		$pd_obj->setYearToDateGrossIncome( 0 );
		$pd_obj->setYearToDateDeduction( 0 );
		$pd_obj->setGrossPayPeriodIncome( 60000 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '60000' );
		$this->assertEquals( $this->mf( $pd_obj->getStatePayPeriodDeductions() ), '2697.00' );
	}

	function testUS_2015_State_NonPeriodic_FormulaA() {
		Debug::text('US - SemiMonthly - Beginning of 2015 01-Jan-2015: ', __FILE__, __LINE__, __METHOD__, 10);

		$pd_obj = new PayrollDeduction('US', 'MO');
		$pd_obj->setAnnualPayPeriods( 12 ); //Monthly
		$pd_obj->setFormulaType( 20 ); //NonPeriodic
		$pd_obj->setStateFilingStatus( 10 ); //Single
		$pd_obj->setStateAllowance( 0 );
		$pd_obj->setProvincialTaxExempt( FALSE );

		$current_pay_period = 1;
		$ytd_gross_income = 0;
		$ytd_deduction = 0;

		//PP1
		$pd_obj->setDate(strtotime('01-Jan-2015'));
		$pd_obj->setCurrentPayPeriod( $current_pay_period );
		$pd_obj->setYearToDateGrossIncome( $ytd_gross_income );
		$pd_obj->setYearToDateDeduction( $ytd_deduction );
		$pd_obj->setGrossPayPeriodIncome( 5000.00 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '5000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getStatePayPeriodDeductions() ), '225.00' );
		$current_pay_period++;
		$ytd_gross_income += $pd_obj->getGrossPayPeriodIncome();
		$ytd_deduction += $pd_obj->getStatePayPeriodDeductions();

		//PP2
		$pd_obj->setDate(strtotime('01-Feb-2015'));
		$pd_obj->setCurrentPayPeriod( $current_pay_period );
		$pd_obj->setYearToDateGrossIncome( $ytd_gross_income );
		$pd_obj->setYearToDateDeduction( $ytd_deduction );
		$pd_obj->setGrossPayPeriodIncome( 5000.00 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '5000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getStatePayPeriodDeductions() ), '224.00' );
		$current_pay_period++;
		$ytd_gross_income += $pd_obj->getGrossPayPeriodIncome();
		$ytd_deduction += $pd_obj->getStatePayPeriodDeductions();

		//PP3
		$pd_obj->setDate(strtotime('01-Mar-2015'));
		$pd_obj->setCurrentPayPeriod( $current_pay_period );
		$pd_obj->setYearToDateGrossIncome( $ytd_gross_income );
		$pd_obj->setYearToDateDeduction( $ytd_deduction );
		$pd_obj->setGrossPayPeriodIncome( 5000.00 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '5000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getStatePayPeriodDeductions() ), '225.00' );
		$current_pay_period++;
		$ytd_gross_income += $pd_obj->getGrossPayPeriodIncome();
		$ytd_deduction += $pd_obj->getStatePayPeriodDeductions();

		//PP4
		$pd_obj->setDate(strtotime('01-Apr-2015'));
		$pd_obj->setCurrentPayPeriod( $current_pay_period );
		$pd_obj->setYearToDateGrossIncome( $ytd_gross_income );
		$pd_obj->setYearToDateDeduction( $ytd_deduction );
		$pd_obj->setGrossPayPeriodIncome( 5000.00 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '5000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getStatePayPeriodDeductions() ), '225.00' );
		$current_pay_period++;
		$ytd_gross_income += $pd_obj->getGrossPayPeriodIncome();
		$ytd_deduction += $pd_obj->getStatePayPeriodDeductions();

		//PP5
		$pd_obj->setDate(strtotime('01-May-2015'));
		$pd_obj->setCurrentPayPeriod( $current_pay_period );
		$pd_obj->setYearToDateGrossIncome( $ytd_gross_income );
		$pd_obj->setYearToDateDeduction( $ytd_deduction );
		$pd_obj->setGrossPayPeriodIncome( 5000.00 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '5000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getStatePayPeriodDeductions() ), '225.00' );
		$current_pay_period++;
		$ytd_gross_income += $pd_obj->getGrossPayPeriodIncome();
		$ytd_deduction += $pd_obj->getStatePayPeriodDeductions();

		//PP6
		$pd_obj->setDate(strtotime('01-Jun-2015'));
		$pd_obj->setCurrentPayPeriod( $current_pay_period );
		$pd_obj->setYearToDateGrossIncome( $ytd_gross_income );
		$pd_obj->setYearToDateDeduction( $ytd_deduction );
		$pd_obj->setGrossPayPeriodIncome( 5000.00 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '5000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getStatePayPeriodDeductions() ), '225.00' );
		$current_pay_period++;
		$ytd_gross_income += $pd_obj->getGrossPayPeriodIncome();
		$ytd_deduction += $pd_obj->getStatePayPeriodDeductions();

		//PP7
		$pd_obj->setDate(strtotime('01-Jul-2015'));
		$pd_obj->setCurrentPayPeriod( $current_pay_period );
		$pd_obj->setYearToDateGrossIncome( $ytd_gross_income );
		$pd_obj->setYearToDateDeduction( $ytd_deduction );
		$pd_obj->setGrossPayPeriodIncome( 5000.00 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '5000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getStatePayPeriodDeductions() ), '224.00' );
		$current_pay_period++;
		$ytd_gross_income += $pd_obj->getGrossPayPeriodIncome();
		$ytd_deduction += $pd_obj->getStatePayPeriodDeductions();

		//PP8
		$pd_obj->setDate(strtotime('01-Aug-2015'));
		$pd_obj->setCurrentPayPeriod( $current_pay_period );
		$pd_obj->setYearToDateGrossIncome( $ytd_gross_income );
		$pd_obj->setYearToDateDeduction( $ytd_deduction );
		$pd_obj->setGrossPayPeriodIncome( 5000.00 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '5000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getStatePayPeriodDeductions() ), '225.00' );
		$current_pay_period++;
		$ytd_gross_income += $pd_obj->getGrossPayPeriodIncome();
		$ytd_deduction += $pd_obj->getStatePayPeriodDeductions();

		//PP9
		$pd_obj->setDate(strtotime('01-Sep-2015'));
		$pd_obj->setCurrentPayPeriod( $current_pay_period );
		$pd_obj->setYearToDateGrossIncome( $ytd_gross_income );
		$pd_obj->setYearToDateDeduction( $ytd_deduction );
		$pd_obj->setGrossPayPeriodIncome( 5000.00 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '5000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getStatePayPeriodDeductions() ), '225.00' );
		$current_pay_period++;
		$ytd_gross_income += $pd_obj->getGrossPayPeriodIncome();
		$ytd_deduction += $pd_obj->getStatePayPeriodDeductions();

		//PP10
		$pd_obj->setDate(strtotime('01-Oct-2015'));
		$pd_obj->setCurrentPayPeriod( $current_pay_period );
		$pd_obj->setYearToDateGrossIncome( $ytd_gross_income );
		$pd_obj->setYearToDateDeduction( $ytd_deduction );
		$pd_obj->setGrossPayPeriodIncome( 5000.00 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '5000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getStatePayPeriodDeductions() ), '224.00' );
		$current_pay_period++;
		$ytd_gross_income += $pd_obj->getGrossPayPeriodIncome();
		$ytd_deduction += $pd_obj->getStatePayPeriodDeductions();

		//PP11
		$pd_obj->setDate(strtotime('01-Nov-2015'));
		$pd_obj->setCurrentPayPeriod( $current_pay_period );
		$pd_obj->setYearToDateGrossIncome( $ytd_gross_income );
		$pd_obj->setYearToDateDeduction( $ytd_deduction );
		$pd_obj->setGrossPayPeriodIncome( 5000.00 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '5000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getStatePayPeriodDeductions() ), '225.00' );
		$current_pay_period++;
		$ytd_gross_income += $pd_obj->getGrossPayPeriodIncome();
		$ytd_deduction += $pd_obj->getStatePayPeriodDeductions();

		//PP12
		$pd_obj->setDate(strtotime('01-Dec-2015'));
		$pd_obj->setCurrentPayPeriod( $current_pay_period );
		$pd_obj->setYearToDateGrossIncome( $ytd_gross_income );
		$pd_obj->setYearToDateDeduction( $ytd_deduction );
		$pd_obj->setGrossPayPeriodIncome( 5000.00 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '5000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getStatePayPeriodDeductions() ), '225.00' );
		$current_pay_period++;
		$ytd_gross_income += $pd_obj->getGrossPayPeriodIncome();
		$ytd_deduction += $pd_obj->getStatePayPeriodDeductions();

		$this->assertEquals( $ytd_gross_income, 60000 );
		$this->assertEquals( $ytd_deduction, 2697.00 );

		//Actual Income/Deductions for the year.
		$pd_obj->setDate(strtotime('01-Dec-2015'));
		$pd_obj->setAnnualPayPeriods( 1 );
		$pd_obj->setCurrentPayPeriod( 1 );
		$pd_obj->setYearToDateGrossIncome( 0 );
		$pd_obj->setYearToDateDeduction( 0 );
		$pd_obj->setGrossPayPeriodIncome( 60000 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '60000' );
		$this->assertEquals( $this->mf( $pd_obj->getStatePayPeriodDeductions() ), '2697.00' );
	}


	function testUS_2015_State_Periodic_Match_NonPeriodic_FormulaA() {
		Debug::text('US - SemiMonthly - Beginning of 2015 01-Jan-2015: ', __FILE__, __LINE__, __METHOD__, 10);

		$pd_obj = new PayrollDeduction('US', 'MO');
		$pd_obj->setAnnualPayPeriods( 12 ); //Monthly
		$pd_obj->setFormulaType( 10 ); //NonPeriodic
		$pd_obj->setStateFilingStatus( 10 ); //Single
		$pd_obj->setStateAllowance( 0 );
		$pd_obj->setProvincialTaxExempt( FALSE );

		//PP1
		$pd_obj->setDate(strtotime('01-Jan-2015'));
		$pd_obj->setCurrentPayPeriod( 1 );
		$pd_obj->setYearToDateGrossIncome( 0 );
		$pd_obj->setYearToDateDeduction( 0 );
		$pd_obj->setGrossPayPeriodIncome( 5000.00 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '5000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getStatePayPeriodDeductions() ), '225.00' );

		//
		//NonPeriodic
		//
		$pd_obj = new PayrollDeduction('US', 'MO');
		$pd_obj->setAnnualPayPeriods( 12 ); //Monthly
		$pd_obj->setFormulaType( 20 ); //NonPeriodic
		$pd_obj->setStateFilingStatus( 10 ); //Single
		$pd_obj->setStateAllowance( 0 );
		$pd_obj->setProvincialTaxExempt( FALSE );

		//PP1
		$pd_obj->setDate(strtotime('01-Jan-2015'));
		$pd_obj->setCurrentPayPeriod( 1 );
		$pd_obj->setYearToDateGrossIncome( 0 );
		$pd_obj->setYearToDateDeduction( 0 );
		$pd_obj->setGrossPayPeriodIncome( 5000.00 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '5000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getStatePayPeriodDeductions() ), '225.00' );

	}

	function testUS_2015_State_Periodic_Match_NonPeriodic_FormulaB() {
		Debug::text('US - SemiMonthly - Beginning of 2015 01-Jan-2015: ', __FILE__, __LINE__, __METHOD__, 10);

		$pd_obj = new PayrollDeduction('US', 'MO');
		$pd_obj->setAnnualPayPeriods( 12 ); //Monthly
		$pd_obj->setFormulaType( 10 ); //NonPeriodic
		$pd_obj->setStateFilingStatus( 10 ); //Single
		$pd_obj->setStateAllowance( 1 );
		$pd_obj->setProvincialTaxExempt( FALSE );

		//PP1
		$pd_obj->setDate(strtotime('01-Jan-2015'));
		$pd_obj->setCurrentPayPeriod( 1 );
		$pd_obj->setYearToDateGrossIncome( 0 );
		$pd_obj->setYearToDateDeduction( 0 );
		$pd_obj->setGrossPayPeriodIncome( 5000.00 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '5000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getStatePayPeriodDeductions() ), '214.00' );

		//
		//NonPeriodic
		//
		$pd_obj = new PayrollDeduction('US', 'MO');
		$pd_obj->setAnnualPayPeriods( 12 ); //Monthly
		$pd_obj->setFormulaType( 20 ); //NonPeriodic
		$pd_obj->setStateFilingStatus( 10 ); //Single
		$pd_obj->setStateAllowance( 1 );
		$pd_obj->setProvincialTaxExempt( FALSE );

		//PP1
		$pd_obj->setDate(strtotime('01-Jan-2015'));
		$pd_obj->setCurrentPayPeriod( 1 );
		$pd_obj->setYearToDateGrossIncome( 0 );
		$pd_obj->setYearToDateDeduction( 0 );
		$pd_obj->setGrossPayPeriodIncome( 5000.00 );
		$this->assertEquals( $this->mf( $pd_obj->getGrossPayPeriodIncome() ), '5000.00' );
		$this->assertEquals( $this->mf( $pd_obj->getStatePayPeriodDeductions() ), '214.00' );
	}
}
?>