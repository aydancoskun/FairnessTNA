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
class Misc
{
    /*
        this method assumes that the form has one or more
        submit buttons and that they are named according
        to this scheme:

        <input type="submit" name="submit:command" value="some value">

        This is useful for identifying which submit button actually
        submitted the form.
    */
    public static function findSubmitButton($prefix = 'action')
    {
        // search post vars, then get vars.
        $queries = array($_POST, $_GET);
        foreach ($queries as $query) {
            foreach ($query as $key => $value) {
                //Debug::Text('Key: '. $key .' Value: '. $value, __FILE__, __LINE__, __METHOD__, 10);
                $newvar = explode(':', $key, 2);
                //Debug::Text('Explode 0: '. $newvar[0] .' 1: '. $newvar[1], __FILE__, __LINE__, __METHOD__, 10);
                if (isset($newvar[0]) and isset($newvar[1]) and $newvar[0] === $prefix) {
                    $val = $newvar[1];

                    // input type=image stupidly appends _x and _y.
                    if (substr($val, (strlen($val) - 2)) === '_x') {
                        $val = substr($val, 0, (strlen($val) - 2));
                    }

                    //Debug::Text('Found Button: '. $val, __FILE__, __LINE__, __METHOD__, 10);
                    return strtolower($val);
                }
            }
            unset($value); //code standards
        }

        return null;
    }

    public static function getSortDirectionArray($text_keys = false)
    {
        if ($text_keys === true) {
            return array('asc' => 'ASC', 'desc' => 'DESC');
        } else {
            return array(1 => 'ASC', -1 => 'DESC');
        }
    }

    //This function totals arrays where the data wanting to be totaled is deep in a multi-dimentional array.
    //Usually a row array just before its passed to smarty.
    public static function ArrayAssocSum($array, $element = null, $decimals = null, $include_non_numeric = false)
    {
        if (!is_array($array)) {
            return false;
        }

        $retarr = array();
        $totals = array();

        foreach ($array as $value) {
            if (isset($element) and isset($value[$element])) {
                foreach ($value[$element] as $sum_key => $sum_value) {
                    if (!isset($totals[$sum_key])) {
                        $totals[$sum_key] = 0;
                    }
                    $totals[$sum_key] += $sum_value;
                }
            } else {
                //Debug::text(' Array Element not set: ', __FILE__, __LINE__, __METHOD__, 10);
                foreach ($value as $sum_key => $sum_value) {
                    if (!isset($totals[$sum_key])) {
                        $totals[$sum_key] = 0;
                    }
                    if (!is_numeric($sum_value)) {
                        if ($include_non_numeric == true and $sum_value != '') {
                            $totals[$sum_key] = $sum_value;
                        }
                    } else {
                        $totals[$sum_key] += $sum_value;
                    }
                    //Debug::text(' Sum: '. $totals[$sum_key] .' Key: '. $sum_key .' This Value: '. $sum_value, __FILE__, __LINE__, __METHOD__, 10);
                }
            }
        }

        //format totals
        if ($decimals !== null) {
            foreach ($totals as $retarr_key => $retarr_value) {
                //Debug::text(' Number Formatting: '. $retarr_value, __FILE__, __LINE__, __METHOD__, 10);
                $retarr[$retarr_key] = number_format($retarr_value, $decimals, '.', '');
            }
        } else {
            return $totals;
        }
        unset($totals);

        return $retarr;
    }

    //This function is similar to a SQL group by clause, only its done on a AssocArray
    //Pass it a row array just before you send it to smarty.
    public static function ArrayGroupBy($array, $group_by_elements, $ignore_elements = array())
    {
        if (!is_array($group_by_elements)) {
            $group_by_elements = array($group_by_elements);
        }

        if (isset($ignore_elements) and is_array($ignore_elements)) {
            foreach ($group_by_elements as $group_by_element) {
                //Remove the group by element from the ignore elements.
                unset($ignore_elements[$group_by_element]);
            }
        }

        $retarr = array();
        if (is_array($array)) {
            foreach ($array as $row) {
                $group_by_key_val = null;
                foreach ($group_by_elements as $group_by_element) {
                    if (isset($row[$group_by_element])) {
                        $group_by_key_val .= $row[$group_by_element];
                    }
                }
                //Debug::Text('Group By Key Val: '. $group_by_key_val, __FILE__, __LINE__, __METHOD__, 10);

                if (!isset($retarr[$group_by_key_val])) {
                    $retarr[$group_by_key_val] = array();
                }

                foreach ($row as $key => $val) {
                    //Debug::text(' Key: '. $key .' Value: '. $val, __FILE__, __LINE__, __METHOD__, 10);
                    if (in_array($key, $group_by_elements)) {
                        $retarr[$group_by_key_val][$key] = $val;
                    } elseif (!in_array($key, $ignore_elements)) {
                        if (isset($retarr[$group_by_key_val][$key])) {
                            $retarr[$group_by_key_val][$key] = Misc::MoneyFormat(bcadd($retarr[$group_by_key_val][$key], $val), false);
                            //Debug::text(' Adding Value: '. $val .' For: '. $retarr[$group_by_key_val][$key], __FILE__, __LINE__, __METHOD__, 10);
                        } else {
                            //Debug::text(' Setting Value: '. $val, __FILE__, __LINE__, __METHOD__, 10);
                            $retarr[$group_by_key_val][$key] = $val;
                        }
                    }
                }
            }
        }

        return $retarr;
    }

    /**
     * Just a number format that looks like currency without currency symbol
     * can maybe be replaced by TTi18n::numberFormat()
     *
     * @param $value
     * @param bool $pretty
     * @return string
     */
    public static function MoneyFormat($value, $pretty = true)
    {
        if ($pretty === true) {
            $thousand_sep = TTi18n::getThousandsSymbol();
        } else {
            $thousand_sep = '';
        }

        return number_format((float)$value, 2, TTi18n::getDecimalSymbol(), $thousand_sep);
    }

    public static function ArrayAvg($arr)
    {
        if ((!is_array($arr)) or (!count($arr) > 0)) {
            return false;
        }

        return (array_sum($arr) / count($arr));
    }

    public static function prependArray($prepend_arr, $arr)
    {
        if (!is_array($prepend_arr) and is_array($arr)) {
            return $arr;
        } elseif (is_array($prepend_arr) and !is_array($arr)) {
            return $prepend_arr;
        } elseif (!is_array($prepend_arr) and !is_array($arr)) {
            return false;
        }

        $retarr = $prepend_arr;

        foreach ($arr as $key => $value) {
            //Don't overwrite entries from the prepend array.
            if (!isset($retarr[$key])) {
                $retarr[$key] = $value;
            }
        }

        return $retarr;
    }

    public static function arrayColumn($input = null, $columnKey = null, $indexKey = null)
    {
        if (function_exists('array_column')) {
            return array_column((array)$input, $columnKey, $indexKey);
        } else {
            // Using func_get_args() in order to check for proper number of
            // parameters and trigger errors exactly as the built-in array_column()
            // does in PHP 5.5.
            $argc = func_num_args();
            $params = func_get_args();

            $params[0] = (array)$params[0];

            if ($argc < 2) {
                trigger_error('array_column() expects at least 2 parameters, ' . $argc . ' given', E_USER_WARNING);
                return null;
            }

            if (!is_array($params[0])) {
                trigger_error('array_column() expects parameter 1 to be array, ' . gettype($params[0]) . ' given', E_USER_WARNING);
                return null;
            }

            if (!is_int($params[1])
                and !is_float($params[1])
                and !is_string($params[1])
                and $params[1] !== null
                and !(is_object($params[1]) and method_exists($params[1], '__toString'))
            ) {
                trigger_error('array_column(): The column key should be either a string or an integer', E_USER_WARNING);
                return false;
            }

            if (isset($params[2])
                and !is_int($params[2])
                and !is_float($params[2])
                and !is_string($params[2])
                and !(is_object($params[2]) and method_exists($params[2], '__toString'))
            ) {
                trigger_error('array_column(): The index key should be either a string or an integer', E_USER_WARNING);
                return false;
            }

            $paramsInput = $params[0];
            $paramsColumnKey = ($params[1] !== null) ? (string)$params[1] : null;

            $paramsIndexKey = null;
            if (isset($params[2])) {
                if (is_float($params[2]) or is_int($params[2])) {
                    $paramsIndexKey = (int)$params[2];
                } else {
                    $paramsIndexKey = (string)$params[2];
                }
            }

            $resultArray = array();

            foreach ($paramsInput as $row) {
                $key = $value = null;
                $keySet = $valueSet = false;

                if ($paramsIndexKey !== null and array_key_exists($paramsIndexKey, $row)) {
                    $keySet = true;
                    $key = (string)$row[$paramsIndexKey];
                }

                if ($paramsColumnKey === null) {
                    $valueSet = true;
                    $value = $row;
                } elseif (is_array($row) and array_key_exists($paramsColumnKey, $row)) {
                    $valueSet = true;
                    $value = $row[$paramsColumnKey];
                }

                if ($valueSet) {
                    if ($keySet) {
                        $resultArray[$key] = $value;
                    } else {
                        $resultArray[] = $value;
                    }
                }
            }

            return $resultArray;
        }
    }

    /*
        When passed an array of input_keys, and an array of output_key => output_values,
        this function will return all the output_key => output_value pairs where
        input_key == output_key
    */

    public static function flattenArray($array, $preserve = false, $r = array())
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $k => $v) {
                    if (is_array($v)) {
                        $tmp = $v;
                        unset($value[$k]);
                    }
                }

                if ($preserve) {
                    $r[$key] = $value;
                } else {
                    $r[] = $value;
                }
            }

            $r = isset($tmp) ? self::flattenArray($tmp, $preserve, $r) : $r;
        }

        return $r;
    }

    /*
        When passed an associative array from a ListFactory, ie:
        array(	0 => array( <...Data ..> ),
                1 => array( <...Data ..> ),
                2 => array( <...Data ..> ),
                ... )
        this function will return an associative array of only the key=>value
        pairs that intersect across all rows.

    */

    public static function arrayIntersectByKey($keys, $options)
    {
        if (is_array($keys) and is_array($options)) {
            $retarr = array();
            foreach ($keys as $key) {
                if (isset($options[$key]) and $key !== false) { //Ignore boolean FALSE, so the Root group isn't always selected.
                    $retarr[$key] = $options[$key];
                }
            }

            if (empty($retarr) == false) {
                return $retarr;
            }
        }

        //Return NULL because if we return FALSE smarty will enter a
        //"blank" option into select boxes.
        return null;
    }

    /*
        Returns all the output_key => output_value pairs where
        the input_keys are not present in output array keys.

    */

    public static function arrayIntersectByRow($rows)
    {
        if (!is_array($rows)) {
            return false;
        }

        if (count($rows) < 2) {
            return false;
        }

        //Debug::Arr($rows, 'Intersected/Common Data', __FILE__, __LINE__, __METHOD__, 10);
        $retval = false;
        if (isset($rows[0])) {
            $retval = @call_user_func_array('array_intersect_assoc', $rows);
            // The '@' cannot be removed, Some of the array_* functions that compare elements in
            // multiple arrays do so by (string)$elem1 === (string)$elem2 If $elem1 or $elem2 is an
            // array, then the array to string notice is thrown, $rows is an array and its every
            // element is also an array, but its element may have one element is still an array, if
            // so, the array to string notice will be produced. this case may be like this:
            //	array(
            //		array('a'), array(
            //			array('a'),
            //		),
            //	);
            // Put a "@" in front to prevent the error, otherwise, the Flex will not work properly.

            //Debug::Arr($retval, 'Intersected/Common Data', __FILE__, __LINE__, __METHOD__, 10);
        }

        return $retval;
    }

    //This only merges arrays where the array keys must already exist.

    public static function arrayDiffByKey($keys, $options)
    {
        if (is_array($keys) and is_array($options)) {
            $retarr = array();
            foreach ($options as $key => $value) {
                if (!in_array($key, $keys, true)) { //Use strict we ignore boolean FALSE, so the Root group isn't always selected.
                    $retarr[$key] = $options[$key];
                }
            }
            unset($value); //code standards

            if (empty($retarr) == false) {
                return $retarr;
            }
        }

        //Return NULL because if we return FALSE smarty will enter a
        //"blank" option into select boxes.
        return null;
    }

    //Merges arrays with overwriting whereas PHP standard array_merge_recursive does not overwrites but combines.

    public static function arrayMergeRecursiveDistinct(array $array1, array $array2)
    {
        $merged = $array1;

        foreach ($array2 as $key => &$value) {
            if (is_array($value) and isset($merged[$key]) and is_array($merged[$key])) {
                $merged[$key] = self::arrayMergeRecursiveDistinct($merged[$key], $value);
            } else {
                $merged[$key] = $value;
            }
        }

        return $merged;
    }

    public static function arrayMergeRecursive(array $array1, array $array2)
    {
        foreach ($array2 as $key => $value) {
            if (array_key_exists($key, $array1) and is_array($value)) {
                $array1[$key] = self::arrayMergeRecursive($array1[$key], $array2[$key]);
            } else {
                $array1[$key] = $value;
            }
        }

        return $array1;
    }

    public static function arrayDiffAssocRecursive($array1, $array2)
    {
        $difference = array();
        if (is_array($array1)) {
            foreach ($array1 as $key => $value) {
                if (is_array($value)) {
                    if (!isset($array2[$key])) {
                        $difference[$key] = $value;
                    } elseif (!is_array($array2[$key])) {
                        $difference[$key] = $value;
                    } else {
                        $new_diff = self::arrayDiffAssocRecursive($value, $array2[$key]);
                        if ($new_diff !== false) {
                            $difference[$key] = $new_diff;
                        }
                    }
                } elseif (!isset($array2[$key]) or $array2[$key] != $value) {
                    $difference[$key] = $value;
                }
            }
        }

        if (empty($difference)) {
            return false;
        }

        return $difference;
    }

    //Adds prefix to all array keys, mainly for reportings and joining array data together to avoid conflicting keys.

    public static function arrayCommonValue($arr)
    {
        $arr_count = array_count_values($arr);
        arsort($arr_count);
        return key($arr_count);
    }

    //Removes prefix to all array keys, mainly for reportings and joining array data together to avoid conflicting keys.

    public static function addKeyPrefix($prefix, $arr, $ignore_elements = null)
    {
        if (is_array($arr)) {
            $retarr = array();
            foreach ($arr as $key => $value) {
                if (!is_array($ignore_elements) or (is_array($ignore_elements) and !in_array($key, $ignore_elements))) {
                    $retarr[$prefix . $key] = $value;
                } else {
                    $retarr[$key] = $value;
                }
            }

            if (empty($retarr) == false) {
                return $retarr;
            }
        }

        //Don't return FALSE, as this can create array( 0 => FALSE ) arrays if we then cast it to an array, which corrupts some report data.
        // Instead just return the original variable that was passed in (likely NULL)
        return $arr;
    }

    //Adds sort prefixes to an array maintaining the original order. Primarily used because Flex likes to reorded arrays with string keys.

    public static function removeKeyPrefix($prefix, $arr, $ignore_elements = null)
    {
        if (is_array($arr)) {
            $retarr = array();
            foreach ($arr as $key => $value) {
                if (!is_array($ignore_elements) or (is_array($ignore_elements) and !in_array($key, $ignore_elements))) {
                    $retarr[self::strReplaceOnce($prefix, '', $key)] = $value;
                } else {
                    $retarr[$key] = $value;
                }
            }

            if (empty($retarr) == false) {
                return $retarr;
            }
        }

        return false;
    }

    //Removes sort prefixes from an array.

    public static function strReplaceOnce($str_pattern, $str_replacement, $string)
    {
        if (strpos($string, $str_pattern) !== false) {
            return substr_replace($string, $str_replacement, strpos($string, $str_pattern), strlen($str_pattern));
        }

        return $string;
    }

    public static function addSortPrefix($arr, $begin_counter = 1)
    {
        $retarr = array();
        $i = $begin_counter;
        foreach ($arr as $key => $value) {
            $sort_prefix = null;
            if (substr($key, 0, 1) != '-') {
                $sort_prefix = '-' . str_pad($i, 4, 0, STR_PAD_LEFT) . '-';
            }
            $retarr[$sort_prefix . $key] = $value;
            $i++;
        }

        if (empty($retarr) == false) {
            return $retarr;
        }

        return false;
    }

    public static function trimSortPrefix($value, $trim_arr_value = false)
    {
        $retval = array();
        if (is_array($value) and count($value) > 0) {
            foreach ($value as $key => $val) {
                if ($trim_arr_value == true) {
                    $retval[$key] = preg_replace('/^-[0-9]{3,4}-/i', '', $val);
                } else {
                    $retval[preg_replace('/^-[0-9]{3,4}-/i', '', $key)] = $val;
                }
            }
        } else {
            $retval = preg_replace('/^-[0-9]{3,4}-/i', '', $value);
        }

        if (empty($retval) == false) {
            return $retval;
        }

        return $value;
    }

    //This function helps sending binary data to the client for saving/viewing as a file.

    public static function APIFileDownload($file_name, $type, $data)
    {
        if ($file_name == '' or $data == '') {
            return false;
        }

        if (is_array($data)) {
            return false;
        }

        $size = strlen($data);

        self::FileDownloadHeader($file_name, $type, $size);
        echo $data;
        //Don't return any TRUE/FALSE here as it could end up in the file.
    }

    public static function FileDownloadHeader($file_name, $type, $size)
    {
        if ($file_name == '' or $size == '') {
            return false;
        }

        Header('Content-Type: ' . $type);

        $agent = (isset($_SERVER['HTTP_USER_AGENT'])) ? trim($_SERVER['HTTP_USER_AGENT']) : '';
        if (preg_match('|MSIE ([0-9.]+)|', $agent, $version) or preg_match('|Internet Explorer/([0-9.]+)|', $agent, $version)) {
            //header('Content-Type: application/x-msdownload');
            if ($version == '5.5') {
                Header('Content-Disposition: filename="' . $file_name . '"');
            } else {
                Header('Content-Disposition: attachment; filename="' . $file_name . '"');
            }
        } else {
            //Header('Content-disposition: inline; filename='.$file_name); //Displays document inline (in browser window) if available
            Header('Content-Disposition: attachment; filename="' . $file_name . '"'); //Forces document to download
        }

        Header('Content-Length: ' . $size);

        return true;
    }

    /**
     * value should be a float and not a string. be sure to run this before TTi18n currency or number formatter due to foreign numeric formatting for decimal being a comma.
     * @param $value float
     * @param int $minimum_decimals
     * @return string
     */
    public static function removeTrailingZeros($value, $minimum_decimals = 2)
    {
        //Remove trailing zeros after the decimal, leave a minimum of X though.
        //*NOTE: This should always be passed in a float, so we don't need to worry about locales or TTi18n::getDecimalSymbol().
        //       If you are running into problems traced to here, try casting to float first.
        //		 If a casted float value is float(50), there won't be a decimal place, so make sure we handle those cases too.
        if (is_float($value) or strpos($value, '.') !== false) {
            $trimmed_value = (float)$value;
            if (strpos($trimmed_value, '.') !== false) {
                $tmp_minimum_decimals = strlen((int)strrev($trimmed_value));
            } else {
                $tmp_minimum_decimals = 0;
            }

            if ($tmp_minimum_decimals > $minimum_decimals) {
                $minimum_decimals = $tmp_minimum_decimals;
            }

            return number_format($trimmed_value, $minimum_decimals, '.', '');
        }

        return $value;
    }

    //Removes vowels from the string always keeping the first and last letter.

    public static function TruncateString($str, $length, $start = 0, $abbreviate = false)
    {
        if (strlen($str) > $length) {
            if ($abbreviate == true) {
                //Try abbreviating it first.
                $retval = trim(substr(self::abbreviateString($str), $start, $length));
                if (strlen($retval) > $length) {
                    $retval .= '...';
                }
            } else {
                $retval = trim(substr(trim($str), $start, $length)) . '...';
            }
        } else {
            $retval = $str;
        }

        return $retval;
    }

    public static function abbreviateString($str)
    {
        $vowels = array('a', 'e', 'i', 'o', 'u');

        $retarr = array();
        $words = explode(' ', trim($str));
        if (is_array($words)) {
            foreach ($words as $word) {
                $first_letter_in_word = substr($word, 0, 1);
                $last_letter_in_word = substr($word, -1, 1);
                $word = str_ireplace($vowels, '', trim($word));
                if (substr($word, 0, 1) != $first_letter_in_word) {
                    $word = $first_letter_in_word . $word;
                }
                if (substr($word, -1, 1) != $last_letter_in_word) {
                    $word .= $last_letter_in_word;
                }
                $retarr[] = $word;
            }

            return implode(' ', $retarr);
        }

        return false;
    }

    public static function HumanBoolean($bool)
    {
        if ($bool == true) {
            return 'Yes';
        } else {
            return 'No';
        }
    }

    public static function getBeforeDecimal($float)
    {
        //$split_float = explode(TTi18n::getDecimalSymbol(), $float);
        $split_float = explode('.', $float);
        return (int)$split_float[0];
    }

    public static function getAfterDecimal($float, $format_number = true)
    {
        if ($format_number == true) {
            $float = Misc::MoneyFormat($float, false);
        }

        //$split_float = explode(TTi18n::getDecimalSymbol(), $float);
        $split_float = explode('.', $float);
        if (isset($split_float[1])) {
            return (int)$split_float[1];
        } else {
            return 0;
        }
    }

    public static function removeDecimal($value)
    {
        return str_replace('.', '', number_format($value, 2, '.', ''));
    }

    //Encode integer to a alphanumeric value that is reversible.
    public static function encodeInteger($int)
    {
        if ($int != '') {
            return strtoupper(base_convert(strrev(str_pad($int, 11, 0, STR_PAD_LEFT)), 10, 36));
        }

        return $int;
    }

    public static function decodeInteger($str, $max = 2147483646)
    {
        $retval = (int)str_pad(strrev(base_convert($str, 36, 10)), 11, 0, STR_PAD_RIGHT);
        if ($retval > $max) { //This helps prevent out of range errors in SQL queries.
            Debug::Text('Decoding string to int, exceeded max: ' . $str . ' Max: ' . $max, __FILE__, __LINE__, __METHOD__, 10);
            $retval = 0;
        }

        return $retval;
    }

    public static function calculatePercent($current, $maximum, $precision = 0)
    {
        if ($maximum == 0) {
            return 100;
        }

        $percent = round((($current / $maximum) * 100), (int)$precision);

        if ($precision == 0) {
            $percent = (int)$percent;
        }

        return $percent;
    }

    //Takes an array with columns, and a 2nd array with column names to sum.
    public static function sumMultipleColumns($data, $sum_elements)
    {
        if (!is_array($data)) {
            return false;
        }

        if (!is_array($sum_elements)) {
            return false;
        }

        $retval = 0;

        foreach ($sum_elements as $sum_element) {
            if (isset($data[$sum_element])) {
                $retval = bcadd($retval, $data[$sum_element]);
                //Debug::Text('Found Element in Source Data: '. $sum_element .' Retval: '. $retval, __FILE__, __LINE__, __METHOD__, 10);
            }
        }

        return $retval;
    }

    public static function calculateIncludeExcludeAmount($amount, $element, $include_elements = array(), $exclude_elements = array())
    {
        //Make sure the element isnt in both include and exclude.
        if (in_array($element, $include_elements) and !in_array($element, $exclude_elements)) {
            return $amount;
        } elseif (in_array($element, $exclude_elements) and !in_array($element, $include_elements)) {
            return ($amount * -1);
        } else {
            return 0;
        }
    }

    public static function calculateMultipleColumns($data, $include_elements = array(), $exclude_elements = array())
    {
        if (!is_array($data)) {
            return false;
        }

        $retval = 0;

        if (is_array($include_elements)) {
            foreach ($include_elements as $include_element) {
                if (isset($data[$include_element])) {
                    $retval = bcadd($retval, $data[$include_element]);
                    //Debug::Text('Found Element in Source Data: '. $sum_element .' Retval: '. $retval, __FILE__, __LINE__, __METHOD__, 10);
                }
            }
        }

        if (is_array($exclude_elements)) {
            foreach ($exclude_elements as $exclude_element) {
                if (isset($data[$exclude_element])) {
                    $retval = bcsub($retval, $data[$exclude_element]);
                    //Debug::Text('Found Element in Source Data: '. $sum_element .' Retval: '. $retval, __FILE__, __LINE__, __METHOD__, 10);
                }
            }
        }

        return $retval;
    }

    public static function getPointerFromArray($array, $element, $start = 1)
    {
        //Debug::Arr($array, 'Source Array: ', __FILE__, __LINE__, __METHOD__, 10);
        //Debug::Text('Searching for Element: '. $element, __FILE__, __LINE__, __METHOD__, 10);
        $keys = array_keys($array);
        //Debug::Arr($keys, 'Source Array Keys: ', __FILE__, __LINE__, __METHOD__, 10);

        //Debug::Text($keys, 'Source Array Keys: ', __FILE__, __LINE__, __METHOD__, 10);
        $key = array_search($element, $keys);

        if ($key !== false) {
            $key = ($key + $start);
        }

        //Debug::Arr($key, 'Result: ', __FILE__, __LINE__, __METHOD__, 10);
        return $key;
    }

    public static function AdjustXY($coord, $adjust_coord)
    {
        return ($coord + $adjust_coord);
    }

    // Static class, static function. avoid PHP strict error.
    public static function writeBarCodeFile($file_name, $num, $print_text = true, $height = 60)
    {
        if (!class_exists('Image_Barcode')) {
            require_once(Environment::getBasePath() . '/classes/Image_Barcode/Barcode.php');
        }

        ob_start();
        $ib = new Image_Barcode();
        $ib->draw($num, 'code128', 'png', false, $print_text, $height);
        $ob_contents = ob_get_contents();
        ob_end_clean();

        if (file_put_contents($file_name, $ob_contents) > 0) {
            //echo "Writing file successfull<Br>\n";
            return true;
        } else {
            //echo "Error writing file<Br>\n";
            return false;
        }
    }

    public static function hex2rgb($hex, $asString = true)
    {
        // strip off any leading #
        if (0 === strpos($hex, '#')) {
            $hex = substr($hex, 1);
        } elseif (0 === strpos($hex, '&H')) {
            $hex = substr($hex, 2);
        }

        // break into hex 3-tuple
        $cutpoint = (ceil((strlen($hex) / 2)) - 1);
        $rgb = explode(':', wordwrap($hex, $cutpoint, ':', $cutpoint), 3);

        // convert each tuple to decimal
        $rgb[0] = (isset($rgb[0]) ? hexdec($rgb[0]) : 0);
        $rgb[1] = (isset($rgb[1]) ? hexdec($rgb[1]) : 0);
        $rgb[2] = (isset($rgb[2]) ? hexdec($rgb[2]) : 0);

        return ($asString ? "{$rgb[0]} {$rgb[1]} {$rgb[2]}" : $rgb);
    }

    public static function Array2CSV($data, $columns = null, $ignore_last_row = true, $include_header = true, $eol = "\n")
    {
        if (is_array($data) and count($data) > 0
            and is_array($columns) and count($columns) > 0
        ) {
            if ($ignore_last_row === true) {
                array_pop($data);
            }

            //Header
            if ($include_header == true) {
                $row_header = array();
                foreach ($columns as $column_name) {
                    $row_header[] = $column_name;
                }
                $out = '"' . implode('","', $row_header) . '"' . $eol;
            } else {
                $out = null;
            }

            foreach ($data as $rows) {
                $row_values = array();
                foreach ($columns as $column_key => $column_name) {
                    if (isset($rows[$column_key])) {
                        $row_values[] = str_replace("\"", "\"\"", $rows[$column_key]);
                    } else {
                        //Make sure we insert blank columns to keep proper order of values.
                        $row_values[] = null;
                    }
                }

                $out .= '"' . implode('","', $row_values) . '"' . $eol;
                unset($row_values);
            }

            return $out;
        }

        return false;
    }

    public static function Array2XML($data, $columns = null, $column_format = null, $ignore_last_row = true, $include_xml_header = false, $root_element_name = 'data', $row_element_name = 'row')
    {
        if (is_array($data) and count($data) > 0
            and is_array($columns) and count($columns) > 0
        ) {
            if ($ignore_last_row === true) {
                array_pop($data);
            }

            //Debug::Arr($column_format, 'Column Format: ', __FILE__, __LINE__, __METHOD__, 10);

            $out = null;

            if ($include_xml_header == true) {
                $out .= '<?xml version=\'1.0\' encoding=\'ISO-8859-1\'?>' . "\n";
            }

            $out .= '<xsd:schema xmlns:xsd="http://www.w3.org/2001/XMLSchema">' . "\n";
            $out .= '	 <xsd:element name="' . $root_element_name . '">' . "\n";
            $out .= '		 <xsd:complexType>' . "\n";
            $out .= '			 <xsd:sequence>' . "\n";
            $out .= '				 <xsd:element name="' . $row_element_name . '">' . "\n";
            $out .= '					 <xsd:complexType>' . "\n";
            $out .= '						 <xsd:sequence>' . "\n";
            foreach ($columns as $column_key => $column_name) {
                $data_type = 'string';
                if (is_array($column_format) and isset($column_format[$column_key])) {
                    switch ($column_format[$column_key]) {
                        case 'report_date':
                            $data_type = 'string';
                            break;
                        case 'currency':
                        case 'percent':
                        case 'numeric':
                            $data_type = 'decimal';
                            break;
                        case 'time_unit':
                            $data_type = 'decimal';
                            break;
                        case 'date_stamp':
                            $data_type = 'date';
                            break;
                        case 'time':
                            $data_type = 'time';
                            break;
                        case 'time_stamp':
                            $data_type = 'dateTime';
                            break;
                        case 'boolean':
                            $data_type = 'string';
                        default:
                            $data_type = 'string';
                            break;
                    }
                }
                $out .= '							 <xsd:element name="' . $column_key . '" type="xsd:' . $data_type . '"/>' . "\n";
            }
            unset($column_name); //code standards
            $out .= '						 </xsd:sequence>' . "\n";
            $out .= '					 </xsd:complexType>' . "\n";
            $out .= '				 </xsd:element>' . "\n";
            $out .= '			 </xsd:sequence>' . "\n";
            $out .= '		 </xsd:complexType>' . "\n";
            $out .= '	 </xsd:element>' . "\n";
            $out .= '</xsd:schema>' . "\n";

            if ($root_element_name != '') {
                $out .= '<' . $root_element_name . '>' . "\n";
            }

            foreach ($data as $rows) {
                $out .= '<' . $row_element_name . '>' . "\n";
                foreach ($columns as $column_key => $column_name) {
                    if (isset($rows[$column_key])) {
                        $out .= '	 <' . $column_key . '>' . $rows[$column_key] . '</' . $column_key . '>' . "\n";
                    }
                }
                $out .= '</' . $row_element_name . '>' . "\n";
            }

            if ($root_element_name != '') {
                $out .= '</' . $root_element_name . '>' . "\n";
            }

            //Debug::Arr($out, 'XML: ', __FILE__, __LINE__, __METHOD__, 10);

            return $out;
        }

        return false;
    }

    public static function Export2XML($factory_arr, $filter_data, $output_file)
    {
        global $global_class_map;

        $global_exclude_arr = array(
            'Factory',
            'FactoryListIterator',
            'SystemSettingFactory',
            'CronJobFactory',
            'CompanyUserCountFactory',

            'HelpFactory',
            'HelpGroupControlFactory',
            'HelpGroupFactory',
            'HierarchyFactory',
            'HierarchyShareFactory',
            'JobUserAllowFactory',
            'JobItemAllowFactory',
            'PolicyGroupAccrualPolicyFactory',
            'PolicyGroupOverTimePolicyFactory',
            'PolicyGroupPremiumPolicyFactory',
            'PolicyGroupRoundIntervalPolicyFactory',
            'ProductTaxPolicyProductFactory',
        );

        $dependency_tree = new DependencyTree();
        $i = 0;
        $global_class_dependancy_map = array();
        foreach ($global_class_map as $class => $file) {
            if (stripos($class, 'Factory') !== false
                and stripos($class, 'API') === false and stripos($class, 'ListFactory') === false and stripos($class, 'Report') === false
                and !in_array($class, $global_exclude_arr)
            ) {
                if (isset($global_class_dependancy_map[$class])) {
                    $dependency_tree->addNode($class, $global_class_dependancy_map[$class], $class, $i);
                } else {
                    $dependency_tree->addNode($class, array(), $class, $i);
                }
            }
            $i++;
        }
        unset($file); //code standards
        $ordered_factory_arr = $dependency_tree->getAllNodesInOrder();
        //Debug::Arr($ordered_factory_arr, 'Ordered Factory List: ', __FILE__, __LINE__, __METHOD__, 10);

        if (is_array($factory_arr) and count($factory_arr) > 0) {
            Debug::Arr($factory_arr, 'Factory Filter: ', __FILE__, __LINE__, __METHOD__, 10);
            $filtered_factory_arr = array();
            foreach ($ordered_factory_arr as $factory) {
                if (in_array($factory, $factory_arr)) {
                    $filtered_factory_arr[] = $factory;
                } // else { //Debug::Text('Removing factory: '. $factory .' due to filter...', __FILE__, __LINE__, __METHOD__, 10);
            }
        } else {
            Debug::Text('Not filtering factory...', __FILE__, __LINE__, __METHOD__, 10);
            $filtered_factory_arr = $ordered_factory_arr;
        }
        unset($ordered_factory_arr);

        if (isset($filtered_factory_arr) and count($filtered_factory_arr) > 0) {
            @unlink($output_file);
            $fp = bzopen($output_file, 'w');

            Debug::Arr($filtered_factory_arr, 'Filtered/Ordered Factory List: ', __FILE__, __LINE__, __METHOD__, 10);

            Debug::Text('Exporting data...', __FILE__, __LINE__, __METHOD__, 10);
            foreach ($filtered_factory_arr as $factory) {
                $class = str_replace('Factory', 'ListFactory', $factory);
                $lf = new $class;
                Debug::Text('Exporting ListFactory: ' . $factory . ' Memory Usage: ' . memory_get_usage() . ' Peak: ' . memory_get_peak_usage(true), __FILE__, __LINE__, __METHOD__, 10);
                self::ExportListFactory2XML($lf, $filter_data, $fp);
                unset($lf);
            }
            bzclose($fp);
        } else {
            Debug::Text('No data to export...', __FILE__, __LINE__, __METHOD__, 10);
        }
    }

    public static function ExportListFactory2XML($lf, $filter_data, $file_pointer)
    {
        require_once(Environment::getBasePath() . 'classes/pear/XML/Serializer.php');

        $serializer = new XML_Serializer(array(
                XML_SERIALIZER_OPTION_INDENT => '  ',
                XML_SERIALIZER_OPTION_RETURN_RESULT => true,
                'linebreak' => "\n",
                'typeHints' => true,
                'encoding' => 'UTF-8',
                'rootName' => get_parent_class($lf),
            )
        );

        $lf->getByCompanyId($filter_data['company_id']);
        if ($lf->getRecordCount() > 0) {
            Debug::Text('Exporting ' . $lf->getRecordCount() . ' rows...', __FILE__, __LINE__, __METHOD__, 10);
            foreach ($lf as $obj) {
                if (isset($obj->data)) {
                    $result = $serializer->serialize($obj->data);
                    bzwrite($file_pointer, $result . "\n");
                    //Debug::Arr($result, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);
                } else {
                    Debug::Text('Object \'data\' variable does not exist, cant export...', __FILE__, __LINE__, __METHOD__, 10);
                }
            }
            unset($result, $obj, $serializer);
        } else {
            Debug::Text('No rows to export...', __FILE__, __LINE__, __METHOD__, 10);
            return false;
        }

        return true;
    }

    public static function inArrayByKeyAndValue($arr, $search_key, $search_value)
    {
        if (!is_array($arr) and $search_key != '' and $search_value != '') {
            return false;
        }

        //Debug::Text('Search Key: '. $search_key .' Search Value: '. $search_value, __FILE__, __LINE__, __METHOD__, 10);
        //Debug::Arr($arr, 'Hay Stack: ', __FILE__, __LINE__, __METHOD__, 10);

        foreach ($arr as $arr_value) {
            if (isset($arr_value[$search_key])) {
                if ($arr_value[$search_key] == $search_value) {
                    return true;
                }
            }
        }

        return false;
    }

    //This function is used to quickly preset array key => value pairs so we don't
    //have to have so many isset() checks throughout the code.
    public static function preSetArrayValues($arr, $keys, $preset_value = null)
    {
        if (($arr == '' or is_bool($arr) or is_null($arr) or is_array($arr) or is_object($arr)) and is_array($keys)) {
            foreach ($keys as $key) {
                if (is_object($arr)) {
                    if (!isset($arr->$key)) {
                        $arr->$key = $preset_value;
                    }
                } else {
                    if (!isset($arr[$key])) {
                        $arr[$key] = $preset_value;
                    }
                }
            }
        } else {
            Debug::Arr($arr, 'ERROR: Unable to initialize preset array values! Current variable is: ', __FILE__, __LINE__, __METHOD__, 10);
        }

        return $arr;
    }

    public static function countLinesInFile($file)
    {
        ini_set('auto_detect_line_endings', true); //PHP can have problems detecting MAC line endings in some case, this should help solve that.

        $line_count = 0;
        $handle = fopen($file, 'r');
        while (!feof($handle)) {
            $line = fgets($handle, 4096);
            $line_count = ($line_count + substr_count($line, "\n"));
        }

        fclose($handle);

        ini_set('auto_detect_line_endings', false);

        return $line_count;
    }

    public static function parseCSV($file, $head = false, $first_column = false, $delim = ',', $len = 9216, $max_lines = null)
    {
        if (!file_exists($file)) {
            Debug::text('Files does not exist: ' . $file, __FILE__, __LINE__, __METHOD__, 10);
            return false;
        }

        //mime_content_type is being deprecated in PHP, and it doesn't work properly on Windows. So if its not available just accept any file type.
        if (function_exists('mime_content_type')) {
            $mime_type = mime_content_type($file);
            if ($mime_type !== false and !in_array($mime_type, array('text/plain', 'plain/text', 'text/comma-separated-values', 'text/csv', 'application/csv', 'text/anytext', 'text/x-c'))) {
                Debug::text('Invalid MIME TYPE: ' . $mime_type, __FILE__, __LINE__, __METHOD__, 10);
                return false;
            }
        }

        ini_set('auto_detect_line_endings', true); //PHP can have problems detecting MAC line endings in some case, this should help solve that.

        $return = false;
        $handle = fopen($file, 'r');
        if ($head !== false) {
            if ($first_column !== false) {
                while (($header = fgetcsv($handle, $len, $delim)) !== false) {
                    if ($header[0] == $first_column) {
                        //echo "FOUND HEADER!<br>\n";
                        $found_header = true;
                        break;
                    }
                }

                if ($found_header !== true) {
                    return false;
                }
            } else {
                $header = fgetcsv($handle, $len, $delim);
            }
        }

        $i = 1;
        while (($data = fgetcsv($handle, $len, $delim)) !== false) {
            if ($data !== array(null)) { // ignore blank lines
                if ($head and isset($header)) {
                    $row = array();
                    foreach ($header as $key => $heading) {
                        $row[trim($heading)] = (isset($data[$key])) ? $data[$key] : '';
                    }
                    $return[] = $row;
                } else {
                    $return[] = $data;
                }

                if ($max_lines !== null and $max_lines != '' and $i == $max_lines) {
                    break;
                }

                $i++;
            }
        }

        fclose($handle);

        ini_set('auto_detect_line_endings', false);

        return $return;
    }

    public static function importApplyColumnMap($column_map, $csv_arr)
    {
        if (!is_array($column_map)) {
            return false;
        }

        if (!is_array($csv_arr)) {
            return false;
        }

        $retarr = array();
        foreach ($column_map as $map_arr) {
            $fairness_column = $map_arr['fairness_column'];
            $csv_column = $map_arr['csv_column'];
            $default_value = $map_arr['default_value'];

            if (isset($csv_arr[$csv_column]) and $csv_arr[$csv_column] != '') {
                $retarr[$fairness_column] = trim($csv_arr[$csv_column]);
                //echo "NOT using default value: ". $default_value ."\n";
            } elseif ($default_value != '') {
                //echo "using Default value! ". $default_value ."\n";
                $retarr[$fairness_column] = trim($default_value);
            }
        }

        if (empty($retarr) == false) {
            return $retarr;
        }

        return false;
    }

    public static function encrypt($str, $key = null)
    {
        if ($str == '' or $str === false or empty($str)) {
            return false;
        }

        if ($key == null or $key == '') {
            global $config_vars;
            $key = $config_vars['other']['salt'];
        }

        $td = mcrypt_module_open('tripledes', '', 'ecb', '');
        $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
        $max_key_size = mcrypt_enc_get_key_size($td);
        mcrypt_generic_init($td, substr($key, 0, $max_key_size), $iv);

        $encrypted_data = base64_encode(mcrypt_generic($td, trim($str)));

        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);

        return $encrypted_data;
    }

    public static function decrypt($str, $key = null)
    {
        if ($key == null or $key == '') {
            global $config_vars;
            $key = $config_vars['other']['salt'];
        }

        if ($str == '') {
            return false;
        }

        //Check to make sure str is actually base64_encoded.
        if (base64_encode(base64_decode($str, true)) !== $str) {
            Debug::Arr($str, 'ERROR: String is not base64_encoded...', __FILE__, __LINE__, __METHOD__, 10);
            return false;
        }

        $td = mcrypt_module_open('tripledes', '', 'ecb', '');
        $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
        $max_key_size = mcrypt_enc_get_key_size($td);
        mcrypt_generic_init($td, substr($key, 0, $max_key_size), $iv);

        $unencrypted_data = rtrim(mdecrypt_generic($td, base64_decode($str)));

        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);

        return $unencrypted_data;
    }

    public static function getJSArray($values, $name = null, $assoc = false, $object = false)
    {
        if ($name != '' and (bool)$assoc == true) {
            $retval = 'new Array();';
            if (is_array($values) and count($values) > 0) {
                foreach ($values as $key => $value) {
                    $retval .= $name . '[\'' . $key . '\']=\'' . $value . '\';';
                }
            }
        } elseif ($name != '' and (bool)$object == true) { //For multidimensional objects.
            $retval = ' {';
            if (is_array($values) and count($values) > 0) {
                foreach ($values as $key => $value) {
                    $retval .= $key . ': ';
                    if (is_array($value)) {
                        $retval .= '{';
                        foreach ($value as $key2 => $value2) {
                            $retval .= $key2 . ': \'' . $value2 . '\', ';
                        }
                        $retval .= '}, ';
                    } else {
                        $retval .= $key . ': \'' . $value . '\', ';
                    }
                }
            }
            $retval .= '} ';
        } else {
            $retval = 'new Array("';
            if (is_array($values) and count($values) > 0) {
                $retval .= implode('","', $values);
            }
            $retval .= '");';
        }

        return $retval;
    }

    public static function getArrayNeighbors($arr, $key, $neighbor = 'both')
    {
        $neighbor = strtolower($neighbor);
        //Neighor can be: Prev, Next, Both

        $retarr = array('prev' => false, 'next' => false);

        $keys = array_keys($arr);
        $key_indexes = array_flip($keys);

        if ($neighbor == 'prev' or $neighbor == 'both') {
            if (isset($keys[($key_indexes[$key] - 1)])) {
                $retarr['prev'] = $keys[($key_indexes[$key] - 1)];
            }
        }

        if ($neighbor == 'next' or $neighbor == 'both') {
            if (isset($keys[($key_indexes[$key] + 1)])) {
                $retarr['next'] = $keys[($key_indexes[$key] + 1)];
            }
        }
        //next($arr);

        return $retarr;
    }

    //Uses the internal array pointer to get array neighnors.

    public static function getRemoteHTTPFileSize($url)
    {
        if (function_exists('curl_exec')) {
            Debug::Text('Using CURL for HTTP...', __FILE__, __LINE__, __METHOD__, 10);
            $result = false; // Assume failure.

            $curl = curl_init($url);

            // Issue a HEAD request and follow any redirects.
            curl_setopt($curl, CURLOPT_NOBODY, true);
            curl_setopt($curl, CURLOPT_HEADER, true);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($curl, CURLOPT_USERAGENT, APPLICATION_NAME . ' ' . APPLICATION_VERSION);

            curl_exec($curl);
            $size = curl_getinfo($curl, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
            curl_close($curl);

            return $size;
        } else {
            Debug::Text('Using PHP streams for HTTP...', __FILE__, __LINE__, __METHOD__, 10);
            $headers = @get_headers($url, 1);
            if ($headers === false) { //Failure downloading headers from URL.
                return false;
            }

            $headers = array_change_key_case($headers);
            if (isset($headers[0]) and stripos($headers[0], '404 Not Found') !== false) {
                return false;
            }

            $retval = isset($headers['content-length']) ? $headers['content-length'] : false;

            return $retval;
        }
    }

    public static function downloadHTTPFile($url, $file_name)
    {
        if (function_exists('curl_exec')) {
            Debug::Text('Using CURL for HTTP...', __FILE__, __LINE__, __METHOD__, 10);
            // open file to write
            $fp = fopen($file_name, 'w+');

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, false); // Set return transfer to false
            curl_setopt($curl, CURLOPT_BINARYTRANSFER, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($curl, CURLOPT_TIMEOUT, 0); //Never timeout
            curl_setopt($curl, CURLOPT_FILE, $fp); // Write data to local file
            curl_exec($curl);
            curl_close($curl);
            fclose($fp);

            if (file_exists($file_name)) {
                $file_size = filesize($file_name);
                if ($file_size > 0) {
                    return (int)$file_size;
                }
            }

            return false;
        } else {
            Debug::Text('Using PHP streams for HTTP...', __FILE__, __LINE__, __METHOD__, 10);
            return @file_put_contents($file_name, fopen($url, 'r'));
        }
    }

    public static function getEmailDomain()
    {
        global $config_vars;

        if (isset($config_vars['other']['email_domain']) and $config_vars['other']['email_domain'] != '') {
            $domain = $config_vars['other']['email_domain'];
        } else {
            Debug::Text('No From Email Domain set, falling back to regular hostname...', __FILE__, __LINE__, __METHOD__, 10);
            $domain = self::getHostName(false);
        }

        return $domain;
    }

    public static function getHostName($include_port = true)
    {
        global $config_vars;

        $server_port = null;
        if (isset($_SERVER['SERVER_PORT'])) {
            $server_port = ':' . $_SERVER['SERVER_PORT'];
        }

        //Try server hostname/servername first, than fallback on .ini hostname setting.
        //If the admin sets the hostname in the .ini file, always use that, as the servers hostname from the CLI could be incorrect.
        if (isset($config_vars['other']['hostname']) and $config_vars['other']['hostname'] != '') {
            $server_domain = $config_vars['other']['hostname'];
            if (strpos($server_domain, ':') === false) {
                //Add port if its not already specified.
                $server_domain .= $server_port;
            }
        } elseif (isset($_SERVER['HTTP_HOST'])) { //Use HTTP_HOST instead of SERVER_NAME first so it includes any custom ports.
            $server_domain = $_SERVER['HTTP_HOST'];
        } elseif (isset($_SERVER['SERVER_NAME'])) {
            $server_domain = $_SERVER['SERVER_NAME'] . $server_port;
        } elseif (isset($_SERVER['HOSTNAME'])) {
            $server_domain = $_SERVER['HOSTNAME'] . $server_port;
        } else {
            Debug::Text('Unable to determine hostname, falling back to localhost...', __FILE__, __LINE__, __METHOD__, 10);
            $server_domain = 'localhost' . $server_port;
        }

        if ($include_port == false) {
            //strip off port, important for sending emails.
            $server_domain = str_replace($server_port, '', $server_domain);
        }

        return $server_domain;
    }

    public static function getEmailReturnPathLocalPart($email = null)
    {
        global $config_vars;

        if (isset($config_vars['other']['email_return_path_local_part']) and $config_vars['other']['email_return_path_local_part'] != '') {
            $local_part = $config_vars['other']['email_return_path_local_part'];
        } else {
            Debug::Text('No Email Local Part set, falling back to default...', __FILE__, __LINE__, __METHOD__, 10);
            $local_part = self::getEmailLocalPart();
        }

        //In case we need to put the original TO address in the bounce local part.
        //This could be an array in some cases.
//		if ( $email != '' ) {
//			$local_part .= '+';
//		}

        return $local_part;
    }

    public static function getEmailLocalPart()
    {
        global $config_vars;

        if (isset($config_vars['other']['email_local_part']) and $config_vars['other']['email_local_part'] != '') {
            $local_part = $config_vars['other']['email_local_part'];
        } else {
            Debug::Text('No Email Local Part set, falling back to default...', __FILE__, __LINE__, __METHOD__, 10);
            $local_part = 'DoNotReply';
        }

        return $local_part;
    }

    public static function checkValidDomain()
    {
        global $config_vars;
        if (PRODUCTION == true and isset($config_vars['other']['enable_csrf_validation']) and $config_vars['other']['enable_csrf_validation'] == true) {
            //Use HTTP_HOST rather than getHostName() as the same site can be referenced with multiple different host names
            //Especially considering on-site installs that default to 'localhost'
            //If deployment ondemand is set, then we assume SERVER_NAME is correct and revert to using that instead of HTTP_HOST which has potential to be forged.
            //Apache's UseCanonicalName On configuration directive can help ensure the SERVER_NAME is always correct and not masked.
            if (isset($_SERVER['HTTP_HOST'])) {
                $host_name = $_SERVER['HTTP_HOST'];
            } elseif (isset($_SERVER['SERVER_NAME'])) {
                $host_name = $_SERVER['SERVER_NAME'];
            } elseif (isset($_SERVER['HOSTNAME'])) {
                $host_name = $_SERVER['HOSTNAME'];
            } else {
                $host_name = '';
            }

            if (isset($config_vars['other']['hostname']) and $config_vars['other']['hostname'] != '') {
                $search_result = strpos($config_vars['other']['hostname'], $host_name);
                if ($search_result === false or (int)$search_result >= 8) { //Check to see if .ini hostname is found within SERVER_NAME in less than the first 8 chars, so we ignore https://.
                    $redirect_url = Misc::getURLProtocol() . '://' . Misc::getHostName() . Environment::getDefaultInterfaceBaseURL();
                    Debug::Text('Web Server Hostname: ' . $host_name . ' does not match .ini specified hostname: ' . $config_vars['other']['hostname'] . ' Redirect: ' . $redirect_url, __FILE__, __LINE__, __METHOD__, 10);

                    $rl = TTNew('RateLimit');
                    $rl->setID('authentication_' . Misc::getRemoteIPAddress());
                    $rl->setAllowedCalls(5);
                    $rl->setTimeFrame(60); //1 minute

                    sleep(1); //Help prevent fast redirect loops.
                    if ($rl->check() == false) {
                        Debug::Text('ERROR: Excessive redirects... sending to down for maintenance page to stop the loop: ' . Misc::getRemoteIPAddress() . ' for up to 1 minutes...', __FILE__, __LINE__, __METHOD__, 10);
                        Redirect::Page(URLBuilder::getURL(array('exception' => 'domain_redirect_loop'), Environment::getBaseURL() . 'DownForMaintenance.php'));
                    } else {
                        Redirect::Page(URLBuilder::getURL(null, $redirect_url));
                    }
                }
                //else {
                //	Debug::Text( 'Domain matches!', __FILE__, __LINE__, __METHOD__, 10);
                //}
            }
        }

        return true;
    }

    //Checks if the domain the user is seeing in their browser matches the configured domain that should be used.
    //If not we can then do a redirect.

    public static function getURLProtocol()
    {
        $retval = 'http';
        if (Misc::isSSL() == true) {
            $retval .= 's';
        }

        return $retval;
    }

    //Checks refer to help mitigate CSRF attacks.

    public static function isSSL($ignore_force_ssl = false)
    {
        global $config_vars;

        if (isset($config_vars['other']['proxy_protocol_header_name']) and $config_vars['other']['proxy_protocol_header_name'] != '') {
            $header_name = $config_vars['other']['proxy_protocol_header_name']; //'HTTP_X_FORWARDED_PROTO'; //X-Forwarded-Proto;
        }

        //ignore_force_ssl is used for things like cookies where we need to determine if SSL is *currently* in use, vs. if we want it to be used or not.
        if ($ignore_force_ssl == false and isset($config_vars['other']['force_ssl']) and ($config_vars['other']['force_ssl'] == true)) {
            return true;
        } elseif (
            (isset($_SERVER['HTTPS']) and (strtolower($_SERVER['HTTPS']) == 'on' or $_SERVER['HTTPS'] == '1'))
            or
            //Handle load balancer/proxy forwarding with SSL offloading.
            //FIXME: Similar to X_FORWARDED_FOR, this can have a comma and contain multiple protocols.
            (isset($header_name) and isset($_SERVER[$header_name]) and strtolower($_SERVER[$header_name]) == 'https')
        ) {
            return true;
        } elseif (isset($_SERVER['SERVER_PORT']) and ($_SERVER['SERVER_PORT'] == '443')) {
            return true;
        }

        return false;
    }

    public static function getRemoteIPAddress()
    {
        global $config_vars;

        if (isset($config_vars['other']['proxy_ip_address_header_name']) and $config_vars['other']['proxy_ip_address_header_name'] != '') {
            $header_name = $config_vars['other']['proxy_ip_address_header_name'];
        }

        if (isset($header_name) and isset($_SERVER[$header_name]) and $_SERVER[$header_name] != '') {
            //Debug::text('Remote IP: '. $_SERVER['REMOTE_ADDR'] .' Behind Proxy IP: '. $_SERVER[$header_name], __FILE__, __LINE__, __METHOD__, 10);

            //Make sure we handle it if multiple IP addresses are returned due to multiple proxies.
            $comma_pos = strpos($_SERVER[$header_name], ',');
            if ($comma_pos !== false) {
                $_SERVER[$header_name] = substr($_SERVER[$header_name], 0, $comma_pos);
            }
            return $_SERVER[$header_name];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            //Debug::text('Remote IP: '. $_SERVER['REMOTE_ADDR'], __FILE__, __LINE__, __METHOD__, 10);
            return $_SERVER['REMOTE_ADDR'];
        }

        return false;
    }

    public static function checkValidReferer($referer = false)
    {
        global $config_vars;

        if (PRODUCTION == true and isset($config_vars['other']['enable_csrf_validation']) and $config_vars['other']['enable_csrf_validation'] == true) {
            if ($referer == false) {
                if (isset($_SERVER['HTTP_ORIGIN']) and $_SERVER['HTTP_ORIGIN'] != '') {
                    //IE9 doesn't send this, but if it exists use it instead as its likely more trustworthy.
                    //Debug::Text( 'Using Referer from Origin header...', __FILE__, __LINE__, __METHOD__, 10);
                    $referer = $_SERVER['HTTP_ORIGIN'];
                    if ($referer == 'file://') { //Mobile App and some browsers can send the origin as: file://
                        return true;
                    }
                } elseif (isset($_SERVER['HTTP_REFERER']) and $_SERVER['HTTP_REFERER'] != '') {
                    $referer = $_SERVER['HTTP_REFERER'];
                } else {
                    $referer = '';
                }
            }

            //Debug::Text( 'Raw Referer: '. $referer, __FILE__, __LINE__, __METHOD__, 10);
            $referer = strtolower(parse_url($referer, PHP_URL_HOST)); //Make sure we lowercase it, so case doesn't prevent a match.

            //Use HTTP_HOST rather than getHostName() as the same site can be referenced with multiple different host names
            //Especially considering on-site installs that default to 'localhost'
            //If deployment ondemand is set, then we assume SERVER_NAME is correct and revert to using that instead of HTTP_HOST which has potential to be forged.
            //Apache's UseCanonicalName On configuration directive can help ensure the SERVER_NAME is always correct and not masked.
            if (isset($_SERVER['HTTP_HOST'])) {
                $host_name = $_SERVER['HTTP_HOST'];
            } elseif (isset($_SERVER['SERVER_NAME'])) {
                $host_name = $_SERVER['SERVER_NAME'];
            } elseif (isset($_SERVER['HOSTNAME'])) {
                $host_name = $_SERVER['HOSTNAME'];
            } else {
                $host_name = '';
            }
            $host_name = ($host_name != '') ? strtolower(parse_url('http://' . $host_name, PHP_URL_HOST)) : ''; //Need to add 'http://' so parse_url() can strip it off again. Also lowercase it so case differences don't prevent a match.
            //Debug::Text( 'Parsed Referer: '. $referer .' Hostname: '. $host_name, __FILE__, __LINE__, __METHOD__, 10);

            if ($referer == $host_name or $host_name == '') {
                return true;
            }

            Debug::Text('CSRF check failed... Parsed Referer: ' . $referer . ' Hostname: ' . $host_name, __FILE__, __LINE__, __METHOD__, 10);
            return false;
        }

        return true;
    }

    public static function getHostNameWithoutSubDomain($host_name)
    {
        $split_host_name = explode('.', $host_name);
        if (count($split_host_name) > 2) {
            unset($split_host_name[0]);
            return implode('.', $split_host_name);
        }

        return $host_name;
    }

    public static function parseDatabaseHostString($database_host_string)
    {
        $retarr = array();

        $db_hosts = explode(',', $database_host_string);
        if (is_array($db_hosts)) {
            $i = 0;
            foreach ($db_hosts as $db_host) {
                $db_host_split = explode('#', $db_host);

                $db_host = $db_host_split[0];
                $weight = (isset($db_host_split[1])) ? $db_host_split[1] : 1;

                $retarr[] = array($db_host, ($i == 0) ? 'master' : 'slave', $weight);

                $i++;
            }
        }

        //Debug::Arr( $retarr,  'Parsed Database Connections: ', __FILE__, __LINE__, __METHOD__, 1);
        return $retarr;
    }

    public static function isOpenPort($address, $port = 80, $timeout = 3)
    {
        $checkport = @fsockopen($address, $port, $errnum, $errstr, $timeout); //The 2 is the time of ping in secs

        //Check if port is closed or open...
        if ($checkport == false) {
            return false;
        }

        return true;
    }

    //Accepts a search_str and key=>val array that it searches through, to return the array key of the closest fuzzy match.

    public static function array_isearch($str, $array)
    {
        foreach ($array as $key => $value) {
            if (strtolower($value) == strtolower($str)) {
                return $key;
            }
        }

        return false;
    }

    //Converts a number between 0 and 25 to the corresponding letter.

    public static function findClosestMatch($search_str, $search_arr, $minimum_percent_match = 0, $return_all_matches = false)
    {
        if ($search_str == '') {
            return false;
        }

        if (!is_array($search_arr) or count($search_arr) == 0) {
            return false;
        }

        $matches = array();
        foreach ($search_arr as $key => $search_val) {
            similar_text(strtolower($search_str), strtolower($search_val), $percent);
            if ($percent >= $minimum_percent_match) {
                $matches[$key] = $percent;
            }
        }

        if (empty($matches) == false) {
            arsort($matches);

            if ($return_all_matches == true) {
                return $matches;
            }

            //Debug::Arr( $search_arr, 'Search Str: '. $search_str .' Search Array: ', __FILE__, __LINE__, __METHOD__, 10);
            //Debug::Arr( $matches, 'Matches: ', __FILE__, __LINE__, __METHOD__, 10);

            reset($matches);
            return key($matches);
        }

        //Debug::Text('No match found for: '. $search_str, __FILE__, __LINE__, __METHOD__, 10);

        return false;
    }

    public static function NumberToLetter($number)
    {
        if ($number > 25) {
            return false;
        }

        return chr(($number + 65));
    }

    public static function reScaleRange($value, $old_min = 1, $old_max = 5, $new_min = 1, $new_max = 10)
    {
        if ($value === '' or $value === null) {
            return $value;
        } else {
            $retval = (((($value - $old_min) * ($new_max - $new_min)) / ($old_max - $old_min)) + $new_min);
            return $retval;
        }
    }

    public static function issetOr(&$var, $default = null)
    {
        if (isset($var)) {
            return $var;
        }

        return $default;
    }

    public static function getFullName($first_name, $middle_name, $last_name, $reverse = false, $include_middle = true)
    {
        if ($first_name != '' and $last_name != '') {
            if ($reverse === true) {
                $retval = $last_name . ', ' . $first_name;
                if ($include_middle == true and $middle_name != '') {
                    $retval .= ' ' . $middle_name[0] . '.'; //Use just the middle initial.
                }
            } else {
                $retval = $first_name . ' ' . $last_name;
            }

            return $retval;
        }

        return false;
    }

    //Caller ID numbers can come in in all sorts of forms:
    // 2505551234
    // 12505551234
    // +12505551234
    // (250) 555-1234
    //Parse out just the digits, and use only the last 10 digits.
    //Currently this will not support international numbers

    public static function getCityAndProvinceAndPostalCode($city, $province, $postal_code)
    {
        $retval = '';
        if ($city != '') {
            $retval .= $city;
        }

        if ($province != '' and $province != '00') {
            if ($retval != '') {
                $retval .= ',';
            }
            $retval .= ' ' . $province;
        }

        if ($postal_code != '') {
            $retval .= ' ' . strtoupper($postal_code);
        }

        return $retval;
    }

    public static function parseCallerID($number)
    {
        $validator = new Validator();

        $retval = substr($validator->stripNonNumeric($number), -10, 10);

        return $retval;
    }

    public static function generateCopyName($name, $strict = false)
    {
        $name = str_replace(TTi18n::getText('Copy of'), '', $name);

        if ($strict === true) {
            $retval = TTi18n::getText('Copy of') . ' ' . $name;
        } else {
            $retval = TTi18n::getText('Copy of') . ' ' . $name . ' [' . rand(1, 99) . ']';
        }

        $retval = substr($retval, 0, 99); //Make sure the name doesn't get too long.
        return $retval;
    }

    public static function generateShareName($from, $name, $strict = false)
    {
        if ($strict === true) {
            $retval = $name . ' (' . TTi18n::getText('Shared by') . ': ' . $from . ')';
        } else {
            $retval = $name . ' (' . TTi18n::getText('Shared by') . ': ' . $from . ') [' . rand(1, 99) . ']';
        }

        $retval = substr($retval, 0, 99); //Make sure the name doesn't get too long.
        return $retval;
    }

    //If rename fails for some reason, attempt a copy instead as that might work, specifically on windows where if the file is in use.
    //  Might fix possible "Access is denied. (code: 5)" errors on Windows when using PHP v5.2 (https://bugs.php.net/bug.php?id=43817)

    /** Delete all files in directory
     * @param $path directory to clean
     * @param $recursive delete files in subdirs
     * @param $delDirs delete subdirs
     * @param $delRoot delete root directory
     * @access public
     * @return success
     */
    public static function cleanDir($path, $recursive = false, $del_dirs = false, $del_root = false, $exclude_regex_filter = null)
    {
        $result = true;

        if (!$dir = @dir($path)) {
            return false;
        }

        Debug::Text('Cleaning: ' . $path . ' Exclude Regex: ' . $exclude_regex_filter, __FILE__, __LINE__, __METHOD__, 10);
        while ($file = $dir->read()) {
            if ($file === '.' or $file === '..') {
                continue;
            }

            $full = $dir->path . DIRECTORY_SEPARATOR . $file;

            if ($exclude_regex_filter != '' and preg_match('/' . $exclude_regex_filter . '/i', $full) == 1) {
                continue;
            }

            if (is_dir($full) and $recursive == true) {
                $result = self::cleanDir($full, $recursive, $del_dirs, $del_dirs, $exclude_regex_filter);
            } elseif (is_file($full)) {
                $result = @unlink($full);
                if ($result == false) {
                    Debug::Text('  Failed Deleting: ' . $full, __FILE__, __LINE__, __METHOD__, 10);
                }
            }
        }
        $dir->close();

        if ($del_root == true) {
            //Debug::Text('Deleting Dir: '. $dir->path, __FILE__, __LINE__, __METHOD__, 10);
            $result = @rmdir($dir->path);
        }

        clearstatcache(); //Clear any stat cache when done.
        return $result;
    }

    public static function rename($oldname, $newname)
    {
        if (@rename($oldname, $newname) == false) {
            Debug::Text('ERROR: Unable to rename: ' . $oldname . ' to: ' . $newname, __FILE__, __LINE__, __METHOD__, 10);
            if (is_dir($oldname) == false and copy($oldname, $newname) == true) {
                @unlink($oldname);
                return true;
            } else {
                Debug::Text('ERROR: Unable to copy after rename failure: ' . $oldname . ' to: ' . $newname, __FILE__, __LINE__, __METHOD__, 10);
            }

            return false;
        }

        return true;
    }

    public static function getFileList($start_dir, $regex_filter = null, $recurse = false)
    {
        $files = array();
        if (is_dir($start_dir) and is_readable($start_dir)) {
            $fh = opendir($start_dir);
            while (($file = readdir($fh)) !== false) {
                # loop through the files, skipping . and .., and recursing if necessary
                if (strcmp($file, '.') == 0 or strcmp($file, '..') == 0) {
                    continue;
                }

                $filepath = $start_dir . DIRECTORY_SEPARATOR . $file;
                if (is_dir($filepath) and $recurse == true) {
                    Debug::Text(' Recursing into dir: ' . $filepath, __FILE__, __LINE__, __METHOD__, 10);

                    $tmp_files = self::getFileList($filepath, $regex_filter, true);
                    if ($tmp_files != false and is_array($tmp_files)) {
                        $files = array_merge($files, $tmp_files);
                    }
                    unset($tmp_files);
                } elseif (!is_dir($filepath)) {
                    if ($regex_filter == '*' or preg_match('/' . $regex_filter . '/i', $file) == 1) {
                        //Debug::Text(' Match: Dir: '. $start_dir .' File: '. $filepath, __FILE__, __LINE__, __METHOD__, 10);
                        if (is_readable($filepath)) {
                            array_push($files, $filepath);
                        } else {
                            Debug::Text(' Matching file is not read/writable: ' . $filepath, __FILE__, __LINE__, __METHOD__, 10);
                        }
                    } // else { //Debug::Text(' NO Match: Dir: '. $start_dir .' File: '. $filepath, __FILE__, __LINE__, __METHOD__, 10);
                }
            }
            closedir($fh);
            sort($files);
        } else {
            # false if the function was called with an invalid non-directory argument
            $files = false;
        }

        //Debug::Arr( $files, 'Matching files: ', __FILE__, __LINE__, __METHOD__, 10);
        return $files;
    }

    public static function convertObjectToArray($obj)
    {
        if (is_object($obj)) {
            $obj = get_object_vars($obj);
        }

        if (is_array($obj)) {
            return array_map(array('Misc', __FUNCTION__), $obj);
        } else {
            return $obj;
        }
    }

    public static function getSystemMemoryInfo()
    {
        if (OPERATING_SYSTEM == 'LINUX') {
            $memory_file = '/proc/meminfo';
            if (@file_exists($memory_file) and is_readable($memory_file)) {
                $buffer = file_get_contents($memory_file);

                preg_match('/MemFree:\s+([0-9]+) kB/im', $buffer, $mem_free_match);
                if (isset($mem_free_match[1])) {
                    $mem_free = Misc::getBytesFromSize((int)$mem_free_match[1] . 'K');
                    unset($mem_free_match);
                }

                preg_match('/Cached:\s+([0-9]+) kB/im', $buffer, $mem_cached_match);
                if (isset($mem_cached_match[1])) {
                    $mem_cached = Misc::getBytesFromSize((int)$mem_cached_match[1] . 'K');
                    unset($mem_cached_match);
                }

                Debug::Text(' Memory Info: Free: ' . $mem_free . 'b Cached: ' . $mem_cached . 'b', __FILE__, __LINE__, __METHOD__, 10);
                return ($mem_free + ($mem_cached * (3 / 4))); //Only allow up to 3/4 of cached memory to be used.
            }
        }

        return 2147483647; //If not linux, return large number, this is in Bytes.
    }

    public static function getBytesFromSize($val)
    {
        $val = trim($val);

        switch (strtolower(substr($val, -1))) {
            case 'm':
                $val = ((int)substr($val, 0, -1) * 1048576);
                break;
            case 'k':
                $val = ((int)substr($val, 0, -1) * 1024);
                break;
            case 'g':
                $val = ((int)substr($val, 0, -1) * 1073741824);
                break;
            case 'b':
                switch (strtolower(substr($val, -2, 1))) {
                    case 'm':
                        $val = ((int)substr($val, 0, -2) * 1048576);
                        break;
                    case 'k':
                        $val = ((int)substr($val, 0, -2) * 1024);
                        break;
                    case 'g':
                        $val = ((int)substr($val, 0, -2) * 1073741824);
                        break;
                    default:
                        break;
                }
                break;
            default:
                break;
        }

        return $val;
    }

    public static function isSystemLoadValid()
    {
        global $config_vars;

        if (!isset($config_vars['other']['max_cron_system_load'])) {
            $config_vars['other']['max_cron_system_load'] = 9999;
        }

        $system_load = Misc::getSystemLoad();
        if (isset($config_vars['other']['max_cron_system_load']) and $system_load <= $config_vars['other']['max_cron_system_load']) {
            Debug::text(' Load average within valid limits: Current: ' . $system_load . ' Max: ' . $config_vars['other']['max_cron_system_load'], __FILE__, __LINE__, __METHOD__, 10);

            return true;
        }

        Debug::text(' Load average NOT within valid limits: Current: ' . $system_load . ' Max: ' . $config_vars['other']['max_cron_system_load'], __FILE__, __LINE__, __METHOD__, 10);

        return false;
    }

    public static function getSystemLoad()
    {
        if (OPERATING_SYSTEM == 'LINUX') {
            $loadavg_file = '/proc/loadavg';
            if (file_exists($loadavg_file) and is_readable($loadavg_file)) {
                $buffer = '0 0 0';
                $buffer = file_get_contents($loadavg_file);
                $load = explode(' ', $buffer);

                //$retval = max((float)$load[0], (float)$load[1], (float)$load[2]);
                $retval = max((float)$load[0], (float)$load[1]); //Only consider 1 and 5 minute load averages, so we don't block cron/reports for more than 5 minutes.
                //Debug::text(' Load Average: '. $retval, __FILE__, __LINE__, __METHOD__, 10);

                return $retval;
            }
        }

        return 0;
    }

    //Parses an RFC822 Email Address ( "John Doe" <john.doe@mydomain.com> ) into its separate components.

    public static function formatEmailAddress($email, $user_obj)
    {
        if (!is_object($user_obj)) {
            return $email;
        }
        $email = '"' . $user_obj->getFirstName() . ' ' . $user_obj->getLastName() . '" <' . $email . '>';
        return $email;
    }

    public static function parseRFC822EmailAddress($input, $return_just_key = false)
    {
        if (strstr($input, '<>') !== false) { //Check for <> together, as that means no email address is specified.
            return false;
        }

        if (function_exists('imap_rfc822_parse_adrlist')) {
            $parsed_data = @imap_rfc822_parse_adrlist($input, 'unknown.local');
            //Debug::Arr( $parsed_data, 'Parsed Email Data From: ' . $input, __FILE__, __LINE__, __METHOD__, 10 );
            if (is_array($parsed_data) and count($parsed_data) > 0) {
                $parsed_data = $parsed_data[0];
                if ($parsed_data->host != 'unknown.local') {
                    $retarr['email'] = $parsed_data->mailbox . '@' . $parsed_data->host;

                    if (isset($parsed_data->personal)) {
                        $retarr['full_name'] = $parsed_data->personal;

                        $split_name = explode(' ', $parsed_data->personal);
                        if ($split_name !== false) {
                            if (isset($split_name[0])) {
                                $retarr['first_name'] = $split_name[0];
                            }
                            if (isset($split_name[(count($split_name) - 1)])) {
                                $retarr['last_name'] = $split_name[(count($split_name) - 1)];
                            }
                        }
                    }

                    if ($return_just_key != '') {
                        if (isset($retarr[$return_just_key])) {
                            return $retarr[$return_just_key];
                        }

                        return false;
                    } else {
                        return $retarr;
                    }
                }
            }
        } else {
            Debug::Text('ERROR: PHP IMAP extension is not installed...', __FILE__, __LINE__, __METHOD__, 10);
        }

        return false;
    }

    public static function disableCaching($email_notification = true)
    {
        //In case the cache directory does not exist, disabling caching can prevent errors from occurring or punches to be missed.
        //So this should be enabled even for ON-DEMAND services just in case.
        if (PRODUCTION == true) {
            $tmp_config_vars = array();
            //Disable caching to prevent stale cache data from being read, and further cache errors.
            $install_obj = new Install();
            $tmp_config_vars['cache']['enable'] = 'FALSE';
            $write_config_result = $install_obj->writeConfigFile($tmp_config_vars);
            unset($install_obj);

            if ($email_notification == true) {
                if ($write_config_result == true) {
                    $subject = APPLICATION_NAME . ' - Error!';
                    $body = 'ERROR writing cache file, likely due to incorrect operating system permissions, disabling caching to prevent data corruption. This may result in ' . APPLICATION_NAME . ' performing slowly.' . "\n\n";
                    $body .= Debug::getOutput();
                } else {
                    $subject = APPLICATION_NAME . ' - Error!';
                    $body = 'ERROR writing config file, likely due to incorrect operating system permissions conflicts. Please correction permissions so ' . APPLICATION_NAME . ' can operate correctly.' . "\n\n";
                    $body .= Debug::getOutput();
                }
//				return self::sendSystemMail( $subject, $body );
            }

            return true;
        }

        return false;
    }

    public static function getMapURL($address1, $address2, $city, $province, $postal_code, $country, $service = 'google')
    {
        if ($address1 == '' and $address2 == '') {
            return false;
        }

        $url = null;

        //Expand the country code to the full country name?
        if (strlen($country) == 2) {
            $cf = TTnew('CompanyFactory');

            $long_country = Option::getByKey($country, $cf->getOptions('country'));
            if ($long_country != '') {
                $country = $long_country;
            }
        }

        if ($service == 'google') {
            $base_url = 'maps.google.com/?z=16&q=';
            $url = $base_url . urlencode($address1 . ' ' . $city . ' ' . $province . ' ' . $postal_code . ' ' . $country);
        }

        if ($url != '') {
            return 'http://' . $url;
        }

        return false;
    }

    public static function isEmail($email, $check_dns = true, $error_level = true)
    {
        if (!function_exists('is_email')) {
            require_once(Environment::getBasePath() . '/classes/misc/is_email.php');
        }

        $result = is_email($email, $check_dns, $error_level);
        if ($result === ISEMAIL_VALID) {
            return true;
        } else {
            Debug::Text('Result Code: ' . $result, __FILE__, __LINE__, __METHOD__, 10);
        }

        return false;
    }

    public static function getPasswordStrength($password)
    {
        if (strlen($password) == 0) {
            return 1;
        }

        $strength = 0;

        //get the length of the password
        $length = strlen($password);

        //check if password is not all lower case
        if (strtolower($password) != $password) {
            $strength++;
        }

        //check if password is not all upper case
        if (strtoupper($password) != $password) {
            $strength++;
        }

        //check string length is 6-9 chars
        if ($length >= 6 and $length <= 9) {
            $strength++;
        }

        //check if length is 10-15 chars
        if ($length >= 10 and $length <= 15) {
            $strength += 2;
        }

        //check if length greater than 15 chars
        if ($length > 15) {
            $strength += 3;
        }

        $duplicate_chars = 1;
        $consecutive_chars = 1;
        $char_arr = str_split(strtolower($password));
        $prev_char_int = ord($char_arr[0]);
        foreach ($char_arr as $char) {
            $curr_char_int = ord($char);
            $char_int_diff = abs($prev_char_int - $curr_char_int);
            if ($char_int_diff == 0) { //Duplicate
                $duplicate_chars++;
            } elseif ($char_int_diff == 1 or $char_int_diff == -1) { //Consecutive
                $consecutive_chars++;
            }
            $prev_char_int = $curr_char_int;
        }
        $duplicate_percent = (($duplicate_chars / strlen($password)) * 100);
        $consecutive_percent = (($consecutive_chars / strlen($password)) * 100);
        if ($duplicate_percent <= 25) {
            $strength++;
        }
        if ($consecutive_percent <= 25) {
            $strength++;
        }

        //get the numbers in the password
        preg_match_all('/[0-9]/', $password, $numbers);
        //Prevent the addition of a single number to the beginning/end of the password from increasing the strength.
        if (is_numeric(substr($password, 0, 1)) == true) {
            array_pop($numbers[0]);
        }
        if (is_numeric(substr($password, -1, 1)) == true) {
            array_pop($numbers[0]);
        }
        $strength += (count($numbers[0]) * 2);

        //check for special chars
        preg_match_all('/[|!@#$%&*\/=?,;.:\-_+~^\\\]/', $password, $specialchars);
        $strength += (count($specialchars[0]) * 3);

        //get the number of unique chars
        $chars = str_split($password);
        $num_unique_chars = count(array_unique($chars));
        $unique_percent = (($num_unique_chars / strlen($password)) * 100);

        $strength += ($num_unique_chars * 2);


        //If the password consists of duplicate or consecutive chars, make it the lowest strength.
        //This should help prevent 12345, or abcde passwords.
        if ($unique_percent <= 20) {
            $strength = 1;
        }
        if ($duplicate_percent >= 50) {
            $strength = 1;
        }
        if ($consecutive_percent >= 60) {
            $strength = 1;
        }
        Debug::Text('Duplicate: Chars: ' . $duplicate_chars . ' Percent: ' . $duplicate_percent . ' Consec: Chars: ' . $consecutive_chars . ' Percent: ' . $consecutive_percent . ' Unique: Chars: ' . $num_unique_chars . ' Percent: ' . $unique_percent, __FILE__, __LINE__, __METHOD__, 10);

        //Check for dictionary word, if its just a dictionary word make it the lowest strength.
        if (function_exists('pspell_new')) {
            //If no aspell dictionary is installed, you might see: WARNING(2): pspell_new(): PSPELL couldn't open the dictionary. reason: No word lists can be found for the language "en".
            //  On Centos this can fixed by: yum install aspell-en
            $pspell_config = @pspell_config_create('en');
            $pspell_link = @pspell_new_config($pspell_config);
            if ($pspell_link != false) {
                if (pspell_check($pspell_link, $password) !== false) {
                    Debug::Text('Matches dictionary word exactly: ' . $password, __FILE__, __LINE__, __METHOD__, 10);
                    $strength = 1;
                }
                if (pspell_check($pspell_link, substr($password, 1)) !== false) {
                    Debug::Text('Matches dictionary word after 1st char is dropped: ' . $password, __FILE__, __LINE__, __METHOD__, 10);
                    $strength = 1;
                }
                if (pspell_check($pspell_link, substr($password, 0, -1)) !== false) {
                    Debug::Text('Matches dictionary word after last char is dropped: ' . $password, __FILE__, __LINE__, __METHOD__, 10);
                    $strength = 1;
                }
                if (pspell_check($pspell_link, substr(substr($password, 1), 0, -1)) !== false) {
                    Debug::Text('Matches dictionary word after first and last char is dropped: ' . $password, __FILE__, __LINE__, __METHOD__, 10);
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
        $strength = floor((($strength / 10) + 1));

        Debug::Text('Strength: ' . $strength, __FILE__, __LINE__, __METHOD__, 10);
        return $strength;
    }

    public static function redirectMobileBrowser()
    {
        $desktop = 0;
        extract(FormVariables::GetVariables(array('desktop')));

        if (!isset($desktop)) {
            $desktop = 0;
        }
        $browser = self::detectMobileBrowser();
        if ($browser == 'ios' or $browser == 'html5' or $browser == 'android') {
            Redirect::Page(URLBuilder::getURL(null, Environment::getBaseURL() . '/quick_punch/QuickPunchLogin.php'));
        }

        return false;
    }

    public static function detectMobileBrowser($useragent = null)
    {
        if ($useragent == '') {
            if (isset($_SERVER['HTTP_USER_AGENT'])) {
                $useragent = $_SERVER['HTTP_USER_AGENT'];
            } else {
                return false;
            }
        }

        //Mobile Browsers: We just need to know if they are WAP or HTML5 for now.
        $retval = false;

        if (preg_match('/android.+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i', $useragent)
            or preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|e\-|e\/|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(di|rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|xda(\-|2|g)|yas\-|your|zeto|zte\-/i', substr($useragent, 0, 4))
        ) {
            $retval = 'html5';

            //Check to see if its an iPhone/iPod/iPad
            if (preg_match('/ip(hone|od|ad)/i', $useragent)) {
                $retval = 'ios';
            } elseif (preg_match('/android.+mobile/i', $useragent)) { //Check to see if its an android browser
                $retval = 'android';
            }

            //WAP is dying and HTTP_ACCEPT seems to cause more problems than it solves, specifically with older blackberry phones.
            /*
            //if (	( isset( $_SERVER['HTTP_ACCEPT'] ) AND strpos( strtolower( $_SERVER['HTTP_ACCEPT'] ), 'application/vnd.wap.xhtml+xml' ) > 0 )
                    // This HTTP_X_WAP_PROFILE seem to be specified for some android phones (LG) that support HTML5 and not actually wap by the looks of it.
                    //( ( isset( $_SERVER['HTTP_X_WAP_PROFILE'] ) OR isset( $_SERVER['HTTP_PROFILE'] ) ) )
                    //( ( isset( $_SERVER['HTTP_X_WAP_PROFILE'] ) ) )
            //	) {
            //	Debug::Text('WAP profile accepted... HTTP_ACCEPT: '. $_SERVER['HTTP_ACCEPT'] .' HTTP_X_WAP_PROFILE: '. $_SERVER['HTTP_X_WAP_PROFILE'] .' HTTP_PROFILE: '. $_SERVER['HTTP_PROFILE'], __FILE__, __LINE__, __METHOD__, 10);
            //	$retval = 'wap';
            //} else
            if ( preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap)/i', strtolower($useragent) ) ) {
                Debug::Text('WAP browser...', __FILE__, __LINE__, __METHOD__, 10);
                $retval = 'wap';
            } else {
                $mobile_ua = strtolower(substr($_SERVER['HTTP_USER_AGENT'], 0, 4));
                $mobile_agents = array(
                    'w3c ', 'acs-', 'alav', 'alca', 'amoi', 'audi', 'avan', 'benq', 'bird', 'blac',
                    'blaz', 'brew', 'cell', 'cldc', 'cmd-', 'dang', 'doco', 'eric', 'hipt', 'inno',
                    'ipaq', 'java', 'jigs', 'kddi', 'keji', 'leno', 'lg-c', 'lg-d', 'lg-g', 'lge-',
                    'maui', 'maxo', 'midp', 'mits', 'mmef', 'mobi', 'mot-', 'moto', 'mwbp', 'nec-',
                    'newt', 'noki', 'oper', 'palm', 'pana', 'pant', 'phil', 'play', 'port', 'prox',
                    'qwap', 'sage', 'sams', 'sany', 'sch-', 'sec-', 'send', 'seri', 'sgh-', 'shar',
                    'sie-', 'siem', 'smal', 'smar', 'sony', 'sph-', 'symb', 't-mo', 'teli', 'tim-',
                    'tosh', 'tsm-', 'upg1', 'upsi', 'vk-v', 'voda', 'wap-', 'wapa', 'wapi', 'wapp',
                    'wapr', 'webc', 'winw', 'winw', 'xda ', 'xda-');

                if ( in_array($mobile_ua, $mobile_agents) ) {
                    Debug::Text('WAP Agent found...', __FILE__, __LINE__, __METHOD__, 10);
                    $retval = 'wap';
                }
            }
            */
        }

        //$retval = 'android';
        Debug::Text('User Agent: ' . $useragent . ' Retval: ' . $retval, __FILE__, __LINE__, __METHOD__, 10);
        return $retval;
    }

    public static function redirectUnSupportedBrowser()
    {
        if (self::isUnSupportedBrowser() == true) {
            Redirect::Page(URLBuilder::getURL(array('fn_version' => APPLICATION_VERSION, 'https://github.com/aydancoskun/fairness/issues')));
        }

        return true;
    }

    //Take an amount and a distribution array of key => value pairs, value being a decimal percent (ie: 0.50 for 50%)
    //return an array with the same keys and resulting distribution between them.
    //Adding any remainder to the last key is the fastest.

    public static function isUnSupportedBrowser($useragent = null)
    {
        if ($useragent == '') {
            if (isset($_SERVER['HTTP_USER_AGENT'])) {
                $useragent = $_SERVER['HTTP_USER_AGENT'];
            } else {
                return false;
            }
        }

        $retval = false;

        if (!class_exists('Browser', false)) {
            require_once(Environment::getBasePath() . '/classes/other/Browser.php');
        }

        $browser = new Browser($useragent);

        //This is for the full web interface
        //IE < 9
        //Firefox < 24
        //Chrome < 32
        //Safari < 5
        //Opera < 12
        if ($browser->getBrowser() == Browser::BROWSER_IE and version_compare($browser->getVersion(), 9, '<')) {
            $retval = true;
        }

        if ($browser->getBrowser() == Browser::BROWSER_FIREFOX and version_compare($browser->getVersion(), 24, '<')) {
            $retval = true;
        }

        if ($browser->getBrowser() == Browser::BROWSER_CHROME and version_compare($browser->getVersion(), 30, '<')) {
            $retval = true;
        }

        if ($browser->getBrowser() == Browser::BROWSER_SAFARI and version_compare($browser->getVersion(), 5, '<')) {
            $retval = true;
        }

        if ($browser->getBrowser() == Browser::BROWSER_OPERA and version_compare($browser->getVersion(), 12, '<')) {
            $retval = true;
        }

        if ($retval == true) {
            Debug::Text('Unsupported Browser: ' . $browser->getBrowser() . ' Version: ' . $browser->getVersion(), __FILE__, __LINE__, __METHOD__, 10);
        }

        return $retval;
    }

    //Change the case of all values in an array

    public static function PercentDistribution($amount, $percent_arr, $remainder_operation = 'last', $precision = 2)
    {
        //$percent_arr = array(
        //					'key1' => 0.505,
        //					'key2' => 0.495,
        //);
        if (is_array($percent_arr) and count($percent_arr) > 0) {
            $retarr = array();
            $total = 0;
            foreach ($percent_arr as $key => $distribution_percent) {
                $distribution_amount = bcmul($amount, $distribution_percent, $precision);
                $retarr[$key] = $distribution_amount;

                $total = bcadd($total, $distribution_amount, $precision);
            }

            //Add any remainder to the last key.
            if ($total != $amount) {
                $remainder_amount = bcsub($amount, $total, $precision);
                //Debug::Text('Found remainder: '. $remainder_amount, __FILE__, __LINE__, __METHOD__, 10);

                if ($remainder_operation == 'first') {
                    reset($retarr);
                    $key = key($retarr);
                }
                $retarr[$key] = bcadd($retarr[$key], $remainder_amount, $precision);
            }

            //Debug::Text('Amount: '. $amount .' Total (After Remainder): '. array_sum( $retarr ), __FILE__, __LINE__, __METHOD__, 10);
            return $retarr;
        }

        return false;
    }

    public static function arrayChangeValueCase($input, $case = CASE_LOWER)
    {
        switch ($case) {
            case CASE_LOWER:
                return array_map('strtolower', $input);
                break;
            case CASE_UPPER:
                return array_map('strtoupper', $input);
                break;
            default:
                trigger_error('Case is not valid, CASE_LOWER or CASE_UPPER only', E_USER_ERROR);
                return false;
        }

        return false;
    }

    public static function isWritable($path)
    {
        if ($path[(strlen($path) - 1)] == '/') {
            return self::isWritable($path . uniqid(mt_rand()) . '.tmp');
        }

        if (file_exists($path)) {
            if (!($f = @fopen($path, 'r+'))) {
                return false;
            }
            fclose($f);
            return true;
        }

        if (!($f = @fopen($path, 'w'))) {
            return false;
        }

        fclose($f);
        unlink($path);

        return true;
    }

    public static function MajorVersionCompare($version1, $version2, $operator)
    {
        $tmp_version1 = explode('.', $version1); //Return first two dot versions.
        array_pop($tmp_version1);
        $version1 = implode('.', $tmp_version1);

        $tmp_version2 = explode('.', $version2); //Return first two dot versions.
        array_pop($tmp_version2);
        $version2 = implode('.', $tmp_version2);

        return version_compare($version1, $version2, $operator);
    }

    public static function stripThe($str, $add_to_end = false)
    {
        if (stripos($str, 'The ') === 0) {
            $retval = substr($str, 4);
            if ($add_to_end == true) {
                $retval .= ', The';
            }
            return $retval;
        }

        return $str;
    }

    //Removes the word "the" from the beginning of strings and optionally places it at the end.
    //Primarily for client/company names like: The XYZ Company -> XYZ Company, The
    //Should often be used to sanitize metaphones.

    public static function stripHTMLSpecialChars($str)
    {
        return str_replace(array('&', '"', '\'', '>', '<'), '', $str);
    }

    //Remove any HTML special char (before its encoded) from the string
    //Useful for things like government forms submitted in XML.

    public static function checkValidImage($file_data)
    {
        $mime_type = Misc::getMimeType($file_data, true);
        if (strpos($mime_type, 'image') !== false) {
            $file_size = strlen($file_data);

            //use getimagesize() to make sure image isn't too large and actually is an image.
            $size = getimagesizefromstring($file_data);
            Debug::Arr($size, 'Mime Type: ' . $mime_type . ' Bytes: ' . $file_size . ' Size: ', __FILE__, __LINE__, __METHOD__, 10);

            if (isset($size) and isset($size[0]) and isset($size[1])) {
                $bytes_to_image_size_ratio = ($file_size / ($size[0] * $size[1]));
                Debug::Text('Bytes to image ratio: ' . $bytes_to_image_size_ratio, __FILE__, __LINE__, __METHOD__, 10);

                //UNFINISHED!

                return true;
            }

            return false;
        }

        Debug::Text('Not a image, unable to process: Mime Type: ' . $mime_type, __FILE__, __LINE__, __METHOD__, 10);
        return true; //Isnt an image, don't bother processing...
    }

    public static function getMimeType($file_name, $buffer = false, $keep_charset = false, $unknown_type = 'application/octet-stream')
    {
        if (function_exists('finfo_buffer')) { //finfo extension in PHP v5.3+
            if ($buffer == false and file_exists($file_name)) {
                //Its a filename passed in.
                $finfo = finfo_open(FILEINFO_MIME_TYPE); // return mime type ala mimetype extension
                $retval = finfo_file($finfo, $file_name);
                finfo_close($finfo);
            } elseif ($buffer == true and $file_name != '') {
                //Its a string buffer;
                $finfo = new finfo(FILEINFO_MIME);
                $retval = $finfo->buffer($file_name);
            }

            if (isset($retval)) {
                if ($keep_charset == false) {
                    $split_retval = explode(';', $retval);
                    if (is_array($split_retval) and isset($split_retval[0])) {
                        $retval = $split_retval[0];
                    }
                }
                Debug::text('MimeType: ' . $retval, __FILE__, __LINE__, __METHOD__, 10);
                return $retval;
            }
        } else {
            //Attempt to detect mime type with PEAR MIME class.
            if ($buffer == false and file_exists($file_name)) {
                require_once(Environment::getBasePath() . '/classes/pear/MIME/Type.php');
                $retval = MIME_Type::autoDetect($file_name);
                if (is_object($retval)) { //MimeType failed.
                    //Attempt to detect mime type manually when finfo extension and PEAR Mime Type is not installed (windows)
                    $extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                    switch ($extension) {
                        case 'jpg':
                            $retval = 'image/jpeg';
                            break;
                        case 'png':
                            $retval = 'image/png';
                            break;
                        case 'gif':
                            $retval = 'image/gif';
                            break;
                        default:
                            $retval = $unknown_type;
                            break;
                    }
                }

                return $retval;
            }
        }

        return false;
    }

    public static function formatAddress($name, $address1 = false, $address2 = false, $city = false, $province = false, $postal_code = false, $country = false)
    {
        $retarr = array();
        $city_arr = array();
        if ($name != '') {
            $retarr[] = $name;
        }

        if ($address1 != '') {
            $retarr[] = $address1;
        }
        if ($address2 != '') {
            $retarr[] = $address2;
        }

        if ($city != '') {
            if ($province != '') {
                $city .= ',';
            }
            $city_arr[] = $city;
        }
        if ($province != '') {
            $city_arr[] = $province;
        }
        if ($postal_code != '') {
            $city_arr[] = $postal_code;
        }

        if (empty($city_arr) == false) {
            $retarr[] = implode(' ', $city_arr);
        }

        if ($country != '') {
            $retarr[] = $country;
        }

        return implode("\n", $retarr);
    }

    public static function getUniqueID()
    {
        global $config_vars;
        if (isset($config_vars['other']['salt']) and $config_vars['other']['salt'] != '') {
            $salt = $config_vars['other']['salt'];
        } else {
            $salt = uniqid(dechex(mt_rand()), true);
        }

        if (function_exists('mcrypt_create_iv')) {
            $retval = $salt . bin2hex(mcrypt_create_iv(128, MCRYPT_DEV_URANDOM)); //Use URANDOM as it wont block if there isn't enough entropy.
        } else {
            $retval = uniqid($salt . dechex(mt_rand()), true);
        }

        return $retval;
    }

    /**
     * zips an array of files and returns a file array for download
     */
    public static function zip($file_array, $zip_file_name = false, $ignore_single_file = false)
    {
        if (!is_array($file_array) or count($file_array) == 0) {
            return $file_array;
        }

        if ($ignore_single_file == true and (count($file_array) == 1 or key_exists('file_name', $file_array))) {
            //if there's just one file don't bother zipping it.
            foreach ($file_array as $file) {
                return $file;
            }
        } else {
            if ($zip_file_name == '') {
                $file_path_info = pathinfo($file_array[key($file_array)]['file_name']);
                $zip_file_name = $file_path_info['filename'] . '.zip';
            }

            global $config_vars;
            $tmp_file = tempnam($config_vars['cache']['dir'], 'zip_');
            $zip = new ZipArchive();
            $result = $zip->open($tmp_file, ZIPARCHIVE::CREATE);
            Debug::Text('Creating new zip file for download: ' . $zip_file_name . ' File Open Result: ' . $result, __FILE__, __LINE__, __METHOD__, 10);

            foreach ($file_array as $file) {
                $zip->addFromString($file['file_name'], $file['data']);
            }

            $zip->close();
            $ret_arr = array('file_name' => $zip_file_name, 'mime_type' => 'application/zip', 'data' => file_get_contents($tmp_file));
            unlink($tmp_file);

            return $ret_arr;
        }
    }
}
