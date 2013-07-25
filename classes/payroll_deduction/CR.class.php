<?php
/*********************************************************************************
 * This file is part of "Fairness", a Payroll and Time Management program.
 * Fairness is Copyright 2013 Aydan Coscun (aydan.ayfer.coskun@gmail.com)
 * Portions of this software are Copyright (C) 2003 - 2013 TimeTrex Software Inc.
 * because Fairness is a fork of "TimeTrex Workforce Management" Software.
 *
 * Fairness is free software; you can redistribute it and/or modify it under the
 * terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation, either version 3 of the License, or (at you option )
 * any later version.
 *
 * Fairness is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE.  See the GNU Affero General Public License for more
 * details.
 *
 * You should have received a copy of the GNU Affero General Public License along
 * with this program; if not, see http://www.gnu.org/licenses or write to the Free
 * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301 USA.
  ********************************************************************************/
/*
 * $Revision: 1615 $
 * $Id: US.class.php 1615 2008-01-08 00:49:02Z ipso $
 * $Date: 2008-01-07 16:49:02 -0800 (Mon, 07 Jan 2008) $
 */

/**
 * @package PayrollDeduction\CR
 */
class PayrollDeduction_CR extends PayrollDeduction_CR_Data {
	//
	// Federal
	//
	function setFederalFilingStatus($value) {
		$this->data['federal_filing_status'] = $value;

		return TRUE;
	}
	function getFederalFilingStatus() {
		if ( isset($this->data['federal_filing_status']) ) {
			return $this->data['federal_filing_status'];
		}

		return 10; //Single
	}

	function setFederalAllowance($value) {
		$this->data['federal_allowance'] = $value;

		return TRUE;
	}
	function getFederalAllowance() {
		if ( isset($this->data['federal_allowance']) ) {
			return $this->data['federal_allowance'];
		}

		return FALSE;
	}

	//
	// Calculation Functions
	//
	function getAnnualTaxableIncome() {

		$retval = bcmul( $this->getGrossPayPeriodIncome(), $this->getAnnualPayPeriods() );

		Debug::text('Annual Taxable Income: '. $retval, __FILE__, __LINE__, __METHOD__, 10);

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

		Debug::text('Annual Taxable Income: '. $annual_taxable_income, __FILE__, __LINE__, __METHOD__, 10);
		Debug::text('Allowance: '. $annual_allowance, __FILE__, __LINE__, __METHOD__, 10);

		if ( $this->getFederalFilingStatus() == 20 ) {
			$annual_filing = $this->getFederalFilingAmount( $this->getData() );
		} else {
			$annual_filing = 0;
		}

		Debug::text('Filing: '. $annual_filing, __FILE__, __LINE__, __METHOD__, 10);

		$taxTable = $this->getData()->getFederalTaxTable($annual_taxable_income);

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
						$tax = bcadd( $tax, (bcmul($rate, bcsub($annual_taxable_income, $prev_income))) );
					} else {
						$tmp_prev_income[$i] = $prev_income;
						if ( $i >= 2 AND $i < 3 ) {
							if ( $annual_taxable_income > $income ) {
								$tax = bcadd( $tax, bcmul($prev_rate, bcsub($prev_income, $tmp_prev_income[$i - 1])) );
								$tax = bcadd( $tax, bcmul($rate, bcsub($annual_taxable_income, $income)) );
							}
						}
					}
				}

				$i++;
			}

			$tax = bcsub($tax, $AD);
		}else{
			Debug::text('Income is less then Total Income Tax Adjustments: ', __FILE__, __LINE__, __METHOD__, 10);

			$tax = 0;
		}

		if ( $tax < 0 ) {
			$tax = 0;
		}

		Debug::text('RetVal: '. $tax, __FILE__, __LINE__, __METHOD__, 10);

		return $tax;
	}
}
?>