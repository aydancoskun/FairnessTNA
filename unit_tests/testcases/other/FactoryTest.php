<?php

/**
 * @group JobApplication
 */
class FactoryTest extends PHPUnit\Framework\TestCase {
	protected $company_id = NULL;
	protected $user_id = NULL;
	protected $currency_id = NULL;
	protected $branch_ids = NULL;
	protected $department_ids = NULL;
	protected $user_title_ids = NULL;
	protected $user_ids = NULL;

	public function setUp(): void {
		global $dd;
		Debug::text('Running setUp(): ', __FILE__, __LINE__, __METHOD__, 10);

		$dd = new DemoData();
		$dd->setEnableQuickPunch( FALSE ); //Helps prevent duplicate punch IDs and validation failures.
		$dd->setUserNamePostFix( '_'.uniqid( NULL, TRUE ) ); //Needs to be super random to prevent conflicts and random failing tests.
		$this->company_id = $dd->createCompany();
		$this->legal_entity_id = $dd->createLegalEntity( $this->company_id, 10 );
		Debug::text('Company ID: '. $this->company_id, __FILE__, __LINE__, __METHOD__, 10);

		//$dd->createPermissionGroups( $this->company_id, 40 ); //Administrator only.

		$this->currency_id = $dd->createCurrency( $this->company_id, 10 );

		$dd->createUserWageGroups( $this->company_id );

		$this->user_id = $dd->createUser( $this->company_id, $this->legal_entity_id, 100 );

		$this->branch_ids[] = $dd->createBranch( $this->company_id, 10 );
		$this->branch_ids[] = $dd->createBranch( $this->company_id, 20 );

		$this->department_ids[] = $dd->createDepartment( $this->company_id, 10 );
		$this->department_ids[] = $dd->createDepartment( $this->company_id, 20 );

		$this->user_title_ids[] = $dd->createUserTitle( $this->company_id, 10 );

		$this->user_ids[] = $dd->createUser( $this->company_id, $this->legal_entity_id, 10 );

		$this->assertGreaterThan( 0, $this->company_id );
		$this->assertGreaterThan( 0, $this->user_id );
	}

	public function tearDown(): void {
		Debug::text('Running tearDown(): ', __FILE__, __LINE__, __METHOD__, 10);
	}

	//Test to make sure the FactoryListIterator is properly clearing objects/sub-objects (ie: Validator) between loop iterations.
	function testFactoryListIteratorA() {
		//Create some test records.
		$utf = new UserTitleFactory();
		$utf->setCompany( $this->company_id );
		$utf->setName('Test0');
		if ( $utf->isValid() ) {
			$utf->Save();
		}

		$utf = new UserTitleFactory();
		$utf->setCompany( $this->company_id );
		$utf->setName('Test1');
		if ( $utf->isValid() ) {
			$utf->Save();
		}

		$utf = new UserTitleFactory();
		$utf->setCompany( $this->company_id );
		$utf->setName('Test2');
		if ( $utf->isValid() ) {
			$utf->Save();
		}

		$utf = new UserTitleFactory();
		$utf->setCompany( $this->company_id );
		$utf->setName('Test3');
		if ( $utf->isValid() ) {
			$utf->Save();
		}

		$utlf = new UserTitleListFactory();
		$utlf->getByCompanyId( $this->company_id );
		$this->assertGreaterThanOrEqual( 3, $utlf->getRecordCount() );
		if ( $utlf->getRecordCount() > 0 ) {
			$i = 0;
			foreach( $utlf as $ut_obj ) {
				if ( $i == 0 ) {
					$this->assertTrue( $ut_obj->isValid() );
					if ( $ut_obj->isValid() ) {
						$ut_obj->Save();
					}
				} elseif ( $i == 1 ) {
					$ut_obj->setName('');
					$this->assertFalse( $ut_obj->isValid() );
				} elseif ( $i == 2 ) {
					$this->assertTrue( $ut_obj->isValid() );
					if ( $ut_obj->isValid() ) {
						$ut_obj->Save();
					}
				} elseif ( $i == 3 ) {
					$ut_obj->setName('');
					$this->assertFalse( $ut_obj->isValid() );
				}

				$i++;
			}
		}

		return TRUE;
	}

	function testUserPre1970BirthDates() {
		TTDate::setTimeZone( 'PST8PDT', TRUE ); //Due to being a singleton and PHPUnit resetting the state, always force the timezone to be set.

		$ulf = TTnew('UserListFactory'); /** @var UserListFactory $ulf */

		$u_obj = $ulf->getById( $this->user_id )->getCurrent();
		$data = $u_obj->getObjectAsArray();
		unset($data['permission_control_id']);
		$data['birth_date'] = '31-Jul-69';
		$u_obj->setObjectFromArray( $data );
		if ( $u_obj->isValid() ) {
			$result = $u_obj->Save();
		}
		unset( $u_obj );

		//Save it multiple times to ensure it doesn't change.
		$u_obj = $ulf->getById( $this->user_id )->getCurrent();
		$data = $u_obj->getObjectAsArray();
		unset($data['permission_control_id']);
		$this->assertEquals( $data['birth_date'], '31-Jul-69' );
		$data['birth_date'] = '31-Jul-69';
		$u_obj->setObjectFromArray( $data );
		if ( $u_obj->isValid() ) {
			$result = $u_obj->Save();
		}
		unset( $u_obj );

		//Save it multiple times to ensure it doesn't change.
		$u_obj = $ulf->getById( $this->user_id )->getCurrent();
		$data = $u_obj->getObjectAsArray();
		unset($data['permission_control_id']);
		$this->assertEquals( $data['birth_date'], '31-Jul-69' );
		$data['birth_date'] = '31-Jul-69';
		$u_obj->setObjectFromArray( $data );
		if ( $u_obj->isValid() ) {
			$result = $u_obj->Save();
		}
		unset( $u_obj );
	}
}
?>
