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

/*
 * Adds pay periods X hrs in advance, so schedules/shifts have something to attach to.
 * This file should/can be run as often as it needs to (once an hour)
 *
 */
require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'global.inc.php');
require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'CLI.inc.php');

$current_epoch = TTDate::getTime();

//If offset is only 24hrs then adding user_date rows can happen before the pay period
//was added. Add pay periods 48hrs in advance now?
$offset = 86400 * 2; //48hrs

$ppslf = new PayPeriodScheduleListFactory();

$clf = new CompanyListFactory();
$clf->getByStatusID(array(10, 20, 23), null, array('a.id' => 'asc'));
if ($clf->getRecordCount() > 0) {
    foreach ($clf as $c_obj) {
        if ($c_obj->getStatus() != 30) {
            //Get all pay period schedules.
            $ppslf->getByCompanyId($c_obj->getId());
            foreach ($ppslf as $pay_period_schedule) {
                $end_date = null;

                $pay_period_schedule->createNextPayPeriod($end_date, $offset);
                if (PRODUCTION == true) {
                    $pay_period_schedule->forceClosePreviousPayPeriods($current_epoch);
                }

                unset($ppf);
                unset($pay_period_schedule);
            }
        }
    }
}
Debug::writeToLog();
Debug::Display();
