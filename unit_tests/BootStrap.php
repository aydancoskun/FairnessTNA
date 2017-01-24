<?php
define('UNIT_TEST_MODE', true); //Add a define so other functions know when we are running unit tests and can change their behavior to not exit/redirect etc...

require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'global.inc.php');
//PHPUnit 3.1.9 works with unit tests, but v3.6 fails on ADODB for some reason.
//Need to run phpunit like this: phpunit --bootstrap BootStrap.php --no-globals-backup DateTimeTest

/*
//Add the following to the setUp() function to display more info.
global $config_vars;
Debug::Text('Version: '. APPLICATION_VERSION .' Production: '. (int)PRODUCTION .' DB Type: '. $config_vars['database']['type'] .' Database: '. $config_vars['database']['database_name'] .' Config: '. CONFIG_FILE, __FILE__, __LINE__, __METHOD__, 10);
*/

//Disable audit log to help speed up tests.
$config_vars['other']['disable_audit_log'] = true;
$config_vars['other']['disable_audit_log_detail'] = true;

Debug::setBufferOutput(false);
Debug::setEnable(false); //Set to TRUE to see debug output. Leave buffer output FALSE.
Debug::setEnableDisplay(true);
Debug::setVerbosity(10);

//Use this command to launch the Selenium server: java -Dwebdriver.gecko.driver=/opt/selenium-server/geckodriver -jar /opt/selenium-server/selenium-server-standalone-3.0.1.jar
//
// In GRID mode use:
// java -jar /opt/selenium-server/selenium-server-standalone-3.0.1.jar -role hub
// java -Dwebdriver.gecko.driver=/opt/selenium-server/geckodriver -jar /opt/selenium-server/selenium-server-standalone-3.0.1.jar -role node -browser browserName=firefox,version=49,platform=LINUX
//

// Also make sure config.xml has the Selenium directory uncommented.
//   and below DEFINES are set to TRUE.
define('ENABLE_SELENIUM_TESTS', false);
define('ENABLE_SELENIUM_REMOTE_TESTS', false);
$selenium_config = array(
    'host' => '10.7.5.31',
    //'host' => '10.7.5.9',
    'browser' => 'chrome',
    'default_url' => 'https://raw.githubusercontent.com/aydancoskun/fairness/master/interface/html5/',
    'default_timeout' => 30,
);

//This prevent PHPUnit from creating a mock ADODB-lib class and causing a fatal error on redeclaration of its functions.
//See for a possible fix? http://sebastian-bergmann.de/archives/797-Global-Variables-and-PHPUnit.html#content
//Must use --no-globals-backup to get tests to run properly.
$ADODB_INCLUDED_LIB = true;
require_once(Environment::getBasePath() . 'classes' . DIRECTORY_SEPARATOR . 'adodb' . DIRECTORY_SEPARATOR . 'adodb.inc.php');
require_once(Environment::getBasePath() . 'classes' . DIRECTORY_SEPARATOR . 'adodb' . DIRECTORY_SEPARATOR . 'adodb-exceptions.inc.php');
require_once(Environment::getBasePath() . 'classes' . DIRECTORY_SEPARATOR . 'adodb' . DIRECTORY_SEPARATOR . 'adodb-lib.inc.php');

if (PRODUCTION != false) {
    echo "DO NOT RUN ON A PRODUCTION SERVER<br>\n";
    exit;
}

require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'vendor/' . DIRECTORY_SEPARATOR . 'autoload.php');
//set_include_path( get_include_path() . PATH_SEPARATOR . '/usr/share/php'  );

echo "Include Path: " . get_include_path() . "\n";

$profiler = new Profiler(true);

TTi18n::setLocale(); //Initialize the locale, this prevents PHP warnings when using Translation2/HHVM.;
