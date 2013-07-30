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
 * $Id: About.php 9743 2013-05-02 21:22:23Z ipso $
 * $Date: 2013-05-02 14:22:23 -0700 (Thu, 02 May 2013) $
 */
require_once('../../includes/global.inc.php');
require_once(Environment::getBasePath() .'includes/Interface.inc.php');

//Debug::setVerbosity( 11 );

$smarty->assign('title', TTi18n::gettext($title = 'About')); // See index.php
BreadCrumb::setCrumb($title);

/*
 * Get FORM variables
 */
extract	(FormVariables::GetVariables(
										array	(
												'action',
												'ytd',
												'all_companies'
												) ) );

$data = $system_settings;

$action = Misc::findSubmitButton();
switch ($action) {
	case 'university':
		//Debug::setVerbosity( 11 );
		Debug::Text('Redirect to Online University!', __FILE__, __LINE__, __METHOD__,10);

		Redirect::Page( URLBuilder::getURL( array(), $config_vars['urls']['university']) );
		exit;

		break;
	case 'check_for_updates':
		Debug::Text('Check For update disabled', __FILE__, __LINE__, __METHOD__,10);
/*
		$ttsc = new TimeTrexSoapClient();

		//We must ensure that the data is up to date
		//Otherwise version check will fail.
		$ttsc->sendCompanyData( $current_company->getId(), TRUE );
		$ttsc->sendCompanyUserLocationData( $current_company->getId() );
		$ttsc->sendCompanyUserCountData( $current_company->getId() );
		$ttsc->sendCompanyVersionData( $current_company->getId() );

		$latest_version = $ttsc->isLatestVersion( $current_company->getId() );
		$latest_tax_engine_version = $ttsc->isLatestTaxEngineVersion( $current_company->getId() );
		$latest_tax_data_version = $ttsc->isLatestTaxDataVersion( $current_company->getId() );

		$sslf = TTnew( 'SystemSettingListFactory' );
		$sslf->getByName('new_version');
		if ( $sslf->getRecordCount() == 1 ) {
			$obj = $sslf->getCurrent();
		} else {
			$obj = TTnew( 'SystemSettingListFactory' );
		}
		$obj->setName( 'new_version' );

		if( $latest_version == FALSE
				OR $latest_tax_engine_version == FALSE
				OR $latest_tax_data_version == FALSE ) {
			$obj->setValue( 1 );
			$data['new_version'] = 1;
		} else {
			$obj->setValue( 0 );
			$data['new_version'] = 0;
		}

		if ( $obj->isValid() ) {
			$obj->Save();
		}
*/
	default:
		$data['product_edition'] = Option::getByKey( ( DEPLOYMENT_ON_DEMAND == TRUE ) ? $current_company->getProductEdition() : getTTProductEdition(), $current_company->getOptions('product_edition') );

		//Get Employee counts for this month, and last month
		$month_of_year_arr = TTDate::getMonthOfYearArray();

		//This month
		if ( isset($ytd) AND $ytd == 1 ) {
			$begin_month_epoch = strtotime( '-2 years' );
		} else {
			$begin_month_epoch = TTDate::getBeginMonthEpoch(TTDate::getBeginMonthEpoch(time())-86400);
		}

		$cuclf = TTnew( 'CompanyUserCountListFactory' );
		if ( isset($config_vars['other']['primary_company_id']) AND $current_company->getId() == $config_vars['other']['primary_company_id'] AND $all_companies == TRUE ) {
			$cuclf->getTotalMonthlyMinAvgMaxByCompanyStatusAndStartDateAndEndDate( 10, $begin_month_epoch, TTDate::getEndMonthEpoch( time() ), NULL, NULL, NULL, array('date_stamp' => 'desc') );
		} else {
			$cuclf->getMonthlyMinAvgMaxByCompanyIdAndStartDateAndEndDate( $current_company->getId(), $begin_month_epoch, TTDate::getEndMonthEpoch( time() ), NULL, NULL, NULL, array('date_stamp' => 'desc') );
		}
		Debug::Text('Company User Count Rows: '. $cuclf->getRecordCount(), __FILE__, __LINE__, __METHOD__,10);
		if ( $cuclf->getRecordCount() > 0 ) {
			foreach( $cuclf as $cuc_obj ) {
				$data['user_counts'][] = array(
																//'label' => $month_of_year_arr[TTDate::getMonth( $begin_month_epoch )] .' '. TTDate::getYear($begin_month_epoch),
																'label' => $month_of_year_arr[TTDate::getMonth( TTDate::strtotime( $cuc_obj->getColumn('date_stamp') ) )] .' '. TTDate::getYear( TTDate::strtotime( $cuc_obj->getColumn('date_stamp') ) ),
																'max_active_users' => $cuc_obj->getColumn('max_active_users'),
																'max_inactive_users' => $cuc_obj->getColumn('max_inactive_users'),
																'max_deleted_users' => $cuc_obj->getColumn('max_deleted_users'),
																);
			}
		}

		$cjlf = TTnew( 'CronJobListFactory' );
		$cjlf->getMostRecentlyRun();
		if ( $cjlf->getRecordCount() > 0 ) {
			$cj_obj = $cjlf->getCurrent();
			$data['cron'] = array(
								'last_run_date' => $cj_obj->getLastRunDate()
								);
		}
}

$data['license'] = str_replace("\n", "<br>", file_get_contents(Environment::getBasePath() ."LICENSE"));
$smarty->assign_by_ref('data', $data);

$smarty->display('help/About.tpl');
?>
