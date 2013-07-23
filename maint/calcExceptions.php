<?php
/*********************************************************************************
 * TimeTrex is a Payroll and Time Management program developed by
 * TimeTrex Software Inc. Copyright (C) 2003 - 2013 TimeTrex Software Inc.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License version 3 as published by
 * the Free Software Foundation with the addition of the following permission
 * added to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED
 * WORK IN WHICH THE COPYRIGHT IS OWNED BY TIMETREX, TIMETREX DISCLAIMS THE
 * WARRANTY OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE.  See the GNU Affero General Public License for more
 * details.
 *
 * You should have received a copy of the GNU Affero General Public License along
 * with this program; if not, see http://www.gnu.org/licenses or write to the Free
 * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301 USA.
 *
 * You can contact TimeTrex headquarters at Unit 22 - 2475 Dobbin Rd. Suite
 * #292 Westbank, BC V4T 2E9, Canada or at email address info@timetrex.com.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License
 * version 3, these Appropriate Legal Notices must retain the display of the
 * "Powered by TimeTrex" logo. If the display of the logo is not reasonably
 * feasible for technical reasons, the Appropriate Legal Notices must display
 * the words "Powered by TimeTrex".
 ********************************************************************************/
/*
 * $Revision: 8904 $
 * $Id: calcExceptions.php 8904 2013-01-20 17:21:15Z ipso $
 * $Date: 2013-01-20 09:21:15 -0800 (Sun, 20 Jan 2013) $
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

$clf = new CompanyListFactory();
$clf->getAll();

$x=0;
if ( $clf->getRecordCount() > 0 ) {
	foreach ( $clf as $c_obj ) {

		if ( $c_obj->getStatus() != 30 ) {
			$company_start_time = microtime(TRUE);
			Debug::text('Company: '. $c_obj->getName() .'('.$c_obj->getId().')', __FILE__, __LINE__, __METHOD__,5);

			//Recalculate at least the last two days.
			$start_date = TTDate::getMiddleDayEpoch( $execution_time ) - (86400*2);
			$end_date = TTDate::getMiddleDayEpoch( time() ) - (86400);

			//Get the last time cron ran this script.
			$cjlf = new CronJobListFactory();
			$cjlf->getByName( 'calcExceptions');
			if ( $cjlf->getRecordCount() > 0 ) {
				foreach( $cjlf as $cj_obj ) {
					$tmp_start_date = $cj_obj->getLastRunDate();
					if ( $tmp_start_date < $start_date ) {
						$start_date = $tmp_start_date;
						Debug::text('  CRON Job hasnt run in more then 48hrs, reducing Start Date to: '. TTDate::getDate('DATE+TIME', $start_date ) , __FILE__, __LINE__, __METHOD__,5);
					}
				}
			}
			unset($cjlf, $cj_obj, $tmp_start_date);

			//Get maximum shift time for each pay period schedule, so we know how far back
			//we have to recalculate days at the minimum.
			$ppslf = new PayPeriodScheduleListFactory();
			$ppslf->getByCompanyId( $c_obj->getId() );
			if ( $ppslf->getRecordCount() > 0 ) {
				foreach( $ppslf as $pps_obj ) {
					$tmp_start_date = TTDate::getMiddleDayEpoch( $execution_time ) - $pps_obj->getMaximumShiftTime();
					if ( $tmp_start_date < $start_date ) {
						$start_date = $tmp_start_date;
						Debug::text('  Maximum Shift Time is greater then 48hrs, reducing Start Date to: '. TTDate::getDate('DATE+TIME', $start_date ) , __FILE__, __LINE__, __METHOD__,5);
					}
				}
			}
			unset($ppslf, $pps_obj, $tmp_start_date);

			//Get earliest pre_mature exception in a NON-closed pay period.
			$elf = new ExceptionListFactory();
			$elf->getByCompanyIDAndTypeAndPayPeriodStatus($c_obj->getId(), 5, array(10,12,15,30), 1, NULL, NULL, array( 'b.date_stamp' => 'asc' ) ); //Limit 1
			if ( $elf->getRecordCount() > 0 ) {
				foreach( $elf as $e_obj ) {
					$tmp_start_date = $e_obj->getUserDateObject()->getDateStamp();
					if ( $tmp_start_date < $start_date ) {
						$start_date = $tmp_start_date;
						Debug::text('  Pre-Mature exceptions occur before start date, reducing to: '. TTDate::getDate('DATE+TIME', $start_date ) .'('. $e_obj->getId().')', __FILE__, __LINE__, __METHOD__,5);
					}
				}
			}
			unset($elf, $e_obj, $tmp_start_date);

			$udlf = new UserDateListFactory();
			$udlf->getByCompanyIdAndStartDateAndEndDateAndPayPeriodStatus($c_obj->getId(), $start_date, $end_date, array(10,12,15,30) );
			Debug::text(' Start Date: '. TTDate::getDate('DATE+TIME', $start_date ) .' End Date: '. TTDate::getDate('DATE+TIME', $end_date ) .' User Date Rows: '. $udlf->getRecordCount(), __FILE__, __LINE__, __METHOD__,5);

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

					//Recalculate system time, and exceptions for the day.
					//Because if its a Monday, it will also recalculate the rest of the days in the week.
					//Shouldn't be a big deal though.
					//This isn't needed, since we now do it in AddRecurringScheduleShift, so dock time is
					//applied at the beginning of the day.
					//The problem is that AddRecurringScheduleShift does it, then for the entire day someone with
					//a dock policy shows up as dock time. Some users have complained about this a few times.

					//Reason for doing two days ago is that if someone starts a shift at 11pm, but doesn't end it in
					//time, it still needs to be re-calculated a day later.
					//Could maybe get around this by getting all punches of yesterday, and getting their date_ids
					//and just recalculating those.

					//Enable pre-mature exceptions if we're recalculating just one day ago.
					//Problem is a late shift on say Monday: 2:00PM to 11:00PM won't trigger the exception at 1AM the next day,
					//but by 1AM the following day (2days later) its too late and emails are disabled if enable_premature_exceptions are disabled.
					$enable_premature_exceptions = FALSE;
					//if ( $end_date == TTDate::getMiddleDayEpoch( $ud_obj->getDateStamp() ) ) {
					if ( TTDate::getMiddleDayEpoch( $ud_obj->getDateStamp() ) >= (TTDate::getMiddleDayEpoch( $execution_time ) - (86400*2)) ) {
						$enable_premature_exceptions = TRUE;
					}
					Debug::text($x .'('.$i.'). User: '. $ud_obj->getUser() .' Date: '. TTDate::getDate('DATE+TIME', $ud_obj->getDateStamp() ) .' User Date ID: '. $ud_obj->getId() .' Enable PreMature Exceptions: '. (int)$enable_premature_exceptions, __FILE__, __LINE__, __METHOD__,5);

					UserDateTotalFactory::reCalculateDay( $ud_obj->getId(), TRUE, $enable_premature_exceptions );

					TTDate::setTimeZone();
					
					$i++;
					$x++;
				}
			}
			Debug::text('Company: '. $c_obj->getName() .'('.$c_obj->getId().') Finished In: '. (microtime(TRUE)-$company_start_time) .'s', __FILE__, __LINE__, __METHOD__,5);
		}
	}
}

Debug::writeToLog();
Debug::Display();
?>