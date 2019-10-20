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
class FormVariables {
	/**
	 * @param $form_variables
	 * @param string $form_type
	 * @param bool $filter_input
	 * @param array $filter_ignore_name_arr
	 * @return array
	 */
	static function getVariables( $form_variables, $form_type = 'BOTH', $filter_input = TRUE, $filter_ignore_name_arr = array('next_page', 'batch_next_page') ) {
		$form_type = trim(strtoupper($form_type));

		$retarr = array();
		if ( is_array($form_variables) ) {
			foreach($form_variables as $variable_name) {
				$retarr[$variable_name] = NULL; //Need to set variables to NULL, otherwise we get a lot of variable not set errors.
				switch ($form_type) {
					case 'GET':
						if ( isset($_GET[$variable_name]) ) {
							$retarr[$variable_name] = $_GET[$variable_name];
						}
						break;
					case 'POST':
						if ( isset($_POST[$variable_name]) ) {
							$retarr[$variable_name] = $_POST[$variable_name];
						}
						break;
					default:
						if ( isset($_GET[$variable_name]) ) {
							$retarr[$variable_name] = $_GET[$variable_name];
						} elseif ( isset($_POST[$variable_name]) ) {
							$retarr[$variable_name] = $_POST[$variable_name];
						}
				}

				//Ignore next_page, batch_next_page variables as those are encoded URLs passed in, and htmlspecialchars
				//will break them.
				if ( $filter_input == TRUE AND isset($retarr[$variable_name]) AND is_string($retarr[$variable_name]) AND $retarr[$variable_name] != ''
						AND ( !is_array($filter_ignore_name_arr) OR ( is_array($filter_ignore_name_arr) AND !in_array( $variable_name, $filter_ignore_name_arr) ) ) ) {
					//Remove "javascript:" from all inputs, and run htmlspecialchars over them to help prevent XSS attacks.
					$retarr[$variable_name] = self::sanitize( $retarr[$variable_name] );
				} elseif ( strtolower($filter_input) == 'recurse' AND is_array($retarr[$variable_name])
								AND ( !is_array($filter_ignore_name_arr) OR ( is_array($filter_ignore_name_arr) AND !in_array( $variable_name, $filter_ignore_name_arr) ) ) ) {
					self::RecurseFilterArray($retarr[$variable_name]);
				}
			}

			if ( empty($retarr) == FALSE ) {
				return $retarr;
			}
		}

		//Return empty array so extract() doesn't complain.
		return array();
	}

	/**
	 * @param $arr
	 * @return bool
	 */
	static function RecurseFilterArray( &$arr) {
		if ( !is_array($arr) ) {
			return FALSE;
		}

		foreach ($arr as $key => $val) {
			if ( is_array($val) ) {
				self::RecurseFilterArray($arr[$key]);
			} else {
				$arr[$key] = self::sanitize( $val );
			}
		}

		return TRUE;
	}

	/**
	 * @param $val
	 * @return string
	 */
	static function sanitize( $val ) {
		return @htmlspecialchars( str_ireplace( array('javascript:', 'src=', 'www.example.com'), '', $val ), ENT_QUOTES, 'UTF-8' ); //Supress warnings due to invalid multibyte sequences
	}

	/**
	 * Reverse sanitation for when HTML is allowed. Make sure we purify the HTML after though.
	 * @param $val
	 * @return string
	 */
	static function reverseSanitize( $val ) {
		return htmlspecialchars_decode( $val );
	}

}
?>
