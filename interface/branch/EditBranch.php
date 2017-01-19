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
 * $Id: EditBranch.php 5164 2011-08-26 23:00:02Z ipso $
 * $Date: 2011-08-26 16:00:02 -0700 (Fri, 26 Aug 2011) $
 */
require_once('../../includes/global.inc.php');
require_once(Environment::getBasePath() .'includes/Interface.inc.php');

if ( !$permission->Check('branch','enabled')
		OR !( $permission->Check('branch','edit') OR $permission->Check('branch','edit_own') ) ) {
	$permission->Redirect( FALSE ); //Redirect
}

$smarty->assign('title', TTi18n::gettext($title = 'Edit Branch')); // See index.php

/*
 * Get FORM variables
 */
extract	(FormVariables::GetVariables(
										array	(
												'action',
												'id',
												'branch_data'
												) ) );

$bf = TTnew( 'BranchFactory' );

$action = Misc::findSubmitButton();
$action = strtolower($action);
switch ($action) {
	case 'submit':
		Debug::Text('Submit!', __FILE__, __LINE__, __METHOD__,10);

		$bf->setId($branch_data['id']);
		$bf->setCompany( $current_company->getId() );
		$bf->setStatus($branch_data['status']);
		$bf->setName($branch_data['name']);
		$bf->setManualId($branch_data['manual_id']);

		if ($branch_data['address1'] != '') {
			$bf->setAddress1($branch_data['address1']);
		}
		if ($branch_data['address2'] != '') {
			$bf->setAddress2($branch_data['address2']);
		}

		$bf->setCity($branch_data['city']);
		$bf->setCountry($branch_data['country']);
		$bf->setProvince($branch_data['province']);

		if ($branch_data['postal_code'] != '') {
			$bf->setPostalCode($branch_data['postal_code']);
		}
		if ($branch_data['work_phone'] != '') {
			$bf->setWorkPhone($branch_data['work_phone']);
		}
		if ($branch_data['fax_phone'] != '') {
			$bf->setFaxPhone($branch_data['fax_phone']);
		}

		if ( isset($branch_data['other_id1']) ) {
			$bf->setOtherID1( $branch_data['other_id1'] );
		}
		if ( isset($branch_data['other_id2']) ) {
			$bf->setOtherID2( $branch_data['other_id2'] );
		}
		if ( isset($branch_data['other_id3']) ) {
			$bf->setOtherID3( $branch_data['other_id3'] );
		}
		if ( isset($branch_data['other_id4']) ) {
			$bf->setOtherID4( $branch_data['other_id4'] );
		}
		if ( isset($branch_data['other_id5']) ) {
			$bf->setOtherID5( $branch_data['other_id5'] );
		}

		if ( $bf->isValid() ) {
			$bf->Save();

			Redirect::Page( URLBuilder::getURL(NULL, 'BranchList.php') );

			break;
		}
	default:
		if ( isset($id) ) {
			BreadCrumb::setCrumb($title);

			$blf = TTnew( 'BranchListFactory' );

			$blf->GetByIdAndCompanyId($id, $current_company->getId() );

			foreach ($blf as $branch) {
				$branch_data = array(
									'id' => $branch->getId(),
									'status' => $branch->getStatus(),
									'manual_id' => $branch->getManualID(),
									'name' => $branch->getName(),
									'address1' => $branch->getAddress1(),
									'address2' => $branch->getAddress2(),
									'city' => $branch->getCity(),
									'province' => $branch->getProvince(),
									'country' => $branch->getCountry(),
									'postal_code' => $branch->getPostalCode(),
									'work_phone' => $branch->getWorkPhone(),
									'fax_phone' => $branch->getFaxPhone(),
									'other_id1' => $branch->getOtherID1(),
									'other_id2' => $branch->getOtherID2(),
									'other_id3' => $branch->getOtherID3(),
									'other_id4' => $branch->getOtherID4(),
									'other_id5' => $branch->getOtherID5(),
									'created_date' => $branch->getCreatedDate(),
									'created_by' => $branch->getCreatedBy(),
									'updated_date' => $branch->getUpdatedDate(),
									'updated_by' => $branch->getUpdatedBy(),
									'deleted_date' => $branch->getDeletedDate(),
									'deleted_by' => $branch->getDeletedBy()
								);
			}
		} elseif ( $action != 'submit' ) {
			$next_available_manual_id = BranchListFactory::getNextAvailableManualId( $current_company->getId() );

			$branch_data = array(
							'country' => $current_company->getCountry(),
							'province' => $current_company->getProvince(),
							'next_available_manual_id' => $next_available_manual_id,
							);
		}

		//Select box options;
		$branch_data['status_options'] = $bf->getOptions('status');

		$cf = TTnew( 'CompanyFactory' );
		$branch_data['country_options'] = $cf->getOptions('country');
		$branch_data['province_options'] = $cf->getOptions('province', $branch_data['country'] );

		//Get other field names
		$oflf = TTnew( 'OtherFieldListFactory' );
		$branch_data['other_field_names'] = $oflf->getByCompanyIdAndTypeIdArray( $current_company->getID(), 4 );

		$smarty->assign_by_ref('branch_data', $branch_data);

		break;
}

$smarty->assign_by_ref('bf', $bf);

$smarty->display('branch/EditBranch.tpl');
?>