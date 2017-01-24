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
 * @package Modules\Company
 */
class CompanyDeductionFactory extends Factory
{
    public $company_obj = null;
        public $pay_stub_entry_account_link_obj = null; //PK Sequence name
    public $pay_stub_entry_account_obj = null;
    public $lookback_pay_stub_lf = null;
    public $country_calculation_ids = array('100', '200', '300');
    public $province_calculation_ids = array('200', '300');
    public $district_calculation_ids = array('300');
    public $calculation_id_fields = array(
        '10' => '10',
        '15' => '15',
        '17' => '17',
        '18' => '18',
        '19' => '19',
        '20' => '20',
        '30' => '30',

        '52' => '52',
        '69' => '69',

        '80' => '80',
        '82' => '82',
        '83' => '83',
        '84' => '84',
        '85' => '85',

        '100' => '',
        '100-CA' => '100-CA',
        '100-US' => '100-US',
        '100-CR' => '100-CR',

        '200' => '',
        '200-CA-BC' => '200-CA',
        '200-CA-AB' => '200-CA',
        '200-CA-SK' => '200-CA',
        '200-CA-MB' => '200-CA',
        '200-CA-QC' => '200-CA',
        '200-CA-ON' => '200-CA',
        '200-CA-NL' => '200-CA',
        '200-CA-NB' => '200-CA',
        '200-CA-NS' => '200-CA',
        '200-CA-PE' => '200-CA',
        '200-CA-NT' => '200-CA',
        '200-CA-YT' => '200-CA',
        '200-CA-NU' => '200-CA',

        '200-US-AL' => '200-US-AL',
        '200-US-AK' => '',
        '200-US-AZ' => '200-US-AZ',
        '200-US-AR' => '200-US-OH',
        '200-US-CA' => '200-US',
        '200-US-CO' => '200-US-WI',
        '200-US-CT' => '200-US-CT',
        '200-US-DE' => '200-US-DE',
        '200-US-DC' => '200-US-DC',
        '200-US-FL' => '',
        '200-US-GA' => '200-US-GA',
        '200-US-HI' => '200-US-WI',
        '200-US-ID' => '200-US-WI',
        '200-US-IL' => '200-US-IL',
        '200-US-IN' => '200-US-IN',
        '200-US-IA' => '200-US-OH',
        '200-US-KS' => '200-US-WI',
        '200-US-KY' => '200-US-OH',
        '200-US-LA' => '200-US-LA',
        '200-US-ME' => '200-US-ME',
        '200-US-MD' => '200-US-MD', //Has district taxes too
        '200-US-MA' => '200-US-MA',
        '200-US-MI' => '200-US-OH',
        '200-US-MN' => '200-US-WI',
        '200-US-MS' => '200-US',
        '200-US-MO' => '200-US',
        '200-US-MT' => '200-US-OH',
        '200-US-NE' => '200-US-WI',
        '200-US-NV' => '',
        '200-US-NH' => '',
        '200-US-NM' => '200-US-WI',
        '200-US-NJ' => '200-US-NJ',
        '200-US-NY' => '100-US', //Just Single/Married are options
        '200-US-NC' => '200-US-NC',
        '200-US-ND' => '200-US-WI',
        '200-US-OH' => '200-US-OH',
        '200-US-OK' => '200-US-WI',
        '200-US-OR' => '200-US-WI',
        '200-US-PA' => '200-US-PA',
        '200-US-RI' => '200-US-WI',
        '200-US-SC' => '200-US-OH',
        '200-US-SD' => '',
        '200-US-TN' => '',
        '200-US-TX' => '',
        '200-US-UT' => '200-US-WI',
        '200-US-VT' => '200-US-WI',
        '200-US-VA' => '200-US-VA',
        '200-US-WA' => '',
        '200-US-WV' => '200-US-WV',
        '200-US-WI' => '200-US-WI',
        '200-US-WY' => '',

        '300-US-AL' => '300-US-PERCENT',
        '300-US-AK' => '300-US-PERCENT',
        '300-US-AZ' => '300-US-PERCENT',
        '300-US-AR' => '300-US-PERCENT',
        '300-US-CA' => '300-US-PERCENT',
        '300-US-CO' => '300-US-PERCENT',
        '300-US-CT' => '300-US-PERCENT',
        '300-US-DE' => '300-US-PERCENT',
        '300-US-DC' => '300-US-PERCENT',
        '300-US-FL' => '300-US-PERCENT',
        '300-US-GA' => '300-US-PERCENT',
        '300-US-HI' => '300-US-PERCENT',
        '300-US-ID' => '300-US-PERCENT',
        '300-US-IL' => '300-US-PERCENT',
        '300-US-IN' => '300-US-IN',
        '300-US-IA' => '300-US-PERCENT',
        '300-US-KS' => '300-US-PERCENT',
        '300-US-KY' => '300-US-PERCENT',
        '300-US-LA' => '300-US-PERCENT',
        '300-US-ME' => '300-US-PERCENT',
        '300-US-MD' => '300-US-MD',
        '300-US-MA' => '300-US-PERCENT',
        '300-US-MI' => '300-US-PERCENT',
        '300-US-MN' => '300-US-PERCENT',
        '300-US-MS' => '300-US-PERCENT',
        '300-US-MO' => '300-US-PERCENT',
        '300-US-MT' => '300-US-PERCENT',
        '300-US-NE' => '300-US-PERCENT',
        '300-US-NV' => '300-US-PERCENT',
        '300-US-NH' => '300-US-PERCENT',
        '300-US-NM' => '300-US-PERCENT',
        '300-US-NJ' => '300-US-PERCENT',
        '300-US-NY' => '300-US',
        '300-US-NC' => '300-US-PERCENT',
        '300-US-ND' => '300-US-PERCENT',
        '300-US-OH' => '300-US-PERCENT',
        '300-US-OK' => '300-US-PERCENT',
        '300-US-OR' => '300-US-PERCENT',
        '300-US-PA' => '300-US-PERCENT',
        '300-US-RI' => '300-US-PERCENT',
        '300-US-SC' => '300-US-PERCENT',
        '300-US-SD' => '300-US-PERCENT',
        '300-US-TN' => '300-US-PERCENT',
        '300-US-TX' => '300-US-PERCENT',
        '300-US-UT' => '300-US-PERCENT',
        '300-US-VT' => '300-US-PERCENT',
        '300-US-VA' => '300-US-PERCENT',
        '300-US-WA' => '300-US-PERCENT',
        '300-US-WV' => '300-US-PERCENT',
        '300-US-WI' => '300-US-PERCENT',
        '300-US-WY' => '300-US-PERCENT',
    );
    protected $table = 'company_deduction';
protected $pk_sequence_name = 'company_deduction_id_seq';
    protected $length_of_service_multiplier = array(
        0 => 0,
        10 => 1,
        20 => 7,
        30 => 30.4167,
        40 => 365.25,
        50 => 0.04166666666666666667, //1/24th of a day.
    );

    protected $account_amount_type_map = array(
        10 => 'amount',
        20 => 'units',
        30 => 'ytd_amount',
        40 => 'ytd_units',
    );

    protected $account_amount_type_ps_entries_map = array(
        10 => 'current',
        20 => 'current',
        30 => 'previous+ytd_adjustment',
        40 => 'previous+ytd_adjustment',
    );

    public static function getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName($company_id, $type_id, $name)
    {
        $psealf = TTnew('PayStubEntryAccountListFactory');
        $psealf->getByCompanyIdAndTypeAndFuzzyName($company_id, $type_id, $name);
        if ($psealf->getRecordCount() > 0) {
            return $psealf->getCurrent()->getId();
        }

        return false;
    }

    public function _getFactoryOptions($name, $parent = null)
    {
        $retval = null;
        switch ($name) {
            case 'status':
                $retval = array(
                    10 => TTi18n::gettext('Enabled'),
                    20 => TTi18n::gettext('Disabled'),
                );
                break;
            case 'type':
                $retval = array(
                    10 => TTi18n::gettext('Tax'),
                    20 => TTi18n::gettext('Deduction'),
                    30 => TTi18n::gettext('Other'),
                );
                break;
            case 'calculation':
                $retval = array(
                    10 => TTi18n::gettext('Percent'),
                    15 => TTi18n::gettext('Advanced Percent'),
                    17 => TTi18n::gettext('Advanced Percent (Range Bracket)'),
                    18 => TTi18n::gettext('Advanced Percent (Tax Bracket)'),
                    19 => TTi18n::gettext('Advanced Percent (Tax Bracket Alt.)'),
                    20 => TTi18n::gettext('Fixed Amount'),
                    30 => TTi18n::gettext('Fixed Amount (Range Bracket)'),

                    //Accrual/YTD formulas. - This requires custom Withdraw From/Deposit To accrual feature in PS account.
                    //50 => TTi18n::gettext('Accrual/YTD Percent'),
                    52 => TTi18n::gettext('Fixed Amount (w/Target)'),

                    //US - Custom Formulas
                    69 => TTi18n::gettext('Custom Formula'),

                    //80 => TTi18n::gettext('US - Advance EIC Formula'), //Repealed as of 31-Dec-2010.
                    82 => TTi18n::gettext('US - Medicare Formula (Employee)'),
                    83 => TTi18n::gettext('US - Medicare Formula (Employer)'),
                    84 => TTi18n::gettext('US - Social Security Formula (Employee)'),
                    85 => TTi18n::gettext('US - Social Security Formula (Employer)'),

                    //Canada - Custom Formulas CPP and EI
                    90 => TTi18n::gettext('Canada - CPP Formula'),
                    91 => TTi18n::gettext('Canada - EI Formula'),

                    //Federal
                    100 => TTi18n::gettext('Federal Income Tax Formula'),

                    //Province/State
                    200 => TTi18n::gettext('Province/State Income Tax Formula'),

                    //Sub-State/Tax Area
                    300 => TTi18n::gettext('District/County Income Tax Formula'),
                );
                break;
            case 'length_of_service_unit':
                $retval = array(
                    10 => TTi18n::gettext('Day(s)'),
                    20 => TTi18n::gettext('Week(s)'),
                    30 => TTi18n::gettext('Month(s)'),
                    40 => TTi18n::gettext('Year(s)'),
                    50 => TTi18n::gettext('Hour(s)'),
                );
                break;
            case 'apply_frequency':
                $retval = array(
                    10 => TTi18n::gettext('each Pay Period'),
                    20 => TTi18n::gettext('Annually'),
                    25 => TTi18n::gettext('Quarterly'),
                    30 => TTi18n::gettext('Monthly'),
                    //40 => TTi18n::gettext('Weekly'),
                    100 => TTi18n::gettext('Hire Date'),
                    110 => TTi18n::gettext('Hire Date (Anniversary)'),
                    120 => TTi18n::gettext('Termination Date'),
                    130 => TTi18n::gettext('Birth Date (Anniversary)'),
                );
                break;
            case 'apply_payroll_run_type':
                $psf = TTnew('PayStubFactory');
                $retval = $psf->getOptions('type');
                break;
            case 'look_back_unit':
                $retval = array(
                    10 => TTi18n::gettext('Day(s)'),
                    20 => TTi18n::gettext('Week(s)'),
                    30 => TTi18n::gettext('Month(s)'),
                    40 => TTi18n::gettext('Year(s)'),
                    //50 => TTi18n::gettext('Hour(s)'),
                    //100 => TTi18n::gettext('Pay Period(s)'), //How do you handle employees switching between pay period schedules? This has too many issues for now.
                );
                break;
            case 'account_amount_type':
                $retval = array(
                    10 => TTi18n::gettext('Amount'),
                    20 => TTi18n::gettext('Units/Hours'),
                    30 => TTi18n::gettext('YTD Amount'),
                    40 => TTi18n::gettext('YTD Units/Hours'),
                );
                break;
            case 'tax_formula_type':
                $retval = array(
                    0 => TTi18n::gettext('Based on Payroll Run Type'), //Best fit formula depending on payroll run type (Regular or Bonus/Correction)
                    10 => TTi18n::gettext('Always Regular (Non-Averaging)'),
                    20 => TTi18n::gettext('Always Special (Cummulative Averaging)'),
                );
                break;
            case 'us_medicare_filing_status': //Medicare certificate
                $retval = array(
                    10 => TTi18n::gettext('Single or Head of Household'),
                    20 => TTi18n::gettext('Married - Without Spouse Filing'),
                    30 => TTi18n::gettext('Married - With Spouse Filing'),

                );
                break;
            case 'us_eic_filing_status': //EIC certificate
                $retval = array(
                    10 => TTi18n::gettext('Single or Head of Household'),
                    20 => TTi18n::gettext('Married - Without Spouse Filing'),
                    30 => TTi18n::gettext('Married - With Spouse Filing'),

                );
                break;
            case 'federal_filing_status': //US
                $retval = array(
                    10 => TTi18n::gettext('Single'),
                    20 => TTi18n::gettext('Married'),
                );
                break;
            case 'state_filing_status':
                $retval = array(
                    10 => TTi18n::gettext('Single'),
                    20 => TTi18n::gettext('Married - Spouse Works'),
                    30 => TTi18n::gettext('Married - Spouse does not Work'),
                    40 => TTi18n::gettext('Head of Household'),
                );
                break;
            case 'state_ga_filing_status':
                $retval = array(
                    10 => TTi18n::gettext('Single'),
                    20 => TTi18n::gettext('Married - Filing Separately'),
                    30 => TTi18n::gettext('Married - Joint One Income'),
                    40 => TTi18n::gettext('Married - Joint Two Incomes'),
                    50 => TTi18n::gettext('Head of Household'),
                );
                break;
            case 'state_nj_filing_status':
                $retval = array(
                    10 => TTi18n::gettext('Rate "A"'),
                    20 => TTi18n::gettext('Rate "B"'),
                    30 => TTi18n::gettext('Rate "C"'),
                    40 => TTi18n::gettext('Rate "D"'),
                    50 => TTi18n::gettext('Rate "E"'),
                );
                break;
            case 'state_nc_filing_status':
                $retval = array(
                    10 => TTi18n::gettext('Single'),
                    20 => TTi18n::gettext('Married - Filing Jointly or Qualified Widow(er)'),
                    30 => TTi18n::gettext('Married - Filing Separately'),
                    40 => TTi18n::gettext('Head of Household'),
                );
                break;
            case 'state_ma_filing_status':
                $retval = array(
                    10 => TTi18n::gettext('Regular'),
                    20 => TTi18n::gettext('Head of Household'),
                    30 => TTi18n::gettext('Blind'),
                    40 => TTi18n::gettext('Head of Household and Blind')
                );
                break;
            case 'state_al_filing_status':
                $retval = array(
                    10 => TTi18n::gettext('Status "S": Claiming $1500'),
                    20 => TTi18n::gettext('Status "M": Claiming $3000'),
                    30 => TTi18n::gettext('Status "0"'),
                    40 => TTi18n::gettext('Head of Household'),
                    50 => TTi18n::gettext('Status "MS"')
                );
                break;
            case 'state_ct_filing_status':
                $retval = array(
                    10 => TTi18n::gettext('Status "A"'),
                    20 => TTi18n::gettext('Status "B"'),
                    30 => TTi18n::gettext('Status "C"'),
                    40 => TTi18n::gettext('Status "D"'),
                    //50 => TTi18n::gettext('Status "E"'), //Doesn't exist.
                    60 => TTi18n::gettext('Status "F"'),
                );
                break;
            case 'state_wv_filing_status':
                $retval = array(
                    10 => TTi18n::gettext('Standard'),
                    20 => TTi18n::gettext('Optional Two Earners'),
                );
                break;
            case 'state_me_filing_status':
                $retval = array(
                    10 => TTi18n::gettext('Single'),
                    20 => TTi18n::gettext('Married'),
                    30 => TTi18n::gettext('Married with 2 incomes'),
                );
                break;
            case 'state_de_filing_status':
                $retval = array(
                    10 => TTi18n::gettext('Single'),
                    20 => TTi18n::gettext('Married (Filing Jointly)'),
                    30 => TTi18n::gettext('Married (Filing Separately)'),
                );
                break;
            case 'state_dc_filing_status':
                $retval = array(
                    10 => TTi18n::gettext('Single'),
                    20 => TTi18n::gettext('Married (Filing Jointly)'),
                    30 => TTi18n::gettext('Married (Filing Separately)'),
                    40 => TTi18n::gettext('Head of Household'),
                );
                break;
            case 'state_la_filing_status':
                $retval = array(
                    10 => TTi18n::gettext('Single'),
                    20 => TTi18n::gettext('Married (Filing Jointly)'),
                );
                break;
            case 'formula_variables':
                $retval = array(

                    '-1010-#pay_stub_amount#' => TTi18n::getText('Pay Stub Amount'),
                    '-1020-#pay_stub_ytd_amount#' => TTi18n::getText('Pay Stub YTD Amount'),
                    '-1030-#pay_stub_units#' => TTi18n::getText('Pay Stub Units'),
                    '-1040-#pay_stub_ytd_units#' => TTi18n::getText('Pay Stub YTD Units'),

                    '-1050-#include_pay_stub_amount#' => TTi18n::getText('Include Pay Stub Amount'),
                    '-1060-#include_pay_stub_ytd_amount#' => TTi18n::getText('Include Pay Stub YTD Amount'),
                    '-1070-#include_pay_stub_units#' => TTi18n::getText('Include Pay Stub Units'),
                    '-1080-#include_pay_stub_ytd_units#' => TTi18n::getText('Include Pay Stub YTD Units'),
                    '-1090-#exclude_pay_stub_amount#' => TTi18n::getText('Exclude Pay Stub Amount'),
                    '-1100-#exclude_pay_stub_ytd_amount#' => TTi18n::getText('Exclude Pay Stub YTD Amount'),
                    '-1110-#exclude_pay_stub_units#' => TTi18n::getText('Exclude Pay Stub Units'),
                    '-1120-#exclude_pay_stub_ytd_units#' => TTi18n::getText('Exclude Pay Stub YTD Units'),

                    '-1130-#employee_hourly_rate#' => TTi18n::getText('Employee Hourly Rate'),
                    '-1132-#employee_annual_wage#' => TTi18n::getText('Employee Annual Wage'),
                    '-1134-#employee_wage_average_weekly_hours#' => TTi18n::getText('Employee Average Weekly Hours'),

                    '-1140-#custom_value1#' => TTi18n::getText('Custom Variable 1'),
                    '-1150-#custom_value2#' => TTi18n::getText('Custom Variable 2'),
                    '-1160-#custom_value3#' => TTi18n::getText('Custom Variable 3'),
                    '-1170-#custom_value4#' => TTi18n::getText('Custom Variable 4'),
                    '-1180-#custom_value5#' => TTi18n::getText('Custom Variable 5'),
                    '-1190-#custom_value6#' => TTi18n::getText('Custom Variable 6'),
                    '-1200-#custom_value7#' => TTi18n::getText('Custom Variable 7'),
                    '-1210-#custom_value8#' => TTi18n::getText('Custom Variable 8'),
                    '-1220-#custom_value9#' => TTi18n::getText('Custom Variable 9'),
                    '-1230-#custom_value10#' => TTi18n::getText('Custom Variable 10'),

                    '-1240-#annual_pay_periods#' => TTi18n::getText('Annual Pay Periods'),
                    '-1242-#pay_period_start_date#' => TTi18n::getText('Pay Period - Start Date'),
                    '-1243-#pay_period_end_date#' => TTi18n::getText('Pay Period - End Date'),
                    '-1244-#pay_period_transaction_date#' => TTi18n::getText('Pay Period - Transaction Date'),
                    '-1245-#pay_period_total_days#' => TTi18n::getText('Pay Period - Total Days'),
                    '-1248-#pay_period_worked_days#' => TTi18n::getText('Pay Period - Total Worked Days'),
                    '-1249-#pay_period_paid_days#' => TTi18n::getText('Pay Period - Total Paid Days'),
                    '-1250-#pay_period_worked_time#' => TTi18n::getText('Pay Period - Total Worked Time'),
                    '-1251-#pay_period_paid_time#' => TTi18n::getText('Pay Period - Total Paid Time'),

                    '-1260-#employee_hire_date#' => TTi18n::getText('Employee Hire Date'),
                    '-1261-#employee_termination_date#' => TTi18n::getText('Employee Termination Date'),
                    '-1270-#employee_birth_date#' => TTi18n::getText('Employee Birth Date'),

                    '-1300-#currency_iso_code#' => TTi18n::getText('Currency ISO Code'),
                    '-1305-#currency_conversion_rate#' => TTi18n::getText('Currency Conversion Rate'),

                    '-1510-#lookback_total_pay_stubs#' => TTi18n::getText('Lookback - Total Pay Stubs'),
                    '-1520-#lookback_start_date#' => TTi18n::getText('Lookback - Start Date'),
                    '-1522-#lookback_end_date#' => TTi18n::getText('Lookback - End Date'),
                    '-1523-#lookback_total_days#' => TTi18n::getText('Lookback - Total Days'),

                    '-1530-#lookback_first_pay_stub_start_date#' => TTi18n::getText('Lookback - First Pay Stub Start Date'),
                    '-1532-#lookback_first_pay_stub_end_date#' => TTi18n::getText('Lookback - First Pay Stub End Date'),
                    '-1534-#lookback_first_pay_stub_transaction_date#' => TTi18n::getText('Lookback - First Pay Stub Transaction Date'),
                    '-1540-#lookback_last_pay_stub_start_date#' => TTi18n::getText('Lookback - Last Pay Stub Start Date'),
                    '-1542-#lookback_last_pay_stub_end_date#' => TTi18n::getText('Lookback - Last Pay Stub End Date'),
                    '-1544-#lookback_last_pay_stub_transaction_date#' => TTi18n::getText('Lookback - Last Pay Stub Transaction Date'),

                    '-1545-#lookback_pay_stub_total_days#' => TTi18n::getText('Lookback - Pay Period Total Days'),
                    '-1546-#lookback_pay_stub_worked_days#' => TTi18n::getText('Lookback - Pay Period Worked Days'),
                    '-1547-#lookback_pay_stub_paid_days#' => TTi18n::getText('Lookback - Pay Period Paid Days'),
                    '-1548-#lookback_pay_stub_worked_time#' => TTi18n::getText('Lookback - Pay Period Worked Time'),
                    '-1549-#lookback_pay_stub_paid_time#' => TTi18n::getText('Lookback - Pay Period Paid Time'),

                    '-1610-#lookback_pay_stub_amount#' => TTi18n::getText('Lookback - Pay Stub Amount'),
                    '-1620-#lookback_pay_stub_ytd_amount#' => TTi18n::getText('Lookback - Pay Stub YTD Amount'),
                    '-1630-#lookback_pay_stub_units#' => TTi18n::getText('Lookback - Pay Stub Units'),
                    '-1640-#lookback_pay_stub_ytd_units#' => TTi18n::getText('Lookback - Pay Stub YTD Units'),

                    '-1650-#lookback_include_pay_stub_amount#' => TTi18n::getText('Lookback - Include Pay Stub Amount'),
                    '-1660-#lookback_include_pay_stub_ytd_amount#' => TTi18n::getText('Lookback - Include Pay Stub YTD Amount'),
                    '-1670-#lookback_include_pay_stub_units#' => TTi18n::getText('Lookback - Include Pay Stub Units'),
                    '-1680-#lookback_include_pay_stub_ytd_units#' => TTi18n::getText('Lookback - Include Pay Stub YTD Units'),
                    '-1690-#lookback_exclude_pay_stub_amount#' => TTi18n::getText('Lookback - Exclude Pay Stub Amount'),
                    '-1700-#lookback_exclude_pay_stub_ytd_amount#' => TTi18n::getText('Lookback - Exclude Pay Stub YTD Amount'),
                    '-1710-#lookback_exclude_pay_stub_units#' => TTi18n::getText('Lookback - Exclude Pay Stub Units'),
                    '-1720-#lookback_exclude_pay_stub_ytd_units#' => TTi18n::getText('Lookback - Exclude Pay Stub YTD Units'),
                );
                break;
            case 'columns':
                $retval = array(
                    '-1010-status' => TTi18n::gettext('Status'),
                    '-1020-type' => TTi18n::gettext('Type'),
                    '-1030-name' => TTi18n::gettext('Name'),
                    '-1040-calculation' => TTi18n::gettext('Calculation'),

                    '-1050-start_date' => TTi18n::gettext('Start Date'),
                    '-1060-end_Date_date' => TTi18n::gettext('End Date'),

                    '-1070-calculation_order' => TTi18n::gettext('Calculation Order'),

                    '-1100-total_users' => TTi18n::gettext('Employees'),

                    '-2000-created_by' => TTi18n::gettext('Created By'),
                    '-2010-created_date' => TTi18n::gettext('Created Date'),
                    '-2020-updated_by' => TTi18n::gettext('Updated By'),
                    '-2030-updated_date' => TTi18n::gettext('Updated Date'),
                );
                break;
            case 'list_columns':
                //Don't show the total_users column here, as its primarily used for Edit Employee -> Tax tab.
                $list_columns = array(
                    'status',
                    'type',
                    'name',
                    'calculation',
                );

                $retval = Misc::arrayIntersectByKey($list_columns, Misc::trimSortPrefix($this->getOptions('columns')));
                break;
            case 'default_display_columns': //Columns that are displayed by default.
                $retval = array(
                    'status',
                    'type',
                    'name',
                    'calculation',
                    'total_users',
                );
                break;
            case 'unique_columns': //Columns that are unique, and disabled for mass editing.
                $retval = array(
                    'calculation_id',
                    'country',
                    'province',
                    'district',
                    'company_value1',
                    'company_value2',
                    'company_value3',
                    'company_value4',
                    'company_value5',
                    'company_value6',
                    'company_value7',
                    'company_value8',
                    'company_value9',
                    'company_value10',
                    'user_value1',
                    'user_value2',
                    'user_value3',
                    'user_value4',
                    'user_value5',
                    'user_value6',
                    'user_value7',
                    'user_value8',
                    'user_value9',
                    'user_value10',
                );
                break;
            case 'linked_columns': //Columns that are linked together, mainly for Mass Edit, if one changes, they all must.
                $retval = array(
                    'country',
                    'province',
                    'district'
                );
                break;
        }

        return $retval;
    }

    public function _getVariableToFunctionMap($data)
    {
        $variable_function_map = array(
            'id' => 'ID',
            'company_id' => 'Company',
            'status_id' => 'Status',
            'status' => false,
            'type_id' => 'Type',
            'type' => false,
            'name' => 'Name',
            'start_date' => 'StartDate',
            'end_date' => 'EndDate',
            'minimum_length_of_service_unit_id' => 'MinimumLengthOfServiceUnit', //Must go before minimum_length_of_service_days, for calculations to not fail.
            'minimum_length_of_service_days' => 'MinimumLengthOfServiceDays',
            'minimum_length_of_service' => 'MinimumLengthOfService',
            'maximum_length_of_service_unit_id' => 'MaximumLengthOfServiceUnit', //Must go before maximum_length_of_service_days, for calculations to not fail.
            'maximum_length_of_service_days' => 'MaximumLengthOfServiceDays',
            'maximum_length_of_service' => 'MaximumLengthOfService',
            'length_of_service_contributing_pay_code_policy_id' => 'LengthOfServiceContributingPayCodePolicy',
            'length_of_service_contributing_pay_code_policy' => false,
            'minimum_user_age' => 'MinimumUserAge',
            'maximum_user_age' => 'MaximumUserAge',
            'apply_frequency_id' => 'ApplyFrequency',
            'apply_frequency_month' => 'ApplyFrequencyMonth',
            'apply_frequency_day_of_month' => 'ApplyFrequencyDayOfMonth',
            'apply_frequency_day_of_week' => 'ApplyFrequencyDayOfWeek',
            'apply_frequency_quarter_month' => 'ApplyFrequencyQuarterMonth',
            'apply_payroll_run_type_id' => 'ApplyPayrollRunType',
            'pay_stub_entry_description' => 'PayStubEntryDescription',
            'calculation_id' => 'Calculation',
            'calculation' => false,
            'calculation_order' => 'CalculationOrder',
            'country' => 'Country',
            'province' => 'Province',
            'district' => 'District',
            'company_value1' => 'CompanyValue1',
            'company_value2' => 'CompanyValue2',
            'company_value3' => 'CompanyValue3',
            'company_value4' => 'CompanyValue4',
            'company_value5' => 'CompanyValue5',
            'company_value6' => 'CompanyValue6',
            'company_value7' => 'CompanyValue7',
            'company_value8' => 'CompanyValue8',
            'company_value9' => 'CompanyValue9',
            'company_value10' => 'CompanyValue10',
            'user_value1' => 'UserValue1',
            'user_value2' => 'UserValue2',
            'user_value3' => 'UserValue3',
            'user_value4' => 'UserValue4',
            'user_value5' => 'UserValue5',
            'user_value6' => 'UserValue6',
            'user_value7' => 'UserValue7',
            'user_value8' => 'UserValue8',
            'user_value9' => 'UserValue9',
            'user_value10' => 'UserValue10',
            'pay_stub_entry_account_id' => 'PayStubEntryAccount',
            'lock_user_value1' => 'LockUserValue1',
            'lock_user_value2' => 'LockUserValue2',
            'lock_user_value3' => 'LockUserValue3',
            'lock_user_value4' => 'LockUserValue4',
            'lock_user_value5' => 'LockUserValue5',
            'lock_user_value6' => 'LockUserValue6',
            'lock_user_value7' => 'LockUserValue7',
            'lock_user_value8' => 'LockUserValue8',
            'lock_user_value9' => 'LockUserValue9',
            'lock_user_value10' => 'LockUserValue10',
            'include_account_amount_type_id' => 'IncludeAccountAmountType',
            'include_pay_stub_entry_account' => 'IncludePayStubEntryAccount',
            'exclude_account_amount_type_id' => 'ExcludeAccountAmountType',
            'exclude_pay_stub_entry_account' => 'ExcludePayStubEntryAccount',
            'user' => 'User',
            'total_users' => 'TotalUsers',
            'deleted' => 'Deleted',
        );
        return $variable_function_map;
    }

    public function getPayStubEntryAccountObject()
    {
        return $this->getGenericObject('PayStubEntryAccountListFactory', $this->getPayStubEntryAccount(), 'pay_stub_entry_account_obj');
    }

    public function getPayStubEntryAccount()
    {
        if (isset($this->data['pay_stub_entry_account_id'])) {
            return (int)$this->data['pay_stub_entry_account_id'];
        }

        return false;
    }

    public function setCompany($id)
    {
        $id = trim($id);

        Debug::Text('Company ID: ' . $id, __FILE__, __LINE__, __METHOD__, 10);
        $clf = TTnew('CompanyListFactory');

        if ($this->Validator->isResultSetWithRows('company',
            $clf->getByID($id),
            TTi18n::gettext('Company is invalid')
        )
        ) {
            $this->data['company_id'] = $id;

            return true;
        }

        return false;
    }

    public function getPayStubEntryDescription()
    {
        if (isset($this->data['pay_stub_entry_description'])) {
            return $this->data['pay_stub_entry_description'];
        }

        return false;
    }

    public function setPayStubEntryDescription($value)
    {
        $value = trim($value);

        if (
            strlen($value) == 0
            or
            $this->Validator->isLength('pay_stub_entry_description',
                $value,
                TTi18n::gettext('Description is too short or too long'),
                0,
                100)
        ) {
            $this->data['pay_stub_entry_description'] = htmlspecialchars($value);

            return true;
        }

        return false;
    }

    public function getStartDate($raw = false)
    {
        if (isset($this->data['start_date'])) {
            if ($raw === true) {
                return $this->data['start_date'];
            } else {
                return TTDate::strtotime($this->data['start_date']);
            }
        }

        return false;
    }

    public function setStartDate($epoch)
    {
        $epoch = (!is_int($epoch)) ? trim($epoch) : $epoch; //Dont trim integer values, as it changes them to strings.

        if ($epoch == '') {
            $epoch = null;
        }

        if (
            $epoch == null
            or
            $this->Validator->isDate('start_date',
                $epoch,
                TTi18n::gettext('Incorrect start date'))
        ) {
            $this->data['start_date'] = $epoch;

            return true;
        }

        return false;
    }

    public function getEndDate($raw = false)
    {
        if (isset($this->data['end_date'])) {
            if ($raw === true) {
                return $this->data['end_date'];
            } else {
                return TTDate::strtotime($this->data['end_date']);
            }
        }

        return false;
    }

    public function setEndDate($epoch)
    {
        $epoch = (!is_int($epoch)) ? trim($epoch) : $epoch; //Dont trim integer values, as it changes them to strings.

        if ($epoch == '') {
            $epoch = null;
        }

        if ($epoch == null
            or
            $this->Validator->isDate('end_date',
                $epoch,
                TTi18n::gettext('Incorrect end date'))
        ) {
            $this->data['end_date'] = $epoch;

            return true;
        }

        return false;
    }

    public function isActiveDate($ud_obj, $pp_end_date = null, $pp_transaction_date = null)
    {
        $pp_end_date = TTDate::getBeginDayEpoch($pp_end_date);

        if ($ud_obj->getStartDate() == '' and $ud_obj->getEndDate() == '') {
            return true;
        }

        if ($this->getCalculation() == 90 and $pp_transaction_date != '') { //CPP
            if (TTDate::getEndDayEpoch($pp_transaction_date) > TTDate::getEndMonthEpoch((int)$ud_obj->getStartDate())
                and (TTDate::getEndDayEpoch($pp_transaction_date) <= TTDate::getEndMonthEpoch((int)$ud_obj->getEndDate()) or $ud_obj->getEndDate() == '')
            ) {
                Debug::text('CPP: Within Start/End Date.', __FILE__, __LINE__, __METHOD__, 10);

                return true;
            }

            Debug::text('CPP: Epoch: ' . TTDate::getDate('DATE+TIME', $pp_transaction_date) . ' is outside Start: ' . TTDate::getDate('DATE+TIME', $ud_obj->getStartDate()) . ' and End Date: ' . TTDate::getDate('DATE+TIME', $ud_obj->getEndDate()), __FILE__, __LINE__, __METHOD__, 10);
            return false;
        } else {
            if ($pp_end_date >= (int)$ud_obj->getStartDate()
                and ($pp_end_date <= (int)$ud_obj->getEndDate() or $ud_obj->getEndDate() == '')
            ) {
                Debug::text('Within Start/End Date.', __FILE__, __LINE__, __METHOD__, 10);

                return true;
            }

            Debug::text('Epoch: ' . TTDate::getDate('DATE+TIME', $pp_end_date) . ' is outside Start: ' . TTDate::getDate('DATE+TIME', $ud_obj->getStartDate()) . ' and End Date: ' . TTDate::getDate('DATE+TIME', $ud_obj->getEndDate()), __FILE__, __LINE__, __METHOD__, 10);
            return false;
        }
    }

    public function getCalculation()
    {
        if (isset($this->data['calculation_id'])) {
            return (int)$this->data['calculation_id'];
        }

        return false;
    }

    public function setMinimumLengthOfService($int)
    {
        $int = (float)trim($int);

        Debug::text('bLength of Service: ' . $int, __FILE__, __LINE__, __METHOD__, 10);

        if ($int >= 0
            and
            $this->Validator->isFloat('minimum_length_of_service',
                $int,
                TTi18n::gettext('Minimum length of service is invalid'))
        ) {
            $this->data['minimum_length_of_service'] = $int;

            return true;
        }

        return false;
    }

    public function setMinimumLengthOfServiceUnit($value)
    {
        $value = trim($value);

        if ($value == ''
            or $this->Validator->inArrayKey('minimum_length_of_service_unit_id',
                $value,
                TTi18n::gettext('Incorrect minimum length of service unit'),
                $this->getOptions('length_of_service_unit'))
        ) {
            $this->data['minimum_length_of_service_unit_id'] = $value;

            return true;
        }

        return false;
    }

    public function setMaximumLengthOfService($int)
    {
        $int = (float)trim($int);

        Debug::text('bLength of Service: ' . $int, __FILE__, __LINE__, __METHOD__, 10);

        if ($int >= 0
            and
            $this->Validator->isFloat('maximum_length_of_service',
                $int,
                TTi18n::gettext('Maximum length of service is invalid'))
        ) {
            $this->data['maximum_length_of_service'] = $int;

            return true;
        }

        return false;
    }

    public function setMaximumLengthOfServiceUnit($value)
    {
        $value = trim($value);

        if ($value == ''
            or $this->Validator->inArrayKey('maximum_length_of_service_unit_id',
                $value,
                TTi18n::gettext('Incorrect maximum length of service unit'),
                $this->getOptions('length_of_service_unit'))
        ) {
            $this->data['maximum_length_of_service_unit_id'] = $value;

            return true;
        }

        return false;
    }

    public function setMinimumUserAge($int)
    {
        $int = (float)trim($int);

        Debug::text('Minimum User Age: ' . $int, __FILE__, __LINE__, __METHOD__, 10);

        if ($int >= 0
            and
            $this->Validator->isFloat('minimum_user_age',
                $int,
                TTi18n::gettext('Minimum employee age is invalid'))
        ) {
            $this->data['minimum_user_age'] = $int;

            return true;
        }

        return false;
    }

    public function setMaximumUserAge($int)
    {
        $int = (float)trim($int);

        Debug::text('Maximum User Age: ' . $int, __FILE__, __LINE__, __METHOD__, 10);

        if ($int >= 0
            and
            $this->Validator->isFloat('maximum_user_age',
                $int,
                TTi18n::gettext('Maximum employee age is invalid'))
        ) {
            $this->data['maximum_user_age'] = $int;

            return true;
        }

        return false;
    }

    public function setLengthOfServiceContributingPayCodePolicy($id)
    {
        $id = trim($id);

        $csplf = TTnew('ContributingPayCodePolicyListFactory');

        if ($id == 0
            or
            $this->Validator->isResultSetWithRows('length_of_service_contributing_pay_code_policy_id',
                $csplf->getByID($id),
                TTi18n::gettext('Contributing Pay Code Policy is invalid')
            )
        ) {
            $this->data['length_of_service_contributing_pay_code_policy_id'] = $id;

            return true;
        }

        return false;
    }

    //Check if this date is within the effective date range

    public function setApplyFrequencyMonth($value)
    {
        $value = trim($value);

        if ($value == 0
            or
            $this->Validator->inArrayKey('apply_frequency_month',
                $value,
                TTi18n::gettext('Incorrect frequency month'),
                TTDate::getMonthOfYearArray())
        ) {
            $this->data['apply_frequency_month'] = $value;

            return true;
        }

        return false;
    }

    public function setApplyFrequencyDayOfMonth($value)
    {
        $value = trim($value);

        if ($value == 0
            or
            $this->Validator->inArrayKey('apply_frequency_day_of_month',
                $value,
                TTi18n::gettext('Incorrect frequency day of month'),
                TTDate::getDayOfMonthArray())
        ) {
            $this->data['apply_frequency_day_of_month'] = $value;

            return true;
        }

        return false;
    }

    public function getApplyFrequencyDayOfWeek()
    {
        if (isset($this->data['apply_frequency_day_of_week'])) {
            return $this->data['apply_frequency_day_of_week'];
        }

        return false;
    }

    public function setApplyFrequencyDayOfWeek($value)
    {
        $value = trim($value);

        if ($value == 0
            or
            $this->Validator->inArrayKey('apply_frequency_day_of_week',
                $value,
                TTi18n::gettext('Incorrect frequency day of week'),
                TTDate::getDayOfWeekArray())
        ) {
            $this->data['apply_frequency_day_of_week'] = $value;

            return true;
        }

        return false;
    }

    public function setApplyFrequencyQuarterMonth($value)
    {
        $value = trim($value);

        if ($value == 0
            or
            (
                $this->Validator->isGreaterThan('apply_frequency_quarter_month',
                    $value,
                    TTi18n::gettext('Incorrect frequency quarter month'),
                    1)
                and
                $this->Validator->isLessThan('apply_frequency_quarter_month',
                    $value,
                    TTi18n::gettext('Incorrect frequency quarter month'),
                    3)
            )
        ) {
            $this->data['apply_frequency_quarter_month'] = $value;

            return true;
        }

        return false;
    }

    public function setApplyPayrollRunType($value)
    {
        $value = trim($value);

        if ($value == 0
            or
            $this->Validator->inArrayKey('apply_payroll_run_type_id',
                $value,
                TTi18n::gettext('Incorrect payroll run type'),
                $this->getOptions('apply_payroll_run_type'))
        ) {
            $this->data['apply_payroll_run_type_id'] = $value;

            return true;
        }

        return false;
    }

    public function inApplyFrequencyWindow($pay_period_start_date, $pay_period_end_date, $hire_date = null, $termination_date = null, $birth_date = null)
    {
        if ($this->getApplyFrequency() == false or $this->getApplyFrequency() == 10) { //Each pay period
            return true;
        }

        $frequency_criteria = array(
            'month' => $this->getApplyFrequencyMonth(),
            'day_of_month' => $this->getApplyFrequencyDayOfMonth(),
            'quarter_month' => $this->getApplyFrequencyQuarterMonth(),
        );

        $frequency_id = $this->getApplyFrequency();
        switch ($this->getApplyFrequency()) {
            case 100: //Hire Date
                $frequency_criteria['date'] = $hire_date;
                $frequency_id = 100; //Specific date
                break;
            case 110: //Hire Date anniversary.
                $frequency_criteria['month'] = TTDate::getMonth($hire_date);
                $frequency_criteria['day_of_month'] = TTDate::getDayOfMonth($hire_date);
                $frequency_id = 20; //Annually
                break;
            case 120:
                $frequency_criteria['date'] = $termination_date;
                $frequency_id = 100; //Specific date
                break;
            case 130: //Birth Date anniversary.
                $frequency_criteria['month'] = TTDate::getMonth($birth_date);
                $frequency_criteria['day_of_month'] = TTDate::getDayOfMonth($birth_date);
                $frequency_id = 20; //Annually
                break;
        }

        $retval = TTDate::inApplyFrequencyWindow($frequency_id, $pay_period_start_date, $pay_period_end_date, $frequency_criteria);
        Debug::Arr($frequency_criteria, 'Frequency: ' . $this->getApplyFrequency() . ' Retval: ' . (int)$retval, __FILE__, __LINE__, __METHOD__, 10);

        return $retval;
    }

    public function getApplyFrequency()
    {
        if (isset($this->data['apply_frequency_id'])) {
            return (int)$this->data['apply_frequency_id'];
        }

        return false;
    }

    public function getApplyFrequencyMonth()
    {
        if (isset($this->data['apply_frequency_month'])) {
            return $this->data['apply_frequency_month'];
        }

        return false;
    }

    public function getApplyFrequencyDayOfMonth()
    {
        if (isset($this->data['apply_frequency_day_of_month'])) {
            return $this->data['apply_frequency_day_of_month'];
        }

        return false;
    }

    public function getApplyFrequencyQuarterMonth()
    {
        if (isset($this->data['apply_frequency_quarter_month'])) {
            return $this->data['apply_frequency_quarter_month'];
        }

        return false;
    }

    public function inApplyPayrollRunType($type_id)
    {
        if ($this->getApplyPayrollRunType() == 0 or $type_id == $this->getApplyPayrollRunType()) {
            return true;
        }

        Debug::Text('Apply Payroll Run Type: ' . $this->getApplyPayrollRunType() . ' Type: ' . (int)$type_id, __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function getApplyPayrollRunType()
    {
        if (isset($this->data['apply_payroll_run_type_id'])) {
            return (int)$this->data['apply_payroll_run_type_id'];
        }

        return false;
    }

    public function isActiveLengthOfService($ud_obj, $epoch)
    {
        $worked_time = 0;
        if (($this->getMinimumLengthOfServiceUnit() == 50 and $this->getMinimumLengthOfService() > 0)
            or ($this->getMaximumLengthOfServiceUnit() == 50 and $this->getMaximumLengthOfService() > 0)
        ) {
            //Hour based length of service, get users hours up until this period.
            $worked_time = TTDate::getHours($this->getWorkedTimeByUserIdAndEndDate($ud_obj->getUser(), $ud_obj->getLengthOfServiceDate(), $epoch));
            Debug::Text('  Worked Time: ' . $worked_time . 'hrs', __FILE__, __LINE__, __METHOD__, 10);
        }

        $employed_days = TTDate::getDays(($epoch - $ud_obj->getLengthOfServiceDate()));
        Debug::Text('  Employed Days: ' . $employed_days . ' Based On: ' . TTDate::getDate('DATE+TIME', $ud_obj->getLengthOfServiceDate()), __FILE__, __LINE__, __METHOD__, 10);

        $minimum_length_of_service_result = false;
        $maximum_length_of_service_result = false;
        //Check minimum length of service
        if ($this->getMinimumLengthOfService() == 0
            or ($this->getMinimumLengthOfServiceUnit() == 50 and $worked_time >= $this->getMinimumLengthOfService())
            or ($this->getMinimumLengthOfServiceUnit() != 50 and $employed_days >= $this->getMinimumLengthOfServiceDays())
        ) {
            $minimum_length_of_service_result = true;
        }

        //Check maximum length of service.
        if ($this->getMaximumLengthOfService() == 0
            or ($this->getMaximumLengthOfServiceUnit() == 50 and $worked_time <= $this->getMaximumLengthOfService())
            or ($this->getMaximumLengthOfServiceUnit() != 50 and $employed_days <= $this->getMaximumLengthOfServiceDays())
        ) {
            $maximum_length_of_service_result = true;
        }

        //Debug::Text('   Min Result: '. (int)$minimum_length_of_service_result .' Max Result: '. (int)$maximum_length_of_service_result, __FILE__, __LINE__, __METHOD__, 10);

        if ($minimum_length_of_service_result == true and $maximum_length_of_service_result == true) {
            return true;
        }

        return false;
    }

    public function getMinimumLengthOfServiceUnit()
    {
        if (isset($this->data['minimum_length_of_service_unit_id'])) {
            return (int)$this->data['minimum_length_of_service_unit_id'];
        }

        return false;
    }

    public function getMinimumLengthOfService()
    {
        if (isset($this->data['minimum_length_of_service'])) {
            return (float)$this->data['minimum_length_of_service'];
        }

        return false;
    }

    public function getMaximumLengthOfServiceUnit()
    {
        if (isset($this->data['maximum_length_of_service_unit_id'])) {
            return (int)$this->data['maximum_length_of_service_unit_id'];
        }

        return false;
    }

    public function getMaximumLengthOfService()
    {
        if (isset($this->data['maximum_length_of_service'])) {
            return (float)$this->data['maximum_length_of_service'];
        }

        return false;
    }

    public function getWorkedTimeByUserIdAndEndDate($user_id, $start_date = null, $end_date = null)
    {
        if ($user_id == '') {
            return false;
        }

        if ($start_date == '') {
            $start_date = 1; //Default to beginning of time if hire date is not specified.
        }

        if ($end_date == '') {
            return false;
        }

        $retval = 0;

        $pay_code_policy_obj = $this->getLengthOfServiceContributingPayCodePolicyObject();
        if (is_object($pay_code_policy_obj)) {
            $udtlf = TTnew('UserDateTotalListFactory');
            $retval = $udtlf->getTotalTimeSumByUserIDAndPayCodeIDAndStartDateAndEndDate($user_id, $pay_code_policy_obj->getPayCode(), $start_date, $end_date);
        }

        Debug::Text('Worked Seconds: ' . (int)$retval . ' Before: ' . TTDate::getDate('DATE+TIME', $end_date), __FILE__, __LINE__, __METHOD__, 10);

        return $retval;
    }

    //
    // Calendar
    //

    public function getLengthOfServiceContributingPayCodePolicyObject()
    {
        return $this->getGenericObject('ContributingPayCodePolicyListFactory', $this->getLengthOfServiceContributingPayCodePolicy(), 'length_of_service_contributing_pay_code_policy_obj');
    }

    public function getLengthOfServiceContributingPayCodePolicy()
    {
        if (isset($this->data['length_of_service_contributing_pay_code_policy_id'])) {
            return (int)$this->data['length_of_service_contributing_pay_code_policy_id'];
        }

        return false;
    }

    public function getMinimumLengthOfServiceDays()
    {
        if (isset($this->data['minimum_length_of_service_days'])) {
            return (float)$this->data['minimum_length_of_service_days'];
        }

        return false;
    }

    public function getMaximumLengthOfServiceDays()
    {
        if (isset($this->data['maximum_length_of_service_days'])) {
            return (float)$this->data['maximum_length_of_service_days'];
        }

        return false;
    }

    public function isActiveUserAge($birth_date, $pp_end_date = null, $pp_transaction_date = null)
    {
        $user_age = TTDate::getYearDifference($birth_date, $pp_end_date);
        Debug::Text('User Age: ' . $user_age . ' Min: ' . $this->getMinimumUserAge() . ' Max: ' . $this->getMaximumUserAge(), __FILE__, __LINE__, __METHOD__, 10);

        if ($this->getCalculation() == 90) { //CPP
            return $this->isCPPAgeEligible($birth_date, $pp_transaction_date);
        } else {
            if (($this->getMinimumUserAge() == 0 or $user_age >= $this->getMinimumUserAge()) and ($this->getMaximumUserAge() == 0 or $user_age <= $this->getMaximumUserAge())) {
                return true;
            }
        }

        return false;
    }

    public function getMinimumUserAge()
    {
        if (isset($this->data['minimum_user_age'])) {
            return (float)$this->data['minimum_user_age'];
        }

        return false;
    }

    public function getMaximumUserAge()
    {
        if (isset($this->data['maximum_user_age'])) {
            return (float)$this->data['maximum_user_age'];
        }

        return false;
    }

    public function isCPPAgeEligible($birth_date, $pp_transaction_date = null)
    {
        //CPP starts on the first transaction date *after* the month they turn 18, and ends on the first transaction date after they turn 70.
        //  Basically so pro-rating is for whole months rather than partial months.
        //http://www.cra-arc.gc.ca/tx/bsnss/tpcs/pyrll/clcltng/cpp-rpc/prrtng/xmpls-eng.html#xmpl_1
        // If they are 18 on Feb 16, and the PP runs: Feb 2nd to Feb 17th Transact: Feb 25, CPP would NOT be deducted, as they turned 18 in the same month.
        // If they are 18 on Feb 16, and the PP runs: Feb 15nd to Feb 28th Transact: Mar 2nd, CPP would be deducted, as they turned 18 in the month befor they were paid.
        // If they are 70 on Feb 16, and the PP runs: Feb 2nd to Feb 17th Transact: Feb 25, CPP would be deducted, as they turned 18 in the same month.
        // If they are 70 on Feb 16, and the PP runs: Feb 15nd to Feb 28th Transact: Mar 2nd, CPP would NOT be deducted, as they turned 18 in the month befor they were paid.
        if ($pp_transaction_date != '') { //CPP
            $user_age = TTDate::getYearDifference($birth_date, $pp_transaction_date);
            Debug::Text('User Age: ' . $user_age . ' Min: ' . $this->getMinimumUserAge() . ' Max: ' . $this->getMaximumUserAge(), __FILE__, __LINE__, __METHOD__, 10);

            if ($this->getMinimumUserAge() == 0 or $this->getMaximumUserAge() == 0) {
                return true;
            } elseif ($user_age >= ($this->getMinimumUserAge() - 0.25) and $user_age <= ($this->getMaximumUserAge() + 0.25)) {
                //Check to see if they are within a few months of the min/max ages
                if (abs($user_age - $this->getMinimumUserAge()) <= 0.25) {
                    $birth_month = TTDate::getBirthDateAtAge($birth_date, $this->getMinimumUserAge());
                    Debug::Text('  aBirth Date: ' . TTDate::getDate('DATE', $birth_date) . ' Birth Month: ' . TTDate::getDate('DATE+TIME', $birth_month) . ' Transaction Date: ' . TTDate::getDate('DATE+TIME', $pp_transaction_date), __FILE__, __LINE__, __METHOD__, 10);

                    //Start deducting CPP
                    if (TTDate::getEndDayEpoch($pp_transaction_date) > TTDate::getEndMonthEpoch($birth_month)) {
                        Debug::Text('Transaction date is after the end of the birth month, eligible...', __FILE__, __LINE__, __METHOD__, 10);
                        return true;
                    } else {
                        Debug::Text('Transaction date is before the end of the birth month, skipping...', __FILE__, __LINE__, __METHOD__, 10);
                        return false;
                    }
                } elseif (abs($user_age - $this->getMaximumUserAge()) <= 0.25) {
                    $birth_month = TTDate::getBirthDateAtAge($birth_date, $this->getMaximumUserAge());
                    Debug::Text('  bBirth Date: ' . TTDate::getDate('DATE', $birth_date) . ' Birth Month: ' . TTDate::getDate('DATE+TIME', $birth_month) . ' Transaction Date: ' . TTDate::getDate('DATE+TIME', $pp_transaction_date), __FILE__, __LINE__, __METHOD__, 10);

                    //Stop deducting CPP.
                    if (TTDate::getEndDayEpoch($pp_transaction_date) > TTDate::getEndMonthEpoch($birth_month)) {
                        Debug::Text('Transaction date is after the end of the birth month, skipping...', __FILE__, __LINE__, __METHOD__, 10);
                        return false;
                    } else {
                        Debug::Text('Transaction date is before the end of the birth month, eligible...', __FILE__, __LINE__, __METHOD__, 10);
                        return true;
                    }
                } else {
                    Debug::Text('Not within 1 year of Min/Max age, assuming always eligible...', __FILE__, __LINE__, __METHOD__, 10);
                    return true;
                }
            }
        } else {
            Debug::Text('ERROR: Transaction date not specified...', __FILE__, __LINE__, __METHOD__, 10);
        }

        return false;
    }

    public function isCountryCalculationID($calculation_id)
    {
        if (in_array($calculation_id, $this->country_calculation_ids)) {
            return true;
        }

        return false;
    }

    public function isProvinceCalculationID($calculation_id)
    {
        if (in_array($calculation_id, $this->province_calculation_ids)) {
            return true;
        }

        return false;
    }

    public function isDistrictCalculationID($calculation_id)
    {
        if (in_array($calculation_id, $this->district_calculation_ids)) {
            return true;
        }

        return false;
    }

    public function getCombinedCalculationID($calculation_id = null, $country = null, $province = null)
    {
        if ($calculation_id == '') {
            $calculation_id = $this->getCalculation();
        }

        if ($country == '') {
            $country = $this->getCountry();
        }

        if ($province == '') {
            $province = $this->getProvince();
        }

        Debug::Text('Calculation ID: ' . $calculation_id . ' Country: ' . $country . ' Province: ' . $province, __FILE__, __LINE__, __METHOD__, 10);

        if (in_array($calculation_id, $this->country_calculation_ids)
            and in_array($calculation_id, $this->province_calculation_ids)
        ) {
            $id = $calculation_id . '-' . $country . '-' . $province;
        } elseif (in_array($calculation_id, $this->country_calculation_ids)) {
            $id = $calculation_id . '-' . $country;
        } else {
            $id = $calculation_id;
        }

        if (isset($this->calculation_id_fields[$id])) {
            $retval = $this->calculation_id_fields[$id];
        } else {
            $retval = false;
        }

        Debug::Text('Retval: ' . $retval, __FILE__, __LINE__, __METHOD__, 10);

        return $retval;
    }

    public function getCountry()
    {
        if (isset($this->data['country'])) {
            return $this->data['country'];
        }

        return false;
    }

    public function getProvince()
    {
        if (isset($this->data['province'])) {
            return $this->data['province'];
        }

        return false;
    }

    public function setCalculation($value)
    {
        $value = trim($value);

        if ($this->Validator->inArrayKey('calculation_id',
            $value,
            TTi18n::gettext('Incorrect Calculation'),
            $this->getOptions('calculation'))
        ) {
            $this->data['calculation_id'] = $value;

            return true;
        }

        return false;
    }

    public function getCalculationOrder()
    {
        if (isset($this->data['calculation_order'])) {
            return $this->data['calculation_order'];
        }

        return false;
    }

    public function setCalculationOrder($value)
    {
        $value = trim($value);

        if ($this->Validator->isNumeric('calculation_order',
            $value,
            TTi18n::gettext('Invalid Calculation Order')
        )
        ) {
            $this->data['calculation_order'] = $value;

            return true;
        }

        return false;
    }

    public function setCountry($country)
    {
        $country = trim($country);

        $cf = TTnew('CompanyFactory');

        if ($country == ''
            or
            $this->Validator->inArrayKey('country',
                $country,
                TTi18n::gettext('Invalid Country'),
                $cf->getOptions('country'))
        ) {
            $this->data['country'] = $country;

            return true;
        }

        return false;
    }

    public function setProvince($province)
    {
        $province = trim($province);

        Debug::Text('Country: ' . $this->getCountry() . ' Province: ' . $province, __FILE__, __LINE__, __METHOD__, 10);

        $cf = TTnew('CompanyFactory');
        $options_arr = $cf->getOptions('province');
        if (isset($options_arr[$this->getCountry()])) {
            $options = $options_arr[$this->getCountry()];
        } else {
            $options = array();
        }

        if ($province == ''
            or
            $this->Validator->inArrayKey('province',
                $province,
                TTi18n::gettext('Invalid Province/State'),
                $options)
        ) {
            $this->data['province'] = $province;

            return true;
        }

        return false;
    }

    public function getDistrictName()
    {
        $retval = null;

        if (strtolower($this->getDistrict()) == 'all'
            or strtolower($this->getDistrict()) == '00'
        ) {
            if ($this->getUserValue5() != '') {
                $retval = $this->getUserValue5();
            }
        } else {
            $retval = $this->getDistrict();
        }

        return $retval;
    }

    public function getDistrict()
    {
        if (isset($this->data['district'])) {
            return $this->data['district'];
        }

        return false;
    }

    public function getUserValue5()
    {
        if (isset($this->data['user_value5'])) {
            return $this->data['user_value5'];
        }

        return false;
    }

    public function setDistrict($district)
    {
        $district = trim($district);

        Debug::Text('Country: ' . $this->getCountry() . ' District: ' . $district, __FILE__, __LINE__, __METHOD__, 10);

        $cf = TTnew('CompanyFactory');
        $options_arr = $cf->getOptions('district');
        if (isset($options_arr[$this->getCountry()][$this->getProvince()])) {
            $options = $options_arr[$this->getCountry()][$this->getProvince()];
        } else {
            $options = array();
        }

        if (($district == '' or $district == '00')
            or
            $this->Validator->inArrayKey('district',
                $district,
                TTi18n::gettext('Invalid District'),
                $options)
        ) {
            $this->data['district'] = $district;

            return true;
        }

        return false;
    }

    public function getCompanyValue1()
    {
        if (isset($this->data['company_value1'])) {
            return $this->data['company_value1'];
        }

        return false;
    }

    public function setCompanyValue1($value)
    {
        $value = trim($value);

        if ($value == ''
            or
            $this->Validator->isLength('company_value1',
                $value,
                TTi18n::gettext('Company Value 1 is too short or too long'),
                1,
                4096)
        ) { //This is the Custom Formula, some of them need to be quite long.

            $this->data['company_value1'] = $value;

            return true;
        }

        return false;
    }

    public function getCompanyValue1Options()
    {
        //Debug::Text('Calculation: '. $this->getCalculation(), __FILE__, __LINE__, __METHOD__, 10);
        switch ($this->getCalculation()) {
            case 100:
            case 200:
            case 300:
                $options = $this->getOptions('tax_formula_type');
                break;
        }

        if (isset($options)) {
            return $options;
        }

        return false;
    }

    public function setCompanyValue2($value)
    {
        $value = trim($value);

        if ($value == ''
            or
            $this->Validator->isLength('company_value2',
                $value,
                TTi18n::gettext('Company Value 2 is too short or too long'),
                1,
                20)
        ) {
            $this->data['company_value2'] = $value;

            return true;
        }

        return false;
    }

    public function setCompanyValue3($value)
    {
        $value = trim($value);

        if ($value == ''
            or
            $this->Validator->isLength('company_value3',
                $value,
                TTi18n::gettext('Company Value 3 is too short or too long'),
                1,
                20)
        ) {
            $this->data['company_value3'] = $value;

            return true;
        }

        return false;
    }

    public function getCompanyValue4()
    {
        if (isset($this->data['company_value4'])) {
            return $this->data['company_value4'];
        }

        return false;
    }

    public function setCompanyValue4($value)
    {
        $value = trim($value);

        if ($value == ''
            or
            $this->Validator->isLength('company_value4',
                $value,
                TTi18n::gettext('Company Value 4 is too short or too long'),
                1,
                20)
        ) {
            $this->data['company_value4'] = $value;

            return true;
        }

        return false;
    }

    //Used for getting district name on W2's

    public function getCompanyValue5()
    {
        if (isset($this->data['company_value5'])) {
            return $this->data['company_value5'];
        }

        return false;
    }

    public function setCompanyValue5($value)
    {
        $value = trim($value);

        if ($value == ''
            or
            $this->Validator->isLength('company_value5',
                $value,
                TTi18n::gettext('Company Value 5 is too short or too long'),
                1,
                20)
        ) {
            $this->data['company_value5'] = $value;

            return true;
        }

        return false;
    }

    public function getCompanyValue6()
    {
        if (isset($this->data['company_value6'])) {
            return $this->data['company_value6'];
        }

        return false;
    }

    public function setCompanyValue6($value)
    {
        $value = trim($value);

        if ($value == ''
            or
            $this->Validator->isLength('company_value6',
                $value,
                TTi18n::gettext('Company Value 6 is too short or too long'),
                1,
                20)
        ) {
            $this->data['company_value6'] = $value;

            return true;
        }

        return false;
    }

    public function getCompanyValue7()
    {
        if (isset($this->data['company_value7'])) {
            return $this->data['company_value7'];
        }

        return false;
    }

    public function setCompanyValue7($value)
    {
        $value = trim($value);

        if ($value == ''
            or
            $this->Validator->isLength('company_value7',
                $value,
                TTi18n::gettext('Company Value 7 is too short or too long'),
                1,
                20)
        ) {
            $this->data['company_value7'] = $value;

            return true;
        }

        return false;
    }

    public function getCompanyValue8()
    {
        if (isset($this->data['company_value8'])) {
            return $this->data['company_value8'];
        }

        return false;
    }

    public function setCompanyValue8($value)
    {
        $value = trim($value);

        if ($value == ''
            or
            $this->Validator->isLength('company_value8',
                $value,
                TTi18n::gettext('Company Value 8 is too short or too long'),
                1,
                20)
        ) {
            $this->data['company_value8'] = $value;

            return true;
        }

        return false;
    }

    public function getCompanyValue9()
    {
        if (isset($this->data['company_value9'])) {
            return $this->data['company_value9'];
        }

        return false;
    }

    public function setCompanyValue9($value)
    {
        $value = trim($value);

        if ($value == ''
            or
            $this->Validator->isLength('company_value9',
                $value,
                TTi18n::gettext('Company Value 9 is too short or too long'),
                1,
                20)
        ) {
            $this->data['company_value9'] = $value;

            return true;
        }

        return false;
    }

    public function getCompanyValue10()
    {
        if (isset($this->data['company_value10'])) {
            return $this->data['company_value10'];
        }

        return false;
    }

    public function setCompanyValue10($value)
    {
        $value = trim($value);

        if ($value == ''
            or
            $this->Validator->isLength('company_value10',
                $value,
                TTi18n::gettext('Company Value 10 is too short or too long'),
                1,
                20)
        ) {
            $this->data['company_value10'] = $value;

            return true;
        }

        return false;
    }

    public function getUserValue1()
    {
        if (isset($this->data['user_value1'])) {
            return $this->data['user_value1'];
        }

        return false;
    }

    public function setUserValue1($value)
    {
        $value = trim($value);

        if ($value == ''
            or
            $this->Validator->isLength('user_value1',
                $value,
                TTi18n::gettext('User Value 1 is too short or too long'),
                1,
                20)
        ) {
            $this->data['user_value1'] = $value;

            return true;
        }

        return false;
    }

    public function getUserValue2()
    {
        if (isset($this->data['user_value2'])) {
            return $this->data['user_value2'];
        }

        return false;
    }

    public function setUserValue2($value)
    {
        $value = trim($value);

        if ($value == ''
            or
            $this->Validator->isLength('user_value2',
                $value,
                TTi18n::gettext('User Value 2 is too short or too long'),
                1,
                20)
        ) {
            $this->data['user_value2'] = $value;

            return true;
        }

        return false;
    }

    public function getUserValue3()
    {
        if (isset($this->data['user_value3'])) {
            return $this->data['user_value3'];
        }

        return false;
    }

    public function setUserValue3($value)
    {
        $value = trim($value);

        if ($value == ''
            or
            $this->Validator->isLength('user_value3',
                $value,
                TTi18n::gettext('User Value 3 is too short or too long'),
                1,
                20)
        ) {
            $this->data['user_value3'] = $value;

            return true;
        }

        return false;
    }

    public function getUserValue4()
    {
        if (isset($this->data['user_value4'])) {
            return $this->data['user_value4'];
        }

        return false;
    }

    public function setUserValue4($value)
    {
        $value = trim($value);

        if ($value == ''
            or
            $this->Validator->isLength('user_value4',
                $value,
                TTi18n::gettext('User Value 4 is too short or too long'),
                1,
                20)
        ) {
            $this->data['user_value4'] = $value;

            return true;
        }

        return false;
    }

    public function setUserValue5($value)
    {
        $value = trim($value);

        if ($value == ''
            or
            $this->Validator->isLength('user_value5',
                $value,
                TTi18n::gettext('User Value 5 is too short or too long'),
                1,
                20)
        ) {
            $this->data['user_value5'] = $value;

            return true;
        }

        return false;
    }

    public function getUserValue6()
    {
        if (isset($this->data['user_value6'])) {
            return $this->data['user_value6'];
        }

        return false;
    }

    public function setUserValue6($value)
    {
        $value = trim($value);

        if ($value == ''
            or
            $this->Validator->isLength('user_value6',
                $value,
                TTi18n::gettext('User Value 6 is too short or too long'),
                1,
                20)
        ) {
            $this->data['user_value6'] = $value;

            return true;
        }

        return false;
    }

    public function getUserValue7()
    {
        if (isset($this->data['user_value7'])) {
            return $this->data['user_value7'];
        }

        return false;
    }

    public function setUserValue7($value)
    {
        $value = trim($value);

        if ($value == ''
            or
            $this->Validator->isLength('user_value7',
                $value,
                TTi18n::gettext('User Value 7 is too short or too long'),
                1,
                20)
        ) {
            $this->data['user_value7'] = $value;

            return true;
        }

        return false;
    }

    public function getUserValue8()
    {
        if (isset($this->data['user_value8'])) {
            return $this->data['user_value8'];
        }

        return false;
    }

    public function setUserValue8($value)
    {
        $value = trim($value);

        if ($value == ''
            or
            $this->Validator->isLength('user_value8',
                $value,
                TTi18n::gettext('User Value 8 is too short or too long'),
                1,
                20)
        ) {
            $this->data['user_value8'] = $value;

            return true;
        }

        return false;
    }

    public function getUserValue9()
    {
        if (isset($this->data['user_value9'])) {
            return $this->data['user_value9'];
        }

        return false;
    }

    public function setUserValue9($value)
    {
        $value = trim($value);

        if ($value == ''
            or
            $this->Validator->isLength('user_value9',
                $value,
                TTi18n::gettext('User Value 9 is too short or too long'),
                1,
                20)
        ) {
            $this->data['user_value9'] = $value;

            return true;
        }

        return false;
    }

    public function getUserValue10()
    {
        if (isset($this->data['user_value10'])) {
            return $this->data['user_value10'];
        }

        return false;
    }

    public function setUserValue10($value)
    {
        $value = trim($value);

        if ($value == ''
            or
            $this->Validator->isLength('user_value10',
                $value,
                TTi18n::gettext('User Value 10 is too short or too long'),
                1,
                20)
        ) {
            $this->data['user_value10'] = $value;

            return true;
        }

        return false;
    }

    public function getUserValue1Options()
    {
        //Debug::Text('Calculation: '. $this->getCalculation(), __FILE__, __LINE__, __METHOD__, 10);
        switch ($this->getCalculation()) {
            case 100:
                //Debug::Text('Country: '. $this->getCountry(), __FILE__, __LINE__, __METHOD__, 10);
                //if ( $this->getCountry() == 'CA' ) {
                //} else
                if ($this->getCountry() == 'US') {
                    $options = $this->getOptions('federal_filing_status');
                }
                break;
            case 200:
                //Debug::Text('Country: '. $this->getCountry(), __FILE__, __LINE__, __METHOD__, 10);
                //Debug::Text('Province: '. $this->getProvince(), __FILE__, __LINE__, __METHOD__, 10);
                //if ( $this->getCountry() == 'CA' ) {
                //} else
                if ($this->getCountry() == 'US') {
                    $state_options_var = strtolower('state_' . $this->getProvince() . '_filing_status_options');
                    //Debug::Text('Specific State Variable Name: '. $state_options_var, __FILE__, __LINE__, __METHOD__, 10);
                    if (isset($this->$state_options_var)) {
                        //Debug::Text('Specific State Options: ', __FILE__, __LINE__, __METHOD__, 10);
                        $options = $this->getOptions($state_options_var);
                    } elseif ($this->getProvince() == 'IL') {
                        $options = false;
                    } else {
                        //Debug::Text('Default State Options: ', __FILE__, __LINE__, __METHOD__, 10);
                        $options = $this->getOptions('state_filing_status');
                    }
                }

                break;
        }

        if (isset($options)) {
            return $options;
        }

        return false;
    }

    public function setPayStubEntryAccount($id)
    {
        $id = trim($id);

        Debug::Text('ID: ' . $id, __FILE__, __LINE__, __METHOD__, 10);
        $psealf = TTnew('PayStubEntryAccountListFactory');

        if (
            ($id == '' or $id == 0)
            or
            $this->Validator->isResultSetWithRows('pay_stub_entry_account',
                $psealf->getByID($id),
                TTi18n::gettext('Pay Stub Account is invalid')
            )
        ) {
            $this->data['pay_stub_entry_account_id'] = $id;

            return true;
        }

        return false;
    }

    public function getLockUserValue1()
    {
        return $this->fromBool($this->data['lock_user_value1']);
    }

    public function setLockUserValue1($bool)
    {
        $this->data['lock_user_value1'] = $this->toBool($bool);

        return true;
    }

    public function getLockUserValue2()
    {
        return $this->fromBool($this->data['lock_user_value2']);
    }

    public function setLockUserValue2($bool)
    {
        $this->data['lock_user_value2'] = $this->toBool($bool);

        return true;
    }

    public function getLockUserValue3()
    {
        return $this->fromBool($this->data['lock_user_value3']);
    }

    public function setLockUserValue3($bool)
    {
        $this->data['lock_user_value3'] = $this->toBool($bool);

        return true;
    }

    public function getLockUserValue4()
    {
        return $this->fromBool($this->data['lock_user_value4']);
    }

    public function setLockUserValue4($bool)
    {
        $this->data['lock_user_value4'] = $this->toBool($bool);

        return true;
    }

    public function getLockUserValue5()
    {
        return $this->fromBool($this->data['lock_user_value5']);
    }

    public function setLockUserValue5($bool)
    {
        $this->data['lock_user_value5'] = $this->toBool($bool);

        return true;
    }

    public function getLockUserValue6()
    {
        return $this->fromBool($this->data['lock_user_value6']);
    }

    public function setLockUserValue6($bool)
    {
        $this->data['lock_user_value6'] = $this->toBool($bool);

        return true;
    }

    public function getLockUserValue7()
    {
        return $this->fromBool($this->data['lock_user_value7']);
    }

    public function setLockUserValue7($bool)
    {
        $this->data['lock_user_value7'] = $this->toBool($bool);

        return true;
    }

    public function getLockUserValue8()
    {
        return $this->fromBool($this->data['lock_user_value8']);
    }

    public function setLockUserValue8($bool)
    {
        $this->data['lock_user_value8'] = $this->toBool($bool);

        return true;
    }

    public function getLockUserValue9()
    {
        return $this->fromBool($this->data['lock_user_value9']);
    }

    public function setLockUserValue9($bool)
    {
        $this->data['lock_user_value9'] = $this->toBool($bool);

        return true;
    }

    public function getLockUserValue10()
    {
        return $this->fromBool($this->data['lock_user_value10']);
    }

    public function setLockUserValue10($bool)
    {
        $this->data['lock_user_value10'] = $this->toBool($bool);

        return true;
    }

    public function setIncludeAccountAmountType($value)
    {
        $value = trim($value);

        if ($this->Validator->inArrayKey('include_account_amount_type_id',
            $value,
            TTi18n::gettext('Incorrect include account amount type'),
            $this->getOptions('account_amount_type'))
        ) {
            $this->data['include_account_amount_type_id'] = $value;

            return true;
        }

        return false;
    }

    public function setIncludePayStubEntryAccount($ids)
    {
        Debug::text('Setting Include IDs : ', __FILE__, __LINE__, __METHOD__, 10);
        if (!is_array($ids)) {
            $ids = array($ids);
        }

        if (is_array($ids)) {
            $tmp_ids = array();
            if (!$this->isNew()) {
                //If needed, delete mappings first.
                $cdpsealf = TTnew('CompanyDeductionPayStubEntryAccountListFactory');
                $cdpsealf->getByCompanyDeductionIdAndTypeId($this->getId(), 10);

                foreach ($cdpsealf as $obj) {
                    $id = $obj->getPayStubEntryAccount();
                    //Debug::text('ID: '. $id, __FILE__, __LINE__, __METHOD__, 10);

                    //Delete users that are not selected.
                    if (!in_array($id, $ids)) {
                        //Debug::text('Deleting: '. $id, __FILE__, __LINE__, __METHOD__, 10);
                        $obj->Delete();
                    } else {
                        //Save ID's that need to be updated.
                        //Debug::text('NOT Deleting : '. $id, __FILE__, __LINE__, __METHOD__, 10);
                        $tmp_ids[] = $id;
                    }
                }
                unset($id, $obj);
            }

            //Insert new mappings.
            $psealf = TTnew('PayStubEntryAccountListFactory');

            foreach ($ids as $id) {
                if ($id != false and isset($ids) and !in_array($id, $tmp_ids)) {
                    $cdpseaf = TTnew('CompanyDeductionPayStubEntryAccountFactory');
                    $cdpseaf->setCompanyDeduction($this->getId());
                    $cdpseaf->setType(10); //Include
                    $cdpseaf->setPayStubEntryAccount($id);

                    $obj = $psealf->getById($id)->getCurrent();

                    if ($this->Validator->isTrue('include_pay_stub_entry_account',
                        $cdpseaf->Validator->isValid(),
                        TTi18n::gettext('Include Pay Stub Account is invalid') . ' (' . $obj->getName() . ')')
                    ) {
                        $cdpseaf->save();
                    }
                }
            }

            return true;
        }

        Debug::text('No IDs to set.', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function setExcludeAccountAmountType($value)
    {
        $value = trim($value);

        if ($this->Validator->inArrayKey('exclude_account_amount_type_id',
            $value,
            TTi18n::gettext('Incorrect exclude account amount type'),
            $this->getOptions('account_amount_type'))
        ) {
            $this->data['exclude_account_amount_type_id'] = $value;

            return true;
        }

        return false;
    }

    public function setExcludePayStubEntryAccount($ids)
    {
        Debug::text('Setting Exclude IDs : ', __FILE__, __LINE__, __METHOD__, 10);
        if (!is_array($ids)) {
            $ids = array($ids);
        }

        if (is_array($ids)) {
            $tmp_ids = array();
            if (!$this->isNew()) {
                //If needed, delete mappings first.
                $cdpsealf = TTnew('CompanyDeductionPayStubEntryAccountListFactory');
                $cdpsealf->getByCompanyDeductionIdAndTypeId($this->getId(), 20);
                foreach ($cdpsealf as $obj) {
                    $id = $obj->getPayStubEntryAccount();
                    //Debug::text('ID: '. $id, __FILE__, __LINE__, __METHOD__, 10);

                    //Delete users that are not selected.
                    if (!in_array($id, $ids)) {
                        //Debug::text('Deleting: '. $id, __FILE__, __LINE__, __METHOD__, 10);
                        $obj->Delete();
                    } else {
                        //Save ID's that need to be updated.
                        //Debug::text('NOT Deleting : '. $id, __FILE__, __LINE__, __METHOD__, 10);
                        $tmp_ids[] = $id;
                    }
                }
                unset($id, $obj);
            }

            //Insert new mappings.
            //$lf = TTnew( 'UserListFactory' );
            $psealf = TTnew('PayStubEntryAccountListFactory');

            foreach ($ids as $id) {
                if ($id != false and isset($ids) and !in_array($id, $tmp_ids)) {
                    $cdpseaf = TTnew('CompanyDeductionPayStubEntryAccountFactory');
                    $cdpseaf->setCompanyDeduction($this->getId());
                    $cdpseaf->setType(20); //Include
                    $cdpseaf->setPayStubEntryAccount($id);

                    $obj = $psealf->getById($id)->getCurrent();

                    if ($this->Validator->isTrue('exclude_pay_stub_entry_account',
                        $cdpseaf->Validator->isValid(),
                        TTi18n::gettext('Exclude Pay Stub Account is invalid') . ' (' . $obj->getName() . ')')
                    ) {
                        $cdpseaf->save();
                    }
                }
            }

            return true;
        }

        Debug::text('No IDs to set.', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function getUser()
    {
        $udlf = TTnew('UserDeductionListFactory');
        $udlf->getByCompanyIdAndCompanyDeductionId($this->getCompany(), $this->getId());

        $list = array();
        foreach ($udlf as $obj) {
            $list[] = $obj->getUser();
        }

        if (empty($list) == false) {
            return $list;
        }

        return false;
    }

    public function getCompany()
    {
        if (isset($this->data['company_id'])) {
            return (int)$this->data['company_id'];
        }

        return false;
    }

    public function setUser($ids)
    {
        if (!is_array($ids)) {
            $ids = array($ids);
        }

        if (is_array($ids)) {
            $tmp_ids = array();
            if (!$this->isNew()) {
                //If needed, delete mappings first.
                $udlf = TTnew('UserDeductionListFactory');
                $udlf->getByCompanyIdAndCompanyDeductionId($this->getCompany(), $this->getId());
                foreach ($udlf as $obj) {
                    $id = $obj->getUser();
                    //Debug::text('ID: '. $id, __FILE__, __LINE__, __METHOD__, 10);

                    //Delete users that are not selected.
                    if (!in_array($id, $ids)) {
                        //Debug::text('Deleting: '. $id, __FILE__, __LINE__, __METHOD__, 10);
                        $obj->Delete();
                    } else {
                        //Save ID's that need to be updated.
                        //Debug::text('NOT Deleting : '. $id, __FILE__, __LINE__, __METHOD__, 10);
                        $tmp_ids[] = $id;
                    }
                }
                unset($id, $obj);
            }

            //Insert new mappings.
            $ulf = TTnew('UserListFactory');
            foreach ($ids as $id) {
                if ($id != false and isset($ids) and !in_array($id, $tmp_ids)) {
                    $udf = TTnew('UserDeductionFactory');
                    $udf->setUser($id);
                    $udf->setCompanyDeduction($this->getId());

                    $ulf->getById($id);
                    if ($ulf->getRecordCount() > 0) {
                        $obj = $ulf->getCurrent();

                        if ($this->Validator->isTrue('user',
                            $udf->Validator->isValid(),
                            TTi18n::gettext('Selected employee is invalid') . ' (' . $obj->getFullName() . ')')
                        ) {
                            $udf->save();
                        }
                    }
                }
            }

            return true;
        }

        Debug::text('No IDs to set.', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function getTotalUsers()
    {
        $udlf = TTnew('UserDeductionListFactory');
        $udlf->getByCompanyDeductionId($this->getId());
        return $udlf->getRecordCount();
    }

    public function getExpandedPayStubEntryAccountIDs($ids)
    {
        //Debug::Arr($ids, 'Total Gross ID: '. $this->getPayStubEntryAccountLinkObject()->getTotalGross() .' IDs:', __FILE__, __LINE__, __METHOD__, 10);
        $ids = (array)$ids;
        $type_ids = array();

        $total_gross_key = array_search($this->getPayStubEntryAccountLinkObject()->getTotalGross(), $ids);
        if ($total_gross_key !== false) {
            $type_ids[] = 10;
            //$type_ids[] = 60; //Automatically inlcude Advance Earnings here?
            unset($ids[$total_gross_key]);
        }
        unset($total_gross_key);

        $total_employee_deduction_key = array_search($this->getPayStubEntryAccountLinkObject()->getTotalEmployeeDeduction(), $ids);
        if ($total_employee_deduction_key !== false) {
            $type_ids[] = 20;
            unset($ids[$total_employee_deduction_key]);
        }
        unset($total_employee_deduction_key);

        $total_employer_deduction_key = array_search($this->getPayStubEntryAccountLinkObject()->getTotalEmployerDeduction(), $ids);
        if ($total_employer_deduction_key !== false) {
            $type_ids[] = 30;
            unset($ids[$total_employer_deduction_key]);
        }
        unset($total_employer_deduction_key);

        $psea_ids_from_type_ids = array();
        if (empty($type_ids) == false) {
            $psealf = TTnew('PayStubEntryAccountListFactory');
            $psea_ids_from_type_ids = $psealf->getByCompanyIdAndStatusIdAndTypeIdArray($this->getCompany(), array(10, 20), $type_ids, false);
            if (is_array($psea_ids_from_type_ids)) {
                $psea_ids_from_type_ids = array_keys($psea_ids_from_type_ids);
            }
        }

        $retval = array_unique(array_merge($ids, $psea_ids_from_type_ids));

        //Debug::Arr($retval, 'Retval: ', __FILE__, __LINE__, __METHOD__, 10);
        return $retval;
    }

    public function getPayStubEntryAccountLinkObject()
    {
        if (is_object($this->pay_stub_entry_account_link_obj)) {
            return $this->pay_stub_entry_account_link_obj;
        } else {
            $pseallf = TTnew('PayStubEntryAccountLinkListFactory');
            $pseallf->getByCompanyId($this->getCompany());
            if ($pseallf->getRecordCount() > 0) {
                $this->pay_stub_entry_account_link_obj = $pseallf->getCurrent();
                return $this->pay_stub_entry_account_link_obj;
            }

            return false;
        }
    }

    public function isLookbackCalculation()
    {
        if ($this->getCalculation() == 69 and isset($this->length_of_service_multiplier[(int)$this->getCompanyValue3()]) and $this->getCompanyValue2() > 0) {
            return true;
        }

        return false;
    }

    public function getCompanyValue3()
    {
        if (isset($this->data['company_value3'])) {
            return $this->data['company_value3'];
        }

        return false;
    }

    public function getCompanyValue2()
    {
        if (isset($this->data['company_value2'])) {
            return $this->data['company_value2'];
        }

        return false;
    }

    public function getLookbackCalculationPayStubAmount($include_account_amount_type_id = null, $exclude_account_amount_type_id = null)
    {
        $amount = 0;
        if (isset($this->lookback_pay_stub_lf) and $this->lookback_pay_stub_lf->getRecordCount() > 0) {
            foreach ($this->lookback_pay_stub_lf as $pay_stub_obj) {
                $pay_stub_obj->loadCurrentPayStubEntries();
                $amount = bcadd($amount, $this->getCalculationPayStubAmount($pay_stub_obj, $include_account_amount_type_id, $exclude_account_amount_type_id));
            }
        }

        Debug::text('Amount: ' . $amount, __FILE__, __LINE__, __METHOD__, 10);
        return $amount;
    }

    public function getCalculationPayStubAmount($pay_stub_obj, $include_account_amount_type_id = null, $exclude_account_amount_type_id = null)
    {
        if (!is_object($pay_stub_obj)) {
            return false;
        }

        $include_ids = $this->getIncludePayStubEntryAccount();
        $exclude_ids = $this->getExcludePayStubEntryAccount();

        $is_included = false;
        $is_excluded = false;

        //This totals up the includes, and minuses the excludes.
        if (isset($include_account_amount_type_id)) {
            $include_account_amount_type = $include_account_amount_type_id;
            $is_included = true;
        } else {
            $include_account_amount_type = $this->getIncludeAccountAmountType();
        }

        if (isset($exclude_account_amount_type_id)) {
            $exclude_account_amount_type = $exclude_account_amount_type_id;
            $is_excluded = true;
        } else {
            $exclude_account_amount_type = $this->getExcludeAccountAmountType();
        }

        $include = $this->getPayStubEntryAmountSum($pay_stub_obj, $include_ids, $this->getAccountAmountTypePSEntriesMap($include_account_amount_type), $this->getAccountAmountTypeMap($include_account_amount_type));
        $exclude = $this->getPayStubEntryAmountSum($pay_stub_obj, $exclude_ids, $this->getAccountAmountTypePSEntriesMap($exclude_account_amount_type), $this->getAccountAmountTypeMap($exclude_account_amount_type));
        Debug::text('Include Amount: ' . $include . ' Exclude Amount: ' . $exclude, __FILE__, __LINE__, __METHOD__, 10);

        //Allow negative values to be returned, as we need to do calculation on accruals and such that may be negative values.
        if ($is_included == true and $is_excluded == true) {
            $amount = bcsub($include, $exclude);
        } elseif ($is_included == true) {
            $amount = $include;
        } elseif ($is_excluded == true) {
            $amount = $exclude;
        } else {
            $amount = bcsub($include, $exclude);
        }

        Debug::text('Amount: ' . $amount, __FILE__, __LINE__, __METHOD__, 10);

        return $amount;
    }

    public function getIncludePayStubEntryAccount()
    {
        $cache_id = 'include_pay_stub_entry-' . $this->getId();
        $list = $this->getCache($cache_id);
        if ($list === false) {
            //Debug::text('Caching Include IDs: '. $this->getId(), __FILE__, __LINE__, __METHOD__, 10);
            $cdpsealf = TTnew('CompanyDeductionPayStubEntryAccountListFactory');
            $cdpsealf->getByCompanyDeductionIdAndTypeId($this->getId(), 10);

            $list = null;
            foreach ($cdpsealf as $obj) {
                $list[] = $obj->getPayStubEntryAccount();
            }
            $this->saveCache($list, $cache_id);
        } //else { //Debug::text('Reading Cached Include IDs: '. $this->getId(), __FILE__, __LINE__, __METHOD__, 10);
        //Debug::Arr($list, 'Include IDs: ', __FILE__, __LINE__, __METHOD__, 10);

        if (isset($list) and is_array($list)) {
            return $list;
        }

        return false;
    }

    public function getExcludePayStubEntryAccount()
    {
        $cache_id = 'exclude_pay_stub_entry-' . $this->getId();
        $list = $this->getCache($cache_id);
        if ($list === false) {
            //Debug::text('Caching Exclude IDs: '. $this->getId(), __FILE__, __LINE__, __METHOD__, 10);
            $cdpsealf = TTnew('CompanyDeductionPayStubEntryAccountListFactory');
            $cdpsealf->getByCompanyDeductionIdAndTypeId($this->getId(), 20);

            $list = null;
            foreach ($cdpsealf as $obj) {
                $list[] = $obj->getPayStubEntryAccount();
            }

            $this->saveCache($list, $cache_id);
        } //else { //Debug::text('Reading Cached Exclude IDs: '. $this->getId(), __FILE__, __LINE__, __METHOD__, 10);

        if (isset($list) and is_array($list)) {
            return $list;
        }

        return false;
    }

    public function getIncludeAccountAmountType()
    {
        if (isset($this->data['include_account_amount_type_id'])) {
            return (int)$this->data['include_account_amount_type_id'];
        }

        return false;
    }

    public function getExcludeAccountAmountType()
    {
        if (isset($this->data['exclude_account_amount_type_id'])) {
            return (int)$this->data['exclude_account_amount_type_id'];
        }

        return false;
    }

    public function getPayStubEntryAmountSum($pay_stub_obj, $ids, $ps_entries = 'current', $return_value = 'amount')
    {
        if (!is_object($pay_stub_obj)) {
            return false;
        }

        if (!is_array($ids)) {
            return false;
        }

        $type_ids = array();

        //Get Linked accounts so we know which IDs are totals.
        $total_gross_key = array_search($this->getPayStubEntryAccountLinkObject()->getTotalGross(), $ids);

        if ($total_gross_key !== false) {
            $type_ids[] = 10;
            $type_ids[] = 60; //Automatically inlcude Advance Earnings here?
            unset($ids[$total_gross_key]);
        }
        unset($total_gross_key);

        $total_employee_deduction_key = array_search($this->getPayStubEntryAccountLinkObject()->getTotalEmployeeDeduction(), $ids);
        if ($total_employee_deduction_key !== false) {
            $type_ids[] = 20;
            unset($ids[$total_employee_deduction_key]);
        }
        unset($total_employee_deduction_key);

        $total_employer_deduction_key = array_search($this->getPayStubEntryAccountLinkObject()->getTotalEmployerDeduction(), $ids);
        if ($total_employer_deduction_key !== false) {
            $type_ids[] = 30;
            unset($ids[$total_employer_deduction_key]);
        }
        unset($total_employer_deduction_key);

        $type_amount_arr = array();
        $type_amount_arr[$return_value] = 0;

        if (empty($type_ids) == false) {
            $type_amount_arr = $pay_stub_obj->getSumByEntriesArrayAndTypeIDAndPayStubAccountID($ps_entries, $type_ids);
        }

        $amount_arr[$return_value] = 0;
        if (count($ids) > 0) {
            //Still other IDs left to total.
            $amount_arr = $pay_stub_obj->getSumByEntriesArrayAndTypeIDAndPayStubAccountID($ps_entries, null, $ids);
        }

        $retval = bcadd($type_amount_arr[$return_value], $amount_arr[$return_value]);

        Debug::text('Type Amount: ' . $type_amount_arr[$return_value] . ' Regular Amount: ' . $amount_arr[$return_value] . ' Total: ' . $retval . ' Return Value: ' . $return_value . ' PS Entries: ' . $ps_entries, __FILE__, __LINE__, __METHOD__, 10);

        return $retval;
    }

    public function getAccountAmountTypePSEntriesMap($id)
    {
        if (isset($this->account_amount_type_ps_entries_map[$id])) {
            return $this->account_amount_type_ps_entries_map[$id];
        }

        Debug::text('Unable to find Account Amount PS Entries mapping... ID: ' . $id, __FILE__, __LINE__, __METHOD__, 10);

        return 'current'; //Default to current entries.
    }

    public function getAccountAmountTypeMap($id)
    {
        if (isset($this->account_amount_type_map[$id])) {
            return $this->account_amount_type_map[$id];
        }

        Debug::text('Unable to find Account Amount mapping... ID: ' . $id, __FILE__, __LINE__, __METHOD__, 10);

        return 'amount'; //Default to amount.
    }

    public function getLookbackPayStubs($user_id, $pay_period_obj)
    {
        $lookback_dates = $this->getLookbackStartAndEndDates($pay_period_obj);

        $pslf = TTNew('PayStubListFactory');
        $this->lookback_pay_stub_lf = $pslf->getByUserIdAndStartDateAndEndDate($user_id, $lookback_dates['start_date'], $lookback_dates['end_date']);

        $retarr = array();
        if ($this->lookback_pay_stub_lf->getRecordCount() > 0) {
            //Get lookback first pay and last pay period dates.
            $retarr['first_pay_stub_start_date'] = $this->lookback_pay_stub_lf->getCurrent()->getStartDate();
            $retarr['first_pay_stub_end_date'] = $this->lookback_pay_stub_lf->getCurrent()->getEndDate();
            $retarr['first_pay_stub_transaction_date'] = $this->lookback_pay_stub_lf->getCurrent()->getTransactionDate();

            $this->lookback_pay_stub_lf->rs->MoveLast();

            $retarr['last_pay_stub_start_date'] = $this->lookback_pay_stub_lf->getCurrent()->getStartDate();
            $retarr['last_pay_stub_end_date'] = $this->lookback_pay_stub_lf->getCurrent()->getEndDate();
            $retarr['last_pay_stub_transaction_date'] = $this->lookback_pay_stub_lf->getCurrent()->getTransactionDate();

            $retarr['total_pay_stubs'] = $this->lookback_pay_stub_lf->getRecordCount();
            Debug::text('Total Pay Stubs: ' . $retarr['total_pay_stubs'] . ' First Transaction Date: ' . TTDate::getDate('DATE+TIME', $retarr['first_pay_stub_transaction_date']) . ' Last Transaction Date: ' . TTDate::getDate('DATE+TIME', $retarr['last_pay_stub_transaction_date']), __FILE__, __LINE__, __METHOD__, 10);

            $this->lookback_pay_stub_lf->rs->MoveFirst();
        } else {
            $retarr = false;
        }

        return $retarr;
    }

    public function getLookbackStartAndEndDates($pay_period_obj)
    {
        $retarr = array(
            'start_date' => false,
            //Make sure we don't include the current transaction date, as we can always access the current amounts with other variables.
            //This also allows us to calculate lookbacks first and avoid circular dependancies in other calculations.
            'end_date' => TTDate::getEndDayEpoch(((int)$pay_period_obj->getTransactionDate() - 86400)),
        );
        if ($this->getCompanyValue3() == 100) { //Pay Periods
            //Not implemented for now, as it has many issues, things like gaps between pay periods, employees switching between pay period schedules, etc...
            //We could just count the number of pay stubs, but this has issues with employees leaving and returning and such.
            unset($pay_period_obj); //Satisfy Coding Standards
        } else {
            $length_of_service_days = bcmul((float)$this->getCompanyValue2(), $this->length_of_service_multiplier[(int)$this->getCompanyValue3()], 4);
            $retarr['start_date'] = TTDate::getBeginDayEpoch(((int)$pay_period_obj->getTransactionDate() - ($length_of_service_days * 86400)));
        }

        Debug::text('Start Date: ' . TTDate::getDate('DATE+TIME', $retarr['start_date']) . ' End Date: ' . TTDate::getDate('DATE+TIME', $retarr['end_date']), __FILE__, __LINE__, __METHOD__, 10);
        return $retarr;
    }

    public function getCalculationYTDAmount($pay_stub_obj)
    {
        if (!is_object($pay_stub_obj)) {
            return false;
        }

        //This totals up the includes, and minuses the excludes.
        $include_ids = $this->getIncludePayStubEntryAccount();
        $exclude_ids = $this->getExcludePayStubEntryAccount();

        //Use current YTD amount because if we only include previous pay stub YTD amounts we won't include YTD adjustment PS amendments on the current PS.
        $include = $this->getPayStubEntryAmountSum($pay_stub_obj, $include_ids, 'previous+ytd_adjustment', 'ytd_amount');
        $exclude = $this->getPayStubEntryAmountSum($pay_stub_obj, $exclude_ids, 'previous+ytd_adjustment', 'ytd_amount');

        $amount = bcsub($include, $exclude);

        if ($amount < 0) {
            $amount = 0;
        }

        Debug::text('Amount: ' . $amount, __FILE__, __LINE__, __METHOD__, 10);

        return $amount;
    }

    public function getPayStubEntryAccountYTDAmount($pay_stub_obj)
    {
        if (!is_object($pay_stub_obj)) {
            return false;
        }

        //Use current YTD amount because if we only include previous pay stub YTD amounts we won't include YTD adjustment PS amendments on the current PS.
        $previous_amount = $this->getPayStubEntryAmountSum($pay_stub_obj, array($this->getPayStubEntryAccount()), 'previous+ytd_adjustment', 'ytd_amount');
        $current_amount = $this->getPayStubEntryAmountSum($pay_stub_obj, array($this->getPayStubEntryAccount()), 'current', 'amount');

        $amount = bcadd($previous_amount, $current_amount);
        if ($amount < 0) {
            $amount = 0;
        }

        Debug::text('Amount: ' . $amount, __FILE__, __LINE__, __METHOD__, 10);

        return $amount;
    }

    public function getJavaScriptArrays()
    {
        $output = 'var fields = ' . Misc::getJSArray($this->calculation_id_fields, 'fields', true);

        $output .= 'var country_calculation_ids = ' . Misc::getJSArray($this->country_calculation_ids);
        $output .= 'var province_calculation_ids = ' . Misc::getJSArray($this->province_calculation_ids);
        $output .= 'var district_calculation_ids = ' . Misc::getJSArray($this->district_calculation_ids);

        return $output;
    }

    public function Validate($ignore_warning = true)
    {
        if ($this->getName() == '') {
            $this->Validator->isTrue('name',
                false,
                TTi18n::gettext('Name not specified'));
        }

        return true;
    }

    public function getName()
    {
        if (isset($this->data['name'])) {
            return $this->data['name'];
        }

        return false;
    }

    public function updateCompanyDeductionForTaxYear($date)
    {
        require_once(Environment::getBasePath() . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'payroll_deduction' . DIRECTORY_SEPARATOR . 'PayrollDeduction.class.php');

        $c_obj = $this->getCompanyObject();
        if (is_object($c_obj) and $c_obj->getStatus() != 30) {
            Debug::text('Company: ' . $c_obj->getName() . ' Date: ' . TTDate::getDate('DATE+TIME', $date), __FILE__, __LINE__, __METHOD__, 9);
            $cdlf = TTnew('CompanyDeductionListFactory');
            $cdlf->getAPISearchByCompanyIdAndArrayCriteria($c_obj->getID(), array('calculation_id' => array(100, 200), 'country' => 'CA'));
            if ($cdlf->getRecordCount() > 0) {
                foreach ($cdlf as $cd_obj) {
                    $pd_obj = new PayrollDeduction($cd_obj->getCountry(), $cd_obj->getProvince());
                    $pd_obj->setDate($date);

                    if ($cd_obj->getCalculation() == 100) { //Federal
                        $pd_obj->setFederalTotalClaimAmount($cd_obj->getUserValue1());
                        $claim_amount = $pd_obj->getFederalTotalClaimAmount();
                    } elseif ($cd_obj->getCalculation() == 200) { //Provincial
                        $pd_obj->setProvincialTotalClaimAmount($cd_obj->getUserValue1());
                        $claim_amount = $pd_obj->getProvincialTotalClaimAmount();
                    }

                    if ((float)$cd_obj->getUserValue1() != (float)$claim_amount) {
                        Debug::text('Updating claim amounts... Old: ' . $cd_obj->getUserValue1() . ' New: ' . $claim_amount, __FILE__, __LINE__, __METHOD__, 9);
                        //Use a SQL query instead of modifying the CompanyDeduction class, as that can cause errors when we add columns to the table later on.
                        $query = 'UPDATE ' . $cd_obj->getTable() . ' set user_value1 = ' . (float)$claim_amount . ' where id = ' . (int)$cd_obj->getId();
                        $this->db->Execute($query);
                    } else {
                        Debug::text('Amount matches, no changes needed... Old: ' . $cd_obj->getUserValue1() . ' New: ' . $claim_amount, __FILE__, __LINE__, __METHOD__, 9);
                    }
                }
                Debug::text('Done updating claim amounts...', __FILE__, __LINE__, __METHOD__, 9);
            }
        }

        return true;
    }

    //
    // Lookback functions.
    //

    public function getCompanyObject()
    {
        return $this->getGenericObject('CompanyListFactory', $this->getCompany(), 'company_obj');
    }

    public function preSave()
    {
        if ($this->getStatus() == '') {
            $this->setStatus(10);
        }
        if ($this->getType() == '') {
            $this->setType(10);
        }
        if ($this->getName() == '') {
            $this->setName('');
        }

        //Set Length of service in days.
        $this->setMinimumLengthOfServiceDays($this->getMinimumLengthOfService());
        $this->setMaximumLengthOfServiceDays($this->getMaximumLengthOfService());

        if ($this->getApplyFrequency() == '') {
            $this->setApplyFrequency(10); //Each pay period.
        }

        return true;
    }

    //Handle look back period, which is always based on the transaction date *before* the current pay periods transaction date.

    public function getStatus()
    {
        if (isset($this->data['status_id'])) {
            return (int)$this->data['status_id'];
        }
        return false;
    }

    public function setStatus($status)
    {
        $status = trim($status);

        if ($this->Validator->inArrayKey('status_id',
            $status,
            TTi18n::gettext('Incorrect Status'),
            $this->getOptions('status'))
        ) {
            $this->data['status_id'] = $status;

            return true;
        }

        return false;
    }

    public function getType()
    {
        if (isset($this->data['type_id'])) {
            return (int)$this->data['type_id'];
        }

        return false;
    }

    public function setType($type)
    {
        $type = trim($type);

        if ($this->Validator->inArrayKey('type_id',
            $type,
            TTi18n::gettext('Incorrect Type'),
            $this->getOptions('type'))
        ) {
            $this->data['type_id'] = $type;

            return true;
        }

        return false;
    }

    public function setName($value)
    {
        $value = trim($value);

        if (
            $this->Validator->isLength('name',
                $value,
                TTi18n::gettext('Name is too short or too long'),
                2,
                100)
            and
            $this->Validator->isTrue('name',
                $this->isUniqueName($value),
                TTi18n::gettext('Name is already in use')
            )
        ) {
            $this->data['name'] = $value;

            return true;
        }

        return false;
    }

    public function isUniqueName($name)
    {
        $name = trim($name);
        if ($name == '') {
            return false;
        }

        $ph = array(
            'company_id' => (int)$this->getCompany(),
            'name' => TTi18n::strtolower($name),
        );

        $query = 'select id from ' . $this->getTable() . ' where company_id = ? AND lower(name) = ? AND deleted=0';
        $id = $this->db->GetOne($query, $ph);
        //Debug::Arr($id, 'Unique Pay Stub Account: '. $name, __FILE__, __LINE__, __METHOD__, 10);

        if ($id === false) {
            return true;
        } else {
            if ($id == $this->getId()) {
                return true;
            }
        }

        return false;
    }

    public function setMinimumLengthOfServiceDays($int)
    {
        $int = (float)trim($int);

        Debug::text('aLength of Service Days: ' . $int, __FILE__, __LINE__, __METHOD__, 10);

        if ($int >= 0
            and
            $this->Validator->isFloat('minimum_length_of_service',
                $int,
                TTi18n::gettext('Minimum length of service is invalid'))
        ) {
            $this->data['minimum_length_of_service_days'] = bcmul($int, $this->length_of_service_multiplier[(int)$this->getMinimumLengthOfServiceUnit()], 4);

            return true;
        }

        return false;
    }

    public function setMaximumLengthOfServiceDays($int)
    {
        $int = (float)trim($int);

        Debug::text('aLength of Service Days: ' . $int, __FILE__, __LINE__, __METHOD__, 10);

        if ($int >= 0
            and
            $this->Validator->isFloat('maximum_length_of_service',
                $int,
                TTi18n::gettext('Maximum length of service is invalid'))
        ) {
            $this->data['maximum_length_of_service_days'] = bcmul($int, $this->length_of_service_multiplier[(int)$this->getMaximumLengthOfServiceUnit()], 4);

            return true;
        }

        return false;
    }

    public function setApplyFrequency($value)
    {
        $value = trim($value);

        if (
        $this->Validator->inArrayKey('apply_frequency_id',
            $value,
            TTi18n::gettext('Incorrect frequency'),
            $this->getOptions('apply_frequency'))
        ) {
            $this->data['apply_frequency_id'] = $value;

            return true;
        }

        return false;
    }

    public function postSave()
    {
        $this->removeCache($this->getId());
        $this->removeCache('include_pay_stub_entry-' . $this->getId());
        $this->removeCache('exclude_pay_stub_entry-' . $this->getId());

        if ($this->getDeleted() == true) {
            //Check if any users are assigned to this, if so, delete mappings.
            $udlf = TTnew('UserDeductionListFactory');

            $udlf->StartTransaction();
            $udlf->getByCompanyIdAndCompanyDeductionId($this->getCompany(), $this->getId());
            if ($udlf->getRecordCount()) {
                foreach ($udlf as $ud_obj) {
                    $ud_obj->setDeleted(true);
                    if ($ud_obj->isValid()) {
                        $ud_obj->Save();
                    }
                }
            }
            $udlf->CommitTransaction();
        }

        return true;
    }

    public function setObjectFromArray($data)
    {
        if (is_array($data)) {
            $variable_function_map = $this->getVariableToFunctionMap();
            foreach ($variable_function_map as $key => $function) {
                if (isset($data[$key])) {
                    $function = 'set' . $function;
                    switch ($key) {
                        case 'start_date':
                        case 'end_date':
                            if (method_exists($this, $function)) {
                                $this->$function(TTDate::parseDateTime($data[$key]));
                            }
                            break;
                        default:
                            if (method_exists($this, $function)) {
                                $this->$function($data[$key]);
                            }
                            break;
                    }
                }
            }

            $this->setCreatedAndUpdatedColumns($data);

            return true;
        }

        return false;
    }

    public function getObjectAsArray($include_columns = null, $permission_children_ids = false, $include_user_id = false)
    {
        $data = array();
        $variable_function_map = $this->getVariableToFunctionMap();
        if (is_array($variable_function_map)) {
            foreach ($variable_function_map as $variable => $function_stub) {
                if ($include_columns == null or (isset($include_columns[$variable]) and $include_columns[$variable] == true)) {
                    $function = 'get' . $function_stub;
                    switch ($variable) {
                        case 'status':
                        case 'type':
                        case 'calculation':
                            $function = 'get' . $variable;
                            if (method_exists($this, $function)) {
                                $data[$variable] = Option::getByKey($this->$function(), $this->getOptions($variable));
                            }
                            break;
                        case 'start_date':
                        case 'end_date':
                            if (method_exists($this, $function)) {
                                $data[$variable] = TTDate::getAPIDate('DATE', $this->$function());
                            }
                            break;
                        default:
                            if (method_exists($this, $function)) {
                                $data[$variable] = $this->$function();
                            }
                            break;
                    }
                }
            }
            //When using the Edit Employee -> Tax tab, API::getCompanyDeduction() is called with include_user_id filter,
            //Since we only return the company deduction records, we have to pass this in separately so we can determine
            //if a child is assigned to a company deduction record.
            $this->getPermissionColumns($data, $include_user_id, $this->getCreatedBy(), $permission_children_ids, $include_columns);
            $this->getCreatedAndUpdatedColumns($data, $include_columns);
        }

        return $data;
    }

    public function addLog($log_action)
    {
        return TTLog::addEntry($this->getId(), $log_action, TTi18n::getText('Tax / Deduction'), null, $this->getTable(), $this);
    }
}
