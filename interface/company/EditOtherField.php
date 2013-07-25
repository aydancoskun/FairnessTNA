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
 * $Id: EditOtherField.php 4104 2011-01-04 19:04:05Z ipso $
 * $Date: 2011-01-04 11:04:05 -0800 (Tue, 04 Jan 2011) $
 */
require_once('../../includes/global.inc.php');
require_once(Environment::getBasePath() .'includes/Interface.inc.php');

if ( !$permission->Check('other_field','enabled')
		OR !( $permission->Check('other_field','edit') OR $permission->Check('other_field','edit_own') ) ) {

	$permission->Redirect( FALSE ); //Redirect
}

$smarty->assign('title', TTi18n::gettext($title = 'Edit Other Field')); // See index.php

/*
 * Get FORM variables
 */
extract	(FormVariables::GetVariables(
										array	(
												'action',
												'id',
												'data'
												) ) );

$off = TTnew( 'OtherFieldFactory' );

$action = Misc::findSubmitButton();
switch ($action) {
	case 'submit':
		Debug::Text('Submit!', __FILE__, __LINE__, __METHOD__,10);

		$off->setId( $data['id'] );
		$off->setCompany( $current_company->getId() );
		$off->setType( $data['type_id'] );
		$off->setOtherID1( $data['other_id1'] );
		$off->setOtherID2( $data['other_id2'] );
		$off->setOtherID3( $data['other_id3'] );
		$off->setOtherID4( $data['other_id4'] );
		$off->setOtherID5( $data['other_id5'] );

		if ( $off->isValid() ) {
			$off->Save();

			Redirect::Page( URLBuilder::getURL( array('type_id' => $data['type_id']), 'OtherFieldList.php') );

			break;
		}
	default:
		if ( isset($id) ) {
			BreadCrumb::setCrumb($title);

			$oflf = TTnew( 'OtherFieldListFactory' );

			//$uwlf->GetByUserIdAndCompanyId($current_user->getId(), $current_company->getId() );
			$oflf->getById($id);

			foreach ($oflf as $obj) {
				$data = array(
									'id' => $obj->getId(),
									'company_id' => $obj->getCompany(),
									'type_id' => $obj->getType(),
									'other_id1' => $obj->getOtherID1(),
									'other_id2' => $obj->getOtherID2(),
									'other_id3' => $obj->getOtherID3(),
									'other_id4' => $obj->getOtherID4(),
									'other_id5' => $obj->getOtherID5(),
									'created_date' => $obj->getCreatedDate(),
									'created_by' => $obj->getCreatedBy(),
									'updated_date' => $obj->getUpdatedDate(),
									'updated_by' => $obj->getUpdatedBy(),
									'deleted_date' => $obj->getDeletedDate(),
									'deleted_by' => $obj->getDeletedBy()
								);
			}
		}
		//Select box options;
		//$jif = TTnew( 'JobItemFactory' );
		$data['type_options'] = $off->getOptions('type');

		$smarty->assign_by_ref('data', $data);

		break;
}

$smarty->assign_by_ref('off', $off);

$smarty->display('company/EditOtherField.tpl');
?>