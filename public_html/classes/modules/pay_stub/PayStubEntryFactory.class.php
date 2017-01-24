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
 * @package Modules\PayStub
 */
class PayStubEntryFactory extends Factory
{
    protected $table = 'pay_stub_entry';
    protected $pk_sequence_name = 'pay_stub_entry_id_seq'; //PK Sequence name

    protected $pay_stub_entry_account_obj = null;
    protected $pay_stub_obj = null;
    protected $ps_amendment_obj = null;

    public function _getVariableToFunctionMap($data)
    {
        $variable_function_map = array(
            'id' => 'ID',
            'type_id' => false,
            'name' => false,
            'pay_stub_id' => 'PayStub',
            'rate' => 'Rate',
            'units' => 'Units',
            'ytd_units' => 'YTDUnits',
            'amount' => 'Amount',
            'ytd_amount' => 'YTDAmount',
            'description' => 'Description',
            'pay_stub_entry_name_id' => 'PayStubEntryNameId',
            'pay_stub_amendment_id' => 'PayStubAmendment',
            'user_expense_id' => 'UserExpense',

            'deleted' => 'Deleted',
        );
        return $variable_function_map;
    }

    public function getPayStubAmendmentObject()
    {
        return $this->getGenericObject('PayStubAmendmentListFactory', $this->getPayStubAmendment(), 'ps_amendment_obj');
    }

    public function getPayStubAmendment()
    {
        if (isset($this->data['pay_stub_amendment_id'])) {
            return (int)$this->data['pay_stub_amendment_id'];
        }

        return false;
    }

    public function setPayStub($id)
    {
        $id = trim($id);

        $pslf = TTnew('PayStubListFactory');

        if ($this->Validator->isResultSetWithRows('pay_stub',
            $pslf->getByID($id),
            TTi18n::gettext('Invalid Pay Stub')
        )
        ) {
            $this->data['pay_stub_id'] = $id;

            return true;
        }

        return false;
    }

    public function setPayStubEntryNameId($id)
    {
        $id = trim($id);

        Debug::text('Entry Account ID: ' . $id, __FILE__, __LINE__, __METHOD__, 10);

        $psealf = TTnew('PayStubEntryAccountListFactory');
        if ($this->Validator->isResultSetWithRows('pay_stub_entry_name_id',
            $psealf->getById($id),
            TTi18n::gettext('Invalid Entry Account Id')
        )
        ) {
            $this->data['pay_stub_entry_name_id'] = $id;

            return true;
        }

        return false;
    }

    public function setPayStubAmendment($id, $start_date = false, $end_date = false)
    {
        $id = (int)$id;

        if ($id != 0) {
            if ($start_date == '' and $end_date == '') {
                $pay_stub_obj = $this->getPayStubObject();
                if (is_object($pay_stub_obj)) {
                    $start_date = $pay_stub_obj->getStartDate();
                    $end_date = $pay_stub_obj->getEndDate();
                } else {
                    return false;
                }
                unset($pay_stub_obj);
            }
        }

        Debug::text('PS Amendment ID: ' . $id, __FILE__, __LINE__, __METHOD__, 10);

        $psalf = TTnew('PayStubAmendmentListFactory');
        if ($id == 0
            or $this->Validator->isResultSetWithRows('pay_stub_amendment_id',
                $psalf->getByIdAndStartDateAndEndDate($id, $start_date, $end_date),
                TTi18n::gettext('Pay Stub Amendment effective date is after employees pay stub end date or termination date')
            )
        ) {
            $this->data['pay_stub_amendment_id'] = $id;

            return true;
        }

        return false;
    }

    public function getPayStubObject()
    {
        return $this->getGenericObject('PayStubListFactory', $this->getPayStub(), 'pay_stub_obj');
    }

    public function getPayStub()
    {
        if (isset($this->data['pay_stub_id'])) {
            return (int)$this->data['pay_stub_id'];
        }

        return false;
    }

    public function getUserExpense()
    {
        if (isset($this->data['user_expense_id'])) {
            return (int)$this->data['user_expense_id'];
        }

        return false;
    }

    public function setUserExpense($id)
    {
        $this->data['user_expense_id'] = 0;
        return true;
    }

    public function getRate()
    {
        if (isset($this->data['rate'])) {
            return $this->data['rate'];
        }

        return null;
    }

    public function setRate($value)
    {
        $value = trim($value);

        if ($value == null or $value == '') {
            return false;
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
            )
        ) {
            //Must round to 2 decimals otherwise discreptancy can occur when generating pay stubs.
            //$this->data['rate'] = Misc::MoneyFormat( $value, FALSE );
            $this->data['rate'] = $value;

            return true;
        }

        return false;
    }

    public function getUnits()
    {
        if (isset($this->data['units'])) {
            return $this->data['units'];
        }

        return null;
    }

    public function setUnits($value)
    {
        $value = trim($value);

        if ($value == null or $value == '') {
            return false;
        }

        Debug::text('Rate: ' . $value, __FILE__, __LINE__, __METHOD__, 10);

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
            )
        ) {
            //Must round to 2 decimals otherwise discreptancy can occur when generating pay stubs.
            //$this->data['units'] = Misc::MoneyFormat( $value, FALSE );
            $this->data['units'] = $value;

            return true;
        }

        return false;
    }

    public function setEnableCalculateYTD($bool)
    {
        $this->enable_calc_ytd = $bool;

        return true;
    }

    public function getEnableCalculateAccrualBalance()
    {
        if (isset($this->enable_calc_accrual_balance)) {
            return $this->enable_calc_accrual_balance;
        }

        return false;
    }

    public function setEnableCalculateAccrualBalance($bool)
    {
        $this->enable_calc_accrual_balance = $bool;

        return true;
    }

    public function setAmount($value)
    {
        $value = trim($value);

        //PHP v5.3.5 has a bug that it converts large values with 0's on the end into scientific notation.
        Debug::text('Amount: ' . $value . ' Name: ' . $this->getPayStubEntryNameId(), __FILE__, __LINE__, __METHOD__, 10);

        //if ($value == NULL OR $value == '' OR $value < 0) {
        //Allow negative values for things like minusing vacation accural?
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
        ) {
            //$this->data['amount'] = Misc::MoneyFormat( $value, FALSE );
            $this->data['amount'] = (is_object($this->getPayStubObject()) and is_object($this->getPayStubObject()->getCurrencyObject())) ? $this->getPayStubObject()->getCurrencyObject()->round($value) : Misc::MoneyFormat($value, false);

            return true;
        }

        return false;
    }

    public function getPayStubEntryNameId()
    {
        if (isset($this->data['pay_stub_entry_name_id'])) {
            return (int)$this->data['pay_stub_entry_name_id'];
        }

        return false;
    }

    public function getDescription()
    {
        return $this->data['description'];
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

    public function Validate($ignore_warning = true)
    {
        //Calc YTD values if they aren't already done.
        if ($this->getYTDAmount() == null or $this->getYTDUnits() == null) {
            $this->preSave();
        }

        //Make sure rate * units = amount

        if ($this->getAmount() === null) {
            //var_dump( $this->getAmount() );
            $this->Validator->isTrue('amount',
                false,
                TTi18n::gettext('Invalid Amount'));
        }

        if ($this->getPayStubEntryNameId() == '') {
            Debug::text('PayStubEntryNameID is NULL: ', __FILE__, __LINE__, __METHOD__, 10);
            $this->Validator->isTrue('pay_stub_entry_name_id',
                false,
                TTi18n::gettext('Invalid Entry Account Id'));
        }

        /*
        //Allow just units to be set. For cases like Gross Pay Units.
        //Make sure Units isn't set if Rate is
        if ( $this->getRate() != NULL AND $this->getUnits() == NULL ) {
            $this->Validator->isTrue(		'units',
                                            FALSE,
                                            TTi18n::gettext('Invalid Units'));
        }

        if ( $this->getUnits() != NULL AND $this->getRate() == NULL ) {
            $this->Validator->isTrue(		'rate',
                                            FALSE,
                                            TTi18n::gettext('Invalid Rate'));
        }
        */

        /*
        //FIXME: For some reason the calculation done here has one less decimal digit then
        //the calculation done in Wage::getOverTime2Wage().
        if ( $this->getRate() !== NULL AND $this->getUnits() !== NULL
                AND ( $this->getRate() * $this->getUnits() ) != $this->getAmount() ) {
            Debug::text('Validate: Rate: '. $this->getRate() .' Units: '. $this->getUnits() .' Amount: '. $this->getAmount() .' Calc: Rate: '. $this->getRate() .' Units: '. $this->getUnits() .' Total: '. ( $this->getRate() * $this->getUnits() ), __FILE__, __LINE__, __METHOD__, 10);
            $this->Validator->isTrue(		'amount',
                                            FALSE,
                                            TTi18n::gettext('Invalid Amount, calculation is incorrect.'));
        }
        */
        //Make sure YTD values are set
        //YTD could be 0 though if we "cancel" out a entry like vacation accrual.
        if ($this->getYTDAmount() === null) {
            Debug::text('getYTDAmount is NULL: ', __FILE__, __LINE__, __METHOD__, 10);
            //var_dump( $this );

            $this->Validator->isTrue('ytd_amount',
                false,
                TTi18n::gettext('Invalid YTD Amount'));
        }

        if ($this->getYTDUnits() === null) {
            $this->Validator->isTrue('ytd_units',
                false,
                TTi18n::gettext('Invalid YTD Units'));
        }

        return true;
    }

    public function getYTDAmount()
    {
        if (isset($this->data['ytd_amount'])) {
            return $this->data['ytd_amount'];
        }

        return null;
    }

    public function getYTDUnits()
    {
        if (isset($this->data['ytd_units'])) {
            return $this->data['ytd_units'];
        }

        return null;
    }

    public function preSave()
    {
        Debug::text('Pay Stub ID: ' . $this->getPayStub() . ' Calc YTD: ' . (int)$this->getEnableCalculateYTD(), __FILE__, __LINE__, __METHOD__, 10);

        if ($this->getYTDAmount() == false) {
            $this->setYTDAmount(0);
        }

        if ($this->getYTDUnits() == false) {
            $this->setYTDUnits(0);
        }

        /*
        if (	$this->getPayStub()
                AND
                (
                    ( $this->getYTDAmount() == FALSE AND $this->getYTDUnits() == FALSE )
                    OR
                    $this->getEnableCalculateYTD() == TRUE
                ) ) {
            Debug::text('Calculating YTD values...', __FILE__, __LINE__, __METHOD__, 10);

            //Calculate things like YTD values
            $pslf = TTnew( 'PayStubListFactory' );
            $ps = $pslf->getById( $this->getPayStub() )->getCurrent();

            $pself = TTnew( 'PayStubEntryListFactory' );

            //if ( $this->getPayStubEntryNameId() == 24 ) { //Vacation accural
            //Debug::text('aaCalculating YTD values...:	 for Vacation Accrual....', __FILE__, __LINE__, __METHOD__, 10);
            if ( $this->getPayStubEntryAccountObject() != FALSE
                    AND $this->getPayStubEntryAccountObject()->getType() == 50 ) {
                //Accurals don't re-start after year boundary.
                Debug::text('aaCalculating Balance (NOT YTD) values for Accrual....', __FILE__, __LINE__, __METHOD__, 10);
                $ytd_values = $pself->getAmountSumByUserIdAndEntryNameIdAndDate( $ps->getUser(), $this->getPayStubEntryNameId(), $ps->getPayPeriodObject()->getEndDate(), $this->getId() );
                //BUG: When re-calculating old pay stubs the balances don't
                //take in to account other entries of the same PSE account on the same pay stub.
                // 5.00	  5.00
                //-5.00	 -5.00
                //$ytd_values = $pself->getAmountSumByUserIdAndEntryNameIdAndDate( $ps->getUser(), $this->getPayStubEntryNameId(), $ps->getPayPeriodObject()->getEndDate(), 0 );
            } else {
                //$ytd_values = $pself->getYTDAmountSumByUserIdAndEntryNameIdAndYear( $ps->getUser(), $this->getPayStubEntryNameId(), $ps->getPayPeriodStartDate() );
                $ytd_values = $pself->getYTDAmountSumByUserIdAndEntryNameIdAndDate( $ps->getUser(), $this->getPayStubEntryNameId(), $ps->getPayPeriodObject()->getTransactionDate(), $this->getId() );
            }

            Debug::text('aCalculating YTD values...: Amount: '. $ytd_values['amount'] .' PS Entry Name ID: '. $this->getPayStubEntryNameId(), __FILE__, __LINE__, __METHOD__, 10);

            $this->setYTDAmount( $ytd_values['amount'] + $this->getAmount() );
            $this->setYTDUnits( $ytd_values['units'] + $this->getUnits() );

            Debug::text('bCalculating YTD values...: Amount: '. $this->getYTDAmount(), __FILE__, __LINE__, __METHOD__, 10);
        } else {
            Debug::text('NOT Calculating YTD values... YTD Amount: '. $this->getYTDAmount() .' YTD Units: '. $this->getYTDUnits(), __FILE__, __LINE__, __METHOD__, 10);
        }
        */

        return true;
    }

    public function getEnableCalculateYTD()
    {
        if (isset($this->enable_calc_ytd)) {
            return $this->enable_calc_ytd;
        }

        return false;
    }

    public function setYTDAmount($value)
    {
        $value = trim($value);

        if ($value == null or $value == '') {
            return false;
        }

        Debug::text('YTD Amount: ' . $value . ' Name: ' . $this->getPayStubEntryNameId(), __FILE__, __LINE__, __METHOD__, 10);

        if ($this->Validator->isFloat('ytd_amount',
            $value,
            TTi18n::gettext('Invalid YTD Amount')
        )
        ) {
            //$this->data['ytd_amount'] = Misc::MoneyFormat( $value, FALSE );
            $this->data['ytd_amount'] = (is_object($this->getPayStubObject()) and is_object($this->getPayStubObject()->getCurrencyObject())) ? $this->getPayStubObject()->getCurrencyObject()->round($value) : Misc::MoneyFormat($value, false);

            return true;
        }

        return false;
    }

    public function setYTDUnits($value)
    {
        $value = trim($value);

        if ($value == null or $value == '') {
            return false;
        }

        Debug::text('YTD Units: ' . $value . ' Name: ' . $this->getPayStubEntryNameId(), __FILE__, __LINE__, __METHOD__, 10);

        if ($this->Validator->isFloat('ytd_units',
            $value,
            TTi18n::gettext('Invalid YTD Units')
        )
        ) {
            $this->data['ytd_units'] = $value;

            return true;
        }

        return false;
    }

    public function getAmount()
    {
        if (isset($this->data['amount'])) {
            return $this->data['amount'];
        }

        return null;
    }

    public function postSave()
    {
        //If this entry is based off pay stub amendment, mark
        //PS amendment as "ACTIVE" status.
        //Once PS is paid, mark them as PAID.

        //If Pay Stub Account is attached to an accrual, handle that now.
        //Only calculate accrual if this is a new pay stub entry, not if we're
        //editing one, so we don't duplicate the accrual entry.
        //
        // **Handle this in PayStubFactory instead.
        //
        /*
        //This all handled in PayStubFactory::addEntry() now.
        if ( $this->getEnableCalculateAccrualBalance() == TRUE
                AND $this->getPayStubEntryAccountObject() != FALSE
                AND $this->getPayStubEntryAccountObject()->getAccrual() != FALSE
                AND $this->getPayStubEntryAccountObject()->getAccrual() != 0
                ) {
            Debug::text('Pay Stub Account is linked to an accrual...', __FILE__, __LINE__, __METHOD__, 10);

            if ( $this->getPayStubEntryAccountObject()->getType() == 10 ) {
                $amount = $this->getAmount()*-1; //This is an earning... Reduce accrual
            } elseif ( $this->getPayStubEntryAccountObject()->getType() == 20 ) {
                $amount = $this->getAmount(); //This is a employee deduction, add to accrual.
            }
            Debug::text('Amount: '. $amount, __FILE__, __LINE__, __METHOD__, 10);

            if ( $amount != 0 ) {
                //Add entry to do the opposite to the accrual.
                $psef = TTnew( 'PayStubEntryFactory' );
                $psef->setPayStub( $this->getPayStub() );
                $psef->setPayStubEntryNameId( $this->getPayStubEntryAccountObject()->getAccrual() );
                $psef->setAmount( $amount );

                return $psef->Save();
            }
        } else {
            Debug::text('Pay Stub Account is NOT linked to an accrual...', __FILE__, __LINE__, __METHOD__, 10);
        }
        */

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
        $variable_function_map = $this->getVariableToFunctionMap();
        $data = array();
        if (is_array($variable_function_map)) {
            foreach ($variable_function_map as $variable => $function_stub) {
                if ($include_columns == null or (isset($include_columns[$variable]) and $include_columns[$variable] == true)) {
                    $function = 'get' . $function_stub;
                    switch ($variable) {
                        case 'type_id':
                        case 'name':
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
            $this->getCreatedAndUpdatedColumns($data, $include_columns);
        }

        return $data;
    }

    public function addLog($log_action)
    {
        return TTLog::addEntry($this->getPayStub(), $log_action, TTi18n::getText('Pay Stub Entry') . ': ' . $this->getPayStubEntryAccountObject()->getName() . ': ' . TTi18n::getText('Amount') . ': ' . $this->getAmount(), null, $this->getTable(), $this);
    }

    public function getPayStubEntryAccountObject()
    {
        return $this->getGenericObject('PayStubEntryAccountListFactory', $this->getPayStubEntryNameID(), 'pay_stub_entry_account_obj');
    }
}
