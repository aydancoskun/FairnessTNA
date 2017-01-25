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
class AccrualBalanceFactory extends Factory
{
    public $user_obj = null;
        protected $table = 'accrual_balance'; //PK Sequence name
protected $pk_sequence_name = 'accrual_balance_id_seq';

    public static function calcBalance($user_id, $accrual_policy_account_id = null)
    {
        global $profiler;

        $profiler->startTimer("AccrualBalanceFactory::calcBalance()");

        $retval = false;
        $update_balance = true;

        $alf = TTnew('AccrualListFactory');

        $alf->StartTransaction();
        //$alf->db->SetTransactionMode( 'SERIALIZABLE' ); //Serialize balance transactions so concurrency issues don't corrupt the balance.

        $balance = $alf->getSumByUserIdAndAccrualPolicyAccount($user_id, $accrual_policy_account_id);
        Debug::text('Balance for User ID: ' . $user_id . ' Accrual Account ID: ' . $accrual_policy_account_id . ' Balance: ' . $balance, __FILE__, __LINE__, __METHOD__, 10);

        $ablf = TTnew('AccrualBalanceListFactory');
        $ablf->getByUserIdAndAccrualPolicyAccount($user_id, $accrual_policy_account_id);
        Debug::text('Found balance records: ' . $ablf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
        if ($ablf->getRecordCount() > 1) { //In case multiple records exist, delete them all and re-insert.
            foreach ($ablf as $ab_obj) {
                $ab_obj->Delete();
            }
            $ab_obj = TTnew('AccrualBalanceFactory');
        } elseif ($ablf->getRecordCount() == 1) {
            $ab_obj = $ablf->getCurrent();
            if ($balance == $ab_obj->getBalance()) {
                Debug::text('Balance has not changed, not updating: ' . $balance, __FILE__, __LINE__, __METHOD__, 10);
                $update_balance = false;
            }
        } else { //No balance record exists yet.
            $ab_obj = TTnew('AccrualBalanceFactory');
        }

        if ($update_balance == true) {
            Debug::text('Setting new balance to: ' . $balance, __FILE__, __LINE__, __METHOD__, 10);
            $ab_obj->setUser($user_id);
            $ab_obj->setAccrualPolicyAccount($accrual_policy_account_id);
            $ab_obj->setBalance($balance);
            if ($ab_obj->isValid()) {
                $retval = $ab_obj->Save();
            } else {
                $alf->FailTransaction();
                Debug::text('Setting new balance failed for User ID: ' . $user_id, __FILE__, __LINE__, __METHOD__, 10);
            }
        }

        $alf->CommitTransaction();
        //$alf->db->SetTransactionMode(''); //Restore default transaction mode.

        $profiler->stopTimer("AccrualBalanceFactory::calcBalance()");

        return $retval;
    }

    public function _getFactoryOptions($name, $parent = null)
    {
        $retval = null;
        switch ($name) {
            case 'columns':
                $retval = array(

                    '-1010-first_name' => TTi18n::gettext('First Name'),
                    '-1020-last_name' => TTi18n::gettext('Last Name'),

                    '-1030-accrual_policy_account' => TTi18n::gettext('Accrual Account'),
                    //'-1040-accrual_policy_type' => TTi18n::gettext('Accrual Policy Type'),
                    '-1050-balance' => TTi18n::gettext('Balance'),

                    '-1090-title' => TTi18n::gettext('Title'),
                    '-1099-group' => TTi18n::gettext('Group'),
                    '-1100-default_branch' => TTi18n::gettext('Branch'),
                    '-1110-default_department' => TTi18n::gettext('Department'),

                    '-2000-created_by' => TTi18n::gettext('Created By'),
                    '-2010-created_date' => TTi18n::gettext('Created Date'),
                    '-2020-updated_by' => TTi18n::gettext('Updated By'),
                    '-2030-updated_date' => TTi18n::gettext('Updated Date'),
                );
                break;
            case 'list_columns':
                $retval = Misc::arrayIntersectByKey(array('accrual_policy_account', 'balance'), Misc::trimSortPrefix($this->getOptions('columns')));
                break;
            case 'default_display_columns': //Columns that are displayed by default.
                $retval = array(
                    'first_name',
                    'last_name',
                    'accrual_policy_account',
                    //'accrual_policy_type',
                    'balance'
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
            'user_id' => 'User',
            'first_name' => false,
            'last_name' => false,
            'accrual_policy_account_id' => 'AccrualPolicyAccount',
            'accrual_policy_account' => false,
            //'accrual_policy_type_id' => FALSE,
            //'accrual_policy_type' => FALSE,
            'default_branch' => false,
            'default_department' => false,
            'group' => false,
            'title' => false,
            'balance' => 'Balance',
        );
        return $variable_function_map;
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
    }

    public function setUser($id)
    {
        $id = trim($id);

        $ulf = TTnew('UserListFactory');

        if ($this->Validator->isResultSetWithRows('user',
            $ulf->getByID($id),
            TTi18n::gettext('Invalid User')
        )
        ) {
            $this->data['user_id'] = $id;

            return true;
        }

        return false;
    }

    public function getAccrualPolicyAccount()
    {
        if (isset($this->data['accrual_policy_account_id'])) {
            return (int)$this->data['accrual_policy_account_id'];
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

    public function getBalance()
    {
        if (isset($this->data['balance'])) {
            return $this->data['balance'];
        }

        return false;
    }

    public function setBalance($int)
    {
        $int = trim($int);

        if (empty($int)) {
            $int = 0;
        }

        if ($this->Validator->isNumeric('balance',
            $int,
            TTi18n::gettext('Incorrect Balance'))
        ) {
            $this->data['balance'] = $int;

            return true;
        }

        return false;
    }

    public function getCreatedBy()
    {
        return false;
    }

    public function setCreatedBy($id = null)
    {
        return false;
    }

    public function getUpdatedDate()
    {
        return false;
    }

    public function setUpdatedDate($epoch = null)
    {
        return false;
    }

    public function getUpdatedBy()
    {
        return false;
    }

    public function setUpdatedBy($id = null)
    {
        return false;
    }

    public function getDeletedDate()
    {
        return false;
    }

    public function setDeletedDate($epoch = null)
    {
        return false;
    }

    public function getDeletedBy()
    {
        return false;
    }

    public function setDeletedBy($id = null)
    {
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

    public function getObjectAsArray($include_columns = null, $permission_children_ids = false)
    {
        $data = array();
        $variable_function_map = $this->getVariableToFunctionMap();
        if (is_array($variable_function_map)) {
            //$apf = TTnew( 'AccrualPolicyFactory' );

            foreach ($variable_function_map as $variable => $function_stub) {
                if ($include_columns == null or (isset($include_columns[$variable]) and $include_columns[$variable] == true)) {
                    $function = 'get' . $function_stub;
                    switch ($variable) {
                        case 'accrual_policy_account':
                            //case 'accrual_policy_type_id':
                        case 'first_name':
                        case 'last_name':
                        case 'title':
                        case 'group':
                        case 'default_branch':
                        case 'default_department':
                            $data[$variable] = $this->getColumn($variable);
                            break;
                        //case 'accrual_policy_type':
                        //	$data[$variable] = Option::getByKey( $this->getColumn( 'accrual_policy_type_id' ), $apf->getOptions( 'type' ) );
                        //	break;
                        default:
                            if (method_exists($this, $function)) {
                                $data[$variable] = $this->$function();
                            }
                            break;
                    }
                }
            }
            $this->getPermissionColumns($data, $this->getUser(), false, $permission_children_ids, $include_columns);
            //Accrual Balances are only created/modified by the system.
            //$this->getCreatedAndUpdatedColumns( $data, $include_columns );
        }

        return $data;
    }
}
