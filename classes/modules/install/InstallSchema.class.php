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
 * @package Modules\Install
 */
class InstallSchema extends Install {

	protected $schema_version = NULL;
	protected $obj = NULL;

	/**
	 * InstallSchema constructor.
	 * @param $database_type
	 * @param $version
	 * @param $db_conn
	 * @param bool $is_upgrade
	 */
	function __construct( $database_type, $version, $db_conn, $is_upgrade = FALSE ) {
		global $config_vars;
		$this->config_vars = $config_vars; //Variable is in the install_obj too, but we need to propegate it here so cleanCacheDirectory() can be called.

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
		$schema_sql_file_name = $this->getSchemaSQLFilename();
		if ( file_exists($schema_class_file_name)
				AND file_exists($schema_sql_file_name ) ) {

			include_once( $schema_class_file_name );

			$class_name = 'InstallSchema_'. $version;

			$this->obj = new $class_name( $this ); //Pass current Install class object to the schema class, so we can call common functions.
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

	/**
	 * @return string
	 */
	function getSQLFileDirectory() {
		return Environment::getBasePath() . DIRECTORY_SEPARATOR .'classes'. DIRECTORY_SEPARATOR .'modules'. DIRECTORY_SEPARATOR .'install'. DIRECTORY_SEPARATOR .'sql'. DIRECTORY_SEPARATOR . $this->database_type . DIRECTORY_SEPARATOR;
	}

	/**
	 * @return string
	 */
	function getSchemaSQLFilename() {
		return $this->getSQLFileDirectory() . $this->schema_version .'.sql';
	}

	//load Schema file data
	function getSchemaSQLFileData() {

	}

	/**
	 * @return bool|null
	 */
	private function getObject() {
		if ( is_object($this->obj) ) {
			return $this->obj;
		}

		return FALSE;
	}

	/**
	 * @param $function_name
	 * @param array $args
	 * @return bool|mixed
	 */
	function __call( $function_name, $args = array() ) {
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
