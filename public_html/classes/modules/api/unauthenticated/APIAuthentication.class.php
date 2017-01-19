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
 * @package API\UnAuthenticated
 */
class APIAuthentication extends APIFactory {
	protected $main_class = 'Authentication';

	public function __construct() {
		parent::__construct(); //Make sure parent constructor is always called.

		return TRUE;
	}

	//Default username=NULL to prevent argument warnings messages if its not passed from the API.
	function Login($user_name = NULL, $password = NULL, $type = 'USER_NAME') {
		global $config_vars;
		$authentication = new Authentication();

		Debug::text('User Name: '. $user_name .' Password Length: '. strlen($password) .' Type: '. $type, __FILE__, __LINE__, __METHOD__, 10);

		if ( ( isset($config_vars['other']['installer_enabled']) AND $config_vars['other']['installer_enabled'] == 1 ) OR ( isset($config_vars['other']['down_for_maintenance']) AND $config_vars['other']['down_for_maintenance'] == 1 ) ) {
			Debug::text('WARNING: Installer is enabled... Normal logins are disabled!', __FILE__, __LINE__, __METHOD__, 10);
			//When installer is enabled, just display down for maintenance message to user if they try to login.
			$error_message = TTi18n::gettext('%1 is currently undergoing maintenance. We apologize for any inconvenience this may cause, please try again later.', array(APPLICATION_NAME) );
			$validator_obj = new Validator();
			$validator_stats = array('total_records' => 1, 'valid_records' => 0 );
			$validator_obj->isTrue( 'user_name', FALSE, $error_message );
			$validator = array();
			$validator[0] = $validator_obj->getErrorsArray();
			return $this->returnHandler( FALSE, 'VALIDATION', TTi18n::getText('INVALID DATA'), $validator, $validator_stats );
		}

		if ( isset($config_vars['other']['web_session_expire']) AND $config_vars['other']['web_session_expire'] != '' ) {
			$authentication->setEnableExpireSession( (int)$config_vars['other']['web_session_expire'] );
		}

		if ( $authentication->Login($user_name, $password, $type) === TRUE ) {
			$retval = $authentication->getSessionId();
			Debug::text('Success, Session ID: '. $retval, __FILE__, __LINE__, __METHOD__, 10);
			return $retval;
		} else {
			$validator_obj = new Validator();
			$validator_stats = array('total_records' => 1, 'valid_records' => 0 );

			$error_column = 'user_name';
			$error_message = TTi18n::gettext('User Name or Password is incorrect');

			//Get company status from user_name, so we can display messages for ONHOLD/Cancelled accounts.
			$clf = TTnew( 'CompanyListFactory' );
			$clf->getByUserName( $user_name );
			if ( $clf->getRecordCount() > 0 ) {
				$c_obj = $clf->getCurrent();
				if ( $c_obj->getStatus() == 20 ) {
					$error_message = TTi18n::gettext('Sorry, your company\'s account has been placed ON HOLD, please contact customer support immediately');
				} elseif ( $c_obj->getStatus() == 23 ) {
					$error_message = TTi18n::gettext('Sorry, your trial period has expired, please contact our sales department to reactivate your account');
				} elseif ( $c_obj->getStatus() == 28 ) {
					if ( $c_obj->getMigrateURL() != '' ) {
						$error_message = TTi18n::gettext('To better serve our customers your account has been migrated, please update your bookmarks to use the following URL from now on') . ': ' . 'http://'. $c_obj->getMigrateURL();
					} else {
						$error_message = TTi18n::gettext('To better serve our customers your account has been migrated, please contact customer support immediately.');
					}
				} elseif ( $c_obj->getStatus() == 30 ) {
					$error_message = TTi18n::gettext('Sorry, your company\'s account has been CANCELLED, please contact customer support if you believe this is an error');
				} else {
					$ulf = TTnew( 'UserListFactory' );
					$ulf->getByUserName( $user_name );
					if ( $ulf->getRecordCount() == 1 ) {
						$u_obj = $ulf->getCurrent();

						if ( $u_obj->checkPassword($password, FALSE) == TRUE ) {
							if ( $u_obj->isFirstLogin() == TRUE AND $u_obj->isCompromisedPassword() == TRUE ) {
								$error_message = TTi18n::gettext('Welcome to %1, since this is your first time logging in, we ask that you change your password to something more secure', array(APPLICATION_NAME) );
								$error_column = 'password';
							} elseif ( $u_obj->isPasswordPolicyEnabled() == TRUE ) {
								if ( $u_obj->isCompromisedPassword() == TRUE ) {
									$error_message = TTi18n::gettext('Due to your company\'s password policy, your password must be changed immediately');
									$error_column = 'password';
								} elseif(  $u_obj->checkPasswordAge() == FALSE ) {
									//Password policy is enabled, confirm users password has not exceeded maximum age.
									//Make sure we confirm that the password is in fact correct, but just expired.
									$error_message = TTi18n::gettext('Your password has exceeded its maximum age specified by your company\'s password policy and must be changed immediately');
									$error_column = 'password';
								}
							}
						}
					}
					unset($ulf, $u_obj);
				}
			}

			$validator_obj->isTrue( $error_column, FALSE, $error_message );

			$validator[0] = $validator_obj->getErrorsArray();

			return $this->returnHandler( FALSE, 'VALIDATION', TTi18n::getText('INVALID DATA'), $validator, $validator_stats );
		}

		return $this->returnHandler( FALSE );
	}

	function newSession( $user_id, $client_id = NULL, $ip_address = NULL ) {
		global $authentication;

		if ( is_object($authentication) AND $authentication->getSessionID() != '' ) {
			Debug::text('Session ID: '. $authentication->getSessionID(), __FILE__, __LINE__, __METHOD__, 10);

			if ( $this->getPermissionObject()->Check('company', 'view') AND $this->getPermissionObject()->Check('company', 'login_other_user') ) {
				if ( !is_numeric( $user_id ) ) { //If username is used, lookup user_id
					Debug::Text('Lookup User ID by UserName: '. $user_id, __FILE__, __LINE__, __METHOD__, 10);
					$ulf = TTnew( 'UserListFactory' );
					$ulf->getByUserName( trim($user_id) );
					if ( $ulf->getRecordCount() == 1 ) {
						$user_id = $ulf->getCurrent()->getID();
					}
				}

				$ulf = TTnew( 'UserListFactory' );
				$ulf->getByIdAndStatus( (int)$user_id, 10 );  //Can only switch to Active employees
				if ( $ulf->getRecordCount() == 1 ) {
					$new_session_user_obj = $ulf->getCurrent();

					Debug::Text('Login as different user: '. $user_id .' IP Address: '. $ip_address, __FILE__, __LINE__, __METHOD__, 10);
					$new_session_id = $authentication->newSession( $user_id, $ip_address );

					$retarr = array(
									'session_id' => $new_session_id,
									'url' => Misc::getHostName(FALSE).Environment::getBaseURL(), //Don't include the port in the hostname, otherwise it can cause problems when forcing port 443 but not using 'https'.
									);

					//Add entry in source *AND* destination user log describing who logged in.
					//Source user log, showing that the source user logged in as someone else.
					TTLog::addEntry( $this->getCurrentUserObject()->getId(), 100, TTi18n::getText('Override Login').': '. TTi18n::getText('SourceIP').': '. $authentication->getIPAddress() .' '. TTi18n::getText('SessionID') .': '.$authentication->getSessionID() .' '.	TTi18n::getText('To Employee').': '. $new_session_user_obj->getFullName() .'('.$user_id.')', $this->getCurrentUserObject()->getId(), 'authentication');

					//Destination user log, showing the destination user was logged in *by* someone else.
					TTLog::addEntry( $user_id, 100, TTi18n::getText('Override Login').': '. TTi18n::getText('SourceIP').': '. $authentication->getIPAddress() .' '. TTi18n::getText('SessionID') .': '.$authentication->getSessionID() .' '.  TTi18n::getText('By Employee').': '. $this->getCurrentUserObject()->getFullName() .'('.$user_id.')', $user_id, 'authentication');

					return $this->returnHandler( $retarr );
				}
			}
		}

		return FALSE;
	}

	//Accepts user_id or user_name.
	function switchUser( $user_id ) {
		global $authentication;

		if ( is_object($authentication) AND $authentication->getSessionID() != '' ) {
			Debug::text('Session ID: '. $authentication->getSessionID(), __FILE__, __LINE__, __METHOD__, 10);

			if ( $this->getPermissionObject()->Check('company', 'view') AND $this->getPermissionObject()->Check('company', 'login_other_user') ) {
				if ( !is_numeric( $user_id ) ) { //If username is used, lookup user_id
					Debug::Text('Lookup User ID by UserName: '. $user_id, __FILE__, __LINE__, __METHOD__, 10);
					$ulf = TTnew( 'UserListFactory' );
					$ulf->getByUserName( trim($user_id) );
					if ( $ulf->getRecordCount() == 1 ) {
						$user_id = $ulf->getCurrent()->getID();
					}
				}

				$ulf = TTnew( 'UserListFactory' );
				$ulf->getByIdAndStatus( (int)$user_id, 10 );  //Can only switch to Active employees
				if ( $ulf->getRecordCount() == 1 ) {
					Debug::Text('Login as different user: '. $user_id, __FILE__, __LINE__, __METHOD__, 10);
					$authentication->changeObject( $user_id );

					//Add entry in source *AND* destination user log describing who logged in.
					//Source user log, showing that the source user logged in as someone else.
					TTLog::addEntry( $this->getCurrentUserObject()->getId(), 100, TTi18n::getText('Override Login').': '. TTi18n::getText('SourceIP').': '. $authentication->getIPAddress() .' '. TTi18n::getText('SessionID') .': '.$authentication->getSessionID() .' '.	TTi18n::getText('To Employee').': '. $authentication->getObject()->getFullName() .'('.$user_id.')', $this->getCurrentUserObject()->getId(), 'authentication');

					//Destination user log, showing the destination user was logged in *by* someone else.
					TTLog::addEntry( $user_id, 100, TTi18n::getText('Override Login').': '. TTi18n::getText('SourceIP').': '. $authentication->getIPAddress() .' '. TTi18n::getText('SessionID') .': '.$authentication->getSessionID() .' '.  TTi18n::getText('By Employee').': '. $this->getCurrentUserObject()->getFullName() .'('.$user_id.')', $user_id, 'authentication');

					return TRUE;
				} else {
					Debug::Text('User is likely not active: '. $user_id, __FILE__, __LINE__, __METHOD__, 10);
				}
			}
		}

		return FALSE;
	}

	function Logout() {
		global $authentication;

		if ( is_object($authentication) AND $authentication->getSessionID() != '' ) {
			Debug::text('Logging out session ID: '. $authentication->getSessionID(), __FILE__, __LINE__, __METHOD__, 10);

			return $authentication->Logout();
		}

		return FALSE;
	}

	function getSessionIdle() {
		global $config_vars;

		if ( isset($config_vars['other']['web_session_timeout']) AND $config_vars['other']['web_session_timeout'] != '' ) {
			return (int)$config_vars['other']['web_session_timeout'];
		} else {
			$authentication = new Authentication();
			return $authentication->getIdle();
		}
	}

	function isLoggedIn( $touch_updated_date = TRUE, $type = 'USER_NAME' ) {
		global $authentication, $config_vars;

		$session_id = getSessionID();

		if ( $session_id != '' ) {
			$authentication = new Authentication();

			Debug::text('AMF Session ID: '. $session_id .' Source IP: '. Misc::getRemoteIPAddress() .' Touch Updated Date: '. (int)$touch_updated_date, __FILE__, __LINE__, __METHOD__, 10);
			if ( isset($config_vars['other']['web_session_timeout']) AND $config_vars['other']['web_session_timeout'] != '' ) {
				$authentication->setIdle( (int)$config_vars['other']['web_session_timeout'] );
			}
			if ( $authentication->Check( $session_id, $type, $touch_updated_date ) === TRUE ) {
				return TRUE;
			}
		}

		return FALSE;
	}

	function getCurrentUserName() {
		if ( is_object( $this->getCurrentUserObject() ) ) {
			return $this->returnHandler( $this->getCurrentUserObject()->getUserName() );
		}

		return $this->returnHandler( FALSE );
	}
	function getCurrentUser() {
		if ( is_object( $this->getCurrentUserObject() ) ) {
			return $this->returnHandler( $this->getCurrentUserObject()->getObjectAsArray( array( 'id' => TRUE, 'company_id' => TRUE, 'currency_id' => TRUE, 'permission_control_id' => TRUE, 'pay_period_schedule_id' => TRUE, 'policy_group_id' => TRUE, 'employee_number' => TRUE, 'user_name' => TRUE, 'phone_id' => TRUE, 'first_name' => TRUE, 'middle_name' => TRUE, 'last_name' => TRUE, 'full_name' => TRUE, 'city' => TRUE, 'province' => TRUE, 'country' => TRUE, 'longitude' => TRUE, 'latitude' => TRUE, 'work_phone' => TRUE, 'home_phone' => TRUE, 'work_email' => TRUE, 'home_email' => TRUE, 'feedback_rating' => TRUE, 'last_login_date' => TRUE, 'created_date' => TRUE, 'is_owner' => TRUE, 'is_child' => TRUE ) ) );
		}

		return $this->returnHandler( FALSE );
	}

	function getCurrentCompany() {
		if ( is_object( $this->getCurrentCompanyObject() ) ) {
			return $this->returnHandler( $this->getCurrentCompanyObject()->getObjectAsArray( array('id' => TRUE, 'name' => TRUE, 'industry' => TRUE, 'city' => TRUE, 'province' => TRUE, 'country' => TRUE, 'work_phone' => TRUE, 'application_build' => TRUE, 'is_setup_complete' => TRUE, 'total_active_days' => TRUE, 'created_date' => TRUE, 'latitude' => TRUE, 'longitude' => TRUE ) ) );
		}

		return $this->returnHandler( FALSE );
	}

	function getCurrentUserPreference() {
		if ( is_object( $this->getCurrentUserObject() ) AND is_object( $this->getCurrentUserObject()->getUserPreferenceObject() ) ) {
			return $this->returnHandler( $this->getCurrentUserObject()->getUserPreferenceObject()->getObjectAsArray() );
		}

		return $this->returnHandler( FALSE );
	}

	//Functions that can be called before the API client is logged in.
	//Mainly so the proper loading/login page can be displayed.
	function getProduction() {
		return PRODUCTION;
	}
	function getApplicationName() {
		return APPLICATION_NAME;
	}
	function getApplicationVersion() {
		return APPLICATION_VERSION;
	}
	function getApplicationVersionDate() {
		return APPLICATION_VERSION_DATE;
	}
	function getApplicationBuild() {
		return APPLICATION_BUILD;
	}
	function getOrganizationName() {
		return ORGANIZATION_NAME;
	}
	function getOrganizationURL() {
		return ORGANIZATION_URL;
	}
	function isApplicationBranded() {
		global $config_vars;

		if ( isset($config_vars['branding']['application_name']) ) {
			return TRUE;
		}

		return FALSE;
	}
	function isPoweredByLogoEnabled() {
		global $config_vars;

		if ( isset($config_vars['branding']['disable_powered_by_logo']) AND $config_vars['branding']['disable_powered_by_logo'] == TRUE ) {
			return FALSE;
		}

		return TRUE;
	}

	function getLocale( $language = NULL, $country = NULL ) {
		$language = Misc::trimSortPrefix( $language );
		if ( $language == '' AND is_object( $this->getCurrentUserObject() ) AND is_object($this->getCurrentUserObject()->getUserPreferenceObject()) ) {
			$language = $this->getCurrentUserObject()->getUserPreferenceObject()->getLanguage();
		}
		if ( $country == '' AND is_object( $this->getCurrentUserObject() ) ) {
			$country = $this->getCurrentUserObject()->getCountry();
		}

		if ( $language != '' ) {
			TTi18n::setLanguage( $language );
		}
		if ( $country != '' ) {
			TTi18n::setCountry( $country );
		}
		TTi18n::setLocale(); //Sets master locale

		//$retval = str_replace('.UTF-8', '', TTi18n::getLocale() );
		$retval = TTi18n::getNormalizedLocale();

		Debug::text('Locale: '. $retval .' Language: '. $language, __FILE__, __LINE__, __METHOD__, 10);
		return $retval;
	}

	function getSystemLoad() {
		return Misc::getSystemLoad();
	}

	function getHTTPHost() {
		return $_SERVER['HTTP_HOST'];
	}

	function getCompanyName() {
		//Get primary company data needs to be used when user isn't logged in as well.
		$clf = TTnew( 'CompanyListFactory' );
		$clf->getByID( PRIMARY_COMPANY_ID );
		Debug::text('Primary Company ID: '. PRIMARY_COMPANY_ID, __FILE__, __LINE__, __METHOD__, 10);
		if ( $clf->getRecordCount() == 1 ) {
			return $clf->getCurrent()->getName();
		}

		Debug::text('  ERROR: Primary Company does not exist!', __FILE__, __LINE__, __METHOD__, 10);
		return FALSE;
	}

	//Returns all login data required in a single call for optimization purposes.
	function getPreLoginData( $api = NULL ) {
		global $config_vars;

		if ( isset($_GET['disable_db']) OR ( isset($config_vars['other']['installer_enabled']) AND $config_vars['other']['installer_enabled'] == 1 ) ) {
			Debug::text('WARNING: Installer is enabled... Normal logins are disabled!', __FILE__, __LINE__, __METHOD__, 10);
			return array(
				'primary_company_id' => PRIMARY_COMPANY_ID, //Needed for some branded checks.
				'primary_company_name' => 'N/A',
				'application_version' => $this->getApplicationVersion(),
				'application_version_date' => $this->getApplicationVersionDate(),
				'application_build' => $this->getApplicationBuild(),
				'powered_by_logo_enabled' => $this->isPoweredByLogoEnabled(),
				'http_host' => $this->getHTTPHost(),
				'production' => $this->getProduction(),
				'base_url' => Environment::getBaseURL(),
				'cookie_base_url' => Environment::getCookieBaseURL(),
				'api_base_url' => Environment::getAPIBaseURL(),
				'language_options' => Misc::addSortPrefix( TTi18n::getLanguageArray() ),
				//Make sure locale is set properly before this function is called, either in api.php or APIGlobal.js.php for example.
				'enable_default_language_translation' => ( isset($config_vars['other']['enable_default_language_translation']) ) ? $config_vars['other']['enable_default_language_translation'] : FALSE,

				'language' => TTi18n::getLanguage(),
				'locale' => TTi18n::getNormalizedLocale(), //Needed for HTML5 interface to load proper translation file.
			);
		}

		$company_name = $this->getCompanyName();
		if ( $company_name == '' ) {
			$company_name = 'N/A';
		}

		return array(
				'primary_company_id' => PRIMARY_COMPANY_ID, //Needed for some branded checks.
				'primary_company_name' => $company_name,
				'base_url' => Environment::getBaseURL(),
				'cookie_base_url' => Environment::getCookieBaseURL(),
				'api_url' => Environment::getAPIURL( $api ),
				'api_base_url' => Environment::getAPIBaseURL( $api ),
				'api_json_url' => Environment::getAPIURL( 'json' ),
				'images_url' => Environment::getImagesURL(),
				'powered_by_logo_enabled' => $this->isPoweredByLogoEnabled(),
				'is_application_branded' => $this->isApplicationBranded(),
				'application_name' => $this->getApplicationName(),
				'organization_name' => $this->getOrganizationName(),
				'organization_url' => $this->getOrganizationURL(),
				'copyright_notice' => COPYRIGHT_NOTICE,
				'web_session_expire' => ( isset($config_vars['other']['web_session_expire']) AND $config_vars['other']['web_session_expire'] != '' ) ? (bool)$config_vars['other']['web_session_expire'] : FALSE, //If TRUE then session expires when browser closes.
				'http_host' => $this->getHTTPHost(),
				'is_ssl' => Misc::isSSL(),
				'production' => $this->getProduction(),
				'application_version' => $this->getApplicationVersion(),
				'application_version_date' => $this->getApplicationVersionDate(),
				'application_build' => $this->getApplicationBuild(),
				'is_logged_in' => $this->isLoggedIn(),
				'session_idle_timeout' => $this->getSessionIdle(),
				'footer_left_html' => ( isset($config_vars['other']['footer_left_html']) AND $config_vars['other']['footer_left_html'] != '' ) ? $config_vars['other']['footer_left_html'] : FALSE,
				'footer_right_html' => ( isset($config_vars['other']['footer_right_html']) AND $config_vars['other']['footer_right_html'] != '' ) ? $config_vars['other']['footer_right_html'] : FALSE,
				'language_options' => Misc::addSortPrefix( TTi18n::getLanguageArray() ),
				//Make sure locale is set properly before this function is called, either in api.php or APIGlobal.js.php for example.
				'enable_default_language_translation' => ( isset($config_vars['other']['enable_default_language_translation']) ) ? $config_vars['other']['enable_default_language_translation'] : FALSE,
				'language' => TTi18n::getLanguage(),
				'locale' => TTi18n::getNormalizedLocale(), //Needed for HTML5 interface to load proper translation file.

				'map_api_key' => ( isset($config_vars['map']['api_key']) AND $config_vars['map']['api_key'] != '' ) ? $config_vars['map']['map_api_key'] : '',
				'map_provider' => isset($config_vars['map']['provider'] ) ? $config_vars['map']['provider'] : '',

		);
	}


	//Function that HTML5 interface can call when an irrecoverable error or uncaught exception is triggered.
	function sendErrorReport( $data = NULL, $screenshot = NULL ) {
		$rl = TTNew('RateLimit');
		$rl->setID( 'error_report_'. Misc::getRemoteIPAddress() );
		$rl->setAllowedCalls( 20 );
		$rl->setTimeFrame( 900 ); //15 minutes
		if ( $rl->check() == FALSE ) {
			Debug::Text('Excessive error reports... Preventing error reports from: '. Misc::getRemoteIPAddress() .' for up to 15 minutes...', __FILE__, __LINE__, __METHOD__, 10);
			return APPLICATION_BUILD;
		}

		$attachments = NULL;
		if ( $screenshot != '' ) {
			$attachments[] = array( 'file_name' => 'screenshot.png', 'mime_type' => 'image/png', 'data' => base64_decode( $screenshot ) );
		}

		if ( defined( 'FAIRNESS_JSON_API' ) == TRUE ) {
			$subject = TTi18n::gettext('HTML5 Error Report');
		} else {
			$subject = TTi18n::gettext('Flex Error Report');
		}

		$data = 'IP Address: '. Misc::getRemoteIPAddress() ."\nServer Version: ". APPLICATION_BUILD ."\n\n". $data;

		//return APPLICATION_BUILD so JS can check if its correct and notify the user to refresh/clear cache.
		return APPLICATION_BUILD;
	}

	/**
	 * Allows user who isn't logged in to change their password.
	 * @param string $user_name
	 * @param string $current_password
	 * @param string $new_password
	 * @param string $new_password2
	 * @param string $type
	 * @return bool
	 */
	function changePassword( $user_name, $current_password, $new_password, $new_password2 ) {
		$rl = TTNew('RateLimit');
		$rl->setID( 'authentication_'. Misc::getRemoteIPAddress() );
		$rl->setAllowedCalls( 20 );
		$rl->setTimeFrame( 900 ); //15 minutes

		$ulf = TTnew( 'UserListFactory' );
		$ulf->getByUserName( $user_name );
		if ( $ulf->getRecordCount() == 1 ) {
			$u_obj = $ulf->getCurrent();
			if ( $rl->check() == FALSE ) {
				Debug::Text('Excessive failed password attempts... Preventing password change from: '. Misc::getRemoteIPAddress() .' for up to 15 minutes...', __FILE__, __LINE__, __METHOD__, 10);
				sleep(5); //Excessive password attempts, sleep longer.
				$u_obj->Validator->isTrue(		'current_password',
										   		FALSE,
										   		TTi18n::gettext('Current password is incorrect') .' (z)' );
			} else {
				if ( $u_obj->getCompanyObject()->getStatus() == 10 ) {
					Debug::text('Attempting to change password for: '. $user_name, __FILE__, __LINE__, __METHOD__, 10);

					if ( $current_password != '' ) {
						if ( $u_obj->checkPassword($current_password, FALSE) !== TRUE ) { //Disable password policy checking on current password.
							Debug::text('Password check failed! Attempt: '. $rl->getAttempts(), __FILE__, __LINE__, __METHOD__, 10);
							sleep( ($rl->getAttempts() * 0.5) ); //If password is incorrect, sleep for some time to slow down brute force attacks.
							$u_obj->Validator->isTrue(	'current_password',
														FALSE,
														TTi18n::gettext('Current password is incorrect') );
						}
					} else {
						Debug::Text('Current password not specified', __FILE__, __LINE__, __METHOD__, 10);
						$u_obj->Validator->isTrue(	'current_password',
												FALSE,
												TTi18n::gettext('Current password is incorrect') );
					}

					if ( $current_password == $new_password ) {
						$u_obj->Validator->isTrue(	'password',
												FALSE,
												TTi18n::gettext('New password must be different than current password') );
					} else {
						if ( $new_password != '' OR $new_password2 != ''  ) {
							if ( $new_password == $new_password2 ) {
								$u_obj->setPassword($new_password);
							} else {
								$u_obj->Validator->isTrue(	'password',
														FALSE,
														TTi18n::gettext('Passwords don\'t match') );
							}
						} else {
							$u_obj->Validator->isTrue(	'password',
													FALSE,
													TTi18n::gettext('Passwords don\'t match') );
						}
					}
				}
			}

					if ( $u_obj->isValid() ) {
						//This should force the updated_by field to match the user changing their password,
						//  so we know now to ask the user to change their password again, since they were the last ones to do so.
						global $current_user;
						$current_user = $u_obj;

						TTLog::addEntry( $u_obj->getID(), 20, TTi18n::getText('Password - Web (Password Policy)'), NULL, $u_obj->getTable() );
					$rl->delete(); //Clear failed password rate limit upon successful login.

						$retval = $u_obj->Save();

						unset($current_user);

						return $this->returnHandler( $retval ); //Single valid record
					} else {
						return $this->returnHandler( FALSE, 'VALIDATION', TTi18n::getText('INVALID DATA'), $u_obj->Validator->getErrorsArray(), array('total_records' => 1, 'valid_records' => 0) );
					}
				}

		return $this->returnHandler( FALSE );
	}

	function resetPassword( $email ) {
		//Debug::setVerbosity( 11 );
		$rl = TTNew('RateLimit');
		$rl->setID( 'password_reset_'. Misc::getRemoteIPAddress() );
		$rl->setAllowedCalls( 10 );
		$rl->setTimeFrame( 900 ); //15 minutes

		$validator = new Validator();

		Debug::Text('Email: '. $email, __FILE__, __LINE__, __METHOD__, 10);
		if ( $rl->check() == FALSE ) {
			Debug::Text('Excessive reset password attempts... Preventing resets from: '. Misc::getRemoteIPAddress() .' for up to 15 minutes...', __FILE__, __LINE__, __METHOD__, 10);
			sleep(5); //Excessive password attempts, sleep longer.
			$validator->isTrue('email', FALSE, TTi18n::getText('Email address was not found in our database (z)') );
		} else {
			$ulf = TTnew( 'UserListFactory' );
			$ulf->getByHomeEmailOrWorkEmail( $email );
			if ( $ulf->getRecordCount() == 1 ) {
				$user_obj = $ulf->getCurrent();
				if ( $user_obj->getStatus() == 10 ) { //Only allow password resets on active employees.
					//Check if company is using LDAP authentication, if so deny password reset.
					if ( $user_obj->getCompanyObject()->getLDAPAuthenticationType() == 0 ) {
						$user_obj->sendPasswordResetEmail();
						Debug::Text('Found USER! ', __FILE__, __LINE__, __METHOD__, 10);
						$rl->delete(); //Clear password reset rate limit upon successful login.
						return $this->returnHandler( array('email_sent' => 1, 'email' => $email ) );
					} else {
						Debug::Text('LDAP Authentication is enabled, password reset is disabled! ', __FILE__, __LINE__, __METHOD__, 10);
						$validator->isTrue('email', FALSE, TTi18n::getText('Please contact your administrator for instructions on changing your password.'). ' (LDAP)' );
					}
				} else {
					$validator->isTrue('email', FALSE, TTi18n::getText('Email address was not found in our database (b)') );
				}
			} else {
				//Error
				Debug::Text('DID NOT FIND USER! Returned: '. $ulf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
				$validator->isTrue('email', FALSE, TTi18n::getText('Email address was not found in our database (a)') );
			}

			Debug::text('Reset Password Failed! Attempt: '. $rl->getAttempts(), __FILE__, __LINE__, __METHOD__, 10);
			sleep( ($rl->getAttempts() * 0.5) ); //If email is incorrect, sleep for some time to slow down brute force attacks.
		}

		return $this->returnHandler( FALSE, 'VALIDATION', TTi18n::getText('INVALID DATA'), array('error' => $validator->getErrorsArray()), array('total_records' => 1, 'valid_records' => 0) );
	}

	/*
	 * Reset the password if users forgotten their password
	 * @param string $key
	 * @param string $password
	 * @param string $password2
	 * */
	function passwordReset( $key, $password, $password2 ) {
		$rl = TTNew('RateLimit');
		$rl->setID( 'password_reset_'. Misc::getRemoteIPAddress() );
		$rl->setAllowedCalls( 10 );
		$rl->setTimeFrame( 900 ); //15 minutes

		$validator = new Validator();
		if ( $rl->check() == FALSE ) {
			Debug::Text('Excessive password reset attempts... Preventing resets from: '. Misc::getRemoteIPAddress() .' for up to 15 minutes...', __FILE__, __LINE__, __METHOD__, 10);
			sleep(5); //Excessive password attempts, sleep longer.
		} else {
			$ulf = TTnew( 'UserListFactory' );
			Debug::Text('Key: '. $key, __FILE__, __LINE__, __METHOD__, 10);
			$ulf->getByPasswordResetKey( $key );
			if ( $ulf->getRecordCount() == 1 ) {
				Debug::Text('FOUND Password reset key! ', __FILE__, __LINE__, __METHOD__, 10);
				$user_obj = $ulf->getCurrent();
				if ( $user_obj->checkPasswordResetKey( $key ) == TRUE ) {
					//Make sure passwords match
					Debug::Text('Change Password Key: '. $key, __FILE__, __LINE__, __METHOD__, 10);
					if ( $password != '' AND trim($password) === trim($password2) ) {
						//Change password
						$user_obj->setPassword( $password ); //Password reset key is cleared when password is changed.
						if ( $user_obj->isValid() ) {
							$user_obj->Save(FALSE);
							Debug::Text('Password Change succesful!', __FILE__, __LINE__, __METHOD__, 10);

							//Logout all sessions for this user when password is successfully reset.
							$authentication = TTNew('Authentication');
							$authentication->logoutUser( $user_obj->getId() );
							unset($user_obj);

							return $this->returnHandler( TRUE );
						} else {
							$validator->merge( $user_obj->Validator ); //Make sure we display any validation errors like password too weak.
						}
					} else {
						$validator->isTrue('password', FALSE, TTi18n::getText('Passwords do not match') );
					}
					//Do this once a successful key is found, so the user can get as many password change attempts as needed.
					$rl->delete(); //Clear password reset rate limit upon successful reset.
				} else {
					Debug::Text('DID NOT FIND Valid Password reset key!', __FILE__, __LINE__, __METHOD__, 10);
					$validator->isTrue('password', FALSE, TTi18n::getText('Password reset key is invalid, please try resetting your password again.') );
				}
			} else {
				Debug::Text('DID NOT FIND Valid Password reset key! (b)', __FILE__, __LINE__, __METHOD__, 10);
				$validator->isTrue('password', FALSE, TTi18n::getText('Password reset key is invalid, please try resetting your password again.') .' (b)' );
			}

			Debug::text('Password Reset Failed! Attempt: '. $rl->getAttempts(), __FILE__, __LINE__, __METHOD__, 10);
			sleep( ($rl->getAttempts() * 0.5) ); //If email is incorrect, sleep for some time to slow down brute force attacks.
		}

		return $this->returnHandler( FALSE, 'VALIDATION', TTi18n::getText('INVALID DATA'), array('error' => $validator->getErrorsArray()), array('total_records' => 1, 'valid_records' => 0) );
	}

	//Ping function is also in APIMisc for when the session timesout is valid.
	//Ping no longer can tell if the session is timed-out, must use "isLoggedIn(FALSE)" instead.
	function Ping() {
		return TRUE;
	}
}
?>
