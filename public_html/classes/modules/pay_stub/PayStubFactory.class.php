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
 * @package Modules\PayStub
 */
class PayStubFactory extends Factory
{
public $validate_only = false;
        protected $table = 'pay_stub'; //PK Sequence name
protected $pk_sequence_name = 'pay_stub_id_seq';
    protected $tmp_data = array('previous_pay_stub' => null, 'current_pay_stub' => null);
    protected $is_unique_pay_stub = null;
    protected $is_unique_pay_stub_type = null;
    protected $pay_period_obj = null;
    protected $currency_obj = null;
    protected $user_obj = null;
    protected $pay_stub_entry_account_link_obj = null;
    protected $pay_stub_entry_accounts_obj = null; //Used by the API to ignore certain validation checks if we are doing validation only.

    public static function CalcDifferences($pay_stub_id1, $pay_stub_id2, $pay_stub_2_end_date, $ps_amendment_date = null)
    {
        $pay_stub_id1 = (int)$pay_stub_id1;
        $pay_stub_id2 = (int)$pay_stub_id2;

        //Allow passing blank/null old pay stub, so we can handle cases where an employee wasn't paid at all, but we need to carry-forward the transaction still.

        //PayStub 1 is new.
        //PayStub 2 is old.
        if ($pay_stub_id1 == 0) {
            return false;
        }

        if ($pay_stub_id1 == $pay_stub_id2) {
            return false;
        }

        Debug::Text('Calculating the differences between Pay Stub: ' . $pay_stub_id1 . ' and: ' . $pay_stub_id2, __FILE__, __LINE__, __METHOD__, 10);

        $pslf = TTnew('PayStubListFactory');

        $pslf->StartTransaction();

        $pslf_a = TTnew('PayStubListFactory');
        $pslf_a->getById($pay_stub_id1);
        if ($pslf_a->getRecordCount() > 0) {
            $pay_stub1_obj = $pslf_a->getCurrent();
        } else {
            Debug::Text('Pay Stub1 does not exist: ', __FILE__, __LINE__, __METHOD__, 10);
            return false;
        }
        unset($pslf_a);

        $pslf_b = TTnew('PayStubListFactory');
        $pslf_b->getById($pay_stub_id2);
        if ($pslf_b->getRecordCount() > 0) {
            $pay_stub2_obj = $pslf_b->getCurrent();
        } else {
            Debug::Text('Pay Stub2 does not exist: ', __FILE__, __LINE__, __METHOD__, 10);
        }
        unset($pslf_b);

        if (isset($pay_stub2_obj) and $pay_stub1_obj->getUser() != $pay_stub2_obj->getUser()) {
            Debug::Text('Pay Stubs are from different users!', __FILE__, __LINE__, __METHOD__, 10);
            return false;
        }

        if ($ps_amendment_date == null or $ps_amendment_date == '') {
            Debug::Text('PS Amendment Date not set, trying to figure it out!', __FILE__, __LINE__, __METHOD__, 10);
            //Take a guess at the end of the newest open pay period.
            $ppslf = TTnew('PayPeriodScheduleListFactory');
            $ppslf->getByUserId($pay_stub1_obj->getUser());
            if ($ppslf->getRecordCount() > 0) {
                Debug::Text('Found Pay Period Schedule, ID: ' . $ppslf->getCurrent()->getId(), __FILE__, __LINE__, __METHOD__, 10);
                $pplf = TTnew('PayPeriodListFactory');
                $pplf->getByPayPeriodScheduleIdAndTransactionDate($ppslf->getCurrent()->getId(), time(), null, array('a.transaction_date' => 'DESC'));
                if ($pplf->getRecordCount() > 0) {
                    Debug::Text('Using Pay Period End Date.', __FILE__, __LINE__, __METHOD__, 10);
                    $ps_amendment_date = TTDate::getBeginDayEpoch($pplf->getCurrent()->getEndDate());
                }
            } else {
                Debug::Text('Using Today.', __FILE__, __LINE__, __METHOD__, 10);
                $ps_amendment_date = time();
            }
        }
        Debug::Text('Using Date: ' . TTDate::getDate('DATE+TIME', $ps_amendment_date), __FILE__, __LINE__, __METHOD__, 10);

        //Only do Earnings for now.
        //Get all earnings, EE/ER deduction PS entries.
        $pay_stub1_entry_ids = array();
        $pay_stub1_entries = TTnew('PayStubEntryListFactory');
        $pay_stub1_entries->getByPayStubIdAndType($pay_stub1_obj->getId(), array(10, 20, 30));
        if ($pay_stub1_entries->getRecordCount() > 0) {
            Debug::Text('Pay Stub1 Entries DO exist: ', __FILE__, __LINE__, __METHOD__, 10);

            foreach ($pay_stub1_entries as $pay_stub1_entry_obj) {
                $pay_stub1_entry_ids[] = $pay_stub1_entry_obj->getPayStubEntryNameId();
            }
        } else {
            Debug::Text('Pay Stub1 Entries does not exist: ', __FILE__, __LINE__, __METHOD__, 10);
            return false;
        }
        Debug::Arr($pay_stub1_entry_ids, 'Pay Stub1 Entry IDs: ', __FILE__, __LINE__, __METHOD__, 10);

        $pay_stub2_entry_ids = array();
        if (isset($pay_stub2_obj)) {
            $pay_stub2_entries = TTnew('PayStubEntryListFactory');
            $pay_stub2_entries->getByPayStubIdAndType($pay_stub2_obj->getId(), array(10, 20, 30));
            if ($pay_stub2_entries->getRecordCount() > 0) {
                Debug::Text('Pay Stub2 Entries DO exist: ', __FILE__, __LINE__, __METHOD__, 10);
                foreach ($pay_stub2_entries as $pay_stub2_entry_obj) {
                    $pay_stub2_entry_ids[] = $pay_stub2_entry_obj->getPayStubEntryNameId();
                }
            } else {
                Debug::Text('Pay Stub2 Entries does not exist: ', __FILE__, __LINE__, __METHOD__, 10);
                return false;
            }
        }
        Debug::Arr($pay_stub2_entry_ids, 'Pay Stub2 Entry IDs: ', __FILE__, __LINE__, __METHOD__, 10);


        $pay_stub_entry_ids = array_unique(array_merge($pay_stub1_entry_ids, $pay_stub2_entry_ids));
        Debug::Arr($pay_stub_entry_ids, 'Pay Stub Entry Differences: ', __FILE__, __LINE__, __METHOD__, 10);
        //var_dump($pay_stub_entry_ids);

        $pself = TTnew('PayStubEntryListFactory');
        if (count($pay_stub_entry_ids) > 0) {
            foreach ($pay_stub_entry_ids as $pay_stub_entry_id) {
                Debug::Text('Entry ID: ' . $pay_stub_entry_id, __FILE__, __LINE__, __METHOD__, 10);
                $pay_stub1_entry_arr = $pself->getSumByPayStubIdAndEntryNameIdAndNotPSAmendment($pay_stub1_obj->getId(), $pay_stub_entry_id);

                if (isset($pay_stub2_obj)) {
                    $pay_stub2_entry_arr = $pself->getSumByPayStubIdAndEntryNameIdAndNotPSAmendment($pay_stub2_obj->getId(), $pay_stub_entry_id);
                } else {
                    $pay_stub2_entry_arr = array('amount' => 0, 'units' => 0);
                }
                Debug::Text('Pay Stub1 Amount: ' . $pay_stub1_entry_arr['amount'] . ' Pay Stub2 Amount: ' . $pay_stub2_entry_arr['amount'], __FILE__, __LINE__, __METHOD__, 10);

                if ($pay_stub1_entry_arr['amount'] != $pay_stub2_entry_arr['amount']) {
                    $amount_diff = bcsub($pay_stub1_entry_arr['amount'], $pay_stub2_entry_arr['amount'], 2);
                    $units_diff = abs(bcsub($pay_stub1_entry_arr['units'], $pay_stub2_entry_arr['units'], 2));
                    Debug::Text('FOUND DIFFERENCE of: Amount: ' . $amount_diff . ' Units: ' . $units_diff, __FILE__, __LINE__, __METHOD__, 10);

                    //Generate PS Amendment.
                    $psaf = TTnew('PayStubAmendmentFactory');
                    $psaf->setUser($pay_stub1_obj->getUser());
                    $psaf->setStatus(50); //Active
                    $psaf->setType(10);
                    $psaf->setPayStubEntryNameId($pay_stub_entry_id);

                    if ($units_diff > 0) {
                        //Re-calculate amount when units are involved, due to rounding issues.
                        //FIXME: However in the case of salaried employees, where there were no units previously, or no units after,
                        //don't use unit calculation to get the amount, just use the amount directly, as it could be different than what they expect.
                        // For example a salaried employee doesn't get paid in a previous PP, the before pay stub doesn't exist, but the new pay stub
                        // could have 42.5 units at an amont of 254.80 (but no rate specified).
                        // However 254.80 / 42.50 = 5.995, which rounds to 6.00 * 42.5 = 255.00. So its $0.20 different when using a rate calculation.
                        // If we just check to see if before/after units != 0, it will break having units in any other case where the line item didn't exist before, like adding overtime.
                        //   Not sure if there is an easy way to fix this...
                        $unit_rate = Misc::MoneyFormat(bcdiv($amount_diff, $units_diff));
                        $amount_diff = Misc::MoneyFormat(bcmul($unit_rate, $units_diff));
                        Debug::Text('bFOUND DIFFERENCE of: Amount: ' . $amount_diff . ' Units: ' . $units_diff . ' Unit Rate: ' . $unit_rate, __FILE__, __LINE__, __METHOD__, 10);

                        $psaf->setRate($unit_rate);
                        $psaf->setUnits($units_diff);
                        $psaf->setAmount($amount_diff);
                    } else {
                        $psaf->setAmount($amount_diff);
                    }

                    $psaf->setDescription('Adjustment from Pay Period Ending: ' . TTDate::getDate('DATE', $pay_stub_2_end_date));

                    $psaf->setEffectiveDate(TTDate::getBeginDayEpoch($ps_amendment_date));

                    if ($psaf->isValid()) {
                        $psaf->Save();
                    }

                    unset($amount_diff, $units_diff, $unit_rate);
                } else {
                    Debug::Text('No DIFFERENCE!', __FILE__, __LINE__, __METHOD__, 10);
                }
            }
        }

        $pslf->CommitTransaction();

        return true;
    }

    public function _getFactoryOptions($name, $param = null)
    {
        $retval = null;
        switch ($name) {
            case 'filtered_status':
                $retval = Option::getByArray(array(25, 40, 100), $this->getOptions('status'));
                break;
            case 'status':
                $retval = array(
                    10 => TTi18n::gettext('NEW'),
                    20 => TTi18n::gettext('LOCKED'),
                    25 => TTi18n::gettext('Open'),
                    30 => TTi18n::gettext('Pending Transaction'),
                    40 => TTi18n::gettext('Paid'),
                    100 => TTi18n::gettext('Opening Balance (YTD)'),
                );
                break;
            case 'type':
                $retval = array(
                    10 => TTi18n::gettext('Normal (In-Cycle)'), //In-Cycle
                    20 => TTi18n::gettext('Bonus/Correction (Out-of-Cycle)'), //Out-of-Cycle
                );
                //$param should be the pay_period status_id.
                if (is_array($param) and count(array_unique($param)) === 1 and end($param) === 30) {
                    $retval[5] = TTi18n::gettext('Post-Adjustment Carry-Forward'); //Just generate PSA's in the next pay period.
                    ksort($retval);
                }
                break;
            case 'export_type':
                $retval = array();
                $retval += array('00' => TTi18n::gettext('-- Direct Deposit --'));
                $retval += $this->getOptions('export_eft');
                $retval += array(
                    '01' => '',
                    '02' => TTi18n::gettext('-- Laser Cheques --'));
                $retval += $this->getOptions('export_cheque');
                break;
            case 'export_eft':
                $retval = array(
                    //EFT formats must start with "eft_"
                    '-1010-eft_ACH' => TTi18n::gettext('United States - ACH (94-Byte)'),
                    '-1020-eft_1464' => TTi18n::gettext('Canada - EFT (1464-Byte)'),
                    '-1022-eft_1464_cibc' => TTi18n::gettext('Canada - EFT CIBC (1464-Byte)'),
                    '-1023-eft_1464_rbc' => TTi18n::gettext('Canada - EFT RBC (1464-Byte)'),
                    '-1030-eft_105' => TTi18n::gettext('Canada - EFT (105-Byte)'),
                    '-1040-eft_HSBC' => TTi18n::gettext('Canada - HSBC EFT-PC (CSV)'),
                    '-1050-eft_BEANSTREAM' => TTi18n::gettext('Beanstream (CSV)'),
                );
                break;
            case 'export_cheque':
                $retval = array(
                    //Cheque formats must start with "cheque_"
                    '-2010-cheque_9085' => TTi18n::gettext('NEBS #9085'),
                    '-2020-cheque_9209p' => TTi18n::gettext('NEBS #9209P'),
                    '-2030-cheque_dlt103' => TTi18n::gettext('NEBS #DLT103'),
                    '-2040-cheque_dlt104' => TTi18n::gettext('NEBS #DLT104'),
                    //Disable Costa Rica formats for now as they don't appear to be correct anymore.
                    //'-2050-cheque_cr_standard_form_1' => TTi18n::gettext('Costa Rica - Std Form 1'),
                    //'-2060-cheque_cr_standard_form_2' => TTi18n::gettext('Costa Rica - Std Form 2'),
                );
                break;
            case 'export_general_ledger':
                $retval = array(
                    '-2010-export_csv' => TTi18n::gettext('Excel (CSV)'),
                    '-2020-simply' => TTi18n::gettext('Simply Accounting GL'),
                    '-2030-quickbooks' => TTi18n::gettext('Quickbooks GL'),
                    '-2040-sage300' => TTi18n::gettext('Sage 300 (Accpac)'),
                );
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
                    '-1050-city' => TTi18n::gettext('City'),
                    '-1060-province' => TTi18n::gettext('Province/State'),
                    '-1070-country' => TTi18n::gettext('Country'),
                    '-1080-currency' => TTi18n::gettext('Currency'),
                    //'-1080-pay_period' => TTi18n::gettext('Pay Period'),

                    '-1140-status' => TTi18n::gettext('Status'),
                    '-1150-type' => TTi18n::gettext('Type'),
                    '-1170-start_date' => TTi18n::gettext('Start Date'),
                    '-1180-end_date' => TTi18n::gettext('End Date'),
                    '-1190-transaction_date' => TTi18n::gettext('Transaction Date'),
                    '-1200-run_id' => TTi18n::gettext('Payroll Run'),

                    '-2000-created_by' => TTi18n::gettext('Created By'),
                    '-2010-created_date' => TTi18n::gettext('Created Date'),
                    '-2020-updated_by' => TTi18n::gettext('Updated By'),
                    '-2030-updated_date' => TTi18n::gettext('Updated Date'),
                );
                break;
            case 'list_columns':
                $columns = array('status', 'start_date', 'end_date', 'transaction_date', 'run_id', 'type');
                $retval = Misc::arrayIntersectByKey($columns, Misc::trimSortPrefix($this->getOptions('columns')));
                break;
            case 'default_display_columns': //Columns that are displayed by default.
                $retval = array(
                    'first_name',
                    'last_name',
                    'status',
                    'start_date',
                    'end_date',
                    'transaction_date',
                    'run_id',
                    'type',
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
            'city' => false,
            'province' => false,
            'country' => false,
            'currency' => false,

            'pay_period_id' => 'PayPeriod',
            'type_id' => 'Type',
            'type' => false,
            'run_id' => 'Run',
            //'pay_period' => FALSE,
            'currency_id' => 'Currency',
            'currency' => false,
            'currency_rate' => 'CurrencyRate',
            'start_date' => 'StartDate',
            'end_date' => 'EndDate',
            'transaction_date' => 'TransactionDate',
            'status_id' => 'Status',
            'status' => false,
            'status_date' => 'StatusDate',
            'status_by' => 'StatusBy',
            'tainted' => 'Tainted',
            'temp' => 'Temp',
            'deleted' => 'Deleted',
        );
        return $variable_function_map;
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

    public function setPayPeriod($id)
    {
        $id = trim($id);

        $pplf = TTnew('PayPeriodListFactory');

        if ($this->Validator->isResultSetWithRows('start_date', //pay_period label isn't used when editing pay stubs.
            $pplf->getByID($id),
            TTi18n::gettext('Invalid Pay Period')
        )
        ) {
            $this->data['pay_period_id'] = $id;

            return true;
        }

        return false;
    }

    public function setRun($id)
    {
        $id = trim($id);

        if (
            $this->Validator->isGreaterThan('run_id', //pay_period label isn't used when editing pay stubs.
                $id,
                TTi18n::gettext('Payroll Run must higher than 1'),
                1
            )
            and
            $this->Validator->isLessThan('run_id', //pay_period label isn't used when editing pay stubs.
                $id,
                TTi18n::gettext('Payroll Run must be less than 128'),
                128
            )
        ) {
            $this->data['run_id'] = $id;

            return true;
        }

        return false;
    }

    public function setCurrency($id)
    {
        $id = trim($id);

        Debug::Text('Currency ID: ' . $id, __FILE__, __LINE__, __METHOD__, 10);
        $culf = TTnew('CurrencyListFactory');

        $old_currency_id = $this->getCurrency();

        if (
        $this->Validator->isResultSetWithRows('currency',
            $culf->getByID($id),
            TTi18n::gettext('Invalid Currency')
        )
        ) {
            $this->data['currency_id'] = $id;

            if ($culf->getRecordCount() == 1
                and ($this->isNew() or $old_currency_id != $id)
            ) {
                $this->setCurrencyRate($culf->getCurrent()->getReverseConversionRate());
            }

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

    public function setCurrencyRate($value)
    {
        $value = trim($value);

        //Pull out only digits and periods.
        $value = $this->Validator->stripNonFloat($value);

        if ($this->Validator->isFloat('currency_rate',
            $value,
            TTi18n::gettext('Incorrect Currency Rate'))
        ) {
            $this->data['currency_rate'] = $value;

            return true;
        }

        return false;
    }

    public function getCurrencyRate()
    {
        if (isset($this->data['currency_rate'])) {
            return $this->data['currency_rate'];
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
            $this->setStatusDate();
            $this->setStatusBy();

            $this->data['status_id'] = $status;

            return true;
        }

        return false;
    }

    public function setStatusDate($epoch = null)
    {
        $epoch = (!is_int($epoch)) ? trim($epoch) : $epoch; //Dont trim integer values, as it changes them to strings.

        if ($epoch == null) {
            $epoch = TTDate::getTime();
        }

        if ($this->Validator->isDate('status_date',
            $epoch,
            TTi18n::gettext('Incorrect Date'))
        ) {
            $this->data['status_date'] = $epoch;

            return true;
        }

        return false;
    }

    public function setStatusBy($id = null)
    {
        $id = trim($id);

        if (empty($id)) {
            global $current_user;

            if (is_object($current_user)) {
                $id = $current_user->getID();
            } else {
                return false;
            }
        }

        $ulf = TTnew('UserListFactory');

        if ($this->Validator->isResultSetWithRows('created_by',
            $ulf->getByID($id),
            TTi18n::gettext('Incorrect User')
        )
        ) {
            $this->data['status_by'] = $id;

            return true;
        }

        return false;
    }

    public function getStatusDate()
    {
        if (isset($this->data['status_date'])) {
            return $this->data['status_date'];
        }

        return false;
    }

    public function getStatusBy()
    {
        if (isset($this->data['status_by'])) {
            return $this->data['status_by'];
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

    public function getTainted()
    {
        if (isset($this->data['tainted'])) {
            return $this->fromBool($this->data['tainted']);
        }

        return false;
    }

    public function setTainted($bool)
    {
        $this->data['tainted'] = $this->toBool($bool);

        return true;
    }

    public function setTemp($bool)
    {
        $this->data['temp'] = $this->toBool($bool);

        return true;
    }

    public function setDefaultDates()
    {
        $start_date = $this->getPayPeriodObject()->getStartDate();
        $end_date = $this->getPayPeriodObject()->getEndDate();
        $transaction_date = $this->getPayPeriodObject()->getTransactionDate();

        Debug::Text('Start Date: ' . TTDate::getDate('DATE+TIME', $start_date), __FILE__, __LINE__, __METHOD__, 10);
        Debug::Text('End Date: ' . TTDate::getDate('DATE+TIME', $end_date), __FILE__, __LINE__, __METHOD__, 10);

        $this->setStartDate($start_date);
        $this->setEndDate($end_date);
        $this->setTransactionDate($transaction_date);

        Debug::Text('Transaction Date: Before: ' . TTDate::getDate('DATE+TIME', $transaction_date) . ' After: ' . TTDate::getDate('DATE+TIME', $this->getTransactionDate()), __FILE__, __LINE__, __METHOD__, 10);
        return true;
    }

    public function getPayPeriodObject()
    {
        return $this->getGenericObject('PayPeriodListFactory', $this->getPayPeriod(), 'pay_period_obj');
    }

    public function getPayPeriod()
    {
        if (isset($this->data['pay_period_id'])) {
            return (int)$this->data['pay_period_id'];
        }

        return false;
    }

    public function setStartDate($epoch)
    {
        $epoch = (!is_int($epoch)) ? trim($epoch) : $epoch; //Dont trim integer values, as it changes them to strings.

        if ($epoch != '') {
            //Make sure all pay periods start at the first second of the day.
            $epoch = TTDate::getTimeLockedDate(strtotime('00:00:00', $epoch), $epoch);
        }

        if ($this->Validator->isDate('start_date',
                $epoch,
                TTi18n::gettext('Incorrect start date'))
            and
            $this->Validator->isTrue('start_date',
                $this->isValidStartDate($epoch),
                TTi18n::gettext('Conflicting start date, does not match pay period'))

        ) {
            $this->data['start_date'] = TTDate::getDBTimeStamp($epoch, false);

            return true;
        }

        return false;
    }

    public function isValidStartDate($epoch)
    {
        if (is_object($this->getPayPeriodObject()) and
            ($epoch >= $this->getPayPeriodObject()->getStartDate() and $epoch < $this->getPayPeriodObject()->getEndDate())
        ) {
            return true;
        }

        return false;
    }

    public function setEndDate($epoch)
    {
        $epoch = (!is_int($epoch)) ? trim($epoch) : $epoch; //Dont trim integer values, as it changes them to strings.

        if ($epoch != '') {
            //Make sure all pay periods end at the last second of the day.
            $epoch = TTDate::getTimeLockedDate(strtotime('23:59:59', $epoch), $epoch);
        }

        if ($this->Validator->isDate('end_date',
                $epoch,
                TTi18n::gettext('Incorrect end date'))
            and
            $this->Validator->isTrue('end_date',
                $this->isValidEndDate($epoch),
                TTi18n::gettext('Conflicting end date, does not match pay period'))

        ) {
            $this->data['end_date'] = TTDate::getDBTimeStamp($epoch, false);

            return true;
        }

        return false;
    }

    public function isValidEndDate($epoch)
    {
        //Allow a 59 second grace period around the pay period end date, due to seconds being stripped in some cases.
        if (is_object($this->getPayPeriodObject()) and
            ($epoch <= ($this->getPayPeriodObject()->getEndDate() + 59) and $epoch >= $this->getPayPeriodObject()->getStartDate())
        ) {
            return true;
        } elseif (is_object($this->getPayPeriodObject()) == false) {
            //In cases where mass editing pay stubs and changing just the end date or transaction date, if the pay period dropdown box is not checked to be mass edited as well,
            //  then there won't be a pay period object, and it will always cause a validation error. This confuses users, so just assume if no pay period object exists the date is correct.
            return true;
        }

        return false;
    }

    public function setTransactionDate($epoch)
    {
        $epoch = (!is_int($epoch)) ? trim($epoch) : $epoch; //Dont trim integer values, as it changes them to strings.

        if ($epoch != '') {
            //Make sure all pay periods transact at noon.
            $epoch = TTDate::getTimeLockedDate(strtotime('12:00:00', $epoch), $epoch);

            //Unless they are on the same date as the end date, then it should match that.
            if ($this->getEndDate() != '' and $this->getEndDate() > $epoch) {
                $epoch = $this->getEndDate();
            }
        }

        if ($this->Validator->isDate('transaction_date',
            $epoch,
            TTi18n::gettext('Incorrect transaction date'))
        ) {
            $this->data['transaction_date'] = TTDate::getDBTimeStamp($epoch, false);

            return true;
        }

        return false;
    }

    public function getEndDate($raw = false)
    {
        if (isset($this->data['end_date'])) {
            if ($raw === true) {
                return $this->data['end_date'];
            } else {
                //In cases where you set the date, then immediately read it again, it will return -1 unless do this.
                return TTDate::strtotime($this->data['end_date']);
            }
        }

        return false;
    }

    public function getTransactionDate($raw = false)
    {
        //Debug::Text('Transaction Date: '. $this->data['transaction_date'] .' - '. TTDate::getDate('DATE+TIME', $this->data['transaction_date']), __FILE__, __LINE__, __METHOD__, 10);
        if (isset($this->data['transaction_date'])) {
            if ($raw === true) {
                return $this->data['transaction_date'];
            } else {
                return TTDate::strtotime($this->data['transaction_date']);
            }
        }

        return false;
    }

    public function setEnableProcessEntries($bool)
    {
        $this->process_entries = (bool)$bool;

        return true;
    }

    public function setEnableEmail($bool)
    {
        $this->email = (bool)$bool;

        return true;
    }

    public function setEnableLinkedAccruals($bool)
    {
        $this->linked_accruals = (bool)$bool;

        return true;
    }

    public function preSave()
    {
        /*
        if ( $this->getEnableProcessEntries() == TRUE ) {
            Debug::Text('Processing PayStub Entries...', __FILE__, __LINE__, __METHOD__, 10);

            $this->processEntries();
            //$this->savePayStubEntries();
        } else {
            Debug::Text('NOT Processing PayStub Entries...', __FILE__, __LINE__, __METHOD__, 10);
        }
        */

        return true;
    }

    public function Validate($ignore_warning = true)
    {
        Debug::Text('Validating PayStub...', __FILE__, __LINE__, __METHOD__, 10);

        if ($this->getType() == 5 and $this->getTemp() == false) {
            $this->Validator->isTrue('type_id',
                false,
                TTi18n::gettext('Invalid type, must be a temporary pay stub instead'));
        }

        //We could re-check these after processEntries are validated,
        //but that might duplicate the error messages?
        if ($this->getDeleted() == false and $this->isUniquePayStub() == false) {
            Debug::Text('Unique Pay Stub...', __FILE__, __LINE__, __METHOD__, 10);
            $this->Validator->isTrue('user_id',
                false,
                TTi18n::gettext('Employee already has a pay stub for this Pay Period and Payroll Run'));
        }

        if ($this->getDeleted() == false and $this->isUniquePayStubType() == false) {
            Debug::Text('Unique Pay Stub Type...', __FILE__, __LINE__, __METHOD__, 10);
            $this->Validator->isTrue('type_id',
                false,
                TTi18n::gettext('Employee already has %1 pay stub for this Pay Period', array(Option::getByKey((int)$this->getType(), $this->getOptions('type')))));
        }

        //When mass editing, don't require all dates to be set.
        if ($this->Validator->getValidateOnly() == false) {
            if (!($this->getUser() > 0 and is_object($this->getUserObject()))) {
                $this->Validator->isTrue('user_id',
                    false,
                    TTi18n::gettext('Employee is not specified'));
            }

            if ($this->getCurrency() == false) {
                $this->Validator->isTrue('currency_id',
                    false,
                    TTi18n::gettext('Currency not specified'));
            }
            if ($this->getStartDate() == false) {
                $this->Validator->isDate('start_date',
                    $this->getStartDate(),
                    TTi18n::gettext('Incorrect start date'));
            }
            if ($this->getEndDate() == false) {
                $this->Validator->isDate('end_date',
                    $this->getEndDate(),
                    TTi18n::gettext('Incorrect end date'));
            }
            if ($this->getTransactionDate() == false) {
                $this->Validator->isDate('transaction_date',
                    $this->getTransactionDate(),
                    TTi18n::gettext('Incorrect transaction date'));
            }

            if ($this->isValidTransactionDate($this->getTransactionDate()) == false) {
                $this->Validator->isTrue('transaction_date',
                    false,
                    TTi18n::gettext('Transaction date is before pay period end date'));
            }
        }

        //Make sure they aren't setting a pay stub to OPEN if the pay period is closed.
        if (is_object($this->getPayPeriodObject())) {
            if ($this->getDeleted() == true) {
                if ($this->getStatus() == 40 or $this->getStatus() == 100) {
                    $this->Validator->isTrue('status_id',
                        false,
                        TTi18n::gettext('Unable to delete pay stubs that are marked as PAID'));
                }

                if ($this->getPayPeriodObject()->getStatus() == 20) {
                    $this->Validator->isTrue('status_id',
                        false,
                        TTi18n::gettext('Unable to delete pay stubs in closed pay periods'));
                }
            } else {
                //Make sure we aren't creating a new pay stub in a already closed pay period
                if ($this->getStatus() != 40 and $this->getPayPeriodObject()->getStatus() == 20) {
                    if ($this->isNew() == true) {
                        $this->Validator->isTrue('status_id',
                            false,
                            TTi18n::gettext('Unable to create pay stubs in a closed pay period'));
                    } else {
                        $this->Validator->isTrue('status_id',
                            false,
                            TTi18n::gettext('Unable to modify pay stubs assigned to a closed pay period'));
                    }
                }
            }
        }

        //Make sure transaction date is not earlier than a pay stub in the same pay period but having an high payroll run.
        $pslf = TTNew('PayStubListFactory');
        if ($this->getUser() > 0 and is_object($this->getUserObject())) {
            $pslf->getByUserIdAndCompanyIdAndPayPeriodId($this->getUser(), $this->getUserObject()->getCompany(), array($this->getPayPeriod()));
            if ($pslf->getRecordCount() > 0) {
                foreach ($pslf as $ps_obj) {
                    Debug::Text('  Checking conflicting transaction dates: Pay Stub ID: ' . $ps_obj->getId() . ' Pay Period ID: ' . $this->getPayPeriod() . ' Transaction Date: ' . TTDate::getDate('DATE', $ps_obj->getTransactionDate()) . '(' . $this->getTransactionDate() . ') Run: ' . $ps_obj->getRun(), __FILE__, __LINE__, __METHOD__, 10);
                    if ($ps_obj->getRun() < $this->getRun() and TTDate::getMiddleDayEpoch($this->getTransactionDate()) < TTDate::getMiddleDayEpoch($ps_obj->getTransactionDate())) {
                        $this->Validator->isTrue('transaction_date',
                            false,
                            TTi18n::gettext('Transaction Date in this pay period cannot come before a previous payroll run transaction date'));
                        break;
                    }

                    if ($ps_obj->getRun() > $this->getRun() and TTDate::getMiddleDayEpoch($this->getTransactionDate()) > TTDate::getMiddleDayEpoch($ps_obj->getTransactionDate())) {
                        $this->Validator->isTrue('transaction_date',
                            false,
                            TTi18n::gettext('Transaction Date in this pay period cannot come after a subsequent payroll run transaction date'));
                        break;
                    }
                }
                unset($ps_obj);
            }
        } //PSLF is used lower down.

        if ($this->getDeleted() == false and $this->getStatus() == 100 and $this->getStartDate() != '') { //Opening Balance
            //Check for any earlier pay stubs so Opening Balance Pay Stubs must be first.
            $pslf->getLastPayStubByUserIdAndStartDateAndRun($this->getUser(), $this->getStartDate(), $this->getRun());
            if ($pslf->getRecordCount() > 0) {
                $this->Validator->isTrue('status_id',
                    false,
                    TTi18n::gettext('Opening Balance Pay Stubs must not come after any other pay stub for this employee'));
            }
        }

        if ($this->getEnableProcessEntries() == true) {
            $this->ValidateEntries();
        }

        return true;
    }

    public function getType()
    {
        if (isset($this->data['type_id'])) {
            return (int)$this->data['type_id'];
        }

        return false;
    }

    public function getTemp()
    {
        if (isset($this->data['temp'])) {
            return $this->fromBool($this->data['temp']);
        }

        return false;
    }

    public function isUniquePayStub()
    {
        if ($this->getTemp() == true) {
            return true;
        }

        if ($this->is_unique_pay_stub === null) {
            $ph = array(
                'pay_period_id' => (int)$this->getPayPeriod(),
                'user_id' => (int)$this->getUser(),
                'run_id' => (int)$this->getRun(),
            );

            $query = 'select id from ' . $this->getTable() . ' where pay_period_id = ? AND user_id = ? AND run_id = ? AND deleted = 0';
            $pay_stub_id = $this->db->GetOne($query, $ph);

            if ($pay_stub_id === false) {
                $this->is_unique_pay_stub = true;
            } else {
                if ($pay_stub_id == $this->getId()) {
                    $this->is_unique_pay_stub = true;
                } else {
                    $this->is_unique_pay_stub = false;
                }
            }
        }

        return $this->is_unique_pay_stub;
    }

    public function getUser()
    {
        if (isset($this->data['user_id'])) {
            return (int)$this->data['user_id'];
        }

        return false;
    }

    public function getRun()
    {
        if (isset($this->data['run_id'])) {
            return (int)$this->data['run_id'];
        }

        return 1; //Always default to 1 if its not set otherwise.
    }

    public function isUniquePayStubType()
    {
        //Only 10=Regular (In-Cycle) types are unique.
        if ($this->getType() == 20 or $this->getTemp() == true) {
            return true;
        }

        if ($this->is_unique_pay_stub_type === null) {
            $ph = array(
                'pay_period_id' => (int)$this->getPayPeriod(),
                'user_id' => (int)$this->getUser(),
                'type_id' => (int)$this->getType(),
            );

            $query = 'select id from ' . $this->getTable() . ' where pay_period_id = ? AND user_id = ? AND type_id = ? AND deleted = 0';
            $pay_stub_id = $this->db->GetOne($query, $ph);

            if ($pay_stub_id === false) {
                $this->is_unique_pay_stub_type = true;
            } else {
                if ($pay_stub_id == $this->getId()) {
                    $this->is_unique_pay_stub_type = true;
                } else {
                    $this->is_unique_pay_stub_type = false;
                }
            }
        }

        return $this->is_unique_pay_stub_type;
    }

    public function getUserObject()
    {
        return $this->getGenericObject('UserListFactory', $this->getUser(), 'user_obj');
    }

    public function getStartDate($raw = false)
    {
        if (isset($this->data['start_date'])) {
            if ($raw === true) {
                return $this->data['start_date'];
            } else {
                //return $this->db->UnixTimeStamp( $this->data['start_date'] );
                //strtotime is MUCH faster than UnixTimeStamp
                //Must use ADODB for times pre-1970 though.
                return TTDate::strtotime($this->data['start_date']);
            }
        }

        return false;
    }

    public function isValidTransactionDate($epoch)
    {
        Debug::Text('Epoch: ' . $epoch . ' ( ' . TTDate::getDate('DATE+TIME', $epoch) . ' ) Pay Stub End Date: ' . TTDate::getDate('DATE+TIME', $this->getEndDate()), __FILE__, __LINE__, __METHOD__, 10);
        if ($epoch >= $this->getEndDate()) {
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

    public function getEnableProcessEntries()
    {
        if (isset($this->process_entries)) {
            return $this->process_entries;
        }

        return false;
    }

    public function ValidateEntries()
    {
        Debug::Text('Validating PayStub Entries...', __FILE__, __LINE__, __METHOD__, 10);

        //Do Pay Stub Entry checks here
        if ($this->isNew() == false) {
            //Make sure the pay stub math adds up.
            Debug::Text('Validate: checkEarnings...', __FILE__, __LINE__, __METHOD__, 10);
            $this->Validator->isTrue('earnings',
                $this->checkNoEarnings(),
                TTi18n::gettext('No Earnings, employee may not have any hours for this pay period, or their wage may not be set'));

            $this->Validator->isTrue('earnings',
                $this->checkEarnings(),
                TTi18n::gettext('Earnings don\'t match gross pay'));


            Debug::Text('Validate: checkDeductions...', __FILE__, __LINE__, __METHOD__, 10);
            $this->Validator->isTrue('deductions',
                $this->checkDeductions(),
                TTi18n::gettext('Deductions don\'t match total deductions'));

            Debug::Text('Validate: checkNetPay...', __FILE__, __LINE__, __METHOD__, 10);
            $this->Validator->isTrue('net_pay',
                $this->checkNetPay(),
                TTi18n::gettext('Net Pay doesn\'t match earnings or deductions'));
        }

        return $this->Validator->isValid();
    }

    public function checkNoEarnings()
    {
        $earnings = $this->getEarningSum();
        if ($earnings == false or $earnings['amount'] <= 0) {
            return false;
        }

        return true;
    }

    public function getEarningSum()
    {
        $retarr = $this->getSumByEntriesArrayAndTypeIDAndPayStubAccountID('current', 10);
        Debug::Text('Earnings Sum (' . $this->getId() . '): ' . $retarr['amount'], __FILE__, __LINE__, __METHOD__, 10);

        return $retarr;
    }

    public function getSumByEntriesArrayAndTypeIDAndPayStubAccountID($ps_entries, $type_ids = null, $ps_account_ids = null)
    {
        //Debug::text('PS Entries: '. $ps_entries .' Type ID: '. count($type_ids) .' PS Account ID: '. count($ps_account_ids), __FILE__, __LINE__, __METHOD__, 10);

        if (strtolower($ps_entries) == 'current') {
            $entries = $this->tmp_data['current_pay_stub'];
        } elseif (strtolower($ps_entries) == 'previous') {
            $entries = $this->tmp_data['previous_pay_stub']['entries'];
        } elseif (strtolower($ps_entries) == 'previous+ytd_adjustment') {
            $entries = $this->tmp_data['previous_pay_stub']['entries'];
            //Include any YTD adjustment PS amendments in the current entries as if they occurred in the previous pay stub.
            //This so we can account for the first pay stub having a YTD adjustment that exceeds a wage base amount, so no amount is calculated.
            if (is_array($this->tmp_data['current_pay_stub'])) {
                foreach ($this->tmp_data['current_pay_stub'] as $current_entry_arr) {
                    if (isset($current_entry_arr['ytd_adjustment']) and $current_entry_arr['ytd_adjustment'] === true) {
                        Debug::Text('Found YTD Adjustment in current pay stub when calculating previous pay stub amounts... Amount: ' . $current_entry_arr['amount'], __FILE__, __LINE__, __METHOD__, 10);
                        //Debug::Arr($current_entry_arr, 'Found YTD Adjustment in current pay stub when calculating previous pay stub amounts...', __FILE__, __LINE__, __METHOD__, 10);
                        $entries[] = $current_entry_arr;
                    }
                }
                unset($current_entry_arr);
            }
        }
        //Debug::Arr( $entries, 'Sum Entries Array: ', __FILE__, __LINE__, __METHOD__, 10);

        if (!is_array($entries)) {
            Debug::text('Returning FALSE...', __FILE__, __LINE__, __METHOD__, 10);
            return false;
        }

        if ($type_ids != '' and !is_array($type_ids)) {
            $type_ids = array($type_ids);
        }

        if ($ps_account_ids != '' and !is_array($ps_account_ids)) {
            $ps_account_ids = array($ps_account_ids);
        }

        $retarr = array(
            'units' => 0,
            'amount' => 0,
            'ytd_units' => 0,
            'ytd_amount' => 0,
        );

        foreach ($entries as $key => $entry_arr) {
            if ($type_ids != '' and is_array($type_ids)) {
                foreach ($type_ids as $type_id) {
                    if (isset($entry_arr['pay_stub_entry_type_id']) and $type_id == $entry_arr['pay_stub_entry_type_id'] and $entry_arr['pay_stub_entry_type_id'] != 50) {
                        if (isset($entry_arr['ytd_adjustment']) and $entry_arr['ytd_adjustment'] === true) {
                            //If a PS amendment makes a YTD adjustment, we need to treat it as a regular PS amendment
                            //affecting the 'amount' instead of the 'ytd_amount', otherwise it will double up YTD amounts.
                            //There are two issues at hand, doubling up YTD amounts, and not counting YTD adjustments
                            //towards getting YTD amounts on the current pay stub for things like calculating
                            //Wage Base/Maximum contributions.
                            //Also, we need to make sure that these amounts aren't included in Tax/Deduction calculations
                            //for this pay stub. But ARE calculated in this pay stub if they affect accruals.
                            //FIXME: I think we need to change this so YTD adjustment PS amendments are just "magically" included in the YTD Amount on pay stubs
                            //		 at anytime, then add a flag to have reports such as Tax reports include YTD adjustments or not. (enabled by default)
                            //		 This should cut down on clutter/confusion with any pay stubs that currently have YTD amounts, as well as offer flexibility
                            //		 to add these amounts in at anytime without having to regenerate pay stubs, so corrections can be made at the end of the year.
                            $retarr['ytd_amount'] = bcadd($retarr['ytd_amount'], $entry_arr['amount']);
                            $retarr['ytd_units'] = bcadd($retarr['ytd_units'], $entry_arr['units']);
                        } else {
                            $retarr['amount'] = bcadd($retarr['amount'], $entry_arr['amount']);
                            $retarr['units'] = bcadd($retarr['units'], $entry_arr['units']);
                            $retarr['ytd_amount'] = bcadd($retarr['ytd_amount'], $entry_arr['ytd_amount']);
                            $retarr['ytd_units'] = bcadd($retarr['ytd_units'], $entry_arr['ytd_units']);
                        }
                    } //else { //Debug::text('Type ID: '. $type_id .' does not match: '. $entry_arr['pay_stub_entry_type_id'], __FILE__, __LINE__, __METHOD__, 10);
                }
            } elseif ($ps_account_ids != '' and is_array($ps_account_ids)) {
                foreach ($ps_account_ids as $ps_account_id) {
                    if (isset($entry_arr['pay_stub_entry_account_id']) and $ps_account_id == $entry_arr['pay_stub_entry_account_id']) {
                        if (isset($entry_arr['ytd_adjustment']) and $entry_arr['ytd_adjustment'] === true and $entry_arr['pay_stub_entry_type_id'] != 50) {
                            $retarr['ytd_amount'] = bcadd($retarr['ytd_amount'], $entry_arr['amount']);
                            $retarr['ytd_units'] = bcadd($retarr['ytd_units'], $entry_arr['units']);
                        } else {
                            $retarr['amount'] = bcadd($retarr['amount'], $entry_arr['amount']);
                            $retarr['units'] = bcadd($retarr['units'], $entry_arr['units']);
                            $retarr['ytd_amount'] = bcadd($retarr['ytd_amount'], $entry_arr['ytd_amount']);
                            $retarr['ytd_units'] = bcadd($retarr['ytd_units'], $entry_arr['ytd_units']);
                        }
                    }
                }
            }
        }

        //Debug::Arr($retarr, 'SumByEntries RetArr: ', __FILE__, __LINE__, __METHOD__, 10);
        return $retarr;
    }

    public function checkEarnings()
    {
        $earnings = $this->getEarningSum();
        if (isset($earnings['amount']) and $earnings['amount'] != $this->getGrossPay()) {
            return false;
        }

        return true;
    }

    public function getGrossPay()
    {
        if ((int)$this->getPayStubEntryAccountLinkObject()->getTotalGross() == 0) {
            return false;
        }

        $retarr = $this->getSumByEntriesArrayAndTypeIDAndPayStubAccountID('current', null, $this->getPayStubEntryAccountLinkObject()->getTotalGross());
        Debug::Text('Gross Pay: ' . $retarr['amount'], __FILE__, __LINE__, __METHOD__, 10);

        if ($retarr['amount'] == '') {
            $retarr['amount'] = 0;
        }

        return $retarr['amount'];
    }

    public function getPayStubEntryAccountLinkObject()
    {
        if (is_object($this->pay_stub_entry_account_link_obj)) {
            return $this->pay_stub_entry_account_link_obj;
        } else {
            if (is_object($this->getUserObject())) {
                $pseallf = TTnew('PayStubEntryAccountLinkListFactory');
                $pseallf->getByCompanyID($this->getUserObject()->getCompany());
                if ($pseallf->getRecordCount() > 0) {
                    $this->pay_stub_entry_account_link_obj = $pseallf->getCurrent();
                    return $this->pay_stub_entry_account_link_obj;
                }
            }

            return false;
        }
    }

    public function checkDeductions()
    {
        $deductions = $this->getDeductionSum();
        if ($deductions['amount'] != $this->getDeductions()) {
            return false;
        }

        return true;
    }

    public function getDeductionSum()
    {
        $retarr = $this->getSumByEntriesArrayAndTypeIDAndPayStubAccountID('current', 20);
        Debug::Text('Deduction Sum: ' . $retarr['amount'], __FILE__, __LINE__, __METHOD__, 10);

        return $retarr;
    }

    public function getDeductions()
    {
        if ((int)$this->getPayStubEntryAccountLinkObject()->getTotalEmployeeDeduction() == 0) {
            return false;
        }

        $retarr = $this->getSumByEntriesArrayAndTypeIDAndPayStubAccountID('current', null, $this->getPayStubEntryAccountLinkObject()->getTotalEmployeeDeduction());
        Debug::Text('Deductions: ' . $retarr['amount'], __FILE__, __LINE__, __METHOD__, 10);

        if ($retarr['amount'] == '') {
            $retarr['amount'] = 0;
        }

        return $retarr['amount'];
    }

    public function checkNetPay()
    {
        $net_pay = $this->getNetPay();
        $tmp_net_pay = bcsub($this->getGrossPay(), $this->getDeductions());
        Debug::Text('aCheck Net Pay: Net Pay: ' . $net_pay . ' Tmp Net Pay: ' . $tmp_net_pay, __FILE__, __LINE__, __METHOD__, 10);

        if ($net_pay == $tmp_net_pay) {
            return true;
        }

        Debug::Text('Check Net Pay: Returning false', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function getNetPay()
    {
        if ((int)$this->getPayStubEntryAccountLinkObject()->getTotalNetPay() == 0) {
            return false;
        }

        $retarr = $this->getSumByEntriesArrayAndTypeIDAndPayStubAccountID('current', null, $this->getPayStubEntryAccountLinkObject()->getTotalNetPay());
        Debug::Text('Net Pay: ' . $retarr['amount'], __FILE__, __LINE__, __METHOD__, 10);

        if ($retarr['amount'] == '') {
            $retarr['amount'] = 0;
        }

        return $retarr['amount'];
    }

    public function postSave()
    {
        $this->removeCache($this->getId());

        if ($this->getEnableProcessEntries() == true) {
            if ($this->savePayStubEntries() == false) {
                $this->FailTransaction(); //Fail transaction as one of the PS entries was not saved.
            }
        }

        //This needs to be run even if entries aren't being processed,
        //for things like marking the pay stub paid or not.
        $this->handlePayStubAmendmentStatuses();
        $this->handleUserExpenseStatuses();

        if ($this->getDeleted() == true) {
            Debug::Text('Deleting Pay Stub, re-calculating YTD ', __FILE__, __LINE__, __METHOD__, 10);
            $this->setEnableCalcYTD(true);
        }

        if ($this->getEnableCalcCurrentYTD() == true) {
            $this->reCalculateCurrentYTD(); //Recalculate the current pay stub as well, in case they changed the transaction date into the next year without modifying entries.
        }

        if ($this->getEnableCalcYTD() == true) {
            $this->reCalculateYTD();
        }

        //Make sure we only email pay stubs that are marked PAID.
        //Do we want to avoid email pay stubs if they are making adjustments after the transaction date? Or maybe just in closed pay periods?
        if ($this->getStatus() == 40 and $this->getEnableEmail(true)) { //Paid
            $this->emailPayStub();
        } else {
            Debug::Text('Pay Stub is not marked paid or email is disabled, not emailing...', __FILE__, __LINE__, __METHOD__, 10);
        }

        return true;
    }

    public function savePayStubEntries()
    {
        if (!is_array($this->tmp_data['current_pay_stub'])) {
            return false;
        }

        //Cant add entries to a new paystub, since the pay_stub_id isn't set yet.
        if ($this->isNew() == true) {
            return false;
        }

        $this->calcPayStubEntriesYTD();

        //Debug::Arr($this->tmp_data['current_pay_stub'], 'Current Pay Stub Entries: ', __FILE__, __LINE__, __METHOD__, 10);

        foreach ($this->tmp_data['current_pay_stub'] as $pse_arr) {
            if (isset($pse_arr['pay_stub_entry_account_id']) and isset($pse_arr['amount'])) {
                Debug::Text('Current Pay Stub ID: ' . $this->getId() . ' Adding Pay Stub Entry for: ' . $pse_arr['pay_stub_entry_account_id'] . ' Amount: ' . $pse_arr['amount'] . ' YTD Amount: ' . $pse_arr['ytd_amount'] . ' YTD Units: ' . $pse_arr['ytd_units'], __FILE__, __LINE__, __METHOD__, 10);
                $psef = TTnew('PayStubEntryFactory');
                $psef->setPayStub($this->getId());
                $psef->setPayStubEntryNameId($pse_arr['pay_stub_entry_account_id']);
                $psef->setRate($pse_arr['rate']);
                $psef->setUnits($pse_arr['units']);
                $psef->setAmount($pse_arr['amount']);
                $psef->setYTDAmount($pse_arr['ytd_amount']);
                $psef->setYTDUnits($pse_arr['ytd_units']);

                $psef->setDescription($pse_arr['description']);
                if (is_numeric($pse_arr['pay_stub_amendment_id']) and $pse_arr['pay_stub_amendment_id'] > 0) {
                    $psef->setPayStubAmendment($pse_arr['pay_stub_amendment_id']);
                }
                if (isset($pse_arr['user_expense_id']) and is_numeric($pse_arr['user_expense_id']) and $pse_arr['user_expense_id'] > 0) {
                    $psef->setUserExpense($pse_arr['user_expense_id']);
                }

                $psef->setEnableCalculateYTD(false);

                if ($psef->isValid() == false or $psef->Save() == false) {
                    Debug::Text('Adding Pay Stub Entry failed!', __FILE__, __LINE__, __METHOD__, 10);

                    $this->Validator->isTrue('entry',
                        false,
                        //TTi18n::gettext('Invalid Pay Stub entry')
                        $psef->Validator->getTextErrors(false) //Get specific error messages from PSEF, rather than use a generic message, as user does see these.
                    );
                    return false;
                }
            }
        }

        return true;
    }

    public function calcPayStubEntriesYTD()
    {
        if (!is_array($this->tmp_data['current_pay_stub'])) {
            return false;
        }

        Debug::Text('Calculating Pay Stub Entry YTD values!', __FILE__, __LINE__, __METHOD__, 10);

        $this->markPayStubEntriesForYTDCalculation($this->tmp_data['previous_pay_stub']['entries']);
        $this->markPayStubEntriesForYTDCalculation($this->tmp_data['current_pay_stub'], false); //Dont clear out YTD values.

        //Debug::Arr($this->tmp_data['current_pay_stub'], 'Before YTD calculation', __FILE__, __LINE__, __METHOD__, 10);

        //addUnUsedYTDEntries() should be called before this

        //Go through each pay stub entry, and if there is no entry of the same
        //PSE account id, calc YTD. If there is a duplicate PSE account id,
        //only calculate the YTD on the LAST one.
        foreach ($this->tmp_data['current_pay_stub'] as $key => $entry_arr) {
            //If YTD is already set, don't recalculate it, because it could be a PS amendment YTD adjustment.
            //Keep in mind this makes it so if a YTD adjustment is set it will show up in the YTD column, and if there
            //is a second PSE account of the same, its YTD will show up too.
            //So this is the ONLY time YTD values should show up for the duplicate PSE accounts on the same PS.
            if ($entry_arr['calc_ytd'] == 0) {
                //Debug::Text('Calculating YTD on PSE account: '. $entry_arr['pay_stub_entry_account_id'], __FILE__, __LINE__, __METHOD__, 10);
                $current_pay_stub_sum = $this->getSumByEntriesArrayAndTypeIDAndPayStubAccountID('current', null, $entry_arr['pay_stub_entry_account_id']);
                $previous_pay_stub_sum = $this->getSumByEntriesArrayAndTypeIDAndPayStubAccountID('previous', null, $entry_arr['pay_stub_entry_account_id']);

                Debug::Text('Key: ' . $key . ' Previous YTD Amount: ' . $previous_pay_stub_sum['ytd_amount'] . ' Current Amount: ' . $current_pay_stub_sum['amount'] . ' Current YTD Amount: ' . $current_pay_stub_sum['ytd_amount'], __FILE__, __LINE__, __METHOD__, 10);
                $this->tmp_data['current_pay_stub'][$key]['ytd_amount'] = bcadd($previous_pay_stub_sum['ytd_amount'], bcadd($current_pay_stub_sum['amount'], $current_pay_stub_sum['ytd_amount']), (is_object($this->getCurrencyObject())) ? $this->getCurrencyObject()->getRoundDecimalPlaces() : 2);
                $this->tmp_data['current_pay_stub'][$key]['ytd_units'] = bcadd($previous_pay_stub_sum['ytd_units'], bcadd($current_pay_stub_sum['units'], $current_pay_stub_sum['ytd_units']), 4);
            } elseif ($this->tmp_data['current_pay_stub'][$key]['ytd_amount'] == '') {
                //Debug::Text('Setting YTD on PSE account: '. $entry_arr['pay_stub_entry_account_id'], __FILE__, __LINE__, __METHOD__, 10);
                $this->tmp_data['current_pay_stub'][$key]['ytd_amount'] = 0;
                $this->tmp_data['current_pay_stub'][$key]['ytd_units'] = 0;
            }
        }

        //Debug::Arr($this->tmp_data['current_pay_stub'], 'After YTD calculation', __FILE__, __LINE__, __METHOD__, 10);

        return true;
    }

    public function markPayStubEntriesForYTDCalculation(&$pay_stub_arr, $clear_out_ytd = true)
    {
        if (!is_array($pay_stub_arr)) {
            return false;
        }

        //Debug::Text('Marking which entries are to have YTD calculated on!', __FILE__, __LINE__, __METHOD__, 10);

        $trace_pay_stub_entry_account_id = array();

        //Loop over the array in reverse
        $pay_stub_arr = array_reverse($pay_stub_arr, true);
        foreach ($pay_stub_arr as $current_key => $val) {
            if (!isset($trace_pay_stub_entry_account_id[$pay_stub_arr[$current_key]['pay_stub_entry_account_id']])) {
                $trace_pay_stub_entry_account_id[$pay_stub_arr[$current_key]['pay_stub_entry_account_id']] = 0;
            } else {
                $trace_pay_stub_entry_account_id[$pay_stub_arr[$current_key]['pay_stub_entry_account_id']]++;
            }

            $pay_stub_arr[$current_key]['calc_ytd'] = $trace_pay_stub_entry_account_id[$pay_stub_arr[$current_key]['pay_stub_entry_account_id']];
            //Order here matters in cases for pay stubs with multiple accrual entries.
            //Because if the YTD amount is:
            // -800.00
            //	  0.00
            //	  0.00
            //We may end up clearing out the only YTD value that is of use.

            //CLEAR_OUT_YTD is used for backwards compat, so old pay stubs that calculated YTD
            //Only duplicate PS entries get zero'd out.
            if ($clear_out_ytd == true and $pay_stub_arr[$current_key]['calc_ytd'] > 0) {
                //Clear out YTD entries so the sum() function can calculate them properly.
                //This is for backwards compat.
                $pay_stub_arr[$current_key]['ytd_amount'] = 0;
                $pay_stub_arr[$current_key]['ytd_units'] = 0;
            }
        }
        $pay_stub_arr = array_reverse($pay_stub_arr, true);

        //Debug::Arr($pay_stub_arr, 'Copy Marked Entries ', __FILE__, __LINE__, __METHOD__, 10);

        return true;
    }

    /*


        Functions used in adding PayStub entries.


    */

    public function getCurrencyObject()
    {
        return $this->getGenericObject('CurrencyListFactory', $this->getCurrency(), 'currency_obj');
    }

    public function handlePayStubAmendmentStatuses()
    {
        //Mark all PS amendments as 'PAID' if this status is paid.
        //Mark as NEW if the PS is deleted?
        if ($this->getStatus() == 40) {
            $ps_amendment_status_id = 55; //PAID
        } elseif ($this->getDeleted() == false) {
            $ps_amendment_status_id = 52; //INUSE
        } else { //Deleted pay stub, re-activate PSA so it can be used again.
            $ps_amendment_status_id = 50; //ACTIVE
        }

        //Loop through each entry in current pay stub, if they have
        //a PS amendment ID assigned to them, change the status.
        if (is_array($this->tmp_data['current_pay_stub'])) {
            foreach ($this->tmp_data['current_pay_stub'] as $entry_arr) {
                if (isset($entry_arr['pay_stub_amendment_id']) and $entry_arr['pay_stub_amendment_id'] != '') {
                    Debug::Text('aFound PS Amendments to change status on...', __FILE__, __LINE__, __METHOD__, 10);

                    $ps_amendment_ids[] = $entry_arr['pay_stub_amendment_id'];
                }
            }

            unset($entry_arr);
        } elseif ($this->getStatus() != 10) {
            //Instead of loading the current pay stub entries, just run a query instead.
            $pself = TTnew('PayStubEntryListFactory');
            $pself->getByPayStubId($this->getId());
            foreach ($pself as $pay_stub_entry_obj) {
                if ($pay_stub_entry_obj->getPayStubAmendment() != false) {
                    Debug::Text('bFound PS Amendments to change status on...', __FILE__, __LINE__, __METHOD__, 10);
                    $ps_amendment_ids[] = $pay_stub_entry_obj->getPayStubAmendment();
                }
            }
        }

        if (isset($ps_amendment_ids) and is_array($ps_amendment_ids)) {
            Debug::Text('cFound PS Amendments to change status on...', __FILE__, __LINE__, __METHOD__, 10);

            foreach ($ps_amendment_ids as $ps_amendment_id) {
                //Set PS amendment status to match Pay stub.
                $psalf = TTnew('PayStubAmendmentListFactory');
                $psalf->getById($ps_amendment_id);
                if ($psalf->getRecordCount() == 1) {
                    $ps_amendment_obj = $psalf->getCurrent();
                    if ($ps_amendment_obj->getStatus() != $ps_amendment_status_id) {
                        Debug::Text('Changing Status of PS Amendment: ' . $ps_amendment_id, __FILE__, __LINE__, __METHOD__, 10);
                        $ps_amendment_obj->setEnablePayStubStatusChange(true); //Tell PSA that its the pay stub changing the status, so we can ignore some validation checks.
                        $ps_amendment_obj->setStatus($ps_amendment_status_id);
                        if ($ps_amendment_obj->isValid()) {
                            $ps_amendment_obj->Save();
                        } else {
                            Debug::Text('Changing Status of PS Amendment FAILED!: ' . $ps_amendment_id, __FILE__, __LINE__, __METHOD__, 10);
                        }
                    } else {
                        Debug::Text('Not Changing Status of PS Amendment, as its already the same: ' . $ps_amendment_id, __FILE__, __LINE__, __METHOD__, 10);
                    }
                    unset($ps_amendment_obj);
                }
                unset($psalf);
            }
            unset($ps_amendment_ids);
        }

        return true;
    }

    public function handleUserExpenseStatuses()
    {
        return true;
    }

    public function setEnableCalcYTD($bool)
    {
        $this->calc_ytd = (bool)$bool;

        return true;
    }

    public function getEnableCalcCurrentYTD()
    {
        if (isset($this->calc_current_ytd)) {
            return $this->calc_current_ytd;
        }

        return false;
    }

    public function reCalculateCurrentYTD()
    {
        Debug::Text('ReCalculating Current Pay Stub YTD...', __FILE__, __LINE__, __METHOD__, 10);

        //Recalculate the current pay stub as well, in case they changed the transaction date into the next year without modifying entries.
        $this->reCalculatePayStubYTD($this->getId(), false);

        return true;
    }

    public function reCalculatePayStubYTD($pay_stub_id, $enable_email = false)
    {
        //Make sure the entire pay stub object is loaded before calling this.
        if ($pay_stub_id != '') {
            Debug::text('Attempting to recalculate pay stub YTD for pay stub id: ' . $pay_stub_id, __FILE__, __LINE__, __METHOD__, 10);
            $pslf = TTnew('PayStubListFactory');
            $pslf->getById($pay_stub_id);

            if ($pslf->getRecordCount() == 1) {
                $pay_stub = $pslf->getCurrent();

                $pay_stub->loadPreviousPayStub();
                if ($pay_stub->loadCurrentPayStubEntries() == true) {
                    $pay_stub->setEnableProcessEntries(true);
                    $pay_stub->processEntries();

                    $pay_stub->setEnableEmail($enable_email);
                    if ($pay_stub->isValid() == true) {
                        Debug::text('Pay Stub is valid, final save.', __FILE__, __LINE__, __METHOD__, 10);
                        $pay_stub->Save();

                        return true;
                    }
                } else {
                    Debug::text('Failed loading current pay stub entries.', __FILE__, __LINE__, __METHOD__, 10);
                }
            }
        }

        return false;
    }

    public function getEnableCalcYTD()
    {
        if (isset($this->calc_ytd)) {
            return $this->calc_ytd;
        }

        return false;
    }

    public function reCalculateYTD()
    {
        Debug::Text('ReCalculating YTD on all newer pay stubs...', __FILE__, __LINE__, __METHOD__, 10);
        //Get all pay stubs NEWER then this one.
        $pslf = TTnew('PayStubListFactory');

        //Because this recalculates YTD amounts and accrual balances which span years, we need to recalculate ALL (even 10yrs into the future) newer pay stubs.
        //Increase transaction date by one day, otherwise it can include the current pay stub and recalculate it, causing it to the incorrect with YTD adjustment PS amendments.
        // Ensure that the sort order is always oldest pay stub to newest, so YTD amounts are properly progated from one to the next.
        //$pslf->getByUserIdAndStartDateAndEndDate( $this->getUser(), ($this->getTransactionDate() + 86400), (time() + (9999 * 86400)) );
        $pslf->getNextPayStubByUserIdAndTransactionDateAndRun($this->getUser(), $this->getTransactionDate(), $this->getRun());
        $total_pay_stubs = $pslf->getRecordCount();
        if ($total_pay_stubs > 0) {
            $pslf->StartTransaction();

            foreach ($pslf as $ps_obj) {
                $this->reCalculatePayStubYTD($ps_obj->getId(), false); //Make sure pay stubs are not emailed out when just recalculating YTD amounts.
            }

            $pslf->CommitTransaction();
        } else {
            Debug::Text('No Newer Pay Stubs found!', __FILE__, __LINE__, __METHOD__, 10);
        }

        return true;
    }

    public function getEnableEmail()
    {
        if (isset($this->email)) {
            return $this->email;
        }

        return true;
    }

    public function emailPayStub()
    {
        Debug::Text('emailPayStub: ', __FILE__, __LINE__, __METHOD__, 10);

        $email_to_arr = $this->getEmailPayStubAddresses();
        if ($email_to_arr == false) {
            return false;
        }

        $from = $reply_to = '"' . APPLICATION_NAME . ' - ' . TTi18n::gettext('Pay Stub') . '" <' . Misc::getEmailLocalPart() . '@' . Misc::getEmailDomain() . '>';
        Debug::Text('From: ' . $from, __FILE__, __LINE__, __METHOD__, 10);

        $to = array_shift($email_to_arr);
        Debug::Text('To: ' . $to, __FILE__, __LINE__, __METHOD__, 10);
        if (is_array($email_to_arr) and count($email_to_arr) > 0) {
            $bcc = implode(',', $email_to_arr);
        } else {
            $bcc = null;
        }
        Debug::Text('Bcc: ' . $bcc, __FILE__, __LINE__, __METHOD__, 10);

        $u_obj = $this->getUserObject();

        //Define subject/body variables here.
        $search_arr = array(
            '#employee_first_name#',
            '#employee_last_name#',
            '#employee_default_branch#',
            '#employee_default_department#',
            '#employee_group#',
            '#employee_title#',
            '#company_name#',
            '#link#',
            '#pay_stub_start_date#', //8
            '#pay_stub_end_date#',
            '#pay_stub_transaction_date#',
            '#display_id#',
        );

        $replace_arr = array(
            $u_obj->getFirstName(),
            $u_obj->getLastName(),
            (is_object($u_obj->getDefaultBranchObject())) ? $u_obj->getDefaultBranchObject()->getName() : null,
            (is_object($u_obj->getDefaultDepartmentObject())) ? $u_obj->getDefaultDepartmentObject()->getName() : null,
            (is_object($u_obj->getGroupObject())) ? $u_obj->getGroupObject()->getName() : null,
            (is_object($u_obj->getTitleObject())) ? $u_obj->getTitleObject()->getName() : null,
            (is_object($u_obj->getCompanyObject())) ? $u_obj->getCompanyObject()->getName() : null,
            null,
            TTDate::getDate('DATE', $this->getStartDate()), //8
            TTDate::getDate('DATE', $this->getEndDate()),
            TTDate::getDate('DATE', $this->getTransactionDate()),
            $this->getDisplayID(),
        );

        $email_subject = TTi18n::gettext('Pay Stub waiting in') . ' ' . APPLICATION_NAME;

        $email_body = TTi18n::gettext('*DO NOT REPLY TO THIS EMAIL - PLEASE USE THE LINK BELOW INSTEAD*') . "\n\n";

        $email_body .= TTi18n::gettext('You have a new pay stub waiting for you in') . ' ' . APPLICATION_NAME . "\n";

        $email_body .= "\n";

        $email_body .= ($replace_arr[8] != '') ? TTi18n::gettext('Pay Stub Start Date') . ': #pay_stub_start_date# ' : null;
        $email_body .= ($replace_arr[9] != '') ? TTi18n::gettext('End Date') . ': #pay_stub_end_date# ' : null;
        $email_body .= ($replace_arr[10] != '') ? TTi18n::gettext('Transaction Date') . ': #pay_stub_transaction_date#' . "\n" : null;
        $email_body .= ($replace_arr[11] != '') ? TTi18n::gettext('Identification #') . ': #display_id#' : null;

        $email_body .= "\n\n";

        $email_body .= ($replace_arr[2] != '') ? TTi18n::gettext('Default Branch') . ': #employee_default_branch#' . "\n" : null;
        $email_body .= ($replace_arr[3] != '') ? TTi18n::gettext('Default Department') . ': #employee_default_department#' . "\n" : null;
        $email_body .= ($replace_arr[4] != '') ? TTi18n::gettext('Group') . ': #employee_group#' . "\n" : null;
        $email_body .= ($replace_arr[5] != '') ? TTi18n::gettext('Title') . ': #employee_title#' . "\n" : null;

        $email_body .= "\n";

        $email_body .= TTi18n::gettext('Link') . ': <a href="' . Misc::getURLProtocol() . '://' . Misc::getHostName() . Environment::getDefaultInterfaceBaseURL() . '">' . APPLICATION_NAME . ' ' . TTi18n::gettext('Login') . '</a>';

        $email_body .= ($replace_arr[6] != '') ? "\n\n\n" . TTi18n::gettext('Company') . ': #company_name#' . "\n" : null; //Always put at the end

        $subject = str_replace($search_arr, $replace_arr, $email_subject);
        Debug::Text('Subject: ' . $subject, __FILE__, __LINE__, __METHOD__, 10);

        $headers = array(
            'From' => $from,
            'Subject' => $subject,
            'Bcc' => $bcc,
            //Reply-To/Return-Path are handled in TTMail.
        );

        $body = '<html><body><pre>' . str_replace($search_arr, $replace_arr, $email_body) . '</pre></body></html>';
        Debug::Text('Body: ' . $body, __FILE__, __LINE__, __METHOD__, 10);

        $mail = new TTMail();
        $mail->setTo($to);
        $mail->setHeaders($headers);

        @$mail->getMIMEObject()->setHTMLBody($body);

        $mail->setBody($mail->getMIMEObject()->get($mail->default_mime_config));
        $retval = $mail->Send();

        if ($retval == true) {
            TTLog::addEntry($this->getId(), 500, TTi18n::getText('Email Pay Stub to') . ': ' . $to . ' Bcc: ' . $headers['Bcc'], null, $this->getTable());
            return true;
        }

        return true; //Always return true
    }

    public function getEmailPayStubAddresses()
    {
        $uplf = TTnew('UserPreferenceListFactory');
        //$uplf->getByUserId( $this->getUser() );
        $uplf->getByUserIdAndStatus($this->getUser(), 10); //Only email ACTIVE employees/supervisors.
        if ($uplf->getRecordCount() > 0) {
            foreach ($uplf as $up_obj) {
                if ($up_obj->getEnableEmailNotificationPayStub() == true and is_object($up_obj->getUserObject()) and $up_obj->getUserObject()->getStatus() == 10) {
                    if ($up_obj->getUserObject()->getWorkEmail() != '' and $up_obj->getUserObject()->getWorkEmailIsValid() == true) {
                        $retarr[] = Misc::formatEmailAddress($up_obj->getUserObject()->getWorkEmail(), $up_obj->getUserObject());
                    }

                    if ($up_obj->getEnableEmailNotificationHome() and is_object($up_obj->getUserObject()) and $up_obj->getUserObject()->getHomeEmail() != '' and $up_obj->getUserObject()->getHomeEmailIsValid() == true) {
                        $retarr[] = Misc::formatEmailAddress($up_obj->getUserObject()->getHomeEmail(), $up_obj->getUserObject());
                    }
                }
            }

            if (isset($retarr)) {
                Debug::Arr($retarr, 'Recipient Email Addresses: ', __FILE__, __LINE__, __METHOD__, 10);
                return array_unique($retarr);
            }
        } else {
            Debug::Text('No user preferences available, or user is not active...', __FILE__, __LINE__, __METHOD__, 10);
        }

        return false;
    }

    public function getDisplayID()
    {
        if ($this->getId() > 0) {
            return str_pad($this->getId(), 15, 0, STR_PAD_LEFT);
        }

        return false;
    }

    public function isAccrualBalanceOutstanding()
    {
        $psea_arr = $this->getPayStubEntryAccountsArray();
        if (is_array($psea_arr)) {
            foreach ($psea_arr as $psea_id => $psea_data) {
                if ($psea_data['type_id'] == 50) { //Accruals
                    $psea_ids[] = $psea_id;
                }
            }

            if (isset($psea_ids)) {
                $retval = $this->getSumByEntriesArrayAndTypeIDAndPayStubAccountID('current', null, $psea_ids);
                Debug::Arr($retval, 'Sum Entries of Accruals: ', __FILE__, __LINE__, __METHOD__, 10);
                if (isset($retval['ytd_amount']) and $retval['ytd_amount'] != 0) {
                    Debug::Text('Accrual balances do exist...', __FILE__, __LINE__, __METHOD__, 10);
                    return true;
                }
            }
        }
        return false;
    }

    public function getPayStubEntryAccountsArray()
    {
        if (is_array($this->pay_stub_entry_accounts_obj)) {
            //Debug::text('Returning Cached data...', __FILE__, __LINE__, __METHOD__, 10);
            return $this->pay_stub_entry_accounts_obj;
        } else {
            $psealf = TTnew('PayStubEntryAccountListFactory');
            $psealf->getByCompanyId($this->getUserObject()->getCompany());
            if ($psealf->getRecordCount() > 0) {
                foreach ($psealf as $psea_obj) {
                    $this->pay_stub_entry_accounts_obj[$psea_obj->getId()] = array(
                        'type_id' => $psea_obj->getType(),
                        'accrual_pay_stub_entry_account_id' => $psea_obj->getAccrual(),
                        'accrual_type_id' => $psea_obj->getAccrualType(),
                    );
                }

                //Debug::Arr($this->pay_stub_entry_accounts_obj, ' Pay Stub Entry Accounts ('.count($this->pay_stub_entry_accounts_obj).'): ', __FILE__, __LINE__, __METHOD__, 10);
                return $this->pay_stub_entry_accounts_obj;
            }

            Debug::text('Returning FALSE...', __FILE__, __LINE__, __METHOD__, 10);
            return false;
        }
    }

    public function loadCurrentPayStubEntries()
    {
        Debug::Text('aLoading current pay stub entries, Pay Stub ID: ' . $this->getId(), __FILE__, __LINE__, __METHOD__, 10);
        if ($this->getId() != '') {
            //Get pay stub entries
            $pself = TTnew('PayStubEntryListFactory');
            $pself->getByPayStubId($this->getID());
            Debug::Text('bLoading current pay stub entries, Pay Stub ID: ' . $this->getId() . ' Record Count: ' . $pself->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);

            if ($pself->getRecordCount() > 0) {
                $this->tmp_data['current_pay_stub'] = null;

                foreach ($pself as $pse_obj) {
                    //Get PSE account type, group by that.
                    $psea_arr = $this->getPayStubEntryAccountArray($pse_obj->getPayStubEntryNameId());
                    if (is_array($psea_arr)) {
                        $type_id = $psea_arr['type_id'];
                    } else {
                        $type_id = null;
                    }

                    //Skip total entries
                    if ($type_id != 40) {
                        $pse_arr[] = array(
                            'id' => $pse_obj->getId(),
                            'pay_stub_entry_type_id' => $type_id,
                            'pay_stub_entry_account_id' => $pse_obj->getPayStubEntryNameId(),
                            'pay_stub_amendment_id' => $pse_obj->getPayStubAmendment(),
                            'rate' => $pse_obj->getRate(),
                            'units' => $pse_obj->getUnits(),
                            'amount' => $pse_obj->getAmount(),
                            //'ytd_units' => $pse_obj->getYTDUnits(),
                            //'ytd_amount' => $pse_obj->getYTDAmount(),
                            //Don't load YTD values, they need to be recalculated.
                            'ytd_units' => null,
                            'ytd_amount' => null,
                            'description' => $pse_obj->getDescription(),

                            //Make sure we carry over YTD adjustments when only recalculating Pay Stub YTD amounts going forward.
                            //This fixes a bug where someone is using YTD adjustments in the middle of the year, and they modify to pay stubs prior to that, causing all newer pay stubs to be recalculated.
                            'ytd_adjustment' => (bool)$pse_obj->getColumn('ytd_adjustment'),
                        );
                    }
                    unset($type_id, $psea_obj);
                }

                //Debug::Arr($pse_arr, 'RetArr: ', __FILE__, __LINE__, __METHOD__, 10);
                if (isset($pse_arr)) {
                    $retarr['entries'] = $pse_arr;

                    $this->tmp_data['current_pay_stub'] = $retarr['entries'];

                    Debug::Text('Loading current pay stub entries success!', __FILE__, __LINE__, __METHOD__, 10);
                    return true;
                }
            }
        }
        Debug::Text('Loading current pay stub entries failed!', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function getPayStubEntryAccountArray($id)
    {
        if ($id == '') {
            return false;
        }

        //Debug::text('ID: '. $id, __FILE__, __LINE__, __METHOD__, 10);
        $psea = $this->getPayStubEntryAccountsArray();

        if (isset($psea[$id])) {
            return $psea[$id];
        }

        Debug::text('Returning FALSE...', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function loadPreviousPayStub()
    {
        if ($this->getUser() == false or $this->getStartDate() == false or $this->getRun() == false) {
            return false;
        }

        Debug::text('Loading Pay Stub data prior to: ' . TTDate::getDate('DATE', $this->getStartDate()) . ' Run: ' . $this->getRun(), __FILE__, __LINE__, __METHOD__, 10);

        //Grab last pay stub so we can use it for YTD calculations on this pay stub.
        $pslf = TTnew('PayStubListFactory');
        $pslf->getLastPayStubByUserIdAndStartDateAndRun($this->getUser(), $this->getStartDate(), $this->getRun());
        if ($pslf->getRecordCount() > 0) {
            $ps_obj = $pslf->getCurrent();
            Debug::text('Loading Data from Pay Stub ID: ' . $ps_obj->getId() . ' Start Date: ' . TTDate::getDate('DATE', $ps_obj->getStartDate()) . ' Run: ' . $ps_obj->getRun(), __FILE__, __LINE__, __METHOD__, 10);

            $retarr = array(
                'id' => $ps_obj->getId(),
                'start_date' => $ps_obj->getStartDate(),
                'end_date' => $ps_obj->getEndDate(),
                'transaction_date' => $ps_obj->getTransactionDate(),
                'entries' => null,
            );

            //
            //If previous pay stub is in a different year, only carry forward the accrual accounts.
            //
            $new_year = false;
            if (TTDate::getYear($this->getTransactionDate()) != TTDate::getYear($ps_obj->getTransactionDate())) {
                Debug::text('Pay Stub Years dont match!...', __FILE__, __LINE__, __METHOD__, 10);
                $new_year = true;
            }

            //Get pay stub entries
            $pself = TTnew('PayStubEntryListFactory');
            $pself->getByPayStubId($ps_obj->getID());
            if ($pself->getRecordCount() > 0) {
                foreach ($pself as $pse_obj) {
                    //Get PSE account type, group by that.
                    $psea_arr = $this->getPayStubEntryAccountArray($pse_obj->getPayStubEntryNameId());
                    if (is_array($psea_arr)) {
                        $type_id = $psea_arr['type_id'];
                    } else {
                        $type_id = null;
                    }

                    //If we're just starting a new year, only carry over
                    //accrual balances, reset all YTD entries.
                    if ($new_year == false or $type_id == 50) {
                        $pse_arr[] = array(
                            'id' => $pse_obj->getId(),
                            'pay_stub_entry_type_id' => $type_id,
                            'pay_stub_entry_account_id' => $pse_obj->getPayStubEntryNameId(),
                            'pay_stub_amendment_id' => $pse_obj->getPayStubAmendment(),
                            'rate' => $pse_obj->getRate(),
                            'units' => $pse_obj->getUnits(),
                            'amount' => $pse_obj->getAmount(),
                            'ytd_units' => $pse_obj->getYTDUnits(),
                            'ytd_amount' => $pse_obj->getYTDAmount(),
                        );
                    }
                    unset($type_id, $psea_obj);
                }

                if (isset($pse_arr)) {
                    $retarr['entries'] = $pse_arr;

                    $this->tmp_data['previous_pay_stub'] = $retarr;

                    return true;
                }
            }
        }

        Debug::text('Returning FALSE...', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function processEntries()
    {
        Debug::Text('Processing PayStub (' . count($this->tmp_data['current_pay_stub']) . ') Entries...', __FILE__, __LINE__, __METHOD__, 10);
        ///Debug::Arr($this->tmp_data['current_pay_stub'], 'Current Entries...', __FILE__, __LINE__, __METHOD__, 10);

        $this->deleteEntries(false); //Delete only total entries
        $this->addUnUsedYTDEntries();
        $this->addEarningSum();
        $this->addDeductionSum();
        $this->addEmployerDeductionSum();
        $this->addNetPay();

        $this->setEnableCalcCurrentYTD(false); //No need to recalculate current YTD if we are processing entries.

        return true;
    }

    public function deleteEntries($all_entries = false)
    {
        //Delete any entries from the pay stub, so they can be re-created.
        $pself = TTnew('PayStubEntryListFactory');

        if ($all_entries == true) {
            $pself->getByPayStubIdAndType($this->getId(), 40);
        } else {
            $pself->getByPayStubId($this->getId());
        }

        foreach ($pself as $pay_stub_entry_obj) {
            Debug::Text('Deleting Pay Stub Entry: ' . $pay_stub_entry_obj->getId(), __FILE__, __LINE__, __METHOD__, 10);
            $del_ps_entry_ids[] = $pay_stub_entry_obj->getId();
        }
        if (isset($del_ps_entry_ids)) {
            $pself->bulkDelete($del_ps_entry_ids);
        }
        unset($pay_stub_entry_obj, $del_ps_entry_ids);

        return true;
    }

    public function addUnUsedYTDEntries()
    {
        Debug::Text('Adding Unused Entries ', __FILE__, __LINE__, __METHOD__, 10);
        //This has to happen ABOVE the total entries... So Gross pay and stuff
        //takes them in to account when doing YTD totals
        //
        //Find out which prior entries have been made and carry any YTD entries forward with 0 amounts
        if (isset($this->tmp_data['previous_pay_stub']) and is_array($this->tmp_data['previous_pay_stub']['entries'])) {
            //Debug::Arr($this->tmp_data['current_pay_stub'], 'Current Pay Stub Entries:', __FILE__, __LINE__, __METHOD__, 10);

            foreach ($this->tmp_data['previous_pay_stub']['entries'] as $key => $entry_arr) {
                //See if current pay stub entries have previous pay stub entries.
                //Skip total entries, as they will be greated after anyways.
                if ($entry_arr['pay_stub_entry_type_id'] != 40
                    and Misc::inArrayByKeyAndValue($this->tmp_data['current_pay_stub'], 'pay_stub_entry_account_id', $entry_arr['pay_stub_entry_account_id']) == false
                ) {
                    Debug::Text('Adding UnUsed Entry: ' . $entry_arr['pay_stub_entry_account_id'], __FILE__, __LINE__, __METHOD__, 10);
                    $this->addEntry($entry_arr['pay_stub_entry_account_id'], 0, 0);
                } else {
                    Debug::Text('NOT Adding already existing Entry: ' . $entry_arr['pay_stub_entry_account_id'], __FILE__, __LINE__, __METHOD__, 10);
                }
            }
        }

        return true;
    }

    public function addEntry($pay_stub_entry_account_id, $amount, $units = null, $rate = null, $description = null, $ps_amendment_id = null, $ytd_amount = null, $ytd_units = null, $ytd_adjustment = false, $user_expense_id = null)
    {
        Debug::text('Add Entry: PSE Account ID: ' . $pay_stub_entry_account_id . ' Amount: ' . $amount . ' YTD Amount: ' . $ytd_amount . ' Pay Stub Amendment Id: ' . $ps_amendment_id . ' User Expense: ' . $user_expense_id, __FILE__, __LINE__, __METHOD__, 10);
        if ($pay_stub_entry_account_id == '') {
            return false;
        }

        //Round amount to 2 decimal places.
        //So any totaling is proper after this point, because it gets rounded to two decimal places in PayStubEntryFactory too.
        //PHP has a bug that round() converts large values with 0's on the end into scientific notation. Use number_format() instead.
        $amount = (is_object($this->getCurrencyObject())) ? $this->getCurrencyObject()->round($amount) : Misc::MoneyFormat($amount, false);
        $ytd_amount = (is_object($this->getCurrencyObject())) ? $this->getCurrencyObject()->round($ytd_amount) : Misc::MoneyFormat($ytd_amount, false);
        if (is_numeric($amount)) {
            $psea_arr = $this->getPayStubEntryAccountArray($pay_stub_entry_account_id);
            if (is_array($psea_arr)) {
                $type_id = $psea_arr['type_id'];
            } else {
                $type_id = null;
            }

            $retarr = array(
                'pay_stub_entry_type_id' => $type_id,
                'pay_stub_entry_account_id' => $pay_stub_entry_account_id,
                'pay_stub_amendment_id' => $ps_amendment_id,
                'user_expense_id' => $user_expense_id,
                'rate' => $rate,
                'units' => $units,
                'amount' => $amount, //PHP v5.3.5 has a bug that it converts large values with 0's on the end into scientific notation.
                'ytd_units' => $ytd_units,
                'ytd_amount' => $ytd_amount,
                'description' => $description,
                'ytd_adjustment' => $ytd_adjustment,
            );

            $this->tmp_data['current_pay_stub'][] = $retarr;

            //Check if this pay stub account is linked to an accrual account.
            //Make sure the PSE account does not match the PSE Accrual account,
            //because we don't want to get in to an infinite loop.
            //Also don't touch the accrual account if the amount is 0.
            //This happens mostly when AddUnUsedEntries is called.
            if ($this->getEnableLinkedAccruals() == true
                and $amount != 0
                and $psea_arr['accrual_pay_stub_entry_account_id'] != ''
                and $psea_arr['accrual_pay_stub_entry_account_id'] != 0
                and $psea_arr['accrual_pay_stub_entry_account_id'] != $pay_stub_entry_account_id
                and $psea_arr['accrual_type_id'] != ''
                and $ytd_adjustment == false
            ) {
                Debug::text('Add Entry: PSE Account Links to Accrual Account!: ' . $pay_stub_entry_account_id . ' Accrual Account ID: ' . $psea_arr['accrual_pay_stub_entry_account_id'] . ' Amount: ' . $amount, __FILE__, __LINE__, __METHOD__, 10);

                if ($psea_arr['accrual_type_id'] == 10) {
                    if ($type_id == 10) {
                        $tmp_amount = ($amount * -1); //This is an earning... Reduce accrual
                    } else {
                        $tmp_amount = $amount;
                    }
                } else {
                    if ($type_id == 20) {
                        $tmp_amount = ($amount * -1); //This is an deduction... Reduce accrual
                    } else {
                        $tmp_amount = $amount;
                    }
                }
                Debug::text('Amount: ' . $tmp_amount, __FILE__, __LINE__, __METHOD__, 10);

                return $this->addEntry($psea_arr['accrual_pay_stub_entry_account_id'], $tmp_amount, null, null, null, null, null, null);
            }

            return true;
        }

        Debug::text('Returning FALSE', __FILE__, __LINE__, __METHOD__, 10);

        $this->Validator->isTrue('entry',
            false,
            TTi18n::gettext('Invalid Pay Stub entry'));

        return false;
    }

    public function getEnableLinkedAccruals()
    {
        if (isset($this->linked_accruals)) {
            return $this->linked_accruals;
        }

        return true;
    }

    public function addEarningSum()
    {
        $sum_arr = $this->getEarningSum();
        Debug::Text('Sum: ' . $sum_arr['amount'], __FILE__, __LINE__, __METHOD__, 10);
        if ($sum_arr['amount'] > 0) {
            $this->addEntry($this->getPayStubEntryAccountLinkObject()->getTotalGross(), $sum_arr['amount'], $sum_arr['units'], null, null, null, $sum_arr['ytd_amount']);
        }
        unset($sum_arr);

        return true;
    }

    //Returns TRUE unless Amount explicitly does not match Gross Pay
    //use checkNoEarnings to see if any earnings exist or not.

    public function addDeductionSum()
    {
        $sum_arr = $this->getDeductionSum();
        if (isset($sum_arr['amount'])) { //Allow negative amounts for adjustment purposes
            $this->addEntry($this->getPayStubEntryAccountLinkObject()->getTotalEmployeeDeduction(), $sum_arr['amount'], $sum_arr['units'], null, null, null, $sum_arr['ytd_amount']);
        }
        unset($sum_arr);

        return true;
    }

    public function addEmployerDeductionSum()
    {
        $sum_arr = $this->getEmployerDeductionSum();
        if (isset($sum_arr['amount'])) { //Allow negative amounts for adjustment purposes
            $this->addEntry($this->getPayStubEntryAccountLinkObject()->getTotalEmployerDeduction(), $sum_arr['amount'], $sum_arr['units'], null, null, null, $sum_arr['ytd_amount']);
        }
        unset($sum_arr);

        return true;
    }

    public function getEmployerDeductionSum()
    {
        $retarr = $this->getSumByEntriesArrayAndTypeIDAndPayStubAccountID('current', 30);
        Debug::Text('Employer Deduction Sum: ' . $retarr['amount'], __FILE__, __LINE__, __METHOD__, 10);

        return $retarr;
    }

    public function addNetPay()
    {
        $earning_sum_arr = $this->getEarningSum();
        $deduction_sum_arr = $this->getDeductionSum();

        if ($earning_sum_arr['amount'] > 0) {
            Debug::Text('Earning Sum is greater than 0.', __FILE__, __LINE__, __METHOD__, 10);

            $net_pay_amount = bcsub($earning_sum_arr['amount'], $deduction_sum_arr['amount']);
            $net_pay_ytd_amount = bcsub($earning_sum_arr['ytd_amount'], $deduction_sum_arr['ytd_amount']);

            $this->addEntry($this->getPayStubEntryAccountLinkObject()->getTotalNetPay(), $net_pay_amount, null, null, null, null, $net_pay_ytd_amount);
        }
        unset($net_pay_amount, $net_pay_ytd_amount, $earning_sum_arr, $deduction_sum_arr);

        Debug::Text('Earning Sum is 0 or less. ', __FILE__, __LINE__, __METHOD__, 10);

        return true;
    }

    public function setEnableCalcCurrentYTD($bool)
    {
        $this->calc_current_ytd = (bool)$bool;

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
                        case 'start_date':
                        case 'end_date':
                        case 'transaction_date':
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
                        case 'city':
                        case 'province':
                        case 'country':
                        case 'currency':
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
                        case 'start_date':
                        case 'end_date':
                        case 'transaction_date':
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
            }
            $this->getPermissionColumns($data, $this->getUser(), $this->getCreatedBy(), $permission_children_ids, $include_columns);
            $this->getCreatedAndUpdatedColumns($data, $include_columns);
        }

        return $data;
    }

    public function exportPayStub($pslf = null, $export_type = null, $company_obj = null)
    {
        if (is_object($company_obj)) {
            $current_company = $company_obj;
        } else {
            global $current_company;
        }

        if (!is_object($pslf) and $this->getId() != '') {
            $pslf = TTnew('PayStubListFactory');
            $pslf->getById($this->getId());
        }

        if (strpos(get_class($pslf), 'PayStubListFactory') !== 0) { //Allow for PayStubListFactoryPlugin to match as well.
            return false;
        }

        if ($export_type == '') {
            return false;
        }

        if ($pslf->getRecordCount() > 0) {
            Debug::Text('aExporting...', __FILE__, __LINE__, __METHOD__, 10);
            switch (strtolower($export_type)) {
                case 'eft_hsbc':
                case 'eft_1464':
                case 'eft_1464_cibc':
                case 'eft_1464_rbc':
                case 'eft_105':
                case 'eft_ach':
                case 'eft_beanstream':
                    //Get file creation number
                    $ugdlf = TTnew('UserGenericDataListFactory');
                    $ugdlf->getByCompanyIdAndScriptAndDefault($current_company->getId(), 'PayStubFactory', true);
                    if ($ugdlf->getRecordCount() > 0) {
                        $ugd_obj = $ugdlf->getCurrent();
                        $setup_data = $ugd_obj->getData();
                    } else {
                        $ugd_obj = TTnew('UserGenericDataFactory');
                    }

                    Debug::Text('bExporting...', __FILE__, __LINE__, __METHOD__, 10);
                    //get User Bank account info
                    $balf = TTnew('BankAccountListFactory');
                    $balf->getCompanyAccountByCompanyId($current_company->getID());
                    if ($balf->getRecordCount() > 0) {
                        $company_bank_obj = $balf->getCurrent();
                        //Debug::Arr($company_bank_obj, 'Company Bank Object', __FILE__, __LINE__, __METHOD__, 10);
                    }

                    if (isset($setup_data['file_creation_number'])) {
                        $setup_data['file_creation_number']++;
                    } else {
                        //Start at a high number, in attempt to eliminate conflicts.
                        $setup_data['file_creation_number'] = 500;
                    }
                    Debug::Text('bFile Creation Number: ' . $setup_data['file_creation_number'], __FILE__, __LINE__, __METHOD__, 10);

                    //Increment file creation number in DB
                    if ($ugd_obj->getId() == '') {
                        $ugd_obj->setID($ugd_obj->getId());
                    }
                    $ugd_obj->setCompany($current_company->getId());
                    $ugd_obj->setScript('PayStubFactory');
                    $ugd_obj->setName('PayStubFactory');
                    $ugd_obj->setData($setup_data);
                    $ugd_obj->setDefault(true);
                    if ($ugd_obj->isValid()) {
                        $ugd_obj->Save();
                    }

                    $eft = new EFT();
                    $eft->setFileFormat(str_replace('eft_', '', $export_type));

                    $eft->setBusinessNumber($current_company->getBusinessNumber()); //ACH
                    $eft->setOriginatorID($current_company->getOriginatorID());
                    $eft->setFileCreationNumber($setup_data['file_creation_number']);
                    $eft->setInitialEntryNumber((($current_company->getOtherID5() != '') ? $current_company->getOtherID5() : substr($current_company->getOriginatorID(), 0, 8))); //ACH
                    $eft->setDataCenter($current_company->getDataCenterID());
                    $eft->setDataCenterName($current_company->getOtherID4()); //ACH
                    $eft->setOriginatorShortName($current_company->getShortName());

                    if (strtolower($export_type) == 'eft_1464_cibc' and isset($company_bank_obj) and is_object($company_bank_obj)) {
                        $eft->setOtherData('cibc_settlement_institution', $company_bank_obj->getInstitution());
                        $eft->setOtherData('cibc_settlement_transit', $company_bank_obj->getTransit());
                        $eft->setOtherData('cibc_settlement_account', $company_bank_obj->getAccount());
                    }

                    if (strtolower($export_type) == 'eft_1464_rbc') {
                        $eft->setFilePrefixData('$$AA01CPA1464[PROD{NL$$' . "\r\n"); //Some RBC services require a "routing" line at the top of the file.
                    }

                    $total_credit_amount = 0;

                    $psealf = TTnew('PayStubEntryAccountListFactory');
                    foreach ($pslf as $key => $pay_stub_obj) {
                        Debug::Text('Looping over Pay Stub... ID: ' . $pay_stub_obj->getId(), __FILE__, __LINE__, __METHOD__, 10);

                        if ($pay_stub_obj->getStatus() == 100) {
                            Debug::Text('  Opening Balance pay stub, not exporting... ID: ' . $pay_stub_obj->getId(), __FILE__, __LINE__, __METHOD__, 10);
                            continue;
                        }

                        //Get pay stub entries.
                        $pself = TTnew('PayStubEntryListFactory');
                        $pself->getByPayStubId($pay_stub_obj->getId());

                        $prev_type = null;
                        $description_subscript_counter = 1;
                        foreach ($pself as $pay_stub_entry) {
                            $description_subscript = null;

                            //$pay_stub_entry_name_obj = $psenlf->getById( $pay_stub_entry->getPayStubEntryNameId() ) ->getCurrent();
                            $pay_stub_entry_name_obj = $psealf->getById($pay_stub_entry->getPayStubEntryNameId())->getCurrent();

                            if ($prev_type == 40 or $pay_stub_entry_name_obj->getType() != 40) {
                                $type = $pay_stub_entry_name_obj->getType();
                            }

                            //var_dump( $pay_stub_entry->getDescription() );
                            if ($pay_stub_entry->getDescription() !== null
                                and $pay_stub_entry->getDescription() !== false
                                and strlen($pay_stub_entry->getDescription()) > 0
                            ) {
                                $pay_stub_entry_descriptions[] = array('subscript' => $description_subscript_counter,
                                    'description' => $pay_stub_entry->getDescription());

                                $description_subscript = $description_subscript_counter;

                                $description_subscript_counter++;
                            }

                            if ($type != 40 or ($type == 40 and $pay_stub_entry->getAmount() != 0)) {
                                $pay_stub_entries[$type][] = array(
                                    'id' => $pay_stub_entry->getId(),
                                    'pay_stub_entry_name_id' => $pay_stub_entry->getPayStubEntryNameId(),
                                    'type' => $pay_stub_entry_name_obj->getType(),
                                    'name' => $pay_stub_entry_name_obj->getName(),
                                    'display_name' => $pay_stub_entry_name_obj->getName(),
                                    'rate' => $pay_stub_entry->getRate(),
                                    'units' => $pay_stub_entry->getUnits(),
                                    'ytd_units' => $pay_stub_entry->getYTDUnits(),
                                    'amount' => $pay_stub_entry->getAmount(),
                                    'ytd_amount' => $pay_stub_entry->getYTDAmount(),

                                    'description' => $pay_stub_entry->getDescription(),
                                    'description_subscript' => $description_subscript,

                                    'created_date' => $pay_stub_entry->getCreatedDate(),
                                    'created_by' => $pay_stub_entry->getCreatedBy(),
                                    'updated_date' => $pay_stub_entry->getUpdatedDate(),
                                    'updated_by' => $pay_stub_entry->getUpdatedBy(),
                                    'deleted_date' => $pay_stub_entry->getDeletedDate(),
                                    'deleted_by' => $pay_stub_entry->getDeletedBy()
                                );
                            }

                            $prev_type = $pay_stub_entry_name_obj->getType();
                        }

                        if (isset($pay_stub_entries)) {
                            $pay_stub = array(
                                'id' => $pay_stub_obj->getId(),
                                'display_id' => $pay_stub_obj->getDisplayID(),
                                'user_id' => $pay_stub_obj->getUser(),
                                'pay_period_id' => $pay_stub_obj->getPayPeriod(),
                                'start_date' => $pay_stub_obj->getStartDate(),
                                'end_date' => $pay_stub_obj->getEndDate(),
                                'transaction_date' => $pay_stub_obj->getTransactionDate(),
                                'status' => $pay_stub_obj->getStatus(),
                                'entries' => $pay_stub_entries,

                                'created_date' => $pay_stub_obj->getCreatedDate(),
                                'created_by' => $pay_stub_obj->getCreatedBy(),
                                'updated_date' => $pay_stub_obj->getUpdatedDate(),
                                'updated_by' => $pay_stub_obj->getUpdatedBy(),
                                'deleted_date' => $pay_stub_obj->getDeletedDate(),
                                'deleted_by' => $pay_stub_obj->getDeletedBy()
                            );
                            unset($pay_stub_entries);

                            //Get User information
                            $ulf = TTnew('UserListFactory');
                            $user_obj = $ulf->getById($pay_stub_obj->getUser())->getCurrent();

                            //Get company information
                            $clf = TTnew('CompanyListFactory');
                            $company_obj = $clf->getById($user_obj->getCompany())->getCurrent();

                            //get User Bank account info
                            $balf = TTnew('BankAccountListFactory');
                            $user_bank_obj = $balf->getUserAccountByCompanyIdAndUserId($user_obj->getCompany(), $user_obj->getId());
                            if ($user_bank_obj->getRecordCount() > 0) {
                                $user_bank_obj = $user_bank_obj->getCurrent();
                            } else {
                                Debug::Text('No bank account defined for User ID: ' . $user_obj->getId() . ' skipping...', __FILE__, __LINE__, __METHOD__, 10);
                                continue;
                            }

                            if (isset($pay_stub['entries'][40][0]['amount'])) {
                                $amount = $pay_stub['entries'][40][0]['amount'];
                            } else {
                                $amount = 0;
                            }

                            if ($amount > 0) {
                                $record = new EFT_Record();
                                $record->setType('C');
                                $record->setCPACode(200);
                                $record->setAmount($amount);

                                $record->setDueDate(TTDate::getBeginDayEpoch($pay_stub_obj->getTransactionDate()));
                                $record->setInstitution($user_bank_obj->getInstitution());
                                $record->setTransit($user_bank_obj->getTransit());
                                $record->setAccount($user_bank_obj->getAccount());
                                $record->setName($user_obj->getFullName());

                                $record->setOriginatorShortName($company_obj->getShortName());
                                $record->setOriginatorLongName(substr($company_obj->getName(), 0, 30));
                                $record->setOriginatorReferenceNumber('TT' . $pay_stub_obj->getId());

                                if (isset($company_bank_obj) and is_object($company_bank_obj)) {
                                    $record->setReturnInstitution($company_bank_obj->getInstitution());
                                    $record->setReturnTransit($company_bank_obj->getTransit());
                                    $record->setReturnAccount($company_bank_obj->getAccount());
                                }

                                $eft->setRecord($record);
                            }

                            $total_credit_amount += $amount;
                            unset($amount);

                            $this->getProgressBarObject()->set(null, $key);
                        }
                    }

                    $is_balanced = CompanySettingFactory::getCompanySettingValueByName($current_company->getId(), 'pay_stub.eft.balance_ach');
                    if ($total_credit_amount > 0
                        and (bool)$is_balanced == true
                        and isset($company_obj) and is_object($company_obj)
                        and isset($company_bank_obj) and is_object($company_bank_obj)
                        and isset($pay_stub_obj) and is_object($pay_stub_obj)
                    ) {
                        Debug::Text('  Balancing ACH... ', __FILE__, __LINE__, __METHOD__, 10);
                        $record = new EFT_Record();
                        $record->setType('D');
                        $record->setCPACode(200);
                        $record->setAmount($total_credit_amount);

                        $record->setDueDate(TTDate::getBeginDayEpoch($pay_stub_obj->getTransactionDate()));
                        $record->setInstitution($company_bank_obj->getInstitution());
                        $record->setTransit($company_bank_obj->getTransit());
                        $record->setAccount($company_bank_obj->getAccount());
                        $record->setName(substr($company_obj->getName(), 0, 30));

                        $record->setOriginatorShortName($company_obj->getShortName());
                        $record->setOriginatorLongName(substr($company_obj->getName(), 0, 30));
                        $record->setOriginatorReferenceNumber('OFFSET');

                        if (isset($company_bank_obj) and is_object($company_bank_obj)) {
                            $record->setReturnInstitution($company_bank_obj->getInstitution());
                            $record->setReturnTransit($company_bank_obj->getTransit());
                            $record->setReturnAccount($company_bank_obj->getAccount());
                        }

                        $eft->setRecord($record);
                    } else {
                        Debug::Text('  NOT Balancing ACH... ', __FILE__, __LINE__, __METHOD__, 10);
                    }
                    unset($is_balanced, $total_credit_amount);

                    $eft->compile();
                    $output = $eft->getCompiledData();

                    unset($eft);
                    break;
                case 'cheque_9085':
                case 'cheque_9209p':
                case 'cheque_dlt103':
                case 'cheque_dlt104':
                case 'cheque_cr_standard_form_1':
                case 'cheque_cr_standard_form_2':
                    $cheque_form_obj = $this->getChequeFormsObject(str_replace('cheque_', '', $export_type));
                    $psealf = TTnew('PayStubEntryAccountListFactory');
                    $numbers_words = new Numbers_Words();
                    $i = 0;
                    foreach ($pslf as $pay_stub_obj) {
                        if ($pay_stub_obj->getStatus() == 100) {
                            Debug::Text('  Opening Balance pay stub, not exporting... ID: ' . $pay_stub_obj->getId(), __FILE__, __LINE__, __METHOD__, 10);
                            continue;
                        }

                        //Get pay stub entries.
                        $pself = TTnew('PayStubEntryListFactory');
                        $pself->getByPayStubId($pay_stub_obj->getId());

                        $pay_stub_entries = null;
                        $prev_type = null;
                        $description_subscript_counter = 1;
                        foreach ($pself as $pay_stub_entry) {
                            $description_subscript = null;

                            //$pay_stub_entry_name_obj = $psenlf->getById( $pay_stub_entry->getPayStubEntryNameId() ) ->getCurrent();
                            $pay_stub_entry_name_obj = $psealf->getById($pay_stub_entry->getPayStubEntryNameId())->getCurrent();

                            //Use this to put the total for each type at the end of the array.
                            if ($prev_type == 40 or $pay_stub_entry_name_obj->getType() != 40) {
                                $type = $pay_stub_entry_name_obj->getType();
                            }
                            //Debug::text('Pay Stub Entry Name ID: '. $pay_stub_entry_name_obj->getId() .' Type ID: '. $pay_stub_entry_name_obj->getType() .' Type: '. $type, __FILE__, __LINE__, __METHOD__, 10);

                            //var_dump( $pay_stub_entry->getDescription() );
                            if ($pay_stub_entry->getDescription() !== null
                                and $pay_stub_entry->getDescription() !== false
                                and strlen($pay_stub_entry->getDescription()) > 0
                            ) {
                                $pay_stub_entry_descriptions[] = array('subscript' => $description_subscript_counter,
                                    'description' => $pay_stub_entry->getDescription());

                                $description_subscript = $description_subscript_counter;

                                $description_subscript_counter++;
                            }

                            $amount_words = str_pad(ucwords($numbers_words->toWords(floor($pay_stub_entry->getAmount()), "en_US")) . ' ', 65, "-", STR_PAD_RIGHT);
                            //echo "Amount: ". floor($pay_stub_entry->getAmount()) ." - Words: ". $amount_words ."<br>\n";
                            //var_dump($amount_words);
                            if ($type != 40 or ($type == 40 and $pay_stub_entry->getAmount() != 0)) {
                                $pay_stub_entries[$type][] = array(
                                    'id' => $pay_stub_entry->getId(),
                                    'pay_stub_entry_name_id' => $pay_stub_entry->getPayStubEntryNameId(),
                                    'type' => $pay_stub_entry_name_obj->getType(),
                                    'name' => $pay_stub_entry_name_obj->getName(),
                                    'display_name' => $pay_stub_entry_name_obj->getName(),
                                    'rate' => $pay_stub_entry->getRate(),
                                    'units' => $pay_stub_entry->getUnits(),
                                    'ytd_units' => $pay_stub_entry->getYTDUnits(),
                                    'amount' => $pay_stub_entry->getAmount(),
                                    'amount_padded' => str_pad(TTi18n::formatNumber($pay_stub_entry->getAmount(), true, $pay_stub_obj->getCurrencyObject()->getRoundDecimalPlaces()), 12, '*', STR_PAD_LEFT),
                                    'amount_words' => $amount_words,
                                    'amount_cents' => Misc::getAfterDecimal($pay_stub_entry->getAmount()),
                                    'ytd_amount' => $pay_stub_entry->getYTDAmount(),

                                    'description' => $pay_stub_entry->getDescription(),
                                    'description_subscript' => $description_subscript,

                                    'created_date' => $pay_stub_entry->getCreatedDate(),
                                    'created_by' => $pay_stub_entry->getCreatedBy(),
                                    'updated_date' => $pay_stub_entry->getUpdatedDate(),
                                    'updated_by' => $pay_stub_entry->getUpdatedBy(),
                                    'deleted_date' => $pay_stub_entry->getDeletedDate(),
                                    'deleted_by' => $pay_stub_entry->getDeletedBy()
                                );
                            }
                            unset($amount_words);

                            $prev_type = $pay_stub_entry_name_obj->getType();
                        }

                        //Get User information
                        $ulf = TTnew('UserListFactory');
                        $user_obj = $ulf->getById($pay_stub_obj->getUser())->getCurrent();

                        //Get company information
                        $clf = TTnew('CompanyListFactory');
                        $company_obj = $clf->getById($user_obj->getCompany())->getCurrent();

                        if ($user_obj->getCountry() == 'CA') {
                            $date_format = 'd/m/Y';
                        } else {
                            $date_format = 'm/d/Y';
                        }
                        $pay_stub = array(
                            'id' => $pay_stub_obj->getId(),
                            'display_id' => $pay_stub_obj->getDisplayID(),
                            'user_id' => $pay_stub_obj->getUser(),
                            'pay_period_id' => $pay_stub_obj->getPayPeriod(),
                            'start_date' => $pay_stub_obj->getStartDate(),
                            'end_date' => $pay_stub_obj->getEndDate(),
                            'transaction_date' => $pay_stub_obj->getTransactionDate(),
                            'transaction_date_display' => date($date_format, $pay_stub_obj->getTransactionDate()),
                            'status' => $pay_stub_obj->getStatus(),
                            'entries' => $pay_stub_entries,
                            'tainted' => $pay_stub_obj->getTainted(),

                            'created_date' => $pay_stub_obj->getCreatedDate(),
                            'created_by' => $pay_stub_obj->getCreatedBy(),
                            'updated_date' => $pay_stub_obj->getUpdatedDate(),
                            'updated_by' => $pay_stub_obj->getUpdatedBy(),
                            'deleted_date' => $pay_stub_obj->getDeletedDate(),
                            'deleted_by' => $pay_stub_obj->getDeletedBy()
                        );
                        unset($pay_stub_entries);

                        if (isset($pay_stub['entries'][40][0]['amount']) and $pay_stub['entries'][40][0]['amount'] > 0) {
                            //Debug::text($i .'. Pay Stub Transaction Date: '. $pay_stub_obj->getTransactionDate(), __FILE__, __LINE__, __METHOD__, 10);

                            //Get Pay Period information
                            $pplf = TTnew('PayPeriodListFactory');
                            $pplf->getById($pay_stub_obj->getPayPeriod());
                            if ($pplf->getRecordCount() > 0) {
                                $pay_period_obj = $pplf->getCurrent();

                                $pp_start_date = $pay_period_obj->getStartDate();
                                $pp_end_date = $pay_period_obj->getEndDate();
                                $pp_transaction_date = $pay_period_obj->getTransactionDate();

                                //Get pay period numbers
                                $ppslf = TTnew('PayPeriodScheduleListFactory');
                                $ppslf->getById($pay_period_obj->getPayPeriodSchedule());
                                if ($ppslf->getRecordCount() > 0) {
                                    $pay_period_schedule_obj = $ppslf->getCurrent();

                                    $pay_period_data = array(
                                        'start_date' => TTDate::getDate('DATE', $pp_start_date),
                                        'end_date' => TTDate::getDate('DATE', $pp_end_date),
                                        'transaction_date' => TTDate::getDate('DATE', $pp_transaction_date),
                                        //'pay_period_number' => $pay_period_schedule_obj->getCurrentPayPeriodNumber( $pay_period_obj->getTransactionDate(), $pay_period_obj->getEndDate() ),
                                        'annual_pay_periods' => $pay_period_schedule_obj->getAnnualPayPeriods()
                                    );

                                    $ps_data = array(
                                        'date' => $pay_stub_obj->getTransactionDate(),
                                        'amount' => $pay_stub['entries'][40][0]['amount'],
                                        'stub_left_column' => $user_obj->getFullName() . "\n" .
                                            TTi18n::gettext('Identification #') . ': ' . $pay_stub['display_id'] . "\n" .
                                            TTi18n::gettext('Net Pay') . ': ' . $pay_stub_obj->getCurrencyObject()->getSymbol() . TTi18n::formatNumber($pay_stub['entries'][40][0]['amount'], true, $pay_stub_obj->getCurrencyObject()->getRoundDecimalPlaces()),

                                        'stub_right_column' => TTi18n::gettext('Pay Start Date') . ': ' . TTDate::getDate('DATE', $pay_stub['start_date']) . "\n" .
                                            TTi18n::gettext('Pay End Date') . ': ' . TTDate::getDate('DATE', $pay_stub['end_date']) . "\n" .
                                            TTi18n::gettext('Payment Date') . ': ' . TTDate::getDate('DATE', $pay_stub['transaction_date']),
                                        'start_date' => $pay_stub['start_date'],
                                        'end_date' => $pay_stub['end_date'],
                                        'full_name' => $user_obj->getFullName(),
                                        'address1' => $user_obj->getAddress1(),
                                        'address2' => $user_obj->getAddress2(),
                                        'city' => $user_obj->getCity(),
                                        'province' => $user_obj->getProvince(),
                                        'postal_code' => $user_obj->getPostalCode(),
                                        'country' => $user_obj->getCountry(),

                                        'company_name' => $company_obj->getName(),

                                        'symbol' => $pay_stub_obj->getCurrencyObject()->getSymbol(),
                                    );

                                    $cheque_form_obj->addRecord($ps_data);
                                    $this->getFormObject()->addForm($cheque_form_obj);
                                }
                            }
                        }

                        $this->getProgressBarObject()->set(null, $i);

                        $i++;
                    }

                    if (stristr($export_type, 'cheque')) {
                        $output_format = 'PDF';
                    }

                    if ($i > 0) {
                        $output = $this->getFormObject()->output($output_format);
                    }

                    break;
            }
        }

        if (isset($output)) {
            return $output;
        }

        return false;
    }

    public function getChequeFormsObject($format)
    {
        if (!isset($this->form_obj[$format]) or !is_object($this->form_obj[$format])) {
            $this->form_obj[$format] = $this->getFormObject()->getFormObject(strtoupper($format));
            return $this->form_obj[$format];
        }

        return $this->form_obj[$format];
    }

    /*

        Below here are functions for generating PDF pay stubs and exporting pay stub data to other
        formats such as cheques, or EFT file formats.

    */

    public function getFormObject()
    {
        if (!isset($this->form_obj['cf']) or !is_object($this->form_obj['cf'])) {
            //
            //Get all data for the form.
            //
            //require_once( Environment::getBasePath() .'/classes/fpdi/fpdi.php');
            //require_once( Environment::getBasePath() .'/classes/tcpdf/tcpdf.php');
            require_once(Environment::getBasePath() . '/classes/ChequeForms/ChequeForms.class.php');

            $cf = new ChequeForms();
            $this->form_obj['cf'] = $cf;
            return $this->form_obj['cf'];
        }

        return $this->form_obj['cf'];
    }

    public function getPayStub($pslf = null, $hide_employer_rows = true)
    {
        if (!is_object($pslf) and $this->getId() != '') {
            $pslf = TTnew('PayStubListFactory');
            $pslf->getById($this->getId());
        }

        if (get_class($pslf) !== 'PayStubListFactory') {
            return false;
        }

        $border = 0;

        $default_line_item_font_size = 10;

        if ($pslf->getRecordCount() > 0) {
            $i = 0;
            foreach ($pslf as $pay_stub_obj) {
                if ($i == 0) {
                    $pdf = new TTPDF('P', 'mm', 'LETTER', $pay_stub_obj->getUserObject()->getCompanyObject()->getEncoding());
                    $pdf->setMargins(0, 0);
                    //$pdf->SetAutoPageBreak(TRUE, 30);
                    $pdf->SetAutoPageBreak(false);

                    $pdf->SetFont(TTi18n::getPDFDefaultFont($pay_stub_obj->getUserObject()->getUserPreferenceObject()->getLanguage(), $pay_stub_obj->getUserObject()->getCompanyObject()->getEncoding()), '', 10);

                    $company_obj = $pay_stub_obj->getUserObject()->getCompanyObject();
                }

                $psealf = TTnew('PayStubEntryAccountListFactory');

                //Debug::text($i .'. Pay Stub Transaction Date: '. $pay_stub_obj->getTransactionDate(), __FILE__, __LINE__, __METHOD__, 10);

                //Get Pay Period information
                $pay_period_obj = $this->getPayPeriodObject();

                //Use Pay Stub dates, not Pay Period dates.
                $pp_start_date = $pay_stub_obj->getStartDate();
                $pp_end_date = $pay_stub_obj->getEndDate();
                $pp_transaction_date = $pay_stub_obj->getTransactionDate();

                //Get User information
                $ulf = TTnew('UserListFactory');
                $user_obj = $pay_stub_obj->getUserObject();

                //Change locale to users own locale.
                TTi18n::setLanguage($user_obj->getUserPreferenceObject()->getLanguage());
                TTi18n::setCountry($user_obj->getCountry());
                TTi18n::setLocale();

                //
                // Pay Stub Header
                //
                $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
                $pdf->AddPage();

                $adjust_x = 20;
                $adjust_y = 10;

                //Print important status as watermark on invoice.
                $pdf->setXY(Misc::AdjustXY(0, 20), Misc::AdjustXY(0, 240));

                $status_text = null;
                if ($pay_stub_obj->getStatus() == 100) {
                    $status_text = TTi18n::gettext('OPENING BALANCE');
                }

                $pdf->StartTransform();
                $pdf->Rotate(57);
                $pdf->SetFont('', 'B', 80);
                $pdf->setTextColor(255, 200, 200);
                if ($status_text != '') {
                    $pdf->Cell(250, 50, $status_text, $border, 0, 'C');
                }
                $pdf->StopTransform();

                $pdf->setPageMark(); //Must be set to multicells know about the background text.

                $pdf->SetFont('', '', 10);
                $pdf->setTextColor(0, 0, 0);
                unset($status_ext);

                //Reset pointer to the beginning of the page after watermark is drawn

                //Logo
                $pdf->Image($company_obj->getLogoFileName(null, true, false, 'large'), Misc::AdjustXY(0, $adjust_x), Misc::AdjustXY(1, $adjust_y), $pdf->pixelsToUnits(167), $pdf->pixelsToUnits(42), '', '', '', false, 300, '', false, false, 0, true);

                //Company name/address
                $pdf->SetFont('', 'B', 14);
                $pdf->setXY(Misc::AdjustXY(50, $adjust_x), Misc::AdjustXY(0, $adjust_y));
                $pdf->Cell(75, 5, $company_obj->getName(), $border, 0, 'C', false, '', 1);

                $pdf->SetFont('', '', 10);
                $pdf->setXY(Misc::AdjustXY(50, $adjust_x), Misc::AdjustXY(6, $adjust_y));
                $pdf->Cell(75, 5, $company_obj->getAddress1() . ' ' . $company_obj->getAddress2(), $border, 0, 'C', false, '', 1);

                $pdf->setXY(Misc::AdjustXY(50, $adjust_x), Misc::AdjustXY(10, $adjust_y));
                $pdf->Cell(75, 5, Misc::getCityAndProvinceAndPostalCode($company_obj->getCity(), $company_obj->getProvince(), $company_obj->getPostalCode()), $border, 0, 'C', false, '', 1);


                //Pay Period info
                $pdf->SetFont('', '', 10);
                $pdf->setXY(Misc::AdjustXY(125, $adjust_x), Misc::AdjustXY(0, $adjust_y));
                $pdf->Cell(30, 5, TTi18n::gettext('Pay Start Date') . ': ', $border, 0, 'R', false, '', 1);
                $pdf->setXY(Misc::AdjustXY(125, $adjust_x), Misc::AdjustXY(5, $adjust_y));
                $pdf->Cell(30, 5, TTi18n::gettext('Pay End Date') . ': ', $border, 0, 'R', false, '', 1);
                $pdf->setXY(Misc::AdjustXY(125, $adjust_x), Misc::AdjustXY(10, $adjust_y));
                $pdf->Cell(30, 5, TTi18n::gettext('Payment Date') . ': ', $border, 0, 'R', false, '', 1);

                $pdf->SetFont('', 'B', 10);
                $pdf->setXY(Misc::AdjustXY(155, $adjust_x), Misc::AdjustXY(0, $adjust_y));
                $pdf->Cell(20, 5, TTDate::getDate('DATE', $pp_start_date), $border, 0, 'R', false, '', 1);
                $pdf->setXY(Misc::AdjustXY(155, $adjust_x), Misc::AdjustXY(5, $adjust_y));
                $pdf->Cell(20, 5, TTDate::getDate('DATE', $pp_end_date), $border, 0, 'R', false, '', 1);
                $pdf->setXY(Misc::AdjustXY(155, $adjust_x), Misc::AdjustXY(10, $adjust_y));
                $pdf->Cell(20, 5, TTDate::getDate('DATE', $pp_transaction_date), $border, 0, 'R', false, '', 1);

                //Line
                $pdf->setLineWidth(1);
                $pdf->Line(Misc::AdjustXY(0, $adjust_x), Misc::AdjustXY(17, $adjust_y), Misc::AdjustXY(185, $adjust_y), Misc::AdjustXY(17, $adjust_y));
                $pdf->setLineWidth(0);

                $pdf->SetFont('', 'B', 14);
                $pdf->setXY(Misc::AdjustXY(0, $adjust_x), Misc::AdjustXY(19, $adjust_y));
                $pdf->Cell(175, 5, TTi18n::gettext('STATEMENT OF EARNINGS AND DEDUCTIONS'), $border, 0, 'C', false, '', 1);

                //Line
                $pdf->setLineWidth(1);
                $pdf->Line(Misc::AdjustXY(0, $adjust_x), Misc::AdjustXY(27, $adjust_y), Misc::AdjustXY(185, $adjust_y), Misc::AdjustXY(27, $adjust_y));

                $pdf->setLineWidth(0.25);

                if ($pay_stub_obj->getStatus() == 100) {
                    $pdf->setXY(Misc::AdjustXY(0, $adjust_x), Misc::AdjustXY(30, $adjust_y));
                    $pdf->SetFont('', 'B', 35);
                    $pdf->setTextColor(255, 0, 0);
                    $pdf->Cell(175, 12, TTi18n::getText('VOID'), $border, 0, 'C');
                    $pdf->SetFont('', '', 10);
                    $pdf->setTextColor(0, 0, 0);
                }

                //Get pay stub entries.
                $pself = TTnew('PayStubEntryListFactory');
                $pself->getByPayStubId($pay_stub_obj->getId());
                Debug::text('Pay Stub Entries: ' . $pself->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);

                $max_widths = array('units' => 0, 'rate' => 0, 'amount' => 0, 'ytd_amount' => 0);
                $prev_type = null;
                $description_subscript_counter = 1;
                foreach ($pself as $pay_stub_entry) {
                    //Debug::text('Pay Stub Entry Account ID: '.$pay_stub_entry->getPayStubEntryNameId(), __FILE__, __LINE__, __METHOD__, 10);
                    $description_subscript = null;

                    $pay_stub_entry_name_obj = $psealf->getById($pay_stub_entry->getPayStubEntryNameId())->getCurrent();

                    //Use this to put the total for each type at the end of the array.
                    //Check for prev_type=NULL/!isset($type) in case there are only Total Gross entries for $0.
                    if ($prev_type == 40 or $pay_stub_entry_name_obj->getType() != 40 or ($prev_type == null and !isset($type))) {
                        $type = $pay_stub_entry_name_obj->getType();
                    }
                    //Debug::text('Pay Stub Entry Name ID: '. $pay_stub_entry_name_obj->getId() .' Type ID: '. $pay_stub_entry_name_obj->getType() .' Type: '. $type, __FILE__, __LINE__, __METHOD__, 10);

                    if ($pay_stub_entry->getDescription() !== null
                        and $pay_stub_entry->getDescription() !== false
                        and strlen($pay_stub_entry->getDescription()) > 0
                        and ($type != 30 or ($type == 30 and $hide_employer_rows == false))
                    ) {     //Make sure PSA descriptions are not shown on employee pay stubs.
                        $pay_stub_entry_descriptions[] = array('subscript' => $description_subscript_counter,
                            'description' => $pay_stub_entry->getDescription());

                        $description_subscript = $description_subscript_counter;

                        $description_subscript_counter++;
                    }

                    //If type if 40 (a total) and the amount is 0, skip it.
                    //This if the employee has no deductions at all, it won't be displayed
                    //on the pay stub.
                    if ($type != 40 or ($type == 40 and $pay_stub_entry->getAmount() != 0)) {
                        $pay_stub_entries[$type][] = array(
                            'id' => $pay_stub_entry->getId(),
                            'pay_stub_entry_name_id' => $pay_stub_entry->getPayStubEntryNameId(),
                            'type' => $pay_stub_entry_name_obj->getType(),
                            'name' => $pay_stub_entry_name_obj->getName(),
                            'display_name' => $pay_stub_entry_name_obj->getName(),
                            'rate' => $pay_stub_entry->getRate(),
                            'units' => $pay_stub_entry->getUnits(),
                            'ytd_units' => $pay_stub_entry->getYTDUnits(),
                            'amount' => $pay_stub_entry->getAmount(),
                            'ytd_amount' => $pay_stub_entry->getYTDAmount(),

                            'description' => $pay_stub_entry->getDescription(),
                            'description_subscript' => $description_subscript,

                            'created_date' => $pay_stub_entry->getCreatedDate(),
                            'created_by' => $pay_stub_entry->getCreatedBy(),
                            'updated_date' => $pay_stub_entry->getUpdatedDate(),
                            'updated_by' => $pay_stub_entry->getUpdatedBy(),
                            'deleted_date' => $pay_stub_entry->getDeletedDate(),
                            'deleted_by' => $pay_stub_entry->getDeletedBy()
                        );

                        //Calculate maximum widths of numeric values.
                        $width_units = strlen($pay_stub_entry->getUnits());
                        if ($width_units > $max_widths['units']) {
                            $max_widths['units'] = $width_units;
                        }

                        $width_rate = strlen($pay_stub_entry->getRate());
                        if ($width_rate > $max_widths['rate']) {
                            $max_widths['rate'] = $width_rate;
                        }

                        $width_amount = strlen($pay_stub_entry->getAmount());
                        if ($width_amount > $max_widths['amount']) {
                            $max_widths['amount'] = $width_amount;
                        }

                        $width_ytd_amount = strlen($pay_stub_entry->getYTDAmount());
                        if ($width_amount > $max_widths['ytd_amount']) {
                            $max_widths['ytd_amount'] = $width_ytd_amount;
                        }

                        unset($width_rate, $width_units, $width_amount, $width_ytd_amount);
                    }

                    $prev_type = $pay_stub_entry_name_obj->getType();
                }

                //There should always be pay stub entries for a pay stub.
                if (!isset($pay_stub_entries)) {
                    continue;
                }
                //Debug::Arr($pay_stub_entries, 'Pay Stub Entries...', __FILE__, __LINE__, __METHOD__, 10);
                //Debug::Arr($max_widths, 'Maximum Widths: ', __FILE__, __LINE__, __METHOD__, 10);

                //Get Accrual Balance records here so we can use it for sizing the font.
                $ablf = TTnew('AccrualBalanceListFactory');
                $ablf->getByUserIdAndCompanyIdAndEnablePayStubBalanceDisplay($user_obj->getId(), $user_obj->getCompany(), true);

                //Calculate font size based on number of records to display
                $total_pay_stub_rows = 0;
                $total_pay_stub_rows += ($ablf->getRecordCount() > 0) ? ($ablf->getRecordCount() + 1) : 0;
                $total_pay_stub_rows += (isset($pay_stub_entries[10])) ? (count($pay_stub_entries[10]) + 2) : 0;
                $total_pay_stub_rows += (isset($pay_stub_entries[20])) ? (ceil(count($pay_stub_entries[20]) / 2) + 2) : 0;
                $total_pay_stub_rows += (isset($pay_stub_entries[50])) ? (count($pay_stub_entries[50]) + 1) : 0;
                $total_pay_stub_rows += (isset($pay_stub_entries[80])) ? (ceil(count($pay_stub_entries[80]) / 2) + 1) : 0;
                $total_pay_stub_rows += (isset($pay_stub_entry_descriptions)) ? (ceil(count($pay_stub_entry_descriptions) / 2) + 1) : 0;
                if ($hide_employer_rows != true) {
                    $total_pay_stub_rows += (isset($pay_stub_entries[30])) ? (ceil(count($pay_stub_entries[30]) / 2) + 2) : 0;
                }

                if ($total_pay_stub_rows == 0) {
                    $total_pay_stub_rows = 1; //Prevent division by 0 on empty pay stubs.
                }

                $default_line_item_font_size = (335 / $total_pay_stub_rows);
                if ($default_line_item_font_size > 12) {
                    $default_line_item_font_size = 12;
                } elseif ($default_line_item_font_size < 4) {
                    $default_line_item_font_size = 4;
                }
                Debug::Text('Pay Stub Total Rows: ' . $total_pay_stub_rows . ' Default Font Size: ' . $default_line_item_font_size, __FILE__, __LINE__, __METHOD__, 10);

                $block_adjust_y = 30;

                //Set Default cell height/width outside of the earnings, especially important if a pay stub has no earnings.
                $cell_height = 10;
                $column_widths['ytd_amount'] = (($max_widths['ytd_amount'] * 2) < 25) ? 25 : ($max_widths['ytd_amount'] * 2);
                $column_widths['amount'] = (($max_widths['amount'] * 2) < 20) ? 20 : ($max_widths['amount'] * 2);
                $column_widths['rate'] = (($max_widths['rate'] * 2) < 5) ? 5 : ($max_widths['rate'] * 2);
                $column_widths['units'] = (($max_widths['units'] * 2) < 17) ? 17 : ($max_widths['units'] * 2);
                $column_widths['name'] = (175 - ($column_widths['ytd_amount'] + $column_widths['amount'] + $column_widths['rate'] + $column_widths['units']));
                //Debug::Arr($column_widths, 'Column Widths: ', __FILE__, __LINE__, __METHOD__, 10);

                //
                //Earnings
                //
                if (isset($pay_stub_entries[10])) {
                    //Earnings Header
                    $pdf->SetFont('', 'B', $default_line_item_font_size);
                    $cell_height = $pdf->getStringHeight(10, 'Z');
                    $pdf->setXY(Misc::AdjustXY(0, $adjust_x), Misc::AdjustXY($block_adjust_y, $adjust_y));
                    $pdf->Cell($column_widths['name'], $cell_height, TTi18n::gettext('Earnings'), $border, 0, 'L', false, '', 1);
                    $pdf->Cell($column_widths['rate'], $cell_height, TTi18n::gettext('Rate'), $border, 0, 'R', false, '', 1);
                    $pdf->Cell($column_widths['units'], $cell_height, TTi18n::gettext('Hrs/Units'), $border, 0, 'R', false, '', 1);
                    $pdf->Cell($column_widths['amount'], $cell_height, TTi18n::gettext('Amount'), $border, 0, 'R', false, '', 1);
                    $pdf->Cell($column_widths['ytd_amount'], $cell_height, TTi18n::gettext('YTD Amount'), $border, 0, 'R', false, '', 1);

                    $block_adjust_y = ($block_adjust_y + $cell_height);

                    $pdf->SetFont('', '', $default_line_item_font_size);
                    $cell_height = $pdf->getStringHeight(10, 'Z');
                    foreach ($pay_stub_entries[10] as $pay_stub_entry) {
                        if ($pay_stub_entry['type'] == 10) {
                            if ($pay_stub_entry['description_subscript'] != '') {
                                $subscript = '[' . $pay_stub_entry['description_subscript'] . ']';
                            } else {
                                $subscript = null;
                            }

                            $pdf->setXY(Misc::AdjustXY(2, $adjust_x), Misc::AdjustXY($block_adjust_y, $adjust_y));
                            $pdf->Cell(($column_widths['name'] - 2), $cell_height, $pay_stub_entry['name'] . $subscript, $border, 0, 'L', false, '', 1); //68
                            $pdf->Cell($column_widths['rate'], $cell_height, ($pay_stub_entry['rate'] != 0) ? TTi18n::formatNumber($pay_stub_entry['rate'], true) : '-', $border, 0, 'R', false, '', 1);
                            $pdf->Cell($column_widths['units'], $cell_height, ($pay_stub_entry['units'] != 0) ? TTi18n::formatNumber($pay_stub_entry['units'], true) : '-', $border, 0, 'R', false, '', 1);
                            $pdf->Cell($column_widths['amount'], $cell_height, TTi18n::formatNumber($pay_stub_entry['amount'], true, $pay_stub_obj->getCurrencyObject()->getRoundDecimalPlaces()), $border, 0, 'R', false, '', 1);
                            $pdf->Cell($column_widths['ytd_amount'], $cell_height, ($pay_stub_entry['ytd_amount'] != 0) ? TTi18n::formatNumber($pay_stub_entry['ytd_amount'], true, $pay_stub_obj->getCurrencyObject()->getRoundDecimalPlaces()) : '-', $border, 0, 'R', false, '', 1);
                        } else {
                            //Total
                            $pdf->SetFont('', 'B', $default_line_item_font_size);
                            $cell_height = $pdf->getStringHeight(10, 'Z');

                            $pdf->line(Misc::AdjustXY(((175 - ($column_widths['ytd_amount']) - $column_widths['amount']) - $column_widths['units']), $adjust_x), Misc::AdjustXY($block_adjust_y, $adjust_y), Misc::AdjustXY((175 - (1 + $column_widths['ytd_amount']) - $column_widths['amount']), $adjust_x), Misc::AdjustXY($block_adjust_y, $adjust_y)); //90
                            $pdf->line(Misc::AdjustXY((175 - ($column_widths['ytd_amount']) - $column_widths['amount']), $adjust_x), Misc::AdjustXY($block_adjust_y, $adjust_y), Misc::AdjustXY((175 - (1 + $column_widths['ytd_amount'])), $adjust_x), Misc::AdjustXY($block_adjust_y, $adjust_y)); //111
                            $pdf->line(Misc::AdjustXY((175 - $column_widths['ytd_amount']), $adjust_x), Misc::AdjustXY($block_adjust_y, $adjust_y), Misc::AdjustXY(175, $adjust_x), Misc::AdjustXY($block_adjust_y, $adjust_y)); //141
                            $pdf->setXY(Misc::AdjustXY(0, $adjust_x), Misc::AdjustXY($block_adjust_y, $adjust_y));
                            $pdf->Cell($column_widths['name'], $cell_height, $pay_stub_entry['name'], $border, 0, 'L', false, '', 1);
                            $pdf->Cell($column_widths['rate'], $cell_height, '', $border, 0, 'R', false, '', 1);
                            $pdf->Cell($column_widths['units'], $cell_height, TTi18n::formatNumber($pay_stub_entry['units'], true), $border, 0, 'R', false, '', 1);
                            $pdf->Cell($column_widths['amount'], $cell_height, TTi18n::formatNumber($pay_stub_entry['amount'], true, $pay_stub_obj->getCurrencyObject()->getRoundDecimalPlaces()), $border, 0, 'R', false, '', 1);
                            $pdf->Cell($column_widths['ytd_amount'], $cell_height, TTi18n::formatNumber($pay_stub_entry['ytd_amount'], true, $pay_stub_obj->getCurrencyObject()->getRoundDecimalPlaces()), $border, 0, 'R', false, '', 1);
                        }

                        $block_adjust_y = ($block_adjust_y + $cell_height);
                    }
                }

                //
                // Deductions
                //
                if (isset($pay_stub_entries[20])) {
                    $max_deductions = count($pay_stub_entries[20]);

                    //Deductions Header
                    $block_adjust_y = ($block_adjust_y + $cell_height);

                    $pdf->SetFont('', 'B', $default_line_item_font_size);
                    $cell_height = $pdf->getStringHeight(10, 'Z');
                    if ($max_deductions > 2) {
                        $column_widths['name'] = (85 - ($column_widths['ytd_amount'] + $column_widths['amount']));

                        $pdf->setXY(Misc::AdjustXY(0, $adjust_x), Misc::AdjustXY($block_adjust_y, $adjust_y));
                        $pdf->Cell($column_widths['name'], $cell_height, TTi18n::gettext('Deductions'), $border, 0, 'L', false, '', 1);
                        $pdf->Cell($column_widths['amount'], $cell_height, TTi18n::gettext('Amount'), $border, 0, 'R', false, '', 1);
                        $pdf->Cell($column_widths['ytd_amount'], $cell_height, TTi18n::gettext('YTD Amount'), $border, 0, 'R', false, '', 1);

                        $pdf->setXY(Misc::AdjustXY(90, $adjust_x), Misc::AdjustXY($block_adjust_y, $adjust_y));
                        $pdf->Cell($column_widths['name'], $cell_height, TTi18n::gettext('Deductions'), $border, 0, 'L', false, '', 1);
                    } else {
                        $column_widths['name'] = (175 - ($column_widths['ytd_amount'] + $column_widths['amount']));

                        $pdf->setXY(Misc::AdjustXY(0, $adjust_x), Misc::AdjustXY($block_adjust_y, $adjust_y));
                        $pdf->Cell($column_widths['name'], $cell_height, TTi18n::gettext('Deductions'), $border, 0, 'L', false, '', 1);
                    }

                    $pdf->Cell($column_widths['amount'], $cell_height, TTi18n::gettext('Amount'), $border, 0, 'R', false, '', 1);
                    $pdf->Cell($column_widths['ytd_amount'], $cell_height, TTi18n::gettext('YTD Amount'), $border, 0, 'R', false, '', 1);

                    $block_adjust_y = $tmp_block_adjust_y = $top_block_adjust_y = ($block_adjust_y + $cell_height);

                    $pdf->SetFont('', '', $default_line_item_font_size);
                    $cell_height = $pdf->getStringHeight(10, 'Z');
                    $x = 0;
                    $max_block_adjust_y = 0;
                    foreach ($pay_stub_entries[20] as $pay_stub_entry) {
                        //Start with the right side.
                        if ($max_deductions > 2 and $x < floor($max_deductions / 2)) {
                            $tmp_adjust_x = 90;
                        } else {
                            if ($tmp_block_adjust_y != 0) {
                                $block_adjust_y = $tmp_block_adjust_y;
                                $tmp_block_adjust_y = 0;
                            }
                            $tmp_adjust_x = 0;
                        }

                        if ($pay_stub_entry['type'] == 20) {
                            if ($pay_stub_entry['description_subscript'] != '') {
                                $subscript = '[' . $pay_stub_entry['description_subscript'] . ']';
                            } else {
                                $subscript = null;
                            }

                            if ($max_deductions > 2) {
                                $pdf->setXY(Misc::AdjustXY(2, ($tmp_adjust_x + $adjust_x)), Misc::AdjustXY($block_adjust_y, $adjust_y));
                                $pdf->Cell(($column_widths['name'] - 2), $cell_height, $pay_stub_entry['name'] . $subscript, $border, 0, 'L', false, '', 1);
                            } else {
                                $pdf->setXY(Misc::AdjustXY(2, $adjust_x), Misc::AdjustXY($block_adjust_y, $adjust_y));
                                $pdf->Cell(($column_widths['name'] - 2), $cell_height, $pay_stub_entry['name'] . $subscript, $border, 0, 'L', false, '', 1);
                            }
                            $pdf->Cell($column_widths['amount'], $cell_height, TTi18n::formatNumber($pay_stub_entry['amount'], true, $pay_stub_obj->getCurrencyObject()->getRoundDecimalPlaces()), $border, 0, 'R', false, '', 1);
                            $pdf->Cell($column_widths['ytd_amount'], $cell_height, ($pay_stub_entry['ytd_amount'] != 0) ? TTi18n::formatNumber($pay_stub_entry['ytd_amount'], true, $pay_stub_obj->getCurrencyObject()->getRoundDecimalPlaces()) : '-', $border, 0, 'R', false, '', 1);
                            //Debug::Text('Y Adjustments: '. $adjust_y .' Block: '. $block_adjust_y, __FILE__, __LINE__, __METHOD__, 10);
                        } else {
                            $block_adjust_y = $max_block_adjust_y;

                            //Total
                            $pdf->SetFont('', 'B', $default_line_item_font_size);
                            $cell_height = $pdf->getStringHeight(10, 'Z');

                            $pdf->line(Misc::AdjustXY((175 - ($column_widths['ytd_amount']) - $column_widths['amount']), $adjust_x), Misc::AdjustXY($block_adjust_y, $adjust_y), Misc::AdjustXY((175 - (1 + $column_widths['ytd_amount'])), $adjust_x), Misc::AdjustXY($block_adjust_y, $adjust_y)); //111
                            $pdf->line(Misc::AdjustXY((175 - $column_widths['ytd_amount']), $adjust_x), Misc::AdjustXY($block_adjust_y, $adjust_y), Misc::AdjustXY(175, $adjust_x), Misc::AdjustXY($block_adjust_y, $adjust_y)); //141

                            $pdf->setXY(Misc::AdjustXY(0, $adjust_x), Misc::AdjustXY($block_adjust_y, $adjust_y));
                            $pdf->Cell((175 - ($column_widths['amount'] + $column_widths['ytd_amount'])), $cell_height, $pay_stub_entry['name'], $border, 0, 'L', false, '', 1); //110
                            $pdf->Cell($column_widths['amount'], $cell_height, TTi18n::formatNumber($pay_stub_entry['amount'], true, $pay_stub_obj->getCurrencyObject()->getRoundDecimalPlaces()), $border, 0, 'R', false, '', 1);
                            $pdf->Cell($column_widths['ytd_amount'], $cell_height, TTi18n::formatNumber($pay_stub_entry['ytd_amount'], true, $pay_stub_obj->getCurrencyObject()->getRoundDecimalPlaces()), $border, 0, 'R', false, '', 1);
                        }

                        $block_adjust_y = ($block_adjust_y + $cell_height);
                        if ($block_adjust_y > $max_block_adjust_y) {
                            $max_block_adjust_y = $block_adjust_y;
                        }

                        $x++;
                    }

                    //Draw line to separate the two columns
                    if ($max_deductions > 2) {
                        $pdf->Line(Misc::AdjustXY(88, $adjust_x), Misc::AdjustXY(($top_block_adjust_y - $cell_height), $adjust_y), Misc::AdjustXY(88, $adjust_x), Misc::AdjustXY(($max_block_adjust_y - $cell_height), $adjust_y));
                    }

                    unset($x, $max_deductions, $tmp_adjust_x, $max_block_adjust_y, $tmp_block_adjust_y, $top_block_adjust_y);
                }

                if (isset($pay_stub_entries[40][0])) {
                    $block_adjust_y = ($block_adjust_y + $cell_height);

                    //Net Pay entry
                    $pdf->SetFont('', 'B', $default_line_item_font_size);
                    $cell_height = $pdf->getStringHeight(10, 'Z');

                    $pdf->setXY(Misc::AdjustXY(0, $adjust_x), Misc::AdjustXY($block_adjust_y, $adjust_y));
                    $pdf->Cell((175 - ($column_widths['amount'] + $column_widths['ytd_amount'])), $cell_height, $pay_stub_entries[40][0]['name'], $border, 0, 'L', false, '', 1);
                    $pdf->Cell($column_widths['amount'], $cell_height, TTi18n::formatNumber($pay_stub_entries[40][0]['amount'], true, $pay_stub_obj->getCurrencyObject()->getRoundDecimalPlaces()), $border, 0, 'R', false, '', 1);
                    $pdf->Cell($column_widths['ytd_amount'], $cell_height, TTi18n::formatNumber($pay_stub_entries[40][0]['ytd_amount'], true, $pay_stub_obj->getCurrencyObject()->getRoundDecimalPlaces()), $border, 0, 'R', false, '', 1);

                    $block_adjust_y = ($block_adjust_y + $cell_height);
                }

                //
                //Miscellaneous
                //
                if (isset($pay_stub_entries[80])) {
                    $max_deductions = count($pay_stub_entries[80]);
                    //Deductions Header
                    $block_adjust_y = ($block_adjust_y + $cell_height);

                    $pdf->SetFont('', 'B', $default_line_item_font_size);
                    $cell_height = $pdf->getStringHeight(10, 'Z');
                    if ($max_deductions > 2) {
                        $column_widths['name'] = (85 - ($column_widths['ytd_amount'] + $column_widths['amount']));

                        $pdf->setXY(Misc::AdjustXY(0, $adjust_x), Misc::AdjustXY($block_adjust_y, $adjust_y));
                        $pdf->Cell($column_widths['name'], $cell_height, TTi18n::gettext('Miscellaneous'), $border, 0, 'L', false, '', 1);
                        $pdf->Cell($column_widths['amount'], $cell_height, TTi18n::gettext('Amount'), $border, 0, 'R', false, '', 1);
                        $pdf->Cell($column_widths['ytd_amount'], $cell_height, TTi18n::gettext('YTD Amount'), $border, 0, 'R', false, '', 1);

                        $pdf->setXY(Misc::AdjustXY(90, $adjust_x), Misc::AdjustXY($block_adjust_y, $adjust_y));
                        $pdf->Cell($column_widths['name'], $cell_height, TTi18n::gettext('Miscellaneous'), $border, 0, 'L', false, '', 1);
                    } else {
                        $column_widths['name'] = (175 - ($column_widths['ytd_amount'] + $column_widths['amount']));

                        $pdf->setXY(Misc::AdjustXY(0, $adjust_x), Misc::AdjustXY($block_adjust_y, $adjust_y));
                        $pdf->Cell($column_widths['name'], $cell_height, TTi18n::gettext('Miscellaneous'), $border, 0, 'L', false, '', 1);
                    }

                    $pdf->Cell($column_widths['amount'], $cell_height, TTi18n::gettext('Amount'), $border, 0, 'R', false, '', 1);
                    $pdf->Cell($column_widths['ytd_amount'], $cell_height, TTi18n::gettext('YTD Amount'), $border, 0, 'R', false, '', 1);

                    $block_adjust_y = $tmp_block_adjust_y = $top_block_adjust_y = ($block_adjust_y + $cell_height);

                    $pdf->SetFont('', '', $default_line_item_font_size);
                    $cell_height = $pdf->getStringHeight(10, 'Z');
                    $x = 0;
                    $max_block_adjust_y = 0;

                    foreach ($pay_stub_entries[80] as $pay_stub_entry) {
                        //Start with the right side.
                        if ($max_deductions > 2 and $x < floor($max_deductions / 2)) {
                            $tmp_adjust_x = 90;
                        } else {
                            if ($tmp_block_adjust_y != 0) {
                                $block_adjust_y = $tmp_block_adjust_y;
                                $tmp_block_adjust_y = 0;
                            }
                            $tmp_adjust_x = 0;
                        }

                        if ($pay_stub_entry['type'] == 80) {
                            if ($pay_stub_entry['description_subscript'] != '') {
                                $subscript = '[' . $pay_stub_entry['description_subscript'] . ']';
                            } else {
                                $subscript = null;
                            }

                            if ($max_deductions > 2) {
                                $pdf->setXY(Misc::AdjustXY(2, ($tmp_adjust_x + $adjust_x)), Misc::AdjustXY($block_adjust_y, $adjust_y));
                                $pdf->Cell(($column_widths['name'] - 2), $cell_height, $pay_stub_entry['name'] . $subscript, $border, 0, 'L', false, '', 1); //38
                            } else {
                                $pdf->setXY(Misc::AdjustXY(2, $adjust_x), Misc::AdjustXY($block_adjust_y, $adjust_y));
                                $pdf->Cell(($column_widths['name'] - 2), $cell_height, $pay_stub_entry['name'] . $subscript, $border, 0, 'L', false, '', 1); //128
                            }
                            $pdf->Cell($column_widths['amount'], $cell_height, TTi18n::formatNumber($pay_stub_entry['amount'], true, $pay_stub_obj->getCurrencyObject()->getRoundDecimalPlaces()), $border, 0, 'R', false, '', 1);
                            $pdf->Cell($column_widths['ytd_amount'], $cell_height, ($pay_stub_entry['ytd_amount'] != 0) ? TTi18n::formatNumber($pay_stub_entry['ytd_amount'], true, $pay_stub_obj->getCurrencyObject()->getRoundDecimalPlaces()) : '-', $border, 0, 'R', false, '', 1);
                        }

                        $block_adjust_y = ($block_adjust_y + $cell_height);
                        if ($block_adjust_y > $max_block_adjust_y) {
                            $max_block_adjust_y = $block_adjust_y;
                        }

                        $x++;
                    }

                    //Draw line to separate the two columns
                    if ($max_deductions > 2) {
                        $pdf->Line(Misc::AdjustXY(88, $adjust_x), Misc::AdjustXY(($top_block_adjust_y - $cell_height), $adjust_y), Misc::AdjustXY(88, $adjust_x), Misc::AdjustXY(($max_block_adjust_y), $adjust_y));
                    }

                    unset($x, $max_deductions, $tmp_adjust_x, $max_block_adjust_y, $tmp_block_adjust_y, $top_block_adjust_y);
                }

                //
                //Employer Contributions
                //
                if (isset($pay_stub_entries[30]) and $hide_employer_rows != true) {
                    $max_deductions = count($pay_stub_entries[30]);
                    //Deductions Header
                    $block_adjust_y = ($block_adjust_y + $cell_height);

                    $pdf->SetFont('', 'B', $default_line_item_font_size);
                    $cell_height = $pdf->getStringHeight(10, 'Z');
                    if ($max_deductions > 2) {
                        $column_widths['name'] = (85 - ($column_widths['ytd_amount'] + $column_widths['amount']));

                        $pdf->setXY(Misc::AdjustXY(0, $adjust_x), Misc::AdjustXY($block_adjust_y, $adjust_y));
                        $pdf->Cell($column_widths['name'], $cell_height, TTi18n::gettext('Employer Contributions'), $border, 0, 'L', false, '', 1);
                        $pdf->Cell($column_widths['amount'], $cell_height, TTi18n::gettext('Amount'), $border, 0, 'R', false, '', 1);
                        $pdf->Cell($column_widths['ytd_amount'], $cell_height, TTi18n::gettext('YTD Amount'), $border, 0, 'R', false, '', 1);

                        $pdf->setXY(Misc::AdjustXY(90, $adjust_x), Misc::AdjustXY($block_adjust_y, $adjust_y));
                        $pdf->Cell($column_widths['name'], $cell_height, TTi18n::gettext('Employer Contributions'), $border, 0, 'L', false, '', 1);
                    } else {
                        $column_widths['name'] = (175 - ($column_widths['ytd_amount'] + $column_widths['amount']));

                        $pdf->setXY(Misc::AdjustXY(0, $adjust_x), Misc::AdjustXY($block_adjust_y, $adjust_y));
                        $pdf->Cell($column_widths['name'], $cell_height, TTi18n::gettext('Employer Contributions'), $border, 0, 'L', false, '', 1);
                    }

                    $pdf->Cell($column_widths['amount'], $cell_height, TTi18n::gettext('Amount'), $border, 0, 'R', false, '', 1);
                    $pdf->Cell($column_widths['ytd_amount'], $cell_height, TTi18n::gettext('YTD Amount'), $border, 0, 'R', false, '', 1);

                    $block_adjust_y = $tmp_block_adjust_y = $top_block_adjust_y = ($block_adjust_y + $cell_height);

                    $pdf->SetFont('', '', $default_line_item_font_size);
                    $cell_height = $pdf->getStringHeight(10, 'Z');
                    $x = 0;
                    $max_block_adjust_y = 0;

                    foreach ($pay_stub_entries[30] as $pay_stub_entry) {
                        //Start with the right side.
                        if ($max_deductions > 2 and $x < floor($max_deductions / 2)) {
                            $tmp_adjust_x = 90;
                        } else {
                            if ($tmp_block_adjust_y != 0) {
                                $block_adjust_y = $tmp_block_adjust_y;
                                $tmp_block_adjust_y = 0;
                            }
                            $tmp_adjust_x = 0;
                        }

                        if ($pay_stub_entry['type'] == 30) {
                            if ($pay_stub_entry['description_subscript'] != '') {
                                $subscript = '[' . $pay_stub_entry['description_subscript'] . ']';
                            } else {
                                $subscript = null;
                            }

                            if ($max_deductions > 2) {
                                $pdf->setXY(Misc::AdjustXY(2, ($tmp_adjust_x + $adjust_x)), Misc::AdjustXY($block_adjust_y, $adjust_y));
                                $pdf->Cell(($column_widths['name'] - 2), $cell_height, $pay_stub_entry['name'] . $subscript, $border, 0, 'L', false, '', 1); //38
                            } else {
                                $pdf->setXY(Misc::AdjustXY(2, $adjust_x), Misc::AdjustXY($block_adjust_y, $adjust_y));
                                $pdf->Cell(($column_widths['name'] - 2), $cell_height, $pay_stub_entry['name'] . $subscript, $border, 0, 'L', false, '', 1); //128
                            }
                            $pdf->Cell($column_widths['amount'], $cell_height, TTi18n::formatNumber($pay_stub_entry['amount'], true, $pay_stub_obj->getCurrencyObject()->getRoundDecimalPlaces()), $border, 0, 'R', false, '', 1);
                            $pdf->Cell($column_widths['ytd_amount'], $cell_height, ($pay_stub_entry['ytd_amount'] != 0) ? TTi18n::formatNumber($pay_stub_entry['ytd_amount'], true, $pay_stub_obj->getCurrencyObject()->getRoundDecimalPlaces()) : '-', $border, 0, 'R', false, '', 1);
                        } else {
                            $block_adjust_y = $max_block_adjust_y;

                            //Total
                            $pdf->SetFont('', 'B', $default_line_item_font_size);

                            $pdf->line(Misc::AdjustXY((175 - ($column_widths['ytd_amount']) - $column_widths['amount']), $adjust_x), Misc::AdjustXY($block_adjust_y, $adjust_y), Misc::AdjustXY((175 - (1 + $column_widths['ytd_amount'])), $adjust_x), Misc::AdjustXY($block_adjust_y, $adjust_y)); //111
                            $pdf->line(Misc::AdjustXY((175 - $column_widths['ytd_amount']), $adjust_x), Misc::AdjustXY($block_adjust_y, $adjust_y), Misc::AdjustXY(175, $adjust_x), Misc::AdjustXY($block_adjust_y, $adjust_y)); //141

                            $pdf->setXY(Misc::AdjustXY(0, $adjust_x), Misc::AdjustXY($block_adjust_y, $adjust_y));
                            $pdf->Cell((175 - ($column_widths['amount'] + $column_widths['ytd_amount'])), $cell_height, $pay_stub_entry['name'], $border, 0, 'L', false, '', 1);
                            $pdf->Cell($column_widths['amount'], $cell_height, TTi18n::formatNumber($pay_stub_entry['amount'], true, $pay_stub_obj->getCurrencyObject()->getRoundDecimalPlaces()), $border, 0, 'R', false, '', 1);
                            $pdf->Cell($column_widths['ytd_amount'], $cell_height, TTi18n::formatNumber($pay_stub_entry['ytd_amount'], true, $pay_stub_obj->getCurrencyObject()->getRoundDecimalPlaces()), $border, 0, 'R', false, '', 1);
                        }

                        $block_adjust_y = ($block_adjust_y + $cell_height);
                        if ($block_adjust_y > $max_block_adjust_y) {
                            $max_block_adjust_y = $block_adjust_y;
                        }

                        $x++;
                    }

                    //Draw line to separate the two columns
                    if ($max_deductions > 2) {
                        $pdf->Line(Misc::AdjustXY(88, $adjust_x), Misc::AdjustXY(($top_block_adjust_y - $cell_height), $adjust_y), Misc::AdjustXY(88, $adjust_x), Misc::AdjustXY(($max_block_adjust_y - $cell_height), $adjust_y));
                    }

                    unset($x, $max_deductions, $tmp_adjust_x, $max_block_adjust_y, $tmp_block_adjust_y, $top_block_adjust_y);
                }

                //
                //Accruals PS accounts
                //
                if (isset($pay_stub_entries[50])) {
                    //Accrual Header
                    $block_adjust_y = ($block_adjust_y + $cell_height);

                    $pdf->SetFont('', 'B', $default_line_item_font_size);
                    $cell_height = $pdf->getStringHeight(10, 'Z');
                    $pdf->setXY(Misc::AdjustXY(0, $adjust_x), Misc::AdjustXY($block_adjust_y, $adjust_y));
                    $pdf->Cell((175 - ($column_widths['amount'] + $column_widths['ytd_amount'])), $cell_height, TTi18n::gettext('Accruals'), $border, 0, 'L', false, '', 1);
                    $pdf->Cell($column_widths['amount'], $cell_height, TTi18n::gettext('Amount'), $border, 0, 'R', false, '', 1);
                    $pdf->Cell($column_widths['ytd_amount'], $cell_height, TTi18n::gettext('Balance'), $border, 0, 'R', false, '', 1);

                    $block_adjust_y = ($block_adjust_y + $cell_height);

                    $pdf->SetFont('', '', $default_line_item_font_size);
                    $cell_height = $pdf->getStringHeight(10, 'Z');
                    foreach ($pay_stub_entries[50] as $pay_stub_entry) {
                        if ($pay_stub_entry['type'] == 50) {
                            if ($pay_stub_entry['description_subscript'] != '') {
                                $subscript = '[' . $pay_stub_entry['description_subscript'] . ']';
                            } else {
                                $subscript = null;
                            }

                            $pdf->setXY(Misc::AdjustXY(2, $adjust_x), Misc::AdjustXY($block_adjust_y, $adjust_y));
                            $pdf->Cell((175 - ($column_widths['amount'] + $column_widths['ytd_amount']) - 2), $cell_height, $pay_stub_entry['name'] . $subscript, $border, 0, 'L', false, '', 1);
                            $pdf->Cell($column_widths['amount'], $cell_height, TTi18n::formatNumber($pay_stub_entry['amount'], true, $pay_stub_obj->getCurrencyObject()->getRoundDecimalPlaces()), $border, 0, 'R', false, '', 1);
                            $pdf->Cell($column_widths['ytd_amount'], $cell_height, ($pay_stub_entry['ytd_amount'] != 0) ? TTi18n::formatNumber($pay_stub_entry['ytd_amount'], true, $pay_stub_obj->getCurrencyObject()->getRoundDecimalPlaces()) : '-', $border, 0, 'R', false, '', 1);
                        }

                        $block_adjust_y = ($block_adjust_y + $cell_height);
                    }
                }

                //
                //Accrual Account Balances
                //
                if ($ablf->getRecordCount() > 0) {
                    //Accrual Header
                    $block_adjust_y = ($block_adjust_y + $cell_height);

                    $pdf->SetFont('', 'B', $default_line_item_font_size);
                    $cell_height = $pdf->getStringHeight(10, 'Z');

                    $pdf->setXY(Misc::AdjustXY(40, $adjust_x), Misc::AdjustXY($block_adjust_y, $adjust_y));

                    $accrual_time_header_start_x = $pdf->getX();
                    $accrual_time_header_start_y = $pdf->getY();

                    $pdf->Cell(70, $cell_height, TTi18n::gettext('Accrual Time Balances as of') . ' ' . TTDate::getDate('DATE', time()), $border, 0, 'L', false, '', 1);
                    $pdf->Cell(25, $cell_height, TTi18n::gettext('Balance (hrs)'), $border, 0, 'R', false, '', 1);

                    $block_adjust_y = ($block_adjust_y + $cell_height);
                    $box_height = $cell_height;

                    $pdf->SetFont('', '', $default_line_item_font_size);
                    $cell_height = $pdf->getStringHeight(10, 'Z');
                    foreach ($ablf as $ab_obj) {
                        $balance = $ab_obj->getBalance();
                        if (!is_numeric($balance)) {
                            $balance = 0;
                        }

                        $pdf->setXY(Misc::AdjustXY(40, $adjust_x), Misc::AdjustXY($block_adjust_y, $adjust_y));
                        $pdf->Cell(70, $cell_height, $ab_obj->getColumn('name'), $border, 0, 'L', false, '', 1);
                        $pdf->Cell(25, $cell_height, TTi18n::formatNumber(TTDate::getHours($balance), true, 2, 2), $border, 0, 'R', false, '', 1);

                        $block_adjust_y = ($block_adjust_y + $cell_height);
                        $box_height = ($box_height + $cell_height);
                        unset($balance);
                    }
                    $pdf->Rect($accrual_time_header_start_x, $accrual_time_header_start_y, 95, $box_height);

                    unset($accrual_time_header_start_x, $accrual_time_header_start_y, $box_height);
                }


                //
                //Descriptions
                //
                if (isset($pay_stub_entry_descriptions) and count($pay_stub_entry_descriptions) > 0) {

                    //Description Header
                    $block_adjust_y = ($block_adjust_y + $cell_height);

                    $pdf->SetFont('', 'B', $default_line_item_font_size);
                    $cell_height = $pdf->getStringHeight(10, 'Z');
                    $pdf->setXY(Misc::AdjustXY(0, $adjust_x), Misc::AdjustXY($block_adjust_y, $adjust_y));
                    $pdf->Cell(175, $cell_height, TTi18n::gettext('Notes'), $border, 0, 'L', false, '', 1);

                    $block_adjust_y = ($block_adjust_y + $cell_height);

                    $pdf->SetFont('', '', $default_line_item_font_size);
                    $cell_height = $pdf->getStringHeight(10, 'Z');
                    $x = 0;
                    foreach ($pay_stub_entry_descriptions as $pay_stub_entry_description) {
                        if (($x % 2) == 0) {
                            $pdf->setXY(Misc::AdjustXY(2, $adjust_x), Misc::AdjustXY($block_adjust_y, $adjust_y));
                        } else {
                            $pdf->setXY(Misc::AdjustXY(90, $adjust_x), Misc::AdjustXY($block_adjust_y, $adjust_y));
                        }

                        $pdf->Cell(85, $cell_height, '[' . $pay_stub_entry_description['subscript'] . '] ' . html_entity_decode($pay_stub_entry_description['description']), $border, 0, 'L', false, '', 1);

                        if (($x % 2) != 0) {
                            $block_adjust_y = ($block_adjust_y + $cell_height);
                        }
                        $x++;
                    }
                }
                unset($x, $pay_stub_entry_descriptions, $pay_stub_entry_description);


                //
                // Tax information.
                //
                $block_adjust_y = 211;
                $pdf->SetFont('', '', 6);
                $pdf->setXY(Misc::AdjustXY(0, $adjust_x), Misc::AdjustXY(($block_adjust_y + 3), $adjust_y));

                $udlf = TTnew('UserDeductionListFactory');
                $udlf->getByCompanyIdAndUserId($user_obj->getCompany(), $user_obj->getID());
                $udlf->getAPISearchByCompanyIdAndArrayCriteria($user_obj->getCompany(), array('status_id' => 10, 'user_id' => $user_obj->getID(), 'calculation_id' => array(100, 200)));
                if ($udlf->getRecordCount() > 0) {
                    $pdf->setLineWidth(0.10);

                    $max_tax_info_rows = ($udlf->getRecordCount() / 2);

                    $left_total_rows = 0;
                    $right_total_rows = 0;
                    foreach ($udlf as $ud_obj) {
                        if ($ud_obj->getCompanyDeductionObject()->getCalculation() == 100) { //Federal
                            $left_total_rows++;
                        } elseif ($ud_obj->getCompanyDeductionObject()->getCalculation() == 200) { //Province/State
                            $right_total_rows++;
                        }
                    }

                    $left_block_adjust_y = $right_block_adjust_y = $block_adjust_y;

                    Debug::Text('Tax Info Rows: Left: ' . $left_total_rows . ' Right: ' . $right_total_rows . ' Transaction Date: ' . TTDate::getDate('DATE', $pp_transaction_date), __FILE__, __LINE__, __METHOD__, 10);
                    if ($left_total_rows < $right_total_rows) {
                        for ($i = 0; $i < ($right_total_rows - $left_total_rows); $i++) {
                            $pdf->setXY(Misc::AdjustXY(0, $adjust_x), Misc::AdjustXY($left_block_adjust_y, $adjust_y));
                            $pdf->Cell(87.5, 3, '', 1, 0, 'C', false, '', 1);
                            $left_block_adjust_y = ($left_block_adjust_y - 3);
                        }
                    } elseif ($right_total_rows < $left_total_rows) {
                        for ($i = 0; $i < ($left_total_rows - $right_total_rows); $i++) {
                            $pdf->setXY(Misc::AdjustXY(87.5, $adjust_x), Misc::AdjustXY($right_block_adjust_y, $adjust_y));
                            $pdf->Cell(87.5, 3, '', 1, 0, 'C', false, '', 1);
                            $right_block_adjust_y = ($right_block_adjust_y - 3);
                        }
                    }

                    foreach ($udlf as $ud_obj) {
                        if ($ud_obj->getCompanyDeductionObject()->getCalculation() == 100) { //Federal
                            $pdf->setXY(Misc::AdjustXY(0, $adjust_x), Misc::AdjustXY($left_block_adjust_y, $adjust_y));
                            $pdf->Cell(87.5, 3, $ud_obj->getDescription($pp_transaction_date), 1, 0, 'C', false, '', 1);
                            $left_block_adjust_y = ($left_block_adjust_y - 3);
                        }
                    }

                    foreach ($udlf as $ud_obj) {
                        if ($ud_obj->getCompanyDeductionObject()->getCalculation() == 200) { //Province/State
                            $pdf->setXY(Misc::AdjustXY(87.5, $adjust_x), Misc::AdjustXY($right_block_adjust_y, $adjust_y));
                            $pdf->Cell(87.5, 3, $ud_obj->getDescription($pp_transaction_date), 1, 0, 'C', false, '', 1);
                            $right_block_adjust_y = ($right_block_adjust_y - 3);
                        }
                    }

                    $block_adjust_y = $left_block_adjust_y;

                    $pdf->SetFont('', 'B', 6);
                    $pdf->setXY(Misc::AdjustXY(0, $adjust_x), Misc::AdjustXY($block_adjust_y, $adjust_y));
                    $pdf->Cell(87.5, 3, TTi18n::gettext('Federal'), 1, 0, 'C', false, '', 1);

                    $pdf->SetFont('', 'B', 6);
                    $pdf->setXY(Misc::AdjustXY(87.5, $adjust_x), Misc::AdjustXY($block_adjust_y, $adjust_y));
                    $pdf->Cell(87.5, 3, TTi18n::gettext('Province/State'), 1, 0, 'C', false, '', 1);

                    $pdf->SetFont('', 'B', 6);
                    $pdf->setXY(Misc::AdjustXY(0, $adjust_x), Misc::AdjustXY(($block_adjust_y - 3), $adjust_y));
                    $pdf->Cell(175, 3, TTi18n::gettext('Tax Information as of') . ' ' . TTDate::getDate('DATE', time()), 1, 0, 'C', false, '', 1);
                }
                unset($udlf, $ud_obj, $left_block_adjust_y, $right_block_adjust_y, $left_total_rows, $right_total_rows);

                //
                // Pay Stub Footer
                //

                $block_adjust_y = 215;
                //Line
                $pdf->setLineWidth(1);
                $pdf->Line(Misc::AdjustXY(0, $adjust_x), Misc::AdjustXY($block_adjust_y, $adjust_y), Misc::AdjustXY(185, $adjust_y), Misc::AdjustXY($block_adjust_y, $adjust_y));
                $pdf->setLineWidth(0);

                //Non Negotiable
                $pdf->SetFont('', 'B', 14);
                $pdf->setXY(Misc::AdjustXY(0, $adjust_x), Misc::AdjustXY(($block_adjust_y + 3), $adjust_y));
                $pdf->Cell(175, 5, TTi18n::gettext('NON NEGOTIABLE'), $border, 0, 'C', false, '', 1);

                if ($pay_stub_obj->getStatus() == 100) {
                    $pdf->setXY(Misc::AdjustXY(0, $adjust_x), Misc::AdjustXY(($block_adjust_y + 15), $adjust_y));
                    $pdf->SetFont('', 'B', 35);
                    $pdf->setTextColor(255, 0, 0);
                    $pdf->Cell(175, 12, TTi18n::getText('VOID'), $border, 0, 'C');
                    $pdf->SetFont('', '', 10);
                    $pdf->setTextColor(0, 0, 0);
                }

                //Employee Address
                $pdf->SetFont('', 'B', 12);
                $pdf->setXY(Misc::AdjustXY(0, $adjust_x), Misc::AdjustXY(($block_adjust_y + 9), $adjust_y));
                $pdf->Cell(60, 5, TTi18n::gettext('CONFIDENTIAL'), $border, 0, 'C', false, '', 1);
                $pdf->SetFont('', '', 10);
                $pdf->setXY(Misc::AdjustXY(0, $adjust_x), Misc::AdjustXY(($block_adjust_y + 14), $adjust_y));
                $pdf->Cell(60, 5, $user_obj->getFullName() . ' (#' . $user_obj->getEmployeeNumber() . ')', $border, 0, 'C', false, '', 1);
                $pdf->setXY(Misc::AdjustXY(0, $adjust_x), Misc::AdjustXY(($block_adjust_y + 19), $adjust_y));
                $pdf->Cell(60, 5, $user_obj->getAddress1(), $border, 0, 'C', false, '', 1);
                $address2_adjust_y = 0;
                if ($user_obj->getAddress2() != '') {
                    $address2_adjust_y = 5;
                    $pdf->setXY(Misc::AdjustXY(0, $adjust_x), Misc::AdjustXY(($block_adjust_y + 24), $adjust_y));
                    $pdf->Cell(60, 5, $user_obj->getAddress2(), $border, 0, 'C', false, '', 1);
                }
                $pdf->setXY(Misc::AdjustXY(0, $adjust_x), Misc::AdjustXY(($block_adjust_y + 24 + $address2_adjust_y), $adjust_y));
                $pdf->Cell(60, 5, Misc::getCityAndProvinceAndPostalCode($user_obj->getCity(), $user_obj->getProvince(), $user_obj->getPostalCode()), $border, 1, 'C', false, '', 1);

                //Pay Period - Balance - ID
                $net_pay_amount = 0;
                if (isset($pay_stub_entries[40][0])) {
                    $net_pay_amount = TTi18n::formatNumber($pay_stub_entries[40][0]['amount'], true, $pay_stub_obj->getCurrencyObject()->getRoundDecimalPlaces());
                }

                if (isset($pay_stub_entries[65]) and count($pay_stub_entries[65]) > 0) {
                    $net_pay_label = TTi18n::gettext('Balance');
                } else {
                    $net_pay_label = TTi18n::gettext('Net Pay');
                }

                $pdf->SetFont('', 'B', 12);
                $pdf->setXY(Misc::AdjustXY(75, $adjust_x), Misc::AdjustXY(($block_adjust_y + 9), $adjust_y));
                $pdf->Cell(100, 5, $net_pay_label . ': ' . $pay_stub_obj->getCurrencyObject()->getSymbol() . $net_pay_amount . ' ' . $pay_stub_obj->getCurrencyObject()->getISOCode(), $border, 1, 'R', false, '', 1);

                //Display additional employee information on the pay stub such as job title, SIN, hire date.
                $block_adjust_y = ($block_adjust_y + 12);

                $pdf->SetFont('', '', 8);
                if ($user_obj->getTitle() > 0 and is_object($user_obj->getTitleObject())) {
                    $block_adjust_y = ($block_adjust_y + 3);
                    $pdf->setXY(Misc::AdjustXY(75, $adjust_x), Misc::AdjustXY($block_adjust_y, $adjust_y));
                    $pdf->Cell(100, 4, TTi18n::gettext('Title') . ': ' . $user_obj->getTitleObject()->getName(), $border, 1, 'R', false, '', 1);
                }
                if ($user_obj->getHireDate() != '') {
                    $block_adjust_y = ($block_adjust_y + 3);
                    $pdf->setXY(Misc::AdjustXY(75, $adjust_x), Misc::AdjustXY($block_adjust_y, $adjust_y));
                    $pdf->Cell(100, 4, TTi18n::gettext('Hire Date') . ': ' . TTDate::getDate('DATE', $user_obj->getHireDate()), $border, 1, 'R', false, '', 1);
                }
                if ($user_obj->getTerminationDate() != '' and $user_obj->getTerminationDate() <= $pay_stub_obj->getEndDate()) {
                    $block_adjust_y = ($block_adjust_y + 3);
                    $pdf->setXY(Misc::AdjustXY(75, $adjust_x), Misc::AdjustXY($block_adjust_y, $adjust_y));
                    $pdf->Cell(100, 4, TTi18n::gettext('Termination Date') . ': ' . TTDate::getDate('DATE', $user_obj->getTerminationDate()), $border, 1, 'R', false, '', 1);
                }
                if ($user_obj->getSIN() != '') {
                    $block_adjust_y = ($block_adjust_y + 3);
                    $pdf->setXY(Misc::AdjustXY(75, $adjust_x), Misc::AdjustXY($block_adjust_y, $adjust_y));
                    $pdf->Cell(100, 4, TTi18n::gettext('SIN / SSN') . ': ' . $user_obj->getSecureSIN(), $border, 1, 'R', false, '', 1);
                }


                if ($pay_stub_obj->getTainted() == true) {
                    $tainted_flag = 'T';
                } else {
                    $tainted_flag = '';
                }

                $block_adjust_y = 215;
                $pdf->setXY(Misc::AdjustXY(75, $adjust_x), Misc::AdjustXY(($block_adjust_y + 27.5), $adjust_y));
                $pdf->Cell(100, 4, TTi18n::gettext('Payroll Run #') . ': ' . str_pad($pay_stub_obj->getRun(), 2, 0, STR_PAD_LEFT), $border, 1, 'R', false, '', 1);

                $pdf->SetFont('', '', 8);
                $pdf->setXY(Misc::AdjustXY(125, $adjust_x), Misc::AdjustXY(($block_adjust_y + 30), $adjust_y));
                $pdf->Cell(50, 5, TTi18n::gettext('Identification #') . ': ' . $pay_stub_obj->getDisplayID() . $tainted_flag, $border, 1, 'R', false, '', 1);
                unset($net_pay_amount, $tainted_flag);

                //Line
                $pdf->setLineWidth(1);
                $pdf->Line(Misc::AdjustXY(0, $adjust_x), Misc::AdjustXY(($block_adjust_y + 35), $adjust_y), Misc::AdjustXY(185, $adjust_y), Misc::AdjustXY(($block_adjust_y + 35), $adjust_y));
                $pdf->setLineWidth(0);

                $pdf->SetFont('', '', 6);
                $pdf->setXY(Misc::AdjustXY(0, $adjust_x), Misc::AdjustXY(($block_adjust_y + 38), $adjust_y));
                $pdf->Cell(175, 1, TTi18n::getText('Pay Stub Generated by') . ' ' . APPLICATION_NAME . ' @ ' . TTDate::getDate('DATE+TIME', $pay_stub_obj->getCreatedDate()), $border, 0, 'C', false, '', 1);

                unset($pay_stub_entries, $pay_period_number);

                $this->getProgressBarObject()->set(null, $pslf->getCurrentRow());

                $i++;
            }

            Debug::Text('Generating PDF...', __FILE__, __LINE__, __METHOD__, 10);
            $output = $pdf->Output('', 'S');
        }

        TTi18n::setMasterLocale();

        if (isset($output)) {
            return $output;
        }

        return false;
    }

    public function addLog($log_action)
    {
        return TTLog::addEntry($this->getId(), $log_action, TTi18n::getText('Pay Stub') . ' - ' . TTi18n::getText('Employee') . ': ' . $this->getUserObject()->getFullName() . ' ' . TTi18n::getText('Status') . ': ' . Option::getByKey($this->getStatus(), $this->getOptions('status')) . ' ' . TTi18n::getText('Start') . ': ' . TTDate::getDate('DATE', $this->getStartDate()) . ' ' . TTi18n::getText('End') . ': ' . TTDate::getDate('DATE', $this->getEndDate()) . ' ' . TTi18n::getText('Transaction') . ': ' . TTDate::getDate('DATE', $this->getTransactionDate()), null, $this->getTable(), $this);
    }
}
