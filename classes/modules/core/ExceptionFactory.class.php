<?php
/*********************************************************************************
 * FairnessTNA is a Workforce Management program forked from TimeTrex in 2013,
 * copyright Aydan Coskun. Original code base is copyright TimeTrex Software Inc.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * You can contact Aydan Coskun via issue tracker on github.com/aydancoskun
 ********************************************************************************/


/**
 * @package Core
 */
class ExceptionFactory extends Factory {
	protected $table = 'exception';
	protected $pk_sequence_name = 'exception_id_seq'; //PK Sequence name

	protected $user_obj = NULL;
	protected $exception_policy_obj = NULL;

	/**
	 * @param $name
	 * @param null $parent
	 * @return array|null
	 */
	function _getFactoryOptions( $name, $parent = NULL ) {

		$retval = NULL;
		switch( $name ) {
			case 'type':
				//Exception life-cycle
				//
				// - Exception occurs, such as missed out punch, in late.
				//	 - If the exception is pre-mature, we wait 16-24hrs for it to become a full-blown exception
				// - If the exception requires authorization, it sits in a pending state waiting for supervsior intervention.
				// - Supervisor authorizes the exception, or makes a correction, leaves a note or something.
				//	 - Exception no longer appears on timesheet/exception list.
				$retval = array(
										5  => TTi18n::gettext('Pre-Mature'),
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
				$retval = Misc::arrayIntersectByKey( array('date_stamp', 'severity', 'exception_policy_type', 'exception_policy_type_id'), Misc::trimSortPrefix( $this->getOptions('columns') ) );
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
				$retval = array(
								);
				break;
			case 'linked_columns': //Columns that are linked together, mainly for Mass Edit, if one changes, they all must.
				$retval = array(
								);

		}

		return $retval;
	}

	/**
	 * @param $data
	 * @return array
	 */
	function _getVariableToFunctionMap( $data ) {
			$variable_function_map = array(
											'id' => 'ID',
											'date_stamp' => FALSE,
											'pay_period_start_date' => FALSE,
											'pay_period_end_date' => FALSE,
											'pay_period_transaction_date' => FALSE,
											'pay_period' => FALSE,
											'exception_policy_id' => 'ExceptionPolicyID',
											'punch_control_id' => 'PunchControlID',
											'punch_id' => 'PunchID',
											'type_id' => 'Type',
											'type' => FALSE,
											'severity_id' => FALSE,
											'severity' => FALSE,
											'exception_color' => 'Color',
											'exception_background_color' => 'BackgroundColor',
											'exception_policy_type_id' => FALSE,
											'exception_policy_type' => FALSE,
											'policy_group' => FALSE,
											'permission_group' => FALSE,
											'pay_period_schedule' => FALSE,
											//'enable_demerit' => 'EnableDemerits',

											'pay_period_id' => FALSE,
											'pay_period_schedule_id' => FALSE,

											'user_id' => FALSE,
											'first_name' => FALSE,
											'last_name' => FALSE,
											'country' => FALSE,
											'province' => FALSE,
											'user_status_id' => FALSE,
											'user_status' => FALSE,
											'group_id' => FALSE,
											'group' => FALSE,
											'title_id' => FALSE,
											'title' => FALSE,
											'default_branch_id' => FALSE,
											'default_branch' => FALSE,
											'default_department_id' => FALSE,
											'default_department' => FALSE,

											'branch_id' => FALSE,
											'branch' => FALSE,
											'department_id' => FALSE,
											'department' => FALSE,

											'deleted' => 'Deleted',
											);
			return $variable_function_map;
	}

	/**
	 * @return bool
	 */
	function getUserObject() {
		return $this->getGenericObject( 'UserListFactory', $this->getUser(), 'user_obj' );
	}

	/**
	 * @return bool
	 */
	function getExceptionPolicyObject() {
		return $this->getGenericObject( 'ExceptionPolicyListFactory', $this->getExceptionPolicyID(), 'exception_policy_obj' );
	}

	/**
	 * @return mixed
	 */
	function getUser() {
		return $this->getGenericDataValue( 'user_id' );
	}

	/**
	 * @param string $value UUID
	 * @return bool
	 */
	function setUser( $value) {
		$value = TTUUID::castUUID( $value );
		//Need to be able to support user_id=0 for open shifts. But this can cause problems with importing punches with user_id=0.
		return $this->setGenericDataValue( 'user_id', $value );
	}

	/**
	 * @return bool|mixed
	 */
	function getPayPeriod() {
		return $this->getGenericDataValue( 'pay_period_id' );
	}

	/**
	 * @param string $value UUID
	 * @return bool
	 */
	function setPayPeriod( $value = NULL) {
		if ( $value == NULL ) {
			$value = PayPeriodListFactory::findPayPeriod( $this->getUser(), $this->getDateStamp() );
		}

		$value = TTUUID::castUUID( $value );
		//Allow NULL pay period, incase its an absence or something in the future.
		//Cron will fill in the pay period later
		return $this->setGenericDataValue( 'pay_period_id', $value );
	}

	/**
	 * @param bool $raw
	 * @return bool|int
	 */
	function getDateStamp( $raw = FALSE ) {
		$value = $this->getGenericDataValue( 'date_stamp' );
		if ( $value !== FALSE ) {
			if ( $raw === TRUE ) {
				return $value;
			} else {
				return TTDate::strtotime( $value );
			}
		}

		return FALSE;
	}

	/**
	 * @param int $value EPOCH
	 * @return bool
	 */
	function setDateStamp( $value) {
		$value = ( !is_int($value) ) ? trim($value) : $value; //Dont trim integer values, as it changes them to strings.
		if ( $value > 0 ) {
			return $this->setGenericDataValue( 'date_stamp', $value );
		}
		return FALSE;
	}

	/**
	 * @return bool|mixed
	 */
	function getExceptionPolicyID() {
		return $this->getGenericDataValue( 'exception_policy_id' );
	}

	/**
	 * @param string $value UUID
	 * @return bool
	 */
	function setExceptionPolicyID( $value) {
		$value = TTUUID::castUUID( $value );
		return $this->setGenericDataValue( 'exception_policy_id', $value );
	}

	/**
	 * @return bool|mixed
	 */
	function getPunchControlID() {
		return $this->getGenericDataValue( 'punch_control_id' );
	}

	/**
	 * @param string $value UUID
	 * @return bool
	 */
	function setPunchControlID( $value) {
		$value = TTUUID::castUUID( $value );
		return $this->setGenericDataValue( 'punch_control_id', $value );
	}

	/**
	 * @return bool|mixed
	 */
	function getPunchID() {
		return $this->getGenericDataValue( 'punch_id' );
	}

	/**
	 * @param string $value UUID
	 * @return bool
	 */
	function setPunchID( $value) {
		$value = TTUUID::castUUID( $value );
		return $this->setGenericDataValue( 'punch_id', $value );
	}

	/**
	 * @return bool|int
	 */
	function getType() {
		return $this->getGenericDataValue( 'type_id' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setType( $value) {
		$value = (int)trim($value);
		return $this->setGenericDataValue( 'type_id', $value );
	}

	/**
	 * @return bool|mixed
	 */
	function getEnableDemerits() {
		return $this->getGenericDataValue( 'enable_demerit' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setEnableDemerits( $value) {
		$this->setGenericDataValue( 'enable_demerit', $value );
		return TRUE;
	}

	/**
	 * @return bool|string
	 */
	function getBackgroundColor() {
		//Use HTML color codes so they work in Flex too.
		$retval = FALSE;
		if (  $this->getType() == 5 ) {
			$retval = '#666666'; #'gray';
		} else {
			if ( $this->getColumn('severity_id') != '' ) {
				switch ( $this->getColumn('severity_id') ) {
					case 10:
						$retval = FALSE;
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

	/**
	 * @return bool|string
	 */
	function getColor() {
		$retval = FALSE;

		//Use HTML color codes so they work in Flex too.
		if (  $this->getType() == 5 ) {
			$retval = '#666666'; #'gray';
		} else {
			if ( $this->getColumn('severity_id') != '' ) {
				switch ( $this->getColumn('severity_id') ) {
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

	/**
	 * @param object $u_obj
	 * @param object $ep_obj
	 * @return array|bool
	 */
	function getEmailExceptionAddresses( $u_obj = NULL, $ep_obj = NULL ) {
		Debug::text(' Attempting to Email Notification...', __FILE__, __LINE__, __METHOD__, 10);

		//Make sure type is not pre-mature.
		if ( $this->getType() > 5 ) {
			if ( !is_object($ep_obj) ) {
				$ep_obj = $this->getExceptionPolicyObject();
			}

			//Make sure exception policy email notifications are enabled.
			if ( $ep_obj->getEmailNotification() > 0 ) {
				$retarr = array();
				if ( !is_object($u_obj) ) {
					$u_obj = $this->getUserObject();
				}

				//Make sure user email notifications are enabled and user is *not* terminated.
				if ( ( $ep_obj->getEmailNotification() == 10 OR $ep_obj->getEmailNotification() == 100 )
						AND is_object( $u_obj->getUserPreferenceObject() )
						AND $u_obj->getUserPreferenceObject()->getEnableEmailNotificationException() == TRUE
						AND $u_obj->getStatus() == 10 ) {
					Debug::Text(' Emailing exception to user!', __FILE__, __LINE__, __METHOD__, 10);
					if ( $u_obj->getWorkEmail() != '' AND $u_obj->getWorkEmailIsValid() == TRUE ) {
						$retarr[] = Misc::formatEmailAddress( $u_obj->getWorkEmail(), $u_obj );
					}
					if ( $u_obj->getUserPreferenceObject()->getEnableEmailNotificationHome() == TRUE AND $u_obj->getHomeEmail() != '' AND $u_obj->getHomeEmailIsValid() == TRUE ) {
						$retarr[] = Misc::formatEmailAddress( $u_obj->getHomeEmail(), $u_obj );
					}
				} else {
					Debug::Text(' Skipping email to user.', __FILE__, __LINE__, __METHOD__, 10);
				}

				//Make sure supervisor email notifcations are enabled
				if ( $ep_obj->getEmailNotification() == 20 OR $ep_obj->getEmailNotification() == 100 ) {
					//Find supervisor(s)
					$hlf = TTnew( 'HierarchyListFactory' ); /** @var HierarchyListFactory $hlf */
					$parent_user_id = $hlf->getHierarchyParentByCompanyIdAndUserIdAndObjectTypeID( $u_obj->getCompany(), $u_obj->getId(), 80 );
					if ( $parent_user_id != FALSE ) {
						//Parent could be multiple supervisors, make sure we email them all.
						$ulf = TTnew( 'UserListFactory' ); /** @var UserListFactory $ulf */
						$ulf->getByIdAndCompanyId( $parent_user_id, $u_obj->getCompany() );
						if ( $ulf->getRecordCount() > 0 ) {
							foreach( $ulf as $parent_user_obj ) {
								//Make sure supervisor has exception notifications enabled and is *not* terminated
								if ( is_object( $parent_user_obj->getUserPreferenceObject() )
										AND $parent_user_obj->getUserPreferenceObject()->getEnableEmailNotificationException() == TRUE
										AND $parent_user_obj->getStatus() == 10 ) {
									Debug::Text(' Emailing exception to supervisor!', __FILE__, __LINE__, __METHOD__, 10);
									if ( $parent_user_obj->getWorkEmail() != '' AND $parent_user_obj->getWorkEmailIsValid() == TRUE ) {
										$retarr[] = Misc::formatEmailAddress( $parent_user_obj->getWorkEmail(), $parent_user_obj );
									}

									if ( $parent_user_obj->getUserPreferenceObject()->getEnableEmailNotificationHome() == TRUE AND $parent_user_obj->getHomeEmail() != '' AND $parent_user_obj->getHomeEmailIsValid() == TRUE ) {
										$retarr[] = Misc::formatEmailAddress( $parent_user_obj->getHomeEmail(), $parent_user_obj );
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

				if ( empty($retarr) == FALSE ) {
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

		return FALSE;
	}


	/*

		What do we pass the emailException function?
			To address, CC address (home email) and Bcc (supervisor) address?

	*/
	/**
	 * @param object $u_obj
	 * @param int $date_stamp EPOCH
	 * @param object $punch_obj
	 * @param object $schedule_obj
	 * @param object $ep_obj
	 * @return bool
	 */
	function emailException( $u_obj, $date_stamp, $punch_obj = NULL, $schedule_obj = NULL, $ep_obj = NULL ) {
		if ( !is_object( $u_obj ) ) {
			return FALSE;
		}

		if ( $date_stamp == '' ) {
			return FALSE;
		}

		if ( !is_object($ep_obj) ) {
			$ep_obj = $this->getExceptionPolicyObject();
		}

		//Only email on active exceptions.
		if ( $this->getType() != 50 ) {
			return FALSE;
		}

		$email_to_arr = $this->getEmailExceptionAddresses( $u_obj, $ep_obj );
		if ( $email_to_arr == FALSE ) {
			return FALSE;
		}

		$from = $reply_to = '"'. APPLICATION_NAME .' - '. TTi18n::gettext('Exception') .'" <'. Misc::getEmailLocalPart() .'@'. Misc::getEmailDomain() .'>';
		Debug::Text('To: '. implode(',', $email_to_arr), __FILE__, __LINE__, __METHOD__, 10);

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
							( is_object( $u_obj->getDefaultBranchObject() ) ) ? $u_obj->getDefaultBranchObject()->getName() : NULL,
							( is_object( $u_obj->getDefaultDepartmentObject() ) ) ? $u_obj->getDefaultDepartmentObject()->getName() : NULL,
							( is_object( $u_obj->getGroupObject() ) ) ? $u_obj->getGroupObject()->getName() : NULL,
							( is_object( $u_obj->getTitleObject() ) ) ? $u_obj->getTitleObject()->getName() : NULL,
							$ep_obj->getType(),
							Option::getByKey( $ep_obj->getType(), $ep_obj->getOptions('type') ),
							Option::getByKey( $ep_obj->getSeverity(), $ep_obj->getOptions('severity') ),
							TTDate::getDate('DATE', $date_stamp ),
							( is_object( $u_obj->getCompanyObject() ) ) ? $u_obj->getCompanyObject()->getName() : NULL,
							NULL,
							( is_object( $schedule_obj ) ) ? TTDate::getDate('TIME', $schedule_obj->getStartTime() ) : NULL,
							( is_object( $schedule_obj ) ) ? TTDate::getDate('TIME', $schedule_obj->getEndTime() ) : NULL,
							( is_object( $schedule_obj ) AND is_object($schedule_obj->getBranchObject()) ) ? $schedule_obj->getBranchObject()->getName() : NULL,
							( is_object( $schedule_obj ) AND is_object($schedule_obj->getDepartmentObject()) ) ? $schedule_obj->getDepartmentObject()->getName() : NULL,
							( is_object( $punch_obj ) ) ? TTDate::getDate('TIME', $punch_obj->getTimeStamp() ) : NULL,
							);

		$exception_email_subject = '#exception_name# (#exception_code#) '. TTi18n::gettext('exception for') .' #employee_first_name# #employee_last_name# '. TTi18n::gettext('on') .' #date#';
		$exception_email_body = TTi18n::gettext('*DO NOT REPLY TO THIS EMAIL - PLEASE USE THE LINK BELOW INSTEAD*')."\n\n";
		$exception_email_body .= TTi18n::gettext('Employee').': #employee_first_name# #employee_last_name#'."\n";
		$exception_email_body .= TTi18n::gettext('Date').': #date#'."\n";
		$exception_email_body .= TTi18n::gettext('Exception').': #exception_name# (#exception_code#)'."\n";
		$exception_email_body .= TTi18n::gettext('Severity').': #exception_severity#'."\n";

		$exception_email_body .= ( $replace_arr[12] != '' OR $replace_arr[13] != '' OR $replace_arr[14] != '' OR $replace_arr[15] != '' OR $replace_arr[16] != '' ) ? "\n" : NULL;
		$exception_email_body .= ( $replace_arr[12] != '' AND $replace_arr[13] != '' ) ? TTi18n::gettext('Schedule').': #schedule_start_time# - #schedule_end_time#'."\n" : NULL;
		$exception_email_body .= ( $replace_arr[14] != '' ) ? TTi18n::gettext('Schedule Branch').': #schedule_branch#'."\n" : NULL;
		$exception_email_body .= ( $replace_arr[15] != '' ) ? TTi18n::gettext('Schedule Department').': #schedule_department#'."\n" : NULL;
		if ( $replace_arr[16] != '' ) {
			$exception_email_body .= TTi18n::gettext('Punch').': #punch_time#'."\n";
		} elseif ( $replace_arr[12] != '' AND $replace_arr[13] != '' ) {
			$exception_email_body .= TTi18n::gettext('Punch').': '. TTi18n::gettext('None') ."\n";
		}

		$exception_email_body .= ( $replace_arr[2] != '' OR $replace_arr[3] != '' OR $replace_arr[4] != '' OR $replace_arr[5] != '' ) ? "\n" : NULL;
		$exception_email_body .= ( $replace_arr[2] != '' ) ? TTi18n::gettext('Default Branch').': #employee_default_branch#'."\n" : NULL;
		$exception_email_body .= ( $replace_arr[3] != '' ) ? TTi18n::gettext('Default Department').': #employee_default_department#'."\n" : NULL;
		$exception_email_body .= ( $replace_arr[4] != '' ) ? TTi18n::gettext('Group').': #employee_group#'."\n" : NULL;
		$exception_email_body .= ( $replace_arr[5] != '' ) ? TTi18n::gettext('Title').': #employee_title#'."\n" : NULL;

		$exception_email_body .= "\n";
		$exception_email_body .= TTi18n::gettext('Link').': <a href="'. Misc::getURLProtocol() .'://'. Misc::getHostName().Environment::getDefaultInterfaceBaseURL().'">'.APPLICATION_NAME.' '. TTi18n::gettext('Login') .'</a>';

		$exception_email_body .= ( $replace_arr[10] != '' ) ? "\n\n\n".TTi18n::gettext('Company').': #company_name#'."\n" : NULL; //Always put at the end

		$exception_email_body .= "\n\n".TTi18n::gettext('Email sent').': '. TTDate::getDate('DATE+TIME', time() )."\n";

		$subject = str_replace( $search_arr, $replace_arr, $exception_email_subject );
		//Debug::Text('Subject: '. $subject, __FILE__, __LINE__, __METHOD__, 10);

		$headers = array(
							'From'	  => $from,
							'Subject' => $subject,
							//Reply-To/Return-Path are handled in TTMail.
						);

		$body = '<html><body><pre>'.str_replace( $search_arr, $replace_arr, $exception_email_body ).'</pre></body></html>';
		Debug::Text('Body: '. $body, __FILE__, __LINE__, __METHOD__, 10);

		$mail = new TTMail();
		$mail->setTo( $email_to_arr );
		$mail->setHeaders( $headers );

		@$mail->getMIMEObject()->setHTMLBody($body);

		$mail->setBody( $mail->getMIMEObject()->get( $mail->default_mime_config ) );
		$retval = $mail->Send();

		if ( $retval == TRUE ) {
			TTLog::addEntry( $this->getId(), 500, TTi18n::getText('Email Exception').': '. Option::getByKey( $ep_obj->getType(), $ep_obj->getOptions('type') ) .' To: '. implode(', ', $email_to_arr), $u_obj->getID(), $this->getTable() ); //Make sure this log entry is assigned to the user triggering the exception so it can be viewed in the audit log.
		}

		return TRUE;
	}

	/**
	 * @param bool $ignore_warning
	 * @return bool
	 */
	function Validate( $ignore_warning = TRUE ) {
		//
		// BELOW: Validation code moved from set*() functions.
		//

		// User
		$ulf = TTnew( 'UserListFactory' ); /** @var UserListFactory $ulf */
		$this->Validator->isResultSetWithRows(	'user',
													$ulf->getByID($this->getUser()),
													TTi18n::gettext('Invalid Employee')
												);
		// Pay Period
		if ( $this->getPayPeriod() != FALSE AND $this->getPayPeriod() != TTUUID::getZeroID() ) {
			$pplf = TTnew( 'PayPeriodListFactory' ); /** @var PayPeriodListFactory $pplf */
			$this->Validator->isResultSetWithRows(	'pay_period',
															$pplf->getByID($this->getPayPeriod()),
															TTi18n::gettext('Invalid Pay Period')
														);
		}
		// Date
		if ( $this->getDateStamp() !== FALSE ) {
			$this->Validator->isDate(		'date_stamp',
													$this->getDateStamp(),
													TTi18n::gettext('Incorrect date'));
			if ( $this->Validator->isError('date_stamp') == FALSE ) {
				$this->setPayPeriod(); //Force pay period to be set as soon as the date is.
			}
		} else {
			$this->Validator->isTRUE(		'date_stamp',
												FALSE,
												TTi18n::gettext('Incorrect date')
											);
		}
		// Exception Policy ID
		if ( $this->getExceptionPolicyID() !== FALSE AND $this->getExceptionPolicyID() != TTUUID::getZeroID() ) {
			$eplf = TTnew( 'ExceptionPolicyListFactory' ); /** @var ExceptionPolicyListFactory $eplf */
			$this->Validator->isResultSetWithRows(	'exception_policy',
															$eplf->getByID($this->getExceptionPolicyID()),
															TTi18n::gettext('Invalid Exception Policy ID')
														);
		}
		// Punch Control ID
		if ( $this->getPunchControlID() !== FALSE AND $this->getPunchControlID() != TTUUID::getZeroID() ) {
			$pclf = TTnew( 'PunchControlListFactory' ); /** @var PunchControlListFactory $pclf */
			$this->Validator->isResultSetWithRows(	'punch_control',
															$pclf->getByID($this->getPunchControlID()),
															TTi18n::gettext('Invalid Punch Control ID')
														);
		}
		// Punch ID
		if ( $this->getPunchID() !== FALSE AND $this->getPunchID() != TTUUID::getZeroID() ) {
			$plf = TTnew( 'PunchListFactory' ); /** @var PunchListFactory $plf */
			$this->Validator->isResultSetWithRows(	'punch',
															$plf->getByID($this->getPunchID()),
															TTi18n::gettext('Invalid Punch ID')
														);
		}
		// Type
		$this->Validator->inArrayKey(	'type',
												$this->getType(),
												TTi18n::gettext('Incorrect Type'),
												$this->getOptions('type')
											);

		//
		// ABOVE: Validation code moved from set*() functions.
		//

		if ( $this->getDeleted() == FALSE AND $this->getDateStamp() == FALSE ) {
			$this->Validator->isTRUE(	'date_stamp',
										FALSE,
										TTi18n::gettext('Date/Time is incorrect, or pay period does not exist for this date. Please create a pay period schedule and assign this employee to it if you have not done so already') );
		}

		return TRUE;
	}

	/**
	 * @return bool
	 */
	function preSave() {
		if ( $this->getPayPeriod() == FALSE ) {
			$this->setPayPeriod();
		}

		return TRUE;
	}

	/**
	 * @return bool
	 */
	function postSave() {
		return TRUE;
	}

	/**
	 * @param $data
	 * @return bool
	 */
	function setObjectFromArray( $data ) {
		if ( is_array( $data ) ) {
			$variable_function_map = $this->getVariableToFunctionMap();
			foreach( $variable_function_map as $key => $function ) {
				if ( isset($data[$key]) ) {

					$function = 'set'.$function;
					switch( $key ) {
						default:
							if ( method_exists( $this, $function ) ) {
								$this->$function( $data[$key] );
							}
							break;
					}
				}
			}

			$this->setCreatedAndUpdatedColumns( $data );

			return TRUE;
		}

		return FALSE;
	}

	/**
	 * @param null $include_columns
	 * @param bool $permission_children_ids
	 * @return array
	 */
	function getObjectAsArray( $include_columns = NULL, $permission_children_ids = FALSE  ) {
		$variable_function_map = $this->getVariableToFunctionMap();

		$epf = TTnew( 'ExceptionPolicyFactory' ); /** @var ExceptionPolicyFactory $epf */
		$exception_policy_type_options = $epf->getOptions('type');
		$exception_policy_severity_options = $epf->getOptions('severity');

		$data = array();
		if ( is_array( $variable_function_map ) ) {
			foreach( $variable_function_map as $variable => $function_stub ) {
				if ( $include_columns == NULL OR ( isset($include_columns[$variable]) AND $include_columns[$variable] == TRUE ) ) {
					$function = 'get'.$function_stub;
					switch( $variable ) {
						case 'pay_period_id':
						case 'pay_period_schedule_id':
						//case 'pay_period_start_date':
						//case 'pay_period_end_date':
						//case 'pay_period_transaction_date':
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
							$data[$variable] = $this->getColumn( $variable );
							break;
						case 'severity':
							$data[$variable] = Option::getByKey( $this->getColumn( 'severity_id' ), $exception_policy_severity_options );
							break;
						case 'exception_policy_type':
							$data[$variable] = Option::getByKey( $this->getColumn( 'exception_policy_type_id' ), $exception_policy_type_options );
							break;
						case 'type':
							$function = 'get'.$variable;
							if ( method_exists( $this, $function ) ) {
								$data[$variable] = Option::getByKey( $this->$function(), $this->getOptions( $variable ) );
							}
							break;
						case 'date_stamp':
							$data[$variable] = TTDate::getAPIDate( 'DATE', $this->getDateStamp() );
							break;
						case 'pay_period_start_date':
							$data[$variable] = TTDate::getAPIDate( 'DATE', TTDate::strtotime( $this->getColumn( 'pay_period_start_date' ) ) );
							break;
						case 'pay_period_end_date':
							$data[$variable] = TTDate::getAPIDate( 'DATE', TTDate::strtotime( $this->getColumn( 'pay_period_end_date' ) ) );
							break;
						case 'pay_period':
						case 'pay_period_transaction_date':
							$data[$variable] = TTDate::getAPIDate( 'DATE', TTDate::strtotime( $this->getColumn( 'pay_period_transaction_date' ) ) );
							break;
						default:
							if ( method_exists( $this, $function ) ) {
								$data[$variable] = $this->$function();
							}
							break;
					}

				}
			}
			$this->getPermissionColumns( $data, $this->getColumn( 'user_id' ), $this->getCreatedBy(), $permission_children_ids, $include_columns );
			$this->getCreatedAndUpdatedColumns( $data, $include_columns );
		}

		return $data;
	}

}
?>
