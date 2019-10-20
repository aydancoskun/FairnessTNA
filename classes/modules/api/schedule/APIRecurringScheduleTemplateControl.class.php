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
 * @package API\Schedule
 */
class APIRecurringScheduleTemplateControl extends APIFactory {
	protected $main_class = 'RecurringScheduleTemplateControlFactory';

	/**
	 * APIRecurringScheduleTemplateControl constructor.
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
				AND ( !$this->getPermissionObject()->Check('recurring_schedule_template', 'enabled')
					OR !( $this->getPermissionObject()->Check('recurring_schedule_template', 'view') OR $this->getPermissionObject()->Check('recurring_schedule_template', 'view_own') OR $this->getPermissionObject()->Check('recurring_schedule_template', 'view_child') ) ) ) {
			$name = 'list_columns';
		}

		return parent::getOptions( $name, $parent );
	}

	/**
	 * Get default recurring_schedule_template_control data for creating new recurring_schedule_template_controles.
	 * @return array
	 */
	function getRecurringScheduleTemplateControlDefaultData() {
		$company_obj = $this->getCurrentCompanyObject();

		Debug::Text('Getting recurring_schedule_template_control default data...', __FILE__, __LINE__, __METHOD__, 10);

		$data = array(
						'company_id' => $company_obj->getId(),
						'created_by_id' => $this->getCurrentUserObject()->getId(),
					);

		return $this->returnHandler( $data );
	}

	/**
	 * Get recurring_schedule_template_control data for one or more recurring_schedule_template_controles.
	 * @param array $data filter data
	 * @param bool $disable_paging
	 * @return array
	 */
	function getRecurringScheduleTemplateControl( $data = NULL, $disable_paging = FALSE ) {
		$data = $this->initializeFilterAndPager( $data, $disable_paging );

		if ( !$this->getPermissionObject()->Check('recurring_schedule_template', 'enabled')
				OR !( $this->getPermissionObject()->Check('recurring_schedule_template', 'view') OR $this->getPermissionObject()->Check('recurring_schedule_template', 'view_own') OR $this->getPermissionObject()->Check('recurring_schedule_template', 'view_child')	) ) {
			//return $this->getPermissionObject()->PermissionDenied();
			$data['filter_columns'] = $this->handlePermissionFilterColumns( (isset($data['filter_columns'])) ? $data['filter_columns'] : NULL, Misc::trimSortPrefix( $this->getOptions('list_columns') ) );
		}

		//Get Permission Hierarchy Children first, as this can be used for viewing, or editing.
		$data['filter_data']['permission_children_ids'] = $this->getPermissionObject()->getPermissionChildren( 'recurring_schedule_template', 'view' );

		$blf = TTnew( 'RecurringScheduleTemplateControlListFactory' ); /** @var RecurringScheduleTemplateControlListFactory $blf */
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
	function exportRecurringScheduleTemplateControl( $format = 'csv', $data = NULL, $disable_paging = TRUE ) {
		$result = $this->stripReturnHandler( $this->getRecurringScheduleTemplateControl( $data, $disable_paging ) );
		return $this->exportRecords( $format, 'export_recurring_schedule_template', $result, ( ( isset($data['filter_columns']) ) ? $data['filter_columns'] : NULL ) );
	}


	/**
	 * Get only the fields that are common across all records in the search criteria. Used for Mass Editing of records.
	 * @param array $data filter data
	 * @return array
	 */
	function getCommonRecurringScheduleTemplateControlData( $data ) {
		return Misc::arrayIntersectByRow( $this->stripReturnHandler( $this->getRecurringScheduleTemplateControl( $data, TRUE ) ) );
	}

	/**
	 * Validate recurring_schedule_template_control data for one or more recurring_schedule_template_controles.
	 * @param array $data recurring_schedule_template_control data
	 * @return array
	 */
	function validateRecurringScheduleTemplateControl( $data ) {
		return $this->setRecurringScheduleTemplateControl( $data, TRUE );
	}

	/**
	 * Set recurring_schedule_template_control data for one or more recurring_schedule_template_controles.
	 * @param array $data recurring_schedule_template_control data
	 * @param bool $validate_only
	 * @param bool $ignore_warning
	 * @return array|bool
	 */
	function setRecurringScheduleTemplateControl( $data, $validate_only = FALSE, $ignore_warning = TRUE ) {
		$validate_only = (bool)$validate_only;
		$ignore_warning = (bool)$ignore_warning;

		if ( !is_array($data) ) {
			return $this->returnHandler( FALSE );
		}

		if ( !$this->getPermissionObject()->Check('recurring_schedule_template', 'enabled')
				OR !( $this->getPermissionObject()->Check('recurring_schedule_template', 'edit') OR $this->getPermissionObject()->Check('recurring_schedule_template', 'edit_own') OR $this->getPermissionObject()->Check('recurring_schedule_template', 'edit_child') OR $this->getPermissionObject()->Check('recurring_schedule_template', 'add') ) ) {
			return	$this->getPermissionObject()->PermissionDenied();
		}

		if ( $validate_only == TRUE ) {
			Debug::Text('Validating Only!', __FILE__, __LINE__, __METHOD__, 10);
		}

		list( $data, $total_records ) = $this->convertToMultipleRecords( $data );
		Debug::Text('Received data for: '. $total_records .' RecurringScheduleTemplateControls', __FILE__, __LINE__, __METHOD__, 10);
		Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

		$validator_stats = array('total_records' => $total_records, 'valid_records' => 0 );
		$validator = $save_result = $key = FALSE;
		if ( is_array($data) AND $total_records > 0 ) {
			$this->getProgressBarObject()->start( $this->getAMFMessageID(), $total_records );

			foreach( $data as $key => $row ) {
				$primary_validator = $tertiary_validator = new Validator();
				$lf = TTnew( 'RecurringScheduleTemplateControlListFactory' ); /** @var RecurringScheduleTemplateControlListFactory $lf */
				$lf->StartTransaction();
				if ( isset($row['id']) AND $row['id'] != '' ) {
					//Modifying existing object.
					//Get recurring_schedule_template_control object, so we can only modify just changed data for specific records if needed.
					$lf->getByIdAndCompanyId( TTUUID::castUUID( $row['id']), $this->getCurrentCompanyObject()->getId() );
					if ( $lf->getRecordCount() == 1 ) {
						//Object exists, check edit permissions
						if (
							$validate_only == TRUE
							OR
								(
								$this->getPermissionObject()->Check('recurring_schedule_template', 'edit')
									OR ( $this->getPermissionObject()->Check('recurring_schedule_template', 'edit_own') AND $this->getPermissionObject()->isOwner( $lf->getCurrent()->getCreatedBy(), $lf->getCurrent()->getID() ) === TRUE )
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
					$primary_validator->isTrue( 'permission', $this->getPermissionObject()->Check('recurring_schedule_template', 'add'), TTi18n::gettext('Add permission denied') );

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

						if ( isset($row['recurring_schedule_template']) ) {
							$recurring_schedule_template_ids = Misc::arrayColumn( $row['recurring_schedule_template'], 'id' );
						} else {
							$recurring_schedule_template_ids = array();
						}

						//Debug::Arr($recurring_schedule_template_ids, 'Template IDs...', __FILE__, __LINE__, __METHOD__, 10);
						if ( count($recurring_schedule_template_ids) > 0 ) {
							//Only delete templates if there are some to delete, and definitely not during a Mass Edit.
							$rstlf = TTnew('RecurringScheduleTemplateListFactory'); /** @var RecurringScheduleTemplateListFactory $rstlf */
							$rstlf->getByRecurringScheduleTemplateControlId( TTUUID::castUUID( $row['id'] ) );
							if ( $rstlf->getRecordCount() > 0 ) {
								foreach( $rstlf as $rst_obj ) {
									if ( !in_array( TTUUID::castUUID($rst_obj->getId()), $recurring_schedule_template_ids ) ) {
										Debug::Text('Removing Template ID: '. $rst_obj->getId(), __FILE__, __LINE__, __METHOD__, 10);
										$rst_obj->Delete( TRUE ); //Disable Audit as records are recreated immediately after.
									}
								}
							}
							unset($rstlf, $rst_obj);
						}
						unset($recurring_schedule_template_ids);

						//Save templates here...
						if ( isset($row['recurring_schedule_template']) AND is_array($row['recurring_schedule_template']) AND count($row['recurring_schedule_template']) > 0 ) {
							$rstlf = TTnew( 'APIRecurringScheduleTemplate' ); /** @var APIRecurringScheduleTemplate $rstlf */
							foreach( $row['recurring_schedule_template'] as $recurring_schedule_template_row ) {
								$recurring_schedule_template_row['recurring_schedule_template_control_id'] = TTUUID::castUUID( $row['id'] );
								$tertiary_validator = $this->convertAPIreturnHandlerToValidatorObject( $rstlf->setRecurringScheduleTemplate( $recurring_schedule_template_row, $validate_only, $ignore_warning ), $tertiary_validator );
								$is_valid = $tertiary_validator->isValid( $ignore_warning );
							}
						}

						if ( $is_valid == TRUE ) {
							if ( $validate_only == TRUE ) {
								$save_result[$key] = TRUE;
							} else {
								$save_result[$key] = $lf->Save( TRUE, TRUE );
							}
							$validator_stats['valid_records']++;
						}
					}
				}

				if ( $is_valid == FALSE ) {
					Debug::Text('Data is Invalid...', __FILE__, __LINE__, __METHOD__, 10);

					$lf->FailTransaction(); //Just rollback this single record, continue on to the rest.

					$validator[$key] = $this->setValidationArray( $primary_validator, $lf, $tertiary_validator );
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
	 * Delete one or more recurring_schedule_template_controls.
	 * @param array $data recurring_schedule_template_control data
	 * @return array|bool
	 */
	function deleteRecurringScheduleTemplateControl( $data ) {
		if ( !is_array($data) ) {
			$data = array($data);
		}

		if ( !is_array($data) ) {
			return $this->returnHandler( FALSE );
		}

		if ( !$this->getPermissionObject()->Check('recurring_schedule_template', 'enabled')
				OR !( $this->getPermissionObject()->Check('recurring_schedule_template', 'delete') OR $this->getPermissionObject()->Check('recurring_schedule_template', 'delete_own') OR $this->getPermissionObject()->Check('recurring_schedule_template', 'delete_child') ) ) {
			return	$this->getPermissionObject()->PermissionDenied();
		}

		Debug::Text('Received data for: '. count($data) .' RecurringScheduleTemplateControls', __FILE__, __LINE__, __METHOD__, 10);
		Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

		$total_records = count($data);
		$validator = $save_result = $key = FALSE;
		$validator_stats = array('total_records' => $total_records, 'valid_records' => 0 );
		if ( is_array($data) AND $total_records > 0 ) {
			$this->getProgressBarObject()->start( $this->getAMFMessageID(), $total_records );

			foreach( $data as $key => $id ) {
				$primary_validator = new Validator();
				$lf = TTnew( 'RecurringScheduleTemplateControlListFactory' ); /** @var RecurringScheduleTemplateControlListFactory $lf */
				$lf->StartTransaction();
				if ( $id != '' ) {
					//Modifying existing object.
					//Get recurring_schedule_template_control object, so we can only modify just changed data for specific records if needed.
					$lf->getByIdAndCompanyId( $id, $this->getCurrentCompanyObject()->getId() );
					if ( $lf->getRecordCount() == 1 ) {
						//Object exists, check edit permissions
						if ( $this->getPermissionObject()->Check('recurring_schedule_template', 'delete')
								OR ( $this->getPermissionObject()->Check('recurring_schedule_template', 'delete_own') AND $this->getPermissionObject()->isOwner( $lf->getCurrent()->getCreatedBy(), $lf->getCurrent()->getID() ) === TRUE ) ) {
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
	 * Copy one or more recurring_schedule_template_controles.
	 * @param array $data recurring_schedule_template_control IDs
	 * @return array
	 */
	function copyRecurringScheduleTemplateControl( $data ) {
		if ( !is_array($data) ) {
			$data = array($data);
		}

		if ( !is_array($data) ) {
			return $this->returnHandler( FALSE );
		}

		Debug::Text('Received data for: '. count($data) .' RecurringScheduleTemplateControls', __FILE__, __LINE__, __METHOD__, 10);
		Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

		$src_rows = $this->stripReturnHandler( $this->getRecurringScheduleTemplateControl( array('filter_data' => array('id' => $data) ), TRUE ) );
		if ( is_array( $src_rows ) AND count($src_rows) > 0 ) {
			Debug::Arr($src_rows, 'SRC Rows: ', __FILE__, __LINE__, __METHOD__, 10);
			$original_ids = array();
			foreach( $src_rows as $key => $row ) {
				$original_ids[$key] = $src_rows[$key]['id'];
				unset($src_rows[$key]['id']); //Clear fields that can't be copied
				$src_rows[$key]['name'] = Misc::generateCopyName( $row['name'] ); //Generate unique name
			}
			//Debug::Arr($src_rows, 'bSRC Rows: ', __FILE__, __LINE__, __METHOD__, 10);

			$retval = $this->setRecurringScheduleTemplateControl( $src_rows ); //Save copied rows

			//Now we need to loop through the result set, and copy the product price records themselves as well.
			if ( empty($original_ids) == FALSE ) {
				Debug::Arr( $original_ids, ' Original IDs: ', __FILE__, __LINE__, __METHOD__, 10 );

				foreach ( $original_ids as $key => $original_id ) {
					$new_id = NULL;
					if ( is_array( $retval ) ) {
						if ( isset( $retval['api_retval'] )
								AND TTUUID::isUUID( $retval['api_retval'] ) AND $retval['api_retval'] != TTUUID::getZeroID() AND $retval['api_retval'] != TTUUID::getNotExistID() ) {
							$new_id = $retval['api_retval'];
						} elseif ( isset( $retval['api_details']['details'][$key] ) ) {
							$new_id = $retval['api_details']['details'][$key];
						}
					} elseif ( TTUUID::isUUID( $retval ) ) {
						$new_id = $retval;
					}

					if ( $new_id !== NULL ) {
						$rstlf = TTnew( 'APIRecurringScheduleTemplate' ); /** @var APIRecurringScheduleTemplate $rstlf */
						$template_src_rows = $this->stripReturnHandler( $rstlf->getRecurringScheduleTemplate( array('filter_data' => array('recurring_schedule_template_control_id' => $original_id) ), TRUE ) );
						if ( is_array( $template_src_rows ) AND count($template_src_rows) > 0 ) {
							//Debug::Arr($template_src_rows, 'TEMPLATE SRC Rows: ', __FILE__, __LINE__, __METHOD__, 10);
							foreach( $template_src_rows as $key_b => $row ) {
								unset($template_src_rows[$key_b]['id']); //Clear fields that can't be copied
								$template_src_rows[$key_b]['recurring_schedule_template_control_id'] = $new_id;
							}

							$rstlf->setRecurringScheduleTemplate( $template_src_rows ); //Save copied rows
						}
					}
				}
			}

			return $this->returnHandler( $retval );
		}

		return $this->returnHandler( FALSE );
	}
}
?>
