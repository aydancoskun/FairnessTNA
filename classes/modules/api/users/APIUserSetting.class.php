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
 * @package API\Company
 */
class APIUserSetting extends APIFactory {
	protected $main_class = 'UserSettingFactory';

	/**
	 * APIUserSetting constructor.
	 */
	public function __construct() {
		parent::__construct(); //Make sure parent constructor is always called.

		return TRUE;
	}

	/**
	 * @param $name
	 * @return array|bool
	 */
	function getUserSetting( $name ) {
		$retarr = UserSettingFactory::getUserSetting( $this->getCurrentUserObject()->getId(), $name );
		if ( $retarr == TRUE ) {
			return $this->returnHandler( $retarr );
		}

		return $this->returnHandler( TRUE);
	}

	/**
	 * @param $name
	 * @param $value
	 * @param int $type_id
	 * @return array|bool
	 */
	function setUserSetting( $name, $value, $type_id = 10 ) {
		$retval = UserSettingFactory::setUserSetting( $this->getCurrentUserObject()->getId(), $name, $value, $type_id );
		return $this->returnHandler($retval);
	}

	/**
	 * @param $name
	 * @return array|bool
	 */
	function deleteUserSetting( $name ) {
		$retval = UserSettingFactory::deleteUserSetting( $this->getCurrentUserObject()->getId(), $name );
		return $this->returnHandler($retval);
	}

}
?>
