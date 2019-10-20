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
class PayrollDeduction_US_SC extends PayrollDeduction_US {

	var $state_income_tax_rate_options = array(
			20190101 => array(
					0 => array( //Uses Subtraction method constants.
								array('income' => 2450, 'rate' => 1.1, 'constant' => 0),
								array('income' => 4900, 'rate' => 3, 'constant' => 46.55),
								array('income' => 7350, 'rate' => 4, 'constant' => 95.55),
								array('income' => 9800, 'rate' => 5, 'constant' => 169.05),
								array('income' => 12250, 'rate' => 6, 'constant' => 267.05),
								array('income' => 12250, 'rate' => 7, 'constant' => 389.55),
					),
			),
			20180101 => array(
					0 => array( //Uses Subtraction method constants.
								array('income' => 2290, 'rate' => 1.4, 'constant' => 0),
								array('income' => 4580, 'rate' => 3, 'constant' => 36.64),
								array('income' => 6870, 'rate' => 4, 'constant' => 82.44),
								array('income' => 9160, 'rate' => 5, 'constant' => 151.14),
								array('income' => 11450, 'rate' => 6, 'constant' => 242.74),
								array('income' => 11450, 'rate' => 7, 'constant' => 357.24),
					),
			),
			20170101 => array(
					0 => array( //Uses Subtraction method constants.
								array('income' => 2140, 'rate' => 1.7, 'constant' => 0),
								array('income' => 4280, 'rate' => 3, 'constant' => 27.82),
								array('income' => 6420, 'rate' => 4, 'constant' => 70.62),
								array('income' => 8560, 'rate' => 5, 'constant' => 134.82),
								array('income' => 10700, 'rate' => 6, 'constant' => 220.42),
								array('income' => 10700, 'rate' => 7, 'constant' => 327.42),
					),
			),
			20060101 => array(
					0 => array( //Uses Subtraction method constants.
								array('income' => 2000, 'rate' => 2, 'constant' => 0),
								array('income' => 4000, 'rate' => 3, 'constant' => 20),
								array('income' => 6000, 'rate' => 4, 'constant' => 60),
								array('income' => 8000, 'rate' => 5, 'constant' => 120),
								array('income' => 10000, 'rate' => 6, 'constant' => 200),
								array('income' => 10000, 'rate' => 7, 'constant' => 300),
					),
			),
	);

	var $state_options = array(
			20190101 => array(
					'standard_deduction_rate'    => 10, //Standard Deduction Rate of 10%
					'standard_deduction_maximum' => 3470,
					'allowance'                  => 2510,
			),
			20180101 => array(
					'standard_deduction_rate'    => 10,
					'standard_deduction_maximum' => 3150,
					'allowance'                  => 2440,
			),
			20170101 => array(
					'standard_deduction_rate'    => 10,
					'standard_deduction_maximum' => 2860,
					'allowance'                  => 2370,
			),
			20060101 => array(
					'standard_deduction_rate'    => 10,
					'standard_deduction_maximum' => 2600,
					'allowance'                  => 2300,
			),
	);

	function getStateAnnualTaxableIncome() {
		$annual_income = $this->getAnnualTaxableIncome();
		$standard_deductions = $this->getStateStandardDeduction();
		$allowance = $this->getStateAllowanceAmount();

		$income = bcsub( bcsub( $annual_income, $standard_deductions ), $allowance );

		Debug::text( 'State Annual Taxable Income: ' . $income, __FILE__, __LINE__, __METHOD__, 10 );

		return $income;
	}

	function getStateFederalTaxMaximum() {
		$retarr = $this->getDataFromRateArray( $this->getDate(), $this->state_options );
		if ( $retarr == FALSE ) {
			return FALSE;
		}

		$maximum = $retarr['federal_tax_maximum'][ $this->getStateFilingStatus() ];

		Debug::text( 'Maximum State allowed Federal Tax: ' . $maximum, __FILE__, __LINE__, __METHOD__, 10 );

		return $maximum;
	}

	function getStateStandardDeduction() {
		$retarr = $this->getDataFromRateArray( $this->getDate(), $this->state_options );
		if ( $retarr == FALSE ) {
			return FALSE;

		}

		if ( $this->getStateAllowance() == 0 ) {
			$deduction = 0;
		} else {
			$rate = bcdiv( $retarr['standard_deduction_rate'], 100 );
			$deduction = bcmul( $this->getAnnualTaxableIncome(), $rate );
			if ( $deduction > $retarr['standard_deduction_maximum'] ) {
				$deduction = $retarr['standard_deduction_maximum'];
			}
		}

		Debug::text( 'Standard Deduction: ' . $deduction, __FILE__, __LINE__, __METHOD__, 10 );

		return $deduction;
	}

	function getStateAllowanceAmount() {
		$retarr = $this->getDataFromRateArray( $this->getDate(), $this->state_options );
		if ( $retarr == FALSE ) {
			return FALSE;

		}

		$allowance = $retarr['allowance'];

		$retval = bcmul( $this->getStateAllowance(), $allowance );

		Debug::text( 'State Allowance Amount: ' . $retval, __FILE__, __LINE__, __METHOD__, 10 );

		return $retval;
	}

	function _getStateTaxPayable() {
		$annual_income = $this->getStateAnnualTaxableIncome();

		$retval = 0;

		if ( $annual_income > 0 ) {
			$rate = $this->getData()->getStateRate( $annual_income );
			$state_constant = $this->getData()->getStateConstant( $annual_income );
			//$state_rate_income = $this->getData()->getStateRatePreviousIncome($annual_income);

			//$retval = bcadd( bcmul( bcsub( $annual_income, $state_rate_income ), $rate ), $state_constant );
			$retval = bcsub( bcmul( $annual_income, $rate ), $state_constant );
		}

		if ( $retval < 0 ) {
			$retval = 0;
		}

		Debug::text( 'State Annual Tax Payable: ' . $retval, __FILE__, __LINE__, __METHOD__, 10 );

		return $retval;
	}
}

?>
