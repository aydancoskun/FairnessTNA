<ul id="nav-one" class="sf-menu sf-navbar">
	<li><a href="{$BASE_URL}/index.php" ><img src = "{$IMAGES_URL}home_icon.gif" /></a></li>
	{if $permission->Check('punch','enabled') }
		<li>
			<a href="#">
				{if $permission->Check('punch','view') OR $permission->Check('punch','view_own')}{if $display_exception_flag == 'red'}<img src = "{$IMAGES_URL}red_flag.gif" />{/if}{/if}
				{t}Attendance{/t}
			</a>
			<ul>
				{if $permission->Check('punch','enabled') AND $permission->Check('punch','punch_in_out') }
					<li><a href="#" onclick="javascript:timePunch();">{t}In / Out{/t}</a></li>
				{/if}
				{if $permission->Check('punch','view') OR $permission->Check('punch','view_own')}
					<li><a href="{$BASE_URL}timesheet/ViewUserTimeSheet.php">{t}Timesheet{/t}</a></li>
				{/if}
				{if $permission->Check('punch','edit') OR $permission->Check('punch','edit_child')}
					<li><a href="{$BASE_URL}punch/PunchList.php">{t}Punches{/t}</a></li>
				{/if}
				{if $permission->Check('punch','view') OR $permission->Check('punch','view_own')}
					<li><a href="{$BASE_URL}punch/UserExceptionList.php">{if $display_exception_flag != false}<img src = "{$IMAGES_URL}{$display_exception_flag}_flag.gif" />{/if}{t}Exceptions{/t}</a></li>
				{/if}
				{if $permission->Check('request','view') OR $permission->Check('request','view_own')}
					<li><a href="{$BASE_URL}request/UserRequestList.php">{t}Requests{/t}</a></li>
				{/if}
				{if $permission->Check('accrual','view') OR $permission->Check('accrual','view_own')}
					<li><a href="{$BASE_URL}accrual/UserAccrualBalanceList.php">{t}Accrual Balances{/t}</a></li>
				{/if}
			</ul>
		</li>
	{/if}
	{if $permission->Check('schedule','enabled') OR $permission->Check('recurring_schedule','enabled') OR $permission->Check('recurring_schedule_template','enabled')}
		<li>
      <a href="#">{t}Schedule{/t}</a>
			<ul>
				{if $permission->Check('schedule','view') OR $permission->Check('schedule','view_own')}
					<li><a href="{$BASE_URL}schedule/ViewSchedule.php">{t}Schedules{/t}</a></li>
				{/if}
				{if $permission->Check('schedule','edit') OR $permission->Check('schedule','edit_child')}
					<li><a href="{$BASE_URL}schedule/ScheduleList.php">{t}Scheduled Shifts{/t}</a></li>
					<li><a href="{$BASE_URL}schedule/AddMassSchedule.php">{t}Mass Schedule{/t}</a></li>
				{/if}
				{if $permission->Check('recurring_schedule','enabled')}
					<li><a href="{$BASE_URL}schedule/RecurringScheduleControlList.php">{t}Recurring Schedule{/t}</a></li>
				{/if}
				{if $permission->Check('recurring_schedule_template','enabled')}
					<li><a href="{$BASE_URL}schedule/RecurringScheduleTemplateControlList.php">{t}Recurring Schedule Template{/t}</a></li>
				{/if}
			</ul>
		</li>
	{/if}
	{if false AND $permission->Check('job','enabled')	AND ( $permission->Check('job','view') OR $permission->Check('job','view_own') ) }
		<li>
			<a href="#">{t}Jobs{/t}</a>
			<ul>
      	{if $permission->Check('job','view') OR $permission->Check('job','view_own')}
					<li><a href="{$BASE_URL}job/JobList.php">{t}Jobs{/t}</a></li>
				{/if}
				{if $permission->Check('job_item','view') OR $permission->Check('job_item','view_own')}
					<li><a href="{$BASE_URL}job_item/JobItemList.php">{t}Tasks{/t}</a></li>
				{/if}
				{if $permission->Check('job','view') OR $permission->Check('job','view_own')}
					<li><a href="{$BASE_URL}job/JobGroupList.php">{t}Job Groups{/t}</a></li>
				{/if}
				{if $permission->Check('job_item','view') OR $permission->Check('job_item','view_own')}
					<li><a href="{$BASE_URL}job_item/JobItemGroupList.php">{t}Task Groups{/t}</a></li>
				{/if}
			</ul>
		</li>
	{/if}
  <li>
    <a href="#">{t}Employee{/t}</a>
    <ul>
      {if $permission->Check('user','enabled') AND ( $permission->Check('user','view') OR $permission->Check('user','view_child') )}
	      <li><a href="{$BASE_URL}users/UserList.php">{t}Employees{/t}</a></li>
      {/if}
      {if false AND $permission->Check('user_contact','enabled') AND ( $permission->Check('user_contact','view') OR $permission->Check('user_contact','view_child') )}
  	    <li><a href="#">{t}Employee Contacts{/t}</a></li>
      {/if}
      {if false AND $permission->Check('user_preference','enabled') AND ( $permission->Check('user_preference','view') OR $permission->Check('user_preference','view_child') )}
    	  <li><a href="#">{t}Preferences{/t}</a></li>
      {/if}
      {if false AND $permission->Check('wage','enabled') AND ( $permission->Check('wage','view') )}
      	<li><a href="#">{t}Wages{/t}</a></li>
      {/if}
      {if false AND  $permission->Check('user','enabled') AND ( $permission->Check('user','edit_own_bank') OR $permission->Check('user','edit_bank') )}
	      <li><a href="#">{t}Bank Accounts{/t}</a></li>
      {/if}
      {if $permission->Check('user','enabled') AND $permission->Check('user','edit') AND $permission->Check('user','add')}
        <li><a href="{$BASE_URL}users/UserTitleList.php">{t}Job Titles{/t}</a></li>
        <li><a href="{$BASE_URL}users/UserGroupList.php">{t}Groups{/t}</a></li>
      {/if}
      {if $permission->Check('user','enabled') AND $permission->Check('user','edit') AND $permission->Check('user','add')}
        <li><a href="{$BASE_URL}users/EditUserDefault.php">{t}New Hire Defaults{/t}</a></li>
      {/if}
      {if false AND $permission->Check('roe','enabled') AND $permission->Check('roe','view') }
        <li><a href="#">{t}Record of Employment{/t}</a></li>
      {/if}
    </ul>
  </li>
  <li>
    <a href="#">{t}Company{/t}</a>
    <ul>
      {if false AND $permission->Check('company','enabled') AND $permission->Check('company','view')}
      <li><a href="{$BASE_URL}company/CompanyList.php">{t}Companies{/t}</a></li>
      {/if}
      {if $permission->Check('company','enabled') AND $permission->Check('company','edit_own')}
        <li><a href="{$BASE_URL}company/EditCompany.php?id">{t}Company Information{/t}</a></li>
      {/if}
      {if $permission->Check('pay_period_schedule','enabled') AND $permission->Check('pay_period_schedule','view')}
        <li><a href="{$BASE_URL}payperiod/PayPeriodScheduleList.php">{t}Pay Period Schedules{/t}</a></li>
      {/if}
      {if $permission->Check('branch','enabled') AND $permission->Check('branch','view')}
	      <li><a href="{$BASE_URL}branch/BranchList.php">{t}Branches{/t}</a></li>
      {/if}
      {if $permission->Check('department','enabled') AND $permission->Check('department','view')}
  	    <li><a href="{$BASE_URL}department/DepartmentList.php">{t}Departments{/t}</a></li>
      {/if}
      {if $permission->Check('hierarchy','enabled') AND $permission->Check('hierarchy','view')}
        <li><a href="{$BASE_URL}hierarchy/HierarchyControlList.php">{t}Hierarchy{/t}</a></li>
      {/if}
      {if $permission->Check('wage','enabled') AND $permission->Check('wage','view')}
    	  <li><a href="{$BASE_URL}company/WageGroupList.php">{t}Secondary Wage Groups{/t}</a></li>
      {/if}
      {if  false AND ($permission->Check('user','view') OR $permission->Check('user','view_own') OR $permission->Check('user','view_child') )}
    	  <li><a href="#">{t}Ethnic Groups{/t}</a></li>
      {/if}
      {if $permission->Check('station','enabled') AND $permission->Check('station','view')}
	      <li><a href="{$BASE_URL}station/StationList.php">{t}Stations{/t}</a></li>
      {/if}
      {if $permission->Check('permission','enabled') AND $permission->Check('permission','edit')}
  	    <li><a href="{$BASE_URL}permission/PermissionControlList.php">{t}Permission Groups{/t}</a></li>
      {/if}
      {if $permission->Check('currency','enabled') AND $permission->Check('currency','view')}
    	  <li><a href="{$BASE_URL}currency/CurrencyList.php">{t}Currencies{/t}</a></li>
      {/if}
      {if $permission->Check('company','enabled') AND $permission->Check('company','edit_own_bank')}
        <li><a href="{$BASE_URL}bank_account/EditBankAccount.php?company_id">{t}Bank Account{/t}</a></li>
      {/if}
      {if $permission->Check('other_field','enabled') AND $permission->Check('other_field','view')}
	      <li><a href="{$BASE_URL}company/OtherFieldList.php">{t}Custom Fields{/t}</a></li>
      {/if}
    </ul>
  </li>

  <li>
    <a href="#">{t}Payroll{/t}</a>
    <ul>
	    {if $false AND permission->Check('pay_period_schedule','enabled') AND $permission->Check('pay_period_schedule','view')}
	      <li><a href="#">{t}Process Payroll{/t}</a></li>
      {/if}
			{if $permission->Check('pay_stub','view') OR $permission->Check('pay_stub','view_own')}
				<li><a href="{$BASE_URL}pay_stub/PayStubList.php">{t}Pay Stubs{/t}</a></li>
			{/if}
	    {if $permission->Check('pay_period_schedule','enabled') AND $permission->Check('pay_period_schedule','view')}
	      <li><a href="{$BASE_URL}payperiod/ClosePayPeriod.php">{t}Pay Periods{/t}</a></li>
      {/if}
      {if $permission->Check('pay_stub_amendment','enabled') AND ( $permission->Check('pay_stub_amendment','view') OR $permission->Check('pay_stub_amendment','view_child') OR $permission->Check('pay_stub_amendment','view_own') )}
        <li><a href="{$BASE_URL}pay_stub_amendment/PayStubAmendmentList.php">{t}Pay Stub Amendments{/t}</a></li>
        <li><a href="{$BASE_URL}pay_stub_amendment/RecurringPayStubAmendmentList.php">{t}Recurring PS Amendments{/t}</a></li>
      {/if}
      {if $permission->Check('pay_period_schedule','enabled') AND $permission->Check('pay_period_schedule','view')}
        <li><a href="{$BASE_URL}payperiod/PayPeriodScheduleList.php">{t}Pay Period Schedules{/t}</a></li>
      {/if}
      {if $permission->Check('pay_stub_account','enabled') AND $permission->Check('pay_stub_account','view')}
        <li><a href="{$BASE_URL}pay_stub/PayStubEntryAccountList.php">{t}Pay Stub Accounts{/t}</a></li>
      {/if}
      {if $permission->Check('company_tax_deduction','enabled') AND $permission->Check('company_tax_deduction','view')}
	      <li><a href="{$BASE_URL}company/CompanyDeductionList.php">{t}Taxes / Deductions{/t}</a></li>
      {/if}
      {if $permission->Check('pay_stub_account','enabled') AND $permission->Check('pay_stub_account','view')}
        <li><a href="{$BASE_URL}pay_stub/EditPayStubEntryAccountLink.php">{t}Pay Stub Account Linking{/t}</a></li>
      {/if}
      {if false AND $permission->Check('user_expense','enabled') AND $permission->Check('user_expense','view') OR $permission->Check('user_expense','view_child') OR $permission->Check('user_expense','view_own') }
        <li><a href="#">{t}Expenses{/t}</a></li>
      {/if}
    </ul>
  </li>
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
    	<a href="#">{t}Policies{/t}</a>
      <ul>
        {if $permission->Check('policy_group','enabled') AND $permission->Check('policy_group','view')}
    	    <li><a href="{$BASE_URL}policy/PolicyGroupList.php">{t}Policy Groups{/t}</a></li>
        {/if}
        {if $permission->Check('schedule_policy','enabled') AND $permission->Check('schedule_policy','view')}
  	      <li><a href="{$BASE_URL}policy/SchedulePolicyList.php">{t}Schedule Policies{/t}</a></li>
        {/if}
        {if $permission->Check('round_policy','enabled') AND $permission->Check('round_policy','view')}
	        <li><a href="{$BASE_URL}policy/RoundIntervalPolicyList.php">{t}Rounding Policies{/t}</a></li>
        {/if}
        {if $permission->Check('meal_policy','enabled') AND $permission->Check('meal_policy','view')}
        	<li><a href="{$BASE_URL}policy/MealPolicyList.php">{t}Meal Policies{/t}</a></li>
        {/if}
        {if $permission->Check('break_policy','enabled') AND $permission->Check('break_policy','view')}
      	  <li><a href="{$BASE_URL}policy/BreakPolicyList.php">{t}Break Policies{/t}</a></li>
        {/if}
        {if $permission->Check('over_time_policy','enabled') AND $permission->Check('over_time_policy','view')}
    	    <li><a href="{$BASE_URL}policy/OverTimePolicyList.php">{t}Overtime Policies{/t}</a></li>
        {/if}
        {if $permission->Check('premium_policy','enabled') AND $permission->Check('premium_policy','view')}
  	      <li><a href="{$BASE_URL}policy/PremiumPolicyList.php">{t}Premium Policies{/t}</a></li>
        {/if}
        {if $permission->Check('exception_policy','enabled') AND $permission->Check('exception_policy','view')}
	        <li><a href="{$BASE_URL}policy/ExceptionPolicyControlList.php">{t}Exception Policies{/t}</a></li>
        {/if}
        {if $permission->Check('accrual_policy','enabled') AND $permission->Check('accrual_policy','view')}
        	<li><a href="{$BASE_URL}policy/AccrualPolicyList.php">{t}Accrual Policies{/t}</a></li>
        {/if}
        {if $permission->Check('absence_policy','enabled') AND $permission->Check('absence_policy','view')}
      	  <li><a href="{$BASE_URL}policy/AbsencePolicyList.php">{t}Absence Policies{/t}</a></li>
        {/if}
				{if $permission->Check('user_expense','enabled') AND $permission->Check('user_expense','view') OR $permission->Check('user_expense','view_child') OR $permission->Check('user_expense','view_own') }
    	    <li><a href="#">{t}Expense Policies{/t}</a></li>
        {/if}
        {if $permission->Check('holiday_policy','enabled') AND $permission->Check('holiday_policy','view')}
  	      <li><a href="{$BASE_URL}policy/HolidayPolicyList.php">{t}Holiday Policies{/t}</a></li>
        {/if}
        {if $permission->Check('holiday_policy','enabled') AND $permission->Check('holiday_policy','view')}
	        <li><a href="{$BASE_URL}policy/RecurringHolidayList.php">{t}Recurring Holidays{/t}</a></li>
        {/if}
      </ul>
    </li>
  {/if}
  {if false AND $permission->Check('client','enabled')
		AND ( $permission->Check('client','view')
			OR $permission->Check('client','view_own') ) }
	  <li>    
	    <a href="#">{t}Invoice{/t}</a>
	    <ul>
				<li>
	  	    <a href="#">{t}Invoice{/t}</a>
					<ul>
			      {if $permission->Check('client','view') OR $permission->Check('client','view_own')}
				      <li><a href="{$BASE_URL}client/ClientList.php">{t}Clients{/t}</a></li>
			      {/if}
			      {if $permission->Check('client','view') OR $permission->Check('client','view_own')}
				      <li><a href="#">{t}Client Contacts{/t}</a></li>
			      {/if}
			      {if $permission->Check('invoice','view') OR $permission->Check('invoice','view_own')}
				      <li><a href="{$BASE_URL}invoice/InvoiceList.php">{t}Invoices{/t}</a></li>
			      {/if}
			      {if $permission->Check('transaction','view') OR $permission->Check('transaction','view_own')}
			      	<li><a href="{$BASE_URL}invoice/TransactionList.php">{t}Transactions{/t}</a></li>
			      {/if}
			      {if $permission->Check('payment_gateway','edit') OR $permission->Check('payment_gateway','edit_own')}
			    	  <li><a href="#">{t}Payment Methods{/t}</a></li>
			      {/if}
			      {if $permission->Check('product','view') OR $permission->Check('product','view_own')}
			  	    <li><a href="{$BASE_URL}product/ProductList.php">{t}Products{/t}</a></li>
			      {/if}
			      {if $permission->Check('tax_policy','view') OR $permission->Check('tax_policy','view_own')}
				      <li><a href="">{t}Policies{/t}</a></li>
				      <li><a href="{$BASE_URL}invoice/DistrictList.php">{t}District{/t}</a></li>
			      {/if}
	 				</ul>
				</li>
				<li>
	  	    <a href="#">{t}Groups{/t}</a>
					<ul>
			      {if $permission->Check('client','view') OR $permission->Check('client','view_own')}
				      <li><a href="{$BASE_URL}client/ClientGroupList.php">{t}Client{/t}</a></li>
			      {/if}
			      {if $permission->Check('product','view') OR $permission->Check('product','view_own')}
				      <li><a href="{$BASE_URL}product/ProductGroupList.php">{t}Product{/t}</a></li>
			      {/if}
	 				</ul>
				</li>
	      <li>
	        <a href="#">{t}Policies{/t}</a>
	        <ul>
	          {if $permission->Check('tax_policy','view') OR $permission->Check('tax_policy','view_own')}
	  	        <li><a href="{$BASE_URL}invoice_policy/TaxPolicyList.php">{t}Tax{/t}</a></li>
	          {/if}
	          {if $permission->Check('shipping_policy','view') OR $permission->Check('shipping_policy','view_own')}
		          <li><a href="{$BASE_URL}invoice_policy/ShippingPolicyList.php">{t}Shipping{/t}</a></li>
	          {/if}
	          {if $permission->Check('area_policy','view') OR $permission->Check('area_policy','view_own')}
		          <li><a href="{$BASE_URL}invoice_policy/AreaPolicyList.php">{t}Area{/t}</a></li>
	          {/if}
	        </ul>
	      </li>
				<li>
	  	    <a href="#">{t}Settings{/t}</a>
					<ul>
			      {if $permission->Check('payment_gateway','edit') OR $permission->Check('payment_gateway','edit_own')}
				      <li><a href="{$BASE_URL}invoice/PaymentGatewayList.php">{t}Payment Gateway{/t}</a></li>
			      {/if}
			      {if $permission->Check('invoice_config','edit') OR $permission->Check('invoice_config','edit_own')}
				      <li><a href="{$BASE_URL}invoice/EditInvoiceConfig.php">{t}Settings{/t}</a></li>
			      {/if}
	        </ul>
	      </li>
	    </ul>
	  </li>
  {/if}

  {if false AND  $permission->Check('client','enabled')
		AND ( $permission->Check('client','view')
			OR $permission->Check('client','view_own') ) }
	  <li>    
	    <a href="#">{t}HR{/t}</a>
	    <ul>
				<li>
	  	    <a href="#">{t}Reviews{/t}</a>
					<ul>
			      {if $permission->Check('client','view') OR $permission->Check('client','view_own')}
				      <li><a href="#">{t}Reviews{/t}</a></li>
			      {/if}
			      {if $permission->Check('client','view') OR $permission->Check('client','view_own')}
				      <li><a href="#">{t}KPI{/t}</a></li>
			      {/if}
			      {if $permission->Check('invoice','view') OR $permission->Check('invoice','view_own')}
				      <li><a href="#">{t}KPI Groups{/t}</a></li>
			      {/if}
	 				</ul>
				</li>
				<li>
	  	    <a href="#">{t}Qualifications{/t}</a>
					<ul>
			      {if $permission->Check('client','view') OR $permission->Check('client','view_own')}
				      <li><a href="#">{t}Qualifications{/t}</a></li>
			      {/if}
			      {if $permission->Check('product','view') OR $permission->Check('product','view_own')}
				      <li><a href="#">{t}Qualification Groups{/t}</a></li>
			      {/if}
			      {if $permission->Check('product','view') OR $permission->Check('product','view_own')}
				      <li><a href="#">{t}Skills{/t}</a></li>
			      {/if}
			      {if $permission->Check('product','view') OR $permission->Check('product','view_own')}
			    	  <li><a href="#">{t}Education{/t}</a></li>
			      {/if}
			      {if $permission->Check('product','view') OR $permission->Check('product','view_own')}
			  	    <li><a href="#">{t}Memberships{/t}</a></li>
			      {/if}
			      {if $permission->Check('product','view') OR $permission->Check('product','view_own')}
				      <li><a href="#">{t}Licenses{/t}</a></li>
			      {/if}
			      {if $permission->Check('product','view') OR $permission->Check('product','view_own')}
				      <li><a href="#">{t}Languages{/t}</a></li>
			      {/if}
	 				</ul>
				</li>
	      <li>
	        <a href="#">{t}Recruitment{/t}</a>
	        <ul>
	          {if $permission->Check('tax_policy','view') OR $permission->Check('tax_policy','view_own')}
				      <li><a href="#">{t}Job Vacancies{/t}</a></li>
	          {/if}
	          {if $permission->Check('shipping_policy','view') OR $permission->Check('shipping_policy','view_own')}
				      <li><a href="#">{t}Job Applicants{/t}</a></li>
	          {/if}
	          {if $permission->Check('area_policy','view') OR $permission->Check('area_policy','view_own')}
			 	    	<li><a href="#">{t}Job Application{/t}</a></li>
	          {/if}
	        </ul>
	      </li>
	    </ul>
	  </li>
  {/if}
  {if $permission->Check('report','enabled')}
	  <li>
	    <a href="#">{t}Reports{/t}</a>
	    <ul>
	      {if $permission->Check('report','enabled') }
	      <li>
	        <a href="#">{t}Employee Reports{/t}</a>
	        <ul>
						{if $permission->Check('report','view_active_shift')}
							<li><a href="{$BASE_URL}report/ActiveShiftList.php">{t}Whos In Summary{/t}</a></li>
						{/if}
			      {if $permission->Check('report','view_user_information')}
							<li><a href="{$BASE_URL}report/UserInformation.php">{t}Employee Information Summary{/t}</a></li>
						{/if}
						{if $permission->Check('report','view_user_detail')}
							<li><a href="{$BASE_URL}report/UserDetail.php">{t}Employee Detail{/t}</a></li>
						{/if}
						{if $permission->Check('report','view_system_log')}
							<li><a href="{$BASE_URL}report/SystemLog.php">{t}Audit Trail{/t}</a></li>
						{/if}
			      {if $permission->Check('report','view_user_barcode')}
							<li><a href="{$BASE_URL}report/UserBarcode.php">{t}Barcodes{/t}</a></li>
						{/if}
	        </ul>
	      </li>
	      {/if}
	      {if $permission->Check('report','enabled') }
		      <li>
	  	      <a href="#">{t}Timesheet Reports{/t}</a>
						<ul>
							{if $permission->Check('report','view_schedule_summary')}
								<li><a href="{$BASE_URL}report/ScheduleSummary.php">{t}Schedule Summary{/t}</a></li>
							{/if}
				      {if $permission->Check('report','view_timesheet_summary')}
					      <li><a href="{$BASE_URL}report/TimesheetSummary.php">{t}Timesheet Summary{/t}</a></li>
					      <li><a href="{$BASE_URL}report/TimesheetDetail.php">{t}Timesheet Detail{/t}</a></li>
				      {/if}
				      {if $permission->Check('report','view_punch_summary')}
					      <li><a href="{$BASE_URL}report/PunchSummary.php">{t}Punch Summary{/t}</a></li>
				      {/if}
				      {if $permission->Check('report','view_accrual_balance_summary')}
					      <li><a href="{$BASE_URL}report/AccrualBalanceSummary.php">{t}Accrual Balance Summary{/t}</a></li>
				      {/if}
				      {if $permission->Check('report','view_punch_summary')}
					      <li><a href="{$BASE_URL}report/PunchSummary.php">{t}Exception Summary{/t}</a></li>
				      {/if}
	        </ul>
	      </li>
	      {/if}
	      {if $permission->Check('report','enabled') }
		      <li>
	  	      <a href="#">{t}Payroll Reports{/t}</a>
						<ul>
				      {if $permission->Check('report','view_pay_stub_summary')}
					      <li><a href="{$BASE_URL}report/PayStubSummary.php">{t}Pay Stub Summary{/t}</a></li>
				      {/if}
				      {if $permission->Check('report','view_payroll_export')}
					      <li><a href="{$BASE_URL}report/PayrollExport.php">{t}Payroll Export{/t}</a></li>
				      {/if}
				      {if $permission->Check('report','view_general_ledger_summary')}
					      <li><a href="{$BASE_URL}report/GeneralLedgerSummary.php">{t}General Ledger Summary{/t}</a></li>
				      {/if}
				      {if false AND $permission->Check('report','enabled')}
					      <li><a href="#">{t}Expense Summary{/t}</a></li>
				      {/if}
							{if $permission->Check('report','view_wages_payable_summary')}
								<li><a href="{$BASE_URL}report/WagesPayableSummary.php">{t}Wages Payable Summary{/t}</a></li>
							{/if}
	        </ul>
	      </li>
	      {/if}
	      {if $permission->Check('job_report','enabled') }
	      <li>
	        <a href="#">{t}Job Tracking Reports{/t}</a>
	        <ul>
	          {if $permission->Check('job_report','view_job_summary')}
	          <li><a href="{$BASE_URL}report/JobSummary.php">{t}Job Summary{/t}</a></li>
	          {/if}
	          {if $permission->Check('job_report','view_job_analysis')}
	          <li><a href="{$BASE_URL}report/JobDetail.php">{t}Job Analysis{/t}</a></li>
	          {/if}
	          {if false AND $permission->Check('job_report','enabled')}
	          <li><a href="#">{t}Job Information{/t}</a></li>
	          {/if}
	          {if false AND $permission->Check('job_report','enabled')}
	          <li><a href="#">{t}Task Information{/t}</a></li>
	          {/if}
	          {if $permission->Check('job_report','view_job_payroll_analysis')}
	          <li><a href="{$BASE_URL}report/JobPayrollDetail.php">{t}Job Payroll Analysis{/t}</a></li>
	          {/if}
	          {if $permission->Check('job_report','view_job_barcode')}
	          <li><a href="{$BASE_URL}report/JobBarcode.php">{t}Barcodes{/t}</a></li>
	          {/if}
	        </ul>
	      </li>
	      {/if}
	      {if $permission->Check('job_report','enabled') }
		      <li>
		        <a href="#">{t}Invoice Reports{/t}</a>
		        <ul>
		          {if $permission->Check('invoice_report','view_transaction_summary')}
		          	<li><a href="{$BASE_URL}report/InvoiceTransactionSummary.php">{t}Transaction Summary{/t}</a></li>
		          {/if}
							{if $permission->Check('report','view_remittance_summary')}
								<li><a href="{$BASE_URL}report/RemittanceSummary.php">{t}Remittance Summary{/t}</a></li>
							{/if}
		        </ul>
		      </li>
	      {/if}
	      {if $permission->Check('job_report','enabled') }
					<li>
						<a href="#">{t}Tax Reports{/t}</a>
						<ul>
							{if $current_company->getCountry() == 'CA'}
								{if $permission->Check('report','view_t4_summary')}
									<li><a href="{$BASE_URL}report/T4Summary.php">{t}T4 Summary{/t}</a></li>
									<li><a href="{$BASE_URL}report/T4ASummary.php">{t}T4A Summary{/t}</a></li>
								{/if}
							{/if}
							{if $current_company->getCountry() == 'US'}
								{if $permission->Check('report','view_form941')}
									<li><a href="{$BASE_URL}report/Form941.php">{t}Form 941{/t}</a></li>
								{/if}
								{if $permission->Check('report','view_form940')}
									<li><a href="{$BASE_URL}report/Form940.php">{t}FUTA - Form 940{/t}</a></li>
								{/if}
								{if $permission->Check('report','view_form940ez')}
									<li><a href="{$BASE_URL}report/Form940ez.php">{t}FUTA - Form 940-EZ{/t}</a></li>
								{/if}
								{if $permission->Check('report','view_form1099misc')}
									<li><a href="{$BASE_URL}report/Form1099Misc.php">{t}Form 1099-Misc{/t}</a></li>
								{/if}
								{if $permission->Check('report','view_formW2')}
									<li><a href="{$BASE_URL}report/FormW2.php">{t}Form W2 / W3{/t}</a></li>
								{/if}
							{/if}
							{if $permission->Check('report','view_generic_tax_summary')}
								<li><a href="{$BASE_URL}report/GenericTaxSummary.php">{t}Tax Summary (Generic){/t}</a></li>
							{/if}
						</ul>
					</li>
				{/if}
	      {if false AND $permission->Check('report','enabled') }
		      <li>
	  	      <a href="#">{t}HR Reports{/t}</a>
						<ul>
							{if $permission->Check('report','enabled')}
								<li><a href="#">{t}Qualification Summary{/t}</a></li>
							{/if}
				      {if $permission->Check('report','enabled')}
					      <li><a href="#">{t}Review Summary{/t}</a></li>
				      {/if}
				      {if $permission->Check('report','enabled')}
					      <li><a href="#">{t}Recruitment Summary{/t}</a></li>
				      {/if}
				      {if $permission->Check('report','enabled')}
					      <li><a href="#">{t}Recruitment Detail{/t}</a></li>
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
    	<a href="#">{t}Account{/t}</a>
			<ul>
	      {if $permission->Check('user','edit_own') }
		      <li>
	  	      <a href="#">{t}My Account{/t}</a>
						<ul>
							{if $permission->Check('request','view') OR $permission->Check('request','view_own')}
								<li><a href="{$BASE_URL}request/UserRequestList.php">{t}Requests{/t}</a></li>
							{/if}
							{if $permission->Check('message','enabled') }
								<li><a href="{$BASE_URL}message/UserMessageList.php">{t}Messages{/t}</a></li>
							{/if}
							{if $permission->Check('user','edit_own')}
								<li><a href="{$BASE_URL}users/EditUser.php?id">{t}Contact Information{/t}</a></li>
							{/if}
							{if $permission->Check('user','edit_own_bank')}
								<li><a href="{$BASE_URL}bank_account/EditBankAccount.php">{t}Bank Information{/t}</a></li>
							{/if}
							{if $permission->Check('user_preference','enabled') AND ( $permission->Check('user_preference','edit_own') OR $permission->Check('user_preference','edit') OR $permission->Check('user_preference','edit_child') )}
								<li><a href="{$BASE_URL}users/EditUserPreference.php">{t}Preferences{/t}</a></li>
							{/if}
				      {if $permission->Check('user_expense','enabled') AND $permission->Check('user_expense','view_own') }
								<li><a href="#">{t}Expenses{/t}</a></li>
							{/if}
						</ul>
					</li>
				{/if}
	      {if $permission->Check('authorization','enabled') }
		      <li>
	  	      <a href="#">{t}Authoization{/t}</a>
						<ul>
							{if $permission->Check('request','view') OR $permission->Check('request','view_own')}
								<li><a href="{$BASE_URL}request/UserRequestList.php">{t}Request Authorization{/t}</a></li>
							{/if}
							{if $permission->Check('authorization','enabled') AND ( $permission->Check('authorization','view') )}
								<li><a href="{$BASE_URL}authorization/AuthorizationList.php">{t}Timesheet Authorization{/t}</a></li>
							{/if}
							{if $permission->Check('user_expense','enabled') AND $permission->Check('user_expense','view') OR $permission->Check('user_expense','view_child') OR $permission->Check('user_expense','view_own') }
								<li><a href="#">{t}Expenses{/t}</a></li>
							{/if}
						</ul>
					</li>
				{/if}
	      {if false AND $permission->Check('document','enabled') }
		      <li>
	  	      <a href="#">{t}Documents{/t}</a>
						<ul>
							{if $permission->Check('document','view') OR $permission->Check('document','view_own') OR $permission->Check('document','view_private')}
								<li><a href="{$BASE_URL}document/DocumentList.php">{t}Documents{/t}</a></li>
							{/if}
							{if $permission->Check('document','edit') }
								<li><a href="{$BASE_URL}document/DocumentGroupList.php">{t}Document Groups{/t}</a></li>
							{/if}
						</ul>
					</li>
				{/if}
	      {if $permission->Check('authorization','enabled') }
		      <li>
	  	      <a href="#">{t}Passwords{/t}</a>
						<ul>
							{if $permission->Check('user','edit_own_password')}
								<li><a href="{$BASE_URL}users/EditUserPassword.php?id">{t}Web Password{/t}</a></li>
							{/if}
							{if $permission->Check('user','edit_own_phone_password')}
								<li><a href="{$BASE_URL}users/EditUserPhonePassword.php?id={$current_user->getId()}">{t}Quick Punch Password{/t}</a></li>
							{/if}      
						</ul>
					</li>
				{/if}
	      {if false AND $permission->Check('user','edit_own') }
					<li>
						<a href="#">{t}Setup{/t}</a>
						<ul>
							{if $permission->Check('company','enabled') AND $permission->Check('company','edit_own_bank')}
								<li><a href="#">{t}Import{/t}</a></li>
							{/if}
							{if $permission->Check('other_field','enabled') AND $permission->Check('other_field','view')}
								<li><a href="#">{t}Quick Start{/t}</a></li>
							{/if}
						</ul>
					</li>
	      {/if}
			  <li><a href="{$BASE_URL}Logout.php">{t}Logout{/t}</a></li>
			</ul>
		</li>
  {/if}
  {if $permission->Check('user','edit') OR $permission->Check('user','edit_child')
    OR $permission->Check('recurring_schedule','enabled')
    OR $permission->Check('recurring_schedule_template','enabled')
    OR $permission->Check('help','enabled')}
		<li>
    	<a href="#">{if $system_settings.new_version == 1}<img src = "{$IMAGES_URL}red_flag.gif" />{/if}{t}Help{/t}</a>
			<ul>
	      {if $permission->Check('help','enabled') AND $permission->Check('help','edit')}
		      <li><a href="{$BASE_URL}help/HelpList.php">{t}Help Administration{/t}</a></li>
	      {/if}
	      {if $permission->Check('help','enabled') AND $permission->Check('help','edit')}
	  	    <li><a href="{$BASE_URL}help/HelpGroupControlList.php">{t}Help Group Administration{/t}</a></li>
	      {/if}
	
	    	{if isset($config_vars.urls.guide) AND $config_vars.urls.guide <> ""}      
	    	  <li><a href="$config_vars.urls.guide" target="_blank">{t}Administrator Guide{/t}</a></li>
	      {/if}
	    	{if isset($config_vars.urls.university) AND $config_vars.urls.university <> ""}
	      	<li><a href="$config_vars.urls.university" target="_blank">{t}Online University{/t}</a></li>
	      {/if}      
	    	{if isset($config_vars.urls.wiki) AND $config_vars.urls.wiki <> ""}      
		      <li><a href="$config_vars.urls.wiki" target="_blank">{t}Wiki{/t}</a></li>
	      {/if}
	    	{if isset($config_vars.urls.whatsnew) AND $config_vars.urls.whatsnew <> ""}      
	  	    <li><a href="$config_vars.urls.whatsnew" target="_blank">{t}What's New{/t}</a></li>
	      {/if}
	 			<li><a href="{$BASE_URL}help/About.php">{if $system_settings.new_version == 1}<img src = "{$IMAGES_URL}red_flag.gif" />{/if}{t}About{/t}</a></li>
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
