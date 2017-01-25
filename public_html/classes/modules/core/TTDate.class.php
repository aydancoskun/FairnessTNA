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
class TTDate
{
    public static $day_of_week_arr = null;
    public static $long_month_of_year_arr = null;
    public static $short_month_of_year_arr = null;
        protected static $time_zone = 'GMT'; //Hours
    protected static $date_format = 'd-M-y';
    protected static $time_format = 'g:i A T';
protected static $time_unit_format = 20;
    protected static $month_arr = array(
        'jan' => 1,
        'january' => 1,
        'feb' => 2,
        'february' => 2,
        'mar' => 3,
        'march' => 3,
        'apr' => 4,
        'april' => 4,
        'may' => 5,
        'jun' => 6,
        'june' => 6,
        'jul' => 7,
        'july' => 7,
        'aug' => 8,
        'august' => 8,
        'sep' => 9,
        'september' => 9,
        'oct' => 10,
        'october' => 10,
        'nov' => 11,
        'november' => 11,
        'dec' => 12,
        'december' => 12
    );

    public function __construct()
    {
        self::setTimeZone();
    }

    public static function isDST($epoch = null)
    {
        if ($epoch == null) {
            $epoch = TTDate::getTime();
        }

        $dst = date('I', $epoch);
        //Debug::text('Epoch: '. TTDate::getDate('DATE+TIME', $epoch) .' DST: '. $dst, __FILE__, __LINE__, __METHOD__, 10);
        return (bool)$dst;
    }

    public static function getTime()
    {
        return time();
    }

    public static function getTimeZone()
    {
        return self::$time_zone;
    }

    public static function setTimeZone($time_zone = null, $force = false, $execute_sql_now = true)
    {
        global $config_vars, $current_user_prefs;

        $time_zone = Misc::trimSortPrefix(trim($time_zone));

        //Default to system local timezone if no timezone is specified.
        if ($time_zone == '' or strtolower($time_zone) == 'system/localtime') { //System/Localtime is an invalid timezone, so default to GMT instead.
            if (isset($current_user_prefs) and is_object($current_user_prefs)) {
                //When TTDate is called from the API directly, its not called statically, so
                //this forces __construct() to call setTimeZone and for the timezone to be set back to the system defined timezone after
                //$current_user->getUserPreferenceObject()->setDateTimePreferences(); is called.
                //This checks to see if a user is logged in and uses their own preferences instead.
                $time_zone = $current_user_prefs->getTimeZone();
            } elseif (isset($config_vars['other']['system_timezone'])) {
                $time_zone = $config_vars['other']['system_timezone'];
            } else {
                //$time_zone = date('e'); //Newer versions of PHP return System/Localtime which is invalid, so force to GMT instead
                $time_zone = 'GMT';
            }
        }

        if ($force == false and $time_zone == self::$time_zone) {
            Debug::text('TimeZone already set to: ' . $time_zone, __FILE__, __LINE__, __METHOD__, 10);
            return true;
        }

        if ($time_zone != '') {
            Debug::text('Setting TimeZone: ' . $time_zone, __FILE__, __LINE__, __METHOD__, 10);

            global $db;
            if (isset($db) and is_object($db)) {
                if (strncmp($db->databaseType, 'postgres', 8) == 0) {
                    //PostgreSQL 9.2+ defaults to GMT in many cases, which causes problems with strtotime() and parsing date column types.
                    //Since date columns return times like: 2014-01-01 00:00:00+00, if the timezone in PHP is PST8PDT it parses to 31-Dec-13 4:00 PM

                    //$execute_sql_now is used in database.inc.php to help delay making a SQL query if not needed. Specifically when calling into APIProgress.
                    if ($db instanceof ADOdbLoadBalancer) {
                        $result = $db->setSessionVariable('TIME ZONE', $db->qstr($time_zone), $execute_sql_now);
                    } else {
                        $result = @$db->Execute('SET SESSION TIME ZONE ' . $db->qstr($time_zone));
                    }
                } elseif (strncmp($db->databaseType, 'mysql', 5) == 0) {
                    if ($db instanceof ADOdbLoadBalancer) {
                        $result = $db->setSessionVariable('time_zone', '=' . $db->qstr($time_zone), $execute_sql_now);
                    } else {
                        $result = @$db->Execute('SET SESSION time_zone=' . $db->qstr($time_zone));
                    }
                }

                if ($result == false) {
                    Debug::text('ERROR: Setting TimeZone: ' . $time_zone . ' DB Type: ' . $db->databaseType, __FILE__, __LINE__, __METHOD__, 10);
                    return false;
                }
            }

            //Set timezone AFTER MySQL query above, so if it fails we don't set the timezone below at all.
            self::$time_zone = $time_zone;

            @date_default_timezone_set($time_zone);
            putenv('TZ=' . $time_zone);

            return true;
        } else {
            //PHP doesn't have a unsetenv(), so this will cause the system to default to UTC.
            //If we don't do this then looping over users and setting timezones, if a user
            //doesn't have a timezone set, it will cause them to use the previous users timezone.
            //This way they at least use UTC and hopefully the issue will stand out more.
            //date_default_timezone_set( '' );
            putenv('TZ=');
        }

        return false;
    }

    public static function setDateFormat($date_format)
    {
        $date_format = trim($date_format);

        Debug::text('Setting Default Date Format: ' . $date_format, __FILE__, __LINE__, __METHOD__, 10);

        if (!empty($date_format)) {
            self::$date_format = $date_format;

            return true;
        }

        return false;
    }

    public static function setTimeFormat($time_format)
    {
        $time_format = trim($time_format);

        Debug::text('Setting Default Time Format: ' . $time_format, __FILE__, __LINE__, __METHOD__, 10);

        if (!empty($time_format)) {
            self::$time_format = $time_format;

            return true;
        }

        return false;
    }

    public static function setTimeUnitFormat($time_unit_format)
    {
        $time_unit_format = trim($time_unit_format);

        Debug::text('Setting Default Time Unit Format: ' . $time_unit_format, __FILE__, __LINE__, __METHOD__, 10);

        if (!empty($time_unit_format)) {
            self::$time_unit_format = $time_unit_format;

            return true;
        }

        return false;
    }

    public static function convertTimeZone($epoch, $timezone)
    {
        if ($timezone == '') {
            return $epoch;
        }

        $old_timezone_offset = TTDate::getTimeZoneOffset();

        try {
            //Use PEAR Date class to convert timezones instead of PHP v5.2 date object so we can still use older PHP versions for distros like CentOS.
            require_once('Date.php');

            $d = new Date(date('r', $epoch));
            $tz = new Date_TimeZone($timezone);

            $new_timezone_offset = ($tz->getOffset($d) / 1000);
            Debug::text('Converting time: ' . $epoch . ' to TimeZone: ' . $timezone . ' Offset: ' . $new_timezone_offset, __FILE__, __LINE__, __METHOD__, 10);

            return ($epoch - ($old_timezone_offset - $new_timezone_offset));
        } catch (Exception $e) {
            unset($e); //code standards
            return $epoch;
        }

        return $epoch;
    }

    public static function getTimeZoneOffset()
    {
        return date('Z');
    }

    public static function parseTimeUnit($time_unit, $format = null)
    {
        /*
            10	=> 'hh:mm (2:15)',
            12	=> 'hh:mm:ss (2:15:59)',
            20	=> 'Hours (2.25)',
            22	=> 'Hours (2.241)',
            23	=> 'Hours (2.2413)',
            30	=> 'Minutes (135)'
            40	=> 'Seconds (3600)'
        */

        if ($format == '') {
            $format = self::$time_unit_format;
        }

        $enable_rounding = true;
        if (strpos($time_unit, '"') !== false) {
            $enable_rounding = false;
        }

        //Get rid of any spaces or commas.
        //ie: 1, 100 :10 should still parse correctly
        //FIXME: comma can be thousands separator or decimal separate depending on locale. Will need to use TTI18n to determine how to display/parse this properly.
        //       Once we start using the INTL class, we can create a TTi18n::getDecimalSeparator() and TTi18n::getThousandsSeparator().
        $thousands_separator = ',';
        $decimal_separator = '.';
        $time_unit = trim(str_replace(array($thousands_separator, ' ', '"'), '', $time_unit));
        //Debug::text('Time Unit: '. $time_unit .' Enable Rounding: '. (int)$enable_rounding, __FILE__, __LINE__, __METHOD__, 10);
        //Debug::text('Time Unit Format: '. self::$time_unit_format, __FILE__, __LINE__, __METHOD__, 10);

        //Convert string to seconds.
        switch ($format) {
            case 10: //hh:mm
            case 12: //hh:mm:ss
                if (strpos($time_unit, $decimal_separator) !== false and strpos($time_unit, ':') === false) { //Hybrid mode, they passed a decimal format HH:MM, try to handle properly.
                    $time_unit = TTDate::getTimeUnit(self::parseTimeUnit($time_unit, 20), $format);
                }

                $time_units = explode(':', $time_unit);

                if (!isset($time_units[0])) {
                    $time_units[0] = 0;
                }
                if (!isset($time_units[1])) {
                    $time_units[1] = 0;
                }
                if (!isset($time_units[2])) {
                    $time_units[2] = 0;
                }

                //Check if the first character is '-', or thre are any negative integers.
                if (strncmp($time_units[0], '-', 1) == 0 or $time_units[0] < 0 or $time_units[1] < 0 or $time_units[2] < 0) {
                    $negative_number = true;
                }

                $seconds = ((abs((int)$time_units[0]) * 3600) + (abs((int)$time_units[1]) * 60) + abs((int)$time_units[2]));

                if (isset($negative_number)) {
                    $seconds = ($seconds * -1);
                }

                break;
            case 20: //hours
            case 22: //hours [Precise]
            case 23: //hours [Super Precise]
                if (strpos($time_unit, ':') !== false and strpos($time_unit, $decimal_separator) === false) { //Hybrid mode, they passed a HH:MM format as a decimal, try to handle properly.
                    $time_unit = TTDate::getTimeUnit(self::parseTimeUnit($time_unit, 10), $format);
                }

                //Round to the nearest minute when entering decimal format to avoid issues with 0.33 (19.8 minutes) or 0.333 (19.98 minutes) or 0.33333...
                //This is only for input, for things like absence time, or meal/break policies, its rare they need sub-minute resolution, and if they
                //do they can use hh:mm:ss instead.
                //However accrual policies have to be second accurate (weekly accruals rounded to 1 minute can result in 52minute differences in a year),
                //so we need a way to disable this rounding as well so the user can properly zero out an accrual balance if needed.
                $seconds = ($time_unit * 3600);
                if ($enable_rounding == true) {
                    $seconds = self::roundTime($seconds, 60);
                }
                break;
            case 30: //minutes
                $seconds = ($time_unit * 60);
                break;
            case 40: //seconds
                $seconds = round($time_unit); //No decimal places when using seconds.
                break;
        }

        if (isset($seconds)) {
            if ($seconds > 2147483646) {
                Debug::text('ERROR: Parsing time unit format exceeds maximum 4 byte integer!', __FILE__, __LINE__, __METHOD__, 10);
                $seconds = 2147483646;
            }

            return $seconds;
        }

        return false;
    }

    public static function getTimeUnit($seconds, $time_unit_format = null)
    {
        if ($time_unit_format == '') {
            $time_unit_format = self::$time_unit_format;
        }

        if (empty($seconds)) {
            switch ($time_unit_format) {
                case 10: //hh:mm
                    $retval = '00:00';
                    break;
                case 12: //hh:mm:ss
                    $retval = '00:00:00';
                    break;
                case 20: //hours with 2 decimal places
                    $retval = '0.00';
                    break;
                case 22: //hours with 3 decimal places
                    $retval = '0.000';
                    break;
                case 23: //hours with 4 decimal places
                    $retval = '0.0000';
                    break;
                case 30: //minutes
                    $retval = 0;
                    break;
                case 40: //seconds
                    $retval = 0;
                    break;
            }
        } else {
            switch ($time_unit_format) {
                case 10: //hh:mm
                    $retval = self::convertSecondsToHMS($seconds);
                    break;
                case 12: //hh:mm:ss
                    $retval = self::convertSecondsToHMS($seconds, true);
                    break;
                case 20: //hours with 2 decimal places
                    $retval = number_format(($seconds / 3600), 2); //Number format doesn't support large numbers.
                    break;
                case 22: //hours with 3 decimal places
                    $retval = number_format(($seconds / 3600), 3);
                    break;
                case 23: //hours with 3 decimal places
                    $retval = number_format(($seconds / 3600), 4);
                    break;
                case 30: //minutes
                    $retval = number_format(($seconds / 60), 0);
                    break;
                case 40: //seconds
                    $retval = number_format($seconds, 0);
                    break;
            }
        }

        if (isset($retval)) {
            return $retval;
        }

        return false;
    }

    public static function convertSecondsToHMS($seconds, $include_seconds = false)
    {
        if ($seconds < 0) {
            $negative_number = true;
        } else {
            $negative_number = false;
        }

        //Check to see if the value is larger than PHP_INT_MAX, so we can switch to using bcmath if needed.
        if (
        (//Check if we're passed a numeric string value that is greater than PHP_INT_MAX.
            is_string($seconds) == true
            and
            (
                ($negative_number == false and bccomp($seconds, PHP_INT_MAX, 0) === 1)
                or
                ($negative_number == true and bccomp($seconds, (PHP_INT_MAX * -1), 0) === -1)
            )
        )
        ) {
            //Greater than PHP_INT_MAX, use bcmath
            //Debug::Text( 'BIGINT Seconds: '. $seconds, __FILE__, __LINE__, __METHOD__, 10);

            if ($negative_number == true) {
                $seconds = substr($seconds, 1); //Remove negative sign to get absolute value.
            }

            //Check to see if there are decimals.
            if (strpos($seconds, '.') !== false) {
                $seconds = bcadd($seconds, 0, 0); //Using scale(0), drop everything after the decimal, as that is fractions of a second. Could try rounding this instead, but its difficult with large values.
            }

            if ($include_seconds == true) {
                $retval = sprintf('%02d:%02d:%02d', bcdiv($seconds, 3600), bcmod(bcdiv($seconds, 60), 60), bcmod($seconds, 60));
            } else {
                $retval = sprintf('%02d:%02d', bcdiv($seconds, 3600), bcmod(bcdiv($seconds, 60), 60));
            }
        } else {
            if (//Check if we're passed a FLOAT value that is greater than PHP_INT_MAX, as precision has been lost if that is the case.
                is_float($seconds) == true
                and
                (
                    ($negative_number == false and $seconds > PHP_INT_MAX)
                    or
                    ($negative_number == true and $seconds < (PHP_INT_MAX * -1))
                )
            ) {
                Debug::Text('  ERROR: Float value outside range, should be using BCMATH instead? Seconds: ' . $seconds, __FILE__, __LINE__, __METHOD__, 10);
                //return 'ERR(FLOAT)'; //Deactive this for now until we have more testing.
            }
            //else {
            $seconds = round(abs($seconds));

            //Using sprintf() is much more efficient, and handles large integers better too.
            if ($include_seconds == true) {
                $retval = sprintf('%02d:%02d:%02d', ($seconds / 3600), (($seconds / 60) % 60), ($seconds % 60));
            } else {
                $retval = sprintf('%02d:%02d', ($seconds / 3600), (($seconds / 60) % 60));
            }
            //}
        }

        if ($negative_number == true) {
            $negative = '-';
        } else {
            $negative = '';
        }

        return $negative . $retval;
    }

    public static function roundTime($epoch, $round_value, $round_type = 20, $grace_time = 0)
    {

        //Debug::text('In Epoch: '. $epoch .' ('.TTDate::getDate('DATE+TIME', $epoch).') Round Value: '. $round_value .' Round Type: '. $round_type, __FILE__, __LINE__, __METHOD__, 10);

        if (empty($epoch) or empty($round_value) or empty($round_type)) {
            return $epoch;
        }

        switch ($round_type) {
            case 10: //Down
                if ($grace_time > 0) {
                    $epoch += $grace_time;
                }
                $epoch = ($epoch - ($epoch % $round_value));
                break;
            case 20: //Average
            case 25: //Average (round split seconds up)
            case 27: //Average (round split seconds down)
                //Only do special rounding if its for more than 1min.
                if ($round_type == 20 or $round_value <= 60) {
                    $tmp_round_value = ($round_value / 2);
                } elseif ($round_type == 25) { //Average (Partial Min. Down)
                    $tmp_round_value = self::roundTime(($round_value / 2), 60, 10); //This is opposite rounding
                } elseif ($round_type == 27) { //Average (Partial Min. Up)
                    $tmp_round_value = self::roundTime(($round_value / 2), 60, 30);
                }

                if ($epoch > 0) {
                    //$epoch = ( (int)( ($epoch + ($round_value / 2) ) / $round_value ) * $round_value );
                    //When doing a 15min average rounding, US law states 7mins and 59 seconds can be rounded down in favor of the employer, and 8mins and 0 seconds must be rounded up.
                    //So if the round interval is not an even number, round it up to the nearest minute before doing the calculations to avoid issues with seconds.
                    $epoch = ((int)(($epoch + $tmp_round_value) / $round_value) * $round_value);
                } else {
                    //$epoch = ( (int)( ($epoch - ($round_value / 2) ) / $round_value ) * $round_value );
                    $epoch = ((int)(($epoch - $tmp_round_value) / $round_value) * $round_value);
                }
                break;
            case 30: //Up
                if ($grace_time > 0) {
                    $epoch -= $grace_time;
                }
                $epoch = ((int)(($epoch + ($round_value - 1)) / $round_value) * $round_value);
                break;
        }

        return $epoch;
    }

    public static function parseDateTime($str)
    {
        if (is_array($str) or is_object($str)) {
            Debug::Arr($str, 'Date is array or object, unable to parse...', __FILE__, __LINE__, __METHOD__, 10);
            return false;
        }

        //List of all formats that require custom parsing.
        $custom_parse_formats = array(
            'd-M-y',
            'd/m/Y',
            'd/m/y',
            'd-m-y',
            'd-m-Y',
            'm/d/y',
            'm/d/Y',
            'm-d-y',
            'm-d-Y',
            'Y-m-d',
            'M-d-y',
            'M-d-Y',
        );

        //This fails to parse Ymd or any other integer only date format as it thinks its a epoch value instead.
        //To properly parse Ymd format, we have to alter the way we detect epochs from a basic is_numeric() check to include the Ymd check too.
        //This causes dates between about 1970 to 1973 to fail to parse properly.

        $str = trim($str);

        if ($str == '') {
            //Debug::text('No date to parse! String: '. $str .' Date Format: '. self::$date_format, __FILE__, __LINE__, __METHOD__, 10);
            //Return NULL so we can determine the difference between a blank/null value and an incorrect parsing.
            //NULL is required so NULL is used in the database rather than 0. Especially for termination dates for users.
            return null;
        }

        //Debug::text('String: '. $str .' Date Format: '. self::$date_format, __FILE__, __LINE__, __METHOD__, 10);
        if (!is_numeric($str) and in_array(self::$date_format, $custom_parse_formats)) {
            //Debug::text('	 Custom Parse Format detected!', __FILE__, __LINE__, __METHOD__, 10);
            //Match to: Year, Month, Day
            $textual_month = false;
            switch (self::$date_format) {
                case 'd-M-y':
                    //Two digit year, custom parsing for it to have more control over 1900 or 2000 years.
                    //PHP handles it like this: values between 00-69 are mapped to 2000-2069 and 70-99 to 1970-1999
                    //Debug::text('	 Parsing format: M-d-y', __FILE__, __LINE__, __METHOD__, 10);
                    $date_pattern = '/([0-9]{1,2})\-([A-Za-z]{3})\-([0-9]{2,4})/';
                    $match_arr = array('year' => 3, 'month' => 2, 'day' => 1);
                    $textual_month = true;
                    break;
                case 'M-d-y':
                case 'M-d-Y':
                    //Debug::text('	 Parsing format: M-d-y', __FILE__, __LINE__, __METHOD__, 10);
                    $date_pattern = '/([A-Za-z]{3})\-([0-9]{1,2})\-([0-9]{2,4})/';
                    $match_arr = array('year' => 3, 'month' => 1, 'day' => 2);
                    $textual_month = true;
                    break;
                case 'm-d-y':
                case 'm-d-Y':
                    //Debug::text('	 Parsing format: m-d-y', __FILE__, __LINE__, __METHOD__, 10);
                    $date_pattern = '/([0-9]{1,2})\-([0-9]{1,2})\-([0-9]{2,4})/';
                    $match_arr = array('year' => 3, 'month' => 1, 'day' => 2);
                    break;
                case 'm/d/y':
                case 'm/d/Y':
                    //Debug::text('	 Parsing format: m/d/y', __FILE__, __LINE__, __METHOD__, 10);
                    $date_pattern = '/([0-9]{1,2})\/([0-9]{1,2})\/([0-9]{2,4})/';
                    $match_arr = array('year' => 3, 'month' => 1, 'day' => 2);
                    break;
                case 'd/m/y':
                case 'd/m/Y':
                    //Debug::text('	 Parsing format: d/m/y', __FILE__, __LINE__, __METHOD__, 10);
                    $date_pattern = '/([0-9]{1,2})\/([0-9]{1,2})\/([0-9]{2,4})/';
                    $match_arr = array('year' => 3, 'month' => 2, 'day' => 1);
                    break;
                case 'd-m-y':
                case 'd-m-Y':
                    //Debug::text('	 Parsing format: d-m-y', __FILE__, __LINE__, __METHOD__, 10);
                    $date_pattern = '/([0-9]{1,2})\-([0-9]{1,2})\-([0-9]{2,4})/';
                    $match_arr = array('year' => 3, 'month' => 2, 'day' => 1);
                    break;
                default:
                    //Debug::text('	 NO pattern match!', __FILE__, __LINE__, __METHOD__, 10);
                    break;
            }

            if (isset($date_pattern)) {
                //Make regex less strict, and attempt to match time as well.
                $date_result = preg_match($date_pattern, $str, $date_matches);

                if ($date_result != 0) {
                    //Debug::text('	 Custom Date Match Success!', __FILE__, __LINE__, __METHOD__, 10);

                    $date_arr = array(
                        'year' => $date_matches[$match_arr['year']],
                        'month' => $date_matches[$match_arr['month']],
                        'day' => $date_matches[$match_arr['day']],
                    );

                    //Handle dates less then 1970
                    //If the two digit year is greater then current year plus 10 we assume its a 1900 year.
                    //Debug::text('Passed Year: '. $date_arr['year'] ." Current Year threshold: ". (date('y')+10), __FILE__, __LINE__, __METHOD__, 10);
                    if (strlen($date_arr['year']) == 2 and $date_arr['year'] > (date('y') + 10)) {
                        $date_arr['year'] = (int)'19' . $date_arr['year'];
                    }
                    //Debug::Arr($date_arr, 'Date Match Arr!', __FILE__, __LINE__, __METHOD__, 10);

                    //; preg_match('/[a-z]/', $date_arr['month']) != 0
                    if ($textual_month == true and isset(self::$month_arr[strtolower($date_arr['month'])])) {
                        $numeric_month = self::$month_arr[strtolower($date_arr['month'])];
                        //Debug::text('	 Numeric Month: '. $numeric_month, __FILE__, __LINE__, __METHOD__, 10);
                        $date_arr['month'] = $numeric_month;
                        unset($numeric_month);
                    }

                    $tmp_date = $date_arr['year'] . '-' . $date_arr['month'] . '-' . $date_arr['day'];
                    //Debug::text('	 Tmp Date: '. $tmp_date, __FILE__, __LINE__, __METHOD__, 10);

                    //Replace the date pattern with NULL leaving only time left to append to the end of the string.
                    $time_result = preg_replace($date_pattern, '', $str);
                    $formatted_date = $tmp_date . ' ' . $time_result;
                } else {
                    Debug::text('  Custom Date Match Failed... Falling back to strtotime. Date String: ' . $str . ' Date Format: ' . self::$date_format, __FILE__, __LINE__, __METHOD__, 10);
                }
            }
        }

        if (!isset($formatted_date)) {
            //Debug::text('	 NO Custom Parse Format detected!', __FILE__, __LINE__, __METHOD__, 10);
            $formatted_date = $str;
        }
        //Debug::text('	 Parsing Date: '. $formatted_date, __FILE__, __LINE__, __METHOD__, 10);

        //On the Recurring Templates, if the user enters "0600", its passed here without a date, and parsed as "600" which is incorrect.
        //We worked around this in the API by prefixing the date infront of 0600 to make it a string instead
        if (is_numeric($formatted_date)) {
            $epoch = (int)$formatted_date;
        } else {
            //$epoch = self::strtotime( $formatted_date );
            $epoch = strtotime($formatted_date); //Don't use self::strtotime() as it treats all numeric values as epochs, which breaks handling for Ymd. Its faster too.

            //Parse failed.
            if ($epoch === false or $epoch === -1) {
                Debug::text('  Parsing Date Failed! Returning FALSE: ' . $formatted_date . ' Format: ' . self::$date_format, __FILE__, __LINE__, __METHOD__, 10);
                $epoch = false;
            }

            //Debug::text('	 Parsed Date: '. TTDate::getDate('DATE+TIME', $epoch) .' ('.$epoch.')', __FILE__, __LINE__, __METHOD__, 10);
        }

        return $epoch;
    }

    public static function getISOTimeStamp($epoch)
    {
        return date('r', $epoch);
    }

    public static function getAPIDate($format = 'DATE+TIME', $epoch)
    {
        return self::getDate($format, $epoch);
    }

    public static function getDate($format = null, $epoch = null)
    {
        if (!is_numeric($epoch) or $epoch == 0) {
            //Debug::text('Epoch is not numeric: '. $epoch, __FILE__, __LINE__, __METHOD__, 10);
            return false;
        }

        if (empty($format)) {
            Debug::text('Format is empty: ' . $format, __FILE__, __LINE__, __METHOD__, 10);
            $format = 'DATE';
        }

        switch (strtolower($format)) {
            case 'date':
                $php_format = self::$date_format;
                break;
            case 'time':
                $php_format = self::$time_format;
                break;
            case 'date+time':
                $php_format = self::$date_format . ' ' . self::$time_format;
                break;
            case 'epoch':
                $php_format = 'U';
                break;
        }
        //Debug::text('Format Name: '. $format .' Epoch: '. $epoch .' Format: '. $php_format, __FILE__, __LINE__, __METHOD__, 10);


        if ($epoch == '' or $epoch == '-1') {
            //$epoch = TTDate::getTime();
            //Don't return anything if EPOCH isn't set.
            //return FALSE;
            return null;
        }

        //Debug::text('Epoch: '. $epoch, __FILE__, __LINE__, __METHOD__, 10);
        //This seems to support pre 1970 dates..
        return date($php_format, $epoch);

        //Support pre 1970 dates?
        //return adodb_date($format, $epoch);
    }

    public static function getDBTimeStamp($epoch, $include_time_zone = true)
    {
        $format = 'Y-m-d H:i:s';
        if ($include_time_zone == true) {
            $format .= ' T';
        }

        return date($format, $epoch);
    }

    public static function getDayOfMonthArray()
    {
        $retarr = array();
        for ($i = 1; $i <= 31; $i++) {
            $retarr[$i] = $i;
        }
        return $retarr;
    }

    public static function getDayOfWeekArrayByStartWeekDay($start_week_day = 0)
    {
        $retarr = array();
        $arr = self::getDayOfWeekArray();
        foreach ($arr as $dow => $name) {
            if ($dow >= $start_week_day) {
                $retarr[$dow] = $name;
            }
        }

        if ($start_week_day > 0) {
            foreach ($arr as $dow => $name) {
                if ($dow < $start_week_day) {
                    $retarr[$dow] = $name;
                } else {
                    break;
                }
            }
        }

        return $retarr;
    }

    public static function getDayOfWeekArray($translation = true)
    {
        if ($translation == true and is_array(self::$day_of_week_arr) == false) {
            self::$day_of_week_arr = array(
                0 => TTi18n::getText('Sunday'),
                1 => TTi18n::getText('Monday'),
                2 => TTi18n::getText('Tuesday'),
                3 => TTi18n::getText('Wednesday'),
                4 => TTi18n::getText('Thursday'),
                5 => TTi18n::getText('Friday'),
                6 => TTi18n::getText('Saturday')
            );
        } else {
            //Translated days of week can't be piped back into strtotime() for parsing.
            self::$day_of_week_arr = array(
                0 => 'Sunday',
                1 => 'Monday',
                2 => 'Tuesday',
                3 => 'Wednesday',
                4 => 'Thursday',
                5 => 'Friday',
                6 => 'Saturday'
            );
        }
        return self::$day_of_week_arr;
    }

    public static function doesRangeSpanDST($start_epoch, $end_epoch)
    {
        if (date('I', $start_epoch) != date('I', $end_epoch)) {
            $retval = true;
        } else {
            $retval = false;
        }

        //Debug::text('Start Epoch: '. TTDate::getDate('DATE+TIME', $start_epoch) .'  End Epoch: '. TTDate::getDate('DATE+TIME', $end_epoch) .' Retval: '. (int)$retval, __FILE__, __LINE__, __METHOD__, 10);
        return $retval;
    }

    public static function getDSTOffset($start_epoch, $end_epoch)
    {
        if (date('I', $start_epoch) == 0 and date('I', $end_epoch) == 1) {
            $retval = 3600; //DST==TRUE: Spring - Spring ahead an hour, which means we lose an hour, so we add one hour from the offset.
        } elseif (date('I', $start_epoch) == 1 and date('I', $end_epoch) == 0) {
            $retval = -3600; //DST==FALSE: Fall - Fall back an hour, which means we gain an hour, or minus one hour to the offset
        } else {
            $retval = 0;
        }

        //Debug::text('Start Epoch: '. TTDate::getDate('DATE+TIME', $start_epoch) .'('.date('I', $start_epoch).')  End Epoch: '. TTDate::getDate('DATE+TIME', $end_epoch) .'('.date('I', $end_epoch).') Retval: '. $retval, __FILE__, __LINE__, __METHOD__, 10);
        return $retval;
    }

    public static function getSeconds($hours)
    {
        return bcmul($hours, 3600);
    }

    public static function getHours($seconds)
    {
        return bcdiv($seconds, 3600);
    }

    public static function getWeeks($seconds)
    {
        return bcdiv($seconds, (86400 * 7));
    }

    public static function getYears($seconds)
    {
        return bcdiv(bcdiv($seconds, 86400), 365);
    }

    public static function getDaysInYear($epoch = null)
    {
        if ($epoch == null) {
            $epoch = TTDate::getTime();
        }

        return date('z', TTDate::getEndYearEpoch($epoch));
    }

    public static function getEndYearEpoch($epoch = null)
    {
        if ($epoch == null or $epoch == '' or !is_numeric($epoch)) {
            $epoch = self::getTime();
        }

        //Debug::text('Attempting to Find End Of Year epoch for: '. TTDate::getDate('DATE+TIME', $epoch), __FILE__, __LINE__, __METHOD__, 10);

        $retval = (mktime(0, 0, 0, 1, 1, (date('Y', $epoch) + 1)) - 1);

        return $retval;
    }

    public static function incrementDate($epoch, $amount, $unit)
    {
        $date_arr = getdate($epoch);

        //Unit: minute, hour, day
        switch ($unit) {
            case 'minute':
                $retval = mktime($date_arr['hours'], ($date_arr['minutes'] + $amount), 0, $date_arr['mon'], $date_arr['mday'], $date_arr['year']);
                break;
            case 'hour':
                $retval = mktime(($date_arr['hours'] + $amount), $date_arr['minutes'], 0, $date_arr['mon'], $date_arr['mday'], $date_arr['year']);
                break;
            case 'day':
                $retval = mktime($date_arr['hours'], $date_arr['minutes'], 0, $date_arr['mon'], ($date_arr['mday'] + $amount), $date_arr['year']);
                break;
            case 'week':
                $retval = mktime($date_arr['hours'], $date_arr['minutes'], 0, $date_arr['mon'], ($date_arr['mday'] + ($amount * 7)), $date_arr['year']);
                break;
            case 'month':
                $retval = mktime($date_arr['hours'], $date_arr['minutes'], 0, ($date_arr['mon'] + $amount), $date_arr['mday'], $date_arr['year']);
                break;
            case 'year':
                $retval = mktime($date_arr['hours'], $date_arr['minutes'], 0, $date_arr['mon'], $date_arr['mday'], ($date_arr['year'] + $amount));
                break;
        }

        return $retval;
    }

    public static function snapTime($epoch, $snap_to_epoch, $snap_type)
    {
        Debug::text('Epoch: ' . $epoch . ' (' . TTDate::getDate('DATE+TIME', $epoch) . ') Snap Epoch: ' . $snap_to_epoch . ' (' . TTDate::getDate('DATE+TIME', $snap_to_epoch) . ') Snap Type: ' . $snap_type, __FILE__, __LINE__, __METHOD__, 10);

        if (empty($epoch) or empty($snap_to_epoch)) {
            return $epoch;
        }

        switch (strtolower($snap_type)) {
            case 'up':
                Debug::text('Snap UP: ', __FILE__, __LINE__, __METHOD__, 10);
                if ($epoch <= $snap_to_epoch) {
                    $epoch = $snap_to_epoch;
                }
                break;
            case 'down':
                Debug::text('Snap Down: ', __FILE__, __LINE__, __METHOD__, 10);
                if ($epoch >= $snap_to_epoch) {
                    $epoch = $snap_to_epoch;
                }
                break;
        }

        Debug::text('Snapped Epoch: ' . $epoch . ' (' . TTDate::getDate('DATE+TIME', $epoch) . ')', __FILE__, __LINE__, __METHOD__, 10);
        return $epoch;
    }

    public static function graceTime($current_epoch, $grace_time, $schedule_epoch)
    {
        //Debug::text('Current Epoch: '. $current_epoch .' Grace Time: '. $grace_time .' Schedule Epoch: '. $schedule_epoch, __FILE__, __LINE__, __METHOD__, 10);
        if ($current_epoch <= ($schedule_epoch + $grace_time)
            and $current_epoch >= ($schedule_epoch - $grace_time)
        ) {
            //Within grace period, return scheduled time.
            return $schedule_epoch;
        }

        return $current_epoch;
    }

    public static function getTimeStampFromSmarty($prefix, $array)
    {
        Debug::text('Prefix: ' . $prefix, __FILE__, __LINE__, __METHOD__, 10);
        //Debug::Arr($array, 'getTimeStampFromSmarty Array:', __FILE__, __LINE__, __METHOD__, 10);

        if (isset($array[$prefix . 'Year'])) {
            $year = $array[$prefix . 'Year'];
        } else {
            $year = strftime("%Y");
        }
        if (isset($array[$prefix . 'Month'])) {
            $month = $array[$prefix . 'Month'];
        } else {
            //$month = strftime("%m");
            $month = 1;
        }
        if (isset($array[$prefix . 'Day'])) {
            $day = $array[$prefix . 'Day'];
        } else {
            //If day isn't specified it uses the current day, but then if its the 30th, and they
            //select February, it goes to March!
            //$day = strftime("%d");
            $day = 1;
        }
        if (isset($array[$prefix . 'Hour'])) {
            $hour = $array[$prefix . 'Hour'];
        } else {
            $hour = 0;
        }
        if (isset($array[$prefix . 'Minute'])) {
            $min = $array[$prefix . 'Minute'];
        } else {
            $min = 0;
        }
        if (isset($array[$prefix . 'Second'])) {
            $sec = $array[$prefix . 'Second'];
        } else {
            $sec = 0;
        }

        Debug::text('Year: ' . $year . ' Month: ' . $month . ' Day: ' . $day . ' Hour: ' . $hour . ' Min: ' . $min . ' Sec: ' . $sec, __FILE__, __LINE__, __METHOD__, 10);

        return self::getTimeStamp($year, $month, $day, $hour, $min, $sec);
    }

    public static function getTimeStamp($year = "", $month = "", $day = "", $hour = 0, $min = 0, $sec = 0)
    {
        if (empty($year)) {
            $year = strftime("%Y");
        }

        if (empty($month)) {
            $month = strftime("%m");
        }

        if (empty($day)) {
            $day = strftime("%d");
        }

        if (empty($hour)) {
            $hour = 0;
        }

        if (empty($min)) {
            $min = 0;
        }

        if (empty($sec)) {
            $sec = 0;
        }

        //Use adodb time library to support dates earlier then 1970.
        //require_once( Environment::getBasePath() .'classes/adodb/adodb-time.inc.php');
        //Debug::text('	 - Year: '. $year .' Month: '. $month .' Day: '. $day .' Hour: '. $hour .' Min: '. $min .' Sec: '. $sec, __FILE__, __LINE__, __METHOD__, 10);
        $epoch = adodb_mktime($hour, $min, $sec, $month, $day, $year);
        //Debug::text('Epoch: '. $epoch .' Date: '. self::getDate($epoch), __FILE__, __LINE__, __METHOD__, 10);

        return $epoch;
    }

    public static function getDayWithMostTime($start_epoch, $end_epoch)
    {
        $time_on_start_date = (TTDate::getEndDayEpoch($start_epoch) - $start_epoch);
        $time_on_end_date = ($end_epoch - TTDate::getBeginDayEpoch($end_epoch));
        if ($time_on_start_date > $time_on_end_date) {
            $day_with_most_time = $start_epoch;
        } else {
            $day_with_most_time = $end_epoch;
        }

        return $day_with_most_time;
    }

    public static function getEndDayEpoch($epoch = null)
    {
        if ($epoch == null or $epoch == '' or !is_numeric($epoch)) {
            $epoch = self::getTime();
        }

        $retval = (mktime(0, 0, 0, date('m', $epoch), (date('d', $epoch) + 1), date('Y', $epoch)) - 1);
        //Debug::text('Begin Day Epoch: '. $retval .' - '. TTDate::getDate('DATE+TIME', $retval), __FILE__, __LINE__, __METHOD__, 10);
        return $retval;
    }

    public static function getBeginDayEpoch($epoch = null)
    {
        if ($epoch == null or $epoch == '' or !is_numeric($epoch)) {
            $epoch = self::getTime();
        }

        //$retval = mktime(0, 0, 0, date('m', $epoch), date('d', $epoch), date('Y', $epoch)); //1million runs = 12165ms
        //$retval = strtotime( 'midnight', $epoch ); //1million runs = 14030ms
        $date = getdate($epoch);
        return mktime(0, 0, 0, $date['mon'], $date['mday'], $date['year']); //1 million runs = 9159ms

        //Debug::text('Begin Day Epoch: '. $retval .' - '. TTDate::getDate('DATE+TIME', $retval) .' Epoch: '. $epoch .' - '. TTDate::getDate('DATE+TIME', $epoch) .' TimeZone: '. self::getTimeZone(), __FILE__, __LINE__, __METHOD__, 10);
        //return $retval;
    }

    public static function getDayDifference($start_epoch, $end_epoch, $round = true)
    {
        if ($start_epoch == '' or $end_epoch == '') {
            return false;
        }

        //This already matches PHPs DateTime class.
        $days = (($end_epoch - $start_epoch) / 86400);
        if ($round == true) {
            $days = round($days);
        }

        //Debug::text('Days Difference: '. $days, __FILE__, __LINE__, __METHOD__, 10);

        return $days;
    }

    public static function getWeekDifference($start_epoch, $end_epoch)
    {
        if ($start_epoch == '' or $end_epoch == '') {
            return false;
        }

        //This already matches PHPs DateTime class.
        $weeks = (($end_epoch - $start_epoch) / (86400 * 7));
        Debug::text('Week Difference: ' . $weeks, __FILE__, __LINE__, __METHOD__, 10);

        return $weeks;
    }

    public static function getMonthDifference($start_epoch, $end_epoch)
    {
        if ($start_epoch == '' or $end_epoch == '') {
            return false;
        }

        //Debug::text('Start Epoch: '. TTDate::getDate('DATE+TIME', $start_epoch) .' End Epoch: '. TTDate::getDate('DATE+TIME', $end_epoch), __FILE__, __LINE__, __METHOD__, 10);

        if (function_exists('date_diff')) {
            //If available, try to be as accurate as possible.
            $diff = date_diff(new DateTime('@' . $end_epoch), new DateTime('@' . $start_epoch), false);
            $x = ((($diff->y * 12) + $diff->m) + ($diff->d / 30));
        } else {
            $epoch_diff = ($end_epoch - $start_epoch);
            //Debug::text('Diff Epoch: '. $epoch_diff, __FILE__, __LINE__, __METHOD__, 10);
            $x = floor(($epoch_diff / (86400 * 30.436875)));
        }
        Debug::text('Month Difference: ' . $x, __FILE__, __LINE__, __METHOD__, 10);

        return $x;
    }

    public static function getYearDifference($start_epoch, $end_epoch)
    {
        if ($start_epoch == '' or $end_epoch == '') {
            return false;
        }

        if (function_exists('date_diff')) {
            //If available, try to be as accurate as possible.
            $diff = date_diff(new DateTime('@' . $start_epoch), new DateTime('@' . $end_epoch), false);
            $years = ($diff->y + ($diff->m / 12) + ($diff->d / 365.25));
        } else {
            $years = ((($end_epoch - $start_epoch) / (86400 * 365.25)));
        }
        //Debug::text('Years Difference: '. $years, __FILE__, __LINE__, __METHOD__, 10);

        return $years;
    }

    public static function getDateByMonthOffset($epoch, $month_offset)
    {
        //return mktime(0, 0, 0, date('n', $epoch) + $month_offset, date('j', $epoch), date('Y', $epoch) );
        return mktime(date('G', $epoch), date('i', $epoch), date('s', $epoch), (date('n', $epoch) + $month_offset), date('j', $epoch), date('Y', $epoch));
    }

    public static function getBeginMinuteEpoch($epoch = null)
    {
        if ($epoch == null or $epoch == '' or !is_numeric($epoch)) {
            $epoch = self::getTime();
        }

        $retval = mktime(date('G', $epoch), date('i', $epoch), 0, date('m', $epoch), date('d', $epoch), date('Y', $epoch));
        //Debug::text('Begin Day Epoch: '. $retval .' - '. TTDate::getDate('DATE+TIME', $retval), __FILE__, __LINE__, __METHOD__, 10);
        return $retval;
    }

    public static function getFiscalYearFromEpoch($epoch, $offset = 3)
    {
        switch (strtolower($offset)) {
            case 'us':
                $offset = 3;
                break;
            case 'ca':
                $offset = -3;
                break;
            default:
                break;
        }

        //Offset is in months.
        if ($offset > 0) {
            //Fiscal year is ahead, so it switches to 2016 when still in 2015.
            $offset_str = '+' . $offset . ' months';
        } else {
            //Fiscal year is behind, so its still 2015 when the year is in 2016.
            $offset_str = $offset . ' months';
        }
        $adjusted_epoch = strtotime($offset_str, $epoch);

        $retval = date('Y', $adjusted_epoch);
        //Debug::text('Epoch: '. TTDate::getDate('DATE+TIME', $epoch) .' Adjusted Epoch: '. TTDate::getDate('DATE+TIME', $adjusted_epoch)  .' Retval: '. $retval .' Offset: '. $offset_str, __FILE__, __LINE__, __METHOD__, 10);

        return $retval;
    }

    public static function getYearQuarterMonth($epoch = null)
    {
        $year_quarter_months = array(
            1 => 1,
            2 => 1,
            3 => 1,
            4 => 2,
            5 => 2,
            6 => 2,
            7 => 3,
            8 => 3,
            9 => 3,
            10 => 4,
            11 => 4,
            12 => 4,
        );

        $month = TTDate::getMonth($epoch);

        if (isset($year_quarter_months[$month])) {
            return $year_quarter_months[$month];
        }

        return false;
    }

    public static function getMonth($epoch = null)
    {
        if ($epoch == null or $epoch == '') {
            $epoch = self::getTime();
        }

        return date('n', $epoch);
    }

    public static function getDateOfNextDayOfWeek($anchor_epoch, $day_of_week_epoch)
    {
        //Anchor Epoch is the anchor date to start searching from.
        //Day of week epoch is the epoch we use to extract the day of the week from.
        Debug::text('-------- ', __FILE__, __LINE__, __METHOD__, 10);
        Debug::text('Anchor Epoch: ' . TTDate::getDate('DATE+TIME', $anchor_epoch), __FILE__, __LINE__, __METHOD__, 10);
        Debug::text('Day Of Week Epoch: ' . TTDate::getDate('DATE+TIME', $day_of_week_epoch), __FILE__, __LINE__, __METHOD__, 10);

        if ($anchor_epoch == '') {
            return false;
        }

        if ($day_of_week_epoch == '') {
            return false;
        }

        //Get day of week of the anchor
        $anchor_dow = date('w', $anchor_epoch);
        $dst_dow = date('w', $day_of_week_epoch);
        Debug::text('Anchor DOW: ' . $anchor_dow . ' Destination DOW: ' . $dst_dow, __FILE__, __LINE__, __METHOD__, 10);

        $days_diff = ($anchor_dow - $dst_dow);
        Debug::text('Days Diff: ' . $days_diff, __FILE__, __LINE__, __METHOD__, 10);

        if ($days_diff > 0) {
            //Add 7 days (1 week) then minus the days diff.
            $anchor_epoch += 604800;
        }

        $retval = mktime(date('H', $day_of_week_epoch),
            date('i', $day_of_week_epoch),
            date('s', $day_of_week_epoch),
            date('m', $anchor_epoch),
            (date('j', $anchor_epoch) - $days_diff),
            date('Y', $anchor_epoch)
        );

        Debug::text('Retval: ' . TTDate::getDate('DATE+TIME', $retval), __FILE__, __LINE__, __METHOD__, 10);
        return $retval;
    }

    public static function getDateOfNextDayOfMonth($anchor_epoch, $day_of_month_epoch, $day_of_month = null)
    {
        //Anchor Epoch is the anchor date to start searching from.
        //Day of month epoch is the epoch we use to extract the day of the month from.
        Debug::text('-------- ', __FILE__, __LINE__, __METHOD__, 10);
        Debug::text('Anchor Epoch: ' . TTDate::getDate('DATE+TIME', $anchor_epoch) . ' Day Of Month Epoch: ' . TTDate::getDate('DATE+TIME', $day_of_month_epoch) . ' Day Of Month: ' . $day_of_month, __FILE__, __LINE__, __METHOD__, 10);

        if ($anchor_epoch == '') {
            return false;
        }

        if ($day_of_month_epoch == '' and $day_of_month == '') {
            return false;
        }

        if ($day_of_month_epoch == '' and $day_of_month != '' and $day_of_month <= 31) {
            $tmp_days_in_month = TTDate::getDaysInMonth($anchor_epoch);
            if ($day_of_month > $tmp_days_in_month) {
                $day_of_month = $tmp_days_in_month;
            }
            unset($tmp_days_in_month);

            $day_of_month_epoch = mktime(date('H', $anchor_epoch),
                date('i', $anchor_epoch),
                date('s', $anchor_epoch),
                date('m', $anchor_epoch),
                $day_of_month,
                date('Y', $anchor_epoch)
            );
        }

        //If the anchor date is AFTER the day of the month, we want to get the same day
        //in the NEXT month.
        $src_dom = date('j', $anchor_epoch);
        $dst_dom = date('j', $day_of_month_epoch);
        //Debug::text('Anchor DOM: '. $src_dom .' DST DOM: '. $dst_dom, __FILE__, __LINE__, __METHOD__, 10);

        if ($src_dom > $dst_dom) {
            //Debug::text('Anchor DOM is greater then Dest DOM', __FILE__, __LINE__, __METHOD__, 10);

            //Get the epoch of the first day of the next month
            //Use getMiddleDayEpoch so daylight savings doesn't throw us off.
            $anchor_epoch = TTDate::getMiddleDayEpoch((TTDate::getEndMonthEpoch($anchor_epoch) + 1));

            //Find out how many days are in this month
            $days_in_month = TTDate::getDaysInMonth($anchor_epoch);

            if ($dst_dom > $days_in_month) {
                $dst_dom = $days_in_month;
            }
            $retval = ($anchor_epoch + (($dst_dom - 1) * 86400));
        } else {
            //Debug::text('Anchor DOM is equal or LESS then Dest DOM', __FILE__, __LINE__, __METHOD__, 10);

            $retval = mktime(date('H', $anchor_epoch),
                date('i', $anchor_epoch),
                date('s', $anchor_epoch),
                date('m', $anchor_epoch),
                date('j', $day_of_month_epoch),
                date('Y', $anchor_epoch)
            );
        }

        return TTDate::getBeginDayEpoch($retval);
    }

    public static function getDaysInMonth($epoch = null)
    {
        if ($epoch == null) {
            $epoch = TTDate::getTime();
        }

        return date('t', $epoch);
    }

    public static function getMiddleDayEpoch($epoch = null)
    {
        if ($epoch == '' or !is_numeric($epoch)) { //Optimize out the $epoch == NULL check as its done by == ''.
            //if ( $epoch == NULL OR $epoch == '' OR !is_numeric($epoch) ) {
            $epoch = self::getTime();
        }

        $date = getdate($epoch);
        return mktime(12, 0, 0, $date['mon'], $date['mday'], $date['year']); //4.2secs x 500000x
        //return strtotime( 'noon', $epoch ); //7.6secs = 500,000x
        //$retval = mktime(12, 0, 0, date('m', $epoch), date('d', $epoch), date('Y', $epoch)); //4secs = 50,000x

        //Debug::text('Middle (noon) Day Epoch: '. $retval .' - '. TTDate::getDate('DATE+TIME', $retval), __FILE__, __LINE__, __METHOD__, 10);
        //return $retval;
    }

    public static function getEndMonthEpoch($epoch = null)
    {
        if ($epoch == null or $epoch == '' or !is_numeric($epoch)) {
            $epoch = self::getTime();
        }

        $retval = (mktime(0, 0, 0, (date('m', $epoch) + 1), 1, date('Y', $epoch)) - 1);

        return $retval;
    }

    public static function getDateOfNextYear($anchor_epoch, $year_epoch)
    {
        //Anchor Epoch is the anchor date to start searching from.
        //Day of year epoch is the epoch we use to extract the day of the year from.
        Debug::text('-------- ', __FILE__, __LINE__, __METHOD__, 10);
        Debug::text('Anchor Epoch: ' . TTDate::getDate('DATE+TIME', $anchor_epoch), __FILE__, __LINE__, __METHOD__, 10);

        if ($anchor_epoch == '') {
            return false;
        }

        $retval = mktime(date('H', $anchor_epoch),
            date('i', $anchor_epoch),
            date('s', $anchor_epoch),
            date('m', $anchor_epoch),
            date('j', $anchor_epoch),
            date('Y', $year_epoch)
        );

        Debug::text('Retval: ' . TTDate::getDate('DATE+TIME', $retval), __FILE__, __LINE__, __METHOD__, 10);
        return $retval;
    }

    public static function getLastHireDateAnniversary($hire_date)
    {
        Debug::Text('Hire Date: ' . $hire_date . ' - ' . TTDate::getDate('DATE+TIME', $hire_date), __FILE__, __LINE__, __METHOD__, 10);

        //Find last hire date anniversery.
        $last_hire_date_anniversary = gmmktime(12, 0, 0, date('n', $hire_date), date('j', $hire_date), (date('Y', TTDate::getTime())));
        //If its after todays date, minus a year from it.
        if ($last_hire_date_anniversary >= TTDate::getTime()) {
            $last_hire_date_anniversary = mktime(0, 0, 0, date('n', $hire_date), date('j', $hire_date), (date('Y', TTDate::getTime()) - 1));
        }
        Debug::Text('Last Hire Date Anniversary: ' . $last_hire_date_anniversary . ' - ' . TTDate::getDate('DATE+TIME', $last_hire_date_anniversary), __FILE__, __LINE__, __METHOD__, 10);

        return $last_hire_date_anniversary;
    }

    public static function getAnnualWeekDays($epoch = null)
    {
        if ($epoch == null or $epoch == '') {
            $epoch = self::getTime();
        }

        //Get the year of the passed epoch
        $year = date('Y', $epoch);

        $end_date = mktime(0, 0, 0, 1, 0, ($year + 1));
        $end_day_of_week = date('w', $end_date);
        $second_end_day_of_week = date('w', ($end_date - 86400));
        //Debug::text('End Date: ('.$end_day_of_week.') '. $end_date .' - '. TTDate::getDate('DATE+TIME', $end_date), __FILE__, __LINE__, __METHOD__, 10);
        //Debug::text('2nd End Date: ('.$second_end_day_of_week.') '. ( $end_date - 86400 ) .' - '. TTDate::getDate('DATE+TIME', ($end_date - 86400 ) ), __FILE__, __LINE__, __METHOD__, 10);

        //Eriks method
        //Always start with 260 days.
        //If the last day of the year is a weekday, add 1
        //If its a leap year, use the 2 last days. If any of them are weekdays, add them.
        $start_days = 260;

        //Debug::text('Leap Year: '. date('L', $end_date), __FILE__, __LINE__, __METHOD__, 10);

        if (date('L', $end_date) == 1) {
            //Leap year
            if ($end_day_of_week != 0 and $end_day_of_week != 6) {
                $start_days++;
            }
            if ($second_end_day_of_week != 0 and $second_end_day_of_week != 6) {
                $start_days++;
            }
        } else {
            //Not leap year

            if ($end_day_of_week != 0 and $end_day_of_week != 6) {
                $start_days++;
            }
        }
        //Debug::text('Days in Year: ('. $year .'): '. $start_days, __FILE__, __LINE__, __METHOD__, 10);


        return $start_days;
    }

    public static function getNearestWeekDay($epoch, $type = 0, $exclude_epochs = array())
    {
        Debug::Text('Epoch: ' . TTDate::getDate('DATE+TIME', $epoch) . ' Type: ' . $type, __FILE__, __LINE__, __METHOD__, 10);

        while (TTDate::isWeekDay($epoch) == false or in_array(TTDate::getBeginDayEpoch($epoch), $exclude_epochs)) {
            Debug::text('<b>FOUND WeekDay/HOLIDAY!</b>', __FILE__, __LINE__, __METHOD__, 10);
            switch ($type) {
                case 0: //No adjustment
                    break 2;
                case 1: //Previous day
                    $epoch -= 86400;
                    break;
                case 2: //Next day
                    $epoch += 86400;
                    break;
                case 3: //Closest day
                    $forward_epoch = $epoch;
                    $forward_days = 0;
                    while (TTDate::isWeekDay($forward_epoch) == false or in_array(TTDate::getBeginDayEpoch($forward_epoch), $exclude_epochs)) {
                        $forward_epoch += 86400;
                        $forward_days++;
                    }

                    $backward_epoch = $epoch;
                    $backward_days = 0;
                    while (TTDate::isWeekDay($backward_epoch) == false or in_array(TTDate::getBeginDayEpoch($backward_epoch), $exclude_epochs)) {
                        $backward_epoch -= 86400;
                        $backward_days++;
                    }

                    if ($backward_days <= $forward_days) {
                        $epoch = $backward_epoch;
                    } else {
                        $epoch = $forward_epoch;
                    }
                    break;
            }
        }

        return $epoch;
    }

    public static function isWeekDay($epoch = null)
    {
        if ($epoch == null or empty($epoch)) {
            $epoch = TTDate::getTime();
        }

        $day_of_week = date('w', $epoch);
        //Make sure day is not Sat. or Sun
        if ($day_of_week != 0 and $day_of_week != 6) {
            //Definitely a business day of week, make sure its not a holiday now.
            return true;
        }

        return false;
    }

    public static function getDateArray($start_date, $end_date, $day_of_week = false)
    {
        $start_date = TTDate::getMiddleDayEpoch($start_date);
        $end_date = TTDate::getMiddleDayEpoch($end_date);

        $retarr = array();
        for ($x = $start_date; $x <= $end_date; $x += 93600) {
            $x = TTDate::getBeginDayEpoch($x);
            //Make sure we use $day_of_week === FALSE check here, because it could come through as (int)0 for Sunday.
            if ($day_of_week === false or TTDate::getDayOfWeek($x) == $day_of_week) {
                $retarr[] = $x;
            }
        }

        return $retarr;
    }

    public static function getDayOfWeek($epoch, $start_week_day = 0)
    {
        $dow = date('w', (int)$epoch);

        if ($start_week_day == 0) {
            return $dow;
        } else {
            $retval = ($dow - $start_week_day);
            if ($dow < $start_week_day) {
                $retval = ($dow + (7 - $start_week_day));
            }
            return $retval;
        }
    }

    public static function getCalendarArray($start_date, $end_date, $start_day_of_week = 0, $force_weeks = true)
    {
        if ($start_date == '' or $end_date == '') {
            return false;
        }

        Debug::text(' Start Day Of Week: ' . $start_day_of_week, __FILE__, __LINE__, __METHOD__, 10);
        Debug::text(' Raw Start Date: ' . TTDate::getDate('DATE+TIME', $start_date) . ' Raw End Date: ' . TTDate::getDate('DATE+TIME', $end_date), __FILE__, __LINE__, __METHOD__, 10);

        if ($force_weeks == true) {
            $cal_start_date = TTDate::getBeginWeekEpoch($start_date, $start_day_of_week);
            $cal_end_date = TTDate::getEndWeekEpoch($end_date, $start_day_of_week);
        } else {
            $cal_start_date = $start_date;
            $cal_end_date = $end_date;
        }

        Debug::text(' Cal Start Date: ' . TTDate::getDate('DATE+TIME', $cal_start_date) . ' Cal End Date: ' . TTDate::getDate('DATE+TIME', $cal_end_date), __FILE__, __LINE__, __METHOD__, 10);

        $prev_month = null;
        $x = 0;
        //Gotta add more then 86400 because of day light savings time. Causes infinite loop without it.
        //Don't add 7200 to Cal End Date because that could cause more then one week to be displayed.
        $retarr = array();
        for ($i = $cal_start_date; $i <= ($cal_end_date); $i += 93600) {
            if ($x > 200) {
                break;
            }

            $i = TTDate::getBeginDayEpoch($i);

            $current_month = date('n', $i);
            $current_day_of_week = date('w', $i);

            if ($current_month != $prev_month and $i >= $start_date) {
                $isNewMonth = true;
            } else {
                $isNewMonth = false;
            }

            if ($current_day_of_week == $start_day_of_week) {
                $isNewWeek = true;
            } else {
                $isNewWeek = false;
            }

            //Display only blank boxes if the date is before the filter start date, or after.
            if ($i >= $start_date and $i <= $end_date) {
                $day_of_week = TTi18n::getText(date('D', $i)); // i18n: these short day strings may not be in .po file.
                $day_of_month = date('j', $i);
                $month_name = TTi18n::getText(date('F', $i)); // i18n: these short month strings may not be defined in .po file.
            } else {
                $day_of_week = null;
                $day_of_month = null;
                $month_name = null;
            }

            $retarr[] = array(
                'epoch' => $i,
                'date_stamp' => TTDate::getISODateStamp($i),
                'start_day_of_week' => $start_day_of_week,
                'day_of_week' => $day_of_week,
                'day_of_month' => $day_of_month,
                'month_name' => $month_name,
                'month_short_name' => substr($month_name, 0, 3),
                'month' => $current_month,
                'isNewMonth' => $isNewMonth,
                'isNewWeek' => $isNewWeek
            );

            $prev_month = $current_month;

            //Debug::text('i: '. $i .' Date: '. TTDate::getDate('DATE+TIME', $i), __FILE__, __LINE__, __METHOD__, 10);
            $x++;
        }

        return $retarr;
    }

    public static function getBeginWeekEpoch($epoch = null, $start_day_of_week = 0)
    {
        if ($epoch == null or $epoch == '') {
            $epoch = self::getTime();
        }

        if (!is_numeric($start_day_of_week)) {
            if (strtolower($start_day_of_week) == 'mon') {
                $start_day_of_week = 1;
            } elseif (strtolower($start_day_of_week) == 'sun') {
                $start_day_of_week = 0;
            }
        }

        //Get day of week
        $day_of_week = date('w', $epoch);
        //Debug::text('Current Day of week: '. $day_of_week, __FILE__, __LINE__, __METHOD__, 10);

        $offset = 0;
        if ($day_of_week < $start_day_of_week) {
            $offset = (7 + ($day_of_week - $start_day_of_week));
        } else {
            $offset = ($day_of_week - $start_day_of_week);
        }

        $retval = mktime(0, 0, 0, date("m", $epoch), (date("j", $epoch) - $offset), date("Y", $epoch));

        //Debug::text(' Epoch: '. TTDate::getDate('DATE+TIME', $epoch) .' Retval: '. TTDate::getDate('DATE+TIME', $retval) .' Start Day of Week: '. $start_day_of_week .' Offset: '. $offset, __FILE__, __LINE__, __METHOD__, 10);
        return $retval;
    }

    public static function getEndWeekEpoch($epoch = null, $start_day_of_week = 0)
    {
        if ($epoch == null or $epoch == '') {
            $epoch = self::getTime();
        }

        $retval = self::getEndDayEpoch((self::getMiddleDayEpoch(self::getBeginWeekEpoch(self::getMiddleDayEpoch($epoch), $start_day_of_week)) + (86400 * 6)));

        //Debug::text(' Epoch: '. TTDate::getDate('DATE+TIME', $epoch) .' Retval: '. TTDate::getDate('DATE+TIME', $retval) .' Start Day of Week: '. $start_day_of_week, __FILE__, __LINE__, __METHOD__, 10);

        return $retval;
    }

    public static function getISODateStamp($epoch)
    {
        $format = 'Ymd';

        return date($format, $epoch);
    }


    //Returns the month of the quarter that the date falls in.
    //Used for government forms that require a break down for each month in the quarter.

    public static function inWindow($epoch, $window_epoch, $window)
    {
        Debug::text(' Epoch: ' . TTDate::getDate('DATE+TIME', $epoch) . ' Window Epoch: ' . TTDate::getDate('DATE+TIME', $window_epoch) . ' Window: ' . $window, __FILE__, __LINE__, __METHOD__, 10);

        if ($epoch >= ($window_epoch - $window)
            and $epoch <= ($window_epoch + $window)
        ) {
            Debug::text(' Within Window', __FILE__, __LINE__, __METHOD__, 10);
            return true;
        }

        Debug::text(' NOT Within Window', __FILE__, __LINE__, __METHOD__, 10);

        return false;
    }

    //Regardless of the quarter, this returns if its the 1st, 2nd or 3rd month in the quarter.
    //Primary used for government forms.

    public static function getTimeOverLapDifference($start_date1, $end_date1, $start_date2, $end_date2)
    {
        $overlap_result = self::getTimeOverlap($start_date1, $end_date1, $start_date2, $end_date2);
        if (is_array($overlap_result)) {
            $retval = ($overlap_result['end_date'] - $overlap_result['start_date']);
            //Debug::text(' Overlap Time Difference: '. $retval, __FILE__, __LINE__, __METHOD__, 10);
            return $retval;
        }

        return false;
    }

    public static function getTimeOverLap($start_date1, $end_date1, $start_date2, $end_date2)
    {
        //Find out if Date1 overlaps with Date2

        //Allow 0 as one of the dates.
        //if ( $start_date1 == '' OR $end_date1 == '' OR $start_date2 == '' OR $end_date2 == '') {
        if (is_numeric($start_date1) == false or is_numeric($end_date1) == false or is_numeric($start_date2) == false or is_numeric($end_date2) == false) {
            return false;
        }

        //Debug::text(' Checking if Start Date: '. TTDate::getDate('DATE+TIME', $start_date1 ) .' End Date: '. TTDate::getDate('DATE+TIME', $end_date1 ), __FILE__, __LINE__, __METHOD__, 10);
        //Debug::text('	  Overlap Start Date: '. TTDate::getDate('DATE+TIME', $start_date2 ) .' End Date: '. TTDate::getDate('DATE+TIME', $end_date2 ), __FILE__, __LINE__, __METHOD__, 10);

        /*
              |-----------------------| <-- Date Pair 1
                1. |-------| <-- Date Pair2
                    2.	 |-------------------------|
        3. |-----------------------|
        4. |------------------------------------------|
        */

        if (($start_date2 >= $start_date1 and $end_date2 <= $end_date1)) { //Case #1
            //Debug::text(' Overlap on Case #1: ', __FILE__, __LINE__, __METHOD__, 10);
            //$retval = ( $end_date2 - $start_date2 );
            $retarr = array('start_date' => $start_date2, 'end_date' => $end_date2, 'scenario' => 'start_after_end_before');
        } elseif (($start_date2 >= $start_date1 and $start_date2 <= $end_date1)) { //Case #2
            //Debug::text(' Overlap on Case #2: ', __FILE__, __LINE__, __METHOD__, 10);
            //$retval = ( $end_date1 - $start_date2 );
            $retarr = array('start_date' => $start_date2, 'end_date' => $end_date1, 'scenario' => 'start_after_end_after');
        } elseif (($end_date2 >= $start_date1 and $end_date2 <= $end_date1)) { //Case #3
            //Debug::text(' Overlap on Case #3: ', __FILE__, __LINE__, __METHOD__, 10);
            //$retval = ( $end_date2 - $start_date1 );
            $retarr = array('start_date' => $start_date1, 'end_date' => $end_date2, 'scenario' => 'start_before_end_before');
        } elseif (($start_date2 <= $start_date1 and $end_date2 >= $end_date1)) { //Case #4
            //Debug::text(' Overlap on Case #4: ', __FILE__, __LINE__, __METHOD__, 10);
            //$retval = ( $end_date1 - $start_date1 );
            $retarr = array('start_date' => $start_date1, 'end_date' => $end_date1, 'scenario' => 'start_before_end_after');
        }

        if (isset($retarr)) {
            //Debug::Text(' Overlap Times: Start: '. TTDate::getDate('DATE+TIME', $retarr['start_date'] ) .' End: '. TTDate::getDate('DATE+TIME', $retarr['end_date'] ) .' Scenario: '. $retarr['scenario'], __FILE__, __LINE__, __METHOD__, 10);
            return $retarr;
        }

        return false;
    }

    public static function isTimeOverLap($start_date1, $end_date1, $start_date2, $end_date2)
    {
        //Find out if Date1 overlaps with Date2

        //Allow 0 as one of the dates.
        //if ( $start_date1 == '' OR $end_date1 == '' OR $start_date2 == '' OR $end_date2 == '') {
        if (is_numeric($start_date1) == false or is_numeric($end_date1) == false or is_numeric($start_date2) == false or is_numeric($end_date2) == false) {
            return false;
        }

        //Debug::text(' Checking if Start Date: '. TTDate::getDate('DATE+TIME', $start_date1 ) .' End Date: '. TTDate::getDate('DATE+TIME', $end_date1 ), __FILE__, __LINE__, __METHOD__, 10);
        //Debug::text('	  Overlap Start Date: '. TTDate::getDate('DATE+TIME', $start_date2 ) .' End Date: '. TTDate::getDate('DATE+TIME', $end_date2 ), __FILE__, __LINE__, __METHOD__, 10);

        /*
              |-----------------------|
                1. |-------|
                    2.	 |-------------------------|
        3. |-----------------------|
        4. |------------------------------------------|
        5.	  |-----------------------| (match exactly)

        */
        if (($start_date2 >= $start_date1 and $end_date2 <= $end_date1)) { //Case #1
            //Debug::text(' Overlap on Case #1: ', __FILE__, __LINE__, __METHOD__, 10);

            return true;
        }

        //Allow case where there are several shifts in a day, ie:
        // 8:00AM to 1:00PM, 1:00PM to 5:00PM, where the end and start times match exactly.
        //if	( ($start_date2 >= $start_date1 AND $start_date2 <= $end_date1) ) { //Case #2
        if (($start_date2 >= $start_date1 and $start_date2 < $end_date1)) { //Case #2
            //Debug::text(' Overlap on Case #2: ', __FILE__, __LINE__, __METHOD__, 10);

            return true;
        }

        //Allow case where there are several shifts in a day, ie:
        // 8:00AM to 1:00PM, 1:00PM to 5:00PM, where the end and start times match exactly.
        //if	( ($end_date2 >= $start_date1 AND $end_date2 <= $end_date1) ) { //Case #3
        if (($end_date2 > $start_date1 and $end_date2 <= $end_date1)) { //Case #3
            //Debug::text(' Overlap on Case #3: ', __FILE__, __LINE__, __METHOD__, 10);

            return true;
        }

        if (($start_date2 <= $start_date1 and $end_date2 >= $end_date1)) { //Case #4
            //Debug::text(' Overlap on Case #4: ', __FILE__, __LINE__, __METHOD__, 10);

            return true;
        }

        if (($start_date2 == $start_date1 and $end_date2 == $end_date1)) { //Case #5
            //Debug::text(' Overlap on Case #5: ', __FILE__, __LINE__, __METHOD__, 10);

            return true;
        }

        return false;
    }

    public static function calculateTimeOnEachDayBetweenRange($start_epoch, $end_epoch)
    {
        $retval = array();
        if (TTDate::doesRangeSpanMidnight($start_epoch, $end_epoch) == true) {
            $total_before_first_midnight = ((TTDate::getEndDayEpoch($start_epoch) + 1) - $start_epoch);
            if ($total_before_first_midnight > 0) {
                $retval[TTDate::getBeginDayEpoch($start_epoch)] = $total_before_first_midnight;
            }

            $loop_start = (TTDate::getEndDayEpoch($start_epoch) + 1);
            $loop_end = TTDate::getBeginDayEpoch($end_epoch);
            for ($x = $loop_start; $x < $loop_end; $x += 86400) {
                $retval[TTDate::getBeginDayEpoch($x)] = 86400;
            }

            $total_after_last_midnight = ($end_epoch - TTDate::getBeginDayEpoch($end_epoch));
            if ($total_after_last_midnight > 0) {
                $retval[TTDate::getBeginDayEpoch($end_epoch)] = $total_after_last_midnight;
            }
        } else {
            $retval = array(TTDate::getBeginDayEpoch($start_epoch) => ($end_epoch - $start_epoch));
        }

        return $retval;
    }

    public static function doesRangeSpanMidnight($start_epoch, $end_epoch, $match_midnight = false)
    {
        if ($start_epoch > $end_epoch) { //If start_epoch is after end_epoch, just swap the two values.
            $tmp = $start_epoch;
            $start_epoch = $end_epoch;
            $end_epoch = $tmp;
        }

        //Debug::text('Start Epoch: '. TTDate::getDate('DATE+TIME', $start_epoch) .'  End Epoch: '. TTDate::getDate('DATE+TIME', $end_epoch), __FILE__, __LINE__, __METHOD__, 10);
        if (abs(self::getDayOfYear($end_epoch) - self::getDayOfYear($start_epoch)) > 1) { //More than one day is between the epochs.
            return true;
        } else {
            $end_epoch_midnight = TTDate::getBeginDayEpoch($end_epoch);
            if ($start_epoch < $end_epoch_midnight and $end_epoch > $end_epoch_midnight) { //Epochs do span midnight.
                return true;
            } elseif ($match_midnight == true and (self::isMidnight($start_epoch) == true or self::isMidnight($end_epoch) == true)) {
                return true;
            }
        }

        return false;
    }

    public static function getDayOfYear($epoch)
    {
        return date('z', $epoch);
    }

    public static function isMidnight($epoch)
    {
        if (TTDate::getHour($epoch) == 0 and TTDate::getMinute($epoch) == 0 and TTDate::getSecond($epoch) == 0) {
            return true;
        }

        return false;
    }

    public static function getHour($epoch = null)
    {
        if ($epoch == null or $epoch == '') {
            $epoch = self::getTime();
        }

        return date('G', $epoch);
    }

    public static function getMinute($epoch = null)
    {
        if ($epoch == null or $epoch == '') {
            $epoch = self::getTime();
        }

        return date('i', $epoch);
    }

    //This could also be called: getWeekOfYear

    public static function getSecond($epoch = null)
    {
        if ($epoch == null or $epoch == '') {
            $epoch = self::getTime();
        }

        return date('s', $epoch);
    }

    /**
     * break up a timespan into array of days between times and on midnight
     * if no filter break days on midnight only
     *
     * @param time $start_time_stamp
     * @param time $end_time_stamp
     * @param time $filter_start_time_stamp
     * @param time $filter_end_time_stamp
     * @return array
     */
    public static function splitDateRangeAtMidnight($start_time_stamp, $end_time_stamp, $filter_start_time_stamp = false, $filter_end_time_stamp = false)
    {
        $return_arr = array();
        $start_timestamp_at_midnight = (TTDate::getEndDayEpoch($start_time_stamp) + 1);

        /**
         * set up first pair
         */
        $date_floor = $date_ceiling = $start_time_stamp;

        if ($filter_start_time_stamp != false and $filter_end_time_stamp != false) {
            $date_ceiling = TTDate::getNextDateFromArray($date_floor, array($start_timestamp_at_midnight, TTDate::getTimeLockedDate($filter_start_time_stamp, $start_time_stamp), TTDate::getTimeLockedDate($filter_end_time_stamp, $start_time_stamp)));
        } else {
            $date_ceiling = TTDate::getNextDateFromArray($date_floor, array($start_timestamp_at_midnight, $end_time_stamp));
        }

        if ($date_ceiling >= $end_time_stamp) {
            $return_arr[] = array('start_time_stamp' => $start_time_stamp, 'end_time_stamp' => $end_time_stamp);
            return $return_arr;
        }

        $c = 0;
        $max_loops = ((($end_time_stamp - $start_time_stamp) / 86400) * 6);
        while ($date_ceiling <= $end_time_stamp and $c <= $max_loops) {
            $return_arr[] = array('start_time_stamp' => $date_floor, 'end_time_stamp' => $date_ceiling);
            $date_floor = $date_ceiling;

            /**
             * There are 3 valid scenarios for the date ceiling:
             * 1. next filter start time
             * 2. next filter end time
             * 3. next midnight
             *
             * ensure each is greater than $date_floor, then choose the lowest of the qualifying values.
             */
            if ($filter_start_time_stamp != false and $filter_end_time_stamp != false) {
                $next_midnight = TTDate::getTimeLockedDate($start_timestamp_at_midnight, (TTDate::getMiddleDayEpoch($date_floor) + 86400));
                $next_filter_start = TTDate::getTimeLockedDate($filter_start_time_stamp, $date_floor);
                $next_filter_end = TTDate::getTimeLockedDate($filter_end_time_stamp, $date_floor);

                $date_ceiling = TTDate::getNextDateFromArray($date_floor, array($next_midnight, $next_filter_start, $next_filter_end));
            } else {
                $date_ceiling = TTDate::getTimeLockedDate($start_timestamp_at_midnight, (TTDate::getMiddleDayEpoch($date_floor) + 86400));
            }

            /**
             * Final case.
             **/
            if ($date_ceiling >= $end_time_stamp) {
                $date_ceiling = $end_time_stamp;
                $return_arr[] = array('start_time_stamp' => $date_floor, 'end_time_stamp' => $date_ceiling);
                unset($end_time_stamp, $start_time_stamp, $filter_end_time_stamp, $filter_start_time_stamp);
                return $return_arr;
            }

            $c++;
        }

        Debug::Text("ERROR: infinite loop detected. This should never happen", __FILE__, __LINE__, __METHOD__, 10);
        return $return_arr;
    }

    /**
     * returns next date from array that is after the floor date
     * @param int $floor
     * @param array $dates
     * @return mixed
     */
    public static function getNextDateFromArray($floor, $dates)
    {
        $tmp_end_times = array();
        foreach ($dates as $date) {
            if ($date > $floor) {
                $tmp_end_times[] = $date;
            }
        }

        if (count($tmp_end_times) > 0) {
            return min($tmp_end_times);
        } else {
            return $floor;
        }
    }

    public static function getTimeLockedDate($time_epoch, $date_epoch)
    {
        //This causes unit tests to fail.
        //if ( $time_epoch == '' OR $date_epoch == '' ) {
        //return FALSE;
        //}

        $time_arr = getdate($time_epoch);
        $date_arr = getdate($date_epoch);

        $epoch = mktime($time_arr['hours'],
            $time_arr['minutes'],
            $time_arr['seconds'],
            $date_arr['mon'],
            $date_arr['mday'],
            $date_arr['year']
        );
        unset($time_arr, $date_arr);

        return $epoch;
    }

    public static function isConsecutiveDays($date_array)
    {
        if (is_array($date_array) and count($date_array) > 1) {
            $retval = array();
            sort($date_array);

            $retval = false;

            $prev_date = false;
            foreach ($date_array as $date) {
                if ($prev_date != false) {
                    $date_diff = (TTDate::getMiddleDayEpoch(TTDate::strtotime($date)) - TTDate::getMiddleDayEpoch(TTDate::strtotime($prev_date)));
                    if ($date_diff <= 86400) {
                        $retval = true;
                    } else {
                        $retval = false;
                        break;
                    }
                }

                $prev_date = $date;
            }

            Debug::Text('Days are consecutive: ' . count($date_array) . ' Retval: ' . (int)$retval, __FILE__, __LINE__, __METHOD__, 10);
            return $retval;
        }

        return false;
    }

    public static function strtotime($str)
    {
        if (is_numeric($str)) {
            return (int)$str;
        }

        //Debug::text(' Original String: '. $str, __FILE__, __LINE__, __METHOD__, 10);
        $retval = strtotime($str);
        //Debug::text(' After strotime String: '. $retval, __FILE__, __LINE__, __METHOD__, 10);

        if ($retval == -1 or $retval === false) {
            return $str;
        }

        return (int)$retval;
    }

    public static function getBirthDateAtAge($birth_date, $age)
    {
        if ($age > 0) {
            $age = '+' . $age;
        }

        return strtotime($age . ' years', $birth_date);
    }

    public static function getEasterDays($year)
    {
        #First calculate the date of easter using Delambre's algorithm.
        $a = ($year % 19);
        $b = floor(($year / 100));
        $c = ($year % 100);
        $d = floor(($b / 4));
        $e = ($b % 4);
        $f = floor((($b + 8) / 25));
        $g = floor((($b - $f + 1) / 3));
        $h = ((19 * $a + $b - $d - $g + 15) % 30);
        $i = floor(($c / 4));
        $k = ($c % 4);
        $l = ((32 + 2 * $e + 2 * $i - $h - $k) % 7);
        $m = floor((($a + 11 * $h + 22 * $l) / 451));
        $n = ($h + $l - 7 * $m + 114);
        $month = floor(($n / 31));
        $day = ($n % 31 + 1);

        #Return the difference between the JulianDayCount for easter and March 21'st
        #of the same year, in order to duplicate the functionality of the easter_days function
        //return GregorianToJD($month, $day, $year) - GregorianToJD(3, 21, $year);
        return round(TTDate::getDays(mktime(0, 0, 0, $month, $day, $year) - mktime(0, 0, 0, 3, 21, $year)));
    }

    public static function getDays($seconds)
    {
        return bcdiv($seconds, 86400);
    }

    public static function getHumanTimeSince($epoch)
    {
        if (time() >= $epoch) {
            $epoch_since = (time() - $epoch);
        } else {
            $epoch_since = ($epoch - time());
        }

        //Debug::text(' Epoch Since: '. $epoch_since, __FILE__, __LINE__, __METHOD__, 10);
        switch (true) {
            case ($epoch_since > (31536000 * 2)):
                //Years
                $num = ((((($epoch_since / 60) / 60) / 24) / 30) / 12);
                $suffix = TTi18n::getText('yr');
                break;
            case ($epoch_since > (((3600 * 24) * 60) * 2)):
                //Months the above number should be 2 months, so we don't get 0 months showing up.
                $num = ((((($epoch_since / 60) / 60) / 24) / 30));
                $suffix = TTi18n::getText('mth');
                break;
            case ($epoch_since > (604800 * 2)):
                //Weeks
                $num = (((($epoch_since / 60) / 60) / 24) / 7);
                $suffix = TTi18n::getText('wk');
                break;
            case ($epoch_since > (86400 * 2)):
                //Days
                $num = ((($epoch_since / 60) / 60) / 24);
                $suffix = TTi18n::getText('day');
                break;
            case ($epoch_since > (3600 * 2)):
                //Hours
                $num = (($epoch_since / 60) / 60);
                $suffix = TTi18n::getText('hr');
                break;
            case ($epoch_since > (60 * 2)):
                //Mins
                $num = ($epoch_since / 60);
                $suffix = TTi18n::getText('min');
                break;
            default:
                //Secs
                $num = $epoch_since;
                $suffix = TTi18n::getText('sec');
                break;
        }

        if ($num > 1.1) { //1.01 Days gets rounded to 1.0 and should not have "s" on the end.
            $suffix .= TTi18n::getText('s');
        }

        //Debug::text(' Num: '. $num .' Suffix: '. $suffix, __FILE__, __LINE__, __METHOD__, 10);
        return sprintf("%0.01f", $num) . " " . $suffix;
    }

    public static function isBindTimeStamp($str)
    {
        if (strpos($str, '-') === false) {
            return false;
        }

        return true;
    }

    //Returns an array of dates within the range.

    public static function getTimePeriodOptions($include_pay_period = true)
    {
        $retarr = array(
            '-1000-custom_date' => TTi18n::getText('Custom Dates'), // Select Start/End dates from calendar.
            //'-1005-custom_time' => TTi18n::getText('Custom Date/Time'), // Select Start/End dates & time from calendar.

            //'-1000-custom_relative_date' => TTi18n::getText('Custom Relative Dates'), //Select a Start and End relative date (from this list)
            '-1010-today' => TTi18n::getText('Today'),
            '-1020-yesterday' => TTi18n::getText('Yesterday'),
            '-1030-last_24_hours' => TTi18n::getText('Last 24 Hours'),
            '-1032-last_48_hours' => TTi18n::getText('Last 48 Hours'),
            '-1034-last_72_hours' => TTi18n::getText('Last 72 Hours'),

            '-1100-this_week' => TTi18n::getText('This Week'),
            '-1110-last_week' => TTi18n::getText('Last Week'),
            '-1112-last_2_weeks' => TTi18n::getText('Last 2 Weeks'),
            '-1120-last_7_days' => TTi18n::getText('Last 7 Days'),
            '-1122-last_14_days' => TTi18n::getText('Last 14 Days'),

            '-1300-this_month' => TTi18n::getText('This Month'),
            '-1310-last_month' => TTi18n::getText('Last Month'),
            '-1312-last_2_months' => TTi18n::getText('Last 2 Months'),
            '-1320-last_30_days' => TTi18n::getText('Last 30 Days'),
            '-1320-last_45_days' => TTi18n::getText('Last 45 Days'),
            '-1322-last_60_days' => TTi18n::getText('Last 60 Days'),

            '-1400-this_quarter' => TTi18n::getText('This Quarter'),
            '-1410-last_quarter' => TTi18n::getText('Last Quarter'),
            '-1420-last_90_days' => TTi18n::getText('Last 90 Days'),
            '-1430-this_year_1st_quarter' => TTi18n::getText('1st Quarter (This Year)'),
            '-1440-this_year_2nd_quarter' => TTi18n::getText('2nd Quarter (This Year)'),
            '-1450-this_year_3rd_quarter' => TTi18n::getText('3rd Quarter (This Year)'),
            '-1460-this_year_4th_quarter' => TTi18n::getText('4th Quarter (This Year)'),
            '-1470-last_year_1st_quarter' => TTi18n::getText('1st Quarter (Last Year)'),
            '-1480-last_year_2nd_quarter' => TTi18n::getText('2nd Quarter (Last Year)'),
            '-1490-last_year_3rd_quarter' => TTi18n::getText('3rd Quarter (Last Year)'),
            '-1500-last_year_4th_quarter' => TTi18n::getText('4th Quarter (Last Year)'),

            '-1600-last_3_months' => TTi18n::getText('Last 3 Months'),
            '-1610-last_6_months' => TTi18n::getText('Last 6 Months'),
            '-1620-last_9_months' => TTi18n::getText('Last 9 Months'),
            '-1630-last_12_months' => TTi18n::getText('Last 12 Months'),
            '-1640-last_18_months' => TTi18n::getText('Last 18 Months'),
            '-1650-last_24_months' => TTi18n::getText('Last 24 Months'),

            '-1700-this_year' => TTi18n::getText('This Year'), //Used to be 'This Year (Year-To-Date)', but its actually the entire year which was confusing for some users. They can use 'This Year (Up To Today)' instead.
            '-1705-this_year_this_pay_period' => TTi18n::getText('This Year (Up To This Pay Period)'),
            '-1710-this_year_last_pay_period' => TTi18n::getText('This Year (Up To Last Pay Period)'),
            '-1715-this_year_yesterday' => TTi18n::getText('This Year (Up To Yesterday)'),
            '-1716-this_year_today' => TTi18n::getText('This Year (Up To Today)'),
            '-1717-this_year_ytd' => TTi18n::getText('This Year (Year-To-Date)'), //Could be "This Year (Up to Tomorrow)"? This does not include the current day.
            //'-1718-this_year_tomorrow' => TTi18n::getText('This Year (Up To Tomorrow)'),
            '-1720-this_year_last_week' => TTi18n::getText('This Year (Up To Last Week)'),
            '-1725-this_year_this_week' => TTi18n::getText('This Year (Up To This Week)'),
            '-1730-this_year_last_month' => TTi18n::getText('This Year (Up To Last Month)'),
            '-1735-this_year_this_month' => TTi18n::getText('This Year (Up To This Month)'),
            '-1740-this_year_30_days' => TTi18n::getText('This Year (Up To 30 Days Ago)'),
            '-1745-this_year_45_days' => TTi18n::getText('This Year (Up To 45 Days Ago)'),
            '-1750-this_year_60_days' => TTi18n::getText('This Year (Up To 60 Days Ago)'),
            '-1755-this_year_90_days' => TTi18n::getText('This Year (Up To 90 Days Ago)'),
            '-1765-this_year_last_quarter' => TTi18n::getText('This Year (Up To Last Quarter)'),
            '-1770-this_year_this_quarter' => TTi18n::getText('This Year (Up To This Quarter)'),

            '-1780-last_year' => TTi18n::getText('Last Year'),
            '-1785-last_2_years' => TTi18n::getText('Last Two Years'),
            '-1790-last_3_years' => TTi18n::getText('Last Three Years'),
            '-1795-last_5_years' => TTi18n::getText('Last Five Years'),

            '-1800-to_yesterday' => TTi18n::getText('Up To Yesterday'),
            '-1802-to_today' => TTi18n::getText('Up To Today'),
            '-1810-to_last_week' => TTi18n::getText('Up To Last Week'),
            '-1812-to_this_week' => TTi18n::getText('Up To This Week'),
            '-1814-to_7_days' => TTi18n::getText('Up To 7 Days Ago'),
            '-1816-to_14_days' => TTi18n::getText('Up To 14 Days Ago'),
            '-1820-to_last_pay_period' => TTi18n::getText('Up To Last Pay Period'),
            '-1822-to_this_pay_period' => TTi18n::getText('Up To This Pay Period'),
            '-1830-to_last_month' => TTi18n::getText('Up To Last Month'),
            '-1832-to_this_month' => TTi18n::getText('Up To This Month'),
            '-1840-to_30_days' => TTi18n::getText('Up To 30 Days Ago'),
            '-1842-to_45_days' => TTi18n::getText('Up To 45 Days Ago'),
            '-1844-to_60_days' => TTi18n::getText('Up To 60 Days Ago'),
            '-1850-to_last_quarter' => TTi18n::getText('Up To Last Quarter'),
            '-1852-to_this_quarter' => TTi18n::getText('Up To This Quarter'),
            '-1854-to_90_days' => TTi18n::getText('Up To 90 Days Ago'),
            '-1860-to_last_year' => TTi18n::getText('Up To Last Year'),
            '-1862-to_this_year' => TTi18n::getText('Up To This Year'),

            '-1900-tomorrow' => TTi18n::getText('Tomorrow'),
            '-1902-next_24_hours' => TTi18n::getText('Next 24 Hours'),
            '-1904-next_48_hours' => TTi18n::getText('Next 48 Hours'),
            '-1906-next_72_hours' => TTi18n::getText('Next 72 Hours'),
            '-1910-next_week' => TTi18n::getText('Next Week'),
            '-1912-next_2_weeks' => TTi18n::getText('Next 2 Weeks'),
            '-1914-next_7_days' => TTi18n::getText('Next 7 Days'),
            '-1916-next_14_days' => TTi18n::getText('Next 14 Days'),
            '-1930-next_month' => TTi18n::getText('Next Month'),
            '-1932-next_2_months' => TTi18n::getText('Next 2 Months'),
            '-1940-next_30_days' => TTi18n::getText('Next 30 Days'),
            '-1942-next_45_days' => TTi18n::getText('Next 45 Days'),
            '-1944-next_60_days' => TTi18n::getText('Next 60 Days'),
            '-1950-next_quarter' => TTi18n::getText('Next Quarter'),
            '-1954-next_90_days' => TTi18n::getText('Next 90 Days'),
            '-1960-next_3_months' => TTi18n::getText('Next 3 Months'),
            '-1962-next_6_months' => TTi18n::getText('Next 6 Months'),
            '-1964-next_9_months' => TTi18n::getText('Next 9 Months'),
            '-1966-next_12_months' => TTi18n::getText('Next 12 Months'),
            '-1968-next_18_months' => TTi18n::getText('Next 18 Months'),
            '-1970-next_24_months' => TTi18n::getText('Next 24 Months'),
            '-1980-next_year' => TTi18n::getText('Next Year'),
            '-1982-next_2_years' => TTi18n::getText('Next Two Years'),
            '-1984-next_3_years' => TTi18n::getText('Next Three Years'),
            '-1986-next_5_years' => TTi18n::getText('Next Five Years'),

            '-1990-all_years' => TTi18n::getText('All Years'),
        );

        if ($include_pay_period == true) {
            $pay_period_arr = array(
                '-1008-custom_pay_period' => TTi18n::getText('Custom Pay Periods'), //Select pay periods individually
                '-1200-this_pay_period' => TTi18n::getText('This Pay Period'), //Select one or more pay period schedules
                '-1210-last_pay_period' => TTi18n::getText('Last Pay Period'), //Select one or more pay period schedules
                '-1212-no_pay_period' => TTi18n::getText('No Pay Period'), //Data assigned to no pay periods or pay_period_id = 0
            );

            $retarr = array_merge($retarr, $pay_period_arr);
            ksort($retarr);
        }

        return $retarr;
    }

    //Loop from filter start date to end date. Creating an array entry for each day.

    public static function getTimePeriodDates($time_period, $epoch = null, $user_obj = null, $params = null)
    {
        $time_period = Misc::trimSortPrefix($time_period);

        if ($epoch == null or $epoch == '' or !is_numeric($epoch)) {
            $epoch = self::getTime();
        }

        $start_week_day = 0;
        if (is_object($user_obj)) {
            $user_prefs = $user_obj->getUserPreferenceObject();
            if (is_object($user_prefs)) {
                $start_week_day = $user_prefs->getStartWeekDay();
            }
        }

        switch ($time_period) {
            case 'custom_date':
                //Params must pass start_date/end_date
                if (isset($params['start_date'])) {
                    $start_date = TTDate::getBeginDayEpoch($params['start_date']);
                }
                if (isset($params['end_date'])) {
                    $end_date = TTDate::getEndDayEpoch($params['end_date']);
                }
                break;
            case 'custom_time':
                //Params must pass start_date/end_date
                if (isset($params['start_date'])) {
                    $start_date = $params['start_date'];
                }
                if (isset($params['end_date'])) {
                    $end_date = $params['end_date'];
                }
                break;
            case 'custom_pay_period':
                //Params must pass pay_period_ids
                if (isset($params['pay_period_id'])) {
                    $pay_period_ids = (array)$params['pay_period_id'];
                }
                break;
            case 'today':
                $start_date = TTDate::getBeginDayEpoch($epoch);
                $end_date = TTDate::getEndDayEpoch($epoch);
                break;
            case 'yesterday':
                $start_date = TTDate::getBeginDayEpoch((TTDate::getMiddleDayEpoch($epoch) - 86400));
                $end_date = TTDate::getEndDayEpoch((TTDate::getMiddleDayEpoch($epoch) - 86400));
                break;
            case 'last_24_hours':
                $start_date = ($epoch - 86400);
                $end_date = $epoch;
                break;
            case 'last_48_hours':
                $start_date = ($epoch - (86400 * 2));
                $end_date = $epoch;
                break;
            case 'last_72_hours':
                $start_date = ($epoch - (86400 * 3));
                $end_date = $epoch;
                break;
            case 'this_week':
                $start_date = TTDate::getBeginWeekEpoch($epoch, $start_week_day);
                $end_date = TTDate::getEndWeekEpoch($epoch, $start_week_day);
                break;
            case 'last_week':
                $start_date = TTDate::getBeginWeekEpoch((TTDate::getMiddleDayEpoch($epoch) - (86400 * 7)), $start_week_day);
                $end_date = TTDate::getEndWeekEpoch((TTDate::getMiddleDayEpoch($epoch) - (86400 * 7)), $start_week_day);
                break;
            case 'last_2_weeks':
                $start_date = TTDate::getBeginWeekEpoch((TTDate::getMiddleDayEpoch($epoch) - (86400 * 14)), $start_week_day);
                $end_date = TTDate::getEndWeekEpoch((TTDate::getMiddleDayEpoch($epoch) - (86400 * 7)), $start_week_day);
                break;
            case 'last_7_days':
                $start_date = TTDate::getBeginDayEpoch((TTDate::getMiddleDayEpoch($epoch) - (86400 * 7)));
                $end_date = TTDate::getEndDayEpoch((TTDate::getMiddleDayEpoch($epoch) - 86400));
                break;
            case 'last_14_days':
                $start_date = TTDate::getBeginDayEpoch((TTDate::getMiddleDayEpoch($epoch) - (86400 * 14)));
                $end_date = TTDate::getEndDayEpoch((TTDate::getMiddleDayEpoch($epoch) - 86400));
                break;

            //Params must be passed if more than one pay period schedule exists.
            case 'no_pay_period':
            case 'this_pay_period':
            case 'last_pay_period':
                Debug::text('Time Period for Pay Period Schedule selected...', __FILE__, __LINE__, __METHOD__, 10);
                //Make sure user_obj is set.
                if (!is_object($user_obj)) {
                    Debug::text('User Object was not passsed...', __FILE__, __LINE__, __METHOD__, 10);
                    break;
                }

                if (!isset($params['pay_period_schedule_id'])) {
                    $params['pay_period_schedule_id'] = null;
                }

                $pay_period_ids = array();

                //Since we allow multiple pay_period schedules to be selected, we have to return pay_period_ids, not start/end dates.
                if ($time_period == 'this_pay_period') {
                    Debug::text('this_pay_period', __FILE__, __LINE__, __METHOD__, 10);
                    $pplf = TTnew('PayPeriodListFactory');
                    $pplf->getThisPayPeriodByCompanyIdAndPayPeriodScheduleIdAndDate($user_obj->getCompany(), $params['pay_period_schedule_id'], time());
                    if ($pplf->getRecordCount() > 0) {
                        foreach ($pplf as $pp_obj) {
                            $pay_period_ids[] = $pp_obj->getId();
                        }
                    }
                } elseif ($time_period == 'last_pay_period') {
                    Debug::text('last_pay_period', __FILE__, __LINE__, __METHOD__, 10);
                    $pplf = TTnew('PayPeriodListFactory');
                    $pplf->getLastPayPeriodByCompanyIdAndPayPeriodScheduleIdAndDate($user_obj->getCompany(), $params['pay_period_schedule_id'], time());
                    if ($pplf->getRecordCount() > 0) {
                        foreach ($pplf as $pp_obj) {
                            $pay_period_ids[] = $pp_obj->getId();
                        }
                    }
                } else {
                    Debug::text('no_pay_period', __FILE__, __LINE__, __METHOD__, 10);
                }

                Debug::Arr($pay_period_ids, 'Pay Period IDs: ', __FILE__, __LINE__, __METHOD__, 10);
                if (count($pay_period_ids) == 0) {
                    unset($pay_period_ids);
                }
                break;
            case 'this_month':
                $start_date = TTDate::getBeginMonthEpoch($epoch);
                $end_date = TTDate::getEndMonthEpoch($epoch);
                break;
            case 'last_month':
                $start_date = TTDate::getBeginMonthEpoch((TTDate::getBeginMonthEpoch($epoch) - 86400));
                $end_date = TTDate::getEndMonthEpoch((TTDate::getBeginMonthEpoch($epoch) - 86400));
                break;
            case 'last_2_months':
                $start_date = TTDate::getBeginMonthEpoch((TTDate::getBeginMonthEpoch($epoch) - (86400 * 32)));
                $end_date = TTDate::getEndMonthEpoch((TTDate::getBeginMonthEpoch($epoch) - 86400));
                break;
            case 'last_30_days':
                $start_date = TTDate::getBeginDayEpoch((TTDate::getMiddleDayEpoch($epoch) - (86400 * 30)));
                $end_date = TTDate::getEndDayEpoch((TTDate::getMiddleDayEpoch($epoch) - 86400));
                break;
            case 'last_45_days':
                $start_date = TTDate::getBeginDayEpoch((TTDate::getMiddleDayEpoch($epoch) - (86400 * 45)));
                $end_date = TTDate::getEndDayEpoch((TTDate::getMiddleDayEpoch($epoch) - 86400));
                break;
            case 'last_60_days':
                $start_date = TTDate::getBeginDayEpoch((TTDate::getMiddleDayEpoch($epoch) - (86400 * 60)));
                $end_date = TTDate::getEndDayEpoch((TTDate::getMiddleDayEpoch($epoch) - 86400));
                break;
            case 'this_quarter':
                $quarter = TTDate::getYearQuarter($epoch);
                $quarter_dates = TTDate::getYearQuarters($epoch, $quarter);
                //Debug::Arr($quarter_dates, 'Quarter Dates: Quarter: '. $quarter, __FILE__, __LINE__, __METHOD__, 10);

                $start_date = $quarter_dates['start'];
                $end_date = $quarter_dates['end'];
                break;
            case 'last_quarter':
                $quarter = (TTDate::getYearQuarter($epoch) - 1);
                if ($quarter == 0) {
                    $quarter = 4;
                    $epoch = (TTDate::getBeginYearEpoch() - 86400); //Need to jump back into the previous year.
                }
                $quarter_dates = TTDate::getYearQuarters($epoch, $quarter);

                $start_date = $quarter_dates['start'];
                $end_date = $quarter_dates['end'];
                break;
            case 'last_90_days':
                $start_date = TTDate::getBeginDayEpoch((TTDate::getMiddleDayEpoch($epoch) - (86400 * 90)));
                $end_date = TTDate::getEndDayEpoch((TTDate::getMiddleDayEpoch($epoch) - 86400));
                break;
            case 'this_year_1st_quarter':
                $quarter = 1;
                $quarter_dates = TTDate::getYearQuarters($epoch, $quarter);

                $start_date = $quarter_dates['start'];
                $end_date = $quarter_dates['end'];
                break;
            case 'this_year_2nd_quarter':
                $quarter = 2;
                $quarter_dates = TTDate::getYearQuarters($epoch, $quarter);

                $start_date = $quarter_dates['start'];
                $end_date = $quarter_dates['end'];
                break;
            case 'this_year_3rd_quarter':
                $quarter = 3;
                $quarter_dates = TTDate::getYearQuarters($epoch, $quarter);

                $start_date = $quarter_dates['start'];
                $end_date = $quarter_dates['end'];
                break;
            case 'this_year_4th_quarter':
                $quarter = 4;
                $quarter_dates = TTDate::getYearQuarters($epoch, $quarter);

                $start_date = $quarter_dates['start'];
                $end_date = $quarter_dates['end'];
                break;
            case 'last_year_1st_quarter':
                $quarter = 1;
                $quarter_dates = TTDate::getYearQuarters((TTDate::getBeginYearEpoch($epoch) - 86400), $quarter);

                $start_date = $quarter_dates['start'];
                $end_date = $quarter_dates['end'];
                break;
            case 'last_year_2nd_quarter':
                $quarter = 2;
                $quarter_dates = TTDate::getYearQuarters((TTDate::getBeginYearEpoch($epoch) - 86400), $quarter);

                $start_date = $quarter_dates['start'];
                $end_date = $quarter_dates['end'];
                break;
            case 'last_year_3rd_quarter':
                $quarter = 3;
                $quarter_dates = TTDate::getYearQuarters((TTDate::getBeginYearEpoch($epoch) - 86400), $quarter);

                $start_date = $quarter_dates['start'];
                $end_date = $quarter_dates['end'];
                break;
            case 'last_year_4th_quarter':
                $quarter = 4;
                $quarter_dates = TTDate::getYearQuarters((TTDate::getBeginYearEpoch($epoch) - 86400), $quarter);

                $start_date = $quarter_dates['start'];
                $end_date = $quarter_dates['end'];
                break;
            case 'last_3_months':
                $end_date = TTDate::getEndDayEpoch((TTDate::getMiddleDayEpoch($epoch) - 86400));
                $start_date = mktime(0, 0, 0, (TTDate::getMonth($end_date) - 3), TTDate::getDayOfMonth($end_date), TTDate::getYear($end_date));
                break;
            case 'last_6_months':
                $end_date = TTDate::getEndDayEpoch((TTDate::getMiddleDayEpoch($epoch) - 86400));
                $start_date = mktime(0, 0, 0, (TTDate::getMonth($end_date) - 6), TTDate::getDayOfMonth($end_date), TTDate::getYear($end_date));
                break;
            case 'last_9_months':
                $end_date = TTDate::getEndDayEpoch((TTDate::getMiddleDayEpoch($epoch) - 86400));
                $start_date = mktime(0, 0, 0, (TTDate::getMonth($end_date) - 9), TTDate::getDayOfMonth($end_date), TTDate::getYear($end_date));
                break;
            case 'last_12_months':
                $end_date = TTDate::getEndDayEpoch((TTDate::getMiddleDayEpoch($epoch) - 86400));
                $start_date = mktime(0, 0, 0, TTDate::getMonth($end_date), TTDate::getDayOfMonth($end_date), (TTDate::getYear($end_date) - 1));
                break;
            case 'last_18_months':
                $end_date = TTDate::getEndDayEpoch((TTDate::getMiddleDayEpoch($epoch) - 86400));
                $start_date = mktime(0, 0, 0, (TTDate::getMonth($end_date) - 18), TTDate::getDayOfMonth($end_date), TTDate::getYear($end_date));
                break;
            case 'last_24_months':
                $end_date = TTDate::getEndDayEpoch((TTDate::getMiddleDayEpoch($epoch) - 86400));
                $start_date = mktime(0, 0, 0, (TTDate::getMonth($end_date) - 24), TTDate::getDayOfMonth($end_date), TTDate::getYear($end_date));
                break;
            case 'this_year':
                $start_date = TTDate::getBeginYearEpoch($epoch);
                $end_date = TTDate::getEndYearEpoch($epoch);
                break;

            case 'this_year_this_pay_period':
            case 'this_year_last_pay_period':
                $start_date = TTDate::getBeginYearEpoch($epoch);

                //Make sure user_obj is set.
                if (!is_object($user_obj)) {
                    Debug::text('User Object was not passsed...', __FILE__, __LINE__, __METHOD__, 10);
                    break;
                }

                if (!isset($params['pay_period_schedule_id'])) {
                    $params['pay_period_schedule_id'] = null;
                }

                $end_date = false;
                //Since we allow multiple pay_period schedules to be selected, we have to return pay_period_ids, not start/end dates.
                if ($time_period == 'this_year_this_pay_period') {
                    $pplf = TTnew('PayPeriodListFactory');
                    $pplf->getThisPayPeriodByCompanyIdAndPayPeriodScheduleIdAndDate($user_obj->getCompany(), $params['pay_period_schedule_id'], time());
                    if ($pplf->getRecordCount() > 0) {
                        foreach ($pplf as $pp_obj) {
                            if ($end_date == false or $pp_obj->getStartDate() < $end_date) {
                                $end_date = $pp_obj->getStartDate();
                            }
                        }
                    }
                } elseif ($time_period == 'this_year_last_pay_period') {
                    $pplf = TTnew('PayPeriodListFactory');
                    $pplf->getLastPayPeriodByCompanyIdAndPayPeriodScheduleIdAndDate($user_obj->getCompany(), $params['pay_period_schedule_id'], time());
                    if ($pplf->getRecordCount() > 0) {
                        foreach ($pplf as $pp_obj) {
                            if ($end_date == false or $pp_obj->getStartDate() < $end_date) {
                                $end_date = $pp_obj->getStartDate();
                            }
                        }
                    }
                }
                $end_date--;
                break;
            case 'this_year_yesterday':
                $start_date = TTDate::getBeginYearEpoch($epoch);
                $end_date = (TTDate::getBeginDayEpoch((TTDate::getMiddleDayEpoch($epoch) - 86400)) - 1);
                break;
            case 'this_year_today':
                $start_date = TTDate::getBeginYearEpoch($epoch);
                $end_date = (TTDate::getBeginDayEpoch(TTDate::getMiddleDayEpoch($epoch)) - 1);
                break;
            case 'this_year_ytd': //Same as This Year (Up To Tomorrow), which includes today.
            case 'this_year_tomorrow':
                $start_date = TTDate::getBeginYearEpoch($epoch);
                $end_date = (TTDate::getBeginDayEpoch((TTDate::getMiddleDayEpoch($epoch) + 86400)) - 1);
                break;
            case 'this_year_last_week':
                $start_date = TTDate::getBeginYearEpoch($epoch);
                $end_date = (TTDate::getBeginWeekEpoch((TTDate::getMiddleDayEpoch($epoch) - (86400 * 7)), $start_week_day) - 1);
                break;
            case 'this_year_this_week':
                $start_date = TTDate::getBeginYearEpoch($epoch);
                $end_date = (TTDate::getBeginWeekEpoch($epoch, $start_week_day) - 1);
                break;
            case 'this_year_this_month':
                $start_date = TTDate::getBeginYearEpoch($epoch);
                $end_date = (TTDate::getBeginMonthEpoch($epoch) - 1);
                break;
            case 'this_year_last_month':
                $start_date = TTDate::getBeginYearEpoch($epoch);
                $end_date = (TTDate::getBeginMonthEpoch((TTDate::getBeginMonthEpoch($epoch) - 86400)) - 1);
                break;
            case 'this_year_30_days':
                $start_date = TTDate::getBeginYearEpoch($epoch);
                $end_date = (TTDate::getBeginDayEpoch((TTDate::getMiddleDayEpoch($epoch) - (86400 * 30))) - 1);
                break;
            case 'this_year_45_days':
                $start_date = TTDate::getBeginYearEpoch($epoch);
                $end_date = (TTDate::getBeginDayEpoch((TTDate::getMiddleDayEpoch($epoch) - (86400 * 45))) - 1);
                break;
            case 'this_year_60_days':
                $start_date = TTDate::getBeginYearEpoch($epoch);
                $end_date = (TTDate::getBeginDayEpoch((TTDate::getMiddleDayEpoch($epoch) - (86400 * 60))) - 1);
                break;
            case 'this_year_90_days':
                $start_date = TTDate::getBeginYearEpoch($epoch);
                $end_date = (TTDate::getBeginDayEpoch((TTDate::getMiddleDayEpoch($epoch) - (86400 * 90))) - 1);
                break;
            case 'this_year_90_days':
                $start_date = TTDate::getBeginYearEpoch($epoch);
                $end_date = (TTDate::getBeginDayEpoch((TTDate::getMiddleDayEpoch($epoch) - (86400 * 90))) - 1);
                break;
            case 'this_year_last_quarter':
                $start_date = TTDate::getBeginYearEpoch($epoch);
                $quarter = (TTDate::getYearQuarter($epoch) - 1);
                if ($quarter == 0) {
                    $quarter = 4;
                    $epoch = (TTDate::getBeginYearEpoch() - 86400); //Need to jump back into the previous year.
                }
                $quarter_dates = TTDate::getYearQuarters($epoch, $quarter);
                $end_date = ($quarter_dates['start'] - 1);
                break;
            case 'this_year_this_quarter':
                $start_date = TTDate::getBeginYearEpoch($epoch);
                $quarter = TTDate::getYearQuarter($epoch);
                $quarter_dates = TTDate::getYearQuarters($epoch, $quarter);
                $end_date = ($quarter_dates['start'] - 1);
                break;

            case 'last_year':
                $start_date = TTDate::getBeginYearEpoch((TTDate::getBeginYearEpoch($epoch) - 86400));
                $end_date = TTDate::getEndYearEpoch((TTDate::getBeginYearEpoch($epoch) - 86400));
                break;
            case 'last_2_years':
                $end_date = TTDate::getEndYearEpoch((TTDate::getBeginYearEpoch($epoch) - 86400));
                $start_date = mktime(0, 0, 0, TTDate::getMonth($end_date), TTDate::getDayOfMonth($end_date), (TTDate::getYear($end_date) - 2));
                break;
            case 'last_3_years':
                $end_date = TTDate::getEndYearEpoch((TTDate::getBeginYearEpoch($epoch) - 86400));
                $start_date = mktime(0, 0, 0, TTDate::getMonth($end_date), TTDate::getDayOfMonth($end_date), (TTDate::getYear($end_date) - 3));
                break;
            case 'last_5_years':
                $end_date = TTDate::getEndYearEpoch((TTDate::getBeginYearEpoch($epoch) - 86400));
                $start_date = mktime(0, 0, 0, TTDate::getMonth($end_date), TTDate::getDayOfMonth($end_date), (TTDate::getYear($end_date) - 5));
                break;


            case 'to_yesterday': //"Up To" means we need to use the end time of the day we go up to.
                $start_date = TTDate::getBeginYearEpoch((TTDate::getBeginYearEpoch(31564800) - 86400));
                $end_date = (TTDate::getBeginDayEpoch((TTDate::getMiddleDayEpoch($epoch) - 86400)) - 1);
                break;
            case 'to_today':
                $start_date = TTDate::getBeginYearEpoch((TTDate::getBeginYearEpoch(31564800) - 86400));
                $end_date = (TTDate::getBeginDayEpoch(TTDate::getMiddleDayEpoch($epoch)) - 1);
                break;
            case 'to_this_week':
                $start_date = TTDate::getBeginYearEpoch((TTDate::getBeginYearEpoch(31564800) - 86400));
                $end_date = (TTDate::getBeginWeekEpoch($epoch, $start_week_day) - 1);
                break;
            case 'to_last_week':
                $start_date = TTDate::getBeginYearEpoch((TTDate::getBeginYearEpoch(31564800) - 86400));
                $end_date = (TTDate::getBeginWeekEpoch((TTDate::getMiddleDayEpoch($epoch) - (86400 * 7)), $start_week_day) - 1);
                break;
            case 'to_7_days':
                $start_date = TTDate::getBeginYearEpoch((TTDate::getBeginYearEpoch(31564800) - 86400));
                $end_date = (TTDate::getBeginDayEpoch((TTDate::getMiddleDayEpoch($epoch) - (86400 * 7))) - 1);
                break;
            case 'to_14_days':
                $start_date = TTDate::getBeginYearEpoch((TTDate::getBeginYearEpoch(31564800) - 86400));
                $end_date = (TTDate::getBeginDayEpoch((TTDate::getMiddleDayEpoch($epoch) - (86400 * 14))) - 1);
                break;
            case 'to_last_pay_period':
            case 'to_this_pay_period':
                Debug::text('Time Period for Pay Period Schedule selected...', __FILE__, __LINE__, __METHOD__, 10);
                //Make sure user_obj is set.
                if (!is_object($user_obj)) {
                    Debug::text('User Object was not passsed...', __FILE__, __LINE__, __METHOD__, 10);
                    break;
                }

                if (!isset($params['pay_period_schedule_id'])) {
                    $params['pay_period_schedule_id'] = null;
                }

                $end_date = false;
                //Since we allow multiple pay_period schedules to be selected, we have to return pay_period_ids, not start/end dates.
                if ($time_period == 'to_this_pay_period') {
                    Debug::text('to_this_pay_period', __FILE__, __LINE__, __METHOD__, 10);
                    $pplf = TTnew('PayPeriodListFactory');
                    $pplf->getThisPayPeriodByCompanyIdAndPayPeriodScheduleIdAndDate($user_obj->getCompany(), $params['pay_period_schedule_id'], time());
                    if ($pplf->getRecordCount() > 0) {
                        foreach ($pplf as $pp_obj) {
                            if ($end_date == false or $pp_obj->getStartDate() < $end_date) {
                                $end_date = $pp_obj->getStartDate();
                            }
                        }
                    }
                } elseif ($time_period == 'to_last_pay_period') {
                    Debug::text('to_last_pay_period', __FILE__, __LINE__, __METHOD__, 10);
                    $pplf = TTnew('PayPeriodListFactory');
                    $pplf->getLastPayPeriodByCompanyIdAndPayPeriodScheduleIdAndDate($user_obj->getCompany(), $params['pay_period_schedule_id'], time());
                    if ($pplf->getRecordCount() > 0) {
                        foreach ($pplf as $pp_obj) {
                            if ($end_date == false or $pp_obj->getStartDate() < $end_date) {
                                $end_date = $pp_obj->getStartDate();
                            }
                        }
                    }
                }

                $start_date = TTDate::getBeginYearEpoch((TTDate::getBeginYearEpoch(31564800) - 86400));
                $end_date--;
                break;
            case 'to_last_month':
                $start_date = TTDate::getBeginYearEpoch((TTDate::getBeginYearEpoch(31564800) - 86400));
                $end_date = (TTDate::getBeginMonthEpoch((TTDate::getBeginMonthEpoch($epoch) - 86400)) - 1);
                break;
            case 'to_this_month':
                $start_date = TTDate::getBeginYearEpoch((TTDate::getBeginYearEpoch(31564800) - 86400));
                $end_date = (TTDate::getBeginMonthEpoch($epoch) - 1);
                break;
            case 'to_30_days':
                $start_date = TTDate::getBeginYearEpoch((TTDate::getBeginYearEpoch(31564800) - 86400));
                $end_date = (TTDate::getBeginDayEpoch((TTDate::getMiddleDayEpoch($epoch) - (86400 * 30))) - 1);
                break;
            case 'to_45_days':
                $start_date = TTDate::getBeginYearEpoch((TTDate::getBeginYearEpoch(31564800) - 86400));
                $end_date = (TTDate::getBeginDayEpoch((TTDate::getMiddleDayEpoch($epoch) - (86400 * 45))) - 1);
                break;
            case 'to_60_days':
                $start_date = TTDate::getBeginYearEpoch((TTDate::getBeginYearEpoch(31564800) - 86400));
                $end_date = (TTDate::getBeginDayEpoch((TTDate::getMiddleDayEpoch($epoch) - (86400 * 60))) - 1);
                break;
            case 'to_last_quarter':
                $quarter = (TTDate::getYearQuarter($epoch) - 1);
                if ($quarter == 0) {
                    $quarter = 4;
                    $epoch = (TTDate::getBeginYearEpoch() - 86400); //Need to jump back into the previous year.
                }
                $quarter_dates = TTDate::getYearQuarters($epoch, $quarter);
                $start_date = TTDate::getBeginYearEpoch((TTDate::getBeginYearEpoch(31564800) - 86400));
                $end_date = ($quarter_dates['start'] - 1);
                break;
            case 'to_this_quarter':
                $quarter = TTDate::getYearQuarter($epoch);
                $quarter_dates = TTDate::getYearQuarters($epoch, $quarter);
                $start_date = TTDate::getBeginYearEpoch((TTDate::getBeginYearEpoch(31564800) - 86400));
                $end_date = ($quarter_dates['start'] - 1);
                break;
            case 'to_90_days':
                $start_date = TTDate::getBeginYearEpoch((TTDate::getBeginYearEpoch(31564800) - 86400));
                $end_date = (TTDate::getBeginDayEpoch((TTDate::getMiddleDayEpoch($epoch) - (86400 * 90))) - 1);
                break;
            case 'to_this_year':
                $start_date = TTDate::getBeginYearEpoch((TTDate::getBeginYearEpoch(31564800) - 86400));
                $end_date = (TTDate::getBeginYearEpoch($epoch) - 1);
                break;
            case 'to_last_year':
                $start_date = TTDate::getBeginYearEpoch((TTDate::getBeginYearEpoch(31564800) - 86400));
                $end_date = (TTDate::getBeginYearEpoch((TTDate::getBeginYearEpoch($epoch) - 86400)) - 1);
                break;
            case 'tomorrow':
                $start_date = TTDate::getBeginDayEpoch((TTDate::getMiddleDayEpoch($epoch) + 86400));
                $end_date = TTDate::getEndDayEpoch((TTDate::getMiddleDayEpoch($epoch) + 86400));
                break;
            case 'next_24_hours':
                $start_date = $epoch;
                $end_date = ($epoch + 86400);
                break;
            case 'next_48_hours':
                $start_date = $epoch;
                $end_date = ($epoch + (86400 * 2));
                break;
            case 'next_72_hours':
                $start_date = $epoch;
                $end_date = ($epoch + (86400 * 3));
                break;
            case 'next_week':
                $start_date = TTDate::getBeginWeekEpoch((TTDate::getMiddleDayEpoch($epoch) + (86400 * 7)), $start_week_day);
                $end_date = TTDate::getEndWeekEpoch((TTDate::getMiddleDayEpoch($epoch) + (86400 * 7)), $start_week_day);
                break;
            case 'next_2_weeks':
                $start_date = TTDate::getBeginWeekEpoch((TTDate::getMiddleDayEpoch($epoch) + (86400 * 7)), $start_week_day);
                $end_date = TTDate::getEndWeekEpoch((TTDate::getMiddleDayEpoch($epoch) + (86400 * 14)), $start_week_day);
                break;
            case 'next_7_days':
                $start_date = TTDate::getBeginDayEpoch((TTDate::getMiddleDayEpoch($epoch) + 86400));
                $end_date = TTDate::getEndDayEpoch((TTDate::getMiddleDayEpoch($epoch) + (86400 * 7)));
                break;
            case 'next_14_days':
                $start_date = TTDate::getBeginDayEpoch((TTDate::getMiddleDayEpoch($epoch) + 86400));
                $end_date = TTDate::getEndDayEpoch((TTDate::getMiddleDayEpoch($epoch) + (86400 * 14)));
                break;
            case 'next_month':
                $start_date = TTDate::getBeginMonthEpoch((TTDate::getEndMonthEpoch($epoch) + 86400));
                $end_date = TTDate::getEndMonthEpoch((TTDate::getEndMonthEpoch($epoch) + 86400));
                break;
            case 'next_2_months':
                $start_date = TTDate::getBeginMonthEpoch((TTDate::getEndMonthEpoch($epoch) + 86400));
                $end_date = TTDate::getEndMonthEpoch((TTDate::getEndMonthEpoch($epoch) + (86400 * 32)));
                break;
            case 'next_30_days':
                $start_date = TTDate::getBeginDayEpoch((TTDate::getMiddleDayEpoch($epoch) + 86400));
                $end_date = TTDate::getEndDayEpoch((TTDate::getMiddleDayEpoch($epoch) + (86400 * 30)));
                break;
            case 'next_45_days':
                $start_date = TTDate::getBeginDayEpoch((TTDate::getMiddleDayEpoch($epoch) + 86400));
                $end_date = TTDate::getEndDayEpoch((TTDate::getMiddleDayEpoch($epoch) + (86400 * 45)));
                break;
            case 'next_60_days':
                $start_date = TTDate::getBeginDayEpoch((TTDate::getMiddleDayEpoch($epoch) + 86400));
                $end_date = TTDate::getEndDayEpoch((TTDate::getMiddleDayEpoch($epoch) + (86400 * 60)));
                break;
            case 'next_quarter':
                $quarter = (TTDate::getYearQuarter($epoch) + 1);
                if ($quarter == 5) {
                    $quarter = 1;
                    $epoch = (TTDate::getEndYearEpoch() + 86400); //Need to jump back into the previous year.
                }
                $quarter_dates = TTDate::getYearQuarters($epoch, $quarter);

                $start_date = $quarter_dates['start'];
                $end_date = $quarter_dates['end'];
                break;
            case 'next_90_days':
                $start_date = TTDate::getBeginDayEpoch((TTDate::getMiddleDayEpoch($epoch) + 86400));
                $end_date = TTDate::getEndDayEpoch((TTDate::getMiddleDayEpoch($epoch) + (86400 * 90)));
                break;
            case 'next_3_months':
                $start_date = TTDate::getEndDayEpoch((TTDate::getMiddleDayEpoch($epoch) + 86400));
                $end_date = mktime(0, 0, 0, (TTDate::getMonth($start_date) + 3), TTDate::getDayOfMonth($start_date), TTDate::getYear($start_date));
                break;
            case 'next_6_months':
                $start_date = TTDate::getEndDayEpoch((TTDate::getMiddleDayEpoch($epoch) + 86400));
                $end_date = mktime(0, 0, 0, (TTDate::getMonth($start_date) + 6), TTDate::getDayOfMonth($start_date), TTDate::getYear($start_date));
                break;
            case 'next_9_months':
                $start_date = TTDate::getEndDayEpoch((TTDate::getMiddleDayEpoch($epoch) + 86400));
                $end_date = mktime(0, 0, 0, (TTDate::getMonth($start_date) + 9), TTDate::getDayOfMonth($start_date), TTDate::getYear($start_date));
                break;
            case 'next_12_months':
                $start_date = TTDate::getEndDayEpoch((TTDate::getMiddleDayEpoch($epoch) + 86400));
                $end_date = mktime(0, 0, 0, (TTDate::getMonth($start_date) + 12), TTDate::getDayOfMonth($start_date), TTDate::getYear($start_date));
                break;
            case 'next_18_months':
                $start_date = TTDate::getEndDayEpoch((TTDate::getMiddleDayEpoch($epoch) + 86400));
                $end_date = mktime(0, 0, 0, (TTDate::getMonth($start_date) + 18), TTDate::getDayOfMonth($start_date), TTDate::getYear($start_date));
                break;
            case 'next_24_months':
                $start_date = TTDate::getEndDayEpoch((TTDate::getMiddleDayEpoch($epoch) + 86400));
                $end_date = mktime(0, 0, 0, (TTDate::getMonth($start_date) + 24), TTDate::getDayOfMonth($start_date), TTDate::getYear($start_date));
                break;
            case 'next_year':
                $start_date = TTDate::getBeginYearEpoch((TTDate::getEndYearEpoch($epoch) + 86400));
                $end_date = TTDate::getEndYearEpoch((TTDate::getEndYearEpoch($epoch) + 86400));
                break;
            case 'next_2_years':
                $start_date = TTDate::getEndYearEpoch((TTDate::getEndYearEpoch($epoch) + 86400));
                $end_date = mktime(0, 0, 0, TTDate::getMonth($start_date), TTDate::getDayOfMonth($start_date), (TTDate::getYear($start_date) + 2));
                break;
            case 'next_3_years':
                $start_date = TTDate::getEndYearEpoch((TTDate::getEndYearEpoch($epoch) + 86400));
                $end_date = mktime(0, 0, 0, TTDate::getMonth($start_date), TTDate::getDayOfMonth($start_date), (TTDate::getYear($start_date) + 3));
                break;
            case 'next_5_years':
                $start_date = TTDate::getEndYearEpoch((TTDate::getEndYearEpoch($epoch) + 86400));
                $end_date = mktime(0, 0, 0, TTDate::getMonth($start_date), TTDate::getDayOfMonth($start_date), (TTDate::getYear($start_date) + 5));
                break;
            case 'all_years':
                $start_date = TTDate::getBeginYearEpoch((TTDate::getBeginYearEpoch(31564800) - 86400));
                $end_date = TTDate::getEndYearEpoch(time() + (86400 * (365 * 2)));
                break;
            default:
                break;
        }

        if (isset($start_date) and isset($end_date)) {
            //Debug::text('Period: '. $time_period .' Start: '. TTDate::getDate('DATE+TIME', $start_date ) .'('.$start_date.') End: '. TTDate::getDate('DATE+TIME', $end_date ) .'('.$end_date.')', __FILE__, __LINE__, __METHOD__, 10);
            return array('start_date' => $start_date, 'end_date' => $end_date);
        } elseif (isset($pay_period_ids)) {
            //Debug::text('Period: '. $time_period .' returning just pay_period_ids...', __FILE__, __LINE__, __METHOD__, 10);
            return array('pay_period_id' => $pay_period_ids);
        }

        return false;
    }

    public static function getBeginMonthEpoch($epoch = null)
    {
        if ($epoch == null or $epoch == '' or !is_numeric($epoch)) {
            $epoch = self::getTime();
        }

        $retval = mktime(0, 0, 0, date('m', $epoch), 1, date('Y', $epoch));

        return $retval;
    }

    //Date pair1

    public static function getYearQuarter($epoch = null)
    {
        if ($epoch == null or $epoch == '' or !is_numeric($epoch)) {
            $epoch = self::getTime();
        }

        $quarter = ceil(date('n', $epoch) / 3);

        //Debug::text('Date: '. TTDate::getDate('DATE+TIME', $epoch ) .' is in quarter: '. $quarter, __FILE__, __LINE__, __METHOD__, 10);
        return $quarter;
    }

    public static function getYearQuarters($epoch = null, $quarter = null, $day_of_month = 1)
    {
        if ($epoch == null or $epoch == '' or !is_numeric($epoch)) {
            $epoch = self::getTime();
        }

        $year = TTDate::getYear($epoch);
        $quarter_dates = array(
            1 => array('start' => mktime(0, 0, 0, 1, $day_of_month, $year), 'end' => mktime(0, 0, -1, 4, ($day_of_month > 30) ? 30 : $day_of_month, $year)),
            2 => array('start' => mktime(0, 0, 0, 4, ($day_of_month > 30) ? 30 : $day_of_month, $year), 'end' => mktime(0, 0, -1, 7, $day_of_month, $year)),
            3 => array('start' => mktime(0, 0, 0, 7, $day_of_month, $year), 'end' => mktime(0, 0, -1, 10, ($day_of_month > 30) ? 30 : $day_of_month, $year)),
            4 => array('start' => mktime(0, 0, 0, 10, $day_of_month, $year), 'end' => mktime(0, 0, -1, 13, $day_of_month, $year)),
        );

        if ($quarter != '') {
            if (isset($quarter_dates[$quarter])) {
                $quarter_dates = $quarter_dates[$quarter];
            } else {
                return false;
            }
        }

        return $quarter_dates;
    }

    public static function getYear($epoch = null)
    {
        if ($epoch == null) {
            $epoch = TTDate::getTime();
        }

        return date('Y', $epoch);
    }

    public static function getBeginYearEpoch($epoch = null)
    {
        if ($epoch == null or $epoch == '' or !is_numeric($epoch)) {
            $epoch = self::getTime();
        }

        $retval = mktime(0, 0, 0, 1, 1, date('Y', $epoch));

        return $retval;
    }

    public static function getDayOfMonth($epoch = null)
    {
        if ($epoch == null or $epoch == '') {
            $epoch = self::getTime();
        }

        return date('j', $epoch);
    }

    public static function getReportDateOptions($column_name_prefix = null, $column_name = null, $sort_prefix = null, $include_pay_period = true)
    {
        if ($sort_prefix == '') {
            $sort_prefix = 19;
        }

        if ($column_name == '') {
            $column_name = TTi18n::getText('Date');
        }

        if ($column_name_prefix != '') {
            $column_name_prefix .= '-';
        }

        $retarr = array(
            '-' . $sort_prefix . '00-' . $column_name_prefix . 'date_stamp' => $column_name,
            '-' . $sort_prefix . '01-' . $column_name_prefix . 'time_stamp' => $column_name . ' - ' . TTi18n::getText('Time of Day'),
            '-' . $sort_prefix . '01-' . $column_name_prefix . 'date_time_stamp' => $column_name . ' - ' . TTi18n::getText('w/Time'),

            '-' . $sort_prefix . '04-' . $column_name_prefix . 'hour_of_day' => $column_name . ' - ' . TTi18n::getText('Hour of Day'),

            '-' . $sort_prefix . '10-' . $column_name_prefix . 'date_dow' => $column_name . ' - ' . TTi18n::getText('Day of Week'),
            '-' . $sort_prefix . '12-' . $column_name_prefix . 'date_dow_week' => $column_name . ' - ' . TTi18n::getText('Day of Week+Week'),
            '-' . $sort_prefix . '14-' . $column_name_prefix . 'date_dow_month' => $column_name . ' - ' . TTi18n::getText('Day of Week+Month'),
            '-' . $sort_prefix . '16-' . $column_name_prefix . 'date_dow_month_year' => $column_name . ' - ' . TTi18n::getText('Day of Week+Month+Year'),
            '-' . $sort_prefix . '18-' . $column_name_prefix . 'date_dow_dom_month_year' => $column_name . ' - ' . TTi18n::getText('Day of Week+Day Of Month+Year'),

            '-' . $sort_prefix . '20-' . $column_name_prefix . 'date_week' => $column_name . ' - ' . TTi18n::getText('Week'),
            '-' . $sort_prefix . '22-' . $column_name_prefix . 'date_week_month' => $column_name . ' - ' . TTi18n::getText('Week+Month'),
            '-' . $sort_prefix . '24-' . $column_name_prefix . 'date_week_month_year' => $column_name . ' - ' . TTi18n::getText('Week+Month+Year'),
            '-' . $sort_prefix . '25-' . $column_name_prefix . 'date_week_start' => $column_name . ' - ' . TTi18n::getText('Week (Starting)'),
            '-' . $sort_prefix . '26-' . $column_name_prefix . 'date_week_end' => $column_name . ' - ' . TTi18n::getText('Week (Ending)'),

            '-' . $sort_prefix . '30-' . $column_name_prefix . 'date_dom' => $column_name . ' - ' . TTi18n::getText('Day of Month'),
            '-' . $sort_prefix . '32-' . $column_name_prefix . 'date_dom_month' => $column_name . ' - ' . TTi18n::getText('Day of Month+Month'),
            '-' . $sort_prefix . '34-' . $column_name_prefix . 'date_dom_month_year' => $column_name . ' - ' . TTi18n::getText('Day of Month+Month+Year'),

            '-' . $sort_prefix . '40-' . $column_name_prefix . 'date_month' => $column_name . ' - ' . TTi18n::getText('Month'),
            '-' . $sort_prefix . '42-' . $column_name_prefix . 'date_month_year' => $column_name . ' - ' . TTi18n::getText('Month+Year'),
            '-' . $sort_prefix . '43-' . $column_name_prefix . 'date_month_start' => $column_name . ' - ' . TTi18n::getText('Month (Starting)'),
            '-' . $sort_prefix . '44-' . $column_name_prefix . 'date_month_end' => $column_name . ' - ' . TTi18n::getText('Month (Ending)'),

            '-' . $sort_prefix . '50-' . $column_name_prefix . 'date_quarter' => $column_name . ' - ' . TTi18n::getText('Quarter'),
            '-' . $sort_prefix . '52-' . $column_name_prefix . 'date_quarter_year' => $column_name . ' - ' . TTi18n::getText('Quarter+Year'),
            '-' . $sort_prefix . '53-' . $column_name_prefix . 'date_quarter_start' => $column_name . ' - ' . TTi18n::getText('Quarter (Starting)'),
            '-' . $sort_prefix . '54-' . $column_name_prefix . 'date_quarter_end' => $column_name . ' - ' . TTi18n::getText('Quarter (Ending)'),

            '-' . $sort_prefix . '60-' . $column_name_prefix . 'date_year' => $column_name . ' - ' . TTi18n::getText('Year'),
            '-' . $sort_prefix . '61-' . $column_name_prefix . 'date_year_start' => $column_name . ' - ' . TTi18n::getText('Year (Starting)'),
            '-' . $sort_prefix . '62-' . $column_name_prefix . 'date_year_end' => $column_name . ' - ' . TTi18n::getText('Year (Ending)'),
        );

        if ($include_pay_period == true) {
            //Don't use the $column_name on these, as there is only one type of pay period columns.
            $pay_period_arr = array(
                '-' . $sort_prefix . '70-' . $column_name_prefix . 'pay_period' => TTi18n::getText('Pay Period'),
                '-' . $sort_prefix . '71-' . $column_name_prefix . 'pay_period_start_date' => TTi18n::getText('Pay Period - Start Date'),
                '-' . $sort_prefix . '72-' . $column_name_prefix . 'pay_period_end_date' => TTi18n::getText('Pay Period - End Date'),
                '-' . $sort_prefix . '73-' . $column_name_prefix . 'pay_period_transaction_date' => TTi18n::getText('Pay Period - Transaction Date'),
            );
            $retarr = array_merge($retarr, $pay_period_arr);
        }

        return $retarr;
    }

    public static function getReportDates($column, $epoch = null, $post_processing = true, $user_obj = null, $params = null)
    {
        //Make sure if epoch is actually NULL that we return a blank array and not todays date.
        //This is import for things like termination dates that may be NULL when not set.
        if ($epoch === null) {
            return array();
        }

        $column = Misc::trimSortPrefix($column);

        //Trim off a column_name_prefix, or everything before the "-"
        $tmp_column = explode('-', $column);
        if (isset($tmp_column[1])) {
            $column = $tmp_column[1];
        }

        //Don't use todays date, as that can cause a lot of confusion in reports, especially when displaying time not assigned to a pay period
        //and the pay period dates all show today. Just leave blank.
        //if ($epoch == NULL OR $epoch == '' ) { //Epoch can be a string sometimes.
        //	$epoch = self::getTime();
        //}

        $start_week_day = 0;
        if (is_object($user_obj)) {
            $user_prefs = $user_obj->getUserPreferenceObject();
            if (is_object($user_prefs)) {
                $start_week_day = $user_prefs->getStartWeekDay();
            }
        }

        if ($post_processing == true) {
            $split_epoch = explode('-', $epoch);
            //Human friendly display, NOT for sorting.
            switch ($column) {
                case 'pay_period_start_date':
                case 'pay_period_end_date':
                case 'pay_period_transaction_date':
                    $retval = TTDate::getDate('DATE', $epoch);
                    break;
                case 'date_stamp':
                case 'date_week_start':
                case 'date_week_end':
                case 'date_month_start':
                case 'date_month_end':
                case 'date_quarter_start':
                case 'date_quarter_end':
                case 'date_year_start':
                case 'date_year_end':
                    $epoch = is_numeric($epoch) ? $epoch : strtotime($epoch);
                    $retval = TTDate::getDate('DATE', $epoch);
                    break;
                case 'time_stamp':
                    $retval = TTDate::getDate('TIME', is_numeric($epoch) ? $epoch : strtotime($epoch));
                    break;
                case 'hour_of_day':
                    $retval = TTDate::getDate('TIME', is_numeric($epoch) ? TTDate::roundTime($epoch, 3600, 10) : TTDate::roundTime(strtotime($epoch), 3600, 10)); //Round down to the nearest hour.
                    break;
                case 'date_time_stamp':
                    $retval = TTDate::getDate('DATE+TIME', is_numeric($epoch) ? $epoch : strtotime($epoch));
                    break;
                case 'date_dow':
                    $retval = TTDate::getDayOfWeekName($epoch);
                    break;
                case 'date_dow_week':
                    $retval = TTDate::getDayOfWeekName($split_epoch[1]) . ' ' . $split_epoch[0];
                    break;
                case 'date_dow_month':
                    $retval = TTDate::getDayOfWeekName($split_epoch[1]) . '-' . TTDate::getMonthName($split_epoch[0]);
                    break;
                case 'date_dow_month_year':
                    $retval = TTDate::getDayOfWeekName($split_epoch[2]) . '-' . TTDate::getMonthName($split_epoch[1]) . '-' . $split_epoch[0];
                    break;
                case 'date_dow_dom_month_year':
                    $retval = TTDate::getDayOfWeekName($split_epoch[2]) . ' ' . $split_epoch[1] . '-' . TTDate::getMonthName($split_epoch[1]) . '-' . $split_epoch[0];
                    break;
                case 'date_week':
                    $retval = $epoch;
                    break;
                case 'date_week_month':
                    $retval = $split_epoch[3] . ' ' . TTDate::getMonthName($split_epoch[1]);
                    break;
                case 'date_week_month_year':
                    $retval = $split_epoch[3] . ' ' . TTDate::getMonthName($split_epoch[1]) . '-' . $split_epoch[0];
                    break;
                case 'date_dom':
                    $retval = $epoch;
                    break;
                case 'date_dom_month':
                    $retval = $split_epoch[1] . '-' . TTDate::getMonthName($split_epoch[0]);
                    break;
                case 'date_dom_month_year':
                    $retval = $split_epoch[2] . '-' . TTDate::getMonthName($split_epoch[1], true) . '-' . $split_epoch[0];
                    break;
                case 'date_month':
                    $retval = TTDate::getMonthName($epoch);
                    break;
                case 'date_month_year':
                    $retval = TTDate::getMonthName($split_epoch[1]) . '-' . $split_epoch[0];
                    break;
                case 'date_quarter':
                    $retval = $epoch;
                    break;
                case 'date_quarter_year':
                    $retval = $split_epoch[1] . '-' . $split_epoch[0];
                    break;
                case 'date_year':
                    $retval = $epoch;
                    break;
                case 'pay_period':
                    $retval = $params;
                    break;
                default:
                    Debug::text('Date Column does not match!: ' . $column, __FILE__, __LINE__, __METHOD__, 10);
                    break;
            }
            //Debug::text('Column: '. $column .' Input: '. $epoch .' Retval: '. $retval, __FILE__, __LINE__, __METHOD__, 10);
        } else {
            //Return data for *all* columns at once.
            if ($epoch == null or $epoch == '' or !is_numeric($epoch)) { //Epoch must be numeric
                $epoch = self::getTime();
            }

            $column_prefix = null;
            if ($column != '') {
                $column_prefix = $column . '-';
            }
            $retval = array(
                $column_prefix . 'date_stamp' => date('Y-m-d', $epoch),
                $column_prefix . 'time_stamp' => $epoch,
                $column_prefix . 'hour_of_day' => TTDate::roundTime($epoch, 3600, 10),
                $column_prefix . 'date_time_stamp' => $epoch,
                $column_prefix . 'date_dow' => date('w', $epoch),
                $column_prefix . 'date_dow_week' => date('W-w', $epoch),
                $column_prefix . 'date_dow_month' => date('m-w', $epoch),
                $column_prefix . 'date_dow_month_year' => date('Y-m-w', $epoch),
                $column_prefix . 'date_dow_dom_month_year' => date('Y-m-w-W', $epoch),
                $column_prefix . 'date_week' => self::getWeek($epoch, $start_week_day),
                $column_prefix . 'date_week_month' => date('Y-m-d-W', TTDate::getBeginWeekEpoch($epoch, $start_week_day)), //Need to have day in here so sorting is done properly.
                $column_prefix . 'date_week_month_year' => date('Y-m-d-W', TTDate::getBeginWeekEpoch($epoch, $start_week_day)), //Need to have day in here so sorting is done properly.
                $column_prefix . 'date_week_start' => date('Y-m-d', TTDate::getBeginWeekEpoch($epoch, $start_week_day)),
                $column_prefix . 'date_week_end' => date('Y-m-d', TTDate::getEndWeekEpoch($epoch, $start_week_day)),
                $column_prefix . 'date_dom' => date('d', $epoch),
                $column_prefix . 'date_dom_month' => date('m-d', $epoch),
                $column_prefix . 'date_dom_month_year' => date('Y-m-d', $epoch),
                $column_prefix . 'date_month' => date('m', $epoch),
                $column_prefix . 'date_month_year' => date('Y-m', $epoch),
                $column_prefix . 'date_month_start' => date('Y-m-d', TTDate::getBeginMonthEpoch($epoch)),
                $column_prefix . 'date_month_end' => date('Y-m-d', TTDate::getEndMonthEpoch($epoch)),
                $column_prefix . 'date_quarter' => TTDate::getYearQuarter($epoch),
                $column_prefix . 'date_quarter_year' => date('Y', $epoch) . '-' . TTDate::getYearQuarter($epoch),
                $column_prefix . 'date_quarter_start' => date('Y-m-d', TTDate::getBeginQuarterEpoch($epoch)),
                $column_prefix . 'date_quarter_end' => date('Y-m-d', TTDate::getEndQuarterEpoch($epoch)),
                $column_prefix . 'date_year' => TTDate::getYear($epoch),
                $column_prefix . 'date_year_start' => date('Y-m-d', TTDate::getBeginYearEpoch($epoch)),
                $column_prefix . 'date_year_end' => date('Y-m-d', TTDate::getEndYearEpoch($epoch)),
            );

            //Only display these dates if they are passed in separately in the $param array.
            if (isset($params['pay_period_start_date']) and $params['pay_period_start_date'] != '' and isset($params['pay_period_end_date']) and $params['pay_period_end_date'] != '') {
                $retval[$column_prefix . 'pay_period'] = array('sort' => $params['pay_period_start_date'], 'display' => TTDate::getDate('DATE', $params['pay_period_start_date']) . ' -> ' . TTDate::getDate('DATE', $params['pay_period_end_date']));
            }
            if (isset($params['pay_period_start_date']) and $params['pay_period_start_date'] != '') {
                $retval[$column_prefix . 'pay_period_start_date'] = $params['pay_period_start_date'];
            }
            if (isset($params['pay_period_end_date']) and $params['pay_period_end_date'] != '') {
                $retval[$column_prefix . 'pay_period_end_date'] = $params['pay_period_end_date'];
            }
            if (isset($params['pay_period_transaction_date']) and $params['pay_period_transaction_date'] != '') {
                $retval[$column_prefix . 'pay_period_transaction_date'] = $params['pay_period_transaction_date'];
            }
        }


        if (isset($retval)) {
            return $retval;
        }

        return false;
    }

    public static function getDayOfWeekName($dow)
    {
        return self::getDayOfWeekByInt($dow);
    }

    public static function getDayOfWeekByInt($int, $translation = true)
    {
        self::getDayOfWeekArray($translation);

        if (isset(self::$day_of_week_arr[$int])) {
            return self::$day_of_week_arr[$int];
        }

        return false;
    }

    public static function getMonthName($month, $short_name = false)
    {
        $month = (int)$month;
        $month_names = self::getMonthOfYearArray($short_name);
        if (isset($month_names[$month])) {
            return $month_names[$month];
        }

        return false;
    }

    // Function to return "13 mins ago" text from a given time.

    public static function getMonthOfYearArray($short_name = false)
    {
        if ($short_name == true) {
            if (is_array(self::$short_month_of_year_arr) == false) {
                self::$short_month_of_year_arr = self::_get_month_short_names();
            }
            return self::$short_month_of_year_arr;
        } else {
            if (is_array(self::$long_month_of_year_arr) == false) {
                self::$long_month_of_year_arr = self::_get_month_long_names();
            }
            return self::$long_month_of_year_arr;
        }
    }

    //Runs strtotime over a string, but if it happens to be an epoch, strtotime
    //returns -1, so in this case, just return the epoch again.

    private static function _get_month_short_names()
    {
        // i18n: This private method is not called anywhere in the class. (it is now)
        //		 It's purpose is simply to ensure that the short (3 letter)
        //		 month forms are included in getText() calls so that they
        //		 will be properly extracted for translation.
        return array(
            1 => TTi18n::getText('Jan'),
            2 => TTi18n::getText('Feb'),
            3 => TTi18n::getText('Mar'),
            4 => TTi18n::getText('Apr'),
            5 => TTi18n::getText('May'),
            6 => TTi18n::getText('Jun'),
            7 => TTi18n::getText('Jul'),
            8 => TTi18n::getText('Aug'),
            9 => TTi18n::getText('Sep'),
            10 => TTi18n::getText('Oct'),
            11 => TTi18n::getText('Nov'),
            12 => TTi18n::getText('Dec'),
        );
    }

    private static function _get_month_long_names()
    {
        // i18n: It's purpose is simply to ensure that the short (3 letter)
        //		 month forms are included in getText() calls so that they
        //		 will be properly extracted for translation.
        return array(
            1 => TTi18n::getText('January'),
            2 => TTi18n::getText('February'),
            3 => TTi18n::getText('March'),
            4 => TTi18n::getText('April'),
            5 => TTi18n::getText('May'),
            6 => TTi18n::getText('June'),
            7 => TTi18n::getText('July'),
            8 => TTi18n::getText('August'),
            9 => TTi18n::getText('September'),
            10 => TTi18n::getText('October'),
            11 => TTi18n::getText('November'),
            12 => TTi18n::getText('December')
        );
    }

    public static function getWeek($epoch = null, $start_week_day = 0)
    {
        //Default start_day_of_week to 1 (Monday) as that is what PHP defaults to.
        if ($epoch == null or $epoch == '') {
            $epoch = self::getTime();
        }

        if ($start_week_day == 1) { //Mon
            $retval = date('W', $epoch);
        } elseif ($start_week_day == 0) { //Sun
            $retval = date('W', ($epoch + 86400));
        } else { //Tue-Sat
            $retval = date('W', ($epoch - (86400 * ($start_week_day - 1))));
        }

        return $retval;
    }

    public static function getBeginQuarterEpoch($epoch = null)
    {
        if ($epoch == null or $epoch == '' or !is_numeric($epoch)) {
            $epoch = self::getTime();
        }

        $quarter = TTDate::getYearQuarter($epoch);
        $quarter_dates = TTDate::getYearQuarters($epoch, $quarter);

        $retval = $quarter_dates['start'];

        return $retval;
    }

    public static function getEndQuarterEpoch($epoch = null)
    {
        if ($epoch == null or $epoch == '' or !is_numeric($epoch)) {
            $epoch = self::getTime();
        }

        $quarter = TTDate::getYearQuarter($epoch);
        $quarter_dates = TTDate::getYearQuarters($epoch, $quarter);

        $retval = $quarter_dates['end'];

        return $retval;
    }

    public static function getISO8601Duration($time)
    {
        $units = array(
            'Y' => (365 * 24 * 3600),
            'D' => (24 * 3600),
            'H' => 3600,
            'M' => 60,
            'S' => 1,
        );

        $str = 'P';
        $istime = false;

        foreach ($units as $unitName => &$unit) {
            $quot = intval($time / $unit);
            $time -= ($quot * $unit);
            $unit = $quot;
            if ($unit > 0) {
                if (!$istime and in_array($unitName, array('H', 'M', 'S'))) { // There may be a better way to do this
                    $str .= 'T';
                    $istime = true;
                }
                $str .= strval($unit) . $unitName;
            }
        }

        return $str;
    }

    public static function inApplyFrequencyWindow($frequency_id, $start_date, $end_date, $frequency_criteria = array())
    {
        /*
        Frequency IDs:
                                                20 => 'Annually',
                                                25 => 'Quarterly',
                                                30 => 'Monthly',
                                                40 => 'Weekly',
                                                100 => 'Specific Date', //Pay Period Dates, Hire Dates, Termination Dates, etc...

         */

        if (!isset($frequency_criteria['month'])) {
            $frequency_criteria['month'] = 0;
        }
        if (!isset($frequency_criteria['day_of_month'])) {
            $frequency_criteria['day_of_month'] = 0;
        }
        if (!isset($frequency_criteria['day_of_week'])) {
            $frequency_criteria['day_of_week'] = 0;
        }
        if (!isset($frequency_criteria['quarter_month'])) {
            $frequency_criteria['quarter_month'] = 0;
        }
        if (!isset($frequency_criteria['date'])) {
            $frequency_criteria['date'] = 0;
        }

        //Debug::Arr($frequency_criteria, 'Freq ID: '. $frequency_id .' Date: Start: '. TTDate::getDate('DATE+TIME', $start_date) .'('.$start_date.') End: '. TTDate::getDate('DATE+TIME', $end_date) .'('.$end_date.')', __FILE__, __LINE__, __METHOD__, 10);
        $retval = false;
        switch ($frequency_id) {
            case 20: //Annually
                $year_epoch1 = mktime(TTDate::getHour($start_date), TTDate::getMinute($start_date), TTDate::getSecond($start_date), $frequency_criteria['month'], $frequency_criteria['day_of_month'], TTDate::getYear($start_date));
                $year_epoch2 = mktime(TTDate::getHour($end_date), TTDate::getMinute($end_date), TTDate::getSecond($end_date), $frequency_criteria['month'], $frequency_criteria['day_of_month'], TTDate::getYear($end_date));
                //Debug::Text('Year1 EPOCH: '. TTDate::getDate('DATE+TIME', $year_epoch1) .'('. $year_epoch1 .')', __FILE__, __LINE__, __METHOD__, 10);
                //Debug::Text('Year2 EPOCH: '. TTDate::getDate('DATE+TIME', $year_epoch2) .'('. $year_epoch2 .')', __FILE__, __LINE__, __METHOD__, 10);

                if (($year_epoch1 >= $start_date and $year_epoch1 <= $end_date)
                    or
                    ($year_epoch2 >= $start_date and $year_epoch2 <= $end_date)
                ) {
                    $retval = true;
                }
                break;
            case 25: //Quarterly
                //Handle quarterly like month, we just need to set the specific month from quarter_month.
                if (abs($end_date - $start_date) > (86400 * 93)) { //3 months
                    $retval = true;
                } else {
                    for ($i = TTDate::getMiddleDayEpoch($start_date); $i <= TTDate::getMiddleDayEpoch($end_date); $i += (86400 * 1)) {
                        if (self::getYearQuarterMonthNumber($i) == $frequency_criteria['quarter_month']
                            and $frequency_criteria['day_of_month'] == self::getDayOfMonth($i)
                        ) {
                            $retval = true;
                            break;
                        }
                    }
                }
                break;
            case 30: //Monthly
                //Make sure if they specify the day of month to be 31, that is still works for months with 30, or 28-29 days, assuming 31 basically means the last day of the month
                if ($frequency_criteria['day_of_month'] > TTDate::getDaysInMonth($start_date)
                    or $frequency_criteria['day_of_month'] > TTDate::getDaysInMonth($end_date)
                ) {
                    $frequency_criteria['day_of_month'] = TTDate::getDaysInMonth($start_date);
                    if (TTDate::getDaysInMonth($end_date) < $frequency_criteria['day_of_month']) {
                        $frequency_criteria['day_of_month'] = TTDate::getDaysInMonth($end_date);
                    }
                    //Debug::Text('Apply frequency day of month exceeds days in this month, using last day of the month instead: '. $frequency_criteria['day_of_month'], __FILE__, __LINE__, __METHOD__, 10);
                }

                $month_epoch1 = mktime(TTDate::getHour($start_date), TTDate::getMinute($start_date), TTDate::getSecond($start_date), TTDate::getMonth($start_date), $frequency_criteria['day_of_month'], TTDate::getYear($start_date));
                $month_epoch2 = mktime(TTDate::getHour($end_date), TTDate::getMinute($end_date), TTDate::getSecond($end_date), TTDate::getMonth($end_date), $frequency_criteria['day_of_month'], TTDate::getYear($end_date));
                //Debug::Text('Day of Month: '. $frequency_criteria['day_of_month'] .' Month EPOCH: '. TTDate::getDate('DATE+TIME', $month_epoch1) .' Current Month: '. TTDate::getMonth( $start_date ), __FILE__, __LINE__, __METHOD__, 10);
                //Debug::Text('Month1 EPOCH: '. TTDate::getDate('DATE+TIME', $month_epoch1) .'('. $month_epoch1 .') Greater Than: '. TTDate::getDate('DATE+TIME', ($start_date)) .' Less Than: '.  TTDate::getDate('DATE+TIME', $end_date) .'('. $end_date .')', __FILE__, __LINE__, __METHOD__, 10);
                //Debug::Text('Month2 EPOCH: '. TTDate::getDate('DATE+TIME', $month_epoch2) .'('. $month_epoch2 .') Greater Than: '. TTDate::getDate('DATE+TIME', ($start_date)) .' Less Than: '.  TTDate::getDate('DATE+TIME', $end_date) .'('. $end_date .')', __FILE__, __LINE__, __METHOD__, 10);

                if (($month_epoch1 >= $start_date and $month_epoch1 <= $end_date)
                    or
                    ($month_epoch2 >= $start_date and $month_epoch2 <= $end_date)
                ) {
                    $retval = true;
                }
                break;
            case 40: //Weekly
                $start_dow = self::getDayOfWeek($start_date);
                $end_dow = self::getDayOfWeek($end_date);

                if ($start_dow == $frequency_criteria['day_of_week']
                    or $end_dow == $frequency_criteria['day_of_week']
                ) {
                    $retval = true;
                } else {
                    if (($end_date - $start_date) > (86400 * 7)) {
                        $retval = true;
                    } else {
                        for ($i = TTDate::getMiddleDayEpoch($start_date); $i <= TTDate::getMiddleDayEpoch($end_date); $i += 86400) {
                            if (self::getDayOfWeek($i) == $frequency_criteria['day_of_week']) {
                                $retval = true;
                                break;
                            }
                        }
                    }
                }
                break;
            case 100: //Specific date
                Debug::Text('Specific Date: ' . TTDate::getDate('DATE+TIME', $frequency_criteria['date']), __FILE__, __LINE__, __METHOD__, 10);
                if ($frequency_criteria['date'] >= $start_date and $frequency_criteria['date'] <= $end_date) {
                    $retval = true;
                }
                break;
        }

        Debug::Text('Retval ' . (int)$retval, __FILE__, __LINE__, __METHOD__, 10);
        return $retval;
    }

    public static function getYearQuarterMonthNumber($epoch = null)
    {
        $year_quarter_months = array(
            1 => 1,
            2 => 2,
            3 => 3,
            4 => 1,
            5 => 2,
            6 => 3,
            7 => 1,
            8 => 2,
            9 => 3,
            10 => 1,
            11 => 2,
            12 => 3,
        );

        $month = TTDate::getMonth($epoch);

        if (isset($year_quarter_months[$month])) {
            return $year_quarter_months[$month];
        }

        return false;
    }
}
