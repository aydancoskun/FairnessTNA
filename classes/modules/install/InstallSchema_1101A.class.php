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

/**
 * THIS IS THE SCHEMA VERSION THAT SWITCHES TO UUIDs.
 * @package Modules\Install
 */
class InstallSchema_1101A extends InstallSchema_Base {

	/**
	 * @return bool
	 */
	function preInstall() {
		Debug::text( 'preInstall: ' . $this->getVersion(), __FILE__, __LINE__, __METHOD__, 9 );

		//No need to manually generate the UUID SEED as its done and written to the config file automatically as part of InstallSchema_Base->replaceSQLVariables()

		return TRUE;
	}

	/**
	 * @return bool
	 */
	function postInstall() {
		Debug::text( 'postInstall: ' . $this->getVersion(), __FILE__, __LINE__, __METHOD__, 9 );

		Debug::text( 'Starting convert company logo...', __FILE__, __LINE__, __METHOD__, 10 );
		$this->convertCompanyLogos();
		Debug::text( 'Finished convert company logo.', __FILE__, __LINE__, __METHOD__, 10 );

		Debug::text( 'Starting convert user photos...', __FILE__, __LINE__, __METHOD__, 10 );
		$this->convertUserPhoto();
		Debug::text( 'Finished convert user photos.', __FILE__, __LINE__, __METHOD__, 10 );

		return TRUE;
	}

	/**
	 * @return bool
	 */
	function convertCompanyLogos() {
		$root_path = realpath( Environment::getStorageBasePath() .'company_logo'. DIRECTORY_SEPARATOR );
		if ( $root_path === FALSE ) {
			Debug::text( 'ERROR: Directory does not exist: '. Environment::getStorageBasePath() .'company_logo'. DIRECTORY_SEPARATOR, __FILE__, __LINE__, __METHOD__, 10 );
			return FALSE;
		}

		try {
			$files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root_path, FilesystemIterator::SKIP_DOTS ), RecursiveIteratorIterator::CHILD_FIRST );
			foreach ( $files as $file_obj ) {
				if ( $file_obj->isDir() == FALSE ) {
					$file = $file_obj->getRealPath();
					$file_chunks = explode( DIRECTORY_SEPARATOR, $file );
					$total_file_chunks = count( $file_chunks );
					if ($total_file_chunks > 1) {
						$company_file_chunk = ( $total_file_chunks - 2 );

						//only convert the path if it's still an int
						if ( TTUUID::isUUID( $file_chunks[ $company_file_chunk ] ) == FALSE ) {
							$file_chunks[ $company_file_chunk ] = TTUUID::convertIntToUUID( $file_chunks[ $company_file_chunk ] );
							$new_path = implode( $file_chunks, DIRECTORY_SEPARATOR );
							$this->renameFile( $file, $new_path );
						}
					}
				}
			}
		} catch( Exception $e ) {
			Debug::Text('Failed opening/reading file or directory: '. $e->getMessage(), __FILE__, __LINE__, __METHOD__, 10);
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * @return bool
	 */
	function convertUserPhoto(){
		$root_path = realpath( Environment::getStorageBasePath() .'user_photo'. DIRECTORY_SEPARATOR );
		if ( $root_path === FALSE ) {
			Debug::text( 'ERROR: Directory does not exist: '. Environment::getStorageBasePath() .'user_photo'. DIRECTORY_SEPARATOR, __FILE__, __LINE__, __METHOD__, 10 );
			return FALSE;
		}

		$changed = FALSE;

		try {
			$files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root_path, FilesystemIterator::SKIP_DOTS ), RecursiveIteratorIterator::CHILD_FIRST );
			foreach ( $files as $file_obj ) {
				if ( $file_obj->isDir() == FALSE ) {
					$file = $file_obj->getRealPath();
					$file_chunks = explode( DIRECTORY_SEPARATOR, $file );
					$total_file_chunks = count( $file_chunks );

					if ($total_file_chunks > 1) {
						$company_file_chunk = ( $total_file_chunks - 2 );
						$filename_chunk = count( $file_chunks ) - 1;
						$filename_chunks = explode( '.', $file_chunks[ $filename_chunk ] );
						$user_id = $filename_chunks[0];
						$extension = $filename_chunks[1];

						//only convert the path if it's still an int
						if ( TTUUID::isUUID( $file_chunks[ $company_file_chunk ] ) == FALSE ) {
							$file_chunks[ $company_file_chunk ] = TTUUID::convertIntToUUID( $file_chunks[ $company_file_chunk ] );
							$changed = TRUE;
						}

						if ( TTUUID::isUUID( $user_id ) == FALSE ) {
							$file_chunks[ $filename_chunk ] = TTUUID::convertIntToUUID( $user_id ) . '.' . $extension;
							$changed = TRUE;
						}

						if ( $changed == TRUE ) {
							$new_path = implode( $file_chunks, DIRECTORY_SEPARATOR );
							$this->renameFile( $file, $new_path );
						}
					}
				}
			}
		} catch( Exception $e ) {
			Debug::Text('Failed opening/reading file or directory: '. $e->getMessage(), __FILE__, __LINE__, __METHOD__, 10);
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * @param $before
	 * @param $after
	 * @param int $counter
	 * @return bool
	 */
	function renameFile( $before, $after, $counter = 0 ) {
		Debug::text( $counter .'. Renaming file: '.$before .' to: '.$after, __FILE__, __LINE__, __METHOD__, 10 );
		@mkdir( dirname( $after ), 0755, TRUE );
		return Misc::rename($before, $after);
	}
}