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
 * @package Modules\KPI
 */
class KPIFactory extends Factory
{
    protected $table = 'kpi';
    protected $pk_sequence_name = 'kpi_id_seq'; //PK Sequence name
    protected $tmp_data = null;
    protected $company_obj = null;

    public function _getFactoryOptions($name, $parent = null)
    {
        $retval = null;
        switch ($name) {
            case 'status':
                $retval = array(10 => TTi18n::gettext('Enabled (Required)'), 15 => TTi18n::gettext('Enabled (Optional)'), 20 => TTi18n::gettext('Disabled'),);
                break;
            case 'type':
                $retval = array(10 => TTi18n::gettext('Scale Rating'), 20 => TTi18n::gettext('Yes/No'), 30 => TTi18n::gettext('Text'),);
                break;
            case 'columns':
                $retval = array('-1000-name' => TTi18n::gettext('Name'), //'-2040-group' => TTi18n::gettext('Group'),
                    '-1040-description' => TTi18n::gettext('Description'), '-1050-type' => TTi18n::getText('Type'), '-4050-minimum_rate' => TTi18n::gettext('Minimum Rating'), '-4060-maximum_rate' => TTi18n::gettext('Maximum Rating'), '-1010-status' => TTi18n::gettext('Status'), '-1300-tag' => TTi18n::gettext('Tags'), '-2000-created_by' => TTi18n::gettext('Created By'), '-2010-created_date' => TTi18n::gettext('Created Date'), '-2020-updated_by' => TTi18n::gettext('Updated By'), '-2030-updated_date' => TTi18n::gettext('Updated Date'),);
                break;
            case 'list_columns':
                $retval = Misc::arrayIntersectByKey($this->getOptions('default_display_columns'), Misc::trimSortPrefix($this->getOptions('columns')));
                break;
            case 'default_display_columns': //Columns that are displayed by default.
                $retval = array('name', //'group',
                    'description', 'type', 'minimum_rate', 'maximum_rate',);
                break;
            case 'unique_columns': //Columns that are unique, and disabled for mass editing.
                $retval = array('name',);
                break;
        }

        return $retval;
    }

    public function _getVariableToFunctionMap($data)
    {
        $variable_function_map = array('id' => 'ID', 'company_id' => 'Company', 'name' => 'Name', 'group_id' => 'Group', //'group' => FALSE,
            'type_id' => 'Type', 'type' => false, 'tag' => 'Tag', 'description' => 'Description', 'minimum_rate' => 'MinimumRate', 'maximum_rate' => 'MaximumRate', 'status_id' => 'Status', 'status' => false, 'deleted' => 'Deleted',);

        return $variable_function_map;
    }

    public function getCompanyObject()
    {
        return $this->getGenericObject('CompanyListFactory', $this->getCompany(), 'company_obj');
    }

    public function getCompany()
    {
        if (isset($this->data['company_id'])) {
            return (int)$this->data['company_id'];
        }

        return false;
    }

    public function setCompany($id)
    {
        $id = trim($id);
        Debug::Text('Company ID: ' . $id, __FILE__, __LINE__, __METHOD__, 10);
        $clf = TTnew('CompanyListFactory');
        if ($this->Validator->isResultSetWithRows('company', $clf->getByID($id), TTi18n::gettext('Company is invalid'))) {
            $this->data['company_id'] = $id;
            Debug::Text('Setting company_id data...	   ' . $this->data['company_id'], __FILE__, __LINE__, __METHOD__, 10);

            return true;
        }

        return false;
    }

    public function getStatus()
    {
        if (isset($this->data['status_id'])) {
            return (int)$this->data['status_id'];
        }

        return false;
    }

    public function setStatus($status)
    {
        $status = trim($status);

        if ($this->Validator->inArrayKey('status', $status, TTi18n::gettext('Incorrect Status'), $this->getOptions('status'))) {
            $this->data['status_id'] = $status;
            Debug::Text('Setting status_id data...	  ' . $this->data['status_id'], __FILE__, __LINE__, __METHOD__, 10);

            return true;
        }

        return false;
    }

    public function setType($type_id)
    {
        $type_id = trim($type_id);
        if ($this->Validator->inArrayKey('type_id', $type_id, TTi18n::gettext('Type is invalid'), $this->getOptions('type'))) {
            $this->data['type_id'] = $type_id;

            return true;
        }

        return false;
    }

    public function getName()
    {
        if (isset($this->data['name'])) {
            return $this->data['name'];
        }

        return false;
    }

    public function setName($name)
    {
        $name = trim($name);
        if ($this->Validator->isLength('name', $name, TTi18n::gettext('Name is too long, consider using description instead'), 3, 100)
            and
            $this->Validator->isTrue('name', $this->isUniqueName($name), TTi18n::gettext('Name is already taken'))
        ) {
            $this->data['name'] = $name;

            return true;
        }

        return false;
    }

    public function isUniqueName($name)
    {
        $name = trim($name);
        if ($name == '') {
            return false;
        }

        $ph = array(
            'company_id' => (int)$this->getCompany(),
            'name' => TTi18n::strtolower($name)
        );

        $query = 'select id from ' . $this->table . '
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

    public function getGroup()
    {
        return CompanyGenericMapListFactory::getArrayByCompanyIDAndObjectTypeIDAndObjectID($this->getCompany(), 2020, $this->getID());
    }

    public function setGroup($ids)
    {
        Debug::text('Setting Groups IDs : ', __FILE__, __LINE__, __METHOD__, 10);
        Debug::Arr($ids, 'Setting Group data... ', __FILE__, __LINE__, __METHOD__, 10);

        return CompanyGenericMapFactory::setMapIDs($this->getCompany(), 2020, $this->getID(), $ids);
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
        if ($this->Validator->isLength('description', $description, TTi18n::gettext('Description is invalid'), 0, 255)) {
            $this->data['description'] = $description;
            Debug::Text('Setting description data...	' . $this->data['description'], __FILE__, __LINE__, __METHOD__, 10);

            return true;
        }

        return false;
    }

    public function setMinimumRate($value)
    {
        $value = trim($value);
        $value = $this->Validator->stripNonFloat($value);
        if (($this->getType() == 10) and ($this->Validator->isLength('minimum_rate', $value, TTi18n::gettext('Invalid  Minimum Rating'), 1)
                and
                ($this->Validator->isNumeric('minimum_rate', $value, TTi18n::gettext('Minimum Rating must only be digits'))
                    and
                    $this->Validator->isLengthAfterDecimal('minimum_rate', $value, TTi18n::gettext('Invalid Minimum Rating'), 0, 2)))
        ) {
            $this->data['minimum_rate'] = $value;
            Debug::Text('Setting minimum_rate data...	 ' . $this->data['minimum_rate'], __FILE__, __LINE__, __METHOD__, 10);

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

    public function setMaximumRate($value)
    {
        $value = trim($value);
        $value = $this->Validator->stripNonFloat($value);
        if (($this->getType() == 10) and ($this->Validator->isLength('maximum_rate', $value, TTi18n::gettext('Invalid Maximum Rating'), 1)
                and
                ($this->Validator->isNumeric('maximum_rate', $value, TTi18n::gettext('Maximum Rating must only be digits'))
                    and
                    $this->Validator->isLengthAfterDecimal('maximum_rate', $value, TTi18n::gettext('Invalid Maximum Rating'), 0, 2)))
        ) {
            $this->data['maximum_rate'] = $value;
            Debug::Text('Setting maximum_rate data...' . $this->data['maximum_rate'], __FILE__, __LINE__, __METHOD__, 10);

            return true;
        }

        return false;
    }

    public function setTag($tags)
    {
        $tags = trim($tags);
        //Save the tags in temporary memory to be committed in postSave()
        $this->tmp_data['tags'] = $tags;

        return true;
    }

    public function Validate($ignore_warning = true)
    {
        if ($this->getType() == 10 and $this->getMinimumRate() != '' and $this->getMaximumRate() != '') {
            if ($this->getMinimumRate() >= $this->getMaximumRate()) {
                $this->Validator->isTrue('minimum_rate', false, TTi18n::gettext('Minimum Rating should be lesser than Maximum Rating'));
            }
        }
        if ($this->getDeleted() == true) {
            $urlf = TTnew('UserReviewListFactory');
            $urlf->getByKpiId($this->getId());
            if ($urlf->getRecordCount() > 0) {
                $this->Validator->isTRUE('in_use', false, TTi18n::gettext('KPI is in use'));
            }
        }

        return true;
    }

    public function getMinimumRate()
    {
        if (isset($this->data['minimum_rate'])) {
            return Misc::removeTrailingZeros((float)$this->data['minimum_rate'], 2);
        }

        return false;
    }

    public function getMaximumRate()
    {
        if (isset($this->data['maximum_rate'])) {
            return Misc::removeTrailingZeros((float)$this->data['maximum_rate'], 2);
        }

        return false;
    }

    public function preSave()
    {
        return true;
    }

    public function postSave()
    {
        $this->removeCache($this->getId());
        if ($this->getDeleted() == false) {
            Debug::text('Setting Tags...', __FILE__, __LINE__, __METHOD__, 10);
            CompanyGenericTagMapFactory::setTags($this->getCompany(), 310, $this->getID(), $this->getTag());
        }

        return true;
    }

    public function getTag()
    {

        //Check to see if any temporary data is set for the tags, if not, make a call to the database instead.
        //postSave() needs to get the tmp_data.
        if (isset($this->tmp_data['tags'])) {
            return $this->tmp_data['tags'];
        } elseif ($this->getCompany() > 0 and $this->getID() > 0) {
            return CompanyGenericTagMapListFactory::getStringByCompanyIDAndObjectTypeIDAndObjectID($this->getCompany(), 310, $this->getID());
        }

        return false;
    }

    //Support setting created_by, updated_by especially for importing data.
    //Make sure data is set based on the getVariableToFunctionMap order.

    public function setObjectFromArray($data)
    {
        Debug::Arr($data, 'setObjectFromArray...', __FILE__, __LINE__, __METHOD__, 10);
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

    public function getObjectAsArray($include_columns = null, $permission_children_ids = false)
    {
        $data = array();

        $variable_function_map = $this->getVariableToFunctionMap();
        if (is_array($variable_function_map)) {
            foreach ($variable_function_map as $variable => $function_stub) {
                if ($include_columns == null or (isset($include_columns[$variable]) and $include_columns[$variable] == true)) {
                    $function = 'get' . $function_stub;
                    switch ($variable) {
                        case 'type':
                        case 'status':
                            $function = 'get' . $variable;
                            if (method_exists($this, $function)) {
                                $data[$variable] = Option::getByKey($this->$function(), $this->getOptions($variable));
                            }
                            break;
                        /*case 'group':
                            if ( $this->getColumn( 'map_id' ) == -1 ) {
                                $data[$variable] = 'All';
                            } else {
                                $data[$variable] = $this->getColumn( $variable );
                            }
                            break;*/
                        default:
                            if (method_exists($this, $function)) {
                                $data[$variable] = $this->$function();
                            }
                            break;
                    }
                }
            }
            $this->getPermissionColumns($data, $this->getCreatedBy(), false, $permission_children_ids, $include_columns);
            $this->getCreatedAndUpdatedColumns($data, $include_columns);
        }

        return $data;
    }

    public function addLog($log_action)
    {
        return TTLog::addEntry($this->getId(), $log_action, TTi18n::getText('KPI'), null, $this->getTable(), $this);
    }
}
