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
date_default_timezone_set('GMT'); //Default timezone to UTC until we can at least determine or set another one. This should also prevent: PHP ERROR - WARNING(2): getdate(): It is not safe to rely on the system's timezone settings. You are *required* to use the date.timezone setting or the date_default_timezone_set() function

//PHP v5.1.0 introduced $_SERVER['REQUEST_TIME'], but it doesn't include microseconds until v5.4.0.
if ( !isset($_SERVER['REQUEST_TIME_FLOAT']) OR version_compare(PHP_VERSION, '5.4.0', '<') == TRUE ) {
	$_SERVER['REQUEST_TIME_FLOAT'] = microtime( TRUE );
}

//BUG in PHP 5.2.2 that causes $HTTP_RAW_POST_DATA not to be set. Work around it.
//This is deprecated in PHP v5.6 and removed in PHP v7, so switch to just always populating it.
$HTTP_RAW_POST_DATA = file_get_contents('php://input');

if ( !isset($_SERVER['HTTP_HOST']) ) {
	$_SERVER['HTTP_HOST'] = 'localhost';
}

ob_start(); //Take care of GZIP in Apache

if ( ini_get('max_execution_time') < 1800 ) {
	ini_set( 'max_execution_time', 1800 );
}
//Disable magic quotes at runtime. Require magic_quotes_gpc to be disabled during install.
//Check: http://ca3.php.net/manual/en/security.magicquotes.php#61188 for disabling magic_quotes_gpc
ini_set( 'magic_quotes_runtime', 0 );

define('APPLICATION_VERSION', '11.6.1' );
define('APPLICATION_VERSION_DATE', 1570604400 ); //Release date of version. CMD: php -r 'echo "\n". strtotime("09-Oct-2019")."\n\n";'

if ( strtoupper( substr(PHP_OS, 0, 3) ) == 'WIN' ) {
	define('OPERATING_SYSTEM', 'WIN' );
} else {
	define('OPERATING_SYSTEM', 'LINUX' );
}

/*
	Find Config file.
	Can use the following line in .htaccess or Apache virtual host definition to define a config file outside the document root.
	SetEnv TT_CONFIG_FILE /etc/fairnesstna/settings.ini.php

	Or from the CLI:
	export TT_CONFIG_FILE=/etc/fairnesstna/settings.ini.php
*/
if ( isset($_SERVER['TT_CONFIG_FILE']) AND $_SERVER['TT_CONFIG_FILE'] != '' ) {
	define('CONFIG_FILE', $_SERVER['TT_CONFIG_FILE'] );
} else {
	define('CONFIG_FILE', dirname(__FILE__) . DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'settings.ini.php');
}

/*
	Config file outside webroot.
*/
if ( file_exists(CONFIG_FILE) ) {
	$config_vars = parse_ini_file( CONFIG_FILE, TRUE);
	if ( $config_vars === FALSE ) {
		echo "ERROR: Config file (". CONFIG_FILE .") contains a syntax error! If your passwords contain special characters you need to wrap them in double quotes, ie:<br>\n password = \"test!1!me\"\n";
		exit(1);
	}
} else {
	echo "ERROR: Config file (". CONFIG_FILE .") does not exist or is not readable!\n";
	exit(1);
}

if ( defined('PRODUCTION') == FALSE ) {
	if ( isset( $config_vars['debug']['production'] ) AND $config_vars['debug']['production'] == 1 ) {
		define( 'PRODUCTION', TRUE );
	} else {
		define( 'PRODUCTION', FALSE );
	}
}

if ( defined('DEMO_MODE') == FALSE ) {
	if ( isset( $config_vars['other']['demo_mode'] ) AND $config_vars['other']['demo_mode'] == 1 ) {
		define( 'DEMO_MODE', TRUE );
	} else {
		define( 'DEMO_MODE', FALSE );
	}
}
define('APPLICATION_NAME', (PRODUCTION == FALSE AND DEMO_MODE == FALSE) ? 'FairnessTNA-Debug' : 'FairnessTNA'); define('ORGANIZATION_NAME', 'FairnessTNA'); define('ORGANIZATION_URL', 'www.fairnesstna.org');
if ( isset($config_vars['other']['deployment_on_demand']) AND $config_vars['other']['deployment_on_demand'] == 1 ) {
	define('DEPLOYMENT_ON_DEMAND', TRUE);
} else {
	define('DEPLOYMENT_ON_DEMAND', FALSE);
}

if ( isset($config_vars['other']['primary_company_id']) AND $config_vars['other']['primary_company_id'] != '') {
	define('PRIMARY_COMPANY_ID', (string)$config_vars['other']['primary_company_id']);
} else {
	define('PRIMARY_COMPANY_ID', FALSE);
}

if ( PRODUCTION == TRUE ) {
	define('APPLICATION_BUILD', APPLICATION_VERSION .'-'. date('Ymd', APPLICATION_VERSION_DATE ) .'-'. date('His', filemtime( __FILE__ ) ) );
} else {
	define('APPLICATION_BUILD', APPLICATION_VERSION .'-'. date('Ymd') ); //Keep as static as possible as if this changes during JS debugging it will wipe out any temporary break points.
}

//Windows doesn't define LC_MESSAGES, so lets do it manually here.
if ( defined('LC_MESSAGES') == FALSE) {
	define('LC_MESSAGES', 6);
}

//If memory limit is set below the minimum required, just bump it up to that minimum. If its higher, keep the higher value.
$memory_limit = str_ireplace( array('G', 'M', 'K'), array('000000000', '000000', '000'), ini_get('memory_limit') );
if ( $memory_limit >= 0 AND $memory_limit < 512000000 ) { //Use * 1000 rather than * 1024 for easier parsing of G, M, K -- Make sure we consider -1 as the limit.
	ini_set('memory_limit', '512000000');
};
unset($memory_limit);

//IIS 5 doesn't seem to set REQUEST_URI, so attempt to build one on our own
//This also appears to fix CGI mode.
//Inspired by: http://neosmart.net/blog/2006/100-apache-compliant-request_uri-for-iis-and-windows/
if ( !isset($_SERVER['REQUEST_URI']) ) {
	if ( isset($_SERVER['SCRIPT_NAME']) ) {
		$_SERVER['REQUEST_URI'] = $_SERVER['SCRIPT_NAME'];
	} elseif ( isset( $_SERVER['PHP_SELF']) ) {
		$_SERVER['REQUEST_URI'] = $_SERVER['PHP_SELF'];
	}

	if ( isset($_SERVER['QUERY_STRING']) AND $_SERVER['QUERY_STRING'] != '') {
		$_SERVER['REQUEST_URI'] .= '?'.$_SERVER['QUERY_STRING'];
	}
}

//HTTP Basic authentication doesn't work properly with CGI/FCGI unless we decode it this way.
if ( isset($_SERVER['HTTP_AUTHORIZATION']) AND $_SERVER['HTTP_AUTHORIZATION'] != '' AND stripos( php_sapi_name(), 'cgi' ) !== FALSE ) {
	//<IfModule mod_rewrite.c>
	//RewriteEngine on
	//RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization},L]
	//</IfModule>
	//Or this instead:
	//SetEnvIf Authorization "(.*)" HTTP_AUTHORIZATION=$1
	list($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) = array_pad( explode(':', base64_decode( substr( $_SERVER['HTTP_AUTHORIZATION'], 6) ) ), 2, NULL);
}


require_once( dirname( __FILE__ ) . DIRECTORY_SEPARATOR .'ClassMap.inc.php');
function TTAutoload( $name ) {
	global $config_vars, $global_class_map, $profiler; //$config_vars needs to be here, otherwise TTPDF can't access the cache_dir.

	if ( isset($profiler) ) {
		$profiler->startTimer( '__autoload' );
	}

	if ( isset($global_class_map[$name]) ) {
		$file_name = Environment::getBasePath() . DIRECTORY_SEPARATOR .'classes'. DIRECTORY_SEPARATOR .'modules'. DIRECTORY_SEPARATOR . $global_class_map[$name];
	} else {
		//If the class name contains "plugin", try to load classes directly from the plugins directory.
		if ( $name == 'PEAR' ) {
			return FALSE; //Skip trying to load PEAR class as it fails anyways.
		} elseif ( strpos( $name, 'Plugin') === FALSE ) {
			$file_name = $name .'.class.php';
		} else {
			$file_name = Environment::getBasePath() . DIRECTORY_SEPARATOR .'classes'. DIRECTORY_SEPARATOR .'modules'. DIRECTORY_SEPARATOR . 'plugins'  . DIRECTORY_SEPARATOR . str_replace('Plugin', '', $name ) .'.plugin.php';
		}
	}

	//Use include_once() instead of require_once so the installer doesn't Fatal Error without displaying anything.
	//include_once() is redundant in __autoload.
	//Debug::Text('Autoloading Class: '. $name .' File: '. $file_name, __FILE__, __LINE__, __METHOD__,10);
	//Debug::Arr(Debug::BackTrace(), 'Backtrace: ', __FILE__, __LINE__, __METHOD__,10);
	//Remove the following @ symbol to help in debugging parse errors.
	if ( file_exists( $file_name ) === TRUE ) {
		include( $file_name );
	} else {
		return FALSE; //File doesn't exist, could be external library or just incorrect name.
	}

	if ( isset($profiler) ) {
		$profiler->stopTimer( '__autoload' );
	}

	return TRUE;
}
spl_autoload_register('TTAutoload'); //Registers the autoloader mainly for use with PHPUnit

//The basis for the plugin system, instantiate all classes through this, allowing the class to be overloaded on the fly by a class in the plugin directory.
//ie: $uf = TTNew( 'UserFactory' ); OR $uf = TTNew( 'UserFactory', $arg1, $arg2, $arg3 );
function TTgetPluginClassName( $class_name ) {
	global $config_vars;

	//Check if the plugin system is enabled in the config.
	if ( isset($config_vars['other']['enable_plugins']) AND $config_vars['other']['enable_plugins'] == 1 ) {
		$plugin_class_name = $class_name.'Plugin';

		//This improves performance greatly for classes with no plugins.
		//But it may cause problems if the original class was somehow loaded before the plugin.
		//However the plugin wouldn't apply to it anyways in that case.
		//
		//Due to a bug that would cause the plugin to not be properly loaded if both the Factory and ListFactory were loaded in the same script
		//we need to always reload the plugin class if the current class relates to it.
		$is_class_exists = class_exists( $class_name, FALSE );
		if ( $is_class_exists == FALSE OR ( $is_class_exists == TRUE AND stripos( $plugin_class_name, $class_name ) !== FALSE ) ) {
			if ( class_exists( $plugin_class_name, FALSE ) == FALSE ) {
				//Class file needs to be loaded.
				$plugin_directory = Environment::getBasePath() .'classes'. DIRECTORY_SEPARATOR .'modules'. DIRECTORY_SEPARATOR .'plugins';
				$plugin_class_file_name = $plugin_directory . DIRECTORY_SEPARATOR . $class_name .'.plugin.php';
				//Debug::Text('Plugin System enabled! Looking for class: '. $class_name .' in file: '. $plugin_class_file_name, __FILE__, __LINE__, __METHOD__,10);
				if ( file_exists( $plugin_class_file_name ) ) {
					@include_once( $plugin_class_file_name );
					$class_name = $plugin_class_name;
					Debug::Text('Found Plugin: '. $plugin_class_file_name .' Class: '. $class_name, __FILE__, __LINE__, __METHOD__, 10);
				}
			} else {
				//Class file is already loaded.
				$class_name = $plugin_class_name;
			}
		}
		//else {
			//Debug::Text('Plugin not found...', __FILE__, __LINE__, __METHOD__, 10);
		//}
	}
	//else {
		//Debug::Text('Plugins disabled...', __FILE__, __LINE__, __METHOD__, 10);
	//}

	return $class_name;
}
function TTnew( $class_name ) { //Unlimited arguments are supported.
	$class_name = TTgetPluginClassName( $class_name );

	if ( func_num_args() > 1 ) {
		$params = func_get_args();
		array_shift( $params ); //Eliminate the class name argument.

		$reflection_class = new ReflectionClass($class_name);
		return $reflection_class->newInstanceArgs($params);
	} else {
		return new $class_name();
	}
}

//Force no caching of file.
function forceNoCacheHeaders() {

	//CSP headers break many things at this stage, unless "unsafe" is used for almost everything.
	//Header('Content-Security-Policy: default-src *; script-src \'self\' *.google.com');
	header('Content-Security-Policy: default-src * \'unsafe-inline\'; script-src \'unsafe-eval\' \'unsafe-inline\' \'self\' *.googleapis.com *.gstatic.com *.google.com; img-src \'self\' *.googleapis.com *.gstatic.com *.google.com google.com https://raw.githubusercontent.com/aydancoskun/FairnessTNA/ data: blob:');

// Allow all
//header('Content-Security-Policy: default-src *; style-src * \'unsafe-inline\'; script-src * \'unsafe-inline\' \'unsafe-eval\'; img-src * data: \'unsafe-inline\'; connect-src * \'unsafe-inline\'; frame-src *;');

	//Help prevent XSS or frame clickjacking.
	header('X-XSS-Protection: 1; mode=block');
	header('X-Frame-Options: SAMEORIGIN');

	//Reduce MIME-TYPE security risks.
	header('X-Content-Type-Options: nosniff');

	//Turn caching off.
	header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
	header('Last-Modified: ' . gmdate("D, d M Y H:i:s") . ' GMT');
	//Can Break IE with downloading PDFs over SSL.
	// IE gets: "file could not be written to cache"
	// It works on some IE installs though.
	header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0');
	if ( isset($_SERVER['HTTP_USER_AGENT']) AND stripos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== FALSE ) {
		header('Pragma: token'); //If set to no-cache it breaks IE downloading reports, with error that the site is not available.
		//20-Jan-16: Re-enable keepalive requests on IE to see if the issue persists after we have (mostly) fixed the duplicate request issue when users double-click icons.
		//if ( preg_match('/(?i)MSIE [5-9]/i', $_SERVER['HTTP_USER_AGENT'] ) ) {
		//	header('Connection: close'); //ie6-9 may send empty POST requests causing API errors due to poor keepalive handling, so force all connections to close instead.
		//}
	} else {
		header('Pragma: no-cache');
	}

	//Only when force_ssl is enabled and the user is using SSL, include the STS header.
	global $config_vars;
	if ( isset($config_vars['other']['force_ssl']) AND ( $config_vars['other']['force_ssl'] == TRUE ) AND Misc::isSSL(TRUE) == TRUE ) {
		header('Strict-Transport-Security: max-age=31536000; includeSubdomains');
	}
}

//Function to force browsers to cache certain files.
function forceCacheHeaders( $file_name = NULL, $mtime = NULL, $etag = NULL ) {
	if ( $file_name == '' ) {
		$file_name = $_SERVER['SCRIPT_FILENAME'];
	}

	if ( $mtime == '' ) {
		$file_modified_time = filemtime($file_name);
	} else {
		$file_modified_time = $mtime;
	}

	if ( $etag != '' ) {
		$etag = trim($etag);
	}

	//Help prevent XSS or frame clickjacking.
	header('X-XSS-Protection: 1; mode=block');
	header('X-Frame-Options: SAMEORIGIN');

	//For some reason even with must-revalidate the browsers won't check ETag every page load.
	//So some pages may get cached for an hour or two regardless of ETag changes.
	header('Cache-Control: must-revalidate, max-age=0');
	header('Cache-Control: private', FALSE);
	header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
	//Check eTag first, then last modified time.
	if ( ( isset($_SERVER['HTTP_IF_NONE_MATCH']) AND trim($_SERVER['HTTP_IF_NONE_MATCH']) == $etag )
			OR ( !isset($_SERVER['HTTP_IF_NONE_MATCH'])
					AND isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])
					AND strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == $file_modified_time ) ) {
		//Cached page, send 304 code and exit.
		header('HTTP/1.1 304 Not Modified');
		//Header('Connection: close'); //This closes keep-alive connections to close, shouldn't be needed and just slows things down.
		ob_clean();
		exit; //File is cached, don't continue.
	} else {
		//Not cached page, add headers to assist caching.
		if ( $etag != '' ) {
			header('ETag: '. $etag);
		}
		header('Last-Modified: '.gmdate('D, d M Y H:i:s', $file_modified_time).' GMT');
	}

	return TRUE;
}

define('TT_PRODUCT_COMMUNITY', 10 ); define('TT_PRODUCT_PROFESSIONAL', 15 ); define('TT_PRODUCT_CORPORATE', 20 ); define('TT_PRODUCT_ENTERPRISE', 25 );
function getTTProductEdition() { global $TT_PRODUCT_EDITION; if ( isset($TT_PRODUCT_EDITION) AND $TT_PRODUCT_EDITION > 0 ) { return $TT_PRODUCT_EDITION; } elseif ( file_exists( Environment::getBasePath() .'classes'. DIRECTORY_SEPARATOR .'modules'. DIRECTORY_SEPARATOR .'expense'. DIRECTORY_SEPARATOR .'UserExpenseFactory.class.php') ) { return TT_PRODUCT_ENTERPRISE; } elseif ( file_exists( Environment::getBasePath() .'classes'. DIRECTORY_SEPARATOR .'modules'. DIRECTORY_SEPARATOR .'job'. DIRECTORY_SEPARATOR .'JobFactory.class.php') ) { return TT_PRODUCT_CORPORATE; } elseif ( file_exists( Environment::getBasePath() .'classes'. DIRECTORY_SEPARATOR .'modules'. DIRECTORY_SEPARATOR .'time_clock'. DIRECTORY_SEPARATOR .'TimeClock.class.php') ) { return TT_PRODUCT_PROFESSIONAL; } return TT_PRODUCT_COMMUNITY; }
function getTTProductEditionName() { switch( getTTProductEdition() ) { case 15: $retval = 'Professional'; break; case 20: $retval = 'Corporate'; break; case 25: $retval = 'Enterprise'; break; default: $retval = 'Community'; break; } return $retval; }

function TTsaveRequestMetrics() {
	global $config_vars;
	if ( function_exists('memory_get_usage') ) {
		$memory_usage = memory_get_usage();
	} else {
		$memory_usage = 0;
	}
	file_put_contents( $config_vars['other']['request_metrics_log'], ((microtime( TRUE ) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000).' '. $memory_usage ."\n", FILE_APPEND ); //Write each response in MS to log for tracking performance
}

//This has to be first, always.
require_once( dirname( __FILE__ ) . DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'classes'. DIRECTORY_SEPARATOR .'modules'. DIRECTORY_SEPARATOR .'core'. DIRECTORY_SEPARATOR .'Environment.class.php');

require_once( dirname( __FILE__ ) . DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'classes'. DIRECTORY_SEPARATOR .'modules'. DIRECTORY_SEPARATOR .'core'. DIRECTORY_SEPARATOR .'Profiler.class.php');
$profiler = new Profiler( TRUE );

set_include_path('.' . PATH_SEPARATOR .
    Environment::getBasePath() .'classes'. DIRECTORY_SEPARATOR . 'modules'. DIRECTORY_SEPARATOR . 'core' .
    PATH_SEPARATOR . Environment::getBasePath() .'classes' .
    PATH_SEPARATOR . Environment::getBasePath() .'classes' . DIRECTORY_SEPARATOR .'plugins' .
    //PATH_SEPARATOR . get_include_path() . //Don't include system include path, as it can cause conflicts with other packages bundled with FairnessTNA. However the bundled PEAR.php must check for class_exists('PEAR') to prevent conflicts with PHPUnit.
    PATH_SEPARATOR . Environment::getBasePath() .'classes'. DIRECTORY_SEPARATOR . 'pear' ); //Put PEAR path at the end so system installed PEAR is used first, this prevents require_once() from including PEAR from two directories, which causes a fatal error.

require_once(Environment::getBasePath() .'classes'. DIRECTORY_SEPARATOR .'modules'. DIRECTORY_SEPARATOR .'core'. DIRECTORY_SEPARATOR .'Exception.class.php');
require_once(Environment::getBasePath() .'classes'. DIRECTORY_SEPARATOR .'modules'. DIRECTORY_SEPARATOR .'core'. DIRECTORY_SEPARATOR .'Debug.class.php');
define( 'COPYRIGHT_NOTICE', 'This Program is free software provided AS IS, without warranty. Licensed under <a href="http://www.fsf.org/licensing/licenses/agpl-3.0.html" class="footerLink" target="_blank">AGPLv3.</a> See about menu for full license.' );
Debug::setEnable( (bool)$config_vars['debug']['enable'] );
Debug::setEnableDisplay( (bool)$config_vars['debug']['enable_display'] );
Debug::setBufferOutput( (bool)$config_vars['debug']['buffer_output'] );
Debug::setEnableLog( (bool)$config_vars['debug']['enable_log'] );
Debug::setVerbosity( (int)$config_vars['debug']['verbosity'] );

if ( Debug::getEnable() == TRUE AND Debug::getEnableDisplay() == TRUE ) {
	ini_set( 'display_errors', 1 );
	ini_set( 'display_startup_errors', 1 );
} else {
	ini_set( 'display_errors', 0 );
	ini_set( 'display_startup_errors', 0 );
}

//Register PHP error handling functions as early as possible.
register_shutdown_function( array('Debug','Shutdown') );
if ( PHP_SAPI != 'cli' AND isset($config_vars['other']['request_metrics_log']) AND $config_vars['other']['request_metrics_log'] != '' ) {
	register_shutdown_function( 'TTsaveRequestMetrics' );
}
set_error_handler( array('Debug','ErrorHandler') );

Debug::Text('URI: '. ( isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'N/A' ) .' IP Address: '. Misc::getRemoteIPAddress(), __FILE__, __LINE__, __METHOD__, 10);
Debug::Text('USER-AGENT: '. ( isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'N/A' ), __FILE__, __LINE__, __METHOD__, 10);
Debug::Text('Version: '. APPLICATION_VERSION .' (PHP: v'. phpversion() .') Edition: '. getTTProductEdition() .' Production: '. (int)PRODUCTION .' Server: '. ( isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : 'N/A' ) .' OS: '. OPERATING_SYSTEM .' Database: Type: '. ( isset($config_vars['database']['type']) ? $config_vars['database']['type'] : 'N/A' ) .' Name: '. ( isset($config_vars['database']['database_name']) ? $config_vars['database']['database_name'] : 'N/A' ) .' Config: '. CONFIG_FILE .' Demo Mode: '. (int)DEMO_MODE, __FILE__, __LINE__, __METHOD__, 10);

if ( function_exists('bcscale') ) {
	bcscale(10);
}

//Make sure we are using SSL if required.
if ( ( isset($config_vars['other']['force_ssl']) AND $config_vars['other']['force_ssl'] == TRUE ) AND Misc::isSSL( TRUE ) == FALSE AND isset( $_SERVER['HTTP_HOST'] ) AND isset( $_SERVER['REQUEST_URI'] ) AND !isset( $disable_https ) AND php_sapi_name() != 'cli' ) {
	Redirect::Page( 'https://'. $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'] );
	exit;
}

if ( PRODUCTION == TRUE ) {
	$origin_url = ( Misc::isSSL( TRUE ) == TRUE ) ? 'https://'.Misc::getHostName( FALSE ) : 'http://'.Misc::getHostName( FALSE );
} else {
	$origin_url = '*';
}
header('Access-Control-Allow-Origin: '. $origin_url );
header('Access-Control-Allow-Headers: Content-Type, REQUEST_URI_FRAGMENT' );
unset($origin_url);

require_once('Database.inc.php');
require_once('Cache.inc.php'); //Put cache after Database so we can handle our own DB caching.
?>
