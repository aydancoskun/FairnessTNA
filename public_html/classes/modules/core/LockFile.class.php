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
 * @package Core
 */
class LockFile {
	var $file_name = NULL;

	var $max_lock_file_age = 86400;
	var $use_pid = TRUE;

	function __construct( $file_name ) {
		$this->file_name = $file_name;

		return TRUE;
	}

	function getFileName( ) {
		return $this->file_name;
	}

	function setFileName($file_name) {
		if ( $file_name != '') {
			$this->file_name = $file_name;

			return TRUE;
		}

		return FALSE;
	}

	function getCurrentPID() {
		if ( $this->use_pid == TRUE AND function_exists('getmypid') == TRUE ) {
			$retval = getmypid();
			Debug::Text( 'Current PID: ' . $retval, __FILE__, __LINE__, __METHOD__, 10 );

			return $retval;
		}

		return FALSE;
	}

	function isPIDRunning( $pid ) {
		if ( $this->use_pid == TRUE AND (int)$pid > 0 AND function_exists('posix_getpgid') == TRUE ) {
			Debug::Text( 'Checking if PID is running: ' . $pid, __FILE__, __LINE__, __METHOD__, 10 );
			if ( posix_getpgid( $pid ) === FALSE ) {
				Debug::Text( '  PID is NOT running!', __FILE__, __LINE__, __METHOD__, 10 );
				return FALSE;
			} else {
				Debug::Text( '  PID IS running!', __FILE__, __LINE__, __METHOD__, 10 );
				return TRUE;
			}
		} else {
			//Debug::Text( 'PID is invalid or POSIX functions dont exist: ' . $pid, __FILE__, __LINE__, __METHOD__, 10 );
			if ( OPERATING_SYSTEM == 'WIN' ) {
				Debug::Text( 'Checking if PID is running on Windows: ' . $pid, __FILE__, __LINE__, __METHOD__, 10 );
				$processes = explode( "\n", shell_exec( 'tasklist.exe' ) );
				if ( is_array($processes) ) {
					foreach ( $processes as $process ) {
						if ( trim($process) == '' OR strpos( "Image Name", $process ) === 0 OR strpos( "===", $process ) === 0 ) {
							continue;
						}

						$matches = FALSE;
						preg_match( "/(.*?)\s+(\d+).*$/", $process, $matches );
						if ( isset($matches[2]) AND $pid == trim( $matches[2] ) ) {
							Debug::Text( '  PID IS running!', __FILE__, __LINE__, __METHOD__, 10 );
							return TRUE;
						}
					}

					Debug::Text( '  PID is NOT running!', __FILE__, __LINE__, __METHOD__, 10 );
					return FALSE;
				}
			}
		}

		return NULL; //Assuming the process is still running if the file exists and PID is invalid.
	}

	function create() {
		//Attempt to create directory if it does not already exist.
		if ( file_exists( dirname( $this->getFileName() ) ) == FALSE ) {
			$mkdir_result = @mkdir( dirname( $this->getFileName() ), 0777, TRUE );
			if ( $mkdir_result == FALSE ) {
				Debug::Text( 'ERROR: Unable to create lock file directory: ' . dirname( $this->getFileName() ), __FILE__, __LINE__, __METHOD__, 10 );
			} else {
				Debug::Text( 'WARNING: Created lock file directory as it didnt exist: ' . dirname( $this->getFileName() ), __FILE__, __LINE__, __METHOD__, 10 );
			}
		}

		//Write current PID to file, so we can check if its still running later on.
		//return @touch( $this->getFileName() );
		return @file_put_contents( $this->getFileName(), $this->getCurrentPID() );
	}

	function delete() {
		if ( file_exists( $this->getFileName() ) ) {
			return @unlink( $this->getFileName() );
		}

		Debug::text(' Failed deleting lock file: '. $this->file_name, __FILE__, __LINE__, __METHOD__, 10);

		return FALSE;
	}

	function exists() {
		//Ignore lock files older than max_lock_file_age, so if the server crashes or is rebooted during an operation, it will start again the next day.
		clearstatcache();
		//if ( file_exists( $this->getFileName() ) AND @filemtime( $this->getFileName() ) >= ( time() - $this->max_lock_file_age ) ) {
		if ( file_exists( $this->getFileName() ) ) {
			$lock_file_pid = (int)@file_get_contents( $this->getFileName() );
			Debug::text(' Lock file exists with PID: '. $lock_file_pid, __FILE__, __LINE__, __METHOD__, 10);

			//Check to see if PID is still running or not.
			$pid_running = $this->isPIDRunning( $lock_file_pid );
			if ( $pid_running !== NULL ) {
				//PID result is reliable, use it.
				return $pid_running;
			} elseif ( @filemtime( $this->getFileName() ) >= ( time() - $this->max_lock_file_age ) ) {
				//PID result may not be reliable, fall back to using file time instead.
				return TRUE;
			}
		}

		return FALSE;
	}
}
?>
