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
 * @package Modules\Report
 */
class Form941Report extends Report {

	protected $user_ids = array();

	/**
	 * Form941Report constructor.
	 */
	function __construct() {
		$this->title = TTi18n::getText('Form 941 Report');
		$this->file_name = 'form_941';

		parent::__construct();

		return TRUE;
	}

	/**
	 * @param string $user_id UUID
	 * @param string $company_id UUID
	 * @return bool
	 */
	protected function _checkPermissions( $user_id, $company_id ) {
		if ( $this->getPermissionObject()->Check('report', 'enabled', $user_id, $company_id )
				AND $this->getPermissionObject()->Check('report', 'view_form941', $user_id, $company_id ) ) {
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * @return bool
	 */
	protected function _validateConfig() {
		$config = $this->getConfig();

		//Make sure some time period is selected.
		if ( ( !isset($config['filter']['time_period']) AND !isset($config['filter']['pay_period_id']) ) OR ( isset($config['filter']['time_period']) AND isset($config['filter']['time_period']['time_period']) AND $config['filter']['time_period']['time_period'] == TTUUID::getZeroId() ) ) {
			$this->validator->isTrue( 'time_period', FALSE, TTi18n::gettext('No time period defined for this report') );
		}

		//Since we added social_security_tax_employer, and medicare_tax_employer form setup fields recently, make sure customers are aware if they aren't set the form isn't configured properly.
		$form_data = $this->formatFormConfig();
		if ( !( isset($form_data['social_security_tax_employer']['include_pay_stub_entry_account']) AND $form_data['social_security_tax_employer']['include_pay_stub_entry_account'] != TTUUID::getZeroID() ) ) {
			$this->validator->isTrue( 'form_setup', FALSE, TTi18n::gettext('Form Setup incomplete for Social Security Employer') );
		}

		if ( !( isset($form_data['medicare_tax_employer']['include_pay_stub_entry_account']) AND $form_data['medicare_tax_employer']['include_pay_stub_entry_account'] != TTUUID::getZeroID() ) ) {
			$this->validator->isTrue( 'form_setup', FALSE, TTi18n::gettext('Form Setup incomplete for Medicare Employer') );
		}

		return TRUE;
	}

	/**
	 * @param $name
	 * @param null $params
	 * @return array|bool|null
	 */
	protected function _getOptions( $name, $params = NULL ) {
		$retval = NULL;
		switch( $name ) {
			case 'output_format':
				$retval = array_merge( parent::getOptions('default_output_format'),
									array(
										'-1100-pdf_form' => TTi18n::gettext('Form'),
										//'-1120-efile' => TTi18n::gettext('eFile'),
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
										'-2000-legal_entity_id' => TTi18n::gettext('Legal Entity'),
										'-2010-user_status_id' => TTi18n::gettext('Employee Status'),
										'-2020-user_group_id' => TTi18n::gettext('Employee Group'),
										'-2030-user_title_id' => TTi18n::gettext('Employee Title'),
										'-2040-include_user_id' => TTi18n::gettext('Employee Include'),
										'-2050-exclude_user_id' => TTi18n::gettext('Employee Exclude'),
										'-2060-default_branch_id' => TTi18n::gettext('Default Branch'),
										'-2070-default_department_id' => TTi18n::gettext('Default Department'),
										'-2100-custom_filter' => TTi18n::gettext('Custom Filter'),

										//'-4020-exclude_ytd_adjustment' => TTi18n::gettext('Exclude YTD Adjustments'),

										'-5000-columns' => TTi18n::gettext('Display Columns'),
										'-5010-group' => TTi18n::gettext('Group By'),
										'-5020-sub_total' => TTi18n::gettext('SubTotal By'),
										'-5030-sort' => TTi18n::gettext('Sort By'),
								);
				break;
			case 'time_period':
				$retval = TTDate::getTimePeriodOptions( FALSE ); //Exclude Pay Period options, since we need a specific start/end date to properly calculate this form. They should use Tax Summary if they need per pay period instead.
				break;
			case 'date_columns':
				$retval = TTDate::getReportDateOptions( NULL, TTi18n::getText('Date'), 13, TRUE );
				break;
			case 'report_custom_column':
				if ( getTTProductEdition() >= TT_PRODUCT_PROFESSIONAL ) {
					$rcclf = TTnew( 'ReportCustomColumnListFactory' ); /** @var ReportCustomColumnListFactory $rcclf */
					// Because the Filter type is just only a filter criteria and not need to be as an option of Display Columns, Group By, Sub Total, Sort By dropdowns.
					// So just get custom columns with Selection and Formula.
					$custom_column_labels = $rcclf->getByCompanyIdAndTypeIdAndFormatIdAndScriptArray( $this->getUserObject()->getCompany(), $rcclf->getOptions('display_column_type_ids'), NULL, 'Form941Report', 'custom_column' );
					if ( is_array($custom_column_labels) ) {
						$retval = Misc::addSortPrefix( $custom_column_labels, 9500 );
					}
				}
				break;
			case 'report_custom_filters':
				if ( getTTProductEdition() >= TT_PRODUCT_PROFESSIONAL ) {
					$rcclf = TTnew( 'ReportCustomColumnListFactory' ); /** @var ReportCustomColumnListFactory $rcclf */
					$retval = $rcclf->getByCompanyIdAndTypeIdAndFormatIdAndScriptArray( $this->getUserObject()->getCompany(), $rcclf->getOptions('filter_column_type_ids'), NULL, 'Form941Report', 'custom_column' );
				}
				break;
			case 'report_dynamic_custom_column':
				if ( getTTProductEdition() >= TT_PRODUCT_PROFESSIONAL ) {
					$rcclf = TTnew( 'ReportCustomColumnListFactory' ); /** @var ReportCustomColumnListFactory $rcclf */
					$report_dynamic_custom_column_labels = $rcclf->getByCompanyIdAndTypeIdAndFormatIdAndScriptArray( $this->getUserObject()->getCompany(), $rcclf->getOptions('display_column_type_ids'), $rcclf->getOptions('dynamic_format_ids'), 'Form941Report', 'custom_column' );
					if ( is_array($report_dynamic_custom_column_labels) ) {
						$retval = Misc::addSortPrefix( $report_dynamic_custom_column_labels, 9700 );
					}
				}
				break;
			case 'report_static_custom_column':
				if ( getTTProductEdition() >= TT_PRODUCT_PROFESSIONAL ) {
					$rcclf = TTnew( 'ReportCustomColumnListFactory' ); /** @var ReportCustomColumnListFactory $rcclf */
					$report_static_custom_column_labels = $rcclf->getByCompanyIdAndTypeIdAndFormatIdAndScriptArray( $this->getUserObject()->getCompany(), $rcclf->getOptions('display_column_type_ids'), $rcclf->getOptions('static_format_ids'), 'Form941Report', 'custom_column' );
					if ( is_array($report_static_custom_column_labels) ) {
						$retval = Misc::addSortPrefix( $report_static_custom_column_labels, 9700 );
					}
				}
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
										'-1035-sin' => TTi18n::gettext('SIN/SSN'),
										'-1040-status' => TTi18n::gettext('Status'),
										'-1050-title' => TTi18n::gettext('Title'),
										'-1060-province' => TTi18n::gettext('Province/State'),
										'-1070-country' => TTi18n::gettext('Country'),
										'-1080-group' => TTi18n::gettext('Group'),
										'-1090-default_branch' => TTi18n::gettext('Default Branch'),
										'-1100-default_department' => TTi18n::gettext('Default Department'),
										'-1110-currency' => TTi18n::gettext('Currency'),
										//'-1111-current_currency' => TTi18n::gettext('Current Currency'),

										//'-1110-verified_time_sheet' => TTi18n::gettext('Verified TimeSheet'),
										//'-1120-pending_request' => TTi18n::gettext('Pending Requests'),

										//Handled in date_columns above.
										//'-1450-pay_period' => TTi18n::gettext('Pay Period'),

										'-1400-permission_control' => TTi18n::gettext('Permission Group'),
										'-1410-pay_period_schedule' => TTi18n::gettext('Pay Period Schedule'),
										'-1420-policy_group' => TTi18n::gettext('Policy Group'),
								);

				$retval = array_merge( $retval, $this->getOptions('date_columns'), (array)$this->getOptions('report_static_custom_column') );
				ksort($retval);
				break;
			case 'dynamic_columns':
				$retval = array(
										//Dynamic - Aggregate functions can be used
										'-2010-wages' => TTi18n::gettext('Wages'), //Line 2
										'-2020-income_tax' => TTi18n::gettext('Income Tax'), //Line 3
										'-2030-social_security_wages' => TTi18n::gettext('Taxable Social Security Wages'), //Line 5a
										'-2032-social_security_tip_wages' => TTi18n::gettext('Taxable Social Security Tips'), //Line 5b
										'-2033-social_security_total_wages' => TTi18n::gettext('Taxable Social Security Total Wages'), //Wages + Tip Wages.
										'-2038-social_security_tax' => TTi18n::gettext('Social Security - Employee'),
										'-2039-social_security_tax_employee_deducted' => TTi18n::gettext('Social Security - Employee (Deducted)'),
										'-2040-social_security_tax_employer' => TTi18n::gettext('Social Security - Employer'),
										'-2041-social_security_tax_employer_deducted' => TTi18n::gettext('Social Security - Employer (Deducted)'),
										'-2042-social_security_tax_total' => TTi18n::gettext('Social Security'),
										'-2050-medicare_wages' => TTi18n::gettext('Taxable Medicare Wages'), //Line 5c
										'-2051-medicare_additional_wages' => TTi18n::gettext('Taxable Medicare Additional Wages'), //Line 5d
										'-2055-additional_medicare_tax' => TTi18n::gettext('Medicare (Additional)'),
										'-2058-medicare_tax' => TTi18n::gettext('Medicare - Employee'),
										'-2059-medicare_tax_employee_deducted' => TTi18n::gettext('Medicare - Employee (Deducted)'),
										'-2060-medicare_tax_employer' => TTi18n::gettext('Medicare - Employer'),
										'-2061-medicare_tax_employer_deducted' => TTi18n::gettext('Medicare - Employer (Deducted)'),
										'-2062-medicare_tax_total' => TTi18n::gettext('Medicare'),
										'-2080-sick_wages' => TTi18n::gettext('Sick Pay'), //Line 7b
										'-2100-total_tax' => TTi18n::gettext('Total Taxes'), //Line 7b
							);
				break;
			case 'columns':
				$retval = array_merge( $this->getOptions('static_columns'), $this->getOptions('dynamic_columns'), (array)$this->getOptions('report_dynamic_custom_column') );
				ksort($retval);
				break;
			case 'column_format':
				//Define formatting function for each column.
				$columns = array_merge( $this->getOptions('dynamic_columns'), (array)$this->getOptions('report_custom_column') );
				if ( is_array($columns) ) {
					foreach($columns as $column => $name ) {
						$retval[$column] = 'currency';
					}
				}
				break;
			case 'aggregates':
				$retval = array();
				$dynamic_columns = array_keys( Misc::trimSortPrefix( array_merge( $this->getOptions('dynamic_columns'), (array)$this->getOptions('report_dynamic_custom_column') ) ) );
				if ( is_array($dynamic_columns ) ) {
					foreach( $dynamic_columns as $column ) {
						switch ( $column ) {
							default:
								$retval[$column] = 'sum';
						}
					}
				}

				break;
			case 'schedule_deposit':
				$retval = array(
									10 => TTi18n::gettext('Monthly'),
									20 => TTi18n::gettext('Semi-Weekly')
								);
				break;
			case 'templates':
				$retval = array(
										'-1010-by_month' => TTi18n::gettext('by Month'),
										'-1015-by_pay_period' => TTi18n::gettext('By Pay Period'),
										'-1020-by_employee' => TTi18n::gettext('by Employee'),
										'-1030-by_branch' => TTi18n::gettext('by Branch'),
										'-1040-by_department' => TTi18n::gettext('by Department'),
										'-1050-by_branch_by_department' => TTi18n::gettext('by Branch/Department'),

										'-1060-by_month_by_employee' => TTi18n::gettext('by Month/Employee'),
										'-1070-by_month_by_branch' => TTi18n::gettext('by Month/Branch'),
										'-1080-by_month_by_department' => TTi18n::gettext('by Month/Department'),
										'-1090-by_month_by_branch_by_department' => TTi18n::gettext('by Month/Branch/Department'),
								);

				break;
			case 'template_config':
				$template = strtolower( Misc::trimSortPrefix( $params['template'] ) );
				if ( isset($template) AND $template != '' ) {
					switch( $template ) {
						case 'default':
							//Proper settings to generate the form.
							//$retval['-1010-time_period']['time_period'] = 'last_quarter';

							$retval['columns'] = $this->getOptions('columns');

							$retval['group'][] = 'date_quarter_month';

							$retval['sort'][] = array('date_quarter_month' => 'asc');

							$retval['other']['grand_total'] = TRUE;

							break;
						default:
							Debug::Text(' Parsing template name: '. $template, __FILE__, __LINE__, __METHOD__, 10);
							$retval['-1010-time_period']['time_period'] = 'last_quarter';

							//Parse template name, and use the keywords separated by '+' to determine settings.
							$template_keywords = explode('+', $template );
							if ( is_array($template_keywords) ) {
								foreach( $template_keywords as $template_keyword ) {
									Debug::Text(' Keyword: '. $template_keyword, __FILE__, __LINE__, __METHOD__, 10);

									switch( $template_keyword ) {
										//Columns

										//Filter
										//Group By
										//SubTotal
										//Sort
										case 'by_month':
											$retval['columns'][] = 'date_month';

											$retval['group'][] = 'date_month';

											$retval['sort'][] = array('date_month' => 'asc');
											break;
										case 'by_pay_period':
											$retval['columns'][] = 'pay_period_transaction_date';

											$retval['group'][] = 'pay_period_transaction_date';

											$retval['sort'][] = array('pay_period_transaction_date' => 'asc');
											break;
										case 'by_employee':
											$retval['columns'][] = 'first_name';
											$retval['columns'][] = 'last_name';

											$retval['group'][] = 'first_name';
											$retval['group'][] = 'last_name';

											$retval['sort'][] = array('last_name' => 'asc');
											$retval['sort'][] = array('first_name' => 'asc');
											break;
										case 'by_branch':
											$retval['columns'][] = 'default_branch';

											$retval['group'][] = 'default_branch';

											$retval['sort'][] = array('default_branch' => 'asc');
											break;
										case 'by_department':
											$retval['columns'][] = 'default_department';

											$retval['group'][] = 'default_department';

											$retval['sort'][] = array('default_department' => 'asc');
											break;
										case 'by_branch_by_department':
											$retval['columns'][] = 'default_branch';
											$retval['columns'][] = 'default_department';

											$retval['group'][] = 'default_branch';
											$retval['group'][] = 'default_department';

											$retval['sub_total'][] = 'default_branch';

											$retval['sort'][] = array('default_branch' => 'asc');
											$retval['sort'][] = array('default_department' => 'asc');
											break;
										case 'by_month_by_employee':
											$retval['columns'][] = 'date_month';
											$retval['columns'][] = 'first_name';
											$retval['columns'][] = 'last_name';

											$retval['group'][] = 'date_month';
											$retval['group'][] = 'first_name';
											$retval['group'][] = 'last_name';

											$retval['sub_total'][] = 'date_month';

											$retval['sort'][] = array('date_month' => 'asc');
											$retval['sort'][] = array('last_name' => 'asc');
											$retval['sort'][] = array('first_name' => 'asc');
											break;
										case 'by_month_by_branch':
											$retval['columns'][] = 'date_month';
											$retval['columns'][] = 'default_branch';

											$retval['group'][] = 'date_month';
											$retval['group'][] = 'default_branch';

											$retval['sub_total'][] = 'date_month';

											$retval['sort'][] = array('date_month' => 'asc');
											$retval['sort'][] = array('default_branch' => 'asc');
											break;
										case 'by_month_by_department':
											$retval['columns'][] = 'date_month';
											$retval['columns'][] = 'default_department';

											$retval['group'][] = 'date_month';
											$retval['group'][] = 'default_department';

											$retval['sub_total'][] = 'date_month';

											$retval['sort'][] = array('date_month' => 'asc');
											$retval['sort'][] = array('default_department' => 'asc');
											break;
										case 'by_month_by_branch_by_department':
											$retval['columns'][] = 'date_month';
											$retval['columns'][] = 'default_branch';
											$retval['columns'][] = 'default_department';

											$retval['group'][] = 'date_month';
											$retval['group'][] = 'default_branch';
											$retval['group'][] = 'default_department';

											$retval['sub_total'][] = 'date_month';
											$retval['sub_total'][] = 'default_branch';

											$retval['sort'][] = array('date_month' => 'asc');
											$retval['sort'][] = array('default_branch' => 'asc');
											$retval['sort'][] = array('default_department' => 'asc');
											break;

									}
								}
							}

							//$retval['columns'] = array_merge( $retval['columns'], array_keys( Misc::trimSortPrefix( $this->getOptions('dynamic_columns') ) ) );
							$retval['columns'][] = 'wages'; //Basically Total Gross.
							$retval['columns'][] = 'income_tax';
							$retval['columns'][] = 'social_security_tax_total';
							$retval['columns'][] = 'medicare_tax_total';
							$retval['columns'][] = 'additional_medicare_tax';
							$retval['columns'][] = 'total_tax';

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

	/**
	 * @return mixed
	 */
	function getFormObject() {
		if ( !isset($this->form_obj['gf']) OR !is_object($this->form_obj['gf']) ) {
			//
			//Get all data for the form.
			//
			require_once( Environment::getBasePath() .'/classes/GovernmentForms/GovernmentForms.class.php');

			$gf = new GovernmentForms();

			$this->form_obj['gf'] = $gf;
			return $this->form_obj['gf'];
		}

		return $this->form_obj['gf'];
	}

	/**
	 * @return bool
	 */
	function clearFormObject() {
		$this->form_obj['gf'] = FALSE;

		return TRUE;
	}


	/**
	 * @return mixed
	 */
	function getF941Object() {
		if ( !isset($this->form_obj['f941']) OR !is_object($this->form_obj['f941']) ) {
			$this->form_obj['f941'] = $this->getFormObject()->getFormObject( '941', 'US' );
			return $this->form_obj['f941'];
		}

		return $this->form_obj['f941'];
	}

	/**
	 * @return bool
	 */
	function clearF941Object() {
		$this->form_obj['f941'] = FALSE;

		return TRUE;
	}

	/**
	 * @return mixed
	 */
	function getRETURN941Object() {
		if ( !isset($this->form_obj['return941']) OR !is_object($this->form_obj['return941']) ) {
			$this->form_obj['return941'] = $this->getFormObject()->getFormObject( 'RETURN941', 'US' );
			return $this->form_obj['return941'];
		}

		return $this->form_obj['return941'];
	}

	/**
	 * @return bool
	 */
	function clearRETURN941Object() {
		$this->form_obj['return941'] = FALSE;

		return TRUE;
	}

	/**
	 * @return array
	 */
	function formatFormConfig() {
		$default_include_exclude_arr = array( 'include_pay_stub_entry_account' => array(), 'exclude_pay_stub_entry_account' => array() );

		$default_arr = array(
				'wages' => $default_include_exclude_arr,
				'income_tax' => $default_include_exclude_arr,
				'social_security_wages' => $default_include_exclude_arr,
				'social_security_tips' => $default_include_exclude_arr,
				'medicare_wages' => $default_include_exclude_arr,
				'sick_wages' => $default_include_exclude_arr,
			);

		$retarr = array_merge( $default_arr, (array)$this->getFormConfig() );
		return $retarr;
	}

	/**
	 * @param $last_date
	 * @param $quarter
	 * @return false|int
	 */
	function get12thOfLastMoQ( $last_date, $quarter ) {
		$quarter_dates = TTDate::getYearQuarters( $last_date, $quarter );

		$target_date = mktime(0, 0, 0, TTDate::getMonth( $quarter_dates['end'] ), 12, TTDate::getYear( $quarter_dates['end'] ) ); //Should be the 12 day of the last month of the quarter.

		Debug::Text(' 12th of Last MoQ Date: '. TTDate::getDate('DATE', $target_date ) .' Quarter: '. $quarter .' Year: '. TTDate::getDate('DATE', $last_date ), __FILE__, __LINE__, __METHOD__, 10);
		return $target_date;
	}

	/**
	 * Get raw data for report
	 * @param null $format
	 * @return bool
	 */
	function _getData( $format = NULL ) {
		$this->tmp_data = array( 'pay_stub_entry' => array(), 'ytd_pay_stub_entry' => array() );

		$filter_data = $this->getFilterConfig();
		$form_data = $this->formatFormConfig();

		require_once( Environment::getBasePath().'/classes/payroll_deduction/PayrollDeduction.class.php');
		$pd_obj = new PayrollDeduction('US', 'WA'); //State doesn't matter.
		$pd_obj->setDate($filter_data['end_date']);

		$social_security_wage_limit = $pd_obj->getSocialSecurityMaximumEarnings();
		$social_security_maximum_contribution = $pd_obj->getSocialSecurityMaximumContribution( 'employee' );
		$medicare_additional_threshold_limit = $pd_obj->getMedicareAdditionalEmployerThreshold();
		Debug::Text('Social Security Wage Limit: '. $social_security_wage_limit .' Medicare Threshold: '. $medicare_additional_threshold_limit .' Date: '. TTDate::getDate('DATE', $filter_data['end_date'] ), __FILE__, __LINE__, __METHOD__, 10);

		//Get the total number of employees paid for the pay period that covers the 12th day of the last month of the quarter. (Part 1, Line 1 on the 941 form)
		//  If the pay peiod starts on June 12th and end on June 26th, but isn't paid until July 5th, that would technically be excluded from the 941 report in Quarter 2, but we need to count the employees in that pay period still.
		//  So that is why we need to run a completely separate query to get this data, rather than trying to count the employees with dollars appearing on the report.
		$report_quarter = TTDate::getYearQuarter( $filter_data['end_date'] );

		$employee_count_date = $this->get12thOfLastMoQ( $filter_data['end_date'], $report_quarter );
		Debug::Text('Employee Count Date: '. TTDate::getDate('DATE', $employee_count_date ), __FILE__, __LINE__, __METHOD__, 10);

		$pplf = TTnew('PayPeriodListFactory'); /** @var PayPeriodListFactory $pplf */
		$employee_count_pay_periods = $pplf->getIDSByListFactory( $pplf->getByCompanyIdAndOverlapStartDateAndEndDate( $this->getUserObject()->getCompany(), $employee_count_date, $employee_count_date ) );

		$pslf = TTnew( 'PayStubListFactory' ); /** @var PayStubListFactory $pslf */
		$employee_count_filter_data = $filter_data;
		$employee_count_filter_data['pay_period_id'] = $employee_count_pay_periods;
		unset( $employee_count_filter_data['start_date'], $employee_count_filter_data['end_date']);
		$pslf->getAPISearchByCompanyIdAndArrayCriteria( $this->getUserObject()->getCompany(), $employee_count_filter_data );
		Debug::Text(' Pay Stub Rows: '. $pslf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
		if ( $pslf->getRecordCount() > 0 ) {
			foreach( $pslf as $ps_obj ) {
				if ( $ps_obj->getStatus() != 25 ) {
					$legal_entity_id = $ps_obj->getColumn( 'legal_entity_id' );
					$this->user_ids[ $legal_entity_id ][ $ps_obj->getUser() ] = TRUE; //Used for counting total number of employees.
				}
			}
		}
		unset( $pplf, $pslf, $legal_entity_id, $employee_count_date, $employee_count_pay_periods );

		//Need to get totals up to the beginning of this quarter so we can determine if any employees have exceeded the social security/additional medicare limit.
		$pself = TTnew( 'PayStubEntryListFactory' ); /** @var PayStubEntryListFactory $pself */
		$ytd_filter_data = $filter_data;
		$ytd_filter_data['end_date'] = ( $ytd_filter_data['start_date'] - 1 );
		$ytd_filter_data['start_date'] = TTDate::getBeginYearEpoch( $ytd_filter_data['start_date'] );
		$pself->getAPIReportByCompanyIdAndArrayCriteria( $this->getUserObject()->getCompany(), $ytd_filter_data );
		Debug::Text('YTD Filter Data: Start Date: '. TTDate::getDate('DATE', $ytd_filter_data['start_date'] ) .' End Date: '. TTDate::getDate('DATE', $ytd_filter_data['end_date'] ) .' Rows: '. $pself->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
		//Debug::Arr($ytd_filter_data, 'YTD Filter Data: Row Count: '.	$pself->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
		if ( $pself->getRecordCount() > 0 ) {
			foreach( $pself as $pse_obj ) {
				$user_id = $pse_obj->getColumn('user_id'); //Make sure we don't add this to the unique user_id list.
				//Always use middle day epoch, otherwise multiple entries could exist for the same day.
				$date_stamp = TTDate::getMiddleDayEpoch( TTDate::strtotime( $pse_obj->getColumn('pay_stub_transaction_date') ) );
				$branch = $pse_obj->getColumn('default_branch');
				$department = $pse_obj->getColumn('default_department');
				$pay_stub_entry_name_id = $pse_obj->getPayStubEntryNameId();

				if ( !isset($this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]) ) {
					$this->tmp_data['pay_stub_entry'][$user_id][$date_stamp] = array(
																'pay_period_start_date' => strtotime( $pse_obj->getColumn('pay_stub_start_date') ),
																'pay_period_end_date' => strtotime( $pse_obj->getColumn('pay_stub_end_date') ),
																'pay_period_transaction_date' => strtotime( $pse_obj->getColumn('pay_stub_transaction_date') ),
																'pay_period' => strtotime( $pse_obj->getColumn('pay_stub_transaction_date') ),
															);
				}

				if ( isset($this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['psen_ids'][$pay_stub_entry_name_id]) ) {
					$this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['psen_ids'][$pay_stub_entry_name_id] = bcadd( $this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['psen_ids'][$pay_stub_entry_name_id], $pse_obj->getColumn('amount') );
				} else {
					$this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['psen_ids'][$pay_stub_entry_name_id] = $pse_obj->getColumn('amount');
				}
			}

			if ( isset($this->tmp_data['pay_stub_entry']) AND is_array($this->tmp_data['pay_stub_entry']) ) {
				foreach($this->tmp_data['pay_stub_entry'] as $user_id => $data_a) {
					foreach($data_a as $date_stamp => $data_b) {
						if ( !isset($this->tmp_data['ytd_pay_stub_entry'][$user_id]['social_security_wages']) ) {
							$this->tmp_data['ytd_pay_stub_entry'][$user_id]['social_security_wages'] = 0;
						}
						$this->tmp_data['ytd_pay_stub_entry'][$user_id]['social_security_wages'] = bcadd( $this->tmp_data['ytd_pay_stub_entry'][$user_id]['social_security_wages'], Misc::calculateMultipleColumns( $data_b['psen_ids'], $form_data['social_security_wages']['include_pay_stub_entry_account'], $form_data['social_security_wages']['exclude_pay_stub_entry_account'] ) );

						if ( !isset($this->tmp_data['ytd_pay_stub_entry'][$user_id]['social_security_tip_wages']) ) {
							$this->tmp_data['ytd_pay_stub_entry'][$user_id]['social_security_tip_wages'] = 0;
						}
						$this->tmp_data['ytd_pay_stub_entry'][$user_id]['social_security_tip_wages'] = bcadd( $this->tmp_data['ytd_pay_stub_entry'][$user_id]['social_security_tip_wages'], Misc::calculateMultipleColumns( $data_b['psen_ids'], $form_data['social_security_tips']['include_pay_stub_entry_account'], $form_data['social_security_tips']['exclude_pay_stub_entry_account'] ) );

						//Combine Social Security and Tip wages.
						$this->tmp_data['ytd_pay_stub_entry'][$user_id]['social_security_total_wages'] = bcadd( $this->tmp_data['ytd_pay_stub_entry'][$user_id]['social_security_wages'], $this->tmp_data['ytd_pay_stub_entry'][$user_id]['social_security_tip_wages'] );

						if ( !isset($this->tmp_data['ytd_pay_stub_entry'][$user_id]['social_security_tax']) ) {
							$this->tmp_data['ytd_pay_stub_entry'][$user_id]['social_security_tax'] = 0;
						}
						$this->tmp_data['ytd_pay_stub_entry'][$user_id]['social_security_tax'] = bcadd( $this->tmp_data['ytd_pay_stub_entry'][$user_id]['social_security_tax'], Misc::calculateMultipleColumns( $data_b['psen_ids'], $form_data['social_security_tax']['include_pay_stub_entry_account'], $form_data['social_security_tax']['exclude_pay_stub_entry_account'] ) );

						//Handle additional medicare wages in excess of 200,000
						if ( !isset($this->tmp_data['ytd_pay_stub_entry'][$user_id]['medicare_wages']) ) {
							$this->tmp_data['ytd_pay_stub_entry'][$user_id]['medicare_wages'] = 0;
						}
						$this->tmp_data['ytd_pay_stub_entry'][$user_id]['medicare_wages'] = bcadd( $this->tmp_data['ytd_pay_stub_entry'][$user_id]['medicare_wages'], Misc::calculateMultipleColumns( $data_b['psen_ids'], $form_data['medicare_wages']['include_pay_stub_entry_account'], $form_data['medicare_wages']['exclude_pay_stub_entry_account'] ) );
					}
				}
			}
			//Debug::Arr($this->tmp_data['ytd_pay_stub_entry'], 'YTD Tmp Raw Data: ', __FILE__, __LINE__, __METHOD__, 10);
		}
		unset($pse_obj, $user_id, $date_stamp, $branch, $department, $pay_stub_entry_name_id, $this->tmp_data['pay_stub_entry'], $data_a, $data_b );



		//Get just the data for the quarter now.
		$pself->getAPIReportByCompanyIdAndArrayCriteria( $this->getUserObject()->getCompany(), $filter_data );
		Debug::Text(' Pay Stub Entry Rows: '. $pself->getRecordCount() .' Start Date: '. TTDate::getDate('DATE', $filter_data['start_date']) .' End Date: '. TTDate::getDate('DATE', $filter_data['end_date']), __FILE__, __LINE__, __METHOD__, 10);
		if ( $pself->getRecordCount() > 0 ) {
			foreach( $pself as $pse_obj ) {
				$legal_entity_id = $pse_obj->getColumn('legal_entity_id');
				$user_id = $pse_obj->getColumn('user_id');
				$date_stamp = TTDate::getMiddleDayEpoch( TTDate::strtotime( $pse_obj->getColumn('pay_stub_transaction_date') ) ); //Always use middle day epoch, otherwise multiple entries could exist for the same day.
				$pay_stub_entry_name_id = $pse_obj->getPayStubEntryNameId();

				if ( !isset($this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]) ) {
					$this->tmp_data['pay_stub_entry'][$user_id][$date_stamp] = array(
																'legal_entity_id' => $legal_entity_id,
																'pay_period_start_date' => strtotime( $pse_obj->getColumn('pay_stub_start_date') ),
																'pay_period_end_date' => strtotime( $pse_obj->getColumn('pay_stub_end_date') ),
																'pay_period_transaction_date' => strtotime( $pse_obj->getColumn('pay_stub_transaction_date') ),
																'pay_period' => strtotime( $pse_obj->getColumn('pay_stub_transaction_date') ),
															);
				}

				if ( isset($this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['psen_ids'][$pay_stub_entry_name_id]) ) {
					$this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['psen_ids'][$pay_stub_entry_name_id] = bcadd( $this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['psen_ids'][$pay_stub_entry_name_id], $pse_obj->getColumn('amount') );
				} else {
					$this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['psen_ids'][$pay_stub_entry_name_id] = $pse_obj->getColumn('amount');
				}
			}
			unset( $legal_entity_id, $user_id, $date_stamp, $pay_stub_entry_name_id, $pse_obj );

			if ( isset($this->tmp_data['pay_stub_entry']) AND is_array($this->tmp_data['pay_stub_entry']) ) {
				foreach($this->tmp_data['pay_stub_entry'] as $user_id => $data_a) {
					foreach($data_a as $date_stamp => $data_b) {
						$legal_entity_id = $data_b['legal_entity_id'];
						$quarter_month = TTDate::getYearQuarterMonthNumber( $date_stamp );
						//Debug::Text('Quarter Month: '. $quarter_month .' Epoch: '. TTDate::getDate('DATE', $date_stamp), __FILE__, __LINE__, __METHOD__, 10);

						$this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['wages']					= ( isset($form_data['wages']) ) ? Misc::calculateMultipleColumns( $data_b['psen_ids'], $form_data['wages']['include_pay_stub_entry_account'], $form_data['wages']['exclude_pay_stub_entry_account'] ) : 0;
						$this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['income_tax']				= ( isset($form_data['income_tax']) ) ? Misc::calculateMultipleColumns( $data_b['psen_ids'], $form_data['income_tax']['include_pay_stub_entry_account'], $form_data['income_tax']['exclude_pay_stub_entry_account'] ) : 0;

						//Because employees can be excluded from Social Security/Medicare, only include wage amounts if the SS tax is not 0.
						$this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['social_security_tax_employee_deducted']	= ( isset($form_data['social_security_tax']) ) ? Misc::calculateMultipleColumns( $data_b['psen_ids'], $form_data['social_security_tax']['include_pay_stub_entry_account'], $form_data['social_security_tax']['exclude_pay_stub_entry_account'] ) : 0;
						$this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['social_security_tax_employer_deducted']	= ( isset($form_data['social_security_tax_employer']) ) ? Misc::calculateMultipleColumns( $data_b['psen_ids'], $form_data['social_security_tax_employer']['include_pay_stub_entry_account'], $form_data['social_security_tax_employer']['exclude_pay_stub_entry_account'] ) : 0;
						if ( ( isset($form_data['social_security_tax']['include_pay_stub_entry_account']) AND !is_array($form_data['social_security_tax']['include_pay_stub_entry_account']) ) OR $this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['social_security_tax_employee_deducted'] != 0 ) {
							$this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['social_security_wages'] = ( isset($form_data['social_security_wages']) ) ? Misc::calculateMultipleColumns( $data_b['psen_ids'], $form_data['social_security_wages']['include_pay_stub_entry_account'], $form_data['social_security_wages']['exclude_pay_stub_entry_account'] ) : 0;
						} else {
							$this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['social_security_wages'] = 0;
						}

						if ( ( isset($form_data['social_security_tips']['include_pay_stub_entry_account']) AND !is_array($form_data['social_security_tips']['include_pay_stub_entry_account']) OR $this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['social_security_tax_employee_deducted'] != 0 ) ) {
							$this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['social_security_tip_wages'] = ( isset($form_data['social_security_tips']) ) ? Misc::calculateMultipleColumns( $data_b['psen_ids'], $form_data['social_security_tips']['include_pay_stub_entry_account'], $form_data['social_security_tips']['exclude_pay_stub_entry_account'] ) : 0;
						} else {
							$this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['social_security_tip_wages'] = 0;
						}

						$this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['social_security_total_wages'] = bcadd( $this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['social_security_wages'], $this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['social_security_tip_wages'] ); //Includes Wages and Tips

						if ( !isset($this->tmp_data['ytd_pay_stub_entry'][$user_id]['social_security_total_wages']) ) {
							$this->tmp_data['ytd_pay_stub_entry'][$user_id]['social_security_total_wages'] = 0;
						}
						if ( !isset($this->tmp_data['ytd_pay_stub_entry'][$user_id]['social_security_tax']) ) {
							$this->tmp_data['ytd_pay_stub_entry'][$user_id]['social_security_tax'] = 0;
						}

						if ( !isset($this->tmp_data['ytd_pay_stub_entry'][$user_id]['social_security_tip_wages']) ) {
							$this->tmp_data['ytd_pay_stub_entry'][$user_id]['social_security_tip_wages'] = 0;
						}

						//Handle maximum amount for social security tips.
						// Calculate tips first as it should favor the employee in that case?
						if ( $this->tmp_data['ytd_pay_stub_entry'][$user_id]['social_security_total_wages'] < $social_security_wage_limit ) {
							if ( bcadd($this->tmp_data['ytd_pay_stub_entry'][$user_id]['social_security_total_wages'], $this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['social_security_tip_wages'] ) > $social_security_wage_limit ) {
								$this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['social_security_tip_wages'] = bcsub( $this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['social_security_tip_wages'], bcsub( bcadd($this->tmp_data['ytd_pay_stub_entry'][$user_id]['social_security_total_wages'], $this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['social_security_tip_wages'] ), $social_security_wage_limit) );
								$this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['social_security_total_wages'] = bcadd( $this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['social_security_wages'], $this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['social_security_tip_wages'] );
								$this->tmp_data['ytd_pay_stub_entry'][$user_id]['social_security_total_wages'] = $social_security_wage_limit;
							} else {
								$this->tmp_data['ytd_pay_stub_entry'][$user_id]['social_security_total_wages'] = bcadd( $this->tmp_data['ytd_pay_stub_entry'][$user_id]['social_security_total_wages'], $this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['social_security_tip_wages'] );
							}
						} else {
							$this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['social_security_total_wages'] = bcsub( $this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['social_security_total_wages'], $this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['social_security_tip_wages'] );
							$this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['social_security_tip_wages'] = 0;
						}


						//Handle maximum amount for social security wages.
						if ( $this->tmp_data['ytd_pay_stub_entry'][$user_id]['social_security_total_wages'] < $social_security_wage_limit ) {
							if ( bcadd($this->tmp_data['ytd_pay_stub_entry'][$user_id]['social_security_total_wages'], $this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['social_security_wages'] ) > $social_security_wage_limit ) {
								$this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['social_security_wages'] = bcsub( $this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['social_security_wages'], bcsub( bcadd($this->tmp_data['ytd_pay_stub_entry'][$user_id]['social_security_total_wages'], $this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['social_security_wages'] ), $social_security_wage_limit) );
								$this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['social_security_total_wages'] = bcadd( $this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['social_security_wages'], $this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['social_security_tip_wages'] );
								$this->tmp_data['ytd_pay_stub_entry'][$user_id]['social_security_total_wages'] = $social_security_wage_limit;
							} else {
								$this->tmp_data['ytd_pay_stub_entry'][$user_id]['social_security_total_wages'] = bcadd( $this->tmp_data['ytd_pay_stub_entry'][$user_id]['social_security_total_wages'], $this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['social_security_wages'] );
							}
						} else {
							$this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['social_security_total_wages'] = bcsub( $this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['social_security_total_wages'], $this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['social_security_wages'] );
							$this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['social_security_wages'] = 0;
						}

						//Social Security Tax must be calculated after the wages are fully adjusted.
						//Calculate the social security based on the wages, not what the employee actually had deducted as the IRS doesn't care about that for the 941 Form. The W2's reconcile that part.
						//  Make sure we cap the YTD social security amount at the proper maximum as well, since just basing it off wages can result in fractions of a cent difference per pay period when the employee reaches the cap.
						//  The pay stubs handle it this way too, so this makes the two values match. Essentially this can result in positive fractions of a cent for the first 3 quarters (ie: 0.07) then a negative for the last quarter (ie: -0.07) if employees have reached the cap.
						$this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['social_security_tax'] = round( bcmul( $this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['social_security_total_wages'], bcdiv( $this->getF941Object()->social_security_rate, 2 ) ), 2 ); //Rate is employee & employer rate, so divide by two so we can split it up separately.
						if ( bcadd( $this->tmp_data['ytd_pay_stub_entry'][$user_id]['social_security_tax'], $this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['social_security_tax'] ) > $social_security_maximum_contribution ) {
							$this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['social_security_tax'] = bcsub( $social_security_maximum_contribution, $this->tmp_data['ytd_pay_stub_entry'][$user_id]['social_security_tax'] );
						}
						$this->tmp_data['ytd_pay_stub_entry'][$user_id]['social_security_tax'] = bcadd( $this->tmp_data['ytd_pay_stub_entry'][$user_id]['social_security_tax'], $this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['social_security_tax'] );
						$this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['social_security_tax_employer'] = $this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['social_security_tax'];


						$this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['medicare_tax_employee_deducted'] = ( isset($form_data['medicare_tax']) ) ? Misc::calculateMultipleColumns( $data_b['psen_ids'], $form_data['medicare_tax']['include_pay_stub_entry_account'], $form_data['medicare_tax']['exclude_pay_stub_entry_account'] ) : 0;
						$this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['medicare_tax_employer_deducted'] = ( isset($form_data['medicare_tax_employer']) ) ? Misc::calculateMultipleColumns( $data_b['psen_ids'], $form_data['medicare_tax_employer']['include_pay_stub_entry_account'], $form_data['medicare_tax_employer']['exclude_pay_stub_entry_account'] ) : 0;
						if ( ( isset($form_data['medicare_tax']['include_pay_stub_entry_account']) AND !is_array($form_data['medicare_tax']['include_pay_stub_entry_account']) ) OR $this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['medicare_tax_employee_deducted'] != 0 ) {
							$this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['medicare_wages'] = ( isset($form_data['medicare_wages']) ) ? Misc::calculateMultipleColumns( $data_b['psen_ids'], $form_data['medicare_wages']['include_pay_stub_entry_account'], $form_data['medicare_wages']['exclude_pay_stub_entry_account'] ) : 0;
						} else {
							$this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['medicare_wages'] = 0;
						}

						//Medicare Tax must be calculated after the wages are fully adjusted.
						//Calculate the medicare based on the wages, not what the employee actually had deducted as the IRS doesn't care about that for the 941 Form. The W2's reconcile that part.
						$this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['medicare_tax'] = $this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['medicare_tax_employer'] = round( bcmul( $this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['medicare_wages'], bcdiv( $this->getF941Object()->medicare_rate, 2 ) ), 2 ); //Rate is employeer & employer rate, so divide by two so we can split it up separately.


						//Handle medicare additional wage limit, only consider wages earned above the threshold to be "medicare_additional_wages"
						$this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['medicare_additional_wages'] = 0;
						if ( !isset($this->tmp_data['ytd_pay_stub_entry'][$user_id]['medicare_wages']) ) {
							$this->tmp_data['ytd_pay_stub_entry'][$user_id]['medicare_wages'] = 0;
						}

						if ( $this->tmp_data['ytd_pay_stub_entry'][$user_id]['medicare_wages'] > $medicare_additional_threshold_limit ) {
							$this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['medicare_additional_wages'] = $this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['medicare_wages'];
						} else {
							if ( ( ( isset($form_data['medicare_tax']['include_pay_stub_entry_account']) AND !is_array($form_data['medicare_tax']['include_pay_stub_entry_account']) ) OR $this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['medicare_tax'] != 0 )
									AND bcadd($this->tmp_data['ytd_pay_stub_entry'][$user_id]['medicare_wages'], $this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['medicare_wages']) > $medicare_additional_threshold_limit	) {
								$this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['medicare_additional_wages'] = bcsub( bcadd($this->tmp_data['ytd_pay_stub_entry'][$user_id]['medicare_wages'], $this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['medicare_wages']), $medicare_additional_threshold_limit );
							} else {
								$this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['medicare_additional_wages'] = 0;
							}
						}
						$this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['additional_medicare_tax'] = round( bcmul( $this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['medicare_additional_wages'], $this->getF941Object()->medicare_additional_rate ), 2 );

						$this->tmp_data['ytd_pay_stub_entry'][$user_id]['medicare_wages'] = bcadd( $this->tmp_data['ytd_pay_stub_entry'][$user_id]['medicare_wages'], $this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['medicare_wages'] );
						//Debug::Text('User ID: '. $user_id .' DateStamp: '. TTDate::getDate('DATE', $date_stamp ) .' YTD Medicare Additional Wages: '. $this->tmp_data['ytd_pay_stub_entry'][$user_id]['medicare_wages'] .' This Pay Stub: '. $this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['medicare_additional_wages'], __FILE__, __LINE__, __METHOD__, 10);

						$this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['sick_wages']				= ( isset($form_data['sick_wages']) ) ? Misc::calculateMultipleColumns( $data_b['psen_ids'], $form_data['sick_wages']['include_pay_stub_entry_account'], $form_data['sick_wages']['exclude_pay_stub_entry_account'] ) : 0;

						$this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['social_security_tax_total'] = bcadd( $this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['social_security_tax'], $this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['social_security_tax_employer'] );
						$this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['medicare_tax_total'] = bcadd( $this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['medicare_tax'], $this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['medicare_tax_employer'] ); //This *does not* include the additional_medicare_tax.
						$this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['total_tax'] = bcadd( $this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['income_tax'], bcadd( $this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['social_security_tax_total'], bcadd( $this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['medicare_tax_total'], $this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['additional_medicare_tax'] ) ) );

						//Separate data used for reporting, grouping, sorting, from data specific used for the Form.
						if ( !isset($this->form_data['pay_period'][$legal_entity_id][$quarter_month][$date_stamp]) ) {
							$this->form_data['pay_period'][$legal_entity_id][$quarter_month][$date_stamp] = Misc::preSetArrayValues( array(), array('l2', 'l3', 'l5a', 'l5b', 'l5c', 'l5d', 'l7', 'l9', 'income_tax', 'medicare_tax_employee_deducted', 'medicare_tax_employer_deducted', 'medicare_tax', 'social_security_tax_employee_deducted', 'social_security_tax_employer_deducted', 'social_security_tax', 'l5a2', 'l5b2', 'l5c2', 'l5d', 'l8', 'l10' ), 0 );
						}

						$this->form_data['pay_period'][$legal_entity_id][$quarter_month][$date_stamp]['social_security_tax_employee_deducted'] = bcadd( $this->form_data['pay_period'][$legal_entity_id][$quarter_month][$date_stamp]['social_security_tax_employee_deducted'], $this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['social_security_tax_employee_deducted'] );
						$this->form_data['pay_period'][$legal_entity_id][$quarter_month][$date_stamp]['social_security_tax_employer_deducted'] = bcadd( $this->form_data['pay_period'][$legal_entity_id][$quarter_month][$date_stamp]['social_security_tax_employer_deducted'], $this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['social_security_tax_employer_deducted'] );
						//Debug::Text('User: '. $user_id .' Date: '. TTDate::getDate('DATE', $date_stamp) .' SS Amounts: YTD Wages: '. $this->tmp_data['ytd_pay_stub_entry'][$user_id]['social_security_total_wages'] .' Wages: '. $this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['social_security_wages'] .' Pre-Rounded: '. bcmul( $this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['social_security_total_wages'], bcdiv( $this->getF941Object()->social_security_rate, 2 ) ) .' Rounded: '. $this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['social_security_tax'] .' Deducted: '. $this->form_data['pay_period'][$legal_entity_id][$quarter_month][$date_stamp]['social_security_tax_employee_deducted'], __FILE__, __LINE__, __METHOD__, 10);

						$this->form_data['pay_period'][$legal_entity_id][$quarter_month][$date_stamp]['medicare_tax_employee_deducted'] = bcadd( $this->form_data['pay_period'][$legal_entity_id][$quarter_month][$date_stamp]['medicare_tax_employee_deducted'], $this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['medicare_tax_employee_deducted'] );
						$this->form_data['pay_period'][$legal_entity_id][$quarter_month][$date_stamp]['medicare_tax_employer_deducted'] = bcadd( $this->form_data['pay_period'][$legal_entity_id][$quarter_month][$date_stamp]['medicare_tax_employer_deducted'], $this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['medicare_tax_employer_deducted'] );

						$this->form_data['pay_period'][$legal_entity_id][$quarter_month][$date_stamp]['l2'] = bcadd( $this->form_data['pay_period'][$legal_entity_id][$quarter_month][$date_stamp]['l2'], $this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['wages'] );
						$this->form_data['pay_period'][$legal_entity_id][$quarter_month][$date_stamp]['l3'] = bcadd( $this->form_data['pay_period'][$legal_entity_id][$quarter_month][$date_stamp]['l3'], $this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['income_tax'] );
						$this->form_data['pay_period'][$legal_entity_id][$quarter_month][$date_stamp]['l5a'] = bcadd( $this->form_data['pay_period'][$legal_entity_id][$quarter_month][$date_stamp]['l5a'], $this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['social_security_wages'] );
						$this->form_data['pay_period'][$legal_entity_id][$quarter_month][$date_stamp]['l5b'] = bcadd( $this->form_data['pay_period'][$legal_entity_id][$quarter_month][$date_stamp]['l5b'], $this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['social_security_tip_wages'] );
						$this->form_data['pay_period'][$legal_entity_id][$quarter_month][$date_stamp]['l5c'] = bcadd( $this->form_data['pay_period'][$legal_entity_id][$quarter_month][$date_stamp]['l5c'], $this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['medicare_wages'] );
						$this->form_data['pay_period'][$legal_entity_id][$quarter_month][$date_stamp]['l5d'] = bcadd( $this->form_data['pay_period'][$legal_entity_id][$quarter_month][$date_stamp]['l5d'], $this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['medicare_additional_wages'] );

						$this->form_data['pay_period'][$legal_entity_id][$quarter_month][$date_stamp]['l5f'] = 0; //Not implemented currently.

						//Calculated fields, make sure we don't use += on these.
						//Calculate amounts for Schedule B.
						$this->form_data['pay_period'][$legal_entity_id][$quarter_month][$date_stamp]['l5a2'] = bcmul( $this->form_data['pay_period'][$legal_entity_id][$quarter_month][$date_stamp]['l5a'], $this->getF941Object()->social_security_rate );
						$this->form_data['pay_period'][$legal_entity_id][$quarter_month][$date_stamp]['l5b2'] = bcmul( $this->form_data['pay_period'][$legal_entity_id][$quarter_month][$date_stamp]['l5b'], $this->getF941Object()->social_security_rate );
						$this->form_data['pay_period'][$legal_entity_id][$quarter_month][$date_stamp]['l5c2'] = bcmul( $this->form_data['pay_period'][$legal_entity_id][$quarter_month][$date_stamp]['l5c'], $this->getF941Object()->medicare_rate );
						$this->form_data['pay_period'][$legal_entity_id][$quarter_month][$date_stamp]['l5d2'] = bcmul( $this->form_data['pay_period'][$legal_entity_id][$quarter_month][$date_stamp]['l5d'], $this->getF941Object()->medicare_additional_rate );

						$this->form_data['pay_period'][$legal_entity_id][$quarter_month][$date_stamp]['l5e'] = bcadd( $this->form_data['pay_period'][$legal_entity_id][$quarter_month][$date_stamp]['l5a2'], bcadd( $this->form_data['pay_period'][$legal_entity_id][$quarter_month][$date_stamp]['l5b2'], bcadd( $this->form_data['pay_period'][$legal_entity_id][$quarter_month][$date_stamp]['l5c2'], $this->form_data['pay_period'][$legal_entity_id][$quarter_month][$date_stamp]['l5d2']) ) );

						$this->form_data['pay_period'][$legal_entity_id][$quarter_month][$date_stamp]['l6'] = bcadd( $this->form_data['pay_period'][$legal_entity_id][$quarter_month][$date_stamp]['l3'], bcadd( $this->form_data['pay_period'][$legal_entity_id][$quarter_month][$date_stamp]['l5e'], $this->form_data['pay_period'][$legal_entity_id][$quarter_month][$date_stamp]['l5f'] ) );

						//Total up Social Security / Medicare Taxes withheld from the employee only, then double them for the employer portion, this helps calculate l7 further down.
						// Since medicare_tax does not include the additional_medicare_tax, we must manually re-add it here.
						// The form setup for Medicare Taxes Witheld should only ever be setup for whatever the employee had withheld, not employee and employer.
						$this->form_data['pay_period'][$legal_entity_id][$quarter_month][$date_stamp]['income_tax'] = bcadd( $this->form_data['pay_period'][$legal_entity_id][$quarter_month][$date_stamp]['income_tax'], $this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['income_tax'] );
						$this->form_data['pay_period'][$legal_entity_id][$quarter_month][$date_stamp]['social_security_tax'] = bcadd( $this->form_data['pay_period'][$legal_entity_id][$quarter_month][$date_stamp]['social_security_tax'], bcmul( $this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['social_security_tax'], 2) );
						$this->form_data['pay_period'][$legal_entity_id][$quarter_month][$date_stamp]['medicare_tax'] = bcadd( $this->form_data['pay_period'][$legal_entity_id][$quarter_month][$date_stamp]['medicare_tax'], bcadd( bcmul( $this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['medicare_tax'], 2 ), $this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['additional_medicare_tax'] ) );

						$this->form_data['pay_period'][$legal_entity_id][$quarter_month][$date_stamp]['l10'] = $this->form_data['pay_period'][$legal_entity_id][$quarter_month][$date_stamp]['l6']; //Add L6 -> L9 if they are implemented later.
					}
				}
				unset( $legal_entity_id, $quarter_month, $date_stamp, $user_id, $data_a, $data_b );

				//Total all pay periods by quarter
				if ( isset($this->form_data['pay_period']) ) {
					foreach( $this->form_data['pay_period'] as $legal_entity_id => $legal_entity_data ) {
						foreach( $this->form_data['pay_period'][$legal_entity_id] as $month_id => $pp_data ) {
							$this->form_data['quarter'][$legal_entity_id][$month_id] = Misc::ArrayAssocSum($pp_data, NULL, 8);
						}

						//Total all quarters.
						if ( isset($this->form_data['quarter'][$legal_entity_id]) ) {
							$this->form_data['total'][$legal_entity_id] = Misc::ArrayAssocSum( $this->form_data['quarter'][$legal_entity_id], NULL, 6);
						}
					}
					unset( $legal_entity_id, $legal_entity_data );
				}
			}
		}

		//Debug::Arr($this->user_ids, 'User IDs: ', __FILE__, __LINE__, __METHOD__, 10);
		//Debug::Arr($this->form_data, 'Form Raw Data: ', __FILE__, __LINE__, __METHOD__, 10);
		//Debug::Arr($this->tmp_data, 'Tmp Raw Data: ', __FILE__, __LINE__, __METHOD__, 10);

		//Get user data for joining.
		$ulf = TTnew( 'UserListFactory' ); /** @var UserListFactory $ulf */
		$ulf->getAPISearchByCompanyIdAndArrayCriteria( $this->getUserObject()->getCompany(), $filter_data );
		Debug::Text(' User Total Rows: '. $ulf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
		$this->getProgressBarObject()->start( $this->getAMFMessageID(), $ulf->getRecordCount(), NULL, TTi18n::getText('Retrieving Data...') );
		foreach ( $ulf as $key => $u_obj ) {
			$this->tmp_data['user'][$u_obj->getId()] = (array)$u_obj->getObjectAsArray( $this->getColumnDataConfig() );
			$this->tmp_data['user'][$u_obj->getId()]['user_id'] = $u_obj->getId();
			$this->tmp_data['user'][$u_obj->getId()]['legal_entity_id'] = $u_obj->getLegalEntity();
			$this->getProgressBarObject()->set( $this->getAMFMessageID(), $key );
		}
		//Debug::Arr($this->tmp_data['user'], 'User Raw Data: ', __FILE__, __LINE__, __METHOD__, 10);

		//Get legal entity data for joining.
		$lelf = TTnew( 'LegalEntityListFactory' ); /** @var LegalEntityListFactory $lelf */
		$lelf->getAPISearchByCompanyIdAndArrayCriteria( $this->getUserObject()->getCompany(), $filter_data );
		Debug::Text( ' Legal Entity Total Rows: ' . $lelf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10 );
		$this->getProgressBarObject()->start( $this->getAMFMessageID(), $lelf->getRecordCount(), NULL, TTi18n::getText( 'Retrieving Legal Entity Data...' ) );
		if ( $lelf->getRecordCount() > 0 ) {
			foreach( $lelf as $key => $le_obj ) {
				if ( $format == 'html' OR $format == 'pdf' ) {
					$this->tmp_data['legal_entity'][$le_obj->getId()] = Misc::addKeyPrefix( 'legal_entity_', (array)$le_obj->getObjectAsArray( Misc::removeKeyPrefix( 'legal_entity_', $this->getColumnDataConfig() ) ) );
					$this->tmp_data['legal_entity'][$le_obj->getId()]['legal_entity_id'] = $le_obj->getId();
				} else {
					$this->form_data['legal_entity'][$le_obj->getId()] = $le_obj;
				}
				$this->getProgressBarObject()->set( $this->getAMFMessageID(), $key );
			}
		}

		//Get remittance agency for joining.
		$filter_data['type_id'] = array(10, 20); //Federal/State (Need State here to determine if they are a multi-state employer or not.
		$filter_data['country'] = array('US'); //US Federal
		$ralf = TTnew( 'PayrollRemittanceAgencyListFactory' ); /** @var PayrollRemittanceAgencyListFactory $ralf */
		$ralf->getAPISearchByCompanyIdAndArrayCriteria( $this->getUserObject()->getCompany(), $filter_data );
		Debug::Text( ' Remittance Agency Total Rows: ' . $ralf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10 );
		$this->getProgressBarObject()->start( $this->getAMFMessageID(), $lelf->getRecordCount(), NULL, TTi18n::getText( 'Retrieving Remittance Agency Data...' ) );
		if ( $ralf->getRecordCount() > 0 ) {
			foreach( $ralf as $key => $ra_obj ) {
				if ( $ra_obj->parseAgencyID( NULL, 'id') == 10 ) {
					$province_id = ( $ra_obj->getType() == 20 ) ? $ra_obj->getProvince() : '00';
					$this->form_data['remittance_agency'][$ra_obj->getLegalEntity()][$province_id] = $ra_obj;
				}
				$this->getProgressBarObject()->set( $this->getAMFMessageID(), $key );
			}
			unset($province_id);
		}

		return TRUE;
	}

	/**
	 * PreProcess data such as calculating additional columns from raw data etc...
	 * @return bool
	 */
	function _preProcess() {
		if ( isset( $this->tmp_data['pay_stub_entry'] ) == FALSE ) {
			return TRUE;
		}
		$this->getProgressBarObject()->start( $this->getAMFMessageID(), count($this->tmp_data['pay_stub_entry']), NULL, TTi18n::getText('Pre-Processing Data...') );

		//Merge time data with user data
		$key = 0;
		if ( isset($this->tmp_data['pay_stub_entry']) ) {
			foreach( $this->tmp_data['pay_stub_entry'] as $user_id => $level_1 ) {
				foreach( $level_1 as $date_stamp => $row ) {
					$date_columns = TTDate::getReportDates( NULL, $date_stamp, FALSE, $this->getUserObject(), array('pay_period_start_date' => $row['pay_period_start_date'], 'pay_period_end_date' => $row['pay_period_end_date'], 'pay_period_transaction_date' => $row['pay_period_transaction_date']) );
					$processed_data	 = array(
											//'pay_period' => array('sort' => $row['pay_period_start_date'], 'display' => TTDate::getDate('DATE', $row['pay_period_start_date'] ).' -> '. TTDate::getDate('DATE', $row['pay_period_end_date'] ) ),
											);

					$tmp_legal_array = array();
					if ( isset($this->tmp_data['legal_entity'][$this->tmp_data['user'][$user_id]['legal_entity_id']]) ) {
						$tmp_legal_array = $this->tmp_data['legal_entity'][$this->tmp_data['user'][$user_id]['legal_entity_id']];
					}
					$this->data[] = array_merge( $this->tmp_data['user'][$user_id], $row, $date_columns, $processed_data, $tmp_legal_array );

					$this->getProgressBarObject()->set( $this->getAMFMessageID(), $key );
					$key++;
				}
			}
			unset($this->tmp_data, $row, $date_columns, $processed_data, $level_1);
		}
		//Debug::Arr($this->data, 'preProcess Data: ', __FILE__, __LINE__, __METHOD__, 10);

		return TRUE;
	}

	/**
	 * @param null $format
	 * @return mixed
	 */
	function _outputPDFForm( $format = NULL ) {
		$show_background = TRUE;
		if ( $format == 'pdf_form_print' ) {
			$show_background = FALSE;
		}
		Debug::Text('Generating Form... Format: '. $format, __FILE__, __LINE__, __METHOD__, 10);

		$setup_data = $this->getFormConfig();
		$filter_data = $this->getFilterConfig();
		//Debug::Arr($filter_data, 'Filter Data: ', __FILE__, __LINE__, __METHOD__, 10);

		$current_company = $this->getUserObject()->getCompanyObject();
		if ( !is_object($current_company) ) {
			Debug::Text('Invalid company object...', __FILE__, __LINE__, __METHOD__, 10);
			return FALSE;
		}

		if ( isset($this->form_data['total']) ) {
			foreach ( $this->form_data['total'] as $legal_entity_id => $legal_entity_data ) {
				if ( isset( $this->form_data['legal_entity'][$legal_entity_id] ) == FALSE ) {
					Debug::Text( 'Missing Legal Entity: ' . $legal_entity_id, __FILE__, __LINE__, __METHOD__, 10 );
					continue;
				}

				if ( isset( $this->form_data['remittance_agency'][$legal_entity_id] ) == FALSE ) {
					Debug::Text( 'Missing Remittance Agency: ' . $legal_entity_id, __FILE__, __LINE__, __METHOD__, 10 );
					continue;
				}

				$legal_entity_obj = $this->form_data['legal_entity'][$legal_entity_id];

				if ( $format == 'efile_xml' ) {
					$return941 = $this->getRETURN941Object();

					$return941->TaxPeriodEndDate = TTDate::getDate('Y-m-d', TTDate::getEndDayEpoch( $filter_data['end_date'] ));
					$return941->ReturnType = '';
					$return941->ein = $this->form_data['remittance_agency'][$legal_entity_id]['00']->getPrimaryIdentification(); //Always use EIN from Federal Agency.
					$return941->BusinessName1 = '';
					$return941->BusinessNameControl = '';

					$return941->AddressLine = $legal_entity_obj->getAddress1() . ' ' . $legal_entity_obj->getAddress2();
					$return941->City = $legal_entity_obj->getCity();
					$return941->State = $legal_entity_obj->getProvince();
					$return941->ZIPCode = $legal_entity_obj->getPostalCode();

					$this->getFormObject()->addForm( $return941 );
				}

				$f941 = $this->getF941Object();
				$f941->setDebug(FALSE);
				$f941->setShowBackground( $show_background );

				$f941->year = TTDate::getYear( $filter_data['end_date'] );

				$f941->ein = $this->form_data['remittance_agency'][$legal_entity_id]['00']->getPrimaryIdentification(); //Always use EIN from Federal Agency.
				$f941->name = $legal_entity_obj->getLegalName();
				$f941->trade_name = $legal_entity_obj->getTradeName();
				$f941->address = $legal_entity_obj->getAddress1() . ' ' . $legal_entity_obj->getAddress2();
				$f941->city = $legal_entity_obj->getCity();
				$f941->state = $legal_entity_obj->getProvince();
				$f941->zip_code = $legal_entity_obj->getPostalCode();

				$f941->quarter = TTDate::getYearQuarter( $filter_data['end_date'] );

				//Debug::Arr($this->form_data, 'Final Data for Form: ', __FILE__, __LINE__, __METHOD__, 10);
				if ( isset($this->form_data) AND count($this->form_data) == 5 ) {
					$f941->l1 = ( isset($this->user_ids[$legal_entity_id]) ) ? count($this->user_ids[$legal_entity_id]) : 0;
					$f941->l2 = $this->form_data['total'][$legal_entity_id]['l2'];
					$f941->l3 = $this->form_data['total'][$legal_entity_id]['l3'];

					$f941->l5a = $this->form_data['total'][$legal_entity_id]['l5a'];
					$f941->l5b = $this->form_data['total'][$legal_entity_id]['l5b'];
					$f941->l5c = $this->form_data['total'][$legal_entity_id]['l5c'];
					$f941->l5d = $this->form_data['total'][$legal_entity_id]['l5d'];

					Debug::Text('L7 - Social Security Tax Total (Including Amount on Tips): '. $this->form_data['total'][$legal_entity_id]['social_security_tax'] .' Medicare Tax Total: '. $this->form_data['total'][$legal_entity_id]['medicare_tax'], __FILE__, __LINE__, __METHOD__, 10);
					$f941->l7z = bcadd( $this->form_data['total'][$legal_entity_id]['social_security_tax'], $this->form_data['total'][$legal_entity_id]['medicare_tax'] ); //Input value used to calculate L7 itself. Be sure to include 'l5b2' (Social Security on Tip Wages)

					Debug::Text('L5 - Actually Deducted from Employee Total: Social Security: '. bcadd( $this->form_data['total'][$legal_entity_id]['social_security_tax_employee_deducted'], $this->form_data['total'][$legal_entity_id]['social_security_tax_employer_deducted'] ) .' Medicare Tax Total: '. bcadd( $this->form_data['total'][$legal_entity_id]['medicare_tax_employee_deducted'], $this->form_data['total'][$legal_entity_id]['medicare_tax_employer_deducted'] ), __FILE__, __LINE__, __METHOD__, 10);
					$f941->l5_actual_deducted = bcadd( $this->form_data['total'][$legal_entity_id]['social_security_tax_employee_deducted'], bcadd( $this->form_data['total'][$legal_entity_id]['social_security_tax_employer_deducted'], bcadd( $this->form_data['total'][$legal_entity_id]['medicare_tax_employee_deducted'], $this->form_data['total'][$legal_entity_id]['medicare_tax_employer_deducted'] ) ) ); //Input value used to calculate L7 itself.

					if ( isset($setup_data['quarter_deposit']) AND $setup_data['quarter_deposit'] != ''	 ) {
						$f941->l13 = Misc::MoneyFormat($setup_data['quarter_deposit'], FALSE);
					}
					//Debug::Text('L13: '. $f941->l13 .' L6: '. $f941->calcL6() .' - '. $this->form_data['total']['l10'], __FILE__, __LINE__, __METHOD__, 10);

					$f941->l15b = TRUE;

					if ( isset($setup_data['deposit_schedule']) AND $setup_data['deposit_schedule'] == 10 ) {
						if ( isset($this->form_data['quarter'][$legal_entity_id][1]['l10']) ) {
							$f941->l16_month1 = bcadd($this->form_data['quarter'][$legal_entity_id][1]['income_tax'], bcadd( $this->form_data['quarter'][$legal_entity_id][1]['social_security_tax'], $this->form_data['quarter'][$legal_entity_id][1]['medicare_tax'] ) ); //Don't use L10 as it is slightly off due to rounding.
						}
						if ( isset($this->form_data['quarter'][$legal_entity_id][2]['l10']) ) {
							$f941->l16_month2 = bcadd($this->form_data['quarter'][$legal_entity_id][2]['income_tax'], bcadd( $this->form_data['quarter'][$legal_entity_id][2]['social_security_tax'], $this->form_data['quarter'][$legal_entity_id][2]['medicare_tax'] ) ); //Don't use L10 as it is slightly off due to rounding.
						}
						if ( isset($this->form_data['quarter'][$legal_entity_id][3]['l10']) ) {
							$f941->l16_month3 = bcadd($this->form_data['quarter'][$legal_entity_id][3]['income_tax'], bcadd( $this->form_data['quarter'][$legal_entity_id][3]['social_security_tax'], $this->form_data['quarter'][$legal_entity_id][3]['medicare_tax'] ) ); //Don't use L10 as it is slightly off due to rounding.
						}
					} elseif ( isset($setup_data['deposit_schedule']) AND $setup_data['deposit_schedule'] == 20 ) {
						$f941sb = $this->getFormObject()->getFormObject( '941sb', 'US' );
						$f941sb->setShowBackground( $show_background );

						$f941sb->year = $f941->year;
						$f941sb->ein = $f941->ein;
						$f941sb->name = $f941->name;
						$f941sb->quarter = $f941->quarter;
						$f941sb_data = array();

						$f941->schedule_b_total = 0;
						for( $i = 1; $i <= 3; $i++ ) {
							if ( isset($this->form_data['pay_period'][$legal_entity_id][$i]) ) {
								foreach( $this->form_data['pay_period'][$legal_entity_id][$i] as $pay_period_epoch => $data ) {
									//Debug::Text('SB: Month: '. $i .' Pay Period Date: '. TTDate::getDate('DATE', $pay_period_epoch) .' DOM: '. TTDate::getDayOfMonth($pay_period_epoch) .' Amount: '. $data['l10'], __FILE__, __LINE__, __METHOD__, 10);
									$f941sb_data[$i][TTDate::getDayOfMonth($pay_period_epoch)] = bcadd($data['income_tax'], bcadd( $data['social_security_tax'], $data['medicare_tax'] ) ); //This should be values that appeared on the actual pay stubs, which are already rounded of course.
									$f941->schedule_b_total = bcadd($f941->schedule_b_total, $f941sb_data[$i][TTDate::getDayOfMonth($pay_period_epoch)] );
								}
							}
						}
						//$f941->schedule_b_total += 1; Test mismatch of Schedule B totals.

						if ( isset($f941sb_data[1]) ) {
							$f941sb->month1 = $f941sb_data[1];
						}
						if ( isset($f941sb_data[2]) ) {
							$f941sb->month2 = $f941sb_data[2];
						}
						if ( isset($f941sb_data[3]) ) {
							$f941sb->month3 = $f941sb_data[3];
						}

						unset($i, $f941sb_data);
					}
				} else {
					Debug::Arr($this->data, 'Invalid Form Data: ', __FILE__, __LINE__, __METHOD__, 10);
				}

				$this->getFormObject()->addForm( $f941 );

				if ( isset($f941sb) AND is_object( $f941sb ) ) {
					$this->getFormObject()->addForm( $f941sb );
				}

				if ( $format == 'efile_xml' ) {
					$output_format = 'XML';
					$file_name = '940_efile_' . date( 'Y_m_d' ) . '_' . Misc::sanitizeFileName( $this->form_data['legal_entity'][ $legal_entity_id ]->getTradeName() ) . '.xml';
					$mime_type = 'applications/octet-stream'; //Force file to download.
				} else {
					$output_format = 'PDF';
					$file_name = $this->file_name . '_' . Misc::sanitizeFileName( $this->form_data['legal_entity'][ $legal_entity_id ]->getTradeName() ) . '.pdf';
					$mime_type = $this->file_mime_type;
				}

				$output = $this->getFormObject()->output( $output_format );
				$file_arr[] = array('file_name' => $file_name, 'mime_type' => $mime_type, 'data' => $output);

				if ( !defined( 'UNIT_TEST_MODE' ) OR UNIT_TEST_MODE === FALSE ) { //When in unit test mode don't clear form objects so we can run asserts against them.
					$this->clearFormObject();
					$this->clearF941Object();
					$this->clearRETURN941Object();
				}
			}
		}

		if ( isset($file_name) AND $file_name != '' ) {
			$zip_filename = explode( '.', $file_name );
			if ( isset( $zip_filename[ ( count( $zip_filename ) - 1 ) ] ) ) {
				$zip_filename = str_replace( '.', '', str_replace( $zip_filename[ ( count( $zip_filename ) - 1 ) ], '', $file_name ) ) . '.zip';
			} else {
				$zip_filename = str_replace( '.', '', $file_name ) . '.zip';
			}

			return Misc::zip( $file_arr, $zip_filename, TRUE );
		}

		Debug::Text(' Returning FALSE!', __FILE__, __LINE__, __METHOD__, 10);
		return FALSE;
	}

	/**
	 * @param null $format
	 * @return array|bool
	 */
	function _output( $format = NULL ) {
		if ( $format == 'pdf_form' OR $format == 'pdf_form_print' OR $format == 'efile_xml' ) {
			return $this->_outputPDFForm( $format );
		} else {
			return parent::_output( $format );
		}
	}
}
?>
