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
 * $Id: HelpList.php 4104 2011-01-04 19:04:05Z ipso $
 * $Date: 2011-01-04 11:04:05 -0800 (Tue, 04 Jan 2011) $
 */
require_once('../../includes/global.inc.php');
require_once(Environment::getBasePath() .'includes/Interface.inc.php');

if ( !$permission->Check('help','enabled')
		OR !( $permission->Check('help','view') OR $permission->Check('help','view_own')
				OR $permission->Check('help','edit') OR $permission->Check('help','edit_own')) ) {

	$permission->Redirect( FALSE ); //Redirect

}

$smarty->assign('title', TTi18n::gettext($title = 'Help List')); // See index.php
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
												'ids'
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

switch ($action) {
	case 'add':

		Redirect::Page( URLBuilder::getURL(NULL, 'EditHelp.php') );

		break;
	case 'delete' OR 'undelete':
		if ( strtolower($action) == 'delete' ) {
			$delete = TRUE;
		} else {
			$delete = FALSE;
		}

		$hlf = TTnew( 'HelpListFactory' );

		foreach ($ids as $id) {
			$hlf->getById($id );
			foreach ($hlf as $help_obj) {
				$help_obj->setDeleted($delete);
				$help_obj->Save();
			}
		}

		Redirect::Page( URLBuilder::getURL(NULL, 'HelpList.php') );

/*
		$slf = TTnew( 'StationListFactory' );

		foreach ($ids as $id) {
			$slf->GetByIdAndCompanyId($id, $current_company->getId() );
			foreach ($slf as $station) {
				$station->setDeleted($delete);
				$station->Save();
			}
		}

		Redirect::Page( URLBuilder::getURL(NULL, 'StationList.php') );
*/
		break;

	default:
		$hlf = TTnew( 'HelpListFactory' );

		$hlf->getAll($current_user_prefs->getItemsPerPage(), $page, NULL, $sort_array );

		$pager = new Pager($hlf);

		foreach ($hlf as $help_obj) {

			$help[] = array(
								'id' => $help_obj->GetId(),
								'type' => Option::getByKey($help_obj->getType(), $help_obj->getOptions('type') ),
								'status' => Option::getByKey($help_obj->getStatus(), $help_obj->getOptions('status') ),
								'heading' => $help_obj->getHeading(),
								'private' => $help_obj->getPrivate(),
								'deleted' => $help_obj->getDeleted()
							);

		}
		$smarty->assign_by_ref('help_entries', $help);

		$smarty->assign_by_ref('sort_column', $sort_column );
		$smarty->assign_by_ref('sort_order', $sort_order );

		$smarty->assign_by_ref('paging_data', $pager->getPageVariables() );

		break;
}
$smarty->display('help/HelpList.tpl');
?>