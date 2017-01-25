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
class UserGroupFactory extends Factory
{
    protected $table = 'user_group';
    protected $pk_sequence_name = 'user_group_id_seq'; //PK Sequence name

    protected $fasttree_obj = null;

    protected $tmp_data = array();

    public function _getFactoryOptions($name, $parent = null)
    {
        $retval = null;
        switch ($name) {
            case 'columns':
                $retval = array(
                    '-1000-name' => TTi18n::gettext('Name'),

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
                    'created_by',
                    'created_date',
                    'updated_by',
                    'updated_date',
                );
                break;
            case 'unique_columns': //Columns that are unique, and disabled for mass editing.
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

        Debug::Text('Company ID: ' . $id, __FILE__, __LINE__, __METHOD__, 10);
        $clf = TTnew('CompanyListFactory');

        if ($this->Validator->isResultSetWithRows('company',
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

    public function getName()
    {
        return $this->data['name'];
    }

    public function setName($name)
    {
        $name = trim($name);
        if ($this->Validator->isLength('name',
            $name,
            TTi18n::gettext('Name is invalid'),
            2, 50)
        ) {
            $this->data['name'] = $name;

            return true;
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
            global $fast_tree_user_group_options;
            $this->fasttree_obj = new FastTree($fast_tree_user_group_options);

            return $this->fasttree_obj;
        }
    }

    public function getCompany()
    {
        if (isset($this->data['company_id'])) {
            return (int)$this->data['company_id'];
        }

        return false;
    }

    public function preSave()
    {
        if ($this->getParent() == '') {
            $this->setParent(0);
        }

        if ($this->isNew()) {
            Debug::Text(' Setting Insert Tree TRUE ', __FILE__, __LINE__, __METHOD__, 10);
            $this->insert_tree = true;
        } else {
            Debug::Text(' Setting Insert Tree FALSE ', __FILE__, __LINE__, __METHOD__, 10);
            $this->insert_tree = false;
        }

        return true;
    }

    public function setParent($id)
    {
        $this->tmp_data['parent_id'] = (int)$id;

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
            $ulf = TTnew('UserListFactory');
            $ulf->getByCompanyIdAndGroupId($this->getCompany(), $this->getId());
            if ($ulf->getRecordCount() > 0) {
                foreach ($ulf as $obj) {
                    Debug::Text(' Re-Grouping Item: ' . $obj->getId(), __FILE__, __LINE__, __METHOD__, 10);
                    $obj->setGroup($parent_id);
                    $obj->Save();
                }
            }

            $this->getFastTreeObject()->delete($this->getId());

            //Delete this group from station/job criteria
            $sugf = TTnew('StationUserGroupFactory');

            $query = 'delete from ' . $sugf->getTable() . ' where group_id = ' . (int)$this->getId();
            $this->db->Execute($query);

            //Job employee criteria
            $cgmlf = TTnew('CompanyGenericMapListFactory');
            $cgmlf->getByCompanyIDAndObjectTypeAndMapID($this->getCompany(), 1030, $this->getID());
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

    //Support setting created_by, updated_by especially for importing data.
    //Make sure data is set based on the getVariableToFunctionMap order.
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
            $this->getCreatedAndUpdatedColumns($data, $include_columns);
        }

        return $data;
    }

    public function addLog($log_action)
    {
        return TTLog::addEntry($this->getId(), $log_action, TTi18n::getText('Employee Group'), null, $this->getTable(), $this);
    }
}
