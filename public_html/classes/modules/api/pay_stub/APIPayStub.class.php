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
 * @package API\PayStub
 */
class APIPayStub extends APIFactory
{
    protected $main_class = 'PayStubFactory';

    public function __construct()
    {
        parent::__construct(); //Make sure parent constructor is always called.

        return true;
    }

    /**
     * overridden to get different columns based on permissions.
     * @param bool $name
     * @param null $parent
     * @return object
     */
    public function getOptions($name = false, $parent = null)
    {
        if ($name == 'columns'
            and (!$this->getPermissionObject()->Check('pay_stub', 'enabled')
                or !($this->getPermissionObject()->Check('pay_stub', 'view') or $this->getPermissionObject()->Check('pay_stub', 'view_child')))
        ) {
            $name = 'list_columns';
        }

        return parent::getOptions($name, $parent);
    }

    /**
     * Get default paystub_entry_account data for creating new paystub_entry_accountes.
     * @return array
     */
    public function getPayStubDefaultData()
    {
        $company_obj = $this->getCurrentCompanyObject();
        $user_obj = $this->getCurrentUserObject();

        Debug::Text('Getting pay stub entry default data...', __FILE__, __LINE__, __METHOD__, 10);

        //Get earliest OPEN pay period.
        $pplf = TTNew('PayPeriodListFactory');
        $pplf->getLastPayPeriodByCompanyIdAndPayPeriodScheduleIdAndDate($company_obj->getId(), null, time());
        if ($pplf->getRecordCount() > 0) {
            $pp_obj = $pplf->getCurrent();

            $pay_period_id = $pp_obj->getId();
            $start_date = TTDate::getDate('DATE', $pp_obj->getStartDate());
            $end_date = TTDate::getDate('DATE', $pp_obj->getEndDate());
            $transaction_date = TTDate::getDate('DATE', $pp_obj->getTransactionDate());
        } else {
            $pay_period_id = 0;
            $start_date = TTDate::getDate('DATE', time());
            $end_date = TTDate::getDate('DATE', time());
            $transaction_date = TTDate::getDate('DATE', time());
        }

        $run_id = $this->stripReturnHandler($this->getCurrentPayRun($pay_period_id));

        $data = array(
            'company_id' => $company_obj->getId(),
            'user_id' => $user_obj->getId(),
            'currency_id' => $user_obj->getCurrency(),
            'pay_period_id' => $pay_period_id,
            'run_id' => $run_id,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'transaction_date' => $transaction_date,
        );

        return $this->returnHandler($data);
    }

    public function getCurrentPayRun($pay_period_ids)
    {
        $retval = 1;
        if (is_array($pay_period_ids) and count($pay_period_ids) > 0) {
            $retval = PayStubListFactory::getCurrentPayRun($this->getCurrentCompanyObject()->getId(), $pay_period_ids);
        }

        Debug::Text('  Current Run ID: ' . $retval, __FILE__, __LINE__, __METHOD__, 10);
        return $retval;
    }

    /**
     * Export data to csv
     * @param array $data filter data
     * @param string $format file format (csv)
     * @return array
     */
    public function exportPayStub($format = 'csv', $data = null, $disable_paging = true)
    {
        $result = $this->stripReturnHandler($this->getPayStub($data, $disable_paging));
        return $this->exportRecords($format, 'export_pay_stub', $result, ((isset($data['filter_columns'])) ? $data['filter_columns'] : null));
    }

    /**
     * Get pay_stub data for one or more pay_stubes.
     * @param array $data filter data
     * @return array
     */
    public function getPayStub($data = null, $disable_paging = false, $format = false, $hide_employer_rows = true)
    {
        if (!$this->getPermissionObject()->Check('pay_stub', 'enabled')
            or !($this->getPermissionObject()->Check('pay_stub', 'view') or $this->getPermissionObject()->Check('pay_stub', 'view_own') or $this->getPermissionObject()->Check('pay_stub', 'view_child'))
        ) {
            return $this->getPermissionObject()->PermissionDenied();
        }
        $data = $this->initializeFilterAndPager($data, $disable_paging);

        $format = Misc::trimSortPrefix($format);

        $data['filter_data']['permission_children_ids'] = $this->getPermissionObject()->getPermissionChildren('pay_stub', 'view');

        if ($this->getPermissionObject()->Check('pay_stub', 'view') == false and $this->getPermissionObject()->Check('pay_stub', 'view_child') == false) {
            //Only display PAID pay stubs.
            $data['filter_data']['status_id'] = array(40);
        }

        //Always hide employer rows unless they have permissions to view all pay stubs.
        if ($this->getPermissionObject()->Check('pay_stub', 'view') == false) {
            $hide_employer_rows = true;
        }

        $pslf = TTnew('PayStubListFactory');
        $pslf->getAPISearchByCompanyIdAndArrayCriteria($this->getCurrentCompanyObject()->getId(), $data['filter_data'], $data['filter_items_per_page'], $data['filter_page'], null, $data['filter_sort']);
        Debug::Text('Record Count: ' . $pslf->getRecordCount() . ' Format: ' . $format, __FILE__, __LINE__, __METHOD__, 10);

        if (strtolower($format) == 'pdf') {
            if ($pslf->getRecordCount() > 0) {
                $this->getProgressBarObject()->setDefaultKey($this->getAMFMessageID());
                $this->getProgressBarObject()->start($this->getAMFMessageID(), $pslf->getRecordCount());
                $pslf->setProgressBarObject($this->getProgressBarObject()); //Expose progress bar object to pay stub object.

                $output = $pslf->getPayStub($pslf, (bool)$hide_employer_rows);

                $this->getProgressBarObject()->stop($this->getAMFMessageID());

                if ($output != '') {
                    return Misc::APIFileDownload('pay_stub.pdf', 'application/pdf', $output);
                } else {
                    return $this->returnHandler(false, 'VALIDATION', TTi18n::getText('ERROR: No data to export...'));
                }
            }
        } elseif (strpos(strtolower($format), 'cheque_') !== false and $this->getPermissionObject()->Check('pay_stub', 'view') == true) {
            if ($pslf->getRecordCount() > 0) {
                $this->getProgressBarObject()->setDefaultKey($this->getAMFMessageID());
                $this->getProgressBarObject()->start($this->getAMFMessageID(), $pslf->getRecordCount());
                $pslf->setProgressBarObject($this->getProgressBarObject()); //Expose progress bar object to pay stub object.

                $output = $pslf->exportPayStub($pslf, strtolower($format));

                $this->getProgressBarObject()->stop($this->getAMFMessageID());

                if ($output != '') {
                    return Misc::APIFileDownload('checks_' . str_replace(array('/', ',', ' '), '_', TTDate::getDate('DATE', time())) . '.pdf', 'application/pdf', $output);
                } else {
                    return $this->returnHandler(false, 'VALIDATION', TTi18n::getText('ERROR: No data to export...'));
                }
            }
        } elseif (strpos(strtolower($format), 'eft_') !== false and $this->getPermissionObject()->Check('pay_stub', 'view') == true) {
            if ($pslf->getRecordCount() > 0) {
                $this->getProgressBarObject()->setDefaultKey($this->getAMFMessageID());
                $this->getProgressBarObject()->start($this->getAMFMessageID(), $pslf->getRecordCount());
                $pslf->setProgressBarObject($this->getProgressBarObject()); //Expose progress bar object to pay stub object.

                $output = $pslf->exportPayStub($pslf, strtolower($format));

                $this->getProgressBarObject()->stop($this->getAMFMessageID());

                if ($output != '') {
                    //Include file creation number in the exported file name, so the user knows what it is without opening the file,
                    //and can generate multiple files if they need to match a specific number.
                    $ugdlf = TTnew('UserGenericDataListFactory');
                    $ugdlf->getByCompanyIdAndScriptAndDefault($this->getCurrentCompanyObject()->getId(), 'PayStubFactory', true);
                    if ($ugdlf->getRecordCount() > 0) {
                        $ugd_obj = $ugdlf->getCurrent();
                        $setup_data = $ugd_obj->getData();
                    }

                    if (isset($setup_data)) {
                        $file_creation_number = $setup_data['file_creation_number']++;
                    } else {
                        $file_creation_number = 0;
                    }

                    return Misc::APIFileDownload('eft_' . $file_creation_number . '_' . date('Y_m_d') . '.txt', 'application/pdf', $output);
                } else {
                    return $this->returnHandler(false, 'VALIDATION', TTi18n::getText('ERROR: No data to export...'));
                }
            }
        } else {
            if ($pslf->getRecordCount() > 0) {
                $this->getProgressBarObject()->start($this->getAMFMessageID(), $pslf->getRecordCount());

                $this->setPagerObject($pslf);

                foreach ($pslf as $ps_obj) {
                    $retarr[] = $ps_obj->getObjectAsArray($data['filter_columns'], $data['filter_data']['permission_children_ids']);

                    $this->getProgressBarObject()->set($this->getAMFMessageID(), $pslf->getCurrentRow());
                }

                $this->getProgressBarObject()->stop($this->getAMFMessageID());

                return $this->returnHandler($retarr);
            }

            return $this->returnHandler(true); //No records returned.
        }
    }

    /**
     * Get only the fields that are common across all records in the search criteria. Used for Mass Editing of records.
     * @param array $data filter data
     * @return array
     */
    public function getCommonPayStubData($data)
    {
        return Misc::arrayIntersectByRow($this->stripReturnHandler($this->getPayStub($data, true)));
    }

    /**
     * Validate pay_stub data for one or more pay_stubes.
     * @param array $data pay_stub data
     * @return array
     */
    public function validatePayStub($data)
    {
        return $this->setPayStub($data, true);
    }

    /**
     * Set pay_stub data for one or more pay_stubes.
     * @param array $data pay_stub data
     * @return array
     */
    public function setPayStub($data, $validate_only = false, $ignore_warning = true)
    {
        $validate_only = (bool)$validate_only;
        $ignore_warning = (bool)$ignore_warning;

        if (!is_array($data)) {
            return $this->returnHandler(false);
        }

        if (!$this->getPermissionObject()->Check('pay_stub', 'enabled')
            or !($this->getPermissionObject()->Check('pay_stub', 'edit') or $this->getPermissionObject()->Check('pay_stub', 'edit_own') or $this->getPermissionObject()->Check('pay_stub', 'edit_child') or $this->getPermissionObject()->Check('pay_stub', 'add'))
        ) {
            return $this->getPermissionObject()->PermissionDenied();
        }

        if ($validate_only == true) {
            Debug::Text('Validating Only!', __FILE__, __LINE__, __METHOD__, 10);
        }

        extract($this->convertToMultipleRecords($data));
        Debug::Text('Received data for: ' . $total_records . ' PayStubs', __FILE__, __LINE__, __METHOD__, 10);
        Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

        $validator_stats = array('total_records' => $total_records, 'valid_records' => 0);
        $validator = $save_result = false;
        if (is_array($data) and $total_records > 0) {
            $this->getProgressBarObject()->start($this->getAMFMessageID(), $total_records);

            foreach ($data as $key => $row) {
                $primary_validator = new Validator();
                $lf = TTnew('PayStubListFactory');
                $lf->StartTransaction();
                if (isset($row['id']) and $row['id'] > 0) {
                    //Modifying existing object.
                    //Get pay_stub object, so we can only modify just changed data for specific records if needed.
                    $lf->getByIdAndCompanyId($row['id'], $this->getCurrentCompanyObject()->getId());
                    if ($lf->getRecordCount() == 1) {
                        //Object exists, check edit permissions
                        if (
                            $validate_only == true
                            or
                            (
                                $this->getPermissionObject()->Check('pay_stub', 'edit')
                                or ($this->getPermissionObject()->Check('pay_stub', 'edit_own') and $this->getPermissionObject()->isOwner($lf->getCurrent()->getCreatedBy(), $lf->getCurrent()->getUser()) === true)
                            )
                        ) {
                            Debug::Text('Row Exists, getting current data: ', $row['id'], __FILE__, __LINE__, __METHOD__, 10);
                            $lf = $lf->getCurrent();

                            //Check to see if the transaction date changed, so we can trigger setEnableCalcYTD().
                            $lf_arr = $lf->getObjectAsArray();
                            if (isset($lf_arr['transaction_date']) and isset($row['transaction_date'])
                                and $lf_arr['transaction_date'] != $row['transaction_date']
                                and TTDate::getYear(TTDate::parseDateTime($lf_arr['transaction_date'])) != TTDate::getYear(TTDate::parseDateTime($row['transaction_date']))
                            ) {
                                Debug::Text('Transaction date changed to a different year, recalculate YTD amounts... Prev: ' . $lf_arr['transaction_date'] . ' New: ' . $row['transaction_date'], __FILE__, __LINE__, __METHOD__, 10);
                                $set_enable_calc_ytd = true;
                            }
                            $row = array_merge($lf_arr, $row);
                            unset($lf_arr);
                        } else {
                            $primary_validator->isTrue('permission', false, TTi18n::gettext('Edit permission denied'));
                        }
                    } else {
                        //Object doesn't exist.
                        $primary_validator->isTrue('id', false, TTi18n::gettext('Edit permission denied, record does not exist'));
                    }
                } else {
                    //Adding new object, check ADD permissions.
                    $primary_validator->isTrue('permission', $this->getPermissionObject()->Check('pay_stub', 'add'), TTi18n::gettext('Add permission denied'));

                    //Because this class has sub-classes that depend on it, when adding a new record we need to make sure the ID is set first,
                    //so the sub-classes can depend on it. We also need to call Save( TRUE, TRUE ) to force a lookup on isNew()
                    $row['id'] = $lf->getNextInsertId();
                }
                Debug::Arr($row, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

                $is_valid = $primary_validator->isValid($ignore_warning);
                if ($is_valid == true) { //Check to see if all permission checks passed before trying to save data.
                    Debug::Text('Setting object data...', __FILE__, __LINE__, __METHOD__, 10);

                    $lf->setObjectFromArray($row);

                    //If the user is changing the Transaction Date between years, make sure we always recalc the current pay stub YTD amount.
                    // ie: Changing it from Dec 31st to January 1st, or vice versa makes the YTD amount reset.
                    // This must go above processEntries() so it can be disabled by it if needed.
                    if (isset($set_enable_calc_ytd) and $set_enable_calc_ytd == true) {
                        $lf->setEnableCalcCurrentYTD(true);
                        $lf->setEnableCalcYTD(true);
                    }

                    if ((isset($row['entries']) and is_array($row['entries']) and count($row['entries']) > 0)) {
                        Debug::Text(' Found modified entries!', __FILE__, __LINE__, __METHOD__, 10);

                        //Load previous pay stub
                        $lf->loadPreviousPayStub();

                        //Delete all entries, so they can be re-added.
                        //$lf->deleteEntries( TRUE );

                        //When editing pay stubs we can't re-process linked accruals.
                        $lf->setEnableLinkedAccruals(false);

                        $processed_entries = 0;
                        foreach ($row['entries'] as $pay_stub_entry) {
                            if ((
                                    (isset($pay_stub_entry['id']) and $pay_stub_entry['id'] > 0)
                                    or
                                    (isset($pay_stub_entry['pay_stub_entry_name_id']) and $pay_stub_entry['pay_stub_entry_name_id'] > 0)
                                )
                                and
                                (
                                    !isset($pay_stub_entry['type'])
                                    or
                                    (isset($pay_stub_entry['type']) and $pay_stub_entry['type'] != 40)
                                )
                                and isset($pay_stub_entry['amount'])
                            ) {
                                Debug::Text('Pay Stub Entry ID: ' . $pay_stub_entry['id'] . ' Amount: ' . $pay_stub_entry['amount'] . ' Pay Stub ID: ' . $row['id'], __FILE__, __LINE__, __METHOD__, 10);

                                //Populate $pay_stub_entry_obj so we can find validation errors before postSave() is called.
                                if ($pay_stub_entry['id'] > 0) {
                                    $pself = TTnew('PayStubEntryListFactory');
                                    $pself->getById($pay_stub_entry['id']);
                                    if ($pself->getRecordCount() > 0) {
                                        $pay_stub_entry_obj = $pself->getCurrent();
                                    }
                                }

                                if (!isset($pay_stub_entry_obj)) {
                                    $pay_stub_entry_obj = TTnew('PayStubEntryListFactory');
                                    //$pay_stub_entry_obj->setPayStub( $row['id'] ); //When creating a new pay stub, we don't have the ID, so this just causes an error anyways.
                                    $pay_stub_entry_obj->setPayStubEntryNameId($pay_stub_entry['pay_stub_entry_name_id']);

                                    if (isset($pay_stub_entry['pay_stub_amendment_id']) and $pay_stub_entry['pay_stub_amendment_id'] != '') {
                                        $pay_stub_entry_obj->setPayStubAmendment($pay_stub_entry['pay_stub_amendment_id'], $lf->getStartDate(), $lf->getEndDate());
                                    }

                                    if (isset($pay_stub_entry['rate']) and $pay_stub_entry['rate'] != '') {
                                        $pay_stub_entry_obj->setRate($pay_stub_entry['rate']);
                                    }
                                    if (isset($pay_stub_entry['units']) and $pay_stub_entry['units'] != '') {
                                        $pay_stub_entry_obj->setUnits($pay_stub_entry['units']);
                                    }

                                    if (isset($pay_stub_entry['amount']) and $pay_stub_entry['amount'] != '') {
                                        $pay_stub_entry_obj->setAmount($pay_stub_entry['amount']);
                                    }
                                }

                                if (!isset($pay_stub_entry['units']) or $pay_stub_entry['units'] == '') {
                                    $pay_stub_entry['units'] = 0;
                                }
                                if (!isset($pay_stub_entry['rate']) or $pay_stub_entry['rate'] == '') {
                                    $pay_stub_entry['rate'] = 0;
                                }
                                if (!isset($pay_stub_entry['description']) or $pay_stub_entry['description'] == '') {
                                    $pay_stub_entry['description'] = null;
                                }
                                if (!isset($pay_stub_entry['pay_stub_amendment_id']) or $pay_stub_entry['pay_stub_amendment_id'] == '') {
                                    $pay_stub_entry['pay_stub_amendment_id'] = null;
                                }
                                if (!isset($pay_stub_entry['user_expense_id']) or $pay_stub_entry['user_expense_id'] == '') {
                                    $pay_stub_entry['user_expense_id'] = null;
                                }

                                $ytd_adjustment = false;
                                if ($pay_stub_entry['pay_stub_amendment_id'] > 0) {
                                    $psamlf = TTNew('PayStubAmendmentListFactory');
                                    $psamlf->getByIdAndCompanyId((int)$pay_stub_entry['pay_stub_amendment_id'], $this->getCurrentCompanyObject()->getId());
                                    if ($psamlf->getRecordCount() > 0) {
                                        $ytd_adjustment = $psamlf->getCurrent()->getYTDAdjustment();
                                    }
                                    Debug::Text(' Pay Stub Amendment Id: ' . $pay_stub_entry['pay_stub_amendment_id'] . ' YTD Adjusment: ' . (int)$ytd_adjustment, __FILE__, __LINE__, __METHOD__, 10);
                                }

                                if ($pay_stub_entry_obj->isValid() == true) {
                                    $lf->addEntry($pay_stub_entry['pay_stub_entry_name_id'], $pay_stub_entry['amount'], $pay_stub_entry['units'], $pay_stub_entry['rate'], $pay_stub_entry['description'], $pay_stub_entry['pay_stub_amendment_id'], null, null, $ytd_adjustment);
                                    $processed_entries++;
                                } else {
                                    Debug::Text(' ERROR: Unable to save PayStubEntry... ', __FILE__, __LINE__, __METHOD__, 10);
                                    $lf->Validator->isTrue('pay_stub_entry', false, TTi18n::getText('%1 entry for amount: %2 is invalid', array($pay_stub_entry_obj->getPayStubEntryAccountObject()->getName(), Misc::MoneyFormat($pay_stub_entry['amount']))));
                                }
                            } else {
                                Debug::Text(' Skipping Total Entry. ', __FILE__, __LINE__, __METHOD__, 10);
                            }
                            unset($pay_stub_entry_obj);
                        }
                        unset($pay_stub_entry_id, $pay_stub_entry);

                        if ($processed_entries > 0) {
                            $lf->setTainted(true); //Make sure tainted flag is set when any entries are processed.
                            $lf->setEnableCalcYTD(true);
                            $lf->setEnableProcessEntries(true);
                            $lf->processEntries();
                        }
                    } else {
                        Debug::Text(' Skipping ALL Entries... ', __FILE__, __LINE__, __METHOD__, 10);
                    }

                    $lf->Validator->setValidateOnly($validate_only);

                    $is_valid = $lf->isValid($ignore_warning);
                    if ($is_valid == true) {
                        Debug::Text('Saving data...', __FILE__, __LINE__, __METHOD__, 10);
                        if ($validate_only == true) {
                            $save_result[$key] = true;
                        } else {
                            $save_result[$key] = $lf->Save(true, true);
                        }
                        $validator_stats['valid_records']++;
                    }
                }

                if ($is_valid == false) {
                    Debug::Text('Data is Invalid...', __FILE__, __LINE__, __METHOD__, 10);

                    $lf->FailTransaction(); //Just rollback this single record, continue on to the rest.

                    $validator[$key] = $this->setValidationArray($primary_validator, $lf);
                } elseif ($validate_only == true) {
                    $lf->FailTransaction();
                }

                $lf->CommitTransaction();

                $this->getProgressBarObject()->set($this->getAMFMessageID(), $key);
            }

            $this->getProgressBarObject()->stop($this->getAMFMessageID());

            return $this->handleRecordValidationResults($validator, $validator_stats, $key, $save_result);
        }

        return $this->returnHandler(false);
    }

    /**
     * Delete one or more pay_stubs.
     * @param array $data pay_stub data
     * @return array
     */
    public function deletePayStub($data)
    {
        if (is_numeric($data)) {
            $data = array($data);
        }

        if (!is_array($data)) {
            return $this->returnHandler(false);
        }

        if (!$this->getPermissionObject()->Check('pay_stub', 'enabled')
            or !($this->getPermissionObject()->Check('pay_stub', 'delete') or $this->getPermissionObject()->Check('pay_stub', 'delete_own') or $this->getPermissionObject()->Check('pay_stub', 'delete_child'))
        ) {
            return $this->getPermissionObject()->PermissionDenied();
        }

        Debug::Text('Received data for: ' . count($data) . ' PayStubs', __FILE__, __LINE__, __METHOD__, 10);
        Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

        $total_records = count($data);
        $validator = $save_result = false;
        $validator_stats = array('total_records' => $total_records, 'valid_records' => 0);
        if (is_array($data) and $total_records > 0) {
            $this->getProgressBarObject()->start($this->getAMFMessageID(), $total_records);

            foreach ($data as $key => $id) {
                $primary_validator = new Validator();
                $lf = TTnew('PayStubListFactory');
                $lf->StartTransaction();
                if (is_numeric($id)) {
                    //Modifying existing object.
                    //Get pay_stub object, so we can only modify just changed data for specific records if needed.
                    $lf->getByIdAndCompanyId($id, $this->getCurrentCompanyObject()->getId());
                    if ($lf->getRecordCount() == 1) {
                        //Object exists, check edit permissions
                        if ($this->getPermissionObject()->Check('pay_stub', 'delete')
                            or ($this->getPermissionObject()->Check('pay_stub', 'delete_own') and $this->getPermissionObject()->isOwner($lf->getCurrent()->getCreatedBy(), $lf->getCurrent()->getUser()) === true)
                        ) {
                            Debug::Text('Record Exists, deleting record: ', $id, __FILE__, __LINE__, __METHOD__, 10);
                            $lf = $lf->getCurrent();
                        } else {
                            $primary_validator->isTrue('permission', false, TTi18n::gettext('Delete permission denied'));
                        }
                    } else {
                        //Object doesn't exist.
                        $primary_validator->isTrue('id', false, TTi18n::gettext('Delete permission denied, record does not exist'));
                    }
                } else {
                    $primary_validator->isTrue('id', false, TTi18n::gettext('Delete permission denied, record does not exist'));
                }

                //Debug::Arr($lf, 'AData: ', __FILE__, __LINE__, __METHOD__, 10);

                $is_valid = $primary_validator->isValid();
                if ($is_valid == true) { //Check to see if all permission checks passed before trying to save data.
                    Debug::Text('Attempting to delete record...', __FILE__, __LINE__, __METHOD__, 10);
                    $lf->setDeleted(true);

                    $is_valid = $lf->isValid();
                    if ($is_valid == true) {
                        Debug::Text('Record Deleted...', __FILE__, __LINE__, __METHOD__, 10);
                        $save_result[$key] = $lf->Save();
                        $validator_stats['valid_records']++;
                    }
                }

                if ($is_valid == false) {
                    Debug::Text('Data is Invalid...', __FILE__, __LINE__, __METHOD__, 10);

                    $lf->FailTransaction(); //Just rollback this single record, continue on to the rest.

                    $validator[$key] = $this->setValidationArray($primary_validator, $lf);
                }

                $lf->CommitTransaction();

                $this->getProgressBarObject()->set($this->getAMFMessageID(), $key);
            }

            $this->getProgressBarObject()->stop($this->getAMFMessageID());

            return $this->handleRecordValidationResults($validator, $validator_stats, $key, $save_result);
        }

        return $this->returnHandler(false);
    }

    public function generatePayStubs($pay_period_ids, $user_ids = null, $enable_correction = false, $run_id = false, $type_id = 10, $transaction_date = null)
    {
        global $profiler;
        Debug::Text('Generate Pay Stubs!', __FILE__, __LINE__, __METHOD__, 10);

        if (!$this->getPermissionObject()->Check('pay_period_schedule', 'enabled')
            or !($this->getPermissionObject()->Check('pay_period_schedule', 'edit') or $this->getPermissionObject()->Check('pay_period_schedule', 'edit_own'))
        ) {
            return $this->getPermissionObject()->PermissionDenied();
        }

        if (!is_array($pay_period_ids)) {
            $pay_period_ids = array($pay_period_ids);
        }
        $pay_period_ids = array_unique($pay_period_ids);


        if ($user_ids !== null and !is_array($user_ids) and $user_ids > 0) {
            $user_ids = array($user_ids);
        } elseif (is_array($user_ids) and isset($user_ids[0]) and $user_ids[0] == 0) {
            $user_ids = null;
        }

        if (is_array($user_ids)) {
            $user_ids = array_unique($user_ids);
        }

        if ($type_id == 5) { //Post-Adjustment Carry-Forward, enable correction and force type to Normal.
            $enable_correction = true;
            $type_id = 10;
        }

        foreach ($pay_period_ids as $pay_period_id) {
            Debug::text('Pay Period ID: ' . $pay_period_id, __FILE__, __LINE__, __METHOD__, 10);

            $epoch = TTDate::getTime();

            $pplf = TTnew('PayPeriodListFactory');
            $pplf->getByIdAndCompanyId($pay_period_id, $this->getCurrentCompanyObject()->getId());
            foreach ($pplf as $pay_period_obj) {
                Debug::text('Pay Period Schedule ID: ' . $pay_period_obj->getPayPeriodSchedule(), __FILE__, __LINE__, __METHOD__, 10);
                if ($pay_period_obj->isPreviousPayPeriodClosed() == true) {
                    $pslf = TTnew('PayStubListFactory');

                    if ((int)$run_id == 0) {
                        $run_id = PayStubListFactory::getCurrentPayRun($this->getCurrentCompanyObject()->getId(), $pay_period_obj->getId());
                    }
                    Debug::text('  Using Run ID: ' . $run_id, __FILE__, __LINE__, __METHOD__, 10);

                    //Check to make sure pay stubs with a transaction date before today are not open, as that can cause the payroll run number to be incorrectly determined on its own.
                    $open_pay_stub_transaction_date = (TTDate::getMiddleDayEpoch(time()) >= TTDate::getMiddleDayEpoch($pay_period_obj->getTransactionDate())) ? $pay_period_obj->getTransactionDate() : TTDate::getBeginDayEpoch(time());
                    $pslf->getByCompanyIdAndPayPeriodIdAndStatusIdAndTransactionDateBeforeDate($this->getCurrentCompanyObject()->getId(), $pay_period_id, array(25), $open_pay_stub_transaction_date, 1);
                    if ($pslf->getRecordCount() > 0) {
                        UserGenericStatusFactory::queueGenericStatus(TTi18n::gettext('ERROR'), 10, TTi18n::gettext('Pay Stubs with a transaction date before today are still OPEN, all pay stubs must be PAID on or before their transaction date'), null);
                        continue;
                    }
                    unset($open_pay_stub_transaction_date);

                    if ($run_id > 1) { //Check to make sure prior payroll runs are marked as PAID.
                        $pslf->getByCompanyIdAndPayPeriodIdAndStatusIdAndNotRun($this->getCurrentCompanyObject()->getId(), $pay_period_obj->getId(), array(10, 20, 25, 30), $run_id, 1); //Only need to return 1 record.
                        if ($pslf->getRecordCount() > 0) {
                            $tmp_pay_stub_obj = $pslf->getCurrent();
                            Debug::text('Pay Stub ID: ' . $tmp_pay_stub_obj->getID() . ' Run: ' . $tmp_pay_stub_obj->getRun() . ' Transaction Date: ' . TTDate::getDate('DATE', $tmp_pay_stub_obj->getTransactionDate()), __FILE__, __LINE__, __METHOD__, 10);
                            UserGenericStatusFactory::queueGenericStatus(TTi18n::gettext('ERROR'), 10, TTi18n::gettext('Payroll Run #%1 of Pay Period %2 is still OPEN, all pay stubs must be PAID before starting a new payroll run.', array($tmp_pay_stub_obj->getRun(), TTDate::getDate('DATE', $pay_period_obj->getStartDate()) . ' -> ' . TTDate::getDate('DATE', $pay_period_obj->getEndDate()))), null);
                            unset($tmp_pay_stub_obj);
                            continue;
                        }
                    }
                    unset($pslf);

                    //Grab all users for pay period
                    $ppsulf = TTnew('PayPeriodScheduleUserListFactory');
                    if (is_array($user_ids) and count($user_ids) > 0 and !in_array(-1, $user_ids)) {
                        Debug::text('Generating pay stubs for specific users...', __FILE__, __LINE__, __METHOD__, 10);
                        TTLog::addEntry($this->getCurrentCompanyObject()->getId(), 500, TTi18n::gettext('Calculating Company Pay Stubs for Pay Period') . ': ' . $pay_period_id, $this->getCurrentUserObject()->getId(), 'pay_stub'); //Notice
                        $ppsulf->getByCompanyIDAndPayPeriodScheduleIdAndUserID($this->getCurrentCompanyObject()->getId(), $pay_period_obj->getPayPeriodSchedule(), $user_ids);
                    } else {
                        Debug::text('Generating pay stubs for all users...', __FILE__, __LINE__, __METHOD__, 10);
                        TTLog::addEntry($this->getCurrentCompanyObject()->getId(), 500, TTi18n::gettext('Calculating Employee Pay Stub for Pay Period') . ': ' . $pay_period_id, $this->getCurrentUserObject()->getId(), 'pay_stub');
                        $ppsulf->getByCompanyIDAndPayPeriodScheduleId($this->getCurrentCompanyObject()->getId(), $pay_period_obj->getPayPeriodSchedule());
                    }
                    $total_pay_stubs = $ppsulf->getRecordCount();

                    if ($total_pay_stubs > 0) {
                        $this->getProgressBarObject()->start($this->getAMFMessageID(), $total_pay_stubs, null, TTi18n::getText('Generating Paystubs...'));

                        //FIXME: If a pay stub already exists, it is deleted first, but then if the new pay stub fails to generate, the original one is
                        //  still deleted, so that can cause some people off guard if they don't fix the problem and re-generate the paystubs again.
                        //  This can be useful in some cases though, as the opposite problem may arise.

                        //Delete existing pay stub. Make sure we only
                        //delete pay stubs that are the same as what we're creating.
                        $pslf = TTnew('PayStubListFactory');
                        $pslf->getByCompanyIdAndPayPeriodIdAndRun($this->getCurrentCompanyObject()->getId(), $pay_period_obj->getId(), $run_id);
                        foreach ($pslf as $pay_stub_obj) {
                            if (is_array($user_ids) and count($user_ids) > 0 and !in_array(-1, $user_ids) and in_array($pay_stub_obj->getUser(), $user_ids) == false) {
                                continue; //Only generating pay stubs for individual employees, skip ones not in the list.
                            }
                            Debug::text('Existing Pay Stub: ' . $pay_stub_obj->getId(), __FILE__, __LINE__, __METHOD__, 10);

                            //Check PS End Date to match with PP End Date
                            //So if an ROE was generated, it won't get deleted when they generate all other Pay Stubs later on.
                            //Unless the ROE used the exact same dates as the pay period? To avoid this, only delete pay stubs for employees with no termination date, or with a termination date after the pay period start date.
                            if ($pay_stub_obj->getStatus() <= 25
                                and $pay_stub_obj->getTainted() === false
                                and TTDate::getMiddleDayEpoch($pay_stub_obj->getEndDate()) == TTDate::getMiddleDayEpoch($pay_period_obj->getEndDate())
                                and (is_object($pay_stub_obj->getUserObject()) and ($pay_stub_obj->getUserObject()->getTerminationDate() == '' or TTDate::getMiddleDayEpoch($pay_stub_obj->getUserObject()->getTerminationDate()) >= TTDate::getMiddleDayEpoch($pay_period_obj->getStartDate())))
                            ) {
                                Debug::text('Deleting pay stub: ' . $pay_stub_obj->getId(), __FILE__, __LINE__, __METHOD__, 10);
                                $pay_stub_obj->setDeleted(true);
                                $pay_stub_obj->Save();
                            } else {
                                Debug::text('Pay stub does not need regenerating, or it is LOCKED! ID: ' . $pay_stub_obj->getID() . ' Status: ' . $pay_stub_obj->getStatus() . ' Tainted: ' . (int)$pay_stub_obj->getTainted() . ' Pay Stub End Date: ' . $pay_stub_obj->getEndDate() . ' Pay Period End Date: ' . $pay_period_obj->getEndDate(), __FILE__, __LINE__, __METHOD__, 10);
                            }
                        }

                        $i = 1;
                        foreach ($ppsulf as $pay_period_schdule_user_obj) {
                            Debug::text('Pay Period User ID: ' . $pay_period_schdule_user_obj->getUser(), __FILE__, __LINE__, __METHOD__, 10);
                            Debug::text('Total Pay Stubs: ' . $total_pay_stubs . ' - ' . ceil(1 / (100 / $total_pay_stubs)), __FILE__, __LINE__, __METHOD__, 10);

                            $profiler->startTimer('Calculating Pay Stub');
                            //Calc paystubs.
                            $cps = new CalculatePayStub();
                            $cps->setEnableCorrection((bool)$enable_correction);
                            $cps->setUser($pay_period_schdule_user_obj->getUser());
                            $cps->setPayPeriod($pay_period_obj->getId());
                            $cps->setType($type_id);
                            $cps->setRun($run_id);
                            if ($transaction_date != '') {
                                $cps->setTransactionDate(TTDate::parseDateTime($transaction_date));
                            }
                            $cps->calculate();
                            unset($cps);
                            $profiler->stopTimer('Calculating Pay Stub');

                            $this->getProgressBarObject()->set($this->getAMFMessageID(), $i);

                            //sleep(1); /////////////////////////////// FOR TESTING ONLY //////////////////

                            $i++;
                        }
                        unset($ppsulf);

                        $this->getProgressBarObject()->stop($this->getAMFMessageID());
                    } else {
                        Debug::text('ERROR: User not assigned to pay period schedule...', __FILE__, __LINE__, __METHOD__, 10);
                        UserGenericStatusFactory::queueGenericStatus(TTi18n::gettext('ERROR'), 10, TTi18n::gettext('Unable to generate pay stub(s), employee(s) may not be assigned to a pay period schedule.'), null);
                    }
                } else {
                    UserGenericStatusFactory::queueGenericStatus(TTi18n::gettext('ERROR'), 10, TTi18n::gettext('Pay period prior to %1 is not closed, please close all previous pay periods and try again...', array(TTDate::getDate('DATE', $pay_period_obj->getStartDate()) . ' -> ' . TTDate::getDate('DATE', $pay_period_obj->getEndDate()))), null);
                }
            }
        }

        if (UserGenericStatusFactory::isStaticQueue() == true) {
            $ugsf = TTnew('UserGenericStatusFactory');
            $ugsf->setUser($this->getCurrentUserObject()->getId());
            $ugsf->setBatchID($ugsf->getNextBatchId());
            $ugsf->setQueue(UserGenericStatusFactory::getStaticQueue());
            $ugsf->saveQueue();
            $user_generic_status_batch_id = $ugsf->getBatchID();
        } else {
            $user_generic_status_batch_id = false;
        }
        unset($ugsf);

        return $this->returnHandler(true, true, false, false, false, $user_generic_status_batch_id);
    }
}
