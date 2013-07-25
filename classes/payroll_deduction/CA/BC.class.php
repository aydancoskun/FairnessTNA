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
 * $Revision: 8720 $
 * $Id: BC.class.php 8720 2012-12-29 01:06:58Z ipso $
 * $Date: 2012-12-28 17:06:58 -0800 (Fri, 28 Dec 2012) $
 */

/**
 * @package PayrollDeduction\CA
 */
class PayrollDeduction_CA_BC extends PayrollDeduction_CA {
	function getProvincialTaxReduction() {

		$A = $this->getAnnualTaxableIncome();
		$T4 = $this->getProvincialBasicTax();
		$V1 = $this->getProvincialSurtax();
		$Y = 0;
		$S = 0;

		Debug::text('BC Specific - Province: '. $this->getProvince(), __FILE__, __LINE__, __METHOD__, 10);
		$tax_reduction_data = $this->getProvincialTaxReductionData( $this->getDate(), $this->getProvince() );
		if ( is_array($tax_reduction_data) ) {
			if ( $A <= $tax_reduction_data['income1'] ) {
				Debug::text('S: Annual Income less than: '. $tax_reduction_data['income1'], __FILE__, __LINE__, __METHOD__, 10);
				if ( $T4 > $tax_reduction_data['amount'] ) {
					$S = $tax_reduction_data['amount'];
				} else {
					$S = $T4;
				}
			} elseif ( $A > $tax_reduction_data['income1'] AND $A <= $tax_reduction_data['income2'] ) {
				Debug::text('S: Annual Income less than '. $tax_reduction_data['income2'], __FILE__, __LINE__, __METHOD__, 10);

				$tmp_S = bcsub( $tax_reduction_data['amount'], bcmul( bcsub( $A, $tax_reduction_data['income1'] ), $tax_reduction_data['rate'] ) );
				Debug::text('Tmp_S: '. $tmp_S, __FILE__, __LINE__, __METHOD__, 10);

				if ( $T4 > $tmp_S ) {
					$S = $tmp_S;
				} else {
					$S = $T4;
				}
				unset($tmp_S);
			}
		}
		Debug::text('aS: '. $S, __FILE__, __LINE__, __METHOD__, 10);

		if ( $S < 0 ) {
			$S = 0;
		}

		Debug::text('bS: '. $S, __FILE__, __LINE__, __METHOD__, 10);

		return $S;
	}
}
?>
