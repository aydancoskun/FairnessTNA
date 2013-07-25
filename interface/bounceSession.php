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
 * $Revision: 9128 $
 * $Id: Login.php 9128 2013-02-15 19:36:46Z ipso $
 * $Date: 2013-02-15 11:36:46 -0800 (Fri, 15 Feb 2013) $
 */
require_once('../includes/global.inc.php');

/*
 * Get FORM variables
 */
extract	(FormVariables::GetVariables(
										array	(
												'name',
												'value',
												'expires',
												'redirect',
												'key'
												) ) );

//Used to help set cookies across domains.
$authentication = new Authentication();
if ( $name == '' ) {
	$name = $authentication->getName();
}

if ( $expires == '' ) {
	$expires = time()+7776000;
}

setcookie( $name, $value, $expires, '/', NULL, $authentication->isSSL() );

if ( $redirect != '' ) {
	Redirect::Page( $redirect );
}
?>