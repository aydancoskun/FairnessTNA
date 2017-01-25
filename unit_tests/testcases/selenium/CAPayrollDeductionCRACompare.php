<?php
/**********************************************************************************
 * This file is part of "FairnessTNA", a Payroll and Time Management program.
 * FairnessTNA is copyright 2013-2017 Aydan Coskun (aydan.ayfer.coskun@gmail.com)
 * others. For full attribution and copyrights details see the COPYRIGHT file.
 *
 * FairnessTNA is free software; you can redistribute it and/or modify it under the
 * terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation, either version 3 of the License, or (at you option )
 * any later version.
 *
 * FairnessTNA is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE.  See the GNU Affero General Public License for more
 * details.
 *
 * You should have received a copy of the GNU Affero General Public License along
 * with this program; if not, see http://www.gnu.org/licenses or write to the Free
 * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301 USA.
 *********************************************************************************/

/**
 * @group CAPayrollDeductionCRACompareTest
 */
class CAPayrollDeductionCRACompareTest extends PHPUnit_Extensions_Selenium2TestCase
{
    public function setUp()
    {
        global $selenium_config;
        $this->selenium_config = $selenium_config;

        Debug::text('Running setUp(): ', __FILE__, __LINE__, __METHOD__, 10);

        require_once(Environment::getBasePath() . '/classes/payroll_deduction/PayrollDeduction.class.php');

        $this->tax_table_file = dirname(__FILE__) . '/../payroll_deduction/CAPayrollDeductionTest2017.csv';

        $this->company_id = PRIMARY_COMPANY_ID;

        $this->selenium_test_case_runs = 0;

        TTDate::setTimeZone('Etc/GMT+8', true); //Due to being a singleton and PHPUnit resetting the state, always force the timezone to be set.

        $this->setHost($selenium_config['host']);
        $this->setBrowser($selenium_config['browser']);
        $this->setBrowserUrl($selenium_config['default_url']);

        return true;
    }

    public function tearDown()
    {
        Debug::text('Running tearDown(): ', __FILE__, __LINE__, __METHOD__, 10);

        return true;
    }

    public function testCRAControl()
    {
        $args = array(
            'date' => strtotime('01-Jan-2016'),
            'province' => 'BC',
            'pay_period_schedule' => 26,
            'federal_claim' => 10000,
            'provincial_claim' => 10000,
            'gross_income' => 9933.99
        );
        $retarr = $this->CRAPayrollDeductionOnlineCalculator($args);

        $this->assertEquals(2428.10, $retarr['federal_deduction']);
        $this->assertEquals(1153.70, $retarr['provincial_deduction']);
        //$this->assertEquals( 485.07, $retarr['cpp_deduction'] );
        //$this->assertEquals( 186.76, $retarr['ei_deduction']  );

        return true;
    }

    public function CRAPayrollDeductionOnlineCalculator($args = array())
    {
        if (ENABLE_SELENIUM_REMOTE_TESTS != true) {
            return false;
        }

        Debug::Arr($args, 'Args: ', __FILE__, __LINE__, __METHOD__, 10);

        if (count($args) == 0) {
            return false;
        }

        try {
            if ($this->selenium_test_case_runs == 0) {
                $url = 'http://www.cra-arc.gc.ca/esrvc-srvce/tx/bsnss/pdoc-eng.html';
                Debug::text('Navigating to URL: ' . $url, __FILE__, __LINE__, __METHOD__, 10);
                $this->url($url);

                $ae = $this->byXPath("//*[@class='col-md-9 col-md-push-3']/p[7]/a[@class='btn btn-primary']");
                Debug::text('Active Element Text: ' . $ae->text(), __FILE__, __LINE__, __METHOD__, 10);
                $ae->click();

                $ae = $this->byId("goStep1");
                Debug::text('Active Element Text: ' . $ae->text(), __FILE__, __LINE__, __METHOD__, 10);
                $ae->click();
            } else {
                //$ae = $this->byId( "goNew" );
                $ae = $this->byXPath("/html/body/div/div/main/form/div[3]/div/input[2]");
                Debug::text('Active Element Text: ' . $ae->text(), __FILE__, __LINE__, __METHOD__, 10);
                $ae->click();
            }

            $province_options = array(
                'AB' => 8,
                'BC' => 9,
                'SK' => 7,
                'MB' => 6,
                'QC' => 4,
                'ON' => 5,
                'NL' => 0,
                'NB' => 3,
                'NS' => 1,
                'PE' => 2,
                'NT' => 11,
                'YT' => 10,
                'NU' => 12
            );
            Debug::Arr(Option::getByKey($args['province'], $province_options), 'Attempting to Select Province Value: ', __FILE__, __LINE__, __METHOD__, 10);
            $ae = $this->byId("province");
            //$this->select( $ae )->selectOptionByLabel( "British Columbia" );
            $this->select($ae)->selectOptionByValue(Option::getByKey($args['province'], $province_options));

            $pp_options = array(
                52 => 1,
                26 => 2,
                24 => 3,
            );
            $ae = $this->byId("payPeriod");
            //$this->select( $ae )->selectOptionByLabel( "Biweekly (26 pay periods a year)" );
            $this->select($ae)->selectOptionByValue(Option::getByKey($args['pay_period_schedule'], $pp_options));

            $ae = $this->byId("cmbFirstYear");
            $this->select($ae)->selectOptionByLabel(date('Y', $args['date']));

            $ae = $this->byId("cmbFirstMonth");
            $this->select($ae)->selectOptionByLabel(date('m', $args['date'])); //Leading 0

            $ae = $this->byId("cmbFirstDay");
            $this->select($ae)->selectOptionByLabel(date('d', $args['date'])); //Leading 0

            $ae = $this->byId("goStep2AddOption");
            Debug::text('Active Element Text: ' . $ae->text(), __FILE__, __LINE__, __METHOD__, 10);
            $ae->click();

            $ae = $this->byId("incomeTypeAmount");
            $this->waitUntil(function () {
                if ($this->byId("incomeTypeAmount")) {
                    return true;
                }
                return null;
            }, 5000);
            $ae->click();
            $this->keys($args['gross_income']); //Sometimes some keystrokes get missed, try putting a wait above here.

            $ae = $this->byId("goStep2Option");
            Debug::text('Active Element Text: ' . $ae->text(), __FILE__, __LINE__, __METHOD__, 10);
            $ae->click();


            if (isset($args['federal_claim'])) {
                $ae = $this->byId("claimCodeFed");
                $this->select($ae)->selectOptionByValue(($args['federal_claim'] == 0 ? 0 : 1)); //Only support 0=$1, 1=Basic Claim
            }

            if (isset($args['provincial_claim']) and $args['province'] != 'QC') { //QC doesn't have provincial claim code.
                $ae = $this->byId("claimCodeProv");
                $this->select($ae)->selectOptionByValue(($args['provincial_claim'] == 0 ? 0 : 1)); //Only support 0=$1, 1=Basic Claim
            }

            $result_row_offset = 1;
            if (isset($args['ytd_cpp'])) {
                $ae = $this->byId("yearToDatePeAmount");
                $ae->click();
                $this->keys($args['ytd_cpp']);
            }

            if (isset($args['ytd_cpp_earnings'])) {
                $ae = $this->byId("yearToDateCPPAmount");
                $ae->click();
                $this->keys($args['ytd_cpp_earnings']);
            }

            if (isset($args['ytd_ei'])) {
                $ae = $this->byId("yearToDateEIAmount");
                $ae->click();
                $this->keys($args['ytd_ei']);
            }

            if (isset($args['ytd_ei_earnings'])) {
                $ae = $this->byId("yearToDateIeAmount");
                $ae->click();
                $this->keys($args['ytd_ei_earnings']);
            }

            $ae = $this->byId("goResults");
            Debug::text('Active Element Text: ' . $ae->text(), __FILE__, __LINE__, __METHOD__, 10);
            $ae->click();

            //
            //Handle results here
            //
            $screenshot_file_name = '/tmp/cra_result_screenshot-' . $args['province'] . '-' . $args['federal_claim'] . '-' . $args['provincial_claim'] . '-' . $args['gross_income'] . '.png';
            file_put_contents($screenshot_file_name, $this->currentScreenshot());

            //Make sure the gross income matches first.
            $ae = $this->byXPath("/html/body/div/div/main/table[1]/tbody/tr[1]"); //Was: 1
            Debug::Text('AE Text (Gross Income) [1]: ' . $ae->text(), __FILE__, __LINE__, __METHOD__, 10);
            $ae = $this->byXPath("/html/body/div/div/main/table[1]/tbody/tr[1]/td[3]");
            Debug::Text('AE Text (Gross Income) [1]: ' . $ae->text() . ' Expecting: ' . $args['gross_income'], __FILE__, __LINE__, __METHOD__, 10);
            //$retarr['gross_inc'] = TTi18n::parseFloat( $ae->text() );
            $this->assertEquals(TTi18n::parseFloat($ae->text()), $args['gross_income']);

            $result_row_offset += 5;
            //Federal Tax
            $ae = $this->byXPath("/html/body/div/div/main/table[1]/tbody/tr[" . $result_row_offset . "]"); //Was: 7
            Debug::Text('AE Text (Federal) [' . $result_row_offset . ']: ' . $ae->text(), __FILE__, __LINE__, __METHOD__, 10);
            $ae = $this->byXPath("/html/body/div/div/main/table[1]/tbody/tr[" . $result_row_offset . "]/td[2]");
            Debug::Text('AE Text (Federal) [' . $result_row_offset . ']: ' . $ae->text(), __FILE__, __LINE__, __METHOD__, 10);
            $retarr['federal_deduction'] = TTi18n::parseFloat($ae->text());

            $result_row_offset += 1;
            //Provincial Tax
            $ae = $this->byXPath("/html/body/div/div/main/table[1]/tbody/tr[" . $result_row_offset . "]"); //Was: 8
            Debug::Text('AE Text (Province) [' . $result_row_offset . ']: ' . $ae->text(), __FILE__, __LINE__, __METHOD__, 10);
            $ae = $this->byXPath("/html/body/div/div/main/table[1]/tbody/tr[" . $result_row_offset . "]/td[2]");
            Debug::Text('AE Text (Province) [' . $result_row_offset . ']: ' . $ae->text(), __FILE__, __LINE__, __METHOD__, 10);
            $retarr['provincial_deduction'] = TTi18n::parseFloat($ae->text());

//			$result_row_offset += 2;
//			//CPP
//			$ae = $this->byXPath( "/html/body/div/div/main/table[1]/tbody/tr[" . $result_row_offset . "]" ); //Was: 10
//			Debug::Text( 'AE Text (CPP) [' . $result_row_offset . ']: ' . $ae->text(), __FILE__, __LINE__, __METHOD__, 10 );
//			$ae = $this->byXPath( "/html/body/div/div/main/table[1]/tbody/tr[" . $result_row_offset . "]/td[3]" );
//			Debug::Text( 'AE Text (CPP) [' . $result_row_offset . ']: ' . $ae->text(), __FILE__, __LINE__, __METHOD__, 10 );
//			$retarr['cpp_deduction'] = TTi18n::parseFloat( $ae->text() );
//
//			$result_row_offset += 1;
//			//EI
//			$ae = $this->byXPath( "/html/body/div/div/main/table[1]/tbody/tr[" . $result_row_offset . "]" ); //Was: 11
//			Debug::Text( 'AE Text (EI) [' . $result_row_offset . ']: ' . $ae->text(), __FILE__, __LINE__, __METHOD__, 10 );
//			$ae = $this->byXPath( "/html/body/div/div/main/table[1]/tbody/tr[" . $result_row_offset . "]/td[3]" );
//			Debug::Text( 'AE Text (EI) [' . $result_row_offset . ']: ' . $ae->text(), __FILE__, __LINE__, __METHOD__, 10 );
//			$retarr['ei_deduction'] = TTi18n::parseFloat( $ae->text() );

            //Debug::Arr( $this->source(), 'Raw Source: ', __FILE__, __LINE__, __METHOD__, 10);
            //sleep(5);
            //$this->click("name=goNew");

            $this->selenium_test_case_runs++;
        } catch (Exception $e) {
            Debug::Text('Exception: ' . $e->getMessage(), __FILE__, __LINE__, __METHOD__, 10);
            file_put_contents(tempnam('/tmp/', 'cra_result_screenshot') . '.png', $this->currentScreenshot());
            sleep(15);
        }

        if (isset($retarr)) {
            Debug::Arr($retarr, 'Retarr: ', __FILE__, __LINE__, __METHOD__, 10);
            return $retarr;
        }

        Debug::Text('ERROR: Returning FALSE!', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function testCRAFromCSVFile()
    {
        $this->assertEquals(file_exists($this->tax_table_file), true);

        $test_rows = Misc::parseCSV($this->tax_table_file, true);

        $total_rows = (count($test_rows) + 1);
        $i = 2;
        foreach ($test_rows as $row) {
            Debug::text('Province: ' . $row['province'] . ' Income: ' . $row['gross_income'], __FILE__, __LINE__, __METHOD__, 10);
            if (isset($row['gross_income']) and isset($row['low_income']) and isset($row['high_income'])
                and $row['gross_income'] == '' and $row['low_income'] != '' and $row['high_income'] != ''
            ) {
                $row['gross_income'] = ($row['low_income'] + (($row['high_income'] - $row['low_income']) / 2));
            }
            if ($row['country'] != '' and $row['gross_income'] != '') {
                //echo $i.'/'.$total_rows.'. Testing Province: '. $row['province'] .' Income: '. $row['gross_income'] ."\n";
                Debug::text($i . '/' . $total_rows . '. Testing Province: ' . $row['province'] . ' Income: ' . $row['gross_income'], __FILE__, __LINE__, __METHOD__, 10);

                $args = array(
                    'date' => strtotime($row['date']),
                    'province' => $row['province'],
                    'pay_period_schedule' => 26,
                    'federal_claim' => $row['federal_claim'],
                    'provincial_claim' => $row['provincial_claim'],
                    'gross_income' => $this->mf($row['gross_income']),
                );


                //Debug::Arr( $row, 'aFinal Row: ', __FILE__, __LINE__, __METHOD__, 10);
                $tmp_cra_data = $this->CRAPayrollDeductionOnlineCalculator($args);
                if (is_array($tmp_cra_data)) {
                    $retarr[] = array_merge($row, $tmp_cra_data);

                    //Debug::Arr( $retarr, 'bFinal Row: ', __FILE__, __LINE__, __METHOD__, 10);
                    //sleep(2); //Should we be friendly to the Gov't server?
                    //if ( $i > 50 ) {
                    //	break;
                    //}
                } else {
                    Debug::text('ERROR! Data from CRA is invalid!', __FILE__, __LINE__, __METHOD__, 10);
                    break;
                }
            }

            $i++;
        }

        //generate column array.
        $column_keys = array_keys($retarr[0]);
        foreach ($column_keys as $column_key) {
            $columns[$column_key] = $column_key;
        }

        //var_dump($test_data);
        //var_dump($retarr);
        //echo Misc::Array2CSV( $retarr, $columns, FALSE, TRUE );
        file_put_contents(dirname($this->tax_table_file) . DIRECTORY_SEPARATOR . 'CAPayrollDeductionCRATest2017.csv', Misc::Array2CSV($retarr, $columns, false, true), FILE_APPEND);

        //Make sure all rows are tested.
        $this->assertEquals($total_rows, ($i - 1));
    }

    //
    // January 2017
    //

    public function mf($amount)
    {
        return Misc::MoneyFormat($amount, false);
    }
}
