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
 * $Id: ON.class.php 8720 2012-12-29 01:06:58Z ipso $
 * $Date: 2012-12-28 17:06:58 -0800 (Fri, 28 Dec 2012) $
 */

/**
 * @package PayrollDeduction\CA
 */
class PayrollDeduction_CA_ON extends PayrollDeduction_CA {
	function getProvincialTaxReduction() {

		$A = $this->getAnnualTaxableIncome();
		$T4 = $this->getProvincialBasicTax();
		$V1 = $this->getProvincialSurtax();
		$Y = 0;
		$S = 0;

		Debug::text('ON Specific - Province: '. $this->getProvince(), __FILE__, __LINE__, __METHOD__, 10);
		$tax_reduction_data = $this->getProvincialTaxReductionData( $this->getDate(), $this->getProvince() );
		if ( is_array($tax_reduction_data) ) {
			$tmp_Sa = bcadd($T4, $V1);
			$tmp_Sb = bcsub( bcmul( 2, bcadd( $tax_reduction_data['amount'], $Y ) ), bcadd( $T4, $V1 ) );

			if ( $tmp_Sa < $tmp_Sb ) {
				$S = $tmp_Sa;
			} else {
				$S = $tmp_Sb;
			}
		}
		Debug::text('aS: '. $S, __FILE__, __LINE__, __METHOD__, 10);

		if ( $S < 0 ) {
			$S = 0;
		}

		Debug::text('bS: '. $S, __FILE__, __LINE__, __METHOD__, 10);

		return $S;
	}

	function getProvincialSurtax() {
		/*
			V1 =
			For Ontario
				Where T4 <= 4016
				V1 = 0

				Where T4 > 4016 <= 5065
				V1 = 0.20 * ( T4 - 4016 )

				Where T4 > 5065
				V1 = 0.20 * (T4 - 4016) + 0.36 * (T4 - 5065)

		*/

		$T4 = $this->getProvincialBasicTax();
		$V1 = 0;

		$surtax_data = $this->getProvincialSurTaxData( $this->getDate(), $this->getProvince() );
		if ( is_array($surtax_data) ) {
			if ( $T4 < $surtax_data['income1'] ) {
				$V1 = 0;
			} elseif ( $T4 > $surtax_data['income1'] AND $T4 <= $surtax_data['income2'] ) {
				$V1 = bcmul( $surtax_data['rate1'], bcsub( $T4, $surtax_data['income1'] ) );
			} elseif ( $T4 > $surtax_data['income2'] ) {
				$V1 = bcadd( bcmul($surtax_data['rate1'], bcsub( $T4, $surtax_data['income1'] ) ), bcmul( $surtax_data['rate2'], bcsub( $T4, $surtax_data['income2'] ) ) );
			}
		}
		Debug::text('V1: '. $V1, __FILE__, __LINE__, __METHOD__, 10);

		return $V1;
	}

	function getAdditionalProvincialSurtax() {
		/*
			V2 =

			Where A < 20,000
			V2 = 0

			Where A >

		*/

		$A = $this->getAnnualTaxableIncome();
		$V2 = 0;

		if ( $this->getDate() >= strtotime('01-Jan-2006') ) {
			if ( $A < 20000 ) {
				$V2 = 0;
			} elseif ( $A > 20000 AND $A <= 36000 ) {
				$tmp_V2 = bcmul(0.06, bcsub($A, 20000) );

				if ( $tmp_V2 > 300 ) {
					$V2 = 300;
				} else {
					$V2 = $tmp_V2;
				}
			} elseif ( $A > 36000 AND $A <= 48000 ) {
				$tmp_V2 = bcadd(300, bcmul( 0.06, bcsub( $A, 36000) ) );

				if ( $tmp_V2 > 450 ) {
					$V2 = 450;
				} else {
					$V2 = $tmp_V2;
				}
			} elseif ( $A > 48000 AND $A <= 72000 ) {
				$tmp_V2 = bcadd(450, bcmul( 0.25, bcsub( $A, 48000) ) );

				if ( $tmp_V2 > 600 ) {
					$V2 = 600;
				} else {
					$V2 = $tmp_V2;
				}
			} elseif ( $A > 72000 AND $A <= 200000 ) {
				$tmp_V2 = bcadd(600, bcmul( 0.25, bcsub( $A, 72000) ) );

				if ( $tmp_V2 > 750 ) {
					$V2 = 750;
				} else {
					$V2 = $tmp_V2;
				}
			} elseif ( $A > 200000 ) {
				$tmp_V2 = bcadd(750, bcmul( 0.25, bcsub( $A, 200000) ) );

				if ( $tmp_V2 > 900 ) {
					$V2 = 900;
				} else {
					$V2 = $tmp_V2;
				}
			}
		}

		Debug::text('V2: '. $V2, __FILE__, __LINE__, __METHOD__, 10);

		return $V2;
	}
}
?>
