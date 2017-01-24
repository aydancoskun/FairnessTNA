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
class InstallSchema_Base
{
    protected $schema_sql_file_name = null;
    protected $schema_failure_state_file = null;
    protected $version = null;
    protected $db = null;
    protected $is_upgrade = false;
    protected $install_obj = false;

    public function __construct($install_obj = false)
    {
        if (is_object($install_obj)) {
            $this->install_obj = $install_obj;
        }

        return true;
    }

    public function setDatabaseConnection($db)
    {
        $this->db = $db;
    }

    public function getIsUpgrade()
    {
        return $this->is_upgrade;
    }

    public function setIsUpgrade($val)
    {
        $this->is_upgrade = (bool)$val;
    }

    public function checkTableExists($table_name)
    {
        Debug::text('Table Name: ' . $table_name, __FILE__, __LINE__, __METHOD__, 9);
        $db_conn = $this->getDatabaseConnection();

        if ($db_conn == false) {
            return false;
        }

        $table_arr = $db_conn->MetaTables();

        if (in_array($table_name, $table_arr)) {
            Debug::text('Exists - Table Name: ' . $table_name, __FILE__, __LINE__, __METHOD__, 9);
            return true;
        }

        Debug::text('Does not Exist - Table Name: ' . $table_name, __FILE__, __LINE__, __METHOD__, 9);
        return false;
    }

    public function getDatabaseConnection()
    {
        return $this->db;
    }

    public function InstallSchema()
    {
        $this->getDatabaseConnection()->StartTrans();

        Debug::text('Installing Schema Version: ' . $this->getVersion(), __FILE__, __LINE__, __METHOD__, 9);
        if ($this->preInstall() == true) {
            if ($this->_InstallSchema() == true) {
                if ($this->postInstall() == true) {
                    $retval = $this->_postPostInstall();
                    if ($retval == true) {
                        Debug::text('Clearing schema failure state file: ' . $this->schema_failure_state_file, __FILE__, __LINE__, __METHOD__, 9);
                        @unlink($this->schema_failure_state_file); //Clear state when schema is applied successfully, including postInstall.

                        $this->getDatabaseConnection()->CompleteTrans();

                        return $retval;
                    }
                }
            }
        }

        $this->getDatabaseConnection()->FailTrans();

        return false;
    }

    public function getVersion()
    {
        return $this->version;
    }

    public function setVersion($value)
    {
        $this->version = $value;
    }

    private function _InstallSchema()
    {
        //Run the actual SQL queries here

        $sql = $this->removeSchemaSQLFileComments($this->getSchemaSQLFileData());
        if ($sql == false) {
            return false;
        }

        global $config_vars;
        if (isset($config_vars['cache']['dir'])) {
            $this->schema_failure_state_file = $config_vars['cache']['dir'] . DIRECTORY_SEPARATOR . 'fn_schema_failure.state';
        }

        //Save a state file if any SQL query fails, so we can continue on from where it left off.
        //Only do this for MySQL though, as PostgreSQL has DDL transactions.

        $schema_failure_state = array();
        if (file_exists($this->schema_failure_state_file) and strncmp($this->getDatabaseConnection()->databaseType, 'mysql', 5) == 0) {
            $schema_failure_state = unserialize(file_get_contents($this->schema_failure_state_file));
            Debug::Arr($schema_failure_state, 'Schema Failure State: ' . $this->schema_failure_state_file, __FILE__, __LINE__, __METHOD__, 9);
        } else {
            Debug::text('No previous Schema failure state file: ' . $this->schema_failure_state_file, __FILE__, __LINE__, __METHOD__, 9);
        }

        if ($sql !== false and strlen($sql) > 0) {
            Debug::text('Schema SQL has data, executing commands!', __FILE__, __LINE__, __METHOD__, 9);

            $i = 0;

            //Split into individual SQL queries, as MySQL apparently doesn't like more then one query
            //in a single query() call.
            $split_sql = explode(';', $sql);
            if (is_array($split_sql)) {
                foreach ($split_sql as $sql_line) {
                    if (isset($schema_failure_state[$this->getVersion()])) {
                        if ($i < ($schema_failure_state[$this->getVersion()])) {
                            Debug::text('Skipping already committed SQL command on line: ' . $i . ' of: ' . $this->getVersion(), __FILE__, __LINE__, __METHOD__, 9);
                            $i++;
                            continue;
                        }
                    }

                    //Debug::text('SQL Line: '. trim($sql_line), __FILE__, __LINE__, __METHOD__, 9);
                    if (trim($sql_line) != '' and substr(trim($sql_line), 0, 2) != '--') {
                        try {
                            $this->getDatabaseConnection()->Execute($sql_line);
                        } catch (Exception $e) {
                            $schema_failure_state = array($this->getVersion() => $i);
                            Debug::text('SQL Command failed on line: ' . $i . ' of: ' . $this->getVersion(), __FILE__, __LINE__, __METHOD__, 9);
                            @file_put_contents($this->schema_failure_state_file, serialize($schema_failure_state));
                            throw new DBError($e);
                            return false;
                        }
                    }

                    $i++;
                }
            }

            //Save state that all schema changes succeeded so they aren't run again even if postInstall fails.
            $schema_failure_state = array($this->getVersion() => $i);
            Debug::text('Schema upgrade succeeded, last line: ' . $i . ' of: ' . $this->getVersion(), __FILE__, __LINE__, __METHOD__, 9);
            @file_put_contents($this->schema_failure_state_file, serialize($schema_failure_state));
        } else {
            Debug::text('Schema SQL does not have data, not executing commands, continuing...', __FILE__, __LINE__, __METHOD__, 9);
        }

        //Clear state file only once postInstall() has completed.

        return true;
    }

    //Copied from Install class.

    public function removeSchemaSQLFileComments($sql)
    {
        $retval = '';

        $split_sql = explode("\n", $sql);
        if (is_array($split_sql)) {
            foreach ($split_sql as $sql_line) {
                if (substr(trim($sql_line), 0, 2) != '--') {
                    $retval .= $sql_line . "\n"; //Make sure the newlines are put back in the proper place, otherwise it can other SQL parse errors.
                } else {
                    Debug::text('Skipping SQL Comment: ' . $sql_line, __FILE__, __LINE__, __METHOD__, 9);
                }
            }
        }

        return $retval;
    }

    //load Schema file data

    public function getSchemaSQLFileData()
    {
        //Read SQL data into memory
        if (is_readable($this->getSchemaSQLFilename())) {
            Debug::text('Schema SQL File is readable: ' . $this->getSchemaSQLFilename(), __FILE__, __LINE__, __METHOD__, 9);
            $contents = file_get_contents($this->getSchemaSQLFilename());

            Debug::Arr($contents, 'SQL File Data: ', __FILE__, __LINE__, __METHOD__, 9);
            return $contents;
        }

        Debug::text('Schema SQL File is NOT readable, or is empty!', __FILE__, __LINE__, __METHOD__, 9);

        return false;
    }

    public function getSchemaSQLFilename()
    {
        return $this->schema_sql_file_name;
    }

    public function setSchemaSQLFilename($file_name)
    {
        $this->schema_sql_file_name = $file_name;
    }

    private function _postPostInstall()
    {
        Debug::text('Modify Schema version in system settings table!', __FILE__, __LINE__, __METHOD__, 9);
        //Modify schema version in system_settings table.

        $sslf = TTnew('SystemSettingListFactory');
        $sslf->getByName('schema_version_group_' . $this->getSchemaGroup());
        if ($sslf->getRecordCount() == 1) {
            $obj = $sslf->getCurrent();
        } else {
            $obj = TTnew('SystemSettingListFactory');
        }

        $obj->setName('schema_version_group_' . $this->getSchemaGroup());
        $obj->setValue($this->getVersion());
        if ($obj->isValid()) {
            Debug::text('Setting Schema Version to: ' . $this->getVersion() . ' Group: ' . $this->getSchemaGroup(), __FILE__, __LINE__, __METHOD__, 9);
            $obj->Save();

            return true;
        }

        return false;
    }

    public function getSchemaGroup()
    {
        $schema_group = substr($this->getVersion(), -1, 1);
        Debug::text('Schema: ' . $this->getVersion() . ' Group: ' . $schema_group, __FILE__, __LINE__, __METHOD__, 9);

        return strtoupper($schema_group);
    }
}
