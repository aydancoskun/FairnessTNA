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
class LockFile {
	var $file_name = NULL;

	var $max_lock_file_age = 86400;
	var $use_pid = TRUE;

	/**
	 * LockFile constructor.
	 * @param $file_name
	 */
	function __construct( $file_name ) {
		$this->file_name = $file_name;

		return TRUE;
	}

	/**
	 * @return null
	 */
	function getFileName( ) {
		return $this->file_name;
	}

	/**
	 * @param $file_name
	 * @return bool
	 */
	function setFileName( $file_name) {
		if ( $file_name != '') {
			$this->file_name = $file_name;

			return TRUE;
		}

		return FALSE;
	}

	/**
	 * @return bool|int
	 */
	function getCurrentPID() {
		if ( $this->use_pid == TRUE AND function_exists('getmypid') == TRUE ) {
			$retval = getmypid();
			Debug::Text( 'Current PID: ' . $retval, __FILE__, __LINE__, __METHOD__, 10 );

			return $retval;
		}

		return FALSE;
	}

	/**
	 * @param int $pid Process ID
	 * @return bool|null
	 */
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

	/**
	 * @return bool|int
	 */
	function create() {
		//Attempt to create directory if it does not already exist.
		$dir = dirname( $this->getFileName() );
		if ( file_exists( $dir ) == FALSE ) {
			$mkdir_result = @mkdir( $dir, 0777, TRUE ); //ugo+rwx
			if ( $mkdir_result == FALSE ) {
				Debug::Text( 'ERROR: Unable to create lock file directory: ' . $dir, __FILE__, __LINE__, __METHOD__, 10 );
			} else {
				Debug::Text( 'WARNING: Created lock file directory as it didnt exist: ' . $dir, __FILE__, __LINE__, __METHOD__, 10 );
			}
		}

		//Write current PID to file, so we can check if its still running later on.
		$retval = @file_put_contents( $this->getFileName(), $this->getCurrentPID() );
		@chmod( $this->getFileName(), 0660 ); //ug+rw

		return $retval;
	}

	/**
	 * @return bool
	 */
	function delete() {
		if ( file_exists( $this->getFileName() ) ) {
			return @unlink( $this->getFileName() );
		}

		Debug::text(' Failed deleting lock file: '. $this->file_name, __FILE__, __LINE__, __METHOD__, 10);

		return FALSE;
	}

	/**
	 * @return bool|null
	 */
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
