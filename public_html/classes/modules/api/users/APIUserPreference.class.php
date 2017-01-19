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
 * @package API\Users
 */
class APIUserPreference extends APIFactory {
	protected $main_class = 'UserPreferenceFactory';

	public function __construct() {
		parent::__construct(); //Make sure parent constructor is always called.

		return TRUE;
	}

	/**
	 * Get default UserPreference data for creating new UserPreferencees.
	 * @return array
	 */
	function getUserPreferenceDefaultData() {
		$company_id = $this->getCurrentCompanyObject()->getId();

		Debug::Text('Getting UserPreference default data...', __FILE__, __LINE__, __METHOD__, 10);

		//Get New Hire Defaults.
		$udlf = TTnew( 'UserDefaultListFactory' );
		$udlf->getByCompanyId( $company_id );
		if ( $udlf->getRecordCount() > 0 ) {
			Debug::Text('Using User Defaults, as they exist...', __FILE__, __LINE__, __METHOD__, 10);
			$udf_obj = $udlf->getCurrent();

			$data = array(
							'company_id' => $company_id,
							'language' => $udf_obj->getLanguage(),
							'date_format' => $udf_obj->getDateFormat(),
							'time_format' => $udf_obj->getTimeFormat(),
							'time_zone' => $udf_obj->getTimeZone(),
							'time_unit_format' => $udf_obj->getTimeUnitFormat(),
							'distance_format' => $udf_obj->getDistanceFormat(),
							'items_per_page' => $udf_obj->getItemsPerPage(),
							'start_week_day' => $udf_obj->getStartWeekDay(),
							'enable_email_notification_exception' => $udf_obj->getEnableEmailNotificationException(),
							'enable_email_notification_message' => $udf_obj->getEnableEmailNotificationMessage(),
							'enable_email_notification_home' => $udf_obj->getEnableEmailNotificationHome(),
							'enable_email_notification_pay_stub' => $udf_obj->getEnableEmailNotificationPayStub(),
							'enable_auto_context_menu' => TRUE,
							'enable_save_timesheet_state' => TRUE,
						);
		} else {
			$data = array(
							'company_id' => $company_id,
							'language' => 'en',
							'time_unit_format' => 20, //Hours
							'distance_format' => 10, // Kilometers
							'items_per_page' => 25,
							'enable_email_notification_exception' => TRUE,
							'enable_email_notification_message' => TRUE,
							'enable_email_notification_home' => FALSE,
							'enable_email_notification_pay_stub' => TRUE,
							'enable_auto_context_menu' => TRUE,
							'enable_save_timesheet_state' => TRUE,
						);
		}

		return $this->returnHandler( $data );
	}

	/**
	 * Get UserPreference data for one or more UserPreferencees.
	 * @param array $data filter data
	 * @return array
	 */
	function getUserPreference( $data = NULL, $disable_paging = FALSE ) {
		if ( !$this->getPermissionObject()->Check('user_preference', 'enabled')
				OR !( $this->getPermissionObject()->Check('user_preference', 'view') OR $this->getPermissionObject()->Check('user_preference', 'view_own') OR $this->getPermissionObject()->Check('user_preference', 'view_child')	) ) {
			return $this->getPermissionObject()->PermissionDenied();
		}
		$data = $this->initializeFilterAndPager( $data, $disable_paging );

		//Get Permission Hierarchy Children first, as this can be used for viewing, or editing.
		$data['filter_data']['permission_children_ids'] = $this->getPermissionObject()->getPermissionChildren( 'user_preference', 'view' );

		$uplf = TTnew( 'UserPreferenceListFactory' );
		$uplf->getAPISearchByCompanyIdAndArrayCriteria( $this->getCurrentCompanyObject()->getId(), $data['filter_data'], $data['filter_items_per_page'], $data['filter_page'], NULL, $data['filter_sort'] );
		Debug::Text('Record Count: '. $uplf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
		if ( $uplf->getRecordCount() > 0 ) {
			$this->getProgressBarObject()->start( $this->getAMFMessageID(), $uplf->getRecordCount() );

			$this->setPagerObject( $uplf );

			$retarr = array();
			foreach( $uplf as $ut_obj ) {
				$retarr[] = $ut_obj->getObjectAsArray( $data['filter_columns'], $data['filter_data']['permission_children_ids'] );

				$this->getProgressBarObject()->set( $this->getAMFMessageID(), $uplf->getCurrentRow() );
			}

			$this->getProgressBarObject()->stop( $this->getAMFMessageID() );

			return $this->returnHandler( $retarr );
		}

		return $this->returnHandler( TRUE ); //No records returned.
	}

	/**
	 * Export data to csv
	 * @param array $data filter data
	 * @param string $format file format (csv)
	 * @return array
	 */
	function exportUserPreference( $format = 'csv', $data = NULL, $disable_paging = TRUE) {
		$result = $this->stripReturnHandler( $this->getUserPreference( $data, $disable_paging ) );
		return $this->exportRecords( $format, 'export_employee_preference', $result, ( ( isset($data['filter_columns']) ) ? $data['filter_columns'] : NULL ) );
	}
	/**
	 * Get only the fields that are common across all records in the search criteria. Used for Mass Editing of records.
	 * @param array $data filter data
	 * @return array
	 */
	function getCommonUserPreferenceData( $data ) {
		return Misc::arrayIntersectByRow( $this->stripReturnHandler( $this->getUserPreference( $data, TRUE ) ) );
	}

	/**
	 * Validate UserPreference data for one or more UserPreferencees.
	 * @param array $data UserPreference data
	 * @return array
	 */
	function validateUserPreference( $data ) {
		return $this->setUserPreference( $data, TRUE );
	}

	/**
	 * Set UserPreference data for one or more UserPreferencees.
	 * @param array $data UserPreference data
	 * @return array
	 */
	function setUserPreference( $data, $validate_only = FALSE, $ignore_warning = TRUE ) {
		$validate_only = (bool)$validate_only;
		$ignore_warning = (bool)$ignore_warning;

		if ( !is_array($data) ) {
			return $this->returnHandler( FALSE );
		}

		if ( !$this->getPermissionObject()->Check('user_preference', 'enabled')
				OR !( $this->getPermissionObject()->Check('user_preference', 'edit') OR $this->getPermissionObject()->Check('user_preference', 'edit_own') OR $this->getPermissionObject()->Check('user_preference', 'edit_child') OR $this->getPermissionObject()->Check('user_preference', 'add') ) ) {
			return	$this->getPermissionObject()->PermissionDenied();
		}

		if ( $validate_only == TRUE ) {
			Debug::Text('Validating Only!', __FILE__, __LINE__, __METHOD__, 10);
		}

		extract( $this->convertToMultipleRecords($data) );
		Debug::Text('Received data for: '. $total_records .' UserPreferences', __FILE__, __LINE__, __METHOD__, 10);
		//Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

		$validator_stats = array('total_records' => $total_records, 'valid_records' => 0 );
		$validator = $save_result = FALSE;
		if ( is_array($data) AND $total_records > 0 ) {
			$this->getProgressBarObject()->start( $this->getAMFMessageID(), $total_records );

			foreach( $data as $key => $row ) {
				$primary_validator = new Validator();
				$lf = TTnew( 'UserPreferenceListFactory' );
				$lf->StartTransaction();
				if ( isset($row['id']) AND $row['id'] > 0 ) {
					//Modifying existing object.
					//Get UserPreference object, so we can only modify just changed data for specific records if needed.
					$lf->getByIdAndCompanyId( $row['id'], $this->getCurrentCompanyObject()->getId() );
					if ( $lf->getRecordCount() == 1 ) {
						//Object exists, check edit permissions
						if (
							$validate_only == TRUE
							OR
								(
								$this->getPermissionObject()->Check('user_preference', 'edit')
									OR ( $this->getPermissionObject()->Check('user_preference', 'edit_own') AND $this->getPermissionObject()->isOwner( $lf->getCurrent()->getCreatedBy(), $lf->getCurrent()->getUser() ) === TRUE )
								) ) {

							Debug::Text('Row Exists, getting current data: '. $row['id'], __FILE__, __LINE__, __METHOD__, 10);
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
					//Always allow the currently logged in user to create preferences in case the record isn't there.
					if ( !( isset($row['user_id']) AND $row['user_id'] == $this->getCurrentUserObject()->getId() ) ) {
						//Adding new object, check ADD permissions.
						$primary_validator->isTrue( 'permission', $this->getPermissionObject()->Check('user', 'add'), TTi18n::gettext('Add permission denied') );
					}
				}
				Debug::Arr($row, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

				$is_valid = $primary_validator->isValid( $ignore_warning );
				if ( $is_valid == TRUE ) { //Check to see if all permission checks passed before trying to save data.
					Debug::Text('Setting object data...', __FILE__, __LINE__, __METHOD__, 10);

					$lf->setObjectFromArray( $row );

					//Force Company ID to current company.
					//$lf->setCompany( $this->getCurrentCompanyObject()->getId() );

					$is_valid = $lf->isValid( $ignore_warning );
					if ( $is_valid == TRUE ) {
						Debug::Text('Saving data...', __FILE__, __LINE__, __METHOD__, 10);
						if ( $validate_only == TRUE ) {
							$save_result[$key] = TRUE;
						} else {
							$save_result[$key] = $lf->Save();
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

				$this->getProgressBarObject()->set( $this->getAMFMessageID(), $key );
			}

			$this->getProgressBarObject()->stop( $this->getAMFMessageID() );

			return $this->handleRecordValidationResults( $validator, $validator_stats, $key, $save_result );
		}

		return $this->returnHandler( FALSE );
	}

	/**
	 * Delete one or more UserPreferences.
	 * @param array $data UserPreference data
	 * @return array
	 */
	function deleteUserPreference( $data ) {
		if ( is_numeric($data) ) {
			$data = array($data);
		}

		if ( !is_array($data) ) {
			return $this->returnHandler( FALSE );
		}

		if ( !$this->getPermissionObject()->Check('user_preference', 'enabled')
				OR !( $this->getPermissionObject()->Check('user_preference', 'delete') OR $this->getPermissionObject()->Check('user_preference', 'delete_own') OR $this->getPermissionObject()->Check('user_preference', 'delete_child') ) ) {
			return	$this->getPermissionObject()->PermissionDenied();
		}

		Debug::Text('Received data for: '. count($data) .' UserPreferences', __FILE__, __LINE__, __METHOD__, 10);
		Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

		$total_records = count($data);
		$validator = $save_result = FALSE;
		$validator_stats = array('total_records' => $total_records, 'valid_records' => 0 );
		if ( is_array($data) AND $total_records > 0 ) {
			$this->getProgressBarObject()->start( $this->getAMFMessageID(), $total_records );

			foreach( $data as $key => $id ) {
				$primary_validator = new Validator();
				$lf = TTnew( 'UserPreferenceListFactory' );
				$lf->StartTransaction();
				if ( is_numeric($id) ) {
					//Modifying existing object.
					//Get UserPreference object, so we can only modify just changed data for specific records if needed.
					$lf->getByIdAndCompanyId( $id, $this->getCurrentCompanyObject()->getId() );
					if ( $lf->getRecordCount() == 1 ) {
						//Object exists, check edit permissions
						if ( $this->getPermissionObject()->Check('user_preference', 'delete')
								OR ( $this->getPermissionObject()->Check('user_preference', 'delete_own') AND $this->getPermissionObject()->isOwner( $lf->getCurrent()->getCreatedBy(), $lf->getCurrent()->getID() ) === TRUE ) ) {
							Debug::Text('Record Exists, deleting record: ', $id, __FILE__, __LINE__, __METHOD__, 10);
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

				$this->getProgressBarObject()->set( $this->getAMFMessageID(), $key );
			}

			$this->getProgressBarObject()->stop( $this->getAMFMessageID() );

			return $this->handleRecordValidationResults( $validator, $validator_stats, $key, $save_result );
		}

		return $this->returnHandler( FALSE );
	}

	/**
	 * Copy one or more UserPreferencees.
	 * @param array $data UserPreference IDs
	 * @return array
	 */
	function copyUserPreference( $data ) {
		if ( is_numeric($data) ) {
			$data = array($data);
		}

		if ( !is_array($data) ) {
			return $this->returnHandler( FALSE );
		}

		Debug::Text('Received data for: '. count($data) .' UserPreferences', __FILE__, __LINE__, __METHOD__, 10);
		Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

		$src_rows = $this->stripReturnHandler( $this->getUserPreference( array('filter_data' => array('id' => $data) ), TRUE ) );
		if ( is_array( $src_rows ) AND count($src_rows) > 0 ) {
			Debug::Arr($src_rows, 'SRC Rows: ', __FILE__, __LINE__, __METHOD__, 10);
			foreach( $src_rows as $key => $row ) {
				unset($src_rows[$key]['id'], $src_rows[$key]['manual_id'] ); //Clear fields that can't be copied
				$src_rows[$key]['name'] = Misc::generateCopyName( $row['name'] ); //Generate unique name
			}
			//Debug::Arr($src_rows, 'bSRC Rows: ', __FILE__, __LINE__, __METHOD__, 10);

			return $this->setUserPreference( $src_rows ); //Save copied rows
		}

		return $this->returnHandler( FALSE );
	}

	function getScheduleIcalendarURL( $user_name = NULL, $type_id = NULL ) {
		$current_user_prefs = $this->getCurrentUserObject()->getUserPreferenceObject();
		if ( is_object($current_user_prefs) ) {
			return $this->returnHandler( $current_user_prefs->getScheduleIcalendarURL( $user_name, $type_id ) );
		}
	}
}
?>
