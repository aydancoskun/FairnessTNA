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
 * @package API\Core
 */
class APIDashboard extends APIFactory {
	protected $main_class = FALSE;

	/**
	 * APIDashboard constructor.
	 */
	public function __construct() {
		parent::__construct(); //Make sure parent constructor is always called.

		return TRUE;
	}

	/**
	 * Get all possible dashlets
	 * @param bool $name
	 * @param null $parent
	 * @return bool|array
	 */
	function getOptions( $name = FALSE, $parent = NULL) {
		$product_edition = $this->getCurrentCompanyObject()->getProductEdition();

		$retarr = array();
		switch ( $name ) {
			case 'dashlets':
				$retarr = array();

				//News
				$retarr['news'] = TTi18n::getText('News');

				if ( $this->getPermissionObject()->Check('punch', 'enabled') AND $this->getPermissionObject()->Check('punch', 'view_own') ) {
					$retarr['exception_summary'] = TTi18n::getText('Exceptions');  //Current/Last week, Own
					$retarr['timesheet_summary'] = TTi18n::getText('TimeSheet Summary');  //Current/Last week, Own
					if ( $this->getPermissionObject()->Check('report', 'enabled' ) AND $this->getPermissionObject()->Check('report', 'view_timesheet_summary' ) ) {
						$retarr['timesheet_verification_summary'] = TTi18n::getText('TimeSheet Verifications'); //Own
					}
				}

				if ( $this->getPermissionObject()->Check('punch', 'enabled') AND ($this->getPermissionObject()->Check('punch', 'view_child') OR $this->getPermissionObject()->Check('punch', 'view')) ) {
					$retarr['exception_summary_child'] = TTi18n::getText('Exceptions (Subordinates)');  //Current/Last week, Subordinates/All
					if ( $this->getPermissionObject()->Check('report', 'enabled' ) AND $this->getPermissionObject()->Check('report', 'view_timesheet_summary' ) ) {
						$retarr['timesheet_verification_summary_child'] = TTi18n::getText('TimeSheet Verifications (Subordinates)'); //Subordinates/All
					}
				}

				if ( $this->getPermissionObject()->Check('request', 'enabled') AND $this->getPermissionObject()->Check('request', 'view_own') ) {
					$retarr['request_summary'] = TTi18n::getText('Requests');  //Current/Last week, Own
				}
				if ( $this->getPermissionObject()->Check('request', 'enabled') AND ($this->getPermissionObject()->Check('request', 'authorize')) ) {
					$retarr['request_authorize_summary'] = TTi18n::getText('Request Authorizations');  //Subordinates
				}

				if ( $this->getPermissionObject()->Check('message', 'enabled') AND $this->getPermissionObject()->Check('message', 'view_own') ) {
					$retarr['message_summary'] = TTi18n::getText('Messages (UnRead)');  //Own
				}

				if ( $this->getPermissionObject()->Check('accrual', 'enabled') AND $this->getPermissionObject()->Check('accrual', 'view_own') ) {
					$retarr['accrual_balance_summary'] = TTi18n::getText('Accrual Balances');  //Own
				}

				if ( $product_edition >= 25 ) {
					if ( $this->getPermissionObject()->Check('expense', 'enabled') AND $this->getPermissionObject()->Check('expense', 'view_own') ) {
						$retarr['expense_summary'] = TTi18n::getText('Expenses');  //Current/Last week, Own
					}
					if ( $this->getPermissionObject()->Check('expense', 'enabled') AND ($this->getPermissionObject()->Check('expense', 'authorize')) ) {
						$retarr['expense_authorize_summary'] = TTi18n::getText('Expense Authorizations');  //Subordinates
					}
				}

				if ( $this->getPermissionObject()->Check('punch', 'enabled') AND $this->getPermissionObject()->Check('report', 'view_active_shift') AND ($this->getPermissionObject()->Check('punch', 'view_child') OR $this->getPermissionObject()->Check('punch', 'view')) ) {
					$retarr['user_active_shift_summary'] = TTi18n::getText('Whos In/Out');  //Subordinates/All
				}

				if ( $this->getPermissionObject()->Check('schedule', 'enabled') ) {
					if ( $this->getPermissionObject()->Check('schedule', 'view_own') ) {
						$retarr['schedule_summary'] = TTi18n::getText('Schedule Summary');  //Own
					}
					if ( $this->getPermissionObject()->Check('schedule', 'view_child') OR $this->getPermissionObject()->Check('schedule', 'view') ) {
						$retarr['schedule_summary_child'] = TTi18n::getText('Schedule Summary (Subordinates)');  //Own
					}
				}

				asort($retarr); //Put Custom List/Custom Report at the bottom always.
				if ( $product_edition > 10 ) {
					$retarr['custom_list'] = TTi18n::getText('Custom List');  //Saved Search & Layout
					$retarr['custom_report'] = TTi18n::getText('Custom Report');  //Report, Report Template/Saved Report
				}

				$retarr = Misc::addSortPrefix($retarr);

				break;
			case 'custom_list':
				$retarr = array();

				//Attendance
				if ( $this->getPermissionObject()->Check('punch', 'enabled') AND ($this->getPermissionObject()->Check('punch', 'view_own') OR $this->getPermissionObject()->Check('punch', 'view_child') OR $this->getPermissionObject()->Check('punch', 'view')) ) {
					$retarr['Exception'] = TTi18n::getText('Exceptions');
				}
				if ( $this->getPermissionObject()->Check('accrual', 'enabled') AND ($this->getPermissionObject()->Check('accrual', 'view_own') OR $this->getPermissionObject()->Check('accrual', 'view_child') OR $this->getPermissionObject()->Check('accrual', 'view')) ) {
					$retarr['AccrualBalance'] = TTi18n::getText('Accrual Balances');
				}
				if ( $this->getPermissionObject()->Check('accrual', 'enabled') AND ($this->getPermissionObject()->Check('accrual', 'view_own') OR $this->getPermissionObject()->Check('accrual', 'view_child') OR $this->getPermissionObject()->Check('accrual', 'view')) ) {
					$retarr['Accrual'] = TTi18n::getText('Accruals');
				}
				if ( $this->getPermissionObject()->Check('schedule', 'enabled') AND ($this->getPermissionObject()->Check('schedule', 'view_own') OR $this->getPermissionObject()->Check('schedule', 'view_child') OR $this->getPermissionObject()->Check('schedule', 'view')) ) {
					$retarr['Schedule'] = TTi18n::getText('Scheduled Shifts');
				}
				if ( $this->getPermissionObject()->Check('recurring_schedule', 'enabled') AND ($this->getPermissionObject()->Check('recurring_schedule', 'view_own') OR $this->getPermissionObject()->Check('recurring_schedule', 'view_child') OR $this->getPermissionObject()->Check('recurring_schedule', 'view')) ) {
					$retarr['RecurringScheduleControl'] = TTi18n::getText('Recurring Schedules');
				}
				if ( $this->getPermissionObject()->Check('recurring_schedule_template', 'enabled') AND ($this->getPermissionObject()->Check('recurring_schedule_template', 'view_own') OR $this->getPermissionObject()->Check('recurring_schedule_template', 'view_child') OR $this->getPermissionObject()->Check('recurring_schedule_template', 'view')) ) {
					$retarr['RecurringScheduleTemplateControl'] = TTi18n::getText('Recurring Templates');
				}
				if ( $product_edition >= 20 ) {
					if ( $this->getPermissionObject()->Check('job', 'enabled') AND ($this->getPermissionObject()->Check('job', 'view_own') OR $this->getPermissionObject()->Check('job', 'view_child') OR $this->getPermissionObject()->Check('job', 'view')) ) {
						$retarr['Job'] = TTi18n::getText('Jobs');
					}
					if ( $this->getPermissionObject()->Check('job_item', 'enabled') AND ($this->getPermissionObject()->Check('job_item', 'view_own') OR $this->getPermissionObject()->Check('job_item', 'view_child') OR $this->getPermissionObject()->Check('job_item', 'view')) ) {
						$retarr['JobItem'] = TTi18n::getText('Tasks');
					}
				}


				//Employee
				if ( $this->getPermissionObject()->Check('user', 'enabled') AND ($this->getPermissionObject()->Check('user', 'view_own') OR $this->getPermissionObject()->Check('user', 'view_child') OR $this->getPermissionObject()->Check('user', 'view')) ) {
					$retarr['User'] = TTi18n::getText('Employees');
				}
				if ( $this->getPermissionObject()->Check('user_contact', 'enabled') AND ($this->getPermissionObject()->Check('user_contact', 'view_own') OR $this->getPermissionObject()->Check('user_contact', 'view_child') OR $this->getPermissionObject()->Check('user_contact', 'view')) ) {
					$retarr['UserContact'] = TTi18n::getText('Employee Contacts');
				}
				if ( $this->getPermissionObject()->Check('wage', 'enabled') AND ($this->getPermissionObject()->Check('wage', 'view_own') OR $this->getPermissionObject()->Check('wage', 'view_child') OR $this->getPermissionObject()->Check('wage', 'view')) ) {
					$retarr['UserWage'] = TTi18n::getText('Wages');
				}

				//Company


				//Payroll
				if ( $this->getPermissionObject()->Check('pay_stub', 'enabled') AND ($this->getPermissionObject()->Check('pay_stub', 'view_own') OR $this->getPermissionObject()->Check('pay_stub', 'view_child') OR $this->getPermissionObject()->Check('pay_stub', 'view')) ) {
					$retarr['PayStub'] = TTi18n::getText('Pay Stubs');
				}
				if ( $this->getPermissionObject()->Check('pay_period', 'enabled') AND ($this->getPermissionObject()->Check('pay_period', 'view_own') OR $this->getPermissionObject()->Check('pay_period', 'view_child') OR $this->getPermissionObject()->Check('pay_period', 'view')) ) {
					$retarr['PayPeriod'] = TTi18n::getText('Pay Periods');
				}
				if ( $this->getPermissionObject()->Check('pay_stub_amendment', 'enabled') AND ($this->getPermissionObject()->Check('pay_stub_amendment', 'view_own') OR $this->getPermissionObject()->Check('pay_stub_amendment', 'view_child') OR $this->getPermissionObject()->Check('pay_stub_amendment', 'view')) ) {
					$retarr['PayStubAmendment'] = TTi18n::getText('Pay Stub Amendments');
				}
				if ( $product_edition >= 25 AND $this->getPermissionObject()->Check('user_expense', 'enabled') AND ($this->getPermissionObject()->Check('user_expense', 'view_own') OR $this->getPermissionObject()->Check('user_expense', 'view_child') OR $this->getPermissionObject()->Check('user_expense', 'view')) ) {
					$retarr['UserExpense'] = TTi18n::getText('Expenses');
				}


				//Invoice
				if ( $product_edition >= 20 ) {
					if ( $this->getPermissionObject()->Check('client', 'enabled') AND ($this->getPermissionObject()->Check('client', 'view_own') OR $this->getPermissionObject()->Check('client', 'view_child') OR $this->getPermissionObject()->Check('client', 'view')) ) {
						$retarr['Client'] = TTi18n::getText('Clients');
					}
					if ( $this->getPermissionObject()->Check('client_contact', 'enabled') AND ($this->getPermissionObject()->Check('client_contact', 'view_own') OR $this->getPermissionObject()->Check('client_contact', 'view_child') OR $this->getPermissionObject()->Check('client_contact', 'view')) ) {
						$retarr['ClientContact'] = TTi18n::getText('Client Contacts');
					}
					if ( $this->getPermissionObject()->Check('invoice', 'enabled') AND ($this->getPermissionObject()->Check('invoice', 'view_own') OR $this->getPermissionObject()->Check('invoice', 'view_child') OR $this->getPermissionObject()->Check('invoice', 'view')) ) {
						$retarr['Invoice'] = TTi18n::getText('Invoices');
					}
					if ( $this->getPermissionObject()->Check('invoice', 'enabled') AND ($this->getPermissionObject()->Check('invoice', 'view_own') OR $this->getPermissionObject()->Check('invoice', 'view_child') OR $this->getPermissionObject()->Check('invoice', 'view')) ) {
						$retarr['Transaction'] = TTi18n::getText('Invoice Transactions');
					}
				}


				//HR
				if ( $this->getPermissionObject()->Check('user_review', 'enabled') AND ($this->getPermissionObject()->Check('user_review', 'view_own') OR $this->getPermissionObject()->Check('user_review', 'view_child') OR $this->getPermissionObject()->Check('user_review', 'view')) ) {
					$retarr['UserReviewControl'] = TTi18n::getText('Reviews');
				}
				if ( $product_edition >= 25 ) {
					if ( $this->getPermissionObject()->Check('job_vacancy', 'enabled') AND ($this->getPermissionObject()->Check('job_vacancy', 'view_own') OR $this->getPermissionObject()->Check('job_vacancy', 'view_child') OR $this->getPermissionObject()->Check('job_vacancy', 'view')) ) {
						$retarr['JobVacancy'] = TTi18n::getText('Job Vacancies');
					}
					if ( $this->getPermissionObject()->Check('job_applicant', 'enabled') AND ($this->getPermissionObject()->Check('job_applicant', 'view_own') OR $this->getPermissionObject()->Check('job_applicant', 'view_child') OR $this->getPermissionObject()->Check('job_applicant', 'view')) ) {
						$retarr['JobApplicant'] = TTi18n::getText('Job Applicants');
					}
					if ( $this->getPermissionObject()->Check('job_application', 'enabled') AND ($this->getPermissionObject()->Check('job_application', 'view_own') OR $this->getPermissionObject()->Check('job_application', 'view_child') OR $this->getPermissionObject()->Check('job_application', 'view')) ) {
						$retarr['JobApplication'] = TTi18n::getText('Job Applications');
					}
				}


				//My Account
				if ( $this->getPermissionObject()->Check('request', 'enabled') AND ($this->getPermissionObject()->Check('request', 'view_own') OR $this->getPermissionObject()->Check('request', 'view_child') OR $this->getPermissionObject()->Check('request', 'view')) ) {
					$retarr['Request'] = TTi18n::getText('Requests');
				}
				//  Expenses is already added above.


				//My Account -> Authorization related views.
				if ( $this->getPermissionObject()->Check('request', 'enabled') AND $this->getPermissionObject()->Check('request', 'authorize') AND ($this->getPermissionObject()->Check('request', 'view_child') OR $this->getPermissionObject()->Check('request', 'view')) ) {
					$retarr['Request-Authorization'] = TTi18n::getText('Request Authorization');
				}
				if ( $this->getPermissionObject()->Check('punch', 'enabled') AND $this->getPermissionObject()->Check('punch', 'verify_time_sheet') AND ($this->getPermissionObject()->Check('punch', 'view_child') OR $this->getPermissionObject()->Check('punch', 'view')) ) {
					$retarr['PayPeriodTimeSheetVerify'] = TTi18n::getText('TimeSheet Verification');
				}
				if ( $product_edition >= 25 AND $this->getPermissionObject()->Check('user_expense', 'enabled') AND $this->getPermissionObject()->Check('user_expense', 'authorize') AND ($this->getPermissionObject()->Check('user_expense', 'view_child') OR $this->getPermissionObject()->Check('user_expense', 'view')) ) {
					$retarr['UserExpense-Authorization'] = TTi18n::getText('Expense Verification');
				}

				asort($retarr);
				$retarr = Misc::addSortPrefix($retarr);

				break;
			case 'custom_report':
				$retarr = array();

				//Employee Reports
				if ( $this->getPermissionObject()->Check('report', 'enabled') AND $this->getPermissionObject()->Check('report', 'view_user_information') ) {
					$retarr['UserSummaryReport'] = TTi18n::getText('Employee Information');
				}
				if ( $this->getPermissionObject()->Check('report', 'enabled') AND $this->getPermissionObject()->Check('report', 'view_active_shift') ) {
					$retarr['ActiveShiftReport'] = TTi18n::getText('Whos In Summary');
				}
				if ( $this->getPermissionObject()->Check('report', 'enabled') AND $this->getPermissionObject()->Check('report', 'view_system_log') ) {
					$retarr['AuditTrailReport'] = TTi18n::getText('Audit Trail');
				}


				//TimeSheet Reports
				if ( $this->getPermissionObject()->Check('report', 'enabled') AND $this->getPermissionObject()->Check('report', 'view_schedule_summary') ) {
					$retarr['ScheduleSummaryReport'] = TTi18n::getText('Schedule Summary');
				}
				if ( $this->getPermissionObject()->Check('report', 'enabled') AND $this->getPermissionObject()->Check('report', 'view_timesheet_summary') ) {
					$retarr['TimeSheetSummaryReport'] = TTi18n::getText('TimeSheet Summary');
					$retarr['TimeSheetDetailReport'] = TTi18n::getText('TimeSheet Detail');
				}
				if ( $this->getPermissionObject()->Check('report', 'enabled') AND $this->getPermissionObject()->Check('report', 'view_punch_summary') ) {
					$retarr['PunchSummaryReport'] = TTi18n::getText('Punch Summary');
				}
				if ( $this->getPermissionObject()->Check('report', 'enabled') AND $this->getPermissionObject()->Check('report', 'view_accrual_balance_summary') ) {
					$retarr['AccrualBalanceSummaryReport'] = TTi18n::getText('Accrual Balance Summary');
				}
				if ( $this->getPermissionObject()->Check('report', 'enabled') AND $this->getPermissionObject()->Check('report', 'view_exception_summary') ) {
					$retarr['ExceptionReport'] = TTi18n::getText('Exception Summary');
				}


				//Payroll Reports
				if ( $this->getPermissionObject()->Check('report', 'enabled') AND $this->getPermissionObject()->Check('report', 'view_pay_stub_summary') ) {
					$retarr['PayStubSummaryReport'] = TTi18n::getText('Pay Stub Summary');
				}
				if ( $product_edition >= 25 AND $this->getPermissionObject()->Check('report', 'enabled') AND $this->getPermissionObject()->Check('report', 'view_expense') ) {
					$retarr['UserExpenseReport'] = TTi18n::getText('Expense Summary');
				}


				//Job Tracking Reports
				if ( $product_edition >= 20 ) {
					if ( $this->getPermissionObject()->Check('report', 'enabled') AND $this->getPermissionObject()->Check('job_report', 'view_job_summary') ) {
						$retarr['JobSummaryReport'] = TTi18n::getText('Job Summary');
					}
					if ( $this->getPermissionObject()->Check('report', 'enabled') AND $this->getPermissionObject()->Check('job_report', 'view_job_analysis') ) {
						$retarr['JobDetailReport'] = TTi18n::getText('Job Analysis');
					}
					if ( $this->getPermissionObject()->Check('report', 'enabled') AND $this->getPermissionObject()->Check('job_report', 'view_job_summary') ) {
						$retarr['JobInformationReport'] = TTi18n::getText('Job Information');
						$retarr['JobItemInformationReport'] = TTi18n::getText('Task Information');
					}
				}


				//Invoice Reports
				if ( $product_edition >= 20 ) {
					if ( $this->getPermissionObject()->Check('report', 'enabled') AND $this->getPermissionObject()->Check('report', 'view_transaction_summary') ) {
						$retarr['InvoiceTransactionSummaryReport'] = TTi18n::getText('Transaction Summary');
					}
				}


				//HR Reports
				if ( $this->getPermissionObject()->Check('report', 'enabled') AND $this->getPermissionObject()->Check('hr_report', 'user_qualification') ) {
					$retarr['UserQualificationReport'] = TTi18n::getText('Qualification Summary');
				}
				if ( $this->getPermissionObject()->Check('report', 'enabled') AND $this->getPermissionObject()->Check('hr_report', 'user_review') ) {
					$retarr['KPIReport'] = TTi18n::getText('Review Summary');
				}


				//HR-Recruitment Reports
				if ( $product_edition >= 25 ) {
					if ( $this->getPermissionObject()->Check('report', 'enabled') AND $this->getPermissionObject()->Check('recruitment_report', 'user_recruitment') ) {
						$retarr['UserRecruitmentSummaryReport'] = TTi18n::getText('Recruitment Summary');
						$retarr['UserRecruitmentDetailReport'] = TTi18n::getText('Recruitment Detail');
					}
				}

				asort($retarr);
				$retarr = Misc::addSortPrefix($retarr);

				break;
			case 'auto_refresh':
				$retarr = array(
					0     => TTi18n::getText('Disabled'),
					//10    => TTi18n::getText('10 seconds'), //Shouldn't be enabled in production.
					300   => TTi18n::getText('5 mins'),
					600   => TTi18n::getText('10 mins'),
					900   => TTi18n::getText('15 mins'),
					1800  => TTi18n::getText('30 mins'),
					3600  => TTi18n::getText('1 hour'),
					7200  => TTi18n::getText('2 hours'),
					10800 => TTi18n::getText('3 hours'),
					21600 => TTi18n::getText('6 hours'),
				);
				break;
		}

		return $retarr;
	}

	/**
	 * Create filter base on user generic data
	 * @param string $view_name the view name
	 * @param array $user_generic_data user generic data
	 * @param $rows_per_page
	 * @return array
	 */
	function buildFilterParameters( $view_name, $user_generic_data, $rows_per_page ) {
		$parameters = array();
		$parameters['filter_columns'] = array();
		// Always return id column in case we may need it.
		$parameters['filter_columns']['id'] = TRUE;
		// If $user_generic_data set filter_data, convert it to api filter_data format.
		if ( isset($user_generic_data['filter_data']) ) {
			$parameters['filter_data'] = array();
			foreach ($user_generic_data['filter_data'] as $key => $value) {
				if ( is_array($value['value']) ) {
					$values = array();
					foreach( $value['value'] as $i => $item ) {
						if ( isset($item['value']) ) { // saved options
							$values[] = $item['value'];
						} else if ( isset($item['id']) ) {  // saved awesomebox
							$values[] = $item['id'];
						} else {
							$values[] = $item;
						}
					}
					$parameters['filter_data'][$key] = $values;
				} else {
					$parameters['filter_data'][$key] = $value['value'];
				}
			}
		}
		// Add default filter for some views.
		if ( $view_name == 'Request-Authorization' ) {
			if ( !isset($parameters['filter_data']) ) {
				$parameters['filter_data'] = array();
			}
			if ( !isset($parameters['filter_data']['type_id']) ) {
				$parameters['filter_data']['type_id'] = array(-1);
			}
			if ( !isset($parameters['filter_data']['hierarchy_level']) ) {
				$parameters['filter_data']['hierarchy_level'] = 1;
			}
		} else if ( $view_name == 'PayPeriodTimeSheetVerify' ) {
			if ( !isset($parameters['filter_data']) ) {
				$parameters['filter_data'] = array();
			}
			if ( !isset($parameters['filter_data']['hierarchy_level']) ) {
				$parameters['filter_data']['hierarchy_level'] = 1;
			}
		} else if ( $view_name == 'UserExpense-Authorization' ) {
			if ( !isset($parameters['filter_data']) ) {
				$parameters['filter_data'] = array();
			}
			$parameters['filter_data']['parent_id'] = array( TTUUID::getZeroID() );
			if ( !isset($parameters['filter_data']['hierarchy_level']) ) {
				$parameters['filter_data']['hierarchy_level'] = 1;
			}
		}
		// filter_sort in user generic data is same format as api need.
		if ( isset($user_generic_data['filter_sort']) ) {
			$parameters['filter_sort'] = $user_generic_data['filter_sort'];
		}
		if ( isset($user_generic_data['display_columns']) ) {
			for ($i = 0; $i < count($user_generic_data['display_columns']); $i++) {
				$parameters['filter_columns'][$user_generic_data['display_columns'][$i]] = TRUE;
			}
		}
		$parameters['filter_items_per_page'] = $rows_per_page;
		return $parameters;
	}

	/**
	 * @param $display_columns
	 * @param $rows_per_page
	 * @return array
	 */
	function buildDefaultDashletParameters( $display_columns, $rows_per_page ) {
		$parameters = array();
		$parameters['filter_columns'] = array();
		$parameters['filter_columns']['id'] = TRUE;
		if ( is_array($display_columns) ) {
			for ($i = 0; $i < count($display_columns); $i++) {
				$parameters['filter_columns'][$display_columns[$i]] = TRUE;
			}
		}
		$parameters['filter_items_per_page'] = $rows_per_page;

		return $parameters;
	}

	/**
	 * Get data for specific datalet
	 * @param string $name name of dashlet
	 * @param bool $data
	 * @return array
	 * @internal param array $params parameters for returning dashlet data
	 */
	function getDashletData( $name, $data = FALSE ) {
		$name = strtolower($name);
		$display_columns = NULL;
		Debug::Text('Name: ' . $name, __FILE__, __LINE__, __METHOD__, 10);
		switch ( $name ) {
			case 'news':
				$retarr  = array();

				$current_epoch = time();

				$product_edition = $this->getCurrentCompanyObject()->getProductEdition();

				$permission = new Permission();
				$permission_level = $permission->getLevel( $this->getCurrentUserObject()->getId(), $this->getCurrentCompanyObject()->getId() );

				//Notify user of new features.
				if ( Misc::MajorVersionCompare( APPLICATION_VERSION, '9.0.0', '=' ) AND TTDate::getBeginDayEpoch( $this->getCurrentUserObject()->getLastLoginDate() ) <= APPLICATION_VERSION_DATE ) {
					$retarr[] = '*<b>'.TTi18n::getText('Tip') .'</b>: '. TTi18n::getText('You can set the default screen that appears after login under MyAccount -> Preferences in the menu along the top of the screen.');
				}

				if ( $product_edition > 10 AND $permission_level >= 10 AND Misc::MajorVersionCompare( APPLICATION_VERSION, '9.0.0', '=' ) AND TTDate::getBeginDayEpoch( $this->getCurrentUserObject()->getLastLoginDate() ) <= APPLICATION_VERSION_DATE ) {
					$retarr[] = '*<b>'.TTi18n::getText('Tip') .'</b>: '. TTi18n::getText('You can add your own custom reports to the dashboard by clicking the Add Dashlet icon at the top left.');
				}

				//
				//Regular Employee Related news:
				//
				if ( ( $this->getPermissionObject()->Check('punch', 'enabled') AND ( $this->getPermissionObject()->Check('punch', 'view_own') OR $this->getPermissionObject()->Check('punch', 'punch_in_out') ) )  ) { //Exceptions are only viewable if they permissions to punch in/out.
					//  Check for critical severity exceptions
					$elf = TTNew( 'ExceptionListFactory' );
					/** @var ExceptionListFactory $elf */
					$elf->getFlaggedExceptionsByUserIdAndPayPeriodStatus( $this->getCurrentUserObject()->getId(), 10 );
					if ( $elf->getRecordCount() > 0 ) {
						foreach ( $elf as $e_obj ) {
							if ( $e_obj->getColumn( 'severity_id' ) == 30 ) {
								$retarr[] = TTi18n::getText( 'Critical severity exceptions exist, you may want to take a look at those.' );
							}
							break;
						}
					}
					unset( $elf, $e_obj );
				}

				//  Check for recent unread messages
				$api = TTNew('APIMessageControl'); /** @var APIMessageControl $api */
				$api_result = $this->stripReturnHandler( $api->getMessageControl( array( 'filter_data' => array( 'status_id' => 10, 'folder_id' => 10) ) ) );
				if ( is_array($api_result) AND count($api_result) > 0 ) {
					foreach( $api_result as $message_data ) {
						$retarr[] = TTi18n::getText('UnRead message sent from %1 on %2.', array($message_data['from_first_name'] .' '. $message_data['from_last_name'], TTDate::getDate('DATE', TTDate::parseDateTime( $message_data['created_date'] ) ) ) );
						break;
					}
				}
				unset($api_result, $message_data);

				//  Check for timesheet verifications
				$ppslf = TTNew('PayPeriodScheduleListFactory'); /** @var PayPeriodScheduleListFactory $ppslf */
				$ppslf->getByUserId( $this->getCurrentUserObject()->getId() );
				if ( $ppslf->getRecordCount() > 0 ) {
					foreach( $ppslf as $pps_obj ) {
						if ( in_array( $pps_obj->getTimeSheetVerifyType(), array(20,40) ) ) { //Check if TimeSheet Verification is enabled or not.
							$pplf = TTNew('PayPeriodListFactory'); /** @var PayPeriodListFactory $pplf */
							$pplf->getThisPayPeriodByCompanyIdAndPayPeriodScheduleIdAndDate( $pps_obj->getCompany(), array( $pps_obj->getId() ), $current_epoch );
							if ( $pplf->getRecordCount() > 0 ) {
								foreach( $pplf as $pp_obj ) {
									Debug::Text( 'Pay Period ID: '. $pp_obj->getId() .' PP Schedule ID: '. $pps_obj->getId(), __FILE__, __LINE__, __METHOD__, 10);
									$pptsvlf = TTnew( 'PayPeriodTimeSheetVerifyListFactory' ); /** @var PayPeriodTimeSheetVerifyListFactory $pptsvlf */
									$pptsvlf->getByPayPeriodIdAndUserId( $pp_obj->getId(), $this->getCurrentUserObject()->getId() );
									if ( $pptsvlf->getRecordCount() > 0 ) {
										$pptsv_obj = $pptsvlf->getCurrent();
										$pptsv_obj->setCurrentUser( $this->getCurrentUserObject()->getId() );
									} else {
										$pptsv_obj = $pptsvlf;
										$pptsv_obj->setCurrentUser( $this->getCurrentUserObject()->getId() );
										$pptsv_obj->setUser( $this->getCurrentUserObject()->getId() );
										$pptsv_obj->setPayPeriod( $pp_obj->getId() );
									}

									$verification_window_dates = $pptsv_obj->getVerificationWindowDates();
									Debug::Arr( array( $pptsv_obj->getPayPeriodObject()->data, $verification_window_dates ), ' Verification Dates: ', __FILE__, __LINE__, __METHOD__, 10);

									//Only display messages if the timesheet isn't already verified.
									if ( $pptsv_obj->displayVerifyButton( $this->getCurrentUserObject()->getId(), $this->getCurrentUserObject()->getId() ) == TRUE ) {
										$retarr[] = TTi18n::getText('Timesheet verification required by %2', array( TTDate::getDate( 'DATE', $verification_window_dates['start'] ), TTDate::getDate( 'DATE', $verification_window_dates['end'] ) ) );
									} else {
										Debug::Text( 'TimeSheet Verification is not required or already done...', __FILE__, __LINE__, __METHOD__, 10);
									}
								}
								unset($pptsvlf, $pptsv_obj, $verification_window_dates);
							} else {
								Debug::Text( 'No Pay Periods found for PP Schedule ID: '. $pps_obj->getId(), __FILE__, __LINE__, __METHOD__, 10);
							}
						} else {
							Debug::Text( 'TimeSheet Verification is not enabled...', __FILE__, __LINE__, __METHOD__, 10);
						}
					}
				}
				unset($ppslf, $pps_obj, $pplf, $pp_obj);

				//  Check for requests that changed status.
				$rlf = TTNew('RequestListFactory'); /** @var RequestListFactory $rlf */
				$rlf->getAPISearchByCompanyIdAndArrayCriteria( $this->getCurrentUserObject()->getCompany(), array( 'user_id' => $this->getCurrentUserObject()->getId(), 'status_id' => array(50,55), 'updated_date_start' => ( TTDate::getBeginDayEpoch( $current_epoch ) - ( 86400 * 2 ) ), 'updated_date_end' => TTDate::getEndDayEpoch( $current_epoch ) ) );
				if ( $rlf->getRecordCount() > 0 ) {
					foreach($rlf as $r_obj) {
						if ( $r_obj->getStatus() == 50 ) {
							$retarr[] = TTi18n::getText('A request has recently been authorized.');
						} elseif ( $r_obj->getStatus() == 55 ) {
							$retarr[] = TTi18n::getText('A request has recently been declined.');
						}
						break;
					}
				}
				unset($rlf, $r_obj);

				//  Check for upcoming scheduled absences
				$api = TTNew('APISchedule'); /** @var APISchedule $api */
				$api_result = $this->stripReturnHandler( $api->getCombinedSchedule( array( 'filter_data' => array('status_id' => 20, 'include_user_ids' => array($this->getCurrentUserObject()->getId()) ) ), TTDate::getDate('DATE', $current_epoch ), 'week', FALSE) );
				if ( isset($api_result['schedule_data']) ) {
					foreach( $api_result['schedule_data'] as $date => $schedule_data ) {
						$retarr[] = TTi18n::getText('Upcoming %1 absence scheduled for %2.', array($schedule_data[0]['absence_policy'], TTDate::getDate('DATE', strtotime( $date ) ) ) );
						break;
					}
				}
				unset($api_result, $schedule_data, $date);

				//  Check for recent pay stubs.
				$api = TTNew('APIPayStub'); /** @var APIPayStub $api */
				$api_result = $this->stripReturnHandler( $api->getPayStub( array( 'filter_data' => array('status_id' => 40, 'include_user_ids' => array($this->getCurrentUserObject()->getId()), 'start_date' => TTDate::getBeginDayEpoch( $current_epoch - (86400 * 2) ), 'end_date' => TTDate::getEndDayEpoch( $current_epoch + (86400 * 5) ) ) ), FALSE) );
				if ( is_array($api_result) AND count($api_result) > 0 ) {
					foreach( $api_result as $pay_stub_data ) {
						$retarr[] = TTi18n::getText('Pay stub scheduled to be paid on %1 is now available.', array($pay_stub_data['transaction_date'] ) );
						break;
					}
				}
				unset($api_result, $pay_stub_data);

				//  Check for Pay Periods nearing end date or transaction date
				$pplf = TTNew('PayPeriodListFactory'); /** @var PayPeriodListFactory $pplf */
				$pplf->getByUserIdAndTransactionDate( $this->getCurrentUserObject()->getId(), $current_epoch );
				if ( $pplf->getRecordCount() > 0 ) {
					foreach( $pplf as $pp_obj ) {
						$pay_period_end_diff = ( $pp_obj->getEndDate() - $current_epoch );
						$pay_period_transaction_diff = ( $pp_obj->getTransactionDate() - $current_epoch );
						if ( $pay_period_end_diff > 0 AND $pay_period_end_diff < ( 86400 * 2 ) ) {
							$retarr[] = TTi18n::getText('Current pay period ends on %1, will be paid by %2', array( TTDate::getDate('DATE', $pp_obj->getEndDate() ), TTDate::getDate('DATE', $pp_obj->getTransactionDate() ) ) );
						} elseif ( $pay_period_end_diff < 0 AND $pay_period_end_diff > ( 86400 * -2 ) ) {
							$retarr[] = TTi18n::getText('Previous pay period ended on %1, will be paid by %2', array( TTDate::getDate('DATE', $pp_obj->getEndDate() ), TTDate::getDate('DATE', $pp_obj->getTransactionDate() ) ) );
						} elseif ( $pay_period_transaction_diff > 0 AND $pay_period_transaction_diff < ( 86400 * 2 )  ) {
							$retarr[] = TTi18n::getText('Previous pay period will be paid by %2', array( TTDate::getDate('DATE', $pp_obj->getEndDate() ), TTDate::getDate('DATE', $pp_obj->getTransactionDate() ) ) );
						}
					}
				}
				unset($pplf, $pp_obj);

				$content = '<style type=\'text/css\'>
							.newsItemContainer{
								display:table;
								width:100%;
								border-spacing:0 5px;
								font-family: Arial, Helvetica, sans-serif;
								font-size: 15px;
							}

							.item{
								display:table-row;
								height:2em;
								line-height: 2em;
								text-align: left;
							}

							.item > div {
							  background-color: #B0C8E0;
							  padding-left: 0.25em;
							}

							.item > div {
							  border-radius: 4px 4px 4px 4px;
							}

							.newsItem g{
								display:table-cell;
							}

							.newsItem{
								width:100%;
								//font-weight: bold;
							}
							</style>';

				if ( count($retarr) > 0 ) {
					$content .= '<div class="newsItemContainer">';
					foreach(  $retarr as $item ) {
						$content .= '<div class="item"><div class="newsItem">'. $item .'</div></div>';
					}
					$content .= '</div><div align="center">&bull;</center>';
				} else {
					$content .= '<div class="newsItemContainer">';
					$content .= '<div class="item"><div class="newsItem">'. TTi18n::getText('Slow news day, nothing to see here yet') .'...</div></div>';
					$content .= '</div><div align="center">&bull;</center>';
				}

				return $this->returnHandler($content);
				break;
			case 'schedule_summary':
			case 'schedule_summary_child':
				$api = TTNew('APISchedule'); /** @var APISchedule $api */

				$maximum_row_limit = ( isset($data['rows_per_page']) AND $data['rows_per_page'] > 0 ) ? $data['rows_per_page'] : $this->getCurrentUserPreferenceObject()->getItemsPerPage();
				$parameters = $this->buildDefaultDashletParameters($display_columns, $data['rows_per_page']);
				if ( $name == 'schedule_summary' ) {
					$display_columns = array('id', 'date_stamp', 'status', 'status_id', 'start_time', 'end_time', 'total_time');
					$parameters['filter_data'] = array('include_user_ids' => array($this->getCurrentUserObject()->getId()));
				} else {
					$display_columns = $api->getOptions('default_display_columns');
					$parameters['filter_data'] = array('exclude_user_ids' => array($this->getCurrentUserObject()->getId(), '0'));
				}

				$retarr['api_retval'] = array();

				$api_result = $api->getCombinedSchedule($parameters, TTDate::getDate('DATE', time()), 'week', FALSE);
				if ( isset($api_result['api_retval']['schedule_data']) ) {
					$i = 0;
					foreach ($api_result['api_retval']['schedule_data'] as $val) {
						foreach ($val as $val1) {
							if ( $val1['status_id'] == 10 ) {
								$val1['status'] = TTi18n::getText('Working');
							} elseif ( $val1['status_id'] == 20 ) {
								$val1['status'] = ( $val1['absence_policy'] != '' ) ? $val1['absence_policy'] : TTi18n::getText('N/A');
							}
							$retarr['api_retval'][] = $val1;
						}

						if ( $i >= $maximum_row_limit ) {
							array_splice( $retarr['api_retval'], $i );
							break;
						}
						$i++;
					}
				}
				unset($api_result, $i, $maximum_row_limit);
				break;
			case 'user_active_shift_summary':
				$api = TTNew('APIActiveShiftReport'); /** @var APIActiveShiftReport $api */
				$template_data = $api->getTemplate('by_status_by_type_by_employee');
				$template_data = Misc::trimSortPrefix($template_data['api_retval']);
				$template_data['other']['maximum_row_limit'] = ( isset($data['rows_per_page']) AND $data['rows_per_page'] > 0 ) ? $data['rows_per_page'] : $this->getCurrentUserPreferenceObject()->getItemsPerPage();
				$display_columns = $template_data['columns'];
				$template_data = Misc::addSortPrefix($template_data);
				$retarr = $api->getActiveShiftReport($template_data, 'raw');
				if ( is_array($retarr['api_retval']) ) {
					array_pop($retarr['api_retval']);
				}
				break;
			case 'timesheet_verification_summary':
			case 'timesheet_verification_summary_child':
				$api = TTNew('APITimesheetSummaryReport'); /** @var APITimesheetSummaryReport $api */
				$template_data = $api->getTemplate('by_pay_period_by_employee+verified_time_sheet');
				$template_data = Misc::trimSortPrefix($template_data['api_retval']);
				$template_data['other']['maximum_row_limit'] = ( isset($data['rows_per_page']) AND $data['rows_per_page'] > 0 ) ? $data['rows_per_page'] : $this->getCurrentUserPreferenceObject()->getItemsPerPage();
				$display_columns = $template_data['columns'];
				if ( $name == 'timesheet_verification_summary_child' ) {
					$template_data['exclude_user_id'] = array($this->getCurrentUserObject()->getId());
				}
				$template_data['sort'][] = array('verified_time_sheet' => 'asc');
				$template_data['sort'][] = array('last_name' => 'asc');
				$template_data = Misc::addSortPrefix($template_data);
				$retarr = $api->getTimesheetSummaryReport($template_data, 'raw');
				if ( is_array($retarr['api_retval']) ) {
					array_pop($retarr['api_retval']);
				}
				break;
			case 'message_summary':
				$api = TTNew('APIMessageControl'); /** @var APIMessageControl $api */
				$display_columns = $api->getOptions('default_display_columns');
				$display_columns[] = 'status_id';
				$parameters = $this->buildDefaultDashletParameters($display_columns, $data['rows_per_page']);
				$parameters['filter_data'] = array('status_id' => 10, 'folder_id' => 10);
				$retarr = $api->getMessageControl($parameters);
				break;
			case 'exception_summary':
			case 'exception_summary_child':
				$ppslf = TTnew('PayPeriodScheduleListFactory'); /** @var PayPeriodScheduleListFactory $ppslf */
				if ( $name == 'exception_summary_child' ) {
					$ppslf->getByCompanyId($this->getCurrentCompanyObject()->getId() ); //Must be all PP schedules when showing subordinate exceptions.
				} else {
					$ppslf->getByCompanyIdAndUserId($this->getCurrentCompanyObject()->getId(), $this->getCurrentUserObject()->getId() ); //Just current users PP schedule.
				}
				$pay_period_schedule_id = $ppslf->getIDSByListFactory( $ppslf );

				//Get pay period schedule that this user belongs to
				if ( is_array( $pay_period_schedule_id ) AND count( $pay_period_schedule_id ) > 0 ) {
					$pay_period_ids = array();

					//Get last and this pay period IDs.
					$pplf = TTnew('PayPeriodListFactory'); /** @var PayPeriodListFactory $pplf */
					$pplf->getThisPayPeriodByCompanyIdAndPayPeriodScheduleIdAndDate($this->getCurrentCompanyObject()->getId(), $pay_period_schedule_id, time());
					if ( $pplf->getRecordCount() > 0 ) {
						foreach ($pplf as $pp_obj) {
							$pay_period_ids[] = $pp_obj->getId();
						}
					}
					$pplf->getLastPayPeriodByCompanyIdAndPayPeriodScheduleIdAndDate($this->getCurrentCompanyObject()->getId(), $pay_period_schedule_id, time());
					if ( $pplf->getRecordCount() > 0 ) {
						foreach ($pplf as $pp_obj) {
							//Only show last pay period if its not closed.
							if ( $pp_obj->getStatus() != 20 ) {
								$pay_period_ids[] = $pp_obj->getId();
							}
						}
					}

					if ( count($pay_period_ids) > 0 ) {
						$api = TTNew('APIException'); /** @var APIException $api */

						if ( $name == 'exception_summary' ) { //Only show the users own exceptions
							//No need to show first/last name as its only displaying their own exceptions anyways.
							$display_columns = array(
								'date_stamp',
								'severity',
								'exception_policy_type',
								'exception_policy_type_id',
							);
						} else {
							//Showing other users exceptions, make sure first/last name is displayed.
							$display_columns = $api->getOptions('default_display_columns');
						}
						$display_columns[] = 'exception_color';
						$display_columns[] = 'exception_background_color';
						$parameters = $this->buildDefaultDashletParameters($display_columns, $data['rows_per_page']);
						$parameters['filter_data'] = array('pay_period_id' => $pay_period_ids);

						if ( $name == 'exception_summary' ) { //Only show the users own exceptions
							$parameters['filter_data']['user_id'] = $this->getCurrentUserObject()->getId();
						} else { //Show all *but* the users own exceptions.
							$parameters['filter_data']['exclude_user_id'] = $this->getCurrentUserObject()->getId();
						}
						$parameters['filter_data']['type_id'] = array(50); //Exclude pre-mature exceptions.
						$retarr = $api->getException($parameters);
					}
				}
				break;
			case 'request_summary':
				$api = TTNew('APIRequest'); /** @var APIRequest $api */
				$display_columns = $api->getOptions('default_display_columns');
				$display_columns[] = 'status_id';
				$parameters = $this->buildDefaultDashletParameters($display_columns, $data['rows_per_page']);
				$parameters['filter_data'] = array('status_id' => 30);
				$retarr = $api->getRequest($parameters);
				break;
			case 'request_authorize_summary':
				$api = TTNew('APIRequest'); /** @var APIRequest $api */
				$display_columns = $api->getOptions('default_display_columns');
				$parameters = $this->buildDefaultDashletParameters($display_columns, $data['rows_per_page']);
				$parameters['filter_data'] = array();
				$parameters['filter_data']['type_id'] = array(-1);
				$parameters['filter_data']['status_id'] = 30; //Pending Auth
				$parameters['filter_data']['hierarchy_level'] = 1;
				$retarr = $api->getRequest($parameters);
				break;
			case 'accrual_balance_summary':
				$api = TTNew('APIAccrualBalance'); /** @var APIAccrualBalance $api */
				$display_columns = $api->getOptions('default_display_columns');
				//$display_columns[] = 'status_id';
				$parameters = $this->buildDefaultDashletParameters($display_columns, $data['rows_per_page']);
				$parameters['filter_data'] = array('status_id' => 10); //Only active employees.
				$retarr = $api->getAccrualBalance($parameters);
				break;
			case 'custom_list':
				/*
				 * Params=array('class' => <string>, 'user_generic_data_id' => array() )
				 */

				if ( isset($data['class']) AND $data['class'] != '' ) {
					Debug::Text('  Class: ' . $data['class'], __FILE__, __LINE__, __METHOD__, 10);

					//Get saved search and layout.
					if ( isset($data['user_generic_data_id']) ) {
						Debug::Text('  Getting UserGenericData for ID: ' . $data['user_generic_data_id'], __FILE__, __LINE__, __METHOD__, 10);

						if ( $data['class'] == 'Request-Authorization' ) {
							$api = TTNew('APIRequest'); /** @var APIRequest $api */
							$function_name = 'getRequest';
						} else if ( $data['class'] == 'UserExpense-Authorization' ) {
							$api = TTNew('APIUserExpense'); /** @var APIUserExpense $api */
							$function_name = 'getUserExpense';
						} else {
							$api = TTNew('API' . $data['class']);
							$function_name = 'get' . $data['class'];
						}

						$augd = TTNew('APIUserGenericData'); /** @var APIUserGenericData $augd */
						$retval = $this->stripReturnHandler($augd->getUserGenericData(array('filter_data' => array('id' => TTUUID::castUUID($data['user_generic_data_id'])))));
						if ( is_array($retval) AND count($retval) == 1 ) {
							if ( isset($retval[0]['data']) ) {
								$user_generic_data = $retval[0]['data'];
								$display_columns = $user_generic_data['display_columns'];
							}
						} else {
							//No user_generic_data specified, just use default display columns instead.
							$display_columns = $api->getOptions('default_display_columns');
							$user_generic_data = array('display_columns' => $display_columns);
						}
						unset($retval);

						if ( isset($user_generic_data) ) {
							Debug::Arr($user_generic_data, '  UserGenericData for Class: ' . $data['class'] . ' ID: ' . $data['user_generic_data_id'], __FILE__, __LINE__, __METHOD__, 10);

							if ( strpos('-', $data['class']) === FALSE ) {
								Debug::Text('  Getting Dashlet Data for Class: ' . $data['class'], __FILE__, __LINE__, __METHOD__, 10);
								$retarr = $api->$function_name($this->buildFilterParameters($data['class'], $user_generic_data, $data['rows_per_page']));
							}
							//else {
								//Special class, need to handle it manually.
							//}
						} else {
							Debug::Text('ERROR: No UserGenericData available...', __FILE__, __LINE__, __METHOD__, 10);
						}
					} else {
						Debug::Text('ERROR: No UserGenericData ID specified...', __FILE__, __LINE__, __METHOD__, 10);
					}
				} else {
					Debug::Text('ERROR: No Class specified...', __FILE__, __LINE__, __METHOD__, 10);
				}

				break;
			case 'custom_report':
				break;
		}

		if ( isset($retarr) ) {
			$data = $retarr['api_retval'];
			$retarr['api_retval'] = array('data' => $data, 'display_columns' => $display_columns);
			return $retarr;
		}

		return $this->returnHandler(FALSE);
	}

	/**
	 * @return array
	 */
	function getDefaultDashlets() {
		$parameters = FALSE;

		$permission = new Permission();
		$permission_level = $permission->getLevel( $this->getCurrentUserObject()->getId(), $this->getCurrentCompanyObject()->getId() );
		Debug::Text('Permission Level: '. $permission_level, __FILE__, __LINE__, __METHOD__, 10);

		//Get available dashlets (as that performs permission checks), and only add ones that are available.
		$dashlet_options = Misc::trimSortPrefix( $this->getOptions('dashlets') );

		//Check if TimeSheet Verifications are even enabled, not need to display dashlets if they aren't.
		$enabled_timesheet_verification = FALSE;
		$ppslf = TTNew('PayPeriodScheduleListFactory'); /** @var PayPeriodScheduleListFactory $ppslf */
		$ppslf->getByCompanyId( $this->getCurrentCompanyObject()->getId() );
		if ( $ppslf->getRecordCount() > 0 ) {
			foreach( $ppslf as $pps_obj ) {
				if ( in_array( $pps_obj->getTimeSheetVerifyType(), array(20,40) ) ) { //Check if TimeSheet Verification is enabled or not.
					$enabled_timesheet_verification = TRUE;
					break;
				}
			}
		}

		//Note: Dashlet information including its name/title is saved to UserGenericData, which means if the user changes languages then the dashlet titles won't get changed unless they edit/save them, or restore defaults.
		//      This is required behavior since the user can rename the dashlets to whatever they want.

		//Common to all levels.
		( ( isset($dashlet_options['news']) ) ? $parameters[] = $this->createDefaultDashletData('news', TTi18n::getText('News') ) : NULL );
		( ( isset($dashlet_options['exception_summary']) ) ? $parameters[] = $this->createDefaultDashletData('exception_summary', TTi18n::getText('Exception Summary') ) : NULL );
		( ( isset($dashlet_options['message_summary']) ) ? $parameters[] = $this->createDefaultDashletData('message_summary', TTi18n::getText('Messages (UnRead)') ) : NULL );

		if ( $permission_level >= 10 ) {
			( ( isset($dashlet_options['request_authorize_summary']) ) ? $parameters[] = $this->createDefaultDashletData('request_authorize_summary', TTi18n::getText('Request Authorizations') ) : NULL );
			( ( isset($dashlet_options['exception_summary_child']) ) ? $parameters[] = $this->createDefaultDashletData('exception_summary_child', TTi18n::getText('Exceptions (Subordinates)') ) : NULL );
			( ( isset($dashlet_options['schedule_summary_child']) ) ? $parameters[] = $this->createDefaultDashletData('schedule_summary_child', TTi18n::getText('Schedule Summary (Subordinates)') ) : NULL );
		}

		if ( $permission_level <= 10 ) {
			( ( isset($dashlet_options['timesheet_summary']) ) ? $parameters[] = $this->createDefaultDashletData('timesheet_summary', TTi18n::getText('TimeSheet Summary') ) : NULL );
		}
		( ( isset($dashlet_options['schedule_summary']) ) ? $parameters[] = $this->createDefaultDashletData('schedule_summary', TTi18n::getText('Schedule Summary') ) : NULL );
		if ( $permission_level <= 10 ) {
			( ( isset($dashlet_options['accrual_balance_summary']) ) ? $parameters[] = $this->createDefaultDashletData('accrual_balance_summary', TTi18n::getText('Accrual Balances') ) : NULL );
		}

		if ( $enabled_timesheet_verification == TRUE ) {
			if ( $permission_level == 10 ) {
				( ( isset($dashlet_options['timesheet_verification_summary_child']) ) ? $parameters[] = $this->createDefaultDashletData('timesheet_verification_summary_child', TTi18n::getText('TimeSheet Verifications (Subordinates)') ) : NULL );
			} elseif( $permission_level >= 15 ) {
				( ( isset($dashlet_options['timesheet_verification_summary']) ) ? $parameters[] = $this->createDefaultDashletData('timesheet_verification_summary', TTi18n::getText('TimeSheet Verifications') ) : NULL );
			}
		}

		if ( $permission_level >= 10 ) {
			( ( isset($dashlet_options['user_active_shift_summary']) ) ? $parameters[] = $this->createDefaultDashletData('user_active_shift_summary', TTi18n::getText('Whos In/Out') ) : NULL );
		}

		$augd = TTNew('APIUserGenericData'); /** @var APIUserGenericData $augd */
		$retval = $augd->setUserGenericData( $parameters );
		if ( $retval['api_retval'] == TRUE ) {
			$parameters = array(
					'name'       => 'order_data',
					'script'     => 'global_dashboard_order',
					'is_default' => TRUE,
					'data'       => $retval['api_details']['details'],
			);
			$augd->setUserGenericData( $parameters );

			return $augd->getUserGenericData( array('filter_data' => array('script' => 'global_dashboard', 'deleted' => FALSE)) );
		} else {
			return $this->returnHandler( FALSE );
		}
	}

	/**
	 * @param $type
	 * @param $name
	 * @return array
	 */
	function createDefaultDashletData( $type, $name) {
		$result = array(
						'script' => 'global_dashboard',
						'is_default' => FALSE,
						'name' => $name,
						'data' => array(
										'dashlet_type' => $type,
										'auto_refresh' => 0,
										'rows_per_page' => 0,
										),
						);

		return $result;
	}

	/**
	 * @return bool
	 */
	function removeAllDashlets() {
		return TRUE;
	}
}
?>
