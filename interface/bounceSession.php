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

require_once('../includes/global.inc.php');

/*
 * Get FORM variables
 */
extract	(FormVariables::GetVariables(
										array	(
												'name',
												'value',
												'expires',
												'redirect',
												'key'
												) ) );

//Used to help set cookies across domains. Currently used by Flex
$authentication = new Authentication();
if ( $name == '' ) {
	$name = $authentication->getName();
}

if ( $expires == '' ) {
	$expires = ( time() + 7776000 );
}

setcookie( $name, $value, $expires, Environment::getCookieBaseURL(), NULL, Misc::isSSL( TRUE ) );

if ( $redirect != '' ) {
	//This can result in a phishing attack, if the user is redirected to an outside site.
	Debug::Text('Attempting Redirect: '. $redirect .' Current hostname: '. Misc::getHostName(), __FILE__, __LINE__, __METHOD__, 10);

	if ( str_replace( array('http://', 'https://'), '', $redirect ) == Misc::getHostName()
			OR strpos( str_replace( array('http://', 'https://'), '', $redirect ), Misc::getHostName().'/' ) === 0 ) { //Make sure we match exactly or with a '/' at the end to prevent ondemand.mydomain.com.phish.com from being accepted.
		Redirect::Page( $redirect );
	} else {
		Debug::Text('ERROR: Unable to redirect to: '. $redirect .' as it does not contain hostname: '. Misc::getHostName(), __FILE__, __LINE__, __METHOD__, 10);
		echo "ERROR: Unable to redirect...<br>\n";
	}
}
Debug::writeToLog();
?>