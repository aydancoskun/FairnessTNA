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
require_once(Environment::getBasePath() . '/classes/pear/System/SharedMemory.php');

class SharedMemory
{
    protected $obj = null;

    public function __construct()
    {
        global $config_vars;

        $shared_memory = new System_SharedMemory();
        if (isset($config_vars['cache']['redis_host']) and $config_vars['cache']['redis_host'] != '') {
            $split_server = explode(',', $config_vars['cache']['redis_host']);
            $host = $split_server[0]; //Use just the master server.

            $this->obj = $shared_memory->Factory('Redis', array('host' => $host, 'db' => (isset($config_vars['cache']['redis_db'])) ? $config_vars['cache']['redis_db'] : '', 'timeout' => 1));
        } else {
            if (OPERATING_SYSTEM == 'WIN') {
                $this->obj = $shared_memory->Factory('File', array('tmp' => $config_vars['cache']['dir']));
            } else {
                $this->obj = $shared_memory->Factory('File', array('tmp' => $config_vars['cache']['dir']));
                ////$this->obj = &System_SharedMemory::Factory( 'Systemv', array( 'size' => $size ) ); //Run into size issues all the time.
            }
        }

        return true;
    }

    public function set($key, $value)
    {
        if (is_string($key)) {
            return $this->obj->set($key, $value);
        }
        return false;
    }

    public function get($key)
    {
        if (is_string($key)) {
            return $this->obj->get($key);
        }
        return false;
    }

    public function delete($key)
    {
        if (is_string($key)) {
            return $this->obj->rm($key);
        }
        return false;
    }
}
