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
 * @package Modules\KPI
 */
class UserReviewControlFactory extends Factory
{
    protected $table = 'user_review_control';
    protected $pk_sequence_name = 'user_review_control_id_seq'; //PK Sequence name
    protected $user_obj = null;

    public function _getFactoryOptions($name, $parent = null)
    {
        $retval = null;
        switch ($name) {
            case 'type':
                $retval = array(
                    10 => TTi18n::gettext('Accolade'),
                    15 => TTi18n::gettext('Discipline'),
                    20 => TTi18n::gettext('Review (General)'),
                    25 => TTi18n::gettext('Review (Wage)'),
                    30 => TTi18n::gettext('Review (Performance)'),
                    35 => TTi18n::gettext('Accident/Injury'),
                    37 => TTi18n::gettext('Background Check'),
                    38 => TTi18n::gettext('Drug Test'),
                    40 => TTi18n::gettext('Entrance Interview'),
                    45 => TTi18n::gettext('Exit Interview'),
                    100 => TTi18n::gettext('Miscellaneous'),
                );
                break;
            case 'term':
                $retval = array(
                    10 => TTi18n::gettext('Positive'),
                    20 => TTi18n::gettext('Neutral'),
                    30 => TTi18n::gettext('Negative'),
                );
                break;
            case 'severity':
                $retval = array(
                    10 => TTi18n::gettext('Normal'),
                    20 => TTi18n::gettext('Low'),
                    30 => TTi18n::gettext('Medium'),
                    40 => TTi18n::gettext('High'),
                    50 => TTi18n::gettext('Critical'),
                );
                break;
            case 'status':
                $retval = array(
                    10 => TTi18n::gettext('Scheduled'),
                    20 => TTi18n::gettext('Being Reviewed'),
                    30 => TTi18n::gettext('Complete'),
                );
                break;
            case 'columns':
                $retval = array(
                    '-4070-user' => TTi18n::gettext('Employee Name'),
                    '-4080-reviewer_user' => TTi18n::gettext('Reviewer Name'),
                    '-1170-start_date' => TTi18n::gettext('Start Date'),
                    '-1180-end_date' => TTi18n::gettext('End Date'),
                    '-4090-due_date' => TTi18n::gettext('Due Date'),
                    '-1040-type' => TTi18n::gettext('Type'),
                    '-1060-term' => TTi18n::gettext('Terms'),
                    '-1010-severity' => TTi18n::gettext('Severity/Importance'),
                    '-1020-status' => TTi18n::gettext('Status'),
                    '-1050-rating' => TTi18n::gettext('Overall Rating'),
                    '-1200-note' => TTi18n::gettext('Notes'),
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
                    'user',
                    'reviewer_user',
                    'type',
                    'term',
                    'severity',
                    'start_date',
                    'end_date',
                    'due_date',
                );
                break;
            case 'unique_columns': //Columns that are unique, and disabled for mass editing.
                $retval = array();
                break;
        }

        return $retval;
    }

    public function _getVariableToFunctionMap($data)
    {
        $variable_function_map = array(
            'id' => 'ID',
            'user_id' => 'User',
            'user' => false,
            'reviewer_user_id' => 'ReviewerUser',
            'reviewer_user' => false,
            'type_id' => 'Type',
            'type' => false,
            'term_id' => 'Term',
            'term' => false,
            'severity_id' => 'Severity',
            'severity' => false,
            'status_id' => 'Status',
            'status' => false,
            'start_date' => 'StartDate',
            'end_date' => 'EndDate',
            'due_date' => 'DueDate',
            'rating' => 'Rating',
            'note' => 'Note',
            'tag' => 'Tag',

            'deleted' => 'Deleted',
        );
        return $variable_function_map;
    }

    public function setUser($id)
    {
        $id = trim($id);
        $ulf = TTnew('UserListFactory');
        //$cgmlf = TTnew( 'CompanyGenericMapListFactory' );
        if ($this->Validator->isResultSetWithRows('user_id',
            $ulf->getByID($id),
            TTi18n::gettext('Invalid employee')
        )
        ) {
            $this->data['user_id'] = $id;

            return true;
        }

        return false;
    }

    public function getReviewerUser()
    {
        if (isset($this->data['reviewer_user_id'])) {
            return (int)$this->data['reviewer_user_id'];
        }
        return false;
    }

    public function setReviewerUser($id)
    {
        $id = trim($id);
        $ulf = TTnew('UserListFactory');
        if ($this->Validator->isResultSetWithRows('reviewer_user_id',
            $ulf->getByID($id),
            TTi18n::gettext('Invalid reviewer')
        )
        ) {
            $this->data['reviewer_user_id'] = $id;

            return true;
        }

        return false;
    }

    public function setType($type)
    {
        $type = trim($type);

        if ($this->Validator->inArrayKey('type',
            $type,
            TTi18n::gettext('Incorrect Type'),
            $this->getOptions('type'))
        ) {
            $this->data['type_id'] = $type;
            return true;
        }

        return false;
    }

    public function getTerm()
    {
        if (isset($this->data['term_id'])) {
            return (int)$this->data['term_id'];
        }

        return false;
    }

    public function setTerm($value)
    {
        $value = trim($value);

        if ($this->Validator->inArrayKey('term',
            $value,
            TTi18n::gettext('Incorrect Terms'),
            $this->getOptions('term'))
        ) {
            $this->data['term_id'] = $value;

            return true;
        }

        return false;
    }

    public function getSeverity()
    {
        if (isset($this->data['severity_id'])) {
            return (int)$this->data['severity_id'];
        }

        return false;
    }

    public function setSeverity($value)
    {
        $value = trim($value);

        if ($this->Validator->inArrayKey('severity',
            $value,
            TTi18n::gettext('Incorrect Severity'),
            $this->getOptions('severity'))
        ) {
            $this->data['severity_id'] = $value;

            return true;
        }

        return false;
    }

    public function setStatus($status)
    {
        $status = trim($status);

        if ($this->Validator->inArrayKey('status',
            $status,
            TTi18n::gettext('Incorrect Status'),
            $this->getOptions('status'))
        ) {
            $this->data['status_id'] = $status;
            Debug::Text('Setting status_id data...	  ' . $this->data['status_id'], __FILE__, __LINE__, __METHOD__, 10);
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
        if ((
            $value == null
            or
            ($this->Validator->isNumeric('rating',
                    $value,
                    TTi18n::gettext('Rating must only be digits')
                )
                and
                $this->Validator->isLengthAfterDecimal('rating',
                    $value,
                    TTi18n::gettext('Invalid Rating'),
                    0,
                    2
                )))
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
                TTi18n::gettext('Note is too short or too long'),
                2, 2048)
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
        $start_date = $this->getStartDate();
        $end_date = $this->getEndDate();
        $due_date = $this->getDueDate();
        if ($start_date != '' and $end_date != '' and $due_date != '') {
            if ($end_date < $start_date) {
                $this->Validator->isTrue('end_date',
                    false,
                    TTi18n::gettext('End date should be after start date')
                );
            }
            if ($due_date < $start_date) {
                $this->Validator->isTrue('due_date',
                    false,
                    TTi18n::gettext('Due date should be after start date')
                );
            }
        }
        return true;
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

    public function getDueDate()
    {
        if (isset($this->data['due_date'])) {
            return (int)$this->data['due_date'];
        }

        return false;
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
            CompanyGenericTagMapFactory::setTags($this->getUserObject()->getCompany(), 320, $this->getID(), $this->getTag());
        }
        return true;
    }

    public function getUserObject()
    {
        return $this->getGenericObject('UserListFactory', $this->getUser(), 'user_obj');
    }

    public function getUser()
    {
        if (isset($this->data['user_id'])) {
            return (int)$this->data['user_id'];
        }
        return false;
    }

    public function getTag()
    {
        //Check to see if any temporary data is set for the tags, if not, make a call to the database instead.
        //postSave() needs to get the tmp_data.
        if (isset($this->tmp_data['tags'])) {
            return $this->tmp_data['tags'];
        } elseif (is_object($this->getUserObject()) and $this->getUserObject()->getCompany() > 0 and $this->getID() > 0) {
            return CompanyGenericTagMapListFactory::getStringByCompanyIDAndObjectTypeIDAndObjectID($this->getUserObject()->getCompany(), 320, $this->getID());
        }
        return false;
    }

    public function setObjectFromArray($data)
    {
        Debug::Arr($data, 'setObjectFromArray...', __FILE__, __LINE__, __METHOD__, 10);
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
                        case 'due_date':
                            $this->setDueDate(TTDate::parseDateTime($data['due_date']));
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
            $epoch == null;
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
            $epoch == null;
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

    public function setDueDate($epoch)
    {
        $epoch = (!is_int($epoch)) ? trim($epoch) : $epoch; //Dont trim integer values, as it changes them to strings.
        if ($epoch == '') {
            $epoch == null;
        }
        if ($epoch == null
            or
            $this->Validator->isDate('due_date',
                $epoch,
                TTi18n::gettext('Incorrect due date'))
        ) {
            $this->data['due_date'] = $epoch;

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
                        case 'type':
                        case 'term':
                        case 'severity':
                        case 'status':
                            $function = 'get' . $variable;
                            if (method_exists($this, $function)) {
                                $data[$variable] = Option::getByKey($this->$function(), $this->getOptions($variable));
                            }
                            break;
                        case 'user':
                            $data[$variable] = Misc::getFullName($this->getColumn('user_first_name'), null, $this->getColumn('user_last_name'), false, false);
                            break;
                        case 'reviewer_user':
                            $data[$variable] = Misc::getFullName($this->getColumn('reviewer_user_first_name'), null, $this->getColumn('reviewer_user_last_name'), false, false);
                            break;
                        case 'start_date':
                            $data['start_date'] = TTDate::getAPIDate('DATE', $this->getStartDate());
                            break;
                        case 'end_date':
                            $data['end_date'] = TTDate::getAPIDate('DATE', $this->getEndDate());
                            break;
                        case 'due_date':
                            $data['due_date'] = TTDate::getAPIDate('DATE', $this->getDueDate());
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

    //Support setting created_by, updated_by especially for importing data.
    //Make sure data is set based on the getVariableToFunctionMap order.

    public function addLog($log_action)
    {
        return TTLog::addEntry($this->getId(), $log_action, TTi18n::getText('Employee Review') . ' - ' . TTi18n::getText('Type') . ': ' . Option::getByKey($this->getType(), $this->getOptions('type')) . ', ' . TTi18n::getText('Status') . ': ' . Option::getByKey($this->getStatus(), $this->getOptions('status')), null, $this->getTable(), $this);
    }

    public function getType()
    {
        if (isset($this->data['type_id'])) {
            return (int)$this->data['type_id'];
        }

        return false;
    }

    public function getStatus()
    {
        if (isset($this->data['status_id'])) {
            return (int)$this->data['status_id'];
        }

        return false;
    }
}
