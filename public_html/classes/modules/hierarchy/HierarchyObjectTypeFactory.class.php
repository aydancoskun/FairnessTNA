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
class HierarchyObjectTypeFactory extends Factory
{
    public $hierarchy_control_obj = null;
        protected $table = 'hierarchy_object_type'; //PK Sequence name
protected $pk_sequence_name = 'hierarchy_object_type_id_seq';

    public function _getFactoryOptions($name, $parent = null)
    {
        $retval = null;
        switch ($name) {
            case 'object_type':
                $retval = array(
                    //10 => TTi18n::gettext('Recurring Schedule'),
                    //20 => TTi18n::gettext('Schedule Amendment'),
                    //30 => TTi18n::gettext('Shift Amendment'),
                    //40 => TTi18n::gettext('Pay Stub Amendment')
                    //50 => TTi18n::gettext('Request'),

                    //Add 1000 to request type_id's. Make sure no other objects pass 1000.
                    1010 => TTi18n::gettext('Request: Missed Punch'),
                    1020 => TTi18n::gettext('Request: Time Adjustment'),
                    1030 => TTi18n::gettext('Request: Absence (incl. Vacation)'),
                    1040 => TTi18n::gettext('Request: Schedule Adjustment'),
                    1100 => TTi18n::gettext('Request: Other'),

                    80 => TTi18n::gettext('Exception'),
                    90 => TTi18n::gettext('TimeSheet'),
                    100 => TTi18n::gettext('Permission'),
                );

                break;
            case 'short_object_type': //Defines a short form of the names.
                $retval = array(
                    //10 => TTi18n::gettext('Recurring Schedule'),
                    //20 => TTi18n::gettext('Schedule Amendment'),
                    //30 => TTi18n::gettext('Shift Amendment'),
                    //40 => TTi18n::gettext('Pay Stub Amendment')
                    //50 => TTi18n::gettext('Request'),

                    //Add 1000 to request type_id's. Make sure no other objects pass 1000.
                    1010 => TTi18n::gettext('R:Missed Punch'),
                    1020 => TTi18n::gettext('R:Adjustment'),
                    1030 => TTi18n::gettext('R:Absence'),
                    1040 => TTi18n::gettext('R:Schedule'),
                    1100 => TTi18n::gettext('R:Other'),

                    80 => TTi18n::gettext('Exception'),
                    90 => TTi18n::gettext('TimeSheet'),
                    100 => TTi18n::gettext('Permission'),
                    200 => TTi18n::gettext('Expense'),
                );
                break;

        }

        return $retval;
    }

    public function setHierarchyControl($id)
    {
        $id = trim($id);

        $hclf = TTnew('HierarchyControlListFactory');
        Debug::Text('Hierarchy Control ID: ' . $id, __FILE__, __LINE__, __METHOD__, 10);

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

    public function setObjectType($id)
    {
        $id = trim($id);

        if ($this->Validator->inArrayKey('object_type',
                $id,
                TTi18n::gettext('Object Type is invalid'),
                $this->getOptions('object_type'))
            and
            $this->Validator->isTrue('object_type',
                $this->isUniqueObjectType($id),
                TTi18n::gettext('Object Type is already assigned to another hierarchy'))

        ) {
            $this->data['object_type_id'] = $id;

            return true;
        }

        return false;
    }

    public function isUniqueObjectType($object_type)
    {
        /*
        $company_id = $this->getHierarchyControlObject()->getCompany();

        $hotlf = TTnew( 'HierarchyObjectTypeListFactory' );
        $hotlf->getByCompanyId( $company_id );
        foreach ( $hotlf as $object_type_obj) {
            if ( $object_type_obj->getId() !== $this->getId() ) {
                $assigned_object_types[] = $object_type_obj->getObjectType();
            }
        }

        if ( isset($assigned_object_types) AND is_array($assigned_object_types) AND in_array( $object_type, $assigned_object_types) ) {
            return FALSE;
        }
*/
        return true;
    }

    public function postSave()
    {
        $cache_id = $this->getHierarchyControlObject()->getCompany() . $this->getObjectType();
        $this->removeCache($cache_id);

        return true;
    }

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

    public function getObjectType()
    {
        return (int)$this->data['object_type_id'];
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

    public function addLog($log_action)
    {
        $object_type = Option::getByKey($this->getObjectType(), Misc::TrimSortPrefix($this->getOptions('object_type')));
        return TTLog::addEntry($this->getHierarchyControl(), $log_action, TTi18n::getText('Object') . ': ' . $object_type, null, $this->getTable());
    }
}
