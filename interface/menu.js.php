<?php
/*********************************************************************************
 * This file is part of "Fairness", a Payroll and Time Management program.
 * Fairness is Copyright 2013 Aydan Coscun (aydan.ayfer.coskun@gmail.com)
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
 * $Revision: 3208 $
 * $Id: menu.js.php 3208 2009-12-23 00:37:01Z ipso $
 * $Date: 2009-12-22 16:37:01 -0800 (Tue, 22 Dec 2009) $
 */
$disable_cache_control = TRUE;
require_once('..'. DIRECTORY_SEPARATOR .'includes'. DIRECTORY_SEPARATOR.'global.inc.php');
require_once('..'. DIRECTORY_SEPARATOR .'includes'. DIRECTORY_SEPARATOR.'Interface.inc.php');

//Cache this file as long as:
// 1. Permissions don't change
// 2. Messages don't change
// 3. Exceptions don't change
// 4. New version check doesn't change.
// 5. User ID doesn't change.
$etag = @md5($current_user->getId().$display_exception_flag.$unread_messages.$system_settings['new_version'].$permission->getLastUpdatedDate() );

forceCacheHeaders( NULL, NULL, $etag );

$smarty->display('menu.tpl');
?>