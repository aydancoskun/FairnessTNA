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
 * @package Modules\Hierarchy
 */
class HierarchyControlFactory extends Factory
{
    protected $table = 'hierarchy_control';
    protected $pk_sequence_name = 'hierarchy_control_id_seq'; //PK Sequence name

    public function _getFactoryOptions($name, $parent = null)
    {
        $retval = null;
        switch ($name) {
            case 'object_type':
                $hotlf = TTnew('HierarchyObjectTypeListFactory');
                $retval = $hotlf->getOptions('object_type');
                break;
            case 'short_object_type':
                $hotlf = TTnew('HierarchyObjectTypeListFactory');
                $retval = $hotlf->getOptions('short_object_type');
                break;
            case 'columns':
                $retval = array(
                    '-1010-name' => TTi18n::gettext('Name'),
                    '-1020-description' => TTi18n::gettext('Description'),
                    '-1030-superiors' => TTi18n::gettext('Superiors'),
                    '-1030-subordinates' => TTi18n::gettext('Subordinates'),
                    '-1050-object_type_display' => TTi18n::gettext('Objects'),

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
                    'description',
                    'superiors',
                    'subordinates',
                    'object_type_display'
                );
                break;
            case 'unique_columns': //Columns that are unique, and disabled for mass editing.
                $retval = array();
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
            'name' => 'Name',
            'description' => 'Description',
            'superiors' => 'TotalSuperiors',
            'subordinates' => 'TotalSubordinates',
            'object_type' => 'ObjectType',
            'object_type_display' => false,
            'user' => 'User',
            'deleted' => 'Deleted',
        );
        return $variable_function_map;
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

    public function setName($name)
    {
        $name = trim($name);

        if ($this->Validator->isLength('name',
                $name,
                TTi18n::gettext('Name is invalid'),
                2, 250)
            and $this->Validator->isTrue('name',
                $this->isUniqueName($name),
                TTi18n::gettext('Name is already in use')
            )
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
            'name' => TTi18n::strtolower($name),
        );

        $query = 'select id from ' . $this->getTable() . ' where company_id = ? AND lower(name) = ? AND deleted = 0';
        $hierarchy_control_id = $this->db->GetOne($query, $ph);
        Debug::Arr($hierarchy_control_id, 'Unique Hierarchy Control ID: ' . $hierarchy_control_id, __FILE__, __LINE__, __METHOD__, 10);

        if ($hierarchy_control_id === false) {
            return true;
        } else {
            if ($hierarchy_control_id == $this->getId()) {
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

        if ($description == ''
            or $this->Validator->isLength('description',
                $description,
                TTi18n::gettext('Description is invalid'),
                1, 250)
        ) {
            $this->data['description'] = $description;

            return true;
        }

        return false;
    }

    public function setObjectType($ids)
    {
        if (is_array($ids) and count($ids) > 0) {
            $tmp_ids = array();
            Debug::Arr($ids, 'IDs: ', __FILE__, __LINE__, __METHOD__, 10);

            if (!$this->isNew()) {
                //If needed, delete mappings first.
                $lf_a = TTnew('HierarchyObjectTypeListFactory');
                $lf_a->getByHierarchyControlId($this->getId());
                Debug::text('Existing Object Type Rows: ' . $lf_a->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);

                foreach ($lf_a as $obj) {
                    //$id = $obj->getId();
                    $id = $obj->getObjectType(); //Need to use object_types rather than row IDs.
                    Debug::text('Hierarchy Object Type ID: ' . $obj->getId() . ' ID: ' . $id, __FILE__, __LINE__, __METHOD__, 10);

                    //Delete users that are not selected.
                    if (!in_array($id, $ids)) {
                        Debug::text('Deleting: Object Type: ' . $id . ' ID: ' . $obj->getID(), __FILE__, __LINE__, __METHOD__, 10);
                        $obj->Delete();
                    } else {
                        //Save ID's that need to be updated.
                        Debug::text('NOT Deleting: Object Type: ' . $id . ' ID: ' . $obj->getID(), __FILE__, __LINE__, __METHOD__, 10);
                        $tmp_ids[] = $id;
                    }
                }
                unset($id, $obj);
            }

            foreach ($ids as $id) {
                if (isset($ids) and !in_array($id, $tmp_ids)) {
                    $f = TTnew('HierarchyObjectTypeFactory');
                    $f->setHierarchyControl($this->getId());
                    $f->setObjectType($id);

                    if ($this->Validator->isTrue('object_type',
                        $f->Validator->isValid(),
                        TTi18n::gettext('Object type is already assigned to another hierarchy'))
                    ) {
                        $f->save();
                    }
                }
            }

            return true;
        }

        $this->Validator->isTrue('object_type',
            false,
            TTi18n::gettext('At least one object must be selected'));

        return false;
    }

    public function setUser($ids)
    {
        if (!is_array($ids)) {
            $ids = array($ids);
        }

        Debug::text('Setting User IDs : ', __FILE__, __LINE__, __METHOD__, 10);
        if (is_array($ids)) {
            $tmp_ids = array();

            if (!$this->isNew()) {
                //If needed, delete mappings first.
                $hulf = TTnew('HierarchyUserListFactory');
                $hulf->getByHierarchyControlID($this->getId());

                foreach ($hulf as $obj) {
                    $id = $obj->getUser();
                    Debug::text('HierarchyControl ID: ' . $obj->getHierarchyControl() . ' ID: ' . $id, __FILE__, __LINE__, __METHOD__, 10);

                    //Delete users that are not selected.
                    if (!in_array($id, $ids)) {
                        Debug::text('Deleting: ' . $id, __FILE__, __LINE__, __METHOD__, 10);
                        $obj->Delete();
                    } else {
                        //Save ID's that need to be updated.
                        Debug::text('NOT Deleting : ' . $id, __FILE__, __LINE__, __METHOD__, 10);
                        $tmp_ids[] = $id;
                    }
                }
                unset($id, $obj);
            }

            //Insert new mappings.
            $ulf = TTnew('UserListFactory');

            foreach ($ids as $id) {
                if (isset($ids) and !in_array($id, $tmp_ids)) {
                    $huf = TTnew('HierarchyUserFactory');
                    $huf->setHierarchyControl($this->getId());
                    $huf->setUser($id);

                    $ulf->getById($id);
                    if ($ulf->getRecordCount() > 0) {
                        $obj = $ulf->getCurrent();

                        if ($this->Validator->isTrue('user',
                            $huf->Validator->isValid(),
                            TTi18n::gettext('Selected subordinate is invalid or already assigned to another hierarchy with the same objects') . ' (' . $obj->getFullName() . ')')
                        ) {
                            $huf->save();
                        }
                    }
                }
            }

            return true;
        }

        Debug::text('No User IDs to set.', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function getTotalSubordinates()
    {
        $hulf = TTnew('HierarchyUserListFactory');
        $hulf->getByHierarchyControlID($this->getId());
        return $hulf->getRecordCount();
    }

    public function getTotalSuperiors()
    {
        $hllf = TTnew('HierarchyLevelListFactory');
        $hllf->getByHierarchyControlId($this->getID());
        return $hllf->getRecordCount();
    }

    public function Validate($ignore_warning = true)
    {
        if ($this->getName() == false and $this->Validator->hasError('name') == false) {
            $this->Validator->isTrue('name',
                false,
                TTi18n::gettext('Name is not specified'));
        }

        //When the user changes just the hierarchy objects, we need to loop through ALL users and confirm no conflicting hierarchies exist.
        //Only do this for existing hierarchies and ones that are already valid up to this point.
        if (!$this->isNew() and $this->Validator->isValid() == true) {
            $user_ids = $this->getUser();
            if (is_array($user_ids)) {
                $huf = TTNew('HierarchyUserFactory');
                $huf->setHierarchyControl($this->getID());

                foreach ($user_ids as $user_id) {
                    if ($huf->isUniqueUser($user_id) == false) {
                        $ulf = TTnew('UserListFactory');
                        $ulf->getById($user_id);
                        if ($ulf->getRecordCount() > 0) {
                            $obj = $ulf->getCurrent();
                            $this->Validator->isTrue('user',
                                $huf->isUniqueUser($user_id, $this->getID()),
                                TTi18n::gettext('Selected subordinate is invalid or already assigned to another hierarchy with the same objects') . ' (' . $obj->getFullName() . ')');
                        } else {
                            TTi18n::gettext('Selected subordinate is invalid or already assigned to another hierarchy with the same object. User ID: %1', array($user_id));
                        }
                    }
                }
            }
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

    public function getUser()
    {
        $hulf = TTnew('HierarchyUserListFactory');
        $hulf->getByHierarchyControlID($this->getId());
        foreach ($hulf as $obj) {
            $list[] = $obj->getUser();
        }

        if (isset($list)) {
            return $list;
        }

        return false;
    }

    public function postSave()
    {
        return true;
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

    public function getObjectAsArray($include_columns = null)
    {
        $variable_function_map = $this->getVariableToFunctionMap();
        if (is_array($variable_function_map)) {
            foreach ($variable_function_map as $variable => $function_stub) {
                if ($include_columns == null or (isset($include_columns[$variable]) and $include_columns[$variable] == true)) {
                    $function = 'get' . $function_stub;
                    switch ($variable) {
                        //case 'superiors':
                        //case 'subordinates':
                        //	$data[$variable] = $this->getColumn($variable);
                        //	break;
                        case 'object_type_display':
                            $data[$variable] = $this->getObjectTypeDisplay();
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

    public function getObjectTypeDisplay()
    {
        $object_type_ids = $this->getObjectType();
        $object_types = $this->getOptions('short_object_type');

        $retval = array();
        if (is_array($object_type_ids)) {
            foreach ($object_type_ids as $object_type_id) {
                $retval[] = Option::getByKey($object_type_id, $object_types);
            }
            sort($retval); //Maintain consistent order.

            return implode(',', $retval);
        }

        return null;
    }

    public function getObjectType()
    {
        $valid_object_type_ids = $this->getOptions('object_type');

        $hotlf = TTnew('HierarchyObjectTypeListFactory');
        $hotlf->getByHierarchyControlId($this->getId());
        if ($hotlf->getRecordCount() > 0) {
            foreach ($hotlf as $object_type) {
                if (isset($valid_object_type_ids[$object_type->getObjectType()])) {
                    $object_type_list[] = $object_type->getObjectType();
                }
            }

            if (isset($object_type_list)) {
                return $object_type_list;
            }
        }

        return false;
    }

    public function addLog($log_action)
    {
        return TTLog::addEntry($this->getId(), $log_action, TTi18n::getText('Hierarchy'), null, $this->getTable(), $this);
    }
}
