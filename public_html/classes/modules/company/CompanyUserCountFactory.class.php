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
 * @package Modules\Company
 */
class CompanyUserCountFactory extends Factory
{
    protected $table = 'company_user_count';
    protected $pk_sequence_name = 'company_user_count_id_seq'; //PK Sequence name

    public function getCompany()
    {
        return (int)$this->data['company_id'];
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

    public function getDateStamp($raw = false)
    {
        if (isset($this->data['date_stamp'])) {
            if ($raw === true) {
                return $this->data['date_stamp'];
            } else {
                return TTDate::strtotime($this->data['date_stamp']);
            }
        }

        return false;
    }

    public function setDateStamp($epoch)
    {
        $epoch = (!is_int($epoch)) ? trim($epoch) : $epoch; //Dont trim integer values, as it changes them to strings.

        if ($this->Validator->isDate('date_stamp',
            $epoch,
            TTi18n::gettext('Incorrect date'))
        ) {
            if ($epoch > 0) {
                $this->data['date_stamp'] = $epoch;

                return true;
            } else {
                $this->Validator->isTRUE('date_stamp',
                    false,
                    TTi18n::gettext('Incorrect date'));
            }
        }

        return false;
    }

    public function getActiveUsers()
    {
        if (isset($this->data['active_users'])) {
            return $this->data['active_users'];
        }

        return false;
    }

    public function setActiveUsers($value)
    {
        $value = (int)trim($value);

        if ($this->Validator->isNumeric('active_users',
            $value,
            TTi18n::gettext('Incorrect value'))
        ) {
            $this->data['active_users'] = $value;

            return true;
        }

        return false;
    }

    public function getInActiveUsers()
    {
        if (isset($this->data['inactive_users'])) {
            return $this->data['inactive_users'];
        }

        return false;
    }

    public function setInActiveUsers($value)
    {
        $value = (int)trim($value);

        if ($this->Validator->isNumeric('inactive_users',
            $value,
            TTi18n::gettext('Incorrect value'))
        ) {
            $this->data['inactive_users'] = $value;

            return true;
        }

        return false;
    }

    public function getDeletedUsers()
    {
        if (isset($this->data['deleted_users'])) {
            return $this->data['deleted_users'];
        }

        return false;
    }

    public function setDeletedUsers($value)
    {
        $value = (int)trim($value);

        if ($this->Validator->isNumeric('deleted_users',
            $value,
            TTi18n::gettext('Incorrect value'))
        ) {
            $this->data['deleted_users'] = $value;

            return true;
        }

        return false;
    }

    public function postSave()
    {
        //$this->removeCache( $this->getId() );

        return true;
    }

    //This table doesn't have any of these columns, so overload the functions.
    public function getDeleted()
    {
        return false;
    }

    public function setDeleted($bool)
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
}
