<?php
require_once( 'TTSeleniumGlobal.php' );
/**
 * @group UI
 */
class UILoginTest extends TTSeleniumGlobal  {
	public function setUp(): void {
		parent::setUp();
	}

	public function tearDown(): void {
		parent::tearDown();
	}

	function testUILoginLogout() {
		$this->Login('demoadmin2','demo.de');
		$this->Logout();
	}

//	function testEditUser() {
//		//TODO: Use input field names/ids rather then positions or xpath indexes.
//		$this->Login();
//
//		//Go to employee list
//		$this->getText('xpath=//div[@id=\'ribbon\']/ul/li[11]/a');
//		$this->click('link=Employee');
//		$this->click('css=#Employee > img');
//		$this->waitForAttribute( 'css=div.view@init_complete' );
//
//		//Add new employee.
//		$this->getText('xpath=//div[@id=\'ribbon\']/ul/li[11]/a');
//		$this->click('css=#addIcon > img');
//		$this->waitForAttribute( 'css=div.view@init_complete' );
//
//		//Enter employee information.
//		$this->type('xpath=id(\'tab0_content_div\')/div[1]/div[13]/div[2]/input', 'selenium.test');
//		$this->fireEvent('xpath=id(\'tab0_content_div\')/div[1]/div[13]/div[2]/input', 'keyup');
//		$this->waitForElementPresent('xpath=id(\'tab0_content_div\')/div[1]/div[13]/div[2]/input[contains(@class,\'error-tip\')]', FALSE );
//
//		$this->type('xpath=id(\'tab0_content_div\')/div[1]/div[15]/div[2]/input', 'demo');
//		$this->fireEvent('xpath=id(\'tab0_content_div\')/div[1]/div[15]/div[2]/input', 'keyup');
//		$this->waitForElementPresent('xpath=id(\'tab0_content_div\')/div[1]/div[15]/div[2]/input[contains(@class,\'error-tip\')]', FALSE );
//
//		$this->type('xpath=id(\'tab0_content_div\')/div[1]/div[17]/div[2]/input', 'demo');
//		$this->fireEvent('xpath=id(\'tab0_content_div\')/div[1]/div[17]/div[2]/input', 'keyup');
//		$this->waitForElementPresent('xpath=id(\'tab0_content_div\')/div[1]/div[17]/div[2]/input[contains(@class,\'error-tip\')]', FALSE );
//
//		$this->type('xpath=(//input[@type=\'text\'])[12]', 'selenium');
//		$this->fireEvent('xpath=(//input[@type=\'text\'])[12]', 'keyup');
//		$this->type('xpath=(//input[@type=\'text\'])[13]', 'test');
//		$this->fireEvent('xpath=(//input[@type=\'text\'])[13]', 'keyup');
//		$this->waitForElementPresent('xpath=(//input[@type=\'text\'])[13][contains(@class,\'error-tip\')]', FALSE );
//
//		$this->waitForElementPresent('xpath=id(\'EmployeeContextMenu\')/div/div/div[1]/ul/li[8][contains(@class,\'disable-image\')]', FALSE );
//		$this->waitForAttribute( 'css=div.edit-view@validate_complete' );
//
//		//Save employee
//		$this->click('xpath=id(\'EmployeeContextMenu\')/div/div/div[1]/ul/li[8]');
//		$this->waitForElementPresent('css=div.popup-loading' );
//		$this->waitForElementPresent('css=div.edit-view', FALSE );
//		$this->waitForAttribute( 'css=div.view@init_complete' );
//
//		//Search for newly created user
//		$this->click('link=BASIC SEARCH');
//		$this->waitForElementPresent('div.ui-tabs-hide', FALSE );
//		$this->type('css=input.t-text-input', 'selenium');
//		$this->click('id=searchBtn');
//		$this->waitForAttribute( 'css=div.search-panel@search_complete' );
//
//		//Select employee
//		$this->uncheck('xpath=//input[contains(@id,\'jqg_employee_view_container_\')]');
//		$this->click('xpath=//input[contains(@id,\'jqg_employee_view_container_\')]');
//		$this->waitForElementPresent('xpath=id(\'EmployeeContextMenu\')/div/div/div[1]/ul/li[5][contains(@class,\'disable-image\')]', FALSE );
//
//		//Delete employee
//		$this->click('id=deleteIcon');
//		$this->isElementPresent('css=div.confirm-alert');
//
//		//Confirm delete
//		$this->click('id=yesBtn');
//		$this->isElementPresent('css=div.no-result-div');
//
//
//		$this->Logout();
//	}
}
?>