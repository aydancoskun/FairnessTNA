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
 * @package PayrollDeduction\US
 */
class PayrollDeduction_US_DC extends PayrollDeduction_US
{
    /*
                                                        10 => TTi18n::gettext('Single'),
                                                        20 => TTi18n::gettext('Married (Filing Jointly)'),
                                                        30 => TTi18n::gettext('Married (Filing Separately)'),
                                                        40 => TTi18n::gettext('Head of Household'),
*/

    public $state_income_tax_rate_options = array(
        20160101 => array(
            10 => array(
                array('income' => 10000, 'rate' => 4.0, 'constant' => 0),
                array('income' => 40000, 'rate' => 6.0, 'constant' => 400),
                array('income' => 60000, 'rate' => 6.5, 'constant' => 2200),
                array('income' => 350000, 'rate' => 8.5, 'constant' => 3500),
                array('income' => 1000000, 'rate' => 8.75, 'constant' => 28150),
                array('income' => 1000000, 'rate' => 8.95, 'constant' => 85025),
            ),
            20 => array(
                array('income' => 10000, 'rate' => 4.0, 'constant' => 0),
                array('income' => 40000, 'rate' => 6.0, 'constant' => 400),
                array('income' => 60000, 'rate' => 6.5, 'constant' => 2200),
                array('income' => 350000, 'rate' => 8.5, 'constant' => 3500),
                array('income' => 1000000, 'rate' => 8.75, 'constant' => 28150),
                array('income' => 1000000, 'rate' => 8.95, 'constant' => 85025),
            ),
            30 => array(
                array('income' => 10000, 'rate' => 4.0, 'constant' => 0),
                array('income' => 40000, 'rate' => 6.0, 'constant' => 400),
                array('income' => 60000, 'rate' => 6.5, 'constant' => 2200),
                array('income' => 350000, 'rate' => 8.5, 'constant' => 3500),
                array('income' => 1000000, 'rate' => 8.75, 'constant' => 28150),
                array('income' => 1000000, 'rate' => 8.95, 'constant' => 85025),
            ),
            40 => array(
                array('income' => 10000, 'rate' => 4.0, 'constant' => 0),
                array('income' => 40000, 'rate' => 6.0, 'constant' => 400),
                array('income' => 60000, 'rate' => 6.5, 'constant' => 2200),
                array('income' => 350000, 'rate' => 8.5, 'constant' => 3500),
                array('income' => 1000000, 'rate' => 8.75, 'constant' => 28150),
                array('income' => 1000000, 'rate' => 8.95, 'constant' => 85025),
            ),
        ),
        20150101 => array(
            10 => array(
                array('income' => 10000, 'rate' => 4.0, 'constant' => 0),
                array('income' => 40000, 'rate' => 6.0, 'constant' => 400),
                array('income' => 60000, 'rate' => 7.0, 'constant' => 2200),
                array('income' => 350000, 'rate' => 8.5, 'constant' => 3600),
                array('income' => 350000, 'rate' => 8.95, 'constant' => 28250),
            ),
            20 => array(
                array('income' => 10000, 'rate' => 4.0, 'constant' => 0),
                array('income' => 40000, 'rate' => 6.0, 'constant' => 400),
                array('income' => 60000, 'rate' => 7.0, 'constant' => 2200),
                array('income' => 350000, 'rate' => 8.5, 'constant' => 3600),
                array('income' => 350000, 'rate' => 8.95, 'constant' => 28250),
            ),
            30 => array(
                array('income' => 10000, 'rate' => 4.0, 'constant' => 0),
                array('income' => 40000, 'rate' => 6.0, 'constant' => 400),
                array('income' => 60000, 'rate' => 7.0, 'constant' => 2200),
                array('income' => 350000, 'rate' => 8.5, 'constant' => 3600),
                array('income' => 350000, 'rate' => 8.95, 'constant' => 28250),
            ),
            40 => array(
                array('income' => 10000, 'rate' => 4.0, 'constant' => 0),
                array('income' => 40000, 'rate' => 6.0, 'constant' => 400),
                array('income' => 60000, 'rate' => 7.0, 'constant' => 2200),
                array('income' => 350000, 'rate' => 8.5, 'constant' => 3600),
                array('income' => 350000, 'rate' => 8.95, 'constant' => 28250),
            ),
        ),
        20120101 => array(
            10 => array(
                array('income' => 10000, 'rate' => 4.0, 'constant' => 0),
                array('income' => 40000, 'rate' => 6.0, 'constant' => 400),
                array('income' => 350000, 'rate' => 8.5, 'constant' => 2200),
                array('income' => 350000, 'rate' => 8.95, 'constant' => 28550),
            ),
            20 => array(
                array('income' => 10000, 'rate' => 4.0, 'constant' => 0),
                array('income' => 40000, 'rate' => 6.0, 'constant' => 400),
                array('income' => 350000, 'rate' => 8.5, 'constant' => 2200),
                array('income' => 350000, 'rate' => 8.95, 'constant' => 28550),
            ),
            30 => array(
                array('income' => 10000, 'rate' => 4.0, 'constant' => 0),
                array('income' => 40000, 'rate' => 6.0, 'constant' => 400),
                array('income' => 350000, 'rate' => 8.5, 'constant' => 2200),
                array('income' => 350000, 'rate' => 8.95, 'constant' => 28550),
            ),
            40 => array(
                array('income' => 10000, 'rate' => 4.0, 'constant' => 0),
                array('income' => 40000, 'rate' => 6.0, 'constant' => 400),
                array('income' => 350000, 'rate' => 8.5, 'constant' => 2200),
                array('income' => 350000, 'rate' => 8.95, 'constant' => 28550),
            ),
        ),
        20100101 => array(
            10 => array(
                array('income' => 4000, 'rate' => 0, 'constant' => 0),
                array('income' => 10000, 'rate' => 4.0, 'constant' => 0),
                array('income' => 40000, 'rate' => 6.0, 'constant' => 240),
                array('income' => 40000, 'rate' => 8.5, 'constant' => 2040),
            ),
            20 => array(
                array('income' => 4000, 'rate' => 0, 'constant' => 0),
                array('income' => 10000, 'rate' => 4.0, 'constant' => 0),
                array('income' => 40000, 'rate' => 6.0, 'constant' => 240),
                array('income' => 40000, 'rate' => 8.5, 'constant' => 2040),
            ),
            30 => array(
                array('income' => 2000, 'rate' => 0, 'constant' => 0),
                array('income' => 10000, 'rate' => 4.0, 'constant' => 0),
                array('income' => 40000, 'rate' => 6.0, 'constant' => 320),
                array('income' => 40000, 'rate' => 8.5, 'constant' => 2120),
            ),
            40 => array(
                array('income' => 4000, 'rate' => 0, 'constant' => 0),
                array('income' => 10000, 'rate' => 4.0, 'constant' => 0),
                array('income' => 40000, 'rate' => 6.0, 'constant' => 240),
                array('income' => 40000, 'rate' => 8.5, 'constant' => 2040),
            ),
        ),
        20090101 => array(
            10 => array(
                array('income' => 4200, 'rate' => 0, 'constant' => 0),
                array('income' => 10000, 'rate' => 4.0, 'constant' => 0),
                array('income' => 40000, 'rate' => 6.0, 'constant' => 232),
                array('income' => 40000, 'rate' => 8.5, 'constant' => 2032),
            ),
            20 => array(
                array('income' => 4200, 'rate' => 0, 'constant' => 0),
                array('income' => 10000, 'rate' => 4.0, 'constant' => 0),
                array('income' => 40000, 'rate' => 6.0, 'constant' => 232),
                array('income' => 40000, 'rate' => 8.5, 'constant' => 2032),
            ),
            30 => array(
                array('income' => 2100, 'rate' => 0, 'constant' => 0),
                array('income' => 10000, 'rate' => 4.0, 'constant' => 0),
                array('income' => 40000, 'rate' => 6.0, 'constant' => 316),
                array('income' => 40000, 'rate' => 8.5, 'constant' => 2116),
            ),
            40 => array(
                array('income' => 4200, 'rate' => 0, 'constant' => 0),
                array('income' => 10000, 'rate' => 4.0, 'constant' => 0),
                array('income' => 40000, 'rate' => 6.0, 'constant' => 232),
                array('income' => 40000, 'rate' => 8.5, 'constant' => 2032),
            ),
        ),
        20060101 => array(
            10 => array(
                array('income' => 2500, 'rate' => 0, 'constant' => 0),
                array('income' => 10000, 'rate' => 4.5, 'constant' => 0),
                array('income' => 40000, 'rate' => 7.0, 'constant' => 337.50),
                array('income' => 40000, 'rate' => 8.7, 'constant' => 2437.50),
            ),
            20 => array(
                array('income' => 2500, 'rate' => 0, 'constant' => 0),
                array('income' => 10000, 'rate' => 4.5, 'constant' => 0),
                array('income' => 40000, 'rate' => 7.0, 'constant' => 337.50),
                array('income' => 40000, 'rate' => 8.7, 'constant' => 2437.50),
            ),
            30 => array(
                array('income' => 1250, 'rate' => 0, 'constant' => 0),
                array('income' => 10000, 'rate' => 4.5, 'constant' => 0),
                array('income' => 40000, 'rate' => 7.0, 'constant' => 393.75),
                array('income' => 40000, 'rate' => 8.7, 'constant' => 2493.75),
            ),
            40 => array(
                array('income' => 2500, 'rate' => 0, 'constant' => 0),
                array('income' => 10000, 'rate' => 4.5, 'constant' => 0),
                array('income' => 40000, 'rate' => 7.0, 'constant' => 337.50),
                array('income' => 40000, 'rate' => 8.7, 'constant' => 2437.50),
            ),
        ),
    );

    public $state_options = array(
        20150101 => array( //01-Jan-2015
            'allowance' => 1775
        ),
        //01-Jan-2014 - No Changes.
        //01-Jan-2013 - No Changes.
        //01-Jan-2012 - No Changes.
        //01-Jan-2011 - No Changes.
        20100101 => array( //01-Jan-2010
            'allowance' => 1675
        ),
        20090101 => array( //01-Jan-09
            'allowance' => 1750
        ),
        20060101 => array(
            'allowance' => 1500
        )
    );

    public function getStateTaxPayable()
    {
        $annual_income = $this->getStateAnnualTaxableIncome();

        $retval = 0;

        if ($annual_income > 0) {
            $rate = $this->getData()->getStateRate($annual_income);
            $state_constant = $this->getData()->getStateConstant($annual_income);
            $state_rate_income = $this->getData()->getStateRatePreviousIncome($annual_income);

            $retval = bcadd(bcmul(bcsub($annual_income, $state_rate_income), $rate), $state_constant);
            //$retval = bcadd( bcmul( $annual_income, $rate ), $state_constant );
        }

        if ($retval < 0) {
            $retval = 0;
        }

        Debug::text('State Annual Tax Payable: ' . $retval, __FILE__, __LINE__, __METHOD__, 10);

        return $retval;
    }

    public function getStateAnnualTaxableIncome()
    {
        $annual_income = $this->getAnnualTaxableIncome();
        $state_allowance = $this->getStateAllowanceAmount();

        $income = bcsub($annual_income, $state_allowance);

        Debug::text('State Annual Taxable Income: ' . $income, __FILE__, __LINE__, __METHOD__, 10);

        return $income;
    }

    public function getStateAllowanceAmount()
    {
        $retarr = $this->getDataFromRateArray($this->getDate(), $this->state_options);
        if ($retarr == false) {
            return false;
        }

        $allowance_arr = $retarr['allowance'];

        $retval = bcmul($this->getStateAllowance(), $allowance_arr);

        Debug::text('State Allowance Amount: ' . $retval, __FILE__, __LINE__, __METHOD__, 10);

        return $retval;
    }
}
