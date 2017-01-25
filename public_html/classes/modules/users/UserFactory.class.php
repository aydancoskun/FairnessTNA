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
class UserFactory extends Factory
{
public $username_validator_regex = '/^[a-z0-9-_\.@]{1,250}$/i';
        public $phoneid_validator_regex = '/^[0-9]{1,250}$/i'; //PK Sequence name
    protected $table = 'users';
protected $pk_sequence_name = 'users_id_seq';
    protected $tmp_data = null;
    protected $user_preference_obj = null;
    protected $user_tax_obj = null;
    protected $company_obj = null;
    protected $title_obj = null;
    protected $branch_obj = null;
    protected $department_obj = null;
    protected $group_obj = null; //Authentication class needs to access this.
    protected $currency_obj = null;
    protected $phonepassword_validator_regex = '/^[0-9]{1,250}$/i';
    protected $name_validator_regex = '/^[a-zA-Z- \.\'()\[\]|\x{0080}-\x{FFFF}]{1,250}$/iu'; //Allow ()/[] so nicknames can be specified.
    protected $address_validator_regex = '/^[a-zA-Z0-9-,_\/\.\'#\ |\x{0080}-\x{FFFF}]{1,250}$/iu';
    protected $city_validator_regex = '/^[a-zA-Z0-9-,_\.\'#\ |\x{0080}-\x{FFFF}]{1,250}$/iu';

    public static function getNextAvailableEmployeeNumber($company_id = null)
    {
        global $current_company;

        if ($company_id == '' and is_object($current_company)) {
            $company_id = $current_company->getId();
        } elseif ($company_id == '' and isset($this) and is_object($this)) {
            $company_id = $this->getCompany();
        }

        $ulf = TTNew('UserListFactory');
        $ulf->getHighestEmployeeNumberByCompanyId($company_id);
        if ($ulf->getRecordCount() > 0) {
            Debug::Text('Highest Employee Number: ' . $ulf->getCurrent()->getEmployeeNumber(), __FILE__, __LINE__, __METHOD__, 10);
            if (is_numeric($ulf->getCurrent()->getEmployeeNumber()) == true) {
                return ($ulf->getCurrent()->getEmployeeNumber() + 1);
            } else {
                Debug::Text('Highest Employee Number is not an integer.', __FILE__, __LINE__, __METHOD__, 10);
                return null;
            }
        } else {
            return 1;
        }
    }

    public static function UnsubscribeEmail($email)
    {
        $email = trim(strtolower($email));

        try {
            $ulf = TTnew('UserListFactory');
            $ulf->getByHomeEmailOrWorkEmail($email);
            if ($ulf->getRecordCount() > 0) {
                foreach ($ulf as $u_obj) {
                    Debug::Text('Unsubscribing: ' . $email . ' User ID: ' . $u_obj->getID(), __FILE__, __LINE__, __METHOD__, 10);
                    if (strtolower($u_obj->getWorkEmail()) == $email and $u_obj->getWorkEmailIsValid() == true) {
                        //$u_obj->setWorkEmail( '' );
                        $u_obj->setWorkEmailIsValid(false);
                        $u_obj->sendValidateEmail('work');
                    }

                    if (strtolower($u_obj->getHomeEmail()) == $email and $u_obj->getHomeEmailIsValid() == true) {
                        //$u_obj->setHomeEmail( '' );
                        $u_obj->setHomeEmailIsValid(false);
                        $u_obj->sendValidateEmail('home');
                    }

                    TTLog::addEntry($u_obj->getId(), 500, TTi18n::gettext('Requiring validation for invalid or bouncing email address') . ': ' . $email, $u_obj->getId(), 'users');
                    if ($u_obj->isValid()) {
                        $u_obj->Save();
                    }
                }
                return true;
            }
        } catch (Exception $e) {
            unset($e); //code standards
            Debug::text('ERROR: Unable to unsubscribe email: ' . $email, __FILE__, __LINE__, __METHOD__, 10);
        }

        return false;
    }

    public function _getFactoryOptions($name, $parent = null)
    {
        $retval = null;
        switch ($name) {
            case 'status':
                $retval = array(
                    //Add System users (for APIs and reseller admin accounts)
                    //Add "New Hire" status for employees going through the onboarding process or newly imported employees.
                    10 => TTi18n::gettext('Active'),
                    11 => TTi18n::gettext('Inactive'), //Add option that isn't terminated/leave but is still not billed/active.
                    12 => TTi18n::gettext('Leave - Illness/Injury'),
                    14 => TTi18n::gettext('Leave - Maternity/Parental'),
                    16 => TTi18n::gettext('Leave - Other'),
                    20 => TTi18n::gettext('Terminated'),
                );
                break;
            case 'sex':
                $retval = array(
                    5 => TTi18n::gettext('Unspecified'),
                    10 => TTi18n::gettext('Male'),
                    20 => TTi18n::gettext('Female'),
                );
                break;
            case 'columns':
                $retval = array(
                    '-1005-company' => TTi18n::gettext('Company'),
                    '-1010-employee_number' => TTi18n::gettext('Employee #'),
                    '-1020-status' => TTi18n::gettext('Status'),
                    '-1030-user_name' => TTi18n::gettext('User Name'),
                    '-1040-phone_id' => TTi18n::gettext('Quick Punch ID'),

                    '-1060-first_name' => TTi18n::gettext('First Name'),
                    '-1070-middle_name' => TTi18n::gettext('Middle Name'),
                    '-1080-last_name' => TTi18n::gettext('Last Name'),
                    '-1082-full_name' => TTi18n::gettext('Full Name'),

                    '-1090-title' => TTi18n::gettext('Title'),
                    '-1099-user_group' => TTi18n::gettext('Group'), //Update ImportUser class if sort order is changed for this.
                    '-1100-ethnic_group' => TTi18n::gettext('Ethnicity'),
                    '-1102-default_branch' => TTi18n::gettext('Branch'),
                    '-1103-default_department' => TTi18n::gettext('Department'),
                    '-1104-default_job' => TTi18n::gettext('Job'),
                    '-1105-default_job_item' => TTi18n::gettext('Task'),
                    '-1106-currency' => TTi18n::gettext('Currency'),

                    '-1108-permission_control' => TTi18n::gettext('Permission Group'),
                    '-1110-pay_period_schedule' => TTi18n::gettext('Pay Period Schedule'),
                    '-1112-policy_group' => TTi18n::gettext('Policy Group'),

                    '-1120-sex' => TTi18n::gettext('Gender'),

                    '-1130-address1' => TTi18n::gettext('Address 1'),
                    '-1140-address2' => TTi18n::gettext('Address 2'),

                    '-1150-city' => TTi18n::gettext('City'),
                    '-1160-province' => TTi18n::gettext('Province/State'),
                    '-1170-country' => TTi18n::gettext('Country'),
                    '-1180-postal_code' => TTi18n::gettext('Postal Code'),
                    '-1190-work_phone' => TTi18n::gettext('Work Phone'),
                    '-1191-work_phone_ext' => TTi18n::gettext('Work Phone Ext'),
                    '-1200-home_phone' => TTi18n::gettext('Home Phone'),
                    '-1210-mobile_phone' => TTi18n::gettext('Mobile Phone'),
                    '-1220-fax_phone' => TTi18n::gettext('Fax Phone'),
                    '-1230-home_email' => TTi18n::gettext('Home Email'),
                    '-1240-work_email' => TTi18n::gettext('Work Email'),
                    '-1250-birth_date' => TTi18n::gettext('Birth Date'),
                    '-1251-birth_date_age' => TTi18n::gettext('Age'),
                    '-1260-hire_date' => TTi18n::gettext('Hire Date'),
                    '-1261-hire_date_age' => TTi18n::gettext('Length of Service'),
                    '-1270-termination_date' => TTi18n::gettext('Termination Date'),
                    '-1280-sin' => TTi18n::gettext('SIN/SSN'),
                    '-1290-note' => TTi18n::gettext('Note'),
                    '-1300-tag' => TTi18n::gettext('Tags'),
                    '-1400-hierarchy_control_display' => TTi18n::gettext('Hierarchy'),
                    '-1401-hierarchy_level_display' => TTi18n::gettext('Hierarchy Superiors'),
                    '-1500-last_login_date' => TTi18n::gettext('Last Login Date'),
                    '-1510-max_punch_time_stamp' => TTi18n::gettext('Last Punch Time'),
                    '-2000-created_by' => TTi18n::gettext('Created By'),
                    '-2010-created_date' => TTi18n::gettext('Created Date'),
                    '-2020-updated_by' => TTi18n::gettext('Updated By'),
                    '-2030-updated_date' => TTi18n::gettext('Updated Date'),
                );
                break;
            case 'user_secure_columns': //Regular employee secure columns (Used in MessageFactory)
                $retval = array(
                    'first_name',
                    'middle_name',
                    'last_name',
                );
                $retval = Misc::arrayIntersectByKey($retval, Misc::trimSortPrefix($this->getOptions('columns')));
                break;
            case 'user_child_secure_columns': //Superior employee secure columns (Used in MessageFactory)
                $retval = array(
                    'first_name',
                    'middle_name',
                    'last_name',
                    'title',
                    'user_group',
                    'default_branch',
                    'default_department',
                );
                $retval = Misc::arrayIntersectByKey($retval, Misc::trimSortPrefix($this->getOptions('columns')));
                break;
            case 'list_columns':
                $retval = Misc::arrayIntersectByKey($this->getOptions('default_display_columns'), Misc::trimSortPrefix($this->getOptions('columns')));
                break;
            case 'default_display_columns': //Columns that are displayed by default.
                $retval = array(
                    'status',
                    'employee_number',
                    'first_name',
                    'last_name',
                    'home_phone',
                );
                break;
            case 'unique_columns': //Columns that are unique, and disabled for mass editing.
                $retval = array(
                    'user_name',
                    'phone_id',
                    'employee_number',
                    'sin'
                );
                break;
            case 'linked_columns': //Columns that are linked together, mainly for Mass Edit, if one changes, they all must.
                $retval = array(
                    'country',
                    'province',
                    'postal_code'
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
            'company' => false,
            'status_id' => 'Status',
            'status' => false,
            'group_id' => 'Group',
            'user_group' => false,
            'ethnic_group_id' => 'EthnicGroup',
            'ethnic_group' => false,
            'user_name' => 'UserName',
            'phone_id' => 'PhoneId',
            'employee_number' => 'EmployeeNumber',
            'title_id' => 'Title',
            'title' => false,
            'default_branch_id' => 'DefaultBranch',
            'default_branch' => false,
            'default_branch_manual_id' => false,
            'default_department_id' => 'DefaultDepartment',
            'default_department' => false,
            'default_department_manual_id' => false,
            'default_job_id' => 'DefaultJob',
            'default_job' => false,
            'default_job_manual_id' => false,
            'default_job_item_id' => 'DefaultJobItem',
            'default_job_item' => false,
            'default_job_item_manual_id' => false,
            'permission_control_id' => 'PermissionControl',
            'permission_control' => false,
            'pay_period_schedule_id' => 'PayPeriodSchedule',
            'pay_period_schedule' => false,
            'policy_group_id' => 'PolicyGroup',
            'policy_group' => false,
            'hierarchy_control' => 'HierarchyControl',
            'first_name' => 'FirstName',
            'first_name_metaphone' => 'FirstNameMetaphone',
            'middle_name' => 'MiddleName',
            'last_name' => 'LastName',
            'last_name_metaphone' => 'LastNameMetaphone',
            'full_name' => 'FullName',
            'second_last_name' => 'SecondLastName',
            'sex_id' => 'Sex',
            'sex' => false,
            'address1' => 'Address1',
            'address2' => 'Address2',
            'city' => 'City',
            'country' => 'Country',
            'province' => 'Province',
            'postal_code' => 'PostalCode',
            'work_phone' => 'WorkPhone',
            'work_phone_ext' => 'WorkPhoneExt',
            'home_phone' => 'HomePhone',
            'mobile_phone' => 'MobilePhone',
            'fax_phone' => 'FaxPhone',
            'home_email' => 'HomeEmail',
            'home_email_is_valid' => 'HomeEmailIsValid',
            'home_email_is_valid_key' => 'HomeEmailIsValidKey',
            'home_email_is_valid_date' => 'HomeEmailIsValidDate',
            'feedback_rating' => 'FeedbackRating',

            'work_email' => 'WorkEmail',
            'work_email_is_valid' => 'WorkEmailIsValid',
            'work_email_is_valid_key' => 'WorkEmailIsValidKey',
            'work_email_is_valid_date' => 'WorkEmailIsValidDate',

            'birth_date' => 'BirthDate',
            'birth_date_age' => false,
            'hire_date' => 'HireDate',
            'hire_date_age' => false,
            'termination_date' => 'TerminationDate',
            'currency_id' => 'Currency',
            'currency' => false,
            'currency_rate' => false,
            'sin' => 'SIN',
            'other_id1' => 'OtherID1',
            'other_id2' => 'OtherID2',
            'other_id3' => 'OtherID3',
            'other_id4' => 'OtherID4',
            'other_id5' => 'OtherID5',
            'note' => 'Note',
            'longitude' => 'Longitude',
            'latitude' => 'Latitude',
            'tag' => 'Tag',
            'last_login_date' => 'LastLoginDate',
            'max_punch_time_stamp' => false,
            'hierarchy_control_display' => false,
            'hierarchy_level_display' => false,

            'password' => 'Password', //Must go near the end, so we can validate based on other info.
            'phone_password' => 'PhonePassword', //Must go near the end, so we can validate based on other info.

            //These must be defined, but they are ignored in setObjectFromArray() due to security risks.
            'password_reset_key' => 'PasswordResetKey',
            'password_reset_date' => 'PasswordResetDate',
            'password_updated_date' => 'PasswordUpdatedDate', //Needs to be defined otherwise password_updated_date never gets set. Also needs to go before setPassword() as it updates the date too.

            'deleted' => 'Deleted',
        );
        return $variable_function_map;
    }

    public function getUserPreferenceObject()
    {
        $retval = $this->getGenericObject('UserPreferenceListFactory', $this->getID(), 'user_preference_obj', 'getByUserId', 'getUser');

        //Always bootstrap the user preferences if none exist.
        if (!is_object($retval)) {
            Debug::Text('NO PREFERENCES SET FOR USER ID: ' . $this->getID() . ' Using Defaults...', __FILE__, __LINE__, __METHOD__, 10);
            $this->user_preference_obj = TTnew('UserPreferenceFactory');
            $this->user_preference_obj->setUser($this->getID());

            return $this->user_preference_obj;
        }

        return $retval;
    }

    public function getTitleObject()
    {
        return $this->getGenericObject('UserTitleListFactory', $this->getTitle(), 'title_obj');
    }

    public function getTitle()
    {
        if (isset($this->data['title_id'])) {
            return (int)$this->data['title_id'];
        }

        return false;
    }

    public function getDefaultBranchObject()
    {
        return $this->getGenericObject('BranchListFactory', $this->getDefaultBranch(), 'branch_obj');
    }

    public function getDefaultBranch()
    {
        if (isset($this->data['default_branch_id'])) {
            return (int)$this->data['default_branch_id'];
        }

        return false;
    }

    public function getDefaultDepartmentObject()
    {
        return $this->getGenericObject('DepartmentListFactory', $this->getDefaultDepartment(), 'department_obj');
    }

    public function getDefaultDepartment()
    {
        if (isset($this->data['default_department_id'])) {
            return (int)$this->data['default_department_id'];
        }

        return false;
    }

    public function getGroupObject()
    {
        return $this->getGenericObject('UserGroupListFactory', $this->getGroup(), 'group_obj');
    }

    public function getGroup()
    {
        if (isset($this->data['group_id'])) {
            return (int)$this->data['group_id'];
        }

        return false;
    }

    public function getCurrencyObject()
    {
        return $this->getGenericObject('CurrencyListFactory', $this->getCurrency(), 'currency_obj');
    }

    public function getCurrency()
    {
        if (isset($this->data['currency_id'])) {
            return (int)$this->data['currency_id'];
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

    public function setGroup($id)
    {
        $id = (int)trim($id);

        $uglf = TTnew('UserGroupListFactory');
        if ($id == 0
            or
            $this->Validator->isResultSetWithRows('group',
                $uglf->getByID($id),
                TTi18n::gettext('Group is invalid')
            )
        ) {
            $this->data['group_id'] = $id;

            return true;
        }

        return false;
    }

    public function setPermissionControl($id)
    {
        $id = (int)trim($id);

        $pclf = TTnew('PermissionControlListFactory');

        $current_user_permission_level = $this->getCurrentUserPermissionLevel();

        $modify_permissions = false;
        if ($current_user_permission_level >= $this->getPermissionLevel()) {
            $modify_permissions = true;
        }

        global $current_user;
        if (is_object($current_user) and $current_user->getID() == $this->getID() and $id != $this->getPermissionControl()) { //Acting on currently logged in user.
            $logged_in_modify_permissions = false; //Must be false for validation to fail.
        } else {
            $logged_in_modify_permissions = true;
        }


        //Don't allow permissions to be modified if the currently logged in user has a lower permission level.
        //As such if someone with a lower level is able to edit the user of higher level, they must not call this function at all, or use a blank value.
        if ($id != ''
            and
            $this->Validator->isResultSetWithRows('permission_control_id',
                $pclf->getByIDAndLevel($id, $current_user_permission_level),
                TTi18n::gettext('Permission Group is invalid')
            )
            and
            $this->Validator->isTrue('permission_control_id',
                $modify_permissions,
                TTi18n::gettext('Insufficient access to modify permissions for this employee')
            )
            and
            $this->Validator->isTrue('permission_control_id',
                $logged_in_modify_permissions,
                TTi18n::gettext('Unable to change permissions of your own record')
            )
        ) {
            $this->tmp_data['permission_control_id'] = $id;

            return true;
        }

        return false;
    }

    public function getCurrentUserPermissionLevel()
    {
        //Get currently logged in users permission level, so we can ensure they don't assign another user to a higher level.
        global $current_user;
        if (isset($current_user) and is_object($current_user)) {
            $permission = new Permission();
            $current_user_permission_level = $permission->getLevel($current_user->getId(), $current_user->getCompany());
        } else {
            //If we can't find the current_user object, we need to allow any permission group to be assigned, in case
            //its being modified from raw factory calls.
            $current_user_permission_level = 100;
        }

        Debug::Text('Current User Permission Level: ' . $current_user_permission_level, __FILE__, __LINE__, __METHOD__, 10);
        return $current_user_permission_level;
    }

    public function getPermissionLevel()
    {
        $permission = new Permission();
        return $permission->getLevel($this->getID(), $this->getCompany());
    }

    public function getCompany()
    {
        if (isset($this->data['company_id'])) {
            return (int)$this->data['company_id'];
        }

        return false;
    }

    public function getPermissionControl()
    {
        //Check to see if any temporary data is set for the hierarchy, if not, make a call to the database instead.
        //postSave() needs to get the tmp_data.
        if (isset($this->tmp_data['permission_control_id'])) {
            return $this->tmp_data['permission_control_id'];
        } elseif ($this->getCompany() > 0 and $this->getID() > 0) {
            $pclfb = TTnew('PermissionControlListFactory');
            $pclfb->getByCompanyIdAndUserId($this->getCompany(), $this->getID());
            if ($pclfb->getRecordCount() > 0) {
                return $pclfb->getCurrent()->getId();
            }
        }

        return false;
    }

    public function setPayPeriodSchedule($id)
    {
        $id = (int)trim($id);

        $ppslf = TTnew('PayPeriodScheduleListFactory');

        if ($id == 0
            or $this->Validator->isResultSetWithRows('pay_period_schedule_id',
                $ppslf->getByID($id),
                TTi18n::gettext('Pay Period schedule is invalid')
            )
        ) {
            $this->tmp_data['pay_period_schedule_id'] = $id;

            return true;
        }

        return false;
    }

    //Display each superior that the employee is assigned too.

    public function setPolicyGroup($id)
    {
        $id = (int)trim($id);

        $pglf = TTnew('PolicyGroupListFactory');

        if ($id != ''
            and
            (
                $id == 0
                or $this->Validator->isResultSetWithRows('policy_group_id',
                    $pglf->getByID($id),
                    TTi18n::gettext('Policy Group is invalid')
                )
            )
        ) {
            $this->tmp_data['policy_group_id'] = $id;

            return true;
        }

        return false;
    }

    //Display each hierarchy that the employee is assigned too.

    public function setHierarchyControl($data)
    {
        if (!is_array($data)) {
            return false;
        }

        //array passed in is hierarchy_object_type_id => hierarchy_control_id
        if (is_array($data)) {
            $hclf = TTnew('HierarchyControlListFactory');
            Debug::Arr($data, 'Hierarchy Control Data: ', __FILE__, __LINE__, __METHOD__, 10);

            foreach ($data as $hierarchy_object_type_id => $hierarchy_control_id) {
                $hierarchy_control_id = Misc::trimSortPrefix($hierarchy_control_id);

                if ($hierarchy_control_id == 0
                    or
                    $this->Validator->isResultSetWithRows('hierarchy_control_id',
                        $hclf->getByID($hierarchy_control_id),
                        TTi18n::gettext('Hierarchy is invalid')
                    )
                ) {
                    $this->tmp_data['hierarchy_control'][$hierarchy_object_type_id] = $hierarchy_control_id;
                } else {
                    return false;
                }
            }

            return true;
        }

        return false;
    }

    public function getFeedbackRating()
    {
        if (isset($this->data['feedback_rating'])) {
            return $this->data['feedback_rating'];
        }

        return false;
    }

    public function setFeedbackRating($rating)
    {
        if ($rating == 1 or $rating == 0 or $rating == -1) {
            $this->data['feedback_rating'] = $rating;

            return true;
        }

        return false;
    }

    public function setUserName($user_name)
    {
        $user_name = trim(strtolower($user_name));

        if ($this->Validator->isRegEx('user_name',
                $user_name,
                TTi18n::gettext('Incorrect characters in user name'),
                $this->username_validator_regex)
            and
            $this->Validator->isLength('user_name',
                $user_name,
                TTi18n::gettext('Incorrect user name length'),
                3,
                250)
            and
            $this->Validator->isTrue('user_name',
                $this->isUniqueUserName($user_name),
                TTi18n::gettext('User name is already taken')
            )
        ) {
            $this->data['user_name'] = $user_name;

            return true;
        }

        return false;
    }

    public function isUniqueUserName($user_name)
    {
        $ph = array(
            'user_name' => trim(strtolower($user_name)),
        );

        $query = 'select id from ' . $this->getTable() . ' where user_name = ? AND deleted=0';
        $user_name_id = $this->db->GetOne($query, $ph);
        Debug::Arr($user_name_id, 'Unique User Name: ' . $user_name, __FILE__, __LINE__, __METHOD__, 10);

        if ($user_name_id === false) {
            return true;
        } else {
            if ($user_name_id == $this->getId()) {
                return true;
            }
        }

        return false;
    }

    public function checkPassword($password, $check_password_policy = true)
    {
        global $config_vars;

        $password = trim(html_entity_decode($password));

        //Don't bother checking a blank password, this can help avoid issues with LDAP settings.
        if ($password == '') {
            Debug::Text('Password is blank, ignoring...', __FILE__, __LINE__, __METHOD__, 10);
            return false;
        }

        //Check if LDAP is enabled
        $ldap_authentication_type_id = 0;
        if (function_exists('ldap_connect') and !isset($config_vars['other']['enable_ldap']) or (isset($config_vars['other']['enable_ldap']) and $config_vars['other']['enable_ldap'] == true)) {
            //Check company object to make sure LDAP is enabled.
            if (is_object($this->getCompanyObject())) {
                $ldap_authentication_type_id = $this->getCompanyObject()->getLDAPAuthenticationType();
                if ($ldap_authentication_type_id > 0) {
                    $ldap = TTnew('TTLDAP');
                    $ldap->setHost($this->getCompanyObject()->getLDAPHost());
                    $ldap->setPort($this->getCompanyObject()->getLDAPPort());
                    $ldap->setBindUserName($this->getCompanyObject()->getLDAPBindUserName());
                    $ldap->setBindPassword($this->getCompanyObject()->getLDAPBindPassword());
                    $ldap->setBaseDN($this->getCompanyObject()->getLDAPBaseDN());
                    $ldap->setBindAttribute($this->getCompanyObject()->getLDAPBindAttribute());
                    $ldap->setUserFilter($this->getCompanyObject()->getLDAPUserFilter());
                    $ldap->setLoginAttribute($this->getCompanyObject()->getLDAPLoginAttribute());
                    if ($ldap->authenticate($this->getUserName(), $password) === true) {
                        return true;
                    } elseif ($ldap_authentication_type_id == 1) {
                        Debug::Text('LDAP authentication failed, falling back to local password...', __FILE__, __LINE__, __METHOD__, 10);
                        TTLog::addEntry($this->getId(), 510, TTi18n::getText('LDAP Authentication failed, falling back to local password for username') . ': ' . $this->getUserName() . TTi18n::getText('IP Address') . ': ' . Misc::getRemoteIPAddress(), $this->getId(), $this->getTable());
                    }
                    unset($ldap);
                } else {
                    Debug::Text('LDAP authentication is not enabled...', __FILE__, __LINE__, __METHOD__, 10);
                }
            }
        } else {
            Debug::Text('LDAP authentication disabled due to config or extension missing...', __FILE__, __LINE__, __METHOD__, 10);
        }

        $password_version = $this->getPasswordVersion();
        $encrypted_password = $this->encryptPassword($password, $password_version);

        //Don't check local TT passwords if LDAP Only authentication is enabled. Still accept override passwords though.
        if ($ldap_authentication_type_id != 2 and $encrypted_password === $this->getPassword()) {
            //If the passwords match, confirm that the password hasn't exceeded its maximum age.
            //Allow override passwords always.
            if ($check_password_policy == true and $this->isFirstLogin() == true and $this->isCompromisedPassword() == true) { //Need to check for compromised password, as last_login_date doesn't get updated until they can actually login fully.
                Debug::Text('Password Policy: First login, password needs to be changed, denying access...', __FILE__, __LINE__, __METHOD__, 10);
                return false;
            } elseif ($check_password_policy == true and $this->isPasswordPolicyEnabled() == true and $this->isCompromisedPassword() == true) {
                Debug::Text('Password Policy: Password has never changed, denying access...', __FILE__, __LINE__, __METHOD__, 10);
                return false;
            } elseif ($check_password_policy == true and $this->isPasswordPolicyEnabled() == true and $this->checkPasswordAge() == false) {
                Debug::Text('Password Policy: Password exceeds maximum age, denying access...', __FILE__, __LINE__, __METHOD__, 10);
                return false;
            } else {
                //If password version is not the latest, update the password version when it successfully matches.
                if ($password_version < 2) {
                    Debug::Text('Converting password to latest encryption version...', __FILE__, __LINE__, __METHOD__, 10);
                    $this->db->Execute('UPDATE ' . $this->getTable() . ' SET password = ? where id = ?', array('password' => $this->encryptPassword($password), 'id' => (int)$this->getID()));
                    unset($password);
                }

                return true; //Password accepted.
            }
        } elseif (isset($config_vars['other']['override_password_prefix'])
            and $config_vars['other']['override_password_prefix'] != ''
        ) {
            //Check override password
            if ($encrypted_password == $this->encryptPassword(trim(trim($config_vars['other']['override_password_prefix']) . substr($this->getUserName(), 0, 2)), $password_version)) {
                TTLog::addEntry($this->getId(), 510, TTi18n::getText('Override Password successful from IP Address') . ': ' . Misc::getRemoteIPAddress(), null, $this->getTable());
                return true;
            }
        }

        return false;
    }

    public function getCompanyObject()
    {
        return $this->getGenericObject('CompanyListFactory', $this->getCompany(), 'company_obj');
    }

    public function getUserName()
    {
        if (isset($this->data['user_name'])) {
            return $this->data['user_name'];
        }

        return false;
    }

    public function getPasswordVersion($encrypted_password = false)
    {
        if ($encrypted_password == '') {
            $encrypted_password = $this->getPassword();
        }

        $split_password = explode(':', $encrypted_password);
        if (is_array($split_password) and count($split_password) == 2) {
            $version = $split_password[0];
        } else {
            $version = 1;
        }

        return $version;
    }

    public function getPassword()
    {
        if (isset($this->data['password'])) {
            return $this->data['password'];
        }

        return false;
    }

    //Always default to latest password version.

    public function encryptPassword($password, $version = 2)
    {
        $password = trim($password);

        //Handle password migration/versioning
        switch ((int)$version) {
            case 2: //v2
                //Case sensitive, uses sha512 and company/user specific salt.
                //Prepend with password version.
                //
                //IMPORTANT: When creating a new user, the ID must be defined before this is called, otherwise the hash is incorrect.
                //           This manifests itself as an incorrect password when its first created, but can be changed and then starts working.
                //
                $encrypted_password = '2:' . hash('sha512', $this->getPasswordSalt() . (int)$this->getCompany() . (int)$this->getID() . $password);
                break;
            default: //v1
                //Case insensitive, uses sha1 and global salt.
                $encrypted_password = sha1($this->getPasswordSalt() . strtolower($password));
                break;
        }
        unset($password);

        return $encrypted_password;
    }

    public function getPasswordSalt()
    {
        global $config_vars;

        if (isset($config_vars['other']['salt']) and $config_vars['other']['salt'] != '') {
            $retval = $config_vars['other']['salt'];
        } else {
            $retval = 'ttsalt03198238';
        }

        return trim($retval);
    }

    public function isFirstLogin()
    {
        if ($this->getLastLoginDate() == '') {
            Debug::Text('is First Login: TRUE', __FILE__, __LINE__, __METHOD__, 10);
            return true;
        }

        return false;
    }

    public function getLastLoginDate()
    {
        if (isset($this->data['last_login_date'])) {
            return $this->data['last_login_date'];
        }

        return false;
    }

    public function isCompromisedPassword()
    {

        //Check to see if the password was updated at the same time the user record was created originally, or if the password was updated by an administrator.
        //  Either way the password should be considered compromised (someone else knows it) and should be changed.
        if ((int)$this->getPasswordUpdatedDate() <= ((int)$this->getCreatedDate() + 3) or ((int)$this->getUpdatedBy() != (int)$this->getId() and (int)$this->getPasswordUpdatedDate() >= ($this->getUpdatedDate() - 3) and (int)$this->getPasswordUpdatedDate() <= ($this->getUpdatedDate() + 3))) {
            Debug::Text('User hasnt ever changed their password... Last Login Date: ' . TTDate::getDate('DATE+TIME', $this->getLastLoginDate()), __FILE__, __LINE__, __METHOD__, 10);
            return true;
        }

        return false;
    }

    public function getPasswordUpdatedDate()
    {
        if (isset($this->data['password_updated_date'])) {
            return $this->data['password_updated_date'];
        }

        return false;
    }

    public function isPasswordPolicyEnabled()
    {
        return false;
    }

    public function checkPasswordAge()
    {
        $c_obj = $this->getCompanyObject();
        //Always add 1 to the PasswordMaximumAge so if its set to 0 by mistake it will still allow the user to login after changing their password.
        Debug::Text('Password Policy: Type: ' . $c_obj->getPasswordPolicyType() . ' Current Age: ' . TTDate::getDays((time() - $this->getPasswordUpdatedDate())) . '(' . $this->getPasswordUpdatedDate() . ') Maximum Age: ' . $c_obj->getPasswordMaximumAge() . ' days Permission Level: ' . $this->getPermissionLevel(), __FILE__, __LINE__, __METHOD__, 10);
        if ($this->isPasswordPolicyEnabled() == true and (int)$this->getPasswordUpdatedDate() < (time() - (($c_obj->getPasswordMaximumAge() + 1) * 86400))) {
            Debug::Text('Password Policy: Password exceeds maximum age, denying access...', __FILE__, __LINE__, __METHOD__, 10);
            return false;
        }
        return true;
    }

    public function setPhoneId($phone_id)
    {
        $phone_id = trim($phone_id);

        if (
            $phone_id == ''
            or
            (
                $this->Validator->isRegEx('phone_id',
                    $phone_id,
                    TTi18n::gettext('Quick Punch ID must be digits only'),
                    $this->phoneid_validator_regex)
                and
                $this->Validator->isLength('phone_id',
                    $phone_id,
                    TTi18n::gettext('Incorrect Quick Punch ID length'),
                    4,
                    8)
                and
                $this->Validator->isTrue('phone_id',
                    $this->isUniquePhoneId($phone_id),
                    TTi18n::gettext('Quick Punch ID is already in use, please try a different one')
                )
            )
        ) {
            $this->data['phone_id'] = $phone_id;

            return true;
        }

        return false;
    }

    public function isUniquePhoneId($phone_id)
    {
        $ph = array(
            'phone_id' => $phone_id,
        );

        $query = 'select id from ' . $this->getTable() . ' where phone_id = ? and deleted = 0';
        $phone_id = $this->db->GetOne($query, $ph);
        Debug::Arr($phone_id, 'Unique Phone ID:', __FILE__, __LINE__, __METHOD__, 10);

        if ($phone_id === false) {
            return true;
        } else {
            if ($phone_id == $this->getId()) {
                return true;
            }
        }
        return false;
    }

    public function checkPhonePassword($password)
    {
        $password = trim($password);

        if ($password == $this->getPhonePassword()) {
            return true;
        }

        return false;
    }

    public function getPhonePassword()
    {
        if (isset($this->data['phone_password'])) {
            return $this->data['phone_password'];
        }

        return false;
    }

    public function setPhonePassword($phone_password, $force = false)
    {
        $phone_password = trim($phone_password);

        $is_new = $this->isNew(true);

        //Phone passwords are now displayed the administrators to make things easier.
        //NOTE: Phone passwords are used for passwords on the timeclock as well, and need to be able to be cleared sometimes.
        //Limit phone password to max of 9 digits so we don't overflow an integer on the timeclocks. (10 digits, but maxes out at 2billion)
        if ($phone_password == ''
            or (
                $this->Validator->isRegEx('phone_password',
                    $phone_password,
                    TTi18n::gettext('Quick Punch password must be digits only'),
                    $this->phonepassword_validator_regex)
                and
                $this->Validator->isTrue('phone_password',
                    (($force == false and $is_new == true and ($this->getPhoneId() == $phone_password)) ? false : true),
                    TTi18n::gettext('Quick Punch password must be different then Quick Punch ID'))
                and
                $this->Validator->isTrue('phone_password',
                    (($force == false and $is_new == true and ($phone_password == '1234' or $phone_password == '12345' or strlen(count_chars($phone_password, 3)) == 1)) ? false : true),
                    TTi18n::gettext('Quick Punch password is too weak, please try something more secure'))
                and
                $this->Validator->isLength('phone_password',
                    $phone_password,
                    TTi18n::gettext('Quick Punch password must be between 4 and 9 digits'),
                    4,
                    9))
        ) {
            $this->data['phone_password'] = $phone_password;

            return true;
        }

        return false;
    }

    public function getPhoneId()
    {
        if (isset($this->data['phone_id'])) {
            return (string)$this->data['phone_id']; //Should not be cast to INT
        }

        return false;
    }

    public function checkEmployeeNumber($id)
    {
        $id = trim($id);

        //Use employee ID for now.
        //if ( $id == $this->getID() ) {
        if ($id == $this->getEmployeeNumber()) {
            return true;
        }

        return false;
    }

    public function getEmployeeNumber()
    {
        if (isset($this->data['employee_number']) and $this->data['employee_number'] != '') {
            return (int)$this->data['employee_number'];
        }

        return false;
    }

    public function setEmployeeNumber($value)
    {
        $value = $this->Validator->stripNonNumeric(trim($value));

        //Allow setting a blank employee number, so we can use Validate() to check employee number against the status_id
        //To allow terminated employees to have a blank employee number, but active ones always have a number.
        if (
            $value == ''
            or (
                $this->Validator->isNumeric('employee_number',
                    $value,
                    TTi18n::gettext('Employee number must only be digits'))
                and
                $this->Validator->isTrue('employee_number',
                    $this->isUniqueEmployeeNumber($value),
                    TTi18n::gettext('Employee number is already in use, please enter a different one'))
            )
        ) {
            if ($value != '' and $value >= 0) {
                $value = (int)$value;
            }

            $this->data['employee_number'] = $value;

            return true;
        }

        return false;
    }

    public function isUniqueEmployeeNumber($id)
    {
        if ($this->getCompany() == false) {
            return false;
        }

        if ($id == 0) {
            return false;
        }

        $ph = array(
            'manual_id' => (int)$id, //Make sure cast this to an int so we can handle overflows above PHP_MAX_INT properly.
            'company_id' => $this->getCompany(),
        );

        $query = 'select id from ' . $this->getTable() . ' where employee_number = ? AND company_id = ? AND deleted = 0';
        $user_id = $this->db->GetOne($query, $ph);
        Debug::Arr($user_id, 'Unique Employee Number: ' . $id, __FILE__, __LINE__, __METHOD__, 10);

        if ($user_id === false) {
            return true;
        } else {
            if ($user_id == $this->getId()) {
                return true;
            }
        }

        return false;
    }

    public function setTitle($id)
    {
        $id = (int)trim($id);

        $utlf = TTnew('UserTitleListFactory');
        if (
            $id == 0
            or
            $this->Validator->isResultSetWithRows('title',
                $utlf->getByID($id),
                TTi18n::gettext('Title is invalid')
            )
        ) {
            $this->data['title_id'] = $id;

            return true;
        }

        return false;
    }

    public function getDefaultJob()
    {
        if (isset($this->data['default_job_id'])) {
            return (int)$this->data['default_job_id'];
        }

        return false;
    }

    public function setDefaultJob($id)
    {
        $id = trim($id);

        if ($id == false or $id == 0 or $id == '') {
            $id = 0;
        }

        Debug::Text('Default Job ID: ' . $id, __FILE__, __LINE__, __METHOD__, 10);
        $id = 0;

        if (
            $id == 0
            or
            $this->Validator->isResultSetWithRows('default_job_id',
                $jlf->getByID($id),
                TTi18n::gettext('Invalid Default Job')
            )
        ) {
            $this->data['default_job_id'] = $id;

            return true;
        }

        return false;
    }

    public function getDefaultJobItem()
    {
        if (isset($this->data['default_job_item_id'])) {
            return (int)$this->data['default_job_item_id'];
        }

        return false;
    }

    public function setDefaultJobItem($id)
    {
        $id = trim($id);

        if ($id == false or $id == 0 or $id == '') {
            $id = 0;
        }

        Debug::Text('Default Job Item ID: ' . $id, __FILE__, __LINE__, __METHOD__, 10);
        $id = 0;

        if (
            $id == 0
            or
            $this->Validator->isResultSetWithRows('default_job_item_id',
                $jilf->getByID($id),
                TTi18n::gettext('Invalid Default Task')
            )
        ) {
            $this->data['default_job_item_id'] = $id;

            return true;
        }

        return false;
    }

    public function setFirstName($first_name)
    {
        $first_name = ucwords(trim($first_name));

        if ($this->Validator->isRegEx('first_name',
                $first_name,
                TTi18n::gettext('First name contains invalid characters'),
                $this->name_validator_regex)
            and
            $this->Validator->isLength('first_name',
                $first_name,
                TTi18n::gettext('First name is too short or too long'),
                2,
                50)
        ) {
            $this->data['first_name'] = $first_name;
            $this->setFirstNameMetaphone($first_name);

            return true;
        }

        return false;
    }

    public function setFirstNameMetaphone($first_name)
    {
        $first_name = metaphone(trim($first_name));

        if ($first_name != '') {
            $this->data['first_name_metaphone'] = $first_name;

            return true;
        }

        return false;
    }

    public function getFirstNameMetaphone()
    {
        if (isset($this->data['first_name_metaphone'])) {
            return $this->data['first_name_metaphone'];
        }

        return false;
    }

    public function setMiddleName($middle_name)
    {
        $middle_name = ucwords(trim($middle_name));

        if (
            $middle_name == ''
            or
            (
                $this->Validator->isRegEx('middle_name',
                    $middle_name,
                    TTi18n::gettext('Middle name contains invalid characters'),
                    $this->name_validator_regex)
                and
                $this->Validator->isLength('middle_name',
                    $middle_name,
                    TTi18n::gettext('Middle name is too short or too long'),
                    1,
                    50)
            )
        ) {
            $this->data['middle_name'] = $middle_name;

            return true;
        }


        return false;
    }

    public function setLastName($last_name)
    {
        $last_name = ucwords(trim($last_name));

        if ($this->Validator->isRegEx('last_name',
                $last_name,
                TTi18n::gettext('Last name contains invalid characters'),
                $this->name_validator_regex)
            and
            $this->Validator->isLength('last_name',
                $last_name,
                TTi18n::gettext('Last name is too short or too long'),
                2,
                50)
        ) {
            $this->data['last_name'] = $last_name;
            $this->setLastNameMetaphone($last_name);

            return true;
        }

        return false;
    }

    public function setLastNameMetaphone($last_name)
    {
        $last_name = metaphone(trim($last_name));

        if ($last_name != '') {
            $this->data['last_name_metaphone'] = $last_name;

            return true;
        }

        return false;
    }

    public function getLastNameMetaphone()
    {
        if (isset($this->data['last_name_metaphone'])) {
            return $this->data['last_name_metaphone'];
        }

        return false;
    }

    public function getSecondLastName()
    {
        if (isset($this->data['second_last_name'])) {
            return $this->data['second_last_name'];
        }

        return false;
    }

    public function setSecondLastName($second_last_name)
    {
        if (
            $second_last_name == ''
            or
            (
                $this->Validator->isRegEx('second_last_name',
                    $second_last_name,
                    TTi18n::gettext('Second last name contains invalid characters'),
                    $this->name_validator_regex)
                and
                $this->Validator->isLength('second_last_name',
                    $second_last_name,
                    TTi18n::gettext('Second last name is too short or too long'),
                    2,
                    50)
            )
        ) {
            $this->data['second_last_name'] = $second_last_name;

            return true;
        }

        return false;
    }

    public function setAddress1($address1)
    {
        $address1 = trim($address1);

        if (
            $address1 == ''
            or
            (
                $this->Validator->isRegEx('address1',
                    $address1,
                    TTi18n::gettext('Address1 contains invalid characters'),
                    $this->address_validator_regex)
                and
                $this->Validator->isLength('address1',
                    $address1,
                    TTi18n::gettext('Address1 is too short or too long'),
                    2,
                    250)
            )
        ) {
            $this->data['address1'] = $address1;

            return true;
        }

        return false;
    }

    public function setAddress2($address2)
    {
        $address2 = trim($address2);

        if ($address2 == ''
            or
            (
                $this->Validator->isRegEx('address2',
                    $address2,
                    TTi18n::gettext('Address2 contains invalid characters'),
                    $this->address_validator_regex)
                and
                $this->Validator->isLength('address2',
                    $address2,
                    TTi18n::gettext('Address2 is too short or too long'),
                    2,
                    250))
        ) {
            $this->data['address2'] = $address2;

            return true;
        }

        return false;
    }

    public function setCity($city)
    {
        $city = trim($city);

        if (
            $city == ''
            or
            (
                $this->Validator->isRegEx('city',
                    $city,
                    TTi18n::gettext('City contains invalid characters'),
                    $this->city_validator_regex)
                and
                $this->Validator->isLength('city',
                    $city,
                    TTi18n::gettext('City name is too short or too long'),
                    2,
                    250)
            )
        ) {
            $this->data['city'] = $city;

            return true;
        }

        return false;
    }

    public function setCountry($country)
    {
        $country = trim($country);

        $cf = TTnew('CompanyFactory');

        if ($this->Validator->inArrayKey('country',
            $country,
            TTi18n::gettext('Invalid Country'),
            $cf->getOptions('country'))
        ) {
            $this->data['country'] = $country;

            return true;
        }

        return false;
    }

    public function setPostalCode($postal_code)
    {
        $postal_code = strtoupper($this->Validator->stripSpaces($postal_code));

        if (
            $postal_code == ''
            or
            (
                $this->Validator->isPostalCode('postal_code',
                    $postal_code,
                    TTi18n::gettext('Postal/ZIP Code contains invalid characters, invalid format, or does not match Province/State'),
                    $this->getCountry(), $this->getProvince())
                and
                $this->Validator->isLength('postal_code',
                    $postal_code,
                    TTi18n::gettext('Postal/ZIP Code is too short or too long'),
                    1,
                    10)
            )
        ) {
            $this->data['postal_code'] = $postal_code;

            return true;
        }

        return false;
    }

    public function getCountry()
    {
        if (isset($this->data['country'])) {
            return $this->data['country'];
        }

        return false;
    }

    public function getProvince()
    {
        if (isset($this->data['province'])) {
            return $this->data['province'];
        }

        return false;
    }

    public function getLongitude()
    {
        if (isset($this->data['longitude'])) {
            return (float)$this->data['longitude'];
        }

        return false;
    }

    public function setLongitude($value)
    {
        $value = TTi18n::parseFloat($value);

        if ($value == 0
            or
            $this->Validator->isFloat('longitude',
                $value,
                TTi18n::gettext('Longitude is invalid')
            )
        ) {
            $this->data['longitude'] = number_format($value, 6); //Always use 6 decimal places as that is to 0.11m accuracy, this also prevents audit logging 0 vs 0.000000000

            return true;
        }

        return false;
    }

    public function getLatitude()
    {
        if (isset($this->data['latitude'])) {
            return (float)$this->data['latitude'];
        }

        return false;
    }

    public function setLatitude($value)
    {
        $value = TTi18n::parseFloat($value);

        if ($value == 0
            or
            $this->Validator->isFloat('latitude',
                $value,
                TTi18n::gettext('Latitude is invalid')
            )
        ) {
            $this->data['latitude'] = number_format($value, 6); //Always use 6 decimal places as that is to 0.11m accuracy, this also prevents audit logging 0 vs 0.000000000

            return true;
        }

        return false;
    }

    public function setWorkPhone($work_phone)
    {
        $work_phone = trim($work_phone);

        if (
            $work_phone == ''
            or
            $this->Validator->isPhoneNumber('work_phone',
                $work_phone,
                TTi18n::gettext('Work phone number is invalid'))
        ) {
            $this->data['work_phone'] = $work_phone;

            return true;
        }

        return false;
    }

    public function getWorkPhoneExt()
    {
        if (isset($this->data['work_phone_ext'])) {
            return $this->data['work_phone_ext'];
        }

        return false;
    }

    public function setWorkPhoneExt($work_phone_ext)
    {
        $work_phone_ext = $this->Validator->stripNonNumeric(trim($work_phone_ext));

        if ($work_phone_ext == ''
            or $this->Validator->isLength('work_phone_ext',
                $work_phone_ext,
                TTi18n::gettext('Work phone number extension is too short or too long'),
                2,
                10)
        ) {
            $this->data['work_phone_ext'] = $work_phone_ext;

            return true;
        }

        return false;
    }

    public function setHomePhone($home_phone)
    {
        $home_phone = trim($home_phone);

        if ($home_phone == ''
            or
            $this->Validator->isPhoneNumber('home_phone',
                $home_phone,
                TTi18n::gettext('Home phone number is invalid'))
        ) {
            $this->data['home_phone'] = $home_phone;

            return true;
        }

        return false;
    }

    public function getMobilePhone()
    {
        if (isset($this->data['mobile_phone'])) {
            return $this->data['mobile_phone'];
        }

        return false;
    }

    public function setMobilePhone($mobile_phone)
    {
        $mobile_phone = trim($mobile_phone);

        if ($mobile_phone == ''
            or $this->Validator->isPhoneNumber('mobile_phone',
                $mobile_phone,
                TTi18n::gettext('Mobile phone number is invalid'))
        ) {
            $this->data['mobile_phone'] = $mobile_phone;

            return true;
        }

        return false;
    }

    public function getFaxPhone()
    {
        if (isset($this->data['fax_phone'])) {
            return $this->data['fax_phone'];
        }

        return false;
    }

    public function setFaxPhone($fax_phone)
    {
        $fax_phone = trim($fax_phone);

        if ($fax_phone == ''
            or $this->Validator->isPhoneNumber('fax_phone',
                $fax_phone,
                TTi18n::gettext('Fax phone number is invalid'))
        ) {
            $this->data['fax_phone'] = $fax_phone;

            return true;
        }

        return false;
    }

    public function setHomeEmail($home_email)
    {
        $home_email = trim($home_email);

        $modify_email = false;
        if ($this->getCurrentUserPermissionLevel() >= $this->getPermissionLevel()) {
            $modify_email = true;
        } elseif ($this->getHomeEmail() == $home_email) { //No modification made.
            $modify_email = true;
        }

        $error_threshold = 7; //No DNS checks.
        if (PRODUCTION === true) {
            $error_threshold = 0; //DNS checks on email address.
        }
        if (($home_email == ''
                or $this->Validator->isEmailAdvanced('home_email',
                    $home_email,
                    TTi18n::gettext('Home Email address is invalid'),
                    $error_threshold)
            )
            and
            $this->Validator->isTrue('home_email',
                $modify_email,
                TTi18n::gettext('Insufficient access to modify home email for this employee')
            )
        ) {
            $this->data['home_email'] = $home_email;
            $this->setEnableClearPasswordResetData(true); //Clear any outstanding password reset key to prevent unexpected changes later on.

            return true;
        }

        return false;
    }

    public function getHomeEmail()
    {
        if (isset($this->data['home_email'])) {
            return $this->data['home_email'];
        }

        return false;
    }

    public function setEnableClearPasswordResetData($value = true)
    {
        $this->tmp_data['enable_clear_password_reset_data'] = $value;
        return true;
    }

    public function getHomeEmailIsValid()
    {
        return $this->fromBool($this->data['home_email_is_valid']);
    }

    public function setHomeEmailIsValid($bool)
    {
        $this->data['home_email_is_valid'] = $this->toBool($bool);

        return true;
    }

    public function getHomeEmailIsValidDate()
    {
        if (isset($this->data['home_email_is_valid_date'])) {
            return $this->data['home_email_is_valid_date'];
        }
    }

    public function setWorkEmail($work_email)
    {
        $work_email = trim($work_email);

        $modify_email = false;
        if ($this->getCurrentUserPermissionLevel() >= $this->getPermissionLevel()) {
            $modify_email = true;
        } elseif ($this->getWorkEmail() == $work_email) { //No modification made.
            $modify_email = true;
        }

        $error_threshold = 7; //No DNS checks.
        if (PRODUCTION === true) {
            $error_threshold = 0; //DNS checks on email address.
        }
        if (($work_email == ''
                or $this->Validator->isEmailAdvanced('work_email',
                    $work_email,
                    TTi18n::gettext('Work Email address is invalid'),
                    $error_threshold)
            )
            and
            $this->Validator->isTrue('work_email',
                $modify_email,
                TTi18n::gettext('Insufficient access to modify work email for this employee')
            )
        ) {
            $this->data['work_email'] = $work_email;
            $this->setEnableClearPasswordResetData(true); //Clear any outstanding password reset key to prevent unexpected changes later on.

            return true;
        }

        return false;
    }

    public function getWorkEmail()
    {
        if (isset($this->data['work_email'])) {
            return $this->data['work_email'];
        }

        return false;
    }

    public function getWorkEmailIsValid()
    {
        return $this->fromBool($this->data['work_email_is_valid']);
    }

    public function setWorkEmailIsValid($bool)
    {
        $this->data['work_email_is_valid'] = $this->toBool($bool);

        return true;
    }

    public function getWorkEmailIsValidDate()
    {
        if (isset($this->data['work_email_is_valid_date'])) {
            return $this->data['work_email_is_valid_date'];
        }
    }

    public function getAge()
    {
        return round(TTDate::getYearDifference($this->getBirthDate(), TTDate::getTime()), 1);
    }

    public function getBirthDate()
    {
        if (isset($this->data['birth_date'])) {
            return $this->data['birth_date'];
        }

        return false;
    }

    public function setBirthDate($epoch)
    {
        if (($epoch !== false and $epoch == '')
            or
            (
                $this->Validator->isDate('birth_date',
                    $epoch,
                    TTi18n::gettext('Birth date is invalid, try specifying the year with four digits'))
                and
                $this->Validator->isTRUE('birth_date',
                    (TTDate::getMiddleDayEpoch($epoch) <= TTDate::getMiddleDayEpoch(time())) ? true : false,
                    TTi18n::gettext('Birth date can not be in the future'))
            )
        ) {

            //Allow for negative epochs, for birthdates less than 1960's
            $this->data['birth_date'] = ($epoch != 0 and $epoch != '') ? TTDate::getMiddleDayEpoch($epoch) : ''; //Allow blank birthdate.

            return true;
        }

        return false;
    }

    public function setHireDate($epoch)
    {
        //Hire Date should be assumed to be the beginning of the day. (inclusive)
        //Termination Date should be assumed to be the end of the day. (inclusive)
        //So if an employee is hired and terminated on the same day, and is salary, they should get one day pay.
        //FIXME: Save hire date as getMiddleDayEpoch(), but change all use cases to force it to beginning of the day when comparisons are made on it.
        //       Save termination date as getMiddleDayEpoch(), but change all use cases to force it to use the end of the day when comparisons are made on it.
        //		 Alternatively, switch it to use date_stamp datatype, and use >= or <= operators.

        //( $epoch !== FALSE AND $epoch == '' ) //Check for strict FALSE causes data from UserDefault to fail if its not set.
        if (($epoch == '')
            or
            (
                $this->Validator->isDate('hire_date',
                    $epoch,
                    TTi18n::gettext('Hire date is invalid'))
                and
                $this->Validator->isTrue('hire_date',
                    $this->isValidWageForHireDate($epoch),
                    TTi18n::gettext('Hire date must be on or after the employees first wage entry, you may need to change their wage effective date first'))
            )
        ) {

            //Use the beginning of the day epoch, so accrual policies that apply on the hired date still work.
            $this->data['hire_date'] = TTDate::getBeginDayEpoch($epoch);

            return true;
        }

        return false;
    }

    public function isValidWageForHireDate($epoch)
    {
        if ($this->getID() > 0 and $epoch != '') {
            $uwlf = TTnew('UserWageListFactory');

            //Check to see if any wage entries exist for this employee
            $uwlf->getLastWageByUserId($this->getID());
            if ($uwlf->getRecordCount() >= 1) {
                Debug::Text('No wage entries exist...', __FILE__, __LINE__, __METHOD__, 10);

                $uwlf->getByUserIdAndGroupIDAndBeforeDate($this->getID(), 0, $epoch, 1);
                if ($uwlf->getRecordCount() == 0) {
                    Debug::Text('No wage entry on or before : ' . TTDate::getDate('DATE+TIME', $epoch), __FILE__, __LINE__, __METHOD__, 10);
                    return false;
                }
            }
        }

        return true;
    }

    public function setTerminationDate($epoch)
    {
        //Hire Date should be assumed to be the beginning of the day. (inclusive)
        //Termination Date should be assumed to be the end of the day. (inclusive)
        //So if an employee is hired and terminated on the same day, and is salary, they should get one day pay.
        //FIXME: Save hire date as getMiddleDayEpoch(), but change all use cases to force it to beginning of the day when comparisons are made on it.
        //       Save termination date as getMiddleDayEpoch(), but change all use cases to force it to use the end of the day when comparisons are made on it.
        //		 Alternatively, switch it to use date_stamp datatype, and use >= or <= operators.

        if (($epoch == '')
            or
            $this->Validator->isDate('termination_date',
                $epoch,
                TTi18n::gettext('Termination date is invalid'))
        ) {
            if ($epoch == '') {
                $epoch = null; //Force to NULL if no termination date is set, this prevents "0" from being entered and causing problems with "is NULL" SQL queries.
            }
            $this->data['termination_date'] = $epoch;

            return true;
        }

        return false;
    }

    public function setLastLoginDate($epoch)
    {
        if (($epoch == '')
            or
            $this->Validator->isDate('last_login_date',
                $epoch,
                TTi18n::gettext('Last Login date is invalid'))
        ) {
            if ($epoch == '') {
                $epoch = null; //Force to NULL if no termination date is set, this prevents "0" from being entered and causing problems with "is NULL" SQL queries.
            }
            $this->data['last_login_date'] = $epoch;

            return true;
        }

        return false;
    }

    public function setCurrency($id)
    {
        $id = trim($id);

        Debug::Text('Currency ID: ' . $id, __FILE__, __LINE__, __METHOD__, 10);
        $culf = TTnew('CurrencyListFactory');

        if (
        $this->Validator->isResultSetWithRows('currency_id',
            $culf->getByID($id),
            TTi18n::gettext('Invalid currency')
        )
        ) {
            $this->data['currency_id'] = $id;

            return true;
        }

        return false;
    }

    public function getSecureSIN($sin = null)
    {
        if ($sin == '') {
            $sin = $this->getSIN();
        }
        if ($sin != '') {
            //Grab the first 1, and last 4 digits.
            $first_four = substr($sin, 0, 1);
            $last_four = substr($sin, -4);

            $total = (strlen($sin) - 5);

            $retval = $first_four . str_repeat('X', $total) . $last_four;

            return $retval;
        }

        return false;
    }

    public function getSIN()
    {
        if (isset($this->data['sin'])) {
            return $this->data['sin'];
        }

        return false;
    }

    public function setSIN($sin)
    {
        //If *'s are in the SIN number, skip setting it
        //This allows them to change other data without seeing the SIN number.
        if (stripos($sin, 'X') !== false) {
            return false;
        }

        $sin = $this->Validator->stripNonNumeric(trim($sin));

        if (
            $sin == ''
            or
            $this->Validator->isSIN('sin',
                $sin,
                TTi18n::gettext('SIN/SSN is invalid'),
                $this->getCountry())
        ) {
            $this->data['sin'] = $sin;

            return true;
        }

        return false;
    }

    public function getOtherID1()
    {
        if (isset($this->data['other_id1'])) {
            return $this->data['other_id1'];
        }

        return false;
    }

    public function setOtherID1($value)
    {
        $value = trim($value);

        if ($value == ''
            or
            $this->Validator->isLength('other_id1',
                $value,
                TTi18n::gettext('Other ID 1 is invalid'),
                1, 255)
        ) {
            $this->data['other_id1'] = $value;

            return true;
        }

        return false;
    }

    public function getOtherID2()
    {
        if (isset($this->data['other_id2'])) {
            return $this->data['other_id2'];
        }

        return false;
    }

    public function setOtherID2($value)
    {
        $value = trim($value);

        if ($value == ''
            or
            $this->Validator->isLength('other_id2',
                $value,
                TTi18n::gettext('Other ID 2 is invalid'),
                1, 255)
        ) {
            $this->data['other_id2'] = $value;

            return true;
        }

        return false;
    }

    public function getOtherID3()
    {
        if (isset($this->data['other_id3'])) {
            return $this->data['other_id3'];
        }

        return false;
    }

    public function setOtherID3($value)
    {
        $value = trim($value);

        if ($value == ''
            or
            $this->Validator->isLength('other_id3',
                $value,
                TTi18n::gettext('Other ID 3 is invalid'),
                1, 255)
        ) {
            $this->data['other_id3'] = $value;

            return true;
        }

        return false;
    }

    public function getOtherID4()
    {
        if (isset($this->data['other_id4'])) {
            return $this->data['other_id4'];
        }

        return false;
    }

    public function setOtherID4($value)
    {
        $value = trim($value);

        if ($value == ''
            or
            $this->Validator->isLength('other_id4',
                $value,
                TTi18n::gettext('Other ID 4 is invalid'),
                1, 255)
        ) {
            $this->data['other_id4'] = $value;

            return true;
        }

        return false;
    }

    public function getOtherID5()
    {
        if (isset($this->data['other_id5'])) {
            return $this->data['other_id5'];
        }

        return false;
    }

    public function setOtherID5($value)
    {
        $value = trim($value);

        if ($value == ''
            or
            $this->Validator->isLength('other_id5',
                $value,
                TTi18n::gettext('Other ID 5 is invalid'),
                1, 255)
        ) {
            $this->data['other_id5'] = $value;

            return true;
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

    public function setNote($value)
    {
        $value = trim($value);

        if ($value == ''
            or
            $this->Validator->isLength('note',
                $value,
                TTi18n::gettext('Note is too long'),
                1,
                2048)
        ) {
            $this->data['note'] = $value;

            return true;
        }

        return false;
    }

    public function checkPasswordResetKey($key)
    {
        if ($this->getPasswordResetDate() != ''
            and $this->getPasswordResetDate() > (time() - 7200)
            and $this->getPasswordResetKey() == $key
        ) {
            return true;
        }

        return false;
    }

    public function getPasswordResetDate()
    {
        if (isset($this->data['password_reset_date'])) {
            return $this->data['password_reset_date'];
        }
    }

    public function getPasswordResetKey()
    {
        if (isset($this->data['password_reset_key'])) {
            return $this->data['password_reset_key'];
        }

        return false;
    }

    public function sendValidateEmail($type = 'work')
    {
        if ($this->getHomeEmail() != false
            or $this->getWorkEmail() != false
        ) {
            if ($this->getWorkEmail() != false and $type == 'work') {
                $primary_email = $this->getWorkEmail();
            } elseif ($this->getHomeEmail() != false and $type == 'home') {
                $primary_email = $this->getHomeEmail();
            } else {
                Debug::text('ERROR: Home/Work email not defined or matching type, unable to send validation email...', __FILE__, __LINE__, __METHOD__, 10);
                return false;
            }

            if ($type == 'work') {
                $this->setWorkEmailIsValidKey(md5(Misc::getUniqueID()));
                $this->setWorkEmailIsValidDate(time());
                $email_is_valid_key = $this->getWorkEmailIsValidKey();
            } else {
                $this->setHomeEmailIsValidKey(md5(Misc::getUniqueID()));
                $this->setHomeEmailIsValidDate(time());
                $email_is_valid_key = $this->getHomeEmailIsValidKey();
            }

            if ($this->isValid()) {
                $this->Save(false);

                $subject = APPLICATION_NAME . ' - ' . TTi18n::gettext('Confirm email address');

                $body = '<html><body>';
                $body .= TTi18n::gettext('The email address %1 has been added to your %2 account', array($primary_email, APPLICATION_NAME)) . ', ';
                $body .= ' <a href="' . Misc::getURLProtocol() . '://' . Misc::getHostName() . Environment::getBaseURL() . 'ConfirmEmail.php?action:confirm_email=1&email=' . $primary_email . '&key=' . $email_is_valid_key . '">' . TTi18n::gettext('please click here to confirm and activate this email address') . '</a>.';
                $body .= '<br><br>';
                $body .= '--<br>';
                $body .= APPLICATION_NAME;
                $body .= '</body></html>';

                TTLog::addEntry($this->getId(), 500, TTi18n::getText('Employee email confirmation sent for') . ': ' . $primary_email, null, $this->getTable());

                $headers = array(
                    'From' => '"' . APPLICATION_NAME . ' - ' . TTi18n::gettext('Email Confirmation') . '" <' . Misc::getEmailLocalPart() . '@' . Misc::getEmailDomain() . '>',
                    'Subject' => $subject,
                );

                $mail = new TTMail();
                $mail->setTo(Misc::formatEmailAddress($primary_email, $this));
                $mail->setHeaders($headers);

                @$mail->getMIMEObject()->setHTMLBody($body);

                $mail->setBody($mail->getMIMEObject()->get($mail->default_mime_config));
                $retval = $mail->Send();

                return $retval;
            }
        }

        return false;
    }

    public function setWorkEmailIsValidKey($value)
    {
        $value = trim($value);

        if ($value == ''
            or
            $this->Validator->isLength('work_email_is_valid_key',
                $value,
                TTi18n::gettext('Email validation key is invalid'),
                1, 255)
        ) {
            $this->data['work_email_is_valid_key'] = $value;

            return true;
        }

        return false;
    }

    public function setWorkEmailIsValidDate($epoch)
    {
        if (empty($epoch)) {
            $epoch = null;
        }

        if ($epoch == ''
            or
            $this->Validator->isDate('work_email_is_valid_date',
                $epoch,
                TTi18n::gettext('Email validation date is invalid'))
        ) {
            $this->data['work_email_is_valid_date'] = $epoch;

            return true;
        }

        return false;
    }

    public function getWorkEmailIsValidKey()
    {
        if (isset($this->data['work_email_is_valid_key'])) {
            return $this->data['work_email_is_valid_key'];
        }

        return false;
    }

    public function setHomeEmailIsValidKey($value)
    {
        $value = trim($value);

        if ($value == ''
            or
            $this->Validator->isLength('home_email_is_valid_key',
                $value,
                TTi18n::gettext('Email validation key is invalid'),
                1, 255)
        ) {
            $this->data['home_email_is_valid_key'] = $value;

            return true;
        }

        return false;
    }

    public function setHomeEmailIsValidDate($epoch)
    {
        if (empty($epoch)) {
            $epoch = null;
        }

        if ($epoch == ''
            or
            $this->Validator->isDate('home_email_is_valid_date',
                $epoch,
                TTi18n::gettext('Email validation date is invalid'))
        ) {
            $this->data['home_email_is_valid_date'] = $epoch;

            return true;
        }

        return false;
    }

    public function getHomeEmailIsValidKey()
    {
        if (isset($this->data['home_email_is_valid_key'])) {
            return $this->data['home_email_is_valid_key'];
        }

        return false;
    }

    public function sendPasswordResetEmail()
    {
        if ($this->getHomeEmail() != false
            or $this->getWorkEmail() != false
        ) {
            if ($this->getWorkEmail() != false) {
                $primary_email = $this->getWorkEmail();
                if ($this->getHomeEmail() != false) {
                    $secondary_email = $this->getHomeEmail();
                } else {
                    $secondary_email = null;
                }
            } else {
                $primary_email = $this->getHomeEmail();
                $secondary_email = null;
            }

            $this->setPasswordResetKey(md5(Misc::getUniqueID()));
            $this->setPasswordResetDate(time());
            if ($this->isValid()) {
                $this->Save(false);
                $subject = APPLICATION_NAME . ' ' . TTi18n::gettext('password reset requested at') . ' ' . TTDate::getDate('DATE+TIME', time()) . ' ' . TTi18n::gettext('from') . ' ' . Misc::getRemoteIPAddress();
                $body = '<html><body>';
                $body .= TTi18n::gettext('A password reset has been requested for') . ' "' . $this->getUserName() . '", ';
                //			$body .= ' <a href="'. Misc::getURLProtocol() .'://'.Misc::getHostName().Environment::getBaseURL() .'ForgotPassword.php?action:password_reset=1&key='. $this->getPasswordResetKey().'">'. TTi18n::gettext('please click here to reset your password now') .'</a>.';
                $body .= ' <a href="' . Misc::getURLProtocol() . '://' . Misc::getHostName() . Environment::getBaseURL() . 'html5/#!sm=ResetPassword&key=' . $this->getPasswordResetKey() . '">' . TTi18n::gettext('please click here to reset your password now') . '</a>.';
                $body .= '<br><br>';
                $body .= TTi18n::gettext('If you did not request your password to be reset, you may ignore this email.');
                $body .= '<br><br>';
                $body .= '--<br>';
                $body .= APPLICATION_NAME;
                $body .= '</body></html>';

                //Don't record the reset key in the audit log for security reasons.
                TTLog::addEntry($this->getId(), 500, TTi18n::getText('Employee Password Reset By') . ': ' . Misc::getRemoteIPAddress(), null, $this->getTable());

                $headers = array(
                    'From' => '"' . APPLICATION_NAME . ' - ' . TTi18n::gettext('Password Reset') . '" <' . Misc::getEmailLocalPart() . '@' . Misc::getEmailDomain() . '>',
                    'Subject' => $subject,
                    'Cc' => Misc::formatEmailAddress($secondary_email, $this),
                );

                $mail = new TTMail();
                $mail->setTo(Misc::formatEmailAddress($primary_email, $this));
                $mail->setHeaders($headers);

                @$mail->getMIMEObject()->setHTMLBody($body);

                $mail->setBody($mail->getMIMEObject()->get($mail->default_mime_config));
                $retval = $mail->Send();
                return $retval;
            }
        }

        return false;
    }

    public function setPasswordResetKey($value)
    {
        $value = trim($value);

        if ($value == ''
            or
            $this->Validator->isLength('password_reset_key',
                $value,
                TTi18n::gettext('Password reset key is invalid'),
                1, 255)
        ) {
            $this->data['password_reset_key'] = $value;

            return true;
        }

        return false;
    }

    public function setPasswordResetDate($epoch)
    {
        if (empty($epoch)) {
            $epoch = null;
        }

        if ($epoch == ''
            or
            $this->Validator->isDate('password_reset_date',
                $epoch,
                TTi18n::gettext('Password reset date is invalid'))
        ) {
            $this->data['password_reset_date'] = $epoch;

            return true;
        }

        return false;
    }

    public function isPhotoExists()
    {
        return file_exists($this->getPhotoFileName());
    }

    public function getPhotoFileName($company_id = null, $user_id = null, $include_default_photo = true)
    {
        //Test for both jpg and png
        $base_name = $this->getStoragePath($company_id) . DIRECTORY_SEPARATOR . $user_id;
        if (file_exists($base_name . '.jpg')) {
            $photo_file_name = $base_name . '.jpg';
        } elseif (file_exists($base_name . '.png')) {
            $photo_file_name = $base_name . '.png';
        } elseif (file_exists($base_name . '.img')) {
            $photo_file_name = $base_name . '.img';
        } else {
            if ($include_default_photo == true) {
                //$photo_file_name = Environment::getImagesPath().'unknown_photo.png';
                $photo_file_name = Environment::getImagesPath() . 's.gif';
            } else {
                return false;
            }
        }

        //Debug::Text('Logo File Name: '. $photo_file_name .' Base Name: '. $base_name .' User ID: '. $user_id .' Include Default: '. (int)$include_default_photo, __FILE__, __LINE__, __METHOD__, 10);
        return $photo_file_name;
    }

    public function getStoragePath($company_id = null, $user_id = null)
    {
        if ($company_id == '') {
            $company_id = $this->getID();
        }

        if ($company_id == '') {
            return false;
        }

        return Environment::getStorageBasePath() . DIRECTORY_SEPARATOR . 'user_photo' . DIRECTORY_SEPARATOR . $company_id;
    }

    public function cleanStoragePath($company_id = null, $user_id = null)
    {
        if ($company_id == '') {
            $company_id = $this->getCompany();
        }

        if ($company_id == '') {
            return false;
        }

        $dir = $this->getStoragePath($company_id) . DIRECTORY_SEPARATOR;
        if ($dir != '') {
            if ($user_id != '') {
                @unlink($this->getPhotoFileName($company_id, $user_id, false)); //Delete just users photo.
            } else {
                //Delete tmp files.
                foreach (glob($dir . '*') as $filename) {
                    unlink($filename);
                }
            }
        }

        return true;
    }

    public function setTag($tags)
    {
        $tags = trim($tags);

        //Save the tags in temporary memory to be committed in postSave()
        $this->tmp_data['tags'] = $tags;

        return true;
    }

    public function isInformationComplete()
    {
        //Make sure the users information is all complete.
        //No longer check for SIN, as employees can't change it anyways.
        //Don't check for postal code, as some countries don't have that.
        if ($this->getAddress1() == ''
            or $this->getCity() == ''
            or $this->getHomePhone() == ''
        ) {
            Debug::text('User Information is NOT Complete: ', __FILE__, __LINE__, __METHOD__, 10);
            return false;
        }

        Debug::text('User Information is Complete: ', __FILE__, __LINE__, __METHOD__, 10);
        return true;
    }

    public function getAddress1()
    {
        if (isset($this->data['address1'])) {
            return $this->data['address1'];
        }

        return false;
    }

    public function getCity()
    {
        if (isset($this->data['city'])) {
            return $this->data['city'];
        }

        return false;
    }

    public function getHomePhone()
    {
        if (isset($this->data['home_phone'])) {
            return $this->data['home_phone'];
        }

        return false;
    }

    public function Validate($ignore_warning = true)
    {
        //When doing a mass edit of employees, user name is never specified, so we need to avoid this validation issue.
        if ($this->getUserName() == '') {
            $this->Validator->isTrue('user_name',
                false,
                TTi18n::gettext('User name not specified'));
        }

        //Re-validate the province just in case the country was set AFTER the province.
        $this->setProvince($this->getProvince());

        if ($this->getCompany() == false) {
            $this->Validator->isTrue('company',
                false,
                TTi18n::gettext('Company is invalid'));
        }

        //When mass editing, don't require currency to be set.
        if ($this->Validator->getValidateOnly() == false and $this->getCurrency() == false) {
            $this->Validator->isTrue('currency_id',
                false,
                TTi18n::gettext('Invalid currency'));
        }

        if ($this->getTerminationDate() != '' and $this->getHireDate() != '' and TTDate::getBeginDayEpoch($this->getTerminationDate()) < TTDate::getBeginDayEpoch($this->getHireDate())) {
            $this->Validator->isTrue('termination_date',
                false,
                TTi18n::gettext('Termination date is before hire date, consider removing the termination date entirely for re-hires'));
        }

        //Need to require password on new employees as the database column is NOT NULL.
        //However when mass editing, no IDs are set so this always fails during the only validation phase.
        if ($this->Validator->getValidateOnly() == false and $this->isNew(true) == true and ($this->getPassword() == false or $this->getPassword() == '')) {
            $this->setPassword(uniqid($this->getPasswordSalt(), true)); //Default to just some random password instead of making the user decide.
            //$this->Validator->isTrue(		'password',
            //								FALSE,
            //								TTi18n::gettext('Please specify a password'));
        }

        if ($this->Validator->getValidateOnly() == false and $this->getEmployeeNumber() == false and $this->getStatus() == 10) {
            $this->Validator->isTrue('employee_number',
                false,
                TTi18n::gettext('Employee number must be specified for ACTIVE employees'));
        }

        global $current_user;
        if (is_object($current_user) and $current_user->getID() == $this->getID()) { //Acting on currently logged in user.
            if ($this->getDeleted() == true) {
                $this->Validator->isTrue('user_name',
                    false,
                    TTi18n::gettext('Unable to delete your own record'));
            }

            if ($this->getStatus() != 10) {
                $this->Validator->isTrue('status_id',
                    false,
                    TTi18n::gettext('Unable to change status of your own record'));
            }
        }

        if ($ignore_warning == false) {
            if ($this->getStatus() == 10 and $this->getTerminationDate() != '' and TTDate::getMiddleDayEpoch($this->getTerminationDate()) < TTDate::getMiddleDayEpoch(time())) {
                $this->Validator->Warning('termination_date', TTi18n::gettext('Employee is active but has a termination date in the past, perhaps their status should be Terminated?'));
            }

            if ($this->getStatus() == 20 and $this->getTerminationDate() == '') { //Terminated
                $this->Validator->Warning('termination_date', TTi18n::gettext('Employee is Terminated, but no termination date is specified'));
            }

            if ($this->getStatus() == 20 and $this->getTerminationDate() != '') { //Terminated
                if (TTDate::getMiddleDayEpoch($this->getTerminationDate()) < TTDate::getMiddleDayEpoch(time())) {
                    $this->Validator->Warning('termination_date', TTi18n::gettext('When setting a termination date retroactively, you may need to recalculate this employees timesheet'));
                }

                if ($this->isNew() == false) {
                    //Check to see if worked/absence time exist after termination
                    $udtlf = TTnew('UserDateTotalListFactory');
                    $udtlf->getByCompanyIDAndUserIdAndObjectTypeAndStartDateAndEndDate($this->getCompany(), $this->getID(), array(10, 50), ($this->getTerminationDate() + 86400), (time() + (86400 * 365)));
                    if ($udtlf->getRecordCount() > 0) {
                        $this->Validator->Warning('termination_date', TTi18n::gettext('Employee has time on their timesheet after their termination date that may be ignored (%1)', array(TTDate::getDate('DATE', $udtlf->getCurrent()->getDateStamp()))));
                    }
                    unset($udtlf);

                    //Check to see if Pay Stub Amendments exists after termination date
                    $psalf = TTnew('PayStubAmendmentListFactory');
                    $psalf->getByUserIdAndAuthorizedAndStartDateAndEndDate($this->getID(), true, ($this->getTerminationDate() + 86400), (time() + (86400 * 365)));
                    if ($psalf->getRecordCount() > 0) {
                        $this->Validator->Warning('termination_date', TTi18n::gettext('Employee has pay stub amendments effective after their termination date that may be ignored (%1)', array(TTDate::getDate('DATE', $psalf->getCurrent()->getEffectiveDate()))));
                    }
                    unset($psalf);
                }
            }

            //Check for duplicate email addresses and warn about possible account lock-out due to password reset functionality being disabled.
            if ($this->isUniqueWorkEmail($this->getWorkEmail()) == false) {
                $this->Validator->Warning('work_email', TTi18n::gettext('Work email address is assigned to another employee, continuing will disable password reset functionality and may result in account lock-out'));
            }
            if ($this->isUniqueHomeEmail($this->getHomeEmail()) == false) {
                $this->Validator->Warning('home_email', TTi18n::gettext('Home email address is assigned to another employee, continuing will disable password reset functionality and may result in account lock-out'));
            }
        }
        return true;
    }

    public function setProvince($province)
    {
        $province = trim($province);

        //Debug::Text('Country: '. $this->getCountry() .' Province: '. $province, __FILE__, __LINE__, __METHOD__, 10);

        $cf = TTnew('CompanyFactory');

        $options_arr = $cf->getOptions('province');
        if (isset($options_arr[$this->getCountry()])) {
            $options = $options_arr[$this->getCountry()];
        } else {
            $options = array();
        }

        //If country isn't set yet, accept the value and re-validate on save.
        if ($this->getCountry() == false
            or
            $this->Validator->inArrayKey('province',
                $province,
                TTi18n::gettext('Invalid Province/State'),
                $options)
        ) {
            $this->data['province'] = $province;

            return true;
        }

        return false;
    }

    public function getTerminationDate()
    {
        if (isset($this->data['termination_date'])) {
            return $this->data['termination_date'];
        }

        return false;
    }

    public function getHireDate()
    {
        if (isset($this->data['hire_date'])) {
            return $this->data['hire_date'];
        }

        return false;
    }

    public function setPassword($password, $password_confirm = null, $force = false)
    {
        $password = trim($password);
        $password_confirm = ($password_confirm !== null) ? trim($password_confirm) : $password_confirm;

        //Make sure we accept just $password being set otherwise setObjectFromArray() won't work correctly.
        if (($password != '' and $password_confirm != '' and $password === $password_confirm) or ($password != '' and $password_confirm === null)) {
            $passwords_match = true;
        } else {
            $passwords_match = false;
        }
        Debug::Text('Password: ' . $password . ' Confirm: ' . $password_confirm . ' Match: ' . (int)$passwords_match, __FILE__, __LINE__, __METHOD__, 10);

        $modify_password = false;
        if ($this->getCurrentUserPermissionLevel() >= $this->getPermissionLevel()) {
            $modify_password = true;
        }

        if ($password != ''
            and
            $this->Validator->isLength('password',
                $password,
                TTi18n::gettext('Password is too short or too long'),
                ($force == false) ? 6 : 4, //DemoData requires 4 chars for password: demo
                64)
            and
            $this->Validator->isTrue('password',
                $passwords_match,
                TTi18n::gettext('Passwords don\'t match'))
            and
            $this->Validator->isTrue('password',
                (($force == false and stripos($password, $this->getUserName()) !== false) ? false : true),
                TTi18n::gettext('User Name must not be a part of the password'))
            and
            $this->Validator->isTrue('password',
                (($force == false and stripos($this->getUserName(), $password) !== false) ? false : true),
                TTi18n::gettext('Password must not be a part of the User Name'))
            and
            $this->Validator->isTrue('password',
                (($force == false and in_array(strtolower($password), array(strtolower($this->getFirstName()), strtolower($this->getMiddleName()), strtolower($this->getLastName()), strtolower($this->getCity()), strtolower($this->getWorkEmail()), strtolower($this->getHomeEmail()), $this->getHomePhone(), $this->getWorkPhone(), $this->getSIN(), $this->getPhoneID())) == true) ? false : true),
                TTi18n::gettext('Password is too weak, it should not match any commonly known personal information'))
            and
            $this->Validator->isTrue('password',
                (($force == false and Misc::getPasswordStrength($password) <= 2) ? false : true),
                TTi18n::gettext('Password is too weak, add additional numbers or special/upper case characters'))
            and
            $this->Validator->isTrue('password',
                $modify_password,
                TTi18n::gettext('Insufficient access to modify passwords for this employee')
            )
        ) {
            $update_password = true;

            //When changing the password, we need to check if a Password Policy is defined.
            $c_obj = $this->getCompanyObject();
            if ($this->isPasswordPolicyEnabled() == true) {
                Debug::Text('Password Policy: Minimum Length: ' . $c_obj->getPasswordMinimumLength() . ' Min. Strength: ' . $c_obj->getPasswordMinimumStrength() . ' (' . Misc::getPasswordStrength($password) . ') Age: ' . $c_obj->getPasswordMinimumAge(), __FILE__, __LINE__, __METHOD__, 10);

                if (strlen($password) < $c_obj->getPasswordMinimumLength()) {
                    $update_password = false;
                    $this->Validator->isTrue('password',
                        false,
                        TTi18n::gettext('Password is too short'));
                }

                if (Misc::getPasswordStrength($password) <= $c_obj->getPasswordMinimumStrength()) {
                    $update_password = false;
                    $this->Validator->isTrue('password',
                        false,
                        TTi18n::gettext('Password is too weak, add additional numbers or special/upper case characters'));
                }

                if ($this->getPasswordUpdatedDate() != '' and $this->getPasswordUpdatedDate() >= (time() - ($c_obj->getPasswordMinimumAge() * 86400))) {
                    $update_password = false;
                    $this->Validator->isTrue('password',
                        false,
                        TTi18n::gettext('Password must reach its minimum age before it can be changed again'));
                }

                if ($this->getId() > 0) {
                    $uilf = TTnew('UserIdentificationListFactory');
                    $uilf->getByUserIdAndTypeIdAndValue($this->getId(), 5, $this->encryptPassword($password));
                    if ($uilf->getRecordCount() > 0) {
                        $update_password = false;
                        $this->Validator->isTrue('password',
                            false,
                            TTi18n::gettext('Password has already been used in the past, please choose a new one'));
                    }
                    unset($uilf);
                }
            } //else { //Debug::Text('Password Policy disabled or does not apply to this user.', __FILE__, __LINE__, __METHOD__, 10);

            if ($update_password === true) {
                Debug::Text('Setting new password...', __FILE__, __LINE__, __METHOD__, 10);
                $this->data['password'] = $this->encryptPassword($password); //Assumes latest password version is used.
                $this->setPasswordUpdatedDate(time());
                $this->setEnableClearPasswordResetData(true); //Clear any outstanding password reset key to prevent unexpected changes later on.
            }

            return true;
        }

        return false;
    }

    public function getFirstName()
    {
        if (isset($this->data['first_name'])) {
            return $this->data['first_name'];
        }

        return false;
    }

    public function getMiddleName()
    {
        if (isset($this->data['middle_name'])) {
            return $this->data['middle_name'];
        }

        return false;
    }

    public function getLastName()
    {
        if (isset($this->data['last_name'])) {
            return $this->data['last_name'];
        }

        return false;
    }

    public function getWorkPhone()
    {
        if (isset($this->data['work_phone'])) {
            return $this->data['work_phone'];
        }

        return false;
    }

    public function setPasswordUpdatedDate($epoch)
    {
        if (empty($epoch)) {
            $epoch = null;
        }

        if ($epoch == ''
            or
            $this->Validator->isDate('password_updated_date',
                $epoch,
                TTi18n::gettext('Password updated date is invalid'))
        ) {
            Debug::Text('Setting new password date: ' . TTDate::getDate('DATE+TIME', $epoch), __FILE__, __LINE__, __METHOD__, 10);
            $this->data['password_updated_date'] = $epoch;

            return true;
        }

        return false;
    }

    public function getStatus()
    {
        if (isset($this->data['status_id'])) {
            return (int)$this->data['status_id'];
        }

        return false;
    }

    public function isUniqueWorkEmail($email)
    {
        //Ignore blank emails.
        if ($email == '') {
            return true;
        }

        $ph = array(
            'email' => trim(strtolower($email)),
            'email2' => trim(strtolower($email)),
        );

        $query = 'select id from ' . $this->getTable() . ' where ( work_email = ? OR home_email = ? ) AND deleted=0';
        $user_email_id = $this->db->GetOne($query, $ph);
        Debug::Arr($user_email_id, 'Unique Email: ' . $email, __FILE__, __LINE__, __METHOD__, 10);

        if ($user_email_id === false) {
            return true;
        } else {
            if ($user_email_id == $this->getId()) {
                return true;
            }
        }

        return false;
    }

    public function isUniqueHomeEmail($email)
    {
        return $this->isUniqueWorkEmail($email);
    }

    public function preSave()
    {
        if ($this->getDefaultBranch() == false) {
            $this->setDefaultBranch(0);
        }
        if ($this->getDefaultDepartment() == false) {
            $this->setDefaultDepartment(0);
        }

        if ($this->getStatus() == false) {
            $this->setStatus(10); //Active
        }

        if ($this->getSex() == false) {
            $this->setSex(5); //UnSpecified
        }

        if ($this->getEthnicGroup() == false) {
            $this->setEthnicGroup(0);
        }

        if ($this->getEnableClearPasswordResetData() == true) {
            Debug::text('Clearing password reset data...', __FILE__, __LINE__, __METHOD__, 10);
            $this->setPasswordResetKey('');
            $this->setPasswordResetDate('');
        }

        //Remember if this is a new user for postSave()
        if ($this->isNew(true)) {
            $this->is_new = true;
        }

        return true;
    }

    public function setDefaultBranch($id)
    {
        $id = (int)trim($id);

        $blf = TTnew('BranchListFactory');
        if (
            $id == 0
            or
            $this->Validator->isResultSetWithRows('default_branch',
                $blf->getByID($id),
                TTi18n::gettext('Invalid Default Branch')
            )
        ) {
            $this->data['default_branch_id'] = $id;

            return true;
        }

        return false;
    }

    public function setDefaultDepartment($id)
    {
        $id = (int)trim($id);

        $dlf = TTnew('DepartmentListFactory');
        if (
            $id == 0
            or
            $this->Validator->isResultSetWithRows('default_department',
                $dlf->getByID($id),
                TTi18n::gettext('Invalid Default Department')
            )
        ) {
            $this->data['default_department_id'] = $id;

            return true;
        }

        return false;
    }

    public function setStatus($status)
    {
        $status = trim($status);

        $modify_status = false;
        if ($this->getCurrentUserPermissionLevel() >= $this->getPermissionLevel()) {
            $modify_status = true;
        } elseif ($this->getStatus() == $status) { //No modification made.
            $modify_status = true;
        }

        if ($this->Validator->inArrayKey('status_id',
                $status,
                TTi18n::gettext('Incorrect Status'),
                $this->getOptions('status'))
            and
            $this->Validator->isTrue('status_id',
                $modify_status,
                TTi18n::gettext('Insufficient access to modify status for this employee')
            )
        ) {
            $this->data['status_id'] = $status;

            return true;
        }

        return false;
    }

    public function getSex()
    {
        if (isset($this->data['sex_id'])) {
            return (int)$this->data['sex_id'];
        }

        return false;
    }

    public function setSex($sex)
    {
        $sex = trim($sex);

        if ($this->Validator->inArrayKey('sex',
            $sex,
            TTi18n::gettext('Invalid gender'),
            $this->getOptions('sex'))
        ) {
            $this->data['sex_id'] = $sex;

            return true;
        }

        return false;
    }

    public function getEthnicGroup()
    {
        if (isset($this->data['ethnic_group_id'])) {
            return (int)$this->data['ethnic_group_id'];
        }
        return false;
    }

    public function setEthnicGroup($id)
    {
        $id = (int)trim($id);
        $eglf = TTnew('EthnicGroupListFactory');

        if ($id == 0
            or
            $this->Validator->isResultSetWithRows('ethnic_group',
                $eglf->getById($id),
                TTi18n::gettext('Ethnic Group is invalid')
            )
        ) {
            $this->data['ethnic_group_id'] = $id;

            return true;
        }

        return false;
    }

    public function getEnableClearPasswordResetData()
    {
        if (isset($this->tmp_data['enable_clear_password_reset_data'])) {
            return $this->tmp_data['enable_clear_password_reset_data'];
        }
        return false;
    }

    public function postSave($data_diff = null)
    {
        $this->removeCache($this->getId());

        if ($this->getDeleted() == false and $this->getPermissionControl() !== false) {
            Debug::text('Permission Group is set...', __FILE__, __LINE__, __METHOD__, 10);

            $pclf = TTnew('PermissionControlListFactory');
            $pclf->getByCompanyIdAndUserID($this->getCompany(), $this->getId());
            if ($pclf->getRecordCount() > 0) {
                Debug::text('Already assigned to a Permission Group...', __FILE__, __LINE__, __METHOD__, 10);

                $pc_obj = $pclf->getCurrent();

                if ($pc_obj->getId() == $this->getPermissionControl()) {
                    $add_permission_control = false;
                } else {
                    Debug::text('Permission Group has changed...', __FILE__, __LINE__, __METHOD__, 10);

                    $pulf = TTnew('PermissionUserListFactory');
                    $pulf->getByPermissionControlIdAndUserID($pc_obj->getId(), $this->getId());
                    Debug::text('Record Count: ' . $pulf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
                    if ($pulf->getRecordCount() > 0) {
                        foreach ($pulf as $pu_obj) {
                            Debug::text('Deleteing from Permission Group: ' . $pu_obj->getPermissionControl(), __FILE__, __LINE__, __METHOD__, 10);
                            $pu_obj->Delete();
                        }

                        $pc_obj->touchUpdatedByAndDate();
                    }

                    $add_permission_control = true;
                }
            } else {
                Debug::text('NOT Already assigned to a Permission Group...', __FILE__, __LINE__, __METHOD__, 10);
                $add_permission_control = true;
            }

            if ($this->getPermissionControl() !== false and $add_permission_control == true) {
                Debug::text('Adding user to Permission Group...', __FILE__, __LINE__, __METHOD__, 10);

                //Add to new permission group
                $puf = TTnew('PermissionUserFactory');
                $puf->setPermissionControl($this->getPermissionControl());
                $puf->setUser($this->getID());

                if ($puf->isValid()) {
                    if (is_object($puf->getPermissionControlObject())) {
                        $puf->getPermissionControlObject()->touchUpdatedByAndDate();
                    }
                    $puf->Save();

                    //Clear permission class for this employee.
                    $pf = TTnew('PermissionFactory');
                    $pf->clearCache($this->getID(), $this->getCompany());
                }
            }
            unset($add_permission_control);
        }

        if ($this->getDeleted() == false and $this->getPayPeriodSchedule() !== false) {
            Debug::text('Pay Period Schedule is set: ' . $this->getPayPeriodSchedule(), __FILE__, __LINE__, __METHOD__, 10);

            $add_pay_period_schedule = false;

            $ppslf = TTnew('PayPeriodScheduleListFactory');
            $ppslf->getByUserId($this->getId());
            if ($ppslf->getRecordCount() > 0) {
                $pps_obj = $ppslf->getCurrent();

                if ($this->getPayPeriodSchedule() == $pps_obj->getId()) {
                    Debug::text('Already assigned to this Pay Period Schedule...', __FILE__, __LINE__, __METHOD__, 10);
                    $add_pay_period_schedule = false;
                } else {
                    Debug::text('Changing Pay Period Schedule...', __FILE__, __LINE__, __METHOD__, 10);

                    //Remove user from current schedule.
                    $ppsulf = TTnew('PayPeriodScheduleUserListFactory');
                    $ppsulf->getByPayPeriodScheduleIdAndUserID($pps_obj->getId(), $this->getId());
                    Debug::text('Record Count: ' . $ppsulf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
                    if ($ppsulf->getRecordCount() > 0) {
                        foreach ($ppsulf as $ppsu_obj) {
                            Debug::text('Deleteing from Pay Period Schedule: ' . $ppsu_obj->getPayPeriodSchedule(), __FILE__, __LINE__, __METHOD__, 10);
                            $ppsu_obj->Delete();
                        }
                    }
                    $add_pay_period_schedule = true;
                }
            } elseif ($this->getPayPeriodSchedule() > 0) {
                Debug::text('Not assigned to ANY Pay Period Schedule...', __FILE__, __LINE__, __METHOD__, 10);
                $add_pay_period_schedule = true;
            }

            if ($this->getPayPeriodSchedule() !== false and $add_pay_period_schedule == true) {
                //Add to new pay period schedule
                $ppsuf = TTnew('PayPeriodScheduleUserFactory');
                $ppsuf->setPayPeriodSchedule($this->getPayPeriodSchedule());
                $ppsuf->setUser($this->getID());
                if ($ppsuf->isValid()) {
                    $ppsuf->Save(false);

                    //Attempt to import data into currently open pay periods if its not a new user.
                    if (!isset($this->is_new) or (isset($this->is_new) and $this->is_new == false) and is_object($ppsuf->getPayPeriodScheduleObject())) {
                        $ppsuf->getPayPeriodScheduleObject()->importData($this->getID());
                    }
                }
                unset($ppsuf);
            }
            unset($add_pay_period_schedule);
        }

        if ($this->getDeleted() == false and $this->getPolicyGroup() !== false) {
            Debug::text('Policy Group is set...', __FILE__, __LINE__, __METHOD__, 10);

            $pglf = TTnew('PolicyGroupListFactory');
            $pglf->getByUserIds($this->getId());
            if ($pglf->getRecordCount() > 0) {
                $pg_obj = $pglf->getCurrent();

                if ($this->getPolicyGroup() == $pg_obj->getId()) {
                    Debug::text('Already assigned to this Policy Group...', __FILE__, __LINE__, __METHOD__, 10);
                    $add_policy_group = false;
                } else {
                    Debug::text('Changing Policy Group...', __FILE__, __LINE__, __METHOD__, 10);

                    //Remove user from current schedule.
                    $pgulf = TTnew('PolicyGroupUserListFactory');
                    $pgulf->getByPolicyGroupIdAndUserId($pg_obj->getId(), $this->getId());
                    Debug::text('Record Count: ' . $pgulf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
                    if ($pgulf->getRecordCount() > 0) {
                        foreach ($pgulf as $pgu_obj) {
                            Debug::text('Deleting from Policy Group: ' . $pgu_obj->getPolicyGroup(), __FILE__, __LINE__, __METHOD__, 10);
                            $pgu_obj->Delete();
                        }
                    }
                    $add_policy_group = true;
                }
            } else {
                Debug::text('Not assigned to ANY Policy Group...', __FILE__, __LINE__, __METHOD__, 10);
                $add_policy_group = true;
            }

            if ($this->getPolicyGroup() !== false and $add_policy_group == true) {
                //Add to new policy group
                $pguf = TTnew('PolicyGroupUserFactory');
                $pguf->setPolicyGroup($this->getPolicyGroup());
                $pguf->setUser($this->getID());

                if ($pguf->isValid()) {
                    $pguf->Save();
                }
            }
            unset($add_policy_group);
        }

        if ($this->getDeleted() == false and $this->getHierarchyControl() !== false) {
            Debug::text('Hierarchies are set...', __FILE__, __LINE__, __METHOD__, 10);

            $hierarchy_control_data = array_unique(array_values((array)$this->getHierarchyControl()));
            //Debug::Arr($hierarchy_control_data, 'Setting hierarchy control data...', __FILE__, __LINE__, __METHOD__, 10);

            if (is_array($hierarchy_control_data)) {
                $hclf = TTnew('HierarchyControlListFactory');
                $hclf->getObjectTypeAppendedListByCompanyIDAndUserID($this->getCompany(), $this->getID());
                $existing_hierarchy_control_data = array_unique(array_values((array)$hclf->getArrayByListFactory($hclf, false, true, false)));
                //Debug::Arr($existing_hierarchy_control_data, 'Existing hierarchy control data...', __FILE__, __LINE__, __METHOD__, 10);

                $hierarchy_control_delete_diff = array_diff($existing_hierarchy_control_data, $hierarchy_control_data);
                //Debug::Arr($hierarchy_control_delete_diff, 'Hierarchy control delete diff: ', __FILE__, __LINE__, __METHOD__, 10);

                //Remove user from existing hierarchy control
                if (is_array($hierarchy_control_delete_diff)) {
                    foreach ($hierarchy_control_delete_diff as $hierarchy_control_id) {
                        if ($hierarchy_control_id != 0) {
                            $hulf = TTnew('HierarchyUserListFactory');
                            $hulf->getByHierarchyControlAndUserID($hierarchy_control_id, $this->getID());
                            if ($hulf->getRecordCount() > 0) {
                                Debug::text('Deleting user from hierarchy control ID: ' . $hierarchy_control_id, __FILE__, __LINE__, __METHOD__, 10);
                                $hulf->getCurrent()->Delete();
                            }
                        }
                    }
                }
                unset($hierarchy_control_delete_diff, $hulf, $hclf, $hierarchy_control_id);

                $hierarchy_control_add_diff = array_diff($hierarchy_control_data, $existing_hierarchy_control_data);
                //Debug::Arr($hierarchy_control_add_diff, 'Hierarchy control add diff: ', __FILE__, __LINE__, __METHOD__, 10);

                if (is_array($hierarchy_control_add_diff)) {
                    foreach ($hierarchy_control_add_diff as $hierarchy_control_id) {
                        Debug::text('Hierarchy data changed...', __FILE__, __LINE__, __METHOD__, 10);
                        if ($hierarchy_control_id != 0) {
                            $huf = TTnew('HierarchyUserFactory');
                            $huf->setHierarchyControl($hierarchy_control_id);
                            $huf->setUser($this->getId());
                            if ($huf->isValid()) {
                                Debug::text('Adding user to hierarchy control ID: ' . $hierarchy_control_id, __FILE__, __LINE__, __METHOD__, 10);
                                $huf->Save();
                            }
                        }
                    }
                }
                unset($huf, $hierarchy_control_id);
            }
        }

        if ($this->getDeleted() == false and $this->getPasswordUpdatedDate() >= (time() - 10)) { //If the password was updated in the last 10 seconds.
            Debug::text('Password changed, saving it for historical purposes... Password: ' . $this->getPassword(), __FILE__, __LINE__, __METHOD__, 10);

            $uif = TTnew('UserIdentificationFactory');
            $uif->setUser($this->getID());
            $uif->setType(5); //Password History
            $uif->setNumber(0);
            $uif->setValue($this->getPassword());
            if ($uif->isValid()) {
                $uif->Save();
            }
            unset($uif);
        }

        if ($this->getDeleted() == false) {
            Debug::text('Setting Tags...', __FILE__, __LINE__, __METHOD__, 10);
            CompanyGenericTagMapFactory::setTags($this->getCompany(), 200, $this->getID(), $this->getTag());

            $this->clearGeoCode($data_diff); //Clear Lon/Lat coordinates when address has changed.

            if (is_array($data_diff) and (isset($data_diff['hire_date']) or isset($data_diff['termination_date']))) {
                Debug::text('Hire Date or Termination date have changed!', __FILE__, __LINE__, __METHOD__, 10);
                $rsf = TTnew('RecurringScheduleFactory');
                $rsf->recalculateRecurringSchedules($this->getID(), (time() - (86400 * 28)), (time() + (86400 * 28)));
            }
        }

        if (isset($this->is_new) and $this->is_new == true) {
            $udlf = TTnew('UserDefaultListFactory');
            $udlf->getByCompanyId($this->getCompany());
            if ($udlf->getRecordCount() > 0) {
                Debug::Text('Using User Defaults', __FILE__, __LINE__, __METHOD__, 10);
                $udf_obj = $udlf->getCurrent();

                Debug::text('Inserting Default Deductions...', __FILE__, __LINE__, __METHOD__, 10);

                $company_deduction_ids = $udf_obj->getCompanyDeduction();
                if (is_array($company_deduction_ids) and count($company_deduction_ids) > 0) {
                    foreach ($company_deduction_ids as $company_deduction_id) {
                        $udf = TTnew('UserDeductionFactory');
                        $udf->setUser($this->getId());
                        $udf->setCompanyDeduction($company_deduction_id);
                        if ($udf->isValid()) {
                            $udf->Save();
                        }
                    }
                }
                unset($company_deduction_ids, $company_deduction_id, $udf);

                Debug::text('Inserting Default Prefs (a)...', __FILE__, __LINE__, __METHOD__, 10);
                $upf = TTnew('UserPreferenceFactory');
                $upf->setUser($this->getId());
                $upf->setLanguage($udf_obj->getLanguage());
                $upf->setDateFormat($udf_obj->getDateFormat());
                $upf->setTimeFormat($udf_obj->getTimeFormat());
                $upf->setTimeUnitFormat($udf_obj->getTimeUnitFormat());
                $upf->setDistanceFormat($udf_obj->getDistanceFormat());

                $upf->setTimeZone($upf->getLocationTimeZone($this->getCountry(), $this->getProvince(), $this->getWorkPhone(), $this->getHomePhone(), $udf_obj->getTimeZone()));
                Debug::text('Time Zone: ' . $upf->getTimeZone(), __FILE__, __LINE__, __METHOD__, 9);

                $upf->setItemsPerPage($udf_obj->getItemsPerPage());
                $upf->setStartWeekDay($udf_obj->getStartWeekDay());
                $upf->setEnableEmailNotificationException($udf_obj->getEnableEmailNotificationException());
                $upf->setEnableEmailNotificationMessage($udf_obj->getEnableEmailNotificationMessage());
                $upf->setEnableEmailNotificationPayStub($udf_obj->getEnableEmailNotificationPayStub());
                $upf->setEnableEmailNotificationHome($udf_obj->getEnableEmailNotificationHome());

                if ($upf->isValid()) {
                    $upf->Save();
                }
            } else {
                //No New Hire defaults, use global defaults.
                Debug::text('Inserting Default Prefs (b)...', __FILE__, __LINE__, __METHOD__, 10);
                $upf = TTnew('UserPreferenceFactory');
                $upf->setUser($this->getId());
                $upf->setLanguage('en');
                $upf->setDateFormat('d-M-y');
                $upf->setTimeFormat('g:i A');
                $upf->setTimeUnitFormat(10);
                $upf->setDistanceFormat(10);

                $upf->setTimeZone($upf->getLocationTimeZone($this->getCountry(), $this->getProvince(), $this->getWorkPhone(), $this->getHomePhone()));
                Debug::text('Time Zone: ' . $upf->getTimeZone(), __FILE__, __LINE__, __METHOD__, 9);

                $upf->setItemsPerPage(25);
                $upf->setStartWeekDay(0);
                $upf->setEnableEmailNotificationException(true);
                $upf->setEnableEmailNotificationMessage(true);
                $upf->setEnableEmailNotificationPayStub(true);
                $upf->setEnableEmailNotificationHome(true);
                if ($upf->isValid()) {
                    $upf->Save();
                }
            }
        }

        if ($this->getDeleted() == true) {
            //Remove them from the authorization hierarchy, policy group, pay period schedule, stations, jobs, etc...
            //Delete any accruals for them as well.

            //Pay Period Schedule
            $ppslf = TTnew('PayPeriodScheduleListFactory');
            $ppslf->getByUserId($this->getId());
            if ($ppslf->getRecordCount() > 0) {
                $pps_obj = $ppslf->getCurrent();

                //Remove user from current schedule.
                $ppsulf = TTnew('PayPeriodScheduleUserListFactory');
                $ppsulf->getByPayPeriodScheduleIdAndUserID($pps_obj->getId(), $this->getId());
                Debug::text('Record Count: ' . $ppsulf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
                if ($ppsulf->getRecordCount() > 0) {
                    foreach ($ppsulf as $ppsu_obj) {
                        Debug::text('Deleting from Pay Period Schedule: ' . $ppsu_obj->getPayPeriodSchedule(), __FILE__, __LINE__, __METHOD__, 10);
                        $ppsu_obj->Delete();
                    }
                }
            }

            //Policy Group
            $pglf = TTnew('PolicyGroupListFactory');
            $pglf->getByUserIds($this->getId());
            if ($pglf->getRecordCount() > 0) {
                $pg_obj = $pglf->getCurrent();

                $pgulf = TTnew('PolicyGroupUserListFactory');
                $pgulf->getByPolicyGroupIdAndUserId($pg_obj->getId(), $this->getId());
                Debug::text('Record Count: ' . $pgulf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
                if ($pgulf->getRecordCount() > 0) {
                    foreach ($pgulf as $pgu_obj) {
                        Debug::text('Deleteing from Policy Group: ' . $pgu_obj->getPolicyGroup(), __FILE__, __LINE__, __METHOD__, 10);
                        $pgu_obj->Delete();
                    }
                }
            }

            //Hierarchy
            $hclf = TTnew('HierarchyControlListFactory');
            $hclf->getByCompanyId($this->getCompany());
            if ($hclf->getRecordCount() > 0) {
                foreach ($hclf as $hc_obj) {
                    $hf = TTnew('HierarchyListFactory');
                    $hf->setUser($this->getID());
                    $hf->setHierarchyControl($hc_obj->getId());
                    $hf->Delete();
                }
                $hf->removeCache(null, $hf->getTable(true)); //On delete we have to delete the entire group.
                unset($hf);
            }

            /*
            //Accrual balances - DON'T DO THIS ANYMORE, AS IT CAUSES PROBLEMS WITH RESTORING DELETED USERS. I THINK IT WAS JUST AN OPTIMIZATION ANYWAYS.
            $alf = TTnew( 'AccrualListFactory' );
            $alf->getByUserIdAndCompanyId( $this->getId(), $this->getCompany() );
            if ( $alf->getRecordCount() > 0 ) {
                foreach( $alf as $a_obj ) {
                    $a_obj->setDeleted(TRUE);
                    if ( $a_obj->isValid() ) {
                        $a_obj->Save();
                    }
                }
            }
            */

            //Station employee critiera
            $siuf = TTnew('StationIncludeUserFactory');
            $seuf = TTnew('StationExcludeUserFactory');

            $query = 'delete from ' . $siuf->getTable() . ' where user_id = ' . (int)$this->getId();
            $this->db->Execute($query);

            $query = 'delete from ' . $seuf->getTable() . ' where user_id = ' . (int)$this->getId();
            $this->db->Execute($query);

            //Job employee criteria
            $cgmlf = TTnew('CompanyGenericMapListFactory');
            $cgmlf->getByCompanyIDAndObjectTypeAndMapID($this->getCompany(), array(1040, 1050), $this->getID());
            if ($cgmlf->getRecordCount() > 0) {
                foreach ($cgmlf as $cgm_obj) {
                    Debug::text('Deleteing from Company Generic Map: ' . $cgm_obj->getID(), __FILE__, __LINE__, __METHOD__, 10);
                    $cgm_obj->Delete();
                }
            }
        }

        if ($this->getDeleted() == true or $this->getStatus() != 10) {
            //Employee is being deleted or inactivated, make sure they are not a company contact, and if so replace them with a new contact.
            $default_company_contact_user_id = false;
            if (in_array($this->getId(), array($this->getCompanyObject()->getAdminContact(), $this->getCompanyObject()->getBillingContact(), $this->getCompanyObject()->getSupportContact()))) {
                $default_company_contact_user_id = $this->getCompanyObject()->getDefaultContact();
                Debug::text('User is primary company contact, remove and replace them with: ' . $default_company_contact_user_id, __FILE__, __LINE__, __METHOD__, 10);

                if ($default_company_contact_user_id != false and $this->getId() == $this->getCompanyObject()->getAdminContact()) {
                    $this->getCompanyObject()->setAdminContact($default_company_contact_user_id);
                    Debug::text('Replacing Admin Contact with: ' . $default_company_contact_user_id, __FILE__, __LINE__, __METHOD__, 10);
                }
                if ($default_company_contact_user_id != false and $this->getId() == $this->getCompanyObject()->getBillingContact()) {
                    $this->getCompanyObject()->setBillingContact($default_company_contact_user_id);
                    Debug::text('Replacing Billing Contact with: ' . $default_company_contact_user_id, __FILE__, __LINE__, __METHOD__, 10);
                }
                if ($default_company_contact_user_id != false and $this->getId() == $this->getCompanyObject()->getSupportContact()) {
                    $this->getCompanyObject()->setSupportContact($default_company_contact_user_id);
                    Debug::text('Replacing Support Contact with: ' . $default_company_contact_user_id, __FILE__, __LINE__, __METHOD__, 10);
                }
                if ($default_company_contact_user_id != false and $this->getCompanyObject()->isValid()) {
                    $this->getCompanyObject()->Save();
                }
            }
            unset($default_company_contact_user_id);
        }

        //If status is set to anything other than ACTIVE, logout user.
        if ($this->getStatus() != 10) {
            $authentication = TTNew('Authentication');
            $authentication->logoutUser($this->getID());
        }

        return true;
    }

    public function getPayPeriodSchedule()
    {
        //Check to see if any temporary data is set for the hierarchy, if not, make a call to the database instead.
        //postSave() needs to get the tmp_data.
        if (isset($this->tmp_data['pay_period_schedule_id'])) {
            return $this->tmp_data['pay_period_schedule_id'];
        } elseif ($this->getCompany() > 0 and $this->getID() > 0) {
            $ppslfb = TTnew('PayPeriodScheduleListFactory');
            $ppslfb->getByUserId($this->getID());
            if ($ppslfb->getRecordCount() > 0) {
                return $ppslfb->getCurrent()->getId();
            }
        }

        return false;
    }

    public function getPolicyGroup()
    {
        //Check to see if any temporary data is set for the hierarchy, if not, make a call to the database instead.
        //postSave() needs to get the tmp_data.
        if (isset($this->tmp_data['policy_group_id'])) {
            return $this->tmp_data['policy_group_id'];
        } elseif ($this->getCompany() > 0 and $this->getID() > 0) {
            $pglf = TTnew('PolicyGroupListFactory');
            $pglf->getByUserIds($this->getID());
            if ($pglf->getRecordCount() > 0) {
                return $pglf->getCurrent()->getId();
            }
        }

        return false;
    }

    public function getHierarchyControl()
    {
        //Check to see if any temporary data is set for the hierarchy, if not, make a call to the database instead.
        //postSave() needs to get the tmp_data.
        if (isset($this->tmp_data['hierarchy_control'])) {
            return $this->tmp_data['hierarchy_control'];
        } elseif ($this->getCompany() > 0 and $this->getID() > 0) {
            $hclf = TTnew('HierarchyControlListFactory');
            $hclf->getObjectTypeAppendedListByCompanyIDAndUserID($this->getCompany(), $this->getID());
            return $hclf->getArrayByListFactory($hclf, false, true, false);
        }

        return false;
    }

    public function getTag()
    {
        //Check to see if any temporary data is set for the tags, if not, make a call to the database instead.
        //postSave() needs to get the tmp_data.
        if (isset($this->tmp_data['tags'])) {
            return $this->tmp_data['tags'];
        } elseif ($this->getCompany() > 0 and $this->getID() > 0) {
            return CompanyGenericTagMapListFactory::getStringByCompanyIDAndObjectTypeIDAndObjectID($this->getCompany(), 200, $this->getID());
        }

        return false;
    }

    public function getMapURL()
    {
        return Misc::getMapURL($this->getAddress1(), $this->getAddress2(), $this->getCity(), $this->getProvince(), $this->getPostalCode(), $this->getCountry());
    }

    public function getAddress2()
    {
        if (isset($this->data['address2'])) {
            return $this->data['address2'];
        }

        return false;
    }

    public function getPostalCode()
    {
        if (isset($this->data['postal_code'])) {
            return $this->data['postal_code'];
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
                        case 'hire_date':
                        case 'birth_date':
                        case 'termination_date':
                            if (method_exists($this, $function)) {
                                $this->$function(TTDate::parseDateTime($data[$key]));
                            }
                            break;
                        case 'password':
                            $password_confirm = null;
                            if (isset($data['password_confirm'])) {
                                $password_confirm = $data['password_confirm'];
                            }
                            $this->setPassword($data[$key], $password_confirm);
                            break;
                        case 'last_login_date': //SKip this as its set by the system.
                        case 'first_name_metaphone':
                        case 'last_name_metaphone':
                        case 'password_reset_date': //Password columns must not be changed from the API.
                        case 'password_reset_key':
                        case 'password_updated_date':
                        case 'work_email_is_valid':
                        case 'work_email_is_valid': //EMail validation fields must not be changed from API.
                        case 'work_email_is_valid_key':
                        case 'work_email_is_valid_date':
                        case 'home_email_is_valid':
                        case 'home_email_is_valid_key':
                        case 'home_email_is_valid_date':
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
        /*
        $include_columns = array(
                                'id' => TRUE,
                                'company_id' => TRUE,
                                ...
                                )

        */

        $variable_function_map = $this->getVariableToFunctionMap();
        $data = array();
        if (is_array($variable_function_map)) {
            foreach ($variable_function_map as $variable => $function_stub) {
                if ($include_columns == null or (isset($include_columns[$variable]) and $include_columns[$variable] == true)) {
                    $function = 'get' . $function_stub;
                    switch ($variable) {
                        case 'full_name':
                            $data[$variable] = $this->getFullName(true);
                        case 'status':
                        case 'sex':
                            $function = 'get' . $variable;
                            if (method_exists($this, $function)) {
                                $data[$variable] = Option::getByKey($this->$function(), $this->getOptions($variable));
                            }
                            break;
                        case 'company':
                        case 'title':
                        case 'user_group':
                        case 'ethnic_group':
                        case 'currency':
                        case 'currency_rate':
                        case 'default_branch':
                        case 'default_branch_manual_id':
                        case 'default_department':
                        case 'default_department_manual_id':
                        case 'default_job':
                        case 'default_job_manual_id':
                        case 'default_job_item':
                        case 'default_job_item_manual_id':
                        case 'permission_control':
                        case 'pay_period_schedule':
                        case 'policy_group':
                        case 'password_updated_date':
                            $data[$variable] = $this->getColumn($variable);
                            break;
                        //The below fields may be set if APISearch ListFactory is used to obtain the data originally,
                        //but if it isn't, use the explicit function to get the data instead.
                        case 'permission_control_id':
                            //These functions are slow to obtain (especially in a large loop), so make sure the column is requested explicitly before we include it.
                            //Flex currently doesn't specify these fields in the Edit view though, so this breaks Flex.
                            //if ( isset($include_columns[$variable]) AND $include_columns[$variable] == TRUE ) {
                            $data[$variable] = $this->getColumn($variable);
                            if ($data[$variable] == false) {
                                $data[$variable] = $this->getPermissionControl();
                            }
                            //}
                            break;
                        case 'pay_period_schedule_id':
                            //These functions are slow to obtain (especially in a large loop), so make sure the column is requested explicitly before we include it.
                            //Flex currently doesn't specify these fields in the Edit view though, so this breaks Flex.
                            //if ( isset($include_columns[$variable]) AND $include_columns[$variable] == TRUE ) {
                            $data[$variable] = $this->getColumn($variable);
                            if ($data[$variable] == false) {
                                $data[$variable] = $this->getPayPeriodSchedule();
                            }
                            //}
                            break;
                        case 'policy_group_id':
                            //These functions are slow to obtain (especially in a large loop), so make sure the column is requested explicitly before we include it.
                            //Flex currently doesn't specify these fields in the Edit view though, so this breaks Flex.
                            //if ( isset($include_columns[$variable]) AND $include_columns[$variable] == TRUE ) {
                            $data[$variable] = $this->getColumn($variable);
                            if ($data[$variable] == false) {
                                $data[$variable] = $this->getPolicyGroup();
                            }
                            //}
                            break;
                        case 'hierarchy_control':
                            //These functions are slow to obtain (especially in a large loop), so make sure the column is requested explicitly before we include it.
                            //Flex currently doesn't specify these fields in the Edit view though, so this breaks Flex.
                            //if ( isset($include_columns[$variable]) AND $include_columns[$variable] == TRUE ) {
                            $data[$variable] = $this->getHierarchyControl();
                            //}
                            break;
                        case 'hierarchy_control_display':
                            //These functions are slow to obtain (especially in a large loop), so make sure the column is requested explicitly before we include it.
                            //if ( isset($include_columns[$variable]) AND $include_columns[$variable] == TRUE ) {
                            $data[$variable] = $this->getHierarchyControlDisplay();
                            //}
                            break;
                        case 'hierarchy_level_display':
                            $data[$variable] = $this->getHierarchyLevelDisplay();
                            break;
                        case 'password': //Don't return password
                            break;
                        //case 'sin': //This is handled in the API class instead.
                        //	$data[$variable] = $this->getSecureSIN();
                        //	break;
                        case 'last_login_date':
                        case 'hire_date':
                        case 'birth_date':
                        case 'termination_date':
                            if (method_exists($this, $function)) {
                                $data[$variable] = TTDate::getAPIDate('DATE', $this->$function());
                            }
                            break;
                        case 'max_punch_time_stamp':
                            $data[$variable] = TTDate::getAPIDate('DATE+TIME', TTDate::strtotime($this->getColumn($variable)));
                            break;
                        case 'birth_date_age':
                            $data[$variable] = (int)floor(TTDate::getYearDifference(TTDate::getBeginDayEpoch($this->getBirthDate()), TTDate::getEndDayEpoch(time())));
                            break;
                        case 'hire_date_age':
                            if ($this->getTerminationDate() != '') {
                                $end_epoch = $this->getTerminationDate();
                            } else {
                                $end_epoch = time();
                            }
                            //Staffing agencies may have employees for only a few days, so need to show partial years.
                            $data[$variable] = number_format(TTDate::getYearDifference(TTDate::getBeginDayEpoch($this->getHireDate()), TTDate::getEndDayEpoch($end_epoch)), 2); //Years (two decimals)
                            unset($end_epoch);
                            break;
                        default:
                            if (method_exists($this, $function)) {
                                $data[$variable] = $this->$function();
                            }
                            break;
                    }
                }
                unset($function);
            }
            $this->getPermissionColumns($data, $this->getID(), $this->getCreatedBy(), $permission_children_ids, $include_columns);
            $this->getCreatedAndUpdatedColumns($data, $include_columns);
        }

        return $data;
    }

    public function getFullName($reverse = false, $include_middle = true)
    {
        return Misc::getFullName($this->getFirstName(), $this->getMiddleInitial(), $this->getLastName(), $reverse, $include_middle);
    }

    public function getMiddleInitial()
    {
        if ($this->getMiddleName() != '') {
            $middle_name = $this->getMiddleName();
            return $middle_name[0];
        }

        return false;
    }

    //Support setting created_by, updated_by especially for importing data.
    //Make sure data is set based on the getVariableToFunctionMap order.

    public function getHierarchyControlDisplay()
    {
        $hclf = TTnew('HierarchyControlListFactory');
        $hclf->getObjectTypeAppendedListByCompanyIDAndUserID($this->getCompany(), $this->getID());
        $data = $hclf->getArrayByListFactory($hclf, false, false, true);

        if (is_array($data)) {
            $retval = array();
            foreach ($data as $name) {
                $retval[] = $name;
            }

            sort($retval); //Maintain consistent order.

            return implode(', ', $retval); //Add space so wordwrap has a chance.
        }

        return false;
    }

    public function getHierarchyLevelDisplay()
    {
        $hllf = new HierarchyLevelListFactory();
        $hllf->getObjectTypeAndHierarchyAppendedListByCompanyIDAndUserID($this->getCompany(), $this->getID());
        if ($hllf->getRecordCount() > 0) {
            $hierarchy_control_retval = array();
            foreach ($hllf as $hl_obj) {
                if (is_object($hl_obj->getUserObject())) {
                    $hierarchy_control_retval[$hl_obj->getColumn('hierarchy_control_name')][] = $hl_obj->getLevel() . '.' . $hl_obj->getUserObject()->getFullName(); //Don't add space after "." to prevent word wrap after the level.
                }
            }

            if (empty($hierarchy_control_retval) == false) {
                $enable_display_hierarchy_control_name = false;
                if (count($hierarchy_control_retval) > 1) {
                    $enable_display_hierarchy_control_name = true;
                }
                $retval = '';
                foreach ($hierarchy_control_retval as $hierarchy_control_name => $levels) {
                    if ($enable_display_hierarchy_control_name == true) {
                        $retval .= $hierarchy_control_name . ': [' . implode(', ', $levels) . '] '; //Include space after, so wordwrap can function better.
                    } else {
                        $retval .= implode(', ', $levels); //Include space after, so wordwrap can function better.
                    }
                }

                return trim($retval);
            }
        }

        return false;
    }

    public function addLog($log_action)
    {
        return TTLog::addEntry($this->getId(), $log_action, TTi18n::getText('Employee') . ': ' . $this->getFullName(false, true), null, $this->getTable(), $this);
    }
}
