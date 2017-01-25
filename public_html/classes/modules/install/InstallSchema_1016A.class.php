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
class InstallSchema_1016A extends InstallSchema_Base
{
    protected $station_users = array();

    public function preInstall()
    {
        Debug::text('preInstall: ' . $this->getVersion(), __FILE__, __LINE__, __METHOD__, 9);

        return true;
    }

    public function postInstall()
    {
        // @codingStandardsIgnoreStart
        global $cache;
        //assumed needed elsewhere
        // @codingStandardsIgnoreEnd

        Debug::text('postInstall: ' . $this->getVersion(), __FILE__, __LINE__, __METHOD__, 9);

        Debug::text('l: ' . $this->getVersion(), __FILE__, __LINE__, __METHOD__, 9);

        $cjlf = TTnew('CronJobListFactory');
        $cjlf->getAll();
        if ($cjlf->getRecordCount() > 0) {
            foreach ($cjlf as $cj_obj) {
                Debug::text('Original Command: ' . $cj_obj->getCommand(), __FILE__, __LINE__, __METHOD__, 9);
                preg_match('/([A-Za-z0-9]+\.php)/i', $cj_obj->getCommand(), $matches);

                if (isset($matches[0]) and $matches[0] != '') {
                    Debug::text('New Command: ' . $matches[0], __FILE__, __LINE__, __METHOD__, 9);
                    $cj_obj->setCommand($matches[0]);
                    if ($cj_obj->isValid()) {
                        $cj_obj->Save();
                    }
                }
            }
        }

        return true;
    }
}
