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
class URLBuilder
{
    protected static $data = array();
    protected static $script = 'index.php';

    //Recursively convert an array to a URL.

    public static function setURL($script, $array = null)
    {
        //Debug::Arr(self::$data, 'Before: ', __FILE__, __LINE__, __METHOD__, 10);
        if (is_array($array) and count($array) > 0) {
            self::$data = array_merge(self::$data, $array);
        }
        //Debug::Arr(self::$data, 'After: ', __FILE__, __LINE__, __METHOD__, 10);

        self::$script = $script;

        return true;
    }

    public static function getURL($array = null, $script = null, $merge = true)
    {
        //Debug::Arr($array, 'Passed Array', __FILE__, __LINE__, __METHOD__, 10);

        //Debug::Arr(self::$data, 'bSelf Data: ', __FILE__, __LINE__, __METHOD__, 10);
        //Debug::Arr($array, 'bArray: ', __FILE__, __LINE__, __METHOD__, 10);
        if (is_array($array) and count($array) > 0 and $merge == true) {
            $array = array_merge(self::$data, $array);
        } elseif ($array == null and $merge == true) {
            $array = self::$data;
        } //else Use $array as is.

        //Debug::Arr($array, 'bAfter: ', __FILE__, __LINE__, __METHOD__, 10);

        if ($script == null) {
            //$script = Environment::getBaseURL().self::$script;
            $script = self::$script;
        }

        //Debug::Arr($array, 'Final Array', __FILE__, __LINE__, __METHOD__, 10);

        if (is_array($array) and count($array) > 0) {
            $url_values = self::urlencode_array($array);
            //Debug::Text('URL Values: '. $url_values, __FILE__, __LINE__, __METHOD__, 10);

            //if (isset($url_values) AND is_array($url_values)) {
            if (isset($url_values) and $url_values != '') {
                $url = '?' . $url_values;
            } else {
                $url = '?';
            }
        }

        if (isset($url)) {
            $retval = $script . $url;
        } else {
            $retval = $script;
        }

        //Debug::Text('URL: '. $retval, __FILE__, __LINE__, __METHOD__, 11);

        return $retval;
    }

    public static function urlencode_array($var, $varName = null, $sub_array = false)
    {
        $separator = '&';
        $toImplode = array();
        foreach ($var as $key => $value) {
            if (is_array($value)) {
                if ($sub_array == false) {
                    $toImplode[] = self::urlencode_array($value, $key, true);
                } else {
                    $toImplode[] = self::urlencode_array($value, $varName . '[' . $key . ']', true);
                }
            } else {
                if ($sub_array == true) {
                    //$toImplode[] = $varName.'['.$key.']='.urlencode($value);
                    $toImplode[] = $varName . '[' . $key . ']=' . $value;
                } else {
                    //$toImplode[] = $key.'='.urlencode($value);
                    $toImplode[] = $key . '=' . $value;
                }
            }
        }

        return implode($separator, $toImplode);
    }
}
