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

//CSP headers break many things at this stage, unless "unsafe" is used for almost everything.
//Header('Content-Security-Policy: default-src *; script-src \'self\' *.google.com');
header('Content-Security-Policy: default-src * \'unsafe-inline\'; script-src \'unsafe-eval\' \'unsafe-inline\' \'self\' *.google.com; img-src \'self\' *.google.com http://google.com/ data: \'unsafe-inline\'');

//header('Content-Security-Policy: default-src *; style-src * 'unsafe-inline'; script-src * 'unsafe-inline' 'unsafe-eval'; img-src * data: 'unsafe-inline'; connect-src * 'unsafe-inline'; frame-src *;');

//Help prevent XSS or frame clickjacking.
Header('X-XSS-Protection: 1; mode=block');
Header('X-Frame-Options: SAMEORIGIN');

//Reduce MIME-TYPE security risks.
header('X-Content-Type-Options: nosniff');

if ( isset($config_vars['other']['force_ssl']) AND ( $config_vars['other']['force_ssl'] == TRUE ) AND Misc::isSSL(TRUE) == TRUE ) {
	header('Strict-Transport-Security: max-age=31536000; includeSubdomains');
}

if ( !isset($disable_cache_control) ) {
	//Turn caching off.
	header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
	header('Last-Modified: ' . gmdate("D, d M Y H:i:s") . ' GMT');
	//Can Break IE with downloading PDFs over SSL.
	// IE gets: "file could not be written to cache"
	// It works on some IE installs though.
	// Comment out No-Cache and Pragma: No-Cache to fix issue.
	header('Cache-Control: no-cache'); //Adding FALSE here breaks IE.
	header('Cache-Control: post-check=0,pre-check=0');
	header('Cache-Control: max-age=0');
	header('Pragma: public');
}

//Do not overwrite a previously sent content-type header, this breaks WAP.
header('Content-Type: text/html; charset=UTF-8');

//Skip this step if disable_database_connection is enabled or the user is going through the installer still
$clf = new CompanyListFactory();
if ( ( !isset($disable_database_connection) OR ( isset($disable_database_connection) AND $disable_database_connection != TRUE ) )
		AND ( !isset($config_vars['other']['installer_enabled']) OR ( isset($config_vars['other']['installer_enabled']) AND $config_vars['other']['installer_enabled'] != TRUE ) )) {
	//Get all system settings, so they can be used even if the user isn't logged in, such as the login page.
	try {
		$sslf = new SystemSettingListFactory();
		$system_settings = $sslf->getAllArray();
		unset($sslf);

		//Get primary company data needs to be used when user isn't logged in as well.
		$clf->getByID( PRIMARY_COMPANY_ID );
		if ( $clf->getRecordCount() == 1 ) {
			$primary_company = $clf->getCurrent();
		}
	} catch (Exception $e) {
		//Database not initialized, or some error, redirect to Install page.
		throw new DBError($e, 'DBInitialize');
	}
}

$permission = new Permission();

$authentication = new Authentication();
if ( isset($authenticate) AND $authenticate === FALSE ) {
	Debug::text('Bypassing Authentication', __FILE__, __LINE__, __METHOD__, 10);
	TTi18n::chooseBestLocale();
} else {
	if ( isset($config_vars['other']['web_session_timeout']) AND $config_vars['other']['web_session_timeout'] != '' ) {
		$authentication->setIdle( (int)$config_vars['other']['web_session_timeout'] );
	}

	if ( $authentication->Check() === TRUE ) {
		$profiler->startTimer( 'Interface.inc - Post-Authentication' );

		/*
		 * Get default interface data here. Things like User info, Company info etc...
		 */

		$current_user = $authentication->getObject();
		Debug::text('User Authenticated: '. $current_user->getUserName() .' Created Date: '. $authentication->getCreatedDate(), __FILE__, __LINE__, __METHOD__, 10);

		if ( isset($primary_company) AND PRIMARY_COMPANY_ID == $current_user->getCompany() ) {
			$current_company = $primary_company;
		} else {
			$current_company = $clf->getByID( $current_user->getCompany() )->getCurrent();
		}

		$db_time_zone_error = FALSE;
		$current_user_prefs = $current_user->getUserPreferenceObject();

		//If user doesnt have any preferences set, we need to bootstrap the preference object.
		if ( $current_user_prefs->getUser() == '' ) {
			$current_user_prefs->setUser( $current_user->getId() );
		}

		if ( $current_user_prefs->setDateTimePreferences() == FALSE ) {
			//Setting timezone failed, alert user to this fact.
			$db_time_zone_error = TRUE;
		}

		/*
		 *	Check locale cookie, if it varies from UserPreference Language,
		 *	change user preferences to match. This could cause some unexpected behavior
		 *  as the change is happening behind the scenes, but if we don't change
		 *  the user prefs then they could login for weeks/months as a different
		 *  language from their preferences, therefore making the user preference
		 *  setting almost useless. Causing issues when printing pay stubs and in each
		 *  users language.
		 */
		Debug::text('Locale Cookie: '. TTi18n::getLocaleCookie(), __FILE__, __LINE__, __METHOD__, 10);
		if ( $current_user_prefs->isNew() == FALSE AND TTi18n::getLocaleCookie() != '' AND $current_user_prefs->getLanguage() !== TTi18n::getLanguageFromLocale( TTi18n::getLocaleCookie() ) ) {
			Debug::text('Changing User Preference Language to match cookie...', __FILE__, __LINE__, __METHOD__, 10);
			$current_user_prefs->setLanguage( TTi18n::getLanguageFromLocale( TTi18n::getLocaleCookie() ) );
			if ( $current_user_prefs->isValid() ) {
				$current_user_prefs->Save(FALSE);
			}
		} else {
			Debug::text('User Preference Language matches cookie!', __FILE__, __LINE__, __METHOD__, 10);
		}
		if ( isset($_GET['language']) AND $_GET['language'] != '' ) {
			TTi18n::setLocale( $_GET['language'] ); //Sets master locale
		} else {
			TTi18n::setLanguage( $current_user_prefs->getLanguage() );
			TTi18n::setCountry( $current_user->getCountry() );
			TTi18n::setLocale(); //Sets master locale
		}

//		//Handle station functionality
//		if ( isset( $_COOKIE['StationID'] ) ) {
//			Debug::text('Station ID Cookie found! '. $_COOKIE['StationID'], __FILE__, __LINE__, __METHOD__, 10);
//
//			$slf = new StationListFactory();
//			$slf->getByStationIdandCompanyId( $_COOKIE['StationID'], $current_company->getId() );
//			$current_station = $slf->getCurrent();
//			unset($slf);
//			if ( $current_station->isNew() ) {
//				Debug::text('Station ID is NOT IN DB!! '. $_COOKIE['StationID'], __FILE__, __LINE__, __METHOD__, 10);
//			}
//		} else {
//			Debug::text('No Station cookie defined... User ID: '. $current_user->getId(), __FILE__, __LINE__, __METHOD__, 10);
//			$current_station = NULL; //No station cookie defined, make sure we at least initialize the variable.
//		}
		//Debug::Arr($current_station, 'Current Station Object: ', __FILE__, __LINE__, __METHOD__, 10);
		//Debug::text('Current Company: '. $current_company->getName(), __FILE__, __LINE__, __METHOD__, 10);

		$profiler->stopTimer( 'Interface.inc - Post-Authentication' );
	} else {
		Debug::text('User NOT Authenticated!', __FILE__, __LINE__, __METHOD__, 10);
		Redirect::Page( URLBuilder::getURL(NULL, Environment::GetBaseURL().'html5/') );
		//exit;
	}
}
unset($clf);

$profiler->startTimer( 'Main' );
?>