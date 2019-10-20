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


/**
 * @package Core
 */
class Environment {

	static protected $template_dir = 'templates';
	static protected $template_compile_dir = 'templates_c';

	/**
	 * @param $path
	 * @return mixed
	 */
	static function stripDuplicateSlashes( $path ) {
		return preg_replace('/([^:])(\/{2,})/', '$1/', $path);
	}

	/**
	 * @return mixed
	 */
	static function getBasePath() {
		//return dirname( __FILE__ ) . DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR;
		return str_replace('classes'. DIRECTORY_SEPARATOR . 'modules'. DIRECTORY_SEPARATOR .'core', '', dirname( __FILE__ ) );
	}

	/**
	 * @return string
	 */
	static function getHostName() {
		return Misc::getHostName( TRUE );
	}

	/**
	 * @return mixed
	 */
	static function getBaseURL() {
		global $config_vars;

		$retval = '/';

		if ( isset($config_vars['path']['base_url']) ) {
			if ( substr( $config_vars['path']['base_url'], -1) != '/' ) {
				$retval = $config_vars['path']['base_url']. '/'; //Don't use directory separator here
			} else {
				$retval = $config_vars['path']['base_url'];
			}
		}

		return self::stripDuplicateSlashes( $retval );
	}

	/**
	 * Due to how the legacy interface is handled, we need to use the this function to determine the URL to redirect too,
	 * as the base_url needs to be /interface most of the time, for images and such to load properly.
	 * @return mixed
	 */
	static function getDefaultInterfaceBaseURL() {
		return self::getBaseURL();
	}

	/**
	 * @return mixed|string
	 */
	static function getCookieBaseURL() {
		//  "/interface"
		//  "/api/json"
		//  "/" <- cookie must go here.
		$retval = str_replace( '\\', '/', dirname( dirname( self::getAPIBaseURL() ) ) ); //PHP5 compatible. dirname(self::getAPIBaseURL(), 2) only works in PHP7. Also Windows tends to use backslashes in some cases, since this is a URL switch to forward slash always.

		if ( $retval == '' ) {
			$retval = '/';
		}

		return $retval;
	}

	/**
	 * Returns the BASE_URL for the API functions.
	 * @param null $api
	 * @return mixed
	 */
	static function getAPIBaseURL( $api = NULL ) {
		global $config_vars;

		//If "interface" appears in the base URL, replace it with API directory
		$base_url = str_replace( array('/interface', '/api'), '', $config_vars['path']['base_url']);

		if ( $api == '' ) {
			if ( defined('FAIRNESS_AMF_API') AND FAIRNESS_AMF_API == TRUE ) {
				$api = 'amf';
			} elseif ( defined('FAIRNESS_SOAP_API') AND FAIRNESS_SOAP_API == TRUE )	 {
				$api = 'soap';
			} elseif ( defined('FAIRNESS_JSON_API') AND FAIRNESS_JSON_API == TRUE )	 {
				$api = 'json';
			}
		}

		$base_url = self::stripDuplicateSlashes( $base_url.'/api/'.$api.'/' );

		return $base_url;
	}

	/**
	 * @param $api
	 * @return string
	 */
	static function getAPIURL( $api ) {
		return self::getAPIBaseURL( $api ).'api.php';
	}

	/**
	 * @return string
	 */
	static function getImagesPath() {
		return self::getBasePath() . DIRECTORY_SEPARATOR .'interface'. DIRECTORY_SEPARATOR .'images'. DIRECTORY_SEPARATOR;
	}

	/**
	 * @return string
	 */
	static function getImagesURL() {
		return self::getBaseURL() .'images/';
	}

	/**
	 * @return string
	 */
	static function getStorageBasePath() {
		global $config_vars;

		return $config_vars['path']['storage'] . DIRECTORY_SEPARATOR;
	}

	/**
	 * @return string
	 */
	static function getLogBasePath() {
		global $config_vars;

		return $config_vars['path']['log'] . DIRECTORY_SEPARATOR;
	}

}
?>
