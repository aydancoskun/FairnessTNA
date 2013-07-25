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
 * $Revision: 8371 $
 * $Id: UT.class.php 8371 2012-11-22 21:18:57Z ipso $
 * $Date: 2012-11-22 13:18:57 -0800 (Thu, 22 Nov 2012) $
 */

/**
 * @package PayrollDeduction\US
 */
class PayrollDeduction_US_UT extends PayrollDeduction_US {

	var $state_options = array(
								1199174400 => array( //Completely new formula after this date.
													'rate' => 5.0, //Percent
													'allowance' => 125,
													'base_allowance' => array(
																					10 => 250,
																					20 => 375
																					),
													'allowance_reduction' => array(
																					10 => 12000,
																					20 => 18000
																					),
													'allowance_reduction_rate' => 1.3, //Percent
													),
								);

	function getStatePayPeriodDeductions() {
		return bcdiv( $this->getStateTaxPayable(), $this->getAnnualPayPeriods() );
	}

	function getStateAnnualTaxableIncome() {
		$income = $this->getAnnualTaxableIncome();

		Debug::text('State Annual Taxable Income: '. $income, __FILE__, __LINE__, __METHOD__, 10);

		return $income;
	}

	function getStateAllowanceAmount() {
		$retarr = $this->getDataFromRateArray($this->getDate(), $this->state_options);
		if ( $retarr == FALSE ) {
			return FALSE;
		}

		$allowance_amount = $retarr['allowance'];
		$retval = bcadd( bcmul( $this->getStateAllowance(), $allowance_amount ), $this->getStateBaseAllowanceAmount() );

		Debug::text('State Allowance Amount: '. $retval, __FILE__, __LINE__, __METHOD__, 10);

		return $retval;
	}

	function getStateBaseAllowanceAmount() {
		$retarr = $this->getDataFromRateArray($this->getDate(), $this->state_options);
		if ( $retarr == FALSE ) {
			return FALSE;
		}

		$retval = 0;
		if ( isset($retarr['base_allowance'][$this->getStateFilingStatus()]) ) {
			$retval = $retarr['base_allowance'][$this->getStateFilingStatus()];
		}
		Debug::text('State Base Allowance Amount: '. $retval, __FILE__, __LINE__, __METHOD__, 10);

		return $retval;
	}

	function getStateAllowanceReductionAmount() {
		$retarr = $this->getDataFromRateArray($this->getDate(), $this->state_options);
		if ( $retarr == FALSE ) {
			return FALSE;
		}

		$allowance_reduction_amount = 0;
		if ( isset($retarr['allowance_reduction'][$this->getStateFilingStatus()]) ) {
			$allowance_reduction_amount = $retarr['allowance_reduction'][$this->getStateFilingStatus()];
		}

		$adjusted_taxable_income = bcsub( $this->getStateAnnualTaxableIncome(), $allowance_reduction_amount );
		if ( $adjusted_taxable_income > 0 )  {
			$allowance_reduction_rate = $retarr['allowance_reduction_rate'];
			$retval = bcmul( $adjusted_taxable_income, bcdiv( $allowance_reduction_rate, 100 ) );
		} else {
			$retval = 0;
		}

		Debug::text('State Allowance Reduction Amount: '. $retval, __FILE__, __LINE__, __METHOD__, 10);

		return $retval;
	}


	function getStateTaxPayable() {
		$annual_income = $this->getStateAnnualTaxableIncome();

		$retval = 0;

		if ( $annual_income > 0 ) {
			$retarr = $this->getDataFromRateArray($this->getDate(), $this->state_options);
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

		Debug::text('State Annual Tax Payable: '. $retval, __FILE__, __LINE__, __METHOD__, 10);

		return $retval;
	}
}
?>
