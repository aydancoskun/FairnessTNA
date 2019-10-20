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
 * Cron replica
 * Run this script every minute from the real cron.
 *
 */
require_once( dirname(__FILE__) . DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'includes'. DIRECTORY_SEPARATOR .'global.inc.php');
require_once( dirname(__FILE__) . DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'includes'. DIRECTORY_SEPARATOR .'CLI.inc.php');

if ( isset($config_vars['other']['installer_enabled']) AND $config_vars['other']['installer_enabled'] == TRUE ) {
	Debug::text( 'CRON: Installer is enabled, skipping cron jobs for now...', __FILE__, __LINE__, __METHOD__, 0 );
} elseif ( isset($config_vars['other']['down_for_maintenance']) AND $config_vars['other']['down_for_maintenance'] == TRUE ) {
	Debug::text( 'CRON: System is down for maintenance, skipping cron jobs for now...', __FILE__, __LINE__, __METHOD__, 0 );
} else {
	if ( isset($config_vars['path']['php_cli']) AND $config_vars['path']['php_cli'] != '' ) {
		//$current_epoch = strtotime('28-Mar-08 1:30 PM');
		$current_epoch = TTDate::getTime();

		$executed_jobs = 0;

		$cjlf = new CronJobListFactory();
		$job_arr = $cjlf->getArrayByListFactory( $cjlf->getAll() );
		$total_jobs = count( $job_arr );
		if ( is_array( $job_arr ) ) {
			foreach ( $job_arr as $job_id => $job_name ) {
				//Get each cronjob row again individually incase the status has changed.
				$cjlf = new CronJobListFactory();
				$cjlf->getById( $job_id ); //Let Execute determine if job is running or not so it can find orphans.
				if ( $cjlf->getRecordCount() > 0 ) {
					foreach ( $cjlf as $cjf_obj ) {
						//Debug::text('Checking if Job ID: '. $job_id .' is scheduled to run...', __FILE__, __LINE__, __METHOD__, 0);
						if ( $cjf_obj->isScheduledToRun( $current_epoch ) == TRUE ) {
							$executed_jobs++;
							$cjf_obj->Execute( $config_vars['path']['php_cli'], dirname( __FILE__ ) );
						}
					}
				}
			}
		}
		echo "NOTE: Jobs are scheduled to run at specific times each day, therefore it is normal for only some jobs to be executed each time this file is run.\n";
		echo "Jobs Executed: $executed_jobs of $total_jobs\n";
		Debug::text( 'CRON: Jobs Executed: ' . $executed_jobs . ' of ' . $total_jobs, __FILE__, __LINE__, __METHOD__, 0 );
	} else {
		echo "ERROR: settings.ini.php does not define 'php_cli' option in the [path] section. Unable to run maintenance jobs!\n";
		Debug::text( 'PHP_CLI not defined in settings.ini.php file.', __FILE__, __LINE__, __METHOD__, 0 );
	}

	//Save file to log directory with the last executed date, so we know if the CRON daemon is actually calling us.
	$file_name = $config_vars['path']['log'] . DIRECTORY_SEPARATOR . 'fairnesstna_cron_last_executed.log';
	@file_put_contents( $file_name, TTDate::getDate( 'DATE+TIME', time() ) . "\n" );
}
Debug::writeToLog();
Debug::Display();
?>