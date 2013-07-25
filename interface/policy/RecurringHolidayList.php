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
 * $Id: RecurringHolidayList.php 4104 2011-01-04 19:04:05Z ipso $
 * $Date: 2011-01-04 11:04:05 -0800 (Tue, 04 Jan 2011) $
 */
require_once('../../includes/global.inc.php');
require_once(Environment::getBasePath() .'includes/Interface.inc.php');

if ( !$permission->Check('holiday_policy','enabled')
		OR !( $permission->Check('holiday_policy','view') OR $permission->Check('holiday_policy','view_own') ) ) {

	$permission->Redirect( FALSE ); //Redirect

}

$smarty->assign('title', TTi18n::gettext($title = 'Recurring Holiday List')); // See index.php
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

$sort_array = NULL;
if ( $sort_column != '' ) {
	$sort_array = array($sort_column => $sort_order);
}

Debug::Arr($ids,'Selected Objects', __FILE__, __LINE__, __METHOD__,10);

$action = Misc::findSubmitButton();
switch ($action) {
	case 'add_presets':
		//Debug::setVerbosity(11);
		RecurringHolidayFactory::addPresets( $current_company->getId(), $current_company->getCountry() );

		Redirect::Page( URLBuilder::getURL( NULL, 'RecurringHolidayList.php') );
	case 'add':

		Redirect::Page( URLBuilder::getURL( NULL, 'EditRecurringHoliday.php', FALSE) );

		break;
	case 'delete' OR 'undelete':
		if ( strtolower($action) == 'delete' ) {
			$delete = TRUE;
		} else {
			$delete = FALSE;
		}

		$rhlf = TTnew( 'RecurringHolidayListFactory' );

		foreach ($ids as $id) {
			$rhlf->getByIdAndCompanyId($id, $current_company->getId() );
			foreach ($rhlf as $rh_obj) {
				$rh_obj->setDeleted($delete);
				if ( $rh_obj->isValid() ) {
					$rh_obj->Save();
				}
			}
		}

		Redirect::Page( URLBuilder::getURL( NULL, 'RecurringHolidayList.php') );

		break;

	default:
		$rhlf = TTnew( 'RecurringHolidayListFactory' );
		$rhlf->getByCompanyId( $current_company->getId(), $current_user_prefs->getItemsPerPage(), $page, NULL, $sort_array );


		$pager = new Pager($rhlf);

		//$type_options = $aplf->getOptions('type');

		foreach ($rhlf as $rh_obj) {

			$rows[] = array(
								'id' => $rh_obj->getId(),
								'name' => $rh_obj->getName(),
								'next_date' => $rh_obj->getNextDate( time() ),
								'deleted' => $rh_obj->getDeleted()
							);
		}
		
		//Special sorting since next_date is calculated outside of the DB.
		if ( $sort_column == 'next_date' ) {
			Debug::Text('Sort By Date!', __FILE__, __LINE__, __METHOD__,10);
			$rows = Sort::Multisort($rows, $sort_column, NULL, $sort_order);
		}
		
		$smarty->assign_by_ref('rows', $rows);

		$smarty->assign_by_ref('sort_column', $sort_column );
		$smarty->assign_by_ref('sort_order', $sort_order );

		$smarty->assign_by_ref('paging_data', $pager->getPageVariables() );

		break;
}
$smarty->display('policy/RecurringHolidayList.tpl');
?>