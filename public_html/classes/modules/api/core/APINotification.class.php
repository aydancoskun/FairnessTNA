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
 * @package API\Core
 */
class APINotification extends APIFactory
{
    protected $main_class = '';

    public function __construct()
    {
        parent::__construct(); //Make sure parent constructor is always called.

        return true;
    }

    /**
     * Returns array of notifications message to be displayed to the user.
     * @param string $action Action that is being performed, possible values: 'login', 'preference', 'notification', 'pay_period'
     * @return array
     */
    public function getNotifications($action = false)
    {
        global $config_vars, $disable_database_connection;

        $retarr = false;

        //Skip this step if disable_database_connection is enabled or the user is going through the installer still
        switch (strtolower($action)) {
            case 'login':
                if ((!isset($disable_database_connection) or (isset($disable_database_connection) and $disable_database_connection != true))
                    and (!isset($config_vars['other']['installer_enabled']) or (isset($config_vars['other']['installer_enabled']) and $config_vars['other']['installer_enabled'] != true))
                ) {
                    //Get all system settings, so they can be used even if the user isn't logged in, such as the login page.
                    $sslf = new SystemSettingListFactory();
                    $system_settings = $sslf->getAllArray();
                }
                unset($sslf);

                //System Requirements not being met.
                if (isset($system_settings['valid_install_requirements']) and (int)$system_settings['valid_install_requirements'] == 0) {
                    $retarr[] = array(
                        'delay' => -1, //0= Show until clicked, -1 = Show until next getNotifications call.
                        'bg_color' => '#FF0000', //Red
                        'message' => TTi18n::getText('WARNING: %1 system requirement check has failed! Please contact your %1 administrator immediately to re-run the %1 installer to correct the issue.', APPLICATION_NAME),
                        'destination' => null,
                    );
                }

                //Check version mismatch
                if (isset($system_settings['system_version']) and APPLICATION_VERSION != $system_settings['system_version']) {
                    $retarr[] = array(
                        'delay' => -1, //0= Show until clicked, -1 = Show until next getNotifications call.
                        'bg_color' => '#FF0000', //Red
                        'message' => TTi18n::getText('WARNING: %1 application version does not match database version. Please re-run the %1 installer to complete the upgrade process.', APPLICATION_NAME),
                        'destination' => null,
                    );
                }

                //Only display message to the primary company.
                if ((
                        ((time() - (int)APPLICATION_VERSION_DATE) > (86400 * 365)) and ($this->getCurrentCompanyObject()->getId() == 1 //~1yr
                            or (isset($config_vars['other']['primary_company_id']) and $this->getCurrentCompanyObject()->getId() == $config_vars['other']['primary_company_id']))
                    )
                    or ((time() - (int)APPLICATION_VERSION_DATE) > (86400 * 760)) //2yrs+30days, show to all companies.
                ) {
                    $retarr[] = array(
                        'delay' => -1,
                        'bg_color' => '#FF0000', //Red
                        'message' => TTi18n::getText('WARNING: This %1 version (v%2) is very out of date and may no longer calculate properly. Please upgrade to the latest version as soon as possible.', array(APPLICATION_NAME, APPLICATION_VERSION)),
                        'destination' => null,
                    );
                }


                //Check installer enabled.
                if (isset($config_vars['other']['installer_enabled']) and $config_vars['other']['installer_enabled'] == 1) {
                    $retarr[] = array(
                        'delay' => -1,
                        'bg_color' => '#FF0000', //Red
                        'message' => TTi18n::getText('WARNING: %1 is currently in INSTALL MODE. Please go to your fairness.ini.php file and set "installer_enabled" to "FALSE".', APPLICATION_NAME),
                        'destination' => null,
                    );
                }

                //Make sure CronJobs are running correctly.
                $cjlf = new CronJobListFactory();
                $cjlf->getMostRecentlyRun();
                if ($cjlf->getRecordCount() > 0) {
                    //Is last run job more then 48hrs old?
                    $cj_obj = $cjlf->getCurrent();

                    if (PRODUCTION == true
                        and $cj_obj->getLastRunDate() < (time() - 172800)
                        and $cj_obj->getCreatedDate() < (time() - 172800)
                    ) {
                        $retarr[] = array(
                            'delay' => -1,
                            'bg_color' => '#FF0000', //Red
                            'message' => TTi18n::getText('WARNING: Critical maintenance jobs have not run in the last 48hours. Please contact your %1 administrator immediately.', APPLICATION_NAME),
                            'destination' => null,
                        );
                    }
                }
                unset($cjlf, $cj_obj);

                //Check if any pay periods are past their transaction date and not closed.
                if ($this->getPermissionObject()->Check('pay_period_schedule', 'enabled') and $this->getPermissionObject()->Check('pay_period_schedule', 'view')) {
                    $pplf = TTnew('PayPeriodListFactory');
                    $pplf->getByCompanyIdAndStatusAndTransactionDate($this->getCurrentCompanyObject()->getId(), array(10, 12, 30), TTDate::getBeginDayEpoch(time())); //Open or Locked or Post Adjustment pay periods.
                    if ($pplf->getRecordCount() > 0) {
                        foreach ($pplf as $pp_obj) {
                            if (is_object($pp_obj->getPayPeriodScheduleObject()) and $pp_obj->getPayPeriodScheduleObject()->getCreatedDate() < (time() - (86400 * 40))) { //Ignore pay period schedules newer than 40 days. They automatically start being closed after 45 days.
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

                //CHeck for unread messages
                $mclf = new MessageControlListFactory();
                $unread_messages = $mclf->getNewMessagesByCompanyIdAndUserId($this->getCurrentCompanyObject()->getId(), $this->getCurrentUserObject()->getId());
                Debug::text('UnRead Messages: ' . $unread_messages, __FILE__, __LINE__, __METHOD__, 10);
                if ($unread_messages > 0) {
                    $retarr[] = array(
                        'delay' => 25,
                        'bg_color' => '#FFFF00', //Yellow
                        'message' => TTi18n::getText('NOTICE: You have %1 new message(s) waiting, click here to read them now.', $unread_messages),
                        'destination' => array('menu_name' => 'Messages'),
                    );
                }
                unset($mclf, $unread_messages);

                $elf = new ExceptionListFactory();
                $elf->getFlaggedExceptionsByUserIdAndPayPeriodStatus($this->getCurrentUserObject()->getId(), 10);
                $display_exception_flag = false;
                if ($elf->getRecordCount() > 0) {
                    foreach ($elf as $e_obj) {
                        if ($e_obj->getColumn('severity_id') == 30) {
                            $display_exception_flag = 'red';
                        }
                        break;
                    }
                }
                if (isset($display_exception_flag) and $display_exception_flag !== false) {
                    Debug::Text('Exception Flag to Display: ' . $display_exception_flag, __FILE__, __LINE__, __METHOD__, 10);
                    $retarr[] = array(
                        'delay' => 30,
                        'bg_color' => '#FFFF00', //Yellow
                        'message' => TTi18n::getText('NOTICE: You have critical severity exceptions pending, click here to view them now.'),
                        'destination' => array('menu_name' => 'Exceptions'),
                    );
                }
                unset($elf, $e_obj, $display_exception_flag);

                if ($this->getPermissionObject()->getLevel() >= 20 //Payroll Admin
                    and ($this->getCurrentUserObject()->getWorkEmail() == '' and $this->getCurrentUserObject()->getHomeEmail() == '')
                ) {
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
        if ($current_user_prefs->setDateTimePreferences() == false) {
            //Setting timezone failed, alert user to this fact.
            if ($this->getPermissionObject()->Check('company', 'enabled') and $this->getPermissionObject()->Check('company', 'edit_own')) {
                $destination_url = 'https://github.com/aydancoskun/fairness/issues';
                $sub_message = TTi18n::getText('For more information please click here.');
            } else {
                $destination_url = null;
                $sub_message = null;
            }

            $retarr[] = array(
                'delay' => -1,
                'bg_color' => '#FF0000', //Red
                'message' => TTi18n::getText('WARNING: %1 was unable to set your time zone. Please contact your %1 administrator immediately.', APPLICATION_NAME) . ' ' . $sub_message,
                'destination' => $destination_url,
            );
            unset($destination_url, $sub_message);
        }

        return $retarr;
    }
}
