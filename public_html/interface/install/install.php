<?php
/**********************************************************************************
 * This file is part of "FairnessTNA", a Payroll and Time Management program.
 * FairnessTNA is copyright 2013-2017 Aydan Coskun (aydan.ayfer.coskun@gmail.com)
 * others. For full attribution and copyrights details see the COPYRIGHT file.
 *
 * FairnessTNA is free software; you can redistribute it and/or modify it under the
 * terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation, either version 3 of the License, or (at you option )
 * any later version.
 *
 * FairnessTNA is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE.  See the GNU Affero General Public License for more
 * details.
 *
 * You should have received a copy of the GNU Affero General Public License along
 * with this program; if not, see http://www.gnu.org/licenses or write to the Free
 * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301 USA.
 *********************************************************************************/


/*

    This files only purpose is to confirm we are running PHP5, and that the
    templates_c directory is writable so we can forward the user to License.php

*/
echo "<html><body>";
echo "Checking pre-flight requirements... ";
echo " 1...";

ini_set('display_errors', 1); //Try to display any errors that may arise on this page.
ini_set('default_socket_timeout', 5);
ini_set('allow_url_fopen', 1);

echo " 2...";
if (isset($_GET['external_installer'])) {
    $external_installer = (int)$_GET['external_installer'];
} else {
    $external_installer = 0;
}

echo " 3...";
$templates_c_dir = dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'templates_c';

echo " 4...";
$redir = true;
if (version_compare(PHP_VERSION, '5.3.0', '<') == 1) {
    echo "You are currently using PHP v<b>" . PHP_VERSION . "</b> FairnessTNA requires PHP <b>v5.3</b> or greater!<br><br>\n";
    $redir = false;
}
if (version_compare(PHP_VERSION, '7.0.99', '>') == 1) {
    echo "You are currently using PHP v<b>" . PHP_VERSION . "</b> FairnessTNA requires PHP <b>v7.0.x</b> or earlier!<br><br>\n";
    $redir = false;
}

echo " 5...";
if (!is_writeable($templates_c_dir)) {
    echo "<b>" . $templates_c_dir . "</b> is NOT writable by your web server! For help on this topic click <a href='https://github.com/aydancoskun/fairness?t=66'>here</a>.<br><br>\n";
    $redir = false;
}

echo " 6...";
//These are all extensions required to even initialize the HTML5 interface.
//if ( extension_loaded( 'intl' ) == FALSE ) {
//	echo "PHP INTL extension is not installed, FairnessTNA requires INTL to be installed.<br><br>\n";
//	$redir = FALSE;
//}
if (extension_loaded('gettext') == false) {
    echo "PHP GetText extension is not installed, FairnessTNA requires GetText to be installed.<br><br>\n";
    $redir = false;
}
if (extension_loaded('mbstring') == false) {
    echo "PHP MBSTRING extension is not installed, FairnessTNA requires MBSTRING to be installed.<br><br>\n";
    $redir = false;
}
if (extension_loaded('json') == false) {
    echo "PHP JSON extension is not installed, FairnessTNA requires JSON to be installed.<br><br>\n";
    $redir = false;
}

echo " 7...";
$test_template_c_sub_dir = $templates_c_dir . DIRECTORY_SEPARATOR . uniqid();
if (@mkdir($test_template_c_sub_dir) !== true) {
    //If SELinux is installed, could try: chcon -t httpd_sys_content_t storage
    echo "Your web server is unable to create directories inside of: <b>" . $templates_c_dir . "</b>, please give your webserver write permissions to this directory. For help on this topic click <a href='https://github.com/aydancoskun/fairness?t=66'>here</a>.<br><br>\n";
    $redir = false;
}
echo " 8...";
@rmdir($test_template_c_sub_dir);
unset($test_template_c_sub_dir);

echo " 9...";

echo " 10...";
if ($redir == true) {
    echo " PASSED!<br><br>\n";
    echo "Please wait while we automatically redirect you to the <a href='License.php?external_installer=" . $external_installer . "'>installer</a>.";
    //echo "<meta http-equiv='refresh' content='0;url=License.php?external_installer=". $external_installer ."'>";
    echo "<meta http-equiv='refresh' content='0;url=../html5/index.php?installer=1&disable_db=1&external_installer=" . $external_installer . "#!m=Install&a=license&external_installer=" . $external_installer . "'>";
} else {
    echo " FAILED!<br><br>\n";
    echo 'For installation support, please join our community. <a href="https://github.com/aydancoskun/fairness" target="_blank"></a><br>' . "\n";
}
echo "</body></html>";
