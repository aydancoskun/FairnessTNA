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


/**
 * @package Core
 */
class Environment {

	static protected $template_dir = 'templates';
	static protected $template_compile_dir = 'templates_c';

	static function stripDuplicateSlashes( $path ) {
		return preg_replace('/([^:])(\/{2,})/', '$1/', $path);
	}

	static function getBasePath() {
		//return dirname( __FILE__ ) . DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR;
		return str_replace('classes'. DIRECTORY_SEPARATOR . 'modules'. DIRECTORY_SEPARATOR .'core', '', dirname( __FILE__ ) );
	}

	static function getHostName() {
		return Misc::getHostName( TRUE );
	}

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

	//Due to how the legacy interface is handled, we need to use the this function to determine the URL to redirect too,
	//as the base_url needs to be /interface most of the time, for images and such to load properly.
	static function getDefaultInterfaceBaseURL() {
		return self::getBaseURL();
	}

	static function getCookieBaseURL() {
		//  "/fairness/interface"
		//  "/fairness/api/json"
		//  "/fairness" <- cookie must go here.
		$retval = str_replace( '\\', '/', dirname( dirname( self::getAPIBaseURL() ) ) ); //PHP5 compatible. dirname(self::getAPIBaseURL(), 2) only works in PHP7. Also Windows tends to use backslashes in some cases, since this is a URL switch to forward slash always.

		if ( $retval == '' ) {
			$retval = '/';
		}

		return $retval;
	}

	//Returns the BASE_URL for the API functions.
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

	static function getAPIURL( $api ) {
		return self::getAPIBaseURL( $api ).'api.php';
	}

	static function getTemplateDir() {
		return self::getBasePath() . self::$template_dir . DIRECTORY_SEPARATOR;
	}

	static function getTemplateCompileDir() {
		global $config_vars;

		if ( isset($config_vars['path']['templates']) ) {
			return $config_vars['path']['templates'] . DIRECTORY_SEPARATOR;
		} else {
			return self::getBasePath() . self::$template_compile_dir . DIRECTORY_SEPARATOR;
		}
	}

	static function getImagesPath() {
		return self::getBasePath() . DIRECTORY_SEPARATOR .'interface'. DIRECTORY_SEPARATOR .'images'. DIRECTORY_SEPARATOR;
	}

	static function getImagesURL() {
		return self::getBaseURL() .'images/';
	}

	static function getStorageBasePath() {
		global $config_vars;

		return $config_vars['path']['storage'] . DIRECTORY_SEPARATOR;
	}

	static function getLogBasePath() {
		global $config_vars;

		return $config_vars['path']['log'] . DIRECTORY_SEPARATOR;
	}

}
?>
