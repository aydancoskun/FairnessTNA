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

//Allow only CLI PHP binaries to call maint scripts. To avoid a remote party from running them from hitting a URL.
if ( PHP_SAPI != 'cli' ) {
	echo "This script can only be called from the Command Line.\n";
	exit;
}

if ( version_compare( PHP_VERSION, 5, '<') == 1 ) {
	echo "You are currently using PHP v". PHP_VERSION ." Fairness requires PHP v5 or greater!\n";
	exit;
}

//Allow CLI scripts to run much longer. ie: Purging database could takes hours.
ini_set( 'max_execution_time', 43200 );

//Check post install requirements, because PHP CLI usually uses a different php.ini file.
$install_obj = new Install();
if ( $install_obj->checkAllRequirements( TRUE ) == 1 ) {
	$failed_requirements = $install_obj->getFailedRequirements( TRUE );
	unset($failed_requirements[0]);
	echo "----WARNING----WARNING----WARNING-----\n";
	echo "--------------------------------------\n";
	echo "Minimum PHP Requirements are NOT met!!\n";
	echo "--------------------------------------\n";
	echo "Failed Requirements: ".implode(',', (array)$failed_requirements )." \n";
	echo "--------------------------------------\n";
	echo "PHP INI: ". $install_obj->getPHPConfigFile() ." \n";
	echo "Process Owner: ". $install_obj->getWebServerUser() ." \n";
	echo "--------------------------------------\n\n\n";
}
unset($install_obj);

TTi18n::chooseBestLocale(); //Make sure a locale is set, specifically when generating PDFs.

//Uncomment the below block to force debug logging with maintenance jobs.
/*
Debug::setEnable( TRUE );
Debug::setBufferOutput( TRUE );
Debug::setEnableLog( TRUE );
if ( Debug::getVerbosity() <= 1 ) {
	Debug::setVerbosity( 1 );
}
*/
?>