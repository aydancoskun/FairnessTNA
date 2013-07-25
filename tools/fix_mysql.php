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
 * $Revision: 1246 $
 * $Id: fix_mysql.php 1246 2007-09-14 23:47:42Z ipso $
 * $Date: 2007-09-14 16:47:42 -0700 (Fri, 14 Sep 2007) $
 */
require_once( dirname(__FILE__) . DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'includes'. DIRECTORY_SEPARATOR .'global.inc.php');
require_once( dirname(__FILE__) . DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'includes'. DIRECTORY_SEPARATOR .'CLI.inc.php');

if ( isset($argv[1]) AND in_array($argv[1], array('--help', '-help', '-h', '-?') ) ) {
	$help_output = "Usage: fix_mysql.php\n";
	echo $help_output;
} else {
	//Handle command line arguments
	$last_arg = count($argv)-1;

	if ( isset($db) AND is_object($db) AND strncmp($db->databaseType,'mysql',5) != 0 ) {
		echo "This script must be run on MySQL only!";
		exit;
	}

	$dict = NewDataDictionary($db);
	$tables = $dict->MetaTables();

	$sequence_modifier = 1000;

	$db->StartTrans();

	$out = NULL;
	foreach( $tables as $table ) {
		if ( strpos($table, '_seq') !== FALSE ) {
			//echo "Found Sequence Table: ". $table ."<br>\n";
			$query = 'select id from '. $table;
			$last_sequence_value = $db->GetOne($query) + $sequence_modifier;
			$query = 'UPDATE '. $table .' set ID = '. $last_sequence_value ;
			//echo "Query: ". $query ."\n";
			$db->Query( $query );
		}
	}

	echo "Done.\n";

	$db->CompleteTrans();
}

Debug::writeToLog();
?>
