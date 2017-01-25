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
 * @package Modules\Schedule
 */
class RecurringScheduleControlFactory extends Factory
{
    protected $table = 'recurring_schedule_control';
    protected $pk_sequence_name = 'recurring_schedule_control_id_seq'; //PK Sequence name

    protected $company_obj = null;
    protected $recurring_schedule_template_obj = null;

    public function _getFactoryOptions($name, $parent = null)
    {
        $retval = null;
        switch ($name) {
            case 'columns':
                $retval = array(
                    '-1010-first_name' => TTi18n::gettext('First Name'),
                    '-1020-last_name' => TTi18n::gettext('Last Name'),

                    '-1030-recurring_schedule_template_control' => TTi18n::gettext('Template'),
                    '-1040-recurring_schedule_template_control_description' => TTi18n::gettext('Description'),
                    '-1050-start_date' => TTi18n::gettext('Start Date'),
                    '-1060-end_date' => TTi18n::gettext('End Date'),
                    '-1065-display_weeks' => TTi18n::gettext('Display Weeks'),
                    '-1070-auto_fill' => TTi18n::gettext('Auto-Punch'),

                    '-1090-title' => TTi18n::gettext('Title'),
                    '-1099-user_group' => TTi18n::gettext('Group'),
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
                    'recurring_schedule_template_control',
                    'recurring_schedule_template_control_description',
                    'start_date',
                    'end_date',
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
            'user_id' => false,
            'first_name' => false,
            'last_name' => false,
            'default_branch' => false,
            'default_department' => false,
            'user_group' => false,
            'title' => false,
            'recurring_schedule_template_control_id' => 'RecurringScheduleTemplateControl',
            'recurring_schedule_template_control' => false,
            'recurring_schedule_template_control_description' => false,
            'start_week' => 'StartWeek',
            'start_date' => 'StartDate',
            'end_date' => 'EndDate',
            'display_weeks' => 'DisplayWeeks',
            'auto_fill' => 'AutoFill',
            'user' => 'User',
            'deleted' => 'Deleted',
        );
        return $variable_function_map;
    }

    public function getCompanyObject()
    {
        return $this->getGenericObject('CompanyListFactory', $this->getCompany(), 'company_obj');
    }

    public function getCompany()
    {
        if (isset($this->data['company_id'])) {
            return (int)$this->data['company_id'];
        }

        return false;
    }

    public function getRecurringScheduleTemplateControlObject()
    {
        return $this->getGenericObject('RecurringScheduleTemplateControlListFactory', $this->getRecurringScheduleTemplateControl(), 'recurring_schedule_template_control_obj');
    }

    public function getRecurringScheduleTemplateControl()
    {
        if (isset($this->data['recurring_schedule_template_control_id'])) {
            return (int)$this->data['recurring_schedule_template_control_id'];
        }

        return false;
    }

    public function setCompany($id)
    {
        $id = trim($id);

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

    public function setRecurringScheduleTemplateControl($id)
    {
        $id = trim($id);

        $rstclf = TTnew('RecurringScheduleTemplateControlListFactory');

        if ($this->Validator->isResultSetWithRows('recurring_schedule_template_control_id',
            $rstclf->getByID($id),
            TTi18n::gettext('Recurring Schedule Template is invalid')
        )
        ) {
            $this->data['recurring_schedule_template_control_id'] = $id;

            return true;
        }

        return false;
    }

    public function getStartDate($raw = false)
    {
        if (isset($this->data['start_date'])) {
            if ($raw === true) {
                return $this->data['start_date'];
            } else {
                return TTDate::strtotime($this->data['start_date']);
            }
        }

        return false;
    }

    public function setStartDate($epoch)
    {
        $epoch = (!is_int($epoch)) ? trim($epoch) : $epoch; //Dont trim integer values, as it changes them to strings.

        if ($this->Validator->isDate('start_date',
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

    public function setDisplayWeeks($int)
    {
        $int = trim($int);

        if (
            $this->Validator->isGreaterThan('display_weeks',
                $int,
                TTi18n::gettext('Display Weeks must be at least 1'),
                1)
            and
            $this->Validator->isLessThan('display_weeks',
                $int,
                TTi18n::gettext('Display Weeks cannot exceed 78'),
                78)
            and
            $this->Validator->isNumeric('display_weeks',
                $int,
                TTi18n::gettext('Display weeks is invalid'))
        ) {
            $this->data['display_weeks'] = $int;

            return true;
        }

        return false;
    }

    public function getAutoFill()
    {
        if (isset($this->data['auto_fill'])) {
            return $this->fromBool($this->data['auto_fill']);
        }

        return false;
    }

    public function setAutoFill($bool)
    {
        $this->data['auto_fill'] = $this->toBool($bool);

        return true;
    }

    public function setUser($ids)
    {
        if (!is_array($ids)) {
            global $current_user;
            if ((int)$ids == 0) {
                $this->Validator->isTrue('user',
                    false,
                    TTi18n::gettext('Please select at least one employee'));
                return false;
            } else {
                $ids = array($ids);
            }
        }

        if (is_array($ids) and count($ids) > 0) {
            $tmp_ids = array();
            if (!$this->isNew()) {
                //If needed, delete mappings first.
                $rsulf = TTnew('RecurringScheduleUserListFactory');
                $rsulf->getByRecurringScheduleControlId($this->getId());
                foreach ($rsulf as $obj) {
                    $id = $obj->getUser();
                    Debug::text('Recurring Schedule ID: ' . $obj->getRecurringScheduleControl() . ' ID: ' . $id, __FILE__, __LINE__, __METHOD__, 10);

                    //Delete users that are not selected.
                    if (!in_array($id, $ids)) {
                        Debug::text('Deleting: ' . $id, __FILE__, __LINE__, __METHOD__, 10);
                        $obj->Delete();
                    } else {
                        //Save ID's that need to be updated.
                        Debug::text('NOT Deleting: ' . $id, __FILE__, __LINE__, __METHOD__, 10);
                        $tmp_ids[] = $id;
                    }
                }
                unset($id, $obj);
            }

            //Insert new mappings.
            $ulf = TTnew('UserListFactory');

            foreach ($ids as $id) {
                if ($id >= 0 and isset($tmp_ids) and !in_array($id, $tmp_ids)) { //-1 is used as "NONE", so ignore it here.
                    //Handle OPEN shifts.
                    $full_name = null;
                    if ($id == 0) {
                        $full_name = TTi18n::getText('OPEN');
                    } else {
                        $ulf->getById($id);
                        if ($ulf->getRecordCount() > 0) {
                            $full_name = $ulf->getCurrent()->getFullName();
                        }
                    }

                    $rsuf = TTnew('RecurringScheduleUserFactory');
                    $rsuf->setRecurringScheduleControl($this->getId());
                    $rsuf->setUser($id);

                    if ($this->Validator->isTrue('user',
                        $rsuf->Validator->isValid(),
                        TTi18n::gettext('Selected employee is invalid') . ' (' . $full_name . ')')
                    ) {
                        $rsuf->save();
                    }
                    unset($full_name);
                } elseif ($id == -1) { //Make sure -1 isn't the only selected option.
                    $this->Validator->isTrue('user',
                        false,
                        TTi18n::gettext('Please select at least one employee'));
                }
            }

            return true;
        }

        Debug::text('No User IDs to set.', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function reMapWeek($current, $start, $max)
    {
        return (((($current - 1) + $max - ($start - 1)) % $max) + 1);
    }

    public function ReMapWeeks($week_arr)
    {
        //We should be able to re-map weeks with simple math:
        //For example:
        //	Start Week = 3, Max Week = 5
        // If template week is less then start week, we add the start week.
        // If template week is greater or equal then start week, we minus the 1-start_week.
        //	Template Week 1 -- 1 + 3(start week)   = ReMapped Week 4
        //	Template Week 2 -- 2 + 3			   = ReMapped Week 5
        //	Template Week 3 -- 3 - 2(start week-1) = ReMapped Week 1
        //	Template Week 4 -- 4 - 2			   = ReMapped Week 2
        //	Template Week 5 -- 5 - 2			   = ReMapped Week 3

        //Remaps weeks based on start week
        Debug::text('Start Week: ' . $this->getStartWeek(), __FILE__, __LINE__, __METHOD__, 10);

        if ($this->getStartWeek() > 1 and in_array($this->getStartWeek(), $week_arr)) {
            Debug::text('Weeks DO need reordering: ', __FILE__, __LINE__, __METHOD__, 10);
            $max_week = count($week_arr);

            $i = 1;
            $arr = array();
            foreach ($week_arr as $key => $val) {
                $new_val = ($key - ($this->getStartWeek() - 1));

                if ($key < $this->getStartWeek()) {
                    $new_val = ($new_val + $max_week);
                }

                $arr[$new_val] = $key;

                $i++;
            }
            unset($val); //code standards
            //var_dump($arr);
            return $arr;
        }

        Debug::text('Weeks do not need reordering: ', __FILE__, __LINE__, __METHOD__, 10);

        return $week_arr;
    }

    public function getStartWeek()
    {
        if (isset($this->data['start_week'])) {
            return (int)$this->data['start_week'];
        }

        return false;
    }

    public function preSave()
    {
        if ($this->getStartWeek() < 1) {
            $this->setStartWeek(1);
        }

        return true;
    }

    public function setStartWeek($int)
    {
        $int = trim($int);

        if (
            $this->Validator->isGreaterThan('start_week',
                $int,
                TTi18n::gettext('Start week must be at least 1'),
                1)
            and
            $this->Validator->isNumeric('start_week',
                $int,
                TTi18n::gettext('Start week is invalid'))
        ) {
            $this->data['start_week'] = $int;

            return true;
        }

        return false;
    }

    public function postSave()
    {
        //
        //**THIS IS DONE IN RecurringScheduleControlFactory, RecurringScheduleTemplateControlFactory, HolidayFactory postSave() as well.
        //

        //Handle generating recurring schedule rows, so they are as real-time as possible.
        //In case an issue arises (like holiday not appearing or something) and they need to recalculate schedules, always start from the prior week.
        //  so we at least have a chance of recalculating retroactively to some degree.
        $current_epoch = TTDate::getBeginWeekEpoch(TTDate::getBeginWeekEpoch(time()) - 86400);

        $rsf = TTnew('RecurringScheduleFactory');
        $rsf->StartTransaction();
        $rsf->clearRecurringSchedulesFromRecurringScheduleControl($this->getID(), ($current_epoch - (86400 * 720)), ($current_epoch + (86400 * 720)));
        if ($this->getDeleted() == false) {
            //FIXME: Put a cap on this perhaps, as 3mths into the future so we don't spend a ton of time doing this
            //if the user puts sets it to display 1-2yrs in the future. Leave creating the rest of the rows to the maintenance job?
            //Since things may change we will want to delete all schedules with each change, but only add back in X weeks at most unless from a maintenance job.
            $maximum_end_date = (TTDate::getBeginWeekEpoch($current_epoch) + ($this->getDisplayWeeks() * (86400 * 7)));
            if ($this->getEndDate() != '' and $maximum_end_date > $this->getEndDate()) {
                $maximum_end_date = $this->getEndDate();
            }
            Debug::text('Recurring Schedule ID: ' . $this->getID() . ' Maximum End Date: ' . TTDate::getDate('DATE+TIME', $maximum_end_date), __FILE__, __LINE__, __METHOD__, 10);

            $rsf->addRecurringSchedulesFromRecurringScheduleControl($this->getCompany(), $this->getID(), $current_epoch, $maximum_end_date);
        }
        $rsf->CommitTransaction();

        return true;
    }

    public function getDisplayWeeks()
    {
        if (isset($this->data['display_weeks'])) {
            return (int)$this->data['display_weeks'];
        }

        return false;
    }

    public function getEndDate($raw = false)
    {
        if (isset($this->data['end_date'])) {
            if ($raw === true) {
                return $this->data['end_date'];
            } else {
                return TTDate::strtotime($this->data['end_date']);
            }
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
                        case 'end_date':
                            $this->$function(TTDate::parseDateTime($data[$key]));
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

    public function getObjectAsArray($include_columns = null, $permission_children_ids = false)
    {
        //
        //When using the Recurring Schedule view, it returns the user list for every single row and runs out of memory at about 1000 rows.
        //Need to make the 'user' column explicitly defined instead perhaps?
        //
        $variable_function_map = $this->getVariableToFunctionMap();
        $data = array();
        if (is_array($variable_function_map)) {
            foreach ($variable_function_map as $variable => $function_stub) {
                if ($include_columns == null or (isset($include_columns[$variable]) and $include_columns[$variable] == true)) {
                    $function = 'get' . $function_stub;
                    switch ($variable) {
                        case 'first_name':
                        case 'last_name':
                            $data[$variable] = ($this->getColumn($variable) == '') ? TTi18n::getText('OPEN') : $this->getColumn($variable);
                            break;
                        case 'title':
                        case 'user_group':
                        case 'default_branch':
                        case 'default_department':
                        case 'recurring_schedule_template_control':
                        case 'recurring_schedule_template_control_description':
                        case 'user_id':
                            $data[$variable] = $this->getColumn($variable);
                            break;
                        case 'start_date':
                        case 'end_date':
                            $data[$variable] = TTDate::getAPIDate('DATE', TTDate::strtotime($this->$function()));
                            break;
                        default:
                            if (method_exists($this, $function)) {
                                $data[$variable] = $this->$function();
                            }
                            break;
                    }
                }
            }

            //Handle expanded and non-expanded mode. In non-expanded mode we need to get all the users
            //so we can check is_owner/is_child permissions on them.
            if ($this->getColumn('user_id') !== false) {
                $user_ids = $this->getColumn('user_id');
            } else {
                $user_ids = $this->getUser();
            }

            $this->getPermissionColumns($data, $user_ids, $this->getCreatedBy(), $permission_children_ids, $include_columns);
            //$this->getPermissionColumns( $data, $this->getColumn('user_id'), $this->getCreatedBy(), $permission_children_ids, $include_columns );
            $this->getCreatedAndUpdatedColumns($data, $include_columns);
        }

        return $data;
    }

    public function getUser()
    {
        $rsulf = TTnew('RecurringScheduleUserListFactory');
        $rsulf->getByRecurringScheduleControlId($this->getId());
        $list = array();
        foreach ($rsulf as $obj) {
            $list[] = $obj->getUser();
        }

        if (empty($list) == false) {
            return $list;
        }

        return false;
    }

    public function addLog($log_action)
    {
        return TTLog::addEntry($this->getId(), $log_action, TTi18n::getText('Recurring Schedule'), null, $this->getTable(), $this);
    }
}
