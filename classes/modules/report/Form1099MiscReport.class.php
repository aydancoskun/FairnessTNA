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
class Form1099MiscReport extends Report {

	protected $user_ids = array();

	/**
	 * Form1099MiscReport constructor.
	 */
	function __construct() {
		$this->title = TTi18n::getText('Form 1099-MISC Report');
		$this->file_name = 'form_1099misc';

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
				AND $this->getPermissionObject()->Check('report', 'view_form1099misc', $user_id, $company_id ) ) {
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
										'-1100-pdf_form' => TTi18n::gettext('Employee (One Employee/Page)'),
										'-1110-pdf_form_government' => TTi18n::gettext('Government (Multiple Employees/Page)'),
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
				$retval = TTDate::getTimePeriodOptions( FALSE ); //Exclude Pay Period options.
				break;
			case 'date_columns':
				//$retval = TTDate::getReportDateOptions( NULL, TTi18n::getText('Date'), 13, TRUE );
				$retval = array();
				break;
			case 'report_custom_column':
				if ( getTTProductEdition() >= TT_PRODUCT_PROFESSIONAL ) {
					$rcclf = TTnew( 'ReportCustomColumnListFactory' ); /** @var ReportCustomColumnListFactory $rcclf */
					// Because the Filter type is just only a filter criteria and not need to be as an option of Display Columns, Group By, Sub Total, Sort By dropdowns.
					// So just get custom columns with Selection and Formula.
					$custom_column_labels = $rcclf->getByCompanyIdAndTypeIdAndFormatIdAndScriptArray( $this->getUserObject()->getCompany(), $rcclf->getOptions('display_column_type_ids'), NULL, 'Form1099MiscReport', 'custom_column' );
					if ( is_array($custom_column_labels) ) {
						$retval = Misc::addSortPrefix( $custom_column_labels, 9500 );
					}
				}
				break;
			case 'report_custom_filters':
				if ( getTTProductEdition() >= TT_PRODUCT_PROFESSIONAL ) {
					$rcclf = TTnew( 'ReportCustomColumnListFactory' ); /** @var ReportCustomColumnListFactory $rcclf */
					$retval = $rcclf->getByCompanyIdAndTypeIdAndFormatIdAndScriptArray( $this->getUserObject()->getCompany(), $rcclf->getOptions('filter_column_type_ids'), NULL, 'Form1099MiscReport', 'custom_column' );
				}
				break;
			case 'report_dynamic_custom_column':
				if ( getTTProductEdition() >= TT_PRODUCT_PROFESSIONAL ) {
					$rcclf = TTnew( 'ReportCustomColumnListFactory' ); /** @var ReportCustomColumnListFactory $rcclf */
					$report_dynamic_custom_column_labels = $rcclf->getByCompanyIdAndTypeIdAndFormatIdAndScriptArray( $this->getUserObject()->getCompany(), $rcclf->getOptions('display_column_type_ids'), $rcclf->getOptions('dynamic_format_ids'), 'Form1099MiscReport', 'custom_column' );
					if ( is_array($report_dynamic_custom_column_labels) ) {
						$retval = Misc::addSortPrefix( $report_dynamic_custom_column_labels, 9700 );
					}
				}
				break;
			case 'report_static_custom_column':
				if ( getTTProductEdition() >= TT_PRODUCT_PROFESSIONAL ) {
					$rcclf = TTnew( 'ReportCustomColumnListFactory' ); /** @var ReportCustomColumnListFactory $rcclf */
					$report_static_custom_column_labels = $rcclf->getByCompanyIdAndTypeIdAndFormatIdAndScriptArray( $this->getUserObject()->getCompany(), $rcclf->getOptions('display_column_type_ids'), $rcclf->getOptions('static_format_ids'), 'Form1099MiscReport', 'custom_column' );
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
										'-0900-legal_entity_legal_name' => TTi18n::gettext('Legal Entity Name'),
										'-0910-legal_entity_trade_name' => TTi18n::gettext( 'Legal Entity Trade Name' ),

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
										'-2020-l4' => TTi18n::gettext('Federal Income Tax (2)'),
										'-2060-l6' => TTi18n::gettext('Medical and Health Payments (6)'),
										'-2060-l7' => TTi18n::gettext('Nonemployee Compensation (7)'),
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
			case 'templates':
				$retval = array(
										//'-1010-by_month' => TTi18n::gettext('by Month'),
										'-1020-by_employee' => TTi18n::gettext('by Employee'),
										'-1030-by_branch' => TTi18n::gettext('by Branch'),
										'-1040-by_department' => TTi18n::gettext('by Department'),
										'-1050-by_branch_by_department' => TTi18n::gettext('by Branch/Department'),

										//'-1060-by_month_by_employee' => TTi18n::gettext('by Month/Employee'),
										//'-1070-by_month_by_branch' => TTi18n::gettext('by Month/Branch'),
										//'-1080-by_month_by_department' => TTi18n::gettext('by Month/Department'),
										//'-1090-by_month_by_branch_by_department' => TTi18n::gettext('by Month/Branch/Department'),
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
							$retval['columns'] = array();
							$retval['-1010-time_period']['time_period'] = 'last_year';

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

							$retval['columns'] = array_merge( $retval['columns'], array_keys( Misc::trimSortPrefix( $this->getOptions('dynamic_columns') ) ) );

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
	function getF1099MiscObject() {
		if ( !isset($this->form_obj['f1099m']) OR !is_object($this->form_obj['f1099m']) ) {
			$this->form_obj['f1099m'] = $this->getFormObject()->getFormObject( '1099misc', 'US' );
			return $this->form_obj['f1099m'];
		}

		return $this->form_obj['f1099m'];
	}

	/**
	 * @return bool
	 */
	function clearF1099MiscObject() {
		$this->form_obj['f1099m'] = FALSE;

		return TRUE;
	}

	/**
	 * @return bool
	 */
	function clearRETURN1040Object() {
		$this->form_obj['return1040'] = FALSE;

		return TRUE;
	}

	/**
	 * @return array
	 */
	function formatFormConfig() {
		$default_include_exclude_arr = array( 'include_pay_stub_entry_account' => array(), 'exclude_pay_stub_entry_account' => array() );

		$default_arr = array(
				'l4' => $default_include_exclude_arr,
				'l6' => $default_include_exclude_arr,
				'l7' => $default_include_exclude_arr,
			);

		$retarr = array_merge( $default_arr, (array)$this->getFormConfig() );
		return $retarr;
	}

	/**
	 * Get raw data for report
	 * @param null $format
	 * @return bool
	 */
	function _getData( $format = NULL ) {
		$this->tmp_data = array( 'pay_stub_entry' => array(), 'remittancy_agency' => array() );

		$filter_data = $this->getFilterConfig();
		$form_data = $this->formatFormConfig();
		$tax_deductions = array();
		$user_deduction_data = array();
		$tax_deduction_pay_stub_account_id_map = array();

		//
		//Figure out state/locality wages/taxes.
		//
		$cdlf = TTnew( 'CompanyDeductionListFactory' ); /** @var CompanyDeductionListFactory $cdlf */
		$cdlf->getByCompanyIdAndStatusIdAndTypeId( $this->getUserObject()->getCompany(), array(10, 20), 10 );
		if ( $cdlf->getRecordCount() > 0 ) {
			foreach( $cdlf as $cd_obj ) {
				$tax_deductions[$cd_obj->getId()] = $cd_obj;

				//Need to determine start/end dates for each CompanyDeduction/User pair, so we can break down total wages earned in the date ranges.
				$udlf = TTnew( 'UserDeductionListFactory' ); /** @var UserDeductionListFactory $udlf */
				$udlf->getByCompanyIdAndCompanyDeductionId( $cd_obj->getCompany(), $cd_obj->getId() );
				if ( $udlf->getRecordCount() > 0 ) {
					foreach( $udlf as $ud_obj ) {
						if ( $ud_obj->getStartDate() != '' OR $ud_obj->getEndDate() != '' ) {
							//Debug::Text('  User Deduction: ID: '. $ud_obj->getID() .' User ID: '. $ud_obj->getUser(), __FILE__, __LINE__, __METHOD__, 10);
							$user_deduction_data[$ud_obj->getCompanyDeduction()][$ud_obj->getUser()] = $ud_obj;
						}
					}
				}
			}
			Debug::Arr($tax_deductions, 'Tax Deductions: ', __FILE__, __LINE__, __METHOD__, 10);
		} else {
			Debug::Text('No Tax Deductions: ', __FILE__, __LINE__, __METHOD__, 10);
		}

		$pself = TTnew( 'PayStubEntryListFactory' ); /** @var PayStubEntryListFactory $pself */
		$pself->getAPIReportByCompanyIdAndArrayCriteria( $this->getUserObject()->getCompany(), $filter_data );
		$this->getProgressBarObject()->start( $this->getAMFMessageID(), $pself->getRecordCount(), NULL, TTi18n::getText('Retrieving Data...') );
		if ( $pself->getRecordCount() > 0 ) {
			foreach( $pself as $key => $pse_obj ) {
				$legal_entity_id = $pse_obj->getColumn('legal_entity_id');
				$user_id = $this->user_ids[] = $pse_obj->getColumn('user_id');
				$date_stamp = TTDate::strtotime( $pse_obj->getColumn('pay_stub_end_date') );
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

				$this->getProgressBarObject()->set( $this->getAMFMessageID(), $key );
			}

			if ( isset( $this->tmp_data['pay_stub_entry'] ) AND is_array( $this->tmp_data['pay_stub_entry'] ) ) {
				foreach ( $this->tmp_data['pay_stub_entry'] as $user_id => $data_a ) {
					foreach ( $data_a as $date_stamp => $data_b ) {
						$this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['l4'] = Misc::calculateMultipleColumns( $data_b['psen_ids'], $form_data['l4']['include_pay_stub_entry_account'], $form_data['l4']['exclude_pay_stub_entry_account'] );
						$this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['l6'] = Misc::calculateMultipleColumns( $data_b['psen_ids'], $form_data['l6']['include_pay_stub_entry_account'], $form_data['l6']['exclude_pay_stub_entry_account'] );
						$this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['l7'] = Misc::calculateMultipleColumns( $data_b['psen_ids'], $form_data['l7']['include_pay_stub_entry_account'], $form_data['l7']['exclude_pay_stub_entry_account'] );

						if ( is_array($data_b['psen_ids']) AND empty($tax_deductions) == FALSE ) {
							//Support multiple tax/deductions that deposit to the same pay stub account.
							//Also make sure we handle tax/deductions that may not have anything deducted/withheld, but do have wages to be displayed.
							//  For example an employee not earning enough to have State income tax taken off yet.
							//Now that user_deduction supports start/end dates per employee, we could use that to better handle employees switching between Tax/Deduction records mid-year
							//  while still accounting for cases where nothing is deducted/withheld but still needs to be displayed.
							foreach ( $tax_deductions as $tax_deduction_id => $cd_obj ) {
								if ( $legal_entity_id == $cd_obj->getLegalEntity() OR $legal_entity_id == TTUUID::getZeroID() ) {
									//Found Tax/Deduction associated with this pay stub account.
									$tax_withheld_amount = Misc::calculateMultipleColumns( $data_b['psen_ids'], array($cd_obj->getPayStubEntryAccount()) );
									if ( $tax_withheld_amount > 0 OR in_array( $user_id, (array)$cd_obj->getUser() ) ) {
										Debug::Text( 'Found User ID: ' . $user_id . ' in Tax Deduction Name: ' . $cd_obj->getName() . '(' . $cd_obj->getId() . ') Calculation ID: ' . $cd_obj->getCalculation() . ' Withheld Amount: ' . $tax_withheld_amount, __FILE__, __LINE__, __METHOD__, 10 );

										$is_active_date = TRUE;
										if ( isset( $user_deduction_data ) AND isset( $user_deduction_data[ $tax_deduction_id ] ) AND isset( $user_deduction_data[ $tax_deduction_id ][ $user_id ] ) ) {
											$is_active_date = $cdlf->isActiveDate( $user_deduction_data[ $tax_deduction_id ][ $user_id ], $this->tmp_data['pay_stub_entry'][ $user_id ][ $date_stamp ]['pay_period_end_date'] );
											Debug::Text( '  Date Restrictions Found... Is Active: ' . (int)$is_active_date . ' Date: ' . TTDate::getDate( 'DATE', $this->tmp_data['pay_stub_entry'][ $user_id ][ $date_stamp ]['pay_period_end_date'] ), __FILE__, __LINE__, __METHOD__, 10 );
										}

										//State records must come before district, so they can be matched up.
										if ( $cd_obj->getCalculation() == 200 AND $cd_obj->getProvince() != '' ) {
											//determine how many district/states currently exist for this employee.
											foreach ( range( 'a', 'z' ) as $z ) {
												//Make sure we are able to combine multiple state Tax/Deduction amounts together in the case
												//where they are using different Pay Stub Accounts for the State Income Tax and State Addl. Income Tax PSA's.
												//Need to have per user state detection vs per user/date, so we can make sure the state_id is unique across all possible data.
												if ( !( isset( $this->tmp_data['state_ids'][ $user_id ][ 'l16' . $z ] ) AND isset( $this->tmp_data['state_ids'][ $user_id ][ 'l17' . $z . '_state' ] ) AND $this->tmp_data['state_ids'][ $user_id ][ 'l17' . $z . '_state' ] != $cd_obj->getProvince() ) ) {
													$state_id = $z;
													break;
												}
											}

											//State Wages/Taxes
											$this->tmp_data['pay_stub_entry'][ $user_id ][ $date_stamp ][ 'l17' . $state_id . '_state' ] = $this->tmp_data['state_ids'][ $user_id ][ 'l17' . $state_id . '_state' ] = $cd_obj->getProvince();

											if ( $is_active_date == TRUE ) {
												if ( !isset( $this->tmp_data['pay_stub_entry'][ $user_id ][ $date_stamp ][ 'l18' . $state_id ] ) OR ( isset( $this->tmp_data['pay_stub_entry'][ $user_id ][ $date_stamp ][ 'l18' . $state_id ] ) AND $this->tmp_data['pay_stub_entry'][ $user_id ][ $date_stamp ][ 'l18' . $state_id ] == 0 ) ) {
													$this->tmp_data['pay_stub_entry'][ $user_id ][ $date_stamp ][ 'l18' . $state_id ] = Misc::calculateMultipleColumns( $data_b['psen_ids'], $cd_obj->getIncludePayStubEntryAccount(), $cd_obj->getExcludePayStubEntryAccount() );
												}
											}
											if ( !isset( $this->tmp_data['pay_stub_entry'][ $user_id ][ $date_stamp ][ 'l16' . $state_id ] ) ) {
												$this->tmp_data['pay_stub_entry'][ $user_id ][ $date_stamp ][ 'l16' . $state_id ] = $this->tmp_data['state_ids'][ $user_id ][ 'l16' . $state_id ] = 0;
											}
											//Just combine the tax withheld part, not the wages/earnings, as we don't want to double up on that.
											$this->tmp_data['pay_stub_entry'][ $user_id ][ $date_stamp ][ 'l16' . $state_id ] = bcadd( $this->tmp_data['pay_stub_entry'][ $user_id ][ $date_stamp ][ 'l16' . $state_id ], Misc::calculateMultipleColumns( $data_b['psen_ids'], array($cd_obj->getPayStubEntryAccount()) ) );
											$this->tmp_data['state_ids'][ $user_id ][ 'l16' . $state_id ] = bcadd( $this->tmp_data['state_ids'][ $user_id ][ 'l16' . $state_id ], $this->tmp_data['pay_stub_entry'][ $user_id ][ $date_stamp ][ 'l16' . $state_id ] );

											//Debug::Text('State ID: '. $state_id .' Withheld: '. $this->tmp_data['pay_stub_entry'][$user_id][$date_stamp]['l16'. $state_id], __FILE__, __LINE__, __METHOD__, 10);

											Debug::Text( 'Not State or Local income tax: ' . $cd_obj->getId() . ' Calculation: ' . $cd_obj->getCalculation() . ' District: ' . $cd_obj->getDistrictName() . ' UserValue5: ' . $cd_obj->getUserValue5() . ' CompanyValue1: ' . $cd_obj->getCompanyValue1(), __FILE__, __LINE__, __METHOD__, 10 );
										}
									} else {
										Debug::Text( 'User is either not assigned to Tax/Deduction, or they do not have any calculated amounts...', __FILE__, __LINE__, __METHOD__, 10 );
									}
									unset( $tax_withheld_amount );
								} else {
									Debug::Text( 'User not assigned to Legal Entity for this CompanyDeduction record, skipping...', __FILE__, __LINE__, __METHOD__, 10 );
								}
							}
							unset( $state_id, $district_id, $district_name, $tax_deduction_id, $cd_obj );
						}
					}
				}
			}
		}

		$this->user_ids = array_unique( $this->user_ids ); //Used to get the total number of employees.

		//Debug::Arr($this->tmp_data['user'], 'User Raw Data: ', __FILE__, __LINE__, __METHOD__, 10);
		//Debug::Arr($this->user_ids, 'User IDs: ', __FILE__, __LINE__, __METHOD__, 10);
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

		//Get legal entity data for joining.
		$lelf = TTnew( 'LegalEntityListFactory' ); /** @var LegalEntityListFactory $lelf */
		$lelf->getAPISearchByCompanyIdAndArrayCriteria( $this->getUserObject()->getCompany(), $filter_data );
		Debug::Text( ' Legal Entity Total Rows: ' . $lelf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10 );
		$this->getProgressBarObject()->start( $this->getAMFMessageID(), $lelf->getRecordCount(), NULL, TTi18n::getText( 'Retrieving Legal Entity Data...' ) );
		if ( $lelf->getRecordCount() > 0 ) {
			foreach ( $lelf as $key => $le_obj ) {
				if ( $format == 'html' OR $format == 'pdf' ) {
					$this->tmp_data['legal_entity'][$le_obj->getId()] = Misc::addKeyPrefix( 'legal_entity_', (array)$le_obj->getObjectAsArray( Misc::removeKeyPrefix( 'legal_entity_', $this->getColumnDataConfig() ) ) );
					$this->tmp_data['legal_entity'][$le_obj->getId()]['legal_entity_id'] = $le_obj->getId();
				} else {
					$this->form_data['legal_entity'][$le_obj->getId()] = $le_obj;
				}
				$this->getProgressBarObject()->set( $this->getAMFMessageID(), $key );
			}
		}

		//Get user data for joining.
		//$ulf = TTnew( 'UserListFactory' );
		$filter_data['type_id'] = array(10, 20); //federal and state
		$filter_data['country'] = array('US'); //US federal
		$ralf = TTnew( 'PayrollRemittanceAgencyListFactory' ); /** @var PayrollRemittanceAgencyListFactory $ralf */
		$ralf->getAPISearchByCompanyIdAndArrayCriteria( $this->getUserObject()->getCompany(), $filter_data );
		Debug::Text( ' Remittance Agency Total Rows: ' . $ralf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10 );
		$this->getProgressBarObject()->start( $this->getAMFMessageID(), $lelf->getRecordCount(), NULL, TTi18n::getText( 'Retrieving Remittance Agency Data...' ) );
		if ( $ralf->getRecordCount() > 0 ) {
			foreach ( $ralf as $key => $ra_obj ) {
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
	 * @param null $format
	 * @return bool
	 */
	function _preProcess( $format = NULL ) {
		$this->getProgressBarObject()->start( $this->getAMFMessageID(), count($this->tmp_data['pay_stub_entry']), NULL, TTi18n::getText('Pre-Processing Data...') );

		//Merge time data with user data
		$key = 0;
		if ( isset($this->tmp_data['pay_stub_entry']) AND isset($this->tmp_data['user']) ) {
			$sort_columns = $this->getSortConfig();

			foreach( $this->tmp_data['pay_stub_entry'] as $user_id => $level_1 ) {
				if ( isset($this->tmp_data['user'][$user_id]) ) {
					foreach ( $level_1 as $date_stamp => $row ) {
						$date_columns = TTDate::getReportDates( NULL, $date_stamp, FALSE, $this->getUserObject(), array('pay_period_start_date' => $row['pay_period_start_date'], 'pay_period_end_date' => $row['pay_period_end_date'], 'pay_period_transaction_date' => $row['pay_period_transaction_date']) );
						$processed_data = array();

						$tmp_legal_array = array();
						if ( isset($this->tmp_data['legal_entity'][$this->tmp_data['user'][$user_id]['legal_entity_id']]) ) {
							$tmp_legal_array = $this->tmp_data['legal_entity'][$this->tmp_data['user'][$user_id]['legal_entity_id']];
						}
						$this->data[] = array_merge( $this->tmp_data['user'][$user_id], $row, $date_columns, $processed_data, $tmp_legal_array );

						$this->getProgressBarObject()->set( $this->getAMFMessageID(), $key );
						$key++;
					}
				}
			}
			unset($this->tmp_data, $row, $date_columns, $processed_data, $tmp_legal_array);

			//Total data per employee for the W2 forms. Just include the columns that are necessary for the form.
			if ( is_array($this->data) AND !($format == 'html' OR $format == 'pdf') ) {
				Debug::Text('Calculating Form Data...', __FILE__, __LINE__, __METHOD__, 10);
				foreach( $this->data as $row ) {
					if ( !isset($this->form_data['user'][$row['legal_entity_id']][$row['user_id']]) ) {
						$this->form_data['user'][$row['legal_entity_id']][$row['user_id']] = array( 'user_id' => $row['user_id'] );
					}

					foreach( $row as $key => $value ) {
						if ( preg_match( '/^l[0-9]{1,2}[a-z]?_(state|district)$/i', $key ) == TRUE ) { //Static keys
							$this->form_data['user'][$row['legal_entity_id']][$row['user_id']][$key] = $value;
						} elseif( is_numeric($value) AND preg_match( '/^l[0-9]{1,2}[a-z]?$/i', $key ) == TRUE ) { //Dynamic keys.
							if ( !isset($this->form_data['user'][$row['legal_entity_id']][$row['user_id']][$key]) ) {
								$this->form_data['user'][$row['legal_entity_id']][$row['user_id']][$key] = 0;
							}
							$this->form_data['user'][$row['legal_entity_id']][$row['user_id']][$key] = bcadd( $this->form_data['user'][$row['legal_entity_id']][$row['user_id']][$key], $value );
						} elseif ( isset( $sort_columns[$key] ) ) { //Sort columns only, to help sortFormData() later on.
							$this->form_data['user'][ $row['legal_entity_id'] ][ $row['user_id'] ][ $key ] = $value;
						}
					}
				}
			}
		}
		//Debug::Arr($this->data, 'preProcess Data: ', __FILE__, __LINE__, __METHOD__, 10);
		//Debug::Arr($this->form_data, 'Form Data: ', __FILE__, __LINE__, __METHOD__, 10);

		return TRUE;
	}

	/**
	 * @param null $format
	 * @return array|bool
	 */
	function _outputPDFForm( $format = NULL ) {
		$file_arr = array();
		$show_background = TRUE;
		if ( $format == 'pdf_form_print' OR $format == 'pdf_form_print_government' ) {
			$show_background = FALSE;
		}
		Debug::Text('Generating Form... Format: '. $format, __FILE__, __LINE__, __METHOD__, 10);

		$current_user = $this->getUserObject();
		$setup_data = $this->getFormConfig();
		$filter_data = $this->getFilterConfig();
		//Debug::Arr($filter_data, 'Filter Data: ', __FILE__, __LINE__, __METHOD__, 10);

		if ( stristr( $format, 'government' ) ) {
			$form_type = 'government';
		} else {
			$form_type = 'employee';
		}
		Debug::Text('Form Type: '. $form_type, __FILE__, __LINE__, __METHOD__, 10);

		if ( isset( $this->form_data['user'] ) AND is_array( $this->form_data['user'] ) ) {
			$this->sortFormData(); //Make sure forms are sorted.

			foreach ( $this->form_data['user'] as $legal_entity_id => $user_rows ) {
				//$total_row = array();

				if ( isset( $this->form_data['legal_entity'][ $legal_entity_id ] ) == FALSE ) {
					Debug::Text( 'Missing Legal Entity: ' . $legal_entity_id, __FILE__, __LINE__, __METHOD__, 10 );
					continue;
				}

				if ( isset( $this->form_data['remittance_agency'][ $legal_entity_id ] ) == FALSE ) {
					Debug::Text( 'Missing Remittance Agency: ' . $legal_entity_id, __FILE__, __LINE__, __METHOD__, 10 );
					continue;
				}

				$x = 0; //Progress bar only.
				$this->getProgressBarObject()->start( $this->getAMFMessageID(), count($user_rows), NULL, TTi18n::getText('Generating Forms...') );

				$legal_entity_obj = $this->form_data['legal_entity'][ $legal_entity_id ];

				$f1099m = $this->getF1099MiscObject();
				$f1099m->setDebug(FALSE);
				$f1099m->setShowBackground( $show_background );

				$f1099m->setType( $form_type );
				$f1099m->year = TTDate::getYear( $filter_data['end_date'] );

				$f1099m->name = $legal_entity_obj->getLegalName();
				$f1099m->trade_name = $legal_entity_obj->getTradeName();
				$f1099m->company_address1 = $legal_entity_obj->getAddress1() . ' ' . $legal_entity_obj->getAddress2();
				$f1099m->company_city = $legal_entity_obj->getCity();
				$f1099m->company_state = $legal_entity_obj->getProvince();
				$f1099m->company_zip_code = $legal_entity_obj->getPostalCode();
				$f1099m->payer_id = $this->form_data['remittance_agency'][$legal_entity_id]['00']->getPrimaryIdentification(); //Use Federal Remittance Agency always.

				if ( isset( $this->form_data ) AND count( $this->form_data ) > 0 ) {
					$i = 0;
					foreach ( $user_rows as $user_id => $row ) {
						if ( !isset( $user_id ) OR TTUUID::isUUID( $user_id ) == FALSE ) {
							Debug::Text( 'User ID not set!', __FILE__, __LINE__, __METHOD__, 10 );
							continue;
						}

						$ulf = TTnew( 'UserListFactory' ); /** @var UserListFactory $ulf */
						$ulf->getById( TTUUID::castUUID( $user_id ) );
						if ( $ulf->getRecordCount() == 1 ) {
							$user_obj = $ulf->getCurrent();

							$ee_data = array(
									'control_number' => $i + 1,
									'first_name' => $user_obj->getFirstName(),
									'middle_name' => $user_obj->getMiddleName(),
									'last_name' => $user_obj->getLastName(),
									'address1' => $user_obj->getAddress1(),
									'address2' => $user_obj->getAddress2(),
									'city' => $user_obj->getCity(),
									'state' => $user_obj->getProvince(),
									'employment_province' => $user_obj->getProvince(),
									'postal_code' => $user_obj->getPostalCode(),
									'recipient_id' => $user_obj->getSIN(),
									'employee_number' => $user_obj->getEmployeeNumber(),
									'l4' => $row['l4'],
									'l6' => $row['l6'],
									'l7' => $row['l7'],
							);

							foreach ( range( 'a', 'z' ) as $z ) {
								//Make sure state information is included if its just local income taxes.
								if ( isset( $row[ 'l18' . $z ] ) AND ( isset( $row[ 'l17' . $z . '_state' ] ) AND isset( $this->form_data['remittance_agency'][ $legal_entity_id ][ $row[ 'l17' . $z . '_state' ] ] ) AND $this->form_data['remittance_agency'][ $legal_entity_id ][ $row[ 'l17' . $z . '_state' ] ]->getType() == 20 ) ) {
									$ee_data[ 'l17' . $z . '_state_id' ] = $this->form_data['remittance_agency'][ $legal_entity_id ][ $row[ 'l17' . $z . '_state' ] ]->getPrimaryIdentification();
									//$ee_data[ 'l17' . $z . '_state' ] = $row[ 'l17' . $z . '_state' ];
									$ee_data[ 'l17' . $z ] = $row[ 'l17' . $z . '_state' ];
									if ( isset($ee_data['l17'.$z.'_state_id']) AND $ee_data['l17'.$z.'_state_id'] != '' ) {
										$ee_data['l17'.$z] .= ' / '. $ee_data['l17'.$z.'_state_id'];
									}
								} else {
									$ee_data[ 'l17' . $z . '_state_id' ] = NULL;
									$ee_data[ 'l17' . $z . '_state' ] = NULL;
								}

								//State income tax
								if ( isset( $row[ 'l18' . $z ] ) ) {
									$ee_data[ 'l18' . $z ] = $row[ 'l18' . $z ];
									$ee_data[ 'l16' . $z ] = $row[ 'l16' . $z ];
								} else {
									$ee_data[ 'l18' . $z ] = NULL;
									$ee_data[ 'l16' . $z ] = NULL;
								}
							}

							$f1099m->addRecord( $ee_data );
							unset($ee_data);

							if ( $format == 'pdf_form_publish_employee' ) {
								// generate PDF for every employee and assign to each government document records
								$this->getFormObject()->addForm( $f1099m );
								GovernmentDocumentFactory::addDocument( $user_obj->getId(), 20, 220, TTDate::getEndYearEpoch( $filter_data['end_date'] ), $this->getFormObject()->output( 'PDF' ) );
								$this->getFormObject()->clearForms();
							}

							$i++;
						}
					}

					$this->getProgressBarObject()->set( $this->getAMFMessageID(), $x );
					$x++;
				}

				if ( $format == 'pdf_form_publish_employee' ) {
					$user_generic_status_batch_id = GovernmentDocumentFactory::saveUserGenericStatus( $current_user->getId() );

					return $user_generic_status_batch_id;
				}

				$this->getFormObject()->addForm( $f1099m );

				if ( $format == 'efile' ) {
					$output_format = 'EFILE';
					if ( $f1099m->getDebug() == TRUE ) {
						$file_name = '1099misc_efile_' . date( 'Y_m_d' ) . '_' . Misc::sanitizeFileName( $this->form_data['legal_entity'][ $legal_entity_id ]->getTradeName() ) . '.csv';
					} else {
						$file_name = '1099misc_efile_' . date( 'Y_m_d' ) . '_' . Misc::sanitizeFileName( $this->form_data['legal_entity'][ $legal_entity_id ]->getTradeName() ) . '.txt';
					}
					$mime_type = 'applications/octet-stream'; //Force file to download.
				} elseif ( $format == 'efile_xml' ) {
					$output_format = 'XML';
					$file_name = '1099misc_efile_' . date( 'Y_m_d' ) . '_' . Misc::sanitizeFileName( $this->form_data['legal_entity'][ $legal_entity_id ]->getTradeName() ) . '.xml';
					$mime_type = 'applications/octet-stream'; //Force file to download.
				} else {
					$output_format = 'PDF';
					$file_name = $this->file_name . '_' . Misc::sanitizeFileName( $this->form_data['legal_entity'][ $legal_entity_id ]->getTradeName() ) . '.pdf';
					$mime_type = $this->file_mime_type;
				}

				$output = $this->getFormObject()->output( $output_format );

				$file_arr[] = array('file_name' => $file_name, 'mime_type' => $mime_type, 'data' => $output);

				$this->clearFormObject();
				$this->clearF1099MiscObject();
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
	 * Short circuit this function, as no postprocessing is required for exporting the data.
	 * @param null $format
	 * @return bool
	 */
	function _postProcess( $format = NULL ) {
		if ( ( $format == 'pdf_form' OR $format == 'pdf_form_government' ) OR ( $format == 'pdf_form_print' OR $format == 'pdf_form_print_government' ) OR $format == 'efile_xml' OR $format == 'pdf_form_publish_employee' ) {
			Debug::Text('Skipping postProcess! Format: '. $format, __FILE__, __LINE__, __METHOD__, 10);
			return TRUE;
		} else {
			return parent::_postProcess( $format );
		}
	}

	/**
	 * @param null $format
	 * @return array|bool
	 */
	function _output( $format = NULL ) {
		if ( ( $format == 'pdf_form' OR $format == 'pdf_form_government' ) OR ( $format == 'pdf_form_print' OR $format == 'pdf_form_print_government' ) OR $format == 'efile_xml' OR $format == 'pdf_form_publish_employee' ) {
			return $this->_outputPDFForm( $format );
		} else {
			return parent::_output( $format );
		}
	}
}
?>
