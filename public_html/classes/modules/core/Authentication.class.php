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
class Authentication
{
    protected $name = 'SessionID';
    protected $idle = 14400; //Max IDLE time
    protected $expire_session; //When TRUE, cookie is expired when browser closes.
    protected $type_id = 800; //USER_NAME
    protected $object_id = null;
    protected $session_id = null;
    protected $ip_address = null;
    protected $created_date = null;
    protected $updated_date = null;

    protected $obj = null;

    public function __construct()
    {
        global $db;

        $this->db = $db;

        $this->rl = TTNew('RateLimit');
        $this->rl->setID('authentication_' . Misc::getRemoteIPAddress());
        $this->rl->setAllowedCalls(20);
        $this->rl->setTimeFrame(900); //15 minutes

        return true;
    }

    public function setEnableExpireSession($bool)
    {
        $this->expire_session = (bool)$bool;
        return true;
    }

    public function newSession($object_id = null, $ip_address = null)
    {
        if ($object_id == '' and $this->getObjectID() != '') {
            $object_id = $this->getObjectID();
        }

        $new_session_id = $this->genSessionID();
        Debug::text('Duplicating session to User ID: ' . $object_id . ' Original SessionID: ' . $this->getSessionID() . ' New Session ID: ' . $new_session_id . ' IP Address: ' . $ip_address, __FILE__, __LINE__, __METHOD__, 10);

        $authentication = new Authentication();
        $authentication->setType($this->getType());
        $authentication->setSessionID($new_session_id);
        $authentication->setIPAddress($ip_address);
        $authentication->setCreatedDate();
        $authentication->setUpdatedDate();
        $authentication->setObjectID($object_id);

        //Sets session cookie.
        //$authentication->setCookie();

        //Write data to db.
        $authentication->Write();

        //$authentication->UpdateLastLoginDate(); //Don't do this when switching users.

        return $authentication->getSessionID();
    }

    //Determine if the session type is for an actual user, so we know if we can create audit logs.

    public function getObjectID()
    {
        return $this->object_id;
    }

    public function setObjectID($id)
    {
        $id = (int)$id;
        if ($id > 0) {
            $this->object_id = $id;

            return true;
        }

        return false;
    }

    private function genSessionID()
    {
        return sha1(uniqid(dechex(mt_rand()), true));
    }

    public function getSessionID()
    {
        return $this->session_id;
    }

    public function setSessionID($session_id)
    {
        $validator = new Validator;
        $session_id = $validator->stripNonAlphaNumeric($session_id);

        if (!empty($session_id)) {
            $this->session_id = $session_id;

            return true;
        }

        return false;
    }

    public function setType($type_id)
    {
        if (!is_numeric($type_id)) {
            $type_id = $this->getTypeIDByName($type_id);
        }

        if (is_int($type_id)) {
            $this->type_id = $type_id;

            return true;
        }

        return false;
    }

    public function getTypeIDByName($type)
    {
        $type = strtolower($type);

        //SmallINT datatype, max of 32767
        $map = array(
            //
            //Non-Users.
            //
            'job_applicant' => 100,
            'client_contact' => 110,

            //
            //Users
            //

            //Other hardware.
            'ibutton' => 500,
            'barcode' => 510,
            'finger_print' => 520,

            //QuickPunch
            //'phone_id' => 600,
            'phone_id' => 800, //Make Phone_ID same as user_name for now, as that is how it used to work and this causes problems with the Mobile App.
            'quick_punch_id' => 600,
            'client_pc' => 610,

            //SSO or alternitive methods
            'http_auth' => 700,
            'sso' => 710,

            //Username/Passwords including two factor.
            'user_name' => 800,
            'user_name_two_factor' => 810,
        );

        if (isset($map[$type])) {
            return (int)$map[$type];
        }

        return false;
    }

    public function getType()
    {
        return $this->type_id;
    }

    //Expire Session when browser is closed?

    private function Write()
    {
        $ph = array(
            'session_id' => $this->getSessionID(),
            'type_id' => (int)$this->getType(),
            'object_id' => $this->getObjectID(),
            'ip_address' => $this->getIPAddress(),
            'created_date' => $this->getCreatedDate(),
            'updated_date' => $this->getUpdatedDate()
        );

        $query = 'INSERT INTO authentication (session_id, type_id, object_id, ip_address, created_date, updated_date) VALUES( ?, ?, ?, ?, ?, ? )';
        try {
            $this->db->Execute($query, $ph);
        } catch (Exception $e) {
            throw new DBError($e);
        }

        return true;
    }

    public function getIPAddress()
    {
        return $this->ip_address;
    }

    public function setIPAddress($ip_address = null)
    {
        if (empty($ip_address)) {
            $ip_address = Misc::getRemoteIPAddress();
        }

        if (!empty($ip_address)) {
            $this->ip_address = $ip_address;

            return true;
        }

        return false;
    }

    public function getCreatedDate()
    {
        return $this->created_date;
    }

    public function setCreatedDate($epoch = null)
    {
        if ($epoch == '') {
            $epoch = TTDate::getTime();
        }

        if (is_numeric($epoch)) {
            $this->created_date = $epoch;

            return true;
        }

        return false;
    }

    public function getUpdatedDate()
    {
        return $this->updated_date;
    }

    //Duplicates existing session with a new SessionID. Useful for multiple logins with the same or different users.

    public function setUpdatedDate($epoch = null)
    {
        if ($epoch == '') {
            $epoch = TTDate::getTime();
        }

        if (is_numeric($epoch)) {
            $this->updated_date = $epoch;

            return true;
        }

        return false;
    }

    public function changeObject($object_id)
    {
        $this->getObjectById($object_id);

        $ph = array(
            'object_id' => (int)$object_id,
            'session_id' => $this->getSessionID(),
        );

        $query = 'UPDATE authentication SET object_id = ? WHERE session_id = ?';

        try {
            $this->db->Execute($query, $ph);
        } catch (Exception $e) {
            throw new DBError($e);
        }

        return true;
    }

    public function getObjectByID($id)
    {
        if (empty($id)) {
            return false;
        }

        if ($this->isUser()) {
            $ulf = TTnew('UserListFactory');
            $ulf->getByID($id);
            if ($ulf->getRecordCount() == 1) {
                $retval = $ulf->getCurrent();
            }
        }

        if (isset($retval) and is_object($retval)) {
            return $retval;
        }

        return false;
    }

    public function isUser($type_id = false)
    {
        if ($type_id == '') {
            $type_id = $this->getType();
        }

        //If this is updated, modify PurgeDatabase.class.php for authentication table as well.
        if (in_array($type_id, array(100, 110))) {
            return false;
        }

        return true;
    }

    public function getObject()
    {
        if (is_object($this->obj)) {
            return $this->obj;
        }

        return false;
    }

    public function HTTPAuthenticationHeader()
    {
        global $config_vars;
        if (isset($config_vars['other']['enable_http_authentication']) and $config_vars['other']['enable_http_authentication'] == 1
            and isset($config_vars['other']['enable_http_authentication_prompt']) and $config_vars['other']['enable_http_authentication_prompt'] == 1
        ) {
            header('WWW-Authenticate: Basic realm="' . APPLICATION_NAME . '"');
            header('HTTP/1.0 401 Unauthorized');
            echo TTi18n::getText('ERROR: A valid username/password is required to access this application. Press refresh in your web browser to try again.');
            Debug::writeToLog();
            exit;
        }
    }

    public function loginHTTPAuthentication()
    {
        $user_name = self::getHTTPAuthenticationUsername();

        global $config_vars;
        if (isset($config_vars['other']['enable_http_authentication']) and $config_vars['other']['enable_http_authentication'] == 1 and $user_name != '') {
            //Debug::Arr($_SERVER, 'Server vars: ', __FILE__, __LINE__, __METHOD__, 10);
            if (isset($_SERVER['PHP_AUTH_PW']) and $_SERVER['PHP_AUTH_PW'] != '') {
                Debug::Text('Handling HTTPAuthentication with password.', __FILE__, __LINE__, __METHOD__, 10);
                return $this->Login($user_name, $_SERVER['PHP_AUTH_PW'], 'USER_NAME');
            } else {
                Debug::Text('Handling HTTPAuthentication without password.', __FILE__, __LINE__, __METHOD__, 10);
                return $this->Login($user_name, 'HTTP_AUTH', 'HTTP_AUTH');
            }
        } elseif ($user_name != '') {
            Debug::Text('HTTPAuthentication is passing username: ' . $user_name . ' however enable_http_authentication is not enabled.', __FILE__, __LINE__, __METHOD__, 10);
        }

        return false;
    }

    public function getHTTPAuthenticationUsername()
    {
        $user_name = false;
        if (isset($_SERVER['PHP_AUTH_USER']) and $_SERVER['PHP_AUTH_USER'] != '') {
            $user_name = $_SERVER['PHP_AUTH_USER'];
        } elseif (isset($_SERVER['REMOTE_USER']) and $_SERVER['REMOTE_USER'] != '') {
            $user_name = $_SERVER['REMOTE_USER'];
        }

        return $user_name;
    }

    public function Login($user_name, $password, $type = 'USER_NAME')
    {
        //DO NOT lowercase username, because iButton values are case sensitive.
        $user_name = html_entity_decode(trim($user_name));
        $password = html_entity_decode($password);

        //Checks user_name/password.. However password is blank for iButton/Fingerprints often so we can't check that.
        if ($user_name == '') {
            return false;
        }

        $type = strtolower($type);
        Debug::text('Login Type: ' . $type, __FILE__, __LINE__, __METHOD__, 10);
        try {
            //Prevent brute force attacks by IP address.
            //Allowed up to 20 attempts in a 30 min period.
            if ($this->rl->check() == false) {
                Debug::Text('Excessive failed password attempts... Preventing login from: ' . Misc::getRemoteIPAddress() . ' for up to 15 minutes...', __FILE__, __LINE__, __METHOD__, 10);
                sleep(5); //Excessive password attempts, sleep longer.
                return false;
            }

            $uf = new UserFactory();
            if (preg_match($uf->username_validator_regex, $user_name) === 0) { //This helps prevent invalid byte sequences on unicode strings.
                Debug::Text('Username doesnt match regex: ' . $user_name, __FILE__, __LINE__, __METHOD__, 10);
                return false; //No company by that user name.
            }
            unset($uf);

            switch ($type) {
                case 'user_name':
                    if ($password == '') {
                        return false;
                    }

                    if ($this->checkCompanyStatus($user_name) == 10) { //Active
                        //Lowercase regular user_names here only.
                        $password_result = $this->checkPassword($user_name, $password);
                    } else {
                        $password_result = false; //No company by that user name.
                    }
                    break;
                case 'phone_id': //QuickPunch ID/Password
                case 'quick_punch_id':
                    $password_result = $this->checkPhonePassword($user_name, $password);
                    break;
                case 'ibutton':
                    $password_result = $this->checkIButton($user_name);
                    break;
                case 'barcode':
                    $password_result = $this->checkBarcode($user_name, $password);
                    break;
                case 'finger_print':
                    $password_result = $this->checkFingerPrint($user_name);
                    break;
                case 'client_pc':
                    //This is for client application persistent connections, use:
                    //Login Type: client_pc
                    //Station Type: PC

                    //$password_result = $this->checkClientPC( $user_name );
                    $password_result = $this->checkBarcode($user_name, $password);
                    break;
                case 'http_auth':
                    if ($this->checkCompanyStatus($user_name) == 10) { //Active
                        //Lowercase regular user_names here only.
                        $password_result = $this->checkUsername($user_name);
                    } else {
                        $password_result = false; //No company by that user name.
                    }
                    break;
                default:
                    return false;
            }

            if ($password_result === true) {
                Debug::text('Login Succesful for User Name: ' . $user_name, __FILE__, __LINE__, __METHOD__, 10);

                $this->setType($type);
                $this->setSessionID($this->genSessionID());
                $this->setIPAddress();
                $this->setCreatedDate();
                $this->setUpdatedDate();

                //Sets session cookie.
                $this->setCookie();

                //Write data to db.
                $this->Write();

                //Only update last_login_date when using user_name to login to the web interface.
                if ($type == 'user_name') {
                    $this->UpdateLastLoginDate();
                }

                //Truncate SessionID for security reasons, so someone with access to the audit log can't steal sessions.
                if ($this->isUser() == true) {
                    TTLog::addEntry($this->getObjectID(), 100, TTi18n::getText('SourceIP') . ': ' . $this->getIPAddress() . ' ' . TTi18n::getText('Type') . ': ' . $type . ' ' . TTi18n::getText('SessionID') . ': ' . $this->getSecureSessionID() . ' ' . TTi18n::getText('ObjectID') . ': ' . $this->getObjectID(), $this->getObjectID(), 'authentication'); //Login
                }

                $this->rl->delete(); //Clear failed password rate limit upon successful login.

                return true;
            }

            Debug::text('Login Failed! Attempt: ' . $this->rl->getAttempts(), __FILE__, __LINE__, __METHOD__, 10);

            sleep(($this->rl->getAttempts() * 0.5)); //If password is incorrect, sleep for some time to slow down brute force attacks.
        } catch (Exception $e) {
            //Database not initialized, or some error, redirect to Install page.
            throw new DBError($e, 'DBInitialize');
        }

        return false;
    }

    public function checkCompanyStatus($user_name)
    {
        $ulf = TTnew('UserListFactory');
        $ulf->getByUserName(strtolower($user_name));
        if ($ulf->getRecordCount() == 1) {
            $u_obj = $ulf->getCurrent();
            if (is_object($u_obj)) {
                $clf = TTnew('CompanyListFactory');
                $clf->getById($u_obj->getCompany());
                if ($clf->getRecordCount() == 1) {
                    //Return the actual status so we can do multiple checks.
                    Debug::text('Company Status: ' . $clf->getCurrent()->getStatus(), __FILE__, __LINE__, __METHOD__, 10);
                    return $clf->getCurrent()->getStatus();
                }
            }
        }

        return false;
    }

    public function checkPassword($user_name, $password)
    {
        //Use UserFactory to set name.
        $ulf = TTnew('UserListFactory');

        $ulf->getByUserNameAndStatus($user_name, 10); //Active

        foreach ($ulf as $user) {
            if ($user->checkPassword($password)) {
                $this->setObjectID($user->getID());
                $this->setObject($user);

                return true;
            } else {
                return false;
            }
        }

        return false;
    }

    public function setObject($object)
    {
        if (is_object($object)) {
            $this->obj = $object;
            return true;
        }

        return false;
        /*
        if ( !empty($object_id) ) {
            $ulf = TTnew( 'UserListFactory' );
            $ulf->getByID($object_id);
            if ( $ulf->getRecordCount() == 1 ) {
                foreach ($ulf as $user) {
                    $this->obj = $user;

                    return TRUE;
                }
            }
        }

        return FALSE;
        */
    }

    public function checkPhonePassword($phone_id, $password)
    {
        //Use UserFactory to set name.
        $ulf = TTnew('UserListFactory');

        $ulf->getByPhoneIdAndStatus($phone_id, 10);

        foreach ($ulf as $user) {
            if ($user->checkPhonePassword($password)) {
                $this->setObjectID($user->getID());
                $this->setObject($user);

                return true;
            } else {
                return false;
            }
        }

        return false;
    }

    public function checkIButton($id)
    {
        $uilf = TTnew('UserIdentificationListFactory');
        $uilf->getByTypeIdAndValue(10, $id);
        if ($uilf->getRecordCount() > 0) {
            foreach ($uilf as $ui_obj) {
                if (is_object($ui_obj->getUserObject()) and $ui_obj->getUserObject()->getStatus() == 10) {
                    $this->setObjectID($ui_obj->getUser());
                    $this->setObject($ui_obj->getUserObject());
                    return true;
                }
            }
        }

        return false;
    }

    public function checkBarcode($object_id, $employee_number)
    {
        //Use UserFactory to set name.
        $ulf = TTnew('UserListFactory');

        $ulf->getByIdAndStatus($object_id, 10);

        foreach ($ulf as $user) {
            if ($user->checkEmployeeNumber($employee_number)) {
                $this->setObjectID($user->getID());
                $this->setObject($user);

                return true;
            } else {
                return false;
            }
        }

        return false;
    }

    public function checkFingerPrint($id)
    {
        $ulf = TTnew('UserListFactory');

        $ulf->getByIdAndStatus($id, 10);

        foreach ($ulf as $user) {
            if ($user->getId() == $id) {
                $this->setObjectID($user->getID());
                $this->setObject($user);

                return true;
            } else {
                return false;
            }
        }

        return false;
    }

    public function checkUsername($user_name)
    {
        //Use UserFactory to set name.
        $ulf = TTnew('UserListFactory');

        $ulf->getByUserNameAndStatus($user_name, 10); //Active
        foreach ($ulf as $user) {
            if (strtolower($user->getUsername()) == strtolower(trim($user_name))) {
                $this->setObjectID($user->getID());
                $this->setObject($user);

                return true;
            } else {
                return false;
            }
        }

        return false;
    }

    private function setCookie($type_id = false)
    {
        if ($this->getSessionID()) {
            $cookie_expires = (time() + 7776000); //90 Days
            if ($this->getEnableExpireSession() === true) {
                $cookie_expires = 0; //Expire when browser closes.
            }
            Debug::text('Cookie Expires: ' . $cookie_expires . ' Path: ' . Environment::getCookieBaseURL(), __FILE__, __LINE__, __METHOD__, 10);

            //15-Jun-2016: This should be not be needed anymore as it has been around for several years now.
            //setcookie( $this->getName(), NULL, ( time() + 9999999 ), Environment::getBaseURL(), NULL, Misc::isSSL( TRUE ) ); //Delete old directory cookie as it can cause a conflict if it stills exists.

            //Upon successful login to a cloud hosted server, set the URL to a cookie that can be read from the upper domain to help get the user back to the proper login URL later.
            setcookie('LoginURL', Misc::getURLProtocol() . '://' . Misc::getHostName() . Environment::getBaseURL(), (time() + 9999999), '/', '.' . Misc::getHostNameWithoutSubDomain(Misc::getHostName(false)), false); //Delete old directory cookie as it can cause a conflict if it stills exists.

            //Set cookie in root directory so other interfaces can access it.
            setcookie($this->getName(), $this->getSessionID(), $cookie_expires, Environment::getCookieBaseURL(), null, Misc::isSSL(true));

            return true;
        }

        return false;
    }

    public function getEnableExpireSession()
    {
        return $this->expire_session;
    }

    public function getName($type_id = false)
    {
        if ($type_id == '') {
            $type_id = $this->getType();
        }
        return $this->getNameByTypeId($type_id);
        //return $this->name;
    }

    //Allow web server to handle authentication with Basic Auth/LDAP/SSO/AD, etc...

    public function getNameByTypeId($type_id)
    {
        if (!is_numeric($type_id)) {
            $type_id = $this->getTypeIDByName($type_id);
        }

        //Seperate session cookie names so if the user logs in with QuickPunch it doesn't log them out of the full interface for example.
        $map = array(
            100 => 'SessionID-JA', //Job Applicant
            110 => 'SessionID-CC', //Client Contact

            500 => 'SessionID-HW',
            510 => 'SessionID-HW',
            520 => 'SessionID-HW',

            600 => 'SessionID-QP', //QuickPunch
            610 => 'SessionID-PC', //ClientPC

            700 => 'SessionID',
            710 => 'SessionID',
            800 => 'SessionID',
            810 => 'SessionID',
        );

        if (isset($map[$type_id])) {
            return $map[$type_id];
        }

        return false;
    }

    private function UpdateLastLoginDate()
    {
        $ph = array(
            'last_login_date' => TTDate::getTime(),
            'object_id' => (int)$this->getObjectID(),
        );

        $query = 'UPDATE users SET last_login_date = ? WHERE id = ?';

        try {
            $this->db->Execute($query, $ph);
        } catch (Exception $e) {
            throw new DBError($e);
        }

        return true;
    }

    public function getSecureSessionID()
    {
        return substr_replace($this->getSessionID(), '...', (int)(strlen($this->getSessionID()) / 3), (int)(strlen($this->getSessionID()) / 3));
    }

    public function Logout()
    {
        $this->destroyCookie();
        $this->Delete();

        if ($this->isUser() == true) {
            TTLog::addEntry($this->getObjectID(), 110, TTi18n::getText('SourceIP') . ': ' . $this->getIPAddress() . ' ' . TTi18n::getText('SessionID') . ': ' . $this->getSecureSessionID() . ' ' . TTi18n::getText('ObjectID') . ': ' . $this->getObjectID(), $this->getObjectID(), 'authentication');
        }

        return true;
    }

    //When company status changes, logout all users for the company.

    private function destroyCookie()
    {
        setcookie($this->getName(), null, (time() + 9999999), Environment::getCookieBaseURL(), null, Misc::isSSL(true));

        return true;
    }

    //When user resets password, logout all sessions for that user.

    private function Delete()
    {
        $ph = array(
            'session_id' => $this->getSessionID(),
        );

        //Can't use IdleTime here, as some users have different idle times.
        //Assume none are longer then one day though.
        $query = 'DELETE FROM authentication WHERE session_id = ? OR (updated_date - created_date) > ' . (86400 * 2) . ' OR (' . TTDate::getTime() . ' - updated_date) > 86400';

        try {
            $this->db->Execute($query, $ph);
        } catch (Exception $e) {
            throw new DBError($e);
        }

        return true;
    }

    //
    //Functions to help check crendentials.
    //

    public function Check($session_id = null, $type = 'USER_NAME', $touch_updated_date = true)
    {
        global $profiler;
        $profiler->startTimer("Authentication::Check()");

        //Debug::text('Session Name: '. $this->getName(), __FILE__, __LINE__, __METHOD__, 10);

        //Support session_ids passed by cookie, post, and get.
        if ($session_id == '') {
            $session_name = $this->getName($type);

            //There appears to be a bug with Flex when uploading files (upload_file.php) that sometimes the browser sends an out-dated sessionID in the cookie
            //that differs from the sessionID sent in the POST variable. This causes a Flex I/O error because FairnessTNA thinks the user isn't authenticated.
            //To fix this check to see if BOTH a COOKIE and POST variable contain SessionIDs, and if so use the POST one.
            if ((isset($_COOKIE[$session_name]) and $_COOKIE[$session_name] != '') and (isset($_POST[$session_name]) and $_POST[$session_name] != '')) {
                $session_id = $_POST[$session_name];
            } elseif (isset($_COOKIE[$session_name]) and $_COOKIE[$session_name] != '') {
                $session_id = $_COOKIE[$session_name];
            } elseif (isset($_POST[$session_name]) and $_POST[$session_name] != '') {
                $session_id = $_POST[$session_name];
            } elseif (isset($_GET[$session_name]) and $_GET[$session_name] != '') {
                $session_id = $_GET[$session_name];
            } else {
                $session_id = false;
            }
        }

        Debug::text('Session ID: ' . $session_id . ' IP Address: ' . Misc::getRemoteIPAddress() . ' URL: ' . $_SERVER['REQUEST_URI'] . ' Touch Updated Date: ' . (int)$touch_updated_date, __FILE__, __LINE__, __METHOD__, 10);
        //Checks session cookie, returns object_id;
        if (isset($session_id)) {
            /*
                Bind session ID to IP address to aid in preventing session ID theft,
                if this starts to cause problems
                for users behind load balancing proxies, allow them to choose to
                bind session IDs to just the first 1-3 quads of their IP address
                as well as the SHA1 of their user-agent string.
                Could also use "behind proxy IP address" if one is supplied.
            */
            try {
                $this->setType($type);
                $this->setSessionID($session_id);
                $this->setIPAddress();

                if ($this->Read() == true) {
                    //touch UpdatedDate in most cases, however when calling PING() we don't want to do this.
                    if ($touch_updated_date !== false) {
                        //Reduce contention and traffic on the session table by only touching the updated_date every 60 +/- rand() seconds.
                        //Especially helpful for things like the dashboard that trigger many async calls.
                        if ((time() - $this->getUpdatedDate()) > (60 + rand(0, 60))) {
                            Debug::text('  Touching updated date due to more than 60s...', __FILE__, __LINE__, __METHOD__, 10);
                            $this->Update();
                        }
                    }

                    $profiler->stopTimer("Authentication::Check()");
                    return true;
                }
            } catch (Exception $e) {
                //Database not initialized, or some error, redirect to Install page.
                throw new DBError($e, 'DBInitialize');
            }
        }

        $profiler->stopTimer("Authentication::Check()");

        return false;
    }

    //Checks just the username, used in conjunction with HTTP Authentication/SSO.

    private function Read()
    {
        $ph = array(
            'session_id' => $this->getSessionID(),
            //'ip_address' => $this->getIPAddress(),
            'type_id' => (int)$this->getType(),
            'updated_date' => (TTDate::getTime() - $this->getIdle()),
        );

        //Need to handle IP addresses changing during the session.
        //When using SSL, don't check for IP address changing at all as we use secure cookies.
        //When *not* using SSL, always require the same IP address for the session.
        //However we need to still allow multiple sessions for the same user, using different IPs.
        $query = 'SELECT type_id, session_id, object_id, ip_address, created_date, updated_date FROM authentication WHERE session_id = ? AND type_id = ? AND updated_date >= ?';

        //Debug::Arr($ph, 'Query: '. $query, __FILE__, __LINE__, __METHOD__, 10);
        $result = $this->db->GetRow($query, $ph);

        if (count($result) > 0) {
            if (PRODUCTION == true and $result['ip_address'] != $this->getIPAddress()) {
                Debug::text('WARNING: IP Address has changed for existing session... Original IP: ' . $result['ip_address'] . ' Current IP: ' . $this->getIPAddress() . ' isSSL: ' . (int)Misc::isSSL(true), __FILE__, __LINE__, __METHOD__, 10);
                //When using SSL, we don't care if the IP address has changed, as the session should still be secure.
                //This allows sessions to work across load balancing routers, or between mobile/wifi connections, which can change 100% of the IP address (so close matches are useless anyways)
                if (Misc::isSSL(true) != true) {
                    //When not using SSL there is no 100% method of preventing session hijacking, so just insist that IP addresses match exactly as its as close as we can get.
                    Debug::text('Not using SSL, IP addresses must match exactly...', __FILE__, __LINE__, __METHOD__, 10);
                    return false;
                }
            }
            $this->setType($result['type_id']);
            $this->setSessionID($result['session_id']);
            $this->setIPAddress($result['ip_address']);
            $this->setCreatedDate($result['created_date']);
            $this->setUpdatedDate($result['updated_date']);
            $this->setObjectID($result['object_id']);

            if ($this->setObject($this->getObjectById($this->getObjectID()))) {
                return true;
            }
        }

        return false;
    }

    public function getIdle()
    {
        //Debug::text('Idle Seconds Allowed: '. $this->idle, __FILE__, __LINE__, __METHOD__, 10);
        return $this->idle;
    }

    public function setIdle($secs)
    {
        if (is_int($secs)) {
            $this->idle = $secs;

            return true;
        }

        return false;
    }

    private function Update()
    {
        $ph = array(
            'updated_date' => TTDate::getTime(),
            'session_id' => $this->getSessionID(),
        );

        $query = 'UPDATE authentication SET updated_date = ? WHERE session_id = ?';

        try {
            $this->db->Execute($query, $ph); //This can cause SQL error: "could not serialize access due to concurrent update" when in READ COMMITTED mode.
        } catch (Exception $e) {
            //Ignore any serialization errors, as its not a big deal anyways.
            Debug::text('WARNING: SQL query failed, likely due to transaction isolotion: ' . $e->getMessage(), __FILE__, __LINE__, __METHOD__, 10);
            //throw new DBError($e);
        }

        return true;
    }

    public function logoutCompany($company_id)
    {
        //MySQL fails with many of these queries due to recently changed syntax in a point release, disable purging when using MySQL for now.
        //http://bugs.mysql.com/bug.php?id=27525
        if (strncmp($this->db->databaseType, 'mysql', 5) == 0) {
            return false;
        }

        $ph = array(
            'company_id' => (int)$company_id,
            'type_id' => (int)$this->getTypeIDByName('USER_NAME'),
        );

        $query = 'DELETE FROM authentication as a USING users as b WHERE a.object_id = b.id AND b.company_id = ? AND a.type_id = ?';

        try {
            Debug::text('Logging out entire company ID: ' . $company_id, __FILE__, __LINE__, __METHOD__, 10);
            $this->db->Execute($query, $ph);
        } catch (Exception $e) {
            throw new DBError($e);
        }

        return true;
    }

    public function logoutUser($object_id)
    {
        $ph = array(
            'object_id' => (int)$object_id,
            'type_id' => (int)$this->getTypeIDByName('USER_NAME'),
        );

        $query = 'DELETE FROM authentication WHERE object_id = ? AND type_id = ?';

        try {
            Debug::text('Logging out all user sessions: ' . $object_id, __FILE__, __LINE__, __METHOD__, 10);
            $this->db->Execute($query, $ph);
        } catch (Exception $e) {
            throw new DBError($e);
        }

        return true;
    }

    public function checkClientPC($user_name)
    {
        //Use UserFactory to set name.
        $ulf = TTnew('UserListFactory');

        $ulf->getByUserNameAndStatus(strtolower($user_name), 10);

        foreach ($ulf as $user) {
            if ($user->getUserName() == $user_name) {
                $this->setObjectID($user->getID());
                $this->setObject($user);

                return true;
            } else {
                return false;
            }
        }

        return false;
    }
}
