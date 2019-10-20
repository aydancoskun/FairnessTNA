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
class PayrollDeduction_US_MS extends PayrollDeduction_US {
	/*
		protected $state_filing_status_options = array(
															10 => 'Single',
															20 => 'Married - Spouse Works',
															30 => 'Married - Spouse does not Work',
															40 => 'Head of Household',
										);
	*/

	var $state_income_tax_rate_options = array(
			20190101 => array(
					0 => array(
							array('income' => 2000, 'rate' => 0.0, 'constant' => 0),
							array('income' => 5000, 'rate' => 3.0, 'constant' => 0),
							array('income' => 10000, 'rate' => 4.0, 'constant' => 90),
							array('income' => 10000, 'rate' => 5.0, 'constant' => 290),
					),
			),
			20060101 => array(
					0 => array(
							array('income' => 5000, 'rate' => 3.0, 'constant' => 0),
							array('income' => 10000, 'rate' => 4.0, 'constant' => 150),
							array('income' => 10000, 'rate' => 5.0, 'constant' => 350),
					),
			),
	);

	var $state_options = array(
			20060101 => array(
					'standard_deduction' => array(
							'10' => 2300,
							'20' => 2300,
							'30' => 4600,
							'40' => 3400,
					),
			),
	);

	function getStateAnnualTaxableIncome() {
		$annual_income = $this->getAnnualTaxableIncome();

		$state_deductions = $this->getStateStandardDeduction();
		$state_allowance = $this->getStateAllowance();

		$income = bcsub( bcsub( $annual_income, $state_deductions ), $state_allowance );

		Debug::text( 'State Annual Taxable Income: ' . $income, __FILE__, __LINE__, __METHOD__, 10 );

		return $income;
	}

	function getStateStandardDeduction() {
		$retarr = $this->getDataFromRateArray( $this->getDate(), $this->state_options );
		if ( $retarr == FALSE ) {
			return FALSE;

		}

		$deduction = $retarr['standard_deduction'][ $this->getStateFilingStatus() ];

		Debug::text( 'Standard Deduction: ' . $deduction, __FILE__, __LINE__, __METHOD__, 10 );

		return $deduction;
	}

	function _getStateTaxPayable() {
		$annual_income = $this->getStateAnnualTaxableIncome();

		$retval = 0;

		if ( $annual_income > 0 ) {
			$rate = $this->getData()->getStateRate( $annual_income );
			$state_constant = $this->getData()->getStateConstant( $annual_income );
			$state_rate_income = $this->getData()->getStateRatePreviousIncome( $annual_income );

			$retval = bcadd( bcmul( bcsub( $annual_income, $state_rate_income ), $rate ), $state_constant );
		}

		if ( $retval < 0 ) {
			$retval = 0;
		}

		Debug::text( 'State Annual Tax Payable: ' . $retval, __FILE__, __LINE__, __METHOD__, 10 );

		return $retval;
	}

}

?>
