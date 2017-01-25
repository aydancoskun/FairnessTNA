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
class PayrollDeduction_US_NM extends PayrollDeduction_US
{
    public $state_income_tax_rate_options = array(
        20170101 => array(
            10 => array(
                array('income' => 2300, 'rate' => 0, 'constant' => 0),
                array('income' => 7800, 'rate' => 1.7, 'constant' => 0),
                array('income' => 13300, 'rate' => 3.2, 'constant' => 93.50),
                array('income' => 18300, 'rate' => 4.7, 'constant' => 269.50),
                array('income' => 18300, 'rate' => 4.9, 'constant' => 504.50),
            ),
            20 => array(
                array('income' => 8650, 'rate' => 0, 'constant' => 0),
                array('income' => 16650, 'rate' => 1.7, 'constant' => 0),
                array('income' => 24650, 'rate' => 3.2, 'constant' => 136),
                array('income' => 32650, 'rate' => 4.7, 'constant' => 392),
                array('income' => 32650, 'rate' => 4.9, 'constant' => 768),
            ),
        ),
        20160101 => array(
            10 => array(
                array('income' => 2250, 'rate' => 0, 'constant' => 0),
                array('income' => 7750, 'rate' => 1.7, 'constant' => 0),
                array('income' => 13250, 'rate' => 3.2, 'constant' => 93.50),
                array('income' => 18250, 'rate' => 4.7, 'constant' => 269.50),
                array('income' => 18250, 'rate' => 4.9, 'constant' => 504.50),
            ),
            20 => array(
                array('income' => 8550, 'rate' => 0, 'constant' => 0),
                array('income' => 16550, 'rate' => 1.7, 'constant' => 0),
                array('income' => 24550, 'rate' => 3.2, 'constant' => 136),
                array('income' => 32550, 'rate' => 4.7, 'constant' => 392),
                array('income' => 32550, 'rate' => 4.9, 'constant' => 768),
            ),
        ),
        20150101 => array(
            10 => array(
                array('income' => 2300, 'rate' => 0, 'constant' => 0),
                array('income' => 7800, 'rate' => 1.7, 'constant' => 0),
                array('income' => 13300, 'rate' => 3.2, 'constant' => 93.50),
                array('income' => 18300, 'rate' => 4.7, 'constant' => 269.50),
                array('income' => 18300, 'rate' => 4.9, 'constant' => 504.50),
            ),
            20 => array(
                array('income' => 8600, 'rate' => 0, 'constant' => 0),
                array('income' => 16600, 'rate' => 1.7, 'constant' => 0),
                array('income' => 24600, 'rate' => 3.2, 'constant' => 136),
                array('income' => 32600, 'rate' => 4.7, 'constant' => 392),
                array('income' => 32600, 'rate' => 4.9, 'constant' => 768),
            ),
        ),
        20140101 => array(
            10 => array(
                array('income' => 2250, 'rate' => 0, 'constant' => 0),
                array('income' => 7750, 'rate' => 1.7, 'constant' => 0),
                array('income' => 13250, 'rate' => 3.2, 'constant' => 93.50),
                array('income' => 18250, 'rate' => 4.7, 'constant' => 269.50),
                array('income' => 18250, 'rate' => 4.9, 'constant' => 504.50),
            ),
            20 => array(
                array('income' => 8450, 'rate' => 0, 'constant' => 0),
                array('income' => 16450, 'rate' => 1.7, 'constant' => 0),
                array('income' => 24450, 'rate' => 3.2, 'constant' => 136),
                array('income' => 32450, 'rate' => 4.7, 'constant' => 392),
                array('income' => 32450, 'rate' => 4.9, 'constant' => 768),
            ),
        ),
        20130101 => array(
            10 => array(
                array('income' => 2200, 'rate' => 0, 'constant' => 0),
                array('income' => 7700, 'rate' => 1.7, 'constant' => 0),
                array('income' => 13200, 'rate' => 3.2, 'constant' => 93.50),
                array('income' => 18200, 'rate' => 4.7, 'constant' => 269.50),
                array('income' => 18200, 'rate' => 4.9, 'constant' => 504.50),
            ),
            20 => array(
                array('income' => 8300, 'rate' => 0, 'constant' => 0),
                array('income' => 16300, 'rate' => 1.7, 'constant' => 0),
                array('income' => 24300, 'rate' => 3.2, 'constant' => 136),
                array('income' => 32300, 'rate' => 4.7, 'constant' => 392),
                array('income' => 32300, 'rate' => 4.9, 'constant' => 768),
            ),
        ),
        20120101 => array(
            10 => array(
                array('income' => 2150, 'rate' => 0, 'constant' => 0),
                array('income' => 7650, 'rate' => 1.7, 'constant' => 0),
                array('income' => 13150, 'rate' => 3.2, 'constant' => 93.50),
                array('income' => 18150, 'rate' => 4.7, 'constant' => 269.50),
                array('income' => 18150, 'rate' => 4.9, 'constant' => 504.50),
            ),
            20 => array(
                array('income' => 8100, 'rate' => 0, 'constant' => 0),
                array('income' => 16100, 'rate' => 1.7, 'constant' => 0),
                array('income' => 24100, 'rate' => 3.2, 'constant' => 136),
                array('income' => 32100, 'rate' => 4.7, 'constant' => 392),
                array('income' => 32100, 'rate' => 4.9, 'constant' => 768),
            ),
        ),
        20090101 => array(
            10 => array(
                array('income' => 2050, 'rate' => 0, 'constant' => 0),
                array('income' => 7550, 'rate' => 1.7, 'constant' => 0),
                array('income' => 13050, 'rate' => 3.2, 'constant' => 93.50),
                array('income' => 18050, 'rate' => 4.7, 'constant' => 269.50),
                array('income' => 18050, 'rate' => 4.9, 'constant' => 504.50),
            ),
            20 => array(
                array('income' => 7750, 'rate' => 0, 'constant' => 0),
                array('income' => 15750, 'rate' => 1.7, 'constant' => 0),
                array('income' => 23750, 'rate' => 3.2, 'constant' => 136),
                array('income' => 31750, 'rate' => 4.7, 'constant' => 392),
                array('income' => 31750, 'rate' => 4.9, 'constant' => 768),
            ),
        ),
        20080101 => array(
            10 => array(
                array('income' => 1900, 'rate' => 0, 'constant' => 0),
                array('income' => 7400, 'rate' => 1.7, 'constant' => 0),
                array('income' => 12900, 'rate' => 3.2, 'constant' => 93.50),
                array('income' => 17900, 'rate' => 4.7, 'constant' => 269.50),
                array('income' => 17900, 'rate' => 4.9, 'constant' => 504.50),
            ),
            20 => array(
                array('income' => 7250, 'rate' => 0, 'constant' => 0),
                array('income' => 15250, 'rate' => 1.7, 'constant' => 0),
                array('income' => 23250, 'rate' => 3.2, 'constant' => 136),
                array('income' => 31250, 'rate' => 4.7, 'constant' => 392),
                array('income' => 31250, 'rate' => 4.9, 'constant' => 768),
            ),
        ),
        20070101 => array(
            10 => array(
                array('income' => 1900, 'rate' => 0, 'constant' => 0),
                array('income' => 7400, 'rate' => 1.7, 'constant' => 0),
                array('income' => 12900, 'rate' => 3.2, 'constant' => 93.50),
                array('income' => 17900, 'rate' => 4.7, 'constant' => 269.50),
                array('income' => 17900, 'rate' => 5.3, 'constant' => 504.50),
            ),
            20 => array(
                array('income' => 7250, 'rate' => 0, 'constant' => 0),
                array('income' => 15250, 'rate' => 1.7, 'constant' => 0),
                array('income' => 23250, 'rate' => 3.2, 'constant' => 136),
                array('income' => 31250, 'rate' => 4.7, 'constant' => 392),
                array('income' => 31250, 'rate' => 5.3, 'constant' => 768),
            ),
        ),
        20060101 => array(
            10 => array(
                array('income' => 1800, 'rate' => 0, 'constant' => 0),
                array('income' => 7300, 'rate' => 1.7, 'constant' => 0),
                array('income' => 12800, 'rate' => 3.2, 'constant' => 93.50),
                array('income' => 17800, 'rate' => 4.7, 'constant' => 269.50),
                array('income' => 17800, 'rate' => 5.3, 'constant' => 504.50),
            ),
            20 => array(
                array('income' => 6950, 'rate' => 0, 'constant' => 0),
                array('income' => 14950, 'rate' => 1.7, 'constant' => 0),
                array('income' => 22950, 'rate' => 3.2, 'constant' => 136),
                array('income' => 30950, 'rate' => 4.7, 'constant' => 392),
                array('income' => 30950, 'rate' => 5.3, 'constant' => 768),
            ),
        ),
    );

    public $state_options = array(
        //01-Jan-2017 - No Change
        20160101 => array( //01-Jan-2016
            'allowance' => 4050
        ),
        20150101 => array( //01-Jan-2015
            'allowance' => 4000
        ),
        20140101 => array( //01-Jan-2014
            'allowance' => 3950
        ),
        20130101 => array( //01-Jan-2013
            'allowance' => 3900
        ),
        20120101 => array( //01-Jan-2012
            'allowance' => 3800
        ),
        20090101 => array( //01-Jan-2009
            'allowance' => 3650
        ),
        20080101 => array(
            'allowance' => 3450
        ),
        20070101 => array(
            'allowance' => 3450
        ),
        20060101 => array(
            'allowance' => 3250
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
