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
 * @package API\Import
 */
class APIImport extends APIFactory {

	public $import_obj = NULL;

	public function __construct() {
		parent::__construct(); //Make sure parent constructor is always called.

		//When APIImport()->getImportObjects() is called directly, there won't be a main class to call.
		if ( isset($this->main_class) AND $this->main_class != '' ) {
			$this->import_obj = new $this->main_class;
			$this->import_obj->company_id = $this->getCurrentCompanyObject()->getID();
			$this->import_obj->user_id = $this->getCurrentUserObject()->getID();
			Debug::Text('Setting main class: '. $this->main_class .' Company ID: '. $this->import_obj->company_id, __FILE__, __LINE__, __METHOD__, 10);
		} else {
			Debug::Text('NOT Setting main class... Company ID: '. $this->getCurrentCompanyObject()->getID(), __FILE__, __LINE__, __METHOD__, 10);
		}

		return TRUE;
	}

	function getImportObject() {
		return $this->import_obj;
	}

	function getImportObjects() {
		$retarr = array();

		if ( $this->getPermissionObject()->Check('user', 'add') AND ($this->getPermissionObject()->Check('user', 'edit') OR $this->getPermissionObject()->Check('user', 'edit_child') ) ) {
			$retarr['-1010-user'] = TTi18n::getText('Employees');
		}
		if ( $this->getPermissionObject()->Check('user', 'edit_bank') AND $this->getPermissionObject()->Check('user', 'edit_child_bank')) {
			$retarr['-1015-bank_account'] = TTi18n::getText('Employee Bank Accounts');
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

		return $this->returnHandler( $retarr );
	}

	function returnFileValidationError() {
		//Make sure we return a complete validation error to be displayed to the user.
		$validator_obj = new Validator();
		$validator_stats = array('total_records' => 1, 'valid_records' => 0 );

		$validator_obj->isTrue( 'file', FALSE, TTi18n::getText('Please upload file again') );

		$validator = array();
		$validator[0] = $validator_obj->getErrorsArray();
		return $this->returnHandler( FALSE, 'IMPORT_FILE', TTi18n::getText('INVALID DATA'), $validator, $validator_stats );
	}

	function generateColumnMap() {
		if ( $this->getImportObject()->getRawDataFromFile() == FALSE ) {
			$this->returnFileValidationError();
		}

		return $this->returnHandler( $this->getImportObject()->generateColumnMap() );
	}
	function mergeColumnMap( $saved_column_map ) {
		if ( $this->getImportObject()->getRawDataFromFile() == FALSE ) {
			$this->returnFileValidationError();
		}

		return $this->returnHandler( $this->getImportObject()->mergeColumnMap( $saved_column_map ) );
	}

	function getRawData( $limit = NULL ) {
		if ( !is_object( $this->getImportObject() ) OR $this->getImportObject()->getRawDataFromFile() == FALSE ) {
			$this->returnFileValidationError();
		}

		return $this->returnHandler( $this->getImportObject()->getRawData( $limit ) );
	}
	function setRawData( $data ) {
		return $this->returnHandler( $this->getImportObject()->saveRawDataToFile( $data ) );
	}

	function getParsedData() {
		return $this->returnHandler( $this->getParsedData() );
	}

	function Import( $column_map, $import_options = array(), $validate_only = FALSE ) {
		if ( $this->getImportObject()->getRawDataFromFile() == FALSE ) {
			return $this->returnFileValidationError();
		}

		if ( $this->getImportObject()->setColumnMap( $column_map ) == FALSE ) {
			return $this->returnHandler( FALSE );
		}

		if ( is_array($import_options) AND $this->getImportObject()->setImportOptions( $import_options ) == FALSE ) {
			return $this->returnHandler( FALSE );
		}

		if ( Misc::isSystemLoadValid() == FALSE ) { //Check system load as the user could ask to calculate decades worth at a time.
			Debug::Text('ERROR: System load exceeded, preventing new imports from starting...', __FILE__, __LINE__, __METHOD__, 10);
			return $this->returnHandler( FALSE );
		}

		//Force this while testing.
		//Force this while testing.
		//Force this while testing.
		//$validate_only = TRUE;

		$this->getImportObject()->setAMFMessageId( $this->getAMFMessageID() ); //This must be set *after* the all constructor functions are called.
		return $this->getImportObject()->Process( $validate_only ); //Don't need return handler here as a API function is called anyways.
	}

}
?>
