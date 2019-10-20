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
 * @package API\Company
 */
class APICompanyDeduction extends APIFactory {
	protected $main_class = 'CompanyDeductionFactory';

	/**
	 * APICompanyDeduction constructor.
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
				AND ( !$this->getPermissionObject()->Check('company_tax_deduction', 'enabled')
					OR !( $this->getPermissionObject()->Check('company_tax_deduction', 'view') OR $this->getPermissionObject()->Check('company_tax_deduction', 'view_own') OR $this->getPermissionObject()->Check('company_tax_deduction', 'view_child') ) ) ) {
			$name = 'list_columns';
		}

		return parent::getOptions( $name, $parent );
	}

	/**
	 * Get default company_deduction data for creating new company_deductiones.
	 * @return array
	 */
	function getCompanyDeductionDefaultData() {
		$company_obj = $this->getCurrentCompanyObject();
		$user_obj = $this->getCurrentUserObject();

		Debug::Text('Getting company_deduction default data...', __FILE__, __LINE__, __METHOD__, 10);

		$data = array(
						'company_id' => $company_obj->getId(),
						'legal_entity_id' => $user_obj->getLegalEntity(),
						'status_id' => 10,
						'type_id' => 10,
						'apply_frequency_id' => 10, //each Pay Period
						'calculation_order' => 100,
					);

		return $this->returnHandler( $data );
	}

	/**
	 * Get company_deduction data for one or more company_deductiones.
	 * @param array $data filter data
	 * @param bool $disable_paging
	 * @return array
	 */
	function getCompanyDeduction( $data = NULL, $disable_paging = FALSE ) {
		$data = $this->initializeFilterAndPager( $data, $disable_paging );

		if ( !$this->getPermissionObject()->Check('company_tax_deduction', 'enabled')
				OR !( $this->getPermissionObject()->Check('company_tax_deduction', 'view') OR $this->getPermissionObject()->Check('company_tax_deduction', 'view_own') OR $this->getPermissionObject()->Check('company_tax_deduction', 'view_child')  ) ) {
			//return $this->getPermissionObject()->PermissionDenied();

			if ( !$this->getPermissionObject()->Check('user_tax_deduction', 'enabled')
					OR !( $this->getPermissionObject()->Check('user_tax_deduction', 'view') OR $this->getPermissionObject()->Check('user_tax_deduction', 'view_own') OR $this->getPermissionObject()->Check('user_tax_deduction', 'view_child')  ) ) {
				$data['filter_columns'] = $this->handlePermissionFilterColumns( (isset($data['filter_columns'])) ? $data['filter_columns'] : NULL, Misc::trimSortPrefix( $this->getOptions('list_columns') ) );
			} else {
				//User has access the user_tax_deduction permissions, restrict columns that are returned so we don't include all users, include/exclude PSA's etc...
				if ( !isset($data['filter_columns']) ) {
					$cdf = TTnew( 'CompanyDeductionFactory' ); /** @var CompanyDeductionFactory $cdf */
					$data['filter_columns'] = Misc::preSetArrayValues( array(), array_keys( $cdf->getVariableToFunctionMap() ), TRUE );
					unset($cdf, $data['filter_columns']['user'], $data['filter_columns']['include_pay_stub_entry_account'], $data['filter_columns']['exclude_pay_stub_entry_account'], $data['filter_columns']['total_users']);
				}
			}
		}

		//Need to pass this into getObjectAsArray() separately to help handle Edit Employee -> Tax tab and is_owner/is_child columns.
		if ( isset($data['filter_data']['include_user_id']) ) {
			$include_user_id = $data['filter_data']['include_user_id'];
		} else {
			$include_user_id = FALSE;
		}

		//Help handle cases where a supervisor can access the Edit Employee -> Tax tab, but not the Payroll -> Tax/Deduction list.
		if ( $this->getPermissionObject()->Check('company_tax_deduction', 'enabled') AND ( $this->getPermissionObject()->Check('company_tax_deduction', 'view') OR $this->getPermissionObject()->Check('company_tax_deduction', 'view_own') OR $this->getPermissionObject()->Check('company_tax_deduction', 'view_child') ) ) {
			Debug::Text('Using company_tax_deduction permission_children...', __FILE__, __LINE__, __METHOD__, 10);
			$data['filter_data']['permission_children_ids'] = $this->getPermissionObject()->getPermissionChildren( 'company_tax_deduction', 'view' );
		} elseif ( $this->getPermissionObject()->Check('user_tax_deduction', 'enabled') AND ( $this->getPermissionObject()->Check('user_tax_deduction', 'view') OR $this->getPermissionObject()->Check('user_tax_deduction', 'view_own') OR $this->getPermissionObject()->Check('user_tax_deduction', 'view_child') ) )  {
			Debug::Text('Using user_tax_deduction permission_children...', __FILE__, __LINE__, __METHOD__, 10);
			$data['filter_data']['permission_children_ids'] = $this->getPermissionObject()->getPermissionChildren( 'user_tax_deduction', 'view' );
		} else {
			$data['filter_data']['permission_children_ids'] = NULL;
		}

		$blf = TTnew( 'CompanyDeductionListFactory' ); /** @var CompanyDeductionListFactory $blf */
		$blf->getAPISearchByCompanyIdAndArrayCriteria( $this->getCurrentCompanyObject()->getId(), $data['filter_data'], $data['filter_items_per_page'], $data['filter_page'], NULL, $data['filter_sort'] );
		Debug::Text('Record Count: '. $blf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
		if ( $blf->getRecordCount() > 0 ) {
			$this->getProgressBarObject()->start( $this->getAMFMessageID(), $blf->getRecordCount() );

			$this->setPagerObject( $blf );

			$retarr = array();
			foreach( $blf as $b_obj ) {
				$retarr[] = $b_obj->getObjectAsArray( $data['filter_columns'], $data['filter_data']['permission_children_ids'], $include_user_id );

				$this->getProgressBarObject()->set( $this->getAMFMessageID(), $blf->getCurrentRow() );
			}

			$this->getProgressBarObject()->stop( $this->getAMFMessageID() );
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
	function exportCompanyDeduction( $format = 'csv', $data = NULL, $disable_paging = TRUE) {
		$result = $this->stripReturnHandler( $this->getCompanyDeduction( $data, $disable_paging ) );
		return $this->exportRecords( $format, 'export_company_deduction', $result, ( ( isset($data['filter_columns']) ) ? $data['filter_columns'] : NULL ) );
	}

	/**
	 * Get only the fields that are common across all records in the search criteria. Used for Mass Editing of records.
	 * @param array $data filter data
	 * @return array
	 */
	function getCommonCompanyDeductionData( $data ) {
		return Misc::arrayIntersectByRow( $this->stripReturnHandler( $this->getCompanyDeduction( $data, TRUE ) ) );
	}

	/**
	 * Validate company_deduction data for one or more company_deductiones.
	 * @param array $data company_deduction data
	 * @return array
	 */
	function validateCompanyDeduction( $data ) {
		return $this->setCompanyDeduction( $data, TRUE );
	}

	/**
	 * Set company_deduction data for one or more company_deductiones.
	 * @param array $data company_deduction data
	 * @param bool $validate_only
	 * @param bool $ignore_warning
	 * @return array|bool
	 */
	function setCompanyDeduction( $data, $validate_only = FALSE, $ignore_warning = TRUE ) {
		$validate_only = (bool)$validate_only;
		$ignore_warning = (bool)$ignore_warning;

		if ( !is_array($data) ) {
			return $this->returnHandler( FALSE );
		}

		if ( !$this->getPermissionObject()->Check('company_tax_deduction', 'enabled')
				OR !( $this->getPermissionObject()->Check('company_tax_deduction', 'edit') OR $this->getPermissionObject()->Check('company_tax_deduction', 'edit_own') OR $this->getPermissionObject()->Check('company_tax_deduction', 'edit_child') OR $this->getPermissionObject()->Check('company_tax_deduction', 'add') ) ) {
			return	$this->getPermissionObject()->PermissionDenied();
		}

		if ( $validate_only == TRUE ) {
			Debug::Text('Validating Only!', __FILE__, __LINE__, __METHOD__, 10);
		}

		list( $data, $total_records ) = $this->convertToMultipleRecords( $data );
		Debug::Text('Received data for: '. $total_records .' CompanyDeductions', __FILE__, __LINE__, __METHOD__, 10);
		Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

		$validator_stats = array('total_records' => $total_records, 'valid_records' => 0 );
		$validator = $save_result = $key = FALSE;
		if ( is_array($data) AND $total_records > 0 ) {
			$this->getProgressBarObject()->start( $this->getAMFMessageID(), $total_records );

			foreach( $data as $key => $row ) {
				$primary_validator = new Validator();
				$lf = TTnew( 'CompanyDeductionListFactory' ); /** @var CompanyDeductionListFactory $lf */
				$lf->StartTransaction();
				if ( isset($row['id']) AND $row['id'] != '' ) {
					//Modifying existing object.
					//Get company_deduction object, so we can only modify just changed data for specific records if needed.
					$lf->getByIdAndCompanyId( $row['id'], $this->getCurrentCompanyObject()->getId() );
					if ( $lf->getRecordCount() == 1 ) {
						//Object exists, check edit permissions
						if (
							$validate_only == TRUE
							OR
								(
								$this->getPermissionObject()->Check('company_tax_deduction', 'edit')
									OR ( $this->getPermissionObject()->Check('company_tax_deduction', 'edit_own') AND $this->getPermissionObject()->isOwner( $lf->getCurrent()->getCreatedBy(), $lf->getCurrent()->getID() ) === TRUE )
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
					$primary_validator->isTrue( 'permission', $this->getPermissionObject()->Check('company_tax_deduction', 'add'), TTi18n::gettext('Add permission denied') );

					//Because this class has sub-classes that depend on it, when adding a new record we need to make sure the ID is set first,
					//so the sub-classes can depend on it. We also need to call Save( TRUE, TRUE ) to force a lookup on isNew()
					$row['id'] = $lf->getNextInsertId();
				}
				Debug::Arr($row, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

				$is_valid = $primary_validator->isValid( $ignore_warning );
				if ( $is_valid == TRUE ) { //Check to see if all permission checks passed before trying to save data.
					Debug::Text('Setting object data...', __FILE__, __LINE__, __METHOD__, 10);

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

				$this->getProgressBarObject()->set( $this->getAMFMessageID(), $key );
			}

			$this->getProgressBarObject()->stop( $this->getAMFMessageID() );

			return $this->handleRecordValidationResults( $validator, $validator_stats, $key, $save_result );
		}

		return $this->returnHandler( FALSE );
	}

	/**
	 * Delete one or more company_deductions.
	 * @param array $data company_deduction data
	 * @return array|bool
	 */
	function deleteCompanyDeduction( $data ) {
		if ( !is_array($data) ) {
			$data = array($data);
		}

		if ( !is_array($data) ) {
			return $this->returnHandler( FALSE );
		}

		if ( !$this->getPermissionObject()->Check('company_tax_deduction', 'enabled')
				OR !( $this->getPermissionObject()->Check('company_tax_deduction', 'delete') OR $this->getPermissionObject()->Check('company_tax_deduction', 'delete_own') OR $this->getPermissionObject()->Check('company_tax_deduction', 'delete_child') ) ) {
			return	$this->getPermissionObject()->PermissionDenied();
		}

		Debug::Text('Received data for: '. count($data) .' CompanyDeductions', __FILE__, __LINE__, __METHOD__, 10);
		Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

		$total_records = count($data);
		$validator = $save_result = $key = FALSE;
		$validator_stats = array('total_records' => $total_records, 'valid_records' => 0 );
		if ( is_array($data) AND $total_records > 0 ) {
			$this->getProgressBarObject()->start( $this->getAMFMessageID(), $total_records );

			foreach( $data as $key => $id ) {
				$primary_validator = new Validator();
				$lf = TTnew( 'CompanyDeductionListFactory' ); /** @var CompanyDeductionListFactory $lf */
				$lf->StartTransaction();
				if ( $id != '' ) {
					//Modifying existing object.
					//Get company_deduction object, so we can only modify just changed data for specific records if needed.
					$lf->getByIdAndCompanyId( $id, $this->getCurrentCompanyObject()->getId() );
					if ( $lf->getRecordCount() == 1 ) {
						//Object exists, check edit permissions
						if ( $this->getPermissionObject()->Check('company_tax_deduction', 'delete')
								OR ( $this->getPermissionObject()->Check('company_tax_deduction', 'delete_own') AND $this->getPermissionObject()->isOwner( $lf->getCurrent()->getCreatedBy(), $lf->getCurrent()->getID() ) === TRUE ) ) {
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

				$this->getProgressBarObject()->set( $this->getAMFMessageID(), $key );
			}

			$this->getProgressBarObject()->stop( $this->getAMFMessageID() );

			return $this->handleRecordValidationResults( $validator, $validator_stats, $key, $save_result );
		}

		return $this->returnHandler( FALSE );
	}

	/**
	 * Copy one or more company_deductiones.
	 * @param array $data company_deduction IDs
	 * @return array
	 */
	function copyCompanyDeduction( $data ) {
		if ( !is_array($data) ) {
			$data = array($data);
		}

		if ( !is_array($data) ) {
			return $this->returnHandler( FALSE );
		}

		Debug::Text('Received data for: '. count($data) .' CompanyDeductions', __FILE__, __LINE__, __METHOD__, 10);
		Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

		$src_rows = $this->stripReturnHandler( $this->getCompanyDeduction( array('filter_data' => array('id' => $data) ), TRUE ) );
		if ( is_array( $src_rows ) AND count($src_rows) > 0 ) {
			Debug::Arr($src_rows, 'SRC Rows: ', __FILE__, __LINE__, __METHOD__, 10);
			foreach( $src_rows as $key => $row ) {
				unset($src_rows[$key]['id']); //Clear fields that can't be copied
				$src_rows[$key]['name'] = Misc::generateCopyName( $row['name'] ); //Generate unique name
			}
			Debug::Arr($src_rows, 'bSRC Rows: ', __FILE__, __LINE__, __METHOD__, 10);

			return $this->setCompanyDeduction( $src_rows ); //Save copied rows
		}

		return $this->returnHandler( FALSE );
	}

	/**
	 * Returns combined calculation ID for company deduction layers.
	 * @param string $calculation_id , $country, $province
	 * @param null $country
	 * @param null $province
	 * @return string
	 */
	function getCombinedCalculationID( $calculation_id = NULL, $country = NULL, $province = NULL ) {
		$cdf = TTnew( 'CompanyDeductionFactory' ); /** @var CompanyDeductionFactory $cdf */
		//Don't use returnHandler here as its boolean TRUE/FALSE that is returned.
		return $cdf->getCombinedCalculationID( $calculation_id, $country, $province );
	}

	/**
	 * Returns boolean if provided calculation ID is for a country.
	 * @param integer $calculation_id
	 * @return bool
	 */
	function isCountryCalculationID( $calculation_id ) {
		$cdf = TTnew( 'CompanyDeductionFactory' ); /** @var CompanyDeductionFactory $cdf */
		return $cdf->isCountryCalculationID( $calculation_id );
	}
	/**
	 * Returns boolean if provided calculation ID is for a province/state
	 * @param integer $calculation_id
	 * @return bool
	 */
	function isProvinceCalculationID( $calculation_id ) {
		$cdf = TTnew( 'CompanyDeductionFactory' ); /** @var CompanyDeductionFactory $cdf */
		return $cdf->isProvinceCalculationID( $calculation_id );
	}
	/**
	 * Returns boolean if provided calculation ID is for a district.
	 * @param integer $calculation_id
	 * @return bool
	 */
	function isDistrictCalculationID( $calculation_id ) {
		$cdf = TTnew( 'CompanyDeductionFactory' ); /** @var CompanyDeductionFactory $cdf */
		return $cdf->isDistrictCalculationID( $calculation_id );
	}
}
?>
