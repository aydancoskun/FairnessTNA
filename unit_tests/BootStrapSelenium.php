<?php
define( 'UNIT_TEST_MODE', TRUE ); //Add a define so other functions know when we are running unit tests and can change their behavior to not exit/redirect etc...

//SELENIUM EXPECTS THE DATABASE TO BE LOADED TO 15-Feb-2018
//run this to refresh the db: php create_demo_data.php -f -s 2 -date 15-Feb-2018

require_once( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'global.inc.php' );

/*
//Add the following to the setUp() function to display more info.
global $config_vars;
Debug::Text('Version: '. APPLICATION_VERSION .' Edition: '. getTTProductEdition() .' Production: '. (int)PRODUCTION .' DB Type: '. $config_vars['database']['type'] .' Database: '. $config_vars['database']['database_name'] .' Config: '. CONFIG_FILE .' Demo Mode: '. (int)DEMO_MODE, __FILE__, __LINE__, __METHOD__, 10);
*/

//Disable audit log to help speed up tests.
$config_vars['other']['disable_audit_log'] = TRUE;
$config_vars['other']['disable_audit_log_detail'] = TRUE;

Debug::setBufferOutput( FALSE );
Debug::setEnable( TRUE ); //Set to TRUE to see debug output. Leave buffer output FALSE.
Debug::setEnableDisplay( TRUE );
Debug::setVerbosity( 10 );

// 
// We are developing FairnessTNA on mac so:
// Install selenium with 'brew install selenium-server'
// install the web driver with 'brew cask install chromedriver'
// start the selenium server with 'selenium-server'
// 
// below are the original instructions that did not work for me because it uses the geckodriver
// but in the config is chrome. Just switching it to firefox does not work on some tests
//
//Use this command to launch the Selenium server: java -Dwebdriver.gecko.driver=/opt/selenium-server/geckodriver -jar /opt/selenium-server/selenium-server-standalone-3.0.1.jar
//
// In GRID mode use:
// java -jar /opt/selenium-server/selenium-server-standalone-3.0.1.jar -role hub
// java -Dwebdriver.gecko.driver=/opt/selenium-server/geckodriver -jar /opt/selenium-server/selenium-server-standalone-3.0.1.jar -role node -browser browserName=firefox,version=49,platform=LINUX
//

// Command: SELENIUM_PORT=4444 ./run_selenium.sh --filter testUIScreenShot
// To diff between directories full of images: /etc/maint/unit_test_compare_screenshots.sh /var/www/UIScreenShotTest/11.2.1-072012-complete /var/www/UIScreenShotTest/mikeb-11.6.0-20190930-152928/
// To view/compare screenshots go to whatever you set in the config e.g.: /UIScreenShotTest/
define( 'ENABLE_SELENIUM_TESTS', TRUE );
define( 'ENABLE_SELENIUM_REMOTE_TESTS', TRUE );
$selenium_config = array(
		'host'            => 'localhost', //DEV1
		'port'			  => ( getenv('SELENIUM_PORT') != '' ? getenv('SELENIUM_PORT') : '4444' ), //Default: 4444
		'browser'         => 'chrome',
		'default_url'     => 'http://tc.test/interface/html5/',
		'default_timeout' => 30,
		'screenshot_path' => __DIR__.'/../interface/UIScreenShotTest',
);

//This prevent PHPUnit from creating a mock ADODB-lib class and causing a fatal error on redeclaration of its functions.
//See for a possible fix? http://sebastian-bergmann.de/archives/797-Global-Variables-and-PHPUnit.html#content
//Must use --no-globals-backup to get tests to run properly.
$ADODB_INCLUDED_LIB = TRUE;
require_once( Environment::getBasePath() . 'classes' . DIRECTORY_SEPARATOR . 'adodb' . DIRECTORY_SEPARATOR . 'adodb.inc.php' );
require_once( Environment::getBasePath() . 'classes' . DIRECTORY_SEPARATOR . 'adodb' . DIRECTORY_SEPARATOR . 'adodb-exceptions.inc.php' );
require_once( Environment::getBasePath() . 'classes' . DIRECTORY_SEPARATOR . 'adodb' . DIRECTORY_SEPARATOR . 'adodb-lib.inc.php' );

if ( PRODUCTION != FALSE ) {
	echo "DO NOT RUN ON A PRODUCTION SERVER<br>\n";
	exit;
}

require_once( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'vendor/' . DIRECTORY_SEPARATOR . 'autoload.php' ); //This must be uncommented for Selenium to work.
//set_include_path( get_include_path() . PATH_SEPARATOR . '/usr/local/bin/php'  );

echo "Include Path: " . get_include_path() . "\n";

$profiler = new Profiler( TRUE );

TTi18n::setLocale(); //Initialize the locale, this prevents PHP warnings when using Translation2/HHVM.
?>
