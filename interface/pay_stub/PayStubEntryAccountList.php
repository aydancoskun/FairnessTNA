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
 * $Revision: 4104 $
 * $Id: PayStubEntryAccountList.php 4104 2011-01-04 19:04:05Z ipso $
 * $Date: 2011-01-04 11:04:05 -0800 (Tue, 04 Jan 2011) $
 */
require_once('../../includes/global.inc.php');
require_once(Environment::getBasePath() .'includes/Interface.inc.php');

if ( !$permission->Check('pay_stub_account','enabled')
		OR !( $permission->Check('pay_stub_account','view') OR $permission->Check('pay_stub_account','view_own') ) ) {

	$permission->Redirect( FALSE ); //Redirect

}

$smarty->assign('title', TTi18n::gettext($title = 'Pay Stub Account List')); // See index.php

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
	case 'add_presets':
		//Debug::setVerbosity(11);
		PayStubEntryAccountFactory::addPresets( $current_company->getId() );

		Redirect::Page( URLBuilder::getURL( NULL, 'PayStubEntryAccountList.php') );
	case 'add':

		Redirect::Page( URLBuilder::getURL( NULL, 'EditPayStubEntryAccount.php', FALSE) );

		break;
	case 'delete':
	case 'undelete':
		if ( strtolower($action) == 'delete' ) {
			$delete = TRUE;
		} else {
			$delete = FALSE;
		}

		$psealf = TTnew( 'PayStubEntryAccountListFactory' );

		foreach ($ids as $id) {
			$psealf->getByIdAndCompanyId($id, $current_company->getId() );
			foreach ($psealf as $psea_obj) {
				$psea_obj->setDeleted($delete);
				if ( $psea_obj->isValid() ) {
					$psea_obj->Save();
				}
			}
		}

		Redirect::Page( URLBuilder::getURL( NULL, 'PayStubEntryAccountList.php') );

		break;
	default:
		BreadCrumb::setCrumb($title);

		$psealf = TTnew( 'PayStubEntryAccountListFactory' );
		$psealf->getByCompanyId( $current_company->getId() );

		$pager = new Pager($psealf);

		$status_options = $psealf->getOptions('status');
		$type_options = $psealf->getOptions('type');

		foreach ($psealf as $psea_obj) {

			$rows[] = array(
								'id' => $psea_obj->getId(),
								'status_id' => $psea_obj->getStatus(),
								'status' => $status_options[$psea_obj->getStatus()],
								'type_id' => $psea_obj->getType(),
								'type' => $type_options[$psea_obj->getType()],
								'name' => $psea_obj->getName(),
								'ps_order' => $psea_obj->getOrder(),
								'debit_account' => $psea_obj->getDebitAccount(),
								'credit_account' => $psea_obj->getCreditAccount(),
								'deleted' => $psea_obj->getDeleted()
							);

		}
		$smarty->assign_by_ref('rows', $rows);

		$smarty->assign_by_ref('sort_column', $sort_column );
		$smarty->assign_by_ref('sort_order', $sort_order );

		$smarty->assign_by_ref('paging_data', $pager->getPageVariables() );

		break;
}
$smarty->display('pay_stub/PayStubEntryAccountList.tpl');
?>