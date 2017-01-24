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
class StationBranchFactory extends Factory
{
    public $branch_obj = null;
        protected $table = 'station_branch'; //PK Sequence name
protected $pk_sequence_name = 'station_branch_id_seq';

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

    public function setBranch($id)
    {
        $id = trim($id);

        $blf = TTnew('BranchListFactory');

        if ($this->Validator->isResultSetWithRows('branch',
            $blf->getByID($id),
            TTi18n::gettext('Selected Branch is invalid')
        )
        ) {
            $this->data['branch_id'] = $id;

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
        $b_obj = $this->getBranchObject();
        if (is_object($b_obj)) {
            return TTLog::addEntry($this->getStation(), $log_action, TTi18n::getText('Branch') . ': ' . $b_obj->getName(), null, $this->getTable());
        }

        return false;
    }

    public function getBranchObject()
    {
        if (is_object($this->branch_obj)) {
            return $this->branch_obj;
        } else {
            $blf = TTnew('BranchListFactory');
            $blf->getById($this->getBranch());
            if ($blf->getRecordCount() == 1) {
                $this->branch_obj = $blf->getCurrent();
                return $this->branch_obj;
            }

            return false;
        }
    }

    public function getBranch()
    {
        if (isset($this->data['branch_id'])) {
            return (int)$this->data['branch_id'];
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
