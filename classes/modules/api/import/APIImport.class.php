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
 * @package API\Import
 */
class APIImport extends APIFactory {

	public $import_obj = NULL;

	/**
	 * APIImport constructor.
	 */
	public function __construct() {
		parent::__construct(); //Make sure parent constructor is always called.

		//When APIImport()->getImportObjects() is called directly, there won't be a main class to call.
		if ( isset($this->main_class) AND $this->main_class != '' ) {
			$this->import_obj = new $this->main_class;
			$this->import_obj->company_id = $this->getCurrentCompanyObject()->getID();
			$this->import_obj->user_id = $this->getCurrentUserObject()->getID();

			global $authentication;
			if ( is_object($authentication) AND $authentication->getSessionID() != '' ) {
				Debug::text('Session ID: '. $authentication->getSessionID(), __FILE__, __LINE__, __METHOD__, 10);
				$this->import_obj->session_id = $authentication->getSessionID();
			}

			Debug::Text('Setting main class: '. $this->main_class .' Company ID: '. $this->import_obj->company_id, __FILE__, __LINE__, __METHOD__, 10);
		} else {
			Debug::Text('NOT Setting main class... Company ID: '. $this->getCurrentCompanyObject()->getID(), __FILE__, __LINE__, __METHOD__, 10);
		}

		return TRUE;
	}

	/**
	 * @return null
	 */
	function getImportObject() {
		return $this->import_obj;
	}

	/**
	 * @return array|bool
	 */
	function getImportObjects() {
		$retarr = array();

		if ( $this->getPermissionObject()->Check('user', 'add') AND ($this->getPermissionObject()->Check('user', 'edit') OR $this->getPermissionObject()->Check('user', 'edit_child') ) ) {
			$retarr['-1010-user'] = TTi18n::getText('Employees');
		}
		if ( $this->getPermissionObject()->Check('remittance_destination_account', 'edit') OR $this->getPermissionObject()->Check('remittance_destination_account', 'edit_child')) {
			$retarr['-1015-remittance_destination_account'] = TTi18n::getText('Employee Payment Methods');
		}
		if ( $this->getPermissionObject()->Check('branch', 'add') AND $this->getPermissionObject()->Check('branch', 'edit') ) {
			$retarr['-1020-branch'] = TTi18n::getText('Branches');
		}
		if ( $this->getPermissionObject()->Check('department', 'add') AND $this->getPermissionObject()->Check('department', 'edit') ) {
			$retarr['-1030-department'] = TTi18n::getText('Departments');
		}
		if ( $this->getPermissionObject()->Check('wage', 'add') AND ($this->getPermissionObject()->Check('wage', 'edit') OR $this->getPermissionObject()->Check('wage', 'edit_child'))) {
			$retarr['-1050-userwage'] = TTi18n::getText('Employee Wages');
		}
		if ( $this->getPermissionObject()->Check('pay_period_schedule', 'add') AND $this->getPermissionObject()->Check('pay_period_schedule', 'edit') ) {
			$retarr['-1060-payperiod'] = TTi18n::getText('Pay Periods');
		}
		if (  $this->getPermissionObject()->Check('pay_stub_amendment', 'add') AND $this->getPermissionObject()->Check('pay_stub_amendment', 'edit') ) {
			$retarr['-1200-paystubamendment'] = TTi18n::getText('Pay Stub Amendments');
		}
		if ( $this->getPermissionObject()->Check('accrual', 'add') AND ($this->getPermissionObject()->Check('accrual', 'edit') OR $this->getPermissionObject()->Check('accrual', 'edit_child') )) {
			$retarr['-1300-accrual'] = TTi18n::getText('Accruals');
		}

		if ( $this->getCurrentCompanyObject()->getProductEdition() >= 15 ) {
			if ( $this->getPermissionObject()->Check('punch', 'add') AND ($this->getPermissionObject()->Check('punch', 'edit') OR $this->getPermissionObject()->Check('punch', 'edit_child')) ) {
				$retarr['-1100-punch'] = TTi18n::getText('Punches');
			}
			if ( $this->getPermissionObject()->Check('punch', 'add') AND ($this->getPermissionObject()->Check('punch', 'edit') OR $this->getPermissionObject()->Check('punch', 'edit_child')) ) {
				$retarr['-1110-userdatetotal'] = TTi18n::getText('Manual TimeSheet');
			}
			if ( $this->getPermissionObject()->Check('schedule', 'add') AND ($this->getPermissionObject()->Check('schedule', 'edit') OR $this->getPermissionObject()->Check('schedule', 'edit_child')) ) {
				$retarr['-1150-schedule'] = TTi18n::getText('Scheduled Shifts');
			}
		}

		if ( $this->getCurrentCompanyObject()->getProductEdition() >= 20 ) {
			if ( $this->getPermissionObject()->Check('client', 'add') AND $this->getPermissionObject()->Check('client', 'edit') ) {
				$retarr['-1500-client'] = TTi18n::getText('Clients');
			}
			if ( $this->getPermissionObject()->Check('job', 'add') AND $this->getPermissionObject()->Check('job', 'edit') ) {
				$retarr['-1600-job'] = TTi18n::getText('Jobs');
			}
			if ( $this->getPermissionObject()->Check('job_item', 'add') AND $this->getPermissionObject()->Check('job_item', 'edit') ) {
				$retarr['-1605-jobitem'] = TTi18n::getText('Tasks');
			}
		}

		return $this->returnHandler( $retarr );
	}

	/**
	 * @return array|bool
	 */
	function returnFileValidationError() {
		//Make sure we return a complete validation error to be displayed to the user.
		$validator_obj = new Validator();
		$validator_stats = array('total_records' => 1, 'valid_records' => 0 );

		$validator_obj->isTrue( 'file', FALSE, TTi18n::getText('Please upload file again') );

		$validator = array();
		$validator[0]['error'] = $validator_obj->getErrorsArray();
		return $this->returnHandler( FALSE, 'IMPORT_FILE', TTi18n::getText('INVALID DATA'), $validator, $validator_stats );
	}

	/**
	 * @param null $maximum_lines
	 * @return array|bool
	 */
	function returnMaximumLineValidationError( $maximum_lines = NULL ) {
		//Make sure we return a complete validation error to be displayed to the user.
		$validator_obj = new Validator();
		$validator_stats = array('total_records' => 1, 'valid_records' => 0 );

		$validator_obj->isTrue( 'records', FALSE, TTi18n::getText('File exceeds maximum number of records (%1) that can be imported at one time', $maximum_lines ) );

		$validator = array();
		$validator[0]['error'] = $validator_obj->getErrorsArray();
		return $this->returnHandler( FALSE, 'IMPORT_FILE', TTi18n::getText('INVALID DATA'), $validator, $validator_stats );
	}

	/**
	 * @return array|bool
	 */
	function generateColumnMap() {
		if ( $this->getImportObject()->getRawDataFromFile() == FALSE ) {
			$this->returnFileValidationError();
		}

		return $this->returnHandler( $this->getImportObject()->generateColumnMap() );
	}

	/**
	 * @param $saved_column_map
	 * @return array|bool
	 */
	function mergeColumnMap( $saved_column_map ) {
		if ( $this->getImportObject()->getRawDataFromFile() == FALSE ) {
			$this->returnFileValidationError();
		}

		return $this->returnHandler( $this->getImportObject()->mergeColumnMap( $saved_column_map ) );
	}

	/**
	 * @param int $limit Limit the number of records returned
	 * @return array|bool
	 */
	function getRawData( $limit = NULL ) {
		if ( !is_object( $this->getImportObject() ) OR $this->getImportObject()->getRawDataFromFile() == FALSE ) {
			$this->returnFileValidationError();
		}

		return $this->returnHandler( $this->getImportObject()->getRawData( $limit ) );
	}

	/**
	 * @param $data
	 * @return array|bool
	 */
	function setRawData( $data ) {
		return $this->returnHandler( $this->getImportObject()->saveRawDataToFile( $data ) );
	}

	/**
	 * @return array|bool
	 */
	function getParsedData() {
		return $this->returnHandler( $this->getParsedData() );
	}

	/**
	 * @param $column_map
	 * @param array $import_options
	 * @param bool $validate_only
	 * @return array|bool
	 */
	function Import( $column_map, $import_options = array(), $validate_only = FALSE ) {
		if ( Misc::isSystemLoadValid() == FALSE ) { //Check system load as the user could ask to calculate decades worth at a time.
			Debug::Text('ERROR: System load exceeded, preventing new imports from starting...', __FILE__, __LINE__, __METHOD__, 10);
			return $this->returnHandler( FALSE );
		}

		global $config_vars;
		if ( isset($config_vars['other']['import_maximum_execution_limit']) AND $config_vars['other']['import_maximum_execution_limit'] != '' ) {
			$maximum_execution_time = $config_vars['other']['import_maximum_execution_limit'];
		} else {
			if ( DEPLOYMENT_ON_DEMAND == TRUE ) {
				$maximum_execution_time = 3600;
			} else {
				$maximum_execution_time = 0;
			}
		}

		if ( $validate_only == TRUE ) { //When validating, reduce the maximum execution time substantially to limit resource/time waste.
			$maximum_execution_time = ceil( $maximum_execution_time / 2 );
		}

		ini_set( 'max_execution_time', $maximum_execution_time );
		Debug::Text('Maximum Execution Time for Import: '. $maximum_execution_time .' Validate Only: '. (int)$validate_only, __FILE__, __LINE__, __METHOD__, 10);

		if ( isset($config_vars['other']['import_maximum_records_limit']) AND $config_vars['other']['import_maximum_records_limit'] != '' ) {
			$maximum_record_limit = $config_vars['other']['import_maximum_records_limit'];
		} else {
			if ( DEPLOYMENT_ON_DEMAND == TRUE ) {
				$maximum_record_limit = 1000;
			} else {
				$maximum_record_limit = 0;
			}
		}
		Debug::Text('Maximum Records allowed for Import: '. $maximum_record_limit .' Validate Only: '. (int)$validate_only, __FILE__, __LINE__, __METHOD__, 10);

		if ( function_exists('proc_nice') ) {
			proc_nice(19); //Low priority.
		}

		if ( $this->getImportObject()->setColumnMap( $column_map ) == FALSE ) {
			return $this->returnHandler( FALSE );
		}

		if ( is_array($import_options) AND $this->getImportObject()->setImportOptions( $import_options ) == FALSE ) {
			return $this->returnHandler( FALSE );
		}

		if ( $this->getImportObject()->getRawDataFromFile() == FALSE ) {
			return $this->returnFileValidationError();
		}

		if ( $maximum_record_limit > 0 AND $this->getImportObject()->getRawDataLines() > $maximum_record_limit ) {
			return $this->returnMaximumLineValidationError( $maximum_record_limit );
		}

		//Force this while testing.
		//$validate_only = TRUE;
		$this->getImportObject()->setAMFMessageId( $this->getAMFMessageID() ); //This must be set *after* the all constructor functions are called.

		return $this->getImportObject()->Process( $validate_only ); //Don't need return handler here as a API function is called anyways.
	}

}
?>
