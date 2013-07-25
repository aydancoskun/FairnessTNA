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
 * $Revision: 8678 $
 * $Id: Company.php 8678 2012-12-21 22:44:01Z ipso $
 * $Date: 2012-12-21 14:44:01 -0800 (Fri, 21 Dec 2012) $
 */
require_once('../../includes/global.inc.php');

//Debug::setVerbosity( 11 );

$authenticate=FALSE;
//Disable database connection for Interface so we don't attempt to get company information before its created causing the cache file to be created with no records.
$disable_database_connection=TRUE;
require_once(Environment::getBasePath() .'includes/Interface.inc.php');

$smarty->assign('title', TTi18n::gettext($title = '5. Company Information')); // See index.php

/*
 * Get FORM variables
 */
extract	(FormVariables::GetVariables(
										array	(
												'action',
												'id',
												'company_data',
												'external_installer',
												) ) );

$install_obj = new Install();
if ( $install_obj->isInstallMode() == FALSE ) {
	Redirect::Page( URLBuilder::getURL(NULL, 'install.php') );
}

$cf = TTnew( 'CompanyFactory' );

$action = Misc::findSubmitButton();
switch ($action) {
	case 'back':
		Debug::Text('Back', __FILE__, __LINE__, __METHOD__,10);

		Redirect::Page( URLBuilder::getURL(NULL, 'SystemSettings.php') );
		break;

	case 'next':
		Debug::Text('Submit!', __FILE__, __LINE__, __METHOD__,10);


		//$cf->setParent($company_data['parent']);
		$cf->setStatus( 10 );
		$cf->setProductEdition( (int)getTTProductEdition() );
		$cf->setName($company_data['name'], TRUE); //Force change.
		$cf->setShortName($company_data['short_name']);
		$cf->setIndustry($company_data['industry_id']);
		$cf->setAddress1($company_data['address1']);
		$cf->setAddress2($company_data['address2']);
		$cf->setCity($company_data['city']);
		$cf->setCountry($company_data['country']);
		$cf->setProvince($company_data['province']);
		$cf->setPostalCode($company_data['postal_code']);
		$cf->setWorkPhone($company_data['work_phone']);

		$cf->setEnableAddCurrency( TRUE );
		$cf->setEnableAddPermissionGroupPreset( TRUE );
		$cf->setEnableAddUserDefaultPreset( TRUE );
		$cf->setEnableAddStation( TRUE );
		$cf->setEnableAddPayStubEntryAccountPreset( TRUE );
		$cf->setEnableAddCompanyDeductionPreset( TRUE );
		$cf->setEnableAddRecurringHolidayPreset( TRUE );

		if ( $cf->isValid() ) {
			$company_id = $cf->Save();

			$install_obj->writeConfigFile( array('primary_company_id' => $company_id ) );

			Redirect::Page( URLBuilder::getURL( array('company_id' => $company_id, 'external_installer' => $external_installer), 'User.php') );

			break;
		}
	default:
		//Select box options;
		$company_data['status_options'] = $cf->getOptions('status');
		$company_data['country_options'] = $cf->getOptions('country');
		$company_data['industry_options'] = $cf->getOptions('industry');

		if (!isset($id) AND isset($company_data['id']) ) {
			$id = $company_data['id'];
		}
		$company_data['user_list_options'] = UserListFactory::getByCompanyIdArray($id);

		$smarty->assign_by_ref('company_data', $company_data);

		break;
}

$handle = @fopen('http://www.timetrex.com/'.URLBuilder::getURL( array('v' => $install_obj->getFullApplicationVersion(), 'page' => 'company'), 'pre_install.php'), "r");
@fclose($handle);

$smarty->assign_by_ref('cf', $cf);
$smarty->assign_by_ref('external_installer', $external_installer);

$smarty->display('install/Company.tpl');
?>