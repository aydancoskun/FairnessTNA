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

//
//http://danielmclaren.net/2008/08/13/tracking-progress-of-a-server-side-action-in-flashflex
//
class ProgressBar {
	protected $obj = NULL;

	var $default_key = NULL;
	private $key_prefix = 'progress_bar_';

	var $update_iteration = 1; //This is how often we actually update the progress bar, even if the function is called more often.

	function __construct() {
		try {
			$this->obj = new SharedMemory();
			return TRUE;
		} catch ( Exception $e ) {
			Debug::text('ERROR: Caught Exception: '. $e->getMessage(), __FILE__, __LINE__, __METHOD__, 9);
			return FALSE;
		}
	}

	//Allow setting a default key so we don't have to pass the key around outside of this object.
	function setDefaultKey( $key ) {
		$this->default_key = $key;
	}
	function getDefaultKey() {
		return $this->default_key;
	}
	
	function error( $key, $msg = NULL ) {
		Debug::text('error: \''. $key .' Key: '. $key .'('.microtime(TRUE).') Message: '. $msg, __FILE__, __LINE__, __METHOD__, 9);

		if ( $key == '' ) {
			$key = $this->getDefaultKey();
			if ( $key == '' ) {
				return FALSE;
			}
		}

		if ( !is_object($this->obj) ) { //If there is an error getting the shared memory object, cancel out early.
			return FALSE;
		}

		if (  $msg == '' ) {
			$msg = TTi18n::getText('Processing...');
		}

		try {
			$progress_bar_arr = $this->obj->get( $this->key_prefix.$key );
			$progress_bar_arr['status_id'] = 9999;
			$progress_bar_arr['message'] = $msg;

			$this->obj->set( $this->key_prefix.$key, $progress_bar_arr );
			return TRUE;
		} catch ( Exception $e ) {
			Debug::text('ERROR: Caught Exception: '. $e->getMessage(), __FILE__, __LINE__, __METHOD__, 9);
			return FALSE;
		}
	}

	function start( $key, $total_iterations = 100, $update_iteration = NULL, $msg = NULL ) {
		Debug::text('start: \''. $key .'\' Iterations: '. $total_iterations .' Update Iterations: '. $update_iteration .' Key: '. $key .'('.microtime(TRUE).') Message: '. $msg, __FILE__, __LINE__, __METHOD__, 9);

		if ( $key == '' ) {
			$key = $this->getDefaultKey();
			if ( $key == '' ) {
				return FALSE;
			}
		}

		if ( $total_iterations <= 1 ) {
			return FALSE;
		}

		if ( !is_object($this->obj) ) { //If there is an error getting the shared memory object, cancel out early.
			return FALSE;
		}
		
		if ( $update_iteration == '' ) {
			$this->update_iteration = ceil($total_iterations / 20); //Update every 5%.
		} else {
			$this->update_iteration = $update_iteration;
		}

		if (  $msg == '' ) {
			$msg = TTi18n::getText('Processing...');
		}

		$epoch = microtime(TRUE);

		$progress_bar_arr = array(
					'status_id' => 10,
					'start_time' => $epoch,
					'current_iteration' => 0,
					'total_iterations' => $total_iterations,
					'last_update_time' => $epoch,
					'message' => $msg,
					);
		try {
			$this->obj->set( $this->key_prefix.$key, $progress_bar_arr );
			return TRUE;
		} catch ( Exception $e ) {
			Debug::text('ERROR: Caught Exception: '. $e->getMessage(), __FILE__, __LINE__, __METHOD__, 9);
			return FALSE;
		}
	}

	function delete( $key ) {
		return $this->stop( $this->key_prefix.$key );
	}
	function stop( $key ) {
		if ( $key == '' ) {
			$key = $this->getDefaultKey();
			if ( $key == '' ) {
				return FALSE;
			}
		}

		if ( !is_object($this->obj) ) { //If there is an error getting the shared memory object, cancel out early.
			return FALSE;
		}

		try {
			return $this->obj->delete( $this->key_prefix.$key );
		} catch ( Exception $e ) {
			Debug::text('ERROR: Caught Exception: '. $e->getMessage(), __FILE__, __LINE__, __METHOD__, 9);
			return FALSE;
		}
	}

	function set( $key, $current_iteration, $msg = NULL ) {
		if ( $key == '' ) {
			$key = $this->getDefaultKey();
			if ( $key == '' ) {
				return FALSE;
			}
		}

		if ( !is_object($this->obj) ) { //If there is an error getting the shared memory object, cancel out early.
			return FALSE;
		}

		//Add quick IF statement to short circuit any work unless we meet the update_iteration, ie: every X calls do we actually do anything.
		//When processing long batches though, we need to update every iteration for the first 10 iterations so we can get an accruate estimated time for completion.
		if ( $current_iteration <= 10 OR ( $current_iteration % $this->update_iteration ) == 0 ) {
			//Debug::text('set: '. $key .' Iteration: '. $current_iteration, __FILE__, __LINE__, __METHOD__, 9);

			try {
				$progress_bar_arr = $this->obj->get( $this->key_prefix.$key );

				if ( $progress_bar_arr != FALSE
						AND is_array( $progress_bar_arr )
						AND $current_iteration >= 0
						AND $current_iteration <= $progress_bar_arr['total_iterations']) {
	
					/*
					if ( PRODUCTION == FALSE AND isset($progress_bar_arr['total_iterations']) AND $progress_bar_arr['total_iterations'] >= 1 ) {
						//Add a delay based on the total iterations so we can test the progressbar more often
						$total_delay = 15000000; //10seconds
						usleep( ( ($total_delay / $progress_bar_arr['total_iterations']) * $this->update_iteration));
					}
					*/
	
					$progress_bar_arr['current_iteration'] = $current_iteration;
					$progress_bar_arr['last_update_time'] = microtime(TRUE);
				}
	
				if ( $msg != '' ) {
					$progress_bar_arr['message'] = $msg;
				}

				return $this->obj->set( $this->key_prefix.$key, $progress_bar_arr );
			} catch ( Exception $e ) {
				Debug::text('ERROR: Caught Exception: '. $e->getMessage(), __FILE__, __LINE__, __METHOD__, 9);
				return FALSE;
			}
		
		}

		return TRUE;
	}

	function get($key) {
		if ( $key == '' ) {
			$key = $this->getDefaultKey();
			if ( $key == '' ) {
				return FALSE;
			}
		}

		if ( !is_object($this->obj) ) { //If there is an error getting the shared memory object, cancel out early.
			return FALSE;
		}
		
		try {
			return $this->obj->get( $this->key_prefix.$key );
		} catch ( Exception $e ) {
			Debug::text('ERROR: Caught Exception: '. $e->getMessage(), __FILE__, __LINE__, __METHOD__, 9);
			return FALSE;
		}		
	}

	function test( $key, $total_iterations = 10 ) {
		Debug::text('testProgressBar: '. $key .' Iterations: '. $total_iterations, __FILE__, __LINE__, __METHOD__, 9);

		$this->start( $key, $total_iterations );

		for($i = 1; $i <= $total_iterations; $i++ ) {
			$this->set( $key, $i);
			sleep(rand(1, 2));
		}

		$this->stop( $key );

		return TRUE;
	}
}
?>
