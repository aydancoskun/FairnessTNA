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
 * @package PayrollDeduction\CR
 */
class PayrollDeduction_CR_Data extends PayrollDeduction_Base
{
    public $db = null;
    public $income_tax_rates = array();
    public $country_primary_currency = 'CRC';

    public $federal_income_tax_rate_options = array(
        20070930 => array(
            10 => array(
                array('income' => 6096000, 'rate' => 0, 'constant' => 0),
                array('income' => 9144000, 'rate' => 10, 'constant' => 0),
                array('income' => 9144000, 'rate' => 15, 'constant' => 0),
            ),
        ),
        20060930 => array(
            10 => array(
                array('income' => 5616000, 'rate' => 0, 'constant' => 0),
                array('income' => 8424000, 'rate' => 10, 'constant' => 0),
                array('income' => 8424000, 'rate' => 15, 'constant' => 0),
            ),
        ),
    );

    public $federal_allowance = array(
        20060930 => 10560.00, //01-Oct-07
        20070930 => 11520.00  //01-Oct-07
    );

    public $federal_filing = array(
        20060930 => 15720.00, //01-Oct-07
        20070930 => 17040.00  //01-Oct-07
    );

    public function __construct()
    {
        global $db;

        $this->db = $db;

        return true;
    }

    public function getData()
    {
        global $cache;

        $country = $this->getCountry();

        $epoch = $this->getDate();
        $federal_status = $this->getFederalFilingStatus();
        if ($federal_status == '') {
            $federal_status = 10;
        }

        if ($epoch == null or $epoch == '') {
            $epoch = $this->getISODate(TTDate::getTime());
        }

        $this->income_tax_rates = false;
        if (isset($this->federal_income_tax_rate_options) and count($this->federal_income_tax_rate_options) > 0) {
            $prev_income = 0;
            $prev_rate = 0;
            $prev_constant = 0;

            $federal_income_tax_rate_options = $this->getDataFromRateArray($epoch, $this->federal_income_tax_rate_options);
            if (isset($federal_income_tax_rate_options[$federal_status])) {
                foreach ($federal_income_tax_rate_options[$federal_status] as $data) {
                    $this->income_tax_rates['federal'][] = array(
                        'prev_income' => $prev_income,
                        'income' => $data['income'],
                        'prev_rate' => ($prev_rate / 100),
                        'rate' => ($data['rate'] / 100),
                        'prev_constant' => $prev_constant,
                        'constant' => $data['constant']
                    );

                    $prev_income = $data['income'];
                    $prev_rate = $data['rate'];
                    $prev_constant = $data['constant'];
                }
            }
            unset($prev_income, $prev_rate, $prev_constant, $data, $federal_income_tax_rate_options);
        }

        return $this;
    }

    public function getFederalTaxTable($income)
    {
        $arr = $this->income_tax_rates['federal'];

        //Debug::Arr($arr, 'Federal tax table: ', __FILE__, __LINE__, __METHOD__, 10);
        return $arr;
    }

    public function getFederalAllowanceAmount($date)
    {
        $retarr = $this->getDataFromRateArray($this->getDate(), $this->federal_allowance);
        if ($retarr != false) {
            return $retarr;
        }

        return false;
    }

    public function getFederalFilingAmount($date)
    {
        $retarr = $this->getDataFromRateArray($this->getDate(), $this->federal_filing);

        if ($retarr != false) {
            return $retarr;
        }

        return false;
    }
}
