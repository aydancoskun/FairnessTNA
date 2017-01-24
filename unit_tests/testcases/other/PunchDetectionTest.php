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
class PunchDetectionTest extends PHPUnit_Framework_TestCase
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

        $dd->createPayStubAccount($this->company_id);
        $this->createPayStubAccounts();
        //$this->createPayStubAccrualAccount();
        $dd->createPayStubAccountLink($this->company_id);
        $this->getPayStubAccountLinkArray();

        $dd->createUserWageGroups($this->company_id);

        $this->user_id = $dd->createUser($this->company_id, 100);
        $ulf = TTnew('UserListFactory');
        $this->user_obj = $ulf->getById($this->user_id)->getCurrent();


        //Don't in each test now, so we can control the new_shift_trigger_time
        //$this->createPayPeriodSchedule( 10 );
        //$this->createPayPeriods();
        //$this->getAllPayPeriods();

        $this->policy_ids['pay_formula_policy'][100] = $dd->createPayFormulaPolicy($this->company_id, 100); //Reg 1.0x
        $this->policy_ids['pay_code'][100] = $dd->createPayCode($this->company_id, 100, $this->policy_ids['pay_formula_policy'][100]); //Regular

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

    public function getUserDateTotalArray($start_date, $end_date)
    {
        $udtlf = new UserDateTotalListFactory();

        $date_totals = array();

        //Get only system totals.
        //$udtlf->getByCompanyIDAndUserIdAndStatusAndStartDateAndEndDate( $this->company_id, $this->user_id, 10, $start_date, $end_date);
        $udtlf->getByCompanyIDAndUserIdAndObjectTypeAndStartDateAndEndDate($this->company_id, $this->user_id, array(5, 20, 30, 40, 100, 110), $start_date, $end_date);
        if ($udtlf->getRecordCount() > 0) {
            foreach ($udtlf as $udt_obj) {
                $type_and_policy_id = $udt_obj->getObjectType() . (int)$udt_obj->getObjectID();

                $date_totals[$udt_obj->getDateStamp()][] = array(
                    'date_stamp' => $udt_obj->getDateStamp(),
                    'id' => $udt_obj->getId(),

                    //'user_date_id' => $udt_obj->getUserDateId(),
                    //Keep legacy status_id/type_id for now, so we don't have to change as many unit tests.
                    'status_id' => $udt_obj->getStatus(),
                    'type_id' => $udt_obj->getType(),
                    //'over_time_policy_id' => $udt_obj->getOverTimePolicyID(),

                    'object_type_id' => $udt_obj->getObjectType(),
                    'object_id' => $udt_obj->getObjectID(),

                    'type_and_policy_id' => $type_and_policy_id,
                    'branch_id' => (int)$udt_obj->getBranch(),
                    'department_id' => $udt_obj->getDepartment(),
                    'total_time' => $udt_obj->getTotalTime(),
                    'name' => $udt_obj->getName(),
                    //Override only shows for SYSTEM override columns...
                    //Need to check Worked overrides too.
                    'tmp_override' => $udt_obj->getOverride()
                );
            }
        }

        return $date_totals;
    }

    public function getPreviousPunch($epoch)
    {
        $plf = TTnew('PunchListFactory');
        $plf->getPreviousPunchByUserIDAndEpoch($this->user_id, $epoch);
        if ($plf->getRecordCount() > 0) {
            Debug::Text(' Found Previous Punch within Continuous Time from now...', __FILE__, __LINE__, __METHOD__, 10);
            $prev_punch_obj = $plf->getCurrent();
            $prev_punch_obj->setUser($this->user_id);

            return $prev_punch_obj;
        }
        Debug::Text(' Previous Punch NOT found!', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    /**
     * @group PunchDetection_testNoMealOrBreakA
     */
    public function testNoMealOrBreakA()
    {
        global $dd;

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        //$policy_ids['meal'][] = $this->createMealPolicy( $this->company_id, 100 );

        //Create Policy Group
        $dd->createPolicyGroup($this->company_id,
            null,
            null,
            null,
            null,
            null,
            null,
            array($this->user_id));

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $dd->createPunch($this->user_id, 10, 10, strtotime($date_stamp . ' 8:00AM'), array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        $punch_time = strtotime($date_stamp . ' 12:00PM');
        $punch_data = $this->getDefaultPunchSettings($punch_time);
        $punch_type_id = $punch_data['type_id'];
        $punch_status_id = $punch_data['status_id'];
        $this->assertEquals($punch_type_id, 10); //Normal
        $this->assertEquals($punch_status_id, 20); //Out
        $dd->createPunch($this->user_id, $punch_type_id, $punch_status_id, $punch_time, array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        $punch_time = strtotime($date_stamp . ' 1:00PM');
        $punch_data = $this->getDefaultPunchSettings($punch_time);
        $punch_type_id = $punch_data['type_id'];
        $punch_status_id = $punch_data['status_id'];
        $this->assertEquals($punch_type_id, 10); //Normal
        $this->assertEquals($punch_status_id, 10); //In
        $dd->createPunch($this->user_id, $punch_type_id, $punch_status_id, $punch_time, array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        $punch_time = strtotime($date_stamp . ' 5:00PM');
        $punch_data = $this->getDefaultPunchSettings($punch_time);
        $punch_type_id = $punch_data['type_id'];
        $punch_status_id = $punch_data['status_id'];
        $this->assertEquals($punch_type_id, 10); //Normal
        $this->assertEquals($punch_status_id, 20); //Out
        $dd->createPunch($this->user_id, $punch_type_id, $punch_status_id, $punch_time, array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        $punch_arr = $this->getPunchDataArray(TTDate::getBeginDayEpoch($date_epoch), TTDate::getEndDayEpoch($date_epoch));
        //print_r($punch_arr);
        $this->assertEquals(2, count($punch_arr[$date_epoch]));
        $this->assertEquals($date_epoch, $punch_arr[$date_epoch][0]['date_stamp']);

        $this->assertEquals(4, count($punch_arr[$date_epoch][0]['shift_data']['punches']));
        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][0]['type_id']);
        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][0]['status_id']);

        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][1]['type_id']);
        $this->assertEquals(20, $punch_arr[$date_epoch][0]['shift_data']['punches'][1]['status_id']);

        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][2]['type_id']);
        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][2]['status_id']);

        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][3]['type_id']);
        $this->assertEquals(20, $punch_arr[$date_epoch][0]['shift_data']['punches'][3]['status_id']);

        return true;
    }

    public function createPayPeriodSchedule($shift_assigned_day = 10, $new_shift_trigger_time = 14400)
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
        $ppsf->setNewDayTriggerTime($new_shift_trigger_time);
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

    public function createPayPeriods()
    {
        $max_pay_periods = 29;

        $ppslf = new PayPeriodScheduleListFactory();
        $ppslf->getById($this->pay_period_schedule_id);
        if ($ppslf->getRecordCount() > 0) {
            $pps_obj = $ppslf->getCurrent();


            for ($i = 0; $i < $max_pay_periods; $i++) {
                if ($i == 0) {
                    //$end_date = TTDate::getBeginYearEpoch( strtotime('01-Jan-07') );
                    $end_date = TTDate::getBeginWeekEpoch((TTDate::getBeginYearEpoch(time()) - (86400 * (7 * 6))));
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

    public function getDefaultPunchSettings($epoch)
    {
        $pf = TTnew('PunchFactory');
        return $pf->getDefaultPunchSettings($this->user_obj, $epoch);
    }

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
                        'date_stamp' => $date_stamp,
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

    /**
     * @group PunchDetection_testNoMealOrBreakB
     */
    public function testNoMealOrBreakB()
    {
        global $dd;

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        //$policy_ids['meal'][] = $this->createMealPolicy( $this->company_id, 100 );

        //Create Policy Group
        $dd->createPolicyGroup($this->company_id,
            null,
            null,
            null,
            null,
            null,
            null,
            array($this->user_id));

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $date_epoch2 = TTDate::getBeginDayEpoch((TTDate::getBeginWeekEpoch(time()) + 86400 + 3600));
        $date_stamp2 = TTDate::getDate('DATE', $date_epoch2);

        $dd->createPunch($this->user_id, 10, 10, strtotime($date_stamp . ' 8:00PM'), array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        $punch_time = strtotime($date_stamp . ' 11:30PM');
        $punch_data = $this->getDefaultPunchSettings($punch_time);
        $punch_type_id = $punch_data['type_id'];
        $punch_status_id = $punch_data['status_id'];
        $this->assertEquals($punch_type_id, 10); //Normal
        $this->assertEquals($punch_status_id, 20); //Out
        $dd->createPunch($this->user_id, $punch_type_id, $punch_status_id, $punch_time, array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        $punch_time = strtotime($date_stamp2 . ' 12:30AM');
        $punch_data = $this->getDefaultPunchSettings($punch_time);
        $punch_type_id = $punch_data['type_id'];
        $punch_status_id = $punch_data['status_id'];
        $this->assertEquals($punch_type_id, 10); //Normal
        $this->assertEquals($punch_status_id, 10); //In
        $dd->createPunch($this->user_id, $punch_type_id, $punch_status_id, $punch_time, array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        $punch_time = strtotime($date_stamp2 . ' 5:00AM');
        $punch_data = $this->getDefaultPunchSettings($punch_time);
        $punch_type_id = $punch_data['type_id'];
        $punch_status_id = $punch_data['status_id'];
        $this->assertEquals($punch_type_id, 10); //Normal
        $this->assertEquals($punch_status_id, 20); //Out
        $dd->createPunch($this->user_id, $punch_type_id, $punch_status_id, $punch_time, array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        $punch_arr = $this->getPunchDataArray(TTDate::getBeginDayEpoch($date_epoch), TTDate::getEndDayEpoch($date_epoch));
        //print_r($punch_arr);
        $this->assertEquals(2, count($punch_arr[$date_epoch]));
        $this->assertEquals($date_epoch, $punch_arr[$date_epoch][0]['date_stamp']);

        $this->assertEquals(4, count($punch_arr[$date_epoch][0]['shift_data']['punches']));
        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][0]['type_id']);
        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][0]['status_id']);

        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][1]['type_id']);
        $this->assertEquals(20, $punch_arr[$date_epoch][0]['shift_data']['punches'][1]['status_id']);

        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][2]['type_id']);
        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][2]['status_id']);

        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][3]['type_id']);
        $this->assertEquals(20, $punch_arr[$date_epoch][0]['shift_data']['punches'][3]['status_id']);

        return true;
    }

    /*
     Tests:
        - Normal In/Out punches in the middle of the day with no policies
        - Normal In/Out punches around midnight with no policies
        - Lunch punches with Time Window detection
        - Lunch punches with Punch Time detection
        - Break punches with Time Window detection
        - Break punches with Punch Time detection
    */

    /**
     * @group PunchDetection_testNoMealOrBreakBWithFutureShiftA
     */
    public function testNoMealOrBreakBWithFutureShiftA()
    {
        global $dd;

        //
        //Test case where a auto-punch scheduled shift is created in the future (ie: 21:00 - 23:00) and the employee is punching earlier than that.
        //

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        //$policy_ids['meal'][] = $this->createMealPolicy( $this->company_id, 100 );

        //Create Policy Group
        $dd->createPolicyGroup($this->company_id,
            null,
            null,
            null,
            null,
            null,
            null,
            array($this->user_id));

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        //$date_epoch2 = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() )+86400+3600 );
        //$date_stamp2 = TTDate::getDate('DATE', $date_epoch2 );

        //Create future shift first.
        $dd->createPunch($this->user_id, 10, 10, strtotime($date_stamp . ' 9:00PM'), array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);
        $dd->createPunch($this->user_id, 10, 20, strtotime($date_stamp . ' 10:00PM'), array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        $punch_time = strtotime($date_stamp . ' 10:00AM');
        $punch_data = $this->getDefaultPunchSettings($punch_time);
        $punch_type_id = $punch_data['type_id'];
        $punch_status_id = $punch_data['status_id'];
        $this->assertEquals($punch_type_id, 10); //Normal
        $this->assertEquals($punch_status_id, 10); //Out
        $dd->createPunch($this->user_id, $punch_type_id, $punch_status_id, $punch_time, array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        $punch_time = strtotime($date_stamp . ' 4:00PM');
        $punch_data = $this->getDefaultPunchSettings($punch_time);
        $punch_type_id = $punch_data['type_id'];
        $punch_status_id = $punch_data['status_id'];
        $this->assertEquals($punch_type_id, 10); //Normal
        $this->assertEquals($punch_status_id, 20); //In
        $dd->createPunch($this->user_id, $punch_type_id, $punch_status_id, $punch_time, array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        $punch_arr = $this->getPunchDataArray(TTDate::getBeginDayEpoch($date_epoch), TTDate::getEndDayEpoch($date_epoch));
        //print_r($punch_arr);
        $this->assertEquals(2, count($punch_arr[$date_epoch]));
        $this->assertEquals($date_epoch, $punch_arr[$date_epoch][0]['date_stamp']);

        $this->assertEquals(2, count($punch_arr[$date_epoch][0]['shift_data']['punches']));
        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][0]['type_id']);
        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][0]['status_id']);

        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][1]['type_id']);
        $this->assertEquals(20, $punch_arr[$date_epoch][0]['shift_data']['punches'][1]['status_id']);

        $this->assertEquals(10, $punch_arr[$date_epoch][1]['shift_data']['punches'][0]['type_id']);
        $this->assertEquals(10, $punch_arr[$date_epoch][1]['shift_data']['punches'][0]['status_id']);

        $this->assertEquals(10, $punch_arr[$date_epoch][1]['shift_data']['punches'][1]['type_id']);
        $this->assertEquals(20, $punch_arr[$date_epoch][1]['shift_data']['punches'][1]['status_id']);

        return true;
    }

    /**
     * @group PunchDetection_testNoMealOrBreakBWithFutureShiftB
     */
    public function testNoMealOrBreakBWithFutureShiftB()
    {
        global $dd;

        //
        //Test case where a auto-punch scheduled shift is created in the future (ie: 21:00 - 23:00) and the employee is punching earlier than that, but also has transfer punches.
        //

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        //$policy_ids['meal'][] = $this->createMealPolicy( $this->company_id, 100 );

        //Create Policy Group
        $dd->createPolicyGroup($this->company_id,
            null,
            null,
            null,
            null,
            null,
            null,
            array($this->user_id));

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        //$date_epoch2 = TTDate::getBeginDayEpoch( TTDate::getBeginWeekEpoch( time() )+86400+3600 );
        //$date_stamp2 = TTDate::getDate('DATE', $date_epoch2 );

        //Create future shift first.
        $dd->createPunch($this->user_id, 10, 10, strtotime($date_stamp . ' 9:00PM'), array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);
        $dd->createPunch($this->user_id, 10, 20, strtotime($date_stamp . ' 10:00PM'), array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        $punch_time = strtotime($date_stamp . ' 10:00AM');
        $punch_data = $this->getDefaultPunchSettings($punch_time);
        $punch_type_id = $punch_data['type_id'];
        $punch_status_id = $punch_data['status_id'];
        $this->assertEquals($punch_type_id, 10); //Normal
        $this->assertEquals($punch_status_id, 10); //Out
        $dd->createPunch($this->user_id, $punch_type_id, $punch_status_id, $punch_time, array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        $punch_time = strtotime($date_stamp . ' 4:00PM');
        $punch_data = $this->getDefaultPunchSettings($punch_time);
        $punch_type_id = $punch_data['type_id'];
        $punch_status_id = $punch_data['status_id'];
        $this->assertEquals($punch_type_id, 10); //Normal
        $this->assertEquals($punch_status_id, 20); //Out
        $dd->createPunch($this->user_id, $punch_type_id, $punch_status_id, $punch_time, array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        $punch_time = strtotime($date_stamp . ' 4:00PM');
        $punch_data = $this->getDefaultPunchSettings($punch_time);
        $punch_type_id = $punch_data['type_id'];
        $punch_status_id = $punch_data['status_id'];
        $this->assertEquals($punch_type_id, 10); //Normal
        $this->assertEquals($punch_status_id, 10); //In
        $dd->createPunch($this->user_id, $punch_type_id, $punch_status_id, $punch_time, array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        $punch_time = strtotime($date_stamp . ' 5:00PM');
        $punch_data = $this->getDefaultPunchSettings($punch_time);
        $punch_type_id = $punch_data['type_id'];
        $punch_status_id = $punch_data['status_id'];
        $this->assertEquals($punch_type_id, 10); //Normal
        $this->assertEquals($punch_status_id, 20); //Out
        $dd->createPunch($this->user_id, $punch_type_id, $punch_status_id, $punch_time, array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        $punch_arr = $this->getPunchDataArray(TTDate::getBeginDayEpoch($date_epoch), TTDate::getEndDayEpoch($date_epoch));
        //print_r($punch_arr);
        $this->assertEquals(3, count($punch_arr[$date_epoch]));
        $this->assertEquals($date_epoch, $punch_arr[$date_epoch][0]['date_stamp']);

        $this->assertEquals(4, count($punch_arr[$date_epoch][0]['shift_data']['punches']));
        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][0]['type_id']);
        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][0]['status_id']);

        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][1]['type_id']);
        $this->assertEquals(20, $punch_arr[$date_epoch][0]['shift_data']['punches'][1]['status_id']);

        $this->assertEquals(10, $punch_arr[$date_epoch][1]['shift_data']['punches'][0]['type_id']);
        $this->assertEquals(10, $punch_arr[$date_epoch][1]['shift_data']['punches'][0]['status_id']);

        $this->assertEquals(10, $punch_arr[$date_epoch][1]['shift_data']['punches'][1]['type_id']);
        $this->assertEquals(20, $punch_arr[$date_epoch][1]['shift_data']['punches'][1]['status_id']);

        return true;
    }

    /**
     * @group PunchDetection_testMealTimeWindowA
     */
    public function testMealTimeWindowA()
    {
        global $dd;

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $policy_ids['meal'][] = $this->createMealPolicy($this->company_id, 100);

        //Create Policy Group
        $dd->createPolicyGroup($this->company_id,
            $policy_ids['meal'],
            null,
            null,
            null,
            null,
            null,
            array($this->user_id));

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $dd->createPunch($this->user_id, 10, 10, strtotime($date_stamp . ' 8:00AM'), array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        $punch_time = strtotime($date_stamp . ' 12:00PM');
        $punch_data = $this->getDefaultPunchSettings($punch_time);
        $punch_type_id = $punch_data['type_id'];
        $punch_status_id = $punch_data['status_id'];
        $this->assertEquals($punch_type_id, 20); //Lunch
        $this->assertEquals($punch_status_id, 20); //Out
        $dd->createPunch($this->user_id, $punch_type_id, $punch_status_id, $punch_time, array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        $punch_time = strtotime($date_stamp . ' 1:00PM');
        $punch_data = $this->getDefaultPunchSettings($punch_time);
        $punch_type_id = $punch_data['type_id'];
        $punch_status_id = $punch_data['status_id'];
        $this->assertEquals($punch_type_id, 20); //Lunch
        $this->assertEquals($punch_status_id, 10); //In
        $dd->createPunch($this->user_id, $punch_type_id, $punch_status_id, $punch_time, array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        $punch_time = strtotime($date_stamp . ' 5:00PM');
        $punch_data = $this->getDefaultPunchSettings($punch_time);
        $punch_type_id = $punch_data['type_id'];
        $punch_status_id = $punch_data['status_id'];
        $this->assertEquals($punch_type_id, 10); //Normal
        $this->assertEquals($punch_status_id, 20); //Out
        $dd->createPunch($this->user_id, $punch_type_id, $punch_status_id, $punch_time, array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        $punch_arr = $this->getPunchDataArray(TTDate::getBeginDayEpoch($date_epoch), TTDate::getEndDayEpoch($date_epoch));
        //print_r($punch_arr);
        $this->assertEquals(2, count($punch_arr[$date_epoch]));
        $this->assertEquals($date_epoch, $punch_arr[$date_epoch][0]['date_stamp']);

        $this->assertEquals(4, count($punch_arr[$date_epoch][0]['shift_data']['punches']));
        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][0]['type_id']);
        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][0]['status_id']);

        $this->assertEquals(20, $punch_arr[$date_epoch][0]['shift_data']['punches'][1]['type_id']);
        $this->assertEquals(20, $punch_arr[$date_epoch][0]['shift_data']['punches'][1]['status_id']);

        $this->assertEquals(20, $punch_arr[$date_epoch][0]['shift_data']['punches'][2]['type_id']);
        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][2]['status_id']);

        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][3]['type_id']);
        $this->assertEquals(20, $punch_arr[$date_epoch][0]['shift_data']['punches'][3]['status_id']);

        return true;
    }

    public function createMealPolicy($company_id, $type)
    {
        $mpf = new MealPolicyFactory();
        $mpf->setCompany($company_id);

        switch ($type) {
            case 100: //Normal 1hr lunch: Detect by Time Window
                $mpf->setName('Normal - Time Window');
                $mpf->setType(20);
                $mpf->setTriggerTime((3600 * 6));
                $mpf->setAmount(3600);
                $mpf->setAutoDetectType(10);

                $mpf->setStartWindow((3 * 3600));
                $mpf->setWindowLength((2 * 3600));
                $mpf->setIncludeLunchPunchTime(false);
                break;
            case 110: //Normal 1hr lunch: Detect by Punch Time
                $mpf->setName('Normal - Punch Time');
                $mpf->setType(20);
                $mpf->setTriggerTime((3600 * 6));
                $mpf->setAmount(3600);
                $mpf->setAutoDetectType(20);

                $mpf->setMinimumPunchTime((60 * 30)); ///0.5hr
                $mpf->setMaximumPunchTime((60 * 75)); //1.25hr
                $mpf->setIncludeLunchPunchTime(false);
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

    /**
     * @group PunchDetection_testMealTimeWindowB
     */
    public function testMealTimeWindowB()
    {
        global $dd;

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $policy_ids['meal'][] = $this->createMealPolicy($this->company_id, 100);

        //Create Policy Group
        $dd->createPolicyGroup($this->company_id,
            $policy_ids['meal'],
            null,
            null,
            null,
            null,
            null,
            array($this->user_id));

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $dd->createPunch($this->user_id, 10, 10, strtotime($date_stamp . ' 8:00AM'), array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        $punch_time = strtotime($date_stamp . ' 10:30AM');
        $punch_data = $this->getDefaultPunchSettings($punch_time);
        $punch_type_id = $punch_data['type_id'];
        $punch_status_id = $punch_data['status_id'];
        $this->assertEquals($punch_type_id, 10); //Lunch
        $this->assertEquals($punch_status_id, 20); //Out
        $dd->createPunch($this->user_id, $punch_type_id, $punch_status_id, $punch_time, array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        $punch_time = strtotime($date_stamp . ' 11:30AM');
        $punch_data = $this->getDefaultPunchSettings($punch_time);
        $punch_type_id = $punch_data['type_id'];
        $punch_status_id = $punch_data['status_id'];
        $this->assertEquals($punch_type_id, 10); //Lunch
        $this->assertEquals($punch_status_id, 10); //In
        $dd->createPunch($this->user_id, $punch_type_id, $punch_status_id, $punch_time, array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        $punch_time = strtotime($date_stamp . ' 5:00PM');
        $punch_data = $this->getDefaultPunchSettings($punch_time);
        $punch_type_id = $punch_data['type_id'];
        $punch_status_id = $punch_data['status_id'];
        $this->assertEquals($punch_type_id, 10); //Normal
        $this->assertEquals($punch_status_id, 20); //Out
        $dd->createPunch($this->user_id, $punch_type_id, $punch_status_id, $punch_time, array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        $punch_arr = $this->getPunchDataArray(TTDate::getBeginDayEpoch($date_epoch), TTDate::getEndDayEpoch($date_epoch));
        //print_r($punch_arr);
        $this->assertEquals(2, count($punch_arr[$date_epoch]));
        $this->assertEquals($date_epoch, $punch_arr[$date_epoch][0]['date_stamp']);

        $this->assertEquals(4, count($punch_arr[$date_epoch][0]['shift_data']['punches']));
        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][0]['type_id']);
        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][0]['status_id']);

        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][1]['type_id']);
        $this->assertEquals(20, $punch_arr[$date_epoch][0]['shift_data']['punches'][1]['status_id']);

        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][2]['type_id']);
        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][2]['status_id']);

        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][3]['type_id']);
        $this->assertEquals(20, $punch_arr[$date_epoch][0]['shift_data']['punches'][3]['status_id']);

        return true;
    }

    /**
     * @group PunchDetection_testMealTimeWindowC
     */
    public function testMealTimeWindowC()
    {
        global $dd;

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $policy_ids['meal'][] = $this->createMealPolicy($this->company_id, 100);

        //Create Policy Group
        $dd->createPolicyGroup($this->company_id,
            $policy_ids['meal'],
            null,
            null,
            null,
            null,
            null,
            array($this->user_id));

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $dd->createPunch($this->user_id, 10, 10, strtotime($date_stamp . ' 8:00AM'), array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        $punch_time = strtotime($date_stamp . ' 3:30PM');
        $punch_data = $this->getDefaultPunchSettings($punch_time);
        $punch_type_id = $punch_data['type_id'];
        $punch_status_id = $punch_data['status_id'];
        $this->assertEquals($punch_type_id, 10); //Lunch
        $this->assertEquals($punch_status_id, 20); //Out
        $dd->createPunch($this->user_id, $punch_type_id, $punch_status_id, $punch_time, array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        $punch_time = strtotime($date_stamp . ' 4:30PM');
        $punch_data = $this->getDefaultPunchSettings($punch_time);
        $punch_type_id = $punch_data['type_id'];
        $punch_status_id = $punch_data['status_id'];
        $this->assertEquals($punch_type_id, 10); //Lunch
        $this->assertEquals($punch_status_id, 10); //In
        $dd->createPunch($this->user_id, $punch_type_id, $punch_status_id, $punch_time, array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        $punch_time = strtotime($date_stamp . ' 5:00PM');
        $punch_data = $this->getDefaultPunchSettings($punch_time);
        $punch_type_id = $punch_data['type_id'];
        $punch_status_id = $punch_data['status_id'];
        $this->assertEquals($punch_type_id, 10); //Normal
        $this->assertEquals($punch_status_id, 20); //Out
        $dd->createPunch($this->user_id, $punch_type_id, $punch_status_id, $punch_time, array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        $punch_arr = $this->getPunchDataArray(TTDate::getBeginDayEpoch($date_epoch), TTDate::getEndDayEpoch($date_epoch));
        //print_r($punch_arr);
        $this->assertEquals(2, count($punch_arr[$date_epoch]));
        $this->assertEquals($date_epoch, $punch_arr[$date_epoch][0]['date_stamp']);

        $this->assertEquals(4, count($punch_arr[$date_epoch][0]['shift_data']['punches']));
        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][0]['type_id']);
        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][0]['status_id']);

        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][1]['type_id']);
        $this->assertEquals(20, $punch_arr[$date_epoch][0]['shift_data']['punches'][1]['status_id']);

        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][2]['type_id']);
        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][2]['status_id']);

        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][3]['type_id']);
        $this->assertEquals(20, $punch_arr[$date_epoch][0]['shift_data']['punches'][3]['status_id']);

        return true;
    }

    /**
     * @group PunchDetection_testMealPunchTimeWindowA
     */
    public function testMealPunchTimeWindowA()
    {
        global $dd;

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $policy_ids['meal'][] = $this->createMealPolicy($this->company_id, 110);

        //Create Policy Group
        $dd->createPolicyGroup($this->company_id,
            $policy_ids['meal'],
            null,
            null,
            null,
            null,
            null,
            array($this->user_id));

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $dd->createPunch($this->user_id, 10, 10, strtotime($date_stamp . ' 8:00AM'), array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        $punch_time = strtotime($date_stamp . ' 12:00PM');
        $punch_data = $this->getDefaultPunchSettings($punch_time);
        $punch_type_id = $punch_data['type_id'];
        $punch_status_id = $punch_data['status_id'];
        $this->assertEquals($punch_type_id, 10); //Normal - Because when using punch time it can't be detected on the first out punch.
        $this->assertEquals($punch_status_id, 20); //Out
        $dd->createPunch($this->user_id, $punch_type_id, $punch_status_id, $punch_time, array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        $punch_time = strtotime($date_stamp . ' 1:00PM');
        $punch_data = $this->getDefaultPunchSettings($punch_time);
        $punch_type_id = $punch_data['type_id'];
        $punch_status_id = $punch_data['status_id'];
        $this->assertEquals($punch_type_id, 20); //Lunch
        $this->assertEquals($punch_status_id, 10); //In
        $dd->createPunch($this->user_id, $punch_type_id, $punch_status_id, $punch_time, array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        $punch_time = strtotime($date_stamp . ' 5:00PM');
        $punch_data = $this->getDefaultPunchSettings($punch_time);
        $punch_type_id = $punch_data['type_id'];
        $punch_status_id = $punch_data['status_id'];
        $this->assertEquals($punch_type_id, 10); //Normal
        $this->assertEquals($punch_status_id, 20); //Out
        $dd->createPunch($this->user_id, $punch_type_id, $punch_status_id, $punch_time, array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        $punch_arr = $this->getPunchDataArray(TTDate::getBeginDayEpoch($date_epoch), TTDate::getEndDayEpoch($date_epoch));
        //print_r($punch_arr);
        $this->assertEquals(2, count($punch_arr[$date_epoch]));
        $this->assertEquals($date_epoch, $punch_arr[$date_epoch][0]['date_stamp']);

        $this->assertEquals(4, count($punch_arr[$date_epoch][0]['shift_data']['punches']));
        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][0]['type_id']);
        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][0]['status_id']);

        $this->assertEquals(20, $punch_arr[$date_epoch][0]['shift_data']['punches'][1]['type_id']);
        $this->assertEquals(20, $punch_arr[$date_epoch][0]['shift_data']['punches'][1]['status_id']);

        $this->assertEquals(20, $punch_arr[$date_epoch][0]['shift_data']['punches'][2]['type_id']);
        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][2]['status_id']);

        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][3]['type_id']);
        $this->assertEquals(20, $punch_arr[$date_epoch][0]['shift_data']['punches'][3]['status_id']);

        return true;
    }

    /**
     * @group PunchDetection_testMealPunchTimeWindowB
     */
    public function testMealPunchTimeWindowB()
    {
        global $dd;

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $policy_ids['meal'][] = $this->createMealPolicy($this->company_id, 110);

        //Create Policy Group
        $dd->createPolicyGroup($this->company_id,
            $policy_ids['meal'],
            null,
            null,
            null,
            null,
            null,
            array($this->user_id));

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $dd->createPunch($this->user_id, 10, 10, strtotime($date_stamp . ' 8:00AM'), array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        $punch_time = strtotime($date_stamp . ' 12:00PM');
        $punch_data = $this->getDefaultPunchSettings($punch_time);
        $punch_type_id = $punch_data['type_id'];
        $punch_status_id = $punch_data['status_id'];
        $this->assertEquals($punch_type_id, 10); //Normal - Because when using punch time it can't be detected on the first out punch.
        $this->assertEquals($punch_status_id, 20); //Out
        $dd->createPunch($this->user_id, $punch_type_id, $punch_status_id, $punch_time, array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        $punch_time = strtotime($date_stamp . ' 1:30PM');
        $punch_data = $this->getDefaultPunchSettings($punch_time);
        $punch_type_id = $punch_data['type_id'];
        $punch_status_id = $punch_data['status_id'];
        $this->assertEquals($punch_type_id, 10); //Normal
        $this->assertEquals($punch_status_id, 10); //In
        $dd->createPunch($this->user_id, $punch_type_id, $punch_status_id, $punch_time, array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        $punch_time = strtotime($date_stamp . ' 5:00PM');
        $punch_data = $this->getDefaultPunchSettings($punch_time);
        $punch_type_id = $punch_data['type_id'];
        $punch_status_id = $punch_data['status_id'];
        $this->assertEquals($punch_type_id, 10); //Normal
        $this->assertEquals($punch_status_id, 20); //Out
        $dd->createPunch($this->user_id, $punch_type_id, $punch_status_id, $punch_time, array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        $punch_arr = $this->getPunchDataArray(TTDate::getBeginDayEpoch($date_epoch), TTDate::getEndDayEpoch($date_epoch));
        //print_r($punch_arr);
        $this->assertEquals(2, count($punch_arr[$date_epoch]));
        $this->assertEquals($date_epoch, $punch_arr[$date_epoch][0]['date_stamp']);

        $this->assertEquals(4, count($punch_arr[$date_epoch][0]['shift_data']['punches']));
        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][0]['type_id']);
        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][0]['status_id']);

        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][1]['type_id']);
        $this->assertEquals(20, $punch_arr[$date_epoch][0]['shift_data']['punches'][1]['status_id']);

        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][2]['type_id']);
        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][2]['status_id']);

        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][3]['type_id']);
        $this->assertEquals(20, $punch_arr[$date_epoch][0]['shift_data']['punches'][3]['status_id']);

        return true;
    }

    /**
     * @group PunchDetection_testBreakTimeWindowA
     */
    public function testBreakTimeWindowA()
    {
        global $dd;

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $policy_ids['break'][] = $this->createBreakPolicy($this->company_id, 100);

        //Create Policy Group
        $dd->createPolicyGroup($this->company_id,
            null,
            null,
            null,
            null,
            null,
            null,
            array($this->user_id),
            $policy_ids['break']
        );

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $dd->createPunch($this->user_id, 10, 10, strtotime($date_stamp . ' 8:00AM'), array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        $punch_time = strtotime($date_stamp . ' 9:30AM');
        $punch_data = $this->getDefaultPunchSettings($punch_time);
        $punch_type_id = $punch_data['type_id'];
        $punch_status_id = $punch_data['status_id'];
        $this->assertEquals($punch_type_id, 30); //Break
        $this->assertEquals($punch_status_id, 20); //Out
        $dd->createPunch($this->user_id, $punch_type_id, $punch_status_id, $punch_time, array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        $punch_time = strtotime($date_stamp . ' 9:45AM');
        $punch_data = $this->getDefaultPunchSettings($punch_time);
        $punch_type_id = $punch_data['type_id'];
        $punch_status_id = $punch_data['status_id'];
        $this->assertEquals($punch_type_id, 30); //Break
        $this->assertEquals($punch_status_id, 10); //In
        $dd->createPunch($this->user_id, $punch_type_id, $punch_status_id, $punch_time, array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        $punch_time = strtotime($date_stamp . ' 5:00PM');
        $punch_data = $this->getDefaultPunchSettings($punch_time);
        $punch_type_id = $punch_data['type_id'];
        $punch_status_id = $punch_data['status_id'];
        $this->assertEquals($punch_type_id, 10); //Normal
        $this->assertEquals($punch_status_id, 20); //Out
        $dd->createPunch($this->user_id, $punch_type_id, $punch_status_id, $punch_time, array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        $punch_arr = $this->getPunchDataArray(TTDate::getBeginDayEpoch($date_epoch), TTDate::getEndDayEpoch($date_epoch));
        //print_r($punch_arr);
        $this->assertEquals(2, count($punch_arr[$date_epoch]));
        $this->assertEquals($date_epoch, $punch_arr[$date_epoch][0]['date_stamp']);

        $this->assertEquals(4, count($punch_arr[$date_epoch][0]['shift_data']['punches']));
        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][0]['type_id']);
        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][0]['status_id']);

        $this->assertEquals(30, $punch_arr[$date_epoch][0]['shift_data']['punches'][1]['type_id']);
        $this->assertEquals(20, $punch_arr[$date_epoch][0]['shift_data']['punches'][1]['status_id']);

        $this->assertEquals(30, $punch_arr[$date_epoch][0]['shift_data']['punches'][2]['type_id']);
        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][2]['status_id']);

        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][3]['type_id']);
        $this->assertEquals(20, $punch_arr[$date_epoch][0]['shift_data']['punches'][3]['status_id']);

        return true;
    }

    public function createBreakPolicy($company_id, $type)
    {
        $bpf = new BreakPolicyFactory();
        $bpf->setCompany($company_id);

        switch ($type) {
            case 100: //Normal 15min break: Detect by Time Window
                $bpf->setName('Normal');
                $bpf->setType(20);
                $bpf->setTriggerTime((3600 * 0.5));
                $bpf->setAmount(60 * 15);
                $bpf->setAutoDetectType(10);

                $bpf->setStartWindow((1 * 3600));
                $bpf->setWindowLength((1 * 3600));

                $bpf->setIncludeBreakPunchTime(false);
                $bpf->setIncludeMultipleBreaks(false);
                break;
            case 110: //Normal 15min break: Detect by Punch Time
                $bpf->setName('Normal');
                $bpf->setType(20);
                $bpf->setTriggerTime((3600 * 0.5));
                $bpf->setAmount(60 * 15);
                $bpf->setAutoDetectType(20);

                $bpf->setMinimumPunchTime((60 * 5)); ///5min
                $bpf->setMaximumPunchTime((60 * 25)); //25min

                $bpf->setIncludeBreakPunchTime(false);
                $bpf->setIncludeMultipleBreaks(false);
                break;
        }

        $bpf->setPayCode($this->policy_ids['pay_code'][100]);

        if ($bpf->isValid()) {
            $insert_id = $bpf->Save();
            Debug::Text('Break Policy ID: ' . $insert_id, __FILE__, __LINE__, __METHOD__, 10);

            return $insert_id;
        }

        Debug::Text('Failed Creating Break Policy!', __FILE__, __LINE__, __METHOD__, 10);

        return false;
    }

    /**
     * @group PunchDetection_testBreakTimeWindowB
     */
    public function testBreakTimeWindowB()
    {
        global $dd;

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $policy_ids['break'][] = $this->createBreakPolicy($this->company_id, 100);

        //Create Policy Group
        $dd->createPolicyGroup($this->company_id,
            null,
            null,
            null,
            null,
            null,
            null,
            array($this->user_id),
            $policy_ids['break']
        );

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $dd->createPunch($this->user_id, 10, 10, strtotime($date_stamp . ' 8:00AM'), array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        $punch_time = strtotime($date_stamp . ' 8:30AM');
        $punch_data = $this->getDefaultPunchSettings($punch_time);
        $punch_type_id = $punch_data['type_id'];
        $punch_status_id = $punch_data['status_id'];
        $this->assertEquals($punch_type_id, 10); //Normal
        $this->assertEquals($punch_status_id, 20); //Out
        $dd->createPunch($this->user_id, $punch_type_id, $punch_status_id, $punch_time, array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        $punch_time = strtotime($date_stamp . ' 8:45AM');
        $punch_data = $this->getDefaultPunchSettings($punch_time);
        $punch_type_id = $punch_data['type_id'];
        $punch_status_id = $punch_data['status_id'];
        $this->assertEquals($punch_type_id, 10); //Normal
        $this->assertEquals($punch_status_id, 10); //In
        $dd->createPunch($this->user_id, $punch_type_id, $punch_status_id, $punch_time, array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        $punch_time = strtotime($date_stamp . ' 5:00PM');
        $punch_data = $this->getDefaultPunchSettings($punch_time);
        $punch_type_id = $punch_data['type_id'];
        $punch_status_id = $punch_data['status_id'];
        $this->assertEquals($punch_type_id, 10); //Normal
        $this->assertEquals($punch_status_id, 20); //Out
        $dd->createPunch($this->user_id, $punch_type_id, $punch_status_id, $punch_time, array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        $punch_arr = $this->getPunchDataArray(TTDate::getBeginDayEpoch($date_epoch), TTDate::getEndDayEpoch($date_epoch));
        //print_r($punch_arr);
        $this->assertEquals(2, count($punch_arr[$date_epoch]));
        $this->assertEquals($date_epoch, $punch_arr[$date_epoch][0]['date_stamp']);

        $this->assertEquals(4, count($punch_arr[$date_epoch][0]['shift_data']['punches']));
        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][0]['type_id']);
        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][0]['status_id']);

        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][1]['type_id']);
        $this->assertEquals(20, $punch_arr[$date_epoch][0]['shift_data']['punches'][1]['status_id']);

        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][2]['type_id']);
        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][2]['status_id']);

        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][3]['type_id']);
        $this->assertEquals(20, $punch_arr[$date_epoch][0]['shift_data']['punches'][3]['status_id']);

        return true;
    }

    /**
     * @group PunchDetection_testBreakTimeWindowC
     */
    public function testBreakTimeWindowC()
    {
        global $dd;

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $policy_ids['break'][] = $this->createBreakPolicy($this->company_id, 100);

        //Create Policy Group
        $dd->createPolicyGroup($this->company_id,
            null,
            null,
            null,
            null,
            null,
            null,
            array($this->user_id),
            $policy_ids['break']
        );

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $dd->createPunch($this->user_id, 10, 10, strtotime($date_stamp . ' 8:00AM'), array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        //Check all normal punches within the time window of the previous normal punch. This triggered a bug before.
        $punch_time = strtotime($date_stamp . ' 3:30PM');
        $punch_data = $this->getDefaultPunchSettings($punch_time);
        $punch_type_id = $punch_data['type_id'];
        $punch_status_id = $punch_data['status_id'];
        $this->assertEquals($punch_type_id, 10); //Normal
        $this->assertEquals($punch_status_id, 20); //Out
        $dd->createPunch($this->user_id, $punch_type_id, $punch_status_id, $punch_time, array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        $punch_time = strtotime($date_stamp . ' 3:45PM');
        $punch_data = $this->getDefaultPunchSettings($punch_time);
        $punch_type_id = $punch_data['type_id'];
        $punch_status_id = $punch_data['status_id'];
        $this->assertEquals($punch_type_id, 10); //Normal
        $this->assertEquals($punch_status_id, 10); //In
        $dd->createPunch($this->user_id, $punch_type_id, $punch_status_id, $punch_time, array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        $punch_time = strtotime($date_stamp . ' 5:00PM');
        $punch_data = $this->getDefaultPunchSettings($punch_time);
        $punch_type_id = $punch_data['type_id'];
        $punch_status_id = $punch_data['status_id'];
        $this->assertEquals($punch_type_id, 10); //Normal
        $this->assertEquals($punch_status_id, 20); //Out
        $dd->createPunch($this->user_id, $punch_type_id, $punch_status_id, $punch_time, array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        $punch_arr = $this->getPunchDataArray(TTDate::getBeginDayEpoch($date_epoch), TTDate::getEndDayEpoch($date_epoch));
        //print_r($punch_arr);
        $this->assertEquals(2, count($punch_arr[$date_epoch]));
        $this->assertEquals($date_epoch, $punch_arr[$date_epoch][0]['date_stamp']);

        $this->assertEquals(4, count($punch_arr[$date_epoch][0]['shift_data']['punches']));
        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][0]['type_id']);
        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][0]['status_id']);

        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][1]['type_id']);
        $this->assertEquals(20, $punch_arr[$date_epoch][0]['shift_data']['punches'][1]['status_id']);

        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][2]['type_id']);
        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][2]['status_id']);

        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][3]['type_id']);
        $this->assertEquals(20, $punch_arr[$date_epoch][0]['shift_data']['punches'][3]['status_id']);

        return true;
    }

    /**
     * @group PunchDetection_testBreakPunchTimeWindowA
     */
    public function testBreakPunchTimeWindowA()
    {
        global $dd;

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $policy_ids['break'][] = $this->createBreakPolicy($this->company_id, 110);

        //Create Policy Group
        $dd->createPolicyGroup($this->company_id,
            null,
            null,
            null,
            null,
            null,
            null,
            array($this->user_id),
            $policy_ids['break']);

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $dd->createPunch($this->user_id, 10, 10, strtotime($date_stamp . ' 8:00AM'), array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        $punch_time = strtotime($date_stamp . ' 10:00AM');
        $punch_data = $this->getDefaultPunchSettings($punch_time);
        $punch_type_id = $punch_data['type_id'];
        $punch_status_id = $punch_data['status_id'];
        $this->assertEquals($punch_type_id, 10); //Normal - Because when using punch time it can't be detected on the first out punch.
        $this->assertEquals($punch_status_id, 20); //Out
        $dd->createPunch($this->user_id, $punch_type_id, $punch_status_id, $punch_time, array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        $punch_time = strtotime($date_stamp . ' 10:15AM');
        $punch_data = $this->getDefaultPunchSettings($punch_time);
        $punch_type_id = $punch_data['type_id'];
        $punch_status_id = $punch_data['status_id'];
        $this->assertEquals($punch_type_id, 30); //Break
        $this->assertEquals($punch_status_id, 10); //In
        $dd->createPunch($this->user_id, $punch_type_id, $punch_status_id, $punch_time, array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        $punch_time = strtotime($date_stamp . ' 5:00PM');
        $punch_data = $this->getDefaultPunchSettings($punch_time);
        $punch_type_id = $punch_data['type_id'];
        $punch_status_id = $punch_data['status_id'];
        $this->assertEquals($punch_type_id, 10); //Normal
        $this->assertEquals($punch_status_id, 20); //Out
        $dd->createPunch($this->user_id, $punch_type_id, $punch_status_id, $punch_time, array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        $punch_arr = $this->getPunchDataArray(TTDate::getBeginDayEpoch($date_epoch), TTDate::getEndDayEpoch($date_epoch));
        //print_r($punch_arr);
        $this->assertEquals(2, count($punch_arr[$date_epoch]));
        $this->assertEquals($date_epoch, $punch_arr[$date_epoch][0]['date_stamp']);

        $this->assertEquals(4, count($punch_arr[$date_epoch][0]['shift_data']['punches']));
        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][0]['type_id']);
        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][0]['status_id']);

        $this->assertEquals(30, $punch_arr[$date_epoch][0]['shift_data']['punches'][1]['type_id']);
        $this->assertEquals(20, $punch_arr[$date_epoch][0]['shift_data']['punches'][1]['status_id']);

        $this->assertEquals(30, $punch_arr[$date_epoch][0]['shift_data']['punches'][2]['type_id']);
        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][2]['status_id']);

        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][3]['type_id']);
        $this->assertEquals(20, $punch_arr[$date_epoch][0]['shift_data']['punches'][3]['status_id']);

        return true;
    }

    /**
     * @group PunchDetection_testBreakPunchTimeWindowB
     */
    public function testBreakPunchTimeWindowB()
    {
        global $dd;

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $policy_ids['break'][] = $this->createBreakPolicy($this->company_id, 110);

        //Create Policy Group
        $dd->createPolicyGroup($this->company_id,
            null,
            null,
            null,
            null,
            null,
            null,
            array($this->user_id),
            $policy_ids['break']);

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $dd->createPunch($this->user_id, 10, 10, strtotime($date_stamp . ' 8:00AM'), array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        $punch_time = strtotime($date_stamp . ' 10:00AM');
        $punch_data = $this->getDefaultPunchSettings($punch_time);
        $punch_type_id = $punch_data['type_id'];
        $punch_status_id = $punch_data['status_id'];
        $this->assertEquals($punch_type_id, 10); //Normal - Because when using punch time it can't be detected on the first out punch.
        $this->assertEquals($punch_status_id, 20); //Out
        $dd->createPunch($this->user_id, $punch_type_id, $punch_status_id, $punch_time, array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        $punch_time = strtotime($date_stamp . ' 10:45AM');
        $punch_data = $this->getDefaultPunchSettings($punch_time);
        $punch_type_id = $punch_data['type_id'];
        $punch_status_id = $punch_data['status_id'];
        $this->assertEquals($punch_type_id, 10); //Normal
        $this->assertEquals($punch_status_id, 10); //In
        $dd->createPunch($this->user_id, $punch_type_id, $punch_status_id, $punch_time, array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        $punch_time = strtotime($date_stamp . ' 5:00PM');
        $punch_data = $this->getDefaultPunchSettings($punch_time);
        $punch_type_id = $punch_data['type_id'];
        $punch_status_id = $punch_data['status_id'];
        $this->assertEquals($punch_type_id, 10); //Normal
        $this->assertEquals($punch_status_id, 20); //Out
        $dd->createPunch($this->user_id, $punch_type_id, $punch_status_id, $punch_time, array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        $punch_arr = $this->getPunchDataArray(TTDate::getBeginDayEpoch($date_epoch), TTDate::getEndDayEpoch($date_epoch));
        //print_r($punch_arr);
        $this->assertEquals(2, count($punch_arr[$date_epoch]));
        $this->assertEquals($date_epoch, $punch_arr[$date_epoch][0]['date_stamp']);

        $this->assertEquals(4, count($punch_arr[$date_epoch][0]['shift_data']['punches']));
        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][0]['type_id']);
        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][0]['status_id']);

        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][1]['type_id']);
        $this->assertEquals(20, $punch_arr[$date_epoch][0]['shift_data']['punches'][1]['status_id']);

        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][2]['type_id']);
        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][2]['status_id']);

        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][3]['type_id']);
        $this->assertEquals(20, $punch_arr[$date_epoch][0]['shift_data']['punches'][3]['status_id']);

        return true;
    }

    /**
     * @group PunchDetection_testBreakPunchTimeWindowC
     */
    public function testBreakPunchTimeWindowC()
    {
        global $dd;

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $policy_ids['break'][] = $this->createBreakPolicy($this->company_id, 110);

        //Create Policy Group
        $dd->createPolicyGroup($this->company_id,
            null,
            null,
            null,
            null,
            null,
            null,
            array($this->user_id),
            $policy_ids['break']);

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $dd->createPunch($this->user_id, 10, 10, strtotime($date_stamp . ' 8:00AM'), array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        $punch_time = strtotime($date_stamp . ' 10:00AM');
        $punch_data = $this->getDefaultPunchSettings($punch_time);
        $punch_type_id = $punch_data['type_id'];
        $punch_status_id = $punch_data['status_id'];
        $this->assertEquals($punch_type_id, 10); //Normal - Because when using punch time it can't be detected on the first out punch.
        $this->assertEquals($punch_status_id, 20); //Out
        $dd->createPunch($this->user_id, $punch_type_id, $punch_status_id, $punch_time, array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        $punch_time = strtotime($date_stamp . ' 10:03AM');
        $punch_data = $this->getDefaultPunchSettings($punch_time);
        $punch_type_id = $punch_data['type_id'];
        $punch_status_id = $punch_data['status_id'];
        $this->assertEquals($punch_type_id, 10); //Normal
        $this->assertEquals($punch_status_id, 10); //In
        $dd->createPunch($this->user_id, $punch_type_id, $punch_status_id, $punch_time, array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        $punch_time = strtotime($date_stamp . ' 5:00PM');
        $punch_data = $this->getDefaultPunchSettings($punch_time);
        $punch_type_id = $punch_data['type_id'];
        $punch_status_id = $punch_data['status_id'];
        $this->assertEquals($punch_type_id, 10); //Normal
        $this->assertEquals($punch_status_id, 20); //Out
        $dd->createPunch($this->user_id, $punch_type_id, $punch_status_id, $punch_time, array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        $punch_arr = $this->getPunchDataArray(TTDate::getBeginDayEpoch($date_epoch), TTDate::getEndDayEpoch($date_epoch));
        //print_r($punch_arr);
        $this->assertEquals(2, count($punch_arr[$date_epoch]));
        $this->assertEquals($date_epoch, $punch_arr[$date_epoch][0]['date_stamp']);

        $this->assertEquals(4, count($punch_arr[$date_epoch][0]['shift_data']['punches']));
        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][0]['type_id']);
        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][0]['status_id']);

        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][1]['type_id']);
        $this->assertEquals(20, $punch_arr[$date_epoch][0]['shift_data']['punches'][1]['status_id']);

        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][2]['type_id']);
        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][2]['status_id']);

        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][3]['type_id']);
        $this->assertEquals(20, $punch_arr[$date_epoch][0]['shift_data']['punches'][3]['status_id']);

        return true;
    }

    /**
     * @group PunchDetection_testBreakPunchTimeWindowD
     */
    public function testBreakPunchTimeWindowD()
    {
        global $dd;

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $policy_ids['break'][] = $this->createBreakPolicy($this->company_id, 110);

        //Create Policy Group
        $dd->createPolicyGroup($this->company_id,
            null,
            null,
            null,
            null,
            null,
            null,
            array($this->user_id),
            $policy_ids['break']);

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $dd->createPunch($this->user_id, 10, 10, strtotime($date_stamp . ' 8:00AM'), array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        $punch_time = strtotime($date_stamp . ' 10:00AM');
        $punch_data = $this->getDefaultPunchSettings($punch_time);
        $punch_type_id = $punch_data['type_id'];
        $punch_status_id = $punch_data['status_id'];
        $this->assertEquals($punch_type_id, 10); //Normal - Because when using punch time it can't be detected on the first out punch.
        $this->assertEquals($punch_status_id, 20); //Out
        $dd->createPunch($this->user_id, $punch_type_id, $punch_status_id, $punch_time, array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        $punch_time = strtotime($date_stamp . ' 10:15AM');
        $punch_data = $this->getDefaultPunchSettings($punch_time);
        $punch_type_id = $punch_data['type_id'];
        $punch_status_id = $punch_data['status_id'];
        $this->assertEquals($punch_type_id, 30); //Break
        $this->assertEquals($punch_status_id, 10); //In
        $dd->createPunch($this->user_id, $punch_type_id, $punch_status_id, $punch_time, array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        $punch_time = strtotime($date_stamp . ' 2:00PM');
        $punch_data = $this->getDefaultPunchSettings($punch_time);
        $punch_type_id = $punch_data['type_id'];
        $punch_status_id = $punch_data['status_id'];
        $this->assertEquals($punch_type_id, 10); //Normal - Because when using punch time it can't be detected on the first out punch.
        $this->assertEquals($punch_status_id, 20); //Out
        $dd->createPunch($this->user_id, $punch_type_id, $punch_status_id, $punch_time, array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        $punch_time = strtotime($date_stamp . ' 2:15PM');
        $punch_data = $this->getDefaultPunchSettings($punch_time);
        $punch_type_id = $punch_data['type_id'];
        $punch_status_id = $punch_data['status_id'];
        $this->assertEquals($punch_type_id, 30); //Break
        $this->assertEquals($punch_status_id, 10); //In
        $dd->createPunch($this->user_id, $punch_type_id, $punch_status_id, $punch_time, array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        $punch_time = strtotime($date_stamp . ' 5:00PM');
        $punch_data = $this->getDefaultPunchSettings($punch_time);
        $punch_type_id = $punch_data['type_id'];
        $punch_status_id = $punch_data['status_id'];
        $this->assertEquals($punch_type_id, 10); //Normal
        $this->assertEquals($punch_status_id, 20); //Out
        $dd->createPunch($this->user_id, $punch_type_id, $punch_status_id, $punch_time, array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        $punch_arr = $this->getPunchDataArray(TTDate::getBeginDayEpoch($date_epoch), TTDate::getEndDayEpoch($date_epoch));
        //print_r($punch_arr);
        $this->assertEquals(3, count($punch_arr[$date_epoch]));
        $this->assertEquals($date_epoch, $punch_arr[$date_epoch][0]['date_stamp']);

        $this->assertEquals(6, count($punch_arr[$date_epoch][0]['shift_data']['punches']));
        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][0]['type_id']);
        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][0]['status_id']);

        $this->assertEquals(30, $punch_arr[$date_epoch][0]['shift_data']['punches'][1]['type_id']);
        $this->assertEquals(20, $punch_arr[$date_epoch][0]['shift_data']['punches'][1]['status_id']);

        $this->assertEquals(30, $punch_arr[$date_epoch][0]['shift_data']['punches'][2]['type_id']);
        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][2]['status_id']);

        $this->assertEquals(30, $punch_arr[$date_epoch][0]['shift_data']['punches'][3]['type_id']);
        $this->assertEquals(20, $punch_arr[$date_epoch][0]['shift_data']['punches'][3]['status_id']);

        $this->assertEquals(30, $punch_arr[$date_epoch][0]['shift_data']['punches'][4]['type_id']);
        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][4]['status_id']);

        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][5]['type_id']);
        $this->assertEquals(20, $punch_arr[$date_epoch][0]['shift_data']['punches'][5]['status_id']);

        return true;
    }

    /**
     * @group PunchDetection_testZeroNewShiftTriggerMealTimeWindowA
     */
    public function testZeroNewShiftTriggerMealTimeWindowA()
    {
        global $dd;

        $this->createPayPeriodSchedule(10, 0);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $policy_ids['meal'][] = $this->createMealPolicy($this->company_id, 100);

        //Create Policy Group
        $dd->createPolicyGroup($this->company_id,
            $policy_ids['meal'],
            null,
            null,
            null,
            null,
            null,
            array($this->user_id));

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $dd->createPunch($this->user_id, 10, 10, strtotime($date_stamp . ' 8:00AM'), array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        $punch_time = strtotime($date_stamp . ' 12:00PM');
        $punch_data = $this->getDefaultPunchSettings($punch_time);
        $punch_type_id = $punch_data['type_id'];
        $punch_status_id = $punch_data['status_id'];
        $this->assertEquals($punch_type_id, 20); //Lunch
        $this->assertEquals($punch_status_id, 20); //Out
        $dd->createPunch($this->user_id, $punch_type_id, $punch_status_id, $punch_time, array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        $punch_time = strtotime($date_stamp . ' 1:00PM');
        $punch_data = $this->getDefaultPunchSettings($punch_time);
        $punch_type_id = $punch_data['type_id'];
        $punch_status_id = $punch_data['status_id'];
        $this->assertEquals($punch_type_id, 20); //Lunch
        $this->assertEquals($punch_status_id, 10); //In
        $dd->createPunch($this->user_id, $punch_type_id, $punch_status_id, $punch_time, array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        $punch_time = strtotime($date_stamp . ' 5:00PM');
        $punch_data = $this->getDefaultPunchSettings($punch_time);
        $punch_type_id = $punch_data['type_id'];
        $punch_status_id = $punch_data['status_id'];
        $this->assertEquals($punch_type_id, 10); //Normal
        $this->assertEquals($punch_status_id, 20); //Out
        $dd->createPunch($this->user_id, $punch_type_id, $punch_status_id, $punch_time, array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        $punch_arr = $this->getPunchDataArray(TTDate::getBeginDayEpoch($date_epoch), TTDate::getEndDayEpoch($date_epoch));
        //print_r($punch_arr);
        $this->assertEquals(2, count($punch_arr[$date_epoch]));
        $this->assertEquals($date_epoch, $punch_arr[$date_epoch][0]['date_stamp']);

        $this->assertEquals(4, count($punch_arr[$date_epoch][0]['shift_data']['punches']));
        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][0]['type_id']);
        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][0]['status_id']);

        $this->assertEquals(20, $punch_arr[$date_epoch][0]['shift_data']['punches'][1]['type_id']);
        $this->assertEquals(20, $punch_arr[$date_epoch][0]['shift_data']['punches'][1]['status_id']);

        $this->assertEquals(20, $punch_arr[$date_epoch][0]['shift_data']['punches'][2]['type_id']);
        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][2]['status_id']);

        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][3]['type_id']);
        $this->assertEquals(20, $punch_arr[$date_epoch][0]['shift_data']['punches'][3]['status_id']);

        return true;
    }

    /**
     * @group PunchDetection_testZeroNewShiftTriggerTimeMealPunchTimeWindowA
     */
    public function testZeroNewShiftTriggerTimeMealPunchTimeWindowA()
    {
        global $dd;

        $this->createPayPeriodSchedule(10, 0);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $policy_ids['meal'][] = $this->createMealPolicy($this->company_id, 110);

        //Create Policy Group
        $dd->createPolicyGroup($this->company_id,
            $policy_ids['meal'],
            null,
            null,
            null,
            null,
            null,
            array($this->user_id));

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $dd->createPunch($this->user_id, 10, 10, strtotime($date_stamp . ' 8:00AM'), array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        $punch_time = strtotime($date_stamp . ' 12:00PM');
        $punch_data = $this->getDefaultPunchSettings($punch_time);
        $punch_type_id = $punch_data['type_id'];
        $punch_status_id = $punch_data['status_id'];
        $this->assertEquals($punch_type_id, 10); //Normal - Because when using punch time it can't be detected on the first out punch.
        $this->assertEquals($punch_status_id, 20); //Out
        $dd->createPunch($this->user_id, $punch_type_id, $punch_status_id, $punch_time, array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        $punch_time = strtotime($date_stamp . ' 1:00PM');
        $punch_data = $this->getDefaultPunchSettings($punch_time);
        $punch_type_id = $punch_data['type_id'];
        $punch_status_id = $punch_data['status_id'];
        $this->assertEquals($punch_type_id, 20); //Lunch
        $this->assertEquals($punch_status_id, 10); //In
        $dd->createPunch($this->user_id, $punch_type_id, $punch_status_id, $punch_time, array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        $punch_time = strtotime($date_stamp . ' 5:00PM');
        $punch_data = $this->getDefaultPunchSettings($punch_time);
        $punch_type_id = $punch_data['type_id'];
        $punch_status_id = $punch_data['status_id'];
        $this->assertEquals($punch_type_id, 10); //Normal
        $this->assertEquals($punch_status_id, 20); //Out
        $dd->createPunch($this->user_id, $punch_type_id, $punch_status_id, $punch_time, array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        $punch_arr = $this->getPunchDataArray(TTDate::getBeginDayEpoch($date_epoch), TTDate::getEndDayEpoch($date_epoch));
        //print_r($punch_arr);
        $this->assertEquals(2, count($punch_arr[$date_epoch]));
        $this->assertEquals($date_epoch, $punch_arr[$date_epoch][0]['date_stamp']);

        $this->assertEquals(4, count($punch_arr[$date_epoch][0]['shift_data']['punches']));
        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][0]['type_id']);
        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][0]['status_id']);

        $this->assertEquals(20, $punch_arr[$date_epoch][0]['shift_data']['punches'][1]['type_id']);
        $this->assertEquals(20, $punch_arr[$date_epoch][0]['shift_data']['punches'][1]['status_id']);

        $this->assertEquals(20, $punch_arr[$date_epoch][0]['shift_data']['punches'][2]['type_id']);
        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][2]['status_id']);

        $this->assertEquals(10, $punch_arr[$date_epoch][0]['shift_data']['punches'][3]['type_id']);
        $this->assertEquals(20, $punch_arr[$date_epoch][0]['shift_data']['punches'][3]['status_id']);

        return true;
    }
}
