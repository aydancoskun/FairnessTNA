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
 * $Id: PayPeriodScheduleList.php 4104 2011-01-04 19:04:05Z ipso $
 * $Date: 2011-01-04 11:04:05 -0800 (Tue, 04 Jan 2011) $
 */
require_once('../../includes/global.inc.php');
require_once(Environment::getBasePath() .'includes/Interface.inc.php');

if ( !$permission->Check('pay_period_schedule','enabled')
		OR !( $permission->Check('pay_period_schedule','view') OR $permission->Check('pay_period_schedule','view_own') ) ) {

	$permission->Redirect( FALSE ); //Redirect

}

$smarty->assign('title', TTi18n::gettext($title = 'Pay Period Schedule List')); // See index.php
BreadCrumb::setCrumb($title);

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



//$ppslf = TTnew( 'PayPeriodScheduleFactory' );

Debug::Arr($ids,'Selected Objects', __FILE__, __LINE__, __METHOD__,10);

$action = Misc::findSubmitButton();
switch ($action) {
	case 'add':

		Redirect::Page( URLBuilder::getURL(NULL, 'EditPayPeriodSchedule.php', FALSE) );

		break;
	case 'delete' OR 'undelete':
		if ( strtolower($action) == 'delete' ) {
			$delete = TRUE;
		} else {
			$delete = FALSE;
		}

		$ppslf = TTnew( 'PayPeriodScheduleListFactory' );

		foreach ($ids as $id) {
			$ppslf->GetByIdAndCompanyId($id, $current_company->getId() );
			foreach ($ppslf as $pay_period_schedule) {
				$pay_period_schedule->setDeleted($delete);
				$pay_period_schedule->Save();
			}
		}

		Redirect::Page( URLBuilder::getURL(NULL, 'PayPeriodScheduleList.php') );

		break;

	default:
		$ppslf = TTnew( 'PayPeriodScheduleListFactory' );

		$ppslf->getByCompanyId($current_company->getId(), $current_user_prefs->getItemsPerPage(), $page, NULL, array($sort_column => $sort_order) );

		$pager = new Pager($ppslf);

		foreach ($ppslf as $pay_period_schedule) {

			$pay_period_schedules[] = array(
											'id' => $pay_period_schedule->getId(),
											'company_id' => $pay_period_schedule->getCompany(),
											'name' => $pay_period_schedule->getName(),
											'description' => $pay_period_schedule->getDescription(),
											'type' => Option::getByKey($pay_period_schedule->getType(), $pay_period_schedule->getOptions('type') ),
											/*
											'anchor_date' => TTDate::getDate( 'DATE', $pay_period_schedule->getAnchorDate() ),
											'primary_date' => TTDate::getDate( 'DATE', $pay_period_schedule->getPrimaryDate() ),
											'primary_transaction_date' => TTDate::getDate( 'DATE', $pay_period_schedule->getPrimaryTransactionDate() ),
											'secondary_date' => TTDate::getDate( 'DATE', $pay_period_schedule->getSecondaryDate() ),
											'secondary_transaction_date' => TTDate::getDate( 'DATE', $pay_period_schedule->getSecondaryTransactionDate() ),
											*/
											'deleted' => $pay_period_schedule->getDeleted()
											);

		}
		$smarty->assign_by_ref('pay_period_schedules', $pay_period_schedules);

		$smarty->assign_by_ref('sort_column', $sort_column );
		$smarty->assign_by_ref('sort_order', $sort_order );

		$smarty->assign_by_ref('paging_data', $pager->getPageVariables() );

		break;
}
$smarty->display('payperiod/PayPeriodScheduleList.tpl');
?>