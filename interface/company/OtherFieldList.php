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
 * $Id: OtherFieldList.php 4104 2011-01-04 19:04:05Z ipso $
 * $Date: 2011-01-04 11:04:05 -0800 (Tue, 04 Jan 2011) $
 */
require_once('../../includes/global.inc.php');
require_once(Environment::getBasePath() .'includes/Interface.inc.php');

if ( !$permission->Check('other_field','enabled')
		OR !( $permission->Check('other_field','view') OR $permission->Check('other_field','view_own') ) ) {

	$permission->Redirect( FALSE ); //Redirect
}

$smarty->assign('title', TTi18n::gettext($title = 'Other Field List')); // See index.php
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
												'type_id'
												) ) );

URLBuilder::setURL($_SERVER['SCRIPT_NAME'],
											array(
													'type_id' => $type_id,
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
	case 'add':

		Redirect::Page( URLBuilder::getURL(array('type_id' => $type_id), 'EditOtherField.php', FALSE) );

		break;
	case 'delete' OR 'undelete':
		if ( strtolower($action) == 'delete' ) {
			$delete = TRUE;
		} else {
			$delete = FALSE;
		}

		$oflf = TTnew( 'OtherFieldListFactory' );

		foreach ($ids as $id) {
			$oflf->getByIdAndCompanyId($id, $current_company->getId() );
			foreach ($oflf as $of_obj) {
				$of_obj->setDeleted($delete);
				$of_obj->Save();
			}
		}

		Redirect::Page( URLBuilder::getURL(array('type_id' => $type_id), 'OtherFieldList.php') );

		break;

	default:
		$oflf = TTnew( 'OtherFieldListFactory' );

		$oflf->getByCompanyId($current_company->getId(), $current_user_prefs->getItemsPerPage(), $page, NULL, $sort_array );

		$pager = new Pager($oflf);

		//Get types
		$off = TTnew( 'OtherFieldFactory' );
		$type_options = $off->getOptions('type');

		foreach ($oflf as $obj) {

			$rows[] = array(
								'id' => $obj->getId(),
								'type_id' => $obj->getType(),
								'type' => $type_options[$obj->getType()],
								'other_id1' => $obj->getOtherID1(),
								'other_id2' => $obj->getOtherID2(),
								'other_id3' => $obj->getOtherID3(),
								'other_id4' => $obj->getOtherID4(),
								'other_id5' => $obj->getOtherID5(),
//								'user_id' => $wage->getUser(),
//								'type' => Option::getByKey($wage->getType(), $wage->getOptions('type') ),
								'deleted' => $obj->getDeleted()
							);

		}
		$smarty->assign_by_ref('rows', $rows);
		$smarty->assign_by_ref('type_id', $type_id );

		$smarty->assign_by_ref('sort_column', $sort_column );
		$smarty->assign_by_ref('sort_order', $sort_order );

		$smarty->assign_by_ref('paging_data', $pager->getPageVariables() );

		break;
}
$smarty->display('company/OtherFieldList.tpl');
?>