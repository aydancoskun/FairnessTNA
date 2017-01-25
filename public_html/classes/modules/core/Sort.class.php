<?php
/**********************************************************************************
 * This file is part of "FairnessTNA", a Payroll and Time Management program.
 * FairnessTNA is copyright 2013-2017 Aydan Coskun (aydan.ayfer.coskun@gmail.com)
 * others. For full attribution and copyrights details see the COPYRIGHT file.
 *
 * FairnessTNA is free software; you can redistribute it and/or modify it under the
 * terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation, either version 3 of the License, or (at you option )
 * any later version.
 *
 * FairnessTNA is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE.  See the GNU Affero General Public License for more
 * details.
 *
 * You should have received a copy of the GNU Affero General Public License along
 * with this program; if not, see http://www.gnu.org/licenses or write to the Free
 * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301 USA.
 *********************************************************************************/


/**
 * @package Core
 */
class Sort
{
    public static function multiSort($data, $col1, $col2 = null, $col1_order = 'ASC', $col2_order = 'ASC')
    {
        global $profiler;

        $profiler->startTimer('multiSort()');
        //Debug::Text('Sorting... Col1: '. $col1 .' Col2: '. $col2 .' Col1 Order: '. $col1_order .' Col2 Order: '. $col2_order, __FILE__, __LINE__, __METHOD__, 10);

        $sort_col1 = array();
        $sort_col2 = array();
        foreach ($data as $key => $row) {
            if (isset($row[$col1])) {
                $sort_col1[$key] = $row[$col1];
            } else {
                $sort_col1[$key] = null;
            }

            if ($col2 !== null) {
                if (isset($row[$col2])) {
                    $sort_col2[$key] = $row[$col2];
                } else {
                    $sort_col2[$key] = null;
                }
            }
        }

        if (strtolower($col1_order) == 'desc' or $col1_order == -1) {
            $col1_order = SORT_DESC;
        } else {
            $col1_order = SORT_ASC;
        }

        if (strtolower($col2_order) == 'desc' or $col2_order == -1) {
            $col2_order = SORT_DESC;
        } else {
            $col2_order = SORT_ASC;
        }

        if (is_array($sort_col2) and count($sort_col2) > 0) {
            array_multisort($sort_col1, $col1_order, $sort_col2, $col2_order, $data);
        } else {
            array_multisort($sort_col1, $col1_order, $data);
        }

        $profiler->stopTimer('multiSort()');
        return $data;
    }

    //Usage: $arr2 = Sort::arrayMultiSort($arr1, array( 'name' => array(SORT_DESC, SORT_REGULAR), 'cat' => SORT_ASC ) );
    //Case insensitive sorting.
    public static function arrayMultiSort($array, $cols)
    {
        global $profiler;
        $profiler->startTimer('Sort()');

        if (!is_array($array)) {
            return false;
        }

        if (!is_array($cols)) {
            return $array; //No sorting to do.
        }

        $colarr = array();
        foreach ($cols as $col => $order) {
            $colarr[$col] = array();
            foreach ($array as $k => $row) {
                if (isset($row[$col])) {
                    //Check if the value is an array with a 'sort' column, ie: array('sort' => 12345678, 'display' => '01-Jan-10' )
                    if (is_array($row[$col])) {
                        $colarr[$col]['_' . $k] = strtolower($row[$col]['sort']);
                    } else {
                        $colarr[$col]['_' . $k] = strtolower($row[$col]);
                    }
                } else {
                    //If the sorting column is invalid, use NULL value instead.
                    $colarr[$col]['_' . $k] = null;
                }
            }
        }

        $params = array();
        $order_type = array();
        $tmp_order_element = array();
        foreach ($cols as $col => $order) {
            $params[] = &$colarr[$col];
            $order = (array)$order;

            //SORT_REGULAR won't work correctly for strings/integers and such, so we need to force the correct sort type based on
            //the first value of each item in the column array. Because call_user_func_array() requires parameters based by reference
            //we need to jump through hoops to make this an array that we can then later reference.
            $order_type[$col] = SORT_REGULAR;
            if (isset($colarr[$col]['_0']) and is_numeric($colarr[$col]['_0'])) {
                //Debug::Text('Using Numeric Sorting for Column: '. $col .' based on: '. $colarr[$col]['_0'], __FILE__, __LINE__, __METHOD__, 10);
                $order_type[$col] = SORT_NUMERIC;
            }

            foreach ($order as $order_element) {
                //pass by reference, as required by php 5.3
                if (!is_numeric($order_element)) {
                    if (strtolower($order_element) == 'asc') {
                        $tmp_order_element[$col] = SORT_ASC;
                    } elseif (strtolower($order_element) == 'desc') {
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
        $first = true;
        foreach ($colarr as $col => $arr) {
            foreach ($arr as $k => $v) {
                if ($first) {
                    $keys[$k] = substr($k, 1);
                }
                $k = $keys[$k];

                if (!isset($retarr[$k])) {
                    $retarr[$k] = $array[$k];
                }

                if (isset($array[$k][$col])) {
                    $retarr[$k][$col] = $array[$k][$col];
                }
            }
            unset($v); //code standards
            $first = false;
        }

        $profiler->stopTimer('Sort()');

        return $retarr;
    }
}
