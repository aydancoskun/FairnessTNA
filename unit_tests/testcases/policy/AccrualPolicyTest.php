<?php

class AccrualPolicyTest extends PHPUnit\Framework\TestCase {
	protected $company_id = NULL;
	protected $user_id = NULL;
	protected $pay_period_schedule_id = NULL;
	protected $pay_period_objs = NULL;
	protected $pay_stub_account_link_arr = NULL;

	public function setUp(): void {
		global $dd;
		Debug::text('Running setUp(): ', __FILE__, __LINE__, __METHOD__, 10);

		TTDate::setTimeZone('PST8PDT', TRUE); //Due to being a singleton and PHPUnit resetting the state, always force the timezone to be set.

		$dd = new DemoData();
		$dd->setEnableQuickPunch( FALSE ); //Helps prevent duplicate punch IDs and validation failures.
		$dd->setUserNamePostFix( '_'.uniqid( NULL, TRUE ) ); //Needs to be super random to prevent conflicts and random failing tests.
		$this->company_id = $dd->createCompany();
		$this->legal_entity_id = $dd->createLegalEntity( $this->company_id, 10 );
		Debug::text('Company ID: '. $this->company_id, __FILE__, __LINE__, __METHOD__, 10);

		//$dd->createPermissionGroups( $this->company_id, 40 ); //Administrator only.

		$dd->createCurrency( $this->company_id, 10 );
		$dd->createUserWageGroups( $this->company_id );

		$this->user_id = $dd->createUser( $this->company_id, $this->legal_entity_id, 100 );
		$user_obj = $this->getUserObject( $this->user_id );
		//Use a consistent hire date, otherwise its difficult to get things correct due to the hire date being in different parts or different pay periods.
		//Make sure it is not on a pay period start date though.
		$user_obj->setHireDate( strtotime('05-Mar-2001') );
		$user_obj->Save(FALSE);

		$this->createPayPeriodSchedule();
		$this->createPayPeriods( TTDate::getBeginDayEpoch( TTDate::getBeginYearEpoch( $user_obj->getHireDate() ) ) );
		$this->getAllPayPeriods();

		$this->assertGreaterThan( 0, $this->company_id );
		$this->assertGreaterThan( 0, $this->user_id );
	}

	public function tearDown(): void {
		Debug::text('Running tearDown(): ', __FILE__, __LINE__, __METHOD__, 10);

		//$this->deleteAllSchedules();
	}

	function createPayPeriodSchedule() {
		$ppsf = new PayPeriodScheduleFactory();

		$ppsf->setCompany( $this->company_id );
		$ppsf->setName( 'Semi-Monthly' );
		$ppsf->setDescription( '' );
		$ppsf->setType( 30 );
		$ppsf->setStartWeekDay( 0 );

		$anchor_date = TTDate::getBeginWeekEpoch( ( TTDate::getBeginYearEpoch( time() ) - (86400 * (7 * 6) ) ) ); //Start 6 weeks ago

		$ppsf->setAnchorDate( $anchor_date );

		$ppsf->setPrimaryDayOfMonth( 1 );
		$ppsf->setSecondaryDayOfMonth( 16 );
		$ppsf->setPrimaryTransactionDayOfMonth( 20 );
		$ppsf->setSecondaryTransactionDayOfMonth( 5 );

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

	function createPayPeriods( $start_date = NULL ) {
		if ( $start_date == '' ) {
			$start_date = TTDate::getBeginWeekEpoch( ( TTDate::getBeginYearEpoch( time() ) - (86400 * (7 * 6) ) ) );
		}

		$max_pay_periods = 192; //Make a lot of pay periods as we need to test 6 years worth of accruals for different milestones.

		$ppslf = new PayPeriodScheduleListFactory();
		$ppslf->getById( $this->pay_period_schedule_id );
		if ( $ppslf->getRecordCount() > 0 ) {
			$pps_obj = $ppslf->getCurrent();

			for ( $i = 0; $i < $max_pay_periods; $i++ ) {
				if ( $i == 0 ) {
					$end_date = $start_date;
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

	function createPayFormulaPolicy( $company_id, $type, $accrual_policy_account_id = 0, $wage_source_contributing_shift_policy_id = 0, $time_source_contributing_shift_policy_id = 0 ) {
		$pfpf = TTnew( 'PayFormulaPolicyFactory' ); /** @var PayFormulaPolicyFactory $pfpf */
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
			case 910:
				$pfpf->setName( 'Bank' );
				$pfpf->setPayType( 10 ); //Pay Multiplied By Factor
				$pfpf->setRate( 1.0 );
				$pfpf->setAccrualPolicyAccount( $accrual_policy_account_id );
				$pfpf->setAccrualRate( -1.0 );
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

	function createAccrualPolicyUserModifier( $accrual_policy_id, $user_id, $length_of_service_date = NULL, $accrual_rate = NULL ) {
		$apumf = TTnew( 'AccrualPolicyUserModifierFactory' ); /** @var AccrualPolicyUserModifierFactory $apumf */
		$apumf->setAccrualPolicy( $accrual_policy_id );
		$apumf->setUser( $user_id );

		if ( $length_of_service_date !== NULL ) {
			$apumf->setLengthOfServiceDate( $length_of_service_date );
		}

		if ( $accrual_rate !== NULL ) {
			$apumf->setAccrualRateModifier( $accrual_rate );
		}

		if ( $apumf->isValid() ) {
			$insert_id = $apumf->Save();
			Debug::Text('AccrualPolicyUserModifier ID: '. $insert_id, __FILE__, __LINE__, __METHOD__, 10);

			return $insert_id;
		}

		Debug::Text('Failed Creating AccrualPolicyUserModifier!', __FILE__, __LINE__, __METHOD__, 10);

		return FALSE;
	}

	function createAccrualPolicyAccount( $company_id, $type ) {
		$apaf = TTnew( 'AccrualPolicyAccountFactory' ); /** @var AccrualPolicyAccountFactory $apaf */

		$apaf->setCompany( $company_id );

		switch ( $type ) {
			case 10: //Bank Time
				$apaf->setName( 'Unit Test' );
				break;
			case 20: //Calendar Based: Vacation/PTO
				$apaf->setName( 'Personal Time Off (PTO)/Vacation' );
				break;
			case 30: //Calendar Based: Vacation/PTO
				$apaf->setName( 'Sick Time' );
				break;
		}

		if ( $apaf->isValid() ) {
			$insert_id = $apaf->Save();
			Debug::Text('Accrual Policy Account ID: '. $insert_id, __FILE__, __LINE__, __METHOD__, 10);

			return $insert_id;
		}

		Debug::Text('Failed Creating Accrual Policy Account!', __FILE__, __LINE__, __METHOD__, 10);

		return FALSE;
	}
	function createAccrualPolicy( $company_id, $type, $accrual_policy_account_id, $contributing_shift_policy_id = 0 ) {
		$apf = TTnew( 'AccrualPolicyFactory' ); /** @var AccrualPolicyFactory $apf */

		$apf->setCompany( $company_id );

		switch ( $type ) {
			case 20: //Calendar Based: Check minimum employed days
				$apf->setName( 'Calendar: Minimum Employed' );
				$apf->setType( 20 );

				$apf->setApplyFrequency( 10 ); //Each Pay Period

				$apf->setMilestoneRolloverHireDate( TRUE );

				$apf->setMinimumEmployedDays( 9999 );
				$apf->setAccrualPolicyAccount( $accrual_policy_account_id );
				break;
			case 30: //Calendar Based: Check milestone not applied yet.
				$apf->setName( 'Calendar: Milestone not applied' );
				$apf->setType( 20 );

				$apf->setApplyFrequency( 10 ); //Each Pay Period

				$apf->setMilestoneRolloverHireDate( TRUE );

				$apf->setMinimumEmployedDays( 9999 );
				$apf->setAccrualPolicyAccount( $accrual_policy_account_id );
				break;
			case 40: //Calendar Based: Pay Period with one milestone
				$apf->setName( 'Calendar: 1 milestone' );
				$apf->setType( 20 );

				$apf->setApplyFrequency( 10 ); //Each Pay Period

				$apf->setMilestoneRolloverHireDate( TRUE );

				$apf->setMinimumEmployedDays( 0 );
				$apf->setAccrualPolicyAccount( $accrual_policy_account_id );
				break;
			case 50: //Calendar Based: Pay Period with 2 milestones
				$apf->setName( 'Calendar: 2 milestone' );
				$apf->setType( 20 );

				$apf->setApplyFrequency( 10 ); //Each Pay Period

				$apf->setMilestoneRolloverHireDate( TRUE );

				$apf->setMinimumEmployedDays( 0 );
				$apf->setAccrualPolicyAccount( $accrual_policy_account_id );
				break;
			case 60: //Calendar Based: Pay Period with 5 milestones
				$apf->setName( 'Calendar: 5 milestone' );
				$apf->setType( 20 );

				$apf->setApplyFrequency( 10 ); //Each Pay Period

				$apf->setMilestoneRolloverHireDate( TRUE );

				$apf->setMinimumEmployedDays( 0 );
				$apf->setAccrualPolicyAccount( $accrual_policy_account_id );
				break;
			case 70: //Calendar Based: Pay Period with 5 milestones rolling over on January 1st.
				$apf->setName( 'Calendar: 5 milestone' );
				$apf->setType( 20 );

				$apf->setApplyFrequency( 10 ); //Each Pay Period

				$apf->setMilestoneRolloverHireDate( FALSE );
				$apf->setMilestoneRolloverMonth( 1 );
				$apf->setMilestoneRolloverDayOfMonth( 1 );

				$apf->setMinimumEmployedDays( 0 );
				$apf->setAccrualPolicyAccount( $accrual_policy_account_id );
				break;
			case 80: //Calendar Based: Pay Period with 2 milestones
				$apf->setName( 'Calendar: 2 milestone' );
				$apf->setType( 20 );

				$apf->setApplyFrequency( 10 ); //Each Pay Period

				$apf->setMilestoneRolloverHireDate( TRUE );

				$apf->setMinimumEmployedDays( 0 );
				$apf->setAccrualPolicyAccount( $accrual_policy_account_id );
				break;


			case 200: //Calendar Based: Weekly with 2 milestones
				$apf->setName( 'Calendar: 2 milestone' );
				$apf->setType( 20 );

				$apf->setApplyFrequency( 40 ); //Weekly
				$apf->setApplyFrequencyDayOfWeek( 0 ); //Sunday

				$apf->setMilestoneRolloverHireDate( TRUE );

				$apf->setMinimumEmployedDays( 0 );
				$apf->setAccrualPolicyAccount( $accrual_policy_account_id );
				break;
			case 210: //Calendar Based: Weekly with 2 milestones
				$apf->setName( 'Calendar: 2 milestone' );
				$apf->setType( 20 );

				$apf->setApplyFrequency( 40 ); //Weekly
				$apf->setApplyFrequencyDayOfWeek( 3 ); //Wed

				$apf->setMilestoneRolloverHireDate( TRUE );

				$apf->setMinimumEmployedDays( 0 );
				$apf->setAccrualPolicyAccount( $accrual_policy_account_id );
				break;

			case 300: //Calendar Based: Monthly with 2 milestones
				$apf->setName( 'Calendar: 2 milestone' );
				$apf->setType( 20 );

				$apf->setApplyFrequency( 30 ); //Monthly
				$apf->setApplyFrequencyDayOfMonth( 1 );

				$apf->setMilestoneRolloverHireDate( TRUE );

				$apf->setMinimumEmployedDays( 0 );
				$apf->setAccrualPolicyAccount( $accrual_policy_account_id );
				break;
			case 310: //Calendar Based: Monthly with 2 milestones
				$apf->setName( 'Calendar: 2 milestone' );
				$apf->setType( 20 );

				$apf->setApplyFrequency( 30 ); //Monthly
				$apf->setApplyFrequencyDayOfMonth( 15 );

				$apf->setMilestoneRolloverHireDate( TRUE );

				$apf->setMinimumEmployedDays( 0 );
				$apf->setAccrualPolicyAccount( $accrual_policy_account_id );
				break;
			case 320: //Calendar Based: Monthly with 2 milestones
				$apf->setName( 'Calendar: 2 milestone' );
				$apf->setType( 20 );

				$apf->setApplyFrequency( 30 ); //Monthly
				$apf->setApplyFrequencyDayOfMonth( 31 );

				$apf->setMilestoneRolloverHireDate( TRUE );

				$apf->setMinimumEmployedDays( 0 );
				$apf->setAccrualPolicyAccount( $accrual_policy_account_id );
				break;
			case 350: //Calendar Based: Quarterly with 2 milestones
				$apf->setName( 'Calendar: 2 milestone' );
				$apf->setType( 20 );

				$apf->setApplyFrequency( 25 ); //Quarterly
				$apf->setApplyFrequencyDayOfMonth( 1 );
				$apf->setApplyFrequencyQuarterMonth( 1 );

				$apf->setMilestoneRolloverHireDate( TRUE );

				$apf->setMinimumEmployedDays( 0 );
				$apf->setAccrualPolicyAccount( $accrual_policy_account_id );
				break;
			case 360: //Calendar Based: Quarterly with 2 milestones
				$apf->setName( 'Calendar: 2 milestone' );
				$apf->setType( 20 );

				$apf->setApplyFrequency( 25 ); //Quarterly
				$apf->setApplyFrequencyDayOfMonth( 15 );
				$apf->setApplyFrequencyQuarterMonth( 2 );

				$apf->setMilestoneRolloverHireDate( TRUE );

				$apf->setMinimumEmployedDays( 0 );
				$apf->setAccrualPolicyAccount( $accrual_policy_account_id );
				break;
			case 370: //Calendar Based: Quarterly with 2 milestones
				$apf->setName( 'Calendar: 2 milestone' );
				$apf->setType( 20 );

				$apf->setApplyFrequency( 25 ); //Quarterly
				$apf->setApplyFrequencyDayOfMonth( 31 );
				$apf->setApplyFrequencyQuarterMonth( 3 );

				$apf->setMilestoneRolloverHireDate( TRUE );

				$apf->setMinimumEmployedDays( 0 );
				$apf->setAccrualPolicyAccount( $accrual_policy_account_id );
				break;

			case 400: //Calendar Based: Annually with 2 milestones
				$apf->setName( 'Calendar: 2 milestone' );
				$apf->setType( 20 );

				$apf->setApplyFrequency( 20 ); //Annually
				$apf->setApplyFrequencyMonth( 1 );
				$apf->setApplyFrequencyDayOfMonth( 1 );

				$apf->setMilestoneRolloverHireDate( TRUE );

				$apf->setMinimumEmployedDays( 0 );
				$apf->setAccrualPolicyAccount( $accrual_policy_account_id );
				break;
			case 410: //Calendar Based: Annually with 2 milestones
				$apf->setName( 'Calendar: 2 milestone' );
				$apf->setType( 20 );

				$apf->setApplyFrequency( 20 ); //Annually
				$apf->setApplyFrequencyMonth( 6 );
				$apf->setApplyFrequencyDayOfMonth( 15 );

				$apf->setMilestoneRolloverHireDate( TRUE );

				$apf->setMinimumEmployedDays( 0 );
				$apf->setAccrualPolicyAccount( $accrual_policy_account_id );
				break;
			case 420: //Calendar Based: Annually with 2 milestones
				$apf->setName( 'Calendar: 2 milestone' );
				$apf->setType( 20 );

				$apf->setApplyFrequency( 20 ); //Annually
				$apf->setApplyFrequencyHireDate( TRUE );

				$apf->setMilestoneRolloverHireDate( TRUE );
				$apf->setEnableOpeningBalance( TRUE );

				$apf->setMinimumEmployedDays( 0 );
				$apf->setAccrualPolicyAccount( $accrual_policy_account_id );
				break;
			case 500: //Calendar Based: Monthly with 2 milestones and rollover set low.
				$apf->setName( 'Calendar: 2 milestone' );
				$apf->setType( 20 );

				$apf->setApplyFrequency( 30 ); //Monthly
				$apf->setApplyFrequencyDayOfMonth( 1 );

				$apf->setMilestoneRolloverHireDate( TRUE );

				$apf->setMinimumEmployedDays( 0 );
				$apf->setAccrualPolicyAccount( $accrual_policy_account_id );
				break;
			case 1000: //Hour Based: 1 milestone, no maximums at all.
			case 1001: //Hour Based: 1 milestone, Rollover = 0 (set below).
				$apf->setName( 'Hour: 1 milestone (basic)' );
				$apf->setType( 30 );

				$apf->setMilestoneRolloverHireDate( TRUE );
				$apf->setContributingShiftPolicy( $contributing_shift_policy_id );

				$apf->setMinimumEmployedDays( 0 );
				$apf->setAccrualPolicyAccount( $accrual_policy_account_id );
				break;
			case 1010: //Hour Based: 1 milestone, maximum balance.
				$apf->setName( 'Hour: 1 milestone (max. balance)' );
				$apf->setType( 30 );

				$apf->setMilestoneRolloverHireDate( TRUE );
				$apf->setContributingShiftPolicy( $contributing_shift_policy_id );

				$apf->setMinimumEmployedDays( 0 );
				$apf->setAccrualPolicyAccount( $accrual_policy_account_id );
				break;
			case 1020: //Hour Based: 1 milestone, maximum balance.
				$apf->setName( 'Hour: 1 milestone (max. annual)' );
				$apf->setType( 30 );

				$apf->setMilestoneRolloverHireDate( TRUE );
				$apf->setContributingShiftPolicy( $contributing_shift_policy_id );

				$apf->setMinimumEmployedDays( 0 );
				$apf->setAccrualPolicyAccount( $accrual_policy_account_id );
				break;

			case 2000: //Calendar Based: Pay Period with one milestone - ProRate Initial Period
				$apf->setName( 'Calendar: 1 milestone' );
				$apf->setType( 20 );

				$apf->setApplyFrequency( 10 ); //Each Pay Period

				$apf->setMilestoneRolloverHireDate( TRUE );
				$apf->setEnableProRateInitialPeriod( TRUE );

				$apf->setMinimumEmployedDays( 0 );
				$apf->setAccrualPolicyAccount( $accrual_policy_account_id );
				break;
			case 2001: //Calendar Based: Pay Period with one milestone - ProRate Initial Period
				$apf->setName( 'Calendar: 1 milestone' );
				$apf->setType( 20 );

				$apf->setApplyFrequency( 10 ); //Each Pay Period

				$apf->setMilestoneRolloverHireDate( TRUE );
				$apf->setEnableProRateInitialPeriod( TRUE );

				$apf->setMinimumEmployedDays( 15 );
				$apf->setAccrualPolicyAccount( $accrual_policy_account_id );
				break;
			case 2010: //Calendar Based: Pay Period with one milestone - ProRate Initial Period
				$apf->setName( 'Calendar: 1 milestone' );
				$apf->setType( 20 );

				$apf->setApplyFrequency( 40 ); //Weekly
				$apf->setApplyFrequencyDayOfWeek( 3 ); //Wednesday

				$apf->setMilestoneRolloverHireDate( TRUE );
				$apf->setEnableProRateInitialPeriod( TRUE );

				$apf->setMinimumEmployedDays( 0 );
				$apf->setAccrualPolicyAccount( $accrual_policy_account_id );
				break;
			case 2020: //Calendar Based: Pay Period with one milestone - ProRate Initial Period
				$apf->setName( 'Calendar: 1 milestone' );
				$apf->setType( 20 );

				$apf->setApplyFrequency( 30 ); //Monthly
				$apf->setApplyFrequencyDayOfMonth( 1 );

				$apf->setMilestoneRolloverHireDate( TRUE );
				$apf->setEnableProRateInitialPeriod( TRUE );

				$apf->setMinimumEmployedDays( 0 );
				$apf->setAccrualPolicyAccount( $accrual_policy_account_id );
				break;
			case 2030: //Calendar Based: Pay Period with one milestone - ProRate Initial Period
				$apf->setName( 'Calendar: 1 milestone' );
				$apf->setType( 20 );

				$apf->setApplyFrequency( 20 ); //Annually
				$apf->setApplyFrequencyMonth( 1 );
				$apf->setApplyFrequencyDayOfMonth( 1 );

				$apf->setMilestoneRolloverHireDate( TRUE );
				$apf->setEnableProRateInitialPeriod( TRUE );

				$apf->setMinimumEmployedDays( 0 );
				$apf->setAccrualPolicyAccount( $accrual_policy_account_id );
				break;
			case 2031: //Calendar Based: Pay Period with one milestone - ProRate Initial Period
				$apf->setName( 'Calendar: 1 milestone' );
				$apf->setType( 20 );

				$apf->setApplyFrequency( 20 ); //Annually
				$apf->setApplyFrequencyHireDate( TRUE );

				$apf->setMilestoneRolloverHireDate( TRUE );
				$apf->setEnableOpeningBalance( TRUE );
				$apf->setEnableProRateInitialPeriod( TRUE );

				$apf->setMinimumEmployedDays( 0 );
				$apf->setAccrualPolicyAccount( $accrual_policy_account_id );
				break;
			case 2100: //Calendar Based: Pay Period with one milestone - Opening Balance
				$apf->setName( 'Calendar: 1 milestone' );
				$apf->setType( 20 );

				$apf->setApplyFrequency( 10 ); //Each Pay Period

				$apf->setMilestoneRolloverHireDate( TRUE );
				$apf->setEnableOpeningBalance( TRUE );
				$apf->setEnableProRateInitialPeriod( TRUE );

				$apf->setMinimumEmployedDays( 0 );
				$apf->setAccrualPolicyAccount( $accrual_policy_account_id );
				break;
			case 2101: //Calendar Based: Pay Period with one milestone - ProRate Initial Period
				$apf->setName( 'Calendar: 1 milestone' );
				$apf->setType( 20 );

				$apf->setApplyFrequency( 10 ); //Each Pay Period

				$apf->setMilestoneRolloverHireDate( TRUE );
				$apf->setEnableOpeningBalance( TRUE );
				$apf->setEnableProRateInitialPeriod( TRUE );

				$apf->setMinimumEmployedDays( 0 );
				$apf->setAccrualPolicyAccount( $accrual_policy_account_id );
				break;
			case 2110: //Calendar Based: Pay Period with one milestone - ProRate Initial Period
				$apf->setName( 'Calendar: 1 milestone' );
				$apf->setType( 20 );

				$apf->setApplyFrequency( 40 ); //Weekly
				$apf->setApplyFrequencyDayOfWeek( 3 ); //Wednesday

				$apf->setMilestoneRolloverHireDate( TRUE );
				$apf->setEnableOpeningBalance( TRUE );
				$apf->setEnableProRateInitialPeriod( TRUE );

				$apf->setMinimumEmployedDays( 0 );
				$apf->setAccrualPolicyAccount( $accrual_policy_account_id );
				break;
			case 2120: //Calendar Based: Pay Period with one milestone - ProRate Initial Period
				$apf->setName( 'Calendar: 1 milestone' );
				$apf->setType( 20 );

				$apf->setApplyFrequency( 30 ); //Monthly
				$apf->setApplyFrequencyDayOfMonth( 1 );

				$apf->setMilestoneRolloverHireDate( TRUE );
				$apf->setEnableOpeningBalance( TRUE );
				$apf->setEnableProRateInitialPeriod( TRUE );

				$apf->setMinimumEmployedDays( 0 );
				$apf->setAccrualPolicyAccount( $accrual_policy_account_id );
				break;
			case 2130: //Calendar Based: Pay Period with one milestone - ProRate Initial Period
				$apf->setName( 'Calendar: 1 milestone' );
				$apf->setType( 20 );

				$apf->setApplyFrequency( 20 ); //Annually
				$apf->setApplyFrequencyMonth( 1 );
				$apf->setApplyFrequencyDayOfMonth( 1 );

				$apf->setMilestoneRolloverHireDate( TRUE );
				$apf->setEnableOpeningBalance( TRUE );
				$apf->setEnableProRateInitialPeriod( TRUE );

				$apf->setMinimumEmployedDays( 0 );
				$apf->setAccrualPolicyAccount( $accrual_policy_account_id );
				break;
			case 2131: //Calendar Based: Pay Period with one milestone - ProRate Initial Period
				$apf->setName( 'Calendar: 1 milestone' );
				$apf->setType( 20 );

				$apf->setApplyFrequency( 20 ); //Annually
				$apf->setApplyFrequencyHireDate( TRUE );

				$apf->setMilestoneRolloverHireDate( TRUE );
				$apf->setEnableOpeningBalance( TRUE );
				$apf->setEnableProRateInitialPeriod( TRUE );

				$apf->setMinimumEmployedDays( 0 );
				$apf->setAccrualPolicyAccount( $accrual_policy_account_id );
				break;
		}

		if ( $apf->isValid() ) {
			$insert_id = $apf->Save();
			Debug::Text('Accrual Policy ID: '. $insert_id, __FILE__, __LINE__, __METHOD__, 10);

			$apmf = TTnew( 'AccrualPolicyMilestoneFactory' ); /** @var AccrualPolicyMilestoneFactory $apmf */

			switch ( $type ) {
				case 20:
					$apmf->setAccrualPolicy( $insert_id );
					$apmf->setLengthOfService( 0 );
					$apmf->setLengthOfServiceUnit( 40 );
					$apmf->setAccrualRate( (3600 * 8) * 5 );
					$apmf->setMaximumTime( (3600 * 9999) );
					$apmf->setRolloverTime( (3600 * 9999) );

					if ( $apmf->isValid() ) {
						Debug::Text('Saving Milestone...', __FILE__, __LINE__, __METHOD__, 10);
						$apmf->Save();
					}
					break;
				case 30:
					$apmf->setAccrualPolicy( $insert_id );
					$apmf->setLengthOfService( 99 );
					$apmf->setLengthOfServiceUnit( 40 );
					$apmf->setAccrualRate( (3600 * 8) * 5 );
					$apmf->setMaximumTime( (3600 * 9999) );
					$apmf->setRolloverTime( (3600 * 9999) );

					if ( $apmf->isValid() ) {
						Debug::Text('Saving Milestone...', __FILE__, __LINE__, __METHOD__, 10);
						$apmf->Save();
					}
					break;
				case 40:
				case 2000:
				case 2001:
				case 2010:
				case 2020:
				case 2030:
				case 2031:
				case 2100:
				case 2101:
				case 2110:
				case 2120:
				case 2130:
				case 2131:
					$apmf->setAccrualPolicy( $insert_id );
					$apmf->setLengthOfService( 0 );
					$apmf->setLengthOfServiceUnit( 40 );
					$apmf->setAccrualRate( (3600 * 8) * 5 );
					$apmf->setMaximumTime( (3600 * 9999) );
					$apmf->setRolloverTime( (3600 * 9999) );

					if ( $apmf->isValid() ) {
						Debug::Text('Saving Milestone...', __FILE__, __LINE__, __METHOD__, 10);
						$apmf->Save();
					}
					break;
				case 50:
					$apmf->setAccrualPolicy( $insert_id );
					$apmf->setLengthOfService( 0 );
					$apmf->setLengthOfServiceUnit( 40 );
					$apmf->setAccrualRate( (3600 * 8) * 5 );
					$apmf->setMaximumTime( (3600 * 9999) );
					$apmf->setRolloverTime( (3600 * 9999) );

					if ( $apmf->isValid() ) {
						Debug::Text('Saving Milestone...', __FILE__, __LINE__, __METHOD__, 10);
						$apmf->Save();
					}

					$apmf->setAccrualPolicy( $insert_id );
					$apmf->setLengthOfService( 1 );
					$apmf->setLengthOfServiceUnit( 40 );
					$apmf->setAccrualRate( (3600 * 8) * 10 );
					$apmf->setMaximumTime( (3600 * 9999) );
					$apmf->setRolloverTime( (3600 * 9999) );

					if ( $apmf->isValid() ) {
						Debug::Text('Saving Milestone...', __FILE__, __LINE__, __METHOD__, 10);
						$apmf->Save();
					}
					break;
				case 60:
					$apmf->setAccrualPolicy( $insert_id );
					$apmf->setLengthOfService( 0 );
					$apmf->setLengthOfServiceUnit( 40 );
					$apmf->setAccrualRate( (3600 * 8) * 5 );
					$apmf->setMaximumTime( (3600 * 9999) );
					$apmf->setRolloverTime( (3600 * 9999) );

					if ( $apmf->isValid() ) {
						Debug::Text('Saving Milestone...', __FILE__, __LINE__, __METHOD__, 10);
						$apmf->Save();
					}

					$apmf->setAccrualPolicy( $insert_id );
					$apmf->setLengthOfService( 1 );
					$apmf->setLengthOfServiceUnit( 40 );
					$apmf->setAccrualRate( (3600 * 8) * 10 );
					$apmf->setMaximumTime( (3600 * 9999) );
					$apmf->setRolloverTime( (3600 * 9999) );

					if ( $apmf->isValid() ) {
						Debug::Text('Saving Milestone...', __FILE__, __LINE__, __METHOD__, 10);
						$apmf->Save();
					}

					$apmf->setAccrualPolicy( $insert_id );
					$apmf->setLengthOfService( 2 );
					$apmf->setLengthOfServiceUnit( 40 );
					$apmf->setAccrualRate( (3600 * 8) * 15 );
					$apmf->setMaximumTime( (3600 * 9999) );
					$apmf->setRolloverTime( (3600 * 9999) );

					if ( $apmf->isValid() ) {
						Debug::Text('Saving Milestone...', __FILE__, __LINE__, __METHOD__, 10);
						$apmf->Save();
					}

					$apmf->setAccrualPolicy( $insert_id );
					$apmf->setLengthOfService( 3 );
					$apmf->setLengthOfServiceUnit( 40 );
					$apmf->setAccrualRate( (3600 * 8) * 20 );
					$apmf->setMaximumTime( (3600 * 9999) );
					$apmf->setRolloverTime( (3600 * 9999) );

					if ( $apmf->isValid() ) {
						Debug::Text('Saving Milestone...', __FILE__, __LINE__, __METHOD__, 10);
						$apmf->Save();
					}

					$apmf->setAccrualPolicy( $insert_id );
					$apmf->setLengthOfService( 4 );
					$apmf->setLengthOfServiceUnit( 40 );
					$apmf->setAccrualRate( (3600 * 8) * 25 );
					$apmf->setMaximumTime( (3600 * 9999) );
					$apmf->setRolloverTime( (3600 * 9999) );

					if ( $apmf->isValid() ) {
						Debug::Text('Saving Milestone...', __FILE__, __LINE__, __METHOD__, 10);
						$apmf->Save();
					}

					$apmf->setAccrualPolicy( $insert_id );
					$apmf->setLengthOfService( 5 );
					$apmf->setLengthOfServiceUnit( 40 );
					$apmf->setAccrualRate( (3600 * 8) * 30 );
					$apmf->setMaximumTime( (3600 * 9999) );
					$apmf->setRolloverTime( (3600 * 9999) );

					if ( $apmf->isValid() ) {
						Debug::Text('Saving Milestone...', __FILE__, __LINE__, __METHOD__, 10);
						$apmf->Save();
					}

					break;
				case 60:
				case 70:
					$apmf->setAccrualPolicy( $insert_id );
					$apmf->setLengthOfService( 0 );
					$apmf->setLengthOfServiceUnit( 40 );
					$apmf->setAccrualRate( (3600 * 8) * 5 );
					$apmf->setMaximumTime( (3600 * 9999) );
					$apmf->setRolloverTime( (3600 * 9999) );

					if ( $apmf->isValid() ) {
						Debug::Text('Saving Milestone...', __FILE__, __LINE__, __METHOD__, 10);
						$apmf->Save();
					}

					$apmf->setAccrualPolicy( $insert_id );
					$apmf->setLengthOfService( 1 );
					$apmf->setLengthOfServiceUnit( 40 );
					$apmf->setAccrualRate( (3600 * 8) * 10 );
					$apmf->setMaximumTime( (3600 * 9999) );
					$apmf->setRolloverTime( (3600 * 9999) );

					if ( $apmf->isValid() ) {
						Debug::Text('Saving Milestone...', __FILE__, __LINE__, __METHOD__, 10);
						$apmf->Save();
					}

					$apmf->setAccrualPolicy( $insert_id );
					$apmf->setLengthOfService( 2 );
					$apmf->setLengthOfServiceUnit( 40 );
					$apmf->setAccrualRate( (3600 * 8) * 15 );
					$apmf->setMaximumTime( (3600 * 9999) );
					$apmf->setRolloverTime( (3600 * 9999) );

					if ( $apmf->isValid() ) {
						Debug::Text('Saving Milestone...', __FILE__, __LINE__, __METHOD__, 10);
						$apmf->Save();
					}

					$apmf->setAccrualPolicy( $insert_id );
					$apmf->setLengthOfService( 3 );
					$apmf->setLengthOfServiceUnit( 40 );
					$apmf->setAccrualRate( (3600 * 8) * 20 );
					$apmf->setMaximumTime( (3600 * 9999) );
					$apmf->setRolloverTime( (3600 * 9999) );

					if ( $apmf->isValid() ) {
						Debug::Text('Saving Milestone...', __FILE__, __LINE__, __METHOD__, 10);
						$apmf->Save();
					}

					$apmf->setAccrualPolicy( $insert_id );
					$apmf->setLengthOfService( 4 );
					$apmf->setLengthOfServiceUnit( 40 );
					$apmf->setAccrualRate( (3600 * 8) * 25 );
					$apmf->setMaximumTime( (3600 * 9999) );
					$apmf->setRolloverTime( (3600 * 9999) );

					if ( $apmf->isValid() ) {
						Debug::Text('Saving Milestone...', __FILE__, __LINE__, __METHOD__, 10);
						$apmf->Save();
					}

					$apmf->setAccrualPolicy( $insert_id );
					$apmf->setLengthOfService( 5 );
					$apmf->setLengthOfServiceUnit( 40 );
					$apmf->setAccrualRate( (3600 * 8) * 30 );
					$apmf->setMaximumTime( (3600 * 9999) );
					$apmf->setRolloverTime( (3600 * 9999) );

					if ( $apmf->isValid() ) {
						Debug::Text('Saving Milestone...', __FILE__, __LINE__, __METHOD__, 10);
						$apmf->Save();
					}
					break;

				case 80:
					$apmf->setAccrualPolicy( $insert_id );
					$apmf->setLengthOfService( 0 );
					$apmf->setLengthOfServiceUnit( 40 );
					$apmf->setAccrualRate( (3600 * 8) * 6 );
					$apmf->setMaximumTime( (3600 * 8) * 3 );
					$apmf->setRolloverTime( (3600 * 8) * 2 );

					if ( $apmf->isValid() ) {
						Debug::Text('Saving Milestone...', __FILE__, __LINE__, __METHOD__, 10);
						$apmf->Save();
					}

					$apmf->setAccrualPolicy( $insert_id );
					$apmf->setLengthOfService( 1 );
					$apmf->setLengthOfServiceUnit( 40 );
					$apmf->setAccrualRate( (3600 * 8) * 10 );
					$apmf->setMaximumTime( (3600 * 8) * 5 );
					$apmf->setRolloverTime( (3600 * 8) * 4 );

					if ( $apmf->isValid() ) {
						Debug::Text('Saving Milestone...', __FILE__, __LINE__, __METHOD__, 10);
						$apmf->Save();
					}
					break;
				case 200:
				case 210:
				case 300:
				case 310:
				case 320:
				case 350:
				case 360:
				case 370:
				case 400:
				case 410:
				case 420:
					$apmf->setAccrualPolicy( $insert_id );
					$apmf->setLengthOfService( 0 );
					$apmf->setLengthOfServiceUnit( 40 );
					$apmf->setAccrualRate( (3600 * 8) * 5 );
					$apmf->setMaximumTime( (3600 * 9999) );
					$apmf->setRolloverTime( (3600 * 9999) );

					if ( $apmf->isValid() ) {
						Debug::Text('Saving Milestone...', __FILE__, __LINE__, __METHOD__, 10);
						$apmf->Save();
					}

					$apmf->setAccrualPolicy( $insert_id );
					$apmf->setLengthOfService( 1 );
					$apmf->setLengthOfServiceUnit( 40 );
					$apmf->setAccrualRate( (3600 * 8) * 10 );
					$apmf->setMaximumTime( (3600 * 9999) );
					$apmf->setRolloverTime( (3600 * 9999) );

					if ( $apmf->isValid() ) {
						Debug::Text('Saving Milestone...', __FILE__, __LINE__, __METHOD__, 10);
						$apmf->Save();
					}
					break;
				case 500:
					$apmf->setAccrualPolicy( $insert_id );
					$apmf->setLengthOfService( 0 );
					$apmf->setLengthOfServiceUnit( 40 );
					$apmf->setAccrualRate( (3600 * 8) * 5 );
					$apmf->setMaximumTime( (3600 * 9999) );
					$apmf->setRolloverTime( (3600 * 8) * 1 );

					if ( $apmf->isValid() ) {
						Debug::Text('Saving Milestone...', __FILE__, __LINE__, __METHOD__, 10);
						$apmf->Save();
					}

					$apmf->setAccrualPolicy( $insert_id );
					$apmf->setLengthOfService( 1 );
					$apmf->setLengthOfServiceUnit( 40 );
					$apmf->setAccrualRate( (3600 * 8) * 10 );
					$apmf->setMaximumTime( (3600 * 9999) );
					$apmf->setRolloverTime( (3600 * 8) * 2 );

					if ( $apmf->isValid() ) {
						Debug::Text('Saving Milestone...', __FILE__, __LINE__, __METHOD__, 10);
						$apmf->Save();
					}
					break;
				case 1000:
					$apmf->setAccrualPolicy( $insert_id );
					$apmf->setLengthOfService( 0 );
					$apmf->setLengthOfServiceUnit( 40 );
					$apmf->setAccrualRate( 1.0 );
					$apmf->setAnnualMaximumTime( 0 );
					$apmf->setMaximumTime( (3600 * 9999) );
					$apmf->setRolloverTime( (3600 * 9999) );

					if ( $apmf->isValid() ) {
						Debug::Text('Saving Milestone...', __FILE__, __LINE__, __METHOD__, 10);
						$apmf->Save();
					}
					break;
				case 1001:
					$apmf->setAccrualPolicy( $insert_id );
					$apmf->setLengthOfService( 0 );
					$apmf->setLengthOfServiceUnit( 40 );
					$apmf->setAccrualRate( 1.0 );
					$apmf->setAnnualMaximumTime( 0 );
					$apmf->setMaximumTime( (3600 * 9999) );
					$apmf->setRolloverTime( (3600 * 0) ); //Zero out balance.

					if ( $apmf->isValid() ) {
						Debug::Text('Saving Milestone...', __FILE__, __LINE__, __METHOD__, 10);
						$apmf->Save();
					}
					break;
				case 1010:
					$apmf->setAccrualPolicy( $insert_id );
					$apmf->setLengthOfService( 0 );
					$apmf->setLengthOfServiceUnit( 40 );
					$apmf->setAccrualRate( 1.0 );
					$apmf->setAnnualMaximumTime( (3600 * 9999) );
					$apmf->setMaximumTime( (3600 * 118) );
					$apmf->setRolloverTime( (3600 * 9999) );

					if ( $apmf->isValid() ) {
						Debug::Text('Saving Milestone...', __FILE__, __LINE__, __METHOD__, 10);
						$apmf->Save();
					}
					break;
				case 1020:
					$apmf->setAccrualPolicy( $insert_id );
					$apmf->setLengthOfService( 0 );
					$apmf->setLengthOfServiceUnit( 40 );
					$apmf->setAccrualRate( 1.0 );
					$apmf->setAnnualMaximumTime( (3600 * 112) );
					$apmf->setMaximumTime( (3600 * 118) );
					$apmf->setRolloverTime( (3600 * 9999) );

					if ( $apmf->isValid() ) {
						Debug::Text('Saving Milestone...', __FILE__, __LINE__, __METHOD__, 10);
						$apmf->Save();
					}
					break;
			}

			return $insert_id;
		}

		Debug::Text('Failed Creating Accrual Policy!', __FILE__, __LINE__, __METHOD__, 10);

		return FALSE;
	}

	function createPunches( $start_date, $end_date, $in_time, $out_time ) {
		global $dd;

		Debug::Text('Start Date: '. TTDate::getDate('DATE', $start_date) .'('.$start_date.') End: '. TTDate::getDate('DATE', $end_date) .'('. $end_date .')', __FILE__, __LINE__, __METHOD__, 10);
		for( $i = $start_date; $i < $end_date; $i += (86400 + 3601) ) {
			$i = TTDate::getBeginDayEpoch( $i );

			Debug::Text('Date: '. TTDate::getDate('DATE', $i) .' In: '. $in_time .' Out: '. $out_time, __FILE__, __LINE__, __METHOD__, 10);
			$dd->createPunchPair( 	$this->user_id,
									strtotime( TTDate::getDate('DATE', $i ).' '. $in_time),
									strtotime( TTDate::getDate('DATE', $i ).' '. $out_time),
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
		}

		return TRUE;
	}

	function calcAccrualTime( $company_id, $accrual_policy_id, $start_date, $end_date, $day_multiplier = 1 ) {
		$start_date = TTDate::getMiddleDayEpoch( $start_date );
		$end_date = TTDate::getMiddleDayEpoch( $end_date );
		//$offset = 79200;
		$offset = ( (86400 * $day_multiplier) - 7200 );

		$aplf = TTnew( 'AccrualPolicyListFactory' ); /** @var AccrualPolicyListFactory $aplf */

		$aplf->getByIdAndCompanyId( $accrual_policy_id, $company_id );
		if ( $aplf->getRecordCount() > 0 ) {
			foreach( $aplf as $ap_obj ) {
				$aplf->StartTransaction();

				$x = 0;
				for( $i = $start_date; $i < $end_date; $i += ( 86400 * $day_multiplier ) ) { //Try skipping by two days to speed up this test.
					//Debug::Text('Recalculating Accruals for Date: '. TTDate::getDate('DATE+TIME', TTDate::getBeginDayEpoch( $i ) ), __FILE__, __LINE__, __METHOD__,10);
					$ap_obj->addAccrualPolicyTime( (TTDate::getBeginDayEpoch( $i ) + 7201), $offset );
					//Debug::Text('----------------------------------', __FILE__, __LINE__, __METHOD__,10);

					$x++;
				}

				$aplf->CommitTransaction();
			}
		}

		return TRUE;
	}

	function getCurrentAccrualBalance( $user_id, $accrual_policy_account_id = NULL ) {
		if ( $user_id == '' ) {
			return FALSE;
		}

		//Check min/max times of accrual policy.
		$ablf = TTnew( 'AccrualBalanceListFactory' ); /** @var AccrualBalanceListFactory $ablf */
		$ablf->getByUserIdAndAccrualPolicyAccount( $user_id, $accrual_policy_account_id );
		if ( $ablf->getRecordCount() > 0 ) {
			$accrual_balance = $ablf->getCurrent()->getBalance();
		} else {
			$accrual_balance = 0;
		}

		Debug::Text('   Current Accrual Balance: '. $accrual_balance, __FILE__, __LINE__, __METHOD__, 10);

		return $accrual_balance;
	}

	function getAccrualArray( $user_id, $accrual_policy_account_id = NULL ) {
		if ( $user_id == '' ) {
			return FALSE;
		}

		$retarr = array();

		//Check min/max times of accrual policy.
		$alf = TTnew( 'AccrualListFactory' ); /** @var AccrualListFactory $alf */
		$alf->getByCompanyIdAndUserIdAndAccrualPolicyAccount( $this->company_id, $user_id, $accrual_policy_account_id );
		if ( $alf->getRecordCount() > 0 ) {
			foreach( $alf as $a_obj ) {
				$retarr[TTDate::getMiddleDayEpoch( $a_obj->getTimeStamp() )][] = $a_obj;
			}
		}

		//Debug::Arr( $retarr, '   Current Accrual Records: ', __FILE__, __LINE__, __METHOD__, 10);
		return $retarr;
	}

	function getUserObject( $user_id ) {
		$ulf = TTNew( 'UserListFactory' ); /** @var UserListFactory $ulf */
		$ulf->getById( $user_id );
		if ( $ulf->getRecordCount() > 0 ) {
			return $ulf->getCurrent();
		}

		return FALSE;
	}


	/*
	 Tests:
		Calendar Based - Minimum Employed Days
		Calendar Based - 1st milestone high length of service.
		Calendar Based - PayPeriod Frequency (1 milestone)
		Calendar Based - PayPeriod Frequency (2 milestones)
		Calendar Based - PayPeriod Frequency (5 milestones)
	*/

	/**
	 * @group AccrualPolicy_testCalendarAccrualA
	 */
	function testCalendarAccrualA() {
		global $dd;

		$hire_date = $this->getUserObject( $this->user_id )->getHireDate();
		$current_epoch = TTDate::getBeginYearEpoch( $hire_date + (86400 * 365 * 2) );

		$accrual_policy_account_id = $this->createAccrualPolicyAccount( $this->company_id, 10 );
		$accrual_policy_id = $this->createAccrualPolicy( $this->company_id, 20, $accrual_policy_account_id );
		$dd->createPolicyGroup( 	$this->company_id,
									NULL,
									NULL,
									NULL,
									NULL,
									NULL,
									NULL,
									array($this->user_id),
									NULL,
									array( $accrual_policy_id ) );

		$this->calcAccrualTime( $this->company_id, $accrual_policy_id, TTDate::getBeginYearEpoch( $current_epoch ), TTDate::getEndYearEpoch( $current_epoch ) );
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );

		$this->assertEquals( $accrual_balance, (0 * 3600) );
	}

	/**
	 * @group AccrualPolicy_testCalendarAccrualB
	 */
	function testCalendarAccrualB() {
		global $dd;

		$hire_date = $this->getUserObject( $this->user_id )->getHireDate();
		$current_epoch = TTDate::getBeginYearEpoch( $hire_date + (86400 * 365 * 2) );

		$accrual_policy_account_id = $this->createAccrualPolicyAccount( $this->company_id, 10 );
		$accrual_policy_id = $this->createAccrualPolicy( $this->company_id, 30, $accrual_policy_account_id );
		$dd->createPolicyGroup( 	$this->company_id,
									NULL,
									NULL,
									NULL,
									NULL,
									NULL,
									NULL,
									array($this->user_id),
									NULL,
									array( $accrual_policy_id ) );

		$this->calcAccrualTime( $this->company_id, $accrual_policy_id, TTDate::getBeginYearEpoch( $current_epoch ), TTDate::getEndYearEpoch( $current_epoch ) );
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );

		$this->assertEquals( $accrual_balance, (0 * 3600) );
	}

	/**
	 * @group AccrualPolicy_testCalendarAccrualC
	 */
	function testCalendarAccrualC() {
		global $dd;

		$hire_date = $this->getUserObject( $this->user_id )->getHireDate();
		$current_epoch = TTDate::getBeginYearEpoch( $hire_date + (86400 * 365 * 2) );

		$accrual_policy_account_id = $this->createAccrualPolicyAccount( $this->company_id, 10 );
		$accrual_policy_id = $this->createAccrualPolicy( $this->company_id, 40, $accrual_policy_account_id );
		$dd->createPolicyGroup( 	$this->company_id,
									NULL,
									NULL,
									NULL,
									NULL,
									NULL,
									NULL,
									array($this->user_id),
									NULL,
									array( $accrual_policy_id ) );

		$this->calcAccrualTime( $this->company_id, $accrual_policy_id, TTDate::getBeginYearEpoch( $current_epoch ), TTDate::getEndYearEpoch( $current_epoch ) );
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );

		$this->assertEquals( $accrual_balance, (40 * 3600) );
	}

	/**
	 * @group AccrualPolicy_testCalendarAccrualD
	 */
	function testCalendarAccrualD() {
		global $dd;

		$hire_date = $this->getUserObject( $this->user_id )->getHireDate();
		$current_epoch = TTDate::getBeginYearEpoch( $hire_date + (86400 * 365 * 2) );

		$accrual_policy_account_id = $this->createAccrualPolicyAccount( $this->company_id, 10 );
		$accrual_policy_id = $this->createAccrualPolicy( $this->company_id, 50, $accrual_policy_account_id );
		$dd->createPolicyGroup( 	$this->company_id,
									NULL,
									NULL,
									NULL,
									NULL,
									NULL,
									NULL,
									array($this->user_id),
									NULL,
									array( $accrual_policy_id ) );

		$this->calcAccrualTime( $this->company_id, $accrual_policy_id, TTDate::getBeginYearEpoch( $current_epoch ), TTDate::getEndYearEpoch( $current_epoch ) );
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );

		$this->assertEquals( $accrual_balance, (80 * 3600) );
	}

	/**
	 * @group AccrualPolicy_testCalendarAccrualE
	 */
	function testCalendarAccrualE() {
		global $dd;

		$hire_date = $this->getUserObject( $this->user_id )->getHireDate();
		$current_epoch = $hire_date;

		$accrual_policy_account_id = $this->createAccrualPolicyAccount( $this->company_id, 10 );
		$accrual_policy_id = $this->createAccrualPolicy( $this->company_id, 60, $accrual_policy_account_id );
		$dd->createPolicyGroup( 	$this->company_id,
									NULL,
									NULL,
									NULL,
									NULL,
									NULL,
									NULL,
									array($this->user_id),
									NULL,
									array( $accrual_policy_id ) );

		$this->calcAccrualTime( $this->company_id, $accrual_policy_id, $current_epoch, strtotime('+7 years', $current_epoch) );
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );

		$this->assertEquals( $accrual_balance, (1080 * 3600) );
	}

	/**
	 * @group AccrualPolicy_testCalendarAccrualF
	 */
	function testCalendarAccrualF() {
		global $dd;

		$hire_date = $this->getUserObject( $this->user_id )->getHireDate();
		$current_epoch = $hire_date;

		$accrual_policy_account_id = $this->createAccrualPolicyAccount( $this->company_id, 10 );
		$accrual_policy_id = $this->createAccrualPolicy( $this->company_id, 70, $accrual_policy_account_id );
		$dd->createPolicyGroup( 	$this->company_id,
									NULL,
									NULL,
									NULL,
									NULL,
									NULL,
									NULL,
									array($this->user_id),
									NULL,
									array( $accrual_policy_id ) );

		$this->calcAccrualTime( $this->company_id, $accrual_policy_id, $current_epoch, strtotime('+7 years', $current_epoch) );
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );

		$this->assertEquals( $accrual_balance, 4038000 );
	}

	/**
	 * @group AccrualPolicy_testCalendarAccrualG
	 */
	function testCalendarAccrualG() {
		global $dd;

		$hire_date = $this->getUserObject( $this->user_id )->getHireDate();
		$current_epoch = $hire_date;

		$accrual_policy_account_id = $this->createAccrualPolicyAccount( $this->company_id, 10 );
		$accrual_policy_id = $this->createAccrualPolicy( $this->company_id, 80, $accrual_policy_account_id );
		$dd->createPolicyGroup( 	$this->company_id,
									NULL,
									NULL,
									NULL,
									NULL,
									NULL,
									NULL,
									array($this->user_id),
									NULL,
									array( $accrual_policy_id ) );

		$this->calcAccrualTime( $this->company_id, $accrual_policy_id, $current_epoch, strtotime('+5 years', $current_epoch) );
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );

		$this->assertEquals( $accrual_balance, 144000 );
	}

	/**
	 * @group AccrualPolicy_testWeeklyCalendarAccrualA
	 */
	function testWeeklyCalendarAccrualA() {
		global $dd;

		$hire_date = $this->getUserObject( $this->user_id )->getHireDate();
		$current_epoch = $hire_date;

		$accrual_policy_account_id = $this->createAccrualPolicyAccount( $this->company_id, 10 );
		$accrual_policy_id = $this->createAccrualPolicy( $this->company_id, 200, $accrual_policy_account_id );
		$dd->createPolicyGroup( 	$this->company_id,
									NULL,
									NULL,
									NULL,
									NULL,
									NULL,
									NULL,
									array($this->user_id),
									NULL,
									array( $accrual_policy_id ) );

		$this->calcAccrualTime( $this->company_id, $accrual_policy_id, $current_epoch, strtotime('+2 years', $current_epoch) );
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );

		//$this->assertEquals( $accrual_balance, 434733 ); //Was this value before we added pro-rating/opening balance. The only difference was the first entry.
		$this->assertEquals( $accrual_balance, 431964 );
	}


	/**
	 * @group AccrualPolicy_testWeeklyCalendarAccrualB
	 */
	function testWeeklyCalendarAccrualB() {
		global $dd;

		$hire_date = $this->getUserObject( $this->user_id )->getHireDate();
		$current_epoch = $hire_date;

		$accrual_policy_account_id = $this->createAccrualPolicyAccount( $this->company_id, 10 );
		$accrual_policy_id = $this->createAccrualPolicy( $this->company_id, 210, $accrual_policy_account_id );
		$dd->createPolicyGroup( 	$this->company_id,
									NULL,
									NULL,
									NULL,
									NULL,
									NULL,
									NULL,
									array($this->user_id),
									NULL,
									array( $accrual_policy_id ) );

		$this->calcAccrualTime( $this->company_id, $accrual_policy_id, $current_epoch, strtotime('+2 years', $current_epoch) );
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );

		$this->assertEquals( $accrual_balance, 431964 );
	}


	/**
	 * @group AccrualPolicy_testMonthlyCalendarAccrualA
	 */
	function testMonthlyCalendarAccrualA() {
		global $dd;

		$hire_date = $this->getUserObject( $this->user_id )->getHireDate();
		$current_epoch = $hire_date;

		$accrual_policy_account_id = $this->createAccrualPolicyAccount( $this->company_id, 10 );
		$accrual_policy_id = $this->createAccrualPolicy( $this->company_id, 300, $accrual_policy_account_id );
		$dd->createPolicyGroup( 	$this->company_id,
									NULL,
									NULL,
									NULL,
									NULL,
									NULL,
									NULL,
									array($this->user_id),
									NULL,
									array( $accrual_policy_id ) );

		$this->calcAccrualTime( $this->company_id, $accrual_policy_id, $current_epoch, strtotime('+2 years', $current_epoch), 10 );
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );

		//$this->assertEquals( $accrual_balance, 432000 ); //Was this value before we added pro-rating/opening balance. The only difference was the first entry.
		$this->assertEquals( $accrual_balance, 420000 );
	}

	/**
	 * @group AccrualPolicy_testMonthlyCalendarAccrualB
	 */
	function testMonthlyCalendarAccrualB() {
		global $dd;

		$hire_date = $this->getUserObject( $this->user_id )->getHireDate();
		$current_epoch = $hire_date;

		$accrual_policy_account_id = $this->createAccrualPolicyAccount( $this->company_id, 10 );
		$accrual_policy_id = $this->createAccrualPolicy( $this->company_id, 310, $accrual_policy_account_id );
		$dd->createPolicyGroup( 	$this->company_id,
									NULL,
									NULL,
									NULL,
									NULL,
									NULL,
									NULL,
									array($this->user_id),
									NULL,
									array( $accrual_policy_id ) );

		$this->calcAccrualTime( $this->company_id, $accrual_policy_id, $current_epoch, strtotime('+2 years', $current_epoch), 10 );
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );

		$this->assertEquals( $accrual_balance, 432000 );
	}

	/**
	 * @group AccrualPolicy_testMonthlyCalendarAccrualC
	 */
	function testMonthlyCalendarAccrualC() {
		global $dd;

		$hire_date = $this->getUserObject( $this->user_id )->getHireDate();
		$current_epoch = $hire_date;

		$accrual_policy_account_id = $this->createAccrualPolicyAccount( $this->company_id, 10 );
		$accrual_policy_id = $this->createAccrualPolicy( $this->company_id, 320, $accrual_policy_account_id );
		$dd->createPolicyGroup( 	$this->company_id,
									NULL,
									NULL,
									NULL,
									NULL,
									NULL,
									NULL,
									array($this->user_id),
									NULL,
									array( $accrual_policy_id ) );

		$this->calcAccrualTime( $this->company_id, $accrual_policy_id, $current_epoch, strtotime('+2 years', $current_epoch) );
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );

		$this->assertEquals( $accrual_balance, 432000 );
	}


	/**
	 * @group AccrualPolicy_testQuarterlyCalendarAccrualA
	 */
	function testQuarterlyCalendarAccrualA() {
		global $dd;

		$hire_date = $this->getUserObject( $this->user_id )->getHireDate();
		$current_epoch = $hire_date;

		$accrual_policy_account_id = $this->createAccrualPolicyAccount( $this->company_id, 10 );
		$accrual_policy_id = $this->createAccrualPolicy( $this->company_id, 350, $accrual_policy_account_id );
		$dd->createPolicyGroup( 	$this->company_id,
									NULL,
									NULL,
									NULL,
									NULL,
									NULL,
									NULL,
									array($this->user_id),
									NULL,
									array( $accrual_policy_id ) );

		$this->calcAccrualTime( $this->company_id, $accrual_policy_id, $current_epoch, strtotime('+2 years', $current_epoch) );
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );

		$this->assertEquals( $accrual_balance, 432000 );
	}

	/**
	 * @group AccrualPolicy_testQuarterlyCalendarAccrualB
	 */
	function testQuarterlyCalendarAccrualB() {
		global $dd;

		$hire_date = $this->getUserObject( $this->user_id )->getHireDate();
		$current_epoch = $hire_date;

		$accrual_policy_account_id = $this->createAccrualPolicyAccount( $this->company_id, 10 );
		$accrual_policy_id = $this->createAccrualPolicy( $this->company_id, 360, $accrual_policy_account_id );
		$dd->createPolicyGroup( 	$this->company_id,
									NULL,
									NULL,
									NULL,
									NULL,
									NULL,
									NULL,
									array($this->user_id),
									NULL,
									array( $accrual_policy_id ) );

		$this->calcAccrualTime( $this->company_id, $accrual_policy_id, $current_epoch, strtotime('+2 years', $current_epoch) );
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );

		$this->assertEquals( $accrual_balance, 432000 );
	}

	/**
	 * @group AccrualPolicy_testQuarterlyCalendarAccrualC
	 */
	function testQuarterlyCalendarAccrualC() {
		global $dd;

		$hire_date = $this->getUserObject( $this->user_id )->getHireDate();
		$current_epoch = $hire_date;

		$accrual_policy_account_id = $this->createAccrualPolicyAccount( $this->company_id, 10 );
		$accrual_policy_id = $this->createAccrualPolicy( $this->company_id, 370, $accrual_policy_account_id );
		$dd->createPolicyGroup( 	$this->company_id,
									NULL,
									NULL,
									NULL,
									NULL,
									NULL,
									NULL,
									array($this->user_id),
									NULL,
									array( $accrual_policy_id ) );

		$this->calcAccrualTime( $this->company_id, $accrual_policy_id, $current_epoch, strtotime('+2 years', $current_epoch) );
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );

		$this->assertEquals( $accrual_balance, 432000 );
	}

	/**
	 * @group AccrualPolicy_testAnnualCalendarAccrualA
	 */
	function testAnnualCalendarAccrualA() {
		global $dd;

		$hire_date = $this->getUserObject( $this->user_id )->getHireDate();
		$current_epoch = $hire_date;

		$accrual_policy_account_id = $this->createAccrualPolicyAccount( $this->company_id, 10 );
		$accrual_policy_id = $this->createAccrualPolicy( $this->company_id, 400, $accrual_policy_account_id );
		$dd->createPolicyGroup( 	$this->company_id,
									NULL,
									NULL,
									NULL,
									NULL,
									NULL,
									NULL,
									array($this->user_id),
									NULL,
									array( $accrual_policy_id ) );

		$this->calcAccrualTime( $this->company_id, $accrual_policy_id, $current_epoch, strtotime('+5 years', $current_epoch), 30 );
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );

		$this->assertEquals( $accrual_balance, 1296000 );
	}

	/**
	 * @group AccrualPolicy_testAnnualCalendarAccrualB
	 */
	function testAnnualCalendarAccrualB() {
		global $dd;

		$hire_date = $this->getUserObject( $this->user_id )->getHireDate();
		$current_epoch = $hire_date;

		$accrual_policy_account_id = $this->createAccrualPolicyAccount( $this->company_id, 10 );
		$accrual_policy_id = $this->createAccrualPolicy( $this->company_id, 410, $accrual_policy_account_id );
		$dd->createPolicyGroup( 	$this->company_id,
									NULL,
									NULL,
									NULL,
									NULL,
									NULL,
									NULL,
									array($this->user_id),
									NULL,
									array( $accrual_policy_id ) );

		$this->calcAccrualTime( $this->company_id, $accrual_policy_id, $current_epoch, strtotime('+5 years', $current_epoch), 30 );
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );

		$this->assertEquals( $accrual_balance, 1296000 );
	}

	/**
	 * @group AccrualPolicy_testAnnualCalendarAccrualC
	 */
	function testAnnualCalendarAccrualC() {
		global $dd;

		$hire_date = $this->getUserObject( $this->user_id )->getHireDate();
		$current_epoch = $hire_date;

		$accrual_policy_account_id = $this->createAccrualPolicyAccount( $this->company_id, 10 );
		$accrual_policy_id = $this->createAccrualPolicy( $this->company_id, 420, $accrual_policy_account_id );
		$dd->createPolicyGroup( 	$this->company_id,
									NULL,
									NULL,
									NULL,
									NULL,
									NULL,
									NULL,
									array($this->user_id),
									NULL,
									array( $accrual_policy_id ) );

		$this->calcAccrualTime( $this->company_id, $accrual_policy_id, $current_epoch, strtotime('+5 years', $current_epoch), 30 );
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );

		$this->assertEquals( $accrual_balance, 1296000 );
	}

	/**
	 * @group AccrualPolicy_testMonthlyCalendarAccrualwithRolloverA
	 */
	function testMonthlyCalendarAccrualwithRolloverA() {
		global $dd;

		$hire_date = $this->getUserObject( $this->user_id )->getHireDate();
		$current_epoch = $hire_date;

		$accrual_policy_account_id = $this->createAccrualPolicyAccount( $this->company_id, 10 );
		$accrual_policy_id = $this->createAccrualPolicy( $this->company_id, 500, $accrual_policy_account_id );
		$dd->createPolicyGroup( 	$this->company_id,
									NULL,
									NULL,
									NULL,
									NULL,
									NULL,
									NULL,
									array($this->user_id),
									NULL,
									array( $accrual_policy_id ) );

		$this->calcAccrualTime( $this->company_id, $accrual_policy_id, $current_epoch, strtotime('+13 months', $current_epoch), 10 );
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );

		$this->assertEquals( $accrual_balance, 81600 );
	}

	/**
	 * @group AccrualPolicy_testMonthlyCalendarAccrualwithRolloverB
	 */
	function testMonthlyCalendarAccrualwithRolloverB() {
		global $dd;

		$hire_date = $this->getUserObject( $this->user_id )->getHireDate();
		$current_epoch = $hire_date;

		$accrual_policy_account_id = $this->createAccrualPolicyAccount( $this->company_id, 10 );
		$accrual_policy_id = $this->createAccrualPolicy( $this->company_id, 500, $accrual_policy_account_id );
		$dd->createPolicyGroup( 	$this->company_id,
									NULL,
									NULL,
									NULL,
									NULL,
									NULL,
									NULL,
									array($this->user_id),
									NULL,
									array( $accrual_policy_id ) );

		$this->calcAccrualTime( $this->company_id, $accrual_policy_id, $current_epoch, strtotime('+25 months', $current_epoch), 10 );
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );

		$this->assertEquals( $accrual_balance, 105600 );
	}

	/**
	 * @group AccrualPolicy_testMonthlyCalendarAccrualwithRolloverC2
	 */
	function testMonthlyCalendarAccrualwithRolloverC2() {
		global $dd;

		if ( getTTProductEdition() == TT_PRODUCT_COMMUNITY ) {
			return TRUE;
		}

		$hire_date = $this->getUserObject( $this->user_id )->getHireDate();
		$current_epoch = $hire_date;

		$accrual_policy_account_id = $this->createAccrualPolicyAccount( $this->company_id, 10 );
		$accrual_policy_id = $this->createAccrualPolicy( $this->company_id, 500, $accrual_policy_account_id );
		$dd->createPolicyGroup( 	$this->company_id,
									NULL,
									NULL,
									NULL,
									NULL,
									NULL,
									NULL,
									array($this->user_id),
									NULL,
									array( $accrual_policy_id ) );

		//Test UserModifier values.
		$this->createAccrualPolicyUserModifier( $accrual_policy_id, $this->user_id, strtotime( '+10 months', $hire_date ) );

		$this->calcAccrualTime( $this->company_id, $accrual_policy_id, $current_epoch, strtotime('+37 months', $current_epoch), 10 );
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );

		$this->assertEquals( $accrual_balance, 129600 );
	}

	/**
	 * @group AccrualPolicy_testMonthlyCalendarAccrualwithRolloverC3
	 */
	function testMonthlyCalendarAccrualwithRolloverC3() {
		global $dd;

		if ( getTTProductEdition() == TT_PRODUCT_COMMUNITY ) {
			return TRUE;
		}

		$hire_date = $this->getUserObject( $this->user_id )->getHireDate();
		$current_epoch = $hire_date;

		$accrual_policy_account_id = $this->createAccrualPolicyAccount( $this->company_id, 10 );
		$accrual_policy_id = $this->createAccrualPolicy( $this->company_id, 500, $accrual_policy_account_id );
		$dd->createPolicyGroup( 	$this->company_id,
									NULL,
									NULL,
									NULL,
									NULL,
									NULL,
									NULL,
									array($this->user_id),
									NULL,
									array( $accrual_policy_id ) );

		//Test UserModifier values.
		$this->createAccrualPolicyUserModifier( $accrual_policy_id, $this->user_id, strtotime( '+10 months', $hire_date), 2 );

		$this->calcAccrualTime( $this->company_id, $accrual_policy_id, $current_epoch, strtotime('+37 months', $current_epoch), 10 );
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );

		$this->assertEquals( $accrual_balance, 201600 );
	}

	/**
	 * @group AccrualPolicy_testCalendarAccrualProRateA
	 */
	function testCalendarAccrualProRateA() {
		global $dd;

		$hire_date = $this->getUserObject( $this->user_id )->getHireDate();
		$current_epoch = $hire_date;

		$accrual_policy_account_id = $this->createAccrualPolicyAccount( $this->company_id, 10 );
		$accrual_policy_id = $this->createAccrualPolicy( $this->company_id, 2000, $accrual_policy_account_id );
		$dd->createPolicyGroup( 	$this->company_id,
									NULL,
									NULL,
									NULL,
									NULL,
									NULL,
									NULL,
									array($this->user_id),
									NULL,
									array( $accrual_policy_id ) );

		$this->calcAccrualTime( $this->company_id, $accrual_policy_id, $current_epoch, TTDate::getEndYearEpoch( ( $current_epoch + 86400 * 365 * 2) ) );
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );

		//$this->assertEquals( $accrual_balance, ( (111*3600)+(60*13)+20 ) ); //111:13:20 <-- This was pre-MiddleDayEpoch() in the proRate function.
		$this->assertEquals( $accrual_balance, ( (111 * 3600) + (60 * 11) + 26 ) ); //111:11:26 <-- This is after MiddleDayEpoch() in the proRate function.
	}

	/**
	 * @group AccrualPolicy_testCalendarAccrualProRateB
	 */
	function testCalendarAccrualProRateB() {
		global $dd;

		$hire_date = $this->getUserObject( $this->user_id )->getHireDate();
		$current_epoch = $hire_date;

		$accrual_policy_account_id = $this->createAccrualPolicyAccount( $this->company_id, 10 );
		$accrual_policy_id = $this->createAccrualPolicy( $this->company_id, 2001, $accrual_policy_account_id );
		$dd->createPolicyGroup( 	$this->company_id,
									NULL,
									NULL,
									NULL,
									NULL,
									NULL,
									NULL,
									array($this->user_id),
									NULL,
									array( $accrual_policy_id ) );

		$this->calcAccrualTime( $this->company_id, $accrual_policy_id, $current_epoch, TTDate::getEndYearEpoch( ( $current_epoch + 86400 * 365 * 2) ) );
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );

		$this->assertEquals( $accrual_balance, ( (110 * 3600) + (60 * 0) ) ); //110:00
	}

	/**
	 * @group AccrualPolicy_testCalendarAccrualProRateC
	 */
	function testCalendarAccrualProRateC() {
		global $dd;

		$hire_date = $this->getUserObject( $this->user_id )->getHireDate();
		$current_epoch = $hire_date;

		$accrual_policy_account_id = $this->createAccrualPolicyAccount( $this->company_id, 10 );
		$accrual_policy_id = $this->createAccrualPolicy( $this->company_id, 2010, $accrual_policy_account_id );
		$dd->createPolicyGroup( 	$this->company_id,
									NULL,
									NULL,
									NULL,
									NULL,
									NULL,
									NULL,
									array($this->user_id),
									NULL,
									array( $accrual_policy_id ) );

		$this->calcAccrualTime( $this->company_id, $accrual_policy_id, $current_epoch, TTDate::getEndYearEpoch( ( $current_epoch + 86400 * 365 * 2) ) );
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );

		$this->assertEquals( $accrual_balance, ( (112 * 3600) + (60 * 31) + 5 ) ); //112:31:05
	}

	/**
	 * @group AccrualPolicy_testCalendarAccrualProRateD
	 */
	function testCalendarAccrualProRateD() {
		global $dd;

		$hire_date = $this->getUserObject( $this->user_id )->getHireDate();
		$current_epoch = $hire_date;

		$accrual_policy_account_id = $this->createAccrualPolicyAccount( $this->company_id, 10 );
		$accrual_policy_id = $this->createAccrualPolicy( $this->company_id, 2020, $accrual_policy_account_id );
		$dd->createPolicyGroup( 	$this->company_id,
									NULL,
									NULL,
									NULL,
									NULL,
									NULL,
									NULL,
									array($this->user_id),
									NULL,
									array( $accrual_policy_id ) );

		$this->calcAccrualTime( $this->company_id, $accrual_policy_id, $current_epoch, TTDate::getEndYearEpoch( ( $current_epoch + 86400 * 365 * 2) ) );
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );

		//$this->assertEquals( $accrual_balance, ( (109*3600)+(60*34)+12 ) ); //109:34:12
		$this->assertEquals( $accrual_balance, ( (109 * 3600) + (60 * 34) + 10 ) ); //109:34:10 <-- This is after MiddleDayEpoch() in the proRate function.
	}

	/**
	 * @group AccrualPolicy_testCalendarAccrualProRateE
	 */
	function testCalendarAccrualProRateE() {
		global $dd;

		$hire_date = $this->getUserObject( $this->user_id )->getHireDate();
		$current_epoch = $hire_date;

		$accrual_policy_account_id = $this->createAccrualPolicyAccount( $this->company_id, 10 );
		$accrual_policy_id = $this->createAccrualPolicy( $this->company_id, 2030, $accrual_policy_account_id );
		$dd->createPolicyGroup( 	$this->company_id,
									NULL,
									NULL,
									NULL,
									NULL,
									NULL,
									NULL,
									array($this->user_id),
									NULL,
									array( $accrual_policy_id ) );

		$this->calcAccrualTime( $this->company_id, $accrual_policy_id, $current_epoch, TTDate::getEndYearEpoch( ( $current_epoch + 86400 * 365 * 2) ) );
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );

		$this->assertEquals( $accrual_balance, ( (73 * 3600) + (60 * 5) + 45 ) ); //73:05:45
	}

	/**
	 * @group AccrualPolicy_testCalendarAccrualProRateF
	 */
	function testCalendarAccrualProRateF() {
		global $dd;

		$hire_date = $this->getUserObject( $this->user_id )->getHireDate();
		$current_epoch = $hire_date;

		$accrual_policy_account_id = $this->createAccrualPolicyAccount( $this->company_id, 10 );
		$accrual_policy_id = $this->createAccrualPolicy( $this->company_id, 2031, $accrual_policy_account_id );
		$dd->createPolicyGroup( 	$this->company_id,
									NULL,
									NULL,
									NULL,
									NULL,
									NULL,
									NULL,
									array($this->user_id),
									NULL,
									array( $accrual_policy_id ) );

		$this->calcAccrualTime( $this->company_id, $accrual_policy_id, $current_epoch, TTDate::getEndYearEpoch( ( $current_epoch + 86400 * 365 * 2) ) );
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );

		$this->assertEquals( $accrual_balance, ( (120 * 3600) ) ); //120:00:00
	}


	/**
	 * @group AccrualPolicy_testCalendarAccrualProRateHireDateTimeA
	 */
	function testCalendarAccrualProRateHireDateTimeA() {
		global $dd;

		$u_obj = $this->getUserObject( $this->user_id );
		$u_obj->data['hire_date'] = TTDate::getMiddleDayEpoch( $u_obj->getHireDate() );
		$u_obj->Save();

			$hire_date = $this->getUserObject( $this->user_id )->getHireDate();
		$current_epoch = $hire_date;

		$accrual_policy_account_id = $this->createAccrualPolicyAccount( $this->company_id, 10 );
		$accrual_policy_id = $this->createAccrualPolicy( $this->company_id, 2020, $accrual_policy_account_id );
		$dd->createPolicyGroup( 	$this->company_id,
								   NULL,
								   NULL,
								   NULL,
								   NULL,
								   NULL,
								   NULL,
								   array($this->user_id),
								   NULL,
								   array( $accrual_policy_id ) );

		$this->calcAccrualTime( $this->company_id, $accrual_policy_id, $current_epoch, TTDate::getEndYearEpoch( ( $current_epoch + 86400 * 365 * 2) ) );
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );

		$this->assertEquals( $accrual_balance, ( (109 * 3600) + (60 * 34) + 10 ) ); //109:34:10
	}

	/**
	 * @group AccrualPolicy_testCalendarAccrualProRateHireDateTimeB
	 */
	function testCalendarAccrualProRateHireDateTimeB() {
		global $dd;

		$u_obj = $this->getUserObject( $this->user_id );
		$u_obj->data['hire_date'] = ( TTDate::getBeginDayEpoch( $u_obj->getHireDate() ) + 60 );
		$u_obj->Save();

		$hire_date = $this->getUserObject( $this->user_id )->getHireDate();
		$current_epoch = $hire_date;

		$accrual_policy_account_id = $this->createAccrualPolicyAccount( $this->company_id, 10 );
		$accrual_policy_id = $this->createAccrualPolicy( $this->company_id, 2020, $accrual_policy_account_id );
		$dd->createPolicyGroup( 	$this->company_id,
								   NULL,
								   NULL,
								   NULL,
								   NULL,
								   NULL,
								   NULL,
								   array($this->user_id),
								   NULL,
								   array( $accrual_policy_id ) );

		$this->calcAccrualTime( $this->company_id, $accrual_policy_id, $current_epoch, TTDate::getEndYearEpoch( ( $current_epoch + 86400 * 365 * 2) ) );
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );

		$this->assertEquals( $accrual_balance, ( (109 * 3600) + (60 * 34) + 10 ) ); //109:34:10
	}

	/**
	 * @group AccrualPolicy_testCalendarAccrualProRateHireDateTimeC
	 */
	function testCalendarAccrualProRateHireDateTimeC() {
		global $dd;

		$u_obj = $this->getUserObject( $this->user_id );
		$u_obj->data['hire_date'] = ( TTDate::getEndDayEpoch( $u_obj->getHireDate() ) - 60 );
		$u_obj->Save();

		$hire_date = $this->getUserObject( $this->user_id )->getHireDate();
		$current_epoch = $hire_date;

		$accrual_policy_account_id = $this->createAccrualPolicyAccount( $this->company_id, 10 );
		$accrual_policy_id = $this->createAccrualPolicy( $this->company_id, 2020, $accrual_policy_account_id );
		$dd->createPolicyGroup( 	$this->company_id,
								   NULL,
								   NULL,
								   NULL,
								   NULL,
								   NULL,
								   NULL,
								   array($this->user_id),
								   NULL,
								   array( $accrual_policy_id ) );

		$this->calcAccrualTime( $this->company_id, $accrual_policy_id, $current_epoch, TTDate::getEndYearEpoch( ( $current_epoch + 86400 * 365 * 2) ) );
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );

		$this->assertEquals( $accrual_balance, ( (109 * 3600) + (60 * 34) + 10 ) ); //109:34:10
	}

	/**
	 * @group AccrualPolicy_testCalendarAccrualOnHireDateA
	 */
	function testCalendarAccrualOnHireDateA() {
		global $dd;

		$u_obj = $this->getUserObject( $this->user_id );
		$u_obj->data['hire_date'] = TTDate::getMiddleDayEpoch( $u_obj->getHireDate() ) + ( 86400 * 10 ); //15-Mar-2001
		$u_obj->Save();

		$hire_date = $this->getUserObject( $this->user_id )->getHireDate();
		$current_epoch = $hire_date;

		$accrual_policy_account_id = $this->createAccrualPolicyAccount( $this->company_id, 10 );
		$accrual_policy_id = $this->createAccrualPolicy( $this->company_id, 310, $accrual_policy_account_id ); //15th of each month.
		$dd->createPolicyGroup( 	$this->company_id,
								   NULL,
								   NULL,
								   NULL,
								   NULL,
								   NULL,
								   NULL,
								   array($this->user_id),
								   NULL,
								   array( $accrual_policy_id ) );

		$this->calcAccrualTime( $this->company_id, $accrual_policy_id, $current_epoch, TTDate::getMiddleDayEpoch( ( $current_epoch + 86400 * 45) ) );
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );

		$this->assertEquals( $accrual_balance, ( (6 * 3600) + (60 * 40) + 0 ) ); //6:40
	}

	/**
	 * @group AccrualPolicy_testCalendarAccrualOnHireDateB
	 */
	function testCalendarAccrualOnHireDateB() {
		global $dd;

		$u_obj = $this->getUserObject( $this->user_id );
		$u_obj->data['hire_date'] = TTDate::getMiddleDayEpoch( $u_obj->getHireDate() ) + ( 86400 * 9 ); //14-Mar-2001 (day before the frequency date)
		$u_obj->Save();

		$hire_date = $this->getUserObject( $this->user_id )->getHireDate();
		$current_epoch = $hire_date;

		$accrual_policy_account_id = $this->createAccrualPolicyAccount( $this->company_id, 10 );
		$accrual_policy_id = $this->createAccrualPolicy( $this->company_id, 310, $accrual_policy_account_id ); //15th of each month.
		$dd->createPolicyGroup( 	$this->company_id,
								   NULL,
								   NULL,
								   NULL,
								   NULL,
								   NULL,
								   NULL,
								   array($this->user_id),
								   NULL,
								   array( $accrual_policy_id ) );

		$this->calcAccrualTime( $this->company_id, $accrual_policy_id, $current_epoch, TTDate::getMiddleDayEpoch( ( $current_epoch + 86400 * 45) ) );
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );

		$this->assertEquals( $accrual_balance, ( (6 * 3600) + (60 * 40) + 0 ) ); //6:40
	}

	/**
	 * @group AccrualPolicy_testCalendarAccrualOnHireDateC
	 */
	function testCalendarAccrualOnHireDateC() {
		global $dd;

		$u_obj = $this->getUserObject( $this->user_id );
		$u_obj->data['hire_date'] = TTDate::getMiddleDayEpoch( $u_obj->getHireDate() ) + ( 86400 * 11 ); //16-Mar-2001 (day after the frequency date)
		$u_obj->Save();

		$hire_date = $this->getUserObject( $this->user_id )->getHireDate();
		$current_epoch = $hire_date;

		$accrual_policy_account_id = $this->createAccrualPolicyAccount( $this->company_id, 10 );
		$accrual_policy_id = $this->createAccrualPolicy( $this->company_id, 310, $accrual_policy_account_id ); //15th of each month.
		$dd->createPolicyGroup( 	$this->company_id,
								   NULL,
								   NULL,
								   NULL,
								   NULL,
								   NULL,
								   NULL,
								   array($this->user_id),
								   NULL,
								   array( $accrual_policy_id ) );

		$this->calcAccrualTime( $this->company_id, $accrual_policy_id, $current_epoch, TTDate::getMiddleDayEpoch( ( $current_epoch + 86400 * 45) ) );
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );

		$this->assertEquals( $accrual_balance, ( (3 * 3600) + (60 * 20) + 0 ) ); //3:20
	}

	/**
	 * @group AccrualPolicy_testCalendarAccrualOpeningBalanceA
	 */
	function testCalendarAccrualOpeningBalanceA() {
		global $dd;

		$hire_date = $this->getUserObject( $this->user_id )->getHireDate();
		$current_epoch = $hire_date;

		$accrual_policy_account_id = $this->createAccrualPolicyAccount( $this->company_id, 10 );
		$accrual_policy_id = $this->createAccrualPolicy( $this->company_id, 2100, $accrual_policy_account_id );
		$dd->createPolicyGroup( 	$this->company_id,
									NULL,
									NULL,
									NULL,
									NULL,
									NULL,
									NULL,
									array($this->user_id),
									NULL,
									array( $accrual_policy_id ) );

		$this->calcAccrualTime( $this->company_id, $accrual_policy_id, $current_epoch, TTDate::getEndYearEpoch( ( $current_epoch + 86400 * 365 * 2) ) );
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );

		//$this->assertEquals( $accrual_balance, ( (112*3600)+(60*53)+20 ) ); //111:53:20
		$this->assertEquals( $accrual_balance, ( (112 * 3600) + (60 * 51) + 26 ) ); //111:51:26 <-- This is after MiddleDayEpoch() in the proRate function.
	}

	/**
	 * @group AccrualPolicy_testCalendarAccrualOpeningBalanceB
	 */
	function testCalendarAccrualOpeningBalanceB() {
		global $dd;

		$hire_date = $this->getUserObject( $this->user_id )->getHireDate();
		$current_epoch = $hire_date;

		$accrual_policy_account_id = $this->createAccrualPolicyAccount( $this->company_id, 10 );
		$accrual_policy_id = $this->createAccrualPolicy( $this->company_id, 2101, $accrual_policy_account_id );
		$dd->createPolicyGroup( 	$this->company_id,
									NULL,
									NULL,
									NULL,
									NULL,
									NULL,
									NULL,
									array($this->user_id),
									NULL,
									array( $accrual_policy_id ) );

		$this->calcAccrualTime( $this->company_id, $accrual_policy_id, $current_epoch, TTDate::getEndYearEpoch( ( $current_epoch + 86400 * 365 * 2) ) );
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );

		//Pro-Rate part of the first accrual balance.
		$this->assertEquals( $accrual_balance, ( (112 * 3600) + (3086) ) ); //112:51.432
	}

	/**
	 * @group AccrualPolicy_testCalendarAccrualOpeningBalanceC
	 */
	function testCalendarAccrualOpeningBalanceC() {
		global $dd;

		$hire_date = $this->getUserObject( $this->user_id )->getHireDate();
		$current_epoch = $hire_date;

		$accrual_policy_account_id = $this->createAccrualPolicyAccount( $this->company_id, 10 );
		$accrual_policy_id = $this->createAccrualPolicy( $this->company_id, 2110, $accrual_policy_account_id );
		$dd->createPolicyGroup( 	$this->company_id,
									NULL,
									NULL,
									NULL,
									NULL,
									NULL,
									NULL,
									array($this->user_id),
									NULL,
									array( $accrual_policy_id ) );

		$this->calcAccrualTime( $this->company_id, $accrual_policy_id, $current_epoch, TTDate::getEndYearEpoch( ( $current_epoch + 86400 * 365 * 2) ) );
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );

		$this->assertEquals( $accrual_balance, ( (113 * 3600) + (60 * 17) + 14 ) ); //113:17:14
	}

	/**
	 * @group AccrualPolicy_testCalendarAccrualOpeningBalanceD
	 */
	function testCalendarAccrualOpeningBalanceD() {
		global $dd;

		$hire_date = $this->getUserObject( $this->user_id )->getHireDate();
		$current_epoch = $hire_date;

		$accrual_policy_account_id = $this->createAccrualPolicyAccount( $this->company_id, 10 );
		$accrual_policy_id = $this->createAccrualPolicy( $this->company_id, 2120, $accrual_policy_account_id );
		$dd->createPolicyGroup( 	$this->company_id,
									NULL,
									NULL,
									NULL,
									NULL,
									NULL,
									NULL,
									array($this->user_id),
									NULL,
									array( $accrual_policy_id ) );

		$this->calcAccrualTime( $this->company_id, $accrual_policy_id, $current_epoch, TTDate::getEndYearEpoch( ( $current_epoch + 86400 * 365 * 2) ) );
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );

		//$this->assertEquals( $accrual_balance, ( (112*3600)+(60*54)+12 ) ); //112:54:12
		$this->assertEquals( $accrual_balance, ( (112 * 3600) + (60 * 54) + 10 ) ); //112:54:10 <-- This is after MiddleDayEpoch() in the proRate function.
	}

	/**
	 * @group AccrualPolicy_testCalendarAccrualOpeningBalanceE
	 */
	function testCalendarAccrualOpeningBalanceE() {
		global $dd;

		$hire_date = $this->getUserObject( $this->user_id )->getHireDate();
		$current_epoch = $hire_date;

		$accrual_policy_account_id = $this->createAccrualPolicyAccount( $this->company_id, 10 );
		$accrual_policy_id = $this->createAccrualPolicy( $this->company_id, 2130, $accrual_policy_account_id );
		$dd->createPolicyGroup( 	$this->company_id,
									NULL,
									NULL,
									NULL,
									NULL,
									NULL,
									NULL,
									array($this->user_id),
									NULL,
									array( $accrual_policy_id ) );

		$this->calcAccrualTime( $this->company_id, $accrual_policy_id, $current_epoch, TTDate::getEndYearEpoch( ( $current_epoch + 86400 * 365 * 2) ) );
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );

		$this->assertEquals( $accrual_balance, ( (113 * 3600) + (60 * 5) + 45 ) ); //113:05:45
	}

	/**
	 * @group AccrualPolicy_testCalendarAccrualOpeningBalanceF
	 */
	function testCalendarAccrualOpeningBalanceF() {
		global $dd;

		$hire_date = $this->getUserObject( $this->user_id )->getHireDate();
		$current_epoch = $hire_date;

		$accrual_policy_account_id = $this->createAccrualPolicyAccount( $this->company_id, 10 );
		$accrual_policy_id = $this->createAccrualPolicy( $this->company_id, 2131, $accrual_policy_account_id );
		$dd->createPolicyGroup( 	$this->company_id,
									NULL,
									NULL,
									NULL,
									NULL,
									NULL,
									NULL,
									array($this->user_id),
									NULL,
									array( $accrual_policy_id ) );

		$this->calcAccrualTime( $this->company_id, $accrual_policy_id, $current_epoch, TTDate::getEndYearEpoch( ( $current_epoch + 86400 * 365 * 2) ) );
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );

		$this->assertEquals( $accrual_balance, ( (120 * 3600) ) ); //120:00:00
	}

	/**
	 * @group testAbsenceAccrualPolicyA
	 */
	function testAbsenceAccrualPolicyA() {
		global $dd;

		$accrual_policy_account_id = $this->createAccrualPolicyAccount( $this->company_id, 10 ); //Bank Time

		$policy_ids['pay_formula_policy'][910] = $this->createPayFormulaPolicy( $this->company_id, 910, $accrual_policy_account_id ); //Bank Accrual

		$policy_ids['pay_code'][910] = $dd->createPayCode( $this->company_id, 910, $policy_ids['pay_formula_policy'][910] ); //Bank

		$policy_ids['absence_policy'][10] = $dd->createAbsencePolicy( $this->company_id, 10, $policy_ids['pay_code'][910] );

		//Create Policy Group
		$dd->createPolicyGroup( 	$this->company_id,
									NULL, //Meal
									NULL, //Exception
									NULL, //Holiday
									NULL, //OT
									NULL, //Premium
									NULL, //Round
									array($this->user_id), //Users
									NULL, //Break
									NULL, //Accrual
									NULL, //Expense
									$policy_ids['absence_policy'], //Absence
									NULL //Regular
									);

		$date_epoch = TTDate::getBeginWeekEpoch( time() );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		//Make sure balance starts at 0
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );
		$this->assertEquals( $accrual_balance, 0 );

		//Day 1
		$dd->createAbsence( $this->user_id, $date_epoch, (1 * 3600), $policy_ids['absence_policy'][10] );
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );
		$this->assertEquals( $accrual_balance, (1 * -3600) );

		//Day 2
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (1 * 86400 + 3601)) ;
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createAbsence( $this->user_id, $date_epoch, (1 * 3600), $policy_ids['absence_policy'][10] );
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );
		$this->assertEquals( $accrual_balance, (2 * -3600) );

		//Day 3
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (2 * 86400 + 3601)) ;
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createAbsence( $this->user_id, $date_epoch, (1 * 3600), $policy_ids['absence_policy'][10] );
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );
		$this->assertEquals( $accrual_balance, (3 * -3600) );

		//Day 4
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (3 * 86400 + 3601)) ;
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createAbsence( $this->user_id, $date_epoch, (1 * 3600), $policy_ids['absence_policy'][10] );
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );
		$this->assertEquals( $accrual_balance, (4 * -3600) );

		//Day 5
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (4 * 86400 + 3601)) ;
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createAbsence( $this->user_id, $date_epoch, (1 * 3600), $policy_ids['absence_policy'][10] );
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );
		$this->assertEquals( $accrual_balance, (5 * -3600) );

		//Day 6
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (5 * 86400 + 3601)) ;
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createAbsence( $this->user_id, $date_epoch, (1 * 3600), $policy_ids['absence_policy'][10] );
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );
		$this->assertEquals( $accrual_balance, (6 * -3600) );

		//Day 7
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (6 * 86400 + 3601)) ;
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createAbsence( $this->user_id, $date_epoch, (1 * 3600), $policy_ids['absence_policy'][10] );
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );
		$this->assertEquals( $accrual_balance, (7 * -3600) );

		//Day 8
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (7 * 86400 + 3601)) ;
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createAbsence( $this->user_id, $date_epoch, (1 * 3600), $policy_ids['absence_policy'][10] );
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );
		$this->assertEquals( $accrual_balance, (8 * -3600) );

		//Day 9
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (8 * 86400 + 3601)) ;
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createAbsence( $this->user_id, $date_epoch, (1 * 3600), $policy_ids['absence_policy'][10] );
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );
		$this->assertEquals( $accrual_balance, (9 * -3600) );

		//Day 10
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (9 * 86400 + 3601)) ;
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createAbsence( $this->user_id, $date_epoch, (1 * 3600), $policy_ids['absence_policy'][10] );
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );
		$this->assertEquals( $accrual_balance, (10 * -3600) );

		//Day 11
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (10 * 86400 + 3601)) ;
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createAbsence( $this->user_id, $date_epoch, (1 * 3600), $policy_ids['absence_policy'][10] );
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );
		$this->assertEquals( $accrual_balance, (11 * -3600) );

		//Day 12
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (11 * 86400 + 3601)) ;
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$absence_id = $dd->createAbsence( $this->user_id, $date_epoch, (1 * 3600), $policy_ids['absence_policy'][10] );
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );
		$this->assertEquals( $accrual_balance, (12 * -3600) );

		//Day 13
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (12 * 86400 + 3601)) ;
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createAbsence( $this->user_id, $date_epoch, (1 * 3600), $policy_ids['absence_policy'][10] );
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );
		$this->assertEquals( $accrual_balance, (13 * -3600) );

		//Day 14
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (13 * 86400 + 3601)) ;
		$date_stamp = TTDate::getDate('DATE', $date_epoch );

		$dd->createAbsence( $this->user_id, $date_epoch, (1 * 3600), $policy_ids['absence_policy'][10] );
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );
		$this->assertEquals( $accrual_balance, (14 * -3600) );

		//Delete absence_id from Day 12th.
		$dd->deleteAbsence( $absence_id );
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );
		$this->assertEquals( $accrual_balance, (13 * -3600) );
	}

	/**
	 * @group AccrualPolicy_testHourAccrualA
	 */
	function testHourAccrualA() {
		global $dd;

		$policy_ids['pay_formula_policy'][100] = $dd->createPayFormulaPolicy( $this->company_id, 100 ); //Reg 1.0x

		$policy_ids['pay_code'][100] = $dd->createPayCode( $this->company_id, 100, $policy_ids['pay_formula_policy'][100] ); //Regular

		$policy_ids['contributing_pay_code_policy'][10] = $dd->createContributingPayCodePolicy( $this->company_id, 10, array( $policy_ids['pay_code'][100] ) ); //Regular
		$policy_ids['contributing_pay_code_policy'][99] = $dd->createContributingPayCodePolicy( $this->company_id, 99, $policy_ids['pay_code'] ); //All Time

		$policy_ids['contributing_shift_policy'][10] = $dd->createContributingShiftPolicy( $this->company_id, 10, $policy_ids['contributing_pay_code_policy'][10] ); //Regular

		$policy_ids['regular'][10] = $dd->createRegularTimePolicy( $this->company_id, 10, $policy_ids['contributing_shift_policy'][10], $policy_ids['pay_code'][100] );

		$hire_date = $this->getUserObject( $this->user_id )->getHireDate();
		$current_epoch = TTDate::getBeginYearEpoch( $hire_date + (86400 * 365 * 2) );

		$accrual_policy_account_id = $this->createAccrualPolicyAccount( $this->company_id, 10 );
		$accrual_policy_id = $this->createAccrualPolicy( $this->company_id, 1000, $accrual_policy_account_id, $policy_ids['contributing_shift_policy'][10] );
		$dd->createPolicyGroup( 	$this->company_id,
									NULL, //Meal
									NULL, //Exception
									NULL, //Holiday
									NULL, //OT
									NULL, //Premium
									NULL, //Round
									array($this->user_id), //Users
									NULL, //Break
									array( $accrual_policy_id ), //Accrual
									NULL, //Expense
									NULL, //Absence
									array($policy_ids['regular'][10]) //Regular
									);

		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );
		$this->assertEquals( $accrual_balance, (0 * 3600) );

		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (0 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );
		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 2:00PM'),
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
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );
		$this->assertEquals( $accrual_balance, (6 * 3600) );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 2:00PM'),
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
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );
		$this->assertEquals( $accrual_balance, (10 * 3600) );

		//Add batch of punches
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (1 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );
		$this->createPunches( $date_epoch, ( TTDate::getMiddleDayEpoch($date_epoch) + (9 * 86400) ), '8:00AM', '6:00PM' ); //Create 10 days worth of punches.
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );
		$this->assertEquals( $accrual_balance, (110 * 3600) );


		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (11 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );
		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 2:00PM'),
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
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );
		$this->assertEquals( $accrual_balance, (116 * 3600) );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 2:00PM'),
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
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );
		$this->assertEquals( $accrual_balance, (120 * 3600) );
	}

	/**
	 * @group AccrualPolicy_testHourAccrualB
	 */
	function testHourAccrualB() {
		if ( getTTProductEdition() == TT_PRODUCT_COMMUNITY ) {
			return TRUE;
		}

		global $dd;

		$policy_ids['pay_formula_policy'][100] = $dd->createPayFormulaPolicy( $this->company_id, 100 ); //Reg 1.0x

		$policy_ids['pay_code'][100] = $dd->createPayCode( $this->company_id, 100, $policy_ids['pay_formula_policy'][100] ); //Regular

		$policy_ids['contributing_pay_code_policy'][10] = $dd->createContributingPayCodePolicy( $this->company_id, 10, array( $policy_ids['pay_code'][100] ) ); //Regular
		$policy_ids['contributing_pay_code_policy'][99] = $dd->createContributingPayCodePolicy( $this->company_id, 99, $policy_ids['pay_code'] ); //All Time

		$policy_ids['contributing_shift_policy'][10] = $dd->createContributingShiftPolicy( $this->company_id, 10, $policy_ids['contributing_pay_code_policy'][10] ); //Regular

		$policy_ids['regular'][10] = $dd->createRegularTimePolicy( $this->company_id, 10, $policy_ids['contributing_shift_policy'][10], $policy_ids['pay_code'][100] );

		$hire_date = $this->getUserObject( $this->user_id )->getHireDate();
		$current_epoch = TTDate::getBeginYearEpoch( $hire_date + (86400 * 365 * 2) );

		$accrual_policy_account_id = $this->createAccrualPolicyAccount( $this->company_id, 10 );
		$accrual_policy_id = $this->createAccrualPolicy( $this->company_id, 1000, $accrual_policy_account_id, $policy_ids['contributing_shift_policy'][10] );
		$dd->createPolicyGroup( 	$this->company_id,
									NULL, //Meal
									NULL, //Exception
									NULL, //Holiday
									NULL, //OT
									NULL, //Premium
									NULL, //Round
									array($this->user_id), //Users
									NULL, //Break
									array( $accrual_policy_id ), //Accrual
									NULL, //Expense
									NULL, //Absence
									array($policy_ids['regular'][10]) //Regular
									);

		//Test UserModifier values.
		$this->createAccrualPolicyUserModifier( $accrual_policy_id, $this->user_id, strtotime( '+10 months', $hire_date), 2 );

		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );
		$this->assertEquals( $accrual_balance, (0 * 3600) );

		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (0 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );
		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 2:00PM'),
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
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );
		$this->assertEquals( $accrual_balance, (12 * 3600) );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 2:00PM'),
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
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );
		$this->assertEquals( $accrual_balance, (20 * 3600) );

		//Add batch of punches
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (1 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );
		$this->createPunches( $date_epoch, ( TTDate::getMiddleDayEpoch($date_epoch) + (9 * 86400) ), '8:00AM', '6:00PM' ); //Create 10 days worth of punches.
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );
		$this->assertEquals( $accrual_balance, (220 * 3600) );


		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (11 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );
		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 2:00PM'),
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
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );
		$this->assertEquals( $accrual_balance, (232 * 3600) );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 2:00PM'),
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
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );
		$this->assertEquals( $accrual_balance, (240 * 3600) );
	}

	/**
	 * @group AccrualPolicy_testHourAccrualMaximumBalanceA
	 */
	function testHourAccrualMaximumBalanceA() {
		global $dd;

		$accrual_policy_account_id = $this->createAccrualPolicyAccount( $this->company_id, 10 );

		$policy_ids['pay_formula_policy'][100] = $dd->createPayFormulaPolicy( $this->company_id, 100 ); //Reg 1.0x
		$policy_ids['pay_formula_policy'][910] = $this->createPayFormulaPolicy( $this->company_id, 910, $accrual_policy_account_id ); //Bank Accrual

		$policy_ids['pay_code'][100] = $dd->createPayCode( $this->company_id, 100, $policy_ids['pay_formula_policy'][100] ); //Regular
		$policy_ids['pay_code'][910] = $dd->createPayCode( $this->company_id, 910, $policy_ids['pay_formula_policy'][910] ); //Bank

		$policy_ids['contributing_pay_code_policy'][10] = $dd->createContributingPayCodePolicy( $this->company_id, 10, array( $policy_ids['pay_code'][100] ) ); //Regular
		$policy_ids['contributing_pay_code_policy'][99] = $dd->createContributingPayCodePolicy( $this->company_id, 99, $policy_ids['pay_code'] ); //All Time

		$policy_ids['contributing_shift_policy'][10] = $dd->createContributingShiftPolicy( $this->company_id, 10, $policy_ids['contributing_pay_code_policy'][10] ); //Regular

		$policy_ids['regular'][10] = $dd->createRegularTimePolicy( $this->company_id, 10, $policy_ids['contributing_shift_policy'][10], $policy_ids['pay_code'][100] );
		$policy_ids['absence_policy'][10] = $dd->createAbsencePolicy( $this->company_id, 10, $policy_ids['pay_code'][910] );

		$hire_date = $this->getUserObject( $this->user_id )->getHireDate();
		$current_epoch = TTDate::getBeginYearEpoch( $hire_date + (86400 * 365 * 2) );

		$accrual_policy_id = $this->createAccrualPolicy( $this->company_id, 1010, $accrual_policy_account_id, $policy_ids['contributing_shift_policy'][10] );
		$dd->createPolicyGroup( 	$this->company_id,
									NULL, //Meal
									NULL, //Exception
									NULL, //Holiday
									NULL, //OT
									NULL, //Premium
									NULL, //Round
									array($this->user_id), //Users
									NULL, //Break
									array( $accrual_policy_id ), //Accrual
									NULL, //Expense
									$policy_ids['absence_policy'], //Absence
									array($policy_ids['regular'][10]) //Regular
									);

		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (0 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );
		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 2:00PM'),
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
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );
		$this->assertEquals( $accrual_balance, (6 * 3600) );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 2:00PM'),
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
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );
		$this->assertEquals( $accrual_balance, (10 * 3600) );


		//Add batch of punches
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (1 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );
		$this->createPunches( $date_epoch, ( TTDate::getMiddleDayEpoch($date_epoch) + (9 * 86400) ), '8:00AM', '6:00PM' ); //Create 10 days worth of punches.
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );
		$this->assertEquals( $accrual_balance, (110 * 3600) );


		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (11 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );
		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 2:00PM'),
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
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );
		$this->assertEquals( $accrual_balance, (116 * 3600) );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 2:00PM'),
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
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );
		$this->assertEquals( $accrual_balance, (118 * 3600) ); //Hit maximum balance.


		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (12 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );
		$dd->createAbsence( $this->user_id, $date_epoch, (5 * 3600), $policy_ids['absence_policy'][10] );
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );
		$this->assertEquals( $accrual_balance, (113 * 3600) ); //Reduce maximum balance, so we can hit again below.


		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() ) + (13 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );
		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 2:00PM'),
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
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );
		$this->assertEquals( $accrual_balance, (118 * 3600) );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 2:00PM'),
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
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );
		$this->assertEquals( $accrual_balance, (118 * 3600) ); //Hit maximum balance.
	}

	/**
	 * Test recalculating the entire pay period to ensure the balances are still the same as if the user just punched in/out day-by-day in real-time.
	 * @group AccrualPolicy_testHourAccrualMaximumBalanceReCalculate
	 */
	function testHourAccrualMaximumBalanceReCalculate() {
		global $dd;

		$accrual_policy_account_id = $this->createAccrualPolicyAccount( $this->company_id, 10 );

		$policy_ids['pay_formula_policy'][100] = $dd->createPayFormulaPolicy( $this->company_id, 100 ); //Reg 1.0x
		$policy_ids['pay_formula_policy'][910] = $this->createPayFormulaPolicy( $this->company_id, 910, $accrual_policy_account_id ); //Bank Accrual

		$policy_ids['pay_code'][100] = $dd->createPayCode( $this->company_id, 100, $policy_ids['pay_formula_policy'][100] ); //Regular
		$policy_ids['pay_code'][910] = $dd->createPayCode( $this->company_id, 910, $policy_ids['pay_formula_policy'][910] ); //Bank

		$policy_ids['contributing_pay_code_policy'][10] = $dd->createContributingPayCodePolicy( $this->company_id, 10, array( $policy_ids['pay_code'][100] ) ); //Regular
		$policy_ids['contributing_pay_code_policy'][99] = $dd->createContributingPayCodePolicy( $this->company_id, 99, $policy_ids['pay_code'] ); //All Time

		$policy_ids['contributing_shift_policy'][10] = $dd->createContributingShiftPolicy( $this->company_id, 10, $policy_ids['contributing_pay_code_policy'][10] ); //Regular

		$policy_ids['regular'][10] = $dd->createRegularTimePolicy( $this->company_id, 10, $policy_ids['contributing_shift_policy'][10], $policy_ids['pay_code'][100] );
		$policy_ids['absence_policy'][10] = $dd->createAbsencePolicy( $this->company_id, 10, $policy_ids['pay_code'][910] );

		$accrual_policy_id = $this->createAccrualPolicy( $this->company_id, 1010, $accrual_policy_account_id, $policy_ids['contributing_shift_policy'][10] );
		$dd->createPolicyGroup( 	$this->company_id,
								   NULL, //Meal
								   NULL, //Exception
								   NULL, //Holiday
								   NULL, //OT
								   NULL, //Premium
								   NULL, //Round
								   array($this->user_id), //Users
								   NULL, //Break
								   array( $accrual_policy_id ), //Accrual
								   NULL, //Expense
								   $policy_ids['absence_policy'], //Absence
								   array($policy_ids['regular'][10]) //Regular
		);

		$hire_date = $this->getUserObject( $this->user_id )->getHireDate();
		$current_epoch = TTDate::getBeginWeekEpoch( ( TTDate::getEndWeekEpoch( $hire_date ) + (8 * 86400 + 3601) ) );

		$date_epoch = TTDate::getBeginDayEpoch( ( $current_epoch + (0 * 86400 + 3601) ) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );
		$dd->createPunchPair( 	$this->user_id,
								 strtotime($date_stamp.' 8:00AM'),
								 strtotime($date_stamp.' 2:00PM'),
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
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );
		$this->assertEquals( $accrual_balance, (6 * 3600) );

		$dd->createPunchPair( 	$this->user_id,
								 strtotime($date_stamp.' 2:00PM'),
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
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );
		$this->assertEquals( $accrual_balance, (14 * 3600) );


		//Add batch of punches
		$date_epoch = TTDate::getBeginDayEpoch( ( $current_epoch + (1 * 86400 + 3601) ) );
		$this->createPunches( $date_epoch, ( TTDate::getMiddleDayEpoch($date_epoch) + (9 * 86400) ), '8:00AM', '8:00PM' ); //Create 10 days worth of punches.
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );
		$this->assertEquals( $accrual_balance, (118 * 3600) );


		$date_epoch = TTDate::getBeginDayEpoch( ( $current_epoch + (11 * 86400 + 3601) ) );
        $dd->createAbsence( $this->user_id, $date_epoch, (7 * 3600), $policy_ids['absence_policy'][10] );
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );
		$this->assertEquals( $accrual_balance, (111 * 3600) ); //Reduce maximum balance, so we can hit again below.

		$date_stamp = TTDate::getDate('DATE', $date_epoch );
		$dd->createPunchPair( 	$this->user_id,
								 strtotime($date_stamp.' 8:00AM'),
								 strtotime($date_stamp.' 2:00PM'),
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
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );
		$this->assertEquals( $accrual_balance, (117 * 3600) );

		$dd->createPunchPair( 	$this->user_id,
								 strtotime($date_stamp.' 2:00PM'),
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
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );
		$this->assertEquals( $accrual_balance, (118 * 3600) ); //Hit maximum balance.


		$date_epoch = TTDate::getBeginDayEpoch( ( $current_epoch + (12 * 86400 + 3601) ) );
		$dd->createAbsence( $this->user_id, $date_epoch, (7 * 3600), $policy_ids['absence_policy'][10] );
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );
		$this->assertEquals( $accrual_balance, (111 * 3600) ); //Reduce maximum balance, so we can hit again below.

		$date_stamp = TTDate::getDate('DATE', $date_epoch );
		$dd->createPunchPair( 	$this->user_id,
								 strtotime($date_stamp.' 8:00AM'),
								 strtotime($date_stamp.' 2:00PM'),
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
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );
		$this->assertEquals( $accrual_balance, (117 * 3600) );

		$dd->createPunchPair( 	$this->user_id,
								 strtotime($date_stamp.' 2:00PM'),
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
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );
		$this->assertEquals( $accrual_balance, (118 * 3600) ); //Hit maximum balance.


		$date_epoch = TTDate::getBeginDayEpoch( ( $current_epoch + (13 * 86400 + 3601) ) );
		$dd->createAbsence( $this->user_id, $date_epoch, (7 * 3600), $policy_ids['absence_policy'][10] );
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );
		$this->assertEquals( $accrual_balance, (111 * 3600) ); //Reduce maximum balance, so we can hit again below.

		$date_stamp = TTDate::getDate('DATE', $date_epoch );
		$dd->createPunchPair( 	$this->user_id,
								 strtotime($date_stamp.' 8:00AM'),
								 strtotime($date_stamp.' 2:00PM'),
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
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );
		$this->assertEquals( $accrual_balance, (117 * 3600) );

		$dd->createPunchPair( 	$this->user_id,
								 strtotime($date_stamp.' 2:00PM'),
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
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );
		$this->assertEquals( $accrual_balance, (118 * 3600) ); //Hit maximum balance.



		//Make sure the proper accrual records exist that reduce the accrual, then accrue more on the last three days.
		$accrual_arr = $this->getAccrualArray(  $this->user_id, $accrual_policy_account_id );
		$this->assertEquals( count($accrual_arr), 13 ); //13 total days.

		$date_epoch = TTDate::getMiddleDayEpoch( strtotime('27-Mar-01') );
		$this->assertArrayHasKey( $date_epoch, $accrual_arr );
		$this->assertEquals( $accrual_arr[$date_epoch][0]->getAmount(), (8 * 3600 ) );

		$date_epoch = TTDate::getMiddleDayEpoch( strtotime('29-Mar-01') );
		$this->assertArrayHasKey( $date_epoch, $accrual_arr );
		$this->assertEquals( $accrual_arr[$date_epoch][0]->getAmount(), (7 * 3600 ) );
		$this->assertEquals( $accrual_arr[$date_epoch][1]->getAmount(), (-7 * 3600 ) );

		$date_epoch = TTDate::getMiddleDayEpoch( strtotime('30-Mar-01') );
		$this->assertArrayHasKey( $date_epoch, $accrual_arr );
		$this->assertEquals( $accrual_arr[$date_epoch][0]->getAmount(), (7 * 3600 ) );
		$this->assertEquals( $accrual_arr[$date_epoch][1]->getAmount(), (-7 * 3600 ) );

		$date_epoch = TTDate::getMiddleDayEpoch( strtotime('31-Mar-01') );
		$this->assertArrayHasKey( $date_epoch, $accrual_arr );
		$this->assertEquals( $accrual_arr[$date_epoch][0]->getAmount(), (7 * 3600 ) );
		$this->assertEquals( $accrual_arr[$date_epoch][1]->getAmount(), (-7 * 3600 ) );
	}

	/**
	 * @group AccrualPolicy_testHourAccrualAnnualMaximumA
	 */
	function testHourAccrualAnnualMaximumA() {
		global $dd;

		$accrual_policy_account_id = $this->createAccrualPolicyAccount( $this->company_id, 10 );

		$policy_ids['pay_formula_policy'][100] = $dd->createPayFormulaPolicy( $this->company_id, 100 ); //Reg 1.0x
		$policy_ids['pay_formula_policy'][910] = $this->createPayFormulaPolicy( $this->company_id, 910, $accrual_policy_account_id ); //Bank Accrual

		$policy_ids['pay_code'][100] = $dd->createPayCode( $this->company_id, 100, $policy_ids['pay_formula_policy'][100] ); //Regular
		$policy_ids['pay_code'][910] = $dd->createPayCode( $this->company_id, 910, $policy_ids['pay_formula_policy'][910] ); //Bank

		$policy_ids['contributing_pay_code_policy'][10] = $dd->createContributingPayCodePolicy( $this->company_id, 10, array( $policy_ids['pay_code'][100] ) ); //Regular
		$policy_ids['contributing_pay_code_policy'][99] = $dd->createContributingPayCodePolicy( $this->company_id, 99, $policy_ids['pay_code'] ); //All Time

		$policy_ids['contributing_shift_policy'][10] = $dd->createContributingShiftPolicy( $this->company_id, 10, $policy_ids['contributing_pay_code_policy'][10] ); //Regular

		$policy_ids['regular'][10] = $dd->createRegularTimePolicy( $this->company_id, 10, $policy_ids['contributing_shift_policy'][10], $policy_ids['pay_code'][100] );
		$policy_ids['absence_policy'][10] = $dd->createAbsencePolicy( $this->company_id, 10, $policy_ids['pay_code'][910] );

		$hire_date = $this->getUserObject( $this->user_id )->getHireDate();
		//$current_epoch = TTDate::getBeginYearEpoch( $hire_date+(86400*365*5) );
		$current_epoch = TTDate::getBeginMonthEpoch( ($hire_date - (86400 * 7) + (86400 * 365 * 5)) );

		$accrual_policy_id = $this->createAccrualPolicy( $this->company_id, 1020, $accrual_policy_account_id, $policy_ids['contributing_shift_policy'][10] );
		$dd->createPolicyGroup( 	$this->company_id,
									NULL, //Meal
									NULL, //Exception
									NULL, //Holiday
									NULL, //OT
									NULL, //Premium
									NULL, //Round
									array($this->user_id), //Users
									NULL, //Break
									array( $accrual_policy_id ), //Accrual
									NULL, //Expense
									$policy_ids['absence_policy'], //Absence
									array($policy_ids['regular'][10]) //Regular
									);

		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( $current_epoch ) + (0 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );
		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 2:00PM'),
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
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );
		$this->assertEquals( $accrual_balance, (6 * 3600) );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 2:00PM'),
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
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );
		$this->assertEquals( $accrual_balance, (10 * 3600) );


		//Add batch of punches
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( $current_epoch ) + (1 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );
		$this->createPunches( $date_epoch, ( TTDate::getMiddleDayEpoch($date_epoch) + (9 * 86400) ), '8:00AM', '6:00PM' ); //Create 10 days worth of punches.
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );
		$this->assertEquals( $accrual_balance, (110 * 3600) );


		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( $current_epoch ) + (11 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );
		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 2:00PM'),
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
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );
		$this->assertEquals( $accrual_balance, (112 * 3600) );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 2:00PM'),
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
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );
		$this->assertEquals( $accrual_balance, (112 * 3600) ); //Hit maximum balance.


		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( $current_epoch ) + (12 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );
		$dd->createAbsence( $this->user_id, $date_epoch, (5 * 3600), $policy_ids['absence_policy'][10] );
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );
		$this->assertEquals( $accrual_balance, (107 * 3600) ); //Reduce maximum balance, so we can hit again below.


		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( $current_epoch ) + (13 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );
		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 2:00PM'),
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
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );
		$this->assertEquals( $accrual_balance, (107 * 3600) );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 2:00PM'),
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
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );
		$this->assertEquals( $accrual_balance, (107 * 3600) ); //Hit maximum balance.

		//
		//Test immediately before employment anniversary date (shouldn't be any increases as maximum accrual limit is still in effect)
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( $current_epoch ) + (34 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );
		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 2:00PM'),
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
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );
		$this->assertEquals( $accrual_balance, (107 * 3600) );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 2:00PM'),
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
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );
		$this->assertEquals( $accrual_balance, (107 * 3600) );

		//
		//Test on employment anniversary date (limit should be reset now, so increases balance)
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( $current_epoch ) + (35 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );
		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 10:00AM'),
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
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );
		$this->assertEquals( $accrual_balance, (109 * 3600) );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 12:00PM'),
								strtotime($date_stamp.' 2:00PM'),
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
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );
		$this->assertEquals( $accrual_balance, (111 * 3600) );

		//
		//Test on the day after the employment anniversary date (limit should be reset now, so increases balance)
		//
		$date_epoch = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( $current_epoch ) + (36 * 86400 + 3601) );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );
		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 8:00AM'),
								strtotime($date_stamp.' 2:00PM'),
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
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );
		$this->assertEquals( $accrual_balance, (117 * 3600) );

		$dd->createPunchPair( 	$this->user_id,
								strtotime($date_stamp.' 2:00PM'),
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
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );
		$this->assertEquals( $accrual_balance, (118 * 3600) ); //Reached maximum balance here.
	}

	/**
	 * @group AccrualPolicy_testInApplyFrequencyWindowA
	 */
	function testInApplyFrequencyWindowA() {
		global $config_vars;
		//Test InApplyFrequency when the system timezone is EST5EDT, but some users are MST5MDT.
		$user_obj = $this->getUserObject( $this->user_id );

		$ap_obj = TTnew('AccrualPolicyFactory'); /** @var AccrualPolicyFactory $ap_obj */
		$ap_obj->setType( 20 );
		$ap_obj->setApplyFrequency( 10 ); //Each Pay Period
		$ap_obj->setMilestoneRolloverHireDate( TRUE );
		$ap_obj->setMinimumEmployedDays( 0 );


		$offset = 79200;

		$current_epoch = strtotime('14-Aug-2016 1:30AM EDT');
		TTDate::setTimeZone('EST5EDT', TRUE); //Due to being a singleton and PHPUnit resetting the state, always force the timezone to be set.
		$config_vars['other']['system_timezone'] = TTDate::getTimeZone();

		$pay_period_dates = array('start_date' => strtotime('31-Jul-2016 12:00AM'), 'end_date' => strtotime('13-Aug-2016 11:59:59PM') );

		$this->assertEquals( $ap_obj->inApplyFrequencyWindow( $current_epoch, $offset, $pay_period_dates, $user_obj ), TRUE );


		TTDate::setTimeZone('MST7MDT', TRUE); //Due to being a singleton and PHPUnit resetting the state, always force the timezone to be set.
		$pay_period_dates = array('start_date' => strtotime('31-Jul-2016 12:00AM'), 'end_date' => strtotime('13-Aug-2016 11:59:59PM') );

		$this->assertEquals( $ap_obj->inApplyFrequencyWindow( $current_epoch, $offset, $pay_period_dates, $user_obj ), TRUE );
		$this->assertEquals( $ap_obj->inApplyFrequencyWindow( strtotime('12-Aug-2016 11:30PM MDT'), $offset, $pay_period_dates, $user_obj ), FALSE );
		$this->assertEquals( $ap_obj->inApplyFrequencyWindow( strtotime('13-Aug-2016 9:30PM MDT'), $offset, $pay_period_dates, $user_obj ), FALSE );
		$this->assertEquals( $ap_obj->inApplyFrequencyWindow( strtotime('13-Aug-2016 9:59:58PM MDT'), $offset, $pay_period_dates, $user_obj ), FALSE );
		$this->assertEquals( $ap_obj->inApplyFrequencyWindow( strtotime('13-Aug-2016 9:59:59PM MDT'), $offset, $pay_period_dates, $user_obj ), TRUE );
		$this->assertEquals( $ap_obj->inApplyFrequencyWindow( strtotime('13-Aug-2016 10:00PM MDT'), $offset, $pay_period_dates, $user_obj ), TRUE );
		$this->assertEquals( $ap_obj->inApplyFrequencyWindow( strtotime('13-Aug-2016 10:01PM MDT'), $offset, $pay_period_dates, $user_obj ), TRUE );
		$this->assertEquals( $ap_obj->inApplyFrequencyWindow( strtotime('13-Aug-2016 10:30PM MDT'), $offset, $pay_period_dates, $user_obj ), TRUE );
		$this->assertEquals( $ap_obj->inApplyFrequencyWindow( strtotime('13-Aug-2016 11:30PM MDT'), $offset, $pay_period_dates, $user_obj ), TRUE );
		$this->assertEquals( $ap_obj->inApplyFrequencyWindow( strtotime('13-Aug-2016 11:59PM MDT'), $offset, $pay_period_dates, $user_obj ), TRUE );
		$this->assertEquals( $ap_obj->inApplyFrequencyWindow( strtotime('14-Aug-2016 12:00AM MDT'), $offset, $pay_period_dates, $user_obj ), TRUE );
		$this->assertEquals( $ap_obj->inApplyFrequencyWindow( strtotime('14-Aug-2016 12:01AM MDT'), $offset, $pay_period_dates, $user_obj ), TRUE );
		$this->assertEquals( $ap_obj->inApplyFrequencyWindow( strtotime('14-Aug-2016 7:59PM MDT'), $offset, $pay_period_dates, $user_obj ), TRUE );
		$this->assertEquals( $ap_obj->inApplyFrequencyWindow( strtotime('14-Aug-2016 8:00PM MDT'), $offset, $pay_period_dates, $user_obj ), FALSE );
		$this->assertEquals( $ap_obj->inApplyFrequencyWindow( strtotime('14-Aug-2016 8:01PM MDT'), $offset, $pay_period_dates, $user_obj ), FALSE );
		$this->assertEquals( $ap_obj->inApplyFrequencyWindow( strtotime('14-Aug-2016 11:30PM MDT'), $offset, $pay_period_dates, $user_obj ), FALSE );
		$this->assertEquals( $ap_obj->inApplyFrequencyWindow( strtotime('15-Aug-2016 11:30PM MDT'), $offset, $pay_period_dates, $user_obj ), FALSE );
	}

	/**
	 * @group testHourAccrualWithRollOverOnHireDateA
	 */
	function testHourAccrualWithRollOverOnHireDateA() {
		//Make sure that a new hire who works on their hire date and accrues time, doesn't trigger a rollover and have their
		// accrued time reset to 0.
		global $dd;

		$policy_ids['pay_formula_policy'][100] = $dd->createPayFormulaPolicy( $this->company_id, 100 ); //Reg 1.0x

		$policy_ids['pay_code'][100] = $dd->createPayCode( $this->company_id, 100, $policy_ids['pay_formula_policy'][100] ); //Regular

		$policy_ids['contributing_pay_code_policy'][10] = $dd->createContributingPayCodePolicy( $this->company_id, 10, array( $policy_ids['pay_code'][100] ) ); //Regular
		$policy_ids['contributing_pay_code_policy'][99] = $dd->createContributingPayCodePolicy( $this->company_id, 99, $policy_ids['pay_code'] ); //All Time

		$policy_ids['contributing_shift_policy'][10] = $dd->createContributingShiftPolicy( $this->company_id, 10, $policy_ids['contributing_pay_code_policy'][10] ); //Regular

		$policy_ids['regular'][10] = $dd->createRegularTimePolicy( $this->company_id, 10, $policy_ids['contributing_shift_policy'][10], $policy_ids['pay_code'][100] );

		$user_obj = $this->getUserObject( $this->user_id );
		$user_obj->setHireDate( time() - (86400 ) ); //Set hire date to yesterday.
		if ( $user_obj->isValid() ) {
			$user_obj->Save( FALSE );
		}

		$hire_date = $user_obj->getHireDate();
		$current_epoch = $hire_date;

		$accrual_policy_account_id = $this->createAccrualPolicyAccount( $this->company_id, 10 );
		$accrual_policy_id = $this->createAccrualPolicy( $this->company_id, 1001, $accrual_policy_account_id, $policy_ids['contributing_shift_policy'][10] );
		$dd->createPolicyGroup( 	$this->company_id,
								   NULL, //Meal
								   NULL, //Exception
								   NULL, //Holiday
								   NULL, //OT
								   NULL, //Premium
								   NULL, //Round
								   array($this->user_id), //Users
								   NULL, //Break
								   array( $accrual_policy_id ), //Accrual
								   NULL, //Expense
								   NULL, //Absence
								   array($policy_ids['regular'][10]) //Regular
		);

		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );
		$this->assertEquals( $accrual_balance, (0 * 3600) );

		//Add punches on hire date.
		$date_epoch = TTDate::getBeginDayEpoch( $hire_date );
		$date_stamp = TTDate::getDate('DATE', $date_epoch );
		$dd->createPunchPair( 	$this->user_id,
								 strtotime($date_stamp.' 8:00AM'),
								 strtotime($date_stamp.' 2:00PM'),
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
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );
		$this->assertEquals( $accrual_balance, (6 * 3600) );

		$dd->createPunchPair( 	$this->user_id,
								 strtotime($date_stamp.' 2:00PM'),
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
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );
		$this->assertEquals( $accrual_balance, (10 * 3600) );


		$this->calcAccrualTime( $this->company_id, $accrual_policy_id, $current_epoch, strtotime('+1 months', $current_epoch), 10 );
		$accrual_balance = $this->getCurrentAccrualBalance( $this->user_id, $accrual_policy_account_id );
		$this->assertEquals( $accrual_balance, (10 * 3600) );
	}

	/**
	 * @group AccrualPolicy_testInApplyFrequencyWindowB
	 */
	function testInApplyFrequencyWindowB() {
		global $config_vars;
		//Test InApplyFrequency when the system timezone is MST5MDT, but some users are EST5EDT.
		$user_obj = $this->getUserObject( $this->user_id );

		$ap_obj = TTnew('AccrualPolicyFactory'); /** @var AccrualPolicyFactory $ap_obj */
		$ap_obj->setType( 20 );
		$ap_obj->setApplyFrequency( 10 ); //Each Pay Period
		$ap_obj->setMilestoneRolloverHireDate( TRUE );
		$ap_obj->setMinimumEmployedDays( 0 );


		$offset = 79200;

		$current_epoch = strtotime('14-Aug-2016 1:30AM MDT');
		TTDate::setTimeZone('MST7MDT', TRUE); //Due to being a singleton and PHPUnit resetting the state, always force the timezone to be set.
		$config_vars['other']['system_timezone'] = TTDate::getTimeZone();

		$pay_period_dates = array('start_date' => strtotime('31-Jul-2016 12:00AM'), 'end_date' => strtotime('13-Aug-2016 11:59:59PM') );

		$this->assertEquals( $ap_obj->inApplyFrequencyWindow( $current_epoch, $offset, $pay_period_dates, $user_obj ), TRUE );


		TTDate::setTimeZone('EST5EDT', TRUE); //Due to being a singleton and PHPUnit resetting the state, always force the timezone to be set.
		$pay_period_dates = array('start_date' => strtotime('31-Jul-2016 12:00AM'), 'end_date' => strtotime('13-Aug-2016 11:59:59PM') );

		$this->assertEquals( $ap_obj->inApplyFrequencyWindow( $current_epoch, $offset, $pay_period_dates, $user_obj ), TRUE );
		$this->assertEquals( $ap_obj->inApplyFrequencyWindow( strtotime('12-Aug-2016 11:30PM EDT'), $offset, $pay_period_dates, $user_obj ), FALSE );
		$this->assertEquals( $ap_obj->inApplyFrequencyWindow( strtotime('13-Aug-2016 9:30PM EDT'), $offset, $pay_period_dates, $user_obj ), FALSE );
		$this->assertEquals( $ap_obj->inApplyFrequencyWindow( strtotime('13-Aug-2016 9:59:58PM EDT'), $offset, $pay_period_dates, $user_obj ), FALSE );
		$this->assertEquals( $ap_obj->inApplyFrequencyWindow( strtotime('13-Aug-2016 9:59:59PM EDT'), $offset, $pay_period_dates, $user_obj ), TRUE );
		$this->assertEquals( $ap_obj->inApplyFrequencyWindow( strtotime('13-Aug-2016 10:00PM EDT'), $offset, $pay_period_dates, $user_obj ), TRUE );
		$this->assertEquals( $ap_obj->inApplyFrequencyWindow( strtotime('13-Aug-2016 10:01PM EDT'), $offset, $pay_period_dates, $user_obj ), TRUE );
		$this->assertEquals( $ap_obj->inApplyFrequencyWindow( strtotime('13-Aug-2016 10:30PM EDT'), $offset, $pay_period_dates, $user_obj ), TRUE );
		$this->assertEquals( $ap_obj->inApplyFrequencyWindow( strtotime('13-Aug-2016 11:30PM EDT'), $offset, $pay_period_dates, $user_obj ), TRUE );
		$this->assertEquals( $ap_obj->inApplyFrequencyWindow( strtotime('13-Aug-2016 11:59PM EDT'), $offset, $pay_period_dates, $user_obj ), TRUE );
		$this->assertEquals( $ap_obj->inApplyFrequencyWindow( strtotime('14-Aug-2016 12:00AM EDT'), $offset, $pay_period_dates, $user_obj ), TRUE );
		$this->assertEquals( $ap_obj->inApplyFrequencyWindow( strtotime('14-Aug-2016 12:01AM EDT'), $offset, $pay_period_dates, $user_obj ), TRUE );
		$this->assertEquals( $ap_obj->inApplyFrequencyWindow( strtotime('14-Aug-2016 7:59PM EDT'), $offset, $pay_period_dates, $user_obj ), TRUE );
		$this->assertEquals( $ap_obj->inApplyFrequencyWindow( strtotime('14-Aug-2016 8:00PM EDT'), $offset, $pay_period_dates, $user_obj ), FALSE );
		$this->assertEquals( $ap_obj->inApplyFrequencyWindow( strtotime('14-Aug-2016 8:01PM EDT'), $offset, $pay_period_dates, $user_obj ), FALSE );
		$this->assertEquals( $ap_obj->inApplyFrequencyWindow( strtotime('14-Aug-2016 11:30PM EDT'), $offset, $pay_period_dates, $user_obj ), FALSE );
		$this->assertEquals( $ap_obj->inApplyFrequencyWindow( strtotime('15-Aug-2016 11:30PM EDT'), $offset, $pay_period_dates, $user_obj ), FALSE );
	}
}
?>