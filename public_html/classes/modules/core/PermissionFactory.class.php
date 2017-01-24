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
 * @package Core
 */
class PermissionFactory extends Factory
{
    protected $table = 'permission';
    protected $pk_sequence_name = 'permission_id_seq'; //PK Sequence name

    protected $permission_control_obj = null;
    protected $company_id = null;

    public static function isIgnore($section, $name = null)
    {
        global $current_company;

        //Ignore by default
        if ($section == '') {
            return true;
        }

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
            'job_vacancy' => 'ALL',
            'job_applicant' => 'ALL',
            'job_application' => 'ALL',
            'recruitment_report' => 'ALL',
        );

        //If they are currently logged in as the primary company ID, allow multiple company permissions.
        if ($current_company->getId() == PRIMARY_COMPANY_ID) {
            unset($ignore_permissions['company']);
        }

        if (isset($ignore_permissions[$section])
            and
            (
                (
                    $name != ''
                    and
                    ($ignore_permissions[$section] == 'ALL'
                        or (is_array($ignore_permissions[$section]) and in_array($name, $ignore_permissions[$section])))
                )
                or
                (
                    $name == ''
                    and
                    $ignore_permissions[$section] == 'ALL'
                )
            )

        ) {
            //Debug::Text(' IGNORING... Section: '. $section .' Name: '. $name, __FILE__, __LINE__, __METHOD__, 10);
            return true;
        } else {
            //Debug::Text(' NOT IGNORING... Section: '. $section .' Name: '. $name, __FILE__, __LINE__, __METHOD__, 10);
            return false;
        }
    }

    public function _getFactoryOptions($name, $parent = null)
    {
        $retval = null;
        switch ($name) {
            case 'preset':
                $retval = array(
                    //-1 => TTi18n::gettext('--'),
                    10 => TTi18n::gettext('Regular Employee (Punch In/Out)'),
                    12 => TTi18n::gettext('Regular Employee (Manual Punch)'), //Can manually Add/Edit own punches/absences.
                    18 => TTi18n::gettext('Supervisor (Subordinates Only)'),
                    20 => TTi18n::gettext('Supervisor (All Employees)'),
                    25 => TTi18n::gettext('HR Manager'),
                    30 => TTi18n::gettext('Payroll Administrator'),
                    40 => TTi18n::gettext('Administrator')
                );

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

                if (defined('FAIRNESS_API') == true and FAIRNESS_API == true) {
                    $retval = Misc::addSortPrefix($retval, 1000);
                }
                break;
            case 'preset_flags':
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

                if (defined('FAIRNESS_API') == true and FAIRNESS_API == true) {
                    unset($retval[0]);
                    $retval = Misc::addSortPrefix($retval, 1000);
                    ksort($retval);
                }

                break;
            case 'section_group_map':
                $retval = array(
                    'company' => array(
                        'system',
                        'company',
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
                    'user' => array(
                        'user',
                        'user_preference',
                        'user_tax_deduction',
                        'user_contact'
                    ),
                    'schedule' => array(
                        'schedule',
                        'recurring_schedule',
                        'recurring_schedule_template',
                    ),
                    'attendance' => array(
                        'punch',
                        'user_date_total',
                        'absence',
                        'accrual',
                        'request',
                    ),
                    'job' => array(
                        'job',
                        'job_item',
                        'job_report',
                    ),
                    'invoice' => array(
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
                    'policy' => array(
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
                    'payroll' => array(
                        'pay_stub_account',
                        'pay_stub',
                        'government_document',
                        'pay_stub_amendment',
                        'wage',
                        'roe',
                        'company_tax_deduction',
                        'user_expense',
                    ),
                    'report' => array(
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

                unset($retval['recruitment'], $retval['invoice'], $retval['job'], $retval['geo_fence'], $retval['government_document']);
                unset($retval['payroll'][array_search('user_expense', $retval['payroll'])], $retval['policy'][array_search('expense_policy', $retval['policy'])]);

                break;
            case 'section':
                $retval = array(
                    'system' => TTi18n::gettext('System'),
                    'company' => TTi18n::gettext('Company'),
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
                    'company' => array(
                        'enabled' => TTi18n::gettext('Enabled'),
                        'view_own' => TTi18n::gettext('View Own'),
                        'view' => TTi18n::gettext('View'),
                        'add' => TTi18n::gettext('Add'),
                        'edit_own' => TTi18n::gettext('Edit Own'),
                        'edit' => TTi18n::gettext('Edit'),
                        'delete_own' => TTi18n::gettext('Delete Own'),
                        'delete' => TTi18n::gettext('Delete'),
                        //'undelete' => TTi18n::gettext('Un-Delete'),
                        'edit_own_bank' => TTi18n::gettext('Edit Own Banking Information'),
                        'login_other_user' => TTi18n::gettext('Login as Other Employee')
                    ),
                    'user' => array(
                        'enabled' => TTi18n::gettext('Enabled'),
                        'view_own' => TTi18n::gettext('View Own'),
                        'view_child' => TTi18n::gettext('View Subordinate'),
                        'view' => TTi18n::gettext('View'),
                        'add' => TTi18n::gettext('Add'),
                        'edit_own' => TTi18n::gettext('Edit Own'),
                        'edit_child' => TTi18n::gettext('Edit Subordinate'),
                        'edit' => TTi18n::gettext('Edit'),
                        'edit_advanced' => TTi18n::gettext('Edit Advanced'),
                        'edit_own_bank' => TTi18n::gettext('Edit Own Bank Info'),
                        'edit_child_bank' => TTi18n::gettext('Edit Subordinate Bank Info'),
                        'edit_bank' => TTi18n::gettext('Edit Bank Info'),
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
                    'user_preference' => array(
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
                    'user_tax_deduction' => array(
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
                    'roe' => array(
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
                    'company_tax_deduction' => array(
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
                    'user_expense' => array(
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
                    'pay_stub_account' => array(
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
                    'pay_stub' => array(
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
                    'pay_stub_amendment' => array(
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
                    'wage' => array(
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
                    'currency' => array(
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
                    'branch' => array(
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
                    'department' => array(
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
                    'station' => array(
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
                    'pay_period_schedule' => array(
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
                    'schedule' => array(
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
                    'other_field' => array(
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
                    'document' => array(
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
                    'accrual' => array(
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
                    'pay_code' => array(
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
                    'pay_formula_policy' => array(
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
                    'policy_group' => array(
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
                    'contributing_pay_code_policy' => array(
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
                    'contributing_shift_policy' => array(
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
                    'schedule_policy' => array(
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
                    'meal_policy' => array(
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
                    'break_policy' => array(
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
                    'absence_policy' => array(
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
                    'accrual_policy' => array(
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
                    'regular_time_policy' => array(
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
                    'over_time_policy' => array(
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
                    'premium_policy' => array(
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
                    'round_policy' => array(
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
                    'exception_policy' => array(
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
                    'holiday_policy' => array(
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
                    'expense_policy' => array(
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

                    'recurring_schedule_template' => array(
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
                    'recurring_schedule' => array(
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
                    'request' => array(
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
                    'punch' => array(
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
                    'absence' => array(
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
                    'hierarchy' => array(
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
                    'authorization' => array(
                        'enabled' => TTi18n::gettext('Enabled'),
                        'view' => TTi18n::gettext('View')
                    ),
                    'message' => array(
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
                    'help' => array(
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
                    'report' => array(
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
                        'view_exception_summary' => TTi18n::gettext('Exception Summary'),
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
                    'job' => array(
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
                    'job_item' => array(
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
                    'job_report' => array(
                        'enabled' => TTi18n::gettext('Enabled'),
                        'view_job_summary' => TTi18n::gettext('Job Summary'),
                        'view_job_analysis' => TTi18n::gettext('Job Analysis'),
                        'view_job_payroll_analysis' => TTi18n::gettext('Job Payroll Analysis'),
                        'view_job_barcode' => TTi18n::gettext('Job Barcode')
                    ),
                    'invoice_config' => array(
                        'enabled' => TTi18n::gettext('Enabled'),
                        'add' => TTi18n::gettext('Add'),
                        'edit' => TTi18n::gettext('Edit'),
                        'delete' => TTi18n::gettext('Delete'),
                        //'undelete' => TTi18n::gettext('Un-Delete')
                    ),
                    'client' => array(
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
                    'client_payment' => array(
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
                    'product' => array(
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
                    'tax_policy' => array(
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
                    'shipping_policy' => array(
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
                    'area_policy' => array(
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
                    'payment_gateway' => array(
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
                    'transaction' => array(
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
                    'invoice_report' => array(
                        'enabled' => TTi18n::gettext('Enabled'),
                        'view_transaction_summary' => TTi18n::gettext('View Transaction Summary'),
                    ),
                    'permission' => array(
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
                    'user_license' => array(
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
                    'user_language' => array(
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
                    'job_applicant' => array(
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
                    'job_application' => array(
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

    public function setCompany($id)
    {
        $this->company_id = $id;
        return true;
    }

    public function setSection($section, $disable_error_check = false)
    {
        $section = trim($section);

        if ($disable_error_check === true
            or
            $this->Validator->inArrayKey('section',
                $section,
                TTi18n::gettext('Incorrect section'),
                $this->getOptions('section'))
        ) {
            $this->data['section'] = $section;

            return true;
        }

        return false;
    }

    public function setName($name, $disable_error_check = false)
    {
        $name = trim($name);

        //Debug::Arr($this->getOptions('name', $this->getSection() ), 'Options: ', __FILE__, __LINE__, __METHOD__, 10);
        if ($disable_error_check === true
            or
            $this->Validator->inArrayKey('name',
                $name,
                TTi18n::gettext('Incorrect permission name'),
                $this->getOptions('name', $this->getSection()))
        ) {
            $this->data['name'] = $name;

            return true;
        }

        return false;
    }

    public function getSection()
    {
        if (isset($this->data['section'])) {
            return $this->data['section'];
        }

        return false;
    }

    public function setValue($value)
    {
        $value = trim($value);

        //Debug::Arr($value, 'Value: ', __FILE__, __LINE__, __METHOD__, 10);

        if ($this->Validator->isLength('value',
            $value,
            TTi18n::gettext('Value is invalid'),
            1,
            255)
        ) {
            $this->data['value'] = $value;

            return true;
        }

        return false;
    }

    public function filterPresetPermissions($preset, $filter_sections = false, $filter_permissions = false)
    {
        //Debug::Arr( array($filter_sections, $filter_permissions), 'Preset: '. $preset, __FILE__, __LINE__, __METHOD__, 10);
        if ($preset == 0) {
            $preset = 40; //Administrator.
        }

        $filter_sections = Misc::trimSortPrefix($filter_sections, true);
        if (!is_array($filter_sections)) {
            $filter_sections = false;
        }

        //Always add enabled, system to the filter_permissions.
        $filter_permissions[] = 'enabled';
        $filter_permissions[] = 'login';
        $filter_permissions = Misc::trimSortPrefix($filter_permissions, true);
        if (!is_array($filter_permissions)) {
            $filter_permissions = false;
        }

        //Get presets based on all flags.
        $preset_permissions = $this->getPresetPermissions($preset, array_keys($this->getOptions('preset_flags')));
        //Debug::Arr($preset_permissions, 'Preset Permissions: ', __FILE__, __LINE__, __METHOD__, 10);

        if (is_array($preset_permissions)) {
            foreach ($preset_permissions as $section => $permissions) {
                if ($filter_sections === false or in_array($section, $filter_sections)) {
                    foreach ($permissions as $name => $value) {
                        //Other permission basically matches anything that is not in filter list. Things like edit_own_password, etc...
                        if ($filter_permissions === false or in_array($name, $filter_permissions) or (in_array('other', $filter_permissions) and !in_array($name, $filter_permissions))) {
                            //Debug::Text('aSetting Permission - Section: '. $section .' Name: '. $name .' Value: '. (int)$value, __FILE__, __LINE__, __METHOD__, 10);
                            $retarr[$section][$name] = $value;
                        } //else { //Debug::Text('bNOT Setting Permission - Section: '. $section .' Name: '. $name .' Value: '. (int)$value, __FILE__, __LINE__, __METHOD__, 10);
                    }
                }
            }
        }

        if (isset($retarr)) {
            Debug::Arr($retarr, 'Filtered Permissions', __FILE__, __LINE__, __METHOD__, 10);
            return $retarr;
        }

        return false;
    }

    public function getPresetPermissions($preset, $preset_flags = array(), $force_system_presets = true)
    {
        $key = Option::getByValue($preset, $this->getOptions('preset'));
        if ($key !== false) {
            $preset = $key;
        }

        //Always add system presets when using the Permission wizard, so employees can login and such.
        //However when upgrading this causes a problem as it resets custom permission groups.
        if ($force_system_presets == true) {
            $preset_flags[] = 0;
        }
        asort($preset_flags);

        Debug::Text('Preset: ' . $preset, __FILE__, __LINE__, __METHOD__, 10);
        Debug::Arr($preset_flags, 'Preset Flags... ', __FILE__, __LINE__, __METHOD__, 10);

        if (!isset($preset) or $preset == '' or $preset == -1) {
            Debug::Text('No Preset set... Skipping!', __FILE__, __LINE__, __METHOD__, 10);
            return false;
        }

        $preset_permissions = array(
            10 => //Role: Regular Employee
                array(
                    0 => //Module: System
                        array(
                            'system' => array(
                                'login' => true,
                            ),
                            'user' => array(
                                'enabled' => true,
                                'view_own' => true,
                                'edit_own' => true,
                                'edit_own_password' => true,
                                'edit_own_phone_password' => true,
                            ),
                            'user_preference' => array(
                                'enabled' => true,
                                'view_own' => true,
                                'add' => true,
                                'edit_own' => true,
                                'delete_own' => true,
                            ),
                            'request' => array(
                                'enabled' => true,
                                'view_own' => true,
                                'add' => true,
                                'add_advanced' => true,
                                'edit_own' => true,
                                'delete_own' => true,
                            ),
                            'message' => array(
                                'enabled' => true,
                                'view_own' => true,
                                'add' => true,
                                'edit_own' => true,
                                'delete_own' => true,
                            ),
                            'help' => array(
                                'enabled' => true,
                                'view' => true,
                            ),

                        ),
                    10 => //Module: Scheduling
                        array(
                            'schedule' => array(
                                'enabled' => true,
                                'view_own' => true,
                            ),
                            'accrual' => array(
                                'enabled' => true,
                                'view_own' => true
                            ),
                            'absence' => array(
                                'enabled' => true,
                                'view_own' => true,
                            ),
                        ),
                    20 => //Module: Time & Attendance
                        array(
                            'punch' => array(
                                'enabled' => true,
                                'view_own' => true,
                                'add' => true,
                                'verify_time_sheet' => true,
                                'punch_in_out' => true,
                                'edit_transfer' => true,
                                'edit_branch' => true,
                                'edit_department' => true,
                                'edit_note' => true,
                                'edit_other_id1' => true,
                                'edit_other_id2' => true,
                                'edit_other_id3' => true,
                                'edit_other_id4' => true,
                                'edit_other_id5' => true,
                                'punch_timesheet' => true,
                            ),
                            'accrual' => array(
                                'enabled' => true,
                                'view_own' => true
                            ),
                            'absence' => array(
                                'enabled' => true,
                                'view_own' => true,
                            ),

                        ),
                    30 => //Module: Payroll
                        array(
                            'user' => array(
                                'enabled' => true,
                                'edit_own_bank' => true,
                            ),
                            'pay_stub' => array(
                                'enabled' => true,
                                'view_own' => true,
                            ),
                            'government_document' => array(
                                'enabled' => true,
                                'view_own' => true,
                            ),
                        ),
                    40 => //Module: Job Costing
                        array(
                            'punch' => array(
                                'edit_job' => true,
                                'edit_job_item' => true,
                                'edit_quantity' => true,
                                'edit_bad_quantity' => true,
                            ),
                            'job' => array(
                                'enabled' => true,
                            ),
                        ),
                    50 => //Module: Document Management
                        array(
                            'document' => array(
                                'enabled' => true,
                                'view' => true,
                            ),
                        ),
                    60 => //Module: Invoicing
                        array(),
                    70 => //Module: Human Resources
                        array(),
                    75 => //Module: Recruitement
                        array(),
                    80 => //Module: Expenses
                        array(
                            'user_expense' => array(
                                'enabled' => true,
                                'view_own' => true,
                                'add' => true,
                                'edit_own' => true, //Allow editing expenses once they are submitted, but not once authorized/declined. This is required to add though.
                                'delete_own' => true,
                            ),
                        ),
                ),
            12 => //Role: Regular Employee (Manual Punch)
                array(
                    20 => //Module: Time & Attendance
                        array(
                            'punch' => array(
                                'edit_own' => true,
                                'delete_own' => true,
                            ),
                            'absence' => array(
                                'add' => true,
                                'edit_own' => true,
                                'delete_own' => true,
                            ),
                        ),
                ),
            14 => //Role: Regular Employee (Manual TimeSheet)
                array(
                    20 => //Module: Time & Attendance
                        array(
                            'punch' => array(
                                'manual_timesheet' => true,
                            ),
                        ),
                ),
            18 => //Role: Supervisor (Subordinates Only)
                array(
                    0 => //Module: System
                        array(
                            'user' => array(
                                'add' => true, //Can only add user with permissions level equal or lower.
                                'view_child' => true,
                                'edit_child' => true,
                                'edit_advanced' => true,
                                'enroll_child' => true,
                                //'delete_child' => TRUE, //Disable deleting of users by default as a precautionary measure.
                                'edit_pay_period_schedule' => true,
                                'edit_permission_group' => true,
                                'edit_policy_group' => true,
                                'edit_hierarchy' => true,
                            ),
                            'user_preference' => array(
                                'view_child' => true,
                                'edit_child' => true,
                            ),
                            'request' => array(
                                'view_child' => true,
                                'edit_child' => true,
                                'delete_child' => true,
                                'authorize' => true
                            ),
                            'authorization' => array(
                                'enabled' => true,
                                'view' => true
                            ),
                            'message' => array(
                                'add_advanced' => true,
                                'send_to_child' => true,
                            ),
                            'report' => array(
                                'enabled' => true,
                                'view_user_information' => true,
                                //'view_user_detail' => TRUE,
                                'view_user_barcode' => true,
                            ),
                            'report_custom_column' => array(
                                'enabled' => true,
                                'view_own' => true,
                                'add' => true,
                                'edit_own' => true,
                                'delete_own' => true,
                            ),
                        ),
                    10 => //Module: Scheduling
                        array(
                            'schedule' => array(
                                'add' => true,
                                'view_child' => true,
                                'view_open' => true,
                                'edit_child' => true,
                                'delete_child' => true,
                                'edit_branch' => true,
                                'edit_department' => true,
                            ),
                            'recurring_schedule_template' => array(
                                'enabled' => true,
                                'view_own' => true,
                                'add' => true,
                                'edit_own' => true,
                                'delete_own' => true,
                            ),
                            'recurring_schedule' => array(
                                'enabled' => true,
                                'view_child' => true,
                                'add' => true,
                                'edit_child' => true,
                                'delete_child' => true,
                            ),
                            'absence' => array(
                                'add' => true,
                                'view_child' => true,
                                'edit_child' => true,
                                'delete_child' => true,
                                'edit_branch' => true,
                                'edit_department' => true,
                            ),
                            'accrual' => array(
                                'add' => true,
                                'view_child' => true,
                                'edit_own' => false,
                                'edit_child' => true,
                                'delete_own' => false,
                                'delete_child' => true,
                            ),
                            'report' => array(
                                'view_schedule_summary' => true,
                                'view_accrual_balance_summary' => true,
                            ),

                        ),
                    20 => //Module: Time & Attendance
                        array(
                            'punch' => array(
                                'view_child' => true,
                                'edit_child' => true,
                                'delete_child' => true,
                                'authorize' => true
                            ),
                            'absence' => array(
                                'add' => true,
                                'view_child' => true,
                                'edit_own' => false,
                                'edit_child' => true,
                                'edit_branch' => true,
                                'edit_department' => true,
                                'delete_own' => false,
                                'delete_child' => true,
                            ),
                            'accrual' => array(
                                'view_child' => true,
                                'add' => true,
                                'edit_child' => true,
                                'delete_child' => true,
                            ),
                            'report' => array(
                                'view_active_shift' => true,
                                'view_timesheet_summary' => true,
                                'view_punch_summary' => true,
                                'view_exception_summary' => true,
                                'view_accrual_balance_summary' => true,
                            ),

                        ),
                    30 => //Module: Payroll
                        array(),
                    40 => //Module: Job Costing
                        array(
                            'schedule' => array(
                                'edit_job' => true,
                                'edit_job_item' => true,
                            ),
                            'absence' => array(
                                'edit_job' => true,
                                'edit_job_item' => true,
                            ),
                            'job' => array(
                                'add' => true,
                                'view' => true, //Must be able to view all jobs so they can punch in/out.
                                'view_own' => true,
                                'edit_own' => true,
                                'delete_own' => true,
                            ),
                            'job_item' => array(
                                'enabled' => true,
                                'view' => true,
                                'add' => true,
                                'edit_own' => true,
                                'delete_own' => true,
                            ),
                            'job_report' => array(
                                'enabled' => true,
                                'view_job_summary' => true,
                                'view_job_analysis' => true,
                                'view_job_payroll_analysis' => true,
                                'view_job_barcode' => true
                            ),
                            'geo_fence' => array(
                                'enabled' => true,
                                'add' => true,
                                'view' => true, //Must be able to view all fences so they can punch in/out.
                                'view_own' => true,
                                'edit_own' => true,
                                'delete_own' => true,
                            ),
                        ),
                    50 => //Module: Document Management
                        array(
                            'document' => array(
                                'add' => true,
                                'view_private' => true,
                                'edit' => true,
                                'edit_private' => true,
                                'delete' => true,
                                'delete_private' => true,
                            ),

                        ),
                    60 => //Module: Invoicing
                        array(
                            'client' => array(
                                'enabled' => true,
                                'view' => true,
                                'add' => true,
                                'edit' => true,
                                'delete' => true,
                            ),
                            'client_payment' => array(
                                'enabled' => true,
                                'view' => true,
                                'add' => true,
                                'edit' => true,
                                'delete' => true,
                            ),
                            'transaction' => array(
                                'enabled' => true,
                                'view' => true,
                                'add' => true,
                                'edit' => true,
                                'delete' => true,
                            ),
                            'invoice' => array(
                                'enabled' => true,
                                'view' => true,
                                'add' => true,
                                'edit' => true,
                                'delete' => true,
                            ),
                        ),
                    70 => //Module: Human Resources
                        array(
                            'user_contact' => array(
                                'enabled' => true,
                                'add' => true, //Can only add user with permissions level equal or lower.
                                'view_child' => true,
                                'edit_child' => true,
                                'delete_child' => true,
                            ),
                            'qualification' => array(
                                'enabled' => true,
                                'add' => true,
                                'view' => true,
                                'edit_own' => true,
                                'delete_own' => true,
                                'view_child' => true,
                                'edit_child' => true,
                                'delete_child' => true,
                            ),
                            'user_education' => array(
                                'enabled' => true,
                                'add' => true,
                                'view' => true,
                                'edit_own' => true,
                                'delete_own' => true,
                                'view_child' => true,
                                'edit_child' => true,
                                'delete_child' => true,
                            ),
                            'user_license' => array(
                                'enabled' => true,
                                'add' => true,
                                'view' => true,
                                'edit_own' => true,
                                'delete_own' => true,
                                'view_child' => true,
                                'edit_child' => true,
                                'delete_child' => true,
                            ),
                            'user_skill' => array(
                                'enabled' => true,
                                'add' => true,
                                'view' => true,
                                'edit_own' => true,
                                'delete_own' => true,
                                'view_child' => true,
                                'edit_child' => true,
                                'delete_child' => true,
                            ),
                            'user_membership' => array(
                                'enabled' => true,
                                'add' => true,
                                'view' => true,
                                'edit_own' => true,
                                'delete_own' => true,
                                'view_child' => true,
                                'edit_child' => true,
                                'delete_child' => true,
                            ),
                            'user_language' => array(
                                'enabled' => true,
                                'add' => true,
                                'view' => true,
                                'edit_own' => true,
                                'delete_own' => true,
                                'view_child' => true,
                                'edit_child' => true,
                                'delete_child' => true,
                            ),
                            'kpi' => array(
                                'enabled' => true,
                                'add' => true,
                                'view' => true,
                                'edit_own' => true,
                                'delete_own' => true,
                                'view_child' => true,
                                'edit_child' => true,
                                'delete_child' => true,
                            ),
                            'user_review' => array(
                                'enabled' => true,
                                'add' => true,
                                'view_own' => true,
                                'view_child' => true,
                                'edit_child' => true,
                                'delete_child' => true,
                            ),
                            'hr_report' => array(
                                'enabled' => true,
                                'user_qualification' => true,
                                'user_review' => true,
                            ),

                        ),
                    75 => //Module: Recruitement
                        array(
                            'job_vacancy' => array(
                                'enabled' => true,
                                'add' => true,
                                'view_child' => true,
                                'edit_child' => true,
                                'delete_child' => true,
                            ),
                            'job_applicant' => array(
                                'enabled' => true,
                                'add' => true,
                                'view_child' => true,
                                'edit_child' => true,
                                'delete_child' => true,
                            ),
                            'job_application' => array(
                                'enabled' => true,
                                'add' => true,
                                'view_child' => true,
                                'edit_child' => true,
                                'delete_child' => true,
                            ),
                            'recruitment_report' => array(
                                'enabled' => true,
                                'user_recruitment' => true,
                            ),

                        ),
                    80 => //Module: Expenses
                        array(
                            'user_expense' => array(
                                'view_child' => true,
                                'add' => true,
                                'edit_child' => true,
                                'delete_child' => true,
                                'authorize' => true,
                            ),
                        ),
                ),
            20 => //Role: Supervisor (All Employees)
                array(
                    0 => //Module: System
                        array(
                            'user' => array(
                                'view' => true,
                                'edit' => true,
                                'enroll' => true,
                                //'delete' => TRUE, //Disable deleting of users by default as a precautionary measure.
                            ),
                            'user_preference' => array(
                                'view' => true,
                                'edit' => true,
                            ),
                            'request' => array(
                                'view' => true,
                                'edit' => true,
                                'delete' => true,
                            ),
                            'message' => array(
                                'send_to_any' => true,
                            ),
                        ),
                    10 => //Module: Scheduling
                        array(
                            'schedule' => array(
                                'view' => true,
                                'edit' => true,
                                'delete' => true
                            ),
                            'recurring_schedule_template' => array(
                                'view' => true,
                                'edit' => true,
                                'delete' => true,
                            ),
                            'recurring_schedule' => array(
                                'view' => true,
                                'edit' => true,
                                'delete' => true,
                            ),
                            'absence' => array(
                                'view' => true,
                                'edit' => true,
                                'delete' => true,
                            ),
                            'accrual' => array(
                                'view' => true,
                                'edit' => true,
                                'delete' => true,
                            ),

                        ),
                    20 => //Module: Time & Attendance
                        array(
                            'punch' => array(
                                'view' => true,
                                'edit' => true,
                                'delete' => true,
                            ),
                            'absence' => array(
                                'view' => true,
                                'edit' => true,
                                'delete' => true,
                                'edit_own' => true,
                                'delete_own' => true,
                            ),
                            'accrual' => array(
                                'view' => true,
                                'edit' => true,
                                'delete' => true,
                            ),
                        ),
                    30 => //Module: Payroll
                        array(),
                    40 => //Module: Job Costing
                        array(
                            'job' => array(
                                'view' => true,
                                'edit' => true,
                                'delete' => true,
                            ),
                            'job_item' => array(
                                'enabled' => true,
                                'view' => true,
                                'add' => true,
                                'edit' => true,
                                'delete' => true,
                            ),
                            'geo_fence' => array(
                                'view' => true,
                                'edit' => true,
                                'delete' => true,
                            ),
                        ),
                    50 => //Module: Document Management
                        array(),
                    60 => //Module: Invoicing
                        array(),
                    70 => //Module: Human Resources
                        array(
                            'user_contact' => array(
                                'add' => true,
                                'view' => true,
                                'edit' => true,
                                'delete' => true,
                            ),
                            'qualification' => array(
                                'edit' => true,
                                'delete' => true
                            ),
                            'user_education' => array(
                                'edit' => true,
                                'delete' => true,
                            ),
                            'user_license' => array(
                                'edit' => true,
                                'delete' => true,
                            ),
                            'user_skill' => array(
                                'edit' => true,
                                'delete' => true,
                            ),
                            'user_membership' => array(
                                'edit' => true,
                                'delete' => true,
                            ),
                            'user_language' => array(
                                'edit' => true,
                                'delete' => true,
                            ),
                            'kpi' => array(
                                'edit' => true,
                                'delete' => true,
                            ),
                            'user_review' => array(
                                'view' => true,
                                'edit' => true,
                                'delete' => true,
                            ),
                        ),
                    75 => //Module: Recruitement
                        array(
                            'job_vacancy' => array(
                                'view' => true,
                                'edit' => true,
                                'delete' => true,
                            ),
                            'job_applicant' => array(
                                'view' => true,
                                'edit' => true,
                                'delete' => true,
                            ),
                            'job_application' => array(
                                'view' => true,
                                'edit' => true,
                                'delete' => true,
                            ),
                        ),
                    80 => //Module: Expenses
                        array(
                            'user_expense' => array(
                                'view' => true,
                                'edit' => true,
                                'delete' => true,
                            ),
                        ),
                ),
            25 => //Role: HR Manager
                array(
                    0 => //Module: System
                        array(),
                    10 => //Module: Scheduling
                        array(),
                    20 => //Module: Time & Attendance
                        array(),
                    30 => //Module: Payroll
                        array(),
                    40 => //Module: Job Costing
                        array(),
                    50 => //Module: Document Management
                        array(),
                    60 => //Module: Invoicing
                        array(),
                    70 => //Module: Human Resources
                        array(
                            'qualification' => array(
                                'enabled' => true,
                                'view' => true,
                                'add' => true,
                                'edit' => true,
                                'delete' => true,
                            ),
                        ),
                    75 => //Module: Recruitement
                        array(),
                    80 => //Module: Expenses
                        array(),
                ),
            30 => //Role: Payroll Administrator
                array(
                    0 => //Module: System
                        array(
                            'company' => array(
                                'enabled' => true,
                                'view_own' => true,
                                'edit_own' => true,
                                'edit_own_bank' => true
                            ),
                            'user' => array(
                                'add' => true,
                                'edit_bank' => true,
                                'view_sin' => true,
                            ),
                            'wage' => array(
                                'enabled' => true,
                                'view' => true,
                                'add' => true,
                                'edit' => true,
                                'delete' => true
                            ),
                            'pay_period_schedule' => array(
                                'enabled' => true,
                                'view' => true,
                                'add' => true,
                                'edit' => true,
                                'delete' => true,
                                'assign' => true
                            ),
                            'pay_code' => array(
                                'enabled' => true,
                                'view' => true,
                                'add' => true,
                                'edit' => true,
                                'delete' => true,
                            ),
                            'pay_formula_policy' => array(
                                'enabled' => true,
                                'view' => true,
                                'add' => true,
                                'edit' => true,
                                'delete' => true,
                            ),
                            'report' => array(
                                'view_system_log' => true,
                            ),
                        ),
                    10 => //Module: Scheduling
                        array(),
                    20 => //Module: Time & Attendance
                        array(),
                    30 => //Module: Payroll
                        array(
                            'user_tax_deduction' => array(
                                'enabled' => true,
                                'view' => true,
                                'add' => true,
                                'edit' => true,
                                'delete' => true,
                            ),
                            'roe' => array(
                                'enabled' => true,
                                'view' => true,
                                'add' => true,
                                'edit' => true,
                                'delete' => true,
                            ),
                            'company_tax_deduction' => array(
                                'enabled' => true,
                                'view' => true,
                                'add' => true,
                                'edit' => true,
                                'delete' => true,
                            ),
                            'pay_stub_account' => array(
                                'enabled' => true,
                                'view' => true,
                                'add' => true,
                                'edit' => true,
                                'delete' => true
                            ),
                            'pay_stub' => array(
                                'view' => true,
                                'add' => true,
                                'edit' => true,
                                'delete' => true
                            ),
                            'government_document' => array(
                                'view' => true,
                                'add' => true,
                                'edit' => true,
                                'delete' => true
                            ),
                            'pay_stub_amendment' => array(
                                'enabled' => true,
                                'view' => true,
                                'add' => true,
                                'edit' => true,
                                'delete' => true
                            ),
                            'report' => array(
                                'view_pay_stub_summary' => true,
                                'view_payroll_export' => true,
                                //'view_employee_pay_stub_summary' => TRUE,
                                'view_remittance_summary' => true,
                                'view_wages_payable_summary' => true,
                                'view_t4_summary' => true,
                                'view_generic_tax_summary' => true,
                                'view_form941' => true,
                                'view_form940' => true,
                                'view_form940ez' => true,
                                'view_form1099misc' => true,
                                'view_formW2' => true,
                                'view_affordable_care' => true,
                                'view_general_ledger_summary' => true,
                            ),
                        ),
                    40 => //Module: Job Costing
                        array(),
                    50 => //Module: Document Management
                        array(),
                    60 => //Module: Invoicing
                        array(
                            'product' => array(
                                'enabled' => true,
                                'view' => true,
                                'add' => true,
                                'edit' => true,
                                'delete' => true,
                            ),
                            'tax_policy' => array(
                                'enabled' => true,
                                'view' => true,
                                'add' => true,
                                'edit' => true,
                                'delete' => true,
                            ),
                            'shipping_policy' => array(
                                'enabled' => true,
                                'view' => true,
                                'add' => true,
                                'edit' => true,
                                'delete' => true,
                            ),
                            'area_policy' => array(
                                'enabled' => true,
                                'view' => true,
                                'add' => true,
                                'edit' => true,
                                'delete' => true,
                            ),
                            'payment_gateway' => array(
                                'enabled' => true,
                                'view' => true,
                                'add' => true,
                                'edit' => true,
                                'delete' => true,
                            ),
                            'invoice_report' => array(
                                'enabled' => true,
                                'view_transaction_summary' => true,
                            ),
                        ),
                    70 => //Module: Human Resources
                        array(),
                    75 => //Module: Recruitement
                        array(),
                    80 => //Module: Expenses
                        array(
                            'report' => array(
                                'view_expense' => true
                            ),
                        ),
                ),
            40 => //Role: Administrator
                array(
                    0 => //Module: System
                        array(
                            'user' => array(
                                'timeclock_admin' => true,
                            ),
                            'user_date_total' => array(
                                'enabled' => true,
                                'view' => true,
                                'add' => true,
                                'edit' => true,
                                'delete' => true,
                            ),
                            'policy_group' => array(
                                'enabled' => true,
                                'view' => true,
                                'add' => true,
                                'edit' => true,
                                'delete' => true,
                            ),
                            'contributing_pay_code_policy' => array(
                                'enabled' => true,
                                'view' => true,
                                'add' => true,
                                'edit' => true,
                                'delete' => true,
                            ),
                            'contributing_shift_policy' => array(
                                'enabled' => true,
                                'view' => true,
                                'add' => true,
                                'edit' => true,
                                'delete' => true,
                            ),
                            'regular_time_policy' => array(
                                'enabled' => true,
                                'view' => true,
                                'add' => true,
                                'edit' => true,
                                'delete' => true,
                            ),
                            'schedule_policy' => array(
                                'enabled' => true,
                                'view' => true,
                                'add' => true,
                                'edit' => true,
                                'delete' => true,
                            ),
                            'meal_policy' => array(
                                'enabled' => true,
                                'view' => true,
                                'add' => true,
                                'edit' => true,
                                'delete' => true,
                            ),
                            'break_policy' => array(
                                'enabled' => true,
                                'view' => true,
                                'add' => true,
                                'edit' => true,
                                'delete' => true,
                            ),
                            'over_time_policy' => array(
                                'enabled' => true,
                                'view' => true,
                                'add' => true,
                                'edit' => true,
                                'delete' => true,
                            ),
                            'premium_policy' => array(
                                'enabled' => true,
                                'view' => true,
                                'add' => true,
                                'edit' => true,
                                'delete' => true,
                            ),
                            'accrual_policy' => array(
                                'enabled' => true,
                                'view' => true,
                                'add' => true,
                                'edit' => true,
                                'delete' => true,
                            ),
                            'absence_policy' => array(
                                'enabled' => true,
                                'view' => true,
                                'add' => true,
                                'edit' => true,
                                'delete' => true,
                            ),
                            'round_policy' => array(
                                'enabled' => true,
                                'view' => true,
                                'add' => true,
                                'edit' => true,
                                'delete' => true,
                            ),
                            'exception_policy' => array(
                                'enabled' => true,
                                'view' => true,
                                'add' => true,
                                'edit' => true,
                                'delete' => true,
                            ),
                            'holiday_policy' => array(
                                'enabled' => true,
                                'view' => true,
                                'add' => true,
                                'edit' => true,
                                'delete' => true,
                            ),
                            'round_policy' => array(
                                'enabled' => true,
                                'view' => true,
                                'add' => true,
                                'edit' => true,
                                'delete' => true,
                            ),
                            'currency' => array(
                                'enabled' => true,
                                'view' => true,
                                'add' => true,
                                'edit' => true,
                                'delete' => true,
                            ),
                            'branch' => array(
                                'enabled' => true,
                                'view' => true,
                                'add' => true,
                                'edit' => true,
                                'delete' => true,
                            ),
                            'department' => array(
                                'enabled' => true,
                                'view' => true,
                                'add' => true,
                                'edit' => true,
                                'delete' => true,
                                'assign' => true
                            ),
                            'gep_fence' => array(
                                'enabled' => true,
                                'view' => true,
                                'add' => true,
                                'edit' => true,
                                'delete' => true),
                            'station' => array(
                                'enabled' => true,
                                'view' => true,
                                'add' => true,
                                'edit' => true,
                                'delete' => true,
                                'assign' => true
                            ),
                            'report' => array(//'view_shift_actual_time' => TRUE,
                            ),
                            'hierarchy' => array(
                                'enabled' => true,
                                'view' => true,
                                'add' => true,
                                'edit' => true,
                                'delete' => true,
                            ),
                            'other_field' => array(
                                'enabled' => true,
                                'view' => true,
                                'add' => true,
                                'edit' => true,
                                'delete' => true,
                            ),
                            'permission' => array(
                                'enabled' => true,
                                'view' => true,
                                'add' => true,
                                'edit' => true,
                                'delete' => true,
                            ),
                            'report_custom_column' => array(
                                'view' => true,
                                'edit' => true,
                                'delete' => true,
                            ),
                        ),
                    10 => //Module: Scheduling
                        array(),
                    20 => //Module: Time & Attendance
                        array(),
                    30 => //Module: Payroll
                        array(),
                    40 => //Module: Job Costing
                        array(),
                    50 => //Module: Document Management
                        array(),
                    60 => //Module: Invoicing
                        array(
                            'invoice_config' => array(
                                'enabled' => true,
                                'add' => true,
                                'edit' => true,
                                'delete' => true,
                            ),

                        ),
                    70 => //Module: Human Resources
                        array(),
                    75 => //Module: Recruitement
                        array(),
                    80 => //Module: Expenses
                        array(
                            'expense_policy' => array(
                                'enabled' => true,
                                'view' => true,
                                'add' => true,
                                'edit' => true,
                                'delete' => true,
                            ),
                        ),
                ),
        );

        $retarr = array();

        //Loop over each preset adding the permissions together for that preset and the role that is selected.
        $preset_options = array_keys(Misc::trimSortPrefix($this->getOptions('preset')));
        if (is_array($preset_options)) {
            foreach ($preset_options as $preset_option) {
                if (isset($preset_permissions[$preset_option]) and $preset_option <= $preset) {
                    foreach ($preset_flags as $preset_flag) {
                        if (isset($preset_permissions[$preset_option][$preset_flag])) {
                            Debug::Text('Applying Preset: ' . $preset_option . ' Preset Flag: ' . $preset_flag, __FILE__, __LINE__, __METHOD__, 10);
                            $retarr = Misc::arrayMergeRecursive($retarr, $preset_permissions[$preset_option][$preset_flag]);
                        }
                    }
                }
            }
        }

        return $retarr;
    }

    public function applyPreset($permission_control_id, $preset, $preset_flags)
    {
        $preset_permissions = $this->getPresetPermissions($preset, $preset_flags);

        if (!is_array($preset_permissions)) {
            return false;
        }

        $this->setPermissionControl($permission_control_id);

        $pf = TTnew('PermissionFactory');
        $pf->StartTransaction();

        //Delete all previous permissions for this control record..
        $this->deletePermissions($this->getCompany(), $permission_control_id);

        $created_date = time();
        foreach ($preset_permissions as $section => $permissions) {
            foreach ($permissions as $name => $value) {
                if ($pf->isIgnore($section, $name) == false) {
                    //Put all inserts into a single query, this speeds things up greatly (9s to less than .5s),
                    //but we are by-passing the audit log so make sure we add a new entry describing what took place.
                    $ph[] = $permission_control_id;
                    $ph[] = $section;
                    $ph[] = $name;
                    $ph[] = (int)$value;
                    $ph[] = $created_date;
                    $data[] = '(?, ?, ?, ?, ?)';

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

        $pclf = TTnew('PermissionControlListFactory');
        if (isset($data)) {
            //Save data in a single SQL query.
            $query = 'INSERT INTO ' . $this->getTable() . '(PERMISSION_CONTROL_ID, SECTION, NAME, VALUE, CREATED_DATE) VALUES' . implode(',', $data);
            //Debug::Text('Query: '. $query, __FILE__, __LINE__, __METHOD__, 10);
            $this->db->Execute($query, $ph);

            //Make sure we keep the seqenence in sync, only required for MySQL.
            if ($this->getDatabaseType() == 'mysql') {
                Debug::Text('Keeping MySQL sequence in sync...', __FILE__, __LINE__, __METHOD__, 10);
                $install = TTNew('Install');
                $install->initializeSequence($this, $this->getTable(), get_class($this), $this->db);
                unset($install);
            }

            //Debug::Text('Logged detail records in: '. (microtime(TRUE) - $start_time), __FILE__, __LINE__, __METHOD__, 10);
            TTLog::addEntry($permission_control_id, 20, TTi18n::getText('Applying Permission Preset') . ': ' . Option::getByKey($preset, $this->getOptions('preset')), null, $pclf->getTable(), $this);
        }
        unset($ph, $data, $created_date, $preset_permissions, $permissions, $section, $name, $value);

        //Clear cache for all users assigned to this permission_control_id
        $pclf->getById($permission_control_id);
        if ($pclf->getRecordCount() > 0) {
            $pc_obj = $pclf->getCurrent();

            if (is_array($pc_obj->getUser())) {
                foreach ($pc_obj->getUser() as $user_id) {
                    $pf->clearCache($user_id, $this->getCompany());
                }
            }
        }
        unset($pclf, $pc_obj, $user_id);

        //$pf->FailTransaction();
        $pf->CommitTransaction();

        return true;
    }

    public function setPermissionControl($id)
    {
        $id = trim($id);

        $pclf = TTnew('PermissionControlListFactory');

        if ($id != 0
            or
            $this->Validator->isResultSetWithRows('permission_control',
                $pclf->getByID($id),
                TTi18n::gettext('Permission Group is invalid')
            )
        ) {
            $this->data['permission_control_id'] = $id;

            return true;
        }

        return false;
    }

    public function deletePermissions($company_id, $permission_control_id)
    {
        if ($company_id == '') {
            return false;
        }

        if ($permission_control_id == '') {
            return false;
        }

        $plf = TTnew('PermissionListFactory');
        $plf->getByCompanyIDAndPermissionControlId($company_id, $permission_control_id);
        foreach ($plf as $permission_obj) {
            $permission_obj->delete(true);
            $this->removeCache($this->getCacheID());
        }

        return true;
    }

    public function getCacheID()
    {
        $cache_id = 'permission_query_' . $this->getSection() . $this->getName() . $this->getPermissionControl() . $this->getCompany();

        return $cache_id;
    }

    public function getName()
    {
        if (isset($this->data['name'])) {
            return $this->data['name'];
        }

        return false;
    }

    //This is used by CompanyFactory to create the initial permissions when creating a new company.
    //Also by the Quick Start wizard.

    public function getPermissionControl()
    {
        if (isset($this->data['permission_control_id'])) {
            return (int)$this->data['permission_control_id'];
        }

        return false;
    }

    public function getCompany()
    {
        if ($this->company_id != '') {
            return $this->company_id;
        } else {
            $company_id = $this->getPermissionControlObject()->getCompany();

            return $company_id;
        }
    }

    public function getPermissionControlObject()
    {
        if (is_object($this->permission_control_obj)) {
            return $this->permission_control_obj;
        } else {
            $pclf = TTnew('PermissionControlListFactory');
            $pclf->getById($this->getPermissionControl());

            if ($pclf->getRecordCount() == 1) {
                $this->permission_control_obj = $pclf->getCurrent();

                return $this->permission_control_obj;
            }

            return false;
        }
    }

    public function preSave()
    {
        //Just update any existing permissions. It would probably be faster to delete them all and re-insert though.
        $plf = TTnew('PermissionListFactory');
        $obj = $plf->getByCompanyIdAndPermissionControlIdAndSectionAndName($this->getCompany(), $this->getPermissionControl(), $this->getSection(), $this->getName())->getCurrent();
        $this->setId($obj->getId());

        return true;
    }

    public function clearCache($user_id, $company_id)
    {
        Debug::Text(' Clearing Cache for User ID: ' . $user_id, __FILE__, __LINE__, __METHOD__, 10);

        $cache_id = 'permission_all' . $user_id . $company_id;
        return $this->removeCache($cache_id);
    }

    public function postSave()
    {
        //$cache_id = 'permission_query_'.$this->getSection().$this->getName().$this->getUser().$this->getCompany();
        //$this->removeCache( $this->getCacheID() );

        return true;
    }

    public function addLog($log_action)
    {
        if ($this->getValue() == true) {
            $value_display = TTi18n::getText('ALLOW');
        } else {
            $value_display = TTi18n::getText('DENY');
        }

        return TTLog::addEntry($this->getPermissionControl(), $log_action, TTi18n::getText('Section') . ': ' . Option::getByKey($this->getSection(), $this->getOptions('section')) . ' Name: ' . Option::getByKey($this->getName(), $this->getOptions('name', $this->getSection())) . ' Value: ' . $value_display, null, $this->getTable());
    }

    public function getValue()
    {
        if (isset($this->data['value']) and $this->data['value'] == 1) {
            return true;
        } else {
            return false;
        }
    }
}
