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
 * $Revision: 5805 $
 * $Id: Environment.class.php 5805 2011-12-21 00:24:15Z ipso $
 * $Date: 2011-12-20 16:24:15 -0800 (Tue, 20 Dec 2011) $
 */

/**
 * @package Core
 */
class Environment {

	static protected $template_dir = 'templates';
	static protected $template_compile_dir = 'templates_c';

	static function getBasePath() {
		//return dirname( __FILE__ ) . DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR;
		return str_replace('classes'. DIRECTORY_SEPARATOR . 'modules'. DIRECTORY_SEPARATOR .'core', '', dirname( __FILE__ ) );
	}

	static function getHostName() {
		return Misc::getHostName( TRUE );
	}

	static function getBaseURL() {
		global $config_vars;

		if ( isset($config_vars['path']['base_url']) ) {
			return $config_vars['path']['base_url']. '/'; //Don't use directory separator here
		}

		return '/';
	}

	//Returns the BASE_URL for the API functions.
	static function getAPIBaseURL( $api = NULL ) {
		global $config_vars;

		//If "interface" appears in the base URL, replace it with API directory
		$base_url = str_replace( array('/interface','/api'), '', $config_vars['path']['base_url']);

		if ( $api == '' ) {
			if ( defined('AMF_API') AND AMF_API == TRUE ) {
				$api = 'amf';
			} elseif ( defined('SOAP_API') AND SOAP_API == TRUE )  {
				$api = 'soap';
			} elseif ( defined('JSON_API') AND JSON_API == TRUE )  {
				$api = 'json';
			}
		}

		$base_url = $base_url.'/api/'.$api.'/';

		return $base_url;
	}

	static function getAPIURL( $api ) {
		return self::getAPIBaseURL( $api ).'api.php';
	}

	static function getTemplateDir() {
		return self::getBasePath() . self::$template_dir . DIRECTORY_SEPARATOR;
	}

	static function getTemplateCompileDir() {
		return self::getBasePath() . self::$template_compile_dir . DIRECTORY_SEPARATOR;
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
