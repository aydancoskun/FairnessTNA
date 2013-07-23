<?php
/*********************************************************************************
 * TimeTrex is a Payroll and Time Management program developed by
 * TimeTrex Software Inc. Copyright (C) 2003 - 2013 TimeTrex Software Inc.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License version 3 as published by
 * the Free Software Foundation with the addition of the following permission
 * added to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED
 * WORK IN WHICH THE COPYRIGHT IS OWNED BY TIMETREX, TIMETREX DISCLAIMS THE
 * WARRANTY OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE.  See the GNU Affero General Public License for more
 * details.
 *
 * You should have received a copy of the GNU Affero General Public License along
 * with this program; if not, see http://www.gnu.org/licenses or write to the Free
 * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301 USA.
 *
 * You can contact TimeTrex headquarters at Unit 22 - 2475 Dobbin Rd. Suite
 * #292 Westbank, BC V4T 2E9, Canada or at email address info@timetrex.com.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License
 * version 3, these Appropriate Legal Notices must retain the display of the
 * "Powered by TimeTrex" logo. If the display of the logo is not reasonably
 * feasible for technical reasons, the Appropriate Legal Notices must display
 * the words "Powered by TimeTrex".
 ********************************************************************************/
/*
 * $Revision: 9768 $
 * $Id: ScheduleFactory.class.php 9768 2013-05-06 17:17:50Z ipso $
 * $Date: 2013-05-06 10:17:50 -0700 (Mon, 06 May 2013) $
 */

/**
 * @package Modules\Schedule
 */
class ScheduleFactory extends Factory {
	protected $table = 'schedule';
	protected $pk_sequence_name = 'schedule_id_seq'; //PK Sequence name

	protected $user_date_obj = NULL;
	protected $schedule_policy_obj = NULL;
	protected $absence_policy_obj = NULL;
	protected $branch_obj = NULL;
	protected $department_obj = NULL;

	function _getFactoryOptions( $name ) {

		$retval = NULL;
		switch( $name ) {
			case 'type':
				$retval = array(

										//10  => TTi18n::gettext('OPEN'), //Available to be covered/overridden.
										//20 => TTi18n::gettext('Manual'),
										//30 => TTi18n::gettext('Recurring')
										//90  => TTi18n::gettext('Replaced'), //Replaced by another shift. Set replaced_id

										//Not displayed on schedules, used to overwrite recurring schedule if we want to change a 8AM-5PM recurring schedule
										//with a 6PM-11PM schedule? Although this can be done with an absence shift as well...
										//100 => TTi18n::gettext('Hidden'),
									);
				break;
			case 'status':
				$retval = array(
										//If user_id = 0 then the schedule is assumed to be open. That way its easy to assign recurring schedules
										//to user_id=0 for open shifts too.
										10 => TTi18n::gettext('Working'),
										20 => TTi18n::gettext('Absent'),
									);
				break;
			case 'columns':
				$retval = array(
										'-1000-first_name' => TTi18n::gettext('First Name'),
										'-1002-last_name' => TTi18n::gettext('Last Name'),
										'-1005-user_status' => TTi18n::gettext('Employee Status'),
										'-1010-title' => TTi18n::gettext('Title'),
										'-1039-group' => TTi18n::gettext('Group'),
										'-1040-default_branch' => TTi18n::gettext('Default Branch'),
										'-1050-default_department' => TTi18n::gettext('Default Department'),
										'-1160-branch' => TTi18n::gettext('Branch'),
										'-1170-department' => TTi18n::gettext('Department'),
										'-1200-status' => TTi18n::gettext('Status'),
										'-1210-schedule_policy_id' => TTi18n::gettext('Schedule Policy'),
										'-1215-date_stamp' => TTi18n::gettext('Date'),
										'-1220-start_time' => TTi18n::gettext('Start Time'),
										'-1230-end_time' => TTi18n::gettext('End Time'),
										'-1240-total_time' => TTi18n::gettext('Total Time'),
										'-1250-note' => TTi18n::gettext('Note'),

										'-2000-created_by' => TTi18n::gettext('Created By'),
										'-2010-created_date' => TTi18n::gettext('Created Date'),
										'-2020-updated_by' => TTi18n::gettext('Updated By'),
										'-2030-updated_date' => TTi18n::gettext('Updated Date'),
							);

				if ( getTTProductEdition() >= TT_PRODUCT_CORPORATE ) {
					$retval['-1180-job'] = TTi18n::gettext('Job');
					$retval['-1190-job_item'] = TTi18n::gettext('Task');
				}
				break;
			case 'list_columns':
				$retval = Misc::arrayIntersectByKey( $this->getOptions('default_display_columns'), Misc::trimSortPrefix( $this->getOptions('columns') ) );
				break;
			case 'default_display_columns': //Columns that are displayed by default.
				$retval = array(
								'first_name',
								'last_name',
								'status',
								'date_stamp',
								'start_time',
								'end_time',
								'total_time',
								);
				break;
			case 'unique_columns': //Columns that are unique, and disabled for mass editing.
				$retval = array(
								);
				break;
			case 'linked_columns': //Columns that are linked together, mainly for Mass Edit, if one changes, they all must.
				$retval = array(
								);
				break;
			case 'group_columns': //Columns available for grouping on the schedule.
				$retval = array(
								'title',
								'group',
								'default_branch',
								'default_department',
								'branch',
								'department',
								);

				if ( getTTProductEdition() >= TT_PRODUCT_CORPORATE ) {
					$retval[] = 'job';
					$retval[] = 'job_item';

				}
				break;
		}

		return $retval;
	}

	function _getVariableToFunctionMap( $data ) {
		$variable_function_map = array(
										'id' => 'ID',
										'company_id' => 'Company',
										'user_id' => 'User',
										'first_name' => FALSE,
										'last_name' => FALSE,
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

										'date_stamp' => FALSE,
										'user_date_id' => 'UserDateID',
										'pay_period_id' => FALSE,
										'status_id' => 'Status',
										'status' => FALSE,
										'schedule_policy_id' => FALSE,
										'schedule_policy' => FALSE,
										'start_date' => FALSE,
										'end_date' => FALSE,
										'start_time' => 'StartTime',
										'end_time' => 'EndTime',
										'schedule_policy_id' => 'SchedulePolicyID',
										'absence_policy_id' => 'AbsencePolicyID',
										'branch_id' => 'Branch',
										'branch' => FALSE,
										'department_id' => 'Department',
										'department' => FALSE,
										'job_id' => 'Job',
										'job' => FALSE,
										'job_item_id' => 'JobItem',
										'job_item' => FALSE,
										'total_time' => 'TotalTime',

										'note' => 'Note',

										'deleted' => 'Deleted',
										);
		return $variable_function_map;
	 }

	function getSchedulePolicyObject() {
		return $this->getGenericObject( 'SchedulePolicyListFactory', $this->getSchedulePolicyID(), 'schedule_policy_obj' );
	}

	function getAbsencePolicyObject() {
		return $this->getGenericObject( 'AbsencePolicyListFactory', $this->getAbsencePolicyID(), 'absence_policy_obj' );
	}

	function getUserDateObject() {
		return $this->getGenericObject( 'UserDateListFactory', $this->getUserDateID(), 'user_date_obj' );
	}

	function getBranchObject() {
		return $this->getGenericObject( 'BranchListFactory', $this->getBranch(), 'branch_obj' );
	}

	function getDepartmentObject() {
		return $this->getGenericObject( 'DepartmentListFactory', $this->getDepartment(), 'department_obj' );
	}

	function getCompany() {
		if ( isset($this->data['company_id']) ) {
			return $this->data['company_id'];
		}

		return FALSE;
	}
	function setCompany($id) {
		$id = trim($id);

		Debug::Text('Company ID: '. $id, __FILE__, __LINE__, __METHOD__,10);
		$clf = TTnew( 'CompanyListFactory' );

		if ( $this->Validator->isResultSetWithRows(	'company',
													$clf->getByID($id),
													TTi18n::gettext('Company is invalid')
													) ) {

			$this->data['company_id'] = $id;

			return TRUE;
		}

		return FALSE;
	}

	function getUser() {
		if ( isset($this->tmp_data['user_id']) ) {
			return $this->tmp_data['user_id'];
		}
	}
	function setUser($id) {
		$id = (int)$id;

		$ulf = TTnew( 'UserListFactory' );

		//Allow "blank" user for OPEN shifts.
		if ( ( $id == 0 AND getTTProductEdition() > TT_PRODUCT_COMMUNITY )
				OR
				$this->Validator->isResultSetWithRows(	'user_id',
															$ulf->getByID($id),
															TTi18n::gettext('Invalid Employee')
															) ) {
			$this->tmp_data['user_id'] = $id;

			return TRUE;
		}

		return FALSE;
	}

	function findUserDate($user_id, $epoch) {
		return $this->setUserDate( $user_id, $epoch );
	}

	function setUserDate($user_id, $date) {
		$user_date_id = UserDateFactory::findOrInsertUserDate( $user_id, $date);
		Debug::text(' User Date ID: '. $user_date_id, __FILE__, __LINE__, __METHOD__,10);
		if ( $user_date_id != '' ) {
			$this->setUser( $user_id );
			$this->setUserDateID( $user_date_id );
			return TRUE;
		}
		Debug::text(' No User Date ID found', __FILE__, __LINE__, __METHOD__,10);

		return FALSE;
	}

	function getOldUserDateID() {
		if ( isset($this->tmp_data['old_user_date_id']) ) {
			return $this->tmp_data['old_user_date_id'];
		}

		return FALSE;
	}
	function setOldUserDateID($id) {
		Debug::Text(' Setting Old User Date ID: '. $id, __FILE__, __LINE__, __METHOD__,10);
		$this->tmp_data['old_user_date_id'] = $id;

		return TRUE;
	}

	function getUserDateID() {
		if ( isset($this->data['user_date_id']) ) {
			return (int)$this->data['user_date_id'];
		}

		return FALSE;
	}

	function setUserDateID($id, $skip_check = FALSE ) {
		$id = (int)trim($id);

		$udlf = TTnew( 'UserDateListFactory' );
		Debug::text('Setting User Date ID to:'. $id, __FILE__, __LINE__, __METHOD__, 10);
		if (  	$skip_check == TRUE
				OR
				(
					$id > 0
					AND
					$this->Validator->isResultSetWithRows(	'user_date',
															$udlf->getByID($id),
															TTi18n::gettext('Date/Time is incorrect or pay period does not exist for this date. Please create a pay period schedule if you have not done so already')
															)
				)
				) {

			if ( $this->getUserDateID() !== $id AND $this->getOldUserDateID() != $this->getUserDateID() ) {
				Debug::Text(' Setting Old User Date ID... Current Old ID: '. (int)$this->getOldUserDateID() .' Current ID: '. (int)$this->getUserDateID(), __FILE__, __LINE__, __METHOD__,10);
				$this->setOldUserDateID( $this->getUserDateID() );
			}

			$this->data['user_date_id'] = $id;

			return TRUE;
		}

		return FALSE;
	}

	function getStatus() {
		if ( isset($this->data['status_id']) ) {
			return $this->data['status_id'];
		}

		return FALSE;
	}
	function setStatus($status) {
		$status = trim($status);

		$key = Option::getByValue($status, $this->getOptions('status') );
		if ($key !== FALSE) {
			$status = $key;
		}

		if ( $this->Validator->inArrayKey(	'status',
											$status,
											TTi18n::gettext('Incorrect Status'),
											$this->getOptions('status')) ) {

			$this->data['status_id'] = $status;

			return FALSE;
		}

		return FALSE;
	}

	function getStartTime( $raw = FALSE ) {
		if ( isset($this->data['start_time']) ) {
			return TTDate::strtotime( $this->data['start_time'] );
		}

		return FALSE;
	}
	function setStartTime($epoch) {
		$epoch = trim($epoch);

		if 	(	$this->Validator->isDate(		'start_time',
												$epoch,
												TTi18n::gettext('Incorrect start time'))

			) {

			$this->data['start_time'] = $epoch;

			return TRUE;
		}

		return FALSE;
	}

	function getEndTime( $raw = FALSE ) {
		if ( isset($this->data['end_time']) ) {
			return TTDate::strtotime( $this->data['end_time'] );
		}

		return FALSE;
	}
	function setEndTime($epoch) {
		$epoch = trim($epoch);

		if 	(	$this->Validator->isDate(		'end_time',
												$epoch,
												TTi18n::gettext('Incorrect end time'))

			) {

			$this->data['end_time'] = $epoch;

			return TRUE;
		}

		return FALSE;
	}

	function getMealPolicyDeductTime( $day_total_time, $filter_type_id = FALSE ) {
		$total_time = 0;
		if ( $this->getSchedulePolicyObject() != FALSE ) {
			if ( $this->getSchedulePolicyObject()->getMealPolicyObject() != FALSE ) {
				if ( ( $filter_type_id == FALSE AND ( $this->getSchedulePolicyObject()->getMealPolicyObject()->getType() == 10 OR $this->getSchedulePolicyObject()->getMealPolicyObject()->getType() == 20 ) )
					  OR ( $filter_type_id == $this->getSchedulePolicyObject()->getMealPolicyObject()->getType() ) ) {
					if ( $day_total_time > $this->getSchedulePolicyObject()->getMealPolicyObject()->getTriggerTime() ) {
						$total_time = $this->getSchedulePolicyObject()->getMealPolicyObject()->getAmount();
					}
				}
			}
		}

		$total_time = $total_time*-1;
		Debug::Text('Meal Policy Deduct Time: '. $total_time, __FILE__, __LINE__, __METHOD__, 10);

		return $total_time;
	}
	function getBreakPolicyDeductTime( $day_total_time, $filter_type_id = FALSE ) {
		$total_time = 0;
		if ( $this->getSchedulePolicyObject() != FALSE ) {
			$break_policy_ids = $this->getSchedulePolicyObject()->getBreakPolicy();
			if ( is_array($break_policy_ids) ) {
				foreach( $break_policy_ids as $break_policy_id ) {
					if ( $break_policy_id > 0 ) {
						$break_policy_obj = $this->getSchedulePolicyObject()->getBreakPolicyObject( $break_policy_id );
						if ( is_object($break_policy_obj)
								AND (
									 ( $filter_type_id == FALSE AND ( $break_policy_obj->getType() == 10 OR $break_policy_obj->getType() == 20 ) )
									 OR
									 ( $filter_type_id == $break_policy_obj->getType() )
									)
							) {
							if ( $day_total_time > $break_policy_obj->getTriggerTime() ) {
								$total_time += $break_policy_obj->getAmount();
							}
						}
					}
				}
			}
		}

		$total_time = $total_time*-1;
		Debug::Text('Break Policy Deduct Time: '. $total_time, __FILE__, __LINE__, __METHOD__, 10);

		return $total_time;
	}
	
	function calcRawTotalTime() {
		if ( $this->getStartTime() > 0 AND $this->getEndTime() > 0 ) {
			//Due to DST, always pay the employee based on the time they actually worked,
			//which is handled automatically by simple epoch math.
			//Therefore in fall they get paid one hour more, and spring one hour less.
			$total_time = ( $this->getEndTime() - $this->getStartTime() ); // + TTDate::getDSTOffset( $this->getStartTime(), $this->getEndTime() );
			Debug::Text('Start Time '.TTDate::getDate('DATE+TIME', $this->getStartTime()) .'('.$this->getStartTime().')  End Time: '. TTDate::getDate('DATE+TIME', $this->getEndTime()).'('.$this->getEndTime().') Total Time: '. TTDate::getHours( $total_time ), __FILE__, __LINE__, __METHOD__, 10);

			return $total_time;
		}

		return FALSE;
	}
	function calcTotalTime() {
		if ( $this->getStartTime() > 0 AND $this->getEndTime() > 0 ) {
			$total_time = $this->calcRawTotalTime();

			if ( $this->getSchedulePolicyObject() != FALSE ) {
				$total_time += $this->getMealPolicyDeductTime( $total_time );
				//if ( $this->getSchedulePolicyObject()->getMealPolicyObject() != FALSE ) {
				//	if ( $this->getSchedulePolicyObject()->getMealPolicyObject()->getType() == 10 OR $this->getSchedulePolicyObject()->getMealPolicyObject()->getType() == 20 ) {
				//		if ( $total_time > $this->getSchedulePolicyObject()->getMealPolicyObject()->getTriggerTime() ) {
				//			$total_time -= $this->getSchedulePolicyObject()->getMealPolicyObject()->getAmount();
				//		}
				//	}
				//}

				$total_time += $this->getBreakPolicyDeductTime( $total_time );
				//$break_policy_ids = $this->getSchedulePolicyObject()->getBreakPolicy();
				//if ( is_array($break_policy_ids) ) {
				//	foreach( $break_policy_ids as $break_policy_id ) {
				//		if ( $break_policy_id > 0 ) {
				//			$break_policy_obj = $this->getSchedulePolicyObject()->getBreakPolicyObject( $break_policy_id );
				//			if ( is_object($break_policy_obj) AND ( $break_policy_obj->getType() == 10 OR $break_policy_obj->getType() == 20 ) ) {
				//				if ( $total_time > $break_policy_obj->getTriggerTime() ) {
				//					$total_time -= $break_policy_obj->getAmount();
				//				}
				//			}
				//		}
				//	}
				//}
				//unset($break_policy_ids, $break_policy_id, $break_policy_obj);
			}

			return $total_time;
		}

		return FALSE;
	}

	function getTotalTime() {
		if ( isset($this->data['total_time']) ) {
			return (int)$this->data['total_time'];
		}
		return FALSE;
	}
	function setTotalTime($int) {
		$int = (int)$int;

		if 	(	$this->Validator->isNumeric(		'total_time',
													$int,
													TTi18n::gettext('Incorrect total time')) ) {
			$this->data['total_time'] = $int;

			return TRUE;
		}

		return FALSE;
	}


	function getSchedulePolicyID() {
		if ( isset($this->data['schedule_policy_id']) ) {
			return $this->data['schedule_policy_id'];
		}

		return FALSE;
	}
	function setSchedulePolicyID($id) {
		$id = trim($id);

		if ( $id == '' OR empty($id) ) {
			$id = NULL;
		}

		$splf = TTnew( 'SchedulePolicyListFactory' );

		if ( $id == NULL
				OR
				$this->Validator->isResultSetWithRows(	'schedule_policy',
														$splf->getByID($id),
														TTi18n::gettext('Schedule Policy is invalid')
													) ) {

			$this->data['schedule_policy_id'] = $id;

			return TRUE;
		}

		return FALSE;
	}

	function getAbsencePolicyID() {
		if ( isset($this->data['absence_policy_id']) ) {
			return $this->data['absence_policy_id'];
		}

		return FALSE;
	}
	function setAbsencePolicyID($id) {
		$id = trim($id);

		if ( $id == '' OR empty($id) ) {
			$id = NULL;
		}

		$aplf = TTnew( 'AbsencePolicyListFactory' );

		if (	$id == NULL
				OR
				$this->Validator->isResultSetWithRows(	'absence_policy',
														$aplf->getByID($id),
														TTi18n::gettext('Invalid Absence Policy ID')
														) ) {
			$this->data['absence_policy_id'] = $id;

			return TRUE;
		}

		return FALSE;
	}

	function getBranch() {
		if ( isset($this->data['branch_id']) ) {
			return $this->data['branch_id'];
		}

		return FALSE;
	}
	function setBranch($id) {
		$id = trim($id);

		if ( $id == FALSE OR $id == 0 OR $id == '' ) {
			$id = 0;
		}

		$blf = TTnew( 'BranchListFactory' );

		if (  $id == 0
				OR
				$this->Validator->isResultSetWithRows(	'branch',
														$blf->getByID($id),
														TTi18n::gettext('Branch does not exist')
														) ) {
			$this->data['branch_id'] = $id;

			return TRUE;
		}

		return FALSE;
	}

	function getDepartment() {
		if ( isset($this->data['department_id']) ) {
			return $this->data['department_id'];
		}

		return FALSE;
	}
	function setDepartment($id) {
		$id = trim($id);

		if ( $id == FALSE OR $id == 0 OR $id == '' ) {
			$id = 0;
		}

		$dlf = TTnew( 'DepartmentListFactory' );

		if (  $id == 0
				OR
				$this->Validator->isResultSetWithRows(	'department',
														$dlf->getByID($id),
														TTi18n::gettext('Department does not exist')
														) ) {
			$this->data['department_id'] = $id;

			return TRUE;
		}

		return FALSE;
	}

	function getJob() {
		if ( isset($this->data['job_id']) ) {
			return $this->data['job_id'];
		}

		return FALSE;
	}
	function setJob($id) {
		$id = trim($id);

		if ( $id == FALSE OR $id == 0 OR $id == '' ) {
			$id = 0;
		}

		if ( getTTProductEdition() >= TT_PRODUCT_CORPORATE ) {
			$jlf = TTnew( 'JobListFactory' );
		}

		if (  $id == 0
				OR
				$this->Validator->isResultSetWithRows(	'job',
														$jlf->getByID($id),
														TTi18n::gettext('Job does not exist')
														) ) {
			$this->data['job_id'] = $id;

			return TRUE;
		}

		return FALSE;
	}

	function getJobItem() {
		if ( isset($this->data['job_item_id']) ) {
			return $this->data['job_item_id'];
		}

		return FALSE;
	}
	function setJobItem($id) {
		$id = trim($id);

		if ( $id == FALSE OR $id == 0 OR $id == '' ) {
			$id = 0;
		}

		if ( getTTProductEdition() >= TT_PRODUCT_CORPORATE ) {
			$jilf = TTnew( 'JobItemListFactory' );
		}

		if (  $id == 0
				OR
				$this->Validator->isResultSetWithRows(	'job_item',
														$jilf->getByID($id),
														TTi18n::gettext('Job Item does not exist')
														) ) {
			$this->data['job_item_id'] = $id;

			return TRUE;
		}

		return FALSE;
	}

	function getNote() {
		if ( isset($this->data['note']) ) {
			return $this->data['note'];
		}

		return FALSE;
	}
	function setNote($val) {
		$val = trim($val);

		if 	(	$val == ''
				OR
				$this->Validator->isLength(		'note',
												$val,
												TTi18n::gettext('Note is too short or too long'),
												0,
												1024) ) {

			$this->data['note'] = $val;

			return TRUE;
		}

		return FALSE;
	}

	//Find the difference between $epoch and the schedule time, so we can determine the best schedule that fits.
	//**This returns FALSE when it doesn't match, so make sure you do an exact comparison using ===
	function inScheduleDifference( $epoch, $status_id = FALSE ) {
		$retval = FALSE;
		if ( $epoch >= $this->getStartTime() AND $epoch <= $this->getEndTime() ) {
			Debug::text('aWithin Schedule: '. $epoch, __FILE__, __LINE__, __METHOD__,10);

			$retval = 0; //Within schedule start/end time, no difference.
		} else  {
			if ( ( $status_id == FALSE OR $status_id == 10 ) AND $epoch < $this->getStartTime() AND $this->inStartWindow( $epoch ) ) {
				$retval = $this->getStartTime() - $epoch;
			} elseif ( ( $status_id == FALSE OR $status_id == 20 ) AND $epoch > $this->getEndTime() AND $this->inStopWindow( $epoch ) ) {
				$retval = $epoch - $this->getEndTime();
			} else {
				$retval = FALSE; //Not within start/stop window at all, return FALSE.
			}
		}

		Debug::text('Difference from schedule: "'. $retval .'" Epoch: '. $epoch .' Status: '. $status_id, __FILE__, __LINE__, __METHOD__,10);
		return $retval;
	}

	function inSchedule( $epoch ) {
		if ( $epoch >= $this->getStartTime() AND $epoch <= $this->getEndTime() ) {
			Debug::text('aWithin Schedule: '. $epoch, __FILE__, __LINE__, __METHOD__,10);

			return TRUE;
		} elseif ( $this->inStartWindow( $epoch ) OR $this->inStopWindow( $epoch ) )  {
			Debug::text('bWithin Schedule: '. $epoch, __FILE__, __LINE__, __METHOD__,10);

			return TRUE;
		}

		return FALSE;
	}

	function inStartWindow( $epoch ) {
		//Debug::text(' Epoch: '. $epoch, __FILE__, __LINE__, __METHOD__,10);

		if ( $epoch == '' ) {
			return FALSE;
		}

		if ( is_object( $this->getSchedulePolicyObject() ) ) {
			$start_stop_window = (int)$this->getSchedulePolicyObject()->getStartStopWindow();
		} else {
			$start_stop_window = 3600; //Default to 1hr
		}

		if ( $epoch >= ( $this->getStartTime() - $start_stop_window ) AND $epoch <= ( $this->getStartTime() + $start_stop_window ) ) {
			Debug::text(' Within Start/Stop window: '. $start_stop_window , __FILE__, __LINE__, __METHOD__,10);
			return TRUE;
		}

		//Debug::text(' NOT Within Start window. Epoch: '. $epoch .' Window: '. $start_stop_window, __FILE__, __LINE__, __METHOD__,10);
		return FALSE;
	}

	function inStopWindow( $epoch ) {
		//Debug::text(' Epoch: '. $epoch, __FILE__, __LINE__, __METHOD__,10);

		if ( $epoch == '' ) {
			return FALSE;
		}

		if ( is_object( $this->getSchedulePolicyObject() ) ) {
			$start_stop_window = (int)$this->getSchedulePolicyObject()->getStartStopWindow();
		} else {
			$start_stop_window = 3600; //Default to 1hr
		}

		if ( $epoch >= ( $this->getEndTime() - $start_stop_window ) AND $epoch <= ( $this->getEndTime() + $start_stop_window ) ) {
			Debug::text(' Within Start/Stop window: '. $start_stop_window , __FILE__, __LINE__, __METHOD__,10);
			return TRUE;
		}

		//Debug::text(' NOT Within Stop window. Epoch: '. $epoch .' Window: '. $start_stop_window, __FILE__, __LINE__, __METHOD__,10);
		return FALSE;
	}

	function mergeScheduleArray($schedule_shifts, $recurring_schedule_shifts) {
		//Debug::text('Merging Schedule, and Recurring Schedule Shifts: ', __FILE__, __LINE__, __METHOD__, 10);

		$ret_arr = $schedule_shifts;

		//Debug::Arr($schedule_shifts, '(c) Schedule Shifts: ', __FILE__, __LINE__, __METHOD__, 10);

		if ( is_array($recurring_schedule_shifts) AND count($recurring_schedule_shifts) > 0 ) {
			foreach( $recurring_schedule_shifts as $date_stamp => $day_shifts_arr ) {
				//Debug::text('----------------------------------', __FILE__, __LINE__, __METHOD__, 10);
				//Debug::text('Date Stamp: '. TTDate::getDate('DATE+TIME', $date_stamp). ' Epoch: '. $date_stamp , __FILE__, __LINE__, __METHOD__, 10);
				//Debug::Arr($schedule_shifts[$date_stamp], 'Date Arr: ', __FILE__, __LINE__, __METHOD__, 10);
				foreach( $day_shifts_arr as $key => $shift_arr ) {

					if ( isset($ret_arr[$date_stamp]) ) {
						//Debug::text('Already Schedule Shift on this day: '. TTDate::getDate('DATE', $date_stamp) , __FILE__, __LINE__, __METHOD__, 10);

						//Loop through each shift on this day, and check for overlaps
						//Only include the recurring shift if ALL times DO NOT overlap
						$overlap = 0;
						foreach( $ret_arr[$date_stamp] as $tmp_shift_arr ) {
							if ( TTDate::isTimeOverLap( $shift_arr['start_time'], $shift_arr['end_time'], $tmp_shift_arr['start_time'], $tmp_shift_arr['end_time']) ) {
								//Debug::text('Times OverLap: '. TTDate::getDate('DATE+TIME', $shift_arr['start_time']) , __FILE__, __LINE__, __METHOD__, 10);
								$overlap++;
							} else {
								//Debug::text('Times DO NOT OverLap: '. TTDate::getDate('DATE+TIME', $shift_arr['start_time']) , __FILE__, __LINE__, __METHOD__, 10);
							}
						}

						if ( $overlap == 0 ) {
							//Debug::text('NO Times OverLap, using recurring schedule: '. TTDate::getDate('DATE+TIME', $shift_arr['start_time']) , __FILE__, __LINE__, __METHOD__, 10);
							$ret_arr[$date_stamp][] = $shift_arr;
						}
					} else {
						//Debug::text('No Schedule Shift on this day: '. TTDate::getDate('DATE', $date_stamp) , __FILE__, __LINE__, __METHOD__, 10);
						$ret_arr[$date_stamp][] = $shift_arr;
					}
				}
			}
		}

		return $ret_arr;
	}

	function getScheduleArray( $filter_data )  {
		global $current_user, $current_user_prefs;

		//Get all schedule data by general filter criteria.
		//Debug::Arr($filter_data, 'Filter Data: ', __FILE__, __LINE__, __METHOD__, 10);

		if ( !isset($filter_data['start_date']) OR $filter_data['start_date'] == '' ) {
			return FALSE;
		}

		if ( !isset($filter_data['end_date']) OR $filter_data['end_date'] == '' ) {
			return FALSE;
		}

		$filter_data['start_date'] = TTDate::getBeginDayEpoch( $filter_data['start_date'] );
		$filter_data['end_date'] = TTDate::getEndDayEpoch( $filter_data['end_date'] );
        
		$schedule_shifts_index = array();
		$branch_options = array(); //No longer needed, use SQL instead.
		$department_options = array(); //No longer needed, use SQL instead.

		$apf = TTnew( 'AbsencePolicyFactory' );
		$absence_policy_paid_type_options = $apf->getOptions('paid_type');

		$max_i = 0;

		$slf = TTnew( 'ScheduleListFactory' );
		$slf->getSearchByCompanyIdAndArrayCriteria( $current_user->getCompany(), $filter_data );
		Debug::text('Found Scheduled Rows: '. $slf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
		//Debug::Arr($absence_policy_paid_type_options, 'Paid Absences: ', __FILE__, __LINE__, __METHOD__, 10);
        if ( $slf->getRecordCount() > 0 ) {
			$this->getProgressBarObject()->start( $this->getAMFMessageID(), $slf->getRecordCount(), NULL, TTi18n::getText('Processing Committed Shifts...') );

			$i=0;
			foreach( $slf as $s_obj ) {
				//Debug::text('Schedule ID: '. $s_obj->getId() .' User ID: '. $s_obj->getColumn('user_id') .' Start Time: '. $s_obj->getStartTime(), __FILE__, __LINE__, __METHOD__, 10);
				if ( $s_obj->getAbsencePolicyID() > 0 ) {
					$absence_policy_name = $s_obj->getColumn('absence_policy');
				} else {
					$absence_policy_name = NULL; //Must be NULL for it to appear as "N/A" in legacy interface.
				}

				$hourly_rate = Misc::MoneyFormat( $s_obj->getColumn('user_wage_hourly_rate'), FALSE );

				if ( $s_obj->getAbsencePolicyID() > 0
						AND  is_object($s_obj->getAbsencePolicyObject())
						AND in_array( $s_obj->getAbsencePolicyObject()->getType(), $absence_policy_paid_type_options ) == FALSE ) {
					//UnPaid Absence.
					$total_time_wage = Misc::MoneyFormat(0);
				} else {
					$total_time_wage = Misc::MoneyFormat( bcmul( TTDate::getHours( $s_obj->getColumn('total_time') ), $hourly_rate ), FALSE );
				}

				$iso_date_stamp = TTDate::getISODateStamp($s_obj->getStartTime());
				//$schedule_shifts[$iso_date_stamp][$s_obj->getColumn('user_id').$s_obj->getStartTime()] = array(
				$schedule_shifts[$iso_date_stamp][$i] = array(
													'id' => (int)$s_obj->getID(),
													'pay_period_id' => (int)$s_obj->getColumn('pay_period_id'),
													'user_id' => (int)$s_obj->getColumn('user_id'),
													'user_created_by' => (int)$s_obj->getColumn('user_created_by'),
													'user_full_name' => ( $s_obj->getColumn('user_id') > 0 ) ? Misc::getFullName( $s_obj->getColumn('first_name'), NULL, $s_obj->getColumn('last_name'), FALSE, FALSE ) : TTi18n::getText('OPEN'),
													'first_name' => $s_obj->getColumn('first_name'),
													'last_name' => $s_obj->getColumn('last_name'),
													'title_id' => $s_obj->getColumn('title_id'),
													'title' => $s_obj->getColumn('title'),
													'group_id' => $s_obj->getColumn('group_id'),
													'group' => $s_obj->getColumn('group'),
													'default_branch_id' => $s_obj->getColumn('default_branch_id'),
													'default_branch' => $s_obj->getColumn('default_branch'),
													'default_department_id' => $s_obj->getColumn('default_department_id'),
													'default_department' => $s_obj->getColumn('default_department'),

													'job_id' => $s_obj->getColumn('job_id'),
													'job' => $s_obj->getColumn('job'),
													'job_status_id' => $s_obj->getColumn('job_status_id'),
													'job_manual_id' => $s_obj->getColumn('job_manual_id'),
													'job_branch_id' => $s_obj->getColumn('job_branch_id'),
													'job_department_id' => $s_obj->getColumn('job_department_id'),
													'job_group_id' => $s_obj->getColumn('job_group_id'),
													'job_item_id' => $s_obj->getColumn('job_item_id'),
													'job_item' => $s_obj->getColumn('job_item'),

													'type_id' => 10, //Committed
													'status_id' => (int)$s_obj->getStatus(),

													'date_stamp' => TTDate::getAPIDate( 'DATE', strtotime( $s_obj->getColumn('date_stamp') ) ),
													'start_date' => ( defined('TIMETREX_API') ) ? TTDate::getAPIDate('DATE+TIME', $s_obj->getStartTime() ) : $s_obj->getStartTime(),
													'end_date' => ( defined('TIMETREX_API') ) ? TTDate::getAPIDate('DATE+TIME', $s_obj->getEndTime() ) : $s_obj->getEndTime(),
													'start_time' => ( defined('TIMETREX_API') ) ? TTDate::getAPIDate('TIME', $s_obj->getStartTime() ) : $s_obj->getStartTime(),
													'end_time' => ( defined('TIMETREX_API') ) ? TTDate::getAPIDate('TIME', $s_obj->getEndTime() ) : $s_obj->getEndTime(),

													'total_time' => $s_obj->getTotalTime(),

													'hourly_rate' => $hourly_rate,
													'total_time_wage' => $total_time_wage,

													'note' => $s_obj->getColumn('note'),

													'schedule_policy_id' => (int)$s_obj->getSchedulePolicyID(),
													'absence_policy_id' => (int)$s_obj->getAbsencePolicyID(),
													'absence_policy' => $absence_policy_name,
													'branch_id' => (int)$s_obj->getBranch(),
													'branch' => $s_obj->getColumn('branch'),
													'department_id' => (int)$s_obj->getDepartment(),
													'department' => $s_obj->getColumn('department'),

													'created_by_id' => $s_obj->getCreatedBy(),
													'created_date' => $s_obj->getCreatedDate(),
													'updated_date' => $s_obj->getUpdatedDate(),
												);
				//$schedule_shifts_index[$iso_date_stamp][$s_obj->getColumn('user_id')][] = $s_obj->getColumn('user_id').$s_obj->getStartTime();
				$schedule_shifts_index[$iso_date_stamp][$s_obj->getColumn('user_id')][] = $i;
				unset($absence_policy_name);

				$this->getProgressBarObject()->set( $this->getAMFMessageID(), $slf->getCurrentRow() );

				$i++;
			}
			$max_i = $i;
			unset($i);

			$this->getProgressBarObject()->stop( $this->getAMFMessageID() );

			//Debug::Arr($schedule_shifts, 'Committed Schedule Shifts: ', __FILE__, __LINE__, __METHOD__, 10);
			//Debug::Arr($schedule_shifts_index, 'Committed Schedule Shifts Index: ', __FILE__, __LINE__, __METHOD__, 10);
		} else {
			$schedule_shifts = array();
		}
		unset($slf);

		//Get holidays
		//FIXME: What if there are two holiday policies, one that defaults to working, and another that defaults to not working, and they are assigned
		//to two different groups of employees? For that matter what if the holiday policy isn't assigned to a specific user at all.
		$holiday_data = array();
		$hlf = TTnew( 'HolidayListFactory' );
		$hlf->getByCompanyIdAndStartDateAndEndDate( $current_user->getCompany(), $filter_data['start_date'], $filter_data['end_date'] );
		Debug::text('Found Holiday Rows: '. $hlf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
		foreach( $hlf as $h_obj ) {
			if ( is_object( $h_obj->getHolidayPolicyObject() ) AND is_object( $h_obj->getHolidayPolicyObject()->getAbsencePolicyObject() ) ) {
				$holiday_data[TTDate::getISODateStamp($h_obj->getDateStamp())] = array('status_id' => (int)$h_obj->getHolidayPolicyObject()->getDefaultScheduleStatus(), 'absence_policy_id' => $h_obj->getHolidayPolicyObject()->getAbsencePolicyID(), 'type_id' => $h_obj->getHolidayPolicyObject()->getAbsencePolicyObject()->getType(), 'absence_policy' => $h_obj->getHolidayPolicyObject()->getAbsencePolicyObject()->getName() );
			} else {
				$holiday_data[TTDate::getISODateStamp($h_obj->getDateStamp())] = array('status_id' => 10 ); //Working
			}
		}
		unset($hlf);

		$recurring_schedule_shifts = array();
		$open_shift_conflict_index = array();

		$rstlf = TTnew( 'RecurringScheduleTemplateListFactory' );
		//Order for this is critcal to working with OPEN shifts. OPEN shifts (user_id=0) must come last, so it can find all conflicting shifts that will override it.
		//Also order by start_time so earlier shifts come first and therefore are the first to be overridden.
		$rstlf->getSearchByCompanyIdAndArrayCriteria( $current_user->getCompany(), $filter_data, NULL, NULL, NULL, array( 'c.start_date' => 'asc', 'cb.user_id' => 'desc', 'a.week' => 'asc', 'a.start_time' => 'asc' ) );
		Debug::text('Found Recurring Schedule Template Rows: '. $rstlf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
		if ( $rstlf->getRecordCount() > 0 ) {
			$this->getProgressBarObject()->start( $this->getAMFMessageID(), $rstlf->getRecordCount(), NULL, TTi18n::getText('Processing Recurring Shifts...') );

			foreach( $rstlf as $rst_obj ) {
				Debug::text('Recurring Schedule Template ID: '. $rst_obj->getID() , __FILE__, __LINE__, __METHOD__, 10);
				$rst_obj->getShifts( $filter_data['start_date'], $filter_data['end_date'], $holiday_data, $branch_options, $department_options, $max_i, $schedule_shifts, $schedule_shifts_index, $open_shift_conflict_index );

				$this->getProgressBarObject()->set( $this->getAMFMessageID(), $rstlf->getCurrentRow() );
			}

			$this->getProgressBarObject()->stop( $this->getAMFMessageID() );
		} else {
			Debug::text('DID NOT find Recurring Schedule for this time period: ', __FILE__, __LINE__, __METHOD__, 10);
		}
		unset($rstlf, $rst_obj, $open_shift_conflict_index);
		//Debug::Arr($schedule_shifts, 'Schedule Shifts: ', __FILE__, __LINE__, __METHOD__, 10);

		//Include employees without scheduled shifts.
		if ( isset($filter_data['include_all_users']) AND $filter_data['include_all_users'] == TRUE ) {
			if ( !isset($filter_data['exclude_id']) ) {
				$filter_data['exclude_id'] = array();
			}

			//If the user is searching for scheduled branch/departments, convert that to default branch/departments when Show All Employees is enabled.
			if ( isset($filter_data['branch_ids']) AND !isset($filter_data['default_branch_ids']) ) {
				$filter_data['default_branch_ids'] = $filter_data['branch_ids'];
			}
			if ( isset($filter_data['department_ids']) AND !isset($filter_data['default_department_ids']) ) {
				$filter_data['default_department_ids'] = $filter_data['department_ids'];
			}

			//Loop through schedule_shifts_index getting user_ids.
			foreach( $schedule_shifts_index as $date_stamp => $date_shifts ) {
				$filter_data['exclude_id'] = array_unique( array_merge( $filter_data['exclude_id'], array_keys( $date_shifts ) ) );
			}
			unset($date_stamp, $date_shifts);

			if ( isset($filter_data['exclude_id']) ) {
				//Debug::Arr($filter_data['exclude_id'], 'Including all employees. Excluded User Ids: ', __FILE__, __LINE__, __METHOD__, 10);
				//Debug::Arr($filter_data, 'All Filter Data: ', __FILE__, __LINE__, __METHOD__, 10);

				//Only include active employees without any scheduled shifts.
				$filter_data['status_id'] = 10;

				$ulf = TTnew( 'UserListFactory' );
				$ulf->getAPISearchByCompanyIdAndArrayCriteria( $current_user->getCompany(), $filter_data );
				Debug::text('Found blank employees: '. $ulf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
				if ( $ulf->getRecordCount() > 0 ) {
					$this->getProgressBarObject()->start( $this->getAMFMessageID(), $ulf->getRecordCount(), NULL, TTi18n::getText('Processing Employees...') );

					$i = $max_i;
					foreach( $ulf as $u_obj ) {
						//Create dummy shift arrays with no start/end time.
						//$schedule_shifts[TTDate::getISODateStamp( $filter_data['start_date'] )][$u_obj->getID().TTDate::getBeginDayEpoch($filter_data['start_date'])] = array(
						$schedule_shifts[TTDate::getISODateStamp( $filter_data['start_date'] )][$i] = array(
															//'id' => (int)$u_obj->getID(),
															'pay_period_id' => FALSE,
															'user_id' => (int)$u_obj->getID(),
															'user_created_by' => (int)$u_obj->getCreatedBy(),
															'user_full_name' => Misc::getFullName( $u_obj->getFirstName(), NULL, $u_obj->getLastName(), FALSE, FALSE ),
															'first_name' => $u_obj->getFirstName(),
															'last_name' => $u_obj->getLastName(),
															'title_id' => $u_obj->getTitle(),
															'title' => $u_obj->getColumn('title'),
															'group_id' => $u_obj->getColumn('group_id'),
															'group' => $u_obj->getColumn('group'),
															'default_branch_id' => $u_obj->getColumn('default_branch_id'),
															'default_branch' => $u_obj->getColumn('default_branch'),
															'default_department_id' => $u_obj->getColumn('default_department_id'),
															'default_department' => $u_obj->getColumn('default_department'),

															'branch_id' => (int)$u_obj->getDefaultBranch(),
															'branch' => $u_obj->getColumn('default_branch'),
															'department_id' => (int)$u_obj->getDefaultDepartment(),
															'department' => $u_obj->getColumn('default_department'),

															'created_by_id' => $u_obj->getCreatedBy(),
															'created_date' => $u_obj->getCreatedDate(),
															'updated_date' => $u_obj->getUpdatedDate(),
														);

						$this->getProgressBarObject()->set( $this->getAMFMessageID(), $ulf->getCurrentRow() );

						$i++;
					}

					$this->getProgressBarObject()->stop( $this->getAMFMessageID() );
				}
			}
			//Debug::Arr($schedule_shifts, 'Final Scheduled Shifts: ', __FILE__, __LINE__, __METHOD__, 10);
		}
		unset($schedule_shifts_index);

		if ( isset($schedule_shifts) ) {
			return $schedule_shifts;
		}

		return FALSE;
	}

	function getEnableReCalculateDay() {
		if ( isset($this->recalc_day) ) {
			return $this->recalc_day;
		}

		return FALSE;
	}
	function setEnableReCalculateDay($bool) {
		$this->recalc_day = $bool;

		return TRUE;
	}

	function getEnableOverwrite() {
		if ( isset($this->overwrite) ) {
			return $this->overwrite;
		}

		return FALSE;
	}
	function setEnableOverwrite($bool) {
		$this->overwrite = $bool;

		return TRUE;
	}

	function getEnableTimeSheetVerificationCheck() {
		if ( isset($this->timesheet_verification_check) ) {
			return $this->timesheet_verification_check;
		}

		return FALSE;
	}
	function setEnableTimeSheetVerificationCheck($bool) {
		$this->timesheet_verification_check = $bool;

		return TRUE;
	}

	function handleDayBoundary() {
		Debug::Text('Start Time '.TTDate::getDate('DATE+TIME', $this->getStartTime()) .'('.$this->getStartTime().')  End Time: '. TTDate::getDate('DATE+TIME', $this->getEndTime()).'('.$this->getEndTime().')', __FILE__, __LINE__, __METHOD__, 10);

		//This used to be done in Validate, but needs to be done in preSave too.
		//Allow 12:00AM to 12:00AM schedules for a total of 24hrs.
		if ( $this->getEndTime() <= $this->getStartTime() ) {
			//Since the initial end time is the same date as the start time, we need to see if DST affects between that end time and one day later. NOT the start time.
			//Due to DST, always pay the employee based on the time they actually worked,
			//which is handled automatically by simple epoch math.
			//Therefore in fall they get paid one hour more, and spring one hour less.
			//$this->setEndTime( $this->getEndTime() + ( 86400 + (TTDate::getDSTOffset( $this->getEndTime(), ($this->getEndTime() + 86400) ) ) ) ); //End time spans midnight, add 24hrs.
			$this->setEndTime( strtotime('+1 day', $this->getEndTime() ) ); //Using strtotime handles DST properly, whereas adding 86400 causes strange behavior.
			Debug::Text('EndTime spans midnight boundary! Bump to next day... New End Time: '. TTDate::getDate('DATE+TIME', $this->getEndTime()).'('.$this->getEndTime().')', __FILE__, __LINE__, __METHOD__,10);
		}

		return TRUE;
	}

	//Write all the schedules shifts for a given week.
	function writeWeekSchedule( $pdf, $cell_width, $week_date_stamps, $max_week_data, $left_margin, $group_schedule, $start_week_day = 0, $bottom_border = FALSE) {
		$week_of_year = TTDate::getWeek( strtotime($week_date_stamps[0]), $start_week_day);
		//Debug::Text('Max Week Shifts: '. (int)$max_week_data[$week_of_year]['shift'], __FILE__, __LINE__, __METHOD__,10);
		//Debug::Text('Max Week Branches: '. count($max_week_data[$week_of_year]['branch']), __FILE__, __LINE__, __METHOD__,10);
		//Debug::Text('Max Week Departments: '. count($max_week_data[$week_of_year]['department']), __FILE__, __LINE__, __METHOD__,10);
		Debug::Text('Week Of Year: '. $week_of_year, __FILE__, __LINE__, __METHOD__,10);
		Debug::Arr($max_week_data, 'max_week_data: ', __FILE__, __LINE__, __METHOD__,10);

		$week_data_array = NULL;

		if ( !isset($max_week_data[$week_of_year]['labels']) ) {
			$max_week_data[$week_of_year]['labels'] = 0;
		}

		if ( $group_schedule == TRUE ) {
			$min_rows_multiplier = 2;
		} else {
			$min_rows_multiplier = 1;
		}

		if ( isset($max_week_data[$week_of_year]['shift']) ) {
			$min_rows_per_day = ($max_week_data[$week_of_year]['shift']*$min_rows_multiplier) + $max_week_data[$week_of_year]['labels'];
			Debug::Text('Shift Total: '. $max_week_data[$week_of_year]['shift'], __FILE__, __LINE__, __METHOD__,10);
		} else {
			$min_rows_per_day = $min_rows_multiplier + $max_week_data[$week_of_year]['labels'];
		}
		Debug::Text('aMin Rows Per Day: '. $min_rows_per_day .' Labels: '. $max_week_data[$week_of_year]['labels'], __FILE__, __LINE__, __METHOD__,10);
		//print_r($this->schedule_shifts);

		//Prepare data so we can write it out line by line, left to right.
		$shift_counter = 0;
		foreach( $week_date_stamps as $week_date_stamp ) {
			Debug::Text('Week Date Stamp: ('.$week_date_stamp.')'. TTDate::getDate('DATE+TIME', strtotime($week_date_stamp)), __FILE__, __LINE__, __METHOD__,10);

			$rows_per_day = 0;
			if ( isset($this->schedule_shifts[$week_date_stamp]) ) {
				foreach( $this->schedule_shifts[$week_date_stamp] as $branch => $department_schedule_shifts ) {
					if ( $branch != '--' ) {
						$tmp_week_data_array[$week_date_stamp][] = array('type' => 'branch', 'date_stamp' => $week_date_stamp, 'label' => $branch );
						$rows_per_day++;
					}

					foreach( $department_schedule_shifts as $department => $tmp_schedule_shifts ) {
						if ( $department != '--' ) {
							$tmp_week_data_array[$week_date_stamp][] = array('type' => 'department', 'label' => $department );
							$rows_per_day++;
						}

						foreach( $tmp_schedule_shifts as $schedule_shift ) {
							if ( $group_schedule == TRUE ) {
								$tmp_week_data_array[$week_date_stamp][] = array('type' => 'user_name', 'label' => $schedule_shift['user_full_name'], 'shift' => $shift_counter );
								if ( $schedule_shift['status_id'] == 10 ) {
									$tmp_week_data_array[$week_date_stamp][] = array('type' => 'shift', 'label' => TTDate::getDate('TIME', $schedule_shift['start_time'] ) .' - '. TTDate::getDate('TIME', $schedule_shift['end_time'] ), 'shift' => $shift_counter );
								} else {
									$tmp_week_data_array[$week_date_stamp][] = array('type' => 'absence', 'label' => $schedule_shift['absence_policy'], 'shift' => $shift_counter );
								}
								$rows_per_day += 2;
							} else {
								if ( $schedule_shift['status_id'] == 10 ) {
									$tmp_week_data_array[$week_date_stamp][] = array('type' => 'shift', 'label' => TTDate::getDate('TIME', $schedule_shift['start_time'] ) .' - '. TTDate::getDate('TIME', $schedule_shift['end_time'] ), 'shift' => $shift_counter );
								} else {
									$tmp_week_data_array[$week_date_stamp][] = array('type' => 'absence', 'label' => $schedule_shift['absence_policy'], 'shift' => $shift_counter );
								}
								$rows_per_day++;
							}
							$shift_counter++;
						}
					}
				}
			}

			if ( $rows_per_day < $min_rows_per_day ) {
				for($z=$rows_per_day; $z < $min_rows_per_day; $z++) {
					$tmp_week_data_array[$week_date_stamp][] = array('type' => 'blank', 'label' => NULL );
				}
			}
		}
		//print_r($tmp_week_data_array);

		for($x=0; $x < $min_rows_per_day; $x++ ) {
			foreach( $week_date_stamps as $week_date_stamp ) {
				if ( isset($tmp_week_data_array[$week_date_stamp][0]) ) {
					$week_data_array[] = $tmp_week_data_array[$week_date_stamp][0];
					array_shift($tmp_week_data_array[$week_date_stamp]);
				}
			}
		}
		unset($tmp_week_data_array);
		//print_r($week_data_array);

		//Render PDF here
		$border = 'LR';
		$i=0;
		$total_cells = count($week_data_array);

		foreach( $week_data_array as $key => $data ) {
			if ( $i % 7 == 0 ) {
				$pdf->Ln();
			}

			$pdf->setTextColor(0,0,0); //Black
			switch( $data['type'] ) {
				case 'branch':
					$pdf->setFillColor(200,200,200);
					$pdf->SetFont('freesans','B',8);
					break;
				case 'department':
					$pdf->setFillColor(220,220,220);
					$pdf->SetFont('freesans','B',8);
					break;
				case 'user_name':
					if ( $data['shift'] % 2 == 0 ) {
						$pdf->setFillColor(240,240,240);
					} else {
						$pdf->setFillColor(255,255,255);
					}
					$pdf->SetFont('freesans','B',8);
					break;
				case 'shift':
					if ( $data['shift'] % 2 == 0 ) {
						$pdf->setFillColor(240,240,240);
					} else {
						$pdf->setFillColor(255,255,255);
					}
					$pdf->SetFont('freesans','',8);
					break;
				case 'absence':
					$pdf->setTextColor(255,0,0);
					if ( $data['shift'] % 2 == 0 ) {
						$pdf->setFillColor(240,240,240);
					} else {
						$pdf->setFillColor(255,255,255);
					}
					$pdf->SetFont('freesans','I',8);
					break;
				case 'blank':
					$pdf->setFillColor(255,255,255);
					$pdf->SetFont('freesans','',8);
					break;
			}

			if ( $bottom_border == TRUE AND $i >= ($total_cells-7) ) {
				$border = 'LRB';
			}

			$pdf->Cell($cell_width, 15, $data['label'], $border, 0, 'C', 1);
			$pdf->setTextColor(0,0,0); //Black

			$i++;
		}

		$pdf->Ln();

		return TRUE;
	}

	//function getSchedule( $company_id, $user_ids, $start_date, $end_date, $start_week_day = 0, $group_schedule = FALSE ) {
	function getSchedule( $filter_data, $start_week_day = 0, $group_schedule = FALSE ) {
		global $current_user, $current_user_prefs;

		//Individual is one schedule per employee, or all on one schedule.
		if (!is_array($filter_data) ) {
			return FALSE;
		}

		$current_epoch = time();

		//Debug::Text('Start Date: '. TTDate::getDate('DATE', $start_date) .' End Date: '. TTDate::getDate('DATE', $end_date) , __FILE__, __LINE__, __METHOD__,10);
		Debug::text(' Start Date: '. TTDate::getDate('DATE+TIME', $filter_data['start_date']) .' End Date: '. TTDate::getDate('DATE+TIME', $filter_data['end_date']) .' Start Week Day: '. $start_week_day, __FILE__, __LINE__, __METHOD__,10);

		$pdf = new TTPDF('L', 'pt', 'Letter');

		$left_margin = 20;
		$top_margin = 20;
		$pdf->setMargins($left_margin,$top_margin);
		$pdf->SetAutoPageBreak(TRUE, 30);
		//$pdf->SetAutoPageBreak(FALSE);
		$pdf->SetFont('freesans','',10);

		$border = 0;
		$adjust_x = 0;
		$adjust_y = 0;

		if ( $group_schedule == FALSE ) {
			$valid_schedules = 0;

			$sf = TTnew( 'ScheduleFactory' );
			$tmp_schedule_shifts = $sf->getScheduleArray( $filter_data );
			//Re-arrange array by user_id->date
			if ( is_array($tmp_schedule_shifts) ) {
				foreach( $tmp_schedule_shifts as $day_epoch => $day_schedule_shifts ) {
					foreach ( $day_schedule_shifts as $day_schedule_shift ) {
						$raw_schedule_shifts[$day_schedule_shift['user_id']][$day_epoch][] = $day_schedule_shift;
					}
				}
			}
			unset($tmp_schedule_shifts);
			//Debug::Arr($raw_schedule_shifts, 'Raw Schedule Shifts: ', __FILE__, __LINE__, __METHOD__,10);

			if ( isset($raw_schedule_shifts) AND is_array($raw_schedule_shifts) ) {
				foreach( $raw_schedule_shifts as $user_id => $day_schedule_shifts ) {

					foreach( $day_schedule_shifts as $day_epoch => $day_schedule_shifts ) {
						foreach ( $day_schedule_shifts as $day_schedule_shift ) {
							//Debug::Arr($day_schedule_shift, 'aDay Schedule Shift: ', __FILE__, __LINE__, __METHOD__,10);
							$tmp_schedule_shifts[$day_epoch][$day_schedule_shift['branch']][$day_schedule_shift['department']][] = $day_schedule_shift;

							if ( isset($schedule_shift_totals[$day_epoch]['total_shifts']) ) {
								$schedule_shift_totals[$day_epoch]['total_shifts']++;
							} else {
								$schedule_shift_totals[$day_epoch]['total_shifts'] = 1;
							}

							//$week_of_year = TTDate::getWeek( strtotime($day_epoch) );
							$week_of_year = TTDate::getWeek( strtotime($day_epoch), $start_week_day );
							if ( !isset($schedule_shift_totals[$day_epoch]['labels']) ) {
								$schedule_shift_totals[$day_epoch]['labels'] = 0;
							}
							if ( $day_schedule_shift['branch'] != '--'
									AND !isset($schedule_shift_totals[$day_epoch]['branch'][$day_schedule_shift['branch']]) ) {
								$schedule_shift_totals[$day_epoch]['branch'][$day_schedule_shift['branch']] = TRUE;
								$schedule_shift_totals[$day_epoch]['labels']++;
							}
							if ( $day_schedule_shift['department'] != '--'
									AND !isset($schedule_shift_totals[$day_epoch]['department'][$day_schedule_shift['branch']][$day_schedule_shift['department']]) ) {
								$schedule_shift_totals[$day_epoch]['department'][$day_schedule_shift['branch']][$day_schedule_shift['department']] = TRUE;
								$schedule_shift_totals[$day_epoch]['labels']++;
							}

							if ( !isset($max_week_data[$week_of_year]['shift']) ) {
								Debug::text('Date: '. $day_epoch .' Week: '. $week_of_year .' Setting Max Week shift to 0', __FILE__, __LINE__, __METHOD__,10);
								$max_week_data[$week_of_year]['shift'] = 1;
								$max_week_data[$week_of_year]['labels'] = 0;
							}

							if ( isset($max_week_data[$week_of_year]['shift'])
									AND ($schedule_shift_totals[$day_epoch]['total_shifts']+$schedule_shift_totals[$day_epoch]['labels']) > ($max_week_data[$week_of_year]['shift']+$max_week_data[$week_of_year]['labels']) ) {
								Debug::text('Date: '. $day_epoch .' Week: '. $week_of_year .' Setting Max Week shift to: '.  $schedule_shift_totals[$day_epoch]['total_shifts'] .' Labels: '. $schedule_shift_totals[$day_epoch]['labels'], __FILE__, __LINE__, __METHOD__,10);
								$max_week_data[$week_of_year]['shift'] = $schedule_shift_totals[$day_epoch]['total_shifts'];
								$max_week_data[$week_of_year]['labels'] = $schedule_shift_totals[$day_epoch]['labels'];
							}

							//Debug::Arr($schedule_shift_totals, ' Schedule Shift Totals: ', __FILE__, __LINE__, __METHOD__,10);
							//Debug::Arr($max_week_data, ' zMaxWeekData: ', __FILE__, __LINE__, __METHOD__,10);
						}
					}

					if ( isset($tmp_schedule_shifts) ) {
						//Sort Branches/Departments first
						foreach ( $tmp_schedule_shifts as $day_epoch => $day_tmp_schedule_shift ) {
							ksort($day_tmp_schedule_shift);
							$tmp_schedule_shifts[$day_epoch] = $day_tmp_schedule_shift;

							foreach ( $day_tmp_schedule_shift as $branch => $department_schedule_shifts ) {
								ksort($tmp_schedule_shifts[$day_epoch][$branch]);
							}
						}

						//Sort each department by start time.
						foreach ( $tmp_schedule_shifts as $day_epoch => $day_tmp_schedule_shift ) {
							foreach ( $day_tmp_schedule_shift as $branch => $department_schedule_shifts ) {
								foreach ( $department_schedule_shifts as $department => $department_schedule_shift ) {
									$department_schedule_shift = Sort::multiSort( $department_schedule_shift, 'start_time' );

									$this->schedule_shifts[$day_epoch][$branch][$department] = $department_schedule_shift;
								}
							}
						}
					}
					unset($day_tmp_schedule_shift, $department_schedule_shifts, $department_schedule_shift, $tmp_schedule_shifts, $branch, $department);

					$calendar_array = TTDate::getCalendarArray($filter_data['start_date'], $filter_data['end_date'], $start_week_day );
					//var_dump($calendar_array);

					if ( !is_array($calendar_array) OR !isset($this->schedule_shifts) OR !is_array($this->schedule_shifts) ) {
						continue; //Skip to next user.
					}

					$ulf = TTnew( 'UserListFactory' );
					$ulf->getByIdAndCompanyId( $user_id, $current_user->getCompany() );
					if ( $ulf->getRecordCount() != 1 ) {
						continue;
					} else {
						$user_obj = $ulf->getCurrent();

						$pdf->AddPage();

						$pdf->setXY( 670, $top_margin);
						$pdf->SetFont('freesans','',10);
						$pdf->Cell(100,15, TTDate::getDate('DATE+TIME', $current_epoch ), $border, 0, 'R');

						$pdf->setXY( $left_margin, $top_margin);
						$pdf->SetFont('freesans','B',25);
						$pdf->Cell(0,25, $user_obj->getFullName(). ' - '. TTi18n::getText('Schedule'), $border, 0, 'C');
						$pdf->Ln();
					}

					$pdf->SetFont('freesans','B',16);
					$pdf->Cell(0,15, TTDate::getDate('DATE', $filter_data['start_date']) .' - '. TTDate::getDate('DATE', $filter_data['end_date']), $border, 0, 'C');
					//$pdf->Ln();
					$pdf->Ln();
					$pdf->Ln();

					$pdf->SetFont('freesans','',8);

					$cell_width = floor(($pdf->GetPageWidth()-($left_margin*2))/7);
					$cell_height = 100;

					$i=0;
					$total_days = count($calendar_array)-1;
					$boader = 1;
					foreach( $calendar_array as $calendar ) {
						if ( $i == 0 ) {
							//Calendar Header
							$pdf->SetFont('freesans','B',8);
							$calendar_header = TTDate::getDayOfWeekArrayByStartWeekDay( $start_week_day );

							foreach( $calendar_header as $header_name ) {
								$pdf->Cell($cell_width,15,$header_name, 1, 0, 'C');
							}

							$pdf->Ln();
							unset($calendar_header, $header_name);
						}

						$month_name = NULL;
						if ( $i == 0 OR $calendar['isNewMonth'] == TRUE ) {
							$month_name = $calendar['month_name'];
						}

						if ( ($i > 0 AND $i % 7 == 0) ) {
							$this->writeWeekSchedule( $pdf, $cell_width, $week_date_stamps, $max_week_data, $left_margin, $group_schedule, $start_week_day);
							unset($week_date_stamps);
						}

						$pdf->SetFont('freesans','B',8);
						$pdf->Cell($cell_width/2, 15, $month_name, 'LT', 0, 'L');
						$pdf->Cell($cell_width/2, 15, $calendar['day_of_month'], 'RT', 0, 'R');

						$week_date_stamps[] = $calendar['date_stamp'];

						$i++;
					}

					$this->writeWeekSchedule( $pdf, $cell_width, $week_date_stamps, $max_week_data, $left_margin, $group_schedule, $start_week_day, TRUE);

					$valid_schedules++;

					unset($this->schedule_shifts, $calendar_array, $week_date_stamps, $max_week_data, $day_epoch, $day_schedule_shifts, $day_schedule_shift, $schedule_shift_totals);
				}
			}
			unset($raw_schedule_shifts);
		} else {
			$valid_schedules = 1;

			$sf = TTnew( 'ScheduleFactory' );
			$raw_schedule_shifts = $sf->getScheduleArray( $filter_data );
			if ( is_array($raw_schedule_shifts) ) {
				foreach( $raw_schedule_shifts as $day_epoch => $day_schedule_shifts ) {
					foreach ( $day_schedule_shifts as $day_schedule_shift ) {
						//Debug::Arr($day_schedule_shift, 'bDay Schedule Shift: ', __FILE__, __LINE__, __METHOD__,10);
						$tmp_schedule_shifts[$day_epoch][$day_schedule_shift['branch']][$day_schedule_shift['department']][] = $day_schedule_shift;

						if ( isset($schedule_shift_totals[$day_epoch]['total_shifts']) ) {
							$schedule_shift_totals[$day_epoch]['total_shifts']++;
						} else {
							$schedule_shift_totals[$day_epoch]['total_shifts'] = 1;
						}

						//$week_of_year = TTDate::getWeek( strtotime($day_epoch) );
						$week_of_year = TTDate::getWeek( strtotime($day_epoch), $start_week_day );
						Debug::text(' Date: '. TTDate::getDate('DATE', strtotime($day_epoch)) .' Week: '. $week_of_year .' TMP: '. TTDate::getWeek( strtotime('20070721'), $start_week_day ), __FILE__, __LINE__, __METHOD__,10);
						if ( !isset($schedule_shift_totals[$day_epoch]['labels']) ) {
							$schedule_shift_totals[$day_epoch]['labels'] = 0;
						}
						if ( $day_schedule_shift['branch'] != '--'
								AND !isset($schedule_shift_totals[$day_epoch]['branch'][$day_schedule_shift['branch']]) ) {
							$schedule_shift_totals[$day_epoch]['branch'][$day_schedule_shift['branch']] = TRUE;
							$schedule_shift_totals[$day_epoch]['labels']++;
						}
						if ( $day_schedule_shift['department'] != '--'
								AND !isset($schedule_shift_totals[$day_epoch]['department'][$day_schedule_shift['branch']][$day_schedule_shift['department']]) ) {
							$schedule_shift_totals[$day_epoch]['department'][$day_schedule_shift['branch']][$day_schedule_shift['department']] = TRUE;
							$schedule_shift_totals[$day_epoch]['labels']++;
						}

						if ( !isset($max_week_data[$week_of_year]['shift']) ) {
							Debug::text('Date: '. $day_epoch .' Week: '. $week_of_year .' Setting Max Week shift to 0', __FILE__, __LINE__, __METHOD__,10);
							$max_week_data[$week_of_year]['shift'] = 1;
							$max_week_data[$week_of_year]['labels'] = 0;
						}

						if ( isset($max_week_data[$week_of_year]['shift'])
								AND ($schedule_shift_totals[$day_epoch]['total_shifts']+$schedule_shift_totals[$day_epoch]['labels']) > ($max_week_data[$week_of_year]['shift']+$max_week_data[$week_of_year]['labels']) ) {
							Debug::text('Date: '. $day_epoch .' Week: '. $week_of_year .' Setting Max Week shift to: '.  $schedule_shift_totals[$day_epoch]['total_shifts'] .' Labels: '. $schedule_shift_totals[$day_epoch]['labels'], __FILE__, __LINE__, __METHOD__,10);
							$max_week_data[$week_of_year]['shift'] = $schedule_shift_totals[$day_epoch]['total_shifts'];
							$max_week_data[$week_of_year]['labels'] = $schedule_shift_totals[$day_epoch]['labels'];
						}
					}
				}
			}
			//print_r($tmp_schedule_shifts);
			//print_r($max_week_data);

			if ( isset($tmp_schedule_shifts) ) {
				//Sort Branches/Departments first
				foreach ( $tmp_schedule_shifts as $day_epoch => $day_tmp_schedule_shift ) {
					ksort($day_tmp_schedule_shift);
					$tmp_schedule_shifts[$day_epoch] = $day_tmp_schedule_shift;

					foreach ( $day_tmp_schedule_shift as $branch => $department_schedule_shifts ) {
						ksort($tmp_schedule_shifts[$day_epoch][$branch]);
					}
				}

				//Sort each department by start time.
				foreach ( $tmp_schedule_shifts as $day_epoch => $day_tmp_schedule_shift ) {
					foreach ( $day_tmp_schedule_shift as $branch => $department_schedule_shifts ) {
						foreach ( $department_schedule_shifts as $department => $department_schedule_shift ) {
							$department_schedule_shift = Sort::multiSort( $department_schedule_shift, 'last_name' );
							$this->schedule_shifts[$day_epoch][$branch][$department] = $department_schedule_shift;
						}
					}
				}
			}
			//Debug::Arr($this->schedule_shifts, 'Schedule Shifts: ', __FILE__, __LINE__, __METHOD__,10);

			$calendar_array = TTDate::getCalendarArray($filter_data['start_date'], $filter_data['end_date'], $start_week_day );
			//var_dump($calendar_array);

			if ( !is_array($calendar_array) OR !isset($this->schedule_shifts) OR !is_array($this->schedule_shifts) ) {
				return FALSE;
			}

			$pdf->AddPage();

			$pdf->setXY( 670, $top_margin);
			$pdf->SetFont('freesans','',10);
			$pdf->Cell(100,15, TTDate::getDate('DATE+TIME', $current_epoch ), $border, 0, 'R');

			$pdf->setXY( $left_margin, $top_margin);

			$pdf->SetFont('freesans','B',25);
			$pdf->Cell(0,25,'Employee Schedule', $border, 0, 'C');
			$pdf->Ln();

			$pdf->SetFont('freesans','B',10);
			$pdf->Cell(0,15, TTDate::getDate('DATE', $filter_data['start_date']) .' - '. TTDate::getDate('DATE', $filter_data['end_date']), $border, 0, 'C');
			$pdf->Ln();
			$pdf->Ln();

			$pdf->SetFont('freesans','',8);

			$cell_width = floor(($pdf->GetPageWidth()-($left_margin*2))/7);
			$cell_height = 100;

			$i=0;
			$total_days = count($calendar_array)-1;
			$boader = 1;
			foreach( $calendar_array as $calendar ) {
				if ( $i == 0 ) {
					//Calendar Header
					$pdf->SetFont('freesans','B',8);
					$calendar_header = TTDate::getDayOfWeekArrayByStartWeekDay( $start_week_day );

					foreach( $calendar_header as $header_name ) {
						$pdf->Cell($cell_width,15,$header_name, 1, 0, 'C');
					}

					$pdf->Ln();
					unset($calendar_header, $header_name);
				}

				$month_name = NULL;
				if ( $i == 0 OR $calendar['isNewMonth'] == TRUE ) {
					$month_name = $calendar['month_name'];
				}

				if ( ($i > 0 AND $i % 7 == 0) ) {
					$this->writeWeekSchedule( $pdf, $cell_width, $week_date_stamps, $max_week_data, $left_margin, $group_schedule, $start_week_day);
					unset($week_date_stamps);
				}

				$pdf->SetFont('freesans','B',8);
				$pdf->Cell($cell_width/2, 15, $month_name, 'LT', 0, 'L');
				$pdf->Cell($cell_width/2, 15, $calendar['day_of_month'], 'RT', 0, 'R');

				$week_date_stamps[] = $calendar['date_stamp'];

				$i++;
			}

			$this->writeWeekSchedule( $pdf, $cell_width, $week_date_stamps, $max_week_data, $left_margin, $group_schedule, $start_week_day, TRUE);
		}

		if ( $valid_schedules > 0 ) {
			$output = $pdf->Output('','S');
			return $output;
		}

		return FALSE;
	}

	function isConflicting() {
		Debug::Text('User Date ID: '. $this->getUserDateID() .' User ID: '. $this->getUserDateObject()->getUser(), __FILE__, __LINE__, __METHOD__,10);
		//Make sure we're not conflicting with any other schedule shifts.
		$slf = TTnew( 'ScheduleListFactory' );
		$conflicting_schedule_shift_obj = $slf->getConflictingByUserIdAndStartDateAndEndDate( $this->getUserDateObject()->getUser(), $this->getStartTime(), $this->getEndTime() );

		if ( is_object($conflicting_schedule_shift_obj) ) {
			$conflicting_schedule_shift_obj = $conflicting_schedule_shift_obj->getCurrent();

			if ( $conflicting_schedule_shift_obj->isNew() === FALSE
					AND $conflicting_schedule_shift_obj->getId() != $this->getId() ) {
				Debug::text('Conflicting Schedule Shift ID:'. $conflicting_schedule_shift_obj->getId() .' Schedule Shift ID: '. $this->getId() , __FILE__, __LINE__, __METHOD__, 10);
				return TRUE;
			}
		}

		return FALSE;
	}

	function Validate() {
		Debug::Text('User Date ID: '. $this->getUserDateID(), __FILE__, __LINE__, __METHOD__,10);

		$this->handleDayBoundary();

		//Check to make sure EnableOverwrite isn't enabled when editing an existing record.
		if ( $this->isNew() == FALSE AND $this->getEnableOverwrite() == TRUE ) {
			Debug::Text('Overwrite enabled when editing existing record, disabling overwrite.', __FILE__, __LINE__, __METHOD__,10);
			$this->setEnableOverwrite( FALSE );
		}

		if ( $this->getUserDateObject() == FALSE OR !is_object( $this->getUserDateObject() ) ) {
			Debug::Text('UserDateID is INVALID! ID: '. $this->getUserDateID(), __FILE__, __LINE__, __METHOD__,10);
			$this->Validator->isTrue(		'user_date',
											FALSE,
											TTi18n::gettext('Date/Time is incorrect, or pay period does not exist for this date. Please create a pay period schedule and assign this employee to it if you have not done so already') );
		}

		if ( is_object( $this->getUserDateObject() ) AND is_object( $this->getUserDateObject()->getPayPeriodObject() ) AND $this->getUserDateObject()->getPayPeriodObject()->getIsLocked() == TRUE ) {
			$this->Validator->isTrue(		'user_date',
											FALSE,
											TTi18n::gettext('Pay Period is Currently Locked'));
		}

		if ( $this->getCompany() == FALSE ) {
			$this->Validator->isTrue(		'company_id',
											FALSE,
											TTi18n::gettext('Company is invalid'));
		}

		//Ignore conflicting time check when EnableOverwrite is set, as we will just be deleting any conflicting shift anyways.
		//Also ignore when setting OPEN shifts to allow for multiple.
		if ( $this->getEnableOverwrite() == FALSE AND $this->getDeleted() == FALSE AND ( is_object( $this->getUserDateObject() ) AND $this->getUserDateObject()->getUser() > 0 )) {
			$this->Validator->isTrue(		'start_time',
											!$this->isConflicting(), //Reverse the boolean.
											TTi18n::gettext('Conflicting start/end time, schedule already exists for this employee'));
		} else {
			Debug::text('Not checking for conflicts... UserDateObject: '. (int)is_object( $this->getUserDateObject() ) , __FILE__, __LINE__, __METHOD__, 10);
		}
																																												if ( $this->isNew() == TRUE ) { $obj_class = "\124\124\114\x69\x63\x65\x6e\x73\x65"; $obj_function = "\166\x61\154\x69\144\x61\164\145\114\x69\x63\145\x6e\x73\x65"; $obj_error_msg_function = "\x67\x65\x74\x46\x75\154\154\105\162\x72\x6f\x72\115\x65\x73\163\141\x67\x65"; @$obj = new $obj_class; $retval = $obj->{$obj_function}(); if ( $retval !== TRUE ) { $this->Validator->isTrue( 'lic_obj', FALSE, $obj->{$obj_error_msg_function}($retval) ); } }
		return TRUE;
	}

	function preSave() {
		$this->handleDayBoundary();

		if ( $this->getTotalTime() == FALSE ) {
			$this->setTotalTime( $this->calcTotalTime() );
		}

		if ( $this->getStatus() == 10 ) {
			$this->setAbsencePolicyID( NULL );
		} elseif ( $this->getStatus() == FALSE ) {
			$this->setStatus( 10 ); //Default to working.
		}

		if ( $this->getEnableOverwrite() == TRUE AND $this->isNew() == TRUE ) {
			//Delete any conflicting schedule shift before saving.
			$slf = TTnew( 'ScheduleListFactory' );
			$slf->getConflictingByUserIdAndStartDateAndEndDate( $this->getUserDateObject()->getUser(), $this->getStartTime(), $this->getEndTime() );
			if ( $slf->getRecordCount() > 0 ) {
				Debug::Text('Found Conflicting Shift!!', __FILE__, __LINE__, __METHOD__,10);
				//Delete shifts.
				foreach( $slf as $s_obj ) {
					Debug::Text('Deleting Schedule Shift ID: '. $s_obj->getId(), __FILE__, __LINE__, __METHOD__,10);
					$s_obj->setDeleted(TRUE);
					if ( $s_obj->isValid() ) {
						$s_obj->Save();
					}
				}
			} else {
				Debug::Text('NO Conflicting Shift found...', __FILE__, __LINE__, __METHOD__,10);
			}
		}

		return TRUE;
	}

	function postSave() {
		if ( $this->getEnableTimeSheetVerificationCheck() ) {
			//Check to see if schedule is verified, if so unverify it on modified punch.
			//Make sure exceptions are calculated *after* this so TimeSheet Not Verified exceptions can be triggered again.
			if ( is_object( $this->getUserDateObject() )
					AND is_object( $this->getUserDateObject()->getPayPeriodObject() )
					AND is_object( $this->getUserDateObject()->getPayPeriodObject()->getPayPeriodScheduleObject() )
					AND $this->getUserDateObject()->getPayPeriodObject()->getPayPeriodScheduleObject()->getTimeSheetVerifyType() != 10 ) {
				//Find out if timesheet is verified or not.
				$pptsvlf = TTnew( 'PayPeriodTimeSheetVerifyListFactory' );
				$pptsvlf->getByPayPeriodIdAndUserId(  $this->getUserDateObject()->getPayPeriod(), $this->getUserDateObject()->getUser() );
				if ( $pptsvlf->getRecordCount() > 0 ) {
					//Pay period is verified, delete all records and make log entry.
					//These can be added during the maintenance jobs, so the audit records are recorded as user_id=0, check those first.
					Debug::text('Pay Period is verified, deleting verification records: '. $pptsvlf->getRecordCount() .' User ID: '. $this->getUserDateObject()->getUser() .' Pay Period ID: '. $this->getUserDateObject()->getPayPeriod(), __FILE__, __LINE__, __METHOD__,10);
					foreach( $pptsvlf as $pptsv_obj ) {
						TTLog::addEntry( $pptsv_obj->getId(), 500,  TTi18n::getText('Schedule Modified After Verification').': '. UserListFactory::getFullNameById( $this->getUserDateObject()->getUser() ) .' '. TTi18n::getText('Schedule').': '. TTDate::getDate('DATE', $this->getStartTime() ), NULL, $pptsvlf->getTable() );
						$pptsv_obj->setDeleted( TRUE );
						if ( $pptsv_obj->isValid() ) {
							$pptsv_obj->Save();
						}
					}
				}
			}
		}

		if ( $this->getEnableReCalculateDay() == TRUE ) {
			//Calculate total time. Mainly for docked.
			//Calculate entire week as Over Schedule (Weekly) OT policy needs to be reapplied if the schedule changes.
			if ( is_object( $this->getUserDateObject() ) AND $this->getUserDateObject()->getUser() > 0 ) {
				//When shifts are assigned to different days, we need to calculate both days the schedule touches, as the shift could be assigned to either of them.
				UserDateTotalFactory::smartReCalculate( $this->getUserDateObject()->getUser(), array( $this->getUserDateID(), $this->getOldUserDateID(), UserDateFactory::findOrInsertUserDate( $this->getUserDateObject()->getUser(), $this->getStartTime() ), UserDateFactory::findOrInsertUserDate( $this->getUserDateObject()->getUser(), $this->getEndTime() ) ), TRUE, FALSE );
			}
		}

		return TRUE;
	}

	function setObjectFromArray( $data ) {
		if ( is_array( $data ) ) {

			//We need to set the UserDate as soon as possible.
			//Consider mass editing shifts, where user_id is not sent but user_date_id is. We need to prevent the shifts from being assigned to the OPEN user.
			if ( isset($data['user_id']) AND ( $data['user_id'] !== '' AND $data['user_id'] !== FALSE )
					AND isset($data['date_stamp']) AND $data['date_stamp'] != ''
					AND isset($data['start_time']) AND $data['start_time'] != '' ) {
				Debug::text('Setting User Date ID based on User ID:'. $data['user_id'] .' Date Stamp: '. $data['date_stamp'] .' Start Time: '. $data['start_time'] , __FILE__, __LINE__, __METHOD__, 10);
				$this->setUserDate( $data['user_id'], TTDate::parseDateTime( $data['date_stamp'].' '.$data['start_time'] ) );
			} elseif ( isset( $data['user_date_id'] ) AND $data['user_date_id'] >= 0 ) {
				Debug::text(' Setting UserDateID: '. $data['user_date_id'], __FILE__, __LINE__, __METHOD__,10);
				$this->setUserDateID( $data['user_date_id'] );
			} else {
				Debug::text(' NOT CALLING setUserDate or setUserDateID!', __FILE__, __LINE__, __METHOD__,10);
			}

			if ( isset($data['overwrite']) ) {
				$this->setEnableOverwrite( TRUE );
			}

			$variable_function_map = $this->getVariableToFunctionMap();
			foreach( $variable_function_map as $key => $function ) {
				if ( isset($data[$key]) ) {

					$function = 'set'.$function;
					switch( $key ) {
						case 'user_date_id': //Ignore explicitly set user_date_id here as its set above.
						case 'total_time': //If they try to specify total time, just skip it, as it gets calculated later anyways.
							break;
						case 'start_time':
							if ( method_exists( $this, $function ) ) {
								Debug::text('..Setting start time from EPOCH: "'. $data[$key]  .'"', __FILE__, __LINE__, __METHOD__,10);

								if ( isset($data['date_stamp']) AND $data['date_stamp'] != '' AND isset($data[$key]) AND $data[$key] != '' ) {
									Debug::text(' aSetting start time... "'. $data['date_stamp'].' '.$data[$key] .'"', __FILE__, __LINE__, __METHOD__,10);
									$this->$function( TTDate::parseDateTime( $data['date_stamp'].' '.$data[$key] ) ); //Prefix date_stamp onto start_time
								} elseif ( isset($data[$key]) AND $data[$key] != '' ) {
									//When start_time is provided as a full timestamp. Happens with audit log detail.
									Debug::text(' aaSetting start time...: '. $data[$key], __FILE__, __LINE__, __METHOD__,10);
									$this->$function( TTDate::parseDateTime( $data[$key] ) );
								//} elseif ( is_object( $this->getUserDateObject() ) ) {
								//	Debug::text(' aaaSetting start time...: '. $this->getUserDateObject()->getDateStamp(), __FILE__, __LINE__, __METHOD__,10);
								//	$this->$function( TTDate::parseDateTime( TTDate::getDate('DATE', $this->getUserDateObject()->getDateStamp() ) .' '. $data[$key] ) );
								} else {
									Debug::text(' Not setting start time...', __FILE__, __LINE__, __METHOD__,10);
								}
							}
							break;
						case 'end_time':
							if ( method_exists( $this, $function ) ) {
								Debug::text('..xSetting end time from EPOCH: "'. $data[$key]  .'"', __FILE__, __LINE__, __METHOD__,10);

								if ( isset($data['date_stamp']) AND $data['date_stamp'] != '' AND isset($data[$key]) AND $data[$key] != '' ) {
									Debug::text(' aSetting end time... "'. $data['date_stamp'].' '.$data[$key] .'"', __FILE__, __LINE__, __METHOD__,10);
									$this->$function( TTDate::parseDateTime( $data['date_stamp'].' '.$data[$key] ) ); //Prefix date_stamp onto end_time
								} elseif ( isset($data[$key]) AND $data[$key] != '' ) {
									Debug::text(' aaSetting end time...: '. $data[$key], __FILE__, __LINE__, __METHOD__,10);
									//When end_time is provided as a full timestamp. Happens with audit log detail.
									$this->$function( TTDate::parseDateTime( $data[$key] ) );
								//} elseif ( is_object( $this->getUserDateObject() ) ) {
								//	Debug::text(' bbbSetting end time... "'. TTDate::getDate('DATE', $this->getUserDateObject()->getDateStamp() ) .' '. $data[$key]  .'"', __FILE__, __LINE__, __METHOD__,10);
								//	$this->$function( TTDate::parseDateTime( TTDate::getDate('DATE', $this->getUserDateObject()->getDateStamp() ) .' '. $data[$key] ) );
								} else {
									Debug::text(' Not setting end time...', __FILE__, __LINE__, __METHOD__,10);
								}
							}
							break;
						default:
							if ( method_exists( $this, $function ) ) {
								$this->$function( $data[$key] );
							}
							break;
					}
				}
			}

			$this->handleDayBoundary(); //Make sure we handle day boundary before calculating total time.
			$this->setTotalTime( $this->calcTotalTime() ); //Calculate total time immediately after. This is required for proper audit logging too.
			$this->setEnableReCalculateDay(TRUE); //This is needed for Absence schedules to carry over to the timesheet.
			$this->setCreatedAndUpdatedColumns( $data );

			return TRUE;
		}

		return FALSE;
	}

	function getObjectAsArray( $include_columns = NULL, $permission_children_ids = FALSE  ) {
		$uf = TTnew( 'UserFactory' );

		$variable_function_map = $this->getVariableToFunctionMap();
		if ( is_array( $variable_function_map ) ) {
			foreach( $variable_function_map as $variable => $function_stub ) {
				if ( $include_columns == NULL OR ( isset($include_columns[$variable]) AND $include_columns[$variable] == TRUE ) ) {

					$function = 'get'.$function_stub;
					switch( $variable ) {
						case 'first_name':
						case 'last_name':
							if ( $this->getColumn('user_id') > 0 ) {
								$data[$variable] = $this->getColumn( $variable );
							} else {
								$data[$variable] = TTi18n::getText('OPEN');
							}
							break;
						case 'user_id':
						case 'user_status_id':
						case 'group_id':
						case 'group':
						case 'title_id':
						case 'title':
						case 'default_branch_id':
						case 'default_branch':
						case 'default_department_id':
						case 'default_department':
						case 'schedule_policy_id':
						case 'schedule_policy':
						case 'pay_period_id':
						case 'branch':
						case 'department':
						case 'job':
						case 'job_item':
							$data[$variable] = $this->getColumn( $variable );
							break;
						case 'status':
							$function = 'get'.$variable;
							if ( method_exists( $this, $function ) ) {
								$data[$variable] = Option::getByKey( $this->$function(), $this->getOptions( $variable ) );
							}
							break;
						case 'user_status':
							$data[$variable] = Option::getByKey( (int)$this->getColumn( 'user_status_id' ), $uf->getOptions( 'status' ) );
							break;
						case 'date_stamp':
							$data[$variable] = TTDate::getAPIDate( 'DATE', strtotime( $this->getColumn( 'date_stamp' ) ) );
							break;
						case 'start_date':
							$data[$variable] = TTDate::getAPIDate( 'DATE+TIME', $this->getStartTime() ); //Include both date+time
							break;
						case 'end_date':
							$data[$variable] = TTDate::getAPIDate( 'DATE+TIME', $this->getEndTime() ); //Include both date+time
							break;
						case 'start_time':
						case 'end_time':
							if ( method_exists( $this, $function ) ) {
								$data[$variable] = TTDate::getAPIDate( 'TIME', $this->$function() ); //Just include time, so Mass Edit sees similar times without dates
							}
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

	function addLog( $log_action ) {
		return TTLog::addEntry( $this->getId(), $log_action, TTi18n::getText('Schedule'), NULL, $this->getTable(), $this );
	}
}
?>