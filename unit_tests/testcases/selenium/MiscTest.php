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
require_once( 'TTSeleniumGlobal.php' );

/**
 * @group UI
 */
class UIMiscTest extends TTSeleniumGlobal {

	function testUIGetGlobalVariable() {
		$this->Login( 'demoadmin2', 'demo.de' );

		$javascript = array('script' => 'return Global.getUIReadyStatus();', 'args' => array());
		$var = $this->execute( $javascript );
		Debug::text( 'Global variable 1 retrieved: ' . print_r( $var, TRUE ), __FILE__, __LINE__, __METHOD__, 10 );

		$this->waitForUIInitComplete();
		$this->byId( 'timesheetIcon' )->click();
		$this->waitForUIInitComplete();

		$javascript = array('script' => 'return Global.UIReadyStatus;', 'args' => array());
		$var2 = $this->execute( $javascript );
		Debug::text( 'Global variable 2 retrieved: ' . print_r( $var, TRUE ), __FILE__, __LINE__, __METHOD__, 10 );

		$this->assertNotEmpty( $var );
		$this->assertNotEmpty( $var2 );

		$this->Logout();
	}
}

?>