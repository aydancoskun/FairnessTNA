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
class PayrollDeduction_US_RI extends PayrollDeduction_US
{
    public $state_income_tax_rate_options = array(
        20170101 => array(
            10 => array(
                array('income' => 61300, 'rate' => 3.75, 'constant' => 0),
                array('income' => 139400, 'rate' => 4.75, 'constant' => 2298.75),
                array('income' => 139400, 'rate' => 5.99, 'constant' => 6008.50),
            ),
            20 => array(
                array('income' => 61300, 'rate' => 3.75, 'constant' => 0),
                array('income' => 139400, 'rate' => 4.75, 'constant' => 2298.75),
                array('income' => 139400, 'rate' => 5.99, 'constant' => 6008.50),
            ),
        ),
        20160101 => array(
            10 => array(
                array('income' => 60850, 'rate' => 3.75, 'constant' => 0),
                array('income' => 138300, 'rate' => 4.75, 'constant' => 2281.88),
                array('income' => 138300, 'rate' => 5.99, 'constant' => 5960.75),
            ),
            20 => array(
                array('income' => 60850, 'rate' => 3.75, 'constant' => 0),
                array('income' => 138300, 'rate' => 4.75, 'constant' => 2281.88),
                array('income' => 138300, 'rate' => 5.99, 'constant' => 5960.75),
            ),
        ),
        20150101 => array(
            10 => array(
                array('income' => 60000, 'rate' => 3.75, 'constant' => 0),
                array('income' => 137650, 'rate' => 4.75, 'constant' => 2270.63),
                array('income' => 137650, 'rate' => 5.99, 'constant' => 5932.88),
            ),
            20 => array(
                array('income' => 60000, 'rate' => 3.75, 'constant' => 0),
                array('income' => 137650, 'rate' => 4.75, 'constant' => 2270.63),
                array('income' => 137650, 'rate' => 5.99, 'constant' => 5932.88),
            ),
        ),
        20140101 => array(
            10 => array(
                array('income' => 59600, 'rate' => 3.75, 'constant' => 0),
                array('income' => 135500, 'rate' => 4.75, 'constant' => 2235.00),
                array('income' => 135500, 'rate' => 5.99, 'constant' => 5840.25),
            ),
            20 => array(
                array('income' => 59600, 'rate' => 3.75, 'constant' => 0),
                array('income' => 135000, 'rate' => 4.75, 'constant' => 2235.00),
                array('income' => 135000, 'rate' => 5.99, 'constant' => 5840.25),
            ),
        ),
        20130101 => array(
            10 => array(
                array('income' => 58600, 'rate' => 3.75, 'constant' => 0),
                array('income' => 133250, 'rate' => 4.75, 'constant' => 2197.50),
                array('income' => 133250, 'rate' => 5.99, 'constant' => 5743.38),
            ),
            20 => array(
                array('income' => 58600, 'rate' => 3.75, 'constant' => 0),
                array('income' => 133250, 'rate' => 4.75, 'constant' => 2197.50),
                array('income' => 133250, 'rate' => 5.99, 'constant' => 5743.38),
            ),
        ),
        20120101 => array(
            10 => array(
                array('income' => 57150, 'rate' => 3.75, 'constant' => 0),
                array('income' => 129900, 'rate' => 4.75, 'constant' => 2143.13),
                array('income' => 129900, 'rate' => 5.99, 'constant' => 5598.75),
            ),
            20 => array(
                array('income' => 57150, 'rate' => 3.75, 'constant' => 0),
                array('income' => 129900, 'rate' => 4.75, 'constant' => 2143.13),
                array('income' => 129900, 'rate' => 5.99, 'constant' => 5598.75),
            ),
        ),
        20110101 => array(
            10 => array(
                array('income' => 55000, 'rate' => 3.75, 'constant' => 0),
                array('income' => 125000, 'rate' => 4.75, 'constant' => 2063),
                array('income' => 125000, 'rate' => 5.99, 'constant' => 5388),
            ),
            20 => array(
                array('income' => 55000, 'rate' => 3.75, 'constant' => 0),
                array('income' => 125000, 'rate' => 4.75, 'constant' => 2063),
                array('income' => 125000, 'rate' => 5.99, 'constant' => 5388),
            ),
        ),
        20100101 => array(
            10 => array(
                array('income' => 2650, 'rate' => 0, 'constant' => 0),
                array('income' => 36050, 'rate' => 3.75, 'constant' => 0),
                array('income' => 78850, 'rate' => 7.0, 'constant' => 1252.50),
                array('income' => 173900, 'rate' => 7.75, 'constant' => 4248.50),
                array('income' => 375650, 'rate' => 9.0, 'constant' => 11614.88),
                array('income' => 375650, 'rate' => 9.9, 'constant' => 29772.38),
            ),
            20 => array(
                array('income' => 6450, 'rate' => 0, 'constant' => 0),
                array('income' => 62700, 'rate' => 3.75, 'constant' => 0),
                array('income' => 133450, 'rate' => 7.0, 'constant' => 2109.38),
                array('income' => 215100, 'rate' => 7.75, 'constant' => 7061.88),
                array('income' => 379500, 'rate' => 9.0, 'constant' => 13389.75),
                array('income' => 379500, 'rate' => 9.9, 'constant' => 28185.75),
            ),
        ),
        20090101 => array(
            10 => array(
                array('income' => 2650, 'rate' => 0, 'constant' => 0),
                array('income' => 36000, 'rate' => 3.75, 'constant' => 0),
                array('income' => 78700, 'rate' => 7.0, 'constant' => 1250.63),
                array('income' => 173600, 'rate' => 7.75, 'constant' => 4239.63),
                array('income' => 374950, 'rate' => 9.0, 'constant' => 11594.38),
                array('income' => 374950, 'rate' => 9.9, 'constant' => 29715.88),
            ),
            20 => array(
                array('income' => 6450, 'rate' => 0, 'constant' => 0),
                array('income' => 62600, 'rate' => 3.75, 'constant' => 0),
                array('income' => 133200, 'rate' => 7.0, 'constant' => 2105.63),
                array('income' => 214700, 'rate' => 7.75, 'constant' => 7047.63),
                array('income' => 378800, 'rate' => 9.0, 'constant' => 13363.88),
                array('income' => 378800, 'rate' => 9.9, 'constant' => 28132.88),
            ),
        ),
        20080101 => array(
            10 => array(
                array('income' => 2650, 'rate' => 0, 'constant' => 0),
                array('income' => 34500, 'rate' => 3.75, 'constant' => 0),
                array('income' => 75500, 'rate' => 7.0, 'constant' => 1194.38),
                array('income' => 166500, 'rate' => 7.75, 'constant' => 4064.38),
                array('income' => 359650, 'rate' => 9.0, 'constant' => 11116.88),
                array('income' => 359650, 'rate' => 9.9, 'constant' => 28500.38),
            ),
            20 => array(
                array('income' => 6450, 'rate' => 0, 'constant' => 0),
                array('income' => 60000, 'rate' => 3.75, 'constant' => 0),
                array('income' => 127750, 'rate' => 7.0, 'constant' => 2008.13),
                array('income' => 205950, 'rate' => 7.75, 'constant' => 6750.63),
                array('income' => 363300, 'rate' => 9.0, 'constant' => 12811.13),
                array('income' => 363300, 'rate' => 9.9, 'constant' => 26972.63),
            ),
        ),
        20070101 => array(
            10 => array(
                array('income' => 2650, 'rate' => 0, 'constant' => 0),
                array('income' => 33520, 'rate' => 3.75, 'constant' => 0),
                array('income' => 77075, 'rate' => 7.0, 'constant' => 1157.63),
                array('income' => 162800, 'rate' => 7.75, 'constant' => 4206.48),
                array('income' => 351650, 'rate' => 9.0, 'constant' => 10850.17),
                array('income' => 351650, 'rate' => 9.9, 'constant' => 27846.67),
            ),
            20 => array(
                array('income' => 6450, 'rate' => 0, 'constant' => 0),
                array('income' => 58700, 'rate' => 3.75, 'constant' => 0),
                array('income' => 124900, 'rate' => 7.0, 'constant' => 1959.38),
                array('income' => 201300, 'rate' => 7.75, 'constant' => 6593.38),
                array('income' => 355200, 'rate' => 9.0, 'constant' => 12514.38),
                array('income' => 355200, 'rate' => 9.9, 'constant' => 26365.38),
            ),
        ),
        20060625 => array(
            10 => array(
                array('income' => 2650, 'rate' => 0, 'constant' => 0),
                array('income' => 32240, 'rate' => 3.75, 'constant' => 0),
                array('income' => 73250, 'rate' => 7.0, 'constant' => 1109.63),
                array('income' => 156650, 'rate' => 7.75, 'constant' => 3980.33),
                array('income' => 338400, 'rate' => 9.0, 'constant' => 10443.83),
                array('income' => 338400, 'rate' => 9.9, 'constant' => 26801.33),
            ),
            20 => array(
                array('income' => 6450, 'rate' => 0, 'constant' => 0),
                array('income' => 56500, 'rate' => 3.75, 'constant' => 0),
                array('income' => 120200, 'rate' => 7.0, 'constant' => 1876.88),
                array('income' => 193750, 'rate' => 7.75, 'constant' => 6335.88),
                array('income' => 341850, 'rate' => 9.0, 'constant' => 12036.01),
                array('income' => 341850, 'rate' => 9.9, 'constant' => 25365.01),
            ),
        ),
        20060101 => array(
            10 => array(
                array('income' => 2650, 'rate' => 0, 'constant' => 0),
                array('income' => 31500, 'rate' => 3.75, 'constant' => 0),
                array('income' => 69750, 'rate' => 7.0, 'constant' => 1081.88),
                array('income' => 151950, 'rate' => 7.75, 'constant' => 3759.38),
                array('income' => 328250, 'rate' => 9.0, 'constant' => 10129.88),
                array('income' => 328250, 'rate' => 9.9, 'constant' => 25996.88),
            ),
            20 => array(
                array('income' => 6450, 'rate' => 0, 'constant' => 0),
                array('income' => 54750, 'rate' => 3.75, 'constant' => 0),
                array('income' => 116600, 'rate' => 7.0, 'constant' => 1811.25),
                array('income' => 187900, 'rate' => 7.75, 'constant' => 6140.75),
                array('income' => 331500, 'rate' => 9.0, 'constant' => 11666.50),
                array('income' => 331500, 'rate' => 9.9, 'constant' => 24590.50),
            ),
        ),
    );

    public $state_options = array(
        20170101 => array( //01-Jan-17
            'allowance' => 1000,
            'allowance_threshold' => 217350, //If annual income more than this, allowance is 0.
        ),
        //01-Jan-12: No Change
        20110101 => array( //01-Jan-11
            'allowance' => 1000,
        ),
        //01-Jan-10: No Change
        20090101 => array( //01-Jan-09
            'allowance' => 3650
        ),
        20080101 => array(
            'allowance' => 3500
        ),
        20070101 => array(
            'allowance' => 3400
        ),
        20060101 => array(
            'allowance' => 3200
        ),
        20060625 => array(
            'allowance' => 3300
        ),
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

        $annual_income = $this->getAnnualTaxableIncome();

        $allowance_arr = $retarr['allowance'];
        $retval = bcmul($this->getStateAllowance(), $allowance_arr);

        if (isset($retarr['allowance_threshold']) and $annual_income > $retarr['allowance_threshold']) {
            Debug::text('Annual income exceeds threshold, setting allowance amount to 0 from: ' . $retval, __FILE__, __LINE__, __METHOD__, 10);
            $retval = 0;
        }
        Debug::text('State Allowance Amount: ' . $retval, __FILE__, __LINE__, __METHOD__, 10);

        return $retval;
    }
}
