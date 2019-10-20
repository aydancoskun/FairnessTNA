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
/*
														10 => TTi18n::gettext('Rate "A"'),
														20 => TTi18n::gettext('Rate "B"'),
														30 => TTi18n::gettext('Rate "C"'),
														40 => TTi18n::gettext('Rate "D"'),
														50 => TTi18n::gettext('Rate "E"'),

*/

class PayrollDeduction_US_NJ extends PayrollDeduction_US {

	var $state_income_tax_rate_options = array(
			20060101 => array(
					10 => array(
							array('income' => 20000, 'rate' => 1.5, 'constant' => 0),
							array('income' => 35000, 'rate' => 2.0, 'constant' => 300),
							array('income' => 40000, 'rate' => 3.9, 'constant' => 600),
							array('income' => 75000, 'rate' => 6.1, 'constant' => 795),
							array('income' => 500000, 'rate' => 7.0, 'constant' => 2930),
							array('income' => 500000, 'rate' => 9.9, 'constant' => 32680),
					),
					20 => array(
							array('income' => 20000, 'rate' => 1.5, 'constant' => 0),
							array('income' => 50000, 'rate' => 2.0, 'constant' => 300),
							array('income' => 70000, 'rate' => 2.7, 'constant' => 900),
							array('income' => 80000, 'rate' => 3.9, 'constant' => 1440),
							array('income' => 150000, 'rate' => 6.1, 'constant' => 1830),
							array('income' => 500000, 'rate' => 7.0, 'constant' => 6100),
							array('income' => 500000, 'rate' => 9.9, 'constant' => 30600),
					),
					30 => array(
							array('income' => 20000, 'rate' => 1.5, 'constant' => 0),
							array('income' => 40000, 'rate' => 2.3, 'constant' => 300),
							array('income' => 50000, 'rate' => 2.8, 'constant' => 760),
							array('income' => 60000, 'rate' => 3.5, 'constant' => 1040),
							array('income' => 150000, 'rate' => 5.6, 'constant' => 1390),
							array('income' => 500000, 'rate' => 6.6, 'constant' => 6430),
							array('income' => 500000, 'rate' => 9.9, 'constant' => 29530),
					),
					40 => array(
							array('income' => 20000, 'rate' => 1.5, 'constant' => 0),
							array('income' => 40000, 'rate' => 2.7, 'constant' => 300),
							array('income' => 50000, 'rate' => 3.4, 'constant' => 840),
							array('income' => 60000, 'rate' => 4.3, 'constant' => 1180),
							array('income' => 150000, 'rate' => 5.6, 'constant' => 1610),
							array('income' => 500000, 'rate' => 6.5, 'constant' => 6650),
							array('income' => 500000, 'rate' => 9.9, 'constant' => 29400),
					),
					50 => array(
							array('income' => 20000, 'rate' => 1.5, 'constant' => 0),
							array('income' => 35000, 'rate' => 2.0, 'constant' => 300),
							array('income' => 100000, 'rate' => 5.8, 'constant' => 600),
							array('income' => 500000, 'rate' => 6.5, 'constant' => 4370),
							array('income' => 500000, 'rate' => 9.9, 'constant' => 30370),
					),
			),
	);

	var $state_options = array(
			20060101 => array(
					'allowance' => 1000,
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
