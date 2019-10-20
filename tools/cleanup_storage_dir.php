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

if ( $argc < 1 OR ( isset($argv[1]) AND in_array($argv[1], array('--help', '-help', '-h', '-?') ) ) ) {
	$help_output = "Usage: cleanup_storage_dir.php [options] [company_id]\n";
	$help_output .= "    -n				Dry-run\n";
	echo $help_output;
} else {
	//Handle command line arguments
	$last_arg = ( count($argv) - 1 );

	if ( in_array('-n', $argv) ) {
		$dry_run = TRUE;
		echo "Using DryRun!\n";
	} else {
		$dry_run = FALSE;
	}

	if ( isset($argv[$last_arg]) AND is_numeric($argv[$last_arg]) ) {
		$company_id = $argv[$last_arg];
	}

	//Force flush after each output line.
	ob_implicit_flush( TRUE );
	ob_end_flush();

	//Top level storage dir.
	$storage_dir = Environment::getStorageBasePath();

	//
	//Loop through all storage directories finding orphaned files.
	//

	//Punch Images
	$punch_image_dir = $storage_dir . DIRECTORY_SEPARATOR . 'punch_images';
	echo "Punch Images: ". $punch_image_dir ."\n";

	$plf = new PunchListFactory();

	$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator( $punch_image_dir ), RecursiveIteratorIterator::SELF_FIRST);
	$i = 0;
	foreach ( $files as $file ) {
		if ( $file->isFile() == TRUE ) {
			$punch_id = str_replace( pathinfo( $file->getFileName(), PATHINFO_EXTENSION ), '', $file->getFilename() );
			$plf->getById( $punch_id );
			if ( $plf->getRecordCount() == 0 OR ( $plf->getRecordCount() == 1 AND (bool)$plf->getCurrent()->getHasImage() == FALSE ) ) {
				echo 'Path+File: ' . $file->getPathName() . ' File: ' . $file->getFilename() . ' Punch ID: ' . $punch_id . ' File mTime: '. TTDate::getDate('DATE+TIME', filectime( $file->getPathName() ) ) . "\n";
				Debug::Text('Path+File: ' . $file->getPathName() . ' File: ' . $file->getFilename() . ' Punch ID: ' . $punch_id . ' File mTime: '. TTDate::getDate('DATE+TIME', filectime( $file->getPathName() ) ), __FILE__, __LINE__, __METHOD__, 10);

				echo '  Punch does not exist, or does not have image, deleting orphaned image file: '. (int)$plf->getRecordCount() ."\n";
				Debug::Text('  Punch does not exist, or does not have image, deleting orphaned image file: '. (int)$plf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);

				if ( $dry_run == FALSE ) {
					@unlink( $file->getPathName() );
					$i++;
				}
			}
		}
	}
	echo "Deleted Punch Images: ". $i ."\n";
}
echo "Done...\n";
Debug::WriteToLog();
Debug::Display();
?>
