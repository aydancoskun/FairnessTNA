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
class PayrollDeduction_US_MA extends PayrollDeduction_US
{
    /*
    protected $state_ma_filing_status_options = array(
                                                        10 => 'Regular',
                                                        20 => 'Head of Household',
                                                        30 => 'Blind',
                                                        40 => 'Head of Household and Blind'
                                    );
*/

    public $state_options = array(
        20160101 => array( //01-Jan-16
            'rate' => 5.10,
            'allowance' => array(4400, 1000), //1 = Base amount, 2 = Per Allowance multiplier
            'federal_tax_maximum' => 2000,
            'minimum_income' => 8000,
        ),
        20150101 => array( //01-Jan-15
            'rate' => 5.15,
            'allowance' => array(4400, 1000), //1 = Base amount, 2 = Per Allowance multiplier
            'federal_tax_maximum' => 2000,
            'minimum_income' => 8000,
        ),
        20140101 => array( //01-Jan-14
            'rate' => 5.20,
            'allowance' => array(4400, 1000), //1 = Base amount, 2 = Per Allowance multiplier
            'federal_tax_maximum' => 2000,
            'minimum_income' => 8000,
        ),
        20120101 => array( //01-Jan-12
            'rate' => 5.25,
            'allowance' => array(4400, 1000), //1 = Base amount, 2 = Per Allowance multiplier
            'federal_tax_maximum' => 2000,
            'minimum_income' => 8000,
        ),
        20090101 => array( //01-Jan-09
            'rate' => 5.30,
            'allowance' => array(4400, 1000), //1 = Base amount, 2 = Per Allowance multiplier
            'federal_tax_maximum' => 2000,
            'minimum_income' => 8000,
        ),
        20060101 => array( //01-Jan-06
            'rate' => 5.30,
            'standard_deduction' => array(
                10 => 0,
                20 => 2100,
                30 => 2200,
                40 => 2200
            ),
            'allowance' => array(3850, 2850),
            'federal_tax_maximum' => 2000,
            'minimum_income' => 8000,
        )
    );

    public function getStateTaxPayable()
    {
        $annual_income = $this->getStateAnnualTaxableIncome();

        $retval = 0;

        if ($annual_income > 0) {
            $retarr = $this->getDataFromRateArray($this->getDate(), $this->state_options);
            if ($retarr == false) {
                return false;
            }

            $rate = bcdiv($retarr['rate'], 100);

            $retval = bcmul($annual_income, $rate);
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
        $federal_tax = bcadd($this->getAnnualEmployeeMedicare(), $this->getAnnualEmployeeSocialSecurity());
        if ($this->getDate() >= 20090101) {
            $state_deductions = 0;
        } else {
            $state_deductions = $this->getStateStandardDeduction();
        }
        $state_allowance = $this->getStateAllowanceAmount();

        if ($federal_tax > $this->getStateFederalTaxMaximum()) {
            $federal_tax = $this->getStateFederalTaxMaximum();
        }
        Debug::text('Federal Tax: ' . $federal_tax, __FILE__, __LINE__, __METHOD__, 10);

        $income = bcsub(bcsub(bcsub($annual_income, $federal_tax), $state_deductions), $state_allowance);

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

    public function getStateAllowanceAmount()
    {
        $retarr = $this->getDataFromRateArray($this->getDate(), $this->state_options);
        if ($retarr == false) {
            return false;
        }

        $allowance_arr = $retarr['allowance'];
        if ($this->getDate() >= 20090101) {
            if ($this->getStateAllowance() == 0) {
                $retval = 0;
            } else {
                $retval = bcadd(bcsub($allowance_arr[0], $allowance_arr[1]), bcmul($this->getStateAllowance(), $allowance_arr[1]));
            }
        } else {
            if ($this->getStateAllowance() == 0) {
                $retval = 0;
            } elseif ($this->getStateAllowance() == 1) {
                $retval = $allowance_arr[0];
            } else {
                $retval = bcadd($allowance_arr[0], bcmul(bcsub($this->getStateAllowance(), 1), $allowance_arr[1]));
            }
        }

        Debug::text('State Allowance Amount: ' . $retval, __FILE__, __LINE__, __METHOD__, 10);

        return $retval;
    }

    public function getStateFederalTaxMaximum()
    {
        $retarr = $this->getDataFromRateArray($this->getDate(), $this->state_options);
        if ($retarr == false) {
            return false;
        }

        $maximum = $retarr['federal_tax_maximum'];

        Debug::text('Maximum State allowed Federal Tax: ' . $maximum, __FILE__, __LINE__, __METHOD__, 10);

        return $maximum;
    }
}
