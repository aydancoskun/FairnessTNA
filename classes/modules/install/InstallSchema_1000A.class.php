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
 * @package Modules\Install
 */
class InstallSchema_1000A extends InstallSchema_Base {

	/**
	 * @return bool
	 */
	function preInstall() {
		Debug::text('preInstall: '. $this->getVersion(), __FILE__, __LINE__, __METHOD__, 9);

		return TRUE;
	}


	/**
	 * @return bool
	 */
	function postInstall() {
		// @codingStandardsIgnoreStart
		global $config_vars;
		// @codingStandardsIgnoreEnd
		Debug::text('postInstall: '. $this->getVersion(), __FILE__, __LINE__, __METHOD__, 9);

		//Immediately after '1000A' schema version is completed, try to get a registration key so help with UUID generation.
		// Also seed InstallSchema_Base->replaceSQLVariables(), as it needs to run a similar check.
		Debug::text('Initializing database, after first schema file executed, setting registration key/UUID seed...', __FILE__, __LINE__, __METHOD__, 9);

		if ( TTUUID::generateSeed() === FALSE ) { //Generate UUID seed and save it to config file.
			Debug::text('ERROR: Failed writing seed to config file... Failing!', __FILE__, __LINE__, __METHOD__, 9);
			return FALSE;
		}

		$maint_base_path = Environment::getBasePath() . DIRECTORY_SEPARATOR .'maint'. DIRECTORY_SEPARATOR;
		if ( PHP_OS == 'WINNT' ) {
			$cron_job_base_command = 'php-win.exe '. $maint_base_path;
		} else {
			$cron_job_base_command = 'php '. $maint_base_path;
		}
		Debug::text('Cron Job Base Command: '. $cron_job_base_command, __FILE__, __LINE__, __METHOD__, 9);

		// >> /dev/null 2>&1
		$cjf = TTnew( 'CronJobFactory' ); /** @var CronJobFactory $cjf */
		$cjf->setName('AddPayPeriod');
		$cjf->setMinute(0);
		$cjf->setHour(0);
		$cjf->setDayOfMonth('*');
		$cjf->setMonth('*');
		$cjf->setDayOfWeek('*');
		$cjf->setCommand($cron_job_base_command.'AddPayPeriod.php');
		$cjf->Save();

		$cjf = TTnew( 'CronJobFactory' ); /** @var CronJobFactory $cjf */
		$cjf->setName('AddUserDate');
		$cjf->setMinute(15);
		$cjf->setHour(0);
		$cjf->setDayOfMonth('*');
		$cjf->setMonth('*');
		$cjf->setDayOfWeek('*');
		$cjf->setCommand($cron_job_base_command.'AddUserDate.php');
		$cjf->Save();

		$cjf = TTnew( 'CronJobFactory' ); /** @var CronJobFactory $cjf */
		$cjf->setName('calcExceptions');
		$cjf->setMinute(30);
		$cjf->setHour(0);
		$cjf->setDayOfMonth('*');
		$cjf->setMonth('*');
		$cjf->setDayOfWeek('*');
		$cjf->setCommand($cron_job_base_command.'calcExceptions.php');
		$cjf->Save();

		$cjf = TTnew( 'CronJobFactory' ); /** @var CronJobFactory $cjf */
		$cjf->setName('AddRecurringPayStubAmendment');
		$cjf->setMinute(45);
		$cjf->setHour(0);
		$cjf->setDayOfMonth('*');
		$cjf->setMonth('*');
		$cjf->setDayOfWeek('*');
		$cjf->setCommand($cron_job_base_command.'AddRecurringPayStubAmendment.php');
		$cjf->Save();

		$cjf = TTnew( 'CronJobFactory' ); /** @var CronJobFactory $cjf */
		$cjf->setName('AddRecurringHoliday');
		$cjf->setMinute(55);
		$cjf->setHour(0);
		$cjf->setDayOfMonth('*');
		$cjf->setMonth('*');
		$cjf->setDayOfWeek('*');
		$cjf->setCommand($cron_job_base_command.'AddRecurringHoliday.php');
		$cjf->Save();

		$cjf = TTnew( 'CronJobFactory' ); /** @var CronJobFactory $cjf */
		$cjf->setName('UserCount');
		$cjf->setMinute(15);
		$cjf->setHour(1);
		$cjf->setDayOfMonth('*');
		$cjf->setMonth('*');
		$cjf->setDayOfWeek('*');
		$cjf->setCommand($cron_job_base_command.'UserCount.php');
		$cjf->Save();

		$cjf = TTnew( 'CronJobFactory' ); /** @var CronJobFactory $cjf */
		$cjf->setName('AddRecurringScheduleShift');
		$cjf->setMinute('20, 50');
		$cjf->setHour('*');
		$cjf->setDayOfMonth('*');
		$cjf->setMonth('*');
		$cjf->setDayOfWeek('*');
		$cjf->setCommand($cron_job_base_command.'AddRecurringScheduleShift.php');
		$cjf->Save();

		$cjf = TTnew( 'CronJobFactory' ); /** @var CronJobFactory $cjf */
		$cjf->setName('CheckForUpdate');
		$cjf->setMinute( rand(0, 59) ); //Random time once a day for load balancing
		$cjf->setHour( rand(0, 23) ); //Random time once a day for load balancing
		$cjf->setDayOfMonth('*');
		$cjf->setMonth('*');
		$cjf->setDayOfWeek('*');
		$cjf->setCommand($cron_job_base_command.'CheckForUpdate.php');
		$cjf->Save();

		return TRUE;

	}
}
?>
