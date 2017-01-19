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
class PayrollDeduction_CA_AB extends PayrollDeduction_CA {
	var $provincial_income_tax_rate_options = array(
													20170101 => array(
																    array( 'income' => 126625,	'rate' => 10,	'constant' => 0 ),
																    array( 'income' => 151950,	'rate' => 12,	'constant' => 2533 ),
																    array( 'income' => 202600,	'rate' => 13,	'constant' => 4052 ),
																    array( 'income' => 303900,	'rate' => 14,	'constant' => 6078 ),
																    array( 'income' => 303900,	'rate' => 15,	'constant' => 9117 ),
																),
													20151001 => array( //01-Oct-2015 (Option 1)
																	array( 'income' => 125000,	'rate' => 10,	'constant' => 0 ),
																	array( 'income' => 150000,	'rate' => 12,	'constant' => 2500 ),
																	array( 'income' => 200000,	'rate' => 13,	'constant' => 4000 ),
																	array( 'income' => 300000,	'rate' => 14,	'constant' => 6000 ),
																	array( 'income' => 300000,	'rate' => 15,	'constant' => 9000 ),
																),
													20040101 => array(
																	array( 'income' => 0,	'rate' => 10,	'constant' => 0 ),
																),
													);
}
?>
