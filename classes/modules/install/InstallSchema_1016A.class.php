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
class InstallSchema_1016A extends InstallSchema_Base {

	protected $station_users = array();

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
		//assumed needed elsewhere
		// @codingStandardsIgnoreEnd

		Debug::text('postInstall: '. $this->getVersion(), __FILE__, __LINE__, __METHOD__, 9);

		Debug::text('l: '. $this->getVersion(), __FILE__, __LINE__, __METHOD__, 9);

		$cjlf = TTnew( 'CronJobListFactory' ); /** @var CronJobListFactory $cjlf */
		$cjlf->getAll();
		if ( $cjlf->getRecordCount() > 0 ) {
			foreach( $cjlf as $cj_obj ) {
				Debug::text('Original Command: '.  $cj_obj->getCommand(), __FILE__, __LINE__, __METHOD__, 9);
				preg_match('/([A-Za-z0-9]+\.php)/i', $cj_obj->getCommand(), $matches );

				if ( isset($matches[0]) AND $matches[0] != '' ) {
					Debug::text('New Command: '. $matches[0], __FILE__, __LINE__, __METHOD__, 9);
					$cj_obj->setCommand( $matches[0] );
					if ( $cj_obj->isValid() ) {
						$cj_obj->Save();
					}
				}
			}
		}

		return TRUE;
	}
}
?>
