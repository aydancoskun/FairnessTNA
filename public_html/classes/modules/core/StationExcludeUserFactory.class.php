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
class StationExcludeUserFactory extends Factory
{
    public $user_obj = null;
        protected $table = 'station_exclude_user'; //PK Sequence name
protected $pk_sequence_name = 'station_exclude_user_id_seq';

    public function setStation($id)
    {
        $id = trim($id);

        if ($id == 0
            or
            $this->Validator->isNumeric('station',
                $id,
                TTi18n::gettext('Selected Station is invalid')
            /*
                            $this->Validator->isResultSetWithRows(	'station',
                                                                $slf->getByID($id),
                                                                TTi18n::gettext('Selected Station is invalid')
            */
            )
        ) {
            $this->data['station_id'] = $id;

            return true;
        }

        return false;
    }

    public function setExcludeUser($id)
    {
        $id = trim($id);

        $ulf = TTnew('UserListFactory');

        if ($this->Validator->isResultSetWithRows('exclude_user',
            $ulf->getByID($id),
            TTi18n::gettext('Selected User is invalid')
        )
        ) {
            $this->data['user_id'] = $id;

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

    public function getCreatedDate()
    {
        return false;
    }

    //This table doesn't have any of these columns, so overload the functions.

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
            return TTLog::addEntry($this->getStation(), $log_action, TTi18n::getText('Employee') . ': ' . $u_obj->getFullName(false, true), null, $this->getTable());
        }

        return false;
    }

    public function getUserObject()
    {
        if (is_object($this->user_obj)) {
            return $this->user_obj;
        } else {
            $ulf = TTnew('UserListFactory');
            $ulf->getById($this->getExcludeUser());
            if ($ulf->getRecordCount() == 1) {
                $this->user_obj = $ulf->getCurrent();
                return $this->user_obj;
            }

            return false;
        }
    }

    public function getExcludeUser()
    {
        if (isset($this->data['user_id'])) {
            return (int)$this->data['user_id'];
        }

        return false;
    }

    public function getStation()
    {
        if (isset($this->data['station_id'])) {
            return (int)$this->data['station_id'];
        }
    }
}
