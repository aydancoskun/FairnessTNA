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
 * @package Core
 */
class Option {
	static function getByKey($key, $options, $false = FALSE ) {
		if ( isset($options[$key]) ) {
			//Debug::text('Returning Value: '. $options[$key], __FILE__, __LINE__, __METHOD__, 9);

			return $options[$key];
		}

		return $false;
		//return FALSE;
	}

	static function getByValue($value, $options, $value_is_translated = TRUE ) {
		// I18n: Calling gettext on the value here enables a match with the translated value in the relevant factory.
		//		 BUT... such string comparisons are messy and we really should be using getByKey for most everything.
		//		 Exceptions can be made by passing false for $value_is_translated.
		if ( $value_is_translated == TRUE ) {
			$value = TTi18n::gettext( $value );
		}
		if ( is_array( $value ) ) {
			return FALSE;
		}

		if ( !is_array( $options ) ) {
			return FALSE;
		}

		$flipped_options = array_flip($options);

		if ( isset($flipped_options[$value]) ) {
			//Debug::text('Returning Key: '. $flipped_options[$value], __FILE__, __LINE__, __METHOD__, 9);

			return $flipped_options[$value];
		}

		return FALSE;
	}

	static function getByFuzzyValue($value, $options, $value_is_translated = TRUE ) {
		// I18n: Calling gettext on the value here enables a match with the translated value in the relevant factory.
		//		 BUT... such string comparisons are messy and we really should be using getByKey for most everything.
		//		 Exceptions can be made by passing false for $value_is_translated.
		if ( $value_is_translated == TRUE ) {
			$value = TTi18n::gettext( $value );
		}
		if ( is_array( $value ) ) {
			return FALSE;
		}

		if ( !is_array( $options ) ) {
			return FALSE;
		}

		$retarr = Misc::findClosestMatch( $value, $options, 10, FALSE );
		//Debug::Arr($retarr, 'RetArr: ', __FILE__, __LINE__, __METHOD__, 10);

		/*
		//Convert SQL search value ie: 'test%test%' to a regular expression.
		$value = str_replace('%', '.*', $value);

		foreach( $options as $key => $option_value ) {
			if ( preg_match('/^'.$value.'$/i', $option_value) ) {
				$retarr[] = $key;
			}
		}
		*/

		if ( isset($retarr) ) {
			return $retarr;
		}

		return FALSE;
	}

	//Takes $needles as an array, loops through them returning matching
	//keys => value pairs from haystack
	//Useful for filtering results to a select box, like status.
	static function getByArray($needles, $haystack) {

		if (!is_array($needles) ) {
			$needles = array($needles);
		}

		$needles = array_unique($needles);

		$retval = array();
		foreach($needles as $needle) {
			if ( isset($haystack[$needle]) ) {
				$retval[$needle] = $haystack[$needle];
			}
		}

		if ( empty($retval) == FALSE ) {
			return $retval;
		}

		return FALSE;
	}

	static function getArrayByBitMask( $bitmask, $options ) {
		$bitmask = (int)$bitmask;

		$retarr = array();
		if ( is_numeric($bitmask) AND is_array($options) ) {
			foreach( $options as $key => $value ) {
				//Debug::Text('Checking Bitmask: '. $bitmask .' mod '. $key .' != 0', __FILE__, __LINE__, __METHOD__, 10);
				if ( ($bitmask & (int)$key) !== 0 ) {
					//Debug::Text('Found Bit: '. $key, __FILE__, __LINE__, __METHOD__, 10);
					$retarr[] = $key;
				}
			}
			unset($value); //code standards
		}

		if ( empty($retarr) == FALSE ) {
			return $retarr;
		}

		return FALSE;
	}

	static function getBitMaskByArray( $keys, $options ) {
		$retval = 0;
		if ( is_array($keys) AND is_array($options) ) {
			foreach( $keys as $key ) {
				if ( isset($options[$key]) ) {
					$retval |= $key;
				} else {
					Debug::Text('Key is not a valid bitmask int: '. $key, __FILE__, __LINE__, __METHOD__, 10);
				}
			}
		}

		return $retval;
	}
}
?>
