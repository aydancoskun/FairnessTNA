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

	protected $critical_disabled_functions = array();

	/**
	 * Install constructor.
	 */
	function __construct() {
		global $config_vars;

		require_once( Environment::getBasePath() .'classes'. DIRECTORY_SEPARATOR .'modules'. DIRECTORY_SEPARATOR .'install'. DIRECTORY_SEPARATOR .'InstallSchema.class.php');

		$this->config_vars = $config_vars;

		//Disable caching so we don't exceed maximum memory settings.
		//global $cache;
		//$cache->_onlyMemoryCaching = TRUE; //This shouldn't be required anymore, as it also breaks invalidating cache files.

		ini_set('default_socket_timeout', 5);
		ini_set('allow_url_fopen', 1);

		return TRUE;
	}

	/**
	 * @return null
	 */
	function getDatabaseDriver() {
		return $this->database_driver;
	}

	/**
	 * @param $driver
	 * @return bool
	 */
	function setDatabaseDriver( $driver ) {
		if ( $this->getDatabaseType( $driver ) !== 1 ) {
			$this->database_driver = $this->getDatabaseType( $driver );

			return TRUE;
		}

		return FALSE;
	}

	/**
	 * @return null|ProgressBar
	 */
	function getProgressBarObject() {
		if	( !is_object( $this->progress_bar_obj ) ) {
			$this->progress_bar_obj = new ProgressBar();
		}

		return $this->progress_bar_obj;
	}

	/**
	 * Returns the AMF messageID for each individual call.
	 * @return bool|null
	 */
	function getAMFMessageID() {
		if ( $this->AMF_message_id != NULL ) {
			return $this->AMF_message_id;
		}
		return FALSE;
	}

	/**
	 * @param string $id UUID
	 * @return bool
	 */
	function setAMFMessageID( $id ) {
		Debug::Text('AMF Message ID: '. $id, __FILE__, __LINE__, __METHOD__, 10);
		if ( $id != '' ) {
			$this->AMF_message_id = $id;
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Read .ini file.
	 * Make sure setup_mode is enabled.
	 * @return bool
	 */
	function isInstallMode() {
		if ( isset($this->config_vars['other']['installer_enabled'])
				AND $this->config_vars['other']['installer_enabled'] == 1 ) {
			Debug::text('Install Mode is ON', __FILE__, __LINE__, __METHOD__, 9);
			return TRUE;
		}

		Debug::text('Install Mode is OFF', __FILE__, __LINE__, __METHOD__, 9);
		return FALSE;
	}

	/**
	 * @param $key
	 * @param $msg
	 * @return bool
	 */
	function setExtendedErrorMessage( $key, $msg ) {
		if ( isset($this->extended_error_messages[$key]) AND in_array( $msg, $this->extended_error_messages[$key] ) ) {
			return TRUE;
		} else {
			$this->extended_error_messages[$key][] = $msg;
		}

		return TRUE;
	}

	/**
	 * @param null $key
	 * @return bool|null|string
	 */
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

	/**
	 * Checks if this is the professional version or not
	 * @return int
	 */
	function getTTProductEdition() {
		return getTTProductEdition();
	}

	/**
	 * @return string
	 */
	function getFullApplicationVersion() {
		$retval = APPLICATION_VERSION;

		if ( getTTProductEdition() == TT_PRODUCT_ENTERPRISE ) {
			$retval .= 'E';
		} elseif ( getTTProductEdition() == TT_PRODUCT_CORPORATE ) {
			$retval .= 'C';
		} elseif ( getTTProductEdition() == TT_PRODUCT_PROFESSIONAL ) {
			$retval .= 'P';
		} else {
			$retval .= 'S';
		}

		return $retval;
	}

	/**
	 * @return bool|string
	 */
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

	/**
	 * @param $val
	 */
	function setIsUpgrade( $val ) {
		$this->is_upgrade = (bool)$val;
	}

	/**
	 * @return bool
	 */
	function getIsUpgrade() {
		return $this->is_upgrade;
	}

	/**
	 * @param object $db_obj
	 * @return bool
	 */
	function setDatabaseConnection( $db_obj ) {
		if ( is_object( $db_obj ) ) {
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

	/**
	 * @return bool|null
	 */
	function getDatabaseConnection() {
		if ( isset($this->temp_db) AND ( is_resource($this->temp_db->_connectionID) OR is_object($this->temp_db->_connectionID)	 ) ) {
			return $this->temp_db;
		}

		return FALSE;
	}

	/**
	 * @param $type
	 * @param $host
	 * @param $user
	 * @param $password
	 * @param $database_name
	 * @return bool
	 */
	function setNewDatabaseConnection( $type, $host, $user, $password, $database_name ) {
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

	/**
	 * @param $bool
	 * @return string
	 */
	function HumanBoolean( $bool) {
		if ( $bool === TRUE OR strtolower(trim($bool)) == 'true' ) {
			return 'TRUE';
		} else {
			return 'FALSE';
		}
	}

	/**
	 * @param $new_config_vars
	 * @return bool|mixed
	 */
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

				if ( isset($new_config_vars['other']['primary_company_id']) AND TTUUID::isUUID( $new_config_vars['other']['primary_company_id'] ) == FALSE ) {
					Debug::Text('PRIMARY_COMPANY_ID is attempting to be saved as a non-UUID, ignoring...', __FILE__, __LINE__, __METHOD__, 9);
					unset( $new_config_vars['other']['primary_company_id'] );
				}

				//Check for bug introduced in v7.4.5 that removed all backslashes from paths, to attempt to put them back automatically.
				//This should be able to be removed by v8.0.
				if ( OPERATING_SYSTEM == 'WIN' ) {
					if ( !isset($new_config_vars['path']['php_cli']) AND isset($config_vars['path']['php_cli']) AND strpos( $config_vars['path']['php_cli'], '\\' ) === FALSE  ) {
						Debug::Text('Found php_cli path without backslash, trying to correct...', __FILE__, __LINE__, __METHOD__, 9);
						$new_config_vars['path']['php_cli'] = str_ireplace(':FairnessTNAphpphp-win.exe', ':\FairnessTNA\php\php-win.exe', $config_vars['path']['php_cli'] );
					}
					if ( !isset($new_config_vars['path']['storage']) AND isset($config_vars['path']['storage']) AND strpos( $config_vars['path']['storage'], '\\' ) === FALSE  ) {
						Debug::Text('Found storage path without backslash, trying to correct...', __FILE__, __LINE__, __METHOD__, 9);
						$new_config_vars['path']['storage'] = str_ireplace(':FairnessTNAstorage', ':\FairnessTNA\storage', $config_vars['path']['storage'] );
					}
					if ( !isset($new_config_vars['path']['log']) AND isset($config_vars['path']['log']) AND strpos( $config_vars['path']['log'], '\\' ) === FALSE  ) {
						Debug::Text('Found log path without backslash, trying to correct...', __FILE__, __LINE__, __METHOD__, 9);
						$new_config_vars['path']['log'] = str_ireplace(':FairnessTNAlog', ':\FairnessTNA\log', $config_vars['path']['log'] );
					}
					if ( !isset($new_config_vars['cache']['dir']) AND isset($config_vars['cache']['dir']) AND strpos( $config_vars['cache']['dir'], '\\' ) === FALSE  ) {
						Debug::Text('Found cache path without backslash, trying to correct...', __FILE__, __LINE__, __METHOD__, 9);
						$new_config_vars['cache']['dir'] = str_ireplace(':FairnessTNAcache', ':\FairnessTNA\cache', $config_vars['cache']['dir'] );
					}
				}
				//Clear erroneous INI sections due to same above bug.
				$new_config_vars['installer_enabled'] = 'TT_DELETE';
				$new_config_vars['default_interface'] = 'TT_DELETE';

				//Allow passing any empty array that will just rewrite the existing .INI file fixing any problems.
				if ( is_array($new_config_vars) ) {
					foreach( $new_config_vars as $section => $key_value_map ) {

						if ( !is_array( $key_value_map ) AND $key_value_map == 'TT_DELETE' ) {
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

					//Debug::Arr($data, 'New Config File!', __FILE__, __LINE__, __METHOD__, 9);
					$retval = $config->writeConfig( CONFIG_FILE, 'inicommented' );
					Debug::text('Modified Config File! writeConfig Result: '. $retval, __FILE__, __LINE__, __METHOD__, 9);

					//Make sure the first line in the file contains "die".
					$contents = file_get_contents( CONFIG_FILE );

					//Make sure we add back in the PHP code for security reasons.
					//BitRock seems to want to remove this and re-arrange the INI file as well for some odd reason.
					if ( stripos( $contents, '<?php' ) === FALSE ) {
						Debug::text('Adding back in security feature...', __FILE__, __LINE__, __METHOD__, 9);
						$contents = "; <?php die('Unauthorized Access...'); //SECURITY MECHANISM, DO NOT REMOVE//?>\n".$contents;
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

	/**
	 * @return bool
	 */
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

	/**
	 * @param $database_name
	 * @return bool
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

	/**
	 * @param $database_name
	 * @return bool
	 */
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

	/**
	 * @param $table_name
	 * @return bool
	 */
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

	/**
	 * @return bool
	 */
	function checkSystemSettingTableExists() {
		global $config_vars;
		if ( $this->checkDatabaseExists( $config_vars['database']['database_name'] ) == TRUE ) {
			if ( $this->checkTableExists( 'company' ) == TRUE ) {
				return TRUE;
			}
		}

		return FALSE;
	}

	/**
	 * Get all schema versions
	 * A=Community, B=Professional, C=Corporate, D=Enterprise, T=Tax
	 * @param array $group
	 * @return array
	 */
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

	/**
	 * @return bool
	 */
	function handleSchemaGroupChange() {
		//Pre v7.0, if the database version is less than 7.0 we need to *copy* the schema version from group B to C so we don't try to upgrade the database with old schemas.
		if ( $this->getIsUpgrade() == TRUE ) {
			$sslf = TTnew( 'SystemSettingListFactory' ); /** @var SystemSettingListFactory $sslf */
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
						$tmp_sslf = TTnew( 'SystemSettingListFactory' ); /** @var SystemSettingListFactory $tmp_sslf */
						$tmp_sslf->getByName( $tmp_name );
						if ( $tmp_sslf->getRecordCount() == 1 ) {
							$tmp_obj = $tmp_sslf->getCurrent();
						} else {
							$tmp_obj = TTnew( 'SystemSettingListFactory' ); /** @var SystemSettingListFactory $tmp_obj */
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

	/**
	 * Creates DB schema starting at and including start_version, and ending at, including end version.
	 * Starting at NULL is first version, ending at NULL is last version.
	 * @param null $start_version
	 * @param null $end_version
	 * @param array $group
	 * @return bool
	 */
	function createSchemaRange( $start_version = NULL, $end_version = NULL, $group = array('A', 'B', 'C', 'D') ) {
		global $cache, $config_vars, $PRIMARY_KEY_IS_UUID;

		if ( $this->checkDatabaseSchema() == 1 ) {
			return FALSE;
		}

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
			if ( $this->getIsUpgrade() == TRUE ) {
				$msg = TTi18n::getText('Upgrading database'.'...');
			} else {
				$msg = TTi18n::getText('Initializing database'.'...');
			}

			//Its possible $this->getIsUpgrade() == TRUE when all the schema is created, but no company record exists yet. See APIInstall->setDatabaseSchema()
			// Try to be smarter on when/how we set the PRIMARY_KEY_IS_UUID flag.
			if ( $this->checkTableExists( 'system_setting') == TRUE ) {
				if ( (int)SystemSettingFactory::getSystemSettingValueByKey( 'schema_version_group_A' ) < 1100 ) {
					Debug::Text( '  Upgrading database before first UUID schema version... Setting PRIMARY_KEY_IS_UUID = FALSE', __FILE__, __LINE__, __METHOD__, 1);
					$PRIMARY_KEY_IS_UUID = FALSE;
					$config_vars['other']['disable_audit_log'] = TRUE; //After v11, when UUID is disabled, disable all audit logging too.
				}
			} else { //Likely no DB schema exists yet, so no UUIDs can exist either.
				$PRIMARY_KEY_IS_UUID = FALSE;
				$config_vars['other']['disable_audit_log'] = TRUE; //After v11, when UUID is disabled, disable all audit logging too.
			}

			if ( PHP_SAPI != 'cli' ) { //Don't bother updating progress bar when being run from the CLI.
				$this->getProgressBarObject()->start( $this->getAMFMessageID(), $total_schema_versions, NULL, $msg );
			}

			//Sequences are no longer used after the change to UUID in v11.
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

					if ( PHP_SAPI != 'cli' ) {
						$this->getProgressBarObject()->set( $this->getAMFMessageID(), $x );
					}

					if ( $create_schema_result === FALSE ) {
						Debug::text('CreateSchema Failed! On Version: '. $schema_version, __FILE__, __LINE__, __METHOD__, 9);
						$this->getDatabaseConnection()->FailTrans();
						return FALSE;
					}
					$this->getDatabaseConnection()->CompleteTrans();

					$this->postCreateSchema( $schema_version, $create_schema_result ); //This must be called outside the transaction, so it can handle things like VACUUM.
				}

				//Fast way to clear memory caching only between schema upgrades to make sure it doesn't get too big.
				$cache->_memoryCachingArray = array();
				$cache->_memoryCachingCounter = 0;

				$x++;
			}

			if ( PHP_SAPI != 'cli' ) {
				$this->getProgressBarObject()->stop( $this->getAMFMessageID() );
			}

			//Sequences are no longer used after the change to UUID in v11.
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

	/**
	 * @param $version
	 * @return bool
	 */
	function createSchema( $version ) {
		if ( $version == '' ) {
			return FALSE;
		}

		$install = FALSE;

		$group = (string)substr( $version, -1, 1 );
		$version_number = (int)substr( $version, 0, ( strlen( $version ) - 1 ) );

		global $PRIMARY_KEY_IS_UUID;
		Debug::text('Version: '. $version .' Version Number: '. $version_number .' Group: '. $group .' Primary Key UUID: '. (int)$PRIMARY_KEY_IS_UUID, __FILE__, __LINE__, __METHOD__, 9);

		//Only create schema if current system settings do not exist, or they are
		//older then this current schema version.
		if ( $this->checkTableExists( 'system_setting') == TRUE ) {
			Debug::text('System Setting Table DOES exist...', __FILE__, __LINE__, __METHOD__, 9);

			$sslf = TTnew( 'SystemSettingListFactory' ); /** @var SystemSettingListFactory $sslf */
			$sslf->getByName( 'schema_version_group_'. substr( $version, -1, 1) );
			if ( $sslf->getRecordCount() > 0 ) {
				$ss_obj = $sslf->getCurrent();
				Debug::text('Found System Setting Entry: '. $ss_obj->getValue(), __FILE__, __LINE__, __METHOD__, 9);

				//The schema group letter is on the end of the schema version in the DB, so make sure if that is the case we always strip it off.
				$numeric_installed_schema = (int)substr( $ss_obj->getValue(), 0, ( strlen( $ss_obj->getValue() ) - 1 ) );
				Debug::text('Schema versions, Installed Schema: '. $ss_obj->getValue() .'('. $numeric_installed_schema .') Current Schema: '. $version_number, __FILE__, __LINE__, __METHOD__, 9);

				if ( $numeric_installed_schema < $version_number ) {
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

		if ( $install === TRUE ) {
			$is_obj = new InstallSchema( $this->getDatabaseDriver(), $version, $this->getDatabaseConnection(), $this->getIsUpgrade() );
			$retval = $is_obj->InstallSchema();
		} else {
			$retval = 'SKIP'; //Schema wasn't installed, so we need a 3rd retval to tell postCreateSchema() that the schema didn't fail, but was skipped instead.
			//Debug::text('  SKIPPING schema version...', __FILE__, __LINE__, __METHOD__, 9);
		}

		return $retval;
	}

	/**
	 * @param $schema_version
	 * @param $create_schema_result
	 * @return bool
	 */
	function postCreateSchema( $schema_version, $create_schema_result ) {
		if ( $create_schema_result === TRUE ) { //Only run post functions when the schema was actually installed and not skipped because the schema version is already ahead.
			if ( $this->getDatabaseType() == 'postgresql' ) {
				if ( $schema_version == '1100A' ) { //Large UUID change.
					Debug::text( '    Running VACUUM FULL ANALYZE...', __FILE__, __LINE__, __METHOD__, 9 );
					$this->getDatabaseConnection()->Execute( 'VACUUM FULL ANALYZE' );
				}
			}
		} else {
			Debug::text( '  NOT running postCreateSchema() functions, schema version failed or was skipped...', __FILE__, __LINE__, __METHOD__, 9 );
		}

		return TRUE;
	}

	/**
	 * @param object $obj
	 * @param $table
	 * @param $class
	 * @param $db_conn
	 * @return bool
	 */
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

	/**
	 * Only required with MySQL, this can help prevent race conditions when creating new tables.
	 * It will also correct any corrupt sequences that don't match their parent tables.
	 * @return bool
	 */
	function initializeSequences() {
		global $PRIMARY_KEY_IS_UUID;
		if ( $PRIMARY_KEY_IS_UUID == TRUE ) {
			Debug::Text('  Skipping sequence initialization, in UUID mode!', __FILE__, __LINE__, __METHOD__, 10);
			return TRUE; //Sequences can be ignored when using UUID primary keys.
		}

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
			} else {
				Debug::Text('  Missing class for table: '. $table .' Class: '. $class, __FILE__, __LINE__, __METHOD__, 10);
			}
		}

		return TRUE;
	}

	/*

		System Requirements

	*/

	/**
	 * @return string
	 */
	function getPHPVersion() {
		return PHP_VERSION;
	}

	/**
	 * @param null $php_version
	 * @return int
	 */
	function checkPHPVersion( $php_version = NULL) {
		// Return
		// 0 = OK
		// 1 = Invalid
		// 2 = UnSupported

		/*
		 *
		 *  *** UPDATE APINotification.class.php when minimum PHP version changes, as it gives early warning to users. ***
		 *
		 */

		if ( $php_version == NULL ) {
			$php_version = $this->getPHPVersion();
		}
		Debug::text('Comparing with Version: '. $php_version, __FILE__, __LINE__, __METHOD__, 9);

		$min_version = '5.4.0';
		$max_version = '7.3.99'; //Change install.php as well, as some versions break backwards compatibility, so we need early checks as well.

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

	/**
	 * @param null $type
	 * @return int|string
	 */
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

	/**
	 * @return mixed|null
	 */
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

	/**
	 * @return string
	 */
	function getPHPConfigFile() {
		return get_cfg_var("cfg_file_path");
	}

	/**
	 * @return mixed|string
	 */
	function getConfigFile() {
		return CONFIG_FILE;
	}

	/**
	 * @return string
	 */
	function getPHPIncludePath() {
		return get_cfg_var("include_path");
	}

	/**
	 * @return array|bool|null
	 */
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

	/**
	 * @return array
	 */
	function getDatabaseTypeArray() {
		$retval = array();
		$retval['postgres8'] = 'PostgreSQL';
//		if ( function_exists('pg_connect') ) {
//			$retval['postgres8'] = 'PostgreSQL v9.1+';
//
//			// set edb_redwood_date = 'off' must be set, otherwise enterpriseDB
//			// changes all date columns to timestamp columns and breaks FairnessTNA.
//			//$retval['enterprisedb'] = 'EnterpriseDB (DISABLE edb_redwood_date)';
//		}
//		if ( function_exists('mysqli_real_connect') ) {
//			$retval['mysqli'] = 'NOT SUPPORTED - MySQLi (v5.5+ w/InnoDB)';
//		}
//		//MySQLt driver is no longer supported, as it causes conflicts with ADODB and complex queries.
//		if ( function_exists('mysql_connect') ) {
//			$retval['mysqlt'] = 'NOT SUPPORTED - MySQL (Legacy Driver - NOT SUPPORTED, use MYSQLi instead!)';
//		}

		return $retval;
	}

	/**
	 * @return int
	 */
	function checkFilePermissions() {
		// Return
		//
		// 0 = OK
		// 1 = Invalid
		// 2 = Unsupported
		if ( PRODUCTION == FALSE OR DEPLOYMENT_ON_DEMAND == TRUE ) {
			return 0; //Skip permission checks.
		}

		//Some systems, especially Windows with really poor virus scanners, will spend 12-24hrs checking permissions, exceeding the maximum execution and likely not triggering visible errors to the user.
		// Try to exit out much earlier if we detect poor performance.
		//$maximum_run_time = 10800; //3 hours - About 1.0s per file. This needs to be fairly high as there could be many cache files that its checking during weekly maintenance. Install/Upgrades clear the cache, so it shouldn't be a problem in that case.
		$start_time = time();

		$is_root_user = Misc::isCurrentOSUserRoot();
		if ( $is_root_user == TRUE ) {
			$web_server_user = Misc::findWebServerOSUser();
			Debug::Text('Current user is root, attempt to fix any permissions that fail... New User: '. $web_server_user, __FILE__, __LINE__, __METHOD__, 10);
		}

		$dirs = array();

		//Make sure we check all files inside the log, storage, and cache directories, in case some files were created with the incorrect permissions and can't be overwritten.
		if ( isset($this->config_vars['cache']['dir']) ) {
			$dirs[] = $this->config_vars['cache']['dir'];
		}
		if ( isset($this->config_vars['path']['log']) ) {
			$dirs[] = $this->config_vars['path']['log'];
		}
		if ( isset($this->config_vars['path']['storage']) ) {
			$dirs[] = $this->config_vars['path']['storage'];
		}

		$dirs[] = realpath( dirname( __FILE__) . DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR );

		if ( PHP_SAPI != 'cli' ) { //Don't bother updating progress bar when being run from the CLI.
			$this->getProgressBarObject()->start( $this->getAMFMessageID(), 10000, NULL, TTi18n::getText( 'Check File Permission...' ) );
		}

		$i = 0;
		foreach( $dirs as $dir ) {
			Debug::Text('Checking directory readable/writable: '. $dir, __FILE__, __LINE__, __METHOD__, 10);
			if ( is_dir( $dir ) AND is_readable( $dir ) ) {
				try {
					$rdi = new RecursiveDirectoryIterator( $dir, RecursiveIteratorIterator::SELF_FIRST );
					foreach ( new RecursiveIteratorIterator( $rdi ) as $file_name => $cur ) {
						//Check if its "." or current directory, and format it as a directory, so file_exists() doesn't fail below.
						// If /var/cache/fairnesstna is chmod 660, file_exists() returns FALSE on '/var/cache/fairnesstna/.' but TRUE on '/var/cache/fairnesstna'
						if ( strcmp( basename($file_name), '.') == 0 ) {
							$file_name = dirname( $file_name ) . DIRECTORY_SEPARATOR;
						}

						//Check if the file is ignored.
						if (
								//strcmp( basename($file_name), '.') == 0 OR //Make sure we do check "." (the current directory). As permissions could be denied on it, but allowed on all sub-dirs/files.
								strcmp( basename($file_name), '..' ) == 0
								OR strpos( $file_name, '.git' ) !== FALSE
								OR strcmp( basename($file_name), '.htaccess' ) == 0 ) { //.htaccess files often aren't writable by the webserver.
							continue;
						}

						//Its possible if it takes a long time to iterate the files, they could be gone by the time we get to them, so just check them again.
						if ( file_exists( $file_name ) == FALSE ) {
							Debug::Text('  Skipping: '. $file_name .' does not exist... File Exists: '. (int)file_exists( $file_name ), __FILE__, __LINE__, __METHOD__, 10);
							continue;
						}

						if ( $is_root_user == TRUE AND $web_server_user != FALSE AND @fileowner( $file_name ) === 0 ) { //Check if file is owned by root. If so, change the owner before we check is readable/writable.
							Debug::Text('  Changing ownership of: '. $file_name, __FILE__, __LINE__, __METHOD__, 10);
							@chown( $file_name, $web_server_user );
							@chgrp( $file_name, $web_server_user );
						}

						//Debug::Text('Checking readable/writable: '. $file_name, __FILE__, __LINE__, __METHOD__, 10);
						if ( is_readable( $file_name ) == FALSE ) { //Since file_exists() is called a few lines above, no need to do it again here.
							Debug::Text('File or directory is not readable: '. $file_name, __FILE__, __LINE__, __METHOD__, 10);
							$this->setExtendedErrorMessage( 'checkFilePermissions', 'Not Readable: '. $file_name );
							return 1; //Invalid
						}

						if ( Misc::isWritable( $file_name ) == FALSE ) {
							Debug::Text('File or directory is not writable: '. $file_name, __FILE__, __LINE__, __METHOD__, 10);
							$this->setExtendedErrorMessage( 'checkFilePermissions', 'Not writable: '. $file_name );
							return 1; //Invalid
						}

						//Ignore for now as it could slow due to the infinite loop caused by: C:\FairnessTNA\cache\upgrade_staging\latest_version\interface\html5\views\payroll\pay_stub_transaction\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
						//if ( ( $i % 10 ) == 0 AND ( time() - $start_time ) > $maximum_run_time ) {
						//	Debug::Text('Exceeded maximum run time of: '. $maximum_run_time .' Files Checked: '. $i .' in: '. round( time() - $start_time ) .'s', __FILE__, __LINE__, __METHOD__, 10);
						//	$this->setExtendedErrorMessage( 'checkFilePermissions', 'Poor system performance, unable to check all files... Files Checked: '. $i .' in: '. round( time() - $start_time ) .'s' );
						//	return 1; //Invalid
						//}

						//Do this last, as it can take a long time on some systems using a slow file system.
						if ( PHP_SAPI != 'cli' AND ( $i % 100 ) == 0 )  {
							$this->getProgressBarObject()->set( $this->getAMFMessageID(), $i );
						}

						$i++;
					}
					unset($cur); //code standards
				} catch( Exception $e ) {
					Debug::Text('Failed opening/reading file or directory: '. $e->getMessage(), __FILE__, __LINE__, __METHOD__, 10);
					return 1;
				}
			} else {
				Debug::Text('Failed reading directory: '. $dir, __FILE__, __LINE__, __METHOD__, 10);
				$this->setExtendedErrorMessage( 'checkFilePermissions', 'Not Readable: '. $dir );
				return 1;
			}
		}

		if ( PHP_SAPI != 'cli' ) {
			$this->getProgressBarObject()->set( $this->getAMFMessageID(), 10000 );

			$this->getProgressBarObject()->stop( $this->getAMFMessageID() );
		}

		Debug::Text('All Files/Directories ('. $i .') are readable/writable! Files Checked: '. $i .' in: '. ( time() - $start_time ) .'s', __FILE__, __LINE__, __METHOD__, 10);
		return 0;
	}

	/**
	 * @return int
	 */
	function checkFileChecksums() {
		// Return
		//
		// 0 = OK
		// 1 = Invalid
		// 2 = Unsupported

		if ( PRODUCTION == FALSE OR DEPLOYMENT_ON_DEMAND == TRUE ) {
			return 0; //Skip checksums.
		}

		//Load checksum file.

		$checksum_file = dirname( __FILE__) . DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR . 'files.sha1';

		if ( file_exists( $checksum_file ) ) {
			$checksum_data = file_get_contents( $checksum_file );
			$checksums = explode("\n", $checksum_data );
			unset($checksum_data);
			if ( is_array($checksums) ) {

				if ( PHP_SAPI != 'cli' ) { //Don't bother updating progress bar when being run from the CLI.
					$this->getProgressBarObject()->start( $this->getAMFMessageID(), count( $checksums ), NULL, TTi18n::getText( 'Check File Checksums...' ) );
				}

				$i = 0;
				foreach($checksums as $checksum_line ) {

					//1st line contains the TT version for the checksums, make sure it matches current version.
					if ( $i == 0 ) {
						if ( preg_match( '/\d+\.\d+\.\d+/', $checksum_line, $checksum_version ) ) {
							Debug::Text('Checksum version: '. $checksum_version[0], __FILE__, __LINE__, __METHOD__, 10);
							if ( version_compare( APPLICATION_VERSION, $checksum_version[0], '=') ) {
								Debug::Text('Checksum version matches!', __FILE__, __LINE__, __METHOD__, 10);
							} else {
								Debug::Text('Checksum version DOES NOT match! Version: '. APPLICATION_VERSION .' Checksum Version: '. $checksum_version[0], __FILE__, __LINE__, __METHOD__, 10);
								$this->setExtendedErrorMessage( 'checkFileChecksums', 'Application version does not match checksum version: '. $checksum_version[0] );
								return 1;
							}
						} else {
							Debug::Text('Checksum version not found in file: '. $checksum_line, __FILE__, __LINE__, __METHOD__, 10);
						}
					} elseif ( strlen( $checksum_line ) > 1 ) {
						$split_line = explode(' ', $checksum_line );
						if ( is_array($split_line) ) {
							$file_name = Environment::getBasePath() . str_replace( '/', DIRECTORY_SEPARATOR, str_replace('./', '', trim($split_line[2]) ) );
							$checksum = trim($split_line[0]);

							if ( file_exists( $file_name ) ) {
								$my_checksum = @sha1_file( $file_name );
								if ( $my_checksum == $checksum ) {
									//Debug::Text('File: '. $file_name .' Checksum: '. $checksum .' MATCHES', __FILE__, __LINE__, __METHOD__, 10);
									unset($my_checksum); //NoOp
								} else {
									Debug::Text('File: '. $file_name .' Checksum: '. $my_checksum .' DOES NOT match provided checksum of: '. $checksum, __FILE__, __LINE__, __METHOD__, 10);
									$this->setExtendedErrorMessage( 'checkFileChecksums', 'Checksum does not match: '. $file_name );
									return 1; //Invalid
								}
								unset($my_checksum);
							} else {
								Debug::Text('File does not exist: '. $file_name, __FILE__, __LINE__, __METHOD__, 10);
								$this->setExtendedErrorMessage( 'checkFileChecksums', 'File does not exist: '. $file_name );
								return 1; //Invalid
							}

						}
						unset($split_line, $file_name, $checksum);
					}

					if ( PHP_SAPI != 'cli' AND ( $i % 100 ) == 0 ) {
						$this->getProgressBarObject()->set( $this->getAMFMessageID(), $i );
					}

					$i++;

				}

				if ( PHP_SAPI != 'cli' ) {
					$this->getProgressBarObject()->stop( $this->getAMFMessageID() );
				}

				return 0; //OK
			}
		} else {
			Debug::Text('Checksum file does not exist: '. $checksum_file, __FILE__, __LINE__, __METHOD__, 10);
			$this->setExtendedErrorMessage( 'checkFileChecksums', 'Checksum file does not exist: '. $checksum_file );
		}

		return 1; //Invalid
	}

	/**
	 * @return int
	 */
	function checkDatabaseType() {
		// Return
		//
		// 0 = OK
		// 1 = Invalid
		// 2 = mysql type in ini

		$retval = 1;

		global $config_vars;
		if ( isset($config_vars['database']['type']) AND strncmp($config_vars['database']['type'], 'mysql', 5) == 0 ) {
			$retval = 2;
		} elseif ( function_exists('pg_connect') ) {
			$retval = 0;
		}

		return $retval;
	}

	/**
	 * @return int
	 */
	function checkDatabaseVersion() {
		$db_version = (string)$this->getDatabaseVersion();
		if ( $db_version == NULL ) {
			Debug::Text('WARNING:  No database connection, unable to verify version!', __FILE__, __LINE__, __METHOD__, 10);
			return 0;
		}

		if ( $this->getDatabaseType() == 'postgresql' ) {
			if ( $db_version == NULL OR version_compare( $db_version, '9.4', '>=') == 1 ) { //v9.4 has JSONB support.
				return 0;
			}
		}
//		elseif ( $this->getDatabaseType() == 'mysql' ) {
//			if ( version_compare( $db_version, '5.5.0', '>=') == 1 ) {
//				return 0;
//			}
//		}

		Debug::Text('ERROR: Database version failed!', __FILE__, __LINE__, __METHOD__, 10);
		return 1;
	}

//	/**
//	 * @return bool
//	 */
//	function checkDatabaseEngine() {
//		//
//		// For MySQL only, this checks to make sure InnoDB is enabled!
//		//
//		Debug::Text('Checking DatabaseEngine...', __FILE__, __LINE__, __METHOD__, 10);
//		if ($this->getDatabaseType() != 'mysql' ) {
//			return TRUE;
//		}
//
//		$db_conn = $this->getDatabaseConnection();
//		if ( $db_conn == FALSE ) {
//			Debug::text('No Database Connection.', __FILE__, __LINE__, __METHOD__, 9);
//			return FALSE;
//		}
//
//		$query = 'show engines';
//		$storage_engines = $db_conn->getAll($query);
//		//Debug::Arr($storage_engines, 'Available Storage Engines:', __FILE__, __LINE__, __METHOD__, 9);
//		if ( is_array($storage_engines) ) {
//			foreach( $storage_engines as $data ) {
//				Debug::Text('Engine: '. $data['Engine'] .' Support: '. $data['Support'], __FILE__, __LINE__, __METHOD__, 10);
//				if ( strtolower($data['Engine']) == 'innodb' AND ( strtolower($data['Support']) == 'yes' OR strtolower($data['Support']) == 'default' )	 ) {
//					Debug::text('InnoDB is available!', __FILE__, __LINE__, __METHOD__, 9);
//					return TRUE;
//				}
//			}
//		}
//
//		Debug::text('InnoDB is NOT available!', __FILE__, __LINE__, __METHOD__, 9);
//		return FALSE;
//	}

	/**
	 * @return bool|int
	 */
	function checkDatabaseSchema() {
		if ( getTTProductEdition() == TT_PRODUCT_COMMUNITY ) {
			$db_conn = $this->getDatabaseConnection();
			if ( $db_conn == FALSE ) {
				Debug::text('No Database Connection.', __FILE__, __LINE__, __METHOD__, 9);
				return FALSE;
			}

			if ( $this->checkTableExists( 'system_setting' ) == TRUE ) {
				$sslf = TTnew( 'SystemSettingListFactory' ); /** @var SystemSettingListFactory $sslf */
				$sslf->getByName( 'schema_version_group_B' );
				if ( $sslf->getRecordCount() == 1 ) {
					Debug::text('ERROR: Database schema out of sync with edition...', __FILE__, __LINE__, __METHOD__, 9);
					return 1;
				}
			}
		}

		return 0;
	}

	/**
	 * @return bool
	 */
	function isSUDOinstalled() {
		if ( OPERATING_SYSTEM == 'LINUX' ) {
			exec( 'which sudo', $output, $exit_code );
			if ( $exit_code == 0 AND $output != '' ) {
				return TRUE;
			}
		}

		return FALSE;
	}

	/**
	 * @return bool
	 */
	function getWebServerUser() {
		return Misc::getCurrentOSUser();
	}

	/**
	 * @return bool|string
	 */
	function getScheduleMaintenanceJobsCommand() {
		$command = FALSE;
		if ( OPERATING_SYSTEM == 'LINUX' ) {
			if ( $this->getWebServerUser() != '' ) {
				$command = Environment::getBasePath() . 'install_cron.sh ' . $this->getWebServerUser();
			}
		} elseif ( OPERATING_SYSTEM == 'WIN' ) {
			$system_root = getenv('SystemRoot');
			if ( $system_root != '' ) {
				//Example: schtasks /create /SC minute /TN fairnesstna_maintenance /TR "c:\php\php-win.exe" "c:\fairnesstna\fairnesstna\maint\cron.php"
				$command = $system_root . '\system32\schtasks /create /SC minute /TN fairnesstna_maintenance /TR ""'. Environment::getBasePath() . '..\php\php-win.exe" "' . Environment::getBasePath() . 'maint\cron.php""';
			}
		}

		return $command;
	}

	/**
	 * @return int
	 */
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

	/**
	 * @return string
	 */
	function getBaseURL() {
		return Misc::getURLProtocol() .'://'. Misc::getHostName( TRUE ).Environment::getBaseURL().'install/install.php'; //Check for a specific file, so we can be sure its not incorrect.
	}

	/**
	 * @return mixed
	 */
	function getRecommendedBaseURL() {
		return str_replace( array( 'install', 'api/json' ), array( '', '' ), dirname( $_SERVER['SCRIPT_NAME'] ) ) .'/interface';
	}

	/**
	 * @return int
	 */
	function checkBaseURL() {
		$url = $this->getBaseURL();
		$headers = @get_headers($url);
		Debug::Arr($headers, 'Checking Base URL: '. $url, __FILE__, __LINE__, __METHOD__, 9);
		if ( isset($headers[0]) AND stripos($headers[0], '404') !== FALSE ) {
			return 1; //Not found
		} else {
			return 0; //Found
		}
	}

	/**
	 * @return string
	 */
	function getPHPOpenBaseDir() {
		return ini_get('open_basedir');
	}

	/**
	 * @return string
	 */
	function getPHPCLIDirectory() {
		return dirname( $this->getPHPCLI() );
	}

	/**
	 * @return int
	 */
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

	/**
	 * @return bool
	 */
	function getPHPCLI() {
		if ( isset($this->config_vars['path']['php_cli']) ) {
			return $this->config_vars['path']['php_cli'];
		}

		return FALSE;
	}

	/**
	 * @return int
	 */
	function checkPHPCLIBinary() {
		if ( $this->getPHPCLI() != '' ) {
			//Sometimes the user may mistaken make the PHP CLI the directory, rather than the executeable itself. Make sure we catch that case.
			if ( is_dir( $this->getPHPCLI() ) == FALSE AND is_executable( $this->getPHPCLI() ) == TRUE ) {
				return 0;
			}
		}

		return 1;
	}

	/**
	 * @return int
	 */
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

	/**
	 * @return string
	 */
	function getPHPCLIRequirementsCommand() {
		$command = '"'. $this->getPHPCLI() .'" "'. Environment::getBasePath() .'tools'. DIRECTORY_SEPARATOR .'unattended_upgrade.php" --config "'. CONFIG_FILE .'" --requirements_only --web_installer';
		return $command;
	}

	/**
	 * Only check this if *not* being called from the CLI to prevent infinite loops.
	 * @return int
	 */
	function checkPHPCLIRequirements() {
		if ( $this->checkPHPCLIBinary() === 0 ) {
			$command = $this->getPHPCLIRequirementsCommand();
			exec( $command, $output, $exit_code );
			Debug::Arr($output, 'PHP CLI Requirements Command: '. $command .' Exit Code: '. $exit_code .' Output: ', __FILE__, __LINE__, __METHOD__, 10);
			if ( $exit_code == 0 ) {
				return 0;
			} else {
				$this->setExtendedErrorMessage( 'checkPHPCLIRequirements', 'PHP CLI Requirements Output: '. '<br>'.implode('<br>', (array)$output ) );
			}
		}

		return 1;
	}

	/**
	 * @return int
	 */
	function checkPEAR() {
		@include_once('PEAR.php');

		if ( class_exists('PEAR') ) {
			return 0;
		}

		return 1;
	}

	/**
	 * @return int
	 */
	function checkPEARHTTP_Download() {
		include_once('HTTP/Download.php');

		if ( class_exists('HTTP_Download') ) {
			return 0;
		}

		return 1;
	}

	/**
	 * @return int
	 */
	function checkPEARValidate() {
		include_once('Validate.php');

		if ( class_exists('Validate') ) {
			return 0;
		}

		return 1;
	}

	/**
	 * @return int
	 */
	function checkPEARValidate_Finance() {
		include_once('Validate/Finance.php');

		if ( class_exists('Validate_Finance') ) {
			return 0;
		}

		return 1;
	}

	/**
	 * @return int
	 */
	function checkPEARValidate_Finance_CreditCard() {
		include_once('Validate/Finance/CreditCard.php');

		if ( class_exists('Validate_Finance_CreditCard') ) {
			return 0;
		}

		return 1;
	}

	/**
	 * @return int
	 */
	function checkPEARNET_Curl() {
		include_once('Net/Curl.php');

		if ( class_exists('NET_Curl') ) {
			return 0;
		}

		return 1;
	}

	/**
	 * @return int
	 */
	function checkPEARMail() {
		include_once('Mail.php');

		if ( class_exists('Mail') ) {
			return 0;
		}

		return 1;
	}

	/**
	 * @return int
	 */
	function checkPEARMail_Mime() {
		include_once('Mail/mime.php');

		if ( class_exists('Mail_Mime') ) {
			return 0;
		}

		return 1;
	}

	/**
	 * @return int
	 */
	function checkZIP() {
		if ( class_exists('ZipArchive') ) {
			return 0;
		}

		return 1;
	}

	/**
	 * @return int
	 */
	function checkMAIL() {
		if ( function_exists('mail') ) {
			return 0;
		}

		return 1;
	}

	/**
	 * @return int
	 */
	function checkGETTEXT() {
		if ( function_exists('gettext') ) {
			return 0;
		}

		return 1;
	}

	/**
	 * @return int
	 */
	function checkINTL() {
		//Don't make this a hard requirement in v10 upgrade as its too close to the end of the year.
		return 0;

//		if ( function_exists('locale_get_default') ) {
//			return 0;
//		}
//
//		return 1;
	}

	/**
	 * @return int
	 */
	function checkBCMATH() {
		if ( function_exists('bcscale') ) {
			return 0;
		}

		return 1;
	}

	/**
	 * @return int
	 */
	function checkMBSTRING() {
		if ( function_exists('mb_detect_encoding') ) {
			return 0;
		}

		return 1;
	}

	/**
	 * No longer required, used pure PHP implemented TTDate::EasterDays() instead.
	 * @return int
	 */
	function checkCALENDAR() {
		if ( function_exists('easter_date') ) {
			return 0;
		}

		return 1;
	}

	/**
	 * @return int
	 */
	function checkSOAP() {
		if ( class_exists('SoapServer') ) {
			return 0;
		}

		return 1;
	}

	/**
	 * @return int
	 */
	//function checkMCRYPT() {
	//	if ( function_exists('mcrypt_module_open') ) {
	//		return 0;
	//	}
	//
	//	return 1;
	//}

	/**
	 * @return int
	 */
	function checkOpenSSL() {
		//FIXME: Automated installer on OSX/Linux doesnt compile SSL into PHP.
		if ( function_exists('openssl_encrypt') OR strtoupper( substr(PHP_OS, 0, 3) ) !== 'WIN' ) {
			return 0;
		}

		return 1;
	}

	/**
	 * @return int
	 */
	function checkGD() {
		if ( function_exists('imagefontheight') ) {
			return 0;
		}

		return 1;
	}

	/**
	 * @return int
	 */
	function checkJSON() {
		if ( function_exists('json_decode') ) {
			return 0;
		}

		return 1;
	}

	/**
	 * Not currently mandatory, but can be useful to provide better SOAP timeouts.
	 * @return int
	 */
	function checkCURL() {
		if ( function_exists('curl_exec') ) {
			return 0;
		}

		return 1;
	}

	/**
	 * @return int
	 */
	function checkSimpleXML() {
		if ( class_exists('SimpleXMLElement') ) {
			return 0;
		}

		return 1;
	}


	/**
	 * @return int
	 */
	function checkWritableConfigFile() {
		if ( Misc::isWritable( CONFIG_FILE ) ) {
			return 0;
		}

		return 1;
	}

	/**
	 * @return int
	 */
	function checkWritableCacheDirectory() {
		if ( isset($this->config_vars['cache']['dir']) AND is_dir($this->config_vars['cache']['dir']) AND Misc::isWritable($this->config_vars['cache']['dir']) ) {
			return 0;
		}

		return 1;
	}

	/**
	 * @return int
	 */
	function checkSafeCacheDirectory() {
		//Make sure the storage path isn't inside an publically accessible directory
		if ( isset($this->config_vars['cache']['dir']) AND Misc::isSubDirectory( $this->config_vars['cache']['dir'], Environment::getBasePath() ) == FALSE ) {
			return 0;
		}

		return 1;
	}

	/**
	 * @param string $exclude_regex_filter
	 * @return bool
	 */
	function cleanCacheDirectory( $exclude_regex_filter = '\.ZIP|\.lock|.state|upgrade_staging' ) {
		return Misc::cleanDir( $this->config_vars['cache']['dir'], TRUE, TRUE, FALSE, $exclude_regex_filter ); //Don't clean UPGRADE.ZIP file and 'upgrade_staging' directory.
	}

	/**
	 * @return bool
	 */
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

	/**
	 * @return int
	 */
	function checkCleanCacheDirectory() {
		if ( DEPLOYMENT_ON_DEMAND == FALSE ) {
			if ( is_dir( $this->config_vars['cache']['dir'] ) ) {
				$raw_cache_files = @scandir( $this->config_vars['cache']['dir'] );

				if ( is_array($raw_cache_files) AND count($raw_cache_files) > 0 ) {
					foreach( $raw_cache_files as $cache_file ) {
						if ( $cache_file != '.' AND $cache_file != '..' AND stristr( $cache_file, '.state') === FALSE AND stristr( $cache_file, '.lock') === FALSE AND stristr( $cache_file, '.ZIP') === FALSE AND stristr( $cache_file, 'upgrade_staging') === FALSE) { //Ignore UPGRADE.ZIP files.
							Debug::Text('Cache file remaining: '. $cache_file, __FILE__, __LINE__, __METHOD__, 9);
							return 1;
						}
					}
				}
			}
		}

		return 0;
	}

	/**
	 * @return int
	 */
	function checkWritableStorageDirectory() {
		if ( isset($this->config_vars['path']['storage']) AND is_dir($this->config_vars['path']['storage']) AND Misc::isWritable($this->config_vars['path']['storage']) ) {
			return 0;
		}

		return 1;
	}

	/**
	 * @return int
	 */
	function checkSafeStorageDirectory() {
		//Make sure the storage path isn't inside an publically accessible directory
		if ( isset($this->config_vars['path']['storage']) AND Misc::isSubDirectory( $this->config_vars['path']['storage'], Environment::getBasePath() ) == FALSE ) {
			return 0;
		}

		return 1;
	}

	/**
	 * @return int
	 */
	function checkWritableLogDirectory() {
		if ( isset($this->config_vars['path']['log']) AND is_dir($this->config_vars['path']['log']) AND Misc::isWritable($this->config_vars['path']['log']) ) {
			return 0;
		}

		return 1;
	}

	/**
	 * @return int
	 */
	function checkSafeLogDirectory() {
		//Make sure the storage path isn't inside an publically accessible directory
		if ( isset($this->config_vars['path']['log']) AND Misc::isSubDirectory( $this->config_vars['path']['log'], Environment::getBasePath() ) == FALSE ) {
			return 0;
		}

		return 1;
	}

	/**
	 * @return array
	 */
	function getCriticalFunctionList() {
		$critical_functions = array( 'system', 'exec', 'passthru' , 'shell_exec', 'curl', 'curl_exec', 'curl_multi_exec', 'parse_ini_file', 'unlink', 'rename', 'eval' ); //'pcntl_alarm'
		return $critical_functions;
	}

	/**
	 * @return string
	 */
	function getCriticalDisabledFunctionList() {
		return implode(',', $this->critical_disabled_functions );
	}

	/**
	 * Check to see if they have disabled functions in there PHP.ini file.
	 * This can cause all sorts of strange failures, but most often they have system(), exec() and other OS/file system related functions disabled that completely breaks things.
	 * @return int
	 */
	function checkPHPDisabledFunctions() {
		$critical_functions = $this->getCriticalFunctionList();
		$disabled_functions = explode(',', ini_get('disable_functions') );

		$this->critical_disabled_functions = array_intersect( $critical_functions, $disabled_functions );
		if ( count($this->critical_disabled_functions) == 0 ) {
			return 0;
		}

		Debug::Arr($this->critical_disabled_functions, 'Disabled functions that must be enabled: ', __FILE__, __LINE__, __METHOD__, 10);
		return 1;
	}

	/**
	 * @return int
	 */
	function checkPHPSafeMode() {
		if ( ini_get('safe_mode') != '1' ) {
			return 0;
		}

		return 1;
	}

	/**
	 * @return int
	 */
	function checkPHPAllowURLFopen() {
		if ( ini_get('allow_url_fopen') == '1' ) {
			return 0;
		}

		return 1;
	}

	/**
	 * @return int
	 */
	function checkPHPMemoryLimit() {
		//If changing the minimum memory limit, update Global.inc.php as well, because it always tries to force the memory limit to this value.
		if ( $this->getMemoryLimit() == NULL OR $this->getMemoryLimit() >= (512 * 1000 * 1000) ) { //512Mbytes - Use * 1000 rather than * 1024 so its easier to determine the limit in Global.inc.php and increase it.
			return 0;
		}

		return 1;
	}

	/**
	 * @return int
	 */
	function checkPHPMagicQuotesGPC() {
		if ( get_magic_quotes_gpc() == 1 ) {
			return 1;
		}

		return 0;
	}

	/**
	 * @return string
	 */
	function getCurrentVersion() {
		//return '1.2.1';
		return APPLICATION_VERSION;
	}

	/**
	 * @return bool
	 */
	function getLatestVersion() {
		if ( $this->checkSOAP() == 0 ) {
			return Misc::getInstallerLatestVersion();
		}

		return FALSE;
	}

	/**
	 * @return int
	 */
	function checkVersion() {
		$current_version = $this->getCurrentVersion();
		$latest_version = $this->getLatestVersion();

		if ( $latest_version == FALSE ) {
			return 1;
		} elseif ( version_compare( $current_version, $latest_version, '>=') == TRUE ) {
			return 0;
		}

		return 2;
	}

	/**
	 * @param bool $post_install_requirements_only
	 * @param bool $exclude_check
	 * @return int
	 */
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
			$retarr[$this->checkSafeCacheDirectory()]++;
			if ( !is_array( $exclude_check ) OR ( is_array($exclude_check) AND in_array('clean_cache', $exclude_check) == FALSE ) ) {
				$retarr[$this->checkCleanCacheDirectory()]++;
			}
			$retarr[$this->checkWritableStorageDirectory()]++;
			$retarr[$this->checkSafeStorageDirectory()]++;
			$retarr[$this->checkWritableLogDirectory()]++;
			$retarr[$this->checkSafeLogDirectory()]++;
			if ( !is_array( $exclude_check ) OR ( is_array($exclude_check) AND in_array('file_permissions', $exclude_check) == FALSE ) ) {
				$retarr[$this->checkFilePermissions()]++;
			}
			if ( !is_array( $exclude_check ) OR ( is_array($exclude_check) AND in_array('file_checksums', $exclude_check) == FALSE ) ) {
				$retarr[$this->checkFileChecksums()]++;
			}
		}

		$retarr[$this->checkPHPSafeMode()]++;
		$retarr[$this->checkPHPDisabledFunctions()]++;
		$retarr[$this->checkPHPAllowURLFopen()]++;
		$retarr[$this->checkPHPMemoryLimit()]++;
		$retarr[$this->checkPHPMagicQuotesGPC()]++;

		if ( $this->getTTProductEdition() >= TT_PRODUCT_CORPORATE ) {
			$retarr[$this->checkPEARValidate()]++;
			//$retarr[$this->checkMCRYPT()]++;
		}

		//Debug::Arr($retarr, 'RetArr: ', __FILE__, __LINE__, __METHOD__, 9);

		if ( $retarr[1] > 0 ) {
			return 1;
		} elseif ( $retarr[2] > 0 ) {
			return 2;
		} else {
			return 0;
		}
	}

	/**
	 * @param bool $post_install_requirements_only
	 * @param bool $exclude_check
	 * @return array|bool
	 */
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
			if ( $fail_all == TRUE OR $this->checkSafeCacheDirectory() != 0 ) {
				$retarr[] = 'UnSafeCacheDir';
			}
			if ( is_array($exclude_check) AND in_array('clean_cache', $exclude_check) == FALSE ) {
				if ( $fail_all == TRUE OR $this->checkCleanCacheDirectory() != 0 ) {
					$retarr[] = 'CleanCacheDir';
				}
			}
			if ( $fail_all == TRUE OR $this->checkWritableStorageDirectory() != 0 ) {
				$retarr[] = 'WStorageDir';
			}
			if ( $fail_all == TRUE OR $this->checkSafeStorageDirectory() != 0 ) {
				$retarr[] = 'UnSafeStorageDir';
			}
			if ( $fail_all == TRUE OR $this->checkWritableLogDirectory() != 0 ) {
				$retarr[] = 'WLogDir';
			}
			if ( $fail_all == TRUE OR $this->checkSafeLogDirectory() != 0 ) {
				$retarr[] = 'UnSafeLogDir';
			}
			if ( is_array($exclude_check) AND in_array('file_permissions', $exclude_check) == FALSE ) {
				if ( $fail_all == TRUE OR $this->checkFilePermissions() != 0 ) {
					$retarr[] = 'WFilePermissions';
				}
			}
			if ( is_array($exclude_check) AND in_array('file_checksums', $exclude_check) == FALSE  ) {
				if ( $fail_all == TRUE OR $this->checkFileChecksums() != 0 ) {
					$retarr[] = 'WFileChecksums';
				}
			}
		}

		if ( $fail_all == TRUE OR $this->checkPHPSafeMode() != 0 ) {
			$retarr[] = 'PHPSafeMode';
		}
		if ( $fail_all == TRUE OR $this->checkPHPDisabledFunctions() != 0 ) {
			$retarr[] = 'PHPDisabledFunctions';
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

		if ( $fail_all == TRUE OR $this->getTTProductEdition() >= TT_PRODUCT_CORPORATE ) {
			if ( $fail_all == TRUE OR $this->checkPEARValidate() != 0 ) {
				$retarr[] = 'PEARVal';
			}

			//if ( $fail_all == TRUE OR $this->checkMCRYPT() != 0 ) {
			//	$retarr[] = 'MCRYPT';
			//}
		}

		if ( isset($retarr) ) {
			return $retarr;
		}

		return FALSE;
	}

	/**
	 * Used by InstallSchema_1100*
	 * @param $matches
	 * @return bool|int|string
	 */
	function regexConvertToUUIDNoHash( $matches ) {
		return $this->regexConvertToUUID($matches, FALSE);
	}

	/**
	 * @param $matches
	 * @param bool $include_hash
	 * @return bool|int|string
	 */
	function regexConvertToUUID( $matches, $include_hash = TRUE ) {
		$id = '';
		if ( isset( $matches[3] ) ) {
			if ( $include_hash == TRUE ) {
				$id = '#';
			}
			$id .= $matches[1] .':'. TTUUID::convertIntToUUID( $matches[3] );
			if ( isset( $matches[4] ) ) {
				$id .= $matches[4];
			}
			if ( $include_hash == TRUE ) {
				$id .= '#';
			}
		} else {
			$id = $matches[0];
		}
		return $id;
	}

	/**
	 * Used by InstallSchema_1100*
	 * takes a listfactory result set as first argument.
	 * @param $array
	 * @return array
	 */
	function convertArrayElementsToUUID( $array ) {
		if ( !is_array($array) ) {
			return $array;
		}

		$recombined_array = array();
		foreach( $array as $key => $item ) {
			if( is_numeric( $item ) ) {
				$recombined_array[$key] = TTUUID::convertIntToUUID( $item );
			} elseif ( is_array( $item ) ) {
				$recombined_array[$key] = $this->convertArrayElementsToUUID( $item );
			} else {
				$recombined_array[$key] = $item;
			}
		}

		return $recombined_array;
	}

	/**
	 * @param $columns_data
	 * @return array
	 */
	function processColumns( $columns_data) {
		$retval = array();
		if ( is_array( $columns_data ) ) {
			foreach ( $columns_data as $key => $value ) {
				$pattern = array('/^(\w+)(\-)([0-9]{1,10})(_\w+|)$/', '/^(PA|PR|PU|PY)()(\d+)$/','/^(custom_column)()(\d+)$/');

				$new_key = preg_replace_callback( $pattern, array($this, 'regexConvertToUUIDNoHash'), trim( $key ) );
				$new_value = preg_replace_callback( $pattern, array($this, 'regexConvertToUUIDNoHash'), $value );
				if ( $new_key !== FALSE AND $new_value !== FALSE ) {
					$retval[$new_key] = $new_value;
				} elseif( $new_key !== FALSE AND $new_value == FALSE ) {
					$retval[$key] = $value;
				} elseif( $new_key == FALSE AND $new_value !== FALSE ) {
					$retval[$key] = $new_value;
				} else {
					$retval[$key] = $value;
				}

			}
		}
		return $retval;
	}
}
?>
