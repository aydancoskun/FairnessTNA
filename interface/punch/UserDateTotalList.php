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
 * $Revision: 9993 $
 * $Id: UserDateTotalList.php 9993 2013-05-24 20:16:41Z ipso $
 * $Date: 2013-05-24 13:16:41 -0700 (Fri, 24 May 2013) $
 */
require_once('../../includes/global.inc.php');
require_once(Environment::getBasePath() .'includes/Interface.inc.php');

//Debug::setVerbosity(11);

if ( !$permission->Check('punch','enabled')
		OR !( $permission->Check('punch','view') OR $permission->Check('punch','view_own') ) ) {

	$permission->Redirect( FALSE ); //Redirect

}

$smarty->assign('title', TTi18n::gettext($title = 'Hour List')); // See index.php
BreadCrumb::setCrumb($title);

/*
 * Get FORM variables
 */
extract	(FormVariables::GetVariables(
										array	(
												'action',
												'page',
												'sort_column',
												'sort_order',
												//'user_date_id',
												'filter_user_id',
												'filter_date',
												'filter_system_time',
												'prev_day',
												'next_day',
												'prev_week',
												'next_week',
												'ids',

												) ) );

if ( $filter_user_id != '' ) {
	$user_id = $filter_user_id;
} else {
	$user_id = $current_user->getId();
}

if ( $filter_date != '' ) {
	$filter_date = TTDate::getBeginDayEpoch( TTDate::parseDateTime( $filter_date ) );
}

if ( isset($prev_day) ) {
	$filter_date = TTDate::getBeginDayEpoch( $filter_date-(86400) );
} elseif ( isset($next_day) ) {
	$filter_date = TTDate::getBeginDayEpoch( $filter_date+(86400) );
}

if ( isset($prev_week) ) {
	$filter_date = TTDate::getBeginDayEpoch( $filter_date-(86400*7) );
} elseif ( isset($next_week) ) {
	$filter_date = TTDate::getBeginDayEpoch( $filter_date+(86400*7) );
}

//This must be below any filter_date modifications
URLBuilder::setURL($_SERVER['SCRIPT_NAME'],
											array(
													//'user_date_id' => $user_date_id,
													'filter_date' => $filter_date,
													'filter_user_id' => $filter_user_id,
													'filter_system_time' => $filter_system_time,
													'sort_column' => $sort_column,
													'sort_order' => $sort_order,
													'page' => $page
												) );

$sort_array = NULL;
if ( $sort_column != '' ) {
	$sort_array = array($sort_column => $sort_order);
}

Debug::Arr($ids,'Selected Objects', __FILE__, __LINE__, __METHOD__,10);

switch ($action) {
	case 'add':

		//Redirect::Page( URLBuilder::getURL(array('user_id' => $user_id), 'EditUserWage.php', FALSE) );

		break;
	case 'delete':
	case 'undelete':
		if ( strtolower($action) == 'delete' ) {
			$delete = TRUE;
		} else {
			$delete = FALSE;
		}

		$udtlf = TTnew( 'UserDateTotalListFactory' );
		if ( is_array($ids) ) {
			$id_count = count($ids)-1;

			$i=0;
			foreach ($ids as $tmp_id) {
				$udtlf->getById($tmp_id);
				foreach ($udtlf as $udt_obj) {
					$udt_obj->setDeleted($delete);

					if ( $id_count == $i ) {
						$udt_obj->setEnableTimeSheetVerificationCheck(TRUE); //Unverify timesheet if its already verified.
						$udt_obj->setEnableCalcSystemTotalTime( TRUE );
						$udt_obj->setEnableCalcWeeklySystemTotalTime( TRUE );
						$udt_obj->setEnableCalcException( TRUE );
					}

					$udt_obj->Save();
				}
				$i++;
			}
		}

		Redirect::Page( URLBuilder::getURL(array('user_id' => $user_id, 'filter_date' => $filter_date), 'UserDateTotalList.php') );
		break;
	default:
		if ( ( !isset($user_date_id) OR (isset($user_date_id) AND $user_date_id == '') ) AND $user_id != '' AND $filter_date != '' ) {
			Debug::Text('User Date ID not passed, inserting one.', __FILE__, __LINE__, __METHOD__,10);
			$user_date_id = UserDateFactory::findOrInsertUserDate($user_id, $filter_date);
		}

		if ( $user_date_id != '' ) {
			$udtlf = TTnew( 'UserDateTotalListFactory' );
			$udtlf->getByUserDateIDAndStatusAndType( $user_date_id, array(10,20,30), array(10,20,30,40,100), $current_user_prefs->getItemsPerPage(), $page, NULL, $sort_array);

			$pager = new Pager($udtlf);

			$blf = TTnew( 'BranchListFactory' );
			$branch_options = $blf->getByCompanyIdArray( $current_company->getId() );

			$dlf = TTnew( 'DepartmentListFactory' );
			$department_options = $dlf->getByCompanyIdArray( $current_company->getId() );

			//Absence policies
			$otplf = TTnew( 'AbsencePolicyListFactory' );
			$absence_policy_options = $otplf->getByCompanyIDArray( $current_company->getId(), TRUE );

			//Overtime policies
			$otplf = TTnew( 'OverTimePolicyListFactory' );
			$over_time_policy_options = $otplf->getByCompanyIDArray( $current_company->getId(), TRUE );

			//Premium policies
			$pplf = TTnew( 'PremiumPolicyListFactory' );
			$premium_policy_options = $pplf->getByCompanyIDArray( $current_company->getId(), TRUE );

			$job_options = array();
			$job_item_options = array();
			/* Aydan

			if ( $current_company->getProductEdition() >= 20 ) {
				$jlf = TTnew( 'JobListFactory' );
				$job_options = $jlf->getByCompanyIdArray( $current_company->getId(), FALSE );

				$jilf = TTnew( 'JobItemListFactory' );
				$job_item_options = $jilf->getByCompanyIdArray( $current_company->getId(), TRUE );
			}
*/
			$day_total_time = array(
								'total_time' => 0,
								'worked_time' => 0,
								'difference' => 0
									);
			foreach ($udtlf as $udt_obj) {
				if ( $udt_obj->getStatus() == 20 ) {
					$day_total_time['worked_time'] += $udt_obj->getTotalTime();
				} elseif ( $udt_obj->getStatus() == 10 AND  $udt_obj->getType() == 10) {
					$day_total_time['total_time'] += $udt_obj->getTotalTime();
				}

				if ( $filter_system_time != 1 AND $udt_obj->getStatus() == 10 ) {
					continue;
				}

				if ( $udt_obj->getJob() != FALSE ) {
					$job = $job_options[$udt_obj->getJob()];
				} else {
					$job = 'No Job';
				}

				if ( $udt_obj->getJobItem() != FALSE ) {
					$job_item = $job_item_options[$udt_obj->getJobItem()];
				} else {
					$job_item = TTi18n::gettext('No Task');
				}

				$rows[] = array(
									'id' => $udt_obj->getId(),
									'status_id' => $udt_obj->getStatus(),
									'status' => Option::getByKey($udt_obj->getStatus(), $udt_obj->getOptions('status') ),
									'type_id' => $udt_obj->getType(),
									'type' => Option::getByKey($udt_obj->getType(), $udt_obj->getOptions('type') ),
									'branch_id' => $udt_obj->getBranch(),
									'branch' => $branch_options[$udt_obj->getBranch()],
									'department_id' => $udt_obj->getDepartment(),
									'department' => $department_options[$udt_obj->getDepartment()],

									'job_id' => $udt_obj->getJob(),
									'job' => $job,
									'job_item_id' => $udt_obj->getJobItem(),
									'job_item' => $job_item,
									'quantity' => (int)$udt_obj->getQuantity(),
									'bad_quantity' => (int)$udt_obj->getBadQuantity(),

									'absence_policy_id' => $udt_obj->getAbsencePolicyID(),
									'absence_policy' => $absence_policy_options[$udt_obj->getAbsencePolicyID()],
									'over_time_policy_id' => $udt_obj->getOverTimePolicyID(),
									'over_time_policy' => $over_time_policy_options[$udt_obj->getOverTimePolicyID()],
									'premium_policy_id' => $udt_obj->getPremiumPolicyID(),
									'premium_policy' => $premium_policy_options[$udt_obj->getPremiumPolicyID()],
									'total_time' => $udt_obj->getTotalTime(),
									'override' => $udt_obj->getOverride(),
									'deleted' => $udt_obj->getDeleted()
								);
			}
			$day_total_time['difference'] = $day_total_time['worked_time'] - $day_total_time['total_time'];

			//var_dump($day_total_time);

			$user_options = UserListFactory::getByCompanyIdArray( $current_company->getId(), FALSE );
			$smarty->assign_by_ref('user_options', $user_options);

			$smarty->assign_by_ref('rows', $rows);
			$smarty->assign_by_ref('day_total_time', $day_total_time);
			$smarty->assign_by_ref('user_date_id', $user_date_id );
			$smarty->assign_by_ref('filter_user_id', $user_id );
			$smarty->assign_by_ref('filter_date', $filter_date );
			$smarty->assign_by_ref('filter_system_time', $filter_system_time );

			$smarty->assign_by_ref('sort_column', $sort_column );
			$smarty->assign_by_ref('sort_order', $sort_order );

			$smarty->assign_by_ref('paging_data', $pager->getPageVariables() );

		}

		break;
}
$smarty->display('punch/UserDateTotalList.tpl');
?>
