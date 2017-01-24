<?php
/*********************************************************************************
 * This file is part of "Fairness", a Payroll and Time Management program.
 * Fairness is Copyright 2013 Aydan Coskun (aydan.ayfer.coskun@gmail.com)
 * Portions of this software are Copyright of T i m e T r e x Software Inc.
 * Fairness is a fork of "T i m e T r e x Workforce Management" Software.
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


/**
 * @package Core
 */
class Debug
{
        public static $tidy_obj = null;            //Enable/Disable debug printing.
    protected static $enable = false;            //Display debug info with a verbosity level equal or lesser then this.
    protected static $verbosity = 5;        //Enable/Disable output buffering.
    protected static $buffer_output = true;        //Output buffer.
    protected static $debug_buffer = null;        //Enable/Disable tidying of output
    protected static $enable_tidy = false;    //Enable/Disable displaying of debug output
    protected static $enable_display = false;        //Enable/Disable logging of debug output
    protected static $enable_log = false;        //Max line size in characters. This is used to break up long lines.
    protected static $max_line_size = 200;    //Max buffer size in lines. **Syslog can't handle much more than 1000.
    protected static $max_buffer_size = 1000;            //Unique identifier for the debug buffer.
    protected static $buffer_id = null;            //Count number of PHP errors so we can automatically email the log.
protected static $php_errors = 0;            //Current buffer size in lines.
protected static $buffer_size = 0;

    public static function getEnable()
    {
        return self::$enable;
    }

    public static function setEnable($bool)
    {
        self::setBufferID();
        self::$enable = $bool;
    }

    public static function setBufferID()
    {
        if (self::$buffer_id == null) {
            self::$buffer_id = uniqid();
        }
    }

    public static function setBufferOutput($bool)
    {
        self::$buffer_output = $bool;
    }

    public static function getEnableTidy()
    {
        return self::$enable_tidy;
    }

    public static function setEnableTidy($bool)
    {
        self::$enable_tidy = $bool;
    }

    public static function getEnableDisplay()
    {
        return self::$enable_display;
    }

    public static function setEnableDisplay($bool)
    {
        self::$enable_display = $bool;
    }

    public static function getEnableLog()
    {
        return self::$enable_log;
    }

    public static function setEnableLog($bool)
    {
        self::$enable_log = $bool;
    }

    public static function profileTimers($profile_obj)
    {
        if (!is_object($profile_obj)) {
            return false;
        }

        ob_start();
        $profile_obj->printTimers();
        $ob_contents = ob_get_contents();
        ob_end_clean();

        return $ob_contents;
    }

    public static function ErrorHandler($error_number, $error_str, $error_file, $error_line)
    {
        //Only handle errors included in the error_reporting()
        if ((error_reporting() & $error_number)) { //Bitwise operator.
            // This error code is not included in error_reporting
            switch ($error_number) {
                case E_USER_ERROR:
                    $error_name = 'FATAL';
                    break;
                case E_USER_WARNING:
                case E_WARNING:
                    $error_name = 'WARNING';
                    break;
                case E_USER_NOTICE:
                case E_NOTICE:
                    $error_name = 'NOTICE';
                    break;
                case E_STRICT:
                    $error_name = 'STRICT';

                    //Don't show STRICT errors when using the legacy HTML interface with PHP v5.4
                    if (defined('FAIRNESS_AMF_API') == false and defined('FAIRNESS_JSON_API') == false and defined('FAIRNESS_SOAP_API') == false) {
                        return true;
                    }
                    break;
                case E_DEPRECATED:
                    $error_name = 'DEPRECATED';
                    break;
                default:
                    $error_name = 'UNKNOWN';
            }

            $error_name .= '(' . $error_number . ')';

            $text = 'PHP ERROR - ' . $error_name . ': ' . $error_str . ' File: ' . $error_file . ' Line: ' . $error_line;

            self::$php_errors++;

            if (PHP_SAPI != 'cli' and function_exists('apache_request_headers')) {
                self::Arr(apache_request_headers(), 'Raw Request Headers: ', $error_file, $error_line, __METHOD__, 1);
            }

            global $HTTP_RAW_POST_DATA;
            if ($HTTP_RAW_POST_DATA != '') {
                self::Arr($HTTP_RAW_POST_DATA, 'Raw POST Request: ', $error_file, $error_line, __METHOD__, 1);
            }

            self::Text($text, $error_file, $error_line, __METHOD__, 1);
            self::Text(self::backTrace(), $error_file, $error_line, __METHOD__, 1);
        }

        return false; //Let the standard PHP error handler work as well.
    }

    public static function Arr($array, $text = null, $file = __FILE__, $line = __LINE__, $method = __METHOD__, $verbosity = 9)
    {
        if ($verbosity > self::getVerbosity() or self::$enable == false) {
            return false;
        }

        if (empty($method)) {
            $method = '[Function]';
        }

        $text_arr = array();
        $text_arr[] = 'DEBUG [L' . str_pad($line, 4, 0, STR_PAD_LEFT) . '] [' . str_pad(self::getExecutionTime(), 5, 0, STR_PAD_LEFT) . 'ms] Array: ' . $method . '(): ' . $text . "\n";
        $text_arr = array_merge($text_arr, self::splitInput(self::varDump($array), null, "\n"));
        $text_arr[] = "\n";

        if (self::$buffer_output == true) {
            foreach ($text_arr as $text_line) {
                self::$debug_buffer[] = array($verbosity, $text_line);
                self::$buffer_size++;
                self::handleBufferSize($line, $method);
            }
        } else {
            if (self::$enable_display == true) {
                foreach ($text_arr as $text_line) {
                    echo $text_line;
                }
            } elseif (OPERATING_SYSTEM != 'WIN' and self::$enable_log == true) {
                foreach ($text_arr as $text_line) {
                    syslog(LOG_DEBUG, $text_line);
                }
            }
        }

        return true;
    }

    //	Three primary log types: $log_types = array( 0 => 'debug', 1 => 'client', 2 => 'timeclock' );

    public static function getVerbosity()
    {
        return self::$verbosity;
    }

    public static function setVerbosity($level)
    {
        global $db;

        self::$verbosity = $level;

        if (is_object($db) and $level == 11) {
            $db->debug = true;
        }
    }

    //Used to add timing to each debug call.

    public static function getExecutionTime()
    {
        return ceil(((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000));
    }

    //Splits long debug lines or array dumps to prevent syslog overflows.
    public static function splitInput($text, $prefix = null, $suffix = null)
    {
        if (strlen($text) > self::$max_line_size) {
            $retarr = array();

            $lines = explode("\n", $text); //Split on newlines first.
            foreach ($lines as $line) {
                $split_lines = str_split($line, self::$max_line_size); //Split on long lines next.
                foreach ($split_lines as $split_line) {
                    $retarr[] = $prefix . $split_line . $suffix;
                }
            }
            unset($lines, $line, $split_lines, $split_line);
        } else {
            $retarr = array($prefix . $text . $suffix); //Always returns an array.
        }

        return $retarr;
    }

    public static function varDump($array)
    {
        ob_start();
        var_dump($array); //Xdebug may interfere with this and cause it to not display all the data...
        //print_r($array);
        $ob_contents = ob_get_contents();
        ob_end_clean();

        return $ob_contents;
    }

    public static function handleBufferSize($line = null, $method = null)
    {
        //When buffer exceeds maximum size, write it to the log and clear it.
        //This will affect displaying large buffers though, but otherwise we may run out of memory.
        //If we detect PHP errors, buffer up to 10x the maximum size to try and capture those errors.
        if ((self::$php_errors == 0 and self::$buffer_size >= self::$max_buffer_size) or (self::$php_errors > 0 and self::$buffer_size >= (self::$max_buffer_size * 100))) {
            self::$debug_buffer[] = array(1, 'DEBUG [L' . str_pad($line, 4, 0, STR_PAD_LEFT) . '] [' . str_pad(self::getExecutionTime(), 5, 0, STR_PAD_LEFT) . 'ms]: ' . $method . '(): Maximum debug buffer size of: ' . self::$max_buffer_size . ' reached. Writing out buffer before continuing... Buffer ID: ' . self::$buffer_id . "\n");
            self::writeToLog();
            self::clearBuffer();
            self::$debug_buffer[] = array(1, 'DEBUG [L' . str_pad($line, 4, 0, STR_PAD_LEFT) . '] [' . str_pad(self::getExecutionTime(), 5, 0, STR_PAD_LEFT) . 'ms]: ' . $method . '(): Continuing debug output from Buffer ID: ' . self::$buffer_id . "\n");

            return true;
        }

        return false;
    }

    public static function writeToLog()
    {
        if (self::$enable_log == true and self::$buffer_output == true) {
            global $config_vars;

            $eol = "\n";

            if (is_array(self::$debug_buffer)) {
                $output = $eol . '---------------[ ' . @date('d-M-Y G:i:s O') . ' [' . $_SERVER['REQUEST_TIME_FLOAT'] . '] (PID: ' . getmypid() . ') ]---------------' . $eol;

                foreach (self::$debug_buffer as $arr) {
                    if ($arr[0] <= self::getVerbosity()) {
                        $output .= $arr[1];
                    }
                }

                $output .= '---------------[ ' . @date('d-M-Y G:i:s O') . ' [' . microtime(true) . '] (PID: ' . getmypid() . ') ]---------------' . $eol;

                if (isset($config_vars['debug']['enable_syslog']) and $config_vars['debug']['enable_syslog'] == true and OPERATING_SYSTEM != 'WIN') {
                    //If using rsyslog, need to set:
                    //$MaxMessageSize 256000 #Above ModuleLoad imtcp
                    openlog(self::getSyslogIdent(), 11, self::getSyslogFacility(0)); //11 = LOG_PID | LOG_NDELAY | LOG_CONS
                    syslog(self::getSyslogPriority(0), $output); //Used to strip_tags output, but that was likely causing problems with SQL queries with >= and <= in them.
                    closelog();
                } elseif (is_writable($config_vars['path']['log'])) {
                    $file_name = $config_vars['path']['log'] . DIRECTORY_SEPARATOR . 'fairness.log';
                    $fp = @fopen($file_name, 'a');
                    @fwrite($fp, $output); //Used to strip_tags output, but that was likely causing problems with SQL queries with >= and <= in them.
                    @fclose($fp);
                    unset($output);
                }

                return true;
            }
        }

        return false;
    }

    public static function getSyslogIdent($extra_ident = null, $company_name = null)
    {
        global $config_vars, $current_company;

        $suffix = null;
        if ($company_name != '') {
            $suffix = $company_name;
        } elseif (isset($current_company) and is_object($current_company)) {
            $suffix = $current_company->getShortName();
        } else {
            $suffix = 'System';
        }

        if (isset($config_vars['debug']['syslog_ident']) and $config_vars['debug']['syslog_ident'] != '') {
            $retval = $config_vars['debug']['syslog_ident'] . '-' . $suffix . $extra_ident;
        } else {
            $retval = APPLICATION_NAME . '-' . $suffix . $extra_ident;
        }

        return preg_replace('/[^a-zA-Z0-9-]/', '', escapeshellarg($retval)); //This will remove spaces.
    }

    public static function getSyslogFacility($log_type = 0)
    {
        global $config_vars;
        if (isset($config_vars['debug']['syslog_facility']) and $config_vars['debug']['syslog_facility'] != '') {
            $facility_arr = explode(',', $config_vars['debug']['syslog_facility']);
            if (is_array($facility_arr) and isset($facility_arr[(int)$log_type])) {
                return (is_numeric($facility_arr[(int)$log_type])) ? $facility_arr[(int)$log_type] : constant(trim($facility_arr[(int)$log_type]));
            }
        }

        return LOG_LOCAL7; //Default
    }

    public static function getSyslogPriority($log_type = 0)
    {
        global $config_vars;

        if (isset($config_vars['debug']['syslog_priority']) and $config_vars['debug']['syslog_priority'] != '') {
            $priority_arr = explode(',', $config_vars['debug']['syslog_priority']);
            if (is_array($priority_arr) and isset($priority_arr[(int)$log_type])) {
                return (is_numeric($priority_arr[(int)$log_type])) ? $priority_arr[(int)$log_type] : constant(trim($priority_arr[(int)$log_type]));
            }
        }

        return LOG_DEBUG; //Default
    }

    public static function clearBuffer()
    {
        self::$debug_buffer = null;
        self::$buffer_size = 0;
        return true;
    }

    public static function Text($text = null, $file = __FILE__, $line = __LINE__, $method = __METHOD__, $verbosity = 9)
    {
        if ($verbosity > self::getVerbosity() or self::$enable == false) {
            return false;
        }

        if (empty($method)) {
            $method = '[Function]';
        }

        //If text is too long, split it into an array.
        $text_arr = self::splitInput($text, 'DEBUG [L' . str_pad($line, 4, 0, STR_PAD_LEFT) . '] [' . str_pad(self::getExecutionTime(), 5, 0, STR_PAD_LEFT) . 'ms]: ' . $method . '(): ', "\n");

        if (self::$buffer_output == true) {
            foreach ($text_arr as $text_line) {
                self::$debug_buffer[] = array($verbosity, $text_line);
                self::$buffer_size++;
                self::handleBufferSize($line, $method);
            }
        } else {
            if (self::$enable_display == true) {
                foreach ($text_arr as $text_line) {
                    echo $text_line;
                }
            } elseif (OPERATING_SYSTEM != 'WIN' and self::$enable_log == true) {
                foreach ($text_arr as $text_line) {
                    syslog(LOG_DEBUG, $text_line);
                }
            }
        }

        return true;
    }

    public static function backTrace()
    {
        //ob_start();
        //debug_print_backtrace();
        //$ob_contents = ob_get_contents();
        //ob_end_clean();
        //return $ob_contents;

        $retval = '';
        $trace_arr = debug_backtrace();
        if (is_array($trace_arr)) {
            $i = 0;
            foreach ($trace_arr as $trace_line) {
                if (isset($trace_line['class']) and isset($trace_line['type'])) {
                    $class = $trace_line['class'] . $trace_line['type'];
                } else {
                    $class = null;
                }

                if (!isset($trace_line['file'])) {
                    $trace_line['file'] = 'N/A';
                }

                if (!isset($trace_line['line'])) {
                    $trace_line['line'] = 'N/A';
                }

                if (isset($trace_line['args']) and is_array($trace_line['args'])) {
                    $args = array();
                    foreach ($trace_line['args'] as $arg) {
                        if (is_array($arg)) {
                            if (self::getVerbosity() == 11) {
                                $args[] = self::varDump($arg);
                            } else {
                                //Don't display the entire array is it polutes the log and is too large for syslog anyways.
                                $args[] = 'Array(' . count($arg) . ')';
                            }
                        } elseif (is_object($arg)) {
                            if (self::getVerbosity() == 11) {
                                $args[] = self::varDump($arg);
                            } else {
                                //Don't display the entire array is it polutes the log and is too large for syslog anyways.
                                $args[] = 'Object(' . get_class($arg) . ')';
                            }
                        } else {
                            $args[] = $arg;
                        }
                    }
                }
                $retval .= '#' . $i . '.' . $class . $trace_line['function'] . '(' . implode(', ', $args) . ') ' . $trace_line['file'] . ':' . $trace_line['line'] . "\n";
                $i++;
            }
        }
        unset($trace_arr, $trace_line, $args);

        return $retval;
    }

    public static function Shutdown()
    {
        $error = error_get_last();
        if ($error !== null and isset($error['type']) and $error['type'] == 1) { //Only trigger fatal errors on shutdown.
            self::$php_errors++;
            self::Text('PHP ERROR - FATAL(' . $error['type'] . '): ' . $error['message'] . ' File: ' . $error['file'] . ' Line: ' . $error['line'], $error['file'], $error['line'], __METHOD__, 1);

            if (defined('FAIRNESS_API') and FAIRNESS_API == true) { //Only when a fatal error occurs.
                global $amf_message_id;
                if ($amf_message_id != '') {
                    $progress_bar = new ProgressBar();
                    $progress_bar->error($amf_message_id, TTi18n::getText('ERROR: Operation cannot be completed.'));
                    unset($progress_bar);
                }
            }
        }

        if (self::$php_errors > 0) {
            self::Text('Detected PHP errors (' . self::$php_errors . '), emailing log...');
            self::Text('---------------[ ' . @date('d-M-Y G:i:s O') . ' [' . microtime(true) . '] (PID: ' . getmypid() . ') ]---------------');

            if ($error !== null) { //Fatal error, write to log once more as this won't be called automatically.
                self::writeToLog();
            }
        }

        return true;
    }

    public static function Display()
    {
        if (self::$enable_display == true and self::$buffer_output == true) {
            $output = self::getOutput();

            if (function_exists('memory_get_usage')) {
                $memory_usage = memory_get_usage();
            } else {
                $memory_usage = 'N/A';
            }

            if (strlen($output) > 0) {
                echo "\nDebug Buffer\n";
                echo "============================================================================\n";
                echo "Memory Usage: " . $memory_usage . " Buffer Size: " . self::$buffer_size . "\n";
                echo "----------------------------------------------------------------------------\n";
                echo $output;
                echo "============================================================================\n";
            }

            return true;
        }

        return false;
    }

    public static function getOutput()
    {
        $output = null;
        if (count(self::$debug_buffer) > 0) {
            foreach (self::$debug_buffer as $arr) {
                $verbosity = $arr[0];
                $text = $arr[1];

                if ($verbosity <= self::getVerbosity()) {
                    $output .= $text;
                }
            }

            return $output;
        }

        return false;
    }

    public static function Tidy()
    {
        if (self::$enable_tidy == true) {
            $tidy_config = Environment::getBasePath() . '/includes/tidy.conf';

            self::$tidy_obj = tidy_parse_string(ob_get_contents(), $tidy_config);

            //erase the output buffer
            ob_clean();

            //tidy_clean_repair();
            self::$tidy_obj->cleanRepair();

            echo self::$tidy_obj;
        }
        return true;
    }

    public static function DisplayTidyErrors()
    {
        if (self::$enable_tidy == true
            and (tidy_error_count(self::$tidy_obj) > 0 or tidy_warning_count(self::$tidy_obj) > 0)
        ) {
            echo "\nTidy Output<\n";
            echo "============================================================================\n";
            echo htmlentities(self::$tidy_obj->errorBuffer);
            echo "============================================================================\n";
        }
    }
}
