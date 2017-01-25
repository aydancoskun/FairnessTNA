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
class LockFile
{
    public $file_name = null;

    public $max_lock_file_age = 86400;
    public $use_pid = true;

    public function __construct($file_name)
    {
        $this->file_name = $file_name;

        return true;
    }

    public function create()
    {
        //Attempt to create directory if it does not already exist.
        if (file_exists(dirname($this->getFileName())) == false) {
            $mkdir_result = @mkdir(dirname($this->getFileName()), 0777, true);
            if ($mkdir_result == false) {
                Debug::Text('ERROR: Unable to create lock file directory: ' . dirname($this->getFileName()), __FILE__, __LINE__, __METHOD__, 10);
            } else {
                Debug::Text('WARNING: Created lock file directory as it didnt exist: ' . dirname($this->getFileName()), __FILE__, __LINE__, __METHOD__, 10);
            }
        }

        //Write current PID to file, so we can check if its still running later on.
        //return @touch( $this->getFileName() );
        return @file_put_contents($this->getFileName(), $this->getCurrentPID());
    }

    public function getFileName()
    {
        return $this->file_name;
    }

    public function setFileName($file_name)
    {
        if ($file_name != '') {
            $this->file_name = $file_name;

            return true;
        }

        return false;
    }

    public function getCurrentPID()
    {
        if ($this->use_pid == true and function_exists('getmypid') == true) {
            $retval = getmypid();
            Debug::Text('Current PID: ' . $retval, __FILE__, __LINE__, __METHOD__, 10);

            return $retval;
        }

        return false;
    }

    public function delete()
    {
        if (file_exists($this->getFileName())) {
            return @unlink($this->getFileName());
        }

        Debug::text(' Failed deleting lock file: ' . $this->file_name, __FILE__, __LINE__, __METHOD__, 10);

        return false;
    }

    public function exists()
    {
        //Ignore lock files older than max_lock_file_age, so if the server crashes or is rebooted during an operation, it will start again the next day.
        clearstatcache();
        //if ( file_exists( $this->getFileName() ) AND @filemtime( $this->getFileName() ) >= ( time() - $this->max_lock_file_age ) ) {
        if (file_exists($this->getFileName())) {
            $lock_file_pid = (int)@file_get_contents($this->getFileName());
            Debug::text(' Lock file exists with PID: ' . $lock_file_pid, __FILE__, __LINE__, __METHOD__, 10);

            //Check to see if PID is still running or not.
            $pid_running = $this->isPIDRunning($lock_file_pid);
            if ($pid_running !== null) {
                //PID result is reliable, use it.
                return $pid_running;
            } elseif (@filemtime($this->getFileName()) >= (time() - $this->max_lock_file_age)) {
                //PID result may not be reliable, fall back to using file time instead.
                return true;
            }
        }

        return false;
    }

    public function isPIDRunning($pid)
    {
        if ($this->use_pid == true and (int)$pid > 0 and function_exists('posix_getpgid') == true) {
            Debug::Text('Checking if PID is running: ' . $pid, __FILE__, __LINE__, __METHOD__, 10);
            if (posix_getpgid($pid) === false) {
                Debug::Text('  PID is NOT running!', __FILE__, __LINE__, __METHOD__, 10);
                return false;
            } else {
                Debug::Text('  PID IS running!', __FILE__, __LINE__, __METHOD__, 10);
                return true;
            }
        } else {
            //Debug::Text( 'PID is invalid or POSIX functions dont exist: ' . $pid, __FILE__, __LINE__, __METHOD__, 10 );
            if (OPERATING_SYSTEM == 'WIN') {
                Debug::Text('Checking if PID is running on Windows: ' . $pid, __FILE__, __LINE__, __METHOD__, 10);
                $processes = explode("\n", shell_exec('tasklist.exe'));
                if (is_array($processes)) {
                    foreach ($processes as $process) {
                        if (trim($process) == '' or strpos("Image Name", $process) === 0 or strpos("===", $process) === 0) {
                            continue;
                        }

                        $matches = false;
                        preg_match("/(.*?)\s+(\d+).*$/", $process, $matches);
                        if (isset($matches[2]) and $pid == trim($matches[2])) {
                            Debug::Text('  PID IS running!', __FILE__, __LINE__, __METHOD__, 10);
                            return true;
                        }
                    }

                    Debug::Text('  PID is NOT running!', __FILE__, __LINE__, __METHOD__, 10);
                    return false;
                }
            }
        }

        return null; //Assuming the process is still running if the file exists and PID is invalid.
    }
}
