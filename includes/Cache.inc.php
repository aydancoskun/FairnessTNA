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

require_once( Environment::getBasePath() .'classes'. DIRECTORY_SEPARATOR .'pear'. DIRECTORY_SEPARATOR .'Cache'. DIRECTORY_SEPARATOR .'Lite.php');

//If caching is disabled, still do memory caching, otherwise permission checks cause the page to take 2+ seconds to load.
if ( $config_vars['cache']['enable'] == FALSE ) {
	$config_vars['cache']['only_memory_cache_enable'] = TRUE;
} else {
	$config_vars['cache']['only_memory_cache_enable'] = FALSE;
}

$cache_options = array(
		'caching' => TRUE,
		'cacheDir' => $config_vars['cache']['dir'] . DIRECTORY_SEPARATOR,
		'lifeTime' => 86400, //604800, //One day, cache should be cleared when the data is modified
		'fileLocking' => TRUE,
		'writeControl' => TRUE,
		'readControl' => TRUE,
		'memoryCaching' => TRUE,
		'onlyMemoryCaching' => $config_vars['cache']['only_memory_cache_enable'],
		'automaticSerialization' => TRUE,
		'hashedDirectoryLevel' => 1,
		'fileNameProtection' => FALSE,
		'redisHost' => ( isset($config_vars['cache']['redis_host']) ) ? $config_vars['cache']['redis_host'] : '',
		'redisDB' => ( isset($config_vars['cache']['redis_db']) ) ? $config_vars['cache']['redis_db'] : '',
);

if ( isset($config_vars['cache']['redis_host']) AND $config_vars['cache']['redis_host'] != '' ) {
	require_once( Environment::getBasePath() .'classes'. DIRECTORY_SEPARATOR .'modules'. DIRECTORY_SEPARATOR .'other'. DIRECTORY_SEPARATOR .'Redis_Cache_Lite.class.php');
	$cache = $ADODB_CACHE = new Redis_Cache_Lite($cache_options);
} else {
	$cache = new Cache_Lite($cache_options);
}
?>