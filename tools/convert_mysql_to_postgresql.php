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

require_once( dirname(__FILE__) . DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'includes'. DIRECTORY_SEPARATOR .'global.inc.php');
require_once( dirname(__FILE__) . DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'includes'. DIRECTORY_SEPARATOR .'CLI.inc.php');

/*
 Proceedure to Convert MySQL to PostgreSQL:
*/


if ( $argc < 2 OR in_array ($argv[1], array('--help', '-help', '-h', '-?') ) ) {
	$help_output = "Usage: convert_mysql_to_postgresql.php [data]\n";
	$help_output .= " [data] = 'truncate'\n";
	echo $help_output;
} else {
	//Handle command line arguments
	$last_arg = count($argv)-1;

	if ( isset($db) AND is_object($db) AND strncmp($db->databaseType,'mysql',5) != 0) {
		echo "ERROR: This script must be run on MySQL only!";
		exit(255);
	}

	if ( isset($argv[$last_arg]) AND $argv[$last_arg] != '' ) {
		$type = trim(strtolower($argv[$last_arg]));

		$dict = NewDataDictionary($db);
		$tables = $dict->MetaTables();

		$sequence_modifier = 1000;

		$out = NULL;
		foreach( $tables as $table ) {
			if ( $type == 'truncate' ) {
				echo 'TRUNCATE '. $table .';'."\n";
			}
		}
	}
}

//Debug::Display();
?>
