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
 * @package PayrollDeduction\CA
 */
class PayrollDeduction_CA_AB extends PayrollDeduction_CA {
	var $provincial_income_tax_rate_options = array(
			20190101 => array(
					array('income' => 131220, 'rate' => 10, 'constant' => 0),
					array('income' => 157464, 'rate' => 12, 'constant' => 2624),
					array('income' => 209952, 'rate' => 13, 'constant' => 4199),
					array('income' => 314928, 'rate' => 14, 'constant' => 6299),
					array('income' => 314928, 'rate' => 15, 'constant' => 9448),
			),
			20180101 => array(
					array('income' => 128145, 'rate' => 10, 'constant' => 0),
					array('income' => 153773, 'rate' => 12, 'constant' => 2563),
					array('income' => 205031, 'rate' => 13, 'constant' => 4101),
					array('income' => 307547, 'rate' => 14, 'constant' => 6151),
					array('income' => 307547, 'rate' => 15, 'constant' => 9226),
			),
			20170101 => array(
					array('income' => 126625, 'rate' => 10, 'constant' => 0),
					array('income' => 151950, 'rate' => 12, 'constant' => 2533),
					array('income' => 202600, 'rate' => 13, 'constant' => 4052),
					array('income' => 303900, 'rate' => 14, 'constant' => 6078),
					array('income' => 303900, 'rate' => 15, 'constant' => 9117),
			),
			20151001 => array( //01-Oct-2015 (Option 1)
							   array('income' => 125000, 'rate' => 10, 'constant' => 0),
							   array('income' => 150000, 'rate' => 12, 'constant' => 2500),
							   array('income' => 200000, 'rate' => 13, 'constant' => 4000),
							   array('income' => 300000, 'rate' => 14, 'constant' => 6000),
							   array('income' => 300000, 'rate' => 15, 'constant' => 9000),
			),
			20040101 => array(
					array('income' => 0, 'rate' => 10, 'constant' => 0),
			),
	);
}

?>
