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
class PolicyGroupFactory extends Factory
{
    protected $table = 'policy_group';
    protected $pk_sequence_name = 'policy_group_id_seq'; //PK Sequence name

    protected $company_obj = null;

    public function _getFactoryOptions($name, $parent = null)
    {
        $retval = null;
        switch ($name) {
            case 'columns':
                $retval = array(
                    '-1000-name' => TTi18n::gettext('Name'),
                    '-1010-description' => TTi18n::gettext('Description'),
                    '-1100-total_users' => TTi18n::gettext('Employees'),

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
                    'total_users',
                    'updated_date',
                    'updated_by',
                );
                break;
            case 'unique_columns': //Columns that are unique, and disabled for mass editing.
                $retval = array(
                    'name',
                    'user',
                );
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
            'user' => 'User',
            'total_users' => 'TotalUsers',
            'regular_time_policy' => 'RegularTimePolicy',
            'over_time_policy' => 'OverTimePolicy',
            'round_interval_policy' => 'RoundIntervalPolicy',
            'premium_policy' => 'PremiumPolicy',
            'meal_policy' => 'MealPolicy',
            'break_policy' => 'BreakPolicy',
            'holiday_policy' => 'HolidayPolicy',
            'accrual_policy' => 'AccrualPolicy',
            'expense_policy' => 'ExpensePolicy',
            'absence_policy' => 'AbsencePolicy',
            'exception_policy_control_id' => 'ExceptionPolicyControlID',
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

    public function getUser()
    {
        $pgulf = TTnew('PolicyGroupUserListFactory');
        $pgulf->getByPolicyGroupId($this->getId());

        $list = array();
        foreach ($pgulf as $obj) {
            $list[] = $obj->getUser();
        }

        if (empty($list) == false) {
            return $list;
        }

        return false;
    }

    public function setUser($ids)
    {
        if (!is_array($ids)) {
            $ids = array($ids);
        }

        if (is_array($ids)) {
            $tmp_ids = array();
            if (!$this->isNew()) {
                //If needed, delete mappings first.
                $pgulf = TTnew('PolicyGroupUserListFactory');
                $pgulf->getByPolicyGroupId($this->getId());
                foreach ($pgulf as $obj) {
                    $id = $obj->getUser();
                    Debug::text('Policy ID: ' . $obj->getPolicyGroup() . ' ID: ' . $id, __FILE__, __LINE__, __METHOD__, 10);

                    //Delete users that are not selected.
                    if (!in_array($id, $ids)) {
                        Debug::text('Deleting: ' . $id, __FILE__, __LINE__, __METHOD__, 10);
                        $obj->Delete();
                    } else {
                        //Save ID's that need to be updated.
                        Debug::text('NOT Deleting : ' . $id, __FILE__, __LINE__, __METHOD__, 10);
                        $tmp_ids[] = $id;
                    }
                }
                unset($id, $obj);
            }

            //Insert new mappings.
            $ulf = TTnew('UserListFactory');
            foreach ($ids as $id) {
                if (isset($ids) and !in_array($id, $tmp_ids)) {
                    $pguf = TTnew('PolicyGroupUserFactory');
                    $pguf->setPolicyGroup($this->getId());
                    $pguf->setUser($id);

                    $ulf->getById($id);
                    if ($ulf->getRecordCount() > 0) {
                        $obj = $ulf->getCurrent();

                        if ($this->Validator->isTrue('user',
                            $pguf->Validator->isValid(),
                            TTi18n::gettext('Selected employee is invalid or already assigned to another policy group') . ' (' . $obj->getFullName() . ')')
                        ) {
                            $pguf->save();
                        }
                    }
                }
            }

            return true;
        }

        Debug::text('No User IDs to set.', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function getTotalUsers()
    {
        $pgulf = TTnew('PolicyGroupUserListFactory');
        return $pgulf->getTotalByPolicyGroupId($this->getId());
    }

    public function getRegularTimePolicy()
    {
        return CompanyGenericMapListFactory::getArrayByCompanyIDAndObjectTypeIDAndObjectID($this->getCompany(), 100, $this->getID());
    }

    public function setRegularTimePolicy($ids)
    {
        Debug::text('Setting Regular Time Policy IDs : ', __FILE__, __LINE__, __METHOD__, 10);
        return CompanyGenericMapFactory::setMapIDs($this->getCompany(), 100, $this->getID(), $ids);
    }

    public function getOverTimePolicy()
    {
        return CompanyGenericMapListFactory::getArrayByCompanyIDAndObjectTypeIDAndObjectID($this->getCompany(), 110, $this->getID());
    }

    public function setOverTimePolicy($ids)
    {
        Debug::text('Setting OverTime Policy IDs : ', __FILE__, __LINE__, __METHOD__, 10);
        return CompanyGenericMapFactory::setMapIDs($this->getCompany(), 110, $this->getID(), $ids);
    }

    public function getPremiumPolicy()
    {
        return CompanyGenericMapListFactory::getArrayByCompanyIDAndObjectTypeIDAndObjectID($this->getCompany(), 120, $this->getID());
    }

    public function setPremiumPolicy($ids)
    {
        Debug::text('Setting Premium Policy IDs : ', __FILE__, __LINE__, __METHOD__, 10);
        return CompanyGenericMapFactory::setMapIDs($this->getCompany(), 120, $this->getID(), $ids);
    }

    public function getRoundIntervalPolicy()
    {
        return CompanyGenericMapListFactory::getArrayByCompanyIDAndObjectTypeIDAndObjectID($this->getCompany(), 130, $this->getID());
    }

    public function setRoundIntervalPolicy($ids)
    {
        Debug::text('Setting Round Interval Policy IDs : ', __FILE__, __LINE__, __METHOD__, 10);
        return CompanyGenericMapFactory::setMapIDs($this->getCompany(), 130, $this->getID(), $ids);
    }

    public function getAccrualPolicy()
    {
        return CompanyGenericMapListFactory::getArrayByCompanyIDAndObjectTypeIDAndObjectID($this->getCompany(), 140, $this->getID());
    }

    public function setAccrualPolicy($ids)
    {
        Debug::text('Setting Accrual Policy IDs : ', __FILE__, __LINE__, __METHOD__, 10);
        return CompanyGenericMapFactory::setMapIDs($this->getCompany(), 140, $this->getID(), $ids);
    }

    public function getMealPolicy()
    {
        return CompanyGenericMapListFactory::getArrayByCompanyIDAndObjectTypeIDAndObjectID($this->getCompany(), 150, $this->getID());
    }

    public function setMealPolicy($ids)
    {
        Debug::text('Setting Meal Policy IDs : ', __FILE__, __LINE__, __METHOD__, 10);
        return CompanyGenericMapFactory::setMapIDs($this->getCompany(), 150, $this->getID(), $ids);
    }

    public function getBreakPolicy()
    {
        return CompanyGenericMapListFactory::getArrayByCompanyIDAndObjectTypeIDAndObjectID($this->getCompany(), 160, $this->getID());
    }

    public function setBreakPolicy($ids)
    {
        Debug::text('Setting Break Policy IDs : ', __FILE__, __LINE__, __METHOD__, 10);
        return CompanyGenericMapFactory::setMapIDs($this->getCompany(), 160, $this->getID(), $ids);
    }

    public function getAbsencePolicy()
    {
        return CompanyGenericMapListFactory::getArrayByCompanyIDAndObjectTypeIDAndObjectID($this->getCompany(), 170, $this->getID());
    }

    public function setAbsencePolicy($ids)
    {
        Debug::text('Setting Absence Policy IDs : ', __FILE__, __LINE__, __METHOD__, 10);
        return CompanyGenericMapFactory::setMapIDs($this->getCompany(), 170, $this->getID(), (array)$ids);
    }

    public function getHolidayPolicy()
    {
        return CompanyGenericMapListFactory::getArrayByCompanyIDAndObjectTypeIDAndObjectID($this->getCompany(), 180, $this->getID());
    }

    public function setHolidayPolicy($ids)
    {
        Debug::text('Setting Holiday Policy IDs : ', __FILE__, __LINE__, __METHOD__, 10);
        return CompanyGenericMapFactory::setMapIDs($this->getCompany(), 180, $this->getID(), (array)$ids);
    }

    public function getExpensePolicy()
    {
        return CompanyGenericMapListFactory::getArrayByCompanyIDAndObjectTypeIDAndObjectID($this->getCompany(), 200, $this->getID());
    }

    public function setExpensePolicy($ids)
    {
        Debug::text('Setting Expense Policy IDs : ', __FILE__, __LINE__, __METHOD__, 10);
        return CompanyGenericMapFactory::setMapIDs($this->getCompany(), 200, $this->getID(), (array)$ids);
    }

    public function getExceptionPolicyControlID()
    {
        if (isset($this->data['exception_policy_control_id'])) {
            return (int)$this->data['exception_policy_control_id'];
        }

        return false;
    }

    public function setExceptionPolicyControlID($id)
    {
        $id = trim($id);

        if ($id == '' or empty($id)) {
            $id = null;
        }

        $epclf = TTnew('ExceptionPolicyControlListFactory');

        if ($id == null
            or
            $this->Validator->isResultSetWithRows('exception_policy',
                $epclf->getByID($id),
                TTi18n::gettext('Exception Policy is invalid')
            )
        ) {
            $this->data['exception_policy_control_id'] = $id;

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
        return true;
    }

    public function postSave()
    {
        if ($this->getDeleted() == true) {
            Debug::Text('UnAssign Policy Group from User Defaults...' . $this->getId(), __FILE__, __LINE__, __METHOD__, 10);
            $udf = TTnew('UserDefaultFactory');

            $query = 'update ' . $udf->getTable() . ' set policy_group_id = 0 where company_id = ' . (int)$this->getCompany() . ' AND policy_group_id = ' . (int)$this->getId();
            $this->db->Execute($query);
        }

        return true;
    }

    //Support setting created_by, updated_by especially for importing data.
    //Make sure data is set based on the getVariableToFunctionMap order.
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
                        //case 'total_users':
                        //	$data[$variable] = $this->getColumn( $variable );
                        //	break;
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
        return TTLog::addEntry($this->getId(), $log_action, TTi18n::getText('Policy Group'), null, $this->getTable(), $this);
    }
}
