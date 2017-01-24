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
 * @package Modules\KPI
 */
class UserReviewFactory extends Factory
{
    protected $table = 'user_review';
    protected $pk_sequence_name = 'user_review_id_seq'; //PK Sequence name
    protected $kpi_obj = null;

    public function _getFactoryOptions($name, $parent = null)
    {
        $retval = null;
        switch ($name) {
            case 'columns':
                $retval = array(
                    '-2050-rating' => TTi18n::gettext('Rating'),
                    '-1200-note' => TTi18n::gettext('Note'),
                    '-1300-tag' => TTi18n::gettext('Tags'),
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
                    'rating',
                    'note'
                );
                break;

        }

        return $retval;
    }

    public function _getVariableToFunctionMap($data)
    {
        $variable_function_map = array(
            'id' => 'ID',
            'user_review_control_id' => 'UserReviewControl',
            'kpi_id' => 'KPI',
            'name' => false,
            'type_id' => false,
            'status_id' => false,
            'minimum_rate' => false,
            'maximum_rate' => false,
            'description' => false,
            'rating' => 'Rating',
            'note' => 'Note',
            'tag' => 'Tag',
            'deleted' => 'Deleted',
        );
        return $variable_function_map;
    }

    public function setKPI($id)
    {
        $id = trim($id);
        $klf = TTnew('KPIListFactory');
        if ($this->Validator->isResultSetWithRows('kpi_id',
            $klf->getById($id),
            TTi18n::gettext('Invalid KPI')
        )
        ) {
            $this->data['kpi_id'] = $id;
            return true;
        }
        return false;
    }

    public function setUserReviewControl($id)
    {
        $id = trim($id);

        $urclf = TTnew('UserReviewControlListFactory');

        if ($this->Validator->isResultSetWithRows('user_review_control_id',
            $urclf->getById($id),
            TTi18n::gettext('Invalid review control')
        )
        ) {
            $this->data['user_review_control_id'] = $id;
            return true;
        }
        return false;
    }

    public function getRating()
    {
        if (isset($this->data['rating'])) {
            return $this->data['rating'];
        }
        return false;
    }

    public function setRating($value)
    {
        $value = trim($value);

        if ($value == '') {
            $value = null;
        }
        if ($value == null
            or
            (
                $this->Validator->isNumeric('rating',
                    $value,
                    TTi18n::gettext('Rating must only be digits')
                )
                and
                $this->Validator->isLengthBeforeDecimal('rating',
                    $value,
                    TTi18n::gettext('Invalid Rating'),
                    0,
                    7
                )
                and
                $this->Validator->isLengthAfterDecimal('rating',
                    $value,
                    TTi18n::gettext('Invalid Rating'),
                    0,
                    2
                )
            )
        ) {
            $this->data['rating'] = $value;

            return true;
        }

        return false;
    }

    public function getNote()
    {
        if (isset($this->data['note'])) {
            return $this->data['note'];
        }
        return false;
    }

    public function setNote($note)
    {
        $note = trim($note);

        if ($note == ''
            or
            $this->Validator->isLength('note',
                $note,
                TTi18n::gettext('Note is too long'),
                0, 4096)
        ) {
            $this->data['note'] = $note;
            return true;
        }

        return false;
    }

    public function setTag($tags)
    {
        $tags = trim($tags);

        //Save the tags in temporary memory to be committed in postSave()
        $this->tmp_data['tags'] = $tags;

        return true;
    }

    public function Validate($ignore_warning = true)
    {
        //$this->setProvince( $this->getProvince() ); //Not sure why this was there, but it causes duplicate errors if the province is incorrect.

        return true;
    }

    public function preSave()
    {
        return true;
    }

    public function postSave()
    {
        $this->removeCache($this->getId());

        if ($this->getDeleted() == false) {
            Debug::text('Setting Tags...', __FILE__, __LINE__, __METHOD__, 10);
            CompanyGenericTagMapFactory::setTags($this->getKPIObject()->getCompany(), 330, $this->getID(), $this->getTag());
        }

        return true;
    }

    public function getKPIObject()
    {
        return $this->getGenericObject('KPIListFactory', $this->getKPI(), 'kpi_obj');
    }

    public function getKPI()
    {
        if (isset($this->data['kpi_id'])) {
            return (int)$this->data['kpi_id'];
        }
        return false;
    }

    public function getTag()
    {
        //Check to see if any temporary data is set for the tags, if not, make a call to the database instead.
        //postSave() needs to get the tmp_data.
        if (isset($this->tmp_data['tags'])) {
            return $this->tmp_data['tags'];
        } elseif (is_object($this->getKPIObject()) and $this->getKPIObject()->getCompany() > 0 and $this->getID() > 0) {
            return CompanyGenericTagMapListFactory::getStringByCompanyIDAndObjectTypeIDAndObjectID($this->getKPIObject()->getCompany(), 330, $this->getID());
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

    public function getObjectAsArray($include_columns = null, $permission_children_ids = false)
    {
        $data = array();
        $variable_function_map = $this->getVariableToFunctionMap();
        if (is_array($variable_function_map)) {
            foreach ($variable_function_map as $variable => $function_stub) {
                if ($include_columns == null or (isset($include_columns[$variable]) and $include_columns[$variable] == true)) {
                    $function = 'get' . $function_stub;

                    switch ($variable) {
                        case 'name':
                        case 'type_id':
                        case 'status_id':
                        case 'minimum_rate':
                        case 'maximum_rate':
                        case 'description':
                            $data[$variable] = $this->getColumn($variable);
                            break;
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
        $kpi_obj = $this->getKPIObject();
        if (is_object($kpi_obj)) {
            return TTLog::addEntry($this->getUserReviewControl(), $log_action, TTi18n::getText('Employee Review KPI') . ' - ' . TTi18n::getText('KPI') . ': ' . $kpi_obj->getName(), null, $this->getTable(), $this);
        }
        return false;
    }

    public function getUserReviewControl()
    {
        if (isset($this->data['user_review_control_id'])) {
            return (int)$this->data['user_review_control_id'];
        }
        return false;
    }
}
