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
class Sort {
	/**
	 * @param $data
	 * @param $col1
	 * @param null $col2
	 * @param string $col1_order
	 * @param string $col2_order
	 * @return mixed
	 */
	static function multiSort( $data, $col1, $col2 = NULL, $col1_order = 'ASC', $col2_order = 'ASC' ) {
		global $profiler;

		$profiler->startTimer( 'multiSort()' );
		//Debug::Text('Sorting... Col1: '. $col1 .' Col2: '. $col2 .' Col1 Order: '. $col1_order .' Col2 Order: '. $col2_order, __FILE__, __LINE__, __METHOD__, 10);

		$sort_col1 = array();
		$sort_col2 = array();
		foreach ($data as $key => $row) {
			if ( isset($row[$col1]) ) {
				$sort_col1[$key] = $row[$col1];
			} else {
				$sort_col1[$key] = NULL;
			}

			if ( $col2 !== NULL ) {
				if ( isset($row[$col2]) ) {
					$sort_col2[$key] = $row[$col2];
				} else {
					$sort_col2[$key] = NULL;
				}
			}
		}

		if ( strtolower($col1_order) == 'desc' OR $col1_order == -1 ) {
			$col1_order = SORT_DESC;
		} else {
			$col1_order = SORT_ASC;
		}

		if ( strtolower($col2_order) == 'desc' OR $col2_order == -1 ) {
			$col2_order = SORT_DESC;
		} else {
			$col2_order = SORT_ASC;
		}

		if ( is_array($sort_col2) AND count($sort_col2) > 0 ) {
			array_multisort($sort_col1, $col1_order, $sort_col2, $col2_order, $data);
		} else {
			array_multisort($sort_col1, $col1_order, $data);
		}

		$profiler->stopTimer( 'multiSort()' );
		return $data;
	}

	/**
	 * Usage: $arr2 = Sort::arrayMultiSort($arr1, array( 'name' => array(SORT_DESC, SORT_REGULAR), 'cat' => SORT_ASC ) );
	 * Case insensitive sorting.
	 * @param $array
	 * @param $cols
	 * @return array|bool
	 */
	static function arrayMultiSort( $array, $cols ) {
		global $profiler;
		$profiler->startTimer( 'Sort()' );

		if ( !is_array($array) ) {
			return FALSE;
		}

		if ( !is_array($cols) ) {
			return $array; //No sorting to do.
		}

		$colarr = array();
		foreach( $cols as $col => $order ) {
			$colarr[$col] = array();
			foreach( $array as $k => $row ) {
				if ( isset($row[$col]) ) {
					//Check if the value is an array with a 'sort' column, ie: array('sort' => 12345678, 'display' => '01-Jan-10' )
					if ( is_array($row[$col]) ) {
						$colarr[$col]['_'.$k] = strtolower( $row[$col]['sort'] );
					} else {
						$colarr[$col]['_'.$k] = strtolower( $row[$col] );
					}
				} else {
					//If the sorting column is invalid, use NULL value instead.
					$colarr[$col]['_'.$k] = NULL;
				}
			}
		}

		$params = array();
		$order_type = array();
		$tmp_order_element = array();
		foreach( $cols as $col => $order ) {
			$params[] = &$colarr[$col];
			$order = (array)$order;

			//SORT_REGULAR won't work correctly for strings/integers and such, so we need to force the correct sort type based on
			//the first value of each item in the column array. Because call_user_func_array() requires parameters based by reference
			//we need to jump through hoops to make this an array that we can then later reference.
			$order_type[$col] = SORT_REGULAR;
			if ( isset($colarr[$col]['_0']) AND is_numeric($colarr[$col]['_0']) ) {
				//Debug::Text('Using Numeric Sorting for Column: '. $col .' based on: '. $colarr[$col]['_0'], __FILE__, __LINE__, __METHOD__, 10);
				$order_type[$col] = SORT_NUMERIC;
			}

			foreach( $order as $order_element ) {
				//pass by reference, as required by php 5.3
				if ( !is_numeric( $order_element ) ) {
					if ( strtolower($order_element) == 'asc' ) {
						$tmp_order_element[$col] = SORT_ASC;
					} elseif ( strtolower($order_element) == 'desc' ) {
						$tmp_order_element[$col] = SORT_DESC;
					}
				} else {
					$tmp_order_element[$col] = $order_element;
				}

				$params[] = &$tmp_order_element[$col];
				$params[] = &$order_type[$col];
			}
		}

		//Debug::Arr($params, 'aSort Data: ', __FILE__, __LINE__, __METHOD__, 10);
		call_user_func_array('array_multisort', $params);
		//Debug::Arr($params, 'bSort Data: ', __FILE__, __LINE__, __METHOD__, 10);

		$retarr = array();
		$keys = array();
		$first = TRUE;
		foreach( $colarr as $col => $arr ) {
			foreach( $arr as $k => $v ) {
				if ($first) {
					$keys[$k] = substr($k, 1);
				}
				$k = $keys[$k];

				if ( !isset($retarr[$k]) ) {
					$retarr[$k] = $array[$k];
				}

				if ( isset($array[$k][$col]) ) {
					$retarr[$k][$col] = $array[$k][$col];
				}
			}
			unset($v); //code standards
			$first = FALSE;
		}

		$profiler->stopTimer( 'Sort()' );

		return $retarr;
	}
}
?>
