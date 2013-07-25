<?php
/*********************************************************************************
 * This file is part of "Fairness", a Payroll and Time Management program.
 * Fairness is Copyright 2013 Aydan Coscun (aydan.ayfer.coskun@gmail.com)
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
 * $Id: APIBranch.class.php 2196 2008-10-14 16:08:54Z ipso $
 * $Date: 2008-10-14 09:08:54 -0700 (Tue, 14 Oct 2008) $
 */

/**
 * @package API\Users
 */
class APIUserGenericStatus extends APIFactory {
	protected $main_class = 'UserGenericStatusFactory';

	public function __construct() {
		parent::__construct(); //Make sure parent constructor is always called.

		return TRUE;
	}


	/**
	 * Get user generic status data for one or more .
	 * @param array $data filter data
	 * @return array
	 */
	function getUserGenericStatus( $data = NULL, $disable_paging = FALSE ) {

		$data = $this->initializeFilterAndPager( $data, $disable_paging );

        $user_id = $this->getCurrentUserObject()->getId();
        if ( $data['filter_data']['batch_id'] != '' ) {

            $batch_id = $data['filter_data']['batch_id'];
            $ugslf = TTnew( 'UserGenericStatusListFactory' );
            $ugslf->getByUserIdAndBatchId( $user_id, $batch_id,  $data['filter_items_per_page'],  $data['filter_page'], NULL, $data['filter_sort'] );

            Debug::Text('Record Count: '. $ugslf->getRecordCount(), __FILE__, __LINE__, __METHOD__,10);

            if ( $ugslf->getRecordCount() > 0 ) {
                //$status_count_arr = $ugslf->getStatusCountArrayByUserIdAndBatchId( $user_id, $batch_id );

                $this->getProgressBarObject()->start( $this->getAMFMessageID(), $ugslf->getRecordCount() );
			    $this->setPagerObject( $ugslf );

				foreach ($ugslf as $ugs_obj) {
					$rows[] = array(
										'id' => $ugs_obj->getId(),
										'user_id' => $ugs_obj->getUser(),
										'batch_id' => $ugs_obj->getBatchId(),
										'status_id' => $ugs_obj->getStatus(),
										'status' => Option::getByKey( $ugs_obj->getStatus(), $ugs_obj->getOptions('status') ),
										'label' => $ugs_obj->getLabel(),
										'description' => $ugs_obj->getDescription(),
										'link' => $ugs_obj->getLink(),
										'deleted' => $ugs_obj->getDeleted()
									);
                    $this->getProgressBarObject()->set( $this->getAMFMessageID(), $ugslf->getCurrentRow() );
				}

                $this->getProgressBarObject()->stop( $this->getAMFMessageID() );

                return $this->returnHandler( $rows );

			} else {
			     return $this->returnHandler( TRUE ); //No records returned.
			}
        } else {
            return $this->returnHandler( TRUE ); //No records returned.
        }

	}

	/**
	 * Delete one or more user generic status data.
	 * @param array $data
	 * @return array
	 */
	function deleteUserGenericStatus( $data ) {
		if ( is_numeric($data) ) {
			$data = array($data);
		}

		if ( !is_array($data) ) {
			return $this->returnHandler( FALSE );
		}

		Debug::Text('Received data for: '. count($data) .' User Generic Status', __FILE__, __LINE__, __METHOD__, 10);
		Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

		$total_records = count($data);
        $validator_stats = array('total_records' => $total_records, 'valid_records' => 0 );
		if ( is_array($data) ) {
			$this->getProgressBarObject()->start( $this->getAMFMessageID(), $total_records );

			foreach( $data as $key => $id ) {
				$primary_validator = new Validator();
				$lf = TTnew( 'UserGenericStatusListFactory' );
				$lf->StartTransaction();
				if ( is_numeric($id) ) {
					//Modifying existing object.
					//Get branch object, so we can only modify just changed data for specific records if needed.
					$lf->getByIdAndCompanyId( $id, $this->getCurrentCompanyObject()->getId() );
					if ( $lf->getRecordCount() == 1 ) {
						//Object exists, check edit permissions
						if ( $this->getPermissionObject()->Check('user','delete')
								OR ( $this->getPermissionObject()->Check('user','delete_own') AND $this->getPermissionObject()->isOwner( $lf->getCurrent()->getCreatedBy(), $lf->getCurrent()->getID() ) === TRUE ) ) {
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

				$this->getProgressBarObject()->set( $this->getAMFMessageID(), $key );
			}

			$this->getProgressBarObject()->stop( $this->getAMFMessageID() );

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

	function getUserGenericStatusCountArray($user_id, $batch_id) {
        $user_id = $this->getCurrentUserObject()->getId();
        if ( $batch_id != '' ) {
            $ugslf = TTnew( 'UserGenericStatusListFactory' );
			$status_count_arr = $ugslf->getStatusCountArrayByUserIdAndBatchId( $user_id, $batch_id );

			return $this->returnHandler( $status_count_arr );
		}

		return $this->returnHandler( FALSE );
	}
}
?>
