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
 * @package Modules\Holiday
 */
class RecurringHolidayFactory extends Factory
{
    protected $table = 'recurring_holiday';
    protected $pk_sequence_name = 'recurring_holiday_id_seq'; //PK Sequence name

    protected $company_obj = null;


    public function _getFactoryOptions($name, $parent = null)
    {
        $retval = null;
        switch ($name) {
            case 'special_day':
                $retval = array(
                    0 => TTi18n::gettext('N/A'),
                    1 => TTi18n::gettext('Good Friday'),
                    5 => TTi18n::gettext('Easter Sunday'),
                    6 => TTi18n::gettext('Easter Monday'),
                );
                break;

            case 'type':
                $retval = array(
                    10 => TTi18n::gettext('Static'),
                    20 => TTi18n::gettext('Dynamic: Week Interval'),
                    30 => TTi18n::gettext('Dynamic: Pivot Day')
                );
                break;
            case 'week_interval':
                $retval = array(
                    1 => TTi18n::gettext('1st'),
                    2 => TTi18n::gettext('2nd'),
                    3 => TTi18n::gettext('3rd'),
                    4 => TTi18n::gettext('4th'),
                    5 => TTi18n::gettext('5th')
                );
                break;

            case 'pivot_day_direction':
                $retval = array(
                    10 => TTi18n::gettext('Before'),
                    20 => TTi18n::gettext('After'),
                    30 => TTi18n::gettext('On or Before'),
                    40 => TTi18n::gettext('On or After'),
                );
                break;
            case 'always_week_day':
                $retval = array(
                    //Adjust holiday to next weekday
                    0 => TTi18n::gettext('No'),
                    1 => TTi18n::gettext('Yes - Previous Week Day'),
                    2 => TTi18n::gettext('Yes - Next Week Day'),
                    3 => TTi18n::gettext('Yes - Closest Week Day'),
                );
                break;
            case 'columns':
                $retval = array(
                    '-1010-name' => TTi18n::gettext('Name'),
                    '-1010-type' => TTi18n::gettext('Type'),
                    '-1020-next_date' => TTi18n::gettext('Next Date'),

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
                    'name',
                    'next_date',
                    'updated_date',
                    'updated_by',
                );
                break;
            case 'unique_columns': //Columns that are unique, and disabled for mass editing.
                $retval = array(
                    'name',
                );
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
            'special_day' => 'SpecialDay',
            'type_id' => 'Type',
            'type' => false,
            'pivot_day_direction_id' => 'PivotDayDirection',
            'name' => 'Name',
            'week_interval' => 'WeekInterval',
            'day_of_week' => 'DayOfWeek',
            'day_of_month' => 'DayOfMonth',
            'month_int' => 'Month',
            'always_week_day_id' => 'AlwaysOnWeekDay',
            'next_date' => 'NextDate',
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

    public function setSpecialDay($value)
    {
        $value = trim($value);

        if ($this->Validator->inArrayKey('special_day',
            $value,
            TTi18n::gettext('Incorrect Special Day'),
            $this->getOptions('special_day'))
        ) {
            $this->data['special_day'] = $value;

            return true;
        }

        return false;
    }

    public function setType($value)
    {
        $value = trim($value);

        if ($this->Validator->inArrayKey('type',
            $value,
            TTi18n::gettext('Incorrect Type'),
            $this->getOptions('type'))
        ) {
            $this->data['type_id'] = $value;

            return true;
        }

        return false;
    }

    public function setPivotDayDirection($value)
    {
        $value = trim($value);

        if ($value == 0
            or
            $this->Validator->inArrayKey('pivot_day_direction',
                $value,
                TTi18n::gettext('Incorrect Pivot Day Direction'),
                $this->getOptions('pivot_day_direction'))
        ) {
            $this->data['pivot_day_direction_id'] = $value;

            return true;
        }

        return false;
    }

    public function setName($name)
    {
        $name = trim($name);
        if ($this->Validator->isLength('name',
                $name,
                TTi18n::gettext('Name is invalid'),
                2, 50)
            and
            $this->Validator->isTrue('name',
                $this->isUniqueName($name),
                TTi18n::gettext('Name is already in use'))

        ) {
            $this->data['name'] = $name;

            return true;
        }

        return false;
    }

    public function isUniqueName($name)
    {
        $name = trim($name);
        if ($name == '') {
            return false;
        }

        $ph = array(
            'company_id' => (int)$this->getCompany(),
            'name' => TTi18n::strtolower($name),
        );

        $query = 'select id from ' . $this->getTable() . ' where company_id = ? AND lower(name) = ? AND deleted=0';
        $name_id = $this->db->GetOne($query, $ph);
        Debug::Arr($name_id, 'Unique Name: ' . $name, __FILE__, __LINE__, __METHOD__, 10);

        if ($name_id === false) {
            return true;
        } else {
            if ($name_id == $this->getId()) {
                return true;
            }
        }

        return false;
    }

    public function setWeekInterval($int)
    {
        $int = trim($int);

        if (empty($int)) {
            $int = 0;
        }

        if ($this->Validator->isNumeric('week_interval',
            $int,
            TTi18n::gettext('Incorrect Week Interval'))
        ) {
            $this->data['week_interval'] = $int;

            return true;
        }

        return false;
    }

    public function setDayOfWeek($int)
    {
        $int = trim($int);

        if ($int == '') {
            $int = 0;
        }

        if ($this->Validator->isNumeric('day_of_week',
            $int,
            TTi18n::gettext('Incorrect Day Of Week'))
        ) {
            $this->data['day_of_week'] = $int;

            return true;
        }

        return false;
    }

    public function setDayOfMonth($int)
    {
        $int = trim($int);

        if (empty($int)) {
            $int = 0;
        }

        if ($this->Validator->isNumeric('day_of_month',
            $int,
            TTi18n::gettext('Incorrect Day Of Month'))
        ) {
            $this->data['day_of_month'] = $int;

            return true;
        }

        return false;
    }

    public function setMonth($int)
    {
        $int = trim($int);

        if (empty($int)) {
            $int = 0;
        }

        if ($this->Validator->isNumeric('month',
            $int,
            TTi18n::gettext('Incorrect Month'))
        ) {
            $this->data['month_int'] = $int;

            return true;
        }

        return false;
    }

    public function setAlwaysOnWeekDay($int)
    {
        $int = (int)$int;

        if ($this->Validator->inArrayKey('always_week_day_id',
            $int,
            TTi18n::gettext('Incorrect always on week day adjustment'),
            $this->getOptions('always_week_day'))
        ) {
            $this->data['always_week_day_id'] = $int;

            return true;
        }

        return false;
    }

    public function getNextDate($epoch = false)
    {
        if ($epoch == '') {
            $epoch = TTDate::getTime();
        }

        if ($this->getSpecialDay() == 1 or $this->getSpecialDay() == 5 or $this->getSpecialDay() == 6) {
            Debug::text('Easter Sunday Date...', __FILE__, __LINE__, __METHOD__, 10);

            //Use easter_days() instead, as easter_date returns incorrect values for some timezones/years (2010 and US/Eastern on Windows)
            //$easter_epoch = easter_date(date('Y', $epoch));
            //$easter_epoch = mktime( 12, 0, 0, 3, ( 21 + easter_days( date('Y', $epoch) ) ), date('Y', $epoch) );
            $easter_epoch = mktime(12, 0, 0, 3, (21 + TTDate::getEasterDays(date('Y', $epoch))), date('Y', $epoch));

            //Fix "cross-year" bug.
            if ($easter_epoch < $epoch) {
                //$easter_epoch = easter_date(date('Y', $epoch)+1);
                //$easter_epoch = mktime( 12, 0, 0, 3, ( 21 + easter_days( (date('Y', $epoch) + 1) ) ), ( date('Y', $epoch) + 1 ) );
                $easter_epoch = mktime(12, 0, 0, 3, (21 + TTDate::getEasterDays((date('Y', $epoch) + 1))), (date('Y', $epoch) + 1));
            }

            if ($this->getSpecialDay() == 1) {
                Debug::text('Good Friday Date...', __FILE__, __LINE__, __METHOD__, 10);
                //$holiday_epoch = mktime(12, 0, 0, date('n', $easter_epoch), date('j', $easter_epoch) - 2, date('Y', $easter_epoch));
                $holiday_epoch = ($easter_epoch - (2 * 86400));
            } elseif ($this->getSpecialDay() == 6) {
                Debug::text('Easter Monday Date...', __FILE__, __LINE__, __METHOD__, 10);
                $holiday_epoch = ($easter_epoch + 86400);
            } else {
                $holiday_epoch = $easter_epoch;
            }
        } else {
            if ($this->getType() == 10) { //Static
                Debug::text('Static Date...', __FILE__, __LINE__, __METHOD__, 10);
                //Static date
                $holiday_epoch = mktime(12, 0, 0, $this->getMonth(), $this->getDayOfMonth(), date('Y', $epoch));
                if ($holiday_epoch < $epoch) {
                    $holiday_epoch = mktime(12, 0, 0, $this->getMonth(), $this->getDayOfMonth(), (date('Y', $epoch) + 1));
                }
            } elseif ($this->getType() == 20) { //Dynamic - Week Interval
                Debug::text('Dynamic - Week Interval... Current Month: ' . TTDate::getMonth($epoch) . ' Holiday Month: ' . $this->getMonth(), __FILE__, __LINE__, __METHOD__, 10);
                //Dynamic
                $start_month_epoch = TTDate::getBeginMonthEpoch($epoch);
                $end_month_epoch = mktime(12, 0, 0, ($this->getMonth() + 1), 1, (date('Y', $epoch) + 1));

                $tmp_holiday_epoch = false;

                Debug::text('Start Epoch: ' . TTDate::getDate('DATE+TIME', $start_month_epoch) . ' End Epoch: ' . TTDate::getDate('DATE+TIME', $end_month_epoch) . ' Current Epoch: ' . TTDate::getDate('DATE+TIME', $epoch), __FILE__, __LINE__, __METHOD__, 10);
                //Get all day of weeks in the month. Determine which is less or greater then day.
                $day_of_week_dates = array();
                $week_interval = 0;
                for ($i = $start_month_epoch; $i <= $end_month_epoch; $i += 86400) {
                    if (TTDate::getMonth($i) == $this->getMonth()) {
                        $day_of_week = TTDate::getDayOfWeek($i);
                        //Debug::text('I: '. $i .'('.TTDate::getDate('DATE+TIME', $i).') Current Day Of Week: '. $day_of_week .' Looking for Day Of Week: '. $this->getDayOfWeek(), __FILE__, __LINE__, __METHOD__, 10);

                        if ($day_of_week == abs($this->getDayOfWeek())) {
                            $day_of_week_dates[] = date('j', $i);
                            Debug::text('I: ' . $i . ' Day Of Month: ' . date('j', $i) . ' Week Interval: ' . $week_interval, __FILE__, __LINE__, __METHOD__, 10);

                            $week_interval++;
                        }

                        if ($week_interval >= $this->getWeekInterval()) {
                            $tmp_holiday_epoch = mktime(12, 0, 0, $this->getMonth(), $day_of_week_dates[($this->getWeekInterval() - 1)], date('Y', $i));

                            //Make sure we keep processing until the holiday comes AFTER todays date.
                            if ($tmp_holiday_epoch > $epoch) {
                                break;
                            }
                        }
                    } else {
                        //Outside the month we need to be in, so reset all other settings.
                        $week_interval = 0;
                        $day_of_week_dates = array();
                    }
                }

                $holiday_epoch = $tmp_holiday_epoch;
            } elseif ($this->getType() == 30) { //Dynamic - Pivot Day
                Debug::text('Dynamic - Pivot Date...', __FILE__, __LINE__, __METHOD__, 10);
                //Dynamic
                if (TTDate::getMonth($epoch) > $this->getMonth()) {
                    $year_modifier = 1;
                } else {
                    $year_modifier = 0;
                }

                $start_epoch = mktime(12, 0, 0, $this->getMonth(), $this->getDayOfMonth(), (date('Y', $epoch) + $year_modifier));

                $holiday_epoch = $start_epoch;

                $x = 0;
                $x_max = 100;

                if ($this->getPivotDayDirection() == 10 or $this->getPivotDayDirection() == 30) {
                    $direction_multiplier = -1;
                } else {
                    $direction_multiplier = 1;
                }

                $adjustment = (86400 * $direction_multiplier);    // Adjust by 1 day before or after.

                if ($this->getPivotDayDirection() == 10 or $this->getPivotDayDirection() == 20) {
                    $holiday_epoch += $adjustment;
                }

                while ($this->getDayOfWeek() != TTDate::getDayOfWeek($holiday_epoch) and $x < $x_max) {
                    Debug::text('X: ' . $x . ' aTrying...' . TTDate::getDate('DATE+TIME', $holiday_epoch), __FILE__, __LINE__, __METHOD__, 10);
                    $holiday_epoch += $adjustment;

                    $x++;
                }
            }
        }

        $holiday_epoch = TTDate::getNearestWeekDay($holiday_epoch, $this->getAlwaysOnWeekDay());

        Debug::text('Next Date for: ' . $this->getName() . ' is: ' . TTDate::getDate('DATE+TIME', $holiday_epoch), __FILE__, __LINE__, __METHOD__, 10);

        return $holiday_epoch;
    }

    public function getSpecialDay()
    {
        if (isset($this->data['special_day'])) {
            return $this->data['special_day'];
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

    public function getMonth()
    {
        if (isset($this->data['month_int'])) {
            return (int)$this->data['month_int'];
        }

        return false;
    }

    public function getDayOfMonth()
    {
        if (isset($this->data['day_of_month'])) {
            return (int)$this->data['day_of_month'];
        }

        return false;
    }

    public function getDayOfWeek()
    {
        if (isset($this->data['day_of_week'])) {
            return (int)$this->data['day_of_week'];
        }

        return false;
    }

    public function getWeekInterval()
    {
        if (isset($this->data['week_interval'])) {
            return (int)$this->data['week_interval'];
        }

        return false;
    }

    public function getPivotDayDirection()
    {
        if (isset($this->data['pivot_day_direction_id'])) {
            return (int)$this->data['pivot_day_direction_id'];
        }

        return false;
    }

    public function getAlwaysOnWeekDay()
    {
        if (isset($this->data['always_week_day_id'])) {
            return (int)$this->data['always_week_day_id'];
        }
        return false;
    }

    public function getName()
    {
        if (isset($this->data['name'])) {
            return $this->data['name'];
        }

        return false;
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
        return true;
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
                        case 'status':
                            $function = 'get' . $variable;
                            if (method_exists($this, $function)) {
                                $data[$variable] = Option::getByKey($this->$function(), $this->getOptions($variable));
                            }
                            break;
                        case 'next_date':
                            if (method_exists($this, $function)) {
                                $data[$variable] = TTDate::getAPIDate('DATE', $this->$function());
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
        return TTLog::addEntry($this->getId(), $log_action, TTi18n::getText('Recurring Holiday'), null, $this->getTable(), $this);
    }
}
