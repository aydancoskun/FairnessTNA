<?php
define('UNIT_TEST_MODE', TRUE ); //Add a define so other functions know when we are running unit tests and can change their behavior to not exit/redirect etc...

require_once( dirname(__FILE__) . '/../includes/global.inc.php');
//PHPUnit 3.1.9 works with unit tests, but v3.6 fails on ADODB for some reason.
//Need to run phpunit like this: phpunit --bootstrap BootStrap.php --no-globals-backup DateTimeTest

/*
//Add the following to the setUp() function to display more info.
global $config_vars;
Debug::Text('Version: '. APPLICATION_VERSION .' Edition: '. getTTProductEdition() .' Production: '. (int)PRODUCTION .' DB Type: '. $config_vars['database']['type'] .' Database: '. $config_vars['database']['database_name'] .' Config: '. CONFIG_FILE .' Demo Mode: '. (int)DEMO_MODE, __FILE__, __LINE__, __METHOD__, 10);
*/

//Disable audit log to help speed up tests.
$config_vars['other']['disable_audit_log'] = TRUE;
$config_vars['other']['disable_audit_log_detail'] = TRUE;

Debug::setBufferOutput(FALSE);
Debug::setEnable(FALSE); //Set to TRUE to see debug output. Leave buffer output FALSE.
Debug::setEnableDisplay(TRUE);
Debug::setVerbosity(10);

define('ENABLE_SELENIUM_TESTS', FALSE );
define('ENABLE_SELENIUM_REMOTE_TESTS', FALSE );

//This prevent PHPUnit from creating a mock ADODB-lib class and causing a fatal error on redeclaration of its functions.
//See for a possible fix? http://sebastian-bergmann.de/archives/797-Global-Variables-and-PHPUnit.html#content
//Must use --no-globals-backup to get tests to run properly.
$ADODB_INCLUDED_LIB = TRUE;
require_once( Environment::getBasePath() .'classes/adodb/adodb.inc.php');
require_once( Environment::getBasePath() .'classes/adodb/adodb-exceptions.inc.php');
require_once( Environment::getBasePath() .'classes/adodb/adodb-lib.inc.php');

if ( PRODUCTION != FALSE ) {
	echo "DO NOT RUN ON A PRODUCTION SERVER<br>\n";
	exit;
}

//require_once( dirname(__FILE__) . '/vendor/autoload.php');
//set_include_path( get_include_path() . PATH_SEPARATOR . '/usr/share/php'  );

echo "Include Path: ". get_include_path() ."\n";

$profiler = new Profiler( TRUE );

TTi18n::setLocale(); //Initialize the locale, this prevents PHP warnings when using Translation2/HHVM.
?>
