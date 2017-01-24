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
 * @group DateTime
 */
class DateTimeTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        Debug::text('Running setUp(): ', __FILE__, __LINE__, __METHOD__, 10);

        TTDate::setTimeZone('Etc/GMT+8', true); //Due to being a singleton and PHPUnit resetting the state, always force the timezone to be set.

        //If using loadbalancer, we need to make a SQL query to initiate at least one connection to a database.
        //This is needed for testTimeZone() to work with the load balancer.
        global $db;
        $db->Execute('SELECT 1');

        return true;
    }

    public function tearDown()
    {
        Debug::text('Running tearDown(): ', __FILE__, __LINE__, __METHOD__, 10);

        return true;
    }

    public function testTimeUnit1()
    {
        Debug::text('Testing Time Unit Format: hh:mm', __FILE__, __LINE__, __METHOD__, 10);

        TTDate::setDateFormat('dMY');
        TTDate::setTimeFormat('g:i A');

        /*
            10 	=> 'hh:mm (2:15)',
            12 	=> 'hh:mm:ss (2:15:59)',
            20 	=> 'Hours (2.25)',
            22 	=> 'Hours (2.241)',
            30 	=> 'Minutes (135)'
        */
        TTDate::setTimeUnitFormat(10);

        $this->assertEquals(TTDate::parseTimeUnit('00:01'), 60);
        $this->assertEquals(TTDate::parseTimeUnit('-00:01'), -60);


        $this->assertEquals(TTDate::parseTimeUnit('01:00'), 3600);
        $this->assertEquals(TTDate::parseTimeUnit('10:00'), 36000);
        $this->assertEquals(TTDate::parseTimeUnit('100:00'), 360000);
        $this->assertEquals(TTDate::parseTimeUnit('1000:00'), 3600000);
        $this->assertEquals(TTDate::parseTimeUnit('1,000:00'), 3600000);
        $this->assertEquals(TTDate::parseTimeUnit('10,000:00'), 36000000);
        $this->assertEquals(TTDate::parseTimeUnit('10,000:01.5'), 36000060);

        $this->assertEquals(TTDate::parseTimeUnit('01'), 3600);
        $this->assertEquals(TTDate::parseTimeUnit('1'), 3600);
        $this->assertEquals(TTDate::parseTimeUnit('-1'), -3600);
        $this->assertEquals(TTDate::parseTimeUnit('1:'), 3600);
        $this->assertEquals(TTDate::parseTimeUnit('1:00:00'), 3600);
        $this->assertEquals(TTDate::parseTimeUnit('1:00:01'), 3601);

        $this->assertEquals(TTDate::parseTimeUnit('00:60'), 3600);
        $this->assertEquals(TTDate::parseTimeUnit(':60'), 3600);
        $this->assertEquals(TTDate::parseTimeUnit(':1'), 60);

        $this->assertEquals(TTDate::parseTimeUnit('1:00:01.5'), 3601);
        $this->assertEquals(TTDate::parseTimeUnit('1:1.5'), 3660);

        //Hybrid mode
        $this->assertEquals(TTDate::parseTimeUnit('1.000'), 3600);
        $this->assertEquals(TTDate::parseTimeUnit('1.00'), 3600);
        $this->assertEquals(TTDate::parseTimeUnit('1'), 3600);
        $this->assertEquals(TTDate::parseTimeUnit('-1'), -3600);
        $this->assertEquals(TTDate::parseTimeUnit('01'), 3600);

        $this->assertEquals(TTDate::parseTimeUnit('0.25'), 900);
        $this->assertEquals(TTDate::parseTimeUnit('0.50'), 1800);

        $this->assertEquals(TTDate::parseTimeUnit('0.34'), 1200); //Automatically rounds to nearest 1min
    }

    public function testTimeUnit2()
    {
        Debug::text('Testing Time Unit Format: Decimal', __FILE__, __LINE__, __METHOD__, 10);

        TTDate::setDateFormat('dMY');
        TTDate::setTimeFormat('g:i A');

        /*
            10 	=> 'hh:mm (2:15)',
            12 	=> 'hh:mm:ss (2:15:59)',
            20 	=> 'Hours (2.25)',
            22 	=> 'Hours (2.241)',
            30 	=> 'Minutes (135)'
        */
        TTDate::setTimeUnitFormat(20);

        $this->assertEquals(TTDate::parseTimeUnit('1.000'), 3600);
        $this->assertEquals(TTDate::parseTimeUnit('1.00'), 3600);
        $this->assertEquals(TTDate::parseTimeUnit('10.00'), 36000);
        $this->assertEquals(TTDate::parseTimeUnit('100.00'), 360000);
        $this->assertEquals(TTDate::parseTimeUnit('1000.00'), 3600000);
        $this->assertEquals(TTDate::parseTimeUnit('1,000.00'), 3600000);
        $this->assertEquals(TTDate::parseTimeUnit('1'), 3600);
        $this->assertEquals(TTDate::parseTimeUnit('-1'), -3600);
        $this->assertEquals(TTDate::parseTimeUnit('01'), 3600);

        $this->assertEquals(TTDate::parseTimeUnit('0.25'), 900);
        $this->assertEquals(TTDate::parseTimeUnit('0.50'), 1800);

        $this->assertEquals(TTDate::parseTimeUnit('0.34'), 1200); //Automatically rounds to nearest 1min

        //Hybrid mode
        $this->assertEquals(TTDate::parseTimeUnit('00:01'), 60);
        $this->assertEquals(TTDate::parseTimeUnit('-00:01'), -60);

        $this->assertEquals(TTDate::parseTimeUnit('01:00'), 3600);
        $this->assertEquals(TTDate::parseTimeUnit('01'), 3600);
        $this->assertEquals(TTDate::parseTimeUnit('1'), 3600);
        $this->assertEquals(TTDate::parseTimeUnit('-1'), -3600);
        $this->assertEquals(TTDate::parseTimeUnit('1:'), 3600);
        $this->assertEquals(TTDate::parseTimeUnit('1:00:00'), 3600);

        $this->assertEquals(TTDate::parseTimeUnit('00:60'), 3600);
        $this->assertEquals(TTDate::parseTimeUnit(':60'), 3600);
        $this->assertEquals(TTDate::parseTimeUnit(':1'), 60);

        $this->assertEquals(TTDate::parseTimeUnit('1:00:01.5'), 3600);
        $this->assertEquals(TTDate::parseTimeUnit('1:1.5'), 3600);
    }

    public function testTimeUnit3()
    {
        Debug::text('Testing Time Unit Format: Decimal', __FILE__, __LINE__, __METHOD__, 10);

        TTDate::setDateFormat('dMY');
        TTDate::setTimeFormat('g:i A');

        /*
            10 	=> 'hh:mm (2:15)',
            12 	=> 'hh:mm:ss (2:15:59)',
            20 	=> 'Hours (2.25)',
            22 	=> 'Hours (2.241)',
            30 	=> 'Minutes (135)'
        */
        TTDate::setTimeUnitFormat(20);

        $this->assertEquals(TTDate::parseTimeUnit('0.02'), (1 * 60));
        $this->assertEquals(TTDate::parseTimeUnit('0.03'), (2 * 60));
        $this->assertEquals(TTDate::parseTimeUnit('0.05'), (3 * 60));
        $this->assertEquals(TTDate::parseTimeUnit('0.07'), (4 * 60));
        $this->assertEquals(TTDate::parseTimeUnit('0.08'), (5 * 60));
        $this->assertEquals(TTDate::parseTimeUnit('0.10'), (6 * 60));
        $this->assertEquals(TTDate::parseTimeUnit('0.12'), (7 * 60));
        $this->assertEquals(TTDate::parseTimeUnit('0.13'), (8 * 60));
        $this->assertEquals(TTDate::parseTimeUnit('0.15'), (9 * 60));
        $this->assertEquals(TTDate::parseTimeUnit('0.17'), (10 * 60));
        $this->assertEquals(TTDate::parseTimeUnit('0.18'), (11 * 60));
        $this->assertEquals(TTDate::parseTimeUnit('0.20'), (12 * 60));
        $this->assertEquals(TTDate::parseTimeUnit('0.22'), (13 * 60));
        $this->assertEquals(TTDate::parseTimeUnit('0.23'), (14 * 60));
        $this->assertEquals(TTDate::parseTimeUnit('0.25'), (15 * 60));

        $this->assertEquals(TTDate::parseTimeUnit('0.27'), (16 * 60));
        $this->assertEquals(TTDate::parseTimeUnit('0.28'), (17 * 60));
        $this->assertEquals(TTDate::parseTimeUnit('0.30'), (18 * 60));
        $this->assertEquals(TTDate::parseTimeUnit('0.32'), (19 * 60));
        $this->assertEquals(TTDate::parseTimeUnit('0.33'), (20 * 60));
        $this->assertEquals(TTDate::parseTimeUnit('0.35'), (21 * 60));
        $this->assertEquals(TTDate::parseTimeUnit('0.37'), (22 * 60));
        $this->assertEquals(TTDate::parseTimeUnit('0.38'), (23 * 60));
        $this->assertEquals(TTDate::parseTimeUnit('0.40'), (24 * 60));
        $this->assertEquals(TTDate::parseTimeUnit('0.42'), (25 * 60));
        $this->assertEquals(TTDate::parseTimeUnit('0.43'), (26 * 60));
        $this->assertEquals(TTDate::parseTimeUnit('0.45'), (27 * 60));
        $this->assertEquals(TTDate::parseTimeUnit('0.47'), (28 * 60));
        $this->assertEquals(TTDate::parseTimeUnit('0.48'), (29 * 60));
        $this->assertEquals(TTDate::parseTimeUnit('0.50'), (30 * 60));


        $this->assertEquals(TTDate::parseTimeUnit('0.52'), (31 * 60));
        $this->assertEquals(TTDate::parseTimeUnit('0.53'), (32 * 60));
        $this->assertEquals(TTDate::parseTimeUnit('0.55'), (33 * 60));
        $this->assertEquals(TTDate::parseTimeUnit('0.57'), (34 * 60));
        $this->assertEquals(TTDate::parseTimeUnit('0.58'), (35 * 60));
        $this->assertEquals(TTDate::parseTimeUnit('0.60'), (36 * 60));
        $this->assertEquals(TTDate::parseTimeUnit('0.62'), (37 * 60));
        $this->assertEquals(TTDate::parseTimeUnit('0.63'), (38 * 60));
        $this->assertEquals(TTDate::parseTimeUnit('0.65'), (39 * 60));
        $this->assertEquals(TTDate::parseTimeUnit('0.67'), (40 * 60));
        $this->assertEquals(TTDate::parseTimeUnit('0.68'), (41 * 60));
        $this->assertEquals(TTDate::parseTimeUnit('0.70'), (42 * 60));
        $this->assertEquals(TTDate::parseTimeUnit('0.72'), (43 * 60));
        $this->assertEquals(TTDate::parseTimeUnit('0.73'), (44 * 60));
        $this->assertEquals(TTDate::parseTimeUnit('0.75'), (45 * 60));

        $this->assertEquals(TTDate::parseTimeUnit('0.77'), (46 * 60));
        $this->assertEquals(TTDate::parseTimeUnit('0.78'), (47 * 60));
        $this->assertEquals(TTDate::parseTimeUnit('0.80'), (48 * 60));
        $this->assertEquals(TTDate::parseTimeUnit('0.82'), (49 * 60));
        $this->assertEquals(TTDate::parseTimeUnit('0.84'), (50 * 60));
        $this->assertEquals(TTDate::parseTimeUnit('0.85'), (51 * 60));
        $this->assertEquals(TTDate::parseTimeUnit('0.87'), (52 * 60));
        $this->assertEquals(TTDate::parseTimeUnit('0.89'), (53 * 60));
        $this->assertEquals(TTDate::parseTimeUnit('0.90'), (54 * 60));
        $this->assertEquals(TTDate::parseTimeUnit('0.92'), (55 * 60));
        $this->assertEquals(TTDate::parseTimeUnit('0.94'), (56 * 60));
        $this->assertEquals(TTDate::parseTimeUnit('0.95'), (57 * 60));
        $this->assertEquals(TTDate::parseTimeUnit('0.97'), (58 * 60));
        $this->assertEquals(TTDate::parseTimeUnit('0.99'), (59 * 60));
        $this->assertEquals(TTDate::parseTimeUnit('1.00'), (60 * 60));
    }

    public function testTimeUnit4()
    {
        Debug::text('Testing Time Unit Format: Decimal', __FILE__, __LINE__, __METHOD__, 10);

        TTDate::setDateFormat('dMY');
        TTDate::setTimeFormat('g:i A');

        /*
            10 	=> 'hh:mm (2:15)',
            12 	=> 'hh:mm:ss (2:15:59)',
            20 	=> 'Hours (2.25)',
            22 	=> 'Hours (2.241)',
            30 	=> 'Minutes (135)'
        */
        TTDate::setTimeUnitFormat(10);
        $this->assertEquals(TTDate::getTimeUnit(3600), '01:00');
        $this->assertEquals(TTDate::getTimeUnit(3660), '01:01');
        $this->assertEquals(TTDate::getTimeUnit(36060), '10:01');
        $this->assertEquals(TTDate::getTimeUnit(36660), '10:11');
        $this->assertEquals(TTDate::getTimeUnit(360660), '100:11');
        $this->assertEquals(TTDate::getTimeUnit(3600660), '1000:11');
        $this->assertEquals(TTDate::getTimeUnit(36000660), '10000:11');
        $this->assertEquals(TTDate::getTimeUnit(360000660), '100000:11');
        $this->assertEquals(TTDate::getTimeUnit(3600000660), '1000000:11');
        //$this->assertEquals( TTDate::getTimeUnit( ( PHP_INT_MAX + PHP_INT_MAX ) ),  				'ERR(FLOAT)' ); //This is passing a float that is losing precision.
        $this->assertEquals(TTDate::getTimeUnit(bcadd(PHP_INT_MAX, PHP_INT_MAX)), '5124095576030431:00');
        $this->assertEquals(TTDate::getTimeUnit(bcadd(bcadd(PHP_INT_MAX, PHP_INT_MAX), 660)), '5124095576030431:11');


        TTDate::setTimeUnitFormat(10);
        $this->assertEquals(TTDate::getTimeUnit(-3600), '-01:00');
        $this->assertEquals(TTDate::getTimeUnit(-3660), '-01:01');
        $this->assertEquals(TTDate::getTimeUnit(-36060), '-10:01');
        $this->assertEquals(TTDate::getTimeUnit(-36660), '-10:11');
        $this->assertEquals(TTDate::getTimeUnit(-360660), '-100:11');
        $this->assertEquals(TTDate::getTimeUnit(-3600660), '-1000:11');
        $this->assertEquals(TTDate::getTimeUnit(-36000660), '-10000:11');
        $this->assertEquals(TTDate::getTimeUnit(-360000660), '-100000:11');
        $this->assertEquals(TTDate::getTimeUnit(-3600000660), '-1000000:11');
        //$this->assertEquals( TTDate::getTimeUnit( ( ( PHP_INT_MAX + PHP_INT_MAX ) * -1 ) ), 		'ERR(FLOAT)' );
        $this->assertEquals(TTDate::getTimeUnit(bcmul(bcadd(PHP_INT_MAX, PHP_INT_MAX), -1)), '-5124095576030431:00');


        TTDate::setTimeUnitFormat(12);
        $this->assertEquals(TTDate::getTimeUnit(3600), '01:00:00');
        $this->assertEquals(TTDate::getTimeUnit(3661), '01:01:01');
        $this->assertEquals(TTDate::getTimeUnit(36060), '10:01:00');
        $this->assertEquals(TTDate::getTimeUnit(36660), '10:11:00');
        $this->assertEquals(TTDate::getTimeUnit(360660), '100:11:00');
        $this->assertEquals(TTDate::getTimeUnit(3600660), '1000:11:00');
        $this->assertEquals(TTDate::getTimeUnit(36000660), '10000:11:00');
        $this->assertEquals(TTDate::getTimeUnit(360000660), '100000:11:00');
        $this->assertEquals(TTDate::getTimeUnit(3600000660), '1000000:11:00');
        //$this->assertEquals( TTDate::getTimeUnit( ( PHP_INT_MAX + PHP_INT_MAX ) ), 	'ERR(FLOAT)' );
        $this->assertEquals(TTDate::getTimeUnit(bcadd(PHP_INT_MAX, PHP_INT_MAX)), '5124095576030431:00:14');

        $this->assertEquals(TTDate::getTimeUnit(bcmul(PHP_INT_MAX, PHP_INT_MAX)), '9223372036854775807:00:49');

        $this->assertEquals(TTDate::getTimeUnit(bcadd(bcmul(PHP_INT_MAX, PHP_INT_MAX), 0.99999)), '9223372036854775807:00:49');
        $this->assertEquals(TTDate::getTimeUnit(bcadd(bcmul(PHP_INT_MAX, PHP_INT_MAX), 0.00001)), '9223372036854775807:00:49');


        TTDate::setTimeUnitFormat(12);
        $this->assertEquals(TTDate::getTimeUnit(-3600), '-01:00:00');
        $this->assertEquals(TTDate::getTimeUnit(-3661), '-01:01:01');
        $this->assertEquals(TTDate::getTimeUnit(-36060), '-10:01:00');
        $this->assertEquals(TTDate::getTimeUnit(-36660), '-10:11:00');
        $this->assertEquals(TTDate::getTimeUnit(-360660), '-100:11:00');
        $this->assertEquals(TTDate::getTimeUnit(-3600660), '-1000:11:00');
        $this->assertEquals(TTDate::getTimeUnit(-36000660), '-10000:11:00');
        $this->assertEquals(TTDate::getTimeUnit(-360000660), '-100000:11:00');
        $this->assertEquals(TTDate::getTimeUnit(-3600000660), '-1000000:11:00');
        //$this->assertEquals( TTDate::getTimeUnit( ( ( PHP_INT_MAX + PHP_INT_MAX ) * -1 ) ), 		'ERR(FLOAT)' );
        $this->assertEquals(TTDate::getTimeUnit(bcmul(bcadd(PHP_INT_MAX, PHP_INT_MAX), -1)), '-5124095576030431:00:14');

        $this->assertEquals(TTDate::getTimeUnit(bcmul(bcmul(PHP_INT_MAX, PHP_INT_MAX), -1)), '-9223372036854775807:00:49');


        TTDate::setTimeUnitFormat(23);
        $this->assertEquals(TTDate::getTimeUnit(3600), '1.000');
        $this->assertEquals(TTDate::getTimeUnit(3660), '1.0167');
        $this->assertEquals(TTDate::getTimeUnit(36060), '10.0167');
        $this->assertEquals(TTDate::getTimeUnit(36660), '10.1833');
        $this->assertEquals(TTDate::getTimeUnit(360660), '100.1833');
        $this->assertEquals(TTDate::getTimeUnit(3600660), '1,000.1833');
        $this->assertEquals(TTDate::getTimeUnit(36000660), '10,000.1833');
        $this->assertEquals(TTDate::getTimeUnit(360000660), '100,000.1833');
        $this->assertEquals(TTDate::getTimeUnit(3600000660), '1,000,000.1833');
        $this->assertEquals(TTDate::getTimeUnit((PHP_INT_MAX + PHP_INT_MAX)), '5,124,095,576,030,431.0000'); //This is passing a float that is losing precision.
        $this->assertEquals(TTDate::getTimeUnit(bcadd(PHP_INT_MAX, PHP_INT_MAX)), '5,124,095,576,030,431.0000');
        $this->assertEquals(TTDate::getTimeUnit(bcadd(bcadd(PHP_INT_MAX, PHP_INT_MAX), 660)), '5,124,095,576,030,431.0000');
    }

    public function testDate_DMY_1()
    {
        Debug::text('Testing Date Format: d-M-y', __FILE__, __LINE__, __METHOD__, 10);

        TTDate::setDateFormat('d-M-y');
        TTDate::setTimeFormat('g:i A');

        $this->assertEquals(TTDate::parseDateTime('25-Feb-05'), 1109318400);

        $this->assertEquals(TTDate::parseDateTime('25-Feb-05 8:09PM'), 1109390940);
        $this->assertEquals(TTDate::parseDateTime('25-Feb-05 8:09 AM'), 1109347740);
        $this->assertEquals(TTDate::parseDateTime('25-Feb-05 8:09:10 AM'), 1109347750);
        $this->assertEquals(TTDate::parseDateTime('25-Feb-05 8:09:10 AM EST'), 1109336950);

        $this->assertEquals(TTDate::parseDateTime('25-Feb-05 18:09'), 1109383740);
        $this->assertEquals(TTDate::parseDateTime('25-Feb-05 18:09:10'), 1109383750);
        $this->assertEquals(TTDate::parseDateTime('25-Feb-05 18:09:10 EST'), 1109372950);


        //Fails on PHP 5.1.2 due to strtotime()
        //TTDate::setDateFormat('d/M/y');
        //TTDate::setTimeFormat('g:i A');

        //$this->assertEquals( TTDate::parseDateTime('25/Feb/05'), 1109318400 );

        //$this->assertEquals( TTDate::parseDateTime('25/Feb/05 8:09PM'), 1109390940 );
        //$this->assertEquals( TTDate::parseDateTime('25/Feb/05 8:09 AM'), 1109347740 );
        //$this->assertEquals( TTDate::parseDateTime('25/Feb/05 8:09:10 AM'), 1109347750 );
        //$this->assertEquals( TTDate::parseDateTime('25/Feb/05 8:09:10 AM EST'), 1109336950 );

        //$this->assertEquals( TTDate::parseDateTime('25/Feb/05 18:09'), 1109383740 );
        //$this->assertEquals( TTDate::parseDateTime('25/Feb/05 18:09:10'), 1109383750 );
        //$this->assertEquals( TTDate::parseDateTime('25/Feb/05 18:09:10 EST'), 1109372950 );


        TTDate::setDateFormat('d-M-Y');
        TTDate::setTimeFormat('g:i A');

        $this->assertEquals(TTDate::parseDateTime('25-Feb-2005'), 1109318400);

        $this->assertEquals(TTDate::parseDateTime('25-Feb-2005 8:09PM'), 1109390940);
        $this->assertEquals(TTDate::parseDateTime('25-Feb-2005 8:09 AM'), 1109347740);
        $this->assertEquals(TTDate::parseDateTime('25-Feb-2005 8:09:10 AM'), 1109347750);
        $this->assertEquals(TTDate::parseDateTime('25-Feb-2005 8:09:10 AM EST'), 1109336950);

        $this->assertEquals(TTDate::parseDateTime('25-Feb-2005 18:09'), 1109383740);
        $this->assertEquals(TTDate::parseDateTime('25-Feb-2005 18:09:10'), 1109383750);
        $this->assertEquals(TTDate::parseDateTime('25-Feb-2005 18:09:10 EST'), 1109372950);

        //Fails on PHP 5.1.2 due to strtotime()

        //TTDate::setDateFormat('d/M/Y');
        //TTDate::setTimeFormat('g:i A');

        //$this->assertEquals( TTDate::parseDateTime('25/Feb/2005'), 1109318400 );

        //$this->assertEquals( TTDate::parseDateTime('25/Feb/2005 8:09PM'), 1109390940 );
        //$this->assertEquals( TTDate::parseDateTime('25/Feb/2005 8:09 AM'), 1109347740 );
        //$this->assertEquals( TTDate::parseDateTime('25/Feb/2005 8:09:10 AM'), 1109347750 );
        //$this->assertEquals( TTDate::parseDateTime('25/Feb/2005 8:09:10 AM EST'), 1109336950 );

        //$this->assertEquals( TTDate::parseDateTime('25/Feb/2005 18:09'), 1109383740 );
        //$this->assertEquals( TTDate::parseDateTime('25/Feb/2005 18:09:10'), 1109383750 );
        //$this->assertEquals( TTDate::parseDateTime('25/Feb/2005 18:09:10 EST'), 1109372950 );
    }

    public function testDate_DMY_2()
    {
        Debug::text('Testing Date Format: dMY', __FILE__, __LINE__, __METHOD__, 10);

        TTDate::setDateFormat('dMY');
        TTDate::setTimeFormat('g:i A');

        $this->assertEquals(TTDate::parseDateTime('25Feb2005'), 1109318400);

        $this->assertEquals(TTDate::parseDateTime('25Feb2005 8:09PM'), 1109390940);
        $this->assertEquals(TTDate::parseDateTime('25Feb2005 8:09 AM'), 1109347740);
        $this->assertEquals(TTDate::parseDateTime('25Feb2005 8:09:10 AM'), 1109347750);
        $this->assertEquals(TTDate::parseDateTime('25Feb2005 8:09:10 AM EST'), 1109336950);

        $this->assertEquals(TTDate::parseDateTime('25Feb2005 18:09'), 1109383740);
        $this->assertEquals(TTDate::parseDateTime('25Feb2005 18:09:10'), 1109383750);
        $this->assertEquals(TTDate::parseDateTime('25Feb2005 18:09:10 EST'), 1109372950);
    }

    public function testDate_DMY_3()
    {
        Debug::text('Testing Date Format: d-m-y', __FILE__, __LINE__, __METHOD__, 10);

        TTDate::setDateFormat('d-m-y');

        TTDate::setTimeFormat('g:i A');
        $this->assertEquals(TTDate::parseDateTime('25-02-2005'), 1109318400);
        $this->assertEquals(TTDate::parseDateTime('25-02-2005 8:09PM'), 1109390940);
        $this->assertEquals(TTDate::parseDateTime('25-02-2005 8:09 AM'), 1109347740);

        TTDate::setTimeFormat('g:i A T');
        $this->assertEquals(TTDate::parseDateTime('25-02-2005 8:09 AM EST'), 1109336940);

        TTDate::setTimeFormat('G:i');
        $this->assertEquals(TTDate::parseDateTime('25-02-2005 18:09'), 1109383740);
        $this->assertEquals(TTDate::parseDateTime('25-02-2005 18:09 EST'), 1109372940);

        //
        // Different separator
        //

        TTDate::setDateFormat('d/m/y');

        TTDate::setTimeFormat('g:i A');
        $this->assertEquals(TTDate::parseDateTime('25/02/2005'), 1109318400);
        $this->assertEquals(TTDate::parseDateTime('25/02/2005 8:09PM'), 1109390940);
        $this->assertEquals(TTDate::parseDateTime('25/02/2005 8:09 AM'), 1109347740);

        TTDate::setTimeFormat('g:i A T');
        $this->assertEquals(TTDate::parseDateTime('25/02/2005 8:09 AM EST'), 1109336940);

        TTDate::setTimeFormat('G:i');
        $this->assertEquals(TTDate::parseDateTime('25/02/2005 18:09'), 1109383740);
        $this->assertEquals(TTDate::parseDateTime('25/02/2005 18:09 EST'), 1109372940);
    }

    public function testDate_MDY_1()
    {
        Debug::text('Testing Date Format: m-d-y', __FILE__, __LINE__, __METHOD__, 10);

        TTDate::setDateFormat('m-d-y');
        //Debug::text('zzz1: ', date_default_timezone_get(), __FILE__, __LINE__, __METHOD__, 10);

        TTDate::setTimeZone('PST8PDT'); //Force to non-DST timezone. 'PST' isnt actually valid.
        //Debug::text('zzz2: ', date_default_timezone_get(), __FILE__, __LINE__, __METHOD__, 10);

        TTDate::setTimeFormat('g:i A');
        $this->assertEquals(TTDate::parseDateTime('02-25-2005'), 1109318400);
        $this->assertEquals(TTDate::parseDateTime('02-25-05'), 1109318400);

        $this->assertEquals(TTDate::parseDateTime('10-27-06'), 1161932400);

        $this->assertEquals(TTDate::parseDateTime('02-25-2005 8:09PM'), 1109390940);
        $this->assertEquals(TTDate::parseDateTime('02-25-2005 8:09 AM'), 1109347740);

        TTDate::setTimeFormat('g:i A T');
        $this->assertEquals(TTDate::parseDateTime('02-25-2005 8:09 AM EST'), 1109336940);

        TTDate::setTimeFormat('G:i');
        $this->assertEquals(TTDate::parseDateTime('02-25-2005 18:09'), 1109383740);
        $this->assertEquals(TTDate::parseDateTime('02-25-2005 18:09 EST'), 1109372940);

        //
        // Different separator
        //
        TTDate::setDateFormat('m/d/y');

        TTDate::setTimeFormat('g:i A');
        $this->assertEquals(TTDate::parseDateTime('02/25/2005'), 1109318400);
        $this->assertEquals(TTDate::parseDateTime('02/25/2005 8:09PM'), 1109390940);
        $this->assertEquals(TTDate::parseDateTime('02/25/2005 8:09 AM'), 1109347740);

        TTDate::setTimeFormat('g:i A T');
        $this->assertEquals(TTDate::parseDateTime('02/25/2005 8:09 AM EST'), 1109336940);

        TTDate::setTimeFormat('G:i');
        $this->assertEquals(TTDate::parseDateTime('02/25/2005 18:09'), 1109383740);
        $this->assertEquals(TTDate::parseDateTime('02/25/2005 18:09 EST'), 1109372940);
    }

    public function testDate_MDY_2()
    {
        Debug::text('Testing Date Format: M-d-y', __FILE__, __LINE__, __METHOD__, 10);

        TTDate::setDateFormat('M-d-y');

        TTDate::setTimeFormat('g:i A');
        $this->assertEquals(TTDate::parseDateTime('Feb-25-2005'), 1109318400);
        $this->assertEquals(TTDate::parseDateTime('Feb-25-05'), 1109318400);
        $this->assertEquals(TTDate::parseDateTime('Feb-25-2005 8:09PM'), 1109390940);
        $this->assertEquals(TTDate::parseDateTime('Feb-25-2005 8:09 AM'), 1109347740);

        TTDate::setTimeFormat('g:i A T');
        $this->assertEquals(TTDate::parseDateTime('Feb-25-2005 8:09 AM EST'), 1109336940);

        TTDate::setTimeFormat('G:i');
        $this->assertEquals(TTDate::parseDateTime('Feb-25-2005 18:09'), 1109383740);
        $this->assertEquals(TTDate::parseDateTime('Feb-25-2005 18:09 EST'), 1109372940);
    }

    public function testDate_MDY_3()
    {
        Debug::text('Testing Date Format: m-d-y (two digit year)', __FILE__, __LINE__, __METHOD__, 10);

        TTDate::setDateFormat('m-d-y');

        TTDate::setTimeFormat('g:i A');
        $this->assertEquals(TTDate::parseDateTime('02-25-05'), 1109318400);
        $this->assertEquals(TTDate::parseDateTime('02-25-05 8:09PM'), 1109390940);
        $this->assertEquals(TTDate::parseDateTime('02-25-05 8:09 AM'), 1109347740);

        TTDate::setTimeFormat('g:i A T');
        $this->assertEquals(TTDate::parseDateTime('02-25-05 8:09 AM EST'), 1109336940);

        TTDate::setTimeFormat('G:i');
        $this->assertEquals(TTDate::parseDateTime('02-25-05 18:09'), 1109383740);
        $this->assertEquals(TTDate::parseDateTime('02-25-05 18:09 EST'), 1109372940);

        //Try test before 1970, like 1920 - *1920 fails after 2010 has passed, try a different value.

        $this->assertEquals(TTDate::parseDateTime('02-25-55'), -468604800);
        $this->assertEquals(TTDate::parseDateTime('02-25-55 8:09PM'), -468532260);
        $this->assertEquals(TTDate::parseDateTime('02-25-55 8:09 AM'), -468575460);
    }


    public function testDate_YMD_1()
    {
        Debug::text('Testing Date Format: Y-m-d', __FILE__, __LINE__, __METHOD__, 10);

        TTDate::setDateFormat('Y-m-d');

        TTDate::setTimeFormat('g:i A');
        $this->assertEquals(TTDate::parseDateTime('2005-02-25'), 1109318400);
        $this->assertEquals(TTDate::parseDateTime('05-02-25'), 1109318400);
        $this->assertEquals(TTDate::parseDateTime('2005-02-25 8:09PM'), 1109390940);
        $this->assertEquals(TTDate::parseDateTime('2005-02-25 8:09 AM'), 1109347740);

        TTDate::setTimeFormat('g:i A T');
        $this->assertEquals(TTDate::parseDateTime('2005-02-25 8:09 AM EST'), 1109336940);

        TTDate::setTimeFormat('G:i');
        $this->assertEquals(TTDate::parseDateTime('2005-02-25 18:09'), 1109383740);
        $this->assertEquals(TTDate::parseDateTime('2005-02-25 18:09 EST'), 1109372940);
    }

    public function test_getDayOfNextWeek()
    {
        Debug::text('Testing Date Format: Y-m-d', __FILE__, __LINE__, __METHOD__, 10);

        TTDate::setDateFormat('Y-m-d');

        TTDate::setTimeFormat('g:i A');
        $this->assertEquals(TTDate::getDateOfNextDayOfWeek(strtotime('29-Dec-06'), strtotime('27-Dec-06')), strtotime('03-Jan-07'));
        $this->assertEquals(TTDate::getDateOfNextDayOfWeek(strtotime('25-Dec-06'), strtotime('28-Dec-06')), strtotime('28-Dec-06'));
        $this->assertEquals(TTDate::getDateOfNextDayOfWeek(strtotime('31-Dec-06'), strtotime('25-Dec-06')), strtotime('01-Jan-07'));
    }

    public function test_getDateOfNextDayOfMonth()
    {
        Debug::text('Testing Date Format: Y-m-d', __FILE__, __LINE__, __METHOD__, 10);

        TTDate::setDateFormat('Y-m-d');

        TTDate::setTimeFormat('g:i A');
        $this->assertEquals(TTDate::getDateOfNextDayOfMonth(strtotime('01-Dec-06'), strtotime('02-Dec-06')), strtotime('02-Dec-06'));
        $this->assertEquals(TTDate::getDateOfNextDayOfMonth(strtotime('14-Dec-06'), strtotime('23-Nov-06')), strtotime('23-Dec-06'));
        $this->assertEquals(TTDate::getDateOfNextDayOfMonth(strtotime('14-Dec-06'), strtotime('13-Dec-06')), strtotime('13-Jan-07'));
        $this->assertEquals(TTDate::getDateOfNextDayOfMonth(strtotime('14-Dec-06'), strtotime('14-Dec-06')), strtotime('14-Dec-06'));
        $this->assertEquals(TTDate::getDateOfNextDayOfMonth(strtotime('12-Dec-06'), strtotime('01-Dec-04')), strtotime('01-Jan-07'));

        $this->assertEquals(TTDate::getDateOfNextDayOfMonth(strtotime('12-Dec-06'), null, 1), strtotime('01-Jan-07'));
        $this->assertEquals(TTDate::getDateOfNextDayOfMonth(strtotime('12-Dec-06'), null, 12), strtotime('12-Dec-06'));
        $this->assertEquals(TTDate::getDateOfNextDayOfMonth(strtotime('12-Dec-06'), null, 31), strtotime('31-Dec-06'));

        $this->assertEquals(TTDate::getDateOfNextDayOfMonth(strtotime('01-Feb-07'), null, 31), strtotime('28-Feb-07'));
        $this->assertEquals(TTDate::getDateOfNextDayOfMonth(strtotime('01-Feb-08'), null, 29), strtotime('29-Feb-08'));
        $this->assertEquals(TTDate::getDateOfNextDayOfMonth(strtotime('01-Feb-08'), null, 31), strtotime('29-Feb-08'));

        //Anchor Epoch: 09-Apr-04 11:59 PM PDT Day Of Month Epoch:  Day Of Month: 24<br>
        $this->assertEquals(TTDate::getDateOfNextDayOfMonth(strtotime('09-Apr-04'), null, 24), strtotime('24-Apr-04'));
    }

    public function test_parseEpoch()
    {
        Debug::text('Testing Date Parsing of EPOCH!', __FILE__, __LINE__, __METHOD__, 10);

        TTDate::setDateFormat('m-d-y');

        TTDate::setTimeFormat('g:i A');
        $this->assertEquals(TTDate::parseDateTime(1162670400), (int)1162670400);


        TTDate::setDateFormat('Y-m-d');

        TTDate::setTimeFormat('g:i A');
        $this->assertEquals(TTDate::parseDateTime(1162670400), (int)1162670400);

        $this->assertEquals(TTDate::parseDateTime(600), 600); //Test small epochs that may conflict with 24hr time that just has the time and not a date.
        $this->assertEquals(TTDate::parseDateTime(1800), 1800);  //Test small epochs that may conflict with 24hr time that just has the time and not a date.

        $this->assertEquals(TTDate::parseDateTime(-600), -600); //Test small epochs that may conflict with 24hr time that just has the time and not a date.
        $this->assertEquals(TTDate::parseDateTime(-1800), -1800); //Test small epochs that may conflict with 24hr time that just has the time and not a date.
    }

    public function test_roundTime()
    {
        //10 = Down
        //20 = Average
        //30 = Up

        //Test rounding down by 15minutes
        $this->assertEquals((int)TTDate::roundTime(strtotime('15-Apr-07 8:06 AM'), (60 * 15), 10), strtotime('15-Apr-07 8:00 AM'));
        //Test rounding down by 5minutes
        $this->assertEquals((int)TTDate::roundTime(strtotime('15-Apr-07 8:06 AM'), (60 * 5), 10), strtotime('15-Apr-07 8:05 AM'));
        //Test rounding down by 5minutes when no rounding should occur.
        $this->assertEquals((int)TTDate::roundTime(strtotime('15-Apr-07 8:05 AM'), (60 * 5), 10), strtotime('15-Apr-07 8:05 AM'));

        //Test rounding down by 15minutes with 3minute grace.
        $this->assertEquals((int)TTDate::roundTime(strtotime('15-Apr-07 4:58 PM'), (60 * 15), 10, (60 * 3)), strtotime('15-Apr-07 5:00 PM'));
        $this->assertEquals((int)TTDate::roundTime(strtotime('15-Apr-07 4:56 PM'), (60 * 15), 10, (60 * 3)), strtotime('15-Apr-07 4:45 PM'));
        $this->assertEquals((int)TTDate::roundTime(strtotime('15-Apr-07 5:11 PM'), (60 * 15), 10, (60 * 3)), strtotime('15-Apr-07 5:00 PM'));
        //Test rounding down by 5minutes with 2minute grace
        $this->assertEquals((int)TTDate::roundTime(strtotime('15-Apr-07 5:11 PM'), (60 * 5), 10, (60 * 2)), strtotime('15-Apr-07 5:10 PM'));
        $this->assertEquals((int)TTDate::roundTime(strtotime('15-Apr-07 5:07 PM'), (60 * 5), 10, (60 * 2)), strtotime('15-Apr-07 5:05 PM'));


        //Test rounding avg by 15minutes
        $this->assertEquals((int)TTDate::roundTime(strtotime('15-Apr-07 8:06 AM'), (60 * 15), 20), strtotime('15-Apr-07 8:00 AM'));
        $this->assertEquals((int)TTDate::roundTime(strtotime('15-Apr-07 8:06:59 AM'), (60 * 15), 20), strtotime('15-Apr-07 8:00 AM'));
        $this->assertEquals((int)TTDate::roundTime(strtotime('15-Apr-07 8:07:29 AM'), (60 * 15), 20), strtotime('15-Apr-07 8:00 AM'));
        $this->assertEquals((int)TTDate::roundTime(strtotime('15-Apr-07 8:07:30 AM'), (60 * 15), 20), strtotime('15-Apr-07 8:15 AM'));
        $this->assertEquals((int)TTDate::roundTime(strtotime('15-Apr-07 8:07:59 AM'), (60 * 15), 20), strtotime('15-Apr-07 8:15 AM'));
        $this->assertEquals((int)TTDate::roundTime(strtotime('15-Apr-07 8:08 AM'), (60 * 15), 20), strtotime('15-Apr-07 8:15 AM'));
        $this->assertEquals((int)TTDate::roundTime(strtotime('15-Apr-07 8:08:01 AM'), (60 * 15), 20), strtotime('15-Apr-07 8:15 AM'));

        //Test rounding avg by 5minutes
        $this->assertEquals((int)TTDate::roundTime(strtotime('15-Apr-07 8:06 AM'), (60 * 5), 20), strtotime('15-Apr-07 8:05 AM'));
        $this->assertEquals((int)TTDate::roundTime(strtotime('15-Apr-07 8:07 AM'), (60 * 5), 20), strtotime('15-Apr-07 8:05 AM'));
        $this->assertEquals((int)TTDate::roundTime(strtotime('15-Apr-07 8:07:01 AM'), (60 * 5), 20), strtotime('15-Apr-07 8:05 AM'));
        $this->assertEquals((int)TTDate::roundTime(strtotime('15-Apr-07 8:07:29 AM'), (60 * 5), 20), strtotime('15-Apr-07 8:05 AM'));
        $this->assertEquals((int)TTDate::roundTime(strtotime('15-Apr-07 8:07:30 AM'), (60 * 5), 20), strtotime('15-Apr-07 8:10 AM'));
        $this->assertEquals((int)TTDate::roundTime(strtotime('15-Apr-07 8:07:31 AM'), (60 * 5), 20), strtotime('15-Apr-07 8:10 AM'));
        $this->assertEquals((int)TTDate::roundTime(strtotime('15-Apr-07 8:07:59 AM'), (60 * 5), 20), strtotime('15-Apr-07 8:10 AM'));
        $this->assertEquals((int)TTDate::roundTime(strtotime('15-Apr-07 8:08 AM'), (60 * 5), 20), strtotime('15-Apr-07 8:10 AM'));
        //Test rounding avg by 5minutes when no rounding should occur.
        $this->assertEquals((int)TTDate::roundTime(strtotime('15-Apr-07 8:05 AM'), (60 * 5), 20), strtotime('15-Apr-07 8:05 AM'));

        //Test rounding avg by 1minute -- This is another special case that we have to be exactly proper rounding for.
        $this->assertEquals((int)TTDate::roundTime(strtotime('15-Apr-07 8:00:00 AM'), (60 * 1), 20), strtotime('15-Apr-07 8:00 AM'));
        $this->assertEquals((int)TTDate::roundTime(strtotime('15-Apr-07 8:00:01 AM'), (60 * 1), 20), strtotime('15-Apr-07 8:00 AM'));
        $this->assertEquals((int)TTDate::roundTime(strtotime('15-Apr-07 8:00:29 AM'), (60 * 1), 20), strtotime('15-Apr-07 8:00 AM'));
        $this->assertEquals((int)TTDate::roundTime(strtotime('15-Apr-07 8:00:30 AM'), (60 * 1), 20), strtotime('15-Apr-07 8:01 AM'));
        $this->assertEquals((int)TTDate::roundTime(strtotime('15-Apr-07 8:00:31 AM'), (60 * 1), 20), strtotime('15-Apr-07 8:01 AM'));
        $this->assertEquals((int)TTDate::roundTime(strtotime('15-Apr-07 8:00:59 AM'), (60 * 1), 20), strtotime('15-Apr-07 8:01 AM'));
        $this->assertEquals((int)TTDate::roundTime(strtotime('15-Apr-07 8:01:00 AM'), (60 * 1), 20), strtotime('15-Apr-07 8:01 AM'));


        //When doing a 15min average rounding, US law states 7mins and 59 seconds can be rounded down in favor of the employer, and 8mins and 0 seconds must be rounded up. See TTDate::roundTime() for more details.
        $this->assertEquals((int)TTDate::roundTime(strtotime('15-Apr-07 8:06 AM'), (60 * 15), 27), strtotime('15-Apr-07 8:00 AM'));
        $this->assertEquals((int)TTDate::roundTime(strtotime('15-Apr-07 8:06:59 AM'), (60 * 15), 27), strtotime('15-Apr-07 8:00 AM'));
        $this->assertEquals((int)TTDate::roundTime(strtotime('15-Apr-07 8:07:00 AM'), (60 * 15), 27), strtotime('15-Apr-07 8:15 AM'));
        $this->assertEquals((int)TTDate::roundTime(strtotime('15-Apr-07 8:07:29 AM'), (60 * 15), 27), strtotime('15-Apr-07 8:15 AM'));
        $this->assertEquals((int)TTDate::roundTime(strtotime('15-Apr-07 8:07:30 AM'), (60 * 15), 27), strtotime('15-Apr-07 8:15 AM'));
        $this->assertEquals((int)TTDate::roundTime(strtotime('15-Apr-07 8:07:59 AM'), (60 * 15), 27), strtotime('15-Apr-07 8:15 AM'));
        $this->assertEquals((int)TTDate::roundTime(strtotime('15-Apr-07 8:08 AM'), (60 * 15), 27), strtotime('15-Apr-07 8:15 AM'));
        $this->assertEquals((int)TTDate::roundTime(strtotime('15-Apr-07 8:08:01 AM'), (60 * 15), 27), strtotime('15-Apr-07 8:15 AM'));

        //When doing a 15min average rounding, US law states 7mins and 59 seconds can be rounded down in favor of the employer, and 8mins and 0 seconds must be rounded up. See TTDate::roundTime() for more details.
        $this->assertEquals((int)TTDate::roundTime(strtotime('15-Apr-07 5:05:01 PM'), (60 * 15), 25), strtotime('15-Apr-07 5:00 PM'));
        $this->assertEquals((int)TTDate::roundTime(strtotime('15-Apr-07 5:06 PM'), (60 * 15), 25), strtotime('15-Apr-07 5:00 PM'));
        $this->assertEquals((int)TTDate::roundTime(strtotime('15-Apr-07 5:06:59 PM'), (60 * 15), 25), strtotime('15-Apr-07 5:00 PM'));
        $this->assertEquals((int)TTDate::roundTime(strtotime('15-Apr-07 5:07:29 PM'), (60 * 15), 25), strtotime('15-Apr-07 5:00 PM'));
        $this->assertEquals((int)TTDate::roundTime(strtotime('15-Apr-07 5:07:30 PM'), (60 * 15), 25), strtotime('15-Apr-07 5:00 PM'));
        $this->assertEquals((int)TTDate::roundTime(strtotime('15-Apr-07 5:07:59 PM'), (60 * 15), 25), strtotime('15-Apr-07 5:00 PM'));
        $this->assertEquals((int)TTDate::roundTime(strtotime('15-Apr-07 5:08 PM'), (60 * 15), 25), strtotime('15-Apr-07 5:15 PM'));

        //When doing a 15min average rounding, US law states 7mins and 59 seconds can be rounded down in favor of the employer, and 8mins and 0 seconds must be rounded up. See TTDate::roundTime() for more details.
        $this->assertEquals((int)TTDate::roundTime(strtotime('15-Apr-07 5:06 PM'), (60 * 5), 25), strtotime('15-Apr-07 5:05 PM'));
        $this->assertEquals((int)TTDate::roundTime(strtotime('15-Apr-07 5:07 PM'), (60 * 5), 25), strtotime('15-Apr-07 5:05 PM'));
        $this->assertEquals((int)TTDate::roundTime(strtotime('15-Apr-07 5:07:01 PM'), (60 * 5), 25), strtotime('15-Apr-07 5:05 PM'));
        $this->assertEquals((int)TTDate::roundTime(strtotime('15-Apr-07 5:07:29 PM'), (60 * 5), 25), strtotime('15-Apr-07 5:05 PM'));
        $this->assertEquals((int)TTDate::roundTime(strtotime('15-Apr-07 5:07:30 PM'), (60 * 5), 25), strtotime('15-Apr-07 5:05 PM'));
        $this->assertEquals((int)TTDate::roundTime(strtotime('15-Apr-07 5:07:31 PM'), (60 * 5), 25), strtotime('15-Apr-07 5:05 PM'));
        $this->assertEquals((int)TTDate::roundTime(strtotime('15-Apr-07 5:07:59 PM'), (60 * 5), 25), strtotime('15-Apr-07 5:05 PM'));


        //Test rounding avg by 1minute -- This is another special case that we have to be exactly proper rounding for.
        $this->assertEquals((int)TTDate::roundTime(strtotime('15-Apr-07 5:00:00 PM'), (60 * 1), 27), strtotime('15-Apr-07 5:00 PM'));
        $this->assertEquals((int)TTDate::roundTime(strtotime('15-Apr-07 5:00:01 PM'), (60 * 1), 27), strtotime('15-Apr-07 5:00 PM'));
        $this->assertEquals((int)TTDate::roundTime(strtotime('15-Apr-07 5:00:29 PM'), (60 * 1), 27), strtotime('15-Apr-07 5:00 PM'));
        $this->assertEquals((int)TTDate::roundTime(strtotime('15-Apr-07 5:00:30 PM'), (60 * 1), 27), strtotime('15-Apr-07 5:01 PM'));
        $this->assertEquals((int)TTDate::roundTime(strtotime('15-Apr-07 5:00:31 PM'), (60 * 1), 27), strtotime('15-Apr-07 5:01 PM'));
        $this->assertEquals((int)TTDate::roundTime(strtotime('15-Apr-07 5:00:59 PM'), (60 * 1), 27), strtotime('15-Apr-07 5:01 PM'));
        $this->assertEquals((int)TTDate::roundTime(strtotime('15-Apr-07 5:01:00 PM'), (60 * 1), 27), strtotime('15-Apr-07 5:01 PM'));
        $this->assertEquals((int)TTDate::roundTime(strtotime('15-Apr-07 5:00:00 PM'), (60 * 1), 25), strtotime('15-Apr-07 5:00 PM'));
        $this->assertEquals((int)TTDate::roundTime(strtotime('15-Apr-07 5:00:01 PM'), (60 * 1), 25), strtotime('15-Apr-07 5:00 PM'));
        $this->assertEquals((int)TTDate::roundTime(strtotime('15-Apr-07 5:00:29 PM'), (60 * 1), 25), strtotime('15-Apr-07 5:00 PM'));
        $this->assertEquals((int)TTDate::roundTime(strtotime('15-Apr-07 5:00:30 PM'), (60 * 1), 25), strtotime('15-Apr-07 5:01 PM'));
        $this->assertEquals((int)TTDate::roundTime(strtotime('15-Apr-07 5:00:31 PM'), (60 * 1), 25), strtotime('15-Apr-07 5:01 PM'));
        $this->assertEquals((int)TTDate::roundTime(strtotime('15-Apr-07 5:00:59 PM'), (60 * 1), 25), strtotime('15-Apr-07 5:01 PM'));
        $this->assertEquals((int)TTDate::roundTime(strtotime('15-Apr-07 5:01:00 PM'), (60 * 1), 25), strtotime('15-Apr-07 5:01 PM'));


        //Test rounding up by 15minutes
        $this->assertEquals((int)TTDate::roundTime(strtotime('15-Apr-07 8:06 AM'), (60 * 15), 30), strtotime('15-Apr-07 8:15 AM'));
        //Test rounding up by 5minutes
        $this->assertEquals((int)TTDate::roundTime(strtotime('15-Apr-07 8:06 AM'), (60 * 5), 30), strtotime('15-Apr-07 8:10 AM'));
        //Test rounding up by 5minutes when no rounding should occur.
        $this->assertEquals((int)TTDate::roundTime(strtotime('15-Apr-07 8:05 AM'), (60 * 5), 30), strtotime('15-Apr-07 8:05 AM'));

        //Test rounding up by 15minutes with 3minute grace.
        $this->assertEquals((int)TTDate::roundTime(strtotime('15-Apr-07 8:01 AM'), (60 * 15), 30, (60 * 3)), strtotime('15-Apr-07 8:00 AM'));
        $this->assertEquals((int)TTDate::roundTime(strtotime('15-Apr-07 8:04 AM'), (60 * 15), 30, (60 * 3)), strtotime('15-Apr-07 8:15 AM'));
        $this->assertEquals((int)TTDate::roundTime(strtotime('15-Apr-07 8:03 AM'), (60 * 15), 30, (60 * 3)), strtotime('15-Apr-07 8:00 AM'));
        //Test rounding up by 5minutes with 2minute grace
        $this->assertEquals((int)TTDate::roundTime(strtotime('15-Apr-07 8:03 AM'), (60 * 5), 30, (60 * 2)), strtotime('15-Apr-07 8:05 AM'));
        $this->assertEquals((int)TTDate::roundTime(strtotime('15-Apr-07 8:01 AM'), (60 * 5), 30, (60 * 2)), strtotime('15-Apr-07 8:00 AM'));


        //Test time units
        $this->assertEquals((int)TTDate::roundTime(TTDate::parseTimeUnit('1:05:00'), (60 * 15), 20), TTDate::parseTimeUnit('1:00'));
        $this->assertEquals((int)TTDate::roundTime(TTDate::parseTimeUnit('1:07:29'), (60 * 15), 20), TTDate::parseTimeUnit('1:00'));
        $this->assertEquals((int)TTDate::roundTime(TTDate::parseTimeUnit('1:07:30'), (60 * 15), 20), TTDate::parseTimeUnit('1:15'));
        $this->assertEquals((int)TTDate::roundTime(TTDate::parseTimeUnit('1:07:31'), (60 * 15), 20), TTDate::parseTimeUnit('1:15'));
        $this->assertEquals((int)TTDate::roundTime(TTDate::parseTimeUnit('1:07:59'), (60 * 15), 20), TTDate::parseTimeUnit('1:15'));

        //Test time units with negative values.
        $this->assertEquals((int)TTDate::roundTime(TTDate::parseTimeUnit('-1:05:00'), (60 * 15), 20), TTDate::parseTimeUnit('-1:00'));
        $this->assertEquals((int)TTDate::roundTime(TTDate::parseTimeUnit('-1:07:29'), (60 * 15), 20), TTDate::parseTimeUnit('-1:00'));
        $this->assertEquals((int)TTDate::roundTime(TTDate::parseTimeUnit('-1:07:30'), (60 * 15), 20), TTDate::parseTimeUnit('-1:15'));
        $this->assertEquals((int)TTDate::roundTime(TTDate::parseTimeUnit('-1:07:31'), (60 * 15), 20), TTDate::parseTimeUnit('-1:15'));
        $this->assertEquals((int)TTDate::roundTime(TTDate::parseTimeUnit('-1:07:59'), (60 * 15), 20), TTDate::parseTimeUnit('-1:15'));

        $this->assertEquals((int)TTDate::roundTime((TTDate::parseTimeUnit('1:05:00') * -1), (60 * 15), 20), (TTDate::parseTimeUnit('1:00') * -1));
        $this->assertEquals((int)TTDate::roundTime((TTDate::parseTimeUnit('1:07:29') * -1), (60 * 15), 20), (TTDate::parseTimeUnit('1:00') * -1));
        $this->assertEquals((int)TTDate::roundTime((TTDate::parseTimeUnit('1:07:30') * -1), (60 * 15), 20), (TTDate::parseTimeUnit('1:15') * -1));
        $this->assertEquals((int)TTDate::roundTime((TTDate::parseTimeUnit('1:07:31') * -1), (60 * 15), 20), (TTDate::parseTimeUnit('1:15') * -1));
        $this->assertEquals((int)TTDate::roundTime((TTDate::parseTimeUnit('1:07:59') * -1), (60 * 15), 20), (TTDate::parseTimeUnit('1:15') * -1));
    }

    public function test_graceTime()
    {
        $this->assertEquals((int)TTDate::graceTime(strtotime('15-Apr-07 7:58 AM'), (60 * 5), strtotime('15-Apr-07 8:00 AM')), strtotime('15-Apr-07 8:00 AM'));
        $this->assertEquals((int)TTDate::graceTime(strtotime('15-Apr-07 7:58:23 AM'), (60 * 5), strtotime('15-Apr-07 8:00 AM')), strtotime('15-Apr-07 8:00 AM'));
    }

    public function test_calculateTimeOnEachDayBetweenRange()
    {
        $test1_result = TTDate::calculateTimeOnEachDayBetweenRange(strtotime('01-Jan-09 8:00AM'), strtotime('01-Jan-09 11:30PM'));
        $this->assertEquals(count($test1_result), 1);
        $this->assertEquals($test1_result[1230796800], 55800);

        $test2_result = TTDate::calculateTimeOnEachDayBetweenRange(strtotime('01-Jan-09 4:00PM'), strtotime('02-Jan-09 8:00AM'));
        $this->assertEquals(count($test2_result), 2);
        $this->assertEquals($test2_result[1230796800], 28800);
        $this->assertEquals($test2_result[1230883200], 28800);

        $test3_result = TTDate::calculateTimeOnEachDayBetweenRange(strtotime('01-Jan-09 4:00PM'), strtotime('03-Jan-09 8:00AM'));
        $this->assertEquals(count($test3_result), 3);
        $this->assertEquals($test3_result[1230796800], 28800);
        $this->assertEquals($test3_result[1230883200], 86400);
        $this->assertEquals($test3_result[1230969600], 28800);

        $test4_result = TTDate::calculateTimeOnEachDayBetweenRange(strtotime('01-Jan-09 4:00PM'), strtotime('9-Jan-09 8:00AM'));
        $this->assertEquals(count($test4_result), 9);
        $this->assertEquals($test4_result[1230796800], 28800);
        $this->assertEquals($test4_result[1230883200], 86400);
        $this->assertEquals($test4_result[1230969600], 86400);
        $this->assertEquals($test4_result[1231056000], 86400);
        $this->assertEquals($test4_result[1231142400], 86400);
        $this->assertEquals($test4_result[1231228800], 86400);
        $this->assertEquals($test4_result[1231315200], 86400);
        $this->assertEquals($test4_result[1231401600], 86400);
        $this->assertEquals($test4_result[1231488000], 28800);

        $test5_result = TTDate::calculateTimeOnEachDayBetweenRange(strtotime('01-Jan-09 12:00AM'), strtotime('01-Jan-09 12:59:59PM'));
        $this->assertEquals(count($test5_result), 1);
        $this->assertEquals($test5_result[1230796800], 46799);

        $test5_result = TTDate::calculateTimeOnEachDayBetweenRange(strtotime('01-Jan-09 12:00AM'), strtotime('02-Jan-09 12:00AM'));
        $this->assertEquals(count($test5_result), 1);
        $this->assertEquals($test5_result[1230796800], 86400);

        $test5_result = TTDate::calculateTimeOnEachDayBetweenRange(strtotime('01-Jan-09 12:01AM'), strtotime('02-Jan-09 12:01AM'));
        $this->assertEquals(count($test5_result), 2);
        $this->assertEquals($test5_result[1230796800], 86340);
        $this->assertEquals($test5_result[1230883200], 60);

        $test5_result = TTDate::calculateTimeOnEachDayBetweenRange(strtotime('01-Jan-09 1:53PM'), strtotime('03-Jan-09 6:12AM'));
        $this->assertEquals(count($test5_result), 3);
        $this->assertEquals($test5_result[1230796800], 36420);
        $this->assertEquals($test5_result[1230883200], 86400);
        $this->assertEquals($test5_result[1230969600], 22320);
    }

    public function test_calculateFiscalYearFromEpoch()
    {
        /*
        For example, the United States government fiscal year for 2016 is:

        1st quarter: 1 October 2015 – 31 December 2015
        2nd quarter: 1 January 2016 – 31 March 2016
        3rd quarter: 1 April 2016 – 30 June 2016
        4th quarter: 1 July 2016 – 30 September 2016
        */

        $this->assertEquals(TTDate::getFiscalYearFromEpoch(strtotime('30-Sep-2015 12:00AM'), 'US'), 2015);
        $this->assertEquals(TTDate::getFiscalYearFromEpoch(strtotime('01-Oct-2015 12:00AM'), 'US'), 2016);
        $this->assertEquals(TTDate::getFiscalYearFromEpoch(strtotime('01-Jan-2016 8:00AM'), 'US'), 2016);
        $this->assertEquals(TTDate::getFiscalYearFromEpoch(strtotime('29-Sep-2016 12:00AM'), 'US'), 2016);
        $this->assertEquals(TTDate::getFiscalYearFromEpoch(strtotime('30-Sep-2016 12:00AM'), 'US'), 2016);
        $this->assertEquals(TTDate::getFiscalYearFromEpoch(strtotime('30-Sep-2016 11:59:59PM'), 'US'), 2016);
        $this->assertEquals(TTDate::getFiscalYearFromEpoch(strtotime('01-Oct-2016 12:00AM'), 'US'), 2017);

        $this->assertEquals(TTDate::getFiscalYearFromEpoch(strtotime('30-Sep-2016 12:00AM'), 'US'), 2016);
        $this->assertEquals(TTDate::getFiscalYearFromEpoch(strtotime('01-Oct-2016 12:00AM'), 'US'), 2017);
        $this->assertEquals(TTDate::getFiscalYearFromEpoch(strtotime('01-Jan-2017 8:00AM'), 'US'), 2017);
        $this->assertEquals(TTDate::getFiscalYearFromEpoch(strtotime('29-Sep-2017 12:00AM'), 'US'), 2017);
        $this->assertEquals(TTDate::getFiscalYearFromEpoch(strtotime('30-Sep-2017 12:00AM'), 'US'), 2017);
        $this->assertEquals(TTDate::getFiscalYearFromEpoch(strtotime('30-Sep-2017 11:59:59PM'), 'US'), 2017);
        $this->assertEquals(TTDate::getFiscalYearFromEpoch(strtotime('01-Oct-2017 12:00AM'), 'US'), 2018);

        /*
        In Canada,[9] the government's financial year runs from 1 April to 31 March (Example 1 April 2015 to 31 March 2016 for the current financial year).
         */
        $this->assertEquals(TTDate::getFiscalYearFromEpoch(strtotime('31-Mar-2015 12:00AM'), 'CA'), 2014);
        $this->assertEquals(TTDate::getFiscalYearFromEpoch(strtotime('01-Apr-2015 12:00AM'), 'CA'), 2015);
        $this->assertEquals(TTDate::getFiscalYearFromEpoch(strtotime('31-Dec-2015 8:00AM'), 'CA'), 2015);
        $this->assertEquals(TTDate::getFiscalYearFromEpoch(strtotime('31-Mar-2016 11:59AM'), 'CA'), 2015);
        $this->assertEquals(TTDate::getFiscalYearFromEpoch(strtotime('01-Apr-2016 12:00AM'), 'CA'), 2016);
    }

    public function test_getWeek()
    {
        //Match up with PHP's function
        $date1 = strtotime('01-Nov-09 12:00PM');
        $this->assertEquals(date('W', $date1), 44);
        $this->assertEquals(TTDate::getWeek($date1, 1), 44);

        $date1 = strtotime('02-Nov-09 12:00PM');
        $this->assertEquals(date('W', $date1), 45);
        $this->assertEquals(TTDate::getWeek($date1, 1), 45);

        $date1 = strtotime('03-Nov-09 12:00PM');
        $this->assertEquals(date('W', $date1), 45);
        $this->assertEquals(TTDate::getWeek($date1, 1), 45);

        $date1 = strtotime('04-Nov-09 12:00PM');
        $this->assertEquals(date('W', $date1), 45);
        $this->assertEquals(TTDate::getWeek($date1, 1), 45);

        $date1 = strtotime('05-Nov-09 12:00PM');
        $this->assertEquals(date('W', $date1), 45);
        $this->assertEquals(TTDate::getWeek($date1, 1), 45);

        $date1 = strtotime('06-Nov-09 12:00PM');
        $this->assertEquals(date('W', $date1), 45);
        $this->assertEquals(TTDate::getWeek($date1, 1), 45);

        $date1 = strtotime('07-Nov-09 12:00PM');
        $this->assertEquals(date('W', $date1), 45);
        $this->assertEquals(TTDate::getWeek($date1, 1), 45);

        $date1 = strtotime('08-Nov-09 12:00PM');
        $this->assertEquals(date('W', $date1), 45);
        $this->assertEquals(TTDate::getWeek($date1, 1), 45);

        $date1 = strtotime('09-Nov-09 12:00PM');
        $this->assertEquals(date('W', $date1), 46);
        $this->assertEquals(TTDate::getWeek($date1, 1), 46);

        //Test with Sunday as start day of week.
        $date1 = strtotime('01-Nov-09 12:00PM');
        $this->assertEquals(TTDate::getWeek($date1, 0), 45);

        $date1 = strtotime('02-Nov-09 12:00PM');
        $this->assertEquals(TTDate::getWeek($date1, 0), 45);

        $date1 = strtotime('03-Nov-09 12:00PM');
        $this->assertEquals(TTDate::getWeek($date1, 0), 45);

        $date1 = strtotime('04-Nov-09 12:00PM');
        $this->assertEquals(TTDate::getWeek($date1, 0), 45);

        $date1 = strtotime('05-Nov-09 12:00PM');
        $this->assertEquals(TTDate::getWeek($date1, 0), 45);

        $date1 = strtotime('06-Nov-09 12:00PM');
        $this->assertEquals(TTDate::getWeek($date1, 0), 45);

        $date1 = strtotime('07-Nov-09 12:00PM');
        $this->assertEquals(TTDate::getWeek($date1, 0), 45);

        $date1 = strtotime('08-Nov-09 12:00PM');
        $this->assertEquals(TTDate::getWeek($date1, 0), 46);

        $date1 = strtotime('09-Nov-09 12:00PM');
        $this->assertEquals(TTDate::getWeek($date1, 0), 46);


        //Test with Tuesday as start day of week.
        $date1 = strtotime('01-Nov-09 12:00PM');
        $this->assertEquals(TTDate::getWeek($date1, 2), 44);

        $date1 = strtotime('02-Nov-09 12:00PM');
        $this->assertEquals(TTDate::getWeek($date1, 2), 44);

        $date1 = strtotime('03-Nov-09 12:00PM');
        $this->assertEquals(TTDate::getWeek($date1, 2), 45);

        $date1 = strtotime('04-Nov-09 12:00PM');
        $this->assertEquals(TTDate::getWeek($date1, 2), 45);

        $date1 = strtotime('05-Nov-09 12:00PM');
        $this->assertEquals(TTDate::getWeek($date1, 2), 45);

        $date1 = strtotime('06-Nov-09 12:00PM');
        $this->assertEquals(TTDate::getWeek($date1, 2), 45);

        $date1 = strtotime('07-Nov-09 12:00PM');
        $this->assertEquals(TTDate::getWeek($date1, 2), 45);

        $date1 = strtotime('08-Nov-09 12:00PM');
        $this->assertEquals(TTDate::getWeek($date1, 2), 45);

        $date1 = strtotime('09-Nov-09 12:00PM');
        $this->assertEquals(TTDate::getWeek($date1, 2), 45);

        $date1 = strtotime('10-Nov-09 12:00PM');
        $this->assertEquals(TTDate::getWeek($date1, 2), 46);

        $date1 = strtotime('11-Nov-09 12:00PM');
        $this->assertEquals(TTDate::getWeek($date1, 2), 46);


        //Test with Wed as start day of week.
        $date1 = strtotime('03-Nov-09 12:00PM');
        $this->assertEquals(TTDate::getWeek($date1, 3), 44);

        $date1 = strtotime('04-Nov-09 12:00PM');
        $this->assertEquals(TTDate::getWeek($date1, 3), 45);

        $date1 = strtotime('05-Nov-09 12:00PM');
        $this->assertEquals(TTDate::getWeek($date1, 3), 45);

        //Test with Thu as start day of week.
        $date1 = strtotime('04-Nov-09 12:00PM');
        $this->assertEquals(TTDate::getWeek($date1, 4), 44);

        $date1 = strtotime('05-Nov-09 12:00PM');
        $this->assertEquals(TTDate::getWeek($date1, 4), 45);

        $date1 = strtotime('06-Nov-09 12:00PM');
        $this->assertEquals(TTDate::getWeek($date1, 4), 45);

        //Test with Fri as start day of week.
        $date1 = strtotime('05-Nov-09 12:00PM');
        $this->assertEquals(TTDate::getWeek($date1, 5), 44);

        $date1 = strtotime('06-Nov-09 12:00PM');
        $this->assertEquals(TTDate::getWeek($date1, 5), 45);

        $date1 = strtotime('07-Nov-09 12:00PM');
        $this->assertEquals(TTDate::getWeek($date1, 5), 45);

        //Test with Sat as start day of week.
        $date1 = strtotime('06-Nov-09 12:00PM');
        $this->assertEquals(TTDate::getWeek($date1, 6), 44);

        $date1 = strtotime('07-Nov-09 12:00PM');
        $this->assertEquals(TTDate::getWeek($date1, 6), 45);

        $date1 = strtotime('08-Nov-09 12:00PM');
        $this->assertEquals(TTDate::getWeek($date1, 6), 45);

        //Test with different years
        $date1 = strtotime('31-Dec-09 12:00PM');
        $this->assertEquals(TTDate::getWeek($date1, 1), 53);

        $date1 = strtotime('01-Jan-10 12:00PM');
        $this->assertEquals(TTDate::getWeek($date1, 1), 53);

        $date1 = strtotime('04-Jan-10 12:00PM');
        $this->assertEquals(TTDate::getWeek($date1, 1), 1);

        $date1 = strtotime('03-Jan-10 12:00PM');
        $this->assertEquals(TTDate::getWeek($date1, 0), 1);

        $date1 = strtotime('09-Jan-10 12:00PM');
        $this->assertEquals(TTDate::getWeek($date1, 6), 1);


        //Start on Monday as thats what PHP uses.
        for ($i = strtotime('07-Jan-13'); $i < strtotime('06-Jan-13'); $i += (86400 * 7)) {
            $this->assertEquals(TTDate::getWeek($i, 1), date('W', $i));
        }

        //Start on Sunday.
        $this->assertEquals(TTDate::getWeek(strtotime('29-Dec-12'), 0), 52);
        $this->assertEquals(TTDate::getWeek(strtotime('30-Dec-12'), 0), 1);
        $this->assertEquals(TTDate::getWeek(strtotime('31-Dec-12'), 0), 1);
        $this->assertEquals(TTDate::getWeek(strtotime('01-Jan-12'), 0), 1);
        $this->assertEquals(TTDate::getWeek(strtotime('02-Jan-12'), 0), 1);
        $this->assertEquals(TTDate::getWeek(strtotime('03-Jan-12'), 0), 1);
        $this->assertEquals(TTDate::getWeek(strtotime('04-Jan-12'), 0), 1);
        $this->assertEquals(TTDate::getWeek(strtotime('05-Jan-12'), 0), 1);
        $this->assertEquals(TTDate::getWeek(strtotime('06-Jan-13'), 0), 2);

        $this->assertEquals(TTDate::getWeek(strtotime('09-Apr-13'), 0), 15);
        $this->assertEquals(TTDate::getWeek(strtotime('28-Jun-13'), 0), 26);

        //Start on every other day of the week
        $this->assertEquals(TTDate::getWeek(strtotime('28-Jun-13'), 6), 25);
        $this->assertEquals(TTDate::getWeek(strtotime('27-Jun-13'), 5), 25);
        $this->assertEquals(TTDate::getWeek(strtotime('26-Jun-13'), 4), 25);
        $this->assertEquals(TTDate::getWeek(strtotime('25-Jun-13'), 3), 25);
        $this->assertEquals(TTDate::getWeek(strtotime('24-Jun-13'), 2), 25);
        $this->assertEquals(TTDate::getWeek(strtotime('23-Jun-13'), 1), 25);
        $this->assertEquals(TTDate::getWeek(strtotime('22-Jun-13'), 0), 25);
    }

    public function test_getNearestWeekDay()
    {
        //case 0: //No adjustment
        //	break 2;
        //case 1: //Previous day
        //	$epoch -= 86400;
        //	break;
        //case 2: //Next day
        //	$epoch += 86400;
        //	break;
        //case 3: //Closest day


        $date1 = strtotime('16-Jan-2010 12:00PM');
        $this->assertEquals(TTDate::getNearestWeekDay($date1, 0), strtotime('16-Jan-2010 12:00PM'));
        $this->assertEquals(TTDate::getNearestWeekDay($date1, 1), strtotime('15-Jan-2010 12:00PM'));
        $this->assertEquals(TTDate::getNearestWeekDay($date1, 2), strtotime('18-Jan-2010 12:00PM'));
        $this->assertEquals(TTDate::getNearestWeekDay($date1, 3), strtotime('15-Jan-2010 12:00PM'));

        $date2 = strtotime('17-Jan-2010 12:00PM');
        $this->assertEquals(TTDate::getNearestWeekDay($date2, 3), strtotime('18-Jan-2010 12:00PM'));

        $holidays = array(
            TTDate::getBeginDayEpoch(strtotime('15-Jan-2010'))
        );
        $date1 = strtotime('16-Jan-2010 12:00PM');
        $this->assertEquals(TTDate::getNearestWeekDay($date1, 3, $holidays), strtotime('14-Jan-2010 12:00PM'));

        $holidays = array(
            TTDate::getBeginDayEpoch(strtotime('15-Jan-2010')),
            TTDate::getBeginDayEpoch(strtotime('14-Jan-2010'))
        );
        $date1 = strtotime('16-Jan-2010 12:00PM');
        $this->assertEquals(TTDate::getNearestWeekDay($date1, 1, $holidays), strtotime('13-Jan-2010 12:00PM'));
        $this->assertEquals(TTDate::getNearestWeekDay($date1, 3, $holidays), strtotime('18-Jan-2010 12:00PM'));

        $holidays = array(
            TTDate::getBeginDayEpoch(strtotime('15-Jan-2010')),
            TTDate::getBeginDayEpoch(strtotime('14-Jan-2010')),
            TTDate::getBeginDayEpoch(strtotime('18-Jan-2010'))
        );
        $date1 = strtotime('16-Jan-2010 12:00PM');
        $this->assertEquals(TTDate::getNearestWeekDay($date1, 1, $holidays), strtotime('13-Jan-2010 12:00PM'));
        $this->assertEquals(TTDate::getNearestWeekDay($date1, 3, $holidays), strtotime('13-Jan-2010 12:00PM'));

        $holidays = array(
            TTDate::getBeginDayEpoch(strtotime('15-Jan-2010')),
            TTDate::getBeginDayEpoch(strtotime('14-Jan-2010')),
            TTDate::getBeginDayEpoch(strtotime('13-Jan-2010')),
            TTDate::getBeginDayEpoch(strtotime('18-Jan-2010'))
        );
        $date1 = strtotime('16-Jan-2010 12:00PM');
        $this->assertEquals(TTDate::getNearestWeekDay($date1, 1, $holidays), strtotime('12-Jan-2010 12:00PM'));
        $this->assertEquals(TTDate::getNearestWeekDay($date1, 3, $holidays), strtotime('19-Jan-2010 12:00PM'));
    }

    public function test_timePeriodDates()
    {
        Debug::text('Testing Time Period Dates!', __FILE__, __LINE__, __METHOD__, 10);
        TTDate::setTimeZone('PST8PDT');

        $dates = TTDate::getTimePeriodDates('custom_date', strtotime('15-Jul-10 12:00 PM'), null, array('start_date' => strtotime('10-Jul-10 12:43 PM'), 'end_date' => strtotime('12-Jul-10 12:43 PM')));
        $this->assertEquals($dates['start_date'], (int)1278745200);
        $this->assertEquals($dates['end_date'], (int)1279004399);

        $dates = TTDate::getTimePeriodDates('custom_time', strtotime('15-Jul-10 12:00 PM'), null, array('start_date' => strtotime('10-Jul-10 12:43 PM'), 'end_date' => strtotime('12-Jul-10 12:53 PM')));
        $this->assertEquals($dates['start_date'], (int)1278790980);
        $this->assertEquals($dates['end_date'], (int)1278964380);

        $dates = TTDate::getTimePeriodDates('today', strtotime('15-Jul-10 12:00 PM'));
        $this->assertEquals($dates['start_date'], (int)1279177200);
        $this->assertEquals($dates['end_date'], (int)1279263599);

        $dates = TTDate::getTimePeriodDates('yesterday', strtotime('15-Jul-10 12:00 PM'));
        $this->assertEquals($dates['start_date'], (int)1279090800);
        $this->assertEquals($dates['end_date'], (int)1279177199);

        $dates = TTDate::getTimePeriodDates('last_24_hours', strtotime('15-Jul-10 12:43 PM'));
        $this->assertEquals($dates['start_date'], (int)1279136580);
        $this->assertEquals($dates['end_date'], (int)1279222980);

        $dates = TTDate::getTimePeriodDates('this_week', strtotime('15-Jul-10 12:00 PM'));
        $this->assertEquals($dates['start_date'], (int)1278831600);
        $this->assertEquals($dates['end_date'], (int)1279436399);

        $dates = TTDate::getTimePeriodDates('last_week', strtotime('15-Jul-10 12:00 PM'));
        $this->assertEquals($dates['start_date'], (int)1278226800);
        $this->assertEquals($dates['end_date'], (int)1278831599);

        $dates = TTDate::getTimePeriodDates('last_7_days', strtotime('15-Jul-10 12:43 PM'));
        $this->assertEquals($dates['start_date'], (int)1278572400);
        $this->assertEquals($dates['end_date'], (int)1279177199);

        $dates = TTDate::getTimePeriodDates('this_month', strtotime('15-Jul-10 12:00 PM'));
        $this->assertEquals($dates['start_date'], (int)1277967600);
        $this->assertEquals($dates['end_date'], (int)1280645999);

        $dates = TTDate::getTimePeriodDates('last_month', strtotime('15-Jul-10 12:00 PM'));
        $this->assertEquals($dates['start_date'], (int)1275375600);
        $this->assertEquals($dates['end_date'], (int)1277967599);

        $dates = TTDate::getTimePeriodDates('last_month', strtotime('15-Mar-10 12:00 PM'));
        $this->assertEquals($dates['start_date'], (int)1265011200);
        $this->assertEquals($dates['end_date'], (int)1267430399);

        $dates = TTDate::getTimePeriodDates('last_30_days', strtotime('15-Jul-10 12:00 PM'));
        $this->assertEquals($dates['start_date'], (int)1276585200);
        $this->assertEquals($dates['end_date'], (int)1279177199);

        $dates = TTDate::getTimePeriodDates('this_quarter', strtotime('15-Jul-10 12:00 PM'));
        $this->assertEquals($dates['start_date'], (int)1277967600);
        $this->assertEquals($dates['end_date'], (int)1285916399);

        $dates = TTDate::getTimePeriodDates('last_quarter', strtotime('15-Jul-10 12:00 PM'));
        $this->assertEquals($dates['start_date'], (int)1270105200);
        $this->assertEquals($dates['end_date'], (int)1277967599);

        $dates = TTDate::getTimePeriodDates('last_90_days', strtotime('15-Jul-10 12:00 PM'));
        $this->assertEquals($dates['start_date'], (int)1271401200);
        $this->assertEquals($dates['end_date'], (int)1279177199);

        $dates = TTDate::getTimePeriodDates('this_year_1st_quarter', strtotime('15-Jul-10 12:00 PM'));
        $this->assertEquals($dates['start_date'], (int)1262332800);
        $this->assertEquals($dates['end_date'], (int)1270105199);

        $dates = TTDate::getTimePeriodDates('this_year_2nd_quarter', strtotime('15-Jul-10 12:00 PM'));
        $this->assertEquals($dates['start_date'], (int)1270105200);
        $this->assertEquals($dates['end_date'], (int)1277967599);

        $dates = TTDate::getTimePeriodDates('this_year_3rd_quarter', strtotime('15-Jul-10 12:00 PM'));
        $this->assertEquals($dates['start_date'], (int)1277967600);
        $this->assertEquals($dates['end_date'], (int)1285916399);

        $dates = TTDate::getTimePeriodDates('this_year_4th_quarter', strtotime('15-Jul-10 12:00 PM'));
        $this->assertEquals($dates['start_date'], (int)1285916400);
        $this->assertEquals($dates['end_date'], (int)1293868799);

        $dates = TTDate::getTimePeriodDates('last_year_1st_quarter', strtotime('15-Jul-10 12:00 PM'));
        $this->assertEquals($dates['start_date'], (int)1230796800);
        $this->assertEquals($dates['end_date'], (int)1238569199);

        $dates = TTDate::getTimePeriodDates('last_year_2nd_quarter', strtotime('15-Jul-10 12:00 PM'));
        $this->assertEquals($dates['start_date'], (int)1238569200);
        $this->assertEquals($dates['end_date'], (int)1246431599);

        $dates = TTDate::getTimePeriodDates('last_year_3rd_quarter', strtotime('15-Jul-10 12:00 PM'));
        $this->assertEquals($dates['start_date'], (int)1246431600);
        $this->assertEquals($dates['end_date'], (int)1254380399);

        $dates = TTDate::getTimePeriodDates('last_year_4th_quarter', strtotime('15-Jul-10 12:00 PM'));
        $this->assertEquals($dates['start_date'], (int)1254380400);
        $this->assertEquals($dates['end_date'], (int)1262332799);

        $dates = TTDate::getTimePeriodDates('last_3_months', strtotime('15-May-10 12:00 PM'));
        $this->assertEquals($dates['start_date'], (int)1266134400);
        $this->assertEquals($dates['end_date'], (int)1273906799);

        $dates = TTDate::getTimePeriodDates('last_6_months', strtotime('15-May-10 12:00 PM'));
        $this->assertEquals($dates['start_date'], (int)1258185600);
        $this->assertEquals($dates['end_date'], (int)1273906799);

        $dates = TTDate::getTimePeriodDates('last_9_months', strtotime('15-May-10 12:00 PM'));
        $this->assertEquals($dates['start_date'], (int)1250233200);
        $this->assertEquals($dates['end_date'], (int)1273906799);

        $dates = TTDate::getTimePeriodDates('last_12_months', strtotime('15-Jul-10 12:00 PM'));
        $this->assertEquals($dates['start_date'], (int)1247554800);
        $this->assertEquals($dates['end_date'], (int)1279177199);

        $dates = TTDate::getTimePeriodDates('this_year', strtotime('15-Jul-10 12:00 PM'));
        $this->assertEquals($dates['start_date'], (int)1262332800);
        $this->assertEquals($dates['end_date'], (int)1293868799);

        $dates = TTDate::getTimePeriodDates('last_year', strtotime('15-Jul-10 12:00 PM'));
        $this->assertEquals($dates['start_date'], (int)1230796800);
        $this->assertEquals($dates['end_date'], (int)1262332799);
    }

    public function test_DST()
    {
        TTDate::setTimeZone('PST8PDT'); //Force to timezone with DST.

        $this->assertEquals(TTDate::doesRangeSpanDST(strtotime('03-Nov-12 10:00PM'), strtotime('04-Nov-12 1:59AM')), false);
        $this->assertEquals(TTDate::doesRangeSpanDST(strtotime('03-Nov-12 10:00PM'), strtotime('04-Nov-12 2:00AM')), true);
        $this->assertEquals(TTDate::doesRangeSpanDST(strtotime('03-Nov-12 10:00PM'), strtotime('04-Nov-12 2:01AM')), true);
        $this->assertEquals(TTDate::doesRangeSpanDST(strtotime('04-Nov-12 12:30AM'), strtotime('04-Nov-12 1:59AM')), false);
        $this->assertEquals(TTDate::doesRangeSpanDST(strtotime('04-Nov-12 12:30AM'), strtotime('04-Nov-12 2:00AM')), true);
        $this->assertEquals(TTDate::doesRangeSpanDST(strtotime('04-Nov-12 1:00AM'), strtotime('04-Nov-12 6:30AM')), true);


        $this->assertEquals(TTDate::doesRangeSpanDST(strtotime('09-Mar-13 10:00PM'), strtotime('10-Mar-13 1:59AM')), false);
        $this->assertEquals(TTDate::doesRangeSpanDST(strtotime('09-Mar-13 10:00PM'), strtotime('10-Mar-13 2:00AM')), true);
        $this->assertEquals(TTDate::doesRangeSpanDST(strtotime('09-Mar-13 10:00PM'), strtotime('10-Mar-13 2:01AM')), true);
        $this->assertEquals(TTDate::doesRangeSpanDST(strtotime('10-Mar-13 12:30AM'), strtotime('10-Mar-13 1:59AM')), false);
        $this->assertEquals(TTDate::doesRangeSpanDST(strtotime('10-Mar-13 12:30AM'), strtotime('10-Mar-13 2:00AM')), true);
        $this->assertEquals(TTDate::doesRangeSpanDST(strtotime('10-Mar-13 1:30AM'), strtotime('10-Mar-13 6:30AM')), true);


        $this->assertEquals(TTDate::getDSTOffset(strtotime('03-Nov-12 10:00PM'), strtotime('04-Nov-12 1:59AM')), 0);
        $this->assertEquals(TTDate::getDSTOffset(strtotime('03-Nov-12 10:00PM'), strtotime('04-Nov-12 2:00AM')), -3600);
        $this->assertEquals(TTDate::getDSTOffset(strtotime('03-Nov-12 10:00PM'), strtotime('04-Nov-12 2:01AM')), -3600);
        $this->assertEquals(TTDate::getDSTOffset(strtotime('04-Nov-12 12:30AM'), strtotime('04-Nov-12 1:59AM')), 0);
        $this->assertEquals(TTDate::getDSTOffset(strtotime('04-Nov-12 12:30AM'), strtotime('04-Nov-12 2:00AM')), -3600);
        $this->assertEquals(TTDate::getDSTOffset(strtotime('04-Nov-12 1:00AM'), strtotime('04-Nov-12 6:30AM')), -3600);


        $this->assertEquals(TTDate::getDSTOffset(strtotime('09-Mar-13 10:00PM'), strtotime('10-Mar-13 1:59AM')), 0);
        $this->assertEquals(TTDate::getDSTOffset(strtotime('09-Mar-13 10:00PM'), strtotime('10-Mar-13 2:00AM')), 3600);
        $this->assertEquals(TTDate::getDSTOffset(strtotime('09-Mar-13 10:00PM'), strtotime('10-Mar-13 2:01AM')), 3600);
        $this->assertEquals(TTDate::getDSTOffset(strtotime('10-Mar-13 12:30AM'), strtotime('10-Mar-13 1:59AM')), 0);
        $this->assertEquals(TTDate::getDSTOffset(strtotime('10-Mar-13 12:30AM'), strtotime('10-Mar-13 2:00AM')), 3600);
        $this->assertEquals(TTDate::getDSTOffset(strtotime('10-Mar-13 1:30AM'), strtotime('10-Mar-13 6:30AM')), 3600);


        //This is a quirk with PHP assuming that PST/PDT both mean PST8PDT, and since 05-Nov-2016 is the day DST changed, it adds an hour and uses PDT timezone instead.
        //  Not really testing anything useful here, other than confirming this quirk exists.
        $this->assertEquals(TTDate::getDate('DATE+TIME', strtotime('04-Nov-2016 6:00PM PDT')), '04-Nov-16 6:00 PM PDT');
        $this->assertEquals(TTDate::getDate('DATE+TIME', strtotime('04-Nov-2016 6:00PM PST')), '04-Nov-16 7:00 PM PDT');
        $this->assertEquals(TTDate::getDate('DATE+TIME', strtotime('05-Nov-2016 6:00AM PST')), '05-Nov-16 7:00 AM PDT');
        $this->assertEquals(TTDate::getDate('DATE+TIME', strtotime('06-Nov-2016 6:00AM PST')), '06-Nov-16 6:00 AM PST');
        $this->assertEquals(TTDate::getDate('DATE+TIME', strtotime('06-Nov-2016 6:00AM PDT')), '06-Nov-16 5:00 AM PST');
    }

    public function test_inApplyFrequencyWindow()
    {
        //Annually
        $frequency_criteria = array(
            'month' => 1,
            'day_of_month' => 2,
            'day_of_week' => 0,
            //'quarter' => 0,
            'quarter_month' => 0,
            'date' => 0
        );

        //No range
        $this->assertEquals(TTDate::inApplyFrequencyWindow(20, strtotime('01-Jan-2010'), strtotime('01-Jan-2010'), $frequency_criteria), false);
        $this->assertEquals(TTDate::inApplyFrequencyWindow(20, strtotime('02-Jan-2010'), strtotime('02-Jan-2010'), $frequency_criteria), true);
        $this->assertEquals(TTDate::inApplyFrequencyWindow(20, strtotime('03-Jan-2010'), strtotime('03-Jan-2010'), $frequency_criteria), false);
        //Range
        $this->assertEquals(TTDate::inApplyFrequencyWindow(20, strtotime('01-Jan-2010'), strtotime('03-Jan-2010'), $frequency_criteria), true);
        $this->assertEquals(TTDate::inApplyFrequencyWindow(20, strtotime('02-Jan-2010 12:00PM'), strtotime('02-Jan-2010 12:00PM'), $frequency_criteria), true);
        $this->assertEquals(TTDate::inApplyFrequencyWindow(20, strtotime('02-Jan-2010 12:00AM'), strtotime('02-Jan-2010 11:59PM'), $frequency_criteria), true);
        $this->assertEquals(TTDate::inApplyFrequencyWindow(20, (strtotime('01-Jan-2010') - (86400 * 7)), strtotime('01-Jan-2010'), $frequency_criteria), false); //Range
        $this->assertEquals(TTDate::inApplyFrequencyWindow(20, (strtotime('02-Jan-2010') - (86400 * 7)), strtotime('02-Jan-2010'), $frequency_criteria), true); //Range
        $this->assertEquals(TTDate::inApplyFrequencyWindow(20, (strtotime('03-Jan-2010') - (86400 * 7)), strtotime('03-Jan-2010'), $frequency_criteria), true); //Range
        $this->assertEquals(TTDate::inApplyFrequencyWindow(20, (strtotime('04-Jan-2010') - (86400 * 7)), strtotime('04-Jan-2010'), $frequency_criteria), true); //Range
        $this->assertEquals(TTDate::inApplyFrequencyWindow(20, (strtotime('05-Jan-2010') - (86400 * 7)), strtotime('05-Jan-2010'), $frequency_criteria), true); //Range
        $this->assertEquals(TTDate::inApplyFrequencyWindow(20, (strtotime('06-Jan-2010') - (86400 * 7)), strtotime('06-Jan-2010'), $frequency_criteria), true); //Range
        $this->assertEquals(TTDate::inApplyFrequencyWindow(20, (strtotime('07-Jan-2010') - (86400 * 7)), strtotime('07-Jan-2010'), $frequency_criteria), true); //Range
        $this->assertEquals(TTDate::inApplyFrequencyWindow(20, (strtotime('08-Jan-2010') - (86400 * 7)), strtotime('08-Jan-2010'), $frequency_criteria), true); //Range
        $this->assertEquals(TTDate::inApplyFrequencyWindow(20, (strtotime('09-Jan-2010') - (86400 * 7)), strtotime('09-Jan-2010'), $frequency_criteria), true); //Range
        $this->assertEquals(TTDate::inApplyFrequencyWindow(20, (strtotime('10-Jan-2010') - (86400 * 7)), strtotime('10-Jan-2010'), $frequency_criteria), false); //Range


        //Quarterly
        $frequency_criteria = array(
            'month' => 0,
            'day_of_month' => 15,
            'day_of_week' => 0,
            //'quarter' => 3,
            'quarter_month' => 2,
            'date' => 0,
        );

        //No range
        $this->assertEquals(TTDate::inApplyFrequencyWindow(25, strtotime('14-Feb-2010'), strtotime('14-Feb-2010'), $frequency_criteria), false);
        $this->assertEquals(TTDate::inApplyFrequencyWindow(25, strtotime('15-Feb-2010'), strtotime('15-Feb-2010'), $frequency_criteria), true);
        $this->assertEquals(TTDate::inApplyFrequencyWindow(25, strtotime('16-Feb-2010'), strtotime('16-Feb-2010'), $frequency_criteria), false);
        $this->assertEquals(TTDate::inApplyFrequencyWindow(25, strtotime('14-May-2010'), strtotime('14-May-2010'), $frequency_criteria), false);
        $this->assertEquals(TTDate::inApplyFrequencyWindow(25, strtotime('15-May-2010'), strtotime('15-May-2010'), $frequency_criteria), true);
        $this->assertEquals(TTDate::inApplyFrequencyWindow(25, strtotime('16-May-2010'), strtotime('16-May-2010'), $frequency_criteria), false);
        $this->assertEquals(TTDate::inApplyFrequencyWindow(25, strtotime('14-Aug-2010'), strtotime('14-Aug-2010'), $frequency_criteria), false);
        $this->assertEquals(TTDate::inApplyFrequencyWindow(25, strtotime('15-Aug-2010'), strtotime('15-Aug-2010'), $frequency_criteria), true);
        $this->assertEquals(TTDate::inApplyFrequencyWindow(25, strtotime('16-Aug-2010'), strtotime('16-Aug-2010'), $frequency_criteria), false);
        $this->assertEquals(TTDate::inApplyFrequencyWindow(25, strtotime('14-Nov-2010'), strtotime('14-Nov-2010'), $frequency_criteria), false);
        $this->assertEquals(TTDate::inApplyFrequencyWindow(25, strtotime('15-Nov-2010'), strtotime('15-Nov-2010'), $frequency_criteria), true);
        $this->assertEquals(TTDate::inApplyFrequencyWindow(25, strtotime('16-Nov-2010'), strtotime('16-Nov-2010'), $frequency_criteria), false);

        $this->assertEquals(TTDate::inApplyFrequencyWindow(25, strtotime('14-Jan-2010'), strtotime('14-Jan-2010'), $frequency_criteria), false);
        $this->assertEquals(TTDate::inApplyFrequencyWindow(25, strtotime('14-Mar-2010'), strtotime('14-Mar-2010'), $frequency_criteria), false);
        $this->assertEquals(TTDate::inApplyFrequencyWindow(25, strtotime('14-Apr-2010'), strtotime('14-Apr-2010'), $frequency_criteria), false);
        $this->assertEquals(TTDate::inApplyFrequencyWindow(25, strtotime('14-Jun-2010'), strtotime('14-Jun-2010'), $frequency_criteria), false);
        $this->assertEquals(TTDate::inApplyFrequencyWindow(25, strtotime('14-Jul-2010'), strtotime('14-Jul-2010'), $frequency_criteria), false);
        $this->assertEquals(TTDate::inApplyFrequencyWindow(25, strtotime('14-Sep-2010'), strtotime('14-Sep-2010'), $frequency_criteria), false);
        $this->assertEquals(TTDate::inApplyFrequencyWindow(25, strtotime('14-Oct-2010'), strtotime('14-Oct-2010'), $frequency_criteria), false);
        $this->assertEquals(TTDate::inApplyFrequencyWindow(25, strtotime('14-Dec-2010'), strtotime('14-Dec-2010'), $frequency_criteria), false);

        //Range
        $this->assertEquals(TTDate::inApplyFrequencyWindow(25, strtotime('01-Aug-2010'), strtotime('15-Aug-2010'), $frequency_criteria), true);
        $this->assertEquals(TTDate::inApplyFrequencyWindow(25, strtotime('15-Aug-2010'), strtotime('20-Aug-2010'), $frequency_criteria), true);
        $this->assertEquals(TTDate::inApplyFrequencyWindow(25, strtotime('16-Aug-2010'), strtotime('20-Aug-2010'), $frequency_criteria), false);

        $this->assertEquals(TTDate::inApplyFrequencyWindow(25, strtotime('01-Jul-2010'), strtotime('15-Aug-2010'), $frequency_criteria), true);
        $this->assertEquals(TTDate::inApplyFrequencyWindow(25, strtotime('01-Jun-2010'), strtotime('15-Aug-2010'), $frequency_criteria), true);
        $this->assertEquals(TTDate::inApplyFrequencyWindow(25, strtotime('01-May-2010'), strtotime('15-Aug-2010'), $frequency_criteria), true);
        $this->assertEquals(TTDate::inApplyFrequencyWindow(25, strtotime('01-Apr-2010'), strtotime('15-Aug-2010'), $frequency_criteria), true);
        $this->assertEquals(TTDate::inApplyFrequencyWindow(25, strtotime('01-Apr-2009'), strtotime('15-Aug-2010'), $frequency_criteria), true);
        $this->assertEquals(TTDate::inApplyFrequencyWindow(25, strtotime('16-Aug-2009'), strtotime('14-Dec-2010'), $frequency_criteria), true);

        $this->assertEquals(TTDate::inApplyFrequencyWindow(25, strtotime('19-Aug-2009'), strtotime('14-Nov-2009'), $frequency_criteria), false);
        $this->assertEquals(TTDate::inApplyFrequencyWindow(25, strtotime('19-Aug-2009'), strtotime('15-Nov-2009'), $frequency_criteria), true);
        $this->assertEquals(TTDate::inApplyFrequencyWindow(25, strtotime('19-Aug-2009'), strtotime('15-Nov-2010'), $frequency_criteria), true);

        //Monthly
        $frequency_criteria = array(
            'month' => 2,
            'day_of_month' => 31,
            'day_of_week' => 0,
            //'quarter' => 0,
            'quarter_month' => 0,
            'date' => 0,
        );

        //No range
        $this->assertEquals(TTDate::inApplyFrequencyWindow(30, strtotime('27-Feb-2010'), strtotime('27-Feb-2010'), $frequency_criteria), false);
        $this->assertEquals(TTDate::inApplyFrequencyWindow(30, strtotime('28-Feb-2010'), strtotime('28-Feb-2010'), $frequency_criteria), true);
        $this->assertEquals(TTDate::inApplyFrequencyWindow(30, strtotime('01-Mar-2010'), strtotime('01-Mar-2010'), $frequency_criteria), false);
        $this->assertEquals(TTDate::inApplyFrequencyWindow(30, strtotime('27-Feb-2011'), strtotime('27-Feb-2011'), $frequency_criteria), false);
        $this->assertEquals(TTDate::inApplyFrequencyWindow(30, strtotime('28-Feb-2011'), strtotime('28-Feb-2011'), $frequency_criteria), true);
        $this->assertEquals(TTDate::inApplyFrequencyWindow(30, strtotime('01-Mar-2011'), strtotime('01-Mar-2011'), $frequency_criteria), false);
        $this->assertEquals(TTDate::inApplyFrequencyWindow(30, strtotime('28-Feb-2012'), strtotime('28-Feb-2012'), $frequency_criteria), false);
        $this->assertEquals(TTDate::inApplyFrequencyWindow(30, strtotime('29-Feb-2012'), strtotime('29-Feb-2012'), $frequency_criteria), true);
        $this->assertEquals(TTDate::inApplyFrequencyWindow(30, strtotime('01-Mar-2012'), strtotime('01-Mar-2012'), $frequency_criteria), false);

        //Range
        $this->assertEquals(TTDate::inApplyFrequencyWindow(30, strtotime('28-Feb-2010'), strtotime('05-Mar-2010'), $frequency_criteria), true);
        $this->assertEquals(TTDate::inApplyFrequencyWindow(30, strtotime('22-Feb-2010'), strtotime('28-Feb-2010'), $frequency_criteria), true);


        //Weekly
        $frequency_criteria = array(
            'month' => 0,
            'day_of_month' => 0,
            'day_of_week' => 2, //Tuesday
            //'quarter' => 0,
            'quarter_month' => 0,
            'date' => 0,
        );

        //No range
        $this->assertEquals(TTDate::inApplyFrequencyWindow(40, strtotime('12-Apr-2010'), strtotime('12-Apr-2010'), $frequency_criteria), false);
        $this->assertEquals(TTDate::inApplyFrequencyWindow(40, strtotime('13-Apr-2010'), strtotime('13-Apr-2010'), $frequency_criteria), true);
        $this->assertEquals(TTDate::inApplyFrequencyWindow(40, strtotime('14-Apr-2010'), strtotime('14-Apr-2010'), $frequency_criteria), false);

        //Range
        $this->assertEquals(TTDate::inApplyFrequencyWindow(40, strtotime('07-Apr-2010'), strtotime('12-Apr-2010'), $frequency_criteria), false);
        $this->assertEquals(TTDate::inApplyFrequencyWindow(40, strtotime('14-Apr-2010'), strtotime('19-Apr-2010'), $frequency_criteria), false);

        $this->assertEquals(TTDate::inApplyFrequencyWindow(40, strtotime('11-Apr-2010'), strtotime('17-Apr-2010'), $frequency_criteria), true);
        $this->assertEquals(TTDate::inApplyFrequencyWindow(40, strtotime('12-Apr-2010'), strtotime('18-Apr-2010'), $frequency_criteria), true);
        $this->assertEquals(TTDate::inApplyFrequencyWindow(40, strtotime('13-Apr-2010'), strtotime('19-Apr-2010'), $frequency_criteria), true);
        $this->assertEquals(TTDate::inApplyFrequencyWindow(40, strtotime('14-Apr-2010'), strtotime('20-Apr-2010'), $frequency_criteria), true);

        $this->assertEquals(TTDate::inApplyFrequencyWindow(40, strtotime('12-Apr-2010'), strtotime('17-Apr-2010'), $frequency_criteria), true);
        $this->assertEquals(TTDate::inApplyFrequencyWindow(40, strtotime('13-Apr-2010'), strtotime('17-Apr-2010'), $frequency_criteria), true);
        $this->assertEquals(TTDate::inApplyFrequencyWindow(40, strtotime('14-Apr-2010'), strtotime('17-Apr-2010'), $frequency_criteria), false);

        $this->assertEquals(TTDate::inApplyFrequencyWindow(40, strtotime('11-Apr-2010'), strtotime('18-Apr-2010'), $frequency_criteria), true);
        $this->assertEquals(TTDate::inApplyFrequencyWindow(40, strtotime('11-Apr-2010'), strtotime('19-Apr-2010'), $frequency_criteria), true);
        $this->assertEquals(TTDate::inApplyFrequencyWindow(40, strtotime('11-Apr-2010'), strtotime('24-Apr-2010'), $frequency_criteria), true);
        $this->assertEquals(TTDate::inApplyFrequencyWindow(40, strtotime('11-Apr-2010'), strtotime('25-Apr-2010'), $frequency_criteria), true);

        //Specific date
        $frequency_criteria = array(
            'month' => 0,
            'day_of_month' => 0,
            'day_of_week' => 0,
            //'quarter' => 0,
            'quarter_month' => 0,
            'date' => strtotime('01-Jan-2010'),
        );

        //No range
        $this->assertEquals(TTDate::inApplyFrequencyWindow(100, strtotime('01-Jan-2010'), strtotime('01-Jan-2010'), $frequency_criteria), true);
        $this->assertEquals(TTDate::inApplyFrequencyWindow(100, strtotime('31-Dec-2009'), strtotime('01-Jan-2010'), $frequency_criteria), true);
        $this->assertEquals(TTDate::inApplyFrequencyWindow(100, strtotime('01-Jan-2010'), strtotime('02-Jan-2010'), $frequency_criteria), true);
        $this->assertEquals(TTDate::inApplyFrequencyWindow(100, strtotime('30-Dec-2009'), strtotime('31-Dec-2009'), $frequency_criteria), false);
        $this->assertEquals(TTDate::inApplyFrequencyWindow(100, strtotime('02-Jan-2010'), strtotime('03-Jan-2010'), $frequency_criteria), false);

        $this->assertEquals(TTDate::inApplyFrequencyWindow(100, strtotime('01-Jan-2009'), strtotime('01-Jan-2010'), $frequency_criteria), true);
        $this->assertEquals(TTDate::inApplyFrequencyWindow(100, strtotime('01-Jan-2009'), strtotime('01-Jan-2011'), $frequency_criteria), true);
    }

    //Compare pure PHP implementation of EasterDays to PHP calendar extension.
    public function test_EasterDays()
    {
        if (function_exists('easter_days')) {
            for ($i = 2000; $i < 2050; $i++) {
                $this->assertEquals(easter_days($i), TTDate::getEasterDays($i));
            }
        }
    }

    public function testTimeZones()
    {
        $upf = new UserPreferenceFactory();
        $zones = $upf->getOptions('time_zone');

        foreach ($zones as $zone => $name) {
            $retval = TTDate::setTimeZone(Misc::trimSortPrefix($zone), true, true);
            $this->assertEquals($retval, true);
        }
    }

    public function testReportDatesA()
    {
        $uf = TTnew('UserFactory');
        $uf->getUserPreferenceObject()->setDateFormat('m-d-y');

        $pre_process_dates = TTDate::getReportDates(null, strtotime('03-Mar-2015 08:00AM'), false, $uf); //Sortable dates
        //var_dump( $pre_process_dates );
        $this->assertEquals($pre_process_dates['date_stamp'], '2015-03-03');

        TTDate::setDateFormat('Y-m-d');
        $this->assertEquals(TTDate::getReportDates('date_stamp', $pre_process_dates['date_stamp'], true, $uf), '2015-03-03');

        TTDate::setDateFormat('d-m-y');
        $this->assertEquals(TTDate::getReportDates('date_stamp', $pre_process_dates['date_stamp'], true, $uf), '03-03-15');
    }

    public function testDoesRangeSpanMidnight()
    {
        $this->assertEquals(TTDate::doesRangeSpanMidnight(strtotime('01-Jan-2016 8:00AM'), strtotime('01-Jan-2016 5:00PM'), false), false);
        $this->assertEquals(TTDate::doesRangeSpanMidnight(strtotime('01-Jan-2016 8:00AM'), strtotime('01-Jan-2016 11:59:59PM'), false), false);
        $this->assertEquals(TTDate::doesRangeSpanMidnight(strtotime('01-Jan-2016 8:00AM'), strtotime('01-Jan-2016 12:00:00AM'), false), false);
        $this->assertEquals(TTDate::doesRangeSpanMidnight(strtotime('01-Jan-2016 8:00AM'), strtotime('02-Jan-2016 12:00:00AM'), false), false);
        $this->assertEquals(TTDate::doesRangeSpanMidnight(strtotime('01-Jan-2016 8:00AM'), strtotime('02-Jan-2016 1:00:00AM'), false), true);
        $this->assertEquals(TTDate::doesRangeSpanMidnight(strtotime('02-Jan-2016 1:00:00AM'), strtotime('01-Jan-2016 8:00AM'), false), true);
        $this->assertEquals(TTDate::doesRangeSpanMidnight(strtotime('01-Jan-2016 8:00AM'), strtotime('03-Jan-2016 1:00:00AM'), false), true);
        $this->assertEquals(TTDate::doesRangeSpanMidnight(strtotime('01-Jan-2016 2:00:00AM'), strtotime('01-Jan-2016 2:00:00AM'), false), false);

        $this->assertEquals(TTDate::doesRangeSpanMidnight(strtotime('02-Jan-2016 12:00:00AM'), strtotime('02-Jan-2016 1:00:00AM'), false), false);
        $this->assertEquals(TTDate::doesRangeSpanMidnight(strtotime('01-Jan-2016 12:00:00AM'), strtotime('03-Jan-2016 12:00:00AM'), false), true);
        $this->assertEquals(TTDate::doesRangeSpanMidnight(strtotime('01-Jan-2016 12:00AM'), strtotime('02-Jan-2016 8:00:00AM'), false), true);
        $this->assertEquals(TTDate::doesRangeSpanMidnight(strtotime('01-Jan-2016 12:00AM'), strtotime('03-Jan-2016 8:00:00AM'), false), true);


        $this->assertEquals(TTDate::doesRangeSpanMidnight(strtotime('02-Jan-2016 12:00AM'), strtotime('02-Jan-2016 1:00:00AM'), true), true);
        $this->assertEquals(TTDate::doesRangeSpanMidnight(strtotime('02-Jan-2016 12:00AM'), strtotime('02-Jan-2016 12:00:00AM'), true), true);
        $this->assertEquals(TTDate::doesRangeSpanMidnight(strtotime('02-Jan-2016 12:00AM'), strtotime('03-Jan-2016 12:00:00AM'), true), true);
        $this->assertEquals(TTDate::doesRangeSpanMidnight(strtotime('01-Jan-2016 12:00AM'), strtotime('03-Jan-2016 8:00:00AM'), true), true);
        $this->assertEquals(TTDate::doesRangeSpanMidnight(strtotime('01-Jan-2016 8:00AM'), strtotime('02-Jan-2016 12:00:00AM'), true), true);
        $this->assertEquals(TTDate::doesRangeSpanMidnight(strtotime('01-Jan-2016 8:00AM'), strtotime('03-Jan-2016 8:00:00AM'), true), true);
    }


    public function testsplitDateRange()
    {
        $split_range_arr = TTDate::splitDateRangeAtMidnight(strtotime('01-Jan-2016 8:00AM'), strtotime('02-Jan-2016 8:00AM'));
        $this->assertEquals($split_range_arr[0]['start_time_stamp'], strtotime('01-Jan-2016 8:00AM'));
        $this->assertEquals($split_range_arr[0]['end_time_stamp'], strtotime('02-Jan-2016 12:00AM'));
        $this->assertEquals($split_range_arr[1]['start_time_stamp'], strtotime('02-Jan-2016 12:00AM'));
        $this->assertEquals($split_range_arr[1]['end_time_stamp'], strtotime('02-Jan-2016 8:00AM'));
        $this->assertEquals(count($split_range_arr), 2);

        $split_range_arr = TTDate::splitDateRangeAtMidnight(strtotime('01-Jan-2016 8:00AM'), strtotime('03-Jan-2016 8:00AM'));
        $this->assertEquals($split_range_arr[0]['start_time_stamp'], strtotime('01-Jan-2016 8:00AM'));
        $this->assertEquals($split_range_arr[0]['end_time_stamp'], strtotime('02-Jan-2016 12:00AM'));
        $this->assertEquals($split_range_arr[1]['start_time_stamp'], strtotime('02-Jan-2016 12:00AM'));
        $this->assertEquals($split_range_arr[1]['end_time_stamp'], strtotime('03-Jan-2016 12:00AM'));
        $this->assertEquals($split_range_arr[2]['start_time_stamp'], strtotime('03-Jan-2016 12:00AM'));
        $this->assertEquals($split_range_arr[2]['end_time_stamp'], strtotime('03-Jan-2016 8:00AM'));
        $this->assertEquals(count($split_range_arr), 3);


        $split_range_arr = TTDate::splitDateRangeAtMidnight(strtotime('01-Jan-2016 8:00AM'), strtotime('04-Jan-2016 8:00AM'));
        $this->assertEquals($split_range_arr[0]['start_time_stamp'], strtotime('01-Jan-2016 8:00AM'));
        $this->assertEquals($split_range_arr[0]['end_time_stamp'], strtotime('02-Jan-2016 12:00AM'));
        $this->assertEquals($split_range_arr[1]['start_time_stamp'], strtotime('02-Jan-2016 12:00AM'));
        $this->assertEquals($split_range_arr[1]['end_time_stamp'], strtotime('03-Jan-2016 12:00AM'));
        $this->assertEquals($split_range_arr[2]['start_time_stamp'], strtotime('03-Jan-2016 12:00AM'));
        $this->assertEquals($split_range_arr[2]['end_time_stamp'], strtotime('04-Jan-2016 12:00AM'));
        $this->assertEquals($split_range_arr[3]['start_time_stamp'], strtotime('04-Jan-2016 12:00AM'));
        $this->assertEquals($split_range_arr[3]['end_time_stamp'], strtotime('04-Jan-2016 8:00AM'));
        $this->assertEquals(count($split_range_arr), 4);


        $split_range_arr = TTDate::splitDateRangeAtMidnight(strtotime('01-Jan-2016 8:00AM'), strtotime('01-Jan-2016 8:00PM'), strtotime('01-Jan-2016 7:00AM'), strtotime('01-Jan-2016 3:00PM'));
        $this->assertEquals($split_range_arr[0]['start_time_stamp'], strtotime('01-Jan-2016 8:00AM'));
        $this->assertEquals($split_range_arr[0]['end_time_stamp'], strtotime('01-Jan-2016 3:00PM'));
        $this->assertEquals($split_range_arr[1]['start_time_stamp'], strtotime('01-Jan-2016 3:00PM'));
        $this->assertEquals($split_range_arr[1]['end_time_stamp'], strtotime('01-Jan-2016 8:00PM'));
        $this->assertEquals(count($split_range_arr), 2);


        $split_range_arr = TTDate::splitDateRangeAtMidnight(strtotime('01-Jan-2016 8:00AM'), strtotime('01-Jan-2016 8:00PM'), strtotime('01-Jan-2016 3:00PM'), strtotime('01-Jan-2016 9:00PM'));
        $this->assertEquals($split_range_arr[0]['start_time_stamp'], strtotime('01-Jan-2016 8:00AM'));
        $this->assertEquals($split_range_arr[0]['end_time_stamp'], strtotime('01-Jan-2016 3:00PM'));
        $this->assertEquals($split_range_arr[1]['start_time_stamp'], strtotime('01-Jan-2016 3:00PM'));
        $this->assertEquals($split_range_arr[1]['end_time_stamp'], strtotime('01-Jan-2016 8:00PM'));
        $this->assertEquals(count($split_range_arr), 2);


        $split_range_arr = TTDate::splitDateRangeAtMidnight(strtotime('01-Jan-2016 8:00AM'), strtotime('01-Jan-2016 8:00PM'), strtotime('01-Jan-2016 9:00AM'), strtotime('01-Jan-2016 7:00PM'));
        $this->assertEquals($split_range_arr[0]['start_time_stamp'], strtotime('01-Jan-2016 8:00AM'));
        $this->assertEquals($split_range_arr[0]['end_time_stamp'], strtotime('01-Jan-2016 9:00AM'));
        $this->assertEquals($split_range_arr[1]['start_time_stamp'], strtotime('01-Jan-2016 9:00AM'));
        $this->assertEquals($split_range_arr[1]['end_time_stamp'], strtotime('01-Jan-2016 7:00PM'));
        $this->assertEquals($split_range_arr[2]['start_time_stamp'], strtotime('01-Jan-2016 7:00PM'));
        $this->assertEquals($split_range_arr[2]['end_time_stamp'], strtotime('01-Jan-2016 8:00PM'));
        $this->assertEquals(count($split_range_arr), 3);


        $split_range_arr = TTDate::splitDateRangeAtMidnight(strtotime('01-Jan-2016 3:00PM'), strtotime('01-Jan-2016 11:00PM'), strtotime('01-Jan-2016 12:00AM'), strtotime('02-Jan-2016 12:00AM'));
        $this->assertEquals($split_range_arr[0]['start_time_stamp'], strtotime('01-Jan-2016 3:00PM'));
        $this->assertEquals($split_range_arr[0]['end_time_stamp'], strtotime('01-Jan-2016 11:00PM'));
        $this->assertEquals(count($split_range_arr), 1);


        $split_range_arr = TTDate::splitDateRangeAtMidnight(strtotime('01-Jan-2016 8:00AM'), strtotime('02-Jan-2016 8:00AM'), strtotime('01-Jan-2016 11:00PM'), strtotime('02-Jan-2016 1:00AM'));
        $this->assertEquals($split_range_arr[0]['start_time_stamp'], strtotime('01-Jan-2016 8:00AM'));
        $this->assertEquals($split_range_arr[0]['end_time_stamp'], strtotime('01-Jan-2016 11:00PM'));
        $this->assertEquals($split_range_arr[1]['start_time_stamp'], strtotime('01-Jan-2016 11:00PM'));
        $this->assertEquals($split_range_arr[1]['end_time_stamp'], strtotime('02-Jan-2016 12:00AM'));
        $this->assertEquals($split_range_arr[2]['start_time_stamp'], strtotime('02-Jan-2016 12:00AM'));
        $this->assertEquals($split_range_arr[2]['end_time_stamp'], strtotime('02-Jan-2016 1:00AM'));
        $this->assertEquals($split_range_arr[3]['start_time_stamp'], strtotime('02-Jan-2016 1:00AM'));
        $this->assertEquals($split_range_arr[3]['end_time_stamp'], strtotime('02-Jan-2016 8:00AM'));
        $this->assertEquals(count($split_range_arr), 4);

        $split_range_arr = TTDate::splitDateRangeAtMidnight(strtotime('01-Jan-2016 8:00AM'), strtotime('02-Jan-2016 8:00AM'), strtotime('01-Jan-2016 11:00PM'), strtotime('01-Jan-2016 1:00AM'));
        $this->assertEquals($split_range_arr[0]['start_time_stamp'], strtotime('01-Jan-2016 8:00AM'));
        $this->assertEquals($split_range_arr[0]['end_time_stamp'], strtotime('01-Jan-2016 11:00PM'));
        $this->assertEquals($split_range_arr[1]['start_time_stamp'], strtotime('01-Jan-2016 11:00PM'));
        $this->assertEquals($split_range_arr[1]['end_time_stamp'], strtotime('02-Jan-2016 12:00AM'));
        $this->assertEquals($split_range_arr[2]['start_time_stamp'], strtotime('02-Jan-2016 12:00AM'));
        $this->assertEquals($split_range_arr[2]['end_time_stamp'], strtotime('02-Jan-2016 1:00AM'));
        $this->assertEquals($split_range_arr[3]['start_time_stamp'], strtotime('02-Jan-2016 1:00AM'));
        $this->assertEquals($split_range_arr[3]['end_time_stamp'], strtotime('02-Jan-2016 8:00AM'));
        $this->assertEquals(count($split_range_arr), 4);

        $split_range_arr = TTDate::splitDateRangeAtMidnight(strtotime('01-Jan-2016 8:00AM'), strtotime('04-Jan-2016 8:00AM'), strtotime('01-Jan-2016 11:00PM'), strtotime('02-Jan-2016 1:00AM'));
        $this->assertEquals($split_range_arr[0]['start_time_stamp'], strtotime('01-Jan-2016 8:00AM'));
        $this->assertEquals($split_range_arr[0]['end_time_stamp'], strtotime('01-Jan-2016 11:00PM'));
        $this->assertEquals($split_range_arr[1]['start_time_stamp'], strtotime('01-Jan-2016 11:00PM'));
        $this->assertEquals($split_range_arr[1]['end_time_stamp'], strtotime('02-Jan-2016 12:00AM'));
        $this->assertEquals($split_range_arr[2]['start_time_stamp'], strtotime('02-Jan-2016 12:00AM'));
        $this->assertEquals($split_range_arr[2]['end_time_stamp'], strtotime('02-Jan-2016 1:00AM'));
        $this->assertEquals($split_range_arr[3]['start_time_stamp'], strtotime('02-Jan-2016 1:00AM'));
        $this->assertEquals($split_range_arr[3]['end_time_stamp'], strtotime('02-Jan-2016 11:00PM'));
        $this->assertEquals($split_range_arr[4]['start_time_stamp'], strtotime('02-Jan-2016 11:00PM'));
        $this->assertEquals($split_range_arr[4]['end_time_stamp'], strtotime('03-Jan-2016 12:00AM'));
        $this->assertEquals($split_range_arr[5]['start_time_stamp'], strtotime('03-Jan-2016 12:00AM'));
        $this->assertEquals($split_range_arr[5]['end_time_stamp'], strtotime('03-Jan-2016 1:00AM'));//should be 1am. things are wrong south of here.
        $this->assertEquals($split_range_arr[6]['start_time_stamp'], strtotime('03-Jan-2016 1:00AM'));
        $this->assertEquals($split_range_arr[6]['end_time_stamp'], strtotime('03-Jan-2016 11:00PM'));
        $this->assertEquals($split_range_arr[7]['start_time_stamp'], strtotime('03-Jan-2016 11:00PM'));
        $this->assertEquals($split_range_arr[7]['end_time_stamp'], strtotime('04-Jan-2016 12:00AM'));
        $this->assertEquals($split_range_arr[8]['start_time_stamp'], strtotime('04-Jan-2016 12:00AM'));
        $this->assertEquals($split_range_arr[8]['end_time_stamp'], strtotime('04-Jan-2016 1:00AM'));
        $this->assertEquals($split_range_arr[9]['start_time_stamp'], strtotime('04-Jan-2016 1:00AM'));
        $this->assertEquals($split_range_arr[9]['end_time_stamp'], strtotime('04-Jan-2016 8:00AM'));
        $this->assertEquals(count($split_range_arr), 10);

        $split_range_arr = TTDate::splitDateRangeAtMidnight(strtotime('01-Jan-2016 8:00AM'), strtotime('03-Jan-2016 8:00PM'), strtotime('01-Jan-2016 9:00AM'), strtotime('01-Jan-2016 7:00PM'));
        $this->assertEquals($split_range_arr[0]['start_time_stamp'], strtotime('01-Jan-2016 8:00AM'));
        $this->assertEquals($split_range_arr[0]['end_time_stamp'], strtotime('01-Jan-2016 9:00AM'));
        $this->assertEquals($split_range_arr[1]['start_time_stamp'], strtotime('01-Jan-2016 9:00AM'));
        $this->assertEquals($split_range_arr[1]['end_time_stamp'], strtotime('01-Jan-2016 7:00PM'));
        $this->assertEquals($split_range_arr[2]['start_time_stamp'], strtotime('01-Jan-2016 7:00PM'));
        $this->assertEquals($split_range_arr[2]['end_time_stamp'], strtotime('02-Jan-2016 12:00AM'));
        $this->assertEquals($split_range_arr[3]['start_time_stamp'], strtotime('02-Jan-2016 12:00AM'));
        $this->assertEquals($split_range_arr[3]['end_time_stamp'], strtotime('02-Jan-2016 9:00AM'));
        $this->assertEquals($split_range_arr[4]['start_time_stamp'], strtotime('02-Jan-2016 9:00AM'));
        $this->assertEquals($split_range_arr[4]['end_time_stamp'], strtotime('02-Jan-2016 7:00PM'));
        $this->assertEquals($split_range_arr[5]['start_time_stamp'], strtotime('02-Jan-2016 7:00PM'));
        $this->assertEquals($split_range_arr[5]['end_time_stamp'], strtotime('03-Jan-2016 12:00AM'));
        $this->assertEquals($split_range_arr[6]['start_time_stamp'], strtotime('03-Jan-2016 12:00AM'));
        $this->assertEquals($split_range_arr[6]['end_time_stamp'], strtotime('03-Jan-2016 9:00AM'));
        $this->assertEquals($split_range_arr[7]['start_time_stamp'], strtotime('03-Jan-2016 9:00AM'));
        $this->assertEquals($split_range_arr[7]['end_time_stamp'], strtotime('03-Jan-2016 7:00PM'));
        $this->assertEquals($split_range_arr[8]['start_time_stamp'], strtotime('03-Jan-2016 7:00PM'));
        $this->assertEquals($split_range_arr[8]['end_time_stamp'], strtotime('03-Jan-2016 8:00PM'));
        $this->assertEquals(count($split_range_arr), 9);

        //start and end only one day apart
        $split_range_arr = TTDate::splitDateRangeAtMidnight(strtotime('10-Mar-2016 8:00AM'), strtotime('11-Mar-2016 8:00PM'));
        $this->assertEquals($split_range_arr[0]['start_time_stamp'], strtotime('10-Mar-2016 8:00AM'));
        $this->assertEquals($split_range_arr[0]['end_time_stamp'], strtotime('11-Mar-2016 12:00AM'));
        $this->assertEquals($split_range_arr[1]['start_time_stamp'], strtotime('11-Mar-2016 12:00AM'));
        $this->assertEquals($split_range_arr[1]['end_time_stamp'], strtotime('11-Mar-2016 8:00PM'));
        $this->assertEquals(count($split_range_arr), 2);

        TTDate::setTimeZone('PST8PDT', true); //Force to timezone that observes DST.
        //spans daylight savings time in spring
        $split_range_arr = TTDate::splitDateRangeAtMidnight(strtotime('13-Mar-2016 8:00AM'), strtotime('14-Mar-2016 8:00PM'));
        $this->assertEquals($split_range_arr[0]['start_time_stamp'], strtotime('13-Mar-2016 8:00AM'));
        $this->assertEquals($split_range_arr[0]['end_time_stamp'], strtotime('14-Mar-2016 12:00AM'));
        $this->assertEquals($split_range_arr[1]['start_time_stamp'], strtotime('14-Mar-2016 12:00AM'));
        $this->assertEquals($split_range_arr[1]['end_time_stamp'], strtotime('14-Mar-2016 8:00PM'));
        $this->assertEquals(count($split_range_arr), 2);

        //spans daylight savings time in spring with filter
        $split_range_arr = TTDate::splitDateRangeAtMidnight(strtotime('13-Mar-2016 8:00AM'), strtotime('14-Mar-2016 8:00PM'), strtotime('01-Jan-2016 9:00AM'), strtotime('01-Jan-2016 7:00PM'));
        $this->assertEquals($split_range_arr[0]['start_time_stamp'], strtotime('13-Mar-2016 8:00AM'));
        $this->assertEquals($split_range_arr[0]['end_time_stamp'], strtotime('13-Mar-2016 9:00AM'));
        $this->assertEquals($split_range_arr[1]['start_time_stamp'], strtotime('13-Mar-2016 9:00AM'));
        $this->assertEquals($split_range_arr[1]['end_time_stamp'], strtotime('13-Mar-2016 7:00PM'));
        $this->assertEquals($split_range_arr[2]['start_time_stamp'], strtotime('13-Mar-2016 7:00PM'));
        $this->assertEquals($split_range_arr[2]['end_time_stamp'], strtotime('14-Mar-2016 12:00AM'));
        $this->assertEquals($split_range_arr[3]['start_time_stamp'], strtotime('14-Mar-2016 12:00AM'));
        $this->assertEquals($split_range_arr[3]['end_time_stamp'], strtotime('14-Mar-2016 9:00AM'));
        $this->assertEquals($split_range_arr[4]['start_time_stamp'], strtotime('14-Mar-2016 9:00AM'));
        $this->assertEquals($split_range_arr[4]['end_time_stamp'], strtotime('14-Mar-2016 7:00PM'));
        $this->assertEquals($split_range_arr[5]['start_time_stamp'], strtotime('14-Mar-2016 7:00PM'));
        $this->assertEquals($split_range_arr[5]['end_time_stamp'], strtotime('14-Mar-2016 8:00PM'));
        $this->assertEquals(count($split_range_arr), 6);

        //spans daylight savings time in spring with filter where the filter spans 11-2
        $split_range_arr = TTDate::splitDateRangeAtMidnight(strtotime('12-Mar-2016 8:00AM'), strtotime('13-Mar-2016 4:00AM'), strtotime('12-Mar-2016 11:00PM'), strtotime('13-Mar-2016 2:00AM'));
        $this->assertEquals($split_range_arr[0]['start_time_stamp'], strtotime('12-Mar-2016 8:00AM'));
        $this->assertEquals($split_range_arr[0]['end_time_stamp'], strtotime('12-Mar-2016 11:00PM'));
        $this->assertEquals($split_range_arr[1]['start_time_stamp'], strtotime('12-Mar-2016 11:00PM'));
        $this->assertEquals($split_range_arr[1]['end_time_stamp'], strtotime('13-Mar-2016 12:00AM'));
        $this->assertEquals($split_range_arr[2]['start_time_stamp'], strtotime('13-Mar-2016 12:00AM'));
        $this->assertEquals($split_range_arr[2]['end_time_stamp'], strtotime('13-Mar-2016 3:00AM'));
        $this->assertEquals($split_range_arr[3]['start_time_stamp'], strtotime('13-Mar-2016 3:00AM'));
        $this->assertEquals($split_range_arr[3]['end_time_stamp'], strtotime('13-Mar-2016 4:00AM'));
        $this->assertEquals(count($split_range_arr), 4);

        //spans daylight savings time in fall
        $split_range_arr = TTDate::splitDateRangeAtMidnight(strtotime('6-Nov-2016 8:00AM'), strtotime('7-Nov-2016 8:00PM'));
        $this->assertEquals($split_range_arr[0]['start_time_stamp'], strtotime('6-Nov-2016 8:00AM'));
        $this->assertEquals($split_range_arr[0]['end_time_stamp'], strtotime('7-Nov-2016 12:00AM'));
        $this->assertEquals($split_range_arr[1]['start_time_stamp'], strtotime('7-Nov-2016 12:00AM'));
        $this->assertEquals($split_range_arr[1]['end_time_stamp'], strtotime('7-Nov-2016 8:00PM'));
        $this->assertEquals(count($split_range_arr), 2);

        //spans daylight savings time in fall with filter
        $split_range_arr = TTDate::splitDateRangeAtMidnight(strtotime('6-Nov-2016 8:00AM'), strtotime('7-Nov-2016 8:00PM'), strtotime('01-Jan-2016 9:00AM'), strtotime('01-Jan-2016 7:00PM'));
        $this->assertEquals($split_range_arr[0]['start_time_stamp'], strtotime('6-Nov-2016 8:00AM'));
        $this->assertEquals($split_range_arr[0]['end_time_stamp'], strtotime('6-Nov-2016 9:00AM'));
        $this->assertEquals($split_range_arr[1]['start_time_stamp'], strtotime('6-Nov-2016 9:00AM'));
        $this->assertEquals($split_range_arr[1]['end_time_stamp'], strtotime('6-Nov-2016 7:00PM'));
        $this->assertEquals($split_range_arr[2]['start_time_stamp'], strtotime('6-Nov-2016 7:00PM'));
        $this->assertEquals($split_range_arr[2]['end_time_stamp'], strtotime('7-Nov-2016 12:00AM'));
        $this->assertEquals($split_range_arr[3]['start_time_stamp'], strtotime('7-Nov-2016 12:00AM'));
        $this->assertEquals($split_range_arr[3]['end_time_stamp'], strtotime('7-Nov-2016 9:00AM'));
        $this->assertEquals($split_range_arr[4]['start_time_stamp'], strtotime('7-Nov-2016 9:00AM'));
        $this->assertEquals($split_range_arr[4]['end_time_stamp'], strtotime('7-Nov-2016 7:00PM'));
        $this->assertEquals($split_range_arr[5]['start_time_stamp'], strtotime('7-Nov-2016 7:00PM'));
        $this->assertEquals($split_range_arr[5]['end_time_stamp'], strtotime('7-Nov-2016 8:00PM'));
        $this->assertEquals(count($split_range_arr), 6);

        //http://stackoverflow.com/questions/2613338/date-returning-wrong-day-although-the-timestamp-is-correct
        //fall daylight savings. illustrating the missing hour
        $split_range_arr = TTDate::splitDateRangeAtMidnight(strtotime('6-Nov-2016 8:00AM'), (strtotime('6-Nov-2016 1:00AM') + 7200));
        $this->assertEquals($split_range_arr[0]['start_time_stamp'], strtotime('06-Nov-2016 08:00AM'));
        $this->assertEquals($split_range_arr[0]['end_time_stamp'], strtotime('06-Nov-2016 02:00AM'));
        $this->assertEquals(count($split_range_arr), 1);

        //the missing hour shows up in the filter
        $split_range_arr = TTDate::splitDateRangeAtMidnight(strtotime('5-Nov-2016 8:00AM'), (strtotime('6-Nov-2016 1:00AM') + 7200), strtotime('6-Nov-2016 8:00AM'), strtotime('6-Nov-2016 1:00AM'));
        $this->assertEquals($split_range_arr[0]['start_time_stamp'], strtotime('5-Nov-2016 8:00AM'));
        $this->assertEquals($split_range_arr[0]['end_time_stamp'], strtotime('6-Nov-2016 12:00AM'));
        $this->assertEquals($split_range_arr[1]['start_time_stamp'], strtotime('6-Nov-2016 12:00AM'));
        $this->assertEquals($split_range_arr[1]['end_time_stamp'], strtotime('6-Nov-2016 1:00AM'));
        $this->assertEquals($split_range_arr[2]['start_time_stamp'], strtotime('6-Nov-2016 1:00AM'));
        $this->assertEquals($split_range_arr[2]['end_time_stamp'], strtotime('6-Nov-2016 2:00AM'));
        $this->assertEquals(count($split_range_arr), 3);

        //fall daylight savings. illustrating the missing hour
        $split_range_arr = TTDate::splitDateRangeAtMidnight(strtotime('5-Nov-2016 8:00AM'), (strtotime('6-Nov-2016 1:00AM') + 7200), strtotime('6-Nov-2016 8:00AM'), (strtotime('6-Nov-2016 1:00AM') + 3600));
        $this->assertEquals($split_range_arr[0]['start_time_stamp'], strtotime('05-Nov-2016 8:00AM'));
        $this->assertEquals($split_range_arr[0]['end_time_stamp'], strtotime('06-Nov-2016 12:00AM'));
        $this->assertEquals($split_range_arr[1]['start_time_stamp'], strtotime('06-Nov-2016 12:00AM'));
        $this->assertEquals($split_range_arr[1]['end_time_stamp'], strtotime('06-Nov-2016 1:00AM'));
        $this->assertEquals($split_range_arr[2]['start_time_stamp'], strtotime('06-Nov-2016 1:00AM'));
        $this->assertEquals($split_range_arr[2]['end_time_stamp'], strtotime('06-Nov-2016 2:00AM'));
        $this->assertEquals(count($split_range_arr), 3);

        //spring daylight savings. illustrating the extra hour
        $split_range_arr = TTDate::splitDateRangeAtMidnight(strtotime('13-Mar-2016 8:00AM'), (strtotime('13-Mar-2016 12:00AM') + 7200));
        $this->assertEquals($split_range_arr[0]['start_time_stamp'], strtotime('13-Mar-2016 8:00AM'));
        $this->assertEquals($split_range_arr[0]['end_time_stamp'], strtotime('13-Mar-2016 3:00AM'));
        $this->assertEquals(count($split_range_arr), 1);

        //spring daylight savings. illustrating the extra hour
        $split_range_arr = TTDate::splitDateRangeAtMidnight(strtotime('11-Mar-2016 8:00AM'), (strtotime('13-Mar-2016 12:00AM') + 7200), strtotime('13-Mar-2016 4:00AM'), (strtotime('13-Mar-2016 1:00AM') + 7200));
        $this->assertEquals($split_range_arr[0]['start_time_stamp'], strtotime('11-Mar-2016 8:00AM'));
        $this->assertEquals($split_range_arr[0]['end_time_stamp'], strtotime('12-Mar-2016 12:00AM'));
        $this->assertEquals($split_range_arr[1]['start_time_stamp'], strtotime('12-Mar-2016 12:00AM'));
        $this->assertEquals($split_range_arr[1]['end_time_stamp'], strtotime('12-Mar-2016 4:00AM'));
        $this->assertEquals($split_range_arr[2]['start_time_stamp'], strtotime('12-Mar-2016 4:00AM'));
        $this->assertEquals($split_range_arr[2]['end_time_stamp'], strtotime('13-Mar-2016 12:00AM'));
        $this->assertEquals($split_range_arr[3]['start_time_stamp'], strtotime('13-Mar-2016 12:00AM'));
        $this->assertEquals($split_range_arr[3]['end_time_stamp'], strtotime('13-Mar-2016 3:00AM'));
        $this->assertEquals(count($split_range_arr), 4);

        //leap year illustration
        $split_range_arr = TTDate::splitDateRangeAtMidnight(strtotime('28-Feb-2016 8:00AM'), (strtotime('1-Mar-2016 12:00AM') + 7200), strtotime('28-Feb-2016 4:00AM'), (strtotime('28-Feb-2016 1:00AM') + 7200));
        $this->assertEquals($split_range_arr[0]['start_time_stamp'], strtotime('28-Feb-2016 8:00AM'));
        $this->assertEquals($split_range_arr[0]['end_time_stamp'], strtotime('29-Feb-2016 12:00AM'));
        $this->assertEquals($split_range_arr[1]['start_time_stamp'], strtotime('29-Feb-2016 12:00AM'));
        $this->assertEquals($split_range_arr[1]['end_time_stamp'], strtotime('29-Feb-2016 3:00AM'));
        $this->assertEquals($split_range_arr[2]['start_time_stamp'], strtotime('29-Feb-2016 3:00AM'));
        $this->assertEquals($split_range_arr[2]['end_time_stamp'], strtotime('29-Feb-2016 4:00AM'));
        $this->assertEquals($split_range_arr[3]['start_time_stamp'], strtotime('29-Feb-2016 4:00AM'));
        $this->assertEquals($split_range_arr[3]['end_time_stamp'], strtotime('01-Mar-2016 12:00AM'));
        $this->assertEquals($split_range_arr[4]['start_time_stamp'], strtotime('01-Mar-2016 12:00AM'));
        $this->assertEquals($split_range_arr[4]['end_time_stamp'], strtotime('01-Mar-2016 2:00AM'));
        $this->assertEquals(count($split_range_arr), 5);

        $split_range_arr = TTDate::splitDateRangeAtMidnight(strtotime('Tue, 03 May 2016 06:00:00 -0600'), strtotime('Thu, 05 May 2016 15:00:00 -0600'), strtotime('Tue, 03 May 2016 08:00:00 -0600'), strtotime('Tue, 03 May 2016 15:00:00 -0600'));
        $this->assertEquals($split_range_arr[0]['start_time_stamp'], strtotime('03 May 2016 06:00:00 -0600'));
        $this->assertEquals($split_range_arr[0]['end_time_stamp'], strtotime('03 May 2016 08:00:00 -0600'));
        $this->assertEquals($split_range_arr[1]['start_time_stamp'], strtotime('Tue, 03 May 2016 08:00:00 -0600'));
        $this->assertEquals($split_range_arr[1]['end_time_stamp'], strtotime('03 May 2016 15:00:00 -0600'));
        $this->assertEquals($split_range_arr[2]['start_time_stamp'], strtotime('03 May 2016 15:00:00 -0600'));
        $this->assertEquals($split_range_arr[2]['end_time_stamp'], strtotime('04-May-2016 12:00AM'));
        $this->assertEquals($split_range_arr[3]['start_time_stamp'], strtotime('04-May-2016 12:00AM'));
        $this->assertEquals($split_range_arr[3]['end_time_stamp'], strtotime('04 May 2016 08:00:00 -0600'));
        $this->assertEquals($split_range_arr[4]['start_time_stamp'], strtotime('04 May 2016 08:00:00 -0600'));
        $this->assertEquals($split_range_arr[4]['end_time_stamp'], strtotime('04 May 2016 15:00:00 -0600'));
        $this->assertEquals($split_range_arr[5]['start_time_stamp'], strtotime('04 May 2016 15:00:00 -0600'));
        $this->assertEquals($split_range_arr[5]['end_time_stamp'], strtotime('05-May-2016 12:00AM'));
        $this->assertEquals($split_range_arr[6]['start_time_stamp'], strtotime('05-May-2016 12:00AM'));
        $this->assertEquals($split_range_arr[6]['end_time_stamp'], strtotime('05 May 2016 08:00:00 -0600'));
        $this->assertEquals($split_range_arr[7]['start_time_stamp'], strtotime('05 May 2016 08:00:00 -0600'));
        $this->assertEquals($split_range_arr[7]['end_time_stamp'], strtotime('05 May 2016 15:00:00 -0600'));
        $this->assertEquals(count($split_range_arr), 8);

        //multi-year test
        $split_range_arr = TTDate::splitDateRangeAtMidnight(strtotime('15-Jun-2010 8:00AM'), strtotime('30-Aug-2016 5:00PM'), strtotime('25-Jun-2010 2:00AM'), strtotime('26-Aug-2016 10:00PM'));
        //first 3 days
        $this->assertEquals($split_range_arr[0]['start_time_stamp'], strtotime('2010-06-15 08:00:00am'));
        $this->assertEquals($split_range_arr[0]['end_time_stamp'], strtotime('2010-06-15 10:00:00pm'));
        $this->assertEquals($split_range_arr[1]['start_time_stamp'], strtotime('2010-06-15 10:00:00pm'));
        $this->assertEquals($split_range_arr[1]['end_time_stamp'], strtotime('2010-06-16 12:00:00am'));
        $this->assertEquals($split_range_arr[2]['start_time_stamp'], strtotime('2010-06-16 12:00:00am'));
        $this->assertEquals($split_range_arr[2]['end_time_stamp'], strtotime('2010-06-16 02:00:00am'));
        //a few from the middle
        $this->assertEquals($split_range_arr[3000]['start_time_stamp'], strtotime('2013-03-11 02:00:00am'));
        $this->assertEquals($split_range_arr[3000]['end_time_stamp'], strtotime('2013-03-11 10:00:00pm'));
        $this->assertEquals($split_range_arr[3001]['start_time_stamp'], strtotime('2013-03-11 10:00:00pm'));
        $this->assertEquals($split_range_arr[3001]['end_time_stamp'], strtotime('2013-03-12 12:00:00am'));
        $this->assertEquals($split_range_arr[3002]['start_time_stamp'], strtotime('2013-03-12 12:00:00am'));
        $this->assertEquals($split_range_arr[3002]['end_time_stamp'], strtotime('2013-03-12 02:00:00am'));
        //last 3 days
        $this->assertEquals($split_range_arr[6802]['start_time_stamp'], strtotime('2016-08-29 10:00:00pm'));
        $this->assertEquals($split_range_arr[6802]['end_time_stamp'], strtotime('2016-08-30 12:00:00am'));
        $this->assertEquals($split_range_arr[6803]['start_time_stamp'], strtotime('2016-08-30 12:00:00am'));
        $this->assertEquals($split_range_arr[6803]['end_time_stamp'], strtotime('2016-08-30 02:00:00am'));
        $this->assertEquals($split_range_arr[6804]['start_time_stamp'], strtotime('2016-08-30 02:00:00am'));
        $this->assertEquals($split_range_arr[6804]['end_time_stamp'], strtotime('2016-08-30 05:00:00pm'));
        $this->assertEquals(count($split_range_arr), 6805);
    }

    /**
     * Magic days and all the problems they leave for us.
     */
    public function testDSTMagic()
    {
        TTDate::setTimeFormat('g:i A T');

        TTDate::setTimeZone('PST8PDT', true); //Force to timezone that observes DST.
        $time_stamp = 1457859600;
        $this->assertEquals(strtotime('13-Mar-2016 1:00AM'), $time_stamp);
        $this->assertEquals(TTDate::getDate('DATE+TIME', $time_stamp), '13-Mar-16 1:00 AM PST');

        $this->assertEquals(strtotime('13-Mar-2016 2:00AM'), ($time_stamp + 3600));
        $this->assertEquals(TTDate::getDate('DATE+TIME', ($time_stamp + 3600)), '13-Mar-16 3:00 AM PDT');
        $this->assertEquals(TTDate::getDate('DATE+TIME', ($time_stamp + 86400)), '14-Mar-16 2:00 AM PDT');

        TTDate::setTimeFormat('g:i A');
        TTDate::setTimeZone('Etc/GMT+8', true); //Force to timezone that does not observe DST.
        $time_stamp = 1457859600;
        $this->assertEquals(strtotime('13-Mar-2016 1:00AM PST'), $time_stamp);
        $this->assertEquals(TTDate::getDate('DATE+TIME', $time_stamp), '13-Mar-16 1:00 AM'); //Was: GMT+8 - But some versions of PHP return "-08", so just ignore the timezone setting for this case.

        $this->assertEquals(strtotime('13-Mar-2016 2:00AM PST'), ($time_stamp + 3600));
        $this->assertEquals(TTDate::getDate('DATE+TIME', ($time_stamp + 3600)), '13-Mar-16 2:00 AM'); //Was: GMT+8
        $this->assertEquals(TTDate::getDate('DATE+TIME', ($time_stamp + 86400)), '14-Mar-16 1:00 AM'); //Was: GMT+8

        TTDate::setTimeFormat('g:i A T');
        TTDate::setTimeZone('PST8PDT', true); //Force to timezone that observes DST.
        $time_stamp = 1478419200;
        $this->assertEquals(strtotime('06-Nov-2016 1:00AM'), $time_stamp);
        $this->assertEquals(TTDate::getDate('DATE+TIME', $time_stamp), '06-Nov-16 1:00 AM PDT');

        $this->assertEquals(TTDate::getDate('DATE+TIME', ($time_stamp + 3600)), '06-Nov-16 1:00 AM PST');

        $this->assertEquals(strtotime('06-Nov-2016 2:00AM'), ($time_stamp + 7200));
        $this->assertEquals(TTDate::getDate('DATE+TIME', ($time_stamp + 7200)), '06-Nov-16 2:00 AM PST');
        $this->assertEquals(TTDate::getDate('DATE+TIME', ($time_stamp + 86400)), '07-Nov-16 12:00 AM PST');


        //http://stackoverflow.com/questions/2613338/date-returning-wrong-day-although-the-timestamp-is-correct
        //illustrating that +86400 will not always give you tomorrow.
        $time_stamp = strtotime('05-Nov-2016 12:00AM');
        $time_stamp += 86400;
        $this->assertEquals(TTDate::getDate('DATE+TIME', ($time_stamp)), '06-Nov-16 12:00 AM PDT'); //normal operation
        $time_stamp += 86400;
        $this->assertEquals(TTDate::getDate('DATE+TIME', ($time_stamp)), '06-Nov-16 11:00 PM PST'); //extra day!!!
        $time_stamp += 86400;
        $this->assertEquals(TTDate::getDate('DATE+TIME', ($time_stamp)), '07-Nov-16 11:00 PM PST'); //normal operation

        //and the same for fall daylight savings
        $time_stamp = strtotime('15-Mar-2016 12:00AM');
        $time_stamp -= 86400;
        $this->assertEquals(TTDate::getDate('DATE+TIME', ($time_stamp)), '14-Mar-16 12:00 AM PDT'); //normal operation
        $time_stamp -= 86400;
        $this->assertEquals(TTDate::getDate('DATE+TIME', ($time_stamp)), '12-Mar-16 11:00 PM PST'); //missing day!!! where is 13th?
        $time_stamp -= 86400;
        $this->assertEquals(TTDate::getDate('DATE+TIME', ($time_stamp)), '11-Mar-16 11:00 PM PST'); //normal operation


        //illustrating that +86400 will not always give you tomorrow, but the middle day epoch will dodge the issue.
        //if we do all the math on the middle of day epoch and continually force it back to noon we can avoid problems with dst
        $time_stamp = TTDate::getMiddleDayEpoch(strtotime('04-Nov-2016 12:00AM'));
        $time_stamp = TTDate::getMiddleDayEpoch($time_stamp + 86400);
        $this->assertEquals(TTDate::getDate('DATE+TIME', ($time_stamp)), '05-Nov-16 12:00 PM PDT'); //normal operation
        $time_stamp = TTDate::getMiddleDayEpoch($time_stamp + 86400);
        $this->assertEquals(TTDate::getDate('DATE+TIME', ($time_stamp)), '06-Nov-16 12:00 PM PST'); //normal operation
        $time_stamp = TTDate::getMiddleDayEpoch($time_stamp + 86400);
        $this->assertEquals(TTDate::getDate('DATE+TIME', ($time_stamp)), '07-Nov-16 12:00 PM PST'); //normal operation
        $time_stamp = TTDate::getMiddleDayEpoch($time_stamp + 86400);
        $this->assertEquals(TTDate::getDate('DATE+TIME', ($time_stamp)), '08-Nov-16 12:00 PM PST'); //normal operation
    }

    public function testGetDateArray()
    {
        $date_arr = TTDate::getDateArray(strtotime('01-Dec-2016'), strtotime('31-Dec-2016')); //No DayOfWeek filter.
        $this->assertEquals(count($date_arr), 31);

        $date_arr = TTDate::getDateArray(strtotime('01-Dec-2016'), strtotime('31-Dec-2016'), false); //No DayOfWeek filter.
        $this->assertEquals(count($date_arr), 31);

        $date_arr = TTDate::getDateArray(strtotime('01-Dec-2016'), strtotime('31-Dec-2016'), 0); //Filter Sundays
        $this->assertEquals(count($date_arr), 4);
        $this->assertEquals($date_arr[0], strtotime('04-Dec-2016'));
        $this->assertEquals($date_arr[1], strtotime('11-Dec-2016'));
        $this->assertEquals($date_arr[2], strtotime('18-Dec-2016'));
        $this->assertEquals($date_arr[3], strtotime('25-Dec-2016'));

        $date_arr = TTDate::getDateArray(strtotime('01-Dec-2016'), strtotime('31-Dec-2016'), 1); //Filter Mondays
        $this->assertEquals(count($date_arr), 4);

        $date_arr = TTDate::getDateArray(strtotime('01-Dec-2016'), strtotime('31-Dec-2016'), 2); //Filter Tuesdays
        $this->assertEquals(count($date_arr), 4);

        $date_arr = TTDate::getDateArray(strtotime('01-Dec-2016'), strtotime('31-Dec-2016'), 3); //Filter Wednesday
        $this->assertEquals(count($date_arr), 4);

        $date_arr = TTDate::getDateArray(strtotime('01-Dec-2016'), strtotime('31-Dec-2016'), 4); //Filter Thursdays
        $this->assertEquals(count($date_arr), 5);

        $date_arr = TTDate::getDateArray(strtotime('01-Dec-2016'), strtotime('31-Dec-2016'), 5); //Filter Fridays
        $this->assertEquals(count($date_arr), 5);

        $date_arr = TTDate::getDateArray(strtotime('01-Dec-2016'), strtotime('31-Dec-2016'), 6); //Filter Saturdays
        $this->assertEquals(count($date_arr), 5);
        $this->assertEquals($date_arr[0], strtotime('03-Dec-2016'));
        $this->assertEquals($date_arr[1], strtotime('10-Dec-2016'));
        $this->assertEquals($date_arr[2], strtotime('17-Dec-2016'));
        $this->assertEquals($date_arr[3], strtotime('24-Dec-2016'));
        $this->assertEquals($date_arr[4], strtotime('31-Dec-2016'));
    }
}
