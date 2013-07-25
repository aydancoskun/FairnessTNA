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
 * $Id: EditPayStubEntryNameAccount.php 4104 2011-01-04 19:04:05Z ipso $
 * $Date: 2011-01-04 11:04:05 -0800 (Tue, 04 Jan 2011) $
 */
require_once('../../includes/global.inc.php');
require_once(Environment::getBasePath() .'includes/Interface.inc.php');

if ( !$permission->Check('pay_stub','enabled')
		OR !$permission->Check('pay_stub','view') ) {

	$permission->Redirect( FALSE ); //Redirect

}

$smarty->assign('title', TTi18n::gettext($title = 'General Ledger Accounts')); // See index.php

/*
 * Get FORM variables
 */
extract	(FormVariables::GetVariables(
										array	(
												'action',
												'id',
												'name_account_data'
												) ) );

$psenalf = TTnew( 'PayStubEntryNameAccountListFactory' );

switch ($action) {
	case 'submit':
		Debug::Text('Submit!', __FILE__, __LINE__, __METHOD__,10);

		$psenaf = TTnew( 'PayStubEntryNameAccountFactory' );

		$psenaf->StartTransaction();
		foreach($name_account_data as $pay_stub_entry_name_id => $value_arr){
			Debug::Text('Pay Stub Entry Name ID: '. $pay_stub_entry_name_id, __FILE__, __LINE__, __METHOD__,10);

			if ( ( isset($value_arr['debit_account'])  AND $value_arr['debit_account'] != '' )
					OR ( isset($value_arr['credit_account']) AND $value_arr['credit_account'] != '' )
					OR ( isset($value_arr['id']) AND $value_arr['id'] != '' )
				) {

				Debug::Text('Pay Stub Entry Name ID: '. $pay_stub_entry_name_id .' ID: '. $value_arr['id'] .'Debit Account: '. $value_arr['debit_account'] .' Credit Account: '. $value_arr['credit_account'], __FILE__, __LINE__, __METHOD__,10);

				if ( isset($value_arr['id']) AND $value_arr['id'] != '' ) {
					$psenaf->setId( $value_arr['id'] );
				}
				$psenaf->setCompany( $current_company->getId() );
				$psenaf->setPayStubEntryNameId( $pay_stub_entry_name_id  );
				$psenaf->setDebitAccount( $value_arr['debit_account'] );
				$psenaf->setCreditAccount( $value_arr['credit_account'] );
				if ( $psenaf->isValid() ) {
					$psenaf->Save();
				}
			} elseif ( ( isset($value_arr['id']) AND $value_arr['id'] != '' )
						AND $value_arr['debit_account'] == '' AND $value_arr['credit_account'] == '') {
				Debug::Text('Delete: ', __FILE__, __LINE__, __METHOD__,10);
			}
		}

		//$psenaf->FailTransaction();
		$psenaf->CommitTransaction();

		Redirect::Page( URLBuilder::getURL(NULL, Environment::getBaseURL().'/pay_stub/EditPayStubEntryNameAccount.php') );

		break;
	default:
		if ( !isset($action) ) {
			BreadCrumb::setCrumb($title);

			$psenalf = TTnew( 'PayStubEntryNameAccountListFactory' );
			$psenalf->getByCompanyId( $current_company->getId() );

			foreach ($psenalf as $name_account_obj) {
				//Debug::Arr($department,'Department', __FILE__, __LINE__, __METHOD__,10);

				$name_account_data[$name_account_obj->getPayStubEntryNameId()] = array(
											'id' => $name_account_obj->getId(),
											'pay_stub_entry_name_id' => $name_account_obj->getPayStubEntryNameId(),
											'debit_account' => $name_account_obj->getDebitAccount(),
											'credit_account' => $name_account_obj->getCreditAccount(),
											'created_date' => $name_account_obj->getCreatedDate(),
											'created_by' => $name_account_obj->getCreatedBy(),
											'updated_date' => $name_account_obj->getUpdatedDate(),
											'updated_by' => $name_account_obj->getUpdatedBy(),
											'deleted_date' => $name_account_obj->getDeletedDate(),
											'deleted_by' => $name_account_obj->getDeletedBy()
								);
			}

			//Get all accounts
			$psenlf = TTnew( 'PayStubEntryNameListFactory' );
			$psenlf->getAll();

			$type_options  = $psenlf->getOptions('type');

			$i=0;
			foreach($psenlf as $entry_name_obj) {
				$display_type = FALSE;
				if ( $i == 0 ) {
					$display_type = TRUE;
				} else {
					if ( $entry_name_obj->getType() != $prev_type_id) {
						$display_type = TRUE;
					}
				}
				$name_account_data[$entry_name_obj->getId()]['pay_stub_entry_description'] = $entry_name_obj->getDescription();
				$name_account_data[$entry_name_obj->getId()]['pay_stub_entry_name_id'] = $entry_name_obj->getId();
				$name_account_data[$entry_name_obj->getId()]['type_id'] = $entry_name_obj->getType();
				$name_account_data[$entry_name_obj->getId()]['type'] = $type_options[$entry_name_obj->getType()];

				$name_account_data[$entry_name_obj->getId()]['display_type'] = $display_type;

				$data[] = $name_account_data[$entry_name_obj->getId()];

				$prev_type_id = $entry_name_obj->getType();
				$i++;
			}


		}

		$smarty->assign_by_ref('name_account_data', $data);
		break;
}

$smarty->assign_by_ref('psenalf', $psenalf);
//$smarty->assign_by_ref('current_time', TTDate::getDate('TIME') );

$smarty->display('pay_stub/EditPayStubEntryNameAccount.tpl');
?>