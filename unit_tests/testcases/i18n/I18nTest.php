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
 * @group i18nTest
 */
class i18nTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        TTi18n::setLocale('en_CA');
    }

    public function tearDown()
    {
        TTi18n::setLocale('en_CA');
    }


    public function testTryLocale()
    {
        TTi18n::setLocale('en_US');
        $this->assertEquals(substr(TTi18n::getLocale(), 0, 5), 'en_US');

        $this->assertEquals(TTi18n::getLanguage(), 'en');

        $this->assertEquals(TTi18n::getCountry(), 'US');

        $this->assertEquals(TTi18n::getCurrencySymbol('USD'), '$');

        $this->assertEquals(TTi18n::tryLocale('en_CA.UTF-8'), 'en_CA.UTF-8');
        $this->assertEquals(TTi18n::tryLocale('en_US.utf-8'), 'en_US.utf-8');
        $this->assertEquals(TTi18n::tryLocale('en_US.utf8'), 'en_US.utf8');
        $this->assertEquals(TTi18n::tryLocale('en_US.uTf-8'), 'en_US.uTf-8');
        $this->assertEquals(TTi18n::tryLocale('NOT REAL'), false);
        //$this->assertEquals( TTi18n::tryLocale( 'en_CA' ), FALSE ); //Some installs may have this locale.
    }

    public function testMisc()
    {
        if (extension_loaded('intl') == false) {
            return true;
        }

        //$expected_lang_arr = array('en' => 'English', 'da' => 'Danish (UO)', 'de' => 'German (UO)', 'es' => 'Spanish (UO)', 'id' => 'Indonesian (UO)', 'it' => 'Italian (UO)', 'fr' => 'French (UO)', 'pt' => 'Portuguese (UO)', 'ar' => 'Arabic (UO)', 'zh' => 'Chinese (UO)', 'yi' => 'Yiddish (UO)');
        //$expected_lang_arr = array('en' => 'English', 'da' => 'Dansk (UO)', 'de' => 'Deutsch (UO)', 'es' => 'Español (UO)', 'id' => 'Bahasa Indonesia (UO)', 'it' => 'Italiano (UO)', 'fr' => 'Français (UO)', 'pt' => 'Português (UO)', 'ar' => 'العربية (UO)', 'zh' => '中文 (UO)', 'yi' => 'ייִדיש (UO)');
        $expected_lang_arr = array('en' => 'English', 'da' => 'Dansk (UO)', 'de' => 'Deutsch (UO)', 'es' => 'Español (UO)', 'id' => 'Bahasa Indonesia (UO)', 'it' => 'Italiano (UO)', 'fr' => 'Français (UO)', 'pt' => 'Português (UO)', 'ar' => 'العربية (UO)', 'zh' => '中文 (UO)');

        TTi18n::setLocale('en_CA');
        $lang_arr = TTi18n::getLanguageArray();
        array_pop($lang_arr); //Pop off Yiddish as it may not be installed everywhere.
        $this->assertEquals($lang_arr, $expected_lang_arr);

        TTi18n::setLocale('es_ES');
        $lang_arr = TTi18n::getLanguageArray();
        array_pop($lang_arr); //Pop off Yiddish as it may not be installed everywhere.
        $this->assertEquals($lang_arr, $expected_lang_arr);

        TTi18n::setLocale('en_CA');
        $this->assertEquals(TTi18n::getNormalizedLocale(), 'en_US');
        $this->assertEquals(TTi18n::getLocaleCookie(), false);
        $this->assertEquals(TTi18n::getLanguageFromLocale(), 'en');
        $this->assertEquals(TTi18n::getLanguageFromLocale('it_CH.utf8'), 'it');
    }

    public function testFormatCurrency()
    {
        if (extension_loaded('intl') == false) {
            return true;
        }

        //Canadian Dollars - INTL wants to us CA$ rather than just $.
        TTi18n::setLocale('en_CA');
        $this->assertEquals(TTi18n::formatCurrency(8.9901), '$8.99');
        $this->assertEquals(TTi18n::formatCurrency(-87.9901), '-$87.99');
        $this->assertEquals(TTi18n::formatCurrency(8799.012345, 'JPY', 0), '¥8,799');

        //US Dollars
        TTi18n::setLocale('en_US');
        $this->assertEquals(TTi18n::formatCurrency(8.9901), '$8.99');
        $this->assertEquals(TTi18n::formatCurrency(-87.9901), '-$87.99');
        $this->assertEquals(TTi18n::formatCurrency(8799.012345, 'JPY', 0), '¥8,799');

        //test euros
        TTi18n::setLocale('it_IT');
        if (TTi18n::getThousandsSymbol() == '.' and TTi18n::getDecimalSymbol() == ',') {
            $this->assertEquals(TTi18n::formatCurrency(8.9901), '8,99 €');
            $this->assertEquals(TTi18n::formatCurrency(-87.9901), '-87,99 €');
            $this->assertEquals(TTi18n::formatCurrency(1888799.012345), '1.888.799,01 €');
            $this->assertEquals(TTi18n::formatCurrency(8799.012345, 'EUR', 1), 'EUR 8.799,01 €');
            $this->assertEquals(TTi18n::formatCurrency(8799.012345, 'EUR', 0), '8.799,01 €');
        } else {
            Debug::Text('ERROR: Locale differs, skipping unit tests...', __FILE__, __LINE__, __METHOD__, 1);
        }

        TTi18n::setLocale('es_ES');
        if (TTi18n::getThousandsSymbol() == '.' and TTi18n::getDecimalSymbol() == ',') {
            $this->assertEquals(TTi18n::formatCurrency(8.9901), '8,99 €');
            $this->assertEquals(TTi18n::formatCurrency(-87.9901), '-87,99 €');
            $this->assertEquals(TTi18n::formatCurrency(1888799.012345), '1.888.799,01 €');
            $this->assertEquals(TTi18n::formatCurrency(8799.012345, 'EUR', 1), 'EUR 8.799,01 €');
            $this->assertEquals(TTi18n::formatCurrency(8799.012345, 'EUR', 0), '8.799,01 €');
        } else {
            Debug::Text('ERROR: Locale differs, skipping unit tests...', __FILE__, __LINE__, __METHOD__, 1);
        }
    }

    public function testNumberFormat()
    {
        //english locales use #,###.##
        TTi18n::setLocale('en_CA');
        $this->assertEquals(TTi18n::formatNumber(8.9901), '8.99');
        $this->assertEquals(TTi18n::formatNumber(-87.9901), '-87.99');
        $this->assertEquals(TTi18n::formatNumber(-87.9991), '-88');
        $this->assertEquals(TTi18n::formatNumber(8987.990122), '8,987.99');
        $this->assertEquals(TTi18n::formatNumber(1234.990122), '1,234.99');
        $this->assertEquals(TTi18n::formatNumber(123456.990122), '123,456.99');
        $this->assertEquals(TTi18n::formatNumber(123456789.990122), '123,456,789.99');
        $this->assertEquals(TTi18n::formatNumber(12345.12345), '12,345.12');
        $this->assertEquals(TTi18n::formatNumber(-12345.12345), '-12,345.12');
        $this->assertEquals(TTi18n::formatNumber(123.12345), '123.12');
        $this->assertEquals(TTi18n::formatNumber(-123.12345), '-123.12');

        $this->assertEquals(TTi18n::formatNumber(-87.990122, true, 2, 6), '-87.990122');
        $this->assertEquals(TTi18n::formatNumber(-87.9000, true, 2, 4), '-87.9000');
        $this->assertEquals(TTi18n::formatNumber(-87.9901, true, 2, 4), '-87.9901');
        $this->assertEquals(TTi18n::formatNumber(1234.990122, true, 2, 4), '1,234.9901');
        $this->assertEquals(TTi18n::formatNumber(123456.990122, true, 3, 5), '123,456.99012');
        $this->assertEquals(TTi18n::formatNumber(123456789.990122, true, 2, 4), '123,456,789.9901');

        //spanish locales show numbers as # ###,##
        TTi18n::setLocale('es_ES');
        if (TTi18n::getThousandsSymbol() == '.' and TTi18n::getDecimalSymbol() == ',') {
            $this->assertEquals(TTi18n::formatNumber(8.9901), '8,99');
            $this->assertEquals(TTi18n::formatNumber(-87.9901), '-87,99');
            $this->assertEquals(TTi18n::formatNumber(-87.9901), '-87,99');
            $this->assertEquals(TTi18n::formatNumber(8987.990122), '8.987,99');
            $this->assertEquals(TTi18n::formatNumber(1234.990122), '1.234,99');
            $this->assertEquals(TTi18n::formatNumber(123456789.990122), '123.456.789,99');

            $this->assertEquals(TTi18n::formatNumber(-87.99015555, true, 2, 6), '-87,990156'); //rounding
            $this->assertEquals(TTi18n::formatNumber(-87.9000, true, 2, 4), '-87,90');
            $this->assertEquals(TTi18n::formatNumber(-87.990155, true, 2, 4), '-87,9902'); //rounding
            $this->assertEquals(TTi18n::formatNumber(1234.990122, true, 2, 4), '1.234,9901');
            $this->assertEquals(TTi18n::formatNumber(123456789.990122, true, 2, 4), '123.456.789,9901');
        } else {
            Debug::Text('ERROR: Locale differs, skipping unit tests...', __FILE__, __LINE__, __METHOD__, 1);
        }

        //comparing TTi18n::formatNumber to Misc::MoneyFormat due to high usage of the MoneyFormat() in existing code.
        TTi18n::setLocale('en_CA');
        $this->assertEquals(TTi18n::formatNumber(12345.152, true, 2, 2), '12,345.15');
        $this->assertEquals(TTi18n::formatNumber(12345.151, false), '12,345.15');
        $this->assertEquals(TTi18n::formatNumber(12345.15, true), '12,345.15');
        $this->assertEquals(TTi18n::formatNumber(12345.15, false), '12,345.15');
        $this->assertEquals(TTi18n::formatNumber(12345.1, true), '12,345.10');
        $this->assertEquals(TTi18n::formatNumber(12345.5, false), '12,345.5');
        $this->assertEquals(TTi18n::formatNumber(12345.12345), '12,345.12');
        $this->assertEquals(TTi18n::formatNumber(-12345.12345), '-12,345.12');
        $this->assertEquals(TTi18n::formatNumber(123.12345), '123.12');
        $this->assertEquals(TTi18n::formatNumber(-123.12345), '-123.12');
        $this->assertEquals(TTi18n::formatNumber(123), '123');
        $this->assertEquals(TTi18n::formatNumber(-123), '-123');

        $this->assertEquals(Misc::MoneyFormat(12345.152, true), '12,345.15');
        $this->assertEquals(Misc::MoneyFormat(12345.151, false), '12345.15');
        $this->assertEquals(Misc::MoneyFormat(12345.15, true), '12,345.15');
        $this->assertEquals(Misc::MoneyFormat(12345.15, false), '12345.15');
        $this->assertEquals(Misc::MoneyFormat(12345.1, true), '12,345.10');
        $this->assertEquals(Misc::MoneyFormat(12345.5, false), '12345.50');
        $this->assertEquals(Misc::MoneyFormat(12345.12345), '12,345.12');
        $this->assertEquals(Misc::MoneyFormat(-12345.12345), '-12,345.12');
        $this->assertEquals(Misc::MoneyFormat(123.12345), '123.12');
        $this->assertEquals(Misc::MoneyFormat(-123.12345), '-123.12');
    }

    public function testBeforeAndAfterDecimal()
    {
        $this->assertEquals(Misc::getBeforeDecimal(12345.92345), '12345');
        $this->assertEquals(Misc::getBeforeDecimal(-12345.92345), '-12345');
        $this->assertEquals(Misc::getAfterDecimal(12345.92345, false), '92345');
        $this->assertEquals(Misc::getAfterDecimal(-12345.92345, false), '92345');

        TTi18n::setLocale('es_ES');
        $this->assertEquals(Misc::getBeforeDecimal(12345.92345), '12345');
        $this->assertEquals(Misc::getBeforeDecimal(-12345.92345), '-12345');
        $this->assertEquals(Misc::getAfterDecimal(12345.92345, false), '92345');
        $this->assertEquals(Misc::getAfterDecimal(-12345.92345, false), '92345');
    }

    public function testParseFloatFunctions()
    {
        if (extension_loaded('intl') == false) {
            return true;
        }

        TTi18n::setLocale('en_US');
        $this->assertEquals(TTI18n::parseFloat('1,234.123'), '1234.123');
        $this->assertEquals(TTI18n::parseFloat('1, 234.123'), '1234.123');
        $this->assertEquals(TTI18n::parseFloat('12.123'), '12.123');
        $this->assertEquals(TTI18n::parseFloat((float)12.123), '12.123');
        $this->assertEquals(TTI18n::parseFloat('0.00'), '0');
        $this->assertEquals(TTI18n::parseFloat('0'), '0');
        $this->assertEquals(TTI18n::parseFloat(0), '0');
        $this->assertEquals(TTI18n::parseFloat(''), '0');
        $this->assertEquals(TTI18n::parseFloat(true), '0');
        $this->assertEquals(TTI18n::parseFloat(false), '0');
        $this->assertEquals(TTI18n::parseFloat(null), '0');

        TTi18n::setLocale('fr_CA');
        $this->assertEquals(TTI18n::parseFloat('1 234,123'), '1234.123');
        $this->assertEquals(TTI18n::parseFloat('12,123'), '12.123');
        $this->assertEquals(TTI18n::parseFloat('0,00'), '0');
        $this->assertEquals(TTI18n::parseFloat((float)12.123), '12.123'); //If its input as an actual float value, it shouldn't be touched.

        TTi18n::setLocale('es_ES');
        if (TTi18n::getThousandsSymbol() == '.' and TTi18n::getDecimalSymbol() == ',') {
            $this->assertEquals(TTI18n::parseFloat('1.234,123'), '1234.123');
            $this->assertEquals(TTI18n::parseFloat('1. 234,123'), '1234.123');
            $this->assertEquals(TTI18n::parseFloat('12,123'), '12.123');
            $this->assertEquals(TTI18n::parseFloat('0,00'), '0');
            $this->assertEquals(TTI18n::parseFloat((float)12.123), '12.123'); //If its input as an actual float value, it shouldn't be touched.
        } else {
            Debug::Text('ERROR: Locale differs, skipping unit tests...', __FILE__, __LINE__, __METHOD__, 1);
        }
    }

    public function testRemoveTrailingZeros()
    {
        TTi18n::setLocale('en_US');
        $this->assertEquals(Misc::removeTrailingZeros((float)123.45, 2), (float)123.45);
        $this->assertEquals(Misc::removeTrailingZeros((float)123.45, 0), (float)123.45);
        $this->assertEquals(Misc::removeTrailingZeros((float)123.450000, 2), (float)123.45);
        $this->assertEquals(Misc::removeTrailingZeros('123.450000', 2), (float)123.45);
        $this->assertEquals(Misc::removeTrailingZeros('test', 2), 'test'); //Make sure if it can't work with the input value, we just output it untouched.

        TTi18n::setLocale('fr_CA');
        $this->assertEquals(Misc::removeTrailingZeros((float)123.45, 2), (float)123.45);
        $this->assertEquals(Misc::removeTrailingZeros((float)123.45, 0), (float)123.45);
        $this->assertEquals(Misc::removeTrailingZeros((float)123.450000, 2), (float)123.45);
        $this->assertEquals(Misc::removeTrailingZeros('123.450000', 2), (float)123.45);
        $this->assertEquals(Misc::removeTrailingZeros('test', 2), 'test'); //Make sure if it can't work with the input value, we just output it untouched.

        TTi18n::setLocale('es_ES');
        if (TTi18n::getThousandsSymbol() == '.' and TTi18n::getDecimalSymbol() == ',') {
            $this->assertEquals(Misc::removeTrailingZeros((float)123.45, 2), (float)123.45);
            $this->assertEquals(Misc::removeTrailingZeros((float)123.45, 0), (float)123.45);
            $this->assertEquals(Misc::removeTrailingZeros((float)123.450000, 2), (float)123.45);
            $this->assertEquals(Misc::removeTrailingZeros('123.450000', 2), (float)123.45);
            $this->assertEquals(Misc::removeTrailingZeros('test', 2), 'test'); //Make sure if it can't work with the input value, we just output it untouched.
        } else {
            Debug::Text('ERROR: Locale differs, skipping unit tests...', __FILE__, __LINE__, __METHOD__, 1);
        }
    }

    public function testSetLocale()
    {
        $this->assertEquals(TTi18n::setLocale('it_CH'), true);
        $this->assertEquals(TTi18n::getLocale(), 'it_CH.UTF-8');
        $this->assertEquals(TTi18n::getCurrencySymbol('EUR'), '€');
        $this->assertEquals(TTi18n::setLocale('en_CA'), true);
        $this->assertEquals(TTi18n::getLocale(), 'en_CA.UTF-8');
        $this->assertEquals(TTi18n::getCurrencySymbol('CAD'), '$');
    }

    public function testTranslations()
    {
        TTi18n::setLocale('es_ES');
        $this->assertEquals(TTi18n::getText('Employee'), 'Empleado');
        TTi18n::setLocale('en_CA');
        $this->assertEquals(TTi18n::getText('Employee'), 'Employee');
        TTi18n::setLocale('yi_US');
        $this->assertEquals(TTi18n::getText('Employee'), 'Z');
        TTi18n::setLocale('fr_CA');
        $this->assertEquals(TTi18n::getText('Saved Reports'), 'Rapports sauvs');
        TTi18n::setLocale('fr_FR');
        $this->assertEquals(TTi18n::getText('Saved Reports'), 'Enregistrer le rapport');

        TTi18n::setLocale('ar_EG');
        $this->assertEquals(TTi18n::getText('Saved Reports'), 'Saved Reports'); //valid locale with no translations returns the original string.
        TTi18n::setLocale('zz_ZZ');
        $this->assertEquals(TTi18n::getText('Saved Reports'), 'Saved Reports'); //invalid locale returns the original string.

        $this->assertEquals(TTi18n::detectUTF8('The quick brown fox jumped over the lazy dog 1234567890!@#$%^&*()_+'), false);
        $this->assertEquals(TTi18n::detectUTF8('ᚠᛇᚻ᛫ᛒᛦᚦ᛫ᚠᚱᚩᚠᚢᚱ᛫ᚠᛁᚱᚪ᛫ᚷᛖᚻᚹᛦᛚᚳᚢᛗ'), true);
    }

    public function testStringFunctions()
    {
        TTi18n::setLanguage('en');
        $this->assertEquals(TTi18n::strtolower('TesT'), 'test');

        TTi18n::setLanguage('fr');
        $this->assertEquals(TTi18n::strtolower('Cumulé'), 'cumulé');

        TTi18n::setLanguage('cn');
        $this->assertEquals(TTi18n::strtolower('壹'), '壹');
    }

    //30,000 currrency formats on existing code took less than a second.
    //30,000 currrency formats on new code took 1.24 seconds
    //500,000 currrency formats on new code took 8.2 seconds
    //500,000 currrency formats on old code took 9.95 seconds
//	function testNumberFormatBenchmark() {
//
//		TTi18n::setLocale('en_CA');
//		$start_benchmark = time();
//		for($i =0; $i<=500000; $i++){
//			$n = TTi18n::formatCurrency($i.'.55555', 'CAD', true);
//		}
//		$end_benchmark = time();
//		echo "time: " . ($end_benchmark - $start_benchmark);
//	}
}
