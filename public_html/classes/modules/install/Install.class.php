<?php
/*********************************************************************************
 * This file is part of "Fairness", a Payroll and Time Management program.
 * Fairness is Copyright 2013 Aydan Coskun (aydan.ayfer.coskun@gmail.com)
 * Portions of this software are Copyright of T i m e T r e x Software Inc.
 * Fairness is a fork of "T i m e T r e x Workforce Management" Software.
 *
 * Fairness is free software; you can redistribute it and/or modify it under the
 * terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation, either version 3 of the License, or (at you option )
 * any later version.
 *
 * Fairness is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE.  See the GNU Affero General Public License for more
 * details.
 *
 * You should have received a copy of the GNU Affero General Public License along
 * with this program; if not, see http://www.gnu.org/licenses or write to the Free
 * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301 USA.
  ********************************************************************************/


/**
 * @package Modules\Install
 */
class Install {

	protected $temp_db = NULL;
	var $config_vars = NULL;
	protected $database_driver = NULL;
	protected $is_upgrade = FALSE;
	protected $extended_error_messages = NULL;
	protected $versions = array(
								'system_version' => APPLICATION_VERSION,
								);
	protected $progress_bar_obj = NULL;
	protected $AMF_message_id = NULL;


	function __construct() {
		global $config_vars;
		// @codingStandardsIgnoreStart
		global $cache;
		//assumed needed
		// @codingStandardsIgnoreEnd
		require_once( Environment::getBasePath() .'classes'. DIRECTORY_SEPARATOR .'modules'. DIRECTORY_SEPARATOR .'install'. DIRECTORY_SEPARATOR .'InstallSchema.class.php');

		$this->config_vars = $config_vars;

		//Disable caching so we don't exceed maximum memory settings.
		//$cache->_onlyMemoryCaching = TRUE; //This shouldn't be required anymore, as it also breaks invalidating cache files.

		ini_set('default_socket_timeout', 5);
		ini_set('allow_url_fopen', 1);

		//As of PHP v5.3 some SAPI's don't support dl(), however it appears that php.ini can still have it enabled.
		//Double check to make sure the dl() function exists prior to calling it.
		if ( version_compare(PHP_VERSION, '5.3.0', '<') AND function_exists('dl') == TRUE AND (bool)ini_get( 'enable_dl' ) == TRUE AND (bool)ini_get( 'safe_mode' ) == FALSE ) {
			$prefix = (PHP_SHLIB_SUFFIX === 'dll') ? 'php_' : '';

			if ( extension_loaded('mysql') == FALSE ) {
				@dl($prefix . 'mysql.' . PHP_SHLIB_SUFFIX);
			}

			if ( extension_loaded('mysqli') == FALSE ) {
				@dl($prefix . 'mysqli.' . PHP_SHLIB_SUFFIX);
			}

			if ( extension_loaded('pgsql') == FALSE ) {
				@dl($prefix . 'pgsql.' . PHP_SHLIB_SUFFIX);
			}
		}

		return TRUE;
	}

	function getDatabaseDriver() {
		return $this->database_driver;
	}

	function setDatabaseDriver( $driver ) {
		if ( $this->getDatabaseType( $driver ) !== 1 ) {
			$this->database_driver = $this->getDatabaseType( $driver );

			return TRUE;
		}

		return FALSE;
	}

	function getProgressBarObject() {
		if	( !is_object( $this->progress_bar_obj ) ) {
			$this->progress_bar_obj = new ProgressBar();
		}

		return $this->progress_bar_obj;
	}
	//Returns the AMF messageID for each individual call.
	function getAMFMessageID() {
		if ( $this->AMF_message_id != NULL ) {
			return $this->AMF_message_id;
		}
		return FALSE;
	}
	function setAMFMessageID( $id ) {
		Debug::Text('AMF Message ID: '. $id, __FILE__, __LINE__, __METHOD__, 10);
		if ( $id != '' ) {
			$this->AMF_message_id = $id;
			return TRUE;
		}

		return FALSE;
	}

	//Read .ini file.
	//Make sure setup_mode is enabled.
	function isInstallMode() {
		if ( isset($this->config_vars['other']['installer_enabled'])
				AND $this->config_vars['other']['installer_enabled'] == 1 ) {
			Debug::text('Install Mode is ON', __FILE__, __LINE__, __METHOD__, 9);
			return TRUE;
		}

		Debug::text('Install Mode is OFF', __FILE__, __LINE__, __METHOD__, 9);
		return FALSE;
	}

	function setExtendedErrorMessage( $key, $msg ) {
		if ( isset($this->extended_error_messages[$key]) AND in_array( $msg, $this->extended_error_messages[$key] ) ) {
			return TRUE;
		} else {
			$this->extended_error_messages[$key][] = $msg;
		}

		return TRUE;
	}

	function getExtendedErrorMessage( $key = NULL ) {
		if ( $key != '' ) {
			if ( isset($this->extended_error_messages[$key]) ) {
				return implode( ',', $this->extended_error_messages[$key] );
			}
		} else {
			return $this->extended_error_messages;
		}

		return FALSE;
	}

	function getLicenseText() {
		$license_file = Environment::getBasePath(). DIRECTORY_SEPARATOR .'LICENSE';

		if ( is_readable($license_file) ) {
			$retval = file_get_contents( $license_file );

			if ( strlen($retval) > 10 ) {
				return $retval;
			}
		}

		return FALSE;
	}

	function setIsUpgrade( $val ) {
		$this->is_upgrade = (bool)$val;
	}
	function getIsUpgrade() {
		return $this->is_upgrade;
	}

	function setDatabaseConnection( $db_obj ) {
		if ( is_object( $db_obj) ) {
			if ( $db_obj instanceof ADOdbLoadBalancer ) {
				$this->temp_db = $db_obj->getConnection( 'master');
			} elseif ( isset($db_obj->_connectionID) AND is_resource( $db_obj->_connectionID) OR is_object( $db_obj->_connectionID) ) {
				$this->temp_db = $db_obj;
			}

			//Because InstallSchema_*.class.php files utilize the $db variable through the Factory.class.php directly,
			//  any queries will always be load-balanced, even if $this->temp_db is a different connection, and this can cause deadlocks and should never happen.
			//Therefore, to prevent any chance of loadbalanced connections, make sure $db is always just a single master connection.
			global $db;
			$db = $this->temp_db;

			return TRUE;
		}

		return FALSE;
	}
	function getDatabaseConnection() {
		if ( isset($this->temp_db) AND ( is_resource($this->temp_db->_connectionID) OR is_object($this->temp_db->_connectionID)	 ) ) {
			return $this->temp_db;
		}

		return FALSE;
	}

	function setNewDatabaseConnection($type, $host, $user, $password, $database_name ) {
		if ( $this->getDatabaseConnection() !== FALSE ) {
			$this->getDatabaseConnection()->Close();
		}

		try {
			$db = ADONewConnection( $type );
			$db->SetFetchMode(ADODB_FETCH_ASSOC);
			$db->Connect( $host, $user, $password, $database_name);
			if (Debug::getVerbosity() == 11) {
				$db->debug = TRUE;
			}

			//MySQLi extension uses an object, not a resource.
			if ( is_resource($db->_connectionID) OR is_object($db->_connectionID) ) {
				$this->setDatabaseConnection( $db );

				return TRUE;
			}
		} catch (Exception $e) {

			unset($e);//code standards
			return FALSE;
		}

		return FALSE;
	}

	function HumanBoolean($bool) {
		if ( $bool === TRUE OR strtolower(trim($bool)) == 'true' ) {
			return 'TRUE';
		} else {
			return 'FALSE';
		}
	}

	function writeConfigFile( $new_config_vars ) {
		if ( is_writeable( CONFIG_FILE ) ) {

			require_once('Config.php');
			$config = new Config();
			$data = $config->parseConfig( CONFIG_FILE, 'inicommented' );
			if ( is_object($data) AND get_class( $data ) == 'PEAR_Error' ) {
				Debug::Arr($data, 'ERROR modifying Config File!', __FILE__, __LINE__, __METHOD__, 9);
			} else {
				global $config_vars;

				//Debug::Arr($data, 'Current Config File!', __FILE__, __LINE__, __METHOD__, 9);
				if ( isset($new_config_vars['path']['base_url']) ) {
					$tmp_base_url = $new_config_vars['path']['base_url'];
				} elseif ( isset($config_vars['path']['base_url']) ) {
					$tmp_base_url = $config_vars['path']['base_url'];
				}
				if ( isset($tmp_base_url) ) {
					$new_config_vars['path']['base_url'] = preg_replace('@^(?:http://)?([^/]+)@i', '', $tmp_base_url );
					unset($tmp_base_url);
				}

				//Allow passing any empty array that will just rewrite the existing .INI file fixing any problems.
				if ( is_array($new_config_vars) ) {
					foreach( $new_config_vars as $section => $key_value_map ) {

						if ( !is_array( $key_value_map ) AND $key_value_map == 'FN_DELETE' ) {
							$item = $data->searchPath( array( $section ) );
							if ( is_object( $item ) ) {
								$item->removeItem();
							}
						} else {

							$key_value_map = (array)$key_value_map;
							foreach( $key_value_map as $key => $value ) {
								$item = $data->searchPath( array( $section, $key ) );
								if ( is_object( $item ) ) {
									$item->setContent( $value );
								} else {
									$item = $data->searchPath( array( $section ) );
									if ( is_object( $item ) ) {
										$item->createDirective( $key, $value, NULL, 'top' );
									} else {
										$item = $data->createSection( $section );
										$item->createDirective( $key, $value, NULL, 'top' );
									}

								}
							}

						}
					}

					Debug::text('Modified Config File!', __FILE__, __LINE__, __METHOD__, 9);
					//Debug::Arr($data, 'New Config File!', __FILE__, __LINE__, __METHOD__, 9);
					$retval = $config->writeConfig( CONFIG_FILE, 'inicommented' );

					//Make sure the first line in the file contains "die".
					$contents = file_get_contents( CONFIG_FILE );

					//Make sure we add back in the PHP code for security reasons.
					//BitRock seems to want to remove this and re-arrange the INI file as well for some odd reason.
					if ( stripos( $contents, ';<?php' ) === FALSE ) {
						Debug::text('Adding back in security feature...', __FILE__, __LINE__, __METHOD__, 9);
						$contents = ";<?php die('Unauthorized Access...'); //SECURITY MECHANISM, DO NOT REMOVE//?>\n".$contents;
					}
					file_put_contents( CONFIG_FILE, $contents );

					return $retval;
				}
			}
		} else {
			Debug::text('Config File Not Writable!', __FILE__, __LINE__, __METHOD__, 9);
		}

		return FALSE;
	}

	function setVersions() {
		if ( is_array($this->versions) ) {
			foreach( $this->versions as $name => $value ) {
				$result = SystemSettingFactory::setSystemSetting( $name, $value );
				if ( $result === FALSE ) {
					return FALSE;
				}
			}

			//Set the date when the upgrade was performed, so we can tell when the version was installed.
			$result = SystemSettingFactory::setSystemSetting( 'system_version_install_date', time() );
			if ( $result === FALSE ) {
				return FALSE;
			}
		}

		return TRUE;
	}
	/*

		Database Schema functions

	*/
	function checkDatabaseExists( $database_name ) {
		Debug::text('Database Name: '. $database_name, __FILE__, __LINE__, __METHOD__, 9);
		$db_conn = $this->getDatabaseConnection();

		if ( $db_conn == FALSE ) {
			return FALSE;
		}

		$database_arr = $db_conn->MetaDatabases();

		if ( in_array($database_name, $database_arr ) ) {
			Debug::text('Exists - Database Name: '. $database_name, __FILE__, __LINE__, __METHOD__, 9);
			return TRUE;
		}

		Debug::text('Does not Exist - Database Name: '. $database_name, __FILE__, __LINE__, __METHOD__, 9);
		return FALSE;
	}

	function createDatabase( $database_name ) {
		Debug::text('Database Name: '. $database_name, __FILE__, __LINE__, __METHOD__, 9);

		require_once( Environment::getBasePath() .'classes'. DIRECTORY_SEPARATOR .'adodb'. DIRECTORY_SEPARATOR .'adodb.inc.php');

		if ( $database_name == '' ) {
			Debug::text('Database Name invalid ', __FILE__, __LINE__, __METHOD__, 9);
			return FALSE;
		}

		$db_conn = $this->getDatabaseConnection();
		if ( $db_conn == FALSE ) {
			Debug::text('No Database Connection.', __FILE__, __LINE__, __METHOD__, 9);
			return FALSE;
		}
		Debug::text('Attempting to Create Database...', __FILE__, __LINE__, __METHOD__, 9);

		$dict = NewDataDictionary( $db_conn );

		$sqlarray = $dict->CreateDatabase( $database_name );
		return $dict->ExecuteSQLArray($sqlarray);
	}

	function checkTableExists( $table_name ) {
		Debug::text('Table Name: '. $table_name, __FILE__, __LINE__, __METHOD__, 9);
		$db_conn = $this->getDatabaseConnection();

		if ( $db_conn == FALSE ) {
			return FALSE;
		}

		$table_arr = $db_conn->MetaTables();

		if ( in_array($table_name, $table_arr ) ) {
			Debug::text('Exists - Table Name: '. $table_name, __FILE__, __LINE__, __METHOD__, 9);
			return TRUE;
		}

		Debug::text('Does not Exist - Table Name: '. $table_name, __FILE__, __LINE__, __METHOD__, 9);
		return FALSE;
	}

	//Get all schema versions
	//A=Community, B=Professional, C=Corporate, D=Enterprise, T=Tax
	function getAllSchemaVersions( $group = array('A', 'B', 'C', 'D') ) {
		if ( !is_array($group) ) {
			$group = array( $group );
		}

		$is_obj = new InstallSchema( $this->getDatabaseDriver(), '', NULL, $this->getIsUpgrade() );

		$dir = $is_obj->getSQLFileDirectory();
		$schema_versions = array();
		if ( $handle = opendir($dir) ) {
			while ( FALSE !== ($file = readdir($handle))) {
				list($schema_base_name, $extension) = explode('.', $file);
				$schema_group = substr($schema_base_name, -1, 1 );
				Debug::text('Schema: '. $file .' Group: '. $schema_group, __FILE__, __LINE__, __METHOD__, 9);

				if ($file != '.' AND $file != '..'
						AND substr($file, 1, 0) != '.'
						AND in_array($schema_group, $group) ) {
					$schema_versions[] = basename($file, '.sql');
				}
			}
			closedir($handle);
		}

		sort($schema_versions);
		Debug::Arr($schema_versions, 'Schema Versions', __FILE__, __LINE__, __METHOD__, 9);

		return $schema_versions;
	}

	function handleSchemaGroupChange() {
		//Pre v7.0, if the database version is less than 7.0 we need to *copy* the schema version from group B to C so we don't try to upgrade the database with old schemas.
		if ( $this->getIsUpgrade() == TRUE ) {
			$sslf = TTnew( 'SystemSettingListFactory' );
			$sslf->getByName( 'system_version' );
			if ( $sslf->getRecordCount() > 0 ) {
				$ss_obj = $sslf->getCurrent();
				$system_version = $ss_obj->getValue();
				Debug::text('System Version: '. $system_version .' Application Version: '. APPLICATION_VERSION, __FILE__, __LINE__, __METHOD__, 9);

				//If the current version is greater than 7.0 and the system_version in the database is less than 7.0, we know we are upgrading from pre7.0 to post7.0.
				if ( version_compare( APPLICATION_VERSION, '7.0', '>=' ) AND version_compare( $system_version, '7.0', '<' ) ) {
					Debug::text('Upgrade schema groups...', __FILE__, __LINE__, __METHOD__, 9);

					$sslf->getByName( 'schema_version_group_B' );
					if ( $sslf->getRecordCount() > 0 ) {
						$ss_obj = $sslf->getCurrent();
						$schema_version_group_b = $ss_obj->getValue();
						Debug::text('Schema Version Group B: '. $schema_version_group_b, __FILE__, __LINE__, __METHOD__, 9);

						$tmp_name = 'schema_version_group_C';
						$tmp_sslf = TTnew( 'SystemSettingListFactory' );
						$tmp_sslf->getByName( $tmp_name );
						if ( $tmp_sslf->getRecordCount() == 1 ) {
							$tmp_obj = $tmp_sslf->getCurrent();
						} else {
							$tmp_obj = TTnew( 'SystemSettingListFactory' );
						}
						$tmp_obj->setName( $tmp_name );
						$tmp_obj->setValue( $schema_version_group_b );
						if ( $tmp_obj->isValid() ) {
							if ( $tmp_obj->Save() === FALSE ) {
								return FALSE;
							}
							return TRUE;
						} else {
							return FALSE;
						}
					}
				}
			}
		}

		return FALSE;
	}

	//Creates DB schema starting at and including start_version, and ending at, including end version.
	//Starting at NULL is first version, ending at NULL is last version.
	function createSchemaRange( $start_version = NULL, $end_version = NULL, $group = array('A', 'B', 'C', 'D') ) {
		global $cache, $progress_bar, $config_vars;

		//Some schema changes can take a very long time to complete, make sure PHP doesn't cancel out on us.
		ignore_user_abort(TRUE);
		ini_set( 'max_execution_time', 0 );
		ini_set( 'memory_limit', '-1' );

		//Clear all cache before we do any upgrading, this is especially important during development processes
		//if we are switching between databases or reloading databases.
		$this->cleanCacheDirectory();
		$cache->clean(); //Clear all cache.

		//Disable detailed audit logging during schema upgrades, as it breaks upgrading from pre-audit log versions to post-audit log versions.
		//ie: v2.2.22 to v3.3.2.
		$config_vars['other']['disable_audit_log_detail'] = TRUE;

		$this->handleSchemaGroupChange(); //Copy schema group B to C during v7.0 upgrade.

		$schema_versions = $this->getAllSchemaVersions( $group );

		Debug::Arr($schema_versions, 'Schema Versions: ', __FILE__, __LINE__, __METHOD__, 9);

		$total_schema_versions = count($schema_versions);
		if ( is_array($schema_versions) AND $total_schema_versions > 0 ) {
			//$this->getDatabaseConnection()->StartTrans();
			if ( $this->is_upgrade ) {
				$msg = TTi18n::getText('Upgrading database');
			} else {
				$msg = TTi18n::getText('Initializing database');
			}
			$this->getProgressBarObject()->start( $this->getAMFMessageID(), $total_schema_versions, NULL, $msg );
			$this->initializeSequences(); //Initialize sequences before we start the schema upgrade to hopefully avoid duplicate key errors.

			$x = 0;
			foreach( $schema_versions as $schema_version ) {
				if ( ( $start_version === NULL OR $schema_version >= $start_version )
						AND ( $end_version === NULL OR $schema_version <= $end_version )
					) {

					//Wrap each schema version in its own transaction (compared to all schema versions in one transaction), this reduces the length of time any one transaction
					//is open for and should allow vacuum to run more often on PostgreSQL speeding up subsequency schemas.
					//This may make it harder to test rollback schema upgrades during development though.
					$this->getDatabaseConnection()->StartTrans();

					$create_schema_result = $this->createSchema( $schema_version );

					if ( is_object($progress_bar) ) {
						$progress_bar->setValue( Misc::calculatePercent( $x, $total_schema_versions ) );
						$progress_bar->display();
					}

					$this->getProgressBarObject()->set( $this->getAMFMessageID(), $x );

					if ( $create_schema_result === FALSE ) {
						Debug::text('CreateSchema Failed! On Version: '. $schema_version, __FILE__, __LINE__, __METHOD__, 9);
						$this->getDatabaseConnection()->FailTrans();
						return FALSE;
					}
					$this->getDatabaseConnection()->CompleteTrans();
				}

				//Fast way to clear memory caching only between schema upgrades to make sure it doesn't get too big.
				$cache->_memoryCachingArray = array();
				$cache->_memoryCachingCounter = 0;

				$x++;
			}
			$this->getProgressBarObject()->stop( $this->getAMFMessageID() );

			$this->initializeSequences(); //Initialize sequences after we finish as well just in case new errors were created during upgrade...

			//$this->getDatabaseConnection()->FailTrans();
			//$this->getDatabaseConnection()->CompleteTrans();
		}

		//Update Tax Engine/Data Versions
		Debug::text('Updating Tax Engine/Data versions...', __FILE__, __LINE__, __METHOD__, 9);
		require_once( Environment::getBasePath(). DIRECTORY_SEPARATOR . 'classes'. DIRECTORY_SEPARATOR .'payroll_deduction'. DIRECTORY_SEPARATOR .'PayrollDeduction.class.php');
		$pd_obj = new PayrollDeduction( 'CA', 'AB' );
		SystemSettingFactory::setSystemSetting( 'tax_data_version', $pd_obj->getDataVersion() );
		SystemSettingFactory::setSystemSetting( 'tax_engine_version', $pd_obj->getVersion() );

		//Clear all cache after the upgrade as well, as much of it is unlikely to be used again.
		$this->cleanCacheDirectory();
		$cache->clean();

		//Delete orphan files after schema upgrade is fully completed.
		$this->cleanOrphanFiles();

		return TRUE;
	}

	function createSchema( $version ) {
		if ( $version == '' ) {
			return FALSE;
		}

		$install = FALSE;

		$group = substr( $version, -1, 1);
		$version_number = substr( $version, 0, (strlen($version) - 1));

		Debug::text('Version: '. $version .' Version Number: '. $version_number .' Group: '. $group, __FILE__, __LINE__, __METHOD__, 9);

		//Only create schema if current system settings do not exist, or they are
		//older then this current schema version.
		if ( $this->checkTableExists( 'system_setting') == TRUE ) {
			Debug::text('System Setting Table DOES exist...', __FILE__, __LINE__, __METHOD__, 9);

			$sslf = TTnew( 'SystemSettingListFactory' );
			$sslf->getByName( 'schema_version_group_'. substr( $version, -1, 1) );
			if ( $sslf->getRecordCount() > 0 ) {
				$ss_obj = $sslf->getCurrent();
				Debug::text('Found System Setting Entry: '. $ss_obj->getValue(), __FILE__, __LINE__, __METHOD__, 9);

				if ( $ss_obj->getValue() < $version_number ) {
					Debug::text('Schema version is older, installing...', __FILE__, __LINE__, __METHOD__, 9);
					$install = TRUE;
				} else {
					Debug::text('Schema version is equal, or newer then what we are trying to install...', __FILE__, __LINE__, __METHOD__, 9);
					$install = FALSE;
				}
			} else {
				Debug::text('Did not find System Setting Entry...', __FILE__, __LINE__, __METHOD__, 9);
				$install = TRUE;
			}
		} else {
			Debug::text('System Setting Table does not exist...', __FILE__, __LINE__, __METHOD__, 9);
			$install = TRUE;
		}

		if ( $install == TRUE ) {
			$is_obj = new InstallSchema( $this->getDatabaseDriver(), $version, $this->getDatabaseConnection(), $this->getIsUpgrade() );
			return $is_obj->InstallSchema();
		}

		return TRUE;
	}

	function initializeSequence( $obj, $table, $class, $db_conn ) {
		$next_insert_id = $obj->getNextInsertId();
		Debug::Text('Table: '. $table .' Class: '. $class .' Sequence Name: '. $obj->getSequenceName() .' Next Insert ID: '. $next_insert_id, __FILE__, __LINE__, __METHOD__, 10);

		$query = 'select max(id) from '. $table;
		$max_id = (int)$db_conn->GetOne('select max(id) from '. $table);
		if ( $next_insert_id == 0 OR $next_insert_id < $max_id ) {
			Debug::Text('  Out-of-sync sequence table, fixing... Current Max ID: '. $max_id .' Next ID: '. $next_insert_id, __FILE__, __LINE__, __METHOD__, 10);
			if ( strncmp($db_conn->databaseType, 'mysql', 5) == 0 ) {
				if ( $next_insert_id == 0 ) {
					$query = 'insert into '. $obj->getSequenceName() .' VALUES('. ( $max_id + 1 ) .')';
				} else {
					$query = 'update '. $obj->getSequenceName() .' set ID = '. ( $max_id + 1 );
				}
			} elseif ( strncmp($db_conn->databaseType, 'postgres', 8) == 0 ) {
				//This can be helpful with PostgreSQL as well as sequences can get out of sync there too if the schema was created incorrectly.
				$query = 'select setval(\''. $obj->getSequenceName() .'\', '. ( $max_id + 1 ) .')';
			}
			//Debug::Text('  Query: '. $query, __FILE__, __LINE__, __METHOD__, 10);
			$db_conn->Execute($query);
		} else {
			Debug::Text('  Sequence is in sync, not updating...', __FILE__, __LINE__, __METHOD__, 10);
		}

		return TRUE;
	}

	//Only required with MySQL, this can help prevent race conditions when creating new tables.
	//It will also correct any corrupt sequences that don't match their parent tables.
	function initializeSequences() {
		require_once( Environment::getBasePath() . DIRECTORY_SEPARATOR . 'includes'. DIRECTORY_SEPARATOR .'TableMap.inc.php');
		global $global_table_map;

		$db_conn = $this->getDatabaseConnection();

		if ( $db_conn == FALSE ) {
			return FALSE;
		}

		$table_arr = $db_conn->MetaTables();

		foreach( $global_table_map as $table => $class ) {
			if ( class_exists( $class ) AND in_array( $table, $table_arr ) ) {
				$obj = new $class;

				if ( $obj->getSequenceName() != '' ) {
					$this->initializeSequence( $obj, $table, $class, $db_conn );
				}
			}
		}

		return TRUE;
	}

	/*

		System Requirements

	*/

	function getPHPVersion() {
		return PHP_VERSION;
	}

	function checkPHPVersion($php_version = NULL) {
		// Return
		// 0 = OK
		// 1 = Invalid
		// 2 = UnSupported

		if ( $php_version == NULL ) {
			$php_version = $this->getPHPVersion();
		}
		Debug::text('Comparing with Version: '. $php_version, __FILE__, __LINE__, __METHOD__, 9);

		$min_version = '5.3.0';
		$max_version = '7.0.99'; //Change install.php as well, as some versions break backwards compatibility, so we need early checks as well.

		$unsupported_versions = array('');

		/*
			Invalid PHP Versions:
				v5.4.0+ - (Fixed as of 10-Apr-13) Fails due to deprecated call-time references (&$), disable for now.
				v5.3.0+ - Fails due to deprecated functions still in use. This is mostly fixed as of v3.1.0-rc1, leave enabled for now.
				v5.0.4 - Fails to assign object values by ref. In ViewTimeSheet.php $smarty->assign_by_ref( $pp_obj->getId() ) fails.
				v5.2.2 - Fails to populate $HTTP_RAW_POST_DATA http://bugs.php.net/bug.php?id=41293
					   - Implemented work around in global.inc.php
		*/
		$invalid_versions = array('');

		if ( version_compare( $php_version, $min_version, '<') == 1 ) {
			//Version too low
			$retval = 1;
		} elseif ( version_compare( $php_version, $max_version, '>') == 1 ) {
			//UnSupported
			$retval = 2;
		} else {
			$retval = 0;
		}

		foreach( $unsupported_versions as $unsupported_version ) {
			if ( version_compare( $php_version, $unsupported_version, 'eq') == 1 ) {
				$retval = 2;
				break;
			}
		}

		foreach( $invalid_versions as $invalid_version ) {
			if ( version_compare( $php_version, $invalid_version, 'eq') == 1 ) {
				$retval = 1;
				break;
			}
		}

		//Debug::text('RetVal: '. $retval, __FILE__, __LINE__, __METHOD__, 9);
		return $retval;
	}

	function getDatabaseType( $type = NULL ) {
		if ( $type != '' ) {
			$db_type = $type;
		} else {
			//$db_type = $this->config_vars['database']['type'];
			$db_type = $this->getDatabaseDriver();
		}

		if ( stristr($db_type, 'postgres') ) {
			$retval = 'postgresql';
		} elseif ( stristr($db_type, 'mysql') ) {
			$retval = 'mysql';
		} else {
			$retval = 1;
		}

		return $retval;
	}

	function getMemoryLimit() {
		//
		// NULL = unlimited
		// INT = limited to that value

		$raw_limit = ini_get('memory_limit');
		//Debug::text('RAW Limit: '. $raw_limit, __FILE__, __LINE__, __METHOD__, 9);

		$limit = str_ireplace( array('G', 'M', 'K'), array('000000000', '000000', '000'), $raw_limit ); //Use * 1000 rather * 1024 for easier parsing of G, M, K -- Make sure we consider -1 as the limit.
		//$limit = (int)rtrim($raw_limit, 'M');
		//Debug::text('Limit: '. $limit, __FILE__, __LINE__, __METHOD__, 9);

		if ( $raw_limit == '' OR $raw_limit <= 0 ) {
			return NULL;
		}

		return $limit;
	}

	function getPHPConfigFile() {
		return get_cfg_var("cfg_file_path");
	}

	function getConfigFile() {
		return CONFIG_FILE;
	}

	function getPHPIncludePath() {
		return get_cfg_var("include_path");
	}

	function getDatabaseVersion() {
		$db_conn = $this->getDatabaseConnection();
		if ( $db_conn == FALSE ) {
			Debug::text('WARNING: No Database Connection...', __FILE__, __LINE__, __METHOD__, 9);
			return NULL;
		}

		if ( $this->getDatabaseType() == 'postgresql' ) {
			$version = @pg_version();
			Debug::Arr($version, 'PostgreSQL Version: ', __FILE__, __LINE__, __METHOD__, 10);
			if ( $version == FALSE ) {
				//No connection
				return NULL;
			} else {
				return $version['server'];
			}
		} elseif ( $this->getDatabaseType() == 'mysqlt' OR $this->getDatabaseType() == 'mysqli' ) {
			$version = @get_server_info();
			Debug::Text('MySQL Version: '. $version, __FILE__, __LINE__, __METHOD__, 10);
			return $version;
		}

		return FALSE;
	}

	function getDatabaseTypeArray() {
		$retval = array();

		if ( function_exists('pg_connect') ) {
			$retval['postgres8'] = 'PostgreSQL v9.1+';

			// set edb_redwood_date = 'off' must be set, otherwise enterpriseDB
			// changes all date columns to timestamp columns and breaks Fairness.
			//$retval['enterprisedb'] = 'EnterpriseDB (DISABLE edb_redwood_date)';
		}
		if ( function_exists('mysqli_real_connect') ) {
			$retval['mysqli'] = 'MySQLi (v5.5+ w/InnoDB)';
		}
		//MySQLt driver is no longer supported, as it causes conflicts with ADODB and complex queries.
		if ( function_exists('mysql_connect') ) {
			$retval['mysqlt'] = 'MySQL (Legacy Driver - NOT SUPPORTED, use MYSQLi instead!)';
		}

		return $retval;
	}

	function checkFilePermissions() {
		// Return
		//
		// 0 = OK
		// 1 = Invalid
		// 2 = Unsupported

		$dirs = array();

		//Make sure we check all files inside the log, storage, cache and templates_c directories, in case some files were created with the incorrect permissions and can't be overwritten.
		if ( isset($this->config_vars['cache']['dir']) ) {
			$dirs[] = $this->config_vars['cache']['dir'];
		}
		if ( isset($this->config_vars['path']['log']) ) {
			$dirs[] = $this->config_vars['path']['log'];
		}
		if ( isset($this->config_vars['path']['storage']) ) {
			$dirs[] = $this->config_vars['path']['storage'];
		}
		if ( Environment::getTemplateCompileDir() != '' ) {
			$dirs[] = Environment::getTemplateCompileDir();
		}

		$dirs[] = dirname( __FILE__) . DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR;

		$this->getProgressBarObject()->start( $this->getAMFMessageID(), 9000, NULL, TTi18n::getText('Check File Permission...') );
		$i = 0;
		foreach( $dirs as $dir ) {
			Debug::Text('Checking directory readable/writable: '. $dir, __FILE__, __LINE__, __METHOD__, 10);
			if ( is_dir( $dir ) AND is_readable( $dir ) ) {
				try {
					$rdi = new RecursiveDirectoryIterator( $dir, RecursiveIteratorIterator::SELF_FIRST );
					foreach ( new RecursiveIteratorIterator( $rdi ) as $file_name => $cur ) {
						if ( strcmp( basename($file_name), '.') == 0
								OR strcmp( basename($file_name), '..' ) == 0
								OR strcmp( basename($file_name), '.htaccess' ) == 0 ) { //.htaccess files often aren't writable by the webserver.
							continue;
						}

						$this->getProgressBarObject()->set( $this->getAMFMessageID(), $i );

						$i++;

						//Debug::Text('Checking readable/writable: '. $file_name, __FILE__, __LINE__, __METHOD__, 10);
						if ( is_readable( $file_name ) == FALSE ) {
							Debug::Text('File or directory is not readable: '. $file_name, __FILE__, __LINE__, __METHOD__, 10);
							$this->setExtendedErrorMessage( 'checkFilePermissions', 'Not Readable: '. $file_name );
							return 1; //Invalid
						}

						if ( Misc::isWritable( $file_name ) == FALSE ) {
							Debug::Text('File or directory is not writable: '. $file_name, __FILE__, __LINE__, __METHOD__, 10);
							$this->setExtendedErrorMessage( 'checkFilePermissions', 'Not writable: '. $file_name );
							return 1; //Invalid
						}
					}
					unset($cur); //code standards
				} catch( Exception $e ) {
					Debug::Text('Failed opening/reading file or directory: '. $e->getMessage(), __FILE__, __LINE__, __METHOD__, 10);
					return 1;
				}
			}

		}

		$this->getProgressBarObject()->set( $this->getAMFMessageID(), 9000 );

		$this->getProgressBarObject()->stop( $this->getAMFMessageID() );

		Debug::Text('All Files/Directories are readable/writable!', __FILE__, __LINE__, __METHOD__, 10);
		return 0;
	}

	function checkDatabaseType() {
		// Return
		//
		// 0 = OK
		// 1 = Invalid
		// 2 = Unsupported

		$retval = 1;

		if ( function_exists('pg_connect') ) {
			$retval = 0;
		} elseif ( function_exists('mysqli_real_connect') ) {
			$retval = 0;
		} elseif ( function_exists('mysql_connect') ) {
			$retval = 2;
		}

		return $retval;
	}

	function checkDatabaseVersion() {
		$db_version = (string)$this->getDatabaseVersion();
		if ( $db_version == NULL ) {
			Debug::Text('WARNING:  No database connection, unable to verify version!', __FILE__, __LINE__, __METHOD__, 10);
			return 0;
		}

		if ( $this->getDatabaseType() == 'postgresql' ) {
			if ( $db_version == NULL OR version_compare( $db_version, '9.1', '>=') == 1 ) {
				return 0;
			}
		} elseif ( $this->getDatabaseType() == 'mysql' ) {
			if ( version_compare( $db_version, '5.5.0', '>=') == 1 ) {
				return 0;
			}
		}

		Debug::Text('ERROR: Database version failed!', __FILE__, __LINE__, __METHOD__, 10);
		return 1;
	}

	function checkDatabaseEngine() {
		//
		// For MySQL only, this checks to make sure InnoDB is enabled!
		//
		Debug::Text('Checking DatabaseEngine...', __FILE__, __LINE__, __METHOD__, 10);
		if ($this->getDatabaseType() != 'mysql' ) {
			return TRUE;
		}

		$db_conn = $this->getDatabaseConnection();
		if ( $db_conn == FALSE ) {
			Debug::text('No Database Connection.', __FILE__, __LINE__, __METHOD__, 9);
			return FALSE;
		}

		$query = 'show engines';
		$storage_engines = $db_conn->getAll($query);
		//Debug::Arr($storage_engines, 'Available Storage Engines:', __FILE__, __LINE__, __METHOD__, 9);
		if ( is_array($storage_engines) ) {
			foreach( $storage_engines as $data ) {
				Debug::Text('Engine: '. $data['Engine'] .' Support: '. $data['Support'], __FILE__, __LINE__, __METHOD__, 10);
				if ( strtolower($data['Engine']) == 'innodb' AND ( strtolower($data['Support']) == 'yes' OR strtolower($data['Support']) == 'default' )	 ) {
					Debug::text('InnoDB is available!', __FILE__, __LINE__, __METHOD__, 9);
					return TRUE;
				}
			}
		}

		Debug::text('InnoDB is NOT available!', __FILE__, __LINE__, __METHOD__, 9);
		return FALSE;
	}

	function isSUDOinstalled() {
		if ( OPERATING_SYSTEM == 'LINUX' ) {
			exec( 'which sudo', $output, $exit_code );
			if ( $exit_code == 0 AND $output != '' ) {
				return TRUE;
			}
		}

		return FALSE;
	}

	function getWebServerUser() {
		if ( OPERATING_SYSTEM == 'LINUX' ) {
			if ( function_exists('posix_geteuid') AND function_exists('posix_getpwuid') ) {
				$user = posix_getpwuid( posix_geteuid() );
				Debug::text('Webserver running as User: '. $user['name'], __FILE__, __LINE__, __METHOD__, 9);

				return $user['name'];
			}
		}

		return FALSE;
	}

	function getScheduleMaintenanceJobsCommand() {
		$command = FALSE;
		if ( OPERATING_SYSTEM == 'LINUX' ) {
			if ( $this->getWebServerUser() != '' ) {
				$command = Environment::getBasePath() . 'install_cron.sh ' . $this->getWebServerUser();
			}
		} elseif ( OPERATING_SYSTEM == 'WIN' ) {
			$system_root = getenv('SystemRoot');
			if ( $system_root != '' ) {
				$command = $system_root . '\system32\schtasks /create /SC minute /TN fairness_maintenance /TR ""'. Environment::getBasePath() . '..\php\php-win.exe" "' . Environment::getBasePath() . 'maint\cron.php""';
			}
		}

		return $command;
	}

	function ScheduleMaintenanceJobs() {
		$command = $this->getScheduleMaintenanceJobsCommand();
		if ( $command != '' ) {
			exec( $command, $output, $exit_code );
			Debug::Arr($output, 'Schedule Maintenance Jobs Command: '. $command .' Exit Code: '. $exit_code .' Output: ', __FILE__, __LINE__, __METHOD__, 10);
			if ( $exit_code == 0 ) {
				return 0;
			}
		}

		return 1; //Fail so we can display the command to the user instead.
	}

	function getBaseURL() {
		return Misc::getURLProtocol() .'://'. Misc::getHostName( TRUE ).Environment::getBaseURL().'install/install.php'; //Check for a specific file, so we can be sure its not incorrect.
	}
	function getRecommendedBaseURL() {
		return str_replace('install', '', dirname( $_SERVER['SCRIPT_NAME'] ) );
	}
	function checkBaseURL() {
		$url = $this->getBaseURL();
		$headers = @get_headers($url);
		Debug::Arr($headers, 'Checking Base URL: '. $url, __FILE__, __LINE__, __METHOD__, 9);
//		if ( isset($headers[0]) AND stripos($headers[0], '404') !== FALSE ) {
//			return 1; //Not found
//		} else {
//			return 0; //Found
//		}

		return 0; //Found
	}

	function getPHPOpenBaseDir() {
		return ini_get('open_basedir');
	}
	function getPHPCLIDirectory() {
		return dirname( $this->getPHPCLI() );
	}

	function checkPHPOpenBaseDir() {
		$open_basedir = $this->getPHPOpenBaseDir();
		Debug::Text('Open BaseDir: '. $open_basedir, __FILE__, __LINE__, __METHOD__, 9);
		if ( $open_basedir == '' ) {
			return 0;
		} else {
			if ( $this->getPHPCLI() != '' ) {
				//Check if PHPCLIDir is contained in open_basedir, or if open_basedir is contained in the PHPCLIDir.
				//For cases like: open_basedir=/var/www/vhosts/domain/ and php_cli=/var/www/vhosts/domain/usr/bin/
				// Or for cases: open_basedir=/usr/ and php_cli=/usr/bin/
				if ( strpos( $open_basedir, $this->getPHPCLIDirectory() ) !== FALSE OR strpos( $this->getPHPCLIDirectory(), $open_basedir ) !== FALSE ) {
					return 0;
				} else {
					Debug::Text('PHP CLI Binary ('. dirname( $this->getPHPCLIDirectory() ) .') NOT found in Open BaseDir: '. $open_basedir, __FILE__, __LINE__, __METHOD__, 9);
				}
			} else {
				return 0;
			}
		}

		return 1;
	}

	function getPHPCLI() {
		if ( isset($this->config_vars['path']['php_cli']) ) {
			return $this->config_vars['path']['php_cli'];
		}

		return FALSE;
	}
	function checkPHPCLIBinary() {
		if ( $this->getPHPCLI() != '' ) {
			//Sometimes the user may mistaken make the PHP CLI the directory, rather than the executeable itself. Make sure we catch that case.
			if ( is_dir( $this->getPHPCLI() ) == FALSE AND is_executable( $this->getPHPCLI() ) == TRUE ) {
				return 0;
			}
		}

		return 1;
	}

	function checkDiskSpace() {
		$free_space = disk_free_space( dirname( __FILE__ ) );
		$total_space = disk_total_space( dirname( __FILE__ ) );
		$free_space_percent = ( ( $free_space / $total_space ) * 100 );

		$min_free_space = 2000000000; //2GB in bytes.
		$min_free_percent = 6; //6%, due to Linux often having a 5% buffer for root.

		Debug::Text('Free Space: '. $free_space .' Free Percent: '. $free_space_percent, __FILE__, __LINE__, __METHOD__, 10);
		if ( $free_space > $min_free_space AND $free_space_percent > $min_free_percent ) {
			return 0;
		}

		return 1;
	}

	function getPHPCLIRequirementsCommand() {
		$command = '"'. $this->getPHPCLI() .'" "'. Environment::getBasePath() .'tools'. DIRECTORY_SEPARATOR .'unattended_upgrade.php" --config "'. CONFIG_FILE .'" --requirements_only --web_installer';
		return $command;
	}
	//Only check this if *not* being called from the CLI to prevent infinite loops.
	function checkPHPCLIRequirements() {
		if ( $this->checkPHPCLIBinary() === 0 ) {
			$command = $this->getPHPCLIRequirementsCommand();
			exec( $command, $output, $exit_code );
			Debug::Arr($output, 'PHP CLI Requirements Command: '. $command .' Output: ', __FILE__, __LINE__, __METHOD__, 10);
			if ( $exit_code == 0 ) {
				return 0;
			} else {
				$this->setExtendedErrorMessage( 'checkPHPCLIRequirements', 'PHP CLI Requirements Output: '. '<br>'.implode('<br>', (array)$output ) );
			}
		}

		return 1;
	}

	function checkPEAR() {
		@include_once('PEAR.php');

		if ( class_exists('PEAR') ) {
			return 0;
		}

		return 1;
	}

	function checkPEARHTML_Progress() {
		include_once('HTML/Progress.php');

		if ( class_exists('HTML_Progress') ) {
			return 0;
		}

		return 1;
	}

	function checkPEARHTML_AJAX() {
		include_once('HTML/AJAX/Server.php');

		if ( class_exists('HTML_AJAX_Server') ) {
			return 0;
		}

		return 1;
	}

	function checkPEARHTTP_Download() {
		include_once('HTTP/Download.php');

		if ( class_exists('HTTP_Download') ) {
			return 0;
		}

		return 1;
	}

	function checkPEARValidate() {
		include_once('Validate.php');

		if ( class_exists('Validate') ) {
			return 0;
		}

		return 1;
	}

	function checkPEARValidate_Finance() {
		include_once('Validate/Finance.php');

		if ( class_exists('Validate_Finance') ) {
			return 0;
		}

		return 1;
	}

	function checkPEARValidate_Finance_CreditCard() {
		include_once('Validate/Finance/CreditCard.php');

		if ( class_exists('Validate_Finance_CreditCard') ) {
			return 0;
		}

		return 1;
	}

	function checkPEARNET_Curl() {
		include_once('Net/Curl.php');

		if ( class_exists('NET_Curl') ) {
			return 0;
		}

		return 1;
	}

	function checkPEARMail() {
		include_once('Mail.php');

		if ( class_exists('Mail') ) {
			return 0;
		}

		return 1;
	}

	function checkPEARMail_Mime() {
		include_once('Mail/mime.php');

		if ( class_exists('Mail_Mime') ) {
			return 0;
		}

		return 1;
	}

	function checkZIP() {
		if ( class_exists('ZipArchive') ) {
			return 0;
		}

		return 1;
	}

	function checkMAIL() {
		if ( function_exists('mail') ) {
			return 0;
		}

		return 1;
	}

	function checkGETTEXT() {
		if ( function_exists('gettext') ) {
			return 0;
		}

		return 1;
	}

	function checkINTL() {
		//Don't make this a hard requirement in v10 upgrade as its too close to the end of the year.
		return 0;

//		if ( function_exists('locale_get_default') ) {
//			return 0;
//		}
//
//		return 1;
	}

	function checkBCMATH() {
		if ( function_exists('bcscale') ) {
			return 0;
		}

		return 1;
	}

	function checkMBSTRING() {
		if ( function_exists('mb_detect_encoding') ) {
			return 0;
		}

		return 1;
	}

	//No longer required, used pure PHP implemented TTDate::EasterDays() instead.
	function checkCALENDAR() {
		if ( function_exists('easter_date') ) {
			return 0;
		}

		return 1;
	}

	function checkSOAP() {
		if ( class_exists('SoapServer') ) {
			return 0;
		}

		return 1;
	}

	function checkMCRYPT() {
		if ( function_exists('mcrypt_module_open') ) {
			return 0;
		}

		return 1;
	}

	function checkOpenSSL() {
		//FIXME: Automated installer on OSX/Linux doesnt compile SSL into PHP.
		if ( function_exists('openssl_encrypt') OR strtoupper( substr(PHP_OS, 0, 3) ) !== 'WIN' ) {
			return 0;
		}

		return 1;
	}

	function checkGD() {
		if ( function_exists('imagefontheight') ) {
			return 0;
		}

		return 1;
	}

	function checkJSON() {
		if ( function_exists('json_decode') ) {
			return 0;
		}

		return 1;
	}

	//Not currently mandatory, but can be useful to provide better SOAP timeouts.
	function checkCURL() {
		if ( function_exists('curl_exec') ) {
			return 0;
		}

		return 1;
	}

	function checkSimpleXML() {
		if ( class_exists('SimpleXMLElement') ) {
			return 0;
		}

		return 1;
	}


	function checkWritableConfigFile() {
		if ( is_writable( CONFIG_FILE ) ) {
			return 0;
		}

		return 1;
	}

	function checkWritableCacheDirectory() {
		if ( isset($this->config_vars['cache']['dir']) AND is_dir($this->config_vars['cache']['dir']) AND is_writable($this->config_vars['cache']['dir']) ) {
			return 0;
		}

		return 1;
	}

	function cleanCacheDirectory( $exclude_regex_filter = '\.ZIP|\.lock|upgrade_staging' ) {
		global $smarty;

		if ( isset($smarty) ) {
			$smarty->clear_all_cache();
		}

		return Misc::cleanDir( $this->config_vars['cache']['dir'], TRUE, TRUE, FALSE, $exclude_regex_filter ); //Don't clean UPGRADE.ZIP file and 'upgrade_staging' directory.
	}

	function cleanOrphanFiles() {
		if ( PRODUCTION == TRUE ) {
			//Load delete file list.
			$file_list = dirname( __FILE__) . DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR . 'files.delete';

			if ( file_exists( $file_list ) ) {
				$file_list_data = file_get_contents( $file_list );
				$files = explode("\n", $file_list_data );
				unset($file_list_data);
				if ( is_array($files) ) {
					foreach( $files as $file ) {
						if ( $file != '' ) {
							$file = Environment::getBasePath() . str_replace( array('/', '\\'), DIRECTORY_SEPARATOR, $file ); //Prefix base path to all files.
							if ( file_exists( $file ) ) {
								if ( @dir( $file ) ) {
									Debug::Text('Deleting Orphaned Dir: '. $file, __FILE__, __LINE__, __METHOD__, 9);
									Misc::cleanDir( $file, TRUE, TRUE, TRUE );
								} else {
									Debug::Text('Deleting Orphaned File: '. $file, __FILE__, __LINE__, __METHOD__, 9);
									@unlink( $file );
								}
							} else {
								Debug::Text('Orphaned File/Dir does not exist, not deleting: '. $file, __FILE__, __LINE__, __METHOD__, 9);
							}
						}
					}
				}
			}
		}

		return TRUE;
	}

	function checkCleanCacheDirectory() {
		if ( is_dir( $this->config_vars['cache']['dir'] ) ) {
			$raw_cache_files = @scandir( $this->config_vars['cache']['dir'] );
			if ( is_array($raw_cache_files) AND count($raw_cache_files) > 0 ) {
				foreach( $raw_cache_files as $cache_file ) {
					if ( $cache_file != '.' AND $cache_file != '..' AND stristr( $cache_file, '.lock') === FALSE AND stristr( $cache_file, '.ZIP') === FALSE AND stristr( $cache_file, 'upgrade_staging') === FALSE) { //Ignore UPGRADE.ZIP files.
						return 1;
					}
				}
			}
		}
		return 0;
	}

	function checkWritableStorageDirectory() {
		if ( isset($this->config_vars['path']['storage']) AND is_dir($this->config_vars['path']['storage']) AND is_writable($this->config_vars['path']['storage']) ) {
			return 0;
		}

		return 1;
	}

	function checkWritableLogDirectory() {
		if ( isset($this->config_vars['path']['log']) AND is_dir($this->config_vars['path']['log']) AND is_writable($this->config_vars['path']['log']) ) {
			return 0;
		}

		return 1;
	}

	function checkPHPSafeMode() {
		if ( ini_get('safe_mode') != '1' ) {
			return 0;
		}

		return 1;
	}

	function checkPHPAllowURLFopen() {
		if ( ini_get('allow_url_fopen') == '1' ) {
			return 0;
		}

		return 1;
	}

	function checkPHPMemoryLimit() {
		//If changing the minimum memory limit, update Global.inc.php as well, because it always tries to force the memory limit to this value.
		if ( $this->getMemoryLimit() == NULL OR $this->getMemoryLimit() >= (512 * 1000 * 1000) ) { //512Mbytes - Use * 1000 rather than * 1024 so its easier to determine the limit in Global.inc.php and increase it.
			return 0;
		}

		return 1;
	}

	function checkPHPMagicQuotesGPC() {
		if ( get_magic_quotes_gpc() == 1 ) {
			return 1;
		}

		return 0;
	}

	function getCurrentFairnessVersion() {
		//return '1.2.1';
		return APPLICATION_VERSION;
	}

	function getLatestFairnessVersion() {
		if ( $this->checkSOAP() == 0 ) {
			$ttsc = new FairnessSoapClient();
			return $ttsc->getSoapObject()->getInstallerLatestVersion();
		}

		return FALSE;
	}

	function checkAllRequirements( $post_install_requirements_only = FALSE, $exclude_check = FALSE ) {
		// Return
		//
		// 0 = OK
		// 1 = Invalid
		// 2 = Unsupported

		//Total up each OK, Invalid, and Unsupported requirements
		$retarr = array(
						0 => 0,
						1 => 0,
						2 => 0
						);

		//$retarr[1]++; //Test failed requirements.

		$retarr[$this->checkPHPVersion()]++;
		$retarr[$this->checkDatabaseType()]++;
		//$retarr[$this->checkDatabaseVersion()]++; //Requires DB connection, which we often won't have.
		$retarr[$this->checkSOAP()]++;
		$retarr[$this->checkBCMATH()]++;
		$retarr[$this->checkMBSTRING()]++;
		//$retarr[$this->checkCALENDAR()]++;
		$retarr[$this->checkGETTEXT()]++;
		$retarr[$this->checkINTL()]++;
		$retarr[$this->checkGD()]++;
		$retarr[$this->checkJSON()]++;
		$retarr[$this->checkSimpleXML()]++;
		$retarr[$this->checkCURL()]++;
		$retarr[$this->checkZIP()]++;
		$retarr[$this->checkMAIL()]++;
		$retarr[$this->checkOpenSSL()]++;

		$retarr[$this->checkPEAR()]++;

		//PEAR modules are bundled as of v1.2.0
		if ( $post_install_requirements_only == FALSE ) {
			if ( !is_array( $exclude_check ) OR ( is_array($exclude_check) AND in_array('disk_space', $exclude_check) == FALSE ) ) {
				$retarr[$this->checkDiskSpace()]++;
			}
			if ( !is_array( $exclude_check ) OR ( is_array($exclude_check) AND in_array('base_url', $exclude_check) == FALSE ) ) {
				$retarr[$this->checkBaseURL()]++;
			}
			if ( !is_array( $exclude_check ) OR ( is_array($exclude_check) AND in_array('php_cli', $exclude_check) == FALSE ) ) {
				$retarr[$this->checkPHPCLIBinary()]++;
				$retarr[$this->checkPHPOpenBaseDir()]++;
			}
			if ( !is_array( $exclude_check ) OR ( is_array($exclude_check) AND in_array('php_cli_requirements', $exclude_check) == FALSE ) ) {
				$retarr[$this->checkPHPCLIRequirements()]++;
			}
			$retarr[$this->checkWritableConfigFile()]++;
			$retarr[$this->checkWritableCacheDirectory()]++;
			if ( !is_array( $exclude_check ) OR ( is_array($exclude_check) AND in_array('clean_cache', $exclude_check) == FALSE ) ) {
				$retarr[$this->checkCleanCacheDirectory()]++;
			}
			$retarr[$this->checkWritableStorageDirectory()]++;
			$retarr[$this->checkWritableLogDirectory()]++;
			if ( !is_array( $exclude_check ) OR ( is_array($exclude_check) AND in_array('file_permissions', $exclude_check) == FALSE ) ) {
				$retarr[$this->checkFilePermissions()]++;
			}
		}

		$retarr[$this->checkPHPSafeMode()]++;
		$retarr[$this->checkPHPAllowURLFopen()]++;
		$retarr[$this->checkPHPMemoryLimit()]++;
		$retarr[$this->checkPHPMagicQuotesGPC()]++;


		//Debug::Arr($retarr, 'RetArr: ', __FILE__, __LINE__, __METHOD__, 9);

		if ( $retarr[1] > 0 ) {
			return 1;
		} elseif ( $retarr[2] > 0 ) {
			return 2;
		} else {
			return 0;
		}
	}

	function getFailedRequirements( $post_install_requirements_only = FALSE, $exclude_check = FALSE ) {
		$fail_all = FALSE;

		$retarr = array();
		$retarr[] = 'Require';

		if ( $fail_all == TRUE OR $this->checkPHPVersion() != 0 ) {
			$retarr[] = 'PHPVersion';
		}

		if ( $fail_all == TRUE OR $this->checkDatabaseType() != 0 ) {
			$retarr[] = 'DatabaseType';
		}

		//Requires DB connection, which we often won't have.
		//if ( $fail_all == TRUE OR $this->checkDatabaseVersion() != 0 ) {
		//	$retarr[] = 'DatabaseVersion';
		//}

		if ( $fail_all == TRUE OR $this->checkSOAP() != 0 ) {
			$retarr[] = 'SOAP';
		}

		if ( $fail_all == TRUE OR $this->checkBCMATH() != 0 ) {
			$retarr[] = 'BCMATH';
		}

		if ( $fail_all == TRUE OR $this->checkMBSTRING() != 0 ) {
			$retarr[] = 'MBSTRING';
		}

		//if ( $fail_all == TRUE OR $this->checkCALENDAR() != 0 ) {
		//	$retarr[] = 'CALENDAR';
		//}

		if ( $fail_all == TRUE OR $this->checkGETTEXT() != 0 ) {
			$retarr[] = 'GETTEXT';
		}

		if ( $fail_all == TRUE OR $this->checkINTL() != 0 ) {
			$retarr[] = 'INTL';
		}

		if ( $fail_all == TRUE OR $this->checkGD() != 0 ) {
			$retarr[] = 'GD';
		}

		if ( $fail_all == TRUE OR $this->checkJSON() != 0 ) {
			$retarr[] = 'JSON';
		}

		if ( $fail_all == TRUE OR $this->checkSimpleXML() != 0 ) {
			$retarr[] = 'SIMPLEXML';
		}

		if ( $fail_all == TRUE OR $this->checkCURL() != 0 ) {
			$retarr[] = 'CURL';
		}

		if ( $fail_all == TRUE OR $this->checkZIP() != 0 ) {
			$retarr[] = 'ZIP';
		}

		if ( $fail_all == TRUE OR $this->checkMAIL() != 0 ) {
			$retarr[] = 'MAIL';
		}

		if ( $fail_all == TRUE OR $this->checkOpenSSL() != 0 ) {
			$retarr[] = 'OPENSSL';
		}


		//Bundled PEAR modules require the base PEAR package at least
		if ( $fail_all == TRUE OR $this->checkPEAR() != 0 ) {
			$retarr[] = 'PEAR';
		}

		if ( $post_install_requirements_only == FALSE ) {
			if ( is_array($exclude_check) AND in_array('disk_space', $exclude_check) == FALSE ) {
				if ( $fail_all == TRUE OR $this->checkDiskSpace() != 0 ) {
					$retarr[] = 'DiskSpace';
				}
			}
			if ( is_array($exclude_check) AND in_array('base_url', $exclude_check) == FALSE ) {
				if ( $fail_all == TRUE OR $this->checkBaseURL() != 0 ) {
					$retarr[] = 'BaseURL';
				}
			}
			if ( is_array($exclude_check) AND in_array('php_cli', $exclude_check) == FALSE ) {
				if ( $fail_all == TRUE OR $this->checkPHPCLIBinary() != 0 ) {
					$retarr[] = 'PHPCLI';
				}
				if ( $fail_all == TRUE OR $this->checkPHPOpenBaseDir() != 0 ) {
					$retarr[] = 'PHPOpenBaseDir';
				}
			}
			if ( is_array($exclude_check) AND in_array('php_cli_requirements', $exclude_check) == FALSE ) {
				if ( $fail_all == TRUE OR $this->checkPHPCLIRequirements() != 0 ) {
					$retarr[] = 'PHPCLIReq';
				}
			}

			if ( $fail_all == TRUE OR $this->checkWritableConfigFile() != 0 ) {
				$retarr[] = 'WConfigFile';
			}
			if ( $fail_all == TRUE OR $this->checkWritableCacheDirectory() != 0 ) {
				$retarr[] = 'WCacheDir';
			}
			if ( is_array($exclude_check) AND in_array('clean_cache', $exclude_check) == FALSE ) {
				if ( $fail_all == TRUE OR $this->checkCleanCacheDirectory() != 0 ) {
					$retarr[] = 'CleanCacheDir';
				}
			}
			if ( $fail_all == TRUE OR $this->checkWritableStorageDirectory() != 0 ) {
				$retarr[] = 'WStorageDir';
			}
			if ( $fail_all == TRUE OR $this->checkWritableLogDirectory() != 0 ) {
				$retarr[] = 'WLogDir';
			}
			if ( is_array($exclude_check) AND in_array('file_permissions', $exclude_check) == FALSE ) {
				if ( $fail_all == TRUE OR $this->checkFilePermissions() != 0 ) {
					$retarr[] = 'WFilePermissions';
				}
			}
		}

		if ( $fail_all == TRUE OR $this->checkPHPSafeMode() != 0 ) {
			$retarr[] = 'PHPSafeMode';
		}
		if ( $fail_all == TRUE OR $this->checkPHPAllowURLFopen() != 0 ) {
			$retarr[] = 'PHPAllowURLFopen';
		}
		if ( $fail_all == TRUE OR $this->checkPHPMemoryLimit() != 0 ) {
			$retarr[] = 'PHPMemoryLimit';
		}
		if ( $fail_all == TRUE OR $this->checkPHPMagicQuotesGPC() != 0 ) {
			$retarr[] = 'PHPMagicQuotesGPC';
		}

		if ( isset($retarr) ) {
			return $retarr;
		}

		return FALSE;
	}
}
?>
