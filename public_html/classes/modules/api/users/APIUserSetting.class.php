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
 * @package API\Company
 */
class APIUserSetting extends APIFactory {
	protected $main_class = 'UserSettingFactory';

	public function __construct() {
		parent::__construct(); //Make sure parent constructor is always called.

		return TRUE;
	}
	
	function getUserSetting( $name ) {
		$retarr = UserSettingFactory::getUserSetting( $this->getCurrentUserObject()->getId(), $name );
		if ( $retarr == TRUE ) {
			return $this->returnHandler( $retarr );
		}
		
		return $this->returnHandler( TRUE);
	}
	
	function setUserSetting( $name, $value, $type_id = 10 ) {
		$retval = UserSettingFactory::setUserSetting( $this->getCurrentUserObject()->getId(), $name, $value, $type_id );
		return $this->returnHandler($retval);
	}
	
	function deleteUserSetting( $name ) {
		$retval = UserSettingFactory::deleteUserSetting( $this->getCurrentUserObject()->getId(), $name );
		return $this->returnHandler($retval);
	}

}
?>
