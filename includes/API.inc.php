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
 * $Revision: 2205 $
 * $Id: CLI.inc.php 2205 2008-10-17 22:17:40Z ipso $
 * $Date: 2008-10-17 15:17:40 -0700 (Fri, 17 Oct 2008) $
 */
define('TIMETREX_API', TRUE );

//Returns session ID from _COOKIE, _POST, then _GET.
function getSessionID() {
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