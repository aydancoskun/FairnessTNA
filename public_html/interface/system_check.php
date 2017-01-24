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
$skip_db_error_exception = true; //Skips DB error redirect
try {
    require_once('../includes/global.inc.php');
} catch (Exception $e) {
    echo 'FAIL (100) - ' . $e->getMessage();
    exit;
}
//Debug::setVerbosity(11);

//Confirm database connection is up and maintenance jobs have run recently...
if (PRODUCTION == true) {
    $cjlf = TTnew('CronJobListFactory');
    $cjlf->getMostRecentlyRun();
    if ($cjlf->getRecordCount() > 0) {
        $last_run_date_diff = time() - $cjlf->getCurrent()->getLastRunDate();
        if ($last_run_date_diff > 1800) { //Must run in the last 30mins.
            echo 'FAIL! (200)';
            exit;
        }
    }
}

//If caching is enabled, make sure cache directory exists and is writeable.
if (isset($config_vars['cache']['enable']) and $config_vars['cache']['enable'] == true) {
    if (isset($config_vars['cache']['redis_host']) and $config_vars['cache']['redis_host'] != '') {
        $tmp_f = TTnew('SystemSettingFactory');
        $random_value = sha1(time());
        $tmp_f->saveCache($random_value, 'system_check');
        $result = $tmp_f->getCache('system_check');
        if ($random_value != $result) {
            echo 'FAIL! (320)';
            exit;
        }
        $tmp_f->removeCache('system_check');
    } elseif (file_exists($config_vars['cache']['dir']) == false) {
        echo 'FAIL! (300)';
        exit;
    } else {
        if (is_writeable($config_vars['cache']['dir']) == false) {
            echo 'FAIL (310)';
            exit;
        }
    }
}

//Everything is good.
echo 'OK';
