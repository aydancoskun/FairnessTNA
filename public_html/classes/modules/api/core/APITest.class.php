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
 * @package API\Core
 */
class APITest extends APIFactory
{
    protected $main_class = '';

    public function __construct()
    {
        parent::__construct(); //Make sure parent constructor is always called.

        return true;
    }

    public function HelloWorld($test)
    {
        return "You said: $test";
    }

    public function delay($seconds = 10)
    {
        Debug::text('delay: ' . $seconds, __FILE__, __LINE__, __METHOD__, 9);

        sleep($seconds);
        return true;
    }

    public function getDataGridData()
    {
        $retarr = array(
            array(
                'first_name' => 'Jane',
                'last_name' => 'Doe',
            ),
            array(
                'first_name' => 'John',
                'last_name' => 'Doe',
            ),
            array(
                'first_name' => 'Ben',
                'last_name' => 'Smith',
            ),

        );

        return $retarr;
    }

    //Return large dataset to test performance.
    public function getLargeDataSet($max_size = 100, $delay = 100000, $progress_bar_id = null)
    {
        if ($max_size > 9999) {
            $max_size = 9999;
        }

        if ($progress_bar_id == '') {
            $progress_bar_id = $this->getAMFMessageID();
        }

        $this->getProgressBarObject()->start($progress_bar_id, $max_size);

        $retarr = array();
        for ($i = 1; $i <= $max_size; $i++) {
            $retarr[] = array('foo1' => 'bar1', 'foo2' => 'bar2', 'foo3' => 'bar3');
            usleep($delay);
            $this->getProgressBarObject()->set($progress_bar_id, $i);
        }

        $this->getProgressBarObject()->stop($progress_bar_id);
        return $retarr;
    }

    //Date test, since Flex doesn't handle timezones very well, run tests to ensure things are working correctly.
    public function dateTest($test = 1)
    {
        switch ($test) {
            case 1:
                $retarr = array(
                    strtotime('30-Oct-09 5:00PM') => TTDate::getDBTimeStamp(strtotime('30-Oct-09 5:00PM')),
                    strtotime('31-Oct-09 5:00PM') => TTDate::getDBTimeStamp(strtotime('31-Oct-09 5:00PM')),
                    strtotime('01-Nov-09 5:00PM') => TTDate::getDBTimeStamp(strtotime('01-Nov-09 5:00PM')),
                    strtotime('02-Nov-09 5:00PM') => TTDate::getDBTimeStamp(strtotime('02-Nov-09 5:00PM')),
                );

                break;
            case 2:
                $retarr = array(
                    strtotime('30-Oct-09 5:00PM') => TTDate::getFlexTimeStamp(strtotime('30-Oct-09 5:00PM')),
                    strtotime('31-Oct-09 5:00PM') => TTDate::getFlexTimeStamp(strtotime('31-Oct-09 5:00PM')),
                    strtotime('01-Nov-09 5:00PM') => TTDate::getFlexTimeStamp(strtotime('01-Nov-09 5:00PM')),
                    strtotime('02-Nov-09 5:00PM') => TTDate::getFlexTimeStamp(strtotime('02-Nov-09 5:00PM')),
                );

                break;
        }

        return $retarr;
    }
}
