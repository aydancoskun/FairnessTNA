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
class Validator {
	private $num_errors = 0; //Number of errors.
	private $num_warnings = 0; //Number of errors.
	private $errors = array(); //Array of errors.
	private $warnings = array(); //Array of errors.
	private $verbosity = 8;

	public $validate_only = FALSE;

	/**
	 * Checks a result set for one or more rows.
	 * @param $label
	 * @param $rs
	 * @param null $msg
	 * @return bool
	 */
	function isResultSetWithRows( $label, $rs, $msg = NULL) {
		//Debug::Arr($rs, 'ResultSet: ', __FILE__, __LINE__, __METHOD__, $this->verbosity);

		if ( is_object($rs) ) {
			if ( isset($rs->rs) AND is_object($rs->rs) AND isset($rs->rs->_numOfRows) AND $rs->rs->_numOfRows > 0 ) {
				return TRUE;
			}
		}

		$this->Error($label, $msg);

		return FALSE;
	}

	/**
	 * @param $label
	 * @param $rs
	 * @param null $msg
	 * @return bool
	 */
	function isNotResultSetWithRows( $label, $rs, $msg = NULL) {
		//Debug::Arr($rs, 'ResultSet: ', __FILE__, __LINE__, __METHOD__, $this->verbosity);

		if ( is_object($rs) ) {
			if ( isset($rs->rs) AND is_object($rs->rs) AND isset($rs->rs->_numOfRows) AND $rs->rs->_numOfRows > 0 ) {
				$this->Error($label, $msg);
				return FALSE;
			}
			//foreach($rs as $result) {
			//	$this->Error($label, $msg);
			//	unset($result); // code standards
			//	return FALSE;
			//}
		}

		return TRUE;
	}

	/**
	 * Function to simple set an error.
	 * @param $label
	 * @param $value
	 * @param null $msg
	 * @return bool
	 */
	function isTrue( $label, $value, $msg = NULL) {
		if ($value == TRUE) {
			return TRUE;
		}

		$this->Error($label, $msg, (int)$value );

		return FALSE;
	}

	/**
	 * @param $label
	 * @param $value
	 * @param null $msg
	 * @return bool
	 */
	function isFalse( $label, $value, $msg = NULL) {
		if ($value == FALSE) {
			return TRUE;
		}

		$this->Error($label, $msg, (int)$value );

		return FALSE;
	}

	/**
	 * @param $label
	 * @param $value
	 * @param null $msg
	 * @return bool
	 */
	function isNull( $label, $value, $msg = NULL) {
		//Debug::text('Value: '. $value, __FILE__, __LINE__, __METHOD__, $this->verbosity);

		if ($value == NULL ) {
			return TRUE;
		}

		$this->Error($label, $msg, (int)$value );

		return FALSE;
	}

	/**
	 * @param $label
	 * @param $value
	 * @param null $msg
	 * @return bool
	 */
	function isNotNull( $label, $value, $msg = NULL) {
		//Debug::text('Value: '. $value, __FILE__, __LINE__, __METHOD__, $this->verbosity);

		if ( $value != NULL ) { //== NULL matches on (float)0.0
			return TRUE;
		}

		$this->Error($label, $msg, $value );

		return FALSE;
	}

	/**
	 * @param $label
	 * @param $value
	 * @param null $msg
	 * @param $array
	 * @return bool
	 */
	function inArrayValue( $label, $value, $msg = NULL, $array) {
		//Debug::text('Value: '. $value, __FILE__, __LINE__, __METHOD__, $this->verbosity);

		if (is_array($array) AND in_array($value, array_values( $array ) ) ) {
			return TRUE;
		}

		$this->Error($label, $msg, $value );

		return FALSE;
	}

	/**
	 * @param $label
	 * @param $key
	 * @param null $msg
	 * @param $array
	 * @return bool
	 */
	function inArrayKey( $label, $key, $msg = NULL, $array) {
		//Debug::text('Key: '. $key, __FILE__, __LINE__, __METHOD__, $this->verbosity);
		//Debug::Arr($array, 'isArrayKey Array:', __FILE__, __LINE__, __METHOD__, $this->verbosity);
		if (is_array($array) AND in_array($key, array_keys( $array ) ) ) {
			return TRUE;
		}

		$this->Error($label, $msg, $key );

		return FALSE;
	}

	/**
	 * @param $label
	 * @param $value
	 * @param null $msg
	 * @return bool
	 */
	function isDigits( $label, $value, $msg = NULL) {
		//Debug::Text('Value:'. $value, __FILE__, __LINE__, __METHOD__, $this->verbosity);

		if ( ctype_digit( trim($value) ) == TRUE ) { //Must be *only* digits.
			return TRUE;
		}

		$this->Error($label, $msg, $value );

		return FALSE;
	}

	/**
	 * @param $label
	 * @param $value
	 * @param null $msg
	 * @return bool
	 */
	function isNumeric( $label, $value, $msg = NULL) {
		//Debug::Text('Value:'. $value, __FILE__, __LINE__, __METHOD__, $this->verbosity);

		//if ( preg_match('/^[-0-9]+$/', $value) ) {
		if ( is_numeric( $value ) == TRUE ) {
			return TRUE;
		}

		$this->Error($label, $msg, $value );

		return FALSE;
	}

	/**
	 * @param $label
	 * @param $value
	 * @param null $msg
	 * @return bool
	 */
	function isUUID( $label, $value, $msg = NULL) {
		//Debug::Text('Value:'. $value, __FILE__, __LINE__, __METHOD__, $this->verbosity);

		//Benchmarking proved that this method is faster than ctype_alnum()
		//this regex is duplicated into Factory::setID()
		if ( TTUUID::isUUID($value) == TRUE ) {
			return TRUE;
		}

		$this->Error($label, $msg, $value );

		return FALSE;
	}

	/**
	 * @param $label
	 * @param $value
	 * @param null $msg
	 * @param null $max
	 * @return bool
	 */
	function isLessThan( $label, $value, $msg = NULL, $max = NULL ) {
		//Debug::Text('Value:'. $value, __FILE__, __LINE__, __METHOD__, $this->verbosity);

		if ( $max === NULL OR $max === '' ) {
			$max = PHP_INT_MAX;
		}

		if ( $value <= $max ) {
			return TRUE;
		}

		$this->Error($label, $msg, $value );

		return FALSE;
	}

	/**
	 * @param $label
	 * @param $value
	 * @param null $msg
	 * @param null $min
	 * @return bool
	 */
	function isGreaterThan( $label, $value, $msg = NULL, $min = NULL ) {
		//Debug::Text('Value:'. $value, __FILE__, __LINE__, __METHOD__, $this->verbosity);

		if ( $min === NULL OR $min === '' ) {
			$min = ( -1 * PHP_INT_MAX );
		}

		if ( $value >= $min ) {
			return TRUE;
		}

		$this->Error($label, $msg, $value );

		return FALSE;
	}


	/**
	 * @param $label
	 * @param $value
	 * @param null $msg
	 * @return bool
	 */
	function isFloat( $label, $value, $msg = NULL) {
		//Debug::Text('Value:'. $value, __FILE__, __LINE__, __METHOD__, $this->verbosity);

		//Don't use TTi18n::parseFloat() here, as if we are going to be doing that we should do it as early as possible to the user input, like in setObjectFromArray()
		//  We do need to check if the value passed in is already cast to float/int and just accept it in that case.
		//    Because in other locales preg_match() casts $value to a string, which means decimal could become a comma, then it won't match.
		//    Allow commas and decimals to be accepted as floats, as parseFloat() should almost always be called after this function, and it accepts both in all locales.
		if ( ( is_float( $value ) == TRUE OR is_int( $value ) === TRUE ) OR preg_match('/^(([\.,][0-9]+)|([-0-9]+([\.,\ 0-9]*)?))$/', trim( $value ) ) === 1 ) {
			return TRUE;
		}

		$this->Error($label, $msg, $value );

		return FALSE;
	}

	/**
	 * @param $label
	 * @param $value
	 * @param $msg
	 * @param $regex
	 * @return bool
	 */
	function isRegEx( $label, $value, $msg, $regex) {
		//Debug::text('Value: '. $value .' RegEx: '. $regex, __FILE__, __LINE__, __METHOD__, $this->verbosity);
		if ( preg_match($regex, $value) ) {
			return TRUE;
		}

		$this->Error($label, $msg, $value );

		return FALSE;
	}

	/**
	 * @param $label
	 * @param $value
	 * @param $msg
	 * @param $regex
	 * @return bool
	 */
	function isNotRegEx( $label, $value, $msg, $regex) {
		//Debug::text('Value: '. $value .' RegEx: '. $regex, __FILE__, __LINE__, __METHOD__, $this->verbosity);
		if ( preg_match($regex, $value) == FALSE ) {
			return TRUE;
		}

		$this->Error($label, $msg, $value );

		return FALSE;
	}

	/**
	 * @param $label
	 * @param $value
	 * @param null $msg
	 * @param int $min
	 * @param int $max
	 * @return bool
	 */
	function isLength( $label, $value, $msg = NULL, $min = 1, $max = 255) {
		$len = strlen($value);

		//Debug::text('Value: '. $value .' Length: '. $len .' Min: '. $min .' Max: '. $max, __FILE__, __LINE__, __METHOD__, $this->verbosity);

		if ($len < $min OR $len > $max) {
			$this->Error($label, $msg, $value );

			return FALSE;
		}

		return TRUE;
	}

	/**
	 * @param $label
	 * @param $value
	 * @param null $msg
	 * @param int $min
	 * @param int $max
	 * @return bool
	 */
	function isLengthBeforeDecimal( $label, $value, $msg = NULL, $min = 1, $max = 255) {
		$len = strlen( Misc::getBeforeDecimal($value) );

		//Debug::text('Value: '. $value .' Length: '. $len .' Min: '. $min .' Max: '. $max, __FILE__, __LINE__, __METHOD__, $this->verbosity);

		if ($len < $min OR $len > $max) {
			$this->Error($label, $msg, $value );

			return FALSE;
		}

		return TRUE;
	}

	/**
	 * @param $label
	 * @param $value
	 * @param null $msg
	 * @param int $min
	 * @param int $max
	 * @return bool
	 */
	function isLengthAfterDecimal( $label, $value, $msg = NULL, $min = 1, $max = 255) {
		$len = strlen( Misc::getAfterDecimal($value, FALSE) );

		//Debug::text('Value: '. $value .' Length: '. $len .' Min: '. $min .' Max: '. $max, __FILE__, __LINE__, __METHOD__, $this->verbosity);

		if ($len < $min OR $len > $max) {
			$this->Error($label, $msg, $value );

			return FALSE;
		}

		return TRUE;
	}

	/**
	 * @param $label
	 * @param $value
	 * @param null $msg
	 * @return bool
	 */
	function isUniqueCharacters( $label, $value, $msg = NULL ) {
		//Check for unique characters and not consecutive characters.
		//This will fail on:
		// aaaaaaa
		// bbbbbbb
		// abc
		// xyz
		if ( strlen($value) > 2 ) {
			$char_arr = str_split( strtolower($value) );
			$prev_char_int = ord($char_arr[0]);
			foreach( $char_arr as $char ) {
				$curr_char_int = ord($char);
				if ( abs($prev_char_int - $curr_char_int) > 1 ) {
					return TRUE;
				}
				$prev_char_int = $curr_char_int;
			}

			$this->Error($label, $msg, $value );

			return FALSE;

		}

		return TRUE;
	}

	/**
	 * @param $label
	 * @param $value
	 * @param null $msg
	 * @param bool $max_duplicate_percent
	 * @param bool $consecutive_only
	 * @return bool
	 */
	function isDuplicateCharacters( $label, $value, $msg = NULL, $max_duplicate_percent = FALSE, $consecutive_only = FALSE ) {
		if ( strlen($value) > 2 AND $max_duplicate_percent != FALSE ) {
			$duplicate_chars = 0;

			$char_arr = str_split( strtolower($value) );
			$prev_char_int = ord($char_arr[0]);
			foreach( $char_arr as $char ) {
				$curr_char_int = ord($char);
				if ( abs($prev_char_int - $curr_char_int) > 1 ) {
					if ( $consecutive_only == TRUE ) {
						$duplicate_chars = 0; //Reset duplicate count.
					}

				} else {
					$duplicate_chars++;
				}
				$prev_char_int = $curr_char_int;
			}

			$duplicate_percent = ( ( $duplicate_chars / strlen($value) ) * 100 );
			Debug::text('Duplicate Chars: '. $duplicate_chars .' Percent: '. $duplicate_percent .' Max Percent: '. $max_duplicate_percent .' Consec: '. (int)$consecutive_only, __FILE__, __LINE__, __METHOD__, $this->verbosity);

			if ( $duplicate_percent < $max_duplicate_percent ) {
				return TRUE;
			}

			$this->Error($label, $msg, $value );

			return FALSE;
		}

		return TRUE;
	}

	/**
	 * @param $label
	 * @param $value
	 * @param null $msg
	 * @param $bad_words
	 * @return bool
	 */
	function isAllowedWords( $label, $value, $msg = NULL, $bad_words ) {
		$words = explode(' ', $value );
		if ( is_array($words) ) {
			foreach( $words as $word ) {
				foreach( $bad_words as $bad_word ) {
					if ( strtolower($word) == strtolower($bad_word) ) {
						$this->Error($label, $msg, $value );

						return FALSE;
					}
				}
			}
		}

		return TRUE;
	}

	/**
	 * @param $label
	 * @param $value
	 * @param null $msg
	 * @param $bad_words
	 * @return bool
	 */
	function isAllowedValues( $label, $value, $msg = NULL, $bad_words ) {
		foreach( $bad_words as $bad_word ) {
			if ( strtolower($value) == strtolower($bad_word) ) {
				$this->Error($label, $msg, $value );

				return FALSE;
			}
		}

		return TRUE;
	}

	/**
	 * @param $label
	 * @param $value
	 * @param null $msg
	 * @return bool
	 */
	function isPhoneNumber( $label, $value, $msg = NULL) {

		//Strip out all non-numeric characters.
		$phone = $this->stripNonNumeric($value);

		//Debug::text('Raw Phone: '. $value .' Phone: '. $phone, __FILE__, __LINE__, __METHOD__, $this->verbosity);

		if ( strlen($phone) >= 6 AND strlen($phone) <= 20 AND preg_match('/^[0-9\(\)\-\.\+\ ]{6,20}$/i', $value) ) {
			return TRUE;
		}

		$this->Error($label, $msg, $value );

		return FALSE;
	}

	/**
	 * @param $label
	 * @param $value
	 * @param null $msg
	 * @param null $country
	 * @param null $province
	 * @return bool
	 */
	function isPostalCode( $label, $value, $msg = NULL, $country = NULL, $province = NULL) {
		//Debug::text('Raw Postal Code: '. $value, __FILE__, __LINE__, __METHOD__, $this->verbosity);

		//Remove any spaces, keep dashes for US extended ZIP.
		$value = str_replace( array(' '), '', trim($value) );

		$province = strtolower( trim($province) );

		switch ( strtolower(trim($country)) ) {
			case 'us':
				//US zip code
				if ( preg_match('/^[0-9]{5}$/i', $value) OR preg_match('/^[0-9]{5}\-[0-9]{4}$/i', $value) ) {

					if ( $province != '' ) {
						$province_postal_code_map = array (
										'ak' => array ('9950099929'),
										'al' => array ('3500036999'),
										'ar' => array ('7160072999', '7550275505'),
										'az' => array ('8500086599'),
										'ca' => array ('9000096199'),
										'co' => array ('8000081699'),
										'ct' => array ('0600006999'),
										'dc' => array ('2000020099', '2020020599'),
										'de' => array ('1970019999'),
										'fl' => array ('3200033999', '3410034999'),
										'ga' => array ('3000031999'),
										'hi' => array ('9670096798', '9680096899'),
										'ia' => array ('5000052999'),
										'id' => array ('8320083899'),
										'il' => array ('6000062999'),
										'in' => array ('4600047999'),
										'ks' => array ('6600067999'),
										'ky' => array ('4000042799', '4527545275'),
										'la' => array ('7000071499', '7174971749'),
										'ma' => array ('0100002799'),
										'md' => array ('2033120331', '2060021999'),
										'me' => array ('0380103801', '0380403804', '0390004999'),
										'mi' => array ('4800049999'),
										'mn' => array ('5500056799'),
										'mo' => array ('6300065899'),
										'ms' => array ('3860039799'),
										'mt' => array ('5900059999'),
										'nc' => array ('2700028999'),
										'nd' => array ('5800058899'),
										'ne' => array ('6800069399'),
										'nh' => array ('0300003803', '0380903899'),
										'nj' => array ('0700008999'),
										'nm' => array ('8700088499'),
										'nv' => array ('8900089899'),
										'ny' => array ('0040000599', '0639006390', '0900014999'),
										'oh' => array ('4300045999'),
										'ok' => array ('7300073199', '7340074999'),
										'or' => array ('9700097999'),
										'pa' => array ('1500019699'),
										'ri' => array ('0280002999', '0637906379'),
										'sc' => array ('2900029999'),
										'sd' => array ('5700057799'),
										'tn' => array ('3700038599', '7239572395'),
										'tx' => array ('7330073399', '7394973949', '7500079999', '8850188599'),
										'ut' => array ('8400084799'),
										'va' => array ('2010520199', '2030120301', '2037020370', '2200024699'),
										'vt' => array ('0500005999'),
										'wa' => array ('9800099499'),
										'wi' => array ('4993649936', '5300054999'),
										'wv' => array ('2470026899'),
										'wy' => array ('8200083199')
									);

						if ( isset($province_postal_code_map[$province]) ) {
							$zip5 = substr($value, 0, 5);
							//Debug::text('Checking ZIP code range, short zip: '. $zip5, __FILE__, __LINE__, __METHOD__, $this->verbosity);
							foreach( $province_postal_code_map[$province] as $postal_code_range ) {
								//Debug::text('Checking ZIP code range: '. $postal_code_range, __FILE__, __LINE__, __METHOD__, $this->verbosity);
								if ( ( $zip5 >= substr($postal_code_range, 0, 5) ) AND ( $zip5 <= substr( $postal_code_range, 5 ) ) ) {
									return TRUE;
								}
							}
						} // else { //Debug::text('Postal Code does not match province!', __FILE__, __LINE__, __METHOD__, $this->verbosity);
					} else {
						return TRUE;
					}
				}
				break;
			case 'ca':
				//Canada postal code
				if ( preg_match('/^[a-zA-Z]{1}[0-9]{1}[a-zA-Z]{1}[-]?[0-9]{1}[a-zA-Z]{1}[0-9]{1}$/i', $value) ) {
					if ( $province != '' ) {
						//Debug::text('Verifying postal code against province!', __FILE__, __LINE__, __METHOD__, $this->verbosity);
						$province_postal_code_map = array(
												'ab' => array('t'),
												'bc' => array('v'),
												'sk' => array('s'),
												'mb' => array('r'),
												'qc' => array('g', 'h', 'j'),
												'on' => array('k', 'l', 'm', 'n', 'p'),
												'nl' => array('a'),
												'nb' => array('e'),
												'ns' => array('b'),
												'pe' => array('c'),
												'nt' => array('x'),
												'yt' => array('y'),
												'nu' => array('x')
											);

						//Debug::Arr($province_postal_code_map[$province], 'Valid Postal Codes for Province', __FILE__, __LINE__, __METHOD__, $this->verbosity);
						if ( isset($province_postal_code_map[$province]) AND in_array( substr( strtolower($value), 0, 1), $province_postal_code_map[$province] ) )	{
							return TRUE;
						} // else { //Debug::text('Postal Code does not match province!', __FILE__, __LINE__, __METHOD__, $this->verbosity);
					} else {
						return TRUE;
					}
				}
				break;
			default:
				//US
				if ( preg_match('/^[0-9]{5}$/i', $value) OR preg_match('/^[0-9]{5}\-[0-9]{4}$/i', $value) ) {
					return TRUE;
				}

				//CA
				if ( preg_match('/^[a-zA-Z]{1}[0-9]{1}[a-zA-Z]{1}[-]?[0-9]{1}[a-zA-Z]{1}[0-9]{1}$/i', $value) ) {
					return TRUE;
				}

				//Other
				if ( preg_match('/^[a-zA-Z0-9]{1,10}$/i', $value) ) {
					return TRUE;
				}

				break;
		}

		$this->Error($label, $msg, $value );

		return FALSE;
	}

	/**
	 * @param $label
	 * @param $value
	 * @param null $msg
	 * @return bool
	 */
	function isEmail( $label, $value, $msg = NULL) {
		//Debug::text('Raw Email: '. $value, __FILE__, __LINE__, __METHOD__, $this->verbosity);
		if ( function_exists('filter_var') AND filter_var($value, FILTER_VALIDATE_EMAIL) !== FALSE ) {
			return TRUE;
		} elseif ( preg_match('/^[\w\.\-\&\+]+\@[\w\.\-]+\.[a-z]{2,5}$/i', $value) ) { //This Email regex is no where near correct, use PHP filter_var instead. - Allow 5 char suffixes to support .local domains.
			return TRUE;
		}

		$this->Error($label, $msg, $value );

		return FALSE;
	}

	/**
	 * @param $label
	 * @param $value
	 * @param null $msg
	 * @param bool $error_level
	 * @return bool
	 */
	function isEmailAdvanced( $label, $value, $msg = NULL, $error_level = TRUE ) {
		//Debug::text('Raw Email: '. $value, __FILE__, __LINE__, __METHOD__, $this->verbosity);

		$retval = Misc::isEmail( $value, TRUE, $error_level, TRUE );
		if ( $retval === ISEMAIL_VALID ) {
			return TRUE;
		}

		if ( is_array($msg) ) {
			if ( isset($msg[$retval]) ) {
				$msg = $msg[$retval];
			} else {
				$msg = $msg[0];
			}
		}

		$this->Error($label, $msg, $value );

		return FALSE;
	}

	/**
	 * @param $label
	 * @param $value
	 * @param null $msg
	 * @return bool
	 */
	function isIPAddress( $label, $value, $msg = NULL) {
		//Debug::text('Raw IP: '. $value, __FILE__, __LINE__, __METHOD__, $this->verbosity);

		$ip = explode('.', $value);

		if( count($ip) == 4 ) {
			$valid = TRUE;

			foreach($ip as $block) {
				if( !is_numeric($block) OR $block >= 255 OR $block < 0) {
					$valid = FALSE;
				}
			}

			if ( $valid == TRUE ) {
				return TRUE;
			}
		}

		$this->Error($label, $msg, $value );

		return FALSE;
	}

	/**
	 * @param $label
	 * @param $value
	 * @param null $msg
	 * @return bool
	 */
	function isDate( $label, $value, $msg = NULL) {
		//Because most epochs are stored as 4-byte integers, make sure we are within range.
		if ( TTDate::isValidDate( $value ) ) {
			$date = gmdate('U', $value);
			//Debug::text('Raw Date: '. $value .' Converted Value: '. $date, __FILE__, __LINE__, __METHOD__, $this->verbosity);

			if (  $date == $value ) {
				return TRUE;
			}
		}

		$this->Error($label, $msg, $value );

		return FALSE;
	}

	/**
	 * @param $label
	 * @param $value
	 * @param null $msg
	 * @param null $country
	 * @return bool
	 */
	function isSIN( $label, $value, $msg = NULL, $country = NULL ) {
		$sin = $this->stripNonAlphaNumeric( trim( $value ) ); //UK National Insurance Number (NINO) has letters, so we can only strip spaces.
		Debug::text('Validating SIN/SSN: '. $value .' Country: '. $country, __FILE__, __LINE__, __METHOD__, $this->verbosity);

		$retval = FALSE;
		switch ( strtolower( trim( $country) ) ) {
			case 'ca':
				if ( ( is_numeric( $sin ) AND $sin >= 100000000 AND $sin < 999999999 ) ) {
					$split_sin = str_split( $sin );

					if ( ( $split_sin[1] *= 2 ) >= 10 ) {
						$split_sin[1] -= 9;
					}
					if ( ( $split_sin[3] *= 2 ) >= 10 ) {
						$split_sin[3] -= 9;
					}
					if ( ( $split_sin[5] *= 2 ) >= 10 ) {
						$split_sin[5] -= 9;
					}
					if ( ( $split_sin[7] *= 2 ) >= 10 ) {
						$split_sin[7] -= 9;
					}

					if ( ( array_sum( $split_sin ) % 10 ) != 0 ) {
						$retval = FALSE;
					} else {
						$retval = TRUE;
					}
				} else {
					if ( $sin == 999999999 OR $sin == '000000000' ) { //Allow all 9/0's for a SIN in case its an out of country employee that doesn't have one.
						$retval = TRUE;
					} else {
						$retval = FALSE;
					}
				}
				break;
			case 'us':
				if ( strlen($sin) == 9 AND is_numeric( $sin ) ) {
					//Due to highgroup randomization, we can no longer validate SSN's without querying the IRS database.
					$retval = TRUE;
				} else {
					$retval = FALSE;
				}
				break;
			default:
				//Allow all foriegn countries to utilize
				$retval = self::isLength($label, $value, $msg, 1, 255);
				break;
		}

		if ( $retval === TRUE ) {
			return TRUE;
		}

		Debug::text('Invalid SIN/SSN: '. $value .' Country: '. $country, __FILE__, __LINE__, __METHOD__, $this->verbosity);
		$this->Error($label, $msg, $value );

		return FALSE;
	}

	/*
	 * String manipulation functions.
	 */
	/**
	 * @param $value
	 * @return int
	 */
	function stripNon32bitInteger( $value) {
		if ( (int)$value >= 2147483647 OR (int)$value <= -2147483648 ) { //Make sure we cast $value to integer, otherwise its a string comparison which is not as intended.
			return 0;
		}

		return $value;
	}

	/**
	 * @param $value
	 * @return mixed
	 */
	function stripSpaces( $value) {
		return str_replace(' ', '', trim($value));
	}

	/**
	 * @param $value
	 * @return mixed
	 */
	function stripNumeric( $value) {
		$retval = preg_replace('/[0-9]/', '', $value);
		return $retval;
	}

	/**
	 * @param $value
	 * @return mixed
	 */
	function stripNonNumeric( $value) {
		$retval = preg_replace('/[^0-9]/', '', $value);
		return $retval;
	}

	/**
	 * @param $value
	 * @return mixed
	 */
	function stripNonAlphaNumeric( $value) {
		$retval = preg_replace('/[^A-Za-z0-9]/', '', $value);

		//Debug::Text('Alpha Numeric String:'. $retval, __FILE__, __LINE__, __METHOD__, $this->verbosity);

		return $retval;
	}

	/**
	 * @param $value int|float|string
	 * @return int|float|string
	 */
	function stripNonFloat( $value) {
		//Don't use TTi18n::parseFloat() here, as if we are going to be doing that we should do it as early as possible to the user input, like in setObjectFromArray().
		//  Then this function would often be called afterwards, so it should always return a float value or something that can be used in math.
		//  TTi18n::parseFloat() parses out non-float characters itself.
		//We do need to check if the value passed in is already cast to float/int and just accept it in that case.
		//    Because in other locales preg_match() casts $value to a string, which means decimal could become a comma, then it won't match.
		if ( is_float( $value ) === TRUE OR is_int( $value ) === TRUE ) {
			return $value;
		} else {
			//Strips repeating "." and "-" characters that might slip in due to typos. Then strips non-float valid characters.
			//This is also done in TTi18n::parseFloat() and Validator->stripNonFloat()
			//$retval = preg_replace( '/([\-\.,])(?=.*?\1)|[^-0-9\.,]/', '', $value );

			//This should return a float value, or at least something that can be used in math functions.
			// TTi18n::parseFloat() should be called in setObjectFromArray() well before this would ever be called, which is typically in set*() functions.
			// CompanyDeductionFactory requires this, as Advanced Percent calculations have values entered as '124,000' which are run through stripNonFloat() prior to performing math on them.
			$retval = preg_replace( '/([\-\.])(?=.*?\1)|[^-0-9\.]/', '', $value );
		}

		//Debug::Text('Float String:'. $retval, __FILE__, __LINE__, __METHOD__, $this->verbosity);

		return $retval;
	}

	/**
	 * Suitable for passing to parseTimeUnit() after.
	 * @param $value
	 * @return mixed
	 */
	function stripNonTimeUnit( $value) {
		$retval = preg_replace('/[^-0-9\.:]/', '', $value);

		//Debug::Text('Float String:'. $retval, __FILE__, __LINE__, __METHOD__, $this->verbosity);

		return $retval;
	}

	/**
	 * @param $value
	 * @return string
	 */
	function stripHTML( $value) {
		return strip_tags($value);
	}

	/**
	 * @param $value
	 * @return string
	 */
	function escapeHTML( $value) {
		return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
	}

	/**
	 * @param $value
	 * @return string
	 */
	function purifyHTML( $value ) {
		global $config_vars;

		//Require inside this function as HTMLPurifier is a huge file.
		require_once( dirname( __FILE__ ) . DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'HTMLPurifier'. DIRECTORY_SEPARATOR .'HTMLPurifier.standalone.php');

		$config = HTMLPurifier_Config::createDefault();
		if ( isset($config_vars['cache']['enable']) AND $config_vars['cache']['enable'] == TRUE
			AND $config_vars['cache']['dir'] != '' AND is_writable( $config_vars['cache']['dir'] ) ) {
			$config->set('Cache.SerializerPath', $config_vars['cache']['dir'] );
			//Debug::Text('Caching HTMLPurifier...', __FILE__, __LINE__, __METHOD__, $this->verbosity);
		} else {
			$config->set('Cache.DefinitionImpl', NULL );
			Debug::Text('NOT caching HTMLPurifier...', __FILE__, __LINE__, __METHOD__, $this->verbosity);
		}

		$purifier = new HTMLPurifier( $config );
		return $purifier->purify( $value );
	}

	/**
	 * @param $value
	 * @return bool|string
	 */
	function getPhoneNumberAreaCode( $value) {
		$phone_number = $this->stripNonNumeric( $value );
		if ( strlen($phone_number) > 7 ) {
			$retval = substr( $phone_number, -10, 3 ); //1 555 555 5555
			return $retval;
		}

		return FALSE;
	}

	/*
	 * Class standard functions.
	 */

	/**
	 * @param $string
	 * @param $var_array
	 * @return mixed
	 */
	function varReplace( $string, $var_array) {
		//var_array = arary('var1' => 'blah1', 'var2' => 'blah2');
		$keys = array();
		$values = array();
		if ( is_array($var_array) AND count($var_array) > 0) {
			foreach($var_array as $key => $value) {
				$keys[] = '#'.$key;
				$values[] = $value;
			}
		}

		$retval = str_replace($keys, $values, $string);

		return $retval;
	}

	/**
	 * @param int $validate_only EPOCH
	 */
	function setValidateOnly( $validate_only ) {
		$this->validate_only = $validate_only;
	}

	/**
	 * @return bool
	 */
	function getValidateOnly() {
		return $this->validate_only;
	}

	/**
	 * Returns both Errors and Warnings combined.
	 * @return array
	 */
	function getErrorsAndWarningsArray() {
		return array( 'errors' => $this->errors, 'warnings' => $this->warnings );
	}

	/**
	 * Merges all errors/warnings from the passed $validator object to this one.
	 * @param object $validator
	 * @return bool
	 */
	function merge( $validator ) {
		if ( is_object( $validator ) AND $validator->isValid() == FALSE ) {
			$this->errors = array_merge( $this->errors, $validator->getErrorsArray() );
			$this->num_errors += count( $validator->getErrorsArray() );

			$this->warnings = array_merge( $this->warnings, $validator->getWarningsArray() );
			$this->num_warnings += count( $validator->getWarningsArray() );
		}

		return TRUE;
	}

	/**
	 * @return array
	 */
	function getErrorsArray() {
		return $this->errors;
	}

	/**
	 * @return bool|string
	 */
	function getErrors() {
		if ( count( $this->errors ) > 0) {
			$output = "<ol>\n";
			foreach ($this->errors as $label) {
				foreach ($label as $msg) {
					$output .= '<li>'.$msg.".</li>";
				}
			}
			$output .= "</ol>\n";
			return $output;
		}

		return FALSE;
	}

	/**
	 * @param bool $numbered_list
	 * @param array $errors_arr Pass in other error array to be converted to text.
	 * @return bool|string
	 */
	function getTextErrors( $numbered_list = TRUE, $errors_arr = NULL ) {
		if ( $errors_arr == NULL ) {
			$errors_arr = $this->errors;
		}

		if ( count( $errors_arr ) > 0) {
			$output = '';
			$number_prefix = NULL;
			$i = 1;
			foreach ( $errors_arr as $label) {
				foreach ($label as $msg) {
					if ( $numbered_list == TRUE ) {
						$number_prefix = $i .'. ';
					}
					$output .= $number_prefix . $msg . "\n";
				}

				$i++;
			}
			return $output;
		}

		return FALSE;
	}

	/**
	 * @param null $label
	 * @return bool
	 */
	final function isValid( $label = NULL ) {
		if ( $this->isError( $label ) OR $this->isWarning( $label ) ) {
			return FALSE;
		}

		return TRUE;
	}


	/**
	 * @param null $label
	 * @return bool
	 */
	final function isError( $label = NULL ) {
		if ( $label != NULL ) {
			return $this->hasError( $label );
		} elseif ( $this->num_errors > 0 ) {
			Debug::Arr($this->errors, 'Errors', __FILE__, __LINE__, __METHOD__, $this->verbosity);
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * @return bool
	 */
	function resetErrors() {
		$this->errors = array(); //Set to blank array rather than use unset() as that will cause PHP warnings in hasError().
		$this->num_errors = 0;

		return TRUE;
	}

	/**
	 * @param $label
	 * @return bool
	 */
	function hasError( $label ) {
		if ( in_array($label, array_keys($this->errors)) ) {
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * @param $label
	 * @param $msg
	 * @param string $value
	 * @return bool
	 */
	function Error( $label, $msg, $value = '' ) {
		Debug::text('Validation Error: Label: '. $label .' Value: "'. $value .'" Msg: '. $msg, __FILE__, __LINE__, __METHOD__, $this->verbosity);

		//If label is NULL, assume we don't actually want to trigger an error.
		//This is good for just using the check functions for other purposes.
		if ( $label != '') {
			$this->errors[$label][] = $msg;

			$this->num_errors++;

			return TRUE;
		}

		return FALSE;
	}

	//
	// Warning functions below here
	//

	/**
	 * @return array
	 */
	function getWarningsArray() {
		return $this->warnings;
	}

	/**
	 * @param null $label
	 * @return bool
	 */
	final function isWarning( $label = NULL ) {
		if ( $label != NULL ) {
			return $this->hasWarning( $label );
		} elseif ( $this->num_warnings > 0 ) {
			Debug::Arr($this->warnings, 'Warnings', __FILE__, __LINE__, __METHOD__, $this->verbosity);
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * @return bool
	 */
	function resetWarnings() {
		$this->warnings = array(); //Set to blank array rather than use unset() as that will cause PHP warnings in hasWarning().
		$this->num_warnings = 0;

		return TRUE;
	}

	/**
	 * @param $label
	 * @return bool
	 */
	function hasWarning( $label ) {
		if ( in_array($label, array_keys($this->warnings)) ) {
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * @param $label
	 * @param $msg
	 * @param string $value
	 * @return bool
	 */
	function Warning( $label, $msg, $value = '' ) {
		Debug::text('Validation Warning: Label: '. $label .' Value: "'. $value .'" Msg: '. $msg, __FILE__, __LINE__, __METHOD__, $this->verbosity);

		if ( $label != '') {
			$this->warnings[$label][] = $msg;

			$this->num_warnings++;

			return TRUE;
		}

		return FALSE;
	}


}
?>
