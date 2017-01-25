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
class PayrollDeduction_US_IN_ALL extends PayrollDeduction_US_IN
{
    public function getDistrictTaxPayable()
    {
        $annual_income = $this->getDistrictAnnualTaxableIncome();

        $retval = 0;

        if ($annual_income > 0) {
            $rate = bcdiv($this->getUserValue3(), 100);

            $retval = bcmul($annual_income, $rate);
        }

        if ($retval < 0) {
            $retval = 0;
        }

        Debug::text('District Annual Tax Payable: ' . $retval, __FILE__, __LINE__, __METHOD__, 10);

        return $retval;
    }

    public function getDistrictAnnualTaxableIncome()
    {
        $annual_income = $this->getAnnualTaxableIncome();
        $state_allowance = $this->getStateAllowanceAmount();
        $state_dependant_allowance = $this->getStateDependantAllowanceAmount();

        $income = bcsub(bcsub($annual_income, $state_allowance), $state_dependant_allowance);

        Debug::text('District Annual Taxable Income: ' . $income, __FILE__, __LINE__, __METHOD__, 10);

        return $income;
    }
}
