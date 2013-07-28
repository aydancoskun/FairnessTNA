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
 * $Revision: 9967 $
 * $Id: install.php 9967 2013-05-22 15:10:29Z ipso $
 * $Date: 2013-05-22 08:10:29 -0700 (Wed, 22 May 2013) $
 */

/*

	This files only purpose is to confirm we are running PHP5, and that the
	templates_c directory is writable so we can forward the user to License.php

*/
echo "<html><body>";
echo "Checking pre-flight requirements... ";
echo " 1...";

ini_set('display_errors', 1 ); //Try to display any errors that may arise on this page.
ini_set('default_socket_timeout', 5);
ini_set('allow_url_fopen', 1);

echo " 2...";
if ( isset($_GET['external_installer']) ) {
	$external_installer = (int)$_GET['external_installer'];
} else {
	$external_installer = 0;
}

echo " 3...";
$templates_c_dir = dirname(__FILE__) . DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'templates_c';

echo " 4...";
$redir = TRUE;
if ( version_compare( PHP_VERSION, 5, '<') == 1 ) {
	echo "You are currenting using PHP v<b>". PHP_VERSION ."</b> TimeTrex requires PHP <b>v5</b> or greater!<br><br>\n";
	$redir = FALSE;
}
if ( version_compare( PHP_VERSION, '5.4.99', '>') == 1 ) {
	echo "You are currenting using PHP v<b>". PHP_VERSION ."</b> Fairness requires PHP <b>v5.4.x</b> or earlier!<br><br>\n";
	$redir = FALSE;
}

echo " 5...";
if ( !is_writeable($templates_c_dir) ) {
	echo "<b>". $templates_c_dir ."</b> is NOT writable by your web server! please give your webserver write permissions to this directory.<br><br>\n";
	$redir = FALSE;
}

echo " 6...";
if ( extension_loaded( 'gettext' ) == FALSE ) {
	echo "PHP GetText extension is not installed, TimeTrex requires GetText to be installed.<br><br>\n";
	$redir = FALSE;
}

echo " 7...";
$test_template_c_sub_dir = $templates_c_dir . DIRECTORY_SEPARATOR . uniqid();
if ( @mkdir( $test_template_c_sub_dir ) !== TRUE ) {
	echo "Your web server is unable to create directories inside of: <b>". $templates_c_dir ."</b>, please give your webserver write permissions to this directory.<br><br>\n";
	$redir = FALSE;
}
echo " 8...";
@rmdir( $test_template_c_sub_dir );
unset($test_template_c_sub_dir);

echo " 9...";

echo " 10...";
if ( $redir == TRUE ) {
	echo " PASSED!<br><br>\n";
	echo "Please wait while we automatically redirect you to the <a href='License.php?external_installer=". $external_installer ."'>installer</a>.";
	echo "<meta http-equiv='refresh' content='0;url=License.php?external_installer=". $external_installer ."'>";

} else {
	echo " FAILED!<br><br>\n";
	echo $config_vars['urls']['installation_support'];
}
echo "</body></html>";
?>
