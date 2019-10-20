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
class PayrollDeduction_CA_NB extends PayrollDeduction_CA {
	var $provincial_income_tax_rate_options = array(
			20190101 => array(
					array('income' => 42592, 'rate' => 9.68, 'constant' => 0),
					array('income' => 85184, 'rate' => 14.82, 'constant' => 2189),
					array('income' => 138491, 'rate' => 16.52, 'constant' => 3637),
					array('income' => 157778, 'rate' => 17.84, 'constant' => 5465),
					array('income' => 157778, 'rate' => 20.30, 'constant' => 9347),
			),
			20180101 => array(
					array('income' => 41675, 'rate' => 9.68, 'constant' => 0),
					array('income' => 83351, 'rate' => 14.82, 'constant' => 2142),
					array('income' => 135510, 'rate' => 16.52, 'constant' => 3559),
					array('income' => 154382, 'rate' => 17.84, 'constant' => 5348),
					array('income' => 154382, 'rate' => 20.30, 'constant' => 9146),
			),
			20170101 => array(
					array('income' => 41059, 'rate' => 9.68, 'constant' => 0),
					array('income' => 82119, 'rate' => 14.82, 'constant' => 2110),
					array('income' => 133507, 'rate' => 16.52, 'constant' => 3506),
					array('income' => 152100, 'rate' => 17.84, 'constant' => 5269),
					array('income' => 152100, 'rate' => 20.30, 'constant' => 9010),
			),
			20160701 => array(
					array('income' => 40492, 'rate' => 9.68, 'constant' => 0),
					array('income' => 80985, 'rate' => 14.82, 'constant' => 2081),
					array('income' => 131664, 'rate' => 16.52, 'constant' => 3458),
					array('income' => 150000, 'rate' => 17.84, 'constant' => 5196),
					array('income' => 250000, 'rate' => 19.60, 'constant' => 7836),
					array('income' => 250000, 'rate' => 14.85, 'constant' => -4039), //Rate change was prorated for the year, so this will be changing in 2017.
			),
			20160101 => array(
					array('income' => 40492, 'rate' => 9.68, 'constant' => 0),
					array('income' => 80985, 'rate' => 14.82, 'constant' => 2081),
					array('income' => 131664, 'rate' => 16.52, 'constant' => 3458),
					array('income' => 150000, 'rate' => 17.84, 'constant' => 5196),
					array('income' => 250000, 'rate' => 21.00, 'constant' => 9936),
					array('income' => 250000, 'rate' => 25.75, 'constant' => 21811),
			),
			20150701 => array(
					array('income' => 39973, 'rate' => 9.68, 'constant' => 0),
					array('income' => 79946, 'rate' => 14.82, 'constant' => 2055),
					array('income' => 129975, 'rate' => 16.52, 'constant' => 3414),
					array('income' => 150000, 'rate' => 17.84, 'constant' => 5129),
					array('income' => 250000, 'rate' => 24.16, 'constant' => 14609),
					array('income' => 250000, 'rate' => 33.66, 'constant' => 38359),
			),
			20150101 => array(
					array('income' => 39973, 'rate' => 9.68, 'constant' => 0),
					array('income' => 79946, 'rate' => 14.82, 'constant' => 2055),
					array('income' => 129975, 'rate' => 16.52, 'constant' => 3414),
					array('income' => 129975, 'rate' => 17.84, 'constant' => 5129),
			),
			20140101 => array(
					array('income' => 39305, 'rate' => 9.68, 'constant' => 0),
					array('income' => 78609, 'rate' => 14.82, 'constant' => 2020),
					array('income' => 127802, 'rate' => 16.52, 'constant' => 3357),
					array('income' => 127802, 'rate' => 17.84, 'constant' => 5044),
			),
			20130701 => array(
					array('income' => 38954, 'rate' => 9.68, 'constant' => 0),
					array('income' => 77908, 'rate' => 14.82, 'constant' => 2002),
					array('income' => 126662, 'rate' => 16.52, 'constant' => 3327),
					array('income' => 126662, 'rate' => 17.84, 'constant' => 4999),
			),
			20130101 => array(
					array('income' => 38954, 'rate' => 9.1, 'constant' => 0),
					array('income' => 77908, 'rate' => 12.10, 'constant' => 1169),
					array('income' => 126662, 'rate' => 12.40, 'constant' => 1402),
					array('income' => 126662, 'rate' => 14.30, 'constant' => 3809),
			),
			20120101 => array(
					array('income' => 38190, 'rate' => 9.1, 'constant' => 0),
					array('income' => 76380, 'rate' => 12.10, 'constant' => 1146),
					array('income' => 124178, 'rate' => 12.40, 'constant' => 1375),
					array('income' => 124178, 'rate' => 14.30, 'constant' => 3734),
			),
			20110701 => array(
					array('income' => 37150, 'rate' => 9.1, 'constant' => 0),
					array('income' => 74300, 'rate' => 12.10, 'constant' => 1115),
					array('income' => 120796, 'rate' => 12.40, 'constant' => 1337),
					array('income' => 120796, 'rate' => 15.90, 'constant' => 1700),
			),
			20110101 => array(
					array('income' => 37150, 'rate' => 9.1, 'constant' => 0),
					array('income' => 74300, 'rate' => 12.10, 'constant' => 1115),
					array('income' => 120796, 'rate' => 12.40, 'constant' => 1337),
					array('income' => 120796, 'rate' => 12.70, 'constant' => 1700),
			),
			20100101 => array(
					array('income' => 36421, 'rate' => 9.3, 'constant' => 0),
					array('income' => 72843, 'rate' => 12.50, 'constant' => 1165),
					array('income' => 118427, 'rate' => 13.30, 'constant' => 1748),
					array('income' => 118427, 'rate' => 14.30, 'constant' => 2932),
			),
			20090701 => array(
					array('income' => 35707, 'rate' => 9.18, 'constant' => 0),
					array('income' => 71415, 'rate' => 13.53, 'constant' => 1550),
					array('income' => 116105, 'rate' => 15.20, 'constant' => 2749),
					array('income' => 116105, 'rate' => 16.05, 'constant' => 3736),
			),
			20090101 => array(
					array('income' => 35707, 'rate' => 10.12, 'constant' => 0),
					array('income' => 71415, 'rate' => 15.48, 'constant' => 1914),
					array('income' => 116105, 'rate' => 16.8, 'constant' => 2857),
					array('income' => 116105, 'rate' => 17.95, 'constant' => 4192),
			),
			20080101 => array(
					array('income' => 34836, 'rate' => 10.12, 'constant' => 0),
					array('income' => 69673, 'rate' => 15.48, 'constant' => 1867),
					array('income' => 113273, 'rate' => 16.80, 'constant' => 2787),
					array('income' => 113273, 'rate' => 17.95, 'constant' => 4090),
			),
			20070701 => array(
					array('income' => 34186, 'rate' => 10.56, 'constant' => 0),
					array('income' => 68374, 'rate' => 16.14, 'constant' => 1908),
					array('income' => 111161, 'rate' => 17.08, 'constant' => 2550),
					array('income' => 111161, 'rate' => 18.06, 'constant' => 3640),
			),
			20070101 => array(
					array('income' => 34186, 'rate' => 9.68, 'constant' => 0),
					array('income' => 68374, 'rate' => 14.82, 'constant' => 1757),
					array('income' => 111161, 'rate' => 16.52, 'constant' => 2920),
					array('income' => 111161, 'rate' => 17.84, 'constant' => 4387),
			),
	);
}

?>
