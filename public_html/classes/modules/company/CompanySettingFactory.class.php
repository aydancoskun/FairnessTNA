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
 * @package Core
 */
class CompanySettingFactory extends Factory
{
    protected $table = 'company_setting';
    protected $pk_sequence_name = 'company_setting_id_seq'; //PK Sequence name

    public static function getCompanySettingArrayByName($company_id, $name)
    {
        $cs_obj = self::getCompanySettingObjectByName($company_id, $name);
        if (is_object($cs_obj)) {
            return $cs_obj->getObjectAsArray();
        }

        return false;
    }

    public static function getCompanySettingObjectByName($company_id, $name)
    {
        $cslf = new CompanySettingListFactory();
        $cslf->getByCompanyIdAndName($company_id, $name);
        if ($cslf->getRecordCount() == 1) {
            $cs_obj = $cslf->getCurrent();
            return $cs_obj;
        }

        return false;
    }

    public static function getCompanySettingValueByName($company_id, $name)
    {
        $cs_obj = self::getCompanySettingObjectByName($company_id, $name);
        if (is_object($cs_obj)) {
            return $cs_obj->getValue();
        }

        return null;
    }

    public static function setCompanySetting($company_id, $name, $value, $type_id = 10)
    {
        $row = array(
            'company_id' => (int)$company_id,
            'name' => $name,
            'value' => $value,
            'type_id' => $type_id
        );
        $cslf = new CompanySettingListFactory();
        $cslf->getByCompanyIdAndName($company_id, $name);
        if ($cslf->getRecordCount() == 1) {
            $csf = $cslf->getCurrent();
            $row = array_merge($csf->getObjectAsArray(), $row);
        } else {
            $csf = new CompanySettingFactory();
        }

        Debug::Arr($row, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);
        $csf->setObjectFromArray($row);
        if ($csf->isValid()) {
            $csf->Save();
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

    public static function deleteCompanySetting($company_id, $name)
    {
        $cslf = new CompanySettingListFactory();
        $cslf->getByCompanyIdAndName($company_id, $name);
        if ($cslf->getRecordCount() == 1) {
            $csf = $cslf->getCurrent();
            $csf->setDeleted(true);
            if ($csf->isValid()) {
                $csf->Save();
            }
        }

        return false;
    }

    public function _getFactoryOptions($name, $parent = null)
    {
        $retval = null;
        switch ($name) {
            case 'type':
                $retval = array(
                    10 => TTi18n::gettext('Public'),
                    20 => TTi18n::gettext('Private'),
                );
                break;
        }

        return $retval;
    }

    public function _getVariableToFunctionMap($data)
    {
        $variable_function_map = array(
            'id' => 'ID',
            'company_id' => 'Company',
            'type_id' => 'Type',
            'type' => false,
            'name' => 'Name',
            'value' => 'Value',
            'deleted' => 'Deleted',
        );
        return $variable_function_map;
    }

    public function setCompany($id)
    {
        $id = trim($id);

        $clf = TTnew('CompanyListFactory');

        if ($id == 0
            or $this->Validator->isResultSetWithRows('company',
                $clf->getByID($id),
                TTi18n::gettext('Company is invalid')
            )
        ) {
            $this->data['company_id'] = $id;

            return true;
        }

        return false;
    }

    public function getType()
    {
        if (isset($this->data['type_id'])) {
            return (int)$this->data['type_id'];
        }

        return false;
    }

    public function setType($type)
    {
        $type = trim($type);

        if ($this->Validator->inArrayKey('type',
            $type,
            TTi18n::gettext('Incorrect Type'),
            $this->getOptions('type'))
        ) {
            $this->data['type_id'] = $type;

            return true;
        }

        return false;
    }

    public function setName($value)
    {
        $value = trim($value);
        if ($this->Validator->isLength('name',
                $value,
                TTi18n::gettext('Name is too short or too long'),
                1, 250)
            and
            $this->Validator->isTrue('name',
                $this->isUniqueName($value),
                TTi18n::gettext('Name already exists')
            )

        ) {
            $this->data['name'] = $value;

            return true;
        }

        return false;
    }

    public function isUniqueName($name)
    {
        Debug::Arr($this->getCompany(), 'Company: ', __FILE__, __LINE__, __METHOD__, 10);
        if ($this->getCompany() == false) {
            return false;
        }

        $name = trim($name);
        if ($name == '') {
            return false;
        }

        $ph = array(
            'company_id' => (int)$this->getCompany(),
            'name' => TTi18n::strtolower($name),
        );

        $query = 'select id from ' . $this->getTable() . '
					where company_id = ?
						AND lower(name) = ?
						AND deleted = 0';
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

    public function setValue($value)
    {
        $value = trim($value);
        if ($this->Validator->isLength('value',
            $value,
            TTi18n::gettext('Value is too short or too long'),
            1, 4096)
        ) {
            $this->data['value'] = $value;

            return true;
        }

        return false;
    }

    public function preSave()
    {
        return true;
    }

    public function postSave()
    {
        $this->removeCache($this->getCompany() . $this->getName());
        return true;
    }

    public function getName()
    {
        if (isset($this->data['name'])) {
            return $this->data['name'];
        }

        return false;
    }

    public function getObjectAsArray($include_columns = null)
    {
        $variable_function_map = $this->getVariableToFunctionMap();
        $data = array();
        if (is_array($variable_function_map)) {
            foreach ($variable_function_map as $variable => $function_stub) {
                if ($include_columns == null or (isset($include_columns[$variable]) and $include_columns[$variable] == true)) {
                    $function = 'get' . $function_stub;
                    switch ($variable) {
                        case 'type':
                            $function = 'get' . $variable;
                            if (method_exists($this, $function)) {
                                $data[$variable] = Option::getByKey($this->$function(), $this->getOptions($variable));
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

    public function addLog($log_action)
    {
        return TTLog::addEntry($this->getId(), $log_action, TTi18n::getText('Company Setting - Name') . ': ' . $this->getName() . ' ' . TTi18n::getText('Value') . ': ' . $this->getValue(), null, $this->getTable());
    }

    public function getValue()
    {
        if (isset($this->data['value'])) {
            return $this->data['value'];
        }

        return false;
    }
}
