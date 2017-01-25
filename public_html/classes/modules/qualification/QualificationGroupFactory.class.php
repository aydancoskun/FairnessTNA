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
 * @package Modules\Qualification
 */
class QualificationGroupFactory extends Factory
{
    protected $table = 'qualification_group';
    protected $pk_sequence_name = 'qualification_group_id_seq'; //PK Sequence name

    protected $fasttree_obj = null;

    public function _getFactoryOptions($name, $parent = null)
    {
        $retval = null;
        switch ($name) {
            case 'columns':
                $retval = array(
                    '-1030-name' => TTi18n::gettext('Name'),

                    '-2000-created_by' => TTi18n::gettext('Created By'),
                    '-2010-created_date' => TTi18n::gettext('Created Date'),
                    '-2020-updated_by' => TTi18n::gettext('Updated By'),
                    '-2030-updated_date' => TTi18n::gettext('Updated Date'),
                );
                break;
            case 'unique_columns': //Columns that are displayed by default.
                $retval = array(
                    'name',
                );
                break;
            case 'list_columns':
                $retval = Misc::arrayIntersectByKey($this->getOptions('default_display_columns'), Misc::trimSortPrefix($this->getOptions('columns')));
                break;
            case 'default_display_columns': //Columns that are displayed by default.
                $retval = array(
                    'name',
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
            'parent_id' => 'Parent',
            'name' => 'Name',
            'deleted' => 'Deleted',
        );
        return $variable_function_map;
    }

    public function setCompany($id)
    {
        $id = trim($id);

        $clf = TTnew('CompanyListFactory');

        if ($id == 0
            or $this->Validator->isResultSetWithRows('company_id',
                $clf->getByID($id),
                TTi18n::gettext('Company is invalid')
            )
        ) {
            $this->data['company_id'] = $id;

            return true;
        }

        return false;
    }

    public function getPreviousParent()
    {
        if (isset($this->tmp_data['previous_parent_id'])) {
            return $this->tmp_data['previous_parent_id'];
        }

        return false;
    }

    public function setPreviousParent($id)
    {
        $this->tmp_data['previous_parent_id'] = $id;

        return true;
    }

    //Use this for completly editing a row in the tree
    //Basically "old_id".

    public function setParent($id)
    {
        $this->tmp_data['parent_id'] = (int)$id;

        return true;
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

        if ($this->Validator->isLength('name',
                $name,
                TTi18n::gettext('Name is too short or too long'),
                2,
                100)
            and
            $this->Validator->isTrue('name',
                $this->isUniqueName($name),
                TTi18n::gettext('Group already exists'))

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

    public function getCompany()
    {
        if (isset($this->data['company_id'])) {
            return (int)$this->data['company_id'];
        }
        return false;
    }

    public function Validate($ignore_warning = true)
    {
        if ($this->isNew() == false
            and $this->getId() == $this->getParent()
        ) {
            $this->Validator->isTrue('parent',
                false,
                TTi18n::gettext('Cannot re-parent group to itself')
            );
        } else {
            if ($this->isNew() == false) {
                $this->getFastTreeObject()->setTree($this->getCompany());
                //$children_ids = array_keys( $this->getFastTreeObject()->getAllChildren( $this->getID(), 'RECURSE' ) );

                $children_ids = $this->getFastTreeObject()->getAllChildren($this->getID(), 'RECURSE');
                if (is_array($children_ids)) {
                    $children_ids = array_keys($children_ids);
                }

                if (is_array($children_ids) and in_array($this->getParent(), $children_ids) == true) {
                    Debug::Text(' Objects cant be re-parented to their own children...', __FILE__, __LINE__, __METHOD__, 10);
                    $this->Validator->isTrue('parent',
                        false,
                        TTi18n::gettext('Unable to change parent to a child of itself')
                    );
                }
            }
        }

        return true;
    }

    public function getParent()
    {
        if (isset($this->tmp_data['parent_id'])) {
            return $this->tmp_data['parent_id'];
        }

        return false;
    }

    public function getFastTreeObject()
    {
        if (is_object($this->fasttree_obj)) {
            return $this->fasttree_obj;
        } else {
            global $fast_tree_qualification_group_options;
            $this->fasttree_obj = new FastTree($fast_tree_qualification_group_options);

            return $this->fasttree_obj;
        }
    }

    public function preSave()
    {
        if ($this->isNew()) {
            Debug::Text(' Setting Insert Tree TRUE ', __FILE__, __LINE__, __METHOD__, 10);
            $this->insert_tree = true;
        } else {
            Debug::Text(' Setting Insert Tree FALSE ', __FILE__, __LINE__, __METHOD__, 10);
            $this->insert_tree = false;
        }

        return true;
    }

    //Must be postSave because we need the ID of the object.
    public function postSave()
    {
        $this->StartTransaction();

        $this->getFastTreeObject()->setTree($this->getCompany());

        if ($this->getDeleted() == true) {
            //FIXME: Get parent of this object, and re-parent all groups to it.
            $parent_id = $this->getFastTreeObject()->getParentId($this->getId());

            //Get items by group id.
            $qlf = TTnew('QualificationListFactory');
            $qlf->getByCompanyIdAndGroupId($this->getCompany(), $this->getId());
            if ($qlf->getRecordCount() > 0) {
                foreach ($qlf as $obj) {
                    Debug::Text(' Re-Grouping Item: ' . $obj->getId(), __FILE__, __LINE__, __METHOD__, 10);
                    $obj->setGroup($parent_id);
                    $obj->Save();
                }
            }

            $this->getFastTreeObject()->delete($this->getId());

            //Delete this group from station/job criteria
            //$sugf = TTnew( 'StationQualificationGroupFactory' );

            //$query = 'delete from '. $sugf->getTable() .' where group_id = '. (int)$this->getId();
            //$this->db->Execute($query);

            //Job employee criteria
            $cgmlf = TTnew('CompanyGenericMapListFactory');
            $cgmlf->getByCompanyIDAndObjectTypeAndMapID($this->getCompany(), 1090, $this->getID());
            if ($cgmlf->getRecordCount() > 0) {
                foreach ($cgmlf as $cgm_obj) {
                    Debug::text('Deleteing from Company Generic Map: ' . $cgm_obj->getID(), __FILE__, __LINE__, __METHOD__, 10);
                    $cgm_obj->Delete();
                }
            }

            $this->CommitTransaction();

            return true;
        } else {
            $retval = true;
            //if ( $this->getId() === FALSE ) {

            if ($this->insert_tree === true) {
                Debug::Text(' Adding Node ', __FILE__, __LINE__, __METHOD__, 10);

                //echo "Current ID: ".	$this->getID() ."<br>\n";
                //echo "Parent ID: ".  $this->getParent() ."<br>\n";

                //Add node to tree
                if ($this->getFastTreeObject()->add($this->getID(), $this->getParent()) === false) {
                    Debug::Text(' Failed adding Node ', __FILE__, __LINE__, __METHOD__, 10);

                    $this->Validator->isTrue('name',
                        false,
                        TTi18n::gettext('Name is already in use')
                    );
                    $retval = false;
                }
            } else {
                Debug::Text(' Editing Node ', __FILE__, __LINE__, __METHOD__, 10);

                //Edit node.
                $retval = $this->getFastTreeObject()->move($this->getID(), $this->getParent());
            }

            if ($retval === true) {
                $this->CommitTransaction();
            } else {
                $this->FailTransaction();
            }

            return $retval;
        }
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

    public function getObjectAsArray($include_columns = null, $permission_children_ids = false)
    {
        $data = array();
        $variable_function_map = $this->getVariableToFunctionMap();
        if (is_array($variable_function_map)) {
            foreach ($variable_function_map as $variable => $function_stub) {
                if ($include_columns == null or (isset($include_columns[$variable]) and $include_columns[$variable] == true)) {
                    $function = 'get' . $function_stub;
                    switch ($variable) {
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
        return TTLog::addEntry($this->getId(), $log_action, TTi18n::getText('Qualification Group'), null, $this->getTable(), $this);
    }
}
