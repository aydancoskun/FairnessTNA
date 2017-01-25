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
 * @package API\FairnessClientAPI
 */
class FairnessClientAPI
{
    protected $url = '';
    protected $session_id = null;
    protected $session_hash = null; //Used to determine if we need to login again because the URL or Session changed.
    protected $class_factory = null;
    protected $namespace = 'urn:api';
    protected $protocol_version = 1;

    public function __construct($class = null, $url = null, $session_id = null)
    {
        global $FAIRNESS_URL, $FAIRNESS_SESSION_ID;

        ini_set('default_socket_timeout', 3600);

        if ($url == '') {
            $url = $FAIRNESS_URL;
        }

        if ($session_id == '') {
            $session_id = $FAIRNESS_SESSION_ID;
        }

        $this->setURL($url);
        $this->setSessionId($session_id);
        $this->setClass($class);

        return true;
    }

    public function setURL($url)
    {
        if ($url != '') {
            $this->url = $url;
            return true;
        }

        return false;
    }

    public function setSessionID($value)
    {
        if ($value != '') {
            global $FAIRNESS_SESSION_ID;
            $this->session_id = $FAIRNESS_SESSION_ID = $value;
            return true;
        }

        return false;
    }

    public function setClass($value)
    {
        $this->class_factory = trim($value);

        return true;
    }

    public function getSessionHash()
    {
        return $this->session_hash;
    }

    //Use the SessionHash to ensure the URL for the session doesn't get changed out from under us without re-logging in.

    public function isFault($result)
    {
        return $this->getSoapClientObject()->is_soap_fault($result);
    }

    public function getSoapClientObject()
    {
        global $FAIRNESS_BASIC_AUTH_USER, $FAIRNESS_BASIC_AUTH_PASSWORD;

        if ($this->session_id != '') {
            $url_pieces[] = 'SessionID=' . $this->session_id;
        }

        $url_pieces[] = 'Class=' . $this->class_factory;

        if (strpos($this->url, '?') === false) {
            $url_separator = '?';
        } else {
            $url_separator = '&';
        }

        $url = $this->url . $url_separator . 'v=' . $this->protocol_version . '&' . implode('&', $url_pieces);

        $retval = new SoapClient(null, array(
                'location' => $url,
                'uri' => $this->namespace,
                'encoding' => 'UTF-8',
                'style' => SOAP_RPC,
                'use' => SOAP_ENCODED,
                'login' => $FAIRNESS_BASIC_AUTH_USER,
                'password' => $FAIRNESS_BASIC_AUTH_PASSWORD,
                //'connection_timeout' => 120,
                //'request_timeout' => 3600,
                'compression' => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP,
                'trace' => 1,
                'exceptions' => 0
            )
        );

        return $retval;
    }

    public function Login($user_name, $password = null, $type = 'USER_NAME')
    {
        //Check to see if we are currently logged in as the same user already?
        global $FAIRNESS_SESSION_ID, $FAIRNESS_SESSION_HASH;
        if ($FAIRNESS_SESSION_ID != '' and $FAIRNESS_SESSION_HASH == $this->calcSessionHash($this->url, $FAIRNESS_SESSION_ID)) { //AND $this->isLoggedIn() == TRUE
            //Already logged in, skipping unnecessary new login procedure.
            return true;
        }

        $this->session_id = $this->session_hash = null; //Don't set old session ID on URL.
        $retval = $this->getSoapClientObject()->Login($user_name, $password, $type);
        if (is_soap_fault($retval)) {
            trigger_error('SOAP Fault: (Code: ' . $retval->faultcode . ', String: ' . $retval->faultstring . ') - Request: ' . $this->getSoapClientObject()->__getLastRequest() . ' Response: ' . $this->getSoapClientObject()->__getLastResponse(), E_USER_NOTICE);
            return false;
        }

        if (!is_array($retval) and $retval != false) {
            $this->setSessionID($retval);
            $this->setSessionHash();
            return true;
        }

        return false;
    }

    public function calcSessionHash($url, $session_id)
    {
        return md5(trim($url) . trim($session_id));
    }

    private function setSessionHash()
    {
        global $FAIRNESS_SESSION_HASH;
        $this->session_hash = $FAIRNESS_SESSION_HASH = $this->calcSessionHash($this->url, $this->session_id);
        return true;
    }

    public function isLoggedIn()
    {
        $old_class = $this->class_factory;
        $this->setClass('Authentication');
        $retval = $this->getSoapClientObject()->isLoggedIn();
        $this->setClass($old_class);
        unset($old_class);

        return $retval;
    }

    public function Logout()
    {
        $this->setClass('Authentication');
        $retval = $this->getSoapClientObject()->Logout();
        if ($retval == true) {
            global $FAIRNESS_SESSION_ID, $FAIRNESS_SESSION_HASH;
            $FAIRNESS_SESSION_ID = $FAIRNESS_SESSION_HASH = false;
            $this->session_id = $this->session_hash = null;
        }

        return $retval;
    }

    public function __call($function_name, $args = array())
    {
        if (is_object($this->getSoapClientObject())) {
            $retval = call_user_func_array(array($this->getSoapClientObject(), $function_name), $args);

            if (is_soap_fault($retval)) {
                trigger_error('SOAP Fault: (Code: ' . $retval->faultcode . ', String: ' . $retval->faultstring . ') - Request: ' . $this->getSoapClientObject()->__getLastRequest() . ' Response: ' . $this->getSoapClientObject()->__getLastResponse(), E_USER_NOTICE);

                return false;
            }

            return new FairnessClientAPIReturnHandler($function_name, $args, $retval);
        }

        return false;
    }
}

/**
 * @package API\FairnessClientAPI
 */
class FairnessClientAPIReturnHandler
{
    /*
    'api_retval' => $retval,
    'api_details' => array(
                    'code' => $code,
                    'description' => $description,
                    'record_details' => array(
                                            'total' => $validator_stats['total_records'],
                                            'valid' => $validator_stats['valid_records'],
                                            'invalid' => ($validator_stats['total_records']-$validator_stats['valid_records'])
                                            ),
                    'details' =>  $details,
                    )
    */
    protected $function_name = null;
    protected $args = null;
    protected $result_data = false;

    public function __construct($function_name, $args, $result_data)
    {
        $this->function_name = $function_name;
        $this->args = $args;
        $this->result_data = $result_data;

        return true;
    }

    public function getResultData()
    {
        return $this->result_data;
    }

    public function __toString()
    {
        $eol = "<br>\n";

        $output = array();
        $output[] = '=====================================';
        $output[] = 'Function: ' . $this->getFunction() . '()';
        if (is_object($this->getArgs()) or is_array($this->getArgs())) {
            $output[] = 'Args: ' . count($this->getArgs());
        } else {
            $output[] = 'Args: ' . $this->getArgs();
        }
        $output[] = '-------------------------------------';
        $output[] = 'Returned:';
        $output[] = ($this->isValid() === true) ? 'IsValid: YES' : 'IsValid: NO';
        if ($this->isValid() === true) {
            $output[] = 'Return Value: ' . $this->getResult();
        } else {
            $output[] = 'Code: ' . $this->getCode();
            $output[] = 'Description: ' . $this->getDescription();
            $output[] = 'Details: ';

            $details = $this->getDetails();
            if (is_array($details)) {
                foreach ($details as $row => $detail) {
                    $output[] = 'Row: ' . $row;
                    foreach ($detail as $field => $msgs) {
                        $output[] = '--Field: ' . $field;
                        foreach ($msgs as $key => $msg) {
                            $output[] = '----Message: ' . $msg;
                        }
                    }
                }
            }
        }
        $output[] = '=====================================';
        $output[] = '';

        return implode($eol, $output);
    }

    public function getFunction()
    {
        return $this->function_name;
    }

    public function getArgs()
    {
        return $this->args;
    }

    public function isValid()
    {
        if (isset($this->result_data['api_retval'])) {
            return (bool)$this->result_data['api_retval'];
        }

        return true;
    }

    public function getResult()
    {
        if (isset($this->result_data['api_retval'])) {
            return $this->result_data['api_retval'];
        } else {
            return $this->result_data;
        }
    }

    public function getCode()
    {
        if (isset($this->result_data['api_details']) and isset($this->result_data['api_details']['code'])) {
            return $this->result_data['api_details']['code'];
        }

        return false;
    }

    public function getDescription()
    {
        if (isset($this->result_data['api_details']) and isset($this->result_data['api_details']['description'])) {
            return $this->result_data['api_details']['description'];
        }

        return false;
    }

    public function getDetails()
    {
        if (isset($this->result_data['api_details']) and isset($this->result_data['api_details']['details'])) {
            return $this->result_data['api_details']['details'];
        }

        return false;
    }

    public function isError()
    { //Opposite of isValid()
        if (isset($this->result_data['api_retval'])) {
            if ($this->result_data['api_retval'] === false) {
                return true;
            } else {
                return false;
            }
        }

        return false;
    }

    public function getRecordDetails()
    {
        if (isset($this->result_data['api_details']) and isset($this->result_data['api_details']['record_details'])) {
            return $this->result_data['api_details']['record_details'];
        }

        return false;
    }

    public function getTotalRecords()
    {
        if (isset($this->result_data['api_details']) and isset($this->result_data['api_details']['record_details']) and isset($this->result_data['api_details']['record_details']['total_records'])) {
            return $this->result_data['api_details']['record_details']['total_records'];
        }

        return false;
    }

    public function getValidRecords()
    {
        if (isset($this->result_data['api_details']) and isset($this->result_data['api_details']['record_details']) and isset($this->result_data['api_details']['record_details']['valid_records'])) {
            return $this->result_data['api_details']['record_details']['valid_records'];
        }

        return false;
    }

    public function getInValidRecords()
    {
        if (isset($this->result_data['api_details']) and isset($this->result_data['api_details']['record_details']) and isset($this->result_data['api_details']['record_details']['invalid_records'])) {
            return $this->result_data['api_details']['record_details']['invalid_records'];
        }

        return false;
    }
}
