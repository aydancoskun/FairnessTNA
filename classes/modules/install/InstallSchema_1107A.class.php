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


/**
 * @package Modules\Install
 */
class InstallSchema_1107A extends InstallSchema_Base {

	/**
	 * @return bool
	 */
	function preInstall() {
		Debug::text( 'preInstall: ' . $this->getVersion(), __FILE__, __LINE__, __METHOD__, 9 );

		return TRUE;
	}

	/**
	 * @return bool
	 */
	function postInstall() {
		Debug::text( 'postInstall: ' . $this->getVersion(), __FILE__, __LINE__, __METHOD__, 9 );

		//We changed the API and UI to always check the system -> login permission before allowing a user to login. It turns out quite a few customers removed this permission when customizing their permission groups.
		// So make sure we go through and add it back to all permission groups to prevent locking them out.

		$clf = TTnew( 'CompanyListFactory' ); /** @var CompanyListFactory $clf */
		$clf->StartTransaction();
		$clf->getAll( NULL, NULL, NULL, array('created_date' => 'asc') );
		Debug::Text( 'Get all companies. Found: ' . $clf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10 );
		if ( $clf->getRecordCount() > 0 ) {
			foreach ( $clf as $cf ) {
				Debug::text( 'Processing company: ' . $cf->getId() . ' Name: ' . $cf->getName(), __FILE__, __LINE__, __METHOD__, 9 );

				//Make sure system -> login permissions are allowed.
				$pclf = TTnew( 'PermissionControlListFactory' ); /** @var PermissionControlListFactory $pclf */
				$pclf->getByCompanyId( $cf->getId(), NULL, NULL, NULL, array('name' => 'asc') ); //Force order to prevent references to columns that haven't been created yet.
				if ( $pclf->getRecordCount() > 0 ) {
					foreach ( $pclf as $pc_obj ) {
						$plf = TTnew( 'PermissionListFactory' );
						/** @var PermissionListFactory $plf */
						$plf->getByCompanyIdAndPermissionControlIdAndSectionAndNameAndValue( $cf->getId(), $pc_obj->getId(), 'system', 'login', 1 ); //Only return records where permission is ALLOWED.
						if ( $plf->getRecordCount() == 0 ) {
							Debug::text( '  Permission Group: ' . $pc_obj->getName(), __FILE__, __LINE__, __METHOD__, 9 );
							Debug::text( '    Found permission group WITHOUT system -> login allowed, add enabled: ' . $plf->getCurrent()->getValue(), __FILE__, __LINE__, __METHOD__, 9 );
							$pc_obj->setPermission( array('system' => array('login' => TRUE)) );
						}
					}
				}
			}
		}

//		$clf->FailTransaction(); return FALSE; //FOR DEBUG DO NOT COMMIT THIS UNLESS IT IS COMMENTED OUT!!!!
		$clf->CommitTransaction();
		unset( $clf );

		return TRUE;
	}
}
?>
