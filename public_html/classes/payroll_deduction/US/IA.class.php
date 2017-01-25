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
class PayrollDeduction_US_IA extends PayrollDeduction_US
{
    public $state_income_tax_rate_options = array(
        20060401 => array(
            0 => array(
                array('income' => 1300, 'rate' => 0.36, 'constant' => 0),
                array('income' => 2600, 'rate' => 0.72, 'constant' => 4.68),
                array('income' => 5200, 'rate' => 2.43, 'constant' => 14.04),
                array('income' => 11700, 'rate' => 4.50, 'constant' => 77.22),
                array('income' => 19500, 'rate' => 6.12, 'constant' => 369.72),
                array('income' => 26000, 'rate' => 6.48, 'constant' => 847.08),
                array('income' => 39000, 'rate' => 6.80, 'constant' => 1268.28),
                array('income' => 58500, 'rate' => 7.92, 'constant' => 2152.28),
                array('income' => 58500, 'rate' => 8.98, 'constant' => 3696.68),
            ),
        ),
    );

    public $state_options = array(
        20060401 => array( //01-Apr-06
            'standard_deduction' => array(1650.00, 4060.00),
            'allowance' => 40
        ),
        20060101 => array(
            'standard_deduction' => array(1500.00, 2600.00),
            'allowance' => 40
        ),
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
        $federal_tax = $this->getFederalTaxPayable();

        $state_deductions = $this->getStateStandardDeduction();

        $income = bcsub(bcsub($annual_income, $federal_tax), $state_deductions);

        Debug::text('State Annual Taxable Income: ' . $income, __FILE__, __LINE__, __METHOD__, 10);

        return $income;
    }

    public function getStateStandardDeduction()
    {
        $retarr = $this->getDataFromRateArray($this->getDate(), $this->state_options);
        if ($retarr == false) {
            return false;
        }

        $deduction = $retarr['standard_deduction'];

        if ($this->getStateAllowance() <= 1) {
            $retval = $deduction[0];
        } else {
            $retval = $deduction[1];
        }

        Debug::text('Standard Deduction: ' . $retval, __FILE__, __LINE__, __METHOD__, 10);

        return $retval;
    }

    public function getStateAllowanceAmount()
    {
        $retarr = $this->getDataFromRateArray($this->getDate(), $this->state_options);
        if ($retarr == false) {
            return false;
        }

        $allowance = $retarr['allowance'];

        $retval = bcmul($allowance, $this->getStateAllowance());

        Debug::text('State Allowance Amount: ' . $retval, __FILE__, __LINE__, __METHOD__, 10);

        return $retval;
    }
}
