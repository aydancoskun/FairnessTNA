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
class UserContactFactory extends Factory
{
    protected $table = 'user_contact';
    protected $pk_sequence_name = 'user_contact_id_seq'; //PK Sequence name

    protected $tmp_data = null;
    protected $user_obj = null;
    protected $name_validator_regex = '/^[a-zA-Z- \.\'|\x{0080}-\x{FFFF}]{1,250}$/iu';
    protected $address_validator_regex = '/^[a-zA-Z0-9-,_\/\.\'#\ |\x{0080}-\x{FFFF}]{1,250}$/iu';
    protected $city_validator_regex = '/^[a-zA-Z0-9-,_\.\'#\ |\x{0080}-\x{FFFF}]{1,250}$/iu';

    public function _getFactoryOptions($name, $parent = null)
    {
        $retval = null;
        switch ($name) {
            case 'status':
                $retval = array(
                    10 => TTi18n::gettext('ENABLED'),
                    20 => TTi18n::gettext('DISABLED'),
                );
                break;
            case 'type':
                $retval = array(
                    10 => TTi18n::gettext('Spouse/Partner'),
                    20 => TTi18n::gettext('Parent/Guardian'),
                    30 => TTi18n::gettext('Sibling'),
                    40 => TTi18n::gettext('Child'),
                    50 => TTi18n::gettext('Relative'),
                    60 => TTi18n::gettext('Dependant'),
                    70 => TTi18n::gettext('Emergency Contact'),
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
                    '-1090-employee_first_name' => TTi18n::gettext('Employee First Name'),
                    //'-1100-employee_middle_name' => TTi18n::gettext('Employee Middle Name'),
                    '-1110-employee_last_name' => TTi18n::gettext('Employee Last Name'),

                    '-1010-title' => TTi18n::gettext('Employee Title'),
                    '-1099-user_group' => TTi18n::gettext('Employee Group'),
                    '-1100-default_branch' => TTi18n::gettext('Employee Branch'),
                    '-1030-default_department' => TTi18n::gettext('Employee Department'),

                    '-1060-first_name' => TTi18n::gettext('First Name'),
                    '-1070-middle_name' => TTi18n::gettext('Middle Name'),
                    '-1080-last_name' => TTi18n::gettext('Last Name'),
                    '-1020-status' => TTi18n::gettext('Status'),
                    '-1050-type' => TTi18n::getText('Type'),

                    '-1120-sex' => TTi18n::gettext('Gender'),
                    '-1125-ethnic_group' => TTi18n::gettext('Ethnic Group'),

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
                    '-1280-sin' => TTi18n::gettext('SIN/SSN'),
                    '-1290-note' => TTi18n::gettext('Note'),
                    '-1300-tag' => TTi18n::gettext('Tags'),
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
                    //'status',
                    'employee_first_name',
                    'employee_last_name',
                    'title',
                    'user_group',
                    'default_branch',
                    'default_department',
                    'type',
                    'first_name',
                    'last_name',
                    'home_phone',
                );
                break;
            case 'unique_columns': //Columns that are unique, and disabled for mass editing.
                $retval = array(
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
            'user_id' => 'User',
            'status_id' => 'Status',
            'status' => false,
            'type_id' => 'Type',
            'type' => false,
            'employee_first_name' => false,
            'employee_last_name' => false,
            'default_branch' => false,
            'default_department' => false,
            'user_group' => false,
            'title' => false,
            'first_name' => 'FirstName',
            'first_name_metaphone' => 'FirstNameMetaphone',
            'middle_name' => 'MiddleName',
            'last_name' => 'LastName',
            'last_name_metaphone' => 'LastNameMetaphone',
            'sex_id' => 'Sex',
            'sex' => false,
            'ethnic_group_id' => 'EthnicGroup',
            'ethnic_group' => false,
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
            'work_email' => 'WorkEmail',
            'birth_date' => 'BirthDate',
            'sin' => 'SIN',
            'note' => 'Note',
            'tag' => 'Tag',
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

    public function getType()
    {
        if (isset($this->data['type_id'])) {
            return (int)$this->data['type_id'];
        }

        return false;
    }

    public function setType($type)
    {
        $type = trim($type);

        if ($this->Validator->inArrayKey('type_id',
            $type,
            TTi18n::gettext('Incorrect Type'),
            $this->getOptions('type'))
        ) {
            $this->data['type_id'] = $type;
            return true;
        }

        return false;
    }

    public function setFirstName($first_name)
    {
        $first_name = trim($first_name);

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
        $middle_name = trim($middle_name);

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
        $last_name = trim($last_name);

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

    public function getHomeEmail()
    {
        if (isset($this->data['home_email'])) {
            return $this->data['home_email'];
        }

        return false;
    }

    public function setHomeEmail($home_email)
    {
        $home_email = trim($home_email);

        $error_threshold = 7; //No DNS checks.
        if ($home_email == ''
            or $this->Validator->isEmailAdvanced('home_email',
                $home_email,
                TTi18n::gettext('Home Email address is invalid'),
                $error_threshold)
        ) {
            $this->data['home_email'] = $home_email;

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

        $error_threshold = 7; //No DNS checks.
        if ($work_email == ''
            or $this->Validator->isEmailAdvanced('work_email',
                $work_email,
                TTi18n::gettext('Work Email address is invalid'),
                $error_threshold)
        ) {
            $this->data['work_email'] = $work_email;

            return true;
        }

        return false;
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
                    (TTDate::getMiddleDayEpoch($epoch) <= TTDate::getMiddleDayEpoch((time() + (365 * 86400)))) ? true : false,
                    TTi18n::gettext('Birth date can not be more than one year in the future'))
            )
        ) {

            //Allow for negative epochs, for birthdates less than 1960's
            $this->data['birth_date'] = ($epoch != 0 and $epoch != '') ? TTDate::getMiddleDayEpoch($epoch) : ''; //Allow blank birthdate.

            return true;
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
            $this->Validator->isLength('sin',
                $sin,
                TTi18n::gettext('SIN is invalid'),
                6,
                20)
        ) {
            $this->data['sin'] = $sin;

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

        //Re-validate the province just in case the country was set AFTER the province.
        $this->setProvince($this->getProvince());
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

    public function preSave()
    {
        if ($this->getStatus() == false) {
            $this->setStatus(10); //ENABLE
        }

        if ($this->getSex() == false) {
            $this->setSex(5); //UnSpecified
        }

        if ($this->getEthnicGroup() == false) {
            $this->setEthnicGroup(0);
        }

        //Remember if this is a new user for postSave()
        if ($this->isNew()) {
            $this->is_new = true;
        }

        return true;
    }

    public function getStatus()
    {
        if (isset($this->data['status_id'])) {
            return (int)$this->data['status_id'];
        }

        return false;
    }

    public function setStatus($status)
    {
        $status = trim($status);

        if ($this->Validator->inArrayKey('status_id',
            $status,
            TTi18n::gettext('Incorrect Status'),
            $this->getOptions('status'))
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

        if ($this->Validator->inArrayKey('sex_id',
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

    public function postSave()
    {
        $this->removeCache($this->getId());

        if ($this->getDeleted() == false) {
            Debug::text('Setting Tags...', __FILE__, __LINE__, __METHOD__, 10);
            CompanyGenericTagMapFactory::setTags($this->getUserObject()->getCompany(), 230, $this->getID(), $this->getTag());
        }

        return true;
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
        return false;
    }

    public function getTag()
    {
        //Check to see if any temporary data is set for the tags, if not, make a call to the database instead.
        //postSave() needs to get the tmp_data.
        if (isset($this->tmp_data['tags'])) {
            return $this->tmp_data['tags'];
        } elseif (is_object($this->getUserObject()) and $this->getUserObject()->getCompany() > 0 and $this->getID() > 0) {
            return CompanyGenericTagMapListFactory::getStringByCompanyIDAndObjectTypeIDAndObjectID($this->getUserObject()->getCompany(), 230, $this->getID());
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
                        case 'birth_date':
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
                        case 'employee_first_name':
                        case 'employee_last_name':
                        case 'title':
                        case 'user_group':
                        case 'ethnic_group':
                        case 'default_branch':
                        case 'default_department':
                            $data[$variable] = $this->getColumn($variable);
                            break;
                        case 'full_name':
                            $data[$variable] = $this->getFullName(true);
                            break;
                        case 'status':
                        case 'type':
                        case 'sex':
                            $function = 'get' . $variable;
                            if (method_exists($this, $function)) {
                                $data[$variable] = Option::getByKey($this->$function(), $this->getOptions($variable));
                            }
                            break;
                        case 'sin':
                            $data[$variable] = $this->getSecureSIN();
                            break;
                        case 'birth_date':
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
                unset($function);
            }
            $this->getPermissionColumns($data, $this->getUser(), $this->getCreatedBy(), $permission_children_ids, $include_columns);
            $this->getCreatedAndUpdatedColumns($data, $include_columns);
        }

        return $data;
    }

    public function getFullName($reverse = false, $include_middle = true)
    {
        return Misc::getFullName($this->getFirstName(), $this->getMiddleInitial(), $this->getLastName(), $reverse, $include_middle);
    }

    public function getFirstName()
    {
        if (isset($this->data['first_name'])) {
            return $this->data['first_name'];
        }

        return false;
    }

    public function getMiddleInitial()
    {
        if ($this->getMiddleName() != '') {
            $middle_name = $this->getMiddleName();
            return $middle_name[0];
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

    //Support setting created_by, updated_by especially for importing data.
    //Make sure data is set based on the getVariableToFunctionMap order.

    public function getSecureSIN($sin = null)
    {
        if ($sin == '') {
            $sin = $this->getSIN();
        }
        if ($sin != '') {
            //Grab the first 1, and last 3 digits.
            $first_four = substr($sin, 0, 1);
            $last_four = substr($sin, -3);

            $total = (strlen($sin) - 4);

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

    public function addLog($log_action)
    {
        return TTLog::addEntry($this->getId(), $log_action, TTi18n::getText('Employee Contact') . ': ' . $this->getFullName(false, true), null, $this->getTable(), $this);
    }
}
