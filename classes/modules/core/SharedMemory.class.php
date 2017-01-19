<?php
/*********************************************************************************
 * This file is part of "Fairness", a Payroll and Time Management program.
 * Fairness is Copyright 2013 Aydan Coskun (aydan.ayfer.coskun@gmail.com)
 * Portions of this software are Copyright (C) 2003 - 2013 TimeTrex Software Inc.
 * because Fairness is a fork of "TimeTrex Workforce Management" Software.
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
/*
 * $Revision: 2490 $
 * $Id: SharedMemory.class.php 2490 2009-04-24 22:13:40Z ipso $
 * $Date: 2009-04-24 15:13:40 -0700 (Fri, 24 Apr 2009) $
 */

/**
 * @package Core
 */
require_once( Environment::getBasePath() .'/classes/pear/System/SharedMemory.php');
class SharedMemory {
	protected $obj = NULL;

	function __construct() {
		global $config_vars;

		$shared_memory = new System_SharedMemory();
		if ( OPERATING_SYSTEM == 'WIN' ) {
			//$this->obj = &System_SharedMemory::Factory( 'File', array('tmp' => $config_vars['cache']['dir'] ) );
			$this->obj = $shared_memory->Factory( 'File', array('tmp' => $config_vars['cache']['dir'] ) );
		} else {
			//$this->obj = &System_SharedMemory::Factory( 'File', array('tmp' => $config_vars['cache']['dir'] ) );
			$this->obj = $shared_memory->Factory( 'File', array('tmp' => $config_vars['cache']['dir'] ) );
			////$this->obj = &System_SharedMemory::Factory( 'Systemv', array( 'size' => $size ) ); //Run into size issues all the time.
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
