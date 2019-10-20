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

if ( isset($argv[1]) AND in_array($argv[1], array('--help', '-help', '-h', '-?') ) ) {
	$help_output = "Usage: pivot_table.php [OPTIONS] [Input CSV File] [Output CSV File]\n";
	$help_output .= "  Options:\n";
	$help_output .= "    -pivot_column [Existing column to pivot on]\n";
	$help_output .= "    -category_column [New column name for categories. ie: 'Pay Stub Accounts']\n";
	$help_output .= "    -data_column [New column name for data. ie: 'Amount']\n";

	echo $help_output;
} else {
	//Handle command line arguments
	$last_arg = count($argv)-1;

	if ( in_array('-pivot_column', $argv) ) {
		$data['pivot_column'] = trim($argv[(array_search('-pivot_column', $argv) + 1)]);
	} else {
		$data['pivot_column'] = FALSE; //Default to first column.
	}

	if ( in_array('-category_column', $argv) ) {
		$data['category_column'] = trim($argv[(array_search('-category_column', $argv) + 1)]);
	} else {
		$data['category_column'] = 'Category';
	}

	if ( in_array('-data_column', $argv) ) {
		$data['data_column'] = trim($argv[(array_search('-data_column', $argv) + 1)]);
	} else {
		$data['data_column'] = 'Data';
	}

	$input_file = $argv[count($argv)-2];
	$output_file = $argv[count($argv)-1];

	if ( file_exists($input_file) ) {
		$input_arr = Misc::parseCSV( $input_file, TRUE );
		if ( is_array($input_arr) ) {
			if ( $data['pivot_column'] == FALSE ) {
				$data['pivot_column'] = key($input_arr[0]);
			}

			$i = 0;
			foreach( $input_arr as $input_row ) {
				foreach( $input_row as $input_column_name => $input_column_data ) {
					if ( $input_column_name != $data['pivot_column'] AND $input_column_data != '' ) {
						if ( isset($input_row[$data['pivot_column']]) ) {
							$output_arr[$i][$data['pivot_column']] = $input_row[$data['pivot_column']];
						}

						$output_arr[$i][$data['category_column']] = $input_column_name;
						$output_arr[$i][$data['data_column']] = $input_column_data;

						$i++;
					}
				}
			}

			if ( isset($output_arr) ) {
				$column_keys = array_keys($output_arr[0]);
				foreach( $column_keys as $column_key ) {
					$columns[$column_key] = $column_key;
				}

				$output_csv = Misc::Array2CSV( $output_arr, $columns, FALSE );
				file_put_contents( $output_file, $output_csv );
			}
		} else {
			echo "ERROR: Unable to parse input file...\n";
		}
	}

	echo "Done.\n";
}

Debug::writeToLog();
//Debug::Display();
?>
