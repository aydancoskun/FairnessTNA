<?php
/**********************************************************************************
 * This file is part of "FairnessTNA", a Payroll and Time Management program.
 * FairnessTNA is copyright 2013-2017 Aydan Coskun (aydan.ayfer.coskun@gmail.com)
 * others. For full attribution and copyrights details see the COPYRIGHT file.
 *
 * FairnessTNA is free software; you can redistribute it and/or modify it under the
 * terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation, either version 3 of the License, or (at you option )
 * any later version.
 *
 * FairnessTNA is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE.  See the GNU Affero General Public License for more
 * details.
 *
 * You should have received a copy of the GNU Affero General Public License along
 * with this program; if not, see http://www.gnu.org/licenses or write to the Free
 * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301 USA.
 *********************************************************************************/


/**
 * @package Modules\Users
 */
class UserReportDataFactory extends Factory
{
    protected $table = 'user_report_data';
    protected $pk_sequence_name = 'user_report_data_id_seq'; //PK Sequence name

    protected $user_obj = null;
    protected $obj_handler = null;

    public function _getFactoryOptions($name, $parent = null)
    {
        $retval = null;
        switch ($name) {
            case 'columns':
                $retval = array(
                    '-1010-name' => TTi18n::gettext('Name'),
                    '-1020-description' => TTi18n::gettext('Description'),
                    '-1030-script_name' => TTi18n::gettext('Report'),
                    '-1040-is_default' => TTi18n::gettext('Default'),
                    '-1050-is_scheduled' => TTi18n::gettext('Scheduled'),

                    '-2000-created_by' => TTi18n::gettext('Created By'),
                    '-2010-created_date' => TTi18n::gettext('Created Date'),
                    '-2020-updated_by' => TTi18n::gettext('Updated By'),
                    '-2030-updated_date' => TTi18n::gettext('Updated Date'),
                );
                break;
            case 'list_columns':
                $retval = Misc::arrayIntersectByKey($this->getOptions('default_display_columns'), Misc::trimSortPrefix($this->getOptions('columns')));
                break;
            case 'default_display_columns': //Columns that are displayed by default.
                $retval = array(
                    'name',
                    'script_name',
                    'description',
                    'is_default',
                );
                break;
            case 'unique_columns': //Columns that are unique, and disabled for mass editing.
                $retval = array(
                    'name',
                    'description',
                );
                break;
            case 'linked_columns': //Columns that are linked together, mainly for Mass Edit, if one changes, they all must.
                $retval = array();
                break;
        }

        return $retval;
    }

    public function _getVariableToFunctionMap($data)
    {
        $variable_function_map = array(
            'id' => 'ID',
            'company_id' => 'Company',
            'user_id' => 'User',
            'script' => 'Script',
            'script_name' => false,
            'name' => 'Name',
            'is_default' => 'Default',
            'is_scheduled' => false,
            'description' => 'Description',
            'data' => 'Data',
            'deleted' => 'Deleted',
        );
        return $variable_function_map;
    }

    public function getUserObject()
    {
        return $this->getGenericObject('UserListFactory', $this->getUser(), 'user_obj');
    }

    public function getUser()
    {
        if (isset($this->data['user_id'])) {
            return (int)$this->data['user_id'];
        }

        return false;
    }

    public function setCompany($id)
    {
        $id = trim($id);

        $clf = TTnew('CompanyListFactory');

        if ($this->Validator->isResultSetWithRows('company',
            $clf->getByID($id),
            TTi18n::gettext('Invalid Company')
        )
        ) {
            $this->data['company_id'] = $id;

            return true;
        }

        return false;
    }

    public function setUser($id)
    {
        $id = trim($id);

        $ulf = TTnew('UserListFactory');

        if ($this->Validator->isResultSetWithRows('user',
            $ulf->getByID($id),
            TTi18n::gettext('Invalid Employee')
        )
        ) {
            $this->data['user_id'] = $id;

            return true;
        }

        return false;
    }

    public function setScript($value)
    {
        //Strip out double slashes, as sometimes those occur and they cause the saved settings to not appear.
        $value = self::handleScriptName(trim($value));
        if ($this->Validator->isLength('script',
            $value,
            TTi18n::gettext('Invalid script'),
            1, 250)
        ) {
            $this->data['script'] = $value;

            return true;
        }

        return false;
    }

    public static function handleScriptName($script_name)
    {
        return str_replace('//', '/', $script_name);
    }

    public function setName($name)
    {
        $name = trim($name);
        if ($this->Validator->isLength('name',
                $name,
                TTi18n::gettext('Name is too short or too long'),
                1, 100)
            and
            $this->Validator->isTrue('name',
                $this->isUniqueName($name),
                TTi18n::gettext('Name already exists'))
        ) {
            $this->data['name'] = $name;

            return true;
        }

        return false;
    }

    public function isUniqueName($name)
    {
        if ($this->getCompany() == false) {
            return false;
        }

        //Allow no user_id to be set yet, as that would be company generic data.

        if ($this->getScript() == false) {
            return false;
        }

        $name = trim($name);
        if ($name == '') {
            return false;
        }

        $ph = array(
            'company_id' => (int)$this->getCompany(),
            'script' => $this->getScript(),
            'name' => TTi18n::strtolower($name),
        );

        $query = 'select id from ' . $this->getTable() . '
					where
						company_id = ?
						AND script = ?
						AND lower(name) = ? ';
        if ($this->getUser() != '') {
            $query .= ' AND user_id = ' . (int)$this->getUser();
        } else {
            $query .= ' AND user_id is NULL ';
        }

        $query .= ' AND deleted = 0';
        $name_id = $this->db->GetOne($query, $ph);
        Debug::Arr($name_id, 'Unique Name: ' . $name, __FILE__, __LINE__, __METHOD__, 10);

        if ($name_id === false) {
            return true;
        } else {
            if ($name_id == $this->getId()) {
                return true;
            }
        }

        return false;
    }

    public function getCompany()
    {
        if (isset($this->data['company_id'])) {
            return (int)$this->data['company_id'];
        }

        return false;
    }

    public function getScript()
    {
        if (isset($this->data['script'])) {
            return $this->data['script'];
        }

        return false;
    }

    public function setDefault($bool)
    {
        $this->data['is_default'] = $this->toBool($bool);

        return true;
    }

    public function getDescription()
    {
        if (isset($this->data['description'])) {
            return $this->data['description'];
        }

        return false;
    }

    public function setDescription($description)
    {
        $description = trim($description);

        if ($this->Validator->isLength('description',
            $description,
            TTi18n::gettext('Description is invalid'),
            0, 1024)
        ) {
            $this->data['description'] = $description;

            return true;
        }

        return false;
    }

    public function getData()
    {
        return unserialize($this->data['data']);
    }

    public function setData($value)
    {
        $value = serialize($value);

        $this->data['data'] = $value;

        return true;
    }

    public function Validate($ignore_warning = true)
    {
        if ($this->Validator->hasError('name') == false and $this->getName() == '') {
            $this->Validator->isTRUE('name',
                false,
                TTi18n::gettext('Name must be specified'));
        }

        return true;
    }

    public function getName()
    {
        if (isset($this->data['name'])) {
            return $this->data['name'];
        }

        return false;
    }

    public function preSave()
    {
        if ($this->getDefault() == true) {
            //Remove default flag from all other entries.
            $urdlf = TTnew('UserReportDataListFactory');
            if ($this->getUser() == false) {
                $urdlf->getByCompanyIdAndScriptAndDefault($this->getUser(), $this->getScript(), true);
            } else {
                $urdlf->getByUserIdAndScriptAndDefault($this->getUser(), $this->getScript(), true);
            }
            if ($urdlf->getRecordCount() > 0) {
                foreach ($urdlf as $urd_obj) {
                    Debug::Text('Removing Default Flag From: ' . $urd_obj->getId(), __FILE__, __LINE__, __METHOD__, 10);
                    $urd_obj->setDefault(false);
                    if ($urd_obj->isValid()) {
                        $urd_obj->Save();
                    }
                }
            }
        }

        return true;
    }

    public function getDefault()
    {
        if (isset($this->data['is_default'])) {
            return $this->fromBool($this->data['is_default']);
        }

        return false;
    }

    public function setObjectFromArray($data)
    {
        if (is_array($data)) {
            $variable_function_map = $this->getVariableToFunctionMap();
            foreach ($variable_function_map as $key => $function) {
                if (isset($data[$key])) {
                    $function = 'set' . $function;
                    switch ($key) {
                        default:
                            if (method_exists($this, $function)) {
                                $this->$function($data[$key]);
                            }
                            break;
                    }
                }
            }

            $this->setCreatedAndUpdatedColumns($data);

            return true;
        }

        return false;
    }

    //Support setting created_by, updated_by especially for importing data.

    public function getObjectAsArray($include_columns = null)
    {
        $data = array();
        $variable_function_map = $this->getVariableToFunctionMap();
        if (is_array($variable_function_map)) {
            foreach ($variable_function_map as $variable => $function_stub) {
                if ($include_columns == null or (isset($include_columns[$variable]) and $include_columns[$variable] == true)) {
                    $function = 'get' . $function_stub;
                    switch ($variable) {
                        case 'is_scheduled':
                            $data[$variable] = $this->getColumn('is_scheduled');
                            break;
                        case 'script_name':
                            $report_obj = $this->getObjectHandler();
                            if (is_object($report_obj)) {
                                $data[$variable] = $report_obj->title;
                            }
                            break;
                        default:
                            if (method_exists($this, $function)) {
                                $data[$variable] = $this->$function();
                            }
                            break;
                    }
                }
            }
            $this->getCreatedAndUpdatedColumns($data, $include_columns);
        }

        return $data;
    }

    public function getObjectHandler()
    {
        if (is_object($this->obj_handler)) {
            return $this->obj_handler;
        } else {
            $class = $this->getScript();
            if (class_exists($class, true)) {
                $this->obj_handler = new $class();
                return $this->obj_handler;
            }

            return false;
        }
    }

    public function addLog($log_action)
    {
        if ($this->getUser() == false and $this->getDefault() == true) {
            //Bypass logging on Company Default Save.
            return true;
        }

        return TTLog::addEntry($this->getId(), $log_action, TTi18n::getText('Saved Report Data'), null, $this->getTable());
    }
}
