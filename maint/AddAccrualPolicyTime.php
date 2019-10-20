<?php
/*********************************************************************************
 * FairnessTNA is a Workforce Management program forked from TimeTrex in 2013,
 * copyright Aydan Coskun. Original code base is copyright TimeTrex Software Inc.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * You can contact Aydan Coskun via issue tracker on github.com/aydancoskun
 ********************************************************************************/

/*
 * Adds time to employee accruals based on calendar milestones
 * This file should run once a day.
 *
 */
require_once( dirname(__FILE__) . DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'includes'. DIRECTORY_SEPARATOR .'global.inc.php');
require_once( dirname(__FILE__) . DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'includes'. DIRECTORY_SEPARATOR .'CLI.inc.php');

//Debug::setVerbosity(11);

$current_epoch = TTDate::getTime();
//$current_epoch = strtotime('01-Aug-17 1:00 AM');

$offset = ( 86400 - ( 3600 * 2 ) ); //22hrs of variance. Must be less than 24hrs which is how often this script runs.

$clf = new CompanyListFactory();
$clf->getByStatusID( array(10,20,23), NULL, array('a.id' => 'asc') );
if ( $clf->getRecordCount() > 0 ) {
	foreach ( $clf as $c_obj ) {
		if ( in_array( $c_obj->getStatus(), array(10, 20, 23) ) ) { //10=Active, 20=Hold, 23=Expired
			$aplf = new AccrualPolicyListFactory();
			$aplf->getByCompanyIdAndTypeId( $c_obj->getId(), array(20, 30) ); //Include hour based accruals so rollover adjustments can be calculated.
			if ( $aplf->getRecordCount() > 0 ) {
				foreach( $aplf as $ap_obj ) {
					//Accrue for the previous day rather than the current day. So if an employee is hired on August 1st and entered on August 1st,
					// the next morning they will see accruals if it happens to be a frequency date.
					//This will make it seem like accruals are delayed by one day though in all other cases, but see #2334
					$ap_obj->addAccrualPolicyTime( ( $current_epoch - 86400 ) , $offset );
				}
			}
		}
	}
}
Debug::writeToLog();
Debug::Display();
?>
