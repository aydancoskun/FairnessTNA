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
define('FAIRNESS_JSON_API', TRUE );
if ( isset($_GET['disable_db']) AND $_GET['disable_db'] == 1 ) {
	$disable_database_connection = TRUE;
}
require_once('../../../includes/global.inc.php');
require_once('../../../includes/API.inc.php');
forceNoCacheHeaders(); //Send headers to disable caching.
header('Content-Type: application/javascript; charset=UTF-8');

TTi18n::chooseBestLocale(); //Make sure we set the locale as best we can when not logged in, this is needed for getPreLoginData as well.
$auth = TTNew('APIAuthentication'); /** @var APIAuthentication $auth */
?>
var APIGlobal = function() {};
APIGlobal.pre_login_data = <?php echo json_encode( $auth->getPreLoginData() );?>; //Convert getPreLoginData() array to JS.

need_load_pre_login_data = false;

var alternate_session_data = decodeURIComponent(getCookie( 'AlternateSessionData' ));

if ( alternate_session_data ) {
	alternate_session_data = JSON.parse(alternate_session_data);
	if ( alternate_session_data && alternate_session_data.new_session_id ) {
		setCookie('SessionID', alternate_session_data.new_session_id, 30, APIGlobal.pre_login_data.cookie_base_url);

		alternate_session_data.new_session_id = null;

		//Allow NewSessionID cookie to be accessible from one level higher subdomain.
		var host = window.location.hostname;
		host = host.substring((host.indexOf('.') + 1));

		setCookie('AlternateSessionData', JSON.stringify(alternate_session_data), 1, APIGlobal.pre_login_data.cookie_base_url, host ); //was NewSessionID

		need_load_pre_login_data = true; // need load it again since APIGlobal.pre_login_data.is_logged_in will be false when first load
	}
}
delete alternate_session_data, host;
<?php
Debug::writeToLog();
?>
