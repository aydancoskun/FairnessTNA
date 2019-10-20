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
 * @package Modules\PayStubAmendment
 */
class RecurringPayStubAmendmentFactory extends Factory {
	protected $table = 'recurring_ps_amendment';
	protected $pk_sequence_name = 'recurring_ps_amendment_id_seq'; //PK Sequence name

	/**
	 * @param $name
	 * @param null $parent
	 * @return array|bool|null
	 */
	function _getFactoryOptions( $name, $parent = NULL ) {

		$retval = NULL;
		switch( $name ) {
			case 'filtered_status':
				//Select box options;
				$status_options_filter = array(50, 60);
				$retval = Option::getByArray( $status_options_filter, $this->getOptions('status') );
				break;
			case 'status':
				$retval = array(
										10 => TTi18n::gettext('INCOMPLETE'),
										20 => TTi18n::gettext('OPEN'),
										30 => TTi18n::gettext('PENDING AUTHORIZATION'),
										40 => TTi18n::gettext('AUTHORIZATION OPEN'),
										50 => TTi18n::gettext('ACTIVE'),
										55 => TTi18n::gettext('AUTHORIZATION DECLINED'),
										60 => TTi18n::gettext('DISABLED')
									);
				break;
			case 'frequency':
				$retval = array(
										10 => TTi18n::gettext('each Pay Period'),
										30 => TTi18n::gettext('Weekly'),
										40 => TTi18n::gettext('Monthly'),
										70 => TTi18n::gettext('Yearly'),

										//20 => TTi18n::gettext('every 2nd Pay Period'),
										//30 => TTi18n::gettext('twice per Pay Period'),
										//50 => TTi18n::gettext('every 2nd Month'),
										//52 => TTi18n::gettext('twice per Month'),
										//60 => TTi18n::gettext('Bi-Weekly'),
										//80 => TTi18n::gettext('Bi-Annually')
									);
				break;
			case 'percent_amount':
				$retval = array(
										10 => TTi18n::gettext('Gross Wage')
									);
				break;

			case 'type':
				$retval = array(
											10 => TTi18n::gettext('Fixed'),
											20 => TTi18n::gettext('Percent')
										);
				break;
			case 'pay_stub_account_type':
				$retval = array(10, 20, 30, 50, 60, 65);
				break;
			case 'percent_pay_stub_account_type':
				$retval = array(10, 20, 30, 40, 50, 60, 65);
				break;
			case 'columns':
				$retval = array(
										'-1000-name' => TTi18n::gettext('Name'),
										'-1002-description' => TTi18n::gettext('Description'),

										'-1110-status' => TTi18n::gettext('Status'),
										'-1115-frequency' => TTi18n::gettext('Frequency'),
										'-1120-type' => TTi18n::gettext('Type'),
										'-1130-pay_stub_entry_name' => TTi18n::gettext('Account'),
										'-1140-effective_date' => TTi18n::gettext('Effective Date'),
										'-1150-amount' => TTi18n::gettext('Amount'),
										'-1160-rate' => TTi18n::gettext('Rate'),
										'-1170-units' => TTi18n::gettext('Units'),

										'-1180-ps_amendment_description' => TTi18n::gettext('PS Amendment Description'),

										'-1190-start_date' => TTi18n::gettext('Start Date'),
										'-1190-end_date' => TTi18n::gettext('End Date'),

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
								'status',
								'name',
								'description',
								'type',
								'frequency',
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

	/**
	 * @param $data
	 * @return array
	 */
	function _getVariableToFunctionMap( $data ) {
		$variable_function_map = array(
										'id' => 'ID',
										'company_id' => 'Company',
										'start_date' => 'StartDate',
										'end_date' => 'EndDate',
										'frequency_id' => 'Frequency',
										'frequency' => FALSE,
										'name' => 'Name',
										'description' => 'Description',
										'pay_stub_entry_name_id' => 'PayStubEntryNameId',
										'pay_stub_entry_name' => FALSE,
										'status_id' => 'Status',
										'status' => FALSE,
										'type_id' => 'Type',
										'type' => FALSE,
										'rate' => 'Rate',
										'units' => 'Units',
										'amount' => 'Amount',
										'percent_amount' => 'PercentAmount',
										'percent_amount_entry_name_id' => 'PercentAmountEntryNameId',
										'ps_amendment_description' => 'PayStubAmendmentDescription',
										'user' => 'User',
										'deleted' => 'Deleted',
										);
		return $variable_function_map;
	}

	/**
	 * @return bool|mixed
	 */
	function getCompany() {
		return $this->getGenericDataValue( 'company_id' );
	}

	/**
	 * @param string $value UUID
	 * @return bool
	 */
	function setCompany( $value) {
		$value = TTUUID::castUUID( $value );

		Debug::Text('Company ID: '. $value, __FILE__, __LINE__, __METHOD__, 10);
		return $this->setGenericDataValue( 'company_id', $value );
	}

	/**
	 * @return mixed
	 */
	function getStartDate() {
		return $this->getGenericDataValue( 'start_date' );
	}

	/**
	 * @param int $value EPOCH
	 * @return bool
	 */
	function setStartDate( $value) {
		$value = ( !is_int($value) ) ? trim($value) : $value; //Dont trim integer values, as it changes them to strings.

		//Add 12 hours to effective date, because we won't want it to be a
		//day boundary and have issues with pay period end date.
		//$epoch = TTDate::getBeginDayEpoch( $epoch ) + (43200-1);

		return $this->setGenericDataValue( 'start_date', $value );
	}

	/**
	 * @return bool|mixed
	 */
	function getEndDate() {
		return $this->getGenericDataValue( 'end_date' );
	}

	/**
	 * @param int $value EPOCH
	 * @return bool
	 */
	function setEndDate( $value) {
		$value = ( !is_int($value) ) ? trim($value) : $value; //Dont trim integer values, as it changes them to strings.

		//Add 12 hours to effective date, because we won't want it to be a
		//day boundary and have issues with pay period end date.
		if ( $value != '' ) {
			$value = ( TTDate::getBeginDayEpoch( $value ) + (43200 - 1) );
		}
		return $this->setGenericDataValue( 'end_date', $value );
	}

	/**
	 * @return bool|int
	 */
	function getFrequency() {
		return $this->getGenericDataValue( 'frequency_id' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setFrequency( $value) {
		$value = (int)trim($value);
		return $this->setGenericDataValue( 'frequency_id', $value );
	}

	/**
	 * @return bool|mixed
	 */
	function getName() {
		return $this->getGenericDataValue( 'name' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setName( $value) {
		$value = trim($value);
		return $this->setGenericDataValue( 'name', htmlspecialchars( $value ) );
	}

	/**
	 * @return bool|mixed
	 */
	function getDescription() {
		return $this->getGenericDataValue( 'description' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setDescription( $value) {
		$value = trim($value);
		return $this->setGenericDataValue( 'description', htmlspecialchars( $value ) );
	}

	/**
	 * @return array|bool
	 */
	function getUser() {
		$rpsaulf = TTnew( 'RecurringPayStubAmendmentUserListFactory' ); /** @var RecurringPayStubAmendmentUserListFactory $rpsaulf */
		$rpsaulf->getByRecurringPayStubAmendment( $this->getId() );
		$user_list = array();
		foreach ($rpsaulf as $ps_amendment_user) {
			$user_list[] = $ps_amendment_user->getUser();
		}

		if ( empty($user_list) == FALSE ) {
			return $user_list;
		}

		return FALSE;
	}

	/**
	 * @param string $ids UUID
	 * @return bool
	 */
	function setUser( $ids) {
		if ( !is_array($ids) ) {
			$ids = array($ids);
		}
		Debug::Arr($ids, 'Selected IDs: ', __FILE__, __LINE__, __METHOD__, 10);

		if ( is_array($ids) ) {
			if ( in_array( -1, $ids ) ) {
				Debug::text('All Users is selected: ', __FILE__, __LINE__, __METHOD__, 10);
				$ids = array(-1);
			}

			$tmp_ids = array();
			if ( !$this->isNew() ) {
				//If needed, delete mappings first.
				$rpsaulf = TTnew( 'RecurringPayStubAmendmentUserListFactory' ); /** @var RecurringPayStubAmendmentUserListFactory $rpsaulf */
				$rpsaulf->getByRecurringPayStubAmendment( $this->getId() );
				foreach ($rpsaulf as $obj) {
					$id = $obj->getUser();
					Debug::text('Recurring Schedule ID: '. $obj->getRecurringPayStubAmendment() .' ID: '. $id, __FILE__, __LINE__, __METHOD__, 10);

					//Delete users that are not selected.
					if ( !in_array($id, $ids) ) {
						Debug::text('Deleting: '. $id, __FILE__, __LINE__, __METHOD__, 10);
						$obj->Delete();
					} else {
						//Save ID's that need to be updated.
						Debug::text('NOT Deleting : '. $id, __FILE__, __LINE__, __METHOD__, 10);
						$tmp_ids[] = $id;
					}
				}
				unset($id, $obj);
			}

			//Insert new mappings.
			$ulf = TTnew( 'UserListFactory' ); /** @var UserListFactory $ulf */
			foreach ($ids as $id) {
				if ( isset($ids) AND !in_array($id, $tmp_ids) ) {
					if ( $id == -1 ) {
						Debug::text('--ALL-- Employees selected...', __FILE__, __LINE__, __METHOD__, 10);
						$rpsauf = TTnew( 'RecurringPayStubAmendmentUserFactory' ); /** @var RecurringPayStubAmendmentUserFactory $rpsauf */
						$rpsauf->setRecurringPayStubAmendment( $this->getId() );
						$rpsauf->setUser( $id );

						if ($this->Validator->isTrue(		'user',
															$rpsauf->Validator->isValid(),
															TTi18n::gettext('Invalid Employee') ) ) {
							$rpsauf->save();
						}

					} else {
						$rpsauf = TTnew( 'RecurringPayStubAmendmentUserFactory' ); /** @var RecurringPayStubAmendmentUserFactory $rpsauf */
						$rpsauf->setRecurringPayStubAmendment( $this->getId() );
						$rpsauf->setUser( $id );

						$ulf->getById( $id );
						if ( $ulf->getRecordCount() > 0 ) {
							$obj = $ulf->getCurrent();

							if ($this->Validator->isTrue(		'user',
																$rpsauf->Validator->isValid(),
																TTi18n::gettext('Selected Employee is invalid').' ('. $obj->getFullName() .')' )) {
								$rpsauf->save();
							}
						}
					}
				}
			}

			return TRUE;
		}

		Debug::text('No User IDs to set.', __FILE__, __LINE__, __METHOD__, 10);
		return FALSE;
	}

	/**
	 * @return bool|mixed
	 */
	function getPayStubEntryNameId() {
		return $this->getGenericDataValue( 'pay_stub_entry_name_id' );
	}

	/**
	 * @param string $id UUID
	 * @return bool
	 */
	function setPayStubEntryNameId( $id) {
		$id = trim($id);

		$psealf = TTnew( 'PayStubEntryAccountListFactory' ); /** @var PayStubEntryAccountListFactory $psealf */
		$psealf->getById( $id );

		if (  $this->Validator->isResultSetWithRows(	'pay_stub_entry_name_id',
														$psealf,
														TTi18n::gettext('Invalid Pay Stub Account')
														) ) {

			$this->data['pay_stub_entry_name_id'] = $psealf->getCurrent()->getId();

			return TRUE;
		}

		return FALSE;
	}

	/**
	 * @param $name
	 * @return bool
	 */
	function setPayStubEntryName( $name) {
		$name = trim($name);

		$psenlf = TTnew( 'PayStubEntryNameListFactory' ); /** @var PayStubEntryNameListFactory $psenlf */
		$result = $psenlf->getByName($name);

		if (  $this->Validator->isResultSetWithRows(	'ps_entry_name',
														$result,
														TTi18n::gettext('Invalid Entry Name')
														) ) {

			$this->data['pay_stub_entry_name_id'] = $result->getId();

			return TRUE;
		}

		return FALSE;
	}

	/**
	 * @return bool|int
	 */
	function getStatus() {
		return $this->getGenericDataValue( 'status_id' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setStatus( $value) {
		$value = (int)trim($value);
		return $this->setGenericDataValue( 'status_id', $value );
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
	 * @return null
	 */
	function getRate() {
		return $this->getGenericDataValue( 'rate' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setRate( $value) {
		$value = trim($value);
		Debug::text('Setting Rate to: '. $value, __FILE__, __LINE__, __METHOD__, 10);
		//Must round to 2 decimals otherwise discreptancy can occur when generating pay stubs.
		//$this->data['rate'] = Misc::MoneyFormat( $value, FALSE );
		return $this->setGenericDataValue( 'rate', $value );
	}

	/**
	 * @return null
	 */
	function getUnits() {
		return $this->getGenericDataValue( 'units' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setUnits( $value) {
		$value = trim($value);
		//Must round to 2 decimals otherwise discreptancy can occur when generating pay stubs.
		//$this->data['units'] = Misc::MoneyFormat( $value, FALSE );
		return $this->setGenericDataValue( 'units', $value );
	}

	/**
	 * @return null|string
	 */
	function getAmount() {
		$value = $this->getGenericDataValue( 'amount' );
		if ( $value !== FALSE ) {
			return Misc::removeTrailingZeros( $value, 2);
		}

		return NULL;
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setAmount( $value) {
		$value = trim($value);

		Debug::text('Amount: '. $value .' Name: '. $this->getPayStubEntryNameId(), __FILE__, __LINE__, __METHOD__, 10);

		if ($value == NULL OR $value == '') {
			return FALSE;
		}
		return $this->setGenericDataValue( 'amount', $value );
	}

	/**
	 * @return null
	 */
	function getPercentAmount() {
		return $this->getGenericDataValue( 'percent_amount' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setPercentAmount( $value) {
		$value = trim($value);
		Debug::text('Amount: '. $value .' Name: '. $this->getPayStubEntryNameId(), __FILE__, __LINE__, __METHOD__, 10);
		if ($value == NULL OR $value == '') {
			return FALSE;
		}
		return $this->setGenericDataValue( 'percent_amount',  round( $value, 2) );
	}

	/**
	 * @return bool|mixed
	 */
	function getPercentAmountEntryNameId() {
		return $this->getGenericDataValue( 'percent_amount_entry_name_id' );
	}

	/**
	 * @param string $value UUID
	 * @return bool
	 */
	function setPercentAmountEntryNameId( $value) {
		$value = TTUUID::castUUID( $value );
		//Not sure why we tried to use $result here, as if the ID passed is NULL, it causes a fatal error.
		//$result = $psealf->getById( $id )->getCurrent();
		return $this->setGenericDataValue( 'percent_amount_entry_name_id', $value );
	}

	/**
	 * @return mixed
	 */
	function getPayStubAmendmentDescription() {
		return $this->getGenericDataValue( 'ps_amendment_description' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setPayStubAmendmentDescription( $value) {
		$value = trim($value);
		return $this->setGenericDataValue( 'ps_amendment_description', htmlspecialchars( $value ) );
	}

	/**
	 * @param int $epoch EPOCH
	 * @return bool
	 */
	function checkTimeFrame( $epoch = NULL ) {
		if ( $epoch == NULL ) {
			$epoch = TTDate::getTime();
		}

		//Due to Cron running late, we want to still be able to insert
		//Recurring PS amendments up to two days after the end date.
		if ( ( ( $this->getEndDate() == 0 OR $this->getEndDate() == '' ) AND $epoch >= $this->getStartDate() )
				OR ( ( $this->getEndDate() != 0 AND $this->getEndDate() != '' )
					AND ( $epoch >= $this->getStartDate() AND $epoch <= ($this->getEndDate() + (86400 * 2)) ) ) ) {
			Debug::text('IN TimeFrame: '. TTDate::getDATE('DATE+TIME', $epoch), __FILE__, __LINE__, __METHOD__, 10);
			return TRUE;
		}

		Debug::text('Not in TimeFrame: '. TTDate::getDATE('DATE+TIME', $epoch), __FILE__, __LINE__, __METHOD__, 10);
		return FALSE;
	}

	/**
	 * @param int $epoch EPOCH
	 * @return bool
	 */
	function createPayStubAmendments( $epoch = NULL) {
		//Get all recurring pay stub amendments and generate single pay stub amendments if appropriate.

		if ( $epoch == '' ) {
			$epoch = TTDate::getTime();
		}

		$ulf = TTnew( 'UserListFactory' ); /** @var UserListFactory $ulf */

		Debug::text('Recurring PS Amendment ID: '. $this->getId() .' Frequency: '. $this->getFrequency(), __FILE__, __LINE__, __METHOD__, 10);

		$this->StartTransaction();

		$user_ids = array();
		$tmp_user_ids = $this->getUser();
		if ( $tmp_user_ids[0] == -1) {
			$ulf->getByCompanyIdAndStatus( $this->getCompany(), 10 );
			foreach($ulf as $user_obj) {
				$user_ids[] = $user_obj->getId();
			}
			unset($user_obj);
		} else {
			$user_ids = $this->getUser();
		}
		unset($tmp_user_ids);
		Debug::text('Total User IDs: '. count($user_ids), __FILE__, __LINE__, __METHOD__, 10);

		if ( empty($user_ids) == FALSE ) {

			//Make the PS amendment duplicate check start/end date separate
			//Make the PS amendment effective date separate.
			switch( $this->getFrequency() ) {
				case 10:
					//Get all open pay periods
					$pplf = TTnew( 'PayPeriodListFactory' ); /** @var PayPeriodListFactory $pplf */
					//FIXME: Get all non-closed pay periods AFTER the start date.
					$pplf->getByUserIdListAndNotStatusAndStartDateAndEndDate($user_ids, 20, $this->getStartDate(), $this->getEndDate() ); //All non-closed pay periods
					Debug::text('Found Open Pay Periods: '. $pplf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
					foreach($pplf as $pay_period_obj) {
						Debug::text('Working on Pay Period: '. $pay_period_obj->getId(), __FILE__, __LINE__, __METHOD__, 10);

						//If near the end of a pay period, or a pay period is already ended, add PS amendment if
						//it does not already exist.
						if ( $epoch >= $pay_period_obj->getEndDate()
								AND $this->checkTimeFrame($epoch) ) {
							Debug::text('After end of pay period. Start Date: '. TTDate::getDate('DATE+TIME', $pay_period_obj->getStartDate() ) .' End Date: '. TTDate::getDate('DATE+TIME', $pay_period_obj->getEndDate() ), __FILE__, __LINE__, __METHOD__, 10);

							$psalf = TTnew( 'PayStubAmendmentListFactory' ); /** @var PayStubAmendmentListFactory $psalf */

							//Loop through each user of this Pay Period Schedule adding PS amendments if they don't already exist.
							$pay_period_schedule_users = $pay_period_obj->getPayPeriodScheduleObject()->getUser();
							Debug::text(' Pay Period Schedule Users: '. count($pay_period_schedule_users), __FILE__, __LINE__, __METHOD__, 10);

							foreach( $pay_period_schedule_users as $user_id ) {
								//Make sure schedule user is in the PS amendment user list and user is active.
								Debug::text(' Pay Period Schedule User: '. $user_id, __FILE__, __LINE__, __METHOD__, 10);
								//Debug::Arr($user_ids, ' Recurring PS Amendment Selected Users: ', __FILE__, __LINE__, __METHOD__, 10);

								if ( $ulf->getById( $user_id )->getCurrent()->getStatus() == 10
										AND in_array( $user_id, $user_ids ) ) {

									//Check to see if the amendment was added already.
									if ( $psalf->getByUserIdAndRecurringPayStubAmendmentIdAndStartDateAndEndDate( $user_id, $this->getId(), $pay_period_obj->getStartDate(), $pay_period_obj->getEndDate() )->getRecordCount() == 0 ) {
										//No amendment, good to insert one
										Debug::text('Inserting Recurring PS Amendment for User: '. $user_id, __FILE__, __LINE__, __METHOD__, 10);

										$psaf = TTnew( 'PayStubAmendmentFactory' ); /** @var PayStubAmendmentFactory $psaf */
										$psaf->setUser( $user_id );
										$psaf->setStatus( 50 );

										$psaf->setType( $this->getType() );

										$psaf->setRecurringPayStubAmendmentId( $this->getId() );
										$psaf->setPayStubEntryNameId( $this->getPayStubEntryNameId() );

										if ( $this->getType() == 10 ) {
											$psaf->setRate( $this->getRate() );
											$psaf->setUnits( $this->getUnits() );
											$psaf->setAmount( $this->getAmount() );
										} else {
											$psaf->setPercentAmount( $this->getPercentAmount() );
											$psaf->setPercentAmountEntryNameID( $this->getPercentAmountEntryNameId() );
										}

										$psaf->setDescription( $this->getPayStubAmendmentDescription() );

										$psaf->setEffectiveDate( TTDate::getBeginDayEpoch( $pay_period_obj->getEndDate() ) );

										if ( $psaf->isValid() ) {
											$psaf->Save();
										}
									} else {
										//Amendment already inserted!
										Debug::text('Recurring PS Amendment already inserted for User: '. $user_id, __FILE__, __LINE__, __METHOD__, 10);
									}
								} else {
									Debug::text('Skipping User because they are INACTIVE or are not on the Recurring PS Amendment User List - ID: '. $user_id, __FILE__, __LINE__, __METHOD__, 10);
									//continue;

								}

							}

						} else {
							Debug::text('Not in TimeFrame, not inserting amendments: Epoch: '. $epoch .' Pay Period End Date: '. $pay_period_obj->getEndDate(), __FILE__, __LINE__, __METHOD__, 10);
						}
					}
					break;
				case 30: //Weekly
				case 40: //Monthly
				case 70: //Annually
					switch ( $this->getFrequency() ) {
						case 30:
							$trigger_date = TTDate::getDateOfNextDayOfWeek( TTDate::getBeginWeekEpoch($epoch), $this->getStartDate() );

							$start_date = TTDate::getBeginWeekEpoch($epoch);
							$end_date = TTDate::getEndWeekEpoch($epoch);
							break;
						case 40:
							$trigger_date = TTDate::getDateOfNextDayOfMonth( TTDate::getBeginMonthEpoch($epoch), $this->getStartDate() );
							//$monthly_date = TTDate::getDateOfNextDayOfMonth( TTDate::getBeginMonthEpoch($epoch), $this->getStartDate() );

							$start_date = TTDate::getBeginMonthEpoch($epoch);
							$end_date = TTDate::getEndMonthEpoch($epoch);
							break;
						case 70:
							$trigger_date = TTDate::getDateOfNextYear( $this->getStartDate(), $epoch );

							//$start_date = TTDate::getBeginYearEpoch($epoch);
							//$end_date = TTDate::getEndYearEpoch($epoch);
							$start_date = TTDate::getBeginDayEpoch( ( $epoch - ( 86400 * 365 ) ) );
							$end_date = TTDate::getEndDayEpoch( $epoch );
							break;
					}
					Debug::text('Trigger Date: '. TTDate::getDate('DATE', $trigger_date), __FILE__, __LINE__, __METHOD__, 10);

					if ( $epoch >= $trigger_date
							AND $this->checkTimeFrame($epoch) ) {
							Debug::text('Within timeframe... Start Date: '. TTDate::getDate('DATE+TIME', $start_date ) .' End Date: '. TTDate::getDate('DATE+TIME', $end_date ), __FILE__, __LINE__, __METHOD__, 10);

						foreach( $user_ids as $user_id ) {
							//Make sure schedule user is in the PS amendment user list and user is active.
							if ( $ulf->getById( $user_id )->getCurrent()->getStatus() != 10
									AND !in_array( $user_id, $user_ids ) ) {
								Debug::text('Skipping User because they are INACTIVE or are not on the Recurring PS Amendment User List - ID: '. $user_id, __FILE__, __LINE__, __METHOD__, 10);
								continue;
							}

							$psalf = TTnew( 'PayStubAmendmentListFactory' ); /** @var PayStubAmendmentListFactory $psalf */
							if ( $psalf->getByUserIdAndRecurringPayStubAmendmentIdAndStartDateAndEndDate( $user_id, $this->getId(), $start_date, $end_date )->getRecordCount() == 0 ) {
								//No amendment, good to insert one
								Debug::text('Inserting Recurring PS Amendment for User: '. $user_id, __FILE__, __LINE__, __METHOD__, 10);

								$psaf = TTnew( 'PayStubAmendmentFactory' ); /** @var PayStubAmendmentFactory $psaf */
								$psaf->setUser( $user_id );
								$psaf->setStatus( 50 );

								$psaf->setType( $this->getType() );

								$psaf->setRecurringPayStubAmendmentId( $this->getId() );
								$psaf->setPayStubEntryNameId( $this->getPayStubEntryNameId() );

								if ( $this->getType() == 10 ) {
									$psaf->setRate( $this->getRate() );
									$psaf->setUnits( $this->getUnits() );
									$psaf->setAmount( $this->getAmount() );
								} else {
									$psaf->setPercentAmount( $this->getPercentAmount() );
									$psaf->setPercentAmountEntryNameID( $this->getPercentAmountEntryNameId() );
								}

								$psaf->setDescription( $this->getDescription() );

								$psaf->setEffectiveDate( TTDate::getBeginDayEpoch( $trigger_date ) );

								if ( $psaf->isValid() ) {
									$psaf->Save();
								}
							} else {
								//Amendment already inserted!
								Debug::text('Recurring PS Amendment already inserted for User: '. $user_id, __FILE__, __LINE__, __METHOD__, 10);
							}
						}
					}

					break;
			}
		}

		//$this->FailTransaction();
		$this->CommitTransaction();

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
		// Company
		$clf = TTnew( 'CompanyListFactory' ); /** @var CompanyListFactory $clf */
		$this->Validator->isResultSetWithRows(	'company',
														$clf->getByID($this->getCompany()),
														TTi18n::gettext('Company is invalid')
						 							);
		// Start date
		$this->Validator->isDate(		'start_date',
												$this->getStartDate(),
												TTi18n::gettext('Incorrect start date')
											);
		// End Date
		if ( $this->getEndDate() != '' ) {
			$this->Validator->isDate(		'end_date',
													$this->getEndDate(),
													TTi18n::gettext('Incorrect end date')
												);
		}
		// Frequency
		$this->Validator->inArrayKey(	'frequency',
												$this->getFrequency(),
												TTi18n::gettext('Incorrect Frequency'),
												$this->getOptions('frequency')
											);
		// Name
		if ( $this->getName() != '' ) {
			$this->Validator->isLength(		'name',
													$this->getName(),
													TTi18n::gettext('Invalid Name Length'),
													2,
													100
												);
		}
		// Description
		if ( $this->getDescription() != '' ) {
			$this->Validator->isLength(		'description',
													$this->getDescription(),
													TTi18n::gettext('Invalid Description Length'),
													2,
													100
												);
		}
		// Status
		$this->Validator->inArrayKey(	'status',
												$this->getStatus(),
												TTi18n::gettext('Incorrect Status'),
												$this->getOptions('status')
											);
		// Type
		$this->Validator->inArrayKey(	'type',
												$this->getType(),
												TTi18n::gettext('Incorrect Type'),
												$this->getOptions('type')
											);
		// Rate
		if ( $this->getRate() != '' ) {
			$this->Validator->isFloat( 'rate',
												$this->getRate(),
												TTi18n::gettext('Invalid Rate')
											);
			if ( $this->Validator->isError('rate') == FALSE ) {
				$this->Validator->isLength( 'rate',
												$this->getRate(),
												TTi18n::gettext('Rate has too many digits'),
												0,
												21
											); //Need to include decimal.
			}
			if ( $this->Validator->isError('rate') == FALSE ) {
				$this->Validator->isLengthBeforeDecimal('rate',
																$this->getRate(),
																TTi18n::gettext('Rate has too many digits before the decimal'),
																0,
																16
															);
			}
			if ( $this->Validator->isError('rate') == FALSE ) {
				$this->Validator->isLengthAfterDecimal(	'rate',
																$this->getRate(),
																TTi18n::gettext('Rate has too many digits after the decimal'),
																0,
																4
															);
			}
		}
		// Units
		if ( $this->getUnits() != '' ) {
			$this->Validator->isFloat(				'units',
															$this->getUnits(),
															TTi18n::gettext('Invalid Units')
														);
			if ( $this->Validator->isError('units') == FALSE ) {
				$this->Validator->isLength(				'units',
																$this->getUnits(),
																TTi18n::gettext('Units has too many digits'),
																0,
																21
															); //Need to include decimal
			}
			if ( $this->Validator->isError('units') == FALSE ) {
				$this->Validator->isLengthBeforeDecimal('units',
																$this->getUnits(),
																TTi18n::gettext('Units has too many digits before the decimal'),
																0,
																16
															);
			}
			if ( $this->Validator->isError('units') == FALSE ) {
				$this->Validator->isLengthAfterDecimal(	'units',
																$this->getUnits(),
																TTi18n::gettext('Units has too many digits after the decimal'),
																0,
																4
															);
			}
		}
		// Amount
		if ( $this->getGenericDataValue( 'amount' ) !== FALSE ) {
			$this->Validator->isFloat(				'amount',
															$this->getGenericDataValue( 'amount' ),
															TTi18n::gettext('Invalid Amount')
														);
			if ( $this->Validator->isError('amount') == FALSE ) {
				$this->Validator->isLength(				'amount',
																$this->getGenericDataValue( 'amount' ),
																TTi18n::gettext('Amount has too many digits'),
																0,
																21
															); //Need to include decimal
			}
			if ( $this->Validator->isError('amount') == FALSE ) {
				$this->Validator->isLengthBeforeDecimal('amount',
																$this->getGenericDataValue( 'amount' ),
																TTi18n::gettext('Amount has too many digits before the decimal'),
																0,
																16
															);
			}
			if ( $this->Validator->isError('amount') == FALSE ) {
				$this->Validator->isLengthAfterDecimal(	'amount',
																$this->getGenericDataValue( 'amount' ),
																TTi18n::gettext('Amount has too many digits after the decimal'),
																0,
																4
															);
			}
		}
		// Percent
		if ( $this->getPercentAmount() !== FALSE ) {
			$this->Validator->isFloat(				'percent_amount',
															$this->getPercentAmount(),
															TTi18n::gettext('Invalid Percent')
														);
		}
		// Percent Of
		if ( $this->getPercentAmountEntryNameId() != TTUUID::getZeroID() ) {
			$psealf = TTnew( 'PayStubEntryAccountListFactory' ); /** @var PayStubEntryAccountListFactory $psealf */
			$psealf->getById( $this->getPercentAmountEntryNameId() );
			//Not sure why we tried to use $result here, as if the ID passed is NULL, it causes a fatal error.
			//$result = $psealf->getById( $id )->getCurrent();

			$this->Validator->isResultSetWithRows(	'percent_amount_entry_name',
															$psealf,
															TTi18n::gettext('Invalid Percent Of')
														);
		}
		// Pay Stub Amendment Description
		if ( $this->getPayStubAmendmentDescription() != '' ) {
			$this->Validator->isLength(		'ps_amendment_description',
													$this->getPayStubAmendmentDescription(),
													TTi18n::gettext('Invalid Pay Stub Amendment Description Length'),
													2,
													100
												);
		}

		//
		// ABOVE: Validation code moved from set*() functions.
		//

		/*
		//If amount is set, make sure percent is cleared. The type defines this, so its not really needed.
		if ( $this->getAmount() != '' AND $this->getPercentAmount() != '' ) {
			$this->Validator->isTrue(		'amount',
											FALSE,
											TTi18n::gettext('Fixed Amount and Percent cannot both be entered'));
		}
		*/

		if ( $this->getType() == 10 ) {
			//If rate and units are set, and not amount, calculate the amount for us.
			if ( $this->getRate() !== NULL AND $this->getUnits() !== NULL AND $this->getAmount() == NULL ) {
				$this->preSave();
			}

			//Make sure amount is sane given the rate and units.
			if ( $this->getRate() !== NULL AND $this->getUnits() !== NULL
					AND $this->getRate() != 0 AND $this->getUnits() != 0
					AND $this->getRate() != '' AND $this->getUnits() != ''
					AND ( round( ( $this->getRate() * $this->getUnits() ), 2 ) ) != round( $this->getAmount(), 2) ) {
				Debug::text('Validate: Rate: '. $this->getRate() .' Units: '. $this->getUnits() .' Amount: '. $this->getAmount() .' Calc: Rate: '. $this->getRate() .' Units: '. $this->getUnits() .' Total: '. ( $this->getRate() * $this->getUnits() ), __FILE__, __LINE__, __METHOD__, 10);
				$this->Validator->isTrue(		'amount',
												FALSE,
												TTi18n::gettext('Invalid Amount, calculation is incorrect'));
			}
		}

		//Make sure rate * units = amount
		if ( $this->getPercentAmount() == NULL AND $this->getAmount() === NULL ) {
			$this->Validator->isTrue(		'amount',
											FALSE,
											TTi18n::gettext('Invalid Amount'));
		}

		return TRUE;
	}

	/**
	 * @return bool
	 */
	function preSave() {
		if ( $this->getFrequency() == 40 ) {
			if ( TTDate::getDayOfMonth( $this->getStartDate() ) > 28 ) {
				Debug::text(' Start Date is After the 28th, making the 28th: ', __FILE__, __LINE__, __METHOD__, 10);
				$this->setStartDate( TTDate::getDateOfNextDayOfMonth( $this->getStartDate(), strtotime('28-Feb-05') ) );
			}
		}

		if ( $this->getType() == 10 ) {
			//If amount isn't set, but Rate and units are, calc amount for them.
			if ( ( $this->getAmount() == NULL OR $this->getAmount() == 0 OR $this->getAmount() == '' )
					AND $this->getRate() !== NULL AND $this->getUnits() !== NULL
					AND $this->getRate() != 0 AND $this->getUnits() != 0
					AND $this->getRate() != '' AND $this->getUnits() != ''
					) {
				$this->setAmount( bcmul( $this->getRate(), $this->getUnits(), 4 ) );
			}
		}


		if ( $this->isNew() == TRUE ) {
			$this->first_insert = TRUE;
		}

		return TRUE;
	}

	function postSave() {
		if ( isset($this->first_insert) AND $this->first_insert == TRUE ) {
			Debug::text('First Insert... Creating PS amendments', __FILE__, __LINE__, __METHOD__, 10);
			//Immediately generate PS amendments
			$this->createPayStubAmendments();
		}
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
						case 'start_date':
						case 'end_date':
							if ( method_exists( $this, $function ) ) {
								$this->$function( TTDate::parseDateTime( $data[$key] ) );
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

			$this->setCreatedAndUpdatedColumns( $data );

			return TRUE;
		}

		return FALSE;
	}

	/**
	 * @param null $include_columns
	 * @return array
	 */
	function getObjectAsArray( $include_columns = NULL ) {

		$data = array();
		$variable_function_map = $this->getVariableToFunctionMap();
		if ( is_array( $variable_function_map ) ) {
			foreach( $variable_function_map as $variable => $function_stub ) {
				if ( $include_columns == NULL OR ( isset($include_columns[$variable]) AND $include_columns[$variable] == TRUE ) ) {

					$function = 'get'.$function_stub;
					switch( $variable ) {
						case 'pay_stub_entry_name':
							$data[$variable] = $this->getColumn( $variable );
							break;
						case 'status':
						case 'type':
						case 'frequency':
							$function = 'get'.$variable;
							if ( method_exists( $this, $function ) ) {
								$data[$variable] = Option::getByKey( $this->$function(), $this->getOptions( $variable ) );
							}
							break;
						case 'start_date':
						case 'end_date':
							if ( method_exists( $this, $function ) ) {
								$data[$variable] = TTDate::getAPIDate( 'DATE', $this->$function() );
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
			$this->getCreatedAndUpdatedColumns( $data, $include_columns );
		}

		return $data;
	}

	/**
	 * @param $log_action
	 * @return bool
	 */
	function addLog( $log_action ) {
		return TTLog::addEntry( $this->getId(), $log_action, TTi18n::getText('Recurring Pay Stub Amendment'), NULL, $this->getTable(), $this );
	}
}
?>
