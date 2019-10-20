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
 * Handle remittance agency events, and send out email reminders.
 *
 */
require_once( dirname(__FILE__) . DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'includes'. DIRECTORY_SEPARATOR .'global.inc.php');
require_once( dirname(__FILE__) . DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'includes'. DIRECTORY_SEPARATOR .'CLI.inc.php');

$clf = new CompanyListFactory();
$clf->getByStatusID( array(10, 20, 23), NULL, array('a.id' => 'asc') );
if ( $clf->getRecordCount() > 0 ) {
	Debug::Text('PROCESSING Payroll Remittance Agency Event Reminders.', __FILE__, __LINE__, __METHOD__, 10);
	foreach ( $clf as $c_obj ) {
		if ( $c_obj->getStatus() != 30 ) {
			$praelf = new PayrollRemittanceAgencyEventListFactory();
			//Handle pay period/on hire/on termination event frequencies that have no dates (update them)
			$praelf->getByCompanyIdAndFrequencyIdAndDueDateIsNull( $c_obj->getId(), array(1000, 90100, 90200, 90310) );
			if ( $praelf->getRecordCount() > 0 ) {
				Debug::Text( '  Payroll Remittance Agency Event Reminders for ' . $c_obj->getName() . ': ' . $praelf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10 );
				foreach ( $praelf as $prae_obj ) {
					if ( ( $prae_obj->getStatus() == 10 OR $prae_obj->getStatus() == 15 ) ) {
						Debug::Text( 'Updating pay-period/on hire/on termination frequency dates for Event: ' . $prae_obj->getId(), __FILE__, __LINE__, __METHOD__, 10 );
						$prae_obj->setEnableRecalculateDates( TRUE );
						if ( $prae_obj->isValid() ) {
							$prae_obj->Save();
						}
					}
				}
				unset( $praelf, $prae_obj );
			}

			$praelf = new PayrollRemittanceAgencyEventListFactory();
			$praelf->getPendingReminder( $c_obj->getId() );
			if ( $praelf->getRecordCount() > 0 ) {
				Debug::Text( '  Found ' . $praelf->getRecordCount() . ' Payroll Remittance Agency Event Reminders for ' . $c_obj->getName(), __FILE__, __LINE__, __METHOD__, 10 );
				foreach ( $praelf as $prae_obj ) {
					if ( ( $prae_obj->getStatus() == 10 OR $prae_obj->getStatus() == 15 ) AND $prae_obj->getNextReminderDate() != '' ) {
						$prae_obj->emailReminder();
						//Need to track last reminder date so we don't spam every time this runs.
						$prae_obj->setLastReminderDate( time() );
						if ( $prae_obj->isValid() ) {
							$prae_obj->save();
						}
					} else {
						Debug::Text( '  Next reminder date is blank, or event is disabled...', __FILE__, __LINE__, __METHOD__, 10 );
					}
				}
				unset( $praelf, $prae_obj );
			} else {
				Debug::Text( 'No pending reminders found for ' . $c_obj->getName(), __FILE__, __LINE__, __METHOD__, 10 );
			}
		}
	}
}
Debug::writeToLog();
Debug::Display();
?>