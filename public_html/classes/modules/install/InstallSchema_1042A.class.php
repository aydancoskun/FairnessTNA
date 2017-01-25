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
 * @package Modules\Install
 */
class InstallSchema_1042A extends InstallSchema_Base
{
    public function preInstall()
    {
        Debug::text('preInstall: ' . $this->getVersion(), __FILE__, __LINE__, __METHOD__, 9);

        return true;
    }


    public function postInstall()
    {
        Debug::text('postInstall: ' . $this->getVersion(), __FILE__, __LINE__, __METHOD__, 9);

        //Add calcQuickException cronjob to database.
        $cjf = TTnew('CronJobFactory');
        $cjf->setName('calcQuickExceptions');
        //This is primarily for Late Starting/Ending Shift, assume a 5 minute grace period, so notifications
        //can be emailed out as soon as 7 minutes after the hour and every 15 minute intervals thereafter.
        $cjf->setMinute('7, 22, 37, 52');
        $cjf->setHour('*');
        $cjf->setDayOfMonth('*');
        $cjf->setMonth('*');
        $cjf->setDayOfWeek('*');
        $cjf->setCommand('calcQuickExceptions.php');
        $cjf->Save();

        return true;
    }
}
