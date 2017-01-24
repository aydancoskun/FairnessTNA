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
 * @package Modules\Qualification
 */
class UserEducationFactory extends Factory
{
    protected $table = 'user_education';
    protected $pk_sequence_name = 'user_education_id_seq'; //PK Sequence name
    protected $qualification_obj = null;

    //protected $grade_score_validator_regex = '/^[0-9]{1,250}$/i';
    public function _getFactoryOptions($name, $parent = null)
    {
        $retval = null;
        switch ($name) {
            case 'columns':
                $retval = array(
                    '-1010-first_name' => TTi18n::gettext('First Name'),
                    '-1020-last_name' => TTi18n::gettext('Last Name'),

                    '-2050-qualification' => TTi18n::gettext('Course'),

                    '-2040-group' => TTi18n::gettext('Group'),

                    '-3030-institute' => TTi18n::gettext('Institute'),
                    '-3040-major' => TTi18n::gettext('Major/Specialization'),
                    '-3050-minor' => TTi18n::gettext('Minor'),
                    '-3060-graduate_date' => TTi18n::gettext('Graduation Date'),
                    '-3070-grade_score' => TTi18n::gettext('Grade/Score'),
                    '-1170-start_date' => TTi18n::gettext('Start Date'),
                    '-1180-end_date' => TTi18n::gettext('End Date'),

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
                    'institute',
                    'major',
                    'minor',
                    'graduate_date',
                    'grade_score',
                    'start_date',
                    'end_date',
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
            'institute' => 'Institute',
            'major' => 'Major',
            'minor' => 'Minor',
            'graduate_date' => 'GraduateDate',
            'grade_score' => 'GradeScore',
            'start_date' => 'StartDate',
            'end_date' => 'EndDate',

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

    public function getInstitute()
    {
        if (isset($this->data['institute'])) {
            return $this->data['institute'];
        }
        return false;
    }

    public function setInstitute($institute)
    {
        $institute = trim($institute);

        if ($institute == ''
            or
            $this->Validator->isLength('institute',
                $institute,
                TTi18n::gettext('Institute is invalid'),
                2, 255)
        ) {
            $this->data['institute'] = $institute;
            return true;
        }

        return false;
    }

    public function getMajor()
    {
        if (isset($this->data['major'])) {
            return $this->data['major'];
        }
        return false;
    }

    public function setMajor($major)
    {
        $major = trim($major);

        if ($major == ''
            or
            $this->Validator->isLength('major',
                $major,
                TTi18n::gettext('Major/Specialization is invalid'),
                2, 255)
        ) {
            $this->data['major'] = $major;
            return true;
        }

        return false;
    }

    public function getMinor()
    {
        if (isset($this->data['minor'])) {
            return $this->data['minor'];
        }
        return false;
    }

    public function setMinor($minor)
    {
        $minor = trim($minor);

        if ($minor == ''
            or
            $this->Validator->isLength('minor',
                $minor,
                TTi18n::gettext('Minor is invalid'),
                2, 255)
        ) {
            $this->data['minor'] = $minor;
            return true;
        }

        return false;
    }

    public function getGradeScore()
    {
        if (isset($this->data['grade_score'])) {
            return $this->data['grade_score'];
        }
        return false;
    }

    public function setGradeScore($grade_score)
    {
        $grade_score = trim($grade_score);
        // $grade_score = $this->Validator->stripNonFloat( $grade_score );
        if (($grade_score != ''
                and
                ($this->Validator->isNumeric('grade_score',
                        $grade_score,
                        TTi18n::gettext('Grade/Score must only be digits')
                    )
                    and
                    $this->Validator->isLengthAfterDecimal('grade_score',
                        $grade_score,
                        TTi18n::gettext('Invalid Grade/Score'),
                        0,
                        2
                    )))
            or $grade_score == ''

        ) {
            $this->data['grade_score'] = $grade_score;
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
            CompanyGenericTagMapFactory::setTags($this->getQualificationObject()->getCompany(), 252, $this->getID(), $this->getTag());
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
            return CompanyGenericTagMapListFactory::getStringByCompanyIDAndObjectTypeIDAndObjectID($this->getQualificationObject()->getCompany(), 252, $this->getID());
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
                        case 'start_date':
                            $this->setStartDate(TTDate::parseDateTime($data['start_date']));
                            break;
                        case 'end_date':
                            $this->setEndDate(TTDate::parseDateTime($data['end_date']));
                            break;
                        case 'graduate_date':
                            $this->setGraduateDate(TTDate::parseDateTime($data['graduate_date']));
                            break;
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

    public function setStartDate($epoch)
    {
        $epoch = (!is_int($epoch)) ? trim($epoch) : $epoch; //Dont trim integer values, as it changes them to strings.

        if ($epoch == '') {
            $epoch = null;
        }

        if ($epoch == null
            or
            $this->Validator->isDate('start_date',
                $epoch,
                TTi18n::gettext('Incorrect start date'))
        ) {
            $this->data['start_date'] = $epoch;

            return true;
        }

        return false;
    }

    public function setEndDate($epoch)
    {
        $epoch = (!is_int($epoch)) ? trim($epoch) : $epoch; //Dont trim integer values, as it changes them to strings.

        if ($epoch == '') {
            $epoch = null;
        }

        if ($epoch == null
            or
            $this->Validator->isDate('end_date',
                $epoch,
                TTi18n::gettext('Incorrect end date'))
        ) {
            $this->data['end_date'] = $epoch;

            return true;
        }

        return false;
    }

    public function setGraduateDate($epoch)
    {
        $epoch = (!is_int($epoch)) ? trim($epoch) : $epoch; //Dont trim integer values, as it changes them to strings.
        if ($epoch == '') {
            $epoch = null;
        }
        if ($epoch == null
            or
            $this->Validator->isDate('graduate_date',
                $epoch,
                TTi18n::gettext('Incorrect graduation date'))
        ) {
            $this->data['graduate_date'] = $epoch;

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
                        case 'start_date':
                            $data[$variable] = TTDate::getAPIDate('DATE', $this->getStartDate());
                            break;
                        case 'end_date':
                            $data['end_date'] = TTDate::getAPIDate('DATE', $this->getEndDate());
                            break;
                        case 'graduate_date':
                            $data['graduate_date'] = TTDate::getAPIDate('DATE', $this->getGraduateDate());
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

    public function getStartDate()
    {
        if (isset($this->data['start_date'])) {
            return (int)$this->data['start_date'];
        }

        return false;
    }

    public function getEndDate()
    {
        if (isset($this->data['end_date'])) {
            return (int)$this->data['end_date'];
        }

        return false;
    }

    public function getGraduateDate()
    {
        if (isset($this->data['graduate_date'])) {
            return (int)$this->data['graduate_date'];
        }

        return false;
    }

    public function addLog($log_action)
    {
        return TTLog::addEntry($this->getId(), $log_action, TTi18n::getText('Education'), null, $this->getTable(), $this);
    }
}
