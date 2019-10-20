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

if ( $argc < 2 OR in_array($argv[1], array('--help', '-help', '-h', '-?') ) ) {
	$help_output = "Usage: uuid.php [integer_to_convert]\n";
	echo $help_output;
} else {
	//Handle command line arguments
	$last_arg = count($argv)-1;

	if ( in_array('--random_seed', $argv) ) {
		echo "Random Seed: uuid_seed = " . TTUUID::generateRandomSeed() . "\n";
	} elseif ( in_array('--primary_company', $argv) ) {
		echo "Primary Company ID: ". PRIMARY_COMPANY_ID ." UUID: ". TTUUID::convertIntToUUID( PRIMARY_COMPANY_ID ) ."\n";
	} elseif ( in_array('--benchmark', $argv) ) {
		$start_time = microtime(TRUE);
		$max = 1000000;
		for($i = 0; $i < $max; $i++ ) {
			$uuid_arr[] = TTUUID::generateUUID();
		}
		$end_time = microtime(TRUE);

		$unique_uuid_arr = array_unique($uuid_arr);
		if ( TRUE OR count($uuid_arr) != count($unique_uuid_arr) ) {
			echo "ERROR: Duplicate UUID generation detected!\n";
			echo "  Raw UUIDs: ". count($uuid_arr) ." Unique UUIDs: ". count($unique_uuid_arr) ."\n\n";
			unset($uuid_arr, $unique_uuid_arr);
			flush();
			ob_flush();

			//Test large timestamps.
			$start_time = microtime(TRUE);
			for($i = 0; $i < $max; $i++ ) {
				$timestamps_arr[] = microtime( TRUE ) * 10000000 + 0x01b21dd213814000;
			}
			$end_time = microtime(TRUE);
			$unique_timestamps_arr = array_unique($timestamps_arr);
			echo "  Raw Large TimeStamps: ". count($timestamps_arr) ." Unique Large TimeStamps: ". count($unique_timestamps_arr) ." Time: ". ( $end_time - $start_time ) ."s\n";
			unset($timestamps_arr, $unique_timestamps_arr);

			//Test large timestamps.
			for($i = 0; $i < $max; $i++ ) {
				$timestamps_arr[] = microtime( TRUE ) * 10000000 + 0x01b21dd213814000 + $i;
			}
			$unique_timestamps_arr = array_unique($timestamps_arr);
			echo "  Raw Counter TimeStamps: ". count($timestamps_arr) ." Unique Counter TimeStamps: ". count($unique_timestamps_arr) ."\n";
			unset($timestamps_arr, $unique_timestamps_arr);

			//Test regular timestamps.
			for($i = 0; $i < $max; $i++ ) {
				$timestamps_arr[] = microtime( TRUE );
			}
			$unique_timestamps_arr = array_unique($timestamps_arr);
			echo "  Raw TimeStamps: ". count($timestamps_arr) ." Unique TimeStamps: ". count($unique_timestamps_arr) ."\n";
			unset($timestamps_arr, $unique_timestamps_arr);

			//Test random bytes.
			$strong_crypto = FALSE;
			for($i = 0; $i < $max; $i++ ) {
				$random_bytes_arr[] = bin2hex( openssl_random_pseudo_bytes( 2, $strong_crypto ) );
				if ( $strong_crypto == FALSE ) {
					echo "ERROR: openssl not using string crypto!\n";
					exit;
				}
			}
			$unique_random_bytes_arr = array_unique($random_bytes_arr);
			echo "  Raw Random Bytes: ". count($random_bytes_arr) ." Unique Random Bytes: ". count($unique_random_bytes_arr) ."\n";
			unset($random_bytes_arr, $unique_random_bytes_arr);

			//Test timestamps + random bytes.
			for($i = 0; $i < $max; $i++ ) {
				$pseudo_uuid_arr[] = microtime( TRUE ) * 10000000 + 0x01b21dd213814000 . bin2hex( openssl_random_pseudo_bytes( 2 ) );
			}
			$unique_pseudo_uuid_arr = array_unique($pseudo_uuid_arr);
			echo "  Raw Psuedo UUIDs: ". count($pseudo_uuid_arr) ." Unique Psuedo UUID: ". count($unique_pseudo_uuid_arr) ."\n";
			unset($pseudo_uuid_arr, $unique_pseudo_uuid_arr);
		}

		echo "Total Time for ". $max ." UUIDs: ". ( $end_time - $start_time ) ."s\n";
	} else {
		if ( isset( $argv[ $last_arg ] ) AND $argv[ $last_arg ] != '' ) {
			$integer = $argv[ $last_arg ];
		} else {
			$integer = 0;
		}

		$uuid = TTUUID::convertIntToUUID( $integer );
		echo "Integer: " . $integer . " converts to UUID: " . $uuid . "\n";

		//PGSQL:
		echo "\n";
		echo "To convert integer ID columns to UUID in PostgreSQL use the following example query: \n";
		echo "ALTER TABLE <table> ALTER COLUMN <column> DROP DEFAULT, ALTER COLUMN <column> SET DATA TYPE UUID USING uuid( concat( '" . TTUUID::getConversionPrefix() . "-', lpad( text( <column> ), 12, '0' ) ) );\n";
	}
}
echo "\n";

//Debug::Display();
Debug::writeToLog();
?>
