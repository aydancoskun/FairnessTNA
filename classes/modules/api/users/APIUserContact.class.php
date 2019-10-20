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
class APIUserContact extends APIFactory {
	protected $main_class = 'UserContactFactory';

	/**
	 * APIUserContact constructor.
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
				AND ( !$this->getPermissionObject()->Check('user_contact', 'enabled')
					OR !( $this->getPermissionObject()->Check('user_contact', 'view') OR $this->getPermissionObject()->Check('user_contact', 'view_own') OR $this->getPermissionObject()->Check('user_contact', 'view_child') ) ) ) {
			$name = 'list_columns';
		}

		return parent::getOptions( $name, $parent );
	}
	/**
	 * Get default user data for creating new users.
	 * @return array
	 */
	function getUserContactDefaultData() {

		//Allow getting default data from other companies, so it makes it easier to create the first employee of a company.
		$company_id = $this->getCurrentCompanyObject()->getId();
		Debug::Text('Getting user contact default data for Company ID: '. $company_id, __FILE__, __LINE__, __METHOD__, 10);

		//Get New Hire Defaults.
		$udlf = TTnew( 'UserDefaultListFactory' ); /** @var UserDefaultListFactory $udlf */
		$udlf->getByCompanyId( $company_id );
		if ( $udlf->getRecordCount() > 0 ) {
			Debug::Text('Using User Defaults, as they exist...', __FILE__, __LINE__, __METHOD__, 10);
			$udf_obj = $udlf->getCurrent();

			$data = array(
							'country' => $udf_obj->getCountry(),
							'province' => $udf_obj->getProvince(),
						);
		}

		if ( !isset( $data['country'] ) ) {
			$data['country'] = 'US';
		}

		return $this->returnHandler( $data );
	}

	/**
	 * Get user data for one or more users.
	 * @param array $data filter data
	 * @param boolean $disable_paging disables paging and returns all records.
	 * @return array|bool
	 */
	function getUserContact( $data = NULL, $disable_paging = FALSE ) {
		$data = $this->initializeFilterAndPager( $data, $disable_paging );

		if ( !$this->getPermissionObject()->Check('user_contact', 'enabled')
				OR !( $this->getPermissionObject()->Check('user_contact', 'view') OR $this->getPermissionObject()->Check('user_contact', 'view_own') OR $this->getPermissionObject()->Check('user_contact', 'view_child')  ) ) {
			return $this->getPermissionObject()->PermissionDenied();
		}

		//Get Permission Hierarchy Children first, as this can be used for viewing, or editing.
		$data['filter_data']['permission_children_ids'] = $this->getPermissionObject()->getPermissionChildren( 'user_contact', 'view' );

		$uclf = TTnew( 'UserContactListFactory' ); /** @var UserContactListFactory $uclf */
		$uclf->getAPISearchByCompanyIdAndArrayCriteria( $this->getCurrentCompanyObject()->getId(), $data['filter_data'], $data['filter_items_per_page'], $data['filter_page'], NULL, $data['filter_sort'] );
		Debug::Text('Record Count: '. $uclf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
		if ( $uclf->getRecordCount() > 0 ) {
			$this->getProgressBarObject()->start( $this->getAMFMessageID(), $uclf->getRecordCount() );

			$this->setPagerObject( $uclf );

			$retarr = array();
			foreach( $uclf as $uc_obj ) {
				$user_data = $uc_obj->getObjectAsArray( $data['filter_columns'], $data['filter_data']['permission_children_ids'] );

				//Hide SIN if user doesn't have permissions to see it.
				if ( isset($user_data['sin']) AND $user_data['sin'] != '' AND $this->getPermissionObject()->Check('user_contact', 'view_sin') == FALSE ) {
					$user_data['sin'] = $uc_obj->getSecureSIN();
				}

				$retarr[] = $user_data;

				$this->getProgressBarObject()->set( $this->getAMFMessageID(), $uclf->getCurrentRow() );
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
	function exportUserContact( $format = 'csv', $data = NULL, $disable_paging = TRUE) {
		$result = $this->stripReturnHandler( $this->getUserContact( $data, $disable_paging ) );
		return $this->exportRecords( $format, 'export_employee_contacts', $result, ( ( isset($data['filter_columns']) ) ? $data['filter_columns'] : NULL ) );
	}


	/**
	 * Get only the fields that are common across all records in the search criteria. Used for Mass Editing of records.
	 * @param array $data filter data
	 * @return array
	 */
	function getCommonUserContactData( $data ) {
		return Misc::arrayIntersectByRow( $this->stripReturnHandler( $this->getUserContact( $data, TRUE ) ) );
	}

	/**
	 * Validate user data for one or more users.
	 * @param array $data user data
	 * @return array
	 */
	function validateUserContact( $data ) {
		return $this->setUserContact( $data, TRUE );
	}

	/**
	 * Set user data for one or more users.
	 * @param array $data user data
	 * @param bool $validate_only
	 * @param bool $ignore_warning
	 * @return array|bool
	 */
	function setUserContact( $data, $validate_only = FALSE, $ignore_warning = TRUE ) {
		$validate_only = (bool)$validate_only;
		$ignore_warning = (bool)$ignore_warning;

		if ( !is_array($data) ) {
			return $this->returnHandler( FALSE );
		}
		if ( !$this->getPermissionObject()->Check('user_contact', 'enabled')
				OR !( $this->getPermissionObject()->Check('user_contact', 'edit') OR $this->getPermissionObject()->Check('user_contact', 'edit_own') OR $this->getPermissionObject()->Check('user_contact', 'edit_child') OR $this->getPermissionObject()->Check('user_contact', 'add') ) ) {
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
		Debug::Text('Received data for: '. $total_records .' Users', __FILE__, __LINE__, __METHOD__, 10);
		//Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

		$validator_stats = array('total_records' => $total_records, 'valid_records' => 0 );
		$validator = $save_result = $key = FALSE;
		if ( is_array($data) AND $total_records > 0 ) {
			$this->getProgressBarObject()->start( $this->getAMFMessageID(), $total_records );

			foreach( $data as $key => $row ) {
				$primary_validator = new Validator();
				$lf = TTnew( 'UserContactListFactory' ); /** @var UserContactListFactory $lf */
				$lf->StartTransaction();
				if ( isset($row['id']) AND $row['id'] != '' ) {
					//Modifying existing object.
					//Get user object, so we can only modify just changed data for specific records if needed.
					$lf->getByIdAndCompanyId( $row['id'], $this->getCurrentCompanyObject()->getId() );
					if ( $lf->getRecordCount() == 1 ) {
						//Object exists, check edit permissions
						//Debug::Text('User ID: '. $row['id'] .' Created By: '. $lf->getCurrent()->getCreatedBy() .' Is Owner: '. (int)$this->getPermissionObject()->isOwner( $lf->getCurrent()->getCreatedBy(), $lf->getCurrent()->getID() ) .' Is Child: '. (int)$this->getPermissionObject()->isChild( $lf->getCurrent()->getId(), $permission_children_ids ), __FILE__, __LINE__, __METHOD__, 10);
						if (
							$validate_only == TRUE
							OR
								(
								$this->getPermissionObject()->Check('user_contact', 'edit')
									OR ( $this->getPermissionObject()->Check('user_contact', 'edit_own') AND $this->getPermissionObject()->isOwner( $lf->getCurrent()->getCreatedBy(), $lf->getCurrent()->getUser() ) === TRUE )
									OR ( $this->getPermissionObject()->Check('user_contact', 'edit_child') AND $this->getPermissionObject()->isChild( $lf->getCurrent()->getUser(), $permission_children_ids ) === TRUE )
								) ) {

							Debug::Text('Row Exists, getting current data for ID: '. $row['id'], __FILE__, __LINE__, __METHOD__, 10);
							$lf = $lf->getCurrent(); //Make the current $lf variable the current object, so we can ignore some fields if needed.
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
								( $this->getPermissionObject()->Check('user_contact', 'add')
									AND
									(
										$this->getPermissionObject()->Check('user_contact', 'edit')
										OR ( isset($row['user_id']) AND $this->getPermissionObject()->Check('user_contact', 'edit_own') AND $this->getPermissionObject()->isOwner( FALSE, $row['user_id'] ) === TRUE ) //We don't know the created_by of the user at this point, but only check if the user is assigned to the logged in person.
										OR ( isset($row['user_id']) AND $this->getPermissionObject()->Check('user_contact', 'edit_child') AND $this->getPermissionObject()->isChild( $row['user_id'], $permission_children_ids ) === TRUE )
									)
								)
							) ) {
						$primary_validator->isTrue( 'permission', FALSE, TTi18n::gettext('Add permission denied') );
					}
				}

				//Debug::Arr($row, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

				$is_valid = $primary_validator->isValid( $ignore_warning );
				if ( $is_valid == TRUE ) { //Check to see if all permission checks passed before trying to save data.
					Debug::Text('Attempting to save data... AMF Message ID: '. $this->getAMFMessageID(), __FILE__, __LINE__, __METHOD__, 10);

					if ( DEMO_MODE == TRUE AND $lf->isNew() == FALSE ) { //Allow changing these if DEMO is enabled, but they are adding new records.
						Debug::Text('DEMO Mode ENABLED, disable modifying some data...', __FILE__, __LINE__, __METHOD__, 10);
						unset($row['status_id']);
					}

					//If the user doesn't have permissions to change the hierarchy_control, unset that data.

					//Force Company ID to current company.
					if ( !isset($row['company_id']) OR !$this->getPermissionObject()->Check('company', 'add') ) {
						//$lf->setCompany( $this->getCurrentCompanyObject()->getId() );
						$row['company_id'] = $this->getCurrentCompanyObject()->getId();
					}

					$lf->setObjectFromArray( $row );

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
					//Always fail transaction when valididate only is used, as	is saved to different tables immediately.
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
	 * Delete one or more users.
	 * @param array $data user data
	 * @return array|bool
	 */
	function deleteUserContact( $data ) {
		if ( !is_array($data) ) {
			$data = array($data);
		}

		if ( !is_array($data) ) {
			return $this->returnHandler( FALSE );
		}

		if ( !$this->getPermissionObject()->Check('user_contact', 'enabled')
				OR !( $this->getPermissionObject()->Check('user_contact', 'delete') OR $this->getPermissionObject()->Check('user_contact', 'delete_own') OR $this->getPermissionObject()->Check('user_contact', 'delete_child') ) ) {
			return	$this->getPermissionObject()->PermissionDenied();
		}

		//Get Permission Hierarchy Children first, as this can be used for viewing, or editing.
		$permission_children_ids = $this->getPermissionChildren();

		Debug::Text('Received data for: '. count($data) .' Users', __FILE__, __LINE__, __METHOD__, 10);
		Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

		$total_records = count($data);
		$validator = $save_result = $key = FALSE;
		$validator_stats = array('total_records' => $total_records, 'valid_records' => 0 );
		if ( is_array($data) AND $total_records > 0 ) {
			$this->getProgressBarObject()->start( $this->getAMFMessageID(), $total_records );

			foreach( $data as $key => $id ) {
				$primary_validator = new Validator();
				$lf = TTnew( 'UserContactListFactory' ); /** @var UserContactListFactory $lf */
				$lf->StartTransaction();
				if ( $id != '' ) {
					//Modifying existing object.
					//Get user object, so we can only modify just changed data for specific records if needed.
					$lf->getByIdAndCompanyId( $id, $this->getCurrentCompanyObject()->getId() );
					if ( $lf->getRecordCount() == 1 ) {
						//Object exists, check edit permissions
						//Debug::Text('User ID: '. $user['id'] .' Created By: '. $lf->getCurrent()->getCreatedBy() .' Is Owner: '. (int)$this->getPermissionObject()->isOwner( $lf->getCurrent()->getCreatedBy(), $lf->getCurrent()->getID() ) .' Is Child: '. (int)$this->getPermissionObject()->isChild( $lf->getCurrent()->getId(), $permission_children_ids ), __FILE__, __LINE__, __METHOD__, 10);
						if ( $this->getPermissionObject()->Check('user_contact', 'delete')
								OR ( $this->getPermissionObject()->Check('user_contact', 'delete_own') AND $this->getPermissionObject()->isOwner( $lf->getCurrent()->getCreatedBy(), $lf->getCurrent()->getUser() ) === TRUE )
								OR ( $this->getPermissionObject()->Check('user_contact', 'delete_child') AND $this->getPermissionObject()->isChild( $lf->getCurrent()->getUser(), $permission_children_ids ) === TRUE )) {

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
	 * Copy one or more users.
	 * @param array $data user data
	 * @return array
	 */
	function copyUserContact( $data ) {
		//Can only Copy as New, not just a regular copy, as too much data needs to be changed,
		//such as username, password, employee_number, SIN, first/last name address...
		return $this->returnHandler( FALSE );
	}
}
?>
