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
class InstallSchema_1024A extends InstallSchema_Base {

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

		//Go through each permission group, and enable payroll export report for anyone who can see pay stub summary report.
		$clf = TTnew( 'CompanyListFactory' ); /** @var CompanyListFactory $clf */
		$clf->getAll();
		if ( $clf->getRecordCount() > 0 ) {
			foreach( $clf as $c_obj ) {
				Debug::text('Company: '. $c_obj->getName(), __FILE__, __LINE__, __METHOD__, 9);
				if ( $c_obj->getStatus() != 30 ) {
					$pclf = TTnew( 'PermissionControlListFactory' ); /** @var PermissionControlListFactory $pclf */
					$pclf->getByCompanyId( $c_obj->getId(), NULL, NULL, NULL, array( 'name' => 'asc' ) ); //Force order to avoid referencing column that was added in a later version (level)
					if ( $pclf->getRecordCount() > 0 ) {
						foreach( $pclf as $pc_obj ) {
							Debug::text('Permission Group: '. $pc_obj->getName(), __FILE__, __LINE__, __METHOD__, 9);
							$plf = TTnew( 'PermissionListFactory' ); /** @var PermissionListFactory $plf */
							$plf->getByCompanyIdAndPermissionControlIdAndSectionAndNameAndValue( $c_obj->getId(), $pc_obj->getId(), 'report', 'view_pay_stub_summary', 1 );
							if ( $plf->getRecordCount() > 0 ) {
								Debug::text('Found permission group with pay stub report enabled: '. $plf->getCurrent()->getValue(), __FILE__, __LINE__, __METHOD__, 9);
								$pc_obj->setPermission( array('report' => array('view_payroll_export' => TRUE) ) );
							} else {
								Debug::text('Permission group does NOT have pay stub report enabled...', __FILE__, __LINE__, __METHOD__, 9);
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
