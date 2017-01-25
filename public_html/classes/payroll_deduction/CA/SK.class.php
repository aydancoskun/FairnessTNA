<?php
/**********************************************************************************
 * This file is part of "FairnessTNA", a Payroll and Time Management program.
 * FairnessTNA is copyright 2013-2017 Aydan Coskun (aydan.ayfer.coskun@gmail.com)
 * others. For full attribution and copyrights details see the COPYRIGHT file.
 *
 * FairnessTNA is free software; you can redistribute it and/or modify it under the
 * terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation, either version 3 of the License, or (at you option )
 * any later version.
 *
 * FairnessTNA is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE.  See the GNU Affero General Public License for more
 * details.
 *
 * You should have received a copy of the GNU Affero General Public License along
 * with this program; if not, see http://www.gnu.org/licenses or write to the Free
 * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301 USA.
 *********************************************************************************/


/**
 * @package PayrollDeduction\CA
 */
class PayrollDeduction_CA_SK extends PayrollDeduction_CA
{
    public $provincial_income_tax_rate_options = array(
        20170101 => array(
            array('income' => 45225, 'rate' => 11, 'constant' => 0),
            array('income' => 129214, 'rate' => 13, 'constant' => 905),
            array('income' => 129214, 'rate' => 15, 'constant' => 3489),
        ),
        20160101 => array(
            array('income' => 44601, 'rate' => 11, 'constant' => 0),
            array('income' => 127430, 'rate' => 13, 'constant' => 892),
            array('income' => 127430, 'rate' => 15, 'constant' => 3441),
        ),
        20150101 => array(
            array('income' => 44028, 'rate' => 11, 'constant' => 0),
            array('income' => 125795, 'rate' => 13, 'constant' => 881),
            array('income' => 125795, 'rate' => 15, 'constant' => 3396),
        ),
        20140101 => array(
            array('income' => 43292, 'rate' => 11, 'constant' => 0),
            array('income' => 123692, 'rate' => 13, 'constant' => 866),
            array('income' => 123692, 'rate' => 15, 'constant' => 3340),
        ),
        20130101 => array(
            array('income' => 42906, 'rate' => 11, 'constant' => 0),
            array('income' => 122589, 'rate' => 13, 'constant' => 858),
            array('income' => 122589, 'rate' => 15, 'constant' => 3310),
        ),
        20120101 => array(
            array('income' => 42065, 'rate' => 11, 'constant' => 0),
            array('income' => 120185, 'rate' => 13, 'constant' => 841),
            array('income' => 120185, 'rate' => 15, 'constant' => 3245),
        ),
        20110101 => array(
            array('income' => 40919, 'rate' => 11, 'constant' => 0),
            array('income' => 116911, 'rate' => 13, 'constant' => 818),
            array('income' => 116911, 'rate' => 15, 'constant' => 3157),
        ),
        20100101 => array(
            array('income' => 40354, 'rate' => 11, 'constant' => 0),
            array('income' => 115297, 'rate' => 13, 'constant' => 807),
            array('income' => 115297, 'rate' => 15, 'constant' => 3113),
        ),
        20090101 => array(
            array('income' => 40113, 'rate' => 11, 'constant' => 0),
            array('income' => 114610, 'rate' => 13, 'constant' => 802),
            array('income' => 114610, 'rate' => 15, 'constant' => 3094),
        ),
        20080101 => array(
            array('income' => 39135, 'rate' => 11, 'constant' => 0),
            array('income' => 111814, 'rate' => 13, 'constant' => 783),
            array('income' => 111814, 'rate' => 15, 'constant' => 3019),
        ),
        20070101 => array(
            array('income' => 38405, 'rate' => 11.0, 'constant' => 0),
            array('income' => 109720, 'rate' => 13.0, 'constant' => 768),
            array('income' => 109720, 'rate' => 15.0, 'constant' => 2963),
        ),
        20060101 => array(
            array('income' => 37579, 'rate' => 11, 'constant' => 0),
            array('income' => 107367, 'rate' => 13, 'constant' => 752),
            array('income' => 107367, 'rate' => 15, 'constant' => 2899),
        ),
    );
}
