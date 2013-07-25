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
 * $Id: EditUserGroup.php 4104 2011-01-04 19:04:05Z ipso $
 * $Date: 2011-01-04 11:04:05 -0800 (Tue, 04 Jan 2011) $
 */
require_once('../../includes/global.inc.php');
require_once(Environment::getBasePath() .'includes/Interface.inc.php');

if ( !$permission->Check('user','enabled')
		OR !( $permission->Check('user','edit') OR $permission->Check('user','edit_own') ) ) {
	$permission->Redirect( FALSE ); //Redirect
}

//Debug::setVerbosity(11);

$smarty->assign('title', TTi18n::gettext($title = 'Edit Employee Group')); // See index.php

/*
 * Get FORM variables
 */
extract	(FormVariables::GetVariables(
										array	(
												'action',
												'id',
												'previous_parent_id',
												'data'
												) ) );

$ugf = TTnew( 'UserGroupFactory' );

$action = Misc::findSubmitButton();
switch ($action) {
	case 'submit':
		Debug::Text('Submit!', __FILE__, __LINE__, __METHOD__,10);

		$ugf->setId( $data['id'] );
		$ugf->setCompany( $current_company->getId() );
		$ugf->setPreviousParent( $previous_parent_id );
		$ugf->setParent( $data['parent_id'] );
		$ugf->setName( $data['name'] );

		if ( $ugf->isValid() ) {
			$ugf->Save();

			Redirect::Page( URLBuilder::getURL( NULL, 'UserGroupList.php') );

			break;
		}
	default:
		$uglf = TTnew( 'UserGroupListFactory' );

		$nodes = FastTree::FormatArray( $uglf->getByCompanyIdArray( $current_company->getId() ), 'TEXT', TRUE);

		foreach($nodes as $node) {
			$parent_list_options[$node['id']] = $node['text'];
		}

		$smarty->assign_by_ref('parent_list_options', $parent_list_options);

		if ( isset($id) ) {
			BreadCrumb::setCrumb($title);

			//Get parent data
			$ft = new FastTree( $fast_tree_user_group_options );
			$ft->setTree( $current_company->getID() );

			//$uwlf->GetByUserIdAndCompanyId($current_user->getId(), $current_company->getId() );
			$uglf->getById( $id );

			foreach ($uglf as $group_obj) {

				$parent_id = $ft->getParentID( $group_obj->getId() );

				$data = array(
									'id' => $group_obj->getId(),
									'previous_parent_id' => $parent_id,
									'parent_id' => $parent_id,
									'name' => $group_obj->getName(),
									'created_date' => $group_obj->getCreatedDate(),
									'created_by' => $group_obj->getCreatedBy(),
									'updated_date' => $group_obj->getUpdatedDate(),
									'updated_by' => $group_obj->getUpdatedBy(),
									'deleted_date' => $group_obj->getDeletedDate(),
									'deleted_by' => $group_obj->getDeletedBy()
								);
			}
		}

		$smarty->assign_by_ref('data', $data);

		break;
}

$smarty->assign_by_ref('ugf', $ugf);

$smarty->display('users/EditUserGroup.tpl');
?>