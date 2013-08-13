<ul id="nav-one" class="sf-menu sf-navbar">
    <li><a href="{$BASE_URL}/index.php" ><img src = "{$IMAGES_URL}home_icon.gif" /></a></li>
  {if $permission->Check('punch','enabled') AND $permission->Check('punch','punch_in_out') }
    <li><a href="#" onclick="javascript:timePunch();">In / Out</a></li>
  {/if}

  {if $permission->Check('punch','enabled') }
  <li>
    <a href="#">
    {if $display_exception_flag == 'red'}
	    <img src = "{$IMAGES_URL}{$display_exception_flag}_flag.gif" />
	  {/if}
	  {t}TimeSheet{/t}
    </a>
    <ul>
      {if $permission->Check('punch','view') OR $permission->Check('punch','view_own')}
      <li><a href="{$BASE_URL}timesheet/ViewUserTimeSheet.php">MyTimeSheet</a></li>
      {/if}
      {if $permission->Check('punch','edit') OR $permission->Check('punch','edit_child')}
      <li><a href="{$BASE_URL}punch/PunchList.php">Punches</a></li>
      {/if}
      {if $permission->Check('request','view') OR $permission->Check('request','view_own')}
      <li><a href="{$BASE_URL}request/UserRequestList.php">Requests</a></li>
      {/if}
      {if $permission->Check('punch','view') OR $permission->Check('punch','view_own')}
      <li><a href="{$BASE_URL}punch/UserExceptionList.php">Exceptions</a></li>
 			<li><a href="{$BASE_URL}punch/UserExceptionList.php">{if $display_exception_flag != false}<img src = "{$IMAGES_URL}{$display_exception_flag}_flag.gif" />{/if}{t}Exceptions{/t}</a></li>

      {/if}
      {if $permission->Check('accrual','view') OR $permission->Check('accrual','view_own')}
      <li><a href="{$BASE_URL}accrual/UserAccrualBalanceList.php">Accruals</a></li>
      {/if}
      {if $permission->Check('pay_stub','view') OR $permission->Check('pay_stub','view_own')}
      <li><a href="{$BASE_URL}pay_stub/PayStubList.php">Pay Stubs</a></li>
      {/if}
    </ul>
  </li>
  {/if}
 
  {if $permission->Check('schedule','enabled')
	  OR $permission->Check('recurring_schedule','enabled')
	  OR $permission->Check('recurring_schedule_template','enabled')}
  <li>
    <a href="#">Schedule</a>
    <ul>
    {if $permission->Check('schedule','view') OR $permission->Check('schedule','view_own')}
      <li><a href="{$BASE_URL}schedule/ViewSchedule.php">MySchedule</a></li>
    {/if}
    {if $permission->Check('schedule','edit') OR $permission->Check('schedule','edit_child')}
      <li><a href="{$BASE_URL}schedule/ScheduleList.php">Scheduled Shifts</a></li>
      <li><a href="{$BASE_URL}schedule/AddMassSchedule.php">Mass Schedule</a></li>
    {/if}
    {if $permission->Check('recurring_schedule','enabled')}
      <li><a href="{$BASE_URL}schedule/RecurringScheduleControlList.php">Recurring Schedule</a></li>
    {/if}
    {if $permission->Check('recurring_schedule_template','enabled')}
      <li><a href="{$BASE_URL}schedule/RecurringScheduleTemplateControlList.php">Recurring Schedule Template</a></li>
    {/if}
    </ul>
  </li>
  {/if}

  {if $permission->Check('job','enabled')
		AND ( $permission->Check('job','view')
			OR $permission->Check('job','view_own')
			) }
  <li>
    <a href="#">Job</a>
    <ul>
      {if $permission->Check('job','view') OR $permission->Check('job','view_own')}
      <li><a href="{$BASE_URL}job/JobList.php"></a></li>
      {/if}
      {if $permission->Check('job_item','view') OR $permission->Check('job_item','view_own')}
      <li><a href="{$BASE_URL}job_item/JobItemList.php"></a></li>
      {/if}
      {if $permission->Check('job','view') OR $permission->Check('job','view_own')}
      <li><a href="{$BASE_URL}job/JobGroupList.php"></a></li>
      {/if}
      {if $permission->Check('job_item','view') OR $permission->Check('job_item','view_own')}
      <li><a href="{$BASE_URL}job_item/JobItemGroupList.php"></a></li>
      {/if}
    </ul>
  </li>
  {/if}
    
  {if $permission->Check('client','enabled')
		AND ( $permission->Check('client','view')
			OR $permission->Check('client','view_own') ) }
  <li>    
    <a href="#">Invoice</a>
    <ul>
      {if $permission->Check('client','view') OR $permission->Check('client','view_own')}
      <li><a href="{$BASE_URL}client/ClientList.php"></a></li>
      {/if}
      {if $permission->Check('invoice','view') OR $permission->Check('invoice','view_own')}
      <li><a href="{$BASE_URL}invoice/InvoiceList.php"></a></li>
      {/if}
      {if $permission->Check('transaction','view') OR $permission->Check('transaction','view_own')}
      <li><a href="{$BASE_URL}invoice/TransactionList.php"></a></li>
      {/if}
      {if $permission->Check('product','view') OR $permission->Check('product','view_own')}
      <li><a href="{$BASE_URL}product/ProductList.php"></a></li>
      {/if}
      {if $permission->Check('tax_policy','view') OR $permission->Check('tax_policy','view_own')}
      <li><a href="">Policies</a></li>
      <li><a href="{$BASE_URL}invoice/DistrictList.php"></a></li>
      {/if}
      {if $permission->Check('client','view') OR $permission->Check('client','view_own')}
      <li><a href="{$BASE_URL}client/ClientGroupList.php"></a></li>
      {/if}
      {if $permission->Check('product','view') OR $permission->Check('product','view_own')}
      <li><a href="{$BASE_URL}product/ProductGroupList.php"></a></li>
      {/if}
      {if $permission->Check('payment_gateway','edit') OR $permission->Check('payment_gateway','edit_own')}
      <li><a href="{$BASE_URL}invoice/PaymentGatewayList.php">Payment Gateway</a></li>
      {/if}
      {if $permission->Check('invoice_config','edit') OR $permission->Check('invoice_config','edit_own')}
      <li><a href="{$BASE_URL}invoice/EditInvoiceConfig.php"></a></li>
      {/if}
      {if $permission->Check('tax_policy','view') OR $permission->Check('tax_policy','view_own') }
      <li>
        <a href="#">Invoice Policies</a>
        <ul>
          {if $permission->Check('tax_policy','view') OR $permission->Check('tax_policy','view_own')}
          <li><a href="{$BASE_URL}invoice_policy/TaxPolicyList.php"></a></li>
          {/if}
          {if $permission->Check('shipping_policy','view') OR $permission->Check('shipping_policy','view_own')}
          <li><a href="{$BASE_URL}invoice_policy/ShippingPolicyList.php"></a></li>
          {/if}
          {if $permission->Check('area_policy','view') OR $permission->Check('area_policy','view_own')}
          <li><a href="{$BASE_URL}invoice_policy/AreaPolicyList.php"></a></li>
          {/if}
        </ul>
      </li>
      {/if}
    </ul>
  </li>
  {/if}

  {if $permission->Check('document','enabled')
		AND ( $permission->Check('document','view')
			OR $permission->Check('document','view_own')
			OR $permission->Check('document','view_private')
			) }
  <li>
    <a href="#">Document</a>
    <ul>
      {if $permission->Check('document','view') OR $permission->Check('document','view_own') OR $permission->Check('document','view_private')}
      <li><a href="{$BASE_URL}document/DocumentList.php"></a></li>
      {/if}
      {if $permission->Check('document','edit') }
      <li><a href="{$BASE_URL}document/DocumentGroupList.php"></a></li>
      {/if}
    </ul>
  </li>
  {/if}

  {if ( $permission->Check('station','enabled') AND $permission->Check('station','view') ) 
  	OR ( $permission->Check('user','enabled') AND ( $permission->Check('user','view') OR $permission->Check('user','view_child') ) )
    OR ( $permission->Check('department','enabled') AND $permission->Check('department','view') )
    OR ( $permission->Check('branch','enabled') AND $permission->Check('branch','view') )
    OR ( $permission->Check('company','enabled') AND $permission->Check('company','view') )
    OR ( $permission->Check('pay_period_schedule','enabled') AND $permission->Check('pay_period_schedule','view') )
    OR ( $permission->Check('hierarchy','enabled') AND $permission->Check('hierarchy','view') )
    OR ( $permission->Check('authorization','enabled') AND $permission->Check('authorization','view') )}
  <li>
    <a href="#">Admin</a>
    <ul>
      {if $permission->Check('user','enabled') AND ( $permission->Check('user','view') OR $permission->Check('user','view_child') )}
      <li><a href="{$BASE_URL}users/UserList.php">Employee Administration</a></li>
      {/if}
      {if $current_company->getProductEdition() > 10 AND $permission->Check('company','enabled') AND $permission->Check('company','view')}
      <li><a href="{$BASE_URL}company/CompanyList.php"></a></li>
      {/if}
      {if $permission->Check('help','enabled') AND $permission->Check('help','edit')}
      <li><a href="{$BASE_URL}help/HelpList.php"></a></li>
      {/if}
      {if $permission->Check('help','enabled') AND $permission->Check('help','edit')}
      <li><a href="{$BASE_URL}help/HelpGroupControlList.php"></a></li>
      {/if}
      {if ( $permission->Check('company','enabled') AND $permission->Check('company','edit_own') )
        OR ( $permission->Check('user','enabled') AND $permission->Check('user','edit') AND $permission->Check('user','add') )
        OR ( $permission->Check('branch','enabled') AND $permission->Check('branch','view') )
        OR ( $permission->Check('currency','enabled') AND $permission->Check('currency','view') )
        OR ( $permission->Check('station','enabled') AND $permission->Check('station','view') )
        OR ( $permission->Check('round_policy','enabled') AND $permission->Check('round_policy','view') )
        OR ( $permission->Check('permission','enabled') AND $permission->Check('permission','edit') )
        OR ( $permission->Check('hierarchy','enabled') AND $permission->Check('hierarchy','view') )
        OR ( $permission->Check('company','enabled') AND $permission->Check('company','edit_own_bank') )}
      <li>
        <a href="#">Company</a>
        <ul>
          {if $permission->Check('company','enabled') AND $permission->Check('company','edit_own')}
          <li><a href="{$BASE_URL}company/EditCompany.php?id">Company Information</a></li>
          {/if}
          {if $permission->Check('user','enabled') AND $permission->Check('user','edit') AND $permission->Check('user','add')}
          <li><a href="{$BASE_URL}users/UserTitleList.php">Employee Titles</a></li>
          <li><a href="{$BASE_URL}users/UserGroupList.php">Employee Groups</a></li>
          {/if}
          {if $permission->Check('currency','enabled') AND $permission->Check('currency','view')}
          <li><a href="{$BASE_URL}currency/CurrencyList.php">Currencies</a></li>
          {/if}
          {if $permission->Check('branch','enabled') AND $permission->Check('branch','view')}
          <li><a href="{$BASE_URL}branch/BranchList.php">Branches</a></li>
          {/if}
          {if $permission->Check('department','enabled') AND $permission->Check('department','view')}
          <li><a href="{$BASE_URL}department/DepartmentList.php">Departments</a></li>
          {/if}
          {if $permission->Check('wage','enabled') AND $permission->Check('wage','view')}
          <li><a href="{$BASE_URL}company/WageGroupList.php">Secondary Wage Groups</a></li>
          {/if}
          {if $permission->Check('station','enabled') AND $permission->Check('station','view')}
          <li><a href="{$BASE_URL}station/StationList.php">Stations</a></li>
          {/if}
          {if $permission->Check('permission','enabled') AND $permission->Check('permission','edit')}
          <li><a href="{$BASE_URL}permission/PermissionControlList.php">Permission Groups</a></li>
          {/if}
          {if $permission->Check('user','enabled') AND $permission->Check('user','edit') AND $permission->Check('user','add')}
          <li><a href="{$BASE_URL}users/EditUserDefault.php">New Hire Defaults</a></li>
          {/if}
          {if $permission->Check('hierarchy','enabled') AND $permission->Check('hierarchy','view')}
          <li><a href="{$BASE_URL}hierarchy/HierarchyControlList.php">Hierarchy</a></li>
          {/if}
          {if $permission->Check('company','enabled') AND $permission->Check('company','edit_own_bank')}
          <li><a href="{$BASE_URL}bank_account/EditBankAccount.php?company_id">Company Bank Information</a></li>
          {/if}
          {if $permission->Check('holiday_policy','enabled') AND $permission->Check('holiday_policy','view')}
          <li><a href="{$BASE_URL}policy/RecurringHolidayList.php">Recurring Holidays</a></li>
          {/if}
          {if $permission->Check('other_field','enabled') AND $permission->Check('other_field','view')}
          <li><a href="{$BASE_URL}company/OtherFieldList.php">Other Fields</a></li>
          {/if}
        </ul>
      </li>
      {/if}

      {if ( $permission->Check('round_policy','enabled') AND $permission->Check('round_policy','view') )
      		OR ( $permission->Check('policy_group','enabled') AND $permission->Check('policy_group','view') )
          OR ( $permission->Check('schedule_policy','enabled') AND $permission->Check('schedule_policy','view') )
          OR ( $permission->Check('meal_policy','enabled') AND $permission->Check('meal_policy','view') )
          OR ( $permission->Check('break_policy','enabled') AND $permission->Check('break_policy','view') )
          OR ( $permission->Check('over_time_policy','enabled') AND $permission->Check('over_time_policy','view') )
          OR ( $permission->Check('premium_policy','enabled') AND $permission->Check('premium_policy','view') )
          OR ( $permission->Check('accrual_policy','enabled') AND $permission->Check('accrual_policy','view') )
          OR ( $permission->Check('absence_policy','enabled') AND $permission->Check('absence_policy','view') )
          OR ( $permission->Check('round_policy','enabled') AND $permission->Check('round_policy','view') )
          OR ( $permission->Check('exception_policy','enabled') AND $permission->Check('exception_policy','view') )
          OR ( $permission->Check('holiday_policy','enabled') AND $permission->Check('holiday_policy','view') )}
      <li>
        <a href="#">Policies</a>
        <ul>
          {if $permission->Check('policy_group','enabled') AND $permission->Check('policy_group','view')}
          <li><a href="{$BASE_URL}policy/PolicyGroupList.php">Policy Groups</a></li>
          {/if}
          {if $permission->Check('schedule_policy','enabled') AND $permission->Check('schedule_policy','view')}
          <li><a href="{$BASE_URL}policy/SchedulePolicyList.php">Schedule Policies</a></li>
          {/if}
          {if $permission->Check('round_policy','enabled') AND $permission->Check('round_policy','view')}
          <li><a href="{$BASE_URL}policy/RoundIntervalPolicyList.php">Rounding Policies</a></li>
          {/if}
          {if $permission->Check('meal_policy','enabled') AND $permission->Check('meal_policy','view')}
          <li><a href="{$BASE_URL}policy/MealPolicyList.php">Meal Policies</a></li>
          {/if}
          {if $permission->Check('break_policy','enabled') AND $permission->Check('break_policy','view')}
          <li><a href="{$BASE_URL}policy/BreakPolicyList.php">Break Policies</a></li>
          {/if}
          {if $permission->Check('accrual_policy','enabled') AND $permission->Check('accrual_policy','view')}
          <li><a href="{$BASE_URL}policy/AccrualPolicyList.php">Accrual Policies</a></li>
          {/if}
          {if $permission->Check('over_time_policy','enabled') AND $permission->Check('over_time_policy','view')}
          <li><a href="{$BASE_URL}policy/OverTimePolicyList.php">Overtime Policies</a></li>
          {/if}
          {if $permission->Check('premium_policy','enabled') AND $permission->Check('premium_policy','view')}
          <li><a href="{$BASE_URL}policy/PremiumPolicyList.php">Premium Policies</a></li>
          {/if}
          {if $permission->Check('absence_policy','enabled') AND $permission->Check('absence_policy','view')}
          <li><a href="{$BASE_URL}policy/AbsencePolicyList.php">Absence Policies</a></li>
          {/if}
          {if $permission->Check('exception_policy','enabled') AND $permission->Check('exception_policy','view')}
          <li><a href="{$BASE_URL}policy/ExceptionPolicyControlList.php">Exception Policies</a></li>
          {/if}
          {if $permission->Check('holiday_policy','enabled') AND $permission->Check('holiday_policy','view')}
          <li><a href="{$BASE_URL}policy/HolidayPolicyList.php">Holiday Policies</a></li>
          {/if}
        </ul>
      </li>
      {/if}
      {if ( $permission->Check('pay_period_schedule','enabled') AND $permission->Check('pay_period_schedule','view') )
          OR ( $permission->Check('pay_stub_amendment','enabled') AND ( $permission->Check('pay_stub_amendment','view') OR $permission->Check('pay_stub_amendment','view_child') OR $permission->Check('pay_stub_amendment','view_own') ) )
          OR ( $permission->Check('pay_period_schedule','enabled') AND $permission->Check('pay_period_schedule','view') )
          OR ( $permission->Check('pay_stub_amendment','enabled') AND $permission->Check('pay_stub_amendment','edit') )}
      <li>
        <a href="#">Payroll</a>
        <ul>
          {if $permission->Check('pay_period_schedule','enabled') AND $permission->Check('pay_period_schedule','view')}
          <li><a href="{$BASE_URL}payperiod/ClosePayPeriod.php">End of Pay Period</a></li>
          {/if}
          {if $permission->Check('pay_stub_amendment','enabled') AND ( $permission->Check('pay_stub_amendment','view') OR $permission->Check('pay_stub_amendment','view_child') OR $permission->Check('pay_stub_amendment','view_own') )}
          <li><a href="{$BASE_URL}pay_stub_amendment/PayStubAmendmentList.php">Pay Stub Amendments</a></li>
          <li><a href="{$BASE_URL}pay_stub_amendment/RecurringPayStubAmendmentList.php">Recurring PS Amendments</a></li>
          {/if}
          {if $permission->Check('pay_period_schedule','enabled') AND $permission->Check('pay_period_schedule','view')}
          <li><a href="{$BASE_URL}payperiod/PayPeriodScheduleList.php">Pay Period Schedules</a></li>
          {/if}
          {if $permission->Check('pay_stub_account','enabled') AND $permission->Check('pay_stub_account','view')}
          <li><a href="{$BASE_URL}pay_stub/PayStubEntryAccountList.php">Pay Stub Accounts</a></li>
          {/if}
          {if $permission->Check('company_tax_deduction','enabled') AND $permission->Check('company_tax_deduction','view')}
          <li><a href="{$BASE_URL}company/CompanyDeductionList.php">Taxes / Deductions</a></li>
          {/if}
          {if $permission->Check('pay_stub_account','enabled') AND $permission->Check('pay_stub_account','view')}
          <li><a href="{$BASE_URL}pay_stub/EditPayStubEntryAccountLink.php">Pay Stub Account Linking</a></li>
          {/if}
        </ul>
      </li>
      {/if}
      {if $permission->Check('authorization','enabled') AND ( $permission->Check('authorization','view') )}
      <li><a href="{$BASE_URL}authorization/AuthorizationList.php">Authorizations</a></li>
      {/if}
    </ul>
  </li>
  {/if}

  {if $permission->Check('report','enabled')}
  <li>
    <a href="#">Reports</a>
    <ul>

      {if $permission->Check('job_report','enabled') }
      <li>
        <a href="#">Report Job</a>
        <ul>
          {if $permission->Check('job_report','view_job_summary')}
          <li><a href="{$BASE_URL}report/JobSummary.php">Job Summary</a></li>
          {/if}
          {if $permission->Check('job_report','view_job_analysis')}
          <li><a href="{$BASE_URL}report/JobDetail.php">Job Analysis</a></li>
          {/if}
          {if $permission->Check('job_report','view_job_payroll_analysis')}
          <li><a href="{$BASE_URL}report/JobPayrollDetail.php">Job Payroll Analysis</a></li>
          {/if}
          {if $permission->Check('job_report','view_job_barcode')}
          <li><a href="{$BASE_URL}report/JobBarcode.php">Barcodes</a></li>
          {/if}
        </ul>
      </li>
      {/if}
      
      {if $permission->Check('invoice_report','enabled') }
      <li>
        <a href="#">Report Invoice</a>
        <ul>
          {if $permission->Check('invoice_report','view_transaction_summary')}
          <li><a href="{$BASE_URL}report/InvoiceTransactionSummary.php">Transaction Summary</a></li>
          {/if}
        </ul>
      </li>
      {/if}

      {if $permission->Check('report','view_active_shift')}
      <li><a href="{$BASE_URL}report/ActiveShiftList.php">Whos In Summary</a></li>
      {/if}
      {if $permission->Check('report','view_user_information')}
      <li><a href="{$BASE_URL}report/UserInformation.php">Employee Information Summary</a></li>
      {/if}
      {if $permission->Check('report','view_user_detail')}
      <li><a href="{$BASE_URL}report/UserDetail.php">Employee Detail</a></li>
      {/if}
			{if $permission->Check('report','view_schedule_summary')}
			<li><a href="{$BASE_URL}report/ScheduleSummary.php">{t}Schedule Summary{/t}</a></li>
			{/if}
      {if $permission->Check('report','view_timesheet_summary')}
      <li><a href="{$BASE_URL}report/TimesheetSummary.php">Timesheet Summary</a></li>
      <li><a href="{$BASE_URL}report/TimesheetDetail.php">Timesheet Detail</a></li>
      {/if}
      {if $permission->Check('report','view_punch_summary')}
      <li><a href="{$BASE_URL}report/PunchSummary.php">Punch Summary</a></li>
      {/if}
      {if $permission->Check('report','view_accrual_balance_summary')}
      <li><a href="{$BASE_URL}report/AccrualBalanceSummary.php">Accrual Balance Summary</a></li>
      {/if}
      {if $permission->Check('report','view_pay_stub_summary')}
      <li><a href="{$BASE_URL}report/PayStubSummary.php">Pay Stub Summary</a></li>
      {/if}
      {if $permission->Check('report','view_wages_payable_summary')}
      <li><a href="{$BASE_URL}report/WagesPayableSummary.php">Wages Payable Summary</a></li>
      {/if}
      {if $permission->Check('report','view_payroll_export')}
      <li><a href="{$BASE_URL}report/PayrollExport.php">Payroll Export</a></li>
      {/if}
      {if $permission->Check('report','view_system_log')}
      <li><a href="{$BASE_URL}report/SystemLog.php">Audit Trail</a></li>
      {/if}
      {if $permission->Check('report','view_general_ledger_summary')}
      <li><a href="{$BASE_URL}report/GeneralLedgerSummary.php">General Ledger Summary</a></li>
      {/if}
      {if $permission->Check('report','view_user_barcode')}
      <li><a href="{$BASE_URL}report/UserBarcode.php">Employee Barcodes</a></li>
      {/if}

      {if $permission->Check('report','view_remittance_summary') 
        OR $permission->Check('report','view_t4_summary')
        OR $permission->Check('report','view_form941')
        OR $permission->Check('report','view_form1099misc')}
      <li>
        <a href="#">Tax Reports</a>
        <ul>
          {if $current_company->getCountry() == 'CA'}
          {if $permission->Check('report','view_remittance_summary')}
          <li><a href="{$BASE_URL}report/RemittanceSummary.php">Remittance Summary</a></li>
          {/if}
          {if $permission->Check('report','view_t4_summary')}
          <li><a href="{$BASE_URL}report/T4Summary.php">T4 Summary</a></li>
          <li><a href="{$BASE_URL}report/T4ASummary.php">T4A Summary</a></li>
          {/if}
          {/if}
          {if $current_company->getCountry() == 'US'}
          {if $permission->Check('report','view_form941')}
          <li><a href="{$BASE_URL}report/Form941.php">Form 941</a></li>
          {/if}
          {if $permission->Check('report','view_form940')}
          <li><a href="{$BASE_URL}report/Form940.php">FUTA - Form 940</a></li>
          {/if}
          {if $permission->Check('report','view_form940ez')}
          <li><a href="{$BASE_URL}report/Form940ez.php">FUTA - Form 940-EZ</a></li>
          {/if}
          {if $permission->Check('report','view_form1099misc')}
          <li><a href="{$BASE_URL}report/Form1099Misc.php">Form 1099-Misc</a></li>
          {/if}
          {if $permission->Check('report','view_formW2')}
          <li><a href="{$BASE_URL}report/FormW2.php">Form W2 / W3</a></li>
          {/if}
          {/if}
          {if $permission->Check('report','view_generic_tax_summary')}
          <li><a href="{$BASE_URL}report/GenericTaxSummary.php">Tax Summary (Generic)</a></li>
          {/if}
        </ul>
      </li>
      {/if}
    </ul>
  </li>
  {/if}

  {if $permission->Check('user','edit_own')
    OR $permission->Check('user_preference','enabled')
    OR $permission->Check('user','edit_own_bank')
    OR $permission->Check('message','enabled')}
  <li>
    <a href="#">My Account</a>
    <ul>
      {if $permission->Check('message','enabled') }
      <li><a href="{$BASE_URL}message/UserMessageList.php">Messages</a></li>
      {/if}
      {if $permission->Check('user','edit_own')}
      <li><a href="{$BASE_URL}users/EditUser.php?id">Contact Information</a></li>
      {/if}
      {if $permission->Check('user_preference','enabled') AND ( $permission->Check('user_preference','edit_own') OR $permission->Check('user_preference','edit') OR $permission->Check('user_preference','edit_child') )}
      <li><a href="{$BASE_URL}users/EditUserPreference.php">Preferences</a></li>
      {/if}
      {if $permission->Check('user','edit_own_password')}
      <li><a href="{$BASE_URL}users/EditUserPassword.php?id">Web Password</a></li>
      {/if}
      
			{if $permission->Check('user','edit_own_phone_password')}
      <li><a href="{$BASE_URL}users/EditUserPhonePassword.php?id={$current_user->getId()}">{t}Quick Punch Password{/t}</a></li>
      {/if}      
      
      {if $permission->Check('user','edit_own_bank')}
      <li><a href="{$BASE_URL}bank_account/EditBankAccount.php">Bank Information</a></li>
      {/if}
    </ul>
  </li>
  {/if}

  <li><a href="{$BASE_URL}Logout.php">Logout</a></li>

  {if $permission->Check('user','edit') OR $permission->Check('user','edit_child')
    OR $permission->Check('recurring_schedule','enabled')
    OR $permission->Check('recurring_schedule_template','enabled')}
  <li>
    <a href="#">{if $system_settings.new_version == 1}<img src = "{$IMAGES_URL}red_flag.gif" />{/if}{t}Help{/t}</a>
    <ul>
    	{if isset($config_vars.urls.university) AND $config_vars.urls.university <> ""}
      <li><a href="$config_vars.urls.university" target="_blank">Online University</a></li>
      {/if}
      
    	{if isset($config_vars.urls.guide) AND $config_vars.urls.guide <> ""}      
      <li><a href="$config_vars.urls.guide" target="_blank">Administrator Guide</a></li>
      {/if}
    	{if isset($config_vars.urls.wiki) AND $config_vars.urls.wiki <> ""}      
      <li><a href="$config_vars.urls.wiki" target="_blank">Wiki</a></li>
      {/if}
    	{if isset($config_vars.urls.whatsnew) AND $config_vars.urls.whatsnew <> ""}      
      <li><a href="$config_vars.urls.whatsnew" target="_blank">What's New</a></li>
      {/if}
			<li><a href="{$BASE_URL}help/About.php"">{if $system_settings.new_version == 1}<img src = "{$IMAGES_URL}red_flag.gif" />{/if}{t}About{/t}</a></li>
    </ul>
  </li>
  {/if}

</ul>
<img src = "{$IMAGES_URL}tab_menu.gif" />

<script type="text/javascript">
{literal}
  $(document).ready(function() { 
    $('#nav-one').superfish(); 
  }); 
{/literal}
</script>
