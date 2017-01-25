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


include_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'GovernmentForms_Base.class.php');

/**
 * @package GovernmentForms
 */
class GovernmentForms_CA extends GovernmentForms_Base
{
    public function filterMiddleName($value)
    {
        //Return just initial
        $value = preg_replace('/[^A-Za-z]/', '', $value); //Strip any non-alpha chars.
        $value = substr(trim($value), 0, 1);
        return $value;
    }

    public function filterCompanyAddress($value)
    {
        //Combine company address for multicell display.
        return Misc::formatAddress(null, $this->company_address1, $this->company_address2, $this->company_city, $this->company_province, $this->company_postal_code);
    }

    public function filterAddress($value)
    {
        //Combine company address for multicell display.
        return Misc::formatAddress(null, $this->address1, $this->address2, $this->city, $this->province, $this->postal_code, $this->country); //Include country in case they are outside of Canada.
    }

    public function filterPayrollAccountNumber($value)
    {
        $value = $this->formatPayrollAccountNumber($value);
        if ($this->getType() == 'employee') {
            $value = $this->payroll_account_number = '***************'; //Hide payroll account number on employees copy for security reasons.
        } else {
            $value = $this->payroll_account_number;
        }
        return $value;
    }

    public function formatPayrollAccountNumber($value)
    {
        $value = str_replace(' ', '', $value);
        return $value;
    }

    public function formatAlpha3CountryCode($country_code)
    {
        $alpha3_codes = array(
            'AD' => 'AND',
            'AE' => 'ARE',
            'AF' => 'AFG',
            'AG' => 'ATG',
            'AI' => 'AIA',
            'AL' => 'ALB',
            'AM' => 'ARM',
            'AO' => 'AGO',
            'AQ' => 'ATA',
            'AR' => 'ARG',
            'AS' => 'ASM',
            'AT' => 'AUT',
            'AU' => 'AUS',
            'AW' => 'ABW',
            'AX' => 'ALA',
            'AZ' => 'AZE',
            'BA' => 'BIH',
            'BB' => 'BRB',
            'BD' => 'BGD',
            'BE' => 'BEL',
            'BF' => 'BFA',
            'BG' => 'BGR',
            'BH' => 'BHR',
            'BI' => 'BDI',
            'BJ' => 'BEN',
            'BL' => 'BLM',
            'BM' => 'BMU',
            'BN' => 'BRN',
            'BO' => 'BOL',
            'BQ' => 'BES',
            'BR' => 'BRA',
            'BS' => 'BHS',
            'BT' => 'BTN',
            'BV' => 'BVT',
            'BW' => 'BWA',
            'BY' => 'BLR',
            'BZ' => 'BLZ',
            'CA' => 'CAN',
            'CC' => 'CCK',
            'CD' => 'COD',
            'CF' => 'CAF',
            'CG' => 'COG',
            'CH' => 'CHE',
            'CI' => 'CIV',
            'CK' => 'COK',
            'CL' => 'CHL',
            'CM' => 'CMR',
            'CN' => 'CHN',
            'CO' => 'COL',
            'CR' => 'CRI',
            'CU' => 'CUB',
            'CV' => 'CPV',
            'CW' => 'CUW',
            'CX' => 'CXR',
            'CY' => 'CYP',
            'CZ' => 'CZE',
            'DE' => 'DEU',
            'DJ' => 'DJI',
            'DK' => 'DNK',
            'DM' => 'DMA',
            'DO' => 'DOM',
            'DZ' => 'DZA',
            'EC' => 'ECU',
            'EE' => 'EST',
            'EG' => 'EGY',
            'EH' => 'ESH',
            'ER' => 'ERI',
            'ES' => 'ESP',
            'ET' => 'ETH',
            'FI' => 'FIN',
            'FJ' => 'FJI',
            'FK' => 'FLK',
            'FM' => 'FSM',
            'FO' => 'FRO',
            'FR' => 'FRA',
            'GA' => 'GAB',
            'GB' => 'GBR',
            'GD' => 'GRD',
            'GE' => 'GEO',
            'GF' => 'GUF',
            'GG' => 'GGY',
            'GH' => 'GHA',
            'GI' => 'GIB',
            'GL' => 'GRL',
            'GM' => 'GMB',
            'GN' => 'GIN',
            'GP' => 'GLP',
            'GQ' => 'GNQ',
            'GR' => 'GRC',
            'GS' => 'SGS',
            'GT' => 'GTM',
            'GU' => 'GUM',
            'GW' => 'GNB',
            'GY' => 'GUY',
            'HK' => 'HKG',
            'HM' => 'HMD',
            'HN' => 'HND',
            'HR' => 'HRV',
            'HT' => 'HTI',
            'HU' => 'HUN',
            'ID' => 'IDN',
            'IE' => 'IRL',
            'IL' => 'ISR',
            'IM' => 'IMN',
            'IN' => 'IND',
            'IO' => 'IOT',
            'IQ' => 'IRQ',
            'IR' => 'IRN',
            'IS' => 'ISL',
            'IT' => 'ITA',
            'JE' => 'JEY',
            'JM' => 'JAM',
            'JO' => 'JOR',
            'JP' => 'JPN',
            'KE' => 'KEN',
            'KG' => 'KGZ',
            'KH' => 'KHM',
            'KI' => 'KIR',
            'KM' => 'COM',
            'KN' => 'KNA',
            'KP' => 'PRK',
            'KR' => 'KOR',
            'XK' => 'XKX',
            'KW' => 'KWT',
            'KY' => 'CYM',
            'KZ' => 'KAZ',
            'LA' => 'LAO',
            'LB' => 'LBN',
            'LC' => 'LCA',
            'LI' => 'LIE',
            'LK' => 'LKA',
            'LR' => 'LBR',
            'LS' => 'LSO',
            'LT' => 'LTU',
            'LU' => 'LUX',
            'LV' => 'LVA',
            'LY' => 'LBY',
            'MA' => 'MAR',
            'MC' => 'MCO',
            'MD' => 'MDA',
            'ME' => 'MNE',
            'MF' => 'MAF',
            'MG' => 'MDG',
            'MH' => 'MHL',
            'MK' => 'MKD',
            'ML' => 'MLI',
            'MM' => 'MMR',
            'MN' => 'MNG',
            'MO' => 'MAC',
            'MP' => 'MNP',
            'MQ' => 'MTQ',
            'MR' => 'MRT',
            'MS' => 'MSR',
            'MT' => 'MLT',
            'MU' => 'MUS',
            'MV' => 'MDV',
            'MW' => 'MWI',
            'MX' => 'MEX',
            'MY' => 'MYS',
            'MZ' => 'MOZ',
            'NA' => 'NAM',
            'NC' => 'NCL',
            'NE' => 'NER',
            'NF' => 'NFK',
            'NG' => 'NGA',
            'NI' => 'NIC',
            'NL' => 'NLD',
            'NO' => 'NOR',
            'NP' => 'NPL',
            'NR' => 'NRU',
            'NU' => 'NIU',
            'NZ' => 'NZL',
            'OM' => 'OMN',
            'PA' => 'PAN',
            'PE' => 'PER',
            'PF' => 'PYF',
            'PG' => 'PNG',
            'PH' => 'PHL',
            'PK' => 'PAK',
            'PL' => 'POL',
            'PM' => 'SPM',
            'PN' => 'PCN',
            'PR' => 'PRI',
            'PS' => 'PSE',
            'PT' => 'PRT',
            'PW' => 'PLW',
            'PY' => 'PRY',
            'QA' => 'QAT',
            'RE' => 'REU',
            'RO' => 'ROU',
            'RS' => 'SRB',
            'RU' => 'RUS',
            'RW' => 'RWA',
            'SA' => 'SAU',
            'SB' => 'SLB',
            'SC' => 'SYC',
            'SD' => 'SDN',
            'SS' => 'SSD',
            'SE' => 'SWE',
            'SG' => 'SGP',
            'SH' => 'SHN',
            'SI' => 'SVN',
            'SJ' => 'SJM',
            'SK' => 'SVK',
            'SL' => 'SLE',
            'SM' => 'SMR',
            'SN' => 'SEN',
            'SO' => 'SOM',
            'SR' => 'SUR',
            'ST' => 'STP',
            'SV' => 'SLV',
            'SX' => 'SXM',
            'SY' => 'SYR',
            'SZ' => 'SWZ',
            'TC' => 'TCA',
            'TD' => 'TCD',
            'TF' => 'ATF',
            'TG' => 'TGO',
            'TH' => 'THA',
            'TJ' => 'TJK',
            'TK' => 'TKL',
            'TL' => 'TLS',
            'TM' => 'TKM',
            'TN' => 'TUN',
            'TO' => 'TON',
            'TR' => 'TUR',
            'TT' => 'TTO',
            'TV' => 'TUV',
            'TW' => 'TWN',
            'TZ' => 'TZA',
            'UA' => 'UKR',
            'UG' => 'UGA',
            'UM' => 'UMI',
            'US' => 'USA',
            'UY' => 'URY',
            'UZ' => 'UZB',
            'VA' => 'VAT',
            'VC' => 'VCT',
            'VE' => 'VEN',
            'VG' => 'VGB',
            'VI' => 'VIR',
            'VN' => 'VNM',
            'VU' => 'VUT',
            'WF' => 'WLF',
            'WS' => 'WSM',
            'YE' => 'YEM',
            'YT' => 'MYT',
            'ZA' => 'ZAF',
            'ZM' => 'ZMB',
            'ZW' => 'ZWE',
            'CS' => 'SCG',
            'AN' => 'ANT'
        );

        if (isset($alpha3_codes[$country_code])) {
            return $alpha3_codes[$country_code];
        }

        return false;
    }
}
