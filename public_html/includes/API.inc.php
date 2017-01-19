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

define('FAIRNESS_API', TRUE );
forceNoCacheHeaders(); //Send headers to disable caching.

//Returns valid classes when unauthenticated.
function getUnauthenticatedAPIClasses() {
	return array('APIAuthentication', 'APIClientStationUnAuthenticated', 'APIAuthenticationPlugin', 'APIClientStationUnAuthenticatedPlugin', 'APIProgressBar', 'APIInstall');
}

//Returns session ID from _COOKIE, _POST, then _GET.
function getSessionID() {

	//FIXME: Work-around for bug in Mobile app v3.0.86 that uses old SessionIDs in the Cookie, but correct ones on the URL.
	if ( isset($_COOKIE['SessionID']) AND isset($_GET['SessionID']) AND $_COOKIE['SessionID'] != $_GET['SessionID'] ) {
		//Debug::Arr( array($_COOKIE, $_POST, $_GET), 'Input Data:', __FILE__, __LINE__, __METHOD__, 10);
		Debug::Text( 'WARNING: Two different SessionIDs sent, COOKIE: '. $_COOKIE['SessionID'] .' GET: '. $_GET['SessionID'], __FILE__, __LINE__, __METHOD__, 10);
		if ( isset($_SERVER['REQUEST_URI']) AND stripos( $_SERVER['REQUEST_URI'], 'APIClientStationUnAuthenticated' ) !== FALSE ) {
			Debug::Text( 'Using GET Session ID...', __FILE__, __LINE__, __METHOD__, 10);
			unset($_COOKIE['SessionID']);
		}
	}

	if ( isset($_COOKIE['SessionID']) AND $_COOKIE['SessionID'] != '' ) {
		$session_id = $_COOKIE['SessionID'];
	} elseif ( isset($_POST['SessionID']) AND $_POST['SessionID'] != '' ) {
		$session_id = $_POST['SessionID'];
	} elseif ( isset($_GET['SessionID']) AND $_GET['SessionID'] != '' ) {
		$session_id = $_GET['SessionID'];
	} else {
		$session_id = FALSE;
	}

	return $session_id;
}

//Returns Station ID from _COOKIE, _POST, then _GET.
function getStationID() {
	if ( isset($_COOKIE['StationID']) AND $_COOKIE['StationID'] != '' ) {
		$station_id = $_COOKIE['StationID'];
	} elseif ( isset($_POST['StationID']) AND $_POST['StationID'] != '' ) {
		$station_id = $_POST['StationID'];
	} elseif ( isset($_GET['StationID']) AND $_GET['StationID'] != '' ) {
		$station_id = $_GET['StationID'];
	} else {
		$station_id = FALSE;
	}

	return $station_id;
}

//Make sure cron job information is always logged.
//Don't do this until log rotation is implemented.
/*
Debug::setEnable( TRUE );
Debug::setBufferOutput( TRUE );
Debug::setEnableLog( TRUE );
if ( Debug::getVerbosity() <= 1 ) {
	Debug::setVerbosity( 1 );
}
*/
?>