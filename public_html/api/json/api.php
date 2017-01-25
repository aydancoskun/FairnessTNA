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

define('FAIRNESS_JSON_API', true);
if (isset($_GET['disable_db']) and $_GET['disable_db'] == 1) {
    $disable_database_connection = true;
}
//Add fairness.ini.php setting to enable/disable the API. Make an entire [API] section.
require_once('../../includes/global.inc.php');
require_once('../../includes/API.inc.php');
Header('Content-Type: application/json; charset=UTF-8'); //Make sure content type is not text/HTML to help avoid XSS.

function getJSONError()
{
    $retval = false;

    if (function_exists('json_last_error')) { //Handle PHP v5.3 and older.
        switch (json_last_error()) {
            case JSON_ERROR_NONE:
                break;
            case JSON_ERROR_DEPTH:
                $retval = 'Maximum stack depth exceeded';
                break;
            case JSON_ERROR_STATE_MISMATCH:
                $retval = 'Underflow or the modes mismatch';
                break;
            case JSON_ERROR_CTRL_CHAR:
                $retval = 'Unexpected control character found';
                break;
            case JSON_ERROR_SYNTAX:
                $retval = 'Syntax error, malformed JSON';
                break;
            case JSON_ERROR_UTF8:
                $retval = 'Malformed UTF-8 characters, possibly incorrectly encoded';
                break;
            default:
                $retval = 'Unknown error';
                break;
        }
    }

    return $retval;
}

function unauthenticatedInvokeService($class_name, $method, $arguments, $message_id, $api_auth)
{
    TTi18n::chooseBestLocale(); //Make sure we set the locale as best we can when not logged in

    Debug::text('Handling UNAUTHENTICATED JSON Call To API Factory: ' . $class_name . ' Method: ' . $method . ' Message ID: ' . $message_id, __FILE__, __LINE__, __METHOD__, 10);
    $valid_unauthenticated_classes = getUnauthenticatedAPIClasses();
    if ($class_name != '' and in_array($class_name, $valid_unauthenticated_classes) and class_exists($class_name)) {
        $obj = new $class_name;
        if (method_exists($obj, 'setAMFMessageID')) {
            $obj->setAMFMessageID($message_id); //Sets AMF message ID so progress bar continues to work.
        }
        if ($method != '' and method_exists($obj, $method)) {
            $retval = call_user_func_array(array($obj, $method), (array)$arguments);
            //If the function returns anything else, encode into JSON and return it.
            //Debug::Arr($retval, 'Retval: ', __FILE__, __LINE__, __METHOD__, 10);
            echo json_encode($retval);
            $json_error = getJSONError();
            if ($json_error !== false) {
                Debug::Arr($retval, 'ERROR: JSON: ' . $json_error, __FILE__, __LINE__, __METHOD__, 10);
                echo json_encode($api_auth->returnHandler(false, 'EXCEPTION', 'ERROR: JSON: ' . $json_error));
            }
        } else {
            $validator = TTnew('Validator');
            Debug::text('Method: ' . $method . ' does not exist!', __FILE__, __LINE__, __METHOD__, 10);
            echo json_encode($api_auth->returnHandler(false, 'SESSION', TTi18n::getText('Method %1 does not exist.', array($validator->escapeHTML($method)))));
        }
    } else {
        $validator = TTnew('Validator');
        Debug::text('Class: ' . $class_name . ' does not exist! (unauth)', __FILE__, __LINE__, __METHOD__, 10);
        echo json_encode($api_auth->returnHandler(false, 'SESSION', TTi18n::getText('Class %1 does not exist, or unauthenticated.', array($validator->escapeHTML($class_name)))));
    }

    return true;
}

function authenticatedInvokeService($class_name, $method, $arguments, $message_id, $authentication, $api_auth)
{
    global $current_user, $current_user_prefs, $current_company, $obj;

    $current_user = $authentication->getObject();

    if (is_object($current_user)) {
        $current_user->getUserPreferenceObject()->setDateTimePreferences();
        $current_user_prefs = $current_user->getUserPreferenceObject();

        Debug::text('Locale Cookie: ' . TTi18n::getLocaleCookie(), __FILE__, __LINE__, __METHOD__, 10);
        if (TTi18n::getLocaleCookie() != '' and $current_user_prefs->getLanguage() !== TTi18n::getLanguageFromLocale(TTi18n::getLocaleCookie())) {
            Debug::text('Changing User Preference Language to match cookie...', __FILE__, __LINE__, __METHOD__, 10);
            $current_user_prefs->setLanguage(TTi18n::getLanguageFromLocale(TTi18n::getLocaleCookie()));
            if ($current_user_prefs->isValid()) {
                $current_user_prefs->Save(false);
            }
        } else {
            Debug::text('User Preference Language matches cookie!', __FILE__, __LINE__, __METHOD__, 10);
        }
        if (isset($_GET['language']) and $_GET['language'] != '') {
            TTi18n::setLocale($_GET['language']); //Sets master locale
        } else {
            TTi18n::setLanguage($current_user_prefs->getLanguage());
            TTi18n::setCountry($current_user->getCountry());
            TTi18n::setLocale(); //Sets master locale
        }
        TTi18n::setLocaleCookie(); //Make sure locale cookie is set so APIGlobal.js.php can read it.

        $clf = new CompanyListFactory();
        $current_company = $clf->getByID($current_user->getCompany())->getCurrent();

        if (is_object($current_company)) {
            Debug::text('Current User: ' . $current_user->getUserName() . ' (User ID: ' . $current_user->getID() . ') Company: ' . $current_company->getName() . ' (Company ID: ' . $current_company->getId() . ')', __FILE__, __LINE__, __METHOD__, 10);

            //Debug::text('Handling JSON Call To API Factory: '.  $class_name .' Method: '. $method .' Message ID: '. $message_id .' UserName: '. $current_user->getUserName(), __FILE__, __LINE__, __METHOD__, 10);
            if ($class_name != '' and class_exists($class_name)) {
                $obj = new $class_name;
                if (method_exists($obj, 'setAMFMessageID')) {
                    $obj->setAMFMessageID($message_id); //Sets AMF message ID so progress bar continues to work.
                }

                if ($method != '' and method_exists($obj, $method)) {
                    $retval = call_user_func_array(array($obj, $method), (array)$arguments);
                    if ($retval !== null) {
                        if (!is_object($retval)) { //Make sure we never return a raw object to end-user, as too much information could be included in it.
                            echo json_encode($retval);
                            $json_error = getJSONError();
                            if ($json_error !== false) {
                                Debug::Arr($retval, 'ERROR: JSON: ' . $json_error, __FILE__, __LINE__, __METHOD__, 10);
                                echo json_encode($api_auth->returnHandler(false, 'EXCEPTION', 'ERROR: JSON: ' . $json_error));
                            }
                        } else {
                            Debug::text('OBJECT return value, not JSON encoding any additional data.', __FILE__, __LINE__, __METHOD__, 10);
                        }
                    } else {
                        Debug::text('NULL return value, not JSON encoding any additional data.', __FILE__, __LINE__, __METHOD__, 10);
                    }
                } else {
                    Debug::text('Method: ' . $method . ' does not exist!', __FILE__, __LINE__, __METHOD__, 10);
                    echo json_encode($api_auth->returnHandler(false, 'EXCEPTION', TTi18n::getText('Method %1 does not exist.', array($current_company->Validator->escapeHTML($method)))));
                }
            } else {
                Debug::text('Class: ' . $class_name . ' does not exist!', __FILE__, __LINE__, __METHOD__, 10);
                echo json_encode($api_auth->returnHandler(false, 'EXCEPTION', TTi18n::getText('Class %1 does not exist.', array($current_company->Validator->escapeHTML($class_name)))));
            }
        } else {
            Debug::text('Failed to get Company Object!', __FILE__, __LINE__, __METHOD__, 10);
            echo json_encode($api_auth->returnHandler(false, 'SESSION', TTi18n::getText('Company does not exist.')));
        }
    } else {
        Debug::text('Failed to get User Object!', __FILE__, __LINE__, __METHOD__, 10);
        echo json_encode($api_auth->returnHandler(false, 'SESSION', TTi18n::getText('User does not exist.')));
    }

    return true;
}

/*
 Arguments:
    GET: SessionID
    GET: Class
    GET: Method
    POST: Arguments for method.
*/
$class_prefix = 'API';
$class_name = false;
$method = false;

if (isset($_GET['Class']) and $_GET['Class'] != '') {
    $class_name = $_GET['Class'];

    //If API wasn't already put on the class, add it manually.
    if (strtolower(substr($class_name, 0, 3)) != 'api') {
        $class_name = $class_prefix . $class_name;
    }

    $class_name = TTgetPluginClassName($class_name);
} else {
    $class_name = TTgetPluginClassName($class_prefix . 'Authentication');
}

if (isset($_GET['Method']) and $_GET['Method'] != '') {
    $method = $_GET['Method'];
}

if (isset($_GET['MessageID']) and $_GET['MessageID'] != '') {
    $message_id = $_GET['MessageID'];
} else {
    $message_id = md5(uniqid()); //Random message_id
}

Debug::text('Handling JSON Call To API Factory: ' . $class_name . ' Method: ' . $method . ' Message ID: ' . $message_id, __FILE__, __LINE__, __METHOD__, 10);

//URL: api.php?SessionID=fc914bf32711bff031a6c80295bbff86&Class=APIPayStub&Method=getPayStub
/*
 RAW POST: data[filter_data][id][0]=101561&paging=TRUE&format=pdf
 JSON (URL encoded): %7B%22data%22%3A%7B%22filter_data%22%3A%7B%22id%22%3A%5B101561%5D%7D%7D%2C%22paging%22%3Atrue%2C%22format%22%3A%22pdf%22%7D

 FULL URL: SessionID=fc914bf32711bff031a6c80295bbff86&Class=APIPayStub&Method=test&json={"data":{"filter_data":{"id":[101561]}},"paging":true,"format":"pdf"}
*/
/*
$_POST = array( 'data' => array('filter_data' => array('id' => array(101561) ) ),
                'paging' => TRUE,
                'format' => 'pdf',
                );
*/
//Debug::Arr(file_get_contents('php://input'), 'POST: ', __FILE__, __LINE__, __METHOD__, 10);
//Debug::Arr($_POST, 'POST: ', __FILE__, __LINE__, __METHOD__, 10);

$arguments = $_POST;
if (isset($_POST['json']) or isset($_GET['json'])) {
    if (isset($_GET['json']) and $_GET['json'] != '') {
        $arguments = json_decode($_GET['json'], true);
    } elseif (isset($_POST['json']) and $_POST['json'] != '') {
        $arguments = json_decode($_POST['json'], true);
    }
}

//Make sure we sanitize all user inputs from XSS vulnerabilities. Where HTML should be allowed we can reverse this process on a case-by-case basis.
//This causes data to be modified when stored in the database though, we have since enabled escaping on output in jqGrid instead.
//FormVariables::RecurseFilterArray( $arguments );

//$argument_size = strlen( serialize($arguments) );
$argument_size = strlen($HTTP_RAW_POST_DATA); //Just strlen this variable rather than serialize all the data as it should be much faster.
if (PRODUCTION == true and $argument_size > (1024 * 12)) {
    Debug::Text('Arguments too large to display... Size: ' . $argument_size, __FILE__, __LINE__, __METHOD__, 10);
} else {
    if (strtolower($method) == 'login' and isset($arguments[0])) { //Make sure passwords arent displayed if logging is enabled.
        Debug::Arr($arguments[0], '*Censored* Arguments: (Size: ' . $argument_size . ')', __FILE__, __LINE__, __METHOD__, 10);
    } else {
        Debug::Arr($arguments, 'Arguments: (Size: ' . $argument_size . ')', __FILE__, __LINE__, __METHOD__, 10);
    }
}
unset($argument_size);

$api_auth = TTNew('APIAuthentication'); //Used to handle error cases and display error messages.
$session_id = getSessionID();
if ((isset($config_vars['other']['installer_enabled']) and $config_vars['other']['installer_enabled'] == false) and (!isset($config_vars['other']['down_for_maintenance']) or isset($config_vars['other']['down_for_maintenance']) and $config_vars['other']['down_for_maintenance'] == '') and $session_id != '' and !isset($_GET['disable_db']) and !in_array(strtolower($method), array('isloggedin', 'ping'))) { //When interface calls PING() on a regular basis we need to skip this check and pass it to APIAuthentication immediately to avoid updating the session time.
    $authentication = new Authentication();

    Debug::text('Session ID: ' . $session_id . ' Source IP: ' . Misc::getRemoteIPAddress(), __FILE__, __LINE__, __METHOD__, 10);
    if ($class_name != 'APIProgressBar' and $authentication->Check($session_id) === true) { //Always treat APIProgressBar as unauthenticated as an optimization to avoid causing uncessary SQL queries.
        if (Misc::checkValidReferer() == true) { //Help prevent CSRF attacks with this, but this is only needed when the user is already logged in.
            authenticatedInvokeService($class_name, $method, $arguments, $message_id, $authentication, $api_auth);
        } else {
            echo json_encode($api_auth->returnHandler(false, 'EXCEPTION', TTi18n::getText('Invalid referrer, possible CSRF.')));
        }
    } else {
        Debug::text('SessionID set but user not authenticated!', __FILE__, __LINE__, __METHOD__, 10);
        //echo json_encode( $api_auth->returnHandler( FALSE, 'SESSION', TTi18n::getText('User is not authenticated.' ) ) );

        //Rather than fail with session error, switch over to using unauthenticated calls, which if its calling to authenticated method will cause a SESSION error at that time.
        unauthenticatedInvokeService($class_name, $method, $arguments, $message_id, $api_auth);
    }
} else {
    Debug::text('No SessionID or calling non-authenticated function...', __FILE__, __LINE__, __METHOD__, 10);
    unauthenticatedInvokeService($class_name, $method, $arguments, $message_id, $api_auth);
}

Debug::text('Server Response Time: ' . ((float)microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']), __FILE__, __LINE__, __METHOD__, 10);
Debug::writeToLog();
