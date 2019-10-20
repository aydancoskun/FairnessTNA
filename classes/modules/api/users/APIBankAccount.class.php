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
 * @package API\Users
 */
class APIBankAccount extends APIFactory {
	protected $main_class = 'BankAccountFactory';

	/**
	 * APIBankAccount constructor.
	 */
	public function __construct() {
		parent::__construct(); //Make sure parent constructor is always called.

		return TRUE;
	}

	/**
	 * Get default bank account data for creating new bank accountes.
	 * @param string $user_id UUID
	 * @return array
	 */
	function getBankAccountDefaultData( $user_id = NULL ) {
		$company_obj = $this->getCurrentCompanyObject();

		Debug::Text('Getting wage default data...', __FILE__, __LINE__, __METHOD__, 10);

		//If user_id is passed, check for other wage entries, if none, default to the employees hire date.

		$data = array(
						'company_id' => $company_obj->getId(),
					);

		return $this->returnHandler( $data );
	}

	/**
	 * Get bank account data for one or more bank accountes.
	 * @param array $data filter data
	 * @param bool $disable_paging
	 * @return array|bool
	 */
	function getBankAccount( $data = NULL, $disable_paging = FALSE ) {
		$data = $this->initializeFilterAndPager( $data, $disable_paging );

		if ( !$this->getPermissionObject()->Check('user', 'enabled')
				OR !( $this->getPermissionObject()->Check('user', 'edit_bank') OR $this->getPermissionObject()->Check('user', 'edit_own_bank') OR $this->getPermissionObject()->Check('user', 'edit_child_bank')  ) ) {
			return $this->getPermissionObject()->PermissionDenied();
		}

		//Get Permission Hierarchy Children first, as this can be used for viewing, or editing.
		if ( $this->getPermissionObject()->Check('user', 'edit_bank') == TRUE ) {
			//Don't set permission_children_ids.
			$data['filter_data']['permission_children_ids'] = NULL;
		} else {
			$data['filter_data']['permission_children_ids'] = array(); //#2243 - Make sure permission_children_ids is overwritten entirely to avoid security issues of appending to an array partially passed by user and partially set here.
			if ( $this->getPermissionObject()->Check('user', 'edit_child_bank') == TRUE ) {
				//Manually handle the permission checks here because edit_child_bank doesn't fit with getPermissionChildren() appending "_own" or "_child" on the end.
				$data['filter_data']['permission_children_ids'] = $this->getPermissionObject()->getPermissionHierarchyChildren( $this->getCurrentCompanyObject()->getId(), $this->getCurrentUserObject()->getId() );
			}

			if ( $this->getPermissionObject()->Check('user', 'edit_own_bank') == TRUE  ) {
				$data['filter_data']['permission_children_ids'][] = $this->getCurrentUserObject()->getId();
			}

			Debug::Arr($data['filter_data']['permission_children_ids'], 'Permission Children: ', __FILE__, __LINE__, __METHOD__, 10);
		}

		$blf = TTnew( 'BankAccountListFactory' ); /** @var BankAccountListFactory $blf */
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

			Debug::Arr($retarr, 'Record Count: '. $blf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
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
	function exportBankAccount( $format = 'csv', $data = NULL, $disable_paging = TRUE ) {
		$result = $this->stripReturnHandler( $this->getBankAccount( $data, $disable_paging ) );
		return $this->exportRecords( $format, 'export_bank_account', $result, ( ( isset($data['filter_columns']) ) ? $data['filter_columns'] : NULL ) );
	}

	/**
	 * Get only the fields that are common across all records in the search criteria. Used for Mass Editing of records.
	 * @param array $data filter data
	 * @return array
	 */
	function getCommonBankAccountData( $data ) {
		return Misc::arrayIntersectByRow( $this->stripReturnHandler( $this->getBankAccount( $data, TRUE ) ) );
	}

	/**
	 * Validate bank account information.
	 * @param array $data bank account data
	 * @return array
	 */
	function validateBankAccount( $data ) {
		return $this->setBankAccount( $data, TRUE );
	}

	/**
	 * Set bank account data for one or more bank accountes.
	 * @param array $data bank account data
	 * @param bool $validate_only
	 * @param bool $ignore_warning
	 * @return array|bool
	 */
	function setBankAccount( $data, $validate_only = FALSE, $ignore_warning = TRUE ) {
		$validate_only = (bool)$validate_only;
		$ignore_warning = (bool)$ignore_warning;

		if ( !is_array($data) ) {
			return $this->returnHandler( FALSE );
		}

		if ( !$this->getPermissionObject()->Check('user', 'enabled')
				OR !( $this->getPermissionObject()->Check('user', 'edit_bank') OR $this->getPermissionObject()->Check('user', 'edit_own_bank') OR $this->getPermissionObject()->Check('user', 'edit_child_bank') ) ) {
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
		Debug::Text('Received data for: '. $total_records .' BankAccounts', __FILE__, __LINE__, __METHOD__, 10);
		Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

		$validator_stats = array('total_records' => $total_records, 'valid_records' => 0 );
		$validator = $save_result = $key = FALSE;
		if ( is_array($data) AND $total_records > 0 ) {
			$this->getProgressBarObject()->start( $this->getAMFMessageID(), $total_records );

			foreach( $data as $key => $row ) {
				$primary_validator = new Validator();
				$lf = TTnew( 'BankAccountListFactory' ); /** @var BankAccountListFactory $lf */
				$lf->StartTransaction();
				if ( isset($row['id']) AND $row['id'] != '' ) {
					//Modifying existing object.
					//Get wage object, so we can only modify just changed data for specific records if needed.
					$lf->getByIdAndCompanyId( $row['id'], $this->getCurrentCompanyObject()->getId() );
					if ( $lf->getRecordCount() == 1 ) {
						//Object exists, check edit permissions
						if (
							$validate_only == TRUE
							OR
								(
								$this->getPermissionObject()->Check('user', 'edit_bank')
									OR ( $this->getPermissionObject()->Check('user', 'edit_own_bank') AND $this->getPermissionObject()->isOwner( $lf->getCurrent()->getCreatedBy(), $lf->getCurrent()->getUser() ) === TRUE )
									OR ( $this->getPermissionObject()->Check('user', 'edit_child_bank') AND $this->getPermissionObject()->isChild( $lf->getCurrent()->getUser(), $permission_children_ids ) === TRUE )
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
				} //else {
					//Adding new object, check ADD permissions.
					//$primary_validator->isTrue( 'permission', $this->getPermissionObject()->Check('user', 'add'), TTi18n::gettext('Add permission denied') );
				//}
				//Debug::Arr($row, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

				$is_valid = $primary_validator->isValid( $ignore_warning );
				if ( $is_valid == TRUE ) { //Check to see if all permission checks passed before trying to save data.
					Debug::Text('Setting object data...', __FILE__, __LINE__, __METHOD__, 10);

					if ( ( $row['user_id'] != '' AND $row['user_id'] == $this->getCurrentUserObject()->getId() ) AND $row['company_id'] == '' AND $this->getPermissionObject()->Check('user', 'edit_own_bank') ) {
						Debug::Text('Current User/Company', __FILE__, __LINE__, __METHOD__, 10);
						//Current user
						$row['company_id'] = $this->getCurrentCompanyObject()->getId();
						$row['user_id'] = $this->getCurrentUserObject()->getId();
					} elseif ( $row['user_id'] != '' AND ( $row['company_id'] == '' OR $row['company_id'] == $this->getCurrentCompanyObject()->getId() ) AND $this->getPermissionObject()->Check('user', 'edit_child_bank') ) {
						Debug::Text('Specified Child User', __FILE__, __LINE__, __METHOD__, 10);
						//Specified User
						$row['company_id'] = $this->getCurrentCompanyObject()->getId();
					} elseif ( $row['user_id'] != '' AND ( $row['company_id'] == '' OR $row['company_id'] == $this->getCurrentCompanyObject()->getId() ) AND $this->getPermissionObject()->Check('user', 'edit_bank') ) {
						Debug::Text('Specified User', __FILE__, __LINE__, __METHOD__, 10);
						//Specified User
						$row['company_id'] = $this->getCurrentCompanyObject()->getId();
					} elseif ( $row['company_id'] != '' AND $row['user_id'] == '' AND $this->getPermissionObject()->Check('company', 'edit_own_bank') ) {
						Debug::Text('Specified Company', __FILE__, __LINE__, __METHOD__, 10);
						//Company bank.
						$row['company_id'] = $this->getCurrentCompanyObject()->getId();
					} else {
						Debug::Text('No Company or User ID specified...', __FILE__, __LINE__, __METHOD__, 10);
						//Assume its always the currently logged in users account.
						$row['company_id'] = $this->getCurrentCompanyObject()->getId();

						if ( !isset($row['user_id']) OR $this->getPermissionObject()->Check('company', 'edit_bank') == FALSE ) {
							$row['user_id'] = $this->getCurrentUserObject()->getId();
						}
					}

					$lf->setObjectFromArray( $row );

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
	 * Delete one or more bank accounts.
	 * @param array $data bank account data
	 * @return array|bool
	 */
	function deleteBankAccount( $data ) {
		if ( !is_array($data) ) {
			$data = array($data);
		}

		if ( !is_array($data) ) {
			return $this->returnHandler( FALSE );
		}

		if ( !$this->getPermissionObject()->Check('user', 'enabled')
				OR !( $this->getPermissionObject()->Check('user', 'edit_bank') OR $this->getPermissionObject()->Check('user', 'edit_own_bank') OR $this->getPermissionObject()->Check('user', 'edit_child_bank') ) ) {
			return	$this->getPermissionObject()->PermissionDenied();
		}

		//Get Permission Hierarchy Children first, as this can be used for viewing, or editing.
		$permission_children_ids = $this->getPermissionChildren();

		Debug::Text('Received data for: '. count($data) .' BankAccounts', __FILE__, __LINE__, __METHOD__, 10);
		Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

		$total_records = count($data);
		$validator = $save_result = $key = FALSE;
		$validator_stats = array('total_records' => $total_records, 'valid_records' => 0 );
		if ( is_array($data) AND $total_records > 0 ) {
			$this->getProgressBarObject()->start( $this->getAMFMessageID(), $total_records );

			foreach( $data as $key => $id ) {
				$primary_validator = new Validator();
				$lf = TTnew( 'BankAccountListFactory' ); /** @var BankAccountListFactory $lf */
				$lf->StartTransaction();
				if ( $id != '' ) {
					//Modifying existing object.
					//Get bank account object, so we can only modify just changed data for specific records if needed.
					$lf->getByIdAndCompanyId( $id, $this->getCurrentCompanyObject()->getId() );
					if ( $lf->getRecordCount() == 1 ) {
						//Object exists, check edit permissions
						if (	$this->getPermissionObject()->Check('user', 'edit_bank')
									OR ( $this->getPermissionObject()->Check('user', 'edit_own_bank') AND $this->getPermissionObject()->isOwner( $lf->getCurrent()->getCreatedBy(), $lf->getCurrent()->getUser() ) === TRUE )
									OR ( $this->getPermissionObject()->Check('user', 'edit_child_bank') AND $this->getPermissionObject()->isChild( $lf->getCurrent()->getId(), $permission_children_ids ) === TRUE )
							) {
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
}
?>
