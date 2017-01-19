<?php
/*********************************************************************************
 * This file is part of "Fairness", a Payroll and Time Management program.
 * Fairness is Copyright 2013 Aydan Coskun (aydan.ayfer.coskun@gmail.com)
 * Portions of this software are Copyright (C) 2003 - 2013 TimeTrex Software Inc.
 * because Fairness is a fork of "TimeTrex Workforce Management" Software.
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
 * $Revision: 8603 $
 * $Id: AddPayPeriod.php 8603 2012-12-14 01:36:15Z ipso $
 * $Date: 2012-12-13 17:36:15 -0800 (Thu, 13 Dec 2012) $
 */
/*
 * Adds pay periods X hrs in advance, so schedules/shifts have something to attach to.
 * This file should/can be run as often as it needs to (once an hour)
 *
 */
require_once( dirname(__FILE__) . DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'includes'. DIRECTORY_SEPARATOR .'global.inc.php');
require_once( dirname(__FILE__) . DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'includes'. DIRECTORY_SEPARATOR .'CLI.inc.php');

$current_epoch = TTDate::getTime();

//If offset is only 24hrs then adding user_date rows can happen before the pay period
//was added. Add pay periods 48hrs in advance now?
$offset = 86400*2; //48hrs

$ppslf = new PayPeriodScheduleListFactory();

$clf = new CompanyListFactory();
$clf->getAll();
if ( $clf->getRecordCount() > 0 ) {
	foreach ( $clf as $c_obj ) {
		if ( $c_obj->getStatus() != 30 ) {
			//Get all pay period schedules.
			$ppslf->getByCompanyId( $c_obj->getId() );
			foreach ($ppslf as $pay_period_schedule) {
				$end_date = NULL;

				$pay_period_schedule->createNextPayPeriod($end_date, $offset);
				if ( PRODUCTION == TRUE AND DEMO_MODE == FALSE ) {
					$pay_period_schedule->forceClosePreviousPayPeriods( $current_epoch );
				}

				unset($ppf);
				unset($pay_period_schedule);
			}
		}
	}
}
Debug::writeToLog();
Debug::Display();
?>