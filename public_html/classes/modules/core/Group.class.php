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
class Group
{
    //Usage: $arr2 = Group::GroupBy($arr1, array( 'name' => array('aggregate' => 'count', 'output' => 'MoneyFormat'), array( 'name' => array('aggregate' => 'count', 'output' => 'MoneyFormat') ) );
    // Aggregate values: 'count', 'sum', 'min', 'max', 'avg'
    //
    //
    public static function GroupBy($array, $cols, $subtotal = false)
    {
        global $profiler;
        $profiler->startTimer('Group()');

        $group_by_cols = array();
        $aggregate_cols = array();
        if (is_array($cols)) {
            foreach ($cols as $col => $aggregate) {
                if (is_string($aggregate) and $aggregate != '') {
                    $aggregate_cols[$col] = $aggregate;
                } else {
                    $group_by_cols[$col] = $col; //Use $col as the key so we can use isset() instead of in_array() later on.
                    //$sort_by_cols[$col] = SORT_ASC;
                }
            }
        }
        //Debug::Arr( $group_by_cols, 'Group By Columns: ', __FILE__, __LINE__, __METHOD__, 10);
        //Debug::Arr( $aggregate_cols, 'Aggregate Columns: ', __FILE__, __LINE__, __METHOD__, 10);

        //Sort the array by group_by_col first, ONLY if subtotal mode, because order matters for merging the subtotal rows back into the main array.
        //The array should be sorted before subtotaling is done though, so we either need to pass the final sorting array here, or don't sort at all.
        //if ( $subtotal == 1 AND count($sort_by_cols) > 0 ) {
        //	$array = Sort::arrayMultiSort( $array, $sort_by_cols );
        //}

        $retarr = array();
        $row_map = array();
        if (is_array($array)) {
            $i = 0;
            foreach ($array as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $group_by_key_val = null;
                if ($subtotal == 2) {
                    $group_by_key_val = 0; //Total the entire array.
                } else {
                    foreach ($group_by_cols as $group_by_element) {
                        if (isset($row[$group_by_element])) {
                            //Check if the value is an array with a 'sort' column, ie: array('sort' => 12345678, 'display' => '01-Jan-10' )
                            if (is_array($row[$group_by_element]) and isset($row[$group_by_element]['sort'])) {
                                $group_by_key_val .= $row[$group_by_element]['sort'];
                            } else {
                                $group_by_key_val .= $row[$group_by_element];
                            }
                        }
                    }
                }
                //Debug::Text('Group By Key Val: '. $group_by_key_val, __FILE__, __LINE__, __METHOD__, 10);

                if (!isset($retarr[$group_by_key_val])) {
                    $retarr[$group_by_key_val] = array();
                }

                //Map the last row that each group_by_key_val was seen. Assume that the array is properly sorted first of course.
                //Suffix '_' to the end so we can do an array_merge and ksort( $arr, SORT_STRING).
                //Although for creating overall totals we will likely need to do that before we merge in any sub-totals.
                $row_map[$group_by_key_val] = $i . '_';

                foreach ($row as $key => $val) {
                    //Debug::text(' aKey: '. $key .' Value: '. $val, __FILE__, __LINE__, __METHOD__, 10);
                    if (isset($group_by_cols[$key])) {
                        //Only include a single key => value pair for the grouped columns to save memory.
                        //If we are subtotaling, ignore all static columns.
                        //Keep all columns even when sub-totalling so we can provide more information regarding the sub-total itself.
                        if (!isset($retarr[$group_by_key_val][$key])) {
                            $retarr[$group_by_key_val][$key] = $val;
                        }
                    } elseif (isset($aggregate_cols[$key])) {
                        $retarr[$group_by_key_val][$key][] = $val;
                    } // else { //Ignore data that isn't in grouping or aggregate.
                }

                $i++;
            }
        }

        //Substitude group_by_key_val with sparse row values so we know where to insert totals within the main array if sub-totaling.
        //Debug::Arr($row_map, ' Row Map: ', __FILE__, __LINE__, __METHOD__, 10);
        if ($subtotal == 1) {
            if (is_array($retarr) and is_array($row_map)) {
                foreach ($row_map as $key => $count) {
                    $retarr[$count] = $retarr[$key];
                    $retarr[$count]['_subtotal'] = true;
                    unset($retarr[$key]);
                }
            }
        }

        if (is_array($retarr) and count($aggregate_cols) > 0) {
            foreach ($retarr as $i => $row) {
                foreach ($row as $key => $val) {
                    //Debug::text(' bKey: '. $key .' Value: '. $val, __FILE__, __LINE__, __METHOD__, 10);
                    if (isset($aggregate_cols[$key])) {
                        //Debug::Arr($aggregate_cols[$key], 'Aggregate MetaData: ', __FILE__, __LINE__, __METHOD__, 10);
                        //Debug::Arr($val, 'Aggregate Data: ', __FILE__, __LINE__, __METHOD__, 10);
                        $retarr[$i][$key] = self::aggregate($val, $aggregate_cols[$key], $subtotal);
                        //Debug::Arr($retarr[$i][$key], 'Aggregate Result: ', __FILE__, __LINE__, __METHOD__, 10);
                    }
                }
            }
        }

        $profiler->stopTimer('Group()');

        if ($subtotal == 1) {
            return $retarr;
        } else {
            return array_values($retarr); //Use array_values() to reindex array starting at 0.
        }
    }

    //Need to use bcmath for large numbers, especially on 32bit PHP installs.

    public static function aggregate($array, $type, $subtotal = false)
    {
        switch ($type) {
            default:
            case 'sum':
                //$retarr = array_sum($array);
                $retarr = self::arraySum($array);
                break;
            case 'average':
            case 'avg':
                //$retarr = ( array_sum($array) / count($array) );
                $retarr = (self::arraySum($array) / count($array));
                break;
            case 'minimum':
            case 'min':
                $retarr = min($array);
                break;
            case 'min_not_null':
                $retarr = self::MinNotNull($array);
                break;
            case 'maximum':
            case 'max':
            case 'max_not_null':
                $retarr = max($array);
                break;
            case 'first':
                reset($array);
                $retarr = current($array);
                break;
            case 'last':
                end($array);
                $retarr = current($array);
                break;
            case 'count':
                $retarr = count($array);
                break;
            case 'concat':
                if ($subtotal == false) {
                    //Pass $array through array_filter() without any callback, as that will remove any FALSE/NULL values, elimating strings like: ' -- -- -- -- '
                    $retarr = implode(' -- ', array_filter($array)); //Using \n or <br> doesn't work properly on HTML/PDF reports, so just use " -- " for now.
                } else {
                    //Don't concat for subtotal/total rows, as there is no point for text/string columns.
                    $retarr = '';
                }
                break;
        }
        //Debug::Arr($array, 'Aggregate Raw Data: ', __FILE__, __LINE__, __METHOD__, 10);
        //Debug::Arr($retarr, 'Aggregate Result: Aggregate: '. $type, __FILE__, __LINE__, __METHOD__, 10);

        return $retarr;
    }

    public static function arraySum($array)
    {
        $retval = 0;
        foreach ($array as $value) {
            $retval = bcadd($retval, $value);
        }

        return $retval;
    }

    public static function MinNotNull($values)
    {
        return @min(array_diff(array_map('intval', $values), array(0))); //If array() OR array(0) is passed in it could cause a PHP warning for min()
    }
}
