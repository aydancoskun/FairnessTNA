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
class InstallSchema_1046A extends InstallSchema_Base {

	/**
	 * @return bool
	 */
	function preInstall() {
		Debug::text('preInstall: '. $this->getVersion(), __FILE__, __LINE__, __METHOD__, 9);

		return TRUE;
	}

	/**
	 * @return bool
	 */
	function postInstall() {
		Debug::text('postInstall: '. $this->getVersion(), __FILE__, __LINE__, __METHOD__, 9);

		//Allow edit password/phone password permissions for all permission groups.
		$clf = TTnew( 'CompanyListFactory' ); /** @var CompanyListFactory $clf */
		$clf->getAll();
		if ( $clf->getRecordCount() > 0 ) {
			foreach( $clf as $c_obj ) {
				Debug::text('Company: '. $c_obj->getName(), __FILE__, __LINE__, __METHOD__, 9);
				if ( $c_obj->getStatus() != 30 ) {
					$pclf = TTnew( 'PermissionControlListFactory' ); /** @var PermissionControlListFactory $pclf */
					$pclf->getByCompanyId( $c_obj->getId(), NULL, NULL, NULL, array( 'name' => 'asc' ) ); //Force order to prevent references to columns that haven't been created yet.
					if ( $pclf->getRecordCount() > 0 ) {
						foreach( $pclf as $pc_obj ) {
							Debug::text('Permission Group: '. $pc_obj->getName(), __FILE__, __LINE__, __METHOD__, 9);
							$plf = TTnew( 'PermissionListFactory' ); /** @var PermissionListFactory $plf */
							$plf->getByCompanyIdAndPermissionControlIdAndSectionAndNameAndValue( $c_obj->getId(), $pc_obj->getId(), 'user', 'edit_own', 1 );
							if ( $plf->getRecordCount() > 0 ) {
								Debug::text('Found permission group with user edit own enabled: '. $plf->getCurrent()->getValue(), __FILE__, __LINE__, __METHOD__, 9);
								$pc_obj->setPermission( array('user' => array('edit_own_password' => TRUE, 'edit_own_phone_password' => TRUE) ) );
							} else {
								Debug::text('Permission group does NOT have user edit own report enabled...', __FILE__, __LINE__, __METHOD__, 9);
							}
						}
					}

				}
			}
		}

		//Metaphoneize data
		$ulf = TTnew('UserListFactory'); /** @var UserListFactory $ulf */
		$ulf->getAll();
		if ( $ulf->getRecordCount() > 0 ) {
			foreach( $ulf as $u_obj ) {

				$ph = array(
							'first_name_metaphone' => $u_obj->getFirstNameMetaphone( $u_obj->setFirstNameMetaphone( $u_obj->getFirstName() ) ),
							'last_name_metaphone' => $u_obj->getLastNameMetaphone( $u_obj->setLastNameMetaphone( $u_obj->getLastName() ) ),
							'id' => TTUUID::castUUID($u_obj->getId()),
							);
				$query = 'update '. $ulf->getTable() .' set first_name_metaphone = ?, last_name_metaphone = ? where id = ?';
				$this->db->Execute( $query, $ph );
			}
		}

		$clf = TTnew('CompanyListFactory'); /** @var CompanyListFactory $clf */
		$clf->getAll();
		if ( $clf->getRecordCount() > 0 ) {
			foreach( $clf as $c_obj ) {

				$ph = array(
							'name_metaphone' => $c_obj->getNameMetaphone( $c_obj->setNameMetaphone( $c_obj->getName() ) ),
							'id' => TTUUID::castUUID($c_obj->getId()),
							);
				$query = 'update '. $clf->getTable() .' set name_metaphone = ? where id = ?';
				$this->db->Execute( $query, $ph );
			}
		}

		$blf = TTnew('BranchListFactory'); /** @var BranchListFactory $blf */
		$blf->getAll();
		if ( $blf->getRecordCount() > 0 ) {
			foreach( $blf as $b_obj ) {

				$ph = array(
							'name_metaphone' => $b_obj->getNameMetaphone( $b_obj->setNameMetaphone( $b_obj->getName() ) ),
							'id' => TTUUID::castUUID($b_obj->getId()),
							);
				$query = 'update '. $blf->getTable() .' set name_metaphone = ? where id = ?';
				$this->db->Execute( $query, $ph );
			}
		}

		$dlf = TTnew('DepartmentListFactory'); /** @var DepartmentListFactory $dlf */
		$dlf->getAll();
		if ( $dlf->getRecordCount() > 0 ) {
			foreach( $dlf as $d_obj ) {

				$ph = array(
							'name_metaphone' => $d_obj->getNameMetaphone( $d_obj->setNameMetaphone( $d_obj->getName() ) ),
							'id' => TTUUID::castUUID($d_obj->getId()),
							);
				$query = 'update '. $dlf->getTable() .' set name_metaphone = ? where id = ?';
				$this->db->Execute( $query, $ph );
			}
		}


		//Add GeoCode cronjob to database to run every morning.
		$cjf = TTnew( 'CronJobFactory' ); /** @var CronJobFactory $cjf */
		$cjf->setName('GeoCode');
		$cjf->setMinute('15');
		$cjf->setHour('2');
		$cjf->setDayOfMonth('*');
		$cjf->setMonth('*');
		$cjf->setDayOfWeek('*');
		$cjf->setCommand('GeoCode.php');
		$cjf->Save();

		return TRUE;
	}
}
?>
