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

//
//http://danielmclaren.net/2008/08/13/tracking-progress-of-a-server-side-action-in-flashflex
//
class ProgressBar
{
    public $default_key = null;
public $update_iteration = 1;
    protected $obj = null;
    private $key_prefix = 'progress_bar_'; //This is how often we actually update the progress bar, even if the function is called more often.

    public function __construct()
    {
        try {
            $this->obj = new SharedMemory();
            return true;
        } catch (Exception $e) {
            Debug::text('ERROR: Caught Exception: ' . $e->getMessage(), __FILE__, __LINE__, __METHOD__, 9);
            return false;
        }
    }

    //Allow setting a default key so we don't have to pass the key around outside of this object.

    public function error($key, $msg = null)
    {
        Debug::text('error: \'' . $key . ' Key: ' . $key . '(' . microtime(true) . ') Message: ' . $msg, __FILE__, __LINE__, __METHOD__, 9);

        if ($key == '') {
            $key = $this->getDefaultKey();
            if ($key == '') {
                return false;
            }
        }

        if (!is_object($this->obj)) { //If there is an error getting the shared memory object, cancel out early.
            return false;
        }

        if ($msg == '') {
            $msg = TTi18n::getText('Processing...');
        }

        try {
            $progress_bar_arr = $this->obj->get($this->key_prefix . $key);
            $progress_bar_arr['status_id'] = 9999;
            $progress_bar_arr['message'] = $msg;

            $this->obj->set($this->key_prefix . $key, $progress_bar_arr);
            return true;
        } catch (Exception $e) {
            Debug::text('ERROR: Caught Exception: ' . $e->getMessage(), __FILE__, __LINE__, __METHOD__, 9);
            return false;
        }
    }

    public function getDefaultKey()
    {
        return $this->default_key;
    }

    public function setDefaultKey($key)
    {
        $this->default_key = $key;
    }

    public function delete($key)
    {
        return $this->stop($this->key_prefix . $key);
    }

    public function stop($key)
    {
        if ($key == '') {
            $key = $this->getDefaultKey();
            if ($key == '') {
                return false;
            }
        }

        if (!is_object($this->obj)) { //If there is an error getting the shared memory object, cancel out early.
            return false;
        }

        try {
            return $this->obj->delete($this->key_prefix . $key);
        } catch (Exception $e) {
            Debug::text('ERROR: Caught Exception: ' . $e->getMessage(), __FILE__, __LINE__, __METHOD__, 9);
            return false;
        }
    }

    public function get($key)
    {
        if ($key == '') {
            $key = $this->getDefaultKey();
            if ($key == '') {
                return false;
            }
        }

        if (!is_object($this->obj)) { //If there is an error getting the shared memory object, cancel out early.
            return false;
        }

        try {
            return $this->obj->get($this->key_prefix . $key);
        } catch (Exception $e) {
            Debug::text('ERROR: Caught Exception: ' . $e->getMessage(), __FILE__, __LINE__, __METHOD__, 9);
            return false;
        }
    }

    public function test($key, $total_iterations = 10)
    {
        Debug::text('testProgressBar: ' . $key . ' Iterations: ' . $total_iterations, __FILE__, __LINE__, __METHOD__, 9);

        $this->start($key, $total_iterations);

        for ($i = 1; $i <= $total_iterations; $i++) {
            $this->set($key, $i);
            sleep(rand(1, 2));
        }

        $this->stop($key);

        return true;
    }

    public function start($key, $total_iterations = 100, $update_iteration = null, $msg = null)
    {
        Debug::text('start: \'' . $key . '\' Iterations: ' . $total_iterations . ' Update Iterations: ' . $update_iteration . ' Key: ' . $key . '(' . microtime(true) . ') Message: ' . $msg, __FILE__, __LINE__, __METHOD__, 9);

        if ($key == '') {
            $key = $this->getDefaultKey();
            if ($key == '') {
                return false;
            }
        }

        if ($total_iterations <= 1) {
            return false;
        }

        if (!is_object($this->obj)) { //If there is an error getting the shared memory object, cancel out early.
            return false;
        }

        if ($update_iteration == '') {
            $this->update_iteration = ceil($total_iterations / 20); //Update every 5%.
        } else {
            $this->update_iteration = $update_iteration;
        }

        if ($msg == '') {
            $msg = TTi18n::getText('Processing...');
        }

        $epoch = microtime(true);

        $progress_bar_arr = array(
            'status_id' => 10,
            'start_time' => $epoch,
            'current_iteration' => 0,
            'total_iterations' => $total_iterations,
            'last_update_time' => $epoch,
            'message' => $msg,
        );
        try {
            $this->obj->set($this->key_prefix . $key, $progress_bar_arr);
            return true;
        } catch (Exception $e) {
            Debug::text('ERROR: Caught Exception: ' . $e->getMessage(), __FILE__, __LINE__, __METHOD__, 9);
            return false;
        }
    }

    public function set($key, $current_iteration, $msg = null)
    {
        if ($key == '') {
            $key = $this->getDefaultKey();
            if ($key == '') {
                return false;
            }
        }

        if (!is_object($this->obj)) { //If there is an error getting the shared memory object, cancel out early.
            return false;
        }

        //Add quick IF statement to short circuit any work unless we meet the update_iteration, ie: every X calls do we actually do anything.
        //When processing long batches though, we need to update every iteration for the first 10 iterations so we can get an accruate estimated time for completion.
        if ($current_iteration <= 10 or ($current_iteration % $this->update_iteration) == 0) {
            //Debug::text('set: '. $key .' Iteration: '. $current_iteration, __FILE__, __LINE__, __METHOD__, 9);

            try {
                $progress_bar_arr = $this->obj->get($this->key_prefix . $key);

                if ($progress_bar_arr != false
                    and is_array($progress_bar_arr)
                    and $current_iteration >= 0
                    and $current_iteration <= $progress_bar_arr['total_iterations']
                ) {

                    /*
                    if ( PRODUCTION == FALSE AND isset($progress_bar_arr['total_iterations']) AND $progress_bar_arr['total_iterations'] >= 1 ) {
                        //Add a delay based on the total iterations so we can test the progressbar more often
                        $total_delay = 15000000; //10seconds
                        usleep( ( ($total_delay / $progress_bar_arr['total_iterations']) * $this->update_iteration));
                    }
                    */

                    $progress_bar_arr['current_iteration'] = $current_iteration;
                    $progress_bar_arr['last_update_time'] = microtime(true);
                }

                if ($msg != '') {
                    $progress_bar_arr['message'] = $msg;
                }

                return $this->obj->set($this->key_prefix . $key, $progress_bar_arr);
            } catch (Exception $e) {
                Debug::text('ERROR: Caught Exception: ' . $e->getMessage(), __FILE__, __LINE__, __METHOD__, 9);
                return false;
            }
        }

        return true;
    }
}
