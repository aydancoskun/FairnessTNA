<?php
/*********************************************************************************
 * This file is part of "Fairness", a Payroll and Time Management program.
 * Fairness is Copyright 2013 Aydan Coskun (aydan.ayfer.coskun@gmail.com)
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
 * $Id: AddMassSchedule.php 9993 2013-05-24 20:16:41Z ipso $
 * $Date: 2013-05-24 13:16:41 -0700 (Fri, 24 May 2013) $
 */
require_once('../../includes/global.inc.php');

//Debug::setVerbosity(11);

$skip_message_check = TRUE;
require_once(Environment::getBasePath() .'includes/Interface.inc.php');

if ( !$permission->Check('schedule','enabled')
		OR !( $permission->Check('schedule','edit')
				OR $permission->Check('schedule','edit_own')
				OR $permission->Check('schedule','edit_child')
				 ) ) {
	$permission->Redirect( FALSE ); //Redirect
}

$smarty->assign('title', TTi18n::gettext($title = 'Mass Schedule')); // See index.php
BreadCrumb::setCrumb($title);

/*
 * Get FORM variables
 */
extract	(FormVariables::GetVariables(
										array	(
												'action',
												'id',
												'data',
												'filter_user_id'
												) ) );

$data = Misc::preSetArrayValues( $data, array( 'start_date_stamp', 'end_date_stamp', 'start_time', 'end_time' ), NULL );

if ( isset($data) ) {
	if ( $data['start_date_stamp'] != ''
			AND !is_numeric($data['start_date_stamp'])
			AND $data['end_date_stamp'] != ''
			AND !is_numeric($data['end_date_stamp'])
			AND $data['start_time'] != ''
			AND !is_numeric($data['end_time'])
			AND $data['end_time'] != ''
			AND !is_numeric($data['end_time'])
			) {
		$data['start_full_time_stamp'] = TTDate::parseDateTime($data['start_date_stamp'].' '.$data['start_time']);
		$data['end_full_time_stamp'] = TTDate::parseDateTime($data['end_date_stamp'].' '.$data['end_time']);
	} else {
		$data['start_full_time_stamp'] = NULL;
		$data['end_full_time_stamp'] = NULL;
	}

	if ( $data['start_date_stamp'] != '') {
		$data['start_date_stamp'] = TTDate::parseDateTime($data['start_date_stamp']);
	}
	if ( $data['end_date_stamp'] != '') {
		$data['end_date_stamp'] = TTDate::parseDateTime($data['end_date_stamp']);
	}

	if ( $data['start_time'] != '') {
		$data['parsed_start_time'] = TTDate::strtotime( $data['start_time'], $data['start_date_stamp'] ) ;
	}
	if ( $data['end_time'] != '') {
		Debug::Text('End Time: '. $data['end_time'] .' Date Stamp: '. $data['start_date_stamp'] , __FILE__, __LINE__, __METHOD__,10);
		$data['parsed_end_time'] = strtotime( $data['end_time'], $data['start_date_stamp'] ) ;
		Debug::Text('bEnd Time: '. $data['end_time'] .' - '. TTDate::getDate('DATE+TIME',$data['end_time']) , __FILE__, __LINE__, __METHOD__,10);
	}
}

//Get Permission Hierarchy Children first, as this can be used for viewing, or editing.
$hlf = TTnew( 'HierarchyListFactory' );
$permission_children_ids = $hlf->getHierarchyChildrenByCompanyIdAndUserIdAndObjectTypeID( $current_company->getId(), $current_user->getId() );
//Debug::Arr($permission_children_ids,'Permission Children Ids:', __FILE__, __LINE__, __METHOD__,10);
$filter_data = array();
if ( $permission->Check('schedule','edit') == FALSE ) {
	if ( $permission->Check('schedule','edit_child') ) {
		$filter_data['permission_children_ids'] = $permission_children_ids;
	}
	if ( $permission->Check('schedule','edit_own') ) {
		$filter_data['permission_children_ids'][] = $current_user->getId();
	}
}

$sf = TTnew( 'ScheduleFactory' );
$ulf = TTnew( 'UserListFactory' );

$action = Misc::findSubmitButton();
$action = strtolower($action);
switch ($action) {
	case 'submit':
		//Debug::setVerbosity(11);
		Debug::Text('Submit!', __FILE__, __LINE__, __METHOD__,10);

		$fail_transaction = FALSE;

		if ( TTDate::getDayDifference( $data['start_date_stamp'], $data['end_date_stamp']) > 31 ) {
			Debug::Text('Date Range Exceeds 31 days, truncating', __FILE__, __LINE__, __METHOD__,10);
			$sf->Validator->isTrue('date_stamp', FALSE, TTi18n::getText('Date range exceeds the maximum of 31 days') );
		}

		if ( !( isset($filter_user_id) AND is_array($filter_user_id) AND count($filter_user_id) > 0 )  ) {
			$sf->Validator->isTrue('user_id', FALSE, TTi18n::getText('Please select at least one employee') );
		}

		if ( !( $data['start_full_time_stamp'] != '' AND $data['end_full_time_stamp'] != ''
				AND $data['start_full_time_stamp'] >= (time()-86400*365) AND $data['end_full_time_stamp'] <= (time()+86400*365) ) ) {
			$sf->Validator->isTrue('date_stamp', FALSE, TTi18n::getText('Start or End dates are invalid') );
		}

		if ( $sf->Validator->isValid() ) {
			Redirect::Page( URLBuilder::getURL( array('action' => 'add_mass_schedule', 'filter_user_id' => $filter_user_id, 'data' => $data ), '../progress_bar/ProgressBarControl.php') );
		}
	default:
		if ( $action != 'submit' AND !is_array($data) ) {
			Debug::Text(' ID was NOT passed: '. $id, __FILE__, __LINE__, __METHOD__,10);

			$user_id = NULL;
			$user_date_id = NULL;
			$user_full_name = NULL;
			$user_default_branch = NULL;
			$user_default_department = NULL;
			$pay_period_is_locked = FALSE;

			$time_stamp = $start_date_stamp = $end_date_stamp = TTDate::getBeginDayEpoch( TTDate::getTime() ) + (3600*12); //Noon

			$data = array(
								//'user_id' => $user_id,
								'start_date_stamp' => $start_date_stamp,
								'end_date_stamp' => $end_date_stamp,
								//'user_date_id' => $user_date_id,
								//'user_full_name' => $user_full_name,
								'start_time' => strtotime('08:00 AM'),
								'parsed_start_time' => strtotime('08:00 AM'),
								'end_time' => strtotime('05:00 PM'),
								'parsed_end_time' => strtotime('05:00 PM'),
								'total_time' => 3600*9,
								'branch_id' => $user_default_branch,
								'department_id' => $user_default_department,
								//'pay_period_is_locked' => $pay_period_is_locked
								'dow' => array(1 => TRUE, 2 => TRUE, 3 => TRUE, 4 => TRUE, 5 => TRUE)

							);
		}
		//var_dump($data);

		$ulf = TTnew( 'UserListFactory' );
		$ulf->getSearchByCompanyIdAndArrayCriteria( $current_company->getId(), $filter_data );
		$src_user_options = UserListFactory::getArrayByListFactory( $ulf, FALSE, FALSE );

		$user_options = Misc::arrayDiffByKey( (array)$filter_user_id, $src_user_options );
		$filter_user_options = Misc::arrayIntersectByKey( (array)$filter_user_id, $src_user_options );

		$prepend_array_option = array( 0 => '--', -1 => TTi18n::gettext('-- Default --') );

		$splf = TTnew( 'SchedulePolicyListFactory' );
		$schedule_policy_options = $splf->getByCompanyIdArray( $current_company->getId() );

		$aplf = TTnew( 'AbsencePolicyListFactory' );
		$absence_policy_options = $aplf->getByCompanyIdArray( $current_company->getId() );

		$blf = TTnew( 'BranchListFactory' );
		$blf->getByCompanyId( $current_company->getId() );
		$branch_options = Misc::prependArray( $prepend_array_option,  $blf->getArrayByListFactory( $blf, FALSE, TRUE ) );

		$dlf = TTnew( 'DepartmentListFactory' );
		$dlf->getByCompanyId( $current_company->getId() );
		$department_options = Misc::prependArray( $prepend_array_option,  $dlf->getArrayByListFactory( $dlf, FALSE, TRUE ) );

/* aydancoskun
		if ( $current_company->getProductEdition() >= 20 ) {
			$jlf = TTnew( 'JobListFactory' );
			$jlf->getByStatusIdAndCompanyId( array(10,20,30,40), $current_company->getId() );
			//$jlf->getByCompanyIdAndUserIdAndStatus( $current_company->getId(),  $data['user_id'], array(10,20,30,40) );
			$data['job_options'] = $jlf->getArrayByListFactory( $jlf, TRUE, TRUE );
			$data['job_manual_id_options'] = $jlf->getManualIDArrayByListFactory($jlf, TRUE);

			$jilf = TTnew( 'JobItemListFactory' );
			$jilf->getByCompanyId( $current_company->getId() );
			$data['job_item_options'] = $jilf->getArrayByListFactory( $jilf, TRUE );
			$data['job_item_manual_id_options'] = $jilf->getManualIdArrayByListFactory( $jilf, TRUE );
		}
*/

		//Select box options;
		$smarty->assign_by_ref('user_options', $user_options);
		$smarty->assign_by_ref('filter_user_options', $filter_user_options);

		$data['status_options'] = $sf->getOptions('status');
		$data['schedule_policy_options'] = $schedule_policy_options;
		$data['absence_policy_options'] = $absence_policy_options;

		//$data['type_options'] = $pf->getOptions('type');
		$data['branch_options'] = $branch_options;
		$data['department_options'] = $department_options;

		$smarty->assign_by_ref('data', $data);

		break;
}

$smarty->assign_by_ref('sf', $sf);

$smarty->display('schedule/AddMassSchedule.tpl');
?>
