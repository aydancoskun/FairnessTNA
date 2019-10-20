<?php
/*********************************************************************************
 * FairnessTNA is a Workforce Management program forked from TimeTrex in 2013,
 * copyright Aydan Coskun. Original code base is copyright TimeTrex Software Inc.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * You can contact Aydan Coskun via issue tracker on github.com/aydancoskun
 ********************************************************************************/


/**
 * @package Core
 */
class PermissionFactory extends Factory {
	protected $table = 'permission';
	protected $pk_sequence_name = 'permission_id_seq'; //PK Sequence name

	protected $permission_control_obj = NULL;
	protected $company_id = NULL;

	/**
	 * @param $name
	 * @param null $parent
	 * @return array|bool|null
	 */
	function _getFactoryOptions( $name, $parent = NULL ) {
		$retval = NULL;
		switch( $name ) {
			case 'preset':
				$retval = array(
										//-1 => TTi18n::gettext('--'),
										10 => TTi18n::gettext('Regular Employee (Punch In/Out)'),
										12 => TTi18n::gettext('Regular Employee (Manual Punch)'), //Can manually Add/Edit own punches/absences.
										14 => TTi18n::gettext('Regular Employee (Manual TimeSheet)'), //Can use manual timesheet and punches.
										18 => TTi18n::gettext('Supervisor (Subordinates Only)'),
										20 => TTi18n::gettext('Supervisor (All Employees)'),
										25 => TTi18n::gettext('HR Manager'),
										30 => TTi18n::gettext('Payroll Administrator'),
										40 => TTi18n::gettext('Administrator')
									);

				if ( getTTProductEdition() == TT_PRODUCT_COMMUNITY ) {
					unset($retval[14]);
				}
				break;
			case 'common_permissions':
				$retval = array(
											'add' => TTi18n::gettext('Add'),
											'view' => TTi18n::gettext('View'),
											'view_own' => TTi18n::gettext('View Own'),
											'view_child' => TTi18n::gettext('View Subordinate'),
											'edit' => TTi18n::gettext('Edit'),
											'edit_own' => TTi18n::gettext('Edit Own'),
											'edit_child' => TTi18n::gettext('Edit Subordinate'),
											'delete' => TTi18n::gettext('Delete'),
											'delete_own' => TTi18n::gettext('Delete Own'),
											'delete_child' => TTi18n::gettext('Delete Subordinate'),
											'other' => TTi18n::gettext('Other'),
											);

				if ( defined('FAIRNESS_API') == TRUE AND FAIRNESS_API == TRUE ) {
					$retval = Misc::addSortPrefix( $retval, 1000 );
				}
				break;
			case 'preset_flags':
				//Remove sections that don't apply to the current product edition.
				$product_edition = Misc::getCurrentCompanyProductEdition();

				if ( $product_edition >= TT_PRODUCT_COMMUNITY ) {
					$retval[10] = TTi18n::gettext('Scheduling');
					$retval[20] = TTi18n::gettext('Time & Attendance');
					$retval[30] = TTi18n::gettext('Payroll');
					$retval[70] = TTi18n::gettext('Human Resources');
				}

				if ( $product_edition >= TT_PRODUCT_CORPORATE ) {
					$retval[40] = TTi18n::gettext('Job Costing');
					$retval[50] = TTi18n::gettext('Document Management');
					$retval[60] = TTi18n::gettext('Invoicing');
				}

				if ( $product_edition >= TT_PRODUCT_ENTERPRISE ) {
					$retval[75] = TTi18n::gettext('Recruitment');
					$retval[80] = TTi18n::gettext('Expense Tracking');
				}
				ksort($retval);
				break;
			case 'preset_level':
				$retval = array(
										10 => 1,
										12 => 2,
										14 => 3,
										18 => 10,
										20 => 15,
										25 => 18,
										30 => 20,
										40 => 25,
									);
				break;
			case 'section_group':
				$retval = array(
											0 => TTi18n::gettext('-- Please Choose --'),
											'all' => TTi18n::gettext('-- All --'),
											'company' => TTi18n::gettext('Company'),
											'user' => TTi18n::gettext('Employee'),
											'schedule' => TTi18n::gettext('Schedule'),
											'attendance' => TTi18n::gettext('Attendance'),
											'job' => TTi18n::gettext('Job Tracking'),
											'invoice' => TTi18n::gettext('Invoicing'),
											'payroll' => TTi18n::gettext('Payroll'),
											'policy' => TTi18n::gettext('Policies'),
											'report' => TTi18n::gettext('Reports'),
											'hr' => TTi18n::gettext('Human Resources (HR)'),
											'recruitment' => TTi18n::gettext('Recruitment'),
											);

				//Remove sections that don't apply to the current product edition.
				$product_edition = Misc::getCurrentCompanyProductEdition();

				//if ( $product_edition == TT_PRODUCT_ENTERPRISE ) { //Enterprise
				// } elseif {
				if ( $product_edition == TT_PRODUCT_CORPORATE ) { //Corporate
					unset( $retval['recruitment'] );
				} elseif ( $product_edition == TT_PRODUCT_COMMUNITY OR $product_edition == TT_PRODUCT_PROFESSIONAL ) { //Community or Professional
					unset( $retval['job'], $retval['invoice'], $retval['recruitment'] );
				}

				if ( defined('FAIRNESS_API') == TRUE AND FAIRNESS_API == TRUE ) {
					unset($retval[0]);
					$retval = Misc::addSortPrefix( $retval, 1000 );
					ksort($retval);
				}

				break;
			case 'section_group_map':
				$retval = array(
										'company' => array(
															'system',
															'company',
															'legal_entity',
															'currency',
															'branch',
															'department',
															'geo_fence',
															'station',
															'hierarchy',
															'authorization',
															'message',
															'other_field',
															'document',
															'help',
															'permission',
															'pay_period_schedule',
															),
										'user'	=> array(
															'user',
															'user_preference',
															'user_tax_deduction',
															'user_contact',
															'remittance_destination_account'
														),
										'schedule'	=> array(
															'schedule',
															'recurring_schedule',
															'recurring_schedule_template',
														),
										'attendance'	=> array(
															'punch',
															'user_date_total',
															'absence',
															'accrual',
															'request',
														),
										'job'	=> array(
															'job',
															'job_item',
															'job_report',
														),
										'invoice'	=> array(
															'invoice_config',
															'client',
															'client_payment',
															'product',
															'tax_policy',
															'area_policy',
															'shipping_policy',
															'payment_gateway',
															'transaction',
															'invoice',
															'invoice_report'
														),
										'policy'	=> array(
															'policy_group',
															'pay_code',
															'pay_formula_policy',
															'contributing_pay_code_policy',
															'contributing_shift_policy',
															'schedule_policy',
															'meal_policy',
															'break_policy',
															'regular_time_policy',
															'over_time_policy',
															'premium_policy',
															'accrual_policy',
															'absence_policy',
															'round_policy',
															'exception_policy',
															'holiday_policy',
															'expense_policy',
														),
										'payroll'	=> array(
															'pay_stub_account',
															'pay_stub',
															'government_document',
															'pay_stub_amendment',
															'payroll_remittance_agency',
															'remittance_source_account',
															'wage',
															'roe',
															'company_tax_deduction',
															'user_expense',
														),
										'report'	=> array(
															'report',
															'report_custom_column',
														),
										'hr' => array(
														'qualification',
														'user_education',
														'user_license',
														'user_skill',
														'user_membership',
														'user_language',
														'kpi',
														'user_review',
														'job_vacancy',
														'job_applicant',
														'job_application',
														'hr_report',
														),
										'recruitment' => array(
														'job_vacancy',
														'job_applicant',
														'job_application',
														'recruitment_report',
														),
										);

				//Remove sections that don't apply to the current product edition.
				$product_edition = Misc::getCurrentCompanyProductEdition();
				//if ( $product_edition == TT_PRODUCT_ENTERPRISE ) { //Enterprise
				//} else
				if ( $product_edition == TT_PRODUCT_CORPORATE ) { //Corporate
					unset( $retval['recruitment'] );
					unset( $retval['payroll'][array_search( 'user_expense', $retval['payroll'])], $retval['policy'][array_search( 'expense_policy', $retval['policy'])] );
				} elseif ( $product_edition == TT_PRODUCT_COMMUNITY OR $product_edition == TT_PRODUCT_PROFESSIONAL ) { //Community or Professional
					unset( $retval['recruitment'], $retval['invoice'], $retval['job'], $retval['geo_fence'], $retval['government_document'] );
					unset( $retval['payroll'][array_search( 'user_expense', $retval['payroll'])], $retval['policy'][array_search( 'expense_policy', $retval['policy'])] );
				}

				break;
			case 'section':
				$retval = array(
										'system' => TTi18n::gettext('System'),
										'company' => TTi18n::gettext('Company'),
										'legal_entity' => TTi18n::gettext('Legal Entity'),
										'currency' => TTi18n::gettext('Currency'),
										'branch' => TTi18n::gettext('Branch'),
										'department' => TTi18n::gettext('Department'),
										'geo_fence' => TTi18n::gettext('GEO Fence'),
										'station' => TTi18n::gettext('Station'),
										'hierarchy' => TTi18n::gettext('Hierarchy'),
										'authorization' => TTi18n::gettext('Authorization'),
										'other_field' => TTi18n::gettext('Other Fields'),
										'document' => TTi18n::gettext('Documents'),
										'message' => TTi18n::gettext('Message'),
										'help' => TTi18n::gettext('Help'),
										'permission' => TTi18n::gettext('Permissions'),

										'user' => TTi18n::gettext('Employees'),
										'user_preference' => TTi18n::gettext('Employee Preferences'),
										'user_tax_deduction' => TTi18n::gettext('Employee Tax / Deductions'),
										'user_contact' => TTi18n::gettext('Employee Contact'),
										'remittance_destination_account' => TTi18n::gettext('Employee Payment Methods'),

										'schedule' => TTi18n::gettext('Schedule'),
										'recurring_schedule' => TTi18n::gettext('Recurring Schedule'),
										'recurring_schedule_template' => TTi18n::gettext('Recurring Schedule Template'),

										'request' => TTi18n::gettext('Requests'),
										'accrual' => TTi18n::gettext('Accruals'),
										'punch' => TTi18n::gettext('Punch'),
										'user_date_total' => TTi18n::gettext('TimeSheet Accumulated Time'),
										'absence' => TTi18n::gettext('Absence'),

										'job' => TTi18n::gettext('Jobs'),
										'job_item' => TTi18n::gettext('Job Tasks'),
										'job_report' => TTi18n::gettext('Job Reports'),

										'invoice_config' => TTi18n::gettext('Invoice Settings'),
										'client' => TTi18n::gettext('Invoice Clients'),
										'client_payment' => TTi18n::gettext('Client Payment Methods'),
										'product' => TTi18n::gettext('Products'),
										'tax_policy' => TTi18n::gettext('Tax Policies'),
										'shipping_policy' => TTi18n::gettext('Shipping Policies'),
										'area_policy' => TTi18n::gettext('Area Policies'),
										'payment_gateway' => TTi18n::gettext('Payment Gateway'),
										'transaction' => TTi18n::gettext('Invoice Transactions'),
										'invoice' => TTi18n::gettext('Invoices'),
										'invoice_report' => TTi18n::gettext('Invoice Reports'),

										'policy_group' => TTi18n::gettext('Policy Group'),
										'pay_code' => TTi18n::gettext('Pay Codes'),
										'pay_formula_policy' => TTi18n::gettext('Pay Formulas'),
										'contributing_pay_code_policy' => TTi18n::gettext('Contributing Pay Code Policies'),
										'contributing_shift_policy' => TTi18n::gettext('Contributing Shift Policies'),
										'schedule_policy' => TTi18n::gettext('Schedule Policies'),
										'meal_policy' => TTi18n::gettext('Meal Policies'),
										'break_policy' => TTi18n::gettext('Break Policies'),
										'regular_time_policy' => TTi18n::gettext('Regular Time Policies'),
										'over_time_policy' => TTi18n::gettext('Overtime Policies'),
										'premium_policy' => TTi18n::gettext('Premium Policies'),
										'accrual_policy' => TTi18n::gettext('Accrual Policies'),
										'absence_policy' => TTi18n::gettext('Absence Policies'),
										'round_policy' => TTi18n::gettext('Rounding Policies'),
										'exception_policy' => TTi18n::gettext('Exception Policies'),
										'holiday_policy' => TTi18n::gettext('Holiday Policies'),
										'expense_policy' => TTi18n::gettext('Expense Policies'),

										'pay_stub_account' => TTi18n::gettext('Pay Stub Accounts'),
										'payroll_remittance_agency' => TTi18n::gettext('Payroll Remittance Agency'),
										'remittance_source_account' => TTi18n::gettext('Remittance Source Account'),
										'pay_stub' => TTi18n::gettext('Employee Pay Stubs'),
										'government_document' => TTi18n::gettext('Government Documents'),
										'pay_stub_amendment' => TTi18n::gettext('Employee Pay Stub Amendments'),
										'wage' => TTi18n::gettext('Wages'),
										'pay_period_schedule' => TTi18n::gettext('Pay Period Schedule'),
										'roe' => TTi18n::gettext('Record of Employment'),
										'company_tax_deduction' => TTi18n::gettext('Company Tax / Deductions'),
										'user_expense' => TTi18n::gettext('Employee Expenses'),

										'report' => TTi18n::gettext('Reports'),
										'report_custom_column' => TTi18n::gettext('Report Custom Column'),

										'qualification' => TTi18n::gettext('Qualifications'),
										'user_education' => TTi18n::gettext('Employee Education'),
										'user_license' => TTi18n::gettext('Employee Licenses'),
										'user_skill' => TTi18n::gettext('Employee Skills'),
										'user_membership' => TTi18n::gettext('Employee Memberships'),
										'user_language' => TTi18n::gettext('Employee Language'),

										'kpi' => TTi18n::gettext('Key Performance Indicators'),
										'user_review' => TTi18n::gettext('Employee Review'),

										'job_vacancy' => TTi18n::gettext('Job Vacancy'),
										'job_applicant' => TTi18n::gettext('Job Applicant'),
										'job_application' => TTi18n::gettext('Job Application'),

										'hr_report' => TTi18n::gettext('HR Reports'),
										'recruitment_report' => TTi18n::gettext('Recruitment Reports'),
									);
				break;
			case 'name':
				$retval = array(
											'system' => array(
																'login' => TTi18n::gettext('Login Enabled'),
															),
											'company' =>	array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view_own' => TTi18n::gettext('View Own'),
																'view' => TTi18n::gettext('View'),
																'add' => TTi18n::gettext('Add'),
																'edit_own' => TTi18n::gettext('Edit Own'),
																'edit' => TTi18n::gettext('Edit'),
																'delete_own' => TTi18n::gettext('Delete Own'),
																'delete' => TTi18n::gettext('Delete'),
																//'undelete' => TTi18n::gettext('Un-Delete'),
																//'edit_own_bank' => TTi18n::gettext('Edit Own Banking Information'),
																'login_other_user' => TTi18n::gettext('Login as Other Employee')
															),
											'user' =>	array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view_own' => TTi18n::gettext('View Own'),
																'view_child' => TTi18n::gettext('View Subordinate'),
																'view' => TTi18n::gettext('View'),
																'add' => TTi18n::gettext('Add'),
																'edit_own' => TTi18n::gettext('Edit Own'),
																'edit_child' => TTi18n::gettext('Edit Subordinate'),
																'edit' => TTi18n::gettext('Edit'),
																'edit_advanced' => TTi18n::gettext('Edit Advanced'),
																//'edit_own_bank' => TTi18n::gettext('Edit Own Bank Info'),
																//'edit_child_bank' => TTi18n::gettext('Edit Subordinate Bank Info'),
																//'edit_bank' => TTi18n::gettext('Edit Bank Info'),
																'edit_permission_group' => TTi18n::gettext('Edit Permission Group'),
																'edit_pay_period_schedule' => TTi18n::gettext('Edit Pay Period Schedule'),
																'edit_policy_group' => TTi18n::gettext('Edit Policy Group'),
																'edit_hierarchy' => TTi18n::gettext('Edit Hierarchy'),
																'edit_own_password' => TTi18n::gettext('Edit Own Password'),
																'edit_own_phone_password' => TTi18n::gettext('Edit Own Quick Punch Password'),
																'enroll' => TTi18n::gettext('Enroll Employees'),
																'enroll_child' => TTi18n::gettext('Enroll Subordinate'),
																'timeclock_admin' => TTi18n::gettext('TimeClock Administrator'),
																'delete_own' => TTi18n::gettext('Delete Own'),
																'delete_child' => TTi18n::gettext('Delete Subordinate'),
																'delete' => TTi18n::gettext('Delete'),
																'view_sin' => TTi18n::gettext('View SIN/SSN'),
																//'undelete' => TTi18n::gettext('Un-Delete')
															),
											'user_contact' => array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view_own' => TTi18n::gettext('View Own'),
																'view_child' => TTi18n::gettext('View Subordinate'),
																'view' => TTi18n::gettext('View'),
																'add' => TTi18n::gettext('Add'),
																'edit_own' => TTi18n::gettext('Edit Own'),
																'edit_child' => TTi18n::gettext('Edit Subordinate'),
																'edit' => TTi18n::gettext('Edit'),
																'delete_own' => TTi18n::gettext('Delete Own'),
																'delete_child' => TTi18n::gettext('Delete Subordinate'),
																'delete' => TTi18n::gettext('Delete'),
																//'view_sin' => TTi18n::gettext('View SIN/SSN'),
																//'undelete' => TTi18n::gettext('Un-Delete')
															),
											'user_preference' =>	array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view_own' => TTi18n::gettext('View Own'),
																'view_child' => TTi18n::gettext('View Subordinate'),
																'view' => TTi18n::gettext('View'),
																'add' => TTi18n::gettext('Add'),
																'edit_own' => TTi18n::gettext('Edit Own'),
																'edit_child' => TTi18n::gettext('Edit Subordinate'),
																'edit' => TTi18n::gettext('Edit'),
																'delete_own' => TTi18n::gettext('Delete Own'),
																'delete_child' => TTi18n::gettext('Delete Subordinate'),
																'delete' => TTi18n::gettext('Delete'),
																//'undelete' => TTi18n::gettext('Un-Delete')
															),
											'user_tax_deduction' =>	array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view_own' => TTi18n::gettext('View Own'),
																'view_child' => TTi18n::gettext('View Subordinate'),
																'view' => TTi18n::gettext('View'),
																'add' => TTi18n::gettext('Add'),
																'edit_own' => TTi18n::gettext('Edit Own'),
																'edit_child' => TTi18n::gettext('Edit Subordinate'),
																'edit' => TTi18n::gettext('Edit'),
																'delete_own' => TTi18n::gettext('Delete Own'),
																'delete_child' => TTi18n::gettext('Delete Subordinate'),
																'delete' => TTi18n::gettext('Delete'),
																//'undelete' => TTi18n::gettext('Un-Delete')
															),
											'roe' =>	array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view_own' => TTi18n::gettext('View Own'),
																'view_child' => TTi18n::gettext('View Subordinate'),
																'view' => TTi18n::gettext('View'),
																'add' => TTi18n::gettext('Add'),
																'edit_own' => TTi18n::gettext('Edit Own'),
																'edit_child' => TTi18n::gettext('Edit Subordinate'),
																'edit' => TTi18n::gettext('Edit'),
																'delete_own' => TTi18n::gettext('Delete Own'),
																'delete_child' => TTi18n::gettext('Delete Subordinate'),
																'delete' => TTi18n::gettext('Delete'),
																//'undelete' => TTi18n::gettext('Un-Delete')
															),
											'company_tax_deduction' =>	array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view_own' => TTi18n::gettext('View Own'),
																'view' => TTi18n::gettext('View'),
																'add' => TTi18n::gettext('Add'),
																'edit_own' => TTi18n::gettext('Edit Own'),
																'edit' => TTi18n::gettext('Edit'),
																'delete_own' => TTi18n::gettext('Delete Own'),
																'delete' => TTi18n::gettext('Delete'),
																//'undelete' => TTi18n::gettext('Un-Delete')
															),
											'user_expense' =>	array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view_own' => TTi18n::gettext('View Own'),
																'view_child' => TTi18n::gettext('View Subordinate'),
																'view' => TTi18n::gettext('View'),
																'add' => TTi18n::gettext('Add'),
																'edit_own' => TTi18n::gettext('Edit Own'),
																'edit_child' => TTi18n::gettext('Edit Subordinate'),
																'edit' => TTi18n::gettext('Edit'),
																'delete_own' => TTi18n::gettext('Delete Own'),
																'delete' => TTi18n::gettext('Delete'),
																'delete_child' => TTi18n::gettext('Delete Subordinate'),
																'authorize' => TTi18n::gettext('Authorize Expense')
																//'undelete' => TTi18n::gettext('Un-Delete')
															),
											'pay_stub_account' =>	array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view_own' => TTi18n::gettext('View Own'),
																'view' => TTi18n::gettext('View'),
																'add' => TTi18n::gettext('Add'),
																'edit_own' => TTi18n::gettext('Edit Own'),
																'edit' => TTi18n::gettext('Edit'),
																'delete_own' => TTi18n::gettext('Delete Own'),
																'delete' => TTi18n::gettext('Delete'),
																//'undelete' => TTi18n::gettext('Un-Delete')
															),
											'payroll_remittance_agency' =>	array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view_own' => TTi18n::gettext('View Own'),
																'view' => TTi18n::gettext('View'),
																'add' => TTi18n::gettext('Add'),
																'edit_own' => TTi18n::gettext('Edit Own'),
																'edit' => TTi18n::gettext('Edit'),
																'delete_own' => TTi18n::gettext('Delete Own'),
																'delete' => TTi18n::gettext('Delete'),
															),
											'remittance_source_account' => array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view_own' => TTi18n::gettext('View Own'),
																'view' => TTi18n::gettext('View'),
																'add' => TTi18n::gettext('Add'),
																'edit_own' => TTi18n::gettext('Edit Own'),
																'edit' => TTi18n::gettext('Edit'),
																'delete_own' => TTi18n::gettext('Delete Own'),
																'delete' => TTi18n::gettext('Delete'),
															),
											'pay_stub' =>	array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view_own' => TTi18n::gettext('View Own'),
																'view_child' => TTi18n::gettext('View Subordinate'),
																'view' => TTi18n::gettext('View'),
																'add' => TTi18n::gettext('Add'),
																'edit_own' => TTi18n::gettext('Edit Own'),
																'edit_child' => TTi18n::gettext('Edit Subordinate'),
																'edit' => TTi18n::gettext('Edit'),
																'delete_own' => TTi18n::gettext('Delete Own'),
																'delete_child' => TTi18n::gettext('Delete Subordinate'),
																'delete' => TTi18n::gettext('Delete'),
																//'undelete' => TTi18n::gettext('Un-Delete')
															),
											'government_document' => array(
																'enabled' => TTi18n::gettext('Enabled'),
																'add' => TTi18n::gettext('Add'),
																'edit' => TTi18n::gettext('Edit'),
																'edit_own' => TTi18n::gettext('Edit Own'),
																'view_own' => TTi18n::gettext('View Own'),
																'view' => TTi18n::gettext('View'),
																'delete_own' => TTi18n::gettext('Delete Own'),
																'delete' => TTi18n::gettext('Delete'),
															),
											'pay_stub_amendment' =>	array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view_own' => TTi18n::gettext('View Own'),
																'view_child' => TTi18n::gettext('View Subordinate'),
																'view' => TTi18n::gettext('View'),
																'add' => TTi18n::gettext('Add'),
																'edit_own' => TTi18n::gettext('Edit Own'),
																'edit_child' => TTi18n::gettext('Edit Subordinate'),
																'edit' => TTi18n::gettext('Edit'),
																'delete_own' => TTi18n::gettext('Delete Own'),
																'delete_child' => TTi18n::gettext('Delete Subordinate'),
																'delete' => TTi18n::gettext('Delete'),
																//'undelete' => TTi18n::gettext('Un-Delete')
															),
											'wage' =>	array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view_own' => TTi18n::gettext('View Own'),
																'view_child' => TTi18n::gettext('View Subordinate'),
																'view' => TTi18n::gettext('View'),
																'add' => TTi18n::gettext('Add'),
																'edit_own' => TTi18n::gettext('Edit Own'),
																'edit_child' => TTi18n::gettext('Edit Subordinate'),
																'edit' => TTi18n::gettext('Edit'),
																'delete_own' => TTi18n::gettext('Delete Own'),
																'delete_child' => TTi18n::gettext('Delete Subordinate'),
																'delete' => TTi18n::gettext('Delete'),
																//'undelete' => TTi18n::gettext('Un-Delete')
															),
											'currency' =>	array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view_own' => TTi18n::gettext('View Own'),
																'view' => TTi18n::gettext('View'),
																'add' => TTi18n::gettext('Add'),
																'edit_own' => TTi18n::gettext('Edit Own'),
																'edit' => TTi18n::gettext('Edit'),
																'delete_own' => TTi18n::gettext('Delete Own'),
																'delete' => TTi18n::gettext('Delete'),
																//'undelete' => TTi18n::gettext('Un-Delete')
															),
											'branch' =>	array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view_own' => TTi18n::gettext('View Own'),
																'view' => TTi18n::gettext('View'),
																'add' => TTi18n::gettext('Add'),
																'edit_own' => TTi18n::gettext('Edit Own'),
																'edit' => TTi18n::gettext('Edit'),
																'delete_own' => TTi18n::gettext('Delete Own'),
																'delete' => TTi18n::gettext('Delete'),
																//'undelete' => TTi18n::gettext('Un-Delete')
															),
											'legal_entity' =>	array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view_own' => TTi18n::gettext('View Own'),
																'view' => TTi18n::gettext('View'),
																'add' => TTi18n::gettext('Add'),
																'edit_own' => TTi18n::gettext('Edit Own'),
																'edit' => TTi18n::gettext('Edit'),
																'delete_own' => TTi18n::gettext('Delete Own'),
																'delete' => TTi18n::gettext('Delete'),
											),
											'remittance_destination_account' => array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view_own' => TTi18n::gettext('View Own'),
																'view_child' => TTi18n::gettext('View Subordinate'),
																'view' => TTi18n::gettext('View'),
																'add' => TTi18n::gettext('Add'),
																'edit_own' => TTi18n::gettext('Edit Own'),
																'edit_child' => TTi18n::gettext('Edit Subordinate'),
																'edit' => TTi18n::gettext('Edit'),
																'delete_own' => TTi18n::gettext('Delete Own'),
																'delete_child' => TTi18n::gettext('Delete Subordinate'),
																'delete' => TTi18n::gettext('Delete'),
																),
											'department' =>	array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view_own' => TTi18n::gettext('View Own'),
																'view' => TTi18n::gettext('View'),
																'add' => TTi18n::gettext('Add'),
																'edit_own' => TTi18n::gettext('Edit Own'),
																'edit' => TTi18n::gettext('Edit'),
																'delete_own' => TTi18n::gettext('Delete Own'),
																'delete' => TTi18n::gettext('Delete'),
																//'undelete' => TTi18n::gettext('Un-Delete'),
																'assign' => TTi18n::gettext('Assign Employees')

															),
											'geo_fence' => array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view_own' => TTi18n::gettext('View Own'),
																'view' => TTi18n::gettext('View'),
																'add' => TTi18n::gettext('Add'),
																'edit_own' => TTi18n::gettext('Edit Own'),
																'edit' => TTi18n::gettext('Edit'),
																'delete_own' => TTi18n::gettext('Delete Own'),
																'delete' => TTi18n::gettext('Delete'),
															),
											'station' =>	array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view_own' => TTi18n::gettext('View Own'),
																'view' => TTi18n::gettext('View'),
																'add' => TTi18n::gettext('Add'),
																'edit_own' => TTi18n::gettext('Edit Own'),
																'edit' => TTi18n::gettext('Edit'),
																'delete_own' => TTi18n::gettext('Delete Own'),
																'delete' => TTi18n::gettext('Delete'),
																//'undelete' => TTi18n::gettext('Un-Delete'),
																'assign' => TTi18n::gettext('Assign Employees')
															),
											'pay_period_schedule' =>	array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view_own' => TTi18n::gettext('View Own'),
																'view' => TTi18n::gettext('View'),
																'add' => TTi18n::gettext('Add'),
																'edit_own' => TTi18n::gettext('Edit Own'),
																'edit' => TTi18n::gettext('Edit'),
																'delete_own' => TTi18n::gettext('Delete Own'),
																'delete' => TTi18n::gettext('Delete'),
																//'undelete' => TTi18n::gettext('Un-Delete'),
																'assign' => TTi18n::gettext('Assign Employees')
															),
											'schedule' =>	array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view_own' => TTi18n::gettext('View Own'),
																'view_child' => TTi18n::gettext('View Subordinate'),
																'view' => TTi18n::gettext('View'),
																'view_open' => TTi18n::gettext('View Open Shifts'),
																'add' => TTi18n::gettext('Add'),
																'edit_own' => TTi18n::gettext('Edit Own'),
																'edit_child' => TTi18n::gettext('Edit Subordinate'),
																'edit' => TTi18n::gettext('Edit'),
																'delete_own' => TTi18n::gettext('Delete Own'),
																'delete_child' => TTi18n::gettext('Delete Subordinate'),
																'delete' => TTi18n::gettext('Delete'),
																//'undelete' => TTi18n::gettext('Un-Delete'),
																'edit_branch' => TTi18n::gettext('Edit Branch Field'),
																'edit_department' => TTi18n::gettext('Edit Department Field'),
																'edit_job' => TTi18n::gettext('Edit Job Field'),
																'edit_job_item' => TTi18n::gettext('Edit Task Field'),
															),
											'other_field' =>	array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view_own' => TTi18n::gettext('View Own'),
																'view' => TTi18n::gettext('View'),
																'add' => TTi18n::gettext('Add'),
																'edit_own' => TTi18n::gettext('Edit Own'),
																'edit' => TTi18n::gettext('Edit'),
																'delete_own' => TTi18n::gettext('Delete Own'),
																'delete' => TTi18n::gettext('Delete'),
																//'undelete' => TTi18n::gettext('Un-Delete'),
															),
											'document' =>	array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view_own' => TTi18n::gettext('View Own'),
																'view' => TTi18n::gettext('View'),
																'view_private' => TTi18n::gettext('View Private'),
																'add' => TTi18n::gettext('Add'),
																'edit_own' => TTi18n::gettext('Edit Own'),
																'edit' => TTi18n::gettext('Edit'),
																'edit_private' => TTi18n::gettext('Edit Private'),
																'delete_own' => TTi18n::gettext('Delete Own'),
																'delete' => TTi18n::gettext('Delete'),
																'delete_private' => TTi18n::gettext('Delete Private'),
																//'undelete' => TTi18n::gettext('Un-Delete'),
															),
											'accrual' =>	array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view_own' => TTi18n::gettext('View Own'),
																'view_child' => TTi18n::gettext('View Subordinate'),
																'view' => TTi18n::gettext('View'),
																'add' => TTi18n::gettext('Add'),
																'edit_own' => TTi18n::gettext('Edit Own'),
																'edit_child' => TTi18n::gettext('Edit Subordinate'),
																'edit' => TTi18n::gettext('Edit'),
																'delete_own' => TTi18n::gettext('Delete Own'),
																'delete_child' => TTi18n::gettext('Delete Subordinate'),
																'delete' => TTi18n::gettext('Delete'),
																//'undelete' => TTi18n::gettext('Un-Delete')
															),
											'pay_code' =>	array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view_own' => TTi18n::gettext('View Own'),
																'view' => TTi18n::gettext('View'),
																'add' => TTi18n::gettext('Add'),
																'edit_own' => TTi18n::gettext('Edit Own'),
																'edit' => TTi18n::gettext('Edit'),
																'delete_own' => TTi18n::gettext('Delete Own'),
																'delete' => TTi18n::gettext('Delete'),
																//'undelete' => TTi18n::gettext('Un-Delete')
															),
											'pay_formula_policy' =>	array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view_own' => TTi18n::gettext('View Own'),
																'view' => TTi18n::gettext('View'),
																'add' => TTi18n::gettext('Add'),
																'edit_own' => TTi18n::gettext('Edit Own'),
																'edit' => TTi18n::gettext('Edit'),
																'delete_own' => TTi18n::gettext('Delete Own'),
																'delete' => TTi18n::gettext('Delete'),
																//'undelete' => TTi18n::gettext('Un-Delete')
															),
											'policy_group' =>	array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view_own' => TTi18n::gettext('View Own'),
																'view' => TTi18n::gettext('View'),
																'add' => TTi18n::gettext('Add'),
																'edit_own' => TTi18n::gettext('Edit Own'),
																'edit' => TTi18n::gettext('Edit'),
																'delete_own' => TTi18n::gettext('Delete Own'),
																'delete' => TTi18n::gettext('Delete'),
																//'undelete' => TTi18n::gettext('Un-Delete')
															),
											'contributing_pay_code_policy' =>	array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view_own' => TTi18n::gettext('View Own'),
																'view' => TTi18n::gettext('View'),
																'add' => TTi18n::gettext('Add'),
																'edit_own' => TTi18n::gettext('Edit Own'),
																'edit' => TTi18n::gettext('Edit'),
																'delete_own' => TTi18n::gettext('Delete Own'),
																'delete' => TTi18n::gettext('Delete'),
																//'undelete' => TTi18n::gettext('Un-Delete')
															),
											'contributing_shift_policy' =>	array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view_own' => TTi18n::gettext('View Own'),
																'view' => TTi18n::gettext('View'),
																'add' => TTi18n::gettext('Add'),
																'edit_own' => TTi18n::gettext('Edit Own'),
																'edit' => TTi18n::gettext('Edit'),
																'delete_own' => TTi18n::gettext('Delete Own'),
																'delete' => TTi18n::gettext('Delete'),
																//'undelete' => TTi18n::gettext('Un-Delete')
															),
											'schedule_policy' =>	array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view_own' => TTi18n::gettext('View Own'),
																'view' => TTi18n::gettext('View'),
																'add' => TTi18n::gettext('Add'),
																'edit_own' => TTi18n::gettext('Edit Own'),
																'edit' => TTi18n::gettext('Edit'),
																'delete_own' => TTi18n::gettext('Delete Own'),
																'delete' => TTi18n::gettext('Delete'),
																//'undelete' => TTi18n::gettext('Un-Delete')
															),
											'meal_policy' =>	array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view_own' => TTi18n::gettext('View Own'),
																'view' => TTi18n::gettext('View'),
																'add' => TTi18n::gettext('Add'),
																'edit_own' => TTi18n::gettext('Edit Own'),
																'edit' => TTi18n::gettext('Edit'),
																'delete_own' => TTi18n::gettext('Delete Own'),
																'delete' => TTi18n::gettext('Delete'),
																//'undelete' => TTi18n::gettext('Un-Delete')
															),
											'break_policy' =>	array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view_own' => TTi18n::gettext('View Own'),
																'view' => TTi18n::gettext('View'),
																'add' => TTi18n::gettext('Add'),
																'edit_own' => TTi18n::gettext('Edit Own'),
																'edit' => TTi18n::gettext('Edit'),
																'delete_own' => TTi18n::gettext('Delete Own'),
																'delete' => TTi18n::gettext('Delete'),
																//'undelete' => TTi18n::gettext('Un-Delete')
															),
											'absence_policy' =>	array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view_own' => TTi18n::gettext('View Own'),
																'view' => TTi18n::gettext('View'),
																'add' => TTi18n::gettext('Add'),
																'edit_own' => TTi18n::gettext('Edit Own'),
																'edit' => TTi18n::gettext('Edit'),
																'delete_own' => TTi18n::gettext('Delete Own'),
																'delete' => TTi18n::gettext('Delete'),
																//'undelete' => TTi18n::gettext('Un-Delete')
															),
											'accrual_policy' =>	array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view_own' => TTi18n::gettext('View Own'),
																'view' => TTi18n::gettext('View'),
																'add' => TTi18n::gettext('Add'),
																'edit_own' => TTi18n::gettext('Edit Own'),
																'edit' => TTi18n::gettext('Edit'),
																'delete_own' => TTi18n::gettext('Delete Own'),
																'delete' => TTi18n::gettext('Delete'),
																//'undelete' => TTi18n::gettext('Un-Delete')
															),
											'regular_time_policy' =>	array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view_own' => TTi18n::gettext('View Own'),
																'view' => TTi18n::gettext('View'),
																'add' => TTi18n::gettext('Add'),
																'edit_own' => TTi18n::gettext('Edit Own'),
																'edit' => TTi18n::gettext('Edit'),
																'delete_own' => TTi18n::gettext('Delete Own'),
																'delete' => TTi18n::gettext('Delete'),
																//'undelete' => TTi18n::gettext('Un-Delete')
															),
											'over_time_policy' =>	array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view_own' => TTi18n::gettext('View Own'),
																'view' => TTi18n::gettext('View'),
																'add' => TTi18n::gettext('Add'),
																'edit_own' => TTi18n::gettext('Edit Own'),
																'edit' => TTi18n::gettext('Edit'),
																'delete_own' => TTi18n::gettext('Delete Own'),
																'delete' => TTi18n::gettext('Delete'),
																//'undelete' => TTi18n::gettext('Un-Delete')
															),
											'premium_policy' =>	array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view_own' => TTi18n::gettext('View Own'),
																'view' => TTi18n::gettext('View'),
																'add' => TTi18n::gettext('Add'),
																'edit_own' => TTi18n::gettext('Edit Own'),
																'edit' => TTi18n::gettext('Edit'),
																'delete_own' => TTi18n::gettext('Delete Own'),
																'delete' => TTi18n::gettext('Delete'),
																//'undelete' => TTi18n::gettext('Un-Delete')
															),
											'round_policy' =>	array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view' => TTi18n::gettext('View'),
																'view_own' => TTi18n::gettext('View Own'),
																'add' => TTi18n::gettext('Add'),
																'edit_own' => TTi18n::gettext('Edit Own'),
																'edit' => TTi18n::gettext('Edit'),
																'delete_own' => TTi18n::gettext('Delete Own'),
																'delete' => TTi18n::gettext('Delete'),
																//'undelete' => TTi18n::gettext('Un-Delete')
															),
											'exception_policy' =>	array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view_own' => TTi18n::gettext('View Own'),
																'view' => TTi18n::gettext('View'),
																'add' => TTi18n::gettext('Add'),
																'edit_own' => TTi18n::gettext('Edit Own'),
																'edit' => TTi18n::gettext('Edit'),
																'delete_own' => TTi18n::gettext('Delete Own'),
																'delete' => TTi18n::gettext('Delete'),
																//'undelete' => TTi18n::gettext('Un-Delete')
															),
											'holiday_policy' =>	array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view_own' => TTi18n::gettext('View Own'),
																'view' => TTi18n::gettext('View'),
																'add' => TTi18n::gettext('Add'),
																'edit_own' => TTi18n::gettext('Edit Own'),
																'edit' => TTi18n::gettext('Edit'),
																'delete_own' => TTi18n::gettext('Delete Own'),
																'delete' => TTi18n::gettext('Delete'),
																//'undelete' => TTi18n::gettext('Un-Delete')
															),
											'expense_policy' =>	array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view_own' => TTi18n::gettext('View Own'),
																'view' => TTi18n::gettext('View'),
																'add' => TTi18n::gettext('Add'),
																'edit_own' => TTi18n::gettext('Edit Own'),
																'edit' => TTi18n::gettext('Edit'),
																'delete_own' => TTi18n::gettext('Delete Own'),
																'delete' => TTi18n::gettext('Delete'),
																//'undelete' => TTi18n::gettext('Un-Delete')
															),

											'recurring_schedule_template' =>	array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view_own' => TTi18n::gettext('View Own'),
																'view' => TTi18n::gettext('View'),
																'add' => TTi18n::gettext('Add'),
																'edit_own' => TTi18n::gettext('Edit Own'),
																'edit' => TTi18n::gettext('Edit'),
																'delete_own' => TTi18n::gettext('Delete Own'),
																'delete' => TTi18n::gettext('Delete'),
																//'undelete' => TTi18n::gettext('Un-Delete')
															),
											'recurring_schedule' =>	array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view_own' => TTi18n::gettext('View Own'),
																'view_child' => TTi18n::gettext('View Subordinate'),
																'view' => TTi18n::gettext('View'),
																'add' => TTi18n::gettext('Add'),
																'edit_own' => TTi18n::gettext('Edit Own'),
																'edit_child' => TTi18n::gettext('Edit Subordinate'),
																'edit' => TTi18n::gettext('Edit'),
																'delete_own' => TTi18n::gettext('Delete Own'),
																'delete_child' => TTi18n::gettext('Delete Subordinate'),
																'delete' => TTi18n::gettext('Delete'),
																//'undelete' => TTi18n::gettext('Un-Delete')
															),
											'request' =>	array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view_own' => TTi18n::gettext('View Own'),
																'view_child' => TTi18n::gettext('View Subordinate'),
																'view' => TTi18n::gettext('View'),
																'add' => TTi18n::gettext('Add'),
																'add_advanced' => TTi18n::gettext('Add Advanced'),
																'edit_own' => TTi18n::gettext('Edit Own'),
																'edit_child' => TTi18n::gettext('Edit Subordinate'),
																'edit' => TTi18n::gettext('Edit'),
																'delete_own' => TTi18n::gettext('Delete Own'),
																'delete_child' => TTi18n::gettext('Delete Subordinate'),
																'delete' => TTi18n::gettext('Delete'),
																//'undelete' => TTi18n::gettext('Un-Delete'),
																'authorize' => TTi18n::gettext('Authorize')
															),
											'punch' =>	array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view_own' => TTi18n::gettext('View Own'),
																'view_child' => TTi18n::gettext('View Subordinate'),
																'view' => TTi18n::gettext('View'),
																'add' => TTi18n::gettext('Add'),
																'edit_own' => TTi18n::gettext('Edit Own'),
																'edit_child' => TTi18n::gettext('Edit Subordinate'),
																'edit' => TTi18n::gettext('Edit'),
																'delete_own' => TTi18n::gettext('Delete Own'),
																'delete_child' => TTi18n::gettext('Delete Subordinate'),
																'delete' => TTi18n::gettext('Delete'),
																//'undelete' => TTi18n::gettext('Un-Delete'),
																'edit_transfer' => TTi18n::gettext('Edit Transfer Field'),
																'default_transfer' => TTi18n::gettext('Default Transfer On'),
																'edit_branch' => TTi18n::gettext('Edit Branch Field'),
																'edit_department' => TTi18n::gettext('Edit Department Field'),
																'edit_job' => TTi18n::gettext('Edit Job Field'),
																'edit_job_item' => TTi18n::gettext('Edit Task Field'),
																'edit_quantity' => TTi18n::gettext('Edit Quantity Field'),
																'edit_bad_quantity' => TTi18n::gettext('Edit Bad Quantity Field'),
																'edit_note' => TTi18n::gettext('Edit Note Field'),
																'edit_other_id1' => TTi18n::gettext('Edit Other ID1 Field'),
																'edit_other_id2' => TTi18n::gettext('Edit Other ID2 Field'),
																'edit_other_id3' => TTi18n::gettext('Edit Other ID3 Field'),
																'edit_other_id4' => TTi18n::gettext('Edit Other ID4 Field'),
																'edit_other_id5' => TTi18n::gettext('Edit Other ID5 Field'),

																'verify_time_sheet' => TTi18n::gettext('Verify TimeSheet'),
																'authorize' => TTi18n::gettext('Authorize TimeSheet'),

																'punch_in_out' => TTi18n::gettext('Punch In/Out'),

																'punch_timesheet' => TTi18n::gettext('Punch TimeSheet'), //Enables Punch Timesheet button for viewing.
																'manual_timesheet' => TTi18n::gettext('Manual TimeSheet'), //Enables Manual Timesheet button for viewing.
															),
											'user_date_total' => array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view_own' => TTi18n::gettext('View Own'),
																'view_child' => TTi18n::gettext('View Subordinate'),
																'view' => TTi18n::gettext('View'),
																'add' => TTi18n::gettext('Add'),
																'edit_own' => TTi18n::gettext('Edit Own'),
																'edit_child' => TTi18n::gettext('Edit Subordinate'),
																'edit' => TTi18n::gettext('Edit'),
																'delete_own' => TTi18n::gettext('Delete Own'),
																'delete_child' => TTi18n::gettext('Delete Subordinate'),
																'delete' => TTi18n::gettext('Delete'),
																//'undelete' => TTi18n::gettext('Un-Delete'),
															),
											'absence' =>	array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view_own' => TTi18n::gettext('View Own'),
																'view_child' => TTi18n::gettext('View Subordinate'),
																'view' => TTi18n::gettext('View'),
																'add' => TTi18n::gettext('Add'),
																'edit_own' => TTi18n::gettext('Edit Own'),
																'edit_child' => TTi18n::gettext('Edit Subordinate'),
																'edit' => TTi18n::gettext('Edit'),
																'delete_own' => TTi18n::gettext('Delete Own'),
																'delete_child' => TTi18n::gettext('Delete Subordinate'),
																'delete' => TTi18n::gettext('Delete'),
																//'undelete' => TTi18n::gettext('Un-Delete'),
																'edit_branch' => TTi18n::gettext('Edit Branch Field'),
																'edit_department' => TTi18n::gettext('Edit Department Field'),
																'edit_job' => TTi18n::gettext('Edit Job Field'),
																'edit_job_item' => TTi18n::gettext('Edit Task Field'),
															),
											'hierarchy' =>	array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view_own' => TTi18n::gettext('View Own'),
																'view' => TTi18n::gettext('View'),
																'add' => TTi18n::gettext('Add'),
																'edit_own' => TTi18n::gettext('Edit Own'),
																'edit' => TTi18n::gettext('Edit'),
																'delete_own' => TTi18n::gettext('Delete Own'),
																'delete' => TTi18n::gettext('Delete'),
																//'undelete' => TTi18n::gettext('Un-Delete')
															),
											'authorization' =>	array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view' => TTi18n::gettext('View')
															),
											'message' =>	array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view_own' => TTi18n::gettext('View Own'),
																'view' => TTi18n::gettext('View'),
																'add' => TTi18n::gettext('Add'),
																'add_advanced' => TTi18n::gettext('Add Advanced'),
																'edit_own' => TTi18n::gettext('Edit Own'),
																'edit' => TTi18n::gettext('Edit'),
																'delete_own' => TTi18n::gettext('Delete Own'),
																'delete' => TTi18n::gettext('Delete'),
																'send_to_any' => TTi18n::gettext('Send to Any Employee'),
																'send_to_child' => TTi18n::gettext('Send to Subordinate')
																//'undelete' => TTi18n::gettext('Un-Delete')
															),
											'help' =>	array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view_own' => TTi18n::gettext('View Own'),
																'view' => TTi18n::gettext('View'),
																'add' => TTi18n::gettext('Add'),
																'edit_own' => TTi18n::gettext('Edit Own'),
																'edit' => TTi18n::gettext('Edit'),
																'delete_own' => TTi18n::gettext('Delete Own'),
																'delete' => TTi18n::gettext('Delete'),
																//'undelete' => TTi18n::gettext('Un-Delete')
															),
											'report' =>	array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view_active_shift' => TTi18n::gettext('Whos In Summary'),
																'view_user_information' => TTi18n::gettext('Employee Information'),
																//'view_user_detail' => TTi18n::gettext('Employee Detail'),
																'view_pay_stub_summary' => TTi18n::gettext('Pay Stub Summary'),
																'view_payroll_export' => TTi18n::gettext('Payroll Export'),
																//'view_wages_payable_summary' => TTi18n::gettext('Wages Payable Summary'),
																'view_system_log' => TTi18n::gettext('Audit Trail'),
																//'view_employee_pay_stub_summary' => TTi18n::gettext('Employee Pay Stub Summary'),
																'view_timesheet_summary' => TTi18n::gettext('Timesheet Summary'),
																'view_exception_summary' => TTi18n::gettext('Exception Summary'),
																'view_accrual_balance_summary' => TTi18n::gettext('Accrual Balance Summary'),
																'view_schedule_summary' => TTi18n::gettext('Schedule Summary'),
																'view_punch_summary' => TTi18n::gettext('Punch Summary'),
																'view_remittance_summary' => TTi18n::gettext('Remittance Summary'),
																//'view_branch_summary' => TTi18n::gettext('Branch Summary'),
																'view_t4_summary' => TTi18n::gettext('T4 Summary'),
																'view_generic_tax_summary' => TTi18n::gettext('Generic Tax Summary'),
																'view_form941' => TTi18n::gettext('Form 941'),
																'view_form940' => TTi18n::gettext('Form 940'),
																'view_form940ez' => TTi18n::gettext('Form 940-EZ'),
																'view_form1099misc' => TTi18n::gettext('Form 1099-Misc'),
																'view_formW2' => TTi18n::gettext('Form W2 / W3'),
																'view_affordable_care' => TTi18n::gettext('Affordable Care'),
																'view_user_barcode' => TTi18n::gettext('Employee Barcodes'),
																'view_general_ledger_summary' => TTi18n::gettext('General Ledger Summary'),
																//'view_roe' => TTi18n::gettext('Record of employment'), //Disable for now as its not needed, use 'roe', 'view' instead.
																'view_expense' => TTi18n::gettext('Expense Summary'),
															),
											'report_custom_column' => array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view_own' => TTi18n::gettext('View Own'),
																'view_child' => TTi18n::gettext('View Subordinate'),
																'view' => TTi18n::gettext('View'),
																'add' => TTi18n::gettext('Add'),
																'edit_own' => TTi18n::gettext('Edit Own'),
																'edit_child' => TTi18n::gettext('Edit Subordinate'),
																'edit' => TTi18n::gettext('Edit'),
																'delete_own' => TTi18n::gettext('Delete Own'),
																'delete_child' => TTi18n::gettext('Delete Subordinate'),
																'delete' => TTi18n::gettext('Delete'),
																//'undelete' => TTi18n::gettext('Un-Delete')
															),
											'job' =>	array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view_own' => TTi18n::gettext('View Own'),
																'view' => TTi18n::gettext('View'),
																'add' => TTi18n::gettext('Add'),
																'edit_own' => TTi18n::gettext('Edit Own'),
																'edit' => TTi18n::gettext('Edit'),
																'delete_own' => TTi18n::gettext('Delete Own'),
																'delete' => TTi18n::gettext('Delete'),
																//'undelete' => TTi18n::gettext('Un-Delete')
															),
											'job_item' =>	array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view_own' => TTi18n::gettext('View Own'),
																'view' => TTi18n::gettext('View'),
																'add' => TTi18n::gettext('Add'),
																'edit_own' => TTi18n::gettext('Edit Own'),
																'edit' => TTi18n::gettext('Edit'),
																'delete_own' => TTi18n::gettext('Delete Own'),
																'delete' => TTi18n::gettext('Delete'),
																//'undelete' => TTi18n::gettext('Un-Delete')
															),
											'job_report' =>	array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view_job_summary' => TTi18n::gettext('Job Summary'),
																'view_job_analysis' => TTi18n::gettext('Job Analysis'),
																'view_job_payroll_analysis' => TTi18n::gettext('Job Payroll Analysis'),
																'view_job_barcode' => TTi18n::gettext('Job Barcode')
															),
											'invoice_config' =>	array(
																'enabled' => TTi18n::gettext('Enabled'),
																'add' => TTi18n::gettext('Add'),
																'edit' => TTi18n::gettext('Edit'),
																'delete' => TTi18n::gettext('Delete'),
																//'undelete' => TTi18n::gettext('Un-Delete')
															),
											'client' =>	array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view_own' => TTi18n::gettext('View Own'),
																'view' => TTi18n::gettext('View'),
																'add' => TTi18n::gettext('Add'),
																'edit_own' => TTi18n::gettext('Edit Own'),
																'edit' => TTi18n::gettext('Edit'),
																'delete_own' => TTi18n::gettext('Delete Own'),
																'delete' => TTi18n::gettext('Delete'),
																//'undelete' => TTi18n::gettext('Un-Delete')
															),
											'client_payment' =>	array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view_own' => TTi18n::gettext('View Own'),
																'view' => TTi18n::gettext('View'),
																'add' => TTi18n::gettext('Add'),
																'edit_own' => TTi18n::gettext('Edit Own'),
																'edit' => TTi18n::gettext('Edit'),
																'delete_own' => TTi18n::gettext('Delete Own'),
																'delete' => TTi18n::gettext('Delete'),
																'view_credit_card' => TTi18n::gettext('View Credit Card #'),
																//'undelete' => TTi18n::gettext('Un-Delete')
															),
											'product' =>	array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view_own' => TTi18n::gettext('View Own'),
																'view' => TTi18n::gettext('View'),
																'add' => TTi18n::gettext('Add'),
																'edit_own' => TTi18n::gettext('Edit Own'),
																'edit' => TTi18n::gettext('Edit'),
																'delete_own' => TTi18n::gettext('Delete Own'),
																'delete' => TTi18n::gettext('Delete'),
																//'undelete' => TTi18n::gettext('Un-Delete')
															),
											'tax_policy' =>	array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view_own' => TTi18n::gettext('View Own'),
																'view' => TTi18n::gettext('View'),
																'add' => TTi18n::gettext('Add'),
																'edit_own' => TTi18n::gettext('Edit Own'),
																'edit' => TTi18n::gettext('Edit'),
																'delete_own' => TTi18n::gettext('Delete Own'),
																'delete' => TTi18n::gettext('Delete'),
																//'undelete' => TTi18n::gettext('Un-Delete')
															),
											'shipping_policy' =>	array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view_own' => TTi18n::gettext('View Own'),
																'view' => TTi18n::gettext('View'),
																'add' => TTi18n::gettext('Add'),
																'edit_own' => TTi18n::gettext('Edit Own'),
																'edit' => TTi18n::gettext('Edit'),
																'delete_own' => TTi18n::gettext('Delete Own'),
																'delete' => TTi18n::gettext('Delete'),
																//'undelete' => TTi18n::gettext('Un-Delete')
															),
											'area_policy' =>	array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view_own' => TTi18n::gettext('View Own'),
																'view' => TTi18n::gettext('View'),
																'add' => TTi18n::gettext('Add'),
																'edit_own' => TTi18n::gettext('Edit Own'),
																'edit' => TTi18n::gettext('Edit'),
																'delete_own' => TTi18n::gettext('Delete Own'),
																'delete' => TTi18n::gettext('Delete'),
																//'undelete' => TTi18n::gettext('Un-Delete')
															),
											'payment_gateway' =>	array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view_own' => TTi18n::gettext('View Own'),
																'view' => TTi18n::gettext('View'),
																'add' => TTi18n::gettext('Add'),
																'edit_own' => TTi18n::gettext('Edit Own'),
																'edit' => TTi18n::gettext('Edit'),
																'delete_own' => TTi18n::gettext('Delete Own'),
																'delete' => TTi18n::gettext('Delete'),
																//'undelete' => TTi18n::gettext('Un-Delete')
															),
											'transaction' =>	array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view_own' => TTi18n::gettext('View Own'),
																'view' => TTi18n::gettext('View'),
																'add' => TTi18n::gettext('Add'),
																'edit_own' => TTi18n::gettext('Edit Own'),
																'edit' => TTi18n::gettext('Edit'),
																'delete_own' => TTi18n::gettext('Delete Own'),
																'delete' => TTi18n::gettext('Delete'),
																//'undelete' => TTi18n::gettext('Un-Delete')
															),
											'invoice' => array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view_own' => TTi18n::gettext('View Own'),
																'view' => TTi18n::gettext('View'),
																'add' => TTi18n::gettext('Add'),
																'edit_own' => TTi18n::gettext('Edit Own'),
																'edit' => TTi18n::gettext('Edit'),
																'delete_own' => TTi18n::gettext('Delete Own'),
																'delete' => TTi18n::gettext('Delete'),
																//'undelete' => TTi18n::gettext('Un-Delete')
															),
											'invoice_report' =>	array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view_transaction_summary' => TTi18n::gettext('View Transaction Summary'),
															),
											'permission' =>	array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view_own' => TTi18n::gettext('View Own'),
																'view_child' => TTi18n::gettext('View Subordinate'),
																'view' => TTi18n::gettext('View'),
																'add' => TTi18n::gettext('Add'),
																'edit_own' => TTi18n::gettext('Edit Own'),
																'edit_child' => TTi18n::gettext('Edit Subordinate'),
																'edit' => TTi18n::gettext('Edit'),
																'delete_own' => TTi18n::gettext('Delete Own'),
																'delete_child' => TTi18n::gettext('Delete Subordinate'),
																'delete' => TTi18n::gettext('Delete'),
																//'undelete' => TTi18n::gettext('Un-Delete')
															),
											'qualification' => array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view_own' => TTi18n::gettext('View Own'),
																'view_child' => TTi18n::gettext('View Subordinate'),
																'view' => TTi18n::gettext('View'),
																'add' => TTi18n::gettext('Add'),
																'edit_own' => TTi18n::gettext('Edit Own'),
																'edit_child' => TTi18n::gettext('Edit Subordinate'),
																'edit' => TTi18n::gettext('Edit'),
																'delete_own' => TTi18n::gettext('Delete Own'),
																'delete_child' => TTi18n::gettext('Delete Subordinate'),
																'delete' => TTi18n::gettext('Delete'),
																//'undelete' => TTi18n::gettext('Un-Delete')
															),
											'user_education' => array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view_own' => TTi18n::gettext('View Own'),
																'view_child' => TTi18n::gettext('View Subordinate'),
																'view' => TTi18n::gettext('View'),
																'add' => TTi18n::gettext('Add'),
																'edit_own' => TTi18n::gettext('Edit Own'),
																'edit_child' => TTi18n::gettext('Edit Subordinate'),
																'edit' => TTi18n::gettext('Edit'),
																'delete_own' => TTi18n::gettext('Delete Own'),
																'delete_child' => TTi18n::gettext('Delete Subordinate'),
																'delete' => TTi18n::gettext('Delete'),
																//'undelete' => TTi18n::gettext('Un-Delete')
															),
											'user_license'	=>	array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view_own' => TTi18n::gettext('View Own'),
																'view_child' => TTi18n::gettext('View Subordinate'),
																'view' => TTi18n::gettext('View'),
																'add' => TTi18n::gettext('Add'),
																'edit_own' => TTi18n::gettext('Edit Own'),
																'edit_child' => TTi18n::gettext('Edit Subordinate'),
																'edit' => TTi18n::gettext('Edit'),
																'delete_own' => TTi18n::gettext('Delete Own'),
																'delete_child' => TTi18n::gettext('Delete Subordinate'),
																'delete' => TTi18n::gettext('Delete'),
																//'undelete' => TTi18n::gettext('Un-Delete')
															),
											'user_skill' => array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view_own' => TTi18n::gettext('View Own'),
																'view_child' => TTi18n::gettext('View Subordinate'),
																'view' => TTi18n::gettext('View'),
																'add' => TTi18n::gettext('Add'),
																'edit_own' => TTi18n::gettext('Edit Own'),
																'edit_child' => TTi18n::gettext('Edit Subordinate'),
																'edit' => TTi18n::gettext('Edit'),
																'delete_own' => TTi18n::gettext('Delete Own'),
																'delete_child' => TTi18n::gettext('Delete Subordinate'),
																'delete' => TTi18n::gettext('Delete'),
																//'undelete' => TTi18n::gettext('Un-Delete')
															),
											'user_membership' => array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view_own' => TTi18n::gettext('View Own'),
																'view_child' => TTi18n::gettext('View Subordinate'),
																'view' => TTi18n::gettext('View'),
																'add' => TTi18n::gettext('Add'),
																'edit_own' => TTi18n::gettext('Edit Own'),
																'edit_child' => TTi18n::gettext('Edit Subordinate'),
																'edit' => TTi18n::gettext('Edit'),
																'delete_own' => TTi18n::gettext('Delete Own'),
																'delete_child' => TTi18n::gettext('Delete Subordinate'),
																'delete' => TTi18n::gettext('Delete'),
																//'undelete' => TTi18n::gettext('Un-Delete')
															),
											'user_language'	=> array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view_own' => TTi18n::gettext('View Own'),
																'view_child' => TTi18n::gettext('View Subordinate'),
																'view' => TTi18n::gettext('View'),
																'add' => TTi18n::gettext('Add'),
																'edit_own' => TTi18n::gettext('Edit Own'),
																'edit_child' => TTi18n::gettext('Edit Subordinate'),
																'edit' => TTi18n::gettext('Edit'),
																'delete_own' => TTi18n::gettext('Delete Own'),
																'delete_child' => TTi18n::gettext('Delete Subordinate'),
																'delete' => TTi18n::gettext('Delete'),
																//'undelete' => TTi18n::gettext('Un-Delete')
															),
											'kpi' => array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view_own' => TTi18n::gettext('View Own'),
																'view_child' => TTi18n::gettext('View Subordinate'),
																'view' => TTi18n::gettext('View'),
																'add' => TTi18n::gettext('Add'),
																'edit_own' => TTi18n::gettext('Edit Own'),
																'edit_child' => TTi18n::gettext('Edit Subordinate'),
																'edit' => TTi18n::gettext('Edit'),
																'delete_own' => TTi18n::gettext('Delete Own'),
																'delete_child' => TTi18n::gettext('Delete Subordinate'),
																'delete' => TTi18n::gettext('Delete'),
																//'undelete' => TTi18n::gettext('Un-Delete')
															),
											'user_review' => array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view_own' => TTi18n::gettext('View Own'),
																'view_child' => TTi18n::gettext('View Subordinate'),
																'view' => TTi18n::gettext('View'),
																'add' => TTi18n::gettext('Add'),
																'edit_own' => TTi18n::gettext('Edit Own'),
																'edit_child' => TTi18n::gettext('Edit Subordinate'),
																'edit' => TTi18n::gettext('Edit'),
																'delete_own' => TTi18n::gettext('Delete Own'),
																'delete_child' => TTi18n::gettext('Delete Subordinate'),
																'delete' => TTi18n::gettext('Delete'),
																//'undelete' => TTi18n::gettext('Un-Delete')
															),
											'job_vacancy' => array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view_own' => TTi18n::gettext('View Own'),
																//'view_child' => TTi18n::gettext('View Subordinate'),
																'view' => TTi18n::gettext('View'),
																'add' => TTi18n::gettext('Add'),
																'edit_own' => TTi18n::gettext('Edit Own'),
																//'edit_child' => TTi18n::gettext('Edit Subordinate'),
																'edit' => TTi18n::gettext('Edit'),
																'delete_own' => TTi18n::gettext('Delete Own'),
																//'delete_child' => TTi18n::gettext('Delete Subordinate'),
																'delete' => TTi18n::gettext('Delete'),
																//'undelete' => TTi18n::gettext('Un-Delete')
															),
											'job_applicant' => array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view_own' => TTi18n::gettext('View Own'),
																//'view_child' => TTi18n::gettext('View Subordinate'),
																'view' => TTi18n::gettext('View'),
																'add' => TTi18n::gettext('Add'),
																'edit_own' => TTi18n::gettext('Edit Own'),
																//'edit_child' => TTi18n::gettext('Edit Subordinate'),
																'edit' => TTi18n::gettext('Edit'),
																'delete_own' => TTi18n::gettext('Delete Own'),
																//'delete_child' => TTi18n::gettext('Delete Subordinate'),
																'delete' => TTi18n::gettext('Delete'),
																//'undelete' => TTi18n::gettext('Un-Delete')

															),
											'job_application' => array(
																'enabled' => TTi18n::gettext('Enabled'),
																'view_own' => TTi18n::gettext('View Own'),
																//'view_child' => TTi18n::gettext('View Subordinate'),
																'view' => TTi18n::gettext('View'),
																'add' => TTi18n::gettext('Add'),
																'edit_own' => TTi18n::gettext('Edit Own'),
																//'edit_child' => TTi18n::gettext('Edit Subordinate'),
																'edit' => TTi18n::gettext('Edit'),
																'delete_own' => TTi18n::gettext('Delete Own'),
																//'delete_child' => TTi18n::gettext('Delete Subordinate'),
																'delete' => TTi18n::gettext('Delete'),
																//'undelete' => TTi18n::gettext('Un-Delete')

															),

											'hr_report' => array(
																'enabled' => TTi18n::gettext('Enabled'),
																'user_qualification' => TTi18n::gettext('Employee Qualifications'),
																'user_review' => TTi18n::getText('Employee Review'),
																'user_recruitment' => TTi18n::gettext('Employee Recruitment'),
															),
											'recruitment_report' => array(
																'enabled' => TTi18n::gettext('Enabled'),
																'user_recruitment' => TTi18n::gettext('Employee Recruitment'),
															),
									);
				break;

		}

		return $retval;
	}

	/**
	 * @param string $value UUID
	 * @return bool
	 */
	function setCompany( $value ) {
		$this->company_id = $value;
		return TRUE;
	}

	/**
	 * @return null
	 */
	function getCompany() {
		if ( $this->company_id != '' ) {
			return $this->company_id;
		} else {
			$company_id = $this->getPermissionControlObject()->getCompany();

			return $company_id;
		}
	}

	/**
	 * @return bool|null
	 */
	function getPermissionControlObject() {
		if ( is_object($this->permission_control_obj) ) {
			return $this->permission_control_obj;
		} else {

			$pclf = TTnew( 'PermissionControlListFactory' ); /** @var PermissionControlListFactory $pclf */
			$pclf->getById( $this->getPermissionControl() );

			if ( $pclf->getRecordCount() == 1 ) {
				$this->permission_control_obj = $pclf->getCurrent();

				return $this->permission_control_obj;
			}

			return FALSE;
		}
	}

	/**
	 * @return bool|int|string
	 */
	function getPermissionControl() {
		return TTUUID::castUUID($this->getGenericDataValue( 'permission_control_id' ));
	}

	/**
	 * @param string $value UUID
	 * @return bool
	 */
	function setPermissionControl( $value) {
		$value = TTUUID::castUUID( $value );
		return $this->setGenericDataValue( 'permission_control_id', $value );
	}

	/**
	 * @return bool|mixed
	 */
	function getSection() {
		return $this->getGenericDataValue( 'section' );
	}

	/**
	 * @param $section
	 * @param bool $disable_error_check
	 * @return bool
	 */
	function setSection( $section, $disable_error_check = FALSE ) {
		$section = trim($section);
		return $this->setGenericDataValue( 'section', $section );
	}

	/**
	 * @return bool|mixed
	 */
	function getName() {
		return $this->getGenericDataValue( 'name' );
	}

	/**
	 * @param $name
	 * @param bool $disable_error_check
	 * @return bool
	 */
	function setName( $name, $disable_error_check = FALSE ) {
		$name = trim($name);
		return $this->setGenericDataValue( 'name', $name );
	}

	/**
	 * @return bool
	 */
	function getValue() {
		$value = $this->getGenericDataValue('value');
		if ( $value !== FALSE AND $value == 1 ) {
			return TRUE;
		} else {
			return FALSE;
		}
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setValue( $value ) {
		$value = (int)$value;

		//Debug::Arr($value, 'Value: ', __FILE__, __LINE__, __METHOD__, 10);
		return $this->setGenericDataValue( 'value', $value );
	}

	/**
	 * @param $preset
	 * @param bool $filter_sections
	 * @param bool $filter_permissions
	 * @return bool
	 */
	function filterPresetPermissions( $preset, $filter_sections = FALSE, $filter_permissions = FALSE ) {
		//Debug::Arr( array($filter_sections, $filter_permissions), 'Preset: '. $preset, __FILE__, __LINE__, __METHOD__, 10);
		if ( $preset == 0 ) {
			$preset = 40; //Administrator.
		}

		$filter_sections = Misc::trimSortPrefix( $filter_sections, TRUE );
		if ( !is_array( $filter_sections ) ) {
			$filter_sections = FALSE;
		}

		//Always add enabled, system to the filter_permissions.
		$filter_permissions[] = 'enabled';
		$filter_permissions[] = 'login';
		$filter_permissions = Misc::trimSortPrefix( $filter_permissions, TRUE );
		if ( !is_array( $filter_permissions ) ) {
			$filter_permissions = FALSE;
		}

		//Get presets based on all flags.
		$preset_permissions = $this->getPresetPermissions( $preset, array_keys( $this->getOptions('preset_flags') ) );
		//Debug::Arr($preset_permissions, 'Preset Permissions: ', __FILE__, __LINE__, __METHOD__, 10);

		if ( is_array($preset_permissions) ) {
			foreach($preset_permissions as $section => $permissions) {
				if ( $filter_sections === FALSE OR in_array( $section, $filter_sections ) ) {
					foreach($permissions as $name => $value) {
						//Other permission basically matches anything that is not in filter list. Things like edit_own_password, etc...
						if ( $filter_permissions === FALSE OR in_array( $name, $filter_permissions ) OR ( in_array( 'other', $filter_permissions ) AND !in_array( $name, $filter_permissions ) ) ) {
							//Debug::Text('aSetting Permission - Section: '. $section .' Name: '. $name .' Value: '. (int)$value, __FILE__, __LINE__, __METHOD__, 10);
							$retarr[$section][$name] = $value;
						} //else { //Debug::Text('bNOT Setting Permission - Section: '. $section .' Name: '. $name .' Value: '. (int)$value, __FILE__, __LINE__, __METHOD__, 10);
					}
				}
			}
		}

		if ( isset($retarr) ) {
			Debug::Arr($retarr, 'Filtered Permissions', __FILE__, __LINE__, __METHOD__, 10);
			return $retarr;
		}

		return FALSE;
	}

	/**
	 * @param $preset
	 * @param array $preset_flags
	 * @param bool $force_system_presets
	 * @return array|bool
	 */
	function getPresetPermissions( $preset, $preset_flags = array(), $force_system_presets = TRUE ) {
		$key = Option::getByValue($preset, $this->getOptions('preset') );
		if ($key !== FALSE) {
			$preset = $key;
		}

		//Always add system presets when using the Permission wizard, so employees can login and such.
		//However when upgrading this causes a problem as it resets custom permission groups.
		if ( $force_system_presets == TRUE ) {
			$preset_flags[] = 0;
		}
		asort($preset_flags);

		Debug::Text('Preset: '. $preset, __FILE__, __LINE__, __METHOD__, 10);
		Debug::Arr($preset_flags, 'Preset Flags... ', __FILE__, __LINE__, __METHOD__, 10);

		if ( !isset($preset) OR $preset == '' OR $preset == -1 ) {
			Debug::Text('No Preset set... Skipping!', __FILE__, __LINE__, __METHOD__, 10);
			return FALSE;
		}

		$preset_permissions = array(
									10 => //Role: Regular Employee
											array(
													0 => //Module: System
														array(
															'system' => array(
																				'login' => TRUE,
																			),
															'user' =>	array(
																				'enabled' => TRUE,
																				'view_own' => TRUE,
																				'edit_own' => TRUE,
																				'edit_own_password' => TRUE,
																				'edit_own_phone_password' => TRUE,
																			),
															'user_preference' =>	array(
																				'enabled' => TRUE,
																				'view_own' => TRUE,
																				'add' => TRUE,
																				'edit_own' => TRUE,
																				'delete_own' => TRUE,
																			),
															'request' =>	array(
																				'enabled' => TRUE,
																				'view_own' => TRUE,
																				'add' => TRUE,
																				'add_advanced' => TRUE,
																				'edit_own' => TRUE,
																				'delete_own' => TRUE,
																			),
															'message' =>	array(
																				'enabled' => TRUE,
																				'view_own' => TRUE,
																				'add' => TRUE,
																				'edit_own' => TRUE,
																				'delete_own' => TRUE,
																			),
															'help' =>	array(
																				'enabled' => TRUE,
																				'view' => TRUE,
																			),

														),
													10 => //Module: Scheduling
														array(
															'schedule' =>	array(
																				'enabled' => TRUE,
																				'view_own' => TRUE,
																				'edit_branch' => TRUE, //Allows the user to see the branch column by default.
																				'edit_department' => TRUE, //Allows the user to see the department column by default.
																			),
															'accrual' =>	array(
																				'enabled' => TRUE,
																				'view_own' => TRUE
																			),
															'absence' =>	array(
																				'enabled' => TRUE,
																				'view_own' => TRUE,
																			),
														),
													20 => //Module: Time & Attendance
														array(
															'punch' =>	array(
																				'enabled' => TRUE,
																				'view_own' => TRUE,
																				'add' => TRUE,
																				'verify_time_sheet' => TRUE,
																				'punch_in_out' => TRUE,
																				'edit_transfer' => TRUE,
																				'edit_branch' => TRUE,
																				'edit_department' => TRUE,
																				'edit_note' => TRUE,
																				'edit_other_id1' => TRUE,
																				'edit_other_id2' => TRUE,
																				'edit_other_id3' => TRUE,
																				'edit_other_id4' => TRUE,
																				'edit_other_id5' => TRUE,
																				'punch_timesheet' => TRUE,
																			),
															'accrual' =>	array(
																				'enabled' => TRUE,
																				'view_own' => TRUE
																			),
															'absence' =>	array(
																				'enabled' => TRUE,
																				'view_own' => TRUE,
																			),

														),
													30 => //Module: Payroll
														array(
															'user' =>	array(
																				'enabled' => TRUE,
																				//'edit_own_bank' => TRUE,
																			),
															'pay_stub' =>	array(
																				'enabled' => TRUE,
																				'view_own' => TRUE,
																			),
															'government_document' => array(
																				'enabled' => TRUE,
																				'view_own' => TRUE,
																			),
															'remittance_destination_account' => array(
																	'enabled' => TRUE,
																	'view_own' => TRUE,
																	'add' => TRUE,
																	'edit_own' => TRUE,
																	'delete_own' => TRUE,
															),
														),
													40 => //Module: Job Costing
														array(
															'schedule' => array(
																				'edit_job' => TRUE,
																				'edit_job_item' => TRUE,
																			),
															'punch' =>	array(
																				'edit_job' => TRUE,
																				'edit_job_item' => TRUE,
																				'edit_quantity' => TRUE,
																				'edit_bad_quantity' => TRUE,
																			),
															'job' =>	array(
																				'enabled' => TRUE,
																			),
														),
													50 => //Module: Document Management
														array(
															'document' =>	array(
																				'enabled' => TRUE,
																				'view' => TRUE,
																			),
														),
													60 => //Module: Invoicing
														array(
														),
													70 => //Module: Human Resources
														array(
														),
													75 => //Module: Recruitement
														array(
														),
													80 => //Module: Expenses
														array(
															'user_expense' =>	array(
																				'enabled' => TRUE,
																				'view_own' => TRUE,
																				'add' => TRUE,
																				'edit_own' => TRUE, //Allow editing expenses once they are submitted, but not once authorized/declined. This is required to add though.
																				'delete_own' => TRUE,
																			),
														),
											),
									12 => //Role: Regular Employee (Manual Punch)
											array(
													20 => //Module: Time & Attendance
														array(
															'punch' =>	array(
																				'edit_own' => TRUE,
																				'delete_own' => TRUE,
																			),
															'absence' =>	array(
																				'add' => TRUE,
																				'edit_own' => TRUE,
																				'delete_own' => TRUE,
																			),
														),
												),
									14 => //Role: Regular Employee (Manual TimeSheet)
											array(
													20 => //Module: Time & Attendance
															array(
																	'punch' =>	array(
																			'manual_timesheet' => TRUE,
																	),
															),
											),
									18 => //Role: Supervisor (Subordinates Only)
											array(
													0 => //Module: System
														array(
															'user' =>	array(
																				'add' => TRUE, //Can only add user with permissions level equal or lower.
																				'view_child' => TRUE,
																				'edit_child' => TRUE,
																				'edit_advanced' => TRUE,
																				'enroll_child' => TRUE,
																				//'delete_child' => TRUE, //Disable deleting of users by default as a precautionary measure.
																				'edit_pay_period_schedule' => TRUE,
																				'edit_permission_group' => TRUE,
																				'edit_policy_group' => TRUE,
																				'edit_hierarchy' => TRUE,
																			),
															'user_preference' =>	array(
																				'view_child' => TRUE,
																				'edit_child' => TRUE,
																			),
															'request' =>	array(
																				'view_child' => TRUE,
																				'edit_child' => TRUE,
																				'delete_child' => TRUE,
																				'authorize' => TRUE
																			),
															'authorization' =>	array(
																				'enabled' => TRUE,
																				'view' => TRUE
																			),
															'message' =>	array(
																				'add_advanced' => TRUE,
																				'send_to_child' => TRUE,
																			),
															'report' =>	array(
																				'enabled' => TRUE,
																				'view_user_information' => TRUE,
																				//'view_user_detail' => TRUE,
																				'view_user_barcode' => TRUE,
																			),
															'report_custom_column' =>	array(
																				'enabled'	=> TRUE,
																				'view_own' => TRUE,
																				'add' => TRUE,
																				'edit_own' => TRUE,
																				'delete_own' => TRUE,
																			),
														),
													10 => //Module: Scheduling
														array(
															'schedule' =>	array(
																				'add' => TRUE,
																				'view_child' => TRUE,
																				'view_open' => TRUE,
																				'edit_child' => TRUE,
																				'delete_child' => TRUE,
																				'edit_branch' => TRUE,
																				'edit_department' => TRUE,
																			),
															'recurring_schedule_template' =>	array(
																				'enabled' => TRUE,
																				'view_own' => TRUE,
																				'add' => TRUE,
																				'edit_own' => TRUE,
																				'delete_own' => TRUE,
																			),
															'recurring_schedule' =>	array(
																				'enabled' => TRUE,
																				'view_child' => TRUE,
																				'add' => TRUE,
																				'edit_child' => TRUE,
																				'delete_child' => TRUE,
																			),
															'absence' =>	array(
																				'add' => TRUE,
																				'view_child' => TRUE,
																				'edit_child' => TRUE,
																				'delete_child' => TRUE,
																				'edit_branch' => TRUE,
																				'edit_department' => TRUE,
																			),
															'accrual' =>	array(
																				'add' => TRUE,
																				'view_child' => TRUE,
																				'edit_own' => FALSE,
																				'edit_child' => TRUE,
																				'delete_own' => FALSE,
																				'delete_child' => TRUE,
																			),
															'report' =>	array(
																				'view_schedule_summary' => TRUE,
																				'view_accrual_balance_summary' => TRUE,
																			),

														),
													20 => //Module: Time & Attendance
														array(
															'punch' =>	array(
																				'view_child' => TRUE,
																				'edit_child' => TRUE,
																				'delete_child' => TRUE,
																				'authorize' => TRUE
																			),
															'absence' =>	array(
																				'add' => TRUE,
																				'view_child' => TRUE,
																				'edit_own' => FALSE,
																				'edit_child' => TRUE,
																				'edit_branch' => TRUE,
																				'edit_department' => TRUE,
																				'delete_own' => FALSE,
																				'delete_child' => TRUE,
																			),
															'accrual' =>	array(
																				'view_child' => TRUE,
																				'add' => TRUE,
																				'edit_child' => TRUE,
																				'delete_child' => TRUE,
																			),
															'report' =>	array(
																				'view_active_shift' => TRUE,
																				'view_timesheet_summary' => TRUE,
																				'view_punch_summary' => TRUE,
																				'view_exception_summary' => TRUE,
																				'view_accrual_balance_summary' => TRUE,
																			),

														),
													30 => //Module: Payroll
														array(
														),
													40 => //Module: Job Costing
														array(
															'schedule' =>	array(
																				'edit_job' => TRUE,
																				'edit_job_item' => TRUE,
																			),
															'absence' =>	array(
																				'edit_job' => TRUE,
																				'edit_job_item' => TRUE,
																			),
															'job' =>	array(
																				'add' => TRUE,
																				'view' => TRUE, //Must be able to view all jobs so they can punch in/out.
																				'view_own' => TRUE,
																				'edit_own' => TRUE,
																				'delete_own' => TRUE,
																			),
															'job_item' =>	array(
																				'enabled' => TRUE,
																				'view' => TRUE,
																				'add' => TRUE,
																				'edit_own' => TRUE,
																				'delete_own' => TRUE,
																			),
															'job_report' => array(
																				'enabled' => TRUE,
																				'view_job_summary' => TRUE,
																				'view_job_analysis' => TRUE,
																				'view_job_payroll_analysis' => TRUE,
																				'view_job_barcode' => TRUE
																			),
															'geo_fence' =>	array(
																				'enabled' => TRUE,
																				'add' => TRUE,
																				'view' => TRUE, //Must be able to view all fences so they can punch in/out.
																				'view_own' => TRUE,
																				'edit_own' => TRUE,
																				'delete_own' => TRUE,
																			),
														),
													50 => //Module: Document Management
														array(
															'document' =>	array(
																				'add' => TRUE,
																				'view_private' => TRUE,
																				'edit' => TRUE,
																				'edit_private' => TRUE,
																				'delete' => TRUE,
																				'delete_private' => TRUE,
																			),

														),
													60 => //Module: Invoicing
														array(
															'client' =>	array(
																				'enabled' => TRUE,
																				'view' => TRUE,
																				'add' => TRUE,
																				'edit' => TRUE,
																				'delete' => TRUE,
																			),
															'client_payment' =>	array(
																				'enabled' => TRUE,
																				'view' => TRUE,
																				'add' => TRUE,
																				'edit' => TRUE,
																				'delete' => TRUE,
																			),
															'transaction' => array(
																				'enabled' => TRUE,
																				'view' => TRUE,
																				'add' => TRUE,
																				'edit' => TRUE,
																				'delete' => TRUE,
																			),
															'invoice' => array(
																				'enabled' => TRUE,
																				'view' => TRUE,
																				'add' => TRUE,
																				'edit' => TRUE,
																				'delete' => TRUE,
																			),
														),
													70 => //Module: Human Resources
														array(
															'user_contact' => array(
																				'enabled' => TRUE,
																				'add' => TRUE, //Can only add user with permissions level equal or lower.
																				'view_child' => TRUE,
																				'edit_child' => TRUE,
																				'delete_child' => TRUE,
																			),
															'qualification' => array(
																				'enabled' => TRUE,
																				'add' => TRUE,
																				'view' => TRUE,
																				'edit_own' => TRUE,
																				'delete_own' => TRUE,
																				'view_child' => TRUE,
																				'edit_child' => TRUE,
																				'delete_child' => TRUE,
																			),
															'user_education' =>	array(
																				'enabled' => TRUE,
																				'add' => TRUE,
																				'view' => TRUE,
																				'edit_own' => TRUE,
																				'delete_own' => TRUE,
																				'view_child' => TRUE,
																				'edit_child' => TRUE,
																				'delete_child' => TRUE,
																			),
															'user_license' => array(
																				'enabled' => TRUE,
																				'add' => TRUE,
																				'view' => TRUE,
																				'edit_own' => TRUE,
																				'delete_own' => TRUE,
																				'view_child' => TRUE,
																				'edit_child' => TRUE,
																				'delete_child' => TRUE,
																			),
															'user_skill' =>	array(
																				'enabled' => TRUE,
																				'add' => TRUE,
																				'view' => TRUE,
																				'edit_own' => TRUE,
																				'delete_own' => TRUE,
																				'view_child' => TRUE,
																				'edit_child' => TRUE,
																				'delete_child' => TRUE,
																			),
															'user_membership' => array(
																				'enabled' => TRUE,
																				'add' => TRUE,
																				'view' => TRUE,
																				'edit_own' => TRUE,
																				'delete_own' => TRUE,
																				'view_child' => TRUE,
																				'edit_child' => TRUE,
																				'delete_child' => TRUE,
																			),
															'user_language' => array(
																				'enabled' => TRUE,
																				'add' => TRUE,
																				'view' => TRUE,
																				'edit_own' => TRUE,
																				'delete_own' => TRUE,
																				'view_child' => TRUE,
																				'edit_child' => TRUE,
																				'delete_child' => TRUE,
																			),
															'kpi' => array(
																				'enabled' => TRUE,
																				'add' => TRUE,
																				'view' => TRUE,
																				'edit_own' => TRUE,
																				'delete_own' => TRUE,
																				'view_child' => TRUE,
																				'edit_child' => TRUE,
																				'delete_child' => TRUE,
																			),
															'user_review' => array(
																				'enabled' => TRUE,
																				'add' => TRUE,
																				'view_own' => TRUE,
																				'view_child' => TRUE,
																				'edit_child' => TRUE,
																				'delete_child' => TRUE,
																			),
															'hr_report' =>	array(
																				'enabled' => TRUE,
																				'user_qualification' => TRUE,
																				'user_review' => TRUE,
																			),

														),
													75 => //Module: Recruitement
														array(
															'job_vacancy' => array(
																				'enabled' => TRUE,
																				'add' => TRUE,
																				'view_child' => TRUE,
																				'edit_child' => TRUE,
																				'delete_child' => TRUE,
																			),
															'job_applicant' => array(
																				'enabled' => TRUE,
																				'add' => TRUE,
																				'view_child' => TRUE,
																				'edit_child' => TRUE,
																				'delete_child' => TRUE,
																			),
															'job_application' => array(
																				'enabled' => TRUE,
																				'add' => TRUE,
																				'view_child' => TRUE,
																				'edit_child' => TRUE,
																				'delete_child' => TRUE,
																			),
															'recruitment_report' =>	array(
																				'enabled' => TRUE,
																				'user_recruitment' => TRUE,
																			),

														),
													80 => //Module: Expenses
														array(
															'user_expense' => array(
																				'view_child' => TRUE,
																				'add' => TRUE,
																				'edit_child' => TRUE,
																				'delete_child' => TRUE,
																				'authorize' => TRUE,
																			),
														),
											),
									20 => //Role: Supervisor (All Employees)
											array(
													0 => //Module: System
														array(
															'user' =>	array(
																				'view' => TRUE,
																				'edit' => TRUE,
																				'enroll' => TRUE,
																				//'delete' => TRUE, //Disable deleting of users by default as a precautionary measure.
																			),
															'user_preference' =>	array(
																				'view' => TRUE,
																				'edit' => TRUE,
																			),
															'request' =>	array(
																				'view' => TRUE,
																				'edit' => TRUE,
																				'delete' => TRUE,
																			),
															'message' =>	array(
																				'send_to_any' => TRUE,
																			),
														),
													10 => //Module: Scheduling
														array(
															'schedule' =>	array(
																				'view' => TRUE,
																				'edit' => TRUE,
																				'delete' => TRUE
																			),
															'recurring_schedule_template' =>	array(
																				'view' => TRUE,
																				'edit' => TRUE,
																				'delete' => TRUE,
																			),
															'recurring_schedule' =>	array(
																				'view' => TRUE,
																				'edit' => TRUE,
																				'delete' => TRUE,
																			),
															'absence' =>	array(
																				'view' => TRUE,
																				'edit' => TRUE,
																				'delete' => TRUE,
																			),
															'accrual' =>	array(
																				'view' => TRUE,
																				'edit' => TRUE,
																				'delete' => TRUE,
																			),

														),
													20 => //Module: Time & Attendance
														array(
															'punch' =>	array(
																				'view' => TRUE,
																				'edit' => TRUE,
																				'delete' => TRUE,
																			),
															'absence' =>	array(
																				'view' => TRUE,
																				'edit' => TRUE,
																				'delete' => TRUE,
																				'edit_own' => TRUE,
																				'delete_own' => TRUE,
																			),
															'accrual' =>	array(
																				'view' => TRUE,
																				'edit' => TRUE,
																				'delete' => TRUE,
																			),
														),
													30 => //Module: Payroll
														array(
														),
													40 => //Module: Job Costing
														array(
															'job' =>	array(
																				'view' => TRUE,
																				'edit' => TRUE,
																				'delete' => TRUE,
																			),
															'job_item' =>	array(
																				'enabled' => TRUE,
																				'view' => TRUE,
																				'add' => TRUE,
																				'edit' => TRUE,
																				'delete' => TRUE,
																			),
															'geo_fence' =>	array(
																				'view' => TRUE,
																				'edit' => TRUE,
																				'delete' => TRUE,
																			),
														),
													50 => //Module: Document Management
														array(
														),
													60 => //Module: Invoicing
														array(
														),
													70 => //Module: Human Resources
														array(
															'user_contact' => array(
																				'add' => TRUE,
																				'view' => TRUE,
																				'edit' => TRUE,
																				'delete' => TRUE,
																			),
															'qualification' => array(
																				'edit' => TRUE,
																				'delete' => TRUE
																			),
															'user_education' =>	array(
																				'edit' => TRUE,
																				'delete' => TRUE,
																			),
															'user_license' =>	array(
																				'edit' => TRUE,
																				'delete' => TRUE,
																			),
															'user_skill' =>	array(
																				'edit' => TRUE,
																				'delete' => TRUE,
																			),
															'user_membership' => array(
																				'edit' => TRUE,
																				'delete' => TRUE,
																			),
															'user_language' => array(
																				'edit' => TRUE,
																				'delete' => TRUE,
																			),
															'kpi' => array(
																				'edit' => TRUE,
																				'delete' => TRUE,
																			),
															'user_review' => array(
																				'view' => TRUE,
																				'edit' => TRUE,
																				'delete' => TRUE,
																			),
														),
													75 => //Module: Recruitement
														array(
															'job_vacancy' =>	array(
																				'view' => TRUE,
																				'edit' => TRUE,
																				'delete' => TRUE,
																			),
															'job_applicant' =>	array(
																				'view' => TRUE,
																				'edit' => TRUE,
																				'delete' => TRUE,
																			),
															'job_application' =>	array(
																				'view' => TRUE,
																				'edit' => TRUE,
																				'delete' => TRUE,
																			),
														),
													80 => //Module: Expenses
														array(
															'user_expense' =>	array(
																				'view' => TRUE,
																				'edit' => TRUE,
																				'delete' => TRUE,
																			),
														),
											),
									25 => //Role: HR Manager
											array(
													0 => //Module: System
														array(
														),
													10 => //Module: Scheduling
														array(
														),
													20 => //Module: Time & Attendance
														array(
														),
													30 => //Module: Payroll
														array(
														),
													40 => //Module: Job Costing
														array(
														),
													50 => //Module: Document Management
														array(
														),
													60 => //Module: Invoicing
														array(
														),
													70 => //Module: Human Resources
														array(
															'qualification' =>	array(
																				'enabled' => TRUE,
																				'view' => TRUE,
																				'add' => TRUE,
																				'edit' => TRUE,
																				'delete' => TRUE,
																			),
														),
													75 => //Module: Recruitement
														array(
														),
													80 => //Module: Expenses
														array(
														),
											),
									30 => //Role: Payroll Administrator
											array(
													0 => //Module: System
														array(
															'company' =>	array(
																				'enabled' => TRUE,
																				'view_own' => TRUE,
																				'edit_own' => TRUE,
																				//'edit_own_bank' => TRUE
																			),
															'legal_entity' =>	array(
																				'enabled' => TRUE,
																				'view' => TRUE,
																				'add' => TRUE,
																				'edit' => TRUE,
																				'delete' => TRUE,
															),
															'user' =>	array(
																				'add' => TRUE,
																				//'edit_bank' => TRUE,
																				'view_sin' => TRUE,
																			),
															'wage' =>	array(
																				'enabled' => TRUE,
																				'view' => TRUE,
																				'add' => TRUE,
																				'edit' => TRUE,
																				'delete' => TRUE
																			),
															'pay_period_schedule' =>	array(
																				'enabled' => TRUE,
																				'view' => TRUE,
																				'add' => TRUE,
																				'edit' => TRUE,
																				'delete' => TRUE,
																				'assign' => TRUE
																			),
															'pay_code' =>	array(
																				'enabled' => TRUE,
																				'view' => TRUE,
																				'add' => TRUE,
																				'edit' => TRUE,
																				'delete' => TRUE,
																			),
															'pay_formula_policy' =>	array(
																				'enabled' => TRUE,
																				'view' => TRUE,
																				'add' => TRUE,
																				'edit' => TRUE,
																				'delete' => TRUE,
																			),
															'report' =>	array(
																				'view_system_log' => TRUE,
																			),
														),
													10 => //Module: Scheduling
														array(
														),
													20 => //Module: Time & Attendance
														array(
														),
													30 => //Module: Payroll
														array(
															'user_tax_deduction' =>	array(
																				'enabled' => TRUE,
																				'view' => TRUE,
																				'add' => TRUE,
																				'edit' => TRUE,
																				'delete' => TRUE,
																			),
															'roe' => array(
																				'enabled' => TRUE,
																				'view' => TRUE,
																				'add' => TRUE,
																				'edit' => TRUE,
																				'delete' => TRUE,
																			),
															'company_tax_deduction' =>	array(
																				'enabled' => TRUE,
																				'view' => TRUE,
																				'add' => TRUE,
																				'edit' => TRUE,
																				'delete' => TRUE,
																			),
															'pay_stub_account' =>	array(
																				'enabled' => TRUE,
																				'view' => TRUE,
																				'add' => TRUE,
																				'edit' => TRUE,
																				'delete' => TRUE
																			),
															'payroll_remittance_agency' =>	array(
																				'enabled' => TRUE,
																				'view' => TRUE,
																				'add' => TRUE,
																				'edit' => TRUE,
																				'delete' => TRUE
																			),
															'remittance_source_account' =>	array(
																				'enabled' => TRUE,
																				'view' => TRUE,
																				'add' => TRUE,
																				'edit' => TRUE,
																				'delete' => TRUE
																			),
															'remittance_destination_account' => array(
																				//'enabled' => TRUE,
																				'view' => TRUE,
																				'add' => TRUE,
																				'edit' => TRUE,
																				'delete' => TRUE,
															),
															'pay_stub' =>	array(
																				'view' => TRUE,
																				'add' => TRUE,
																				'edit' => TRUE,
																				'delete' => TRUE
																			),
															'government_document' =>	array(
																				'view' => TRUE,
																				'add' => TRUE,
																				'edit' => TRUE,
																				'delete' => TRUE
																			),
															'pay_stub_amendment' =>	array(
																				'enabled' => TRUE,
																				'view' => TRUE,
																				'add' => TRUE,
																				'edit' => TRUE,
																				'delete' => TRUE
																			),
															'report' =>	array(
																				'view_pay_stub_summary' => TRUE,
																				'view_payroll_export' => TRUE,
																				//'view_employee_pay_stub_summary' => TRUE,
																				'view_remittance_summary' => TRUE,
																				'view_wages_payable_summary' => TRUE,
																				'view_t4_summary' => TRUE,
																				'view_generic_tax_summary' => TRUE,
																				'view_form941' => TRUE,
																				'view_form940' => TRUE,
																				'view_form940ez' => TRUE,
																				'view_form1099misc' => TRUE,
																				'view_formW2' => TRUE,
																				'view_affordable_care' => TRUE,
																				'view_general_ledger_summary' => TRUE,
																			),
														),
													40 => //Module: Job Costing
														array(
														),
													50 => //Module: Document Management
														array(
														),
													60 => //Module: Invoicing
														array(
															'product' =>	array(
																				'enabled' => TRUE,
																				'view' => TRUE,
																				'add' => TRUE,
																				'edit' => TRUE,
																				'delete' => TRUE,
																			),
															'tax_policy' =>	array(
																				'enabled' => TRUE,
																				'view' => TRUE,
																				'add' => TRUE,
																				'edit' => TRUE,
																				'delete' => TRUE,
																			),
															'shipping_policy' =>	array(
																				'enabled' => TRUE,
																				'view' => TRUE,
																				'add' => TRUE,
																				'edit' => TRUE,
																				'delete' => TRUE,
																			),
															'area_policy' =>	array(
																				'enabled' => TRUE,
																				'view' => TRUE,
																				'add' => TRUE,
																				'edit' => TRUE,
																				'delete' => TRUE,
																			),
															'payment_gateway' =>	array(
																				'enabled' => TRUE,
																				'view' => TRUE,
																				'add' => TRUE,
																				'edit' => TRUE,
																				'delete' => TRUE,
																			),
															'invoice_report' =>	array(
																				'enabled' => TRUE,
																				'view_transaction_summary' => TRUE,
																			),
														),
													70 => //Module: Human Resources
														array(
														),
													75 => //Module: Recruitement
														array(
														),
													80 => //Module: Expenses
														array(
															'report' =>	array(
																				'view_expense' => TRUE
																			),
														),
											),
									40 => //Role: Administrator
											array(
													0 => //Module: System
														array(
															'user' =>	array(
																				'timeclock_admin' => TRUE,
																			),
															'user_date_total' =>	array(
																				'enabled' => TRUE,
																				'view' => TRUE,
																				//By default allow them to view Accumulated Time, but not add/edit/delete because they likely don't understand the implications.
																				//'add' => TRUE,
																				//'edit' => TRUE,
																				//'delete' => TRUE,
																			),
															'policy_group' =>	array(
																				'enabled' => TRUE,
																				'view' => TRUE,
																				'add' => TRUE,
																				'edit' => TRUE,
																				'delete' => TRUE,
																			),
															'contributing_pay_code_policy' =>	array(
																				'enabled' => TRUE,
																				'view' => TRUE,
																				'add' => TRUE,
																				'edit' => TRUE,
																				'delete' => TRUE,
																			),
															'contributing_shift_policy' =>	array(
																				'enabled' => TRUE,
																				'view' => TRUE,
																				'add' => TRUE,
																				'edit' => TRUE,
																				'delete' => TRUE,
																			),
															'regular_time_policy' =>	array(
																				'enabled' => TRUE,
																				'view' => TRUE,
																				'add' => TRUE,
																				'edit' => TRUE,
																				'delete' => TRUE,
																			),
															'schedule_policy' =>	array(
																				'enabled' => TRUE,
																				'view' => TRUE,
																				'add' => TRUE,
																				'edit' => TRUE,
																				'delete' => TRUE,
																			),
															'meal_policy' =>	array(
																				'enabled' => TRUE,
																				'view' => TRUE,
																				'add' => TRUE,
																				'edit' => TRUE,
																				'delete' => TRUE,
																			),
															'break_policy' =>	array(
																				'enabled' => TRUE,
																				'view' => TRUE,
																				'add' => TRUE,
																				'edit' => TRUE,
																				'delete' => TRUE,
																			),
															'over_time_policy' =>	array(
																				'enabled' => TRUE,
																				'view' => TRUE,
																				'add' => TRUE,
																				'edit' => TRUE,
																				'delete' => TRUE,
																			),
															'premium_policy' =>	array(
																				'enabled' => TRUE,
																				'view' => TRUE,
																				'add' => TRUE,
																				'edit' => TRUE,
																				'delete' => TRUE,
																			),
															'accrual_policy' =>	array(
																				'enabled' => TRUE,
																				'view' => TRUE,
																				'add' => TRUE,
																				'edit' => TRUE,
																				'delete' => TRUE,
																			),
															'absence_policy' =>	array(
																				'enabled' => TRUE,
																				'view' => TRUE,
																				'add' => TRUE,
																				'edit' => TRUE,
																				'delete' => TRUE,
																			),
															'round_policy' => array(
																				'enabled' => TRUE,
																				'view' => TRUE,
																				'add' => TRUE,
																				'edit' => TRUE,
																				'delete' => TRUE,
																			),
															'exception_policy' =>	array(
																				'enabled' => TRUE,
																				'view' => TRUE,
																				'add' => TRUE,
																				'edit' => TRUE,
																				'delete' => TRUE,
																			),
															'holiday_policy' =>	array(
																				'enabled' => TRUE,
																				'view' => TRUE,
																				'add' => TRUE,
																				'edit' => TRUE,
																				'delete' => TRUE,
																			),
															'currency' =>	array(
																				'enabled' => TRUE,
																				'view' => TRUE,
																				'add' => TRUE,
																				'edit' => TRUE,
																				'delete' => TRUE,
																			),
															'branch' =>	array(
																				'enabled' => TRUE,
																				'view' => TRUE,
																				'add' => TRUE,
																				'edit' => TRUE,
																				'delete' => TRUE,
																			),
															'department' =>	array(
																				'enabled' => TRUE,
																				'view' => TRUE,
																				'add' => TRUE,
																				'edit' => TRUE,
																				'delete' => TRUE,
																				'assign' => TRUE
																			),
															'station' =>	array(
																				'enabled' => TRUE,
																				'view' => TRUE,
																				'add' => TRUE,
																				'edit' => TRUE,
																				'delete' => TRUE,
																				'assign' => TRUE
																			),
															'report' =>	array(
																				//'view_shift_actual_time' => TRUE,
																			),
															'hierarchy' =>	array(
																				'enabled' => TRUE,
																				'view' => TRUE,
																				'add' => TRUE,
																				'edit' => TRUE,
																				'delete' => TRUE,
																			),
															'other_field' =>	array(
																				'enabled' => TRUE,
																				'view' => TRUE,
																				'add' => TRUE,
																				'edit' => TRUE,
																				'delete' => TRUE,
																			),
															'permission' =>	array(
																				'enabled' => TRUE,
																				'view' => TRUE,
																				'add' => TRUE,
																				'edit' => TRUE,
																				'delete' => TRUE,
																			),
															'report_custom_column' => array(
																				'view' => TRUE,
																				'edit' => TRUE,
																				'delete' => TRUE,
																			),
														),
													10 => //Module: Scheduling
														array(
														),
													20 => //Module: Time & Attendance
														array(
														),
													30 => //Module: Payroll
														array(
														),
													40 => //Module: Job Costing
														array(
														),
													50 => //Module: Document Management
														array(
														),
													60 => //Module: Invoicing
														array(
															'invoice_config' =>	array(
																				'enabled' => TRUE,
																				'add' => TRUE,
																				'edit' => TRUE,
																				'delete' => TRUE,
																			),

														),
													70 => //Module: Human Resources
														array(
														),
													75 => //Module: Recruitement
														array(
														),
													80 => //Module: Expenses
														array(
															'expense_policy' => array(
																				'enabled' => TRUE,
																				'view' => TRUE,
																				'add' => TRUE,
																				'edit' => TRUE,
																				'delete' => TRUE,
																			),
														),
											),
									);

		$retarr = array();

		//Loop over each preset adding the permissions together for that preset and the role that is selected.
		$preset_options = array_keys( Misc::trimSortPrefix( $this->getOptions('preset') ) );
		if ( is_array($preset_options) ) {
			foreach( $preset_options as $preset_option ) {
				if ( isset($preset_permissions[$preset_option]) AND $preset_option <= $preset ) {
					foreach( $preset_flags as $preset_flag ) {
						if ( isset($preset_permissions[$preset_option][$preset_flag]) ) {
							Debug::Text('Applying Preset: '. $preset_option .' Preset Flag: '. $preset_flag, __FILE__, __LINE__, __METHOD__, 10);
							$retarr = Misc::arrayMergeRecursive( $retarr, $preset_permissions[$preset_option][$preset_flag] );
						}
					}
				}
			}
		}

		return $retarr;
	}

	/**
	 * This is used by CompanyFactory to create the initial permissions when creating a new company.
	 * Also by the Quick Start wizard.
	 * @param string $permission_control_id UUID
	 * @param $preset
	 * @param $preset_flags
	 * @return bool
	 */
	function applyPreset( $permission_control_id, $preset, $preset_flags) {
		$preset_permissions = $this->getPresetPermissions( $preset, $preset_flags );

		if ( !is_array($preset_permissions) ) {
			return FALSE;
		}

		$this->setPermissionControl( $permission_control_id );

		$product_edition = $this->getPermissionControlObject()->getCompanyObject()->getProductEdition();
		//Debug::Arr($preset_flags, 'Preset: '. $preset .' Product Edition: '. $product_edition, __FILE__, __LINE__, __METHOD__, 10);

		$pf = TTnew( 'PermissionFactory' ); /** @var PermissionFactory $pf */
		$pf->StartTransaction();

		//Delete all previous permissions for this control record..
		$this->deletePermissions( $this->getCompany(), $permission_control_id );

		$created_date = time();
		foreach($preset_permissions as $section => $permissions) {
			foreach($permissions as $name => $value) {
				if ( $pf->isIgnore( $section, $name, $product_edition ) == FALSE ) {
					//Put all inserts into a single query, this speeds things up greatly (9s to less than .5s),
					//but we are by-passing the audit log so make sure we add a new entry describing what took place.
					$ph[] = $pf->getNextInsertId(); //This needs work before UUID and after.
					$ph[] = $permission_control_id;
					$ph[] = $section;
					$ph[] = $name;
					$ph[] = (int)$value;
					$ph[] = $created_date;
					$data[] = '(?, ?, ?, ?, ?, ?)';

					/*
					//Debug::Text('Setting Permission - Section: '. $section .' Name: '. $name .' Value: '. (int)$value, __FILE__, __LINE__, __METHOD__, 10);
					$pf->setPermissionControl( $permission_control_id );
					$pf->setSection( $section );
					$pf->setName( $name );
					$pf->setValue( (int)$value );
					if ( $pf->isValid() ) {
						$pf->save();
					} else {
						Debug::Text('ERROR: Setting Permission - Section: '. $section .' Name: '. $name .' Value: '. (int)$value, __FILE__, __LINE__, __METHOD__, 10);
					}
					*/
				}
			}
		}

		$pclf = TTnew( 'PermissionControlListFactory' ); /** @var PermissionControlListFactory $pclf */
		if ( isset($data) ) {
			//Save data in a single SQL query.
			$query = 'INSERT INTO '. $this->getTable() .'(ID, PERMISSION_CONTROL_ID, SECTION, NAME, VALUE, CREATED_DATE) VALUES'. implode(',', $data );
			//Debug::Text('Query: '. $query, __FILE__, __LINE__, __METHOD__, 10);
			$this->ExecuteSQL($query, $ph);

			//Make sure we keep the seqenence in sync, only required for MySQL.
			if ( $this->getDatabaseType() == 'mysql' ) {
				Debug::Text('Keeping MySQL sequence in sync...', __FILE__, __LINE__, __METHOD__, 10);
				$install = TTNew('Install'); /** @var Install $install */
				$install->initializeSequence( $this, $this->getTable(), get_class( $this ), $this->db );
				unset($install);
			}

			//Debug::Text('Logged detail records in: '. (microtime(TRUE) - $start_time), __FILE__, __LINE__, __METHOD__, 10);
			TTLog::addEntry( $permission_control_id, 20, TTi18n::getText('Applying Permission Preset').': '. Option::getByKey( $preset, $this->getOptions('preset') ), NULL, $pclf->getTable(), $this );
		}
		unset($ph, $data, $created_date, $preset_permissions, $permissions, $section, $name, $value );

		//Clear cache for all users assigned to this permission_control_id
		$pclf->getById( $permission_control_id );
		if ( $pclf->getRecordCount() > 0 ) {
			$pc_obj = $pclf->getCurrent();

			if ( is_array($pc_obj->getUser() ) ) {
				foreach( $pc_obj->getUser() as $user_id ) {
					$pf->clearCache( $user_id, $this->getCompany() );
				}
			}
		}
		unset($pclf, $pc_obj, $user_id);

		//$pf->FailTransaction();
		$pf->CommitTransaction();

		return TRUE;
	}

	/**
	 * @param string $company_id UUID
	 * @param string $permission_control_id UUID
	 * @return bool
	 */
	function deletePermissions( $company_id, $permission_control_id ) {
		if ( $company_id == '' ) {
			return FALSE;
		}

		if ( $permission_control_id == '' ) {
			return FALSE;
		}

		$plf = TTnew( 'PermissionListFactory' ); /** @var PermissionListFactory $plf */
		$plf->getByCompanyIDAndPermissionControlId( $company_id, $permission_control_id );
		foreach($plf as $permission_obj) {
			$permission_obj->Delete();
			$this->removeCache( $this->getCacheID() );
		}

		return TRUE;
	}

	/**
	 * @param $section
	 * @param null $name
	 * @param int $product_edition
	 * @return bool
	 */
	static function isIgnore( $section, $name = NULL, $product_edition = 10 ) {
		global $current_company;

		//Ignore by default
		if ( $section == '' ) {
			return TRUE;
		}

		//Debug::Text(' Product Edition: '. $product_edition .' Primary Company ID: '. PRIMARY_COMPANY_ID, __FILE__, __LINE__, __METHOD__, 10);
		if ( $product_edition == TT_PRODUCT_ENTERPRISE ) { //Enterprise
			//Company ignore permissions must be enabled always, and unset below if this is the primary company
			$ignore_permissions = array('help' => 'ALL',
										'company' => array('add', 'delete', 'delete_own', 'undelete', 'view', 'edit', 'login_other_user'),
										);
		} elseif ( $product_edition == TT_PRODUCT_CORPORATE ) { //Corporate
			//Company ignore permissions must be enabled always, and unset below if this is the primary company
			$ignore_permissions = array('help' => 'ALL',
										'company' => array('add', 'delete', 'delete_own', 'undelete', 'view', 'edit', 'login_other_user'),
										'job_vacancy' => 'ALL',
										'job_applicant' => 'ALL',
										'job_application' => 'ALL',
										'user_expense' => 'ALL',
										'expense_policy' => 'ALL',
										'report' => array('view_expense'),
										'recruitment_report' => 'ALL',
										);
		} elseif ( $product_edition == TT_PRODUCT_PROFESSIONAL ) { //Professional
			$ignore_permissions = array('help' => 'ALL',
										'company' => array('add', 'delete', 'delete_own', 'undelete', 'view', 'edit', 'login_other_user'),
										'schedule' => array('edit_job', 'edit_job_item'),
										'punch' => array('edit_job', 'edit_job_item', 'edit_quantity', 'edit_bad_quantity'),
										'absence' => array('edit_job', 'edit_job_item'),
										'job_item' => 'ALL',
										'invoice_config' => 'ALL',
										'client' => 'ALL',
										'client_payment' => 'ALL',
										'product' => 'ALL',
										'tax_policy' => 'ALL',
										'area_policy' => 'ALL',
										'shipping_policy' => 'ALL',
										'payment_gateway' => 'ALL',
										'transaction' => 'ALL',
										'job_report' => 'ALL',
										'invoice_report' => 'ALL',
										'invoice' => 'ALL',
										'geo_fence' => 'ALL',
										'job' => 'ALL',
										'document' => 'ALL',
										'job_vacancy' => 'ALL',
										'job_applicant' => 'ALL',
										'job_application' => 'ALL',
										'user_expense' => 'ALL',
										'expense_policy' => 'ALL',
										'report' => array('view_expense'),
										'recruitment_report' => 'ALL',
										);
		} elseif ( $product_edition == TT_PRODUCT_COMMUNITY ) { //Community
			//Company ignore permissions must be enabled always, and unset below if this is the primary company
			$ignore_permissions = array('help' => 'ALL',
										'company' => array('add', 'delete', 'delete_own', 'undelete', 'view', 'edit', 'login_other_user'),
										'schedule' => array('edit_job', 'edit_job_item'),
										'punch' => array('manual_timesheet', 'edit_job', 'edit_job_item', 'edit_quantity', 'edit_bad_quantity'),
										'user_date_total' => 'ALL',
										'absence' => array('edit_job', 'edit_job_item'),
										'job_item' => 'ALL',
										'invoice_config' => 'ALL',
										'client' => 'ALL',
										'client_payment' => 'ALL',
										'product' => 'ALL',
										'tax_policy' => 'ALL',
										'area_policy' => 'ALL',
										'shipping_policy' => 'ALL',
										'payment_gateway' => 'ALL',
										'transaction' => 'ALL',
										'job_report' => 'ALL',
										'invoice_report' => 'ALL',
										'invoice' => 'ALL',
										'geo_fence' => 'ALL',
										'job' => 'ALL',
										'document' => 'ALL',
										'government_document' => 'ALL',
										'job_vacancy' => 'ALL',
										'job_applicant' => 'ALL',
										'job_application' => 'ALL',
										'user_expense' => 'ALL',
										'expense_policy' => 'ALL',
										'report' => array('view_expense'),
										'recruitment_report' => 'ALL',
										);
		}

		//If they are currently logged in as the primary company ID, allow multiple company permissions.
		if ( isset($current_company) AND $current_company->getProductEdition() > TT_PRODUCT_COMMUNITY AND $current_company->getId() == PRIMARY_COMPANY_ID ) {
			unset($ignore_permissions['company']);
		}

		if ( isset($ignore_permissions[$section])
				AND
					(
						(
							$name != ''
							AND
							($ignore_permissions[$section] == 'ALL'
							OR ( is_array($ignore_permissions[$section]) AND in_array($name, $ignore_permissions[$section]) ) )
						)
						OR
						(
							$name == ''
							AND
							$ignore_permissions[$section] == 'ALL'
						)
					)

					) {
			//Debug::Text(' IGNORING... Section: '. $section .' Name: '. $name, __FILE__, __LINE__, __METHOD__, 10);
			return TRUE;
		} else {
			//Debug::Text(' NOT IGNORING... Section: '. $section .' Name: '. $name, __FILE__, __LINE__, __METHOD__, 10);
			return FALSE;
		}
	}

	/**
	 * @return bool
	 */
	function preSave() {
		//Just update any existing permissions. It would probably be faster to delete them all and re-insert though.
		$plf = TTnew( 'PermissionListFactory' ); /** @var PermissionListFactory $plf */
		$obj = $plf->getByCompanyIdAndPermissionControlIdAndSectionAndName( $this->getCompany(), $this->getPermissionControl(), $this->getSection(), $this->getName() )->getCurrent();
		$this->setId( $obj->getId() );

		return TRUE;
	}

	/**
	 * @return string
	 */
	function getCacheID() {
		$cache_id = 'permission_query_'.$this->getSection().$this->getName().$this->getPermissionControl().$this->getCompany();

		return $cache_id;
	}

	/**
	 * @param string $user_id UUID
	 * @param string $company_id UUID
	 * @return bool
	 */
	function clearCache( $user_id, $company_id ) {
		Debug::Text(' Clearing Cache for User ID: '. $user_id, __FILE__, __LINE__, __METHOD__, 10);

		$cache_id = 'permission_level'.$user_id.$company_id;
		$retval = $this->removeCache( $cache_id );

		$cache_id = 'permission_all'.$user_id.$company_id;
		$retval = $this->removeCache( $cache_id );

		return $retval;
	}

	/**
	 * @return bool
	 */
	function getEnableSectionAndNameValidation() {
		if ( isset($this->enable_section_and_name_validation) ) {
			return $this->enable_section_and_name_validation;
		}

		return TRUE; //Default to TRUE
	}

	/**
	 * @param $bool
	 * @return bool
	 */
	function setEnableSectionAndNameValidation( $bool) {
		$this->enable_section_and_name_validation = $bool;

		return TRUE;
	}


	/**
	 * @return bool
	 */
	function Validate() {
		//
		// BELOW: Validation code moved from set*() functions.
		//
		// Permission Group
		if ( $this->getPermissionControl() == TTUUID::getZeroID() ) {
			$pclf = TTnew( 'PermissionControlListFactory' ); /** @var PermissionControlListFactory $pclf */
			$this->Validator->isResultSetWithRows(	'permission_control',
															$pclf->getByID($this->getPermissionControl()),
															TTi18n::gettext('Permission Group is invalid')
														);
		}

		if ( $this->getEnableSectionAndNameValidation() == TRUE ) {
			// Section
			if ( $this->getGenericTempDataValue( 'section' ) !== FALSE ) {
				$this->Validator->inArrayKey( 'section',
											  $this->getGenericTempDataValue( 'section' ),
											  TTi18n::gettext( 'Incorrect section' ),
											  $this->getOptions( 'section' )
				);
			}
			// Permission Name
			if ( $this->getGenericTempDataValue( 'name' ) !== FALSE ) {
				$this->Validator->inArrayKey( 'name',
											  $this->getGenericTempDataValue( 'name' ),
											  TTi18n::gettext( 'Incorrect permission name' ),
											  $this->getOptions( 'name', $this->getSection() )
				);
			}
		}

		//
		// ABOVE: Validation code moved from set*() functions.
		//

		return TRUE;
	}

	/**
	 * @return bool
	 */
	function postSave() {
		//$cache_id = 'permission_query_'.$this->getSection().$this->getName().$this->getUser().$this->getCompany();
		//$this->removeCache( $this->getCacheID() );

		return TRUE;
	}

	/**
	 * @param $log_action
	 * @return bool
	 */
	function addLog( $log_action ) {
		if ( $this->getValue() == TRUE ) {
			$value_display = TTi18n::getText( 'ALLOW' );
		} else {
			$value_display = TTi18n::getText( 'DENY' );
		}

		return TTLog::addEntry( $this->getPermissionControl(), $log_action, TTi18n::getText('Section').': '. Option::getByKey($this->getSection(), $this->getOptions('section') ) .' Name: '. Option::getByKey( $this->getName(), $this->getOptions('name', $this->getSection() ) ) .' Value: '. $value_display, NULL, $this->getTable() );
	}
}
?>
