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
class PayrollDeduction_US extends PayrollDeduction_US_Data
{
    //
    // Federal
    //
    public function setFederalFilingStatus($value)
    {
        //Check for invalid value, default to single if found.
        if ($value > 20) {
            $value = 10; //Single
        }
        $this->data['federal_filing_status'] = $value;

        return true;
    }

    public function getFederalFilingStatus()
    {
        if (isset($this->data['federal_filing_status']) and $this->data['federal_filing_status'] != '') {
            return $this->data['federal_filing_status'];
        }

        return 10; //Single
    }

    public function setFederalAllowance($value)
    {
        $this->data['federal_allowance'] = $value;

        return true;
    }

    public function setFederalAdditionalDeduction($value)
    {
        $this->data['federal_additional_deduction'] = $value;

        return true;
    }

    public function getFederalAdditionalDeduction()
    {
        if (isset($this->data['federal_additional_deduction'])) {
            return $this->data['federal_additional_deduction'];
        }

        return false;
    }

    public function setMedicareFilingStatus($value)
    {
        $this->data['medicare_filing_status'] = $value;

        return true;
    }

    public function setEICFilingStatus($value)
    {
        $this->data['eic_filing_status'] = $value;

        return true;
    }

    public function setYearToDateSocialSecurityContribution($value)
    {
        if ($value > 0) {
            $this->data['social_security_ytd_contribution'] = $value;

            return true;
        }

        return false;
    }

    public function setYearToDateFederalUIContribution($value)
    {
        if ($value > 0) {
            $this->data['federal_ui_ytd_contribution'] = $value;

            return true;
        }

        return false;
    }

    public function setFederalTaxExempt($value)
    {
        $this->data['federal_tax_exempt'] = $value;

        return true;
    }

    public function setStateFilingStatus($value)
    {
        $this->data['state_filing_status'] = $value;

        return true;
    }

    public function getStateFilingStatus()
    {
        if (isset($this->data['state_filing_status']) and $this->data['state_filing_status'] != '') {
            return $this->data['state_filing_status'];
        }

        return 10; //Single
    }

    public function setStateAllowance($value)
    {
        $this->data['state_allowance'] = (int)$value; //Don't allow fractions, like 1.5 allowances, as this can cause problems with rate lookups failing when its expecting 1 or 2, and it gets 1.5

        return true;
    }

    public function getStateAllowance()
    {
        if (isset($this->data['state_allowance'])) {
            return $this->data['state_allowance'];
        }

        return false;
    }

    public function setStateAdditionalDeduction($value)
    {
        $this->data['state_additional_deduction'] = $value;

        return true;
    }

    public function getStateAdditionalDeduction()
    {
        if (isset($this->data['state_additional_deduction'])) {
            return $this->data['state_additional_deduction'];
        }

        return false;
    }

    //
    // State
    //

    public function getDistrictPayPeriodDeductions()
    {
        if ($this->getFormulaType() == 20) {
            Debug::text('Formula Type: ' . $this->getFormulaType() . ' YTD Payable: ' . $this->getDistrictTaxPayable() . ' YTD Paid: ' . $this->getYearToDateDeduction() . ' Current PP: ' . $this->getCurrentPayPeriod(), __FILE__, __LINE__, __METHOD__, 10);
            $retval = $this->calcNonPeriodicDeduction($this->getDistrictTaxPayable(), $this->getYearToDateDeduction());
        } else {
            $retval = bcdiv($this->getDistrictTaxPayable(), $this->getAnnualPayPeriods());
        }

        Debug::text('District Pay Period Deductions: ' . $retval, __FILE__, __LINE__, __METHOD__, 10);
        return $retval;
    }

    public function getDistrictTaxPayable()
    {
        $annual_income = $this->getDistrictAnnualTaxableIncome();

        if ($annual_income > 0) {
            $rate = bcdiv($this->getUserValue2(), 100);
            $retval = bcmul($annual_income, $rate);
        }

        if (!isset($retval) or $retval < 0) {
            $retval = 0;
        }

        Debug::text('zzDistrict Annual Tax Payable: ' . $retval . ' User Value 2: ' . $this->getUserValue2() . ' Annual Income: ' . $annual_income, __FILE__, __LINE__, __METHOD__, 10);

        return $retval;
    }

    public function getDistrictAnnualTaxableIncome()
    {
        $annual_income = $this->getAnnualTaxableIncome();

        return $annual_income;
    }

    public function getAnnualTaxableIncome()
    {
        if ($this->getFormulaType() == 20) {
            Debug::text('Formula Type: ' . $this->getFormulaType() . ' YTD Gross: ' . $this->getYearToDateGrossIncome() . ' This Gross: ' . $this->getGrossPayPeriodIncome() . ' Current PP: ' . $this->getCurrentPayPeriod(), __FILE__, __LINE__, __METHOD__, 10);
            $retval = $this->calcNonPeriodicIncome($this->getYearToDateGrossIncome(), $this->getGrossPayPeriodIncome());
        } else {
            $retval = bcmul($this->getGrossPayPeriodIncome(), $this->getAnnualPayPeriods());
        }
        Debug::text('Annual Taxable Income: ' . $retval, __FILE__, __LINE__, __METHOD__, 10);

        return $retval;
    }

    public function setDistrictFilingStatus($value)
    {
        $this->data['district_filing_status'] = $value;

        return true;
    }

    public function getDistrictFilingStatus()
    {
        if (isset($this->data['district_filing_status'])) {
            return $this->data['district_filing_status'];
        }

        return 10; //Single
    }

    //Default to 0 unless otherwise defined in a State specific class.

    public function setDistrictAllowance($value)
    {
        $this->data['district_allowance'] = $value;

        return true;
    }

    public function getDistrictAllowance()
    {
        if (isset($this->data['district_allowance'])) {
            return $this->data['district_allowance'];
        }

        return false;
    }

    public function setYearToDateStateUIContribution($value)
    {
        if ($value > 0) {
            $this->data['state_ui_ytd_contribution'] = $value;

            return true;
        }

        return false;
    }

    //
    // District
    //

    //Generic district functions that handle straight percentages for any district unless otherwise overloaded.
    //for custom formulas.

    public function getYearToDateStateUIContribution()
    {
        if (isset($this->data['state_ui_ytd_contribution'])) {
            return $this->data['state_ui_ytd_contribution'];
        }

        return 0;
    }

    public function setStateUIRate($value)
    {
        if ($value > 0) {
            $this->data['state_ui_rate'] = $value;

            return true;
        }

        return false;
    }

    public function getStateUIRate()
    {
        if (isset($this->data['state_ui_rate'])) {
            return $this->data['state_ui_rate'];
        }

        return 0;
    }

    public function setStateUIWageBase($value)
    {
        if ($value > 0) {
            $this->data['state_ui_wage_base'] = $value;

            return true;
        }

        return false;
    }

    public function getStateUIWageBase()
    {
        if (isset($this->data['state_ui_wage_base'])) {
            return $this->data['state_ui_wage_base'];
        }

        return 0;
    }

    public function setProvincialTaxExempt($value)
    {
        $this->data['provincial_tax_exempt'] = $value;

        return true;
    }

    public function getProvincialTaxExempt()
    {
        if (isset($this->data['provincial_tax_exempt'])) {
            return $this->data['provincial_tax_exempt'];
        }

        return false;
    }

    public function setSocialSecurityExempt($value)
    {
        $this->data['social_security_exempt'] = $value;

        return true;
    }

    public function setMedicareExempt($value)
    {
        $this->data['medicare_exempt'] = $value;

        return true;
    }

    public function setUIExempt($value)
    {
        $this->data['ui_exempt'] = $value;

        return true;
    }

    public function getAnnualEmployeeSocialSecurity()
    {
        if ($this->getSocialSecurityExempt() == true) {
            return 0;
        }

        $annual_income = $this->getAnnualTaxableIncome();
        $rate = bcdiv($this->getSocialSecurityRate(), 100);
        $maximum_contribution = $this->getSocialSecurityMaximumContribution();

        Debug::text('Rate: ' . $rate . ' Maximum Contribution: ' . $maximum_contribution, __FILE__, __LINE__, __METHOD__, 10);

        $amount = bcmul($annual_income, $rate);
        $max_amount = $maximum_contribution;

        if ($amount > $max_amount) {
            $retval = $max_amount;
        } else {
            $retval = $amount;
        }

        if ($retval < 0) {
            $retval = 0;
        }

        return $retval;
    }

    public function getSocialSecurityExempt()
    {
        if (isset($this->data['social_security_exempt'])) {
            return $this->data['social_security_exempt'];
        }

        return false;
    }

    public function getEmployerSocialSecurity()
    {
        if ($this->getSocialSecurityExempt() == true) {
            return 0;
        }

        $type = 'employer';

        $pay_period_income = $this->getGrossPayPeriodIncome();
        $rate = bcdiv($this->getSocialSecurityRate($type), 100);
        $maximum_contribution = $this->getSocialSecurityMaximumContribution($type);
        $ytd_contribution = $this->getYearToDateSocialSecurityContribution();

        Debug::text('Rate: ' . $rate . ' YTD Contribution: ' . $ytd_contribution, __FILE__, __LINE__, __METHOD__, 10);

        $amount = bcmul($pay_period_income, $rate);
        $max_amount = bcsub($maximum_contribution, $ytd_contribution);

        if ($amount > $max_amount) {
            $retval = $max_amount;
        } else {
            $retval = $amount;
        }

        if ($retval < 0) {
            $retval = 0;
        }

        return $retval;
    }

    public function getYearToDateSocialSecurityContribution()
    {
        if (isset($this->data['social_security_ytd_contribution'])) {
            return $this->data['social_security_ytd_contribution'];
        }

        return 0;
    }

    public function getAnnualEmployeeMedicare()
    {
        return bcmul($this->getEmployeeMedicare(), $this->getAnnualPayPeriods());
    }

    public function getEmployeeMedicare()
    {
        if ($this->getMedicareExempt() == true) {
            return 0;
        }

        $pay_period_income = $this->getGrossPayPeriodIncome();

        $rate_data = $this->getMedicareRate();
        $rate = bcdiv($rate_data['employee_rate'], 100);
        Debug::text('Rate: ' . $rate, __FILE__, __LINE__, __METHOD__, 10);

        $amount = bcmul($pay_period_income, $rate);
        Debug::text('Amount: ' . $amount, __FILE__, __LINE__, __METHOD__, 10);

        $threshold_income = (isset($rate_data['employee_threshold'][$this->getMedicareFilingStatus()])) ? $rate_data['employee_threshold'][$this->getMedicareFilingStatus()] : $rate_data['employee_threshold'][10]; //Default to single.
        Debug::text('Threshold Income: ' . $threshold_income, __FILE__, __LINE__, __METHOD__, 10);
        if ($threshold_income > 0 and ($this->getYearToDateGrossIncome() + $this->getGrossPayPeriodIncome()) > $threshold_income) {
            if ($this->getYearToDateGrossIncome() < $threshold_income) {
                $threshold_income = bcsub(bcadd($this->getYearToDateGrossIncome(), $this->getGrossPayPeriodIncome()), $threshold_income);
            } else {
                $threshold_income = $pay_period_income;
            }
            Debug::text('bThreshold Income: ' . $threshold_income, __FILE__, __LINE__, __METHOD__, 10);
            $threshold_amount = bcmul($threshold_income, bcdiv($rate_data['employee_threshold_rate'], 100));
            Debug::text('Threshold Amount: ' . $threshold_amount, __FILE__, __LINE__, __METHOD__, 10);
            $amount = bcadd($amount, $threshold_amount);
        }

        return $amount;
    }

    public function getMedicareExempt()
    {
        if (isset($this->data['medicare_exempt'])) {
            return $this->data['medicare_exempt'];
        }

        return false;
    }

    public function getMedicareFilingStatus()
    {
        if (isset($this->data['medicare_filing_status'])) {
            return $this->data['medicare_filing_status'];
        }

        return false;
    }

    public function getEmployerMedicare()
    {
        //return $this->getEmployeeMedicare();
        if ($this->getMedicareExempt() == true) {
            return 0;
        }

        $pay_period_income = $this->getGrossPayPeriodIncome();

        $rate_data = $this->getMedicareRate();
        $rate = bcdiv($rate_data['employer_rate'], 100);
        Debug::text('Rate: ' . $rate, __FILE__, __LINE__, __METHOD__, 10);

        $amount = bcmul($pay_period_income, $rate);

        return $amount;
    }

    public function getPayPeriodEmployeeNetPay()
    {
        return bcsub($this->getGrossPayPeriodIncome(), $this->getPayPeriodEmployeeTotalDeductions());
    }

    public function getPayPeriodEmployeeTotalDeductions()
    {
        //return $this->getPayPeriodTaxDeductions() + $this->getEmployeeCPP() + $this->getEmployeeEI();
        return bcadd(bcadd($this->getPayPeriodTaxDeductions(), $this->getEmployeeSocialSecurity()), $this->getEmployeeMedicare());
    }

    //
    // Calculation Functions
    //

    public function getPayPeriodTaxDeductions()
    {
        return bcadd($this->getFederalPayPeriodDeductions(), $this->getStatePayPeriodDeductions());
    }

    //
    // Federal Tax
    //

    public function getFederalPayPeriodDeductions()
    {
        if ($this->getFormulaType() == 20) {
            Debug::text('Formula Type: ' . $this->getFormulaType() . ' YTD Payable: ' . $this->getFederalTaxPayable() . ' YTD Paid: ' . $this->getYearToDateDeduction() . ' Current PP: ' . $this->getCurrentPayPeriod(), __FILE__, __LINE__, __METHOD__, 10);
            $retval = $this->calcNonPeriodicDeduction($this->getFederalTaxPayable(), $this->getYearToDateDeduction());
        } else {
            $retval = bcdiv($this->getFederalTaxPayable(), $this->getAnnualPayPeriods());
        }

        Debug::text('Federal Pay Period Deductions: ' . $retval, __FILE__, __LINE__, __METHOD__, 10);
        return $retval;
    }

    public function getFederalTaxPayable()
    {
        if ($this->getFederalTaxExempt() == true) {
            Debug::text('Federal Tax Exempt!', __FILE__, __LINE__, __METHOD__, 10);
            return 0;
        }

        $annual_taxable_income = $this->getAnnualTaxableIncome();
        $annual_allowance = bcmul($this->getFederalAllowanceAmount($this->getDate()), $this->getFederalAllowance());

        Debug::text('Annual Taxable Income: ' . $annual_taxable_income, __FILE__, __LINE__, __METHOD__, 10);
        Debug::text('Allowance: ' . $annual_allowance, __FILE__, __LINE__, __METHOD__, 10);

        if ($annual_taxable_income > $annual_allowance) {
            $modified_annual_taxable_income = bcsub($annual_taxable_income, $annual_allowance);
            $rate = $this->getData()->getFederalRate($modified_annual_taxable_income);
            $federal_constant = $this->getData()->getFederalConstant($modified_annual_taxable_income);
            $federal_rate_income = $this->getData()->getFederalRatePreviousIncome($modified_annual_taxable_income);

            $retval = bcadd(bcmul(bcsub($modified_annual_taxable_income, $federal_rate_income), $rate), $federal_constant);
        } else {
            Debug::text('Income is less then allowance: ', __FILE__, __LINE__, __METHOD__, 10);

            $retval = 0;
        }

        if ($retval < 0) {
            $retval = 0;
        }

        Debug::text('RetVal: ' . $retval, __FILE__, __LINE__, __METHOD__, 10);

        return $retval;
    }

    //
    // Social Security
    //

    public function getFederalTaxExempt()
    {
        if (isset($this->data['federal_tax_exempt'])) {
            return $this->data['federal_tax_exempt'];
        }

        return false;
    }

    public function getFederalAllowance()
    {
        if (isset($this->data['federal_allowance'])) {
            return $this->data['federal_allowance'];
        }

        return false;
    }

    public function getStatePayPeriodDeductions()
    {
        if ($this->getFormulaType() == 20) {
            Debug::text('Formula Type: ' . $this->getFormulaType() . ' YTD Payable: ' . $this->getStateTaxPayable() . ' YTD Paid: ' . $this->getYearToDateDeduction() . ' Current PP: ' . $this->getCurrentPayPeriod(), __FILE__, __LINE__, __METHOD__, 10);
            $retval = $this->calcNonPeriodicDeduction($this->getStateTaxPayable(), $this->getYearToDateDeduction());
        } else {
            $retval = bcdiv($this->getStateTaxPayable(), $this->getAnnualPayPeriods());
        }

        Debug::text('State Pay Period Deductions: ' . $retval, __FILE__, __LINE__, __METHOD__, 10);
        return $this->getStatePayPeriodDeductionRoundedValue($retval);
    }


    //
    // Medicare
    //

    public function getStateTaxPayable()
    {
        return 0;
    }

    public function getStatePayPeriodDeductionRoundedValue($amount)
    {
        return $amount;
    }

    public function getEmployeeSocialSecurity()
    {
        if ($this->getSocialSecurityExempt() == true) {
            return 0;
        }

        $type = 'employee';

        $pay_period_income = $this->getGrossPayPeriodIncome();
        $rate = bcdiv($this->getSocialSecurityRate($type), 100);
        $maximum_contribution = $this->getSocialSecurityMaximumContribution($type);
        $ytd_contribution = $this->getYearToDateSocialSecurityContribution();

        Debug::text('Rate: ' . $rate . ' YTD Contribution: ' . $ytd_contribution, __FILE__, __LINE__, __METHOD__, 10);

        $amount = bcmul($pay_period_income, $rate);
        $max_amount = bcsub($maximum_contribution, $ytd_contribution);

        if ($amount > $max_amount) {
            $retval = $max_amount;
        } else {
            $retval = $amount;
        }

        if ($retval < 0) {
            $retval = 0;
        }

        return $retval;
    }

    //
    // Federal UI
    //

    public function RoundNearestDollar($amount)
    {
        return round($amount, 0);
    }

    public function getEIC()
    {
        if ($this->getDate() <= 20101231) { //Repealed as of 31-Dec-2010.
            $eic_options = $this->getEICRateArray($this->getAnnualTaxableIncome(), $this->getEICFilingStatus());
            //Debug::Arr($eic_options, ' EIC Options: ', __FILE__, __LINE__, __METHOD__, 10);

            if (is_array($eic_options) and isset($eic_options['calculation_type'])) {
                $retval = 0;
                switch ($eic_options['calculation_type']) {
                    case 10: //Percent
                        if (isset($eic_options['percent'])) {
                            $retval = bcmul(bcdiv($eic_options['percent'], 100), $this->getAnnualTaxableIncome());
                        }
                        break;
                    case 20: //Amount
                        if (isset($eic_options['amount'])) {
                            $retval = $eic_options['amount'];
                        }
                        break;
                    case 30: //Amount less percent
                        if (isset($eic_options['percent']) and isset($eic_options['amount']) and isset($eic_options['income'])) {
                            $retval = bcsub($eic_options['amount'], bcmul(bcdiv($eic_options['percent'], 100), bcsub($this->getAnnualTaxableIncome(), $eic_options['income'])));
                        }
                        break;
                }
                Debug::Text(' Type: ' . $eic_options['calculation_type'] . ' Annual Amount: ' . $retval, __FILE__, __LINE__, __METHOD__, 10);

                if (isset($retval) and $retval > 0) {
                    $retval = bcdiv($retval, $this->getAnnualPayPeriods());

                    Debug::Text('EIC Amount: ' . $retval, __FILE__, __LINE__, __METHOD__, 10);
                    return $retval * -1;
                } else {
                    Debug::Text('Calculation didnt return valid amount...', __FILE__, __LINE__, __METHOD__, 10);
                }
            }
        } else {
            Debug::Text('EIC has been repealed for dates after 31-Dec-2010: ' . $this->getDate(), __FILE__, __LINE__, __METHOD__, 10);
        }

        return 0;
    }

    public function getEICFilingStatus()
    {
        if (isset($this->data['eic_filing_status'])) {
            return $this->data['eic_filing_status'];
        }

        return false;
    }

    public function getArray()
    {
        $array = array(
            'gross_pay' => $this->getGrossPayPeriodIncome(),
            'federal_tax' => $this->getFederalPayPeriodDeductions(),
            'state_tax' => $this->getStatePayPeriodDeductions(),
            /*
                                    'employee_social_security' => $this->getEmployeeSocialSecurity(),
                                    'employer_social_security' => $this->getEmployeeSocialSecurity(),
                                    'employee_medicare' => $this->getEmployeeMedicare(),
                                    'employer_medicare' => $this->getEmployerMedicare(),
            */
            'employee_social_security' => $this->getEmployeeSocialSecurity(),
            'federal_employer_ui' => $this->getFederalEmployerUI(),
//						'state_employer_ui' => $this->getStateEmployerUI(),

        );

        Debug::Arr($array, 'Deductions Array:', __FILE__, __LINE__, __METHOD__, 10);

        return $array;
    }

    public function getFederalEmployerUI()
    {
        if ($this->getUIExempt() == true) {
            return 0;
        }

        $pay_period_income = $this->getGrossPayPeriodIncome();
        $rate = bcdiv($this->getFederalUIRate(), 100);
        $maximum_contribution = $this->getFederalUIMaximumContribution();
        $ytd_contribution = $this->getYearToDateFederalUIContribution();

        Debug::text('Rate: ' . $rate . ' YTD Contribution: ' . $ytd_contribution . ' Maximum: ' . $maximum_contribution, __FILE__, __LINE__, __METHOD__, 10);

        $amount = bcmul($pay_period_income, $rate);
        $max_amount = bcsub($maximum_contribution, $ytd_contribution);

        if ($amount > $max_amount) {
            $retval = $max_amount;
        } else {
            $retval = $amount;
        }

        return $retval;
    }

    //
    // Earning Income Tax Credit (EIC, EITC). - Repealed as of 31-Dec-2010.
    //

    public function getUIExempt()
    {
        if (isset($this->data['ui_exempt'])) {
            return $this->data['ui_exempt'];
        }

        return false;
    }

    /*
        Use this to get all useful values.
    */

    public function getYearToDateFederalUIContribution()
    {
        if (isset($this->data['federal_ui_ytd_contribution'])) {
            return $this->data['federal_ui_ytd_contribution'];
        }

        return 0;
    }
}
