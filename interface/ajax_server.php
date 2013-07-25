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
 * $Revision: 3224 $
 * $Id: ajax_server.php 3224 2009-12-26 18:10:21Z ipso $
 * $Date: 2009-12-26 10:10:21 -0800 (Sat, 26 Dec 2009) $
 */

require_once('../includes/global.inc.php');
if ( ( isset($config_vars['other']['installer_enabled']) AND $config_vars['other']['installer_enabled'] == 1)
		OR ( isset($_SERVER['HTTP_REFERER']) AND stristr( $_SERVER['HTTP_REFERER'], 'quick_punch') ) ) {
        Debug::text('AJAX Server - Installer enabled, or using quickpunch... NOT AUTHENTICATING...', __FILE__, __LINE__, __METHOD__, 10);
		$authenticate = FALSE;
}
$skip_message_check = TRUE;

require_once(Environment::getBasePath() .'includes/Interface.inc.php');
require_once('HTML/AJAX/Server.php');

class AutoServer extends HTML_AJAX_Server {
        // this flag must be set for your init methods to be used
        var $initMethods = true;

        // init method for my ajax class
        function initAJAX_Server() {
			$ajax = new AJAX_Server();
			$this->registerClass($ajax);
        }
}

$server = new AutoServer();
$server->handleRequest();

Debug::text('AJAX Server called...', __FILE__, __LINE__, __METHOD__, 10);
Debug::writeToLog();
?>