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

require_once('Numbers/Words.php');

/**
 * @package Modules\PayStubAmendment
 */
class PayStubAmendmentFactory extends Factory
{
    public $user_obj = null;
        public $pay_stub_entry_account_link_obj = null; //PK Sequence name
    public $pay_stub_entry_name_obj = null;
    public $pay_stub_obj = null;
    public $percent_amount_entry_name_obj = null;
    protected $table = 'pay_stub_amendment';
protected $pk_sequence_name = 'pay_stub_amendment_id_seq';

    public static function releaseAllAccruals($user_id, $effective_date = null)
    {
        Debug::Text('Release 100% of all accruals!', __FILE__, __LINE__, __METHOD__, 10);

        if ($user_id == '') {
            return false;
        }

        if ($effective_date == '') {
            $effective_date = TTDate::getTime();
        }
        Debug::Text('Effective Date: ' . TTDate::getDate('DATE+TIME', $effective_date), __FILE__, __LINE__, __METHOD__, 10);

        $ulf = TTnew('UserListFactory');
        $ulf->getById($user_id);
        if ($ulf->getRecordCount() > 0) {
            $user_obj = $ulf->getCurrent();
        } else {
            return false;
        }

        //Get all PSE acccount accruals
        $psealf = TTnew('PayStubEntryAccountListFactory');
        $psealf->getByCompanyIdAndStatusIdAndTypeId($user_obj->getCompany(), 10, 50);
        if ($psealf->getRecordCount() > 0) {
            $ulf->StartTransaction();
            foreach ($psealf as $psea_obj) {
                //Get PSE account that affects this accrual.
                //What if there are two accounts? It takes the first one in the list.
                $psealf_tmp = TTnew('PayStubEntryAccountListFactory');
                $psealf_tmp->getByCompanyIdAndAccrualId($user_obj->getCompany(), $psea_obj->getId());
                if ($psealf_tmp->getRecordCount() > 0) {
                    $release_account_id = $psealf_tmp->getCurrent()->getId();

                    $psaf = TTnew('PayStubAmendmentFactory');
                    $psaf->setStatus(50); //Active
                    $psaf->setType(20); //Percent
                    $psaf->setUser($user_obj->getId());
                    $psaf->setPayStubEntryNameId($release_account_id);
                    $psaf->setPercentAmount(100);
                    $psaf->setPercentAmountEntryNameId($psea_obj->getId());
                    $psaf->setEffectiveDate($effective_date);
                    $psaf->setDescription('Release Accrual Balance');

                    if ($psaf->isValid()) {
                        Debug::Text('Release Accrual Is Valid!!: ', __FILE__, __LINE__, __METHOD__, 10);
                        $psaf->Save();
                    }
                } else {
                    Debug::Text('No Release Account for this Accrual!!', __FILE__, __LINE__, __METHOD__, 10);
                }
            }

            //$ulf->FailTransaction();
            $ulf->CommitTransaction();
        } else {
            Debug::Text('No Accruals to release...', __FILE__, __LINE__, __METHOD__, 10);
        }

        return false;
    }

    public function _getFactoryOptions($name, $parent = null)
    {
        $retval = null;
        switch ($name) {
            case 'filtered_status':
                //Select box options;
                $status_options_filter = array(50);
                if ($this->getStatus() == 55) {
                    $status_options_filter = array(55);
                } elseif ($this->getStatus() == 52) {
                    $status_options_filter = array(52);
                }

                $retval = Option::getByArray($status_options_filter, $this->getOptions('status'));
                break;
            case 'status':
                $retval = array(
                    10 => TTi18n::gettext('NEW'),
                    20 => TTi18n::gettext('OPEN'),
                    30 => TTi18n::gettext('PENDING AUTHORIZATION'),
                    40 => TTi18n::gettext('AUTHORIZATION OPEN'),
                    50 => TTi18n::gettext('ACTIVE'),
                    52 => TTi18n::gettext('IN USE'),
                    55 => TTi18n::gettext('PAID'),
                    60 => TTi18n::gettext('DISABLED')
                );
                break;
            case 'type':
                $retval = array(
                    10 => TTi18n::gettext('Fixed'),
                    20 => TTi18n::gettext('Percent')
                );
                break;
            case 'pay_stub_account_type':
                $retval = array(10, 20, 30, 50, 60, 65, 80);
                break;
            case 'percent_pay_stub_account_type':
                $retval = array(10, 20, 30, 40, 50, 60, 65, 80);
                break;
            case 'export_type':
            case 'export_eft':
            case 'export_cheque':
                $psf = TTNew('PayStubFactory');
                $retval = $psf->getOptions($name);
                break;
            case 'columns':
                $retval = array(
                    '-1000-first_name' => TTi18n::gettext('First Name'),
                    '-1002-last_name' => TTi18n::gettext('Last Name'),
                    '-1005-user_status' => TTi18n::gettext('Employee Status'),
                    '-1010-title' => TTi18n::gettext('Title'),
                    '-1020-user_group' => TTi18n::gettext('Group'),
                    '-1030-default_branch' => TTi18n::gettext('Default Branch'),
                    '-1040-default_department' => TTi18n::gettext('Default Department'),

                    '-1110-status' => TTi18n::gettext('Status'),
                    '-1120-type' => TTi18n::gettext('Type'),
                    '-1130-pay_stub_entry_name' => TTi18n::gettext('Account'),
                    '-1140-effective_date' => TTi18n::gettext('Effective Date'),
                    '-1150-amount' => TTi18n::gettext('Amount'),
                    '-1160-rate' => TTi18n::gettext('Rate'),
                    '-1170-units' => TTi18n::gettext('Units'),
                    '-1180-description' => TTi18n::gettext('Pay Stub Note (Public)'),
                    '-1182-private_description' => TTi18n::gettext('Description (Private)'),
                    '-1190-ytd_adjustment' => TTi18n::gettext('YTD Adjustment'),

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
                    'status',
                    'pay_stub_entry_name',
                    'effective_date',
                    'amount',
                    'description',
                );
                break;
            case 'unique_columns': //Columns that are unique, and disabled for mass editing.
                $retval = array();
                break;
            case 'linked_columns': //Columns that are linked together, mainly for Mass Edit, if one changes, they all must.
                $retval = array();
                break;
        }

        return $retval;
    }

    public function getStatus()
    {
        if (isset($this->data['status_id'])) {
            return (int)$this->data['status_id'];
        }

        return false;
    }

    public function _getVariableToFunctionMap($data)
    {
        $variable_function_map = array(
            'id' => 'ID',
            'user_id' => 'User',

            'first_name' => false,
            'last_name' => false,
            'user_status_id' => false,
            'user_status' => false,
            'group_id' => false,
            'user_group' => false,
            'title_id' => false,
            'title' => false,
            'default_branch_id' => false,
            'default_branch' => false,
            'default_department_id' => false,
            'default_department' => false,

            'pay_stub_entry_name_id' => 'PayStubEntryNameId',
            'pay_stub_entry_name' => false,
            //'recurring_ps_amendment_id' => 'RecurringPayStubAmendmentId',
            'effective_date' => 'EffectiveDate',
            'status_id' => 'Status',
            'status' => false,
            'type_id' => 'Type',
            'type' => false,
            'rate' => 'Rate',
            'units' => 'Units',
            'amount' => 'Amount',
            'percent_amount' => 'PercentAmount',
            'percent_amount_entry_name_id' => 'PercentAmountEntryNameId',
            'ytd_adjustment' => 'YTDAdjustment',
            'description' => 'Description',
            'private_description' => 'PrivateDescription',
            'authorized' => 'Authorized',
            'deleted' => 'Deleted',
        );
        return $variable_function_map;
    }

    public function getPayStubEntryNameObject()
    {
        if (is_object($this->pay_stub_entry_name_obj)) {
            return $this->pay_stub_entry_name_obj;
        } else {
            $psealf = TTnew('PayStubEntryAccountListFactory');
            $psealf->getByID($this->getPayStubEntryNameId());
            if ($psealf->getRecordCount() > 0) {
                $this->pay_stub_entry_name_obj = $psealf->getCurrent();
                return $this->pay_stub_entry_name_obj;
            }

            return false;
        }
    }

    public function getPayStubEntryNameId()
    {
        if (isset($this->data['pay_stub_entry_name_id'])) {
            return (int)$this->data['pay_stub_entry_name_id'];
        }

        return false;
    }

    public function getPercentAmountEntryNameObject()
    {
        if (is_object($this->percent_amount_entry_name_obj)) {
            return $this->percent_amount_entry_name_obj;
        } else {
            $psealf = TTnew('PayStubEntryAccountListFactory');
            $psealf->getByID($this->getPercentAmountEntryNameId());
            if ($psealf->getRecordCount() > 0) {
                $this->percent_amount_entry_name_obj = $psealf->getCurrent();
                return $this->percent_amount_entry_name_obj;
            }

            return false;
        }
    }

    public function getPercentAmountEntryNameId()
    {
        if (isset($this->data['percent_amount_entry_name_id'])) {
            return (int)$this->data['percent_amount_entry_name_id'];
        }

        return false;
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

    public function setPayStubEntryNameId($id)
    {
        $id = trim($id);

        //$psenlf = TTnew( 'PayStubEntryNameListFactory' );
        $psealf = TTnew('PayStubEntryAccountListFactory');
        $result = $psealf->getById($id);
        //Debug::Arr($result, 'Result: ID: '. $id .' Rows: '. $result->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);

        if ($this->Validator->isResultSetWithRows('pay_stub_entry_name',
            $result,
            TTi18n::gettext('Invalid Pay Stub Account')
        )
        ) {
            $this->data['pay_stub_entry_name_id'] = $id;

            return true;
        }

        return false;
    }

    public function setName($name)
    {
        $name = trim($name);

        $psenlf = TTnew('PayStubEntryNameListFactory');
        $result = $psenlf->getByName($name);

        if ($this->Validator->isResultSetWithRows('name',
            $result,
            TTi18n::gettext('Invalid Entry Name')
        )
        ) {
            $this->data['pay_stub_entry_name_id'] = $result->getCurrent()->getId();

            return true;
        }

        return false;
    }

    public function getRecurringPayStubAmendmentId()
    {
        if (isset($this->data['recurring_ps_amendment_id'])) {
            return (int)$this->data['recurring_ps_amendment_id'];
        }

        return false;
    }

    public function setRecurringPayStubAmendmentId($id)
    {
        $id = trim($id);

        $rpsalf = TTnew('RecurringPayStubAmendmentListFactory');
        $rpsalf->getById($id);
        //Not sure why we tried to use $result here, as if the ID passed is NULL, it causes a fatal error.
        //$result = $rpsalf->getById( $id )->getCurrent();

        if (($id == null or $id == 0)
            //OR
            //$this->Validator->isResultSetWithRows(	'recurring_ps_amendment_id',
            //										$rpsalf,
            //										TTi18n::gettext('Invalid Recurring Pay Stub Amendment ID') )
        ) {
            $this->data['recurring_ps_amendment_id'] = $id;

            return true;
        }

        return false;
    }

    public function setEffectiveDate($epoch)
    {
        $epoch = (!is_int($epoch)) ? trim($epoch) : $epoch; //Dont trim integer values, as it changes them to strings.

        //Adjust effective date, because we won't want it to be a
        //day boundary and have issues with pay period start/end dates.
        //Although with employees in timezones that differ from the pay period timezones, there can still be issues.
        $epoch = TTDate::getMiddleDayEpoch($epoch);

        if ($this->Validator->isDate('effective_date',
            $epoch,
            TTi18n::gettext('Incorrect effective date'))
        ) {
            $this->data['effective_date'] = $epoch;

            return true;
        }

        return false;
    }

    public function setRate($value)
    {
        $value = trim($value);

        //Pull out only digits and periods.
        $value = $this->Validator->stripNonFloat($value);

        if ($value == 0 or $value == '') {
            $value = null;
        }

        if (empty($value) or
            (
                $this->Validator->isFloat('rate',
                    $value,
                    TTi18n::gettext('Invalid Rate'))
                and
                $this->Validator->isLength('rate',
                    $value,
                    TTi18n::gettext('Rate has too many digits'),
                    0,
                    21) //Need to include decimal.
                and
                $this->Validator->isLengthBeforeDecimal('rate',
                    $value,
                    TTi18n::gettext('Rate has too many digits before the decimal'),
                    0,
                    16)
                and
                $this->Validator->isLengthAfterDecimal('rate',
                    $value,
                    TTi18n::gettext('Rate has too many digits after the decimal'),
                    0,
                    4)
            )
        ) {
            Debug::text('Setting Rate to: ' . $value, __FILE__, __LINE__, __METHOD__, 10);
            //Must round to 2 decimals otherwise discreptancy can occur when generating pay stubs.
            //$this->data['rate'] = Misc::MoneyFormat( $value, FALSE );
            $this->data['rate'] = $value;

            return true;
        }

        return false;
    }

    public function setUnits($value)
    {
        $value = trim($value);

        //Pull out only digits and periods.
        $value = $this->Validator->stripNonFloat($value);

        if ($value == 0 or $value == '') {
            $value = null;
        }

        if (empty($value) or
            (
                $this->Validator->isFloat('units',
                    $value,
                    TTi18n::gettext('Invalid Units'))
                and
                $this->Validator->isLength('units',
                    $value,
                    TTi18n::gettext('Units has too many digits'),
                    0,
                    21) //Need to include decimal
                and
                $this->Validator->isLengthBeforeDecimal('units',
                    $value,
                    TTi18n::gettext('Units has too many digits before the decimal'),
                    0,
                    16)
                and
                $this->Validator->isLengthAfterDecimal('units',
                    $value,
                    TTi18n::gettext('Units has too many digits after the decimal'),
                    0,
                    4)
            )
        ) {
            //Must round to 2 decimals otherwise discreptancy can occur when generating pay stubs.
            //$this->data['units'] = Misc::MoneyFormat( $value, FALSE );
            $this->data['units'] = $value;

            return true;
        }

        return false;
    }

    public function getPayStubId()
    {
        //Find which pay period this effective date belongs too
        $pplf = TTnew('PayPeriodListFactory');
        $pplf->getByUserIdAndEndDate($this->getUser(), $this->getEffectiveDate());
        if ($pplf->getRecordCount() > 0) {
            $pp_obj = $pplf->getCurrent();
            Debug::text('Found Pay Period ID: ' . $pp_obj->getId(), __FILE__, __LINE__, __METHOD__, 10);

            $pslf = TTnew('PayStubListFactory');
            $pslf->getByUserIdAndPayPeriodId($this->getUser(), $pp_obj->getId());
            if ($pslf->getRecordCount() > 0) {
                $ps_obj = $pslf->getCurrent();
                Debug::text('Found Pay Stub for this effective date: ' . $ps_obj->getId(), __FILE__, __LINE__, __METHOD__, 10);

                return $ps_obj->getId();
            }
        }

        return false;
    }

    public function getUser()
    {
        if (isset($this->data['user_id'])) {
            return (int)$this->data['user_id'];
        }

        return false;
    }

    public function getEffectiveDate()
    {
        if (isset($this->data['effective_date'])) {
            return $this->data['effective_date'];
        }

        return false;
    }

    public function getCalculatedAmount($pay_stub_obj)
    {
        if (!is_object($pay_stub_obj)) {
            return false;
        }

        if ($this->getType() == 10) {
            //Fixed
            return $this->getAmount();
        } else {
            //Percent
            if ($this->getPercentAmountEntryNameId() != '') {
                $ps_amendment_percent_amount = $this->getPayStubEntryAmountSum($pay_stub_obj, array($this->getPercentAmountEntryNameId()));

                $pay_stub_entry_account = $pay_stub_obj->getPayStubEntryAccountArray($this->getPercentAmountEntryNameId());
                if (isset($pay_stub_entry_account['type_id']) and $pay_stub_entry_account['type_id'] == 50) {
                    //Get balance amount from previous pay stub so we can include that in our percent calculation.
                    $previous_pay_stub_amount_arr = $pay_stub_obj->getSumByEntriesArrayAndTypeIDAndPayStubAccountID('previous', null, array($this->getPercentAmountEntryNameId()));

                    $ps_amendment_percent_amount = bcadd($ps_amendment_percent_amount, $previous_pay_stub_amount_arr['ytd_amount']);
                    Debug::text('Pay Stub Amendment is a Percent of an Accrual, add previous pay stub accrual balance to amount: ' . $previous_pay_stub_amount_arr['ytd_amount'], __FILE__, __LINE__, __METHOD__, 10);
                }
                unset($pay_stub_entry_account, $previous_pay_stub_amount_arr);

                Debug::text('Pay Stub Amendment Total Amount: ' . $ps_amendment_percent_amount . ' Percent Amount: ' . $this->getPercentAmount(), __FILE__, __LINE__, __METHOD__, 10);
                if ($ps_amendment_percent_amount != 0 and $this->getPercentAmount() != 0) { //Allow negative values.
                    $amount = bcmul($ps_amendment_percent_amount, bcdiv($this->getPercentAmount(), 100));

                    return $amount;
                }
            }
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

    public function getAmount()
    {
        if (isset($this->data['amount'])) {
            return Misc::removeTrailingZeros((float)$this->data['amount'], 2);
        }

        return null;
    }

    public function getPayStubEntryAmountSum($pay_stub_obj, $ids)
    {
        if (!is_object($pay_stub_obj)) {
            return false;
        }

        if (!is_array($ids)) {
            return false;
        }

        $type_ids = array();

        //Get Linked accounts so we know which IDs are totals.
        $total_gross_key = array_search($this->getPayStubEntryAccountLinkObject()->getTotalGross(), $ids);
        if ($total_gross_key !== false) {
            $type_ids[] = 10;
            $type_ids[] = 60; //Automatically inlcude Advance Earnings here?
            unset($ids[$total_gross_key]);
        }
        unset($total_gross_key);

        $total_employee_deduction_key = array_search($this->getPayStubEntryAccountLinkObject()->getTotalEmployeeDeduction(), $ids);
        if ($total_employee_deduction_key !== false) {
            $type_ids[] = 20;
            unset($ids[$total_employee_deduction_key]);
        }
        unset($total_employee_deduction_key);

        $total_employer_deduction_key = array_search($this->getPayStubEntryAccountLinkObject()->getTotalEmployerDeduction(), $ids);
        if ($total_employer_deduction_key !== false) {
            $type_ids[] = 30;
            unset($ids[$total_employer_deduction_key]);
        }
        unset($total_employer_deduction_key);

        $type_amount_arr = array();
        $type_amount_arr['amount'] = 0;
        if (empty($type_ids) == false) {
            //$type_amount_arr = $pself->getSumByPayStubIdAndType( $pay_stub_id, $type_ids );
            $type_amount_arr = $pay_stub_obj->getSumByEntriesArrayAndTypeIDAndPayStubAccountID('current', $type_ids);
        }

        $amount_arr = array();
        $amount_arr['amount'] = 0;
        if (count($ids) > 0) {
            //Still other IDs left to total.
            //$amount_arr = $pself->getAmountSumByPayStubIdAndEntryNameID( $pay_stub_id, $ids );
            $amount_arr = $pay_stub_obj->getSumByEntriesArrayAndTypeIDAndPayStubAccountID('current', null, $ids);
        }

        $retval = bcadd($type_amount_arr['amount'], $amount_arr['amount']);

        Debug::text('Type Amount: ' . $type_amount_arr['amount'] . ' Regular Amount: ' . $amount_arr['amount'] . ' Total: ' . $retval, __FILE__, __LINE__, __METHOD__, 10);

        return $retval;
    }

    public function getPayStubEntryAccountLinkObject()
    {
        if (is_object($this->pay_stub_entry_account_link_obj)) {
            return $this->pay_stub_entry_account_link_obj;
        } else {
            $pseallf = TTnew('PayStubEntryAccountLinkListFactory');
            $pseallf->getByCompanyID($this->getUserObject()->getCompany());
            if ($pseallf->getRecordCount() > 0) {
                $this->pay_stub_entry_account_link_obj = $pseallf->getCurrent();
                return $this->pay_stub_entry_account_link_obj;
            }

            return false;
        }
    }

    public function getUserObject()
    {
        return $this->getGenericObject('UserListFactory', $this->getUser(), 'user_obj');
    }

    public function getPercentAmount()
    {
        if (isset($this->data['percent_amount'])) {
            return $this->data['percent_amount'];
        }

        return null;
    }

    public function setPercentAmount($value)
    {
        $value = trim($value);

        //Pull out only digits and periods.
        $value = $this->Validator->stripNonFloat($value);

        Debug::text('Amount: ' . $value . ' Name: ' . $this->getPayStubEntryNameId(), __FILE__, __LINE__, __METHOD__, 10);

        if ($value == null or $value == '') {
            return false;
        }

        if ($this->Validator->isFloat('percent_amount',
            $value,
            TTi18n::gettext('Invalid Percent')
        )
        ) {
            $this->data['percent_amount'] = round($value, 2);

            return true;
        }
        return false;
    }

    public function setPercentAmountEntryNameId($id)
    {
        $id = trim($id);

        $psealf = TTnew('PayStubEntryAccountListFactory');
        $psealf->getById($id);
        //Not sure why we tried to use $result here, as if the ID passed is NULL, it causes a fatal error.
        //$result = $psealf->getById( $id )->getCurrent();

        if (($id == null or $id == 0)
            or
            $this->Validator->isResultSetWithRows('percent_amount_entry_name',
                $psealf,
                TTi18n::gettext('Invalid Percent Of')
            )
        ) {
            $this->data['percent_amount_entry_name_id'] = $id;

            return true;
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

    public function setDescription($text)
    {
        $text = trim($text);

        if (strlen($text) == 0
            or
            $this->Validator->isLength('description',
                $text,
                TTi18n::gettext('Invalid Description Length'),
                2,
                100)
        ) {
            $this->data['description'] = htmlspecialchars($text);

            return true;
        }

        return false;
    }

    public function getPrivateDescription()
    {
        if (isset($this->data['private_description'])) {
            return $this->data['private_description'];
        }

        return false;
    }

    public function setPrivateDescription($text)
    {
        $text = trim($text);

        if (strlen($text) == 0
            or
            $this->Validator->isLength('description',
                $text,
                TTi18n::gettext('Invalid Description Length'),
                2,
                250)
        ) {
            $this->data['private_description'] = htmlspecialchars($text);

            return true;
        }

        return false;
    }

    public function getYTDAdjustment()
    {
        if (isset($this->data['ytd_adjustment'])) {
            return $this->fromBool($this->data['ytd_adjustment']);
        }

        return false;
    }

    public function setYTDAdjustment($bool)
    {
        $this->data['ytd_adjustment'] = $this->toBool($bool);

        return true;
    }

    public function setEnablePayStubStatusChange($bool)
    {
        $this->pay_stub_status_change = $bool;

        return true;
    }

    public function Validate($ignore_warning = true)
    {
        if ($this->getDeleted() == false) {
            if ($ignore_warning == false) {
                //This is needed for releasing vacation accrual after they have been terminated. Just make this a warning instead.
                if (is_object($this->getUserObject()) and $this->getUserObject()->getTerminationDate() != '' and TTDate::getMiddleDayEpoch($this->getEffectiveDate()) > TTDate::getMiddleDayEpoch($this->getUserObject()->getTerminationDate())) {
                    $this->Validator->Warning('effective_date', TTi18n::gettext('Effective date is after the employees termination date.'));
                }
            }

            if ($this->Validator->getValidateOnly() == false and $this->getUser() == false and $this->Validator->hasError('user_id') == false) {
                $this->Validator->isTrue('user_id',
                    false,
                    TTi18n::gettext('Invalid Employee'));
            }

            if (is_object($this->getUserObject()) and $this->getUserObject()->getHireDate() != '' and TTDate::getMiddleDayEpoch($this->getEffectiveDate()) < TTDate::getMiddleDayEpoch($this->getUserObject()->getHireDate())) {
                $this->Validator->isTrue('effective_date',
                    false,
                    TTi18n::gettext('Effective date is before the employees hire date.'));
            }

            $this->Validator->isTrue('user_id',
                $this->isUnique(),
                TTi18n::gettext('Another Pay Stub Amendment already exists for the same employee, account, effective date and amount'));
        }

        //Only show this error if it wasn't already triggered earlier.
        if ($this->Validator->getValidateOnly() == false and is_object($this->Validator) and $this->Validator->hasError('pay_stub_entry_name_id') == false and $this->getPayStubEntryNameId() == false) {
            $this->Validator->isTrue('pay_stub_entry_name_id',
                false,
                TTi18n::gettext('Invalid Pay Stub Account'));
        }

        if ($this->getType() == 10) {
            //If rate and units are set, and not amount, calculate the amount for us.
            if ($this->getRate() !== null and $this->getUnits() !== null and $this->getAmount() == null) {
                $this->preSave();
            }

            //Make sure rate * units = amount
            if ($this->getAmount() === null) {
                Debug::Text('Amount is NULL...', __FILE__, __LINE__, __METHOD__, 10);
                $this->Validator->isTrue('amount',
                    false,
                    TTi18n::gettext('Amount is blank or not specified'));
            }

            //Make sure amount is sane given the rate and units.
            if ($this->getRate() !== null and $this->getUnits() !== null
                and $this->getRate() != 0 and $this->getUnits() != 0
                and $this->getRate() != '' and $this->getUnits() != ''
                //AND ( Misc::MoneyFormat( bcmul( $this->getRate(), $this->getUnits() ), FALSE) ) != Misc::MoneyFormat( $this->getAmount(), FALSE )
                and (Misc::MoneyFormat($this->calcAmount(), false) != Misc::MoneyFormat($this->getAmount(), false)) //Use MoneyFormat here as the legacy interface doesn't handle more than two decimal places.
            ) {
                Debug::text('Validate: Rate: ' . $this->getRate() . ' Units: ' . $this->getUnits() . ' Amount: ' . $this->getAmount() . ' Calc: Amount: ' . $this->calcAmount() . ' Raw: ' . bcmul($this->getRate(), $this->getUnits(), 4), __FILE__, __LINE__, __METHOD__, 10);
                $this->Validator->isTrue('amount',
                    false,
                    TTi18n::gettext('Invalid Amount, calculation is incorrect'));
            }
        }

        //Check the status of any pay stub this is attached too. If its PAID then don't allow editing/deleting.
        if ($this->getEnablePayStubStatusChange() == false
            and ($this->getStatus() == 55
                or (is_object($this->getPayStubObject()) and $this->getPayStubObject()->getStatus() == 40))
        ) {
            $this->Validator->isTrue('user_id',
                false,
                TTi18n::gettext('Unable to modify Pay Stub Amendment that is currently in use by a Pay Stub marked PAID'));
        }

        //Don't allow these to be deleted in closed pay periods either.
        //Make sure effective date isn't in a CLOSED pay period?
        $pplf = TTNew('PayPeriodListFactory');
        $pplf->getByUserIdAndEndDate($this->getUser(), $this->getEffectiveDate());
        if ($pplf->getRecordCount() == 1) {
            $pp_obj = $pplf->getCurrent();

            //Only check for CLOSED (not locked) pay periods when the
            //status of the PSA is *not* 52=InUse and 55=PAID.
            //Allow deleting of 50=Active PSAs in CLOSED pay periods to make it easier to fix the warning that displays in this case when generating pay stubs.
            if ($pp_obj->getStatus() == 20 and (($this->getDeleted() == false and $this->getStatus() != 52 and $this->getStatus() != 55) or ($this->getDeleted() == true and $this->getStatus() != 50))) {
                $this->Validator->isTrue('effective_date',
                    false,
                    TTi18n::gettext('Pay Period that this effective date falls within is currently closed'));
            }
        }
        unset($pplf, $pp_obj);

        return true;
    }

    public function isUnique()
    {
        $ph = array(
            'user_id' => (int)$this->getUser(),
            //'status_id' => $this->getStatus(), //This allows IN USE vs ACTIVE PSA to exists, which shouldn't.
            'pay_stub_entry_name_id' => (int)$this->getPayStubEntryNameId(),
            'effective_date' => (int)$this->getEffectiveDate(),
            'amount' => (float)$this->getAmount(),
        );

        $query = 'select id from ' . $this->getTable() . ' where user_id = ? AND pay_stub_entry_name_id = ? AND effective_date = ? AND amount = ? AND deleted=0';
        $id = $this->db->GetOne($query, $ph);
        Debug::Arr($id, 'Unique PSA: ' . $id, __FILE__, __LINE__, __METHOD__, 10);

        if ($id === false) {
            return true;
        } else {
            if ($id == $this->getId()) {
                return true;
            }
        }

        return false;
    }

    public function getRate()
    {
        if (isset($this->data['rate'])) {
            return $this->data['rate'];
        }

        return null;
    }

    public function getUnits()
    {
        if (isset($this->data['units'])) {
            return $this->data['units'];
        }

        return null;
    }

    public function preSave()
    {
        //Authorize all pay stub amendments until we decide they will actually go through an authorization process
        if ($this->getAuthorized() == false) {
            $this->setAuthorized(true);
        }

        //Make sure we always have a status and type set.
        if ($this->getStatus() == false) {
            $this->setStatus(50);
        }
        if ($this->getType() == false) {
            $this->setType(10);
        }

        /*
        //Handle YTD adjustments just like any other amendment.
        if ( $this->getYTDAdjustment() == TRUE
                AND $this->getStatus() != 55
                AND $this->getStatus() != 60) {
            Debug::Text('Calculating Amount...', __FILE__, __LINE__, __METHOD__, 10);
            $this->setStatus( 52 );
        }
        */

        //If amount isn't set, but Rate and units are, calc amount for them.
        if (($this->getAmount() == null or $this->getAmount() == 0 or $this->getAmount() == '')
            and $this->getRate() !== null and $this->getUnits() !== null
            and $this->getRate() != 0 and $this->getUnits() != 0
            and $this->getRate() != '' and $this->getUnits() != ''
        ) {
            Debug::Text('Calculating Amount...', __FILE__, __LINE__, __METHOD__, 10);
            //$this->setAmount( bcmul( $this->getRate(), $this->getUnits(), 4 ) );
            $this->setAmount($this->calcAmount());
        }

        return true;
    }

    public function getAuthorized()
    {
        if (isset($this->data['authorized'])) {
            return $this->fromBool($this->data['authorized']);
        }

        return false;
    }

    //Used to determine if the pay stub is changing the status, so we can ignore some validation checks.

    public function setAuthorized($bool)
    {
        $this->data['authorized'] = $this->toBool($bool);

        return true;
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

    public function setType($type)
    {
        $type = trim($type);

        if ($this->Validator->inArrayKey('type',
            $type,
            TTi18n::gettext('Incorrect Type'),
            $this->getOptions('type'))
        ) {
            $this->data['type_id'] = $type;

            return true;
        }

        return false;
    }

    public function setAmount($value)
    {
        $value = trim($value);

        //Pull out only digits and periods.
        $value = $this->Validator->stripNonFloat($value);

        Debug::text('Amount: ' . $value . ' Name: ' . $this->getPayStubEntryNameId(), __FILE__, __LINE__, __METHOD__, 10);

        if ($value == null or $value == '') {
            return false;
        }

        if ($this->Validator->isFloat('amount',
                $value,
                TTi18n::gettext('Invalid Amount'))
            and
            $this->Validator->isLength('amount',
                $value,
                TTi18n::gettext('Amount has too many digits'),
                0,
                21) //Need to include decimal
            and
            $this->Validator->isLengthBeforeDecimal('amount',
                $value,
                TTi18n::gettext('Amount has too many digits before the decimal'),
                0,
                16)
            and
            $this->Validator->isLengthAfterDecimal('amount',
                $value,
                TTi18n::gettext('Amount has too many digits after the decimal'),
                0,
                4)
        ) {
            $this->data['amount'] = $value;

            return true;
        }

        return false;
    }

    public function calcAmount()
    {
        $retval = bcmul($this->getRate(), $this->getUnits(), 4);
        if (is_object($this->getUserObject()) and is_object($this->getUserObject()->getCurrencyObject())) {
            $retval = $this->getUserObject()->getCurrencyObject()->round($retval);
        } //else { //Debug::Text('No currency object found, amount: '. $retval, __FILE__, __LINE__, __METHOD__, 10);
        //Debug::Text('Amount: '. $retval, __FILE__, __LINE__, __METHOD__, 10);
        return $retval;
    }

    public function getEnablePayStubStatusChange()
    {
        if (isset($this->pay_stub_status_change)) {
            return $this->pay_stub_status_change;
        }

        return false;
    }

    public function getPayStubObject()
    {
        if (is_object($this->pay_stub_obj)) {
            return $this->pay_stub_obj;
        } else {
            $pslf = TTnew('PayStubListFactory');
            $pslf->getByUserIdAndPayStubAmendmentId($this->getUser(), $this->getID());
            if ($pslf->getRecordCount() > 0) {
                $this->pay_stub_obj = $pslf->getCurrent();
                return $this->pay_stub_obj;
            }

            return false;
        }
    }

    public function setObjectFromArray($data)
    {
        if (is_array($data)) {
            $variable_function_map = $this->getVariableToFunctionMap();
            foreach ($variable_function_map as $key => $function) {
                if (isset($data[$key])) {
                    $function = 'set' . $function;
                    switch ($key) {
                        case 'effective_date':
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
        $uf = TTnew('UserFactory');

        $data = array();
        $variable_function_map = $this->getVariableToFunctionMap();
        if (is_array($variable_function_map)) {
            foreach ($variable_function_map as $variable => $function_stub) {
                if ($include_columns == null or (isset($include_columns[$variable]) and $include_columns[$variable] == true)) {
                    $function = 'get' . $function_stub;
                    switch ($variable) {
                        case 'first_name':
                        case 'last_name':
                        case 'user_status_id':
                        case 'group_id':
                        case 'user_group':
                        case 'title_id':
                        case 'title':
                        case 'default_branch_id':
                        case 'default_branch':
                        case 'default_department_id':
                        case 'default_department':
                        case 'pay_stub_entry_name':
                            $data[$variable] = $this->getColumn($variable);
                            break;
                        case 'user_status':
                            $data[$variable] = Option::getByKey((int)$this->getColumn('user_status_id'), $uf->getOptions('status'));
                            break;
                        case 'status':
                        case 'type':
                            $function = 'get' . $variable;
                            if (method_exists($this, $function)) {
                                $data[$variable] = Option::getByKey($this->$function(), $this->getOptions($variable));
                            }
                            break;
                        case 'effective_date':
                            if (method_exists($this, $function)) {
                                $data[$variable] = TTDate::getAPIDate('DATE', $this->$function());
                            }
                            break;
                        case 'amount':
                            if ($this->getType() == 20) { //Show percent sign at end, so the user can tell the difference.
                                $data[$variable] = Misc::removeTrailingZeros((float)$this->getPercentAmount(), 0) . '%';
                            } else {
                                $data[$variable] = $this->getAmount();
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
            $this->getPermissionColumns($data, $this->getID(), $this->getCreatedBy(), $permission_children_ids, $include_columns);
            $this->getCreatedAndUpdatedColumns($data, $include_columns);
        }

        return $data;
    }

    public function addLog($log_action)
    {
        return TTLog::addEntry($this->getId(), $log_action, TTi18n::getText('Pay Stub Amendment - Employee') . ': ' . UserListFactory::getFullNameById($this->getUser()) . ' ' . TTi18n::getText('Amount') . ': ' . $this->getAmount(), null, $this->getTable(), $this);
    }
}
