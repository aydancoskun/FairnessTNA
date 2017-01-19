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
 * @package API\Report
 */
class APIReport extends APIFactory {
	public $report_obj = NULL;

	public function __construct() {
		parent::__construct(); //Make sure parent constructor is always called.

		$report_obj = TTNew( $this->main_class ); //Allow plugins to work with reports.
		$report_obj->setUserObject( $this->getCurrentUserObject() );
		$report_obj->setPermissionObject( $this->getPermissionObject() );

		$this->setMainClassObject( $report_obj );

		return TRUE;
	}

	function getReportObject() {
		return $this->getMainClassObject();
	}

	function getTemplate( $name = FALSE ) {
		return $this->returnHandler( $this->getReportObject()->getTemplate( $name ) );
	}

	function getConfig() {
		return $this->returnHandler( $this->getReportObject()->getConfig() );
	}
	function setConfig( $data = FALSE ) {
		return $this->returnHandler( $this->getReportObject()->setConfig( $data ) );
	}

	function getOtherConfig() {
		return $this->returnHandler( $this->getReportObject()->getOtherConfig() );
	}
	function getChartConfig() {
		return $this->returnHandler( $this->getReportObject()->getChartConfig() );
	}

	function setCompanyFormConfig( $data = FALSE ) {
		if ( $this->getReportObject()->checkPermissions() == TRUE ) {
			return $this->returnHandler( $this->getReportObject()->setCompanyFormConfig( $data ) );
		}

		return $this->returnHandler( FALSE, 'VALIDATION', TTi18n::getText('PERMISSION DENIED') );
	}
	function getCompanyFormConfig() {
		if ( $this->getReportObject()->checkPermissions() == TRUE ) {
			return $this->returnHandler( $this->getReportObject()->getCompanyFormConfig() );
		}

		return $this->returnHandler( FALSE, 'VALIDATION', TTi18n::getText('PERMISSION DENIED') );
	}

	function validateReport( $config = FALSE, $format = 'pdf' ) {
		$this->getReportObject()->setConfig( $config ); //Set config first, so checkPermissions can check/modify data in the config for Printing timesheets for regular employees.
		if ( $this->getReportObject()->checkPermissions() == TRUE ) {
			$validation_obj = $this->getReportObject()->validateConfig( $format );
			if ( $validation_obj->isValid() == FALSE ) {
				return $this->returnHandler( FALSE, 'VALIDATION', TTi18n::getText('INVALID DATA'), array( 0 => $validation_obj->getErrorsArray() ), array('total_records' => 1, 'valid_records' => 0 ) );
			}
		}

		return $this->returnHandler( TRUE );
	}

	//Use JSON API to download PDF files.
	function getReport( $config = FALSE, $format = 'pdf' ) {
		if ( Misc::isSystemLoadValid() == FALSE ) {
			return $this->returnHandler( FALSE, 'VALIDATION', TTi18n::getText('Please try again later...') );
		}

		$format = Misc::trimSortPrefix( $format );
		Debug::Text('Format: '. $format, __FILE__, __LINE__, __METHOD__, 10);
		$this->getReportObject()->setConfig( $config ); //Set config first, so checkPermissions can check/modify data in the config for Printing timesheets for regular employees.
		if ( $this->getReportObject()->checkPermissions() == TRUE ) {
			$this->getReportObject()->setAMFMessageID( $this->getAMFMessageID() ); //This must be set *after* the all constructor functions are called, as its primarily called from JSON.

			$validation_obj = $this->getReportObject()->validateConfig( $format );
			if ( $validation_obj->isValid() == TRUE ) {
				//return Misc::APIFileDownload( 'report.pdf', 'application/pdf', $this->getReportObject()->getOutput( $format ) );
				$output_arr = $this->getReportObject()->getOutput( $format );

				if ( isset($output_arr['file_name']) AND isset($output_arr['mime_type']) AND isset($output_arr['data']) ) {
					//If using the SOAP API, return data base64 encoded so it can be decoded on the client side.
					if ( defined('FAIRNESS_SOAP_API') AND FAIRNESS_SOAP_API == TRUE ) {
						$output_arr['data'] = base64_encode( $output_arr['data'] );
						return $this->returnHandler( $output_arr );
					} else {
						if ( $output_arr['mime_type'] === 'text/html' ) {
							return $this->returnHandler( $output_arr['data'] );
						} else {
							Misc::APIFileDownload( $output_arr['file_name'], $output_arr['mime_type'], $output_arr['data'] );
							return NULL; //Don't send any additional data, so JSON encoding doesn't corrupt the download.
						}
					}
				} elseif ( isset($output_arr['api_retval']) ) { //Pass through validation errors.
					Debug::Text('Report returned VALIDATION error, passing through...', __FILE__, __LINE__, __METHOD__, 10);
					return $this->returnHandler( $output_arr['api_retval'], $output_arr['api_details']['code'], $output_arr['api_details']['description'] );
					//return $this->returnHandler( FALSE, 'VALIDATION', TTi18n::getText('Please try again later...') );
				} elseif ( $output_arr !== FALSE ) {
					//Likely RAW data, return untouched.
					return $this->returnHandler( $output_arr );
				} else {
					//getOutput() returned FALSE, some error occurred. Likely load too high though.
					//return $this->returnHandler( FALSE, 'VALIDATION', TTi18n::getText('Error generating report...') );
					return $this->returnHandler( FALSE, 'VALIDATION', TTi18n::getText('ERROR: Please try again later or narrow your search criteria to decrease the size of your report').'...' );
				}
			} else {
				return $this->returnHandler( FALSE, 'VALIDATION', TTi18n::getText('INVALID DATA'), array( 0 => $validation_obj->getErrorsArray() ), array('total_records' => 1, 'valid_records' => 0 ) );
			}
		}

		return $this->returnHandler( FALSE, 'VALIDATION', TTi18n::getText('PERMISSION DENIED') );
	}

}
?>
