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
 * $Revision: 9743 $
 * $Id: CheckForUpdate.php 9743 2013-05-02 21:22:23Z ipso $
 * $Date: 2013-05-02 14:22:23 -0700 (Thu, 02 May 2013) $
 */
/*
 * Checks for any version updates...
 *
 */
require_once( dirname(__FILE__) . DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'includes'. DIRECTORY_SEPARATOR .'global.inc.php');
require_once( dirname(__FILE__) . DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'includes'. DIRECTORY_SEPARATOR .'CLI.inc.php');

$latest_version = false;
$handle = @fopen("https://raw.github.com/Aydan/fairness/master/VERSION", "r");
if ($handle) {
	$latest_version = trim(fgets($handle, 4096));
	fclose($handle);
	Debug::Text('Github says latest version is '.$latest_version, __FILE__, __LINE__, __METHOD__,10);
} else {
	Debug::Text('Auto Update Notifications failed!', __FILE__, __LINE__, __METHOD__,10);
}

		$sslf = new SystemSettingListFactory();
		$sslf->getByName('new_version');
		if ( $sslf->getRecordCount() == 1 ) {
			$obj = $sslf->getCurrent();
		} else {
			$obj = new SystemSettingListFactory();
		}
		$obj->setName( 'new_version' );

		if ( $latest_version AND version_compare( APPLICATION_VERSION, $latest_version, '<') === TRUE ) {
			$obj->setValue( 1 );
			Debug::Text('Auto Update Notifications check => new_version = TRUE', __FILE__, __LINE__, __METHOD__,10);
		} else {
			$obj->setValue( 0 );
			Debug::Text('Auto Update Notifications check => FALSE', __FILE__, __LINE__, __METHOD__,10);
		}

		if ( $obj->isValid() ) {
			$obj->Save();
		}


Debug::writeToLog();
Debug::Display();
