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
class PayrollDeduction_CA_MB extends PayrollDeduction_CA {
	var $provincial_income_tax_rate_options = array(
			20190101 => array(
					array('income' => 32670, 'rate' => 10.8, 'constant' => 0),
					array('income' => 70610, 'rate' => 12.75, 'constant' => 637),
					array('income' => 70610, 'rate' => 17.4, 'constant' => 3920),
			),
			20180101 => array(
					array('income' => 31843, 'rate' => 10.8, 'constant' => 0),
					array('income' => 68821, 'rate' => 12.75, 'constant' => 621),
					array('income' => 68821, 'rate' => 17.4, 'constant' => 3821),
			),
			20170101 => array(
					array('income' => 31465, 'rate' => 10.8, 'constant' => 0),
					array('income' => 68005, 'rate' => 12.75, 'constant' => 614),
					array('income' => 68005, 'rate' => 17.4, 'constant' => 3776),
			),
			20090101 => array(
					array('income' => 31000, 'rate' => 10.8, 'constant' => 0),
					array('income' => 67000, 'rate' => 12.75, 'constant' => 605),
					array('income' => 67000, 'rate' => 17.4, 'constant' => 3720),
			),
			20080101 => array(
					array('income' => 30544, 'rate' => 10.90, 'constant' => 0),
					array('income' => 66000, 'rate' => 12.75, 'constant' => 565),
					array('income' => 66000, 'rate' => 17.40, 'constant' => 3634),
			),
			20070101 => array(
					array('income' => 30544, 'rate' => 10.9, 'constant' => 0),
					array('income' => 65000, 'rate' => 13.0, 'constant' => 641),
					array('income' => 65000, 'rate' => 17.4, 'constant' => 3501),
			),
			20060101 => array(
					array('income' => 30544, 'rate' => 10.9, 'constant' => 0),
					array('income' => 65000, 'rate' => 13.5, 'constant' => 794),
					array('income' => 65000, 'rate' => 17.4, 'constant' => 3329),
			),
	);
}

?>
