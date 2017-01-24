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

 ** Formula partially based on: http://i2i.nfc.usda.gov/Publications/Tax_Formulas/State_City_County/taxla.html

 *Due to backwards compatibility user_value_3 is filing status, NOT user_value_1;

 10 = Single
 20 = Married Filing Jointly

*/

/**
 * @package PayrollDeduction\US
 */
class PayrollDeduction_US_LA extends PayrollDeduction_US
{
    public $state_income_tax_rate_options = array(
        20090701 => array(
            10 => array(
                array('income' => 12500, 'rate' => 2.1, 'constant' => 0),
                array('income' => 50000, 'rate' => 3.7, 'constant' => 262.50),
                array('income' => 50000, 'rate' => 5.05, 'constant' => 1650.00),
            ),
            20 => array(
                array('income' => 25000, 'rate' => 2.1, 'constant' => 0),
                array('income' => 100000, 'rate' => 3.75, 'constant' => 525.00),
                array('income' => 100000, 'rate' => 5.10, 'constant' => 3337.50),
            ),
        ),
    );


    public $state_options = array(
        20060101 => array(
            'allowance' => 4500,
            'dependant_allowance' => 1000,
            'allowance_rates' => array( //Personal exceptions
                10 => array(
                    0 => array(12500, 2.1, 0),
                    1 => array(12500, 3.7, 262.50),
                ),
                20 => array(
                    0 => array(25000, 2.1, 0),
                    1 => array(25000, 3.45, 525),
                )
            ),
        )
    );

    public function setStateFilingStatus($value)
    {
        return $this->setUserValue3($value);
    }

    public function setStateAllowance($value)
    {
        return $this->setUserValue1($value);
    }

    public function getStateAllowance()
    {
        return $this->getUserValue1();
    }

    public function getStateTaxPayable()
    {
        $annual_income = $this->getAnnualTaxableIncome();

        $retval = 0;

        if ($annual_income > 0) {
            $rate = $this->getData()->getStateRate($annual_income);
            $state_constant = $this->getData()->getStateConstant($annual_income);
            $prev_income = $this->getData()->getStateRatePreviousIncome($annual_income);

            Debug::text('Rate: ' . $rate . ' Constant: ' . $state_constant . ' Prev Rate Income: ' . $prev_income, __FILE__, __LINE__, __METHOD__, 10);
            $retval = bcadd(bcmul(bcsub($annual_income, $prev_income), $rate), $state_constant);
            Debug::text('Inital State Annual Tax Payable: ' . $retval, __FILE__, __LINE__, __METHOD__, 10);

            $retval = bcsub($retval, $this->getStateTaxableAllowanceAmount());
            Debug::text('Final State Annual Tax Payable: ' . $retval, __FILE__, __LINE__, __METHOD__, 10);
        }

        if ($retval < 0) {
            $retval = 0;
        }

        Debug::text('State Annual Tax Payable: ' . $retval, __FILE__, __LINE__, __METHOD__, 10);

        return $retval;
    }

    public function getStateTaxableAllowanceAmount()
    {
        $retarr = $this->getDataFromRateArray($this->getDate(), $this->state_options);
        if ($retarr == false) {
            return false;
        }

        $retval = 0;
        if ($this->getStateTotalAllowanceAmount() > 0 and isset($retarr['allowance_rates'][$this->getStateFilingStatus()])) {
            $standard_deduction_arr = $this->getDataByIncome($this->getStateTotalAllowanceAmount(), $retarr['allowance_rates'][$this->getStateFilingStatus()]);
            //Debug::Arr($standard_deduction_arr, 'State Taxable Allowance: '. $this->getStateTotalAllowanceAmount(), __FILE__, __LINE__, __METHOD__, 10);

            $retval = bcadd(bcmul($this->getStateTotalAllowanceAmount(), bcdiv($standard_deduction_arr[1], 100)), $standard_deduction_arr[2]);

            Debug::text('State Taxable Allowance Amount: ' . $retval, __FILE__, __LINE__, __METHOD__, 10);
        }

        return $retval;
    }

    public function getStateTotalAllowanceAmount()
    {
        $retval = bcadd($this->getStateAllowanceAmount(), $this->getStateDependantAllowanceAmount());

        Debug::text('State Total Allowance Amount: ' . $retval, __FILE__, __LINE__, __METHOD__, 10);
        return $retval;
    }

    public function getStateAllowanceAmount()
    {
        $retarr = $this->getDataFromRateArray($this->getDate(), $this->state_options);
        if ($retarr == false) {
            return false;
        }

        $allowance_arr = $retarr['allowance'];

        $retval = bcmul($this->getUserValue1(), $allowance_arr);

        Debug::text('State Allowance Amount: ' . $retval, __FILE__, __LINE__, __METHOD__, 10);

        return $retval;
    }

    public function getStateDependantAllowanceAmount()
    {
        $retarr = $this->getDataFromRateArray($this->getDate(), $this->state_options);
        if ($retarr == false) {
            return false;
        }

        $allowance_arr = $retarr['dependant_allowance'];

        $retval = bcmul($this->getUserValue2(), $allowance_arr);

        Debug::text('State Dependant Allowance Amount: ' . $retval, __FILE__, __LINE__, __METHOD__, 10);

        return $retval;
    }

    public function getStateFilingStatus()
    {
        if ($this->getUserValue3() != '') {
            return $this->getUserValue3();
        }

        return 10; //Single
    }

    public function getDataByIncome($income, $arr)
    {
        if (!is_array($arr)) {
            return false;
        }

        $prev_value = 0;
        $total_rates = count($arr) - 1;
        $i = 0;
        foreach ($arr as $key => $values) {
            if ($income > $prev_value and $income <= $values[0]) {
                return $values;
            } elseif ($i == $total_rates) {
                return $values;
            }
            $prev_value = $values[0];
            $i++;
        }

        return false;
    }
}
