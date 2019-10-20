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
 * @package Core
 */
class DBError extends Exception {
	/**
	 * DBError constructor.
	 * @param string $e
	 * @param string $code
	 */
	function __construct( $e, $code = 'DBError' ) {
		global $db, $skip_db_error_exception;

		if ( isset($skip_db_error_exception) AND $skip_db_error_exception === TRUE ) { //Used by system_check script.
			return TRUE;
		}

		//If we couldn't connect to the database, this method may not exist.
		if (  isset($db) AND is_object($db) AND method_exists( $db, 'FailTrans' ) ) {
			$db->FailTrans();
		}

		//print_r($e);
		//adodb_pr($e);

		Debug::Text('Begin Exception... [ '. @date('d-M-Y G:i:s O') .' ['. microtime(TRUE) .'] (PID: '. getmypid() .') ]', __FILE__, __LINE__, __METHOD__, 10);
		Debug::Arr( Debug::backTrace(), ' BackTrace: ', __FILE__, __LINE__, __METHOD__, 10);

		//Log database error
		if ( $e->getMessage() != '' ) {
			if ( stristr( $e->getMessage(), 'statement timeout' ) !== FALSE ) {
				$code = 'DBTimeout';
			} elseif ( stristr( $e->getMessage(), 'unique constraint' ) !== FALSE) {
				$code = 'DBUniqueConstraint';
			} elseif ( stristr( $e->getMessage(), 'invalid byte sequence' ) !== FALSE) {
				$code = 'DBInvalidByteSequence';
			} elseif ( stristr( $e->getMessage(), 'could not serialize' ) !== FALSE) {
				$code = 'DBSerialize';
			} elseif ( stristr( $e->getMessage(), 'deadlock' ) !== FALSE OR stristr( $e->getMessage(), 'lock timeout' ) !== FALSE OR stristr( $e->getMessage(), 'concurrent' ) !== FALSE ) {
				$code = 'DBDeadLock';
			} elseif ( stristr( $e->getMessage(), 'server has gone away' ) !== FALSE OR stristr( $e->getMessage(), 'closed the connection unexpectedly' ) !== FALSE OR stristr( $e->getMessage(), 'execution was interrupted' ) !== FALSE ) { //Connection was lost after it was initially made.
				$code = 'DBConnectionLost';
			} elseif ( stristr( $e->getMessage(), 'No space left on device' ) !== FALSE) { //Unrecoverable error, set down_for_maintenance so server admin can investigate?
				$code = 'DBNoSpaceOnDevice';
			} elseif ( stristr( $e->getMessage(), 'connection failed' ) !== FALSE ) { //Connection could not be established to begin with.
				$code = 'DBConnectionFailed';
			}
			Debug::Text( 'Code: '. $code .'('. $e->getCode() .') Message: '. $e->getMessage(), __FILE__, __LINE__, __METHOD__, 10);
		}

		if ( $e->getTrace() != '' ) {
			ob_start(); //ADDBO_BACKTRACE() always insists on printing its output and returning it, so capture the output and drop it, so we can use the $e variable instead.
			$e = adodb_backtrace( $e->getTrace(), 9999, FALSE );
			ob_end_clean();
			Debug::Arr( $e, 'Exception...', __FILE__, __LINE__, __METHOD__, 10);
		}

		Debug::Text('End Exception...', __FILE__, __LINE__, __METHOD__, 10);

		if ( !defined( 'UNIT_TEST_MODE' ) OR UNIT_TEST_MODE === FALSE ) { //When in unit test mode don't exit/redirect

			if ( DEPLOYMENT_ON_DEMAND == TRUE OR ( DEPLOYMENT_ON_DEMAND == FALSE AND in_array( $code, array( 'DBConnectionFailed', 'DBNoSpaceOnDevice', 'DBConnectionLost' ) ) == FALSE ) ) {
				Debug::emailLog();
			}

			//Dump debug buffer.
			Debug::Display();
			Debug::writeToLog();

			//Prevent PHP error by checking to make sure output buffer exists before clearing it.
			if ( ob_get_level() > 0 ) {
				ob_flush();
				ob_clean();
			}

			if ( defined('FAIRNESS_JSON_API') ) {
				if ( DEPLOYMENT_ON_DEMAND == TRUE ) {
					switch ( strtolower($code) ) {
						case 'dbtimeout':
							$description = TTi18n::getText('%1 database query has timed-out, if you were trying to run a report it may be too large, please narrow your search criteria and try again.', array( APPLICATION_NAME ) );
							break;
						case 'dbuniqueconstraint':
						case 'dbdeadlock':
							$description = TTi18n::getText('%1 has detected a duplicate request, this may be due to double-clicking a button or a poor internet connection.', array( APPLICATION_NAME ) );
							break;
						case 'dbinvalidbytesequence':
							$description = TTi18n::getText('%1 has detected invalid UTF8 characters, if you are attempting to use non-english characters, they may be invalid.', array( APPLICATION_NAME ) );
							break;
						case 'dbserialize':
							$description = TTi18n::getText('%1 has detected a duplicate request running at the exact same time, please try your request again.', array( APPLICATION_NAME ) );
							break;
						default:
							$description = TTi18n::getText('%1 is currently undergoing maintenance. We\'re sorry for any inconvenience this may cause. Please try again in 15 minutes.', array( APPLICATION_NAME ) );
							break;
					}
				} else {
					switch ( strtolower($code) ) {
						case 'dbtimeout':
							$description = TTi18n::getText('%1 database query has timed-out, if you were trying to run a report it may be too large, please narrow your search criteria and try again.', array( APPLICATION_NAME ) );
							break;
						case 'dbuniqueconstraint':
						case 'dbdeadlock':
							$description = TTi18n::getText('%1 has detected a duplicate request, this may be due to double-clicking a button or a poor internet connection.', array( APPLICATION_NAME ) );
							break;
						case 'dbinvalidbytesequence':
							$description = TTi18n::getText('%1 has detected invalid UTF8 characters, if you are attempting to use non-english characters, they may be invalid.', array( APPLICATION_NAME ) );
							break;
						case 'dbserialize':
							$description = TTi18n::getText('%1 has detected a duplicate request running at the exact same time, please try your request again.', array( APPLICATION_NAME ) );
							break;
						case 'dbnospaceondevice':
							$description = TTi18n::getText('%1 has detected a database error, please contact technical support immediately.', array( APPLICATION_NAME ) );
							break;
						case 'dberror':
						case 'dbconnectionfailed':
							$description = TTi18n::getText('%1 is unable to connect to its database, please make sure that the database service on your own local %1 server has been started and is running. If you are unsure, try rebooting your server.', array( APPLICATION_NAME ));
							break;
						case 'dbinitialize':
							$description = TTi18n::getText('%1 database has not been initialized yet, please run the installer again and follow the on screen instructions. <a href="%2">Click here to run the installer now.</a>', array( APPLICATION_NAME, Environment::getBaseURL().'/install/install.php?external_installer=1' ));
							break;
						default:
							$description = TTi18n::getText('%1 experienced a general error, please contact technical support.', array( APPLICATION_NAME ) );
							break;
					}
				}

				$obj = new APIAuthentication();
				echo json_encode( $obj->returnHandler( FALSE, 'EXCEPTION', $description ) );
				exit;
			} elseif ( PHP_SAPI == 'cli' ) {
				//Don't attempt to redirect
				echo "Fatal Exception: Code: ". $code ."... Exiting with error code 254!\n";
				exit(254);
			} else {
				global $config_vars;
				if ( DEPLOYMENT_ON_DEMAND == FALSE
						AND isset($config_vars['other']['installer_enabled']) AND $config_vars['other']['installer_enabled'] == 1
						AND in_array( strtolower($code), array('dberror', 'dbinitialize') ) ) {
					Redirect::Page( URLBuilder::getURL( array(), Environment::getBaseURL().'html5/index.php?installer=1&disable_db=1&external_installer=0#!m=Install&a=license&external_installer=0') );
				} else {
					Redirect::Page( URLBuilder::getURL( array('exception' => $code ), Environment::getBaseURL().'html5/DownForMaintenance.php') );
				}
				exit;
			}
		}

		return TRUE;
	}
}

//Used by RetryTransaction() when a nested retry block fails, so we can detect it and trigger the outer most retry block to retry from the scratch.
class NestedRetryTransaction extends Exception {}

/**
 * @package Core
 */
class GeneralError extends Exception {
	/**
	 * GeneralError constructor.
	 * @param string $message
	 */
	function __construct( $message) {
		global $db;

		//debug_print_backtrace();

		//If we couldn't connect to the database, this method may not exist.
		if ( isset($db) AND is_object($db) AND method_exists( $db, 'FailTrans' ) ) {
			$db->FailTrans();
		}

		/*
		echo "======================================================================<br>\n";
		echo "EXCEPTION!<br>\n";
		echo "======================================================================<br>\n";
		echo "<b>Error message: </b>".$message ."<br>\n";
		echo "<b>Error code: </b>".$this->getCode()."<br>\n";
		echo "<b>Script Name: </b>".$this->getFile()."<br>\n";
		echo "<b>Line Number: </b>".$this->getLine()."<br>\n";
		echo "======================================================================<br>\n";
		echo "EXCEPTION!<br>\n";
		echo "======================================================================<br>\n";
		*/

		Debug::Text('EXCEPTION: Code: '. $this->getCode() .' Message: '. $message .' File: '. $this->getFile() .' Line: '. $this->getLine(), __FILE__, __LINE__, __METHOD__, 10);
		Debug::Arr( Debug::backTrace(), ' BackTrace: ', __FILE__, __LINE__, __METHOD__, 10);

		if ( !defined( 'UNIT_TEST_MODE' ) OR UNIT_TEST_MODE === FALSE ) { //When in unit test mode don't exit/redirect
			//Dump debug buffer.
			Debug::Display();
			Debug::writeToLog();
			Debug::emailLog();
			ob_flush();
			ob_clean();

			if ( defined('FAIRNESS_JSON_API') ) {
				$obj = new APIAuthentication();
				echo json_encode( $obj->returnHandler( FALSE, 'EXCEPTION', TTi18n::getText('%1 experienced a general error, please contact technical support.', array( APPLICATION_NAME ) ) ) );
				exit;
			} else {
				Redirect::Page( URLBuilder::getURL( array('exception' => 'GeneralError'), Environment::getBaseURL().'html5/DownForMaintenance.php') );
				exit;
			}
		}
	}
}
?>
