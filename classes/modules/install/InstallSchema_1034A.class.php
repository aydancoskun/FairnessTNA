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
class InstallSchema_1034A extends InstallSchema_Base {

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

		//Go through all employee wages and update HourlyRate to the accurate annual hourly rate.
		//Take into account wage entries that don't have the proper effective date based on the employees hire date, force a correct effective_date.
		$uwlf = TTnew( 'UserWageListFactory' ); /** @var UserWageListFactory $uwlf */
		$uwlf->getAll();
		if ( $uwlf->getRecordCount() > 0 ) {
			foreach( $uwlf as $uw_obj ) {
				$uw_obj->setHourlyRate( $uw_obj->calcHourlyRate( time(), TRUE ) );
				if ( $uw_obj->getWageGroup() == 0 AND $uw_obj->isValidEffectiveDate( $uw_obj->getEffectiveDate() ) == FALSE ) {
					//Set wage effective date to employees hire date.
					$u_obj = $uw_obj->getUserObject();
					if ( is_object($u_obj) ) {
						$uw_obj->setEffectiveDate( $u_obj->getHireDate() );
					}
				}
				if ( $uw_obj->isValid() ) {
					$uw_obj->Save();
				}
			}
		}

		return TRUE;

	}
}
?>
