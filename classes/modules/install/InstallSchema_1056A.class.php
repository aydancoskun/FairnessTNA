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
class InstallSchema_1056A extends InstallSchema_Base {

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

		//Make sure Medicare Employer uses the same include/exclude accounts as Medicare Employee.
		$clf = TTnew( 'CompanyListFactory' ); /** @var CompanyListFactory $clf */
		$clf->getAll();
		if ( $clf->getRecordCount() > 0 ) {
			foreach( $clf as $c_obj ) {
				Debug::text('Company: '. $c_obj->getName(), __FILE__, __LINE__, __METHOD__, 9);
				if ( $c_obj->getStatus() != 30 ) {
					$ppslf = TTNew('PayPeriodScheduleListFactory'); /** @var PayPeriodScheduleListFactory $ppslf */
					$ppslf->getByCompanyID( $c_obj->getId() );
					if ( $ppslf->getRecordCount() > 0 ) {
						$minimum_time_between_shifts = $ppslf->getCurrent()->getNewDayTriggerTime();
					}

					if ( isset($minimum_time_between_shifts) ) {
						$pplf = TTNew('PremiumPolicyListFactory'); /** @var PremiumPolicyListFactory $pplf */
						$pplf->getAPISearchByCompanyIdAndArrayCriteria( $c_obj->getID(), array('type_id' => 50) );
						if ( $pplf->getRecordCount() > 0 ) {
							foreach( $pplf as $pp_obj ) {
								$pp_obj->setMinimumTimeBetweenShift( $minimum_time_between_shifts );
								if ( $pp_obj->isValid() ) {
									$pp_obj->Save();
								}
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
