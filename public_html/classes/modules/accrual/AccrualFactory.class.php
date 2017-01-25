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
 * @package Modules\Accrual
 */
class AccrualFactory extends Factory
{
    public $user_obj = null;
        protected $table = 'accrual'; //PK Sequence name
protected $pk_sequence_name = 'accrual_id_seq';
protected $system_type_ids = array(10, 20, 75, 76); //These all special types reserved for system use only.

    public static function deleteOrphans($user_id, $date_stamp)
    {
        Debug::text('Attempting to delete Orphaned Records for User ID: ' . $user_id . ' Date: ' . TTDate::getDate('DATE', $date_stamp), __FILE__, __LINE__, __METHOD__, 10);
        //Remove orphaned entries
        $alf = TTnew('AccrualListFactory');
        $alf->getOrphansByUserIdAndDate($user_id, $date_stamp);
        Debug::text('Found Orphaned Records: ' . $alf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
        if ($alf->getRecordCount() > 0) {
            $accrual_policy_ids = array();
            foreach ($alf as $a_obj) {
                Debug::text('Orphan Record ID: ' . $a_obj->getID(), __FILE__, __LINE__, __METHOD__, 10);
                $accrual_policy_ids[] = $a_obj->getAccrualPolicyAccount();
                $a_obj->Delete();
            }

            //ReCalc balances
            if (empty($accrual_policy_ids) === false) {
                foreach ($accrual_policy_ids as $accrual_policy_id) {
                    AccrualBalanceFactory::calcBalance($user_id, $accrual_policy_id);
                }
            }
        }

        return true;
    }

    public function _getFactoryOptions($name, $parent = null)
    {
        $retval = null;
        switch ($name) {
            case 'type':
                $retval = array(
                    10 => TTi18n::gettext('Banked'), //System: Can never be deleted/edited/added
                    20 => TTi18n::gettext('Used'), //System: Can never be deleted/edited/added
                    30 => TTi18n::gettext('Awarded'),
                    40 => TTi18n::gettext('Un-Awarded'),
                    50 => TTi18n::gettext('Gift'),
                    55 => TTi18n::gettext('Paid Out'),
                    60 => TTi18n::gettext('Rollover Adjustment'),
                    70 => TTi18n::gettext('Initial Balance'),
                    75 => TTi18n::gettext('Calendar-Based Accrual Policy'), //System: Can never be added or edited.
                    76 => TTi18n::gettext('Hour-Based Accrual Policy'), //System: Can never be added or edited.
                    80 => TTi18n::gettext('Other')
                );
                break;
            case 'system_type':
                $retval = array_intersect_key($this->getOptions('type'), array_flip($this->system_type_ids));
                break;
            case 'add_type':
            case 'edit_type':
            case 'user_type':
                $retval = array_diff_key($this->getOptions('type'), array_flip($this->system_type_ids));
                break;
            case 'delete_type': //Types that can be deleted
                $retval = $this->getOptions('type');
                unset($retval[10], $retval[20]); //Remove just Banked/Used as those can't be deleted.
                break;
            case 'accrual_policy_type':
                $apf = TTNew('AccrualPolicyFactory');
                $retval = $apf->getOptions('type');
                break;
            case 'columns':
                $retval = array(

                    '-1010-first_name' => TTi18n::gettext('First Name'),
                    '-1020-last_name' => TTi18n::gettext('Last Name'),

                    '-1030-accrual_policy_account' => TTi18n::gettext('Accrual Account'),
                    '-1040-type' => TTi18n::gettext('Type'),
                    //'-1050-time_stamp' => TTi18n::gettext('Date'),
                    '-1050-date_stamp' => TTi18n::gettext('Date'), //Date stamp is combination of time_stamp and user_date.date_stamp columns.
                    '-1060-amount' => TTi18n::gettext('Amount'),
                    '-1070-note' => TTi18n::gettext('Note'),

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
                $retval = Misc::arrayIntersectByKey(array('accrual_policy_account', 'type', 'date_stamp', 'amount'), Misc::trimSortPrefix($this->getOptions('columns')));
                break;
            case 'default_display_columns': //Columns that are displayed by default.
                $retval = array(
                    'first_name',
                    'last_name',
                    'accrual_policy_account',
                    'type',
                    'amount',
                    'date_stamp'
                );
                break;
            case 'linked_columns': //Columns that are linked together, mainly for Mass Edit, if one changes, they all must.
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
            'default_branch' => false,
            'default_department' => false,
            'user_group' => false,
            'title' => false,
            'accrual_policy_account_id' => 'AccrualPolicyAccount',
            'accrual_policy_account' => false,
            'accrual_policy_id' => 'AccrualPolicy',
            'accrual_policy' => false,
            'accrual_policy_type' => false,
            'type_id' => 'Type',
            'type' => false,
            'user_date_total_id' => 'UserDateTotalID',
            'date_stamp' => false,
            'time_stamp' => 'TimeStamp',
            'amount' => 'Amount',
            'note' => 'Note',
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

    public function setAccrualPolicyAccount($id)
    {
        $id = trim($id);

        if ($id == '' or empty($id)) {
            $id = null;
        }

        $apalf = TTnew('AccrualPolicyAccountListFactory');

        if ($id == null
            or
            $this->Validator->isResultSetWithRows('accrual_policy_account',
                $apalf->getByID($id),
                TTi18n::gettext('Accrual Account is invalid')
            )
        ) {
            $this->data['accrual_policy_account_id'] = $id;

            return true;
        }

        return false;
    }

    public function setAccrualPolicy($id)
    {
        $id = trim($id);

        if ($id == '' or empty($id)) {
            $id = null;
        }

        $aplf = TTnew('AccrualPolicyListFactory');

        if ($id == null
            or
            $this->Validator->isResultSetWithRows('accrual_policy',
                $aplf->getByID($id),
                TTi18n::gettext('Accrual Policy is invalid')
            )
        ) {
            $this->data['accrual_policy_id'] = $id;

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

    public function isSystemType()
    {
        if (in_array($this->getType(), $this->system_type_ids)) {
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

    public function setUserDateTotalID($id)
    {
        $id = trim($id);

        $udtlf = TTnew('UserDateTotalListFactory');

        if ($id == 0
            or
            $this->Validator->isResultSetWithRows('user_date_total',
                $udtlf->getByID($id),
                TTi18n::gettext('User Date Total ID is invalid')
            )
        ) {
            $this->data['user_date_total_id'] = $id;

            return true;
        }

        return false;
    }

    public function setAmount($int)
    {
        $int = trim($int);

        if (empty($int)) {
            $int = 0;
        }

        if ($this->Validator->isNumeric('amount',
                $int,
                TTi18n::gettext('Incorrect Amount'))
            and
            $this->Validator->isTrue('amount',
                $this->isValidAmount($int),
                TTi18n::gettext('Amount does not match type, try using a negative or positive value instead'))
        ) {
            $this->data['amount'] = $int;

            return true;
        }

        return false;
    }

    public function isValidAmount($amount)
    {
        Debug::text('Type: ' . $this->getType() . ' Amount: ' . $amount, __FILE__, __LINE__, __METHOD__, 10);
        //Based on type, set Amount() pos/neg
        switch ($this->getType()) {
            case 10: // Banked
            case 30: // Awarded
            case 50: // Gifted
                if ($amount >= 0) {
                    return true;
                }
                break;
            case 20: // Used
            case 55: // Paid Out
            case 40: // Un Awarded
                if ($amount <= 0) {
                    return true;
                }
                break;
            default:
                return true;
                break;
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

    public function setNote($val)
    {
        $val = trim($val);

        if ($val == ''
            or
            $this->Validator->isLength('note',
                $val,
                TTi18n::gettext('Note is too long'),
                0,
                1024)
        ) {
            $this->data['note'] = $val;

            return true;
        }

        return false;
    }

    public function setEnableCalcBalance($bool)
    {
        $this->calc_balance = $bool;

        return true;
    }

    public function Validate($ignore_warning = true)
    {
        if ($this->Validator->getValidateOnly() == false) { //Don't do the follow validation checks during Mass Edit.
            if ($this->getUser() == false or $this->getUser() == 0) {
                $this->Validator->isTrue('user_id',
                    false,
                    TTi18n::gettext('Please specify an employee'));
            }

            if ($this->getType() == false or $this->getType() == 0) {
                $this->Validator->isTrue('type_id',
                    false,
                    TTi18n::gettext('Please specify accrual type'));
            }

            if ($this->getAccrualPolicyAccount() == false or $this->getAccrualPolicyAccount() == 0) {
                $this->Validator->isTrue('accrual_policy_account_id',
                    false,
                    TTi18n::gettext('Please select an accrual account'));
            }
        }

        return true;
    }

    public function getUser()
    {
        if (isset($this->data['user_id'])) {
            return (int)$this->data['user_id'];
        }
    }

    public function getAccrualPolicyAccount()
    {
        if (isset($this->data['accrual_policy_account_id'])) {
            return (int)$this->data['accrual_policy_account_id'];
        }

        return false;
    }

    public function preSave()
    {
        if ($this->getTimeStamp() == false) {
            $this->setTimeStamp(TTDate::getTime());
        }

        //Delete duplicates before saving.
        //Or orphaned entries on Sum'ing?
        //Would have to do it on view as well though.
        if ($this->getUserDateTotalID() > 0) {
            $alf = TTnew('AccrualListFactory');
            $alf->getByUserIdAndAccrualPolicyAccountAndAccrualPolicyAndUserDateTotalID($this->getUser(), $this->getAccrualPolicyAccount(), $this->getAccrualPolicy(), $this->getUserDateTotalID());
            Debug::text('Found Duplicate Records: ' . (int)$alf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
            if ($alf->getRecordCount() > 0) {
                foreach ($alf as $a_obj) {
                    $a_obj->Delete();
                }
            }
        }

        return true;
    }

    public function getTimeStamp($raw = false)
    {
        if (isset($this->data['time_stamp'])) {
            if ($raw === true) {
                return $this->data['time_stamp'];
            } else {
                return TTDate::strtotime($this->data['time_stamp']);
            }
        }

        return false;
    }

    public function setTimeStamp($epoch)
    {
        $epoch = (!is_int($epoch)) ? trim($epoch) : $epoch; //Dont trim integer values, as it changes them to strings.

        if ($this->Validator->isDate('times_tamp',
            $epoch,
            TTi18n::gettext('Incorrect time stamp'))

        ) {
            $this->data['time_stamp'] = $epoch;

            return true;
        }

        return false;
    }

    public function getUserDateTotalID()
    {
        if (isset($this->data['user_date_total_id'])) {
            return (int)$this->data['user_date_total_id'];
        }

        return false;
    }

    public function getAccrualPolicy()
    {
        if (isset($this->data['accrual_policyid'])) {
            return (int)$this->data['accrual_policy_id'];
        }

        return false;
    }

    public function postSave()
    {
        //Calculate balance
        if ($this->getEnableCalcBalance() == true) {
            Debug::text('Calculating Balance is enabled! ', __FILE__, __LINE__, __METHOD__, 10);
            AccrualBalanceFactory::calcBalance($this->getUser(), $this->getAccrualPolicyAccount());
        }

        return true;
    }

    public function getEnableCalcBalance()
    {
        if (isset($this->calc_balance)) {
            return $this->calc_balance;
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
                        case 'user_date_total_id': //Skip this, as it should never be set from the API.
                            break;
                        case 'time_stamp':
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

    public function getObjectAsArray($include_columns = null, $permission_children_ids = false)
    {
        $data = array();
        $variable_function_map = $this->getVariableToFunctionMap();
        if (is_array($variable_function_map)) {
            foreach ($variable_function_map as $variable => $function_stub) {
                if ($include_columns == null or (isset($include_columns[$variable]) and $include_columns[$variable] == true)) {
                    $function = 'get' . $function_stub;
                    switch ($variable) {
                        case 'accrual_policy_account':
                        case 'accrual_policy':
                        case 'first_name':
                        case 'last_name':
                        case 'title':
                        case 'user_group':
                        case 'default_branch':
                        case 'default_department':
                            $data[$variable] = $this->getColumn($variable);
                            break;
                        case 'accrual_policy_type':
                            $data[$variable] = Option::getByKey($this->getColumn('accrual_policy_type_id'), $this->getOptions($variable));
                            break;
                        case 'type':
                            $function = 'get' . $variable;
                            if (method_exists($this, $function)) {
                                $data[$variable] = Option::getByKey($this->$function(), $this->getOptions($variable));
                            }
                            break;
                        case 'time_stamp':
                            if (method_exists($this, $function)) {
                                $data[$variable] = TTDate::getAPIDate('DATE', $this->$function());
                            }
                            break;
                        case 'date_stamp': //This is a combination of the time_stamp and user_date.date_stamp columns.
                            $data[$variable] = TTDate::getAPIDate('DATE', strtotime($this->getColumn($variable)));
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

        //Debug::Arr($data, 'Data Object: ', __FILE__, __LINE__, __METHOD__, 10);

        return $data;
    }

    public function addLog($log_action)
    {
        $u_obj = $this->getUserObject();
        if (is_object($u_obj)) {
            return TTLog::addEntry($this->getId(), $log_action, TTi18n::getText('Accrual') . ' - ' . TTi18n::getText('Employee') . ': ' . $u_obj->getFullName(false, true) . ' ' . TTi18n::getText('Type') . ': ' . Option::getByKey($this->getType(), $this->getOptions('type')) . ' ' . TTi18n::getText('Date') . ': ' . TTDate::getDate('DATE', $this->getTimeStamp()) . ' ' . TTi18n::getText('Total Time') . ': ' . TTDate::getTimeUnit($this->getAmount()), null, $this->getTable(), $this);
        }

        return false;
    }

    public function getUserObject()
    {
        return $this->getGenericObject('UserListFactory', $this->getUser(), 'user_obj');
    }

    public function getAmount()
    {
        if (isset($this->data['amount'])) {
            return $this->data['amount'];
        }

        return false;
    }
}
