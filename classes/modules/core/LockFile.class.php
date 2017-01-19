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
 * $Revision: 1827 $
 * $Id: FastTree.class.php 1827 2008-04-17 16:57:18Z ipso $
 * $Date: 2008-04-17 09:57:18 -0700 (Thu, 17 Apr 2008) $
 */

/**
 * @package Core
 */
class LockFile {
	var $file_name = NULL;

	var $max_lock_file_age = 86400;

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

	function create() {
		return touch( $this->getFileName() );
	}

	function delete() {
		if ( file_exists( $this->getFileName() ) ) {
			return unlink( $this->getFileName() );
		}

		Debug::text(' Failed deleting lock file: '. $this->file_name, __FILE__, __LINE__, __METHOD__, 10);

		return FALSE;
	}

	function exists() {
		//Ignore lock files older than max_lock_file_age, so if the server crashes or is rebooted during an operation, it will start again the next day.
		clearstatcache();
		if ( file_exists( $this->getFileName() ) AND filemtime( $this->getFileName() ) >= ( time()-$this->max_lock_file_age ) ) {
			return TRUE;
		}

		return FALSE;
	}
}
?>
