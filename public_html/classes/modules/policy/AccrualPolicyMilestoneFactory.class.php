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
 * @package Modules\Policy
 */
class AccrualPolicyMilestoneFactory extends Factory
{
    protected $table = 'accrual_policy_milestone';
    protected $pk_sequence_name = 'accrual_policy_milestone_id_seq'; //PK Sequence name

    protected $accrual_policy_obj = null;

    protected $length_of_service_multiplier = array(
        0 => 0,
        10 => 1,
        20 => 7,
        30 => 30.4167,
        40 => 365.25,
        50 => 0.04166666666666666667, // 1/24th of a day.
    );

    public function _getFactoryOptions($name, $parent = null)
    {
        $retval = null;
        switch ($name) {
            case 'length_of_service_unit':
                $retval = array(
                    10 => TTi18n::gettext('Day(s)'),
                    20 => TTi18n::gettext('Week(s)'),
                    30 => TTi18n::gettext('Month(s)'),
                    40 => TTi18n::gettext('Year(s)'),
                    50 => TTi18n::gettext('Hour(s)'),
                );
                break;
            case 'columns':
                $retval = array(
                    '-1010-length_of_service' => TTi18n::gettext('Length Of Service'),
                    '-1020-length_of_service_unit' => TTi18n::gettext('Units'),
                    '-1030-accrual_rate' => TTi18n::gettext('Accrual Rate'),
                    '-1050-maximum_time' => TTi18n::gettext('Maximum Time'),
                    '-1050-rollover_time' => TTi18n::gettext('Rollover Time'),

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
                    'length_of_service',
                    'length_of_service_unit',
                    'accrual_rate',
                    'maximum_time',
                    'rollover_time',
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
            'accrual_policy_id' => 'AccrualPolicy',
            'length_of_service_days' => 'LengthOfServiceDays',
            'length_of_service' => 'LengthOfService',
            'length_of_service_unit_id' => 'LengthOfServiceUnit',
            //'length_of_service_unit' => FALSE,
            'accrual_rate' => 'AccrualRate',
            'annual_maximum_time' => 'AnnualMaximumTime',
            'maximum_time' => 'MaximumTime',
            'rollover_time' => 'RolloverTime',
            'deleted' => 'Deleted',
        );
        return $variable_function_map;
    }

    public function getAccrualPolicyObject()
    {
        if (is_object($this->accrual_policy_obj)) {
            return $this->accrual_policy_obj;
        } else {
            $aplf = TTnew('AccrualPolicyListFactory');
            $aplf->getById($this->getAccrualPolicyID());
            if ($aplf->getRecordCount() > 0) {
                $this->accrual_policy_obj = $aplf->getCurrent();
                return $this->accrual_policy_obj;
            }

            return false;
        }
    }

    public function setAccrualPolicy($id)
    {
        $id = trim($id);

        $aplf = TTnew('AccrualPolicyListFactory');

        if ($this->Validator->isResultSetWithRows('accrual_policy',
            $aplf->getByID($id),
            TTi18n::gettext('Accrual Policy is invalid')
        )
        ) {
            $this->data['accrual_policy_id'] = $id;

            return true;
        }

        return false;
    }

    public function getLengthOfServiceDate($milestone_rollover_date)
    {
        switch ($this->getLengthOfServiceUnit()) {
            case 10: //Days
                $unit_str = 'Days';
                break;
            case 20: //Weeks
                $unit_str = 'Weeks';
                break;
            case 30: //Months
                $unit_str = 'Months';
                break;
            case 40: //Years
                $unit_str = 'Years';
                break;
        }

        if (isset($unit_str)) {
            $retval = TTDate::getBeginDayEpoch(strtotime('+' . $this->getLengthOfService() . ' ' . $unit_str, $milestone_rollover_date));
            Debug::text('MileStone Rollover Days based on Length Of Service: ' . TTDate::getDate('DATE+TIME', $retval), __FILE__, __LINE__, __METHOD__, 10);
            return $retval;
        }

        return false;
    }

    //If we just base LengthOfService on days, leap years and such can cause off-by-one errors.
    //So we need to determine the exact dates when the milestones rollover and base it on that instead.

    public function getLengthOfServiceUnit()
    {
        if (isset($this->data['length_of_service_unit_id'])) {
            return (int)$this->data['length_of_service_unit_id'];
        }

        return false;
    }

    public function getLengthOfService()
    {
        if (isset($this->data['length_of_service'])) {
            return (int)$this->data['length_of_service'];
        }

        return false;
    }

    public function getLengthOfServiceDays()
    {
        if (isset($this->data['length_of_service_days'])) {
            return (int)$this->data['length_of_service_days'];
        }

        return false;
    }

    public function setLengthOfService($int)
    {
        $int = (int)trim($int);

        Debug::text('bLength of Service: ' . $int, __FILE__, __LINE__, __METHOD__, 10);

        if ($int >= 0
            and
            $this->Validator->isFloat('length_of_service' . $this->getLabelID(),
                $int,
                TTi18n::gettext('Length of service is invalid'))
        ) {
            $this->data['length_of_service'] = $int;

            return true;
        }

        return false;
    }

    public function setLengthOfServiceUnit($value)
    {
        $value = trim($value);

        if ($this->Validator->inArrayKey('length_of_service_unit_id' . $this->getLabelID(),
            $value,
            TTi18n::gettext('Incorrect Length of service unit'),
            $this->getOptions('length_of_service_unit'))
        ) {
            $this->data['length_of_service_unit_id'] = $value;

            return true;
        }

        return false;
    }

    public function getAccrualRate()
    {
        if (isset($this->data['accrual_rate'])) {
            return $this->data['accrual_rate'];
        }

        return false;
    }

    public function setAccrualRate($int)
    {
        $int = trim($int);

        if ($int > 0
            and
            $this->Validator->isNumeric('accrual_rate' . $this->getLabelID(),
                $int,
                TTi18n::gettext('Incorrect Accrual Rate'))
        ) {
            $this->data['accrual_rate'] = $int;

            return true;
        }

        return false;
    }

    public function getAnnualMaximumTime()
    {
        if (isset($this->data['annual_maximum_time'])) {
            return (int)$this->data['annual_maximum_time'];
        }

        return false;
    }

    public function setAnnualMaximumTime($int)
    {
        $int = trim($int);

        if ($int == 0
            or
            $this->Validator->isNumeric('annual_maximum_time' . $this->getLabelID(),
                $int,
                TTi18n::gettext('Incorrect Accrual Annual Maximum'))
        ) {
            $this->data['annual_maximum_time'] = $int;

            return true;
        }

        return false;
    }

    public function getMaximumTime()
    {
        if (isset($this->data['maximum_time'])) {
            return (int)$this->data['maximum_time'];
        }

        return false;
    }

    public function setMaximumTime($int)
    {
        $int = trim($int);

        if ($int == 0
            or
            $this->Validator->isNumeric('maximum_time' . $this->getLabelID(),
                $int,
                TTi18n::gettext('Incorrect Maximum Balance'))
        ) {
            $this->data['maximum_time'] = $int;

            return true;
        }

        return false;
    }

    public function getRolloverTime()
    {
        if (isset($this->data['rollover_time'])) {
            return (int)$this->data['rollover_time'];
        }

        return false;
    }

    public function setRolloverTime($int)
    {
        $int = trim($int);

        if ($int == 0
            or
            $this->Validator->isNumeric('rollover_time' . $this->getLabelID(),
                $int,
                TTi18n::gettext('Incorrect Rollover Time'))
        ) {
            $this->data['rollover_time'] = $int;

            return true;
        }

        return false;
    }

    public function Validate()
    {
        if ($this->validate_only == false and $this->getAccrualPolicy() == false) {
            $this->Validator->isTRUE('accrual_policy_id' . $this->getLabelID(),
                false,
                TTi18n::gettext('Accrual Policy is invalid'));
        }

        return true;
    }

    public function getAccrualPolicy()
    {
        if (isset($this->data['accrual_policy_id'])) {
            return (int)$this->data['accrual_policy_id'];
        }

        return false;
    }

    public function preSave()
    {
        //Set Length of service in days.
        $this->setLengthOfServiceDays($this->getLengthOfService());

        return true;
    }

    public function setLengthOfServiceDays($int)
    {
        $int = (int)trim($int);

        Debug::text('aLength of Service Days: ' . $int, __FILE__, __LINE__, __METHOD__, 10);

        if ($int >= 0
            and
            $this->Validator->isFloat('length_of_service' . $this->getLabelID(),
                $int,
                TTi18n::gettext('Length of service is invalid'))
        ) {
            $this->data['length_of_service_days'] = bcmul($int, $this->length_of_service_multiplier[$this->getLengthOfServiceUnit()], 4);

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
                        /*
                        //This is not displayed anywhere that needs it in text rather then from the options.
                        case 'length_of_service_unit':
                            //$function = 'getLengthOfServiceUnit';
                            if ( method_exists( $this, $function ) ) {
                                $data[$variable] = Option::getByKey( $this->getLengthOfServiceUnit(), $this->getOptions( $variable ) );
                            }
                            break;
                        */
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
        return TTLog::addEntry($this->getAccrualPolicy(), $log_action, TTi18n::getText('Accrual Policy Milestone') . ' (ID: ' . $this->getID() . ')', null, $this->getTable(), $this);
    }
}
