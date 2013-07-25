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
 * $Revision: 3725 $
 * $Id: CLI.inc.php 3725 2010-07-28 21:16:05Z ipso $
 * $Date: 2010-07-28 14:16:05 -0700 (Wed, 28 Jul 2010) $
 */
//Allow both CLI and CGI PHP binaries to call maint scripts.
if ( PHP_SAPI != 'cli' AND PHP_SAPI != 'cgi' AND PHP_SAPI != 'cgi-fcgi') {
	echo "This script can only be called from the Command Line.\n";
	exit;
}

if ( version_compare( PHP_VERSION, 5, '<') == 1 ) {
	echo "You are currently using PHP v". PHP_VERSION ." TimeTrex requires PHP v5 or greater!\n";
	exit;
}

//Allow CLI scripts to run much longer.
ini_set( 'max_execution_time', 7200 );

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
	echo "--------------------------------------\n\n\n";
}

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