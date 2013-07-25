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
 * $Id: EditPayStubEntryAccount.php 4104 2011-01-04 19:04:05Z ipso $
 * $Date: 2011-01-04 11:04:05 -0800 (Tue, 04 Jan 2011) $
 */
require_once('../../includes/global.inc.php');
require_once(Environment::getBasePath() .'includes/Interface.inc.php');

if ( !$permission->Check('pay_stub_account','enabled')
		OR !( $permission->Check('pay_stub_account','edit') OR $permission->Check('pay_stub_account','edit_own') ) ) {

	$permission->Redirect( FALSE ); //Redirect

}

$smarty->assign('title', TTi18n::gettext($title = 'Edit Pay Stub Account')); // See index.php

/*
 * Get FORM variables
 */
extract	(FormVariables::GetVariables(
										array	(
												'action',
												'id',
												'data'
												) ) );

$pseaf = TTnew( 'PayStubEntryAccountFactory' );

$action = Misc::findSubmitButton();
$action = strtolower($action);
switch ($action) {
	case 'submit':
		Debug::Text('Submit!', __FILE__, __LINE__, __METHOD__,10);
		//Debug::setVerbosity(11);

		$pseaf->setId( $data['id'] );
		$pseaf->setCompany( $current_company->getId() );
		$pseaf->setStatus( $data['status_id'] );
		$pseaf->setType( $data['type_id'] );
		$pseaf->setName( $data['name'] );
		$pseaf->setOrder( $data['order'] );
		$pseaf->setAccrual( $data['accrual_id'] );
		$pseaf->setDebitAccount( $data['debit_account'] );
		$pseaf->setCreditAccount( $data['credit_account'] );

		if ( $pseaf->isValid() ) {
			$pseaf->Save();

			Redirect::Page( URLBuilder::getURL( NULL, 'PayStubEntryAccountList.php') );

			break;
		}

	default:
		if ( isset($id) ) {
			BreadCrumb::setCrumb($title);

			$psealf = TTnew( 'PayStubEntryAccountListFactory' );
			$psealf->getById($id);

			foreach ($psealf as $psea_obj) {
				//Debug::Arr($station,'Department', __FILE__, __LINE__, __METHOD__,10);

				$data = array(
									'id' => $psea_obj->getId(),
									'status_id' => $psea_obj->getStatus(),
									'type_id' => $psea_obj->getType(),
									'name' => $psea_obj->getName(),
									'order' => $psea_obj->getOrder(),
									'accrual_id' => $psea_obj->getAccrual(),
									'debit_account' => $psea_obj->getDebitAccount(),
									'credit_account' => $psea_obj->getCreditAccount(),
									'accrual_id' => $psea_obj->getAccrual(),
									'created_date' => $psea_obj->getCreatedDate(),
									'created_by' => $psea_obj->getCreatedBy(),
									'updated_date' => $psea_obj->getUpdatedDate(),
									'updated_by' => $psea_obj->getUpdatedBy(),
									'deleted_date' => $psea_obj->getDeletedDate(),
									'deleted_by' => $psea_obj->getDeletedBy()
								);
			}
		}

		//Select box options;
		$data['status_options'] = $pseaf->getOptions('status');
		$data['type_options'] = $pseaf->getOptions('type');

		$psealf = TTnew( 'PayStubEntryAccountListFactory' );
		$data['accrual_options'] = $psealf->getByCompanyIdAndStatusIdAndTypeIdArray( $current_company->getId(), 10, array(50), TRUE );

		$smarty->assign_by_ref('data', $data);

		break;
}

$smarty->assign_by_ref('pseaf', $pseaf);

$smarty->display('pay_stub/EditPayStubEntryAccount.tpl');
?>