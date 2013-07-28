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
 * $Revision: 9761 $
 * $Id: Done.php 9761 2013-05-03 23:06:47Z ipso $
 * $Date: 2013-05-03 16:06:47 -0700 (Fri, 03 May 2013) $
 */
require_once('../../includes/global.inc.php');

$authenticate=FALSE;
require_once(Environment::getBasePath() .'includes/Interface.inc.php');

$authentication->Logout(); //Logout during the install process.

//Debug::setVerbosity(11);

$smarty->assign('title', TTi18n::gettext($title = 'Done!')); // See index.php

/*
 * Get FORM variables
 */
extract	(FormVariables::GetVariables(
										array	(
												'action',
												'data',
												'upgrade',
												) ) );

$install_obj = new Install();
if ( $install_obj->isInstallMode() == FALSE ) {
	Redirect::Page( URLBuilder::getURL(NULL, 'install.php') );
	exit;
}

//Disable installer now that we're done.
$data['installer_enabled'] = 'FALSE';
$install_obj->writeConfigFile( $data );

//Reset new_version flag.
$sslf = TTnew( 'SystemSettingListFactory' );
$sslf->getByName('new_version');
if ( $sslf->getRecordCount() == 1 ) {
	$obj = $sslf->getCurrent();
} else {
	$obj = TTnew( 'SystemSettingListFactory' );
}
$obj->setName( 'new_version' );
$obj->setValue( 0 );
if ( $obj->isValid() ) {
	$obj->Save();
}

//Reset system requirement flag, as all requirements should have passed.
$sslf = new SystemSettingListFactory();
$sslf->getByName('valid_install_requirements');
if ( $sslf->getRecordCount() == 1 ) {
	$obj = $sslf->getCurrent();
} else {
	$obj = new SystemSettingListFactory();
}
$obj->setName( 'valid_install_requirements' );
$obj->setValue( 1 );
if ( $obj->isValid() ) {
	$obj->Save();
}

$action = Misc::findSubmitButton();
switch ($action) {
	case 'back':
		Debug::Text('Back', __FILE__, __LINE__, __METHOD__,10);

		Redirect::Page( URLBuilder::getURL(NULL, 'User.php') );
		break;

	case 'next':
		Debug::Text('Next', __FILE__, __LINE__, __METHOD__,10);

		Redirect::Page( URLBuilder::getURL(NULL, '../Login.php') );
		break;
	default:
		break;
}

$cache->clean(); //Clear all cache.


$smarty->assign_by_ref('upgrade', $upgrade);
$smarty->assign_by_ref('install_obj', $install_obj);
$smarty->display('install/Done.tpl');
?>
