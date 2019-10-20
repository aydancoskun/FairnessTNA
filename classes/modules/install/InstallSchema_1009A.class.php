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
class InstallSchema_1009A extends InstallSchema_Base {

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
		Debug::text('postInstall: '. $this->getVersion(), __FILE__, __LINE__, __METHOD__, 9);

		//Add Calendar Based Accruals to Cron.
		$maint_base_path = Environment::getBasePath() . DIRECTORY_SEPARATOR .'maint'. DIRECTORY_SEPARATOR;
		if ( PHP_OS == 'WINNT' ) {
			$cron_job_base_command = 'php-win.exe '. $maint_base_path;
		} else {
			$cron_job_base_command = 'php '. $maint_base_path;
		}
		Debug::text('Cron Job Base Command: '. $cron_job_base_command, __FILE__, __LINE__, __METHOD__, 9);

		$cjf = TTnew( 'CronJobFactory' ); /** @var CronJobFactory $cjf */
		$cjf->setName('AddAccrualPolicyTime');
		$cjf->setMinute(30);
		$cjf->setHour(1);
		$cjf->setDayOfMonth('*');
		$cjf->setMonth('*');
		$cjf->setDayOfWeek('*');
		$cjf->setCommand($cron_job_base_command.'AddAccrualPolicyTime.php');
		$cjf->Save();

		return TRUE;

	}
}
?>
