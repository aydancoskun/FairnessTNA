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
class Validator
{
        public $validate_only = false; //Number of errors.
    private $num_errors = 0; //Number of errors.
    private $num_warnings = 0; //Array of errors.
    private $errors = array(); //Array of errors.
private $warnings = array();
    private $verbosity = 8;

    //Checks a result set for one or more rows.

    public function isResultSetWithRows($label, $rs, $msg = null)
    {
        //Debug::Arr($rs, 'ResultSet: ', __FILE__, __LINE__, __METHOD__, $this->verbosity);

        if (is_object($rs)) {
            foreach ($rs as $result) {
                return true;
            }
            unset($result); //code standards
        }

        $this->Error($label, $msg);

        return false;
    }

    public function Error($label, $msg, $value = '')
    {
        Debug::text('Validation Error: Label: ' . $label . ' Value: "' . $value . '" Msg: ' . $msg, __FILE__, __LINE__, __METHOD__, $this->verbosity);

        //If label is NULL, assume we don't actually want to trigger an error.
        //This is good for just using the check functions for other purposes.
        if ($label != '') {
            $this->errors[$label][] = $msg;

            $this->num_errors++;

            return true;
        }

        return false;
    }

    //Function to simple set an error.

    public function isNotResultSetWithRows($label, $rs, $msg = null)
    {
        //Debug::Arr($rs, 'ResultSet: ', __FILE__, __LINE__, __METHOD__, $this->verbosity);

        if (is_object($rs)) {
            foreach ($rs as $result) {
                $this->Error($label, $msg);
                unset($result); // code standards
                return false;
            }
        }

        return true;
    }

    public function isTrue($label, $value, $msg = null)
    {
        if ($value == true) {
            return true;
        }

        $this->Error($label, $msg, (int)$value);

        return false;
    }

    public function isFalse($label, $value, $msg = null)
    {
        if ($value == false) {
            return true;
        }

        $this->Error($label, $msg, (int)$value);

        return false;
    }

    public function isNull($label, $value, $msg = null)
    {
        //Debug::text('Value: '. $value, __FILE__, __LINE__, __METHOD__, $this->verbosity);

        if ($value == null) {
            return true;
        }

        $this->Error($label, $msg, (int)$value);

        return false;
    }

    public function isNotNull($label, $value, $msg = null)
    {
        //Debug::text('Value: '. $value, __FILE__, __LINE__, __METHOD__, $this->verbosity);

        if ($value != null) {
            return true;
        }

        $this->Error($label, $msg, (int)$value);

        return false;
    }

    public function inArrayValue($label, $value, $msg = null, $array)
    {
        //Debug::text('Value: '. $value, __FILE__, __LINE__, __METHOD__, $this->verbosity);

        if (is_array($array) and in_array($value, array_values($array))) {
            return true;
        }

        $this->Error($label, $msg, $value);

        return false;
    }

    public function inArrayKey($label, $key, $msg = null, $array)
    {
        //Debug::text('Key: '. $key, __FILE__, __LINE__, __METHOD__, $this->verbosity);
        //Debug::Arr($array, 'isArrayKey Array:', __FILE__, __LINE__, __METHOD__, $this->verbosity);
        if (is_array($array) and in_array($key, array_keys($array))) {
            return true;
        }

        $this->Error($label, $msg, $key);

        return false;
    }

    public function isNumeric($label, $value, $msg = null)
    {
        //Debug::Text('Value:'. $value, __FILE__, __LINE__, __METHOD__, $this->verbosity);

        //if ( preg_match('/^[-0-9]+$/', $value) ) {
        if (is_numeric($value) == true) {
            return true;
        }

        $this->Error($label, $msg, $value);

        return false;
    }

    public function isLessThan($label, $value, $msg = null, $max = null)
    {
        //Debug::Text('Value:'. $value, __FILE__, __LINE__, __METHOD__, $this->verbosity);

        if ($max == '') {
            $max = PHP_INT_MAX;
        }

        if ($value <= $max) {
            return true;
        }

        $this->Error($label, $msg, $value);

        return false;
    }

    public function isGreaterThan($label, $value, $msg = null, $min = null)
    {
        //Debug::Text('Value:'. $value, __FILE__, __LINE__, __METHOD__, $this->verbosity);

        if ($min == '') {
            $min = (-1 * PHP_INT_MAX);
        }

        if ($value >= $min) {
            return true;
        }

        $this->Error($label, $msg, $value);

        return false;
    }

    public function isFloat($label, $value, $msg = null)
    {
        //Debug::Text('Value:'. $value, __FILE__, __LINE__, __METHOD__, $this->verbosity);

        //Don't use TTi18n::parseFloat() here, as if we are going to be doing that we should do it as early as possible to the user input, like in setObjectFromArray()
        //  We do need to check if the value passed in is already cast to float/int and just accept it in that case.
        //    Because in other locales preg_match() casts $value to a string, which means decimal could become a comma, then it won't match.
        if ((is_float($value) == true or is_int($value) === true) or preg_match('/^((\.[0-9]+)|([-0-9]+(\.[0-9]*)?))$/', $value)) {
            return true;
        }

        $this->Error($label, $msg, $value);

        return false;
    }

    public function isRegEx($label, $value, $msg, $regex)
    {
        //Debug::text('Value: '. $value .' RegEx: '. $regex, __FILE__, __LINE__, __METHOD__, $this->verbosity);
        if (preg_match($regex, $value)) {
            return true;
        }

        $this->Error($label, $msg, $value);

        return false;
    }

    public function isNotRegEx($label, $value, $msg, $regex)
    {
        //Debug::text('Value: '. $value .' RegEx: '. $regex, __FILE__, __LINE__, __METHOD__, $this->verbosity);
        if (preg_match($regex, $value) == false) {
            return true;
        }

        $this->Error($label, $msg, $value);

        return false;
    }

    public function isLengthBeforeDecimal($label, $value, $msg = null, $min = 1, $max = 255)
    {
        $len = strlen(Misc::getBeforeDecimal($value));

        //Debug::text('Value: '. $value .' Length: '. $len .' Min: '. $min .' Max: '. $max, __FILE__, __LINE__, __METHOD__, $this->verbosity);

        if ($len < $min or $len > $max) {
            $this->Error($label, $msg, $value);

            return false;
        }

        return true;
    }

    public function isLengthAfterDecimal($label, $value, $msg = null, $min = 1, $max = 255)
    {
        $len = strlen(Misc::getAfterDecimal($value, false));

        //Debug::text('Value: '. $value .' Length: '. $len .' Min: '. $min .' Max: '. $max, __FILE__, __LINE__, __METHOD__, $this->verbosity);

        if ($len < $min or $len > $max) {
            $this->Error($label, $msg, $value);

            return false;
        }

        return true;
    }

    public function isUniqueCharacters($label, $value, $msg = null)
    {
        //Check for unique characters and not consecutive characters.
        //This will fail on:
        // aaaaaaa
        // bbbbbbb
        // abc
        // xyz
        if (strlen($value) > 2) {
            $char_arr = str_split(strtolower($value));
            $prev_char_int = ord($char_arr[0]);
            foreach ($char_arr as $char) {
                $curr_char_int = ord($char);
                if (abs($prev_char_int - $curr_char_int) > 1) {
                    return true;
                }
                $prev_char_int = $curr_char_int;
            }

            $this->Error($label, $msg, $value);

            return false;
        }

        return true;
    }

    public function isDuplicateCharacters($label, $value, $msg = null, $max_duplicate_percent = false, $consecutive_only = false)
    {
        if (strlen($value) > 2 and $max_duplicate_percent != false) {
            $duplicate_chars = 0;

            $char_arr = str_split(strtolower($value));
            $prev_char_int = ord($char_arr[0]);
            foreach ($char_arr as $char) {
                $curr_char_int = ord($char);
                if (abs($prev_char_int - $curr_char_int) > 1) {
                    if ($consecutive_only == true) {
                        $duplicate_chars = 0; //Reset duplicate count.
                    }
                } else {
                    $duplicate_chars++;
                }
                $prev_char_int = $curr_char_int;
            }

            $duplicate_percent = (($duplicate_chars / strlen($value)) * 100);
            Debug::text('Duplicate Chars: ' . $duplicate_chars . ' Percent: ' . $duplicate_percent . ' Max Percent: ' . $max_duplicate_percent . ' Consec: ' . (int)$consecutive_only, __FILE__, __LINE__, __METHOD__, $this->verbosity);

            if ($duplicate_percent < $max_duplicate_percent) {
                return true;
            }

            $this->Error($label, $msg, $value);

            return false;
        }

        return true;
    }

    public function isAllowedWords($label, $value, $msg = null, $bad_words)
    {
        $words = explode(' ', $value);
        if (is_array($words)) {
            foreach ($words as $word) {
                foreach ($bad_words as $bad_word) {
                    if (strtolower($word) == strtolower($bad_word)) {
                        $this->Error($label, $msg, $value);

                        return false;
                    }
                }
            }
        }

        return true;
    }

    public function isAllowedValues($label, $value, $msg = null, $bad_words)
    {
        foreach ($bad_words as $bad_word) {
            if (strtolower($value) == strtolower($bad_word)) {
                $this->Error($label, $msg, $value);

                return false;
            }
        }

        return true;
    }

    public function isPhoneNumber($label, $value, $msg = null)
    {

        //Strip out all non-numeric characters.
        $phone = $this->stripNonNumeric($value);

        //Debug::text('Raw Phone: '. $value .' Phone: '. $phone, __FILE__, __LINE__, __METHOD__, $this->verbosity);

        if (strlen($phone) >= 6 and strlen($phone) <= 20 and preg_match('/^[0-9\(\)\-\.\+\ ]{6,20}$/i', $value)) {
            return true;
        }

        $this->Error($label, $msg, $value);

        return false;
    }

    public function stripNonNumeric($value)
    {
        $retval = preg_replace('/[^0-9]/', '', $value);
        return $retval;
    }

    public function isPostalCode($label, $value, $msg = null, $country = null, $province = null)
    {
        //Debug::text('Raw Postal Code: '. $value, __FILE__, __LINE__, __METHOD__, $this->verbosity);

        //Remove any spaces, keep dashes for US extended ZIP.
        $value = str_replace(array(' '), '', trim($value));

        $province = strtolower(trim($province));

        switch (strtolower(trim($country))) {
            case 'us':
                //US zip code
                if (preg_match('/^[0-9]{5}$/i', $value) or preg_match('/^[0-9]{5}\-[0-9]{4}$/i', $value)) {
                    if ($province != '') {
                        $province_postal_code_map = array(
                            'ak' => array('9950099929'),
                            'al' => array('3500036999'),
                            'ar' => array('7160072999', '7550275505'),
                            'az' => array('8500086599'),
                            'ca' => array('9000096199'),
                            'co' => array('8000081699'),
                            'ct' => array('0600006999'),
                            'dc' => array('2000020099', '2020020599'),
                            'de' => array('1970019999'),
                            'fl' => array('3200033999', '3410034999'),
                            'ga' => array('3000031999'),
                            'hi' => array('9670096798', '9680096899'),
                            'ia' => array('5000052999'),
                            'id' => array('8320083899'),
                            'il' => array('6000062999'),
                            'in' => array('4600047999'),
                            'ks' => array('6600067999'),
                            'ky' => array('4000042799', '4527545275'),
                            'la' => array('7000071499', '7174971749'),
                            'ma' => array('0100002799'),
                            'md' => array('2033120331', '2060021999'),
                            'me' => array('0380103801', '0380403804', '0390004999'),
                            'mi' => array('4800049999'),
                            'mn' => array('5500056799'),
                            'mo' => array('6300065899'),
                            'ms' => array('3860039799'),
                            'mt' => array('5900059999'),
                            'nc' => array('2700028999'),
                            'nd' => array('5800058899'),
                            'ne' => array('6800069399'),
                            'nh' => array('0300003803', '0380903899'),
                            'nj' => array('0700008999'),
                            'nm' => array('8700088499'),
                            'nv' => array('8900089899'),
                            'ny' => array('0040000599', '0639006390', '0900014999'),
                            'oh' => array('4300045999'),
                            'ok' => array('7300073199', '7340074999'),
                            'or' => array('9700097999'),
                            'pa' => array('1500019699'),
                            'ri' => array('0280002999', '0637906379'),
                            'sc' => array('2900029999'),
                            'sd' => array('5700057799'),
                            'tn' => array('3700038599', '7239572395'),
                            'tx' => array('7330073399', '7394973949', '7500079999', '8850188599'),
                            'ut' => array('8400084799'),
                            'va' => array('2010520199', '2030120301', '2037020370', '2200024699'),
                            'vt' => array('0500005999'),
                            'wa' => array('9800099499'),
                            'wi' => array('4993649936', '5300054999'),
                            'wv' => array('2470026899'),
                            'wy' => array('8200083199')
                        );

                        if (isset($province_postal_code_map[$province])) {
                            $zip5 = substr($value, 0, 5);
                            //Debug::text('Checking ZIP code range, short zip: '. $zip5, __FILE__, __LINE__, __METHOD__, $this->verbosity);
                            foreach ($province_postal_code_map[$province] as $postal_code_range) {
                                //Debug::text('Checking ZIP code range: '. $postal_code_range, __FILE__, __LINE__, __METHOD__, $this->verbosity);
                                if (($zip5 >= substr($postal_code_range, 0, 5)) and ($zip5 <= substr($postal_code_range, 5))) {
                                    return true;
                                }
                            }
                        } // else { //Debug::text('Postal Code does not match province!', __FILE__, __LINE__, __METHOD__, $this->verbosity);
                    } else {
                        return true;
                    }
                }
                break;
            case 'ca':
                //Canada postal code
                if (preg_match('/^[a-zA-Z]{1}[0-9]{1}[a-zA-Z]{1}[-]?[0-9]{1}[a-zA-Z]{1}[0-9]{1}$/i', $value)) {
                    if ($province != '') {
                        //Debug::text('Verifying postal code against province!', __FILE__, __LINE__, __METHOD__, $this->verbosity);
                        $province_postal_code_map = array(
                            'ab' => array('t'),
                            'bc' => array('v'),
                            'sk' => array('s'),
                            'mb' => array('r'),
                            'qc' => array('g', 'h', 'j'),
                            'on' => array('k', 'l', 'm', 'n', 'p'),
                            'nl' => array('a'),
                            'nb' => array('e'),
                            'ns' => array('b'),
                            'pe' => array('c'),
                            'nt' => array('x'),
                            'yt' => array('y'),
                            'nu' => array('x')
                        );

                        //Debug::Arr($province_postal_code_map[$province], 'Valid Postal Codes for Province', __FILE__, __LINE__, __METHOD__, $this->verbosity);
                        if (isset($province_postal_code_map[$province]) and in_array(substr(strtolower($value), 0, 1), $province_postal_code_map[$province])) {
                            return true;
                        } // else { //Debug::text('Postal Code does not match province!', __FILE__, __LINE__, __METHOD__, $this->verbosity);
                    } else {
                        return true;
                    }
                }
                break;
            default:
                //US
                if (preg_match('/^[0-9]{5}$/i', $value) or preg_match('/^[0-9]{5}\-[0-9]{4}$/i', $value)) {
                    return true;
                }

                //CA
                if (preg_match('/^[a-zA-Z]{1}[0-9]{1}[a-zA-Z]{1}[-]?[0-9]{1}[a-zA-Z]{1}[0-9]{1}$/i', $value)) {
                    return true;
                }

                //Other
                if (preg_match('/^[a-zA-Z0-9]{1,10}$/i', $value)) {
                    return true;
                }

                break;
        }

        $this->Error($label, $msg, $value);

        return false;
    }

    public function isEmail($label, $value, $msg = null)
    {
        //Debug::text('Raw Email: '. $value, __FILE__, __LINE__, __METHOD__, $this->verbosity);
        if (function_exists('filter_var') and filter_var($value, FILTER_VALIDATE_EMAIL) !== false) {
            return true;
        } elseif (preg_match('/^[\w\.\-\&\+]+\@[\w\.\-]+\.[a-z]{2,5}$/i', $value)) { //This Email regex is no where near correct, use PHP filter_var instead. - Allow 5 char suffixes to support .local domains.
            return true;
        }

        $this->Error($label, $msg, $value);

        return false;
    }

    public function isEmailAdvanced($label, $value, $msg = null, $error_level = true)
    {
        //Debug::text('Raw Email: '. $value, __FILE__, __LINE__, __METHOD__, $this->verbosity);

        if (Misc::isEmail($value, true, $error_level) === true) {
            return true;
        }

        $this->Error($label, $msg, $value);

        return false;
    }

    public function isIPAddress($label, $value, $msg = null)
    {
        //Debug::text('Raw IP: '. $value, __FILE__, __LINE__, __METHOD__, $this->verbosity);

        $ip = explode('.', $value);

        if (count($ip) == 4) {
            $valid = true;

            foreach ($ip as $block) {
                if (!is_numeric($block) or $block >= 255 or $block < 0) {
                    $valid = false;
                }
            }

            if ($valid == true) {
                return true;
            }
        }

        $this->Error($label, $msg, $value);

        return false;
    }

    public function isDate($label, $value, $msg = null)
    {
        //Because most epochs are stored as 4-byte integers, make sure we are within range.
        if ($value !== false and $value != '' and is_numeric($value) and $value >= -2147483648 and $value <= 2147483647) {
            $date = gmdate('U', $value);
            //Debug::text('Raw Date: '. $value .' Converted Value: '. $date, __FILE__, __LINE__, __METHOD__, $this->verbosity);

            if ($date == $value) {
                return true;
            }
        }

        $this->Error($label, $msg, $value);

        return false;
    }

    /*
     * String manipulation functions.
     */

    public function isSIN($label, $value, $msg = null, $country = null)
    {
        $sin = $this->stripNonNumeric($value);

        Debug::text('Validating SIN/SSN: ' . $value . ' Country: ' . $country, __FILE__, __LINE__, __METHOD__, $this->verbosity);

        $retval = false;
        switch (strtolower(trim($country))) {
            case 'ca':
                if ((is_numeric($sin) and $sin >= 100000000 and $sin <= 999999999)) {
                    $a_SIN = str_split($sin);

                    if (($a_SIN[1] *= 2) >= 10) {
                        $a_SIN[1] -= 9;
                    }
                    if (($a_SIN[3] *= 2) >= 10) {
                        $a_SIN[3] -= 9;
                    }
                    if (($a_SIN[5] *= 2) >= 10) {
                        $a_SIN[5] -= 9;
                    }
                    if (($a_SIN[7] *= 2) >= 10) {
                        $a_SIN[7] -= 9;
                    }

                    if ((array_sum($a_SIN) % 10) != 0) {
                        $retval = false;
                    } else {
                        $retval = true;
                    }
                } else {
                    $retval = false;
                }
                break;
            case 'us':
                if (strlen($sin) == 9) {
                    $retval = true;
                    /*
                    //Due to highgroup randomization, this can no longer validate SSNs properly.
                    global $cache;
                    require_once(Environment::getBasePath() .'classes/pear/Validate/US.php');

                    $ssn_high_groups = $cache->get( 'ssn_high_groups', 'validator' );
                    if ( $ssn_high_groups === FALSE ) {
                        Debug::text('Downloading SSN high groups...', __FILE__, __LINE__, __METHOD__, $this->verbosity);
                        $ssn_high_groups = Validate_US::ssnGetHighGroups();
                        $cache->save( $ssn_high_groups, 'ssn_high_groups', 'validator' );
                    }

                    if ( is_array( $ssn_high_groups ) AND count($ssn_high_groups) > 1 ) {
                        $retval = Validate_US::SSN( $sin, $ssn_high_groups );
                    } else {
                        Debug::text('NOT using full SSN validation...', __FILE__, __LINE__, __METHOD__, $this->verbosity);
                        if ( strlen($sin) == 9 ) {
                            $retval = TRUE;
                        }
                    }
                    */
                } else {
                    $retval = false;
                }
                break;
            default:
                //Allow all foriegn countries to utilize
                $retval = self::isLength($label, $value, $msg, 1, 255);
                break;
        }

        if ($retval === true) {
            return true;
        }

        Debug::text('Invalid SIN/SSN: ' . $value . ' Country: ' . $country, __FILE__, __LINE__, __METHOD__, $this->verbosity);
        $this->Error($label, $msg, $value);

        return false;
    }

    public function isLength($label, $value, $msg = null, $min = 1, $max = 255)
    {
        $len = strlen($value);

        //Debug::text('Value: '. $value .' Length: '. $len .' Min: '. $min .' Max: '. $max, __FILE__, __LINE__, __METHOD__, $this->verbosity);

        if ($len < $min or $len > $max) {
            $this->Error($label, $msg, $value);

            return false;
        }

        return true;
    }

    public function stripNon32bitInteger($value)
    {
        if ($value > 2147483647 or $value < -2147483648) {
            return 0;
        }

        return $value;
    }

    public function stripSpaces($value)
    {
        return str_replace(' ', '', trim($value));
    }

    public function stripNumeric($value)
    {
        $retval = preg_replace('/[0-9]/', '', $value);
        return $retval;
    }

    public function stripNonAlphaNumeric($value)
    {
        $retval = preg_replace('/[^A-Za-z0-9]/', '', $value);

        //Debug::Text('Alpha Numeric String:'. $retval, __FILE__, __LINE__, __METHOD__, $this->verbosity);

        return $retval;
    }

    //Suitable for passing to parseTimeUnit() after.

    public function stripNonFloat($value)
    {
        //Don't use TTi18n::parseFloat() here, as if we are going to be doing that we should do it as early as possible to the user input, like in setObjectFromArray()
        //  We do need to check if the value passed in is already cast to float/int and just accept it in that case.
        //    Because in other locales preg_match() casts $value to a string, which means decimal could become a comma, then it won't match.
        if (is_float($value) === true or is_int($value) === true) {
            return $value;
        } else {
            $retval = preg_replace('/[^-0-9\.]/', '', $value);
        }

        //Debug::Text('Float String:'. $retval, __FILE__, __LINE__, __METHOD__, $this->verbosity);

        return $retval;
    }

    public function stripNonTimeUnit($value)
    {
        $retval = preg_replace('/[^-0-9\.:]/', '', $value);

        //Debug::Text('Float String:'. $retval, __FILE__, __LINE__, __METHOD__, $this->verbosity);

        return $retval;
    }

    public function stripHTML($value)
    {
        return strip_tags($value);
    }

    public function escapeHTML($value)
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    public function purifyHTML($value)
    {
        global $config_vars;

        //Require inside this function as HTMLPurifier is a huge file.
        require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'HTMLPurifier' . DIRECTORY_SEPARATOR . 'HTMLPurifier.standalone.php');

        $config = HTMLPurifier_Config::createDefault();
        if (isset($config_vars['cache']['enable']) and $config_vars['cache']['enable'] == true
            and $config_vars['cache']['dir'] != '' and is_writable($config_vars['cache']['dir'])
        ) {
            $config->set('Cache.SerializerPath', $config_vars['cache']['dir']);
            //Debug::Text('Caching HTMLPurifier...', __FILE__, __LINE__, __METHOD__, $this->verbosity);
        } else {
            $config->set('Cache.DefinitionImpl', null);
            Debug::Text('NOT caching HTMLPurifier...', __FILE__, __LINE__, __METHOD__, $this->verbosity);
        }

        $purifier = new HTMLPurifier($config);
        return $purifier->purify($value);
    }

    /*
     * Class standard functions.
     */

    public function getPhoneNumberAreaCode($value)
    {
        $phone_number = $this->stripNonNumeric($value);
        if (strlen($phone_number) > 7) {
            $retval = substr($phone_number, -10, 3); //1 555 555 5555
            return $retval;
        }

        return false;
    }

    public function varReplace($string, $var_array)
    {
        //var_array = arary('var1' => 'blah1', 'var2' => 'blah2');
        $keys = array();
        $values = array();
        if (is_array($var_array) and count($var_array) > 0) {
            foreach ($var_array as $key => $value) {
                $keys[] = '#' . $key;
                $values[] = $value;
            }
        }

        $retval = str_replace($keys, $values, $string);

        return $retval;
    }

    public function getValidateOnly()
    {
        return $this->validate_only;
    }

    //Returns both Errors and Warnings combined.

    public function setValidateOnly($validate_only)
    {
        $this->validate_only = $validate_only;
    }

    //Merges all errors/warnings from the passed $validator object to this one.

    public function getErrorsAndWarningsArray()
    {
        return array('errors' => $this->errors, 'warnings' => $this->warnings);
    }

    public function merge($validator)
    {
        if (is_object($validator) and $validator->isValid() == false) {
            $this->errors = array_merge($this->errors, $validator->getErrorsArray());
            $this->num_errors += count($validator->getErrorsArray());

            $this->warnings = array_merge($this->warnings, $validator->getWarningsArray());
            $this->num_warnings += count($validator->getWarningsArray());
        }

        return true;
    }

    public function getErrorsArray()
    {
        return $this->errors;
    }

    public function getErrors()
    {
        if (count($this->errors) > 0) {
            $output = "<ol>\n";
            foreach ($this->errors as $label) {
                foreach ($label as $msg) {
                    $output .= '<li>' . $msg . ".</li>";
                }
            }
            $output .= "</ol>\n";
            return $output;
        }

        return false;
    }

    public function getTextErrors($numbered_list = true)
    {
        if (count($this->errors) > 0) {
            $output = '';
            $number_prefix = null;
            $i = 1;
            foreach ($this->errors as $label) {
                foreach ($label as $msg) {
                    if ($numbered_list == true) {
                        $number_prefix = $i . '. ';
                    }
                    $output .= $number_prefix . $msg . "\n";
                }

                $i++;
            }
            return $output;
        }

        return false;
    }

    final public function isValid($label = null)
    {
        if ($this->isError($label) or $this->isWarning($label)) {
            return false;
        }

        return true;
    }

    final public function isError($label = null)
    {
        if ($label != null) {
            return $this->hasError($label);
        } elseif ($this->num_errors > 0) {
            Debug::Arr($this->errors, 'Errors', __FILE__, __LINE__, __METHOD__, $this->verbosity);
            return true;
        }

        return false;
    }

    public function hasError($label)
    {
        if (in_array($label, array_keys($this->errors))) {
            return true;
        }

        return false;
    }

    final public function isWarning($label = null)
    {
        if ($label != null) {
            return $this->hasWarning($label);
        } elseif ($this->num_warnings > 0) {
            Debug::Arr($this->warnings, 'Warnings', __FILE__, __LINE__, __METHOD__, $this->verbosity);
            return true;
        }

        return false;
    }

    //
    // Warning functions below here
    //

    public function hasWarning($label)
    {
        if (in_array($label, array_keys($this->warnings))) {
            return true;
        }

        return false;
    }

    public function resetErrors()
    {
        unset($this->errors);
        $this->num_errors = 0;

        return true;
    }

    public function getWarningsArray()
    {
        return $this->warnings;
    }

    public function resetWarnings()
    {
        unset($this->warnings);
        $this->num_warnings = 0;

        return true;
    }

    public function Warning($label, $msg, $value = '')
    {
        Debug::text('Validation Warning: Label: ' . $label . ' Value: "' . $value . '" Msg: ' . $msg, __FILE__, __LINE__, __METHOD__, $this->verbosity);

        if ($label != '') {
            $this->warnings[$label][] = $msg;

            $this->num_warnings++;

            return true;
        }

        return false;
    }
}
