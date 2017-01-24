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
 * @package PayrollDeduction\US
 */
class PayrollDeduction_US_DE extends PayrollDeduction_US
{
    public $state_income_tax_rate_options = array(
        20140101 => array(
            0 => array(
                array('income' => 2000, 'rate' => 0, 'constant' => 0),
                array('income' => 5000, 'rate' => 2.20, 'constant' => 0),
                array('income' => 10000, 'rate' => 3.90, 'constant' => 66),
                array('income' => 20000, 'rate' => 4.80, 'constant' => 261),
                array('income' => 25000, 'rate' => 5.20, 'constant' => 741),
                array('income' => 60000, 'rate' => 5.55, 'constant' => 1001),
                array('income' => 60000, 'rate' => 6.60, 'constant' => 2943.50),
            ),
        ),
        20100101 => array(
            0 => array(
                array('income' => 2000, 'rate' => 0, 'constant' => 0),
                array('income' => 5000, 'rate' => 2.20, 'constant' => 0),
                array('income' => 10000, 'rate' => 3.90, 'constant' => 66),
                array('income' => 20000, 'rate' => 4.80, 'constant' => 261),
                array('income' => 25000, 'rate' => 5.20, 'constant' => 741),
                array('income' => 60000, 'rate' => 5.55, 'constant' => 1001),
                array('income' => 60000, 'rate' => 6.95, 'constant' => 2943.50),
            ),
        ),
        20060101 => array(
            0 => array(
                array('income' => 2000, 'rate' => 0, 'constant' => 0),
                array('income' => 5000, 'rate' => 2.20, 'constant' => 0),
                array('income' => 10000, 'rate' => 3.90, 'constant' => 66),
                array('income' => 20000, 'rate' => 4.80, 'constant' => 261),
                array('income' => 25000, 'rate' => 5.20, 'constant' => 741),
                array('income' => 60000, 'rate' => 5.55, 'constant' => 1001),
                array('income' => 60000, 'rate' => 5.95, 'constant' => 2943.50),
            ),
        ),
    );

    public $state_options = array(
        20060101 => array(
            'standard_deduction' => array(
                10 => 3250,
                20 => 6500,
                30 => 3250
            ),
            'allowance' => 110
        )
    );

    public function getStateTaxPayable()
    {
        $annual_income = $this->getStateAnnualTaxableIncome();

        $retval = 0;

        if ($annual_income > 0) {
            $rate = $this->getData()->getStateRate($annual_income);
            $prev_income = $this->getData()->getStateRatePreviousIncome($annual_income);
            $state_constant = $this->getData()->getStateConstant($annual_income);

            //$retval = bcadd( bcmul( bcsub( $annual_income, $state_rate_income ), $rate ), $state_constant );
            $retval = bcadd(bcmul(bcsub($annual_income, $prev_income), $rate), $state_constant);
        }

        $retval = bcsub($retval, $this->getStateAllowanceAmount());

        if ($retval < 0) {
            $retval = 0;
        }

        Debug::text('State Annual Tax Payable: ' . $retval, __FILE__, __LINE__, __METHOD__, 10);

        return $retval;
    }

    public function getStateAnnualTaxableIncome()
    {
        $annual_income = $this->getAnnualTaxableIncome();
        $standard_deduction = $this->getStateStandardDeduction();

        $income = bcsub($annual_income, $standard_deduction);

        Debug::text('State Annual Taxable Income: ' . $income, __FILE__, __LINE__, __METHOD__, 10);

        return $income;
    }

    public function getStateStandardDeduction()
    {
        $retarr = $this->getDataFromRateArray($this->getDate(), $this->state_options);
        if ($retarr == false) {
            return false;
        }

        if (isset($retarr['standard_deduction'][$this->getStateFilingStatus()])) {
            $deduction = $retarr['standard_deduction'][$this->getStateFilingStatus()];
        } else {
            $deduction = $retarr['standard_deduction'][10]; //Single
        }

        Debug::text('Standard Deduction: ' . $deduction, __FILE__, __LINE__, __METHOD__, 10);

        return $deduction;
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
