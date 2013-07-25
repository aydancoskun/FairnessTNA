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
 * $Id: SchedulePolicyList.php 4104 2011-01-04 19:04:05Z ipso $
 * $Date: 2011-01-04 11:04:05 -0800 (Tue, 04 Jan 2011) $
 */
require_once('../../includes/global.inc.php');
require_once(Environment::getBasePath() .'includes/Interface.inc.php');

if ( !$permission->Check('schedule_policy','enabled')
		OR !( $permission->Check('schedule_policy','view') OR $permission->Check('schedule_policy','view_own') ) ) {

	$permission->Redirect( FALSE ); //Redirect

}

$smarty->assign('title', TTi18n::gettext($title = 'Schedule Policy List')); // See index.php
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
												'ids',
												) ) );

URLBuilder::setURL($_SERVER['SCRIPT_NAME'],
											array(
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

		Redirect::Page( URLBuilder::getURL( NULL, 'EditSchedulePolicy.php', FALSE) );

		break;
	case 'delete' OR 'undelete':
		if ( strtolower($action) == 'delete' ) {
			$delete = TRUE;
		} else {
			$delete = FALSE;
		}

		$splf = TTnew( 'SchedulePolicyListFactory' );

		foreach ($ids as $id) {
			$splf->getByIdAndCompanyId($id, $current_company->getId() );
			foreach ($splf as $sp_obj) {
				$sp_obj->setDeleted($delete);
				if ( $sp_obj->isValid() ) {
					$sp_obj->Save();
				}
			}
		}

		Redirect::Page( URLBuilder::getURL( NULL, 'SchedulePolicyList.php') );

		break;

	default:
		$splf = TTnew( 'SchedulePolicyListFactory' );
		$splf->getByCompanyId( $current_company->getId() );

		$pager = new Pager($splf);

		$aplf = TTnew( 'AbsencePolicyListFactory' );
		$absence_options = $aplf->getByCompanyIDArray( $current_company->getId(), TRUE );

		$mplf = TTnew( 'MealPolicyListFactory' );
		$meal_options = $mplf->getByCompanyIDArray( $current_company->getId(), TRUE );

		foreach ($splf as $sp_obj) {

			$policies[] = array(
								'id' => $sp_obj->getId(),
								'name' => $sp_obj->getName(),
								'meal_policy_id' => $sp_obj->getMealPolicyID(),
								'meal_policy' => Option::getByKey($sp_obj->getMealPolicyID(), $meal_options ),
								'absence_policy_id' => $sp_obj->getAbsencePolicyID(),
								'absence_policy' => Option::getByKey($sp_obj->getAbsencePolicyID(), $absence_options ),
								'start_stop_window' => $sp_obj->getStartStopWindow(),
								'deleted' => $sp_obj->getDeleted()
							);

		}
		$smarty->assign_by_ref('policies', $policies);

		$smarty->assign_by_ref('sort_column', $sort_column );
		$smarty->assign_by_ref('sort_order', $sort_order );

		$smarty->assign_by_ref('paging_data', $pager->getPageVariables() );

		break;
}
$smarty->display('policy/SchedulePolicyList.tpl');
?>