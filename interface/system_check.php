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
$skip_db_error_exception = TRUE; //Skips DB error redirect
try {
	require_once('../includes/global.inc.php');
} catch(Exception $e) {
	echo 'FAIL (100) - '. $e->getMessage();
	exit;
}
//Debug::setVerbosity(11);

//First check if we are installing or down for maintenance, so we don't try to initiate any DB connections.
if ( ( isset($config_vars['other']['installer_enabled']) AND $config_vars['other']['installer_enabled'] == TRUE )
		OR ( isset($config_vars['other']['down_for_maintenance']) AND $config_vars['other']['down_for_maintenance'] == TRUE ) ) {
	echo 'FAIL! (INSTALLER/DOWN FOR MAINTENANCE)';
	exit;
}

//Confirm database connection is up and maintenance jobs have run recently...
if ( PRODUCTION == TRUE ) {
	$cjlf = TTnew( 'CronJobListFactory' ); /** @var CronJobListFactory $cjlf */
	$cjlf->getMostRecentlyRun();
	if ( $cjlf->getRecordCount() > 0 ) {
		$last_run_date_diff = time()-$cjlf->getCurrent()->getLastRunDate();
		if ( $last_run_date_diff > 1800 ) { //Must run in the last 30mins.
			echo 'FAIL! (200)';
			exit;
		}
	}
}

//If caching is enabled, make sure cache directory exists and is writeable.
if ( isset($config_vars['cache']['enable']) AND $config_vars['cache']['enable'] == TRUE ) {
	if ( isset($config_vars['cache']['redis_host']) AND $config_vars['cache']['redis_host'] != '' ) {
		$tmp_f = TTnew('SystemSettingFactory'); /** @var SystemSettingFactory $tmp_f */
		$random_value = sha1( time() );
		$tmp_f->saveCache( $random_value, 'system_check' );
		$result = $tmp_f->getCache( 'system_check' );
		if ( $random_value != $result ) {
			echo 'FAIL! (320)';
			exit;
		}
		$tmp_f->removeCache('system_check');
	} elseif ( file_exists($config_vars['cache']['dir']) == FALSE ) {
		echo 'FAIL! (300)';
		exit;
	} else {
		if ( is_writeable( $config_vars['cache']['dir'] ) == FALSE ) {
			echo 'FAIL (310)';
			exit;
		}
	}
}

//Everything is good.
echo 'OK';
?>