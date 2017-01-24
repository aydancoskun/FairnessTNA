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
class OtherFieldFactory extends Factory
{
    protected $table = 'other_field';
    protected $pk_sequence_name = 'other_field_id_seq'; //PK Sequence name

    protected $company_obj = null;


    public function _getFactoryOptions($name, $parent = null)
    {
        $retval = null;
        switch ($name) {
            case 'type':
                $retval = array(
                    2 => TTi18n::gettext('Company'),
                    4 => TTi18n::gettext('Branch'),
                    5 => TTi18n::gettext('Department'),
                    10 => TTi18n::gettext('Employee'),
                    12 => TTi18n::gettext('Employee Title'),
                    15 => TTi18n::gettext('Punch'),
                    18 => TTi18n::gettext('Schedule')
                );
                break;
            case 'columns':
                $retval = array(
                    '-1010-type' => TTi18n::gettext('Type'),
                    '-1021-other_id1' => TTi18n::gettext('Other ID1'),
                    '-1022-other_id2' => TTi18n::gettext('Other ID2'),
                    '-1023-other_id3' => TTi18n::gettext('Other ID3'),
                    '-1024-other_id4' => TTi18n::gettext('Other ID4'),
                    '-1025-other_id5' => TTi18n::gettext('Other ID5'),

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
                    'type_id', //Required by Flex when a supervisor logs in to handle other fields properly.
                    'type',
                    'other_id1',
                    'other_id2',
                    'other_id3',
                    'other_id4',
                    'other_id5',
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
            'type_id' => 'Type',
            'type' => false,
            'other_id1' => 'OtherID1',
            'other_id2' => 'OtherID2',
            'other_id3' => 'OtherID3',
            'other_id4' => 'OtherID4',
            'other_id5' => 'OtherID5',
            'other_id6' => 'OtherID6',
            'other_id7' => 'OtherID7',
            'other_id8' => 'OtherID8',
            'other_id9' => 'OtherID9',
            'other_id10' => 'OtherID10',
            'deleted' => 'Deleted',
        );
        return $variable_function_map;
    }

    public function getCompanyObject()
    {
        if (is_object($this->company_obj)) {
            return $this->company_obj;
        } else {
            $clf = TTnew('CompanyListFactory');
            $this->company_obj = $clf->getById($this->getCompany())->getCurrent();

            return $this->company_obj;
        }
    }

    public function getCompany()
    {
        if (isset($this->data['company_id'])) {
            return (int)$this->data['company_id'];
        }

        return false;
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

    public function getType()
    {
        if (isset($this->data['type_id'])) {
            return (int)$this->data['type_id'];
        }

        return false;
    }

    public function setType($type)
    {
        $type = trim($type);
        Debug::text('Attempting to set Type To: ' . $type, __FILE__, __LINE__, __METHOD__, 10);

        if ($this->Validator->inArrayKey('type_id',
                $type,
                TTi18n::gettext('Incorrect Type'),
                $this->getOptions('type'))
            and
            $this->Validator->isTrue('type_id',
                $this->isUniqueType($type),
                TTi18n::gettext('Type already exists'))

        ) {
            $this->data['type_id'] = $type;

            return true;
        }

        return false;
    }

    public function isUniqueType($type)
    {
        $ph = array(
            'company_id' => (int)$this->getCompany(),
            'type_id' => (int)$type,
        );

        $query = 'select id from ' . $this->getTable() . '
					where company_id = ?
						AND type_id = ?
						AND deleted = 0';
        $type_id = $this->db->GetOne($query, $ph);
        Debug::Arr($type_id, 'Unique Type: ' . $type, __FILE__, __LINE__, __METHOD__, 10);

        if ($type_id === false) {
            return true;
        } else {
            if ($type_id == $this->getId()) {
                return true;
            }
        }

        return false;
    }

    public function getOtherID1()
    {
        return $this->data['other_id1'];
    }

    public function setOtherID1($value)
    {
        $value = trim($value);

        if ($value == ''
            or
            $this->Validator->isLength('other_id1',
                $value,
                TTi18n::gettext('Other ID1 is invalid'),
                1, 255)
        ) {
            $this->data['other_id1'] = $value;

            return true;
        }

        return false;
    }

    public function getOtherID2()
    {
        return $this->data['other_id2'];
    }

    public function setOtherID2($value)
    {
        $value = trim($value);

        if ($value == ''
            or
            $this->Validator->isLength('other_id2',
                $value,
                TTi18n::gettext('Other ID2 is invalid'),
                1, 255)
        ) {
            $this->data['other_id2'] = $value;

            return true;
        }

        return false;
    }

    public function getOtherID3()
    {
        return $this->data['other_id3'];
    }

    public function setOtherID3($value)
    {
        $value = trim($value);

        if ($value == ''
            or
            $this->Validator->isLength('other_id3',
                $value,
                TTi18n::gettext('Other ID3 is invalid'),
                1, 255)
        ) {
            $this->data['other_id3'] = $value;

            return true;
        }

        return false;
    }

    public function getOtherID4()
    {
        return $this->data['other_id4'];
    }

    public function setOtherID4($value)
    {
        $value = trim($value);

        if ($value == ''
            or
            $this->Validator->isLength('other_id4',
                $value,
                TTi18n::gettext('Other ID4 is invalid'),
                1, 255)
        ) {
            $this->data['other_id4'] = $value;

            return true;
        }

        return false;
    }

    public function getOtherID5()
    {
        return $this->data['other_id5'];
    }

    public function setOtherID5($value)
    {
        $value = trim($value);

        if ($value == ''
            or
            $this->Validator->isLength('other_id5',
                $value,
                TTi18n::gettext('Other ID5 is invalid'),
                1, 255)
        ) {
            $this->data['other_id5'] = $value;

            return true;
        }

        return false;
    }

    public function getOtherID6()
    {
        return $this->data['other_id6'];
    }

    public function setOtherID6($value)
    {
        $value = trim($value);

        if ($value == ''
            or
            $this->Validator->isLength('other_id6',
                $value,
                TTi18n::gettext('Other ID6 is invalid'),
                1, 255)
        ) {
            $this->data['other_id6'] = $value;

            return true;
        }

        return false;
    }

    public function getOtherID7()
    {
        return $this->data['other_id7'];
    }

    public function setOtherID7($value)
    {
        $value = trim($value);

        if ($value == ''
            or
            $this->Validator->isLength('other_id7',
                $value,
                TTi18n::gettext('Other ID7 is invalid'),
                1, 255)
        ) {
            $this->data['other_id7'] = $value;

            return true;
        }

        return false;
    }

    public function getOtherID8()
    {
        return $this->data['other_id8'];
    }

    public function setOtherID8($value)
    {
        $value = trim($value);

        if ($value == ''
            or
            $this->Validator->isLength('other_id8',
                $value,
                TTi18n::gettext('Other ID8 is invalid'),
                1, 255)
        ) {
            $this->data['other_id8'] = $value;

            return true;
        }

        return false;
    }

    public function getOtherID9()
    {
        return $this->data['other_id9'];
    }

    public function setOtherID9($value)
    {
        $value = trim($value);

        if ($value == ''
            or
            $this->Validator->isLength('other_id9',
                $value,
                TTi18n::gettext('Other ID9 is invalid'),
                1, 255)
        ) {
            $this->data['other_id9'] = $value;

            return true;
        }

        return false;
    }

    public function getOtherID10()
    {
        return $this->data['other_id10'];
    }

    public function setOtherID10($value)
    {
        $value = trim($value);

        if ($value == ''
            or
            $this->Validator->isLength('other_id10',
                $value,
                TTi18n::gettext('Other ID10 is invalid'),
                1, 255)
        ) {
            $this->data['other_id10'] = $value;

            return true;
        }

        return false;
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
        $data = array();
        $variable_function_map = $this->getVariableToFunctionMap();
        if (is_array($variable_function_map)) {
            foreach ($variable_function_map as $variable => $function_stub) {
                if ($include_columns == null or (isset($include_columns[$variable]) and $include_columns[$variable] == true)) {
                    $function = 'get' . $function_stub;
                    switch ($variable) {
                        case 'type':
                            $function = 'get' . $variable;
                            if (method_exists($this, $function)) {
                                $data[$variable] = Option::getByKey($this->$function(), $this->getOptions($variable));
                            }
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

    public function addLog($log_action)
    {
        return TTLog::addEntry($this->getId(), $log_action, TTi18n::getText('Other Fields'), null, $this->getTable(), $this);
    }
}
