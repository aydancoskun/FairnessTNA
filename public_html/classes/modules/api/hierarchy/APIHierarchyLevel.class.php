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
 * @package API\Hierarchy
 */
class APIHierarchyLevel extends APIFactory {
	protected $main_class = 'HierarchyLevelFactory';

	public function __construct() {
		parent::__construct(); //Make sure parent constructor is always called.

		return TRUE;
	}

	/**
	 * Get default hierarchy_level data for creating new hierarchy_leveles.
	 * @return array
	 */
	function getHierarchyLevelDefaultData() {
		Debug::Text('Getting hierarchy_level default data...', __FILE__, __LINE__, __METHOD__, 10);

		$data = array(
						'level' => 1,
					);

		return $this->returnHandler( $data );
	}

	/**
	 * Get hierarchy_level and hierarchy_control_ids for authorization list.
	 * @param integer $object_type_id hierarchy object_type_id
	 * @return array
	 */
	function getHierarchyLevelOptions( $object_type_id ) {
		if ( is_array($object_type_id) AND count($object_type_id) > 0 ) {
			$hllf = TTnew( 'HierarchyLevelListFactory' );
			$hierarchy_level_arr = $hllf->getLevelsAndHierarchyControlIDsByUserIdAndObjectTypeID( $this->getCurrentUserObject()->getId(), $object_type_id );
			//Debug::Arr( $hierarchy_level_arr, 'Hierarchy Levels: ', __FILE__, __LINE__, __METHOD__, 10);
			//Debug::Arr( $object_type_id, 'Object Type ID: ', __FILE__, __LINE__, __METHOD__, 10);

			if ( is_array($hierarchy_level_arr) ) {

				$retarr = array();
				foreach( $hierarchy_level_arr as $key => $hierarchy_control_data ) {
					$retarr[$key] = $key;
				}
				unset($hierarchy_control_data); //code standards

				if ( is_array($retarr) ) {
					return $this->returnHandler( $retarr );
				}
			}

			//Return TRUE as no hierarchy levels exist, because if we return FALSE then its considered an error?
			return $this->returnHandler( TRUE );
		}

		Debug::Text('Returning FALSE...', __FILE__, __LINE__, __METHOD__, 10);

		return $this->returnHandler( FALSE );
	}

	/**
	 * Get hierarchy_level data for one or more hierarchy_leveles.
	 * @param array $data filter data
	 * @return array
	 */
	function getHierarchyLevel( $data = NULL, $disable_paging = FALSE ) {
		if ( !$this->getPermissionObject()->Check('hierarchy', 'enabled')
				OR !( $this->getPermissionObject()->Check('hierarchy', 'view') OR $this->getPermissionObject()->Check('hierarchy', 'view_own') OR $this->getPermissionObject()->Check('hierarchy', 'view_child')  ) ) {
			return $this->getPermissionObject()->PermissionDenied();
		}
		$data = $this->initializeFilterAndPager( $data, $disable_paging );

		$data['filter_data']['permission_children_ids'] = $this->getPermissionObject()->getPermissionChildren( 'hierarchy', 'view' );

		$blf = TTnew( 'HierarchyLevelListFactory' );
		$blf->getAPISearchByCompanyIdAndArrayCriteria( $this->getCurrentCompanyObject()->getId(), $data['filter_data'], $data['filter_items_per_page'], $data['filter_page'], NULL, $data['filter_sort'] );
		Debug::Text('Record Count: '. $blf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
		if ( $blf->getRecordCount() > 0 ) {
			$this->getProgressBarObject()->start( $this->getAMFMessageID(), $blf->getRecordCount() );

			$this->setPagerObject( $blf );

			$retarr = array();
			foreach( $blf as $b_obj ) {
				$retarr[] = $b_obj->getObjectAsArray( $data['filter_columns'] );

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
	function getCommonHierarchyLevelData( $data ) {
		return Misc::arrayIntersectByRow( $this->stripReturnHandler( $this->getHierarchyLevel( $data, TRUE ) ) );
	}

	/**
	 * Validate hierarchy_level data for one or more hierarchy_leveles.
	 * @param array $data hierarchy_level data
	 * @return array
	 */
	function validateHierarchyLevel( $data ) {
		return $this->setHierarchyLevel( $data, TRUE );
	}

	/**
	 * Set hierarchy_level data for one or more hierarchy_leveles.
	 * @param array $data hierarchy_level data
	 * @return array
	 */
	function setHierarchyLevel( $data, $validate_only = FALSE, $ignore_warning = TRUE ) {
		$validate_only = (bool)$validate_only;
		$ignore_warning = (bool)$ignore_warning;

		if ( !is_array($data) ) {
			return $this->returnHandler( FALSE );
		}

		if ( !$this->getPermissionObject()->Check('hierarchy', 'enabled')
				OR !( $this->getPermissionObject()->Check('hierarchy', 'edit') OR $this->getPermissionObject()->Check('hierarchy', 'edit_own') OR $this->getPermissionObject()->Check('hierarchy', 'edit_child') OR $this->getPermissionObject()->Check('hierarchy', 'add') ) ) {
			return	$this->getPermissionObject()->PermissionDenied();
		}

		if ( $validate_only == TRUE ) {
			Debug::Text('Validating Only!', __FILE__, __LINE__, __METHOD__, 10);
		}

		extract( $this->convertToMultipleRecords($data) );
		Debug::Text('Received data for: '. $total_records .' HierarchyLevels', __FILE__, __LINE__, __METHOD__, 10);
		Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

		$validator_stats = array('total_records' => $total_records, 'valid_records' => 0 );
		$validator = $save_result = FALSE;
		if ( is_array($data) AND $total_records > 0 ) {
			$this->getProgressBarObject()->start( $this->getAMFMessageID(), $total_records );

			foreach( $data as $key => $row ) {
				$primary_validator = new Validator();
				$lf = TTnew( 'HierarchyLevelListFactory' );
				$lf->StartTransaction();
				if ( isset($row['id']) AND $row['id'] > 0 ) {
					//Modifying existing object.
					//Get hierarchy_level object, so we can only modify just changed data for specific records if needed.
					$lf->getByIdAndCompanyId( $row['id'], $this->getCurrentCompanyObject()->getId() );
					if ( $lf->getRecordCount() == 1 ) {
						//Object exists, check edit permissions
						if (
							$validate_only == TRUE
							OR
								(
								$this->getPermissionObject()->Check('hierarchy', 'edit')
									OR ( $this->getPermissionObject()->Check('hierarchy', 'edit_own') AND $this->getPermissionObject()->isOwner( $lf->getCurrent()->getCreatedBy(), $lf->getCurrent()->getID() ) === TRUE )
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
					$primary_validator->isTrue( 'permission', $this->getPermissionObject()->Check('hierarchy', 'add'), TTi18n::gettext('Add permission denied') );
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
	 * Delete one or more hierarchy_levels.
	 * @param array $data hierarchy_level data
	 * @return array
	 */
	function deleteHierarchyLevel( $data ) {
		if ( is_numeric($data) ) {
			$data = array($data);
		}

		if ( !is_array($data) ) {
			return $this->returnHandler( FALSE );
		}

		if ( !$this->getPermissionObject()->Check('hierarchy', 'enabled')
				OR !( $this->getPermissionObject()->Check('hierarchy', 'delete') OR $this->getPermissionObject()->Check('hierarchy', 'delete_own') OR $this->getPermissionObject()->Check('hierarchy', 'delete_child') ) ) {
			return	$this->getPermissionObject()->PermissionDenied();
		}

		Debug::Text('Received data for: '. count($data) .' HierarchyLevels', __FILE__, __LINE__, __METHOD__, 10);
		Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

		$total_records = count($data);
		$validator = $save_result = FALSE;
		$validator_stats = array('total_records' => $total_records, 'valid_records' => 0 );
		if ( is_array($data) AND $total_records > 0 ) {
			$this->getProgressBarObject()->start( $this->getAMFMessageID(), $total_records );

			foreach( $data as $key => $id ) {
				$primary_validator = new Validator();
				$lf = TTnew( 'HierarchyLevelListFactory' );
				$lf->StartTransaction();
				if ( is_numeric($id) ) {
					//Modifying existing object.
					//Get hierarchy_level object, so we can only modify just changed data for specific records if needed.
					$lf->getByIdAndCompanyId( $id, $this->getCurrentCompanyObject()->getId() );
					if ( $lf->getRecordCount() == 1 ) {
						//Object exists, check edit permissions
						if ( $this->getPermissionObject()->Check('hierarchy', 'delete')
								OR ( $this->getPermissionObject()->Check('hierarchy', 'delete_own') AND $this->getPermissionObject()->isOwner( $lf->getCurrent()->getCreatedBy(), $lf->getCurrent()->getID() ) === TRUE ) ) {
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
	 * ReMaps hierarchy levels so they always start from 1 and don't have any gaps.
	 * @param array $data hierarchy_level data
	 * @return array
	 */
	function ReMapHierarchyLevels( $data ) {
		//Debug::Arr($data, ' aHierarchy Level Data: ', __FILE__, __LINE__, __METHOD__, 10);
		$remapped_levels = HierarchyLevelFactory::ReMapHierarchyLevels( $data );
		//Debug::Arr($remapped_levels, ' ReMapped Levels: ', __FILE__, __LINE__, __METHOD__, 10);

		foreach( $data as $key => $arr ) {
			$data[$key]['level'] = $remapped_levels[$arr['level']];
		}

		Debug::Arr($data, ' ReMapped Hierarchy Level Data: ', __FILE__, __LINE__, __METHOD__, 10);

		return $this->returnHandler( $data );
	}
}
?>
