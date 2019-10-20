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
class PayrollDeduction_US_DE extends PayrollDeduction_US {

	var $state_income_tax_rate_options = array(
			20140101 => array(
					0 => array(
							array('income' => 2000, 'rate' => 0, 'constant' => 0),
							array('income' => 5000, 'rate' => 2.20, 'constant' => 0),
							array('income' => 10000, 'rate' => 3.90, 'constant' => 66),
							array('income' => 20000, 'rate' => 4.80, 'constant' => 261),
							array('income' => 25000, 'rate' => 5.20, 'constant' => 741),
							array('income' => 60000, 'rate' => 5.55, 'constant' => 1001),
							array('income' => 60000, 'rate' => 6.60, 'constant' => 2943.50),
					),
			),
			20100101 => array(
					0 => array(
							array('income' => 2000, 'rate' => 0, 'constant' => 0),
							array('income' => 5000, 'rate' => 2.20, 'constant' => 0),
							array('income' => 10000, 'rate' => 3.90, 'constant' => 66),
							array('income' => 20000, 'rate' => 4.80, 'constant' => 261),
							array('income' => 25000, 'rate' => 5.20, 'constant' => 741),
							array('income' => 60000, 'rate' => 5.55, 'constant' => 1001),
							array('income' => 60000, 'rate' => 6.95, 'constant' => 2943.50),
					),
			),
			20060101 => array(
					0 => array(
							array('income' => 2000, 'rate' => 0, 'constant' => 0),
							array('income' => 5000, 'rate' => 2.20, 'constant' => 0),
							array('income' => 10000, 'rate' => 3.90, 'constant' => 66),
							array('income' => 20000, 'rate' => 4.80, 'constant' => 261),
							array('income' => 25000, 'rate' => 5.20, 'constant' => 741),
							array('income' => 60000, 'rate' => 5.55, 'constant' => 1001),
							array('income' => 60000, 'rate' => 5.95, 'constant' => 2943.50),
					),
			),
	);

	var $state_options = array(
			20060101 => array(
					'standard_deduction' => array(
							10 => 3250,
							20 => 6500,
							30 => 3250,
					),
					'allowance'          => 110,
			),
	);

	function getStateAnnualTaxableIncome() {
		$annual_income = $this->getAnnualTaxableIncome();
		$standard_deduction = $this->getStateStandardDeduction();

		$income = bcsub( $annual_income, $standard_deduction );

		Debug::text( 'State Annual Taxable Income: ' . $income, __FILE__, __LINE__, __METHOD__, 10 );

		return $income;
	}

	function getStateStandardDeduction() {
		$retarr = $this->getDataFromRateArray( $this->getDate(), $this->state_options );
		if ( $retarr == FALSE ) {
			return FALSE;

		}

		if ( isset( $retarr['standard_deduction'][ $this->getStateFilingStatus() ] ) ) {
			$deduction = $retarr['standard_deduction'][ $this->getStateFilingStatus() ];
		} else {
			$deduction = $retarr['standard_deduction'][10]; //Single
		}

		Debug::text( 'Standard Deduction: ' . $deduction, __FILE__, __LINE__, __METHOD__, 10 );

		return $deduction;
	}

	function getStateAllowanceAmount() {
		$retarr = $this->getDataFromRateArray( $this->getDate(), $this->state_options );
		if ( $retarr == FALSE ) {
			return FALSE;

		}

		$allowance_arr = $retarr['allowance'];

		$retval = bcmul( $this->getStateAllowance(), $allowance_arr );

		Debug::text( 'State Allowance Amount: ' . $retval, __FILE__, __LINE__, __METHOD__, 10 );

		return $retval;
	}

	function _getStateTaxPayable() {
		$annual_income = $this->getStateAnnualTaxableIncome();

		$retval = 0;

		if ( $annual_income > 0 ) {
			$rate = $this->getData()->getStateRate( $annual_income );
			$prev_income = $this->getData()->getStateRatePreviousIncome( $annual_income );
			$state_constant = $this->getData()->getStateConstant( $annual_income );

			//$retval = bcadd( bcmul( bcsub( $annual_income, $state_rate_income ), $rate ), $state_constant );
			$retval = bcadd( bcmul( bcsub( $annual_income, $prev_income ), $rate ), $state_constant );
		}

		$retval = bcsub( $retval, $this->getStateAllowanceAmount() );

		if ( $retval < 0 ) {
			$retval = 0;
		}

		Debug::text( 'State Annual Tax Payable: ' . $retval, __FILE__, __LINE__, __METHOD__, 10 );

		return $retval;
	}
}

?>
