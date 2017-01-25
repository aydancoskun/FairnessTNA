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
 * @package Modules\Qualification
 */
class UserLanguageFactory extends Factory
{
    protected $table = 'user_language';
    protected $pk_sequence_name = 'user_language_id_seq'; //PK Sequence name
    protected $qualification_obj = null;

    public function _getFactoryOptions($name, $parent = null)
    {
        $retval = null;
        switch ($name) {
            case 'fluency':
                $retval = array(
                    10 => TTi18n::gettext('Speaking'),
                    20 => TTi18n::gettext('Writing'),
                    30 => TTi18n::gettext('Reading'),
                );
                break;
            case 'competency':
                $retval = array(
                    10 => TTi18n::gettext('Native Language'),
                    20 => TTi18n::gettext('Good'),
                    30 => TTi18n::gettext('Basic'),
                    40 => TTi18n::gettext('Poor'),
                );
                break;
            case 'columns':
                $retval = array(
                    '-1010-first_name' => TTi18n::gettext('First Name'),
                    '-1020-last_name' => TTi18n::gettext('Last Name'),
                    '-2050-qualification' => TTi18n::gettext('Language'),
                    '-2040-group' => TTi18n::gettext('Group'),
                    '-4010-fluency' => TTi18n::gettext('Fluency'),
                    '-4020-competency' => TTi18n::gettext('Competency'),
                    '-1040-description' => TTi18n::getText('Description'),
                    '-1300-tag' => TTi18n::gettext('Tags'),

                    '-1090-title' => TTi18n::gettext('Title'),
                    '-1099-user_group' => TTi18n::gettext('Employee Group'),
                    '-1100-default_branch' => TTi18n::gettext('Branch'),
                    '-1110-default_department' => TTi18n::gettext('Department'),
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
                    'first_name',
                    'last_name',
                    'qualification',
                    'fluency',
                    'competency',
                    'description',
                );
                break;

        }

        return $retval;
    }

    public function _getVariableToFunctionMap($data)
    {
        $variable_function_map = array(
            'id' => 'ID',
            'user_id' => 'User',
            'first_name' => false,
            'last_name' => false,
            'qualification_id' => 'Qualification',
            'qualification' => false,
            'group' => false,
            'fluency_id' => 'Fluency',
            'fluency' => false,

            'competency_id' => 'Competency',
            'competency' => false,

            'description' => 'Description',
            'tag' => 'Tag',
            'default_branch' => false,
            'default_department' => false,
            'user_group' => false,
            'title' => false,
            'deleted' => 'Deleted',
        );
        return $variable_function_map;
    }

    public function setUser($id)
    {
        $id = trim($id);

        $ulf = TTnew('UserListFactory');

        if ($this->Validator->isResultSetWithRows('user_id',
            $ulf->getByID($id),
            TTi18n::gettext('Invalid Employee')
        )
        ) {
            $this->data['user_id'] = $id;

            return true;
        }

        return false;
    }

    public function setQualification($id)
    {
        $id = trim($id);

        $qlf = TTnew('QualificationListFactory');

        if ($this->Validator->isResultSetWithRows('qualification_id',
            $qlf->getById($id),
            TTi18n::gettext('Invalid Qualification')
        )
        ) {
            $this->data['qualification_id'] = $id;

            return true;
        }

        return false;
    }

    public function getFluency()
    {
        if (isset($this->data['fluency_id'])) {
            return (int)$this->data['fluency_id'];
        }
        return false;
    }

    public function setFluency($fluency_id)
    {
        $fluency_id = trim($fluency_id);

        if ($this->Validator->inArrayKey('fluency_id',
            $fluency_id,
            TTi18n::gettext('Fluency is invalid'),
            $this->getOptions('fluency'))
        ) {
            $this->data['fluency_id'] = $fluency_id;

            return true;
        }

        return false;
    }

    public function getCompetency()
    {
        if (isset($this->data['competency_id'])) {
            return (int)$this->data['competency_id'];
        }
        return false;
    }

    public function setCompetency($competency_id)
    {
        $competency_id = trim($competency_id);

        if ($this->Validator->inArrayKey('competency_id',
            $competency_id,
            TTi18n::gettext('Competency is invalid'),
            $this->getOptions('competency'))
        ) {
            $this->data['competency_id'] = $competency_id;

            return true;
        }

        return false;
    }

    public function getDescription()
    {
        if (isset($this->data['description'])) {
            return $this->data['description'];
        }
        return false;
    }

    public function setDescription($description)
    {
        $description = trim($description);

        if ($description == ''
            or
            $this->Validator->isLength('description',
                $description,
                TTi18n::gettext('Description is invalid'),
                2, 255)
        ) {
            $this->data['description'] = $description;
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
        $this->removeCache($this->getUser() . $this->getQualification());

        if ($this->getDeleted() == false) {
            Debug::text('Setting Tags...', __FILE__, __LINE__, __METHOD__, 10);
            CompanyGenericTagMapFactory::setTags($this->getQualificationObject()->getCompany(), 254, $this->getID(), $this->getTag());
        }
        return true;
    }

    public function getUser()
    {
        if (isset($this->data['user_id'])) {
            return (int)$this->data['user_id'];
        }
        return false;
    }

    public function getQualification()
    {
        if (isset($this->data['qualification_id'])) {
            return (int)$this->data['qualification_id'];
        }
        return false;
    }

    public function getQualificationObject()
    {
        return $this->getGenericObject('QualificationListFactory', $this->getQualification(), 'qualification_obj');
    }

    public function getTag()
    {
        //Check to see if any temporary data is set for the tags, if not, make a call to the database instead.
        //postSave() needs to get the tmp_data.
        if (isset($this->tmp_data['tags'])) {
            return $this->tmp_data['tags'];
        } elseif (is_object($this->getQualificationObject()) and $this->getQualificationObject()->getCompany() > 0 and $this->getID() > 0) {
            return CompanyGenericTagMapListFactory::getStringByCompanyIDAndObjectTypeIDAndObjectID($this->getQualificationObject()->getCompany(), 254, $this->getID());
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
                        case 'qualification':
                        case 'group':
                        case 'first_name':
                        case 'last_name':
                        case 'title':
                        case 'user_group':
                        case 'default_branch':
                        case 'default_department':
                            $data[$variable] = $this->getColumn($variable);
                            break;
                        case 'fluency':
                        case 'competency':
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
            $this->getPermissionColumns($data, $this->getUser(), $this->getCreatedBy(), $permission_children_ids, $include_columns);

            $this->getCreatedAndUpdatedColumns($data, $include_columns);
        }

        return $data;
    }

    public function addLog($log_action)
    {
        return TTLog::addEntry($this->getId(), $log_action, TTi18n::getText('Language'), null, $this->getTable(), $this);
    }
}
