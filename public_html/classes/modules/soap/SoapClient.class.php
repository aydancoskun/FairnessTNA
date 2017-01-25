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
 * @package Modules\SOAP
 */
class FairnessSoapClient
{
    public $soap_client_obj = null;

    public function __construct()
    {
        $this->getSoapObject();

        return true;
    }

    public function getSoapObject()
    {
        if ($this->soap_client_obj == null) {
            if (function_exists('openssl_encrypt')) {
                $location = 'https://';
            } else {
                $location = 'http://';
            }
            $location .= 'github.com/aydancoskun/fairness'; // set this up

            $this->soap_client_obj = new SoapClient(null, array(
                    'location' => $location,
                    'uri' => 'urn:test',
                    'style' => SOAP_RPC,
                    'use' => SOAP_ENCODED,
                    'encoding' => 'UTF-8',
                    'connection_timeout' => 30,
                    'keep_alive' => false, //This should prevent "Error fetching HTTP headers" or "errno=10054 An existing connection was forcibly closed by the remote host." SOAP errors.
                    'trace' => 1,
                    'exceptions' => 0
                )
            );
        }

        return $this->soap_client_obj;
    }

    public function printSoapDebug()
    {
        echo "<pre>\n";
        echo "Request :\n" . htmlspecialchars($this->getSoapObject()->__getLastRequest()) . "\n";
        echo "Response :\n" . htmlspecialchars($this->getSoapObject()->__getLastResponse()) . "\n";
        echo "</pre>\n";
    }

    public function ping()
    {
        return $this->getSoapObject()->ping();
    }

    //
    // Currency Data Feed functions
    //
    public function getCurrencyExchangeRates($company_id, $currency_arr, $base_currency)
    {
        if ($company_id == '') {
            return false;
        }

        if (!is_array($currency_arr)) {
            return false;
        }

        if ($base_currency == '') {
            return false;
        }

        $currency_rates = $this->getSoapObject()->getCurrencyExchangeRates(false, $company_id, $currency_arr, $base_currency);

        if (isset($currency_rates) and is_array($currency_rates) and count($currency_rates) > 0) {
            return $currency_rates;
        }

        return false;
    }

    public function getCurrencyExchangeRatesByDate($company_id, $currency_arr, $base_currency, $start_date = null, $end_date = null)
    {
        if ($company_id == '') {
            return false;
        }

        if (!is_array($currency_arr)) {
            return false;
        }

        if ($base_currency == '') {
            return false;
        }

        if ($start_date == '') {
            $start_date = time();
        }

        if ($end_date == '') {
            $end_date = time();
        }

        $currency_rates = $this->getSoapObject()->getCurrencyExchangeRatesByDate(false, $company_id, $currency_arr, $base_currency, $start_date, $end_date);

        if (isset($currency_rates) and is_array($currency_rates) and count($currency_rates) > 0) {
            return $currency_rates;
        }

        return false;
    }
}
