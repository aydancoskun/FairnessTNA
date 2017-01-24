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
 * @package Core
 */
class PermissionUserFactory extends Factory
{
    public $user_obj = null;
        public $permission_control_obj = null; //PK Sequence name
    protected $table = 'permission_user';
protected $pk_sequence_name = 'permission_user_id_seq';

    public function getPermissionControlObject()
    {
        return $this->getGenericObject('PermissionControlListFactory',
            $this->getPermissionControl(), 'permission_control_obj');
    }

    public function getPermissionControl()
    {
        return (int)$this->data['permission_control_id'];
    }

    public function setPermissionControl($id)
    {
        $id = trim($id);

        $pclf = TTnew('PermissionControlListFactory');

        if ($id != 0
            or $this->Validator->isResultSetWithRows('permission_control',
                $pclf->getByID($id),
                TTi18n::gettext('Permission Group is
															invalid'))
        ) {
            $this->data['permission_control_id'] = $id;

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
            and $this->Validator->isTrue('user',
                $this->isUniqueUser($id),
                TTi18n::gettext('Selected Employee is already assigned to another Permission Group')
            )
        ) {
            $this->data['user_id'] = $id;

            return true;
        }

        return false;
    }

    public function isUniqueUser($id)
    {
        $pclf = TTnew('PermissionControlListFactory');

        $ph = array(
            'id' => $id
        );

        $query = 'select a.id from ' . $this->getTable() . ' as a, ' . $pclf->getTable() . ' as b where a.permission_control_id = b.id AND a.user_id = ? AND b.deleted = 0';
        $user_id = $this->db->GetOne($query, $ph);
        Debug::Arr($user_id, 'Unique User ID: ' . $user_id, __FILE__, __LINE__, __METHOD__, 10);

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
            return TTLog::addEntry($this->getPermissionControl(), $log_action, TTi18n::getText('Employee') . ': ' . $u_obj->getFullName(false, true), null, $this->getTable());
        }

        return false;
    }

    public function getUserObject()
    {
        return $this->getGenericObject('UserListFactory', $this->getUser(), 'user_obj');
    }

    public function getUser()
    {
        return (int)$this->data['user_id'];
    }
}
