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
class PayFormulaPolicyFactory extends Factory
{
    protected $table = 'pay_formula_policy';
    protected $pk_sequence_name = 'pay_formula_policy_id_seq'; //PK Sequence name

    protected $company_obj = null;
    protected $accrual_policy_account_obj = null;

    public function _getFactoryOptions($name, $parent = null)
    {
        $retval = null;
        switch ($name) {
            case 'pay_type':
                //THIS NEEDS TO GO BACK TO EACH INDIVIDUAL POLICY
                //Otherwise customers will just need to duplicate pay codes for each policy in many cases.
                // ** Actually since they can use Regular Time policies, they shouldn't need as many policies even if the rate is only defined in the pay code.
                // ** Pay Code *must* define the rate though, as we need to support manual entry of codes.
                $retval = array(
                    10 => TTi18n::gettext('Pay Multiplied By Factor'),
                    //20 => TTi18n::gettext('Premium Only'), //Just the specified premium amount. This is now #32 though as that makes more sense.
                    30 => TTi18n::gettext('Flat Hourly Rate (Relative to Wage)'), //This is a relative rate based on their hourly rate.
                    32 => TTi18n::gettext('Flat Hourly Rate'), //NOT relative to their default rate.
                    40 => TTi18n::gettext('Minimum Hourly Rate (Relative to Wage)'), //Pays whichever is greater, this rate or the employees original rate.
                    42 => TTi18n::gettext('Minimum Hourly Rate'), //Pays whichever is greater, this rate or the employees original rate.
                    50 => TTi18n::gettext('Pay + Premium'),
                    //128 => TTi18n::gettext('Custom Formula'), //Can be used to calculate piece work rates.
                );
                break;
            case 'wage_source_type':
                //Used to calculate wages based on inputs other than just their wage record.
                //For example if an employee works in two different departments at two different rates, average them then calculate OT on the average.
                //  This should help cut down on requiring a ton of OT policies for each different rate of pay the employee can get.

                //
                //****PAY CODES HAVE TO CALCULATE PAY, SO THEY CAN BE MANUALLY ENTERED DIRECTLY FROM A MANUAL TIMESHEET.
                // Have two levels of rate calculations, so the premium policy can calculate its own rate, then pass it off to the pay code, which can do additional calculations.
                // For Chesapeake, they would only need different pay codes for Regular Rate, then OT would all be based on that, so it actually wouldn't be that bad.

                //Label: Obtain Hourly Rate From:
                $retval = array(
                    10 => TTi18n::gettext('Wage Group'),

                    //"Code" is singular, as it can just be one. Input pay code calculation
                    // This is basically the source policy(?)
                    20 => TTi18n::gettext('Contributing Pay Code'),
                );
                break;
            case 'columns':
                $retval = array(
                    '-1010-name' => TTi18n::gettext('Name'),
                    '-1020-description' => TTi18n::gettext('Description'),

                    '-1100-pay_type' => TTi18n::gettext('Pay Type'),
                    '-1110-rate' => TTi18n::gettext('Rate'),
                    '-1120-accrual_rate' => TTi18n::gettext('Accrual Rate'),

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
            'name' => 'Name',
            'description' => 'Description',

            'wage_source_type_id' => 'WageSourceType',
            'wage_source_type' => false,
            'wage_source_contributing_shift_policy_id' => 'WageSourceContributingShiftPolicy',
            'wage_source_contributing_shift_policy' => false,
            'time_source_contributing_shift_policy_id' => 'TimeSourceContributingShiftPolicy',
            'time_source_contributing_shift_policy' => false,

            'pay_type_id' => 'PayType',
            'pay_type' => false,
            'rate' => 'Rate',
            'wage_group_id' => 'WageGroup',

            'accrual_rate' => 'AccrualRate',
            'accrual_policy_account_id' => 'AccrualPolicyAccount',

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

    public function getAccrualPolicyAccountObject()
    {
        return $this->getGenericObject('AccrualPolicyAccountListFactory', $this->getAccrualPolicyAccount(), 'accrual_policy_account_obj');
    }

    public function getAccrualPolicyAccount()
    {
        if (isset($this->data['accrual_policy_account_id'])) {
            return (int)$this->data['accrual_policy_account_id'];
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

    public function setName($name)
    {
        $name = trim($name);
        if ($this->Validator->isLength('name',
                $name,
                TTi18n::gettext('Name is too short or too long'),
                2, 100) //Needs to be long enough for upgrade procedure when converting from other policies.
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

    public function getCode()
    {
        if (isset($this->data['code'])) {
            return $this->data['code'];
        }

        return false;
    }

    public function setCode($code)
    {
        $code = trim($code);
        if ($this->Validator->isLength('code',
            $code,
            TTi18n::gettext('Code is too short or too long'),
            2, 50)
        ) {
            $this->data['code'] = $code;

            return true;
        }

        return false;
    }

    public function setPayType($value)
    {
        $value = trim($value);

        if ($this->Validator->inArrayKey('pay_type_id',
            $value,
            TTi18n::gettext('Incorrect Pay Type'),
            $this->getOptions('pay_type'))
        ) {
            $this->data['pay_type_id'] = $value;

            return true;
        }

        return false;
    }

    public function getWageSourceType()
    {
        if (isset($this->data['wage_source_type_id'])) {
            return (int)$this->data['wage_source_type_id'];
        }

        return false;
    }

    public function setWageSourceType($value)
    {
        $value = trim($value);

        if ($this->Validator->inArrayKey('wage_source_type_id',
            $value,
            TTi18n::gettext('Incorrect Wage Source Type'),
            $this->getOptions('wage_source_type'))
        ) {
            $this->data['wage_source_type_id'] = $value;

            return true;
        }

        return false;
    }

    public function getWageSourceContributingShiftPolicy()
    {
        if (isset($this->data['wage_source_contributing_shift_policy_id'])) {
            return (int)$this->data['wage_source_contributing_shift_policy_id'];
        }

        return false;
    }

    public function setWageSourceContributingShiftPolicy($id)
    {
        $id = trim($id);

        $csplf = TTnew('ContributingShiftPolicyListFactory');

        if ($id == 0
            or
            $this->Validator->isResultSetWithRows('wage_source_contributing_shift_policy_id',
                $csplf->getByID($id),
                TTi18n::gettext('Wage Source Contributing Shift Policy is invalid')
            )
        ) {
            $this->data['wage_source_contributing_shift_policy_id'] = $id;

            return true;
        }

        return false;
    }

    public function getTimeSourceContributingShiftPolicy()
    {
        if (isset($this->data['time_source_contributing_shift_policy_id'])) {
            return (int)$this->data['time_source_contributing_shift_policy_id'];
        }

        return false;
    }

    public function setTimeSourceContributingShiftPolicy($id)
    {
        $id = trim($id);

        $csplf = TTnew('ContributingShiftPolicyListFactory');

        if ($id == 0
            or
            $this->Validator->isResultSetWithRows('time_source_contributing_shift_policy_id',
                $csplf->getByID($id),
                TTi18n::gettext('Time Source Contributing Shift Policy is invalid')
            )
        ) {
            $this->data['time_source_contributing_shift_policy_id'] = $id;

            return true;
        }

        return false;
    }

    public function getHourlyRate($original_hourly_rate)
    {
        //Debug::text(' Getting Rate based off Hourly Rate: '. $original_hourly_rate .' Pay Type: '. $this->getPayType(), __FILE__, __LINE__, __METHOD__, 10);
        $rate = 0;

        switch ($this->getPayType()) {
            case 10: //Pay Factor
                //Since they are already paid for this time with regular or OT, minus 1 from the rate
                $rate = ($original_hourly_rate * $this->getRate());
                break;
            case 30: //Flat Hourly Rate (Relative)
                //Get the difference between the employees current wage and the original wage.
                $rate = ($this->getRate() - $original_hourly_rate);
                break;
            //case 20: //Was Premium Only, but its really the same as Flat Hourly Rate (NON relative)
            case 32: //Flat Hourly Rate (NON relative)
                //This should be original_hourly_rate, which is typically related to the users wage/wage group, so they can pay whatever is defined there.
                //If they want to pay a flat hourly rate specified in the pay code use Pay Plus Premium instead.
                //$rate = $original_hourly_rate;
                //In v7 this was the above, which isn't correct and unexpected for the user.
                $rate = $this->getRate();
                break;
            case 40: //Minimum/Prevailing wage (relative)
                if ($this->getRate() > $original_hourly_rate) {
                    $rate = ($this->getRate() - $original_hourly_rate);
                } else {
                    $rate = 0;
                }
                break;
            case 42: //Minimum/Prevailing wage (NON relative)
                if ($this->getRate() > $original_hourly_rate) {
                    $rate = $this->getRate();
                } else {
                    //Use the original rate rather than 0, since this is non-relative its likely
                    //that the employee is just getting paid from pay codes, so if they are getting
                    //paid more than the pay code states, without this they would get paid nothing.
                    //This allows pay codes like "Painting (Regular)" to actually have wages associated with them.
                    $rate = $original_hourly_rate;
                }
                break;
            case 50: //Pay Plus Premium
                $rate = ($original_hourly_rate + $this->getRate());
                break;
            default:
                Debug::text(' ERROR: Invalid Pay Type: ' . $this->getPayType(), __FILE__, __LINE__, __METHOD__, 10);
                break;
        }

        //Don't round rate, as some currencies accept more than 2 decimal places now.
        //and all wages support up to 4 decimal places too.
        //return Misc::MoneyFormat($rate, FALSE);
        //Debug::text(' Final Rate: '. $rate, __FILE__, __LINE__, __METHOD__, 10);

        return $rate;
    }

    public function getPayType()
    {
        if (isset($this->data['pay_type_id'])) {
            return (int)$this->data['pay_type_id'];
        }

        return false;
    }

    public function getRate()
    {
        if (isset($this->data['rate'])) {
            return $this->data['rate'];
        }

        return false;
    }

    public function setRate($int)
    {
        $int = trim($int);

        if (empty($int)) {
            $int = 0;
        }

        if ($this->Validator->isFloat('rate',
            $int,
            TTi18n::gettext('Incorrect Rate'))
        ) {
            $this->data['rate'] = $int;

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

        if (empty($int)) {
            $int = 0;
        }

        if ($this->Validator->isFloat('accrual_rate',
            $int,
            TTi18n::gettext('Incorrect Accrual Rate'))
        ) {
            $this->data['accrual_rate'] = $int;

            return true;
        }

        return false;
    }

    public function setAccrualPolicyAccount($id)
    {
        $id = trim($id);

        if ($id == '' or empty($id)) {
            $id = 0;
        }

        $apalf = TTnew('AccrualPolicyAccountListFactory');

        if ($id == 0
            or
            $this->Validator->isResultSetWithRows('accrual_policy_account_id',
                $apalf->getByID($id),
                TTi18n::gettext('Accrual Account is invalid')
            )
        ) {
            $this->data['accrual_policy_account_id'] = $id;

            return true;
        }

        return false;
    }

    public function Validate($ignore_warning = true)
    {
        if ($this->getDeleted() != true and $this->Validator->getValidateOnly() == false) { //Don't check the below when mass editing.
            if ($this->getName() == '') {
                $this->Validator->isTRUE('name',
                    false,
                    TTi18n::gettext('Please specify a name'));
            }
        }

        if ($this->getDeleted() == true) {
            $pclf = TTNew('PayCodeListFactory');
            $pclf->getByCompanyIdAndPayFormulaPolicyId($this->getCompany(), $this->getId());
            if ($pclf->getRecordCount() > 0) {
                $this->Validator->isTRUE('in_use',
                    false,
                    TTi18n::gettext('This pay formula policy is currently in use') . ' ' . TTi18n::gettext('by pay codes'));
            }

            $rtplf = TTNew('RegularTimePolicyListFactory');
            $rtplf->getByCompanyIdAndPayFormulaPolicyId($this->getCompany(), $this->getId());
            if ($rtplf->getRecordCount() > 0) {
                $this->Validator->isTRUE('in_use',
                    false,
                    TTi18n::gettext('This pay formula policy is currently in use') . ' ' . TTi18n::gettext('by regular time policies'));
            }

            $otplf = TTNew('OverTimePolicyListFactory');
            $otplf->getByCompanyIdAndPayFormulaPolicyId($this->getCompany(), $this->getId());
            if ($otplf->getRecordCount() > 0) {
                $this->Validator->isTRUE('in_use',
                    false,
                    TTi18n::gettext('This pay formula policy is currently in use') . ' ' . TTi18n::gettext('by overtime policies'));
            }

            $pplf = TTNew('PremiumPolicyListFactory');
            $pplf->getByCompanyIdAndPayFormulaPolicyId($this->getCompany(), $this->getId());
            if ($pplf->getRecordCount() > 0) {
                $this->Validator->isTRUE('in_use',
                    false,
                    TTi18n::gettext('This pay formula policy is currently in use') . ' ' . TTi18n::gettext('by premium policies'));
            }

            $aplf = TTNew('AbsencePolicyListFactory');
            $aplf->getByCompanyIdAndPayFormulaPolicyId($this->getCompany(), $this->getId());
            if ($aplf->getRecordCount() > 0) {
                $this->Validator->isTRUE('in_use',
                    false,
                    TTi18n::gettext('This pay formula policy is currently in use') . ' ' . TTi18n::gettext('by absence policies'));
            }

            $mplf = TTNew('MealPolicyListFactory');
            $mplf->getByCompanyIdAndPayFormulaPolicyId($this->getCompany(), $this->getId());
            if ($mplf->getRecordCount() > 0) {
                $this->Validator->isTRUE('in_use',
                    false,
                    TTi18n::gettext('This pay formula policy is currently in use') . ' ' . TTi18n::gettext('by meal policies'));
            }

            $bplf = TTNew('BreakPolicyListFactory');
            $bplf->getByCompanyIdAndPayFormulaPolicyId($this->getCompany(), $this->getId());
            if ($bplf->getRecordCount() > 0) {
                $this->Validator->isTRUE('in_use',
                    false,
                    TTi18n::gettext('This pay formula policy is currently in use') . ' ' . TTi18n::gettext('by break policies'));
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

    public function preSave()
    {
        if ($this->getWageGroup() === false) {
            $this->setWageGroup(0);
        }
        return true;
    }

    public function getWageGroup()
    {
        if (isset($this->data['wage_group_id'])) {
            return (int)$this->data['wage_group_id'];
        }

        return false;
    }

    public function setWageGroup($id)
    {
        $id = trim($id);

        $wglf = TTnew('WageGroupListFactory');

        if ($id == 0
            or
            $this->Validator->isResultSetWithRows('wage_group_id',
                $wglf->getByID($id),
                TTi18n::gettext('Wage Group is invalid')
            )
        ) {
            $this->data['wage_group_id'] = $id;

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
                        case 'pay_type':
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
        return TTLog::addEntry($this->getId(), $log_action, TTi18n::getText('Pay Formula Policy'), null, $this->getTable(), $this);
    }
}
