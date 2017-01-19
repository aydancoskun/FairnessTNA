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
 * @group UI
 */
class UILoginTest extends PHPUnit_Extensions_Selenium2TestCase {
	public function setUp() {
		global $selenium_config;
		$this->selenium_config = $selenium_config;

		Debug::text('Running setUp(): ', __FILE__, __LINE__, __METHOD__, 10);

		TTDate::setTimeZone('Etc/GMT+8', TRUE); //Due to being a singleton and PHPUnit resetting the state, always force the timezone to be set.

		$this->setHost( $selenium_config['host'] );
		$this->setBrowser( $selenium_config['browser'] );
		$this->setBrowserUrl( $selenium_config['default_url'] );

		return TRUE;
	}

	public function tearDown() {
		Debug::text('Running tearDown(): ', __FILE__, __LINE__, __METHOD__, 10);

		return TRUE;
	}

	function Login() {
		Debug::text('Login to: '. $this->selenium_config['default_url'], __FILE__, __LINE__, __METHOD__, 10);
		$this->url( $this->selenium_config['default_url'] );

//		$this->waitForAttribute( 'css=div.login-view@init_complete' );
//
//		$this->type('id=user_name', 'demoadmin1');
//		$this->type('id=password', 'demo.de');
//		$this->click('id=login_btn');
//		$this->waitForAttribute( 'css=div.view@init_complete' );

		$this->waitUntil(function () {
			if ($this->byId('user_name')) {
				return true;
			}
			return null;
		}, 5000);
		$this->clickOnElement('user_name');
		$this->keys('demoadmin1');

		$this->clickOnElement('password');
		$this->keys('demo.de');

		$this->clickOnElement('login_btn');

		Debug::text('Login Complete...', __FILE__, __LINE__, __METHOD__, 10);
	}

	function Logout() {
//		$view_name = $this->getText('xpath=//div[@id=\'ribbon\']/ul/li[11]/a');
//		Debug::text('View: '. $view_name, __FILE__, __LINE__, __METHOD__, 10);
//		$this->click('link=My Account');
//		$this->click('id=Logout');
//		$this->waitForAttribute( 'css=div.login-view@init_complete' );

		$view_name = $this->byXPath('xpath=//div[@id=\'ribbon\']/ul/li[11]/a');
		Debug::text('View: '. $view_name, __FILE__, __LINE__, __METHOD__, 10);
		$this->click('link=My Account');
		$this->click('id=Logout');
		$this->waitForAttribute( 'css=div.login-view@init_complete' );



		Debug::text('Logout...', __FILE__, __LINE__, __METHOD__, 10);
	}

	function waitForAttribute( $attribute_name, $value = 'true', $timeout = FALSE ) {
		if ( $timeout == '' ) {
			$timeout = $this->selenium_config['default_timeout'];
		}

		for ($second = 0; ; $second++) {
			if ( $second >= $timeout ) {
				Debug::text('TIMEOUT waitForAttribute failed: '. $attribute_name, __FILE__, __LINE__, __METHOD__, 10);
				$this->fail('timeout');
			}

			try {
				if ( $this->getAttribute( $attribute_name ) == $value ) {
					break;
				}
			} catch ( Exception $e ) {
				Debug::text('Exception! waitForAttribute failed: '. $attribute_name, __FILE__, __LINE__, __METHOD__, 10);
			}
			sleep(1);
		}

		return TRUE;
	}

	function waitForElementPresent( $attribute_name, $value = TRUE, $timeout = FALSE ) {
		if ( $timeout == '' ) {
			$timeout = $this->selenium_config['default_timeout'];
		}

		for ($second = 0; ; $second++) {
			if ( $second >= $timeout ) {
				Debug::text('TIMEOUT isElementPresent failed: '. $attribute_name, __FILE__, __LINE__, __METHOD__, 10);
				$this->fail('timeout');
			}

			try {
				if ( $this->isElementPresent( $attribute_name ) == $value ) {
					break;
				}
			} catch ( Exception $e ) {
				Debug::text('Exception! isElementPresent failed: '. $attribute_name, __FILE__, __LINE__, __METHOD__, 10);
			}
			sleep(1);
		}

		return TRUE;
	}

	function testUILoginLogout() {
		$this->Login();
		$this->Logout();
	}

	function testEditUser() {
		//TODO: Use input field names/ids rather then positions or xpath indexes.
		$this->Login();

		//Go to employee list
		$this->getText('xpath=//div[@id=\'ribbon\']/ul/li[11]/a');
		$this->click('link=Employee');
		$this->click('css=#Employee > img');
		$this->waitForAttribute( 'css=div.view@init_complete' );

		//Add new employee.
		$this->getText('xpath=//div[@id=\'ribbon\']/ul/li[11]/a');
		$this->click('css=#addIcon > img');
		$this->waitForAttribute( 'css=div.view@init_complete' );

		//Enter employee information.
		$this->type('xpath=id(\'tab0_content_div\')/div[1]/div[13]/div[2]/input', 'selenium.test');
		$this->fireEvent('xpath=id(\'tab0_content_div\')/div[1]/div[13]/div[2]/input', 'keyup');
		$this->waitForElementPresent('xpath=id(\'tab0_content_div\')/div[1]/div[13]/div[2]/input[contains(@class,\'error-tip\')]', FALSE );

		$this->type('xpath=id(\'tab0_content_div\')/div[1]/div[15]/div[2]/input', 'demo');
		$this->fireEvent('xpath=id(\'tab0_content_div\')/div[1]/div[15]/div[2]/input', 'keyup');
		$this->waitForElementPresent('xpath=id(\'tab0_content_div\')/div[1]/div[15]/div[2]/input[contains(@class,\'error-tip\')]', FALSE );

		$this->type('xpath=id(\'tab0_content_div\')/div[1]/div[17]/div[2]/input', 'demo');
		$this->fireEvent('xpath=id(\'tab0_content_div\')/div[1]/div[17]/div[2]/input', 'keyup');
		$this->waitForElementPresent('xpath=id(\'tab0_content_div\')/div[1]/div[17]/div[2]/input[contains(@class,\'error-tip\')]', FALSE );

		$this->type('xpath=(//input[@type=\'text\'])[12]', 'selenium');
		$this->fireEvent('xpath=(//input[@type=\'text\'])[12]', 'keyup');
		$this->type('xpath=(//input[@type=\'text\'])[13]', 'test');
		$this->fireEvent('xpath=(//input[@type=\'text\'])[13]', 'keyup');
		$this->waitForElementPresent('xpath=(//input[@type=\'text\'])[13][contains(@class,\'error-tip\')]', FALSE );

		$this->waitForElementPresent('xpath=id(\'EmployeeContextMenu\')/div/div/div[1]/ul/li[8][contains(@class,\'disable-image\')]', FALSE );
		$this->waitForAttribute( 'css=div.edit-view@validate_complete' );

		//Save employee
		$this->click('xpath=id(\'EmployeeContextMenu\')/div/div/div[1]/ul/li[8]');
		$this->waitForElementPresent('css=div.popup-loading' );
		$this->waitForElementPresent('css=div.edit-view', FALSE );
		$this->waitForAttribute( 'css=div.view@init_complete' );

		//Search for newly created user
		$this->click('link=BASIC SEARCH');
		$this->waitForElementPresent('div.ui-tabs-hide', FALSE );
		$this->type('css=input.t-text-input', 'selenium');
		$this->click('id=searchBtn');
		$this->waitForAttribute( 'css=div.search-panel@search_complete' );

		//Select employee
		$this->uncheck('xpath=//input[contains(@id,\'jqg_employee_view_container_\')]');
		$this->click('xpath=//input[contains(@id,\'jqg_employee_view_container_\')]');
		$this->waitForElementPresent('xpath=id(\'EmployeeContextMenu\')/div/div/div[1]/ul/li[5][contains(@class,\'disable-image\')]', FALSE );

		//Delete employee
		$this->click('id=deleteIcon');
		$this->isElementPresent('css=div.confirm-alert');

		//Confirm delete
		$this->click('id=yesBtn');
		$this->isElementPresent('css=div.no-result-div');


		$this->Logout();
	}
}
?>