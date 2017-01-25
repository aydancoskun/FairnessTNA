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

/*
 * Cron replica
 * Run this script every minute from the real cron.
 *
 */
require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'global.inc.php');
require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'CLI.inc.php');

if (isset($config_vars['other']['installer_enabled']) and $config_vars['other']['installer_enabled'] == true) {
    Debug::text('CRON: Installer is enabled, skipping cron jobs for now...', __FILE__, __LINE__, __METHOD__, 0);
} elseif (isset($config_vars['other']['down_for_maintenance']) and $config_vars['other']['down_for_maintenance'] == true) {
    Debug::text('CRON: System is down for maintenance, skipping cron jobs for now...', __FILE__, __LINE__, __METHOD__, 0);
} else {
    //$current_epoch = strtotime('28-Mar-08 1:30 PM');
    $current_epoch = TTDate::getTime();

    $executed_jobs = 0;

    $cjlf = new CronJobListFactory();
    $job_arr = $cjlf->getArrayByListFactory($cjlf->getAll());
    $total_jobs = count($job_arr);
    foreach ($job_arr as $job_id => $job_name) {
        //Get each cronjob row again individually incase the status has changed.
        $cjlf = new CronJobListFactory();
        $cjlf->getById($job_id); //Let Execute determine if job is running or not so it can find orphans.
        if ($cjlf->getRecordCount() > 0) {
            foreach ($cjlf as $cjf_obj) {
                //Debug::text('Checking if Job ID: '. $job_id .' is scheduled to run...', __FILE__, __LINE__, __METHOD__, 0);
                if ($cjf_obj->isScheduledToRun($current_epoch) == true) {
                    $executed_jobs++;
                    $cjf_obj->Execute($config_vars['path']['php_cli'], dirname(__FILE__));
                }
            }
        }
    }
    echo "NOTE: Jobs are scheduled to run at specific times each day, therefore it is normal for only some jobs to be executed each time this file is run.\n";
    echo "Jobs Executed: $executed_jobs of $total_jobs\n";
    Debug::text('CRON: Jobs Executed: ' . $executed_jobs . ' of ' . $total_jobs, __FILE__, __LINE__, __METHOD__, 0);

    //Save file to log directory with the last executed date, so we know if the CRON daemon is actually calling us.
    $file_name = $config_vars['path']['log'] . DIRECTORY_SEPARATOR . 'fairness_cron_last_executed.log';
    @file_put_contents($file_name, TTDate::getDate('DATE+TIME', time()) . "\n");
}
Debug::writeToLog();
Debug::Display();
