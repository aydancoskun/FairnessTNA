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
 * @package Modules\Help
 */
class HelpGroupFactory extends Factory
{
    protected $table = 'help_group';
    protected $pk_sequence_name = 'help_group_id_seq'; //PK Sequence name

    public function getHelpGroupControl()
    {
        return (int)$this->data['help_group_control_id'];
    }

    public function setHelpGroupControl($id)
    {
        $id = trim($id);

        $hgclf = TTnew('HelpGroupControlListFactory');

        if ($this->Validator->isResultSetWithRows('help_group_control',
            $hgclf->getByID($id),
            TTi18n::gettext('Help Group Control is invalid')
        )
        ) {
            $this->data['help_group_control_id'] = $id;

            return true;
        }

        return false;
    }

    public function getHelp()
    {
        return (int)$this->data['help_id'];
    }

    public function setHelp($id)
    {
        $id = trim($id);

        $hlf = TTnew('HelpListFactory');

        if ($this->Validator->isResultSetWithRows('help',
            $hlf->getByID($id),
            TTi18n::gettext('Help Entry is invalid')
        )
        ) {
            $this->data['help_id'] = $id;

            return true;
        }

        return false;
    }

    public function getOrder()
    {
        return $this->data['order_value'];
    }

    public function setOrder($value)
    {
        $value = trim($value);

        if ($this->Validator->isNumeric('order',
            $value,
            TTi18n::gettext('Order is invalid')
        )
        ) {
            $this->data['order_value'] = $value;

            return true;
        }

        return false;
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
}
