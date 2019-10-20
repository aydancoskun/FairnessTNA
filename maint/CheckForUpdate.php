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

/*
 * Checks for any version updates...
 *
 */
require_once( dirname(__FILE__) . DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'includes'. DIRECTORY_SEPARATOR .'global.inc.php');
require_once( dirname(__FILE__) . DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'includes'. DIRECTORY_SEPARATOR .'CLI.inc.php');

if ( Misc::isUpdateNotifyEnabled() == TRUE ) {
	sleep( rand(0, 60) ); //Further randomize when calls are made.
	$clf = new CompanyListFactory();
	$clf->getAll();
	if ( $clf->getRecordCount() > 0 ) {
		$i = 0;
		foreach ( $clf as $c_obj ) {

			if ( $i == 0 AND getTTProductEdition() >= TT_PRODUCT_PROFESSIONAL ) {
				if ( !isset($system_settings['license']) ) {
					$system_settings['license'] = NULL;
				}
			}

			//Only need to call this on the last company
			if ( $i == ( $clf->getRecordCount() - 1 ) ) {
				$latest_version = Misc::isLatestVersion( $c_obj->getId() );

				$sslf = new SystemSettingListFactory();
				$sslf->getByName('new_version');
				if ( $sslf->getRecordCount() == 1 ) {
					$obj = $sslf->getCurrent();
				} else {
					$obj = new SystemSettingListFactory();
				}
				$obj->setName( 'new_version' );

				if( $latest_version == FALSE ) {
					$obj->setValue( 1 );
				} else {
					$obj->setValue( 0 );
				}

				if ( $obj->isValid() ) {
					$obj->Save();
				}
			}

			$i++;
		}
	}
} else {
	Debug::Text('Auto Update Notifications are disabled!', __FILE__, __LINE__, __METHOD__, 10);
}
Debug::writeToLog();
Debug::Display();
?>