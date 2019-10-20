<?php

class SQLTest extends PHPUnit\Framework\TestCase {

	public function setUp(): void {
		global $dd;
		Debug::text( 'Running setUp(): ', __FILE__, __LINE__, __METHOD__, 10 );

		$dd = new DemoData();
		$dd->setEnableQuickPunch( FALSE ); //Helps prevent duplicate punch IDs and validation failures.
		$dd->setUserNamePostFix( '_' . uniqid( NULL, TRUE ) ); //Needs to be super random to prevent conflicts and random failing tests.
		$this->company_id = $dd->createCompany();
		$this->legal_entity_id = $dd->createLegalEntity( $this->company_id, 10 );
		Debug::text( 'Company ID: ' . $this->company_id, __FILE__, __LINE__, __METHOD__, 10 );
		$this->assertGreaterThan( 0, $this->company_id );

		//$dd->createPermissionGroups( $this->company_id, 40 ); //Administrator only.

		$dd->createCurrency( $this->company_id, 10 );

		$this->branch_id = $dd->createBranch( $this->company_id, 10 ); //NY

		//$dd->createPayStubAccount( $this->company_id );
		//$dd->createPayStubAccountLink( $this->company_id );

		$dd->createUserWageGroups( $this->company_id );

		$this->user_id = $dd->createUser( $this->company_id, $this->legal_entity_id, 100 );
		$this->assertGreaterThan( 0, $this->user_id );
	}

	public function tearDown(): void {
		Debug::text( 'Running tearDown(): ', __FILE__, __LINE__, __METHOD__, 10 );
	}

	function getListFactoryClassList( $equal_parts = 1 ) {
		global $global_class_map;

		$retarr = array();

		//Get all ListFactory classes
		foreach ( $global_class_map as $class_name => $class_file_name ) {
			if ( strpos( $class_name, 'ListFactory' ) !== FALSE ) {
				$retarr[] = $class_name;
			}
		}

		$chunk_size = ceil( ( count( $retarr ) / $equal_parts ) );

		return array_chunk( $retarr, $chunk_size );
	}

	function runSQLTestOnListFactory( $factory_name ) {
		if ( class_exists( $factory_name ) ) {
			$reflectionClass = new ReflectionClass( $factory_name );
			$class_file_name = $reflectionClass->getFileName();

			Debug::text( 'Checking Class: ' . $factory_name . ' File: ' . $class_file_name, __FILE__, __LINE__, __METHOD__, 10 );

			$filter_data_types = array(
					'not_set',
					'true',
					'false',
					'null',
					'empty_string',
					'negative_small_int',
					'small_int',
					'large_int',
					'string',
					'array',
			);

			//Parse filter array keys from class file so we can populate them with dummy data.
			preg_match_all( '/\$filter_data\[\'([a-z0-9_]*)\'\]/i', file_get_contents( $class_file_name ), $filter_data_match );
			if ( isset( $filter_data_match[1] ) ) {
				//Debug::Arr($filter_data_match, 'Filter Data Match: ', __FILE__, __LINE__, __METHOD__, 10);
				foreach ( $filter_data_types as $filter_data_type ) {
					Debug::Text( 'Filter Data Type: ' . $filter_data_type, __FILE__, __LINE__, __METHOD__, 10 );

					$filter_data = array();

					$filter_data_match[1] = array_unique( $filter_data_match[1] );
					foreach ( $filter_data_match[1] as $filter_data_key ) {
						//Skip sort_column/sort_order
						if ( in_array( $filter_data_key, array('sort_column', 'sort_order') ) ) {
							continue;
						}

						//Test with:
						// Small Integers
						// Large Integers (64bit)
						// Strings
						// Arrays
						switch ( $filter_data_type ) {
							case 'true':
								$filter_data[$filter_data_key] = TRUE;
								break;
							case 'false':
								$filter_data[$filter_data_key] = FALSE;
								break;
							case 'null':
								$filter_data[$filter_data_key] = NULL;
								break;
							case 'empty_string':
								$filter_data[$filter_data_key] = '';
								break;
							case 'negative_small_int':
								$filter_data[$filter_data_key] = ( rand( 0, 128 ) * -1 );
								break;
							case 'small_int':
								$filter_data[$filter_data_key] = rand( 0, 128 );
								break;
							case 'large_int':
								$filter_data[$filter_data_key] = rand( 2147483648, 21474836489 );
								break;
							case 'string':
								$filter_data[$filter_data_key] = 'A' . substr( md5( microtime() ), rand( 0, 26 ), 10 );
								break;
							case 'array':
								$filter_data[$filter_data_key] = array(rand( 0, 128 ), rand( 2147483648, 21474836489 ), 'A' . substr( md5( microtime() ), rand( 0, 26 ), 10 ));
								break;
							case 'not_set':
								break;
						}
					}
					//Debug::Arr($filter_data, 'Filter Data: ', __FILE__, __LINE__, __METHOD__, 10);

					$lf = TTNew( $factory_name );
					switch ( $factory_name ) {
						case 'RecurringScheduleControlListFactory':
							$retarr = $lf->getAPIExpandedSearchByCompanyIdAndArrayCriteria( $this->company_id, $filter_data, 1, 1, NULL, NULL );
							$this->assertNotEquals( $retarr, FALSE );
							$this->assertTrue( is_object( $retarr ), TRUE );

							$retarr = $lf->getAPISearchByCompanyIdAndArrayCriteria( $this->company_id, $filter_data, 1, 1, NULL, NULL );
							$this->assertNotEquals( $retarr, FALSE );
							$this->assertTrue( is_object( $retarr ), TRUE );
							break;
						case 'ScheduleListFactory':
							$retarr = $lf->getSearchByCompanyIdAndArrayCriteria( $this->company_id, $filter_data, 1, 1, NULL, NULL );
							$this->assertNotEquals( $retarr, FALSE );
							$this->assertTrue( is_object( $retarr ), TRUE );

							$retarr = $lf->getAPISearchByCompanyIdAndArrayCriteria( $this->company_id, $filter_data, 1, 1, NULL, NULL );
							$this->assertNotEquals( $retarr, FALSE );
							$this->assertTrue( is_object( $retarr ), TRUE );
							break;
						case 'MessageControlListFactory':
							$filter_data['current_user_id'] = $this->user_id;
						default:
							if ( method_exists( $lf, 'getAPISearchByCompanyIdAndArrayCriteria' ) ) {
								//Make sure we test pagination, especially with MySQL due to its limitation with subqueries and need for _ADODB_COUNT workarounds, $limit = NULL, $page = NULL, $where = NULL, $order = NULL
								$retarr = $lf->getAPISearchByCompanyIdAndArrayCriteria( $this->company_id, $filter_data, 1, 1, NULL, NULL );
								$this->assertNotEquals( $retarr, FALSE );
								$this->assertTrue( is_object( $retarr ), TRUE );
							}
							break;
					}
				}
			}
			unset( $filter_data_match );

			Debug::text( 'Done...', __FILE__, __LINE__, __METHOD__, 10 );

			return TRUE;
		} else {
			Debug::text( 'Class does not exist: ' . $factory_name, __FILE__, __LINE__, __METHOD__, 10 );
		}

		return FALSE;
	}

	function runSQLTestOnListFactoryMethods( $factory_name ) {
		if ( in_array( $factory_name, array(
											'HierarchyListFactory',
											'PolicyGroupAccrualPolicyListFactory',
											'PolicyGroupOverTimePolicyListFactory',
											'PolicyGroupPremiumPolicyListFactory',
											'PolicyGroupRoundIntervalPolicyListFactory',
											'ProductTaxPolicyProductListFactory',
									)
		)
		) {
			return TRUE; //Deprecated classes.
		}

		if ( class_exists( $factory_name ) ) {
			$reflectionClass = new ReflectionClass( $factory_name );
			Debug::text( 'Checking Class: ' . $factory_name, __FILE__, __LINE__, __METHOD__, 10 );

			$raw_methods = $reflectionClass->getMethods( ReflectionMethod::IS_PUBLIC );
			if ( is_array( $raw_methods ) ) {
				global $db;
				foreach ( $raw_methods as $raw_method ) {
					if ( $factory_name == $raw_method->class
							AND (
									strpos( $raw_method->name, 'getAll' ) !== FALSE
									OR strpos( $raw_method->name, 'getBy' ) !== FALSE
									OR strpos( $raw_method->name, 'Report' ) !== FALSE
							)
							AND (
									strncmp( $db->databaseType, 'mysql', 5 ) != 0
									OR
									//Exclude function calls that are known to not work in MySQL.
									( strncmp( $db->databaseType, 'mysql', 5 ) == 0 AND !in_array( $raw_method->name, array('getByPhonePunchDataByCompanyIdAndStartDateAndEndDate') ) )
							)
							AND (
								//Skip getByCompanyIdArray() functions, but include getBy*AndArrayCriteria(). So just check if its ends with Array or not.
							( substr( $raw_method->name, -5 ) !== 'Array' )
							)
					) {
						Debug::text( 'Class: ' . $factory_name . ' Method: ' . $raw_method->name, __FILE__, __LINE__, __METHOD__, 10 );

						$test_modes = array('default', 'fuzz');
						foreach ( $test_modes as $test_mode ) {
							Debug::text( '  Test Mode: ' . $test_mode, __FILE__, __LINE__, __METHOD__, 10 );
							//Get method arguments.
							$method_parameters = $raw_method->getParameters();
							if ( is_array( $method_parameters ) ) {
								$input_arguments = array();
								foreach ( $method_parameters as $method_parameter ) {
									Debug::text( '  Parameter: ' . $method_parameter->name, __FILE__, __LINE__, __METHOD__, 10 );

									switch ( $factory_name ) {
										case 'ClientContactListFactory':
											switch ( $method_parameter->name ) {
												case 'key':
													$input_argument = '900d6136975e3a728051a62ed119191034568745';
													break;
											}
											break;
										case 'ClientContactListFactory':
											switch ( $method_parameter->name ) {
												case 'name':
													$input_argument = 'test';
													break;
											}
											break;
										case 'RoundIntervalPolicyListFactory':
											switch ( $method_parameter->name ) {
												case 'type_id':
													$input_argument = 40;
													break;
											}
											break;
										case 'ScheduleListFactory':
											switch ( $method_parameter->name ) {
												case 'direction':
													$input_argument = 'before';
													break;
											}
											break;
										case 'UserListFactory':
										case 'UserContactListFactory':
										case 'JobApplicantListFactory':
											switch ( $method_parameter->name ) {
												case 'email':
													$input_argument = 'hi@hi.com';
													break;
												case 'key':
													$input_argument = '900d6136975e3a728051a62ed119191034568745';
													break;
											}
											break;
										case 'PayPeriodTimeSheetVerifyListFactory':
										case 'RequestListFactory':
											switch ( $method_parameter->name ) {
												case 'hierarchy_level_map':
													$input_argument = array(
															array(
																	'hierarchy_control_id' => 1,
																	'level'                => 1,
																	'last_level'           => 2,
																	'object_type_id'       => 10,
															)
													);
													break;

											}
											break;
										case 'ExceptionListFactory':
											switch ( $method_parameter->name ) {
												case 'time_period':
													$input_argument = 'week';
													break;

											}
											break;
									}

									if ( $test_mode == 'fuzz' ) {
										//If LIMIT argument is available always set it to 1 to reduce memory usage.
										if ( in_array( $method_parameter->name, array('where', 'order', 'page') ) ) {
											$input_argument = NULL;
										} elseif ( !isset( $input_argument ) AND ( $method_parameter->name == 'id' OR strpos( $method_parameter->name, '_id' ) !== FALSE OR $method_parameter->name == 'limit' ) ) { //Use integer as its a ID argument.
											$input_argument = 'false'; //Try passing a string where ID is expected.
										} elseif ( !isset( $input_argument ) ) {
											$input_argument = 2;
										}
										$input_arguments[] = $input_argument;
									} else {
										//If LIMIT argument is available always set it to 1 to reduce memory usage.
										if ( in_array( $method_parameter->name, array('where', 'order', 'page') ) ) {
											$input_argument = NULL;
										} elseif ( !isset( $input_argument ) AND ( $method_parameter->name == 'id' OR strpos( $method_parameter->name, '_id' ) !== FALSE OR $method_parameter->name == 'limit' ) ) { //Use integer as its a ID argument.
											$input_argument = 1;
										} elseif ( !isset( $input_argument ) ) {
											$input_argument = 2;
										}
										$input_arguments[] = $input_argument;
									}
									unset( $input_argument );
								}

								if ( isset( $input_arguments ) AND is_array( $input_arguments ) ) {
									Debug::Arr( $input_arguments, '    Calling Class: ' . $factory_name . ' Method: ' . $raw_method->name, __FILE__, __LINE__, __METHOD__, 10 );
									$lf = TTNew( $factory_name );
									switch ( $factory_name . '::' . $raw_method->name ) {
										case 'StationListFactory::getByUserIdAndStatusAndType':
										case 'PayStubEntryAccountListFactory::getByTypeArrayByCompanyIdAndStatusId':
											//Skip due to failures.
											break;
										case 'CompanyListFactory::getByPhoneID':
											$retarr = call_user_func_array( array($lf, $raw_method->name), $input_arguments );
											if ( $test_mode == 'fuzz' ) {
												$this->assertEquals( $retarr, FALSE ); //This will be FALSE
											} else {
												$this->assertNotEquals( $retarr, FALSE );
												$this->assertTrue( is_object( $retarr ), TRUE );
											}
											break;
										case 'MessageControlListFactory::getByCompanyIdAndObjectTypeAndObjectAndNotUser':
											$retarr = call_user_func_array( array($lf, $raw_method->name), $input_arguments );
											$this->assertEquals( $retarr, FALSE ); //This will be FALSE, but it still executes a query.
											//$this->assertTrue( is_object($retarr), TRUE );
											break;
										case 'CompanyListFactory::getByPhoneID':
										case 'PayStubEntryListFactory::getByPayStubIdAndEntryNameId':
											//FUZZ tests should return FALSE, otherwise they should be normal.
											$retarr = call_user_func_array( array($lf, $raw_method->name), $input_arguments );
											if ( $test_mode == 'fuzz' ) {
												$this->assertEquals( $retarr, FALSE ); //This will be FALSE
											} else {
												$this->assertNotEquals( $retarr, FALSE );
												$this->assertTrue( is_object( $retarr ), TRUE );
											}
											break;
										default:
											$retarr = call_user_func_array( array($lf, $raw_method->name), $input_arguments );
											//Debug::Arr($retarr, '    RetArr: ', __FILE__, __LINE__, __METHOD__, 10);
											$this->assertNotEquals( $retarr, FALSE );
											$this->assertTrue( is_object( $retarr ), TRUE );
											break;
									}
								} else {
									Debug::text( '  No INPUT arguments... Skipping Class: ' . $factory_name . ' Method: ' . $raw_method->name, __FILE__, __LINE__, __METHOD__, 10 );
								}

							}
						}
					} else {
						Debug::text( 'Skipping... Class: ' . $factory_name . ' Method: ' . $raw_method->name, __FILE__, __LINE__, __METHOD__, 10 );
					}
				}
			}

			Debug::text( 'Done...', __FILE__, __LINE__, __METHOD__, 10 );

			return TRUE;
		} else {
			Debug::text( 'Class does not exist: ' . $factory_name, __FILE__, __LINE__, __METHOD__, 10 );
		}

		return FALSE;
	}

	function runSQLSortTestOnListFactory( $factory_name ) {
		if ( class_exists( $factory_name ) ) {
			$reflectionClass = new ReflectionClass( $factory_name );
			$class_file_name = $reflectionClass->getFileName();

			Debug::text( 'Checking Class: ' . $factory_name . ' File: ' . $class_file_name, __FILE__, __LINE__, __METHOD__, 10 );

			$lf = TTNew( $factory_name );
			if ( method_exists( $lf, 'getOptions') ) {
				if ( method_exists( $lf, 'getAPISearchByCompanyIdAndArrayCriteria' ) ) {
					$columns = array_fill_keys( array_keys( array_flip( array_keys( Misc::trimSortPrefix( $lf->getOptions('columns') ) ) ) ), 'asc'); //Set sort order to ASC for all columns.
					if ( is_array($columns) ) {
						try {
							//$retarr = $lf->getAPISearchByCompanyIdAndArrayCriteria( $this->company_id, array(), 1, 1, NULL, array('a.bogus' => 'asc') );
							$retarr = $lf->getAPISearchByCompanyIdAndArrayCriteria( $this->company_id, array(), 1, 1, NULL, $columns );
							$this->assertNotEquals( $retarr, FALSE );
							$this->assertTrue( is_object( $retarr ), TRUE );
						} catch ( Exception $e ) {
							Debug::Arr( $columns,  'Columns: ', __FILE__, __LINE__, __METHOD__, 10 );

							$this->assertTrue( FALSE );
						}
					} else {
						Debug::text( 'getOptions(\'columns\') does not return any data, skipping...', __FILE__, __LINE__, __METHOD__, 10 );
					}
				}
			} else {
				Debug::text( 'getOptions() method does not exist, skipping...', __FILE__, __LINE__, __METHOD__, 10 );
			}

			Debug::text( 'Done...', __FILE__, __LINE__, __METHOD__, 10 );

			return TRUE;
		} else {
			Debug::text( 'Class does not exist: ' . $factory_name, __FILE__, __LINE__, __METHOD__, 10 );
		}

		return FALSE;
	}

	function runSQLTestOnEdition( $product_edition = TT_PRODUCT_ENTERPRISE, $class_list ) {
		global $TT_PRODUCT_EDITION, $db;

		$original_product_edition = getTTProductEdition();

		$this->assertTrue( TRUE );
		if ( $product_edition <= $original_product_edition ) {
			$TT_PRODUCT_EDITION = $product_edition;
			Debug::text( 'Checking against Edition: ' . getTTProductEditionName(), __FILE__, __LINE__, __METHOD__, 10 );

			//Loop through all ListFactory classes testing SQL queries.

			//Run tests with count rows enabled, then with it disabled as well.
			$db->pageExecuteCountRows = FALSE;
			foreach ( $class_list as $class_name ) {
				//$this->runSQLSortTestOnListFactory( $class_name );  //FIXME: Re-enable when we have time and make sure all sorting works.
				$this->runSQLTestOnListFactoryMethods( $class_name );
				$this->runSQLTestOnListFactory( $class_name );
			}

			$db->pageExecuteCountRows = TRUE;
			foreach ( $class_list as $class_name ) {
				$this->runSQLTestOnListFactoryMethods( $class_name );
				$this->runSQLTestOnListFactory( $class_name );
			}
		}

		return TRUE;
	}

	/**
	 * @group SQL_CommunityA
	 */
	function testSQLCommunityA() {
		$classes = $this->getListFactoryClassList( 4 );
		$this->runSQLTestOnEdition( TT_PRODUCT_COMMUNITY, $classes[0] );
	}

	/**
	 * @group SQL_CommunityB
	 */
	function testSQLCommunityB() {
		$classes = $this->getListFactoryClassList( 4 );
		$this->runSQLTestOnEdition( TT_PRODUCT_COMMUNITY, $classes[1] );
	}

	/**
	 * @group SQL_CommunityC
	 */
	function testSQLCommunityC() {
		$classes = $this->getListFactoryClassList( 4 );
		$this->runSQLTestOnEdition( TT_PRODUCT_COMMUNITY, $classes[2] );
	}

	/**
	 * @group SQL_CommunityD
	 */
	function testSQLCommunityD() {
		$classes = $this->getListFactoryClassList( 4 );
		$this->runSQLTestOnEdition( TT_PRODUCT_COMMUNITY, $classes[3] );
	}


	/**
	 * @group SQL_ProfessionalA
	 */
	function testSQLProfessionalA() {
		$classes = $this->getListFactoryClassList( 4 );
		$this->runSQLTestOnEdition( TT_PRODUCT_PROFESSIONAL, $classes[0] );
	}

	/**
	 * @group SQL_ProfessionalB
	 */
	function testSQLProfessionalB() {
		$classes = $this->getListFactoryClassList( 4 );
		$this->runSQLTestOnEdition( TT_PRODUCT_PROFESSIONAL, $classes[1] );
	}

	/**
	 * @group SQL_ProfessionalC
	 */
	function testSQLProfessionalC() {
		$classes = $this->getListFactoryClassList( 4 );
		$this->runSQLTestOnEdition( TT_PRODUCT_PROFESSIONAL, $classes[2] );
	}

	/**
	 * @group SQL_ProfessionalD
	 */
	function testSQLProfessionalD() {
		$classes = $this->getListFactoryClassList( 4 );
		$this->runSQLTestOnEdition( TT_PRODUCT_PROFESSIONAL, $classes[3] );
	}


	/**
	 * @group SQL_CorporateA
	 */
	function testSQLCorporateA() {
		$classes = $this->getListFactoryClassList( 4 );
		$this->runSQLTestOnEdition( TT_PRODUCT_CORPORATE, $classes[0] );
	}

	/**
	 * @group SQL_CorporateB
	 */
	function testSQLCorporateB() {
		$classes = $this->getListFactoryClassList( 4 );
		$this->runSQLTestOnEdition( TT_PRODUCT_CORPORATE, $classes[1] );
	}

	/**
	 * @group SQL_CorporateC
	 */
	function testSQLCorporateC() {
		$classes = $this->getListFactoryClassList( 4 );
		$this->runSQLTestOnEdition( TT_PRODUCT_CORPORATE, $classes[2] );
	}

	/**
	 * @group SQL_CorporateD
	 */
	function testSQLCorporateD() {
		$classes = $this->getListFactoryClassList( 4 );
		$this->runSQLTestOnEdition( TT_PRODUCT_CORPORATE, $classes[3] );
	}


	/**
	 * @group SQL_EnterpriseA
	 */
	function testSQLEnterpriseA() {
		$classes = $this->getListFactoryClassList( 4 );
		$this->runSQLTestOnEdition( TT_PRODUCT_ENTERPRISE, $classes[0] );
	}

	/**
	 * @group SQL_EnterpriseB
	 */
	function testSQLEnterpriseB() {
		$classes = $this->getListFactoryClassList( 4 );
		$this->runSQLTestOnEdition( TT_PRODUCT_ENTERPRISE, $classes[1] );
	}

	/**
	 * @group SQL_EnterpriseC
	 */
	function testSQLEnterpriseC() {
		$classes = $this->getListFactoryClassList( 4 );
		$this->runSQLTestOnEdition( TT_PRODUCT_ENTERPRISE, $classes[2] );
	}

	/**
	 * @group SQL_EnterpriseD
	 */
	function testSQLEnterpriseD() {
		$classes = $this->getListFactoryClassList( 4 );
		$this->runSQLTestOnEdition( TT_PRODUCT_ENTERPRISE, $classes[3] );
	}

	/**
	 * @group SQL_ADODBActiveRecordCount
	 */
	function testADODBActiveRecordCount() {
		global $db;

		//This will test the automatic functionality of ADODB to add count(*) in SQL queries.

		//PageExecute($query, $limit, $page, $ph)

		$db->pageExecuteCountRows = TRUE;

		try {
			$query = 'SELECT id FROM currency';
			$db->PageExecute( $query, 2, 2 );
			$this->assertTrue( TRUE );
		}  catch ( Exception $e ) {
			$this->assertTrue( FALSE );
		}

		try {
			$query = 'SELECT id, status_id FROM currency';
			$db->PageExecute( $query, 2, 2 );
			$this->assertTrue( TRUE );
		}  catch ( Exception $e ) {
			$this->assertTrue( FALSE );
		}

		try {
			$query = 'SELECT a.id FROM currency AS a LEFT JOIN company as tmp ON a.id = tmp.id';
			$db->PageExecute( $query, 2, 2 );
			$this->assertTrue( TRUE );
		}  catch ( Exception $e ) {
			$this->assertTrue( FALSE );
		}

		try {
			$query = 'SELECT a.id FROM currency AS a LEFT JOIN ( SELECT id FROM company ) as tmp ON a.id = tmp.id';
			$db->PageExecute( $query, 2, 2 );
			$this->assertTrue( TRUE );
		}  catch ( Exception $e ) {
			$this->assertTrue( FALSE );
		}

		try {
			$query = 'SELECT a.id, a.status_id FROM currency AS a LEFT JOIN ( SELECT id, status_id FROM company ) as tmp ON a.id = tmp.id';
			$db->PageExecute( $query, 2, 2 );
			$this->assertTrue( TRUE );
		}  catch ( Exception $e ) {
			$this->assertTrue( FALSE );
		}

		try {
			$query = 'SELECT * FROM ( SELECT a.id FROM currency AS a LEFT JOIN company as tmp ON a.id = tmp.id ) as tmp2';
			$db->PageExecute( $query, 2, 2 );
			$this->assertTrue( TRUE );
		}  catch ( Exception $e ) {
			$this->assertTrue( FALSE );
		}

		try {
			$query = 'SELECT _ADODB_COUNT id, (SELECT 1 FROM currency LIMIT 1) _ADODB_COUNT AS tmp FROM users';
			$db->PageExecute( $query, 2, 2 );

			$this->assertTrue( TRUE );
		} catch ( Exception $e ) {
			$this->assertTrue( FALSE );
		}

		//This will fail on MySQL, but pass on PostgreSQL. Since this query should have the _ADODB_COUNT keyword above to make it work on all databases.
		//It works on PGSQL because it can wrap it in a sub-query, ie: SELECT count(*) FROM ( $query )
		try {
			$query = 'SELECT id, (SELECT 1 FROM currency LIMIT 1) AS tmp FROM users';
			$db->PageExecute( $query, 2, 2 );

			//Expect everything to succeed on PGSQL.
			if ( strncmp( $db->databaseType, 'mysql', 5 ) != 0 ) {
				$this->assertTrue( TRUE ); //PGSQL
			}
		} catch ( Exception $e ) {
			//Expect an exception on MySQL.
			if ( strncmp( $db->databaseType, 'mysql', 5 ) == 0 ) {
				$this->assertTrue( TRUE ); //MYSQL
			} else {
				$this->assertTrue( FALSE ); //PGSQL
			}
		}
	}

	/**
	 * @group SQL_SQLInjectionA
	 */
	function testSQLInjectionA() {
		//Test SQL injection with SORT SQL.
		//Test SQL injection with WHERE SQL.

		$utlf = new UserTitleListFactory();

		//Test standard case that should work.
		$sort_arr = array(
				'a.name' => 'asc'
		);
		$utlf->getAPISearchByCompanyIdAndArrayCriteria( 1, array(), NULL, NULL, NULL, $sort_arr );

		//var_dump( $utlf->rs->sql );
		if ( stripos( $utlf->rs->sql, 'a.name' ) !== FALSE ) {
			$this->assertTrue( TRUE );
		} else {
			$this->assertTrue( FALSE );
		}


		//Test advanced case that should work.
		$sort_arr = array(
				//'a.name = \'test\'' => 'asc'
				'a.created_date = 0' => 'asc'
		);
		$utlf->getAPISearchByCompanyIdAndArrayCriteria( 1, array(), NULL, NULL, NULL, $sort_arr );

		//var_dump( $utlf->rs->sql );
		if ( stripos( $utlf->rs->sql, 'a.created_date = 0' ) !== FALSE ) {
			$this->assertTrue( TRUE );
		} else {
			$this->assertTrue( FALSE );
		}



		//Test advanced case that does not work currently, but we may need to get to work.
		try {
			$pself = new PayStubEntryListFactory();
			$sort_arr = array(
					'abs(a.created_date)' => 'asc'
			);

			$pself->getAPISearchByCompanyIdAndArrayCriteria( 1, array(), NULL, NULL, NULL, $sort_arr );
			//var_dump( $pself->rs->sql );
			if ( stripos( $pself->rs->sql, 'abs(a.created_date)' ) !== FALSE ) {
				$this->assertTrue( FALSE );
			} else {
				$this->assertTrue( TRUE );
			}
		} catch ( Exception $e ) {
			$this->assertTrue( TRUE );
		}


		//Test SQL injection in the ORDER BY clause.
		try {
			$sort_arr = array(
								'created_by' => '(SELECT 1)-- .id.'
								);
			$utlf->getAPISearchByCompanyIdAndArrayCriteria( 1, array(), NULL, NULL, NULL, $sort_arr );

			//var_dump( $utlf->rs->sql );
			if ( stripos( $utlf->rs->sql, '(SELECT 1)' ) !== FALSE ) {
				$this->assertTrue( FALSE );
			} else {
				$this->assertTrue( TRUE );
			}
		} catch ( Exception $e ) {
			$this->assertTrue( TRUE );
		}


		//Test SQL injection with brackets and "--"
		try {
			$sort_arr = array(
								'(SELECT 1)-- .id.' => 1
								);
			$utlf->getAPISearchByCompanyIdAndArrayCriteria( 1, array(), NULL, NULL, NULL, $sort_arr );

			//var_dump( $utlf->rs->sql );
			if ( stripos( $utlf->rs->sql, '(SELECT 1)' ) !== FALSE ) {
				$this->assertTrue( FALSE );
			} else {
				$this->assertTrue( TRUE );
			}
		} catch ( Exception $e ) {
			$this->assertTrue( TRUE );
		}


		//Test SQL injection with ";" and "--"
		try {
			$sort_arr = array(
					'; (SELECT 1)-- .id.' => 1
			);
			$utlf->getAPISearchByCompanyIdAndArrayCriteria( 1, array(), NULL, NULL, NULL, $sort_arr );

			//var_dump( $utlf->rs->sql );
			if ( stripos( $utlf->rs->sql, '(SELECT 1)' ) !== FALSE ) {
				$this->assertTrue( FALSE );
			} else {
				$this->assertTrue( TRUE );
			}
		} catch ( Exception $e ) {
			$this->assertTrue( TRUE );
		}


		//FIXME: Test around the WHERE clause, even though no user input should ever get to it.
//		$pslf = TTnew('PayStubListFactory');
//		//$pslf->getByCompanyId( 1, 1, NULL, ( array('a.start_date' => ">= '". $pslf->db->BindTimeStamp( TTDate::getBeginDayEpoch( time() - (86400 * 30) ) )."'") ) );
//		$pslf->getByCompanyId( 1, 1, NULL, ( array('a.start_date >=' => $pslf->db->BindTimeStamp( TTDate::getBeginDayEpoch( time() - (86400 * 30) ) ) ) ) );
//		//$pslf->getByCompanyId( 1, 1, NULL, ( array('a.created_date' => "=1-- (SELECT 1)--") ) );
//		var_dump( $pslf->rs->sql );
	}


	/**
	 * Used to call protected methods in the Factory class.
	 * @param $name
	 * @return ReflectionMethod
	 */
	protected static function getMethod($name) {
		$class = new ReflectionClass('UserListFactory');
		$method = $class->getMethod($name);
		$method->setAccessible(true);
		return $method;
	}

	/**
	 * @group SQL_testWhereClauseSQL
	 */
	function testWhereClauseSQL( ) {
		$method = self::getMethod('getWhereClauseSQL');
		$ulf = new UserListFactory();


		//Boolean TRUE
		$ph = array();
		$retval = $method->invokeArgs($ulf, array( 'a.private', (bool)TRUE, 'boolean', &$ph ) );
		$this->assertEquals( $retval, ' AND a.private = ? ' );
		$this->assertEquals( $ph[0], 1 );

		$ph = array();
		$retval = $method->invokeArgs($ulf, array( 'a.private', (int)1, 'boolean', &$ph ) );
		$this->assertEquals( $retval, ' AND a.private = ? ' );
		$this->assertEquals( $ph[0], 1 );

		$ph = array();
		$retval = $method->invokeArgs($ulf, array( 'a.private', 1, 'boolean', &$ph ) );
		$this->assertEquals( $retval, ' AND a.private = ? ' );
		$this->assertEquals( $ph[0], 1 );

		$ph = array();
		$retval = $method->invokeArgs($ulf, array( 'a.private', 1.00, 'boolean', &$ph ) );
		$this->assertEquals( $retval, ' AND a.private = ? ' );
		$this->assertEquals( $ph[0], 1 );

		$ph = array();
		$retval = $method->invokeArgs($ulf, array( 'a.private', '1', 'boolean', &$ph ) );
		$this->assertEquals( $retval, ' AND a.private = ? ' );
		$this->assertEquals( $ph[0], 1 );

		$ph = array();
		$retval = $method->invokeArgs($ulf, array( 'a.private', 'TRUE', 'boolean', &$ph ) );
		$this->assertEquals( $retval, ' AND a.private = ? ' );
		$this->assertEquals( $ph[0], 1 );


		//Boolean FALSE
		$ph = array();
		$retval = $method->invokeArgs($ulf, array( 'a.private', (bool)FALSE, 'boolean', &$ph ) );
		$this->assertEquals( $retval, ' AND a.private = ? ' );
		$this->assertEquals( $ph[0], 0 );

		$ph = array();
		$retval = $method->invokeArgs($ulf, array( 'a.private', (int)0, 'boolean', &$ph ) );
		$this->assertEquals( $retval, ' AND a.private = ? ' );
		$this->assertEquals( $ph[0], 0 );

		$ph = array();
		$retval = $method->invokeArgs($ulf, array( 'a.private', 0, 'boolean', &$ph ) );
		$this->assertEquals( $retval, ' AND a.private = ? ' );
		$this->assertEquals( $ph[0], 0 );

		$ph = array();
		$retval = $method->invokeArgs($ulf, array( 'a.private', 0.00, 'boolean', &$ph ) );
		$this->assertEquals( $retval, ' AND a.private = ? ' );
		$this->assertEquals( $ph[0], 0 );

		$ph = array();
		$retval = $method->invokeArgs($ulf, array( 'a.private', '0', 'boolean', &$ph ) );
		$this->assertEquals( $retval, ' AND a.private = ? ' );
		$this->assertEquals( $ph[0], 0 );

		$ph = array();
		$retval = $method->invokeArgs($ulf, array( 'a.private', 'FALSE', 'boolean', &$ph ) );
		$this->assertEquals( $retval, ' AND a.private = ? ' );
		$this->assertEquals( $ph[0], 0 );
	}

	function testTransactionNestingA() {
		$uf = new UserFactory();

		$this->assertEquals( $uf->db->transCnt, 0 );
		$this->assertEquals( $uf->db->transOff, 0 );
		$this->assertEquals( $uf->db->_transOK, TRUE );


		$uf->StartTransaction();
		$this->assertEquals( $uf->db->transCnt, 1 );
		$this->assertEquals( $uf->db->transOff, 1 );

		$uf->StartTransaction();
		$this->assertEquals( $uf->db->transCnt, 1 );
		$this->assertEquals( $uf->db->transOff, 2 );

		$uf->StartTransaction();
		$this->assertEquals( $uf->db->transCnt, 1 );
		$this->assertEquals( $uf->db->transOff, 3 );

		//$uf->FailTransaction();
		$uf->CommitTransaction();
		$this->assertEquals( $uf->db->transCnt, 1 );
		$this->assertEquals( $uf->db->transOff, 2 );

		$uf->CommitTransaction();
		$this->assertEquals( $uf->db->transCnt, 1 );
		$this->assertEquals( $uf->db->transOff, 1 );

		$uf->CommitTransaction();
		$this->assertEquals( $uf->db->transCnt, 0 );
		$this->assertEquals( $uf->db->transOff, 0 );

		$this->assertEquals( $uf->db->transCnt, 0 );
		$this->assertEquals( $uf->db->transOff, 0 );
		$this->assertEquals( $uf->db->_transOK, TRUE );
	}

	function testTransactionNestingB() {
		$uf = new UserFactory();

		$this->assertEquals( $uf->db->transCnt, 0 );
		$this->assertEquals( $uf->db->transOff, 0 );
		$this->assertEquals( $uf->db->_transOK, TRUE );


		$uf->StartTransaction();
		$this->assertEquals( $uf->db->transCnt, 1 );
		$this->assertEquals( $uf->db->transOff, 1 );
		$this->assertEquals( $uf->db->_transOK, TRUE );

		$uf->StartTransaction();
		$this->assertEquals( $uf->db->transCnt, 1 );
		$this->assertEquals( $uf->db->transOff, 2 );
		$this->assertEquals( $uf->db->_transOK, TRUE );

		$uf->StartTransaction();
		$this->assertEquals( $uf->db->transCnt, 1 );
		$this->assertEquals( $uf->db->transOff, 3 );
		$this->assertEquals( $uf->db->_transOK, TRUE );

		$uf->FailTransaction();
		$this->assertEquals( $uf->db->transCnt, 1 );
		$this->assertEquals( $uf->db->transOff, 3 );
		$this->assertEquals( $uf->db->_transOK, FALSE );

		$uf->CommitTransaction();
		$this->assertEquals( $uf->db->transCnt, 1 );
		$this->assertEquals( $uf->db->transOff, 2 );
		$this->assertEquals( $uf->db->_transOK, FALSE );

		$uf->CommitTransaction();
		$this->assertEquals( $uf->db->transCnt, 1 );
		$this->assertEquals( $uf->db->transOff, 1 );
		$this->assertEquals( $uf->db->_transOK, FALSE );

		$uf->CommitTransaction();
		$this->assertEquals( $uf->db->transCnt, 0 );
		$this->assertEquals( $uf->db->transOff, 0 );
		$this->assertEquals( $uf->db->_transOK, FALSE );


		$this->assertEquals( $uf->db->transCnt, 0 );
		$this->assertEquals( $uf->db->transOff, 0 );
		$this->assertEquals( $uf->db->_transOK, FALSE );
	}

	function testTransactionNestingC() {
		$uf = new UserFactory();

		$this->assertEquals( $uf->db->transCnt, 0 );
		$this->assertEquals( $uf->db->transOff, 0 );
		$this->assertEquals( $uf->db->_transOK, TRUE );


		$uf->StartTransaction();
		$this->assertEquals( $uf->db->transCnt, 1 );
		$this->assertEquals( $uf->db->transOff, 1 );
		$this->assertEquals( $uf->db->_transOK, TRUE );

		$uf->StartTransaction();
		$this->assertEquals( $uf->db->transCnt, 1 );
		$this->assertEquals( $uf->db->transOff, 2 );
		$this->assertEquals( $uf->db->_transOK, TRUE );

		$uf->StartTransaction();
		$this->assertEquals( $uf->db->transCnt, 1 );
		$this->assertEquals( $uf->db->transOff, 3 );
		$this->assertEquals( $uf->db->_transOK, TRUE );

		$uf->FailTransaction();
		$this->assertEquals( $uf->db->transCnt, 1 );
		$this->assertEquals( $uf->db->transOff, 3 );
		$this->assertEquals( $uf->db->_transOK, FALSE );

		$uf->CommitTransaction( TRUE ); //Unest all transactions.
		$this->assertEquals( $uf->db->transCnt, 0 );
		$this->assertEquals( $uf->db->transOff, 0 );
		$this->assertEquals( $uf->db->_transOK, FALSE );


		$this->assertEquals( $uf->db->transCnt, 0 );
		$this->assertEquals( $uf->db->transOff, 0 );
		$this->assertEquals( $uf->db->_transOK, FALSE );
	}

	function testTransactionNestingC2() {
		$uf = new UserFactory();

		$this->assertEquals( $uf->db->transCnt, 0 );
		$this->assertEquals( $uf->db->transOff, 0 );
		$this->assertEquals( $uf->db->_transOK, TRUE );


		$uf->StartTransaction();
		$uf->StartTransaction();
		$uf->StartTransaction();
		$uf->StartTransaction();
		$uf->StartTransaction();
		$uf->StartTransaction();
		$uf->StartTransaction();
		$uf->StartTransaction();
		$uf->StartTransaction();
		$uf->FailTransaction();

		$uf->CommitTransaction( TRUE ); //Unest all transactions.

		$this->assertEquals( $uf->db->transCnt, 0 );
		$this->assertEquals( $uf->db->transOff, 0 );
		$this->assertEquals( $uf->db->_transOK, FALSE );
	}

	function testTransactionNestingC3() {
		$uf = new UserFactory();

		$this->assertEquals( $uf->db->transCnt, 0 );
		$this->assertEquals( $uf->db->transOff, 0 );
		$this->assertEquals( $uf->db->_transOK, TRUE );


		$uf->StartTransaction();
		$uf->StartTransaction();
		$uf->StartTransaction();
		$uf->StartTransaction();
		$uf->StartTransaction();
		$uf->StartTransaction();
		$uf->StartTransaction();
		$uf->StartTransaction();
		$uf->StartTransaction();
		$uf->FailTransaction();
		$uf->FailTransaction();
		$uf->FailTransaction();

		$uf->CommitTransaction( TRUE ); //Unest all transactions.

		$this->assertEquals( $uf->db->transCnt, 0 );
		$this->assertEquals( $uf->db->transOff, 0 );
		$this->assertEquals( $uf->db->_transOK, FALSE );
	}
}
