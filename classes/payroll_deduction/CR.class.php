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
 * @package PayrollDeduction\CR
 */
class PayrollDeduction_CR extends PayrollDeduction_CR_Data {
	//
	// Federal
	//
	function setFederalFilingStatus( $value ) {
		$this->data['federal_filing_status'] = $value;

		return TRUE;
	}

	function getFederalFilingStatus() {
		if ( isset( $this->data['federal_filing_status'] ) ) {
			return $this->data['federal_filing_status'];
		}

		return 10; //Single
	}

	function setFederalAllowance( $value ) {
		$this->data['federal_allowance'] = $value;

		return TRUE;
	}

	function getFederalAllowance() {
		if ( isset( $this->data['federal_allowance'] ) ) {
			return $this->data['federal_allowance'];
		}

		return FALSE;
	}

	//
	// Calculation Functions
	//
	function getAnnualTaxableIncome() {

		$retval = bcmul( $this->getGrossPayPeriodIncome(), $this->getAnnualPayPeriods() );

		Debug::text( 'Annual Taxable Income: ' . $retval, __FILE__, __LINE__, __METHOD__, 10 );

		return $retval;
	}

	//
	// Federal Tax
	//
	function getFederalPayPeriodDeductions() {
		return $this->convertToUserCurrency( bcdiv( $this->getFederalTaxPayable(), $this->getAnnualPayPeriods() ) );
	}

	function getFederalTaxPayable() {

		$annual_taxable_income = $this->getAnnualTaxableIncome();
		$annual_allowance = bcmul( $this->getFederalAllowanceAmount( $this->getDate() ), $this->getFederalAllowance() );

		Debug::text( 'Annual Taxable Income: ' . $annual_taxable_income, __FILE__, __LINE__, __METHOD__, 10 );
		Debug::text( 'Allowance: ' . $annual_allowance, __FILE__, __LINE__, __METHOD__, 10 );

		if ( $this->getFederalFilingStatus() == 20 ) {
			$annual_filing = $this->getFederalFilingAmount( $this->getData() );
		} else {
			$annual_filing = 0;
		}

		Debug::text( 'Filing: ' . $annual_filing, __FILE__, __LINE__, __METHOD__, 10 );

		$taxTable = $this->getData()->getFederalTaxTable( $annual_taxable_income );

		/*
		 *	T = Total Income Tax calculated for that employee
		 *	TT1= Tax Tier 1, ranging from CRC 0 to ~ CRC 6MM
		 *	TT2 = Tax Tier 2, ranging from ~ CRC 6MM to ~ CRC 9MM
		 *	TT3 = Tax Tier 3, above ~ CRC 9MM
		 *	AD = Total Income Tax Adjustments
		 *
		 *	T =  (TT1 + TT2 + TT3)  – AD
		*/

		$AD = $annual_allowance + $annual_filing;
		$tax = 0;
		if ( $annual_taxable_income > $AD ) {
			$tmp_prev_income = array();
			$i = 0;

			foreach ( $taxTable as $taxTier ) {
				$prev_income = $taxTier['prev_income'];
				$prev_rate = $taxTier['prev_rate'];
				$income = $taxTier['income'];
				$rate = $taxTier['rate'];

				if ( $prev_income != 0 AND $prev_income > 0 ) {

					if ( $annual_taxable_income > $prev_income AND $annual_taxable_income <= $income ) {
						$tax = bcadd( $tax, ( bcmul( $rate, bcsub( $annual_taxable_income, $prev_income ) ) ) );
					} else {
						$tmp_prev_income[ $i ] = $prev_income;
						if ( $i >= 2 AND $i < 3 ) {
							if ( $annual_taxable_income > $income ) {
								$tax = bcadd( $tax, bcmul( $prev_rate, bcsub( $prev_income, $tmp_prev_income[ $i - 1 ] ) ) );
								$tax = bcadd( $tax, bcmul( $rate, bcsub( $annual_taxable_income, $income ) ) );
							}
						}
					}
				}

				$i++;
			}

			$tax = bcsub( $tax, $AD );
		} else {
			Debug::text( 'Income is less then Total Income Tax Adjustments: ', __FILE__, __LINE__, __METHOD__, 10 );

			$tax = 0;
		}

		if ( $tax < 0 ) {
			$tax = 0;
		}

		Debug::text( 'RetVal: ' . $tax, __FILE__, __LINE__, __METHOD__, 10 );

		return $tax;
	}
}

?>