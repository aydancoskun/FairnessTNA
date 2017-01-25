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
class PayrollDeduction_US_GA extends PayrollDeduction_US
{
    /*
    protected $state_ga_filing_status_options = array(
                                                        10 => 'Single',
                                                        20 => 'Married - Filing Separately',
                                                        30 => 'Married - Joint One Income',
                                                        40 => 'Married - Joint Two Incomes',
                                                        50 => 'Head of Household',
                                    );

*/

    public $state_income_tax_rate_options = array(
        20060101 => array(
            10 => array(
                array('income' => 750, 'rate' => 1.0, 'constant' => 0),
                array('income' => 2250, 'rate' => 2.0, 'constant' => 7.50),
                array('income' => 3750, 'rate' => 3.0, 'constant' => 37.50),
                array('income' => 5250, 'rate' => 4.0, 'constant' => 82.50),
                array('income' => 7000, 'rate' => 5.0, 'constant' => 142.50),
                array('income' => 7000, 'rate' => 6.0, 'constant' => 230),
            ),
            20 => array(
                array('income' => 500, 'rate' => 1.0, 'constant' => 0),
                array('income' => 1500, 'rate' => 2.0, 'constant' => 5),
                array('income' => 2500, 'rate' => 3.0, 'constant' => 25),
                array('income' => 3500, 'rate' => 4.0, 'constant' => 55),
                array('income' => 5000, 'rate' => 5.0, 'constant' => 95),
                array('income' => 5000, 'rate' => 6.0, 'constant' => 170),
            ),
            30 => array(
                array('income' => 1000, 'rate' => 1.0, 'constant' => 0),
                array('income' => 3000, 'rate' => 2.0, 'constant' => 10),
                array('income' => 5000, 'rate' => 3.0, 'constant' => 50),
                array('income' => 7000, 'rate' => 4.0, 'constant' => 110),
                array('income' => 10000, 'rate' => 5.0, 'constant' => 190),
                array('income' => 10000, 'rate' => 6.0, 'constant' => 340),
            ),
            40 => array(
                array('income' => 500, 'rate' => 1.0, 'constant' => 0),
                array('income' => 1500, 'rate' => 2.0, 'constant' => 5),
                array('income' => 2500, 'rate' => 3.0, 'constant' => 25),
                array('income' => 3500, 'rate' => 4.0, 'constant' => 55),
                array('income' => 5000, 'rate' => 5.0, 'constant' => 95),
                array('income' => 5000, 'rate' => 6.0, 'constant' => 170),
            ),
            50 => array(
                array('income' => 1000, 'rate' => 1.0, 'constant' => 0),
                array('income' => 3000, 'rate' => 2.0, 'constant' => 10),
                array('income' => 5000, 'rate' => 3.0, 'constant' => 50),
                array('income' => 7000, 'rate' => 4.0, 'constant' => 110),
                array('income' => 10000, 'rate' => 5.0, 'constant' => 190),
                array('income' => 10000, 'rate' => 6.0, 'constant' => 340),
            ),
        ),
    );

    public $state_options = array(
        20060101 => array(
            'standard_deduction' => array(
                '10' => 2300.00,
                '20' => 1500.00,
                '30' => 3000.00,
                '40' => 1500.00,
                '50' => 2300.00,
            ),
            'employee_allowance' => 2700,
            'dependant_allowance' => 3000
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
        $state_employee_allowance = $this->getStateEmployeeAllowanceAmount();
        $state_dependant_allowance = $this->getStateDependantAllowanceAmount();

        $income = bcsub(bcsub(bcsub($annual_income, $state_deductions), $state_employee_allowance), $state_dependant_allowance);

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

    public function getStateEmployeeAllowanceAmount()
    {
        $retarr = $this->getDataFromRateArray($this->getDate(), $this->state_options);
        if ($retarr == false) {
            return false;
        }

        $allowance_arr = $retarr['employee_allowance'];

        $retval = bcmul($this->getUserValue2(), $allowance_arr);

        Debug::text('State Employee Allowance Amount: ' . $retval, __FILE__, __LINE__, __METHOD__, 10);

        return $retval;
    }

    public function getStateDependantAllowanceAmount()
    {
        $retarr = $this->getDataFromRateArray($this->getDate(), $this->state_options);
        if ($retarr == false) {
            return false;
        }

        $allowance_arr = $retarr['dependant_allowance'];

        $retval = bcmul($this->getUserValue3(), $allowance_arr);

        Debug::text('State Dependant Allowance Amount: ' . $retval, __FILE__, __LINE__, __METHOD__, 10);

        return $retval;
    }
}
