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
 * @package API\Core
 */
class APIUserDateTotal extends APIFactory {
	protected $main_class = 'UserDateTotalFactory';

	/**
	 * APIUserDateTotal constructor.
	 */
	public function __construct() {
		parent::__construct(); //Make sure parent constructor is always called.

		return TRUE;
	}

	/**
	 * Get default user_date_total data for creating new user_date_totales.
	 * @param string $user_id UUID
	 * @param int $date EPOCH
	 * @return array
	 */
	function getUserDateTotalDefaultData( $user_id = NULL, $date = NULL ) {
		$company_obj = $this->getCurrentCompanyObject();

		Debug::Text('Getting user_date_total default data...', __FILE__, __LINE__, __METHOD__, 10);

		$data = array(
						'currency_id' => $this->getCurrentUserObject()->getCurrency(),
						'branch_id' => $this->getCurrentUserObject()->getDefaultBranch(),
						'department_id' => $this->getCurrentUserObject()->getDefaultDepartment(),
						'total_time' => 0,
						'base_hourly_rate' => 0,
						'hourly_rate' => 0,
						'override' => TRUE,
					);

		//If user_id is specified, use their default branch/department.
		$ulf = TTnew( 'UserListFactory' ); /** @var UserListFactory $ulf */
		$ulf->getByIdAndCompanyId( $user_id, $company_obj->getID() );
		if ( $ulf->getRecordCount() == 1 ) {
			$user_obj = $ulf->getCurrent();

			$data['user_id'] = $user_obj->getID();
			$data['branch_id'] = $user_obj->getDefaultBranch();
			$data['department_id'] = $user_obj->getDefaultDepartment();
			$data['job_id'] = $user_obj->getDefaultJob();
			$data['job_item_id'] = $user_obj->getDefaultJobItem();

			$uwlf = TTnew('UserWageListFactory'); /** @var UserWageListFactory $uwlf */
			$uwlf->getByUserIdAndGroupIDAndBeforeDate( $user_id, TTUUID::getZeroID(), TTDate::parseDateTime( $date ), 1 );
			if ( $uwlf->getRecordCount() > 0 ) {
				foreach( $uwlf as $uw_obj ) {
					$data['base_hourly_rate'] = $data['hourly_rate'] = $uw_obj->getHourlyRate();
				}
			}
			unset($uwlf, $uw_obj);
		}
		unset($ulf, $user_obj);

		Debug::Arr($data, 'Default data: ', __FILE__, __LINE__, __METHOD__, 10);
		return $this->returnHandler( $data );
	}

	/**
	 * Get combined recurring user_date_total and committed user_date_total data for one or more user_date_totales.
	 * @param array $data filter data
	 * @return array|bool
	 */
	function getCombinedUserDateTotal( $data = NULL ) {
		if ( !$this->getPermissionObject()->Check('punch', 'enabled')
				OR !( $this->getPermissionObject()->Check('punch', 'view') OR $this->getPermissionObject()->Check('punch', 'view_child')  ) ) {
			return $this->getPermissionObject()->PermissionDenied();
		}

		$data = $this->initializeFilterAndPager( $data );

		$sf = TTnew( 'UserDateTotalFactory' ); /** @var UserDateTotalFactory $sf */
		$retarr = $sf->getUserDateTotalArray( $data['filter_data'] );

		Debug::Arr($retarr, 'RetArr: ', __FILE__, __LINE__, __METHOD__, 10);

		return $this->returnHandler( $retarr );
	}


	/**
	 * Get user_date_total data for one or more user_date_totales.
	 * @param array $data filter data
	 * @param bool $disable_paging
	 * @return array|bool
	 */
	function getUserDateTotal( $data = NULL, $disable_paging = FALSE ) {
		$data = $this->initializeFilterAndPager( $data );

		//Regular employees with permissions to edit their own absences need this.
		if ( !$this->getPermissionObject()->Check('punch', 'enabled')
				OR !( $this->getPermissionObject()->Check('punch', 'view') OR $this->getPermissionObject()->Check('punch', 'view_own') OR $this->getPermissionObject()->Check('punch', 'view_child') ) ) {
			return $this->getPermissionObject()->PermissionDenied();
		}

		//Parse date string sent by HTML5 interface for searching.
		if ( isset($data['filter_data']['date_stamp']) ) {
			$data['filter_data']['date_stamp'] = TTDate::getMiddleDayEpoch( TTDate::parseDateTime( $data['filter_data']['date_stamp'] ) );
		}

		if ( isset($data['filter_data']['start_date']) ) {
			$data['filter_data']['start_date'] = TTDate::parseDateTime( $data['filter_data']['start_date'] );
		}

		if ( isset($data['filter_data']['end_date']) ) {
			$data['filter_data']['end_date'] = TTDate::parseDateTime( $data['filter_data']['end_date'] );
		}

		//This can be used to edit Absences as well, how do we differentiate between them?
		$data['filter_data']['permission_children_ids'] = $this->getPermissionObject()->getPermissionChildren( 'punch', 'view' );

		$blf = TTnew( 'UserDateTotalListFactory' ); /** @var UserDateTotalListFactory $blf */
		$blf->getAPISearchByCompanyIdAndArrayCriteria( $this->getCurrentCompanyObject()->getId(), $data['filter_data'], $data['filter_items_per_page'], $data['filter_page'], NULL, $data['filter_sort'] );
		Debug::Text('Record Count: '. $blf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
		if ( $blf->getRecordCount() > 0 ) {
			$this->getProgressBarObject()->start( $this->getAMFMessageID(), $blf->getRecordCount() );

			$this->setPagerObject( $blf );

			$retarr = array();
			foreach( $blf as $b_obj ) {
				$retarr[] = $b_obj->getObjectAsArray( $data['filter_columns'], $data['filter_data']['permission_children_ids'] );

				$this->getProgressBarObject()->set( $this->getAMFMessageID(), $blf->getCurrentRow() );
			}

			$this->getProgressBarObject()->stop( $this->getAMFMessageID() );

			return $this->returnHandler( $retarr );
		}

		return $this->returnHandler( TRUE ); //No records returned.
	}

	/**
	 * Get only the fields that are common across all records in the search criteria. Used for Mass Editing of records.
	 * @param array $data filter data
	 * @return array
	 */
	function getCommonUserDateTotalData( $data ) {
		return Misc::arrayIntersectByRow( $this->stripReturnHandler( $this->getUserDateTotal( $data, TRUE ) ) );
	}

	/**
	 * Validate user_date_total data for one or more user_date_totales.
	 * @param array $data user_date_total data
	 * @return array
	 */
	function validateUserDateTotal( $data ) {
		return $this->setUserDateTotal( $data, TRUE );
	}

	/**
	 * Set user_date_total data for one or more user_date_totales.
	 * @param array $data user_date_total data
	 * @param bool $validate_only
	 * @param bool $ignore_warning
	 * @return array|bool
	 */
	function setUserDateTotal( $data, $validate_only = FALSE, $ignore_warning = TRUE ) {
		$validate_only = (bool)$validate_only;
		$ignore_warning = (bool)$ignore_warning;

		if ( !is_array($data) ) {
			return $this->returnHandler( FALSE );
		}

		if ( !( $this->getPermissionObject()->Check('punch', 'enabled') OR $this->getPermissionObject()->Check('absence', 'enabled') )
				OR !( $this->getPermissionObject()->Check('punch', 'edit') OR $this->getPermissionObject()->Check('punch', 'edit_own') OR $this->getPermissionObject()->Check('punch', 'edit_child') OR $this->getPermissionObject()->Check('punch', 'add') )
				OR !( $this->getPermissionObject()->Check('absence', 'edit') OR $this->getPermissionObject()->Check('absence', 'edit_own') OR $this->getPermissionObject()->Check('absence', 'edit_child') OR $this->getPermissionObject()->Check('absence', 'add') )
				) {
			return	$this->getPermissionObject()->PermissionDenied();
		}

		if ( $validate_only == TRUE ) {
			Debug::Text('Validating Only!', __FILE__, __LINE__, __METHOD__, 10);
			$permission_children_ids = FALSE;
		} else {
			//Get Permission Hierarchy Children first, as this can be used for viewing, or editing.
			$permission_children_ids = $this->getPermissionChildren();
		}

		list( $data, $total_records ) = $this->convertToMultipleRecords( $data );
		Debug::Text('Received data for: '. $total_records .' UserDateTotals', __FILE__, __LINE__, __METHOD__, 10);
		Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

		$validator_stats = array('total_records' => $total_records, 'valid_records' => 0 );
		$validator = $save_result = $key = FALSE;
		if ( is_array($data) AND $total_records > 0 ) {
			$this->getProgressBarObject()->start( $this->getAMFMessageID(), $total_records );

			$transaction_function = function() use ( $data, $validate_only, $ignore_warning, $validator_stats, $validator, $save_result, $key, $permission_children_ids ) {
				$lf = TTnew( 'UserDateTotalListFactory' ); /** @var UserDateTotalListFactory $lf */
				if ( $validate_only	== FALSE ) { //Only switch into serializable mode when actually saving the record.
					$lf->setTransactionMode( 'REPEATABLE READ' ); //Required to help prevent duplicate simulataneous HTTP requests from causing incorrect calculations in user_date_total table.
				}
				$lf->StartTransaction(); //Wrap entire batch in the transaction.

				$recalculate_user_date_stamp = FALSE;
				foreach ( $data as $key => $row ) {
					$primary_validator = new Validator();
					$lf = TTnew( 'UserDateTotalListFactory' ); /** @var UserDateTotalListFactory $lf */
					//$lf->StartTransaction();
					if ( isset( $row['id'] ) AND $row['id'] != '' ) {
						//Modifying existing object.
						//Get user_date_total object, so we can only modify just changed data for specific records if needed.
						$lf->getByIdAndCompanyId( $row['id'], $this->getCurrentCompanyObject()->getId() );
						if ( $lf->getRecordCount() == 1 ) {
							//Object exists, check edit permissions
							if (
									$validate_only == TRUE
									OR
									(
											$this->getPermissionObject()->Check( 'punch', 'edit' )
											OR ( $this->getPermissionObject()->Check( 'punch', 'edit_own' ) AND $this->getPermissionObject()->isOwner( $lf->getCurrent()->getCreatedBy(), $lf->getCurrent()->getUser() ) === TRUE )
											OR ( $this->getPermissionObject()->Check( 'punch', 'edit_child' ) AND $this->getPermissionObject()->isChild( $lf->getCurrent()->getUser(), $permission_children_ids ) === TRUE )
									)
									OR
									(
											$this->getPermissionObject()->Check( 'absence', 'edit' )
											OR ( $this->getPermissionObject()->Check( 'absence', 'edit_own' ) AND $this->getPermissionObject()->isOwner( $lf->getCurrent()->getCreatedBy(), $lf->getCurrent()->getUser() ) === TRUE )
											OR ( $this->getPermissionObject()->Check( 'absence', 'edit_child' ) AND $this->getPermissionObject()->isChild( $lf->getCurrent()->getUser(), $permission_children_ids ) === TRUE )
									)
							) {

								Debug::Text( 'Row Exists, getting current data for ID: ' . $row['id'], __FILE__, __LINE__, __METHOD__, 10 );
								$lf = $lf->getCurrent();

								//When editing a record if the date changes, we need to recalculate the old date.
								//This must occur before we merge the data together.
								if ( ( isset( $row['user_id'] )
												AND $lf->getUser() != $row['user_id'] )
										OR
										( isset( $row['date_stamp'] )
												AND $lf->getDateStamp()
												AND TTDate::parseDateTime( $row['date_stamp'] ) != $lf->getDateStamp() )
								) {
									Debug::Text( 'Date has changed, recalculate old date... New: [ Date: ' . $row['date_stamp'] . ' ] UserID: ' . $lf->getUser(), __FILE__, __LINE__, __METHOD__, 10 );
									$recalculate_user_date_stamp[ $lf->getUser() ][] = TTDate::getMiddleDayEpoch( $lf->getDateStamp() ); //Help avoid confusion with different timezones/DST.
								}

								//Since switching to batch calculation mode, need to store every possible date to recalculate.
								if ( isset( $row['user_id'] ) AND $row['user_id'] != '' AND isset( $row['date_stamp'] ) AND $row['date_stamp'] != '' ) {
									//Since switching to batch calculation mode, need to store every possible date to recalculate.
									$recalculate_user_date_stamp[ TTUUID::castUUID( $row['user_id'] ) ][] = TTDate::getMiddleDayEpoch( TTDate::parseDateTime( $row['date_stamp'] ) ); //Help avoid confusion with different timezones/DST.
								}
								$recalculate_user_date_stamp[ $lf->getUser() ][] = TTDate::getMiddleDayEpoch( $lf->getDateStamp() ); //Help avoid confusion with different timezones/DST.

								$row = array_merge( $lf->getObjectAsArray(), $row );
							} else {
								$primary_validator->isTrue( 'permission', FALSE, TTi18n::gettext( 'Edit permission denied' ) );
							}
						} else {
							//Object doesn't exist.
							$primary_validator->isTrue( 'id', FALSE, TTi18n::gettext( 'Edit permission denied, record does not exist' ) );
						}
					} else {
						//Adding new object, check ADD permissions.
						if ( !( $validate_only == TRUE
								OR
								( $this->getPermissionObject()->Check( 'punch', 'add' )
										AND
										(
												$this->getPermissionObject()->Check( 'punch', 'edit' )
												OR ( isset( $row['user_id'] ) AND $this->getPermissionObject()->Check( 'punch', 'edit_own' ) AND $this->getPermissionObject()->isOwner( FALSE, $row['user_id'] ) === TRUE ) //We don't know the created_by of the user at this point, but only check if the user is assigned to the logged in person.
												OR ( isset( $row['user_id'] ) AND $this->getPermissionObject()->Check( 'punch', 'edit_child' ) AND $this->getPermissionObject()->isChild( $row['user_id'], $permission_children_ids ) === TRUE )
										)
								)
								OR
								( $this->getPermissionObject()->Check( 'absence', 'add' )
										AND
										(
												$this->getPermissionObject()->Check( 'absence', 'edit' )
												OR ( isset( $row['user_id'] ) AND $this->getPermissionObject()->Check( 'absence', 'edit_own' ) AND $this->getPermissionObject()->isOwner( FALSE, $row['user_id'] ) === TRUE ) //We don't know the created_by of the user at this point, but only check if the user is assigned to the logged in person.
												OR ( isset( $row['user_id'] ) AND $this->getPermissionObject()->Check( 'absence', 'edit_child' ) AND $this->getPermissionObject()->isChild( $row['user_id'], $permission_children_ids ) === TRUE )
										)
								)
						) ) {
							$primary_validator->isTrue( 'permission', FALSE, TTi18n::gettext( 'Add permission denied' ) );
						} else {
							if ( isset( $row['user_id'] ) AND $row['user_id'] != '' AND isset( $row['date_stamp'] ) AND $row['date_stamp'] != '' ) {
								//Since switching to batch calculation mode, need to store every possible date to recalculate.
								$recalculate_user_date_stamp[ TTUUID::castUUID( $row['user_id'] ) ][] = TTDate::getMiddleDayEpoch( TTDate::parseDateTime( $row['date_stamp'] ) ); //Help avoid confusion with different timezones/DST.
							}
						}
					}
					Debug::Arr( $row, 'Data: ', __FILE__, __LINE__, __METHOD__, 10 );

					$is_valid = $primary_validator->isValid( $ignore_warning );
					if ( $is_valid == TRUE ) { //Check to see if all permission checks passed before trying to save data.
						Debug::Text( 'Setting object data...', __FILE__, __LINE__, __METHOD__, 10 );

						//If the currently logged in user is timezone GMT, and he edits an absence for a user in timezone PST
						//it can cause confusion as to which date needs to be recalculated, the GMT or PST date?
						//Try to avoid this by using getMiddleDayEpoch() as much as possible.
						$lf->setObjectFromArray( $row );
						$lf->Validator->setValidateOnly( $validate_only );

						$is_valid = $lf->isValid( $ignore_warning );
						if ( $is_valid == TRUE ) {
							Debug::Text( 'Saving data...', __FILE__, __LINE__, __METHOD__, 10 );
							if ( $validate_only == TRUE ) {
								$save_result[ $key ] = TRUE;
							} else {
								$lf->setEnableTimeSheetVerificationCheck( TRUE ); //Unverify timesheet if its already verified.

								//Before batch calculation mode was enabled...
								//$lf->setEnableCalcSystemTotalTime( TRUE );
								//$lf->setEnableCalcWeeklySystemTotalTime( TRUE );
								//$lf->setEnableCalcException( TRUE );
								$lf->setEnableCalcSystemTotalTime( FALSE );
								$lf->setEnableCalcWeeklySystemTotalTime( FALSE );
								$lf->setEnableCalcException( FALSE );

								$save_result[ $key ] = $lf->Save();
							}
							$validator_stats['valid_records']++;
						}
					}

					if ( $is_valid == FALSE ) {
						Debug::Text( 'Data is Invalid...', __FILE__, __LINE__, __METHOD__, 10 );

						$lf->FailTransaction(); //Just rollback this single record, continue on to the rest.

						$validator[ $key ] = $this->setValidationArray( $primary_validator, $lf );
					} elseif ( $validate_only == TRUE ) {
						$lf->FailTransaction();
					}
					//$lf->CommitTransaction();

					$this->getProgressBarObject()->set( $this->getAMFMessageID(), $key );
				}

				if ( $is_valid == TRUE AND $validate_only == FALSE ) {
					if ( is_array( $recalculate_user_date_stamp ) AND count( $recalculate_user_date_stamp ) > 0 ) {
						Debug::Arr( $recalculate_user_date_stamp, 'Recalculating other dates...', __FILE__, __LINE__, __METHOD__, 10 );
						foreach ( $recalculate_user_date_stamp as $user_id => $date_arr ) {
							$ulf = TTNew( 'UserListFactory' ); /** @var UserListFactory $ulf */
							$ulf->getByIdAndCompanyId( $user_id, $this->getCurrentCompanyObject()->getId() );
							if ( $ulf->getRecordCount() > 0 ) {
								$cp = TTNew( 'CalculatePolicy' ); /** @var CalculatePolicy $cp */
								$cp->setUserObject( $ulf->getCurrent() );
								$cp->addPendingCalculationDate( $date_arr );
								$cp->calculate(); //This sets timezone itself.
								$cp->Save();
							}
						}
					} else {
						Debug::Text( 'aNot recalculating batch...', __FILE__, __LINE__, __METHOD__, 10 );
					}
				} else {
					Debug::Text( 'bNot recalculating batch...', __FILE__, __LINE__, __METHOD__, 10 );
				}

				$lf->CommitTransaction();
				$lf->setTransactionMode(); //Back to default isolation level.

				return array( $validator, $validator_stats, $key, $save_result );
			};

			$this->getProgressBarObject()->stop( $this->getAMFMessageID() );

			if ( $total_records > 100 ) { //When importing, or mass adding punches, don't retry as the transaction will be much too large.
				$retry_max_attempts = 1;
			} elseif ( $total_records > 20 ) { //When importing, or mass adding punches, don't retry as the transaction will be much too large.
				$retry_max_attempts = 2;
			} else {
				$retry_max_attempts = 3;
			}

			list( $validator, $validator_stats, $key, $save_result ) = $this->RetryTransaction( $transaction_function, $retry_max_attempts );

			return $this->handleRecordValidationResults( $validator, $validator_stats, $key, $save_result );
		}

		return $this->returnHandler( FALSE );
	}

	/**
	 * Delete one or more user_date_totals.
	 * @param array $data user_date_total data
	 * @return array|bool
	 */
	function deleteUserDateTotal( $data ) {
		if ( !is_array($data) ) {
			$data = array($data);
		}

		if ( !is_array($data) ) {
			return $this->returnHandler( FALSE );
		}

		if ( !( $this->getPermissionObject()->Check('punch', 'enabled') OR $this->getPermissionObject()->Check('absence', 'enabled') )
				OR !( $this->getPermissionObject()->Check('punch', 'edit') OR $this->getPermissionObject()->Check('punch', 'edit_own') OR $this->getPermissionObject()->Check('punch', 'edit_child') OR $this->getPermissionObject()->Check('punch', 'add') )
				OR !( $this->getPermissionObject()->Check('absence', 'edit') OR $this->getPermissionObject()->Check('absence', 'edit_own') OR $this->getPermissionObject()->Check('absence', 'edit_child') OR $this->getPermissionObject()->Check('absence', 'add') )
				) {
			return	$this->getPermissionObject()->PermissionDenied();
		}

		//Get Permission Hierarchy Children first, as this can be used for viewing, or editing.
		$permission_children_ids = $this->getPermissionChildren();

		Debug::Text('Received data for: '. count($data) .' UserDateTotals', __FILE__, __LINE__, __METHOD__, 10);
		Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

		$total_records = count($data);
		$validator = $save_result = $key = FALSE;
		$validator_stats = array('total_records' => $total_records, 'valid_records' => 0 );
		if ( is_array($data) AND $total_records > 0 ) {
			$this->getProgressBarObject()->start( $this->getAMFMessageID(), $total_records );

			$transaction_function = function() use ( $data, $validator_stats, $validator, $save_result, $permission_children_ids ) {
				$lf = TTnew( 'UserDateTotalListFactory' ); /** @var UserDateTotalListFactory $lf */
				$lf->setTransactionMode( 'REPEATABLE READ' ); //Required to help prevent duplicate simulataneous HTTP requests from causing incorrect calculations in user_date_total table.
				$lf->StartTransaction();

				$recalculate_user_date_stamp = FALSE;
				foreach ( $data as $key => $id ) {
					$primary_validator = new Validator();
					$lf = TTnew( 'UserDateTotalListFactory' ); /** @var UserDateTotalListFactory $lf */
					//$lf->StartTransaction();
					if ( $id != '' ) {
						//Modifying existing object.
						//Get user_date_total object, so we can only modify just changed data for specific records if needed.
						$lf->getByIdAndCompanyId( $id, $this->getCurrentCompanyObject()->getId() );
						if ( $lf->getRecordCount() == 1 ) {
							//Object exists, check edit permissions
							if ( (
											$this->getPermissionObject()->Check( 'punch', 'delete' )
											OR ( $this->getPermissionObject()->Check( 'punch', 'delete_own' ) AND $this->getPermissionObject()->isOwner( $lf->getCurrent()->getCreatedBy(), $lf->getCurrent()->getUser() ) === TRUE )
											OR ( $this->getPermissionObject()->Check( 'punch', 'delete_child' ) AND $this->getPermissionObject()->isChild( $lf->getCurrent()->getUser(), $permission_children_ids ) === TRUE )
									)
									OR
									(
											$this->getPermissionObject()->Check( 'absence', 'delete' )
											OR ( $this->getPermissionObject()->Check( 'absence', 'delete_own' ) AND $this->getPermissionObject()->isOwner( $lf->getCurrent()->getCreatedBy(), $lf->getCurrent()->getUser() ) === TRUE )
											OR ( $this->getPermissionObject()->Check( 'absence', 'delete_child' ) AND $this->getPermissionObject()->isChild( $lf->getCurrent()->getUser(), $permission_children_ids ) === TRUE )
									)
							) {
								Debug::Text( 'Record Exists, deleting record: ' . $id, __FILE__, __LINE__, __METHOD__, 10 );
								$lf = $lf->getCurrent();

								$recalculate_user_date_stamp[ $lf->getUser() ][] = TTDate::getMiddleDayEpoch( $lf->getDateStamp() ); //Help avoid confusion with different timezones/DST.
							} else {
								$primary_validator->isTrue( 'permission', FALSE, TTi18n::gettext( 'Delete permission denied' ) );
							}
						} else {
							//Object doesn't exist.
							$primary_validator->isTrue( 'id', FALSE, TTi18n::gettext( 'Delete permission denied, record does not exist' ) );
						}
					} else {
						$primary_validator->isTrue( 'id', FALSE, TTi18n::gettext( 'Delete permission denied, record does not exist' ) );
					}

					//Debug::Arr($lf, 'AData: ', __FILE__, __LINE__, __METHOD__, 10);

					//Prevent user from deleting records that haven't been overridden already. For example records created by punches and not the manual timesheet.
					//  Just in case the manual timesheet UI for some reason shows them the delete (minus) icon on the wrong row when it shouldn't.
					if ( $lf->getOverride() == FALSE ) {
						Debug::Text( 'Skip deleting UDT record that isnt already overridden. Object Type: '. $lf->getObjectType() .' ID: '. $lf->getId(), __FILE__, __LINE__, __METHOD__, 10 );
						if ( $lf->getObjectType() == 50 ) {
							$primary_validator->isTrue( 'override', FALSE, TTi18n::gettext( 'Unable to delete absences that originated from the schedule. Instead delete scheduled shift or edit this absence and set the total time to 0' ) );
						} else {
							$primary_validator->isTrue( 'override', FALSE, TTi18n::gettext( 'Unable to delete system records. Instead edit the record and set the total time to 0' ) );
						}
					}

					$is_valid = $primary_validator->isValid();
					if ( $is_valid == TRUE ) { //Check to see if all permission checks passed before trying to save data.
						Debug::Text( 'Attempting to delete record...', __FILE__, __LINE__, __METHOD__, 10 );
						$lf->setDeleted( TRUE );

						$is_valid = $lf->isValid();
						if ( $is_valid == TRUE ) {
							Debug::Text( 'Record Deleted...', __FILE__, __LINE__, __METHOD__, 10 );
							$lf->setEnableTimeSheetVerificationCheck( TRUE ); //Unverify timesheet if its already verified.

							//Before batch calculation mode was enabled...
							//$lf->setEnableCalcSystemTotalTime( TRUE );
							//$lf->setEnableCalcWeeklySystemTotalTime( TRUE );
							//$lf->setEnableCalcException( TRUE );
							$lf->setEnableCalcSystemTotalTime( FALSE );
							$lf->setEnableCalcWeeklySystemTotalTime( FALSE );
							$lf->setEnableCalcException( FALSE );

							$save_result[ $key ] = $lf->Save();
							$validator_stats['valid_records']++;
						}
					}

					if ( $is_valid == FALSE ) {
						Debug::Text( 'Data is Invalid...', __FILE__, __LINE__, __METHOD__, 10 );

						$lf->FailTransaction(); //Just rollback this single record, continue on to the rest.

						$validator[ $key ] = $this->setValidationArray( $primary_validator, $lf );
					}

					//$lf->CommitTransaction();

					$this->getProgressBarObject()->set( $this->getAMFMessageID(), $key );
				}

				if ( $is_valid == TRUE ) {
					if ( is_array( $recalculate_user_date_stamp ) AND count( $recalculate_user_date_stamp ) > 0 ) {
						Debug::Arr( $recalculate_user_date_stamp, 'Recalculating other dates...', __FILE__, __LINE__, __METHOD__, 10 );
						foreach ( $recalculate_user_date_stamp as $user_id => $date_arr ) {
							$ulf = TTNew( 'UserListFactory' ); /** @var UserListFactory $ulf */
							$ulf->getByIdAndCompanyId( $user_id, $this->getCurrentCompanyObject()->getId() );
							if ( $ulf->getRecordCount() > 0 ) {
								$cp = TTNew( 'CalculatePolicy' ); /** @var CalculatePolicy $cp */
								$cp->setUserObject( $ulf->getCurrent() );
								$cp->addPendingCalculationDate( $date_arr );
								$cp->calculate(); //This sets timezone itself.
								$cp->Save();
							}
						}
					} else {
						Debug::Text( 'aNot recalculating batch...', __FILE__, __LINE__, __METHOD__, 10 );
					}
				} else {
					Debug::Text( 'bNot recalculating batch...', __FILE__, __LINE__, __METHOD__, 10 );
				}

				$lf->CommitTransaction();
				$lf->setTransactionMode(); //Back to default isolation level.

				return array( $validator, $validator_stats, $key, $save_result );
			};

			$this->getProgressBarObject()->stop( $this->getAMFMessageID() );

			list( $validator, $validator_stats, $key, $save_result ) = $this->RetryTransaction( $transaction_function );

			return $this->handleRecordValidationResults( $validator, $validator_stats, $key, $save_result );
		}

		return $this->returnHandler( FALSE );
	}

	/**
	 * Copy one or more user_date_totales.
	 * @param array $data user_date_total IDs
	 * @return array
	 */
	function copyUserDateTotal( $data ) {
		if ( !is_array($data) ) {
			$data = array($data);
		}

		if ( !is_array($data) ) {
			return $this->returnHandler( FALSE );
		}

		Debug::Text('Received data for: '. count($data) .' UserDateTotals', __FILE__, __LINE__, __METHOD__, 10);
		Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

		$src_rows = $this->stripReturnHandler( $this->getUserDateTotal( array('filter_data' => array('id' => $data) ), TRUE ) );
		if ( is_array( $src_rows ) AND count($src_rows) > 0 ) {
			Debug::Arr($src_rows, 'SRC Rows: ', __FILE__, __LINE__, __METHOD__, 10);
			foreach( $src_rows as $key => $row ) {
				unset($src_rows[$key]['id'], $src_rows[$key]['manual_id'] ); //Clear fields that can't be copied
				$src_rows[$key]['name'] = Misc::generateCopyName( $row['name'] ); //Generate unique name
			}
			//Debug::Arr($src_rows, 'bSRC Rows: ', __FILE__, __LINE__, __METHOD__, 10);

			return $this->setUserDateTotal( $src_rows ); //Save copied rows
		}

		return $this->returnHandler( FALSE );
	}

	/**
	 * @param $data
	 * @param bool $disable_paging
	 * @return array|bool
	 */
	function getAccumulatedUserDateTotal( $data, $disable_paging = FALSE  ) {
		return UserDateTotalFactory::calcAccumulatedTime( $this->getUserDateTotal( $data, TRUE ) );
	}

	/**
	 * @param $data
	 * @param bool $disable_paging
	 * @return bool
	 */
	function getTotalAccumulatedUserDateTotal( $data, $disable_paging = FALSE ) {
		$retarr = UserDateTotalFactory::calcAccumulatedTime( $this->getUserDateTotal( $data, TRUE ) );
		if ( isset($retarr['total']) ) {
			return $retarr['total'];
		}

		return FALSE;
	}

}
?>
