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
 * @package Modules\Install
 */
class InstallSchema extends Install
{
    protected $schema_version = null;
    protected $obj = null;

    public function __construct($database_type, $version, $db_conn, $is_upgrade = false)
    {
        Debug::text('Database Type: ' . $database_type . ' Version: ' . $version, __FILE__, __LINE__, __METHOD__, 10);
        $this->database_type = $database_type;
        $this->schema_version = $version;

        if ($database_type == '') {
            return false;
        }

        if ($version == '') {
            return false;
        }

        $schema_class_file_name = Environment::getBasePath() . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . 'install' . DIRECTORY_SEPARATOR . 'InstallSchema_' . $version . '.class.php';
        $schema_sql_file_name = $this->getSchemaSQLFilename($version);
        if (file_exists($schema_class_file_name)
            and file_exists($schema_sql_file_name)
        ) {
            include_once($schema_class_file_name);

            $class_name = 'InstallSchema_' . $version;

            $this->obj = new $class_name($this); //Pass current Install class object to the schema class, so we can call common functions.
            $this->obj->setDatabaseConnection($db_conn);
            $this->obj->setIsUpgrade($is_upgrade);
            $this->obj->setVersion($version);
            $this->obj->setSchemaSQLFilename($this->getSchemaSQLFilename());

            return true;
        } else {
            Debug::text('Schema Install Class File DOES NOT Exists - File Name: ' . $schema_class_file_name . ' Schema SQL File: ' . $schema_sql_file_name, __FILE__, __LINE__, __METHOD__, 10);
        }

        return false;
    }

    public function getSchemaSQLFilename()
    {
        return $this->getSQLFileDirectory() . $this->schema_version . '.sql';
    }

    public function getSQLFileDirectory()
    {
        return Environment::getBasePath() . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . 'install' . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . $this->database_type . DIRECTORY_SEPARATOR;
    }

    //load Schema file data

    public function getSchemaSQLFileData()
    {
    }

    public function __call($function_name, $args = array())
    {
        if ($this->getObject() !== false) {
            //Debug::text('Calling Sub-Class Function: '. $function_name, __FILE__, __LINE__, __METHOD__, 10);
            if (is_callable(array($this->getObject(), $function_name))) {
                $return = call_user_func_array(array($this->getObject(), $function_name), $args);

                return $return;
            }
        }

        Debug::text('Sub-Class Function Call FAILED!:' . $function_name, __FILE__, __LINE__, __METHOD__, 10);

        return false;
    }

    private function getObject()
    {
        if (is_object($this->obj)) {
            return $this->obj;
        }

        return false;
    }
}
