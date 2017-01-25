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
class BackgroundProcess
{
    public $max_processes = 1;
    public $max_process_check_sleep = 2;
    public $max_process_check_timeout = 600; //Max time to wait to run the next process
    public $process_number_digits = 5;

    public $lock_file_dir = '/tmp/';
    public $lock_file_prefix = 'background_process';
    public $lock_file_postfix = '.lock';
    public $max_lock_file_age = 86400;

    public function __construct()
    {
        return true;
    }

    public function setLockFileDirectory($dir)
    {
        if ($dir != '' and file_exists($dir) and is_writable($dir)) {
            $this->lock_file_dir = $dir;

            return true;
        }

        return false;
    }

    public function run($cmd)
    {
        //Check to see how many lock files with the prefix exist already.

        $timeout_start = time();
        while ((time() - $timeout_start) <= $this->max_process_check_timeout) {
            $lock_files = $this->getLockFiles();
            $current_processes = $this->getCurrentProcesses($lock_files);
            //Debug::Text(' Attempting to run command...', __FILE__, __LINE__, __METHOD__, 10);

            if ($current_processes < $this->getMaxProcesses()) {
                $next_lock_file_name = $this->getNextLockFileName($lock_files);
                $cmd = $this->ReplaceCommandVariables($cmd, $next_lock_file_name);
                Debug::Text(' Running Command: ' . $cmd . ' Next Lock File Name: ' . $next_lock_file_name, __FILE__, __LINE__, __METHOD__, 10);

                //Run command
                $this->BackgroundExec($cmd);

                //Check to make sure lock file exists, if not loop for up to 2.5 seconds waiting for it.
                usleep(250000); //.25 seconds
                if (file_exists($next_lock_file_name) == false) {
                    $max = 5;
                    for ($i = 0; $i <= $max; $i++) {
                        if (file_exists($next_lock_file_name)) {
                            //Debug::Text(' Lock file was created, returning...', __FILE__, __LINE__, __METHOD__, 10);
                            break;
                        } else {
                            Debug::Text('I: ' . $i . ' Waiting for lock file to be created... File Name: ' . $next_lock_file_name, __FILE__, __LINE__, __METHOD__, 10);
                            usleep(500000); //.5 seconds
                        }
                    }
                } // else { //Debug::Text(' Lock file was created, returning...', __FILE__, __LINE__, __METHOD__, 10);

                return true;
            } else {
                Debug::Text(' Too many processes already running (' . $current_processes . '), sleeping for: ' . $this->max_process_check_sleep . ' before next check...', __FILE__, __LINE__, __METHOD__, 10);
                sleep($this->max_process_check_sleep);
            }
        }

        Debug::Text(' Timeout waiting for spot in process pool to open up.', __FILE__, __LINE__, __METHOD__, 10);

        return false;
    }

    public function getLockFiles()
    {
        $start_dir = $this->getLockFileDirectory();
        $regex_filter = $this->getLockFilePrefix() . '\.lock.*';

        $retarr = Misc::getFileList($start_dir, $regex_filter, false);

        //Debug::Arr($retarr, ' Existing Lock Files: ', __FILE__, __LINE__, __METHOD__, 10);

        $this->purgeLockFiles($retarr);

        return $retarr;
    }

    public function getLockFileDirectory()
    {
        return $this->lock_file_dir;
    }

    public function getLockFilePrefix()
    {
        return $this->lock_file_prefix;
    }

    public function setLockFilePrefix($prefix)
    {
        if ($prefix != '') {
            $this->lock_file_prefix = $prefix;

            return true;
        }

        return false;
    }

    public function purgeLockFiles($lock_files)
    {
        if (is_array($lock_files)) {
            foreach ($lock_files as $lock_file) {
                $current_epoch = time();
                if (file_exists($lock_file) and ($current_epoch - @filemtime($lock_file)) > $this->max_lock_file_age and @is_writable($lock_file)) {
                    Debug::Text(' Purging stale lock file: ' . $lock_file, __FILE__, __LINE__, __METHOD__, 10);
                    @unlink($lock_file);
                }
            }
        }

        return true;
    }

    public function getCurrentProcesses($lock_files)
    {
        if (is_array($lock_files)) {
            $retval = count($lock_files);
        } else {
            $retval = 0;
        }

        //Debug::Text(' Current Running Processes: '. $retval, __FILE__, __LINE__, __METHOD__, 10);

        return $retval;
    }

    public function getMaxProcesses()
    {
        return $this->max_processes;
    }

    //Delete any lock files older then max age, incase they are stale.

    public function setMaxProcesses($int)
    {
        $int = (int)$int;

        if ($int <= 0) {
            $int = 1;
        }
        $this->max_processes = $int;

        return true;
    }

    public function getNextLockFileName($lock_files)
    {
        //Lock file name example: <prefix>.lock.<process_number>
        //ie: timeclocksync.lock.2
        $last_process_number = 1;
        $process_numbers = array();
        if (is_array($lock_files)) {
            foreach ($lock_files as $lock_file) {
                if (preg_match('/' . $this->getLockFilePrefix() . '\.lock\.([0-9]{1,' . $this->process_number_digits . '})/i', $lock_file, $matches)) {
                    if (isset($matches[0]) and isset($matches[1]) and $matches[1] != '') {
                        $process_numbers[] = (int)$matches[1];
                    }
                }
            }

            if (empty($process_numbers) == false) {
                rsort($process_numbers);
                $last_process_number = ((int)$process_numbers[0] + 1);
            }
        }
        //Debug::Text(' Last Process Number: '. $last_process_number, __FILE__, __LINE__, __METHOD__, 10);

        //Pad process number to proper digits
        $last_process_number = str_pad($last_process_number, $this->process_number_digits, '0', STR_PAD_LEFT);

        $retval = $this->getBaseLockFileName(true) . '.' . $last_process_number;

        //Debug::Text(' Next Lock File Name: '. $retval, __FILE__, __LINE__, __METHOD__, 10);

        return $retval;
    }

    public function getBaseLockFileName($include_dir = false)
    {
        if ($include_dir == true) {
            $retval = $this->getLockFileDirectory() . DIRECTORY_SEPARATOR;
        } else {
            $retval = '';
        }

        $retval .= $this->getLockFilePrefix() . $this->lock_file_postfix;

        //Debug::Text(' Base Lock File Name: '. $retval, __FILE__, __LINE__, __METHOD__, 10);

        return $retval;
    }

    public function ReplaceCommandVariables($cmd, $next_lock_file_name)
    {
        $search_array = array(
            '#lock_file#'
        );
        $replace_array = array(
            $next_lock_file_name
        );
        $retval = str_replace($search_array, $replace_array, $cmd);

        //Debug::Text(' Before: '. $cmd, __FILE__, __LINE__, __METHOD__, 10);
        //Debug::Text(' After: '. $retval, __FILE__, __LINE__, __METHOD__, 10);

        return $retval;
    }

    public function BackgroundExec($cmd)
    {
        if (PHP_OS == 'WINNT') {
            //Windows
            global $config_vars;
            if (strpos($config_vars['path']['php_cli'], ' ') === false) {
                //No space found in command, can run in background.

                //Unfortunately start.exe won't run a command with quotes around it, so we can't reliably run in the background without some extra
                //helper scripts, as FairnessTNA could be installed in a directory which contains a space.
                //Remove quotes from command as "start.exe" fails to run if they exist.
                $full_command = str_replace('"', '', 'start /B ' . $cmd);
                Debug::Text(' Executing Command in Background: ' . $full_command, __FILE__, __LINE__, __METHOD__, 10);

                pclose(popen($full_command, 'r'));
            } else {
                Debug::Text(' Executing Command in Foreground: ' . $cmd, __FILE__, __LINE__, __METHOD__, 10);
                exec($cmd);
            }
        } else {
            //Linux/Unix
            //exec($cmd . ' 2>&1> /dev/null &');
            exec($cmd . ' > /dev/null &');
        }

        return true;
    }
}
