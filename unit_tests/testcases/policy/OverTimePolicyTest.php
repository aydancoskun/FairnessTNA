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

class OverTimePolicyTest extends PHPUnit_Framework_TestCase {
	protected $company_id = NULL;
	protected $user_id = NULL;
	protected $pay_period_schedule_id = NULL;
	protected $pay_period_objs = NULL;
	protected $pay_stub_account_link_arr = NULL;

	public function setUp() {
		global $dd;
		Debug::text('Running setUp(): ', __FILE__, __LINE__, __METHOD__, 10);

		TTDate::setTimeZone('PST8PDT', TRUE); //Due to being a singleton and PHPUnit resetting the state, always force the timezone to be set.

		$dd = new DemoData();
		$dd->setEnableQuickPunch( FALSE ); //Helps prevent duplicate punch IDs and validation failures.
		$dd->setUserNamePostFix( '_'.uniqid( NULL, TRUE ) ); //Needs to be super random to prevent conflicts and random failing tests.
		$this->company_id = $dd->createCompany();
		Debug::text('Company ID: '. $this->company_id, __FILE__, __LINE__, __METHOD__, 10);

		//$dd->createPermissionGroups( $this->company_id, 40 ); //Administrator only.

		$dd->createCurrency( $this->company_id, 10 );

		$dd->createPayStubAccount( $this->company_id );
		$this->createPayStubAccounts();
		//$this->createPayStubAccrualAccount();
		$dd->createPayStubAccountLink( $this->company_id );
		$this->getPayStubAccountLinkArray();

		$dd->createUserWageGroups( $this->company_id );
		$this->user_wage_groups = $dd->user_wage_groups;

		$this->user_id = $dd->createUser( $this->company_id, 100 );

		$this->createPayPeriodSchedule();
		$this->createPayPeriods();
		$this->getAllPayPeriods();


		$this->policy_ids['accrual_policy_account'][10] = $dd->createAccrualPolicyAccount( $this->company_id, 10 ); //Bank

		$this->policy_ids['pay_formula_policy'][100] = $dd->createPayFormulaPolicy( $this->company_id, 100 ); //Reg 1.0x
		$this->policy_ids['pay_formula_policy'][910] = $this->createPayFormulaPolicy( $this->company_id, 910, $this->policy_ids['accrual_policy_account'][10] ); //Bank
		$this->policy_ids['pay_formula_policy'][1101]  = $this->createPayFormulaPolicy( $this->company_id, 1101 ); //Reg Alt Wage #1
		$this->policy_ids['pay_formula_policy'][1102]  = $this->createPayFormulaPolicy( $this->company_id, 1102 ); //Reg Alt Wage #2

		$this->policy_ids['pay_code'][100] = $dd->createPayCode( $this->company_id, 100, $this->policy_ids['pay_formula_policy'][100] ); //Regular
		$this->policy_ids['pay_code'][101] = $dd->createPayCode( $this->company_id, 101, $this->policy_ids['pay_formula_policy'][1101] ); //Regular B1 Alt Wage #1
		$this->policy_ids['pay_code'][102] = $dd->createPayCode( $this->company_id, 102, $this->policy_ids['pay_formula_policy'][1102] ); //Regular B2 Alt Wage #2
		$this->policy_ids['pay_code'][190] = $dd->createPayCode( $this->company_id, 190, $this->policy_ids['pay_formula_policy'][100] ); //Lunch
		$this->policy_ids['pay_code'][192] = $dd->createPayCode( $this->company_id, 192, $this->policy_ids['pay_formula_policy'][100] ); //Break
		$this->policy_ids['pay_code'][300] = $dd->createPayCode( $this->company_id, 300, $this->policy_ids['pay_formula_policy'][100] ); //Prem1
		$this->policy_ids['pay_code'][310] = $dd->createPayCode( $this->company_id, 310, $this->policy_ids['pay_formula_policy'][100] ); //Prem2
		$this->policy_ids['pay_code'][900] = $dd->createPayCode( $this->company_id, 900, $this->policy_ids['pay_formula_policy'][910] ); //Vacation
		$this->policy_ids['pay_code'][910] = $dd->createPayCode( $this->company_id, 910, $this->policy_ids['pay_formula_policy'][100] ); //Bank
		$this->policy_ids['pay_code'][920] = $dd->createPayCode( $this->company_id, 920, $this->policy_ids['pay_formula_policy'][100] ); //Sick

		$this->policy_ids['contributing_pay_code_policy'][10] = $dd->createContributingPayCodePolicy( $this->company_id, 10, array( $this->policy_ids['pay_code'][100], $this->policy_ids['pay_code'][101], $this->policy_ids['pay_code'][102] ) ); //Regular
		$this->policy_ids['contributing_pay_code_policy'][12] = $dd->createContributingPayCodePolicy( $this->company_id, 12, array( $this->policy_ids['pay_code'][100], $this->policy_ids['pay_code'][101], $this->policy_ids['pay_code'][102], $this->policy_ids['pay_code'][190], $this->policy_ids['pay_code'][192] ) ); //Regular+Meal/Break
		$this->policy_ids['contributing_pay_code_policy'][14] = $dd->createContributingPayCodePolicy( $this->company_id, 14, array( $this->policy_ids['pay_code'][100], $this->policy_ids['pay_code'][101], $this->policy_ids['pay_code'][102], $this->policy_ids['pay_code'][190], $this->policy_ids['pay_code'][192], $this->policy_ids['pay_code'][900] ) ); //Regular+Meal/Break+Absence
		$this->policy_ids['contributing_pay_code_policy'][90] = $dd->createContributingPayCodePolicy( $this->company_id, 90, array( $this->policy_ids['pay_code'][900] ) ); //Absence
		$this->policy_ids['contributing_pay_code_policy'][99] = $dd->createContributingPayCodePolicy( $this->company_id, 99, $this->policy_ids['pay_code'] ); //All Time

		$this->policy_ids['contributing_shift_policy'][10] = $dd->createContributingShiftPolicy( $this->company_id, 10, $this->policy_ids['contributing_pay_code_policy'][10] ); //Regular
		$this->policy_ids['contributing_shift_policy'][12] = $dd->createContributingShiftPolicy( $this->company_id, 20, $this->policy_ids['contributing_pay_code_policy'][12] ); //Regular+Meal/Break
		$this->policy_ids['contributing_shift_policy'][14] = $dd->createContributingShiftPolicy( $this->company_id, 40, $this->policy_ids['contributing_pay_code_policy'][14] ); //Regular+Meal/Break+Absence
		$this->policy_ids['contributing_shift_policy'][90] = $dd->createContributingShiftPolicy( $this->company_id, 90, $this->policy_ids['contributing_pay_code_policy'][90] ); //Absence

		$this->policy_ids['regular'][10] = $dd->createRegularTimePolicy( $this->company_id, 10, $this->policy_ids['contributing_shift_policy'][10], $this->policy_ids['pay_code'][100] );
		$this->policy_ids['regular'][12] = $dd->createRegularTimePolicy( $this->company_id, 20, $this->policy_ids['contributing_shift_policy'][12], $this->policy_ids['pay_code'][100] );

		$this->absence_policy_id = $dd->createAbsencePolicy( $this->company_id, 10, $this->policy_ids['pay_code'][900] );

		$this->branch_ids[] = $dd->createBranch( $this->company_id, 10 );
		$this->branch_ids[] = $dd->createBranch( $this->company_id, 20 );

		$this->assertGreaterThan( 0, $this->company_id );
		$this->assertGreaterThan( 0, $this->user_id );

		return TRUE;
	}

	public function tearDown() {
		Debug::text('Running tearDown(): ', __FILE__, __LINE__, __METHOD__, 10);

		//$this->deleteAllSchedules();

		return TRUE;
	}

	function getPayStubAccountLinkArray() {
		$this->pay_stub_account_link_arr = array(
			'total_gross' => CompanyDeductionFactory::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName( $this->company_id, 40, 'Total Gross'),
			'total_deductions' => CompanyDeductionFactory::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName( $this->company_id, 40, 'Total Deductions'),
			'employer_contribution' => CompanyDeductionFactory::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName($this->company_id, 40, 'Employer Total Contributions'),
			'net_pay' => CompanyDeductionFactory::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName($this->company_id, 40, 'Net Pay'),
			'regular_time' => CompanyDeductionFactory::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName($this->company_id, 10, 'Regular Time'),
			);

		return TRUE;
	}

	function createPayStubAccounts() {
		Debug::text('Saving.... Employee Deduction - Other', __FILE__, __LINE__, __METHOD__, 10);
		$pseaf = new PayStubEntryAccountFactory();
		$pseaf->setCompany( $this->company_id );
		$pseaf->setStatus(10);
		$pseaf->setType(20);
		$pseaf->setName('Other');
		$pseaf->setOrder(290);

		if ( $pseaf->isValid() ) {
			$pseaf->Save();
		}

		Debug::text('Saving.... Employee Deduction - Other2', __FILE__, __LINE__, __METHOD__, 10);
		$pseaf = new PayStubEntryAccountFactory();
		$pseaf->setCompany( $this->company_id );
		$pseaf->setStatus(10);
		$pseaf->setType(20);
		$pseaf->setName('Other2');
		$pseaf->setOrder(291);

		if ( $pseaf->isValid() ) {
			$pseaf->Save();
		}

		Debug::text('Saving.... Employee Deduction - EI', __FILE__, __LINE__, __METHOD__, 10);
		$pseaf = new PayStubEntryAccountFactory();
		$pseaf->setCompany( $this->company_id );
		$pseaf->setStatus(10);
		$pseaf->setType(20);
		$pseaf->setName('EI');
		$pseaf->setOrder(292);

		if ( $pseaf->isValid() ) {
			$pseaf->Save();
		}

		Debug::text('Saving.... Employee Deduction - CPP', __FILE__, __LINE__, __METHOD__, 10);
		$pseaf = new PayStubEntryAccountFactory();
		$pseaf->setCompany( $this->company_id );
		$pseaf->setStatus(10);
		$pseaf->setType(20);
		$pseaf->setName('CPP');
		$pseaf->setOrder(293);

		if ( $pseaf->isValid() ) {
			$pseaf->Save();
		}

		//Link Account EI and CPP accounts
		$pseallf = new PayStubEntryAccountLinkListFactory();
		$pseallf->getByCompanyId( $this->company_id );
		if ( $pseallf->getRecordCount() > 0 ) {
			$pseal_obj = $pseallf->getCurrent();
			$pseal_obj->setEmployeeEI( CompanyDeductionFactory::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName($this->company_id, 20, 'EI') );
			$pseal_obj->setEmployeeCPP( CompanyDeductionFactory::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName($this->company_id, 20, 'CPP') );
			$pseal_obj->Save();
		}


		return TRUE;
	}

	function createPayPeriodSchedule() {
		$ppsf = new PayPeriodScheduleFactory();

		$ppsf->setCompany( $this->company_id );
		//$ppsf->setName( 'Bi-Weekly'.rand(1000,9999) );
		$ppsf->setName( 'Bi-Weekly' );
		$ppsf->setDescription( 'Pay every two weeks' );
		$ppsf->setType( 20 );
		$ppsf->setStartWeekDay( 0 );

		$anchor_date = TTDate::getBeginWeekEpoch( ( TTDate::getBeginYearEpoch( time() ) - (86400 * (7 * 6) ) ) ); //Start 6 weeks ago

		$ppsf->setAnchorDate( $anchor_date );

		$ppsf->setStartDayOfWeek( TTDate::getDayOfWeek( $anchor_date ) );
		$ppsf->setTransactionDate( 7 );

		$ppsf->setTransactionDateBusinessDay( TRUE );
		$ppsf->setTimeZone('PST8PDT');

		$ppsf->setDayStartTime( 0 );
		$ppsf->setNewDayTriggerTime( (4 * 3600) );
		$ppsf->setMaximumShiftTime( (16 * 3600) );
		$ppsf->setShiftAssignedDay( 10 );

		$ppsf->setEnableInitialPayPeriods( FALSE );
		if ( $ppsf->isValid() ) {
			$insert_id = $ppsf->Save(FALSE);
			Debug::Text('Pay Period Schedule ID: '. $insert_id, __FILE__, __LINE__, __METHOD__, 10);

			$ppsf->setUser( array($this->user_id) );
			$ppsf->Save();

			$this->pay_period_schedule_id = $insert_id;

			return $insert_id;
		}

		Debug::Text('Failed Creating Pay Period Schedule!', __FILE__, __LINE__, __METHOD__, 10);

		return FALSE;

	}

	function createPayPeriods() {
		$max_pay_periods = 35;

		$ppslf = new PayPeriodScheduleListFactory();
		$ppslf->getById( $this->pay_period_schedule_id );
		if ( $ppslf->getRecordCount() > 0 ) {
			$pps_obj = $ppslf->getCurrent();

			for ( $i = 0; $i < $max_pay_periods; $i++ ) {
				if ( $i == 0 ) {
					//$end_date = TTDate::getBeginYearEpoch( strtotime('01-Jan-07') );
					//$end_date = TTDate::getBeginWeekEpoch( ( TTDate::getBeginYearEpoch( time() )-(86400*(7*6) ) ) );
					$end_date = TTDate::getBeginWeekEpoch( ( TTDate::getBeginWeekEpoch( time() ) - (86400 * (7 * 52) ) ) ); //Go back 52 weeks.
				} else {
					$end_date = ($end_date + ( (86400 * 14) ));
				}

				Debug::Text('I: '. $i .' End Date: '. TTDate::getDate('DATE+TIME', $end_date), __FILE__, __LINE__, __METHOD__, 10);

				$pps_obj->createNextPayPeriod( $end_date, (86400 * 3600), FALSE ); //Don't import punches, as that causes deadlocks when running tests in parallel.
			}

		}

		return TRUE;
	}

	function getAllPayPeriods() {
		$pplf = new PayPeriodListFactory();
		//$pplf->getByCompanyId( $this->company_id );
		$pplf->getByPayPeriodScheduleId( $this->pay_period_schedule_id );
		if ( $pplf->getRecordCount() > 0 ) {
			foreach( $pplf as $pp_obj ) {
				Debug::text('Pay Period... Start: '. TTDate::getDate('DATE+TIME', $pp_obj->getStartDate() ) .' End: '. TTDate::getDate('DATE+TIME', $pp_obj->getEndDate() ), __FILE__, __LINE__, __METHOD__, 10);

				$this->pay_period_objs[] = $pp_obj;
			}
		}

		$this->pay_period_objs = array_reverse( $this->pay_period_objs );

		return TRUE;
	}

	function getCurrentPayPeriod( $epoch = NULL ) {
		if ( $epoch == '' ) {
			$epoch = time();
		}

		$this->getAllPayPeriods(); //This doesn't return the pay periods, just populates an array and returns TRUE.
		$pay_periods = $this->pay_period_objs;
		if ( is_array($pay_periods) ) {
			foreach( $pay_periods as $pp_obj ) {
				if ( $pp_obj->getStartDate() <= $epoch AND $pp_obj->getEndDate() >= $epoch ) {
					Debug::text('Current Pay Period... Start: '. TTDate::getDate('DATE+TIME', $pp_obj->getStartDate() ) .' End: '. TTDate::getDate('DATE+TIME', $pp_obj->getEndDate() ), __FILE__, __LINE__, __METHOD__, 10);

					return $pp_obj;
				}
			}
		}

		Debug::text('Current Pay Period not found! Epoch: '. TTDate::getDate('DATE+TIME', $epoch ), __FILE__, __LINE__, __METHOD__, 10);

		return FALSE;
	}

	function createSchedulePolicy( $type, $meal_policy_id ) {
		$spf = TTnew( 'SchedulePolicyFactory' );
		$spf->setCompany( $this->company_id );

		switch ( $type ) {
			case 10: //Normal
				$spf->setName( 'Schedule Policy' );
				//$spf->setAbsencePolicyID( 0 );
				$spf->setStartStopWindow( (3600 * 2) );
				break;
			case 20: //No Lunch
				$spf->setName( 'No Lunch' );
				//$spf->setAbsencePolicyID( 0 );
				$spf->setStartStopWindow( (3600 * 2) );
				break;
		}

		if ( $spf->isValid() ) {
			$insert_id = $spf->Save( FALSE );

			$spf->setMealPolicy( $meal_policy_id );

			Debug::Text('Schedule Policy ID: '. $insert_id, __FILE__, __LINE__, __METHOD__, 10);

			return $insert_id;
		}

		Debug::Text('Failed Creating Schedule Policy!', __FILE__, __LINE__, __METHOD__, 10);

		return FALSE;
	}

	function createSchedule( $user_id, $date_stamp, $data = NULL ) {
		$sf = TTnew( 'ScheduleFactory' );
		$sf->setCompany( $this->company_id );
		$sf->setUser( $user_id );
		//$sf->setUserDateId( UserDateFactory::findOrInsertUserDate( $user_id, $date_stamp) );

		if ( isset($data['status_id']) ) {
			$sf->setStatus( $data['status_id'] );
		} else {
			$sf->setStatus( 10 );
		}

		if ( isset($data['schedule_policy_id']) ) {
			$sf->setSchedulePolicyID( $data['schedule_policy_id'] );
		}

		if ( isset($data['absence_policy_id']) ) {
			$sf->setAbsencePolicyID( $data['absence_policy_id'] );
		}
		if ( isset($data['branch_id']) ) {
			$sf->setBranch( $data['branch_id'] );
		}
		if ( isset($data['department_id']) ) {
			$sf->setDepartment( $data['department_id'] );
		}

		if ( isset($data['job_id']) ) {
			$sf->setJob( $data['job_id'] );
		}

		if ( isset($data['job_item_id'] ) ) {
			$sf->setJobItem( $data['job_item_id'] );
		}

		if ( $data['start_time'] != '') {
			$start_time = strtotime( $data['start_time'], $date_stamp ) ;
		}
		if ( $data['end_time'] != '') {
			Debug::Text('End Time: '. $data['end_time'] .' Date Stamp: '. $date_stamp, __FILE__, __LINE__, __METHOD__, 10);
			$end_time = strtotime( $data['end_time'], $date_stamp ) ;
			Debug::Text('bEnd Time: '. $data['end_time'] .' - '. TTDate::getDate('DATE+TIME', $data['end_time']), __FILE__, __LINE__, __METHOD__, 10);
		}

		$sf->setStartTime( $start_time );
		$sf->setEndTime( $end_time );

		if ( $sf->isValid() ) {
			$sf->setEnableReCalculateDay(TRUE); //This is needed to calculate accrual balances.
			$insert_id = $sf->Save();
			Debug::Text('Schedule ID: '. $insert_id, __FILE__, __LINE__, __METHOD__, 10);

			return $insert_id;
		}

		Debug::Text('Failed Creating Schedule!', __FILE__, __LINE__, __METHOD__, 10);

		return FALSE;
	}

	function createMealPolicy( $company_id, $type ) {
		$mpf = new MealPolicyFactory();
		$mpf->setCompany( $company_id );

		switch ( $type ) {
			case 100: //Normal 1hr lunch
				$mpf->setName( 'Normal' );
				$mpf->setType( 20 );
				$mpf->setTriggerTime( (3600 * 6) );
				$mpf->setAmount( 3600 );
				$mpf->setIncludeLunchPunchTime( FALSE );
				$mpf->setPayCode( $this->policy_ids['pay_code'][190] );
				break;
			case 110: //AutoAdd 1hr
				$mpf->setName( 'AutoAdd 1hr' );
				$mpf->setType( 15 );
				$mpf->setTriggerTime( (3600 * 6) );
				$mpf->setAmount( 3600 );
				$mpf->setIncludeLunchPunchTime( FALSE );
				$mpf->setPayCode( $this->policy_ids['pay_code'][190] );
				break;
			case 115: //AutoAdd 1hr
				$mpf->setName( 'AutoAdd 1hr' );
				$mpf->setType( 15 );
				$mpf->setTriggerTime( (3600 * 6) );
				$mpf->setAmount( 3600 );
				$mpf->setIncludeLunchPunchTime( TRUE );
				$mpf->setPayCode( $this->policy_ids['pay_code'][190] );
				break;
			case 120: //AutoDeduct 1hr
				$mpf->setName( 'AutoDeduct 1hr' );
				$mpf->setType( 10 );
				$mpf->setTriggerTime( (3600 * 6) );
				$mpf->setAmount( 3600 );
				$mpf->setIncludeLunchPunchTime( FALSE );
				$mpf->setPayCode( $this->policy_ids['pay_code'][190] );
				break;
		}

		if ( $mpf->isValid() ) {
			$insert_id = $mpf->Save();
			Debug::Text('Meal Policy ID: '. $insert_id, __FILE__, __LINE__, __METHOD__, 10);

			return $insert_id;
		}

		Debug::Text('Failed Creating Meal Policy!', __FILE__, __LINE__, __METHOD__, 10);

		return FALSE;
	}

	function createBreakPolicy( $company_id, $type ) {
		$bpf = new BreakPolicyFactory();
		$bpf->setCompany( $company_id );

		switch ( $type ) {
			case 100: //Normal 15min break
				$bpf->setName( 'Normal' );
				$bpf->setType( 20 );
				$bpf->setTriggerTime( (3600 * 6) );
				$bpf->setAmount( 60 * 15 );
				$bpf->setIncludeBreakPunchTime( FALSE );
				$bpf->setIncludeMultipleBreaks( FALSE );
				$bpf->setPayCode( $this->policy_ids['pay_code'][192] );
				break;
			case 110: //AutoAdd 15min
				$bpf->setName( 'AutoAdd 15min' );
				$bpf->setType( 15 );
				$bpf->setTriggerTime( (3600 * 1) );
				$bpf->setAmount( 60 * 15 );
				$bpf->setIncludeBreakPunchTime( FALSE );
				$bpf->setIncludeMultipleBreaks( FALSE );
				$bpf->setPayCode( $this->policy_ids['pay_code'][192] );
				break;
			case 115: //AutoAdd 15min
				$bpf->setName( 'AutoAdd 15min (Include Punch Time)' );
				$bpf->setType( 15 );
				$bpf->setTriggerTime( (3600 * 1) );
				$bpf->setAmount( 60 * 15 );
				$bpf->setIncludeBreakPunchTime( TRUE );
				$bpf->setIncludeMultipleBreaks( FALSE );
				$bpf->setPayCode( $this->policy_ids['pay_code'][192] );
				break;

			case 120: //AutoDeduct 15min
				$bpf->setName( 'AutoDeduct 15min' );
				$bpf->setType( 10 );
				$bpf->setTriggerTime( (3600 * 6) );
				$bpf->setAmount( 15 * 60 );
				$bpf->setIncludeBreakPunchTime( FALSE );
				$bpf->setIncludeMultipleBreaks( FALSE );
				$bpf->setPayCode( $this->policy_ids['pay_code'][192] );
				break;
			case 121: //AutoDeduct 15min
				$bpf->setName( 'AutoDeduct 15min (b)' );
				$bpf->setType( 10 );
				$bpf->setTriggerTime( (3600 * 6) );
				$bpf->setAmount( 15 * 60 );
				$bpf->setIncludeBreakPunchTime( FALSE );
				$bpf->setIncludeMultipleBreaks( FALSE );
				$bpf->setPayCode( $this->policy_ids['pay_code'][192] );
				break;
			case 130: //AutoDeduct 30min
				$bpf->setName( 'AutoDeduct 30min' );
				$bpf->setType( 10 );
				$bpf->setTriggerTime( (3600 * 6) );
				$bpf->setAmount( 30 * 60 );
				$bpf->setIncludeBreakPunchTime( FALSE );
				$bpf->setIncludeMultipleBreaks( FALSE );
				$bpf->setPayCode( $this->policy_ids['pay_code'][192] );
				break;

			case 150: //AutoAdd 15min
				$bpf->setName( 'AutoAdd 15min (Include Both)' );
				$bpf->setType( 15 );
				$bpf->setTriggerTime( (3600 * 1) );
				$bpf->setAmount( 60 * 15 );
				$bpf->setIncludeBreakPunchTime( TRUE );
				$bpf->setIncludeMultipleBreaks( TRUE );
				$bpf->setPayCode( $this->policy_ids['pay_code'][192] );
				break;
			case 152: //AutoAdd 15min
				$bpf->setName( 'AutoAdd 15min (Include Both) [2]' );
				$bpf->setType( 15 );
				$bpf->setTriggerTime( (3600 * 3) );
				$bpf->setAmount( 60 * 15 );
				$bpf->setIncludeBreakPunchTime( TRUE );
				$bpf->setIncludeMultipleBreaks( TRUE );
				$bpf->setPayCode( $this->policy_ids['pay_code'][192] );
				break;
			case 154: //AutoAdd 15min
				$bpf->setName( 'AutoAdd 15min (Include Both) [3]' );
				$bpf->setType( 15 );
				$bpf->setTriggerTime( (3600 * 5) );
				$bpf->setAmount( 60 * 15 );
				$bpf->setIncludeBreakPunchTime( TRUE );
				$bpf->setIncludeMultipleBreaks( TRUE );
				$bpf->setPayCode( $this->policy_ids['pay_code'][192] );
				break;
			case 156: //AutoAdd 15min
				$bpf->setName( 'AutoAdd 15min (Include Both) [4]' );
				$bpf->setType( 15 );
				$bpf->setTriggerTime( (3600 * 10) );
				$bpf->setAmount( 60 * 15 );
				$bpf->setIncludeBreakPunchTime( TRUE );
				$bpf->setIncludeMultipleBreaks( TRUE );
				$bpf->setPayCode( $this->policy_ids['pay_code'][192] );
				break;

		}

		if ( $bpf->isValid() ) {
			$insert_id = $bpf->Save();
			Debug::Text('Break Policy ID: '. $insert_id, __FILE__, __LINE__, __METHOD__, 10);

			return $insert_id;
		}

		Debug::Text('Failed Creating Break Policy!', __FILE__, __LINE__, __METHOD__, 10);

		return FALSE;
	}

	function getCurrentAccrualBalance( $user_id, $accrual_policy_account_id = NULL ) {
		if ( $user_id == '' ) {
			return FALSE;
		}

		if ( $accrual_policy_account_id == '' ) {
			$accrual_policy_account_id = $this->getId();
		}

		//Check min/max times of accrual policy.
		$ablf = TTnew( 'AccrualBalanceListFactory' );
		$ablf->getByUserIdAndAccrualPolicyAccount( $user_id, $accrual_policy_account_id );
		if ( $ablf->getRecordCount() > 0 ) {
			$accrual_balance = $ablf->getCurrent()->getBalance();
		} else {
			$accrual_balance = 0;
		}

		Debug::Text('&nbsp;&nbsp; Current Accrual Balance: '. $accrual_balance, __FILE__, __LINE__, __METHOD__, 10);

		return $accrual_balance;
	}

	function getUserDateTotalArray( $start_date, $end_date ) {
		$udtlf = new UserDateTotalListFactory();

		$date_totals = array();

		//Get only system totals.
		$udtlf->getByCompanyIDAndUserIdAndObjectTypeAndStartDateAndEndDate( $this->company_id, $this->user_id, array(5, 20, 25, 30, 40, 100, 110), $start_date, $end_date);
		if ( $udtlf->getRecordCount() > 0 ) {
			foreach($udtlf as $udt_obj) {
				//Debug::Text('Date: '. TTDate::getDate('DATE+TIME', $udt_obj->getDateStamp() ), __FILE__, __LINE__, __METHOD__, 10);

				$type_and_policy_id = $udt_obj->getObjectType().(int)$udt_obj->getPayCode();

				$date_totals[$udt_obj->getDateStamp()][] = array(
												//'date_stamp' => $udt_obj->getColumn('user_date_stamp'),
												'date_stamp' => $udt_obj->getDateStamp(),
												'id' => $udt_obj->getId(),

												//Keep legacy status_id/type_id for now, so we don't have to change as many unit tests.
												'status_id' => $udt_obj->getStatus(),
												'type_id' => $udt_obj->getType(),
												'src_object_id' => $udt_obj->getSourceObject(),

												'object_type_id' => $udt_obj->getObjectType(),
												'pay_code_id' => $udt_obj->getPayCode(),

												'type_and_policy_id' => $type_and_policy_id,
												'branch_id' => (int)$udt_obj->getBranch(),
												'department_id' => $udt_obj->getDepartment(),
												'total_time' => $udt_obj->getTotalTime(),
												'name' => $udt_obj->getName(),

												'start_time_stamp' => $udt_obj->getStartTimeStamp(),
												'end_time_stamp' => $udt_obj->getEndTimeStamp(),

												//'start_time_stamp_display' => TTDate::getDate('DATE+TIME', $udt_obj->getStartTimeStamp() ),
												//'end_time_stamp_display' => TTDate::getDate('DATE+TIME', $udt_obj->getEndTimeStamp() ),

												'quantity' => $udt_obj->getQuantity(),
												'bad_quantity' => $udt_obj->getBadQuantity(),

												'hourly_rate' => $udt_obj->getHourlyRate(),
												'hourly_rate_with_burden' => $udt_obj->getHourlyRateWithBurden(),
												//Override only shows for SYSTEM override columns...
												//Need to check Worked overrides too.
												'tmp_override' => $udt_obj->getOverride()
												);
			}
		}

		return $date_totals;
	}

	function createPayCode( $company_id, $type, $pay_formula_policy_id = 0 ) {
		$pcf = TTnew( 'PayCodeFactory' );
		$pcf->setCompany( $company_id );

		switch ( $type ) {
			case 100:
				$pcf->setName( 'Daily (>8hrs)' );
				//$pcf->setRate( '1.5' );
				break;
			case 110:
				$pcf->setName( 'Daily (>9hrs)' );
				//$pcf->setRate( '2.0' );
				break;
			case 120:
				$pcf->setName( 'Daily (>10hrs)' );
				//$pcf->setRate( '2.5' );
				break;
			case 190:
				$pcf->setName( 'Lunch' );
				//$pcf->setRate( '2.5' );
				break;
			case 200:
				$pcf->setName( 'Weekly (>47hrs)' );
				//$pcf->setRate( '1.5' );
				break;
			case 210:
				$pcf->setName( 'Weekly (>59hrs)' );
				//$pcf->setRate( '2.0' );
				break;
			case 220:
				$pcf->setName( 'Weekly (>71hrs)' );
				//$pcf->setRate( '2.5' );
				break;
			case 230:
				$pcf->setName( 'Weekly (>31hrs)' );
				//$pcf->setRate( '1.5' );
				break;
			case 240:
				$pcf->setName( 'Weekly (>39hrs)' );
				//$pcf->setRate( '2.0' );
				break;
			case 250:
				$pcf->setName( 'Weekly (>47hrs)' );
				//$pcf->setRate( '2.5' );
				break;
			case 300:
				$pcf->setName( 'BiWeekly (>80hrs)' );
				//$pcf->setRate( '1.5' );
				break;
			case 310:
				$pcf->setName( 'BiWeekly (>84hrs)' );
				//$pcf->setRate( '2.0' );
				break;
			case 320:
				$pcf->setName( 'BiWeekly (>86hrs)' );
				//$pcf->setRate( '2.5' );
				break;
			case 500:
				$pcf->setName( 'Holiday' );
				//$pcf->setRate( '1.5' );
				break;
			case 510:
				$pcf->setName( 'Holiday' );
				//$pcf->setRate( '4.0' ); //This should have the highest rate as it always takes precedance.
				break;
			}

		$pcf->setCode( md5( $pcf->getName() ) );
		$pcf->setType( 10 ); //Paid
		//$pcf->setAccrualPolicyID( $accrual_policy_id );
		$pcf->setPayFormulaPolicy( $pay_formula_policy_id );
		$pcf->setPayStubEntryAccountID( CompanyDeductionFactory::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName($company_id, 10, 'Over Time 1') );
		//$pcf->setAccrualRate( 1.0 );

		if ( $pcf->isValid() ) {
			$insert_id = $pcf->Save();
			Debug::Text('Pay Code ID: '. $insert_id, __FILE__, __LINE__, __METHOD__, 10);

			return $insert_id;
		}

		Debug::Text('Failed Creating Pay Code!', __FILE__, __LINE__, __METHOD__, 10);

		return FALSE;
	}

	function createRegularTimePolicy( $company_id, $type, $contributing_shift_policy_id = 0, $pay_code_id = 0 ) {
		$rtpf = TTnew( 'RegularTimePolicyFactory' );
		$rtpf->setId( $rtpf->getNextInsertId() ); //Make sure we can define the differential criteria before calling isValid()
		$rtpf->setCompany( $company_id );

		switch ( $type ) {
			case 10:
				$rtpf->setName( 'Regular Time' );
				$rtpf->setContributingShiftPolicy( $contributing_shift_policy_id );
				$rtpf->setPayCode( $pay_code_id );
				$rtpf->setCalculationOrder( 9999 );
				break;
			case 20:
				$rtpf->setName( 'Regular Time (2)' );
				$rtpf->setContributingShiftPolicy( $contributing_shift_policy_id );
				$rtpf->setPayCode( $pay_code_id );
				$rtpf->setCalculationOrder( 9999 );
				break;

			case 1010:
				$rtpf->setName( 'Regular Time (B1)' );
				$rtpf->setContributingShiftPolicy( $contributing_shift_policy_id );
				$rtpf->setPayCode( $pay_code_id );
				$rtpf->setCalculationOrder( 1000 );

				$rtpf->setBranchSelectionType( 20 );
				$rtpf->setBranch( array( $this->branch_ids[0] ) );
				break;
			case 1020:
				$rtpf->setName( 'Regular Time (B2)' );
				$rtpf->setContributingShiftPolicy( $contributing_shift_policy_id );
				$rtpf->setPayCode( $pay_code_id );
				$rtpf->setCalculationOrder( 1001 );

				$rtpf->setBranchSelectionType( 20 );
				$rtpf->setBranch( array( $this->branch_ids[1] ) );
				break;
		}

		if ( $rtpf->isValid() ) {
			$insert_id = $rtpf->Save( TRUE, TRUE );
			Debug::Text('Regular Time Policy ID: '. $insert_id, __FILE__, __LINE__, __METHOD__, 10);

			return $insert_id;
		}

		Debug::Text('Failed Creating Regular Policy!', __FILE__, __LINE__, __METHOD__, 10);

		return FALSE;
	}

	function updatePayFormulaPolicy( $id, $accrual_policy_account_id = 0, $wage_source_contributing_shift_policy_id = 0, $time_source_contributing_shift_policy_id = 0 ) {
		$pfplf = TTnew( 'PayFormulaPolicyListFactory' );
		$pfplf->getById( $id );
		if ( $pfplf->getRecordCount() == 1 ) {
			$pfpf = $pfplf->getCurrent();

			$pfpf->setWageSourceContributingShiftPolicy( $wage_source_contributing_shift_policy_id );
			$pfpf->setTimeSourceContributingShiftPolicy( $time_source_contributing_shift_policy_id );
			$pfpf->setAccrualPolicyAccount( $accrual_policy_account_id );
			if ( $pfpf->isValid() ) {
				$pfpf->Save();
				Debug::Text('Updating Pay Formula Policy! ID: '. $id, __FILE__, __LINE__, __METHOD__, 10);
				return TRUE;
			}
		}

		Debug::Text('Failed Updating Pay Formula Policy!', __FILE__, __LINE__, __METHOD__, 10);
		return FALSE;
	}
	function createPayFormulaPolicy( $company_id, $type, $accrual_policy_account_id = 0, $wage_source_contributing_shift_policy_id = 0, $time_source_contributing_shift_policy_id = 0 ) {
		$pfpf = TTnew( 'PayFormulaPolicyFactory' );
		$pfpf->setCompany( $company_id );

		switch ( $type ) {
			case 10:
				$pfpf->setName( 'None ($0)' );
				$pfpf->setPayType( 10 ); //Pay Multiplied By Factor
				$pfpf->setRate( 0 );
				$pfpf->setAccrualPolicyAccount( $accrual_policy_account_id );
				$pfpf->setAccrualRate( 0 );
				break;
			case 100:
				$pfpf->setName( 'Regular' );
				$pfpf->setPayType( 10 ); //Pay Multiplied By Factor
				$pfpf->setRate( 1.0 );
				$pfpf->setAccrualPolicyAccount( $accrual_policy_account_id );
				$pfpf->setAccrualRate( 1.0 );
				break;
			case 200:
				$pfpf->setName( 'OverTime (1.5x)' );
				$pfpf->setPayType( 10 ); //Pay Multiplied By Factor
				$pfpf->setRate( 1.5 );
				$pfpf->setAccrualPolicyAccount( $accrual_policy_account_id );
				$pfpf->setAccrualRate( 1.0 );
				break;
			case 210:
				$pfpf->setName( 'OverTime (2.0x)' );
				$pfpf->setPayType( 10 ); //Pay Multiplied By Factor
				$pfpf->setRate( 2.0 );
				$pfpf->setAccrualPolicyAccount( $accrual_policy_account_id );
				$pfpf->setAccrualRate( 1.0 );
				break;
			case 220:
				$pfpf->setName( 'OverTime (2.5x)' );
				$pfpf->setPayType( 10 ); //Pay Multiplied By Factor
				$pfpf->setRate( 2.5 );
				$pfpf->setAccrualPolicyAccount( $accrual_policy_account_id );
				$pfpf->setAccrualRate( 1.0 );
				break;
			case 510:
				$pfpf->setName( 'OverTime (4.0x)' );
				$pfpf->setPayType( 10 ); //Pay Multiplied By Factor
				$pfpf->setRate( 4.0 );
				$pfpf->setAccrualPolicyAccount( $accrual_policy_account_id );
				$pfpf->setAccrualRate( 1.0 );
				break;
			case 910:
				$pfpf->setName( 'Bank' );
				$pfpf->setPayType( 10 ); //Pay Multiplied By Factor
				$pfpf->setRate( 1.0 );
				$pfpf->setAccrualPolicyAccount( $accrual_policy_account_id );
				$pfpf->setAccrualRate( -1.0 );
				break;
			case 1101:
				$pfpf->setName( 'Regular (Alt Wage #1)' );
				$pfpf->setPayType( 10 ); //Pay Multiplied By Factor
				$pfpf->setRate( 1.0 );
				$pfpf->setWageGroup( $this->user_wage_groups[0] );
				$pfpf->setAccrualPolicyAccount( $accrual_policy_account_id );
				$pfpf->setAccrualRate( 1.0 );
				break;
			case 1102:
				$pfpf->setName( 'Regular (Alt Wage #2)' );
				$pfpf->setPayType( 10 ); //Pay Multiplied By Factor
				$pfpf->setRate( 1.0 );
				$pfpf->setWageGroup( $this->user_wage_groups[1] );
				$pfpf->setAccrualPolicyAccount( $accrual_policy_account_id );
				$pfpf->setAccrualRate( 1.0 );
				break;

			case 1200: //Overtime averaging.
				$pfpf->setName( 'OverTime Avg (1.5x)' );
				$pfpf->setPayType( 10 ); //Pay Multiplied By Factor
				$pfpf->setWageSourceType( 30 ); //Average of contributing pay codes.
				$pfpf->setWageSourceContributingShiftPolicy( $wage_source_contributing_shift_policy_id );
				$pfpf->setTimeSourceContributingShiftPolicy( $time_source_contributing_shift_policy_id );
				$pfpf->setRate( 1.5 );
				$pfpf->setAccrualPolicyAccount( $accrual_policy_account_id );
				$pfpf->setAccrualRate( 1.0 );
				break;
			case 1210: //Overtime averaging.
				$pfpf->setName( 'OverTime Avg (2.0x)' );
				$pfpf->setPayType( 10 ); //Pay Multiplied By Factor
				$pfpf->setWageSourceType( 30 ); //Average of contributing pay codes.
				$pfpf->setWageSourceContributingShiftPolicy( $wage_source_contributing_shift_policy_id );
				$pfpf->setTimeSourceContributingShiftPolicy( $time_source_contributing_shift_policy_id );
				$pfpf->setRate( 2.0 );
				$pfpf->setAccrualPolicyAccount( $accrual_policy_account_id );
				$pfpf->setAccrualRate( 1.0 );
				break;
			case 1220: //Overtime averaging.
				$pfpf->setName( 'OverTime Avg (2.5x)' );
				$pfpf->setPayType( 10 ); //Pay Multiplied By Factor
				$pfpf->setWageSourceType( 30 ); //Average of contributing pay codes.
				$pfpf->setWageSourceContributingShiftPolicy( $wage_source_contributing_shift_policy_id );
				$pfpf->setTimeSourceContributingShiftPolicy( $time_source_contributing_shift_policy_id );
				$pfpf->setRate( 2.5 );
				$pfpf->setAccrualPolicyAccount( $accrual_policy_account_id );
				$pfpf->setAccrualRate( 1.0 );
				break;

		}

		if ( $pfpf->isValid() ) {
			$insert_id = $pfpf->Save();
			Debug::Text('Pay Formula Policy ID: '. $insert_id, __FILE__, __LINE__, __METHOD__, 10);

			return $insert_id;
		}

		Debug::Text('Failed Creating Pay Formula Policy!', __FILE__, __LINE__, __METHOD__, 10);

		return FALSE;
	}

	function createOverTimePolicy( $company_id, $type, $contributing_shift_policy_id = 0, $pay_code_id = 0, $trigger_time_adjust_contributing_shift_policy_id = 0 ) {
		$otpf = new OverTimePolicyFactory();
		$otpf->setId( $otpf->getNextInsertId() ); //Make sure we can define the differential criteria before calling isValid()
		$otpf->setCompany( $company_id );

		switch ( $type ) {
			//
			//Changing the OT rates will make a big difference is how these tests are calculated.
			//
			case 90:
				$otpf->setName( 'Daily (>7hrs)' );
				$otpf->setType( 10 );
				$otpf->setTriggerTime( (3600 * 7) );
				break;
			case 100:
				$otpf->setName( 'Daily (>8hrs)' );
				$otpf->setType( 10 );
				$otpf->setTriggerTime( (3600 * 8) );
				break;
			case 110:
				$otpf->setName( 'Daily (>9hrs)' );
				$otpf->setType( 10 );
				$otpf->setTriggerTime( (3600 * 9) );
				break;
			case 120:
				$otpf->setName( 'Daily (>10hrs)' );
				$otpf->setType( 10 );
				$otpf->setTriggerTime( (3600 * 10) );
				break;
			case 200:
				$otpf->setName( 'Weekly (>47hrs)' );
				$otpf->setType( 20 );
				$otpf->setTriggerTime( (3600 * 47) );
				break;
			case 210:
				$otpf->setName( 'Weekly (>59hrs)' );
				$otpf->setType( 20 );
				$otpf->setTriggerTime( (3600 * 59) );
				break;
			case 220:
				$otpf->setName( 'Weekly (>71hrs)' );
				$otpf->setType( 20 );
				$otpf->setTriggerTime( (3600 * 71) );
				break;
			case 230:
				$otpf->setName( 'Weekly (>31hrs)' );
				$otpf->setType( 20 );
				$otpf->setTriggerTime( (3600 * 31) );
				break;
			case 240:
				$otpf->setName( 'Weekly (>39hrs)' );
				$otpf->setType( 20 );
				$otpf->setTriggerTime( (3600 * 39) );
				break;
			case 242:
				$otpf->setName( 'Weekly (>40hrs) [B]' );
				$otpf->setType( 20 );
				$otpf->setTriggerTime( (3600 * 40) );
				break;
			case 250:
				$otpf->setName( 'Weekly (>47hrs) [B]' );
				$otpf->setType( 20 );
				$otpf->setTriggerTime( (3600 * 47) );
				break;
			case 300:
				$otpf->setName( 'BiWeekly (>80hrs)' );
				$otpf->setType( 30 );
				$otpf->setTriggerTime( (3600 * 80) );
				break;
			case 310:
				$otpf->setName( 'BiWeekly (>84hrs)' );
				$otpf->setType( 30 );
				$otpf->setTriggerTime( (3600 * 84) );
				break;
			case 320:
				$otpf->setName( 'BiWeekly (>86hrs)' );
				$otpf->setType( 30 );
				$otpf->setTriggerTime( (3600 * 86) );
				break;
			case 500:
				$otpf->setName( 'Holiday' );
				$otpf->setType( 180 );
				$otpf->setTriggerTime( 0 );
				break;
			case 510:
				$otpf->setName( 'Holiday' );
				$otpf->setType( 180 );
				$otpf->setTriggerTime( 0 );
				//$otpf->setPayCode( $pay_code_id ); //Rate should be 4.0... This should have the highest rate as it always takes precedance.
				break;


			case 1000: //Differential
				$otpf->setName( 'Daily (>8hrs) [B1]' );
				$otpf->setType( 10 );
				$otpf->setTriggerTime( (3600 * 8) );

				$otpf->setBranchSelectionType( 20 );
				$otpf->setBranch( array( $this->branch_ids[0] ) );
				break;
			case 1001: //Differential
				$otpf->setName( 'Daily (>8hrs) [B2]' );
				$otpf->setType( 10 );
				$otpf->setTriggerTime( (3600 * 8) );

				$otpf->setBranchSelectionType( 20 );
				$otpf->setBranch( array( $this->branch_ids[1] ) );
				break;

			case 1230: //Differential
				$otpf->setName( 'Weekly (>31hrs) [B1]' );
				$otpf->setType( 20 );
				$otpf->setTriggerTime( (3600 * 31) );

				$otpf->setBranchSelectionType( 20 );
				$otpf->setBranch( array( $this->branch_ids[1] ) );
				break;
			case 1231: //Differential
				$otpf->setName( 'Weekly (>31hrs) [B2]' );
				$otpf->setType( 20 );
				$otpf->setTriggerTime( (3600 * 31) );

				$otpf->setBranchSelectionType( 20 );
				$otpf->setBranch( array( $this->branch_ids[0] ) );
				break;

			case 1240: //Differential
				$otpf->setName( 'Weekly (>39hrs) [B1]' );
				$otpf->setType( 20 );
				$otpf->setTriggerTime( (3600 * 39) );

				$otpf->setBranchSelectionType( 20 );
				$otpf->setBranch( array( $this->branch_ids[1] ) );
				break;

			case 1300: //Differential
				$otpf->setName( 'BiWeekly (>80hrs) [B2]' );
				$otpf->setType( 30 );
				$otpf->setTriggerTime( (3600 * 80) );

				$otpf->setBranchSelectionType( 20 );
				$otpf->setBranch( array( $this->branch_ids[0] ) );
				break;
			case 1310: //Differential
				$otpf->setName( 'BiWeekly (>84hrs) [B2]' );
				$otpf->setType( 30 );
				$otpf->setTriggerTime( (3600 * 84) );

				$otpf->setBranchSelectionType( 20 );
				$otpf->setBranch( array( $this->branch_ids[0] ) );
				break;
			case 1320: //Differential
				$otpf->setName( 'BiWeekly (>86hrs) [B2]' );
				$otpf->setType( 30 );
				$otpf->setTriggerTime( (3600 * 86) );

				$otpf->setBranchSelectionType( 20 );
				$otpf->setBranch( array( $this->branch_ids[0] ) );
				break;

			case 1900: //Differential - Used to test mid-shift differential at a higher rate (ie: 2.0x) that just applies to an hour or so. The lower rate should then apply thereafter still.
				$otpf->setName( 'OT Differential [B1]' );
				$otpf->setType( 10 );
				$otpf->setTriggerTime( (3600 * 0) );

				$otpf->setBranchSelectionType( 20 );
				$otpf->setBranch( array( $this->branch_ids[0] ) );
				break;
		}

		$otpf->setPayCode( $pay_code_id );
		$otpf->setContributingShiftPolicy( $contributing_shift_policy_id );
		$otpf->setTriggerTimeAdjustContributingShiftPolicy( $trigger_time_adjust_contributing_shift_policy_id );

		if ( $otpf->isValid() ) {
			$insert_id = $otpf->Save( TRUE, TRUE );
			Debug::Text('Overtime Policy ID: '. $insert_id, __FILE__, __LINE__, __METHOD__, 10);

			return $insert_id;
		}

		Debug::Text('Failed Creating Overtime Policy!', __FILE__, __LINE__, __METHOD__, 10);

		return FALSE;
	}

	function createHolidayPolicy( $company_id, $type ) {
		$hpf = new HolidayPolicyFactory();
		$hpf->setCompany( $company_id );

		switch ( $type ) {
			case 10:
				$hpf->setName( 'Default' );
				$hpf->setType( 10 );

				$hpf->setDefaultScheduleStatus( 10 );
				$hpf->setMinimumEmployedDays( 0 );
				$hpf->setMinimumWorkedPeriodDays( 0 );
				$hpf->setMinimumWorkedDays( 0 );
				$hpf->setAverageTimeDays( 10 );
				$hpf->setAverageTimeWorkedDays( TRUE );
				$hpf->setIncludeOverTime( TRUE );
				$hpf->setIncludePaidAbsenceTime( TRUE );
				$hpf->setForceOverTimePolicy( TRUE );

				$hpf->setMinimumTime( 0 );
				$hpf->setMaximumTime( 0 );

				$hpf->setAbsencePolicyID( $this->absence_policy_id );
				//$hpf->setRoundIntervalPolicyID( $data['round_interval_policy_id'] );

				break;
		}

		if ( $hpf->isValid() ) {
			$insert_id = $hpf->Save();
			Debug::Text('Holiday Policy ID: '. $insert_id, __FILE__, __LINE__, __METHOD__, 10);

			return $insert_id;
		}

		Debug::Text('Failed Creating Holiday Policy!', __FILE__, __LINE__, __METHOD__, 10);

		return FALSE;
	}

	function createHoliday( $company_id, $type, $date, $holiday_policy_id ) {
		$hf = new HolidayFactory();

		switch ( $type ) {
			case 10:
				$hf->setHolidayPolicyId( $holiday_policy_id );
				$hf->setDateStamp( $date );
				$hf->setName( 'Test1' );

				break;
		}

		if ( $hf->isValid() ) {
			$insert_id = $hf->Save();
			Debug::Text('Holiday ID: '. $insert_id, __FILE__, __LINE__, __METHOD__, 10);

			return $insert_id;
		}

		Debug::Text('Failed Creating Holiday!', __FILE__, __LINE__, __METHOD__, 10);

		return FALSE;
	}

	/*
	 Tests:
		No Overtime
		Daily OverTime (3 levels)
		Weekly OverTime (3 Levels)
		BiWeekly OverTime (3 Levels)
		Combination Daily+Weekly (3 Levels)
		Combination Daily+Weekly+Holiday (3 Levels)
	*/

	/**
	 * @group OvertimePolicy_testNoOverTimePolicyA
	 */
	function testNoOverTimePolicyA() {
		global $dd;

		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 200 ); //OT1.5
		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 210 ); //OT2.0
		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 220 ); //OT2.5

		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 100, $policy_ids['pay_formula_policy'][0] );
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 110, $policy_ids['pay_formula_policy'][1] );
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 120, $policy_ids['pay_formula_policy'][2] );

		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 100, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][0] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 110, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][1] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 120, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][2] );

		//Create Policy Group
		$dd->createPolicyGroup( 	$this->company_id,
									NULL, //Meal
									NULL, //Exception
									NULL, //Holiday
									$policy_ids['overtime'], //OT
									NULL, //Premium
									NULL, //Round
									array($this->user_id), //Users
									NULL, //Break
									NULL, //Accrual
									NULL, //Expense
									NULL, //Absence
									array($this->policy_ids['regular'][12]) //Regular
									);

		$date_epoch = TTDate::getBeginWeekEpoch( time() );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 3:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);

		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (7 * 3600) );
		$this->assertEquals( $udt_arr[$date_epoch][0]['hourly_rate'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['hourly_rate_with_burden'], 0 );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (7 * 3600) );
		$this->assertEquals( $udt_arr[$date_epoch][1]['hourly_rate'], 21.50 );
		$this->assertEquals( $udt_arr[$date_epoch][1]['hourly_rate_with_burden'], 24.4025 );

		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 2 );

		return TRUE;
	}

	/**
	 * @group OvertimePolicy_testDailyOverTimePolicyA
	 */
	function testDailyOverTimePolicyA() {
		global $dd;

		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 200 ); //OT1.5
		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 210 ); //OT2.0
		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 220 ); //OT2.5

		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 100, $policy_ids['pay_formula_policy'][0] );
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 110, $policy_ids['pay_formula_policy'][1] );
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 120, $policy_ids['pay_formula_policy'][2] );

		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 100, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][0] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 110, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][1] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 120, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][2] );

		//Create Policy Group
		$dd->createPolicyGroup( 	$this->company_id,
									NULL, //Meal
									NULL, //Exception
									NULL, //Holiday
									$policy_ids['overtime'], //OT
									NULL, //Premium
									NULL, //Round
									array($this->user_id), //Users
									NULL, //Break
									NULL, //Accrual
									NULL, //Expense
									NULL, //Absence
									array($this->policy_ids['regular'][12]) //Regular
									);


		$date_epoch = TTDate::getBeginWeekEpoch( time() );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);

		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		$this->assertEquals( $udt_arr[$date_epoch][0]['start_time_stamp'], strtotime($date_stamp.' 8:00AM') );
		$this->assertEquals( $udt_arr[$date_epoch][0]['end_time_stamp'], strtotime($date_stamp.' 8:00PM') );
		$this->assertEquals( $udt_arr[$date_epoch][0]['hourly_rate'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['hourly_rate_with_burden'], 0 );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (8 * 3600) );
		$this->assertEquals( $udt_arr[$date_epoch][1]['start_time_stamp'], strtotime($date_stamp.' 8:00AM') );
		$this->assertEquals( $udt_arr[$date_epoch][1]['end_time_stamp'], strtotime($date_stamp.' 4:00PM') );
		$this->assertEquals( $udt_arr[$date_epoch][1]['hourly_rate'], (1 * 21.50) );
		$this->assertEquals( $udt_arr[$date_epoch][1]['hourly_rate_with_burden'], (1 * 21.50 * 1.135) ); //13.5%
		//Overtime 1
		$this->assertEquals( $udt_arr[$date_epoch][4]['object_type_id'], 30 ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][4]['pay_code_id'], $policy_ids['pay_code'][0] );
		$this->assertEquals( $udt_arr[$date_epoch][4]['total_time'], (1 * 3600) );
		$this->assertEquals( $udt_arr[$date_epoch][4]['start_time_stamp'], strtotime($date_stamp.' 4:00PM') );
		$this->assertEquals( $udt_arr[$date_epoch][4]['end_time_stamp'], strtotime($date_stamp.' 5:00PM') );
		$this->assertEquals( $udt_arr[$date_epoch][4]['hourly_rate'], (1.5 * 21.50) );
		$this->assertEquals( Misc::MoneyFormat( $udt_arr[$date_epoch][4]['hourly_rate_with_burden'], FALSE ), Misc::MoneyFormat( (1.5 * 21.50 * 1.135), FALSE) ); //13.5%
		//Overtime 2
		$this->assertEquals( $udt_arr[$date_epoch][3]['object_type_id'], 30 ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][3]['pay_code_id'], $policy_ids['pay_code'][1] );
		$this->assertEquals( $udt_arr[$date_epoch][3]['total_time'], (1 * 3600) );
		$this->assertEquals( $udt_arr[$date_epoch][3]['start_time_stamp'], strtotime($date_stamp.' 5:00PM') );
		$this->assertEquals( $udt_arr[$date_epoch][3]['end_time_stamp'], strtotime($date_stamp.' 6:00PM') );
		$this->assertEquals( $udt_arr[$date_epoch][3]['hourly_rate'], (2.0 * 21.50) );
		$this->assertEquals( $udt_arr[$date_epoch][3]['hourly_rate_with_burden'], (2.0 * 21.50 * 1.135) ); //13.5%
		//Overtime 3
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 30 ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $policy_ids['pay_code'][2] );
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (2 * 3600) );
		$this->assertEquals( $udt_arr[$date_epoch][2]['start_time_stamp'], strtotime($date_stamp.' 6:00PM') );
		$this->assertEquals( $udt_arr[$date_epoch][2]['end_time_stamp'], strtotime($date_stamp.' 8:00PM') );
		$this->assertEquals( $udt_arr[$date_epoch][2]['hourly_rate'], (2.5 * 21.50) );
		$this->assertEquals( Misc::MoneyFormat( $udt_arr[$date_epoch][2]['hourly_rate_with_burden'] ), Misc::MoneyFormat( (2.5 * 21.50 * 1.135) ) ); //13.5%

		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 5 );
		return TRUE;
	}

	/**
	 * @group OvertimePolicy_testDailyOverTimePolicyB
	 */
	function testDailyOverTimePolicyB() {
		global $dd;

		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 200 ); //OT1.5
		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 210 ); //OT2.0
		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 220 ); //OT2.5

		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 100, $policy_ids['pay_formula_policy'][0] );
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 110, $policy_ids['pay_formula_policy'][1] );
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 120, $policy_ids['pay_formula_policy'][2] );

		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 100, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][0] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 110, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][1] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 120, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][2] );

		//Create Policy Group
		$dd->createPolicyGroup( 	$this->company_id,
									NULL, //Meal
									NULL, //Exception
									NULL, //Holiday
									$policy_ids['overtime'], //OT
									NULL, //Premium
									NULL, //Round
									array($this->user_id), //Users
									NULL, //Break
									NULL, //Accrual
									NULL, //Expense
									NULL, //Absence
									array($this->policy_ids['regular'][12]) //Regular
									);


		$date_epoch = TTDate::getBeginWeekEpoch( time() );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 12:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);
		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 12:30PM'),
								strtotime($date_stamp.' 5:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);

		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (8.5 * 3600) );
		$this->assertEquals( $udt_arr[$date_epoch][0]['start_time_stamp'], strtotime($date_stamp.' 8:00AM') );
		$this->assertEquals( $udt_arr[$date_epoch][0]['end_time_stamp'], strtotime($date_stamp.' 5:00PM') );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (4 * 3600) );
		$this->assertEquals( $udt_arr[$date_epoch][1]['start_time_stamp'], strtotime($date_stamp.' 8:00AM') );
		$this->assertEquals( $udt_arr[$date_epoch][1]['end_time_stamp'], strtotime($date_stamp.' 12:00PM') );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (4 * 3600) );
		$this->assertEquals( $udt_arr[$date_epoch][2]['start_time_stamp'], strtotime($date_stamp.' 12:30PM') );
		$this->assertEquals( $udt_arr[$date_epoch][2]['end_time_stamp'], strtotime($date_stamp.' 4:30PM') );
		//Overtime 1
		$this->assertEquals( $udt_arr[$date_epoch][3]['object_type_id'], 30 ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][3]['pay_code_id'], $policy_ids['pay_code'][0] );
		$this->assertEquals( $udt_arr[$date_epoch][3]['total_time'], (0.5 * 3600) );
		$this->assertEquals( $udt_arr[$date_epoch][3]['start_time_stamp'], strtotime($date_stamp.' 4:30PM') );
		$this->assertEquals( $udt_arr[$date_epoch][3]['end_time_stamp'], strtotime($date_stamp.' 5:00PM') );

		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 4 );
		return TRUE;
	}

	/**
	 * @group OvertimePolicy_testWeeklyOverTimePolicyA
	 */
	function testWeeklyOverTimePolicyA() {
		global $dd;

		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 200 ); //OT1.5
		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 210 ); //OT2.0
		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 220 ); //OT2.5

		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 200, $policy_ids['pay_formula_policy'][0] );
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 210, $policy_ids['pay_formula_policy'][1] );
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 220, $policy_ids['pay_formula_policy'][2] );

		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 200, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][0] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 210, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][1] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 220, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][2] );
		//print_r($policy_ids['overtime']);

		//Create Policy Group
		$dd->createPolicyGroup( 	$this->company_id,
									NULL, //Meal
									NULL, //Exception
									NULL, //Holiday
									$policy_ids['overtime'], //OT
									NULL, //Premium
									NULL, //Round
									array($this->user_id), //Users
									NULL, //Break
									NULL, //Accrual
									NULL, //Expense
									NULL, //Absence
									array($this->policy_ids['regular'][12]) //Regular
									);

		$date_epoch = TTDate::getBeginWeekEpoch( time() );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		//
		//Day of Week: 1
		//
		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (12 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 2 );
		//
		//Day of Week: 2
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (1 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (12 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 2 );

		//
		//Day of Week: 3
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (2 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (12 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 2 );

		//
		//Day of Week: 4
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (3 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (11 * 3600) );
		//Overtime 1
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 30 ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $policy_ids['pay_code'][0] );
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (1 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 3 );

		//
		//Day of Week: 5
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (4 * 86400 + 3601)) ;
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//Overtime 1
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 30 ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $policy_ids['pay_code'][0] );
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (11 * 3600) );
		//Overtime 2
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 30 ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $policy_ids['pay_code'][1] );
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (1 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 3 );

		//
		//Day of Week: 6
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (5 * 86400 + 3601) ) ;
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//Overtime 1
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 30 ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $policy_ids['pay_code'][1] );
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (11 * 3600) );
		//Overtime 2
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 30 ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $policy_ids['pay_code'][2] );
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (1 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 3 );

		return TRUE;
	}

	/**
	 * @group OvertimePolicy_testDuplicateWeeklyOverTimePolicyA
	 */
	function testDuplicateWeeklyOverTimePolicyA() {
		global $dd;

		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 200 ); //OT1.5
		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 210 ); //OT2.0
		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 220 ); //OT2.5

		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 200, $policy_ids['pay_formula_policy'][0] );
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 210, $policy_ids['pay_formula_policy'][1] );
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 220, $policy_ids['pay_formula_policy'][2] );

		//Duplicate Weekly OT policies, they should BOTH be attempted to calculate due to differential criteria.
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 242, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][0] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 250, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][0] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 200, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][0] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 220, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][1] );
		//print_r($policy_ids['overtime']);

		//Create Policy Group
		$dd->createPolicyGroup( 	$this->company_id,
									NULL, //Meal
									NULL, //Exception
									NULL, //Holiday
									$policy_ids['overtime'], //OT
									NULL, //Premium
									NULL, //Round
									array($this->user_id), //Users
									NULL, //Break
									NULL, //Accrual
									NULL, //Expense
									NULL, //Absence
									array($this->policy_ids['regular'][12]) //Regular
									);

		$date_epoch = TTDate::getBeginWeekEpoch( time() );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		//
		//Day of Week: 1
		//
		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (12 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 2 );
		//
		//Day of Week: 2
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (1 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (12 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 2 );

		//
		//Day of Week: 3
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (2 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (12 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 2 );

		//
		//Day of Week: 4
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (3 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (4 * 3600) );
		//Overtime 2
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 30 ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $policy_ids['pay_code'][0] );
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (1 * 3600) );
		//Overtime 1
		$this->assertEquals( $udt_arr[$date_epoch][3]['object_type_id'], 30 ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][3]['pay_code_id'], $policy_ids['pay_code'][0] );
		$this->assertEquals( $udt_arr[$date_epoch][3]['total_time'], (7 * 3600) );

		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 4 );

		//
		//Day of Week: 5
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (4 * 86400 + 3601)) ;
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//Overtime 1
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 30 ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $policy_ids['pay_code'][0] );
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (12 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 2 );

		//
		//Day of Week: 6
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (5 * 86400 + 3601) ) ;
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//Overtime 2
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 30 ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $policy_ids['pay_code'][1] );
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (1 * 3600) );
		//Overtime 1
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 30 ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $policy_ids['pay_code'][0] );
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (11 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 3 );

		return TRUE;
	}


	/**
	 * @group OvertimePolicy_testDailyOverTimeWithTimeBank
	 */
	function testDailyOverTimeWithTimeBank() {
		//Test handling daily OT with absences and the Start/End timestamps for each UDT record.
		global $dd;

		//$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 910 ); //OT1.5 BANK
		//$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 210 ); //OT2.0
		//$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 220 ); //OT2.5

		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 100, $this->policy_ids['pay_formula_policy'][910] );
		//$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 110, $policy_ids['pay_formula_policy'][1] );
		//$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 120, $policy_ids['pay_formula_policy'][2] );

		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 100, $this->policy_ids['contributing_shift_policy'][14], $policy_ids['pay_code'][0] );
		//$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 110, $this->policy_ids['contributing_shift_policy'][14], $policy_ids['pay_code'][1] );
		//$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 120, $this->policy_ids['contributing_shift_policy'][14], $policy_ids['pay_code'][2] );

		//Create Policy Group
		$dd->createPolicyGroup( 	$this->company_id,
									NULL, //Meal
									NULL, //Exception
									NULL, //Holiday
									$policy_ids['overtime'], //OT
									NULL, //Premium
									NULL, //Round
									array($this->user_id), //Users
									NULL, //Break
									NULL, //Accrual
									NULL, //Expense
									array($this->absence_policy_id), //Absence
									array($this->policy_ids['regular'][12]) //Regular
									);


		$date_epoch = TTDate::getBeginWeekEpoch( time(), 1 ); //Start on Monday to avoid DST issues on Sunday morning.
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$this->assertEquals( $this->getCurrentAccrualBalance( $this->user_id, $this->policy_ids['accrual_policy_account'][10] ), (0 * 3600) );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 5:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 5:00PM'),
								strtotime($date_stamp.' 6:30PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$this->assertEquals( $this->getCurrentAccrualBalance( $this->user_id, $this->policy_ids['accrual_policy_account'][10] ), (-2.5 * 3600) );

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);

		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (10.5 * 3600) );
		$this->assertEquals( $udt_arr[$date_epoch][0]['start_time_stamp'], strtotime($date_stamp.' 8:00AM') );
		$this->assertEquals( $udt_arr[$date_epoch][0]['end_time_stamp'], strtotime($date_stamp.' 6:30PM') );
		$this->assertEquals( $udt_arr[$date_epoch][0]['hourly_rate'], 0 );

		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (8 * 3600) );
		$this->assertEquals( $udt_arr[$date_epoch][1]['start_time_stamp'], strtotime($date_stamp.' 8:00AM') );
		$this->assertEquals( $udt_arr[$date_epoch][1]['end_time_stamp'], strtotime($date_stamp.' 4:00PM') );
		$this->assertEquals( $udt_arr[$date_epoch][1]['hourly_rate'], (1 * 21.50) );

		//Overtime 1(a)
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 30 ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $policy_ids['pay_code'][0] );
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (1 * 3600) );
		$this->assertEquals( $udt_arr[$date_epoch][2]['start_time_stamp'], strtotime($date_stamp.' 4:00PM') );
		$this->assertEquals( $udt_arr[$date_epoch][2]['end_time_stamp'], strtotime($date_stamp.' 5:00PM') );
		$this->assertEquals( $udt_arr[$date_epoch][2]['hourly_rate'], (1.0 * 21.50) );
		//Overtime 1(b)
		$this->assertEquals( $udt_arr[$date_epoch][3]['object_type_id'], 30 ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][3]['pay_code_id'], $policy_ids['pay_code'][0] );
		$this->assertEquals( $udt_arr[$date_epoch][3]['total_time'], (1.5 * 3600) );
		$this->assertEquals( $udt_arr[$date_epoch][3]['start_time_stamp'], strtotime($date_stamp.' 5:00PM') );
		$this->assertEquals( $udt_arr[$date_epoch][3]['end_time_stamp'], strtotime($date_stamp.' 6:30PM') );
		$this->assertEquals( $udt_arr[$date_epoch][3]['hourly_rate'], (1.0 * 21.50) );

		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 4 );

		$this->assertEquals( $this->getCurrentAccrualBalance( $this->user_id, $this->policy_ids['accrual_policy_account'][10] ), (-2.5 * 3600) );

		return TRUE;
	}


	/**
	 * @group OvertimePolicy_testDailyOverTimeWithAbsencePolicyA
	 */
	function testDailyOverTimeWithAbsencePolicyA() {
		//Test handling daily OT with absences and the Start/End timestamps for each UDT record.
		global $dd;

		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 200 ); //OT1.5
		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 210 ); //OT2.0
		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 220 ); //OT2.5

		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 100, $policy_ids['pay_formula_policy'][0] );
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 110, $policy_ids['pay_formula_policy'][1] );
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 120, $policy_ids['pay_formula_policy'][2] );

		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 100, $this->policy_ids['contributing_shift_policy'][14], $policy_ids['pay_code'][0] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 110, $this->policy_ids['contributing_shift_policy'][14], $policy_ids['pay_code'][1] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 120, $this->policy_ids['contributing_shift_policy'][14], $policy_ids['pay_code'][2] );

		//Create Policy Group
		$dd->createPolicyGroup( 	$this->company_id,
									NULL, //Meal
									NULL, //Exception
									NULL, //Holiday
									$policy_ids['overtime'], //OT
									NULL, //Premium
									NULL, //Round
									array($this->user_id), //Users
									NULL, //Break
									NULL, //Accrual
									NULL, //Expense
									array($this->absence_policy_id), //Absence
									array($this->policy_ids['regular'][12]) //Regular
									);


		$date_epoch = TTDate::getBeginWeekEpoch( time(), 1 ); //Start on Monday to avoid DST issues on Sunday morning.
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$this->assertEquals( $this->getCurrentAccrualBalance( $this->user_id, $this->policy_ids['accrual_policy_account'][10] ), (0 * 3600) );

		$absence_id = $dd->createAbsence( $this->user_id, $date_epoch, (4 * 3600), $this->absence_policy_id );
		$this->assertEquals( $this->getCurrentAccrualBalance( $this->user_id, $this->policy_ids['accrual_policy_account'][10] ), (-4 * 3600) );

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);

		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (16 * 3600) );
		$this->assertEquals( $udt_arr[$date_epoch][0]['start_time_stamp'], strtotime($date_stamp.' 4:00AM') );
		$this->assertEquals( $udt_arr[$date_epoch][0]['end_time_stamp'], strtotime($date_stamp.' 8:00PM') );
		$this->assertEquals( $udt_arr[$date_epoch][0]['hourly_rate'], 0 );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (4 * 3600) );
		$this->assertEquals( $udt_arr[$date_epoch][1]['start_time_stamp'], strtotime($date_stamp.' 8:00AM') );
		$this->assertEquals( $udt_arr[$date_epoch][1]['end_time_stamp'], strtotime($date_stamp.' 12:00PM') );
		$this->assertEquals( $udt_arr[$date_epoch][1]['hourly_rate'], (1 * 21.50) );
		//Absence Time
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 25 ); //Absence Time
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $this->policy_ids['pay_code'][900] ); //Absence Time
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (4 * 3600) );
		$this->assertEquals( $udt_arr[$date_epoch][2]['start_time_stamp'], strtotime($date_stamp.' 4:00AM') );
		$this->assertEquals( $udt_arr[$date_epoch][2]['end_time_stamp'], strtotime($date_stamp.' 8:00AM') );
		$this->assertEquals( $udt_arr[$date_epoch][2]['hourly_rate'], (1 * 21.50) );

		//Overtime 1
		$this->assertEquals( $udt_arr[$date_epoch][5]['object_type_id'], 30 ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][5]['pay_code_id'], $policy_ids['pay_code'][0] );
		$this->assertEquals( $udt_arr[$date_epoch][5]['total_time'], (1 * 3600) );
		$this->assertEquals( $udt_arr[$date_epoch][5]['start_time_stamp'], strtotime($date_stamp.' 12:00PM') );
		$this->assertEquals( $udt_arr[$date_epoch][5]['end_time_stamp'], strtotime($date_stamp.' 1:00PM') );
		$this->assertEquals( $udt_arr[$date_epoch][5]['hourly_rate'], (1.5 * 21.50) );
		//Overtime 2
		$this->assertEquals( $udt_arr[$date_epoch][4]['object_type_id'], 30 ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][4]['pay_code_id'], $policy_ids['pay_code'][1] );
		$this->assertEquals( $udt_arr[$date_epoch][4]['total_time'], (1 * 3600) );
		$this->assertEquals( $udt_arr[$date_epoch][4]['start_time_stamp'], strtotime($date_stamp.' 1:00PM') );
		$this->assertEquals( $udt_arr[$date_epoch][4]['end_time_stamp'], strtotime($date_stamp.' 2:00PM') );
		$this->assertEquals( $udt_arr[$date_epoch][4]['hourly_rate'], (2.0 * 21.50) );
		//Overtime 3
		$this->assertEquals( $udt_arr[$date_epoch][3]['object_type_id'], 30 ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][3]['pay_code_id'], $policy_ids['pay_code'][2] );
		$this->assertEquals( $udt_arr[$date_epoch][3]['total_time'], (6 * 3600) );
		$this->assertEquals( $udt_arr[$date_epoch][3]['start_time_stamp'], strtotime($date_stamp.' 2:00PM') );
		$this->assertEquals( $udt_arr[$date_epoch][3]['end_time_stamp'], strtotime($date_stamp.' 8:00PM') );
		$this->assertEquals( $udt_arr[$date_epoch][3]['hourly_rate'], (2.5 * 21.50) );

		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 6 );

		$this->assertEquals( $this->getCurrentAccrualBalance( $this->user_id, $this->policy_ids['accrual_policy_account'][10] ), (-4 * 3600) );

		//Delete absence in the middle of the week and confirm balance is still correct.
		$dd->deleteAbsence( $absence_id );
		$this->assertEquals( $this->getCurrentAccrualBalance( $this->user_id, $this->policy_ids['accrual_policy_account'][10] ), (0 * 3600) );

		return TRUE;
	}

	/**
	 * @group OvertimePolicy_testDailyOverTimeWithAbsencePolicyB
	 */
	function testDailyOverTimeWithAbsencePolicyB() {
		//Test handling OT that goes to a Time Bank along with Absences.
		global $dd;

		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 200 ); //OT1.5
		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 210 ); //OT2.0
		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 220 ); //OT2.5

		//$this->policy_ids['pay_code'][900]
		//$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 100, $policy_ids['pay_formula_policy'][0] );
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 100, $this->policy_ids['pay_formula_policy'][910] );
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 110, $policy_ids['pay_formula_policy'][1] );
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 120, $policy_ids['pay_formula_policy'][2] );

		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 100, $this->policy_ids['contributing_shift_policy'][14], $policy_ids['pay_code'][0] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 110, $this->policy_ids['contributing_shift_policy'][14], $policy_ids['pay_code'][1] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 120, $this->policy_ids['contributing_shift_policy'][14], $policy_ids['pay_code'][2] );

		//Create Policy Group
		$dd->createPolicyGroup( 	$this->company_id,
									NULL, //Meal
									NULL, //Exception
									NULL, //Holiday
									$policy_ids['overtime'], //OT
									NULL, //Premium
									NULL, //Round
									array($this->user_id), //Users
									NULL, //Break
									NULL, //Accrual
									NULL, //Expense
									array($this->absence_policy_id), //Absence
									array($this->policy_ids['regular'][12]) //Regular
									);


		$date_epoch = TTDate::getBeginWeekEpoch( time(), 1 ); //Start on Monday to avoid DST issues on Sunday morning.
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$this->assertEquals( $this->getCurrentAccrualBalance( $this->user_id, $this->policy_ids['accrual_policy_account'][10] ), (-1 * 3600) );

		$absence_id = $dd->createAbsence( $this->user_id, $date_epoch, (4 * 3600), $this->absence_policy_id );
		$this->assertEquals( $this->getCurrentAccrualBalance( $this->user_id, $this->policy_ids['accrual_policy_account'][10] ), (-5 * 3600) );

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);

		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (16 * 3600) );
		$this->assertEquals( $udt_arr[$date_epoch][0]['start_time_stamp'], strtotime($date_stamp.' 4:00AM') );
		$this->assertEquals( $udt_arr[$date_epoch][0]['end_time_stamp'], strtotime($date_stamp.' 8:00PM') );
		$this->assertEquals( $udt_arr[$date_epoch][0]['hourly_rate'], 0 );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (4 * 3600) );
		$this->assertEquals( $udt_arr[$date_epoch][1]['start_time_stamp'], strtotime($date_stamp.' 8:00AM') );
		$this->assertEquals( $udt_arr[$date_epoch][1]['end_time_stamp'], strtotime($date_stamp.' 12:00PM') );
		$this->assertEquals( $udt_arr[$date_epoch][1]['hourly_rate'], (1 * 21.50) );
		//Absence Time
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 25 ); //Absence Time
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $this->policy_ids['pay_code'][900] ); //Absence Time
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (4 * 3600) );
		$this->assertEquals( $udt_arr[$date_epoch][2]['start_time_stamp'], strtotime($date_stamp.' 4:00AM') );
		$this->assertEquals( $udt_arr[$date_epoch][2]['end_time_stamp'], strtotime($date_stamp.' 8:00AM') );
		$this->assertEquals( $udt_arr[$date_epoch][2]['hourly_rate'], (1 * 21.50) );

		//Overtime 1
		$this->assertEquals( $udt_arr[$date_epoch][5]['object_type_id'], 30 ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][5]['pay_code_id'], $policy_ids['pay_code'][0] );
		$this->assertEquals( $udt_arr[$date_epoch][5]['total_time'], (1 * 3600) );
		$this->assertEquals( $udt_arr[$date_epoch][5]['start_time_stamp'], strtotime($date_stamp.' 12:00PM') );
		$this->assertEquals( $udt_arr[$date_epoch][5]['end_time_stamp'], strtotime($date_stamp.' 1:00PM') );
		$this->assertEquals( $udt_arr[$date_epoch][5]['hourly_rate'], (1.0 * 21.50) );
		//Overtime 2
		$this->assertEquals( $udt_arr[$date_epoch][4]['object_type_id'], 30 ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][4]['pay_code_id'], $policy_ids['pay_code'][1] );
		$this->assertEquals( $udt_arr[$date_epoch][4]['total_time'], (1 * 3600) );
		$this->assertEquals( $udt_arr[$date_epoch][4]['start_time_stamp'], strtotime($date_stamp.' 1:00PM') );
		$this->assertEquals( $udt_arr[$date_epoch][4]['end_time_stamp'], strtotime($date_stamp.' 2:00PM') );
		$this->assertEquals( $udt_arr[$date_epoch][4]['hourly_rate'], (2.0 * 21.50) );
		//Overtime 3
		$this->assertEquals( $udt_arr[$date_epoch][3]['object_type_id'], 30 ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][3]['pay_code_id'], $policy_ids['pay_code'][2] );
		$this->assertEquals( $udt_arr[$date_epoch][3]['total_time'], (6 * 3600) );
		$this->assertEquals( $udt_arr[$date_epoch][3]['start_time_stamp'], strtotime($date_stamp.' 2:00PM') );
		$this->assertEquals( $udt_arr[$date_epoch][3]['end_time_stamp'], strtotime($date_stamp.' 8:00PM') );
		$this->assertEquals( $udt_arr[$date_epoch][3]['hourly_rate'], (2.5 * 21.50) );

		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 6 );

		$this->assertEquals( $this->getCurrentAccrualBalance( $this->user_id, $this->policy_ids['accrual_policy_account'][10] ), (-5 * 3600) );

		//Delete absence in the middle of the week and confirm balance is still correct.
		$dd->deleteAbsence( $absence_id );
		$this->assertEquals( $this->getCurrentAccrualBalance( $this->user_id, $this->policy_ids['accrual_policy_account'][10] ), (-1 * 3600) );

		return TRUE;
	}

	/**
	 * @group OvertimePolicy_testDailyOverTimeWithAbsencePolicyC
	 */
	function testDailyOverTimeWithAbsencePolicyC() {
		//Test handling daily OT with absences and the Start/End timestamps for each UDT record.
		global $dd;

		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 200 ); //OT1.5
		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 210 ); //OT2.0
		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 220 ); //OT2.5

		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 100, $policy_ids['pay_formula_policy'][0] );
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 110, $policy_ids['pay_formula_policy'][1] );
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 120, $policy_ids['pay_formula_policy'][2] );

		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 100, $this->policy_ids['contributing_shift_policy'][14], $policy_ids['pay_code'][0] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 110, $this->policy_ids['contributing_shift_policy'][14], $policy_ids['pay_code'][1] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 120, $this->policy_ids['contributing_shift_policy'][14], $policy_ids['pay_code'][2] );

		//Create Policy Group
		$dd->createPolicyGroup( 	$this->company_id,
									NULL, //Meal
									NULL, //Exception
									NULL, //Holiday
									$policy_ids['overtime'], //OT
									NULL, //Premium
									NULL, //Round
									array($this->user_id), //Users
									NULL, //Break
									NULL, //Accrual
									NULL, //Expense
									array($this->absence_policy_id), //Absence
									array($this->policy_ids['regular'][12]) //Regular
									);


		$date_epoch = TTDate::getBeginWeekEpoch( time(), 1 ); //Start on Monday to avoid DST issues on Sunday morning.
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$this->assertEquals( $this->getCurrentAccrualBalance( $this->user_id, $this->policy_ids['accrual_policy_account'][10] ), (0 * 3600) );

		$absence_id = $dd->createAbsence( $this->user_id, $date_epoch, (12 * 3600), $this->absence_policy_id );
		$this->assertEquals( $this->getCurrentAccrualBalance( $this->user_id, $this->policy_ids['accrual_policy_account'][10] ), (-12 * 3600) );

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);

		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//$this->assertEquals( $udt_arr[$date_epoch][0]['start_time_stamp'], 	strtotime($date_stamp.' 8:00AM') );
		//$this->assertEquals( $udt_arr[$date_epoch][0]['end_time_stamp'], 	strtotime($date_stamp.' 8:00PM') );
		$this->assertEquals( $udt_arr[$date_epoch][0]['hourly_rate'], 0 );

		//Absence Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 25 ); //Absence Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][900] ); //Absence Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (8 * 3600) );
		$this->assertEquals( $udt_arr[$date_epoch][1]['start_time_stamp'], strtotime($date_stamp.' 12:00AM') );
		$this->assertEquals( $udt_arr[$date_epoch][1]['end_time_stamp'], strtotime($date_stamp.' 8:00AM') );
		$this->assertEquals( $udt_arr[$date_epoch][1]['hourly_rate'], (1 * 21.50) );

		//Overtime 1
		$this->assertEquals( $udt_arr[$date_epoch][4]['object_type_id'], 30 ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][4]['pay_code_id'], $policy_ids['pay_code'][0] );
		$this->assertEquals( $udt_arr[$date_epoch][4]['total_time'], (1 * 3600) );
		$this->assertEquals( $udt_arr[$date_epoch][4]['start_time_stamp'], strtotime($date_stamp.' 8:00AM') );
		$this->assertEquals( $udt_arr[$date_epoch][4]['end_time_stamp'], strtotime($date_stamp.' 9:00AM') );
		$this->assertEquals( $udt_arr[$date_epoch][4]['hourly_rate'], (1.5 * 21.50) );
		//Overtime 2
		$this->assertEquals( $udt_arr[$date_epoch][3]['object_type_id'], 30 ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][3]['pay_code_id'], $policy_ids['pay_code'][1] );
		$this->assertEquals( $udt_arr[$date_epoch][3]['total_time'], (1 * 3600) );
		$this->assertEquals( $udt_arr[$date_epoch][3]['start_time_stamp'], strtotime($date_stamp.' 9:00AM') );
		$this->assertEquals( $udt_arr[$date_epoch][3]['end_time_stamp'], strtotime($date_stamp.' 10:00AM') );
		$this->assertEquals( $udt_arr[$date_epoch][3]['hourly_rate'], (2.0 * 21.50) );
		//Overtime 3
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 30 ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $policy_ids['pay_code'][2] );
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (2 * 3600) );
		$this->assertEquals( $udt_arr[$date_epoch][2]['start_time_stamp'], strtotime($date_stamp.' 10:00AM') );
		$this->assertEquals( $udt_arr[$date_epoch][2]['end_time_stamp'], strtotime($date_stamp.' 12:00PM') );
		$this->assertEquals( $udt_arr[$date_epoch][2]['hourly_rate'], (2.5 * 21.50) );

		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 5 );

		$this->assertEquals( $this->getCurrentAccrualBalance( $this->user_id, $this->policy_ids['accrual_policy_account'][10] ), (-12 * 3600) );

		//Delete absence in the middle of the week and confirm balance is still correct.
		$dd->deleteAbsence( $absence_id );
		$this->assertEquals( $this->getCurrentAccrualBalance( $this->user_id, $this->policy_ids['accrual_policy_account'][10] ), (0 * 3600) );

		return TRUE;
	}

	/**
	 * @group OvertimePolicy_testDailyOverTimeWithAbsencePolicyD
	 */
	function testDailyOverTimeWithAbsencePolicyD() {
		//Test handling daily OT with absences and the Start/End timestamps for each UDT record.
		//Only have the absence record entered directly on the timesheet, so there are no start/end timestamps though.
		//and make sure the Absence policy uses Regular Time pay code so its included in OT.
		global $dd;

		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 200 ); //OT1.5
		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 210 ); //OT2.0
		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 220 ); //OT2.5

		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 100, $policy_ids['pay_formula_policy'][0] );
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 110, $policy_ids['pay_formula_policy'][1] );
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 120, $policy_ids['pay_formula_policy'][2] );

		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 100, $this->policy_ids['contributing_shift_policy'][14], $policy_ids['pay_code'][0] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 110, $this->policy_ids['contributing_shift_policy'][14], $policy_ids['pay_code'][1] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 120, $this->policy_ids['contributing_shift_policy'][14], $policy_ids['pay_code'][2] );

		$policy_ids['absence'][] = $dd->createAbsencePolicy( $this->company_id, 30, $this->policy_ids['pay_code'][100] );

		//Create Policy Group
		$dd->createPolicyGroup( 	$this->company_id,
									NULL, //Meal
									NULL, //Exception
									NULL, //Holiday
									$policy_ids['overtime'], //OT
									NULL, //Premium
									NULL, //Round
									array($this->user_id), //Users
									NULL, //Break
									NULL, //Accrual
									NULL, //Expense
									$policy_ids['absence'], //Absence
									array($this->policy_ids['regular'][12]) //Regular
									);

		$date_epoch = TTDate::getBeginWeekEpoch( time(), 1 ); //Start on Monday to avoid DST issues on Sunday morning.
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$this->assertEquals( $this->getCurrentAccrualBalance( $this->user_id, $this->policy_ids['accrual_policy_account'][10] ), (0 * 3600) );

		$absence_id = $dd->createAbsence( $this->user_id, $date_epoch, (7 * 3600), $policy_ids['absence'][0] );
		//$this->assertEquals( $this->getCurrentAccrualBalance( $this->user_id, $this->policy_ids['accrual_policy_account'][10] ), (-12 * 3600) );

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);

		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (7 * 3600) );
		//$this->assertEquals( $udt_arr[$date_epoch][0]['start_time_stamp'], 	strtotime($date_stamp.' 8:00AM') );
		//$this->assertEquals( $udt_arr[$date_epoch][0]['end_time_stamp'], 	strtotime($date_stamp.' 8:00PM') );
		$this->assertEquals( $udt_arr[$date_epoch][0]['hourly_rate'], 0 );

		//Absence Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Absence Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Absence Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (7 * 3600) );
		$this->assertEquals( $udt_arr[$date_epoch][1]['start_time_stamp'], strtotime($date_stamp.' 12:00AM') );
		$this->assertEquals( $udt_arr[$date_epoch][1]['end_time_stamp'], strtotime($date_stamp.' 7:00AM') );
		$this->assertEquals( $udt_arr[$date_epoch][1]['hourly_rate'], (1 * 21.50) );

		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 2 );

		//$this->assertEquals( $this->getCurrentAccrualBalance( $this->user_id, $this->policy_ids['accrual_policy_account'][10] ), (-12 * 3600) );

		//Delete absence in the middle of the week and confirm balance is still correct.
		$dd->deleteAbsence( $absence_id );
		$this->assertEquals( $this->getCurrentAccrualBalance( $this->user_id, $this->policy_ids['accrual_policy_account'][10] ), (0 * 3600) );

		return TRUE;
	}

	/**
	 * @group OvertimePolicy_testDailyOverTimeWithAbsencePolicyE
	 */
	function testDailyOverTimeWithAbsencePolicyE() {
		//Test handling daily OT with absences and the Start/End timestamps for each UDT record.
		//Only have the absence record entered directly on the timesheet, so there are no start/end timestamps though.
		//and make sure the Absence policy uses Regular Time pay code so its included in OT.
		global $dd;

		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 200 ); //OT1.5
		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 210 ); //OT2.0
		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 220 ); //OT2.5

		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 100, $policy_ids['pay_formula_policy'][0] );
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 110, $policy_ids['pay_formula_policy'][1] );
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 120, $policy_ids['pay_formula_policy'][2] );

		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 100, $this->policy_ids['contributing_shift_policy'][14], $policy_ids['pay_code'][0] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 110, $this->policy_ids['contributing_shift_policy'][14], $policy_ids['pay_code'][1] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 120, $this->policy_ids['contributing_shift_policy'][14], $policy_ids['pay_code'][2] );

		$policy_ids['absence'][] = $dd->createAbsencePolicy( $this->company_id, 30, $this->policy_ids['pay_code'][100] );

		//Create Policy Group
		$dd->createPolicyGroup( 	$this->company_id,
									NULL, //Meal
									NULL, //Exception
									NULL, //Holiday
									$policy_ids['overtime'], //OT
									NULL, //Premium
									NULL, //Round
									array($this->user_id), //Users
									NULL, //Break
									NULL, //Accrual
									NULL, //Expense
									$policy_ids['absence'], //Absence
									array($this->policy_ids['regular'][12]) //Regular
									);

		$date_epoch = TTDate::getBeginWeekEpoch( time(), 1 ); //Start on Monday to avoid DST issues on Sunday morning.
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$this->assertEquals( $this->getCurrentAccrualBalance( $this->user_id, $this->policy_ids['accrual_policy_account'][10] ), (0 * 3600) );

		$absence_id = $dd->createAbsence( $this->user_id, $date_epoch, (12 * 3600), $policy_ids['absence'][0] );
		//$this->assertEquals( $this->getCurrentAccrualBalance( $this->user_id, $this->policy_ids['accrual_policy_account'][10] ), (-12 * 3600) );

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);

		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//$this->assertEquals( $udt_arr[$date_epoch][0]['start_time_stamp'], 	strtotime($date_stamp.' 8:00AM') );
		//$this->assertEquals( $udt_arr[$date_epoch][0]['end_time_stamp'], 	strtotime($date_stamp.' 8:00PM') );
		$this->assertEquals( $udt_arr[$date_epoch][0]['hourly_rate'], 0 );

		//Absence Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Absence Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Absence Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (8 * 3600) );
		$this->assertEquals( $udt_arr[$date_epoch][1]['start_time_stamp'], strtotime($date_stamp.' 12:00AM') );
		$this->assertEquals( $udt_arr[$date_epoch][1]['end_time_stamp'], strtotime($date_stamp.' 8:00AM') );
		$this->assertEquals( $udt_arr[$date_epoch][1]['hourly_rate'], (1 * 21.50) );

		//Overtime 1
		$this->assertEquals( $udt_arr[$date_epoch][4]['object_type_id'], 30 ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][4]['pay_code_id'], $policy_ids['pay_code'][0] );
		$this->assertEquals( $udt_arr[$date_epoch][4]['total_time'], (1 * 3600) );
		$this->assertEquals( $udt_arr[$date_epoch][4]['start_time_stamp'], strtotime($date_stamp.' 8:00AM') );
		$this->assertEquals( $udt_arr[$date_epoch][4]['end_time_stamp'], strtotime($date_stamp.' 9:00AM') );
		$this->assertEquals( $udt_arr[$date_epoch][4]['hourly_rate'], (1.5 * 21.50) );
		//Overtime 2
		$this->assertEquals( $udt_arr[$date_epoch][3]['object_type_id'], 30 ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][3]['pay_code_id'], $policy_ids['pay_code'][1] );
		$this->assertEquals( $udt_arr[$date_epoch][3]['total_time'], (1 * 3600) );
		$this->assertEquals( $udt_arr[$date_epoch][3]['start_time_stamp'], strtotime($date_stamp.' 9:00AM') );
		$this->assertEquals( $udt_arr[$date_epoch][3]['end_time_stamp'], strtotime($date_stamp.' 10:00AM') );
		$this->assertEquals( $udt_arr[$date_epoch][3]['hourly_rate'], (2.0 * 21.50) );
		//Overtime 3
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 30 ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $policy_ids['pay_code'][2] );
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (2 * 3600) );
		$this->assertEquals( $udt_arr[$date_epoch][2]['start_time_stamp'], strtotime($date_stamp.' 10:00AM') );
		$this->assertEquals( $udt_arr[$date_epoch][2]['end_time_stamp'], strtotime($date_stamp.' 12:00PM') );
		$this->assertEquals( $udt_arr[$date_epoch][2]['hourly_rate'], (2.5 * 21.50) );

		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 5 );

		//$this->assertEquals( $this->getCurrentAccrualBalance( $this->user_id, $this->policy_ids['accrual_policy_account'][10] ), (-12 * 3600) );

		//Delete absence in the middle of the week and confirm balance is still correct.
		$dd->deleteAbsence( $absence_id );
		$this->assertEquals( $this->getCurrentAccrualBalance( $this->user_id, $this->policy_ids['accrual_policy_account'][10] ), (0 * 3600) );

		return TRUE;
	}


	/**
	 * @group OvertimePolicy_testNoOverTimePolicyWithAbsence
	 */
	function testNoOverTimePolicyWithAbsence() {
		//This is mainly to test for a bug that occurs when no UDT records are returned to calculateOverTimePolicy
		global $dd;

		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 200 ); //OT1.5
		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 210 ); //OT2.0
		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 220 ); //OT2.5

		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 100, $policy_ids['pay_formula_policy'][0] );
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 110, $policy_ids['pay_formula_policy'][1] );
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 120, $policy_ids['pay_formula_policy'][2] );

		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 100, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][0] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 110, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][1] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 120, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][2] );

		//Create Policy Group
		$dd->createPolicyGroup( 	$this->company_id,
								   NULL, //Meal
								   NULL, //Exception
								   NULL, //Holiday
								   $policy_ids['overtime'], //OT
								   NULL, //Premium
								   NULL, //Round
								   array($this->user_id), //Users
								   NULL, //Break
								   NULL, //Accrual
								   NULL, //Expense
								   array($this->absence_policy_id), //Absence
								   array($this->policy_ids['regular'][12]) //Regular
		);

		$date_epoch = TTDate::getBeginWeekEpoch( time() );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$this->assertEquals( $this->getCurrentAccrualBalance( $this->user_id, $this->policy_ids['accrual_policy_account'][10] ), (0 * 3600) );

		$absence_id = $dd->createAbsence( $this->user_id, $date_epoch, (4 * 3600), $this->absence_policy_id );
		$this->assertEquals( $this->getCurrentAccrualBalance( $this->user_id, $this->policy_ids['accrual_policy_account'][10] ), (-4 * 3600) );

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);

		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (4 * 3600) );
		$this->assertEquals( $udt_arr[$date_epoch][0]['hourly_rate'], 0 );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 25 ); //Absence Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][900] ); //PTO/Vacation
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (4 * 3600) );
		$this->assertEquals( $udt_arr[$date_epoch][1]['hourly_rate'], 21.50 );

		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 2 );

		return TRUE;
	}

	/**
	 * @group OvertimePolicy_testWeeklyOverTimeWithAbsencePolicyA
	 */
	function testWeeklyOverTimeWithAbsencePolicyA() {
		global $dd;

		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 200 ); //OT1.5
		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 210 ); //OT2.0
		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 220 ); //OT2.5

		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 200, $policy_ids['pay_formula_policy'][0] );
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 210, $policy_ids['pay_formula_policy'][1] );
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 220, $policy_ids['pay_formula_policy'][2] );

		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 200, $this->policy_ids['contributing_shift_policy'][14], $policy_ids['pay_code'][0] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 210, $this->policy_ids['contributing_shift_policy'][14], $policy_ids['pay_code'][1] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 220, $this->policy_ids['contributing_shift_policy'][14], $policy_ids['pay_code'][2] );
		//print_r($policy_ids['overtime']);

		//Create Policy Group
		$dd->createPolicyGroup( 	$this->company_id,
									NULL, //Meal
									NULL, //Exception
									NULL, //Holiday
									$policy_ids['overtime'], //OT
									NULL, //Premium
									NULL, //Round
									array($this->user_id), //Users
									NULL, //Break
									NULL, //Accrual
									NULL, //Expense
									array($this->absence_policy_id), //Absence
									array($this->policy_ids['regular'][12]) //Regular
									);

		$date_epoch = TTDate::getBeginWeekEpoch( time() );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$this->assertEquals( $this->getCurrentAccrualBalance( $this->user_id, $this->policy_ids['accrual_policy_account'][10] ), (0 * 3600) );

		//
		//Day of Week: 1
		//
		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (12 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 2 );


		//
		//Day of Week: 2 (Absence to be included in Weekly OT)
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (1 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$this->assertEquals( $this->getCurrentAccrualBalance( $this->user_id, $this->policy_ids['accrual_policy_account'][10] ), (0 * 3600) );
		$dd->createAbsence( $this->user_id, $date_epoch, (12 * 3600), $this->absence_policy_id );
		$this->assertEquals( $this->getCurrentAccrualBalance( $this->user_id, $this->policy_ids['accrual_policy_account'][10] ), (-12 * 3600) );

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);

		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//Absence Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 25 ); //Absence Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][900] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (12 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 2 );

		//
		//Day of Week: 3
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (2 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (12 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 2 );

		//
		//Day of Week: 4
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (3 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (11 * 3600) );
		//Overtime 1
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 30 ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $policy_ids['pay_code'][0] );
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (1 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 3 );

		//
		//Day of Week: 5
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (4 * 86400 + 3601)) ;
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//Overtime 1
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 30 ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $policy_ids['pay_code'][0] );
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (11 * 3600) );
		//Overtime 2
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 30 ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $policy_ids['pay_code'][1] );
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (1 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 3 );

		//
		//Day of Week: 6
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (5 * 86400 + 3601) ) ;
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//Overtime 1
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 30 ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $policy_ids['pay_code'][1] );
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (11 * 3600) );
		//Overtime 2
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 30 ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $policy_ids['pay_code'][2] );
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (1 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 3 );

		$this->assertEquals( $this->getCurrentAccrualBalance( $this->user_id, $this->policy_ids['accrual_policy_account'][10] ), (-12 * 3600) );

		return TRUE;
	}

	/**
	 * @group OvertimePolicy_testWeeklyOverTimeWithAbsencePolicyB
	 */
	function testWeeklyOverTimeWithAbsencePolicyB() {
		//Test with absence at the beginning of the week.
		global $dd;

		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 200 ); //OT1.5
		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 210 ); //OT2.0
		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 220 ); //OT2.5

		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 200, $policy_ids['pay_formula_policy'][0] );
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 210, $policy_ids['pay_formula_policy'][1] );
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 220, $policy_ids['pay_formula_policy'][2] );

		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 200, $this->policy_ids['contributing_shift_policy'][14], $policy_ids['pay_code'][0] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 210, $this->policy_ids['contributing_shift_policy'][14], $policy_ids['pay_code'][1] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 220, $this->policy_ids['contributing_shift_policy'][14], $policy_ids['pay_code'][2] );
		//print_r($policy_ids['overtime']);

		//Create Policy Group
		$dd->createPolicyGroup( 	$this->company_id,
									NULL, //Meal
									NULL, //Exception
									NULL, //Holiday
									$policy_ids['overtime'], //OT
									NULL, //Premium
									NULL, //Round
									array($this->user_id), //Users
									NULL, //Break
									NULL, //Accrual
									NULL, //Expense
									array($this->absence_policy_id), //Absence
									array($this->policy_ids['regular'][12]) //Regular
									);

		$this->assertEquals( $this->getCurrentAccrualBalance( $this->user_id, $this->policy_ids['accrual_policy_account'][10] ), (0 * 3600) );

		//
		//Day of Week: 1 (Absence to be included in Weekly OT)
		//
		$date_epoch = TTDate::getBeginWeekEpoch( time() );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createAbsence( $this->user_id, $date_epoch, (12 * 3600), $this->absence_policy_id );
		$this->assertEquals( $this->getCurrentAccrualBalance( $this->user_id, $this->policy_ids['accrual_policy_account'][10] ), (-12 * 3600) );


		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);

		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//Absence Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 25 ); //Absence Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][900] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (12 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 2 );

		//
		//Day of Week: 2
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (1 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (12 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 2 );



		//
		//Day of Week: 3
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (2 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (12 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 2 );

		//
		//Day of Week: 4
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (3 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (11 * 3600) );
		//Overtime 1
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 30 ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $policy_ids['pay_code'][0] );
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (1 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 3 );

		//
		//Day of Week: 5
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (4 * 86400 + 3601)) ;
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//Overtime 1
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 30 ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $policy_ids['pay_code'][0] );
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (11 * 3600) );
		//Overtime 2
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 30 ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $policy_ids['pay_code'][1] );
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (1 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 3 );

		//
		//Day of Week: 6
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (5 * 86400 + 3601) ) ;
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//Overtime 1
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 30 ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $policy_ids['pay_code'][1] );
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (11 * 3600) );
		//Overtime 2
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 30 ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $policy_ids['pay_code'][2] );
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (1 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 3 );

		$this->assertEquals( $this->getCurrentAccrualBalance( $this->user_id, $this->policy_ids['accrual_policy_account'][10] ), (-12 * 3600) );

		return TRUE;
	}

	/**
	 * @group OvertimePolicy_testWeeklyOverTimeWithAbsencePolicyC
	 */
	function testWeeklyOverTimeWithAbsencePolicyC() {
		//Test with absence at the end of the week, where it switched into OT.
		global $dd;

		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 200 ); //OT1.5
		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 210 ); //OT2.0
		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 220 ); //OT2.5

		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 200, $policy_ids['pay_formula_policy'][0] );
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 210, $policy_ids['pay_formula_policy'][1] );
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 220, $policy_ids['pay_formula_policy'][2] );

		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 200, $this->policy_ids['contributing_shift_policy'][14], $policy_ids['pay_code'][0] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 210, $this->policy_ids['contributing_shift_policy'][14], $policy_ids['pay_code'][1] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 220, $this->policy_ids['contributing_shift_policy'][14], $policy_ids['pay_code'][2] );
		//print_r($policy_ids['overtime']);

		//Create Policy Group
		$dd->createPolicyGroup( 	$this->company_id,
									NULL, //Meal
									NULL, //Exception
									NULL, //Holiday
									$policy_ids['overtime'], //OT
									NULL, //Premium
									NULL, //Round
									array($this->user_id), //Users
									NULL, //Break
									NULL, //Accrual
									NULL, //Expense
									array($this->absence_policy_id), //Absence
									array($this->policy_ids['regular'][12]) //Regular
									);

		$this->assertEquals( $this->getCurrentAccrualBalance( $this->user_id, $this->policy_ids['accrual_policy_account'][10] ), (0 * 3600) );


		//
		//Day of Week: 1
		//
		$date_epoch = TTDate::getBeginWeekEpoch( time() );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (12 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 2 );



		//
		//Day of Week: 2
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (1 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (12 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 2 );

		//
		//Day of Week: 3
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (2 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (12 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 2 );

		//
		//Day of Week: 4 (Absence to be included in Weekly OT)
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (3 * 86400 + 3601) ) ;
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$this->assertEquals( $this->getCurrentAccrualBalance( $this->user_id, $this->policy_ids['accrual_policy_account'][10] ), (0 * 3600) );
		$dd->createAbsence( $this->user_id, $date_epoch, (12 * 3600), $this->absence_policy_id );
		$this->assertEquals( $this->getCurrentAccrualBalance( $this->user_id, $this->policy_ids['accrual_policy_account'][10] ), (-12 * 3600) );

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);

		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//Absence Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 25 ); //Absence Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][900] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (11 * 3600) );
		//Overtime 1
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 30 ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $policy_ids['pay_code'][0] );
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (1 * 3600) );

		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 3 );

		//
		//Day of Week: 5 (Absence to be included in Weekly OT)
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (4 * 86400 + 3601) ) ;
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$absence_id = $dd->createAbsence( $this->user_id, $date_epoch, (12 * 3600), $this->absence_policy_id );
		$this->assertEquals( $this->getCurrentAccrualBalance( $this->user_id, $this->policy_ids['accrual_policy_account'][10] ), (-24 * 3600) );


		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);

		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//Overtime 2
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 30 ); //Over Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $policy_ids['pay_code'][1] );
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (1 * 3600) );
		//Overtime 1
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 30 ); //Over Time
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $policy_ids['pay_code'][0] );
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (11 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 3 );

		//
		//Day of Week: 6 (Absence to be included in Weekly OT)
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (5 * 86400 + 3601) ) ;
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createAbsence( $this->user_id, $date_epoch, (12 * 3600), $this->absence_policy_id );
		$this->assertEquals( $this->getCurrentAccrualBalance( $this->user_id, $this->policy_ids['accrual_policy_account'][10] ), (-36 * 3600) );

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);

		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//Overtime 2
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 30 ); //Over Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $policy_ids['pay_code'][2] );
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (1 * 3600) );
		//Overtime 1
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 30 ); //Over Time
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $policy_ids['pay_code'][1] );
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (11 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 3 );

		$this->assertEquals( $this->getCurrentAccrualBalance( $this->user_id, $this->policy_ids['accrual_policy_account'][10] ), (-36 * 3600) );

		//Delete absence in the middle of the week and confirm balance is still correct.
		$dd->deleteAbsence( $absence_id );
		$this->assertEquals( $this->getCurrentAccrualBalance( $this->user_id, $this->policy_ids['accrual_policy_account'][10] ), (-24 * 3600) );

		return TRUE;
	}

	/**
	 * @group OvertimePolicy_testWeeklyOverTimeWithAbsencePolicyD
	 */
	function testWeeklyOverTimeWithAbsencePolicyD() {
		//Test with schedule absence and auto-deduct lunch at the end of the week, where it switched into OT.
		global $dd;

		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 200 ); //OT1.5
		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 210 ); //OT2.0
		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 220 ); //OT2.5

		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 200, $policy_ids['pay_formula_policy'][0] );
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 210, $policy_ids['pay_formula_policy'][1] );
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 220, $policy_ids['pay_formula_policy'][2] );

		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 200, $this->policy_ids['contributing_shift_policy'][14], $policy_ids['pay_code'][0] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 210, $this->policy_ids['contributing_shift_policy'][14], $policy_ids['pay_code'][1] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 220, $this->policy_ids['contributing_shift_policy'][14], $policy_ids['pay_code'][2] );
		//print_r($policy_ids['overtime']);

		$policy_ids['meal'][] = $this->createMealPolicy( $this->company_id, 120 ); //AutoDeduct 1hr

		$schedule_policy_id = $this->createSchedulePolicy( 10, $policy_ids['meal'][0] );

		//Create Policy Group
		$dd->createPolicyGroup( 	$this->company_id,
									NULL, //Meal
									NULL, //Exception
									NULL, //Holiday
									$policy_ids['overtime'], //OT
									NULL, //Premium
									NULL, //Round
									array($this->user_id), //Users
									NULL, //Break
									NULL, //Accrual
									NULL, //Expense
									array($this->absence_policy_id), //Absence
									array($this->policy_ids['regular'][12]) //Regular
									);

		$this->assertEquals( $this->getCurrentAccrualBalance( $this->user_id, $this->policy_ids['accrual_policy_account'][10] ), (0 * 3600) );


		//
		//Day of Week: 1
		//
		$date_epoch = TTDate::getBeginWeekEpoch( time() );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (12 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 2 );



		//
		//Day of Week: 2
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (1 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (12 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 2 );

		//
		//Day of Week: 3
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (2 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (12 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 2 );

		//
		//Day of Week: 4 (Absence to be included in Weekly OT)
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (3 * 86400 + 3601) ) ;
		$date_stamp = TTDate::getDate('DATE', $date_epoch );


		$this->assertEquals( $this->getCurrentAccrualBalance( $this->user_id, $this->policy_ids['accrual_policy_account'][10] ), (0 * 3600) );
		//$dd->createAbsence( $this->user_id, $date_epoch, (12 * 3600), $this->absence_policy_id );
		$schedule_id = $this->createSchedule( $this->user_id, $date_epoch, array(
																	'status_id' => 20, //Absence
																	'absence_policy_id' => $this->absence_policy_id,
																	'schedule_policy_id' => $schedule_policy_id,
																	'start_time' => '8:00AM',
																	'end_time' => '9:00PM',
																	) );

		$this->assertEquals( $this->getCurrentAccrualBalance( $this->user_id, $this->policy_ids['accrual_policy_account'][10] ), (-12 * 3600) );

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);

		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//Absence Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 25 ); //Absence Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][900] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (11 * 3600) );
		//Overtime 1
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 30 ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $policy_ids['pay_code'][0] );
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (1 * 3600) );

		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 3 );

		//
		//Day of Week: 5 (Absence to be included in Weekly OT)
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (4 * 86400 + 3601) ) ;
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		//$dd->createAbsence( $this->user_id, $date_epoch, (12 * 3600), $this->absence_policy_id );
		$this->createSchedule( $this->user_id, $date_epoch, array(
																	'status_id' => 20, //Absence
																	'absence_policy_id' => $this->absence_policy_id,
																	'schedule_policy_id' => $schedule_policy_id,
																	'start_time' => '8:00AM',
																	'end_time' => '9:00PM',
																	) );

		$this->assertEquals( $this->getCurrentAccrualBalance( $this->user_id, $this->policy_ids['accrual_policy_account'][10] ), (-24 * 3600) );


		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);

		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//Overtime 2
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 30 ); //Over Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $policy_ids['pay_code'][1] );
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (1 * 3600) );
		//Overtime 1
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 30 ); //Over Time
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $policy_ids['pay_code'][0] );
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (11 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 3 );

		//
		//Day of Week: 6 (Absence to be included in Weekly OT)
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (5 * 86400 + 3601) ) ;
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$this->createSchedule( $this->user_id, $date_epoch, array(
																	'status_id' => 20, //Absence
																	'absence_policy_id' => $this->absence_policy_id,
																	'schedule_policy_id' => $schedule_policy_id,
																	'start_time' => '8:00AM',
																	'end_time' => '9:00PM',
																	) );

		//$dd->createAbsence( $this->user_id, $date_epoch, (12 * 3600), $this->absence_policy_id );
		$this->assertEquals( $this->getCurrentAccrualBalance( $this->user_id, $this->policy_ids['accrual_policy_account'][10] ), (-36 * 3600) );

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);

		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//Overtime 2
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 30 ); //Over Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $policy_ids['pay_code'][2] );
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (1 * 3600) );
		//Overtime 1
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 30 ); //Over Time
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $policy_ids['pay_code'][1] );
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (11 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 3 );

		$this->assertEquals( $this->getCurrentAccrualBalance( $this->user_id, $this->policy_ids['accrual_policy_account'][10] ), (-36 * 3600) );


		//Delete scheduled absence in the middle of the week and confirm balance is still correct.
		$dd->deleteSchedule( $schedule_id );
		$this->assertEquals( $this->getCurrentAccrualBalance( $this->user_id, $this->policy_ids['accrual_policy_account'][10] ), (-24 * 3600) );

		return TRUE;
	}


	/**
	 * @group OvertimePolicy_testWeeklyOverTimeWithAdjustTriggerAndAbsencePolicyA
	 */
	function testWeeklyOverTimeWithAdjustTriggerAndAbsencePolicyA() {
		return TRUE;
	}

	/**
	 * @group OvertimePolicy_testWeeklyOverTimeWithAdjustTriggerAndAbsencePolicyB
	 */
	function testWeeklyOverTimeWithAdjustTriggerAndAbsencePolicyB() {
		return TRUE;
	}

	/**
	 * @group OvertimePolicy_testBiWeeklyOverTimeDates
	 */
	function testBiWeeklyOverTimeDates() {
		$cp = TTnew( 'CalculatePolicy' );

		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('22-Dec-2013'), strtotime('22-Dec-2013'), 0 ), FALSE ); //Sun
		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('29-Dec-2013'), strtotime('22-Dec-2013'), 0 ), TRUE ); //Sun

		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('29-Dec-2013'), strtotime('29-Dec-2013'), 0 ), FALSE ); //Sun
		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('30-Dec-2013'), strtotime('29-Dec-2013'), 0 ), FALSE );
		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('31-Dec-2013'), strtotime('29-Dec-2013'), 0 ), FALSE );
		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('01-Jan-2014'), strtotime('29-Dec-2013'), 0 ), FALSE );
		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('02-Jan-2014'), strtotime('29-Dec-2013'), 0 ), FALSE );
		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('03-Jan-2014'), strtotime('29-Dec-2013'), 0 ), FALSE );
		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('04-Jan-2014'), strtotime('29-Dec-2013'), 0 ), FALSE );
		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('05-Jan-2014'), strtotime('29-Dec-2013'), 0 ), TRUE );
		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('06-Jan-2014'), strtotime('29-Dec-2013'), 0 ), TRUE );
		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('07-Jan-2014'), strtotime('29-Dec-2013'), 0 ), TRUE );
		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('08-Jan-2014'), strtotime('29-Dec-2013'), 0 ), TRUE );
		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('09-Jan-2014'), strtotime('29-Dec-2013'), 0 ), TRUE );
		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('10-Jan-2014'), strtotime('29-Dec-2013'), 0 ), TRUE );
		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('11-Jan-2014'), strtotime('29-Dec-2013'), 0 ), TRUE );

		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('29-Dec-2013'), strtotime('01-Jan-2014'), 0 ), FALSE ); //Sun
		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('30-Dec-2013'), strtotime('01-Jan-2014'), 0 ), FALSE );
		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('31-Dec-2013'), strtotime('01-Jan-2014'), 0 ), FALSE );
		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('01-Jan-2014'), strtotime('01-Jan-2014'), 0 ), FALSE );
		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('02-Jan-2014'), strtotime('01-Jan-2014'), 0 ), FALSE );
		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('03-Jan-2014'), strtotime('01-Jan-2014'), 0 ), FALSE );
		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('04-Jan-2014'), strtotime('01-Jan-2014'), 0 ), FALSE );
		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('05-Jan-2014'), strtotime('01-Jan-2014'), 0 ), TRUE );
		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('06-Jan-2014'), strtotime('01-Jan-2014'), 0 ), TRUE );
		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('07-Jan-2014'), strtotime('01-Jan-2014'), 0 ), TRUE );
		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('08-Jan-2014'), strtotime('01-Jan-2014'), 0 ), TRUE );
		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('09-Jan-2014'), strtotime('01-Jan-2014'), 0 ), TRUE );
		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('10-Jan-2014'), strtotime('01-Jan-2014'), 0 ), TRUE );
		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('11-Jan-2014'), strtotime('01-Jan-2014'), 0 ), TRUE );

		//These were originally incorrect by the looks of it, and once we added daydiff rounding it fixed them.
		//$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('01-May-2016'), strtotime('28-Dec-2013'), 0 ), FALSE ); //Sat
		//$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('01-May-2016'), strtotime('29-Dec-2013'), 0 ), TRUE ); //Sun
		//$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('01-May-2016'), strtotime('01-Jan-2014'), 0 ), TRUE ); //Wed
		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('01-May-2016'), strtotime('28-Dec-2013'), 0 ), TRUE ); //Sat
		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('01-May-2016'), strtotime('29-Dec-2013'), 0 ), FALSE ); //Sun
		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('01-May-2016'), strtotime('01-Jan-2014'), 0 ), FALSE ); //Wed

		//Test 53 week year.
		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('29-Dec-2014'), strtotime('29-Dec-2014'), 0 ), FALSE ); //Sun: Week 1
		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('05-Jan-2015'), strtotime('29-Dec-2014'), 0 ), TRUE ); //Sun
		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('12-Jan-2015'), strtotime('29-Dec-2014'), 0 ), FALSE ); //Sun
		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('26-Jan-2015'), strtotime('29-Dec-2014'), 0 ), FALSE ); //Sun
		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('09-Feb-2015'), strtotime('29-Dec-2014'), 0 ), FALSE ); //Sun
		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('23-Feb-2015'), strtotime('29-Dec-2014'), 0 ), FALSE ); //Sun


		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('09-Mar-2015'), strtotime('29-Dec-2014'), 0 ), FALSE ); //Sun
		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('23-Mar-2015'), strtotime('29-Dec-2014'), 0 ), FALSE ); //Sun


		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('14-Dec-2015'), strtotime('29-Dec-2014'), 0 ), FALSE ); //Sun: Week 51
		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('21-Dec-2015'), strtotime('29-Dec-2014'), 0 ), TRUE ); //Sun: Week 52
		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('28-Dec-2015'), strtotime('29-Dec-2014'), 0 ), FALSE ); //Sun: Week 53

		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('01-Jan-2014'), strtotime('01-Jan-2014'), 0 ), FALSE ); //Sun
		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('06-Jan-2014'), strtotime('01-Jan-2014'), 0 ), TRUE ); //Sun
		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('12-Jan-2014'), strtotime('01-Jan-2014'), 0 ), FALSE ); //Sun
		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('20-Jan-2014'), strtotime('01-Jan-2014'), 0 ), TRUE ); //Sun

		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('03-Feb-2014'), strtotime('01-Jan-2014'), 0 ), TRUE ); //Sun
		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('17-Feb-2014'), strtotime('01-Jan-2014'), 0 ), TRUE ); //Sun


		$retval = FALSE;
		$anchor_date = TTDate::getMiddleDayEpoch( strtotime('29-Dec-2014') );
		$x = 0;
		for( $i = $anchor_date; $i <= TTDate::getMiddleDayEpoch( strtotime('29-Dec-2015') ); $i += ( 86400 * 7 ) ) {
			$x++; //Run at beginning so the counter essentially starts at 1.
			$this->assertEquals( $cp->isSecondBiWeeklyOverTimeWeek( $i, $anchor_date, 0 ), $retval );
			$retval = !$retval; //Swap

		}
		$this->assertEquals( $x, 53 );


		$retval = FALSE;
		$anchor_date = TTDate::getMiddleDayEpoch( strtotime('29-Dec-2014') );
		$x = 0;
		for( $i = $anchor_date; $i <= TTDate::getMiddleDayEpoch( strtotime('29-Dec-2025') ); $i += ( 86400 * 7 ) ) {
			$x++; //Run at beginning so the counter essentially starts at 1.
			$this->assertEquals( $cp->isSecondBiWeeklyOverTimeWeek( $i, $anchor_date, 0 ), $retval );
			$retval = !$retval; //Swap

		}
		$this->assertEquals( $x, 575 );
	}

	/**
	 * @group OvertimePolicy_testBiWeeklyOverTimePeriodDates
	 */
	function testBiWeeklyOverTimePeriodDates() {
		$cp = TTnew( 'CalculatePolicy' );

		$ot_period_dates = $cp->getOverTimePeriodDates( strtotime('22-Dec-2013'), 2, strtotime('22-Dec-2013'), 0 );
		$this->assertEquals( $ot_period_dates['start_date'], TTDate::getBeginDayEpoch( strtotime('22-Dec-2013') ) ); //Sun
		$this->assertEquals( $ot_period_dates['end_date'], TTDate::getEndDayEpoch( strtotime('04-Jan-2014') ) ); //Mon
		$this->assertEquals( $ot_period_dates['is_first_week'], TRUE );
		$this->assertEquals( $ot_period_dates['is_last_week'], FALSE );
//		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('29-Dec-2013'), strtotime('22-Dec-2013'), 0 ), TRUE ); //Sun



		$ot_period_dates = $cp->getOverTimePeriodDates( strtotime('29-Dec-2013'), 2, strtotime('29-Dec-2013'), 0 );
		$this->assertEquals( $ot_period_dates['start_date'], TTDate::getBeginDayEpoch( strtotime('29-Dec-2013') ) ); //Sun
		$this->assertEquals( $ot_period_dates['end_date'], TTDate::getEndDayEpoch( strtotime('11-Jan-2014') ) ); //Mon
		$this->assertEquals( $ot_period_dates['is_first_week'], TRUE );
		$this->assertEquals( $ot_period_dates['is_last_week'], FALSE );
//		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('29-Dec-2013'), strtotime('29-Dec-2013'), 0 ), FALSE ); //Sun

		$ot_period_dates = $cp->getOverTimePeriodDates( strtotime('30-Dec-2013'), 2, strtotime('29-Dec-2013'), 0 );
		$this->assertEquals( $ot_period_dates['start_date'], TTDate::getBeginDayEpoch( strtotime('29-Dec-2013') ) ); //Sun
		$this->assertEquals( $ot_period_dates['end_date'], TTDate::getEndDayEpoch( strtotime('11-Jan-2014') ) ); //Mon
		$this->assertEquals( $ot_period_dates['is_first_week'], TRUE );
		$this->assertEquals( $ot_period_dates['is_last_week'], FALSE );
//		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('30-Dec-2013'), strtotime('29-Dec-2013'), 0 ), FALSE );

		$ot_period_dates = $cp->getOverTimePeriodDates( strtotime('31-Dec-2013'), 2, strtotime('29-Dec-2013'), 0 );
		$this->assertEquals( $ot_period_dates['start_date'], TTDate::getBeginDayEpoch( strtotime('29-Dec-2013') ) ); //Sun
		$this->assertEquals( $ot_period_dates['end_date'], TTDate::getEndDayEpoch( strtotime('11-Jan-2014') ) ); //Mon
		$this->assertEquals( $ot_period_dates['is_first_week'], TRUE );
		$this->assertEquals( $ot_period_dates['is_last_week'], FALSE );
//		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('31-Dec-2013'), strtotime('29-Dec-2013'), 0 ), FALSE );

		$ot_period_dates = $cp->getOverTimePeriodDates( strtotime('01-Jan-2014'), 2, strtotime('29-Dec-2013'), 0 );
		$this->assertEquals( $ot_period_dates['start_date'], TTDate::getBeginDayEpoch( strtotime('29-Dec-2013') ) ); //Sun
		$this->assertEquals( $ot_period_dates['end_date'], TTDate::getEndDayEpoch( strtotime('11-Jan-2014') ) ); //Mon
		$this->assertEquals( $ot_period_dates['is_first_week'], TRUE );
		$this->assertEquals( $ot_period_dates['is_last_week'], FALSE );
//		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('01-Jan-2014'), strtotime('29-Dec-2013'), 0 ), FALSE );

		$ot_period_dates = $cp->getOverTimePeriodDates( strtotime('02-Jan-2014'), 2, strtotime('29-Dec-2013'), 0 );
		$this->assertEquals( $ot_period_dates['start_date'], TTDate::getBeginDayEpoch( strtotime('29-Dec-2013') ) ); //Sun
		$this->assertEquals( $ot_period_dates['end_date'], TTDate::getEndDayEpoch( strtotime('11-Jan-2014') ) ); //Mon
		$this->assertEquals( $ot_period_dates['is_first_week'], TRUE );
		$this->assertEquals( $ot_period_dates['is_last_week'], FALSE );
//		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('02-Jan-2014'), strtotime('29-Dec-2013'), 0 ), FALSE );

		$ot_period_dates = $cp->getOverTimePeriodDates( strtotime('03-Jan-2014'), 2, strtotime('29-Dec-2013'), 0 );
		$this->assertEquals( $ot_period_dates['start_date'], TTDate::getBeginDayEpoch( strtotime('29-Dec-2013') ) ); //Sun
		$this->assertEquals( $ot_period_dates['end_date'], TTDate::getEndDayEpoch( strtotime('11-Jan-2014') ) ); //Mon
		$this->assertEquals( $ot_period_dates['is_first_week'], TRUE );
		$this->assertEquals( $ot_period_dates['is_last_week'], FALSE );
//		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('03-Jan-2014'), strtotime('29-Dec-2013'), 0 ), FALSE );

		$ot_period_dates = $cp->getOverTimePeriodDates( strtotime('04-Jan-2014'), 2, strtotime('29-Dec-2013'), 0 );
		$this->assertEquals( $ot_period_dates['start_date'], TTDate::getBeginDayEpoch( strtotime('29-Dec-2013') ) ); //Sun
		$this->assertEquals( $ot_period_dates['end_date'], TTDate::getEndDayEpoch( strtotime('11-Jan-2014') ) ); //Mon
		$this->assertEquals( $ot_period_dates['is_first_week'], TRUE );
		$this->assertEquals( $ot_period_dates['is_last_week'], FALSE );
//		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('04-Jan-2014'), strtotime('29-Dec-2013'), 0 ), FALSE );

		$ot_period_dates = $cp->getOverTimePeriodDates( strtotime('05-Jan-2014'), 2, strtotime('29-Dec-2013'), 0 );
		$this->assertEquals( $ot_period_dates['start_date'], TTDate::getBeginDayEpoch( strtotime('29-Dec-2013') ) ); //Sun
		$this->assertEquals( $ot_period_dates['end_date'], TTDate::getEndDayEpoch( strtotime('11-Jan-2014') ) ); //Mon
		$this->assertEquals( $ot_period_dates['is_first_week'], FALSE );
		$this->assertEquals( $ot_period_dates['is_last_week'], TRUE );
//		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('05-Jan-2014'), strtotime('29-Dec-2013'), 0 ), TRUE );

		$ot_period_dates = $cp->getOverTimePeriodDates( strtotime('06-Jan-2014'), 2, strtotime('29-Dec-2013'), 0 );
		$this->assertEquals( $ot_period_dates['start_date'], TTDate::getBeginDayEpoch( strtotime('29-Dec-2013') ) ); //Sun
		$this->assertEquals( $ot_period_dates['end_date'], TTDate::getEndDayEpoch( strtotime('11-Jan-2014') ) ); //Mon
		$this->assertEquals( $ot_period_dates['is_first_week'], FALSE );
		$this->assertEquals( $ot_period_dates['is_last_week'], TRUE );
//		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('06-Jan-2014'), strtotime('29-Dec-2013'), 0 ), TRUE );

		$ot_period_dates = $cp->getOverTimePeriodDates( strtotime('07-Jan-2014'), 2, strtotime('29-Dec-2013'), 0 );
		$this->assertEquals( $ot_period_dates['start_date'], TTDate::getBeginDayEpoch( strtotime('29-Dec-2013') ) ); //Sun
		$this->assertEquals( $ot_period_dates['end_date'], TTDate::getEndDayEpoch( strtotime('11-Jan-2014') ) ); //Mon
		$this->assertEquals( $ot_period_dates['is_first_week'], FALSE );
		$this->assertEquals( $ot_period_dates['is_last_week'], TRUE );
//		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('07-Jan-2014'), strtotime('29-Dec-2013'), 0 ), TRUE );

		$ot_period_dates = $cp->getOverTimePeriodDates( strtotime('08-Jan-2014'), 2, strtotime('29-Dec-2013'), 0 );
		$this->assertEquals( $ot_period_dates['start_date'], TTDate::getBeginDayEpoch( strtotime('29-Dec-2013') ) ); //Sun
		$this->assertEquals( $ot_period_dates['end_date'], TTDate::getEndDayEpoch( strtotime('11-Jan-2014') ) ); //Mon
		$this->assertEquals( $ot_period_dates['is_first_week'], FALSE );
		$this->assertEquals( $ot_period_dates['is_last_week'], TRUE );
//		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('08-Jan-2014'), strtotime('29-Dec-2013'), 0 ), TRUE );

		$ot_period_dates = $cp->getOverTimePeriodDates( strtotime('09-Jan-2014'), 2, strtotime('29-Dec-2013'), 0 );
		$this->assertEquals( $ot_period_dates['start_date'], TTDate::getBeginDayEpoch( strtotime('29-Dec-2013') ) ); //Sun
		$this->assertEquals( $ot_period_dates['end_date'], TTDate::getEndDayEpoch( strtotime('11-Jan-2014') ) ); //Mon
		$this->assertEquals( $ot_period_dates['is_first_week'], FALSE );
		$this->assertEquals( $ot_period_dates['is_last_week'], TRUE );
//		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('09-Jan-2014'), strtotime('29-Dec-2013'), 0 ), TRUE );

		$ot_period_dates = $cp->getOverTimePeriodDates( strtotime('10-Jan-2014'), 2, strtotime('29-Dec-2013'), 0 );
		$this->assertEquals( $ot_period_dates['start_date'], TTDate::getBeginDayEpoch( strtotime('29-Dec-2013') ) ); //Sun
		$this->assertEquals( $ot_period_dates['end_date'], TTDate::getEndDayEpoch( strtotime('11-Jan-2014') ) ); //Mon
		$this->assertEquals( $ot_period_dates['is_first_week'], FALSE );
		$this->assertEquals( $ot_period_dates['is_last_week'], TRUE );
//		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('10-Jan-2014'), strtotime('29-Dec-2013'), 0 ), TRUE );

		$ot_period_dates = $cp->getOverTimePeriodDates( strtotime('11-Jan-2014'), 2, strtotime('29-Dec-2013'), 0 );
		$this->assertEquals( $ot_period_dates['start_date'], TTDate::getBeginDayEpoch( strtotime('29-Dec-2013') ) ); //Sun
		$this->assertEquals( $ot_period_dates['end_date'], TTDate::getEndDayEpoch( strtotime('11-Jan-2014') ) ); //Mon
		$this->assertEquals( $ot_period_dates['is_first_week'], FALSE );
		$this->assertEquals( $ot_period_dates['is_last_week'], TRUE );
//		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('11-Jan-2014'), strtotime('29-Dec-2013'), 0 ), TRUE );



		$ot_period_dates = $cp->getOverTimePeriodDates( strtotime('29-Dec-2013'), 2, strtotime('01-Jan-2014'), 0 );
		$this->assertEquals( $ot_period_dates['start_date'], TTDate::getBeginDayEpoch( strtotime('29-Dec-2013') ) ); //Sun
		$this->assertEquals( $ot_period_dates['end_date'], TTDate::getEndDayEpoch( strtotime('11-Jan-2014') ) ); //Mon
		$this->assertEquals( $ot_period_dates['is_first_week'], TRUE );
		$this->assertEquals( $ot_period_dates['is_last_week'], FALSE );
//		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('29-Dec-2013'), strtotime('01-Jan-2014'), 0 ), FALSE ); //Sun

		$ot_period_dates = $cp->getOverTimePeriodDates( strtotime('30-Dec-2013'), 2, strtotime('01-Jan-2014'), 0 );
		$this->assertEquals( $ot_period_dates['start_date'], TTDate::getBeginDayEpoch( strtotime('29-Dec-2013') ) ); //Sun
		$this->assertEquals( $ot_period_dates['end_date'], TTDate::getEndDayEpoch( strtotime('11-Jan-2014') ) ); //Mon
		$this->assertEquals( $ot_period_dates['is_first_week'], TRUE );
		$this->assertEquals( $ot_period_dates['is_last_week'], FALSE );
//		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('30-Dec-2013'), strtotime('01-Jan-2014'), 0 ), FALSE );

		$ot_period_dates = $cp->getOverTimePeriodDates( strtotime('31-Dec-2013'), 2, strtotime('01-Jan-2014'), 0 );
		$this->assertEquals( $ot_period_dates['start_date'], TTDate::getBeginDayEpoch( strtotime('29-Dec-2013') ) ); //Sun
		$this->assertEquals( $ot_period_dates['end_date'], TTDate::getEndDayEpoch( strtotime('11-Jan-2014') ) ); //Mon
		$this->assertEquals( $ot_period_dates['is_first_week'], TRUE );
		$this->assertEquals( $ot_period_dates['is_last_week'], FALSE );
//		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('31-Dec-2013'), strtotime('01-Jan-2014'), 0 ), FALSE );

		$ot_period_dates = $cp->getOverTimePeriodDates( strtotime('01-Jan-2014'), 2, strtotime('01-Jan-2014'), 0 );
		$this->assertEquals( $ot_period_dates['start_date'], TTDate::getBeginDayEpoch( strtotime('29-Dec-2013') ) ); //Sun
		$this->assertEquals( $ot_period_dates['end_date'], TTDate::getEndDayEpoch( strtotime('11-Jan-2014') ) ); //Mon
		$this->assertEquals( $ot_period_dates['is_first_week'], TRUE );
		$this->assertEquals( $ot_period_dates['is_last_week'], FALSE );
//		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('01-Jan-2014'), strtotime('01-Jan-2014'), 0 ), FALSE );

		$ot_period_dates = $cp->getOverTimePeriodDates( strtotime('02-Jan-2014'), 2, strtotime('01-Jan-2014'), 0 );
		$this->assertEquals( $ot_period_dates['start_date'], TTDate::getBeginDayEpoch( strtotime('29-Dec-2013') ) ); //Sun
		$this->assertEquals( $ot_period_dates['end_date'], TTDate::getEndDayEpoch( strtotime('11-Jan-2014') ) ); //Mon
		$this->assertEquals( $ot_period_dates['is_first_week'], TRUE );
		$this->assertEquals( $ot_period_dates['is_last_week'], FALSE );
//		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('02-Jan-2014'), strtotime('01-Jan-2014'), 0 ), FALSE );

		$ot_period_dates = $cp->getOverTimePeriodDates( strtotime('03-Jan-2014'), 2, strtotime('01-Jan-2014'), 0 );
		$this->assertEquals( $ot_period_dates['start_date'], TTDate::getBeginDayEpoch( strtotime('29-Dec-2013') ) ); //Sun
		$this->assertEquals( $ot_period_dates['end_date'], TTDate::getEndDayEpoch( strtotime('11-Jan-2014') ) ); //Mon
		$this->assertEquals( $ot_period_dates['is_first_week'], TRUE );
		$this->assertEquals( $ot_period_dates['is_last_week'], FALSE );
//		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('03-Jan-2014'), strtotime('01-Jan-2014'), 0 ), FALSE );

		$ot_period_dates = $cp->getOverTimePeriodDates( strtotime('04-Jan-2014'), 2, strtotime('01-Jan-2014'), 0 );
		$this->assertEquals( $ot_period_dates['start_date'], TTDate::getBeginDayEpoch( strtotime('29-Dec-2013') ) ); //Sun
		$this->assertEquals( $ot_period_dates['end_date'], TTDate::getEndDayEpoch( strtotime('11-Jan-2014') ) ); //Mon
		$this->assertEquals( $ot_period_dates['is_first_week'], TRUE );
		$this->assertEquals( $ot_period_dates['is_last_week'], FALSE );
//		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('04-Jan-2014'), strtotime('01-Jan-2014'), 0 ), FALSE );

		$ot_period_dates = $cp->getOverTimePeriodDates( strtotime('05-Jan-2014'), 2, strtotime('01-Jan-2014'), 0 );
		$this->assertEquals( $ot_period_dates['start_date'], TTDate::getBeginDayEpoch( strtotime('29-Dec-2013') ) ); //Sun
		$this->assertEquals( $ot_period_dates['end_date'], TTDate::getEndDayEpoch( strtotime('11-Jan-2014') ) ); //Mon
		$this->assertEquals( $ot_period_dates['is_first_week'], FALSE );
		$this->assertEquals( $ot_period_dates['is_last_week'], TRUE );
//		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('05-Jan-2014'), strtotime('01-Jan-2014'), 0 ), TRUE );

		$ot_period_dates = $cp->getOverTimePeriodDates( strtotime('06-Jan-2014'), 2, strtotime('01-Jan-2014'), 0 );
		$this->assertEquals( $ot_period_dates['start_date'], TTDate::getBeginDayEpoch( strtotime('29-Dec-2013') ) ); //Sun
		$this->assertEquals( $ot_period_dates['end_date'], TTDate::getEndDayEpoch( strtotime('11-Jan-2014') ) ); //Mon
		$this->assertEquals( $ot_period_dates['is_first_week'], FALSE );
		$this->assertEquals( $ot_period_dates['is_last_week'], TRUE );
//		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('06-Jan-2014'), strtotime('01-Jan-2014'), 0 ), TRUE );

		$ot_period_dates = $cp->getOverTimePeriodDates( strtotime('07-Jan-2014'), 2, strtotime('01-Jan-2014'), 0 );
		$this->assertEquals( $ot_period_dates['start_date'], TTDate::getBeginDayEpoch( strtotime('29-Dec-2013') ) ); //Sun
		$this->assertEquals( $ot_period_dates['end_date'], TTDate::getEndDayEpoch( strtotime('11-Jan-2014') ) ); //Mon
		$this->assertEquals( $ot_period_dates['is_first_week'], FALSE );
		$this->assertEquals( $ot_period_dates['is_last_week'], TRUE );
//		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('07-Jan-2014'), strtotime('01-Jan-2014'), 0 ), TRUE );

		$ot_period_dates = $cp->getOverTimePeriodDates( strtotime('08-Jan-2014'), 2, strtotime('01-Jan-2014'), 0 );
		$this->assertEquals( $ot_period_dates['start_date'], TTDate::getBeginDayEpoch( strtotime('29-Dec-2013') ) ); //Sun
		$this->assertEquals( $ot_period_dates['end_date'], TTDate::getEndDayEpoch( strtotime('11-Jan-2014') ) ); //Mon
		$this->assertEquals( $ot_period_dates['is_first_week'], FALSE );
		$this->assertEquals( $ot_period_dates['is_last_week'], TRUE );
//		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('08-Jan-2014'), strtotime('01-Jan-2014'), 0 ), TRUE );

		$ot_period_dates = $cp->getOverTimePeriodDates( strtotime('09-Jan-2014'), 2, strtotime('01-Jan-2014'), 0 );
		$this->assertEquals( $ot_period_dates['start_date'], TTDate::getBeginDayEpoch( strtotime('29-Dec-2013') ) ); //Sun
		$this->assertEquals( $ot_period_dates['end_date'], TTDate::getEndDayEpoch( strtotime('11-Jan-2014') ) ); //Mon
		$this->assertEquals( $ot_period_dates['is_first_week'], FALSE );
		$this->assertEquals( $ot_period_dates['is_last_week'], TRUE );
//		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('09-Jan-2014'), strtotime('01-Jan-2014'), 0 ), TRUE );

		$ot_period_dates = $cp->getOverTimePeriodDates( strtotime('10-Jan-2014'), 2, strtotime('01-Jan-2014'), 0 );
		$this->assertEquals( $ot_period_dates['start_date'], TTDate::getBeginDayEpoch( strtotime('29-Dec-2013') ) ); //Sun
		$this->assertEquals( $ot_period_dates['end_date'], TTDate::getEndDayEpoch( strtotime('11-Jan-2014') ) ); //Mon
		$this->assertEquals( $ot_period_dates['is_first_week'], FALSE );
		$this->assertEquals( $ot_period_dates['is_last_week'], TRUE );
//		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('10-Jan-2014'), strtotime('01-Jan-2014'), 0 ), TRUE );

		$ot_period_dates = $cp->getOverTimePeriodDates( strtotime('11-Jan-2014'), 2, strtotime('01-Jan-2014'), 0 );
		$this->assertEquals( $ot_period_dates['start_date'], TTDate::getBeginDayEpoch( strtotime('29-Dec-2013') ) ); //Sun
		$this->assertEquals( $ot_period_dates['end_date'], TTDate::getEndDayEpoch( strtotime('11-Jan-2014') ) ); //Mon
		$this->assertEquals( $ot_period_dates['is_first_week'], FALSE );
		$this->assertEquals( $ot_period_dates['is_last_week'], TRUE );
//		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('11-Jan-2014'), strtotime('01-Jan-2014'), 0 ), TRUE );

		$ot_period_dates = $cp->getOverTimePeriodDates( strtotime('01-May-2016'), 2, strtotime('28-Dec-2013'), 0 );
		$this->assertEquals( $ot_period_dates['start_date'], TTDate::getBeginDayEpoch( strtotime('24-Apr-2016') ) ); //Sat
		$this->assertEquals( $ot_period_dates['end_date'], TTDate::getEndDayEpoch( strtotime('07-May-2016') ) ); //Fri
		$this->assertEquals( $ot_period_dates['is_first_week'], FALSE );
		$this->assertEquals( $ot_period_dates['is_last_week'], TRUE );
		//$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('01-May-2016'), strtotime('28-Dec-2013'), 0 ), TRUE ); //Sat

		$ot_period_dates = $cp->getOverTimePeriodDates( strtotime('01-May-2016'), 2, strtotime('29-Dec-2013'), 0 );
		$this->assertEquals( $ot_period_dates['start_date'], TTDate::getBeginDayEpoch( strtotime('01-May-2016') ) ); //Sun
		$this->assertEquals( $ot_period_dates['end_date'], TTDate::getEndDayEpoch( strtotime('14-May-2016') ) ); //Sat
		$this->assertEquals( $ot_period_dates['is_first_week'], TRUE );
		$this->assertEquals( $ot_period_dates['is_last_week'], FALSE );
		//$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('01-May-2016'), strtotime('29-Dec-2013'), 0 ), FALSE ); //Sun

		$ot_period_dates = $cp->getOverTimePeriodDates( strtotime('01-May-2016'), 2, strtotime('01-Jan-2014'), 0 );
		$this->assertEquals( $ot_period_dates['start_date'], TTDate::getBeginDayEpoch( strtotime('01-May-2016') ) ); //Wed
		$this->assertEquals( $ot_period_dates['end_date'], TTDate::getEndDayEpoch( strtotime('14-May-2016') ) ); //Tue
		$this->assertEquals( $ot_period_dates['is_first_week'], TRUE );
		$this->assertEquals( $ot_period_dates['is_last_week'], FALSE );
		//$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('01-May-2016'), strtotime('01-Jan-2014'), 0 ), FALSE ); //Wed



//		//Test 53 week year.
		$ot_period_dates = $cp->getOverTimePeriodDates( strtotime('29-Dec-2014'), 2, strtotime('29-Dec-2014'), 0 );
		$this->assertEquals( $ot_period_dates['start_date'], TTDate::getBeginDayEpoch( strtotime('28-Dec-14') ) );
		$this->assertEquals( $ot_period_dates['end_date'], TTDate::getEndDayEpoch( strtotime('10-Jan-15') ) );
		$this->assertEquals( $ot_period_dates['is_first_week'], TRUE );
		$this->assertEquals( $ot_period_dates['is_last_week'], FALSE );
//		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('29-Dec-2014'), strtotime('29-Dec-2014'), 0 ), FALSE ); //Sun: Week 1

		$ot_period_dates = $cp->getOverTimePeriodDates( strtotime('05-Jan-2015'), 2, strtotime('29-Dec-2014'), 0 );
		$this->assertEquals( $ot_period_dates['start_date'], TTDate::getBeginDayEpoch( strtotime('28-Dec-14') ) );
		$this->assertEquals( $ot_period_dates['end_date'], TTDate::getEndDayEpoch( strtotime('10-Jan-15') ) );
		$this->assertEquals( $ot_period_dates['is_first_week'], FALSE );
		$this->assertEquals( $ot_period_dates['is_last_week'], TRUE );
//		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('05-Jan-2015'), strtotime('29-Dec-2014'), 0 ), TRUE ); //Sun

		$ot_period_dates = $cp->getOverTimePeriodDates( strtotime('12-Jan-2015'), 2, strtotime('29-Dec-2014'), 0 );
		$this->assertEquals( $ot_period_dates['start_date'], TTDate::getBeginDayEpoch( strtotime('11-Jan-15') ) );
		$this->assertEquals( $ot_period_dates['end_date'], TTDate::getEndDayEpoch( strtotime('24-Jan-15') ) );
		$this->assertEquals( $ot_period_dates['is_first_week'], TRUE );
		$this->assertEquals( $ot_period_dates['is_last_week'], FALSE );
//		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('12-Jan-2015'), strtotime('29-Dec-2014'), 0 ), FALSE ); //Sun

		$ot_period_dates = $cp->getOverTimePeriodDates( strtotime('26-Jan-2015'), 2, strtotime('29-Dec-2014'), 0 );
		$this->assertEquals( $ot_period_dates['start_date'], TTDate::getBeginDayEpoch( strtotime('25-Jan-15') ) );
		$this->assertEquals( $ot_period_dates['end_date'], TTDate::getEndDayEpoch( strtotime('07-Feb-15') ) );
		$this->assertEquals( $ot_period_dates['is_first_week'], TRUE );
		$this->assertEquals( $ot_period_dates['is_last_week'], FALSE );
//		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('26-Jan-2015'), strtotime('29-Dec-2014'), 0 ), FALSE ); //Sun

		$ot_period_dates = $cp->getOverTimePeriodDates( strtotime('09-Feb-2015'), 2, strtotime('29-Dec-2014'), 0 );
		$this->assertEquals( $ot_period_dates['start_date'], TTDate::getBeginDayEpoch( strtotime('08-Feb-15') ) );
		$this->assertEquals( $ot_period_dates['end_date'], TTDate::getEndDayEpoch( strtotime('21-Feb-15') ) );
		$this->assertEquals( $ot_period_dates['is_first_week'], TRUE );
		$this->assertEquals( $ot_period_dates['is_last_week'], FALSE );
//		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('09-Feb-2015'), strtotime('29-Dec-2014'), 0 ), FALSE ); //Sun

		$ot_period_dates = $cp->getOverTimePeriodDates( strtotime('23-Feb-2015'), 2, strtotime('29-Dec-2014'), 0 );
		$this->assertEquals( $ot_period_dates['start_date'], TTDate::getBeginDayEpoch( strtotime('22-Feb-15') ) );
		$this->assertEquals( $ot_period_dates['end_date'], TTDate::getEndDayEpoch( strtotime('07-Mar-15') ) );
		$this->assertEquals( $ot_period_dates['is_first_week'], TRUE );
		$this->assertEquals( $ot_period_dates['is_last_week'], FALSE );
//		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('23-Feb-2015'), strtotime('29-Dec-2014'), 0 ), FALSE ); //Sun


		$ot_period_dates = $cp->getOverTimePeriodDates( strtotime('09-Mar-2015'), 2, strtotime('29-Dec-2014'), 0 );
		$this->assertEquals( $ot_period_dates['start_date'], TTDate::getBeginDayEpoch( strtotime('08-Mar-15') ) );
		$this->assertEquals( $ot_period_dates['end_date'], TTDate::getEndDayEpoch( strtotime('21-Mar-15') ) );
		$this->assertEquals( $ot_period_dates['is_first_week'], TRUE );
		$this->assertEquals( $ot_period_dates['is_last_week'], FALSE );
//		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('09-Mar-2015'), strtotime('29-Dec-2014'), 0 ), FALSE ); //Sun

		$ot_period_dates = $cp->getOverTimePeriodDates( strtotime('23-Mar-2015'), 2, strtotime('29-Dec-2014'), 0 );
		$this->assertEquals( $ot_period_dates['start_date'], TTDate::getBeginDayEpoch( strtotime('22-Mar-15') ) );
		$this->assertEquals( $ot_period_dates['end_date'], TTDate::getEndDayEpoch( strtotime('04-Apr-15') ) );
		$this->assertEquals( $ot_period_dates['is_first_week'], TRUE );
		$this->assertEquals( $ot_period_dates['is_last_week'], FALSE );
//		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('23-Mar-2015'), strtotime('29-Dec-2014'), 0 ), FALSE ); //Sun


		$ot_period_dates = $cp->getOverTimePeriodDates( strtotime('14-Dec-2015'), 2, strtotime('29-Dec-2014'), 0 );
		$this->assertEquals( $ot_period_dates['start_date'], TTDate::getBeginDayEpoch( strtotime('13-Dec-15') ) );
		$this->assertEquals( $ot_period_dates['end_date'], TTDate::getEndDayEpoch( strtotime('26-Dec-15') ) );
		$this->assertEquals( $ot_period_dates['is_first_week'], TRUE );
		$this->assertEquals( $ot_period_dates['is_last_week'], FALSE );
//		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('14-Dec-2015'), strtotime('29-Dec-2014'), 0 ), FALSE ); //Sun: Week 51

		$ot_period_dates = $cp->getOverTimePeriodDates( strtotime('21-Dec-2015'), 2, strtotime('29-Dec-2014'), 0 );
		$this->assertEquals( $ot_period_dates['start_date'], TTDate::getBeginDayEpoch( strtotime('13-Dec-15') ) );
		$this->assertEquals( $ot_period_dates['end_date'], TTDate::getEndDayEpoch( strtotime('26-Dec-15') ) );
		$this->assertEquals( $ot_period_dates['is_first_week'], FALSE );
		$this->assertEquals( $ot_period_dates['is_last_week'], TRUE );
//		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('21-Dec-2015'), strtotime('29-Dec-2014'), 0 ), TRUE ); //Sun: Week 52

		$ot_period_dates = $cp->getOverTimePeriodDates( strtotime('28-Dec-2015'), 2, strtotime('29-Dec-2014'), 0 );
		$this->assertEquals( $ot_period_dates['start_date'], TTDate::getBeginDayEpoch( strtotime('27-Dec-15') ) );
		$this->assertEquals( $ot_period_dates['end_date'], TTDate::getEndDayEpoch( strtotime('09-Jan-16') ) );
		$this->assertEquals( $ot_period_dates['is_first_week'], TRUE );
		$this->assertEquals( $ot_period_dates['is_last_week'], FALSE );
//		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('28-Dec-2015'), strtotime('29-Dec-2014'), 0 ), FALSE ); //Sun: Week 53

		$ot_period_dates = $cp->getOverTimePeriodDates( strtotime('01-Jan-2014'), 2, strtotime('01-Jan-2014'), 0 );
		$this->assertEquals( $ot_period_dates['start_date'], TTDate::getBeginDayEpoch( strtotime('29-Dec-2013') ) );
		$this->assertEquals( $ot_period_dates['end_date'], TTDate::getEndDayEpoch( strtotime('11-Jan-2014') ) );
		$this->assertEquals( $ot_period_dates['is_first_week'], TRUE );
		$this->assertEquals( $ot_period_dates['is_last_week'], FALSE );
//		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('01-Jan-2014'), strtotime('01-Jan-2014'), 0 ), FALSE ); //Sun

		$ot_period_dates = $cp->getOverTimePeriodDates( strtotime('06-Jan-2014'), 2, strtotime('01-Jan-2014'), 0 );
		$this->assertEquals( $ot_period_dates['start_date'], TTDate::getBeginDayEpoch( strtotime('29-Dec-2013') ) );
		$this->assertEquals( $ot_period_dates['end_date'], TTDate::getEndDayEpoch( strtotime('11-Jan-2014') ) );
		$this->assertEquals( $ot_period_dates['is_first_week'], FALSE );
		$this->assertEquals( $ot_period_dates['is_last_week'], TRUE );
//		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('06-Jan-2014'), strtotime('01-Jan-2014'), 0 ), TRUE ); //Sun

		$ot_period_dates = $cp->getOverTimePeriodDates( strtotime('12-Jan-2014'), 2, strtotime('01-Jan-2014'), 0 );
		$this->assertEquals( $ot_period_dates['start_date'], TTDate::getBeginDayEpoch( strtotime('12-Jan-2014') ) );
		$this->assertEquals( $ot_period_dates['end_date'], TTDate::getEndDayEpoch( strtotime('25-Jan-2014') ) );
		$this->assertEquals( $ot_period_dates['is_first_week'], TRUE );
		$this->assertEquals( $ot_period_dates['is_last_week'], FALSE );
//		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('12-Jan-2014'), strtotime('01-Jan-2014'), 0 ), FALSE ); //Sun

		$ot_period_dates = $cp->getOverTimePeriodDates( strtotime('20-Jan-2014'), 2, strtotime('01-Jan-2014'), 0 );
		$this->assertEquals( $ot_period_dates['start_date'], TTDate::getBeginDayEpoch( strtotime('12-Jan-2014') ) );
		$this->assertEquals( $ot_period_dates['end_date'], TTDate::getEndDayEpoch( strtotime('25-Jan-2014') ) );
		$this->assertEquals( $ot_period_dates['is_first_week'], FALSE );
		$this->assertEquals( $ot_period_dates['is_last_week'], TRUE );
//		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('20-Jan-2014'), strtotime('01-Jan-2014'), 0 ), TRUE ); //Sun

		$ot_period_dates = $cp->getOverTimePeriodDates( strtotime('03-Feb-2014'), 2, strtotime('01-Jan-2014'), 0 );
		$this->assertEquals( $ot_period_dates['start_date'], TTDate::getBeginDayEpoch( strtotime('26-Jan-2014') ) );
		$this->assertEquals( $ot_period_dates['end_date'], TTDate::getEndDayEpoch( strtotime('08-Feb-2014') ) );
		$this->assertEquals( $ot_period_dates['is_first_week'], FALSE );
		$this->assertEquals( $ot_period_dates['is_last_week'], TRUE );
//		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('03-Feb-2014'), strtotime('01-Jan-2014'), 0 ), TRUE ); //Sun

		$ot_period_dates = $cp->getOverTimePeriodDates( strtotime('17-Feb-2014'), 2, strtotime('01-Jan-2014'), 0 );
		$this->assertEquals( $ot_period_dates['start_date'], TTDate::getBeginDayEpoch( strtotime('09-Feb-2014') ) );
		$this->assertEquals( $ot_period_dates['end_date'], TTDate::getEndDayEpoch( strtotime('22-Feb-2014') ) );
		$this->assertEquals( $ot_period_dates['is_first_week'], FALSE );
		$this->assertEquals( $ot_period_dates['is_last_week'], TRUE );
//		$this->assertEquals(  $cp->isSecondBiWeeklyOverTimeWeek( strtotime('17-Feb-2014'), strtotime('01-Jan-2014'), 0 ), TRUE ); //Sun

		$retval = FALSE;
		$anchor_date = TTDate::getMiddleDayEpoch( strtotime('29-Dec-2014') );
		$x = 0;
		for( $i = $anchor_date; $i <= TTDate::getMiddleDayEpoch( strtotime('29-Dec-2015') ); $i += ( 86400 * 7 ) ) {
			$x++; //Run at beginning so the counter essentially starts at 1.
//			$this->assertEquals( $cp->isSecondBiWeeklyOverTimeWeek( $i, $anchor_date, 0 ), $retval );
//			$retval = !$retval; //Swap

			$ot_period_dates = $cp->getOverTimePeriodDates( $i, 2, $anchor_date, 0 );
			$this->assertEquals( $ot_period_dates['is_last_week'], $retval );
			$retval = !$retval; //Swap
		}
		$this->assertEquals( $x, 53 );

		$retval = FALSE;
		$anchor_date = TTDate::getMiddleDayEpoch( strtotime('29-Dec-2014') );
		$x = 0;
		for( $i = $anchor_date; $i <= TTDate::getMiddleDayEpoch( strtotime('29-Dec-2025') ); $i += ( 86400 * 7 ) ) {
			$x++; //Run at beginning so the counter essentially starts at 1.
			//$this->assertEquals( $cp->isSecondBiWeeklyOverTimeWeek( $i, $anchor_date, 0 ), $retval );
			//$retval = !$retval; //Swap

			$ot_period_dates = $cp->getOverTimePeriodDates( $i, 2, $anchor_date, 0 );
			$this->assertEquals( $ot_period_dates['is_last_week'], $retval );
			$retval = !$retval; //Swap
		}
		$this->assertEquals( $x, 575 );
	}
	/**
	 * @group OvertimePolicy_testBiWeeklyOverTimePolicyA
	 */
	function testBiWeeklyOverTimePolicyA() {
		global $dd;

		//Test reaching the biweekly overtime in the first week, and part of it going into the second.
		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 200 ); //OT1.5
		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 210 ); //OT2.0
		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 220 ); //OT2.5

		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 300, $policy_ids['pay_formula_policy'][0] );
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 310, $policy_ids['pay_formula_policy'][1] );
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 320, $policy_ids['pay_formula_policy'][2] );

		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 300, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][0] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 310, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][1] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 320, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][2] );
		//print_r($policy_ids['overtime']);

		//Create Policy Group
		$dd->createPolicyGroup( 	$this->company_id,
									NULL, //Meal
									NULL, //Exception
									NULL, //Holiday
									$policy_ids['overtime'], //OT
									NULL, //Premium
									NULL, //Round
									array($this->user_id), //Users
									NULL, //Break
									NULL, //Accrual
									NULL, //Expense
									NULL, //Absence
									array($this->policy_ids['regular'][12]) //Regular
									);

		//Start two weeks ago...
		$start_epoch = $date_epoch = TTDate::getBeginWeekEpoch( time() );

		$current_pay_period_obj = $this->getCurrentPayPeriod( $date_epoch );
		if ( is_object($current_pay_period_obj) ) {
			$date_stamp = TTDate::getDate('DATE', $current_pay_period_obj->getStartDate() );
			$start_epoch = $date_epoch = TTDate::getBeginDayEpoch( $current_pay_period_obj->getStartDate() );
		} else {
			$date_stamp = TTDate::getDate('DATE', $date_epoch );
		}
		Debug::text('Using date stamp: '. TTDate::getDate('DATE+TIME', $date_stamp ), __FILE__, __LINE__, __METHOD__, 10);

		//
		//Day of Week: 1
		//
		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (12 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 2 );
		//
		//Day of Week: 2
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( $start_epoch ) + (1 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (12 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 2 );

		//
		//Day of Week: 3
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( $start_epoch ) + (2 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (12 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 2 );

		//
		//Day of Week: 4
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( $start_epoch ) + (3 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (12 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 2 );

		//
		//Day of Week: 5
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( $start_epoch ) + (4 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (12 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 2 );



		//
		//Day of Week: 1 - Beginning of next week...
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( $start_epoch ) + (5 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (12 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 2 );

		//
		//Day of Week: 2
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( $start_epoch ) + (6 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (8 * 3600) );
		//Overtime 1
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 30 ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $policy_ids['pay_code'][0] );
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (4 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 3 );

		//
		//Day of Week: 3
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( $start_epoch ) + (7 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );

		//Overtime policies are sorted by id desc, so the we have to reverse the order.
		//Overtime 2
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 30 ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $policy_ids['pay_code'][2] );
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (10 * 3600) );
		//Overtime 3
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 30 ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $policy_ids['pay_code'][1] );
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (2 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 3 );

		//
		//Day of Week: 4
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( $start_epoch ) + (8 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );

		//Overtime policies are sorted by id desc, so the we have to reverse the order.
		//Overtime 3
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 30 ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $policy_ids['pay_code'][2] );
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (12 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 2 );
		return TRUE;
	}

	/**
	 * @group OvertimePolicy_testBiWeeklyOverTimePolicyB
	 */
	function testBiWeeklyOverTimePolicyB() {
		global $dd;

		//Test reaching the biweekly overtime in the first week, and part of it going into the second.
		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 200 ); //OT1.5
		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 210 ); //OT2.0
		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 220 ); //OT2.5

		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 300, $policy_ids['pay_formula_policy'][0] );
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 310, $policy_ids['pay_formula_policy'][1] );
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 320, $policy_ids['pay_formula_policy'][2] );

		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 300, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][0] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 310, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][1] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 320, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][2] );
		//print_r($policy_ids['overtime']);

		//Create Policy Group
		$dd->createPolicyGroup( 	$this->company_id,
									NULL, //Meal
									NULL, //Exception
									NULL, //Holiday
									$policy_ids['overtime'], //OT
									NULL, //Premium
									NULL, //Round
									array($this->user_id), //Users
									NULL, //Break
									NULL, //Accrual
									NULL, //Expense
									NULL, //Absence
									array($this->policy_ids['regular'][12]) //Regular
									);

		//Start two weeks ago...
		$start_epoch = $date_epoch = TTDate::getBeginWeekEpoch( time() );

		$current_pay_period_obj = $this->getCurrentPayPeriod( $date_epoch );
		if ( is_object($current_pay_period_obj) ) {
			$date_stamp = TTDate::getDate('DATE', $current_pay_period_obj->getStartDate() );
			$start_epoch = $date_epoch = TTDate::getBeginDayEpoch( $current_pay_period_obj->getStartDate() );
		} else {
			$date_stamp = TTDate::getDate('DATE', $date_epoch );
		}
		Debug::text('Using date stamp: '. TTDate::getDate('DATE+TIME', $date_stamp ), __FILE__, __LINE__, __METHOD__, 10);

		//
		//Day of Week: 1
		//
		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 10:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (14 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (14 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 2 );

		//
		//Day of Week: 2
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( $start_epoch ) + (1 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 10:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (14 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (14 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 2 );

		//
		//Day of Week: 3
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( $start_epoch ) + (2 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 10:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (14 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (14 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 2 );

		//
		//Day of Week: 4
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( $start_epoch ) + (3 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 10:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (14 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (14 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 2 );

		//
		//Day of Week: 5
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( $start_epoch ) + (4 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 10:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (14 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (14 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 2 );

		//
		//Day of Week: 6
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( $start_epoch ) + (5 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 10:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (14 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (10 * 3600) );
		//Overtime 1
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 30 ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $policy_ids['pay_code'][0] );
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (4 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 3 );


		//
		//Day of Week: 7
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( $start_epoch ) + (6 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 10:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (14 * 3600) );
		//Overtime 2
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 30 ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $policy_ids['pay_code'][2] );
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (12 * 3600) );
		//Overtime 3
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 30 ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $policy_ids['pay_code'][1] );
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (2 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 3 );


		//
		//Day of Week: 1 - Beginning of next week...
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( $start_epoch ) + (7 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 10:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (14 * 3600) );
		//Overtime 3
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 30 ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $policy_ids['pay_code'][2] );
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (14 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 2 );

		//
		//Day of Week: 2
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( $start_epoch ) + (8 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 10:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (14 * 3600) );
		//Overtime 3
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 30 ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $policy_ids['pay_code'][2] );
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (14 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 2 );

		//
		//Day of Week: 3
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( $start_epoch ) + (9 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 10:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (14 * 3600) );
		//Overtime 3
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 30 ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $policy_ids['pay_code'][2] );
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (14 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 2 );
		return TRUE;
	}

	/**
	 * @group OvertimePolicy_testBiWeeklyOverTimePolicyC
	 */
	function testBiWeeklyOverTimePolicyC() {
		global $dd;

		//Test reaching the biweekly overtime just in the 2nd week.
		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 200 ); //OT1.5
		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 210 ); //OT2.0
		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 220 ); //OT2.5

		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 300, $policy_ids['pay_formula_policy'][0] );
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 310, $policy_ids['pay_formula_policy'][1] );
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 320, $policy_ids['pay_formula_policy'][2] );

		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 300, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][0] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 310, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][1] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 320, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][2] );
		//print_r($policy_ids['overtime']);

		//Create Policy Group
		$dd->createPolicyGroup( 	$this->company_id,
									NULL, //Meal
									NULL, //Exception
									NULL, //Holiday
									$policy_ids['overtime'], //OT
									NULL, //Premium
									NULL, //Round
									array($this->user_id), //Users
									NULL, //Break
									NULL, //Accrual
									NULL, //Expense
									NULL, //Absence
									array($this->policy_ids['regular'][12]) //Regular
									);

		//Start two weeks ago...
		$start_epoch = $date_epoch = TTDate::getBeginWeekEpoch( time() );

		$current_pay_period_obj = $this->getCurrentPayPeriod( $date_epoch );
		if ( is_object($current_pay_period_obj) ) {
			$date_stamp = TTDate::getDate('DATE', ($current_pay_period_obj->getStartDate() + (7 * 86400 + 3601)) );
			$start_epoch = $date_epoch = TTDate::getBeginDayEpoch( $current_pay_period_obj->getStartDate() + (7 * 86400 + 3601) );
		} else {
			$date_stamp = TTDate::getDate('DATE', $date_epoch );
		}
		Debug::text('Using date stamp: '. TTDate::getDate('DATE+TIME', $date_stamp ), __FILE__, __LINE__, __METHOD__, 10);

		//
		//Day of Week: 1
		//
		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 6:00AM'),
								strtotime($date_stamp.' 10:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (16 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (16 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 2 );

		//
		//Day of Week: 2
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( $start_epoch ) + (1 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 6:00AM'),
								strtotime($date_stamp.' 10:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (16 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (16 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 2 );

		//
		//Day of Week: 3
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( $start_epoch ) + (2 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 6:00AM'),
								strtotime($date_stamp.' 10:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (16 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (16 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 2 );

		//
		//Day of Week: 4
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( $start_epoch ) + (3 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 6:00AM'),
								strtotime($date_stamp.' 10:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (16 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (16 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 2 );

		//
		//Day of Week: 5
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( $start_epoch ) + (4 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 6:00AM'),
								strtotime($date_stamp.' 10:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (16 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (16 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 2 );

		//
		//Day of Week: 6
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( $start_epoch ) + (5 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 6:00AM'),
								strtotime($date_stamp.' 10:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (16 * 3600) );
		//Overtime 1
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 30 ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $policy_ids['pay_code'][2] );
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (10 * 3600) );
		//Overtime 2
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 30 ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $policy_ids['pay_code'][1] );
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (2 * 3600) );
		//Overtime 3
		$this->assertEquals( $udt_arr[$date_epoch][3]['object_type_id'], 30 ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][3]['pay_code_id'], $policy_ids['pay_code'][0] );
		$this->assertEquals( $udt_arr[$date_epoch][3]['total_time'], (4 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 4 );

		//
		//Day of Week: 7
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( $start_epoch ) + (6 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 6:00AM'),
								strtotime($date_stamp.' 10:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (16 * 3600) );
		//Overtime 2
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 30 ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $policy_ids['pay_code'][2] );
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (16 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 2 );

		return TRUE;
	}

	/**
	 * @group OvertimePolicy_testDailyAndWeeklyOverTimePolicyA
	 */
	function testDailyAndWeeklyOverTimePolicyA() {
		global $dd;

		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 200 ); //OT1.5
		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 210 ); //OT2.0
		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 220 ); //OT2.5

		//Daily
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 100, $policy_ids['pay_formula_policy'][0] );
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 110, $policy_ids['pay_formula_policy'][1] );
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 120, $policy_ids['pay_formula_policy'][2] );

		//Weekly
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 230, $policy_ids['pay_formula_policy'][0] );
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 240, $policy_ids['pay_formula_policy'][1] );
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 250, $policy_ids['pay_formula_policy'][2] );

		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 100, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][0] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 110, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][1] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 120, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][2] );

		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 230, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][3] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 240, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][4] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 250, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][5] );

		//Create Policy Group
		$dd->createPolicyGroup( 	$this->company_id,
									NULL, //Meal
									NULL, //Exception
									NULL, //Holiday
									$policy_ids['overtime'], //OT
									NULL, //Premium
									NULL, //Round
									array($this->user_id), //Users
									NULL, //Break
									NULL, //Accrual
									NULL, //Expense
									NULL, //Absence
									array($this->policy_ids['regular'][12]) //Regular
									);


		$date_epoch = TTDate::getBeginWeekEpoch( time() );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		//
		//Day of Week: 1
		//
		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (8 * 3600) );
		//Overtime1
		$this->assertEquals( $udt_arr[$date_epoch][4]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][4]['pay_code_id'], $policy_ids['pay_code'][0] );
		$this->assertEquals( $udt_arr[$date_epoch][4]['total_time'], (1 * 3600) );
		//Overtime2
		$this->assertEquals( $udt_arr[$date_epoch][3]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][3]['pay_code_id'], $policy_ids['pay_code'][1] );
		$this->assertEquals( $udt_arr[$date_epoch][3]['total_time'], (1 * 3600) );
		//Overtime3
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $policy_ids['pay_code'][2] );
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (2 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 5 );

		//
		//Day of Week: 2
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (1 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (8 * 3600) );
		//Overtime1
		$this->assertEquals( $udt_arr[$date_epoch][4]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][4]['pay_code_id'], $policy_ids['pay_code'][0] );
		$this->assertEquals( $udt_arr[$date_epoch][4]['total_time'], (1 * 3600) );
		//Overtime2
		$this->assertEquals( $udt_arr[$date_epoch][3]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][3]['pay_code_id'], $policy_ids['pay_code'][1] );
		$this->assertEquals( $udt_arr[$date_epoch][3]['total_time'], (1 * 3600) );
		//Overtime3
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $policy_ids['pay_code'][2] );
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (2 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 5 );

		//
		//Day of Week: 3
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (2 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (8 * 3600) );
		//Overtime1
		$this->assertEquals( $udt_arr[$date_epoch][4]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][4]['pay_code_id'], $policy_ids['pay_code'][0] );
		$this->assertEquals( $udt_arr[$date_epoch][4]['total_time'], (1 * 3600) );
		//Overtime2
		$this->assertEquals( $udt_arr[$date_epoch][3]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][3]['pay_code_id'], $policy_ids['pay_code'][1] );
		$this->assertEquals( $udt_arr[$date_epoch][3]['total_time'], (1 * 3600) );
		//Overtime3
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $policy_ids['pay_code'][2] );
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (2 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 5 );

		//
		//Day of Week: 4
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (3 * 86400 + 3601)) ;
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (7 * 3600) );
		//Weekly Overtime1
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $policy_ids['pay_code'][3] );
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (1 * 3600) );
		//Overtime1
		$this->assertEquals( $udt_arr[$date_epoch][5]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][5]['pay_code_id'], $policy_ids['pay_code'][0] );
		$this->assertEquals( $udt_arr[$date_epoch][5]['total_time'], (1 * 3600) );
		//Overtime2
		$this->assertEquals( $udt_arr[$date_epoch][4]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][4]['pay_code_id'], $policy_ids['pay_code'][1] );
		$this->assertEquals( $udt_arr[$date_epoch][4]['total_time'], (1 * 3600) );
		//Overtime3
		$this->assertEquals( $udt_arr[$date_epoch][3]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][3]['pay_code_id'], $policy_ids['pay_code'][2] );
		$this->assertEquals( $udt_arr[$date_epoch][3]['total_time'], (2 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 6 );

		//
		//Day of Week: 5
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (4 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//Weekly Overtime1 >39
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $policy_ids['pay_code'][4] );
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (2 * 3600) );
		//Weekly Overtime2 >31
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $policy_ids['pay_code'][3] );
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (7 * 3600) );
		//Overtime1
		//$this->assertEquals( $udt_arr[$date_epoch][5]['object_type_id'], 30 ); //Overtime
		//$this->assertEquals( $udt_arr[$date_epoch][5]['pay_code_id'], $policy_ids['pay_code'][0] );
		//$this->assertEquals( $udt_arr[$date_epoch][5]['total_time'], (1*3600) );
		//Overtime2
		$this->assertEquals( $udt_arr[$date_epoch][4]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][4]['pay_code_id'], $policy_ids['pay_code'][1] );
		$this->assertEquals( $udt_arr[$date_epoch][4]['total_time'], (1 * 3600) );
		//Overtime3
		$this->assertEquals( $udt_arr[$date_epoch][3]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][3]['pay_code_id'], $policy_ids['pay_code'][2] );
		$this->assertEquals( $udt_arr[$date_epoch][3]['total_time'], (2 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 5 );

		//
		//Day of Week: 6
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (5 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);

		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//Weekly Overtime1 >47
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $policy_ids['pay_code'][5] );
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (4 * 3600) );
		//Weekly Overtime2 >39
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $policy_ids['pay_code'][4] );
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (6 * 3600) );
		//Overtime1 >8
		//$this->assertEquals( $udt_arr[$date_epoch][5]['object_type_id'], 30 ); //Overtime
		//$this->assertEquals( $udt_arr[$date_epoch][5]['pay_code_id'], $policy_ids['pay_code'][0] );
		//$this->assertEquals( $udt_arr[$date_epoch][5]['total_time'], (1*3600) );
		//Overtime2 >9
		//$this->assertEquals( $udt_arr[$date_epoch][4]['object_type_id'], 30 ); //Overtime
		//$this->assertEquals( $udt_arr[$date_epoch][4]['pay_code_id'], $policy_ids['pay_code'][1] );
		//$this->assertEquals( $udt_arr[$date_epoch][4]['total_time'], (1*3600) );
		//Overtime3 >10
		$this->assertEquals( $udt_arr[$date_epoch][3]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][3]['pay_code_id'], $policy_ids['pay_code'][2] );
		$this->assertEquals( $udt_arr[$date_epoch][3]['total_time'], (2 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 4 );

		return TRUE;
	}

	/**
	 * @group OvertimePolicy_testDailyAndWeeklyOverTimePolicyB
	 */
	function testDailyAndWeeklyOverTimePolicyB() {
		global $dd;

		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 200 ); //OT1.5
		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 210 ); //OT2.0
		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 220 ); //OT2.5

		//Daily
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 100, $policy_ids['pay_formula_policy'][0] );
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 110, $policy_ids['pay_formula_policy'][1] );
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 120, $policy_ids['pay_formula_policy'][2] );

		//Weekly
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 230, $policy_ids['pay_formula_policy'][0] );
		//$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 240, $policy_ids['pay_formula_policy'][1] );
		//$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 250, $policy_ids['pay_formula_policy'][2] );

		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 100, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][0] );
		//$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 110, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][1] );
		//$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 120, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][2] );

		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 230, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][3] );
		//$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 240, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][4] );
		//$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 250, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][5] );

		//Create Policy Group
		$dd->createPolicyGroup( 	$this->company_id,
									NULL, //Meal
									NULL, //Exception
									NULL, //Holiday
									$policy_ids['overtime'], //OT
									NULL, //Premium
									NULL, //Round
									array($this->user_id), //Users
									NULL, //Break
									NULL, //Accrual
									NULL, //Expense
									NULL, //Absence
									array($this->policy_ids['regular'][12]) //Regular
									);


		$date_epoch = TTDate::getBeginWeekEpoch( time() );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		//
		//Day of Week: 1
		//
		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (8 * 3600) );
		//Overtime1
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $policy_ids['pay_code'][0] );
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (4 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 3 );

		//
		//Day of Week: 2
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (1 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (8 * 3600) );
		//Overtime1
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $policy_ids['pay_code'][0] );
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (4 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 3 );

		//
		//Day of Week: 3
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (2 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (8 * 3600) );
		//Overtime1
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $policy_ids['pay_code'][0] );
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (4 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 3 );

		//
		//Day of Week: 4
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (3 * 86400 + 3601)) ;
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 6:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);
		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 6:00PM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (7 * 3600) );
		//Weekly Overtime1
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $policy_ids['pay_code'][3] );
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (1 * 3600) );
		//Overtime1
		$this->assertEquals( $udt_arr[$date_epoch][3]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][3]['pay_code_id'], $policy_ids['pay_code'][0] );
		$this->assertEquals( $udt_arr[$date_epoch][3]['total_time'], (2 * 3600) );
		//Overtime1
		$this->assertEquals( $udt_arr[$date_epoch][4]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][4]['pay_code_id'], $policy_ids['pay_code'][0] );
		$this->assertEquals( $udt_arr[$date_epoch][4]['total_time'], (2 * 3600) );

		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 5 );

		return TRUE;
	}

	/**
	 * @group OvertimePolicy_testDailyAndWeeklyOverTimePolicyC
	 */
	function testDailyAndWeeklyOverTimePolicyC() {
		global $dd;

		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 200 ); //OT1.5
		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 210 ); //OT2.0
		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 220 ); //OT2.5

		//Daily
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 100, $policy_ids['pay_formula_policy'][0] );
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 120, $policy_ids['pay_formula_policy'][2] );

		//Weekly
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 240, $policy_ids['pay_formula_policy'][1] );

		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 100, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][0] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 120, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][1] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 242, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][2] );

		//Create Policy Group
		$dd->createPolicyGroup( 	$this->company_id,
									NULL, //Meal
									NULL, //Exception
									NULL, //Holiday
									$policy_ids['overtime'], //OT
									NULL, //Premium
									NULL, //Round
									array($this->user_id), //Users
									NULL, //Break
									NULL, //Accrual
									NULL, //Expense
									NULL, //Absence
									array($this->policy_ids['regular'][12]) //Regular
									);


		$date_epoch = TTDate::getBeginWeekEpoch( time() );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		//
		//Day of Week: 1
		//
		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (8 * 3600) );
		//Overtime2
		$this->assertEquals( $udt_arr[$date_epoch][3]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][3]['pay_code_id'], $policy_ids['pay_code'][0] );
		$this->assertEquals( $udt_arr[$date_epoch][3]['total_time'], (2 * 3600) );
		//Overtime3
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $policy_ids['pay_code'][1] );
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (2 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 4 );

		//
		//Day of Week: 2
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (1 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (8 * 3600) );
		//Overtime2
		$this->assertEquals( $udt_arr[$date_epoch][3]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][3]['pay_code_id'], $policy_ids['pay_code'][0] );
		$this->assertEquals( $udt_arr[$date_epoch][3]['total_time'], (2 * 3600) );
		//Overtime3
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $policy_ids['pay_code'][1] );
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (2 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 4 );


		//
		//Day of Week: 3
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (2 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (8 * 3600) );
		//Overtime2
		$this->assertEquals( $udt_arr[$date_epoch][3]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][3]['pay_code_id'], $policy_ids['pay_code'][0] );
		$this->assertEquals( $udt_arr[$date_epoch][3]['total_time'], (2 * 3600) );
		//Overtime3
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $policy_ids['pay_code'][1] );
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (2 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 4 );


		//
		//Day of Week: 4
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (3 * 86400 + 3601)) ;
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (8 * 3600) );
		//Overtime2
		$this->assertEquals( $udt_arr[$date_epoch][3]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][3]['pay_code_id'], $policy_ids['pay_code'][0] );
		$this->assertEquals( $udt_arr[$date_epoch][3]['total_time'], (2 * 3600) );
		//Overtime3
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $policy_ids['pay_code'][1] );
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (2 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 4 );


		//
		//Day of Week: 5
		//

		//
		//Test split shift where the first part of the shift doesn't cross into overtime and only the 2nd half does.
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (4 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 9:35AM'),
								strtotime($date_stamp.' 2:10PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);
		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 2:20PM'),
								strtotime($date_stamp.' 6:45PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (9 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (12300) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (16500) );
		//Overtime2
		//This could be Daily >8 or Weekly >39 as they both apply at the exact same time.
		//However even though Daily OT has a lower calculation order and is calculated first, Weekly > 39 has a higher rate.
		//Which one should be used? Go with Daily OT for now as its calculated first and always needs to apply even after Weekly OT (ie: >12hrs)
		$this->assertEquals( $udt_arr[$date_epoch][3]['object_type_id'], 30 ); //Overtime
		//$this->assertEquals( $udt_arr[$date_epoch][3]['pay_code_id'], $policy_ids['pay_code'][0] ); //Daily >8
		$this->assertEquals( $udt_arr[$date_epoch][3]['pay_code_id'], $policy_ids['pay_code'][2] ); //Weekly >39
		$this->assertEquals( $udt_arr[$date_epoch][3]['total_time'], (1 * 3600) );

		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 4 );

		return TRUE;
	}

	/**
	 * @group OvertimePolicy_testDailyAndWeeklyOverTimePolicyC2
	 */
	function testDailyAndWeeklyOverTimePolicyC2() {
		global $dd;

		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 200 ); //OT1.5
		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 210 ); //OT2.0
		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 220 ); //OT2.5

		//Daily
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 100, $policy_ids['pay_formula_policy'][0] );
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 120, $policy_ids['pay_formula_policy'][2] );

		//Weekly
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 240, $policy_ids['pay_formula_policy'][1] );

		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 100, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][0] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 120, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][1] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 242, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][0] ); //Test with same rate as Daily >8

		//Create Policy Group
		$dd->createPolicyGroup( 	$this->company_id,
									NULL, //Meal
									NULL, //Exception
									NULL, //Holiday
									$policy_ids['overtime'], //OT
									NULL, //Premium
									NULL, //Round
									array($this->user_id), //Users
									NULL, //Break
									NULL, //Accrual
									NULL, //Expense
									NULL, //Absence
									array($this->policy_ids['regular'][12]) //Regular
									);


		$date_epoch = TTDate::getBeginWeekEpoch( time() );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		//
		//Day of Week: 1
		//
		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (8 * 3600) );
		//Overtime2
		$this->assertEquals( $udt_arr[$date_epoch][3]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][3]['pay_code_id'], $policy_ids['pay_code'][0] );
		$this->assertEquals( $udt_arr[$date_epoch][3]['total_time'], (2 * 3600) );
		//Overtime3
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $policy_ids['pay_code'][1] );
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (2 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 4 );

		//
		//Day of Week: 2
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (1 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (8 * 3600) );
		//Overtime2
		$this->assertEquals( $udt_arr[$date_epoch][3]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][3]['pay_code_id'], $policy_ids['pay_code'][0] );
		$this->assertEquals( $udt_arr[$date_epoch][3]['total_time'], (2 * 3600) );
		//Overtime3
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $policy_ids['pay_code'][1] );
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (2 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 4 );


		//
		//Day of Week: 3
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (2 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (8 * 3600) );
		//Overtime2
		$this->assertEquals( $udt_arr[$date_epoch][3]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][3]['pay_code_id'], $policy_ids['pay_code'][0] );
		$this->assertEquals( $udt_arr[$date_epoch][3]['total_time'], (2 * 3600) );
		//Overtime3
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $policy_ids['pay_code'][1] );
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (2 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 4 );


		//
		//Day of Week: 4
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (3 * 86400 + 3601)) ;
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (8 * 3600) );
		//Overtime2
		$this->assertEquals( $udt_arr[$date_epoch][3]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][3]['pay_code_id'], $policy_ids['pay_code'][0] );
		$this->assertEquals( $udt_arr[$date_epoch][3]['total_time'], (2 * 3600) );
		//Overtime3
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $policy_ids['pay_code'][1] );
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (2 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 4 );


		//
		//Day of Week: 5
		//

		//
		//Test split shift where the first part of the shift doesn't cross into overtime and only the 2nd half does.
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (4 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 9:35AM'),
								strtotime($date_stamp.' 2:10PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);
		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 2:20PM'),
								strtotime($date_stamp.' 6:45PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (9 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (12300) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (16500) );
		//Overtime2
		//This could be Daily >8 or Weekly >39 as they both apply at the exact same time.
		//However both have the same rate, so Daily >8 should *definitely* be used over Weekly OT.
		$this->assertEquals( $udt_arr[$date_epoch][3]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][3]['pay_code_id'], $policy_ids['pay_code'][0] ); //Daily >8
		$this->assertEquals( $udt_arr[$date_epoch][3]['total_time'], (1 * 3600) );

		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 4 );

		return TRUE;
	}

	/**
	 * @group OvertimePolicy_testDailyAndWeeklyOverTimePolicyD
	 */
	function testDailyAndWeeklyOverTimePolicyD() {
		global $dd;

		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 200 ); //OT1.5
		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 210 ); //OT2.0
		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 220 ); //OT2.5

		//Daily
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 100, $policy_ids['pay_formula_policy'][0] );
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 120, $policy_ids['pay_formula_policy'][2] );

		//Weekly
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 240, $policy_ids['pay_formula_policy'][1] );

		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 100, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][0] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 120, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][1] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 242, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][2] );

		//Create Policy Group
		$dd->createPolicyGroup( 	$this->company_id,
									NULL, //Meal
									NULL, //Exception
									NULL, //Holiday
									$policy_ids['overtime'], //OT
									NULL, //Premium
									NULL, //Round
									array($this->user_id), //Users
									NULL, //Break
									NULL, //Accrual
									NULL, //Expense
									NULL, //Absence
									array($this->policy_ids['regular'][12]) //Regular
									);


		$date_epoch = TTDate::getBeginWeekEpoch( time() );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		//
		//Day of Week: 1
		//
		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (8 * 3600) );
		//Overtime2
		$this->assertEquals( $udt_arr[$date_epoch][3]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][3]['pay_code_id'], $policy_ids['pay_code'][0] );
		$this->assertEquals( $udt_arr[$date_epoch][3]['total_time'], (2 * 3600) );
		//Overtime3
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $policy_ids['pay_code'][1] );
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (2 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 4 );

		//
		//Day of Week: 2
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (1 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (8 * 3600) );
		//Overtime2
		$this->assertEquals( $udt_arr[$date_epoch][3]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][3]['pay_code_id'], $policy_ids['pay_code'][0] );
		$this->assertEquals( $udt_arr[$date_epoch][3]['total_time'], (2 * 3600) );
		//Overtime3
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $policy_ids['pay_code'][1] );
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (2 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 4 );


		//
		//Day of Week: 3
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (2 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (8 * 3600) );
		//Overtime2
		$this->assertEquals( $udt_arr[$date_epoch][3]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][3]['pay_code_id'], $policy_ids['pay_code'][0] );
		$this->assertEquals( $udt_arr[$date_epoch][3]['total_time'], (2 * 3600) );
		//Overtime3
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $policy_ids['pay_code'][1] );
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (2 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 4 );


		//
		//Day of Week: 4
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (3 * 86400 + 3601)) ;
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (8 * 3600) );
		//Overtime2
		$this->assertEquals( $udt_arr[$date_epoch][3]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][3]['pay_code_id'], $policy_ids['pay_code'][0] );
		$this->assertEquals( $udt_arr[$date_epoch][3]['total_time'], (2 * 3600) );
		//Overtime3
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $policy_ids['pay_code'][1] );
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (2 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 4 );


		//
		//Day of Week: 5
		//

		//
		//Test split shift where the first part of the shift doesn't cross into overtime and only the 2nd half does.
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (4 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 9:35AM'),
								strtotime($date_stamp.' 11:10AM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);
		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 11:20AM'),
								strtotime($date_stamp.' 6:45PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (9 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (5700) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (23100) );
		//Overtime2
		//This could be Daily >8 or Weekly >39 as they both apply at the exact same time.
		//However even though Daily OT has a lower calculation order and is calculated first, Weekly > 39 has a higher rate.
		//Which one should be used? Go with Daily OT for now as its calculated first and always needs to apply even after Weekly OT (ie: >12hrs)
		$this->assertEquals( $udt_arr[$date_epoch][3]['object_type_id'], 30 ); //Overtime
		//$this->assertEquals( $udt_arr[$date_epoch][3]['pay_code_id'], $policy_ids['pay_code'][0] ); //Daily >8
		$this->assertEquals( $udt_arr[$date_epoch][3]['pay_code_id'], $policy_ids['pay_code'][2] ); //Weekly >39
		$this->assertEquals( $udt_arr[$date_epoch][3]['total_time'], (1 * 3600) );

		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 4 );

		return TRUE;
	}

	/**
	 * @group OvertimePolicy_testDailyAndWeeklyOverTimePolicyE
	 */
	function testDailyAndWeeklyOverTimePolicyE() {
		//Test Daily and Weekly OT policies using the same pay code, as this causes problems for calculating weekly OT properly.
		global $dd;

		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 200 ); //OT1.5
		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 210 ); //OT2.0
		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 220 ); //OT2.5

		//Daily
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 100, $policy_ids['pay_formula_policy'][0] );
		//$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 120, $policy_ids['pay_formula_policy'][2] );

		//Weekly
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 240, $policy_ids['pay_formula_policy'][1] );

		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 100, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][0] );
		//$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 120, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][1] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 242, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][0] );

		//Create Policy Group
		$dd->createPolicyGroup( 	$this->company_id,
									NULL, //Meal
									NULL, //Exception
									NULL, //Holiday
									$policy_ids['overtime'], //OT
									NULL, //Premium
									NULL, //Round
									array($this->user_id), //Users
									NULL, //Break
									NULL, //Accrual
									NULL, //Expense
									NULL, //Absence
									array($this->policy_ids['regular'][12]) //Regular
									);


		$date_epoch = TTDate::getBeginWeekEpoch( time() );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		//
		//Day of Week: 1
		//
		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 6:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (10 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (8 * 3600) );
		//Overtime2
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $policy_ids['pay_code'][0] );
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (2 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 3 );

		//
		//Day of Week: 2
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (1 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 6:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (10 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (8 * 3600) );
		//Overtime2
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $policy_ids['pay_code'][0] );
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (2 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 3 );


		//
		//Day of Week: 3
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (2 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 6:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (10 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (8 * 3600) );
		//Overtime2
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $policy_ids['pay_code'][0] );
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (2 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 3 );


		//
		//Day of Week: 4
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (3 * 86400 + 3601)) ;
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 1:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (5 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (5 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 2 );


		//
		//Day of Week: 5
		//

		//
		//Test split shift where the first part of the shift doesn't cross into overtime and only the 2nd half does.
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (4 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 12:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);
		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 1:00PM'),
								strtotime($date_stamp.' 7:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (10 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (4 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (4 * 3600) );
		//Overtime2
		$this->assertEquals( $udt_arr[$date_epoch][3]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][3]['pay_code_id'], $policy_ids['pay_code'][0] );
		$this->assertEquals( $udt_arr[$date_epoch][3]['total_time'], (2 * 3600) );

		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 4 );

		return TRUE;
	}

	/**
	 * @group OvertimePolicy_testDailyAndWeeklyOverTimePolicyF
	 */
	function testDailyAndWeeklyOverTimePolicyF() {
		//Test cases where Weekly OT has a higher rate than Daily OT and therefore takes priority.
		//For example when there is Daily >8 @ 1.5x, Daily >12 @ 2.0x, and Weekly > 40 @ 1.5x and Weekly > 44 @ 2.0x
		global $dd;

		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 200 ); //OT1.5
		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 210 ); //OT2.0
		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 220 ); //OT2.5

		//Daily
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 100, $policy_ids['pay_formula_policy'][0] );
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 120, $policy_ids['pay_formula_policy'][2] );

		//Weekly
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 240, $policy_ids['pay_formula_policy'][1] );

		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 100, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][0] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 120, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][1] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 242, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][2] );

		//Create Policy Group
		$dd->createPolicyGroup( 	$this->company_id,
									NULL, //Meal
									NULL, //Exception
									NULL, //Holiday
									$policy_ids['overtime'], //OT
									NULL, //Premium
									NULL, //Round
									array($this->user_id), //Users
									NULL, //Break
									NULL, //Accrual
									NULL, //Expense
									NULL, //Absence
									array($this->policy_ids['regular'][12]) //Regular
									);


		$date_epoch = TTDate::getBeginWeekEpoch( time() );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		//
		//Day of Week: 1
		//
		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (8 * 3600) );
		//Overtime2
		$this->assertEquals( $udt_arr[$date_epoch][3]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][3]['pay_code_id'], $policy_ids['pay_code'][0] );
		$this->assertEquals( $udt_arr[$date_epoch][3]['total_time'], (2 * 3600) );
		//Overtime3
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $policy_ids['pay_code'][1] );
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (2 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 4 );

		//
		//Day of Week: 2
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (1 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (8 * 3600) );
		//Overtime2
		$this->assertEquals( $udt_arr[$date_epoch][3]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][3]['pay_code_id'], $policy_ids['pay_code'][0] );
		$this->assertEquals( $udt_arr[$date_epoch][3]['total_time'], (2 * 3600) );
		//Overtime3
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $policy_ids['pay_code'][1] );
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (2 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 4 );


		//
		//Day of Week: 3
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (2 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (8 * 3600) );
		//Overtime2
		$this->assertEquals( $udt_arr[$date_epoch][3]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][3]['pay_code_id'], $policy_ids['pay_code'][0] );
		$this->assertEquals( $udt_arr[$date_epoch][3]['total_time'], (2 * 3600) );
		//Overtime3
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $policy_ids['pay_code'][1] );
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (2 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 4 );


		//
		//Day of Week: 4
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (3 * 86400 + 3601)) ;
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (8 * 3600) );
		//Overtime2
		$this->assertEquals( $udt_arr[$date_epoch][3]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][3]['pay_code_id'], $policy_ids['pay_code'][0] );
		$this->assertEquals( $udt_arr[$date_epoch][3]['total_time'], (2 * 3600) );
		//Overtime3
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $policy_ids['pay_code'][1] );
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (2 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 4 );


		//
		//Day of Week: 5
		//

		//
		//Test split shift where the first part of the shift doesn't cross into overtime and only the 2nd half does.
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (4 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 9:35AM'),
								strtotime($date_stamp.' 11:10AM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);
		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 11:20AM'),
								strtotime($date_stamp.' 6:45PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (9 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (5700) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (23100) );
		//Overtime2
		//This could be Daily >8 or Weekly >39 as they both apply at the exact same time.
		//However even though Daily OT has a lower calculation order and is calculated first, Weekly > 39 has a higher rate.
		//Which one should be used? Go with Daily OT for now as its calculated first and always needs to apply even after Weekly OT (ie: >12hrs)
		$this->assertEquals( $udt_arr[$date_epoch][3]['object_type_id'], 30 ); //Overtime
		//$this->assertEquals( $udt_arr[$date_epoch][3]['pay_code_id'], $policy_ids['pay_code'][0] ); //Daily >8
		$this->assertEquals( $udt_arr[$date_epoch][3]['pay_code_id'], $policy_ids['pay_code'][2] ); //Weekly >39
		$this->assertEquals( $udt_arr[$date_epoch][3]['total_time'], (1 * 3600) );

		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 4 );

		//
		//Day of Week: 6
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (5 * 86400 + 3601)) ;
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//Overtime2
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $policy_ids['pay_code'][1] );
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (2 * 3600) );
		//Overtime3
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $policy_ids['pay_code'][2] );
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (10 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 3 );

		return TRUE;
	}

	/**
	 * @group OvertimePolicy_testHolidayAndDailyAndWeeklyOverTimePolicyA
	 */
	function testHolidayAndDailyAndWeeklyOverTimePolicyA() {
		global $dd;

		$date_epoch = TTDate::getBeginWeekEpoch( time() );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		//Holiday
		$policy_ids['holiday'][] = $this->createHolidayPolicy( $this->company_id, 10 );
		$this->createHoliday( $this->company_id, 10, $date_epoch, $policy_ids['holiday'][0] );

		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 200 ); //OT1.5
		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 210 ); //OT2.0
		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 220 ); //OT2.5
		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 510 ); //OT4.0

		//Daily
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 100, $policy_ids['pay_formula_policy'][0] );
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 110, $policy_ids['pay_formula_policy'][1] );
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 120, $policy_ids['pay_formula_policy'][2] );

		//Weekly
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 230, $policy_ids['pay_formula_policy'][0] );
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 240, $policy_ids['pay_formula_policy'][1] );
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 250, $policy_ids['pay_formula_policy'][2] );

		//Holiday
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 500, $policy_ids['pay_formula_policy'][3] );

		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 100, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][0] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 110, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][1] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 120, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][2] );

		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 230, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][3] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 240, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][4] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 250, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][5] );

		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 500, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][6] );

		//Create Policy Group
		$dd->createPolicyGroup( 	$this->company_id,
									NULL, //Meal
									NULL, //Exception
									$policy_ids['holiday'],
									$policy_ids['overtime'], //OT
									NULL, //Premium
									NULL, //Round
									array($this->user_id), //Users
									NULL, //Break
									NULL, //Accrual
									NULL, //Expense
									NULL, //Absence
									array($this->policy_ids['regular'][12]) //Regular
									);

		//
		//Day of Week: 1
		//
		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );

		//Since the Holiday OT rate is 4.0x, its higher than any other OT rate, so the employee should stay on holiday OT for the entire day.
		//Holiday OT
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $policy_ids['pay_code'][6] );
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (12 * 3600) );

		//Holiday OT
		//$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 30 ); //Overtime
		//$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $policy_ids['pay_code'][6] );
		//$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (8*3600) );
		//Overtime 1
		//$this->assertEquals( $udt_arr[$date_epoch][4]['object_type_id'], 30 ); //Overtime
		//$this->assertEquals( $udt_arr[$date_epoch][4]['pay_code_id'], $policy_ids['pay_code'][0] );
		//$this->assertEquals( $udt_arr[$date_epoch][4]['total_time'], (1*3600) );
		//Overtime 2
		//$this->assertEquals( $udt_arr[$date_epoch][3]['object_type_id'], 30 ); //Overtime
		//$this->assertEquals( $udt_arr[$date_epoch][3]['pay_code_id'], $policy_ids['pay_code'][1] );
		//$this->assertEquals( $udt_arr[$date_epoch][3]['total_time'], (1*3600) );
		//Overtime 3
		//$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 30 ); //Overtime
		//$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $policy_ids['pay_code'][2] );
		//$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (2*3600) );

		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 2 );

		//
		//Day of Week: 2
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (1 * 86400 + 3601) ) ;
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (8 * 3600) );
		//Overtime1
		$this->assertEquals( $udt_arr[$date_epoch][4]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][4]['pay_code_id'], $policy_ids['pay_code'][0] );
		$this->assertEquals( $udt_arr[$date_epoch][4]['total_time'], (1 * 3600) );
		//Overtime2
		$this->assertEquals( $udt_arr[$date_epoch][3]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][3]['pay_code_id'], $policy_ids['pay_code'][1] );
		$this->assertEquals( $udt_arr[$date_epoch][3]['total_time'], (1 * 3600) );
		//Overtime3
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $policy_ids['pay_code'][2] );
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (2 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 5 );

		//
		//Day of Week: 3
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (2 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (8 * 3600) );
		//Overtime1
		$this->assertEquals( $udt_arr[$date_epoch][4]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][4]['pay_code_id'], $policy_ids['pay_code'][0] );
		$this->assertEquals( $udt_arr[$date_epoch][4]['total_time'], (1 * 3600) );
		//Overtime2
		$this->assertEquals( $udt_arr[$date_epoch][3]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][3]['pay_code_id'], $policy_ids['pay_code'][1] );
		$this->assertEquals( $udt_arr[$date_epoch][3]['total_time'], (1 * 3600) );
		//Overtime3
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $policy_ids['pay_code'][2] );
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (2 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 5 );

		//
		//Day of Week: 4
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (3 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (8 * 3600) );
		//Overtime1
		$this->assertEquals( $udt_arr[$date_epoch][4]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][4]['pay_code_id'], $policy_ids['pay_code'][0] );
		$this->assertEquals( $udt_arr[$date_epoch][4]['total_time'], (1 * 3600) );
		//Overtime2
		$this->assertEquals( $udt_arr[$date_epoch][3]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][3]['pay_code_id'], $policy_ids['pay_code'][1] );
		$this->assertEquals( $udt_arr[$date_epoch][3]['total_time'], (1 * 3600) );
		//Overtime3
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $policy_ids['pay_code'][2] );
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (2 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 5 );

		//
		//Day of Week: 5
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (4 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (7 * 3600) );
		//Weekly Overtime 1
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $policy_ids['pay_code'][3] );
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (1 * 3600) );
		//Overtime1
		$this->assertEquals( $udt_arr[$date_epoch][5]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][5]['pay_code_id'], $policy_ids['pay_code'][0] );
		$this->assertEquals( $udt_arr[$date_epoch][5]['total_time'], (1 * 3600) );
		//Overtime2
		$this->assertEquals( $udt_arr[$date_epoch][4]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][4]['pay_code_id'], $policy_ids['pay_code'][1] );
		$this->assertEquals( $udt_arr[$date_epoch][4]['total_time'], (1 * 3600) );
		//Overtime3
		$this->assertEquals( $udt_arr[$date_epoch][3]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][3]['pay_code_id'], $policy_ids['pay_code'][2] );
		$this->assertEquals( $udt_arr[$date_epoch][3]['total_time'], (2 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 6 );

		//
		//Day of Week: 6
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (5 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//Weekly Overtime1 >39
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $policy_ids['pay_code'][4] );
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (2 * 3600) );
		//Weekly Overtime1 >31
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $policy_ids['pay_code'][3] );
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (7 * 3600) );
		//Overtime1
		//$this->assertEquals( $udt_arr[$date_epoch][5]['object_type_id'], 30 ); //Overtime
		//$this->assertEquals( $udt_arr[$date_epoch][5]['pay_code_id'], $policy_ids['pay_code'][0] );
		//$this->assertEquals( $udt_arr[$date_epoch][5]['total_time'], (1*3600) );
		//Overtime2
		$this->assertEquals( $udt_arr[$date_epoch][4]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][4]['pay_code_id'], $policy_ids['pay_code'][1] );
		$this->assertEquals( $udt_arr[$date_epoch][4]['total_time'], (1 * 3600) );
		//Overtime3
		$this->assertEquals( $udt_arr[$date_epoch][3]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][3]['pay_code_id'], $policy_ids['pay_code'][2] );
		$this->assertEquals( $udt_arr[$date_epoch][3]['total_time'], (2 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 5 );

		//
		//Day of Week: 7
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (6 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//Weekly Overtime 2 >47
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $policy_ids['pay_code'][5] );
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (4 * 3600) );
		//Weekly Overtime 3 >39
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $policy_ids['pay_code'][4] );
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (6 * 3600) );
		//Overtime1
		//$this->assertEquals( $udt_arr[$date_epoch][5]['object_type_id'], 30 ); //Overtime
		//$this->assertEquals( $udt_arr[$date_epoch][5]['pay_code_id'], $policy_ids['pay_code'][0] );
		//$this->assertEquals( $udt_arr[$date_epoch][5]['total_time'], (1*3600) );
		//Overtime2
		//$this->assertEquals( $udt_arr[$date_epoch][4]['object_type_id'], 30 ); //Overtime
		//$this->assertEquals( $udt_arr[$date_epoch][4]['pay_code_id'], $policy_ids['pay_code'][1] );
		//$this->assertEquals( $udt_arr[$date_epoch][4]['total_time'], (1*3600) );
		//Overtime3
		$this->assertEquals( $udt_arr[$date_epoch][3]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][3]['pay_code_id'], $policy_ids['pay_code'][2] );
		$this->assertEquals( $udt_arr[$date_epoch][3]['total_time'], (2 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 4 );

		return TRUE;
	}

	/**
	 * @group OvertimePolicy_testHolidayOverTimePolicyA
	 */
	function testHolidayOverTimePolicyA() {
		global $dd;

		$date_epoch = TTDate::getBeginWeekEpoch( time() );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		//Holiday
		$policy_ids['holiday'][] = $this->createHolidayPolicy( $this->company_id, 10 );
		$this->createHoliday( $this->company_id, 10, $date_epoch, $policy_ids['holiday'][0] );

		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 200 ); //OT1.5
		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 210 ); //OT2.0
		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 220 ); //OT2.5
		//$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 510 ); //OT4.0

		//Daily
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 100, $policy_ids['pay_formula_policy'][0] );
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 110, $policy_ids['pay_formula_policy'][1] );
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 120, $policy_ids['pay_formula_policy'][2] );

		//Holiday
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 500, $policy_ids['pay_formula_policy'][0] );

		//$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 100, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][0] );
		//$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 110, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][1] );
		//$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 120, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][2] );

		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 510, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][3] );

		//Create Policy Group
		$dd->createPolicyGroup( 	$this->company_id,
									NULL, //Meal
									NULL, //Exception
									$policy_ids['holiday'],
									$policy_ids['overtime'], //OT
									NULL, //Premium
									NULL, //Round
									array($this->user_id), //Users
									NULL, //Break
									NULL, //Accrual
									NULL, //Expense
									NULL, //Absence
									array($this->policy_ids['regular'][12]) //Regular
									);

		//
		//Day of Week: 1
		//
		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);

		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		$this->assertEquals( $udt_arr[$date_epoch][0]['start_time_stamp'], strtotime($date_stamp.' 8:00AM') );
		$this->assertEquals( $udt_arr[$date_epoch][0]['end_time_stamp'], strtotime($date_stamp.' 8:00PM') );

		//Holiday OT
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $policy_ids['pay_code'][3] );
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (12 * 3600) );
		$this->assertEquals( $udt_arr[$date_epoch][1]['start_time_stamp'], strtotime($date_stamp.' 8:00AM') );
		$this->assertEquals( $udt_arr[$date_epoch][1]['end_time_stamp'], strtotime($date_stamp.' 8:00PM') );

		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 2 );
		return TRUE;
	}

	/**
	 * @group OvertimePolicy_testHolidayOverTimePolicyB
	 */
	function testHolidayOverTimePolicyB() {
		global $dd;

		$date_epoch = TTDate::getBeginWeekEpoch( time() );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		//Holiday
		$policy_ids['holiday'][] = $this->createHolidayPolicy( $this->company_id, 10 );
		$this->createHoliday( $this->company_id, 10, $date_epoch, $policy_ids['holiday'][0] );

		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 200 ); //OT1.5
		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 210 ); //OT2.0
		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 220 ); //OT2.5
		//$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 510 ); //OT4.0

		//Daily
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 100, $policy_ids['pay_formula_policy'][0] );
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 110, $policy_ids['pay_formula_policy'][1] );
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 120, $policy_ids['pay_formula_policy'][2] );

		//Holiday
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 500, $policy_ids['pay_formula_policy'][0] );

		//$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 100, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][0] );
		//$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 110, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][1] );
		//$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 120, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][2] );

		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 510, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][3] );

		//Create Policy Group
		$dd->createPolicyGroup( 	$this->company_id,
									NULL, //Meal
									NULL, //Exception
									$policy_ids['holiday'],
									$policy_ids['overtime'], //OT
									NULL, //Premium
									NULL, //Round
									array($this->user_id), //Users
									NULL, //Break
									NULL, //Accrual
									NULL, //Expense
									NULL, //Absence
									array($this->policy_ids['regular'][12]) //Regular
									);

		//
		//Day of Week: 1
		//
		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 12:15PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 20,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);
		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 12:45PM'),
								strtotime($date_stamp.' 8:30PM'),
								array(
											'in_type_id' => 20,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);

		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		$this->assertEquals( $udt_arr[$date_epoch][0]['start_time_stamp'], strtotime($date_stamp.' 8:00AM') );
		$this->assertEquals( $udt_arr[$date_epoch][0]['end_time_stamp'], strtotime($date_stamp.' 8:30PM') );

		//Holiday OT
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $policy_ids['pay_code'][3] );
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (4.25 * 3600) );
		$this->assertEquals( $udt_arr[$date_epoch][1]['start_time_stamp'], strtotime($date_stamp.' 8:00AM') );
		$this->assertEquals( $udt_arr[$date_epoch][1]['end_time_stamp'], strtotime($date_stamp.' 12:15PM') );

		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $policy_ids['pay_code'][3] );
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (7.75 * 3600) );
		$this->assertEquals( $udt_arr[$date_epoch][2]['start_time_stamp'], strtotime($date_stamp.' 12:45PM') );
		$this->assertEquals( $udt_arr[$date_epoch][2]['end_time_stamp'], strtotime($date_stamp.' 8:30PM') );

		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 3 );
		return TRUE;
	}

	/**
	 * @group OvertimePolicy_testHolidayOverTimePolicyC
	 */
	function testHolidayOverTimePolicyC() {
		global $dd;

		$date_epoch = TTDate::getBeginWeekEpoch( time() );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		//Holiday
		$policy_ids['holiday'][] = $this->createHolidayPolicy( $this->company_id, 10 );
		$this->createHoliday( $this->company_id, 10, $date_epoch, $policy_ids['holiday'][0] );

		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 200 ); //OT1.5
		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 210 ); //OT2.0
		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 220 ); //OT2.5
		//$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 510 ); //OT4.0

		//Daily
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 100, $policy_ids['pay_formula_policy'][0] );
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 110, $policy_ids['pay_formula_policy'][1] );
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 120, $policy_ids['pay_formula_policy'][2] );

		//Holiday
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 500, $policy_ids['pay_formula_policy'][0] );

		//$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 100, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][0] );
		//$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 110, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][1] );
		//$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 120, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][2] );

		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 510, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][3] );

		//Create Policy Group
		$dd->createPolicyGroup( 	$this->company_id,
									NULL, //Meal
									NULL, //Exception
									$policy_ids['holiday'],
									$policy_ids['overtime'], //OT
									NULL, //Premium
									NULL, //Round
									array($this->user_id), //Users
									NULL, //Break
									NULL, //Accrual
									NULL, //Expense
									NULL, //Absence
									array($this->policy_ids['regular'][12]) //Regular
									);

		//
		//Day of Week: 1
		//
		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 12:15PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 20,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);
		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 12:45PM'),
								strtotime($date_stamp.' 4:30PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);
		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 5:00PM'),
								strtotime($date_stamp.' 9:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);

		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		$this->assertEquals( $udt_arr[$date_epoch][0]['start_time_stamp'], strtotime($date_stamp.' 8:00AM') );
		$this->assertEquals( $udt_arr[$date_epoch][0]['end_time_stamp'], strtotime($date_stamp.' 9:00PM') );

		//Holiday OT
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $policy_ids['pay_code'][3] );
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (3.75 * 3600) );
		$this->assertEquals( $udt_arr[$date_epoch][1]['start_time_stamp'], strtotime($date_stamp.' 12:45PM') );
		$this->assertEquals( $udt_arr[$date_epoch][1]['end_time_stamp'], strtotime($date_stamp.' 4:30PM') );

		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $policy_ids['pay_code'][3] );
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (4 * 3600) );
		$this->assertEquals( $udt_arr[$date_epoch][2]['start_time_stamp'], strtotime($date_stamp.' 5:00PM') );
		$this->assertEquals( $udt_arr[$date_epoch][2]['end_time_stamp'], strtotime($date_stamp.' 9:00PM') );

		$this->assertEquals( $udt_arr[$date_epoch][3]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][3]['pay_code_id'], $policy_ids['pay_code'][3] );
		$this->assertEquals( $udt_arr[$date_epoch][3]['total_time'], (4.25 * 3600) );
		$this->assertEquals( $udt_arr[$date_epoch][3]['start_time_stamp'], strtotime($date_stamp.' 8:00AM') );
		$this->assertEquals( $udt_arr[$date_epoch][3]['end_time_stamp'], strtotime($date_stamp.' 12:15PM') );

		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 4 );
		return TRUE;
	}

	/**
	 * @group OvertimePolicy_testHolidayAndDailyOverTimePolicyA
	 */
	function testHolidayAndDailyOverTimePolicyA() {
		global $dd;

		$date_epoch = TTDate::getBeginWeekEpoch( time() );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		//Holiday
		$policy_ids['holiday'][] = $this->createHolidayPolicy( $this->company_id, 10 );
		$this->createHoliday( $this->company_id, 10, $date_epoch, $policy_ids['holiday'][0] );

		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 200 ); //OT1.5
		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 210 ); //OT2.0
		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 220 ); //OT2.5
		//$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 510 ); //OT4.0

		//Daily
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 100, $policy_ids['pay_formula_policy'][0] );
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 110, $policy_ids['pay_formula_policy'][1] );
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 120, $policy_ids['pay_formula_policy'][2] );

		//Holiday
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 500, $policy_ids['pay_formula_policy'][0] );

		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 100, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][0] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 110, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][1] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 120, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][2] );

		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 510, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][3] );

		//Create Policy Group
		$dd->createPolicyGroup( 	$this->company_id,
									NULL, //Meal
									NULL, //Exception
									$policy_ids['holiday'],
									$policy_ids['overtime'], //OT
									NULL, //Premium
									NULL, //Round
									array($this->user_id), //Users
									NULL, //Break
									NULL, //Accrual
									NULL, //Expense
									NULL, //Absence
									array($this->policy_ids['regular'][12]) //Regular
									);

		//
		//Day of Week: 1
		//
		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);

		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );

		//Holiday OT: This is daily OT at 1.5x, so other OT at same or higher rates should still kick in after this.
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $policy_ids['pay_code'][3] );
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (8 * 3600) );
		//Overtime 1 >8 (1.5x)
		$this->assertEquals( $udt_arr[$date_epoch][4]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][4]['pay_code_id'], $policy_ids['pay_code'][0] );
		$this->assertEquals( $udt_arr[$date_epoch][4]['total_time'], (1 * 3600) );
		//Overtime 2 >9 (@2.0x)
		$this->assertEquals( $udt_arr[$date_epoch][3]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][3]['pay_code_id'], $policy_ids['pay_code'][1] );
		$this->assertEquals( $udt_arr[$date_epoch][3]['total_time'], (1 * 3600) );
		//Overtime 3 >10 (@2.5x)
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $policy_ids['pay_code'][2] );
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (2 * 3600) );
		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 5 );
		return TRUE;
	}

	/**
	 * @group OvertimePolicy_testHolidayAndDailyOverTimePolicyB
	 */
	function testHolidayAndDailyOverTimePolicyB() {
		global $dd;

		$date_epoch = TTDate::getBeginWeekEpoch( time() );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		//Holiday
		$policy_ids['holiday'][] = $this->createHolidayPolicy( $this->company_id, 10 );
		$this->createHoliday( $this->company_id, 10, $date_epoch, $policy_ids['holiday'][0] );

		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 200 ); //OT1.5
		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 210 ); //OT2.0
		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 220 ); //OT2.5
		//$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 510 ); //OT4.0

		//Daily
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 100, $policy_ids['pay_formula_policy'][0] );
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 110, $policy_ids['pay_formula_policy'][1] );
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 120, $policy_ids['pay_formula_policy'][2] );

		//Holiday
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 500, $policy_ids['pay_formula_policy'][0] );

		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 100, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][0] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 110, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][0] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 120, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][0] );

		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 510, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][3] );

		//Create Policy Group
		$dd->createPolicyGroup( 	$this->company_id,
									NULL, //Meal
									NULL, //Exception
									$policy_ids['holiday'],
									$policy_ids['overtime'], //OT
									NULL, //Premium
									NULL, //Round
									array($this->user_id), //Users
									NULL, //Break
									NULL, //Accrual
									NULL, //Expense
									NULL, //Absence
									array($this->policy_ids['regular'][12]) //Regular
									);

		//
		//Day of Week: 1
		//
		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);

		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );

		//Holiday OT
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $policy_ids['pay_code'][3] );
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (8 * 3600) );

		//Overtime 1 >8 (1.5x) [All Daily OT have same pay code]
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $policy_ids['pay_code'][0] );
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (2 * 3600) );

		//Overtime 1 >8 (1.5x) [All Daily OT have same pay code]
		$this->assertEquals( $udt_arr[$date_epoch][3]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][3]['pay_code_id'], $policy_ids['pay_code'][0] );
		$this->assertEquals( $udt_arr[$date_epoch][3]['total_time'], (1 * 3600) );

		//Overtime 1 >8 (1.5x) [All Daily OT have same pay code]
		$this->assertEquals( $udt_arr[$date_epoch][4]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][4]['pay_code_id'], $policy_ids['pay_code'][0] );
		$this->assertEquals( $udt_arr[$date_epoch][4]['total_time'], (1 * 3600) );

		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 5 );
		return TRUE;
	}


	/**
	 * @group OvertimePolicy_testQuantityWithOverTimePolicy
	 */
	function testQuantityWithOverTimePolicy() {
		global $dd;

		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 200 ); //OT1.5
		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 210 ); //OT2.0
		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 220 ); //OT2.5

		//Daily
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 100, $policy_ids['pay_formula_policy'][0] );
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 110, $policy_ids['pay_formula_policy'][1] );
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 120, $policy_ids['pay_formula_policy'][2] );

		//Weekly
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 230, $policy_ids['pay_formula_policy'][0] );
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 240, $policy_ids['pay_formula_policy'][1] );
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 250, $policy_ids['pay_formula_policy'][2] );

		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 100, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][0] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 110, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][1] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 120, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][2] );

		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 230, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][3] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 240, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][4] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 250, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][5] );

		//Create Policy Group
		$dd->createPolicyGroup( 	$this->company_id,
									NULL, //Meal
									NULL, //Exception
									NULL, //Holiday
									$policy_ids['overtime'], //OT
									NULL, //Premium
									NULL, //Round
									array($this->user_id), //Users
									NULL, //Break
									NULL, //Accrual
									NULL, //Expense
									NULL, //Absence
									array($this->policy_ids['regular'][12]) //Regular
									);


		$date_epoch = TTDate::getBeginWeekEpoch( time() );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		//
		//Day of Week: 1
		//
		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
											'quantity' => 13,
											'bad_quantity' => 3,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		$this->assertEquals( $udt_arr[$date_epoch][0]['quantity'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['bad_quantity'], 0 );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (8 * 3600) );
		$this->assertEquals( $udt_arr[$date_epoch][1]['quantity'], 8.67 );
		$this->assertEquals( $udt_arr[$date_epoch][1]['bad_quantity'], 2 );
		//Overtime1
		$this->assertEquals( $udt_arr[$date_epoch][4]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][4]['pay_code_id'], $policy_ids['pay_code'][0] );
		$this->assertEquals( $udt_arr[$date_epoch][4]['total_time'], (1 * 3600) );
		$this->assertEquals( $udt_arr[$date_epoch][4]['quantity'], 1.08 );
		$this->assertEquals( $udt_arr[$date_epoch][4]['bad_quantity'], 0.25 );
		//Overtime2
		$this->assertEquals( $udt_arr[$date_epoch][3]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][3]['pay_code_id'], $policy_ids['pay_code'][1] );
		$this->assertEquals( $udt_arr[$date_epoch][3]['total_time'], (1 * 3600) );
		$this->assertEquals( $udt_arr[$date_epoch][3]['quantity'], 1.08 );
		$this->assertEquals( $udt_arr[$date_epoch][3]['bad_quantity'], 0.25 );
		//Overtime3
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $policy_ids['pay_code'][2] );
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (2 * 3600) );
		$this->assertEquals( $udt_arr[$date_epoch][2]['quantity'], 2.17 );
		$this->assertEquals( $udt_arr[$date_epoch][2]['bad_quantity'], 0.5 );

		$quantity_total = ($udt_arr[$date_epoch][0]['quantity'] + $udt_arr[$date_epoch][1]['quantity'] + $udt_arr[$date_epoch][2]['quantity'] + $udt_arr[$date_epoch][3]['quantity'] + $udt_arr[$date_epoch][4]['quantity']);
		$this->assertEquals( $quantity_total, 13 );

		$bad_quantity_total = ($udt_arr[$date_epoch][0]['bad_quantity'] + $udt_arr[$date_epoch][1]['bad_quantity'] + $udt_arr[$date_epoch][2]['bad_quantity'] + $udt_arr[$date_epoch][3]['bad_quantity'] + $udt_arr[$date_epoch][4]['bad_quantity']);
		$this->assertEquals( $bad_quantity_total, 3 );

		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 5 );

		//
		//Day of Week: 2
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (1 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 8:05PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
											'quantity' => 13,
											'bad_quantity' => 3,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], 43500 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['quantity'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['bad_quantity'], 0 );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (8 * 3600) );
		$this->assertEquals( $udt_arr[$date_epoch][1]['quantity'], 8.61 );
		$this->assertEquals( $udt_arr[$date_epoch][1]['bad_quantity'], 1.99 );
		//Overtime1
		$this->assertEquals( $udt_arr[$date_epoch][4]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][4]['pay_code_id'], $policy_ids['pay_code'][0] );
		$this->assertEquals( $udt_arr[$date_epoch][4]['total_time'], (1 * 3600) );
		$this->assertEquals( $udt_arr[$date_epoch][4]['quantity'], 1.08 );
		$this->assertEquals( $udt_arr[$date_epoch][4]['bad_quantity'], 0.25 );
		//Overtime2
		$this->assertEquals( $udt_arr[$date_epoch][3]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][3]['pay_code_id'], $policy_ids['pay_code'][1] );
		$this->assertEquals( $udt_arr[$date_epoch][3]['total_time'], (1 * 3600) );
		$this->assertEquals( $udt_arr[$date_epoch][3]['quantity'], 1.07 );
		$this->assertEquals( $udt_arr[$date_epoch][3]['bad_quantity'], 0.25 );
		//Overtime3
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 30 ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $policy_ids['pay_code'][2] );
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], 7500 );
		$this->assertEquals( $udt_arr[$date_epoch][2]['quantity'], 2.24 );
		$this->assertEquals( $udt_arr[$date_epoch][2]['bad_quantity'], 0.51 );

		$quantity_total = ($udt_arr[$date_epoch][0]['quantity'] + $udt_arr[$date_epoch][1]['quantity'] + $udt_arr[$date_epoch][2]['quantity'] + $udt_arr[$date_epoch][3]['quantity'] + $udt_arr[$date_epoch][4]['quantity']);
		$this->assertEquals( $quantity_total, 13 );

		$bad_quantity_total = ($udt_arr[$date_epoch][0]['bad_quantity'] + $udt_arr[$date_epoch][1]['bad_quantity'] + $udt_arr[$date_epoch][2]['bad_quantity'] + $udt_arr[$date_epoch][3]['bad_quantity'] + $udt_arr[$date_epoch][4]['bad_quantity']);
		$this->assertEquals( $bad_quantity_total, 3 );

		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 5 );

		return TRUE;
	}

	/**
	 * @group OvertimePolicy_testAutoDeductMealAndNoOverTimePolicyA
	 */
	function testAutoDeductMealAndNoOverTimePolicyA() {
		global $dd;

		$policy_ids['pay_formula_policy'][] = $this->createPayFormulaPolicy( $this->company_id, 200 ); //OT1.5
		$policy_ids['pay_formula_policy'][] = $this->createPayFormulaPolicy( $this->company_id, 210 ); //OT2.0
		$policy_ids['pay_formula_policy'][] = $this->createPayFormulaPolicy( $this->company_id, 220 ); //OT2.5

		$policy_ids['pay_code'][] = $this->createPayCode( $this->company_id, 100, $policy_ids['pay_formula_policy'][0] );
		$policy_ids['pay_code'][] = $this->createPayCode( $this->company_id, 110, $policy_ids['pay_formula_policy'][1] );
		$policy_ids['pay_code'][] = $this->createPayCode( $this->company_id, 120, $policy_ids['pay_formula_policy'][2] );
		$policy_ids['pay_code'][] = $this->createPayCode( $this->company_id, 190, $policy_ids['pay_formula_policy'][0] );

		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 100, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][0] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 110, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][1] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 120, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][2] );

		$policy_ids['meal'][] = $this->createMealPolicy( $this->company_id, 120 ); //AutoDeduct 1hr

		//Create Policy Group
		$dd->createPolicyGroup( 	$this->company_id,
									$policy_ids['meal'], //Meal
									NULL, //Exception
									NULL, //Holiday
									$policy_ids['overtime'], //OT
									NULL, //Premium
									NULL, //Round
									array($this->user_id), //Users
									NULL, //Break
									NULL, //Accrual
									NULL, //Expense
									NULL, //Absence
									array($this->policy_ids['regular'][10]) //Regular
									);

		$date_epoch = TTDate::getBeginWeekEpoch( time() );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 3:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);

		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (7 * 3600) ); //Unless meal/break time is deducted from Regular Time, the total should be the same as worked time.
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (7 * 3600) );
		//Regular Time (AutoDeduct Lunch)
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 100 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $this->policy_ids['pay_code'][190] ); //Lunch Time
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (-1 * 3600) );

		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 3 );

		return TRUE;
	}

	/**
	 * @group OvertimePolicy_testAutoDeductMealAndNoOverTimePolicyB
	 */
	function testAutoDeductMealAndNoOverTimePolicyB() {
		global $dd;

		$policy_ids['pay_formula_policy'][] = $this->createPayFormulaPolicy( $this->company_id, 200 ); //OT1.5
		$policy_ids['pay_formula_policy'][] = $this->createPayFormulaPolicy( $this->company_id, 210 ); //OT2.0
		$policy_ids['pay_formula_policy'][] = $this->createPayFormulaPolicy( $this->company_id, 220 ); //OT2.5

		$policy_ids['pay_code'][] = $this->createPayCode( $this->company_id, 100, $policy_ids['pay_formula_policy'][0] );
		$policy_ids['pay_code'][] = $this->createPayCode( $this->company_id, 110, $policy_ids['pay_formula_policy'][1] );
		$policy_ids['pay_code'][] = $this->createPayCode( $this->company_id, 120, $policy_ids['pay_formula_policy'][2] );
		$policy_ids['pay_code'][] = $this->createPayCode( $this->company_id, 190, $policy_ids['pay_formula_policy'][0] );

		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 100, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][0] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 110, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][1] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 120, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][2] );

		$policy_ids['meal'][] = $this->createMealPolicy( $this->company_id, 120 ); //AutoDeduct 1hr

		//Create Policy Group
		$dd->createPolicyGroup( 	$this->company_id,
									$policy_ids['meal'], //Meal
									NULL, //Exception
									NULL, //Holiday
									$policy_ids['overtime'], //OT
									NULL, //Premium
									NULL, //Round
									array($this->user_id), //Users
									NULL, //Break
									NULL, //Accrual
									NULL, //Expense
									NULL, //Absence
									array($this->policy_ids['regular'][10]) //Regular
									);

		$date_epoch = TTDate::getBeginWeekEpoch( time() );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 5:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);

		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (9 * 3600) ); //Unless meal/break time is deducted from Regular Time, the total should be the same as worked time.
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (9 * 3600) );
		//Regular Time (AutoDeduct Lunch)
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 100 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $this->policy_ids['pay_code'][190] ); //Lunch Time
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (-1 * 3600) );

		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 3 );

		return TRUE;
	}

	/**
	 * @group OvertimePolicy_testAutoDeductMealAndNoOverTimePolicyC
	 */
	function testAutoDeductMealAndNoOverTimePolicyC() {
		global $dd;

		$policy_ids['pay_formula_policy'][] = $this->createPayFormulaPolicy( $this->company_id, 200 ); //OT1.5
		$policy_ids['pay_formula_policy'][] = $this->createPayFormulaPolicy( $this->company_id, 210 ); //OT2.0
		$policy_ids['pay_formula_policy'][] = $this->createPayFormulaPolicy( $this->company_id, 220 ); //OT2.5

		$policy_ids['pay_code'][] = $this->createPayCode( $this->company_id, 100, $policy_ids['pay_formula_policy'][0] );
		$policy_ids['pay_code'][] = $this->createPayCode( $this->company_id, 110, $policy_ids['pay_formula_policy'][1] );
		$policy_ids['pay_code'][] = $this->createPayCode( $this->company_id, 120, $policy_ids['pay_formula_policy'][2] );
		$policy_ids['pay_code'][] = $this->createPayCode( $this->company_id, 190, $policy_ids['pay_formula_policy'][0] );

		//Don't include meal/break in overtime. Include it in Regular time instead.
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 100, $this->policy_ids['contributing_shift_policy'][10], $policy_ids['pay_code'][0] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 110, $this->policy_ids['contributing_shift_policy'][10], $policy_ids['pay_code'][1] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 120, $this->policy_ids['contributing_shift_policy'][10], $policy_ids['pay_code'][2] );

		$policy_ids['meal'][] = $this->createMealPolicy( $this->company_id, 120 ); //AutoDeduct 1hr

		//Create Policy Group
		$dd->createPolicyGroup( 	$this->company_id,
									$policy_ids['meal'], //Meal
									NULL, //Exception
									NULL, //Holiday
									$policy_ids['overtime'], //OT
									NULL, //Premium
									NULL, //Round
									array($this->user_id), //Users
									NULL, //Break
									NULL, //Accrual
									NULL, //Expense
									NULL, //Absence
									array($this->policy_ids['regular'][12]) //Regular incl. meal/break
									);

		$date_epoch = TTDate::getBeginWeekEpoch( time() );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 3:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);

		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (6 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (6 * 3600) );
		//Regular Time (AutoDeduct Lunch)
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 100 ); //Lunch
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $this->policy_ids['pay_code'][190] ); //Lunch Time
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (-1 * 3600) );

		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 3 );

		return TRUE;
	}

	/**
	 * @group OvertimePolicy_testAutoDeductMealAndNoOverTimePolicyD
	 */
	function testAutoDeductMealAndNoOverTimePolicyD() {
		global $dd;

		$policy_ids['pay_formula_policy'][] = $this->createPayFormulaPolicy( $this->company_id, 200 ); //OT1.5
		$policy_ids['pay_formula_policy'][] = $this->createPayFormulaPolicy( $this->company_id, 210 ); //OT2.0
		$policy_ids['pay_formula_policy'][] = $this->createPayFormulaPolicy( $this->company_id, 220 ); //OT2.5

		$policy_ids['pay_code'][] = $this->createPayCode( $this->company_id, 100, $policy_ids['pay_formula_policy'][0] );
		$policy_ids['pay_code'][] = $this->createPayCode( $this->company_id, 110, $policy_ids['pay_formula_policy'][1] );
		$policy_ids['pay_code'][] = $this->createPayCode( $this->company_id, 120, $policy_ids['pay_formula_policy'][2] );
		$policy_ids['pay_code'][] = $this->createPayCode( $this->company_id, 190, $policy_ids['pay_formula_policy'][0] );

		//Don't include meal/break in overtime. Include it in Regular time instead.
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 100, $this->policy_ids['contributing_shift_policy'][10], $policy_ids['pay_code'][0] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 110, $this->policy_ids['contributing_shift_policy'][10], $policy_ids['pay_code'][1] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 120, $this->policy_ids['contributing_shift_policy'][10], $policy_ids['pay_code'][2] );

		$policy_ids['meal'][] = $this->createMealPolicy( $this->company_id, 120 ); //AutoDeduct 1hr

		//Create Policy Group
		$dd->createPolicyGroup( 	$this->company_id,
									$policy_ids['meal'], //Meal
									NULL, //Exception
									NULL, //Holiday
									$policy_ids['overtime'], //OT
									NULL, //Premium
									NULL, //Round
									array($this->user_id), //Users
									NULL, //Break
									NULL, //Accrual
									NULL, //Expense
									NULL, //Absence
									array($this->policy_ids['regular'][12]) //Regular incl. meal/break
									);

		$date_epoch = TTDate::getBeginWeekEpoch( time() );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 5:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);

		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (8 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (8 * 3600) );
		//Regular Time (AutoDeduct Lunch)
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 100 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $this->policy_ids['pay_code'][190] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (-1 * 3600) );

		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 3 );

		return TRUE;
	}

	/**
	 * @group OvertimePolicy_testAutoDeductMealAndOverTimePolicyA
	 */
	function testAutoDeductMealAndOverTimePolicyA() {
		global $dd;

		$policy_ids['pay_formula_policy'][] = $this->createPayFormulaPolicy( $this->company_id, 200 ); //OT1.5
		$policy_ids['pay_formula_policy'][] = $this->createPayFormulaPolicy( $this->company_id, 210 ); //OT2.0
		$policy_ids['pay_formula_policy'][] = $this->createPayFormulaPolicy( $this->company_id, 220 ); //OT2.5

		$policy_ids['pay_code'][] = $this->createPayCode( $this->company_id, 100, $policy_ids['pay_formula_policy'][0] );
		$policy_ids['pay_code'][] = $this->createPayCode( $this->company_id, 110, $policy_ids['pay_formula_policy'][1] );
		$policy_ids['pay_code'][] = $this->createPayCode( $this->company_id, 120, $policy_ids['pay_formula_policy'][2] );
		$policy_ids['pay_code'][] = $this->createPayCode( $this->company_id, 190, $policy_ids['pay_formula_policy'][0] );

		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 100, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][0] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 110, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][1] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 120, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][2] );

		$policy_ids['meal'][] = $this->createMealPolicy( $this->company_id, 120 ); //AutoDeduct 1hr

		//Create Policy Group
		$dd->createPolicyGroup( 	$this->company_id,
									$policy_ids['meal'], //Meal
									NULL, //Exception
									NULL, //Holiday
									$policy_ids['overtime'], //OT
									NULL, //Premium
									NULL, //Round
									array($this->user_id), //Users
									NULL, //Break
									NULL, //Accrual
									NULL, //Expense
									NULL, //Absence
									array($this->policy_ids['regular'][10]) //Regular
									);

		$date_epoch = TTDate::getBeginWeekEpoch( time() );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 5:30PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);

		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (9.5 * 3600) ); //Unless meal/break time is deducted from Regular Time, the total should be the same as worked time.
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (9 * 3600) );

		//OverTime
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 30 ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $policy_ids['pay_code'][0] ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (0.5 * 3600) );

		//Regular Time (AutoDeduct Lunch)
		$this->assertEquals( $udt_arr[$date_epoch][3]['object_type_id'], 100 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][3]['pay_code_id'], $this->policy_ids['pay_code'][190] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][3]['total_time'], (-1 * 3600) );

		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 4 );

		return TRUE;
	}

	/**
	 * @group OvertimePolicy_testAutoDeductMealAndOverTimePolicyB
	 */
	function testAutoDeductMealAndOverTimePolicyB() {
		global $dd;

		$policy_ids['pay_formula_policy'][] = $this->createPayFormulaPolicy( $this->company_id, 200 ); //OT1.5
		$policy_ids['pay_formula_policy'][] = $this->createPayFormulaPolicy( $this->company_id, 210 ); //OT2.0
		$policy_ids['pay_formula_policy'][] = $this->createPayFormulaPolicy( $this->company_id, 220 ); //OT2.5

		$policy_ids['pay_code'][] = $this->createPayCode( $this->company_id, 100, $policy_ids['pay_formula_policy'][0] );
		$policy_ids['pay_code'][] = $this->createPayCode( $this->company_id, 110, $policy_ids['pay_formula_policy'][1] );
		$policy_ids['pay_code'][] = $this->createPayCode( $this->company_id, 120, $policy_ids['pay_formula_policy'][2] );
		$policy_ids['pay_code'][] = $this->createPayCode( $this->company_id, 190, $policy_ids['pay_formula_policy'][0] );

		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 100, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][0] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 110, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][1] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 120, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][2] );

		$policy_ids['meal'][] = $this->createMealPolicy( $this->company_id, 120 ); //AutoDeduct 1hr

		//Create Policy Group
		$dd->createPolicyGroup( 	$this->company_id,
									$policy_ids['meal'], //Meal
									NULL, //Exception
									NULL, //Holiday
									$policy_ids['overtime'], //OT
									NULL, //Premium
									NULL, //Round
									array($this->user_id), //Users
									NULL, //Break
									NULL, //Accrual
									NULL, //Expense
									NULL, //Absence
									array($this->policy_ids['regular'][10]) //Regular
									);

		$date_epoch = TTDate::getBeginWeekEpoch( time() );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 6:30PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);

		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (10.5 * 3600) ); //Unless meal/break time is deducted from Regular Time, the total should be the same as worked time.
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (9 * 3600) );
		//OverTime
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 30 ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $policy_ids['pay_code'][1] ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (0.5 * 3600) );
		//OverTime
		$this->assertEquals( $udt_arr[$date_epoch][3]['object_type_id'], 30 ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][3]['pay_code_id'], $policy_ids['pay_code'][0] ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][3]['total_time'], (1 * 3600) );
		//Regular Time (AutoDeduct Lunch)
		$this->assertEquals( $udt_arr[$date_epoch][4]['object_type_id'], 100 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][4]['pay_code_id'], $this->policy_ids['pay_code'][190] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][4]['total_time'], (-1 * 3600) );

		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 5 );

		return TRUE;
	}

	/**
	 * @group OvertimePolicy_testAutoDeductMealAndOverTimePolicyC
	 */
	function testAutoDeductMealAndOverTimePolicyC() {
		global $dd;

		$policy_ids['pay_formula_policy'][] = $this->createPayFormulaPolicy( $this->company_id, 200 ); //OT1.5
		$policy_ids['pay_formula_policy'][] = $this->createPayFormulaPolicy( $this->company_id, 210 ); //OT2.0
		$policy_ids['pay_formula_policy'][] = $this->createPayFormulaPolicy( $this->company_id, 220 ); //OT2.5

		$policy_ids['pay_code'][] = $this->createPayCode( $this->company_id, 100, $policy_ids['pay_formula_policy'][0] );
		$policy_ids['pay_code'][] = $this->createPayCode( $this->company_id, 110, $policy_ids['pay_formula_policy'][1] );
		$policy_ids['pay_code'][] = $this->createPayCode( $this->company_id, 120, $policy_ids['pay_formula_policy'][2] );
		$policy_ids['pay_code'][] = $this->createPayCode( $this->company_id, 190, $policy_ids['pay_formula_policy'][0] );

		//Don't include meal/break in overtime. Include it in Regular time instead.
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 100, $this->policy_ids['contributing_shift_policy'][10], $policy_ids['pay_code'][0] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 110, $this->policy_ids['contributing_shift_policy'][10], $policy_ids['pay_code'][1] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 120, $this->policy_ids['contributing_shift_policy'][10], $policy_ids['pay_code'][2] );

		$policy_ids['meal'][] = $this->createMealPolicy( $this->company_id, 120 ); //AutoDeduct 1hr

		//Create Policy Group
		$dd->createPolicyGroup( 	$this->company_id,
									$policy_ids['meal'], //Meal
									NULL, //Exception
									NULL, //Holiday
									$policy_ids['overtime'], //OT
									NULL, //Premium
									NULL, //Round
									array($this->user_id), //Users
									NULL, //Break
									NULL, //Accrual
									NULL, //Expense
									NULL, //Absence
									array($this->policy_ids['regular'][12]) //Regular incl. meal/break
									);

		$date_epoch = TTDate::getBeginWeekEpoch( time() );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 5:30PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);

		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (8.5 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (8 * 3600) );

		//OverTime
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 30 ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $policy_ids['pay_code'][0] ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (0.5 * 3600) );

		//Regular Time (AutoDeduct Lunch)
		$this->assertEquals( $udt_arr[$date_epoch][3]['object_type_id'], 100 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][3]['pay_code_id'], $this->policy_ids['pay_code'][190] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][3]['total_time'], (-1 * 3600) );

		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 4 );

		return TRUE;
	}

	/**
	 * @group OvertimePolicy_testAutoDeductMealAndOverTimePolicyD
	 */
	function testAutoDeductMealAndOverTimePolicyD() {
		global $dd;

		$policy_ids['pay_formula_policy'][] = $this->createPayFormulaPolicy( $this->company_id, 200 ); //OT1.5
		$policy_ids['pay_formula_policy'][] = $this->createPayFormulaPolicy( $this->company_id, 210 ); //OT2.0
		$policy_ids['pay_formula_policy'][] = $this->createPayFormulaPolicy( $this->company_id, 220 ); //OT2.5

		$policy_ids['pay_code'][] = $this->createPayCode( $this->company_id, 100, $policy_ids['pay_formula_policy'][0] );
		$policy_ids['pay_code'][] = $this->createPayCode( $this->company_id, 110, $policy_ids['pay_formula_policy'][1] );
		$policy_ids['pay_code'][] = $this->createPayCode( $this->company_id, 120, $policy_ids['pay_formula_policy'][2] );
		$policy_ids['pay_code'][] = $this->createPayCode( $this->company_id, 190, $policy_ids['pay_formula_policy'][0] );

		//Don't include meal/break in overtime. Include it in Regular time instead.
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 100, $this->policy_ids['contributing_shift_policy'][10], $policy_ids['pay_code'][0] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 110, $this->policy_ids['contributing_shift_policy'][10], $policy_ids['pay_code'][1] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 120, $this->policy_ids['contributing_shift_policy'][10], $policy_ids['pay_code'][2] );

		$policy_ids['meal'][] = $this->createMealPolicy( $this->company_id, 120 ); //AutoDeduct 1hr

		//Create Policy Group
		$dd->createPolicyGroup( 	$this->company_id,
									$policy_ids['meal'], //Meal
									NULL, //Exception
									NULL, //Holiday
									$policy_ids['overtime'], //OT
									NULL, //Premium
									NULL, //Round
									array($this->user_id), //Users
									NULL, //Break
									NULL, //Accrual
									NULL, //Expense
									NULL, //Absence
									array($this->policy_ids['regular'][12]) //Regular incl. meal/break
									);

		$date_epoch = TTDate::getBeginWeekEpoch( time() );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 6:30PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);

		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (9.5 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (8 * 3600) );
		//OverTime
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 30 ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $policy_ids['pay_code'][1] ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (0.5 * 3600) );
		//OverTime
		$this->assertEquals( $udt_arr[$date_epoch][3]['object_type_id'], 30 ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][3]['pay_code_id'], $policy_ids['pay_code'][0] ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][3]['total_time'], (1 * 3600) );

		//Regular Time (AutoDeduct Lunch)
		$this->assertEquals( $udt_arr[$date_epoch][4]['object_type_id'], 100 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][4]['pay_code_id'], $this->policy_ids['pay_code'][190] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][4]['total_time'], (-1 * 3600) );

		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 5 );

		return TRUE;
	}

	/**
	 * @group OvertimePolicy_testAutoDeductMealAndOverTimePolicyE
	 */
	function testAutoDeductMealAndOverTimePolicyE() {
		global $dd;

		$policy_ids['pay_formula_policy'][] = $this->createPayFormulaPolicy( $this->company_id, 200 ); //OT1.5
		$policy_ids['pay_formula_policy'][] = $this->createPayFormulaPolicy( $this->company_id, 210 ); //OT2.0
		$policy_ids['pay_formula_policy'][] = $this->createPayFormulaPolicy( $this->company_id, 220 ); //OT2.5

		$policy_ids['pay_code'][] = $this->createPayCode( $this->company_id, 100, $policy_ids['pay_formula_policy'][0] );
		$policy_ids['pay_code'][] = $this->createPayCode( $this->company_id, 110, $policy_ids['pay_formula_policy'][1] );
		$policy_ids['pay_code'][] = $this->createPayCode( $this->company_id, 120, $policy_ids['pay_formula_policy'][2] );
		$policy_ids['pay_code'][] = $this->createPayCode( $this->company_id, 190, $policy_ids['pay_formula_policy'][0] );

		//Don't include meal/break in overtime. Include it in Regular time instead.
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 100, $this->policy_ids['contributing_shift_policy'][10], $policy_ids['pay_code'][0] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 110, $this->policy_ids['contributing_shift_policy'][10], $policy_ids['pay_code'][1] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 120, $this->policy_ids['contributing_shift_policy'][10], $policy_ids['pay_code'][2] );

		$policy_ids['meal'][] = $this->createMealPolicy( $this->company_id, 120 ); //AutoDeduct 1hr

		//Create Policy Group
		$dd->createPolicyGroup( 	$this->company_id,
									$policy_ids['meal'], //Meal
									NULL, //Exception
									NULL, //Holiday
									$policy_ids['overtime'], //OT
									NULL, //Premium
									NULL, //Round
									array($this->user_id), //Users
									NULL, //Break
									NULL, //Accrual
									NULL, //Expense
									NULL, //Absence
									array($this->policy_ids['regular'][12]) //Regular incl. meal/break
									);

		$date_epoch = TTDate::getBeginWeekEpoch( time() );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 1:30PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);
		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 1:30PM'),
								strtotime($date_stamp.' 6:30PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);
		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (9.5 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], 10885 );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], 17915 );
		//OverTime
		$this->assertEquals( $udt_arr[$date_epoch][3]['object_type_id'], 30 ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][3]['pay_code_id'], $policy_ids['pay_code'][1] ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][3]['total_time'], (0.5 * 3600) );
		//OverTime
		$this->assertEquals( $udt_arr[$date_epoch][4]['object_type_id'], 30 ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][4]['pay_code_id'], $policy_ids['pay_code'][0] ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][4]['total_time'], (1 * 3600) );
		//Regular Time (AutoDeduct Lunch)
		$this->assertEquals( $udt_arr[$date_epoch][5]['object_type_id'], 100 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][5]['pay_code_id'], $this->policy_ids['pay_code'][190] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][5]['total_time'], -1885 );
		//Regular Time (AutoDeduct Lunch)
		$this->assertEquals( $udt_arr[$date_epoch][6]['object_type_id'], 100 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][6]['pay_code_id'], $this->policy_ids['pay_code'][190] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][6]['total_time'], -1715 );

		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 7 );

		return TRUE;
	}

	/**
	 * @group OvertimePolicy_testAutoAddMealAndNoOverTimePolicyA
	 */
	function testAutoAddMealAndNoOverTimePolicyA() {
		global $dd;

		$policy_ids['pay_formula_policy'][] = $this->createPayFormulaPolicy( $this->company_id, 200 ); //OT1.5
		$policy_ids['pay_formula_policy'][] = $this->createPayFormulaPolicy( $this->company_id, 210 ); //OT2.0
		$policy_ids['pay_formula_policy'][] = $this->createPayFormulaPolicy( $this->company_id, 220 ); //OT2.5

		$policy_ids['pay_code'][] = $this->createPayCode( $this->company_id, 100, $policy_ids['pay_formula_policy'][0] );
		$policy_ids['pay_code'][] = $this->createPayCode( $this->company_id, 110, $policy_ids['pay_formula_policy'][1] );
		$policy_ids['pay_code'][] = $this->createPayCode( $this->company_id, 120, $policy_ids['pay_formula_policy'][2] );
		$policy_ids['pay_code'][] = $this->createPayCode( $this->company_id, 190, $policy_ids['pay_formula_policy'][0] );

		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 100, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][0] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 110, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][1] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 120, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][2] );

		$policy_ids['meal'][] = $this->createMealPolicy( $this->company_id, 110 ); //AutoAdd 1hr

		//Create Policy Group
		$dd->createPolicyGroup( 	$this->company_id,
									$policy_ids['meal'], //Meal
									NULL, //Exception
									NULL, //Holiday
									$policy_ids['overtime'], //OT
									NULL, //Premium
									NULL, //Round
									array($this->user_id), //Users
									NULL, //Break
									NULL, //Accrual
									NULL, //Expense
									NULL, //Absence
									array($this->policy_ids['regular'][10]) //Regular
									);

		$date_epoch = TTDate::getBeginWeekEpoch( time() );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 9:00AM'),
								strtotime($date_stamp.' 12:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 20,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);
		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 1:00PM'),
								strtotime($date_stamp.' 4:00PM'),
								array(
											'in_type_id' => 20,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);

		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (6 * 3600) ); //Unless meal/break time is added to Regular Time, the total should be the same as worked time.
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (3 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (3 * 3600) );
		//Regular Time (AutoAdd Lunch)
		$this->assertEquals( $udt_arr[$date_epoch][3]['object_type_id'], 100 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][3]['pay_code_id'], $this->policy_ids['pay_code'][190] ); //Lunch Time
		$this->assertEquals( $udt_arr[$date_epoch][3]['total_time'], (0.5 * 3600) );
		//Regular Time (AutoAdd Lunch)
		$this->assertEquals( $udt_arr[$date_epoch][4]['object_type_id'], 100 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][4]['pay_code_id'], $this->policy_ids['pay_code'][190] ); //Lunch Time
		$this->assertEquals( $udt_arr[$date_epoch][4]['total_time'], (0.5 * 3600) );

		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 5 );

		return TRUE;
	}

	/**
	 * @group OvertimePolicy_testAutoAddMealAndOverTimePolicyA
	 */
	function testAutoAddMealAndOverTimePolicyA() {
		global $dd;

		$policy_ids['pay_formula_policy'][] = $this->createPayFormulaPolicy( $this->company_id, 200 ); //OT1.5
		$policy_ids['pay_formula_policy'][] = $this->createPayFormulaPolicy( $this->company_id, 210 ); //OT2.0
		$policy_ids['pay_formula_policy'][] = $this->createPayFormulaPolicy( $this->company_id, 220 ); //OT2.5

		$policy_ids['pay_code'][] = $this->createPayCode( $this->company_id, 100, $policy_ids['pay_formula_policy'][0] );
		$policy_ids['pay_code'][] = $this->createPayCode( $this->company_id, 110, $policy_ids['pay_formula_policy'][1] );
		$policy_ids['pay_code'][] = $this->createPayCode( $this->company_id, 120, $policy_ids['pay_formula_policy'][2] );
		$policy_ids['pay_code'][] = $this->createPayCode( $this->company_id, 190, $policy_ids['pay_formula_policy'][0] );

		//Don't include meal/break in overtime. Include it in Regular time instead.
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 100, $this->policy_ids['contributing_shift_policy'][10], $policy_ids['pay_code'][0] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 110, $this->policy_ids['contributing_shift_policy'][10], $policy_ids['pay_code'][1] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 120, $this->policy_ids['contributing_shift_policy'][10], $policy_ids['pay_code'][2] );

		//$policy_ids['meal'][] = $this->createMealPolicy( $this->company_id, 120 ); //AutoDeduct 1hr
		$policy_ids['meal'][] = $this->createMealPolicy( $this->company_id, 110 ); //AutoAdd 1hr


		//Create Policy Group
		$dd->createPolicyGroup( 	$this->company_id,
									$policy_ids['meal'], //Meal
									NULL, //Exception
									NULL, //Holiday
									$policy_ids['overtime'], //OT
									NULL, //Premium
									NULL, //Round
									array($this->user_id), //Users
									NULL, //Break
									NULL, //Accrual
									NULL, //Expense
									NULL, //Absence
									array($this->policy_ids['regular'][12]) //Regular incl. meal/break
									);

		$date_epoch = TTDate::getBeginWeekEpoch( time() );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 2:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 20,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);
		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 3:00PM'),
								strtotime($date_stamp.' 8:00PM'),
								array(
											'in_type_id' => 20,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);

		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], 1637 );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], 1963 );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][3]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][3]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][3]['total_time'], 3600 );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][4]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][4]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][4]['total_time'], (6 * 3600) );


		//OverTime
		$this->assertEquals( $udt_arr[$date_epoch][5]['object_type_id'], 30 ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][5]['pay_code_id'], $policy_ids['pay_code'][2] ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][5]['total_time'], (2 * 3600) );
		//OverTime
		$this->assertEquals( $udt_arr[$date_epoch][6]['object_type_id'], 30 ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][6]['pay_code_id'], $policy_ids['pay_code'][1] ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][6]['total_time'], (1 * 3600) );
		//OverTime
		$this->assertEquals( $udt_arr[$date_epoch][7]['object_type_id'], 30 ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][7]['pay_code_id'], $policy_ids['pay_code'][0] ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][7]['total_time'], (1 * 3600) );

		//Regular Time (AutoDeduct Lunch)
		$this->assertEquals( $udt_arr[$date_epoch][8]['object_type_id'], 100 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][8]['pay_code_id'], $this->policy_ids['pay_code'][190] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][8]['total_time'], 1637 );
		//Regular Time (AutoDeduct Lunch)
		$this->assertEquals( $udt_arr[$date_epoch][9]['object_type_id'], 100 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][9]['pay_code_id'], $this->policy_ids['pay_code'][190] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][9]['total_time'], 1963 );

		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 10 );

		return TRUE;
	}

	/**
	 * @group OvertimePolicy_testAutoDeductMealAndBreakAndOverTimePolicyA
	 */
	function testAutoDeductMealAndBreakAndOverTimePolicyA() {
		global $dd;

		$policy_ids['pay_formula_policy'][] = $this->createPayFormulaPolicy( $this->company_id, 200 ); //OT1.5
		$policy_ids['pay_formula_policy'][] = $this->createPayFormulaPolicy( $this->company_id, 210 ); //OT2.0
		$policy_ids['pay_formula_policy'][] = $this->createPayFormulaPolicy( $this->company_id, 220 ); //OT2.5

		$policy_ids['pay_code'][] = $this->createPayCode( $this->company_id, 100, $policy_ids['pay_formula_policy'][0] );
		$policy_ids['pay_code'][] = $this->createPayCode( $this->company_id, 110, $policy_ids['pay_formula_policy'][1] );
		$policy_ids['pay_code'][] = $this->createPayCode( $this->company_id, 120, $policy_ids['pay_formula_policy'][2] );
		$policy_ids['pay_code'][] = $this->createPayCode( $this->company_id, 190, $policy_ids['pay_formula_policy'][0] );

		//Don't include meal/break in overtime. Include it in Regular time instead.
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 100, $this->policy_ids['contributing_shift_policy'][10], $policy_ids['pay_code'][0] );
		//$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 110, $this->policy_ids['contributing_shift_policy'][10], $policy_ids['pay_code'][1] );
		//$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 120, $this->policy_ids['contributing_shift_policy'][10], $policy_ids['pay_code'][2] );

		$policy_ids['meal'][] = $this->createMealPolicy( $this->company_id, 120 ); //AutoDeduct 1hr
		//$policy_ids['break'][] = $this->createBreakPolicy( $this->company_id, 120 ); //AutoDeduct 15min
		//$policy_ids['break'][] = $this->createBreakPolicy( $this->company_id, 121 ); //AutoDeduct 15min
		$policy_ids['break'][] = $this->createBreakPolicy( $this->company_id, 130 ); //AutoDeduct 30min

		//Create Policy Group
		$dd->createPolicyGroup( 	$this->company_id,
									$policy_ids['meal'], //Meal
									NULL, //Exception
									NULL, //Holiday
									$policy_ids['overtime'], //OT
									NULL, //Premium
									NULL, //Round
									array($this->user_id), //Users
									$policy_ids['break'], //Break
									NULL, //Accrual
									NULL, //Expense
									NULL, //Absence
									array($this->policy_ids['regular'][12]) //Regular incl. meal/break
									);

		$date_epoch = TTDate::getBeginWeekEpoch( time() );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 5:30PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);

		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (8 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (8 * 3600) );

		//Regular Time (AutoDeduct Lunch)
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 100 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $this->policy_ids['pay_code'][190] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (-1 * 3600) );

		//Regular Time (AutoDeduct Break)
		$this->assertEquals( $udt_arr[$date_epoch][3]['object_type_id'], 110 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][3]['pay_code_id'], $this->policy_ids['pay_code'][192] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][3]['total_time'], (-0.5 * 3600) );

		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 4 );

		return TRUE;
	}

	/**
	 * @group OvertimePolicy_testAutoDeductMealAndBreakAndOverTimePolicyB
	 */
	function testAutoDeductMealAndBreakAndOverTimePolicyB() {
		global $dd;

		$policy_ids['pay_formula_policy'][] = $this->createPayFormulaPolicy( $this->company_id, 200 ); //OT1.5
		$policy_ids['pay_formula_policy'][] = $this->createPayFormulaPolicy( $this->company_id, 210 ); //OT2.0
		$policy_ids['pay_formula_policy'][] = $this->createPayFormulaPolicy( $this->company_id, 220 ); //OT2.5

		$policy_ids['pay_code'][] = $this->createPayCode( $this->company_id, 100, $policy_ids['pay_formula_policy'][0] );
		$policy_ids['pay_code'][] = $this->createPayCode( $this->company_id, 110, $policy_ids['pay_formula_policy'][1] );
		$policy_ids['pay_code'][] = $this->createPayCode( $this->company_id, 120, $policy_ids['pay_formula_policy'][2] );
		$policy_ids['pay_code'][] = $this->createPayCode( $this->company_id, 190, $policy_ids['pay_formula_policy'][0] );

		//Don't include meal/break in overtime. Include it in Regular time instead.
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 100, $this->policy_ids['contributing_shift_policy'][10], $policy_ids['pay_code'][0] );
		//$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 110, $this->policy_ids['contributing_shift_policy'][10], $policy_ids['pay_code'][1] );
		//$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 120, $this->policy_ids['contributing_shift_policy'][10], $policy_ids['pay_code'][2] );

		$policy_ids['meal'][] = $this->createMealPolicy( $this->company_id, 120 ); //AutoDeduct 1hr
		//$policy_ids['break'][] = $this->createBreakPolicy( $this->company_id, 120 ); //AutoDeduct 15min
		//$policy_ids['break'][] = $this->createBreakPolicy( $this->company_id, 121 ); //AutoDeduct 15min
		$policy_ids['break'][] = $this->createBreakPolicy( $this->company_id, 130 ); //AutoDeduct 30min

		//Create Policy Group
		$dd->createPolicyGroup( 	$this->company_id,
									$policy_ids['meal'], //Meal
									NULL, //Exception
									NULL, //Holiday
									$policy_ids['overtime'], //OT
									NULL, //Premium
									NULL, //Round
									array($this->user_id), //Users
									$policy_ids['break'], //Break
									NULL, //Accrual
									NULL, //Expense
									NULL, //Absence
									array($this->policy_ids['regular'][12]) //Regular incl. meal/break
									);

		$date_epoch = TTDate::getBeginWeekEpoch( time() );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 6:00PM'),
								array(
											'in_type_id' => 10,
											'out_type_id' => 10,
											'branch_id' => 0,
											'department_id' => 0,
											'job_id' => 0,
											'job_item_id' => 0,
										),
								TRUE
								);

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);

		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (8.5 * 3600) );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (8 * 3600) );

		//OverTime
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 30 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $policy_ids['pay_code'][0] ); //Overtime
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (0.5 * 3600) );

		//Regular Time (AutoDeduct Lunch)
		$this->assertEquals( $udt_arr[$date_epoch][3]['object_type_id'], 100 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][3]['pay_code_id'], $this->policy_ids['pay_code'][190] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][3]['total_time'], (-1 * 3600) );

		//Regular Time (AutoDeduct Break)
		$this->assertEquals( $udt_arr[$date_epoch][4]['object_type_id'], 110 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][4]['pay_code_id'], $this->policy_ids['pay_code'][192] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][4]['total_time'], (-0.5 * 3600) );

		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 5 );

		return TRUE;
	}

	//
	// Test OverTime Policy Differential Criteria.
	//

	/**
	 * @group OvertimePolicy_testDifferentialDailyOverTimePolicyA
	 */
	function testDifferentialDailyOverTimePolicyA() {
		return TRUE;
	}

	/**
	 * @group OvertimePolicy_testDifferentialDailyOverTimePolicyB
	 */
	function testDifferentialDailyOverTimePolicyB() {
		return TRUE;
	}

	/**
	 * @group OvertimePolicy_testDifferentialDailyOverTimePolicyC
	 */
	function testDifferentialDailyOverTimePolicyC() {
		return TRUE;
	}

	/**
	 * @group OvertimePolicy_testDifferentialDailyOverTimePolicyD
	 */
	function testDifferentialDailyOverTimePolicyD() {
		return TRUE;
	}

	/**
	 * @group OvertimePolicy_testDifferentialDailyOverTimePolicyD2
	 */
	function testDifferentialDailyOverTimePolicyD2() {
		return TRUE;
	}

	/**
	 * @group OvertimePolicy_testDifferentialDailyOverTimePolicyE1
	 */
	function testDifferentialDailyOverTimePolicyE1() {
		return TRUE;
	}

	/**
	 * @group OvertimePolicy_testDifferentialWeeklyOverTimePolicyA
	 */
	function testDifferentialWeeklyOverTimePolicyA() {
		return TRUE;
	}

	/**
	 * @group OvertimePolicy_testDifferentialDailyAndWeeklyOverTimePolicyA
	 */
	function testDifferentialDailyAndWeeklyOverTimePolicyA() {
		return TRUE;
	}

	/**
	 * @group OvertimePolicy_testDifferentialDailyAndWeeklyOverTimePolicyB
	 */
	function testDifferentialDailyAndWeeklyOverTimePolicyB() {
		return TRUE;
	}

	/**
	 * @group OvertimePolicy_testDifferentialDailyAndWeeklyOverTimePolicyC
	 */
	function testDifferentialDailyAndWeeklyOverTimePolicyC() {
		return TRUE;
	}

	/**
	 * @group OvertimePolicy_testDifferentialDailyAndWeeklyOverTimePolicyD
	 */
	function testDifferentialDailyAndWeeklyOverTimePolicyD() {
		return TRUE;
	}

	/**
	 * @group OvertimePolicy_testDifferentialDailyAndWeeklyOverTimePolicyE
	 */
	function testDifferentialDailyAndWeeklyOverTimePolicyE() {
		return TRUE;
	}

	/**
	 * @group OvertimePolicy_testDifferentialDailyAndWeeklyOverTimePolicyF
	 */
	function testDifferentialDailyAndWeeklyOverTimePolicyF() {
		return TRUE;
	}

	/**
	 * @group OvertimePolicy_testDifferentialDailyAndWeeklyOverTimePolicyG
	 */
	function testDifferentialDailyAndWeeklyOverTimePolicyG() {
		return TRUE;
	}

	/**
	 * @group OvertimePolicy_testDifferentialDailyAndWeeklyOverTimePolicyH
	 */
	function testDifferentialDailyAndWeeklyOverTimePolicyH() {

		return TRUE;
	}

	/**
	 * @group OvertimePolicy_testAverageHourlyRateOverTimePolicyA
	 */
	function testAverageHourlyRateOverTimePolicyA() {
		return TRUE;
	}


	/**
	 * @group OvertimePolicy_testBiWeeklyOverTimeWeekModifierA
	 */
	function testBiWeeklyOverTimeWeekModifierA() {
		$cp = TTnew( 'CalculatePolicy' );

		/*
		 *
		 * Bi-Weekly Pay Period Start Dates:
		 06-Dec-15
		 20-Dec-15
		 03-Jan-16
		 17-Jan-16
		 31-Jan-16
		 */
		$start_date = $first_pay_period_start_date = TTDate::getMiddleDayEpoch( strtotime('04-Jul-2010') ); //Sunday
		$end_date = TTDate::getMiddleDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (365 * 86400) );
		while( $start_date <= $end_date ) {
			$pp_start_date = $start_date;
			$pp_end_date = ( strtotime( 'next Sunday', strtotime( 'next Sunday', $start_date ) ) - 43200 ); //BiWeekly, so do this twice.
			Debug::text('PP Start Date: '. TTDate::getDate('DATE', $pp_start_date) .' End Date: '. TTDate::getDate('DATE', $pp_end_date), __FILE__, __LINE__, __METHOD__, 10);

			$x = 1;
			for( $i = $pp_start_date; $i <= $pp_end_date; $i += 86400 ) {
				$i = TTDate::getMiddleDayEpoch( $i ); //Make sure DST doesn't make the date walk causing failures after 4-5years.

				if ( $x >= 8 AND $x <= 14 ) {
					$is_second_week = TRUE;
				} else {
					$is_second_week = FALSE;
				}
				//$retval = $cp->isSecondBiWeeklyOverTimeWeek( $i, $first_pay_period_start_date, 0 ); //Start Week on Wed.
				$ot_period_dates = $cp->getOverTimePeriodDates( $i, 2, $first_pay_period_start_date, 0 );
				$retval = $ot_period_dates['is_last_week'];
				//Debug::text('  Test Date: '. TTDate::getDate('DATE', $i) .' Is Second Week: '. (int)$retval .' Assert: '. (int)$is_second_week, __FILE__, __LINE__, __METHOD__,10);

				$this->assertEquals( $retval, $is_second_week );

				$x++;
			}

			$start_date = TTDate::getBeginDayEpoch( $pp_end_date + 43400 );
		}
	}

	/**
	 * @group OvertimePolicy_testBiWeeklyOverTimeWeekModifierB
	 */
	function testBiWeeklyOverTimeWeekModifierB() {
		$cp = TTnew( 'CalculatePolicy' );

		$start_date = $first_pay_period_start_date = TTDate::getMiddleDayEpoch( strtotime('30-Jun-2010') ); //Wed - Must span at least 5yrs for a full test.
		$end_date = TTDate::getMiddleDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (365 * 86400) );
		while( $start_date <= $end_date ) {
			$pp_start_date = $start_date;
			$pp_end_date = ( strtotime( 'next Wednesday', strtotime( 'next Wednesday', $start_date ) ) - 43200 ); //BiWeekly, so do this twice.
			Debug::text('PP Start Date: '. TTDate::getDate('DATE+TIME', $pp_start_date) .' End Date: '. TTDate::getDate('DATE+TIME', $pp_end_date), __FILE__, __LINE__, __METHOD__, 10);

			$x = 1;
			for( $i = $pp_start_date; $i <= $pp_end_date; $i += 86400 ) {
				$i = TTDate::getMiddleDayEpoch( $i ); //Make sure DST doesn't make the date walk causing failures after 4-5years.

				if ( $x >= 8 AND $x <= 14 ) {
					$is_second_week = TRUE;
				} else {
					$is_second_week = FALSE;
				}
				//$retval = $cp->isSecondBiWeeklyOverTimeWeek( $i, $first_pay_period_start_date, 3 ); //Start Week on Wed.
				$ot_period_dates = $cp->getOverTimePeriodDates( $i, 2, $first_pay_period_start_date, 3 );
				$retval = $ot_period_dates['is_last_week'];
				Debug::text('  X: '. $x .' Test Date: '. TTDate::getDate('DATE+TIME', $i) .' Is Second Week: '. (int)$retval .' Assert: '. (int)$is_second_week, __FILE__, __LINE__, __METHOD__, 10);

				$this->assertEquals( $retval, $is_second_week );

				$x++;
			}

			$start_date = TTDate::getBeginDayEpoch( $pp_end_date + 43400 );
		}
	}

	/**
	 * @group OvertimePolicy_testManualTimeSheetDailyOverTimePolicyA
	 */
	function testManualTimeSheetDailyOverTimePolicyA() {
		global $dd;

		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 200 ); //OT1.5
		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 210 ); //OT2.0
		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 220 ); //OT2.5

		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 100, $policy_ids['pay_formula_policy'][0] );
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 110, $policy_ids['pay_formula_policy'][1] );
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 120, $policy_ids['pay_formula_policy'][2] );

		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 100, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][0] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 110, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][1] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 120, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][2] );

		//Create Policy Group
		$dd->createPolicyGroup( 	$this->company_id,
								   NULL, //Meal
								   NULL, //Exception
								   NULL, //Holiday
								   $policy_ids['overtime'], //OT
								   NULL, //Premium
								   NULL, //Round
								   array($this->user_id), //Users
								   NULL, //Break
								   NULL, //Accrual
								   NULL, //Expense
								   NULL, //Absence
								   array($this->policy_ids['regular'][12]) //Regular
		);


		$date_epoch = TTDate::getBeginWeekEpoch( time(), 1 ); //Start on Monday to avoid problems with Sunday and DST which over.
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createUserDateTotal( $this->user_id, $date_epoch, (3600 * 12), $this->branch_ids[0] );

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);

		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (12 * 3600) );
		$this->assertEquals( $udt_arr[$date_epoch][0]['start_time_stamp'], FALSE );
		$this->assertEquals( $udt_arr[$date_epoch][0]['end_time_stamp'], FALSE );
		$this->assertEquals( $udt_arr[$date_epoch][0]['hourly_rate'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['hourly_rate_with_burden'], 0 );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (8 * 3600) );
		$this->assertEquals( $udt_arr[$date_epoch][1]['start_time_stamp'], strtotime($date_stamp.' 12:00AM') );
		$this->assertEquals( $udt_arr[$date_epoch][1]['end_time_stamp'], strtotime($date_stamp.' 8:00AM') );
		$this->assertEquals( $udt_arr[$date_epoch][1]['hourly_rate'], (1 * 21.50) );
		$this->assertEquals( $udt_arr[$date_epoch][1]['hourly_rate_with_burden'], (1 * 21.50 * 1.135) ); //13.5%
		//Overtime 1
		$this->assertEquals( $udt_arr[$date_epoch][4]['object_type_id'], 30 ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][4]['pay_code_id'], $policy_ids['pay_code'][0] );
		$this->assertEquals( $udt_arr[$date_epoch][4]['total_time'], (1 * 3600) );
		$this->assertEquals( $udt_arr[$date_epoch][4]['start_time_stamp'], strtotime($date_stamp.' 8:00AM') );
		$this->assertEquals( $udt_arr[$date_epoch][4]['end_time_stamp'], strtotime($date_stamp.' 9:00AM') );
		$this->assertEquals( $udt_arr[$date_epoch][4]['hourly_rate'], (1.5 * 21.50) );
		$this->assertEquals( Misc::MoneyFormat( $udt_arr[$date_epoch][4]['hourly_rate_with_burden'], FALSE ), Misc::MoneyFormat( (1.5 * 21.50 * 1.135), FALSE) ); //13.5%
		//Overtime 2
		$this->assertEquals( $udt_arr[$date_epoch][3]['object_type_id'], 30 ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][3]['pay_code_id'], $policy_ids['pay_code'][1] );
		$this->assertEquals( $udt_arr[$date_epoch][3]['total_time'], (1 * 3600) );
		$this->assertEquals( $udt_arr[$date_epoch][3]['start_time_stamp'], strtotime($date_stamp.' 9:00AM') );
		$this->assertEquals( $udt_arr[$date_epoch][3]['end_time_stamp'], strtotime($date_stamp.' 10:00AM') );
		$this->assertEquals( $udt_arr[$date_epoch][3]['hourly_rate'], (2.0 * 21.50) );
		$this->assertEquals( $udt_arr[$date_epoch][3]['hourly_rate_with_burden'], (2.0 * 21.50 * 1.135) ); //13.5%
		//Overtime 3
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 30 ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $policy_ids['pay_code'][2] );
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (2 * 3600) );
		$this->assertEquals( $udt_arr[$date_epoch][2]['start_time_stamp'], strtotime($date_stamp.' 10:00AM') );
		$this->assertEquals( $udt_arr[$date_epoch][2]['end_time_stamp'], strtotime($date_stamp.' 12:00PM') );
		$this->assertEquals( $udt_arr[$date_epoch][2]['hourly_rate'], (2.5 * 21.50) );
		$this->assertEquals( Misc::MoneyFormat( $udt_arr[$date_epoch][2]['hourly_rate_with_burden'] ), Misc::MoneyFormat( (2.5 * 21.50 * 1.135) ) ); //13.5%

		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 5 );
		return TRUE;
	}

	/**
	 * @group OvertimePolicy_testManualTimeSheetDailyOverTimePolicyB
	 */
	function testManualTimeSheetDailyOverTimePolicyB() {
		global $dd;

		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 200 ); //OT1.5
		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 210 ); //OT2.0
		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 220 ); //OT2.5

		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 100, $policy_ids['pay_formula_policy'][0] );
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 110, $policy_ids['pay_formula_policy'][1] );
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 120, $policy_ids['pay_formula_policy'][2] );

		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 100, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][0] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 110, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][1] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 120, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][2] );

		//Create Policy Group
		$dd->createPolicyGroup( 	$this->company_id,
								   NULL, //Meal
								   NULL, //Exception
								   NULL, //Holiday
								   $policy_ids['overtime'], //OT
								   NULL, //Premium
								   NULL, //Round
								   array($this->user_id), //Users
								   NULL, //Break
								   NULL, //Accrual
								   NULL, //Expense
								   NULL, //Absence
								   array($this->policy_ids['regular'][12]) //Regular
		);


		$date_epoch = TTDate::getBeginWeekEpoch( time(), 1 ); //Start on Monday to avoid problems with Sunday and DST which over.
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createUserDateTotal( $this->user_id, $date_epoch, (3600 * 2), $this->branch_ids[0] );
		$dd->createUserDateTotal( $this->user_id, $date_epoch, (3600 * 11), $this->branch_ids[1] );

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);

		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (13 * 3600) );
		$this->assertEquals( $udt_arr[$date_epoch][0]['start_time_stamp'], FALSE );
		$this->assertEquals( $udt_arr[$date_epoch][0]['end_time_stamp'], FALSE );
		$this->assertEquals( $udt_arr[$date_epoch][0]['hourly_rate'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['hourly_rate_with_burden'], 0 );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (2 * 3600) );
		$this->assertEquals( $udt_arr[$date_epoch][1]['start_time_stamp'], strtotime($date_stamp.' 12:00AM') );
		$this->assertEquals( $udt_arr[$date_epoch][1]['end_time_stamp'], strtotime($date_stamp.' 2:00AM') );
		$this->assertEquals( $udt_arr[$date_epoch][1]['hourly_rate'], (1 * 21.50) );
		$this->assertEquals( $udt_arr[$date_epoch][1]['hourly_rate_with_burden'], (1 * 21.50 * 1.135) ); //13.5%
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (6 * 3600) );
		$this->assertEquals( $udt_arr[$date_epoch][2]['start_time_stamp'], strtotime($date_stamp.' 2:00AM') );
		$this->assertEquals( $udt_arr[$date_epoch][2]['end_time_stamp'], strtotime($date_stamp.' 8:00AM') );
		$this->assertEquals( $udt_arr[$date_epoch][2]['hourly_rate'], (1 * 21.50) );
		$this->assertEquals( $udt_arr[$date_epoch][2]['hourly_rate_with_burden'], (1 * 21.50 * 1.135) ); //13.5%
		//Overtime 3 (>10)
		$this->assertEquals( $udt_arr[$date_epoch][3]['object_type_id'], 30 ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][3]['pay_code_id'], $policy_ids['pay_code'][2] );
		$this->assertEquals( $udt_arr[$date_epoch][3]['total_time'], (3 * 3600) );
		$this->assertEquals( $udt_arr[$date_epoch][3]['start_time_stamp'], strtotime($date_stamp.' 10:00AM') );
		$this->assertEquals( $udt_arr[$date_epoch][3]['end_time_stamp'], strtotime($date_stamp.' 1:00PM') );
		$this->assertEquals( $udt_arr[$date_epoch][3]['hourly_rate'], (2.5 * 21.50) );
		$this->assertEquals( Misc::MoneyFormat( $udt_arr[$date_epoch][3]['hourly_rate_with_burden'] ), Misc::MoneyFormat( (2.5 * 21.50 * 1.135) ) ); //13.5%
		//Overtime 2 (>9)
		$this->assertEquals( $udt_arr[$date_epoch][4]['object_type_id'], 30 ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][4]['pay_code_id'], $policy_ids['pay_code'][1] );
		$this->assertEquals( $udt_arr[$date_epoch][4]['total_time'], (1 * 3600) );
		$this->assertEquals( $udt_arr[$date_epoch][4]['start_time_stamp'], strtotime($date_stamp.' 9:00AM') );
		$this->assertEquals( $udt_arr[$date_epoch][4]['end_time_stamp'], strtotime($date_stamp.' 10:00AM') );
		$this->assertEquals( $udt_arr[$date_epoch][4]['hourly_rate'], (2.0 * 21.50) );
		$this->assertEquals( $udt_arr[$date_epoch][4]['hourly_rate_with_burden'], (2.0 * 21.50 * 1.135) ); //13.5%
		//Overtime 1 (>8)
		$this->assertEquals( $udt_arr[$date_epoch][5]['object_type_id'], 30 ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][5]['pay_code_id'], $policy_ids['pay_code'][0] );
		$this->assertEquals( $udt_arr[$date_epoch][5]['total_time'], (1 * 3600) );
		$this->assertEquals( $udt_arr[$date_epoch][5]['start_time_stamp'], strtotime($date_stamp.' 8:00AM') );
		$this->assertEquals( $udt_arr[$date_epoch][5]['end_time_stamp'], strtotime($date_stamp.' 9:00AM') );
		$this->assertEquals( $udt_arr[$date_epoch][5]['hourly_rate'], (1.5 * 21.50) );
		$this->assertEquals( Misc::MoneyFormat( $udt_arr[$date_epoch][5]['hourly_rate_with_burden'], FALSE ), Misc::MoneyFormat( (1.5 * 21.50 * 1.135), FALSE) ); //13.5%

		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 6 );
		return TRUE;
	}

	/**
	 * @group OvertimePolicy_testManualTimeSheetDailyOverTimePolicyC
	 */
	function testManualTimeSheetDailyOverTimePolicyC() {
		global $dd;

		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 200 ); //OT1.5
		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 210 ); //OT2.0
		$policy_ids['pay_formula_policy'][]  = $this->createPayFormulaPolicy( $this->company_id, 220 ); //OT2.5

		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 100, $policy_ids['pay_formula_policy'][0] );
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 110, $policy_ids['pay_formula_policy'][1] );
		$policy_ids['pay_code'][]  = $this->createPayCode( $this->company_id, 120, $policy_ids['pay_formula_policy'][2] );

		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 100, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][0] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 110, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][1] );
		$policy_ids['overtime'][] = $this->createOverTimePolicy( $this->company_id, 120, $this->policy_ids['contributing_shift_policy'][12], $policy_ids['pay_code'][2] );

		//Create Policy Group
		$dd->createPolicyGroup( 	$this->company_id,
								   NULL, //Meal
								   NULL, //Exception
								   NULL, //Holiday
								   $policy_ids['overtime'], //OT
								   NULL, //Premium
								   NULL, //Round
								   array($this->user_id), //Users
								   NULL, //Break
								   NULL, //Accrual
								   NULL, //Expense
								   NULL, //Absence
								   array($this->policy_ids['regular'][12]) //Regular
		);


		$date_epoch = TTDate::getBeginWeekEpoch( time() );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		//$dd->createUserDateTotal( $this->user_id, $date_epoch, (3600 * 2), $this->branch_ids[0] );
		$dd->createPunchPair( 	$this->user_id,
								 strtotime($date_stamp.' 8:00AM'),
								 strtotime($date_stamp.' 10:00AM'),
								 array(
										 'in_type_id' => 10,
										 'out_type_id' => 10,
										 'branch_id' => $this->branch_ids[0],
										 'department_id' => 0,
										 'job_id' => 0,
										 'job_item_id' => 0,
								 ),
								 TRUE
		);

		$dd->createUserDateTotal( $this->user_id, $date_epoch, (3600 * 11), $this->branch_ids[1] );

		$udt_arr = $this->getUserDateTotalArray( $date_epoch, $date_epoch );
		//print_r($udt_arr);

		//Total Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['object_type_id'], 5 ); //5=System Total
		$this->assertEquals( $udt_arr[$date_epoch][0]['pay_code_id'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['total_time'], (13 * 3600) );
		$this->assertEquals( $udt_arr[$date_epoch][0]['start_time_stamp'], strtotime($date_stamp.' 8:00AM') ); //Punch Start time
		$this->assertEquals( $udt_arr[$date_epoch][0]['end_time_stamp'], strtotime($date_stamp.' 10:00AM') ); //Punch End Time
		$this->assertEquals( $udt_arr[$date_epoch][0]['hourly_rate'], 0 );
		$this->assertEquals( $udt_arr[$date_epoch][0]['hourly_rate_with_burden'], 0 );
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][1]['total_time'], (2 * 3600) );
		$this->assertEquals( $udt_arr[$date_epoch][1]['start_time_stamp'], strtotime($date_stamp.' 8:00AM') );
		$this->assertEquals( $udt_arr[$date_epoch][1]['end_time_stamp'], strtotime($date_stamp.' 10:00AM') );
		$this->assertEquals( $udt_arr[$date_epoch][1]['hourly_rate'], (1 * 21.50) );
		$this->assertEquals( $udt_arr[$date_epoch][1]['hourly_rate_with_burden'], (1 * 21.50 * 1.135) ); //13.5%
		//Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 20 ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $this->policy_ids['pay_code'][100] ); //Regular Time
		$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (6 * 3600) );
		$this->assertEquals( $udt_arr[$date_epoch][2]['start_time_stamp'], strtotime($date_stamp.' 10:00AM') );
		$this->assertEquals( $udt_arr[$date_epoch][2]['end_time_stamp'], strtotime($date_stamp.' 4:00PM') );
		$this->assertEquals( $udt_arr[$date_epoch][2]['hourly_rate'], (1 * 21.50) );
		$this->assertEquals( $udt_arr[$date_epoch][2]['hourly_rate_with_burden'], (1 * 21.50 * 1.135) ); //13.5%

		//Overtime 3 (>10)
		$this->assertEquals( $udt_arr[$date_epoch][3]['object_type_id'], 30 ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][3]['pay_code_id'], $policy_ids['pay_code'][2] );
		$this->assertEquals( $udt_arr[$date_epoch][3]['total_time'], (3 * 3600) );
		$this->assertEquals( $udt_arr[$date_epoch][3]['start_time_stamp'], strtotime($date_stamp.' 6:00PM') );
		$this->assertEquals( $udt_arr[$date_epoch][3]['end_time_stamp'], strtotime($date_stamp.' 9:00PM') );
		$this->assertEquals( $udt_arr[$date_epoch][3]['hourly_rate'], (2.5 * 21.50) );
		$this->assertEquals( Misc::MoneyFormat( $udt_arr[$date_epoch][3]['hourly_rate_with_burden'] ), Misc::MoneyFormat( (2.5 * 21.50 * 1.135) ) ); //13.5%
		//Overtime 2 (>9)
		$this->assertEquals( $udt_arr[$date_epoch][4]['object_type_id'], 30 ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][4]['pay_code_id'], $policy_ids['pay_code'][1] );
		$this->assertEquals( $udt_arr[$date_epoch][4]['total_time'], (1 * 3600) );
		$this->assertEquals( $udt_arr[$date_epoch][4]['start_time_stamp'], strtotime($date_stamp.' 5:00PM') );
		$this->assertEquals( $udt_arr[$date_epoch][4]['end_time_stamp'], strtotime($date_stamp.' 6:00PM') );
		$this->assertEquals( $udt_arr[$date_epoch][4]['hourly_rate'], (2.0 * 21.50) );
		$this->assertEquals( $udt_arr[$date_epoch][4]['hourly_rate_with_burden'], (2.0 * 21.50 * 1.135) ); //13.5%
		//Overtime 1 (>8)
		$this->assertEquals( $udt_arr[$date_epoch][5]['object_type_id'], 30 ); //OverTime
		$this->assertEquals( $udt_arr[$date_epoch][5]['pay_code_id'], $policy_ids['pay_code'][0] );
		$this->assertEquals( $udt_arr[$date_epoch][5]['total_time'], (1 * 3600) );
		$this->assertEquals( $udt_arr[$date_epoch][5]['start_time_stamp'], strtotime($date_stamp.' 4:00PM') );
		$this->assertEquals( $udt_arr[$date_epoch][5]['end_time_stamp'], strtotime($date_stamp.' 5:00PM') );
		$this->assertEquals( $udt_arr[$date_epoch][5]['hourly_rate'], (1.5 * 21.50) );
		$this->assertEquals( Misc::MoneyFormat( $udt_arr[$date_epoch][5]['hourly_rate_with_burden'], FALSE ), Misc::MoneyFormat( (1.5 * 21.50 * 1.135), FALSE) ); //13.5%

		//Make sure no other hours
		$this->assertEquals( count($udt_arr[$date_epoch]), 6 );
		return TRUE;
	}
}
?>