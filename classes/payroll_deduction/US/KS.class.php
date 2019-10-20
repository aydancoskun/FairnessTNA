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
class PayrollDeduction_US_KS extends PayrollDeduction_US {

	var $state_income_tax_rate_options = array(
			20170601 => array(
					10 => array(
							array('income' => 3000, 'rate' => 0, 'constant' => 0),
							array('income' => 18000, 'rate' => 3.1, 'constant' => 0),
							array('income' => 33000, 'rate' => 5.25, 'constant' => 465),
							array('income' => 33000, 'rate' => 5.70, 'constant' => 1252.50),
					),
					20 => array(
							array('income' => 7500, 'rate' => 0, 'constant' => 0),
							array('income' => 37500, 'rate' => 3.1, 'constant' => 0),
							array('income' => 67500, 'rate' => 5.25, 'constant' => 930),
							array('income' => 67500, 'rate' => 5.70, 'constant' => 2505),
					),
			),
			20150101 => array(
					10 => array(
							array('income' => 3000, 'rate' => 0, 'constant' => 0),
							array('income' => 18000, 'rate' => 2.7, 'constant' => 0),
							array('income' => 18000, 'rate' => 4.6, 'constant' => 405),
					),
					20 => array(
							array('income' => 6000, 'rate' => 0, 'constant' => 0),
							array('income' => 36000, 'rate' => 2.7, 'constant' => 0),
							array('income' => 36000, 'rate' => 4.6, 'constant' => 810),
					),
			),
			20140101 => array(
					10 => array(
							array('income' => 3000, 'rate' => 0, 'constant' => 0),
							array('income' => 18000, 'rate' => 2.7, 'constant' => 0),
							array('income' => 18000, 'rate' => 4.8, 'constant' => 405),
					),
					20 => array(
							array('income' => 6000, 'rate' => 0, 'constant' => 0),
							array('income' => 36000, 'rate' => 2.7, 'constant' => 0),
							array('income' => 36000, 'rate' => 4.8, 'constant' => 810),
					),
			),
			20130101 => array(
					10 => array(
							array('income' => 3000, 'rate' => 0, 'constant' => 0),
							array('income' => 18000, 'rate' => 3.0, 'constant' => 0),
							array('income' => 18000, 'rate' => 4.9, 'constant' => 450),
					),
					20 => array(
							array('income' => 6000, 'rate' => 0, 'constant' => 0),
							array('income' => 36000, 'rate' => 3.0, 'constant' => 0),
							array('income' => 36000, 'rate' => 4.9, 'constant' => 900),
					),
			),
			20060101 => array(
					10 => array(
							array('income' => 3000, 'rate' => 0, 'constant' => 0),
							array('income' => 18000, 'rate' => 3.5, 'constant' => 0),
							array('income' => 33000, 'rate' => 6.25, 'constant' => 525),
							array('income' => 33000, 'rate' => 6.45, 'constant' => 1462.50),
					),
					20 => array(
							array('income' => 6000, 'rate' => 0, 'constant' => 0),
							array('income' => 36000, 'rate' => 3.5, 'constant' => 0),
							array('income' => 66000, 'rate' => 6.25, 'constant' => 1050),
							array('income' => 66000, 'rate' => 6.45, 'constant' => 2925),
					),
			),
	);

	var $state_options = array(
			20060101 => array(
					'allowance' => 2250,
			),
	);

	function getStateAnnualTaxableIncome() {
		$annual_income = $this->getAnnualTaxableIncome();
		$state_allowance = $this->getStateAllowanceAmount();

		$income = bcsub( $annual_income, $state_allowance );

		Debug::text( 'State Annual Taxable Income: ' . $income, __FILE__, __LINE__, __METHOD__, 10 );

		return $income;
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
