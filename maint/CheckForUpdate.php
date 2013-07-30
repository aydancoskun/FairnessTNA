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
 * $Revision: 9743 $
 * $Id: CheckForUpdate.php 9743 2013-05-02 21:22:23Z ipso $
 * $Date: 2013-05-02 14:22:23 -0700 (Thu, 02 May 2013) $
 */
/*
 * Checks for any version updates...
 *
 */
require_once( dirname(__FILE__) . DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'includes'. DIRECTORY_SEPARATOR .'global.inc.php');
require_once( dirname(__FILE__) . DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'includes'. DIRECTORY_SEPARATOR .'CLI.inc.php');

Debug::Text('Auto Update Notifications permanently disabled in file /main/CheckForUpdate.php', __FILE__, __LINE__, __METHOD__,10);
/*
$ttsc = new TimeTrexSoapClient();
if ( $ttsc->isUpdateNotifyEnabled() == TRUE ) {
	sleep( rand(0,60) ); //Further randomize when calls are made.
	$clf = new CompanyListFactory();
	$clf->getAll();
	if ( $clf->getRecordCount() > 0 ) {
		$i=0;
		foreach ( $clf as $c_obj ) {
			if ( $ttsc->getLocalRegistrationKey() == FALSE
					OR $ttsc->getLocalRegistrationKey() == '' ) {
				$ttsc->saveRegistrationKey();
			}

			//We must ensure that the data is up to date
			//Otherwise version check will fail.
			$ttsc->sendCompanyData( $c_obj->getId() );
			$ttsc->sendCompanyUserLocationData( $c_obj->getId() );
			$ttsc->sendCompanyUserCountData( $c_obj->getId() );
			$ttsc->sendCompanyVersionData( $c_obj->getId() );

			//Check for new license once it starts expiring.
			//Help -> About, checking for new versions also gets the updated license file.
//			if ( $c_obj->getID() == $config_vars['other']['primary_company_id'] AND getTTProductEdition() > PRODUCT_COMMUNITY_10 ) {
			if ( $c_obj->getID() == $config_vars['other']['primary_company_id'] ) {
				if ( !isset($system_settings['license']) ) {
					$system_settings['license'] = NULL;
				}
			}

			//Only need to call this on the last company
			if ( $i == $clf->getRecordCount()-1 ) {
				$latest_version = $ttsc->isLatestVersion( $c_obj->getId() );
				$latest_tax_engine_version = $ttsc->isLatestTaxEngineVersion( $c_obj->getId() );
				$latest_tax_data_version = $ttsc->isLatestTaxDataVersion( $c_obj->getId() );

				$sslf = new SystemSettingListFactory();
				$sslf->getByName('new_version');
				if ( $sslf->getRecordCount() == 1 ) {
					$obj = $sslf->getCurrent();
				} else {
					$obj = new SystemSettingListFactory();
				}
				$obj->setName( 'new_version' );

				if( $latest_version == FALSE
						OR $latest_tax_engine_version == FALSE
						OR $latest_tax_data_version == FALSE ) {
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
	Debug::Text('Auto Update Notifications are disabled!', __FILE__, __LINE__, __METHOD__,10);
}
*/
Debug::writeToLog();
Debug::Display();
