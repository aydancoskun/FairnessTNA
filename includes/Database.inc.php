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

require_once( Environment::getBasePath() .'classes'. DIRECTORY_SEPARATOR .'adodb'. DIRECTORY_SEPARATOR .'adodb.inc.php');
require_once( Environment::getBasePath() .'classes'. DIRECTORY_SEPARATOR .'adodb'. DIRECTORY_SEPARATOR .'adodb-exceptions.inc.php');

$PRIMARY_KEY_IS_UUID = TRUE;

//Use overloading to abstract $db and have calls directly to ADODB
if ( !isset($disable_database_connection) ) {
	if ( isset($config_vars['database']['type']) AND isset($config_vars['database']['host']) AND isset($config_vars['database']['user']) AND isset($config_vars['database']['password']) AND isset($config_vars['database']['database_name']) ) {
		try {
			if ( isset($config_vars['database']['type']) AND trim( strtolower($config_vars['database']['type']) ) == 'mysqlt' ) { //Added in v9.0.6, 24-Dec-2015
				if ( extension_loaded('mysqli') !== FALSE ) {
					//If MySQLi extension is already loaded, just switch it in the config file.
					$install_obj = TTnew('Install'); /** @var Install $install_obj */
					$install_obj->writeConfigFile( array('database' => array( 'type' => 'mysqli' ) ) );
					$config_vars['database']['type'] = 'mysqli';
					unset($install_obj);
				} else {
					echo "ERROR: Currently selected database driver '". $config_vars['database']['type'] ."' in '". CONFIG_FILE ."' is out of date, switch to 'mysqli' instead.\n";
					exit(1);
				}
			}

			if ( isset($config_vars['cache']['dir']) AND $config_vars['cache']['dir'] != '' ) {
				$ADODB_CACHE_DIR = $config_vars['cache']['dir'] . DIRECTORY_SEPARATOR;
			}

			if ( Debug::getVerbosity() == 11 ) {
				$ADODB_OUTP = 'ADODBDebug';
				function ADODBDebug( $msg, $newline = TRUE ) {
					Debug::Text( html_entity_decode( strip_tags( $msg ) ), __FILE__, __LINE__, __METHOD__, 11);
					return TRUE;
				}
			}

			$ADODB_GETONE_EOF = FALSE; //Make sure GetOne returns FALSE rather then NULL.
			if ( strpos( $config_vars['database']['host'], ',' ) !== FALSE ) {
				require_once( Environment::getBasePath() .'classes'. DIRECTORY_SEPARATOR .'adodb'. DIRECTORY_SEPARATOR .'adodb-loadbalancer.inc.php');
				if ( !isset($config_vars['database']['persistent_connections']) ) {
					$config_vars['database']['persistent_connections'] = FALSE;
				}

				$db = new ADOdbLoadBalancer();

				//Use comma separated database hosts, assuming the first is always the master, the rest are slaves.
				//Anything after the # is the weight. Username/password/database is assumed to be the same across all connections.
				//ie: 127.0.0.1:5433#10,127.0.0.2:5433#100,127.0.0.3:5433#120
				$db_hosts = Misc::parseDatabaseHostString( $config_vars['database']['host'] );
				foreach( $db_hosts as $db_host_arr ) {
					Debug::Text( 'Adding DB Connection: Host: '. $db_host_arr[0] .' Type: '. $db_host_arr[1] .' Weight: '. $db_host_arr[2], __FILE__, __LINE__, __METHOD__, 1);
					$db_connection_obj = new ADOdbLoadBalancerConnection( $config_vars['database']['type'], $db_host_arr[1], $db_host_arr[2], (bool)$config_vars['database']['persistent_connections'], $db_host_arr[0], $config_vars['database']['user'], $config_vars['database']['password'], $config_vars['database']['database_name'] );
					$db_connection_obj->getADODbObject()->SetFetchMode(ADODB_FETCH_ASSOC);
					$db_connection_obj->getADODbObject()->noBlobs = TRUE; //Optimization to tell ADODB to not bother checking for blobs in any result set.
					$db_connection_obj->getADODbObject()->fmtTimeStamp = "'Y-m-d H:i:s'";

					if ( Debug::getVerbosity() == 11 ) {
						//Use 1 instead of TRUE, so it only outputs some debugging and not things like backtraces for every cache read/write.
						//Set to 99 to get all debug output.
						$db_connection_obj->getADODbObject()->debug = 1;
					}

					if ( isset($config_vars['database']['disable_row_count']) AND $config_vars['database']['disable_row_count'] == TRUE ) {
						//Dont count rows for pagination, much faster. However two queries must be run to tell if we are at the last page or not.
						$db_connection_obj->getADODbObject()->pageExecuteCountRows = FALSE;
					}
					$db->addConnection( $db_connection_obj );
				}
				unset($db_hosts, $db_host_arr, $db_connection_obj);

				if ( strncmp($config_vars['database']['type'], 'mysql', 5) == 0 ) {
					//Put MySQL into ANSI mode
					//READ COMMITTED mode is what PGSQL defaults to.
					//This should hopefully fix odd issues like hierarchy trees becoming corrupt.
					$db->setSessionInitSQL( 'SET SESSION wait_timeout = 86400' ); //For long running processes, increase the wait_timeout to avoid losing connections and having to restart all that work.
					$db->setSessionInitSQL( 'SET SESSION sql_mode=\'ansi\'' );
					$db->setSessionInitSQL( 'SET TRANSACTION ISOLATION LEVEL READ COMMITTED' );
					//$db->setSessionInitSQL( 'SET TRANSACTION ISOLATION LEVEL REPEATABLE READ' );
				} else {
					$db->setSessionInitSQL( 'SET datestyle = \'ISO\'' ); //Needed for ADODB to properly parse dates, as we removed it from ADODB as an optimization so it can be delayed until the first query is executed.
					//$db->setSessionInitSQL( 'SET SESSION CHARACTERISTICS AS TRANSACTION ISOLATION LEVEL REPEATABLE READ' ); //This is required to properly handle simultaneous recalculations of timesheets/pay stubs. We moved this to trigger via setTransactionMode() only for certain operations instead though.
				}
			} else {
				//To enable PDO support. Type: pdo_pgsql or pdo_mysql
				//$dsn = $config_vars['database']['type'].'://'.$config_vars['database']['user'].':'.$config_vars['database']['password'].'@'.$config_vars['database']['host'].'/'.$config_vars['database']['database_name'].'?persist';
				//$db = ADONewConnection( $dsn );
				$db = ADONewConnection( $config_vars['database']['type'] );
				$db->SetFetchMode(ADODB_FETCH_ASSOC);
				if ( isset($config_vars['database']['persistent_connections']) AND $config_vars['database']['persistent_connections'] == TRUE ) {
					$db->PConnect( $config_vars['database']['host'], $config_vars['database']['user'], $config_vars['database']['password'], $config_vars['database']['database_name']);
				} else {
					$db->Connect( $config_vars['database']['host'], $config_vars['database']['user'], $config_vars['database']['password'], $config_vars['database']['database_name']);
				}
				$db->noBlobs = TRUE; //Optimization to tell ADODB to not bother checking for blobs in any result set.

				//Use long timezone format because PostgreSQL 8.1 doesn't support some short names, like SGT,IST
				//Using "e" for the timezone fixes the Asia/Calcutta & IST bug where the two were getting confused.
				//We set the timezone in PostgreSQL like we do with MySQL, so 'e' shouldn't be required anymore.
				//$db->fmtTimeStamp = "'Y-m-d H:i:s e'";
				$db->fmtTimeStamp = "'Y-m-d H:i:s'";

				if ( Debug::getVerbosity() == 11 ) {
					//Use 1 instead of TRUE, so it only outputs some debugging and not things like backtraces for every cache read/write.
					//Set to 99 to get all debug output.
					$db->debug = 1;
				}

				if ( strncmp($db->databaseType, 'mysql', 5) == 0 ) {
					//Put MySQL into ANSI mode
					//READ COMMITTED mode is what PGSQL defaults to.
					//This should hopefully fix odd issues like hierarchy trees becoming corrupt.
					$db->Execute( 'SET SESSION sql_mode=\'ansi\'' );
					$db->Execute('SET TRANSACTION ISOLATION LEVEL READ COMMITTED');
					//$db->Execute( 'SET TRANSACTION ISOLATION LEVEL REPEATABLE READ' );
				} else {
					$db->Execute( 'SET datestyle = \'ISO\'' ); //Needed for ADODB to properly parse dates, as we removed it from ADODB as an optimization so it can be delayed until the first query is executed.
					//$db->Execute( 'SET SESSION CHARACTERISTICS AS TRANSACTION ISOLATION LEVEL REPEATABLE READ' ); //This is required to properly handle simultaneous recalculations of timesheets/pay stubs. We moved this to trigger via setTransactionMode() only for certain operations instead though.
				}

				if ( isset($config_vars['database']['disable_row_count']) AND $config_vars['database']['disable_row_count'] == TRUE ) {
					//Dont count rows for pagination, much faster. However two queries must be run to tell if we are at the last page or not.
					$db->pageExecuteCountRows = FALSE;
				}
			}
		} catch (Exception $e) {
			Debug::Text( 'Error connecting to the database!', __FILE__, __LINE__, __METHOD__, 1);
			throw new DBError($e);
		}


		if ( ( isset($config_vars['other']['installer_enabled']) AND $config_vars['other']['installer_enabled'] == TRUE ) OR ( isset($_SERVER['SCRIPT_FILENAME']) AND stripos( $_SERVER['SCRIPT_FILENAME'], 'unattended_upgrade' ) !== FALSE ) ) { //Make sure we always check the schema versions when run from unattended_upgrade, just in case the upgrade failed and we need to determine if we are in UUID mode or not.
			//Make sure that during initial installation we confirm that the database/table actually exists, otherwise this can throw a fatal SQL error.
			$install_obj = new Install();
			$install_obj->setDatabaseConnection( $db ); //Default connection
			if ( $install_obj->checkSystemSettingTableExists() == TRUE ) {
				//Check to see if the DB schema is before the UUID upgrade (schema 1070 or older) and set the $PRIMARY_KEY_IS_UUID accordingly.
				//  THIS IS in tools/unattended_install.php, tools/unattended_upgrade.php, includes/database.inc.php  as well.
				if ( (int)SystemSettingFactory::getSystemSettingValueByKey( 'schema_version_group_A' ) < 1100 ) {
					Debug::Text( 'Setting PRIMARY_KEY_IS_UUID to FALSE due to pre-UUID schema version: '. SystemSettingFactory::getSystemSettingValueByKey( 'schema_version_group_A' ), __FILE__, __LINE__, __METHOD__, 1);
					$PRIMARY_KEY_IS_UUID = FALSE;
				}
			}
		}
	} else {
		Debug::Text( 'Database config options are not set... Unable to connect to database.', __FILE__, __LINE__, __METHOD__, 1);
		throw new DBError( new Exception );
	}
}

//Set timezone to system local timezone by default. This is so we sync up all timezones
//in the database (specifically MySQL) and PHP. This fixes timezone bugs
//mainly in maintenance scripts. We used to default this to just GMT, but that can cause additional problems in threaded environments.
//This must be run AFTER the database connection has been made to work properly.
if ( !isset($config_vars['other']['system_timezone']) OR ( isset($config_vars['other']['system_timezone']) AND $config_vars['other']['system_timezone'] == '' ) ) {
	$config_vars['other']['system_timezone'] = @date('e');
}
TTDate::setTimeZone( $config_vars['other']['system_timezone'], FALSE, FALSE ); //Don't force SQL to be executed here, as an optimization to avoid DB connections when calling things like getProgressBar()
?>
