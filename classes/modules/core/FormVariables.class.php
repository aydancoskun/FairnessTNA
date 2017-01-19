<?php
/*********************************************************************************
 * This file is part of "Fairness", a Payroll and Time Management program.
 * Fairness is Copyright 2013 Aydan Coskun (aydan.ayfer.coskun@gmail.com)
 * Portions of this software are Copyright (C) 2003 - 2013 TimeTrex Software Inc.
 * because Fairness is a fork of "TimeTrex Workforce Management" Software.
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
/*
 * $Revision: 5166 $
 * $Id: FormVariables.class.php 5166 2011-08-26 23:01:36Z ipso $
 * $Date: 2011-08-26 16:01:36 -0700 (Fri, 26 Aug 2011) $
 */

/**
 * @package Core
 */
class FormVariables {
	static function getVariables($form_variables, $form_type = 'BOTH', $filter_input = TRUE, $filter_ignore_name_arr = array('next_page','batch_next_page') ) {
		$form_type = trim(strtoupper($form_type));

		if ( is_array($form_variables) ) {
			foreach($form_variables as $variable_name) {

				$retarr[$variable_name] = NULL;
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
					default:
						if ( isset($_GET[$variable_name]) ) {
							$retarr[$variable_name] = $_GET[$variable_name];
						} elseif ( isset($_POST[$variable_name]) ) {
							$retarr[$variable_name] = $_POST[$variable_name];
						}
				}

				//Ignore next_page, batch_next_page variables as those are encoded URLs passed in, and htmlspecialchars
				//will break them.
				if ( $filter_input == TRUE AND is_string($retarr[$variable_name]) AND $retarr[$variable_name] != ''
						AND ( !is_array($filter_ignore_name_arr) OR ( is_array($filter_ignore_name_arr) AND !in_array( $variable_name, $filter_ignore_name_arr) ) ) ) {
					//Remove "javascript:" from all inputs, and run htmlspecialchars over them to help prevent XSS attacks.
					$retarr[$variable_name] = self::sanitize( $retarr[$variable_name] );
				} elseif ( strtolower($filter_input) == 'recurse' AND is_array($retarr[$variable_name])
								AND ( !is_array($filter_ignore_name_arr) OR ( is_array($filter_ignore_name_arr) AND !in_array( $variable_name, $filter_ignore_name_arr) ) ) ) {
					self::RecurseFilterArray($retarr[$variable_name]);
				}
			}

			if ( isset($retarr) ) {
				return $retarr;
			}
		}

		//Return empty array so extract() doesn't complain.
		return array();
	}

	static function RecurseFilterArray(&$arr) {
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

	static function sanitize( $val ) {
		return @htmlspecialchars( str_ireplace( array('javascript:', 'src=', 'www.example.com'), '', $val ), ENT_QUOTES, 'UTF-8' ); //Supress warnings due to invalid multibyte sequences
	}
}
?>
