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
class PayrollDeduction_CA_NU extends PayrollDeduction_CA {
	var $provincial_income_tax_rate_options = array(
			20190101 => array(
					array('income' => 45414, 'rate' => 4.0, 'constant' => 0),
					array('income' => 90829, 'rate' => 7.0, 'constant' => 1362),
					array('income' => 147667, 'rate' => 9.0, 'constant' => 3179),
					array('income' => 147667, 'rate' => 11.5, 'constant' => 6871),
			),
			20180101 => array(
					array('income' => 44437, 'rate' => 4.0, 'constant' => 0),
					array('income' => 88874, 'rate' => 7.0, 'constant' => 1333),
					array('income' => 144488, 'rate' => 9.0, 'constant' => 3111),
					array('income' => 144488, 'rate' => 11.5, 'constant' => 6723),
			),
			20170101 => array(
					array('income' => 43780, 'rate' => 4.0, 'constant' => 0),
					array('income' => 87560, 'rate' => 7.0, 'constant' => 1313),
					array('income' => 142353, 'rate' => 9.0, 'constant' => 3065),
					array('income' => 142353, 'rate' => 11.5, 'constant' => 6623),
			),
			20160101 => array(
					array('income' => 43176, 'rate' => 4.0, 'constant' => 0),
					array('income' => 86351, 'rate' => 7.0, 'constant' => 1295),
					array('income' => 140388, 'rate' => 9.0, 'constant' => 3022),
					array('income' => 140388, 'rate' => 11.5, 'constant' => 6532),
			),
			20150101 => array(
					array('income' => 42622, 'rate' => 4.0, 'constant' => 0),
					array('income' => 85243, 'rate' => 7.0, 'constant' => 1279),
					array('income' => 138586, 'rate' => 9.0, 'constant' => 2984),
					array('income' => 138586, 'rate' => 11.5, 'constant' => 6448),
			),
			20140101 => array(
					array('income' => 41909, 'rate' => 4.0, 'constant' => 0),
					array('income' => 83818, 'rate' => 7.0, 'constant' => 1257),
					array('income' => 136270, 'rate' => 9.0, 'constant' => 2934),
					array('income' => 136270, 'rate' => 11.5, 'constant' => 6340),
			),
			20130101 => array(
					array('income' => 41535, 'rate' => 4.0, 'constant' => 0),
					array('income' => 83071, 'rate' => 7.0, 'constant' => 1246),
					array('income' => 135054, 'rate' => 9.0, 'constant' => 2907),
					array('income' => 135054, 'rate' => 11.5, 'constant' => 6284),
			),
			20120101 => array(
					array('income' => 40721, 'rate' => 4.0, 'constant' => 0),
					array('income' => 81442, 'rate' => 7.0, 'constant' => 1222),
					array('income' => 132406, 'rate' => 9.0, 'constant' => 2850),
					array('income' => 132406, 'rate' => 11.5, 'constant' => 6161),
			),
			20110101 => array(
					array('income' => 39612, 'rate' => 4.0, 'constant' => 0),
					array('income' => 79224, 'rate' => 7.0, 'constant' => 1188),
					array('income' => 128800, 'rate' => 9.0, 'constant' => 2773),
					array('income' => 128800, 'rate' => 11.5, 'constant' => 5993),
			),
			20100101 => array(
					array('income' => 39065, 'rate' => 4.0, 'constant' => 0),
					array('income' => 78130, 'rate' => 7.0, 'constant' => 1172),
					array('income' => 127021, 'rate' => 9.0, 'constant' => 2735),
					array('income' => 127021, 'rate' => 11.5, 'constant' => 5910),
			),
			20090101 => array(
					array('income' => 38832, 'rate' => 4.0, 'constant' => 0),
					array('income' => 77664, 'rate' => 7.0, 'constant' => 1165),
					array('income' => 126264, 'rate' => 9.0, 'constant' => 2718),
					array('income' => 126264, 'rate' => 11.5, 'constant' => 5875),
			),
			20080101 => array(
					array('income' => 37885, 'rate' => 4, 'constant' => 0),
					array('income' => 75770, 'rate' => 7, 'constant' => 1137),
					array('income' => 123184, 'rate' => 9, 'constant' => 2652),
					array('income' => 123184, 'rate' => 11.5, 'constant' => 5732),
			),
			20070101 => array(
					array('income' => 37178, 'rate' => 4.0, 'constant' => 0),
					array('income' => 74357, 'rate' => 7.0, 'constant' => 1115),
					array('income' => 120887, 'rate' => 9.0, 'constant' => 2602),
					array('income' => 120887, 'rate' => 11.5, 'constant' => 5625),
			),
	);
}

?>
