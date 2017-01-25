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
 * @package Modules\Qualification
 */
class UserMembershipFactory extends Factory
{
    protected $table = 'user_membership';
    protected $pk_sequence_name = 'user_membership_id_seq'; //PK Sequence name
    protected $qualification_obj = null;

    public function _getFactoryOptions($name, $parent = null)
    {
        $retval = null;
        switch ($name) {
            case 'ownership':
                $retval = array(
                    10 => TTi18n::gettext('Company'),
                    20 => TTi18n::gettext('Individual'),
                );
                break;
            case 'columns':
                $retval = array(
                    '-1010-first_name' => TTi18n::gettext('First Name'),
                    '-1020-last_name' => TTi18n::gettext('Last Name'),
                    '-2050-qualification' => TTi18n::gettext('Membership'),
                    '-2040-group' => TTi18n::gettext('Group'),
                    '-4030-ownership' => TTi18n::gettext('Ownership'),
                    '-1060-amount' => TTi18n::gettext('Amount'),
                    '-2500-currency' => TTi18n::gettext('Currency'),
                    '-1080-start_date' => TTi18n::gettext('Start Date'),
                    '-4040-renewal_date' => TTi18n::gettext('Renewal Date'),
                    '-1300-tag' => TTi18n::gettext('Tags'),

                    '-1090-title' => TTi18n::gettext('Title'),
                    '-1099-user_group' => TTi18n::gettext('Employee Group'),
                    '-1100-default_branch' => TTi18n::gettext('Branch'),
                    '-1110-default_department' => TTi18n::gettext('Department'),

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
                    'qualification',
                    'ownership',
                    'amount',
                    'currency',
                    'start_date',
                    'renewal_date',
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
            'first_name' => false,
            'last_name' => false,
            'qualification_id' => 'Qualification',
            'qualification' => false,
            'group' => false,
            'ownership_id' => 'Ownership',
            'ownership' => false,
            'amount' => 'Amount',
            'currency_id' => 'Currency',
            'currency' => false,

            'start_date' => 'StartDate',

            'renewal_date' => 'RenewalDate',

            'tag' => 'Tag',
            'default_branch' => false,
            'default_department' => false,
            'user_group' => false,
            'title' => false,
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

    public function setQualification($id)
    {
        $id = trim($id);

        $qlf = TTnew('QualificationListFactory');

        if ($this->Validator->isResultSetWithRows('qualification_id',
            $qlf->getById($id),
            TTi18n::gettext('Invalid Qualification')
        )
        ) {
            $this->data['qualification_id'] = $id;

            return true;
        }

        return false;
    }

    public function getOwnership()
    {
        if (isset($this->data['ownership_id'])) {
            return (int)$this->data['ownership_id'];
        }
        return false;
    }

    public function setOwnership($ownership_id)
    {
        $ownership_id = trim($ownership_id);

        if ($this->Validator->inArrayKey('ownership_id',
            $ownership_id,
            TTi18n::gettext('Ownership is invalid'),
            $this->getOptions('ownership'))
        ) {
            $this->data['ownership_id'] = $ownership_id;

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
        $this->Validator->isResultSetWithRows('currency_id',
            $culf->getByID($id),
            TTi18n::gettext('Invalid Currency')
        )
        ) {
            $this->data['currency_id'] = $id;

            return true;
        }

        return false;
    }

    public function getAmount()
    {
        if (isset($this->data['amount'])) {
            return Misc::MoneyFormat($this->data['amount'], false);
        }

        return false;
    }

    public function setAmount($value)
    {
        $value = trim($value);

        //Pull out only digits and periods.
        $value = $this->Validator->stripNonFloat($value);

        if (
        $this->Validator->isFloat('amount',
            $value,
            TTi18n::gettext('Invalid Amount, Must be a numeric value'))
        ) {
            $this->data['amount'] = Misc::MoneyFormat($value, false);

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

    /*
    function getAmount() {
        if ( isset($this->data['amount']) ) {
            return $this->data['amount'];
        }

        return FALSE;
    }

    function setAmount($int) {
        $int = trim($int);

        if	( empty($int) ) {
            $int = 0;
        }

        if	(	$this->Validator->isNumeric(		'amount',
                                                    $int,
                                                    TTi18n::gettext('Incorrect Amount'))
                ) {

            $this->data['amount'] = $int;

            return TRUE;
        }

        return FALSE;
    }
    */

    public function Validate($ignore_warning = true)
    {
        //$this->setProvince( $this->getProvince() ); //Not sure why this was there, but it causes duplicate errors if the province is incorrect.

        return true;
    }

    public function preSave()
    {
        return true;
    }

    public function postSave()
    {
        $this->removeCache($this->getId());
        $this->removeCache($this->getUser() . $this->getQualification());

        if ($this->getDeleted() == false) {
            Debug::text('Setting Tags...', __FILE__, __LINE__, __METHOD__, 10);
            CompanyGenericTagMapFactory::setTags($this->getQualificationObject()->getCompany(), 255, $this->getID(), $this->getTag());
        }
        return true;
    }

    public function getUser()
    {
        if (isset($this->data['user_id'])) {
            return (int)$this->data['user_id'];
        }
        return false;
    }

    public function getQualification()
    {
        if (isset($this->data['qualification_id'])) {
            return (int)$this->data['qualification_id'];
        }
        return false;
    }

    public function getQualificationObject()
    {
        return $this->getGenericObject('QualificationListFactory', $this->getQualification(), 'qualification_obj');
    }

    public function getTag()
    {
        //Check to see if any temporary data is set for the tags, if not, make a call to the database instead.
        //postSave() needs to get the tmp_data.
        if (isset($this->tmp_data['tags'])) {
            return $this->tmp_data['tags'];
        } elseif (is_object($this->getQualificationObject()) and $this->getQualificationObject()->getCompany() > 0 and $this->getID() > 0) {
            return CompanyGenericTagMapListFactory::getStringByCompanyIDAndObjectTypeIDAndObjectID($this->getQualificationObject()->getCompany(), 255, $this->getID());
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
                        case 'start_date':
                            $this->setStartDate(TTDate::parseDateTime($data['start_date']));
                            break;
                        case 'renewal_date':
                            $this->setRenewalDate(TTDate::parseDateTime($data['renewal_date']));
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

    public function setStartDate($epoch)
    {
        $epoch = (!is_int($epoch)) ? trim($epoch) : $epoch; //Dont trim integer values, as it changes them to strings.

        if ($epoch == '') {
            $epoch = null;
        }
        if (($epoch != null)
            or
            $this->Validator->isDate('start_date',
                $epoch,
                TTi18n::gettext('Start date is invalid'))
        ) {
            //$this->data['start_date']  = TTDate::getBeginDayEpoch( $epoch );
            $this->data['start_date'] = $epoch;
            return true;
        }

        return false;
    }

    public function setRenewalDate($epoch)
    {
        $epoch = (!is_int($epoch)) ? trim($epoch) : $epoch; //Dont trim integer values, as it changes them to strings.

        if ($epoch == '') {
            $epoch = null;
        }
        if (($epoch != null)
            or
            $this->Validator->isDate('renewal_date',
                $epoch,
                TTi18n::gettext('Renewal date is invalid'))
        ) {
            //$this->data['renewal_date']	 = TTDate::getBeginDayEpoch( $epoch );
            $this->data['renewal_date'] = $epoch;
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
                        case 'qualification':
                        case 'group':
                        case 'currency':
                        case 'first_name':
                        case 'last_name':
                        case 'title':
                        case 'user_group':
                        case 'default_branch':
                        case 'default_department':
                            $data[$variable] = $this->getColumn($variable);
                            break;
                        case 'ownership':
                            $function = 'get' . $variable;
                            if (method_exists($this, $function)) {
                                $data[$variable] = Option::getByKey($this->$function(), $this->getOptions($variable));
                            }
                            break;
                        case 'start_date':
                            $data[$variable] = TTDate::getAPIDate('DATE', $this->getStartDate());
                            break;
                        case 'renewal_date':
                            $data['renewal_date'] = TTDate::getAPIDate('DATE', $this->getRenewalDate());
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

    public function getStartDate()
    {
        if (isset($this->data['start_date'])) {
            return $this->data['start_date'];
        }

        return false;
    }

    public function getRenewalDate()
    {
        if (isset($this->data['renewal_date'])) {
            return $this->data['renewal_date'];
        }

        return false;
    }

    public function addLog($log_action)
    {
        return TTLog::addEntry($this->getId(), $log_action, TTi18n::getText('Membership'), null, $this->getTable(), $this);
    }
}
