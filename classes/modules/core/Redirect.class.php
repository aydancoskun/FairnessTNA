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
 * $Revision: 2055 $
 * $Id: Redirect.class.php 2055 2008-08-20 15:27:04Z ipso $
 * $Date: 2008-08-20 08:27:04 -0700 (Wed, 20 Aug 2008) $
 */

/**
 * @package Core
 */
class Redirect {
	static function page($url = NULL) {
		if ( empty($url) AND !empty($_SERVER['HTTP_REFERER']) ) {
			$url = $_SERVER['HTTP_REFERER'];
		}

		Debug::Text('Redirect URL: '. $url, __FILE__, __LINE__, __METHOD__,11);

		if ( Debug::getVerbosity() != 11 ) {
			header("Location: $url\n\n");

			//Prevent the rest of the script from running after redirect?
			Debug::writeToLog();

			ob_clean();
			exit;
		}

		return TRUE;
	}
}
?>
