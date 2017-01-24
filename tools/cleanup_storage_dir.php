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

require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'global.inc.php');
require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'CLI.inc.php');

if ($argc < 1 or (isset($argv[1]) and in_array($argv[1], array('--help', '-help', '-h', '-?')))) {
    $help_output = "Usage: cleanup_storage_dir.php [options] [company_id]\n";
    $help_output .= "    -n				Dry-run\n";
    echo $help_output;
} else {
    //Handle command line arguments
    $last_arg = (count($argv) - 1);

    if (in_array('-n', $argv)) {
        $dry_run = true;
        echo "Using DryRun!\n";
    } else {
        $dry_run = false;
    }

    if (isset($argv[$last_arg]) and is_numeric($argv[$last_arg])) {
        $company_id = $argv[$last_arg];
    }

    //Force flush after each output line.
    ob_implicit_flush(true);
    ob_end_flush();

    //Top level storage dir.
    $storage_dir = Environment::getStorageBasePath();

    //
    //Loop through all storage directories finding orphaned files.
    //

    //Punch Images
    $punch_image_dir = $storage_dir . DIRECTORY_SEPARATOR . 'punch_images';
    echo "Punch Images: " . $punch_image_dir . "\n";

    $plf = new PunchListFactory();

    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($punch_image_dir), RecursiveIteratorIterator::SELF_FIRST);
    $i = 0;
    foreach ($files as $file) {
        if ($file->isFile() == true) {
            $punch_id = str_replace(pathinfo($file->getFileName(), PATHINFO_EXTENSION), '', $file->getFilename());
            $plf->getById($punch_id);
            if ($plf->getRecordCount() == 0 or ($plf->getRecordCount() == 1 and (bool)$plf->getCurrent()->getHasImage() == false)) {
                echo 'Path+File: ' . $file->getPathName() . ' File: ' . $file->getFilename() . ' Punch ID: ' . $punch_id . ' File mTime: ' . TTDate::getDate('DATE+TIME', filectime($file->getPathName())) . "\n";
                Debug::Text('Path+File: ' . $file->getPathName() . ' File: ' . $file->getFilename() . ' Punch ID: ' . $punch_id . ' File mTime: ' . TTDate::getDate('DATE+TIME', filectime($file->getPathName())), __FILE__, __LINE__, __METHOD__, 10);

                echo '  Punch does not exist, or does not have image, deleting orphaned image file: ' . (int)$plf->getRecordCount() . "\n";
                Debug::Text('  Punch does not exist, or does not have image, deleting orphaned image file: ' . (int)$plf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);

                if ($dry_run == false) {
                    @unlink($file->getPathName());
                    $i++;
                }
            }
        }
    }
    echo "Deleted Punch Images: " . $i . "\n";
}
echo "Done...\n";
Debug::WriteToLog();
Debug::Display();
