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
 * @package API\PayStub
 */
class APIPayStubEntry extends APIFactory {
	protected $main_class = 'PayStubEntryFactory';

	/**
	 * APIPayStubEntry constructor.
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
			AND ( !$this->getPermissionObject()->Check('pay_stub', 'enabled')
				OR !( $this->getPermissionObject()->Check('pay_stub', 'view') OR $this->getPermissionObject()->Check('pay_stub', 'view_own') OR $this->getPermissionObject()->Check('pay_stub', 'view_child') ) ) ) {
			$name = 'list_columns';
		}

		return parent::getOptions( $name, $parent );
	}

	/**
	 * Get default paystub_entry_account data for creating new paystub_entry_accountes.
	 * @return array
	 */
	function getPayStubEntryDefaultData() {
		Debug::Text('Getting pay stub entry default data...', __FILE__, __LINE__, __METHOD__, 10);

		return $this->returnHandler( FALSE );
	}

	/**
	 * Get paystub_entry_account data for one or more paystub_entry_accountes.
	 * @param array $data filter data
	 * @param bool $disable_paging
	 * @return array|bool
	 */
	function getPayStubEntry( $data = NULL, $disable_paging = FALSE ) {
		$data = $this->initializeFilterAndPager( $data, $disable_paging );

		if ( !$this->getPermissionObject()->Check('pay_stub', 'enabled')
			OR !( $this->getPermissionObject()->Check('pay_stub', 'view') OR $this->getPermissionObject()->Check('pay_stub', 'view_child')	) ) {
			return $this->getPermissionObject()->PermissionDenied();
		}

		$data['filter_data']['permission_children_ids'] = $this->getPermissionObject()->getPermissionChildren( 'pay_stub', 'view' );

		$pself = TTnew( 'PayStubEntryListFactory' ); /** @var PayStubEntryListFactory $pself */
		$pself->getAPISearchByCompanyIdAndArrayCriteria( $this->getCurrentCompanyObject()->getId(), $data['filter_data'], $data['filter_items_per_page'], $data['filter_page'], NULL, $data['filter_sort'] );
		Debug::Text('Record Count: '. $pself->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
		if ( $pself->getRecordCount() > 0 ) {
			$this->setPagerObject( $pself );
			$retarr = array();
			foreach( $pself as $pse_obj ) {

				$retarr[] = $pse_obj->getObjectAsArray( $data['filter_columns'] );
			}

			return $this->returnHandler( $retarr );
		}
		return $this->returnHandler( TRUE ); //No records returned.
	}

	/**
	 * Get only the fields that are common across all records in the search criteria. Used for Mass Editing of records.
	 * @param array $data filter data
	 * @return array
	 */
	function getCommonPayStubEntryData( $data ) {
		return Misc::arrayIntersectByRow( $this->stripReturnHandler( $this->getPayStubEntry( $data, TRUE ) ) );
	}

	/**
	 * Validate paystub_entry_account data for one or more paystub_entry_accountes.
	 * @param array $data paystub_entry_account data
	 * @return array
	 */
	function validatePayStubEntry( $data ) {
		return $this->setPayStubEntry( $data, TRUE );
	}

	/**
	 * Delete one or more paystub_entry_accounts.
	 * @param array $data paystub_entry_account data
	 * @return array|bool
	 */
//	function deletePayStubEntry( $data ) {
//		//
//		//This is required by Edit Pay Stub view to delete individual Pay Stub entries.
//		//  FIXME: It is broken though, since if they delete a pay stub entry, then a validation error occurs, the pay stub totals are out of sync.
//		//
//		if ( !is_array($data) ) {
//			$data = array($data);
//		}
//
//		if ( !is_array($data) ) {
//			return $this->returnHandler( FALSE );
//		}
//
//		if ( !$this->getPermissionObject()->Check('pay_stub', 'enabled')
//			OR !( $this->getPermissionObject()->Check('pay_stub', 'delete') OR $this->getPermissionObject()->Check('pay_stub', 'delete_own') OR $this->getPermissionObject()->Check('pay_stub', 'delete_child') ) ) {
//			return	$this->getPermissionObject()->PermissionDenied();
//		}
//
//		Debug::Text('Received data for: '. count($data) .' PayStubEntrys', __FILE__, __LINE__, __METHOD__, 10);
//		Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);
//
//		$total_records = count($data);
//		$validator = $save_result = $key = FALSE;
//		$validator_stats = array('total_records' => $total_records, 'valid_records' => 0 );
//		if ( is_array($data) AND $total_records > 0 ) {
//			foreach( $data as $key => $id ) {
//				$primary_validator = new Validator();
//				$lf = TTnew( 'PayStubEntryListFactory' );
//				$lf->StartTransaction();
//				if ( $id != '' ) {
//					//Modifying existing object.
//					//Get paystub_entry_account object, so we can only modify just changed data for specific records if needed.
//					$lf->getByIdAndCompanyId( $id, $this->getCurrentCompanyObject()->getId() );
//					if ( $lf->getRecordCount() == 1 ) {
//						//Object exists, check edit permissions
//						if ( $this->getPermissionObject()->Check('pay_stub', 'delete')
//							OR ( $this->getPermissionObject()->Check('pay_stub', 'delete_own') AND $this->getPermissionObject()->isOwner( $lf->getCurrent()->getCreatedBy(), $lf->getCurrent()->getID() ) === TRUE ) ) {
//							Debug::Text('Record Exists, deleting record ID: '. $id, __FILE__, __LINE__, __METHOD__, 10);
//							$lf = $lf->getCurrent();
//						} else {
//							$primary_validator->isTrue( 'permission', FALSE, TTi18n::gettext('Delete permission denied') );
//						}
//					} else {
//						//Object doesn't exist.
//						$primary_validator->isTrue( 'id', FALSE, TTi18n::gettext('Delete permission denied, record does not exist') );
//					}
//				} else {
//					$primary_validator->isTrue( 'id', FALSE, TTi18n::gettext('Delete permission denied, record does not exist') );
//				}
//
//				//Debug::Arr($lf, 'AData: ', __FILE__, __LINE__, __METHOD__, 10);
//
//				$is_valid = $primary_validator->isValid();
//				if ( $is_valid == TRUE ) { //Check to see if all permission checks passed before trying to save data.
//					Debug::Text('Attempting to delete record...', __FILE__, __LINE__, __METHOD__, 10);
//					$lf->setDeleted(TRUE);
//
//					$is_valid = $lf->isValid();
//					if ( $is_valid == TRUE ) {
//						Debug::Text('Record Deleted...', __FILE__, __LINE__, __METHOD__, 10);
//						$save_result[$key] = $lf->Save();
//						$validator_stats['valid_records']++;
//					}
//				}
//
//				if ( $is_valid == FALSE ) {
//					Debug::Text('Data is Invalid...', __FILE__, __LINE__, __METHOD__, 10);
//
//					$lf->FailTransaction(); //Just rollback this single record, continue on to the rest.
//
//					$validator[$key] = $this->setValidationArray( $primary_validator, $lf );
//				}
//
//				$lf->CommitTransaction();
//			}
//
//			return $this->handleRecordValidationResults( $validator, $validator_stats, $key, $save_result );
//		}
//
//		return $this->returnHandler( FALSE );
//	}
}
?>
