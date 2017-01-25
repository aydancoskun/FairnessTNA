<?php

/**********************************************************************************
 * This file is part of "FairnessTNA", a Payroll and Time Management program.
 * FairnessTNA is copyright 2013-2017 Aydan Coskun (aydan.ayfer.coskun@gmail.com)
 * others. For full attribution and copyrights details see the COPYRIGHT file.
 *
 * FairnessTNA is free software; you can redistribute it and/or modify it under the
 * terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation, either version 3 of the License, or (at you option )
 * any later version.
 *
 * FairnessTNA is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE.  See the GNU Affero General Public License for more
 * details.
 *
 * You should have received a copy of the GNU Affero General Public License along
 * with this program; if not, see http://www.gnu.org/licenses or write to the Free
 * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301 USA.
 *********************************************************************************/
class PunchTest extends PHPUnit_Framework_TestCase
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
        $this->assertGreaterThan(0, $this->company_id);

        $dd->createPermissionGroups($this->company_id, 40); //Administrator only.

        $dd->createCurrency($this->company_id, 10);

        $this->branch_id = $dd->createBranch($this->company_id, 10); //NY

        $dd->createPayStubAccount($this->company_id);
        $this->createPayStubAccounts();
        //$this->createPayStubAccrualAccount();
        $dd->createPayStubAccountLink($this->company_id);
        $this->getPayStubAccountLinkArray();

        $dd->createUserWageGroups($this->company_id);

        $this->policy_ids['pay_formula_policy'][100] = $dd->createPayFormulaPolicy($this->company_id, 100); //Reg 1.0x
        $this->policy_ids['pay_code'][100] = $dd->createPayCode($this->company_id, 100, $this->policy_ids['pay_formula_policy'][100]); //Regular

        $this->user_id = $dd->createUser($this->company_id, 100);

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

    /**
     * @group Punch_testDayShiftStartsBasicA
     */
    public function testDayShiftStartsBasicA()
    {
        global $dd;

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

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
        //Total Time
        //$this->assertEquals( 10, $udt_arr[$date_epoch][0]['status_id'] );
        //$this->assertEquals( 10, $udt_arr[$date_epoch][0]['type_id'] );
        $this->assertEquals(5, $udt_arr[$date_epoch][0]['object_type_id']);
        $this->assertEquals((7 * 3600), $udt_arr[$date_epoch][0]['total_time']);

        return true;
    }

    public function createPayPeriodSchedule($shift_assigned_day = 10, $maximum_shift_time = 57600, $new_shift_trigger_time = 14400)
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
        $ppsf->setMaximumShiftTime($maximum_shift_time);
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

    public function getUserDateTotalArray($start_date, $end_date)
    {
        $udtlf = new UserDateTotalListFactory();

        $date_totals = array();

        //Get only system totals.
        $udtlf->getByCompanyIDAndUserIdAndObjectTypeAndStartDateAndEndDate($this->company_id, $this->user_id, array(5, 20, 30, 40, 100, 110), $start_date, $end_date);
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

    /**
     * @group Punch_testDayShiftStartsBasicB
     */
    public function testDayShiftStartsBasicB()
    {
        global $dd;

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 7:00AM'),
            strtotime($date_stamp . ' 11:00AM'),
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

        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 2:00PM'),
            strtotime($date_stamp . ' 6:00PM'),
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
        $this->assertEquals(2, count($punch_arr[$date_epoch]));
        $this->assertEquals($date_epoch, $punch_arr[$date_epoch][0]['date_stamp']);
        $this->assertEquals($date_epoch, $punch_arr[$date_epoch][1]['date_stamp']);

        $this->assertEquals($punch_arr[$date_epoch][0]['shift_data']['punches'][0]['punch_control_id'], $punch_arr[$date_epoch][1]['shift_data']['punches'][0]['punch_control_id']); //Make sure punch_control_id from both shifts DO match.


        $udt_arr = $this->getUserDateTotalArray($date_epoch, $date_epoch);
        //Total Time
        $this->assertEquals(5, $udt_arr[$date_epoch][0]['object_type_id']);
        $this->assertEquals((8 * 3600), $udt_arr[$date_epoch][0]['total_time']);

        return true;
    }

    /**
     * @group Punch_testDayShiftStartsBasicC
     */
    public function testDayShiftStartsBasicC()
    {
        global $dd;

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 7:00AM'),
            strtotime($date_stamp . ' 11:00AM'),
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

        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 4:00PM'),
            strtotime($date_stamp . ' 8:00PM'),
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
        $this->assertEquals(2, count($punch_arr[$date_epoch]));
        $this->assertEquals($date_epoch, $punch_arr[$date_epoch][0]['date_stamp']);
        $this->assertEquals($date_epoch, $punch_arr[$date_epoch][1]['date_stamp']);

        $this->assertEquals(2, count($punch_arr[$date_epoch][0]['shift_data']['punches'])); //Make sure there are only two punches per shift.
        $this->assertEquals(2, count($punch_arr[$date_epoch][1]['shift_data']['punches'])); //Make sure there are only two punches per shift.

        $this->assertNotEquals($punch_arr[$date_epoch][0]['shift_data']['punches'][0]['punch_control_id'], $punch_arr[$date_epoch][1]['shift_data']['punches'][0]['punch_control_id']); //Make sure punch_control_id from both shifts don't match.

        $udt_arr = $this->getUserDateTotalArray($date_epoch, $date_epoch);
        //Total Time
        $this->assertEquals(5, $udt_arr[$date_epoch][0]['object_type_id']);
        $this->assertEquals((8 * 3600), $udt_arr[$date_epoch][0]['total_time']);

        return true;
    }

    /**
     * @group Punch_testDayShiftStartsBasicD
     */
    public function testDayShiftStartsBasicD()
    {
        global $dd;

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $date_epoch2 = TTDate::getBeginDayEpoch((TTDate::getBeginWeekEpoch(time()) + 86400 + 3600));
        $date_stamp2 = TTDate::getDate('DATE', $date_epoch2);

        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 11:00PM'),
            strtotime($date_stamp2 . ' 6:00AM'),
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

        $punch_arr = $this->getPunchDataArray(TTDate::getBeginDayEpoch($date_epoch), TTDate::getEndDayEpoch($date_epoch2));
        //print_r($punch_arr);
        $this->assertEquals(1, count($punch_arr[$date_epoch]));
        $this->assertEquals($date_epoch, $punch_arr[$date_epoch][0]['date_stamp']);


        $udt_arr = $this->getUserDateTotalArray($date_epoch, $date_epoch2);
        //Total Time
        $this->assertEquals(5, $udt_arr[$date_epoch][0]['object_type_id']);
        $this->assertEquals((7 * 3600), $udt_arr[$date_epoch][0]['total_time']);

        return true;
    }

    /*
     Tests:
        [PP Schedule Assigns shift to day they start on]
        - Basic In/Out punch in the middle of the day
        - Basic split shift in the middle of the day, with 3hr gap between them. (single shift)
        - Basic split shift in the middle of the day, with 6hr gap between them. (double shift)
        - In at 11:00PM on one day, and out at 2PM on another day.
        - In at 8PM, lunch out at 11:30PM, lunch in at 12:30 (next day), Out at 4AM. (single shift)

        [PP Schedule Assigns shift to day they end on]
        - In at 11:00PM on one day, and out at 2PM on another day.
        - In at 8PM, lunch out at 11:30PM, lunch in at 12:30 (next day), Out at 4AM. (single shift)

        - Advanced:
            - Many punches at the end of a day. Test to make sure they are on a specific date.
                Add more punches on the next day, test to make sure all punches in the shift change date.

        [PP Schedule Assigns shift to day they work most on]
        - In at 11:00PM on one day, and out at 2PM on another day.
        - In at 8PM, lunch out at 11:30PM, lunch in at 12:30 (next day), Out at 4AM. (single shift)

        [PP Schedule Split at midnight]
        - In at 11:00PM on one day, and out at 2PM on another day.
        - In at 8PM, lunch out at 11:30PM, lunch in at 12:30 (next day), Out at 4AM. (two shifts)
        - In at 8PM, lunch out at 10:30PM, lunch in at 11:30PM , Out at 4AM. (single shift)
        - In at exactly midnight, out at 8AM (one-shift)
        - In at 8PM out at exactly midnight (one-shift)

        - Advanced:
            - Many punches at the end of a day. Test to make sure they are on a specific date.
                Add more punches on the next day, test to make sure all punches in the shift change date.

        - Test punch control matching, but adding single punches at a time. Then across day boundaries
            then outside the new_shift_trigger time.


        - Test editing and deleting punches.
            - Basic Editing, changing the time by one hour.
                - Changing the In punch to a different day, causing the entire shift to be moved.

            - Deleting basic punches
            - Deleting punches that affect which day the shift falls on.


        - Validation tests:
            - Changing the in time to AFTER the out time. Check for validation error.
            - Changing the out time to BEFORE the in time. Check for validation error.
            - Trying to add punch inbetween two other existing punches in a pair.
            - Trying to add punch inbetween two other existing punches NOT in a pair, but that don't have any time between them (transfer punch)
            - Two punches of the same date/time but different status should succeed (transfer punches)
            - Two punches of the same date/time in same punch pair. Should fail.
            - Two punches of the same status/date/time in different punch pair. Should fail.

            - Test punch rounding, specifically lunch,break,day total rounding.
        --------------------------- DONE ABOVE THIS LINE --------------------------

        - Make sure we can't assign a punch to some random punch_control_id for another user/company.
    */

    /**
     * @group Punch_testDayShiftStartsBasicE
     */
    public function testDayShiftStartsBasicE()
    {
        global $dd;

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $date_epoch2 = TTDate::getBeginDayEpoch((TTDate::getBeginWeekEpoch(time()) + 86400 + 3600));
        $date_stamp2 = TTDate::getDate('DATE', $date_epoch2);

        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 8:00PM'),
            strtotime($date_stamp . ' 11:30PM'), //Lunch Out
            array(
                'in_type_id' => 10,
                'out_type_id' => 20,
                'branch_id' => 0,
                'department_id' => 0,
                'job_id' => 0,
                'job_item_id' => 0,
            ),
            true
        );

        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp2 . ' 12:30AM'),
            strtotime($date_stamp2 . ' 4:00AM'), //Lunch Out
            array(
                'in_type_id' => 20,
                'out_type_id' => 10,
                'branch_id' => 0,
                'department_id' => 0,
                'job_id' => 0,
                'job_item_id' => 0,
            ),
            true
        );

        $punch_arr = $this->getPunchDataArray(TTDate::getBeginDayEpoch($date_epoch), TTDate::getEndDayEpoch($date_epoch2));
        //print_r($punch_arr);
        $this->assertEquals(2, count($punch_arr[$date_epoch]));
        $this->assertEquals($date_epoch, $punch_arr[$date_epoch][0]['date_stamp']);
        $this->assertEquals($date_epoch, $punch_arr[$date_epoch][1]['date_stamp']);

        $this->assertEquals($punch_arr[$date_epoch][0]['shift_data']['punches'][0]['punch_control_id'], $punch_arr[$date_epoch][1]['shift_data']['punches'][0]['punch_control_id']); //Make sure punch_control_id from both shifts DO match.

        $udt_arr = $this->getUserDateTotalArray($date_epoch, $date_epoch2);
        //Total Time
        $this->assertEquals(5, $udt_arr[$date_epoch][0]['object_type_id']);
        $this->assertEquals((7 * 3600), $udt_arr[$date_epoch][0]['total_time']);

        return true;
    }

    /**
     * @group Punch_testDayShiftStartsBasicF
     */
    public function testDayShiftStartsBasicF()
    {
        //Special test to fix a bug when there is a 2hr gap between punches, but a new shift is only triggered after 4hrs.
        global $dd;

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 10:00AM'),
            strtotime($date_stamp . ' 2:00PM'),
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

        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 4:00PM'),
            strtotime($date_stamp . ' 8:00PM'),
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

        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 8:00PM'),
            strtotime($date_stamp . ' 9:00PM'),
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
        $this->assertEquals(3, count($punch_arr[$date_epoch]));
        $this->assertEquals($date_epoch, $punch_arr[$date_epoch][0]['date_stamp']);
        $this->assertEquals($date_epoch, $punch_arr[$date_epoch][1]['date_stamp']);
        $this->assertEquals($date_epoch, $punch_arr[$date_epoch][2]['date_stamp']);

        $this->assertEquals($punch_arr[$date_epoch][0]['shift_data']['punches'][0]['punch_control_id'], $punch_arr[$date_epoch][1]['shift_data']['punches'][0]['punch_control_id']); //Make sure punch_control_id from both shifts DO match.
        $this->assertEquals($punch_arr[$date_epoch][1]['shift_data']['punches'][0]['punch_control_id'], $punch_arr[$date_epoch][2]['shift_data']['punches'][0]['punch_control_id']); //Make sure punch_control_id from both shifts DO match.


        $udt_arr = $this->getUserDateTotalArray($date_epoch, $date_epoch);
        //Total Time
        $this->assertEquals(5, $udt_arr[$date_epoch][0]['object_type_id']);
        $this->assertEquals((9 * 3600), $udt_arr[$date_epoch][0]['total_time']);

        return true;
    }

    /**
     * @group Punch_testDayShiftStartsBasicG
     */
    public function testDayShiftStartsBasicG()
    {
        //Special test to handle 24hr shifts with no gaps between them for multiple days. (such as fire fighters/live-in care homes)
        global $dd;

        $this->createPayPeriodSchedule(10, (25 * 3600), 0); //NewShiftTriggerTime=0
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);
        $date_epoch2 = TTDate::getBeginDayEpoch(TTDate::getBeginWeekEpoch(time()) + (1 * 86400 + 3601));
        $date_stamp2 = TTDate::getDate('DATE', $date_epoch2);

        $date_epoch3 = TTDate::getBeginDayEpoch(TTDate::getBeginWeekEpoch(time()) + (2 * 86400 + 3601));
        $date_stamp3 = TTDate::getDate('DATE', $date_epoch3);
        $date_epoch4 = TTDate::getBeginDayEpoch(TTDate::getBeginWeekEpoch(time()) + (3 * 86400 + 3601));
        $date_stamp4 = TTDate::getDate('DATE', $date_epoch4);

        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 3:00PM'),
            strtotime($date_stamp2 . ' 3:00PM'),
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

        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp2 . ' 3:00PM'),
            strtotime($date_stamp3 . ' 3:00PM'),
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

        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp3 . ' 3:00PM'),
            strtotime($date_stamp4 . ' 3:00PM'),
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

        //Date 1
        $punch_arr = $this->getPunchDataArray(TTDate::getBeginDayEpoch($date_epoch), TTDate::getEndDayEpoch($date_epoch));
        //print_r($punch_arr);
        $this->assertEquals(1, count($punch_arr[$date_epoch]));
        $this->assertEquals($date_epoch, $punch_arr[$date_epoch][0]['date_stamp']);
        $this->assertEquals($punch_arr[$date_epoch][0]['shift_data']['punches'][0]['punch_control_id'], $punch_arr[$date_epoch][0]['shift_data']['punches'][1]['punch_control_id']); //Make sure punch_control_id from both shifts DO match.

        $udt_arr = $this->getUserDateTotalArray($date_epoch, $date_epoch);
        //Total Time
        $this->assertEquals(5, $udt_arr[$date_epoch][0]['object_type_id']);
        $this->assertEquals((24 * 3600), $udt_arr[$date_epoch][0]['total_time']);

        //Date 2
        $punch_arr = $this->getPunchDataArray(TTDate::getBeginDayEpoch($date_epoch2), TTDate::getEndDayEpoch($date_epoch2));
        //print_r($punch_arr);
        $this->assertEquals(1, count($punch_arr[$date_epoch2]));
        $this->assertEquals($date_epoch2, $punch_arr[$date_epoch2][0]['date_stamp']);
        $this->assertEquals($punch_arr[$date_epoch2][0]['shift_data']['punches'][0]['punch_control_id'], $punch_arr[$date_epoch2][0]['shift_data']['punches'][1]['punch_control_id']); //Make sure punch_control_id from both shifts DO match.

        $udt_arr = $this->getUserDateTotalArray($date_epoch2, $date_epoch2);
        //Total Time
        $this->assertEquals(5, $udt_arr[$date_epoch2][0]['object_type_id']);
        $this->assertEquals((24 * 3600), $udt_arr[$date_epoch2][0]['total_time']);

        //Date 3
        $punch_arr = $this->getPunchDataArray(TTDate::getBeginDayEpoch($date_epoch3), TTDate::getEndDayEpoch($date_epoch3));
        //print_r($punch_arr);
        $this->assertEquals(1, count($punch_arr[$date_epoch3]));
        $this->assertEquals($date_epoch3, $punch_arr[$date_epoch3][0]['date_stamp']);
        $this->assertEquals($punch_arr[$date_epoch3][0]['shift_data']['punches'][0]['punch_control_id'], $punch_arr[$date_epoch3][0]['shift_data']['punches'][1]['punch_control_id']); //Make sure punch_control_id from both shifts DO match.

        $udt_arr = $this->getUserDateTotalArray($date_epoch3, $date_epoch3);
        //Total Time
        $this->assertEquals(5, $udt_arr[$date_epoch3][0]['object_type_id']);
        $this->assertEquals((24 * 3600), $udt_arr[$date_epoch3][0]['total_time']);

        return true;
    }

    /**
     * @group Punch_testDayShiftEndsBasicA
     */
    public function testDayShiftEndsBasicA()
    {
        //  Test when shifts are assigned to the day they end on.
        global $dd;

        $this->createPayPeriodSchedule(20);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

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
        //Total Time
        $this->assertEquals(5, $udt_arr[$date_epoch][0]['object_type_id']);
        $this->assertEquals((7 * 3600), $udt_arr[$date_epoch][0]['total_time']);

        return true;
    }

    /**
     * @group Punch_testDayShiftEndsBasicB
     */
    public function testDayShiftEndsBasicB()
    {
        global $dd;

        $this->createPayPeriodSchedule(20);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $date_epoch2 = TTDate::getBeginDayEpoch((TTDate::getBeginWeekEpoch(time()) + 86400 + 3600));
        $date_stamp2 = TTDate::getDate('DATE', $date_epoch2);

        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 11:00PM'),
            strtotime($date_stamp2 . ' 6:00AM'),
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

        $punch_arr = $this->getPunchDataArray(TTDate::getBeginDayEpoch($date_epoch), TTDate::getEndDayEpoch($date_epoch2));

        $date_epoch2 = TTDate::getBeginDayEpoch($date_epoch2); //This accounts for DST.
        $this->assertEquals(1, count($punch_arr[$date_epoch2]));
        $this->assertEquals($date_epoch2, $punch_arr[$date_epoch2][0]['date_stamp']);


        $udt_arr = $this->getUserDateTotalArray($date_epoch, $date_epoch2);
        //Total Time
        //$this->assertEquals( 10, $udt_arr[$date_epoch2][0]['status_id'] );
        //$this->assertEquals( 10, $udt_arr[$date_epoch2][0]['type_id'] );
        $this->assertEquals(5, $udt_arr[$date_epoch2][0]['object_type_id']);
        $this->assertEquals((7 * 3600), $udt_arr[$date_epoch2][0]['total_time']);

        return true;
    }

    /**
     * @group Punch_testDayShiftEndsBasicC
     */
    public function testDayShiftEndsBasicC()
    {
        global $dd;

        $this->createPayPeriodSchedule(20);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $date_epoch2 = TTDate::getBeginDayEpoch((TTDate::getBeginWeekEpoch(time()) + 86400 + 3600));
        $date_stamp2 = TTDate::getDate('DATE', $date_epoch2);

        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 8:00PM'),
            strtotime($date_stamp . ' 11:30PM'), //Lunch Out
            array(
                'in_type_id' => 10,
                'out_type_id' => 20,
                'branch_id' => 0,
                'department_id' => 0,
                'job_id' => 0,
                'job_item_id' => 0,
            ),
            true
        );

        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp2 . ' 12:30AM'),
            strtotime($date_stamp2 . ' 4:00AM'), //Lunch Out
            array(
                'in_type_id' => 20,
                'out_type_id' => 10,
                'branch_id' => 0,
                'department_id' => 0,
                'job_id' => 0,
                'job_item_id' => 0,
            ),
            true
        );

        $punch_arr = $this->getPunchDataArray(TTDate::getBeginDayEpoch($date_epoch), TTDate::getEndDayEpoch($date_epoch2));
        //print_r($punch_arr);
        $date_epoch2 = TTDate::getBeginDayEpoch($date_epoch2); //This accounts for DST.

        $this->assertEquals(2, count($punch_arr[$date_epoch2]));
        $this->assertEquals($date_epoch2, $punch_arr[$date_epoch2][0]['date_stamp']);
        $this->assertEquals($date_epoch2, $punch_arr[$date_epoch2][1]['date_stamp']);

        $this->assertEquals($punch_arr[$date_epoch2][0]['shift_data']['punches'][0]['punch_control_id'], $punch_arr[$date_epoch2][1]['shift_data']['punches'][0]['punch_control_id']); //Make sure punch_control_id from both shifts DO match.

        $udt_arr = $this->getUserDateTotalArray($date_epoch, $date_epoch2);
        //Total Time
        //$this->assertEquals( 10, $udt_arr[$date_epoch2][0]['status_id'] );
        //$this->assertEquals( 10, $udt_arr[$date_epoch2][0]['type_id'] );
        $this->assertEquals(5, $udt_arr[$date_epoch2][0]['object_type_id']);
        $this->assertEquals((7 * 3600), $udt_arr[$date_epoch2][0]['total_time']);

        return true;
    }

    /**
     * @group Punch_testDayMostWorkedBasicA
     */
    public function testDayMostWorkedBasicA()
    {
        //  Test when shifts are assigned to the day most worked on.
        global $dd;

        $this->createPayPeriodSchedule(30);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

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
        //Total Time
        $this->assertEquals(5, $udt_arr[$date_epoch][0]['object_type_id']);
        $this->assertEquals((7 * 3600), $udt_arr[$date_epoch][0]['total_time']);

        return true;
    }

    /**
     * @group Punch_testDayMostWorkedBasicB
     */
    public function testDayMostWorkedBasicB()
    {
        global $dd;

        $this->createPayPeriodSchedule(30);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $date_epoch2 = TTDate::getBeginDayEpoch((TTDate::getBeginWeekEpoch(time()) + 86400 + 3600));
        $date_stamp2 = TTDate::getDate('DATE', $date_epoch2);

        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 6:00PM'),
            strtotime($date_stamp2 . ' 1:00AM'),
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

        $punch_arr = $this->getPunchDataArray(TTDate::getBeginDayEpoch($date_epoch), TTDate::getEndDayEpoch($date_epoch2));
        //print_r($punch_arr);
        $this->assertEquals(1, count($punch_arr[$date_epoch]));
        $this->assertEquals($date_epoch, $punch_arr[$date_epoch][0]['date_stamp']);


        $udt_arr = $this->getUserDateTotalArray($date_epoch, $date_epoch2);
        //Total Time
        $this->assertEquals(5, $udt_arr[$date_epoch][0]['object_type_id']);
        $this->assertEquals((7 * 3600), $udt_arr[$date_epoch][0]['total_time']);

        return true;
    }

    /**
     * @group Punch_testDayMostWorkedBasicC
     */
    public function testDayMostWorkedBasicC()
    {
        global $dd;

        $this->createPayPeriodSchedule(30);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $date_epoch2 = TTDate::getBeginDayEpoch((TTDate::getBeginWeekEpoch(time()) + 86400 + 3600));
        $date_stamp2 = TTDate::getDate('DATE', $date_epoch2);

        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 11:00PM'),
            strtotime($date_stamp2 . ' 6:00AM'),
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

        $punch_arr = $this->getPunchDataArray(TTDate::getBeginDayEpoch($date_epoch), TTDate::getEndDayEpoch($date_epoch2));
        //print_r($punch_arr);

        $date_epoch = TTDate::getBeginDayEpoch($date_epoch); //This accounts for DST.
        $date_epoch2 = TTDate::getBeginDayEpoch($date_epoch2); //This accounts for DST.
        $this->assertEquals(1, count($punch_arr[$date_epoch2]));
        $this->assertEquals($date_epoch2, $punch_arr[$date_epoch2][0]['date_stamp']);


        $udt_arr = $this->getUserDateTotalArray($date_epoch, $date_epoch2);
        //Total Time
        //$this->assertEquals( 10, $udt_arr[$date_epoch2][0]['status_id'] );
        //$this->assertEquals( 10, $udt_arr[$date_epoch2][0]['type_id'] );
        $this->assertEquals(5, $udt_arr[$date_epoch2][0]['object_type_id']);
        $this->assertEquals((7 * 3600), $udt_arr[$date_epoch2][0]['total_time']);

        return true;
    }

    /**
     * @group Punch_testDayMostWorkedBasicD
     */
    public function testDayMostWorkedBasicD()
    {
        global $dd;

        $this->createPayPeriodSchedule(30);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $date_epoch2 = TTDate::getBeginDayEpoch((TTDate::getBeginWeekEpoch(time()) + 86400 + 3600));
        $date_stamp2 = TTDate::getDate('DATE', $date_epoch2);

        //First punch pair
        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 8:30PM'),
            strtotime($date_stamp . ' 11:30PM'), //Lunch Out
            array(
                'in_type_id' => 10,
                'out_type_id' => 20,
                'branch_id' => 0,
                'department_id' => 0,
                'job_id' => 0,
                'job_item_id' => 0,
            ),
            true
        );

        $punch_arr = $this->getPunchDataArray(TTDate::getBeginDayEpoch($date_epoch), TTDate::getEndDayEpoch($date_epoch2));
        //print_r($punch_arr);
        $this->assertEquals(1, count($punch_arr[$date_epoch]));
        $this->assertEquals($date_epoch, $punch_arr[$date_epoch][0]['date_stamp']);

        $udt_arr = $this->getUserDateTotalArray($date_epoch, $date_epoch2);
        //Total Time
        $this->assertEquals(5, $udt_arr[$date_epoch][0]['object_type_id']);
        $this->assertEquals((3 * 3600), $udt_arr[$date_epoch][0]['total_time']);

        //Second punch pair
        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp2 . ' 12:30AM'),
            strtotime($date_stamp2 . ' 4:30AM'), //Lunch Out
            array(
                'in_type_id' => 20,
                'out_type_id' => 10,
                'branch_id' => 0,
                'department_id' => 0,
                'job_id' => 0,
                'job_item_id' => 0,
            ),
            true
        );

        $punch_arr = $this->getPunchDataArray(TTDate::getBeginDayEpoch($date_epoch), TTDate::getEndDayEpoch($date_epoch2));
        //print_r($punch_arr);
        $date_epoch = TTDate::getBeginDayEpoch($date_epoch); //This accounts for DST.
        $date_epoch2 = TTDate::getBeginDayEpoch($date_epoch2); //This accounts for DST.

        $this->assertEquals(2, count($punch_arr[$date_epoch2]));
        $this->assertEquals($date_epoch2, $punch_arr[$date_epoch2][0]['date_stamp']);

        $this->assertEquals($punch_arr[$date_epoch2][0]['shift_data']['punches'][0]['punch_control_id'], $punch_arr[$date_epoch2][1]['shift_data']['punches'][0]['punch_control_id']); //Make sure punch_control_id from both shifts DO match.

        $udt_arr = $this->getUserDateTotalArray($date_epoch, $date_epoch2);

        //Total Time
        //$this->assertEquals( 5, $udt_arr[$date_epoch][0]['object_type_id'] ); //Since we don't save UDT records where total_time=0, don't check this anymore.
        //Instead check to make sure no records on that date exist at all.
        if (isset($udt_arr[$date_epoch][0]['object_type_id'])) {
            $this->assertTrue(false);
        } else {
            $this->assertTrue(true);
        }

        $this->assertEquals(5, $udt_arr[$date_epoch2][0]['object_type_id']);
        $this->assertEquals((7 * 3600), $udt_arr[$date_epoch2][0]['total_time']);

        return true;
    }

    /**
     * @group Punch_testSplitAtMidnightBasicA
     */
    public function testSplitAtMidnightBasicA()
    {
        //  Test when shifts are split at midnight.
        global $dd;

        $this->createPayPeriodSchedule(40);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

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
        //Total Time
        $this->assertEquals(5, $udt_arr[$date_epoch][0]['object_type_id']);
        $this->assertEquals((7 * 3600), $udt_arr[$date_epoch][0]['total_time']);

        return true;
    }

    /**
     * @group Punch_testSplitAtMidnightBasicB
     */
    public function testSplitAtMidnightBasicB()
    {
        global $dd;

        $this->createPayPeriodSchedule(40);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $date_epoch2 = TTDate::getBeginDayEpoch((TTDate::getBeginWeekEpoch(time()) + 86400 + 3600));
        $date_stamp2 = TTDate::getDate('DATE', $date_epoch2);

        //Need to create the punches separately as createPunchPair won't split the punches.
        $dd->createPunch($this->user_id, 10, 10, strtotime($date_stamp . ' 6:00PM'), array('branch_id' => $this->branch_id, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);
        $dd->createPunch($this->user_id, 10, 20, strtotime($date_stamp2 . ' 2:00AM'), array('branch_id' => $this->branch_id, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        $punch_arr = $this->getPunchDataArray(TTDate::getBeginDayEpoch($date_epoch), TTDate::getEndDayEpoch($date_epoch2));
        //print_r($punch_arr);

        //Make sure branch is the same across both punches
        $this->assertEquals($punch_arr[$date_epoch][0]['branch_id'], $punch_arr[$date_epoch2][1]['branch_id']);


        //Date 1
        $this->assertEquals(1, count($punch_arr[$date_epoch]));
        $this->assertEquals($date_epoch, $punch_arr[$date_epoch][0]['date_stamp']);

        //Date 2
        $this->assertEquals(1, count($punch_arr[$date_epoch2]));
        $this->assertEquals($date_epoch2, $punch_arr[$date_epoch2][1]['date_stamp']);


        $udt_arr = $this->getUserDateTotalArray($date_epoch, $date_epoch2);
        //Total Time - Date 1
        $this->assertEquals(5, $udt_arr[$date_epoch][0]['object_type_id']);
        $this->assertEquals((6 * 3600), $udt_arr[$date_epoch][0]['total_time']);

        //Total Time - Date 2
        //$this->assertEquals( 10, $udt_arr[$date_epoch2][0]['status_id'] );
        //$this->assertEquals( 10, $udt_arr[$date_epoch2][0]['type_id'] );
        $this->assertEquals(5, $udt_arr[$date_epoch2][0]['object_type_id']);
        $this->assertEquals((2 * 3600), $udt_arr[$date_epoch2][0]['total_time']);

        return true;
    }

    /**
     * @group Punch_testSplitAtMidnightBasicC
     */
    public function testSplitAtMidnightBasicC()
    {
        global $dd;

        $this->createPayPeriodSchedule(40);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $date_epoch2 = TTDate::getBeginDayEpoch((TTDate::getBeginWeekEpoch(time()) + 86400 + 3600));
        $date_stamp2 = TTDate::getDate('DATE', $date_epoch2);

        //Need to create the punches separately as createPunchPair won't split the punches.
        $dd->createPunch($this->user_id, 10, 10, strtotime($date_stamp . ' 5:00PM'), array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);
        $dd->createPunch($this->user_id, 20, 20, strtotime($date_stamp . ' 10:30PM'), array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);
        $dd->createPunch($this->user_id, 20, 10, strtotime($date_stamp . ' 11:30PM'), array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);
        $dd->createPunch($this->user_id, 10, 20, strtotime($date_stamp2 . ' 2:00AM'), array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        $punch_arr = $this->getPunchDataArray(TTDate::getBeginDayEpoch($date_epoch), TTDate::getEndDayEpoch($date_epoch2));
        //print_r($punch_arr);

        //Date 1
        $this->assertEquals(2, count($punch_arr[$date_epoch]));
        $this->assertEquals($date_epoch, $punch_arr[$date_epoch][0]['date_stamp']);

        //Date 2
        $this->assertEquals(1, count($punch_arr[$date_epoch2]));
        $this->assertEquals($date_epoch2, $punch_arr[$date_epoch2][2]['date_stamp']);


        $udt_arr = $this->getUserDateTotalArray($date_epoch, $date_epoch2);
        //Total Time - Date 1
        $this->assertEquals(5, $udt_arr[$date_epoch][0]['object_type_id']);
        $this->assertEquals((6 * 3600), $udt_arr[$date_epoch][0]['total_time']);

        //Total Time - Date 2
        $this->assertEquals(5, $udt_arr[$date_epoch2][0]['object_type_id']);
        $this->assertEquals((2 * 3600), $udt_arr[$date_epoch2][0]['total_time']);

        return true;
    }

    /**
     * @group Punch_testSplitAtMidnightBasicD
     */
    public function testSplitAtMidnightBasicD()
    {
        global $dd;

        $this->createPayPeriodSchedule(40);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $date_epoch2 = TTDate::getBeginDayEpoch((TTDate::getBeginWeekEpoch(time()) + 86400 + 3600));
        $date_stamp2 = TTDate::getDate('DATE', $date_epoch2);

        //Need to create the punches separately as createPunchPair won't split the punches.
        $dd->createPunch($this->user_id, 10, 10, strtotime($date_stamp . ' 5:30PM'), array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);
        $dd->createPunch($this->user_id, 20, 20, strtotime($date_stamp . ' 11:30PM'), array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);
        $dd->createPunch($this->user_id, 20, 10, strtotime($date_stamp2 . ' 12:30AM'), array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);
        $dd->createPunch($this->user_id, 10, 20, strtotime($date_stamp2 . ' 2:30AM'), array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        $punch_arr = $this->getPunchDataArray(TTDate::getBeginDayEpoch($date_epoch), TTDate::getEndDayEpoch($date_epoch2));
        //print_r($punch_arr);

        //Date 1
        $this->assertEquals(2, count($punch_arr[$date_epoch]));
        $this->assertEquals($date_epoch, $punch_arr[$date_epoch][0]['date_stamp']);

        $udt_arr = $this->getUserDateTotalArray($date_epoch, $date_epoch2);
        //Total Time - Date 1
        $this->assertEquals(5, $udt_arr[$date_epoch][0]['object_type_id']);
        $this->assertEquals((8 * 3600), $udt_arr[$date_epoch][0]['total_time']);

        return true;
    }

    /**
     * @group Punch_testSplitAtMidnightBasicE
     */
    public function testSplitAtMidnightBasicE()
    {
        global $dd;

        $this->createPayPeriodSchedule(40);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $date_epoch2 = TTDate::getBeginDayEpoch((TTDate::getBeginWeekEpoch(time()) + 86400 + 3600));
        $date_stamp2 = TTDate::getDate('DATE', $date_epoch2);

        //Need to create the punches separately as createPunchPair won't split the punches.
        $dd->createPunch($this->user_id, 10, 10, strtotime($date_stamp . ' 4:00PM'), array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);
        $dd->createPunch($this->user_id, 10, 20, strtotime($date_stamp2 . ' 12:00AM'), array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        $punch_arr = $this->getPunchDataArray(TTDate::getBeginDayEpoch($date_epoch), TTDate::getEndDayEpoch($date_epoch2));
        //print_r($punch_arr);

        //Date 1
        $this->assertEquals(1, count($punch_arr[$date_epoch]));
        $this->assertEquals($date_epoch, $punch_arr[$date_epoch][0]['date_stamp']);

        $udt_arr = $this->getUserDateTotalArray($date_epoch, $date_epoch2);
        //Total Time - Date 1
        $this->assertEquals(5, $udt_arr[$date_epoch][0]['object_type_id']);
        $this->assertEquals((8 * 3600), $udt_arr[$date_epoch][0]['total_time']);

        return true;
    }

    /**
     * @group Punch_testSplitAtMidnightBasicF
     */
    public function testSplitAtMidnightBasicF()
    {
        global $dd;

        $this->createPayPeriodSchedule(40);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $date_epoch = TTDate::getBeginWeekEpoch(time(), 1); //Start weeks on Monday so DST change doesn't affect this.
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $date_epoch2 = TTDate::getBeginDayEpoch((TTDate::getBeginWeekEpoch(time(), 1) + 86400 + 3600));
        $date_stamp2 = TTDate::getDate('DATE', $date_epoch2);

        //Need to create the punches separately as createPunchPair won't split the punches.
        $dd->createPunch($this->user_id, 10, 10, strtotime($date_stamp . ' 12:00AM'), array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);
        $dd->createPunch($this->user_id, 10, 20, strtotime($date_stamp . ' 8:00AM'), array('branch_id' => 0, 'department_id' => 0, 'job_id' => 0, 'job_item_id' => 0), true);

        $punch_arr = $this->getPunchDataArray(TTDate::getBeginDayEpoch($date_epoch), TTDate::getEndDayEpoch($date_epoch2));
        //print_r($punch_arr);

        //Date 1
        $this->assertEquals(1, count($punch_arr[$date_epoch]));
        $this->assertEquals($date_epoch, $punch_arr[$date_epoch][0]['date_stamp']);

        $udt_arr = $this->getUserDateTotalArray($date_epoch, $date_epoch2);
        //Total Time - Date 1
        $this->assertEquals(5, $udt_arr[$date_epoch][0]['object_type_id']);
        $this->assertEquals((8 * 3600), $udt_arr[$date_epoch][0]['total_time']);

        return true;
    }

    /**
     * @group Punch_testPunchControlMatchingA
     */
    public function testPunchControlMatchingA()
    {
        global $dd;

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        //Create just an IN punch.
        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 8:30AM'),
            null,
            array(
                'in_type_id' => 10,
                'out_type_id' => 20,
                'branch_id' => 0,
                'department_id' => 0,
                'job_id' => 0,
                'job_item_id' => 0,
            ),
            true
        );

        $dd->createPunchPair($this->user_id,
            null,
            strtotime($date_stamp . ' 11:30AM'), //Lunch Out
            array(
                'in_type_id' => 10,
                'out_type_id' => 20,
                'branch_id' => 0,
                'department_id' => 0,
                'job_id' => 0,
                'job_item_id' => 0,
            ),
            true
        );

        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 12:30PM'),
            null,
            array(
                'in_type_id' => 20,
                'out_type_id' => 10,
                'branch_id' => 0,
                'department_id' => 0,
                'job_id' => 0,
                'job_item_id' => 0,
            ),
            true
        );

        $dd->createPunchPair($this->user_id,
            null,
            strtotime($date_stamp . ' 5:30PM'), //Lunch Out
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
        $this->assertEquals(4, count($punch_arr[$date_epoch][0]['shift_data']['punches']));
        $this->assertEquals(2, count($punch_arr[$date_epoch][0]['shift_data']['punch_control_ids']));
        //$this->assertEquals( $date_epoch, $punch_arr[$date_epoch][0]['date_stamp'] );

        $udt_arr = $this->getUserDateTotalArray($date_epoch, $date_epoch);
        //Total Time
        $this->assertEquals(5, $udt_arr[$date_epoch][0]['object_type_id']);
        $this->assertEquals((8 * 3600), $udt_arr[$date_epoch][0]['total_time']);

        return true;
    }

    /**
     * @group Punch_testPunchControlMatchingB
     */
    public function testPunchControlMatchingB()
    {
        global $dd;

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        //Create just an IN punch.
        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 8:30AM'),
            null, //strtotime($date_stamp.' 11:30PM'), //Lunch Out
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

        $dd->createPunchPair($this->user_id,
            null, //strtotime($date_stamp.' 8:30PM'),
            strtotime($date_stamp . ' 4:30PM'), //Lunch Out
            array(
                'in_type_id' => 10,
                'out_type_id' => 20,
                'branch_id' => 0,
                'department_id' => 0,
                'job_id' => 0,
                'job_item_id' => 0,
            ),
            true
        );

        $punch_arr = $this->getPunchDataArray(TTDate::getBeginDayEpoch($date_epoch), TTDate::getEndDayEpoch($date_epoch));
        //print_r($punch_arr);
        $this->assertEquals(2, count($punch_arr[$date_epoch][0]['shift_data']['punches']));
        $this->assertEquals(1, count($punch_arr[$date_epoch][0]['shift_data']['punch_control_ids']));
        //$this->assertEquals( $date_epoch, $punch_arr[$date_epoch][0]['date_stamp'] );

        $udt_arr = $this->getUserDateTotalArray($date_epoch, $date_epoch);
        //Total Time
        $this->assertEquals(5, $udt_arr[$date_epoch][0]['object_type_id']);
        $this->assertEquals((8 * 3600), $udt_arr[$date_epoch][0]['total_time']);

        return true;
    }

    /**
     * @group Punch_testPunchControlMatchingC
     */
    public function testPunchControlMatchingC()
    {
        global $dd;

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        //Create just an IN punch.
        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 4:00AM'),
            null,
            array(
                'in_type_id' => 10,
                'out_type_id' => 20,
                'branch_id' => 0,
                'department_id' => 0,
                'job_id' => 0,
                'job_item_id' => 0,
            ),
            true
        );

        //Just create out punch, 15.5hrs later. Threshold is 16hrs.
        $dd->createPunchPair($this->user_id,
            null,
            strtotime($date_stamp . ' 7:30PM'), //Normal Out
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
        $date_epoch = TTDate::getBeginDayEpoch($date_epoch); //This accounts for DST.
        $date_epoch2 = TTDate::getEndDayEpoch($date_epoch); //This accounts for DST.

        $this->assertEquals(2, count($punch_arr[$date_epoch][0]['shift_data']['punches']));
        $this->assertEquals(1, count($punch_arr[$date_epoch][0]['shift_data']['punch_control_ids']));
        //$this->assertEquals( $date_epoch, $punch_arr[$date_epoch][0]['date_stamp'] );

        $udt_arr = $this->getUserDateTotalArray($date_epoch, $date_epoch);
        //Total Time
        $this->assertEquals(5, $udt_arr[$date_epoch][0]['object_type_id']);

        $this->assertEquals((15.5 * 3600), $udt_arr[$date_epoch][0]['total_time']); //If this is the week of the DST switchover, this can be off by one hour.
        //if ( TTDate::doesRangeSpanDST( $date_epoch, $date_epoch2 ) ) {
        //	$this->assertEquals( ((15.5 * 3600)+TTDate::getDSTOffset($date_epoch, $date_epoch2)), $udt_arr[$date_epoch][0]['total_time'] ); //If this is the week of the DST switchover, this can be off by one hour.
        //} else {
        //	$this->assertEquals( (15.5 * 3600), $udt_arr[$date_epoch][0]['total_time'] ); //If this is the week of the DST switchover, this can be off by one hour.
        //}

        return true;
    }

    /**
     * @group Punch_testPunchControlMatchingD
     */
    public function testPunchControlMatchingD()
    {
        global $dd;

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        //Create just an IN punch.
        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 2:00AM'),
            null,
            array(
                'in_type_id' => 10,
                'out_type_id' => 20,
                'branch_id' => 0,
                'department_id' => 0,
                'job_id' => 0,
                'job_item_id' => 0,
            ),
            true
        );

        //Just create out punch, 17.5hrs later. Threshold is 16hrs. //This needs to be more than 1 hour outside the limit due to DST issues.
        $dd->createPunchPair($this->user_id,
            null,
            strtotime($date_stamp . ' 7:30PM'), //Normal Out
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

        $date_epoch = TTDate::getBeginDayEpoch($date_epoch); //This accounts for DST.
        $this->assertEquals(1, count($punch_arr[$date_epoch][0]['shift_data']['punches']));
        $this->assertEquals(1, count($punch_arr[$date_epoch][0]['shift_data']['punch_control_ids']));

        $this->assertEquals(1, count($punch_arr[$date_epoch][1]['shift_data']['punches']));
        $this->assertEquals(1, count($punch_arr[$date_epoch][1]['shift_data']['punch_control_ids']));

        $this->assertNotEquals($punch_arr[$date_epoch][0]['shift_data']['punches'][0]['id'], $punch_arr[$date_epoch][1]['shift_data']['punches'][0]['id']);

        $udt_arr = $this->getUserDateTotalArray($date_epoch, $date_epoch);
        //Total Time
        if (isset($udt_arr[$date_epoch][0])) {
            $this->assertTrue(false);
        } else {
            $this->assertTrue(true);
        }
        //$this->assertEquals( 5, $udt_arr[$date_epoch][0]['object_type_id'] );
        //$this->assertEquals( (0 * 3600), $udt_arr[$date_epoch][0]['total_time'] );

        return true;
    }

    /**
     * @group Punch_testPunchControlMatchingE
     */
    public function testPunchControlMatchingE()
    {
        global $dd;

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $date_epoch2 = TTDate::getBeginDayEpoch((TTDate::getBeginWeekEpoch(time()) + 86400 + 3600));
        $date_stamp2 = TTDate::getDate('DATE', $date_epoch2);

        //Create just an IN punch.
        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 10:00AM'),
            null,
            array(
                'in_type_id' => 10,
                'out_type_id' => 20,
                'branch_id' => 0,
                'department_id' => 0,
                'job_id' => 0,
                'job_item_id' => 0,
            ),
            true
        );

        //Just create out punch, 15.5hrs later. Threshold is 16hrs.
        $dd->createPunchPair($this->user_id,
            null,
            strtotime($date_stamp2 . ' 1:30AM'), //Normal Out
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

        $punch_arr = $this->getPunchDataArray(TTDate::getBeginDayEpoch($date_epoch), TTDate::getEndDayEpoch($date_epoch2));
        //print_r($punch_arr);
        $this->assertEquals(2, count($punch_arr[$date_epoch][0]['shift_data']['punches']));
        $this->assertEquals(1, count($punch_arr[$date_epoch][0]['shift_data']['punch_control_ids']));
        //$this->assertEquals( $date_epoch, $punch_arr[$date_epoch][0]['date_stamp'] );

        $udt_arr = $this->getUserDateTotalArray($date_epoch, $date_epoch2);
        //Total Time
        $this->assertEquals(5, $udt_arr[$date_epoch][0]['object_type_id']);
        $this->assertEquals((15.5 * 3600), $udt_arr[$date_epoch][0]['total_time']);

        return true;
    }

    /**
     * @group Punch_testPunchControlMatchingF
     */
    public function testPunchControlMatchingF()
    {
        global $dd;

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $date_epoch2 = TTDate::getBeginDayEpoch((TTDate::getBeginWeekEpoch(time()) + 86400 + 3600));
        $date_stamp2 = TTDate::getDate('DATE', $date_epoch2);

        //Create just an IN and Lunch Out punch.
        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 8:00AM'),
            strtotime($date_stamp . ' 1:30PM'),
            array(
                'in_type_id' => 10,
                'out_type_id' => 20,
                'branch_id' => 0,
                'department_id' => 0,
                'job_id' => 0,
                'job_item_id' => 0,
            ),
            true
        );

        //Lunch IN and Normal Out punch more than maximum shift time apart.
        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp2 . ' 1:30PM'),
            strtotime($date_stamp2 . ' 2:00PM'), //Normal Out
            array(
                'in_type_id' => 20,
                'out_type_id' => 10,
                'branch_id' => 0,
                'department_id' => 0,
                'job_id' => 0,
                'job_item_id' => 0,
            ),
            true
        );

        //Day 1
        $punch_arr = $this->getPunchDataArray(TTDate::getBeginDayEpoch($date_epoch), TTDate::getEndDayEpoch($date_epoch));
        //print_r($punch_arr);
        $this->assertEquals(2, count($punch_arr[$date_epoch][0]['shift_data']['punches']));
        $this->assertEquals(1, count($punch_arr[$date_epoch][0]['shift_data']['punch_control_ids']));
        $this->assertEquals($date_epoch, $punch_arr[$date_epoch][0]['date_stamp']);

        $udt_arr = $this->getUserDateTotalArray($date_epoch, $date_epoch);
        //Total Time
        $this->assertEquals(5, $udt_arr[$date_epoch][0]['object_type_id']);
        $this->assertEquals((5.5 * 3600), $udt_arr[$date_epoch][0]['total_time']);

        //Day 2
        $punch_arr = $this->getPunchDataArray(TTDate::getBeginDayEpoch($date_epoch2), TTDate::getEndDayEpoch($date_epoch2));
        //print_r($punch_arr);
        $this->assertEquals(2, count($punch_arr[$date_epoch2][0]['shift_data']['punches']));
        $this->assertEquals(1, count($punch_arr[$date_epoch2][0]['shift_data']['punch_control_ids']));
        $this->assertEquals($date_epoch2, $punch_arr[$date_epoch2][0]['date_stamp']);

        $udt_arr = $this->getUserDateTotalArray($date_epoch2, $date_epoch2);
        //Total Time
        $this->assertEquals(5, $udt_arr[$date_epoch2][0]['object_type_id']);
        $this->assertEquals((0.5 * 3600), $udt_arr[$date_epoch2][0]['total_time']);

        return true;
    }

    /**
     * @group Punch_testPunchControlMatchingG
     */
    public function testPunchControlMatchingG()
    {
        global $dd;

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $date_epoch2 = TTDate::getBeginDayEpoch((TTDate::getBeginWeekEpoch(time()) + 86400 + 3600));
        $date_stamp2 = TTDate::getDate('DATE', $date_epoch2);

        //Create just an IN and Lunch Out punch.
        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 8:00AM'),
            strtotime($date_stamp . ' 11:30PM'),
            array(
                'in_type_id' => 10,
                'out_type_id' => 20,
                'branch_id' => 0,
                'department_id' => 0,
                'job_id' => 0,
                'job_item_id' => 0,
            ),
            true
        );

        //Lunch IN and Normal Out punch less than maximum shift time apart.
        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp2 . ' 1:30PM'),
            strtotime($date_stamp2 . ' 2:00PM'), //Normal Out
            array(
                'in_type_id' => 20,
                'out_type_id' => 10,
                'branch_id' => 0,
                'department_id' => 0,
                'job_id' => 0,
                'job_item_id' => 0,
            ),
            true
        );

        //Day 1
        $punch_arr = $this->getPunchDataArray(TTDate::getBeginDayEpoch($date_epoch), TTDate::getEndDayEpoch($date_epoch));
        //print_r($punch_arr);
        $this->assertEquals(4, count($punch_arr[$date_epoch][0]['shift_data']['punches']));
        $this->assertEquals(2, count($punch_arr[$date_epoch][0]['shift_data']['punch_control_ids']));
        $this->assertEquals($date_epoch, $punch_arr[$date_epoch][0]['date_stamp']);

        $udt_arr = $this->getUserDateTotalArray($date_epoch, $date_epoch);
        //Total Time
        $this->assertEquals(5, $udt_arr[$date_epoch][0]['object_type_id']);
        $this->assertEquals((16 * 3600), $udt_arr[$date_epoch][0]['total_time']);

        return true;
    }

    /**
     * @group Punch_testPunchEditingA
     */
    public function testPunchEditingA()
    {
        global $dd;

        $this->createPayPeriodSchedule(20); //Day shift ends on.
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $date_epoch2 = TTDate::getBeginDayEpoch((TTDate::getBeginWeekEpoch(time()) + 86400 + 3600));
        $date_stamp2 = TTDate::getDate('DATE', $date_epoch2);

        //Create just an IN punch.
        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 10:00AM'),
            null,
            array(
                'in_type_id' => 10,
                'out_type_id' => 20,
                'branch_id' => 0,
                'department_id' => 0,
                'job_id' => 0,
                'job_item_id' => 0,
            ),
            true
        );

        //Just create out punch, 15.5hrs later. Threshold is 16hrs.
        $dd->createPunchPair($this->user_id,
            null,
            strtotime($date_stamp . ' 10:00PM'), //Normal Out
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

        $punch_arr = $this->getPunchDataArray(TTDate::getBeginDayEpoch($date_epoch), TTDate::getEndDayEpoch($date_epoch2));
        //print_r($punch_arr);
        $this->assertEquals(2, count($punch_arr[$date_epoch][0]['shift_data']['punches']));
        $this->assertEquals(1, count($punch_arr[$date_epoch][0]['shift_data']['punch_control_ids']));
        $this->assertEquals($date_epoch, $punch_arr[$date_epoch][0]['date_stamp']);

        $udt_arr = $this->getUserDateTotalArray($date_epoch, $date_epoch2);
        //Total Time
        $this->assertEquals(5, $udt_arr[$date_epoch][0]['object_type_id']);
        $this->assertEquals((12 * 3600), $udt_arr[$date_epoch][0]['total_time']);

        //Edit punch to move out time into next day.
        $dd->editPunch($punch_arr[$date_epoch][0]['shift_data']['punches'][1]['id'],
            array(
                'time_stamp' => strtotime($date_stamp . ' 11:00PM'),
            ));

        $punch_arr = $this->getPunchDataArray(TTDate::getBeginDayEpoch($date_epoch), TTDate::getEndDayEpoch($date_epoch2));
        //print_r($punch_arr);

        $this->assertEquals(2, count($punch_arr[$date_epoch][0]['shift_data']['punches']));
        $this->assertEquals(1, count($punch_arr[$date_epoch][0]['shift_data']['punch_control_ids']));
        $this->assertEquals($date_epoch, $punch_arr[$date_epoch][0]['date_stamp']);

        $udt_arr = $this->getUserDateTotalArray($date_epoch, $date_epoch2);
        //Total Time
        $this->assertEquals(5, $udt_arr[$date_epoch][0]['object_type_id']);
        $this->assertEquals((13 * 3600), $udt_arr[$date_epoch][0]['total_time']);


        return true;
    }

    /**
     * @group Punch_testPunchEditingB
     */
    public function testPunchEditingB()
    {
        global $dd;

        $this->createPayPeriodSchedule(20); //Day shift ends on.
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $date_epoch2 = TTDate::getBeginDayEpoch((TTDate::getBeginWeekEpoch(time()) + 86400 + 3600));
        $date_stamp2 = TTDate::getDate('DATE', $date_epoch2);

        //Create just an IN punch.
        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 10:00AM'),
            null,
            array(
                'in_type_id' => 10,
                'out_type_id' => 20,
                'branch_id' => 0,
                'department_id' => 0,
                'job_id' => 0,
                'job_item_id' => 0,
            ),
            true
        );

        //Just create out punch, 15.5hrs later. Threshold is 16hrs.
        $dd->createPunchPair($this->user_id,
            null,
            strtotime($date_stamp . ' 10:00PM'), //Normal Out
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

        $punch_arr = $this->getPunchDataArray(TTDate::getBeginDayEpoch($date_epoch), TTDate::getEndDayEpoch($date_epoch2));
        //print_r($punch_arr);
        $this->assertEquals(2, count($punch_arr[$date_epoch][0]['shift_data']['punches']));
        $this->assertEquals(1, count($punch_arr[$date_epoch][0]['shift_data']['punch_control_ids']));
        $this->assertEquals($date_epoch, $punch_arr[$date_epoch][0]['date_stamp']);

        $udt_arr = $this->getUserDateTotalArray($date_epoch, $date_epoch2);
        //Total Time
        $this->assertEquals(5, $udt_arr[$date_epoch][0]['object_type_id']);
        $this->assertEquals((12 * 3600), $udt_arr[$date_epoch][0]['total_time']);

        //Edit punch to move out time into next day.
        $dd->editPunch($punch_arr[$date_epoch][0]['shift_data']['punches'][1]['id'],
            array(
                'time_stamp' => strtotime($date_stamp2 . ' 1:30AM'),
            ));

        $punch_arr = $this->getPunchDataArray(TTDate::getBeginDayEpoch($date_epoch), TTDate::getEndDayEpoch($date_epoch2));
        //print_r($punch_arr);

        $date_epoch = TTDate::getBeginDayEpoch($date_epoch); //This accounts for DST.
        $date_epoch2 = TTDate::getBeginDayEpoch($date_epoch2); //This accounts for DST.

        //Make sure previous day has no totals, but new day has proper totals.
        if (!isset($punch_arr[$date_epoch])) {
            $this->assertTrue(true);
        } else {
            $this->assertTrue(false);
        }

        if (isset($punch_arr[$date_epoch2])) {
            $this->assertTrue(true);
        } else {
            $this->assertTrue(false);
        }
        $this->assertEquals(2, count($punch_arr[$date_epoch2][0]['shift_data']['punches']));
        $this->assertEquals(1, count($punch_arr[$date_epoch2][0]['shift_data']['punch_control_ids']));
        $this->assertEquals($date_epoch2, $punch_arr[$date_epoch2][0]['date_stamp']);

        $udt_arr = $this->getUserDateTotalArray($date_epoch, $date_epoch2);
        //Total Time
        //$this->assertEquals( 10, $udt_arr[$date_epoch2][0]['status_id'] );
        //$this->assertEquals( 10, $udt_arr[$date_epoch2][0]['type_id'] );
        $this->assertEquals(5, $udt_arr[$date_epoch2][0]['object_type_id']);
        $this->assertEquals((15.5 * 3600), $udt_arr[$date_epoch2][0]['total_time']);

        return true;
    }

    /**
     * @group Punch_testPunchEditingC
     */
    public function testPunchEditingC()
    {
        global $dd;

        $this->createPayPeriodSchedule(10); //Day shift starts on.
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $date_epoch2 = TTDate::getBeginDayEpoch((TTDate::getBeginWeekEpoch(time()) + 86400 + 3600));
        $date_stamp2 = TTDate::getDate('DATE', $date_epoch2);

        //Create just an IN punch.
        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp2 . ' 1:00AM'),
            null,
            array(
                'in_type_id' => 10,
                'out_type_id' => 20,
                'branch_id' => 0,
                'department_id' => 0,
                'job_id' => 0,
                'job_item_id' => 0,
            ),
            true
        );

        //Just create out punch, 15.5hrs later. Threshold is 16hrs.
        $dd->createPunchPair($this->user_id,
            null,
            strtotime($date_stamp2 . ' 1:00PM'), //Normal Out
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

        $punch_arr = $this->getPunchDataArray(TTDate::getBeginDayEpoch($date_epoch), TTDate::getEndDayEpoch($date_epoch2));
        //print_r($punch_arr);
        $date_epoch = TTDate::getBeginDayEpoch($date_epoch); //This accounts for DST.
        $date_epoch2 = TTDate::getBeginDayEpoch($date_epoch2); //This accounts for DST.
        $this->assertEquals(2, count($punch_arr[$date_epoch2][0]['shift_data']['punches']));
        $this->assertEquals(1, count($punch_arr[$date_epoch2][0]['shift_data']['punch_control_ids']));
        $this->assertEquals($date_epoch2, $punch_arr[$date_epoch2][0]['date_stamp']);

        $udt_arr = $this->getUserDateTotalArray($date_epoch, $date_epoch2);
        //Total Time
        //$this->assertEquals( 10, $udt_arr[$date_epoch2][0]['status_id'] );
        //$this->assertEquals( 10, $udt_arr[$date_epoch2][0]['type_id'] );
        $this->assertEquals(5, $udt_arr[$date_epoch2][0]['object_type_id']);
        $this->assertEquals((12 * 3600), $udt_arr[$date_epoch2][0]['total_time']);

        //Edit punch to move out time into next day.
        $dd->editPunch($punch_arr[$date_epoch2][0]['shift_data']['punches'][0]['id'],
            array(
                'time_stamp' => strtotime($date_stamp . ' 9:30PM'),
            ));

        $punch_arr = $this->getPunchDataArray(TTDate::getBeginDayEpoch($date_epoch), TTDate::getEndDayEpoch($date_epoch2));
        //print_r($punch_arr);

        //Make sure previous day has no totals, but new day has proper totals.
        if (!isset($punch_arr[$date_epoch2])) {
            $this->assertTrue(true);
        } else {
            $this->assertTrue(false);
        }

        if (isset($punch_arr[$date_epoch])) {
            $this->assertTrue(true);
        } else {
            $this->assertTrue(false);
        }
        $this->assertEquals(2, count($punch_arr[$date_epoch][0]['shift_data']['punches']));
        $this->assertEquals(1, count($punch_arr[$date_epoch][0]['shift_data']['punch_control_ids']));
        $this->assertEquals($date_epoch, $punch_arr[$date_epoch][0]['date_stamp']);

        $udt_arr = $this->getUserDateTotalArray($date_epoch, $date_epoch2);
        //Total Time
        $this->assertEquals(5, $udt_arr[$date_epoch][0]['object_type_id']);
        $this->assertEquals((15.5 * 3600), $udt_arr[$date_epoch][0]['total_time']);

        return true;
    }

    /**
     * @group testPunchEditingTimeZoneA
     */
    public function testPunchEditingTimeZoneA()
    {
        global $dd;

        //
        //Cause punch to switch days due to timezone, and not actually changing the dates.
        //  Make sure that UDT records follow the date properly too.
        //
        TTDate::setTimeZone('PST8PDT', true); //Due to being a singleton and PHPUnit resetting the state, always force the timezone to be set.

        $this->createPayPeriodSchedule(10); //Day shift starts on
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $date_epoch2 = TTDate::getBeginDayEpoch((TTDate::getBeginWeekEpoch(time()) + 86400 + 3600));
        $date_stamp2 = TTDate::getDate('DATE', $date_epoch2);

        //Create just an IN punch.
        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 11:30PM'),
            strtotime($date_stamp2 . ' 7:30AM'),
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

        $punch_arr = $this->getPunchDataArray(TTDate::getBeginDayEpoch($date_epoch), TTDate::getEndDayEpoch($date_epoch2));
        //print_r($punch_arr);
        $this->assertEquals(2, count($punch_arr[$date_epoch][0]['shift_data']['punches']));
        $this->assertEquals(1, count($punch_arr[$date_epoch][0]['shift_data']['punch_control_ids']));
        $this->assertEquals($date_epoch, $punch_arr[$date_epoch][0]['date_stamp']);

        $udt_arr = $this->getUserDateTotalArray($date_epoch, $date_epoch2);
        //Total Time
        $this->assertEquals(5, $udt_arr[$date_epoch][0]['object_type_id']);
        $this->assertEquals((8 * 3600), $udt_arr[$date_epoch][0]['total_time']);


        TTDate::setTimeZone('MST7MDT', true); //Due to being a singleton and PHPUnit resetting the state, always force the timezone to be set.
        //After the timezone has been changed, re-get the date_epoch2 so it matches in the correct timezone.
        $date_epoch2 = TTDate::getBeginDayEpoch((TTDate::getBeginWeekEpoch(time()) + 86400 + 3600));
        $date_stamp2 = TTDate::getDate('DATE', $date_epoch2);


        //Edit punch to move out time into next day.
        $dd->editPunch($punch_arr[$date_epoch][0]['shift_data']['punches'][1]['id'],
            array(
                //'time_stamp' => strtotime($date_stamp.' 11:00PM'),
                'time_stamp' => strtotime(date('Ymd H:i:s', $punch_arr[$date_epoch][0]['shift_data']['punches'][1]['time_stamp'])),
            ));

        $punch_arr = $this->getPunchDataArray(TTDate::getBeginDayEpoch($date_epoch), TTDate::getEndDayEpoch($date_epoch2));
        //print_r($punch_arr);

        if (isset($punch_arr[$date_epoch])) {
            $this->assertTrue(false);
        } else {
            $this->assertTrue(true);
        }

        $this->assertEquals(2, count($punch_arr[$date_epoch2][0]['shift_data']['punches']));
        $this->assertEquals(1, count($punch_arr[$date_epoch2][0]['shift_data']['punch_control_ids']));
        $this->assertEquals($date_epoch2, $punch_arr[$date_epoch2][0]['date_stamp']);

        $udt_arr = $this->getUserDateTotalArray($date_epoch, $date_epoch2);
        //Total Time
        $this->assertEquals(5, $udt_arr[$date_epoch2][0]['object_type_id']);
        $this->assertEquals((8 * 3600), $udt_arr[$date_epoch2][0]['total_time']);


        TTDate::setTimeZone('PST8PDT', true); //Due to being a singleton and PHPUnit resetting the state, always force the timezone to be set.

        //Edit punch to move out time into next day.
        $dd->editPunch($punch_arr[$date_epoch2][0]['shift_data']['punches'][1]['id'],
            array(
                //'time_stamp' => strtotime($date_stamp.' 11:00PM'),
                'time_stamp' => strtotime(date('Ymd H:i:s', $punch_arr[$date_epoch2][0]['shift_data']['punches'][1]['time_stamp'])),
            ));

        $punch_arr = $this->getPunchDataArray(TTDate::getBeginDayEpoch($date_epoch), TTDate::getEndDayEpoch($date_epoch2));
        //print_r($punch_arr);

        if (isset($punch_arr[$date_epoch2])) {
            $this->assertTrue(false);
        } else {
            $this->assertTrue(true);
        }

        $this->assertEquals(2, count($punch_arr[$date_epoch][0]['shift_data']['punches']));
        $this->assertEquals(1, count($punch_arr[$date_epoch][0]['shift_data']['punch_control_ids']));
        $this->assertEquals($date_epoch, $punch_arr[$date_epoch][0]['date_stamp']);

        $udt_arr = $this->getUserDateTotalArray($date_epoch, $date_epoch2);
        //Total Time
        $this->assertEquals(5, $udt_arr[$date_epoch][0]['object_type_id']);
        $this->assertEquals((8 * 3600), $udt_arr[$date_epoch][0]['total_time']);

        return true;
    }

    /**
     * @group Punch_testPunchEditingShiftDayChangeA
     */
    public function testPunchEditingShiftDayChangeA()
    {
        //Test moving shifts from one day to the next when punches are edited.
        global $dd;

        $this->createPayPeriodSchedule(10); //Day shift starts on.
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $date_epoch2 = TTDate::getBeginDayEpoch((TTDate::getBeginWeekEpoch(time()) + 86400 + 3600));
        $date_stamp2 = TTDate::getDate('DATE', $date_epoch2);

        //Create just an IN punch.
        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 1:30PM'),
            strtotime($date_stamp . ' 2:30PM'),
            array(
                'in_type_id' => 10,
                'out_type_id' => 20,
                'branch_id' => 0,
                'department_id' => 0,
                'job_id' => 0,
                'job_item_id' => 0,
            ),
            true
        );

        //Just create out punch, 15.5hrs later. Threshold is 16hrs.
        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp2 . ' 12:30AM'), //Normal Out
            strtotime($date_stamp2 . ' 2:30AM'), //Normal Out
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

        $punch_arr = $this->getPunchDataArray(TTDate::getBeginDayEpoch($date_epoch), TTDate::getEndDayEpoch($date_epoch2));
        //print_r($punch_arr);

        $date_epoch = TTDate::getBeginDayEpoch($date_epoch); //This accounts for DST.
        $date_epoch2 = TTDate::getBeginDayEpoch($date_epoch2); //This accounts for DST.
        $this->assertEquals(2, count($punch_arr[$date_epoch][0]['shift_data']['punches']));
        $this->assertEquals(1, count($punch_arr[$date_epoch][0]['shift_data']['punch_control_ids']));
        $this->assertEquals($date_epoch, $punch_arr[$date_epoch][0]['date_stamp']);

        $this->assertEquals(2, count($punch_arr[$date_epoch2][1]['shift_data']['punches']));
        $this->assertEquals(1, count($punch_arr[$date_epoch2][1]['shift_data']['punch_control_ids']));
        $this->assertEquals($date_epoch2, $punch_arr[$date_epoch2][1]['date_stamp']);

        $udt_arr = $this->getUserDateTotalArray($date_epoch, $date_epoch2);
        //Total Time - Day 1
        $this->assertEquals(5, $udt_arr[$date_epoch][0]['object_type_id']);
        $this->assertEquals((1 * 3600), $udt_arr[$date_epoch][0]['total_time']);

        //Total Time - Day 2
        //$this->assertEquals( 10, $udt_arr[$date_epoch2][0]['status_id'] );
        //$this->assertEquals( 10, $udt_arr[$date_epoch2][0]['type_id'] );
        $this->assertEquals(5, $udt_arr[$date_epoch2][0]['object_type_id']);
        $this->assertEquals((2 * 3600), $udt_arr[$date_epoch2][0]['total_time']);

        //Edit punch to move out time into next day.
        $dd->editPunch($punch_arr[$date_epoch][0]['shift_data']['punches'][1]['id'],
            array(
                'time_stamp' => strtotime($date_stamp . ' 11:30PM'),
            ));

        $punch_arr = $this->getPunchDataArray(TTDate::getBeginDayEpoch($date_epoch), TTDate::getEndDayEpoch($date_epoch2));
        //print_r($punch_arr);

        //Make sure previous day has no totals, but new day has proper totals.
        if (!isset($punch_arr[$date_epoch2])) {
            $this->assertTrue(true);
        } else {
            $this->assertTrue(false);
        }

        if (isset($punch_arr[$date_epoch])) {
            $this->assertTrue(true);
        } else {
            $this->assertTrue(false);
        }
        $this->assertEquals(4, count($punch_arr[$date_epoch][0]['shift_data']['punches']));
        $this->assertEquals(2, count($punch_arr[$date_epoch][0]['shift_data']['punch_control_ids']));
        $this->assertEquals($date_epoch, $punch_arr[$date_epoch][0]['date_stamp']);

        $udt_arr = $this->getUserDateTotalArray($date_epoch, $date_epoch2);
        //Total Time - Day1
        $this->assertEquals(5, $udt_arr[$date_epoch][0]['object_type_id']);
        $this->assertEquals((12 * 3600), $udt_arr[$date_epoch][0]['total_time']);

        return true;
    }

    /**
     * @group Punch_testPunchEditingShiftDayChangeB
     */
    public function testPunchEditingShiftDayChangeB()
    {
        //Test moving shifts from one day to the next when punches are edited.
        global $dd;

        $this->createPayPeriodSchedule(10); //Day shift starts on.
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $date_epoch2 = TTDate::getBeginDayEpoch((TTDate::getBeginWeekEpoch(time()) + 86400 + 3600));
        $date_stamp2 = TTDate::getDate('DATE', $date_epoch2);

        //Create just an IN punch.
        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 1:30PM'),
            strtotime($date_stamp . ' 11:30PM'),
            array(
                'in_type_id' => 10,
                'out_type_id' => 20,
                'branch_id' => 0,
                'department_id' => 0,
                'job_id' => 0,
                'job_item_id' => 0,
            ),
            true
        );

        //Just create out punch, 15.5hrs later. Threshold is 16hrs.
        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp2 . ' 12:30AM'), //Normal Out
            strtotime($date_stamp2 . ' 2:30AM'), //Normal Out
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

        $punch_arr = $this->getPunchDataArray(TTDate::getBeginDayEpoch($date_epoch), TTDate::getEndDayEpoch($date_epoch2));
        //print_r($punch_arr);

        $date_epoch = TTDate::getBeginDayEpoch($date_epoch); //This accounts for DST.
        $date_epoch2 = TTDate::getBeginDayEpoch($date_epoch2); //This accounts for DST.
        $this->assertEquals(4, count($punch_arr[$date_epoch][0]['shift_data']['punches']));
        $this->assertEquals(2, count($punch_arr[$date_epoch][0]['shift_data']['punch_control_ids']));
        $this->assertEquals($date_epoch, $punch_arr[$date_epoch][0]['date_stamp']);

        $udt_arr = $this->getUserDateTotalArray($date_epoch, $date_epoch2);
        //Total Time
        $this->assertEquals(5, $udt_arr[$date_epoch][0]['object_type_id']);
        $this->assertEquals((12 * 3600), $udt_arr[$date_epoch][0]['total_time']);

        //Edit punch to move out time into next day.
        $dd->editPunch($punch_arr[$date_epoch][0]['shift_data']['punches'][1]['id'],
            array(
                'time_stamp' => strtotime($date_stamp . ' 2:30PM'),
            ));

        $punch_arr = $this->getPunchDataArray(TTDate::getBeginDayEpoch($date_epoch), TTDate::getEndDayEpoch($date_epoch2));
        //print_r($punch_arr);

        //Make sure previous day has no totals, but new day has proper totals.
        if (isset($punch_arr[$date_epoch2])) {
            $this->assertTrue(true);
        } else {
            $this->assertTrue(false);
        }

        if (isset($punch_arr[$date_epoch])) {
            $this->assertTrue(true);
        } else {
            $this->assertTrue(false);
        }
        $this->assertEquals(2, count($punch_arr[$date_epoch][0]['shift_data']['punches']));
        $this->assertEquals(1, count($punch_arr[$date_epoch][0]['shift_data']['punch_control_ids']));
        $this->assertEquals($date_epoch, $punch_arr[$date_epoch][0]['date_stamp']);

        $udt_arr = $this->getUserDateTotalArray($date_epoch, $date_epoch2);
        //Total Time - Day1
        $this->assertEquals(5, $udt_arr[$date_epoch][0]['object_type_id']);
        $this->assertEquals((1 * 3600), $udt_arr[$date_epoch][0]['total_time']);

        //Total Time - Day2
        //$this->assertEquals( 10, $udt_arr[$date_epoch2][0]['status_id'] );
        //$this->assertEquals( 10, $udt_arr[$date_epoch2][0]['type_id'] );
        $this->assertEquals(5, $udt_arr[$date_epoch2][0]['object_type_id']);
        $this->assertEquals((2 * 3600), $udt_arr[$date_epoch2][0]['total_time']);

        return true;
    }

    /**
     * @group Punch_testPunchEditingD
     */
    public function testPunchEditingD()
    {
        global $dd;

        $this->createPayPeriodSchedule(30); //Day with most time worked
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $date_epoch2 = TTDate::getBeginDayEpoch((TTDate::getBeginWeekEpoch(time()) + 86400 + 3600));
        $date_stamp2 = TTDate::getDate('DATE', $date_epoch2);

        //Create just an IN punch.
        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 5:30PM'),
            null,
            array(
                'in_type_id' => 10,
                'out_type_id' => 20,
                'branch_id' => 0,
                'department_id' => 0,
                'job_id' => 0,
                'job_item_id' => 0,
            ),
            true
        );

        //Just create out punch, 15.5hrs later. Threshold is 16hrs.
        $dd->createPunchPair($this->user_id,
            null,
            strtotime($date_stamp . ' 11:30PM'), //Normal Out
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

        $punch_arr = $this->getPunchDataArray(TTDate::getBeginDayEpoch($date_epoch), TTDate::getEndDayEpoch($date_epoch2));
        //print_r($punch_arr);
        $this->assertEquals(2, count($punch_arr[$date_epoch][0]['shift_data']['punches']));
        $this->assertEquals(1, count($punch_arr[$date_epoch][0]['shift_data']['punch_control_ids']));
        $this->assertEquals($date_epoch, $punch_arr[$date_epoch][0]['date_stamp']);

        $udt_arr = $this->getUserDateTotalArray($date_epoch, $date_epoch2);
        //Total Time
        $this->assertEquals(5, $udt_arr[$date_epoch][0]['object_type_id']);
        $this->assertEquals((6 * 3600), $udt_arr[$date_epoch][0]['total_time']);

        //Edit punch to move out time into next day.
        $dd->editPunch($punch_arr[$date_epoch][0]['shift_data']['punches'][1]['id'],
            array(
                'time_stamp' => strtotime($date_stamp2 . ' 7:30AM'),
            ));

        $punch_arr = $this->getPunchDataArray(TTDate::getBeginDayEpoch($date_epoch), TTDate::getEndDayEpoch($date_epoch2));
        //print_r($punch_arr);

        $date_epoch = TTDate::getBeginDayEpoch($date_epoch); //This accounts for DST.
        $date_epoch2 = TTDate::getBeginDayEpoch($date_epoch2); //This accounts for DST.

        //Make sure previous day has no totals, but new day has proper totals.
        if (!isset($punch_arr[$date_epoch])) {
            $this->assertTrue(true);
        } else {
            $this->assertTrue(false);
        }

        if (isset($punch_arr[$date_epoch2])) {
            $this->assertTrue(true);
        } else {
            $this->assertTrue(false);
        }
        $this->assertEquals(2, count($punch_arr[$date_epoch2][0]['shift_data']['punches']));
        $this->assertEquals(1, count($punch_arr[$date_epoch2][0]['shift_data']['punch_control_ids']));
        $this->assertEquals($date_epoch2, $punch_arr[$date_epoch2][0]['date_stamp']);

        $udt_arr = $this->getUserDateTotalArray($date_epoch, $date_epoch2);
        //Total Time
        //$this->assertEquals( 10, $udt_arr[$date_epoch2][0]['status_id'] );
        //$this->assertEquals( 10, $udt_arr[$date_epoch2][0]['type_id'] );
        $this->assertEquals(5, $udt_arr[$date_epoch2][0]['object_type_id']);
        $this->assertEquals((14 * 3600), $udt_arr[$date_epoch2][0]['total_time']);

        return true;
    }

    /**
     * @group Punch_testPunchEditingE
     */
    public function testPunchEditingE()
    {
        global $dd;

        $this->createPayPeriodSchedule(20); //Day shift ends on.
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $date_epoch2 = TTDate::getBeginDayEpoch((TTDate::getBeginWeekEpoch(time()) + 86400 + 3600));
        $date_stamp2 = TTDate::getDate('DATE', $date_epoch2);

        //Create just an IN punch.
        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 8:00AM'),
            strtotime($date_stamp . ' 10:00AM'), //Break Out
            array(
                'in_type_id' => 10,
                'out_type_id' => 30,
                'branch_id' => 0,
                'department_id' => 0,
                'job_id' => 0,
                'job_item_id' => 0,
            ),
            true
        );

        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 10:15AM'), //Break In
            strtotime($date_stamp . ' 12:00PM'), //Lunch Out
            array(
                'in_type_id' => 30,
                'out_type_id' => 20,
                'branch_id' => 0,
                'department_id' => 0,
                'job_id' => 0,
                'job_item_id' => 0,
            ),
            true
        );

        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 1:00PM'), //Lunch In
            strtotime($date_stamp . ' 5:15PM'), //Normal Out
            array(
                'in_type_id' => 20,
                'out_type_id' => 10,
                'branch_id' => 0,
                'department_id' => 0,
                'job_id' => 0,
                'job_item_id' => 0,
            ),
            true
        );

        $punch_arr = $this->getPunchDataArray(TTDate::getBeginDayEpoch($date_epoch), TTDate::getEndDayEpoch($date_epoch2));
        //print_r($punch_arr);
        $this->assertEquals(6, count($punch_arr[$date_epoch][0]['shift_data']['punches']));
        $this->assertEquals(3, count($punch_arr[$date_epoch][0]['shift_data']['punch_control_ids']));
        $this->assertEquals($date_epoch, $punch_arr[$date_epoch][0]['date_stamp']);

        $udt_arr = $this->getUserDateTotalArray($date_epoch, $date_epoch2);
        //Total Time
        $this->assertEquals(5, $udt_arr[$date_epoch][0]['object_type_id']);
        $this->assertEquals((8 * 3600), $udt_arr[$date_epoch][0]['total_time']);

        //Edit punch to move out time into next day.
        $edit_punch_result = $dd->editPunch($punch_arr[$date_epoch][0]['shift_data']['punches'][2]['id'],
            array(
                'time_stamp' => strtotime($date_stamp . ' 10:15PM'), //Set it from 10:15AM to 10:15PM which overlaps with subsequent punches and should cause a validation error.
            ));

        $this->assertEquals(false, $edit_punch_result); //Make sure editing returns false.

        $punch_arr = $this->getPunchDataArray(TTDate::getBeginDayEpoch($date_epoch), TTDate::getEndDayEpoch($date_epoch2));
        //print_r($punch_arr);

        $this->assertEquals(6, count($punch_arr[$date_epoch][0]['shift_data']['punches']));
        $this->assertEquals(3, count($punch_arr[$date_epoch][0]['shift_data']['punch_control_ids']));
        $this->assertEquals($date_epoch, $punch_arr[$date_epoch][0]['date_stamp']);

        $udt_arr = $this->getUserDateTotalArray($date_epoch, $date_epoch2);
        //Total Time
        $this->assertEquals(5, $udt_arr[$date_epoch][0]['object_type_id']);
        $this->assertEquals((8 * 3600), $udt_arr[$date_epoch][0]['total_time']);

        return true;
    }

    /**
     * @group Punch_testPunchDeletingA
     */
    public function testPunchDeletingA()
    {
        global $dd;

        $this->createPayPeriodSchedule(10); //Day shift starts on.
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $date_epoch2 = TTDate::getBeginDayEpoch((TTDate::getBeginWeekEpoch(time()) + 86400 + 3600));
        $date_stamp2 = TTDate::getDate('DATE', $date_epoch2);

        //Create just an IN punch.
        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 10:00AM'),
            null,
            array(
                'in_type_id' => 10,
                'out_type_id' => 20,
                'branch_id' => 0,
                'department_id' => 0,
                'job_id' => 0,
                'job_item_id' => 0,
            ),
            true
        );

        //Just create out punch, 15.5hrs later. Threshold is 16hrs.
        $dd->createPunchPair($this->user_id,
            null,
            strtotime($date_stamp . ' 10:00PM'), //Normal Out
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

        $punch_arr = $this->getPunchDataArray(TTDate::getBeginDayEpoch($date_epoch), TTDate::getEndDayEpoch($date_epoch2));
        //print_r($punch_arr);
        $this->assertEquals(2, count($punch_arr[$date_epoch][0]['shift_data']['punches']));
        $this->assertEquals(1, count($punch_arr[$date_epoch][0]['shift_data']['punch_control_ids']));
        $this->assertEquals($date_epoch, $punch_arr[$date_epoch][0]['date_stamp']);

        $udt_arr = $this->getUserDateTotalArray($date_epoch, $date_epoch2);
        //Total Time
        $this->assertEquals(5, $udt_arr[$date_epoch][0]['object_type_id']);
        $this->assertEquals((12 * 3600), $udt_arr[$date_epoch][0]['total_time']);

        //Delete punch
        $dd->deletePunch($punch_arr[$date_epoch][0]['shift_data']['punches'][1]['id']);

        $punch_arr = $this->getPunchDataArray(TTDate::getBeginDayEpoch($date_epoch), TTDate::getEndDayEpoch($date_epoch2));
        //print_r($punch_arr);

        $this->assertEquals(1, count($punch_arr[$date_epoch][0]['shift_data']['punches']));
        $this->assertEquals(1, count($punch_arr[$date_epoch][0]['shift_data']['punch_control_ids']));
        $this->assertEquals($date_epoch, $punch_arr[$date_epoch][0]['date_stamp']);

        $udt_arr = $this->getUserDateTotalArray($date_epoch, $date_epoch2);
        //Total Time
        if (isset($udt_arr[$date_epoch][0])) {
            $this->assertTrue(false);
        } else {
            $this->assertTrue(true);
        }
        //$this->assertEquals( 5, $udt_arr[$date_epoch][0]['object_type_id'] );
        //$this->assertEquals( (0 * 3600), $udt_arr[$date_epoch][0]['total_time'] );

        return true;
    }

    /**
     * @group Punch_testPunchDeletingB
     */
    public function testPunchDeletingB()
    {
        global $dd;

        $this->createPayPeriodSchedule(10); //Day shift starts on.
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $date_epoch2 = TTDate::getBeginDayEpoch((TTDate::getBeginWeekEpoch(time()) + 86400 + 3600));
        $date_stamp2 = TTDate::getDate('DATE', $date_epoch2);

        //Create just an IN punch.
        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 10:00PM'),
            null,
            array(
                'in_type_id' => 10,
                'out_type_id' => 20,
                'branch_id' => 0,
                'department_id' => 0,
                'job_id' => 0,
                'job_item_id' => 0,
            ),
            true
        );

        //Just create out punch, 15.5hrs later. Threshold is 16hrs.
        $dd->createPunchPair($this->user_id,
            null,
            strtotime($date_stamp . ' 11:30PM'), //Normal Out
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

        //Create just an IN punch.
        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp2 . ' 1:00AM'),
            null,
            array(
                'in_type_id' => 10,
                'out_type_id' => 20,
                'branch_id' => 0,
                'department_id' => 0,
                'job_id' => 0,
                'job_item_id' => 0,
            ),
            true
        );

        //Just create out punch, 15.5hrs later. Threshold is 16hrs.
        $dd->createPunchPair($this->user_id,
            null,
            strtotime($date_stamp2 . ' 2:30AM'), //Normal Out
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

        $punch_arr = $this->getPunchDataArray(TTDate::getBeginDayEpoch($date_epoch), TTDate::getEndDayEpoch($date_epoch2));
        //print_r($punch_arr);
        $this->assertEquals(4, count($punch_arr[$date_epoch][0]['shift_data']['punches']));
        $this->assertEquals(2, count($punch_arr[$date_epoch][0]['shift_data']['punch_control_ids']));
        $this->assertEquals($date_epoch, $punch_arr[$date_epoch][0]['date_stamp']);

        $udt_arr = $this->getUserDateTotalArray($date_epoch, $date_epoch2);
        //Total Time
        $this->assertEquals(5, $udt_arr[$date_epoch][0]['object_type_id']);
        $this->assertEquals((3 * 3600), $udt_arr[$date_epoch][0]['total_time']);

        //Delete first out punch, causing the totals to change, but nothing else.
        $dd->deletePunch($punch_arr[$date_epoch][0]['shift_data']['punches'][1]['id']);

        $punch_arr = $this->getPunchDataArray(TTDate::getBeginDayEpoch($date_epoch), TTDate::getEndDayEpoch($date_epoch2));
        //print_r($punch_arr);

        $date_epoch = TTDate::getBeginDayEpoch($date_epoch); //This accounts for DST.
        $date_epoch2 = TTDate::getBeginDayEpoch($date_epoch2); //This accounts for DST.

        $this->assertEquals(3, count($punch_arr[$date_epoch][0]['shift_data']['punches']));
        $this->assertEquals(2, count($punch_arr[$date_epoch][0]['shift_data']['punch_control_ids']));
        $this->assertEquals($date_epoch, $punch_arr[$date_epoch][0]['date_stamp']);

        $udt_arr = $this->getUserDateTotalArray($date_epoch, $date_epoch2);
        //Total Time
        $this->assertEquals(5, $udt_arr[$date_epoch][0]['object_type_id']);
        $this->assertEquals((1.5 * 3600), $udt_arr[$date_epoch][0]['total_time']);

        //Delete first in punch (last punch in pair), causing the totals to change, and the final two punches to switch days.
        $dd->deletePunch($punch_arr[$date_epoch][0]['shift_data']['punches'][0]['id']);

        $punch_arr = $this->getPunchDataArray(TTDate::getBeginDayEpoch($date_epoch), TTDate::getEndDayEpoch($date_epoch2));
        //print_r($punch_arr);

        if (!isset($punch_arr[$date_epoch])) {
            $this->assertTrue(true);
        } else {
            $this->assertTrue(false);
        }

        $this->assertEquals(2, count($punch_arr[$date_epoch2][0]['shift_data']['punches']));
        $this->assertEquals(1, count($punch_arr[$date_epoch2][0]['shift_data']['punch_control_ids']));
        $this->assertEquals($date_epoch2, $punch_arr[$date_epoch2][0]['date_stamp']);

        $udt_arr = $this->getUserDateTotalArray($date_epoch, $date_epoch2);
        //Total Time
        //$this->assertEquals( 10, $udt_arr[$date_epoch2][0]['status_id'] );
        //$this->assertEquals( 10, $udt_arr[$date_epoch2][0]['type_id'] );
        $this->assertEquals(5, $udt_arr[$date_epoch2][0]['object_type_id']);
        $this->assertEquals((1.5 * 3600), $udt_arr[$date_epoch2][0]['total_time']);

        return true;
    }

    /**
     * @group Punch_testValidationA
     */
    public function testValidationA()
    {
        global $dd;

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

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
        //Total Time
        $this->assertEquals(5, $udt_arr[$date_epoch][0]['object_type_id']);
        $this->assertEquals((7 * 3600), $udt_arr[$date_epoch][0]['total_time']);


        //Edit punch to after Out time.
        $edit_punch_result = $dd->editPunch($punch_arr[$date_epoch][0]['shift_data']['punches'][0]['id'],
            array(
                'time_stamp' => strtotime($date_stamp . ' 3:30PM'),
            ));


        //Make sure edit punch failed.
        if ($edit_punch_result === false) {
            $this->assertTrue(true);
        } else {
            $this->assertTrue(false);
        }

        $udt_arr = $this->getUserDateTotalArray($date_epoch, $date_epoch);
        //Total Time
        $this->assertEquals(5, $udt_arr[$date_epoch][0]['object_type_id']);
        $this->assertEquals((7 * 3600), $udt_arr[$date_epoch][0]['total_time']);

        return true;
    }

    /**
     * @group Punch_testValidationA2
     */
    public function testValidationA2()
    {
        global $dd;

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

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
        //Total Time
        $this->assertEquals(5, $udt_arr[$date_epoch][0]['object_type_id']);
        $this->assertEquals((7 * 3600), $udt_arr[$date_epoch][0]['total_time']);


        //Edit punch to after Out time,
        //*that exceeds the new shift trigger time of 4hrs.*
        $edit_punch_result = $dd->editPunch($punch_arr[$date_epoch][0]['shift_data']['punches'][0]['id'],
            array(
                'time_stamp' => strtotime($date_stamp . ' 8:00PM'),
            ));


        //Make sure edit punch failed.
        if ($edit_punch_result === false) {
            $this->assertTrue(true);
        } else {
            $this->assertTrue(false);
        }

        $udt_arr = $this->getUserDateTotalArray($date_epoch, $date_epoch);
        //Total Time
        $this->assertEquals(5, $udt_arr[$date_epoch][0]['object_type_id']);
        $this->assertEquals((7 * 3600), $udt_arr[$date_epoch][0]['total_time']);

        return true;
    }

    /**
     * @group Punch_testValidationB
     */
    public function testValidationB()
    {
        global $dd;

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

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
        //Total Time
        $this->assertEquals(5, $udt_arr[$date_epoch][0]['object_type_id']);
        $this->assertEquals((7 * 3600), $udt_arr[$date_epoch][0]['total_time']);


        //Edit punch to after Out time.
        $edit_punch_result = $dd->editPunch($punch_arr[$date_epoch][0]['shift_data']['punches'][1]['id'],
            array(
                'time_stamp' => strtotime($date_stamp . ' 7:30AM'),
            ));


        //Make sure edit punch failed.
        if ($edit_punch_result === false) {
            $this->assertTrue(true);
        } else {
            $this->assertTrue(false);
        }

        $udt_arr = $this->getUserDateTotalArray($date_epoch, $date_epoch);
        //Total Time
        $this->assertEquals(5, $udt_arr[$date_epoch][0]['object_type_id']);
        $this->assertEquals((7 * 3600), $udt_arr[$date_epoch][0]['total_time']);

        return true;
    }

    /**
     * @group Punch_testValidationC
     */
    public function testValidationC()
    {
        global $dd;

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

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
        //Total Time
        $this->assertEquals(5, $udt_arr[$date_epoch][0]['object_type_id']);
        $this->assertEquals((7 * 3600), $udt_arr[$date_epoch][0]['total_time']);

        //Try to add another punch inbetween already existing punch pair.
        $edit_punch_result = $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 9:00AM'),
            null,
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


        //Make sure edit punch failed.
        if ($edit_punch_result === false) {
            $this->assertTrue(true);
        } else {
            $this->assertTrue(false);
        }

        $udt_arr = $this->getUserDateTotalArray($date_epoch, $date_epoch);
        //Total Time
        $this->assertEquals(5, $udt_arr[$date_epoch][0]['object_type_id']);
        $this->assertEquals((7 * 3600), $udt_arr[$date_epoch][0]['total_time']);

        return true;
    }

    /**
     * @group Punch_testValidationD
     */
    public function testValidationD()
    {
        global $dd;

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

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
        //Total Time
        $this->assertEquals(5, $udt_arr[$date_epoch][0]['object_type_id']);
        $this->assertEquals((7 * 3600), $udt_arr[$date_epoch][0]['total_time']);


        //Add additional punch outside existing punch pair, so we can later edit it to fit inbetween punch pair.
        $edit_punch_result = $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 4:00PM'),
            null,
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

        //Make sure adding 3rd punch succeeded
        if ($edit_punch_result === true) {
            $this->assertTrue(true);
        } else {
            $this->assertTrue(false);
        }

        $udt_arr = $this->getUserDateTotalArray($date_epoch, $date_epoch);
        //Total Time
        $this->assertEquals(5, $udt_arr[$date_epoch][0]['object_type_id']);
        $this->assertEquals((7 * 3600), $udt_arr[$date_epoch][0]['total_time']);


        $punch_arr = $this->getPunchDataArray(TTDate::getBeginDayEpoch($date_epoch), TTDate::getEndDayEpoch($date_epoch));
        //print_r($punch_arr);

        //Edit punch to after Out time.
        $edit_punch_result = $dd->editPunch($punch_arr[$date_epoch][0]['shift_data']['punches'][2]['id'],
            array(
                'time_stamp' => strtotime($date_stamp . ' 2:00PM'),
            ));


        //Make sure editing punch failed
        if ($edit_punch_result === false) {
            $this->assertTrue(true);
        } else {
            $this->assertTrue(false);
        }

        $udt_arr = $this->getUserDateTotalArray($date_epoch, $date_epoch);
        //Total Time
        $this->assertEquals(5, $udt_arr[$date_epoch][0]['object_type_id']);
        $this->assertEquals((7 * 3600), $udt_arr[$date_epoch][0]['total_time']);

        return true;
    }

    /**
     * @group Punch_testValidationE
     */
    public function testValidationE()
    {
        global $dd;

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 8:00AM'),
            strtotime($date_stamp . ' 1:00PM'),
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

        //Add additional punch pair with no gap between them.
        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 1:00PM'),
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
        $this->assertEquals(2, count($punch_arr[$date_epoch]));
        $this->assertEquals($date_epoch, $punch_arr[$date_epoch][0]['date_stamp']);


        $udt_arr = $this->getUserDateTotalArray($date_epoch, $date_epoch);
        //Total Time
        $this->assertEquals(5, $udt_arr[$date_epoch][0]['object_type_id']);
        $this->assertEquals((7 * 3600), $udt_arr[$date_epoch][0]['total_time']);


        //Try to add additional punch between two punch pairs with no gap.
        $edit_punch_result = $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 1:00PM'),
            null,
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

        //Make sure adding 3rd punch succeeded
        if ($edit_punch_result === false) {
            $this->assertTrue(true);
        } else {
            $this->assertTrue(false);
        }

        $udt_arr = $this->getUserDateTotalArray($date_epoch, $date_epoch);
        //Total Time
        $this->assertEquals(5, $udt_arr[$date_epoch][0]['object_type_id']);
        $this->assertEquals((7 * 3600), $udt_arr[$date_epoch][0]['total_time']);

        return true;
    }

    /**
     * @group Punch_testValidationF
     */
    public function testValidationF()
    {
        global $dd;

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 8:00AM'),
            null,
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

        //Add additional punch pair with no gap between them.
        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 8:00AM'),
            null,
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
        //Total Time
        if (isset($udt_arr[$date_epoch][0])) {
            $this->assertTrue(false);
        } else {
            $this->assertTrue(true);
        }
        //$this->assertEquals( 5, $udt_arr[$date_epoch][0]['object_type_id'] );
        //$this->assertEquals( (0 * 3600), $udt_arr[$date_epoch][0]['total_time'] );

        return true;
    }

    /**
     * @group Punch_testValidationG
     */
    public function testValidationG()
    {
        global $dd;

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 8:00AM'),
            strtotime($date_stamp . ' 8:00AM'),
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
        $this->assertEquals(0, count($punch_arr[$date_epoch]));
        //$this->assertEquals( $date_epoch, $punch_arr[$date_epoch][0]['date_stamp'] );

        return true;
    }

    /**
     * @group Punch_testRoundingA
     */
    public function testRoundingA()
    {
        global $dd;

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $policy_ids['round'][] = $this->createRoundingPolicy($this->company_id, 10); //In
        $policy_ids['round'][] = $this->createRoundingPolicy($this->company_id, 20); //Out

        //Create Policy Group
        $dd->createPolicyGroup($this->company_id,
            null,
            null,
            null,
            null,
            null,
            $policy_ids['round'],
            array($this->user_id));

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 8:03AM'),
            strtotime($date_stamp . ' 4:46PM'),
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

        $this->assertEquals($punch_arr[$date_epoch][0]['shift_data']['punches'][0]['time_stamp'], strtotime($date_stamp . ' 8:00AM'));
        $this->assertEquals($punch_arr[$date_epoch][0]['shift_data']['punches'][1]['time_stamp'], strtotime($date_stamp . ' 4:45PM'));

        $udt_arr = $this->getUserDateTotalArray($date_epoch, $date_epoch);
        //Total Time
        $this->assertEquals(5, $udt_arr[$date_epoch][0]['object_type_id']);
        $this->assertEquals((8.75 * 3600), $udt_arr[$date_epoch][0]['total_time']);

        return true;
    }

    public function createRoundingPolicy($company_id, $type)
    {
        $ripf = TTnew('RoundIntervalPolicyFactory');
        $ripf->setCompany($company_id);

        switch ($type) {
            case 10: //In - Up
                $ripf->setName('5min [1]');
                $ripf->setPunchType(40); //In
                $ripf->setRoundType(30); //Up
                $ripf->setInterval((60 * 5)); //5mins
                $ripf->setGrace((60 * 3)); //3min
                $ripf->setStrict(false);
                break;
            case 11: //In - Down
                $ripf->setName('5min [1]');
                $ripf->setPunchType(40); //In
                $ripf->setRoundType(10); //Down
                $ripf->setInterval((60 * 5)); //5mins
                $ripf->setGrace((60 * 3)); //3min
                $ripf->setStrict(false);
                break;
            case 20: //Out
                $ripf->setName('5min [2]');
                $ripf->setPunchType(50); //In
                $ripf->setRoundType(10); //Down
                $ripf->setInterval((60 * 5)); //5mins
                $ripf->setGrace((60 * 3)); //3min
                $ripf->setStrict(false);
                break;
            case 30: //Day total
                $ripf->setName('15min [3]');
                $ripf->setPunchType(120); //In
                $ripf->setRoundType(10); //Down
                $ripf->setInterval((60 * 15)); //15mins
                $ripf->setGrace((60 * 3)); //3min
                $ripf->setStrict(false);
                break;
            case 40: //Lunch total
                $ripf->setName('15min [4]');
                $ripf->setPunchType(100); //In
                $ripf->setRoundType(10); //Down
                $ripf->setInterval((60 * 15)); //15mins
                $ripf->setGrace((60 * 3)); //3min
                $ripf->setStrict(false);
                break;
            case 50: //Break total
                $ripf->setName('15min [5]');
                $ripf->setPunchType(110); //In
                $ripf->setRoundType(10); //Down
                $ripf->setInterval((60 * 15)); //15mins
                $ripf->setGrace((60 * 3)); //3min
                $ripf->setStrict(false);
                break;
            case 110: //In - Static Time Condition
                $ripf->setName('15min [6]');
                $ripf->setPunchType(40); //In
                $ripf->setRoundType(10); //Down
                $ripf->setInterval((60 * 5)); //5mins
                $ripf->setGrace(0); //0min
                $ripf->setStrict(false);
                /*
                                                        'condition_type_id' => 'ConditionType',
                                                        'condition_static_time' => 'ConditionStaticTime',
                                                        'condition_static_total_time' => 'ConditionStaticTotalTime',
                                                        'condition_start_window' => 'ConditionStartWindow',
                                                        'condition_end_window' => 'ConditionEndWindow',
                */
                $ripf->setConditionType(30); //Static Time
                $ripf->setConditionStaticTime(strtotime('8:00 AM'));
                $ripf->setConditionStartWindow(900); //15 Min
                $ripf->setConditionStopWindow(900); //15 Min
                break;
            case 111: //In - Schedule Time Condition
                $ripf->setName('15min [6b]');
                $ripf->setPunchType(40); //In
                $ripf->setRoundType(10); //Down
                $ripf->setInterval((60 * 5)); //5mins
                $ripf->setGrace(0); //0min
                $ripf->setStrict(false);

                $ripf->setConditionType(10); //Schedule Time
                $ripf->setConditionStartWindow(900); //15 Min
                $ripf->setConditionStopWindow(900); //15 Min
                break;
            case 120: //Out - Static Time Condition
                $ripf->setName('5min [7]');
                $ripf->setPunchType(50); //Out
                $ripf->setRoundType(10); //Down
                $ripf->setInterval((60 * 5)); //5mins
                $ripf->setGrace(0); //3min
                $ripf->setStrict(false);

                $ripf->setConditionType(30); //Static Time
                $ripf->setConditionStaticTime(strtotime('5:00 PM'));
                $ripf->setConditionStartWindow(900); //15 Min
                $ripf->setConditionStopWindow(900); //15 Min
                break;
            case 121: //Out - Static Time Condition
                $ripf->setName('5min [7b]');
                $ripf->setPunchType(50); //Out
                $ripf->setRoundType(10); //Down
                $ripf->setInterval((60 * 5)); //5mins
                $ripf->setGrace(0); //3min
                $ripf->setStrict(false);

                $ripf->setConditionType(10); //Static Time
                $ripf->setConditionStartWindow(900); //15 Min
                $ripf->setConditionStopWindow(900); //15 Min
                break;
            case 130: //Day Total - Static Total Time Condition
                $ripf->setName('15min [8]');
                $ripf->setPunchType(120); //Day Total
                $ripf->setRoundType(10); //Down
                $ripf->setInterval((60 * 5)); //15mins
                $ripf->setGrace(0); //3min
                $ripf->setStrict(false);

                $ripf->setConditionType(40); //Static Total Time
                $ripf->setConditionStaticTotalTime((9 * 3600));
                $ripf->setConditionStartWindow(900); //15 Min
                $ripf->setConditionStopWindow(900); //15 Min
                break;

        }

        if ($ripf->isValid()) {
            $insert_id = $ripf->Save();
            Debug::Text('Rounding Policy ID: ' . $insert_id, __FILE__, __LINE__, __METHOD__, 10);

            return $insert_id;
        }

        Debug::Text('Failed Creating Rounding Policy!', __FILE__, __LINE__, __METHOD__, 10);

        return false;
    }

    /**
     * @group Punch_testRoundingB
     */
    public function testRoundingB()
    {
        global $dd;

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $policy_ids['round'][] = $this->createRoundingPolicy($this->company_id, 30); //Day Total

        //Create Policy Group
        $dd->createPolicyGroup($this->company_id,
            null,
            null,
            null,
            null,
            null,
            $policy_ids['round'],
            array($this->user_id));

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 8:03AM'),
            null,
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

        $dd->createPunchPair($this->user_id,
            null,
            strtotime($date_stamp . ' 5:12PM'),
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

        $this->assertEquals($punch_arr[$date_epoch][0]['shift_data']['punches'][0]['time_stamp'], strtotime($date_stamp . ' 8:03AM'));
        $this->assertEquals($punch_arr[$date_epoch][0]['shift_data']['punches'][1]['time_stamp'], strtotime($date_stamp . ' 5:03PM'));

        $udt_arr = $this->getUserDateTotalArray($date_epoch, $date_epoch);
        //Total Time
        $this->assertEquals(5, $udt_arr[$date_epoch][0]['object_type_id']);
        $this->assertEquals((9 * 3600), $udt_arr[$date_epoch][0]['total_time']);

        return true;
    }

    /**
     * @group Punch_testRoundingC
     */
    public function testRoundingC()
    {
        global $dd;

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $policy_ids['round'][] = $this->createRoundingPolicy($this->company_id, 30); //Day Total
        $policy_ids['round'][] = $this->createRoundingPolicy($this->company_id, 40); //Lunch Total

        //Create Policy Group
        $dd->createPolicyGroup($this->company_id,
            null,
            null,
            null,
            null,
            null,
            $policy_ids['round'],
            array($this->user_id));

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 8:03AM'),
            null,
            array(
                'in_type_id' => 10,
                'out_type_id' => 20,
                'branch_id' => 0,
                'department_id' => 0,
                'job_id' => 0,
                'job_item_id' => 0,
            ),
            true
        );

        $dd->createPunchPair($this->user_id,
            null,
            strtotime($date_stamp . ' 12:06PM'),
            array(
                'in_type_id' => 10,
                'out_type_id' => 20,
                'branch_id' => 0,
                'department_id' => 0,
                'job_id' => 0,
                'job_item_id' => 0,
            ),
            true
        );

        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 1:12PM'),
            null,
            array(
                'in_type_id' => 20,
                'out_type_id' => 10,
                'branch_id' => 0,
                'department_id' => 0,
                'job_id' => 0,
                'job_item_id' => 0,
            ),
            true
        );

        $dd->createPunchPair($this->user_id,
            null,
            strtotime($date_stamp . ' 5:07PM'),
            array(
                'in_type_id' => 20,
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
        $this->assertEquals(2, count($punch_arr[$date_epoch]));

        $this->assertEquals($punch_arr[$date_epoch][0]['shift_data']['punches'][0]['time_stamp'], strtotime($date_stamp . ' 8:03AM'));
        $this->assertEquals($punch_arr[$date_epoch][0]['shift_data']['punches'][1]['time_stamp'], strtotime($date_stamp . ' 12:06PM'));
        $this->assertEquals($punch_arr[$date_epoch][0]['shift_data']['punches'][2]['time_stamp'], strtotime($date_stamp . ' 1:06PM'));
        $this->assertEquals($punch_arr[$date_epoch][0]['shift_data']['punches'][3]['time_stamp'], strtotime($date_stamp . ' 5:03PM'));

        $udt_arr = $this->getUserDateTotalArray($date_epoch, $date_epoch);
        //Total Time
        $this->assertEquals(5, $udt_arr[$date_epoch][0]['object_type_id']);
        $this->assertEquals((8 * 3600), $udt_arr[$date_epoch][0]['total_time']);

        return true;
    }

    /**
     * @group Punch_testRoundingD
     */
    public function testRoundingD()
    {
        global $dd;

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $policy_ids['round'][] = $this->createRoundingPolicy($this->company_id, 30); //Day Total
        $policy_ids['round'][] = $this->createRoundingPolicy($this->company_id, 40); //Lunch Total
        $policy_ids['round'][] = $this->createRoundingPolicy($this->company_id, 50); //Break Total

        //Create Policy Group
        $dd->createPolicyGroup($this->company_id,
            null,
            null,
            null,
            null,
            null,
            $policy_ids['round'],
            array($this->user_id));

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 8:03AM'),
            null,
            array(
                'in_type_id' => 10,
                'out_type_id' => 20,
                'branch_id' => 0,
                'department_id' => 0,
                'job_id' => 0,
                'job_item_id' => 0,
            ),
            true
        );

        $dd->createPunchPair($this->user_id,
            null,
            strtotime($date_stamp . ' 12:06PM'),
            array(
                'in_type_id' => 10,
                'out_type_id' => 30,
                'branch_id' => 0,
                'department_id' => 0,
                'job_id' => 0,
                'job_item_id' => 0,
            ),
            true
        );

        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 1:12PM'),
            null,
            array(
                'in_type_id' => 30,
                'out_type_id' => 10,
                'branch_id' => 0,
                'department_id' => 0,
                'job_id' => 0,
                'job_item_id' => 0,
            ),
            true
        );

        $dd->createPunchPair($this->user_id,
            null,
            strtotime($date_stamp . ' 5:07PM'),
            array(
                'in_type_id' => 20,
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
        $this->assertEquals(2, count($punch_arr[$date_epoch]));

        $this->assertEquals($punch_arr[$date_epoch][0]['shift_data']['punches'][0]['time_stamp'], strtotime($date_stamp . ' 8:03AM'));
        $this->assertEquals($punch_arr[$date_epoch][0]['shift_data']['punches'][1]['time_stamp'], strtotime($date_stamp . ' 12:06PM'));
        $this->assertEquals($punch_arr[$date_epoch][0]['shift_data']['punches'][2]['time_stamp'], strtotime($date_stamp . ' 1:06PM'));
        $this->assertEquals($punch_arr[$date_epoch][0]['shift_data']['punches'][3]['time_stamp'], strtotime($date_stamp . ' 5:03PM'));

        $udt_arr = $this->getUserDateTotalArray($date_epoch, $date_epoch);
        //Total Time
        $this->assertEquals(5, $udt_arr[$date_epoch][0]['object_type_id']);
        $this->assertEquals((8 * 3600), $udt_arr[$date_epoch][0]['total_time']);

        return true;
    }

    /**
     * @group Punch_testRoundingConditionA
     */
    public function testRoundingConditionA()
    {
        return true;
    }

    /**
     * @group Punch_testRoundingConditionA2
     */
    public function testRoundingConditionA2()
    {
        return true;
    }

    /**
     * @group Punch_testRoundingConditionB
     */
    public function testRoundingConditionB()
    {
        return true;
    }

    /**
     * @group Punch_testRoundingConditionB2
     */
    public function testRoundingConditionB2()
    {
        return true;
    }

    /**
     * @group Punch_testRoundingConditionC
     */
    public function testRoundingConditionC()
    {
        return true;
    }

    /**
     * @group Punch_testRoundingConditionC2
     */
    public function testRoundingConditionC2()
    {
        return true;
    }

    /**
     * @group Punch_testRoundingConditionD
     */
    public function testRoundingConditionD()
    {
        return true;
    }

    /**
     * @group Punch_testRoundingConditionD2
     */
    public function testRoundingConditionD2()
    {
        return true;
    }

    /**
     * @group Punch_testDST
     */
    public function testDSTFall()
    {
        //DST time should be recorded based on the time the employee actually works, therefore one hour more on this day.
        //See US department of labor description: http://www.dol.gov/elaws/esa/flsa/hoursworked/screenER11.asp
        global $dd;

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods(strtotime('01-Jan-2013'));
        $this->getAllPayPeriods();

        $date_epoch = strtotime('02-Nov-2013'); //Use current year
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $date_epoch2 = strtotime('03-Nov-2013'); //Use current year
        $date_stamp2 = TTDate::getDate('DATE', $date_epoch2);

        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 10:00PM'),
            strtotime($date_stamp2 . ' 1:00AM'),
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

        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp2 . ' 1:30AM'),
            strtotime($date_stamp2 . ' 6:30AM'),
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
        $this->assertEquals(2, count($punch_arr[$date_epoch]));
        $this->assertEquals($date_epoch, $punch_arr[$date_epoch][0]['date_stamp']);
        $this->assertEquals($date_epoch, $punch_arr[$date_epoch][1]['date_stamp']);

        $this->assertEquals($punch_arr[$date_epoch][0]['shift_data']['punches'][0]['punch_control_id'], $punch_arr[$date_epoch][1]['shift_data']['punches'][0]['punch_control_id']); //Make sure punch_control_id from both shifts DO match.


        $udt_arr = $this->getUserDateTotalArray($date_epoch, $date_epoch);
        //Total Time
        $this->assertEquals(5, $udt_arr[$date_epoch][0]['object_type_id']);
        $this->assertEquals((9 * 3600), $udt_arr[$date_epoch][0]['total_time']);

        return true;
    }

    /**
     * @group Punch_testDST
     */
    public function testDSTFallB()
    {
        //DST time should be recorded based on the time the employee actually works, therefore one hour more on this day.
        global $dd;

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods(strtotime('01-Jan-2013'));
        $this->getAllPayPeriods();

        $date_epoch = strtotime('02-Nov-2013'); //Use current year
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $date_epoch2 = strtotime('03-Nov-2013'); //Use current year
        $date_stamp2 = TTDate::getDate('DATE', $date_epoch2);

        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 10:00PM'),
            strtotime($date_stamp2 . ' 6:00AM'),
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
        //$this->assertEquals( $date_epoch, $punch_arr[$date_epoch][1]['date_stamp'] );

        //$this->assertEquals( $punch_arr[$date_epoch][0]['shift_data']['punches'][0]['punch_control_id'], $punch_arr[$date_epoch][1]['shift_data']['punches'][0]['punch_control_id'] ); //Make sure punch_control_id from both shifts DO match.


        $udt_arr = $this->getUserDateTotalArray($date_epoch, $date_epoch);
        //Total Time
        $this->assertEquals(5, $udt_arr[$date_epoch][0]['object_type_id']);
        $this->assertEquals((9 * 3600), $udt_arr[$date_epoch][0]['total_time']);

        return true;
    }

    /**
     * @group Punch_testDST
     */
    public function testDSTSpring()
    {
        //DST time should be recorded based on the time the employee actually works, therefore one hour less on this day.
        global $dd;

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods(strtotime('01-Jan-2013'));
        $this->getAllPayPeriods();

        $date_epoch = strtotime('09-Mar-2013'); //Use current year
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $date_epoch2 = strtotime('10-Mar-2013'); //Use current year
        $date_stamp2 = TTDate::getDate('DATE', $date_epoch2);

        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 10:00PM'),
            strtotime($date_stamp2 . ' 1:00AM'),
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

        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp2 . ' 1:30AM'),
            strtotime($date_stamp2 . ' 6:30AM'),
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
        $this->assertEquals(2, count($punch_arr[$date_epoch]));
        $this->assertEquals($date_epoch, $punch_arr[$date_epoch][0]['date_stamp']);
        $this->assertEquals($date_epoch, $punch_arr[$date_epoch][1]['date_stamp']);

        $this->assertEquals($punch_arr[$date_epoch][0]['shift_data']['punches'][0]['punch_control_id'], $punch_arr[$date_epoch][1]['shift_data']['punches'][0]['punch_control_id']); //Make sure punch_control_id from both shifts DO match.


        $udt_arr = $this->getUserDateTotalArray($date_epoch, $date_epoch);
        //Total Time
        $this->assertEquals(5, $udt_arr[$date_epoch][0]['object_type_id']);
        $this->assertEquals((7 * 3600), $udt_arr[$date_epoch][0]['total_time']);

        return true;
    }

    /**
     * @group Punch_testDST
     */
    public function testDSTSpringB()
    {
        //DST time should be recorded based on the time the employee actually works, therefore one hour less on this day.
        global $dd;

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods(strtotime('01-Jan-2013'));
        $this->getAllPayPeriods();

        $date_epoch = strtotime('09-Mar-2013'); //Use current year
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $date_epoch2 = strtotime('10-Mar-2013'); //Use current year
        $date_stamp2 = TTDate::getDate('DATE', $date_epoch2);

        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 10:00PM'),
            strtotime($date_stamp2 . ' 6:00AM'),
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
        //$this->assertEquals( $date_epoch, $punch_arr[$date_epoch][1]['date_stamp'] );

        //$this->assertEquals( $punch_arr[$date_epoch][0]['shift_data']['punches'][0]['punch_control_id'], $punch_arr[$date_epoch][1]['shift_data']['punches'][0]['punch_control_id'] ); //Make sure punch_control_id from both shifts DO match.


        $udt_arr = $this->getUserDateTotalArray($date_epoch, $date_epoch);
        //Total Time
        $this->assertEquals(5, $udt_arr[$date_epoch][0]['object_type_id']);
        $this->assertEquals((7 * 3600), $udt_arr[$date_epoch][0]['total_time']);

        return true;
    }

    /**
     * @group Punch_testScheduleMatchingA
     */
    public function testScheduleMatchingA()
    {
        global $dd;

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods();
        $this->getAllPayPeriods();


        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);


        $meal_policy_id = $this->createMealPolicy(10); //60min autodeduct
        $schedule_policy_id = $this->createSchedulePolicy(10, $meal_policy_id);
        $this->createSchedule($this->user_id, $date_epoch, array(
            'schedule_policy_id' => $schedule_policy_id,
            'start_time' => ' 8:00AM',
            'end_time' => '5:00PM',
        ));

        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 8:00AM'),
            strtotime($date_stamp . ' 5:00PM'),
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
        //print_r($udt_arr);

        //Total Time
        $this->assertEquals(5, $udt_arr[$date_epoch][0]['object_type_id']);
        $this->assertEquals((8 * 3600), $udt_arr[$date_epoch][0]['total_time']);

        $this->assertEquals(1, count($udt_arr));
        $this->assertEquals(2, count($udt_arr[$date_epoch]));

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

    public function createSchedulePolicy($type, $meal_policy_id)
    {
        $spf = TTnew('SchedulePolicyFactory');
        $spf->setCompany($this->company_id);

        switch ($type) {
            case 10: //Normal
                $spf->setName('Schedule Policy');
                //$spf->setAbsencePolicyID( 0 );
                $spf->setStartStopWindow((3600 * 2));
                break;
            case 20: //No Lunch
                $spf->setName('No Lunch');
                //$spf->setAbsencePolicyID( 0 );
                $spf->setStartStopWindow((3600 * 2));
                break;
        }

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
            $sf->setEnableReCalculateDay(false);
            $insert_id = $sf->Save();
            Debug::Text('Schedule ID: ' . $insert_id, __FILE__, __LINE__, __METHOD__, 10);

            return $insert_id;
        }

        Debug::Text('Failed Creating Schedule!', __FILE__, __LINE__, __METHOD__, 10);

        return false;
    }

    /**
     * @group Punch_testScheduleMatchingB
     */
    public function testScheduleMatchingB()
    {
        global $dd;

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods();
        $this->getAllPayPeriods();


        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);


        $meal_policy_id = $this->createMealPolicy(10); //60min autodeduct
        $schedule_policy_id = $this->createSchedulePolicy(10, $meal_policy_id);
        $this->createSchedule($this->user_id, $date_epoch, array(
            'schedule_policy_id' => $schedule_policy_id,
            'start_time' => ' 8:00AM',
            'end_time' => '5:00PM',
        ));

        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 8:00AM'),
            strtotime($date_stamp . ' 2:00PM'),
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

        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 2:00PM'),
            strtotime($date_stamp . ' 5:00PM'),
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
        $this->assertEquals(2, count($punch_arr[$date_epoch]));
        $this->assertEquals($date_epoch, $punch_arr[$date_epoch][0]['date_stamp']);
        $this->assertEquals($date_epoch, $punch_arr[$date_epoch][1]['date_stamp']);

        $this->assertEquals($punch_arr[$date_epoch][0]['shift_data']['punches'][0]['punch_control_id'], $punch_arr[$date_epoch][1]['shift_data']['punches'][0]['punch_control_id']); //Make sure punch_control_id from both shifts DO match.


        $udt_arr = $this->getUserDateTotalArray($date_epoch, $date_epoch);
        //print_r($udt_arr);
        //Total Time
        $this->assertEquals(5, $udt_arr[$date_epoch][0]['object_type_id']);
        $this->assertEquals((8 * 3600), $udt_arr[$date_epoch][0]['total_time']);

        $this->assertEquals(1, count($udt_arr));
        $this->assertEquals(3, count($udt_arr[$date_epoch]));

        return true;
    }

    /**
     * @group Punch_testScheduleMatchingC
     */
    public function testScheduleMatchingC()
    {
        global $dd;

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods();
        $this->getAllPayPeriods();


        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $date_epoch2 = TTDate::getBeginDayEpoch(($date_epoch + 86400 + 3600));
        $date_stamp2 = TTDate::getDate('DATE', $date_epoch2);


        $meal_policy_id = $this->createMealPolicy(10); //60min autodeduct
        $schedule_policy_id = $this->createSchedulePolicy(10, $meal_policy_id);
        $this->createSchedule($this->user_id, $date_epoch, array(
            'schedule_policy_id' => $schedule_policy_id,
            'start_time' => ' 11:00PM',
            'end_time' => '8:00AM',
        ));

        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 11:00PM'),
            strtotime($date_stamp2 . ' 8:00AM'),
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
        //print_r($udt_arr);

        //Total Time
        $this->assertEquals(5, $udt_arr[$date_epoch][0]['object_type_id']);
        $this->assertEquals((8 * 3600), $udt_arr[$date_epoch][0]['total_time']);

        $this->assertEquals(1, count($udt_arr));
        $this->assertEquals(2, count($udt_arr[$date_epoch]));

        return true;
    }

    /**
     * @group Punch_testScheduleMatchingD
     */
    public function testScheduleMatchingD()
    {
        global $dd;

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods();
        $this->getAllPayPeriods();


        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $date_epoch2 = TTDate::getBeginDayEpoch(($date_epoch + 86400 + 3600));
        $date_stamp2 = TTDate::getDate('DATE', $date_epoch2);


        $meal_policy_id = $this->createMealPolicy(10); //60min autodeduct
        $schedule_policy_id = $this->createSchedulePolicy(10, $meal_policy_id);
        $this->createSchedule($this->user_id, $date_epoch, array(
            'schedule_policy_id' => $schedule_policy_id,
            'start_time' => ' 11:00PM',
            'end_time' => '8:00AM',
        ));

        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp2 . ' 12:30AM'),
            strtotime($date_stamp2 . ' 8:00AM'),
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

        $punch_arr = $this->getPunchDataArray(TTDate::getBeginDayEpoch($date_epoch), TTDate::getEndDayEpoch($date_epoch2));
        //print_r($punch_arr);
        $this->assertEquals(1, count($punch_arr)); //Make sure only one day exists.
        $this->assertEquals(1, count($punch_arr[$date_epoch2]));
        $this->assertEquals($date_epoch2, $punch_arr[$date_epoch2][0]['date_stamp']);

        $udt_arr = $this->getUserDateTotalArray($date_epoch2, $date_epoch2);
        //print_r($udt_arr);

        //Total Time
        //$this->assertEquals( 10, $udt_arr[$date_epoch2][0]['status_id'] );
        //$this->assertEquals( 10, $udt_arr[$date_epoch2][0]['type_id'] );
        $this->assertEquals(5, $udt_arr[$date_epoch2][0]['object_type_id']);
        $this->assertEquals((6.5 * 3600), $udt_arr[$date_epoch2][0]['total_time']);

        $this->assertEquals(1, count($udt_arr));
        $this->assertEquals(2, count($udt_arr[$date_epoch2]));

        return true;
    }

    /**
     * @group Punch_testScheduleMatchingE
     */
    public function testScheduleMatchingE()
    {
        global $dd;

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods();
        $this->getAllPayPeriods();


        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $date_epoch2 = TTDate::getBeginDayEpoch(($date_epoch + 86400 + 3600));
        $date_stamp2 = TTDate::getDate('DATE', $date_epoch2);


        $meal_policy_id = $this->createMealPolicy(10); //60min autodeduct
        $schedule_policy_id = $this->createSchedulePolicy(10, $meal_policy_id);
        $this->createSchedule($this->user_id, $date_epoch2, array(
            'schedule_policy_id' => $schedule_policy_id,
            'start_time' => ' 12:30AM',
            'end_time' => '8:00AM',
        ));

        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 11:30PM'),
            strtotime($date_stamp2 . ' 8:00AM'),
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

        $punch_arr = $this->getPunchDataArray(TTDate::getBeginDayEpoch($date_epoch), TTDate::getEndDayEpoch($date_epoch2));
        //print_r($punch_arr);
        $this->assertEquals(1, count($punch_arr)); //Make sure only one day exists.
        $this->assertEquals(1, count($punch_arr[$date_epoch]));
        $this->assertEquals($date_epoch, $punch_arr[$date_epoch][0]['date_stamp']);

        $udt_arr = $this->getUserDateTotalArray($date_epoch, $date_epoch2);
        //print_r($udt_arr);

        //Total Time
        $this->assertEquals(5, $udt_arr[$date_epoch][0]['object_type_id']);
        $this->assertEquals((7.5 * 3600), $udt_arr[$date_epoch][0]['total_time']);

        //$this->assertEquals( 1, count($udt_arr) );
        $this->assertEquals(2, count($udt_arr[$date_epoch]));

        return true;
    }

    /**
     * @group Punch_testScheduleMatchingF
     */
    public function testScheduleMatchingF()
    {
        global $dd;

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods();
        $this->getAllPayPeriods();


        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $date_epoch2 = TTDate::getBeginDayEpoch(($date_epoch + 86400 + 3600));
        $date_stamp2 = TTDate::getDate('DATE', $date_epoch2);


        $meal_policy_id = $this->createMealPolicy(10); //60min autodeduct
        $schedule_policy_id = $this->createSchedulePolicy(10, $meal_policy_id);
        $no_lunch_schedule_policy_id = $this->createSchedulePolicy(20, -1);
        $this->createSchedule($this->user_id, $date_epoch, array(
            'schedule_policy_id' => $schedule_policy_id,
            'start_time' => ' 10:00PM',
            'end_time' => '7:00AM',
        ));

        $this->createSchedule($this->user_id, $date_epoch2, array(
            'schedule_policy_id' => $no_lunch_schedule_policy_id, //No meal policy on this shift.
            'start_time' => ' 7:30AM',
            'end_time' => '4:30PM',
        ));

        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 10:00PM'),
            strtotime($date_stamp2 . ' 7:00AM'),
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

        $punch_arr = $this->getPunchDataArray(TTDate::getBeginDayEpoch($date_epoch), TTDate::getEndDayEpoch($date_epoch2));
        //print_r($punch_arr);
        $this->assertEquals(1, count($punch_arr)); //Make sure only one day exists.
        $this->assertEquals(1, count($punch_arr[$date_epoch]));
        $this->assertEquals($date_epoch, $punch_arr[$date_epoch][0]['date_stamp']);

        $udt_arr = $this->getUserDateTotalArray($date_epoch, $date_epoch2);
        //print_r($udt_arr);

        //Total Time
        $this->assertEquals(5, $udt_arr[$date_epoch][0]['object_type_id']);
        $this->assertEquals((8 * 3600), $udt_arr[$date_epoch][0]['total_time']);

        //$this->assertEquals( 1, count($udt_arr) );
        $this->assertEquals(2, count($udt_arr[$date_epoch]));

        return true;
    }

    /**
     * @group Punch_testScheduleMatchingG
     */
    public function testScheduleMatchingG()
    {
        global $dd;

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods();
        $this->getAllPayPeriods();


        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $date_epoch2 = TTDate::getBeginDayEpoch(($date_epoch + 86400 + 3600));
        $date_stamp2 = TTDate::getDate('DATE', $date_epoch2);


        $meal_policy_id = $this->createMealPolicy(10); //60min autodeduct
        $schedule_policy_id = $this->createSchedulePolicy(10, $meal_policy_id);
        $no_lunch_schedule_policy_id = $this->createSchedulePolicy(20, -1);
        $this->createSchedule($this->user_id, $date_epoch, array(
            'schedule_policy_id' => $no_lunch_schedule_policy_id, //No meal policy on this shift.
            'start_time' => ' 10:00PM',
            'end_time' => '7:00AM',
        ));

        $this->createSchedule($this->user_id, $date_epoch2, array(
            'schedule_policy_id' => $schedule_policy_id,
            'start_time' => ' 7:30AM',
            'end_time' => '4:30PM',
        ));

        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp2 . ' 7:30AM'),
            strtotime($date_stamp2 . ' 4:30PM'),
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

        $punch_arr = $this->getPunchDataArray(TTDate::getBeginDayEpoch($date_epoch), TTDate::getEndDayEpoch($date_epoch2));
        //print_r($punch_arr);
        $this->assertEquals(1, count($punch_arr)); //Make sure only one day exists.
        $this->assertEquals(1, count($punch_arr[$date_epoch2]));
        $this->assertEquals($date_epoch2, $punch_arr[$date_epoch2][0]['date_stamp']);

        $udt_arr = $this->getUserDateTotalArray($date_epoch, $date_epoch2);
        //print_r($udt_arr);

        //Total Time
        $this->assertEquals(5, $udt_arr[$date_epoch2][0]['object_type_id']);
        $this->assertEquals((8 * 3600), $udt_arr[$date_epoch2][0]['total_time']);

        //$this->assertEquals( 1, count($udt_arr) );
        $this->assertEquals(2, count($udt_arr[$date_epoch2]));

        return true;
    }

    /**
     * @group Punch_testDefaultPunchSettingsNoScheduleA
     */
    public function testDefaultPunchSettingsNoScheduleA()
    {
        //No defaults in station or employee profile.
        global $dd;

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);
        $epoch = strtotime($date_stamp . ' 8:00AM');

        $ulf = TTNew('UserListFactory');
        $ulf->getById($this->user_id);
        $user_obj = $ulf->getCurrent();

        $plf = TTNew('PunchFactory');

        $data = $plf->getDefaultPunchSettings($user_obj, $epoch);
        //var_dump($data);

        $this->assertEquals(10, $data['status_id']); //In/Out
        $this->assertEquals(10, $data['type_id']); //Normal/Lunch/Break

        $this->assertEquals(0, $data['branch_id']);
        $this->assertEquals(0, $data['department_id']);
        $this->assertEquals(0, $data['job_id']);
        $this->assertEquals(0, $data['job_item_id']);

        return true;
    }

    /**
     * @group Punch_testDefaultPunchSettingsNoScheduleB
     */
    public function testDefaultPunchSettingsNoScheduleB()
    {
        //Test with default branch/department set in employee profile
        global $dd;

        $this->tmp_branch_id[] = $this->branch_id;
        $this->tmp_branch_id[] = $dd->createBranch($this->company_id, 20);
        $this->tmp_department_id[] = $dd->createDepartment($this->company_id, 10);
        $this->tmp_department_id[] = $dd->createDepartment($this->company_id, 20);
        $this->user_id = $dd->createUser($this->company_id, 10, 0, $this->tmp_branch_id[0], $this->tmp_department_id[0]); //Non-Admin user.

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);
        $epoch = strtotime($date_stamp . ' 8:00AM');

        $ulf = TTNew('UserListFactory');
        $ulf->getById($this->user_id);
        $user_obj = $ulf->getCurrent();

        $plf = TTNew('PunchFactory');

        $data = $plf->getDefaultPunchSettings($user_obj, $epoch);
        //var_dump($data);

        $this->assertEquals(10, $data['status_id']); //In/Out
        $this->assertEquals(10, $data['type_id']); //Normal/Lunch/Break

        $this->assertEquals($this->tmp_branch_id[0], $data['branch_id']);
        $this->assertEquals($this->tmp_department_id[0], $data['department_id']);
        $this->assertEquals(0, $data['job_id']);
        $this->assertEquals(0, $data['job_item_id']);

        return true;
    }

    //
    //Test with default branch/department set in employee profile and station.
    //

    /**
     * @group Punch_testDefaultPunchSettingsNoScheduleD
     */
    public function testDefaultPunchSettingsNoScheduleD()
    {
        //Test with previous Normal punch.
        global $dd;

        $this->tmp_branch_id[] = $this->branch_id;
        $this->tmp_branch_id[] = $dd->createBranch($this->company_id, 20);
        $this->tmp_department_id[] = $dd->createDepartment($this->company_id, 10);
        $this->tmp_department_id[] = $dd->createDepartment($this->company_id, 20);
        $this->user_id = $dd->createUser($this->company_id, 10, 0, $this->tmp_branch_id[0], $this->tmp_department_id[0]); //Non-Admin user.

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 8:00AM'),
            null, //Out
            array(
                'in_type_id' => 10,
                'out_type_id' => 10,
                'branch_id' => $this->tmp_branch_id[0],
                'department_id' => $this->tmp_department_id[0],
                'job_id' => 0,
                'job_item_id' => 0,
            ),
            true
        );

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);
        $epoch = strtotime($date_stamp . ' 5:00PM');

        $ulf = TTNew('UserListFactory');
        $ulf->getById($this->user_id);
        $user_obj = $ulf->getCurrent();

        $plf = TTNew('PunchFactory');

        $data = $plf->getDefaultPunchSettings($user_obj, $epoch);

        $this->assertEquals(20, $data['status_id']); //In/Out
        $this->assertEquals(10, $data['type_id']); //Normal/Lunch/Break

        $this->assertEquals($this->tmp_branch_id[0], $data['branch_id']);
        $this->assertEquals($this->tmp_department_id[0], $data['department_id']);
        $this->assertEquals(0, $data['job_id']);
        $this->assertEquals(0, $data['job_item_id']);

        return true;
    }

    /**
     * @group Punch_testDefaultPunchSettingsNoScheduleE
     */
    public function testDefaultPunchSettingsNoScheduleE()
    {
        //Test with previous Break punch.
        global $dd;

        $this->tmp_branch_id[] = $this->branch_id;
        $this->tmp_branch_id[] = $dd->createBranch($this->company_id, 20);
        $this->tmp_department_id[] = $dd->createDepartment($this->company_id, 10);
        $this->tmp_department_id[] = $dd->createDepartment($this->company_id, 20);
        $this->user_id = $dd->createUser($this->company_id, 10, 0, $this->tmp_branch_id[0], $this->tmp_department_id[0]); //Non-Admin user.

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 8:00AM'),
            strtotime($date_stamp . ' 9:15AM'),
            array(
                'in_type_id' => 10,
                'out_type_id' => 30,
                'branch_id' => $this->tmp_branch_id[0],
                'department_id' => $this->tmp_department_id[0],
                'job_id' => 0,
                'job_item_id' => 0,
            ),
            true
        );

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);
        $epoch = strtotime($date_stamp . ' 9:30AM');

        $ulf = TTNew('UserListFactory');
        $ulf->getById($this->user_id);
        $user_obj = $ulf->getCurrent();

        $plf = TTNew('PunchFactory');

        $data = $plf->getDefaultPunchSettings($user_obj, $epoch);

        $this->assertEquals(10, $data['status_id']); //In/Out
        $this->assertEquals(30, $data['type_id']); //Normal/Lunch/Break

        $this->assertEquals($this->tmp_branch_id[0], $data['branch_id']);
        $this->assertEquals($this->tmp_department_id[0], $data['department_id']);
        $this->assertEquals(0, $data['job_id']);
        $this->assertEquals(0, $data['job_item_id']);

        return true;
    }

    /**
     * @group Punch_testDefaultPunchSettingsNoScheduleF
     */
    public function testDefaultPunchSettingsNoScheduleF()
    {
        //Test with previous Lunch punch.
        global $dd;

        $this->tmp_branch_id[] = $this->branch_id;
        $this->tmp_branch_id[] = $dd->createBranch($this->company_id, 20);
        $this->tmp_department_id[] = $dd->createDepartment($this->company_id, 10);
        $this->tmp_department_id[] = $dd->createDepartment($this->company_id, 20);
        $this->user_id = $dd->createUser($this->company_id, 10, 0, $this->tmp_branch_id[0], $this->tmp_department_id[0]); //Non-Admin user.

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 8:00AM'),
            strtotime($date_stamp . ' 12:00PM'),
            array(
                'in_type_id' => 10,
                'out_type_id' => 20,
                'branch_id' => $this->tmp_branch_id[0],
                'department_id' => $this->tmp_department_id[0],
                'job_id' => 0,
                'job_item_id' => 0,
            ),
            true
        );

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);
        $epoch = strtotime($date_stamp . ' 1:00PM');

        $ulf = TTNew('UserListFactory');
        $ulf->getById($this->user_id);
        $user_obj = $ulf->getCurrent();

        $plf = TTNew('PunchFactory');

        $data = $plf->getDefaultPunchSettings($user_obj, $epoch);

        $this->assertEquals(10, $data['status_id']); //In/Out
        $this->assertEquals(20, $data['type_id']); //Normal/Lunch/Break

        $this->assertEquals($this->tmp_branch_id[0], $data['branch_id']);
        $this->assertEquals($this->tmp_department_id[0], $data['department_id']);
        $this->assertEquals(0, $data['job_id']);
        $this->assertEquals(0, $data['job_item_id']);

        return true;
    }

    /**
     * @group Punch_testDefaultPunchSettingsNoScheduleG
     */
    public function testDefaultPunchSettingsNoScheduleG()
    {
        //Test with split shift.
        global $dd;

        $this->tmp_branch_id[] = $this->branch_id;
        $this->tmp_branch_id[] = $dd->createBranch($this->company_id, 20);
        $this->tmp_department_id[] = $dd->createDepartment($this->company_id, 10);
        $this->tmp_department_id[] = $dd->createDepartment($this->company_id, 20);
        $this->user_id = $dd->createUser($this->company_id, 10, 0, $this->tmp_branch_id[0], $this->tmp_department_id[0]); //Non-Admin user.

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 8:00AM'),
            strtotime($date_stamp . ' 1:00PM'),
            array(
                'in_type_id' => 10,
                'out_type_id' => 10,
                'branch_id' => $this->tmp_branch_id[0],
                'department_id' => $this->tmp_department_id[0],
                'job_id' => 0,
                'job_item_id' => 0,
            ),
            true
        );

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);
        $epoch = strtotime($date_stamp . ' 2:00PM');

        $ulf = TTNew('UserListFactory');
        $ulf->getById($this->user_id);
        $user_obj = $ulf->getCurrent();

        $plf = TTNew('PunchFactory');

        $data = $plf->getDefaultPunchSettings($user_obj, $epoch);

        $this->assertEquals(10, $data['status_id']); //In/Out
        $this->assertEquals(10, $data['type_id']); //Normal/Lunch/Break

        $this->assertEquals($this->tmp_branch_id[0], $data['branch_id']);
        $this->assertEquals($this->tmp_department_id[0], $data['department_id']);
        $this->assertEquals(0, $data['job_id']);
        $this->assertEquals(0, $data['job_item_id']);

        return true;
    }

    /**
     * @group Punch_testDefaultPunchSettingsNoScheduleGB
     */
    public function testDefaultPunchSettingsNoScheduleGB()
    {
        //Test with split shift (B)
        global $dd;

        $this->tmp_branch_id[] = $this->branch_id;
        $this->tmp_branch_id[] = $dd->createBranch($this->company_id, 20);
        $this->tmp_department_id[] = $dd->createDepartment($this->company_id, 10);
        $this->tmp_department_id[] = $dd->createDepartment($this->company_id, 20);
        $this->user_id = $dd->createUser($this->company_id, 10, 0, 0, 0); //Non-Admin user.

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 8:00AM'),
            strtotime($date_stamp . ' 1:00PM'),
            array(
                'in_type_id' => 10,
                'out_type_id' => 10,
                'branch_id' => $this->tmp_branch_id[0],
                'department_id' => $this->tmp_department_id[0],
                'job_id' => 0,
                'job_item_id' => 0,
            ),
            true
        );

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);
        $epoch = strtotime($date_stamp . ' 2:00PM');

        $ulf = TTNew('UserListFactory');
        $ulf->getById($this->user_id);
        $user_obj = $ulf->getCurrent();

        $plf = TTNew('PunchFactory');

        $data = $plf->getDefaultPunchSettings($user_obj, $epoch);

        $this->assertEquals(10, $data['status_id']); //In/Out
        $this->assertEquals(10, $data['type_id']); //Normal/Lunch/Break

        $this->assertEquals(0, $data['branch_id']);
        $this->assertEquals(0, $data['department_id']);
        $this->assertEquals(0, $data['job_id']);
        $this->assertEquals(0, $data['job_item_id']);

        return true;
    }

    /**
     * @group Punch_testDefaultPunchSettingsNoScheduleGC1
     */
    public function testDefaultPunchSettingsNoScheduleGC1()
    {
        //Test with split shift and $new_shift_trigger_time = 0, where the first "shift" is really short, the and 2nd is really long.
        global $dd;

        $this->tmp_branch_id[] = $this->branch_id;
        $this->tmp_branch_id[] = $dd->createBranch($this->company_id, 20);
        $this->tmp_department_id[] = $dd->createDepartment($this->company_id, 10);
        $this->tmp_department_id[] = $dd->createDepartment($this->company_id, 20);
        $this->user_id = $dd->createUser($this->company_id, 10, 0, $this->tmp_branch_id[0], $this->tmp_department_id[0]); //Non-Admin user.

        $this->createPayPeriodSchedule(10, 57600, 0); //Set $new_shift_trigger_time = 0
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 10:00AM'),
            strtotime($date_stamp . ' 12:00PM'),
            array(
                'in_type_id' => 10,
                'out_type_id' => 10,
                'branch_id' => $this->tmp_branch_id[0],
                'department_id' => $this->tmp_department_id[0],
                'job_id' => 0,
                'job_item_id' => 0,
            ),
            true
        );

        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 11:00PM'),
            null,
            array(
                'in_type_id' => 10,
                'out_type_id' => 10,
                'branch_id' => $this->tmp_branch_id[0],
                'department_id' => $this->tmp_department_id[0],
                'job_id' => 0,
                'job_item_id' => 0,
            ),
            true
        );

        $date_epoch = (TTDate::getBeginWeekEpoch(time()) + (86400 + 3601));
        $date_stamp = TTDate::getDate('DATE', $date_epoch);
        $epoch = strtotime($date_stamp . ' 8:00AM');

        $ulf = TTNew('UserListFactory');
        $ulf->getById($this->user_id);
        $user_obj = $ulf->getCurrent();

        $plf = TTNew('PunchFactory');

        $data = $plf->getDefaultPunchSettings($user_obj, $epoch);

        $this->assertEquals(20, $data['status_id']); //In/Out
        $this->assertEquals(10, $data['type_id']); //Normal/Lunch/Break

        $this->assertEquals($this->tmp_branch_id[0], $data['branch_id']);
        $this->assertEquals($this->tmp_department_id[0], $data['department_id']);
        $this->assertEquals(0, $data['job_id']);
        $this->assertEquals(0, $data['job_item_id']);

        return true;
    }

    /**
     * @group Punch_testDefaultPunchSettingsNoScheduleGC2
     */
    public function testDefaultPunchSettingsNoScheduleGC2()
    {
        //Test with split shift and $new_shift_trigger_time = 4hrs, where the first "shift" is really long, causing the 1st and 2nd shifts to be combined and exceed maximum shift time.
        global $dd;

        $this->tmp_branch_id[] = $this->branch_id;
        $this->tmp_branch_id[] = $dd->createBranch($this->company_id, 20);
        $this->tmp_department_id[] = $dd->createDepartment($this->company_id, 10);
        $this->tmp_department_id[] = $dd->createDepartment($this->company_id, 20);
        $this->user_id = $dd->createUser($this->company_id, 10, 0, $this->tmp_branch_id[0], $this->tmp_department_id[0]); //Non-Admin user.

        $this->createPayPeriodSchedule(10, 57600, (3600 * 4)); //Set $new_shift_trigger_time = 4hrs
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 10:00AM'),
            strtotime($date_stamp . ' 09:00PM'),
            array(
                'in_type_id' => 10,
                'out_type_id' => 10,
                'branch_id' => $this->tmp_branch_id[0],
                'department_id' => $this->tmp_department_id[0],
                'job_id' => 0,
                'job_item_id' => 0,
            ),
            true
        );

        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 11:00PM'),
            null,
            array(
                'in_type_id' => 10,
                'out_type_id' => 10,
                'branch_id' => $this->tmp_branch_id[0],
                'department_id' => $this->tmp_department_id[0],
                'job_id' => 0,
                'job_item_id' => 0,
            ),
            true
        );

        $date_epoch = (TTDate::getBeginWeekEpoch(time()) + (86400 + 3601));
        $date_stamp = TTDate::getDate('DATE', $date_epoch);
        $epoch = strtotime($date_stamp . ' 8:00AM');

        $ulf = TTNew('UserListFactory');
        $ulf->getById($this->user_id);
        $user_obj = $ulf->getCurrent();

        $plf = TTNew('PunchFactory');

        $data = $plf->getDefaultPunchSettings($user_obj, $epoch);

        $this->assertEquals(10, $data['status_id']); //In/Out
        $this->assertEquals(10, $data['type_id']); //Normal/Lunch/Break

        $this->assertEquals($this->tmp_branch_id[0], $data['branch_id']);
        $this->assertEquals($this->tmp_department_id[0], $data['department_id']);
        $this->assertEquals(0, $data['job_id']);
        $this->assertEquals(0, $data['job_item_id']);

        return true;
    }

    /**
     * @group Punch_testDefaultPunchSettingsNoScheduleH
     */
    public function testDefaultPunchSettingsNoScheduleH()
    {
        //Test with duplicate punches at the exact same time, including seconds.
        global $dd;

        $this->tmp_branch_id[] = $this->branch_id;
        $this->tmp_branch_id[] = $dd->createBranch($this->company_id, 20);
        $this->tmp_department_id[] = $dd->createDepartment($this->company_id, 10);
        $this->tmp_department_id[] = $dd->createDepartment($this->company_id, 20);
        $this->user_id = $dd->createUser($this->company_id, 10, 0, 0, 0); //Non-Admin user.

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 8:00:00 AM'),
            strtotime($date_stamp . ' 1:00:57 PM'),
            array(
                'in_type_id' => 10,
                'out_type_id' => 10,
                'branch_id' => $this->tmp_branch_id[0],
                'department_id' => $this->tmp_department_id[0],
                'job_id' => 0,
                'job_item_id' => 0,
            ),
            true
        );

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);
        $epoch = strtotime($date_stamp . ' 1:00:57 PM');

        $ulf = TTNew('UserListFactory');
        $ulf->getById($this->user_id);
        $user_obj = $ulf->getCurrent();

        $plf = TTNew('PunchFactory');

        $data = $plf->getDefaultPunchSettings($user_obj, $epoch);

        $this->assertEquals(10, $data['status_id']); //In/Out
        $this->assertEquals(10, $data['type_id']); //Normal/Lunch/Break

        $this->assertEquals(0, $data['branch_id']);
        $this->assertEquals(0, $data['department_id']);
        $this->assertEquals(0, $data['job_id']);
        $this->assertEquals(0, $data['job_item_id']);

        return true;
    }

    /**
     * @group Punch_testDefaultPunchSettingsNoScheduleHB
     */
    public function testDefaultPunchSettingsNoScheduleHB()
    {
        //Test with duplicate punches at the exact same time, including seconds.
        global $dd;

        $this->tmp_branch_id[] = $this->branch_id;
        $this->tmp_branch_id[] = $dd->createBranch($this->company_id, 20);
        $this->tmp_department_id[] = $dd->createDepartment($this->company_id, 10);
        $this->tmp_department_id[] = $dd->createDepartment($this->company_id, 20);
        $this->user_id = $dd->createUser($this->company_id, 10, 0, 0, 0); //Non-Admin user.

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 8:00:00 AM'),
            strtotime($date_stamp . ' 1:00:23 PM'),
            array(
                'in_type_id' => 10,
                'out_type_id' => 10,
                'branch_id' => $this->tmp_branch_id[0],
                'department_id' => $this->tmp_department_id[0],
                'job_id' => 0,
                'job_item_id' => 0,
            ),
            true
        );

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);
        $epoch = strtotime($date_stamp . ' 1:00:23 PM');

        $ulf = TTNew('UserListFactory');
        $ulf->getById($this->user_id);
        $user_obj = $ulf->getCurrent();

        $plf = TTNew('PunchFactory');

        $data = $plf->getDefaultPunchSettings($user_obj, $epoch);

        $this->assertEquals(10, $data['status_id']); //In/Out
        $this->assertEquals(10, $data['type_id']); //Normal/Lunch/Break

        $this->assertEquals(0, $data['branch_id']);
        $this->assertEquals(0, $data['department_id']);
        $this->assertEquals(0, $data['job_id']);
        $this->assertEquals(0, $data['job_item_id']);

        return true;
    }

    /**
     * @group Punch_testDefaultPunchSettingsNoScheduleHC
     */
    public function testDefaultPunchSettingsNoScheduleHC()
    {
        global $dd;

        $this->tmp_branch_id[] = $this->branch_id;
        $this->tmp_branch_id[] = $dd->createBranch($this->company_id, 20);
        $this->tmp_department_id[] = $dd->createDepartment($this->company_id, 10);
        $this->tmp_department_id[] = $dd->createDepartment($this->company_id, 20);
        $this->user_id = $dd->createUser($this->company_id, 10, 0, 0, 0); //Non-Admin user.

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 8:00:00 AM'),
            strtotime($date_stamp . ' 1:00:23 PM'),
            array(
                'in_type_id' => 10,
                'out_type_id' => 10,
                'branch_id' => $this->tmp_branch_id[0],
                'department_id' => $this->tmp_department_id[0],
                'job_id' => 0,
                'job_item_id' => 0,
            ),
            true
        );

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);
        $epoch = strtotime($date_stamp . ' 1:00:23 PM');

        $ulf = TTNew('UserListFactory');
        $ulf->getById($this->user_id);
        $user_obj = $ulf->getCurrent();

        $prev_punch_obj = TTnew('PunchListFactory');
        $status_id = false;
        $type_id = false;

        //Test similar functionality to what the timeclock would use to avoid duplicate punches.
        //This is different than what the mobile app would do though, as the mobile app needs to be able to refresh its default punch settings immediately.
        $plf = TTnew('PunchListFactory');
        $plf->getPreviousPunchByUserIDAndEpoch($user_obj->getId(), $epoch);
        if ($plf->getRecordCount() > 0) {
            $prev_punch_obj = $plf->getCurrent();

            $prev_punch_obj->setUser($user_obj->getId());

            $status_id = $prev_punch_obj->getNextStatus();
            $type_id = $prev_punch_obj->getNextType($epoch); //Detects breaks/lunches too.
        }

        //If previous punch actual time matches current punch time, we can skip the auto-status logic
        //  as its a duplicate punch and shouldn't have a different status. This way its more likely to get rejected as a duplicate.
        $this->assertEquals($prev_punch_obj->getActualTimeStamp(), $epoch);
        $this->assertEquals(1, $plf->getRecordCount());
        $this->assertEquals(10, $status_id); //In

        return true;
    }


    /**
     * @group Punch_testDefaultPunchSettingsScheduleA
     */
    public function testDefaultPunchSettingsScheduleA()
    {
        //No defaults in station or employee profile.
        global $dd;

        $this->tmp_branch_id[] = $this->branch_id;
        $this->tmp_branch_id[] = $dd->createBranch($this->company_id, 20);
        $this->tmp_department_id[] = $dd->createDepartment($this->company_id, 10);
        $this->tmp_department_id[] = $dd->createDepartment($this->company_id, 20);
        $this->user_id = $dd->createUser($this->company_id, 10, 0, $this->tmp_branch_id[0], $this->tmp_department_id[0]); //Non-Admin user.

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $meal_policy_id = $this->createMealPolicy(10); //60min autodeduct
        $schedule_policy_id = $this->createSchedulePolicy(10, $meal_policy_id);
        $this->createSchedule($this->user_id, $date_epoch, array(
            'schedule_policy_id' => $schedule_policy_id,
            'start_time' => ' 8:00AM',
            'end_time' => '5:00PM',
            'branch_id' => 0, //If no branch/department is specified in the schedule, use EE profile.
            'department_id' => 0,
        ));

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);
        $epoch = strtotime($date_stamp . ' 8:00AM');

        $ulf = TTNew('UserListFactory');
        $ulf->getById($this->user_id);
        $user_obj = $ulf->getCurrent();

        $plf = TTNew('PunchFactory');

        $data = $plf->getDefaultPunchSettings($user_obj, $epoch);
        //var_dump($data);

        $this->assertEquals(10, $data['status_id']); //In/Out
        $this->assertEquals(10, $data['type_id']); //Normal/Lunch/Break

        $this->assertEquals($this->tmp_branch_id[0], $data['branch_id']);
        $this->assertEquals($this->tmp_department_id[0], $data['department_id']);
        $this->assertEquals(0, $data['job_id']);
        $this->assertEquals(0, $data['job_item_id']);

        return true;
    }

    /**
     * @group Punch_testDefaultPunchSettingsScheduleB
     */
    public function testDefaultPunchSettingsScheduleB()
    {
        //No defaults in station or employee profile.
        global $dd;

        $this->tmp_branch_id[] = $this->branch_id;
        $this->tmp_branch_id[] = $dd->createBranch($this->company_id, 20);
        $this->tmp_department_id[] = $dd->createDepartment($this->company_id, 10);
        $this->tmp_department_id[] = $dd->createDepartment($this->company_id, 20);
        $this->user_id = $dd->createUser($this->company_id, 10, 0, $this->tmp_branch_id[0], $this->tmp_department_id[0]); //Non-Admin user.

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $meal_policy_id = $this->createMealPolicy(10); //60min autodeduct
        $schedule_policy_id = $this->createSchedulePolicy(10, $meal_policy_id);
        $this->createSchedule($this->user_id, $date_epoch, array(
            'schedule_policy_id' => $schedule_policy_id,
            'start_time' => ' 8:00AM',
            'end_time' => '5:00PM',
            'branch_id' => $this->tmp_branch_id[1],
            'department_id' => $this->tmp_department_id[1],
        ));

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);
        $epoch = strtotime($date_stamp . ' 8:00AM');

        $ulf = TTNew('UserListFactory');
        $ulf->getById($this->user_id);
        $user_obj = $ulf->getCurrent();

        $plf = TTNew('PunchFactory');

        $data = $plf->getDefaultPunchSettings($user_obj, $epoch);
        //var_dump($data);

        $this->assertEquals(10, $data['status_id']); //In/Out
        $this->assertEquals(10, $data['type_id']); //Normal/Lunch/Break

        $this->assertEquals($this->tmp_branch_id[1], $data['branch_id']);
        $this->assertEquals($this->tmp_department_id[1], $data['department_id']);
        $this->assertEquals(0, $data['job_id']);
        $this->assertEquals(0, $data['job_item_id']);

        return true;
    }

    /**
     * @group Punch_testDefaultPunchSettingsScheduleC
     */
    public function testDefaultPunchSettingsScheduleC()
    {
        //Test with previous Normal punch.
        global $dd;

        $this->tmp_branch_id[] = $this->branch_id;
        $this->tmp_branch_id[] = $dd->createBranch($this->company_id, 20);
        $this->tmp_department_id[] = $dd->createDepartment($this->company_id, 10);
        $this->tmp_department_id[] = $dd->createDepartment($this->company_id, 20);
        $this->user_id = $dd->createUser($this->company_id, 10, 0, $this->tmp_branch_id[0], $this->tmp_department_id[0]); //Non-Admin user.

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $meal_policy_id = $this->createMealPolicy(10); //60min autodeduct
        $schedule_policy_id = $this->createSchedulePolicy(10, $meal_policy_id);
        $this->createSchedule($this->user_id, $date_epoch, array(
            'schedule_policy_id' => $schedule_policy_id,
            'start_time' => ' 8:00AM',
            'end_time' => '5:00PM',
            'branch_id' => $this->tmp_branch_id[1],
            'department_id' => $this->tmp_department_id[1],
        ));

        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 8:00AM'),
            null, //Out
            array(
                'in_type_id' => 10,
                'out_type_id' => 10,
                'branch_id' => $this->tmp_branch_id[0],
                'department_id' => $this->tmp_department_id[0],
                'job_id' => 0,
                'job_item_id' => 0,
            ),
            true
        );

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);
        $epoch = strtotime($date_stamp . ' 5:00PM');

        $ulf = TTNew('UserListFactory');
        $ulf->getById($this->user_id);
        $user_obj = $ulf->getCurrent();

        $plf = TTNew('PunchFactory');

        $data = $plf->getDefaultPunchSettings($user_obj, $epoch);

        $this->assertEquals(20, $data['status_id']); //In/Out
        $this->assertEquals(10, $data['type_id']); //Normal/Lunch/Break

        $this->assertEquals($this->tmp_branch_id[0], $data['branch_id']);
        $this->assertEquals($this->tmp_department_id[0], $data['department_id']);
        $this->assertEquals(0, $data['job_id']);
        $this->assertEquals(0, $data['job_item_id']);

        return true;
    }

    /**
     * @group Punch_testDefaultPunchSettingsScheduleD
     */
    public function testDefaultPunchSettingsScheduleD()
    {
        //Test with previous Break punch.
        global $dd;

        $this->tmp_branch_id[] = $this->branch_id;
        $this->tmp_branch_id[] = $dd->createBranch($this->company_id, 20);
        $this->tmp_department_id[] = $dd->createDepartment($this->company_id, 10);
        $this->tmp_department_id[] = $dd->createDepartment($this->company_id, 20);
        $this->user_id = $dd->createUser($this->company_id, 10, 0, $this->tmp_branch_id[0], $this->tmp_department_id[0]); //Non-Admin user.

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $meal_policy_id = $this->createMealPolicy(10); //60min autodeduct
        $schedule_policy_id = $this->createSchedulePolicy(10, $meal_policy_id);
        $this->createSchedule($this->user_id, $date_epoch, array(
            'schedule_policy_id' => $schedule_policy_id,
            'start_time' => ' 8:00AM',
            'end_time' => '5:00PM',
            'branch_id' => $this->tmp_branch_id[1],
            'department_id' => $this->tmp_department_id[1],
        ));

        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 8:00AM'),
            strtotime($date_stamp . ' 9:15AM'),
            array(
                'in_type_id' => 10,
                'out_type_id' => 30,
                'branch_id' => $this->tmp_branch_id[0],
                'department_id' => $this->tmp_department_id[0],
                'job_id' => 0,
                'job_item_id' => 0,
            ),
            true
        );

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);
        $epoch = strtotime($date_stamp . ' 9:30AM');

        $ulf = TTNew('UserListFactory');
        $ulf->getById($this->user_id);
        $user_obj = $ulf->getCurrent();

        $plf = TTNew('PunchFactory');

        $data = $plf->getDefaultPunchSettings($user_obj, $epoch);

        $this->assertEquals(10, $data['status_id']); //In/Out
        $this->assertEquals(30, $data['type_id']); //Normal/Lunch/Break

        $this->assertEquals($this->tmp_branch_id[0], $data['branch_id']);
        $this->assertEquals($this->tmp_department_id[0], $data['department_id']);
        $this->assertEquals(0, $data['job_id']);
        $this->assertEquals(0, $data['job_item_id']);

        return true;
    }

    /**
     * @group Punch_testDefaultPunchSettingsScheduleE
     */
    public function testDefaultPunchSettingsScheduleE()
    {
        //Test with previous Lunch punch.
        global $dd;

        $this->tmp_branch_id[] = $this->branch_id;
        $this->tmp_branch_id[] = $dd->createBranch($this->company_id, 20);
        $this->tmp_department_id[] = $dd->createDepartment($this->company_id, 10);
        $this->tmp_department_id[] = $dd->createDepartment($this->company_id, 20);
        $this->user_id = $dd->createUser($this->company_id, 10, 0, $this->tmp_branch_id[0], $this->tmp_department_id[0]); //Non-Admin user.

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $meal_policy_id = $this->createMealPolicy(10); //60min autodeduct
        $schedule_policy_id = $this->createSchedulePolicy(10, $meal_policy_id);
        $this->createSchedule($this->user_id, $date_epoch, array(
            'schedule_policy_id' => $schedule_policy_id,
            'start_time' => ' 8:00AM',
            'end_time' => '5:00PM',
            'branch_id' => $this->tmp_branch_id[1],
            'department_id' => $this->tmp_department_id[1],
        ));

        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 8:00AM'),
            strtotime($date_stamp . ' 12:00PM'),
            array(
                'in_type_id' => 10,
                'out_type_id' => 20,
                'branch_id' => $this->tmp_branch_id[0],
                'department_id' => $this->tmp_department_id[0],
                'job_id' => 0,
                'job_item_id' => 0,
            ),
            true
        );

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);
        $epoch = strtotime($date_stamp . ' 1:00PM');

        $ulf = TTNew('UserListFactory');
        $ulf->getById($this->user_id);
        $user_obj = $ulf->getCurrent();

        $plf = TTNew('PunchFactory');

        $data = $plf->getDefaultPunchSettings($user_obj, $epoch);

        $this->assertEquals(10, $data['status_id']); //In/Out
        $this->assertEquals(20, $data['type_id']); //Normal/Lunch/Break

        $this->assertEquals($this->tmp_branch_id[0], $data['branch_id']);
        $this->assertEquals($this->tmp_department_id[0], $data['department_id']);
        $this->assertEquals(0, $data['job_id']);
        $this->assertEquals(0, $data['job_item_id']);

        return true;
    }

    /**
     * @group Punch_testDefaultPunchSettingsScheduleF
     */
    public function testDefaultPunchSettingsScheduleF()
    {
        //Test with split shift.
        global $dd;

        $this->tmp_branch_id[] = $this->branch_id;
        $this->tmp_branch_id[] = $dd->createBranch($this->company_id, 20);
        $this->tmp_department_id[] = $dd->createDepartment($this->company_id, 10);
        $this->tmp_department_id[] = $dd->createDepartment($this->company_id, 20);
        $this->user_id = $dd->createUser($this->company_id, 10, 0, $this->tmp_branch_id[0], $this->tmp_department_id[0]); //Non-Admin user.

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $meal_policy_id = $this->createMealPolicy(10); //60min autodeduct
        $schedule_policy_id = $this->createSchedulePolicy(10, $meal_policy_id);
        $this->createSchedule($this->user_id, $date_epoch, array(
            'schedule_policy_id' => $schedule_policy_id,
            'start_time' => ' 8:00AM',
            'end_time' => '5:00PM',
            'branch_id' => $this->tmp_branch_id[1],
            'department_id' => $this->tmp_department_id[1],
        ));

        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 8:00AM'),
            strtotime($date_stamp . ' 1:00PM'),
            array(
                'in_type_id' => 10,
                'out_type_id' => 10,
                'branch_id' => $this->tmp_branch_id[0],
                'department_id' => $this->tmp_department_id[0],
                'job_id' => 0,
                'job_item_id' => 0,
            ),
            true
        );

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);
        $epoch = strtotime($date_stamp . ' 2:00PM');

        $ulf = TTNew('UserListFactory');
        $ulf->getById($this->user_id);
        $user_obj = $ulf->getCurrent();

        $plf = TTNew('PunchFactory');

        $data = $plf->getDefaultPunchSettings($user_obj, $epoch);

        $this->assertEquals(10, $data['status_id']); //In/Out
        $this->assertEquals(10, $data['type_id']); //Normal/Lunch/Break

        $this->assertEquals($this->tmp_branch_id[1], $data['branch_id']);
        $this->assertEquals($this->tmp_department_id[1], $data['department_id']);
        $this->assertEquals(0, $data['job_id']);
        $this->assertEquals(0, $data['job_item_id']);

        return true;
    }

    /**
     * @group Punch_testDefaultPunchSettingsScheduleFB
     */
    public function testDefaultPunchSettingsScheduleFB()
    {
        //Test with split shift (B)
        global $dd;

        $this->tmp_branch_id[] = $this->branch_id;
        $this->tmp_branch_id[] = $dd->createBranch($this->company_id, 20);
        $this->tmp_department_id[] = $dd->createDepartment($this->company_id, 10);
        $this->tmp_department_id[] = $dd->createDepartment($this->company_id, 20);
        $this->user_id = $dd->createUser($this->company_id, 10, 0, 0, 0); //Non-Admin user.

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $meal_policy_id = $this->createMealPolicy(10); //60min autodeduct
        $schedule_policy_id = $this->createSchedulePolicy(10, $meal_policy_id);
        $this->createSchedule($this->user_id, $date_epoch, array(
            'schedule_policy_id' => $schedule_policy_id,
            'start_time' => ' 8:00AM',
            'end_time' => '5:00PM',
            'branch_id' => $this->tmp_branch_id[1],
            'department_id' => $this->tmp_department_id[1],
        ));

        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 8:00AM'),
            strtotime($date_stamp . ' 1:00PM'),
            array(
                'in_type_id' => 10,
                'out_type_id' => 10,
                'branch_id' => $this->tmp_branch_id[0],
                'department_id' => $this->tmp_department_id[0],
                'job_id' => 0,
                'job_item_id' => 0,
            ),
            true
        );

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);
        $epoch = strtotime($date_stamp . ' 2:00PM');

        $ulf = TTNew('UserListFactory');
        $ulf->getById($this->user_id);
        $user_obj = $ulf->getCurrent();

        $plf = TTNew('PunchFactory');

        $data = $plf->getDefaultPunchSettings($user_obj, $epoch);

        $this->assertEquals(10, $data['status_id']); //In/Out
        $this->assertEquals(10, $data['type_id']); //Normal/Lunch/Break

        $this->assertEquals($this->tmp_branch_id[1], $data['branch_id']);
        $this->assertEquals($this->tmp_department_id[1], $data['department_id']);
        $this->assertEquals(0, $data['job_id']);
        $this->assertEquals(0, $data['job_item_id']);

        return true;
    }

    /**
     * @group Punch_testDefaultPunchSettingsScheduleFC
     */
    public function testDefaultPunchSettingsScheduleFC()
    {
        //Test with split shift (C)
        global $dd;

        $this->tmp_branch_id[] = $this->branch_id;
        $this->tmp_branch_id[] = $dd->createBranch($this->company_id, 20);
        $this->tmp_department_id[] = $dd->createDepartment($this->company_id, 10);
        $this->tmp_department_id[] = $dd->createDepartment($this->company_id, 20);
        $this->user_id = $dd->createUser($this->company_id, 10, 0, 0, 0); //Non-Admin user.

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $meal_policy_id = $this->createMealPolicy(10); //60min autodeduct
        $schedule_policy_id = $this->createSchedulePolicy(10, $meal_policy_id);
        $this->createSchedule($this->user_id, $date_epoch, array(
            'schedule_policy_id' => $schedule_policy_id,
            'start_time' => ' 8:00AM',
            'end_time' => '1:00PM',
            'branch_id' => $this->tmp_branch_id[0],
            'department_id' => $this->tmp_department_id[0],
        ));

        $this->createSchedule($this->user_id, $date_epoch, array(
            'schedule_policy_id' => $schedule_policy_id,
            'start_time' => ' 2:00PM',
            'end_time' => '5:00PM',
            'branch_id' => $this->tmp_branch_id[1],
            'department_id' => $this->tmp_department_id[1],
        ));
        /*
        $dd->createPunchPair( 	$this->user_id,
                                strtotime($date_stamp.' 8:00AM'),
                                strtotime($date_stamp.' 1:00PM'),
                                array(
                                            'in_type_id' => 10,
                                            'out_type_id' => 10,
                                            'branch_id' => $this->tmp_branch_id[0],
                                            'department_id' => $this->tmp_department_id[0],
                                            'job_id' => 0,
                                            'job_item_id' => 0,
                                        ),
                                TRUE
                                );
        */
        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);
        $epoch = strtotime($date_stamp . ' 8:00AM');

        $ulf = TTNew('UserListFactory');
        $ulf->getById($this->user_id);
        $user_obj = $ulf->getCurrent();

        $plf = TTNew('PunchFactory');

        $data = $plf->getDefaultPunchSettings($user_obj, $epoch);

        $this->assertEquals(10, $data['status_id']); //In/Out
        $this->assertEquals(10, $data['type_id']); //Normal/Lunch/Break

        $this->assertEquals($this->tmp_branch_id[0], $data['branch_id']);
        $this->assertEquals($this->tmp_department_id[0], $data['department_id']);
        $this->assertEquals(0, $data['job_id']);
        $this->assertEquals(0, $data['job_item_id']);

        return true;
    }

    /**
     * @group Punch_testDefaultPunchSettingsScheduleFD
     */
    public function testDefaultPunchSettingsScheduleFD()
    {
        //Test with split shift (D)
        global $dd;

        $this->tmp_branch_id[] = $this->branch_id;
        $this->tmp_branch_id[] = $dd->createBranch($this->company_id, 20);
        $this->tmp_department_id[] = $dd->createDepartment($this->company_id, 10);
        $this->tmp_department_id[] = $dd->createDepartment($this->company_id, 20);
        $this->user_id = $dd->createUser($this->company_id, 10, 0, 0, 0); //Non-Admin user.

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $meal_policy_id = $this->createMealPolicy(10); //60min autodeduct
        $schedule_policy_id = $this->createSchedulePolicy(10, $meal_policy_id);
        $this->createSchedule($this->user_id, $date_epoch, array(
            'schedule_policy_id' => $schedule_policy_id,
            'start_time' => ' 8:00AM',
            'end_time' => '1:00PM',
            'branch_id' => $this->tmp_branch_id[0],
            'department_id' => $this->tmp_department_id[0],
        ));

        $this->createSchedule($this->user_id, $date_epoch, array(
            'schedule_policy_id' => $schedule_policy_id,
            'start_time' => ' 2:00PM',
            'end_time' => '5:00PM',
            'branch_id' => $this->tmp_branch_id[1],
            'department_id' => $this->tmp_department_id[1],
        ));

        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 8:00AM'),
            false, //strtotime($date_stamp.' 1:00PM'),
            array(
                'in_type_id' => 10,
                'out_type_id' => 10,
                'branch_id' => $this->tmp_branch_id[0],
                'department_id' => $this->tmp_department_id[0],
                'job_id' => 0,
                'job_item_id' => 0,
            ),
            true
        );

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);
        $epoch = strtotime($date_stamp . ' 1:00PM');

        $ulf = TTNew('UserListFactory');
        $ulf->getById($this->user_id);
        $user_obj = $ulf->getCurrent();

        $plf = TTNew('PunchFactory');

        $data = $plf->getDefaultPunchSettings($user_obj, $epoch);

        $this->assertEquals(10, $data['status_id']); //In/Out
        $this->assertEquals(10, $data['type_id']); //Normal/Lunch/Break

        $this->assertEquals($this->tmp_branch_id[0], $data['branch_id']);
        $this->assertEquals($this->tmp_department_id[0], $data['department_id']);
        $this->assertEquals(0, $data['job_id']);
        $this->assertEquals(0, $data['job_item_id']);

        return true;
    }

    /**
     * @group Punch_testDefaultPunchSettingsScheduleFE
     */
    public function testDefaultPunchSettingsScheduleFE()
    {
        //Test with split shift (E)
        global $dd;

        $this->tmp_branch_id[] = $this->branch_id;
        $this->tmp_branch_id[] = $dd->createBranch($this->company_id, 20);
        $this->tmp_department_id[] = $dd->createDepartment($this->company_id, 10);
        $this->tmp_department_id[] = $dd->createDepartment($this->company_id, 20);
        $this->user_id = $dd->createUser($this->company_id, 10, 0, 0, 0); //Non-Admin user.

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $meal_policy_id = $this->createMealPolicy(10); //60min autodeduct
        $schedule_policy_id = $this->createSchedulePolicy(10, $meal_policy_id);
        $this->createSchedule($this->user_id, $date_epoch, array(
            'schedule_policy_id' => $schedule_policy_id,
            'start_time' => ' 8:00AM',
            'end_time' => '1:00PM',
            'branch_id' => $this->tmp_branch_id[0],
            'department_id' => $this->tmp_department_id[0],
        ));

        $this->createSchedule($this->user_id, $date_epoch, array(
            'schedule_policy_id' => $schedule_policy_id,
            'start_time' => ' 2:00PM',
            'end_time' => '5:00PM',
            'branch_id' => $this->tmp_branch_id[1],
            'department_id' => $this->tmp_department_id[1],
        ));

        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 8:00AM'),
            null, //strtotime($date_stamp.' 1:00PM'),
            array(
                'in_type_id' => 10,
                'out_type_id' => 10,
                'branch_id' => $this->tmp_branch_id[0],
                'department_id' => $this->tmp_department_id[0],
                'job_id' => 0,
                'job_item_id' => 0,
            ),
            true
        );

        $date_epoch = TTDate::getBeginWeekEpoch(time());
        $date_stamp = TTDate::getDate('DATE', $date_epoch);
        $epoch = strtotime($date_stamp . ' 2:10PM');

        $ulf = TTNew('UserListFactory');
        $ulf->getById($this->user_id);
        $user_obj = $ulf->getCurrent();

        $plf = TTNew('PunchFactory');

        $data = $plf->getDefaultPunchSettings($user_obj, $epoch);

        $this->assertEquals(20, $data['status_id']); //In/Out
        $this->assertEquals(10, $data['type_id']); //Normal/Lunch/Break

        $this->assertEquals($this->tmp_branch_id[0], $data['branch_id']);
        $this->assertEquals($this->tmp_department_id[0], $data['department_id']);
        $this->assertEquals(0, $data['job_id']);
        $this->assertEquals(0, $data['job_item_id']);

        return true;
    }

    /**
     * @group Punch_testMaximumShiftTimeA
     */
    public function testMaximumShiftTimeA()
    {
        global $dd;

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $date_epoch = TTDate::getBeginWeekEpoch(time(), 1); //Start weeks on Monday so DST switchover does cause problems.
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $date_epoch2 = TTDate::getBeginDayEpoch((TTDate::getBeginWeekEpoch(time(), 1) + 86400 + 3600));
        $date_stamp2 = TTDate::getDate('DATE', $date_epoch2);

        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 1:00AM'),
            strtotime($date_stamp . ' 4:30PM'),
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

        $punch_arr = $this->getPunchDataArray(TTDate::getBeginDayEpoch($date_epoch, 1), TTDate::getEndDayEpoch($date_epoch2, 1));
        //print_r($punch_arr);

        $this->assertEquals(2, count($punch_arr[$date_epoch][0]['shift_data']['punches']));
        $this->assertEquals(1, count($punch_arr[$date_epoch][0]['shift_data']['punch_control_ids']));
        //$this->assertEquals( $date_epoch, $punch_arr[$date_epoch][0]['date_stamp'] );

        $udt_arr = $this->getUserDateTotalArray($date_epoch, $date_epoch2);
        //Total Time
        $this->assertEquals(5, $udt_arr[$date_epoch][0]['object_type_id']);
        $this->assertEquals((15.5 * 3600), $udt_arr[$date_epoch][0]['total_time']);

        return true;
    }

    /**
     * @group Punch_testMaximumShiftTimeB
     */
    public function testMaximumShiftTimeB()
    {
        global $dd;

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $date_epoch = TTDate::getBeginWeekEpoch(time(), 1);
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $date_epoch2 = TTDate::getBeginDayEpoch((TTDate::getBeginWeekEpoch(time(), 1) + 86400 + 3600));
        $date_stamp2 = TTDate::getDate('DATE', $date_epoch2);

        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 1:00AM'),
            strtotime($date_stamp . ' 5:30PM'),
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

        $punch_arr = $this->getPunchDataArray(TTDate::getBeginDayEpoch($date_epoch, 1), TTDate::getEndDayEpoch($date_epoch2, 1));
        //print_r($punch_arr);

        $this->assertEquals(0, count($punch_arr[$date_epoch][0]['shift_data']['punches']));
        $this->assertEquals(0, count($punch_arr[$date_epoch][0]['shift_data']['punch_control_ids']));

        $udt_arr = $this->getUserDateTotalArray($date_epoch, $date_epoch2);

        $this->assertEquals(0, count($udt_arr));

        return true;
    }

    /**
     * @group Punch_testMaximumShiftTimeC
     */
    public function testMaximumShiftTimeC()
    {
        global $dd;

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $date_epoch = TTDate::getBeginWeekEpoch(time(), 1);
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $date_epoch2 = TTDate::getBeginDayEpoch((TTDate::getBeginWeekEpoch(time(), 1) + 86400 + 3600));
        $date_stamp2 = TTDate::getDate('DATE', $date_epoch2);

        //Create two punch pairs with the minimum time between shifts, so they both fall on the same day, but are considered two separate shifts.
        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 1:00AM'),
            strtotime($date_stamp . ' 2:30PM'),
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

        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 6:30PM'),
            strtotime($date_stamp2 . ' 6:30AM'),
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

        $punch_arr = $this->getPunchDataArray(TTDate::getBeginDayEpoch($date_epoch, 1), TTDate::getEndDayEpoch($date_epoch2, 1));
        //print_r($punch_arr);

        $this->assertEquals(2, count($punch_arr[$date_epoch][0]['shift_data']['punches']));
        $this->assertEquals(1, count($punch_arr[$date_epoch][0]['shift_data']['punch_control_ids']));

        $this->assertEquals(2, count($punch_arr[$date_epoch][1]['shift_data']['punches']));
        $this->assertEquals(1, count($punch_arr[$date_epoch][1]['shift_data']['punch_control_ids']));

        //$this->assertEquals( $date_epoch, $punch_arr[$date_epoch][0]['date_stamp'] );

        $udt_arr = $this->getUserDateTotalArray($date_epoch, $date_epoch2);
        //Total Time
        $this->assertEquals(5, $udt_arr[$date_epoch][0]['object_type_id']);
        $this->assertEquals((25.5 * 3600), $udt_arr[$date_epoch][0]['total_time']);

        return true;
    }

    /**
     * @group Punch_testMaximumShiftTimeD
     */
    public function testMaximumShiftTimeD()
    {
        global $dd;

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods();
        $this->getAllPayPeriods();

        $date_epoch = TTDate::getBeginWeekEpoch(time(), 1);
        $date_stamp = TTDate::getDate('DATE', $date_epoch);

        $date_epoch2 = TTDate::getBeginDayEpoch((TTDate::getBeginWeekEpoch(time(), 1) + 86400 + 3600));
        $date_stamp2 = TTDate::getDate('DATE', $date_epoch2);

        //Create two punch pairs with LESS than the minimum time between shifts, so they both fall on the same day, but are considered ONE shift and therefore fails.
        //However the last punch must be more than 16hrs away from the previous OUT punch (2:30PM)
        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 1:00AM'),
            strtotime($date_stamp . ' 2:30PM'),
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

        $dd->createPunchPair($this->user_id,
            strtotime($date_stamp . ' 6:15PM'),
            strtotime($date_stamp2 . ' 8:00AM'),
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

        $punch_arr = $this->getPunchDataArray(TTDate::getBeginDayEpoch($date_epoch, 1), TTDate::getEndDayEpoch($date_epoch2, 1));
        //print_r($punch_arr);

        $this->assertEquals(2, count($punch_arr[$date_epoch][0]['shift_data']['punches']));
        $this->assertEquals(1, count($punch_arr[$date_epoch][0]['shift_data']['punch_control_ids']));
        //$this->assertEquals( $date_epoch, $punch_arr[$date_epoch][0]['date_stamp'] );

        $udt_arr = $this->getUserDateTotalArray($date_epoch, $date_epoch2);
        //Total Time
        $this->assertEquals(5, $udt_arr[$date_epoch][0]['object_type_id']);
        $this->assertEquals((13.5 * 3600), $udt_arr[$date_epoch][0]['total_time']);

        return true;
    }

    /**
     * @group SQL_UniqueConstraint
     */
    public function testSQLUniqueConstraintError()
    {
        global $config_vars;

        //This won't work when using a load balancer due to the host name having multiple servers on it.
        if (stripos($config_vars['database']['host'], ',') !== false) {
            return true;
        }

        global $dd;

        $this->createPayPeriodSchedule(10);
        $this->createPayPeriods(strtotime('01-Jan-2013'));
        $this->getAllPayPeriods();

        $date_epoch = strtotime('02-Nov-2013'); //Use current year
        $date_stamp = TTDate::getDate('DATE', $date_epoch);


        $fail_transaction = false;
        $pf = TTnew('PunchFactory');
        $pf->setTransfer(false);
        $pf->setUser($this->user_id);
        $pf->setType(10);
        $pf->setStatus(10);
        $pf->setTimeStamp(strtotime($date_stamp . ' 8:00AM'));

        if ($pf->isNew()) {
            $pf->setActualTimeStamp(strtotime($date_stamp . ' 8:00AM'));
            $pf->setOriginalTimeStamp($pf->getTimeStamp());
        }

        $punch_control_id = $pf->findPunchControlID();
        $pf->setPunchControlID($punch_control_id);
        if ($pf->isValid()) {
            if ($punch_id = $pf->Save(false) === false) {
                Debug::Text(' aFail Transaction: ', __FILE__, __LINE__, __METHOD__, 10);
                $fail_transaction = true;
            }
        } else {
            $fail_transaction = true;
        }

        if ($fail_transaction == false) {
            $pcf = TTnew('PunchControlFactory');
            $pcf->setId($pf->getPunchControlID());
            $pcf->setPunchObject($pf);
            //$pcf->setBranch( 0 );
            //$pcf->setDepartment( 0 );
            $pcf->setEnableCalcUserDateID(true);
            $pcf->setEnableCalcTotalTime(false);
            $pcf->setEnableCalcSystemTotalTime(false);
            $pcf->setEnableCalcWeeklySystemTotalTime(false);
            $pcf->setEnableCalcUserDateTotal(false);
            $pcf->setEnableCalcException(false);

            if ($pcf->isValid() == true) {
                $punch_control_id = $pcf->Save(true, true); //Force lookup

                if ($fail_transaction == false) {
                    Debug::Text('Punch Control ID: ' . $punch_control_id, __FILE__, __LINE__, __METHOD__, 10);
                    $pf->CommitTransaction();
                }
            }
        }

        Debug::text('Punch ID: ' . $punch_id . ' Punch Control ID: ' . $punch_control_id, __FILE__, __LINE__, __METHOD__, 10);

        //Cause a unique constraint failure by trying to do a raw insert using the duplicate values.
        $db2 = ADONewConnection($config_vars['database']['type']);
        $db2->SetFetchMode(ADODB_FETCH_ASSOC);
        $db2->Connect($config_vars['database']['host'], $config_vars['database']['user'], $config_vars['database']['password'], $config_vars['database']['database_name']);

        try {
            $db2->Execute('INSERT INTO punch ( ID, PUNCH_CONTROL_ID, STATION_ID, TYPE_ID, STATUS_ID, TIME_STAMP, ORIGINAL_TIME_STAMP, ACTUAL_TIME_STAMP, CREATED_DATE, CREATED_BY, UPDATED_DATE, UPDATED_BY, LONGITUDE, LATITUDE, POSITION_ACCURACY )
							VALUES ( 3' . ($punch_id + 1) . ', ' . (int)$punch_control_id . ', 111125, 10, 10, \'2013-11-02 8:00:00\', \'2013-11-02 8:00:00\', \'2013-11-02 8:00:00\', 1461089023, 178455, 1461089023, 178455, 0.0000000000, 0.0000000000, 0.0000000000 )');
        } catch (Exception $e) {
            try {
                $this->assertEquals($e->getCode(), -5);
                throw new DBError($e);
            } catch (Exception $e) {
                $this->assertEquals($e->getCode(), 0);
            }
        }

        $pf->FailTransaction();
        $pf->CommitTransaction();
    }

    public function testStationCheckSourceNetMask()
    {
        global $dd;

        include_once('Net/IPv4.php');
        include_once('Net/IPv6.php');

        $ipv4 = '75.157.242.71';
        $ipv6 = '2001:569:f9f0:d900:218:f3ff:fe4e:bd60';
        $this->assertEquals(Net_IPv4::validateIP($ipv4), true);
        $this->assertEquals(Net_IPv4::validateIP($ipv6), false);
        $this->assertEquals(Net_IPv4::ipInNetwork($ipv4, $ipv4), false);
        $this->assertEquals(Net_IPv4::ipInNetwork($ipv4, '75.157.242.71/32'), true);
        $this->assertEquals(Net_IPv4::ipInNetwork($ipv4, '75.157.242.0/24'), true);


        $this->assertEquals(Net_IPv6::checkIPv6($ipv6), true);
        $this->assertEquals(Net_IPv6::checkIPv6($ipv4), false);


        $this->assertEquals(Net_IPv6::isInNetmask($ipv6, $ipv6, 128), true);
        $this->assertEquals(Net_IPv6::isInNetmask($ipv6, '2001:569:f9f0:d900:218:f3ff:fe4e:bd61', 128), false);
        $this->assertEquals(Net_IPv6::isInNetmask($ipv6, '2001:569:f9f0:d900:218:f3ff:fe4e:bd61/128'), false);
        $this->assertEquals(Net_IPv6::isInNetmask($ipv6, '2001:569:f9f0:d900:218:f3ff:fe4e:bd61/128', 4), true); //The specified bits overrides any netmask in the string.

        $this->assertEquals(Net_IPv6::isInNetmask($ipv6, '2001:569:f9f0:d900:218:f3ff:fe4e::', 112), true);
        $this->assertEquals(Net_IPv6::isInNetmask($ipv6, '2001:569:f9f0:d900:218:f3ff:fe4e::/112'), true);
        $this->assertEquals(Net_IPv6::isInNetmask($ipv6, '2001:569:f9f0:d900:218:f3ff:fe4f::', 112), false);
        $this->assertEquals(Net_IPv6::isInNetmask($ipv6, '2001:569:f9f0:d900:218:f3ff:fe4f::/112'), false);
    }


    public function testStationCheckSourceANY()
    {
        global $dd;

        $ipv4 = '75.157.242.71';
        $ipv6 = '2001:569:f9f0:d900:218:f3ff:fe4e:bd60';

        $station_id = $dd->createStation($this->company_id, 'ANY', 'ANY');
        Debug::Text('Station ID: ' . $station_id . ' User ID: ' . $this->user_id, __FILE__, __LINE__, __METHOD__, 10);
        $slf = new StationListFactory();
        $slf->getById($station_id);
        if ($slf->getRecordCount() == 1) {
            $current_station = $slf->getCurrent();
            $station_type = $current_station->getType();
        }
        unset($slf);
        $_SERVER['REMOTE_ADDR'] = $ipv4; //Fake remote IP address for testing.
        $this->assertEquals($current_station->checkAllowed($this->user_id, $station_id, $station_type), true);
        $_SERVER['REMOTE_ADDR'] = $ipv6; //Fake remote IP address for testing.
        $this->assertEquals($current_station->checkAllowed($this->user_id, $station_id, $station_type), true);
    }

    public function testStationCheckSourceIPv4()
    {
        global $dd;

        $ipv4 = '75.157.242.71';
        $ipv6 = '2001:569:f9f0:d900:218:f3ff:fe4e:bd60';

        $station_id = $dd->createStation($this->company_id, $ipv4, 'ANY');
        Debug::Text('Station ID: ' . $station_id . ' User ID: ' . $this->user_id, __FILE__, __LINE__, __METHOD__, 10);
        $slf = new StationListFactory();
        $slf->getById($station_id);
        if ($slf->getRecordCount() == 1) {
            $current_station = $slf->getCurrent();
            $station_type = $current_station->getType();
        }
        unset($slf);
        $_SERVER['REMOTE_ADDR'] = $ipv4; //Fake remote IP address for testing.
        $this->assertEquals($current_station->checkAllowed($this->user_id, $station_id, $station_type), true);
        $_SERVER['REMOTE_ADDR'] = $ipv6; //Fake remote IP address for testing.
        $this->assertEquals($current_station->checkAllowed($this->user_id, $station_id, $station_type), false);
    }

    public function testStationCheckSourceIPv4NetMaskA()
    {
        global $dd;

        $ipv4 = '75.157.242.71';
        $ipv6 = '2001:569:f9f0:d900:218:f3ff:fe4e:bd60';

        $station_id = $dd->createStation($this->company_id, '75.157.242.0/24', 'ANY');
        Debug::Text('Station ID: ' . $station_id . ' User ID: ' . $this->user_id, __FILE__, __LINE__, __METHOD__, 10);
        $slf = new StationListFactory();
        $slf->getById($station_id);
        if ($slf->getRecordCount() == 1) {
            $current_station = $slf->getCurrent();
            $station_type = $current_station->getType();
        }
        unset($slf);
        $_SERVER['REMOTE_ADDR'] = $ipv4; //Fake remote IP address for testing.
        $this->assertEquals($current_station->checkAllowed($this->user_id, $station_id, $station_type), true);
        $_SERVER['REMOTE_ADDR'] = $ipv6; //Fake remote IP address for testing.
        $this->assertEquals($current_station->checkAllowed($this->user_id, $station_id, $station_type), false);
    }

    public function testStationCheckSourceIPv4NetMaskB()
    {
        global $dd;

        $ipv4 = '75.157.242.71';
        $ipv6 = '2001:569:f9f0:d900:218:f3ff:fe4e:bd60';

        $station_id = $dd->createStation($this->company_id, '127.0.0.0/8,75.157.242.0/24', 'ANY');
        Debug::Text('Station ID: ' . $station_id . ' User ID: ' . $this->user_id, __FILE__, __LINE__, __METHOD__, 10);
        $slf = new StationListFactory();
        $slf->getById($station_id);
        if ($slf->getRecordCount() == 1) {
            $current_station = $slf->getCurrent();
            $station_type = $current_station->getType();
        }
        unset($slf);
        $_SERVER['REMOTE_ADDR'] = $ipv4; //Fake remote IP address for testing.
        $this->assertEquals($current_station->checkAllowed($this->user_id, $station_id, $station_type), true);
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1'; //Fake remote IP address for testing.
        $this->assertEquals($current_station->checkAllowed($this->user_id, $station_id, $station_type), true);

        $_SERVER['REMOTE_ADDR'] = '76.1.1.1'; //Fake remote IP address for testing.
        $this->assertEquals($current_station->checkAllowed($this->user_id, $station_id, $station_type), false);

        $_SERVER['REMOTE_ADDR'] = $ipv6; //Fake remote IP address for testing.
        $this->assertEquals($current_station->checkAllowed($this->user_id, $station_id, $station_type), false);
    }

    public function testStationCheckSourceIPv6()
    {
        global $dd;

        $ipv4 = '75.157.242.71';
        $ipv6 = '2001:569:f9f0:d900:218:f3ff:fe4e:bd60';

        $station_id = $dd->createStation($this->company_id, $ipv6, 'ANY');
        Debug::Text('Station ID: ' . $station_id . ' User ID: ' . $this->user_id, __FILE__, __LINE__, __METHOD__, 10);
        $slf = new StationListFactory();
        $slf->getById($station_id);
        if ($slf->getRecordCount() == 1) {
            $current_station = $slf->getCurrent();
            $station_type = $current_station->getType();
        }
        unset($slf);
        $_SERVER['REMOTE_ADDR'] = $ipv4; //Fake remote IP address for testing.
        $this->assertEquals($current_station->checkAllowed($this->user_id, $station_id, $station_type), false);
        $_SERVER['REMOTE_ADDR'] = $ipv6; //Fake remote IP address for testing.
        $this->assertEquals($current_station->checkAllowed($this->user_id, $station_id, $station_type), true);
    }

    public function testStationCheckSourceIPv6NetMaskA()
    {
        global $dd;

        $ipv4 = '75.157.242.71';
        $ipv6 = '2001:569:f9f0:d900:218:f3ff:fe4e:bd60';

        $station_id = $dd->createStation($this->company_id, '2001:569:f9f0:d900:218:f3ff:fe4e:/112', 'ANY');
        Debug::Text('Station ID: ' . $station_id . ' User ID: ' . $this->user_id, __FILE__, __LINE__, __METHOD__, 10);
        $slf = new StationListFactory();
        $slf->getById($station_id);
        if ($slf->getRecordCount() == 1) {
            $current_station = $slf->getCurrent();
            $station_type = $current_station->getType();
        }
        unset($slf);
        $_SERVER['REMOTE_ADDR'] = $ipv4; //Fake remote IP address for testing.
        $this->assertEquals($current_station->checkAllowed($this->user_id, $station_id, $station_type), false);

        $_SERVER['REMOTE_ADDR'] = $ipv6; //Fake remote IP address for testing.
        $this->assertEquals($current_station->checkAllowed($this->user_id, $station_id, $station_type), true);

        $_SERVER['REMOTE_ADDR'] = '2001:569:f9f0:d900:218:f3ff:fe4f:3333'; //Fake remote IP address for testing.
        $this->assertEquals($current_station->checkAllowed($this->user_id, $station_id, $station_type), false);
    }

    public function testStationCheckSourceIPv6NetMaskB()
    {
        global $dd;

        $ipv4 = '75.157.242.71';
        $ipv6 = '2001:569:f9f0:d900:218:f3ff:fe4e:bd60';

        $station_id = $dd->createStation($this->company_id, '2005::/20,2001:569:f9f0:d900:218:f3ff:fe4e:/112', 'ANY');
        Debug::Text('Station ID: ' . $station_id . ' User ID: ' . $this->user_id, __FILE__, __LINE__, __METHOD__, 10);
        $slf = new StationListFactory();
        $slf->getById($station_id);
        if ($slf->getRecordCount() == 1) {
            $current_station = $slf->getCurrent();
            $station_type = $current_station->getType();
        }
        unset($slf);
        $_SERVER['REMOTE_ADDR'] = $ipv4; //Fake remote IP address for testing.
        $this->assertEquals($current_station->checkAllowed($this->user_id, $station_id, $station_type), false);

        $_SERVER['REMOTE_ADDR'] = $ipv6; //Fake remote IP address for testing.
        $this->assertEquals($current_station->checkAllowed($this->user_id, $station_id, $station_type), true);
        $_SERVER['REMOTE_ADDR'] = '2005:569:f9f0:d900:218:f3ff:fe4e:bd60'; //Fake remote IP address for testing.
        $this->assertEquals($current_station->checkAllowed($this->user_id, $station_id, $station_type), true);

        $_SERVER['REMOTE_ADDR'] = '2001:569:f9f0:d900:218:f3ff:fe4f:3333'; //Fake remote IP address for testing.
        $this->assertEquals($current_station->checkAllowed($this->user_id, $station_id, $station_type), false);
        $_SERVER['REMOTE_ADDR'] = '2004:569:f9f0:d900:218:f3ff:fe4f:3333'; //Fake remote IP address for testing.
        $this->assertEquals($current_station->checkAllowed($this->user_id, $station_id, $station_type), false);
    }
}
