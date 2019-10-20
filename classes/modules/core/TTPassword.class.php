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
class TTPassword {
	static protected $latest_password_version = 3;

	/**
	 * @return int
	 */
	static function getLatestPasswordVersion() {
		return self::$latest_password_version;
	}

	/**
	 * @return string
	 */
	static function getPasswordSalt() {
		global $config_vars;

		if ( isset($config_vars['other']['salt']) AND $config_vars['other']['salt'] != '' ) {
			$retval = $config_vars['other']['salt'];
		} else {
			$retval = 'ttsalt03198238';
		}

		return trim($retval);
	}

	/**
	 * @param int $length
	 * @return bool|string
	 */
	static function generateRandomPassword( $length = 12 ) {
		//$password = substr( sha1( uniqid( self::getPasswordSalt(), TRUE ) ), 0, $length ); //12 digit random password. -- This would generate weak passwords in some cases.

		$alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890.?!@';
		$alphaLength = strlen( $alphabet ) - 1; //put the length -1 in cache

		$pass = array(); //remember to declare $pass as an array
		for ( $i = 0; $i < $length; $i++ ) {
			$n = rand( 0, $alphaLength );

			$random_char = $alphabet[ $n ];
			if ( !in_array( $random_char, $pass) ) { //Prevent duplicate characters from being in the password.
				$pass[] = $random_char;
			} else {
				$i--; //Retry for a new character.
			}
		}

		return implode( $pass ); //turn the array into a string

		return $password;
	}

	/**
	 * @param bool $encrypted_password
	 * @return int|mixed
	 */
	static function getPasswordVersion( $encrypted_password ) {
		$split_password = explode(':', $encrypted_password );
		if ( is_array($split_password) AND count($split_password) == 2 ) {
			$version = $split_password[0];
		} else {
			$version = 1;
		}

		return $version;
	}

	/**
	 * @param $password
	 * @param null $id1
	 * @param null $id2
	 * @param int $version
	 * @return string
	 */
	static function encryptPassword( $password, $id1 = NULL, $id2 = NULL, $version = NULL ) {
		//Always default to latest password version.
		if ( $version == '' ) {
			$version = self::$latest_password_version;
		}

		$password = trim($password);

		//Handle password migration/versioning
		switch( (int)$version ) {
			case 2: //v2
				//Case sensitive, uses sha512 and company/user specific salt.
				//Prepend with password version.
				//
				//IMPORTANT: When creating a new user, the ID must be defined before this is called, otherwise the hash is incorrect.
				//           This manifests itself as an incorrect password when its first created, but can be changed and then starts working.
				//
				//NOTE: After upgrade to UUIDs, we must convert UUIDs to integers for v2 hashes to work. All new v3+ hashes will use UUIDs instead.
				$encrypted_password = '2:'. hash( 'sha512', self::getPasswordSalt() . TTUUID::convertUUIDtoInt( $id1 ) . TTUUID::convertUUIDToInt( $id2 ) . $password );
				break;
			case 3: //v3 that uses UUIDs
				$encrypted_password = '3:'. hash( 'sha512', self::getPasswordSalt() . TTUUID::castUUID( $id1 ) . TTUUID::castUUID( $id2 ) . $password );
				break;
			default: //v1
				//Case insensitive, uses sha1 and global salt.
				$encrypted_password = sha1( self::getPasswordSalt() . strtolower($password) );
				break;
		}
		unset($password);

		return $encrypted_password;
	}

	/**
	 * @param $user_entered_password
	 * @param $database_password
	 * @return bool
	 */
	static function checkPassword( $user_entered_password, $database_password ) {
		if ( $user_entered_password === $database_password ) {
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * @param $password
	 * @return int
	 */
	static function getPasswordStrength( $password ) {
		if ( strlen( $password ) == 0 ) {
			return 1;
		}

		$strength = 0;

		//get the length of the password
		$length = strlen($password);

		//check if password is not all lower case
		if ( strtolower($password) != $password ) {
			$strength++;
		}

		//check if password is not all upper case
		if ( strtoupper($password) != $password ) {
			$strength++;
		}

		//check string length is 6-9 chars
		if ( $length >= 6 AND $length <= 9 ) {
			$strength++;
		}

		//check if length is 10-15 chars
		if ( $length >= 10 AND $length <= 15 ) {
			$strength += 2;
		}

		//check if length greater than 15 chars
		if ( $length > 15 ) {
			$strength += 3;
		}

		$duplicate_chars = 1;
		$consecutive_chars = 1;
		$char_arr = str_split( strtolower($password) );
		$prev_char_int = ord($char_arr[0]);
		foreach( $char_arr as $char ) {
			$curr_char_int = ord($char);
			$char_int_diff = abs($prev_char_int - $curr_char_int);
			if ( $char_int_diff == 0 ) { //Duplicate
				$duplicate_chars++;
			} elseif ( $char_int_diff == 1 OR $char_int_diff == -1 ) { //Consecutive
				$consecutive_chars++;
			}
			$prev_char_int = $curr_char_int;
		}
		$duplicate_percent = ( ( $duplicate_chars / strlen($password) ) * 100 );
		$consecutive_percent = ( ( $consecutive_chars / strlen($password) ) * 100 );
		if ( $duplicate_percent <= 25 ) {
			$strength++;
		}
		if ( $consecutive_percent <= 25 ) {
			$strength++;
		}

		//get the numbers in the password
		preg_match_all('/[0-9]/', $password, $numbers);
		//Prevent the addition of a single number to the beginning/end of the password from increasing the strength.
		if ( is_numeric( substr( $password, 0, 1) ) == TRUE ) {
			array_pop( $numbers[0] );
		}
		if ( is_numeric( substr( $password, -1, 1) ) == TRUE ) {
			array_pop( $numbers[0] );
		}
		$strength += ( count( $numbers[0] ) * 2 );

		//check for special chars
		preg_match_all('/[|!@#$%&*\/=?,;.:\-_+~^\\\]/', $password, $specialchars);
		$strength += ( count($specialchars[0]) * 3 );

		//get the number of unique chars
		$chars = str_split($password);
		$num_unique_chars = count( array_unique($chars) );
		$unique_percent = ( ( $num_unique_chars / strlen($password) ) * 100 );

		$strength += ( $num_unique_chars * 2 );


		//If the password consists of duplicate or consecutive chars, make it the lowest strength.
		//This should help prevent 12345, or abcde passwords.
		if ( $unique_percent <= 20 ) {
			$strength = 1;
		}
		if ( $duplicate_percent >= 50 ) {
			$strength = 1;
		}
		if ( $consecutive_percent >= 60 ) {
			$strength = 1;
		}
		Debug::Text('Duplicate: Chars: '. $duplicate_chars .' Percent: '. $duplicate_percent  .' Consec: Chars: '. $consecutive_chars .' Percent: '. $consecutive_percent .' Unique: Chars: '. $num_unique_chars .' Percent: '. $unique_percent, __FILE__, __LINE__, __METHOD__, 10);

		//Check for dictionary word, if its just a dictionary word make it the lowest strength.
		if ( function_exists( 'pspell_new' ) ) {
			//If no aspell dictionary is installed, you might see: WARNING(2): pspell_new(): PSPELL couldn't open the dictionary. reason: No word lists can be found for the language "en".
			//  On Centos this can fixed by: yum install aspell-en
			$pspell_config = @pspell_config_create( 'en' );
			$pspell_link = @pspell_new_config( $pspell_config );
			if ( $pspell_link != FALSE ) {
				if ( pspell_check( $pspell_link, $password ) !== FALSE ) {
					Debug::Text( 'Matches dictionary word exactly: ' . $password, __FILE__, __LINE__, __METHOD__, 10 );
					$strength = 1;
				}
				if ( pspell_check( $pspell_link, substr( $password, 1 ) ) !== FALSE ) {
					Debug::Text( 'Matches dictionary word after 1st char is dropped: ' . $password, __FILE__, __LINE__, __METHOD__, 10 );
					$strength = 1;
				}
				if ( pspell_check( $pspell_link, substr( $password, 0, -1 ) ) !== FALSE ) {
					Debug::Text( 'Matches dictionary word after last char is dropped: ' . $password, __FILE__, __LINE__, __METHOD__, 10 );
					$strength = 1;
				}
				if ( pspell_check( $pspell_link, substr( substr( $password, 1 ), 0, -1 ) ) !== FALSE ) {
					Debug::Text( 'Matches dictionary word after first and last char is dropped: ' . $password, __FILE__, __LINE__, __METHOD__, 10 );
					$strength = 1;
				}
			} else {
				Debug::Text('WARNING: pspell extension is installed but not functioning, is a dictionary installed?', __FILE__, __LINE__, __METHOD__, 10);
			}
		} else {
			Debug::Text('WARNING: pspell extension is not enabled...', __FILE__, __LINE__, __METHOD__, 10);
		}

		//strength is a number 1-10;
		$strength = $strength > 99 ? 99 : $strength;
		$strength = floor( ( ( $strength / 10 ) + 1 ) );

		Debug::Text('Strength: '. $strength, __FILE__, __LINE__, __METHOD__, 10);
		return $strength;
	}
}
?>
