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
class PayrollDeduction_US_AZ extends PayrollDeduction_US {

	function _getStateTaxPayable() {
		//Arizona is a percent of federal tax rate.
		//However after 01-Jul-10 it changed to a straight percent of gross.
		$annual_income = $this->getAnnualTaxableIncome();

		$rate = $this->getUserValue1();
		Debug::text( 'Raw Rate: ' . $rate, __FILE__, __LINE__, __METHOD__, 10 );

		//Because of the change from a percent of federal rate to a gross rate,
		//add some checks so if an employee's amount isn't changed we default to the closest rate.
		if ( $rate >= 39.5 ) {
			$rate = 5.1;
		} elseif ( $rate >= 33.1 ) {
			$rate = 4.2;
		} elseif ( $rate >= 26.7 ) {
			$rate = 3.6;
		} elseif ( $rate >= 24.5 ) {
			$rate = 2.7;
		} elseif ( $rate >= 20.3 ) {
			$rate = 1.8;
		} elseif ( $rate >= 10.7 ) {
			$rate = 1.3;
		}
		Debug::text( ' Adjusted Rate: ' . $rate, __FILE__, __LINE__, __METHOD__, 10 );
		$retval = bcmul( $annual_income, bcdiv( $rate, 100 ) );

		if ( $retval < 0 ) {
			$retval = 0;
		}

		Debug::text( 'State Annual Tax Payable: ' . $retval, __FILE__, __LINE__, __METHOD__, 10 );

		return $retval;
	}
}

?>
