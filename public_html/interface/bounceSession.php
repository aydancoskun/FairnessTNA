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

/*
 * Get FORM variables
 */
extract(FormVariables::GetVariables(
    array(
        'name',
        'value',
        'expires',
        'redirect',
        'key'
    )));

//Used to help set cookies across domains. Currently used by Flex
$authentication = new Authentication();
if ($name == '') {
    $name = $authentication->getName();
}

if ($expires == '') {
    $expires = (time() + 7776000);
}

setcookie($name, $value, $expires, Environment::getCookieBaseURL(), null, Misc::isSSL(true));

if ($redirect != '') {
    //This can result in a phishing attack, if the user is redirected to an outside site.
    Debug::Text('Attempting Redirect: ' . $redirect . ' Current hostname: ' . Misc::getHostName(), __FILE__, __LINE__, __METHOD__, 10);

    if (str_replace(array('http://', 'https://'), '', $redirect) == Misc::getHostName()
        or strpos(str_replace(array('http://', 'https://'), '', $redirect), Misc::getHostName() . '/') === 0
    ) { //Make sure we match exactly or with a '/' at the end to prevent ondemand.mydomain.com.phish.com from being accepted.
        Redirect::Page($redirect);
    } else {
        Debug::Text('ERROR: Unable to redirect to: ' . $redirect . ' as it does not contain hostname: ' . Misc::getHostName(), __FILE__, __LINE__, __METHOD__, 10);
        echo "ERROR: Unable to redirect...<br>\n";
    }
}
Debug::writeToLog();
