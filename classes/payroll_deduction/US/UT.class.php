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
class PayrollDeduction_US_UT extends PayrollDeduction_US {

	var $state_income_tax_rate_options = array(
		//See state_options below, as they switched to a completely new formula after this date.
		20080101 => array(
				10 => array(
						array('income' => 6600, 'rate' => 0, 'constant' => 0),
						array('income' => 8200, 'rate' => 1.0, 'constant' => 0),
						array('income' => 11000, 'rate' => 2.0, 'constant' => 0),
						array('income' => 14700, 'rate' => 3.0, 'constant' => 0),
						array('income' => 21100, 'rate' => 4.0, 'constant' => 0),
						array('income' => 39800, 'rate' => 5.0, 'constant' => 0),
						array('income' => 39800, 'rate' => 5.0, 'constant' => 0),
				),
				20 => array(
						array('income' => 13200, 'rate' => 0, 'constant' => 0),
						array('income' => 16400, 'rate' => 1.0, 'constant' => 0),
						array('income' => 22000, 'rate' => 2.0, 'constant' => 0),
						array('income' => 29400, 'rate' => 3.0, 'constant' => 0),
						array('income' => 42200, 'rate' => 4.0, 'constant' => 0),
						array('income' => 79600, 'rate' => 5.0, 'constant' => 0),
						array('income' => 79600, 'rate' => 5.0, 'constant' => 0),
				),
		),
		20070101 => array(
				10 => array(
						array('income' => 2630, 'rate' => 0, 'constant' => 0),
						array('income' => 3630, 'rate' => 2.3, 'constant' => 0),
						array('income' => 4630, 'rate' => 3.1, 'constant' => 23),
						array('income' => 5630, 'rate' => 4.0, 'constant' => 54),
						array('income' => 6630, 'rate' => 4.9, 'constant' => 94),
						array('income' => 8130, 'rate' => 5.7, 'constant' => 143),
						array('income' => 8130, 'rate' => 6.5, 'constant' => 229),
				),
				20 => array(
						array('income' => 2630, 'rate' => 0, 'constant' => 0),
						array('income' => 4630, 'rate' => 2.3, 'constant' => 0),
						array('income' => 6630, 'rate' => 3.1, 'constant' => 46),
						array('income' => 8630, 'rate' => 4.0, 'constant' => 108),
						array('income' => 10630, 'rate' => 4.9, 'constant' => 188),
						array('income' => 13630, 'rate' => 5.7, 'constant' => 286),
						array('income' => 13630, 'rate' => 6.5, 'constant' => 457),
				),
		),
		20060101 => array(
				10 => array(
						array('income' => 2300, 'rate' => 0, 'constant' => 0),
						array('income' => 3163, 'rate' => 2.3, 'constant' => 0),
						array('income' => 4026, 'rate' => 3.1, 'constant' => 20),
						array('income' => 4888, 'rate' => 4.0, 'constant' => 47),
						array('income' => 5750, 'rate' => 4.9, 'constant' => 81),
						array('income' => 6613, 'rate' => 5.7, 'constant' => 123),
						array('income' => 6613, 'rate' => 6.5, 'constant' => 172),
				),
				20 => array(
						array('income' => 2300, 'rate' => 0, 'constant' => 0),
						array('income' => 4026, 'rate' => 2.3, 'constant' => 0),
						array('income' => 5750, 'rate' => 3.1, 'constant' => 40),
						array('income' => 7476, 'rate' => 4.0, 'constant' => 93),
						array('income' => 9200, 'rate' => 4.9, 'constant' => 162),
						array('income' => 10926, 'rate' => 5.7, 'constant' => 246),
						array('income' => 10926, 'rate' => 6.5, 'constant' => 344),
				),
		),
	);

	var $state_options = array(
			20180101 => array(
					'rate'                     => 4.95, //Percent
					'allowance'                => 0,
					'base_allowance'           => array(
							10 => 360,
							20 => 720,
					),
					'allowance_reduction'      => array(
							10 => 7128,
							20 => 14256,
					),
					'allowance_reduction_rate' => 1.3, //Percent
			),
			20080101 => array( //Completely new formula after this date.
							   'rate'                     => 5.0, //Percent
							   'allowance'                => 125,
							   'base_allowance'           => array(
									   10 => 250,
									   20 => 375,
							   ),
							   'allowance_reduction'      => array(
									   10 => 12000,
									   20 => 18000,
							   ),
							   'allowance_reduction_rate' => 1.3, //Percent
			),
	);

	function getStateAnnualTaxableIncome() {
		$income = $this->getAnnualTaxableIncome();

		Debug::text( 'State Annual Taxable Income: ' . $income, __FILE__, __LINE__, __METHOD__, 10 );

		return $income;
	}

	function getStateAllowanceAmount() {
		$retarr = $this->getDataFromRateArray( $this->getDate(), $this->state_options );
		if ( $retarr == FALSE ) {
			return FALSE;
		}

		$allowance_amount = $retarr['allowance'];
		$retval = bcadd( bcmul( $this->getStateAllowance(), $allowance_amount ), $this->getStateBaseAllowanceAmount() );

		Debug::text( 'State Allowance Amount: ' . $retval, __FILE__, __LINE__, __METHOD__, 10 );

		return $retval;
	}

	function getStateBaseAllowanceAmount() {
		$retarr = $this->getDataFromRateArray( $this->getDate(), $this->state_options );
		if ( $retarr == FALSE ) {
			return FALSE;
		}

		$retval = 0;
		if ( isset( $retarr['base_allowance'][ $this->getStateFilingStatus() ] ) ) {
			$retval = $retarr['base_allowance'][ $this->getStateFilingStatus() ];
		}
		Debug::text( 'State Base Allowance Amount: ' . $retval, __FILE__, __LINE__, __METHOD__, 10 );

		return $retval;
	}

	function getStateAllowanceReductionAmount() {
		$retarr = $this->getDataFromRateArray( $this->getDate(), $this->state_options );
		if ( $retarr == FALSE ) {
			return FALSE;
		}

		$allowance_reduction_amount = 0;
		if ( isset( $retarr['allowance_reduction'][ $this->getStateFilingStatus() ] ) ) {
			$allowance_reduction_amount = $retarr['allowance_reduction'][ $this->getStateFilingStatus() ];
		}

		$adjusted_taxable_income = bcsub( $this->getStateAnnualTaxableIncome(), $allowance_reduction_amount );
		if ( $adjusted_taxable_income > 0 ) {
			$allowance_reduction_rate = $retarr['allowance_reduction_rate'];
			$retval = bcmul( $adjusted_taxable_income, bcdiv( $allowance_reduction_rate, 100 ) );
		} else {
			$retval = 0;
		}

		Debug::text( 'State Allowance Reduction Amount: ' . $retval, __FILE__, __LINE__, __METHOD__, 10 );

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

			$gross_tax = bcmul( $annual_income, bcdiv( $retarr['rate'], 100 ) );
			$allowance_amount = bcsub( $this->getStateAllowanceAmount(), $this->getStateAllowanceReductionAmount() );
			if ( $allowance_amount < 0 ) {
				$allowance_amount = 0;
			}

			$retval = bcsub( $gross_tax, $allowance_amount );
		}

		if ( $retval < 0 ) {
			$retval = 0;
		}

		Debug::text( 'State Annual Tax Payable: ' . $retval, __FILE__, __LINE__, __METHOD__, 10 );

		return $retval;
	}
}

?>
