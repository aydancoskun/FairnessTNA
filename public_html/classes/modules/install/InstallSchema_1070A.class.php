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


/**
 * @package Module_Install
 */
class InstallSchema_1070A extends InstallSchema_Base {

	function preInstall() {
		Debug::text('preInstall: '. $this->getVersion(), __FILE__, __LINE__, __METHOD__, 9);

		return TRUE;
	}

	function postInstall() {
		Debug::text('postInstall: '. $this->getVersion(), __FILE__, __LINE__, __METHOD__, 9);

		//Loop through all contributing shift policies switch include_shift_type_id from boolean to integer.
		$csplf = new ContributingShiftPolicyListFactory();
		$csplf->getAll();
		if ( $csplf->getRecordCount() > 0 ) {
			Debug::text( 'ContributingShiftPolicies: '. $csplf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 9 );
			foreach( $csplf as $csp_obj ) {
				$previous_shift_type_id = (int)$csp_obj->getIncludeShiftType();
				if ( $previous_shift_type_id === 0 ) {
					$csp_obj->setIncludeShiftType( 200 ); //Full Shift (this was the default before)
				} else {
					$csp_obj->setIncludeShiftType( 100 ); //Partial Shift
				}

				Debug::text(' IncludeShiftType ID: Previous: '. $previous_shift_type_id .' New: '. (int)$csp_obj->getIncludeShiftType(), __FILE__, __LINE__, __METHOD__, 9);
				if ( $csp_obj->isValid() ) {
					$csp_obj->Save();
				}
			}
		}

		//Handle new permissions.
		$clf = TTNew('CompanyListFactory');
		$clf->getAll();
		if ( $clf->getRecordCount() > 0 ) {
			$x = 0;
			foreach ( $clf as $company_obj ) {
				Debug::text( 'Company: ' . $company_obj->getName() . ' X: ' . $x . ' of :' . $clf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 9 );

				//Add: "Regular Employee (Manual TimeSheet)

				//Go through each permission group, and rename "Regular Employee (Manual Entry)" to "Regular Employee (Manual Punch)" -- that can punch in/out manually.
				$pclf = new PermissionControlListFactory;
				$pclf->getByCompanyId( $company_obj->getId(), NULL, NULL, NULL, array('name' => 'asc') ); //Force order to prevent references to columns that haven't been created yet.
				if ( $pclf->getRecordCount() > 0 ) {
					foreach ( $pclf as $pc_obj ) {
						Debug::text( 'Permission Group: '. $pc_obj->getName(), __FILE__, __LINE__, __METHOD__, 9 );
						if ( stripos( $pc_obj->getName(), 'Manual Entry' ) ) {
							$pc_obj->setName( str_ireplace('Manual Entry', 'Manual Punch', $pc_obj->getName() ) );
							Debug::text( '  Renaming Permission Group to: ' . $pc_obj->getName(), __FILE__, __LINE__, __METHOD__, 9 );
						}

						//Add punch_timesheet to all existing permission groups.
						$plf = TTnew( 'PermissionListFactory' );
						$plf->getByCompanyIdAndPermissionControlIdAndSectionAndNameAndValue( $company_obj->getId(), $pc_obj->getId(), 'punch', 'add', 1 ); //Only return records where permission is ALLOWED.
						if ( $plf->getRecordCount() > 0 ) {
							Debug::text( '  Found permission group with punch,add, add punch_timesheet: ' . $plf->getCurrent()->getValue(), __FILE__, __LINE__, __METHOD__, 9 );
							$pc_obj->setPermission(
									array(
											'punch' => array('punch_timesheet' => TRUE),
									)
							);
						} else {
							Debug::text( '  Permission group does NOT have punch,add enabled...', __FILE__, __LINE__, __METHOD__, 9 );
						}
					}
				}
				unset( $pclf, $plf, $pc_obj );
				$x++;
			}
		}

		return TRUE;
	}
}
?>
