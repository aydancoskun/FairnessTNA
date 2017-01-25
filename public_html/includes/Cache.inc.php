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

require_once(Environment::getBasePath() . 'classes' . DIRECTORY_SEPARATOR . 'pear' . DIRECTORY_SEPARATOR . 'Cache' . DIRECTORY_SEPARATOR . 'Lite.php');

//If caching is disabled, still do memory caching, otherwise permission checks cause the page to take 2+ seconds to load.
if ($config_vars['cache']['enable'] == false) {
    $config_vars['cache']['only_memory_cache_enable'] = true;
} else {
    $config_vars['cache']['only_memory_cache_enable'] = false;
}

$cache_options = array(
    'caching' => true,
    'cacheDir' => $config_vars['cache']['dir'] . DIRECTORY_SEPARATOR,
    'lifeTime' => 86400, //604800, //One day, cache should be cleared when the data is modified
    'fileLocking' => true,
    'writeControl' => true,
    'readControl' => true,
    'memoryCaching' => true,
    'onlyMemoryCaching' => $config_vars['cache']['only_memory_cache_enable'],
    'automaticSerialization' => true,
    'hashedDirectoryLevel' => 1,
    'fileNameProtection' => false,
    'redisHost' => (isset($config_vars['cache']['redis_host'])) ? $config_vars['cache']['redis_host'] : '',
    'redisDB' => (isset($config_vars['cache']['redis_db'])) ? $config_vars['cache']['redis_db'] : '',
);

if (isset($config_vars['cache']['redis_host']) and $config_vars['cache']['redis_host'] != '') {
    require_once(Environment::getBasePath() . 'classes' . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . 'other' . DIRECTORY_SEPARATOR . 'Redis_Cache_Lite.class.php');
    $cache = $ADODB_CACHE = new Redis_Cache_Lite($cache_options);
} else {
    $cache = new Cache_Lite($cache_options);
}
