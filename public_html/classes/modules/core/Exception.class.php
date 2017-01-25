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
class DBError extends Exception
{
    public function __construct($e, $code = 'DBError')
    {
        global $db, $skip_db_error_exception;

        if (isset($skip_db_error_exception) and $skip_db_error_exception === true) { //Used by system_check script.
            return true;
        }

        //If we couldn't connect to the database, this method may not exist.
        if (isset($db) and is_object($db) and method_exists($db, 'FailTrans')) {
            $db->FailTrans();
        }

        //print_r($e);
        //adodb_pr($e);

        Debug::Text('Begin Exception...', __FILE__, __LINE__, __METHOD__, 10);
        Debug::Arr(Debug::backTrace(), ' BackTrace: ', __FILE__, __LINE__, __METHOD__, 10);

        //Log database error
        if ($e->getMessage() != '') {
            if (stristr($e->getMessage(), 'statement timeout') !== false) {
                $code = 'DBTimeout';
            } elseif (stristr($e->getMessage(), 'unique constraint') !== false) {
                $code = 'DBUniqueConstraint';
            } elseif (stristr($e->getMessage(), 'invalid byte sequence') !== false) {
                $code = 'DBInvalidByteSequence';
            } elseif (stristr($e->getMessage(), 'could not serialize') !== false) {
                $code = 'DBSerialize';
            } elseif (stristr($e->getMessage(), 'deadlock') !== false or stristr($e->getMessage(), 'concurrent') !== false) {
                $code = 'DBDeadLock';
            } elseif (stristr($e->getMessage(), 'server has gone away') !== false or stristr($e->getMessage(), 'closed the connection unexpectedly') !== false or stristr($e->getMessage(), 'execution was interrupted') !== false) { //Connection was lost after it was initially made.
                $code = 'DBConnectionLost';
            } elseif (stristr($e->getMessage(), 'No space left on device') !== false) { //Unrecoverable error, set down_for_maintenance so server admin can investigate?
                $code = 'DBNoSpaceOnDevice';
            } elseif (stristr($e->getMessage(), 'connection failed') !== false) { //Connection could not be established to begin with.
                $code = 'DBConnectionFailed';
            }
            Debug::Text('Code: ' . $code . '(' . $e->getCode() . ') Message: ' . $e->getMessage(), __FILE__, __LINE__, __METHOD__, 10);
        }

        if ($e->getTrace() != '') {
            ob_start(); //ADDBO_BACKTRACE() always insists on printing its output and returning it, so capture the output and drop it, so we can use the $e variable instead.
            $e = adodb_backtrace($e->getTrace(), 9999, 0, false);
            ob_end_clean();
            Debug::Arr($e, 'Exception...', __FILE__, __LINE__, __METHOD__, 10);
        }

        Debug::Text('End Exception...', __FILE__, __LINE__, __METHOD__, 10);

        if (!defined('UNIT_TEST_MODE') or UNIT_TEST_MODE === false) { //When in unit test mode don't exit/redirect
            //Dump debug buffer.
            Debug::Display();
            Debug::writeToLog();

            //Prevent PHP error by checking to make sure output buffer exists before clearing it.
            if (ob_get_level() > 0) {
                ob_flush();
                ob_clean();
            }

            if (defined('FAIRNESS_JSON_API')) {
                switch (strtolower($code)) {
                    case 'dbtimeout':
                        $description = TTi18n::getText('%1 database query has timed-out, if you were trying to run a report it may be too large, please narrow your search criteria and try again.', array(APPLICATION_NAME));
                        break;
                    case 'dbuniqueconstraint':
                    case 'dbdeadlock':
                        $description = TTi18n::getText('%1 has detected a duplicate request, this may be due to double-clicking a button or a poor internet connection.', array(APPLICATION_NAME));
                        break;
                    case 'dbinvalidbytesequence':
                        $description = TTi18n::getText('%1 has detected invalid UTF8 characters, if you are attempting to use non-english characters, they may be invalid.', array(APPLICATION_NAME));
                        break;
                    case 'dbserialize':
                        $description = TTi18n::getText('%1 has detected a duplicate request running at the exact same time, please try your request again in a couple minutes.', array(APPLICATION_NAME));
                        break;
                    case 'dbnospaceondevice':
                        $description = TTi18n::getText('%1 has detected a database error, please contact technical support immediately.', array(APPLICATION_NAME));
                        break;
                    case 'dberror':
                    case 'dbconnectionfailed':
                        $description = TTi18n::getText('%1 is unable to connect to its database, please make sure that the database service on your own local %1 server has been started and is running. If you are unsure, try rebooting your server.', array(APPLICATION_NAME));
                        break;
                    case 'dbinitialize':
                        $description = TTi18n::getText('%1 database has not been initialized yet, please run the installer again and follow the on screen instructions. <a href="%2">Click here to run the installer now.</a>', array(APPLICATION_NAME, Environment::getBaseURL() . '/install/install.php?external_installer=1'));
                        break;
                    default:
                        $description = TTi18n::getText('%1 experienced a general error, please contact technical support.', array(APPLICATION_NAME));
                        break;
                }

                $obj = new APIAuthentication();
                echo json_encode($obj->returnHandler(false, 'EXCEPTION', $description));
                exit;
            } elseif (PHP_SAPI == 'cli') {
                //Don't attempt to redirect
                echo "Fatal Exception: Code: " . $code . "... Exiting with error code 254!\n";
                exit(254);
            } else {
                global $config_vars;
                if (isset($config_vars['other']['installer_enabled']) and $config_vars['other']['installer_enabled'] == 1 and in_array(strtolower($code), array('dberror', 'dbinitialize'))) {
                    Redirect::Page(URLBuilder::getURL(array(), Environment::getBaseURL() . 'html5/index.php?installer=1&disable_db=1&external_installer=0#!m=Install&a=license&external_installer=0'));
                }
                exit;
            }
        }
    }
}

/**
 * @package Core
 */
class GeneralError extends Exception
{
    public function __construct($message)
    {
        global $db;

        //debug_print_backtrace();

        //If we couldn't connect to the database, this method may not exist.
        if (isset($db) and is_object($db) and method_exists($db, 'FailTrans')) {
            $db->FailTrans();
        }

        /*
        echo "======================================================================<br>\n";
        echo "EXCEPTION!<br>\n";
        echo "======================================================================<br>\n";
        echo "<b>Error message: </b>".$message ."<br>\n";
        echo "<b>Error code: </b>".$this->getCode()."<br>\n";
        echo "<b>Script Name: </b>".$this->getFile()."<br>\n";
        echo "<b>Line Number: </b>".$this->getLine()."<br>\n";
        echo "======================================================================<br>\n";
        echo "EXCEPTION!<br>\n";
        echo "======================================================================<br>\n";
        */

        Debug::Text('EXCEPTION: Code: ' . $this->getCode() . ' Message: ' . $message . ' File: ' . $this->getFile() . ' Line: ' . $this->getLine(), __FILE__, __LINE__, __METHOD__, 10);
        Debug::Arr(Debug::backTrace(), ' BackTrace: ', __FILE__, __LINE__, __METHOD__, 10);

        if (!defined('UNIT_TEST_MODE') or UNIT_TEST_MODE === false) { //When in unit test mode don't exit/redirect
            //Dump debug buffer.
            Debug::Display();
            Debug::writeToLog();
            ob_flush();
            ob_clean();

            if (defined('FAIRNESS_JSON_API')) {
                global $obj;
                echo json_encode($obj->returnHandler(false, 'EXCEPTION', TTi18n::getText('%1 experienced a general error, please contact technical support.', array(APPLICATION_NAME))));
                exit;
            } else {
                Redirect::Page(URLBuilder::getURL(array('exception' => 'GeneralError'), Environment::getBaseURL() . 'DownForMaintenance.php'));
                exit;
            }
        }
    }
}
