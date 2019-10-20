<?php
/*********************************************************************************
 * FairnessTNA is a Workforce Management program forked from TimeTrex in 2013,
 * copyright Aydan Coskun. Original code base is copyright TimeTrex Software Inc.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * You can contact Aydan Coskun via issue tracker on github.com/aydancoskun
 ********************************************************************************/


/**
 * @package Modules\Cron
 */
class CronJobFactory extends Factory {
	protected $table = 'cron';
	protected $pk_sequence_name = 'cron_id_seq'; //PK Sequence name

	protected $execute_flag = FALSE;

	/**
	 * @param $name
	 * @param null $parent
	 * @return array|null
	 */
	function _getFactoryOptions( $name, $parent = NULL ) {

		$retval = NULL;
		switch( $name ) {
			case 'limit':
				$retval = array(
							'minute' => array('min' => 0, 'max' => 59 ),
							'hour' => array('min' => 0, 'max' => 23 ),
							'day_of_month' => array('min' => 1, 'max' => 31 ),
							'month' => array('min' => 1, 'max' => 12 ),
							'day_of_week' => array('min' => 0, 'max' => 7 ),
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

	/**
	 * @return bool|int
	 */
	function getStatus() {
		return $this->getGenericDataValue( 'status_id' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setStatus( $value) {
		$value = (int)trim($value);
		return $this->setGenericDataValue( 'status_id', $value );
	}

	/**
	 * @return bool|mixed
	 */
	function getName() {
		return $this->getGenericDataValue( 'name' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setName( $value) {
		$value = trim($value);
		return $this->setGenericDataValue( 'name', $value );
	}

	/**
	 * @param $value_arr
	 * @param $limit_arr
	 * @return bool
	 */
	function isValidLimit( $value_arr, $limit_arr ) {
		if ( is_array($value_arr) AND is_array($limit_arr) ) {
			foreach($value_arr as $value ) {
				if ( $value == '*' ) {
					$retval = TRUE;
				}

				if ( $value >= $limit_arr['min'] AND $value <= $limit_arr['max'] ) {
					$retval = TRUE;
				} else {
					return FALSE;
				}
			}

			return $retval;
		}

		return FALSE;
	}

	/**
	 * @return bool|mixed
	 */
	function getMinute() {
		return $this->getGenericDataValue( 'minute' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setMinute( $value) {
		$value = trim($value);
		return $this->setGenericDataValue( 'minute', $value );
	}

	/**
	 * @return bool|mixed
	 */
	function getHour() {
		return $this->getGenericDataValue( 'hour' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setHour( $value) {
		$value = trim($value);
		return $this->setGenericDataValue( 'hour', $value );
	}

	/**
	 * @return bool|mixed
	 */
	function getDayOfMonth() {
		return $this->getGenericDataValue( 'day_of_month' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setDayOfMonth( $value) {
		$value = trim($value);
		return $this->setGenericDataValue( 'day_of_month', $value );
	}

	/**
	 * @return bool|mixed
	 */
	function getMonth() {
		return $this->getGenericDataValue( 'month' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setMonth( $value) {
		$value = trim($value);
		return $this->setGenericDataValue( 'month', $value );
	}

	/**
	 * @return bool|mixed
	 */
	function getDayOfWeek() {
		return $this->getGenericDataValue( 'day_of_week' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setDayOfWeek( $value) {
		$value = trim($value);
		return $this->setGenericDataValue( 'day_of_week', $value );
	}

	/**
	 * @return bool|mixed
	 */
	function getCommand() {
		return $this->getGenericDataValue( 'command' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setCommand( $value) {
		$value = trim($value);
		return $this->setGenericDataValue( 'command', $value );
	}

	/**
	 * @param bool $raw
	 * @return bool|int
	 */
	function getLastRunDate( $raw = FALSE ) {
		$value = $this->getGenericDataValue( 'last_run_date' );
		if ( $value !== FALSE ) {
			if ( $raw === TRUE ) {
				return $value;
			} else {
				return TTDate::strtotime( $value );
			}
		}

		return FALSE;
	}

	/**
	 * @param int $value EPOCH
	 * @return bool
	 */
	function setLastRunDate( $value) {
		$value = ( !is_int($value) ) ? trim($value) : $value; //Dont trim integer values, as it changes them to strings.
		return $this->setGenericDataValue( 'last_run_date', $value );
	}

	/**
	 * @param $bool
	 */
	private function setExecuteFlag( $bool ) {
		$this->execute_flag = (bool)$bool;
	}

	/**
	 * @return bool
	 */
	private function getExecuteFlag() {
		return $this->execute_flag;
	}

	/**
	 * @return bool
	 */
	function isSystemLoadValid() {
		return Misc::isSystemLoadValid();
	}

	/**
	 * Check if job is scheduled to run right NOW.
	 * If the job has missed a run, it will run immediately.
	 * @param int $epoch EPOCH
	 * @param int $last_run_date EPOCH
	 * @return bool
	 */
	function isScheduledToRun( $epoch = NULL, $last_run_date = NULL ) {
		//Debug::text('Checking if Cron Job is scheduled to run: '. $this->getName(), __FILE__, __LINE__, __METHOD__, 10);
		if ( $epoch == '' ) {
			$epoch = time();
		}

		//Debug::text('Checking if Cron Job is scheduled to run: '. $this->getName(), __FILE__, __LINE__, __METHOD__, 10);
		if ( $last_run_date == '' ) {
			$last_run_date = (int)$this->getLastRunDate();
		}

		Debug::text(' Name: '. $this->getName() .' Current Epoch: '. TTDate::getDate('DATE+TIME', $epoch) .' Last Run Date: '. TTDate::getDate('DATE+TIME', $last_run_date), __FILE__, __LINE__, __METHOD__, 10);
		return Cron::isScheduledToRun( $this->getMinute(), $this->getHour(), $this->getDayOfMonth(), $this->getMonth(), $this->getDayOfWeek(), $epoch, $last_run_date );
	}

	/**
	 * Executes the CronJob
	 * @param null $php_cli
	 * @param null $dir
	 * @return bool
	 */
	function Execute( $php_cli = NULL, $dir = NULL ) {
		global $config_vars;
		$lock_file = new LockFile( $config_vars['cache']['dir'] . DIRECTORY_SEPARATOR . $this->getName().'.lock' );

		//Check job last updated date, if its more then 12hrs and its still in the "running" status,
		//chances are its an orphan. Change status.
		//if ( $this->getStatus() != 10 AND $this->getUpdatedDate() > 0 AND $this->getUpdatedDate() < (time() - ( 6 * 3600 )) ) {
		if ( $this->getStatus() != 10 AND $this->getUpdatedDate() > 0 ) {
			$clear_lock = FALSE;
			if ( $lock_file->exists() == FALSE ) {
				Debug::text( 'ERROR: Job PID is not running assuming its an orphan, marking as ready for next run.', __FILE__, __LINE__, __METHOD__, 10 );
				$clear_lock = TRUE;
			} elseif ( $this->getUpdatedDate() < (time() - ( 6 * 3600 )) ) {
				Debug::text( 'ERROR: Job has been running for more then 6 hours! Assuming its an orphan, marking as ready for next run.', __FILE__, __LINE__, __METHOD__, 10 );
				$clear_lock = TRUE;
			}

			if ( $clear_lock == TRUE ) {
				$this->setStatus( 10 );
				$this->Save( FALSE );
				$lock_file->delete();
			}

			unset($clear_lock);
		}

		if ( !is_executable( $php_cli ) ) {
			Debug::text('ERROR: PHP CLI is not executable: '. $php_cli, __FILE__, __LINE__, __METHOD__, 10);
			return FALSE;
		}

		if ( $this->isSystemLoadValid() == FALSE ) {
			Debug::text('System load is too high, skipping...', __FILE__, __LINE__, __METHOD__, 10);
			return FALSE;
		}

		//Cron script to execute
		$script = $dir . DIRECTORY_SEPARATOR . $this->getCommand();

		if ( $this->getStatus() == 10 AND $lock_file->exists() == FALSE ) {
			$lock_file->create();

			$this->setExecuteFlag(TRUE);

			Debug::text('Job is NOT currently running, running now...', __FILE__, __LINE__, __METHOD__, 10);
			//Mark job as running
			$this->setStatus(20); //Running
			$this->Save(FALSE);

			//Even if the file does not exist, we still need to "pretend" the cron job ran (set last ran date) so we don't
			//display the big red error message saying that NO jobs have run in the last 24hrs.
			if ( file_exists( $script ) ) {
				if ( DEPLOYMENT_ON_DEMAND == TRUE ) { //In cases where many instances may be triggering jobs at the same time, add a random sleep to stagger them.
					$sleep_timer = rand(0, 120);
					Debug::text(' Random Sleep: '. $sleep_timer, __FILE__, __LINE__, __METHOD__, 10);
					sleep( $sleep_timer );
				}

				$command = '"'. $php_cli .'" "'. $script .'"';
				//if ( OPERATING_SYSTEM == 'WIN' ) {
					//Windows requires quotes around the entire command, and each individual section with that might have spaces.
					//23-May-13: This seems to cause the command to fail now. Perhaps its related to newer versions of PHP?
					//$command = '"'. $command .'"';
				//}
				Debug::text('Command: '. $command, __FILE__, __LINE__, __METHOD__, 10);

				$start_time = microtime(TRUE);
				exec($command, $output, $retcode);
				Debug::Arr($output, 'Time: '. (microtime(TRUE) - $start_time) .'s - Command RetCode: '. $retcode .' Output: ', __FILE__, __LINE__, __METHOD__, 10);

				TTLog::addEntry( $this->getId(), 500, TTi18n::getText('Executing Cron Job').': '. $this->getID() .' '.	TTi18n::getText('Command').': '. $command .' '.	 TTi18n::getText('Return Code').': '. $retcode, NULL, $this->getTable() );
			} else {
				Debug::text('WARNING: File does not exist, skipping: '. $script, __FILE__, __LINE__, __METHOD__, 10);
			}

			$this->setStatus(10); //Ready
			$this->setLastRunDate( TTDate::roundTime( time(), 60, 30) );
			$this->Save(FALSE);

			$this->setExecuteFlag(FALSE);

			$lock_file->delete();
			return TRUE;
		} else {
			Debug::text('Job is currently running, skipping...', __FILE__, __LINE__, __METHOD__, 10);
		}

		return FALSE;
	}
	/**
	 * @return bool
	 */
	function Validate() {
		//
		// BELOW: Validation code moved from set*() functions.
		//
		// Status
		$this->Validator->inArrayKey(	'status',
												$this->getStatus(),
												TTi18n::gettext('Incorrect Status'),
												$this->getOptions('status')
											);
		// Name
		$this->Validator->isLength(	'name',
											$this->getName(),
											TTi18n::gettext('Name is invalid'),
											1, 250
										);
		// Minute
		$this->Validator->isLength(	'minute',
											$this->getMinute(),
											TTi18n::gettext('Minute is invalid'),
											1, 250
										);
		// Hour
		$this->Validator->isLength(	'hour',
											$this->getHour(),
											TTi18n::gettext('Hour is invalid'),
											1, 250
										);
		// Day of Month
		$this->Validator->isLength(	'day_of_month',
											$this->getDayOfMonth(),
											TTi18n::gettext('Day of Month is invalid'),
											1, 250
										);
		// Month
		$this->Validator->isLength(	'month',
											$this->getMonth(),
											TTi18n::gettext('Month is invalid'),
											1, 250
										);
		// Day of Week
		$this->Validator->isLength(	'day_of_week',
											$this->getDayOfWeek(),
											TTi18n::gettext('Day of Week is invalid'),
											1, 250
										);
		// Command
		$this->Validator->isLength(	'command',
											$this->getCommand(),
											TTi18n::gettext('Command is invalid'),
											1, 250
										);
		// last run
		if ( $this->getLastRunDate() !== FALSE ) {
			$this->Validator->isDate(		'last_run',
													$this->getLastRunDate(),
													TTi18n::gettext('Incorrect last run')
												);
		}
		//
		// ABOVE: Validation code moved from set*() functions.
		//
		return TRUE;
	}
	/**
	 * @return bool
	 */
	function preSave() {
		if ( $this->getStatus() == '' ) {
			$this->setStatus(10); //Ready
		}

		if ( $this->getMinute() == '' ) {
			$this->setMinute('*');
		}

		if ( $this->getHour() == '' ) {
			$this->setHour('*');
		}

		if ( $this->getDayOfMonth() == '' ) {
			$this->setDayOfMonth('*');
		}

		if ( $this->getMonth() == '' ) {
			$this->setMonth('*');
		}

		if ( $this->getDayOfWeek() == '' ) {
			$this->setDayOfWeek('*');
		}

		return TRUE;
	}

	/**
	 * @return bool
	 */
	function postSave() {
		$this->removeCache( $this->getId() );

		return TRUE;
	}

	/**
	 * @param $log_action
	 * @return bool
	 */
	function addLog( $log_action ) {
		if ( $this->getExecuteFlag() == FALSE ) {
			return TTLog::addEntry( $this->getId(), $log_action, TTi18n::getText('Cron Job'), NULL, $this->getTable() );
		}

		return TRUE;
	}
}
?>
