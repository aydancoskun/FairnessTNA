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
 * @package Modules\Users
 */
class BankAccountFactory extends Factory
{
    protected $table = 'bank_account';
    protected $pk_sequence_name = 'bank_account_id_seq'; //PK Sequence name

    protected $user_obj = null;

    public function _getFactoryOptions($name, $parent = null)
    {
        $retval = null;
        switch ($name) {
            case 'ach_transaction_type': //ACH transactions require a transaction code that matches the bank account.
                $retval = array(
                    22 => TTi18n::getText('Checking'),
                    32 => TTi18n::getText('Savings'),
                );
                break;
            case 'columns':
                $retval = array(

                    '-1010-first_name' => TTi18n::gettext('First Name'),
                    '-1020-last_name' => TTi18n::gettext('Last Name'),

                    '-1090-title' => TTi18n::gettext('Title'),
                    '-1099-user_group' => TTi18n::gettext('Group'),
                    '-1100-default_branch' => TTi18n::gettext('Branch'),
                    '-1110-default_department' => TTi18n::gettext('Department'),

                    '-5010-transit' => TTi18n::gettext('Transit/Routing'),
                    '-5020-account' => TTi18n::gettext('Account'),
                    '-5030-institution' => TTi18n::gettext('Institution'),

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
                    'account',
                    'institution',
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
            'user_id' => 'User',
            'first_name' => false,
            'last_name' => false,

            'institution' => 'Institution',
            'transit' => 'Transit',
            'account' => 'Account',

            'default_branch' => false,
            'default_department' => false,
            'user_group' => false,
            'title' => false,

            'deleted' => 'Deleted',
        );
        return $variable_function_map;
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

    public function setUser($id)
    {
        $id = trim($id);

        $ulf = TTnew('UserListFactory');

        if ($id == 0
            or $this->Validator->isResultSetWithRows('user',
                $ulf->getByID($id),
                TTi18n::gettext('Invalid Employee')
            )
        ) {
            $this->data['user_id'] = $id;

            return true;
        }

        return false;
    }

    public function getTransit()
    {
        if (isset($this->data['transit'])) {
            return $this->data['transit'];
        }

        return false;
    }

    public function setTransit($value)
    {
        $value = trim($value);

        if (
            $this->Validator->isNumeric('transit',
                $value,
                TTi18n::gettext('Invalid transit number, must be digits only'))
            and
            $this->Validator->isLength('transit',
                $value,
                TTi18n::gettext('Invalid transit number length'),
                2,
                15)
        ) {
            $this->data['transit'] = $value;

            return true;
        }

        return false;
    }

    public function setAccount($value)
    {
        //If *'s are in the account number, skip setting it
        //This allows them to change other data without seeing the account number.
        if (stripos($value, 'X') !== false) {
            return false;
        }

        $value = $this->Validator->stripNonNumeric(trim($value));
        if (
        $this->Validator->isLength('account',
            $value,
            TTi18n::gettext('Invalid account number length'),
            3,
            20)
        ) {
            $this->data['account'] = $value;

            return true;
        }

        return false;
    }

    public function Validate($ignore_warning = true)
    {
        if ($this->getAccount() == false) {
            $this->Validator->isTRUE('account',
                false,
                TTi18n::gettext('Bank account not specified'));
        }

        //Make sure this entry is unique.
        if ($this->getDeleted() == false and $this->isUnique() == false) {
            $this->Validator->isTRUE('user_id',
                false,
                TTi18n::gettext('Bank account already exists for this employee'));

            return false;
        }

        return true;
    }

    public function getAccount()
    {
        if (isset($this->data['account'])) {
            return $this->data['account'];
        }

        return false;
    }

    public function isUnique()
    {
        if ($this->getCompany() == false) {
            return false;
        }

        if ($this->getUser() > 0) {
            $ph = array(
                'company_id' => (int)$this->getCompany(),
                'user_id' => (int)$this->getUser(),
            );

            $query = 'select id from ' . $this->getTable() . ' where company_id = ? AND user_id = ? AND deleted = 0';
        } else {
            $ph = array(
                'company_id' => (int)$this->getCompany(),
            );

            $query = 'select id from ' . $this->getTable() . ' where company_id = ? AND user_id is NULL AND deleted = 0';
        }
        $id = $this->db->GetOne($query, $ph);
        Debug::Arr($ph, 'Unique ID: ' . $id . ' Query: ' . $query, __FILE__, __LINE__, __METHOD__, 10);

        if ($id === false) {
            return true;
        } else {
            if ($id == $this->getId()) {
                return true;
            }
        }

        return false;
    }

    public function getCompany()
    {
        return (int)$this->data['company_id'];
    }

    public function getUser()
    {
        if (isset($this->data['user_id'])) {
            return (int)$this->data['user_id'];
        }

        return false;
    }

    public function preSave()
    {
        if ($this->getUser() == false) {
            Debug::Text('Clearing User value, because this is strictly a company record', __FILE__, __LINE__, __METHOD__, 10);
            //$this->setUser( 0 ); //COMPANY record.
        }

        //PGSQL has a NOT NULL constraint on Instituion number prior to schema v1014A.
        if ($this->getInstitution() == false) {
            $this->setInstitution('000');
        }

        return true;
    }

    public function getInstitution()
    {
        if (isset($this->data['institution'])) {
            return $this->data['institution'];
        }

        return false;
    }

    public function setInstitution($value)
    {
        $value = trim($value);

        if (
            $value == ''
            or
            (
                $this->Validator->isNumeric('institution',
                    $value,
                    TTi18n::gettext('Invalid institution number, must be digits only'))
                and
                $this->Validator->isLength('institution',
                    $value,
                    TTi18n::gettext('Invalid institution number length'),
                    2,
                    3)
            )
        ) {
            $this->data['institution'] = $value;

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

    public function getObjectAsArray($include_columns = null, $permission_children_ids = false)
    {
        $data = array();
        $variable_function_map = $this->getVariableToFunctionMap();
        if (is_array($variable_function_map)) {
            foreach ($variable_function_map as $variable => $function_stub) {
                if ($include_columns == null or (isset($include_columns[$variable]) and $include_columns[$variable] == true)) {
                    $function = 'get' . $function_stub;
                    switch ($variable) {
                        case 'account':
                            $data[$variable] = $this->getSecureAccount();
                            break;
                        case 'first_name':
                        case 'last_name':
                        case 'title':
                        case 'user_group':
                        case 'currency':
                        case 'default_branch':
                        case 'default_department':
                            $data[$variable] = $this->getColumn($variable);
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

    public function getSecureAccount($value = null)
    {
        if ($value == '') {
            $value = $this->getAccount();
        }

        //Replace the middle digits leaving only 2 digits on each end, or just 1 digit on each end if the account is too short.
        $replace_length = ((strlen($value) - 4) >= 4) ? (strlen($value) - 4) : 3;
        $start_digit = (strlen($value) >= 7) ? 2 : 1;

        $account = str_replace(substr($value, $start_digit, $replace_length), str_repeat('X', $replace_length), $value);
        return $account;
    }

    public function addLog($log_action)
    {
        if ($this->getUser() == '') {
            $log_description = TTi18n::getText('Company');
        } else {
            $log_description = TTi18n::getText('Employee');

            $u_obj = $this->getUserObject();
            if (is_object($u_obj)) {
                $log_description .= ': ' . $u_obj->getFullName(false, true);
            }
        }
        return TTLog::addEntry($this->getId(), $log_action, TTi18n::getText('Bank Account') . ' - ' . $log_description, null, $this->getTable(), $this);
    }

    public function getUserObject()
    {
        return $this->getGenericObject('UserListFactory', $this->getUser(), 'user_obj');
    }
}
