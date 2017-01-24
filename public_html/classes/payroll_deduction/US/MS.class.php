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
class PayrollDeduction_US_MS extends PayrollDeduction_US
{
    /*
    protected $state_filing_status_options = array(
                                                        10 => 'Single',
                                                        20 => 'Married - Spouse Works',
                                                        30 => 'Married - Spouse does not Work',
                                                        40 => 'Head of Household',
                                    );
*/

    public $state_income_tax_rate_options = array(
        20060101 => array(
            0 => array(
                array('income' => 5000, 'rate' => 3.0, 'constant' => 0),
                array('income' => 10000, 'rate' => 4.0, 'constant' => 150),
                array('income' => 10000, 'rate' => 5.0, 'constant' => 350),
            ),
        ),
    );

    public $state_options = array(
        20060101 => array(
            'standard_deduction' => array(
                '10' => 2300.00,
                '20' => 2300.00,
                '30' => 4600.00,
                '40' => 3400.00,
            ),
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

        if ($retval < 0) {
            $retval = 0;
        }

        Debug::text('State Annual Tax Payable: ' . $retval, __FILE__, __LINE__, __METHOD__, 10);

        return $retval;
    }

    public function getStateAnnualTaxableIncome()
    {
        $annual_income = $this->getAnnualTaxableIncome();

        $state_deductions = $this->getStateStandardDeduction();
        $state_allowance = $this->getStateAllowance();

        $income = bcsub(bcsub($annual_income, $state_deductions), $state_allowance);

        Debug::text('State Annual Taxable Income: ' . $income, __FILE__, __LINE__, __METHOD__, 10);

        return $income;
    }

    public function getStateStandardDeduction()
    {
        $retarr = $this->getDataFromRateArray($this->getDate(), $this->state_options);
        if ($retarr == false) {
            return false;
        }

        $deduction = $retarr['standard_deduction'][$this->getStateFilingStatus()];

        Debug::text('Standard Deduction: ' . $deduction, __FILE__, __LINE__, __METHOD__, 10);

        return $deduction;
    }
}
