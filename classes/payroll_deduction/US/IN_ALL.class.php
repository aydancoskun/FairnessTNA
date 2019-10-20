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
class PayrollDeduction_US_IN_ALL extends PayrollDeduction_US_IN {

	function getDistrictAnnualTaxableIncome() {
		$annual_income = $this->getAnnualTaxableIncome();
		$state_allowance = $this->getStateAllowanceAmount();
		$state_dependant_allowance = $this->getStateDependantAllowanceAmount();

		$income = bcsub( bcsub( $annual_income, $state_allowance ), $state_dependant_allowance );

		Debug::text( 'District Annual Taxable Income: ' . $income, __FILE__, __LINE__, __METHOD__, 10 );

		return $income;
	}

	function getDistrictTaxPayable() {
		$annual_income = $this->getDistrictAnnualTaxableIncome();

		$retval = 0;

		if ( $annual_income > 0 ) {
			$rate = bcdiv( $this->getUserValue3(), 100 );

			$retval = bcmul( $annual_income, $rate );
		}

		if ( $retval < 0 ) {
			$retval = 0;
		}

		Debug::text( 'District Annual Tax Payable: ' . $retval, __FILE__, __LINE__, __METHOD__, 10 );

		return $retval;
	}
}

?>
