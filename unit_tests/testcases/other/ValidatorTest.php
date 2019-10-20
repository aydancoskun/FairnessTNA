<?php

/**
 * @group DateTime
 */
class ValidatorTest extends PHPUnit\Framework\TestCase {

	public function setUp(): void {
		Debug::text('Running setUp(): ', __FILE__, __LINE__, __METHOD__, 10);

		TTDate::setTimeZone('Etc/GMT+8', TRUE); //Due to being a singleton and PHPUnit resetting the state, always force the timezone to be set.

		//If using loadbalancer, we need to make a SQL query to initiate at least one connection to a database.
		//This is needed for testTimeZone() to work with the load balancer.
		global $db;
		$db->Execute( 'SELECT 1' );
	}

	public function tearDown(): void {
		Debug::text('Running tearDown(): ', __FILE__, __LINE__, __METHOD__, 10);
	}

	function testValidatorIsFloat() {
		TTi18n::setLocale( 'en_US' );

		$validator = new Validator();

		$this->assertEquals( $validator->isFloat( 'unit_test', 12.9 ), TRUE );
		$this->assertEquals( $validator->isFloat( 'unit_test', 12.91 ), TRUE );
		$this->assertEquals( $validator->isFloat( 'unit_test', 12.9123 ), TRUE );
		$this->assertEquals( $validator->isFloat( 'unit_test', 12.91234 ), TRUE );
		$this->assertEquals( $validator->isFloat( 'unit_test', '12.9' ), TRUE );
		$this->assertEquals( $validator->isFloat( 'unit_test', '12.91' ), TRUE );
		$this->assertEquals( $validator->isFloat( 'unit_test', '12.9123' ), TRUE );
		$this->assertEquals( $validator->isFloat( 'unit_test', '12.91234' ), TRUE );

		$this->assertEquals( $validator->isFloat( 'unit_test', -12.9 ), TRUE );
		$this->assertEquals( $validator->isFloat( 'unit_test', -12.91 ), TRUE );
		$this->assertEquals( $validator->isFloat( 'unit_test', -12.9123 ), TRUE );
		$this->assertEquals( $validator->isFloat( 'unit_test', -12.91234 ), TRUE );

		$this->assertEquals( $validator->isFloat( 'unit_test', '123.91' ), TRUE );
		$this->assertEquals( $validator->isFloat( 'unit_test', '1234.91' ), TRUE );
		$this->assertEquals( $validator->isFloat( 'unit_test', '30 000.91' ), TRUE );
		$this->assertEquals( $validator->isFloat( 'unit_test', '1 234.91' ), TRUE );
		$this->assertEquals( $validator->isFloat( 'unit_test', '1,234.91' ), TRUE );
		$this->assertEquals( $validator->isFloat( 'unit_test', '1, 234.91' ), TRUE );
		$this->assertEquals( $validator->isFloat( 'unit_test', ' 1, 234.91' ), TRUE );
		$this->assertEquals( $validator->isFloat( 'unit_test', ' 1, 234.91' ), TRUE );
		$this->assertEquals( $validator->isFloat( 'unit_test', ' 1, 234.91 ' ), TRUE );

		$this->assertEquals( $validator->isFloat( 'unit_test', '1 234.91' ), TRUE );
		$this->assertEquals( $validator->isFloat( 'unit_test', '1.234,91' ), TRUE );
		$this->assertEquals( $validator->isFloat( 'unit_test', '30 000,91' ), TRUE );
		$this->assertEquals( $validator->isFloat( 'unit_test', '1. 234,91' ), TRUE );
		$this->assertEquals( $validator->isFloat( 'unit_test', ' 1. 234,91' ), TRUE );
		$this->assertEquals( $validator->isFloat( 'unit_test', ' 1. 234,91' ), TRUE );
		$this->assertEquals( $validator->isFloat( 'unit_test', ' 1. 234,91 ' ), TRUE );

		$this->assertEquals( $validator->isFloat( 'unit_test', .91 ), TRUE );
		$this->assertEquals( $validator->isFloat( 'unit_test', ',91' ), TRUE );
		$this->assertEquals( $validator->isFloat( 'unit_test', 12,9 ), TRUE );
		$this->assertEquals( $validator->isFloat( 'unit_test', '12,9' ), TRUE );

		TTi18n::setLocale( 'es_ES' );
		if ( TTi18n::getThousandsSymbol() == '.' AND TTi18n::getDecimalSymbol() == ',' ) {
			$this->assertEquals( $validator->isFloat( 'unit_test', .91 ), TRUE );
			$this->assertEquals( $validator->isFloat( 'unit_test', ',91' ), TRUE );
			$this->assertEquals( $validator->isFloat( 'unit_test', 12,9 ), TRUE );
			$this->assertEquals( $validator->isFloat( 'unit_test', '12,9' ), TRUE );

			$this->assertEquals( $validator->isFloat( 'unit_test', '123.91' ), TRUE );
			$this->assertEquals( $validator->isFloat( 'unit_test', '1234.91' ), TRUE );
			$this->assertEquals( $validator->isFloat( 'unit_test', '1 234.91' ), TRUE );
			$this->assertEquals( $validator->isFloat( 'unit_test', '1,234.91' ), TRUE );
			$this->assertEquals( $validator->isFloat( 'unit_test', '1, 234.91' ), TRUE );
			$this->assertEquals( $validator->isFloat( 'unit_test', ' 1, 234.91' ), TRUE );
			$this->assertEquals( $validator->isFloat( 'unit_test', ' 1, 234.91' ), TRUE );
			$this->assertEquals( $validator->isFloat( 'unit_test', ' 1, 234.91 ' ), TRUE );

			$this->assertEquals( $validator->isFloat( 'unit_test', '1 234.91' ), TRUE );
			$this->assertEquals( $validator->isFloat( 'unit_test', '1.234,91' ), TRUE );
			$this->assertEquals( $validator->isFloat( 'unit_test', '1. 234,91' ), TRUE );
			$this->assertEquals( $validator->isFloat( 'unit_test', ' 1. 234,91' ), TRUE );
			$this->assertEquals( $validator->isFloat( 'unit_test', ' 1. 234,91' ), TRUE );
			$this->assertEquals( $validator->isFloat( 'unit_test', ' 1. 234,91 ' ), TRUE );
		}
	}

	function testValidatorStripNonFloat() {
		TTi18n::setLocale( 'en_US' );

		$validator = new Validator();

		$this->assertEquals( $validator->stripNonFloat( 12.9 ), 12.9 );
		$this->assertEquals( $validator->stripNonFloat( 12.91 ), 12.91 );
		$this->assertEquals( $validator->stripNonFloat( 12.9123 ), 12.9123 );
		$this->assertEquals( $validator->stripNonFloat( 12.91234 ), 12.91234 );
		$this->assertEquals( $validator->stripNonFloat( '12.9' ), '12.9' );
		$this->assertEquals( $validator->stripNonFloat( '12.91' ), '12.91' );
		$this->assertEquals( $validator->stripNonFloat( '12.9123' ), '12.9123' );
		$this->assertEquals( $validator->stripNonFloat( '12.91234' ), '12.91234' );

		$this->assertEquals( $validator->stripNonFloat( -12.9 ), -12.9 );
		$this->assertEquals( $validator->stripNonFloat( -12.91 ), -12.91 );
		$this->assertEquals( $validator->stripNonFloat( -12.9123 ), -12.9123 );
		$this->assertEquals( $validator->stripNonFloat( -12.91234 ), -12.91234 );

		$this->assertEquals( $validator->stripNonFloat( '-123.91' ), '-123.91' );
		$this->assertEquals( $validator->stripNonFloat( '123.91' ), '123.91' );
		$this->assertEquals( $validator->stripNonFloat( '1234.91' ), '1234.91' );
		$this->assertEquals( $validator->stripNonFloat( '1 234.91' ), '1234.91' );
		$this->assertEquals( $validator->stripNonFloat( '1,234.91' ), '1234.91' ); //Always strips commas out so it doesn't work in other locales, TTi18n::parseFloat() should be called before this.
		$this->assertEquals( $validator->stripNonFloat( '1, 234.91' ), '1234.91' ); //Always strips commas out so it doesn't work in other locales, TTi18n::parseFloat() should be called before this.
		$this->assertEquals( $validator->stripNonFloat( ' 1, 234.91' ), '1234.91' ); //Always strips commas out so it doesn't work in other locales, TTi18n::parseFloat() should be called before this.
		$this->assertEquals( $validator->stripNonFloat( ' 1, 234.91' ), '1234.91' ); //Always strips commas out so it doesn't work in other locales, TTi18n::parseFloat() should be called before this.
		$this->assertEquals( $validator->stripNonFloat( ' 1, 234.91 ' ), '1234.91' ); //Always strips commas out so it doesn't work in other locales, TTi18n::parseFloat() should be called before this.

		$this->assertEquals( $validator->stripNonFloat( '1 234.91' ), '1234.91' );
		$this->assertEquals( $validator->stripNonFloat( '1.234,91' ), '1.23491' ); //Always strips commas out so it doesn't work in other locales, TTi18n::parseFloat() should be called before this.
		$this->assertEquals( $validator->stripNonFloat( '1. 234,91' ), '1.23491' ); //Always strips commas out so it doesn't work in other locales, TTi18n::parseFloat() should be called before this.
		$this->assertEquals( $validator->stripNonFloat( ' 1. 234,91' ), '1.23491' ); //Always strips commas out so it doesn't work in other locales, TTi18n::parseFloat() should be called before this.
		$this->assertEquals( $validator->stripNonFloat( ' 1. 234,91' ), '1.23491' ); //Always strips commas out so it doesn't work in other locales, TTi18n::parseFloat() should be called before this.
		$this->assertEquals( $validator->stripNonFloat( ' 1. 234,91 ' ), '1.23491' ); //Always strips commas out so it doesn't work in other locales, TTi18n::parseFloat() should be called before this.

		$this->assertEquals( $validator->stripNonFloat( .91 ), .91 );
		$this->assertEquals( $validator->stripNonFloat( ',91' ), '91' ); //Always strips commas out so it doesn't work in other locales, TTi18n::parseFloat() should be called before this.
		$this->assertEquals( $validator->stripNonFloat( 12,9 ), 12 ); //Always strips commas out so it doesn't work in other locales, TTi18n::parseFloat() should be called before this.
		$this->assertEquals( $validator->stripNonFloat( '12,9' ), '129' ); //Always strips commas out so it doesn't work in other locales, TTi18n::parseFloat() should be called before this.

		$this->assertEquals( $validator->stripNonFloat( 'A123.91' ), '123.91' );
		$this->assertEquals( $validator->stripNonFloat( 'A123.91B' ), '123.91' );
		$this->assertEquals( $validator->stripNonFloat( '12A3.91' ), '123.91' );
		$this->assertEquals( $validator->stripNonFloat( '123A.91' ), '123.91' );
		$this->assertEquals( $validator->stripNonFloat( '123.A91' ), '123.91' );

		$this->assertEquals( $validator->stripNonFloat( '*&#$#\'"123.JKLFDJFL91' ), '123.91' );

		TTi18n::setLocale( 'es_ES' );
		if ( TTi18n::getThousandsSymbol() == '.' AND TTi18n::getDecimalSymbol() == ',' ) {
			$this->assertEquals( $validator->stripNonFloat( .91 ), .91 );
			$this->assertEquals( $validator->stripNonFloat( ',91' ), '91' ); //Always strips commas out so it doesn't work in other locales, TTi18n::parseFloat() should be called before this.
			$this->assertEquals( $validator->stripNonFloat( 12,9 ), 12 ); //Always strips commas out so it doesn't work in other locales, TTi18n::parseFloat() should be called before this.
			$this->assertEquals( $validator->stripNonFloat( '12,9' ), '129' ); //Always strips commas out so it doesn't work in other locales, TTi18n::parseFloat() should be called before this.

			$this->assertEquals( $validator->stripNonFloat( '123.91' ), '123.91' );
			$this->assertEquals( $validator->stripNonFloat( '1234.91' ), '1234.91' );
			$this->assertEquals( $validator->stripNonFloat( '1 234.91' ), '1234.91' );
			$this->assertEquals( $validator->stripNonFloat( '1,234.91' ), '1234.91' ); //Always strips commas out so it doesn't work in other locales, TTi18n::parseFloat() should be called before this.
			$this->assertEquals( $validator->stripNonFloat( '1, 234.91' ), '1234.91' ); //Always strips commas out so it doesn't work in other locales, TTi18n::parseFloat() should be called before this.
			$this->assertEquals( $validator->stripNonFloat( ' 1, 234.91' ), '1234.91' ); //Always strips commas out so it doesn't work in other locales, TTi18n::parseFloat() should be called before this.
			$this->assertEquals( $validator->stripNonFloat( ' 1, 234.91' ), '1234.91' ); //Always strips commas out so it doesn't work in other locales, TTi18n::parseFloat() should be called before this.
			$this->assertEquals( $validator->stripNonFloat( ' 1, 234.91 ' ), '1234.91' ); //Always strips commas out so it doesn't work in other locales, TTi18n::parseFloat() should be called before this.

			$this->assertEquals( $validator->stripNonFloat( '1 234.91' ), '1234.91' );
			$this->assertEquals( $validator->stripNonFloat( '1.234,91' ), '1.23491' ); //Always strips commas out so it doesn't work in other locales, TTi18n::parseFloat() should be called before this.
			$this->assertEquals( $validator->stripNonFloat( '1. 234,91' ), '1.23491' ); //Always strips commas out so it doesn't work in other locales, TTi18n::parseFloat() should be called before this.
			$this->assertEquals( $validator->stripNonFloat( ' 1. 234,91' ), '1.23491' ); //Always strips commas out so it doesn't work in other locales, TTi18n::parseFloat() should be called before this.
			$this->assertEquals( $validator->stripNonFloat( ' 1. 234,91' ), '1.23491' ); //Always strips commas out so it doesn't work in other locales, TTi18n::parseFloat() should be called before this.
			$this->assertEquals( $validator->stripNonFloat( ' 1. 234,91 ' ), '1.23491' ); //Always strips commas out so it doesn't work in other locales, TTi18n::parseFloat() should be called before this.
		}
	}
}
?>