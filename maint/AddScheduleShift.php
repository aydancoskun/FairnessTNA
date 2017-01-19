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

require_once( dirname(__FILE__) . DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'includes'. DIRECTORY_SEPARATOR .'global.inc.php');
require_once( dirname(__FILE__) . DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'includes'. DIRECTORY_SEPARATOR .'CLI.inc.php');

$minimum_add_shift_offset = (3600 * 8.5) ; //Add shifts at least 8.5hrs before they start.
$lookup_shift_offset = (3600 * 4.5); //Lookup shifts that started X hrs before now. Since this should run every 4hrs, check for shifts that may have been missed on the last run.

$current_epoch = TTDate::getTime();
//$current_epoch = strtotime('05-Sep-2013 10:00 PM');
Debug::text('Current Epoch: '. TTDate::getDate('DATE+TIME', $current_epoch ), __FILE__, __LINE__, __METHOD__, 10);

//Initial Start/End dates need to cover all timezones, we narrow it done further once we change to each users timezone later on.
$initial_start_date = ( $current_epoch - $lookup_shift_offset );
$initial_end_date = ($current_epoch + $minimum_add_shift_offset );
Debug::text('Initial Start Date: '. TTDate::getDate('DATE+TIME', $initial_start_date ) .' End Date: '. TTDate::getDate('DATE+TIME', $initial_end_date ), __FILE__, __LINE__, __METHOD__, 10);

$clf = new CompanyListFactory();
$clf->getByStatusID( array(10,20,23), NULL, array('a.id' => 'asc') );
if ( $clf->getRecordCount() > 0 ) {
	foreach ( $clf as $c_obj ) {
		if ( $c_obj->getStatus() != 30 ) {
			Debug::text('Company: '. $c_obj->getName() .' ID: '. $c_obj->getID(), __FILE__, __LINE__, __METHOD__, 10);

			//
			//Get recurring schedules that are ready to be committed.
			//
			$rsf = TTNew('RecurringScheduleFactory');
			$rsf->addScheduleFromRecurringSchedule( $c_obj, $initial_start_date, $initial_end_date );
		} else {
			Debug::text('Company is not ACTIVE: '. $c_obj->getId(), __FILE__, __LINE__, __METHOD__, 10);
		}
	}
}
//$profiler->printTimers(TRUE);
Debug::writeToLog();
Debug::Display();
?>