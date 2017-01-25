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
class HierarchyUserFactory extends Factory
{
    public $hierarchy_control_obj = null;
        public $user_obj = null; //PK Sequence name
    protected $table = 'hierarchy_user';
protected $pk_sequence_name = 'hierarchy_user_id_seq';

    public function getHierarchyControlObject()
    {
        if (is_object($this->hierarchy_control_obj)) {
            return $this->hierarchy_control_obj;
        } else {
            $hclf = TTnew('HierarchyControlListFactory');
            $this->hierarchy_control_obj = $hclf->getById($this->getHierarchyControl())->getCurrent();

            return $this->hierarchy_control_obj;
        }
    }

    public function getHierarchyControl()
    {
        if (isset($this->data['hierarchy_control_id'])) {
            return (int)$this->data['hierarchy_control_id'];
        }

        return false;
    }

    public function setHierarchyControl($id)
    {
        $id = trim($id);

        $hclf = TTnew('HierarchyControlListFactory');

        //This is a sub-class, need to support setting HierachyControlID before its created.
        if ($id != 0
            or $this->Validator->isResultSetWithRows('hierarchy_control_id',
                $hclf->getByID($id),
                TTi18n::gettext('Invalid Hierarchy Control')
            )
        ) {
            $this->data['hierarchy_control_id'] = $id;

            return true;
        }

        return false;
    }

    public function setUser($id)
    {
        $id = trim($id);

        $ulf = TTnew('UserListFactory');

        if ($id != 0
            and $this->Validator->isResultSetWithRows('user',
                $ulf->getByID($id),
                TTi18n::gettext('Selected Employee is invalid')
            )
            /*
                            //Allow superiors to be assigned as subordinates in the same hierarchy to make it easier to administer hierarchies
                            //that have superiors sharing responsibility.
                            //For example Super1 and Super2 look after 10 subordinates as well as each other. This would require 3 hierarchies normally,
                            //but if we allow Super1 and Super2 to be subordinates in the same hierarchy, it can be done with a single hierarchy.
                            //The key with this though is to have Permission->getPermissionChildren() *not* return the current user, even if they are a subordinates,
                            //as that could cause a conflict with view_own and view_child permissions (as a child would imply view_own)
                            AND
                            $this->Validator->isNotResultSetWithRows(	'user',
                                                                        $hllf->getByHierarchyControlIdAndUserId( $this->getHierarchyControl(), $id ),
                                                                        TTi18n::gettext('Selected Employee is assigned as both a superior and subordinate')
                                                                        )
            */
            and $this->Validator->isTrue('user',
                $this->isUniqueUser($id),
                TTi18n::gettext('Selected Employee is already assigned to another hierarchy')
            )
        ) {
            $this->data['user_id'] = $id;

            return true;
        }

        return false;
    }

    public function isUniqueUser($id, $exclude_id = 0)
    {
        $hcf = TTnew('HierarchyControlFactory');
        $hotf = TTnew('HierarchyObjectTypeFactory');

        $ph = array(
            'hierarchy_control_id' => $this->getHierarchyControl(),
            'id' => $id,
            'exclude_id' => (int)$exclude_id,
        );

        //$query = 'select a.id from '. $this->getTable() .' as a, '. $pglf->getTable() .' as b where a.hierarchy_control_id = b.id AND a.user_id = ? AND b.deleted=0';
        $query = '
					select *
					from ' . $hotf->getTable() . ' as a
					LEFT JOIN ' . $this->getTable() . ' as b ON a.hierarchy_control_id = b.hierarchy_control_id
					LEFT JOIN ' . $hcf->getTable() . ' as c ON a.hierarchy_control_id = c.id
					WHERE a.object_type_id in (
							select object_type_id
							from hierarchy_object_type
							where hierarchy_control_id = ? )
					AND b.user_id = ?
					AND a.hierarchy_control_id != ?
					AND c.deleted = 0
				';
        //Debug::Arr($ph, 'Query: '. $query, __FILE__, __LINE__, __METHOD__, 10);
        $user_id = $this->db->GetOne($query, $ph);

        if ($user_id === false) {
            return true;
        }

        return false;
    }

    public function getDeleted()
    {
        return false;
    }

    public function setDeleted($bool)
    {
        return false;
    }

    //This table doesn't have any of these columns, so overload the functions.

    public function getCreatedDate()
    {
        return false;
    }

    public function setCreatedDate($epoch = null)
    {
        return false;
    }

    public function getCreatedBy()
    {
        return false;
    }

    public function setCreatedBy($id = null)
    {
        return false;
    }

    public function getUpdatedDate()
    {
        return false;
    }

    public function setUpdatedDate($epoch = null)
    {
        return false;
    }

    public function getUpdatedBy()
    {
        return false;
    }

    public function setUpdatedBy($id = null)
    {
        return false;
    }

    public function getDeletedDate()
    {
        return false;
    }

    public function setDeletedDate($epoch = null)
    {
        return false;
    }

    public function getDeletedBy()
    {
        return false;
    }

    public function setDeletedBy($id = null)
    {
        return false;
    }

    public function addLog($log_action)
    {
        $u_obj = $this->getUserObject();
        if (is_object($u_obj)) {
            return TTLog::addEntry($this->getHierarchyControl(), $log_action, TTi18n::getText('Suborindate') . ': ' . $u_obj->getFullName(false, true), null, $this->getTable());
        }

        return false;
    }

    public function getUserObject()
    {
        if (is_object($this->user_obj)) {
            return $this->user_obj;
        } else {
            $ulf = TTnew('UserListFactory');
            $ulf->getById($this->getUser());
            if ($ulf->getRecordCount() == 1) {
                $this->user_obj = $ulf->getCurrent();
                return $this->user_obj;
            }

            return false;
        }
    }

    public function getUser()
    {
        if (isset($this->data['user_id'])) {
            return (int)$this->data['user_id'];
        }
    }
}
