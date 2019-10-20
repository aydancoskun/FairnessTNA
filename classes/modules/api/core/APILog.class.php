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
 * @package API\Core
 */
class APILog extends APIFactory {
	protected $main_class = 'LogFactory';

	/**
	 * APILog constructor.
	 */
	public function __construct() {
		parent::__construct(); //Make sure parent constructor is always called.

		return TRUE;
	}

	/**
	 * Get log data for one or more logs.
	 * @param array $data filter data
	 * @param bool $disable_paging
	 * @return array
	 */
	function getLog( $data = NULL, $disable_paging = FALSE ) {
		//Check permissions based on the filter table_name.
		//Its important that regular employees can't view the entire log as some sensitive information may be contained within.
		$data = $this->initializeFilterAndPager( $data, $disable_paging );

		$blf = TTnew( 'LogListFactory' ); /** @var LogListFactory $blf */

		if ( isset($data['filter_data']['table_name_object_id']) ) {
			if ( !is_array($data['filter_data']['table_name_object_id']) ) {
				$data['filter_data']['table_name_object_id'] = array($data['filter_data']['table_name_object_id']);
			}

			$filter_table_names = array_keys( $data['filter_data']['table_name_object_id'] );
		} elseif ( isset($data['filter_data']['table_name']) ) {
			if ( !is_array($data['filter_data']['table_name']) ) {
				$data['filter_data']['table_name'] = array($data['filter_data']['table_name']);
			}

			$filter_table_names = $data['filter_data']['table_name'];
		} else {
			Debug::Text('ERROR: No filter table names specified...', __FILE__, __LINE__, __METHOD__, 10);
		}

		if ( isset($filter_table_names) AND count($filter_table_names) > 0 ) {
			$table_name_permission_map = $blf->getOptions('table_name_permission_map');
			foreach( $filter_table_names as $key => $filter_table_name ) {
				if ( isset($table_name_permission_map[$filter_table_name]) ) {
					foreach( $table_name_permission_map[$filter_table_name] as $permission_section ) {
						if ( !( $this->getPermissionObject()->Check( $permission_section, 'enabled')
								AND ( $this->getPermissionObject()->Check( $permission_section, 'edit')
									OR $this->getPermissionObject()->Check( $permission_section, 'edit_child')
								) ) ) {
							//By default administrators have company,edit_own permissions, which means they can't see their own companies audit tab. This is just to be on the safe side.

							//If permission checks fail, force the filter to include the currently logged in user_id, assuming that they can always see audit records created by themselves.
							//This is needed so they can see the audit tab for saved/scheduled reports and at least view when they were sent out.
							if ( $this->getPermissionObject()->Check( $permission_section, 'view_own' ) OR $this->getPermissionObject()->Check( $permission_section, 'edit_own' ) ) {
								Debug::Text( 'Forcing filter to currently logged in user due to audit log table permissions: ' . $filter_table_name . ' Permission Section: ' . $permission_section . ' Key: ' . $key, __FILE__, __LINE__, __METHOD__, 10 );
								$data['filter_data']['user_id'] = $this->getCurrentUserObject()->getId();
							} else {
								Debug::Text('Skipping table name due to permissions: '. $filter_table_name .' Permission Section: '. $permission_section .' Key: '. $key, __FILE__, __LINE__, __METHOD__, 10);
								unset($data['filter_data']['table_name'][$key], $data['filter_data']['table_name_object_id'][$filter_table_name]);
							}
						} else {
							Debug::Text('Allowing table name due to permissions: '. $filter_table_name, __FILE__, __LINE__, __METHOD__, 10);
						}
					}
				} else {
					Debug::Text('Skipping undefined table name: '. $filter_table_name, __FILE__, __LINE__, __METHOD__, 10);
					unset($data['filter_data']['table_name'][$key], $data['filter_data']['table_name_object_id'][$filter_table_name]);
				}
			}
		}

		//Debug::Arr($data, 'Filter data: ', __FILE__, __LINE__, __METHOD__, 10);
		if ( ( isset($data['filter_data']['table_name']) AND count($data['filter_data']['table_name']) == 0 )
				OR ( isset($data['filter_data']['table_name_object_id']) AND count($data['filter_data']['table_name_object_id']) == 0 ) ) {
			Debug::Text('ERROR: No filter table names specified, not returning any records... (b)', __FILE__, __LINE__, __METHOD__, 10);
			return $this->returnHandler( TRUE ); //No records returned.
		}

		//Debug::Arr($data, 'Filter Data: ', __FILE__, __LINE__, __METHOD__, 10);
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
	 * Validate log data for one or more logs.
	 * @param array $data log data
	 * @return array
	 */
	function validateLog( $data ) {
		return $this->setLog( $data, TRUE );
	}

	/**
	 * Set log data for one or more logs.
	 * @param array $data log data
	 * @param bool $validate_only
	 * @param bool $ignore_warning
	 * @return array
	 */
	function setLog( $data, $validate_only = FALSE, $ignore_warning = TRUE ) {
		$validate_only = (bool)$validate_only;
		$ignore_warning = (bool)$ignore_warning;

		if ( !is_array($data) ) {
			return $this->returnHandler( FALSE );
		}
		if ( $validate_only == TRUE ) {
			Debug::Text('Validating Only!', __FILE__, __LINE__, __METHOD__, 10);
		}

		list( $data, $total_records ) = $this->convertToMultipleRecords( $data );
		Debug::Text('Received data for: '. $total_records .' Logs', __FILE__, __LINE__, __METHOD__, 10);
		Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

		$validator_stats = array('total_records' => $total_records, 'valid_records' => 0 );
		$validator = $save_result = $key = FALSE;
		if ( is_array($data) AND $total_records > 0 ) {
			$this->getProgressBarObject()->start( $this->getAMFMessageID(), $total_records );

			foreach( $data as $key => $row ) {
				$primary_validator = new Validator();
				$lf = TTnew( 'LogListFactory' ); /** @var LogListFactory $lf */
				$lf->StartTransaction();

				//Can add log entries only.
				unset($row['id']);
				Debug::Arr($row, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

				Debug::Text('Setting object data...', __FILE__, __LINE__, __METHOD__, 10);

				$lf->setObjectFromArray( $row );

				//Force Company ID to current company.
				$lf->setUser( $this->getCurrentUserObject()->getId() );


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
