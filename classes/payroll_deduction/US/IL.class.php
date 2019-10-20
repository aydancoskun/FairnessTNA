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
class PayrollDeduction_US_IL extends PayrollDeduction_US {

	var $state_options = array(
			20190101 => array( // 01-Jan-2019
							   'rate'             => 4.95,
							   'line_1_allowance' => 2275,
							   'line_2_allowance' => 1000,
			),
			20180101 => array( // 01-Jan-2018
							   'rate'             => 4.95,
							   'line_1_allowance' => 2225,
							   'line_2_allowance' => 1000,
			),
			20170701 => array( // 01-Jul-2017
							   'rate'             => 4.95,
							   'line_1_allowance' => 2175,
							   'line_2_allowance' => 1000,
			),
			20160101 => array( // 01-Jan-2016
							   'rate'             => 3.75,
							   'line_1_allowance' => 2175,
							   'line_2_allowance' => 1000,
			),
			20150101 => array( // 01-Jan-2015
							   'rate'             => 3.75,
							   'line_1_allowance' => 2150,
							   'line_2_allowance' => 1000,
			),
			20140101 => array( // 01-Jan-2014
							   'rate'             => 5.0,
							   'line_1_allowance' => 2125,
							   'line_2_allowance' => 1000,
			),
			20130101 => array( // 01-Jan-2013
							   'rate'             => 5.0,
							   'line_1_allowance' => 2100,
							   'line_2_allowance' => 1000,
			),
			20060101 => array(
					'rate'             => 3.0,
					'line_1_allowance' => 2000,
					'line_2_allowance' => 1000,
			),
	);

	function getStateAnnualTaxableIncome() {
		$annual_income = $this->getAnnualTaxableIncome();

		$line_1_allowance = $this->getStateLine1AllowanceAmount();
		$line_2_allowance = $this->getStateLine2AllowanceAmount();

		$income = bcsub( bcsub( $annual_income, $line_1_allowance ), $line_2_allowance );

		Debug::text( 'State Annual Taxable Income: ' . $income, __FILE__, __LINE__, __METHOD__, 10 );

		return $income;
	}


	function getStateLine1AllowanceAmount() {
		$retarr = $this->getDataFromRateArray( $this->getDate(), $this->state_options );
		if ( $retarr == FALSE ) {
			return FALSE;
		}

		$allowance = $retarr['line_1_allowance'];

		$retval = bcmul( $this->getUserValue1(), $allowance );

		Debug::text( 'State Line 1 Allowance Amount: ' . $retval, __FILE__, __LINE__, __METHOD__, 10 );

		return $retval;
	}

	function getStateLine2AllowanceAmount() {
		$retarr = $this->getDataFromRateArray( $this->getDate(), $this->state_options );
		if ( $retarr == FALSE ) {
			return FALSE;
		}

		$allowance = $retarr['line_2_allowance'];

		$retval = bcmul( $this->getUserValue2(), $allowance );

		Debug::text( 'State Line 1 Allowance Amount: ' . $retval, __FILE__, __LINE__, __METHOD__, 10 );

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

		Debug::text( 'State Annual Tax Payable: ' . $retval, __FILE__, __LINE__, __METHOD__, 10 );

		if ( $retval < 0 ) {
			$retval = 0;
		}

		return $retval;
	}
}

?>
