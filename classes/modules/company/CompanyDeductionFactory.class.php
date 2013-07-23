<?php
/*********************************************************************************
 * TimeTrex is a Payroll and Time Management program developed by
 * TimeTrex Software Inc. Copyright (C) 2003 - 2013 TimeTrex Software Inc.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License version 3 as published by
 * the Free Software Foundation with the addition of the following permission
 * added to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED
 * WORK IN WHICH THE COPYRIGHT IS OWNED BY TIMETREX, TIMETREX DISCLAIMS THE
 * WARRANTY OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE.  See the GNU Affero General Public License for more
 * details.
 *
 * You should have received a copy of the GNU Affero General Public License along
 * with this program; if not, see http://www.gnu.org/licenses or write to the Free
 * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301 USA.
 *
 * You can contact TimeTrex headquarters at Unit 22 - 2475 Dobbin Rd. Suite
 * #292 Westbank, BC V4T 2E9, Canada or at email address info@timetrex.com.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License
 * version 3, these Appropriate Legal Notices must retain the display of the
 * "Powered by TimeTrex" logo. If the display of the logo is not reasonably
 * feasible for technical reasons, the Appropriate Legal Notices must display
 * the words "Powered by TimeTrex".
 ********************************************************************************/
/*
 * $Revision: 9881 $
 * $Id: CompanyDeductionFactory.class.php 9881 2013-05-14 17:48:07Z ipso $
 * $Date: 2013-05-14 10:48:07 -0700 (Tue, 14 May 2013) $
 */

/**
 * @package Modules\Company
 */
class CompanyDeductionFactory extends Factory {
	protected $table = 'company_deduction';
	protected $pk_sequence_name = 'company_deduction_id_seq'; //PK Sequence name

	var $pay_stub_entry_account_link_obj = NULL;
	var $pay_stub_entry_account_obj = NULL;

	var $country_calculation_ids = array('100','200','300');
	var	$province_calculation_ids = array('200','300');
	var $district_calculation_ids = array('300');
	var $calculation_id_fields = array(
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

	function _getFactoryOptions( $name ) {
		$retval = NULL;
		switch( $name ) {
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

										80 => TTi18n::gettext('US - Advance EIC Formula'),
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
			case 'account_amount_type':
				$retval = array(
										10 => TTi18n::gettext('Amount'),
										20 => TTi18n::gettext('Units/Hours'),
										30 => TTi18n::gettext('YTD Amount'),
										40 => TTi18n::gettext('YTD Units/Hours'),
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
														20 => TTi18n::gettext('Married or Qualified Widow(er)'),
														30 => TTi18n::gettext('Head of Household'),
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
														10 => TTi18n::gettext('Status "S" Claiming $1500'),
														20 => TTi18n::gettext('Status "M" Claiming $3000'),
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

                                                        '-1250-#employee_termination_date#' => TTi18n::getText('Employee Termination Date'),
                                                        '-1260-#employee_birth_date#' => TTi18n::getText('Employee Birth Date'),
                                                        '-1270-#employee_hire_date#' => TTi18n::getText('Employee Hire Date'),
                                    );
                break;
			case 'columns':
				$retval = array(
										'-1010-status' => TTi18n::gettext('Status'),
										'-1020-type' => TTi18n::gettext('Type'),
										'-1030-name' => TTi18n::gettext('Name'),
										'-1040-calculation' => TTi18n::gettext('Calculation'),

										'-1040-start_date' => TTi18n::gettext('Start Date'),
										'-1040-end_Date_date' => TTi18n::gettext('End Date'),

										'-2000-created_by' => TTi18n::gettext('Created By'),
										'-2010-created_date' => TTi18n::gettext('Created Date'),
										'-2020-updated_by' => TTi18n::gettext('Updated By'),
										'-2030-updated_date' => TTi18n::gettext('Updated Date'),
							);
				break;
			case 'list_columns':
				$retval = Misc::arrayIntersectByKey( $this->getOptions('default_display_columns'), Misc::trimSortPrefix( $this->getOptions('columns') ) );
				break;
			case 'default_display_columns': //Columns that are displayed by default.
				$retval = array(
								'status',
								'type',
								'name',
								'calculation',
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

	function _getVariableToFunctionMap( $data ) {
		$variable_function_map = array(
										'id' => 'ID',
										'company_id' => 'Company',
										'status_id' => 'Status',
										'status' => FALSE,
										'type_id' => 'Type',
										'type' => FALSE,
										'name' => 'Name',
										'start_date' => 'StartDate',
										'end_date' => 'EndDate',
										'minimum_length_of_service_unit_id' => 'MinimumLengthOfServiceUnit', //Must go before minimum_length_of_service_days, for calculations to not fail.
										'minimum_length_of_service_days' => 'MinimumLengthOfServiceDays',
										'minimum_length_of_service' => 'MinimumLengthOfService',
										'maximum_length_of_service_unit_id' => 'MaximumLengthOfServiceUnit', //Must go before maximum_length_of_service_days, for calculations to not fail.
										'maximum_length_of_service_days' => 'MaximumLengthOfServiceDays',
										'maximum_length_of_service' => 'MaximumLengthOfService',
										'minimum_user_age' => 'MinimumUserAge',
										'maximum_user_age' => 'MaximumUserAge',
										'calculation_id' => 'Calculation',
										'calculation' => FALSE,
										'calculation_order' => 'CalculationOrder',
										'country' => 'Country',
										'province' => 'Province',
										'district' => 'District',
										'company_value1' => 'CompanyValue1',
										'company_value2' => 'CompanyValue2',
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
										'deleted' => 'Deleted',
										);
		return $variable_function_map;
	}

	function getPayStubEntryAccountLinkObject() {
		if ( is_object($this->pay_stub_entry_account_link_obj) ) {
			return $this->pay_stub_entry_account_link_obj;
		} else {
			$pseallf = TTnew( 'PayStubEntryAccountLinkListFactory' );
			$pseallf->getByCompanyId( $this->getCompany() );
			if ( $pseallf->getRecordCount() > 0 ) {
				$this->pay_stub_entry_account_link_obj = $pseallf->getCurrent();
				return $this->pay_stub_entry_account_link_obj;
			}

			return FALSE;
		}
	}

	function getPayStubEntryAccountObject() {
		if ( is_object($this->pay_stub_entry_account_obj) ) {
			return $this->pay_stub_entry_account_obj;
		} else {
			$psealf = TTnew( 'PayStubEntryAccountListFactory' );
			$psealf->getById( $this->getPayStubEntryAccount() );
			if ( $psealf->getRecordCount() > 0 ) {
				$this->pay_stub_entry_account_obj = $psealf->getCurrent();
				return $this->pay_stub_entry_account_obj;
			}

			return FALSE;
		}
	}

	function getCompany() {
		if ( isset($this->data['company_id']) ) {
			return $this->data['company_id'];
		}

		return FALSE;
	}
	function setCompany($id) {
		$id = trim($id);

		Debug::Text('Company ID: '. $id, __FILE__, __LINE__, __METHOD__,10);
		$clf = TTnew( 'CompanyListFactory' );

		if ( $this->Validator->isResultSetWithRows(	'company',
													$clf->getByID($id),
													TTi18n::gettext('Company is invalid')
													) ) {

			$this->data['company_id'] = $id;

			return TRUE;
		}

		return FALSE;
	}

	function getStatus() {
		return (int)$this->data['status_id'];
	}
	function setStatus($status) {
		$status = trim($status);

		$key = Option::getByValue($status, $this->getOptions('status') );
		if ($key !== FALSE) {
			$status = $key;
		}

		if ( $this->Validator->inArrayKey(	'status',
											$status,
											TTi18n::gettext('Incorrect Status'),
											$this->getOptions('status')) ) {

			$this->data['status_id'] = $status;

			return FALSE;
		}

		return FALSE;
	}

	function getType() {
		if ( isset($this->data['type_id']) ) {
			return $this->data['type_id'];
		}

		return FALSE;
	}
	function setType($type) {
		$type = trim($type);

		$key = Option::getByValue($type, $this->getOptions('type') );
		if ($key !== FALSE) {
			$type = $key;
		}

		if ( $this->Validator->inArrayKey(	'type',
											$type,
											TTi18n::gettext('Incorrect Type'),
											$this->getOptions('type')) ) {

			$this->data['type_id'] = $type;

			return FALSE;
		}

		return FALSE;
	}

	function isUniqueName($name) {
		$ph = array(
					'company_id' => $this->getCompany(),
					'name' => $name,
					);

		$query = 'select id from '. $this->getTable() .' where company_id = ? AND  name = ? AND deleted=0';
		$id = $this->db->GetOne($query, $ph);
		//Debug::Arr($id,'Unique Pay Stub Account: '. $name, __FILE__, __LINE__, __METHOD__,10);

		if ( $id === FALSE ) {
			return TRUE;
		} else {
			if ($id == $this->getId() ) {
				return TRUE;
			}
		}

		return FALSE;
	}
	function getName() {
		if ( isset($this->data['name']) ) {
			return $this->data['name'];
		}

		return FALSE;
	}
	function setName($value) {
		$value = trim($value);

		if 	(
					$this->Validator->isLength(		'name',
													$value,
													TTi18n::gettext('Name is too short or too long'),
													2,
													100)
				AND
				$this->Validator->isTrue(				'name',
														$this->isUniqueName($value),
														TTi18n::gettext('Name is already in use')
													)
													) {

			$this->data['name'] = $value;

			return TRUE;
		}

		return FALSE;
	}

	function getStartDate( $raw = FALSE ) {
		if ( isset($this->data['start_date']) ) {
			if ( $raw === TRUE ) {
				return $this->data['start_date'];
			} else {
				return TTDate::strtotime( $this->data['start_date'] );
			}
		}

		return FALSE;
	}
	function setStartDate($epoch) {
		$epoch = trim($epoch);

		if ( $epoch == '' ){
			$epoch = NULL;
		}

		if 	(
				$epoch == NULL
				OR
				$this->Validator->isDate(		'start_date',
												$epoch,
												TTi18n::gettext('Incorrect start date'))
			) {

			$this->data['start_date'] = $epoch;

			return TRUE;
		}

		return FALSE;
	}

	function getEndDate( $raw = FALSE ) {
		if ( isset($this->data['end_date']) ) {
			if ( $raw === TRUE ) {
				return $this->data['end_date'];
			} else {
				return TTDate::strtotime( $this->data['end_date'] );
			}
		}

		return FALSE;
	}
	function setEndDate($epoch) {
		$epoch = trim($epoch);

		if ( $epoch == '' ){
			$epoch = NULL;
		}

		if 	(	$epoch == NULL
				OR
				$this->Validator->isDate(		'end_date',
												$epoch,
												TTi18n::gettext('Incorrect end date'))
			) {

			$this->data['end_date'] = $epoch;

			return TRUE;
		}

		return FALSE;
	}

	//Check if this date is within the effective date range
	function isActiveDate( $epoch ) {
		$epoch = TTDate::getBeginDayEpoch( $epoch );

		if ( $this->getStartDate() == '' AND $this->getEndDate() == '' ) {
			return TRUE;
		}

		if ( $epoch >= (int)$this->getStartDate()
				AND ( $epoch <= (int)$this->getEndDate() OR $this->getEndDate() == '' ) ) {
			Debug::text('Within Start/End Date.', __FILE__, __LINE__, __METHOD__, 10);

			return TRUE;
		}

		Debug::text('Outside Start/End Date.', __FILE__, __LINE__, __METHOD__, 10);

		return FALSE;
	}

	function getMinimumLengthOfServiceDays() {
		if ( isset($this->data['minimum_length_of_service_days']) ) {
			return (float)$this->data['minimum_length_of_service_days'];
		}

		return FALSE;
	}
	function setMinimumLengthOfServiceDays($int) {
		$int = (float)trim($int);

		Debug::text('aLength of Service Days: '. $int, __FILE__, __LINE__, __METHOD__, 10);

		if 	(	$int >= 0
				AND
				$this->Validator->isFloat(			'minimum_length_of_service',
													$int,
													TTi18n::gettext('Minimum length of service is invalid')) ) {

			$this->data['minimum_length_of_service_days'] = bcmul( $int, $this->length_of_service_multiplier[(int)$this->getMinimumLengthOfServiceUnit()], 4);

			return TRUE;
		}

		return FALSE;
	}

	function getMinimumLengthOfService() {
		if ( isset($this->data['minimum_length_of_service']) ) {
			return (float)$this->data['minimum_length_of_service'];
		}

		return FALSE;
	}
	function setMinimumLengthOfService($int) {
		$int = (float)trim($int);

		Debug::text('bLength of Service: '. $int, __FILE__, __LINE__, __METHOD__, 10);

		if 	(	$int >= 0
				AND
				$this->Validator->isFloat(			'minimum_length_of_service',
													$int,
													TTi18n::gettext('Minimum length of service is invalid')) ) {

			$this->data['minimum_length_of_service'] = $int;

			return TRUE;
		}

		return FALSE;
	}

	function getMinimumLengthOfServiceUnit() {
		if ( isset($this->data['minimum_length_of_service_unit_id']) ) {
			return $this->data['minimum_length_of_service_unit_id'];
		}

		return FALSE;
	}
	function setMinimumLengthOfServiceUnit($value) {
		$value = trim($value);

		if ( $value == ''
				OR $this->Validator->inArrayKey(	'minimum_length_of_service_unit_id',
											$value,
											TTi18n::gettext('Incorrect minimum length of service unit'),
											$this->getOptions('length_of_service_unit')) ) {

			$this->data['minimum_length_of_service_unit_id'] = $value;

			return TRUE;
		}

		return FALSE;
	}

	function getMaximumLengthOfServiceDays() {
		if ( isset($this->data['maximum_length_of_service_days']) ) {
			return (float)$this->data['maximum_length_of_service_days'];
		}

		return FALSE;
	}
	function setMaximumLengthOfServiceDays($int) {
		$int = (float)trim($int);

		Debug::text('aLength of Service Days: '. $int, __FILE__, __LINE__, __METHOD__, 10);

		if 	(	$int >= 0
				AND
				$this->Validator->isFloat(			'maximum_length_of_service',
													$int,
													TTi18n::gettext('Maximum length of service is invalid')) ) {

			$this->data['maximum_length_of_service_days'] = bcmul( $int, $this->length_of_service_multiplier[(int)$this->getMaximumLengthOfServiceUnit()], 4);

			return TRUE;
		}

		return FALSE;
	}

	function getMaximumLengthOfService() {
		if ( isset($this->data['maximum_length_of_service']) ) {
			return (float)$this->data['maximum_length_of_service'];
		}

		return FALSE;
	}
	function setMaximumLengthOfService($int) {
		$int = (float)trim($int);

		Debug::text('bLength of Service: '. $int, __FILE__, __LINE__, __METHOD__, 10);

		if 	(	$int >= 0
				AND
				$this->Validator->isFloat(			'maximum_length_of_service',
													$int,
													TTi18n::gettext('Maximum length of service is invalid')) ) {

			$this->data['maximum_length_of_service'] = $int;

			return TRUE;
		}

		return FALSE;
	}

	function getMaximumLengthOfServiceUnit() {
		if ( isset($this->data['maximum_length_of_service_unit_id']) ) {
			return $this->data['maximum_length_of_service_unit_id'];
		}

		return FALSE;
	}
	function setMaximumLengthOfServiceUnit($value) {
		$value = trim($value);

		if ( $value == ''
				OR $this->Validator->inArrayKey(	'maximum_length_of_service_unit_id',
											$value,
											TTi18n::gettext('Incorrect maximum length of service unit'),
											$this->getOptions('length_of_service_unit')) ) {

			$this->data['maximum_length_of_service_unit_id'] = $value;

			return TRUE;
		}

		return FALSE;
	}

	function getMinimumUserAge() {
		if ( isset($this->data['minimum_user_age']) ) {
			return (float)$this->data['minimum_user_age'];
		}

		return FALSE;
	}
	function setMinimumUserAge($int) {
		$int = (float)trim($int);

		Debug::text('Minimum User Age: '. $int, __FILE__, __LINE__, __METHOD__, 10);

		if 	(	$int >= 0
				AND
				$this->Validator->isFloat(			'minimum_user_age',
													$int,
													TTi18n::gettext('Minimum employee age is invalid')) ) {

			$this->data['minimum_user_age'] = $int;

			return TRUE;
		}

		return FALSE;
	}

	function getMaximumUserAge() {
		if ( isset($this->data['maximum_user_age']) ) {
			return (float)$this->data['maximum_user_age'];
		}

		return FALSE;
	}
	function setMaximumUserAge($int) {
		$int = (float)trim($int);

		Debug::text('Maximum User Age: '. $int, __FILE__, __LINE__, __METHOD__, 10);

		if 	(	$int >= 0
				AND
				$this->Validator->isFloat(			'maximum_user_age',
													$int,
													TTi18n::gettext('Maximum employee age is invalid')) ) {

			$this->data['maximum_user_age'] = $int;

			return TRUE;
		}

		return FALSE;
	}

	function getWorkedTimeByUserIdAndEndDate( $user_id, $start_date = NULL, $end_date = NULL ) {
		if ( $user_id == '' ) {
			return FALSE;
		}

		if ( $start_date == '' ) {
			$start_date = 1; //Default to beginning of time if hire date is not specified.
		}

		if ( $end_date == '' ) {
			return FALSE;
		}

		$udtlf = TTnew( 'UserDateTotalListFactory' );
		$retval = $udtlf->getWorkedTimeSumByUserIDAndStartDateAndEndDate( $user_id, $start_date, $end_date );

		Debug::Text('Worked Seconds: '. (int)$retval .' Before: '. TTDate::getDate('DATE+TIME', $end_date), __FILE__, __LINE__, __METHOD__,10);

		return $retval;
	}

	function isActiveLengthOfService( $u_obj, $epoch ) {
		$worked_time = 0;
		if ( ( $this->getMinimumLengthOfServiceUnit() == 50 AND $this->getMinimumLengthOfService() > 0 )
				OR ( $this->getMaximumLengthOfServiceUnit() == 50 AND $this->getMaximumLengthOfService() > 0 ) ) {
			//Hour based length of service, get users hours up until this period.
			$worked_time = TTDate::getHours( $this->getWorkedTimeByUserIdAndEndDate( $u_obj->getId(), $u_obj->getHireDate(), $epoch ) );
			Debug::Text('&nbsp;&nbsp;Worked Time: '. $worked_time .'hrs', __FILE__, __LINE__, __METHOD__,10);
		}

		$employed_days = TTDate::getDays( ($epoch-$u_obj->getHireDate()) );
		Debug::Text('&nbsp;&nbsp;Employed Days: '. $employed_days, __FILE__, __LINE__, __METHOD__,10);

		$minimum_length_of_service_result = FALSE;
		$maximum_length_of_service_result = FALSE;
		//Check minimum length of service
		if ( $this->getMinimumLengthOfService() == 0
				OR ( $this->getMinimumLengthOfServiceUnit() == 50 AND $worked_time >= $this->getMinimumLengthOfService() )
				OR ( $this->getMinimumLengthOfServiceUnit() != 50 AND $employed_days >= $this->getMinimumLengthOfServiceDays() ) ) {
			$minimum_length_of_service_result = TRUE;
		}

		//Check maximum length of service.
		if ( $this->getMaximumLengthOfService() == 0
				OR ( $this->getMaximumLengthOfServiceUnit() == 50 AND $worked_time <= $this->getMaximumLengthOfService() )
				OR ( $this->getMaximumLengthOfServiceUnit() != 50 AND $employed_days <= $this->getMaximumLengthOfServiceDays() ) ) {
			$maximum_length_of_service_result = TRUE;
		}

		Debug::Text('&nbsp;&nbsp; Min Result: : '. (int)$minimum_length_of_service_result .' Max Result: '. (int)$maximum_length_of_service_result, __FILE__, __LINE__, __METHOD__,10);

		if ( $minimum_length_of_service_result == TRUE AND $maximum_length_of_service_result == TRUE ) {
			return TRUE;
		}

		return FALSE;
	}

	function isActiveUserAge( $u_obj, $epoch ) {
		$user_age = TTDate::getYearDifference( $u_obj->getBirthDate(), $epoch );
		Debug::Text('User Age: '. $user_age .' Min: '. $this->getMinimumUserAge() .' Max: '. $this->getMaximumUserAge(), __FILE__, __LINE__, __METHOD__,10);

		if ( ( $this->getMinimumUserAge() == 0 OR $user_age >= $this->getMinimumUserAge() ) AND ( $this->getMaximumUserAge() == 0 OR $user_age <= $this->getMaximumUserAge() ) ) {
			return TRUE;
		}

		return FALSE;
	}

	function isCountryCalculationID( $calculation_id ) {
		if ( in_array($calculation_id, $this->country_calculation_ids ) ) {
			return TRUE;
		}

		return FALSE;
	}
	function isProvinceCalculationID( $calculation_id ) {
		if ( in_array($calculation_id, $this->province_calculation_ids ) ) {
			return TRUE;
		}

		return FALSE;
	}
	function isDistrictCalculationID( $calculation_id ) {
		if ( in_array($calculation_id, $this->district_calculation_ids ) ) {
			return TRUE;
		}

		return FALSE;
	}


	function getCombinedCalculationID( $calculation_id = NULL, $country = NULL, $province = NULL ) {
		if ( $calculation_id == '' ) {
			$calculation_id = $this->getCalculation();
		}

		if ( $country == '' ) {
			$country = $this->getCountry();
		}

		if ( $province == '' ) {
			$province = $this->getProvince();
		}

		Debug::Text('Calculation ID: '. $calculation_id .' Country: '. $country .' Province: '. $province, __FILE__, __LINE__, __METHOD__,10);

		if ( in_array($calculation_id , $this->country_calculation_ids )
				AND in_array($calculation_id, $this->province_calculation_ids ) ) {
			$id = $calculation_id.'-'.$country.'-'.$province;
		} elseif ( in_array($calculation_id, $this->country_calculation_ids ) ) {
			$id = $calculation_id.'-'.$country;
		} else {
			$id = $calculation_id;
		}

		if ( isset($this->calculation_id_fields[$id]) ) {
			$retval = $this->calculation_id_fields[$id];
		} else {
			$retval = FALSE;
		}

		Debug::Text('Retval: '. $retval, __FILE__, __LINE__, __METHOD__,10);

		return $retval;
	}
	function getCalculation() {
		if ( isset($this->data['calculation_id']) ) {
			return $this->data['calculation_id'];
		}

		return FALSE;
	}
	function setCalculation($value) {
		$value = trim($value);

		$key = Option::getByValue($value, $this->getOptions('calculation') );
		if ($key !== FALSE) {
			$type = $key;
		}

		if ( $this->Validator->inArrayKey(	'calculation',
											$value,
											TTi18n::gettext('Incorrect Calculation'),
											$this->getOptions('calculation')) ) {

			$this->data['calculation_id'] = $value;

			return FALSE;
		}

		return FALSE;
	}

	function getCalculationOrder() {
		if ( isset($this->data['calculation_order']) ) {
			return $this->data['calculation_order'];
		}

		return FALSE;
	}
	function setCalculationOrder($value) {
		$value = trim($value);

		if ( $this->Validator->isNumeric(		'calculation_order',
												$value,
												TTi18n::gettext('Invalid Calculation Order')
										) ) {


			$this->data['calculation_order'] = $value;

			return TRUE;
		}

		return FALSE;
	}

	function getCountry() {
		if ( isset($this->data['country']) ) {
			return $this->data['country'];
		}

		return FALSE;
	}
	function setCountry($country) {
		$country = trim($country);

		$cf = TTnew( 'CompanyFactory' );

		if (	$country == ''
				OR
				$this->Validator->inArrayKey(	'country',
												$country,
												TTi18n::gettext('Invalid Country'),
												$cf->getOptions('country') ) ) {

			$this->data['country'] = $country;

			return TRUE;
		}

		return FALSE;
	}

	function getProvince() {
		if ( isset($this->data['province']) ) {
			return $this->data['province'];
		}

		return FALSE;
	}
	function setProvince($province) {
		$province = trim($province);

		Debug::Text('Country: '. $this->getCountry() .' Province: '. $province, __FILE__, __LINE__, __METHOD__,10);

		$cf = TTnew( 'CompanyFactory' );
		$options_arr = $cf->getOptions('province');
		if ( isset($options_arr[$this->getCountry()]) ) {
			$options = $options_arr[$this->getCountry()];
		} else {
			$options = array();
		}

		if (	$province == ''
				OR
				$this->Validator->inArrayKey(	'province',
												$province,
												TTi18n::gettext('Invalid Province/State'),
												$options ) ) {

			$this->data['province'] = $province;

			return TRUE;
		}

		return FALSE;
	}

	//Used for getting district name on W2's
	function getDistrictName() {
		$retval = NULL;

		if ( strtolower($this->getDistrict()) == 'all'
				OR strtolower($this->getDistrict()) == '00' ) {
			if ( $this->getUserValue5() != '' ) {
				$retval = $this->getUserValue5();
			}
		} else {
			$retval = $this->getDistrict();
		}

		return $retval;
	}
	function getDistrict() {
		if ( isset($this->data['district']) ) {
			return $this->data['district'];
		}

		return FALSE;
	}
	function setDistrict($district) {
		$district = trim($district);

		Debug::Text('Country: '. $this->getCountry() .' District: '. $district, __FILE__, __LINE__, __METHOD__,10);

		$cf = TTnew( 'CompanyFactory' );
		$options_arr = $cf->getOptions('district');
		if ( isset($options_arr[$this->getCountry()][$this->getProvince()]) ) {
			$options = $options_arr[$this->getCountry()][$this->getProvince()];
		} else {
			$options = array();
		}

		if (	( $district == '' OR $district == '00' )
				OR
				$this->Validator->inArrayKey(	'district',
												$district,
												TTi18n::gettext('Invalid District'),
												$options ) ) {

			$this->data['district'] = $district;

			return TRUE;
		}

		return FALSE;
	}

	function getCompanyValue1() {
		if ( isset($this->data['company_value1']) ) {
			return $this->data['company_value1'];
		}

		return FALSE;
	}
	function setCompanyValue1($value) {
	    
		$value = trim($value);

		if 	(	$value == ''
				OR
				$this->Validator->isLength(		'company_value1',
												$value,
												TTi18n::gettext('Company Value 1 is too short or too long'),
												1,
												1000) ) {

			$this->data['company_value1'] = $value;

			return TRUE;
		}

		return FALSE;
	}

	function getCompanyValue2() {
		if ( isset($this->data['company_value2']) ) {
			return $this->data['company_value2'];
		}

		return FALSE;
	}
	function setCompanyValue2($value) {
		$value = trim($value);

		if 	(	$value == ''
				OR
				$this->Validator->isLength(		'company_value2',
												$value,
												TTi18n::gettext('Company Value 2 is too short or too long'),
												1,
												20) ) {

			$this->data['company_value2'] = $value;

			return TRUE;
		}

		return FALSE;
	}

	function getUserValue1() {
		if ( isset($this->data['user_value1']) ) {
			return $this->data['user_value1'];
		}

		return FALSE;
	}
	function setUserValue1($value) {
		$value = trim($value);

		if 	(	$value == ''
				OR
				$this->Validator->isLength(		'user_value1',
												$value,
												TTi18n::gettext('User Value 1 is too short or too long'),
												1,
												20) ) {

			$this->data['user_value1'] = $value;

			return TRUE;
		}

		return FALSE;
	}

	function getUserValue2() {
		if ( isset($this->data['user_value2']) ) {
			return $this->data['user_value2'];
		}

		return FALSE;
	}
	function setUserValue2($value) {
		$value = trim($value);

		if 	(	$value == ''
				OR
				$this->Validator->isLength(		'user_value2',
												$value,
												TTi18n::gettext('User Value 2 is too short or too long'),
												1,
												20) ) {

			$this->data['user_value2'] = $value;

			return TRUE;
		}

		return FALSE;
	}

	function getUserValue3() {
		if ( isset($this->data['user_value3']) ) {
			return $this->data['user_value3'];
		}

		return FALSE;
	}
	function setUserValue3($value) {
		$value = trim($value);

		if 	(	$value == ''
				OR
				$this->Validator->isLength(		'user_value3',
												$value,
												TTi18n::gettext('User Value 3 is too short or too long'),
												1,
												20) ) {

			$this->data['user_value3'] = $value;

			return TRUE;
		}

		return FALSE;
	}

	function getUserValue4() {
		if ( isset($this->data['user_value4']) ) {
			return $this->data['user_value4'];
		}

		return FALSE;
	}
	function setUserValue4($value) {
		$value = trim($value);

		if 	(	$value == ''
				OR
				$this->Validator->isLength(		'user_value4',
												$value,
												TTi18n::gettext('User Value 4 is too short or too long'),
												1,
												20) ) {

			$this->data['user_value4'] = $value;

			return TRUE;
		}

		return FALSE;
	}

	function getUserValue5() {
		if ( isset($this->data['user_value5']) ) {
			return $this->data['user_value5'];
		}

		return FALSE;
	}
	function setUserValue5($value) {
		$value = trim($value);

		if 	(	$value == ''
				OR
				$this->Validator->isLength(		'user_value5',
												$value,
												TTi18n::gettext('User Value 5 is too short or too long'),
												1,
												20) ) {

			$this->data['user_value5'] = $value;

			return TRUE;
		}

		return FALSE;
	}

	function getUserValue6() {
		if ( isset($this->data['user_value6']) ) {
			return $this->data['user_value6'];
		}

		return FALSE;
	}
	function setUserValue6($value) {
		$value = trim($value);

		if 	(	$value == ''
				OR
				$this->Validator->isLength(		'user_value6',
												$value,
												TTi18n::gettext('User Value 6 is too short or too long'),
												1,
												20) ) {

			$this->data['user_value6'] = $value;

			return TRUE;
		}

		return FALSE;
	}

	function getUserValue7() {
		if ( isset($this->data['user_value7']) ) {
			return $this->data['user_value7'];
		}

		return FALSE;
	}
	function setUserValue7($value) {
		$value = trim($value);

		if 	(	$value == ''
				OR
				$this->Validator->isLength(		'user_value7',
												$value,
												TTi18n::gettext('User Value 7 is too short or too long'),
												1,
												20) ) {

			$this->data['user_value7'] = $value;

			return TRUE;
		}

		return FALSE;
	}

	function getUserValue8() {
		if ( isset($this->data['user_value8']) ) {
			return $this->data['user_value8'];
		}

		return FALSE;
	}
	function setUserValue8($value) {
		$value = trim($value);

		if 	(	$value == ''
				OR
				$this->Validator->isLength(		'user_value8',
												$value,
												TTi18n::gettext('User Value 8 is too short or too long'),
												1,
												20) ) {

			$this->data['user_value8'] = $value;

			return TRUE;
		}

		return FALSE;
	}

	function getUserValue9() {
		if ( isset($this->data['user_value9']) ) {
			return $this->data['user_value9'];
		}

		return FALSE;
	}
	function setUserValue9($value) {
		$value = trim($value);

		if 	(	$value == ''
				OR
				$this->Validator->isLength(		'user_value9',
												$value,
												TTi18n::gettext('User Value 9 is too short or too long'),
												1,
												20) ) {

			$this->data['user_value9'] = $value;

			return TRUE;
		}

		return FALSE;
	}

	function getUserValue10() {
		if ( isset($this->data['user_value10']) ) {
			return $this->data['user_value10'];
		}

		return FALSE;
	}
	function setUserValue10($value) {
		$value = trim($value);

		if 	(	$value == ''
				OR
				$this->Validator->isLength(		'user_value10',
												$value,
												TTi18n::gettext('User Value 10 is too short or too long'),
												1,
												20) ) {

			$this->data['user_value10'] = $value;

			return TRUE;
		}

		return FALSE;
	}

	function getUserValue1Options() {
		//Debug::Text('Calculation: '. $this->getCalculation(), __FILE__, __LINE__, __METHOD__,10);
		switch ( $this->getCalculation() ) {
			case 100:
				//Debug::Text('Country: '. $this->getCountry(), __FILE__, __LINE__, __METHOD__,10);
				if ( $this->getCountry() == 'CA' ) {
				} elseif ( $this->getCountry() == 'US' ) {
					//$options = $this->federal_filing_status_options;
					$options = $this->getOptions('federal_filing_status');
				}

				break;
			case 200:
				//Debug::Text('Country: '. $this->getCountry(), __FILE__, __LINE__, __METHOD__,10);
				//Debug::Text('Province: '. $this->getProvince(), __FILE__, __LINE__, __METHOD__,10);
				if ( $this->getCountry() == 'CA' ) {
				} elseif ( $this->getCountry() == 'US' ) {
					$state_options_var = strtolower('state_'. $this->getProvince() .'_filing_status_options');
					//Debug::Text('Specific State Variable Name: '. $state_options_var, __FILE__, __LINE__, __METHOD__,10);
					if ( isset( $this->$state_options_var ) ) {
						//Debug::Text('Specific State Options: ', __FILE__, __LINE__, __METHOD__,10);
						//$options = $this->$state_options_var;
						$options = $this->getOptions($state_options_var);
					} elseif ( $this->getProvince() == 'IL' ) {
						$options = FALSE;
					} else {
						//Debug::Text('Default State Options: ', __FILE__, __LINE__, __METHOD__,10);
						//$options = $this->state_filing_status_options;
						$options = $this->getOptions('state_filing_status');
					}
				}

				break;
		}

		if ( isset($options) ) {
			return $options;
		}

		return FALSE;
	}

	function getPayStubEntryAccount() {
		if ( isset($this->data['pay_stub_entry_account_id']) ) {
			return $this->data['pay_stub_entry_account_id'];
		}

		return FALSE;
	}
	function setPayStubEntryAccount($id) {
		$id = trim($id);

		Debug::Text('ID: '. $id, __FILE__, __LINE__, __METHOD__,10);
		$psealf = TTnew( 'PayStubEntryAccountListFactory' );

		if (
				( $id == '' OR $id == 0 )
				OR
				$this->Validator->isResultSetWithRows(	'pay_stub_entry_account',
														$psealf->getByID($id),
														TTi18n::gettext('Pay Stub Account is invalid')
													) ) {

			$this->data['pay_stub_entry_account_id'] = $id;

			return TRUE;
		}

		return FALSE;
	}

	function getLockUserValue1() {
		return $this->fromBool( $this->data['lock_user_value1'] );
	}
	function setLockUserValue1($bool) {
		$this->data['lock_user_value1'] = $this->toBool($bool);

		return true;
	}

	function getLockUserValue2() {
		return $this->fromBool( $this->data['lock_user_value2'] );
	}
	function setLockUserValue2($bool) {
		$this->data['lock_user_value2'] = $this->toBool($bool);

		return true;
	}

	function getLockUserValue3() {
		return $this->fromBool( $this->data['lock_user_value3'] );
	}
	function setLockUserValue3($bool) {
		$this->data['lock_user_value3'] = $this->toBool($bool);

		return true;
	}

	function getLockUserValue4() {
		return $this->fromBool( $this->data['lock_user_value4'] );
	}
	function setLockUserValue4($bool) {
		$this->data['lock_user_value4'] = $this->toBool($bool);

		return true;
	}

	function getLockUserValue5() {
		return $this->fromBool( $this->data['lock_user_value5'] );
	}
	function setLockUserValue5($bool) {
		$this->data['lock_user_value5'] = $this->toBool($bool);

		return true;
	}

	function getLockUserValue6() {
		return $this->fromBool( $this->data['lock_user_value6'] );
	}
	function setLockUserValue6($bool) {
		$this->data['lock_user_value6'] = $this->toBool($bool);

		return true;
	}

	function getLockUserValue7() {
		return $this->fromBool( $this->data['lock_user_value7'] );
	}
	function setLockUserValue7($bool) {
		$this->data['lock_user_value7'] = $this->toBool($bool);

		return true;
	}

	function getLockUserValue8() {
		return $this->fromBool( $this->data['lock_user_value8'] );
	}
	function setLockUserValue8($bool) {
		$this->data['lock_user_value8'] = $this->toBool($bool);

		return true;
	}

	function getLockUserValue9() {
		return $this->fromBool( $this->data['lock_user_value9'] );
	}
	function setLockUserValue9($bool) {
		$this->data['lock_user_value9'] = $this->toBool($bool);

		return true;
	}

	function getLockUserValue10() {
		return $this->fromBool( $this->data['lock_user_value10'] );
	}
	function setLockUserValue10($bool) {
		$this->data['lock_user_value10'] = $this->toBool($bool);

		return true;
	}

	function getAccountAmountTypeMap( $id ) {
		if ( isset( $this->account_amount_type_map[$id]) ) {
			return $this->account_amount_type_map[$id];
		}

		Debug::text('Unable to find Account Amount mapping... ID: '. $id, __FILE__, __LINE__, __METHOD__, 10);

		return 'amount'; //Default to amount.
	}

	function getAccountAmountTypePSEntriesMap( $id ) {
		if ( isset( $this->account_amount_type_ps_entries_map [$id]) ) {
			return $this->account_amount_type_ps_entries_map[$id];
		}

		Debug::text('Unable to find Account Amount PS Entries mapping... ID: '. $id, __FILE__, __LINE__, __METHOD__, 10);

		return 'current'; //Default to current entries.
	}


	function getIncludeAccountAmountType() {
		if ( isset($this->data['include_account_amount_type_id']) ) {
			return $this->data['include_account_amount_type_id'];
		}

		return FALSE;
	}
	function setIncludeAccountAmountType($value) {
		$value = trim($value);

		if ( $this->Validator->inArrayKey(	'include_account_amount_type_id',
											$value,
											TTi18n::gettext('Incorrect include account amount type'),
											$this->getOptions('account_amount_type')) ) {

			$this->data['include_account_amount_type_id'] = $value;

			return TRUE;
		}

		return FALSE;
	}

	function getIncludePayStubEntryAccount() {
		$cache_id = 'include_pay_stub_entry-'. $this->getId();
		$list = $this->getCache( $cache_id );
		if ( $list === FALSE ) {
			//Debug::text('Caching Include IDs: '. $this->getId(), __FILE__, __LINE__, __METHOD__, 10);
			$cdpsealf = TTnew( 'CompanyDeductionPayStubEntryAccountListFactory' );
			$cdpsealf->getByCompanyDeductionIdAndTypeId( $this->getId(), 10 );

			$list = NULL;
			foreach ($cdpsealf as $obj) {
				$list[] = $obj->getPayStubEntryAccount();
			}
			$this->saveCache( $list, $cache_id);
		} else {
			//Debug::text('Reading Cached Include IDs: '. $this->getId(), __FILE__, __LINE__, __METHOD__, 10);
		}
		//Debug::Arr($list, 'Include IDs: ', __FILE__, __LINE__, __METHOD__, 10);

		if ( isset($list) AND is_array($list) ) {
			return $list;
		}

		return FALSE;
	}
	function setIncludePayStubEntryAccount($ids) {
		Debug::text('Setting Include IDs : ', __FILE__, __LINE__, __METHOD__, 10);
		if ( !is_array($ids) ) {
			$ids = array($ids);
		}

		if ( is_array($ids) ) {
			$tmp_ids = array();
			if ( !$this->isNew() ) {
				//If needed, delete mappings first.
				$cdpsealf = TTnew( 'CompanyDeductionPayStubEntryAccountListFactory' );
				$cdpsealf->getByCompanyDeductionIdAndTypeId( $this->getId(), 10 );

				foreach ($cdpsealf as $obj) {
					$id = $obj->getPayStubEntryAccount();
					Debug::text('ID: '. $id, __FILE__, __LINE__, __METHOD__, 10);

					//Delete users that are not selected.
					if ( !in_array($id, $ids) ) {
						Debug::text('Deleting: '. $id, __FILE__, __LINE__, __METHOD__, 10);
						$obj->Delete();
					} else {
						//Save ID's that need to be updated.
						Debug::text('NOT Deleting : '. $id, __FILE__, __LINE__, __METHOD__, 10);
						$tmp_ids[] = $id;
					}
				}
				unset($id, $obj);
			}

			//Insert new mappings.
			$psealf = TTnew( 'PayStubEntryAccountListFactory' );

			foreach ($ids as $id) {
				if ( $id != FALSE AND isset($ids) AND !in_array($id, $tmp_ids) ) {
					$cdpseaf = TTnew( 'CompanyDeductionPayStubEntryAccountFactory' );
					$cdpseaf->setCompanyDeduction( $this->getId() );
					$cdpseaf->setType(10); //Include
					$cdpseaf->setPayStubEntryAccount( $id );

					$obj = $psealf->getById( $id )->getCurrent();

					if ($this->Validator->isTrue(		'include_pay_stub_entry_account',
														$cdpseaf->Validator->isValid(),
														TTi18n::gettext('Include Pay Stub Account is invalid').' ('. $obj->getName() .')' )) {
						$cdpseaf->save();
					}
				}
			}

			return TRUE;
		}

		Debug::text('No IDs to set.', __FILE__, __LINE__, __METHOD__, 10);
		return FALSE;
	}

	function getExcludeAccountAmountType() {
		if ( isset($this->data['exclude_account_amount_type_id']) ) {
			return $this->data['exclude_account_amount_type_id'];
		}

		return FALSE;
	}
	function setExcludeAccountAmountType($value) {
		$value = trim($value);

		if ( $this->Validator->inArrayKey(	'exclude_account_amount_type_id',
											$value,
											TTi18n::gettext('Incorrect exclude account amount type'),
											$this->getOptions('account_amount_type')) ) {

			$this->data['exclude_account_amount_type_id'] = $value;

			return TRUE;
		}

		return FALSE;
	}

	function getExcludePayStubEntryAccount() {
		$cache_id = 'exclude_pay_stub_entry-'. $this->getId();
		$list = $this->getCache( $cache_id );
		if ( $list === FALSE ) {
			//Debug::text('Caching Exclude IDs: '. $this->getId(), __FILE__, __LINE__, __METHOD__, 10);
			$cdpsealf = TTnew( 'CompanyDeductionPayStubEntryAccountListFactory' );
			$cdpsealf->getByCompanyDeductionIdAndTypeId( $this->getId(), 20 );

			$list = NULL;
			foreach ($cdpsealf as $obj) {
				$list[] = $obj->getPayStubEntryAccount();
			}

			$this->saveCache( $list, $cache_id);
		} else {
			//Debug::text('Reading Cached Exclude IDs: '. $this->getId(), __FILE__, __LINE__, __METHOD__, 10);
		}

		if ( isset($list) AND is_array($list) ) {
			return $list;
		}

		return FALSE;
	}
	function setExcludePayStubEntryAccount($ids) {
		Debug::text('Setting Exclude IDs : ', __FILE__, __LINE__, __METHOD__, 10);
		if ( !is_array($ids) ) {
			$ids = array($ids);
		}

		if ( is_array($ids) ) {
			if ( !$this->isNew() ) {
				//If needed, delete mappings first.
				$cdpsealf = TTnew( 'CompanyDeductionPayStubEntryAccountListFactory' );
				$cdpsealf->getByCompanyDeductionIdAndTypeId( $this->getId(), 20 );

				$tmp_ids = array();
				foreach ($cdpsealf as $obj) {
					$id = $obj->getPayStubEntryAccount();
					Debug::text('ID: '. $id, __FILE__, __LINE__, __METHOD__, 10);

					//Delete users that are not selected.
					if ( !in_array($id, $ids) ) {
						Debug::text('Deleting: '. $id, __FILE__, __LINE__, __METHOD__, 10);
						$obj->Delete();
					} else {
						//Save ID's that need to be updated.
						Debug::text('NOT Deleting : '. $id, __FILE__, __LINE__, __METHOD__, 10);
						$tmp_ids[] = $id;
					}
				}
				unset($id, $obj);
			}

			//Insert new mappings.
			//$lf = TTnew( 'UserListFactory' );
			$psealf = TTnew( 'PayStubEntryAccountListFactory' );

			foreach ($ids as $id) {
				if ( $id != FALSE AND isset($ids) AND !in_array($id, $tmp_ids) ) {
					$cdpseaf = TTnew( 'CompanyDeductionPayStubEntryAccountFactory' );
					$cdpseaf->setCompanyDeduction( $this->getId() );
					$cdpseaf->setType(20); //Include
					$cdpseaf->setPayStubEntryAccount( $id );

					$obj = $psealf->getById( $id )->getCurrent();

					if ($this->Validator->isTrue(		'exclude_pay_stub_entry_account',
														$cdpseaf->Validator->isValid(),
														TTi18n::gettext('Exclude Pay Stub Account is invalid').' ('. $obj->getName() .')' )) {
						$cdpseaf->save();
					}
				}
			}

			return TRUE;
		}

		Debug::text('No IDs to set.', __FILE__, __LINE__, __METHOD__, 10);
		return FALSE;
	}

	function getUser() {
		$udlf = TTnew( 'UserDeductionListFactory' );
		$udlf->getByCompanyIdAndCompanyDeductionId( $this->getCompany(), $this->getId() );
		foreach ($udlf as $obj) {
			$list[] = $obj->getUser();
		}

		if ( isset($list) ) {
			return $list;
		}

		return FALSE;
	}
	function setUser($ids) {
		if ( !is_array($ids) ) {
			$ids = array($ids);
		}

		if ( is_array($ids) ) {
			if ( !$this->isNew() ) {
				//If needed, delete mappings first.
				$udlf = TTnew( 'UserDeductionListFactory' );
				$udlf->getByCompanyIdAndCompanyDeductionId( $this->getCompany(), $this->getId() );

				$tmp_ids = array();
				foreach ($udlf as $obj) {
					$id = $obj->getUser();
					Debug::text('ID: '. $id, __FILE__, __LINE__, __METHOD__, 10);

					//Delete users that are not selected.
					if ( !in_array($id, $ids) ) {
						Debug::text('Deleting: '. $id, __FILE__, __LINE__, __METHOD__, 10);
						$obj->Delete();
					} else {
						//Save ID's that need to be updated.
						Debug::text('NOT Deleting : '. $id, __FILE__, __LINE__, __METHOD__, 10);
						$tmp_ids[] = $id;
					}
				}
				unset($id, $obj);
			}

			//Insert new mappings.
			$ulf = TTnew( 'UserListFactory' );
			foreach ($ids as $id) {
				if ( $id != FALSE AND isset($ids) AND !in_array($id, $tmp_ids) ) {
					$udf = TTnew( 'UserDeductionFactory' );
					$udf->setUser( $id );
					$udf->setCompanyDeduction( $this->getId() );

					$ulf->getById( $id );
					if ( $ulf->getRecordCount() > 0 ) {
						$obj = $ulf->getCurrent();

						if ($this->Validator->isTrue(		'user',
															$udf->Validator->isValid(),
															TTi18n::gettext('Selected employee is invalid').' ('. $obj->getFullName() .')' )) {
							$udf->save();
						}
					}
				}
			}

			return TRUE;
		}

		Debug::text('No IDs to set.', __FILE__, __LINE__, __METHOD__, 10);
		return FALSE;
	}

	function getExpandedPayStubEntryAccountIDs( $ids ) {
		//Debug::Arr($ids, 'Total Gross ID: '. $this->getPayStubEntryAccountLinkObject()->getTotalGross() .' IDs:', __FILE__, __LINE__, __METHOD__,10);
		$ids = (array)$ids;

		$total_gross_key = array_search( $this->getPayStubEntryAccountLinkObject()->getTotalGross(), $ids);
		if ( $total_gross_key !== FALSE ) {
			$type_ids[] = 10;
			$type_ids[] = 60; //Automatically inlcude Advance Earnings here?
			unset($ids[$total_gross_key]);
		}
		unset($total_gross_key);

		$total_employee_deduction_key = array_search( $this->getPayStubEntryAccountLinkObject()->getTotalEmployeeDeduction(), $ids);
		if ( $total_employee_deduction_key !== FALSE ) {
			$type_ids[] = 20;
			unset($ids[$total_employee_deduction_key]);
		}
		unset($total_employee_deduction_key);

		$total_employer_deduction_key = array_search( $this->getPayStubEntryAccountLinkObject()->getTotalEmployerDeduction(), $ids);
		if ( $total_employer_deduction_key !== FALSE ) {
			$type_ids[] = 30;
			unset($ids[$total_employer_deduction_key]);
		}
		unset($total_employer_deduction_key);

		$psea_ids_from_type_ids = array();
		if ( isset($type_ids) ) {
			$psealf = TTnew( 'PayStubEntryAccountListFactory' );
			$psea_ids_from_type_ids = $psealf->getByCompanyIdAndStatusIdAndTypeIdArray( $this->getCompany(), array(10,20), $type_ids, FALSE );
			if ( is_array( $psea_ids_from_type_ids ) ) {
				$psea_ids_from_type_ids = array_keys( $psea_ids_from_type_ids );
			}
		}

		$retval = array_unique( array_merge( $ids, $psea_ids_from_type_ids ) );

		//Debug::Arr($retval, 'Retval: ', __FILE__, __LINE__, __METHOD__,10);
		return $retval;

	}

	//Combines include account IDs/Type IDs and exclude account IDs/Type Ids
	//and outputs just include account ids.
	function getCombinedIncludeExcludePayStubEntryAccount( $include_ids, $exclude_ids ) {
		$ret_include_ids = $this->getExpandedPayStubEntryAccountIDs( $include_ids );
		$ret_exclude_ids = $this->getExpandedPayStubEntryAccountIDs( $exclude_ids );

		$retarr = array_diff( $ret_include_ids, $ret_exclude_ids );

		//Debug::Arr($retarr, 'Retarr: ', __FILE__, __LINE__, __METHOD__,10);
		return $retarr;
	}

	function getPayStubEntryAmountSum( $pay_stub_obj, $ids, $ps_entries = 'current', $return_value = 'amount' ) {
		if ( !is_object($pay_stub_obj) ) {
			return FALSE;
		}

		if ( !is_array($ids) ) {
			return FALSE;
		}

		$pself = TTnew( 'PayStubEntryListFactory' );

		//Get Linked accounts so we know which IDs are totals.
		$total_gross_key = array_search( $this->getPayStubEntryAccountLinkObject()->getTotalGross(), $ids);

		if ( $total_gross_key !== FALSE ) {
			$type_ids[] = 10;
			$type_ids[] = 60; //Automatically inlcude Advance Earnings here?
			unset($ids[$total_gross_key]);
		}
		unset($total_gross_key);

		$total_employee_deduction_key = array_search( $this->getPayStubEntryAccountLinkObject()->getTotalEmployeeDeduction(), $ids);
		if ( $total_employee_deduction_key !== FALSE ) {
			$type_ids[] = 20;
			unset($ids[$total_employee_deduction_key]);
		}
		unset($total_employee_deduction_key);

		$total_employer_deduction_key = array_search( $this->getPayStubEntryAccountLinkObject()->getTotalEmployerDeduction(), $ids);
		if ( $total_employer_deduction_key !== FALSE ) {
			$type_ids[] = 30;
			unset($ids[$total_employer_deduction_key]);
		}
		unset($total_employer_deduction_key);

		$type_amount_arr[$return_value] = 0;

		if ( isset($type_ids) ) {
			$type_amount_arr = $pay_stub_obj->getSumByEntriesArrayAndTypeIDAndPayStubAccountID( $ps_entries, $type_ids );
		}

		$amount_arr[$return_value] = 0;
		if ( count($ids) > 0 ) {
			//Still other IDs left to total.
			$amount_arr = $pay_stub_obj->getSumByEntriesArrayAndTypeIDAndPayStubAccountID( $ps_entries, NULL, $ids );
		}

		$retval = bcadd($type_amount_arr[$return_value], $amount_arr[$return_value] );

		Debug::text('Type Amount: '. $type_amount_arr[$return_value] .' Regular Amount: '. $amount_arr[$return_value] .' Total: '. $retval .' Return Value: '. $return_value .' PS Entries: '. $ps_entries, __FILE__, __LINE__, __METHOD__, 10);

		return $retval;
	}

	function getCalculationPayStubAmount( $pay_stub_obj, $include_account_amount_type_id = NULL, $exclude_account_amount_type_id = NULL ) {
		if ( !is_object($pay_stub_obj) ) {
			return FALSE;
		}

		$include_ids = $this->getIncludePayStubEntryAccount();
		$exclude_ids = $this->getExcludePayStubEntryAccount();

        $is_included = FALSE;
        $is_excluded = FALSE;

		//This totals up the includes, and minuses the excludes.
        if ( isset( $include_account_amount_type_id ) ) {
            $include_account_amount_type = $include_account_amount_type_id;
            $is_included = TRUE;
        } else {
            $include_account_amount_type = $this->getIncludeAccountAmountType();
        }

        if ( isset( $exclude_account_amount_type_id ) ) {
            $exclude_account_amount_type = $exclude_account_amount_type_id;
            $is_excluded = TRUE;
        } else {
            $exclude_account_amount_type = $this->getExcludeAccountAmountType();
        }

		$include = $this->getPayStubEntryAmountSum( $pay_stub_obj, $include_ids, $this->getAccountAmountTypePSEntriesMap( $include_account_amount_type ), $this->getAccountAmountTypeMap( $include_account_amount_type ) );
		$exclude = $this->getPayStubEntryAmountSum( $pay_stub_obj, $exclude_ids, $this->getAccountAmountTypePSEntriesMap( $exclude_account_amount_type ), $this->getAccountAmountTypeMap( $exclude_account_amount_type ) );
		Debug::text('Include Amount: '. $include .' Exclude Amount: '. $exclude, __FILE__, __LINE__, __METHOD__, 10);

		//Allow negative values to be returned, as we need to do calculation on accruals and such that may be negative values.

        if ( $is_included == TRUE AND $is_excluded == TRUE ) {
            $amount = bcsub( $include, $exclude);
        } elseif( $is_included == TRUE ) {
            $amount = $include;
        } elseif( $is_excluded == TRUE ) {
            $amount = $exclude;
        } else {
            $amount = bcsub( $include, $exclude);
        }

		Debug::text('Amount: '. $amount, __FILE__, __LINE__, __METHOD__, 10);

		return $amount;
	}

	function getCalculationYTDAmount( $pay_stub_obj ) {
		if ( !is_object($pay_stub_obj) ) {
			return FALSE;
		}

		//This totals up the includes, and minuses the excludes.
		$include_ids = $this->getIncludePayStubEntryAccount();
		$exclude_ids = $this->getExcludePayStubEntryAccount();

		//Use current YTD amount because if we only include previous pay stub YTD amounts we won't include YTD adjustment PS amendments on the current PS.
		$include = $this->getPayStubEntryAmountSum( $pay_stub_obj, $include_ids, 'previous+ytd_adjustment', 'ytd_amount' );
		$exclude = $this->getPayStubEntryAmountSum( $pay_stub_obj, $exclude_ids, 'previous+ytd_adjustment', 'ytd_amount' );

		$amount = bcsub( $include, $exclude);

		if ( $amount < 0 ) {
			$amount = 0;
		}

		Debug::text('Amount: '. $amount, __FILE__, __LINE__, __METHOD__, 10);

		return $amount;
	}

	function getJavaScriptArrays() {
		$output = 'var fields = '. Misc::getJSArray( $this->calculation_id_fields, 'fields', TRUE );

		$output .= 'var country_calculation_ids = '. Misc::getJSArray( $this->country_calculation_ids );
		$output .= 'var province_calculation_ids = '. Misc::getJSArray( $this->province_calculation_ids );
		$output .= 'var district_calculation_ids = '. Misc::getJSArray( $this->district_calculation_ids );

		return $output;
	}

	static function getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName( $company_id, $type_id, $name ) {
		$psealf = TTnew( 'PayStubEntryAccountListFactory' );
		$psealf->getByCompanyIdAndTypeAndFuzzyName( $company_id, $type_id, $name );
		if ( $psealf->getRecordCount() > 0 ) {
			return $psealf->getCurrent()->getId();
		}

		return FALSE;
	}

	static function addPresets($company_id) {
		if ( $company_id == '' ) {
			Debug::text('Company ID: '. $company_id , __FILE__, __LINE__, __METHOD__, 10);
			return FALSE;
		}

		$clf = TTnew( 'CompanyListFactory' );
		$clf->getById( $company_id );
		if ( $clf->getRecordCount() > 0 ) {
			$company_obj = $clf->getCurrent();
			$country = $company_obj->getCountry();
			$province = $company_obj->getProvince();
		} else {
			Debug::text('bCompany ID: '. $company_id , __FILE__, __LINE__, __METHOD__, 10);
			return FALSE;
		}

		//Get PayStub Link accounts
		$pseallf = TTnew( 'PayStubEntryAccountLinkListFactory' );
		$pseallf->getByCompanyId( $company_id );
		if  ( $pseallf->getRecordCount() > 0 ) {
			$psea_obj = $pseallf->getCurrent();
		} else {
			Debug::text('cCompany ID: '. $company_id , __FILE__, __LINE__, __METHOD__, 10);
			return FALSE;
		}

		require_once( Environment::getBasePath().'/classes/payroll_deduction/PayrollDeduction.class.php');
		$cdf = TTnew( 'CompanyDeductionFactory' );
		$cdf->StartTransaction();

		/*
										10 => 'Percent',
										15 => 'Advanced Percent',
										20 => 'Fixed Amount',

										//Federal
										100 => 'Federal Income Tax Formula',

										//Province/State
										200 => 'Province/State Income Tax Formula',
										210 => 'Province/State UI Formula',
		*/

		Debug::text('Country: '. $country , __FILE__, __LINE__, __METHOD__, 10);
		switch (strtolower($country)) {
			case 'ca':
				$pd_obj = new PayrollDeduction( $country, 'BC' ); //Pick default province for now.
				$pd_obj->setDate( time() );

				$cdf = TTnew( 'CompanyDeductionFactory' );
				$cdf->setCompany( $company_id );
				$cdf->setStatus( 10 ); //Enabled
				$cdf->setType( 10 ); //Tax
				$cdf->setName( 'Federal Income Tax' );
				$cdf->setCalculation( 100 );
				$cdf->setCalculationOrder( 100 );
				$cdf->setCountry( 'CA' );
				$cdf->setPayStubEntryAccount( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName( $company_id, 20, '%Federal Income%') );
				$cdf->setUserValue1( $pd_obj->getBasicFederalClaimCodeAmount() );

				if ( $cdf->isValid() ) {
					$cdf->Save(FALSE);

					$exclude_ids = array(
										self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName($company_id, 20, 'Union'),
										);
					$cdf->setIncludePayStubEntryAccount( array( $psea_obj->getTotalGross() ) );
					//var_dump($exclude_ids);
					$cdf->setExcludePayStubEntryAccount( $exclude_ids );

					unset($exclude_ids);

					if ( $cdf->isValid() ) {
						$cdf->Save();
					}
				}

				$cdf = TTnew( 'CompanyDeductionFactory' );
				$cdf->setCompany( $company_id );
				$cdf->setStatus( 10 ); //Enabled
				$cdf->setType( 10 ); //Tax
				$cdf->setName( 'Additional Income Tax' );
				$cdf->setCalculation( 20 );
				$cdf->setCalculationOrder( 105 );
				$cdf->setPayStubEntryAccount( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName($company_id, 20, '%Additional Income Tax%') );
				$cdf->setUserValue1( 0 );

				if ( $cdf->isValid() ) {
					$cdf->Save(FALSE);

					if ( $cdf->isValid() ) {
						$cdf->Save();
					}
				}

				$cdf = TTnew( 'CompanyDeductionFactory' );
				$cdf->setCompany( $company_id );
				$cdf->setStatus( 10 ); //Enabled
				$cdf->setType( 10 ); //Tax
				$cdf->setName( 'CPP - Employee' );
				$cdf->setCalculation( 90 ); // CPP Formula
				$cdf->setMinimumUserAge( 18 );
				$cdf->setMaximumUserAge( 70 );

				$cdf->setCalculationOrder( 80 );
				$cdf->setPayStubEntryAccount( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName($company_id, 20, 'CPP') );

				if ( $cdf->isValid() ) {
					$cdf->Save(FALSE);

					$cdf->setIncludePayStubEntryAccount( array( $psea_obj->getTotalGross() ));

					if ( $cdf->isValid() ) {
						$cdf->Save();
					}
				}

				$cdf = TTnew( 'CompanyDeductionFactory' );
				$cdf->setCompany( $company_id );
				$cdf->setStatus( 10 ); //Enabled
				$cdf->setType( 10 ); //Tax
				$cdf->setName( 'CPP - Employer' );
				$cdf->setCalculation( 10 );
				$cdf->setCalculationOrder( 85 );
				$cdf->setPayStubEntryAccount( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName($company_id, 30, '%CPP - Employer%') );
				$cdf->setUserValue1( 100 );

				if ( $cdf->isValid() ) {
					$cdf->Save(FALSE);

					$cdf->setIncludePayStubEntryAccount( array( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName($company_id, 20, 'CPP') ) );

					if ( $cdf->isValid() ) {
						$cdf->Save();
					}
				}

				$cdf = TTnew( 'CompanyDeductionFactory' );
				$cdf->setCompany( $company_id );
				$cdf->setStatus( 10 ); //Enabled
				$cdf->setType( 10 ); //Tax
				$cdf->setName( 'EI - Employee' );
				$cdf->setCalculation( 91 ); //EI Formula
				$cdf->setCalculationOrder( 90 );
				$cdf->setPayStubEntryAccount( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName($company_id, 20, 'EI') );

				if ( $cdf->isValid() ) {
					$cdf->Save(FALSE);

					$cdf->setIncludePayStubEntryAccount( array( $psea_obj->getTotalGross() ) );

					if ( $cdf->isValid() ) {
						$cdf->Save();
					}
				}

				$cdf = TTnew( 'CompanyDeductionFactory' );
				$cdf->setCompany( $company_id );
				$cdf->setStatus( 10 ); //Enabled
				$cdf->setType( 10 ); //Tax
				$cdf->setName( 'EI - Employer' );
				$cdf->setCalculation( 10 );
				$cdf->setCalculationOrder( 95 );
				$cdf->setPayStubEntryAccount( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName($company_id, 30, '%EI - Employer%') );
				$cdf->setUserValue1( 140 ); //2006

				if ( $cdf->isValid() ) {
					$cdf->Save(FALSE);

					$cdf->setIncludePayStubEntryAccount( array( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName($company_id, 20, 'EI') ) );

					if ( $cdf->isValid() ) {
						$cdf->Save();
					}
				}

				$cdf = TTnew( 'CompanyDeductionFactory' );
				$cdf->setCompany( $company_id );
				$cdf->setStatus( 10 ); //Enabled
				$cdf->setType( 10 ); //Tax
				$cdf->setName( 'WCB - Employer' );
				$cdf->setCalculation( 15 );
				$cdf->setCalculationOrder( 95 );
				$cdf->setPayStubEntryAccount( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName($company_id, 30, '%WCB%') );
				$cdf->setUserValue1( 0.00 ); //Default
				$cdf->setUserValue2( 0 ); //Annual Wage Base: WCB has this, but can differ between rates/classifications.
				$cdf->setUserValue3( 0 );

				if ( $cdf->isValid() ) {
					$cdf->Save(FALSE);

					$cdf->setIncludePayStubEntryAccount( array( $psea_obj->getTotalGross() ) );

					if ( $cdf->isValid() ) {
						$cdf->Save();
					}
				}

				$cdf = TTnew( 'CompanyDeductionFactory' );
				$cdf->setCompany( $company_id );
				$cdf->setStatus( 10 ); //Enabled
				$cdf->setType( 20 ); //Deduction
				$cdf->setName( 'Vacation Accrual' );
				$cdf->setCalculation( 10 );
				$cdf->setCalculationOrder( 50 );
				$cdf->setPayStubEntryAccount( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName($company_id, 50, 'Vacation Accrual') );
				$cdf->setUserValue1( 4 );

				if ( $cdf->isValid() ) {
					$cdf->Save(FALSE);

					$cdf->setIncludePayStubEntryAccount( array( $psea_obj->getTotalGross() ) );
					$exclude_ids = array(
										self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName($company_id, 10, 'Vacation Accrual Release'),
										self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName($company_id, 10, 'Vacation Time'),
										);
					$cdf->setExcludePayStubEntryAccount( $exclude_ids );

					if ( $cdf->isValid() ) {
						$cdf->Save();
					}
				}

				$cdf = TTnew( 'CompanyDeductionFactory' );
				$cdf->setCompany( $company_id );
				$cdf->setStatus( 10 ); //Enabled
				$cdf->setType( 20 ); //Deduction
				$cdf->setName( 'Vacation Release' );
				$cdf->setCalculation( 10 );
				$cdf->setCalculationOrder( 51 );
				$cdf->setPayStubEntryAccount( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName($company_id, 10, 'Vacation Accrual Release') );
				$cdf->setUserValue1( 4 );

				if ( $cdf->isValid() ) {
					$cdf->Save(FALSE);

					$cdf->setIncludePayStubEntryAccount( array( $psea_obj->getTotalGross() ) );
					$exclude_ids = array(
										self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName($company_id, 10, 'Vacation Accrual Release'),
										self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName($company_id, 10, 'Vacation Time'),
										);
					$cdf->setExcludePayStubEntryAccount( $exclude_ids );

					if ( $cdf->isValid() ) {
						$cdf->Save();
					}
				}

				break;
			case 'us':
				$cdf = TTnew( 'CompanyDeductionFactory' );
				$cdf->setCompany( $company_id );
				$cdf->setStatus( 10 ); //Enabled
				$cdf->setType( 10 ); //Tax
				$cdf->setName( 'Federal Income Tax' );
				$cdf->setCalculation( 100 );
				$cdf->setCalculationOrder( 100 );
				$cdf->setCountry( 'US' );
				$cdf->setPayStubEntryAccount( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName( $company_id, 20, '%Federal Income%') );
				$cdf->setUserValue1( 10 ); //Single
				$cdf->setUserValue2( 1 ); //0 Allowances

				if ( $cdf->isValid() ) {
					$cdf->Save(FALSE);

					$cdf->setIncludePayStubEntryAccount( array( $psea_obj->getTotalGross() ) );

					unset($exclude_ids);

					if ( $cdf->isValid() ) {
						$cdf->Save();
					}
				}

				/*
				//Repealed as of 31-Dec-2010.
				$cdf = TTnew( 'CompanyDeductionFactory' );
				$cdf->setCompany( $company_id );
				$cdf->setStatus( 10 ); //Enabled
				$cdf->setType( 10 ); //Tax
				$cdf->setName( 'Advance Earned Income Credit (EIC)' );
				$cdf->setCalculation( 80 );
				$cdf->setCalculationOrder( 105 );
				$cdf->setPayStubEntryAccount( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName( $company_id, 20, '%Advance EIC%') );
				$cdf->setUserValue1( 10 ); //Single

				if ( $cdf->isValid() ) {
					$cdf->Save(FALSE);

					$cdf->setIncludePayStubEntryAccount( array( $psea_obj->getTotalGross() ) );

					unset($exclude_ids);

					if ( $cdf->isValid() ) {
						$cdf->Save();
					}
				}
				*/

				$cdf = TTnew( 'CompanyDeductionFactory' );
				$cdf->setCompany( $company_id );
				$cdf->setStatus( 10 ); //Enabled
				$cdf->setType( 10 ); //Tax
				$cdf->setName( 'Federal Unemployment Insurance - Employer' );
				$cdf->setCalculation( 15 );
				$cdf->setCalculationOrder( 80 );
				$cdf->setPayStubEntryAccount( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName($company_id, 30, 'Fed. Unemployment Ins.') );
				$cdf->setUserValue1( 0.80 ); //2009
				$cdf->setUserValue2( 7000 );
				$cdf->setUserValue3( 0 );

				if ( $cdf->isValid() ) {
					$cdf->Save(FALSE);

					$cdf->setIncludePayStubEntryAccount( array( $psea_obj->getTotalGross() ));

					if ( $cdf->isValid() ) {
						$cdf->Save();
					}
				}

				$cdf = TTnew( 'CompanyDeductionFactory' );
				$cdf->setCompany( $company_id );
				$cdf->setStatus( 10 ); //Enabled
				$cdf->setType( 10 ); //Tax
				$cdf->setName( 'Social Security - Employee' );
				$cdf->setCalculation( 15 );
				$cdf->setCalculationOrder( 80 );
				$cdf->setPayStubEntryAccount( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName($company_id, 20, 'Social Security (FICA)') );
				$cdf->setUserValue1( 4.2 ); //2012, differ from employer rate.
				$cdf->setUserValue2( 110100 );
				$cdf->setUserValue3( 0 );

				if ( $cdf->isValid() ) {
					$cdf->Save(FALSE);

					$cdf->setIncludePayStubEntryAccount( array( $psea_obj->getTotalGross() ));

					if ( $cdf->isValid() ) {
						$cdf->Save();
					}
				}

				$cdf = TTnew( 'CompanyDeductionFactory' );
				$cdf->setCompany( $company_id );
				$cdf->setStatus( 10 ); //Enabled
				$cdf->setType( 10 ); //Tax
				$cdf->setName( 'Social Security - Employer' );
				$cdf->setCalculation( 15 );
				$cdf->setCalculationOrder( 85 );
				$cdf->setPayStubEntryAccount( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName($company_id, 30, 'Social Security%') );
				$cdf->setUserValue1( 6.2 ); //2012, differ from employee rate.
				$cdf->setUserValue2( 110100 );
				$cdf->setUserValue3( 0 );

				if ( $cdf->isValid() ) {
					$cdf->Save(FALSE);

					$cdf->setIncludePayStubEntryAccount( array( $psea_obj->getTotalGross() ));

					if ( $cdf->isValid() ) {
						$cdf->Save();
					}
				}

				$cdf = TTnew( 'CompanyDeductionFactory' );
				$cdf->setCompany( $company_id );
				$cdf->setStatus( 10 ); //Enabled
				$cdf->setType( 10 ); //Tax
				$cdf->setName( 'Medicare - Employee' );
				$cdf->setCalculation( 10 );
				$cdf->setCalculationOrder( 90 );
				$cdf->setPayStubEntryAccount( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName($company_id, 20, 'Medicare') );
				$cdf->setUserValue1( 1.45 ); //2009

				if ( $cdf->isValid() ) {
					$cdf->Save(FALSE);

					$cdf->setIncludePayStubEntryAccount( array( $psea_obj->getTotalGross() ));

					if ( $cdf->isValid() ) {
						$cdf->Save();
					}
				}

				$cdf = TTnew( 'CompanyDeductionFactory' );
				$cdf->setCompany( $company_id );
				$cdf->setStatus( 10 ); //Enabled
				$cdf->setType( 10 ); //Tax
				$cdf->setName( 'Medicare - Employer' );
				$cdf->setCalculation( 10 );
				$cdf->setCalculationOrder( 95 );
				$cdf->setPayStubEntryAccount( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName($company_id, 30, 'Medicare') );
				$cdf->setUserValue1( 100 );

				if ( $cdf->isValid() ) {
					$cdf->Save(FALSE);

					//$cdf->setIncludePayStubEntryAccount( array( $psea_obj->getTotalGross() ));
					$cdf->setIncludePayStubEntryAccount( array( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName($company_id, 20, 'Medicare') ) );

					if ( $cdf->isValid() ) {
						$cdf->Save();
					}
				}

				break;
			case 'cr':
				$cdf = TTnew( 'CompanyDeductionFactory' );
				$cdf->setCompany( $company_id );
				$cdf->setStatus( 10 ); //Enabled
				$cdf->setType( 10 ); //Tax
				$cdf->setName( TTi18n::gettext('Income Tax') );
				$cdf->setCalculation( 100 );
				$cdf->setCalculationOrder( 100 );
				$cdf->setCountry( 'CR' );
				$cdf->setPayStubEntryAccount( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName( $company_id, 20, '%Federal Income%') );
				$cdf->setUserValue1( 10 ); //Single
				$cdf->setUserValue2( 0 ); //0 Allowances

				if ( $cdf->isValid() ) {
					$cdf->Save(FALSE);

					$cdf->setIncludePayStubEntryAccount( array( $psea_obj->getTotalGross() ) );

					unset($exclude_ids);

					if ( $cdf->isValid() ) {
				   		$cdf->Save();
					}
				}

				break;
		}

		$pd_obj = new PayrollDeduction( $country, $province );
		$pd_obj->setDate( time() );

		Debug::text('Province/State: '. $province , __FILE__, __LINE__, __METHOD__, 10);
		switch (strtolower($province)) {
			//Canada
			case 'ab':
			case 'bc':
			case 'sk':
			case 'mb':
			case 'qc':
			case 'on':
			case 'nl':
			case 'nb':
			case 'ns':
			case 'pe':
			case 'nt':
			case 'yt':
			case 'nu':
				$provincial_claim_amount = $pd_obj->getBasicProvinceClaimCodeAmount();
				break;

			//US
			case 'al':
				$cdf = TTnew( 'CompanyDeductionFactory' );
				$cdf->setCompany( $company_id );
				$cdf->setStatus( 10 ); //Enabled
				$cdf->setType( 10 ); //Tax
				$cdf->setName( strtoupper($province).' - Unemployment Insurance' );
				$cdf->setCalculation( 15 );
				$cdf->setCalculationOrder( 185 );
				$cdf->setPayStubEntryAccount( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName( $company_id, 30, 'State Unemployment Ins.') );
				$cdf->setUserValue1( 0.00 ); //2011
				$cdf->setUserValue2( 8000 );

				if ( $cdf->isValid() ) {
					$cdf->Save(FALSE);

					$cdf->setIncludePayStubEntryAccount( array( $psea_obj->getTotalGross() ) );

					unset($exclude_ids);

					if ( $cdf->isValid() ) {
						$cdf->Save();
					}
				}
				break;
			case 'ak':
				$cdf = TTnew( 'CompanyDeductionFactory' );
				$cdf->setCompany( $company_id );
				$cdf->setStatus( 10 ); //Enabled
				$cdf->setType( 10 ); //Tax
				$cdf->setName( strtoupper($province).' - Unemployment Insurance - Employer' );
				$cdf->setCalculation( 15 );
				$cdf->setCalculationOrder( 185 );
				$cdf->setPayStubEntryAccount( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName( $company_id, 30, 'State Unemployment Ins.') );
				$cdf->setUserValue1( 0.00 ); //2011
				$cdf->setUserValue2( 34600 );

				if ( $cdf->isValid() ) {
					$cdf->Save(FALSE);

					$cdf->setIncludePayStubEntryAccount( array( $psea_obj->getTotalGross() ) );

					unset($exclude_ids);

					if ( $cdf->isValid() ) {
						$cdf->Save();
					}
				}

				$cdf = TTnew( 'CompanyDeductionFactory' );
				$cdf->setCompany( $company_id );
				$cdf->setStatus( 10 ); //Enabled
				$cdf->setType( 10 ); //Tax
				$cdf->setName( strtoupper($province).' - Unemployment Insurance - Employee' );
				$cdf->setCalculation( 15 );
				$cdf->setCalculationOrder( 185 );
				$cdf->setPayStubEntryAccount( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName( $company_id, 20, 'State Unemployment Ins.') );
				$cdf->setUserValue1( 0.00 ); //2011
				$cdf->setUserValue2( 34600 );

				if ( $cdf->isValid() ) {
					$cdf->Save(FALSE);

					$cdf->setIncludePayStubEntryAccount( array( $psea_obj->getTotalGross() ) );

					unset($exclude_ids);

					if ( $cdf->isValid() ) {
						$cdf->Save();
					}
				}
				break;
			case 'ar':
				$cdf = TTnew( 'CompanyDeductionFactory' );
				$cdf->setCompany( $company_id );
				$cdf->setStatus( 10 ); //Enabled
				$cdf->setType( 10 ); //Tax
				$cdf->setName( strtoupper($province).' - Unemployment Insurance' );
				$cdf->setCalculation( 15 );
				$cdf->setCalculationOrder( 185 );
				$cdf->setPayStubEntryAccount( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName( $company_id, 30, 'State Unemployment Ins.') );
				$cdf->setUserValue1( 0.00 ); //2011
				$cdf->setUserValue2( 12000 );

				if ( $cdf->isValid() ) {
					$cdf->Save(FALSE);

					$cdf->setIncludePayStubEntryAccount( array( $psea_obj->getTotalGross() ) );

					unset($exclude_ids);

					if ( $cdf->isValid() ) {
						$cdf->Save();
					}
				}
				break;
			case 'az':
				$cdf = TTnew( 'CompanyDeductionFactory' );
				$cdf->setCompany( $company_id );
				$cdf->setStatus( 10 ); //Enabled
				$cdf->setType( 10 ); //Tax
				$cdf->setName( strtoupper($province).' - Unemployment Insurance' );
				$cdf->setCalculation( 15 );
				$cdf->setCalculationOrder( 185 );
				$cdf->setPayStubEntryAccount( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName( $company_id, 30, 'State Unemployment Ins.') );
				$cdf->setUserValue1( 0.00 ); //2011
				$cdf->setUserValue2( 7000 );

				if ( $cdf->isValid() ) {
					$cdf->Save(FALSE);

					$cdf->setIncludePayStubEntryAccount( array( $psea_obj->getTotalGross() ) );

					unset($exclude_ids);

					if ( $cdf->isValid() ) {
						$cdf->Save();
					}
				}

				$cdf = TTnew( 'CompanyDeductionFactory' );
				$cdf->setCompany( $company_id );
				$cdf->setStatus( 10 ); //Enabled
				$cdf->setType( 10 ); //Tax
				$cdf->setName( strtoupper($province).' - Job Training' );
				$cdf->setCalculation( 15 );
				$cdf->setCalculationOrder( 185 );
				$cdf->setPayStubEntryAccount( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName( $company_id, 30, 'State Employee Training') );
				$cdf->setUserValue1( 0.10 ); //2011
				$cdf->setUserValue2( 7000 );

				if ( $cdf->isValid() ) {
					$cdf->Save(FALSE);

					$cdf->setIncludePayStubEntryAccount( array( $psea_obj->getTotalGross() ) );

					unset($exclude_ids);

					if ( $cdf->isValid() ) {
						$cdf->Save();
					}
				}

				break;
			case 'ca':
				$cdf = TTnew( 'CompanyDeductionFactory' );
				$cdf->setCompany( $company_id );
				$cdf->setStatus( 10 ); //Enabled
				$cdf->setType( 10 ); //Tax
				$cdf->setName( strtoupper($province).' - Disability Insurance' );
				$cdf->setCalculation( 15 );
				$cdf->setCalculationOrder( 180 );
				$cdf->setPayStubEntryAccount( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName( $company_id, 20, 'State Disability Ins.') );
				$cdf->setUserValue1( 1.20 ); //2011
				$cdf->setUserValue2( 93316 );

				if ( $cdf->isValid() ) {
					$cdf->Save(FALSE);

					$cdf->setIncludePayStubEntryAccount( array( $psea_obj->getTotalGross() ) );

					unset($exclude_ids);

					if ( $cdf->isValid() ) {
						$cdf->Save();
					}
				}

				$cdf = TTnew( 'CompanyDeductionFactory' );
				$cdf->setCompany( $company_id );
				$cdf->setStatus( 10 ); //Enabled
				$cdf->setType( 10 ); //Tax
				$cdf->setName( strtoupper($province).' - Unemployment Insurance' );
				$cdf->setCalculation( 15 );
				$cdf->setCalculationOrder( 185 );
				$cdf->setPayStubEntryAccount( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName( $company_id, 30, 'State Unemployment Ins.') );
				$cdf->setUserValue1( 0 ); //2011
				$cdf->setUserValue2( 7000 );

				if ( $cdf->isValid() ) {
					$cdf->Save(FALSE);

					$cdf->setIncludePayStubEntryAccount( array( $psea_obj->getTotalGross() ) );

					unset($exclude_ids);

					if ( $cdf->isValid() ) {
						$cdf->Save();
					}
				}

				$cdf = TTnew( 'CompanyDeductionFactory' );
				$cdf->setCompany( $company_id );
				$cdf->setStatus( 10 ); //Enabled
				$cdf->setType( 10 ); //Tax
				$cdf->setName( strtoupper($province).' - Employee Training' );
				$cdf->setCalculation( 15 );
				$cdf->setCalculationOrder( 185 );
				$cdf->setPayStubEntryAccount( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName( $company_id, 30, 'State Employee Training') );
				$cdf->setUserValue1( 0.10 ); //2011
				$cdf->setUserValue2( 7000 );

				if ( $cdf->isValid() ) {
					$cdf->Save(FALSE);

					$cdf->setIncludePayStubEntryAccount( array( $psea_obj->getTotalGross() ) );

					unset($exclude_ids);

					if ( $cdf->isValid() ) {
						$cdf->Save();
					}
				}
				break;
			case 'co':
				$cdf = TTnew( 'CompanyDeductionFactory' );
				$cdf->setCompany( $company_id );
				$cdf->setStatus( 10 ); //Enabled
				$cdf->setType( 10 ); //Tax
				$cdf->setName( strtoupper($province).' - Unemployment Insurance' );
				$cdf->setCalculation( 15 );
				$cdf->setCalculationOrder( 185 );
				$cdf->setPayStubEntryAccount( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName( $company_id, 30, 'State Unemployment Ins.') );
				$cdf->setUserValue1( 0.00 ); //2011
				$cdf->setUserValue2( 10000 );

				if ( $cdf->isValid() ) {
					$cdf->Save(FALSE);

					$cdf->setIncludePayStubEntryAccount( array( $psea_obj->getTotalGross() ) );

					unset($exclude_ids);

					if ( $cdf->isValid() ) {
						$cdf->Save();
					}
				}
				break;
			case 'ct':
				$cdf = TTnew( 'CompanyDeductionFactory' );
				$cdf->setCompany( $company_id );
				$cdf->setStatus( 10 ); //Enabled
				$cdf->setType( 10 ); //Tax
				$cdf->setName( strtoupper($province).' - Unemployment Insurance' );
				$cdf->setCalculation( 15 );
				$cdf->setCalculationOrder( 185 );
				$cdf->setPayStubEntryAccount( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName( $company_id, 30, 'State Unemployment Ins.') );
				$cdf->setUserValue1( 0.00 ); //2011
				$cdf->setUserValue2( 15000 );

				if ( $cdf->isValid() ) {
					$cdf->Save(FALSE);

					$cdf->setIncludePayStubEntryAccount( array( $psea_obj->getTotalGross() ) );

					unset($exclude_ids);

					if ( $cdf->isValid() ) {
						$cdf->Save();
					}
				}
				break;
			case 'dc':
				$cdf = TTnew( 'CompanyDeductionFactory' );
				$cdf->setCompany( $company_id );
				$cdf->setStatus( 10 ); //Enabled
				$cdf->setType( 10 ); //Tax
				$cdf->setName( strtoupper($province).' - Unemployment Insurance' );
				$cdf->setCalculation( 15 );
				$cdf->setCalculationOrder( 185 );
				$cdf->setPayStubEntryAccount( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName( $company_id, 30, 'State Unemployment Ins.') );
				$cdf->setUserValue1( 0.00 ); //2011
				$cdf->setUserValue2( 9000 );

				if ( $cdf->isValid() ) {
					$cdf->Save(FALSE);

					$cdf->setIncludePayStubEntryAccount( array( $psea_obj->getTotalGross() ) );

					unset($exclude_ids);

					if ( $cdf->isValid() ) {
						$cdf->Save();
					}
				}
				break;
			case 'de':
				$cdf = TTnew( 'CompanyDeductionFactory' );
				$cdf->setCompany( $company_id );
				$cdf->setStatus( 10 ); //Enabled
				$cdf->setType( 10 ); //Tax
				$cdf->setName( strtoupper($province).' - Unemployment Insurance' );
				$cdf->setCalculation( 15 );
				$cdf->setCalculationOrder( 185 );
				$cdf->setPayStubEntryAccount( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName( $company_id, 30, 'State Unemployment Ins.') );
				$cdf->setUserValue1( 0.00 ); //2011
				$cdf->setUserValue2( 10500 );

				if ( $cdf->isValid() ) {
					$cdf->Save(FALSE);

					$cdf->setIncludePayStubEntryAccount( array( $psea_obj->getTotalGross() ) );

					unset($exclude_ids);

					if ( $cdf->isValid() ) {
						$cdf->Save();
					}
				}
				break;
			case 'fl':
				$cdf = TTnew( 'CompanyDeductionFactory' );
				$cdf->setCompany( $company_id );
				$cdf->setStatus( 10 ); //Enabled
				$cdf->setType( 10 ); //Tax
				$cdf->setName( strtoupper($province).' - Unemployment Insurance' );
				$cdf->setCalculation( 15 );
				$cdf->setCalculationOrder( 185 );
				$cdf->setPayStubEntryAccount( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName( $company_id, 30, 'State Unemployment Ins.') );
				$cdf->setUserValue1( 0.00 ); //2011
				$cdf->setUserValue2( 7000 );

				if ( $cdf->isValid() ) {
					$cdf->Save(FALSE);

					$cdf->setIncludePayStubEntryAccount( array( $psea_obj->getTotalGross() ) );

					unset($exclude_ids);

					if ( $cdf->isValid() ) {
						$cdf->Save();
					}
				}
				break;
			case 'ga':
				$cdf = TTnew( 'CompanyDeductionFactory' );
				$cdf->setCompany( $company_id );
				$cdf->setStatus( 10 ); //Enabled
				$cdf->setType( 10 ); //Tax
				$cdf->setName( strtoupper($province).' - Unemployment Insurance' );
				$cdf->setCalculation( 15 );
				$cdf->setCalculationOrder( 185 );
				$cdf->setPayStubEntryAccount( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName( $company_id, 30, 'State Unemployment Ins.') );
				$cdf->setUserValue1( 0.00 ); //2011
				$cdf->setUserValue2( 8500 );

				if ( $cdf->isValid() ) {
					$cdf->Save(FALSE);

					$cdf->setIncludePayStubEntryAccount( array( $psea_obj->getTotalGross() ) );

					unset($exclude_ids);

					if ( $cdf->isValid() ) {
						$cdf->Save();
					}
				}
				break;
			case 'hi':
				$cdf = TTnew( 'CompanyDeductionFactory' );
				$cdf->setCompany( $company_id );
				$cdf->setStatus( 10 ); //Enabled
				$cdf->setType( 10 ); //Tax
				$cdf->setName( strtoupper($province).' - Unemployment Insurance' );
				$cdf->setCalculation( 15 );
				$cdf->setCalculationOrder( 185 );
				$cdf->setPayStubEntryAccount( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName( $company_id, 30, 'State Unemployment Ins.') );
				$cdf->setUserValue1( 0.00 ); //2011
				$cdf->setUserValue2( 34200 );

				if ( $cdf->isValid() ) {
					$cdf->Save(FALSE);

					$cdf->setIncludePayStubEntryAccount( array( $psea_obj->getTotalGross() ) );

					unset($exclude_ids);

					if ( $cdf->isValid() ) {
						$cdf->Save();
					}
				}
				break;
			case 'ia':
				$cdf = TTnew( 'CompanyDeductionFactory' );
				$cdf->setCompany( $company_id );
				$cdf->setStatus( 10 ); //Enabled
				$cdf->setType( 10 ); //Tax
				$cdf->setName( strtoupper($province).' - Unemployment Insurance' );
				$cdf->setCalculation( 15 );
				$cdf->setCalculationOrder( 185 );
				$cdf->setPayStubEntryAccount( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName( $company_id, 30, 'State Unemployment Ins.') );
				$cdf->setUserValue1( 0.00 ); //2011
				$cdf->setUserValue2( 24700 );

				if ( $cdf->isValid() ) {
					$cdf->Save(FALSE);

					$cdf->setIncludePayStubEntryAccount( array( $psea_obj->getTotalGross() ) );

					unset($exclude_ids);

					if ( $cdf->isValid() ) {
						$cdf->Save();
					}
				}
				break;
			case 'id':
				$cdf = TTnew( 'CompanyDeductionFactory' );
				$cdf->setCompany( $company_id );
				$cdf->setStatus( 10 ); //Enabled
				$cdf->setType( 10 ); //Tax
				$cdf->setName( strtoupper($province).' - Unemployment Insurance' );
				$cdf->setCalculation( 15 );
				$cdf->setCalculationOrder( 185 );
				$cdf->setPayStubEntryAccount( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName( $company_id, 30, 'State Unemployment Ins.') );
				$cdf->setUserValue1( 0.00 ); //2011
				$cdf->setUserValue2( 33300 );

				if ( $cdf->isValid() ) {
					$cdf->Save(FALSE);

					$cdf->setIncludePayStubEntryAccount( array( $psea_obj->getTotalGross() ) );

					unset($exclude_ids);

					if ( $cdf->isValid() ) {
						$cdf->Save();
					}
				}
				break;
			case 'il':
				$cdf = TTnew( 'CompanyDeductionFactory' );
				$cdf->setCompany( $company_id );
				$cdf->setStatus( 10 ); //Enabled
				$cdf->setType( 10 ); //Tax
				$cdf->setName( strtoupper($province).' - Unemployment Insurance' );
				$cdf->setCalculation( 15 );
				$cdf->setCalculationOrder( 185 );
				$cdf->setPayStubEntryAccount( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName( $company_id, 30, 'State Unemployment Ins. - Employer') );
				$cdf->setUserValue1( 0.00 ); //2011
				$cdf->setUserValue2( 12740 );

				if ( $cdf->isValid() ) {
					$cdf->Save(FALSE);

					$cdf->setIncludePayStubEntryAccount( array( $psea_obj->getTotalGross() ) );

					unset($exclude_ids);

					if ( $cdf->isValid() ) {
						$cdf->Save();
					}
				}
				break;
			case 'in':
				$cdf = TTnew( 'CompanyDeductionFactory' );
				$cdf->setCompany( $company_id );
				$cdf->setStatus( 10 ); //Enabled
				$cdf->setType( 10 ); //Tax
				$cdf->setName( strtoupper($province).' - Unemployment Insurance' );
				$cdf->setCalculation( 15 );
				$cdf->setCalculationOrder( 185 );
				$cdf->setPayStubEntryAccount( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName( $company_id, 30, 'State Unemployment Ins.') );
				$cdf->setUserValue1( 0.00 ); //2011
				$cdf->setUserValue2( 9500 );

				if ( $cdf->isValid() ) {
					$cdf->Save(FALSE);

					$cdf->setIncludePayStubEntryAccount( array( $psea_obj->getTotalGross() ) );

					unset($exclude_ids);

					if ( $cdf->isValid() ) {
						$cdf->Save();
					}
				}
				break;
			case 'ks':
				$cdf = TTnew( 'CompanyDeductionFactory' );
				$cdf->setCompany( $company_id );
				$cdf->setStatus( 10 ); //Enabled
				$cdf->setType( 10 ); //Tax
				$cdf->setName( strtoupper($province).' - Unemployment Insurance' );
				$cdf->setCalculation( 15 );
				$cdf->setCalculationOrder( 185 );
				$cdf->setPayStubEntryAccount( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName( $company_id, 30, 'State Unemployment Ins.') );
				$cdf->setUserValue1( 0.00 ); //2011
				$cdf->setUserValue2( 8000 );

				if ( $cdf->isValid() ) {
					$cdf->Save(FALSE);

					$cdf->setIncludePayStubEntryAccount( array( $psea_obj->getTotalGross() ) );

					unset($exclude_ids);

					if ( $cdf->isValid() ) {
						$cdf->Save();
					}
				}
				break;
			case 'ky':
				$cdf = TTnew( 'CompanyDeductionFactory' );
				$cdf->setCompany( $company_id );
				$cdf->setStatus( 10 ); //Enabled
				$cdf->setType( 10 ); //Tax
				$cdf->setName( strtoupper($province).' - Unemployment Insurance' );
				$cdf->setCalculation( 15 );
				$cdf->setCalculationOrder( 185 );
				$cdf->setPayStubEntryAccount( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName( $company_id, 30, 'State Unemployment Ins.') );
				$cdf->setUserValue1( 0.00 ); //2011
				$cdf->setUserValue2( 8000 );

				if ( $cdf->isValid() ) {
					$cdf->Save(FALSE);

					$cdf->setIncludePayStubEntryAccount( array( $psea_obj->getTotalGross() ) );

					unset($exclude_ids);

					if ( $cdf->isValid() ) {
						$cdf->Save();
					}
				}
				break;
			case 'la':
				$cdf = TTnew( 'CompanyDeductionFactory' );
				$cdf->setCompany( $company_id );
				$cdf->setStatus( 10 ); //Enabled
				$cdf->setType( 10 ); //Tax
				$cdf->setName( strtoupper($province).' - Unemployment Insurance' );
				$cdf->setCalculation( 15 );
				$cdf->setCalculationOrder( 185 );
				$cdf->setPayStubEntryAccount( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName( $company_id, 30, 'State Unemployment Ins.') );
				$cdf->setUserValue1( 0.00 ); //2011
				$cdf->setUserValue2( 7700 );

				if ( $cdf->isValid() ) {
					$cdf->Save(FALSE);

					$cdf->setIncludePayStubEntryAccount( array( $psea_obj->getTotalGross() ) );

					unset($exclude_ids);

					if ( $cdf->isValid() ) {
						$cdf->Save();
					}
				}
				break;
			case 'ma':
				$cdf = TTnew( 'CompanyDeductionFactory' );
				$cdf->setCompany( $company_id );
				$cdf->setStatus( 10 ); //Enabled
				$cdf->setType( 10 ); //Tax
				$cdf->setName( strtoupper($province).' - Unemployment Insurance' );
				$cdf->setCalculation( 15 );
				$cdf->setCalculationOrder( 185 );
				$cdf->setPayStubEntryAccount( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName( $company_id, 30, 'State Unemployment Ins.') );
				$cdf->setUserValue1( 0.00 ); //2011
				$cdf->setUserValue2( 14000 );

				if ( $cdf->isValid() ) {
					$cdf->Save(FALSE);

					$cdf->setIncludePayStubEntryAccount( array( $psea_obj->getTotalGross() ) );

					unset($exclude_ids);

					if ( $cdf->isValid() ) {
						$cdf->Save();
					}
				}
				break;
			case 'md':
				$cdf = TTnew( 'CompanyDeductionFactory' );
				$cdf->setCompany( $company_id );
				$cdf->setStatus( 10 ); //Enabled
				$cdf->setType( 10 ); //Tax
				$cdf->setName( strtoupper($province).' - Unemployment Insurance' );
				$cdf->setCalculation( 15 );
				$cdf->setCalculationOrder( 185 );
				$cdf->setPayStubEntryAccount( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName( $company_id, 30, 'State Unemployment Ins.') );
				$cdf->setUserValue1( 0.00 ); //2011
				$cdf->setUserValue2( 8500 );

				if ( $cdf->isValid() ) {
					$cdf->Save(FALSE);

					$cdf->setIncludePayStubEntryAccount( array( $psea_obj->getTotalGross() ) );

					unset($exclude_ids);

					if ( $cdf->isValid() ) {
						$cdf->Save();
					}
				}
				break;
			case 'me':
				$cdf = TTnew( 'CompanyDeductionFactory' );
				$cdf->setCompany( $company_id );
				$cdf->setStatus( 10 ); //Enabled
				$cdf->setType( 10 ); //Tax
				$cdf->setName( strtoupper($province).' - Unemployment Insurance' );
				$cdf->setCalculation( 15 );
				$cdf->setCalculationOrder( 185 );
				$cdf->setPayStubEntryAccount( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName( $company_id, 30, 'State Unemployment Ins.') );
				$cdf->setUserValue1( 0.00 ); //2011
				$cdf->setUserValue2( 12000 );

				if ( $cdf->isValid() ) {
					$cdf->Save(FALSE);

					$cdf->setIncludePayStubEntryAccount( array( $psea_obj->getTotalGross() ) );

					unset($exclude_ids);

					if ( $cdf->isValid() ) {
						$cdf->Save();
					}
				}
				break;
			case 'mi':
				$cdf = TTnew( 'CompanyDeductionFactory' );
				$cdf->setCompany( $company_id );
				$cdf->setStatus( 10 ); //Enabled
				$cdf->setType( 10 ); //Tax
				$cdf->setName( strtoupper($province).' - Unemployment Insurance' );
				$cdf->setCalculation( 15 );
				$cdf->setCalculationOrder( 185 );
				$cdf->setPayStubEntryAccount( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName( $company_id, 30, 'State Unemployment Ins.') );
				$cdf->setUserValue1( 0.00 ); //2011
				$cdf->setUserValue2( 9000 );

				if ( $cdf->isValid() ) {
					$cdf->Save(FALSE);

					$cdf->setIncludePayStubEntryAccount( array( $psea_obj->getTotalGross() ) );

					unset($exclude_ids);

					if ( $cdf->isValid() ) {
						$cdf->Save();
					}
				}
				break;
			case 'mn':
				$cdf = TTnew( 'CompanyDeductionFactory' );
				$cdf->setCompany( $company_id );
				$cdf->setStatus( 10 ); //Enabled
				$cdf->setType( 10 ); //Tax
				$cdf->setName( strtoupper($province).' - Unemployment Insurance' );
				$cdf->setCalculation( 15 );
				$cdf->setCalculationOrder( 185 );
				$cdf->setPayStubEntryAccount( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName( $company_id, 30, 'State Unemployment Ins.') );
				$cdf->setUserValue1( 0.00 ); //2011
				$cdf->setUserValue2( 27000 );

				if ( $cdf->isValid() ) {
					$cdf->Save(FALSE);

					$cdf->setIncludePayStubEntryAccount( array( $psea_obj->getTotalGross() ) );

					unset($exclude_ids);

					if ( $cdf->isValid() ) {
						$cdf->Save();
					}
				}
				break;
			case 'mo':
				$cdf = TTnew( 'CompanyDeductionFactory' );
				$cdf->setCompany( $company_id );
				$cdf->setStatus( 10 ); //Enabled
				$cdf->setType( 10 ); //Tax
				$cdf->setName( strtoupper($province).' - Unemployment Insurance' );
				$cdf->setCalculation( 15 );
				$cdf->setCalculationOrder( 185 );
				$cdf->setPayStubEntryAccount( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName( $company_id, 30, 'State Unemployment Ins.') );
				$cdf->setUserValue1( 0.00 ); //2011
				$cdf->setUserValue2( 13000 );

				if ( $cdf->isValid() ) {
					$cdf->Save(FALSE);

					$cdf->setIncludePayStubEntryAccount( array( $psea_obj->getTotalGross() ) );

					unset($exclude_ids);

					if ( $cdf->isValid() ) {
						$cdf->Save();
					}
				}
				break;
			case 'ms':
				$cdf = TTnew( 'CompanyDeductionFactory' );
				$cdf->setCompany( $company_id );
				$cdf->setStatus( 10 ); //Enabled
				$cdf->setType( 10 ); //Tax
				$cdf->setName( strtoupper($province).' - Unemployment Insurance' );
				$cdf->setCalculation( 15 );
				$cdf->setCalculationOrder( 185 );
				$cdf->setPayStubEntryAccount( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName( $company_id, 30, 'State Unemployment Ins.') );
				$cdf->setUserValue1( 0.00 ); //2011
				$cdf->setUserValue2( 14000 );

				if ( $cdf->isValid() ) {
					$cdf->Save(FALSE);

					$cdf->setIncludePayStubEntryAccount( array( $psea_obj->getTotalGross() ) );

					unset($exclude_ids);

					if ( $cdf->isValid() ) {
						$cdf->Save();
					}
				}
				break;
			case 'mt':
				$cdf = TTnew( 'CompanyDeductionFactory' );
				$cdf->setCompany( $company_id );
				$cdf->setStatus( 10 ); //Enabled
				$cdf->setType( 10 ); //Tax
				$cdf->setName( strtoupper($province).' - Unemployment Insurance' );
				$cdf->setCalculation( 15 );
				$cdf->setCalculationOrder( 185 );
				$cdf->setPayStubEntryAccount( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName( $company_id, 30, 'State Unemployment Ins.') );
				$cdf->setUserValue1( 0.00 ); //2011
				$cdf->setUserValue2( 26300 );

				if ( $cdf->isValid() ) {
					$cdf->Save(FALSE);

					$cdf->setIncludePayStubEntryAccount( array( $psea_obj->getTotalGross() ) );

					unset($exclude_ids);

					if ( $cdf->isValid() ) {
						$cdf->Save();
					}
				}
				break;
			case 'nc':
				$cdf = TTnew( 'CompanyDeductionFactory' );
				$cdf->setCompany( $company_id );
				$cdf->setStatus( 10 ); //Enabled
				$cdf->setType( 10 ); //Tax
				$cdf->setName( strtoupper($province).' - Unemployment Insurance' );
				$cdf->setCalculation( 15 );
				$cdf->setCalculationOrder( 185 );
				$cdf->setPayStubEntryAccount( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName( $company_id, 30, 'State Unemployment Ins.') );
				$cdf->setUserValue1( 0.00 ); //2011
				$cdf->setUserValue2( 19700 );

				if ( $cdf->isValid() ) {
					$cdf->Save(FALSE);

					$cdf->setIncludePayStubEntryAccount( array( $psea_obj->getTotalGross() ) );

					unset($exclude_ids);

					if ( $cdf->isValid() ) {
						$cdf->Save();
					}
				}
				break;
			case 'nd':
				$cdf = TTnew( 'CompanyDeductionFactory' );
				$cdf->setCompany( $company_id );
				$cdf->setStatus( 10 ); //Enabled
				$cdf->setType( 10 ); //Tax
				$cdf->setName( strtoupper($province).' - Unemployment Insurance' );
				$cdf->setCalculation( 15 );
				$cdf->setCalculationOrder( 185 );
				$cdf->setPayStubEntryAccount( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName( $company_id, 30, 'State Unemployment Ins.') );
				$cdf->setUserValue1( 0.00 ); //2011
				$cdf->setUserValue2( 25500 );

				if ( $cdf->isValid() ) {
					$cdf->Save(FALSE);

					$cdf->setIncludePayStubEntryAccount( array( $psea_obj->getTotalGross() ) );

					unset($exclude_ids);

					if ( $cdf->isValid() ) {
						$cdf->Save();
					}
				}
				break;

			case 'nh':
				$cdf = TTnew( 'CompanyDeductionFactory' );
				$cdf->setCompany( $company_id );
				$cdf->setStatus( 10 ); //Enabled
				$cdf->setType( 10 ); //Tax
				$cdf->setName( strtoupper($province).' - Unemployment Insurance' );
				$cdf->setCalculation( 15 );
				$cdf->setCalculationOrder( 185 );
				$cdf->setPayStubEntryAccount( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName( $company_id, 30, 'State Unemployment Ins.') );
				$cdf->setUserValue1( 0.00 ); //2011
				$cdf->setUserValue2( 12000 );

				if ( $cdf->isValid() ) {
					$cdf->Save(FALSE);

					$cdf->setIncludePayStubEntryAccount( array( $psea_obj->getTotalGross() ) );

					unset($exclude_ids);

					if ( $cdf->isValid() ) {
						$cdf->Save();
					}
				}
				break;
			case 'ne':
				$cdf = TTnew( 'CompanyDeductionFactory' );
				$cdf->setCompany( $company_id );
				$cdf->setStatus( 10 ); //Enabled
				$cdf->setType( 10 ); //Tax
				$cdf->setName( strtoupper($province).' - Unemployment Insurance' );
				$cdf->setCalculation( 15 );
				$cdf->setCalculationOrder( 185 );
				$cdf->setPayStubEntryAccount( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName( $company_id, 30, 'State Unemployment Ins.') );
				$cdf->setUserValue1( 0.00 ); //2011
				$cdf->setUserValue2( 9000 );

				if ( $cdf->isValid() ) {
					$cdf->Save(FALSE);

					$cdf->setIncludePayStubEntryAccount( array( $psea_obj->getTotalGross() ) );

					unset($exclude_ids);

					if ( $cdf->isValid() ) {
						$cdf->Save();
					}
				}
				break;
			case 'nj':
				$cdf = TTnew( 'CompanyDeductionFactory' );
				$cdf->setCompany( $company_id );
				$cdf->setStatus( 10 ); //Enabled
				$cdf->setType( 10 ); //Tax
				$cdf->setName( strtoupper($province).' - Unemployment Insurance: Employee' );
				$cdf->setCalculation( 15 );
				$cdf->setCalculationOrder( 185 );
				$cdf->setPayStubEntryAccount( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName( $company_id, 20, 'State Unemployment Ins.') );
				$cdf->setUserValue1( 0.00 ); //2011
				$cdf->setUserValue2( 29600 );

				if ( $cdf->isValid() ) {
					$cdf->Save(FALSE);

					$cdf->setIncludePayStubEntryAccount( array( $psea_obj->getTotalGross() ) );

					unset($exclude_ids);

					if ( $cdf->isValid() ) {
						$cdf->Save();
					}
				}

				$cdf = TTnew( 'CompanyDeductionFactory' );
				$cdf->setCompany( $company_id );
				$cdf->setStatus( 10 ); //Enabled
				$cdf->setType( 10 ); //Tax
				$cdf->setName( strtoupper($province).' - Unemployment Insurance: Company' );
				$cdf->setCalculation( 15 );
				$cdf->setCalculationOrder( 185 );
				$cdf->setPayStubEntryAccount( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName( $company_id, 30, 'State Unemployment Ins.') );
				$cdf->setUserValue1( 0.00 ); //2011
				$cdf->setUserValue2( 29600 );

				if ( $cdf->isValid() ) {
					$cdf->Save(FALSE);

					$cdf->setIncludePayStubEntryAccount( array( $psea_obj->getTotalGross() ) );

					unset($exclude_ids);

					if ( $cdf->isValid() ) {
						$cdf->Save();
					}
				}
				break;
			case 'nm':
				$cdf = TTnew( 'CompanyDeductionFactory' );
				$cdf->setCompany( $company_id );
				$cdf->setStatus( 10 ); //Enabled
				$cdf->setType( 10 ); //Tax
				$cdf->setName( strtoupper($province).' - Unemployment Insurance' );
				$cdf->setCalculation( 15 );
				$cdf->setCalculationOrder( 185 );
				$cdf->setPayStubEntryAccount( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName( $company_id, 30, 'State Unemployment Ins.') );
				$cdf->setUserValue1( 0.00 ); //2011
				$cdf->setUserValue2( 21900 );

				if ( $cdf->isValid() ) {
					$cdf->Save(FALSE);

					$cdf->setIncludePayStubEntryAccount( array( $psea_obj->getTotalGross() ) );

					unset($exclude_ids);

					if ( $cdf->isValid() ) {
						$cdf->Save();
					}
				}
				break;
			case 'nv':
				$cdf = TTnew( 'CompanyDeductionFactory' );
				$cdf->setCompany( $company_id );
				$cdf->setStatus( 10 ); //Enabled
				$cdf->setType( 10 ); //Tax
				$cdf->setName( strtoupper($province).' - Unemployment Insurance' );
				$cdf->setCalculation( 15 );
				$cdf->setCalculationOrder( 185 );
				$cdf->setPayStubEntryAccount( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName( $company_id, 30, 'State Unemployment Ins.') );
				$cdf->setUserValue1( 0.00 ); //2011
				$cdf->setUserValue2( 26600 );

				if ( $cdf->isValid() ) {
					$cdf->Save(FALSE);

					$cdf->setIncludePayStubEntryAccount( array( $psea_obj->getTotalGross() ) );

					unset($exclude_ids);

					if ( $cdf->isValid() ) {
						$cdf->Save();
					}
				}
				break;
			case 'ny':
				$cdf = TTnew( 'CompanyDeductionFactory' );
				$cdf->setCompany( $company_id );
				$cdf->setStatus( 10 ); //Enabled
				$cdf->setType( 10 ); //Tax
				$cdf->setName( strtoupper($province).' - Unemployment Insurance' );
				$cdf->setCalculation( 15 );
				$cdf->setCalculationOrder( 185 );
				$cdf->setPayStubEntryAccount( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName( $company_id, 30, 'State Unemployment Ins.') );
				$cdf->setUserValue1( 0 ); //2011
				$cdf->setUserValue2( 8500 );

				if ( $cdf->isValid() ) {
					$cdf->Save(FALSE);

					$cdf->setIncludePayStubEntryAccount( array( $psea_obj->getTotalGross() ) );

					unset($exclude_ids);

					if ( $cdf->isValid() ) {
						$cdf->Save();
					}
				}

				$cdf = TTnew( 'CompanyDeductionFactory' );
				$cdf->setCompany( $company_id );
				$cdf->setStatus( 10 ); //Enabled
				$cdf->setType( 10 ); //Tax
				$cdf->setName( strtoupper($province).' - Reemployment Service Fund' );
				$cdf->setCalculation( 15 );
				$cdf->setCalculationOrder( 185 );
				$cdf->setPayStubEntryAccount( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName( $company_id, 30, 'State Reemployment') );
				$cdf->setUserValue1( 0.075 ); //2011
				$cdf->setUserValue2( 8500 );

				if ( $cdf->isValid() ) {
					$cdf->Save(FALSE);

					$cdf->setIncludePayStubEntryAccount( array( $psea_obj->getTotalGross() ) );

					unset($exclude_ids);

					if ( $cdf->isValid() ) {
						$cdf->Save();
					}
				}

				$cdf = TTnew( 'CompanyDeductionFactory' );
				$cdf->setCompany( $company_id );
				$cdf->setStatus( 10 ); //Enabled
				$cdf->setType( 10 ); //Tax
				$cdf->setName( strtoupper($province).' - Disability Insurance' );
				$cdf->setCalculation( 15 );
				$cdf->setCalculationOrder( 180 );
				$cdf->setPayStubEntryAccount( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName( $company_id, 20, 'State Disability Ins.') );
				$cdf->setUserValue1( 0.50 ); //2011
				$cdf->setUserValue2( 6240 ); //Max $0.60/week

				if ( $cdf->isValid() ) {
					$cdf->Save(FALSE);

					$cdf->setIncludePayStubEntryAccount( array( $psea_obj->getTotalGross() ) );

					unset($exclude_ids);

					if ( $cdf->isValid() ) {
						$cdf->Save();
					}
				}

				break;
			case 'oh':
				$cdf = TTnew( 'CompanyDeductionFactory' );
				$cdf->setCompany( $company_id );
				$cdf->setStatus( 10 ); //Enabled
				$cdf->setType( 10 ); //Tax
				$cdf->setName( strtoupper($province).' - Unemployment Insurance' );
				$cdf->setCalculation( 15 );
				$cdf->setCalculationOrder( 185 );
				$cdf->setPayStubEntryAccount( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName( $company_id, 30, 'State Unemployment Ins.') );
				$cdf->setUserValue1( 0.00 ); //2011
				$cdf->setUserValue2( 9000 );

				if ( $cdf->isValid() ) {
					$cdf->Save(FALSE);

					$cdf->setIncludePayStubEntryAccount( array( $psea_obj->getTotalGross() ) );

					unset($exclude_ids);

					if ( $cdf->isValid() ) {
						$cdf->Save();
					}
				}
				break;
			case 'ok':
				$cdf = TTnew( 'CompanyDeductionFactory' );
				$cdf->setCompany( $company_id );
				$cdf->setStatus( 10 ); //Enabled
				$cdf->setType( 10 ); //Tax
				$cdf->setName( strtoupper($province).' - Unemployment Insurance' );
				$cdf->setCalculation( 15 );
				$cdf->setCalculationOrder( 185 );
				$cdf->setPayStubEntryAccount( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName( $company_id, 30, 'State Unemployment Ins.') );
				$cdf->setUserValue1( 0.00 ); //2011
				$cdf->setUserValue2( 18600 );

				if ( $cdf->isValid() ) {
					$cdf->Save(FALSE);

					$cdf->setIncludePayStubEntryAccount( array( $psea_obj->getTotalGross() ) );

					unset($exclude_ids);

					if ( $cdf->isValid() ) {
						$cdf->Save();
					}
				}
				break;
			case 'or':
				$cdf = TTnew( 'CompanyDeductionFactory' );
				$cdf->setCompany( $company_id );
				$cdf->setStatus( 10 ); //Enabled
				$cdf->setType( 10 ); //Tax
				$cdf->setName( strtoupper($province).' - Unemployment Insurance' );
				$cdf->setCalculation( 15 );
				$cdf->setCalculationOrder( 185 );
				$cdf->setPayStubEntryAccount( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName( $company_id, 30, 'State Unemployment Insurance') );
				$cdf->setUserValue1( 0.00 ); //2011
				$cdf->setUserValue2( 32300 );

				if ( $cdf->isValid() ) {
					$cdf->Save(FALSE);

					$cdf->setIncludePayStubEntryAccount( array( $psea_obj->getTotalGross() ) );

					unset($exclude_ids);

					if ( $cdf->isValid() ) {
						$cdf->Save();
					}
				}
				break;
			case 'pa':
				$cdf = TTnew( 'CompanyDeductionFactory' );
				$cdf->setCompany( $company_id );
				$cdf->setStatus( 10 ); //Enabled
				$cdf->setType( 10 ); //Tax
				$cdf->setName( strtoupper($province).' - Unemployment Insurance' );
				$cdf->setCalculation( 15 );
				$cdf->setCalculationOrder( 185 );
				$cdf->setPayStubEntryAccount( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName( $company_id, 30, 'State Unemployment Ins.') );
				$cdf->setUserValue1( 0.00 ); //2011
				$cdf->setUserValue2( 8000 );

				if ( $cdf->isValid() ) {
					$cdf->Save(FALSE);

					$cdf->setIncludePayStubEntryAccount( array( $psea_obj->getTotalGross() ) );

					unset($exclude_ids);

					if ( $cdf->isValid() ) {
						$cdf->Save();
					}
				}
				break;
			case 'ri':
				$cdf = TTnew( 'CompanyDeductionFactory' );
				$cdf->setCompany( $company_id );
				$cdf->setStatus( 10 ); //Enabled
				$cdf->setType( 10 ); //Tax
				$cdf->setName( strtoupper($province).' - Employment Security' );
				$cdf->setCalculation( 15 );
				$cdf->setCalculationOrder( 185 );
				$cdf->setPayStubEntryAccount( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName( $company_id, 30, 'State Unemployment Ins.') );
				$cdf->setUserValue1( 0.00 ); //2011
				$cdf->setUserValue2( 19000 );

				if ( $cdf->isValid() ) {
					$cdf->Save(FALSE);

					$cdf->setIncludePayStubEntryAccount( array( $psea_obj->getTotalGross() ) );

					unset($exclude_ids);

					if ( $cdf->isValid() ) {
						$cdf->Save();
					}
				}
				break;
			case 'sc':
				$cdf = TTnew( 'CompanyDeductionFactory' );
				$cdf->setCompany( $company_id );
				$cdf->setStatus( 10 ); //Enabled
				$cdf->setType( 10 ); //Tax
				$cdf->setName( strtoupper($province).' - Unemployment Insurance' );
				$cdf->setCalculation( 15 );
				$cdf->setCalculationOrder( 185 );
				$cdf->setPayStubEntryAccount( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName( $company_id, 30, 'State Unemployment Ins.') );
				$cdf->setUserValue1( 0.00 ); //2011
				$cdf->setUserValue2( 10000 );

				if ( $cdf->isValid() ) {
					$cdf->Save(FALSE);

					$cdf->setIncludePayStubEntryAccount( array( $psea_obj->getTotalGross() ) );

					unset($exclude_ids);

					if ( $cdf->isValid() ) {
						$cdf->Save();
					}
				}
				break;
			case 'sd':
				$cdf = TTnew( 'CompanyDeductionFactory' );
				$cdf->setCompany( $company_id );
				$cdf->setStatus( 10 ); //Enabled
				$cdf->setType( 10 ); //Tax
				$cdf->setName( strtoupper($province).' - Unemployment Insurance' );
				$cdf->setCalculation( 15 );
				$cdf->setCalculationOrder( 185 );
				$cdf->setPayStubEntryAccount( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName( $company_id, 30, 'State Unemployment Ins.') );
				$cdf->setUserValue1( 0.00 ); //2011
				$cdf->setUserValue2( 11000 );

				if ( $cdf->isValid() ) {
					$cdf->Save(FALSE);

					$cdf->setIncludePayStubEntryAccount( array( $psea_obj->getTotalGross() ) );

					unset($exclude_ids);

					if ( $cdf->isValid() ) {
						$cdf->Save();
					}
				}
				break;
			case 'tn':
				$cdf = TTnew( 'CompanyDeductionFactory' );
				$cdf->setCompany( $company_id );
				$cdf->setStatus( 10 ); //Enabled
				$cdf->setType( 10 ); //Tax
				$cdf->setName( strtoupper($province).' - Unemployment Insurance' );
				$cdf->setCalculation( 15 );
				$cdf->setCalculationOrder( 185 );
				$cdf->setPayStubEntryAccount( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName( $company_id, 30, 'State Unemployment Ins.') );
				$cdf->setUserValue1( 0.00 ); //2011
				$cdf->setUserValue2( 9000 );

				if ( $cdf->isValid() ) {
					$cdf->Save(FALSE);

					$cdf->setIncludePayStubEntryAccount( array( $psea_obj->getTotalGross() ) );

					unset($exclude_ids);

					if ( $cdf->isValid() ) {
						$cdf->Save();
					}
				}
				break;
			case 'tx':
				$cdf = TTnew( 'CompanyDeductionFactory' );
				$cdf->setCompany( $company_id );
				$cdf->setStatus( 10 ); //Enabled
				$cdf->setType( 10 ); //Tax
				$cdf->setName( strtoupper($province).' - Unemployment Insurance' );
				$cdf->setCalculation( 15 );
				$cdf->setCalculationOrder( 185 );
				$cdf->setPayStubEntryAccount( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName( $company_id, 30, 'State Unemployment Ins.') );
				$cdf->setUserValue1( 0 ); //2011
				$cdf->setUserValue2( 9000 );

				if ( $cdf->isValid() ) {
					$cdf->Save(FALSE);

					$cdf->setIncludePayStubEntryAccount( array( $psea_obj->getTotalGross() ) );

					unset($exclude_ids);

					if ( $cdf->isValid() ) {
						$cdf->Save();
					}
				}

				$cdf = TTnew( 'CompanyDeductionFactory' );
				$cdf->setCompany( $company_id );
				$cdf->setStatus( 10 ); //Enabled
				$cdf->setType( 10 ); //Tax
				$cdf->setName( strtoupper($province).' - Employee Training' );
				$cdf->setCalculation( 15 );
				$cdf->setCalculationOrder( 185 );
				$cdf->setPayStubEntryAccount( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName( $company_id, 30, 'State Employee Training') );
				$cdf->setUserValue1( 0.10 ); //2011
				$cdf->setUserValue2( 9000 );

				if ( $cdf->isValid() ) {
					$cdf->Save(FALSE);

					$cdf->setIncludePayStubEntryAccount( array( $psea_obj->getTotalGross() ) );

					unset($exclude_ids);

					if ( $cdf->isValid() ) {
						$cdf->Save();
					}
				}
				break;
			case 'ut':
				$cdf = TTnew( 'CompanyDeductionFactory' );
				$cdf->setCompany( $company_id );
				$cdf->setStatus( 10 ); //Enabled
				$cdf->setType( 10 ); //Tax
				$cdf->setName( strtoupper($province).' - Unemployment Insurance' );
				$cdf->setCalculation( 15 );
				$cdf->setCalculationOrder( 185 );
				$cdf->setPayStubEntryAccount( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName( $company_id, 30, 'State Unemployment Ins.') );
				$cdf->setUserValue1( 0.00 ); //2011
				$cdf->setUserValue2( 28600 );

				if ( $cdf->isValid() ) {
					$cdf->Save(FALSE);

					$cdf->setIncludePayStubEntryAccount( array( $psea_obj->getTotalGross() ) );

					unset($exclude_ids);

					if ( $cdf->isValid() ) {
						$cdf->Save();
					}
				}
				break;
			case 'va':
				$cdf = TTnew( 'CompanyDeductionFactory' );
				$cdf->setCompany( $company_id );
				$cdf->setStatus( 10 ); //Enabled
				$cdf->setType( 10 ); //Tax
				$cdf->setName( strtoupper($province).' - Unemployment Insurance' );
				$cdf->setCalculation( 15 );
				$cdf->setCalculationOrder( 185 );
				$cdf->setPayStubEntryAccount( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName( $company_id, 30, 'State Unemployment Ins.') );
				$cdf->setUserValue1( 0.00 ); //2011
				$cdf->setUserValue2( 8000 );

				if ( $cdf->isValid() ) {
					$cdf->Save(FALSE);

					$cdf->setIncludePayStubEntryAccount( array( $psea_obj->getTotalGross() ) );

					unset($exclude_ids);

					if ( $cdf->isValid() ) {
						$cdf->Save();
					}
				}
				break;
			case 'vt':
				$cdf = TTnew( 'CompanyDeductionFactory' );
				$cdf->setCompany( $company_id );
				$cdf->setStatus( 10 ); //Enabled
				$cdf->setType( 10 ); //Tax
				$cdf->setName( strtoupper($province).' - Unemployment Insurance' );
				$cdf->setCalculation( 15 );
				$cdf->setCalculationOrder( 185 );
				$cdf->setPayStubEntryAccount( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName( $company_id, 30, 'State Unemployment Ins.') );
				$cdf->setUserValue1( 0.00 ); //2011
				$cdf->setUserValue2( 13000 );

				if ( $cdf->isValid() ) {
					$cdf->Save(FALSE);

					$cdf->setIncludePayStubEntryAccount( array( $psea_obj->getTotalGross() ) );

					unset($exclude_ids);

					if ( $cdf->isValid() ) {
						$cdf->Save();
					}
				}
				break;

			case 'wa':
				$cdf = TTnew( 'CompanyDeductionFactory' );
				$cdf->setCompany( $company_id );
				$cdf->setStatus( 10 ); //Enabled
				$cdf->setType( 10 ); //Tax
				$cdf->setName( strtoupper($province).' - Unemployment Insurance' );
				$cdf->setCalculation( 15 );
				$cdf->setCalculationOrder( 185 );
				$cdf->setPayStubEntryAccount( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName( $company_id, 30, 'State Unemployment Ins.') );
				$cdf->setUserValue1( 0.00 ); //2011
				$cdf->setUserValue2( 37300 );

				if ( $cdf->isValid() ) {
					$cdf->Save(FALSE);

					$cdf->setIncludePayStubEntryAccount( array( $psea_obj->getTotalGross() ) );

					unset($exclude_ids);

					if ( $cdf->isValid() ) {
						$cdf->Save();
					}
				}
				break;
			case 'wi':
				$cdf = TTnew( 'CompanyDeductionFactory' );
				$cdf->setCompany( $company_id );
				$cdf->setStatus( 10 ); //Enabled
				$cdf->setType( 10 ); //Tax
				$cdf->setName( strtoupper($province).' - Unemployment Insurance' );
				$cdf->setCalculation( 15 );
				$cdf->setCalculationOrder( 185 );
				$cdf->setPayStubEntryAccount( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName( $company_id, 30, 'State Unemployment Ins.') );
				$cdf->setUserValue1( 0.00 ); //2011
				$cdf->setUserValue2( 13000 );

				if ( $cdf->isValid() ) {
					$cdf->Save(FALSE);

					$cdf->setIncludePayStubEntryAccount( array( $psea_obj->getTotalGross() ) );

					unset($exclude_ids);

					if ( $cdf->isValid() ) {
						$cdf->Save();
					}
				}
				break;
			case 'wv':
				$cdf = TTnew( 'CompanyDeductionFactory' );
				$cdf->setCompany( $company_id );
				$cdf->setStatus( 10 ); //Enabled
				$cdf->setType( 10 ); //Tax
				$cdf->setName( strtoupper($province).' - Unemployment Insurance' );
				$cdf->setCalculation( 15 );
				$cdf->setCalculationOrder( 185 );
				$cdf->setPayStubEntryAccount( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName( $company_id, 30, 'State Unemployment Ins.') );
				$cdf->setUserValue1( 0.00 ); //2011
				$cdf->setUserValue2( 12000 );

				if ( $cdf->isValid() ) {
					$cdf->Save(FALSE);

					$cdf->setIncludePayStubEntryAccount( array( $psea_obj->getTotalGross() ) );

					unset($exclude_ids);

					if ( $cdf->isValid() ) {
						$cdf->Save();
					}
				}
				break;
			case 'wy':
				$cdf = TTnew( 'CompanyDeductionFactory' );
				$cdf->setCompany( $company_id );
				$cdf->setStatus( 10 ); //Enabled
				$cdf->setType( 10 ); //Tax
				$cdf->setName( strtoupper($province).' - Unemployment Insurance' );
				$cdf->setCalculation( 15 );
				$cdf->setCalculationOrder( 185 );
				$cdf->setPayStubEntryAccount( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName( $company_id, 30, 'State Unemployment Ins.') );
				$cdf->setUserValue1( 0.00 ); //2011
				$cdf->setUserValue2( 22300 );

				if ( $cdf->isValid() ) {
					$cdf->Save(FALSE);

					$cdf->setIncludePayStubEntryAccount( array( $psea_obj->getTotalGross() ) );

					unset($exclude_ids);

					if ( $cdf->isValid() ) {
						$cdf->Save();
					}
				}
				break;

		}

		if ( $country == 'CA' ) {
			$cdf = TTnew( 'CompanyDeductionFactory' );
			$cdf->setCompany( $company_id );
			$cdf->setStatus( 10 ); //Enabled
			$cdf->setType( 10 ); //Tax
			$cdf->setName( strtoupper($province) .' - Provincial Income Tax' );
			$cdf->setCalculation( 200 );
			$cdf->setCalculationOrder( 110 );
			$cdf->setCountry( 'CA' );
			$cdf->setProvince( strtoupper($province) );
			$cdf->setPayStubEntryAccount( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName($company_id, 20, '%Provincial Income%') );
			$cdf->setUserValue1( $provincial_claim_amount );

			if ( $cdf->isValid() ) {
				$cdf->Save(FALSE);

				$exclude_ids = array(
									//Not proper way to do it with CPP/EI
									//self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName($company_id, 'CPP'),
									//self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName($company_id, 'EI'),
									self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName($company_id, 20, 'Union'),
									);
				$cdf->setIncludePayStubEntryAccount( array( $psea_obj->getTotalGross() ) );
				$cdf->setExcludePayStubEntryAccount( $exclude_ids );

				unset($exclude_ids);

				if ( $cdf->isValid() ) {
					$cdf->Save();
				}
			}
		} elseif ( $country == 'US' ) {
			$cdf = TTnew( 'CompanyDeductionFactory' );
			$cdf->setCompany( $company_id );
			$cdf->setStatus( 10 ); //Enabled
			$cdf->setType( 10 ); //Tax
			$cdf->setName( 'State Income Tax' );
			$cdf->setCalculation( 200 );
			$cdf->setCalculationOrder( 200 );
			$cdf->setCountry( 'US' );
			$cdf->setProvince( strtoupper($province) );
			$cdf->setPayStubEntryAccount( self::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName( $company_id, 20, '%State Income%') );

			//FIXME: Not all states are the same. Need to customize UserValues for each one.
			$cdf->setUserValue1( 10 ); //Single
			$cdf->setUserValue2( 1 ); //0 Allowances

			if ( $cdf->isValid() ) {
				$cdf->Save(FALSE);

				$cdf->setIncludePayStubEntryAccount( array( $psea_obj->getTotalGross() ) );

				unset($exclude_ids);

				if ( $cdf->isValid() ) {
					$cdf->Save();
				}
			}
		}

		$cdf->CommitTransaction();
		//$cdf->FailTransaction();

		return TRUE;
	}


    function Validate() {

        if ( $this->getCalculation() == 69 ) {
            
            $valid_formula = TTMath::ValidateFormula( TTMath::translateVariables( $this->getCompanyValue1(), TTMath::clearVariables( Misc::trimSortPrefix( $this->getOptions('formula_variables') ) ) ) ); 

            if ( $valid_formula != FALSE ) {
                $this->Validator->isTrue(	'company_value1',
											FALSE,
											implode("\n", $valid_formula) );
            }

        }

        return TRUE;

	}


	function preSave() {

		//Set Length of service in days.
		$this->setMinimumLengthOfServiceDays( $this->getMinimumLengthOfService() );
		$this->setMaximumLengthOfServiceDays( $this->getMaximumLengthOfService() );

		return TRUE;
	}

	function postSave() {
		$this->removeCache( $this->getId() );
		$this->removeCache( 'include_pay_stub_entry-'. $this->getId() );
		$this->removeCache( 'exclude_pay_stub_entry-'. $this->getId() );

		if ( $this->getDeleted() == TRUE ) {
			//Check if any users are assigned to this, if so, delete mappings.
			$udlf = TTnew( 'UserDeductionListFactory' );

			$udlf->StartTransaction();
			$udlf->getByCompanyIdAndCompanyDeductionId( $this->getCompany(), $this->getId() );
			if ( $udlf->getRecordCount() ) {
				foreach( $udlf as $ud_obj ) {
					$ud_obj->setDeleted(TRUE);
					if ( $ud_obj->isValid() ) {
						$ud_obj->Save();
					}
				}
			}
			$udlf->CommitTransaction();
		}

		return TRUE;
	}

	function setObjectFromArray( $data ) {
		if ( is_array( $data ) ) {
			$variable_function_map = $this->getVariableToFunctionMap();
			foreach( $variable_function_map as $key => $function ) {
				if ( isset($data[$key]) ) {

					$function = 'set'.$function;
					switch( $key ) {
						case 'start_date':
						case 'end_date':
							if ( method_exists( $this, $function ) ) {
								$this->$function( TTDate::parseDateTime( $data[$key] ) );
							}
							break;
						default:
							if ( method_exists( $this, $function ) ) {
								$this->$function( $data[$key] );
							}
							break;
					}
				}
			}

			$this->setCreatedAndUpdatedColumns( $data );

			return TRUE;
		}

		return FALSE;
	}

	function getObjectAsArray( $include_columns = NULL ) {
		$variable_function_map = $this->getVariableToFunctionMap();
		if ( is_array( $variable_function_map ) ) {
			foreach( $variable_function_map as $variable => $function_stub ) {
				if ( $include_columns == NULL OR ( isset($include_columns[$variable]) AND $include_columns[$variable] == TRUE ) ) {

					$function = 'get'.$function_stub;
					switch( $variable ) {
						case 'status':
						case 'type':
						case 'calculation':
							$function = 'get'.$variable;
							if ( method_exists( $this, $function ) ) {
								$data[$variable] = Option::getByKey( $this->$function(), $this->getOptions( $variable ) );
							}
							break;
						case 'start_date':
						case 'end_date':
							if ( method_exists( $this, $function ) ) {
								$data[$variable] = TTDate::getAPIDate( 'DATE', $this->$function() );
							}
							break;
						default:
							if ( method_exists( $this, $function ) ) {
								$data[$variable] = $this->$function();
							}
							break;
					}

				}
			}
			$this->getCreatedAndUpdatedColumns( $data, $include_columns );
		}

		return $data;
	}

	function addLog( $log_action ) {
		return TTLog::addEntry( $this->getId(), $log_action,  TTi18n::getText('Tax / Deduction'), NULL, $this->getTable(), $this );
	}
}
?>
