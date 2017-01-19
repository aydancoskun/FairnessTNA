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
 * @package Modules\Report
 */
class TimesheetDetailReport extends Report {

	function __construct() {
		$this->title = TTi18n::getText('TimeSheet Detail Report');
		$this->file_name = 'timesheet_detail_report';

		parent::__construct();

		return TRUE;
	}

	protected function _checkPermissions( $user_id, $company_id ) {
		if ( $this->getPermissionObject()->Check('report', 'enabled', $user_id, $company_id )
				AND $this->getPermissionObject()->Check('report', 'view_timesheet_summary', $user_id, $company_id ) ) { //Piggyback on timesheet summary permissions.
			return TRUE;
		} else {
			//Debug::Text('Regular employee viewing their own timesheet...', __FILE__, __LINE__, __METHOD__, 10);
			//Regular employee printing timesheet for themselves. Force specific config options.
			//Get current pay period from config, then overwrite it with
			$filter_config = $this->getFilterConfig();
			if ( isset($filter_config['time_period']['pay_period_id']) ) {
				$pay_period_id = $filter_config['time_period']['pay_period_id'];
			} else {
				$pay_period_id = 0;
			}
			$this->setFilterConfig( array( 'include_user_id' => array($user_id), 'time_period' => array( 'time_period' => 'custom_pay_period', 'pay_period_id' => $pay_period_id ) ) );

			return TRUE;
		}

		return FALSE;
	}

	protected function _validateConfig() {
		$config = $this->getConfig();

		//Make sure some time period is selected.
		if ( !isset($config['filter']['time_period']) AND !isset($config['filter']['pay_period_id']) ) {
			$this->validator->isTrue( 'time_period', FALSE, TTi18n::gettext('No time period defined for this report') );
		}

		return TRUE;
	}

	protected function _getOptions( $name, $params = NULL ) {
		$retval = NULL;
		switch( $name ) {
			case 'output_format':
				$retval = array_merge( parent::getOptions('default_output_format'),
									array(
										'-1100-pdf_timesheet' => TTi18n::gettext('TimeSheet Summary'),
										'-1110-pdf_timesheet_detail' => TTi18n::gettext('TimeSheet Detail'),
										)
									);
				break;
			case 'default_setup_fields':
				$retval = array(
										'template',
										'time_period',
										'columns',
								);
				break;
			case 'setup_fields':
				$retval = array(
										//Static Columns - Aggregate functions can't be used on these.
										'-1000-template' => TTi18n::gettext('Template'),
										'-1010-time_period' => TTi18n::gettext('Time Period'),

										'-2010-user_status_id' => TTi18n::gettext('Employee Status'),
										'-2020-user_group_id' => TTi18n::gettext('Employee Group'),
										'-2030-user_title_id' => TTi18n::gettext('Employee Title'),

										'-2035-user_tag' => TTi18n::gettext('Employee Tags'),
										'-2040-include_user_id' => TTi18n::gettext('Employee Include'),
										'-2050-exclude_user_id' => TTi18n::gettext('Employee Exclude'),
										'-2060-default_branch_id' => TTi18n::gettext('Default Branch'),
										'-2070-default_department_id' => TTi18n::gettext('Default Department'),
										'-2080-punch_branch_id' => TTi18n::gettext('Punch Branch'),
										'-2090-punch_department_id' => TTi18n::gettext('Punch Department'),
										'-2100-custom_filter' => TTi18n::gettext('Custom Filter'),
										'-2200-currency_id' => TTi18n::gettext('Currency'),

										'-4020-include_no_data_rows' => TTi18n::gettext('Include Blank Records'),

										'-5000-columns' => TTi18n::gettext('Display Columns'),
										'-5010-group' => TTi18n::gettext('Group By'),
										'-5020-sub_total' => TTi18n::gettext('SubTotal By'),
										'-5030-sort' => TTi18n::gettext('Sort By'),
							);
				ksort($retval);
				break;
			case 'time_period':
				$retval = TTDate::getTimePeriodOptions();
				break;
			case 'date_columns':
				$retval = array_merge(
									TTDate::getReportDateOptions( 'hire', TTi18n::getText('Hire Date'), 12, FALSE ),
									TTDate::getReportDateOptions( 'termination', TTi18n::getText('Termination Date'), 13, FALSE ),
									TTDate::getReportDateOptions( NULL, TTi18n::getText('Date'), 14, TRUE )
								);
				break;
			case 'custom_columns':
				//Get custom fields for report data.
				$oflf = TTnew( 'OtherFieldListFactory' );
				//User and Punch fields conflict as they are merged together in a secondary process.
				$other_field_names = $oflf->getByCompanyIdAndTypeIdArray( $this->getUserObject()->getCompany(), array(4, 5, 10, 12), array( 4 => 'branch_', 5 => 'department_', 10 => '', 12 => 'user_title_' ) );
				if ( is_array($other_field_names) ) {
					$retval = Misc::addSortPrefix( $other_field_names, 9000 );
				}
				break;
			case 'report_custom_column':
				break;
			case 'report_custom_filters':
				break;
			case 'report_dynamic_custom_column':
				break;
			case 'report_static_custom_column':
				break;
			case 'formula_columns':
				$retval = TTMath::formatFormulaColumns( array_merge( array_diff( $this->getOptions('static_columns'), (array)$this->getOptions('report_static_custom_column') ), $this->getOptions('dynamic_columns') ) );
				break;
			case 'filter_columns':
				$retval = TTMath::formatFormulaColumns( array_merge( $this->getOptions('static_columns'), $this->getOptions('dynamic_columns'), (array)$this->getOptions('report_dynamic_custom_column') ) );
				break;
			case 'static_columns':
				$retval = array(
										//Static Columns - Aggregate functions can't be used on these.
										'-1000-first_name' => TTi18n::gettext('First Name'),
										'-1001-middle_name' => TTi18n::gettext('Middle Name'),
										'-1002-last_name' => TTi18n::gettext('Last Name'),
										'-1005-full_name' => TTi18n::gettext('Full Name'),
										'-1030-employee_number' => TTi18n::gettext('Employee #'),
										'-1035-user_title_name' => TTi18n::gettext('Employee Title'),

										'-1040-status' => TTi18n::gettext('Status'),
										'-1050-title' => TTi18n::gettext('Title'),
										'-1055-city' => TTi18n::gettext('City'),
										'-1060-province' => TTi18n::gettext('Province/State'),
										'-1070-country' => TTi18n::gettext('Country'),
										'-1080-user_group' => TTi18n::gettext('Group'),
										'-1090-default_branch' => TTi18n::gettext('Default Branch'),
										'-1100-default_department' => TTi18n::gettext('Default Department'),
										'-1110-currency' => TTi18n::gettext('Currency'),
										'-1111-current_currency' => TTi18n::gettext('Current Currency'),

										//'-1110-verified_time_sheet' => TTi18n::gettext('Verified TimeSheet'),
										//'-1120-pending_request' => TTi18n::gettext('Pending Requests'),

										'-1150-permission_control' => TTi18n::gettext('Permission Group'),
										'-1160-pay_period_schedule' => TTi18n::gettext('Pay Period Schedule'),
										'-1170-policy_group' => TTi18n::gettext('Policy Group'),

										//Handled in date_columns above.
										//'-1430-pay_period' => TTi18n::gettext('Pay Period'),

										'-1530-branch' => TTi18n::gettext('Branch'), //Need to keep legacy key as to no break saved reports.
										'-1531-branch_manual_id' => TTi18n::gettext('Branch Code'),
										'-1540-department' => TTi18n::gettext('Department'), //Need to keep legacy key as to no break saved reports.
										'-1541-department_manual_id' => TTi18n::gettext('Department Code'),

										'-1580-sin' => TTi18n::gettext('SIN/SSN'),

										'-1590-note' => TTi18n::gettext('Note'),
										'-1595-tag' => TTi18n::gettext('Tags'),

										'-1610-verified_time_sheet' => TTi18n::gettext('Verified TimeSheet'),
										'-1615-verified_time_sheet_date' => TTi18n::gettext('Verified TimeSheet Date'),

										'-2100-worked_hour_of_day' => TTi18n::gettext('Worked Hour Of Day'),
							);

				$retval = array_merge( $retval, (array)$this->getOptions('date_columns'), (array)$this->getOptions('custom_columns'), (array)$this->getOptions('report_static_custom_column') );
				ksort($retval);
				break;
			case 'dynamic_columns':
				$retval = array(
										//Dynamic - Aggregate functions can be used

										//Take into account wage groups. However hourly_rates for the same hour type, so we need to figure out an average hourly rate for each column?
										//'-2010-hourly_rate' => TTi18n::gettext('Hourly Rate'),

										'-2070-schedule_working' => TTi18n::gettext('Scheduled Time'),
										'-2072-schedule_working_diff' => TTi18n::gettext('Scheduled Time Diff.'),
										'-2080-schedule_absence' => TTi18n::gettext('Scheduled Absence'),

										'-2085-worked_days' => TTi18n::gettext('Worked Days'),
										'-2090-worked_hour_of_day_total' => TTi18n::gettext('Worked Employees/Hour'),

										'-2094-min_punch_time_stamp' => TTi18n::gettext('First In Punch'),
										'-2095-max_punch_time_stamp' => TTi18n::gettext('Last Out Punch'),

										'-2096-min_schedule_time_stamp' => TTi18n::gettext('First In Schedule'),
										'-2097-max_schedule_time_stamp' => TTi18n::gettext('Last Out Schedule'),

										'-2098-min_schedule_diff' => TTi18n::gettext('First In Schedule Diff.'),
										'-2099-max_schedule_diff' => TTi18n::gettext('Last Out Schedule Diff.'),

										'-3000-worked_time' => TTi18n::gettext('Total Worked Time'),
										'-3010-regular_time' => TTi18n::gettext('Total Regular Time'),
										'-3015-overtime_time' => TTi18n::gettext('Total OverTime'),
										'-3020-absence_time' => TTi18n::gettext('Total Absence Time'),
										'-3022-absence_taken_time' => TTi18n::gettext('Total Absence Time (Taken)'),
										'-3025-premium_time' => TTi18n::gettext('Total Premium Time'),
										'-3030-gross_time' => TTi18n::gettext('Total Paid Time'),
										//'-3030-actual_time' => TTi18n::gettext('Total Actual Time'),
										//'-3035-actual_time_diff' => TTi18n::gettext('Actual Time Difference'),

										'-3090-lunch_time' => TTi18n::gettext('Lunch Time (Taken)'),
										'-3091-break_time' => TTi18n::gettext('Break Time (Taken)'),

										'-3210-regular_wage' => TTi18n::gettext('Total Regular Time Wage'),
										'-3215-overtime_wage' => TTi18n::gettext('Total OverTime Wage'),
										'-3220-absence_wage' => TTi18n::gettext('Total Absence Time Wage'),
										'-3225-premium_wage' => TTi18n::gettext('Total Premium Time Wage'),

										'-3200-gross_wage' => TTi18n::gettext('Gross Wage'),
										'-3400-gross_wage_with_burden' => TTi18n::gettext('Gross Wage w/Burden'),
										//'-3390-gross_hourly_rate' => TTi18n::gettext('Gross Hourly Rate'),

										'-3500-udt_note' => TTi18n::gettext('TimeSheet Notes'),
							);

				$retval = array_merge( $retval, $this->getOptions('paycode_columns') );
				ksort($retval);

				break;
			case 'paycode_columns':
				$retval = parent::__getOptions( $name, 3 );
				break;
			case 'columns':
				$retval = array_merge( $this->getOptions('static_columns'), $this->getOptions('dynamic_columns'), (array)$this->getOptions('report_dynamic_custom_column') );
				break;
			case 'column_format':
				//Define formatting function for each column.
				$columns = array_merge( $this->getOptions('dynamic_columns'), (array)$this->getOptions('report_custom_column') );
				if ( is_array($columns) ) {
					foreach($columns as $column => $name ) {
						if ( strpos($column, '_wage') !== FALSE OR strpos($column, '_hourly_rate') !== FALSE ) {
							$retval[$column] = 'currency';
						} elseif ( strpos($column, '_time') OR strpos($column, 'schedule_') ) {
							$retval[$column] = 'time_unit';
						}
					}
				}
				$retval['verified_time_sheet_date'] = 'time_stamp';
				$retval['min_punch_time_stamp'] = $retval['max_punch_time_stamp'] = 'time';
				$retval['min_schedule_time_stamp'] = $retval['max_schedule_time_stamp'] = 'time';
				$retval['worked_hour_of_day'] = 'time';
				$retval['worked_days'] = 'numeric';
				break;
			case 'aggregates':
				$retval = array();
				$dynamic_columns = array_keys( Misc::trimSortPrefix( array_merge( $this->getOptions('dynamic_columns'), (array)$this->getOptions('report_dynamic_custom_column') ) ) );
				if ( is_array($dynamic_columns ) ) {
					foreach( $dynamic_columns as $column ) {
						switch ( $column ) {
							default:
								if ( strpos($column, '_hourly_rate') !== FALSE ) {
									$retval[$column] = 'avg';
								} elseif ( strpos($column, 'min_punch_time_stamp') !== FALSE OR strpos($column, 'min_schedule_time_stamp') !== FALSE ) {
									$retval[$column] = 'min_not_null'; //Need to use the min_not_null otherwise when auto-deduct meal policies exist the IN punch will always be blank.
								} elseif ( strpos($column, 'max_punch_time_stamp') !== FALSE OR strpos($column, 'max_schedule_time_stamp') !== FALSE ) {
									$retval[$column] = 'max_not_null';
								} elseif ( strpos($column, '_note') !== FALSE ) {
									$retval[$column] = 'concat';
								} else {
									$retval[$column] = 'sum';
								}
						}
					}
				}
				$retval['verified_time_sheet'] = 'first';
				$retval['verified_time_sheet_date'] = 'first';
				break;
			case 'templates':
				$retval = array(
										'-1050-by_employee+all_time' => TTi18n::gettext('All Time by Employee'),
										'-1150-by_date_by_full_name+all_time' => TTi18n::gettext('All Time by Date/Employee'),
										'-1200-by_full_name_by_date+all_time' => TTi18n::gettext('All Time by Employee/Date'),
										'-1250-by_branch+regular+all_time' => TTi18n::gettext('All Time by Branch'),
										'-1300-by_department+all_time' => TTi18n::gettext('All Time by Department'),
										'-1350-by_branch_by_department+all_time' => TTi18n::gettext('All Time by Branch/Department'),
										'-1400-by_pay_period+all_time' => TTi18n::gettext('All Time by Pay Period'),
										'-1450-by_pay_period_by_employee+all_time' => TTi18n::gettext('All Time by Pay Period/Employee'),
										'-1455-by_pay_period_by_date_stamp_by_employee+all_time' => TTi18n::gettext('All Time by Pay Period/Date/Employee'),
										'-1500-by_pay_period_by_branch+regular+regular_wage+all_time' => TTi18n::gettext('All Time by Pay Period/Branch'),
										'-1550-by_pay_period_by_department+all_time' => TTi18n::gettext('All Time by Pay Period/Department'),
										'-1600-by_pay_period_by_branch_by_department+all_time' => TTi18n::gettext('All Time by Pay Period/Branch/Department'),
										'-1650-by_employee_by_pay_period+all_time' => TTi18n::gettext('All Time by Employee/Pay Period'),
										'-1700-by_branch_by_pay_period+all_time' => TTi18n::gettext('All Time by Pay Branch/Pay Period'),
										'-1850-by_department_by_pay_period+all_time' => TTi18n::gettext('All Time by Department/Pay Period'),
										'-1900-by_branch_by_department_by_pay_period+all_time' => TTi18n::gettext('All Time by Branch/Department/Pay Period'),
										'-1950-by_full_name_by_dow+all_time' => TTi18n::gettext('All Time by Employee/Day of Week'),

										'-2100-by_employee+all_time+all_wage' => TTi18n::gettext('All Time+Wage by Employee'),
										'-2150-by_date_by_full_name+all_time+all_wage' => TTi18n::gettext('All Time+Wage by Date/Employee'),
										'-2200-by_full_name_by_date+all_time+all_wage' => TTi18n::gettext('All Time+Wage by Employee/Date'),
										'-2250-by_branch+regular+all_time+all_wage' => TTi18n::gettext('All Time+Wage by Branch'),
										'-2300-by_department+all_time+all_wage' => TTi18n::gettext('All Time+Wage by Department'),
										'-2350-by_branch_by_department+all_time+all_wage' => TTi18n::gettext('All Time+Wage by Branch/Department'),
										'-2400-by_pay_period+all_time+all_wage' => TTi18n::gettext('All Time+Wage by Pay Period'),
										'-2450-by_pay_period+all_time+all_wage' => TTi18n::gettext('All Time+Wage by Pay Period/Employee'),
										'-2455-by_pay_period_by_date_stamp_by_employee+all_time+all_wage' => TTi18n::gettext('All Time+Wage by Pay Period/Date/Employee'),
										'-2500-by_pay_period_by_branch+regular+regular_wage+all_time+all_wage' => TTi18n::gettext('All Time+Wage by Pay Period/Branch'),
										'-2550-by_pay_period_by_department+all_time+all_wage' => TTi18n::gettext('All Time+Wage by Pay Period/Department'),
										'-2600-by_pay_period_by_branch_by_department+all_time+all_wage' => TTi18n::gettext('All Time+Wage by Pay Period/Branch/Department'),
										'-2650-by_employee_by_pay_period+all_time+all_wage' => TTi18n::gettext('All Time+Wage by Employee/Pay Period'),
										'-2700-by_branch_by_pay_period+all_time+all_wage' => TTi18n::gettext('All Time+Wage by Pay Branch/Pay Period'),
										'-2850-by_department_by_pay_period+all_time+all_wage' => TTi18n::gettext('All Time+Wage by Department/Pay Period'),
										'-2900-by_branch_by_department_by_pay_period+all_time+all_wage' => TTi18n::gettext('All Time+Wage by Branch/Department/Pay Period'),
										'-2950-by_full_name_by_dow+all_time+all_wage' => TTi18n::gettext('All Time+Wage by Employee/Day of Week'),
							);
				break;
			case 'template_config':
				$template = strtolower( Misc::trimSortPrefix( $params['template'] ) );
				if ( isset($template) AND $template != '' ) {
					switch( $template ) {
						case 'specific_template_name':
							//$retval['column'] = array();
							//$retval['filter'] = array();
							//$retval['group'] = array();
							//$retval['sub_total'] = array();
							//$retval['sort'] = array();
							break;
						default:
							Debug::Text(' Parsing template name: '. $template, __FILE__, __LINE__, __METHOD__, 10);
							$retval['-1010-time_period']['time_period'] = 'last_pay_period';

							//Parse template name, and use the keywords separated by '+' to determine settings.
							$template_keywords = explode('+', $template );
							if ( is_array($template_keywords) ) {
								foreach( $template_keywords as $template_keyword ) {
									Debug::Text(' Keyword: '. $template_keyword, __FILE__, __LINE__, __METHOD__, 10);

									switch( $template_keyword ) {
										//Columns
										case 'all_time':
											$retval['columns'][] = 'worked_time';
											$retval['columns'][] = 'regular_time';
											$retval['columns'][] = 'overtime_time';
											$retval['columns'][] = 'absence_time';
											$retval['columns'][] = 'premium_time';
											break;
										case 'all_wage':
											$retval['columns'][] = 'gross_wage';
											$retval['columns'][] = 'regular_wage';
											$retval['columns'][] = 'overtime_wage';
											$retval['columns'][] = 'absence_wage';
											$retval['columns'][] = 'premium_wage';
											break;
										case 'schedule_diff':
											$retval['columns'][] = 'min_punch_time_stamp';
											$retval['columns'][] = 'min_schedule_time_stamp';
											$retval['columns'][] = 'min_schedule_diff';
											$retval['columns'][] = 'max_punch_time_stamp';
											$retval['columns'][] = 'max_schedule_time_stamp';
											$retval['columns'][] = 'max_schedule_diff';
											$retval['columns'][] = 'worked_time';
											$retval['columns'][] = 'schedule_working';
											$retval['columns'][] = 'schedule_working_diff';
											break;
										//Filter

										//Group By
										//SubTotal
										//Sort

										case 'by_employee':
											$retval['columns'][] = 'first_name';
											$retval['columns'][] = 'last_name';

											$retval['group'][] = 'first_name';
											$retval['group'][] = 'last_name';

											$retval['sort'][] = array('last_name' => 'asc');
											$retval['sort'][] = array('first_name' => 'asc');
											break;
										case 'by_branch':
											$retval['columns'][] = 'branch';

											$retval['group'][] = 'branch';

											$retval['sort'][] = array('branch' => 'asc');
											break;
										case 'by_department':
											$retval['columns'][] = 'department';

											$retval['group'][] = 'department';

											$retval['sort'][] = array('department' => 'asc');
											break;
										case 'by_branch_by_department':
											$retval['columns'][] = 'branch';
											$retval['columns'][] = 'department';

											$retval['group'][] = 'branch';
											$retval['group'][] = 'department';

											$retval['sub_total'][] = 'branch';

											$retval['sort'][] = array('branch' => 'asc');
											$retval['sort'][] = array('department' => 'asc');
											break;
										case 'by_pay_period':
											$retval['columns'][] = 'pay_period';

											$retval['group'][] = 'pay_period';

											$retval['sort'][] = array('pay_period' => 'asc');
											break;
										case 'by_pay_period_by_employee':
											$retval['columns'][] = 'pay_period';
											$retval['columns'][] = 'first_name';
											$retval['columns'][] = 'last_name';

											$retval['group'][] = 'pay_period';
											$retval['group'][] = 'first_name';
											$retval['group'][] = 'last_name';

											$retval['sub_total'][] = 'pay_period';

											$retval['sort'][] = array('pay_period' => 'asc');
											$retval['sort'][] = array('last_name' => 'asc');
											$retval['sort'][] = array('first_name' => 'asc');
											break;
										case 'by_pay_period_by_date_stamp_by_employee':
											$retval['columns'][] = 'pay_period';
											$retval['columns'][] = 'date_stamp';
											$retval['columns'][] = 'first_name';
											$retval['columns'][] = 'last_name';

											$retval['group'][] = 'pay_period';
											$retval['group'][] = 'date_stamp';
											$retval['group'][] = 'first_name';
											$retval['group'][] = 'last_name';

											$retval['sub_total'][] = 'pay_period';
											$retval['sub_total'][] = 'date_stamp';

											$retval['sort'][] = array('pay_period' => 'asc');
											$retval['sort'][] = array('date_stamp' => 'asc');
											$retval['sort'][] = array('last_name' => 'asc');
											$retval['sort'][] = array('first_name' => 'asc');
											break;
										case 'by_pay_period_by_branch':
											$retval['columns'][] = 'pay_period';
											$retval['columns'][] = 'branch';

											$retval['group'][] = 'pay_period';
											$retval['group'][] = 'branch';

											$retval['sub_total'][] = 'pay_period';

											$retval['sort'][] = array('pay_period' => 'asc');
											$retval['sort'][] = array('branch' => 'asc');
											break;
										case 'by_pay_period_by_department':
											$retval['columns'][] = 'pay_period';
											$retval['columns'][] = 'department';

											$retval['group'][] = 'pay_period';
											$retval['group'][] = 'department';

											$retval['sub_total'][] = 'pay_period';

											$retval['sort'][] = array('pay_period' => 'asc');
											$retval['sort'][] = array('department' => 'asc');
											break;
										case 'by_pay_period_by_branch_by_department':
											$retval['columns'][] = 'pay_period';
											$retval['columns'][] = 'branch';
											$retval['columns'][] = 'department';

											$retval['group'][] = 'pay_period';
											$retval['group'][] = 'branch';
											$retval['group'][] = 'department';

											$retval['sub_total'][] = 'pay_period';
											$retval['sub_total'][] = 'branch';

											$retval['sort'][] = array('pay_period' => 'asc');
											$retval['sort'][] = array('branch' => 'asc');
											$retval['sort'][] = array('department' => 'asc');
											break;
										case 'by_employee_by_pay_period':
											$retval['columns'][] = 'full_name';
											$retval['columns'][] = 'pay_period';

											$retval['group'][] = 'full_name';
											$retval['group'][] = 'pay_period';

											$retval['sub_total'][] = 'full_name';

											$retval['sort'][] = array('full_name' => 'asc');
											$retval['sort'][] = array('pay_period' => 'asc');
											break;
										case 'by_branch_by_pay_period':
											$retval['columns'][] = 'branch';
											$retval['columns'][] = 'pay_period';

											$retval['group'][] = 'branch';
											$retval['group'][] = 'pay_period';

											$retval['sub_total'][] = 'branch';

											$retval['sort'][] = array('branch' => 'asc');
											$retval['sort'][] = array('pay_period' => 'asc');
											break;
										case 'by_department_by_pay_period':
											$retval['columns'][] = 'department';
											$retval['columns'][] = 'pay_period';

											$retval['group'][] = 'department';
											$retval['group'][] = 'pay_period';

											$retval['sub_total'][] = 'department';

											$retval['sort'][] = array('department' => 'asc');
											$retval['sort'][] = array('pay_period' => 'asc');
											break;
										case 'by_branch_by_department_by_pay_period':
											$retval['columns'][] = 'branch';
											$retval['columns'][] = 'department';
											$retval['columns'][] = 'pay_period';

											$retval['group'][] = 'branch';
											$retval['group'][] = 'department';
											$retval['group'][] = 'pay_period';

											$retval['sub_total'][] = 'branch';
											$retval['sub_total'][] = 'department';

											$retval['sort'][] = array('branch' => 'asc');
											$retval['sort'][] = array('department' => 'asc');
											$retval['sort'][] = array('pay_period' => 'asc');
											break;
										case 'by_date_by_full_name':
											$retval['columns'][] = 'date_stamp';
											$retval['columns'][] = 'full_name';

											$retval['group'][] = 'date_stamp';
											$retval['group'][] = 'full_name';

											$retval['sub_total'][] = 'date_stamp';

											$retval['sort'][] = array('date_stamp' => 'asc');
											$retval['sort'][] = array('full_name' => 'asc');
											break;
										case 'by_full_name_by_date':
											$retval['columns'][] = 'full_name';
											$retval['columns'][] = 'date_stamp';

											$retval['group'][] = 'full_name';
											$retval['group'][] = 'date_stamp';

											$retval['sub_total'][] = 'full_name';

											$retval['sort'][] = array('full_name' => 'asc');
											$retval['sort'][] = array('date_stamp' => 'asc');
											break;
										case 'by_full_name_by_dow':
											$retval['columns'][] = 'full_name';
											$retval['columns'][] = 'date_dow';

											$retval['group'][] = 'full_name';
											$retval['group'][] = 'date_dow';

											$retval['sub_total'][] = 'full_name';

											$retval['sort'][] = array('full_name' => 'asc');
											$retval['sort'][] = array('date_dow' => 'asc');
											break;
										case 'by_date_by_worked_hour_of_day':
											$retval['columns'][] = 'date_stamp';
											$retval['columns'][] = 'worked_hour_of_day';
											$retval['columns'][] = 'worked_hour_of_day_total';

											$retval['group'][] = 'date_stamp';
											$retval['group'][] = 'worked_hour_of_day';

											$retval['sub_total'][] = 'date_stamp';

											$retval['sort'][] = array('date_stamp' => 'asc');
											$retval['sort'][] = array('worked_hour_of_day' => 'asc');
											break;
										case 'by_date_dow_by_worked_hour_of_day':
											$retval['columns'][] = 'date_dow';
											$retval['columns'][] = 'worked_hour_of_day';
											$retval['columns'][] = 'worked_hour_of_day_total';

											$retval['group'][] = 'date_dow';
											$retval['group'][] = 'worked_hour_of_day';

											$retval['sub_total'][] = 'date_dow';

											$retval['sort'][] = array('date_dow' => 'asc');
											$retval['sort'][] = array('worked_hour_of_day' => 'asc');
											break;

									}
								}
							}
							break;
					}
				}

				//Set the template dropdown as well.
				$retval['-1000-template'] = $template;

				//Add sort prefixes so Flex can maintain order.
				if ( isset($retval['filter']) ) {
					$retval['-5000-filter'] = $retval['filter'];
					unset($retval['filter']);
				}
				if ( isset($retval['columns']) ) {
					$retval['-5010-columns'] = $retval['columns'];
					unset($retval['columns']);
				}
				if ( isset($retval['group']) ) {
					$retval['-5020-group'] = $retval['group'];
					unset($retval['group']);
				}
				if ( isset($retval['sub_total']) ) {
					$retval['-5030-sub_total'] = $retval['sub_total'];
					unset($retval['sub_total']);
				}
				if ( isset($retval['sort']) ) {
					$retval['-5040-sort'] = $retval['sort'];
					unset($retval['sort']);
				}
				Debug::Arr($retval, ' Template Config for: '. $template, __FILE__, __LINE__, __METHOD__, 10);

				break;
			default:
				//Call report parent class options function for options valid for all reports.
				$retval = $this->__getOptions( $name );
				break;
		}

		return $retval;
	}

	//This function takes worked time for a single day and multiplies it by each hour worked.
	function splitDataByHoursWorked( $row, $dynamic_columns ) {
		$retval = array();
		if ( isset($row['min_punch_time_stamp']) AND isset($row['max_punch_time_stamp']) AND $row['min_punch_time_stamp'] > 0 AND $row['max_punch_time_stamp'] > 0 ) {
			$total_hours = ( ( $row['max_punch_time_stamp'] - $row['min_punch_time_stamp'] ) / 3600 );
			if ( $total_hours == 0 ) {
				$total_hours = 1;
			}

			$start_time = TTDate::roundTime( $row['min_punch_time_stamp'], 3600, 10 );
			//If the employee punches out exact at 5:00PM, minus 1 second from that time so its recorded as an hour for 4:00PM and not 5:00PM.
			$end_time = TTDate::roundTime( ($row['max_punch_time_stamp'] - 1), 3600, 10 );

			//Debug::Text('Total Hours: '. $total_hours .' Start Time: '. TTDate::getDATE('DATE+TIME', $start_time ) .' End Time: '. TTDate::getDATE('DATE+TIME', $end_time ), __FILE__, __LINE__, __METHOD__, 10);
			$x = 0;
			for( $i = $start_time; $i <= $end_time; $i += 3600 ) {
				//Debug::Text('Hour: '. TTDate::getDate('DATE+TIME', $i ) .'('. $i .') Total Hours: '. $total_hours, __FILE__, __LINE__, __METHOD__, 10);
				$retval[$i]['worked_hour_of_day'] = $i;

				/*
				//Handle partial hours. Though we don't need to do this as we track the number of hours worked per hour as well, so that gives us man hours.
				if ( $row['min_punch_time_stamp'] > $i AND ( $row['min_punch_time_stamp'] - $i ) < 3600 ) {
					$retval[$i]['worked_hour_of_day_total'] = ( TTDate::roundTime( ( $row['min_punch_time_stamp'] - $i ), 900, 10 ) / 3600 );
				} elseif( $row['max_punch_time_stamp'] > $i AND ( $row['max_punch_time_stamp'] - $i ) < 3600 ) {
					$retval[$i]['worked_hour_of_day_total'] = ( TTDate::roundTime( ( $row['max_punch_time_stamp'] - $i ), 900, 10 ) / 3600 );
				} else {
					$retval[$i]['worked_hour_of_day_total'] = 1;
				}
				*/
				$retval[$i]['worked_hour_of_day_total'] = 1.00;

				foreach( $row as $column => $value ) {
					if ( isset( $dynamic_columns[$column] ) AND is_numeric($value) AND !in_array( $column, array('min_punch_time_stamp', 'max_punch_time_stamp') ) ) {
						$retval[$i][$column] = ( $value / $total_hours );
					} else {
						$retval[$i][$column] = $value;
					}
				}

				$x++;
			}
		}

		if ( !isset($retval) ) {
			$retval[0] = $row;
		}

		return $retval;
	}

	//Get raw data for report
	function _getData( $format = NULL ) {
		$this->tmp_data = array('user_date_total' => array(), 'schedule' => array(), 'worked_days' => array(), 'user' => array(), 'user_title' => array(), 'timesheet_authorization' => array(), 'verified_timesheet' => array(), 'punch_rows' => array(), 'punch_control_rows' => array(), 'pay_period_schedule' => array(), 'pay_period' => array() );

		$columns = $this->getColumnDataConfig();
		$filter_data = $this->getFilterConfig();

		$currency_convert_to_base = $this->getCurrencyConvertToBase();
		$base_currency_obj = $this->getBaseCurrencyObject();
		$this->handleReportCurrency( $currency_convert_to_base, $base_currency_obj, $filter_data );
		$currency_options = $this->getOptions('currency');

		$filter_data['permission_children_ids'] = $this->getPermissionObject()->getPermissionChildren( 'punch', 'view', $this->getUserObject()->getID(), $this->getUserObject()->getCompany() );
		$wage_permission_children_ids = $this->getPermissionObject()->getPermissionChildren( 'wage', 'view', $this->getUserObject()->getID(), $this->getUserObject()->getCompany() );

		$pay_period_ids = array();

		if ( isset($columns['udt_note']) AND $columns['udt_note'] == TRUE ) {
			//Get punch notes to append to UDT records below.
			$punch_control_filter_data = $filter_data;
			$punch_control_filter_data['has_note'] = TRUE;
			$pclf = TTnew( 'PunchControlListFactory' );
			$pclf->getAPISearchByCompanyIdAndArrayCriteria( $this->getUserObject()->getCompany(), $punch_control_filter_data);
			Debug::Text('Got punch control data... Total Rows: '. $pclf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
			$this->getProgressBarObject()->start( $this->getAMFMessageID(), $pclf->getRecordCount(), NULL, TTi18n::getText('Retrieving Punch Notes Data...') );
			if ( $pclf->getRecordCount() > 0 ) {
				foreach( $pclf as $key => $pc_obj ) {
					if ( $pc_obj->getNote() != '' ) {
						if ( isset( $this->tmp_data['punch_control_rows'][(int)$pc_obj->getColumn( 'user_id' )][(int)TTDate::strtotime( $pc_obj->getColumn( 'date_stamp' ) )][(int)$pc_obj->getBranch()][(int)$pc_obj->getDepartment()]['udt_note'] ) ) {
							$this->tmp_data['punch_control_rows'][(int)$pc_obj->getColumn( 'user_id' )][(int)TTDate::strtotime( $pc_obj->getColumn( 'date_stamp' ) )][(int)$pc_obj->getBranch()][(int)$pc_obj->getDepartment()]['udt_note'] .= ' -- ' . $pc_obj->getNote();
						} else {
							$this->tmp_data['punch_control_rows'][(int)$pc_obj->getColumn( 'user_id' )][(int)TTDate::strtotime( $pc_obj->getColumn( 'date_stamp' ) )][(int)$pc_obj->getBranch()][(int)$pc_obj->getDepartment()]['udt_note'] = $pc_obj->getNote();
						}
					}
					$this->getProgressBarObject()->set( $this->getAMFMessageID(), $key );
				}
			}
			unset($pclf, $pc_obj, $punch_control_filter_data);
			//Debug::Arr($this->tmp_data['punch_control_rows'], ' Punch Control Data: ', __FILE__, __LINE__, __METHOD__, 10);
		}

		$udtlf = TTnew( 'UserDateTotalListFactory' );
		$udtlf->getTimesheetDetailReportByCompanyIdAndArrayCriteria( $this->getUserObject()->getCompany(), $filter_data );
		Debug::Text(' Total Rows: '. $udtlf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
		$this->getProgressBarObject()->start( $this->getAMFMessageID(), $udtlf->getRecordCount(), NULL, TTi18n::getText('Retrieving Data...') );
		$include_no_data_rows_arr = array();
		if ( $udtlf->getRecordCount() > 0 ) {
			foreach ( $udtlf as $key => $udt_obj ) {
				$pay_period_ids[$udt_obj->getColumn('pay_period_id')] = TRUE;

				$user_id = (int)$udt_obj->getColumn('user_id');
				$date_stamp = $udt_obj->getDateStamp();
				$branch_id = (int)$udt_obj->getColumn('branch_id');
				$department_id = (int)$udt_obj->getColumn('department_id');
				$currency_rate = $udt_obj->getColumn('currency_rate');
				$currency_id = (int)$udt_obj->getColumn('currency_id');

				//With pay codes, paid time makes sense now and is associated with branch/departments too.
				$time_columns = $udt_obj->getTimeCategory( FALSE, $columns  ); //Exclude 'total' as its not used in reports anyways, and causes problems when grouping by branch/default branch.

				//Debug::Text('Column: '. $column .' Total Time: '. $udt_obj->getColumn('total_time') .' Status: '. $status_id .' Type: '. $type_id .' Rate: '. $udt_obj->getColumn( 'hourly_rate' ), __FILE__, __LINE__, __METHOD__, 10);
				if ( ( isset($filter_data['include_no_data_rows']) AND $filter_data['include_no_data_rows'] == 1 )
					OR ( ( !isset($filter_data['include_no_data_rows']) OR ( isset($filter_data['include_no_data_rows']) AND $filter_data['include_no_data_rows'] == 0 ) ) AND $date_stamp != '' AND count($time_columns) > 0 AND $udt_obj->getColumn('total_time') != 0 )	 ) {

					$enable_wages = $this->getPermissionObject()->isPermissionChild( $user_id, $wage_permission_children_ids );

					//Split time by user, date, branch, department as that is the lowest level we can split time.
					//We always need to split time as much as possible as it can always be combined together by grouping.
					if ( strpos($format, 'pdf_') !== FALSE ) {
						if ( !isset($this->form_data['user_date_total'][$user_id]['data'][$date_stamp]) ) {
							$this->form_data['user_date_total'][$user_id]['data'][$date_stamp] = array(
																'branch_id' => $udt_obj->getColumn('branch_id'),
																'department_id' => $udt_obj->getColumn('department_id'),
																'pay_period_start_date' => strtotime( $udt_obj->getColumn('pay_period_start_date') ),
																'pay_period_end_date' => strtotime( $udt_obj->getColumn('pay_period_end_date') ),
																'pay_period_transaction_date' => strtotime( $udt_obj->getColumn('pay_period_transaction_date') ),
																'pay_period' => strtotime( $udt_obj->getColumn('pay_period_transaction_date') ),
																'pay_period_id' => $udt_obj->getColumn('pay_period_id'),

																//Normalize the timestamps to the same day, otherwise min/max aggregates will always use what times are on the first/last days.
																'min_punch_time_stamp' => ( $udt_obj->getObjectType() == 10 AND $udt_obj->getColumn('start_time_stamp') != '' ) ? strtotime( $udt_obj->getColumn('start_time_stamp') ) : NULL,
																'max_punch_time_stamp' => ( $udt_obj->getObjectType() == 10 AND $udt_obj->getColumn('end_time_stamp') != '' ) ? strtotime( $udt_obj->getColumn('end_time_stamp') ) : NULL,

																'min_schedule_time_stamp' => NULL,
																'max_schedule_time_stamp' => NULL,
																);
						} else {
							if ( $udt_obj->getObjectType() == 10 AND ( $this->form_data['user_date_total'][$user_id]['data'][$date_stamp]['min_punch_time_stamp'] == '' OR strtotime( $udt_obj->getColumn('start_time_stamp') ) < $this->form_data['user_date_total'][$user_id]['data'][$date_stamp]['min_punch_time_stamp'] ) ) {
								$this->form_data['user_date_total'][$user_id]['data'][$date_stamp]['min_punch_time_stamp'] = strtotime( $udt_obj->getColumn('start_time_stamp') );
							}
							if ( $udt_obj->getObjectType() == 10 AND ( $this->form_data['user_date_total'][$user_id]['data'][$date_stamp]['max_punch_time_stamp'] == '' OR strtotime( $udt_obj->getColumn('end_time_stamp') ) > $this->form_data['user_date_total'][$user_id]['data'][$date_stamp]['max_punch_time_stamp'] ) ) {
								$this->form_data['user_date_total'][$user_id]['data'][$date_stamp]['max_punch_time_stamp'] = strtotime( $udt_obj->getColumn('end_time_stamp') );
							}
						}
					}

					if ( !isset($this->tmp_data['user_date_total'][$user_id][$date_stamp][$branch_id][$department_id]) ) {
						$this->tmp_data['user_date_total'][$user_id][$date_stamp][$branch_id][$department_id] = array(
															'branch_id' => $udt_obj->getColumn('branch_id'),
															'department_id' => $udt_obj->getColumn('department_id'),
															'pay_period_start_date' => strtotime( $udt_obj->getColumn('pay_period_start_date') ),
															'pay_period_end_date' => strtotime( $udt_obj->getColumn('pay_period_end_date') ),
															'pay_period_transaction_date' => strtotime( $udt_obj->getColumn('pay_period_transaction_date') ),
															'pay_period' => strtotime( $udt_obj->getColumn('pay_period_transaction_date') ),
															'pay_period_id' => $udt_obj->getColumn('pay_period_id'),

															//Normalize the timestamps to the same day, otherwise min/max aggregates will always use what times are on the first/last days.
															'min_punch_time_stamp' => ( $udt_obj->getObjectType() == 10 AND $udt_obj->getColumn('start_time_stamp') != '' ) ? TTDate::getTimeLockedDate( strtotime( $udt_obj->getColumn('start_time_stamp') ), 86400) : NULL,
															'max_punch_time_stamp' => ( $udt_obj->getObjectType() == 10 AND $udt_obj->getColumn('end_time_stamp') != '' ) ? TTDate::getTimeLockedDate( strtotime( $udt_obj->getColumn('end_time_stamp') ), 86400) : NULL,

															'min_schedule_time_stamp' => NULL,
															'max_schedule_time_stamp' => NULL,
															);

						if ( !isset($include_no_data_rows_arr[$udt_obj->getColumn('pay_period_id')][$date_stamp]) ) {
							$include_no_data_rows_arr[$udt_obj->getColumn('pay_period_id')][$date_stamp] = array(
																			'branch_id' => 0,
																			'department_id' => 0,
																			'pay_period_start_date' => strtotime( $udt_obj->getColumn('pay_period_start_date') ),
																			'pay_period_end_date' => strtotime( $udt_obj->getColumn('pay_period_end_date') ),
																			'pay_period_transaction_date' => strtotime( $udt_obj->getColumn('pay_period_transaction_date') ),
																			'pay_period' => strtotime( $udt_obj->getColumn('pay_period_transaction_date') ),
																			'pay_period_id' => $udt_obj->getColumn('pay_period_id'),

																			'min_punch_time_stamp' => NULL,
																			'max_punch_time_stamp' => NULL,

																			'min_schedule_time_stamp' => NULL,
																			'max_schedule_time_stamp' => NULL,
																		);
						}
					} else {
						if ( $udt_obj->getObjectType() == 10 AND ( $this->tmp_data['user_date_total'][$user_id][$date_stamp][$branch_id][$department_id]['min_punch_time_stamp'] == '' OR TTDate::getTimeLockedDate( strtotime( $udt_obj->getColumn('start_time_stamp') ), 86400 ) < $this->tmp_data['user_date_total'][$user_id][$date_stamp][$branch_id][$department_id]['min_punch_time_stamp'] ) ) {
							$this->tmp_data['user_date_total'][$user_id][$date_stamp][$branch_id][$department_id]['min_punch_time_stamp'] = TTDate::getTimeLockedDate( strtotime( $udt_obj->getColumn('start_time_stamp') ), 86400 );
						}
						if ( $udt_obj->getObjectType() == 10 AND ( $this->tmp_data['user_date_total'][$user_id][$date_stamp][$branch_id][$department_id]['max_punch_time_stamp'] == '' OR TTDate::getTimeLockedDate( strtotime( $udt_obj->getColumn('end_time_stamp') ), 86400 ) > $this->tmp_data['user_date_total'][$user_id][$date_stamp][$branch_id][$department_id]['max_punch_time_stamp'] ) ) {
							$this->tmp_data['user_date_total'][$user_id][$date_stamp][$branch_id][$department_id]['max_punch_time_stamp'] = TTDate::getTimeLockedDate( strtotime( $udt_obj->getColumn('end_time_stamp') ), 86400 );
						}
					}


					if ( !isset($this->tmp_data['user_date_total'][$user_id][$date_stamp][$branch_id][$department_id]['udt_note']) ) {
						$this->tmp_data['user_date_total'][$user_id][$date_stamp][$branch_id][$department_id]['udt_note'] = '';
					}

					if ( isset($this->tmp_data['punch_control_rows'][$user_id][$date_stamp][$branch_id][$department_id]['udt_note']) ) {
						if ( $this->tmp_data['user_date_total'][$user_id][$date_stamp][$branch_id][$department_id]['udt_note'] != '' ) {
							$this->tmp_data['user_date_total'][$user_id][$date_stamp][$branch_id][$department_id]['udt_note'] .= ' -- '; //Note delimiter
						}

						$this->tmp_data['user_date_total'][$user_id][$date_stamp][$branch_id][$department_id]['udt_note'] .= $this->tmp_data['punch_control_rows'][$user_id][$date_stamp][$branch_id][$department_id]['udt_note'];
						unset($this->tmp_data['punch_control_rows'][$user_id][$date_stamp][$branch_id][$department_id]['udt_note']); //Make sure we don't use it twice.
					}

					if ( $udt_obj->getColumn('udt_note') != '' ) {
						if ( $this->tmp_data['user_date_total'][$user_id][$date_stamp][$branch_id][$department_id]['udt_note'] != '' ) {
							$this->tmp_data['user_date_total'][$user_id][$date_stamp][$branch_id][$department_id]['udt_note'] .= ' -- '; //Note delimiter
						}
						$this->tmp_data['user_date_total'][$user_id][$date_stamp][$branch_id][$department_id]['udt_note'] .= $udt_obj->getColumn( 'udt_note' );
					}

					if ( $udt_obj->getPayCode() > 0 ) { //Make sure we don't set the currency based on worked_time that will always be currency_id=0 and have no pay_code_id associated.
						$this->tmp_data['user_date_total'][$user_id][$date_stamp][$branch_id][$department_id]['currency_rate'] = $currency_rate;
						$this->tmp_data['user_date_total'][$user_id][$date_stamp][$branch_id][$department_id]['currency'] = $this->tmp_data['user_date_total'][$user_id][$date_stamp][$branch_id][$department_id]['current_currency'] = Option::getByKey( $currency_id, $currency_options );
						if ( $currency_convert_to_base == TRUE AND is_object( $base_currency_obj ) ) {
							$this->tmp_data['user_date_total'][$user_id][$date_stamp][$branch_id][$department_id]['current_currency'] = Option::getByKey( $base_currency_obj->getId(), $currency_options );
						}
					}

					foreach( $time_columns as $column ) {
						//Debug::Text('bColumn: '. $column .' Total Time: '. $udt_obj->getColumn('total_time') .' Object Type ID: '. $udt_obj->getColumn('object_type_id') .' Rate: '. $udt_obj->getColumn( 'hourly_rate' ), __FILE__, __LINE__, __METHOD__, 10);

						//
						//Handle data for PDF timesheet. Don't split it out by branch/department
						//	as that causes multiple rows per day to display.
						//
						if ( strpos($format, 'pdf_') !== FALSE ) {
							if ( isset($this->form_data['user_date_total'][$user_id]['data'][$date_stamp][$column.'_time']) ) {
								$this->form_data['user_date_total'][$user_id]['data'][$date_stamp][$column.'_time'] = bcadd( $this->form_data['user_date_total'][$user_id]['data'][$date_stamp][$column.'_time'], $udt_obj->getColumn('total_time') );
							} else {
								$this->form_data['user_date_total'][$user_id]['data'][$date_stamp][$column.'_time'] = $udt_obj->getColumn('total_time');
							}

							if ( $udt_obj->getObjectType() == 20 AND strpos( $column, 'pay_code-' ) !== FALSE ) { //Regular
								$this->form_data['user_date_total'][$user_id]['data'][$date_stamp]['categorized_time']['regular_time_policy'][$column.'_time'] = TRUE;
							} elseif ( $udt_obj->getObjectType() == 25 AND strpos( $column, 'pay_code-' ) !== FALSE ) { //Absence
								$this->form_data['user_date_total'][$user_id]['data'][$date_stamp]['categorized_time']['absence_policy'][$column.'_time'] = TRUE;
							} elseif ( $udt_obj->getObjectType() == 30 AND strpos( $column, 'pay_code-' ) !== FALSE ) { //Overtime
								$this->form_data['user_date_total'][$user_id]['data'][$date_stamp]['categorized_time']['over_time_policy'][$column.'_time'] = TRUE;
							}
						} else {
							if ( isset($this->tmp_data['user_date_total'][$user_id][$date_stamp][$branch_id][$department_id][$column.'_time']) ) {
								$this->tmp_data['user_date_total'][$user_id][$date_stamp][$branch_id][$department_id][$column.'_time'] = bcadd( $this->tmp_data['user_date_total'][$user_id][$date_stamp][$branch_id][$department_id][$column.'_time'], $udt_obj->getColumn('total_time') );
							} else {
								$this->tmp_data['user_date_total'][$user_id][$date_stamp][$branch_id][$department_id][$column.'_time'] = $udt_obj->getColumn('total_time');
							}

							//Gross wage (paid_wage) calculation must go here otherwise it gets doubled up.
							//Worked Time is required for printable TimeSheets. Therefore this report is handled differently from TimeSheetSummary.
							if ( $enable_wages == TRUE AND !in_array( $column, array('total','worked') ) AND ( $udt_obj->getColumn('total_time_amount') != 0 OR $udt_obj->getColumn('total_time_amount_with_burden') != 0 ) ) { //Exclude worked time from gross wage total.
								if ( isset($this->tmp_data['user_date_total'][$user_id][$date_stamp][$branch_id][$department_id][$column.'_wage']) ) {
									$this->tmp_data['user_date_total'][$user_id][$date_stamp][$branch_id][$department_id][$column.'_wage'] = bcadd( $this->tmp_data['user_date_total'][$user_id][$date_stamp][$branch_id][$department_id][$column.'_wage'], $udt_obj->getColumn('total_time_amount') );
									$this->tmp_data['user_date_total'][$user_id][$date_stamp][$branch_id][$department_id][$column.'_wage_with_burden'] = bcadd( $this->tmp_data['user_date_total'][$user_id][$date_stamp][$branch_id][$department_id][$column.'_wage_with_burden'], $udt_obj->getColumn('total_time_amount_with_burden') );
								} else {
									$this->tmp_data['user_date_total'][$user_id][$date_stamp][$branch_id][$department_id][$column.'_wage'] = $udt_obj->getColumn('total_time_amount');
									$this->tmp_data['user_date_total'][$user_id][$date_stamp][$branch_id][$department_id][$column.'_wage_with_burden'] = $udt_obj->getColumn('total_time_amount_with_burden');
								}

								if ( isset($this->tmp_data['user_date_total'][$user_id][$date_stamp][$branch_id][$department_id][$column.'_hourly_rate']) AND $this->tmp_data['user_date_total'][$user_id][$date_stamp][$branch_id][$department_id][$column.'_wage'] != 0 AND $this->tmp_data['user_date_total'][$user_id][$date_stamp][$branch_id][$department_id][$column.'_time'] != 0 ) {
									$this->tmp_data['user_date_total'][$user_id][$date_stamp][$branch_id][$department_id][$column.'_hourly_rate'] = bcdiv($this->tmp_data['user_date_total'][$user_id][$date_stamp][$branch_id][$department_id][$column.'_wage'], TTDate::getHours($this->tmp_data['user_date_total'][$user_id][$date_stamp][$branch_id][$department_id][$column.'_time']) );
									$this->tmp_data['user_date_total'][$user_id][$date_stamp][$branch_id][$department_id][$column.'_hourly_rate_with_burden'] = bcdiv($this->tmp_data['user_date_total'][$user_id][$date_stamp][$branch_id][$department_id][$column.'_wage_with_burden'], TTDate::getHours($this->tmp_data['user_date_total'][$user_id][$date_stamp][$branch_id][$department_id][$column.'_time']) );
								} else {
									$this->tmp_data['user_date_total'][$user_id][$date_stamp][$branch_id][$department_id][$column.'_hourly_rate'] = $udt_obj->getColumn( 'hourly_rate' );
									$this->tmp_data['user_date_total'][$user_id][$date_stamp][$branch_id][$department_id][$column.'_hourly_rate_with_burden'] = $udt_obj->getColumn( 'hourly_rate_with_burden' );
								}
							}

							//Worked Days is tricky, since if they worked in multiple branches/departments in a single day, is that considered one worked day?
							//How do they find out how many days they worked in each branch/department though? It would add up to more days than they actually worked.
							//If we did some sort of partial day though, then due to rounding it could be thrown off, but either way it woulnd't be that helpful because
							//it would show they worked .33 of a day in one branch if they filtered by that branch.
							if ( $column == 'worked' AND $udt_obj->getColumn('total_time') > 0 AND !isset($this->tmp_data['worked_days'][$user_id.$date_stamp]) ) {
								$this->tmp_data['user_date_total'][$user_id][$date_stamp][$branch_id][$department_id]['worked_days'] = 1;
								$this->tmp_data['worked_days'][$user_id.$date_stamp] = TRUE;
							}
						}
					}
				}

				$this->getProgressBarObject()->set( $this->getAMFMessageID(), $key );
			}
			unset( $udt_obj, $user_id, $date_stamp, $branch_id, $department_id, $currency_rate, $currency_id, $time_columns);
		}
		//Debug::Arr($this->tmp_data['user_date_total'], 'User Date Total Raw Data: ', __FILE__, __LINE__, __METHOD__, 10);

		if ( strpos($format, 'pdf_') === FALSE AND ( isset($columns['schedule_working']) OR isset($columns['schedule_working_diff']) OR isset($columns['schedule_absence']) ) ) {
			$slf = TTnew( 'ScheduleListFactory' );
			//$slf->getDayReportByCompanyIdAndArrayCriteria( $this->getUserObject()->getCompany(), $filter_data );
			//$slf->getScheduleSummaryReportByCompanyIdAndArrayCriteria( $this->getUserObject()->getCompany(), $filter_data );
			$slf->getSearchByCompanyIdAndArrayCriteria( $this->getUserObject()->getCompany(), $filter_data );
			if ( $slf->getRecordCount() > 0 ) {
				foreach($slf as $s_obj) {
					$user_id = (int)$s_obj->getUser();
					$date_stamp = $s_obj->getDateStamp();
					$branch_id = (int)$s_obj->getColumn('branch_id');
					$department_id = (int)$s_obj->getColumn('department_id');

					$status = strtolower( Option::getByKey($s_obj->getColumn('status_id'), $s_obj->getOptions('status') ) );

					//Check if the user worked on any of the scheduled days, if not insert a dummy day so the scheduled time at least appears still.
					if ( !isset($this->tmp_data['user_date_total'][$user_id][$date_stamp][$branch_id][$department_id]) ) {
						$this->tmp_data['user_date_total'][$user_id][$date_stamp][$branch_id][$department_id] = array(
							'branch' => $s_obj->getColumn('branch'),
							'department' => $s_obj->getColumn('department'),
							'pay_period_start_date' => strtotime( $s_obj->getColumn('pay_period_start_date') ),
							'pay_period_end_date' => strtotime( $s_obj->getColumn('pay_period_end_date') ),
							'pay_period_transaction_date' => strtotime( $s_obj->getColumn('pay_period_transaction_date') ),
							'pay_period' => strtotime( $s_obj->getColumn('pay_period_transaction_date') ),
							'pay_period_id' => $s_obj->getColumn('pay_period_id'),

							'min_punch_time_stamp' => NULL,
							'max_punch_time_stamp' => NULL,

							//Normalize the timestamps to the same day, otherwise min/max aggregates will always use what times are on the first/last days.
							'min_schedule_time_stamp' => ( $s_obj->getColumn('status_id') == 10 AND $s_obj->getColumn('start_time') != '' ) ? TTDate::getTimeLockedDate( strtotime( $s_obj->getColumn('start_time') ), 86400) : NULL,
							'max_schedule_time_stamp' => ( $s_obj->getColumn('status_id') == 10 AND $s_obj->getColumn('end_time') != '' ) ? TTDate::getTimeLockedDate( strtotime( $s_obj->getColumn('end_time') ), 86400) : NULL,

							);
					} else {
						if ( $s_obj->getColumn('status_id') == 10 AND ( $this->tmp_data['user_date_total'][$user_id][$date_stamp][$branch_id][$department_id]['min_schedule_time_stamp'] == '' OR TTDate::getTimeLockedDate( strtotime( $s_obj->getColumn('start_time') ), 86400 ) < $this->tmp_data['user_date_total'][$user_id][$date_stamp][$branch_id][$department_id]['min_schedule_time_stamp'] ) ) {
							$this->tmp_data['user_date_total'][$user_id][$date_stamp][$branch_id][$department_id]['min_schedule_time_stamp'] = TTDate::getTimeLockedDate( strtotime( $s_obj->getColumn('start_time') ), 86400 );
						}
						if ( $s_obj->getColumn('status_id') == 10 AND ( $this->tmp_data['user_date_total'][$user_id][$date_stamp][$branch_id][$department_id]['max_schedule_time_stamp'] == '' OR TTDate::getTimeLockedDate( strtotime( $s_obj->getColumn('end_time') ), 86400 ) > $this->tmp_data['user_date_total'][$user_id][$date_stamp][$branch_id][$department_id]['max_schedule_time_stamp'] ) ) {
							$this->tmp_data['user_date_total'][$user_id][$date_stamp][$branch_id][$department_id]['max_schedule_time_stamp'] = TTDate::getTimeLockedDate( strtotime( $s_obj->getColumn('end_time') ), 86400 );
						}
					}

					//Make sure we handle multiple schedules on the same day.
					if ( isset($this->tmp_data['schedule'][$user_id][$date_stamp][$branch_id][$department_id]['schedule_'.$status]) ) {
						$this->tmp_data['schedule'][$user_id][$date_stamp][$branch_id][$department_id]['schedule_'.$status] = bcadd( $this->tmp_data['schedule'][$user_id][$date_stamp][$branch_id][$department_id]['schedule_'.$status], $s_obj->getColumn('total_time') );
					} else {
						$this->tmp_data['schedule'][$user_id][$date_stamp][$branch_id][$department_id]['schedule_'.$status] = $s_obj->getColumn('total_time');
					}
				}
				unset( $user_id, $date_stamp, $branch_id, $department_id);

			}
			//Debug::Arr($this->tmp_data['schedule'], 'Schedule Raw Data: ', __FILE__, __LINE__, __METHOD__, 10);
			unset($slf, $s_obj, $status);
		}

		//Get user data for joining.
		$ulf = TTnew( 'UserListFactory' );
		$ulf->getAPISearchByCompanyIdAndArrayCriteria( $this->getUserObject()->getCompany(), $filter_data );
		Debug::Text(' User Total Rows: '. $ulf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
		$this->getProgressBarObject()->start( $this->getAMFMessageID(), $ulf->getRecordCount(), NULL, TTi18n::getText('Retrieving Data...') );
		foreach ( $ulf as $key => $u_obj ) {
			$this->tmp_data['user'][$u_obj->getId()] = (array)$u_obj->getObjectAsArray( array_merge( (array)$this->getColumnDataConfig(), array( 'province' => TRUE, 'hire_date' => TRUE, 'termination_date' => TRUE , 'title_id' => TRUE ) ) );
			$this->tmp_data['user'][$u_obj->getId()]['user_province'] = $this->tmp_data['user'][$u_obj->getId()]['province']; //Used in Payroll Export for PBJ.

			if ( $currency_convert_to_base == TRUE AND is_object( $base_currency_obj ) ) {
				$this->tmp_data['user'][$u_obj->getId()]['current_currency'] = Option::getByKey( $base_currency_obj->getId(), $currency_options );
				$this->tmp_data['user'][$u_obj->getId()]['currency_rate'] = $u_obj->getColumn('currency_rate');
			} else {
				$this->tmp_data['user'][$u_obj->getId()]['current_currency'] = $u_obj->getColumn('currency');
			}

			if ( strpos($format, 'pdf_') !== FALSE ) {
				if ( !isset($this->form_data['user_date_total'][$u_obj->getId()]) ) {
					$this->form_data['user_date_total'][$u_obj->getId()] = array();
				}
				//Make sure we merge this array with existing data and include all required fields for generating timesheets. This prevents slow columns from being returned.
				$this->form_data['user_date_total'][$u_obj->getId()] += (array)$u_obj->getObjectAsArray( array('first_name' => TRUE, 'last_name' => TRUE, 'employee_number' => TRUE, 'title' => TRUE, 'group' => TRUE, 'default_branch' => TRUE, 'default_department' => TRUE ) );
			}

			//User doesn't have any UDT rows, add a blank one.
			if ( ( isset($filter_data['include_no_data_rows']) AND $filter_data['include_no_data_rows'] == 1 AND !isset($this->tmp_data['user_date_total'][$u_obj->getId()]) ) ) {
				foreach( $include_no_data_rows_arr as $tmp_pay_period_id => $tmp_date_stamps ) {
					foreach( $tmp_date_stamps as $tmp_date_stamp => $tmp_pay_period_data ) {
						$this->tmp_data['user_date_total'][$u_obj->getId()][$tmp_date_stamp][0][0] = $tmp_pay_period_data;
						if ( strpos($format, 'pdf_') !== FALSE ) {
							$this->form_data['user_date_total'][$u_obj->getId()]['data'][$tmp_date_stamp] = $tmp_pay_period_data;
						}
					}
				}
				unset($tmp_pay_period_id, $tmp_date_stamps, $tmp_date_stamp, $tmp_pay_period_data);
			}

			$this->getProgressBarObject()->set( $this->getAMFMessageID(), $key );
		}
		unset($include_no_data_rows_arr);
		//Debug::Arr($this->form_data, 'zUser Raw Data: ', __FILE__, __LINE__, __METHOD__, 10);
		//Debug::Arr($this->tmp_data['user'], 'User Raw Data: ', __FILE__, __LINE__, __METHOD__, 10);

		$blf = TTnew( 'BranchListFactory' );
		$blf->getAPISearchByCompanyIdAndArrayCriteria( $this->getUserObject()->getCompany(), array() ); //Dont send filter data as permission_children_ids intended for users corrupts the filter
		Debug::Text(' Branch Total Rows: '. $blf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
		$this->getProgressBarObject()->start( $this->getAMFMessageID(), $blf->getRecordCount(), NULL, TTi18n::getText('Retrieving Data...') );
		foreach ( $blf as $key => $b_obj ) {
			$this->tmp_data['default_branch'][$b_obj->getId()] = Misc::addKeyPrefix( 'default_branch_', (array)$b_obj->getObjectAsArray( array('id' => TRUE, 'name' => TRUE, 'province' => TRUE, 'manual_id' => TRUE, 'other_id1' => TRUE, 'other_id2' => TRUE, 'other_id3' => TRUE, 'other_id4' => TRUE, 'other_id5' => TRUE ) ) );
			$this->tmp_data['branch'][$b_obj->getId()] = Misc::addKeyPrefix( 'branch_', (array)$b_obj->getObjectAsArray( array('id' => TRUE, 'name' => TRUE, 'manual_id' => TRUE, 'province' => TRUE, 'other_id1' => TRUE, 'other_id2' => TRUE, 'other_id3' => TRUE, 'other_id4' => TRUE, 'other_id5' => TRUE ) ) );
			//For backwards compatibility with saved reports, use "branch" and "branch_name" as the same thing.
			$this->tmp_data['branch'][$b_obj->getId()]['branch'] = $this->tmp_data['branch'][$b_obj->getId()]['branch_name'];
			$this->getProgressBarObject()->set( $this->getAMFMessageID(), $key );
		}
		//Debug::Arr($this->tmp_data['default_branch'], 'Default Branch Raw Data: ', __FILE__, __LINE__, __METHOD__, 10);

		$dlf = TTnew( 'DepartmentListFactory' );
		$dlf->getAPISearchByCompanyIdAndArrayCriteria( $this->getUserObject()->getCompany(), array() ); //Dont send filter data as permission_children_ids intended for users corrupts the filter
		Debug::Text(' Department Total Rows: '. $dlf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
		$this->getProgressBarObject()->start( $this->getAMFMessageID(), $dlf->getRecordCount(), NULL, TTi18n::getText('Retrieving Data...') );
		foreach ( $dlf as $key => $d_obj ) {
			$this->tmp_data['default_department'][$d_obj->getId()] = Misc::addKeyPrefix( 'default_department_', (array)$d_obj->getObjectAsArray( array('id' => TRUE, 'name' => TRUE, 'manual_id' => TRUE, 'province' => TRUE, 'other_id1' => TRUE, 'other_id2' => TRUE, 'other_id3' => TRUE, 'other_id4' => TRUE, 'other_id5' => TRUE ) ) );
			$this->tmp_data['department'][$d_obj->getId()] = Misc::addKeyPrefix( 'department_', (array)$d_obj->getObjectAsArray( array('id' => TRUE, 'name' => TRUE, 'manual_id' => TRUE, 'province' => TRUE, 'other_id1' => TRUE, 'other_id2' => TRUE, 'other_id3' => TRUE, 'other_id4' => TRUE, 'other_id5' => TRUE ) ) );

			//For backwards compatibility with saved reports, use "branch" and "branch_name" as the same thing.
			$this->tmp_data['department'][$d_obj->getId()]['department'] = $this->tmp_data['department'][$d_obj->getId()]['department_name'];

			$this->getProgressBarObject()->set( $this->getAMFMessageID(), $key );
		}
		//Debug::Arr($this->tmp_data['default_department'], 'Default Department Raw Data: ', __FILE__, __LINE__, __METHOD__, 10);
		//Debug::Arr($this->tmp_data['department'], 'Department Raw Data: ', __FILE__, __LINE__, __METHOD__, 10);


		$utlf = TTnew( 'UserTitleListFactory' );
		$utlf->getAPISearchByCompanyIdAndArrayCriteria( $this->getUserObject()->getCompany(), array() ); //Dont send filter data as permission_children_ids intended for users corrupts the filter
		Debug::Text(' User Title Total Rows: '. $dlf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
		$user_title_column_config = array_merge( (array)Misc::removeKeyPrefix( 'user_title_', (array)$this->getColumnDataConfig() ), array('id' => TRUE, 'name' => TRUE ) ); //Always include title_id column so we can merge title data.
		$this->getProgressBarObject()->start( $this->getAMFMessageID(), $utlf->getRecordCount(), NULL, TTi18n::getText('Retrieving Titles...') );
		foreach ( $utlf as $key => $ut_obj ) {
			$this->tmp_data['user_title'][$ut_obj->getId()] = Misc::addKeyPrefix( 'user_title_', (array)$ut_obj->getObjectAsArray( $user_title_column_config ) );
			$this->getProgressBarObject()->set( $this->getAMFMessageID(), $key );
		}
		//Debug::Arr($this->tmp_data['user_title'],'user_title_data', __FILE__, __LINE__, __METHOD__, 10);

		//Get verified timesheets for all pay periods considered in report.
		$pay_period_ids = array_unique( array_keys( $pay_period_ids ) );
		if ( isset($pay_period_ids) AND count($pay_period_ids) > 0 ) {
			//Get timesheet verification authorizations by pay period so we can list the supervisors who authorized timesheets too.
			$alf = TTNew('AuthorizationListFactory');
			$alf->getAPISearchByCompanyIdAndArrayCriteria( $this->getUserObject()->getCompany(), array( 'object_type_id' => 90, 'pay_period_id' => $pay_period_ids ) );
			if ( $alf->getRecordCount() > 0 ) {
				foreach( $alf as $a_obj ) {
					$this->tmp_data['timesheet_authorization'][$a_obj->getObject()][] = array(
																							'first_name' => $a_obj->getColumn('created_by_first_name'),
																							'last_name' => $a_obj->getColumn('created_by_last_name'),
																							'created_date' => $a_obj->getCreatedDate(),
																							);
				}
			}
			unset($alf, $a_obj);

			$filter_data['pay_period_id'] = $pay_period_ids;

			$pptsvlf = TTnew( 'PayPeriodTimeSheetVerifyListFactory' );
			//$pptsvlf->getByPayPeriodIdAndCompanyId( $pay_period_ids, $this->getUserObject()->getCompany() );
			$pptsvlf->getAPISearchByCompanyIdAndArrayCriteria( $this->getUserObject()->getCompany(), $filter_data );
			if ( $pptsvlf->getRecordCount() > 0 ) {
				foreach( $pptsvlf as $pptsv_obj ) {
					$this->tmp_data['verified_timesheet'][$pptsv_obj->getUser()][$pptsv_obj->getPayPeriod()] = array(
																									'id' => $pptsv_obj->getID(),
																									'user_verified' => $pptsv_obj->getUserVerified(),
																									'user_verified_date' => $pptsv_obj->getUserVerifiedDate(),
																									'status_id' => $pptsv_obj->getStatus(),
																									'status' => $pptsv_obj->getVerificationStatusShortDisplay(),
																									'updated_date' => $pptsv_obj->getUpdatedDate(),
																									);
				}
			}

			$ppslf = TTnew('PayPeriodScheduleListFactory');
			$ppslf->getByPayPeriodIdAndCompanyId($pay_period_ids, $this->getUserObject()->getCompany() );
			if ( $ppslf->getRecordCount() > 0 ) {
				foreach( $ppslf as $pps_obj ) {
					$this->tmp_data['pay_period_schedule'][$pps_obj->getID()] = array( 'start_week_day' => $pps_obj->getStartWeekDay() );
				}
			}

			if ( strpos($format, 'pdf_') !== FALSE ) {
				$pplf = TTnew('PayPeriodListFactory');
				$pplf->getByIDList( $pay_period_ids );
				if ( $pplf->getRecordCount() > 0 ) {
					foreach( $pplf as $pp_obj ) {
						if ( isset($this->tmp_data['pay_period_schedule'][$pp_obj->getPayPeriodSchedule()]) ) {
							$this->form_data['pay_period'][$pp_obj->getID()] = $this->tmp_data['pay_period_schedule'][$pp_obj->getPayPeriodSchedule()];
						}
					}
				}
			}
		}

		//Debug::Arr($this->tmp_data, 'zUser Raw Data: ', __FILE__, __LINE__, __METHOD__, 10);
		//Debug::Arr($this->form_data, 'zUser Raw Data: ', __FILE__, __LINE__, __METHOD__, 10);

		return TRUE;
	}

	//PreProcess data such as calculating additional columns from raw data etc...
	function _preProcess( $format = NULL ) {
		$this->getProgressBarObject()->start( $this->getAMFMessageID(), count($this->tmp_data['user_date_total']), NULL, TTi18n::getText('Pre-Processing Data...') );

		$columns = $this->getColumnDataConfig();
		$dynamic_columns = Misc::trimSortPrefix( $this->getOptions('dynamic_columns') );

		$split_data_by_hours_worked = FALSE;
		if ( strpos($format, 'pdf_') === FALSE AND isset($columns['worked_hour_of_day']) ) {
			$split_data_by_hours_worked = TRUE;
		}
		unset($columns);

		//Merge time data with user data
		$key = 0;
		if ( isset($this->tmp_data['user_date_total']) ) {
			foreach( $this->tmp_data['user_date_total'] as $user_id => $level_1 ) {
				if ( isset($this->tmp_data['user'][$user_id]) ) {
					foreach( $level_1 as $date_stamp => $level_2 ) {
						foreach( $level_2 as $branch => $level_3 ) {
							foreach( $level_3 as $department => $level_4 ) {
								if ( $split_data_by_hours_worked == TRUE ) {
									$level_5 = $this->splitDataByHoursWorked( $level_4, $dynamic_columns );
								} else {
									$level_5[0] = $level_4;
								}
								foreach( $level_5 as $row ) {
									$date_columns = TTDate::getReportDates( NULL, $date_stamp, FALSE, $this->getUserObject(), array('pay_period_start_date' => $row['pay_period_start_date'], 'pay_period_end_date' => $row['pay_period_end_date'], 'pay_period_transaction_date' => $row['pay_period_transaction_date']) );

									if ( isset($this->tmp_data['user'][$user_id]['hire_date']) ) {
										$hire_date_columns = TTDate::getReportDates( 'hire', TTDate::parseDateTime( $this->tmp_data['user'][$user_id]['hire_date'] ), FALSE, $this->getUserObject() );
									} else {
										$hire_date_columns = array();
									}

									if ( isset($this->tmp_data['user'][$user_id]['termination_date']) ) {
										$termination_date_columns = TTDate::getReportDates( 'termination', TTDate::parseDateTime( $this->tmp_data['user'][$user_id]['termination_date'] ), FALSE, $this->getUserObject() );
									} else {
										$termination_date_columns = array();
									}

									$processed_data	 = array(
															//'branch' => $branch,
															//'department' => $department,
															//'pay_period' => array('sort' => $row['pay_period_start_date'], 'display' => TTDate::getDate('DATE', $row['pay_period_start_date'] ).' -> '. TTDate::getDate('DATE', $row['pay_period_end_date'] ) ),
															//'min_punch_time_stamp' => TTDate::getDate('TIME', $row['min_punch_time_stamp']),
															//'max_punch_time_stamp' => TTDate::getDate('TIME', $row['max_punch_time_stamp'])
															);

									if ( isset( $this->tmp_data['verified_timesheet'][$user_id][$row['pay_period_id']]) ) {
										$processed_data['verified_time_sheet_id'] = $this->tmp_data['verified_timesheet'][$user_id][$row['pay_period_id']]['id'];
										$processed_data['verified_time_sheet_user_verified'] = $this->tmp_data['verified_timesheet'][$user_id][$row['pay_period_id']]['user_verified'];
										$processed_data['verified_time_sheet_user_verified_date'] = $this->tmp_data['verified_timesheet'][$user_id][$row['pay_period_id']]['user_verified_date'];
										$processed_data['verified_time_sheet_status_id'] = $this->tmp_data['verified_timesheet'][$user_id][$row['pay_period_id']]['status_id'];
										$processed_data['verified_time_sheet'] = $this->tmp_data['verified_timesheet'][$user_id][$row['pay_period_id']]['status'];
										$processed_data['verified_time_sheet_date'] = $this->tmp_data['verified_timesheet'][$user_id][$row['pay_period_id']]['updated_date'];

										$this->form_data['timesheet_authorization'][$processed_data['verified_time_sheet_id']] = ( isset($this->tmp_data['timesheet_authorization'][$processed_data['verified_time_sheet_id']]) ) ? $this->tmp_data['timesheet_authorization'][$processed_data['verified_time_sheet_id']] : FALSE;
									} else {
										$processed_data['verified_time_sheet_status_id'] = $processed_data['verified_time_sheet_user_verified'] = $processed_data['verified_time_sheet_user_verified_date'] = FALSE;
										$processed_data['verified_time_sheet'] = TTi18n::getText('No');
										$processed_data['verified_time_sheet_date'] = FALSE;
									}

									if (  isset( $this->tmp_data['pay_period'][$row['pay_period_id']] ) ) {
										$processed_data['start_week_day'] = $this->tmp_data['pay_period'][$row['pay_period_id']]['start_week_day'];
									}

									if ( !isset($row['worked_time']) ) {
										$row['worked_time'] = 0;
									}

									$processed_data['min_schedule_diff'] = ( $row['min_punch_time_stamp'] != '' AND $row['min_schedule_time_stamp'] != '' ) ? ( $row['min_punch_time_stamp'] - $row['min_schedule_time_stamp'] ) : NULL;
									$processed_data['max_schedule_diff'] = ( $row['max_punch_time_stamp'] != '' AND $row['max_schedule_time_stamp'] != '' ) ? ( $row['max_punch_time_stamp'] - $row['max_schedule_time_stamp'] ) : NULL;

									if ( isset($this->tmp_data['schedule'][$user_id][$date_stamp][$branch][$department]['schedule_working']) ) {
										$processed_data['schedule_working'] = $this->tmp_data['schedule'][$user_id][$date_stamp][$branch][$department]['schedule_working'];
										$processed_data['schedule_working_diff'] = ($row['worked_time'] - $this->tmp_data['schedule'][$user_id][$date_stamp][$branch][$department]['schedule_working']);


										//We can only include scheduled_time once per user/date combination. Otherwise its duplicates the amounts and makes it incorrect.
										//So once its used unset it so it can't be used again.
										unset($this->tmp_data['schedule'][$user_id][$date_stamp][$branch][$department]['schedule_working']);
									} else {
										$processed_data['schedule_working'] = 0;
										$processed_data['schedule_working_diff'] = $row['worked_time'];
									}
									if ( isset($this->tmp_data['schedule'][$user_id][$date_stamp][$branch][$department]['schedule_absent']) ) {
										$processed_data['schedule_absent'] = $this->tmp_data['schedule'][$user_id][$date_stamp][$branch][$department]['schedule_absent'];
										unset($this->tmp_data['schedule'][$user_id][$date_stamp][$branch][$department]['schedule_absent']);
									} else {
										$processed_data['schedule_absent'] = 0;
									}

									if ( isset($this->tmp_data['user'][$user_id]['default_branch_id']) AND isset($this->tmp_data['default_branch'][$this->tmp_data['user'][$user_id]['default_branch_id']]) ) {
										$tmp_default_branch = $this->tmp_data['default_branch'][$this->tmp_data['user'][$user_id]['default_branch_id']];
									} else {
										$tmp_default_branch = array();
									}
									if ( isset($this->tmp_data['user'][$user_id]['default_department_id']) AND isset($this->tmp_data['default_department'][$this->tmp_data['user'][$user_id]['default_department_id']]) ) {
										$tmp_default_department = $this->tmp_data['default_department'][$this->tmp_data['user'][$user_id]['default_department_id']];
									} else {
										$tmp_default_department = array();
									}

									if ( isset($row['branch_id']) AND isset($this->tmp_data['branch'][$row['branch_id']]) ) {
										$tmp_branch = $this->tmp_data['branch'][$row['branch_id']];
									} else {
										$tmp_branch = array();
									}
									if ( isset($row['department_id']) AND isset($this->tmp_data['department'][$row['department_id']]) ) {
										$tmp_department = $this->tmp_data['department'][$row['department_id']];
									} else {
										$tmp_department = array();
									}

									if ( isset($this->tmp_data['user'][$user_id]['title_id']) AND isset($this->tmp_data['user_title'][$this->tmp_data['user'][$user_id]['title_id']]) ) {
										$tmp_user_title = $this->tmp_data['user_title'][$this->tmp_data['user'][$user_id]['title_id']];
									} else {
										$tmp_user_title = array();
									}

									if ( strpos($format, 'pdf_') === FALSE ) {
										$this->data[] = array_merge( $this->tmp_data['user'][$user_id], $tmp_default_branch, $tmp_default_department, $tmp_branch, $tmp_department, $tmp_user_title, $row, $date_columns, $hire_date_columns, $termination_date_columns, $processed_data );
									} else {
										$this->form_data['user_date_total'][$user_id]['data'][$date_stamp] = array_merge( $this->form_data['user_date_total'][$user_id]['data'][$date_stamp], $date_columns, $hire_date_columns, $termination_date_columns, $processed_data );
										//$this->form_data[$user_id]['data'][] = array_merge( $row, $date_columns, $processed_data );
									}
								}

							}
						}
					}
				}
				$this->getProgressBarObject()->set( $this->getAMFMessageID(), $key );
				$key++;
			}
			unset($this->tmp_data, $row, $date_columns, $processed_data, $level_1, $level_2, $level_3);
		}
		//Debug::Arr($this->data, 'preProcess Data: ', __FILE__, __LINE__, __METHOD__, 10);
		//Debug::Arr($this->form_data, 'Form Data: ', __FILE__, __LINE__, __METHOD__, 10);

		return TRUE;
	}


	function timesheetHeader( $user_data ) {
		$margins = $this->pdf->getMargins();
		$current_company = $this->getUserObject()->getCompanyObject();

		$border = 0;

		$total_width = ($this->pdf->getPageWidth() - $margins['left'] - $margins['right']);

		$this->pdf->SetFont($this->config['other']['default_font'], 'B', $this->_pdf_fontSize(24) );
		$this->pdf->Cell( $total_width, $this->_pdf_scaleSize(10), TTi18n::gettext('Employee TimeSheet'), $border, 0, 'C');
		$this->pdf->Ln( $this->_pdf_scaleSize(10) );
		$this->pdf->SetFont($this->config['other']['default_font'], 'B', $this->_pdf_fontSize(12) );
		$this->pdf->Cell( $total_width, $this->_pdf_scaleSize(5), $current_company->getName(), $border, 0, 'C');
		$this->pdf->Ln( $this->_pdf_scaleSize(5) + $this->_pdf_scaleSize(2) );

		//Generated Date/User top right.
		$this->pdf->SetFont($this->config['other']['default_font'], '', $this->_pdf_fontSize(6) );
		$this->pdf->setY( ($this->pdf->getY() - $this->_pdf_fontSize(6)) );
		$this->pdf->setX( ($this->pdf->getPageWidth() - $margins['right'] - 50) );
		$this->pdf->Cell(50, $this->_pdf_scaleSize(2), TTi18n::getText('Generated').': '. TTDate::getDate('DATE+TIME', time() ), $border, 0, 'R', 0, '', 1);
		$this->pdf->Ln( $this->_pdf_scaleSize(2) );
		$this->pdf->setX( ($this->pdf->getPageWidth() - $margins['right'] - 50) );
		$this->pdf->Cell(50, $this->_pdf_scaleSize(2), TTi18n::getText('Generated For').': '. $this->getUserObject()->getFullName(), $border, 0, 'R', 0, '', 1);
		$this->pdf->Ln( $this->_pdf_scaleSize(5) );

		$this->pdf->Rect( $this->pdf->getX(), ($this->pdf->getY() - $this->_pdf_scaleSize(2)), $total_width, $this->_pdf_scaleSize(14) );

		$this->pdf->SetFont($this->config['other']['default_font'], '', $this->_pdf_fontSize(12) );
		$this->pdf->Cell(30, $this->_pdf_scaleSize(5), TTi18n::gettext('Employee').':', $border, 0, 'R');
		$this->pdf->SetFont($this->config['other']['default_font'], 'B', $this->_pdf_fontSize(12) );
		$this->pdf->Cell( (70 + (($total_width - 200) / 2)), $this->_pdf_scaleSize(5), $user_data['first_name'] .' '. $user_data['last_name'] .' (#'. $user_data['employee_number'] .')', $border, 0, 'L', 0, '', 1);

		$this->pdf->SetFont('', '', $this->_pdf_fontSize(12) );
		$this->pdf->Cell(40, $this->_pdf_scaleSize(5), TTi18n::gettext('Title').':', $border, 0, 'R');
		$this->pdf->SetFont('', 'B', $this->_pdf_fontSize(12) );
		$this->pdf->Cell( ( 60 + ( ( $total_width - 200 ) / 2 ) ), $this->_pdf_scaleSize(5), $user_data['title'], $border, 0, 'L', 0, '', 1);
		$this->pdf->Ln( $this->_pdf_scaleSize(5) );

		$this->pdf->SetFont('', '', $this->_pdf_fontSize(12) );
		$this->pdf->Cell(30, $this->_pdf_scaleSize(5), TTi18n::gettext('Branch').':', $border, 0, 'R');
		$this->pdf->Cell( (70 + ( ( $total_width - 200 ) / 2 ) ), $this->_pdf_scaleSize(5), $user_data['default_branch'], $border, 0, 'L', 0, '', 1);
		$this->pdf->Cell(40, $this->_pdf_scaleSize(5), TTi18n::gettext('Department').':', $border, 0, 'R');
		$this->pdf->Cell( ( 60 + ( ( $total_width - 200 ) / 2 ) ), $this->_pdf_scaleSize(5), $user_data['default_department'], $border, 0, 'L', 0, '', 1);
		$this->pdf->Ln( $this->_pdf_scaleSize(5) );

		$this->pdf->SetFont($this->config['other']['default_font'], '', $this->_pdf_fontSize(10) );
		$this->pdf->Ln( $this->_pdf_scaleSize(5) );

		return TRUE;
	}

	function timesheetPayPeriodHeader( $user_data, $data ) {
		$line_h = $this->_pdf_scaleSize(5);

		$margins = $this->pdf->getMargins();
		$total_width = ($this->pdf->getPageWidth() - $margins['left'] - $margins['right']);

		$this->pdf->SetFont($this->config['other']['default_font'], 'B', $this->_pdf_fontSize(10) );
		$this->pdf->setFillColor(220, 220, 220);
		if ( isset($data['verified_time_sheet']) AND $data['verified_time_sheet_user_verified'] == TRUE AND $data['verified_time_sheet_user_verified_date'] != '' ) {
			$this->pdf->Cell( 77.9, $line_h, TTi18n::gettext('Pay Period').': '. $data['pay_period']['display'], 1, 0, 'L', 1);
			$this->pdf->Cell( ($total_width - 77.9), $line_h, TTi18n::gettext('Electronically signed by') .' '. $user_data['first_name'] .' '. $user_data['last_name'] .' '. TTi18n::gettext('on') .' '. TTDate::getDate('DATE+TIME', $data['verified_time_sheet_user_verified_date']  ), 1, 0, 'R', 1);
		} else {
			$this->pdf->Cell( $total_width, $line_h, TTi18n::gettext('Pay Period').': '. $data['pay_period']['display'], 1, 0, 'L', 1);
		}

		$this->pdf->Ln();

		unset($this->timesheet_week_totals);
		$this->timesheet_week_totals = Misc::preSetArrayValues( NULL, array( 'worked_time', 'absence_time', 'regular_time', 'overtime_time' ), 0 );

		return TRUE;
	}

	function timesheetWeekHeader( $column_widths ) {
		$line_h = $this->_pdf_scaleSize(5);

		$this->pdf->SetFont($this->config['other']['default_font'], 'B', $this->_pdf_fontSize(10) );
		$this->pdf->setFillColor(220, 220, 220);

		$this->pdf->Cell( $column_widths['line'], $line_h, '#', 1, 0, 'C', 1, '', 1 );
		$this->pdf->Cell( $column_widths['date_stamp'], $line_h, TTi18n::gettext('Date'), 1, 0, 'C', 1, '', 1 );
		$this->pdf->Cell( $column_widths['dow'], $line_h, TTi18n::gettext('DoW'), 1, 0, 'C', 1, '', 1 );
		$this->pdf->Cell( $column_widths['in_punch_time_stamp'], $line_h, TTi18n::gettext('In'), 1, 0, 'C', 1, '', 1 );
		$this->pdf->Cell( $column_widths['out_punch_time_stamp'], $line_h, TTi18n::gettext('Out'), 1, 0, 'C', 1, '', 1 );
		$this->pdf->Cell( $column_widths['worked_time'], $line_h, TTi18n::gettext('Worked Time'), 1, 0, 'C', 1, '', 1 );
		$this->pdf->Cell( $column_widths['regular_time'], $line_h, TTi18n::gettext('Regular Time'), 1, 0, 'C', 1, '', 1 );
		$this->pdf->Cell( $column_widths['overtime_time'], $line_h, TTi18n::gettext('Over Time'), 1, 0, 'C', 1, '', 1 );
		$this->pdf->Cell( $column_widths['absence_time'], $line_h, TTi18n::gettext('Absence Time'), 1, 0, 'C', 1, '', 1 );
		$this->pdf->Ln();

		return TRUE;
	}

	function timesheetDayRow( $format, $columns, $column_widths, $user_data, $data, $prev_data ) {

		//Handle page break.
		$page_break_height = 25;
		if ( $this->counter_i == 1 OR $this->counter_x == 1 ) {
			if ( $this->counter_i == 1 ) {
				$page_break_height += 5;
			}
			$page_break_height += 5;
		}

		$this->timesheetCheckPageBreak( $page_break_height, TRUE );

		//Debug::Text('Pay Period Changed: Current: '.	$data['pay_period_id'] .' Prev: '. $prev_data['pay_period_id'] .' Counter X: '. $this->counter_x .' Max I: '. $this->max_i .' PP Start: '. TTDate::getDate('DATE', $data['pay_period_start_date'] ), __FILE__, __LINE__, __METHOD__, 10);
		if ( $prev_data !== FALSE AND $data['pay_period_id'] != $prev_data['pay_period_id'] ) {
			//Only display week total if we are in the middle of a week when the pay period ends, not at the end of the week.
			if ( $this->counter_x != 1 ) {
				$this->timesheetWeekTotal( $column_widths, $this->timesheet_week_totals );
				$this->counter_x++;
			}
			$this->timesheetPayPeriodHeader( $user_data, $data );
		}

		//Show Header
		if ( $this->counter_i == 1 OR $this->counter_x == 1 ) {
			//Debug::Text('aFirst Row: Header', __FILE__, __LINE__, __METHOD__, 10);
			if ( $this->counter_i == 1 ) {
				$this->timesheetPayPeriodHeader( $user_data, $data );
			}

			$this->timesheetWeekHeader( $column_widths );
		}

		if ( ($this->counter_x % 2) == 0 ) {
			$this->pdf->setFillColor(220, 220, 220);
		} else {
			$this->pdf->setFillColor(255, 255, 255);
		}

		if ( $data['time_stamp'] !== '' ) {
			$default_line_h = $this->_pdf_scaleSize(5);
			$line_h = $default_line_h;

			$total_rows_arr = array();

			//Find out how many punches fall on this day, so we can change row height to fit.
			$total_punch_rows = 1;

			if ( isset($user_data['punch_rows'][$data['pay_period_id']][$data['time_stamp']]) ) {
				//Debug::Text('Punch Data Row: '. $this->counter_x, __FILE__, __LINE__, __METHOD__, 10);

				$day_punch_data = $user_data['punch_rows'][$data['pay_period_id']][$data['time_stamp']];
				$total_punch_rows = count($day_punch_data);
			} //else { Debug::Text('NO Punch Data Row: '. $this->counter_x .' Date: '. TTDate::getDate('DATE', $data['time_stamp'] ) .'('.$data['time_stamp'].') PP: '. $data['pay_period_id'], __FILE__, __LINE__, __METHOD__, 10);

			$total_rows_arr[] = $total_punch_rows;

			$total_regular_time_rows = 1;
			if ( $data['regular_time'] > 0 AND isset($data['categorized_time']['regular_time_policy']) ) {
				$total_regular_time_rows = count($data['categorized_time']['regular_time_policy']);
			}
			$total_rows_arr[] = $total_regular_time_rows;

			$total_over_time_rows = 1;
			if ( $data['overtime_time'] > 0 AND isset($data['categorized_time']['over_time_policy']) ) {
				$total_over_time_rows = count($data['categorized_time']['over_time_policy']);
			}
			$total_rows_arr[] = $total_over_time_rows;

			$total_absence_rows = 1;
			if ( $data['absence_time'] > 0 AND isset($data['categorized_time']['absence_policy']) ) {
				$total_absence_rows = count($data['categorized_time']['absence_policy']);
			}

			$total_rows_arr[] = $total_absence_rows;

			rsort($total_rows_arr);
			$max_rows = $total_rows_arr[0];
			$line_h = ( $format == 'pdf_timesheet_detail' ) ? ( $default_line_h * $max_rows ) : $default_line_h;

			$this->pdf->SetFont($this->config['other']['default_font'], '', $this->_pdf_fontSize(9) );
			$this->pdf->Cell( $column_widths['line'], $line_h, $this->counter_x, 1, 0, 'C', 1);
			$this->pdf->Cell( $column_widths['date_stamp'], $line_h, TTDate::getDate('DATE', $data['time_stamp'] ), 1, 0, 'C', 1, '', 1);
			$this->pdf->Cell( $column_widths['dow'], $line_h, date('D', $data['time_stamp']), 1, 0, 'C', 1);

			$pre_punch_x = $this->pdf->getX();
			$pre_punch_y = $this->pdf->getY();

			//Print Punches
			if ( $format == 'pdf_timesheet_detail' AND isset($day_punch_data) ) {
				$this->pdf->SetFont($this->config['other']['default_font'], '', $this->_pdf_fontSize(8) );

				$n = 0;
				$punch_y = 0;
				foreach( $day_punch_data as $punch_data ) {
					if ( !isset($punch_data[10]['time_stamp']) ) {
						$punch_data[10]['time_stamp'] = NULL;
						$punch_data[10]['type_code'] = NULL;
					}
					if ( !isset($punch_data[20]['time_stamp']) ) {
						$punch_data[20]['time_stamp'] = NULL;
						$punch_data[20]['type_code'] = NULL;
					}

					if ( $n > 0 ) {
						$this->pdf->setXY( $pre_punch_x, ($punch_y + $default_line_h) );
					}

					$this->pdf->Cell( $column_widths['in_punch_time_stamp'], ($line_h / $total_punch_rows), TTDate::getDate('TIME', $punch_data[10]['time_stamp'] ) .' '. $punch_data[10]['type_code'], 1, 0, 'C', 1);
					$this->pdf->Cell( $column_widths['out_punch_time_stamp'], ($line_h / $total_punch_rows), TTDate::getDate('TIME', $punch_data[20]['time_stamp'] ) .' '. $punch_data[20]['type_code'], 1, 0, 'C', 1);

					$punch_x = $this->pdf->getX();
					$punch_y = $this->pdf->getY();

					$n++;
				}

				$this->pdf->setXY( $punch_x, $pre_punch_y);

				$this->pdf->SetFont($this->config['other']['default_font'], '', $this->_pdf_fontSize(9) );
			} else {
				$this->pdf->Cell( $column_widths['in_punch_time_stamp'], $line_h, ( ( isset($data['min_punch_time_stamp']) ) ? TTDate::getDate('TIME', $data['min_punch_time_stamp'] ) : NULL ), 1, 0, 'C', 1);
				$this->pdf->Cell( $column_widths['out_punch_time_stamp'], $line_h, ( ( isset($data['max_punch_time_stamp']) ) ? TTDate::getDate('TIME', $data['max_punch_time_stamp'] ) : NULL ), 1, 0, 'C', 1);
			}

			$this->pdf->Cell( $column_widths['worked_time'], $line_h, TTDate::getTimeUnit( $data['worked_time'] ), 1, 0, 'C', 1);

			if ( $format == 'pdf_timesheet_detail' ) {

				//If we just check to make sure there are more than two regular time entries, then if its just one entry or the other on a specific day the user won't be able to know which it is.
				//So not sure we have much choice, but to always display the Pay Code label.
				if ( $data['regular_time'] > 0 AND isset($data['categorized_time']['regular_time_policy']) ) {
					$pre_regular_time_x = $this->pdf->getX();
					$this->pdf->SetFont($this->config['other']['default_font'], '', $this->_pdf_fontSize(8) );

					//Count how many absence policy rows there are.
					$regular_time_policy_total_rows = count($data['categorized_time']['regular_time_policy']);
					foreach( $data['categorized_time']['regular_time_policy'] as $policy_column => $value ) {
						//When showing Regular Time details, the majority of them will show the "Regular Time" label, which is somewhat redundant...
						//So we check here to see if there is only one row on the day and if that label is 'Regular Time', if so don't use any labels.
						$pay_code_label = ( isset($columns[$policy_column]) ) ? $columns[$policy_column].': ' : TTi18n::getText('ERROR: UnAssigned Regular Time').' ';
						if ( $regular_time_policy_total_rows == 1 AND isset($columns[$policy_column]) AND strtolower($columns[$policy_column]) == 'regular time' ) {
							$pay_code_label = '';
						}
						$this->pdf->Cell( $column_widths['regular_time'], ($line_h / $total_regular_time_rows), $pay_code_label . TTDate::getTimeUnit( $data[$policy_column] ), 1, 0, 'C', 1, '', 1);
						$this->pdf->setXY( $pre_regular_time_x, ($this->pdf->getY() + ($line_h / $total_regular_time_rows)) );

						$regular_time_x = $this->pdf->getX();
					}
					unset($value); // code standards
					unset($pay_code_label);
					$this->pdf->setXY( ($regular_time_x + $column_widths['regular_time']), $pre_punch_y);
					$this->pdf->SetFont($this->config['other']['default_font'], '', $this->_pdf_fontSize(9) );
				} else {
					$this->pdf->Cell( $column_widths['regular_time'], $line_h, TTDate::getTimeUnit( $data['regular_time'] ), 1, 0, 'C', 1);
				}

				if ( $data['overtime_time'] > 0 AND isset($data['categorized_time']['over_time_policy']) ) {
					$pre_over_time_x = $this->pdf->getX();
					$this->pdf->SetFont($this->config['other']['default_font'], '', $this->_pdf_fontSize(8) );

					//Count how many absence policy rows there are.
					foreach( $data['categorized_time']['over_time_policy'] as $policy_column => $value ) {
						$this->pdf->Cell( $column_widths['overtime_time'], ($line_h / $total_over_time_rows), $columns[$policy_column].': '.TTDate::getTimeUnit( $data[$policy_column] ), 1, 0, 'C', 1, '', 1);
						$this->pdf->setXY( $pre_over_time_x, ($this->pdf->getY() + ($line_h / $total_over_time_rows)) );

						$over_time_x = $this->pdf->getX();
					}
					$this->pdf->setXY( ($over_time_x + $column_widths['overtime_time']), $pre_punch_y);
					$this->pdf->SetFont($this->config['other']['default_font'], '', $this->_pdf_fontSize(9) );
				} else {
					$this->pdf->Cell( $column_widths['overtime_time'], $line_h, TTDate::getTimeUnit( $data['overtime_time'] ), 1, 0, 'C', 1);
				}

				if ( $data['absence_time'] > 0 AND isset($data['categorized_time']['absence_policy']) ) {
					$pre_absence_time_x = $this->pdf->getX();
					$this->pdf->SetFont($this->config['other']['default_font'], '', $this->_pdf_fontSize(8) );

					//Count how many absence policy rows there are.
					foreach( $data['categorized_time']['absence_policy'] as $policy_column => $value ) {
						$this->pdf->Cell( $column_widths['absence_time'], ($line_h / $total_absence_rows), ( isset($columns[$policy_column]) ? $columns[$policy_column] : TTi18n::getText('N/A') ) .': '. TTDate::getTimeUnit( $data[$policy_column] ), 1, 0, 'C', 1, '', 1);
						$this->pdf->setXY( $pre_absence_time_x, ($this->pdf->getY() + ($line_h / $total_absence_rows)));
					}

					$this->pdf->setY( ($this->pdf->getY() - ($line_h / $total_absence_rows)) );
					$this->pdf->SetFont($this->config['other']['default_font'], '', $this->_pdf_fontSize(9) );
				} else {
					$this->pdf->Cell( $column_widths['absence_time'], $line_h, TTDate::getTimeUnit( $data['absence_time'] ), 1, 0, 'C', 1);
				}
			} else {
				$this->pdf->Cell( $column_widths['regular_time'], $line_h, TTDate::getTimeUnit( $data['regular_time'] ), 1, 0, 'C', 1);
				$this->pdf->Cell( $column_widths['overtime_time'], $line_h, TTDate::getTimeUnit( $data['overtime_time'] ), 1, 0, 'C', 1);
				$this->pdf->Cell( $column_widths['absence_time'], $line_h, TTDate::getTimeUnit( $data['absence_time'] ), 1, 0, 'C', 1);
			}
			$this->pdf->Ln( $line_h );

			unset($day_punch_data);
		}

		$this->timesheet_totals['worked_time'] += $data['worked_time'];
		$this->timesheet_totals['absence_time'] += $data['absence_time'];
		$this->timesheet_totals['regular_time'] += $data['regular_time'];
		$this->timesheet_totals['overtime_time'] += $data['overtime_time'];

		$this->timesheet_week_totals['worked_time'] += $data['worked_time'];
		$this->timesheet_week_totals['absence_time'] += $data['absence_time'];
		$this->timesheet_week_totals['regular_time'] += $data['regular_time'];
		$this->timesheet_week_totals['overtime_time'] += $data['overtime_time'];

		//Debug::Text('Row: '. $this->counter_x .' I: '. $this->counter_i .' Max I: '. $this->max_i, __FILE__, __LINE__, __METHOD__, 10);
		if ( TTDate::getDayOfWeek( (TTDate::getMiddleDayEpoch($data['time_stamp']) + 86400) ) == $data['start_week_day']
				OR ( isset($prev_data['start_week_day']) AND $data['start_week_day'] != $prev_data['start_week_day'] )
				OR $this->counter_i == $this->max_i ) {
			$this->timesheetWeekTotal( $column_widths, $this->timesheet_week_totals );

			unset($this->timesheet_week_totals);
			$this->timesheet_week_totals = Misc::preSetArrayValues( NULL, array( 'worked_time', 'absence_time', 'regular_time', 'overtime_time' ), 0 );
		}

		$this->counter_i++;
		$this->counter_x++;

		return TRUE;
	}

	function timesheetWeekTotal( $column_widths, $week_totals ) {
		//Debug::Text('Week Total: Row: '. $this->counter_x, __FILE__, __LINE__, __METHOD__, 10);

		$line_h = $this->_pdf_scaleSize(6);

		//Show Week Total.
		$total_cell_width = ($column_widths['line'] + $column_widths['date_stamp'] + $column_widths['dow'] + $column_widths['in_punch_time_stamp'] + $column_widths['out_punch_time_stamp']);
		$this->pdf->SetFont($this->config['other']['default_font'], 'B', $this->_pdf_fontSize(9) );
		$this->pdf->Cell( $total_cell_width, $line_h, TTi18n::gettext('Week Total').': ', 0, 0, 'R', 0);
		$this->pdf->Cell( $column_widths['worked_time'], $line_h, TTDate::getTimeUnit( $week_totals['worked_time'] ), 0, 0, 'C', 0);
		$this->pdf->Cell( $column_widths['regular_time'], $line_h, TTDate::getTimeUnit( $week_totals['regular_time'] ), 0, 0, 'C', 0);
		$this->pdf->Cell( $column_widths['overtime_time'], $line_h, TTDate::getTimeUnit( $week_totals['overtime_time'] ), 0, 0, 'C', 0);
		$this->pdf->Cell( $column_widths['absence_time'], $line_h, TTDate::getTimeUnit( $week_totals['absence_time'] ), 0, 0, 'C', 0);
		$this->pdf->Ln();

		$this->counter_x = 0; //Reset to 0, as the counter increases to 1 immediately after.
		$this->counter_y++;

		return TRUE;
	}

	function timesheetTotal( $column_widths, $totals ) {

		$line_h = $this->_pdf_scaleSize(6);

		$total_cell_width = ($column_widths['line'] + $column_widths['date_stamp'] + $column_widths['dow'] + $column_widths['in_punch_time_stamp']);
		$this->pdf->SetFont($this->config['other']['default_font'], 'B', $this->_pdf_fontSize(9) );
		$this->pdf->Cell( $total_cell_width, $line_h, '', 0, 0, 'R', 0);
		$this->pdf->Cell( $column_widths['out_punch_time_stamp'], $line_h, TTi18n::gettext('Overall Total').': ', 'T', 0, 'R', 0);
		$this->pdf->Cell( $column_widths['worked_time'], $line_h, TTDate::getTimeUnit( $totals['worked_time'] ), 'T', 0, 'C', 0);
		$this->pdf->Cell( $column_widths['regular_time'], $line_h, TTDate::getTimeUnit( $totals['regular_time'] ), 'T', 0, 'C', 0);
		$this->pdf->Cell( $column_widths['overtime_time'], $line_h, TTDate::getTimeUnit( $totals['overtime_time'] ), 'T', 0, 'C', 0);
		$this->pdf->Cell( $column_widths['absence_time'], $line_h, TTDate::getTimeUnit( $totals['absence_time'] ), 'T', 0, 'C', 0);
		$this->pdf->Ln();

		return TRUE;
	}

	function timesheetNoData() {
		$margins = $this->pdf->getMargins();

		$border = 0;

		$total_width = ($this->pdf->getPageWidth() - $margins['left'] - $margins['right']);

		$this->pdf->Ln(10);

		$this->pdf->Rect( $this->pdf->getX(), ($this->pdf->getY() - 2), $total_width, 10 );

		$this->pdf->SetFont($this->config['other']['default_font'], 'B', $this->_pdf_fontSize(12) );
		$this->pdf->Cell($total_width, 5, 'NO TIMESHEET DATA FOR THIS PERIOD', $border, 0, 'C');

		$this->pdf->Ln(10);

		$this->pdf->SetFont($this->config['other']['default_font'], '', $this->_pdf_fontSize(10) );
		$this->pdf->Ln();

		return TRUE;
	}

	function timesheetSignature( $user_data, $data ) {
		$border = 0;

		$this->pdf->SetFont($this->config['other']['default_font'], '', $this->_pdf_fontSize(10) );
		$this->pdf->setFillColor(255, 255, 255);
		$this->pdf->Ln(1);

		$margins = $this->pdf->getMargins();
		$total_width = ($this->pdf->getPageWidth() - $margins['left'] - $margins['right']);

		$line_h = $this->_pdf_scaleSize(6);

		//Signature lines
		$this->pdf->MultiCell($total_width, 5, TTi18n::gettext('By signing this timesheet I hereby certify that the above time accurately and fully reflects the time that').' '. $user_data['first_name'] .' '. $user_data['last_name'] .' '.TTi18n::gettext('worked during the designated period.'), $border, 'L');
		$this->pdf->Ln( $line_h );

		$this->pdf->Cell(40, $line_h, TTi18n::gettext('Employee Signature').':', $border, 0, 'L');
		$this->pdf->Cell(60, $line_h, '_____________________________', $border, 0, 'C');
		$this->pdf->Cell(40, $line_h, TTi18n::gettext('Supervisor Signature').':', $border, 0, 'R');
		$this->pdf->Cell(60, $line_h, '_____________________________', $border, 0, 'C');

		$this->pdf->Ln(	 $line_h );
		$this->pdf->Cell(40, $line_h, '', $border, 0, 'R');
		$this->pdf->Cell(60, $line_h, $user_data['first_name'] .' '. $user_data['last_name'], $border, 0, 'C');

		$this->pdf->Ln(	 $line_h );
		$this->pdf->Cell(140, $line_h, '', $border, 0, 'R');
		$this->pdf->Cell(60, $line_h, '_____________________________', $border, 0, 'C');

		$this->pdf->Ln(	 $line_h );
		$this->pdf->Cell(140, $line_h, '', $border, 0, 'R');
		$this->pdf->Cell(60, $line_h, TTi18n::gettext('(print name)'), $border, 0, 'C');

		if ( isset($data['verified_time_sheet']) AND $data['verified_time_sheet_user_verified'] == TRUE AND $data['verified_time_sheet_user_verified_date'] != '' ) {
			$this->pdf->Ln( $line_h );
			$this->pdf->SetFont($this->config['other']['default_font'], 'B', $this->_pdf_fontSize(10) );
			$this->pdf->Cell(200, $line_h, TTi18n::gettext('TimeSheet electronically signed by').' '. $user_data['first_name'] .' '. $user_data['last_name'] .' '. TTi18n::gettext('on') .' '. TTDate::getDate('DATE+TIME', $data['verified_time_sheet_user_verified_date'] ), $border, 0, 'C');
		}

		//Make sure we display the superior authorization even if the user hasn't hasn't verified the timesheet yet, or at all.
		if ( isset($data['verified_time_sheet_id']) AND isset($this->form_data['timesheet_authorization'][$data['verified_time_sheet_id']]) AND is_array($this->form_data['timesheet_authorization'][$data['verified_time_sheet_id']]) ) {
			foreach( $this->form_data['timesheet_authorization'][$data['verified_time_sheet_id']] as $timesheet_authorization_data ) {
				$this->pdf->Ln( $line_h );
				$this->pdf->SetFont($this->config['other']['default_font'], 'B', $this->_pdf_fontSize(10) );
				$this->pdf->Cell(200, $line_h, TTi18n::gettext('TimeSheet electronically authorized by').' '. $timesheet_authorization_data['first_name'] .' '. $timesheet_authorization_data['last_name'] .' '. TTi18n::gettext('on') .' '. TTDate::getDate('DATE+TIME', $timesheet_authorization_data['created_date'] ), $border, 0, 'C');
			}
		}

		return TRUE;
	}

	//function timesheetFooter( $pdf_created_date, $adjust_x, $adjust_y ) {
	function timesheetFooter() {
		$margins = $this->pdf->getMargins();

		$this->pdf->SetFont($this->config['other']['default_font'], '', $this->_pdf_fontSize(8) );

		//Save x, y and restore after footer is set.
		$x = $this->pdf->getX();
		$y = $this->pdf->getY();

		//Jump to end of page.
		$this->pdf->setY( ($this->pdf->getPageHeight() - $margins['bottom'] - $margins['top'] - 10) );

		$this->pdf->Cell( ($this->pdf->getPageWidth() - $margins['right']), $this->_pdf_fontSize(5), TTi18n::getText('Page').' '. $this->pdf->PageNo() .' of '. $this->pdf->getAliasNbPages(), 0, 0, 'C', 0 );
		$this->pdf->Ln();

		$this->pdf->SetFont($this->config['other']['default_font'], '', $this->_pdf_fontSize(6) );
		$this->pdf->Cell( ($this->pdf->getPageWidth() - $margins['right']), $this->_pdf_fontSize(5), TTi18n::gettext('Report Generated By').' '. APPLICATION_NAME .' v'. APPLICATION_VERSION, 0, 0, 'C', 0 );

		$this->pdf->setX( $x );
		$this->pdf->setY( $y );
		return TRUE;
	}

	function timesheetCheckPageBreak( $height, $add_page = TRUE ) {
		$margins = $this->pdf->getMargins();

		if ( ($this->pdf->getY() + $height) > ($this->pdf->getPageHeight() - $margins['bottom'] - $margins['top'] - 10) ) {
			//Debug::Text('Detected Page Break needed...', __FILE__, __LINE__, __METHOD__, 10);
			$this->timesheetAddPage();

			return TRUE;
		}
		return FALSE;
	}

	function timesheetHandleDayGaps( $start_date, $end_date, $format, $columns, $column_widths, $user_data, $data, $prev_data ) {
		//Debug::Text('FOUND GAP IN DAYS!', __FILE__, __LINE__, __METHOD__, 10);
		$blank_row_data = FALSE;
		for( $d = TTDate::getMiddleDayEpoch($start_date); $d < $end_date; $d += 86400) {
			if ( $this->_pdf_checkMaximumPageLimit() == FALSE ) {
				Debug::Text('Exceeded maximum page count...', __FILE__, __LINE__, __METHOD__, 10);
				//Exceeded maximum pages, stop processing.
				$this->_pdf_displayMaximumPageLimitError();
				break;
			}

			//Need to handle pay periods switching in the middle of a string of blank rows.
			$blank_row_time_stamp = TTDate::getBeginDayEpoch($d);
			//Debug::Text('Blank row timestamp: '. TTDate::getDate('DATE+TIME', $blank_row_time_stamp ) .' Pay Period End Date: '. TTDate::getDate('DATE+TIME', $prev_data['pay_period_end_date'] ), __FILE__, __LINE__, __METHOD__, 10);
			if ( $blank_row_time_stamp >= $prev_data['pay_period_end_date'] ) {
				//Debug::Text('aBlank row timestamp: '. TTDate::getDate('DATE+TIME', $blank_row_time_stamp ) .' Pay Period End Date: '. TTDate::getDate('DATE+TIME', $prev_data['pay_period_end_date'] ), __FILE__, __LINE__, __METHOD__, 10);
				$pay_period_id = $data['pay_period_id'];
				$pay_period_start_date = $data['pay_period_start_date'];
				$pay_period_end_date = $data['pay_period_end_date'];
				$pay_period = $data['pay_period'];
			} else {
				//Debug::Text('bBlank row timestamp: '. TTDate::getDate('DATE+TIME', $blank_row_time_stamp ) .' Pay Period End Date: '. TTDate::getDate('DATE+TIME', $prev_data['pay_period_end_date'] ), __FILE__, __LINE__, __METHOD__, 10);
				$pay_period_id = $prev_data['pay_period_id'];
				$pay_period_start_date = $prev_data['pay_period_start_date'];
				$pay_period_end_date = $prev_data['pay_period_end_date'];
				$pay_period = $prev_data['pay_period'];
			}

			$blank_row_data = array(
										'pay_period_id' => $pay_period_id,
										'pay_period_start_date' => $pay_period_start_date,
										'pay_period_end_date' => $pay_period_end_date,
										'pay_period' => $pay_period,
										'start_week_day' => ( isset( $data['start_week_day'] ) ) ? $data['start_week_day'] : 0,
										'time_stamp' => $blank_row_time_stamp,
										'min_punch_time_stamp' => NULL,
										'max_punch_time_stamp' => NULL,
										'in_punch_time' => NULL,
										'out_punch_time' => NULL,
										'worked_time' => NULL,
										'regular_time' => NULL,
										'overtime_time' => NULL,
										'absence_time' => NULL
									);

			//Don't increase max_i if the last day is a gap. However if there are gaps in the middle of the pay period will cause a problem?
			if ( $d != TTDate::getMiddleDayEpoch($end_date) ) {
				$this->max_i++;
			}
			$this->timesheetDayRow( $format, $columns, $column_widths, $user_data, $blank_row_data, $prev_data ); //Prev data is actually the current data for a blank row.

			//Make sure we set prev_data here as well so if a pay period changes in the middle of a blank row range its detected only once. not multiple times.
			$prev_data = $blank_row_data;
			unset( $blank_row_time_stamp, $pay_period_id, $pay_period_start_date, $pay_period_end_date, $pay_period);
		}

		return $blank_row_data; //Return the last rows data, so we can use this as the new prev_data in the main loop.
	}

	function timesheetAddPage() {
		$this->timesheetFooter();
		$this->pdf->AddPage();
		return TRUE;
	}


	function _outputPDFTimesheet( $format ) {
		Debug::Text(' Format: '. $format, __FILE__, __LINE__, __METHOD__, 10);

		$pdf_created_date = time();

		$adjust_x = 10;
		$adjust_y = 10;

		//Debug::Arr($this->form_data, 'Form Data: ', __FILE__, __LINE__, __METHOD__, 10);
		if ( isset($this->form_data) AND count($this->form_data) > 0 ) {
			//Start displaying dates/times here. Start with header.
			//Make sure we sort the form data for printable timesheets.
			$this->form_data['user_date_total'] = Sort::arrayMultiSort( $this->form_data['user_date_total'], $this->getSortConfig() );

			//Get pay period schedule data for each pay period.
			$this->pdf = new TTPDF( 'P', 'mm', 'LETTER', $this->getUserObject()->getCompanyObject()->getEncoding() );

			$this->pdf->SetAuthor( APPLICATION_NAME );
			$this->pdf->SetTitle( $this->title );
			$this->pdf->SetSubject( APPLICATION_NAME .' '. TTi18n::getText('Report') );

			$this->pdf->setMargins( $this->config['other']['left_margin'], $this->config['other']['top_margin'], $this->config['other']['right_margin'] );
			//Debug::Arr($this->config['other'], 'Margins: ', __FILE__, __LINE__, __METHOD__, 10);

			$this->pdf->SetAutoPageBreak(FALSE);

			$this->pdf->SetFont($this->config['other']['default_font'], '', $this->_pdf_fontSize(10) );

			$margins = $this->pdf->getMargins();
			$total_width = ($this->pdf->getPageWidth() - $margins['left'] - $margins['right']);

			//Debug::Arr($this->form_data, 'zabUser Raw Data: ', __FILE__, __LINE__, __METHOD__, 10);

			$filter_data = $this->getFilterConfig();
			$columns = Misc::trimSortPrefix( $this->getOptions('columns') );

			$this->getProgressBarObject()->start( $this->getAMFMessageID(), 2, NULL, TTi18n::getText('Querying Database...') ); //Iterations need to be 2, otherwise progress bar is not created.
			$this->getProgressBarObject()->set( $this->getAMFMessageID(), 2 );

			if ( $format == 'pdf_timesheet_detail' ) {
				$plf = TTnew( 'PunchListFactory' );
				$plf->getSearchByCompanyIdAndArrayCriteria( $this->getUserObject()->getCompany(), $filter_data);
				Debug::Text('Got punch data... Total Rows: '. $plf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
				$this->getProgressBarObject()->start( $this->getAMFMessageID(), $plf->getRecordCount(), NULL, TTi18n::getText('Retrieving Punch Data...') );
				if ( $plf->getRecordCount() > 0 ) {
					foreach( $plf as $key => $p_obj ) {
						$this->form_data['user_date_total'][(int)$p_obj->getColumn('user_id')]['punch_rows'][(int)$p_obj->getColumn('pay_period_id')][(int)TTDate::strtotime($p_obj->getColumn('date_stamp'))][$p_obj->getPunchControlID()][$p_obj->getStatus()] = array( 'status_id' => $p_obj->getStatus(), 'type_id' => $p_obj->getType(), 'type_code' => $p_obj->getTypeCode(), 'time_stamp' => $p_obj->getTimeStamp() );
						$this->getProgressBarObject()->set( $this->getAMFMessageID(), $key );
					}
				}
				unset($plf, $p_obj);
			}

			Debug::Text('Drawing timesheets...', __FILE__, __LINE__, __METHOD__, 10);
			$this->getProgressBarObject()->start( $this->getAMFMessageID(), count($this->form_data['user_date_total']), NULL, TTi18n::getText('Generating TimeSheets...') );
			$key = 0;
			$page_count = 0;
			foreach( $this->form_data['user_date_total'] as $user_data ) {
				if ( $this->_pdf_checkMaximumPageLimit() == FALSE ) {
					Debug::Text('Exceeded maximum page count...', __FILE__, __LINE__, __METHOD__, 10);
					//Exceeded maximum pages, stop processing.
					$this->_pdf_displayMaximumPageLimitError();
					break;
				}

				if ( isset($user_data['first_name']) AND isset($user_data['last_name']) AND isset($user_data['employee_number']) ) {
					//Debug::Text('User: '. $user_data['first_name'] .' '. $user_data['last_name'], __FILE__, __LINE__, __METHOD__, 10);

					//Use percentages so it properly scales to landscape mode.
					$column_widths = array(
										'line' => ($total_width * 0.025), //Was: 5
										'date_stamp' => ($total_width * 0.10), //Was: 20
										'dow' => ($total_width * 0.05), //Was: 10
										'in_punch_time_stamp' => ($total_width * 0.08), //Was: 20
										'out_punch_time_stamp' => ($total_width * 0.08), //Was: 20
										'worked_time' => ($total_width * 0.10), //Was: 20
										'regular_time' => ($total_width * 0.17), //Was: 20
										'overtime_time' => ($total_width * 0.17), //Was: 40.6
										'absence_time' => ($total_width * 0.225), //Was: 45
										);

					if ( isset($user_data['data']) AND is_array($user_data['data']) ) {
						$this->pdf->AddPage( 'P', 'LETTER' );
						$this->timesheetHeader( $user_data );

						$user_data['data'] = Sort::arrayMultiSort( $user_data['data'], array( 'time_stamp' => SORT_ASC ) );

						$this->timesheet_week_totals = Misc::preSetArrayValues( NULL, array( 'worked_time', 'absence_time', 'regular_time', 'overtime_time' ), 0 );
						$this->timesheet_totals = array();
						$this->timesheet_totals = Misc::preSetArrayValues( $this->timesheet_totals, array( 'worked_time', 'absence_time', 'regular_time', 'overtime_time' ), 0 );

						$this->counter_i = 1; //Overall row counter.
						$this->counter_x = 1; //Row counter, starts over each week.
						$this->counter_y = 1; //Week counter.
						$this->max_i = count($user_data['data']);
						$prev_data = FALSE;
						foreach( $user_data['data'] as $data ) {
							if ( $this->_pdf_checkMaximumPageLimit() == FALSE ) {
								Debug::Text('Exceeded maximum page count...', __FILE__, __LINE__, __METHOD__, 10);
								//Exceeded maximum pages, stop processing.
								$this->_pdf_displayMaximumPageLimitError();
								break 2;
							}

							if ( isset($this->form_data['pay_period'][$data['pay_period_id']]) ) {
								//Debug::Arr( $data, 'Data: i: '. $this->counter_i .' x: '. $this->counter_x .' Max I: '. $this->max_i, __FILE__, __LINE__, __METHOD__, 10);
								$data = Misc::preSetArrayValues( $data, array('time_stamp', 'in_punch_time_stamp', 'out_punch_time_stamp', 'worked_time', 'absence_time', 'regular_time', 'overtime_time' ), '--' );

								$data['start_week_day'] = $this->form_data['pay_period'][$data['pay_period_id']]['start_week_day'];

								$row_date_gap = ($prev_data !== FALSE ) ? (TTDate::getMiddleDayEpoch($data['time_stamp']) - TTDate::getMiddleDayEpoch($prev_data['time_stamp'])) : 0; //Take into account DST by using mid-day epochs.
								//Debug::Text('Row Gap: '. $row_date_gap, __FILE__, __LINE__, __METHOD__, 10);
								if ( $prev_data !== FALSE AND $row_date_gap > (86400 - 7200) ) {
									//Handle gaps between individual days with hours.
									$prev_data = $this->timesheetHandleDayGaps( (TTDate::getMiddleDayEpoch($prev_data['time_stamp']) + 86400), $data['time_stamp'], $format, $columns, $column_widths, $user_data, $data, $prev_data);
								} elseif ( $this->counter_i == 1 AND (TTDate::getMiddleDayEpoch($data['time_stamp']) - TTDate::getMiddleDayEpoch($data['pay_period_start_date'])) >= (86400 - 7200) ) {
									//Always fill gaps between the pay period start date and the date with time, even if not filtering by pay period.
									//Handle gaps before the first date with hours is displayed, only when filtering by pay period though.
									$prev_data = $this->timesheetHandleDayGaps( $data['pay_period_start_date'], $data['time_stamp'], $format, $columns, $column_widths, $user_data, $data, $prev_data );
								}

								//Check for gaps at the end of the date range and before the end of the pay period.
								//If we find one we have to increase $max_i by one so the last timesheetDayRow doesn't display the week totals.
								if ( $this->counter_i == $this->max_i AND (TTDate::getMiddleDayEpoch($data['pay_period_end_date']) - TTDate::getMiddleDayEpoch($data['time_stamp'])) >= 86400 ) {
									$this->max_i++;
								}

								$this->timesheetDayRow( $format, $columns, $column_widths, $user_data, $data, $prev_data );

								$prev_data = $data;
							} else {
								Debug::Text('Pay Period does not exist, skipping... ID: '. $data['pay_period_id'], __FILE__, __LINE__, __METHOD__, 10);
							}
						}

						//Check for gaps at the end of the date range and before the end of the pay period so we can fill them in. Only when filtering by pay period though.
						//as filtering by start/end date can result in a lot of data if they want show time for the last year but an employee was just hired.
						if ( isset($data['pay_period_end_date']) AND (TTDate::getMiddleDayEpoch($data['pay_period_end_date']) - TTDate::getMiddleDayEpoch($data['time_stamp'])) >= 86400 ) {
							//Handle gaps between the last day with hours and the end of the pay period.
							//Always fill gaps between the pay period end date and the current date with time, even if not filtering by pay period.
							$this->timesheetHandleDayGaps( (TTDate::getMiddleDayEpoch($data['time_stamp']) + 86400), $data['pay_period_end_date'], $format, $columns, $column_widths, $user_data, $data, $prev_data );
						}

						if ( isset($this->timesheet_totals) AND is_array($this->timesheet_totals) ) {
							//Display overall totals.
							$this->timesheetTotal( $column_widths, $this->timesheet_totals );

						}

						$this->timesheetSignature( $user_data, $data );

						$this->timesheetFooter( $pdf_created_date, $adjust_x, $adjust_y );
						unset($data, $prev_data);

						$page_count++;
					} else {
						if ( isset($filter_data['include_no_data_rows']) AND $filter_data['include_no_data_rows'] == 1 ) {
							$this->pdf->AddPage( 'P', 'LETTER' );
							$this->timesheetHeader( $user_data );
							$this->timesheetNoData();
							$this->timesheetFooter( $pdf_created_date, $adjust_x, $adjust_y );

							$page_count++;
						}
					}
				}

				$this->getProgressBarObject()->set( $this->getAMFMessageID(), $key );
				if ( ($key % 25) == 0 AND $this->isSystemLoadValid() == FALSE ) {
					return FALSE;
				}
				$key++;
			}

			//Make sure we display something if no data matches.
			if ( $page_count == 0 ) {
				$this->pdf->AddPage( 'P', 'LETTER' );
				$this->timesheetNoData();
				$this->timesheetFooter( $pdf_created_date, $adjust_x, $adjust_y );
			}

			$output = $this->pdf->Output('', 'S');

			return $output;
		}

		Debug::Text('No data to return...', __FILE__, __LINE__, __METHOD__, 10);
		return FALSE;
	}

	function _output( $format = NULL ) {
		if ( $format == 'pdf_timesheet' OR $format == 'pdf_timesheet_print'
				OR $format == 'pdf_timesheet_detail' OR $format == 'pdf_timesheet_detail_print' ) {
			return $this->_outputPDFTimesheet( $format );
		} else {
			return parent::_output( $format );
		}
	}

}
?>
