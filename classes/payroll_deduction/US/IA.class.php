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
class PayrollDeduction_US_IA extends PayrollDeduction_US {

	var $state_income_tax_rate_options = array(
			20190101 => array(
					0 => array(
							array('income' => 1333, 'rate' => 0.33, 'constant' => 0),
							array('income' => 2666, 'rate' => 0.67, 'constant' => 4.40),
							array('income' => 5331, 'rate' => 2.25, 'constant' => 13.33),
							array('income' => 11995, 'rate' => 4.14, 'constant' => 73.29),
							array('income' => 19992, 'rate' => 5.63, 'constant' => 349.18),
							array('income' => 26656, 'rate' => 5.96, 'constant' => 799.41),
							array('income' => 39984, 'rate' => 6.25, 'constant' => 1196.58),
							array('income' => 59976, 'rate' => 7.44, 'constant' => 2029.58),
							array('income' => 59976, 'rate' => 8.53, 'constant' => 3516.98),
					),
			),
			20060401 => array(
					0 => array(
							array('income' => 1300, 'rate' => 0.36, 'constant' => 0),
							array('income' => 2600, 'rate' => 0.72, 'constant' => 4.68),
							array('income' => 5200, 'rate' => 2.43, 'constant' => 14.04),
							array('income' => 11700, 'rate' => 4.50, 'constant' => 77.22),
							array('income' => 19500, 'rate' => 6.12, 'constant' => 369.72),
							array('income' => 26000, 'rate' => 6.48, 'constant' => 847.08),
							array('income' => 39000, 'rate' => 6.80, 'constant' => 1268.28),
							array('income' => 58500, 'rate' => 7.92, 'constant' => 2152.28),
							array('income' => 58500, 'rate' => 8.98, 'constant' => 3696.68),
					),
			),
	);

	var $state_options = array(
			20190101 => array( //01-Jan-2019
							   'standard_deduction' => array(1690.00, 4160.00),
							   'allowance'          => 40,
			),
			20060401 => array( //01-Apr-06
							   'standard_deduction' => array(1650.00, 4060.00),
							   'allowance'          => 40,
			),
			20060101 => array(
					'standard_deduction' => array(1500.00, 2600.00),
					'allowance'          => 40,
			),
	);

	function getStateAnnualTaxableIncome() {
		$annual_income = $this->getAnnualTaxableIncome();
		$federal_tax = $this->getFederalTaxPayable();

		$state_deductions = $this->getStateStandardDeduction();

		$income = bcsub( bcsub( $annual_income, $federal_tax ), $state_deductions );

		Debug::text( 'State Annual Taxable Income: ' . $income, __FILE__, __LINE__, __METHOD__, 10 );

		return $income;
	}

	function getStateStandardDeduction() {
		$retarr = $this->getDataFromRateArray( $this->getDate(), $this->state_options );
		if ( $retarr == FALSE ) {
			return FALSE;

		}

		$deduction = $retarr['standard_deduction'];

		if ( $this->getStateAllowance() <= 1 ) {
			$retval = $deduction[0];
		} else {
			$retval = $deduction[1];
		}

		Debug::text( 'Standard Deduction: ' . $retval, __FILE__, __LINE__, __METHOD__, 10 );

		return $retval;
	}

	function getStateAllowanceAmount() {
		$retarr = $this->getDataFromRateArray( $this->getDate(), $this->state_options );
		if ( $retarr == FALSE ) {
			return FALSE;

		}

		$allowance = $retarr['allowance'];

		$retval = bcmul( $allowance, $this->getStateAllowance() );

		Debug::text( 'State Allowance Amount: ' . $retval, __FILE__, __LINE__, __METHOD__, 10 );

		return $retval;
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

		$retval = bcsub( $retval, $this->getStateAllowanceAmount() );

		if ( $retval < 0 ) {
			$retval = 0;
		}

		Debug::text( 'State Annual Tax Payable: ' . $retval, __FILE__, __LINE__, __METHOD__, 10 );

		return $retval;
	}
}

?>
