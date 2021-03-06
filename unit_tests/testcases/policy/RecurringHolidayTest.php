<?php

class RecurringHolidayTest extends PHPUnit\Framework\TestCase {
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

		$this->assertGreaterThan( 0, $this->company_id );
		$this->assertGreaterThan( 0, $this->user_id );
	}

	public function tearDown(): void {
		Debug::text('Running tearDown(): ', __FILE__, __LINE__, __METHOD__, 10);

		//$this->deleteAllSchedules();
	}

	function getUserObject( $user_id ) {
		$ulf = TTNew( 'UserListFactory' ); /** @var UserListFactory $ulf */
		$ulf->getById( $user_id );
		if ( $ulf->getRecordCount() > 0 ) {
			return $ulf->getCurrent();
		}

		return FALSE;
	}

	/**
	 * @group RecurringHoliday_testRecurringHolidayDatesA
	 */
	function testRecurringHolidayDatesA() {
		//First Monday in August
		$rhf = TTNew('RecurringHolidayFactory'); /** @var RecurringHolidayFactory $rhf */
		$rhf->setCompany( $this->company_id );
		$rhf->setName('BC - British Columbia Day');
		$rhf->setType( 20 );
		$rhf->setWeekInterval( 1 );
		$rhf->setDayOfWeek( 1 );
		$rhf->setMonth( 8 );
		$rhf->setAlwaysOnWeekDay( 3 ); //Closest

		$next_date = $rhf->getNextDate( strtotime('15-Aug-2015') );
		//Debug::text('Next Date: '. TTDate::getDate('DATE+TIME', $next_date ), __FILE__, __LINE__, __METHOD__, 10);
		$this->assertEquals( $next_date, strtotime('01-Aug-2016 12:00PM PDT') );

		$next_date = $rhf->getNextDate( strtotime('15-Aug-2014') );
		//Debug::text('Next Date: '. TTDate::getDate('DATE+TIME', $next_date ), __FILE__, __LINE__, __METHOD__, 10);
		$this->assertEquals( $next_date, strtotime('03-Aug-2015 12:00PM PDT') );

		$next_date = $rhf->getNextDate( strtotime('15-Aug-2013') );
		//Debug::text('Next Date: '. TTDate::getDate('DATE+TIME', $next_date ), __FILE__, __LINE__, __METHOD__, 10);
		$this->assertEquals( $next_date, strtotime('04-Aug-2014 12:00PM PDT') );

		$next_date = $rhf->getNextDate( strtotime('15-Aug-2012') );
		//Debug::text('Next Date: '. TTDate::getDate('DATE+TIME', $next_date ), __FILE__, __LINE__, __METHOD__, 10);
		$this->assertEquals( $next_date, strtotime('05-Aug-2013 12:00PM PDT') );

		$next_date = $rhf->getNextDate( strtotime('15-Aug-2011') );
		//Debug::text('Next Date: '. TTDate::getDate('DATE+TIME', $next_date ), __FILE__, __LINE__, __METHOD__, 10);
		$this->assertEquals( $next_date, strtotime('06-Aug-2012 12:00PM PDT') );

		$next_date = $rhf->getNextDate( strtotime('15-Aug-2010') );
		//Debug::text('Next Date: '. TTDate::getDate('DATE+TIME', $next_date ), __FILE__, __LINE__, __METHOD__, 10);
		$this->assertEquals( $next_date, strtotime('01-Aug-2011 12:00PM PDT') );
	}

	/**
	 * @group RecurringHoliday_testRecurringHolidayDatesB
	 */
	function testRecurringHolidayDatesB() {
		//First Monday in August
		$rhf = TTNew('RecurringHolidayFactory'); /** @var RecurringHolidayFactory $rhf */
		$rhf->setCompany( $this->company_id );
		$rhf->setName('BC - Family');
		$rhf->setType( 20 );
		$rhf->setWeekInterval( 2 );
		$rhf->setDayOfWeek( 1 );
		$rhf->setMonth( 2 );
		$rhf->setAlwaysOnWeekDay( 3 ); //Closest

		$next_date = $rhf->getNextDate( strtotime('01-Mar-2015') );
		//Debug::text('Next Date: '. TTDate::getDate('DATE+TIME', $next_date ), __FILE__, __LINE__, __METHOD__, 10);
		$this->assertEquals( $next_date, strtotime('08-Feb-2016 12:00PM PST') );

		$next_date = $rhf->getNextDate( strtotime('01-Mar-2014') );
		Debug::text('Next Date: '. TTDate::getDate('DATE+TIME', $next_date ), __FILE__, __LINE__, __METHOD__, 10);
		$this->assertEquals( $next_date, strtotime('09-Feb-2015 12:00PM PST') );

		$next_date = $rhf->getNextDate( strtotime('01-Mar-2013') );
		//Debug::text('Next Date: '. TTDate::getDate('DATE+TIME', $next_date ), __FILE__, __LINE__, __METHOD__, 10);
		$this->assertEquals( $next_date, strtotime('10-Feb-2014 12:00PM PST') );

		$next_date = $rhf->getNextDate( strtotime('01-Mar-2012') );
		//Debug::text('Next Date: '. TTDate::getDate('DATE+TIME', $next_date ), __FILE__, __LINE__, __METHOD__, 10);
		$this->assertEquals( $next_date, strtotime('11-Feb-2013 12:00PM PST') );

		$next_date = $rhf->getNextDate( strtotime('01-Mar-2011') );
		//Debug::text('Next Date: '. TTDate::getDate('DATE+TIME', $next_date ), __FILE__, __LINE__, __METHOD__, 10);
		$this->assertEquals( $next_date, strtotime('13-Feb-2012 12:00PM PST') );

		$next_date = $rhf->getNextDate( strtotime('01-Mar-2010') );
		//Debug::text('Next Date: '. TTDate::getDate('DATE+TIME', $next_date ), __FILE__, __LINE__, __METHOD__, 10);
		$this->assertEquals( $next_date, strtotime('14-Feb-2011 12:00PM PST') );
	}

	/**
	 * @group RecurringHoliday_testRecurringHolidayDatesC
	 */
	function testRecurringHolidayDatesC() {
		//First Monday in August
		$rhf = TTNew('RecurringHolidayFactory'); /** @var RecurringHolidayFactory $rhf */
		$rhf->setCompany( $this->company_id );
		$rhf->setName('US - Thanksgiving');
		$rhf->setType( 20 );
		$rhf->setWeekInterval( 4 );
		$rhf->setDayOfWeek( 4 );
		$rhf->setMonth( 11 );
		$rhf->setAlwaysOnWeekDay( 0 );

		$next_date = $rhf->getNextDate( strtotime('28-Nov-2013') );
		Debug::text('Next Date: '. TTDate::getDate('DATE+TIME', $next_date ), __FILE__, __LINE__, __METHOD__, 10);
		$this->assertEquals( $next_date, strtotime('28-Nov-2013 12:00PM PST') );

		$start_date = strtotime('29-Nov-2013 12:00PM');
		$end_date = strtotime('27-Nov-2014 12:00PM');
		$n = 0;
		for( $i = $start_date; $i < $end_date; $i += 86400 ) {
			$next_date = $rhf->getNextDate( $i );
			Debug::text('N: '. $n .' Next Date: '. TTDate::getDate('DATE+TIME', $next_date ), __FILE__, __LINE__, __METHOD__, 10);
			$this->assertEquals( $next_date, strtotime('27-Nov-2014 12:00PM PST') );
			$n++;
		}

		$start_date = strtotime('28-Nov-2014 12:00PM');
		$end_date = strtotime('26-Nov-2015 12:00PM');
		$n = 0;
		for( $i = $start_date; $i < $end_date; $i += 86400 ) {
			$next_date = $rhf->getNextDate( $i );
			Debug::text('N: '. $n .' Next Date: '. TTDate::getDate('DATE+TIME', $next_date ), __FILE__, __LINE__, __METHOD__, 10);
			$this->assertEquals( $next_date, strtotime('26-Nov-2015 12:00PM PST') );
			$n++;
		}

		$next_date = $rhf->getNextDate( strtotime('27-Nov-2015') );
		Debug::text('Next Date: '. TTDate::getDate('DATE+TIME', $next_date ), __FILE__, __LINE__, __METHOD__, 10);
		$this->assertEquals( $next_date, strtotime('24-Nov-2016 12:00PM PST') );

		$next_date = $rhf->getNextDate( strtotime('28-Nov-2015') );
		Debug::text('Next Date: '. TTDate::getDate('DATE+TIME', $next_date ), __FILE__, __LINE__, __METHOD__, 10);
		$this->assertEquals( $next_date, strtotime('24-Nov-2016 12:00PM PST') );

		$next_date = $rhf->getNextDate( strtotime('29-Nov-2015') );
		Debug::text('Next Date: '. TTDate::getDate('DATE+TIME', $next_date ), __FILE__, __LINE__, __METHOD__, 10);
		$this->assertEquals( $next_date, strtotime('24-Nov-2016 12:00PM PST') );

		$next_date = $rhf->getNextDate( strtotime('30-Nov-2015') );
		Debug::text('Next Date: '. TTDate::getDate('DATE+TIME', $next_date ), __FILE__, __LINE__, __METHOD__, 10);
		$this->assertEquals( $next_date, strtotime('24-Nov-2016 12:00PM PST') );

		$next_date = $rhf->getNextDate( strtotime('01-Dec-2015') );
		Debug::text('Next Date: '. TTDate::getDate('DATE+TIME', $next_date ), __FILE__, __LINE__, __METHOD__, 10);
		$this->assertEquals( $next_date, strtotime('24-Nov-2016 12:00PM PST') );
	}
}
?>