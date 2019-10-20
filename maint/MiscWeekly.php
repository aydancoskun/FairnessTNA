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
 * Checks for any version updates...
 *
 */
require_once( dirname(__FILE__) . DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'includes'. DIRECTORY_SEPARATOR .'global.inc.php');
require_once( dirname(__FILE__) . DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'includes'. DIRECTORY_SEPARATOR .'CLI.inc.php');

//
//Check system requirements.
//
if ( PRODUCTION == TRUE AND DEPLOYMENT_ON_DEMAND == FALSE ) {
	Debug::Text('Checking system requirements... '. TTDate::getDate('DATE+TIME', time() ), __FILE__, __LINE__, __METHOD__, 10);
	$install_obj = new Install();
	$failed_requirment_requirements = $install_obj->getFailedRequirements( FALSE, array('base_url', 'clean_cache', 'file_checksums') );

	if ( is_array( $failed_requirment_requirements ) AND count($failed_requirment_requirements) > 1 ) {
		SystemSettingFactory::setSystemSetting( 'valid_install_requirements', 0 );
		Debug::Text('Failed system requirements: '. implode($failed_requirment_requirements), __FILE__, __LINE__, __METHOD__, 10);
		TTLog::addEntry( 0, 510, 'Failed system requirements: '. implode($failed_requirment_requirements), 0, 'company' );
	} else {
		SystemSettingFactory::setSystemSetting( 'valid_install_requirements', 1 );
	}

	unset($install_obj, $check_all_requirements);
	Debug::Text('Checking system requirements complete... '. TTDate::getDate('DATE+TIME', time() ), __FILE__, __LINE__, __METHOD__, 10);
}

//
// Purge database tables
//
if ( !isset($config_vars['other']['disable_database_purging'])
		OR isset($config_vars['other']['disable_database_purging']) AND $config_vars['other']['disable_database_purging'] != TRUE ) {
	PurgeDatabase::Execute();
}

//
// Clean cache directories
// - Make sure cache directory is set, and log/storage directories are not contained within it.
//
if ( !isset($config_vars['other']['disable_cache_purging'])
		OR isset($config_vars['other']['disable_cache_purging']) AND $config_vars['other']['disable_cache_purging'] != TRUE ) {

	if ( isset($config_vars['cache']['dir'])
			AND $config_vars['cache']['dir'] != ''
			AND strpos( $config_vars['path']['log'], $config_vars['cache']['dir'] ) === FALSE
			AND strpos( $config_vars['path']['storage'], $config_vars['cache']['dir'] ) === FALSE ) {

		Debug::Text('Purging Cache directory: '. $config_vars['cache']['dir'] .' - '. TTDate::getDate('DATE+TIME', time() ), __FILE__, __LINE__, __METHOD__, 10);
		$install_obj = new Install();
		$install_obj->cleanCacheDirectory( '' ); //Don't exclude .ZIP files, so if there is a corrupt one it will be redownloaded within a week.
		Debug::Text('Purging Cache directory complete: '. TTDate::getDate('DATE+TIME', time() ), __FILE__, __LINE__, __METHOD__, 10);
	} else {
		Debug::Text('Cache directory is invalid: '. TTDate::getDate('DATE+TIME', time() ), __FILE__, __LINE__, __METHOD__, 10);
	}
}

//
//Check for severely out of date versions and take out of production mode if necessary.
//
if ( PRODUCTION == TRUE AND DEPLOYMENT_ON_DEMAND == FALSE AND ( (time() - (int)APPLICATION_VERSION_DATE) > (86400 * 455) ) ) {
	Debug::Text('ERROR: Application version is severely out of date, changing production mode... ', __FILE__, __LINE__, __METHOD__, 10);
	$install_obj = new Install();
	$tmp_config_vars['debug']['production'] = 'FALSE';
	$write_config_result = $install_obj->writeConfigFile( $tmp_config_vars );
	unset($install_obj, $tmp_config_vars, $write_config_result);
}

Debug::writeToLog();
Debug::Display();
?>