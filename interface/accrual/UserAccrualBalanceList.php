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
 * $Revision: 4104 $
 * $Id: UserAccrualBalanceList.php 4104 2011-01-04 19:04:05Z ipso $
 * $Date: 2011-01-04 11:04:05 -0800 (Tue, 04 Jan 2011) $
 */
require_once('../../includes/global.inc.php');
require_once(Environment::getBasePath() .'includes/Interface.inc.php');

if ( !$permission->Check('accrual','enabled')
		OR !( $permission->Check('accrual','view') OR $permission->Check('accrual','view_own') OR $permission->Check('accrual','view_child') ) ) {
	$permission->Redirect( FALSE ); //Redirect
}

//Debug::setVerbosity( 11 );

$smarty->assign('title', TTi18n::gettext($title = 'Accrual Balance List')); // See index.php
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
												'filter_user_id',
												'ids',
												) ) );

URLBuilder::setURL($_SERVER['SCRIPT_NAME'],
											array(
													'filter_user_id' => $filter_user_id,
													'sort_column' => $sort_column,
													'sort_order' => $sort_order,
													'page' => $page
												) );

$sort_array = NULL;
if ( $sort_column != '' ) {
	$sort_array = array($sort_column => $sort_order);
}

Debug::Arr($ids,'Selected Objects', __FILE__, __LINE__, __METHOD__,10);

$action = Misc::findSubmitButton();
switch ($action) {
	case 'add':
		Redirect::Page( URLBuilder::getURL( NULL, 'EditUserAccrual.php') );
		break;
	default:
		$ablf = TTnew( 'AccrualBalanceListFactory' );
		$ulf = TTnew( 'UserListFactory' );

		if ( $permission->Check('accrual','view') OR $permission->Check('accrual','view_child') ) {
			if ( isset($filter_user_id) ) {
				$user_id = $filter_user_id;
			} else {
				$user_id = $current_user->getId();
				$filter_user_id = $current_user->getId();
			}
		} else {
			$filter_user_id = $user_id = $current_user->getId();
		}

		$filter_data = NULL;

		//Get user object
		$ulf->getByIdAndCompanyID( $user_id, $current_company->getId() );
		if (  $ulf->getRecordCount() > 0 ) {
			$user_obj = $ulf->getCurrent();

			$ablf->getByUserIdAndCompanyId( $user_id, $current_company->getId(), $current_user_prefs->getItemsPerPage(), $page, NULL, $sort_array );

			$pager = new Pager($ablf);

			$aplf = TTnew( 'AccrualPolicyListFactory' );
			$accrual_policy_options = $aplf->getByCompanyIDArray( $current_company->getId() );

			foreach ($ablf as $ab_obj) {
				$accruals[] = array(
									'id' => $ab_obj->getId(),
									'user_id' => $ab_obj->getUser(),
									'accrual_policy_id' => $ab_obj->getAccrualPolicyId(),
									'accrual_policy' => $accrual_policy_options[$ab_obj->getAccrualPolicyId()],
									'balance' => $ab_obj->getBalance(),
									'deleted' => $ab_obj->getDeleted()
								);
			}

			$smarty->assign_by_ref('accruals', $accruals);

			$hlf = TTnew( 'HierarchyListFactory' );
			$permission_children_ids = $hlf->getHierarchyChildrenByCompanyIdAndUserIdAndObjectTypeID( $current_company->getId(), $current_user->getId() );
			Debug::Arr($permission_children_ids,'Permission Children Ids:', __FILE__, __LINE__, __METHOD__,10);
			if ( $permission->Check('accrual','view') == FALSE ) {
				if ( $permission->Check('accrual','view_child') ) {
					$filter_data['permission_children_ids'] = $permission_children_ids;
				}
				if ( $permission->Check('accrual','view_own') ) {
					$filter_data['permission_children_ids'][] = $current_user->getId();
				}
			}

			$ulf->getSearchByCompanyIdAndArrayCriteria( $current_company->getId(), $filter_data );
			$user_options = $ulf->getArrayByListFactory( $ulf, FALSE, TRUE );
			$smarty->assign_by_ref('user_options', $user_options);

			$smarty->assign_by_ref('filter_user_id', $filter_user_id);
			$smarty->assign('is_owner', $permission->isOwner( $user_obj->getCreatedBy(), $user_obj->getId() ) );
			$smarty->assign('is_child', $permission->isChild( $user_obj->getId(), $permission_children_ids ) );

			$smarty->assign_by_ref('sort_column', $sort_column );
			$smarty->assign_by_ref('sort_order', $sort_order );

			$smarty->assign_by_ref('paging_data', $pager->getPageVariables() );
		}

		break;
}
$smarty->display('accrual/UserAccrualBalanceList.tpl');
?>