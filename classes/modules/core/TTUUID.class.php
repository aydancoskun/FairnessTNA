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
 * @package Core
 */
class TTUUID {
	protected static $uuid_counter = 1;

	/**
	 * @return int|string
	 */
	static function getZeroID() {
		global $PRIMARY_KEY_IS_UUID;
		if ( $PRIMARY_KEY_IS_UUID == FALSE ) {
			return (int)0;
		}

		return '00000000-0000-0000-0000-000000000000';
	}

	/**
	 * @param null $int
	 * @return int|string
	 */
	static function getNotExistID( $int = NULL ) {
		global $PRIMARY_KEY_IS_UUID;
		if ( $PRIMARY_KEY_IS_UUID == FALSE ) {
			return (int)-1;
		}

		if ( is_numeric( $int ) ) {

			return 'ffffffff-ffff-ffff-ffff-'. str_pad( substr( abs( $int ), 0 , 12 ), 12, 0, STR_PAD_LEFT );
		} else {
			return 'ffffffff-ffff-ffff-ffff-ffffffffffff';
		}

	}

	/**
	 * @param null $seed
	 * @return string
	 */
	static function generateUUID( $seed = NULL ) {
		if ( $seed == NULL OR strlen( $seed ) !== 12 ) {
			$seed = self::getSeed( TRUE );
		}

		/**
		 * Get time since Gregorian calendar reform in 100ns intervals
		 * This is exceedingly difficult because of PHP's (and pack()'s)
		 * integer size limits.
		 * Note that this will never be more accurate than to the microsecond.
		 */

		//On 32bit PHP installs, the microtime() resolution isn't high enough (only 1/64 of a second) and can cause many UUID duplicates in tight loops. Suppliment the timer with a counter instead.
		if ( PHP_INT_SIZE === 4 ) { //32bit
			$time = ( microtime( TRUE ) + ( self::$uuid_counter / 100000 ) ) * 10000000 + 0x01b21dd213814000;
			self::$uuid_counter++;
		} else { //64bit
			$time = microtime( TRUE ) * 10000000 + 0x01b21dd213814000;
		}

		// Convert time to a string representation without any decimal places, and not scientific notation. Using sprintf is slightly faster than substr( $time, 0, strpos( $time, '.' ) )
		$time = sprintf( '%.0F', $time );

		// And now to a 64-bit binary representation
		$time = pack( 'H*', str_pad( base_convert( $time, 10, 16 ), 16, '0', STR_PAD_LEFT ) );

		// Reorder bytes to their proper locations in the UUID. Append random clock sequence to end.
		$uuid = $time[4] . $time[5] . $time[6] . $time[7] . $time[2] . $time[3] . $time[0] . $time[1] . openssl_random_pseudo_bytes( 2 );

		// set variant
		$uuid[8] = chr( ord( $uuid[8] ) & 63 | 128 );
		// set version
		$uuid[6] = chr( ord( $uuid[6] ) & 63 | 16 );

		$uuid = bin2hex( $uuid );

		//create an ORDERED UUID https://www.percona.com/blog/2014/12/19/store-uuid-optimized-way/
		$uuid = substr( $uuid, 12, 4 ) . substr( $uuid, 8, 4 ) . '-' . substr( $uuid, 0, 4 ) . '-' . substr( $uuid, 4, 4 ) . '-' . substr( $uuid, 16, 4 ) . '-' . $seed;

		return $uuid;
	}

	/**
	 * @param string $uuid UUID
	 * @param bool $allow_null
	 * @return int|string
	 */
	static function castUUID( $uuid, $allow_null = FALSE ) {
		//@see comment in isUUID

		//During upgrade from V10.x (pre-UUID) to v11 (post-UUID), we need numeric IDs to be left as integers to avoid SQL errors.
		global $PRIMARY_KEY_IS_UUID;
		if ( $PRIMARY_KEY_IS_UUID == FALSE ) {
			return (int)$uuid;
		}

		//Allow NULLs for cases where the column allows it.
		$uuid = ( is_string( $uuid ) ) ? trim( $uuid ) : $uuid;
		if ( ( $uuid === NULL AND $allow_null == TRUE ) OR self::isUUID( $uuid ) == TRUE ) {
			return $uuid;
		}

		return self::getZeroID();
	}

	/**
	 * @param bool $exact_string
	 * @return string
	 */
	static function getRegex( $exact_string = TRUE ) {
		$regex = '[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}';
		if ( $exact_string === TRUE ) {
			return '/^'.$regex.'$/';
		} else {
			return '/'.$regex.'/';
		}
	}

	/**
	 * @param string $uuid UUID
	 * @return bool
	 */
	static function isUUID( $uuid ) {
		//Must be strict enough to enfore PostgreSQL UUID storage standard (all lower, no '{}' must have dashes)
		//if we do not enforce this here, MySQL can insert whatever format they want and break '$a->getID() == $b->getID()' comparisons.

		//During upgrade from V10.x (pre-UUID) to v11 (post-UUID), we need numeric IDs to be left as integers to avoid SQL errors.
		global $PRIMARY_KEY_IS_UUID;
		if ( $PRIMARY_KEY_IS_UUID == FALSE AND is_numeric($uuid) ) {
			return $uuid;
		}

		if ( is_string( $uuid ) AND $uuid != '' AND preg_match( self::getRegex(), $uuid ) ) {
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * @param $uuid
	 * @param int $group
	 * @return bool
	 */
	static function getUUIDGroup( $uuid, $group = 4 )  {
		$bits = explode('-', $uuid);
		if ( isset($bits[$group]) ) {
			return $bits[$group];
		}

		return FALSE;
	}

	/**
	 * @param string $uuid UUID
	 * @return int
	 */
	static function convertUUIDtoInt( $uuid ) {
		$bits = explode('-', $uuid);
		return (int)$bits[( count($bits) - 1 )];
	}

	/**
	 * @param $int
	 * @return int|string
	 */
	static function convertIntToUUID( $int ) {
		if ( is_numeric($int) ) {
			if ( $int === 0 ) {
				return self::getZeroID();
			} elseif ( $int === -1 ) {
				return self::getNotExistID();
			}

			return self::getConversionPrefix() .'-'. str_pad( $int, 12, '0', STR_PAD_LEFT );
		} else {
			return $int;
		}
	}

	/**
	 * @param $str string
	 * @return string
	 */
	static function convertStringToUUID( $str ) {
		$retval = substr($str, 0, 8 ) .'-'. substr($str, 8, 4) .'-'. substr($str, 12, 4) .'-'. substr($str, 16, 4) .'-'. substr($str, 20);

		return $retval;
	}

	/**
	 * @param bool $fail_to_random
	 * @return bool|string
	 */
	static function getSeed( $fail_to_random = FALSE ) {
		global $config_vars;
		if ( isset( $config_vars['other']['uuid_seed'] ) AND strlen($config_vars['other']['uuid_seed']) == 12 AND preg_match('/^[a-z0-9]{12}$/', $config_vars['other']['uuid_seed']) ) {
			return strtolower( trim( $config_vars['other']['uuid_seed'] ) );
		}

		if ( $fail_to_random == TRUE ) {
			Debug::text('  WARNING: Generating random seed!', __FILE__, __LINE__, __METHOD__, 9);
			return self::generateRandomSeed();
		}

		return FALSE;
	}

	/**
	 * @return string
	 */
	static function generateRandomSeed() {
		return bin2hex( openssl_random_pseudo_bytes(6) );
	}

	/**
	 * @return bool|string
	 */
	static function generateSeed() {
		//Once the seed is generated, it must not be ever generated to something different. Especially if the upgrade failed half way through and is run later on, or even on a different server.
		global $config_vars;
		if ( isset( $config_vars['other']['uuid_seed'] ) AND strlen($config_vars['other']['uuid_seed']) == 12 AND preg_match('/^[a-z0-9]{12}$/', $config_vars['other']['uuid_seed']) ) {
			return strtolower( trim( $config_vars['other']['uuid_seed'] ) );
		} else {
			global $db;

			//Make sure we check that the database/system_setting table exists before we attempt to use it. Otherwise it may fail on initial installation.
			$install_obj = new Install();
			$install_obj->setDatabaseConnection( $db ); //Default connection
			if ( $install_obj->checkSystemSettingTableExists() == TRUE ) {
				$registration_key = SystemSettingFactory::getSystemSettingValueByKey( 'registration_key' );
			} else {
				Debug::text('Database or system_setting table does not exist yet, generating temporary registration key...', __FILE__, __LINE__, __METHOD__, 9);
				$registration_key = md5( uniqid( NULL, TRUE ) );
			}

			//Make sure the UUID key used for upgrading is as unique as possible, so we can avoid the chance of conflicts as best as possible.
			//  Include the database type and database name to further help make this unique in the event that a database was copied on the same server (hardware_id), it should at least have a different name.
			//  Be sure to use CONFIG_FILE file creation time rather than mtime as the config file gets changed during upgrade/installs and can cause the seed to then change.
			//  Seed should only be exactly 12 characters
			$uuid_seed = substr( sha1( $registration_key . 'hardware_id' . $db->databaseType . $db->database . filectime( CONFIG_FILE ) ), 0, 12 );
			$config_vars['other']['uuid_seed'] = $uuid_seed; //Save UUID_SEED to any in memory $config_vars to its able to be used immediately.

			Debug::text( '  Generated Seed: ' . $uuid_seed . ' From Registration Key: ' . $registration_key . ' Hardware ID: ' . 'hardware_id' . ' Database Type: ' . $db->databaseType . ' DB Name: ' . $db->database . ' Config File: ' . CONFIG_FILE . ' ctime: ' . filectime( CONFIG_FILE ), __FILE__, __LINE__, __METHOD__, 9 );

			$tmp_config_data = array();
			$tmp_config_data['other']['uuid_seed'] = $uuid_seed;
			if ( isset($config_vars['other']['primary_company_id']) AND is_numeric( $config_vars['other']['primary_company_id'] ) ) { //Convert to UUID while we are at it.
				$uuid_primary_company_id = TTUUID::convertIntToUUID( $config_vars['other']['primary_company_id'] );
				$config_vars['other']['primary_company_id'] = $uuid_primary_company_id; //Save UUID primary_company_id to any in memory $config_vars to its able to be used immediately.

				$tmp_config_data['other']['primary_company_id'] = $uuid_primary_company_id;
			}

			if ( $install_obj->writeConfigFile( $tmp_config_data ) !== TRUE ) {
				return FALSE;
			}

			return $uuid_seed;
		}

		return FALSE;
	}

	/**
	 * @return bool|string
	 */
	static function getConversionPrefix() {
		$uuid_seed = self::generateSeed();
		if ( $uuid_seed !== FALSE ) {
			$uuid_key = $uuid_seed . substr( sha1( $uuid_seed ), 12 ); //Make sure we sha1() the seed just to pad out to at least 24 characters. Make the first 12 characters the original seed for consistency though.

			$uuid_prefix = substr( $uuid_key, 0, 8 ) . '-' . substr( $uuid_key, 8, 2 ) . substr( substr( $uuid_key, -10 ), 0, 2 ) . '-' . substr( substr( $uuid_key, -8 ), 0, 4 ) . '-' . substr( $uuid_key, -4 );
			//Debug::text( 'UUID Key: ' . $uuid_key . ' UUID PREFIX: ' . $uuid_prefix, __FILE__, __LINE__, __METHOD__, 9 );

			return $uuid_prefix;
		}

		return FALSE;
	}

	/**
	 * @param $uuid
	 * @param $length
	 * @param bool $include_dashes
	 * @return string
	 */
	static function truncateUUID( $uuid, $length, $include_dashes = TRUE ) {
		//Re-arrange UUID so most unique data is at the beginning.
		if ( is_numeric( self::getUUIDGroup( $uuid, 4 ) ) AND stripos( self::getSeed( FALSE ), self::getUUIDGroup( $uuid, 0 ) ) !== FALSE ) {
			//If its a legacy UUID converted from an INT, the only unique part is group 4, so it needs to be at the begining.
			//  However in cases where the SEED changes in the .ini file for some reason, this won't work anymore. Alternatively we could maybe just check that the first two digits of group 4 are '00' as well as being numeric. The chances of that happening are quite rare, but still possible.
			$tmp_uuid = self::getUUIDGroup( $uuid, 4 ) . '-' . self::getUUIDGroup( $uuid, 1 ) . '-' . self::getUUIDGroup( $uuid, 2 ) . '-' . self::getUUIDGroup( $uuid, 3 ) . '-' . self::getUUIDGroup( $uuid, 0 );
		} else {
			$tmp_uuid = self::getUUIDGroup( $uuid, 1 ) . '-' . self::getUUIDGroup( $uuid, 2 ) . '-' . self::getUUIDGroup( $uuid, 3 ) . '-' . self::getUUIDGroup( $uuid, 0 ) . '-' . self::getUUIDGroup( $uuid, 4 );
		}

		if ( $include_dashes == FALSE ) {
			$tmp_uuid = str_replace('-', '', $tmp_uuid);
		}

		return trim( substr( $tmp_uuid, 0, $length ), '-' );
	}
}
?>
