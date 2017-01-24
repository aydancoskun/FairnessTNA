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
 * @group Schedule
 */
class ScheduleTest extends PHPUnit_Framework_TestCase
{
    protected $company_id = null;
    protected $user_id = null;
    protected $pay_period_schedule_id = null;
    protected $pay_period_objs = null;
    protected $pay_stub_account_link_arr = null;

    public function setUp()
    {
        global $dd;
        Debug::text('Running setUp(): ', __FILE__, __LINE__, __METHOD__, 10);

        TTDate::setTimeZone('PST8PDT', true); //Due to being a singleton and PHPUnit resetting the state, always force the timezone to be set.

        $dd = new DemoData();
        $dd->setEnableQuickPunch(false); //Helps prevent duplicate punch IDs and validation failures.
        $dd->setUserNamePostFix('_' . uniqid(null, true)); //Needs to be super random to prevent conflicts and random failing tests.
        $this->company_id = $dd->createCompany();
        Debug::text('Company ID: ' . $this->company_id, __FILE__, __LINE__, __METHOD__, 10);

        //$dd->createPermissionGroups( $this->company_id, 40 ); //Administrator only.

        $dd->createCurrency($this->company_id, 10);

        $this->branch_id = $dd->createBranch($this->company_id, 10); //NY
        $this->department_id = $dd->createDepartment($this->company_id, 10);


        $dd->createPayStubAccount($this->company_id);
        $this->createPayStubAccounts();
        //$this->createPayStubAccrualAccount();
        $dd->createPayStubAccountLink($this->company_id);
        $this->getPayStubAccountLinkArray();

        $dd->createUserWageGroups($this->company_id);

        $this->user_id = $dd->createUser($this->company_id, 100);
        $this->user_id2 = $dd->createUser($this->company_id, 10);

        $this->policy_ids['accrual_policy_account'][20] = $dd->createAccrualPolicyAccount($this->company_id, 20); //Vacation
        $this->policy_ids['accrual_policy_account'][30] = $dd->createAccrualPolicyAccount($this->company_id, 30); //Sick

        $this->policy_ids['pay_formula_policy'][100] = $dd->createPayFormulaPolicy($this->company_id, 100); //Reg 1.0x
        $this->policy_ids['pay_formula_policy'][120] = $dd->createPayFormulaPolicy($this->company_id, 120, $this->policy_ids['accrual_policy_account'][20]); //Vacation
        $this->policy_ids['pay_formula_policy'][130] = $dd->createPayFormulaPolicy($this->company_id, 130, $this->policy_ids['accrual_policy_account'][30]); //Sick

        $this->policy_ids['pay_code'][100] = $dd->createPayCode($this->company_id, 100, $this->policy_ids['pay_formula_policy'][100]); //Regular
        $this->policy_ids['pay_code'][900] = $dd->createPayCode($this->company_id, 900, $this->policy_ids['pay_formula_policy'][120]); //Vacation
        $this->policy_ids['pay_code'][910] = $dd->createPayCode($this->company_id, 910, $this->policy_ids['pay_formula_policy'][130]); //Sick

        $this->policy_ids['contributing_pay_code_policy'][10] = $dd->createContributingPayCodePolicy($this->company_id, 10, array($this->policy_ids['pay_code'][100])); //Regular
        $this->policy_ids['contributing_shift_policy'][10] = $dd->createContributingShiftPolicy($this->company_id, 10, $this->policy_ids['contributing_pay_code_policy'][10]); //Regular

        $this->policy_ids['regular'][10] = $dd->createRegularTimePolicy($this->company_id, 10, $this->policy_ids['contributing_shift_policy'][10], $this->policy_ids['pay_code'][100]);

        $this->policy_ids['absence_policy'][10] = $dd->createAbsencePolicy($this->company_id, 10, $this->policy_ids['pay_code'][900]); //Vacation
        $this->policy_ids['absence_policy'][30] = $dd->createAbsencePolicy($this->company_id, 30, $this->policy_ids['pay_code'][910]); //Sick

        $this->assertGreaterThan(0, $this->company_id);
        $this->assertGreaterThan(0, $this->user_id);

        return true;
    }

    public function createPayStubAccounts()
    {
        Debug::text('Saving.... Employee Deduction - Other', __FILE__, __LINE__, __METHOD__, 10);
        $pseaf = new PayStubEntryAccountFactory();
        $pseaf->setCompany($this->company_id);
        $pseaf->setStatus(10);
        $pseaf->setType(20);
        $pseaf->setName('Other');
        $pseaf->setOrder(290);

        if ($pseaf->isValid()) {
            $pseaf->Save();
        }

        Debug::text('Saving.... Employee Deduction - Other2', __FILE__, __LINE__, __METHOD__, 10);
        $pseaf = new PayStubEntryAccountFactory();
        $pseaf->setCompany($this->company_id);
        $pseaf->setStatus(10);
        $pseaf->setType(20);
        $pseaf->setName('Other2');
        $pseaf->setOrder(291);

        if ($pseaf->isValid()) {
            $pseaf->Save();
        }

        Debug::text('Saving.... Employee Deduction - EI', __FILE__, __LINE__, __METHOD__, 10);
        $pseaf = new PayStubEntryAccountFactory();
        $pseaf->setCompany($this->company_id);
        $pseaf->setStatus(10);
        $pseaf->setType(20);
        $pseaf->setName('EI');
        $pseaf->setOrder(292);

        if ($pseaf->isValid()) {
            $pseaf->Save();
        }

        Debug::text('Saving.... Employee Deduction - CPP', __FILE__, __LINE__, __METHOD__, 10);
        $pseaf = new PayStubEntryAccountFactory();
        $pseaf->setCompany($this->company_id);
        $pseaf->setStatus(10);
        $pseaf->setType(20);
        $pseaf->setName('CPP');
        $pseaf->setOrder(293);

        if ($pseaf->isValid()) {
            $pseaf->Save();
        }

        //Link Account EI and CPP accounts
        $pseallf = new PayStubEntryAccountLinkListFactory();
        $pseallf->getByCompanyId($this->company_id);
        if ($pseallf->getRecordCount() > 0) {
            $pseal_obj = $pseallf->getCurrent();
            $pseal_obj->setEmployeeEI(CompanyDeductionFactory::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName($this->company_id, 20, 'EI'));
            $pseal_obj->setEmployeeCPP(CompanyDeductionFactory::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName($this->company_id, 20, 'CPP'));
            $pseal_obj->Save();
        }


        return true;
    }

    public function getPayStubAccountLinkArray()
    {
        $this->pay_stub_account_link_arr = array(
            'total_gross' => CompanyDeductionFactory::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName($this->company_id, 40, 'Total Gross'),
            'total_deductions' => CompanyDeductionFactory::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName($this->company_id, 40, 'Total Deductions'),
            'employer_contribution' => CompanyDeductionFactory::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName($this->company_id, 40, 'Employer Total Contributions'),
            'net_pay' => CompanyDeductionFactory::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName($this->company_id, 40, 'Net Pay'),
            'regular_time' => CompanyDeductionFactory::getPayStubEntryAccountByCompanyIDAndTypeAndFuzzyName($this->company_id, 10, 'Regular Time'),
        );

        return true;
    }

    public function tearDown()
    {
        Debug::text('Running tearDown(): ', __FILE__, __LINE__, __METHOD__, 10);

        //$this->deleteAllSchedules();

        return true;
    }

    public function testScheduleA()
    {
        global $dd;

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods();
        $this->getAllPayPeriods();


        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);


        $meal_policy_id = $this->createMealPolicy(10); //60min autodeduct
        $schedule_policy_id = $this->createSchedulePolicy($meal_policy_id);
        $schedule_id = $this->createSchedule($this->user_id, $date_epoch, array(
            'schedule_policy_id' => $schedule_policy_id,
            'start_time' => ' 8:00AM',
            'end_time' => '5:00PM',
        ));

        $slf = TTNew('ScheduleListFactory');
        $slf->getByID($schedule_id);
        if ($slf->getRecordCount() == 1) {
            $s_obj = $slf->getCurrent();
            $this->assertEquals($date_stamp, TTDate::getDate('DATE', $s_obj->getStartTime()));
            $this->assertEquals($date_stamp, TTDate::getDate('DATE', $s_obj->getEndTime()));
            $this->assertEquals((8 * 3600), $s_obj->getTotalTime());
        } else {
            $this->assertEquals(true, false);
        }

        return true;
    }

    public function createPayPeriodSchedule($shift_assigned_day = 10)
    {
        $ppsf = new PayPeriodScheduleFactory();

        $ppsf->setCompany($this->company_id);
        //$ppsf->setName( 'Bi-Weekly'.rand(1000,9999) );
        $ppsf->setName('Bi-Weekly');
        $ppsf->setDescription('Pay every two weeks');
        $ppsf->setType(20);
        $ppsf->setStartWeekDay(0);


        $anchor_date = TTDate::getBeginWeekEpoch((TTDate::getBeginYearEpoch(time()) - (86400 * (7 * 6)))); //Start 6 weeks ago

        $ppsf->setAnchorDate($anchor_date);

        $ppsf->setStartDayOfWeek(TTDate::getDayOfWeek($anchor_date));
        $ppsf->setTransactionDate(7);

        $ppsf->setTransactionDateBusinessDay(true);
        $ppsf->setTimeZone('PST8PDT');

        $ppsf->setDayStartTime(0);
        $ppsf->setNewDayTriggerTime((4 * 3600));
        $ppsf->setMaximumShiftTime((16 * 3600));
        $ppsf->setShiftAssignedDay($shift_assigned_day);

        $ppsf->setEnableInitialPayPeriods(false);
        if ($ppsf->isValid()) {
            $insert_id = $ppsf->Save(false);
            Debug::Text('Pay Period Schedule ID: ' . $insert_id, __FILE__, __LINE__, __METHOD__, 10);

            $ppsf->setUser(array($this->user_id));
            $ppsf->Save();

            $this->pay_period_schedule_id = $insert_id;

            return $insert_id;
        }

        Debug::Text('Failed Creating Pay Period Schedule!', __FILE__, __LINE__, __METHOD__, 10);

        return false;
    }

    public function createPayPeriods($initial_date = false)
    {
        $max_pay_periods = 35;

        $ppslf = new PayPeriodScheduleListFactory();
        $ppslf->getById($this->pay_period_schedule_id);
        if ($ppslf->getRecordCount() > 0) {
            $pps_obj = $ppslf->getCurrent();

            for ($i = 0; $i < $max_pay_periods; $i++) {
                if ($i == 0) {
                    if ($initial_date !== false) {
                        $end_date = $initial_date;
                    } else {
                        //$end_date = TTDate::getBeginYearEpoch( strtotime('01-Jan-07') );
                        $end_date = TTDate::getBeginWeekEpoch((TTDate::getBeginYearEpoch(time()) - (86400 * (7 * 6))));
                    }
                } else {
                    $end_date = ($end_date + ((86400 * 14)));
                }

                Debug::Text('I: ' . $i . ' End Date: ' . TTDate::getDate('DATE+TIME', $end_date), __FILE__, __LINE__, __METHOD__, 10);

                $pps_obj->createNextPayPeriod($end_date, (86400 * 3600), false); //Don't import punches, as that causes deadlocks when running tests in parallel.
            }
        }

        return true;
    }

    public function getAllPayPeriods()
    {
        $pplf = new PayPeriodListFactory();
        //$pplf->getByCompanyId( $this->company_id );
        $pplf->getByPayPeriodScheduleId($this->pay_period_schedule_id);
        if ($pplf->getRecordCount() > 0) {
            foreach ($pplf as $pp_obj) {
                Debug::text('Pay Period... Start: ' . TTDate::getDate('DATE+TIME', $pp_obj->getStartDate()) . ' End: ' . TTDate::getDate('DATE+TIME', $pp_obj->getEndDate()), __FILE__, __LINE__, __METHOD__, 10);

                $this->pay_period_objs[] = $pp_obj;
            }
        }

        $this->pay_period_objs = array_reverse($this->pay_period_objs);

        return true;
    }

    public function createMealPolicy($type_id)
    {
        $mpf = TTnew('MealPolicyFactory');

        $mpf->setCompany($this->company_id);

        switch ($type_id) {
            case 10: //60min auto-deduct.
                $mpf->setName('60min (AutoDeduct)');
                $mpf->setType(10); //AutoDeduct
                $mpf->setTriggerTime((3600 * 5));
                $mpf->setAmount(3600);
                $mpf->setStartWindow((3600 * 4));
                $mpf->setWindowLength((3600 * 2));
                break;
        }

        $mpf->setPayCode($this->policy_ids['pay_code'][100]);

        if ($mpf->isValid()) {
            $insert_id = $mpf->Save();
            Debug::Text('Meal Policy ID: ' . $insert_id, __FILE__, __LINE__, __METHOD__, 10);

            return $insert_id;
        }

        Debug::Text('Failed Creating Meal Policy!', __FILE__, __LINE__, __METHOD__, 10);

        return false;
    }

    public function createSchedulePolicy($meal_policy_id, $full_shift_absence_policy_id = 0, $partial_shift_absence_policy_id = 0)
    {
        $spf = TTnew('SchedulePolicyFactory');

        $spf->setCompany($this->company_id);
        $spf->setName('Schedule Policy');
        $spf->setFullShiftAbsencePolicyID($full_shift_absence_policy_id);
        $spf->setPartialShiftAbsencePolicyID($partial_shift_absence_policy_id);
        $spf->setStartStopWindow((3600 * 2));

        if ($spf->isValid()) {
            $insert_id = $spf->Save(false);

            $spf->setMealPolicy($meal_policy_id);
            Debug::Text('Schedule Policy ID: ' . $insert_id, __FILE__, __LINE__, __METHOD__, 10);

            return $insert_id;
        }

        Debug::Text('Failed Creating Schedule Policy!', __FILE__, __LINE__, __METHOD__, 10);

        return false;
    }

    public function createSchedule($user_id, $date_stamp, $data = null)
    {
        $sf = TTnew('ScheduleFactory');
        $sf->setCompany($this->company_id);
        $sf->setUser($user_id);
        //$sf->setUserDateId( UserDateFactory::findOrInsertUserDate( $user_id, $date_stamp) );

        if (isset($data['replaced_id'])) {
            $sf->setReplacedId($data['replaced_id']);
        }

        if (isset($data['status_id'])) {
            $sf->setStatus($data['status_id']);
        } else {
            $sf->setStatus(10);
        }

        if (isset($data['schedule_policy_id'])) {
            $sf->setSchedulePolicyID($data['schedule_policy_id']);
        }

        if (isset($data['absence_policy_id'])) {
            $sf->setAbsencePolicyID($data['absence_policy_id']);
        }
        if (isset($data['branch_id'])) {
            $sf->setBranch($data['branch_id']);
        }
        if (isset($data['department_id'])) {
            $sf->setDepartment($data['department_id']);
        }

        if (isset($data['job_id'])) {
            $sf->setJob($data['job_id']);
        }

        if (isset($data['job_item_id'])) {
            $sf->setJobItem($data['job_item_id']);
        }

        if ($data['start_time'] != '') {
            $start_time = strtotime($data['start_time'], $date_stamp);
        }
        if ($data['end_time'] != '') {
            Debug::Text('End Time: ' . $data['end_time'] . ' Date Stamp: ' . $date_stamp, __FILE__, __LINE__, __METHOD__, 10);
            $end_time = strtotime($data['end_time'], $date_stamp);
            Debug::Text('bEnd Time: ' . $data['end_time'] . ' - ' . TTDate::getDate('DATE+TIME', $data['end_time']), __FILE__, __LINE__, __METHOD__, 10);
        }

        $sf->setStartTime($start_time);
        $sf->setEndTime($end_time);

        if ($sf->isValid()) {
            $sf->setEnableReCalculateDay(true);
            $insert_id = $sf->Save();
            Debug::Text('Schedule ID: ' . $insert_id, __FILE__, __LINE__, __METHOD__, 10);

            return $insert_id;
        }

        Debug::Text('Failed Creating Schedule!', __FILE__, __LINE__, __METHOD__, 10);

        return false;
    }

    public function testScheduleB()
    {
        global $dd;

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $date_epoch = TTDate::getBeginWeekEpoch(time()); //Use current year
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $date_epoch2 = (TTDate::getBeginWeekEpoch(time()) + (86400 * 1.5)); //Use current year, handle DST.
        $date_stamp2 = TTDate::getDate('DATE', $date_epoch2);


        $meal_policy_id = $this->createMealPolicy(10); //60min autodeduct
        $schedule_policy_id = $this->createSchedulePolicy($meal_policy_id);
        $schedule_id = $this->createSchedule($this->user_id, $date_epoch, array(
            'schedule_policy_id' => $schedule_policy_id,
            'start_time' => ' 11:00PM',
            'end_time' => '8:00AM',
        ));

        $slf = TTNew('ScheduleListFactory');
        $slf->getByID($schedule_id);
        if ($slf->getRecordCount() == 1) {
            $s_obj = $slf->getCurrent();
            $this->assertEquals($date_stamp, TTDate::getDate('DATE', $s_obj->getStartTime()));
            $this->assertEquals($date_stamp2, TTDate::getDate('DATE', $s_obj->getEndTime()));
            $this->assertEquals((8 * 3600), $s_obj->getTotalTime());
        } else {
            $this->assertEquals(true, false);
        }

        return true;
    }

    public function testScheduleDSTFall()
    {
        global $dd;

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods(strtotime('01-Jan-2013'));
        $this->getAllPayPeriods();

        $date_epoch = strtotime('02-Nov-2013'); //Use current year
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $date_epoch2 = strtotime('03-Nov-2013'); //Use current year
        $date_stamp2 = TTDate::getDate('DATE', $date_epoch2);

        $schedule_id = $this->createSchedule($this->user_id, $date_epoch, array(
            'schedule_policy_id' => 0,
            'start_time' => ' 11:00PM',
            'end_time' => '7:00AM',
        ));

        $slf = TTNew('ScheduleListFactory');
        $slf->getByID($schedule_id);
        if ($slf->getRecordCount() == 1) {
            $s_obj = $slf->getCurrent();
            $this->assertEquals($date_stamp, TTDate::getDate('DATE', $s_obj->getStartTime()));
            $this->assertEquals($date_stamp2, TTDate::getDate('DATE', $s_obj->getEndTime()));
            $this->assertEquals((9 * 3600), $s_obj->getTotalTime());
        } else {
            $this->assertEquals(true, false);
        }

        return true;
    }

    /*
     Tests:
        - Spanning midnight
        - Spanning DST.

    */

    public function testScheduleDSTFallB()
    {
        global $dd;

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods(strtotime('01-Jan-2013'));
        $this->getAllPayPeriods();

        $date_epoch = strtotime('05-Nov-2016'); //Use current year
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $date_epoch2 = strtotime('05-Nov-2016'); //Use current year
        $date_stamp2 = TTDate::getDate('DATE', $date_epoch2);

        TTDate::setTimeFormat('g:i A T');
        $schedule_id = $this->createSchedule($this->user_id, $date_epoch, array(
            'schedule_policy_id' => 0,
            //'start_time' => '6:00PM PDT', //These will fail due to parsing PDT/PST -- ALSO SEE: test_DST() regarding the "quirk" about PST date parsing.
            //'end_time' => '6:00AM PST', //These will fail due to parsing PDT/PST -- ALSO SEE: test_DST() regarding the "quirk" about PST date parsing.
            'start_time' => '6:00PM America/Vancouver',
            'end_time' => '6:00AM America/Vancouver',
        ));
        TTDate::setTimeFormat('g:i A');

        $slf = TTNew('ScheduleListFactory');
        $slf->getByID($schedule_id);
        if ($slf->getRecordCount() == 1) {
            $s_obj = $slf->getCurrent();
            $this->assertEquals('05-Nov-16', TTDate::getDate('DATE', $s_obj->getStartTime()));
            $this->assertEquals('06-Nov-16', TTDate::getDate('DATE', $s_obj->getEndTime()));
            $this->assertEquals((13 * 3600), $s_obj->getTotalTime()); //6PM -> 6AM = 12hrs, plus 1hr DST.
        } else {
            $this->assertEquals(true, false);
        }

        return true;
    }

    public function testScheduleDSTSpring()
    {
        global $dd;

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods(strtotime('01-Jan-2013'));
        $this->getAllPayPeriods();

        $date_epoch = strtotime('09-Mar-2013'); //Use current year
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $date_epoch2 = strtotime('10-Mar-2013'); //Use current year
        $date_stamp2 = TTDate::getDate('DATE', $date_epoch2);

        $schedule_id = $this->createSchedule($this->user_id, $date_epoch, array(
            'schedule_policy_id' => 0,
            'start_time' => ' 11:00PM',
            'end_time' => '7:00AM',
        ));

        $slf = TTNew('ScheduleListFactory');
        $slf->getByID($schedule_id);
        if ($slf->getRecordCount() == 1) {
            $s_obj = $slf->getCurrent();
            $this->assertEquals($date_stamp, TTDate::getDate('DATE', $s_obj->getStartTime()));
            $this->assertEquals($date_stamp2, TTDate::getDate('DATE', $s_obj->getEndTime()));
            $this->assertEquals((7 * 3600), $s_obj->getTotalTime());
        } else {
            $this->assertEquals(true, false);
        }

        return true;
    }

    //DST time should be recorded based on the time the employee actually works, therefore one hour more on this day.

    public function testScheduleUnderTimePolicyA()
    {
        global $dd;

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods();
        $this->getAllPayPeriods();


        //Create Policy Group
        $dd->createPolicyGroup($this->company_id,
            null, //Meal
            null, //Exception
            null, //Holiday
            null, //OT
            null, //Premium
            null, //Round
            array($this->user_id), //Users
            null, //Break
            null, //Accrual
            null, //Expense
            array($this->policy_ids['absence_policy'][10], $this->policy_ids['absence_policy'][30]), //Absence
            array($this->policy_ids['regular'][10]) //Regular
        );

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $meal_policy_id = $this->createMealPolicy(10); //60min autodeduct
        $schedule_policy_id = $this->createSchedulePolicy(array(0), 0, $this->policy_ids['absence_policy'][10]); //Partial Shift Only
        $schedule_id = $this->createSchedule($this->user_id, $date_epoch, array(
            'schedule_policy_id' => $schedule_policy_id,
            'start_time' => ' 8:00AM',
            'end_time' => '4:00PM',
        ));

        $slf = TTNew('ScheduleListFactory');
        $slf->getByID($schedule_id);
        if ($slf->getRecordCount() == 1) {
            $s_obj = $slf->getCurrent();
            $this->assertEquals($date_stamp, TTDate::getDate('DATE', $s_obj->getStartTime()));
            $this->assertEquals($date_stamp, TTDate::getDate('DATE', $s_obj->getEndTime()));
            $this->assertEquals((8 * 3600), $s_obj->getTotalTime());
        } else {
            $this->assertEquals(true, false);
        }


        //Create punches to trigger undertime on same day.
        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 8:00AM'),
            strtotime($date_stamp . ' 3:00PM'),
            array(
                'in_type_id' => 10,
                'out_type_id' => 10,
                'branch_id' => 0,
                'department_id' => 0,
                'job_id' => 0,
                'job_item_id' => 0,
            ),
            true
        );

        $punch_arr = $this->getPunchDataArray(TTDate::getBeginDayEpoch($date_epoch), TTDate::getEndDayEpoch($date_epoch));
        //print_r($punch_arr);
        $this->assertEquals(1, count($punch_arr[$date_epoch]));
        $this->assertEquals($date_epoch, $punch_arr[$date_epoch][0]['date_stamp']);

        $udt_arr = $this->getUserDateTotalArray($date_epoch, $date_epoch);
        //var_dump( $udt_arr );

        //Total Time
        $this->assertEquals($udt_arr[$date_epoch][0]['object_type_id'], 5); //5=System Total
        $this->assertEquals($udt_arr[$date_epoch][0]['pay_code_id'], 0);
        $this->assertEquals($udt_arr[$date_epoch][0]['total_time'], (8 * 3600));
        //Regular Time
        $this->assertEquals($udt_arr[$date_epoch][1]['object_type_id'], 20); //Regular Time
        $this->assertEquals($udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100]); //Regular Time
        $this->assertEquals($udt_arr[$date_epoch][1]['total_time'], (7 * 3600));
        //Absence Time
        $this->assertEquals($udt_arr[$date_epoch][2]['object_type_id'], 25); //Absence
        $this->assertEquals($udt_arr[$date_epoch][2]['pay_code_id'], $this->policy_ids['pay_code'][900]); //Absence
        $this->assertEquals($udt_arr[$date_epoch][2]['total_time'], (1 * 3600));
        //Absence Time
        $this->assertEquals($udt_arr[$date_epoch][3]['object_type_id'], 50); //Absence
        $this->assertEquals($udt_arr[$date_epoch][3]['pay_code_id'], $this->policy_ids['pay_code'][900]); //Absence
        $this->assertEquals($udt_arr[$date_epoch][3]['total_time'], (1 * 3600));

        //Make sure no other hours
        $this->assertEquals(count($udt_arr[$date_epoch]), 4);

        //Check Accrual Balance
        $accrual_balance = $this->getCurrentAccrualBalance($this->user_id, $this->policy_ids['accrual_policy_account'][20]);
        $this->assertEquals($accrual_balance, (1 * -3600));

        //Add a 0.5hr absence of the same type, but because there is already an entry for this,
        //this will take precedance and override the undertime absence.
        //Therefore it shouldn't change the accrual due to the conflict detection.
        $absence_id = $dd->createAbsence($this->user_id, $date_epoch, (0.5 * 3600), $this->policy_ids['absence_policy'][10], true);
        $accrual_balance = $this->getCurrentAccrualBalance($this->user_id, $this->policy_ids['accrual_policy_account'][20]);
        $this->assertEquals($accrual_balance, (0.5 * -3600));

        $dd->deleteAbsence($absence_id);
        $accrual_balance = $this->getCurrentAccrualBalance($this->user_id, $this->policy_ids['accrual_policy_account'][20]);
        $this->assertEquals($accrual_balance, (1.0 * -3600));


        //Add a 1hr absence of the same type, but because there is already an entry for this,
        //this will take precedance and override the undertime absence.
        //Therefore it shouldn't change the accrual due to the conflict detection.
        $absence_id = $dd->createAbsence($this->user_id, $date_epoch, (1 * 3600), $this->policy_ids['absence_policy'][10]);
        $accrual_balance = $this->getCurrentAccrualBalance($this->user_id, $this->policy_ids['accrual_policy_account'][20]);
        $this->assertEquals($accrual_balance, (1 * -3600));

        $dd->deleteAbsence($absence_id);
        $accrual_balance = $this->getCurrentAccrualBalance($this->user_id, $this->policy_ids['accrual_policy_account'][20]);
        $this->assertEquals($accrual_balance, (1 * -3600));


        //Add a 2hr absence of the same type, this should adjust the accrual balance by 2hrs though.
        $absence_id = $dd->createAbsence($this->user_id, $date_epoch, (2 * 3600), $this->policy_ids['absence_policy'][10], true);
        $accrual_balance = $this->getCurrentAccrualBalance($this->user_id, $this->policy_ids['accrual_policy_account'][20]);
        $this->assertEquals($accrual_balance, (2 * -3600));

        $dd->deleteAbsence($absence_id);
        $accrual_balance = $this->getCurrentAccrualBalance($this->user_id, $this->policy_ids['accrual_policy_account'][20]);
        $this->assertEquals($accrual_balance, (1 * -3600));


        //Add a 1hr absence of a *different*  type
        $absence_id = $dd->createAbsence($this->user_id, $date_epoch, (1 * 3600), $this->policy_ids['absence_policy'][30], true);
        $accrual_balance = $this->getCurrentAccrualBalance($this->user_id, $this->policy_ids['accrual_policy_account'][30]);
        $this->assertEquals($accrual_balance, (1 * -3600));

        $dd->deleteAbsence($absence_id);
        $accrual_balance = $this->getCurrentAccrualBalance($this->user_id, $this->policy_ids['accrual_policy_account'][30]);
        $this->assertEquals($accrual_balance, (0 * -3600));

        return true;
    }

    //DST time should be recorded based on the time the employee actually works, therefore one hour more on this day.

    public function getPunchDataArray($start_date, $end_date)
    {
        $plf = new PunchListFactory();

        $plf->getByCompanyIDAndUserIdAndStartDateAndEndDate($this->company_id, $this->user_id, $start_date, $end_date);
        if ($plf->getRecordCount() > 0) {
            //Only return punch_control data for now
            $i = 0;
            $prev_punch_control_id = null;
            foreach ($plf as $p_obj) {
                if ($prev_punch_control_id == null or $prev_punch_control_id != $p_obj->getPunchControlID()) {
                    $date_stamp = TTDate::getBeginDayEpoch($p_obj->getPunchControlObject()->getDateStamp());
                    $p_obj->setUser($this->user_id);
                    $p_obj->getPunchControlObject()->setPunchObject($p_obj);

                    $retarr[$date_stamp][$i] = array(
                        'id' => $p_obj->getPunchControlObject()->getID(),
                        'branch_id' => $p_obj->getPunchControlObject()->getBranch(),
                        'date_stamp' => $date_stamp,
                        //'user_date_id' => $p_obj->getPunchControlObject()->getUserDateID(),
                        'shift_data' => $p_obj->getPunchControlObject()->getShiftData()
                    );

                    $prev_punch_control_id = $p_obj->getPunchControlID();
                    $i++;
                }
            }

            if (isset($retarr)) {
                return $retarr;
            }
        }

        return false;
    }

    //DST time should be recorded based on the time the employee actually works, therefore one hour less on this day.

    public function getUserDateTotalArray($start_date, $end_date)
    {
        $udtlf = new UserDateTotalListFactory();

        $date_totals = array();

        //Get only system totals.
        $udtlf->getByCompanyIDAndUserIdAndObjectTypeAndStartDateAndEndDate($this->company_id, $this->user_id, array(5, 20, 25, 30, 40, 50, 100, 110), $start_date, $end_date);
        if ($udtlf->getRecordCount() > 0) {
            foreach ($udtlf as $udt_obj) {
                $type_and_policy_id = $udt_obj->getObjectType() . (int)$udt_obj->getPayCode();

                $date_totals[$udt_obj->getDateStamp()][] = array(
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

                    'quantity' => $udt_obj->getQuantity(),
                    'bad_quantity' => $udt_obj->getBadQuantity(),

                    'hourly_rate' => $udt_obj->getHourlyRate(),
                    //Override only shows for SYSTEM override columns...
                    //Need to check Worked overrides too.
                    'tmp_override' => $udt_obj->getOverride()
                );
            }
        }

        return $date_totals;
    }

    public function getCurrentAccrualBalance($user_id, $accrual_policy_account_id = null)
    {
        if ($user_id == '') {
            return false;
        }

        if ($accrual_policy_account_id == '') {
            $accrual_policy_account_id = $this->getId();
        }

        //Check min/max times of accrual policy.
        $ablf = TTnew('AccrualBalanceListFactory');
        $ablf->getByUserIdAndAccrualPolicyAccount($user_id, $accrual_policy_account_id);
        if ($ablf->getRecordCount() > 0) {
            $accrual_balance = $ablf->getCurrent()->getBalance();
        } else {
            $accrual_balance = 0;
        }

        Debug::Text('&nbsp;&nbsp; Current Accrual Balance: ' . $accrual_balance, __FILE__, __LINE__, __METHOD__, 10);

        return $accrual_balance;
    }

    public function testScheduleUnderTimePolicyB()
    {
        global $dd;

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods();
        $this->getAllPayPeriods();


        //Create Policy Group
        $dd->createPolicyGroup($this->company_id,
            null, //Meal
            null, //Exception
            null, //Holiday
            null, //OT
            null, //Premium
            null, //Round
            array($this->user_id), //Users
            null, //Break
            null, //Accrual
            null, //Expense
            array($this->policy_ids['absence_policy'][10], $this->policy_ids['absence_policy'][30]), //Absence
            array($this->policy_ids['regular'][10]) //Regular
        );

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $meal_policy_id = $this->createMealPolicy(10); //60min autodeduct
        $schedule_policy_id = $this->createSchedulePolicy(array(0), 0, 0); //No undertime
        $schedule_id = $this->createSchedule($this->user_id, $date_epoch, array(
            'schedule_policy_id' => $schedule_policy_id,
            'start_time' => ' 8:00AM',
            'end_time' => '4:00PM',
        ));

        $slf = TTNew('ScheduleListFactory');
        $slf->getByID($schedule_id);
        if ($slf->getRecordCount() == 1) {
            $s_obj = $slf->getCurrent();
            $this->assertEquals($date_stamp, TTDate::getDate('DATE', $s_obj->getStartTime()));
            $this->assertEquals($date_stamp, TTDate::getDate('DATE', $s_obj->getEndTime()));
            $this->assertEquals((8 * 3600), $s_obj->getTotalTime());
        } else {
            $this->assertEquals(true, false);
        }


        //Create punches to trigger undertime on same day.
        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 8:00AM'),
            strtotime($date_stamp . ' 3:00PM'),
            array(
                'in_type_id' => 10,
                'out_type_id' => 10,
                'branch_id' => 0,
                'department_id' => 0,
                'job_id' => 0,
                'job_item_id' => 0,
            ),
            true
        );

        $punch_arr = $this->getPunchDataArray(TTDate::getBeginDayEpoch($date_epoch), TTDate::getEndDayEpoch($date_epoch));
        //print_r($punch_arr);
        $this->assertEquals(1, count($punch_arr[$date_epoch]));
        $this->assertEquals($date_epoch, $punch_arr[$date_epoch][0]['date_stamp']);

        $udt_arr = $this->getUserDateTotalArray($date_epoch, $date_epoch);
        //var_dump( $udt_arr );

        //Total Time
        $this->assertEquals($udt_arr[$date_epoch][0]['object_type_id'], 5); //5=System Total
        $this->assertEquals($udt_arr[$date_epoch][0]['pay_code_id'], 0);
        $this->assertEquals($udt_arr[$date_epoch][0]['total_time'], (7 * 3600));
        //Regular Time
        $this->assertEquals($udt_arr[$date_epoch][1]['object_type_id'], 20); //Regular Time
        $this->assertEquals($udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][100]); //Regular Time
        $this->assertEquals($udt_arr[$date_epoch][1]['total_time'], (7 * 3600));
        //Absence Time
        //$this->assertEquals( $udt_arr[$date_epoch][2]['object_type_id'], 25 ); //Absence
        //$this->assertEquals( $udt_arr[$date_epoch][2]['pay_code_id'], $this->policy_ids['pay_code'][900] ); //Absence
        //$this->assertEquals( $udt_arr[$date_epoch][2]['total_time'], (1*3600) );
        //Absence Time
        //$this->assertEquals( $udt_arr[$date_epoch][3]['object_type_id'], 50 ); //Absence
        //$this->assertEquals( $udt_arr[$date_epoch][3]['pay_code_id'], $this->policy_ids['pay_code'][900] ); //Absence
        //$this->assertEquals( $udt_arr[$date_epoch][3]['total_time'], (1*3600) );

        //Make sure no other hours
        $this->assertEquals(count($udt_arr[$date_epoch]), 2);

        //Check Accrual Balance
        $accrual_balance = $this->getCurrentAccrualBalance($this->user_id, $this->policy_ids['accrual_policy_account'][20]);
        $this->assertEquals($accrual_balance, 0);

        return true;
    }

    public function testScheduleUnderTimePolicyC()
    {
        global $dd;

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods();
        $this->getAllPayPeriods();


        //Create Policy Group
        $dd->createPolicyGroup($this->company_id,
            null, //Meal
            null, //Exception
            null, //Holiday
            null, //OT
            null, //Premium
            null, //Round
            array($this->user_id), //Users
            null, //Break
            null, //Accrual
            null, //Expense
            array($this->policy_ids['absence_policy'][10], $this->policy_ids['absence_policy'][30]), //Absence
            array($this->policy_ids['regular'][10]) //Regular
        );

        $date_epoch = TTDate::getBeginDayEpoch((time() - 86400)); //This needs to be before today, as CalculatePolicy() restricts full shift undertime to only previous days.
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $meal_policy_id = $this->createMealPolicy(10); //60min autodeduct
        $schedule_policy_id = $this->createSchedulePolicy(array(0), $this->policy_ids['absence_policy'][10], 0); //Full Shift Undertime
        $schedule_id = $this->createSchedule($this->user_id, $date_epoch, array(
            'schedule_policy_id' => $schedule_policy_id,
            'start_time' => ' 8:00AM',
            'end_time' => '4:00PM',
        ));

        $slf = TTNew('ScheduleListFactory');
        $slf->getByID($schedule_id);
        if ($slf->getRecordCount() == 1) {
            $s_obj = $slf->getCurrent();
            $this->assertEquals($date_stamp, TTDate::getDate('DATE', $s_obj->getStartTime()));
            $this->assertEquals($date_stamp, TTDate::getDate('DATE', $s_obj->getEndTime()));
            $this->assertEquals((8 * 3600), $s_obj->getTotalTime());
        } else {
            $this->assertEquals(true, false);
        }


        $udt_arr = $this->getUserDateTotalArray($date_epoch, $date_epoch);
        //var_dump( $udt_arr );

        //Total Time
        $this->assertEquals($udt_arr[$date_epoch][0]['object_type_id'], 5); //5=System Total
        $this->assertEquals($udt_arr[$date_epoch][0]['pay_code_id'], 0);
        $this->assertEquals($udt_arr[$date_epoch][0]['total_time'], (8 * 3600));
        //Absence Time
        $this->assertEquals($udt_arr[$date_epoch][1]['object_type_id'], 25); //Absence
        $this->assertEquals($udt_arr[$date_epoch][1]['pay_code_id'], $this->policy_ids['pay_code'][900]); //Absence
        $this->assertEquals($udt_arr[$date_epoch][1]['total_time'], (8 * 3600));
        //Absence Time
        $this->assertEquals($udt_arr[$date_epoch][2]['object_type_id'], 50); //Absence
        $this->assertEquals($udt_arr[$date_epoch][2]['pay_code_id'], $this->policy_ids['pay_code'][900]); //Absence
        $this->assertEquals($udt_arr[$date_epoch][2]['total_time'], (8 * 3600));

        //Make sure no other hours
        $this->assertEquals(count($udt_arr[$date_epoch]), 3);

        //Check Accrual Balance
        $accrual_balance = $this->getCurrentAccrualBalance($this->user_id, $this->policy_ids['accrual_policy_account'][20]);
        $this->assertEquals($accrual_balance, (-8 * 3600));

        return true;
    }

    public function testScheduleConflictA()
    {
        global $dd;

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods();
        $this->getAllPayPeriods();


        //Create Policy Group
        $dd->createPolicyGroup($this->company_id,
            null, //Meal
            null, //Exception
            null, //Holiday
            null, //OT
            null, //Premium
            null, //Round
            array($this->user_id), //Users
            null, //Break
            null, //Accrual
            null, //Expense
            array($this->policy_ids['absence_policy'][10], $this->policy_ids['absence_policy'][30]), //Absence
            array($this->policy_ids['regular'][10]) //Regular
        );

        $date_epoch = TTDate::getBeginDayEpoch((time() - 86400)); //This needs to be before today, as CalculatePolicy() restricts full shift undertime to only previous days.
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $schedule_policy_id = $this->createSchedulePolicy(array(0), $this->policy_ids['absence_policy'][10], 0); //Full Shift Undertime
        $schedule_id = $this->createSchedule($this->user_id, $date_epoch, array(
            'schedule_policy_id' => $schedule_policy_id,
            'start_time' => ' 8:30AM',
            'end_time' => '4:30PM',
        ));

        $slf = TTNew('ScheduleListFactory');
        $slf->getByID($schedule_id);
        if ($slf->getRecordCount() == 1) {
            $s_obj = $slf->getCurrent();
            $this->assertEquals($date_stamp, TTDate::getDate('DATE', $s_obj->getStartTime()));
            $this->assertEquals($date_stamp, TTDate::getDate('DATE', $s_obj->getEndTime()));
            $this->assertEquals((8 * 3600), $s_obj->getTotalTime());
        } else {
            $this->assertEquals(true, false);
        }

        $schedule_id = $this->createSchedule($this->user_id, $date_epoch, array(
            'schedule_policy_id' => $schedule_policy_id,
            'start_time' => ' 8:30AM',
            'end_time' => '4:30PM',
        ));
        $this->assertEquals($schedule_id, false); //Validation error should occur, conflicting start/end time.

        $schedule_id = $this->createSchedule($this->user_id, $date_epoch, array(
            'schedule_policy_id' => $schedule_policy_id,
            'start_time' => ' 8:35AM',
            'end_time' => '4:30PM',
        ));
        $this->assertEquals($schedule_id, false); //Validation error should occur, conflicting start/end time.

        $schedule_id = $this->createSchedule($this->user_id, $date_epoch, array(
            'schedule_policy_id' => $schedule_policy_id,
            'start_time' => ' 8:30AM',
            'end_time' => '4:35PM',
        ));
        $this->assertEquals($schedule_id, false); //Validation error should occur, conflicting start/end time.

        $schedule_id = $this->createSchedule($this->user_id, $date_epoch, array(
            'schedule_policy_id' => $schedule_policy_id,
            'start_time' => ' 8:25AM',
            'end_time' => '4:30PM',
        ));
        $this->assertEquals($schedule_id, false); //Validation error should occur, conflicting start/end time.

        $schedule_id = $this->createSchedule($this->user_id, $date_epoch, array(
            'schedule_policy_id' => $schedule_policy_id,
            'start_time' => ' 8:30AM',
            'end_time' => '4:25PM',
        ));
        $this->assertEquals($schedule_id, false); //Validation error should occur, conflicting start/end time.

        $schedule_id = $this->createSchedule($this->user_id, $date_epoch, array(
            'schedule_policy_id' => $schedule_policy_id,
            'start_time' => ' 8:25AM',
            'end_time' => '4:25PM',
        ));
        $this->assertEquals($schedule_id, false); //Validation error should occur, conflicting start/end time.

        $schedule_id = $this->createSchedule($this->user_id, $date_epoch, array(
            'schedule_policy_id' => $schedule_policy_id,
            'start_time' => ' 8:35AM',
            'end_time' => '4:35PM',
        ));
        $this->assertEquals($schedule_id, false); //Validation error should occur, conflicting start/end time.

        $schedule_id = $this->createSchedule($this->user_id, $date_epoch, array(
            'schedule_policy_id' => $schedule_policy_id,
            'start_time' => ' 8:25AM',
            'end_time' => '4:35PM',
        ));
        $this->assertEquals($schedule_id, false); //Validation error should occur, conflicting start/end time.

        $schedule_id = $this->createSchedule($this->user_id, $date_epoch, array(
            'schedule_policy_id' => $schedule_policy_id,
            'start_time' => ' 1:00PM',
            'end_time' => '1:05PM',
        ));
        $this->assertEquals($schedule_id, false); //Validation error should occur, conflicting start/end time.

        $schedule_id = $this->createSchedule($this->user_id, $date_epoch, array(
            'schedule_policy_id' => $schedule_policy_id,
            'start_time' => ' 1:25AM',
            'end_time' => '11:35PM',
        ));
        $this->assertEquals($schedule_id, false); //Validation error should occur, conflicting start/end time.

        return true;
    }

    public function testOpenScheduleConflictA()
    {
        return true;
    }

    public function testOpenScheduleConflictB()
    {
        return true;
    }
}
