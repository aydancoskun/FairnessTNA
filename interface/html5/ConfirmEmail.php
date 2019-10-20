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
require_once('../../includes/global.inc.php');
forceNoCacheHeaders(); //Send headers to disable caching.
TTi18n::chooseBestLocale();
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
		$ulf = TTnew( 'UserListFactory' ); /** @var UserListFactory $ulf */
		$ulf->getByEmailIsValidKey( $key );
		if ( $ulf->getRecordCount() == 1 ) {
			Debug::Text('FOUND Email Validation key! Email: '. $email, __FILE__, __LINE__, __METHOD__, 10);

			$valid_key = TRUE;

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

				Redirect::Page( URLBuilder::getURL( array('email_confirmed' => 1, 'email' => $email ), Environment::getBaseURL().'html5/ConfirmEmail.php' ) );
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
		//Make sure we don't allow malicious users to use some long email address like:
		//"This is the FBI, you have been fired if you don't..."
		if ( $validator->isEmail( 'email', $email, TTi18n::getText('Invalid confirmation key') ) == FALSE ) {
			$email = NULL;
			$email_confirmed = FALSE;
		}

		break;
}
$BASE_URL = './';
$META_TITLE = TTi18n::getText('Confirm Email');
require ('../../includes/Header.inc.php');
?>
<div id="contentContainer" class="content-container">
	<div class="container">
		<div class="row">
			<div class="col-xs-12">
				<div id="contentBox-ConfirmEmail">
					<div class="textTitle2"><?php echo TTi18n::getText('Email Address Confirmed') ?></div>
					<?php if ( $email_confirmed == TRUE ) { ?>
						<div id="rowWarning" class="text-center">
							<?php echo TTi18n::getText('Email address') . ' <b>'. $email .'</b> ' . TTi18n::getText('has been confirmed and activated.') ?>
						</div>
					<?php } else if ( $email_confirmed == FALSE ) { ?>
						<div id="rowWarning" valign="center">
							<?php echo TTi18n::getText('Invalid or expired confirmation key, please try again.') ?>
						</div>
					<?php } ?>
				</div>
			</div>
		</div>
	</div>
</div>
<?php
require ('../../includes/Footer.inc.php');
?>
