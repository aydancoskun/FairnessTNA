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
 * @package Module_Install
 */
class InstallSchema_1070A extends InstallSchema_Base {

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
		$clf = TTNew('CompanyListFactory'); /** @var CompanyListFactory $clf */
		$clf->getAll();
		if ( $clf->getRecordCount() > 0 ) {
			$x = 0;
			foreach ( $clf as $company_obj ) {
				Debug::text( 'Company: ' . $company_obj->getName() . ' X: ' . $x . ' of :' . $clf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 9 );

				//Add: "Regular Employee (Manual TimeSheet)
				if ( $company_obj->getProductEdition() >= TT_PRODUCT_PROFESSIONAL ) {
					Debug::text( '  Add Regular Employee (Manual TimeSheet) permission group: ', __FILE__, __LINE__, __METHOD__, 9 );
					$pf = new PermissionFactory;

					$preset_flags = array_keys( $pf->getOptions('preset_flags') );
					$preset_options = $pf->getOptions('preset');
					$preset_level_options = $pf->getOptions('preset_level');

					$pcf = TTnew( 'PermissionControlFactory' ); /** @var PermissionControlFactory $pcf */
					$pcf->setCompany( $company_obj->getID() ); //Regular Employee (Manual TimeSheet)
					$pcf->setName( $preset_options[14] );
					$pcf->setDescription( '' );
					$pcf->setLevel( $preset_level_options[14] );
					if ( $pcf->isValid() ) {
						$pcf_id = $pcf->Save(FALSE);
						$pf->applyPreset($pcf_id, 14, $preset_flags );
					}
					unset($preset_flags, $preset_options, $preset_level_options, $pcf, $pf, $pcf_id);
				}

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
						$plf = TTnew( 'PermissionListFactory' ); /** @var PermissionListFactory $plf */
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

						if ( $company_obj->getProductEdition() >= TT_PRODUCT_PROFESSIONAL ) {
							//Add manual_timesheet to all existing permission groups. Since in theory any regular employee could have manual timesheet mode enabled, all supervisors need to have it enabled too.
							$plf = TTnew( 'PermissionListFactory' ); /** @var PermissionListFactory $plf */
							$plf->getByCompanyIdAndPermissionControlIdAndSectionAndNameAndValue( $company_obj->getId(), $pc_obj->getId(), 'punch', 'edit_child', 1 ); //Only return records where permission is ALLOWED.
							if ( $plf->getRecordCount() > 0 ) {
								Debug::text( '  Found permission group with punch,edit_child, adding manual_timesheet: ' . $plf->getCurrent()->getValue(), __FILE__, __LINE__, __METHOD__, 9 );
								$pc_obj->setPermission(
										array(
												'punch' => array('manual_timesheet' => TRUE),
										)
								);
							} else {
								Debug::text( '  Permission group does NOT have punch,edit_child enabled...', __FILE__, __LINE__, __METHOD__, 9 );
							}
						}

						if ( $company_obj->getProductEdition() >= TT_PRODUCT_PROFESSIONAL ) {
							//Add request,add_advanced to all existing permission groups by default.
							$plf = TTnew( 'PermissionListFactory' ); /** @var PermissionListFactory $plf */
							$plf->getByCompanyIdAndPermissionControlIdAndSectionAndNameAndValue( $company_obj->getId(), $pc_obj->getId(), 'request', 'add', 1 ); //Only return records where permission is ALLOWED.
							if ( $plf->getRecordCount() > 0 ) {
								Debug::text( '  Found permission group with request,add, adding add_advanced: ' . $plf->getCurrent()->getValue(), __FILE__, __LINE__, __METHOD__, 9 );
								$pc_obj->setPermission(
										array(
												'request' => array('add_advanced' => TRUE),
										)
								);
							} else {
								Debug::text( '  Permission group does NOT have request,add enabled...', __FILE__, __LINE__, __METHOD__, 9 );
							}

							//
							//Add government_document,view_own to all existing permission groups by default.
							//
							$plf = TTnew( 'PermissionListFactory' ); /** @var PermissionListFactory $plf */
							$plf->getByCompanyIdAndPermissionControlIdAndSectionAndNameAndValue( $company_obj->getId(), $pc_obj->getId(), 'pay_stub', 'view_own', 1 ); //Only return records where permission is ALLOWED.
							if ( $plf->getRecordCount() > 0 ) {
								Debug::text( '  Found permission group with request,add, adding add_advanced: ' . $plf->getCurrent()->getValue(), __FILE__, __LINE__, __METHOD__, 9 );
								$pc_obj->setPermission(
										array(
												'government_document' => array('enabled' => TRUE, 'view_own' => TRUE),
										)
								);
							} else {
								Debug::text( '  Permission group does NOT have request,add enabled...', __FILE__, __LINE__, __METHOD__, 9 );
							}

							$plf = TTnew( 'PermissionListFactory' ); /** @var PermissionListFactory $plf */
							$plf->getByCompanyIdAndPermissionControlIdAndSectionAndNameAndValue( $company_obj->getId(), $pc_obj->getId(), 'pay_stub', array( 'add', 'edit', 'delete'), 1 ); //Only return records where permission is ALLOWED.
							if ( $plf->getRecordCount() > 0 ) {
								Debug::text( '  Found permission group with request,add, adding add_advanced: ' . $plf->getCurrent()->getValue(), __FILE__, __LINE__, __METHOD__, 9 );
								$pc_obj->setPermission(
										array(
												'government_document' => array('enabled' => TRUE, 'view' => TRUE, 'add' => TRUE, 'edit' => TRUE, 'delete' => TRUE),
										)
								);
							} else {
								Debug::text( '  Permission group does NOT have request,add enabled...', __FILE__, __LINE__, __METHOD__, 9 );
							}
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
