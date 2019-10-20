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

//Allow only CLI PHP binaries to call maint scripts. To avoid a remote party from running them from hitting a URL.
if ( PHP_SAPI != 'cli' ) {
	echo "This script can only be called from the Command Line. (". PHP_SAPI .")\n";
	exit(1);
}

//There appears to be cases where ARGC/ARGV may not be set, so check those too. Fixes: PHP ERROR - NOTICE(8): Undefined variable: argc File: C:\FairnessTNA\fairnesstna\tools\unattended_install.php Line: 31
if ( !isset($argc) OR !isset($argv) ) {
	echo "This script can only be called from the Command Line. (args)\n";
	exit(1);
}

if ( version_compare( PHP_VERSION, 5, '<') == 1 ) {
	echo "You are currently using PHP v". PHP_VERSION ." FairnessTNA requires PHP v5 or greater!\n";
	exit(1);
}

//Allow CLI scripts to run much longer. ie: Purging database could takes hours.
ini_set( 'max_execution_time', 86000 ); //Just less than 24hrs, so scripts that run daily can't build up.

$install_obj = new Install();

//Make sure CLI tools are not being run as root, otherwise show error message and attempt to down-grade users.
if ( Misc::isCurrentOSUserRoot() == TRUE ) {
	fwrite(STDERR, 'ERROR: Running as \'root\' forbidden! To avoid permission conflicts, must run as the web-server user instead.'."\n" );
	fwrite(STDERR, '       Example: su www-data -c "'. ( ( isset($config_vars['path']['php_cli']) ) ? $config_vars['path']['php_cli'] : 'php' ) .' '. implode(' ', ( ( isset($argv) ) ? $argv : array() ) ) .'"'."\n" );
	Debug::Text('WARNING: Running as OS user \'root\' forbidden!', __FILE__, __LINE__, __METHOD__, 10);

	//Before we down-grade user privileges, check to make sure we can read/write all necessary files.
	$install_obj->checkFilePermissions();
	if ( Misc::setProcessUID( Misc::findWebServerOSUser() ) != TRUE ) {
		Debug::Display();
		Debug::writeToLog();
		exit(1);
	}
}

//Check post install requirements, because PHP CLI usually uses a different php.ini file.
if ( $install_obj->checkAllRequirements( TRUE ) == 1 ) {
	$failed_requirements = $install_obj->getFailedRequirements( TRUE );
	unset($failed_requirements[0]);
	echo "----WARNING----WARNING----WARNING-----\n";
	echo "--------------------------------------\n";
	echo "Minimum PHP Requirements are NOT met!!\n";
	echo "--------------------------------------\n";
	echo "Failed Requirements: ".implode(',', (array)$failed_requirements )." \n";
	echo "--------------------------------------\n";
	echo "PHP INI: ". $install_obj->getPHPConfigFile() ." \n";
	echo "Process Owner: ". $install_obj->getWebServerUser() ." \n";
	echo "--------------------------------------\n\n\n";
}
unset($install_obj);

TTi18n::chooseBestLocale(); //Make sure a locale is set, specifically when generating PDFs.

//Uncomment the below block to force debug logging with maintenance jobs.
/*
Debug::setEnable( TRUE );
Debug::setBufferOutput( TRUE );
Debug::setEnableLog( TRUE );
if ( Debug::getVerbosity() <= 1 ) {
	Debug::setVerbosity( 1 );
}
*/
?>