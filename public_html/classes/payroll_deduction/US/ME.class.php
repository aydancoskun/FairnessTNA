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
class PayrollDeduction_US_ME extends PayrollDeduction_US
{
    public $state_income_tax_rate_options = array(
        20170101 => array(
            10 => array(
                array('income' => 21100, 'rate' => 5.8, 'constant' => 0),
                array('income' => 50000, 'rate' => 6.75, 'constant' => 1224),
                array('income' => 200001, 'rate' => 7.15, 'constant' => 3175),
                array('income' => 200001, 'rate' => 10.15, 'constant' => 13900),
            ),
            20 => array(
                array('income' => 42250, 'rate' => 5.8, 'constant' => 0),
                array('income' => 100000, 'rate' => 6.75, 'constant' => 2451),
                array('income' => 200001, 'rate' => 7.15, 'constant' => 6349),
                array('income' => 200001, 'rate' => 10.15, 'constant' => 13499),
            ),
        ),
        20160101 => array(
            10 => array(
                array('income' => 8750, 'rate' => 0, 'constant' => 0),
                array('income' => 29800, 'rate' => 5.8, 'constant' => 0),
                array('income' => 46250, 'rate' => 6.75, 'constant' => 1221),
                array('income' => 46250, 'rate' => 7.15, 'constant' => 2331),
            ),
            20 => array(
                array('income' => 20350, 'rate' => 0, 'constant' => 0),
                array('income' => 62450, 'rate' => 5.8, 'constant' => 0),
                array('income' => 95350, 'rate' => 6.75, 'constant' => 2442),
                array('income' => 95350, 'rate' => 7.15, 'constant' => 4663),
            ),
        ),
        20150101 => array(
            10 => array(
                array('income' => 8650, 'rate' => 0, 'constant' => 0),
                array('income' => 24350, 'rate' => 6.5, 'constant' => 0),
                array('income' => 24350, 'rate' => 7.95, 'constant' => 1020.50),
            ),
            20 => array(
                array('income' => 20200, 'rate' => 0, 'constant' => 0),
                array('income' => 51600, 'rate' => 6.5, 'constant' => 0),
                array('income' => 51600, 'rate' => 7.95, 'constant' => 2041),
            ),
        ),
        20140101 => array(
            10 => array(
                array('income' => 8550, 'rate' => 0, 'constant' => 0),
                array('income' => 24250, 'rate' => 6.5, 'constant' => 0),
                array('income' => 24250, 'rate' => 7.95, 'constant' => 1020.50),
            ),
            20 => array(
                array('income' => 20000, 'rate' => 0, 'constant' => 0),
                array('income' => 51400, 'rate' => 6.5, 'constant' => 0),
                array('income' => 51400, 'rate' => 7.95, 'constant' => 2041),
            ),
        ),
        20130101 => array(
            10 => array(
                array('income' => 8450, 'rate' => 0, 'constant' => 0),
                array('income' => 24150, 'rate' => 6.5, 'constant' => 0),
                array('income' => 24150, 'rate' => 7.95, 'constant' => 1021),
            ),
            20 => array(
                array('income' => 17750, 'rate' => 0, 'constant' => 0),
                array('income' => 49150, 'rate' => 6.5, 'constant' => 0),
                array('income' => 49150, 'rate' => 7.95, 'constant' => 2041),
            ),
        ),
        20120101 => array(
            10 => array(
                array('income' => 3100, 'rate' => 0, 'constant' => 0),
                array('income' => 8200, 'rate' => 2.0, 'constant' => 0),
                array('income' => 13250, 'rate' => 4.5, 'constant' => 102),
                array('income' => 23450, 'rate' => 7.0, 'constant' => 329),
                array('income' => 23450, 'rate' => 8.5, 'constant' => 1043),
            ),
            20 => array(
                array('income' => 9050, 'rate' => 0, 'constant' => 0),
                array('income' => 19250, 'rate' => 2.0, 'constant' => 0),
                array('income' => 29400, 'rate' => 4.5, 'constant' => 204),
                array('income' => 49750, 'rate' => 7.0, 'constant' => 661),
                array('income' => 49750, 'rate' => 8.5, 'constant' => 2085),
            ),
        ),
        20110101 => array(
            10 => array(
                array('income' => 2950, 'rate' => 0, 'constant' => 0),
                array('income' => 7950, 'rate' => 2.0, 'constant' => 0),
                array('income' => 12900, 'rate' => 4.5, 'constant' => 100),
                array('income' => 22900, 'rate' => 7.0, 'constant' => 323),
                array('income' => 22900, 'rate' => 8.5, 'constant' => 1023),
            ),
            20 => array(
                array('income' => 6800, 'rate' => 0, 'constant' => 0),
                array('income' => 16800, 'rate' => 2.0, 'constant' => 0),
                array('income' => 26750, 'rate' => 4.5, 'constant' => 200),
                array('income' => 46700, 'rate' => 7.0, 'constant' => 648),
                array('income' => 46700, 'rate' => 8.5, 'constant' => 2045),
            ),
        ),
        20100101 => array(
            10 => array(
                array('income' => 2850, 'rate' => 0, 'constant' => 0),
                array('income' => 7800, 'rate' => 2.0, 'constant' => 0),
                array('income' => 12700, 'rate' => 4.5, 'constant' => 99),
                array('income' => 22600, 'rate' => 7.0, 'constant' => 320),
                array('income' => 22600, 'rate' => 8.5, 'constant' => 1013),
            ),
            20 => array(
                array('income' => 6700, 'rate' => 0, 'constant' => 0),
                array('income' => 16650, 'rate' => 2.0, 'constant' => 0),
                array('income' => 26450, 'rate' => 4.5, 'constant' => 199),
                array('income' => 46250, 'rate' => 7.0, 'constant' => 640),
                array('income' => 46250, 'rate' => 8.5, 'constant' => 2026),
            ),
        ),
        20090101 => array(
            10 => array(
                array('income' => 2850, 'rate' => 0, 'constant' => 0),
                array('income' => 7900, 'rate' => 2.0, 'constant' => 0),
                array('income' => 12900, 'rate' => 4.5, 'constant' => 101),
                array('income' => 23000, 'rate' => 7.0, 'constant' => 326),
                array('income' => 23000, 'rate' => 8.5, 'constant' => 1033),
            ),
            20 => array(
                array('income' => 6650, 'rate' => 0, 'constant' => 0),
                array('income' => 16800, 'rate' => 2.0, 'constant' => 0),
                array('income' => 26800, 'rate' => 4.5, 'constant' => 203),
                array('income' => 47000, 'rate' => 7.0, 'constant' => 653),
                array('income' => 47000, 'rate' => 8.5, 'constant' => 2067),
            ),
        ),
        20060101 => array(
            10 => array(
                array('income' => 2300, 'rate' => 0, 'constant' => 0),
                array('income' => 6850, 'rate' => 2.0, 'constant' => 0),
                array('income' => 11400, 'rate' => 4.5, 'constant' => 91),
                array('income' => 20550, 'rate' => 7.0, 'constant' => 296),
                array('income' => 20550, 'rate' => 8.5, 'constant' => 936),
            ),
            20 => array(
                array('income' => 5750, 'rate' => 0, 'constant' => 0),
                array('income' => 14900, 'rate' => 2.0, 'constant' => 0),
                array('income' => 24000, 'rate' => 4.5, 'constant' => 183),
                array('income' => 42300, 'rate' => 7.0, 'constant' => 593),
                array('income' => 42300, 'rate' => 8.5, 'constant' => 1874),
            ),
            30 => array(
                array('income' => 2875, 'rate' => 0, 'constant' => 0),
                array('income' => 7450, 'rate' => 2.0, 'constant' => 0),
                array('income' => 12000, 'rate' => 4.5, 'constant' => 92),
                array('income' => 21150, 'rate' => 7.0, 'constant' => 296),
                array('income' => 21150, 'rate' => 8.5, 'constant' => 937),
            ),
        ),
    );

    public $state_options = array(
        20170101 => array( //01-Jan-17 - Standard Deduction formula seems to have changed slightly in 2017.
            'allowance' => 4050,
            'standard_deduction' => array(
                '10' => 8750,
                '20' => 20350,
            ),
            'standard_deduction_threshold' => array(
                '10' => array(70000, 145000, 75000), //Min/Max/Divisor
                '20' => array(140000, 290000, 150000), //Min/Max/Divsor
            ),
        ),
        20160101 => array( //01-Jan-16
            'allowance' => 4050
        ),
        20150101 => array( //01-Jan-15
            'allowance' => 4000
        ),
        20140101 => array( //01-Jan-14
            'allowance' => 3950
        ),
        20130101 => array( //01-Jan-13
            'allowance' => 3900
        ),
        //01-Jan-12: No Change.
        //01-Jan-11: No Change.
        //01-Jan-10: No Change.
        //01-Jan-09: No Change.
        20060101 => array(
            'allowance' => 2850
        )
    );

    public function getStatePayPeriodDeductionRoundedValue($amount)
    {
        return $this->RoundNearestDollar($amount);
    }

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
        $state_deductions = $this->getStateStandardDeduction();
        $state_allowance = $this->getStateAllowanceAmount();

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

        if (!isset($retarr['standard_deduction'][$this->getStateFilingStatus()])) {
            return false;
        }

        if (!isset($retarr['standard_deduction_threshold'][$this->getStateFilingStatus()])) {
            return false;
        }

        $annual_income = $this->getAnnualTaxableIncome();
        $deduction = $retarr['standard_deduction'][$this->getStateFilingStatus()];
        $thresholds = $retarr['standard_deduction_threshold'][$this->getStateFilingStatus()];

        if ($annual_income <= $thresholds[0]) {
            $retval = $deduction;
        } elseif ($annual_income >= $thresholds[1]) {
            $retval = 0;
        } else {
            $retval = bcmul(bcsub(1, bcdiv(bcsub($annual_income, $thresholds[0]), $thresholds[2], 4)), $deduction);
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

        $allowance_arr = $retarr['allowance'];

        $retval = bcmul($this->getStateAllowance(), $allowance_arr);

        Debug::text('State Allowance Amount: ' . $retval, __FILE__, __LINE__, __METHOD__, 10);

        return $retval;
    }
}
