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
 * @package Modules\Company
 */
class BranchFactory extends Factory
{
    protected $table = 'branch';
    protected $pk_sequence_name = 'branch_id_seq'; //PK Sequence name

    protected $address_validator_regex = '/^[a-zA-Z0-9-,_\/\.\'#\ |\x{0080}-\x{FFFF}]{1,250}$/iu';
    protected $city_validator_regex = '/^[a-zA-Z0-9-,_\.\'#\ |\x{0080}-\x{FFFF}]{1,250}$/iu';

    public static function getNextAvailableManualId($company_id = null)
    {
        global $current_company;

        if ($company_id == '' and is_object($current_company)) {
            $company_id = $current_company->getId();
        } elseif ($company_id == '' and isset($this) and is_object($this)) {
            $company_id = $this->getCompany();
        }

        $blf = TTnew('BranchListFactory');
        $blf->getHighestManualIDByCompanyId($company_id);
        if ($blf->getRecordCount() > 0) {
            $next_available_manual_id = ($blf->getCurrent()->getManualId() + 1);
        } else {
            $next_available_manual_id = 1;
        }

        return $next_available_manual_id;
    }

    public function getCompany()
    {
        if (isset($this->data['company_id'])) {
            return (int)$this->data['company_id'];
        }

        return false;
    }

    public function _getFactoryOptions($name, $parent = null)
    {
        $retval = null;
        switch ($name) {
            case 'status':
                $retval = array(
                    10 => TTi18n::gettext('ENABLED'),
                    20 => TTi18n::gettext('DISABLED')
                );
                break;
            case 'columns':
                $retval = array(
                    '-1010-status' => TTi18n::gettext('Status'),
                    '-1020-manual_id' => TTi18n::gettext('Code'),
                    '-1030-name' => TTi18n::gettext('Name'),

                    '-1140-address1' => TTi18n::gettext('Address 1'),
                    '-1150-address2' => TTi18n::gettext('Address 2'),
                    '-1160-city' => TTi18n::gettext('City'),
                    '-1170-province' => TTi18n::gettext('Province/State'),
                    '-1180-country' => TTi18n::gettext('Country'),
                    '-1190-postal_code' => TTi18n::gettext('Postal Code'),
                    '-1200-work_phone' => TTi18n::gettext('Work Phone'),
                    '-1210-fax_phone' => TTi18n::gettext('Fax Phone'),

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
                    'manual_id',
                    'name',
                    'city',
                    'province',
                );
                break;
            case 'unique_columns': //Columns that are unique, and disabled for mass editing.
                $retval = array(
                    'name',
                    'manual_id'
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
            'status_id' => 'Status',
            'status' => false,
            'manual_id' => 'ManualID',
            'name' => 'Name',
            'name_metaphone' => 'NameMetaphone',
            'address1' => 'Address1',
            'address2' => 'Address2',
            'city' => 'City',
            'country' => 'Country',
            'province' => 'Province',
            'postal_code' => 'PostalCode',
            'work_phone' => 'WorkPhone',
            'fax_phone' => 'FaxPhone',
            'other_id1' => 'OtherID1',
            'other_id2' => 'OtherID2',
            'other_id3' => 'OtherID3',
            'other_id4' => 'OtherID4',
            'other_id5' => 'OtherID5',
            'longitude' => 'Longitude',
            'latitude' => 'Latitude',
            'geo_fence_ids' => 'GEOFenceIds',
            'tag' => 'Tag',
            'deleted' => 'Deleted',
        );
        return $variable_function_map;
    }

    public function setCompany($id)
    {
        $id = trim($id);

        $clf = TTnew('CompanyListFactory');

        if ($id == 0
            or $this->Validator->isResultSetWithRows('company',
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
                2,
                100)
            and
            $this->Validator->isTrue('name',
                $this->isUniqueName($name),
                TTi18n::gettext('Branch name already exists'))

        ) {
            $this->data['name'] = $name;
            $this->setNameMetaphone($name);

            return true;
        }

        return false;
    }

    public function isUniqueName($name)
    {
        Debug::Arr($this->getCompany(), 'Company: ', __FILE__, __LINE__, __METHOD__, 10);
        if ($this->getCompany() == false) {
            return false;
        }

        $name = trim($name);
        if ($name == '') {
            return false;
        }

        $ph = array(
            'company_id' => (int)$this->getCompany(),
            'name' => TTi18n::strtolower($name),
        );

        $query = 'select id from ' . $this->getTable() . '
					where company_id = ?
						AND lower(name) = ?
						AND deleted = 0';
        $name_id = $this->db->GetOne($query, $ph);
        Debug::Arr($name_id, 'Unique Name: ' . $name, __FILE__, __LINE__, __METHOD__, 10);

        if ($name_id === false) {
            return true;
        } else {
            if ($name_id == $this->getId()) {
                return true;
            }
        }

        return false;
    }

    public function setNameMetaphone($value)
    {
        $value = metaphone(trim($value));

        if ($value != '') {
            $this->data['name_metaphone'] = $value;

            return true;
        }

        return false;
    }

    public function getNameMetaphone()
    {
        if (isset($this->data['name_metaphone'])) {
            return $this->data['name_metaphone'];
        }

        return false;
    }

    public function setAddress1($address1)
    {
        $address1 = trim($address1);

        if ($address1 != null
            and
            ($this->Validator->isRegEx('address1',
                    $address1,
                    TTi18n::gettext('Address1 contains invalid characters'),
                    $this->address_validator_regex)
                and
                $this->Validator->isLength('address1',
                    $address1,
                    TTi18n::gettext('Address1 is too short or too long'),
                    2,
                    250))
        ) {
            $this->data['address1'] = $address1;

            return true;
        }

        return false;
    }

    public function setAddress2($address2)
    {
        $address2 = trim($address2);

        if ($address2 != null
            and (
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

        if ($this->Validator->isRegEx('city',
                $city,
                TTi18n::gettext('City contains invalid characters'),
                $this->city_validator_regex)
            and
            $this->Validator->isLength('city',
                $city,
                TTi18n::gettext('City name is too short or too long'),
                2,
                250)
        ) {
            $this->data['city'] = $city;

            return true;
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

        if ($work_phone != null
            and $this->Validator->isPhoneNumber('work_phone',
                $work_phone,
                TTi18n::gettext('Work phone number is invalid'))
        ) {
            $this->data['work_phone'] = $work_phone;

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

        if ($fax_phone != null
            and $this->Validator->isPhoneNumber('fax_phone',
                $fax_phone,
                TTi18n::gettext('Fax phone number is invalid'))
        ) {
            $this->data['fax_phone'] = $fax_phone;

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

    public function getGEOFenceIds()
    {
        return CompanyGenericMapListFactory::getArrayByCompanyIDAndObjectTypeIDAndObjectID($this->getCompany(), 4000, $this->getID());
    }

    public function setGEOFenceIds($ids)
    {
        Debug::text('Setting GEO Fence IDs : ', __FILE__, __LINE__, __METHOD__, 10);
        return CompanyGenericMapFactory::setMapIDs($this->getCompany(), 4000, $this->getID(), (array)$ids);
    }

    public function setTag($tags)
    {
        $tags = trim($tags);

        //Save the tags in temporary memory to be committed in postSave()
        $this->tmp_data['tags'] = $tags;

        return true;
    }

    public function Validate($ignore_warning = true)
    {
        //$this->setProvince( $this->getProvince() ); //Not sure why this was there, but it causes duplicate errors if the province is incorrect.

        return true;
    }

    public function preSave()
    {
        if ($this->getStatus() == false) {
            $this->setStatus(10);
        }

        if ($this->getManualID() == false) {
            $this->setManualID(BranchListFactory::getNextAvailableManualId($this->getCompany()));
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

        if ($this->Validator->inArrayKey('status',
            $status,
            TTi18n::gettext('Incorrect Status'),
            $this->getOptions('status'))
        ) {
            $this->data['status_id'] = $status;

            return true;
        }

        return false;
    }

    public function getManualID()
    {
        if (isset($this->data['manual_id'])) {
            return (int)$this->data['manual_id'];
        }

        return false;
    }

    public function setManualID($value)
    {
        $value = $this->Validator->stripNonNumeric(trim($value));

        if ($this->Validator->isNumeric('manual_id',
                $value,
                TTi18n::gettext('Code is invalid'))
            and
            $this->Validator->isLength('manual_id',
                $value,
                TTi18n::gettext('Code has too many digits'),
                0,
                10)
            and
            $this->Validator->isTrue('manual_id',
                ($this->Validator->stripNon32bitInteger($value) === 0) ? false : true,
                TTi18n::gettext('Code is invalid, maximum value exceeded'))
            and
            $this->Validator->isTrue('manual_id',
                $this->isUniqueManualID($value),
                TTi18n::gettext('Code is already in use, please enter a different one'))
        ) {
            $this->data['manual_id'] = $value;

            return true;
        }

        return false;
    }

    public function isUniqueManualID($id)
    {
        if ($this->getCompany() == false) {
            return false;
        }

        $ph = array(
            'manual_id' => $id,
            'company_id' => $this->getCompany(),
        );

        $query = 'select id from ' . $this->getTable() . ' where manual_id = ? AND company_id = ? AND deleted=0';
        $id = $this->db->GetOne($query, $ph);
        Debug::Arr($id, 'Unique Code: ' . $id, __FILE__, __LINE__, __METHOD__, 10);

        if ($id === false) {
            return true;
        } else {
            if ($id == $this->getId()) {
                return true;
            }
        }

        return false;
    }

    public function postSave($data_diff = null)
    {
        $this->removeCache($this->getId());

        if ($this->getDeleted() == false) {
            CompanyGenericTagMapFactory::setTags($this->getCompany(), 110, $this->getID(), $this->getTag());

            $this->clearGeoCode($data_diff); //Clear Lon/Lat coordinates when address has changed.
        }

        if ($this->getDeleted() == true) {
            Debug::Text('UnAssign Hours from Branch: ' . $this->getId(), __FILE__, __LINE__, __METHOD__, 10);
            //Unassign hours from this branch.
            $pcf = TTnew('PunchControlFactory');
            $udtf = TTnew('UserDateTotalFactory');
            $uf = TTnew('UserFactory');
            $sf = TTnew('StationFactory');
            $sbf = TTnew('StationBranchFactory');
            $sf_b = TTnew('ScheduleFactory');
            $udf = TTnew('UserDefaultFactory');
            $rstf = TTnew('RecurringScheduleTemplateFactory');

            $query = 'update ' . $pcf->getTable() . ' set branch_id = 0 where branch_id = ' . (int)$this->getId();
            $this->db->Execute($query);

            $query = 'update ' . $udtf->getTable() . ' set branch_id = 0 where branch_id = ' . (int)$this->getId();
            $this->db->Execute($query);

            $query = 'update ' . $sf_b->getTable() . ' set branch_id = 0 where branch_id = ' . (int)$this->getId();
            $this->db->Execute($query);

            $query = 'update ' . $uf->getTable() . ' set default_branch_id = 0 where company_id = ' . (int)$this->getCompany() . ' AND default_branch_id = ' . (int)$this->getId();
            $this->db->Execute($query);

            $query = 'update ' . $udf->getTable() . ' set default_branch_id = 0 where company_id = ' . (int)$this->getCompany() . ' AND default_branch_id = ' . (int)$this->getId();
            $this->db->Execute($query);

            $query = 'update ' . $sf->getTable() . ' set branch_id = 0 where company_id = ' . (int)$this->getCompany() . ' AND branch_id = ' . (int)$this->getId();
            $this->db->Execute($query);

            $query = 'delete from ' . $sbf->getTable() . ' where branch_id = ' . (int)$this->getId();
            $this->db->Execute($query);

            $query = 'update ' . $rstf->getTable() . ' set branch_id = 0 where branch_id = ' . (int)$this->getId();
            $this->db->Execute($query);
        }

        return true;
    }

    public function getTag()
    {
        //Check to see if any temporary data is set for the tags, if not, make a call to the database instead.
        //postSave() needs to get the tmp_data.
        if (isset($this->tmp_data['tags'])) {
            return $this->tmp_data['tags'];
        } elseif ($this->getCompany() > 0 and $this->getID() > 0) {
            return CompanyGenericTagMapListFactory::getStringByCompanyIDAndObjectTypeIDAndObjectID($this->getCompany(), 110, $this->getID());
        }

        return false;
    }

    public function getMapURL()
    {
        return Misc::getMapURL($this->getAddress1(), $this->getAddress2(), $this->getCity(), $this->getProvince(), $this->getPostalCode(), $this->getCountry());
    }

    public function getAddress1()
    {
        if (isset($this->data['address1'])) {
            return $this->data['address1'];
        }

        return false;
    }

    public function getAddress2()
    {
        if (isset($this->data['address2'])) {
            return $this->data['address2'];
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
                        case 'status':
                            $function = 'get' . $variable;
                            if (method_exists($this, $function)) {
                                $data[$variable] = Option::getByKey($this->$function(), $this->getOptions($variable));
                            }
                            break;
                        case 'name_metaphone':
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
        return TTLog::addEntry($this->getId(), $log_action, TTi18n::getText('Branch') . ': ' . $this->getName(), null, $this->getTable(), $this);
    }

    public function getName()
    {
        if (isset($this->data['name'])) {
            return $this->data['name'];
        }

        return false;
    }
}
