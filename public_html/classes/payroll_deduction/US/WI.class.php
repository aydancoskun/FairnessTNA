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


/*

        ******** USE Calculation Method "B" *********

*/

/**
 * @package PayrollDeduction\US
 */
class PayrollDeduction_US_WI extends PayrollDeduction_US
{
    public $state_income_tax_rate_options = array(
        20140401 => array(
            10 => array(
                array('income' => 5730, 'rate' => 0, 'constant' => 0),
                array('income' => 15200, 'rate' => 4.0, 'constant' => 0),
                array('income' => 16486, 'rate' => 4.48, 'constant' => 378.80),
                array('income' => 26227, 'rate' => 6.5408, 'constant' => 436.41),
                array('income' => 62950, 'rate' => 7.0224, 'constant' => 1073.55),
                array('income' => 240190, 'rate' => 6.27, 'constant' => 3652.39),
                array('income' => 240190, 'rate' => 7.65, 'constant' => 14765.34),
            ),
            20 => array(
                array('income' => 7870, 'rate' => 0, 'constant' => 0),
                array('income' => 18780, 'rate' => 4.0, 'constant' => 0),
                array('income' => 21400, 'rate' => 5.84, 'constant' => 436.40),
                array('income' => 28308, 'rate' => 7.008, 'constant' => 589.41),
                array('income' => 60750, 'rate' => 7.524, 'constant' => 1073.52),
                array('income' => 240190, 'rate' => 6.27, 'constant' => 3514.46),
                array('income' => 240190, 'rate' => 7.65, 'constant' => 14765.35),
            ),
        ),
        20100101 => array(
            10 => array(
                array('income' => 4000, 'rate' => 0, 'constant' => 0),
                array('income' => 10620, 'rate' => 4.6, 'constant' => 0),
                array('income' => 13602, 'rate' => 5.152, 'constant' => 304.52),
                array('income' => 22486, 'rate' => 6.888, 'constant' => 458.15),
                array('income' => 43953, 'rate' => 7.28, 'constant' => 1070.08),
                array('income' => 149330, 'rate' => 6.5, 'constant' => 2632.88),
                array('income' => 219200, 'rate' => 6.75, 'constant' => 9482.39),
                array('income' => 219200, 'rate' => 7.75, 'constant' => 14198.62),
            ),
            20 => array(
                array('income' => 5500, 'rate' => 0, 'constant' => 0),
                array('income' => 14950, 'rate' => 4.6, 'constant' => 0),
                array('income' => 15375, 'rate' => 5.52, 'constant' => 434.70),
                array('income' => 23667, 'rate' => 7.38, 'constant' => 458.16),
                array('income' => 42450, 'rate' => 7.8, 'constant' => 1070.11),
                array('income' => 149330, 'rate' => 6.5, 'constant' => 2535.18),
                array('income' => 219200, 'rate' => 6.75, 'constant' => 9482.38),
                array('income' => 219200, 'rate' => 7.75, 'constant' => 14198.61),
            ),
        ),
        20060101 => array(
            10 => array(
                array('income' => 4000, 'rate' => 0, 'constant' => 0),
                array('income' => 10620, 'rate' => 4.6, 'constant' => 0),
                array('income' => 11825, 'rate' => 5.154, 'constant' => 305),
                array('income' => 18629, 'rate' => 6.888, 'constant' => 367),
                array('income' => 43953, 'rate' => 7.280, 'constant' => 836),
                array('income' => 115140, 'rate' => 6.5, 'constant' => 2680),
                array('income' => 115140, 'rate' => 6.75, 'constant' => 7307),
            ),
            20 => array(
                array('income' => 5500, 'rate' => 0, 'constant' => 0),
                array('income' => 13470, 'rate' => 4.6, 'constant' => 0),
                array('income' => 14950, 'rate' => 6.15, 'constant' => 367),
                array('income' => 20067, 'rate' => 7.38, 'constant' => 458),
                array('income' => 42450, 'rate' => 7.8, 'constant' => 836),
                array('income' => 115140, 'rate' => 6.5, 'constant' => 2582),
                array('income' => 115140, 'rate' => 6.75, 'constant' => 7307),
            ),
        ),
    );

    public $state_options = array(
        //01-Jan-10: No Change.
        20060101 => array(
            'allowance' => 22,
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
        }

        $retval = $retval - $this->getStateAllowanceAmount();

        if ($retval < 0) {
            $retval = 0;
        }
        Debug::text('State Annual Tax Payable: ' . $retval, __FILE__, __LINE__, __METHOD__, 10);

        return $retval;
    }

    public function getStateAnnualTaxableIncome()
    {
        $annual_income = $this->getAnnualTaxableIncome();

        $income = $annual_income;

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
