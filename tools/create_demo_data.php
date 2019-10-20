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

//Allow CLI scripts to run much longer.
ini_set( 'max_execution_time', 86400 );
ini_set( 'memory_limit', '1024M' );

if ( $argc < 2 OR in_array ($argv[1], array('--help', '-help', '-h', '-?') ) ) {
	$help_output = "Usage: create_demo_data.php [OPTIONS]\n";
	$help_output .= "  Options:\n";
	$help_output .= "    -f (Force creating data even if DEMO_MODE is not enabled. *NOT RECOMMENDED*)\n";
	$help_output .= "    -s [Numeric USER NAME suffix, ie: '100' to create user names like: 'demoadmin100']\n";
	$help_output .= "    -n [Number of random users to create above 25]\n";
	$help_output .= "    -date [Static date to base all other dates on]\n";
	$help_output .= "    -skip [Skip objects, ie: schedule,punch,invoice,expense,document,hr]\n";

	echo $help_output;
} else {
	if ( in_array('-s', $argv) ) {
		$data['suffix'] = trim($argv[(array_search('-s', $argv) + 1)]);
	} else {
		$data['suffix'] = '1';
	}

	if ( in_array('-n', $argv) ) {
		$data['random_users'] = (int)trim($argv[(array_search('-n', $argv) + 1)]);
	} else {
		$data['random_users'] = 0;
	}

	if ( in_array('-date', $argv) ) {
		$data['date'] = trim($argv[(array_search('-date', $argv) + 1)]);
	} else {
		$data['date'] = time();
	}

	if ( in_array('-f', $argv) ) {
		$data['force'] = TRUE;
	} else {
		$data['force'] = FALSE;
	}

	if ( in_array('-skip', $argv) ) {
		$skip = explode(',', trim($argv[( array_search('-skip', $argv) + 1)]) );
	} else {
		$skip = array();
	}

	if ( $data['force'] === TRUE ) {
		define('DEMO_MODE', TRUE); //When forcing, define this before global.inc.php gets loaded.
	}

	require_once( dirname(__FILE__) . DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'includes'. DIRECTORY_SEPARATOR .'global.inc.php');
	require_once( dirname(__FILE__) . DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'includes'. DIRECTORY_SEPARATOR .'CLI.inc.php');

	//Debug::setBufferOutput(FALSE);
	//Debug::setEnable(FALSE);
	//Debug::setVerbosity(0);

	/*
	Debug::setBufferOutput(TRUE);
	Debug::setEnable(TRUE);
	Debug::setEnableDisplay(TRUE);
	//Debug::setVerbosity(11);
	Debug::setVerbosity(10);
	*/


	$config_vars['other']['demo_mode'] = TRUE;
	$config_vars['other']['enable_plugins'] = FALSE; //Disable plugins as they shouldn't be needed and likely just cause problems.
	$config_vars['other']['disable_audit_log'] = TRUE; //Disable audit logging during initial creation of data to help speed things up.
	$config_vars['other']['disable_audit_log_detail'] = TRUE; //Disable audit logging during initial creation of data to help speed things up.

	if ( DEMO_MODE == TRUE OR $data['force'] === TRUE ) {
		SystemSettingListFactory::setSystemSetting( 'system_version', APPLICATION_VERSION );
		SystemSettingListFactory::setSystemSetting( 'tax_engine_version', '1.1.0' );
		SystemSettingListFactory::setSystemSetting( 'tax_data_version', date('Ymd') );

		Debug::Text('Generating DEMO Data...', __FILE__, __LINE__, __METHOD__, 10);
		echo "UserName suffix: ". $data['suffix'] ." Max Random Users: ". $data['random_users'] ."<br>\n";
		sleep(1);

		$dd = new DemoData();
		$dd->setDate( TTDate::getMiddleDayEpoch( TTDate::strtotime($data['date']) ) ); //Always use middle day epoch so its consistent across runs on the same day.
		$dd->setMaxRandomUsers( $data['random_users'] );
		$dd->setUserNamePostFix( $data['suffix'] );

		if ( is_array($skip) ) {
			foreach($skip as $skip_object) {
				if ( isset($dd->create_data[$skip_object]) ) {
					Debug::Text('  Skipping Object: '. $skip_object, __FILE__, __LINE__, __METHOD__, 10);
					echo "  Skipping Object: ". $skip_object ."\n";
					$dd->create_data[$skip_object] = FALSE;
				}
			}
		}
		flush();
		ob_flush();
		sleep(5);

		$dd->generateData();
		Debug::Text('Done Generating DEMO Data!', __FILE__, __LINE__, __METHOD__, 10);
	} else {
		echo "DEMO MODE IS NOT ENABLED!<br>\n";
		exit(1);
	}
}
Debug::WriteToLog();
//Debug::Display();
?>
