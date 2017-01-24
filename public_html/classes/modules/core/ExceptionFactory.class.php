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
class ExceptionFactory extends Factory
{
    protected $table = 'exception';
    protected $pk_sequence_name = 'exception_id_seq'; //PK Sequence name

    protected $user_obj = null;
    protected $exception_policy_obj = null;

    public function _getFactoryOptions($name, $parent = null)
    {
        $retval = null;
        switch ($name) {
            case 'type':
                //Exception life-cycle
                //
                // - Exception occurs, such as missed out punch, in late.
                //	 - If the exception is pre-mature, we wait 16-24hrs for it to become a full-blown exception
                // - If the exception requires authorization, it sits in a pending state waiting for supervsior intervention.
                // - Supervisor authorizes the exception, or makes a correction, leaves a note or something.
                //	 - Exception no longer appears on timesheet/exception list.
                $retval = array(
                    5 => TTi18n::gettext('Pre-Mature'),
                    30 => TTi18n::gettext('PENDING AUTHORIZATION'),
                    40 => TTi18n::gettext('AUTHORIZATION OPEN'),
                    50 => TTi18n::gettext('ACTIVE'),
                    55 => TTi18n::gettext('AUTHORIZATION DECLINED'),
                    60 => TTi18n::gettext('DISABLED'),
                    70 => TTi18n::gettext('Corrected')
                );
                break;
            case 'columns':
                $retval = array(
                    '-1000-first_name' => TTi18n::gettext('First Name'),
                    '-1002-last_name' => TTi18n::gettext('Last Name'),
                    //'-1005-user_status' => TTi18n::gettext('Employee Status'),
                    '-1010-title' => TTi18n::gettext('Title'),
                    '-1039-group' => TTi18n::gettext('Group'),
                    '-1050-default_branch' => TTi18n::gettext('Default Branch'),
                    '-1060-default_department' => TTi18n::gettext('Default Department'),
                    '-1070-branch' => TTi18n::gettext('Branch'),
                    '-1080-department' => TTi18n::gettext('Department'),
                    '-1090-country' => TTi18n::gettext('Country'),
                    '-1100-province' => TTi18n::gettext('Province'),

                    '-1120-date_stamp' => TTi18n::gettext('Date'),
                    '-1130-severity' => TTi18n::gettext('Severity'),
                    '-1140-exception_policy_type' => TTi18n::gettext('Exception'),
                    '-1150-exception_policy_type_id' => TTi18n::gettext('Code'),
                    '-1160-policy_group' => TTi18n::gettext('Policy Group'),
                    '-1170-permission_group' => TTi18n::gettext('Permission Group'),
                    '-1200-pay_period_schedule' => TTi18n::gettext('Pay Period Schedule'),

                    '-2000-created_by' => TTi18n::gettext('Created By'),
                    '-2010-created_date' => TTi18n::gettext('Created Date'),
                    '-2020-updated_by' => TTi18n::gettext('Updated By'),
                    '-2030-updated_date' => TTi18n::gettext('Updated Date'),
                );
                break;
            case 'list_columns':
                $retval = Misc::arrayIntersectByKey(array('date_stamp', 'severity', 'exception_policy_type', 'exception_policy_type_id'), Misc::trimSortPrefix($this->getOptions('columns')));
                break;
            case 'default_display_columns': //Columns that are displayed by default.
                $retval = array(
                    'first_name',
                    'last_name',
                    'date_stamp',
                    'severity',
                    'exception_policy_type',
                    'exception_policy_type_id',
                );
                break;
            case 'unique_columns': //Columns that are unique, and disabled for mass editing.
                $retval = array();
                break;
            case 'linked_columns': //Columns that are linked together, mainly for Mass Edit, if one changes, they all must.
                $retval = array();

        }

        return $retval;
    }

    public function _getVariableToFunctionMap($data)
    {
        $variable_function_map = array(
            'id' => 'ID',
            'date_stamp' => false,
            'pay_period_start_date' => false,
            'pay_period_end_date' => false,
            'pay_period_transaction_date' => false,
            'pay_period' => false,
            'exception_policy_id' => 'ExceptionPolicyID',
            'punch_control_id' => 'PunchControlID',
            'punch_id' => 'PunchID',
            'type_id' => 'Type',
            'type' => false,
            'severity_id' => false,
            'severity' => false,
            'exception_color' => 'Color',
            'exception_background_color' => 'BackgroundColor',
            'exception_policy_type_id' => false,
            'exception_policy_type' => false,
            'policy_group' => false,
            'permission_group' => false,
            'pay_period_schedule' => false,
            //'enable_demerit' => 'EnableDemerits',

            'pay_period_id' => false,
            'pay_period_schedule_id' => false,

            'user_id' => false,
            'first_name' => false,
            'last_name' => false,
            'country' => false,
            'province' => false,
            'user_status_id' => false,
            'user_status' => false,
            'group_id' => false,
            'group' => false,
            'title_id' => false,
            'title' => false,
            'default_branch_id' => false,
            'default_branch' => false,
            'default_department_id' => false,
            'default_department' => false,

            'branch_id' => false,
            'branch' => false,
            'department_id' => false,
            'department' => false,

            'deleted' => 'Deleted',
        );
        return $variable_function_map;
    }

    public function setUser($id)
    {
        $id = trim($id);

        $ulf = TTnew('UserListFactory');

        //Need to be able to support user_id=0 for open shifts. But this can cause problems with importing punches with user_id=0.
        if ($this->Validator->isResultSetWithRows('user',
            $ulf->getByID($id),
            TTi18n::gettext('Invalid User')
        )
        ) {
            $this->data['user_id'] = $id;

            return true;
        }

        return false;
    }

    public function setDateStamp($epoch)
    {
        $epoch = (!is_int($epoch)) ? trim($epoch) : $epoch; //Dont trim integer values, as it changes them to strings.

        if ($this->Validator->isDate('date_stamp',
            $epoch,
            TTi18n::gettext('Incorrect date'))
        ) {
            if ($epoch > 0) {
                $this->data['date_stamp'] = $epoch;

                $this->setPayPeriod(); //Force pay period to be set as soon as the date is.
                return true;
            } else {
                $this->Validator->isTRUE('date_stamp',
                    false,
                    TTi18n::gettext('Incorrect date'));
            }
        }

        return false;
    }

    public function setPayPeriod($id = null)
    {
        $id = trim($id);

        if ($id == null) {
            $id = (int)PayPeriodListFactory::findPayPeriod($this->getUser(), $this->getDateStamp());
        }

        $pplf = TTnew('PayPeriodListFactory');

        //Allow NULL pay period, incase its an absence or something in the future.
        //Cron will fill in the pay period later.
        if (
            $id == 0
            or
            $this->Validator->isResultSetWithRows('pay_period',
                $pplf->getByID($id),
                TTi18n::gettext('Invalid Pay Period')
            )
        ) {
            $this->data['pay_period_id'] = $id;

            return true;
        }

        return false;
    }

    public function getUser()
    {
        if (isset($this->data['user_id'])) {
            return (int)$this->data['user_id'];
        }
    }

    public function getDateStamp($raw = false)
    {
        if (isset($this->data['date_stamp'])) {
            if ($raw === true) {
                return $this->data['date_stamp'];
            } else {
                return TTDate::strtotime($this->data['date_stamp']);
            }
        }

        return false;
    }

    public function setExceptionPolicyID($id)
    {
        $id = trim($id);

        if ($id == '' or empty($id)) {
            $id = null;
        }

        $eplf = TTnew('ExceptionPolicyListFactory');

        if ($id == null
            or
            $this->Validator->isResultSetWithRows('exception_policy',
                $eplf->getByID($id),
                TTi18n::gettext('Invalid Exception Policy ID')
            )
        ) {
            $this->data['exception_policy_id'] = $id;

            return true;
        }

        return false;
    }

    public function getPunchControlID()
    {
        if (isset($this->data['punch_control_id'])) {
            return (int)$this->data['punch_control_id'];
        }

        return false;
    }

    public function setPunchControlID($id)
    {
        $id = trim($id);

        if ($id == '' or empty($id)) {
            $id = null;
        }

        $pclf = TTnew('PunchControlListFactory');

        if (
            $id == null
            or
            $this->Validator->isResultSetWithRows('punch_control',
                $pclf->getByID($id),
                TTi18n::gettext('Invalid Punch Control ID')
            )
        ) {
            $this->data['punch_control_id'] = $id;

            return true;
        }

        return false;
    }

    public function getPunchID()
    {
        if (isset($this->data['punch_id'])) {
            return (int)$this->data['punch_id'];
        }

        return false;
    }

    public function setPunchID($id)
    {
        $id = trim($id);

        if ($id == '' or empty($id)) {
            $id = null;
        }

        $plf = TTnew('PunchListFactory');

        if ($id == null
            or
            $this->Validator->isResultSetWithRows('punch',
                $plf->getByID($id),
                TTi18n::gettext('Invalid Punch ID')
            )
        ) {
            $this->data['punch_id'] = $id;

            return true;
        }

        return false;
    }

    public function setType($value)
    {
        $value = trim($value);

        if ($this->Validator->inArrayKey('type',
            $value,
            TTi18n::gettext('Incorrect Type'),
            $this->getOptions('type'))
        ) {
            $this->data['type_id'] = $value;

            return true;
        }

        return false;
    }

    public function getEnableDemerits()
    {
        if (isset($this->data['enable_demerit'])) {
            return $this->data['enable_demerit'];
        }

        return false;
    }

    public function setEnableDemerits($bool)
    {
        $this->data['enable_demerit'] = $bool;

        return true;
    }

    public function getBackgroundColor()
    {
        //Use HTML color codes so they work in Flex too.
        $retval = false;
        if ($this->getType() == 5) {
            $retval = '#666666'; #'gray';
        } else {
            if ($this->getColumn('severity_id') != '') {
                switch ($this->getColumn('severity_id')) {
                    case 10:
                        $retval = false;
                        break;
                    case 20:
                        $retval = '#FFFF00'; #'yellow';
                        break;
                    case 25:
                        $retval = '#FF9900'; #'orange';
                        break;
                    case 30:
                        $retval = '#FF0000'; #'red';
                        break;
                }
            }
        }

        return $retval;
    }

    public function getType()
    {
        if (isset($this->data['type_id'])) {
            return (int)$this->data['type_id'];
        }

        return false;
    }

    public function getColor()
    {
        $retval = false;

        //Use HTML color codes so they work in Flex too.
        if ($this->getType() == 5) {
            $retval = '#666666'; #'gray';
        } else {
            if ($this->getColumn('severity_id') != '') {
                switch ($this->getColumn('severity_id')) {
                    case 10:
                        $retval = '#000000'; #'black';
                        break;
                    case 20:
                        $retval = '#0000FF'; #'blue';
                        break;
                    case 25:
                        $retval = '#FF9900'; #'blue';
                        break;
                    case 30:
                        $retval = '#FF0000'; #'red';
                        break;
                }
            }
        }

        return $retval;
    }

    public function emailException($u_obj, $date_stamp, $punch_obj = null, $schedule_obj = null, $ep_obj = null)
    {
        if (!is_object($u_obj)) {
            return false;
        }

        if ($date_stamp == '') {
            return false;
        }

        if (!is_object($ep_obj)) {
            $ep_obj = $this->getExceptionPolicyObject();
        }

        //Only email on active exceptions.
        if ($this->getType() != 50) {
            return false;
        }

        $email_to_arr = $this->getEmailExceptionAddresses($u_obj, $ep_obj);
        if ($email_to_arr == false) {
            return false;
        }

        $from = $reply_to = '"' . APPLICATION_NAME . ' - ' . TTi18n::gettext('Exception') . '" <' . Misc::getEmailLocalPart() . '@' . Misc::getEmailDomain() . '>';
        Debug::Text('To: ' . implode(',', $email_to_arr), __FILE__, __LINE__, __METHOD__, 10);

        if (is_array($email_to_arr)) {
            $reply_to = $email_to_arr[0];
        }

        //Define subject/body variables here.
        $search_arr = array(
            '#employee_first_name#',
            '#employee_last_name#',
            '#employee_default_branch#',
            '#employee_default_department#',
            '#employee_group#',
            '#employee_title#',
            '#exception_code#',
            '#exception_name#',
            '#exception_severity#',
            '#date#',
            '#company_name#',
            '#link#',
            '#schedule_start_time#',
            '#schedule_end_time#',
            '#schedule_branch#',
            '#schedule_department#',
            '#punch_time#',
        );

        $replace_arr = array(
            $u_obj->getFirstName(),
            $u_obj->getLastName(),
            (is_object($u_obj->getDefaultBranchObject())) ? $u_obj->getDefaultBranchObject()->getName() : null,
            (is_object($u_obj->getDefaultDepartmentObject())) ? $u_obj->getDefaultDepartmentObject()->getName() : null,
            (is_object($u_obj->getGroupObject())) ? $u_obj->getGroupObject()->getName() : null,
            (is_object($u_obj->getTitleObject())) ? $u_obj->getTitleObject()->getName() : null,
            $ep_obj->getType(),
            Option::getByKey($ep_obj->getType(), $ep_obj->getOptions('type')),
            Option::getByKey($ep_obj->getSeverity(), $ep_obj->getOptions('severity')),
            TTDate::getDate('DATE', $date_stamp),
            (is_object($u_obj->getCompanyObject())) ? $u_obj->getCompanyObject()->getName() : null,
            null,
            (is_object($schedule_obj)) ? TTDate::getDate('TIME', $schedule_obj->getStartTime()) : null,
            (is_object($schedule_obj)) ? TTDate::getDate('TIME', $schedule_obj->getEndTime()) : null,
            (is_object($schedule_obj) and is_object($schedule_obj->getBranchObject())) ? $schedule_obj->getBranchObject()->getName() : null,
            (is_object($schedule_obj) and is_object($schedule_obj->getDepartmentObject())) ? $schedule_obj->getDepartmentObject()->getName() : null,
            (is_object($punch_obj)) ? TTDate::getDate('TIME', $punch_obj->getTimeStamp()) : null,
        );

        $exception_email_subject = '#exception_name# (#exception_code#) ' . TTi18n::gettext('exception for') . ' #employee_first_name# #employee_last_name# ' . TTi18n::gettext('on') . ' #date#';
        $exception_email_body = TTi18n::gettext('*DO NOT REPLY TO THIS EMAIL - PLEASE USE THE LINK BELOW INSTEAD*') . "\n\n";
        $exception_email_body .= TTi18n::gettext('Employee') . ': #employee_first_name# #employee_last_name#' . "\n";
        $exception_email_body .= TTi18n::gettext('Date') . ': #date#' . "\n";
        $exception_email_body .= TTi18n::gettext('Exception') . ': #exception_name# (#exception_code#)' . "\n";
        $exception_email_body .= TTi18n::gettext('Severity') . ': #exception_severity#' . "\n";

        $exception_email_body .= ($replace_arr[12] != '' or $replace_arr[13] != '' or $replace_arr[14] != '' or $replace_arr[15] != '' or $replace_arr[16] != '') ? "\n" : null;
        $exception_email_body .= ($replace_arr[12] != '' and $replace_arr[13] != '') ? TTi18n::gettext('Schedule') . ': #schedule_start_time# - #schedule_end_time#' . "\n" : null;
        $exception_email_body .= ($replace_arr[14] != '') ? TTi18n::gettext('Schedule Branch') . ': #schedule_branch#' . "\n" : null;
        $exception_email_body .= ($replace_arr[15] != '') ? TTi18n::gettext('Schedule Department') . ': #schedule_department#' . "\n" : null;
        if ($replace_arr[16] != '') {
            $exception_email_body .= TTi18n::gettext('Punch') . ': #punch_time#' . "\n";
        } elseif ($replace_arr[12] != '' and $replace_arr[13] != '') {
            $exception_email_body .= TTi18n::gettext('Punch') . ': ' . TTi18n::gettext('None') . "\n";
        }

        $exception_email_body .= ($replace_arr[2] != '' or $replace_arr[3] != '' or $replace_arr[4] != '' or $replace_arr[5] != '') ? "\n" : null;
        $exception_email_body .= ($replace_arr[2] != '') ? TTi18n::gettext('Default Branch') . ': #employee_default_branch#' . "\n" : null;
        $exception_email_body .= ($replace_arr[3] != '') ? TTi18n::gettext('Default Department') . ': #employee_default_department#' . "\n" : null;
        $exception_email_body .= ($replace_arr[4] != '') ? TTi18n::gettext('Group') . ': #employee_group#' . "\n" : null;
        $exception_email_body .= ($replace_arr[5] != '') ? TTi18n::gettext('Title') . ': #employee_title#' . "\n" : null;

        $exception_email_body .= "\n";
        $exception_email_body .= TTi18n::gettext('Link') . ': <a href="' . Misc::getURLProtocol() . '://' . Misc::getHostName() . Environment::getDefaultInterfaceBaseURL() . '">' . APPLICATION_NAME . ' ' . TTi18n::gettext('Login') . '</a>';

        $exception_email_body .= ($replace_arr[10] != '') ? "\n\n\n" . TTi18n::gettext('Company') . ': #company_name#' . "\n" : null; //Always put at the end

        $exception_email_body .= "\n\n" . TTi18n::gettext('Email sent') . ': ' . TTDate::getDate('DATE+TIME', time()) . "\n";

        $subject = str_replace($search_arr, $replace_arr, $exception_email_subject);
        //Debug::Text('Subject: '. $subject, __FILE__, __LINE__, __METHOD__, 10);

        $headers = array(
            'From' => $from,
            'Subject' => $subject,
            //Reply-To/Return-Path are handled in TTMail.
        );

        $body = '<html><body><pre>' . str_replace($search_arr, $replace_arr, $exception_email_body) . '</pre></body></html>';
        Debug::Text('Body: ' . $body, __FILE__, __LINE__, __METHOD__, 10);

        $mail = new TTMail();
        $mail->setTo($email_to_arr);
        $mail->setHeaders($headers);

        @$mail->getMIMEObject()->setHTMLBody($body);

        $mail->setBody($mail->getMIMEObject()->get($mail->default_mime_config));
        $retval = $mail->Send();

        if ($retval == true) {
            TTLog::addEntry($this->getId(), 500, TTi18n::getText('Email Exception') . ': ' . Option::getByKey($ep_obj->getType(), $ep_obj->getOptions('type')) . ' To: ' . implode(', ', $email_to_arr), $u_obj->getID(), $this->getTable()); //Make sure this log entry is assigned to the user triggering the exception so it can be viewed in the audit log.
        }

        return true;
    }

    public function getExceptionPolicyObject()
    {
        return $this->getGenericObject('ExceptionPolicyListFactory', $this->getExceptionPolicyID(), 'exception_policy_obj');
    }

    public function getExceptionPolicyID()
    {
        if (isset($this->data['exception_policy_id'])) {
            return (int)$this->data['exception_policy_id'];
        }

        return false;
    }

    public function getEmailExceptionAddresses($u_obj = null, $ep_obj = null)
    {
        Debug::text(' Attempting to Email Notification...', __FILE__, __LINE__, __METHOD__, 10);

        //Make sure type is not pre-mature.
        if ($this->getType() > 5) {
            if (!is_object($ep_obj)) {
                $ep_obj = $this->getExceptionPolicyObject();
            }

            //Make sure exception policy email notifications are enabled.
            if ($ep_obj->getEmailNotification() > 0) {
                $retarr = array();
                if (!is_object($u_obj)) {
                    $u_obj = $this->getUserObject();
                }

                //Make sure user email notifications are enabled and user is *not* terminated.
                if (($ep_obj->getEmailNotification() == 10 or $ep_obj->getEmailNotification() == 100)
                    and is_object($u_obj->getUserPreferenceObject())
                    and $u_obj->getUserPreferenceObject()->getEnableEmailNotificationException() == true
                    and $u_obj->getStatus() == 10
                ) {
                    Debug::Text(' Emailing exception to user!', __FILE__, __LINE__, __METHOD__, 10);
                    if ($u_obj->getWorkEmail() != '' and $u_obj->getWorkEmailIsValid() == true) {
                        $retarr[] = Misc::formatEmailAddress($u_obj->getWorkEmail(), $u_obj);
                    }
                    if ($u_obj->getUserPreferenceObject()->getEnableEmailNotificationHome() == true and $u_obj->getHomeEmail() != '' and $u_obj->getHomeEmailIsValid() == true) {
                        $retarr[] = Misc::formatEmailAddress($u_obj->getHomeEmail(), $u_obj);
                    }
                } else {
                    Debug::Text(' Skipping email to user.', __FILE__, __LINE__, __METHOD__, 10);
                }

                //Make sure supervisor email notifcations are enabled
                if ($ep_obj->getEmailNotification() == 20 or $ep_obj->getEmailNotification() == 100) {
                    //Find supervisor(s)
                    $hlf = TTnew('HierarchyListFactory');
                    $parent_user_id = $hlf->getHierarchyParentByCompanyIdAndUserIdAndObjectTypeID($u_obj->getCompany(), $u_obj->getId(), 80);
                    if ($parent_user_id != false) {
                        //Parent could be multiple supervisors, make sure we email them all.
                        $ulf = TTnew('UserListFactory');
                        $ulf->getByIdAndCompanyId($parent_user_id, $u_obj->getCompany());
                        if ($ulf->getRecordCount() > 0) {
                            foreach ($ulf as $parent_user_obj) {
                                //Make sure supervisor has exception notifications enabled and is *not* terminated
                                if (is_object($parent_user_obj->getUserPreferenceObject())
                                    and $parent_user_obj->getUserPreferenceObject()->getEnableEmailNotificationException() == true
                                    and $parent_user_obj->getStatus() == 10
                                ) {
                                    Debug::Text(' Emailing exception to supervisor!', __FILE__, __LINE__, __METHOD__, 10);
                                    if ($parent_user_obj->getWorkEmail() != '' and $parent_user_obj->getWorkEmailIsValid() == true) {
                                        $retarr[] = Misc::formatEmailAddress($parent_user_obj->getWorkEmail(), $parent_user_obj);
                                    }

                                    if ($parent_user_obj->getUserPreferenceObject()->getEnableEmailNotificationHome() == true and $parent_user_obj->getHomeEmail() != '' and $parent_user_obj->getHomeEmailIsValid() == true) {
                                        $retarr[] = Misc::formatEmailAddress($parent_user_obj->getHomeEmail(), $parent_user_obj);
                                    }
                                } else {
                                    Debug::Text(' Skipping email to supervisor.', __FILE__, __LINE__, __METHOD__, 10);
                                }
                            }
                        }
                    } else {
                        Debug::Text(' No Hierarchy Parent Found, skipping email to supervisor.', __FILE__, __LINE__, __METHOD__, 10);
                    }
                }

                if (empty($retarr) == false) {
                    return array_unique($retarr);
                } else {
                    Debug::text(' No user objects to email too...', __FILE__, __LINE__, __METHOD__, 10);
                }
            } else {
                Debug::text(' Exception Policy Email Exceptions are disabled, skipping email...', __FILE__, __LINE__, __METHOD__, 10);
            }
        } else {
            Debug::text(' Pre-Mature exception, or not in production mode, skipping email...', __FILE__, __LINE__, __METHOD__, 10);
        }

        return false;
    }

    public function getUserObject()
    {
        return $this->getGenericObject('UserListFactory', $this->getUser(), 'user_obj');
    }


    /*

        What do we pass the emailException function?
            To address, CC address (home email) and Bcc (supervisor) address?

    */

    public function Validate($ignore_warning = true)
    {
        if ($this->getUser() == false) {
            $this->Validator->isTRUE('user_id',
                false,
                TTi18n::gettext('Employee is invalid'));
        }

        if ($this->getDeleted() == false and $this->getDateStamp() == false) {
            $this->Validator->isTRUE('date_stamp',
                false,
                TTi18n::gettext('Date/Time is incorrect, or pay period does not exist for this date. Please create a pay period schedule and assign this employee to it if you have not done so already'));
        }

        return true;
    }

    public function preSave()
    {
        if ($this->getPayPeriod() == false) {
            $this->setPayPeriod();
        }

        return true;
    }

    public function getPayPeriod()
    {
        if (isset($this->data['pay_period_id'])) {
            return (int)$this->data['pay_period_id'];
        }

        return false;
    }

    public function postSave()
    {
        return true;
    }

    public function setObjectFromArray($data)
    {
        if (is_array($data)) {
            $variable_function_map = $this->getVariableToFunctionMap();
            foreach ($variable_function_map as $key => $function) {
                if (isset($data[$key])) {
                    $function = 'set' . $function;
                    switch ($key) {
                        default:
                            if (method_exists($this, $function)) {
                                $this->$function($data[$key]);
                            }
                            break;
                    }
                }
            }

            $this->setCreatedAndUpdatedColumns($data);

            return true;
        }

        return false;
    }

    public function getObjectAsArray($include_columns = null, $permission_children_ids = false)
    {
        $variable_function_map = $this->getVariableToFunctionMap();

        $epf = TTnew('ExceptionPolicyFactory');
        $exception_policy_type_options = $epf->getOptions('type');
        $exception_policy_severity_options = $epf->getOptions('severity');

        $data = array();
        if (is_array($variable_function_map)) {
            foreach ($variable_function_map as $variable => $function_stub) {
                if ($include_columns == null or (isset($include_columns[$variable]) and $include_columns[$variable] == true)) {
                    $function = 'get' . $function_stub;
                    switch ($variable) {
                        case 'pay_period_id':
                        case 'pay_period_schedule_id':
                        case 'pay_period_start_date':
                        case 'pay_period_end_date':
                        case 'pay_period_transaction_date':
                        case 'user_id':
                        case 'first_name':
                        case 'last_name':
                        case 'country':
                        case 'province':
                        case 'user_status_id':
                        case 'group_id':
                        case 'group':
                        case 'title_id':
                        case 'title':
                        case 'default_branch_id':
                        case 'default_branch':
                        case 'default_department_id':
                        case 'default_department':
                        case 'branch_id':
                        case 'branch':
                        case 'department_id':
                        case 'department':
                        case 'severity_id':
                        case 'exception_policy_type_id':
                        case 'policy_group':
                        case 'permission_group':
                        case 'pay_period_schedule':
                            $data[$variable] = $this->getColumn($variable);
                            break;
                        case 'severity':
                            $data[$variable] = Option::getByKey($this->getColumn('severity_id'), $exception_policy_severity_options);
                            break;
                        case 'exception_policy_type':
                            $data[$variable] = Option::getByKey($this->getColumn('exception_policy_type_id'), $exception_policy_type_options);
                            break;
                        case 'type':
                            $function = 'get' . $variable;
                            if (method_exists($this, $function)) {
                                $data[$variable] = Option::getByKey($this->$function(), $this->getOptions($variable));
                            }
                            break;
                        case 'date_stamp':
                            $data[$variable] = TTDate::getAPIDate('DATE', $this->getDateStamp());
                            break;
                        case 'pay_period_start_date':
                            $data[$variable] = TTDate::getAPIDate('DATE', TTDate::strtotime($this->getColumn('pay_period_start_date')));
                            break;
                        case 'pay_period_end_date':
                            $data[$variable] = TTDate::getAPIDate('DATE', TTDate::strtotime($this->getColumn('pay_period_end_date')));
                            break;
                        case 'pay_period':
                        case 'pay_period_transaction_date':
                            $data[$variable] = TTDate::getAPIDate('DATE', TTDate::strtotime($this->getColumn('pay_period_transaction_date')));
                            break;
                        default:
                            if (method_exists($this, $function)) {
                                $data[$variable] = $this->$function();
                            }
                            break;
                    }
                }
            }
            $this->getPermissionColumns($data, $this->getColumn('user_id'), $this->getCreatedBy(), $permission_children_ids, $include_columns);
            $this->getCreatedAndUpdatedColumns($data, $include_columns);
        }

        return $data;
    }
}
