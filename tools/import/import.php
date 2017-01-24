<?php
/*********************************************************************************
 * This file is part of "Fairness", a Payroll and Time Management program.
 * Fairness is Copyright 2013 Aydan Coskun (aydan.ayfer.coskun@gmail.com)
 * Portions of this software are Copyright of T i m e T r e x Software Inc.
 * Fairness is a fork of "T i m e T r e x Workforce Management" Software.
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

require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'client' . DIRECTORY_SEPARATOR . 'FairnessClientAPI.class.php');

error_reporting(E_ALL);
ini_set('display_errors', 1); //Try to display any errors that may arise from the API.

function Array2CSV($data, $columns = null, $ignore_last_row = true, $include_header = true, $eol = "\n")
{
    if (is_array($data) and count($data) > 0
        and is_array($columns) and count($columns) > 0
    ) {
        if ($ignore_last_row === true) {
            array_pop($data);
        }

        //Header
        if ($include_header == true) {
            foreach ($columns as $column_name) {
                $row_header[] = $column_name;
            }
            $out = '"' . implode('","', $row_header) . '"' . $eol;
        } else {
            $out = null;
        }

        foreach ($data as $rows) {
            foreach ($columns as $column_key => $column_name) {
                if (isset($rows[$column_key])) {
                    $row_values[] = str_replace("\"", "\"\"", $rows[$column_key]);
                } else {
                    //Make sure we insert blank columns to keep proper order of values.
                    $row_values[] = null;
                }
            }

            $out .= '"' . implode('","', $row_values) . '"' . $eol;
            unset($row_values);
        }

        return $out;
    }

    return false;
}

function parseCSV($file, $head = false, $first_column = false, $delim = ",", $len = 9216, $max_lines = null)
{
    if (!file_exists($file)) {
        Debug::text('Files does not exist: ' . $file, __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    $return = false;
    $handle = fopen($file, 'r');
    if ($head !== false) {
        if ($first_column !== false) {
            while (($header = fgetcsv($handle, $len, $delim)) !== false) {
                if ($header[0] == $first_column) {
                    //echo "FOUND HEADER!<br>\n";
                    $found_header = true;
                    break;
                }
            }

            if ($found_header !== true) {
                return false;
            }
        } else {
            $header = fgetcsv($handle, $len, $delim);
        }
    }

    $i = 1;
    while (($data = fgetcsv($handle, $len, $delim)) !== false) {
        if ($head and isset($header)) {
            foreach ($header as $key => $heading) {
                $row[trim($heading)] = (isset($data[$key])) ? $data[$key] : '';
            }
            $return[] = $row;
        } else {
            $return[] = $data;
        }

        if ($max_lines !== null and $max_lines != '' and $i == $max_lines) {
            break;
        }

        $i++;
    }

    fclose($handle);

    return $return;
}

if ($argc < 3 or in_array($argv[1], array('--help', '-help', '-h', '-?'))) {
    $help_output = "Usage: import.php [OPTIONS] [Column MAP file] [CSV File]\n";
    $help_output .= "\n";
    $help_output .= "  Options:\n";
    $help_output .= "    -server <URL>				URL to API server\n";
    $help_output .= "    -username <username>		API username\n";
    $help_output .= "    -password <password>		API password\n";
    $help_output .= "    -object <object>			Object to import (ie: User,Branch,Punch)\n";
    $help_output .= "    -f <flag>				Custom flags, ie: fuzzy_match,update\n";
    $help_output .= "    -n 					Dry-run, display the first two lines to confirm mapping is correct\n";
    $help_output .= "    -export_map <name>		Export the mapping information from the web interface saved as <name>\n";

    echo $help_output;
} else {
    //Handle command line arguments
    $last_arg = (count($argv) - 1);

    if (in_array('-n', $argv)) {
        $dry_run = true;
    } else {
        $dry_run = false;
    }

    if (in_array('-server', $argv)) {
        $api_url = trim($argv[(array_search('-server', $argv) + 1)]);
    } else {
        $api_url = false;
    }

    if (in_array('-username', $argv)) {
        $username = trim($argv[(array_search('-username', $argv) + 1)]);
    } else {
        $username = false;
    }

    if (in_array('-password', $argv)) {
        $password = trim($argv[(array_search('-password', $argv) + 1)]);
    } else {
        $password = false;
    }

    if (in_array('-object', $argv)) {
        $object = trim($argv[(array_search('-object', $argv) + 1)]);
    } else {
        $object = false;
    }

    if (in_array('-f', $argv)) {
        $raw_flags = trim($argv[(array_search('-f', $argv) + 1)]);
        if (strpos($raw_flags, ',') !== false) {
            $raw_flag_split = explode(',', $raw_flags);
            if (is_array($raw_flag_split)) {
                foreach ($raw_flag_split as $tmp_flag) {
                    $flags[$tmp_flag] = true;
                }
            }
        } else {
            $flags = array($raw_flags => true);
        }
    } else {
        $flags = array();
    }

    if (in_array('-export_map', $argv)) {
        $export_map = trim($argv[(array_search('-export_map', $argv) + 1)]);
    } else {
        $export_map = false;
    }

    if ($export_map == false) {
        if (isset($argv[($last_arg - 1)]) and $argv[($last_arg - 1)] != '') {
            if (!file_exists($argv[($last_arg - 1)]) or !is_readable($argv[($last_arg - 1)])) {
                echo "Column MAP File: " . $argv[($last_arg - 1)] . " does not exist or is not readable!\n";
            } else {
                $column_map_file = $argv[($last_arg - 1)];
            }
        }

        if (isset($argv[$last_arg]) and $argv[$last_arg] != '') {
            if (!file_exists($argv[$last_arg]) or !is_readable($argv[$last_arg])) {
                echo "Import CSV File: " . $argv[$last_arg] . " does not exist or is not readable!\n";
            } else {
                $import_csv_file = $argv[$last_arg];
            }
        }

        if (!isset($column_map_file)) {
            echo "ERROR: Column Map File not set!\n";
            exit;
        }
    } else {
        if (isset($argv[$last_arg]) and $argv[$last_arg] != '') {
            if (file_exists($argv[$last_arg])) { //OR !is_writable( $argv[$last_arg] ) ) {
                echo "Column Map File: " . $argv[$last_arg] . " already exists or is not writable!\n";
            } else {
                $column_map_file = $argv[$last_arg];
            }
        }

        if (!isset($column_map_file)) {
            echo "ERROR: Column Map File not set!\n";
            exit;
        }
    }

    $FAIRNESS_URL = $api_url;

    $api_session = new FairnessClientAPI();
    $api_session->Login($username, $password);
    if ($FAIRNESS_SESSION_ID == false) {
        echo "API Username/Password is incorrect!\n";
        exit;
    }
    //echo "Session ID: $FAIRNESS_SESSION_ID\n";

    if ($object != '') {
        if ($export_map == false) {
            $column_map = parseCSV($column_map_file, true, false, ',', 9216);
            if (is_array($column_map)) {
                foreach ($column_map as $column_map_row) {
                    if (isset($column_map_row['fairness_column'])) {
                        $column_map_arr[$column_map_row['fairness_column']] = array('map_column_name' => $column_map_row['csv_column'], 'default_value' => $column_map_row['default_value'], 'parse_hint' => $column_map_row['parse_hint']);
                    } elseif (isset($column_map_row['import_column'])) {
                        $column_map_arr[$column_map_row['import_column']] = array('map_column_name' => $column_map_row['map_column_name'], 'default_value' => $column_map_row['default_value'], 'parse_hint' => $column_map_row['parse_hint']);
                    }
                }
            } else {
                echo "Column map is invalid!\n";
            }

            $obj = new FairnessClientAPI('Import' . ucfirst($object));
            $obj->setRawData(file_get_contents($import_csv_file));
            //var_dump( $obj->getOptions('columns') );

            $retval = $obj->Import($column_map_arr, $flags, $dry_run);
            if (is_object($retval) and $retval->getResult() == true) {
                echo "Import successful!\n";
            } else {
                echo "ERROR: Failed importing data...\n";
                echo $retval;
                exit(1);
            }
        } else {
            //Get export mapping.
            $obj = new FairnessClientAPI('UserGenericData');
            $result = $obj->getUserGenericData(array('filter_data' => array('script' => 'import_wizard' . strtolower($object), 'name' => $export_map)));
            $retval = $result->getResult();
            if (is_array($retval) and isset($retval[0]['data'])) {
                $output = array();

                $i = 0;
                foreach ($retval[0]['data'] as $column_map) {
                    unset($column_map['row_1'], $column_map['id']); //Strip unneeded columns.

                    if ($i == 0) {
                        $columns = array(
                            'field' => 'import_column',
                            'map_column_name' => 'map_column_name',
                            'default_value' => 'default_value',
                            'parse_hint' => 'parse_hint',
                        );
                    }

                    $output[] = $column_map;
                    $i++;
                }

                if (isset($columns) and count($output) > 0) {
                    file_put_contents($column_map_file, Array2CSV($output, $columns, false));
                    echo "Column map written to: " . $column_map_file . "\n";
                }
            } else {
                echo "ERROR: No Column map matching that object/name...\n";
                exit(1);
            }
        }
    } else {
        echo "ERROR: Object argument not specified!\n";
        exit(1);
    }
}
echo "Done!\n";
