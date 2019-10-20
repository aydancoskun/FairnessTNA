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
 * Automatically performans an upgrade if a new version is available...
 */
require_once( dirname(__FILE__) . DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'includes'. DIRECTORY_SEPARATOR .'global.inc.php');
require_once( dirname(__FILE__) . DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'includes'. DIRECTORY_SEPARATOR .'CLI.inc.php');

if ( DEPLOYMENT_ON_DEMAND == FALSE
		AND ( !isset($config_vars['other']['disable_auto_upgrade']) OR isset($config_vars['other']['disable_auto_upgrade']) AND $config_vars['other']['disable_auto_upgrade'] != TRUE ) ) {
	Debug::Text('Auto Upgrade is enabled, checking for new version...', __FILE__, __LINE__, __METHOD__, 10);

	sleep( rand(0, 60) ); //Further randomize when calls are made.
	
	$php_cli = $config_vars['path']['php_cli'];
	if ( is_executable( $php_cli ) ) {
		ini_set( 'max_execution_time', 0 );
		
		$command = $php_cli .' '. Environment::getBasePath() . DIRECTORY_SEPARATOR . 'tools' . DIRECTORY_SEPARATOR .'unattended_upgrade.php';
		system( $command, $output );
		Debug::Arr($output, 'Auto Upgrade Command: '. $command .' Output: ', __FILE__, __LINE__, __METHOD__, 10);
	} else {
		Debug::Text('ERROR PHP CLI not executable!', __FILE__, __LINE__, __METHOD__, 10);
	}
} else {
	Debug::Text('Auto Upgrade is disabled!', __FILE__, __LINE__, __METHOD__, 10);
}
Debug::writeToLog();
Debug::Display();
?>