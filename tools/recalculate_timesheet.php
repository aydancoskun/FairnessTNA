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

require_once( dirname(__FILE__) . DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'includes'. DIRECTORY_SEPARATOR .'global.inc.php');
require_once( dirname(__FILE__) . DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'includes'. DIRECTORY_SEPARATOR .'CLI.inc.php');

if ( $argc < 1 OR ( isset($argv[1]) AND in_array($argv[1], array('--help', '-help', '-h', '-?') ) ) ) {
	$help_output = "Usage: recalculate_timesheet.php [options] [company_id]\n";
	$help_output .= "    -start_date			Start Date to recalculate\n";
	$help_output .= "    -end_date				End Date to recalculate\n";
	$help_output .= "    -user_id				User to recalculate\n";
	$help_output .= "    -n				Dry-run\n";
	echo $help_output;
} else {
	//Handle command line arguments
	$last_arg = count($argv)-1;

	if ( in_array('-start_date', $argv) ) {
		$start_date = trim($argv[array_search('-start_date', $argv)+1]);
	} else {
		$start_date = time();
	}
	if ( in_array('-end_date', $argv) ) {
		$end_date = trim($argv[array_search('-end_date', $argv)+1]);
	} else {
		$end_date = $start_date;
	}

	if ( in_array('-user_id', $argv) ) {
		$filter_user_id = trim($argv[array_search('-user_id', $argv)+1]);
	} else {
		$filter_user_id = NULL;
	}

	if ( in_array('-n', $argv) ) {
		$dry_run = TRUE;
		echo "Using DryRun!\n";
	} else {
		$dry_run = FALSE;
	}

	if ( isset($argv[$last_arg]) AND $argv[$last_arg] != '' AND TTUUID::isUUID( $argv[$last_arg] ) ) {
		$company_id = $argv[$last_arg];
	}


	$date_stamps = TTDate::getDateArray( strtotime( $start_date ), strtotime( $end_date ) );

	//Force flush after each output line.
	ob_implicit_flush( TRUE );
	ob_end_flush();

	//TTDate::setTimeZone( 'UTC' ); //Always force the timezone to be set.

	$clf = new CompanyListFactory();
	$clf->getAll();
	if ( $clf->getRecordCount() > 0 ) {
		foreach ( $clf as $c_obj ) {
			if ( isset($company_id) AND $company_id != '' AND $company_id != $c_obj->getId() ) {
				continue;
			}

			//if ( !in_array( $c_obj->getID(), array(1310,1347) ) ) {
			//	continue;
			//}

			if ( $c_obj->getStatus() != 30 ) {
				Debug::text('Company: '. $c_obj->getName() .' ID: '. $c_obj->getID(), __FILE__, __LINE__, __METHOD__, 10);
				echo 'Company: '. $c_obj->getName() .' ID: '. $c_obj->getID() ."\n";
				//Check to see if there any absence entries that are linked to an accrual, but the accrual record does not exist.

				echo ' Recalculating TimeSheets from '. date('r', strtotime( $start_date ) ) .' to '. date('r', strtotime( $end_date ) ) ."\n";

				$flags = array(
					'meal' => TRUE,
					'undertime_absence' => TRUE, //Needs to calculate undertime absences, otherwise it won't update accruals.
					'break' => TRUE,
					'holiday' => TRUE,
					'schedule_absence' => TRUE, //Required to calculate absences created from the schedule that may be causing duplicate entries in the accrual.
					'absence' => TRUE,
					'regular' => TRUE,
					'overtime' => TRUE,
					'premium' => TRUE,
					'accrual' => TRUE,

					'exception' => TRUE,
					//Exception options
					'exception_premature' => FALSE, //Calculates premature exceptions
					'exception_future' => FALSE, //Calculates exceptions in the future.

					//Calculate policies for future dates.
					'future_dates' => FALSE, //Calculates dates in the future.
				);

				$ulf = new UserListFactory();
				$ulf->getByCompanyId( $c_obj->getID() );
				if ( $ulf->getRecordCount() > 0 ) {
					foreach( $ulf as $user_obj ) {
						if ( $filter_user_id == '' OR $filter_user_id == $user_obj->getId() ) {
							$c_obj->StartTransaction(); //Try to keep transactions are short lived as possible.

//							if ( !in_array( $user_obj->getID(), array('bc150cf3-7ace-feb6-0ab0-000000000744') ) ) {
//								continue;
//							}

							if ( $user_obj->getStatus() == 20 ) {
								continue;
							}

							$user_obj_prefs = $user_obj->getUserPreferenceObject();
							if ( is_object( $user_obj_prefs ) ) {
								$user_obj_prefs->setTimeZonePreferences();
							} else {
								//Use system timezone.
								TTDate::setTimeZone();
							}
							echo '   Employee: ' . $user_obj->getUserName() . ' - ' . date( 'r' ) . "\n";
							Debug::text( 'User Name: ' . $user_obj->getUserName() . ' ID: ' . $user_obj->getID(), __FILE__, __LINE__, __METHOD__, 10 );

							//Calculate pre-mature exceptions, so pre-mature Missing Out Punch exceptions arn't made active until they are ready.
							//Don't calculate future exceptions though.
							$cp = TTNew( 'CalculatePolicy' );
							$cp->setFlag( $flags );
							$cp->setUserObject( $user_obj );
							$cp->addPendingCalculationDate( $date_stamps );
							$cp->calculate(); //This sets timezone itself.
							$cp->Save();

							if ( $dry_run == TRUE ) {
								$c_obj->FailTransaction();
							}
							$c_obj->CommitTransaction();
						} else {
							Debug::text( 'Skipping User Name: ' . $user_obj->getUserName() . ' ID: ' . $user_obj->getID(), __FILE__, __LINE__, __METHOD__, 10 );
						}
					}
				}


			}
		}
	}
}
echo "Done...\n";
Debug::WriteToLog();
Debug::Display();
?>
