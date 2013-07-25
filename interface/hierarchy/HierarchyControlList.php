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
 * $Id: HierarchyControlList.php 4104 2011-01-04 19:04:05Z ipso $
 * $Date: 2011-01-04 11:04:05 -0800 (Tue, 04 Jan 2011) $
 */
require_once('../../includes/global.inc.php');
require_once(Environment::getBasePath() .'includes/Interface.inc.php');

if ( !$permission->Check('hierarchy','enabled')
		OR !( $permission->Check('hierarchy','view') OR $permission->Check('hierarchy','view_own') ) ) {

	$permission->Redirect( FALSE ); //Redirect

}

$smarty->assign('title', TTi18n::gettext($title = 'Hierarchy List')); // See index.php
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
												'id'
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


//$ppslf = TTnew( 'PayPeriodScheduleFactory' );

Debug::Arr($ids,'Selected Objects', __FILE__, __LINE__, __METHOD__,10);

$action = Misc::findSubmitButton();
switch ($action) {
	case 'add':

		Redirect::Page( URLBuilder::getURL(NULL, 'EditHierarchyControl.php', FALSE) );

		break;
	case 'delete' OR 'undelete':
		if ( strtolower($action) == 'delete' ) {
			$delete = TRUE;
		} else {
			$delete = FALSE;
		}

		$hclf = TTnew( 'HierarchyControlListFactory' );

		foreach ($ids as $id) {
			//$dsclf->GetByIdAndUserId($id, $current_user->getId() );
			$hclf->GetById($id);
			foreach ($hclf as $hierarchy_control) {
				$hierarchy_control->setDeleted($delete);
				$hierarchy_control->Save();
			}
		}

		Redirect::Page( URLBuilder::getURL(NULL, 'HierarchyControlList.php') );

		break;

	default:
		$hclf = TTnew( 'HierarchyControlListFactory' );
		$hclf->getByCompanyId($current_company->getId(), $current_user_prefs->getItemsPerPage(), $page, NULL, $sort_array );

		$pager = new Pager($hclf);

		$hotf = TTnew( 'HierarchyObjectTypeFactory' );
		$object_type_options = $hotf->getOptions('object_type');

		foreach ($hclf as $hierarchy_control) {
			$object_type_ids = $hierarchy_control->getObjectType();

			$object_types = array();
			foreach($object_type_ids as $object_type_id) {
				if ( isset($object_type_options[$object_type_id]) ) {
					$object_types[] = $object_type_options[$object_type_id];
				}
			}

			$hierarchy_controls[] = array(
				'id' => $hierarchy_control->getId(),
				'name' => $hierarchy_control->getName(),
				'description' => $hierarchy_control->getDescription(),
				'object_types' => $object_types,
				'deleted' => $hierarchy_control->getDeleted()
				);

			unset($object_types);
		}

		$smarty->assign_by_ref('hierarchy_controls', $hierarchy_controls);

		$smarty->assign_by_ref('sort_column', $sort_column );
		$smarty->assign_by_ref('sort_order', $sort_order );

		$smarty->assign_by_ref('paging_data', $pager->getPageVariables() );

		break;
}
$smarty->display('hierarchy/HierarchyControlList.tpl');
?>