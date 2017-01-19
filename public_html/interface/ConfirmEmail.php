<?php
/*********************************************************************************
 * This file is part of "Fairness", a Payroll and Time Management program.
 * Fairness is Copyright 2013 Aydan Coskun (aydan.ayfer.coskun@gmail.com)
 * Portions of this software are Copyright of T i m e T r e x Software Inc.
 * Fairness is a fork of "T i m e T r e x Workforce Management" Software.
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

require_once('../includes/global.inc.php');

//Debug::setVerbosity( 11 );

$authenticate = FALSE;
require_once(Environment::getBasePath() .'includes/Interface.inc.php');

$smarty->assign('title', TTi18n::gettext('Confirm Email'));

/*
 * Get FORM variables
 */
extract	(FormVariables::GetVariables(
										array	(
												'action',
												'email',
												'email_confirmed',
												'key',
												) ) );

$validator = new Validator();

$action = Misc::findSubmitButton();
Debug::Text('Action: '. $action, __FILE__, __LINE__, __METHOD__, 10);
switch ($action) {
	case 'confirm_email':
		$ulf = TTnew( 'UserListFactory' );
		$ulf->getByEmailIsValidKey( $key );
		if ( $ulf->getRecordCount() == 1 ) {
			Debug::Text('FOUND Email Validation key! Email: '. $email, __FILE__, __LINE__, __METHOD__, 10);

			$valid_key = TRUE;
			
			$ttsc = new FairnessSoapClient();

			$user_obj = $ulf->getCurrent();
			if ( $user_obj->getWorkEmailIsValidKey() == $key AND $user_obj->getWorkEmail() == $email ) {
				$user_obj->setWorkEmailIsValidKey( '' );
				//$user_obj->setWorkEmailIsValidDate( '' ); //Keep date so we know when the address was validated last.
				$user_obj->setWorkEmailIsValid( TRUE );

			} elseif( $user_obj->getHomeEmailIsValidKey() == $key AND $user_obj->getHomeEmail() == $email ) {
				$user_obj->setHomeEmailIsValidKey( '' );
				//$user_obj->setHomeEmailIsValidDate( '' ); //Keep date so we know when the address was validated last.
				$user_obj->setHomeEmailIsValid( TRUE );

			} else {
				$valid_key = FALSE;
			}

			if ( $valid_key == TRUE AND $user_obj->isValid() ) {
				$user_obj->Save(FALSE);
				Debug::Text('Email validation is succesful!', __FILE__, __LINE__, __METHOD__, 10);

				TTLog::addEntry( $user_obj->getId(), 500, TTi18n::gettext('Validated email address').': '. $email, $user_obj->getId(), 'users' );

				Redirect::Page( URLBuilder::getURL( array('email_confirmed' => 1, 'email' => $email ), 'ConfirmEmail.php' ) );
				break;
			} else {
				Debug::Text('aDID NOT FIND email validation key!', __FILE__, __LINE__, __METHOD__, 10);
				$email_confirmed = FALSE;
			}
		} else {
			Debug::Text('bDID NOT FIND email validation key!', __FILE__, __LINE__, __METHOD__, 10);
			$email_confirmed = FALSE;
		}
	default:
		if ( $email_confirmed == FALSE ) {
			//Make sure we don't allow malicious users to use some long email address like:
			//"This is the FBI, you have been fired if you don't..."
			if ( $validator->isEmail( 'email', $email, TTi18n::getText('Invalid confirmation key') ) == FALSE ) {
				$email = NULL;
				//$email_sent = FALSE;
			}
		}

		break;
}

$smarty->assign_by_ref('email', $email);
$smarty->assign_by_ref('email_confirmed', $email_confirmed);
$smarty->assign_by_ref('key', $key);
$smarty->assign_by_ref('action', $action);

$smarty->assign_by_ref('validator', $validator);

$smarty->display('ConfirmEmail.tpl');
?>