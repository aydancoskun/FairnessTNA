<?php
/*********************************************************************************
 * This file is part of "Fairness", a Payroll and Time Management program.
 * Fairness is Copyright 2013 Aydan Coskun (aydan.ayfer.coskun@gmail.com)
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
 * $Revision: 8371 $
 * $Id: InstallSchema.class.php 8371 2012-11-22 21:18:57Z ipso $
 * $Date: 2012-11-22 13:18:57 -0800 (Thu, 22 Nov 2012) $
 */

/**
 * @package Modules\Install
 */
class InstallSchema extends Install {

	protected $schema_version = NULL;
	protected $obj = NULL;

	function __construct( $database_type, $version, $db_conn, $is_upgrade = FALSE ) {
		Debug::text('Database Type: '. $database_type .' Version: '. $version, __FILE__, __LINE__, __METHOD__, 10);
		$this->database_type = $database_type;
		$this->schema_version = $version;

		if ( $database_type == '' ) {
			return FALSE;
		}

		if ( $version == '' ) {
			return FALSE;
		}

		$schema_class_file_name = Environment::getBasePath() . DIRECTORY_SEPARATOR .'classes'. DIRECTORY_SEPARATOR .'modules'. DIRECTORY_SEPARATOR .'install'. DIRECTORY_SEPARATOR .'InstallSchema_'. $version .'.class.php';
		$schema_sql_file_name = $this->getSchemaSQLFilename( $version );
		if ( file_exists($schema_class_file_name)
				AND file_exists($schema_sql_file_name ) ) {

			include_once( $schema_class_file_name );

			$class_name = 'InstallSchema_'. $version;

			$this->obj = new $class_name;
			$this->obj->setDatabaseConnection( $db_conn );
			$this->obj->setIsUpgrade( $is_upgrade );
			$this->obj->setVersion( $version );
			$this->obj->setSchemaSQLFilename( $this->getSchemaSQLFilename() );

			return TRUE;
		} else {
			Debug::text('Schema Install Class File DOES NOT Exists - File Name: '. $schema_class_file_name .' Schema SQL File: '. $schema_sql_file_name, __FILE__, __LINE__, __METHOD__, 10);
		}

		return FALSE;
	}

	function getSQLFileDirectory() {
		return Environment::getBasePath() . DIRECTORY_SEPARATOR .'classes'. DIRECTORY_SEPARATOR .'modules'. DIRECTORY_SEPARATOR .'install'. DIRECTORY_SEPARATOR .'sql'. DIRECTORY_SEPARATOR . $this->database_type . DIRECTORY_SEPARATOR;
	}

	function getSchemaSQLFilename() {
		return $this->getSQLFileDirectory() . $this->schema_version .'.sql';
	}

	//load Schema file data
	function getSchemaSQLFileData() {

	}

	private function getObject() {
		if ( is_object($this->obj) ) {
			return $this->obj;
		}

		return FALSE;
	}

	function __call($function_name, $args = array() ) {
		if ( $this->getObject() !== FALSE ) {
			//Debug::text('Calling Sub-Class Function: '. $function_name, __FILE__, __LINE__, __METHOD__, 10);
			if ( is_callable( array($this->getObject(), $function_name) ) ) {
				$return = call_user_func_array(array($this->getObject(), $function_name), $args);

				return $return;
			}
		}

		Debug::text('Sub-Class Function Call FAILED!:'. $function_name, __FILE__, __LINE__, __METHOD__, 10);

		return FALSE;
	}

}
?>
