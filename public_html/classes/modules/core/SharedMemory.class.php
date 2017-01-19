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
require_once( Environment::getBasePath() .'/classes/pear/System/SharedMemory.php');
class SharedMemory {
	protected $obj = NULL;

	function __construct() {
		global $config_vars;

		$shared_memory = new System_SharedMemory();
		if ( isset($config_vars['cache']['redis_host']) AND $config_vars['cache']['redis_host'] != '' ) {
			$split_server = explode(',', $config_vars['cache']['redis_host'] );
			$host = $split_server[0]; //Use just the master server.
			
			$this->obj = $shared_memory->Factory( 'Redis', array('host' => $host, 'db' => ( isset($config_vars['cache']['redis_db']) ) ? $config_vars['cache']['redis_db'] : '', 'timeout' => 1 ) );
		} else {
			if ( OPERATING_SYSTEM == 'WIN' ) {
				$this->obj = $shared_memory->Factory( 'File', array('tmp' => $config_vars['cache']['dir'] ) );
			} else {
				$this->obj = $shared_memory->Factory( 'File', array('tmp' => $config_vars['cache']['dir'] ) );
				////$this->obj = &System_SharedMemory::Factory( 'Systemv', array( 'size' => $size ) ); //Run into size issues all the time.
			}
		}

		return TRUE;
	}

	function set( $key, $value ) {
		if ( is_string( $key ) ) {
			return $this->obj->set( $key, $value );
		}
		return FALSE;
	}

	function get( $key ) {
		if ( is_string( $key ) ) {
			return $this->obj->get( $key );
		}
		return FALSE;
	}

	function delete( $key ) {
		if ( is_string( $key ) ) {
			return $this->obj->rm( $key );
		}
		return FALSE;
	}
}
?>
