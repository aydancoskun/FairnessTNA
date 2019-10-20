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
class InstallSchema_1045A extends InstallSchema_Base {

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

		//Go through each permission group, and enable absence/schedule edit field permissions for anyone who can edit absence/schedules.
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
							$plf->getByCompanyIdAndPermissionControlIdAndSectionAndNameAndValue( $c_obj->getId(), $pc_obj->getId(), 'absence', array('edit', 'edit_own', 'edit_child'), 1 );
							if ( $plf->getRecordCount() > 0 ) {
								Debug::text('Found permission group with Edit Absence enabled: '. $plf->getCurrent()->getValue(), __FILE__, __LINE__, __METHOD__, 9);
								$pc_obj->setPermission(
														array(	'absence' => array(
																					'edit_branch' => TRUE,
																					'edit_department' => TRUE,
																				)
															)
														);
							} else {
								Debug::text('Permission group does NOT have absences enabled...', __FILE__, __LINE__, __METHOD__, 9);
							}

							$plf->getByCompanyIdAndPermissionControlIdAndSectionAndNameAndValue( $c_obj->getId(), $pc_obj->getId(), 'schedule', array('edit', 'edit_own', 'edit_child'), 1 );
							if ( $plf->getRecordCount() > 0 ) {
								Debug::text('Found permission group with Edit Schedule enabled: '. $plf->getCurrent()->getValue(), __FILE__, __LINE__, __METHOD__, 9);
								$pc_obj->setPermission(
														array(	'schedule' => array(
																					'edit_branch' => TRUE,
																					'edit_department' => TRUE,
																					'edit_job' => TRUE,
																					'edit_job_item' => TRUE,
																				)
															)
														);
							} else {
								Debug::text('Permission group does NOT have schedules enabled...', __FILE__, __LINE__, __METHOD__, 9);
							}

						}
					}
				}
			}
		}

		return TRUE;
	}
}
?>
