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
define('FAIRNESS_JSON_API', true);
if (isset($_GET['disable_db']) and $_GET['disable_db'] == 1) {
    $disable_database_connection = true;
}
require_once('../../../includes/global.inc.php');
require_once('../../../includes/API.inc.php');
forceNoCacheHeaders(); //Send headers to disable caching.
header('Content-Type: application/javascript; charset=UTF-8');

TTi18n::chooseBestLocale(); //Make sure we set the locale as best we can when not logged in, this is needed for getPreLoginData as well.
$auth = TTNew('APIAuthentication');
?>
var APIGlobal = function () {
};
APIGlobal.pre_login_data = <?php echo json_encode($auth->getPreLoginData());?>; //Convert getPreLoginData() array to JS.

need_load_pre_login_data = false;
var new_session = getCookie('NewSessionID');
if (new_session) {
    setCookie('SessionID', new_session, 30, APIGlobal.pre_login_data.cookie_base_url);

    //Allow NewSessionID cookie to be accessible from one level higher subdomain.
    var host = window.location.hostname;
    host = host.substring((host.indexOf('.') + 1));
    setCookie('NewSessionID', null, 0, APIGlobal.pre_login_data.cookie_base_url, host);

    need_load_pre_login_data = true; // need load it again since APIGlobal.pre_login_data.is_logged_in will be false when first load
}
delete new_session, host;
<?php
Debug::writeToLog();
?>
