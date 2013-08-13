<?php
/*********************************************************************************
 * This file is part of "Fairness", a Payroll and Time Management program.
 * Fairness is Copyright 2013 Aydan Coscun (aydan.ayfer.coskun@gmail.com)
 * Portions of this software are Copyright (C) 2003 - 2013 TimeTrex Software Inc.
 * because Fairness is a fork of "TimeTrex Workforce Management" Software.
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
/*
 * $Revision: 2196 $
 * $Id: APINotification.class.php 2196 2008-10-14 16:08:54Z ipso $
 * $Date: 2008-10-14 09:08:54 -0700 (Tue, 14 Oct 2008) $
 */

/**
 * @package API\Core
 */
class APINotification extends APIFactory {
	protected $main_class = '';

	public function __construct() {
		parent::__construct(); //Make sure parent constructor is always called.

		return TRUE;
	}

	/**
	 * Returns array of notifications message to be displayed to the user.
	 * @param string $action Action that is being performed, possible values: 'login', 'preference', 'notification', 'pay_period'
	 * @return array
	 */
	function getNotifications( $action = FALSE ) {
		global $config_vars, $disable_database_connection;

		$retarr = FALSE;

		//Skip this step if disable_database_connection is enabled or the user is going through the installer still
		switch ( strtolower($action) ) {
			case 'login':
				if ( ( !isset($disable_database_connection) 							OR ( isset($disable_database_connection) 								AND $disable_database_connection != TRUE ) )
				 AND ( !isset($config_vars['other']['installer_enabled']) OR ( isset($config_vars['other']['installer_enabled'])	AND $config_vars['other']['installer_enabled'] != TRUE ) )
				)
				{
					//Get all system settings, so they can be used even if the user isn't logged in, such as the login page.
					$sslf = new SystemSettingListFactory();
					$system_settings = $sslf->getAllArray();
					unset($sslf);
				}

				$tmp_company_id = 1;
				if ( isset($config_vars['other']['primary_company_id']) )
					$tmp_company_id = $config_vars['other']['primary_company_id'];
				// Only show these warnings to the primary company if SOFTWARE_AS_SERVICE
				if ( ( ! SOFTWARE_AS_SERVICE OR $this->getCurrentCompanyObject()->getId() == $tmp_company_id ) AND DEMO_MODE == FALSE ) {

					//System Requirements not being met.
					if ( isset($system_settings['valid_install_requirements']) AND (int)$system_settings['valid_install_requirements'] == 0 ) {
						$retarr[] = array(
										  'delay' => -1, //0= Show until clicked, -1 = Show until next getNotifications call.
										  'bg_color' => '#FF0000', //Red
										  'message' => TTi18n::getText('WARNING: %1 system requirement check has failed! Please contact your %1 administrator immediately to re-run the %1 installer to correct the issue.', APPLICATION_NAME ),
										  'destination' => NULL,
										  );
					}

					//Check version mismatch
					if ( isset($system_settings['system_version']) AND APPLICATION_VERSION != $system_settings['system_version'] ) {
						$retarr[] = array(
										  'delay' => -1, //0= Show until clicked, -1 = Show until next getNotifications call.
										  'bg_color' => '#FF0000', //Red
										  'message' => TTi18n::getText('WARNING: %1 application version does not match database version. Please re-run the %1 installer to complete the upgrade process.', APPLICATION_NAME ),
										  'destination' => NULL,
										  );
					}

					if ( (time()-(int)APPLICATION_VERSION_DATE) > (86400*475) ) { //~1yr and 3mths
						$retarr[] = array(
										  'delay' => -1,
										  'bg_color' => '#FF0000', //Red
										  'message' => TTi18n::getText('WARNING: This %1 version (v%2) is severely out of date and may no longer be supported. Please upgrade to the latest version as soon as possible as invalid calculations may already be occurring.', array( APPLICATION_NAME, APPLICATION_VERSION ) ),
										  'destination' => NULL,
										  );
					}

					//New version available notification.
					if ( isset( $system_settings['new_version'] ) AND $system_settings['new_version'] == 1 ) {

						//Only display this every two weeks.
						$new_version_available_notification_arr = UserSettingFactory::getUserSetting( $this->getCurrentUserObject()->getID(), 'new_version_available_notification' );
						if ( !isset($new_version_available_notification_arr['value']) OR ( isset($new_version_available_notification_arr['value']) AND $new_version_available_notification_arr['value'] <= (time()-(86400*14)) ) ) {
							UserSettingFactory::setUserSetting( $this->getCurrentUserObject()->getID(), 'new_version_available_notification', time() );

							$retarr[] = array(
											  'delay' => -1,
											  'bg_color' => '#FFFF00', //Yellow
											  'message' => TTi18n::getText('NOTICE: A new version of %1 available, it is highly recommended that you upgrade as soon as possible. Click here to download the latest version.', array( APPLICATION_NAME ) ),
											  'destination' => 'https://github.com/Aydan/fairness',
											  );
						}
						unset($new_version_available_notification);
					}

					//Make sure CronJobs are running correctly.
					$cjlf = new CronJobListFactory();
					$cjlf->getMostRecentlyRun();
					if ( $cjlf->getRecordCount() > 0 ) {
						//Is last run job more then 48hrs old?
						$cj_obj = $cjlf->getCurrent();
						if ( PRODUCTION == TRUE AND DEMO_MODE == FALSE
							AND $cj_obj->getLastRunDate() < ( time()-172800 )
							AND $cj_obj->getCreatedDate() < ( time()-172800 ) ) {
						$retarr[] = array(
											  'delay' => -1,
											  'bg_color' => '#FF0000', //Red
											  'message' => TTi18n::getText('WARNING: Critical maintenance jobs have not run in the last 48hours. Please contact your %1 administrator immediately.', APPLICATION_NAME ),
											  'destination' => NULL,
											  );
						}
					}
					unset($cjlf, $cj_obj);

				}
				unset($tmp_company_id);

				//Check if we need to notify for major new version.
				$new_version_notification_arr = UserSettingFactory::getUserSetting( $this->getCurrentUserObject()->getID(), 'new_version_notification' );
				$tmp_value = "";
				if ( isset( $new_version_notification_arr['value'] ) ) $tmp_value = $new_version_notification_arr['value'];
				if (	DEMO_MODE == FALSE
						AND $this->getPermissionObject()->getLevel() >= 20 //Payroll Admin
						AND $this->getCurrentUserObject()->getCreatedDate() <= APPLICATION_VERSION_DATE
						AND ( ! $tmp_value OR ( $tmp_value AND Misc::MajorVersionCompare( APPLICATION_VERSION, $tmp_value, '>' ) ) ) ) {
					UserSettingFactory::setUserSetting( $this->getCurrentUserObject()->getID(), 'new_version_notification', APPLICATION_VERSION );

					$retarr[] = array(
										  'delay' => -1,
										  'bg_color' => '#FFFF00', //Yellow
										  'message' => TTi18n::getText('NOTICE: Your instance of %1 has been upgraded to v%2, click here to see whats new.', array( APPLICATION_NAME, APPLICATION_VERSION ) ),
										  'destination' => $config_vars['urls']['whats_new'],
										  );
				}
				unset($new_version_notification_arr);
				unset($tmp_value);

				//Check installer enabled.
				if ( isset($config_vars['other']['installer_enabled']) AND $config_vars['other']['installer_enabled'] == 1 ) {
					$retarr[] = array(
										  'delay' => -1,
										  'bg_color' => '#FF0000', //Red
										  'message' => TTi18n::getText('WARNING: %1 is currently in INSTALL MODE. Please go to your fairness.ini.php file and set "installer_enabled" to "FALSE".', APPLICATION_NAME ),
										  'destination' => NULL,
										  );
				}

				//Check if any pay periods are past their transaction date and not closed.
				if ( DEMO_MODE == FALSE AND $this->getPermissionObject()->Check('pay_period_schedule','enabled') AND $this->getPermissionObject()->Check('pay_period_schedule','view') ) {
					$pplf = TTnew('PayPeriodListFactory');
					$pplf->getByCompanyIdAndStatusAndTransactionDate( $this->getCurrentCompanyObject()->getId(), array(10,30), TTDate::getBeginDayEpoch( time() ) ); //Open or Post Adjustment pay periods.
					if ( $pplf->getRecordCount() > 0 ) {
						foreach( $pplf as $pp_obj ) {
							if ( $pp_obj->getCreatedDate() < (time()-(86400*40)) ) { //Ignore pay period schedules newer than 40 days. They are automatically closed after 45 days.
								$retarr[] = array(
													  'delay' => 0,
													  'bg_color' => '#FF0000', //Red
													  'message' => TTi18n::getText('WARNING: Pay periods past their transaction date have not been closed yet. It\'s critical that these pay periods are closed to prevent data loss, click here to close them now.'),
													  'destination' => array('menu_name' => 'Pay Periods'),
													  );
								break;
							}
						}
					}
					unset($pplf, $pp_obj);
				}

				//Check for unread messages
				$mclf = new MessageControlListFactory();
				$unread_messages = $mclf->getNewMessagesByCompanyIdAndUserId( $this->getCurrentCompanyObject()->getId(), $this->getCurrentUserObject()->getId() );
				Debug::text('UnRead Messages: '. $unread_messages, __FILE__, __LINE__, __METHOD__, 10);
				if ( $unread_messages > 0 ) {
					$retarr[] = array(
										  'delay' => 25,
										  'bg_color' => '#FFFF00', //Yellow
										  'message' => TTi18n::getText('NOTICE: You have %1 new message(s) waiting, click here to read them now.', $unread_messages ),
										  'destination' => array('menu_name' => 'Messages'),
										  );
				}
				unset($mclf, $unread_messages);

				if ( DEMO_MODE == FALSE ) {
					$elf = new ExceptionListFactory();
					$elf->getFlaggedExceptionsByUserIdAndPayPeriodStatus( $this->getCurrentUserObject()->getId(), 10 );
					$display_exception_flag = FALSE;
					if ( $elf->getRecordCount() > 0 ) {
						foreach($elf as $e_obj) {
							if ( $e_obj->getColumn('severity_id') == 30 ) {
								$display_exception_flag = 'red';
							}
							break;
						}
					}
					if ( isset($display_exception_flag) AND $display_exception_flag !== FALSE ) {
						Debug::Text('Exception Flag to Display: '. $display_exception_flag, __FILE__, __LINE__, __METHOD__, 10);
						$retarr[] = array(
											  'delay' => 30,
											  'bg_color' => '#FFFF00', //Yellow
											  'message' => TTi18n::getText('NOTICE: You have critical severity exceptions pending, click here to view them now.'),
											  'destination' => array('menu_name' => 'Exceptions'),
											  );
					}
					unset($elf, $e_obj, $display_exception_flag);
				}

				if ( DEMO_MODE == FALSE
						AND $this->getPermissionObject()->getLevel() >= 20 //Payroll Admin
						AND ( $this->getCurrentUserObject()->getWorkEmail() == '' AND $this->getCurrentUserObject()->getHomeEmail() == '' ) ) {
					$retarr[] = array(
										  'delay' => 30,
										  'bg_color' => '#FF0000', //Red
										  'message' => TTi18n::getText('WARNING: Please click here and enter an email address for your account, this is required to receive important notices and prevent your account from being locked out.'),
										  'destination' => array('menu_name' => 'Contact Information'),
										  );
				}

				break;
			default:
				break;
		}

		//Check timezone is proper.
		$current_user_prefs = $this->getCurrentUserObject()->getUserPreferenceObject();
		if ( $current_user_prefs->setDateTimePreferences() == FALSE ) {
			//Setting timezone failed, alert user to this fact.
			//WARNING: %1 was unable to set your time zone. Please contact your %1 administrator immediately.{/t} {if $permission->Check('company','enabled') AND $permission->Check('company','edit_own')}<a href="">{t}For more information please click here.{/t}</a>{/if}
			if ( $this->getPermissionObject()->Check('company','enabled') AND $this->getPermissionObject()->Check('company','edit_own') ) {
				$destination_url = $config_vars['urls']['timezone_error'];
				$sub_message = TTi18n::getText('For more information please click here.');
			} else {
				$destination_url = NULL;
				$sub_message = NULL;
			}

			$retarr[] = array(
								  'delay' => -1,
								  'bg_color' => '#FF0000', //Red
								  'message' => TTi18n::getText('WARNING: %1 was unable to set your time zone. Please contact your %1 administrator immediately.', APPLICATION_NAME ).' '. $sub_message,
								  'destination' => $destination_url,
								  );
			unset($destination_url, $sub_message );
		}

		return $retarr;

	}
}
?>
