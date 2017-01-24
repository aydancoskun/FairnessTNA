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


/**
 * @package Core
 */
/*
 - Example config array:

 $log_rotate_config[] = array(
                            'directory' => '/var/log/fairness',
                            'recurse' => TRUE,
                            'file' => '*',
                            'frequency' => 'DAILY',
                            'history' =>  5 );
*/

class LogRotate
{
    private $config_arr = array();

    public function __construct($config_arr = null)
    {
        $this->config_arr = $config_arr;
        return true;
    }

    public function addConfig($arr)
    {
        $this->config_arr[] = $arr;
        return true;
    }

    public function Rotate()
    {
        //Loop through config entries
        if (is_array($this->config_arr) and isset($this->config_arr[0])) {
            foreach ($this->config_arr as $rotate_config) {
                //Debug::Arr($rotate_config, ' Log Rotate Config: ', __FILE__, __LINE__, __METHOD__, 10);
                if (isset($rotate_config['directory']) and $rotate_config['directory'] != '') {
                    Debug::Text(' Rotating Logs: Dir: ' . $rotate_config['directory'] . ' File: ' . $rotate_config['file'], __FILE__, __LINE__, __METHOD__, 10);

                    $files = $this->getFileList($rotate_config['directory'], $rotate_config['file'], $rotate_config['recurse']);
                    //Debug::Arr( $files, 'Matching files: ', __FILE__, __LINE__, __METHOD__, 10);

                    if (is_array($files) and count($files) > 0) {
                        $this->_rotate($files, $rotate_config);
                    } else {
                        Debug::Text(' No files to rotate...', __FILE__, __LINE__, __METHOD__, 10);
                    }
                }
            }
        } else {
            Debug::Text(' No config loaded!', __FILE__, __LINE__, __METHOD__, 10);
        }

        return true;
    }

    public function getFileList($start_dir, $regex_filter = null, $recurse = false)
    {
        return Misc::getFileList($start_dir, $regex_filter, $recurse);
    }

    //Checks to see if the file has a numeric extension signifying that it is not a primary file and has already been rotated.

    public function _rotate($files, $rotate_config)
    {
        if (is_array($files)) {
            foreach ($files as $filename) {
                if ($this->isFileRotatable($filename) == true) {
                    Debug::Text(' File IS a primary log file: ' . $filename, __FILE__, __LINE__, __METHOD__, 10);

                    if ($this->isFileReadyToRotate($filename, $rotate_config['frequency']) == true) {
                        Debug::Text(' File is old enough to be rotated: ' . $filename, __FILE__, __LINE__, __METHOD__, 10);

                        $this->handleHistoryFiles($this->getRotatedHistoryFiles($files, $filename), $rotate_config['history']);

                        //Rename primary log file
                        $new_file = $filename . '.' . $this->padExtension(1, $rotate_config['history']);
                        if (file_exists($filename) and !file_exists($new_file)) {
                            Debug::Text(' Renaming primary log file: ' . $filename . ' To: ' . $new_file, __FILE__, __LINE__, __METHOD__, 10);
                            if (@rename($filename, $new_file) == false) {
                                Debug::Text('   ERROR: Rename failed, possibly due to permissions?', __FILE__, __LINE__, __METHOD__, 10);
                            }
                        } else {
                            Debug::Text(' NOT Renaming primary log file: ' . $filename, __FILE__, __LINE__, __METHOD__, 10);
                        }
                        unset($new_file);
                    } else {
                        Debug::Text(' File does not need to be rotated yet: ' . $filename, __FILE__, __LINE__, __METHOD__, 10);
                    }
                } else {
                    Debug::Text(' File is not a primary log file: ' . $filename, __FILE__, __LINE__, __METHOD__, 10);
                }
            }
        }

        return true;
    }

    public function isFileRotatable($file)
    {
        $extension = pathinfo($file, PATHINFO_EXTENSION);
        //Debug::Text(' File:  '. $file .' Extension: '. $extension, __FILE__, __LINE__, __METHOD__, 10);

        //Only rotate if the file size is greater then 0 bytes.
        if (!is_numeric($extension) and file_exists($file) and filesize($file) > 0) {
            return true;
        }

        return false;
    }

    public function isFileReadyToRotate($file, $frequency = 'daily')
    {
        $retval = false;

        if (file_exists($file)) {
            //Linux doesnt store when the file was created, so we have to base this on when we last ran.
            //$file_created_time = filectime( $file );
            //Debug::Text(' File: '. $file .' was created: '. date('r', $file_created_time), __FILE__, __LINE__, __METHOD__, 10);

            switch (strtolower($frequency)) {
                case 'always':
                    $retval = true;
                    break;
                case 'daily':
                    $retval = true;
                    break;
            }
        }

        return $retval;
    }

    public function handleHistoryFiles($files, $history = 0)
    {
        if (is_array($files)) {
            rsort($files);
            foreach ($files as $filename) {
                $path_info = pathinfo($filename);

                $new_extension = $this->padExtension(((int)$path_info['extension'] + 1), $history);
                $new_file = $path_info['dirname'] . DIRECTORY_SEPARATOR . $path_info['filename'] . '.' . $new_extension;

                if ($new_extension > $history and is_writable($filename)) {
                    Debug::Text(' Found last file in history, delete rather then rename: ' . $filename, __FILE__, __LINE__, __METHOD__, 10);
                    unlink($filename);
                } else {
                    if (file_exists($filename) and !file_exists($new_file)) {
                        Debug::Text(' Renaming: ' . $filename . ' To: ' . $new_file, __FILE__, __LINE__, __METHOD__, 10);

                        rename($filename, $new_file);
                    } else {
                        Debug::Text(' Unable to rename file, file does not exist or new name does exist or we do not have permission: ' . $new_file, __FILE__, __LINE__, __METHOD__, 10);
                    }
                }
            }

            return true;
        }

        return false;
    }

    public function padExtension($extension, $history)
    {
        if (strlen($history) < 2) {
            $pad_length = 2;
        } else {
            $pad_length = strlen($history);
        }
        return str_pad($extension, $pad_length, '0', STR_PAD_LEFT);
    }

    public function getRotatedHistoryFiles($files, $primary_file)
    {
        $retarr = array();
        if (is_array($files)) {
            foreach ($files as $filename) {
                $pattern = '/' . str_replace(array('/', '\\'), array('\\', '\\\\'), $primary_file) . '\.[0-9]{1,2}/i';
                //Debug::Text(' Pattern: '. $pattern, __FILE__, __LINE__, __METHOD__, 10);
                if (preg_match($pattern, $filename) == 1) {
                    $retarr[] = $filename;
                }
            }
        }

        if (empty($retarr) == false) {
            return $retarr;
        }

        return false;
    }
}
