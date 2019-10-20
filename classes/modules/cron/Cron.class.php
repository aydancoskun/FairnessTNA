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
 * @package Modules\Cron
 */
class Cron {

	static protected $limits = array(
							'minute' => array('min' => 0, 'max' => 59 ),
							'hour' => array('min' => 0, 'max' => 23 ),
							'day_of_month' => array('min' => 1, 'max' => 31 ),
							'month' => array('min' => 1, 'max' => 12 ),
							'day_of_week' => array('min' => 0, 'max' => 7 ),
					);

	/**
	 * @param $name
	 * @param int $interval
	 * @return array|bool
	 */
	static function getOptions( $name, $interval = 1 ) {
		$all_array_option = array( '*' => TTi18n::getText('-- All --') );

		$retval = FALSE;
		switch ( $name ) {
			case 'minute':
				for( $i = 0; $i <= 59; $i += $interval ) {
					$retval[$i] = $i;
				}
				$retval = Misc::prependArray( $all_array_option, $retval );
				break;
			case 'hour':
				for( $i = 0; $i <= 23; $i += $interval ) {
					$retval[$i] = $i;
				}
				$retval = Misc::prependArray( $all_array_option, $retval );
				break;
			case 'day_of_month':
				$retval = Misc::prependArray( $all_array_option, TTDate::getDayOfMonthArray() );
				break;
			case 'month':
				$retval = Misc::prependArray( $all_array_option, TTDate::getMonthOfYearArray() );
				break;
			case 'day_of_week':
				$retval = Misc::prependArray( $all_array_option, TTDate::getDayOfWeekArray() );
				break;
		}

		return $retval;
	}

	/**
	 * @param $value_arr
	 * @param $type
	 * @return bool
	 */
	static function isValidLimit( $value_arr, $type ) {
		if ( isset(self::$limits[$type]) ) {
			$limit_arr = self::$limits[$type];
		} else {
			Debug::text('Type is invalid: '. $type, __FILE__, __LINE__, __METHOD__, 10);
			return FALSE;
		}

		if ( is_array($value_arr) AND is_array($limit_arr) AND count($value_arr) > 0 ) {
			//Debug::Arr($value_arr, 'Value Arr: ', __FILE__, __LINE__, __METHOD__, 10);
			//Debug::Arr($limit_arr, 'Limit Arr: ', __FILE__, __LINE__, __METHOD__, 10);

			foreach($value_arr as $value ) {
				if ( $value == '*' ) {
					$retval = TRUE;
				} else {
					if ( $value >= $limit_arr['min'] AND $value <= $limit_arr['max'] ) {
						$retval = TRUE;
					} else {
						return FALSE;
					}
				}
			}

			return $retval;
		}

		return FALSE;
	}

	/**
	 * @param $arr
	 * @param $type
	 * @return bool|string
	 */
	static function arrayToScheduleString( $arr, $type ) {
		if ( !is_array($arr) ) {
			if ( $arr !== 0 AND $arr !== '0' AND empty($arr) ) {
				$arr = '*';
			}
			$arr = array($arr);
		}

		//If any of the array entries is '*', just return that as the string and ignore everything else.
		// Use STRICT=TRUE on in_array() check, otherwise (int)0 matches '*' search.
		if ( is_array($arr) AND in_array('*', $arr, TRUE) === TRUE ) {
			return '*';
		} else {
			if ( is_array( $arr ) ) {
				sort( $arr );
				$retval = implode( ',', array_unique( $arr ) );

				//Debug::Arr( $retval, 'Final String: ', __FILE__, __LINE__, __METHOD__, 10 );
				return $retval;
			}
		}

		return FALSE;
	}

	/**
	 * Parses any column into a complete list of entries.
	 * ie: converts:		0-59 to an array of: 0, 1, 2, 3, 4, 5, 6, ...
	 * 						0-2, 16, 18 to array of 0, 1, 2, 16, 18
	 * 						<star>/2 to array of 0, 2, 4, 6, 8, ...
	 * @param $str
	 * @param $type
	 * @return array
	 */
	static function parseScheduleString( $str, $type ) {
		if ( $str !== 0 AND $str !== '0' AND empty($str) ) {
			$str = '*';
		}

		$split_str = explode(',', $str);

		if ( count($split_str) == 0 ) {
			//Debug::text('Schedule String DOES NOT have multiple commas: '. count($split_str), __FILE__, __LINE__, __METHOD__, 10);
			$split_str = array($split_str);
		} //else { Debug::text('Schedule String has multiple commas: '. count($split_str), __FILE__, __LINE__, __METHOD__, 10);

		$retarr = array();
		$limit_options = self::$limits;
		foreach( $split_str as $str_atom ) {
			if ( strpos($str_atom, '-') !== FALSE ) {
				//Debug::text('Schedule atom has basic range: '. $str_atom, __FILE__, __LINE__, __METHOD__, 10);
				//Found basic range
				//get Min/Max of range
				$str_atom_range = explode('-', $str_atom);

				$retarr = array_merge( $retarr, range($str_atom_range[0], $str_atom_range[1]) );
				unset($str_atom_range);
			} elseif ( strpos($str_atom, '/') !== FALSE ) {
				//Debug::text('Schedule atom has advanced range: '. $str_atom, __FILE__, __LINE__, __METHOD__, 10);
				//Found basic range
				//get Min/Max of range
				$str_atom_range = explode('/', $str_atom);

				$retarr = array_merge( $retarr, range($limit_options[$type]['min'], $limit_options[$type]['max'], $str_atom_range[1]) );
				unset($str_atom_range);
			} else {
				//No Range found
				//Debug::text('Schedule atom does not have range: '. $str_atom, __FILE__, __LINE__, __METHOD__, 10);

				if ( trim($str_atom) == '*' ) {
					//Debug::text('Found Full Range!: '. $str_atom, __FILE__, __LINE__, __METHOD__, 10);
					$retarr = array_merge( $retarr, range($limit_options[$type]['min'], $limit_options[$type]['max']) );
				} else {
					//Debug::text('Singleton: '. $str_atom, __FILE__, __LINE__, __METHOD__, 10);
					$retarr[] = (int)$str_atom;
				}
			}
		}

		rsort($retarr);
		$retarr = array_values( array_unique($retarr) ); //Unique and rekey array so index is consecutive. This prevents problems with the dropdown box.

		//Debug::Arr($retarr, 'Final Array: ', __FILE__, __LINE__, __METHOD__, 10);
		return $retarr;
	}

	/**
	 * @param $min_col
	 * @param $hour_col
	 * @param $dom_col
	 * @param $month_col
	 * @param $dow_col
	 * @param int $epoch EPOCH
	 * @return false|int|null
	 */
	static function getNextScheduleDate( $min_col, $hour_col, $dom_col, $month_col, $dow_col, $epoch = NULL ) {
		if ( $epoch == '' ) {
			$epoch = 0;
		}

		$month_arr = self::parseScheduleString( $month_col, 'month' );
		$day_of_month_arr = self::parseScheduleString( $dom_col, 'day_of_month' );
		$day_of_week_arr = self::parseScheduleString( $dow_col, 'day_of_week' );
		$hour_arr = self::parseScheduleString( $hour_col, 'hour' );
		$minute_arr = self::parseScheduleString( $min_col, 'minute' );

		$retval = $epoch;
		$i = 0;
		while ( $i < 500 ) { //Prevent infinite loop
			$date_arr = getdate( $retval );
			$i++;

			//Order from minute to month, least granular to most granular.
			if ( !in_array( $date_arr['minutes'], $minute_arr ) ) {
				$retval = TTDate::incrementDate( $retval, 1, 'minute');
				//Debug::text(' Minute: Retval: '. TTDate::getDate('DATE+TIME', $retval) .' Current Epoch: '. TTDate::getDate('DATE+TIME', $epoch), __FILE__, __LINE__, __METHOD__, 10);
				continue;
			}

			if ( !in_array( $date_arr['hours'], $hour_arr ) ) {
				$retval = TTDate::incrementDate( $retval, 1, 'hour');
				//Debug::text(' Hour: Retval: '. TTDate::getDate('DATE+TIME', $retval) .' Current Epoch: '. TTDate::getDate('DATE+TIME', $epoch), __FILE__, __LINE__, __METHOD__, 10);
				continue;
			}

			if ( !in_array( $date_arr['mday'], $day_of_month_arr ) OR !in_array( $date_arr['wday'], $day_of_week_arr ) ) {
				$retval = TTDate::getBeginDayEpoch( TTDate::incrementDate( $retval, 1, 'day') );
				//Debug::text(' Day: Retval: '. TTDate::getDate('DATE+TIME', $retval) .' Current Epoch: '. TTDate::getDate('DATE+TIME', $epoch), __FILE__, __LINE__, __METHOD__, 10);
				continue;
			}

			if ( !in_array( $date_arr['mon'], $month_arr ) ) {
				$retval = TTDate::getBeginDayEpoch( TTDate::incrementDate( $retval, 1, 'month') );
				//Debug::text(' Month: Retval: '. TTDate::getDate('DATE+TIME', $retval) .' Current Epoch: '. TTDate::getDate('DATE+TIME', $epoch), __FILE__, __LINE__, __METHOD__, 10);
				continue;
			}
			//Debug::text(' None: Retval: '. TTDate::getDate('DATE+TIME', $retval) .' Current Epoch: '. TTDate::getDate('DATE+TIME', $epoch), __FILE__, __LINE__, __METHOD__, 10);

			//Halt the loop...
			break;
		}

		Debug::text(' Next Scheduled Date: '. TTDate::getDate('DATE+TIME', $retval) .' Based on Current Epoch: '. TTDate::getDate('DATE+TIME', $epoch), __FILE__, __LINE__, __METHOD__, 10);

		//Debug::text('	 JOB is NOT SCHEDULED TO RUN YET!', __FILE__, __LINE__, __METHOD__, 10);
		return $retval;

	}


	/**
	 * @param $min_col
	 * @param $hour_col
	 * @param $dom_col
	 * @param $month_col
	 * @param $dow_col
	 * @param int $epoch EPOCH
	 * @param int $last_run_date EPOCH
	 * @return bool
	 */
	static function isScheduledToRun( $min_col, $hour_col, $dom_col, $month_col, $dow_col, $epoch = NULL, $last_run_date = NULL ) {
		//Debug::text('Checking if Cron Job is scheduled to run: '. self::getName(), __FILE__, __LINE__, __METHOD__, 10);
		if ( $epoch == '' ) {
			//$epoch = time();
			$epoch = 0;
		}

		//Debug::text('Checking if Cron Job is scheduled to run: '. self::getName(), __FILE__, __LINE__, __METHOD__, 10);
		if ( $last_run_date == '' ) {
			$last_run_date = 0;
		}

		if ( $last_run_date > (time() + 86400) ) {
			Debug::text(' Last Run Date is in the future: '. TTDate::getDate('DATE+TIME', $last_run_date) .' assuming this is an error and forcing it to run now...', __FILE__, __LINE__, __METHOD__, 10);
			$last_run_date = 0;
		}

		$next_schedule_epoch = self::getNextScheduleDate( $min_col, $hour_col, $dom_col, $month_col, $dow_col, $last_run_date );
		if ( $next_schedule_epoch < $epoch ) {
			Debug::text('  JOB is SCHEDULED TO RUN NOW!', __FILE__, __LINE__, __METHOD__, 10);
			return TRUE;
		}

		Debug::text('  JOB is NOT scheduled to run right now...', __FILE__, __LINE__, __METHOD__, 10);
		return FALSE;
	}
}
?>
