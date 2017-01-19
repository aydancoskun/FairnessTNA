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
class URLBuilder {
	static protected $data = array();
	static protected $script = 'index.php';

	//Recursively convert an array to a URL.
	static function urlencode_array($var, $varName = NULL, $sub_array = FALSE ) {
		$separator = '&';
		$toImplode = array();
		foreach ($var as $key => $value) {
			if ( is_array($value) ) {
				
				if ( $sub_array == FALSE ) {
					$toImplode[] = self::urlencode_array($value, $key, TRUE );
				} else {
					$toImplode[] = self::urlencode_array($value, $varName.'['.$key.']', TRUE );
				}
			} else {				
				if ( $sub_array == TRUE ) {
					//$toImplode[] = $varName.'['.$key.']='.urlencode($value);
					$toImplode[] = $varName.'['.$key.']='.$value;
				} else {
					//$toImplode[] = $key.'='.urlencode($value);
					$toImplode[] = $key.'='.$value;
				}
			}
		}
		
		return implode($separator, $toImplode);
	}

	static function setURL($script, $array = NULL) {
		//Debug::Arr(self::$data, 'Before: ', __FILE__, __LINE__, __METHOD__, 10);
		if ( is_array($array) AND count($array) > 0) {
			self::$data = array_merge(self::$data, $array);
		}
		//Debug::Arr(self::$data, 'After: ', __FILE__, __LINE__, __METHOD__, 10);

		self::$script = $script;

		return TRUE;
	}

	static function getURL($array = NULL, $script = NULL, $merge = TRUE) {
		//Debug::Arr($array, 'Passed Array', __FILE__, __LINE__, __METHOD__, 10);

		//Debug::Arr(self::$data, 'bSelf Data: ', __FILE__, __LINE__, __METHOD__, 10);
		//Debug::Arr($array, 'bArray: ', __FILE__, __LINE__, __METHOD__, 10);
		if ( is_array($array) AND count($array) > 0 AND $merge == TRUE) {
			$array = array_merge(self::$data, $array);
		} elseif ($array == NULL AND $merge == TRUE) {
			$array = self::$data;
		} //else Use $array as is.

		//Debug::Arr($array, 'bAfter: ', __FILE__, __LINE__, __METHOD__, 10);

		if ($script == NULL) {
			//$script = Environment::getBaseURL().self::$script;
			$script = self::$script;
		}

		//Debug::Arr($array, 'Final Array', __FILE__, __LINE__, __METHOD__, 10);

		if ( is_array($array) AND count($array) > 0 ) {
			$url_values = self::urlencode_array( $array );
			//Debug::Text('URL Values: '. $url_values, __FILE__, __LINE__, __METHOD__, 10);

			//if (isset($url_values) AND is_array($url_values)) {
			if (isset($url_values) AND $url_values != '' ) {
				$url = '?'.$url_values;
			} else {
				$url = '?';
			}
		}

		if ( isset($url) ) {
			$retval = $script.$url;
		} else {
			$retval = $script;
		}

		//Debug::Text('URL: '. $retval, __FILE__, __LINE__, __METHOD__, 11);

		return $retval;
	}
}
?>
