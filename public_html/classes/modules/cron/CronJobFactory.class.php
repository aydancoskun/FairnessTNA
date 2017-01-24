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


/**
 * @package Modules\Cron
 */
class CronJobFactory extends Factory
{
    protected $table = 'cron';
    protected $pk_sequence_name = 'cron_id_seq'; //PK Sequence name

    protected $temp_time = null;
    protected $execute_flag = false;

    public function _getFactoryOptions($name, $parent = null)
    {
        $retval = null;
        switch ($name) {
            case 'limit':
                $retval = array(
                    'minute' => array('min' => 0, 'max' => 59),
                    'hour' => array('min' => 0, 'max' => 23),
                    'day_of_month' => array('min' => 1, 'max' => 31),
                    'month' => array('min' => 1, 'max' => 12),
                    'day_of_week' => array('min' => 0, 'max' => 7),
                );
                break;
            case 'status':
                $retval = array(
                    10 => TTi18n::gettext('READY'),
                    20 => TTi18n::gettext('RUNNING'),
                );
                break;

        }

        return $retval;
    }

    public function setName($name)
    {
        $name = trim($name);

        if ($this->Validator->isLength('name',
            $name,
            TTi18n::gettext('Name is invalid'),
            1, 250)
        ) {
            $this->data['name'] = $name;

            return true;
        }

        return false;
    }

    public function isValidLimit($value_arr, $limit_arr)
    {
        if (is_array($value_arr) and is_array($limit_arr)) {
            foreach ($value_arr as $value) {
                if ($value == '*') {
                    $retval = true;
                }

                if ($value >= $limit_arr['min'] and $value <= $limit_arr['max']) {
                    $retval = true;
                } else {
                    return false;
                }
            }

            return $retval;
        }

        return false;
    }

    public function setCommand($value)
    {
        $value = trim($value);

        if ($this->Validator->isLength('command',
            $value,
            TTi18n::gettext('Command is invalid'),
            1, 250)
        ) {
            $this->data['command'] = $value;

            return true;
        }

        return false;
    }

    public function isScheduledToRun($epoch = null, $last_run_date = null)
    {
        //Debug::text('Checking if Cron Job is scheduled to run: '. $this->getName(), __FILE__, __LINE__, __METHOD__, 10);
        if ($epoch == '') {
            $epoch = time();
        }

        //Debug::text('Checking if Cron Job is scheduled to run: '. $this->getName(), __FILE__, __LINE__, __METHOD__, 10);
        if ($last_run_date == '') {
            $last_run_date = (int)$this->getLastRunDate();
        }

        Debug::text(' Name: ' . $this->getName() . ' Current Epoch: ' . TTDate::getDate('DATE+TIME', $epoch) . ' Last Run Date: ' . TTDate::getDate('DATE+TIME', $last_run_date), __FILE__, __LINE__, __METHOD__, 10);
        return Cron::isScheduledToRun($this->getMinute(), $this->getHour(), $this->getDayOfMonth(), $this->getMonth(), $this->getDayOfWeek(), $epoch, $last_run_date);
    }

    public function getLastRunDate($raw = false)
    {
        if (isset($this->data['last_run_date'])) {
            if ($raw === true) {
                return $this->data['last_run_date'];
            } else {
                return TTDate::strtotime($this->data['last_run_date']);
            }
        }

        return false;
    }

    public function getName()
    {
        if (isset($this->data['name'])) {
            return $this->data['name'];
        }

        return false;
    }

    public function getMinute()
    {
        if (isset($this->data['minute'])) {
            return $this->data['minute'];
        }

        return false;
    }

    public function getHour()
    {
        if (isset($this->data['hour'])) {
            return $this->data['hour'];
        }

        return false;
    }

    public function getDayOfMonth()
    {
        if (isset($this->data['day_of_month'])) {
            return $this->data['day_of_month'];
        }

        return false;
    }

    public function getMonth()
    {
        if (isset($this->data['month'])) {
            return $this->data['month'];
        }

        return false;
    }

    public function getDayOfWeek()
    {
        if (isset($this->data['day_of_week'])) {
            return $this->data['day_of_week'];
        }

        return false;
    }

    public function Execute($php_cli = null, $dir = null)
    {
        global $config_vars;
        $lock_file = new LockFile($config_vars['cache']['dir'] . DIRECTORY_SEPARATOR . $this->getName() . '.lock');

        //Check job last updated date, if its more then 12hrs and its still in the "running" status,
        //chances are its an orphan. Change status.
        //if ( $this->getStatus() != 10 AND $this->getUpdatedDate() > 0 AND $this->getUpdatedDate() < (time() - ( 6 * 3600 )) ) {
        if ($this->getStatus() != 10 and $this->getUpdatedDate() > 0) {
            $clear_lock = false;
            if ($lock_file->exists() == false) {
                Debug::text('ERROR: Job PID is not running assuming its an orphan, marking as ready for next run.', __FILE__, __LINE__, __METHOD__, 10);
                $clear_lock = true;
            } elseif ($this->getUpdatedDate() < (time() - (6 * 3600))) {
                Debug::text('ERROR: Job has been running for more then 6 hours! Assuming its an orphan, marking as ready for next run.', __FILE__, __LINE__, __METHOD__, 10);
                $clear_lock = true;
            }

            if ($clear_lock == true) {
                $this->setStatus(10);
                $this->Save(false);
                $lock_file->delete();
            }

            unset($clear_lock);
        }

        if (!is_executable($php_cli)) {
            Debug::text('ERROR: PHP CLI is not executable: ' . $php_cli, __FILE__, __LINE__, __METHOD__, 10);
            return false;
        }

        if ($this->isSystemLoadValid() == false) {
            Debug::text('System load is too high, skipping...', __FILE__, __LINE__, __METHOD__, 10);
            return false;
        }

        //Cron script to execute
        $script = $dir . DIRECTORY_SEPARATOR . $this->getCommand();

        if ($this->getStatus() == 10 and $lock_file->exists() == false) {
            $lock_file->create();

            $this->setExecuteFlag(true);

            Debug::text('Job is NOT currently running, running now...', __FILE__, __LINE__, __METHOD__, 10);
            //Mark job as running
            $this->setStatus(20); //Running
            $this->Save(false);

            //Even if the file does not exist, we still need to "pretend" the cron job ran (set last ran date) so we don't
            //display the big red error message saying that NO jobs have run in the last 24hrs.
            if (file_exists($script)) {
                $command = '"' . $php_cli . '" "' . $script . '"';
                //if ( OPERATING_SYSTEM == 'WIN' ) {
                //Windows requires quotes around the entire command, and each individual section with that might have spaces.
                //23-May-13: This seems to cause the command to fail now. Perhaps its related to newer versions of PHP?
                //$command = '"'. $command .'"';
                //}
                Debug::text('Command: ' . $command, __FILE__, __LINE__, __METHOD__, 10);

                $start_time = microtime(true);
                exec($command, $output, $retcode);
                Debug::Arr($output, 'Time: ' . (microtime(true) - $start_time) . 's - Command RetCode: ' . $retcode . ' Output: ', __FILE__, __LINE__, __METHOD__, 10);

                TTLog::addEntry($this->getId(), 500, TTi18n::getText('Executing Cron Job') . ': ' . $this->getID() . ' ' . TTi18n::getText('Command') . ': ' . $command . ' ' . TTi18n::getText('Return Code') . ': ' . $retcode, null, $this->getTable());
            } else {
                Debug::text('WARNING: File does not exist, skipping: ' . $script, __FILE__, __LINE__, __METHOD__, 10);
            }

            $this->setStatus(10); //Ready
            $this->setLastRunDate(TTDate::roundTime(time(), 60, 30));
            $this->Save(false);

            $this->setExecuteFlag(false);

            $lock_file->delete();
            return true;
        } else {
            Debug::text('Job is currently running, skipping...', __FILE__, __LINE__, __METHOD__, 10);
        }

        return false;
    }

    public function getStatus()
    {
        if (isset($this->data['status_id'])) {
            return (int)$this->data['status_id'];
        }

        return false;
    }

    public function setStatus($status)
    {
        $status = trim($status);

        if ($this->Validator->inArrayKey('status',
            $status,
            TTi18n::gettext('Incorrect Status'),
            $this->getOptions('status'))
        ) {
            $this->data['status_id'] = $status;

            return true;
        }

        return false;
    }

    public function isSystemLoadValid()
    {
        return Misc::isSystemLoadValid();
    }

    public function getCommand()
    {
        if (isset($this->data['command'])) {
            return $this->data['command'];
        }

        return false;
    }

    private function setExecuteFlag($bool)
    {
        $this->execute_flag = (bool)$bool;
    }

    public function setLastRunDate($epoch)
    {
        $epoch = (!is_int($epoch)) ? trim($epoch) : $epoch; //Dont trim integer values, as it changes them to strings.

        if ($this->Validator->isDate('last_run',
            $epoch,
            TTi18n::gettext('Incorrect last run'))
        ) {
            $this->data['last_run_date'] = $epoch;

            return true;
        }

        return false;
    }

    public function preSave()
    {
        if ($this->getStatus() == '') {
            $this->setStatus(10); //Ready
        }

        if ($this->getMinute() == '') {
            $this->setMinute('*');
        }

        if ($this->getHour() == '') {
            $this->setHour('*');
        }

        if ($this->getDayOfMonth() == '') {
            $this->setDayOfMonth('*');
        }

        if ($this->getMonth() == '') {
            $this->setMonth('*');
        }

        if ($this->getDayOfWeek() == '') {
            $this->setDayOfWeek('*');
        }

        return true;
    }

    public function setMinute($value)
    {
        $value = trim($value);

        if ($this->Validator->isLength('minute',
            $value,
            TTi18n::gettext('Minute is invalid'),
            1, 250)
        ) {
            $this->data['minute'] = $value;

            return true;
        }

        return false;
    }

    public function setHour($value)
    {
        $value = trim($value);

        if ($this->Validator->isLength('hour',
            $value,
            TTi18n::gettext('Hour is invalid'),
            1, 250)
        ) {
            $this->data['hour'] = $value;

            return true;
        }

        return false;
    }

    public function setDayOfMonth($value)
    {
        $value = trim($value);

        if ($this->Validator->isLength('day_of_month',
            $value,
            TTi18n::gettext('Day of Month is invalid'),
            1, 250)
        ) {
            $this->data['day_of_month'] = $value;

            return true;
        }

        return false;
    }

    public function setMonth($value)
    {
        $value = trim($value);

        if ($this->Validator->isLength('month',
            $value,
            TTi18n::gettext('Month is invalid'),
            1, 250)
        ) {
            $this->data['month'] = $value;

            return true;
        }

        return false;
    }

    public function setDayOfWeek($value)
    {
        $value = trim($value);

        if ($this->Validator->isLength('day_of_week',
            $value,
            TTi18n::gettext('Day of Week is invalid'),
            1, 250)
        ) {
            $this->data['day_of_week'] = $value;

            return true;
        }

        return false;
    }

    //Check if job is scheduled to run right NOW.
    //If the job has missed a run, it will run immediately.

    public function postSave()
    {
        $this->removeCache($this->getId());

        return true;
    }

    //Executes the CronJob

    public function addLog($log_action)
    {
        if ($this->getExecuteFlag() == false) {
            return TTLog::addEntry($this->getId(), $log_action, TTi18n::getText('Cron Job'), null, $this->getTable());
        }

        return true;
    }

    private function getExecuteFlag()
    {
        return $this->execute_flag;
    }

    private function setTempTime($epoch)
    {
        $this->temp_time = $epoch;
    }

    private function getTempTime()
    {
        return $this->temp_time;
    }
}
