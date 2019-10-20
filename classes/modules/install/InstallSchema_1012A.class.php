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
class InstallSchema_1012A extends InstallSchema_Base {

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

		// @codingStandardsIgnoreStart
		global $cache;
		// @codingStandardsIgnoreEnd

		Debug::text('postInstall: '. $this->getVersion(), __FILE__, __LINE__, __METHOD__, 9);

		//Get all pay period schedules.
		$ppslf = TTnew( 'PayPeriodScheduleListFactory' ); /** @var PayPeriodScheduleListFactory $ppslf */
		$ppslf->getAll();
		if ( $ppslf->getRecordCount() > 0 ) {
			foreach( $ppslf as $pps_obj ) {
				$user_ids = $pps_obj->getUser();
				if ( is_array($user_ids) ) {
					$time_zone_arr = array();
					foreach( $user_ids as $user_id ) {
						$uplf = TTnew( 'UserPreferenceListFactory' ); /** @var UserPreferenceListFactory $uplf */
						$uplf->getByUserId( $user_id );
						if ( $uplf->getRecordCount() > 0 ) {
							if ( isset($time_zone_arr[$uplf->getCurrent()->getTimeZone()]) ) {
								$time_zone_arr[$uplf->getCurrent()->getTimeZone()]++;
							} else {
								$time_zone_arr[$uplf->getCurrent()->getTimeZone()] = 1;
							}
						}
					}

					arsort($time_zone_arr);

					//Grab the first time zone, as it is most common
					foreach( $time_zone_arr as $time_zone => $count ) {
						break;
					}
					unset($count); //code standards

					if ( $time_zone != '' ) {
						//Set pay period timezone to the timezone of the majority of the users are in.
						$pps_obj->setTimeZone( $time_zone );
						if ( $pps_obj->isValid() ) {
							$pps_obj->Save();
						}
					}
				}
			}
		}

		Debug::text('l: '. $this->getVersion(), __FILE__, __LINE__, __METHOD__, 9);

		return TRUE;
	}
}
?>
