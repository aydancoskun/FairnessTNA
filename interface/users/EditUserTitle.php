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
 * $Id: EditUserTitle.php 4104 2011-01-04 19:04:05Z ipso $
 * $Date: 2011-01-04 11:04:05 -0800 (Tue, 04 Jan 2011) $
 */
require_once('../../includes/global.inc.php');
require_once(Environment::getBasePath() .'includes/Interface.inc.php');

if ( !$permission->Check('user','enabled')
		OR !( $permission->Check('user','edit') ) ) {

	$permission->Redirect( FALSE ); //Redirect

}

$smarty->assign('title',  TTi18n::gettext($title = 'Edit Employee Title')); // See index.php

/*
 * Get FORM variables
 */
extract	(FormVariables::GetVariables(
										array	(
												'action',
												'id',
												'title_data'
												) ) );

$utf = TTnew( 'UserTitleFactory' );

$action = Misc::findSubmitButton();
switch ($action) {
	case 'submit':
		Debug::Text('Submit!', __FILE__, __LINE__, __METHOD__,10);

		$utf->setId($title_data['id']);
		$utf->setCompany( $current_company->getId() );
		$utf->setName($title_data['name']);

		if ( $utf->isValid() ) {
			$utf->Save();

			Redirect::Page( URLBuilder::getURL(NULL, 'UserTitleList.php') );

			break;
		}
	default:
		if ( isset($id) ) {
			BreadCrumb::setCrumb($title);

			$utlf = TTnew( 'UserTitleListFactory' );

			$utlf->GetByIdAndCompanyId($id, $current_company->getId() );

			foreach ($utlf as $title_obj) {
				//Debug::Arr($title_obj,'Title Object', __FILE__, __LINE__, __METHOD__,10);

				$title_data = array(
									'id' => $title_obj->getId(),
									'name' => $title_obj->getName(),
									'created_date' => $title_obj->getCreatedDate(),
									'created_by' => $title_obj->getCreatedBy(),
									'updated_date' => $title_obj->getUpdatedDate(),
									'updated_by' => $title_obj->getUpdatedBy(),
									'deleted_date' => $title_obj->getDeletedDate(),
									'deleted_by' => $title_obj->getDeletedBy()
								);
			}
		}

		$smarty->assign_by_ref('title_data', $title_data);

		break;
}

$smarty->assign_by_ref('utf', $utf);

$smarty->display('users/EditUserTitle.tpl');
?>