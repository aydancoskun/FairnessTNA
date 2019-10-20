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
class PayrollDeduction_US_MD_ALL extends PayrollDeduction_US_MD {

	var $district_options = array(
		20180601 => array( //01-Jun-2018
				'standard_deduction_rate'    => 15,
				'standard_deduction_minimum' => 1500,
				'standard_deduction_maximum' => 2250,
				'allowance'                  => 3200,
		),

		//01-Jan-12: No change.
		//01-Jan-11: No change.
		//01-Jan-10: No change.
		//01-Jan-09: No change.
		20080701 => array(
				'standard_deduction_rate'    => 15,
				'standard_deduction_minimum' => 1500,
				'standard_deduction_maximum' => 2000,
				'allowance'                  => 3200,
		),
		20060101 => array(
				'standard_deduction_rate'    => 15,
				'standard_deduction_minimum' => 1500,
				'standard_deduction_maximum' => 2000,
				'allowance'                  => 2400,
		),
	);

	function getDistrictAnnualTaxableIncome() {
		$annual_income = $this->getAnnualTaxableIncome();
		$standard_deduction = $this->getDistrictStandardDeductionAmount();
		$district_allowance = $this->getDistrictAllowanceAmount();

		$income = bcsub( bcsub( $annual_income, $standard_deduction ), $district_allowance );

		Debug::text( 'District Annual Taxable Income: ' . $income, __FILE__, __LINE__, __METHOD__, 10 );

		return $income;
	}

	function getDistrictStandardDeductionAmount() {
		$retarr = $this->getDataFromRateArray( $this->getDate(), $this->district_options );
		if ( $retarr == FALSE ) {
			return FALSE;
		}

		$rate = bcdiv( $retarr['standard_deduction_rate'], 100 );

		$deduction = bcmul( $this->getAnnualTaxableIncome(), $rate );

		if ( $deduction < $retarr['standard_deduction_minimum'] ) {
			$retval = $retarr['standard_deduction_minimum'];
		} elseif ( $deduction > $retarr['standard_deduction_maximum'] ) {
			$retval = $retarr['standard_deduction_maximum'];
		} else {
			$retval = $deduction;
		}

		Debug::text( 'District Standard Deduction Amount: ' . $retval, __FILE__, __LINE__, __METHOD__, 10 );

		return $retval;
	}

	function getDistrictAllowanceAmount() {
		$retarr = $this->getDataFromRateArray( $this->getDate(), $this->district_options );
		if ( $retarr == FALSE ) {
			return FALSE;
		}

		$allowance_arr = $retarr['allowance'];

		$retval = bcmul( $this->getDistrictAllowance(), $allowance_arr );

		Debug::text( 'District Allowance Amount: ' . $retval, __FILE__, __LINE__, __METHOD__, 10 );

		return $retval;
	}

	function getDistrictTaxPayable() {
		$annual_income = $this->getDistrictAnnualTaxableIncome();

		$rate = bcdiv( $this->getUserValue1(), 100 );

		$retval = bcmul( $annual_income, $rate );

		if ( $retval < 0 ) {
			$retval = 0;
		}

		Debug::text( 'District Annual Tax Payable: ' . $retval, __FILE__, __LINE__, __METHOD__, 10 );

		return $retval;
	}
}

?>
