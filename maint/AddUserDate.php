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
 * $Revision: 2116 $
 * $Id: AddUserDate.php 2116 2008-09-05 18:49:48Z ipso $
 * $Date: 2008-09-05 11:49:48 -0700 (Fri, 05 Sep 2008) $
 */
/*
 * Adds a user_date row for every ACTIVE user, every day
 * This fixes a limitation when employees are switched from one pay period
 * schedule to another, then they click on a day without a user_date row.
 * TimeTrex doesn't know which pay period the day belongs too.
 *
 *
 * Run this twice a day. AFTER AddPayPeriod if possible.
 */
require_once( dirname(__FILE__) . DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'includes'. DIRECTORY_SEPARATOR .'global.inc.php');
require_once( dirname(__FILE__) . DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'includes'. DIRECTORY_SEPARATOR .'CLI.inc.php');

$clf = new CompanyListFactory();
$clf->getAll();
if ( $clf->getRecordCount() > 0 ) {
	foreach ( $clf as $c_obj ) {
		if ( $c_obj->getStatus() != 30 ) {

			$ppslf = new PayPeriodScheduleListFactory();
			$ulf = new UserListFactory();
			$ulf->getByCompanyId( $c_obj->getId() );
			if ( $ulf->getRecordCount() > 0 ) {

				$i=0;
				foreach ($ulf as $u_obj) {
					if ( $u_obj->getStatus() != 10 ) {
						continue;
					}
					Debug::text($i .'. User: '. $u_obj->getUserName(), __FILE__, __LINE__, __METHOD__,10);

					//Find the pay period schedule for each user and change the timezone to that of the pay period schedule.
					$ppslf->getByCompanyIdAndUserId($c_obj->getId(), $u_obj->getId() );
					if ( $ppslf->getRecordCount() > 0 ) {
						$pps_obj = $ppslf->getCurrent();
						$pps_obj->setPayPeriodTimeZone();

						//Insert user date row for TOMORROW.
						$epoch = (time()+(86400+3601));
						//$epoch = strtotime('22-Jan-08');
						UserDateFactory::findOrInsertUserDate( $u_obj->getId(),  TTDate::getBeginDayEpoch( $epoch ) );
					}

					$i++;
				}
			}
		} else {
			Debug::text('Company is not ACTIVE: '. $c_obj->getId(), __FILE__, __LINE__, __METHOD__, 10);
		}
	}
}
Debug::writeToLog();
Debug::Display();
?>