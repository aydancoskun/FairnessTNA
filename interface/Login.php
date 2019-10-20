<?php
/*********************************************************************************
 * FairnessTNA is a Workforce Management program forked from TimeTrex in 2013,
 * copyright Aydan Coskun. Original code base is copyright TimeTrex Software Inc.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * You can contact Aydan Coskun via issue tracker on github.com/aydancoskun
 ********************************************************************************/

require_once('../includes/global.inc.php');
$form_vars = FormVariables::GetVariables( array('desktop') );
if ( array_key_exists( 'desktop', $form_vars ) AND $form_vars['desktop'] != 1 ) { //isset() won't work here as 'desktop' key can be NULL
	unset($form_vars['desktop']);
}
Redirect::Page( URLBuilder::getURL( $form_vars, Environment::GetBaseURL().'html5/') );
?>
