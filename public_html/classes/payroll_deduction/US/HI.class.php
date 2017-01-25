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
class PayrollDeduction_US_HI extends PayrollDeduction_US
{
    public $state_income_tax_rate_options = array(
        20090101 => array(
            10 => array(
                array('income' => 2400, 'rate' => 1.4, 'constant' => 0),
                array('income' => 4800, 'rate' => 3.2, 'constant' => 34),
                array('income' => 9600, 'rate' => 5.5, 'constant' => 110),
                array('income' => 14400, 'rate' => 6.4, 'constant' => 374),
                array('income' => 19200, 'rate' => 6.8, 'constant' => 682),
                array('income' => 24000, 'rate' => 7.2, 'constant' => 1008),
                array('income' => 36000, 'rate' => 7.6, 'constant' => 1354),
                array('income' => 36000, 'rate' => 7.9, 'constant' => 2266),
            ),
            20 => array(
                array('income' => 4800, 'rate' => 1.4, 'constant' => 0),
                array('income' => 9600, 'rate' => 3.2, 'constant' => 67),
                array('income' => 19200, 'rate' => 5.5, 'constant' => 221),
                array('income' => 28800, 'rate' => 6.4, 'constant' => 749),
                array('income' => 38400, 'rate' => 6.8, 'constant' => 1363),
                array('income' => 48000, 'rate' => 7.2, 'constant' => 2016),
                array('income' => 72000, 'rate' => 7.6, 'constant' => 2707),
                array('income' => 72000, 'rate' => 7.9, 'constant' => 4531),
            ),
        ),
        20070101 => array(
            10 => array(
                array('income' => 2400, 'rate' => 1.4, 'constant' => 0),
                array('income' => 4800, 'rate' => 3.2, 'constant' => 34),
                array('income' => 9600, 'rate' => 5.5, 'constant' => 110),
                array('income' => 14400, 'rate' => 6.4, 'constant' => 374),
                array('income' => 19200, 'rate' => 6.8, 'constant' => 682),
                array('income' => 24000, 'rate' => 7.2, 'constant' => 1008),
                array('income' => 24000, 'rate' => 7.6, 'constant' => 1354),
            ),
            20 => array(
                array('income' => 4800, 'rate' => 1.4, 'constant' => 0),
                array('income' => 9600, 'rate' => 3.2, 'constant' => 67),
                array('income' => 19200, 'rate' => 5.5, 'constant' => 221),
                array('income' => 28800, 'rate' => 6.4, 'constant' => 749),
                array('income' => 38400, 'rate' => 6.8, 'constant' => 1363),
                array('income' => 48000, 'rate' => 7.2, 'constant' => 2016),
                array('income' => 48000, 'rate' => 7.6, 'constant' => 2707),
            ),
        ),
        20060101 => array(
            10 => array(
                array('income' => 2000, 'rate' => 1.4, 'constant' => 0),
                array('income' => 4000, 'rate' => 3.2, 'constant' => 28),
                array('income' => 8000, 'rate' => 5.5, 'constant' => 92),
                array('income' => 12000, 'rate' => 6.4, 'constant' => 312),
                array('income' => 16000, 'rate' => 6.8, 'constant' => 568),
                array('income' => 20000, 'rate' => 7.2, 'constant' => 840),
                array('income' => 20000, 'rate' => 7.6, 'constant' => 1128),
            ),
            20 => array(
                array('income' => 4000, 'rate' => 1.4, 'constant' => 0),
                array('income' => 8000, 'rate' => 3.2, 'constant' => 56),
                array('income' => 16000, 'rate' => 5.5, 'constant' => 184),
                array('income' => 24000, 'rate' => 6.4, 'constant' => 624),
                array('income' => 32000, 'rate' => 6.8, 'constant' => 1136),
                array('income' => 40000, 'rate' => 7.2, 'constant' => 1680),
                array('income' => 40000, 'rate' => 7.6, 'constant' => 2256),
            ),
        ),
    );

    public $state_options = array(
        20110101 => array( //01-Jan-2011
            'allowance' => 1144
        ),
        20060101 => array(
            'allowance' => 1040
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
