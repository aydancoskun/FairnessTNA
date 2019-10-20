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
$disable_database_connection = TRUE;
require_once('../../includes/global.inc.php');
forceNoCacheHeaders(); //Send headers to disable caching.
TTi18n::chooseBestLocale();
extract	(FormVariables::GetVariables(
										array	(
												'exception',
												) ) );
$BASE_URL = './';
$META_TITLE = TTi18n::getText('Down For Maintenance');
require ('../../includes/Header.inc.php');
?>

<div id="contentContainer" class="content-container">
	<div class="container">
		<div class="row">
			<div class="col-xs-12">
				<div id="contentBox-DownForMaintenance">
					<div class="textTitle2"><?php echo TTi18n::getText('Down for Maintenance') ?></div>
                    <div id="rowWarning" class="text-center">
						<?php
						if ( DEPLOYMENT_ON_DEMAND == TRUE ) {
							if ( strtolower($exception) == 'dbtimeout' ) {
								echo APPLICATION_NAME . ' ' . TTi18n::getText('database query has timed-out, if you were trying to run a report it may be too large, please narrow your search criteria and try again.');
							} else {
								echo APPLICATION_NAME . ' ' . TTi18n::getText('is currently undergoing maintenance. We\'re sorry for any inconvenience this may cause.');
							}
						} else {
							if ( strtolower($exception) == 'dberror' OR strtolower($exception) == 'dbconnectionfailed' ) {
								echo APPLICATION_NAME . ' ' . TTi18n::getText('is unable to connect to its database, please make sure that the database service on your own local') . ' ' . APPLICATION_NAME . ' ' . TTi18n::getText('server has been started and is running. If you are unsure, try rebooting your server.') ;
							} else if ( strtolower($exception) == 'dbtimeout' ) {
								echo APPLICATION_NAME . ' ' . TTi18n::getText('database query has timed-out, if you were trying to run a report it may be too large, please narrow your search criteria and try again.');
							} else if ( strtolower($exception) == 'dbinitialize' ) {
								echo APPLICATION_NAME . ' ' . TTi18n::getText('database has not been initialized yet, please run the installer again and follow the on screen instructions.') . '<a href="'. Environment::getBaseURL() .'/html5/index.php?installer=1&disable_db=1&external_installer=1#!m=Install&a=license&external_installer=0">' . TTi18n::getText('Click here to run the installer now.') . '</a>';
							} else if ( strtolower($exception) == 'down_for_maintenance' ) {
								echo APPLICATION_NAME . ' ' . TTi18n::getText('is currently undergoing maintenance. We\'re sorry for any inconvenience this may cause.');
							} else {
								echo APPLICATION_NAME . ' ' . TTi18n::getText('experienced a general error, please contact technical support.');
							}
						}
						?>
                        <br>
                        <a href='#' onClick="javascript:history.back()">Try Again?</a>
                    </div>
				</div>
			</div>
		</div>
	</div>
</div>
<?php
require ('../../includes/Footer.inc.php');
?>
