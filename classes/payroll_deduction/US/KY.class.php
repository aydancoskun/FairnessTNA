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
class PayrollDeduction_US_KY extends PayrollDeduction_US {

	var $state_income_tax_rate_options = array(
		//20180101 - Switched to 5% flat rate, see getStateTaxPayable() below
		20060101 => array(
				0 => array(
						array('income' => 3000, 'rate' => 2, 'constant' => 0),
						array('income' => 4000, 'rate' => 3, 'constant' => 60),
						array('income' => 5000, 'rate' => 4, 'constant' => 90),
						array('income' => 8000, 'rate' => 5, 'constant' => 130),
						array('income' => 75000, 'rate' => 5.8, 'constant' => 280),
						array('income' => 75000, 'rate' => 6, 'constant' => 4166),
				),
		),
	);


	var $state_options = array(
			20190101 => array( //01-Jan-2019
							   'standard_deduction' => 2590,
							   'allowance'          => 0, //Removed as of 2018
			),
			20180101 => array( //01-Jan-2018
							   'standard_deduction' => 2530,
							   'allowance'          => 0, //Removed as of 2018
			),
			20170101 => array( //01-Jan-2017
							   'standard_deduction' => 2480,
							   'allowance'          => 10,
			),
			20160101 => array( //01-Jan-2016
							   'standard_deduction' => 2460,
							   'allowance'          => 20,
			),
			20150101 => array( //01-Jan-2015
							   'standard_deduction' => 2440,
							   'allowance'          => 20,
			),
			20140101 => array( //01-Jan-2014
							   'standard_deduction' => 2400,
							   'allowance'          => 20,
			),
			20130101 => array( //01-Jan-2013
							   'standard_deduction' => 2360,
							   'allowance'          => 20,
			),
			20120101 => array( //01-Jan-2012
							   'standard_deduction' => 2290,
							   'allowance'          => 20,
			),
			20090101 => array( //01-Jan-2009
							   'standard_deduction' => 2190,
							   'allowance'          => 20,
			),
			20080101 => array(
					'standard_deduction' => 2100,
					'allowance'          => 20,
			),
			20070101 => array(
					'standard_deduction' => 2050,
					'allowance'          => 20,
			),
			20060101 => array(
					'standard_deduction' => 1970,
					'allowance'          => 22,
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
		$retval = bcmul( $this->getStateAllowance(), $allowance_arr );

		Debug::text( 'State Allowance Amount: ' . $retval, __FILE__, __LINE__, __METHOD__, 10 );

		return $retval;
	}

	function _getStateTaxPayable() {
		$annual_income = $this->getStateAnnualTaxableIncome();

		$retval = 0;

		if ( $annual_income > 0 ) {
			if ( $this->getDate() >= 20180101 ) { //Switched to flat rate 5%
				$retval = bcmul( $annual_income, bcdiv( 5.0, 100 ) );
			} else {
				$rate = $this->getData()->getStateRate( $annual_income );
				$prev_income = $this->getData()->getStateRatePreviousIncome( $annual_income );
				$state_constant = $this->getData()->getStateConstant( $annual_income );

				$retval = bcadd( bcmul( bcsub( $annual_income, $prev_income ), $rate ), $state_constant );
			}
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
