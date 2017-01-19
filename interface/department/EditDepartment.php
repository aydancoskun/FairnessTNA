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
 * $Revision: 5164 $
 * $Id: EditDepartment.php 5164 2011-08-26 23:00:02Z ipso $
 * $Date: 2011-08-26 16:00:02 -0700 (Fri, 26 Aug 2011) $
 */
require_once('../../includes/global.inc.php');
require_once(Environment::getBasePath() .'includes/Interface.inc.php');

if ( !$permission->Check('department','enabled')
		OR !( $permission->Check('department','view') OR $permission->Check('department','view_own') ) ) {
	$permission->Redirect( FALSE ); //Redirect
}

$smarty->assign('title', TTi18n::gettext($title = 'Edit Department')); // See index.php

/*
 * Get FORM variables
 */
extract	(FormVariables::GetVariables(
										array	(
												'action',
												'id',
												'department_data'
												) ) );

$df = TTnew( 'DepartmentFactory' );

$action = Misc::findSubmitButton();
switch ($action) {
	case 'submit':
		Debug::Text('Submit!', __FILE__, __LINE__, __METHOD__,10);

		$df->setId($department_data['id']);
		$df->setCompany( $current_company->getId() );
		$df->setStatus($department_data['status']);
		$df->setName($department_data['name']);
		$df->setManualId($department_data['manual_id']);

		if ( isset($department_data['other_id1']) ) {
			$df->setOtherID1( $department_data['other_id1'] );
		}
		if ( isset($department_data['other_id2']) ) {
			$df->setOtherID2( $department_data['other_id2'] );
		}
		if ( isset($department_data['other_id3']) ) {
			$df->setOtherID3( $department_data['other_id3'] );
		}
		if ( isset($department_data['other_id4']) ) {
			$df->setOtherID4( $department_data['other_id4'] );
		}
		if ( isset($department_data['other_id5']) ) {
			$df->setOtherID5( $department_data['other_id5'] );
		}

		if ( $df->isValid() ) {
			$df->Save(FALSE);

			if ( isset($department_data['branch_list']) ){
				$df->setBranch( $department_data['branch_list'] );
				$df->Save(TRUE);
			}

			Redirect::Page( URLBuilder::getURL(NULL, 'DepartmentList.php') );

			break;
		}
	default:
		if ( isset($id) ) {
			BreadCrumb::setCrumb($title);

			$dlf = TTnew( 'DepartmentListFactory' );

			$dlf->GetByIdAndCompanyId($id, $current_company->getId() );

			foreach ($dlf as $department) {
				Debug::Arr($department,'Department', __FILE__, __LINE__, __METHOD__,10);

				$department_data = array(
									'id' => $department->getId(),
									'company_name' => $current_company->getName(),
									'status' => $department->getStatus(),
									'name' => $department->getName(),
									'manual_id' => $department->getManualID(),
									'branch_list' => $department->getBranch(),
									'other_id1' => $department->getOtherID1(),
									'other_id2' => $department->getOtherID2(),
									'other_id3' => $department->getOtherID3(),
									'other_id4' => $department->getOtherID4(),
									'other_id5' => $department->getOtherID5(),
									'created_date' => $department->getCreatedDate(),
									'created_by' => $department->getCreatedBy(),
									'updated_date' => $department->getUpdatedDate(),
									'updated_by' => $department->getUpdatedBy(),
									'deleted_date' => $department->getDeletedDate(),
									'deleted_by' => $department->getDeletedBy()
								);
			}
		} elseif ( $action != 'submit' ) {
			$next_available_manual_id = DepartmentListFactory::getNextAvailableManualId( $current_company->getId() );

			$department_data = array(
							'next_available_manual_id' => $next_available_manual_id,
							);
		}

		//Select box options;
		$department_data['status_options'] = $df->getOptions('status');
		$blf = TTnew( 'BranchListFactory' );
		$blf->getByCompanyId( $current_company->getId() );
		$department_data['branch_list_options'] = $blf->getArrayByListFactory( $blf, FALSE);

		//Get other field names
		$oflf = TTnew( 'OtherFieldListFactory' );
		$department_data['other_field_names'] = $oflf->getByCompanyIdAndTypeIdArray( $current_company->getID(), 5 );

		$smarty->assign_by_ref('department_data', $department_data);

		break;
}

$smarty->assign_by_ref('df', $df);

$smarty->display('department/EditDepartment.tpl');
?>