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
if ( PHP_SAPI != 'cli' ) {
	echo "This script can only be called from the Command Line.\n";
	exit;
}
require_once( dirname(__FILE__) . DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'classes'. DIRECTORY_SEPARATOR .'modules'. DIRECTORY_SEPARATOR .'api'. DIRECTORY_SEPARATOR .'client'. DIRECTORY_SEPARATOR .'ClientAPI.class.php');

//Example:	php export_report.php -server "http://192.168.1.1/api/soap/api.php" -username myusername -password mypass -report UserSummaryReport -template "by_employee+contact" /tmp/employee_list.csv csv
//			php export_report.php -server "http://192.168.1.1/api/soap/api.php" -username myusername -password mypass -report UserSummaryReport -time_period last_year /tmp/employee_list.csv csv
//			php export_report.php -server "http://192.168.1.1/api/soap/api.php" -username myusername -password mypass -report UserSummaryReport -time_period custom_date -filter start_date=01-Jan-19,end_date=31-Jan-19 /tmp/employee_list.csv csv
//			php export_report.php -server "http://192.168.1.1/api/soap/api.php" -username myusername -password mypass -report UserSummaryReport -time_period custom_date -filter date_stamp="\=>29-Jun-19 & <\=29-Jun-19" /tmp/employee_list.csv csv
if ( $argc < 3 OR in_array ($argv[1], array('--help', '-help', '-h', '-?') ) ) {
	$help_output = "Usage: export_report.php [OPTIONS] [output file] [file format]\n";
	$help_output .= "\n";
	$help_output .= "  Options:\n";
	$help_output .= "    -server <URL>				URL to API server\n";
	$help_output .= "    -username <username>		API username\n";
	$help_output .= "    -password <password>		API password\n";
	$help_output .= "    -report <report>			Report to export (ie: TimesheetDetailReport,TimesheetSummaryReport,ScheduleSummaryReport,UserSummaryReport,PayStubSummaryReport)\n";
	$help_output .= "    -saved_report <name>		Name of saved report\n";
	$help_output .= "    -template <template>		Name of template\n";
	$help_output .= "    -time_period <name>		Time Period for report\n";
	$help_output .= "    -filter <name>=<value>,<name>=<value>		Other filter options\n";

	echo $help_output;
} else {
	//Handle command line arguments
	$last_arg = count($argv)-1;

	if ( in_array('-n', $argv) ) {
		$dry_run = TRUE;
	} else {
		$dry_run = FALSE;
	}

	if ( in_array('-server', $argv) ) {
		$api_url = trim($argv[array_search('-server', $argv)+1]);
	} else {
		$api_url = FALSE;
	}

	if ( in_array('-username', $argv) ) {
		$username = trim($argv[array_search('-username', $argv)+1]);
	} else {
		$username = FALSE;
	}

	if ( in_array('-password', $argv) ) {
		$password = trim($argv[array_search('-password', $argv)+1]);
	} else {
		$password = FALSE;
	}

	if ( in_array('-report', $argv) ) {
		$report = trim($argv[array_search('-report', $argv)+1]);
	} else {
		$report = FALSE;
	}

	if ( in_array('-template', $argv) ) {
		$template = trim($argv[array_search('-template', $argv)+1]);
	} else {
		$template = FALSE;
	}

	if ( in_array('-saved_report', $argv) ) {
		$saved_report = trim($argv[array_search('-saved_report', $argv)+1]);
	} else {
		$saved_report = FALSE;
	}

	if ( in_array('-time_period', $argv) ) {
		$time_period = trim($argv[array_search('-time_period', $argv)+1]);
	} else {
		$time_period = FALSE;
	}

	if ( in_array('-filter', $argv) ) {
		//Allow handling escapted deliminters so we can handle date ranges like: >=01-Jan-18 without the "=" being treated as a different name/value pair.
		$other_filter = preg_split('~(?<!\\\)' . preg_quote(',', '~') . '~', trim($argv[array_search('-filter', $argv)+1]) );
		if ( is_array($other_filter) ) {
			foreach( $other_filter as $tmp_other_filter ) {
				//$split_other_filter = explode('=', $tmp_other_filter);
				$split_other_filter = preg_split('~(?<!\\\)' . preg_quote('=', '~') . '~', $tmp_other_filter);
				if ( isset($split_other_filter[0]) AND isset($split_other_filter[1]) ) {
					$split_other_filter[1] = str_replace( '\=', '=', $split_other_filter[1] ); //Unescape deliminter
					if ( isset($override_filter[ $split_other_filter[0] ]) ) { //Handle array of data.
						$override_filter[ $split_other_filter[0] ][] = $split_other_filter[1];
					} else {
						$override_filter[ $split_other_filter[0] ] = $split_other_filter[1];
					}
				}
			}
		}
	} else {
		$override_filter = FALSE;
	}

	$output_file = NULL;
	if ( isset($argv[$last_arg-1]) AND $argv[$last_arg-1] != '' ) {
		$output_file = $argv[$last_arg-1];
	}

	$file_format = 'csv';
	if ( isset($argv[$last_arg]) AND $argv[$last_arg] != '' ) {
		$file_format = $argv[$last_arg];
	}

	if ( !isset($output_file) ) {
		echo "Output File not set!\n";
		exit;
	}

	$FAIRNESS_URL = $api_url;

	$api_session = new ClientAPI();
	$api_session->Login( $username, $password );
	if ( $FAIRNESS_SESSION_ID == FALSE ) {
		echo "API Username/Password is incorrect!\n";
		exit(1);
	}
	//echo "Session ID: $FAIRNESS_SESSION_ID\n";

	if ( $report != '' ) {
		$report_obj = new ClientAPI( $report );

		$config = array();
		if ( $saved_report != '' ) {
			$saved_report_obj = new ClientAPI( 'UserReportData' );
			$saved_report_result = $saved_report_obj->getUserReportData( array('filter_data' => array('name' => trim($saved_report) ) ) );
			$saved_report_data = $saved_report_result->getResult();
			if ( is_array($saved_report_data) AND isset($saved_report_data[0]) AND isset($saved_report_data[0]['data']) ) {
				$config = $saved_report_data[0]['data']['config'];
			} else {
				echo "ERROR: Saved report not found...\n";
				exit(1);
			}
		} elseif ( $template != '' ) {
			$config_result = $report_obj->getTemplate( $template );
			$config = $config_result->getResult();
		}

		if ( $time_period != '' ) {
			$config['time_period']['time_period'] = $time_period;
		}

		if ( isset($override_filter) AND is_array( $override_filter ) ) {
			$config = array_merge( $config, $override_filter );
		}
		//var_dump($config);

		$result = $report_obj->getReport( $config, strtolower($file_format) );
		$retval = $result->getResult();
		if ( is_array($retval) ) {
			if ( isset($retval['file_name']) AND $output_file == '' ) {
				$output_file = $retval['file_name'];
			}
			file_put_contents( $output_file, base64_decode($retval['data']) );
		} else {
			var_dump($retval);
			echo "ERROR: No report data...\n";
			exit(1);
		}
	} else {
		echo "ERROR: No report specified...\n";
		exit(1);
	}
}
?>
