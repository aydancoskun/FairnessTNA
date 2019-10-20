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
class PayrollDeduction_US_MI extends PayrollDeduction_US {

	var $state_options = array(
			20190101 => array( //01-Jan-10
							   'rate'      => 4.25,
							   'allowance' => 4400,
			),
			20180101 => array( //01-Jan-18
							   'rate'      => 4.25,
							   'allowance' => 4050,
			),
			20140101 => array( //01-Jan-14
							   'rate'      => 4.25,
							   'allowance' => 4000,
			),
			20130101 => array( //01-Jan-13
							   'rate'      => 4.25,
							   'allowance' => 3950,
			),
			20110101 => array( //01-Jan-11
							   'rate'      => 4.35,
							   'allowance' => 3700,
			),
			20090101 => array( //01-Jan-09
							   'rate'      => 4.35,
							   'allowance' => 3600,
			),
			20071001 => array( //01-Oct-07
							   'rate'      => 4.35,
							   'allowance' => 3400,
			),
			20070101 => array(
					'rate'      => 3.9,
					'allowance' => 3400,
			),
			20060101 => array(
					'rate'      => 3.9,
					'allowance' => 3300,
			),
	);

	function getStateAnnualTaxableIncome() {
		$annual_income = $this->getAnnualTaxableIncome();

		$allowance = $this->getStateAllowanceAmount();

		$income = bcsub( $annual_income, $allowance );

		Debug::text( 'State Annual Taxable Income: ' . $income, __FILE__, __LINE__, __METHOD__, 10 );

		return $income;
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
			$retarr = $this->getDataFromRateArray( $this->getDate(), $this->state_options );
			if ( $retarr == FALSE ) {
				return FALSE;
			}

			$rate = bcdiv( $retarr['rate'], 100 );
			$retval = bcmul( $annual_income, $rate );
		}

		if ( $retval < 0 ) {
			$retval = 0;
		}

		Debug::text( 'State Annual Tax Payable: ' . $retval, __FILE__, __LINE__, __METHOD__, 10 );

		return $retval;
	}
}

?>
