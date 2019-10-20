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
 * @package API\PayPeriod
 */
class APIPayPeriodSchedule extends APIFactory {
	protected $main_class = 'PayPeriodScheduleFactory';

	/**
	 * APIPayPeriodSchedule constructor.
	 */
	public function __construct() {
		parent::__construct(); //Make sure parent constructor is always called.

		return TRUE;
	}

	/**
	 * Get options for dropdown boxes.
	 * @param bool|string $name Name of options to return, ie: 'columns', 'type', 'status'
	 * @param mixed $parent Parent name/ID of options to return if data is in hierarchical format. (ie: Province)
	 * @return bool|array
	 */
	function getOptions( $name = FALSE, $parent = NULL ) {
		if ( $name == 'columns'
				AND ( !$this->getPermissionObject()->Check('pay_period_schedule', 'enabled')
					OR !( $this->getPermissionObject()->Check('pay_period_schedule', 'view') OR $this->getPermissionObject()->Check('pay_period_schedule', 'view_own') OR $this->getPermissionObject()->Check('pay_period_schedule', 'view_child') ) ) ) {
			$name = 'list_columns';
		}

		return parent::getOptions( $name, $parent );
	}

	/**
	 * Get default data for creating pay period schedules.
	 * @return array
	 */
	function getPayPeriodScheduleDefaultData() {
		Debug::Text('Getting user default data...', __FILE__, __LINE__, __METHOD__, 10);

		$data = array(
						'company_id' => $this->getCurrentCompanyObject()->getId(),
						'anchor_date' => TTDate::getAPIDate( 'DATE', TTDate::getBeginMonthEpoch( time() ) ),
						'shift_assigned_day_id' => 10,
						'day_start_time' => 0,
						'new_day_trigger_time' => (3600 * 4),
						'maximum_shift_time' => (3600 * 16),
						'time_zone' => $this->getCurrentUserPreferenceObject()->getTimeZone(),
						'type_id' => 20,
						'start_week_day_id' => 0,
						'start_day_of_week' => 0,
						'timesheet_verify_type_id' => 10, //Disabled
						'timesheet_verify_before_end_date' => 0,
						'timesheet_verify_before_transaction_date' => 0,
					);

		Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

		return $this->returnHandler( $data );
	}

	/**
	 * Get user data for one or more users.
	 * @param array $data filter data
	 * @param bool $disable_paging
	 * @return array
	 */
	function getPayPeriodSchedule( $data = NULL, $disable_paging = FALSE ) {

		$data = $this->initializeFilterAndPager( $data, $disable_paging );

		if ( !$this->getPermissionObject()->Check('pay_period_schedule', 'enabled')
				OR !( $this->getPermissionObject()->Check('pay_period_schedule', 'view') OR $this->getPermissionObject()->Check('pay_period_schedule', 'view_own') OR $this->getPermissionObject()->Check('pay_period_schedule', 'view_child')	) ) {
			//return $this->getPermissionObject()->PermissionDenied();
			$data['filter_columns'] = $this->handlePermissionFilterColumns( (isset($data['filter_columns'])) ? $data['filter_columns'] : NULL, Misc::trimSortPrefix( $this->getOptions('list_columns') ) );
		}

		$data['filter_data']['permission_children_ids'] = $this->getPermissionObject()->getPermissionChildren( 'pay_period_schedule', 'view' );

		//Allow getting users from other companies, so we can change admin contacts when using the master company.
		if ( isset($data['filter_data']['company_id'])
				AND TTUUID::isUUID( $data['filter_data']['company_id'] ) AND $data['filter_data']['company_id'] != TTUUID::getZeroID() AND $data['filter_data']['company_id'] != TTUUID::getNotExistID()
				AND ( $this->getPermissionObject()->Check('company', 'enabled') AND $this->getPermissionObject()->Check('company', 'view') ) ) {
			$company_id = $data['filter_data']['company_id'];
		} else {
			$company_id = $this->getCurrentCompanyObject()->getId();
		}

		$ulf = TTnew( 'PayPeriodScheduleListFactory' ); /** @var PayPeriodScheduleListFactory $ulf */
		$ulf->getAPISearchByCompanyIdAndArrayCriteria( $company_id, $data['filter_data'], $data['filter_items_per_page'], $data['filter_page'], NULL, $data['filter_sort'] );
		Debug::Text('Record Count: '. $ulf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
		if ( $ulf->getRecordCount() > 0 ) {
			$this->setPagerObject( $ulf );

			$retarr = array();
			foreach( $ulf as $u_obj ) {
				$retarr[] = $u_obj->getObjectAsArray( $data['filter_columns'] );
			}

			return $this->returnHandler( $retarr );
		}

		return $this->returnHandler( TRUE ); //No records returned.
	}

	/**
	 * Export data to csv
	 * @param string $format file format (csv)
	 * @param array $data filter data
	 * @param bool $disable_paging
	 * @return array
	 */
	function exportPayPeriodSchedule( $format = 'csv', $data = NULL, $disable_paging = TRUE) {
		$result = $this->stripReturnHandler( $this->getPayPeriodSchedule( $data, $disable_paging ) );
		return $this->exportRecords( $format, 'export_pay_period_schedule', $result, ( ( isset($data['filter_columns']) ) ? $data['filter_columns'] : NULL ) );
	}

	/**
	 * Get only the fields that are common across all records in the search criteria. Used for Mass Editing of records.
	 * @param array $data filter data
	 * @return array
	 */
	function getCommonPayPeriodScheduleData( $data ) {
		return Misc::arrayIntersectByRow( $this->stripReturnHandler( $this->getPayPeriodSchedule( $data, TRUE ) ) );
	}

	/**
	 * Validate user data for one or more users.
	 * @param array $data user data
	 * @return array
	 */
	function validatePayPeriodSchedule( $data ) {
		return $this->setPayPeriodSchedule( $data, TRUE );
	}

	/**
	 * Set user data for one or more users.
	 * @param array $data user data
	 * @param bool $validate_only
	 * @param bool $ignore_warning
	 * @return array|bool
	 */
	function setPayPeriodSchedule( $data, $validate_only = FALSE, $ignore_warning = TRUE ) {
		$validate_only = (bool)$validate_only;
		$ignore_warning = (bool)$ignore_warning;

		if ( !is_array($data) ) {
			return $this->returnHandler( FALSE );
		}

		if ( !$this->getPermissionObject()->Check('pay_period_schedule', 'enabled')
				OR !( $this->getPermissionObject()->Check('pay_period_schedule', 'edit') OR $this->getPermissionObject()->Check('pay_period_schedule', 'edit_own') OR $this->getPermissionObject()->Check('pay_period_schedule', 'edit_child') OR $this->getPermissionObject()->Check('pay_period_schedule', 'add') ) ) {
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
		Debug::Text('Received data for: '. $total_records .' PayPeriodSchedules', __FILE__, __LINE__, __METHOD__, 10);
		Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

		$validator_stats = array('total_records' => $total_records, 'valid_records' => 0 );
		$validator = $save_result = $key = FALSE;
		if ( is_array($data) AND $total_records > 0 ) {
			foreach( $data as $key => $row ) {
				$primary_validator = new Validator();
				$lf = TTnew( 'PayPeriodScheduleListFactory' ); /** @var PayPeriodScheduleListFactory $lf */
				$lf->StartTransaction();
				if ( isset($row['id']) AND $row['id'] != '' ) {
					//Modifying existing object.
					//Get user object, so we can only modify just changed data for specific records if needed.
					$lf->getByIdAndCompanyId( $row['id'], $this->getCurrentCompanyObject()->getId() );
					if ( $lf->getRecordCount() == 1 ) {
						//Object exists, check edit permissions
						//Debug::Text('PayPeriodSchedule ID: '. $row['id'] .' Created By: '. $lf->getCurrent()->getCreatedBy() .' Is Owner: '. (int)$this->getPermissionObject()->isOwner( $lf->getCurrent()->getCreatedBy(), $lf->getCurrent()->getID() ) .' Is Child: '. (int)$this->getPermissionObject()->isChild( $lf->getCurrent()->getId(), $permission_children_ids ), __FILE__, __LINE__, __METHOD__, 10);
						if (
							$validate_only == TRUE
							OR
								(
								$this->getPermissionObject()->Check('pay_period_schedule', 'edit')
									OR ( $this->getPermissionObject()->Check('pay_period_schedule', 'edit_own') AND $this->getPermissionObject()->isOwner( $lf->getCurrent()->getCreatedBy(), $lf->getCurrent()->getID() ) === TRUE )
									OR ( $this->getPermissionObject()->Check('pay_period_schedule', 'edit_child') AND $this->getPermissionObject()->isChild( $lf->getCurrent()->getId(), $permission_children_ids ) === TRUE )
								) ) {

							Debug::Text('Row Exists, getting current data for ID: '. $row['id'], __FILE__, __LINE__, __METHOD__, 10);
							$lf = $lf->getCurrent();
							$row = array_merge( $lf->getObjectAsArray(), $row );
						} else {
							$primary_validator->isTrue( 'permission', FALSE, TTi18n::gettext('Edit permission denied') );
						}
					} else {
						//Object doesn't exist.
						$primary_validator->isTrue( 'id', FALSE, TTi18n::gettext('Edit permission denied, record does not exist') );
					}
				} else {
					//Adding new object, check ADD permissions.
					$primary_validator->isTrue( 'permission', $this->getPermissionObject()->Check('pay_period_schedule', 'add'), TTi18n::gettext('Add permission denied') );

					//Because this class has sub-classes that depend on it, when adding a new record we need to make sure the ID is set first,
					//so the sub-classes can depend on it. We also need to call Save( TRUE, TRUE ) to force a lookup on isNew()
					$row['id'] = $lf->getNextInsertId();

					//Make sure initial pay periods are created when adding a new pay period schedule.
					$lf->setEnableInitialPayPeriods( TRUE );
					$lf->setCreateInitialPayPeriods( TRUE );
				}
				Debug::Arr($row, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

				$is_valid = $primary_validator->isValid( $ignore_warning );
				if ( $is_valid == TRUE ) { //Check to see if all permission checks passed before trying to save data.
					Debug::Text('Attempting to save data...', __FILE__, __LINE__, __METHOD__, 10);

					//Force Company ID to current company.
					$row['company_id'] = $this->getCurrentCompanyObject()->getId();

					$lf->setObjectFromArray( $row );

					$is_valid = $lf->isValid( $ignore_warning );
					if ( $is_valid == TRUE ) {
						Debug::Text('Saving data...', __FILE__, __LINE__, __METHOD__, 10);
						if ( $validate_only == TRUE ) {
							$save_result[$key] = TRUE;
						} else {
							$save_result[$key] = $lf->Save( TRUE, TRUE );
						}
						$validator_stats['valid_records']++;
					}
				}

				if ( $is_valid == FALSE ) {
					Debug::Text('Data is Invalid...', __FILE__, __LINE__, __METHOD__, 10);

					$lf->FailTransaction(); //Just rollback this single record, continue on to the rest.

					$validator[$key] = $this->setValidationArray( $primary_validator, $lf );
				} elseif ( $validate_only == TRUE ) {
					$lf->FailTransaction();
				}


				$lf->CommitTransaction();
			}

			return $this->handleRecordValidationResults( $validator, $validator_stats, $key, $save_result );
		}

		return $this->returnHandler( FALSE );
	}

	/**
	 * Delete one or more users.
	 * @param array $data user data
	 * @return array|bool
	 */
	function deletePayPeriodSchedule( $data ) {
		if ( !is_array($data) ) {
			$data = array($data);
		}

		if ( !is_array($data) ) {
			return $this->returnHandler( FALSE );
		}

		if ( !$this->getPermissionObject()->Check('pay_period_schedule', 'enabled')
				OR !( $this->getPermissionObject()->Check('pay_period_schedule', 'delete') OR $this->getPermissionObject()->Check('pay_period_schedule', 'delete_own') OR $this->getPermissionObject()->Check('pay_period_schedule', 'delete_child') ) ) {
			return	$this->getPermissionObject()->PermissionDenied();
		}

		//Get Permission Hierarchy Children first, as this can be used for viewing, or editing.
		$permission_children_ids = $this->getPermissionChildren();

		Debug::Text('Received data for: '. count($data) .' PayPeriodSchedules', __FILE__, __LINE__, __METHOD__, 10);
		Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

		$total_records = count($data);
		$validator = $save_result = $key = FALSE;
		$validator_stats = array('total_records' => $total_records, 'valid_records' => 0 );
		if ( is_array($data) AND $total_records > 0 ) {
			foreach( $data as $key => $id ) {
				$primary_validator = new Validator();
				$lf = TTnew( 'PayPeriodScheduleListFactory' ); /** @var PayPeriodScheduleListFactory $lf */
				$lf->StartTransaction();
				if ( $id != '' ) {
					//Modifying existing object.
					//Get user object, so we can only modify just changed data for specific records if needed.
					$lf->getByIdAndCompanyId( $id, $this->getCurrentCompanyObject()->getId() );
					if ( $lf->getRecordCount() == 1 ) {
						//Object exists, check edit permissions
						//Debug::Text('PayPeriodSchedule ID: '. $user['id'] .' Created By: '. $lf->getCurrent()->getCreatedBy() .' Is Owner: '. (int)$this->getPermissionObject()->isOwner( $lf->getCurrent()->getCreatedBy(), $lf->getCurrent()->getID() ) .' Is Child: '. (int)$this->getPermissionObject()->isChild( $lf->getCurrent()->getId(), $permission_children_ids ), __FILE__, __LINE__, __METHOD__, 10);
						if ( $this->getPermissionObject()->Check('pay_period_schedule', 'delete')
								OR ( $this->getPermissionObject()->Check('pay_period_schedule', 'delete_own') AND $this->getPermissionObject()->isOwner( $lf->getCurrent()->getCreatedBy(), $lf->getCurrent()->getID() ) === TRUE )
								OR ( $this->getPermissionObject()->Check('pay_period_schedule', 'delete_child') AND $this->getPermissionObject()->isChild( $lf->getCurrent()->getId(), $permission_children_ids ) === TRUE )) {

							Debug::Text('Record Exists, deleting record ID: '. $id, __FILE__, __LINE__, __METHOD__, 10);
							$lf = $lf->getCurrent();
						} else {
							$primary_validator->isTrue( 'permission', FALSE, TTi18n::gettext('Delete permission denied') );
						}
					} else {
						//Object doesn't exist.
						$primary_validator->isTrue( 'id', FALSE, TTi18n::gettext('Delete permission denied, record does not exist') );
					}
				} else {
					$primary_validator->isTrue( 'id', FALSE, TTi18n::gettext('Delete permission denied, record does not exist') );
				}

				//Debug::Arr($lf, 'AData: ', __FILE__, __LINE__, __METHOD__, 10);

				$is_valid = $primary_validator->isValid();
				if ( $is_valid == TRUE ) { //Check to see if all permission checks passed before trying to save data.
					Debug::Text('Attempting to delete record...', __FILE__, __LINE__, __METHOD__, 10);
					$lf->setDeleted(TRUE);

					$is_valid = $lf->isValid();
					if ( $is_valid == TRUE ) {
						Debug::Text('Record Deleted...', __FILE__, __LINE__, __METHOD__, 10);
						$save_result[$key] = $lf->Save();
						$validator_stats['valid_records']++;
					}
				}

				if ( $is_valid == FALSE ) {
					Debug::Text('Data is Invalid...', __FILE__, __LINE__, __METHOD__, 10);

					$lf->FailTransaction(); //Just rollback this single record, continue on to the rest.

					$validator[$key] = $this->setValidationArray( $primary_validator, $lf );
				}

				$lf->CommitTransaction();
			}

			return $this->handleRecordValidationResults( $validator, $validator_stats, $key, $save_result );
		}

		return $this->returnHandler( FALSE );
	}

	/**
	 * Copy one or more users.
	 * @param array $data user data
	 * @return array
	 */
	function copyPayPeriodSchedule( $data ) {
		//Can only Copy as New, not just a regular copy, as too much data needs to be changed,
		//such as username, password, employee_number, SIN, first/last name address...
		return $this->returnHandler( FALSE );
	}

	/**
	 * @param int $type_id
	 * @param int $example_dates EPOCH
	 * @return array|bool
	 */
	function detectPayPeriodScheduleSettings( $type_id, $example_dates ) {
		$ppsf = TTnew('PayPeriodScheduleFactory'); /** @var PayPeriodScheduleFactory $ppsf */

		//Start with default data...
		$ppsf->setObjectFromArray( $this->stripReturnHandler( $this->getPayPeriodScheduleDefaultData() ) );

		$ppsf->setCompany( $this->getCurrentCompanyObject()->getId() );

		if ( $ppsf->detectPayPeriodScheduleSettings( $type_id, $example_dates ) == TRUE ) {
			$retarr = $ppsf->getObjectAsArray();

			//Set name here so it doesn't go through the validation check prior to Flex submitting it back to the API. This avoid the name field being sent through as "false".
			$retarr['name'] = TTi18n::getText('Default') .' ['. rand(10, 99).']';

			Debug::Arr($retarr, 'Detected settings: ', __FILE__, __LINE__, __METHOD__, 10);
			return $retarr;
		}

		return $this->returnHandler( FALSE ); //return true so Flex doesn't display an error message.
	}

	/**
	 * @param int $type_id
	 * @param int $start_date EPOCH
	 * @return array|bool
	 */
	function detectPayPeriodScheduleDates( $type_id, $start_date ) {
		$ppsf = TTnew('PayPeriodScheduleFactory'); /** @var PayPeriodScheduleFactory $ppsf */
		$retval = $ppsf->detectPayPeriodScheduleDates( $type_id, $start_date );
		Debug::Arr($retval, 'Dates: ', __FILE__, __LINE__, __METHOD__, 10);
		return $this->returnHandler( $retval );
	}

}
?>
