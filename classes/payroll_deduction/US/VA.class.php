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
class PayrollDeduction_US_VA extends PayrollDeduction_US {

	var $state_income_tax_rate_options = array(
			20060101 => array(
					0 => array(
							array('income' => 3000, 'rate' => 2, 'constant' => 0),
							array('income' => 5000, 'rate' => 3, 'constant' => 60),
							array('income' => 17000, 'rate' => 5, 'constant' => 120),
							array('income' => 17000, 'rate' => 5.75, 'constant' => 720),
					),
			),
	);

	var $state_options = array(
			20190101 => array(
					'standard_deduction' => 4500,
					'allowance'          => 930,
					'age65_allowance'    => 800,
			),
			20080101 => array(
					'standard_deduction' => 3000,
					'allowance'          => 930,
					'age65_allowance'    => 800,
			),
			20060101 => array(
					'standard_deduction' => 3000,
					'allowance'          => 900,
					'age65_allowance'    => 800,
			),
	);

	function getStateAnnualTaxableIncome() {
		$annual_income = $this->getAnnualTaxableIncome();
		$state_standard_deduction = $this->getStateStandardDeductionAmount();
		$state_allowance = $this->getStateAllowanceAmount();
		$state_age65_allowance = $this->getStateAge65AllowanceAmount();

		$income = bcsub( bcsub( bcsub( $annual_income, $state_standard_deduction ), $state_allowance ), $state_age65_allowance );

		Debug::text( 'State Annual Taxable Income: ' . $income, __FILE__, __LINE__, __METHOD__, 10 );

		return $income;
	}

	function getStateStandardDeductionAmount() {
		$retarr = $this->getDataFromRateArray( $this->getDate(), $this->state_options );
		if ( $retarr == FALSE ) {
			return FALSE;

		}

		$retval = $retarr['standard_deduction'];

		Debug::text( 'State Standard Deduction Amount: ' . $retval, __FILE__, __LINE__, __METHOD__, 10 );

		return $retval;
	}

	function getStateAllowanceAmount() {
		$retarr = $this->getDataFromRateArray( $this->getDate(), $this->state_options );
		if ( $retarr == FALSE ) {
			return FALSE;

		}

		$allowance_arr = $retarr['allowance'];

		$retval = bcmul( $this->getUserValue1(), $allowance_arr );

		Debug::text( 'State Allowance Amount: ' . $retval, __FILE__, __LINE__, __METHOD__, 10 );

		return $retval;
	}

	function getStateAge65AllowanceAmount() {
		$retarr = $this->getDataFromRateArray( $this->getDate(), $this->state_options );
		if ( $retarr == FALSE ) {
			return FALSE;

		}

		$allowance_arr = $retarr['age65_allowance'];

		$retval = bcmul( $this->getUserValue2(), $allowance_arr );

		Debug::text( 'State Age65 Allowance Amount: ' . $retval, __FILE__, __LINE__, __METHOD__, 10 );

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
			//$retval = bcadd( bcmul( $annual_income, $rate ), $state_constant );
		}

		if ( $retval < 0 ) {
			$retval = 0;
		}

		Debug::text( 'State Annual Tax Payable: ' . $retval, __FILE__, __LINE__, __METHOD__, 10 );

		return $retval;
	}
}

?>
