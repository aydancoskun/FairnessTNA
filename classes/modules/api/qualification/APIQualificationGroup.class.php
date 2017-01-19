<?php
/*********************************************************************************
 * This file is part of "Fairness", a Payroll and Time Management program.
 * Fairness is Copyright 2013 Aydan Coskun (aydan.ayfer.coskun@gmail.com)
 * Portions of this software are Copyright (C) 2003 - 2013 TimeTrex Software Inc.
 * because Fairness is a fork of "TimeTrex Workforce Management" Software.
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
/*
 * $Revision: 2196 $
 * $Id: APIQualificationGroup.class.php 2196 2008-10-14 16:08:54Z ipso $
 * $Date: 2008-10-14 09:08:54 -0700 (Tue, 14 Oct 2008) $
 */

/**
 * @package API_APIQualificationGroup
 */
class APIQualificationGroup extends APIFactory {
	protected $main_class = 'QualificationGroupFactory';

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
	function getOptions( $name, $parent = NULL ) {
		if ( $name == 'columns'
				AND ( !$this->getPermissionObject()->Check('qualification','enabled')
					OR !( $this->getPermissionObject()->Check('qualification','view') OR $this->getPermissionObject()->Check('qualification','view_own') OR $this->getPermissionObject()->Check('qualification','view_child') ) ) ) {
			$name = 'list_columns';
		}

		return parent::getOptions( $name, $parent );
	}
	
	/**
	 * Get default QualificationGroup data for creating new QualificationGroupes.
	 * @return array
	 */
	function getQualificationGroupDefaultData() {
		$company_obj = $this->getCurrentCompanyObject();

		Debug::Text('Getting QualificationGroup default data...', __FILE__, __LINE__, __METHOD__,10);

		$data = array(
						'company_id' => $company_obj->getId(),
                        'parent_id' => 0,
						'name' => NULL,
					);

		return $this->returnHandler( $data );
	}

	/**
	 * Get QualificationGroup data for one or more QualificationGroupes.
	 * @param array $data filter data
	 * @return array
	 */
	function getQualificationGroup( $data = NULL, $disable_paging = FALSE, $mode = 'flat' ) {	 
		if ( !$this->getPermissionObject()->Check('qualification','enabled')
				OR !( $this->getPermissionObject()->Check('qualification','view') OR $this->getPermissionObject()->Check('qualification','view_own') OR $this->getPermissionObject()->Check('qualification','view_child') ) ) {
			//return $this->getPermissionObject()->PermissionDenied();
			$data['filter_columns'] = $this->handlePermissionFilterColumns( (isset($data['filter_columns'])) ? $data['filter_columns'] : NULL, Misc::trimSortPrefix( $this->getOptions('list_columns') ) );
		     
        }
		$data = $this->initializeFilterAndPager( $data, $disable_paging );

		$data['filter_data']['permission_children_ids'] = $this->getPermissionObject()->getPermissionChildren( 'qualification', 'view' );

		$qglf = TTnew( 'QualificationGroupListFactory' );

		if ( $mode == 'flat' ) {
		   
			$qglf->getAPISearchByCompanyIdAndArrayCriteria( $this->getCurrentCompanyObject()->getId(), $data['filter_data'], $data['filter_items_per_page'], $data['filter_page'], NULL, $data['filter_sort'] );
			Debug::Text('Record Count: '. $qglf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
            
			if ( $qglf->getRecordCount() > 0 ) {
				$this->getProgressBarObject()->start( $this->getAMFMessageID(), $qglf->getRecordCount() );

				$this->setPagerObject( $qglf );

				foreach( $qglf as $ug_obj ) {
					$retarr[] = $ug_obj->getObjectAsArray( $data['filter_columns'], $data['filter_data']['permission_children_ids']  );

					$this->getProgressBarObject()->set( $this->getAMFMessageID(), $qglf->getCurrentRow() );
				}

				$this->getProgressBarObject()->stop( $this->getAMFMessageID() );

				return $this->returnHandler( $retarr );
			}
		} else {
			$nodes = $qglf->getByCompanyIdArray( $this->getCurrentCompanyObject()->getId() );
			//Debug::Arr($nodes, ' Nodes: ', __FILE__, __LINE__, __METHOD__, 10);
			Debug::Text('Record Count: '. count($nodes), __FILE__, __LINE__, __METHOD__, 10);
			if ( isset($nodes) ) {
				//$retarr = $uglf->getArrayByNodes( FastTree::FormatArray( $nodes, 'PLAIN_TEXT', TRUE) );
				$retarr = FastTree::FormatFlexArray( $nodes );
				Debug::Arr($retarr, ' Data: ', __FILE__, __LINE__, __METHOD__, 10);

				//There seems to be a bug with Flex here that if getQualification() and getQualificationGroup() are called at the same time
				//if this function returns an array with the keys out of order (1, 5, 10, rather then 0,1,3,4,5) Flex just sees
				//some empty object.
				//Not sure why this is happening with just this function, but the workaround for now is to call getQualificationGroup()
				//in a separate call to the server.
				//This could have something to do with the array having 0 => ... as the first entry, which we ran into a issue
				//in ExceptionPolicyFactory with getOptions('email_notification')

				return $this->returnHandler( $retarr );
			}
		}

		return $this->returnHandler( TRUE ); //No records returned.
	}

	/**
	 * Get only the fields that are common across all records in the search criteria. Used for Mass Editing of records.
	 * @param array $data filter data
	 * @return array
	 */
	function getCommonQualificationGroupData( $data ) {
		return Misc::arrayIntersectByRow( $this->getQualificationGroup( $data, TRUE ) );
	}

	/**
	 * Validate QualificationGroup data for one or more QualificationGroupes.
	 * @param array $data QualificationGroup data
	 * @return array
	 */
	function validateQualificationGroup( $data ) {
		return $this->setQualificationGroup( $data, TRUE );
	}

	/**
	 * Set QualificationGroup data for one or more QualificationGroupes.
	 * @param array $data QualificationGroup data
	 * @return array
	 */
	function setQualificationGroup( $data, $validate_only = FALSE ) {
		$validate_only = (bool)$validate_only;

		if ( !is_array($data) ) {
			return $this->returnHandler( FALSE );
		}

		if ( !$this->getPermissionObject()->Check('qualification','enabled')
				OR !( $this->getPermissionObject()->Check('qualification','edit') OR $this->getPermissionObject()->Check('qualification','edit_own') OR $this->getPermissionObject()->Check('qualification','edit_child') OR $this->getPermissionObject()->Check('qualification','add') ) ) {
			return  $this->getPermissionObject()->PermissionDenied();
		}

		if ( $validate_only == TRUE ) {
			Debug::Text('Validating Only!', __FILE__, __LINE__, __METHOD__, 10);
            $permission_children_ids = FALSE;
		} else {
            //Get Permission Hierarchy Children first, as this can be used for viewing, or editing.
			$permission_children_ids = $this->getPermissionChildren();
		}

		extract( $this->convertToMultipleRecords($data) );
        
		Debug::Text('Received data for: '. $total_records .' QualificationGroups', __FILE__, __LINE__, __METHOD__, 10);
		Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);
        
		$validator_stats = array('total_records' => $total_records, 'valid_records' => 0 );
		if ( is_array($data) ) {
			foreach( $data as $key => $row ) {
				$primary_validator = new Validator();
				$lf = TTnew( 'QualificationGroupListFactory' );
				$lf->StartTransaction();
                
				if ( isset($row['id']) AND $row['id'] > 0 ) {
					//Modifying existing object.
					//Get QualificationGroup object, so we can only modify just changed data for specific records if needed.                    
					$lf->getByIdAndCompanyId( $row['id'], $this->getCurrentCompanyObject()->getId() );
                    
					if ( $lf->getRecordCount() == 1 ) {
						//Object exists, check edit permissions
						if (
							  $validate_only == TRUE
							  OR
								(
								$this->getPermissionObject()->Check('qualification','edit')
									OR ( $this->getPermissionObject()->Check('qualification','edit_own') AND $this->getPermissionObject()->isOwner( $lf->getCurrent()->getCreatedBy() ) === TRUE )
                                    OR ( $this->getPermissionObject()->Check('qualification','edit_child') AND $this->getPermissionObject()->isChild( $lf->getCurrent()->getCreatedBy(), $permission_children_ids ) === TRUE )
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
					$primary_validator->isTrue( 'permission', $this->getPermissionObject()->Check('qualification','add'), TTi18n::gettext('Add permission denied') );
				}
				Debug::Arr($row, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

				$is_valid = $primary_validator->isValid();
				if ( $is_valid == TRUE ) { //Check to see if all permission checks passed before trying to save data.
					Debug::Text('Setting object data...', __FILE__, __LINE__, __METHOD__, 10);
                    //Force Company ID to current company.
                    $row['company_id'] = $this->getCurrentCompanyObject()->getId();  
                    
					$lf->setObjectFromArray( $row ); 
                    
					$is_valid = $lf->isValid();
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

					if ( $primary_validator->isValid() == FALSE ) {
						$validator[$key] = $primary_validator->getErrorsArray();
					} else {
						$validator[$key] = $lf->Validator->getErrorsArray();
					}
				} elseif ( $validate_only == TRUE ) {
					$lf->FailTransaction();
				}


				$lf->CommitTransaction();
			}

			if ( $validator_stats['valid_records'] > 0 AND $validator_stats['total_records'] == $validator_stats['valid_records'] ) {
				if ( $validator_stats['total_records'] == 1 ) {
					return $this->returnHandler( $save_result[$key] ); //Single valid record
				} else {
					return $this->returnHandler( TRUE, 'SUCCESS', TTi18n::getText('MULTIPLE RECORDS SAVED'), $save_result, $validator_stats ); //Multiple valid records
				}
			} else {
				return $this->returnHandler( FALSE, 'VALIDATION', TTi18n::getText('INVALID DATA'), $validator, $validator_stats );
			}
		}

		return $this->returnHandler( FALSE );
	}

	/**
	 * Delete one or more QualificationGroups.
	 * @param array $data QualificationGroup data
	 * @return array
	 */
	function deleteQualificationGroup( $data ) {
		if ( is_numeric($data) ) {
			$data = array($data);
		}

		if ( !is_array($data) ) {
			return $this->returnHandler( FALSE );
		}

		if ( !$this->getPermissionObject()->Check('qualification','enabled')
				OR !( $this->getPermissionObject()->Check('qualification','delete') OR $this->getPermissionObject()->Check('qualification','delete_own') OR $this->getPermissionObject()->Check('qualification','delete_child') ) ) {
			return  $this->getPermissionObject()->PermissionDenied();
		}
        
        $permission_children_ids = $this->getPermissionChildren();

		Debug::Text('Received data for: '. count($data) .' QualificationGroups', __FILE__, __LINE__, __METHOD__, 10);
		Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

		$total_records = count($data);
        $validator_stats = array('total_records' => $total_records, 'valid_records' => 0 );
		if ( is_array($data) ) {
			foreach( $data as $key => $id ) {
				$primary_validator = new Validator();
				$lf = TTnew( 'QualificationGroupListFactory' );
				$lf->StartTransaction();
				if ( is_numeric($id) ) {
					//Modifying existing object.
					//Get QualificationGroup object, so we can only modify just changed data for specific records if needed.
					$lf->getByIdAndCompanyId( $id, $this->getCurrentCompanyObject()->getId() );
					if ( $lf->getRecordCount() == 1 ) {
						//Object exists, check edit permissions
						if ( $this->getPermissionObject()->Check('qualification','delete')
								OR ( $this->getPermissionObject()->Check('qualification','delete_own') AND $this->getPermissionObject()->isOwner( $lf->getCurrent()->getCreatedBy() ) === TRUE )
                                OR ( $this->getPermissionObject()->Check('qualification','delete_child') AND $this->getPermissionObject()->isChild( $lf->getCurrent()->getCreatedBy(), $permission_children_ids ) === TRUE ) ) {
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

					if ( $primary_validator->isValid() == FALSE ) {
						$validator[$key] = $primary_validator->getErrorsArray();
					} else {
						$validator[$key] = $lf->Validator->getErrorsArray();
					}
				}

				$lf->CommitTransaction();
			}

			if ( $validator_stats['valid_records'] > 0 AND $validator_stats['total_records'] == $validator_stats['valid_records'] ) {
				if ( $validator_stats['total_records'] == 1 ) {
					return $this->returnHandler( $save_result[$key] ); //Single valid record
				} else {
					return $this->returnHandler( TRUE, 'SUCCESS', TTi18n::getText('MULTIPLE RECORDS SAVED'), $save_result, $validator_stats ); //Multiple valid records
				}
			} else {
				return $this->returnHandler( FALSE, 'VALIDATION', TTi18n::getText('INVALID DATA'), $validator, $validator_stats );
			}
		}

		return $this->returnHandler( FALSE );
	}

	/**
	 * Copy one or more QualificationGroupes.
	 * @param array $data QualificationGroup IDs
	 * @return array
	 */
	function copyQualificationGroup( $data ) {
		if ( is_numeric($data) ) {
			$data = array($data);
		}

		if ( !is_array($data) ) {
			return $this->returnHandler( FALSE );
		}

		Debug::Text('Received data for: '. count($data) .' QualificationGroups', __FILE__, __LINE__, __METHOD__, 10);
		Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

		$src_rows = $this->getQualificationGroup( array('filter_data' => array('id' => $data) ), TRUE );
		if ( is_array( $src_rows ) AND count($src_rows) > 0 ) {
			Debug::Arr($src_rows, 'SRC Rows: ', __FILE__, __LINE__, __METHOD__, 10);
			foreach( $src_rows as $key => $row ) {
				unset($src_rows[$key]['id'],$src_rows[$key]['manual_id'] ); //Clear fields that can't be copied
				$src_rows[$key]['name'] = Misc::generateCopyName( $row['name'] ); //Generate unique name
			}
			//Debug::Arr($src_rows, 'bSRC Rows: ', __FILE__, __LINE__, __METHOD__, 10);

			return $this->setQualificationGroup( $src_rows ); //Save copied rows
		}

		return $this->returnHandler( FALSE );
	}
    
    /**
	 * Change parent of one or more groups to another group.
	 * @param array $src_id source Group ID
	 * @param int $dst_id destination Group ID
	 * @return array
	 */
	function dragNdropQualificationGroup( $src_id, $dst_id )	{
		if ( !is_array($src_id) ) {
			$src_id = array($src_id);
		}

		if ( is_array($dst_id) ) {
			return $this->returnHandler( FALSE );
		}

		Debug::Arr($src_id, 'Src ID: Data: ', __FILE__, __LINE__, __METHOD__, 10);
		Debug::Arr($dst_id, 'Dst ID: Data: ', __FILE__, __LINE__, __METHOD__, 10);

		$src_rows = $this->getQualificationGroup( array('filter_data' => array('id' => $src_id ) ), TRUE, 'flat' );
		if ( is_array( $src_rows ) AND count($src_rows) > 0 ) {
			Debug::Arr($src_rows, 'SRC Rows: ', __FILE__, __LINE__, __METHOD__, 10);
			foreach( $src_rows as $key => $row ) {
				$src_rows[$key]['parent_id'] = $dst_id;
			}
			Debug::Arr($src_rows, 'bSRC Rows: ', __FILE__, __LINE__, __METHOD__, 10);

			return $this->setQualificationGroup( $src_rows ); //Save copied rows
		}

		return $this->returnHandler( FALSE );
	}
}
?>
