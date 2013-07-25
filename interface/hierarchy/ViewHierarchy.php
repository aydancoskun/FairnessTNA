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
 * $Id: ViewHierarchy.php 4104 2011-01-04 19:04:05Z ipso $
 * $Date: 2011-01-04 11:04:05 -0800 (Tue, 04 Jan 2011) $
 */
require_once('../../includes/global.inc.php');
require_once(Environment::getBasePath() .'includes/Interface.inc.php');

if ( !$permission->Check('hierarchy','enabled')
		OR !( $permission->Check('hierarchy','view') OR $permission->Check('hierarchy','view_own') ) ) {

	$permission->Redirect( FALSE ); //Redirect

}

$smarty->assign('title', TTi18n::gettext($title = 'View Hierarchy')); // See index.php
BreadCrumb::setCrumb($title);

/*
 * Get FORM variables
 */
extract	(FormVariables::GetVariables(
										array	(
												'action',
												'hierarchy_id',
												'id'
												) ) );

switch ($action) {
	default:
		if ( isset($id) ) {

			$hlf = TTnew( 'HierarchyListFactory' );

			$tmp_id = $id;
			$i=0;
			do {
				Debug::Text(' Iteration...', __FILE__, __LINE__, __METHOD__,10);
				$parents = $hlf->getParentLevelIdArrayByHierarchyControlIdAndUserId( $hierarchy_id, $tmp_id);

				$level = $hlf->getFastTreeObject()->getLevel( $tmp_id )-1;

				if ( is_array($parents) AND count($parents) > 0 ) {
					$parent_users = array();
					foreach($parents as $user_id) {
						//Get user information
						$ulf = TTnew( 'UserListFactory' );
						$ulf->getById( $user_id );
						$user = $ulf->getCurrent();
						unset($ulf);

						$parent_users[] = array( 'name' => $user->getFullName() );
						unset($user);
					}

					$parent_groups[] = array( 'users' => $parent_users, 'level' => $level );
					unset($parent_users);
				}

				if ( isset($parents[0]) ) {
					$tmp_id = $parents[0];
				}
				
				$i++;
			} while ( is_array($parents) AND count($parents) > 0 AND $i < 100 );
		}

		$smarty->assign_by_ref('parent_groups', $parent_groups);

		break;
}
$smarty->display('hierarchy/ViewHierarchy.tpl');
?>