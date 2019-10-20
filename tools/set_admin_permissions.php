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

require_once( dirname(__FILE__) . DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'includes'. DIRECTORY_SEPARATOR .'global.inc.php');
require_once( dirname(__FILE__) . DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'includes'. DIRECTORY_SEPARATOR .'CLI.inc.php');

if ( $argc < 2 OR in_array ($argv[1], array('--help', '-help', '-h', '-?') ) ) {
	$help_output = "Usage: set_admin_permissions.php [user_name]\n";
	echo $help_output;
} else {
	//Handle command line arguments
	$last_arg = count($argv)-1;

	if ( isset($argv[$last_arg]) AND $argv[$last_arg] != '' ) {
		$user_name = $argv[$last_arg];
		//Get user_id from user_name
		$ulf = new UserListFactory();
		$ulf->getByUserName( $user_name );
		if ( $ulf->getRecordCount() == 1 ) {
			echo "Found user, apply administrator permissions...\n";
			ob_flush();

			$u_obj = $ulf->getCurrent();
			//Create new Permission Group just for this purpose.
			$pf = new PermissionFactory();
			$pf->StartTransaction();

			//Apply all preset flags, including 0 => "system"
			$preset_flags = array_merge( array( 0 ), array_keys( $pf->getOptions('preset_flags') ) );

			$pcf = new PermissionControlFactory();
			$pcf->setCompany( $u_obj->getCompany() );
			$pcf->setLevel( 25 );
			$pcf->setName( 'Administrator Fix ('.rand(1,1000).')' );
			$pcf->setDescription( 'Created By set_admin_permissions.php' );
			if ( $pcf->isValid() ) {
				$pcf_id = $pcf->Save(FALSE);

				$pcf->setUser( array( $u_obj->getId() ) );

				$pcf->Save();

				if ( $pf->applyPreset($pcf_id, 40, $preset_flags ) == TRUE ) {
					echo "Success!\n";
				}
			}
			//$pf->FailTransaction();
			$pf->CommitTransaction();
		} elseif ( $ulf->getRecordCount() > 2 ) {
			echo "Found more then one user with the same user name, not updating permissions!\n";
		} else {
			echo "User name not found!\n";
		}
	}
}

//Debug::Display();
Debug::writeToLog();
?>
