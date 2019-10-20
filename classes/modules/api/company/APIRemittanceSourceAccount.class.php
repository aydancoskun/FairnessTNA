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
class APIRemittanceSourceAccount extends APIFactory {
	protected $main_class = 'RemittanceSourceAccountFactory';

	/**
	 * APIRemittanceSourceAccount constructor.
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
			AND ( !$this->getPermissionObject()->Check('remittance_source_account', 'enabled')
				OR !( $this->getPermissionObject()->Check('remittance_source_account', 'view') OR $this->getPermissionObject()->Check('remittance_source_account', 'view_own') ) ) ) {
			$name = 'list_columns';
		}

		return parent::getOptions( $name, $parent );
	}

	/**
	 * Get default remittance source account data for creating new remittance source account.
	 * @return array
	 */
	function getRemittanceSourceAccountDefaultData() {
		$company_obj = $this->getCurrentCompanyObject();

		Debug::Text('Getting remittance source account default data...', __FILE__, __LINE__, __METHOD__, 10);
		$data = array(
			'company_id' => $company_obj->getId(),
			'status_id' => 10,
			'type_id' => 3000,
			'last_transaction_number' => 0,
		);

		//Get New Hire Defaults.
		$udlf = TTnew( 'UserDefaultListFactory' ); /** @var UserDefaultListFactory $udlf */
		$udlf->getByCompanyId( $company_obj->getId() );
		if ( $udlf->getRecordCount() > 0 ) {
			Debug::Text('Using User Defaults, as they exist...', __FILE__, __LINE__, __METHOD__, 10);
			$udf_obj = $udlf->getCurrent();

			$data['legal_entity_id'] = $udf_obj->getLegalEntity();
			$data['currency_id'] = $udf_obj->getCurrency();
			$data['country'] = $udf_obj->getCountry();
		}

		//Handle default data formats based on the country.
		if ( strtoupper( $data['country'] ) == 'CA' ) {
			$data['data_format_id'] = 20; //EFT 1464
		} else {
			$data['data_format_id'] = 10; //ACH
		}

		return $this->returnHandler( $data );
	}

	/**
	 * Get remittance source account data for one or more remittance source accounts.
	 * @param array $data filter data
	 * @param bool $disable_paging
	 * @return array
	 */
	function getRemittanceSourceAccount( $data = NULL, $disable_paging = FALSE ) {
		$data = $this->initializeFilterAndPager( $data, $disable_paging );

		if ( !$this->getPermissionObject()->Check('remittance_source_account', 'enabled')
			OR !( $this->getPermissionObject()->Check('remittance_source_account', 'view') OR $this->getPermissionObject()->Check('remittance_source_account', 'view_own') ) ) {
			//return $this->getPermissionObject()->PermissionDenied();
			//Rather then permission denied, restrict to just 'list_view' columns.
			$data['filter_columns'] = $this->handlePermissionFilterColumns( (isset($data['filter_columns'])) ? $data['filter_columns'] : NULL, Misc::trimSortPrefix( $this->getOptions('list_columns') ) );
		}

		$blf = TTnew( 'RemittanceSourceAccountListFactory' ); /** @var RemittanceSourceAccountListFactory $blf */
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
	function getCommonRemittanceSourceAccountData( $data ) {
		return Misc::arrayIntersectByRow( $this->stripReturnHandler( $this->getRemittanceSourceAccount( $data, TRUE ) ) );
	}

	/**
	 * Validate remittance source account data for one or more remittance source accounts.
	 * @param array $data remittance source account data
	 * @return array
	 */
	function validateRemittanceSourceAccount( $data ) {
		return $this->setRemittanceSourceAccount( $data, TRUE );
	}

	/**
	 * Set remittance source account data for one or more remittance source accounts.
	 * @param array $data remittance source account data
	 * @param bool $validate_only
	 * @param bool $ignore_warning
	 * @return array|bool
	 */
	function setRemittanceSourceAccount( $data, $validate_only = FALSE, $ignore_warning = TRUE ) {
		$validate_only = (bool)$validate_only;
		$ignore_warning = (bool)$ignore_warning;

		if ( !is_array($data) ) {
			return $this->returnHandler( FALSE );
		}

		if ( !$this->getPermissionObject()->Check('remittance_source_account', 'enabled')
			OR !( $this->getPermissionObject()->Check('remittance_source_account', 'edit') OR $this->getPermissionObject()->Check('remittance_source_account', 'edit_own') OR $this->getPermissionObject()->Check('remittance_source_account', 'add') ) ) {
			return	$this->getPermissionObject()->PermissionDenied();
		}

		if ( $validate_only == TRUE ) {
			Debug::Text('Validating Only!', __FILE__, __LINE__, __METHOD__, 10);
		}

		list( $data, $total_records ) = $this->convertToMultipleRecords( $data );
		Debug::Text('Received data for: '. $total_records .' Remittance source accounts', __FILE__, __LINE__, __METHOD__, 10);
		Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

		$validator_stats = array('total_records' => $total_records, 'valid_records' => 0 );
		$validator = $save_result = $key = FALSE;
		if ( is_array($data) AND $total_records > 0 ) {
			$this->getProgressBarObject()->start( $this->getAMFMessageID(), $total_records );

			foreach( $data as $key => $row ) {
				$primary_validator = new Validator();
				$lf = TTnew( 'RemittanceSourceAccountListFactory' ); /** @var RemittanceSourceAccountListFactory $lf */
				$lf->StartTransaction();
				if ( isset($row['id']) AND $row['id'] != '' ) {
					//Modifying existing object.
					$lf->getByIdAndCompanyId( $row['id'], $this->getCurrentCompanyObject()->getId() );
					if ( $lf->getRecordCount() == 1 ) {
						//Object exists, check edit permissions
						if (
							$validate_only == TRUE
							OR
							(
								$this->getPermissionObject()->Check('remittance_source_account', 'edit')
								OR ( $this->getPermissionObject()->Check('remittance_source_account', 'edit_own') AND $this->getPermissionObject()->isOwner( $lf->getCurrent()->getCreatedBy(), $lf->getCurrent()->getID() ) === TRUE )
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
					$row['company_id'] = $this->getCurrentCompanyObject()->getId();
					//Adding new object, check ADD permissions.
					$primary_validator->isTrue( 'permission', $this->getPermissionObject()->Check('remittance_source_account', 'add'), TTi18n::gettext('Add permission denied') );
				}
				Debug::Arr($row, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

				$is_valid = $primary_validator->isValid( $ignore_warning );
				if ( $is_valid == TRUE ) { //Check to see if all permission checks passed before trying to save data.
					Debug::Text('Setting object data...', __FILE__, __LINE__, __METHOD__, 10);

					$lf->setObjectFromArray( $row );

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
	 * Delete one or more remittance source accounts.
	 * @param array $data remittance source account data
	 * @return array|bool
	 */
	function deleteRemittanceSourceAccount( $data ) {
		if ( !is_array($data) ) {
			$data = array($data);
		}

		if ( !is_array($data) ) {
			return $this->returnHandler( FALSE );
		}

		if ( !$this->getPermissionObject()->Check('remittance_source_account', 'enabled')
			OR !( $this->getPermissionObject()->Check('remittance_source_account', 'delete') OR $this->getPermissionObject()->Check('remittance_source_account', 'delete_own') ) ) {
			return	$this->getPermissionObject()->PermissionDenied();
		}

		Debug::Text('Received data for: '. count($data) .' Remittance source accounts', __FILE__, __LINE__, __METHOD__, 10);
		Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

		$total_records = count($data);
		$validator = $save_result = $key = FALSE;
		$validator_stats = array('total_records' => $total_records, 'valid_records' => 0 );
		if ( is_array($data) AND $total_records > 0 ) {
			$this->getProgressBarObject()->start( $this->getAMFMessageID(), $total_records );

			foreach( $data as $key => $id ) {
				$primary_validator = new Validator();
				$lf = TTnew( 'RemittanceSourceAccountListFactory' ); /** @var RemittanceSourceAccountListFactory $lf */
				$lf->StartTransaction();
				if ( $id != '' ) {
					//Modifying existing object.
					$lf->getByIdAndCompanyId( $id, $this->getCurrentCompanyObject()->getId() );
					if ( $lf->getRecordCount() == 1 ) {
						//Object exists, check edit permissions
						if ( $this->getPermissionObject()->Check('remittance_source_account', 'delete')
							OR ( $this->getPermissionObject()->Check('remittance_source_account', 'delete_own') AND $this->getPermissionObject()->isOwner( $lf->getCurrent()->getCreatedBy(), $lf->getCurrent()->getID() ) === TRUE ) ) {
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
	 * Copy one or more remittance source accounts.
	 * @param array $data remittance source account IDs
	 * @return array
	 */
	function copyRemittanceSourceAccount( $data ) {
		if ( !is_array($data) ) {
			$data = array($data);
		}

		if ( !is_array($data) ) {
			return $this->returnHandler( FALSE );
		}

		Debug::Text('Received data for: '. count($data) .' Remittance source accounts', __FILE__, __LINE__, __METHOD__, 10);
		Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

		$src_rows = $this->stripReturnHandler( $this->getRemittanceSourceAccount( array('filter_data' => array('id' => $data) ), TRUE ) );
		if ( is_array( $src_rows ) AND count($src_rows) > 0 ) {
			Debug::Arr($src_rows, 'SRC Rows: ', __FILE__, __LINE__, __METHOD__, 10);
			foreach( $src_rows as $key => $row ) {
				unset($src_rows[$key]['id'] ); //Clear fields that can't be copied
				$src_rows[$key]['name'] = Misc::generateCopyName( $row['name'] ); //Generate unique name
			}
			//Debug::Arr($src_rows, 'bSRC Rows: ', __FILE__, __LINE__, __METHOD__, 10);

			return $this->setRemittanceSourceAccount( $src_rows ); //Save copied rows
		}

		return $this->returnHandler( FALSE );
	}

	/**
	 * Download a test file for $0.01 post dated for 2 days in the future for each provided source account ID.
	 * @param $ids
	 * @return array|bool
	 */
	function testExport( $ids ) {
		require_once( Environment::getBasePath() . '/classes/ChequeForms/ChequeForms.class.php' );

		$output = array();
		$filter_data = array(
				'id' => $ids,
		);

		$rsalf = TTnew('RemittanceSourceAccountListFactory'); /** @var RemittanceSourceAccountListFactory $rsalf */
		$rsalf->getAPISearchByCompanyIdAndArrayCriteria( $this->getCurrentCompanyObject()->getId(), $filter_data );

		foreach ( $rsalf as $rs_obj ) {
			$pstf = TTnew('PayStubTransactionFactory'); /** @var PayStubTransactionFactory $pstf */
			$pstf->setAmount( 0.01 );
			$pstf->setCurrency( $rs_obj->getCurrency() );
			$pstf->setType( $rs_obj->getType() );
			$pstf->setRemittanceSourceAccount( $rs_obj->getId() );

			$ps_obj = TTnew('PayStubFactory'); /** @var PayStubFactory $ps_obj */
			$ps_obj->setTransactionDate ( TTDate::getBeginDayEpoch(time()) );
			$ps_obj->setStartDate( mktime(0, 0, 0, TTDate::getMonth(time()), TTDate::getDayOfMonth( TTDate::incrementDate( time(), -14, 'day') ), TTDate::getYear( time() ) ) );
			$ps_obj->setEndDate( mktime(0, 0, 0, TTDate::getMonth(time()), TTDate::getDayOfMonth( TTDate::incrementDate( time(), -1, 'day') ), TTDate::getYear( time() ) ) );
			$ps_obj->setCurrency( $rs_obj->getCurrency() );

			//This mirrors PayStubTransaction::exportPayStubTransaction()
			if ( $rs_obj->getType() == 3000 ) {
				$next_transaction_number = $rs_obj->getNextTransactionNumber();
				$eft = $pstf->startEFTFile( $rs_obj );
				$confirmation_number = strtoupper( substr( sha1( TTUUID::generateUUID() ), -8 ) );
				$record = $pstf->getEFTRecord( $eft, $pstf, $ps_obj, $rs_obj, $this->getCurrentUserObject(), $confirmation_number );

				//Make the destination the same as the source for the sample file, at least then its not 0's and the bank is likely to verify more information.
				$record->setInstitution( $rs_obj->getValue1() );
				$record->setTransit( $rs_obj->getValue2() );
				$record->setAccount( $rs_obj->getValue3() );

				$eft->setRecord( $record );
				$output = $pstf->endEFTFile( $eft, $rs_obj, $this->getCurrentUserObject(), $ps_obj, $this->getCurrentCompanyObject()->getId(), $pstf->getAmount(), $next_transaction_number, $output );
			}

			if ( $rs_obj->getType() == 2000 ) {
				$data_format_types = $rs_obj->getOptions('data_format_check_form');

				$data_format_type_id = $rs_obj->getDataFormat();
				$check_file_obj = TTnew('ChequeForms'); /** @var ChequeForms $check_file_obj */
				$check_obj = $check_file_obj->getFormObject( strtoupper( $data_format_types[$data_format_type_id] ) );
//				if ( PRODUCTION == FALSE AND Debug::getEnable() == TRUE ) {
//					$check_obj->setDebug( TRUE );
//				}

				$check_obj->setPageOffsets( $rs_obj->getValue6(), $rs_obj->getValue5() ); //Value5=Vertical, Value6=Horizontal

				$transaction_number = $rs_obj->getNextTransactionNumber();
				$ps_data = $pstf->getChequeData( $ps_obj, $pstf, $rs_obj, $this->getCurrentUserObject(), $transaction_number, TRUE ); //Draw alignment grid when testing check format.
				$check_obj->addRecord( $ps_data );
				$check_file_obj->addForm( $check_obj );
				$transaction_number++;
				$output = $pstf->endChequeFile( $rs_obj, $ps_obj, $transaction_number, $output, $check_file_obj );
			}
		}

		if ( is_array($output) AND count($output) > 0 ) {
			$filename = 'sample_transaction_file_'.TTDate::getDate( 'DATE', time() ).'.zip';
			$zip_file = Misc::zip($output, $filename, TRUE);
			return Misc::APIFileDownload($zip_file['file_name'], $zip_file['mime_type'], $zip_file['data'] );
		} else {
			return $this->returnHandler( FALSE, 'VALIDATION', TTi18n::getText('ERROR: No data to export...') );
		}
	}


	/**
	 * @param $id
	 * @return bool
	 */
	function deleteImage( $id ) {
		//permissions match setRemittanceSourceAccount()
		if ( !$this->getPermissionObject()->Check('remittance_source_account', 'enabled')
				OR !( $this->getPermissionObject()->Check('remittance_source_account', 'edit') OR $this->getPermissionObject()->Check('remittance_source_account', 'edit_own') OR $this->getPermissionObject()->Check('remittance_source_account', 'add') ) ) {
			return	$this->getPermissionObject()->PermissionDenied();
		}


		$result = $this->stripReturnHandler( $this->getRemittanceSourceAccount( array('filter_data' => array( 'id' => $id ) ) ) );
		if ( isset($result[0]) AND count($result[0]) > 0 ) {
			$uf = TTnew( 'RemittanceSourceAccountFactory' ); /** @var RemittanceSourceAccountFactory $uf */
			$file_name = $uf->getSignatureFileName( $this->current_company->getId(), $id );

			if ( file_exists($file_name) ) {
				unlink($file_name);
			}
		}

		return $this->returnHandler( TRUE );
	}
}
?>
