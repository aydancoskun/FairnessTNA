<?php
/*********************************************************************************
 * This file is part of "Fairness", a Payroll and Time Management program.
 * Fairness is Copyright 2013 Aydan Coskun (aydan.ayfer.coskun@gmail.com)
 * Portions of this software are Copyright (C) 2003 - 2013 TimeTrex Software Inc.
 * because Fairness is a fork of "TimeTrex Workforce Management" Software.
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
/*
 * $Revision: 4279 $
 * $Id: Cache.inc.php 4279 2011-02-22 00:11:42Z ipso $
 * $Date: 2011-02-21 16:11:42 -0800 (Mon, 21 Feb 2011) $
 */
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
);

$cache = new Cache_Lite($cache_options);
?>