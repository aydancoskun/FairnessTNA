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
 * @package Core
 */
class RateLimit
{
    protected $sleep = false; //When rate limit is reached, do we sleep or return FALSE?

    protected $id = 1;
    protected $group = 'rate_limit';

    protected $allowed_calls = 25;
    protected $time_frame = 60; //1 minute.

    protected $memory = null;

    public function __construct()
    {
        try {
            $this->memory = new SharedMemory();
            return true;
        } catch (Exception $e) {
            Debug::text('ERROR: Caught Exception: ' . $e->getMessage(), __FILE__, __LINE__, __METHOD__, 9);
            return false;
        }
    }

    public function getAttempts()
    {
        $rate_data = $this->getRateData();
        if (isset($rate_data['attempts'])) {
            return $rate_data['attempts'];
        }

        return false;
    }

    public function getRateData()
    {
        if (is_object($this->memory)) {
            try {
                return $this->memory->get($this->group . $this->getID());
            } catch (Exception $e) {
                Debug::text('ERROR: Caught Exception: ' . $e->getMessage(), __FILE__, __LINE__, __METHOD__, 9);
                return false;
            }
        }

        return false;
    }

    //Define the number of calls to check() allowed over a given time frame.

    public function getID()
    {
        return $this->id;
    }

    public function setID($value)
    {
        if ($value != '') {
            $this->id = $value;

            return true;
        }

        return false;
    }

    public function check()
    {
        if ($this->getID() != '') {
            $rate_data = $this->getRateData();
            //Debug::Arr($rate_data, 'Failed Attempt Data: ', __FILE__, __LINE__, __METHOD__, 10);
            if (!isset($rate_data['attempts'])) {
                $rate_data = array(
                    'attempts' => 0,
                    'first_date' => microtime(true),
                );
            } elseif (isset($rate_data['attempts'])) {
                if ($rate_data['attempts'] > $this->getAllowedCalls() and $rate_data['first_date'] >= (microtime(true) - $this->getTimeFrame())) {
                    return false;
                } elseif ($rate_data['first_date'] < (microtime(true) - $this->getTimeFrame())) {
                    $rate_data['attempts'] = 0;
                    $rate_data['first_date'] = microtime(true);
                }
            }

            $rate_data['attempts']++;
            $this->setRateData($rate_data);
            return true; //Don't return result of setRateData() so if it can't write the data to shared memory it fails "OPEN".
        }

        return true; //Return TRUE is no ID is specified, so it fails "OPEN".
    }

    public function getAllowedCalls()
    {
        return $this->allowed_calls;
    }

    public function setAllowedCalls($value)
    {
        if ($value != '') {
            $this->allowed_calls = $value;

            return true;
        }

        return false;
    }

    public function getTimeFrame()
    {
        return $this->time_frame;
    }

    public function setTimeFrame($value)
    {
        if ($value != '') {
            $this->time_frame = $value;

            return true;
        }

        return false;
    }

    public function setRateData($data)
    {
        if (is_object($this->memory)) {
            try {
                return $this->memory->set($this->group . $this->getID(), $data);
            } catch (Exception $e) {
                Debug::text('ERROR: Caught Exception: ' . $e->getMessage(), __FILE__, __LINE__, __METHOD__, 9);
                return false;
            }
        }

        return false;
    }

    public function delete()
    {
        if (is_object($this->memory)) {
            try {
                return $this->memory->delete($this->group . $this->getID());
            } catch (Exception $e) {
                Debug::text('ERROR: Caught Exception: ' . $e->getMessage(), __FILE__, __LINE__, __METHOD__, 9);
                return false;
            }
        }

        return false;
    }
}
