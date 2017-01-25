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


require_once('../includes/global.inc.php');
if ((isset($config_vars['other']['installer_enabled']) and $config_vars['other']['installer_enabled'] == 1)
    or (isset($_SERVER['HTTP_REFERER']) and stristr($_SERVER['HTTP_REFERER'], 'quick_punch'))
) {
    Debug::text('AJAX Server - Installer enabled, or using quickpunch... NOT AUTHENTICATING...', __FILE__, __LINE__, __METHOD__, 10);
    $authenticate = false;
}
$skip_message_check = true;

unset($_GET['cb'], $_POST['cb']); //Prevent usage of a callback function which could result in an exploit.

require_once(Environment::getBasePath() . 'includes/Interface.inc.php');
require_once('HTML/AJAX/Server.php');

class AutoServer extends HTML_AJAX_Server
{
    // this flag must be set for your init methods to be used
    public $initMethods = true;

    // init method for my ajax class
    public function initAJAX_Server()
    {
        $ajax = new AJAX_Server();
        $this->registerClass($ajax);
    }
}

$server = new AutoServer();
$server->handleRequest();

Debug::text('AJAX Server called...', __FILE__, __LINE__, __METHOD__, 10);
Debug::writeToLog();
