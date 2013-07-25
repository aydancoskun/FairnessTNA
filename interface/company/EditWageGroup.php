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
 * $Revision: 1246 $
 * $Id: EditUserTitle.php 1246 2007-09-14 23:47:42Z ipso $
 * $Date: 2007-09-14 16:47:42 -0700 (Fri, 14 Sep 2007) $
 */
require_once('../../includes/global.inc.php');
require_once(Environment::getBasePath() .'includes/Interface.inc.php');

if ( !$permission->Check('wage','enabled')
		OR !( $permission->Check('wage','edit') ) ) {

	$permission->Redirect( FALSE ); //Redirect

}

$smarty->assign('title',  TTi18n::gettext($title = 'Edit Wage Group')); // See index.php

/*
 * Get FORM variables
 */
extract	(FormVariables::GetVariables(
										array	(
												'action',
												'id',
												'group_data'
												) ) );

$wgf = TTnew( 'WageGroupFactory' );

$action = Misc::findSubmitButton();
switch ($action) {
	case 'submit':
		Debug::Text('Submit!', __FILE__, __LINE__, __METHOD__,10);

		$wgf->setId($group_data['id']);
		$wgf->setCompany( $current_company->getId() );
		$wgf->setName($group_data['name']);

		if ( $wgf->isValid() ) {
			$wgf->Save();

			Redirect::Page( URLBuilder::getURL(NULL, 'WageGroupList.php') );

			break;
		}
	default:
		if ( isset($id) ) {
			BreadCrumb::setCrumb($title);

			$wglf = TTnew( 'WageGroupListFactory' );

			$wglf->GetByIdAndCompanyId($id, $current_company->getId() );

			foreach ($wglf as $group_obj) {
				//Debug::Arr($title_obj,'Title Object', __FILE__, __LINE__, __METHOD__,10);

				$group_data = array(
									'id' => $group_obj->getId(),
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

		$smarty->assign_by_ref('group_data', $group_data);

		break;
}

$smarty->assign_by_ref('wgf', $wgf);

$smarty->display('company/EditWageGroup.tpl');
?>