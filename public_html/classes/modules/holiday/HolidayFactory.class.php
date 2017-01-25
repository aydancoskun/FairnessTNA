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
class HolidayFactory extends Factory
{
    protected $table = 'holidays';
    protected $pk_sequence_name = 'holidays_id_seq'; //PK Sequence name

    protected $holiday_policy_obj = null;

    public function _getFactoryOptions($name, $parent = null)
    {
        $retval = null;
        switch ($name) {
            case 'columns':
                $retval = array(
                    '-1010-name' => TTi18n::gettext('Name'),
                    '-1020-date_stamp' => TTi18n::gettext('Date'),

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
                    'date_stamp',
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
            'holiday_policy_id' => 'HolidayPolicyID',
            'date_stamp' => 'DateStamp',
            'name' => 'Name',
            'deleted' => 'Deleted',
        );
        return $variable_function_map;
    }

    public function setHolidayPolicyID($id)
    {
        $id = trim($id);

        $hplf = TTnew('HolidayPolicyListFactory');

        if (
        $this->Validator->isResultSetWithRows('holiday_policy',
            $hplf->getByID($id),
            TTi18n::gettext('Holiday Policy is invalid')
        )
        ) {
            $this->data['holiday_policy_id'] = $id;

            return true;
        }

        return false;
    }

    public function setDateStamp($epoch)
    {
        $epoch = (!is_int($epoch)) ? trim($epoch) : $epoch; //Dont trim integer values, as it changes them to strings.

        if ($this->Validator->isDate('date_stamp',
                $epoch,
                TTi18n::gettext('Incorrect date'))
            and
            $this->Validator->isTrue('date_stamp',
                $this->isUniqueDateStamp($epoch),
                TTi18n::gettext('Date is already in use by another Holiday'))

        ) {
            if ($epoch > 0) {
                if ($this->getDateStamp() !== $epoch and $this->getOldDateStamp() != $this->getDateStamp()) {
                    Debug::Text(' Setting Old DateStamp... Current Old DateStamp: ' . (int)$this->getOldDateStamp() . ' Current DateStamp: ' . (int)$this->getDateStamp(), __FILE__, __LINE__, __METHOD__, 10);
                    $this->setOldDateStamp($this->getDateStamp());
                }

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

    public function isUniqueDateStamp($date_stamp)
    {
        $ph = array(
            'policy_id' => $this->getHolidayPolicyID(),
            'date_stamp' => $this->db->BindDate($date_stamp),
        );

        $query = 'select id from ' . $this->getTable() . '
					where holiday_policy_id = ?
						AND date_stamp = ?
						AND deleted=0';
        $date_stamp_id = $this->db->GetOne($query, $ph);
        Debug::Arr($date_stamp_id, 'Unique Date Stamp: ' . $date_stamp, __FILE__, __LINE__, __METHOD__, 10);

        if ($date_stamp_id === false) {
            return true;
        } else {
            if ($date_stamp_id == $this->getId()) {
                return true;
            }
        }

        return false;
    }

    public function getHolidayPolicyID()
    {
        if (isset($this->data['holiday_policy_id'])) {
            return (int)$this->data['holiday_policy_id'];
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

    public function getOldDateStamp()
    {
        if (isset($this->tmp_data['old_date_stamp'])) {
            return $this->tmp_data['old_date_stamp'];
        }

        return false;
    }

    public function setOldDateStamp($date_stamp)
    {
        Debug::Text(' Setting Old DateStamp: ' . TTDate::getDate('DATE', $date_stamp), __FILE__, __LINE__, __METHOD__, 10);
        $this->tmp_data['old_date_stamp'] = TTDate::getMiddleDayEpoch($date_stamp);

        return true;
    }

    public function getName()
    {
        if (isset($this->data['name'])) {
            return $this->data['name'];
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
                TTi18n::gettext('Name is already in use in this year, or within 30 days'))

        ) {
            $this->data['name'] = $name;

            return true;
        }

        return false;
    }

    public function isUniqueName($name)
    {
        //BindDate() causes a deprecated error if date_stamp is not set, so just return TRUE so we can throw a invalid date error elsewhere instead.
        //This also causes it so we can never have a invalid date and invalid name validation errors at the same time.
        if ($this->getDateStamp() == '') {
            return true;
        }

        $name = trim($name);
        if ($name == '') {
            return false;
        }

        //When a holiday gets moved back/forward due to falling on weekend, it can throw off the check to see if the holiday
        //appears in the same year. For example new years 01-Jan-2011 gets moved to 31-Dec-2010, its in the same year
        //as the previous New Years day or 01-Jan-2010, so this check fails.
        //
        //I think this can only happen with New Years, or other holidays that fall within two days of the new year.
        //So exclude the first three days of the year to allow for weekend adjustments.
        $ph = array(
            'policy_id' => $this->getHolidayPolicyID(),
            'name' => TTi18n::strtolower($name),
            'start_date1' => $this->db->BindDate((TTDate::getBeginYearEpoch($this->getDateStamp()) + (86400 * 3))),
            'end_date1' => $this->db->BindDate(TTDate::getEndYearEpoch($this->getDateStamp())),
            'start_date2' => $this->db->BindDate(($this->getDateStamp() - (86400 * 15))),
            'end_date2' => $this->db->BindDate(($this->getDateStamp() + (86400 * 15))),
        );

        $query = 'select id from ' . $this->getTable() . '
					where holiday_policy_id = ?
						AND lower(name) = ?
						AND
							(
								(
								date_stamp >= ?
								AND date_stamp <= ?
								)
							OR
								(
								date_stamp >= ?
								AND date_stamp <= ?
								)
							)
						AND deleted=0';
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

    public function isEligible($user_id, $ignore_after_eligibility = false)
    {
        if ($user_id == '') {
            return false;
        }

        $original_time_zone = TTDate::getTimeZone(); //Store current timezone so we can return to it after.

        $ulf = TTnew('UserListFactory');
        $ulf->getById($user_id);
        if ($ulf->getRecordCount() == 1) {
            $user_obj = $ulf->getCurrent();

            //Use CalculatePolicy to determine if they are eligible for the holiday or not.
            $flags = array(
                'meal' => false,
                'undertime_absence' => false,
                'break' => false,
                'holiday' => true,
                'schedule_absence' => false,
                'absence' => false,
                'regular' => false,
                'overtime' => false,
                'premium' => false,
                'accrual' => false,
                'exception' => false,

                //Exception options
                'exception_premature' => false, //Calculates premature exceptions
                'exception_future' => false, //Calculates exceptions in the future.

                //Calculate policies for future dates.
                'future_dates' => false, //Calculates dates in the future.
                'past_dates' => false, //Calculates dates in the past. This is only needed when Pay Formulas that use averaging are enabled?*
            );
            $cp = TTNew('CalculatePolicy');
            $cp->setFlag($flags);
            $cp->setUserObject($user_obj);
            $cp->getRequiredData($this->getDateStamp());

            $retval = $cp->isEligibleForHoliday($this->getDateStamp(), $this->getHolidayPolicyObject(), $ignore_after_eligibility);

            TTDate::setTimeZone($original_time_zone); //Store current timezone so we can return to it after.

            return $retval;
        }

        Debug::text('ERROR: Unable to get user object...', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    //ignore_after_eligibility is used when scheduling employees as absent on a holiday, since they haven't worked after the holiday
    // when the schedule is created, it will always fail.

    public function getHolidayPolicyObject()
    {
        return $this->getGenericObject('HolidayPolicyListFactory', $this->getHolidayPolicyID(), 'holiday_policy_obj');
    }

    public function Validate($ignore_warning = true)
    {
        if ($this->Validator->hasError('date_stamp') == false and $this->getDateStamp() == '') {
            $this->Validator->isTrue('date_stamp',
                false,
                TTi18n::gettext('Date is invalid'));
        }

        return true;
    }

    public function preSave()
    {
        return true;
    }

    public function postSave()
    {
        //ReCalculate Recurring Schedule records based on this holiday, assuming its in the future.
        if (TTDate::getMiddleDayEpoch($this->getDateStamp()) >= TTDate::getMiddleDayEpoch(time())) {
            Debug::text('Holiday is today or in the future, try to recalculate recurring schedules on this date: ' . TTDate::getDate('DATE', $this->getDateStamp()), __FILE__, __LINE__, __METHOD__, 10);

            $date_ranges = array();
            if (TTDate::getMiddleDayEpoch($this->getDateStamp()) != TTDate::getMiddleDayEpoch($this->getOldDateStamp())) {
                $date_ranges[] = array('start_date' => TTDate::getBeginDayEpoch($this->getOldDateStamp()), 'end_date' => TTDate::getEndDayEpoch($this->getOldDateStamp()));
            }
            $date_ranges[] = array('start_date' => TTDate::getBeginDayEpoch($this->getDateStamp()), 'end_date' => TTDate::getEndDayEpoch($this->getDateStamp()));

            foreach ($date_ranges as $date_range) {
                $start_date = $date_range['start_date'];
                $end_date = $date_range['end_date'];

                //Get existing recurring_schedule rows on the holiday day, so we can figure out which recurring_schedule_control records to recalculate.
                $recurring_schedule_control_ids = array();

                $rslf = TTnew('RecurringScheduleListFactory');
                $rslf->getByCompanyIDAndStartDateAndEndDateAndNoConflictingSchedule($this->getHolidayPolicyObject()->getCompany(), $start_date, $end_date);
                Debug::text('Recurring Schedule Record Count: ' . $rslf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
                if ($rslf->getRecordCount() > 0) {
                    foreach ($rslf as $rs_obj) {
                        if ($rs_obj->getRecurringScheduleControl() > 0) {
                            $recurring_schedule_control_ids[] = $rs_obj->getRecurringScheduleControl();
                        }
                    }
                }
                $recurring_schedule_control_ids = array_unique($recurring_schedule_control_ids);
                Debug::Arr($recurring_schedule_control_ids, 'Recurring Schedule Control IDs: ', __FILE__, __LINE__, __METHOD__, 10);

                if (count($recurring_schedule_control_ids) > 0) {
                    //
                    //**THIS IS DONE IN RecurringScheduleControlFactory, RecurringScheduleTemplateControlFactory, HolidayFactory postSave() as well.
                    //
                    $rsf = TTnew('RecurringScheduleFactory');
                    $rsf->StartTransaction();
                    $rsf->clearRecurringSchedulesFromRecurringScheduleControl($recurring_schedule_control_ids, $start_date, $end_date);
                    $rsf->addRecurringSchedulesFromRecurringScheduleControl($this->getHolidayPolicyObject()->getCompany(), $recurring_schedule_control_ids, $start_date, $end_date);
                    $rsf->CommitTransaction();
                }
            }
        } else {
            Debug::text('Holiday is not in the future...', __FILE__, __LINE__, __METHOD__, 10);
        }

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
                        case 'date_stamp':
                            if (method_exists($this, $function)) {
                                $this->$function(TTDate::parseDateTime($data[$key]));
                            }
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

    public function getObjectAsArray($include_columns = null)
    {
        $data = array();
        $variable_function_map = $this->getVariableToFunctionMap();

        if (is_array($variable_function_map)) {
            foreach ($variable_function_map as $variable => $function_stub) {
                if ($include_columns == null or (isset($include_columns[$variable]) and $include_columns[$variable] == true)) {
                    $function = 'get' . $function_stub;
                    switch ($variable) {
                        case 'date_stamp':
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
        return TTLog::addEntry($this->getId(), $log_action, TTi18n::getText('Holiday'), null, $this->getTable(), $this);
    }
}
