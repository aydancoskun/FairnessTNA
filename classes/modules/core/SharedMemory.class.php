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
require_once( Environment::getBasePath() .'/classes/pear/System/SharedMemory.php');

/**
 * Class SharedMemory
 */
class SharedMemory {
	protected $obj = NULL;

	/**
	 * SharedMemory constructor.
	 */
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

	/**
	 * @param $key
	 * @param $value
	 * @return bool
	 */
	function set( $key, $value ) {
		if ( is_string( $key ) ) {
			return $this->obj->set( $key, $value );
		}
		return FALSE;
	}

	/**
	 * @param $key
	 * @return bool
	 */
	function get( $key ) {
		if ( is_string( $key ) ) {
			return $this->obj->get( $key );
		}
		return FALSE;
	}

	/**
	 * @param $key
	 * @return bool
	 */
	function delete( $key ) {
		if ( is_string( $key ) ) {
			return $this->obj->rm( $key );
		}
		return FALSE;
	}
}
?>
