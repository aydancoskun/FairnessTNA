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
 * $Revision: 8243 $
 * $Id: ForgotPassword.php 8243 2012-11-08 17:00:03Z ipso $
 * $Date: 2012-11-08 09:00:03 -0800 (Thu, 08 Nov 2012) $
 */
require_once('../includes/global.inc.php');

//Debug::setVerbosity( 11 );

$authenticate=FALSE;
require_once(Environment::getBasePath() .'includes/Interface.inc.php');

$smarty->assign('title', TTi18n::gettext('Password Reset'));

/*
 * Get FORM variables
 */
extract	(FormVariables::GetVariables(
										array	(
												'action',
												'email',
												'key',
												'email_sent',
												'password',
												'password2',
												) ) );

$validator = new Validator();

$action = Misc::findSubmitButton();
Debug::Text('Action: '. $action, __FILE__, __LINE__, __METHOD__,10);
switch ($action) {
	case 'change_password':
		Debug::Text('Change Password: '. $key, __FILE__, __LINE__, __METHOD__,10);
		$ulf = TTnew( 'UserListFactory' );
		$ulf->getByPasswordResetKey( $key );
		if ( $ulf->getRecordCount() == 1 ) {
			Debug::Text('FOUND Password reset key! ', __FILE__, __LINE__, __METHOD__,10);

			$user_obj = $ulf->getCurrent();
			$user_name = $user_obj->getUserName();

			//Make sure passwords match
			if ( $password == $password2 ) {
				//Change password
				$user_obj->setPassword( $password );
				$user_obj->setPasswordResetKey('');
				$user_obj->setPasswordResetDate('');
				if ( $user_obj->isValid() ) {
					$user_obj->Save();

					Debug::Text('Password Change succesful!', __FILE__, __LINE__, __METHOD__,10);

					Redirect::Page( URLBuilder::getURL( array('password_reset' => 1 ), 'Login.php' ) );
				}

			} else {

				$validator->isTrue('password',FALSE, TTi18n::getText('Passwords do not match') );
			}

		} else {
			Debug::Text('DID NOT FIND Password reset key! ', __FILE__, __LINE__, __METHOD__,10);
			$action = 'reset_password';
		}

		break;
	case 'password_reset':
		//Debug::setVerbosity( 11 );
		Debug::Text('Key: '. $key, __FILE__, __LINE__, __METHOD__,10);
		$ulf = TTnew( 'UserListFactory' );
		$ulf->getByPasswordResetKey( $key );
		if ( $ulf->getRecordCount() == 1 ) {
			Debug::Text('FOUND Password reset key! ', __FILE__, __LINE__, __METHOD__,10);
			$user_obj = $ulf->getCurrent();

			$user_name = $user_obj->getUserName();

		} else {
			Debug::Text('DID NOT FIND Password reset key! ', __FILE__, __LINE__, __METHOD__,10);
			$action = 'reset_password';
		}

		break;
	case 'reset_password':
		//Debug::setVerbosity( 11 );
		Debug::Text('Email: '. $email, __FILE__, __LINE__, __METHOD__,10);

		$ulf = TTnew( 'UserListFactory' );
		$ulf->getByHomeEmailOrWorkEmail( $email );
		if ( $ulf->getRecordCount() == 1 ) {
			$user_obj = $ulf->getCurrent();

			if ( $user_obj->getStatus() == 10 ) { //Only allow password resets on active employees.
				//Check if company is using LDAP authentication, if so deny password reset.
				if ( $user_obj->getCompanyObject()->getLDAPAuthenticationType() == 0 ) {
					$user_obj->sendPasswordResetEmail();
					Debug::Text('Found USER! ', __FILE__, __LINE__, __METHOD__,10);

					Redirect::Page( URLBuilder::getURL( array('email_sent' => 1, 'email' => $email ), 'ForgotPassword.php' ) );
				} else {
					Debug::Text('LDAP Authentication is enabled, password reset is disabled! ', __FILE__, __LINE__, __METHOD__,10);
					$validator->isTrue('email', FALSE, TTi18n::getText('Please contact your administrator for instructions on changing your password.'). ' (LDAP)' );
				}
			} else {
				$validator->isTrue('email', FALSE, TTi18n::getText('Email address was not found in our database (b)') );
			}
		} else {
			//Error
			Debug::Text('DID NOT FIND USER! Returned: '. $ulf->getRecordCount(), __FILE__, __LINE__, __METHOD__,10);
			$validator->isTrue('email', FALSE, TTi18n::getText('Email address was not found in our database (a)') );
		}
		break;
	default:
		break;
}

$smarty->assign_by_ref('email', $email);
$smarty->assign_by_ref('email_sent', $email_sent);
$smarty->assign_by_ref('key', $key);
$smarty->assign_by_ref('user_name', $user_name);
$smarty->assign_by_ref('action', $action);

$smarty->assign_by_ref('validator', $validator);

$smarty->display('ForgotPassword.tpl');
?>