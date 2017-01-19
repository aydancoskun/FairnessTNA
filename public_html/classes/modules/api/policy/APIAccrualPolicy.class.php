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
 * @package API\Policy
 */
class APIAccrualPolicy extends APIFactory {
	protected $main_class = 'AccrualPolicyFactory';

	public function __construct() {
		parent::__construct(); //Make sure parent constructor is always called.

		return TRUE;
	}

	/**
	 * Get options for dropdown boxes.
	 * @param string $name Name of options to return, ie: 'columns', 'type', 'status'
	 * @param mixed $parent Parent name/ID of options to return if data is in hierarchical format. (ie: Province)
	 * @return array
	 */
	function getOptions( $name = FALSE, $parent = NULL ) {
		if ( $name == 'columns'
				AND ( !$this->getPermissionObject()->Check('accrual_policy', 'enabled')
					OR !( $this->getPermissionObject()->Check('accrual_policy', 'view') OR $this->getPermissionObject()->Check('accrual_policy', 'view_own') OR $this->getPermissionObject()->Check('accrual_policy', 'view_child') ) ) ) {
			$name = 'list_columns';
		}

		return parent::getOptions( $name, $parent );
	}

	/**
	 * Get default accrual_policy data for creating new accrual_policyes.
	 * @return array
	 */
	function getAccrualPolicyDefaultData() {
		$company_obj = $this->getCurrentCompanyObject();

		Debug::Text('Getting accrual_policy default data...', __FILE__, __LINE__, __METHOD__, 10);

		$data = array(
						'company_id' => $company_obj->getId(),
						'type_id' => 20,
						'minimum_employed_days' => 0,
						'milestone_rollover_hire_date' => TRUE,
						'enable_pro_rate_initial_period' => TRUE,
					);

		return $this->returnHandler( $data );
	}

	/**
	 * Get accrual_policy data for one or more accrual_policyes.
	 * @param array $data filter data
	 * @return array
	 */
	function getAccrualPolicy( $data = NULL, $disable_paging = FALSE ) {
		if ( !$this->getPermissionObject()->Check('accrual_policy', 'enabled')
				OR !( $this->getPermissionObject()->Check('accrual_policy', 'view') OR $this->getPermissionObject()->Check('accrual_policy', 'view_own') OR $this->getPermissionObject()->Check('accrual_policy', 'view_child')	 ) ) {
			//return $this->getPermissionObject()->PermissionDenied();
			$data['filter_columns'] = $this->handlePermissionFilterColumns( (isset($data['filter_columns'])) ? $data['filter_columns'] : NULL, Misc::trimSortPrefix( $this->getOptions('list_columns') ) );
		}
		$data = $this->initializeFilterAndPager( $data, $disable_paging );

		$data['filter_data']['permission_children_ids'] = $this->getPermissionObject()->getPermissionChildren( 'accrual_policy', 'view' );

		$blf = TTnew( 'AccrualPolicyListFactory' );
		$blf->getAPISearchByCompanyIdAndArrayCriteria( $this->getCurrentCompanyObject()->getId(), $data['filter_data'], $data['filter_items_per_page'], $data['filter_page'], NULL, $data['filter_sort'] );
		Debug::Text('Record Count: '. $blf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
		if ( $blf->getRecordCount() > 0 ) {
			$this->setPagerObject( $blf );

			$retarr = array();
			foreach( $blf as $b_obj ) {
				$retarr[] = $b_obj->getObjectAsArray( $data['filter_columns'] );
			}

			return $this->returnHandler( $retarr );
		}

		return $this->returnHandler( TRUE ); //No records returned.
	}

	/**
	 * @param string $format
	 * @param null $data
	 * @param bool $disable_paging
	 * @return array|bool
	 */
	function exportAccrualPolicy( $format = 'csv', $data = NULL, $disable_paging = TRUE ) {
		$result = $this->stripReturnHandler( $this->getAccrualPolicy( $data, $disable_paging ) );
		return $this->exportRecords( $format, 'export_accrual_policy', $result, ( ( isset($data['filter_columns']) ) ? $data['filter_columns'] : NULL ) );
	}

	/**
	 * Get only the fields that are common across all records in the search criteria. Used for Mass Editing of records.
	 * @param array $data filter data
	 * @return array
	 */
	function getCommonAccrualPolicyData( $data ) {
		return Misc::arrayIntersectByRow( $this->stripReturnHandler( $this->getAccrualPolicy( $data, TRUE ) ) );
	}

	/**
	 * Validate accrual_policy data for one or more accrual_policyes.
	 * @param array $data accrual_policy data
	 * @return array
	 */
	function validateAccrualPolicy( $data ) {
		return $this->setAccrualPolicy( $data, TRUE );
	}

	/**
	 * Set accrual_policy data for one or more accrual_policyes.
	 * @param array $data accrual_policy data
	 * @return array
	 */
	function setAccrualPolicy( $data, $validate_only = FALSE, $ignore_warning = TRUE ) {
		$validate_only = (bool)$validate_only;
		$ignore_warning = (bool)$ignore_warning;

		if ( !is_array($data) ) {
			return $this->returnHandler( FALSE );
		}

		if ( !$this->getPermissionObject()->Check('accrual_policy', 'enabled')
				OR !( $this->getPermissionObject()->Check('accrual_policy', 'edit') OR $this->getPermissionObject()->Check('accrual_policy', 'edit_own') OR $this->getPermissionObject()->Check('accrual_policy', 'edit_child') OR $this->getPermissionObject()->Check('accrual_policy', 'add') ) ) {
			return	$this->getPermissionObject()->PermissionDenied();
		}

		if ( $validate_only == TRUE ) {
			Debug::Text('Validating Only!', __FILE__, __LINE__, __METHOD__, 10);
		}

		extract( $this->convertToMultipleRecords($data) );
		Debug::Text('Received data for: '. $total_records .' AccrualPolicys', __FILE__, __LINE__, __METHOD__, 10);
		Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

		$validator_stats = array('total_records' => $total_records, 'valid_records' => 0 );
		$validator = $save_result = FALSE;
		if ( is_array($data) AND $total_records > 0 ) {
			foreach( $data as $key => $row ) {
				$primary_validator = new Validator();
				$lf = TTnew( 'AccrualPolicyListFactory' );
				$lf->StartTransaction();
				if ( isset($row['id']) AND $row['id'] > 0 ) {
					//Modifying existing object.
					//Get accrual_policy object, so we can only modify just changed data for specific records if needed.
					$lf->getByIdAndCompanyId( $row['id'], $this->getCurrentCompanyObject()->getId() );
					if ( $lf->getRecordCount() == 1 ) {
						//Object exists, check edit permissions
						if (
							$validate_only == TRUE
							OR
								(
								$this->getPermissionObject()->Check('accrual_policy', 'edit')
									OR ( $this->getPermissionObject()->Check('accrual_policy', 'edit_own') AND $this->getPermissionObject()->isOwner( $lf->getCurrent()->getCreatedBy(), $lf->getCurrent()->getID() ) === TRUE )
								) ) {

							Debug::Text('Row Exists, getting current data: ', $row['id'], __FILE__, __LINE__, __METHOD__, 10);
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
					$primary_validator->isTrue( 'permission', $this->getPermissionObject()->Check('accrual_policy', 'add'), TTi18n::gettext('Add permission denied') );
				}
				Debug::Arr($row, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

				$is_valid = $primary_validator->isValid( $ignore_warning );
				if ( $is_valid == TRUE ) { //Check to see if all permission checks passed before trying to save data.
					Debug::Text('Setting object data...', __FILE__, __LINE__, __METHOD__, 10);

					//Force Company ID to current company.
					$row['company_id'] = $this->getCurrentCompanyObject()->getId();

					$lf->setObjectFromArray( $row );
					$lf->Validator->setValidateOnly( $validate_only );

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
			}

			return $this->handleRecordValidationResults( $validator, $validator_stats, $key, $save_result );
		}

		return $this->returnHandler( FALSE );
	}

	/**
	 * Delete one or more accrual_policys.
	 * @param array $data accrual_policy data
	 * @return array
	 */
	function deleteAccrualPolicy( $data ) {
		if ( is_numeric($data) ) {
			$data = array($data);
		}

		if ( !is_array($data) ) {
			return $this->returnHandler( FALSE );
		}

		if ( !$this->getPermissionObject()->Check('accrual_policy', 'enabled')
				OR !( $this->getPermissionObject()->Check('accrual_policy', 'delete') OR $this->getPermissionObject()->Check('accrual_policy', 'delete_own') OR $this->getPermissionObject()->Check('accrual_policy', 'delete_child') ) ) {
			return	$this->getPermissionObject()->PermissionDenied();
		}

		Debug::Text('Received data for: '. count($data) .' AccrualPolicys', __FILE__, __LINE__, __METHOD__, 10);
		Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

		$total_records = count($data);
		$validator = $save_result = FALSE;
		$validator_stats = array('total_records' => $total_records, 'valid_records' => 0 );
		if ( is_array($data) AND $total_records > 0 ) {
			foreach( $data as $key => $id ) {
				$primary_validator = new Validator();
				$lf = TTnew( 'AccrualPolicyListFactory' );
				$lf->StartTransaction();
				if ( is_numeric($id) ) {
					//Modifying existing object.
					//Get accrual_policy object, so we can only modify just changed data for specific records if needed.
					$lf->getByIdAndCompanyId( $id, $this->getCurrentCompanyObject()->getId() );
					if ( $lf->getRecordCount() == 1 ) {
						//Object exists, check edit permissions
						if ( $this->getPermissionObject()->Check('accrual_policy', 'delete')
								OR ( $this->getPermissionObject()->Check('accrual_policy', 'delete_own') AND $this->getPermissionObject()->isOwner( $lf->getCurrent()->getCreatedBy(), $lf->getCurrent()->getID() ) === TRUE ) ) {
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
			}

			return $this->handleRecordValidationResults( $validator, $validator_stats, $key, $save_result );
		}

		return $this->returnHandler( FALSE );
	}

	/**
	 * Copy one or more accrual_policyes.
	 * @param array $data accrual_policy IDs
	 * @return array
	 */
	function copyAccrualPolicy( $data ) {
		if ( is_numeric($data) ) {
			$data = array($data);
		}

		if ( !is_array($data) ) {
			return $this->returnHandler( FALSE );
		}

		Debug::Text('Received data for: '. count($data) .' AccrualPolicys', __FILE__, __LINE__, __METHOD__, 10);
		Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

		$src_rows = $this->stripReturnHandler( $this->getAccrualPolicy( array('filter_data' => array('id' => $data) ), TRUE ) );
		if ( is_array( $src_rows ) AND count($src_rows) > 0 ) {
			$original_ids = array();
			Debug::Arr($src_rows, 'SRC Rows: ', __FILE__, __LINE__, __METHOD__, 10);
			foreach( $src_rows as $key => $row ) {
				$original_ids[$key] = $src_rows[$key]['id'];
				unset($src_rows[$key]['id'], $src_rows[$key]['manual_id'] ); //Clear fields that can't be copied
				$src_rows[$key]['name'] = Misc::generateCopyName( $row['name'] ); //Generate unique name
			}
			//Debug::Arr($src_rows, 'bSRC Rows: ', __FILE__, __LINE__, __METHOD__, 10);

			$retval = $this->setAccrualPolicy( $src_rows ); //Save copied rows

			//Now we need to loop through the result set, and copy the milestones as well.
			if ( empty($original_ids) == FALSE ) {
				Debug::Arr($original_ids, ' Original IDs: ', __FILE__, __LINE__, __METHOD__, 10);
				Debug::Arr($retval, ' New IDs: ', __FILE__, __LINE__, __METHOD__, 10);

				foreach( $original_ids as $key => $original_id ) {
					$new_id = NULL;
					if ( is_array($retval) ) {
						if ( isset($retval['api_retval']) AND is_numeric($retval['api_retval']) AND $retval['api_retval'] > 0 ) {
							$new_id = $retval['api_retval'];
						} elseif ( isset($retval['api_details']['details'][$key]) ) {
							$new_id = $retval['api_details']['details'][$key];
						}
					} elseif ( is_numeric($retval) ) {
						$new_id = $retval;
					}

					if ( $new_id !== NULL ) {
						//Get milestones by original_id.
						$apmlf = TTnew( 'AccrualPolicyMilestoneListFactory' );
						$apmlf->getByAccrualPolicyID( $original_id );
						if ( $apmlf->getRecordCount() > 0 ) {
							foreach( $apmlf as $apm_obj ) {
								Debug::Text('Copying Milestone ID: '. $apm_obj->getID()	 .' To Accrual Policy: '. $new_id, __FILE__, __LINE__, __METHOD__, 10);

								//Copy milestone to new_id
								$apm_obj->setId( FALSE );
								$apm_obj->setAccrualPolicy( $new_id );
								if ( $apm_obj->isValid() ) {
									$apm_obj->Save();
								}
							}
						}
					}
				}
			}

			return $retval;
		}

		return $this->returnHandler( FALSE );
	}

	/**
	 * ReCalculate accrual policies
	 * @return bool
	 */
	function reCalculateAccrual( $accrual_policy_ids, $time_period_arr, $user_ids = NULL ) {
		//Debug::text('Recalculating Employee Timesheet: User ID: '. $user_ids .' Pay Period ID: '. $pay_period_ids, __FILE__, __LINE__, __METHOD__, 10);
		//Debug::setVerbosity(11);

		if ( !$this->getPermissionObject()->Check('accrual_policy', 'enabled')
				OR !( $this->getPermissionObject()->Check('accrual_policy', 'edit') OR $this->getPermissionObject()->Check('accrual_policy', 'edit_child') OR $this->getPermissionObject()->Check('accrual_policy', 'edit_own') ) ) {
			return	$this->getPermissionObject()->PermissionDenied();
		}

		if ( Misc::isSystemLoadValid() == FALSE ) { //Check system load before anything starts.
			Debug::Text('ERROR: System load exceeded, preventing new recalculation processes from starting...', __FILE__, __LINE__, __METHOD__, 10);
			return $this->returnHandler( FALSE );
		}

		$report_obj = TTNew('Report');
		$date_arr = $report_obj->convertTimePeriodToStartEndDate( $time_period_arr, NULL, TRUE ); //Force start/end dates even if pay periods selected.
		Debug::Arr($date_arr, 'Date Arr', __FILE__, __LINE__, __METHOD__, 10);
		
		if ( isset($date_arr['start_date']) AND isset($date_arr['end_date']) ) {
			$total_days = TTDate::getDays( ( $date_arr['end_date'] - $date_arr['start_date'] ) );

			$aplf = TTnew( 'AccrualPolicyListFactory' );
			$aplf->getByIdAndCompanyId( (array)$accrual_policy_ids, $this->getCurrentCompanyObject()->getId() );
			if ( $aplf->getRecordCount() > 0 ) {
				$this->getProgressBarObject()->start( $this->getAMFMessageID(), $aplf->getRecordCount(), NULL, TTi18n::getText('ReCalculating...') );

				foreach( $aplf as $ap_obj ) {
					if ( Misc::isSystemLoadValid() == FALSE ) { //Check system load as the user could ask to calculate decades worth at a time.
						Debug::Text('ERROR: System load exceeded, stopping recalculation... (a)', __FILE__, __LINE__, __METHOD__, 10);
						break;
					}

					$aplf->StartTransaction();

					TTLog::addEntry( $this->getCurrentUserObject()->getId(), 500, 'Recalculate Accrual Policy: '. $ap_obj->getName() .' Start Date: '. TTDate::getDate('DATE', $date_arr['start_date'] ) .' End Date: '. TTDate::getDate('DATE', $date_arr['end_date'] ) .' Total Days: '. round( $total_days ), $this->getCurrentUserObject()->getId(), $ap_obj->getTable() );

					$x = 0;
					for( $i = $date_arr['start_date']; $i < $date_arr['end_date']; $i += (86400) ) {
						if ( ( $x % 100 ) == 0 AND Misc::isSystemLoadValid() == FALSE ) { //Check system load as the user could ask to calculate decades worth at a time.
							Debug::Text('ERROR: System load exceeded, stopping recalculation... (b)', __FILE__, __LINE__, __METHOD__, 10);
							break;
						}

						//$i = TTDate::getBeginDayEpoch( $i ); //This causes infinite loops during DST transitions.
						Debug::Text('Recalculating Accruals for Date: '. TTDate::getDate('DATE+TIME', TTDate::getBeginDayEpoch( $i ) ), __FILE__, __LINE__, __METHOD__, 10);
						$ap_obj->addAccrualPolicyTime( TTDate::getBeginDayEpoch( $i ), 79200, $user_ids ); //Use default offset.

						$this->getProgressBarObject()->set( $this->getAMFMessageID(), $x );

						$x++;
					}

					//$aplf->FailTransaction();
					$aplf->CommitTransaction();
				}

				$this->getProgressBarObject()->stop( $this->getAMFMessageID() );
			} else {
				Debug::Text('No accrual policies to recalculate...', __FILE__, __LINE__, __METHOD__, 10);
			}
		} else {
			Debug::Text('No dates to calculate accrual policies for...', __FILE__, __LINE__, __METHOD__, 10);
		}

		return $this->returnHandler( TRUE );
	}

}
?>
