<?php
/*********************************************************************************
 * FairnessTNA is a Workforce Management program forked from TimeTrex in 2013,
 * copyright Aydan Coskun. Original code base is copyright TimeTrex Software Inc.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * You can contact Aydan Coskun via issue tracker on github.com/aydancoskun
 ********************************************************************************/


/**
 * @package PayrollDeduction\US
 */
class PayrollDeduction_US_NY_NYC extends PayrollDeduction_US_NY {
	/*
															 10 => 'Single',
															20 => 'Married',

	Used to be:
															10 => 'Single',
															20 => 'Married - Spouse Works',
															30 => 'Married - Spouse does not Work',
															40 => 'Head of Household',
	*/

	var $district_income_tax_rate_options = array(
			20180101 => array(
					10 => array(
							array('income' => 8000, 'rate' => 2.05, 'constant' => 0),
							array('income' => 8700, 'rate' => 2.80, 'constant' => 164),
							array('income' => 15000, 'rate' => 3.25, 'constant' => 184),
							array('income' => 25000, 'rate' => 3.95, 'constant' => 388),
							array('income' => 60000, 'rate' => 4.15, 'constant' => 783),
							array('income' => 60000, 'rate' => 4.25, 'constant' => 2236),
					),
					20 => array(
							array('income' => 8000, 'rate' => 2.05, 'constant' => 0),
							array('income' => 8700, 'rate' => 2.80, 'constant' => 164),
							array('income' => 15000, 'rate' => 3.25, 'constant' => 184),
							array('income' => 25000, 'rate' => 3.95, 'constant' => 388),
							array('income' => 60000, 'rate' => 4.15, 'constant' => 783),
							array('income' => 60000, 'rate' => 4.25, 'constant' => 2236),
					),
			),
			20170701 => array(
					10 => array(
							array('income' => 8000, 'rate' => 2.25, 'constant' => 0),
							array('income' => 8700, 'rate' => 3.00, 'constant' => 180),
							array('income' => 15000, 'rate' => 3.45, 'constant' => 201),
							array('income' => 25000, 'rate' => 4.15, 'constant' => 418),
							array('income' => 60000, 'rate' => 4.35, 'constant' => 833),
							array('income' => 500000, 'rate' => 4.45, 'constant' => 2356),
							array('income' => 500000, 'rate' => 4.25, 'constant' => 20828), //Just the constant changed by the looks of it.
					),
					20 => array(
							array('income' => 8000, 'rate' => 2.25, 'constant' => 0),
							array('income' => 8700, 'rate' => 3.00, 'constant' => 180),
							array('income' => 15000, 'rate' => 3.45, 'constant' => 201),
							array('income' => 25000, 'rate' => 4.15, 'constant' => 418),
							array('income' => 60000, 'rate' => 4.35, 'constant' => 833),
							array('income' => 500000, 'rate' => 4.45, 'constant' => 2356),
							array('income' => 500000, 'rate' => 4.25, 'constant' => 20828), //Just the constant changed by the looks of it.
					),
			),
			20160101 => array(
					10 => array(
							array('income' => 8000, 'rate' => 1.9, 'constant' => 0),
							array('income' => 8700, 'rate' => 2.65, 'constant' => 152),
							array('income' => 15000, 'rate' => 3.1, 'constant' => 171),
							array('income' => 25000, 'rate' => 3.7, 'constant' => 366),
							array('income' => 60000, 'rate' => 3.9, 'constant' => 736),
							array('income' => 500000, 'rate' => 4.0, 'constant' => 2101),
							array('income' => 500000, 'rate' => 4.25, 'constant' => 20828.46), //Just the constant changed by the looks of it.
					),
					20 => array(
							array('income' => 8000, 'rate' => 1.9, 'constant' => 0),
							array('income' => 8700, 'rate' => 2.65, 'constant' => 152),
							array('income' => 15000, 'rate' => 3.1, 'constant' => 171),
							array('income' => 25000, 'rate' => 3.7, 'constant' => 366),
							array('income' => 60000, 'rate' => 3.9, 'constant' => 736),
							array('income' => 500000, 'rate' => 4.0, 'constant' => 2101),
							array('income' => 500000, 'rate' => 4.25, 'constant' => 20828.46),
					),
			),
			20150601 => array(
					10 => array(
							array('income' => 8000, 'rate' => 1.9, 'constant' => 0),
							array('income' => 8700, 'rate' => 2.65, 'constant' => 152),
							array('income' => 15000, 'rate' => 3.1, 'constant' => 171),
							array('income' => 25000, 'rate' => 3.7, 'constant' => 366),
							array('income' => 60000, 'rate' => 3.9, 'constant' => 736),
							array('income' => 500000, 'rate' => 4.0, 'constant' => 2101),
							array('income' => 500000, 'rate' => 4.25, 'constant' => 20834.16),
					),
					20 => array(
							array('income' => 8000, 'rate' => 1.9, 'constant' => 0),
							array('income' => 8700, 'rate' => 2.65, 'constant' => 152),
							array('income' => 15000, 'rate' => 3.1, 'constant' => 171),
							array('income' => 25000, 'rate' => 3.7, 'constant' => 366),
							array('income' => 60000, 'rate' => 3.9, 'constant' => 736),
							array('income' => 500000, 'rate' => 4.0, 'constant' => 2101),
							array('income' => 500000, 'rate' => 4.25, 'constant' => 20834.16),
					),
			),
			20110101 => array(
					10 => array(
							array('income' => 8000, 'rate' => 1.9, 'constant' => 0),
							array('income' => 8700, 'rate' => 2.65, 'constant' => 152),
							array('income' => 15000, 'rate' => 3.1, 'constant' => 171),
							array('income' => 25000, 'rate' => 3.7, 'constant' => 366),
							array('income' => 60000, 'rate' => 3.9, 'constant' => 736),
							array('income' => 500000, 'rate' => 4.0, 'constant' => 2101),
							array('income' => 500000, 'rate' => 4.25, 'constant' => 19701),
					),
					20 => array(
							array('income' => 8000, 'rate' => 1.9, 'constant' => 0),
							array('income' => 8700, 'rate' => 2.65, 'constant' => 152),
							array('income' => 15000, 'rate' => 3.1, 'constant' => 171),
							array('income' => 25000, 'rate' => 3.7, 'constant' => 366),
							array('income' => 60000, 'rate' => 3.9, 'constant' => 736),
							array('income' => 500000, 'rate' => 4.0, 'constant' => 2101),
							array('income' => 500000, 'rate' => 4.25, 'constant' => 19701),
					),
			),
			20060101 => array(
					10 => array(
							array('income' => 8000, 'rate' => 1.9, 'constant' => 0),
							array('income' => 8700, 'rate' => 2.65, 'constant' => 152),
							array('income' => 15000, 'rate' => 3.10, 'constant' => 172),
							array('income' => 25000, 'rate' => 3.70, 'constant' => 366),
							array('income' => 60000, 'rate' => 3.90, 'constant' => 736),
							array('income' => 60000, 'rate' => 4.00, 'constant' => 2101),
					),
					20 => array(
							array('income' => 8000, 'rate' => 1.9, 'constant' => 0),
							array('income' => 8700, 'rate' => 2.65, 'constant' => 152),
							array('income' => 15000, 'rate' => 3.10, 'constant' => 172),
							array('income' => 25000, 'rate' => 3.70, 'constant' => 366),
							array('income' => 60000, 'rate' => 3.90, 'constant' => 736),
							array('income' => 60000, 'rate' => 4.00, 'constant' => 2101),
					),
			),
	);

	var $district_options = array(
			20060101 => array(
					'standard_deduction' => array(
							'10' => 5000.00,
							'20' => 5500.00,
							'30' => 5500.00,
							'40' => 5000.00,
					),
					'allowance'          => array(
							'10' => 1000,
							'20' => 1000,
							'30' => 1000,
							'40' => 1000,
					),
			),
	);

	function getDistrictAnnualTaxableIncome() {
		$annual_income = $this->getAnnualTaxableIncome();
		$federal_tax = $this->getFederalTaxPayable();
		$district_deductions = $this->getDistrictStandardDeduction();
		$district_allowance = $this->getDistrictAllowanceAmount();

		$income = bcsub( bcsub( $annual_income, $district_deductions ), $district_allowance );

		Debug::text( 'District Annual Taxable Income: ' . $income, __FILE__, __LINE__, __METHOD__, 10 );

		return $income;
	}

	function getDistrictStandardDeduction() {
		$retarr = $this->getDataFromRateArray( $this->getDate(), $this->district_options );
		if ( $retarr == FALSE ) {
			return FALSE;

		}

		if ( isset( $retarr['standard_deduction'][ $this->getDistrictFilingStatus() ] ) ) {
			$deduction = $retarr['standard_deduction'][ $this->getDistrictFilingStatus() ];
		} else {
			$deduction = $retarr['standard_deduction'][10];
		}

		Debug::text( 'Standard Deduction: ' . $deduction, __FILE__, __LINE__, __METHOD__, 10 );

		return $deduction;
	}

	function getDistrictAllowanceAmount() {
		$retarr = $this->getDataFromRateArray( $this->getDate(), $this->district_options );
		if ( $retarr == FALSE ) {
			return FALSE;

		}

		if ( isset( $retarr['allowance'][ $this->getDistrictFilingStatus() ] ) ) {
			$allowance = $retarr['allowance'][ $this->getDistrictFilingStatus() ];
		} else {
			$allowance = $retarr['allowance'][10];
		}

		if ( $this->getDistrictAllowance() == 0 ) {
			$retval = 0;
		} else {
			$retval = bcmul( $this->getDistrictAllowance(), $allowance );
		}

		Debug::text( 'District Allowance Amount: ' . $retval, __FILE__, __LINE__, __METHOD__, 10 );


		return $retval;
	}

	function getDistrictTaxPayable() {
		$annual_income = $this->getDistrictAnnualTaxableIncome();

		$retval = 0;

		if ( $annual_income > 0 ) {
			$rate = $this->getData()->getDistrictRate( $annual_income );
			$district_constant = $this->getData()->getDistrictConstant( $annual_income );
			$district_rate_income = $this->getData()->getDistrictRatePreviousIncome( $annual_income );

			$retval = bcadd( bcmul( bcsub( $annual_income, $district_rate_income ), $rate ), $district_constant );
		}

		if ( $retval < 0 ) {
			$retval = 0;
		}

		Debug::text( 'District Annual Tax Payable: ' . $retval, __FILE__, __LINE__, __METHOD__, 10 );

		return $retval;
	}
}

?>
