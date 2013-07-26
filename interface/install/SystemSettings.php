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
 * $Revision: 9521 $
 * $Id: SystemSettings.php 9521 2013-04-08 23:09:52Z ipso $
 * $Date: 2013-04-08 16:09:52 -0700 (Mon, 08 Apr 2013) $
 */
require_once('../../includes/global.inc.php');

$authenticate=FALSE;
require_once(Environment::getBasePath() .'includes/Interface.inc.php');

$smarty->assign('title', TTi18n::gettext($title = '4. System Settings')); // See index.php

/*
 * Get FORM variables
 */
extract	(FormVariables::GetVariables(
										array	(
												'action',
												'data',
												'external_installer',
												) ) );

$install_obj = new Install();
if ( $install_obj->isInstallMode() == FALSE ) {
	Redirect::Page( URLBuilder::getURL(NULL, 'install.php') );
}

$action = Misc::findSubmitButton();
switch ($action) {
	case 'back':
		Debug::Text('Back', __FILE__, __LINE__, __METHOD__,10);

		Redirect::Page( URLBuilder::getURL(NULL, 'DatabaseConfig.php') );
		break;

	case 'next':
		//Debug::setVerbosity(11);
		Debug::Text('Next', __FILE__, __LINE__, __METHOD__,10);

		//Set salt if it isn't already.
		$data['salt'] = md5( uniqid() );

		$install_obj->writeConfigFile( $data );

		//Write auto_update feature to system settings.
		$sslf = TTnew( 'SystemSettingListFactory' );
		$sslf->getByName('update_notify');
		if ( $sslf->getRecordCount() == 1 ) {
			$obj = $sslf->getCurrent();
		} else {
			$obj = TTnew( 'SystemSettingListFactory' );
		}

		$obj->setName( 'update_notify' );
		if ( ( isset($data['update_notify']) AND $data['update_notify'] == 1 )
				OR getTTProductEdition() > 10
				OR $external_installer == 1 ) {
			$obj->setValue( 1 );
		} else {
			$obj->setValue( 0 );
		}
		if ( $obj->isValid() ) {
			$obj->Save();
		}

		//Write anonymous_auto_update feature to system settings.
		$sslf = TTnew( 'SystemSettingListFactory' );
		$sslf->getByName('anonymous_update_notify');
		if ( $sslf->getRecordCount() == 1 ) {
			$obj = $sslf->getCurrent();
		} else {
			$obj = TTnew( 'SystemSettingListFactory' );
		}

		$obj->setName( 'anonymous_update_notify' );
		if ( getTTProductEdition() == PRODUCT_COMMUNITY_10 AND isset($data['anonymous_update_notify']) AND $data['anonymous_update_notify'] == 1 ) {
			$obj->setValue( 1 );
		} else {
			$obj->setValue( 0 );
		}
		if ( $obj->isValid() ) {
			$obj->Save();
		}

		$ttsc = new TimeTrexSoapClient();
		$ttsc->saveRegistrationKey();

		$handle = fopen('http://www.timetrex.com/'.URLBuilder::getURL( array('v' => $install_obj->getFullApplicationVersion(), 'page' => 'system_setting', 'update_notify' => (int)$data['update_notify'], 'anonymous_update_notify' => (int)$data['anonymous_update_notify']), 'pre_install.php'), "r");
		fclose($handle);

		Redirect::Page( URLBuilder::getURL( array('external_installer' => $external_installer), 'Company.php') );
		break;
	default:
		Debug::Text('Request URI: '. $_SERVER['REQUEST_URI'], __FILE__, __LINE__, __METHOD__,10);

		$data = array(
					'host_name' => $_SERVER['HTTP_HOST'],
					'base_url' => str_replace('/install', '', dirname( $_SERVER['REQUEST_URI'] ) ),
					'log_dir' => $config_vars['path']['log'],
					'storage_dir' => $config_vars['path']['storage'],
					'cache_dir' => $config_vars['cache']['dir'],
					);

		$smarty->assign_by_ref('data', $data);

		break;
}

$handle = @fopen('http://www.timetrex.com/'.URLBuilder::getURL( array('v' => $install_obj->getFullApplicationVersion(), 'page' => 'system_setting'), 'pre_install.php'), "r");
@fclose($handle);

$smarty->assign_by_ref('install_obj', $install_obj);
$smarty->assign_by_ref('external_installer', $external_installer);
$smarty->display('install/SystemSettings.tpl');
?>