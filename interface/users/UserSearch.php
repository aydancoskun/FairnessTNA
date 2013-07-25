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
 * $Id: UserSearch.php 4104 2011-01-04 19:04:05Z ipso $
 * $Date: 2011-01-04 11:04:05 -0800 (Tue, 04 Jan 2011) $
 */
require_once('../../includes/global.inc.php');
//Debug::setVerbosity(11);

$skip_message_check = TRUE;
require_once(Environment::getBasePath() .'includes/Interface.inc.php');

if ( !$permission->Check('user','enabled')
		OR !( $permission->Check('user','view') OR $permission->Check('user','view_own') OR $permission->Check('user','view_child')) ) {
	$permission->Redirect( FALSE ); //Redirect
}

$smarty->assign('title', TTi18n::gettext($title = 'Employee Search')); // See index.php

/*
 * Get FORM variables
 */
extract	(FormVariables::GetVariables(
										array	(
												'action',
												'src_element_id',
												'dst_element_id',
												'data'
												) ) );

$ulf = TTnew( 'UserListFactory' );

$action = Misc::findSubmitButton();
$action = strtolower($action);
switch ($action) {
	case 'search':
		Debug::Text('Search!', __FILE__, __LINE__, __METHOD__,10);

		$hlf = TTnew( 'HierarchyListFactory' );
		$permission_children_ids = $hlf->getHierarchyChildrenByCompanyIdAndUserIdAndObjectTypeID( $current_company->getId(), $current_user->getId() );
		Debug::Arr($permission_children_ids,'Permission Children Ids:', __FILE__, __LINE__, __METHOD__,10);
		if ( $permission->Check('user','view') == FALSE ) {
			if ( $permission->Check('user','view_child') ) {
				$data['permission_children_ids'] = $permission_children_ids;
			}
			if ( $permission->Check('user','view_own') ) {
				$data['permission_children_ids'][] = $current_user->getId();
			}
		}
		$ulf->getSearchByCompanyIdAndArrayCriteria( $current_company->getId(), $data );

		//$ulf->getSearchByCompanyIdAndBranchIdAndDepartmentIdAndStatusId( $current_company->getId(), $data['branch_id'], $data['department_id'], $data['status_id']);
		$data['user_options'] = $ulf->getArrayByListFactory( $ulf, FALSE );
		if ( is_array($data['user_options']) ) {
			$data['filter_user_ids'] = array_keys($data['user_options']);
			$data['total_users'] = count($data['user_options']);
		}
		//var_dump($filter_user_ids);
	default:

		if ( isset($current_company) ) {
			$uglf = TTnew( 'UserGroupListFactory' );
			$group_options = $uglf->getArrayByNodes( FastTree::FormatArray( $uglf->getByCompanyIdArray( $current_company->getId() ), 'TEXT', TRUE) );

			$blf = TTnew( 'BranchListFactory' );
			$branch_options = $blf->getByCompanyIdArray( $current_company->getId() );

			$dlf = TTnew( 'DepartmentListFactory' );
			$department_options = $dlf->getByCompanyIdArray( $current_company->getId() );
		}

		//Select box options;
		$data['status_options'] = $ulf->getOptions('status');
		$data['group_options'] = $group_options;
		$data['branch_options'] = $branch_options;
		$data['department_options'] = $department_options;

		if ( $action != 'search' ) {
			$data['status_id'] = array(10);
		}

		$smarty->assign_by_ref('data', $data);
		Debug::Text('SRC Element ID: '. $src_element_id, __FILE__, __LINE__, __METHOD__,10);
		$smarty->assign_by_ref('src_element_id', $src_element_id);
		$smarty->assign_by_ref('dst_element_id', $dst_element_id);

		break;
}
$smarty->display('users/UserSearch.tpl');
?>