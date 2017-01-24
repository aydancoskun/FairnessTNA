<?php
/*********************************************************************************
 * This file is part of "Fairness", a Payroll and Time Management program.
 * Fairness is Copyright 2013 Aydan Coskun (aydan.ayfer.coskun@gmail.com)
 * Portions of this software are Copyright of T i m e T r e x Software Inc.
 * Fairness is a fork of "T i m e T r e x Workforce Management" Software.
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


/**
 * @package PayrollDeduction\CA
 */
class PayrollDeduction_CA_NS extends PayrollDeduction_CA
{
    public $provincial_income_tax_rate_options = array(
        20110101 => array(
            array('income' => 29590, 'rate' => 8.79, 'constant' => 0),
            array('income' => 59180, 'rate' => 14.95, 'constant' => 1823),
            array('income' => 93000, 'rate' => 16.67, 'constant' => 2841),
            array('income' => 150000, 'rate' => 17.5, 'constant' => 3613),
            array('income' => 150000, 'rate' => 21.0, 'constant' => 8863),
        ),
        20100701 => array(
            array('income' => 29590, 'rate' => 8.79, 'constant' => 0),
            array('income' => 59180, 'rate' => 14.95, 'constant' => 1823),
            array('income' => 93000, 'rate' => 16.67, 'constant' => 2841),
            array('income' => 150000, 'rate' => 17.5, 'constant' => 3613),
            array('income' => 150000, 'rate' => 24.5, 'constant' => 14113),
        ),
        20070101 => array(
            array('income' => 29590, 'rate' => 8.79, 'constant' => 0),
            array('income' => 59180, 'rate' => 14.95, 'constant' => 1823),
            array('income' => 93000, 'rate' => 16.67, 'constant' => 2841),
            array('income' => 93000, 'rate' => 17.5, 'constant' => 3613),
        ),
    );

//	function getProvincialSurtax() {
//		/*
//			V1 =
//			For NS
//				Where T4 <= 10000
//				V1 = 0
//
//				Where T4 > 10000
//				V1 = 0.10 * ( T4 - 10000 )
//		*/
//
//		$T4 = $this->getProvincialBasicTax();
//		$V1 = 0;
//
//		//This was phased at some point, but not 100% sure when.
//		if ( $this->getDate() >= 20080101 ) {
//			if ( $T4 <= 10000 ) {
//				$V1 = 0;
//			} elseif ( $T4 > 10000 ) {
//				$V1 = bcmul( 0.10, bcsub( $T4, 10000 ) );
//			}
//		}
//
//		Debug::text('V1: '. $V1, __FILE__, __LINE__, __METHOD__, 10);
//
//		return $V1;
//	}
}
