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
//This class extends the built-in PHP SoapClient class to use CURL with proper timeout periods.
class SoapClientTimeout extends SoapClient
{
    private $timeout;

    public function __setTimeout($timeout)
    {
        if (!is_int($timeout) and !is_null($timeout)) {
            throw new Exception("Invalid timeout value");
        }
        $this->timeout = $timeout;
    }

    public function __doRequest($request, $location, $action, $version, $one_way = false)
    {
        if (!$this->timeout) {
            // Call via parent because we require no timeout
            $response = parent::__doRequest($request, $location, $action, $version, $one_way);
        } else {
            // Call via Curl and use the timeout
            $curl = curl_init($location);

            curl_setopt($curl, CURLOPT_VERBOSE, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: text/xml"));
            curl_setopt($curl, CURLOPT_TIMEOUT, $this->timeout);

            $response = curl_exec($curl);

            if (curl_errno($curl)) {
                throw new Exception(curl_error($curl));
            }

            curl_close($curl);
        }

        // Return?
        if (!$one_way) {
            return $response;
        }
    }
}
