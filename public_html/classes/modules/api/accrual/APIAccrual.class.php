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
 * @package API\Accrual
 */
class APIAccrual extends APIFactory {
	protected $main_class = 'AccrualFactory';

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
				AND ( !$this->getPermissionObject()->Check('accrual', 'enabled')
					OR !( $this->getPermissionObject()->Check('accrual', 'view') OR $this->getPermissionObject()->Check('accrual', 'view_child') ) ) ) {
			$name = 'list_columns';
		}

		return parent::getOptions( $name, $parent );
	}

	/**
	 * Get default accrual data for creating new accruales.
	 * @return array
	 */
	function getAccrualDefaultData() {
		$company_obj = $this->getCurrentCompanyObject();

		Debug::Text('Getting accrual default data...', __FILE__, __LINE__, __METHOD__, 10);

		$data = array(
						'company_id' => $company_obj->getId(),
						'amount' => 0,
						'time_stamp' => TTDate::getAPIDate('DATE', TTDate::getTime() )
					);

		return $this->returnHandler( $data );
	}

	/**
	 * Get accrual data for one or more accruales.
	 * @param array $data filter data
	 * @return array
	 */
	function getAccrual( $data = NULL, $disable_paging = FALSE ) {
		if ( !$this->getPermissionObject()->Check('accrual', 'enabled')
				OR !( $this->getPermissionObject()->Check('accrual', 'view') OR $this->getPermissionObject()->Check('accrual', 'view_own') OR $this->getPermissionObject()->Check('accrual', 'view_child')	) ) {
			return $this->getPermissionObject()->PermissionDenied();
		}
		$data = $this->initializeFilterAndPager( $data, $disable_paging );

		$data['filter_data']['permission_children_ids'] = $this->getPermissionObject()->getPermissionChildren( 'accrual', 'view' );

		$blf = TTnew( 'AccrualListFactory' );
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

			//Debug::Arr($retarr, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);
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
	function exportAccrual( $format = 'csv', $data = NULL, $disable_paging = TRUE) {
		$result = $this->stripReturnHandler( $this->getAccrual( $data, $disable_paging ) );
		return $this->exportRecords( $format, 'export_accrual', $result, ( ( isset($data['filter_columns']) ) ? $data['filter_columns'] : NULL ) );
	}

	/**
	 * Get only the fields that are common across all records in the search criteria. Used for Mass Editing of records.
	 * @param array $data filter data
	 * @return array
	 */
	function getCommonAccrualData( $data ) {
		return Misc::arrayIntersectByRow( $this->stripReturnHandler( $this->getAccrual( $data, TRUE ) ) );
	}

	/**
	 * Validate accrual data for one or more accruales.
	 * @param array $data accrual data
	 * @return array
	 */
	function validateAccrual( $data ) {
		return $this->setAccrual( $data, TRUE );
	}

	/**
	 * Set accrual data for one or more accruales.
	 * @param array $data accrual data
	 * @return array
	 */
	function setAccrual( $data, $validate_only = FALSE, $ignore_warning = TRUE ) {
		$validate_only = (bool)$validate_only;
		$ignore_warning = (bool)$ignore_warning;

		if ( !is_array($data) ) {
			return $this->returnHandler( FALSE );
		}

		if ( !$this->getPermissionObject()->Check('accrual', 'enabled')
				OR !( $this->getPermissionObject()->Check('accrual', 'edit') OR $this->getPermissionObject()->Check('accrual', 'edit_own') OR $this->getPermissionObject()->Check('accrual', 'edit_child') OR $this->getPermissionObject()->Check('accrual', 'add') ) ) {
			return	$this->getPermissionObject()->PermissionDenied();
		}

		if ( $validate_only == TRUE ) {
			Debug::Text('Validating Only!', __FILE__, __LINE__, __METHOD__, 10);
			$permission_children_ids = FALSE;
		} else {
			//Get Permission Hierarchy Children first, as this can be used for viewing, or editing.
			$permission_children_ids = $this->getPermissionChildren();
		}

		extract( $this->convertToMultipleRecords($data) );
		Debug::Text('Received data for: '. $total_records .' Accruals', __FILE__, __LINE__, __METHOD__, 10);
		Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

		$validator_stats = array('total_records' => $total_records, 'valid_records' => 0 );
		$validator = $save_result = FALSE;
		if ( is_array($data) AND $total_records > 0 ) {
			$this->getProgressBarObject()->start( $this->getAMFMessageID(), $total_records );

			foreach( $data as $key => $row ) {
				$primary_validator = new Validator();
				$lf = TTnew( 'AccrualListFactory' );
				$lf->StartTransaction();
				if ( isset($row['id']) AND $row['id'] > 0 ) {
					//Modifying existing object.
					//Get accrual object, so we can only modify just changed data for specific records if needed.
					$lf->getByIdAndCompanyId( $row['id'], $this->getCurrentCompanyObject()->getId() );
					if ( $lf->getRecordCount() == 1 ) {
						//Object exists, check edit permissions
						if (
							$validate_only == TRUE
							OR
								(
								$this->getPermissionObject()->Check('accrual', 'edit')
									OR ( isset($row['user_id']) AND $this->getPermissionObject()->Check('accrual', 'edit_own') AND $this->getPermissionObject()->isOwner( $lf->getCurrent()->getCreatedBy(), $row['user_id'] ) === TRUE )
									OR ( isset($row['user_id']) AND $this->getPermissionObject()->Check('accrual', 'edit_child') AND $this->getPermissionObject()->isChild( $row['user_id'], $permission_children_ids ) === TRUE )
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
					if (	!( $validate_only == TRUE
								OR
								( $this->getPermissionObject()->Check('accrual', 'add')
									AND
									(
										$this->getPermissionObject()->Check('accrual', 'edit')
										OR ( isset($row['user_id']) AND $this->getPermissionObject()->Check('accrual', 'edit_own') AND $this->getPermissionObject()->isOwner( FALSE, $row['user_id'] ) === TRUE ) //We don't know the created_by of the user at this point, but only check if the user is assigned to the logged in person.
										OR ( isset($row['user_id']) AND $this->getPermissionObject()->Check('accrual', 'edit_child') AND $this->getPermissionObject()->isChild( $row['user_id'], $permission_children_ids ) === TRUE )
									)
								)
							) ) {
						$primary_validator->isTrue( 'permission', FALSE, TTi18n::gettext('Add permission denied') );
					}
				}
				Debug::Arr($row, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

				$is_valid = $primary_validator->isValid( $ignore_warning );
				if ( $is_valid == TRUE ) { //Check to see if all permission checks passed before trying to save data.
					Debug::Text('Setting object data...', __FILE__, __LINE__, __METHOD__, 10);

					$lf->setObjectFromArray( $row );

					$lf->setEnableCalcBalance( TRUE ); //Recalculate Accrual Balance.
					//Force Company ID to current company.
					//$lf->setCompany( $this->getCurrentCompanyObject()->getId() );

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

				$this->getProgressBarObject()->set( $this->getAMFMessageID(), $key );
			}

			$this->getProgressBarObject()->stop( $this->getAMFMessageID() );

			return $this->handleRecordValidationResults( $validator, $validator_stats, $key, $save_result );
		}

		return $this->returnHandler( FALSE );
	}

	/**
	 * Delete one or more accruals.
	 * @param array $data accrual data
	 * @return array
	 */
	function deleteAccrual( $data ) {
		if ( is_numeric($data) ) {
			$data = array($data);
		}

		if ( !is_array($data) ) {
			return $this->returnHandler( FALSE );
		}

		if ( !$this->getPermissionObject()->Check('accrual', 'enabled')
				OR !( $this->getPermissionObject()->Check('accrual', 'delete') OR $this->getPermissionObject()->Check('accrual', 'delete_own') OR $this->getPermissionObject()->Check('accrual', 'delete_child') ) ) {
			return	$this->getPermissionObject()->PermissionDenied();
		}

		Debug::Text('Received data for: '. count($data) .' Accruals', __FILE__, __LINE__, __METHOD__, 10);
		Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

		$total_records = count($data);
		$validator = $save_result = FALSE;
		$validator_stats = array('total_records' => $total_records, 'valid_records' => 0 );
		if ( is_array($data) AND $total_records > 0 ) {
			$this->getProgressBarObject()->start( $this->getAMFMessageID(), $total_records );

			foreach( $data as $key => $id ) {
				$primary_validator = new Validator();
				$lf = TTnew( 'AccrualListFactory' );
				$lf->StartTransaction();
				if ( is_numeric($id) ) {
					//Modifying existing object.
					//Get accrual object, so we can only modify just changed data for specific records if needed.
					$lf->getByIdAndCompanyId( $id, $this->getCurrentCompanyObject()->getId() );
					if ( $lf->getRecordCount() == 1 ) {
						//Object exists, check edit permissions
						if ( $this->getPermissionObject()->Check('accrual', 'delete')
								OR ( $this->getPermissionObject()->Check('accrual', 'delete_own') AND $this->getPermissionObject()->isOwner( $lf->getCurrent()->getCreatedBy(), $lf->getCurrent()->getID() ) === TRUE ) ) {
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

					$lf->setEnableCalcBalance(TRUE); //Make sure balances are calculated.
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
	 * Copy one or more accruales.
	 * @param array $data accrual IDs
	 * @return array
	 */
	function copyAccrual( $data ) {
		if ( is_numeric($data) ) {
			$data = array($data);
		}

		if ( !is_array($data) ) {
			return $this->returnHandler( FALSE );
		}

		Debug::Text('Received data for: '. count($data) .' Accruals', __FILE__, __LINE__, __METHOD__, 10);
		Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

		//Make sure only user_type accruals can be copied, not system types.
		$src_rows = $this->stripReturnHandler( $this->getAccrual( array('filter_data' => array('id' => $data, 'type_id' => array_keys( (array)$this->getOptions('user_type') ) ) ), TRUE ) );
		if ( is_array( $src_rows ) AND count($src_rows) > 0 ) {
			Debug::Arr($src_rows, 'SRC Rows: ', __FILE__, __LINE__, __METHOD__, 10);
			foreach( $src_rows as $key => $row ) {
				unset( $src_rows[$key]['id'], $row ); //Clear fields that can't be copied
			}
			//Debug::Arr($src_rows, 'bSRC Rows: ', __FILE__, __LINE__, __METHOD__, 10);

			return $this->setAccrual( $src_rows ); //Save copied rows
		}

		return $this->returnHandler( FALSE );
	}
}
?>
