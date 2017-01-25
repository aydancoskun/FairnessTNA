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
 * @package API\ROE
 */
class APIROE extends APIFactory
{
    protected $main_class = 'ROEFactory';

    public function __construct()
    {
        parent::__construct(); //Make sure parent constructor is always called.

        return true;
    }

    /**
     * Get options for dropdown boxes.
     * @param string $name Name of options to return, ie: 'columns', 'type', 'status'
     * @param mixed $parent Parent name/ID of options to return if data is in hierarchical format. (ie: Province)
     * @return array
     */
    public function getOptions($name = false, $parent = null)
    {
        if ($name == 'columns'
            and (!$this->getPermissionObject()->Check('roe', 'enabled')
                or !($this->getPermissionObject()->Check('roe', 'view') or $this->getPermissionObject()->Check('roe', 'view_own')))
        ) {
            $name = 'list_columns';
        }

        return parent::getOptions($name, $parent);
    }

    /**
     * Get default roe data for creating new roe.
     * @return array
     */
    public function getROEDefaultData($user_id = null)
    {
        $company_obj = $this->getCurrentCompanyObject();

        if ($user_id > 0) {
            Debug::Text('Getting roe default data... User ID: ' . $user_id, __FILE__, __LINE__, __METHOD__, 10);
            $rf = new ROEFactory();
            $rf->setUser($user_id);
            $first_date = $rf->calculateFirstDate();
            $last_date = $rf->calculateLastDate();
            $pay_period = $rf->calculatePayPeriodType($last_date);
            $final_pay_stub_end_date = $rf->calculateFinalPayStubEndDate();
            $final_pay_stub_transaction_date = $rf->calculateFinalPayStubTransactionDate();
            if ($rf->isFinalPayStubExists() == true) {
                $release_accruals = false;
                $generate_pay_stub = false;
            } else {
                $release_accruals = true;
                $generate_pay_stub = true;
            }

            $data = array(
                'company_id' => $company_obj->getId(),
                'user_id' => $user_id,
                'pay_period_type_id' => $pay_period['pay_period_type_id'],
                'first_date' => TTDate::getAPIDate('DATE', $first_date),
                'last_date' => TTDate::getAPIDate('DATE', $last_date),
                'pay_period_end_date' => TTDate::getAPIDate('DATE', $pay_period['pay_period_end_date']),
                'final_pay_stub_end_date' => ($final_pay_stub_end_date > time()) ? TTDate::getAPIDate('DATE', time()) : TTDate::getAPIDate('DATE', $final_pay_stub_end_date),
                'final_pay_stub_transaction_date' => TTDate::getAPIDate('DATE', $final_pay_stub_transaction_date),
                'release_accruals' => $release_accruals,
                'generate_pay_stub' => $generate_pay_stub,
            );
        } else {
            $data = array(
                'company_id' => $company_obj->getId(),
                'release_accruals' => true,
                'generate_pay_stub' => true,
            );
        }

        Debug::Arr($data, 'zzz9Getting roe default data... User ID: ' . $user_id, __FILE__, __LINE__, __METHOD__, 10);
        return $this->returnHandler($data);
    }

    /**
     * Export data to csv
     * @param array $data filter data
     * @param string $format file format (csv)
     * @return array
     */
    public function exportROE($format = 'csv', $data = null, $disable_paging = true)
    {
        $result = $this->stripReturnHandler($this->getROE($data, $disable_paging));
        return $this->exportRecords($format, 'export_roe', $result, ((isset($data['filter_columns'])) ? $data['filter_columns'] : null));
    }

    /**
     * Get roe data for one or more roe.
     * @param array $data filter data
     * @return array
     */
    public function getROE($data = null, $disable_paging = false)
    {
        if (!$this->getPermissionObject()->Check('roe', 'enabled')
            or !($this->getPermissionObject()->Check('roe', 'view') or $this->getPermissionObject()->Check('roe', 'view_own') or $this->getPermissionObject()->Check('roe', 'view_child'))
        ) {
            return $this->getPermissionObject()->PermissionDenied();
            //Rather then permission denied, restrict to just 'list_view' columns.
            //$data['filter_columns'] = $this->handlePermissionFilterColumns( (isset($data['filter_columns'])) ? $data['filter_columns'] : NULL, Misc::trimSortPrefix( $this->getOptions('list_columns') ) );
        }
        $data = $this->initializeFilterAndPager($data, $disable_paging);

        $data['filter_data']['permission_children_ids'] = $this->getPermissionObject()->getPermissionChildren('roe', 'view');

        $rlf = TTnew('ROEListFactory');
        $rlf->getAPISearchByCompanyIdAndArrayCriteria($this->getCurrentCompanyObject()->getId(), $data['filter_data'], $data['filter_items_per_page'], $data['filter_page'], null, $data['filter_sort']);
        Debug::Text('Record Count: ' . $rlf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
        if ($rlf->getRecordCount() > 0) {
            $this->getProgressBarObject()->start($this->getAMFMessageID(), $rlf->getRecordCount());

            $this->setPagerObject($rlf);

            $retarr = array();
            foreach ($rlf as $roe_obj) {
                $retarr[] = $roe_obj->getObjectAsArray($data['filter_columns']);

                $this->getProgressBarObject()->set($this->getAMFMessageID(), $rlf->getCurrentRow());
            }

            $this->getProgressBarObject()->stop($this->getAMFMessageID());

            return $this->returnHandler($retarr);
        }

        return $this->returnHandler(true); //No records returned.
    }

    /**
     * Get only the fields that are common across all records in the search criteria. Used for Mass Editing of records.
     * @param array $data filter data
     * @return array
     */
    public function getCommonROEData($data)
    {
        return Misc::arrayIntersectByRow($this->stripReturnHandler($this->getROE($data, true)));
    }

    /**
     * Validate roe data for one or more roe.
     * @param array $data roe data
     * @return array
     */
    public function validateROE($data)
    {
        return $this->setROE($data, true);
    }

    /**
     * Set roe data for one or more roe.
     * @param array $data roe data
     * @return array
     */
    public function setROE($data, $validate_only = false, $ignore_warning = true)
    {
        $validate_only = (bool)$validate_only;
        $ignore_warning = (bool)$ignore_warning;

        if (!is_array($data)) {
            return $this->returnHandler(false);
        }

        if (!$this->getPermissionObject()->Check('roe', 'enabled')
            or !($this->getPermissionObject()->Check('roe', 'edit') or $this->getPermissionObject()->Check('roe', 'edit_own') or $this->getPermissionObject()->Check('roe', 'edit_child') or $this->getPermissionObject()->Check('roe', 'add'))
        ) {
            return $this->getPermissionObject()->PermissionDenied();
        }

        if ($validate_only == true) {
            Debug::Text('Validating Only!', __FILE__, __LINE__, __METHOD__, 10);
        }

        extract($this->convertToMultipleRecords($data));
        Debug::Text('Received data for: ' . $total_records . ' ROE', __FILE__, __LINE__, __METHOD__, 10);
        Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

        $validator_stats = array('total_records' => $total_records, 'valid_records' => 0);
        $validator = $save_result = false;
        if (is_array($data) and $total_records > 0) {
            $this->getProgressBarObject()->start($this->getAMFMessageID(), $total_records);

            foreach ($data as $key => $row) {
                $primary_validator = new Validator();
                $lf = TTnew('ROEListFactory');
                $lf->StartTransaction();
                if (isset($row['id']) and $row['id'] > 0) {
                    //Modifying existing object.
                    //Get branch object, so we can only modify just changed data for specific records if needed.
                    $lf->getByIdAndCompanyId($row['id'], $this->getCurrentCompanyObject()->getId());
                    if ($lf->getRecordCount() == 1) {
                        //Object exists, check edit permissions
                        if (
                            $validate_only == true
                            or
                            (
                                $this->getPermissionObject()->Check('roe', 'edit')
                                or ($this->getPermissionObject()->Check('roe', 'edit_own') and $this->getPermissionObject()->isOwner($lf->getCurrent()->getCreatedBy(), $lf->getCurrent()->getID()) === true)
                            )
                        ) {
                            Debug::Text('Row Exists, getting current data: ', $row['id'], __FILE__, __LINE__, __METHOD__, 10);
                            $lf = $lf->getCurrent();
                            $row = array_merge($lf->getObjectAsArray(), $row);
                        } else {
                            $primary_validator->isTrue('permission', false, TTi18n::gettext('Edit permission denied'));
                        }
                    } else {
                        //Object doesn't exist.
                        $primary_validator->isTrue('id', false, TTi18n::gettext('Edit permission denied, record does not exist'));
                    }
                } else {
                    //Adding new object, check ADD permissions.
                    $primary_validator->isTrue('permission', $this->getPermissionObject()->Check('roe', 'add'), TTi18n::gettext('Add permission denied'));
                }
                Debug::Arr($row, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

                $is_valid = $primary_validator->isValid($ignore_warning);
                if ($is_valid == true) { //Check to see if all permission checks passed before trying to save data.
                    Debug::Text('Setting object data...', __FILE__, __LINE__, __METHOD__, 10);

                    //Force Company ID to current company.
                    $row['company_id'] = $this->getCurrentCompanyObject()->getId();

                    $lf->setObjectFromArray($row);

                    $is_valid = $lf->isValid($ignore_warning);
                    if ($is_valid == true) {
                        Debug::Text('Saving data...', __FILE__, __LINE__, __METHOD__, 10);
                        $lf->setEnableReCalculate(true);
                        if (isset($row['generate_pay_stub']) and $row['generate_pay_stub'] == 1) {
                            $lf->setEnableGeneratePayStub(true);
                        } else {
                            $lf->setEnableGeneratePayStub(false);
                        }
                        if (isset($row['release_accruals']) and $row['release_accruals'] == 1) {
                            $lf->setEnableReleaseAccruals(true);
                        } else {
                            $lf->setEnableReleaseAccruals(false);
                        }
                        if ($validate_only == true) {
                            $save_result[$key] = true;
                        } else {
                            $save_result[$key] = $lf->Save();
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

            if ($validator_stats['valid_records'] > 0 and $validator_stats['total_records'] == $validator_stats['valid_records']) {
                if ($validator_stats['total_records'] == 1) {
                    return $this->returnHandler($save_result[$key], true, false, false, false, $user_generic_status_batch_id); //Single valid record
                } else {
                    return $this->returnHandler(true, 'SUCCESS', TTi18n::getText('MULTIPLE RECORDS SAVED'), $save_result, $validator_stats, $user_generic_status_batch_id); //Multiple valid records
                }
            } else {
                return $this->returnHandler(false, 'VALIDATION', TTi18n::getText('INVALID DATA'), $validator, $validator_stats, $user_generic_status_batch_id);
            }
        }

        return $this->returnHandler(false);
    }

    /**
     * Delete one or more roe.
     * @param array $data roe data
     * @return array
     */
    public function deleteROE($data)
    {
        if (is_numeric($data)) {
            $data = array($data);
        }

        if (!is_array($data)) {
            return $this->returnHandler(false);
        }

        if (!$this->getPermissionObject()->Check('roe', 'enabled')
            or !($this->getPermissionObject()->Check('roe', 'delete') or $this->getPermissionObject()->Check('roe', 'delete_own') or $this->getPermissionObject()->Check('roe', 'delete_child'))
        ) {
            return $this->getPermissionObject()->PermissionDenied();
        }

        Debug::Text('Received data for: ' . count($data) . ' ROE', __FILE__, __LINE__, __METHOD__, 10);
        Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

        $total_records = count($data);
        $validator = $save_result = false;
        $validator_stats = array('total_records' => $total_records, 'valid_records' => 0);
        if (is_array($data) and $total_records > 0) {
            $this->getProgressBarObject()->start($this->getAMFMessageID(), $total_records);

            foreach ($data as $key => $id) {
                $primary_validator = new Validator();
                $lf = TTnew('ROEListFactory');
                $lf->StartTransaction();
                if (is_numeric($id)) {
                    //Modifying existing object.
                    //Get branch object, so we can only modify just changed data for specific records if needed.
                    $lf->getByIdAndCompanyId($id, $this->getCurrentCompanyObject()->getId());
                    if ($lf->getRecordCount() == 1) {
                        //Object exists, check edit permissions
                        if ($this->getPermissionObject()->Check('roe', 'delete')
                            or ($this->getPermissionObject()->Check('roe', 'delete_own') and $this->getPermissionObject()->isOwner($lf->getCurrent()->getCreatedBy(), $lf->getCurrent()->getID()) === true)
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

    /**
     * Copy one or more roe.
     * @param array $data roe IDs
     * @return array
     */
    public function copyROE($data)
    {
        if (is_numeric($data)) {
            $data = array($data);
        }

        if (!is_array($data)) {
            return $this->returnHandler(false);
        }

        Debug::Text('Received data for: ' . count($data) . ' ROE', __FILE__, __LINE__, __METHOD__, 10);
        Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

        $src_rows = $this->stripReturnHandler($this->getROE(array('filter_data' => array('id' => $data)), true));
        if (is_array($src_rows) and count($src_rows) > 0) {
            Debug::Arr($src_rows, 'SRC Rows: ', __FILE__, __LINE__, __METHOD__, 10);
            foreach ($src_rows as $key => $row) {
                unset($src_rows[$key]['id']); //Clear fields that can't be copied
            }
            unset($row); //code standards
            //Debug::Arr($src_rows, 'bSRC Rows: ', __FILE__, __LINE__, __METHOD__, 10);

            return $this->setROE($src_rows); //Save copied rows
        }

        return $this->returnHandler(false);
    }
}
