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
class PayrollDeduction_US_SC extends PayrollDeduction_US
{
    public $state_income_tax_rate_options = array(
        20170101 => array(
            0 => array(
                array('income' => 2140, 'rate' => 1.7, 'constant' => 0),
                array('income' => 4280, 'rate' => 3, 'constant' => 27.82),
                array('income' => 6420, 'rate' => 4, 'constant' => 70.62),
                array('income' => 8560, 'rate' => 5, 'constant' => 134.82),
                array('income' => 10700, 'rate' => 6, 'constant' => 220.42),
                array('income' => 10700, 'rate' => 7, 'constant' => 327.42),
            ),
        ),
        20060101 => array(
            0 => array(
                array('income' => 2000, 'rate' => 2, 'constant' => 0),
                array('income' => 4000, 'rate' => 3, 'constant' => 20),
                array('income' => 6000, 'rate' => 4, 'constant' => 60),
                array('income' => 8000, 'rate' => 5, 'constant' => 120),
                array('income' => 10000, 'rate' => 6, 'constant' => 200),
                array('income' => 10000, 'rate' => 7, 'constant' => 300),
            ),
        ),
    );

    public $state_options = array(
        20170101 => array(
            'standard_deduction_rate' => 10,
            'standard_deduction_maximum' => 2860,
            'allowance' => 2370
        ),
        20060101 => array(
            'standard_deduction_rate' => 10,
            'standard_deduction_maximum' => 2600,
            'allowance' => 2300
        ),
    );

    public function getStateFederalTaxMaximum()
    {
        $retarr = $this->getDataFromRateArray($this->getDate(), $this->state_options);
        if ($retarr == false) {
            return false;
        }

        $maximum = $retarr['federal_tax_maximum'][$this->getStateFilingStatus()];

        Debug::text('Maximum State allowed Federal Tax: ' . $maximum, __FILE__, __LINE__, __METHOD__, 10);

        return $maximum;
    }

    public function getStateTaxPayable()
    {
        $annual_income = $this->getStateAnnualTaxableIncome();

        $retval = 0;

        if ($annual_income > 0) {
            $rate = $this->getData()->getStateRate($annual_income);
            $state_constant = $this->getData()->getStateConstant($annual_income);
            //$state_rate_income = $this->getData()->getStateRatePreviousIncome($annual_income);

            //$retval = bcadd( bcmul( bcsub( $annual_income, $state_rate_income ), $rate ), $state_constant );
            $retval = bcsub(bcmul($annual_income, $rate), $state_constant);
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
        $standard_deductions = $this->getStateStandardDeduction();
        $allowance = $this->getStateAllowanceAmount();

        $income = bcsub(bcsub($annual_income, $standard_deductions), $allowance);

        Debug::text('State Annual Taxable Income: ' . $income, __FILE__, __LINE__, __METHOD__, 10);

        return $income;
    }

    public function getStateStandardDeduction()
    {
        $retarr = $this->getDataFromRateArray($this->getDate(), $this->state_options);
        if ($retarr == false) {
            return false;
        }

        if ($this->getStateAllowance() == 0) {
            $deduction = 0;
        } else {
            $rate = bcdiv($retarr['standard_deduction_rate'], 100);
            $deduction = bcmul($this->getAnnualTaxableIncome(), $rate);
            if ($deduction > $retarr['standard_deduction_maximum']) {
                $deduction = $retarr['standard_deduction_maximum'];
            }
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

        $allowance = $retarr['allowance'];

        $retval = bcmul($this->getStateAllowance(), $allowance);

        Debug::text('State Allowance Amount: ' . $retval, __FILE__, __LINE__, __METHOD__, 10);

        return $retval;
    }
}
