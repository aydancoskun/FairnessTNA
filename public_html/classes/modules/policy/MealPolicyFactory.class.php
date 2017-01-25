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
 * @package Modules\Policy
 */
class MealPolicyFactory extends Factory
{
    protected $table = 'meal_policy';
    protected $pk_sequence_name = 'meal_policy_id_seq'; //PK Sequence name

    protected $company_obj = null;
    protected $pay_code_obj = null;


    public function _getFactoryOptions($name, $parent = null)
    {
        $retval = null;
        switch ($name) {
            case 'type':
                $retval = array(
                    10 => TTi18n::gettext('Auto-Deduct'),
                    15 => TTi18n::gettext('Auto-Add'),
                    20 => TTi18n::gettext('Normal')
                );
                break;
            case 'auto_detect_type':
                $retval = array(
                    10 => TTi18n::gettext('Time Window'),
                    20 => TTi18n::gettext('Punch Time'),
                );
                break;
            case 'columns':
                $retval = array(
                    '-1010-type' => TTi18n::gettext('Type'),
                    '-1020-name' => TTi18n::gettext('Name'),
                    '-1025-description' => TTi18n::gettext('Description'),
                    '-1030-amount' => TTi18n::gettext('Meal Time'),
                    '-1040-trigger_time' => TTi18n::gettext('Active After'),

                    '-1050-auto_detect_type' => TTi18n::gettext('Auto Detect Meals By'),
                    //'-1060-start_window' => TTi18n::gettext('Start Window'),
                    //'-1070-window_length' => TTi18n::gettext('Window Length'),
                    //'-1080-minimum_punch_time' => TTi18n::gettext('Minimum Punch Time'),
                    //'-1090-maximum_punch_time' => TTi18n::gettext('Maximum Punch Time'),

                    '-1100-include_lunch_punch_time' => TTi18n::gettext('Include Lunch Punch'),

                    '-1900-in_use' => TTi18n::gettext('In Use'),

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
                    'description',
                    'type',
                    'amount',
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
            'type_id' => 'Type',
            'type' => false,
            'name' => 'Name',
            'description' => 'Description',
            'trigger_time' => 'TriggerTime',
            'amount' => 'Amount',
            'auto_detect_type_id' => 'AutoDetectType',
            'auto_detect_type' => false,
            'start_window' => 'StartWindow',
            'window_length' => 'WindowLength',
            'minimum_punch_time' => 'MinimumPunchTime',
            'maximum_punch_time' => 'MaximumPunchTime',
            'include_lunch_punch_time' => 'IncludeLunchPunchTime',

            'pay_code_id' => 'PayCode',
            'pay_code' => false,
            'pay_formula_policy_id' => 'PayFormulaPolicy',
            'pay_formula_policy' => false,

            'in_use' => false,
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

    public function setName($name)
    {
        $name = trim($name);
        if ($this->Validator->isLength('name',
                $name,
                TTi18n::gettext('Name is too short or too long'),
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
        $id = $this->db->GetOne($query, $ph);
        Debug::Arr($id, 'Unique: ' . $name, __FILE__, __LINE__, __METHOD__, 10);

        if ($id === false) {
            return true;
        } else {
            if ($id == $this->getId()) {
                return true;
            }
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
            or $this->Validator->isLength('description',
                $description,
                TTi18n::gettext('Description is invalid'),
                1, 250)
        ) {
            $this->data['description'] = $description;

            return true;
        }

        return false;
    }

    public function getTriggerTime()
    {
        if (isset($this->data['trigger_time'])) {
            return (int)$this->data['trigger_time'];
        }

        return false;
    }

    public function setTriggerTime($int)
    {
        $int = trim($int);

        if (empty($int)) {
            $int = 0;
        }

        if ($this->Validator->isNumeric('trigger_time',
            $int,
            TTi18n::gettext('Incorrect Trigger Time'))
        ) {
            $this->data['trigger_time'] = $int;

            return true;
        }

        return false;
    }

    public function getAmount()
    {
        if (isset($this->data['amount'])) {
            return $this->data['amount'];
        }

        return false;
    }

    public function setAmount($value)
    {
        $value = trim($value);

        if ($this->Validator->isNumeric('amount',
            $value,
            TTi18n::gettext('Incorrect Deduction Amount'))
        ) {
            $this->data['amount'] = $value;

            return true;
        }

        return false;
    }

    public function getStartWindow()
    {
        if (isset($this->data['start_window'])) {
            return $this->data['start_window'];
        }

        return false;
    }

    public function setStartWindow($value)
    {
        $value = (int)trim($value);

        if ($value == 0
            or
            $this->Validator->isNumeric('start_window',
                $value,
                TTi18n::gettext('Incorrect Start Window'))
        ) {
            $this->data['start_window'] = $value;

            return true;
        }

        return false;
    }

    public function getWindowLength()
    {
        if (isset($this->data['window_length'])) {
            return $this->data['window_length'];
        }

        return false;
    }

    public function setWindowLength($value)
    {
        $value = (int)trim($value);

        if ($value == 0
            or
            $this->Validator->isNumeric('window_length',
                $value,
                TTi18n::gettext('Incorrect Window Length'))
        ) {
            $this->data['window_length'] = $value;

            return true;
        }

        return false;
    }

    public function getMinimumPunchTime()
    {
        if (isset($this->data['minimum_punch_time'])) {
            return $this->data['minimum_punch_time'];
        }

        return false;
    }

    public function setMinimumPunchTime($value)
    {
        $value = (int)trim($value);

        if ($value == 0
            or
            $this->Validator->isNumeric('minimum_punch_time',
                $value,
                TTi18n::gettext('Incorrect Minimum Punch Time'))
        ) {
            $this->data['minimum_punch_time'] = $value;

            return true;
        }

        return false;
    }

    public function getMaximumPunchTime()
    {
        if (isset($this->data['maximum_punch_time'])) {
            return $this->data['maximum_punch_time'];
        }

        return false;
    }

    public function setMaximumPunchTime($value)
    {
        $value = (int)trim($value);

        if ($value == 0
            or
            $this->Validator->isNumeric('maximum_punch_time',
                $value,
                TTi18n::gettext('Incorrect Maximum Punch Time'))
        ) {
            $this->data['maximum_punch_time'] = $value;

            return true;
        }

        return false;
    }

    public function getIncludeLunchPunchTime()
    {
        if (isset($this->data['include_lunch_punch_time'])) {
            return $this->fromBool($this->data['include_lunch_punch_time']);
        }

        return false;
    }

    public function setIncludeLunchPunchTime($bool)
    {
        $this->data['include_lunch_punch_time'] = $this->toBool($bool);

        return true;
    }

    public function setPayCode($id)
    {
        $id = trim($id);

        if ($id == '' or empty($id)) {
            $id = 0;
        }

        $pclf = TTnew('PayCodeListFactory');

        if ($id == 0
            or
            $this->Validator->isResultSetWithRows('pay_code_id',
                $pclf->getById($id),
                TTi18n::gettext('Invalid Pay Code')
            )
        ) {
            $this->data['pay_code_id'] = $id;

            return true;
        }

        return false;
    }

    public function setPayFormulaPolicy($id)
    {
        $id = trim($id);

        if ($id == '' or empty($id)) {
            $id = 0;
        }

        $pfplf = TTnew('PayFormulaPolicyListFactory');

        if ($id == 0
            or
            $this->Validator->isResultSetWithRows('pay_formula_policy_id',
                $pfplf->getByID($id),
                TTi18n::gettext('Pay Formula Policy is invalid')
            )
        ) {
            $this->data['pay_formula_policy_id'] = $id;

            return true;
        }

        return false;
    }

    /*
        This takes into account any lunch punches when calculating the meal policy.
        If enabled for:
            Auto-Deduct:	It will only deduct the amount that is not taken in lunch time.
                            So if they auto-deduct 60mins, and an employee takes 30mins of lunch,
                            it will deduct the remaining 30mins to equal 60mins. If they don't
                            take any lunch, it deducts the full 60mins.
            Auto-Include:	It will include the amount taken in lunch time, up to the amount given.
                            So if they auto-include 30mins and an employee takes a 60min lunch
                            only 30mins will be included, and 30mins is automatically deducted
                            as a regular lunch punch.
                            If they don't take a lunch, it doesn't include any time.

        If not enabled for:
        Auto-Deduct: Always deducts the amount.
        Auto-Inlcyde: Always includes the amount.
    */

    public function Validate($ignore_warning = true)
    {
        if ($this->getDeleted() != true and $this->Validator->getValidateOnly() == false) { //Don't check the below when mass editing.
            if ($this->getName() == '') {
                $this->Validator->isTRUE('name',
                    false,
                    TTi18n::gettext('Please specify a name'));
            }

            if ($this->getPayCode() == 0) {
                $this->Validator->isTRUE('pay_code_id',
                    false,
                    TTi18n::gettext('Please choose a Pay Code'));
            }

            //Make sure Pay Formula Policy is defined somewhere.
            if ($this->getPayFormulaPolicy() == 0 and $this->getPayCode() > 0 and (!is_object($this->getPayCodeObject()) or (is_object($this->getPayCodeObject()) and $this->getPayCodeObject()->getPayFormulaPolicy() == 0))) {
                $this->Validator->isTRUE('pay_formula_policy_id',
                    false,
                    TTi18n::gettext('Selected Pay Code does not have a Pay Formula Policy defined'));
            }
        }

        if ($this->getDeleted() == true) {
            //Check to make sure nothing else references this policy, so we can be sure its okay to delete it.
            $pglf = TTnew('PolicyGroupListFactory');
            $pglf->getAPISearchByCompanyIdAndArrayCriteria($this->getCompany(), array('meal_policy' => $this->getId()), 1);
            if ($pglf->getRecordCount() > 0) {
                $this->Validator->isTRUE('in_use',
                    false,
                    TTi18n::gettext('This policy is currently in use') . ' ' . TTi18n::gettext('by policy groups'));
            }

            $splf = TTnew('SchedulePolicyListFactory');
            $splf->getAPISearchByCompanyIdAndArrayCriteria($this->getCompany(), array('meal_policy_id' => $this->getId()), 1);
            if ($splf->getRecordCount() > 0) {
                $this->Validator->isTRUE('in_use',
                    false,
                    TTi18n::gettext('This policy is currently in use') . ' ' . TTi18n::gettext('by schedule policies'));
            }
        }

        return true;
    }

    public function getName()
    {
        if (isset($this->data['name'])) {
            return $this->data['name'];
        }

        return false;
    }

    public function getPayCode()
    {
        if (isset($this->data['pay_code_id'])) {
            return (int)$this->data['pay_code_id'];
        }

        return false;
    }

    public function getPayFormulaPolicy()
    {
        if (isset($this->data['pay_formula_policy_id'])) {
            return (int)$this->data['pay_formula_policy_id'];
        }

        return false;
    }

    public function getPayCodeObject()
    {
        return $this->getGenericObject('PayCodeListFactory', $this->getPayCode(), 'pay_code_obj');
    }

    public function preSave()
    {
        if ($this->getAutoDetectType() == false) {
            $this->setAutoDetectType(10);
        }
        return true;
    }

    public function getAutoDetectType()
    {
        if (isset($this->data['auto_detect_type_id'])) {
            return (int)$this->data['auto_detect_type_id'];
        }

        return false;
    }

    public function setAutoDetectType($value)
    {
        $value = trim($value);

        if ($this->Validator->inArrayKey('auto_detect_type',
            $value,
            TTi18n::gettext('Incorrect Auto-Detect Type'),
            $this->getOptions('auto_detect_type'))
        ) {
            $this->data['auto_detect_type_id'] = $value;

            return true;
        }

        return false;
    }

    public function postSave()
    {
        $this->removeCache($this->getId());

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
                        case 'in_use':
                            $data[$variable] = $this->getColumn($variable);
                            break;
                        case 'type':
                        case 'auto_detect_type':
                            $function = 'get' . str_replace('_', '', $variable);
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
        return TTLog::addEntry($this->getId(), $log_action, TTi18n::getText('Meal Policy'), null, $this->getTable(), $this);
    }
}
