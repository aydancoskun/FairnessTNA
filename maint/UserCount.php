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
 * Counts the total active/inactive/deleted users for each company once a day.
 *
 */
require_once( dirname(__FILE__) . DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'includes'. DIRECTORY_SEPARATOR .'global.inc.php');
require_once( dirname(__FILE__) . DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'includes'. DIRECTORY_SEPARATOR .'CLI.inc.php');

$cuclf = new CompanyUserCountListFactory();
$cuclf->getActiveUsers();
if ( $cuclf->getRecordCount() > 0 ) {
	foreach( $cuclf as $cuc_obj ) {
		$user_counts[$cuc_obj->getColumn('company_id')]['active'] = $cuc_obj->getColumn('total');
	}
}

$cuclf->getInActiveUsers();
if ( $cuclf->getRecordCount() > 0 ) {
	foreach( $cuclf as $cuc_obj ) {
		$user_counts[$cuc_obj->getColumn('company_id')]['inactive'] = $cuc_obj->getColumn('total');
	}
}

$cuclf->getDeletedUsers();
if ( $cuclf->getRecordCount() > 0 ) {
	foreach( $cuclf as $cuc_obj ) {
		$user_counts[$cuc_obj->getColumn('company_id')]['deleted'] = $cuc_obj->getColumn('total');
	}
}

$cuclf->StartTransaction();
if ( isset($user_counts) AND count($user_counts) > 0 ) {
	foreach( $user_counts as $company_id => $user_count_arr) {

		$cucf = new CompanyUserCountFactory();
		$cucf->setCompany( $company_id );
		$cucf->setDateStamp( time() );
		if ( !isset($user_count_arr['active']) ) {
			$user_count_arr['active'] = 0;
		}
		$cucf->setActiveUsers( $user_count_arr['active'] );

		if ( !isset($user_count_arr['inactive']) ) {
			$user_count_arr['inactive'] = 0;
		}
		$cucf->setInActiveUsers( $user_count_arr['inactive'] );

		if ( !isset($user_count_arr['deleted']) ) {
			$user_count_arr['deleted'] = 0;
		}
		$cucf->setDeletedUsers( $user_count_arr['deleted']);

		Debug::text('Company ID: '. $company_id .' Active: '. $user_count_arr['active'] .' InActive: '. $user_count_arr['inactive'] .' Deleted: '. $user_count_arr['deleted'], __FILE__, __LINE__, __METHOD__, 10);

		if ( $cucf->isValid() ) {
			$cucf->Save();
		}
	}
}
$cuclf->CommitTransaction();

Debug::Display();
?>