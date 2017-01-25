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
class PayrollDeduction_US_ID extends PayrollDeduction_US
{
    public $state_income_tax_rate_options = array(
        20160101 => array( //01-Jan-2016 (Guide updated June 2016, but it was retroactive.)
            10 => array(
                array('income' => 2250, 'rate' => 0, 'constant' => 0),
                array('income' => 3704, 'rate' => 1.6, 'constant' => 0),
                array('income' => 5158, 'rate' => 3.6, 'constant' => 23),
                array('income' => 6612, 'rate' => 4.1, 'constant' => 75),
                array('income' => 8066, 'rate' => 5.1, 'constant' => 135),
                array('income' => 9520, 'rate' => 6.1, 'constant' => 209),
                array('income' => 13155, 'rate' => 7.1, 'constant' => 298),
                array('income' => 13155, 'rate' => 7.4, 'constant' => 556),
            ),
            20 => array(
                array('income' => 8550, 'rate' => 0, 'constant' => 0),
                array('income' => 11458, 'rate' => 1.6, 'constant' => 0),
                array('income' => 14366, 'rate' => 3.6, 'constant' => 47),
                array('income' => 17274, 'rate' => 4.1, 'constant' => 152),
                array('income' => 20182, 'rate' => 5.1, 'constant' => 271),
                array('income' => 23090, 'rate' => 6.1, 'constant' => 419),
                array('income' => 30360, 'rate' => 7.1, 'constant' => 596),
                array('income' => 30360, 'rate' => 7.4, 'constant' => 1112),
            ),
        ),
        20140601 => array(
            10 => array(
                array('income' => 2250, 'rate' => 0, 'constant' => 0),
                array('income' => 3679, 'rate' => 1.6, 'constant' => 0),
                array('income' => 5108, 'rate' => 3.6, 'constant' => 23),
                array('income' => 6537, 'rate' => 4.1, 'constant' => 74),
                array('income' => 7966, 'rate' => 5.1, 'constant' => 133),
                array('income' => 9395, 'rate' => 6.1, 'constant' => 206),
                array('income' => 12968, 'rate' => 7.1, 'constant' => 293),
                array('income' => 12968, 'rate' => 7.4, 'constant' => 547),
            ),
            20 => array(
                array('income' => 8450, 'rate' => 0, 'constant' => 0),
                array('income' => 11308, 'rate' => 1.6, 'constant' => 0),
                array('income' => 14166, 'rate' => 3.6, 'constant' => 46),
                array('income' => 17024, 'rate' => 4.1, 'constant' => 149),
                array('income' => 19882, 'rate' => 5.1, 'constant' => 266),
                array('income' => 22740, 'rate' => 6.1, 'constant' => 412),
                array('income' => 29886, 'rate' => 7.1, 'constant' => 586),
                array('income' => 29886, 'rate' => 7.4, 'constant' => 1093),
            ),
        ),
        20130521 => array(
            10 => array(
                array('income' => 2200, 'rate' => 0, 'constant' => 0),
                array('income' => 3609, 'rate' => 1.6, 'constant' => 0),
                array('income' => 5018, 'rate' => 3.6, 'constant' => 23),
                array('income' => 6427, 'rate' => 4.1, 'constant' => 74),
                array('income' => 7836, 'rate' => 5.1, 'constant' => 132),
                array('income' => 9245, 'rate' => 6.1, 'constant' => 204),
                array('income' => 12768, 'rate' => 7.1, 'constant' => 290),
                array('income' => 12768, 'rate' => 7.4, 'constant' => 540),
            ),
            20 => array(
                array('income' => 8300, 'rate' => 0, 'constant' => 0),
                array('income' => 11118, 'rate' => 1.6, 'constant' => 0),
                array('income' => 13936, 'rate' => 3.6, 'constant' => 45),
                array('income' => 16754, 'rate' => 4.1, 'constant' => 146),
                array('income' => 19572, 'rate' => 5.1, 'constant' => 262),
                array('income' => 22390, 'rate' => 6.1, 'constant' => 406),
                array('income' => 29436, 'rate' => 7.1, 'constant' => 578),
                array('income' => 29436, 'rate' => 7.4, 'constant' => 1078),
            ),
        ),
        20130101 => array(
            10 => array(
                array('income' => 2150, 'rate' => 0, 'constant' => 0),
                array('income' => 3530, 'rate' => 1.6, 'constant' => 0),
                array('income' => 4910, 'rate' => 3.6, 'constant' => 22),
                array('income' => 6290, 'rate' => 4.1, 'constant' => 72),
                array('income' => 7670, 'rate' => 5.1, 'constant' => 129),
                array('income' => 9050, 'rate' => 6.1, 'constant' => 199),
                array('income' => 12500, 'rate' => 7.1, 'constant' => 283),
                array('income' => 12500, 'rate' => 7.4, 'constant' => 528),
            ),
            20 => array(
                array('income' => 8100, 'rate' => 0, 'constant' => 0),
                array('income' => 10860, 'rate' => 1.6, 'constant' => 0),
                array('income' => 13620, 'rate' => 3.6, 'constant' => 44),
                array('income' => 16380, 'rate' => 4.1, 'constant' => 143),
                array('income' => 19140, 'rate' => 5.1, 'constant' => 256),
                array('income' => 21900, 'rate' => 6.1, 'constant' => 397),
                array('income' => 28800, 'rate' => 7.1, 'constant' => 565),
                array('income' => 28800, 'rate' => 7.4, 'constant' => 1055),
            ),
        ),
        20120101 => array(
            10 => array(
                array('income' => 2100, 'rate' => 0, 'constant' => 0),
                array('income' => 3438, 'rate' => 1.6, 'constant' => 0),
                array('income' => 4776, 'rate' => 3.6, 'constant' => 21),
                array('income' => 6114, 'rate' => 4.1, 'constant' => 69),
                array('income' => 7452, 'rate' => 5.1, 'constant' => 124),
                array('income' => 8790, 'rate' => 6.1, 'constant' => 192),
                array('income' => 12135, 'rate' => 7.1, 'constant' => 274),
                array('income' => 28860, 'rate' => 7.4, 'constant' => 511),
                array('income' => 28860, 'rate' => 7.8, 'constant' => 1749),
            ),
            20 => array(
                array('income' => 7900, 'rate' => 0, 'constant' => 0),
                array('income' => 10576, 'rate' => 1.6, 'constant' => 0),
                array('income' => 13252, 'rate' => 3.6, 'constant' => 43),
                array('income' => 15928, 'rate' => 4.1, 'constant' => 139),
                array('income' => 18604, 'rate' => 5.1, 'constant' => 249),
                array('income' => 21280, 'rate' => 6.1, 'constant' => 385),
                array('income' => 27970, 'rate' => 7.1, 'constant' => 548),
                array('income' => 61420, 'rate' => 7.4, 'constant' => 1023),
                array('income' => 61420, 'rate' => 7.8, 'constant' => 3498),
            ),
        ),
        20090101 => array(
            10 => array(
                array('income' => 1950, 'rate' => 0, 'constant' => 0),
                array('income' => 3222, 'rate' => 1.6, 'constant' => 0),
                array('income' => 4494, 'rate' => 3.6, 'constant' => 20),
                array('income' => 5766, 'rate' => 4.1, 'constant' => 66),
                array('income' => 7038, 'rate' => 5.1, 'constant' => 118),
                array('income' => 8310, 'rate' => 6.1, 'constant' => 183),
                array('income' => 11490, 'rate' => 7.1, 'constant' => 261),
                array('income' => 27391, 'rate' => 7.4, 'constant' => 487),
                array('income' => 27391, 'rate' => 7.8, 'constant' => 1664),
            ),
            20 => array(
                array('income' => 7400, 'rate' => 0, 'constant' => 0),
                array('income' => 9944, 'rate' => 1.6, 'constant' => 0),
                array('income' => 12488, 'rate' => 3.6, 'constant' => 41),
                array('income' => 15032, 'rate' => 4.1, 'constant' => 133),
                array('income' => 17576, 'rate' => 5.1, 'constant' => 237),
                array('income' => 20120, 'rate' => 6.1, 'constant' => 367),
                array('income' => 26480, 'rate' => 7.1, 'constant' => 522),
                array('income' => 58282, 'rate' => 7.4, 'constant' => 974),
                array('income' => 58282, 'rate' => 7.8, 'constant' => 3327),
            ),
        ),
        20060101 => array(
            10 => array(
                array('income' => 1800, 'rate' => 0, 'constant' => 0),
                array('income' => 2959, 'rate' => 1.6, 'constant' => 0),
                array('income' => 4118, 'rate' => 3.6, 'constant' => 19),
                array('income' => 5277, 'rate' => 4.1, 'constant' => 61),
                array('income' => 6436, 'rate' => 5.1, 'constant' => 109),
                array('income' => 7594, 'rate' => 6.1, 'constant' => 168),
                array('income' => 10492, 'rate' => 7.1, 'constant' => 239),
                array('income' => 24978, 'rate' => 7.4, 'constant' => 445),
                array('income' => 24978, 'rate' => 7.8, 'constant' => 1517),
            ),
            20 => array(
                array('income' => 6800, 'rate' => 0, 'constant' => 0),
                array('income' => 9118, 'rate' => 1.6, 'constant' => 0),
                array('income' => 11436, 'rate' => 3.6, 'constant' => 37),
                array('income' => 13754, 'rate' => 4.1, 'constant' => 120),
                array('income' => 16072, 'rate' => 5.1, 'constant' => 215),
                array('income' => 18388, 'rate' => 6.1, 'constant' => 333),
                array('income' => 24184, 'rate' => 7.1, 'constant' => 474),
                array('income' => 53156, 'rate' => 7.4, 'constant' => 886),
                array('income' => 53156, 'rate' => 7.8, 'constant' => 3030),
            ),
        ),
    );

    public $state_options = array(
        20160101 => array( //01-Jan-2016 (Guide updated June 2016, but it was retroactive.)
            'allowance' => 4050
        ),
        20140601 => array( //01-Jun-2014
            'allowance' => 3950
        ),
        20130521 => array( //21-May-2013
            'allowance' => 3900
        ),
        20130101 => array( //01-Jan-2013
            'allowance' => 3800
        ),
        20120101 => array( //01-Jan-2009
            'allowance' => 3700
        ),
        20090101 => array( //01-Jan-2009
            'allowance' => 3500
        ),
        20060101 => array( //01-Jan-2006
            'allowance' => 3200
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
        $state_allowance = $this->getStateAllowanceAmount();

        $income = bcsub($annual_income, $state_allowance);

        Debug::text('State Annual Taxable Income: ' . $income, __FILE__, __LINE__, __METHOD__, 10);

        return $income;
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
