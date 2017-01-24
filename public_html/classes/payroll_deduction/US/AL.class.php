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
class PayrollDeduction_US_AL extends PayrollDeduction_US
{
    /*
    protected $state_al_filing_status_options = array(
                                                        10 => 'Status "S" Claiming $1500',
                                                        20 => 'Status "M" Claiming $3000',
                                                        30 => 'Status "0"',
                                                        40 => 'Head of Household'
                                                        50 => 'Status "MS"'
                                    );
*/
    public $state_income_tax_rate_options = array(
        20120101 => array(
            10 => array(
                array('income' => 500, 'rate' => 2, 'constant' => 0),
                array('income' => 2500, 'rate' => 4, 'constant' => 10),
                array('income' => 3000, 'rate' => 5, 'constant' => 90),
            ),
            20 => array(
                array('income' => 1000, 'rate' => 2, 'constant' => 0),
                array('income' => 5000, 'rate' => 4, 'constant' => 20),
                array('income' => 6000, 'rate' => 5, 'constant' => 180),
            ),
            30 => array(
                array('income' => 500, 'rate' => 2, 'constant' => 0),
                array('income' => 2500, 'rate' => 4, 'constant' => 10),
                array('income' => 3000, 'rate' => 5, 'constant' => 90),
            ),
            40 => array(
                array('income' => 500, 'rate' => 2, 'constant' => 0),
                array('income' => 2500, 'rate' => 4, 'constant' => 10),
                array('income' => 3000, 'rate' => 5, 'constant' => 90),
            ),
            50 => array(
                array('income' => 500, 'rate' => 2, 'constant' => 0),
                array('income' => 2500, 'rate' => 4, 'constant' => 10),
                array('income' => 3000, 'rate' => 5, 'constant' => 90),
            ),
        ),
        20060101 => array(
            10 => array(
                array('income' => 500, 'rate' => 2, 'constant' => 0),
                array('income' => 3000, 'rate' => 4, 'constant' => 10),
                array('income' => 3000, 'rate' => 5, 'constant' => 110),
            ),
            20 => array(
                array('income' => 1000, 'rate' => 2, 'constant' => 0),
                array('income' => 6000, 'rate' => 4, 'constant' => 20),
                array('income' => 6000, 'rate' => 5, 'constant' => 220),
            ),
            30 => array(
                array('income' => 500, 'rate' => 2, 'constant' => 0),
                array('income' => 3000, 'rate' => 4, 'constant' => 10),
                array('income' => 3000, 'rate' => 5, 'constant' => 110),
            ),
            40 => array(
                array('income' => 500, 'rate' => 2, 'constant' => 0),
                array('income' => 3000, 'rate' => 4, 'constant' => 10),
                array('income' => 3000, 'rate' => 5, 'constant' => 110),
            ),
            50 => array(
                array('income' => 500, 'rate' => 2, 'constant' => 0),
                array('income' => 3000, 'rate' => 4, 'constant' => 10),
                array('income' => 3000, 'rate' => 5, 'constant' => 110),
            ),
        ),
    );

    public $state_options = array(
        20130709 => array( //09-Jul-13 (was 13-Jul-09)
            'standard_deduction_rate' => 0,
            'standard_deduction_maximum' => array(
                '10' => array(
                    //1 = Income
                    //2 = Reduce By
                    //3 = Reduce by for every amount over the prev income level.
                    //4 = Previous Income
                    0 => array(20499, 2500, 0, 0, 0),
                    1 => array(30000, 2500, 25, 500, 20499),
                    2 => array(30000, 2000, 0, 0, 30000)
                ),
                '20' => array(
                    0 => array(20499, 7500, 0, 0, 0),
                    1 => array(30000, 7500, 175, 500, 20499),
                    2 => array(30000, 4000, 0, 0, 30000)
                ),
                '30' => array(
                    0 => array(20499, 2500, 0, 0, 0),
                    1 => array(30000, 2500, 25, 500, 20000),
                    2 => array(30000, 2000, 0, 0, 30000)
                ),
                '40' => array(
                    0 => array(20499, 4700, 0, 0, 0),
                    1 => array(30000, 4700, 135, 500, 20499),
                    2 => array(30000, 2000, 0, 0, 30000)
                ),
                '50' => array(
                    0 => array(10249, 3750, 0, 0, 0),
                    1 => array(15000, 3750, 88, 250, 10249),
                    2 => array(15000, 2000, 0, 0, 15000)
                ),
            ),
            'personal_deduction' => array(
                '10' => 1500,
                '20' => 3000,
                '30' => 0,
                '40' => 3000,
                '50' => 1500,
            ),

            'dependant_allowance' => array(
                0 => array(20000, 1000),
                1 => array(100000, 500),
                2 => array(100000, 300)
            )
        ),
        20070101 => array(
            'standard_deduction_rate' => 0,
            'standard_deduction_maximum' => array(
                '10' => array(
                    //1 = Income
                    //2 = Reduce By
                    //3 = Reduce by for every amount over the prev income level.
                    //4 = Previous Income
                    0 => array(20000, 2500, 0, 0, 0),
                    1 => array(30000, 2500, 25, 500, 20000),
                    2 => array(30000, 2000, 0, 0, 30000)
                ),
                '20' => array(
                    0 => array(20000, 7500, 0, 0, 0),
                    1 => array(30000, 7500, 175, 500, 20000),
                    2 => array(30000, 4000, 0, 0, 30000)
                ),
                '30' => array(
                    0 => array(20000, 2500, 0, 0, 0),
                    1 => array(30000, 2500, 25, 500, 20000),
                    2 => array(30000, 2000, 0, 0, 30000)
                ),
                '40' => array(
                    0 => array(20000, 4700, 0, 0, 0),
                    1 => array(30000, 4700, 135, 500, 20000),
                    2 => array(30000, 2000, 0, 0, 30000)
                ),
                '50' => array(
                    0 => array(10000, 3750, 0, 0, 0),
                    1 => array(15000, 3750, 88, 250, 10000),
                    2 => array(15000, 2000, 0, 0, 15000)
                ),
            ),
            'personal_deduction' => array(
                '10' => 1500,
                '20' => 3000,
                '30' => 0,
                '40' => 3000,
                '50' => 1500,
            ),

            'dependant_allowance' => array(
                0 => array(20000, 1000),
                1 => array(100000, 500),
                2 => array(100000, 300)
            )
        ),
        20060101 => array(
            'standard_deduction_rate' => 20,
            'standard_deduction_maximum' => array(
                '10' => 2000,
                '20' => 4000,
                '30' => 2000,
                '40' => 2000,
                '50' => 2000,
            ),
            'personal_deduction' => array(
                '10' => 1500,
                '20' => 3000,
                '30' => 0,
                '40' => 3000,
                '50' => 1500
            ),

            'dependant_allowance' => 300
        )
    );

    public function getStateTaxPayable()
    {
        $annual_income = $this->getStateAnnualTaxableIncome();

        $retval = 0;

        if ($annual_income > 0) {
            $rate = $this->getData()->getStateRate($annual_income);
            $state_constant = $this->getData()->getStateConstant($annual_income);
            $prev_income = $this->getData()->getStateRatePreviousIncome($annual_income);

            Debug::text('Rate: ' . $rate . ' Constant: ' . $state_constant . ' Prev Rate Income: ' . $prev_income, __FILE__, __LINE__, __METHOD__, 10);
            $retval = bcadd(bcmul(bcsub($annual_income, $prev_income), $rate), $state_constant);
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
        $federal_tax = $this->getFederalTaxPayable();
        $standard_deduction = $this->getStateStandardDeduction();
        $personal_deduction = $this->getStatePersonalDeduction();
        $dependant_allowance = $this->getStateDependantAllowanceAmount();

        Debug::text('Federal Annual Tax: ' . $federal_tax, __FILE__, __LINE__, __METHOD__, 10);
        Debug::text('Standard Deduction: ' . $standard_deduction, __FILE__, __LINE__, __METHOD__, 10);
        Debug::text('Personal Deduction: ' . $personal_deduction, __FILE__, __LINE__, __METHOD__, 10);
        Debug::text('Dependant Allowance: ' . $dependant_allowance, __FILE__, __LINE__, __METHOD__, 10);

        $income = bcsub(bcsub(bcsub(bcsub($annual_income, $standard_deduction), $personal_deduction), $dependant_allowance), $federal_tax);

        Debug::text('State Annual Taxable Income: ' . $income, __FILE__, __LINE__, __METHOD__, 10);

        return $income;
    }

    public function getStateStandardDeduction()
    {
        $retarr = $this->getDataFromRateArray($this->getDate(), $this->state_options);
        if ($retarr == false) {
            return false;
        }

        if ($this->getDate() >= 20070101) {
            Debug::text('Standard Deduction Formula (NEW)', __FILE__, __LINE__, __METHOD__, 10);
            $deduction_arr = $this->getDataByIncome($this->getAnnualTaxableIncome(), $retarr['standard_deduction_maximum'][$this->getStateFilingStatus()]);

            if ($deduction_arr[3] > 0) {
                Debug::text('Complex Standard Deduction Formula (NEW)', __FILE__, __LINE__, __METHOD__, 10);
                //Find out how far we're over the previous income level.
                $deduction = bcsub($deduction_arr[1], bcmul(ceil(bcdiv(bcsub($this->getAnnualTaxableIncome(), $deduction_arr[4]), $deduction_arr[3])), $deduction_arr[2]));
            } else {
                Debug::text('Basic Standard Deduction Formula (NEW)', __FILE__, __LINE__, __METHOD__, 10);
                $deduction = $deduction_arr[1];
            }
        } else {
            Debug::text('Standard Deduction Forumla (OLD)', __FILE__, __LINE__, __METHOD__, 10);
            $rate = bcdiv($retarr['standard_deduction_rate'], 100);

            $deduction = bcmul($this->getAnnualTaxableIncome(), $rate);

            if ($deduction >= $retarr['standard_deduction_maximum'][$this->getStateFilingStatus()]) {
                $deduction = $retarr['standard_deduction_maximum'][$this->getStateFilingStatus()];
            }
        }

        Debug::text('Standard Deduction: ' . $deduction . ' Filing Status: ' . $this->getStateFilingStatus(), __FILE__, __LINE__, __METHOD__, 10);

        return $deduction;
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
            if ($this->getAnnualTaxableIncome() > $prev_value and $this->getAnnualTaxableIncome() <= $values[0]) {
                return $values;
            } elseif ($i == $total_rates) {
                return $values;
            }
            $prev_value = $values[0];
            $i++;
        }

        return false;
    }

    public function getStatePersonalDeduction()
    {
        $retarr = $this->getDataFromRateArray($this->getDate(), $this->state_options);
        if ($retarr == false) {
            return false;
        }

        $deduction = $retarr['personal_deduction'][$this->getStateFilingStatus()];

        Debug::text('Personal Deduction: ' . $deduction . ' Filing Status: ' . $this->getStateFilingStatus(), __FILE__, __LINE__, __METHOD__, 10);

        return $deduction;
    }

    public function getStateDependantAllowanceAmount()
    {
        $retarr = $this->getDataFromRateArray($this->getDate(), $this->state_options);
        if ($retarr == false) {
            return false;
        }

        if ($this->getDate() >= 20070101) {
            $allowance_arr = $this->getDataByIncome($this->getAnnualTaxableIncome(), $retarr['dependant_allowance']);
            $allowance = $allowance_arr[1];
        } else {
            $allowance = $retarr['dependant_allowance'];
        }

        $retval = bcmul($allowance, $this->getStateAllowance());

        Debug::text('State Dependant Allowance Amount: ' . $retval, __FILE__, __LINE__, __METHOD__, 10);

        return $retval;
    }
}
