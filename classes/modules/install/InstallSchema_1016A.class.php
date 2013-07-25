<?php
/*********************************************************************************
 * This file is part of "Fairness", a Payroll and Time Management program.
 * Fairness is Copyright 2013 Aydan Coscun (aydan.ayfer.coskun@gmail.com)
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
 * $Revision: 8371 $
 * $Id: InstallSchema_1016A.class.php 8371 2012-11-22 21:18:57Z ipso $
 * $Date: 2012-11-22 13:18:57 -0800 (Thu, 22 Nov 2012) $
 */

/**
 * @package Modules\Install
 */
class InstallSchema_1016A extends InstallSchema_Base {

	protected $station_users = array();

	function preInstall() {
		Debug::text('preInstall: '. $this->getVersion() , __FILE__, __LINE__, __METHOD__,9);

		return TRUE;
	}

	function postInstall() {
		global $cache;

		Debug::text('postInstall: '. $this->getVersion(), __FILE__, __LINE__, __METHOD__,9);

		Debug::text('l: '. $this->getVersion(), __FILE__, __LINE__, __METHOD__,9);

		$cjlf = TTnew( 'CronJobListFactory' );
		$cjlf->getAll();
		if ( $cjlf->getRecordCount() > 0 ) {
			foreach( $cjlf as $cj_obj ) {
				Debug::text('Original Command: '.  $cj_obj->getCommand(), __FILE__, __LINE__, __METHOD__,9);
				$retval = preg_match('/([A-Za-z0-9]+\.php)/i', $cj_obj->getCommand(), $matches );

				if ( isset($matches[0]) AND $matches[0] != '' ) {
					Debug::text('New Command: '. $matches[0] , __FILE__, __LINE__, __METHOD__,9);
					$cj_obj->setCommand( $matches[0] );
					if ( $cj_obj->isValid() ) {
						$cj_obj->Save();
					}
				}
			}
		}

		return TRUE;
	}
}
?>
