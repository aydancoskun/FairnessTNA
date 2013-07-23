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
 * $Revision: 9521 $
 * $Id: ROEFactory.class.php 9521 2013-04-08 23:09:52Z ipso $
 * $Date: 2013-04-08 16:09:52 -0700 (Mon, 08 Apr 2013) $
 */

/**
 * @package Modules\Other
 */
class ROEFactory extends Factory {
	protected $table = 'roe';
	protected $pk_sequence_name = 'roe_id_seq'; //PK Sequence name

	var $user_obj = NULL;
	var $pay_stub_entry_account_link_obj = NULL;
	var $pay_period_earnings = NULL;

	function _getFactoryOptions( $name ) {

		$retval = NULL;
		switch( $name ) {
			case 'code':
				$retval = array(
											'A' 	=> TTi18n::gettext('(A) Shortage of Work'),
											'B' 	=> TTi18n::gettext('(B) Strike Or Lockout'),
											'C' 	=> TTi18n::gettext('(C) Return to School'),
											'D' 	=> TTi18n::gettext('(D) Illness or Injury'),
											'E' 	=> TTi18n::gettext('(E) Quit'),
											'F' 	=> TTi18n::gettext('(F) Maternity'),
											'G' 	=> TTi18n::gettext('(G) Retirement'),
											'H' 	=> TTi18n::gettext('(H) Work Sharing'),
											'J' 	=> TTi18n::gettext('(J) Apprentice Training'),
											'M' 	=> TTi18n::gettext('(M) Dismissal'),
											'N' 	=> TTi18n::gettext('(N) Leave of Absence'),
											'P' 	=> TTi18n::gettext('(P) Parental'),
											'K' 	=> TTi18n::gettext('(K) Other')
									);
				break;
            case 'columns':
                $retval = array(
                                            '-1010-first_name' => TTi18n::gettext('First Name'),
                                            '-1020-last_name' => TTi18n::gettext('Last Name'),
                                            '-1025-insurable_absence_policies' => TTi18n::gettext('Insurable Absence Policies'),
                                            '-1030-insurable_earnings' => TTi18n::gettext('Insurable Earnings (Box 15B)'),
                                            '-1040-vacation_pay' => TTi18n::gettext('Vacation Pay (Box 17A)'),
                                            '-1050-code' => TTi18n::gettext('Reason'),
                                            '-1060-pay_period_type' => TTi18n::gettext('Pay Period Type'),
                                            '-1070-first_date' => TTi18n::gettext('First Day Worked'),
                                            '-1080-last_date' => TTi18n::gettext('Last Day For Which Paid'),
                                            '-1100-pay_period_end_date' => TTi18n::gettext('Final Pay Period Ending Date'),
                                            '-1120-recall_date' => TTi18n::gettext('Expected Date of Recall'),
                                            '-1150-serial' => TTi18n::gettext('Serial No'),
                                            '-1170-comments' => TTi18n::gettext('Comments'),
                                            '-1200-release_accruals' => TTi18n::gettext('Release All Accruals'),
                                            '-1220-generate_pay_stub' => TTi18n::gettext('Generate Final Pay Stub'),
                                            '-1230-insurable_hours' => TTi18n::gettext('Insurable Hours'),

                                            '-2000-created_by' => TTi18n::gettext('Created By'),
    										'-2010-created_date' => TTi18n::gettext('Created Date'),
    										'-2020-updated_by' => TTi18n::gettext('Updated By'),
    										'-2030-updated_date' => TTi18n::gettext('Updated Date'),
                );
                break;
            case 'list_columns':
				$retval = Misc::arrayIntersectByKey( $this->getOptions('default_display_columns'), Misc::trimSortPrefix( $this->getOptions('columns') ) );
				break;
			case 'default_display_columns': //Columns that are displayed by default.
				$retval = array(
								'first_name',
								'last_name',
								'first_date',
								'last_date',
								'code',
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

		}

		return $retval;
	}

    function _getVariableToFunctionMap( $data ) {
		$variable_function_map = array(
										'id' => 'ID',
										'user_id' => 'User',
                                        'first_name' => FALSE,
                                        'last_name' => FALSE,
										'pay_period_type_id' => 'PayPeriodType',
										'pay_period_type' => FALSE,
                                        'code_id' => 'Code',
                                        'code' => FALSE,
                                        'first_date' => 'FirstDate',
                                        'last_date' => 'LastDate',
                                        'pay_period_end_date' => 'PayPeriodEndDate',
                                        'recall_date' => 'RecallDate',
                                        'insurable_hours' => 'InsurableHours',
                                        'insurable_earnings' => 'InsurableEarnings',
                                        'vacation_pay' => FALSE,
                                        'serial' => 'Serial',
                                        'comments' => 'Comments',

                                        'release_accruals' => FALSE,
                                        'generate_pay_stub' => FALSE,

										'deleted' => 'Deleted',
										);
		return $variable_function_map;
	}

	function getUserObject() {
		if ( is_object($this->user_obj) ) {
			return $this->user_obj;
		} else {
			$ulf = TTnew( 'UserListFactory' );
			$this->user_obj = $ulf->getById( $this->getUser() )->getCurrent();

			return $this->user_obj;
		}
	}

	function getPayStubEntryAccountLinkObject() {
		if ( is_object($this->pay_stub_entry_account_link_obj) ) {
			return $this->pay_stub_entry_account_link_obj;
		} else {
			$pseallf = TTnew( 'PayStubEntryAccountLinkListFactory' );
			$pseallf->getByCompanyID( $this->getUserObject()->getCompany() );
			if ( $pseallf->getRecordCount() > 0 ) {
				$this->pay_stub_entry_account_link_obj = $pseallf->getCurrent();
				return $this->pay_stub_entry_account_link_obj;
			}

			return FALSE;
		}
	}

	function getUser() {
		if ( isset($this->data['user_id']) ) {
			return $this->data['user_id'];
		}

		return FALSE;
	}
	function setUser($id) {
		$id = trim($id);

		$ulf = TTnew( 'UserListFactory' );

		if ( $this->Validator->isResultSetWithRows(	'user',
															$ulf->getByID($id),
															TTi18n::gettext('Invalid User')
															) ) {
			$this->data['user_id'] = $id;

			return TRUE;
		}

		return FALSE;
	}

	function getPayPeriodType() {
		if ( isset($this->data['pay_period_type_id']) ) {
			return $this->data['pay_period_type_id'];
		}

		return FALSE;
	}
	function setPayPeriodType($value) {
		$value = trim($value);

		Debug::Text('Type ID: '. $value, __FILE__, __LINE__, __METHOD__,10);

		$ppsf = TTnew( 'PayPeriodScheduleFactory' );

		$key = Option::getByValue($value, $ppsf->getOptions('type') );
		if ($key !== FALSE) {
			$value = $key;
		}

		if ( $this->Validator->inArrayKey(	'pay_period_type_id',
											$value,
											TTi18n::gettext('Incorrect pay period type'),
											$ppsf->getOptions('type')) ) {

			$this->data['pay_period_type_id'] = $value;

			return FALSE;
		}

		return FALSE;
	}

	function getCode() {
		if ( isset($this->data['code_id']) ) {
			return $this->data['code_id'];
		}

		return FALSE;
	}
	function setCode($value) {
		$value = trim($value);

		$key = Option::getByValue($value, $this->getOptions('code') );
		if ($key !== FALSE) {
			$value = $key;
		}

		if ( $this->Validator->inArrayKey(	'code_id',
											$value,
											TTi18n::gettext('Incorrect code'),
											$this->getOptions('code')) ) {

			$this->data['code_id'] = $value;

			return FALSE;
		}

		return FALSE;
	}

	function getFirstDate() {
		if ( isset($this->data['first_date']) ) {
			return $this->data['first_date'];
		}

		return FALSE;
	}
	function setFirstDate($epoch) {
		$epoch = trim($epoch);

		if 	(	$this->Validator->isDate(		'first_date',
												$epoch,
												TTi18n::gettext('Invalid first date')) ) {

			$this->data['first_date'] = $epoch;

			return TRUE;
		}

		return FALSE;

	}

	function getLastDate() {
		if ( isset($this->data['last_date']) ) {
			return $this->data['last_date'];
		}

		return FALSE;
	}
	function setLastDate($epoch) {
		$epoch = trim($epoch);

		//Include the entire day.
		//$epoch = TTDate::getBeginDayEpoch( $epoch ) + (86400-120);
		$epoch = TTDate::getEndDayEpoch( $epoch );

		if 	(	$this->Validator->isDate(		'last_date',
												$epoch,
												TTi18n::gettext('Invalid last date')) ) {

			$this->data['last_date'] = $epoch;

			return TRUE;
		}

		return FALSE;

	}

	function getPayPeriodEndDate() {
		if ( isset($this->data['pay_period_end_date']) ) {
			return $this->data['pay_period_end_date'];
		}

		return FALSE;
	}
	function setPayPeriodEndDate($epoch) {
		$epoch = trim($epoch);

		if 	(	$this->Validator->isDate(		'pay_period_end_date',
												$epoch,
												TTi18n::gettext('Invalid final pay period end date')) ) {

			$this->data['pay_period_end_date'] = $epoch;

			return TRUE;
		}

		return FALSE;
	}

	function getRecallDate() {
		if ( isset($this->data['recall_date']) ) {
			return $this->data['recall_date'];
		}

		return FALSE;
	}
	function setRecallDate($epoch) {
		$epoch = trim($epoch);
        if ( $epoch == '' ) {
            $epoch = NULL;
        }
		if 	( $epoch == NULL
                OR
 	          $this->Validator->isDate(		'recall_date',
												$epoch,
												TTi18n::gettext('Invalid recall date')) ) {

			$this->data['recall_date'] = $epoch;

			return TRUE;
		}

		return FALSE;

	}

	function getInsurableHours() {
		if ( isset($this->data['insurable_hours']) ) {
			return $this->data['insurable_hours'];
		}

		return FALSE;
	}
	function setInsurableHours($value) {
		$value = trim($value);
        if ( $value == '' OR $value == NULL ) {
            $value = 0;
        }
		if 	(  $value == 0
                OR
            	$this->Validator->isFloat(		'insurable_hours',
												$value,
												TTi18n::gettext('Invalid insurable hours')) ) {

			$this->data['insurable_hours'] = $value;

			return TRUE;
		}

		return FALSE;
	}

	function getInsurableEarnings() {
		if ( isset($this->data['insurable_earnings']) ) {
			return $this->data['insurable_earnings'];
		}

		return FALSE;
	}
	function setInsurableEarnings($value) {
		$value = trim($value);
        if ( $value == '' OR $value == NULL ) {
            $value = 0;
        }
		if 	( $value == 0
                OR
            	$this->Validator->isFloat(		'insurable_earnings',
												$value,
												TTi18n::gettext('Invalid insurable earnings')) ) {

			$this->data['insurable_earnings'] = $value;

			return TRUE;
		}

		return FALSE;
	}

	function getVacationPay() {
		if ( isset($this->data['vacation_pay']) ) {
			return $this->data['vacation_pay'];
		}

		return FALSE;
	}
	function setVacationPay($value) {
		$value = trim($value);

		if 	(	$this->Validator->isFloat(		'vacation_pay',
												$value,
												TTi18n::gettext('Invalid vacation pay')) ) {

			$this->data['vacation_pay'] = $value;

			return TRUE;
		}

		return FALSE;
	}

	function getSerial() {
		if ( isset($this->data['serial']) ) {
			return $this->data['serial'];
		}

		return FALSE;
	}
	function setSerial($value) {
		$value = trim($value);

		//Don't force serial numbers anymore, as online ROEs don't require them.
		if 	(	$value == ''
				OR
				$this->Validator->isLength(		'serial',
												$value,
												TTi18n::gettext('Serial number should be between 9 and 15 digits'),
												9,
												15) ) {

			$this->data['serial'] = $value;

			return TRUE;
		}

		return FALSE;
	}

	function getComments() {
		if ( isset($this->data['comments']) ) {
			return $this->data['comments'];
		}

		return FALSE;
	}
	function setComments($value) {
		$value = trim($value);

		if 	(	$this->Validator->isLength(		'comments',
												$value,
												TTi18n::gettext('Invalid comments'),
												0,
												1024) ) {

			$this->data['comments'] = $value;

			return TRUE;
		}

		return FALSE;
	}

	function getInsurableHoursReportPayPeriods() {
		$report_period_arr = array(
							10  => 53, //'Weekly',
							20 	=> 27, //'Bi-Weekly',
							30  => 25, //'Semi-Monthly',
							40	=> 13, //'Monthly + Advance',
							50  => 13, //'Monthly'
							);

		return $report_period_arr[$this->getPayPeriodType()];
	}

	function getInsurableEarningsReportPayPeriods() {
		//eFile requires more data than paper forms, but the paper form we use supports up to 53 boxes anyways, so just default to that.
		$report_period_arr = array(
							10  => 53, //'Weekly',
							20 	=> 27, //'Bi-Weekly',
							30  => 25, //'Semi-Monthly',
							40	=> 13, //'Monthly + Advance',
							50  => 13, //'Monthly'
							);
		/*
		$report_period_arr = array(
							10  => 27, //'Weekly',
							20 	=> 14, //'Bi-Weekly',
							30  => 13, //'Semi-Monthly',
							40	=> 7, //'Monthly + Advance',
							50  => 7, //'Monthly'
							);
		*/
		return $report_period_arr[$this->getPayPeriodType()];
	}

	function getInsurablePayPeriodStartDate( $pay_periods ) {
		Debug::Text('Pay Periods to Consider: '. $pay_periods, __FILE__, __LINE__, __METHOD__,10);
		Debug::Text('First Day Worked: '. TTDate::getDate('DATE+TIME', $this->getFirstDate() ) .' Last Worked Day: '. TTDate::getDate('DATE+TIME', $this->getLastDate() ), __FILE__, __LINE__, __METHOD__,10);

		$start_date = FALSE;

		$pplf = TTnew( 'PayPeriodListFactory' );
		$pay_period_obj = $pplf->getByUserIdAndEndDate( $this->getUser(), $this->getLastDate() )->getCurrent();
		Debug::Text('Pay Period ID: '. $pay_period_obj->getId(), __FILE__, __LINE__, __METHOD__,10);

		$pplf->getByPayPeriodScheduleId($pay_period_obj->getPayPeriodSchedule(), NULL, NULL, NULL, array('start_date' => 'desc') );
		$i=1;
		foreach($pplf as $pay_period) {
			//Make sure if there are more pay periods inserted AFTER the last day, we DO NOT include
			//those in the count.
			Debug::Text('Pay Period: Start Date: '. TTDate::getDate('DATE+TIME', $pay_period->getStartDate() ) .' End Date: '. TTDate::getDate('DATE+TIME', $pay_period->getEndDate() ), __FILE__, __LINE__, __METHOD__,10);

			if ( $this->getFirstDate() <= $pay_period->getEndDate() AND $this->getLastDate() >= $pay_period->getStartDate() ) {
				Debug::Text($i. '.  Including Pay Period...', __FILE__, __LINE__, __METHOD__,10);
				//If there aren't enough pay periods yet, use what we have...
				$start_date = $pay_period->getStartDate();

				if ( $i == $pay_periods ) {
					break;
				}

				$i++;
			}
		}

		Debug::Text('Pay Period Report Start Date: '. TTDate::getDate('DATE+TIME', $start_date), __FILE__, __LINE__, __METHOD__,10);

		return $start_date;
	}

	function getEnableReCalculate() {
		if ( isset($this->recalc) ) {
			return $this->recalc;
		}

		return FALSE;
	}
	function setEnableReCalculate($bool) {
		$this->recalc = $bool;

		return TRUE;
	}

	function getEnableReleaseAccruals() {
		if ( isset($this->release_accruals) ) {
			return $this->release_accruals;
		}

		return FALSE;
	}
	function setEnableReleaseAccruals($bool) {
		$this->release_accruals = $bool;

		return TRUE;
	}
	function getEnableGeneratePayStub() {
		if ( isset($this->generate_pay_stub) ) {
			return $this->generate_pay_stub;
		}

		return FALSE;
	}
	function setEnableGeneratePayStub($bool) {
		$this->generate_pay_stub = $bool;

		return TRUE;
	}

    function calculateFirstDate( $user_id ) {
        //get User data for hire date
		$ulf = TTnew( 'UserListFactory' );
		$user_obj = $ulf->getById($user_id)->getCurrent();

		$plf = TTnew( 'PunchListFactory' );

		//Is there a previous ROE? If so, find first shift back since ROE was issued.
		$rlf = TTnew( 'ROEListFactory' );
		$rlf->getLastROEByUserId( $user_id );
		if ( $rlf->getRecordCount() > 0 ) {
			$roe_obj = $rlf->getCurrent();

			Debug::Text('Previous ROE Last Date: '. TTDate::getDate('DATE+TIME', $roe_obj->getLastDate() ) , __FILE__, __LINE__, __METHOD__,10);
			//$plf->getFirstPunchByUserIDAndEpoch( $user_id, $roe_obj->getLastDate() );
			$plf->getNextPunchByUserIdAndEpoch( $user_id, $roe_obj->getLastDate() );
			if ( $plf->getRecordCount() > 0 ) {
				$first_date = $plf->getCurrent()->getTimeStamp();
			}
		}

		if ( !isset($first_date) OR $first_date == '' ) {
			$first_date = $user_obj->getHireDate();
		}
		Debug::Text('First Date: '. TTDate::getDate('DATE+TIME', $first_date) , __FILE__, __LINE__, __METHOD__,10);

        return $first_date;
    }

    function calculateLastDate( $user_id ) {
        $plf = TTnew( 'PunchListFactory' );
        $plf->getLastPunchByUserId( $user_id );
		if ( $plf->getRecordCount() > 0 ) {
			$punch_obj = $plf->getCurrent();
			$last_date = $punch_obj->getPunchControlObject()->getUserDateObject()->getDateStamp();
		} else {
			$last_date = TTDate::getTime();
		}

		Debug::Text('Last Punch Date: '. TTDate::getDate('DATE+TIME', $last_date) , __FILE__, __LINE__, __METHOD__,10);

        return $last_date;
    }

    function calculatePayPeriodType( $user_id, $date ) {
        $plf = TTnew( 'PayPeriodListFactory' );
		$pay_period_obj = $plf->getByUserIdAndEndDate( $user_id, $date )->getCurrent();

		$pay_period_type_id = FALSE;
		if ( is_object( $pay_period_obj->getPayPeriodScheduleObject() ) ) {
			$pay_period_type_id = $pay_period_obj->getPayPeriodScheduleObject()->getType();
		}

        $pay_period_end_date = $pay_period_obj->getEndDate();

        return array( 'pay_period_type_id' => $pay_period_type_id, 'pay_period_end_date' => $pay_period_end_date );
    }

	function getSetupData() {
		//FIXME: Alert the user if they don't have enough information in TimeTrex to get accurate values.
		//Get insurable hours, earnings, and vacation pay now that the final pay stub is generated
		$ugdlf = TTnew( 'UserGenericDataListFactory' );
		$ugdlf->getByCompanyIdAndScriptAndDefault( $this->getUserObject()->getCompany(), $this->getTable() );
		if ( $ugdlf->getRecordCount() > 0 ) {
			Debug::Text('Found Company Form Setup!', __FILE__, __LINE__, __METHOD__,10);
			$ugd_obj = $ugdlf->getCurrent();
			$setup_data = $ugd_obj->getData();
		}
		unset($ugd_obj);

		if ( isset($setup_data) ) {
			//var_dump($setup_data);
			if ( !isset($setup_data['insurable_earnings_psea_ids']) ) {
				$setup_data['insurable_earnings_psea_ids'] = $this->getPayStubEntryAccountLinkObject()->getTotalGross();
			}

			if ( !isset($setup_data['absence_policy_ids']) ) {
				$setup_data['absence_policy_ids'] = array();
			}

			return $setup_data;
		}

		return FALSE;
	}

	function getInsurableEarningsByPayPeriod() {
		if ( $this->pay_period_earnings !== NULL ) {
			return $this->pay_period_earnings;
		}

		$setup_data = $this->getSetupData();
		$insurable_earnings_start_date = $this->getInsurablePayPeriodStartDate( $this->getInsurableEarningsReportPayPeriods() );

		$pself = TTnew( 'PayStubEntryListFactory' );
		$pself->getPayPeriodReportByUserIdAndEntryNameIdAndStartDateAndEndDate( $this->getUser(), $setup_data['insurable_earnings_psea_ids'], $insurable_earnings_start_date, $this->getLastDate(), 0, NULL, array('x.start_date' => 'desc') );
		if ( $pself->getRecordCount() > 0 ) {
			foreach( $pself as $pse_obj ) {
				$retarr[$pse_obj->getColumn('pay_period_id')] = array(
																		//'pay_period_start_date' => $pse_obj->getColumn('pay_period_start_date'),
																		'amount' => $pse_obj->getColumn('amount'),
																		'units' => $pse_obj->getColumn('units'),
																	);
			}
		}

		if ( isset($retarr) ) {
			Debug::Arr($retarr, 'Pay Period Earnings: ', __FILE__, __LINE__, __METHOD__,10);
			$this->pay_period_earnings = $retarr;
			return $this->pay_period_earnings;
		}

		return FALSE;
	}

	function isPayPeriodWithNoEarnings() {
		//Show earnings per pay period always, as some provinces require it for certain purposes like EI to determine highest weekly earnings.
		return TRUE;
		/*
		$pp_earnings = $this->getInsurableEarningsByPayPeriod();
		if ( is_array($pp_earnings) ) {
			foreach( $pp_earnings as $pp_earning ) {
				if ( $pp_earning['amount'] <= 0 ) {
					return TRUE;
				}

			}
		}

		return FALSE;
		*/
	}

	function getTotalInsurableEarnings() {
		$total_earnings = 0;

		$pp_earnings = $this->getInsurableEarningsByPayPeriod();
		if ( is_array($pp_earnings) ) {
			foreach( $pp_earnings as $pp_earning ) {
				$total_earnings += $pp_earning['amount'];
			}
		}
		Debug::Text('Total Insurable Earnings: '. $total_earnings, __FILE__, __LINE__, __METHOD__,10);

		return $total_earnings;
	}

	function getLastPayPeriodVacationEarnings() {
		$setup_data = $this->getSetupData();

		//Get last pay period id
		$pay_period_earnings = $this->getInsurableEarningsByPayPeriod();
		if ( is_array( $pay_period_earnings ) ) {
			$last_pay_period_id = array_shift( array_keys( $pay_period_earnings ) );

			$pself = TTnew( 'PayStubEntryListFactory' );
			$retval = $pself->getAmountSumByUserIdAndEntryNameIdAndPayPeriodId( $this->getUser(), $setup_data['vacation_psea_ids'], (int)$last_pay_period_id);

			Debug::Text('Last Pay Period Vacation Pay: '. $retval['amount'] .' Last Pay Period ID: '. $last_pay_period_id, __FILE__, __LINE__, __METHOD__,10);
			return $retval['amount'];
		}

		return FALSE;
	}

	function reCalculate() {
		//Re-generate final pay stub
		//get current pay period based off their last day of work
		$pplf = TTnew( 'PayPeriodListFactory' );
		$pay_period_id = $pplf->getByUserIdAndEndDate( $this->getUser(), $this->getLastDate() )->getCurrent()->getId();
		Debug::Text('Pay Period ID: '. $pay_period_id, __FILE__, __LINE__, __METHOD__,10);

		if ( is_numeric($pay_period_id) == FALSE ) {
			UserGenericStatusFactory::queueGenericStatus( $this->getUserObject()->getFullName(TRUE).' - '.TTi18n::gettext('Pay Stub'), 10, TTi18n::gettext('Pay Period is invalid!'), NULL );

			return FALSE;
		}

		if ( $this->getEnableGeneratePayStub() == TRUE ) {
			//Find out if a pay stub is already generated for the pay period we are currently in.
			//If it is, delete it so we can start from fresh
			$pslf = TTnew( 'PayStubListFactory' );
			$pslf->getByUserIdAndPayPeriodId( $this->getUser(), $pay_period_id );

			foreach ($pslf as $pay_stub) {
				Debug::Text('Found Pay Stub ID: '. $pay_stub->getId(), __FILE__, __LINE__, __METHOD__,10);
				//Do not delete PAID pay stubs!
				if ( $pay_stub->getStatus() == 10 ) {
					Debug::Text('Last Pay Stub Exists: '. $pay_stub->getId(), __FILE__, __LINE__, __METHOD__,10);
					$pay_stub->setDeleted(TRUE);
					$pay_stub->Save();
				}
			}

			//FIXME: Make sure user isn't already in-active! Otherwise pay stub won't generate.
			//Check if pay stub is already generated as well, if it is, and marked paid, then
			//we can't re-generate it, we need to skip this step.
			Debug::Text('Calculating Pay Stub...', __FILE__, __LINE__, __METHOD__,10);
			$cps = new CalculatePayStub();
			$cps->setUser( $this->getUser() );
			$cps->setPayPeriod( $pay_period_id );
			$cps->calculate();
			Debug::Text('Done Calculating Pay Stub', __FILE__, __LINE__, __METHOD__,10);
		} else {
			UserGenericStatusFactory::queueGenericStatus( $this->getUserObject()->getFullName(TRUE), 20, TTi18n::gettext('Not generating final pay stub!'), NULL );
		}

		//FIXME: Alert the user if they don't have enough information in TimeTrex to get accurate values.
		//Get insurable hours, earnings, and vacation pay now that the final pay stub is generated
		$ugdlf = TTnew( 'UserGenericDataListFactory' );
		$ugdlf->getByCompanyIdAndScriptAndDefault( $this->getUserObject()->getCompany(), $this->getTable() );
		if ( $ugdlf->getRecordCount() > 0 ) {
			Debug::Text('Found Company Form Setup!', __FILE__, __LINE__, __METHOD__,10);
			$ugd_obj = $ugdlf->getCurrent();
			$setup_data = $ugd_obj->getData();
		}
		unset($ugd_obj);

		$absence_policy_ids = array();
		$insurable_earnings_psea_ids = array();
		if ( isset($setup_data) ) {
			//var_dump($setup_data);
			if ( isset($setup_data['insurable_earnings_psea_ids']) ) {
				$insurable_earnings_psea_ids = $setup_data['insurable_earnings_psea_ids'];
			} else {
				//Fall back to Total Gross.
				$insurable_earnings_psea_ids = $this->getPayStubEntryAccountLinkObject()->getTotalGross();
			}

			if ( isset($setup_data['absence_policy_ids']) ) {
				$absence_policy_ids = $setup_data['absence_policy_ids'];
			}
		}

		//Find out the date of how far back we have to go to get insurable values.
		//Insurable Hours
		$insurable_hours_start_date = $this->getInsurablePayPeriodStartDate( $this->getInsurableHoursReportPayPeriods() );

		//All worked time and overtime is considered insurable.
		$udtlf = TTnew( 'UserDateTotalListFactory' );
		$worked_total_time = $udtlf->getWorkedTimeSumByUserIDAndStartDateAndEndDate( $this->getUser(), $insurable_hours_start_date, $this->getLastDate() );
		Debug::text('Worked Total Time: '. $worked_total_time, __FILE__, __LINE__, __METHOD__,10);

		//User definable absence policies for insurable hours.
		$absence_total_time = $udtlf->getAbsenceTimeSumByUserIDAndAbsenceIDAndStartDateAndEndDate( $this->getUser(), $absence_policy_ids, $insurable_hours_start_date, $this->getLastDate() );
		Debug::text('Absence Total Time: '. $absence_total_time, __FILE__, __LINE__, __METHOD__,10);

		$total_hours = Misc::MoneyFormat( TTDate::getHours( $worked_total_time + $absence_total_time ), FALSE );
		Debug::Text('Total Insurable Hours: '. $total_hours, __FILE__, __LINE__, __METHOD__,10);

		$insurable_earnings_start_date = $this->getInsurablePayPeriodStartDate( $this->getInsurableEarningsReportPayPeriods() );

		$pself = TTnew( 'PayStubEntryListFactory' );
		$total_earnings = $this->getTotalInsurableEarnings();

		//Note, this includes the current pay stub we just generated
		Debug::Text('Total Insurable Earnings: '. $total_earnings, __FILE__, __LINE__, __METHOD__,10);

		UserGenericStatusFactory::queueGenericStatus( $this->getUserObject()->getFullName(TRUE).' - '. TTi18n::gettext('Record of Employment'), 30, TTi18n::gettext('Insurable Hours:').' '. $total_hours .' '. TTi18n::gettext('Insurable Earnings:').' '. $total_earnings, NULL );

		//ReSave these
		if ( $this->getId() != '' ) {
			$rlf = TTnew( 'ROEListFactory' );
			$rlf->getById( $this->getId() );
			if ( $rlf->getRecordCount() > 0 ) {
				$roe_obj = $rlf->getCurrent();

				$roe_obj->setInsurableHours( $total_hours );
				$roe_obj->setInsurableEarnings( $total_earnings );
				if ( $roe_obj->isValid() ) {
					$roe_obj->Save();
				}
			}
		}

		return TRUE;
	}

    function getFormObject() {
		if ( !isset($this->form_obj['gf']) OR !is_object($this->form_obj['gf']) ) {
			//
			//Get all data for the form.
			//
			require_once( Environment::getBasePath() .'/classes/fpdi/fpdi.php');
			require_once( Environment::getBasePath() .'/classes/tcpdf/tcpdf.php');
			require_once( Environment::getBasePath() .'/classes/GovernmentForms/GovernmentForms.class.php');

			$gf = new GovernmentForms();

			$this->form_obj['gf'] = $gf;
			return $this->form_obj['gf'];
		}

		return $this->form_obj['gf'];
	}

	function getROEObject() {
		if ( !isset($this->form_obj['roe']) OR !is_object($this->form_obj['roe']) ) {
			$this->form_obj['roe'] = $this->getFormObject()->getFormObject( 'ROE', 'CA' );
			return $this->form_obj['roe'];
		}

		return $this->form_obj['roe'];
	}

	function exportROE( $rlf ) {
		if ( !is_object($rlf) AND $this->getId() != '' ) {
			$rlf = TTnew( 'ROEListFactory' );
			$rlf->getById( $this->getId() );
		}

		if ( get_class( $rlf ) !== 'ROEListFactory' ) {
			return FALSE;
		}

		$border = 0;

		if ( $rlf->getRecordCount() > 0 ) {
			$ppsf = TTnew( 'PayPeriodScheduleListFactory' );
			$pay_period_type_options = array(
											//5 => TTi18n::gettext('Manual'),
											10  => 'W',
											20  => 'B',
											30  => 'S',
											50  => 'M'
										);

			$xml = new SimpleXMLElement('<ROEHEADER Application="RoeWeb" FileVersion="1.00"></ROEHEADER>');

			$r=0;
			foreach ($rlf as $r_obj) {

				//$r_obj->getTotalInsurableEarnings();

				//Get User information
				$ulf = TTnew( 'UserListFactory' );
				$user_obj = $ulf->getById( $r_obj->getUser() )->getCurrent();

				$ulf = TTnew( 'UserListFactory' );
				$created_user_obj = $ulf->getById( $r_obj->getCreatedBy() )->getCurrent();

				//Get company information
				$clf = TTnew( 'CompanyListFactory' );
				$company_obj = $clf->getById( $user_obj->getCompany() )->getCurrent();

				$xml->addChild('Roe');

				//Box5
				$xml->Roe[$r]->addChild('B5', $company_obj->getBusinessNumber() );

				//Box6
				$xml->Roe[$r]->addChild('B6', $pay_period_type_options[$r_obj->getPayPeriodType()] );

				//Box8
				$xml->Roe[$r]->addChild('B8', $user_obj->getSIN() );

				//Box9
				$xml->Roe[$r]->addChild('B9');
				$xml->Roe[$r]->B9->addChild('FN', $user_obj->getFirstName() );
				$xml->Roe[$r]->B9->addChild('MN', substr( $user_obj->getMiddleName(), 0, 1) );
				$xml->Roe[$r]->B9->addChild('LN', $user_obj->getLastName() );
				$xml->Roe[$r]->B9->addChild('A1', $user_obj->getAddress1().' '.$user_obj->getAddress2() );
				$xml->Roe[$r]->B9->addChild('A2', $user_obj->getCity() );
				$xml->Roe[$r]->B9->addChild('A3', $user_obj->getProvince() .' '. $user_obj->getPostalCode() );

				//Box10
				$xml->Roe[$r]->addChild('B10', date('dmY', $r_obj->getFirstDate() ) );

				//Box11
				$xml->Roe[$r]->addChild('B11',  date('dmY', $r_obj->getLastDate() ) );

				//Box12
				$xml->Roe[$r]->addChild('B12',  date('dmY', $r_obj->getPayPeriodEndDate() ) );

				//Box13 - Employee Title
				if ( is_object($user_obj->getTitleObject() ) ) {
					$title = $user_obj->getTitleObject()->getName();
					$xml->Roe[$r]->addChild('B13', $title );
				}

				//Box15A
				$xml->Roe[$r]->addChild('B15A', round( $r_obj->getInsurableHours() ) );

				//Box15B
				$xml->Roe[$r]->addChild('B15B', $r_obj->getInsurableEarnings() );

				//Box15C
				$xml->Roe[$r]->addChild('B15C');
				if ( $r_obj->isPayPeriodWithNoEarnings() == TRUE ) {
					$pay_period_earnings = $r_obj->getInsurableEarningsByPayPeriod();
					if ( is_array($pay_period_earnings) ) {
						$i=1;
						$x=0;
						foreach( $pay_period_earnings as $pay_period_earning ) {
							$xml->Roe[$r]->B15C->addChild('PP');
							$xml->Roe[$r]->B15C->PP[$x]->addAttribute('nbr', $i);
							$xml->Roe[$r]->B15C->PP[$x]->addChild('AMT', (float)$pay_period_earning['amount'] );
							$i++;
							$x++;
						}
					}
				} else {
					$xml->Roe[$r]->B15C->addChild('PP');
					$xml->Roe[$r]->B15C->PP->addAttribute('nbr', 1);
					$xml->Roe[$r]->B15C->PP->addChild('AMT', $r_obj->getInsurableEarnings() );
				}

				//Box16
				$xml->Roe[$r]->addChild('B16' );
				$xml->Roe[$r]->B16->addChild('CD', $r_obj->getCode() );
				$xml->Roe[$r]->B16->addChild('FN', $created_user_obj->getFirstName() );
				$xml->Roe[$r]->B16->addChild('LN', $created_user_obj->getLastName() );

				if ( $created_user_obj->getWorkPhone() != '' ) {
					$phone = $created_user_obj->getWorkPhone();
				} else {
					$phone = $created_user_obj->getCompanyObject()->getWorkPhone();
				}
				$validator = new Validator();
				$phone = $validator->stripNonNumeric($phone);

				$xml->Roe[$r]->B16->addChild('AC', substr($phone, 0, 3 ) );
				$xml->Roe[$r]->B16->addChild('TEL', substr($phone, 3, 7 ) );

				//Box17A
				$vacation_pay = $r_obj->getLastPayPeriodVacationEarnings();
				if ( $vacation_pay > 0 ) {
					$xml->Roe[$r]->addChild('B17A', $vacation_pay );
				}

				$xml->Roe[$r]->addChild('B18', $r_obj->getComments() );

				$r++;
			}

			$output = $xml->asXML();
		}

		if ( isset($output) ) {
			return $output;
		}

		return FALSE;
	}

	function getROE( $rlf = NULL, $show_background = TRUE ) {

		if ( !is_object($rlf) AND $this->getId() != '' ) {
			$rlf = TTnew( 'ROEListFactory' );
			$rlf->getById( $this->getId() );
		}

		if ( get_class( $rlf ) !== 'ROEListFactory' ) {
			return FALSE;
		}

		$border = 0;

		if ( $rlf->getRecordCount() > 0 ) {
			$ppsf = TTnew( 'PayPeriodScheduleListFactory' );
			$pay_period_type_options = $ppsf->getOptions('type');

			$pdf = new TTPDF();
			$pdf->setMargins(0,0,0,0);
			$pdf->SetAutoPageBreak(FALSE);

			foreach ($rlf  as $r_obj) {
				$pdf->SetFont('freesans','',12);

				//Get User information
				$ulf = TTnew( 'UserListFactory' );
				$user_obj = $ulf->getById( $r_obj->getUser() )->getCurrent();

				$ulf = TTnew( 'UserListFactory' );
				$created_user_obj = $ulf->getById( $r_obj->getCreatedBy() )->getCurrent();

				//Get company information
				$clf = TTnew( 'CompanyListFactory' );
				$company_obj = $clf->getById( $user_obj->getCompany() )->getCurrent();

				$pdf->AddPage();

				if ( $show_background == TRUE ) {
					//Use this command to convert PDF to images: convert -density 600x600 -quality 00 $file
					$pdf->Image(Environment::getImagesPath() .'roe-template.jpg',0,0,210,300);
				}

				//Serial
				$pdf->setXY(10,17);
				$pdf->Cell(55,10,$r_obj->getSerial(), $border, 0, 'L');

				//Employer Info
				$pdf->setXY(10,30);
				$pdf->Cell(120,10,$company_obj->getName(), $border, 0, 'L');

				$pdf->setXY(10,40);
				$pdf->Cell(120,10,$company_obj->getAddress1().' '.$company_obj->getAddress2(), $border, 0, 'L');

				$pdf->setXY(10,50);
				$pdf->Cell(90,10,$company_obj->getCity().', '.$company_obj->getProvince(), $border, 0, 'L');

				$postal_code_a = substr($company_obj->getPostalCode(), 0, 3);
				$postal_code_b = substr($company_obj->getPostalCode(), 3, 6);

				$pdf->setXY(110,50);
				$pdf->Cell(10,10,$postal_code_a, $border, 0, 'L');

				$pdf->setXY(122,50);
				$pdf->Cell(10,10,$postal_code_b, $border, 0, 'L');

				//Business Number
				$pdf->setXY(138,28);
				$pdf->Cell(120,10,$company_obj->getBusinessNumber(), $border, 0, 'L');

				//Pay Period Type
				$pdf->setXY(138,40);
				$pdf->Cell(50,10, $pay_period_type_options[$r_obj->getPayPeriodType()], $border, 0, 'L');

				//SIN
				$pdf->setXY(138,50);
				$pdf->Cell(50,10,$user_obj->getSIN(), $border, 0, 'L');

				//Employee info
				$pdf->SetFontSize(10);
				$pdf->setXY(10,75);
				$pdf->Cell(90,5,$user_obj->getFullName(), $border, 0, 'L');

				$pdf->setXY(10,80);
				$pdf->Cell(90,5,$user_obj->getAddress1().' '.$user_obj->getAddress2(), $border, 0, 'L');

				$pdf->setXY(10,85);
				$pdf->Cell(90,5,$user_obj->getCity().', '.$user_obj->getProvince() .' '. $user_obj->getPostalCode() , $border, 0, 'L');

				$pdf->SetFontSize(12);

				//Employee Title
				if ( is_object($user_obj->getTitleObject() ) ) {
					$title = $user_obj->getTitleObject()->getName();
				} else {
					$title = NULL;
				}
				$pdf->setXY(10,100);
				$pdf->Cell(90,10, $title, $border, 0, 'L');

				//First Day Worked
				$pdf->SetFontSize(10);
				$first_date = getdate( $r_obj->getFirstDate() );
				$pdf->setXY(175,64);
				$pdf->Cell(8,10, $first_date['mday'], $border, 0, 'C');
				$pdf->setXY(185,64);
				$pdf->Cell(8,10, $first_date['mon'], $border, 0, 'C');
				$pdf->setXY(196,64);
				$pdf->Cell(10,10, $first_date['year'], $border, 0, 'C');

				//Last day paid
				$last_date = getdate( $r_obj->getLastDate() );
				$pdf->setXY(175,75);
				$pdf->Cell(8,10, $last_date['mday'], $border, 0, 'C');
				$pdf->setXY(185,75);
				$pdf->Cell(8,10, $last_date['mon'], $border, 0, 'C');
				$pdf->setXY(196,75);
				$pdf->Cell(10,10, $last_date['year'], $border, 0, 'C');

				//Pay Period End Date
				$pay_period_end_date = getdate( $r_obj->getPayPeriodEndDate() );
				$pdf->setXY(175,86);
				$pdf->Cell(8,10, $pay_period_end_date['mday'], $border, 0, 'C');
				$pdf->setXY(185,86);
				$pdf->Cell(8,10, $pay_period_end_date['mon'], $border, 0, 'C');
				$pdf->setXY(196,86);
				$pdf->Cell(10,10, $pay_period_end_date['year'], $border, 0, 'C');

				//Insurable Hours
				$pdf->SetFontSize(10);
				$pdf->setXY(75,113);
				$pdf->Cell(25,10, Misc::getBeforeDecimal( $r_obj->getInsurableHours() ), $border, 0, 'R');

				$pdf->setXY(101,113);
				$pdf->Cell(10,10, Misc::getAfterDecimal( Misc::MoneyFormat( $r_obj->getInsurableHours(), FALSE ) ), $border, 0, 'L');

				//Enter Code
				$pdf->setXY(185,113);
				$pdf->Cell(10,10, $r_obj->getCode(), $border, 0, 'C');

				//Further Information Contact Name
				$pdf->setXY(130,126);
				$pdf->Cell(75,5, $created_user_obj->getFullName() , $border, 0, 'R');
				$pdf->setXY(130,132);
				$pdf->Cell(75,10, $created_user_obj->getWorkPhone() , $border, 0, 'R');

				//Insurable Earnings
				$pdf->setXY(75,131);
				$pdf->Cell(25,10, Misc::getBeforeDecimal( $r_obj->getInsurableEarnings() ), $border, 0, 'R');

				$pdf->setXY(101,131);
				$pdf->Cell(10,10, Misc::getAfterDecimal( Misc::MoneyFormat( $r_obj->getInsurableEarnings(), FALSE ) ), $border, 0, 'L');

				//Check to see if a pay period didn't have earnings.
				if ( $r_obj->isPayPeriodWithNoEarnings() == TRUE ) {
					$pay_period_earnings = $r_obj->getInsurableEarningsByPayPeriod();
					if ( is_array($pay_period_earnings) ) {

						//Add additional entries for testing alignment purposes.
						/*
						for( $y=0; $y < 14; $y++ ) {
							$pay_period_earnings[] = array('amount' => rand(1,10) );
						}
						*/

						$top_left_x = $x = Misc::AdjustXY(30, 0);
						$top_left_y = $y = Misc::AdjustXY(157, 0);

						$col=1;
						$i=1;
						foreach( $pay_period_earnings as $pay_period_earning ) {
							Debug::Text('I: '. $i .' X: '. $x .' Y: '. $y .' Col: '. $col .' Amount: '. (float)$pay_period_earning['amount'], __FILE__, __LINE__, __METHOD__,10);
							$pdf->setXY( $x, $y );
							$pdf->Cell(6,6, Misc::MoneyFormat( (float)$pay_period_earning['amount'], FALSE ), $border, 0, 'R');

							if ( $i > 0 AND $i % 3 == 0 ) {
								$x = $top_left_x;
								$y += 7;
							} else {
								$x += 35;
							}
							$i++;
						}
					}
				}

				//Box 17A, Vacation pay in last pay period.
				$vacation_pay = $r_obj->getLastPayPeriodVacationEarnings();
				if ( $vacation_pay > 0 ) {
					$pdf->setXY(132,155);
					$pdf->Cell(10,10, Misc::getBeforeDecimal( Misc::MoneyFormat( $vacation_pay, FALSE ) ), $border, 0, 'R');
					$pdf->Cell(10,10, Misc::getAfterDecimal( Misc::MoneyFormat( $vacation_pay, FALSE ) ), $border, 0, 'L');
				}

				//Comments
				$pdf->setXY(115,212);
				$pdf->MultiCell(85,5, $r_obj->getComments(), $border, 'L');

				//English
				$pdf->setXY(8.5,256.5);
				$pdf->Cell(10,10, 'X', $border, 0, 'L');

				//ROE creator phone number
				$pdf->setXY(75,258);
				$pdf->Cell(25,10, $created_user_obj->getWorkPhone() , $border, 0, 'L');

				//ROE create name.
				$pdf->SetFontSize(12);
				$pdf->setXY(87,273);
				$pdf->Cell(75,10, $created_user_obj->getFullName() , $border, 0, 'C');

				//Create Date
				$created_date = getdate( $r_obj->getCreatedDate() );
				$pdf->SetFontSize(10);
				$pdf->setXY(175,273);
				$pdf->Cell(8,10, $created_date['mday'] , $border, 0, 'C');

				$pdf->setXY(185,273);
				$pdf->Cell(8,10, $created_date['mon'] , $border, 0, 'C');

				$pdf->setXY(195,273);
				$pdf->Cell(10,10, $created_date['year'] , $border, 0, 'C');
			}

			$output = $pdf->Output('','S');
		}

		if ( isset($output) ) {
			return $output;
		}

		return FALSE;
	}

    function Validate() {


		return TRUE;
	}

	function preSave() {

		if ( $this->isNew() AND $this->getEnableReleaseAccruals() == TRUE ) {
			//Create PS amendment releasing all accruals
			UserGenericStatusFactory::queueGenericStatus( $this->getUserObject()->getFullName(TRUE).' - '. TTi18n::gettext('Pay Stub Amendment'), 30, TTi18n::gettext('Releasing all employee accruals'), NULL );

			PayStubAmendmentFactory::releaseAllAccruals( $this->getUser(), $this->getLastDate() );
		}

		//Start these off as zero, until we can save this row, and re-calc them after
		//the final pay stub has been generated.
		if ( $this->getInsurableHours() == '' ) {
			$this->setInsurableHours( 0 );
		}
		if ( $this->getInsurableEarnings() == '' ) {
			$this->setInsurableEarnings( 0 );
		}

		return TRUE;
	}

	function postSave() {
		//Handle dirty work here.
		Debug::Text('ID we just saved: '. $this->getId(), __FILE__, __LINE__, __METHOD__,10);

		if ( $this->getEnableReCalculate() == TRUE ) {
			//Set User Termination date to Last Day.
			$ulf = TTnew( 'UserListFactory' );
			$ulf->getById( $this->getUser() );
			if ( $ulf->getRecordCount() > 0 ) {
				Debug::Text('Setting User Termination Date', __FILE__, __LINE__, __METHOD__,10);

				$user_obj = $ulf->getCurrent();
				$user_obj->setTerminationDate( $this->getLastDate() );
				if ( $user_obj->isValid() ) {
					$user_obj->Save();

					UserGenericStatusFactory::queueGenericStatus( $this->getUserObject()->getFullName(TRUE).' - '. TTi18n::gettext('Employee Record'), 30, TTi18n::gettext('Setting employee termination date to:').' '. TTDate::getDate('DATE', $this->getLastDate() ), NULL );
				}
			}

			$this->ReCalculate();
		}

		return TRUE;
	}

    function setObjectFromArray( $data ) {
		if ( is_array( $data ) ) {
			$variable_function_map = $this->getVariableToFunctionMap();
			foreach( $variable_function_map as $key => $function ) {
				if ( isset($data[$key]) ) {

					$function = 'set'.$function;
					switch( $key ) {
					    case 'first_date':
                            $this->setFirstDate( TTDate::parseDateTime( $data['first_date'] ) );
                            break;
                        case 'last_date':
                            $this->setLastDate( TTDate::parseDateTime( $data['last_date'] ) );
                            break;
                        case 'pay_period_end_date':
                            $this->setPayPeriodEndDate( TTDate::parseDateTime( $data['pay_period_end_date'] ) );
                            break;
                        case 'recall_date':
                            $this->setRecallDate( TTDate::parseDateTime( $data['recall_date'] ) );
                            break;
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

	function getObjectAsArray( $include_columns = NULL ) {
		$variable_function_map = $this->getVariableToFunctionMap();
		if ( is_array( $variable_function_map ) ) {
			foreach( $variable_function_map as $variable => $function_stub ) {
				if ( $include_columns == NULL OR ( isset($include_columns[$variable]) AND $include_columns[$variable] == TRUE ) ) {

					$function = 'get'.$function_stub;
					switch( $variable ) {
					    case 'first_name':
                        case 'last_name':
                            $data[$variable] = $this->getColumn( $variable );
							break;
						case 'code':
							$function = 'get'.$variable;
							if ( method_exists( $this, $function ) ) {
								$data[$variable] = Option::getByKey( $this->$function(), $this->getOptions( $variable ) );
							}
							break;
                        case 'pay_period_type':
                            $ppsf = TTnew( 'PayPeriodScheduleFactory' );
                            $data[$variable] = Option::getByKey( $this->getPayPeriodType(), $ppsf->getOptions( 'type' ) );
                            break;
                        case 'first_date':
                            $data[$variable] = TTDate::getAPIDate( 'DATE', $this->getFirstDate() );
                            break;
						case 'last_date':
                            $data[$variable] = TTDate::getAPIDate( 'DATE', $this->getLastDate() );
                            break;
                        case 'pay_period_end_date':
                            $data[$variable] = TTDate::getAPIDate( 'DATE', $this->getPayPeriodEndDate() );
                            break;
                        case 'recall_date':
                            $data[$variable] = TTDate::getAPIDate( 'DATE', $this->getRecallDate() );
                            break;
                        case 'insurable_earnings':
                            $data[$variable] = $this->getInsurableEarnings();
                            break;
                        case 'vacation_pay':
                            $data[$variable] = $this->getVacationPay();
                            break;
						default:
							if ( method_exists( $this, $function ) ) {
								$data[$variable] = $this->$function();
							}
							break;
					}

				}
			}
			$this->getCreatedAndUpdatedColumns( $data, $include_columns );
		}

		return $data;
	}

	function addLog( $log_action ) {
		return TTLog::addEntry( $this->getId(), $log_action, TTi18n::getText('ROE'), NULL, $this->getTable(), $this );
	}
}
?>
