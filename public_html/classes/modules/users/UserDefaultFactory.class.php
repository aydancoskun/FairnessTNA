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
 * @package Modules\Users
 */
class UserDefaultFactory extends Factory
{
    protected $table = 'user_default';
    protected $pk_sequence_name = 'user_default_id_seq'; //PK Sequence name

    protected $company_obj = null;
    protected $title_obj = null;

    protected $city_validator_regex = '/^[a-zA-Z0-9-,_\.\'#\ |\x{0080}-\x{FFFF}]{1,250}$/iu';

    public function _getVariableToFunctionMap($data)
    {
        $variable_function_map = array(
            'id' => 'ID',
            'company_id' => 'Company',
            'permission_control_id' => 'PermissionControl',
            'pay_period_schedule_id' => 'PayPeriodSchedule',
            'policy_group_id' => 'PolicyGroup',
            'employee_number' => 'EmployeeNumber',
            'title_id' => 'Title',
            'default_branch_id' => 'DefaultBranch',
            'default_department_id' => 'DefaultDepartment',
            'currency_id' => 'Currency',
            'city' => 'City',
            'country' => 'Country',
            'province' => 'Province',
            'work_phone' => 'WorkPhone',
            'work_phone_ext' => 'WorkPhoneExt',
            'work_email' => 'WorkEmail',
            'hire_date' => 'HireDate',
            'language' => 'Language',
            'date_format' => 'DateFormat',
            'time_format' => 'TimeFormat',
            'time_zone' => 'TimeZone',
            'time_unit_format' => 'TimeUnitFormat',
            'distance_format' => 'DistanceFormat',
            'items_per_page' => 'ItemsPerPage',
            'start_week_day' => 'StartWeekDay',
            'enable_email_notification_exception' => 'EnableEmailNotificationException',
            'enable_email_notification_message' => 'EnableEmailNotificationMessage',
            'enable_email_notification_pay_stub' => 'EnableEmailNotificationPayStub',
            'enable_email_notification_home' => 'EnableEmailNotificationHome',
            'company_deduction' => 'CompanyDeduction',
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

    public function setCompany($id)
    {
        $id = trim($id);

        Debug::Text('Company ID: ' . $id, __FILE__, __LINE__, __METHOD__, 10);
        $clf = TTnew('CompanyListFactory');

        if ($this->Validator->isResultSetWithRows('company',
            $clf->getByID($id),
            TTi18n::gettext('Company is invalid')
            and
            $this->Validator->isTrue('company',
                $this->isUniqueCompany($id),
                TTi18n::gettext('Default settings for this company already exist'))
        )
        ) {
            $this->data['company_id'] = $id;

            return true;
        }

        return false;
    }

    public function isUniqueCompany($company_id)
    {
        $ph = array(
            'company_id' => (int)$company_id,
        );

        $query = 'select id from ' . $this->getTable() . ' where company_id = ? AND deleted=0';
        $unique_company_id = $this->db->GetOne($query, $ph);
        Debug::Arr($unique_company_id, 'Unique Company: ' . $this->getID(), __FILE__, __LINE__, __METHOD__, 10);

        if ($unique_company_id === false) {
            return true;
        } else {
            if ($unique_company_id == $this->getId()) {
                return true;
            }
        }

        return false;
    }

    public function getPermissionControl()
    {
        if (isset($this->data['permission_control_id'])) {
            return (int)$this->data['permission_control_id'];
        }

        return false;
    }

    public function setPermissionControl($id)
    {
        $id = trim($id);

        $pclf = TTnew('PermissionControlListFactory');

        if ($this->Validator->isResultSetWithRows('permission_control_id',
            $pclf->getByID($id),
            TTi18n::gettext('Permission Group is invalid')
        )
        ) {
            $this->data['permission_control_id'] = $id;

            return true;
        }

        return false;
    }

    public function getPayPeriodSchedule()
    {
        if (isset($this->data['pay_period_schedule_id'])) {
            return (int)$this->data['pay_period_schedule_id'];
        }

        return false;
    }

    public function setPayPeriodSchedule($id)
    {
        $id = trim($id);

        $ppslf = TTnew('PayPeriodScheduleListFactory');

        if ($id == 0
            or $this->Validator->isResultSetWithRows('pay_period_schedule_id',
                $ppslf->getByID($id),
                TTi18n::gettext('Pay Period schedule is invalid')
            )
        ) {
            $this->data['pay_period_schedule_id'] = $id;

            return true;
        }

        return false;
    }

    public function getPolicyGroup()
    {
        if (isset($this->data['policy_group_id'])) {
            return (int)$this->data['policy_group_id'];
        }

        return false;
    }

    public function setPolicyGroup($id)
    {
        $id = trim($id);

        $pglf = TTnew('PolicyGroupListFactory');

        if ($id == 0
            or $this->Validator->isResultSetWithRows('policy_group_id',
                $pglf->getByID($id),
                TTi18n::gettext('Policy Group is invalid')
            )
        ) {
            $this->data['policy_group_id'] = $id;

            return true;
        }

        return false;
    }

    public function getEmployeeNumber()
    {
        if (isset($this->data['employee_number'])) {
            return $this->data['employee_number'];
        }

        return false;
    }

    public function setEmployeeNumber($value)
    {
        $value = trim($value);

        if (
            $value == ''
            or
            $this->Validator->isLength('employee_number',
                $value,
                TTi18n::gettext('Employee number is too short or too long'),
                1,
                100)
        ) {
            $this->data['employee_number'] = $value;

            return true;
        }

        return false;
    }

    public function setTitle($id)
    {
        $id = trim($id);

        Debug::Text('Title ID: ' . $id, __FILE__, __LINE__, __METHOD__, 10);
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

    public function getDefaultBranch()
    {
        if (isset($this->data['default_branch_id'])) {
            return (int)$this->data['default_branch_id'];
        }

        return false;
    }

    public function setDefaultBranch($id)
    {
        $id = trim($id);

        Debug::Text('Branch ID: ' . $id, __FILE__, __LINE__, __METHOD__, 10);
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

    public function getDefaultDepartment()
    {
        if (isset($this->data['default_department_id'])) {
            return (int)$this->data['default_department_id'];
        }

        return false;
    }

    public function setDefaultDepartment($id)
    {
        $id = trim($id);

        Debug::Text('Department ID: ' . $id, __FILE__, __LINE__, __METHOD__, 10);
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

    public function getCurrency()
    {
        if (isset($this->data['currency_id'])) {
            return (int)$this->data['currency_id'];
        }

        return false;
    }

    public function setCurrency($id)
    {
        $id = trim($id);

        Debug::Text('Currency ID: ' . $id, __FILE__, __LINE__, __METHOD__, 10);
        $culf = TTnew('CurrencyListFactory');

        if (
        $this->Validator->isResultSetWithRows('currency',
            $culf->getByID($id),
            TTi18n::gettext('Invalid Currency')
        )
        ) {
            $this->data['currency_id'] = $id;

            return true;
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

    public function getProvince()
    {
        if (isset($this->data['province'])) {
            return $this->data['province'];
        }

        return false;
    }

    public function setProvince($province)
    {
        $province = trim($province);

        Debug::Text('Country: ' . $this->getCountry() . ' Province: ' . $province, __FILE__, __LINE__, __METHOD__, 10);

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

    public function getCountry()
    {
        if (isset($this->data['country'])) {
            return $this->data['country'];
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

    public function getWorkEmail()
    {
        if (isset($this->data['work_email'])) {
            return $this->data['work_email'];
        }

        return false;
    }

    public function setWorkEmail($work_email)
    {
        $work_email = trim($work_email);

        if ($work_email == ''
            or $this->Validator->isEmail('work_email',
                $work_email,
                TTi18n::gettext('Work Email address is invalid'))
        ) {
            $this->data['work_email'] = $work_email;

            return true;
        }

        return false;
    }

    public function getLanguage()
    {
        if (isset($this->data['language'])) {
            return $this->data['language'];
        }

        return false;
    }

    public function setLanguage($value)
    {
        $value = trim($value);

        $language_options = TTi18n::getLanguageArray();

        if ($this->Validator->inArrayKey('language',
            $value,
            TTi18n::gettext('Incorrect language'),
            $language_options)
        ) {
            $this->data['language'] = $value;

            return true;
        }

        return false;
    }

    /*

        User Preferences

    */

    public function getDateFormat()
    {
        if (isset($this->data['date_format'])) {
            return $this->data['date_format'];
        }

        return false;
    }

    public function setDateFormat($date_format)
    {
        $date_format = trim($date_format);
        $upf = TTnew('UserPreferenceFactory');

        if ($this->Validator->inArrayKey('date_format',
            $date_format,
            TTi18n::gettext('Incorrect date format'),
            Misc::trimSortPrefix($upf->getOptions('date_format')))
        ) {
            $this->data['date_format'] = $date_format;

            return true;
        }

        return false;
    }

    public function getTimeFormat()
    {
        if (isset($this->data['time_format'])) {
            return $this->data['time_format'];
        }

        return false;
    }

    public function setTimeFormat($time_format)
    {
        $time_format = trim($time_format);

        $upf = TTnew('UserPreferenceFactory');

        if ($this->Validator->inArrayKey('time_format',
            $time_format,
            TTi18n::gettext('Incorrect time format'),
            $upf->getOptions('time_format'))
        ) {
            $this->data['time_format'] = $time_format;

            return true;
        }

        return false;
    }

    public function getTimeZone()
    {
        if (isset($this->data['time_zone'])) {
            return $this->data['time_zone'];
        }

        return false;
    }

    public function setTimeZone($time_zone)
    {
        $time_zone = Misc::trimSortPrefix(trim($time_zone));

        $upf = TTnew('UserPreferenceFactory');
        if ($this->Validator->inArrayKey('time_zone',
            $time_zone,
            TTi18n::gettext('Incorrect time zone'),
            Misc::trimSortPrefix($upf->getOptions('time_zone')))
        ) {
            $this->data['time_zone'] = $time_zone;

            return true;
        }

        return false;
    }

    public function getTimeUnitFormatExample()
    {
        $options = $this->getOptions('time_unit_format');

        return $options[$this->getTimeUnitFormat()];
    }

    public function getTimeUnitFormat()
    {
        if (isset($this->data['time_unit_format'])) {
            return $this->data['time_unit_format'];
        }

        return false;
    }

    public function setTimeUnitFormat($time_unit_format)
    {
        $time_unit_format = trim($time_unit_format);

        $upf = TTnew('UserPreferenceFactory');
        if ($this->Validator->inArrayKey('time_unit_format',
            $time_unit_format,
            TTi18n::gettext('Incorrect time units'),
            $upf->getOptions('time_unit_format'))
        ) {
            $this->data['time_unit_format'] = $time_unit_format;

            return true;
        }

        return false;
    }

    public function getDistanceFormat()
    {
        if (isset($this->data['distance_format'])) {
            return $this->data['distance_format'];
        }

        return false;
    }

    public function setDistanceFormat($distance_format)
    {
        $distance_format = trim($distance_format);

        $upf = TTnew('UserPreferenceFactory');
        if ($this->Validator->inArrayKey('distance_format',
            $distance_format,
            TTi18n::gettext('Incorrect distance units'),
            $upf->getOptions('distance_format'))
        ) {
            $this->data['distance_format'] = $distance_format;

            return true;
        }

        return false;
    }

    public function getItemsPerPage()
    {
        if (isset($this->data['items_per_page'])) {
            return $this->data['items_per_page'];
        }

        return false;
    }

    public function setItemsPerPage($items_per_page)
    {
        $items_per_page = trim($items_per_page);

        if ($items_per_page != '' and $items_per_page >= 1 and $items_per_page <= 200) {
            $this->data['items_per_page'] = $items_per_page;

            return true;
        } else {
            $this->Validator->isTrue('items_per_page',
                false,
                TTi18n::gettext('Items per page must be between 10 and 200'));
        }

        return false;
    }

    public function getStartWeekDay()
    {
        if (isset($this->data['start_week_day'])) {
            return $this->data['start_week_day'];
        }

        return false;
    }

    public function setStartWeekDay($value)
    {
        $value = trim($value);

        $upf = TTnew('UserPreferenceFactory');
        if ($this->Validator->inArrayKey('start_week_day',
            $value,
            TTi18n::gettext('Incorrect day to start a week on'),
            $upf->getOptions('start_week_day'))
        ) {
            $this->data['start_week_day'] = $value;

            return true;
        }

        return false;
    }

    public function getEnableEmailNotificationException()
    {
        return $this->fromBool($this->data['enable_email_notification_exception']);
    }

    public function setEnableEmailNotificationException($bool)
    {
        $this->data['enable_email_notification_exception'] = $this->toBool($bool);

        return true;
    }

    public function getEnableEmailNotificationMessage()
    {
        return $this->fromBool($this->data['enable_email_notification_message']);
    }

    public function setEnableEmailNotificationMessage($bool)
    {
        $this->data['enable_email_notification_message'] = $this->toBool($bool);

        return true;
    }

    public function getEnableEmailNotificationPayStub()
    {
        return $this->fromBool($this->data['enable_email_notification_pay_stub']);
    }

    public function setEnableEmailNotificationPayStub($bool)
    {
        $this->data['enable_email_notification_pay_stub'] = $this->toBool($bool);

        return true;
    }

    public function getEnableEmailNotificationHome()
    {
        return $this->fromBool($this->data['enable_email_notification_home']);
    }

    public function setEnableEmailNotificationHome($bool)
    {
        $this->data['enable_email_notification_home'] = $this->toBool($bool);

        return true;
    }

    public function getCompanyDeduction()
    {
        $udcdlf = TTnew('UserDefaultCompanyDeductionListFactory');
        $udcdlf->getByUserDefaultId($this->getId());

        $list = array();
        foreach ($udcdlf as $obj) {
            $list[] = $obj->getCompanyDeduction();
        }

        if (empty($list) == false) {
            return $list;
        }

        return false;
    }

    public function setCompanyDeduction($ids)
    {
        Debug::text('Setting Company Deduction IDs : ', __FILE__, __LINE__, __METHOD__, 10);

        if ($ids == '') {
            $ids = array(); //This is for the API, it sends FALSE when no branches are selected, so this will delete all branches.
        }

        if (is_array($ids)) {
            $tmp_ids = array();
            if (!$this->isNew()) {
                //If needed, delete mappings first.
                $udcdlf = TTnew('UserDefaultCompanyDeductionListFactory');
                $udcdlf->getByUserDefaultId($this->getId());
                foreach ($udcdlf as $obj) {
                    $id = $obj->getCompanyDeduction();
                    Debug::text('ID: ' . $id, __FILE__, __LINE__, __METHOD__, 10);

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
            //$lf = TTnew( 'UserListFactory' );
            $cdlf = TTnew('CompanyDeductionListFactory');

            foreach ($ids as $id) {
                if ($id != false and isset($ids) and !in_array($id, $tmp_ids)) {
                    $udcdf = TTnew('UserDefaultCompanyDeductionFactory');
                    $udcdf->setUserDefault($this->getId());
                    $udcdf->setCompanyDeduction($id);

                    $obj = $cdlf->getById($id)->getCurrent();

                    if ($this->Validator->isTrue('company_deduction',
                        $udcdf->Validator->isValid(),
                        TTi18n::gettext('Deduction is invalid') . ' (' . $obj->getName() . ')')
                    ) {
                        $udcdf->save();
                    }
                }
            }

            return true;
        }

        Debug::text('No IDs to set.', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    /*

        Company Deductions

    */

    public function Validate($ignore_warning = true)
    {
        if ($this->getCompany() == false) {
            $this->Validator->isTrue('company',
                false,
                TTi18n::gettext('Company is invalid'));
        }

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
                        case 'hire_date':
                            $this->setHireDate(TTDate::parseDateTime($data['hire_date']));
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

    public function setHireDate($epoch)
    {
        if (empty($epoch)) {
            $epoch = null;
        }

        if ($epoch == ''
            or
            $this->Validator->isDate('hire_date',
                $epoch,
                TTi18n::gettext('Hire date is invalid'))
        ) {
            $this->data['hire_date'] = $epoch;

            return true;
        }

        return false;
    }

    //Support setting created_by, updated_by especially for importing data.
    //Make sure data is set based on the getVariableToFunctionMap order.

    public function getObjectAsArray($include_columns = null)
    {
        $data = array();
        $variable_function_map = $this->getVariableToFunctionMap();
        if (is_array($variable_function_map)) {
            foreach ($variable_function_map as $variable => $function_stub) {
                if ($include_columns == null or (isset($include_columns[$variable]) and $include_columns[$variable] == true)) {
                    $function = 'get' . $function_stub;
                    switch ($variable) {
                        case 'hire_date':
                            $data[$variable] = TTDate::getAPIDate('DATE', $this->getHireDate());
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

    public function getHireDate()
    {
        if (isset($this->data['hire_date'])) {
            return $this->data['hire_date'];
        }

        return false;
    }

    public function addLog($log_action)
    {
        return TTLog::addEntry($this->getId(), $log_action, TTi18n::getText('Employee Default Information'), null, $this->getTable(), $this);
    }
}
