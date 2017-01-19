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
 * $Revision: 9761 $
 * $Id: License.php 9761 2013-05-03 23:06:47Z ipso $
 * $Date: 2013-05-03 16:06:47 -0700 (Fri, 03 May 2013) $
 */
$disable_database_connection=TRUE;
require_once('../../includes/global.inc.php');

$authenticate=FALSE;
require_once(Environment::getBasePath() .'includes/Interface.inc.php');

$smarty->assign('title', TTi18n::gettext($title = '1. License Acceptance')); // See index.php

/*
 * Get FORM variables
 */
extract	(FormVariables::GetVariables(
										array	(
												'action',
												'data',
												'external_installer',
												) ) );

$install_obj = new Install();

$action = Misc::findSubmitButton();
switch ($action) {
	case 'start':
		Debug::Text('Start', __FILE__, __LINE__, __METHOD__,10);

		if ( $data['license_accept'] == 1 ) {
			Redirect::Page( URLBuilder::getURL( array('external_installer' => $external_installer ), 'Requirements.php') );
		}

		break;
	default:
		break;
}


$smarty->assign_by_ref('install_obj', $install_obj);
$smarty->assign_by_ref('external_installer', $external_installer);

$smarty->display('install/License.tpl');
?>
