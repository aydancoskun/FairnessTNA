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
class PayrollDeduction_US_MD extends PayrollDeduction_US
{
    /*
                                10 => TTi18n::gettext('Single'),
                                20 => TTi18n::gettext('Married (Filing Jointly)'),
                                30 => TTi18n::gettext('Married (Filing Separately)'),
                                40 => TTi18n::gettext('Head of Household'),
    */

    public $state_income_tax_rate_options = array(
        20130101 => array(
            10 => array(
                array('income' => 100000, 'rate' => 4.75, 'constant' => 0),
                array('income' => 125000, 'rate' => 5.00, 'constant' => 4750),
                array('income' => 150000, 'rate' => 5.25, 'constant' => 6000),
                array('income' => 250000, 'rate' => 5.50, 'constant' => 7312.50),
                array('income' => 250000, 'rate' => 5.75, 'constant' => 12812.50),
            ),
            20 => array(
                array('income' => 150000, 'rate' => 4.75, 'constant' => 0),
                array('income' => 175000, 'rate' => 5.00, 'constant' => 7125),
                array('income' => 225000, 'rate' => 5.25, 'constant' => 8375),
                array('income' => 300000, 'rate' => 5.50, 'constant' => 11000),
                array('income' => 300000, 'rate' => 5.75, 'constant' => 15125),
            ),
            30 => array(
                array('income' => 100000, 'rate' => 4.75, 'constant' => 0),
                array('income' => 125000, 'rate' => 5.00, 'constant' => 4750),
                array('income' => 150000, 'rate' => 5.25, 'constant' => 6000),
                array('income' => 250000, 'rate' => 5.50, 'constant' => 7312.50),
                array('income' => 250000, 'rate' => 5.75, 'constant' => 12812.50),
            ),
            40 => array(
                array('income' => 150000, 'rate' => 4.75, 'constant' => 0),
                array('income' => 175000, 'rate' => 5.00, 'constant' => 7125),
                array('income' => 225000, 'rate' => 5.25, 'constant' => 8375),
                array('income' => 300000, 'rate' => 5.50, 'constant' => 11000),
                array('income' => 300000, 'rate' => 5.75, 'constant' => 15125),
            ),
        ),
        20090101 => array(
            10 => array(
                array('income' => 150000, 'rate' => 4.75, 'constant' => 0),
                array('income' => 300000, 'rate' => 5, 'constant' => 7125),
                array('income' => 500000, 'rate' => 5.25, 'constant' => 14625),
                array('income' => 1000000, 'rate' => 5.5, 'constant' => 25125),
                array('income' => 1000000, 'rate' => 6.25, 'constant' => 52625),
            ),
            20 => array(
                array('income' => 200000, 'rate' => 4.75, 'constant' => 0),
                array('income' => 350000, 'rate' => 5, 'constant' => 9500),
                array('income' => 500000, 'rate' => 5.25, 'constant' => 19500),
                array('income' => 1000000, 'rate' => 5.5, 'constant' => 30000),
                array('income' => 1000000, 'rate' => 6.25, 'constant' => 57500),
            ),
            30 => array(
                array('income' => 150000, 'rate' => 4.75, 'constant' => 0),
                array('income' => 300000, 'rate' => 5, 'constant' => 7125),
                array('income' => 500000, 'rate' => 5.25, 'constant' => 14625),
                array('income' => 1000000, 'rate' => 5.5, 'constant' => 25125),
                array('income' => 1000000, 'rate' => 6.25, 'constant' => 52625),
            ),
            40 => array(
                array('income' => 200000, 'rate' => 4.75, 'constant' => 0),
                array('income' => 350000, 'rate' => 5, 'constant' => 9500),
                array('income' => 500000, 'rate' => 5.25, 'constant' => 19500),
                array('income' => 1000000, 'rate' => 5.5, 'constant' => 30000),
                array('income' => 1000000, 'rate' => 6.25, 'constant' => 57500),
            ),
        ),
        20080101 => array(
            10 => array(
                array('income' => 1000, 'rate' => 2, 'constant' => 0),
                array('income' => 2000, 'rate' => 3, 'constant' => 20),
                array('income' => 3000, 'rate' => 4, 'constant' => 50),
                array('income' => 150000, 'rate' => 4.75, 'constant' => 90),
                array('income' => 300000, 'rate' => 5, 'constant' => 7072.50),
                array('income' => 500000, 'rate' => 5.25, 'constant' => 14572.50),
                array('income' => 1000000, 'rate' => 5.5, 'constant' => 25072.50),
                array('income' => 1000000, 'rate' => 6.25, 'constant' => 52572.50),
            ),
            20 => array(
                array('income' => 1000, 'rate' => 2, 'constant' => 0),
                array('income' => 2000, 'rate' => 3, 'constant' => 20),
                array('income' => 3000, 'rate' => 4, 'constant' => 50),
                array('income' => 200000, 'rate' => 4.75, 'constant' => 90),
                array('income' => 350000, 'rate' => 5, 'constant' => 9447.50),
                array('income' => 500000, 'rate' => 5.25, 'constant' => 16947.50),
                array('income' => 1000000, 'rate' => 5.5, 'constant' => 24822.50),
                array('income' => 1000000, 'rate' => 6.25, 'constant' => 52322.50),
            ),
            30 => array(
                array('income' => 1000, 'rate' => 2, 'constant' => 0),
                array('income' => 2000, 'rate' => 3, 'constant' => 20),
                array('income' => 3000, 'rate' => 4, 'constant' => 50),
                array('income' => 150000, 'rate' => 4.75, 'constant' => 90),
                array('income' => 300000, 'rate' => 5, 'constant' => 7072.50),
                array('income' => 500000, 'rate' => 5.25, 'constant' => 14572.50),
                array('income' => 1000000, 'rate' => 5.5, 'constant' => 25072.50),
                array('income' => 1000000, 'rate' => 6.25, 'constant' => 52572.50),
            ),
            40 => array(
                array('income' => 1000, 'rate' => 2, 'constant' => 0),
                array('income' => 2000, 'rate' => 3, 'constant' => 20),
                array('income' => 3000, 'rate' => 4, 'constant' => 50),
                array('income' => 200000, 'rate' => 4.75, 'constant' => 90),
                array('income' => 350000, 'rate' => 5, 'constant' => 9447.50),
                array('income' => 500000, 'rate' => 5.25, 'constant' => 16947.50),
                array('income' => 1000000, 'rate' => 5.5, 'constant' => 24822.50),
                array('income' => 1000000, 'rate' => 6.25, 'constant' => 52322.50),
            ),
        ),
    );

    //
    //I don't think will ever be 100% accurate, because the tax brackets completely change for each county, based on the county percent.
    //We will need to have the county tax rate passed into this class so the proper calculations can be made.
    //
    public $state_options = array(
        //01-Jan-13: No Changes
        //01-Jan-12: No Changes
        //01-Jan-11: No Changes
        //01-Jan-10: No Changes
        //01-Jan-09: No Changes
        20080101 => array( //2008
            'standard_deduction' => array(
                'minimum' => 1500,
                'maximum' => 2000,
                'rate' => 0.15, //percent
            ),
            'allowance' => 3200
        ),
    );

    public function getStateTaxPayable()
    {
        $annual_income = $this->getStateAnnualTaxableIncome();

        $retval = 0;

        $county_rate = bcdiv($this->getUserValue3(), 100);
        if (!is_numeric($county_rate) or $county_rate < 0) {
            $county_rate = 0;
        }
        Debug::text('County Rate: ' . $county_rate, __FILE__, __LINE__, __METHOD__, 10);

        if ($annual_income > 0) {
            $rate = $this->getData()->getStateRate($annual_income);
            $state_constant = $this->getData()->getStateConstant($annual_income);
            $state_rate_income = $this->getData()->getStateRatePreviousIncome($annual_income);

            //Modify rate/constant based on county rate, since it affects each tax bracket.

            //Calculate the constant modifier, based on the county_rate percent difference from the state rate.
            $constant_modifier = bcdiv($county_rate, $rate); //Percent that the constant needs to be modified by.
            $county_constant = bcmul($state_constant, $constant_modifier);
            Debug::text('County: Rate: ' . $county_rate . ' Modifier Rate: ' . $constant_modifier . ' County Constant: ' . $county_constant, __FILE__, __LINE__, __METHOD__, 10);

            $rate = bcadd($rate, $county_rate);
            $state_constant = bcadd($state_constant, $county_constant);

            Debug::text('Rate: ' . $rate . ' Constant: ' . $state_constant . ' Rate Income: ' . $state_rate_income, __FILE__, __LINE__, __METHOD__, 10);
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
        //$federal_tax = $this->getFederalTaxPayable();
        $standard_deduction = $this->getStateStandardDeduction();
        $state_allowance = $this->getStateAllowanceAmount();

        //Debug::text('Federal Annual Tax: '. $federal_tax, __FILE__, __LINE__, __METHOD__, 10);
        Debug::text('Standard Deduction: ' . $standard_deduction, __FILE__, __LINE__, __METHOD__, 10);
        Debug::text('State Allowance: ' . $state_allowance, __FILE__, __LINE__, __METHOD__, 10);

        $income = bcsub(bcsub($annual_income, $standard_deduction), $state_allowance);

        Debug::text('State Annual Taxable Income: ' . $income, __FILE__, __LINE__, __METHOD__, 10);

        return $income;
    }

    public function getStateStandardDeduction()
    {
        $retarr = $this->getDataFromRateArray($this->getDate(), $this->state_options);
        if ($retarr == false) {
            return false;
        }

        $deduction_arr = $retarr['standard_deduction'];

        $retval = bcmul($this->getAnnualTaxableIncome(), $deduction_arr['rate']);

        if ($retval < $deduction_arr['minimum']) {
            $retval = $deduction_arr['minimum'];
        }

        if ($retval > $deduction_arr['maximum']) {
            $retval = $deduction_arr['maximum'];
        }

        Debug::text('State Standard Deduction Amount: ' . $retval, __FILE__, __LINE__, __METHOD__, 10);

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
