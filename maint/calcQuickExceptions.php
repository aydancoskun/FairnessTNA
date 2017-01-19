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
 * $Id: calcExceptions.php 2116 2008-09-05 18:49:48Z ipso $
 * $Date: 2008-09-05 11:49:48 -0700 (Fri, 05 Sep 2008) $
 */
/*
 * Calculate Exceptions for the previous day. This helps especially for
 * the "Unscheuled Absence" exception.
 *
 * Run this once a day. AFTER AddUserDate
 */
require_once( dirname(__FILE__) . DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'includes'. DIRECTORY_SEPARATOR .'global.inc.php');
require_once( dirname(__FILE__) . DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'includes'. DIRECTORY_SEPARATOR .'CLI.inc.php');

//Debug::setVerbosity(5);

$execution_time = time();


//Calculate exceptions just for today and yesterday, because some shifts may start late in the day and need to be handled first thing in the morning.
//Make sure we also go one day in the future too, since the servers can be PST and if its 11:00PM, it will stop at midnight for that day, so
//shifts that would have already started in a different timezone (say EST) will not receive exceptions until we have moved into the next day for PST (3hrs late)
$start_date = TTDate::getBeginDayEpoch( (TTDate::getMiddleDayEpoch( $execution_time )-86400) );
$end_date = TTDate::getEndDayEpoch( (TTDate::getMiddleDayEpoch( $execution_time )+86400) );

$udlf = new UserDateListFactory();
//Use optimized query to speed this process up significantly.
$udlf->getMidDayExceptionsByStartDateAndEndDateAndPayPeriodStatus( $start_date, $end_date, array(10,12,15,30) );
Debug::text(' calcQuickExceptions: Start Date: '. TTDate::getDate('DATE+TIME', $start_date ) .' End Date: '. TTDate::getDate('DATE+TIME', $end_date ) .' User Date Rows: '. $udlf->getRecordCount(), __FILE__, __LINE__, __METHOD__,5);
if ( $udlf->getRecordCount() > 0 ) {
	$i=0;
	foreach ($udlf as $ud_obj) {
		$user_obj_prefs = $ud_obj->getUserObject()->getUserPreferenceObject();
		if ( is_object( $user_obj_prefs ) ) {
			$user_obj_prefs->setTimeZonePreferences();
		} else {
			//Use system timezone.
			TTDate::setTimeZone();
		}

		Debug::text('('.$i.'). User: '. $ud_obj->getUser() .' Date: '. TTDate::getDate('DATE+TIME', $ud_obj->getDateStamp() ) .' User Date ID: '. $ud_obj->getId(), __FILE__, __LINE__, __METHOD__,5);

		//Calculate pre-mature exceptions, so pre-mature Missing Out Punch exceptions arn't made active until they are ready.
		//Don't calculate future exceptions though.
		ExceptionPolicyFactory::calcExceptions( $ud_obj->getId(), TRUE, FALSE );

		TTDate::setTimeZone();

		$i++;
	}
}
Debug::text(' calcQuickExceptions: Done', __FILE__, __LINE__, __METHOD__,5);

Debug::writeToLog();
Debug::Display();
?>