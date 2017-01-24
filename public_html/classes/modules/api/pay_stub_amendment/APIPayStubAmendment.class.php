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
 * @package API\PayStubAmendment
 */
class APIPayStubAmendment extends APIFactory
{
    protected $main_class = 'PayStubAmendmentFactory';

    public function __construct()
    {
        parent::__construct(); //Make sure parent constructor is always called.

        return true;
    }

    /**
     * Get default branch data for creating new branches.
     * @return array
     */
    public function getPayStubAmendmentDefaultData()
    {
        $company_obj = $this->getCurrentCompanyObject();

        Debug::Text('Getting pay stub amendment default data...', __FILE__, __LINE__, __METHOD__, 10);

        $data = array(
            'company_id' => $company_obj->getId(),
            'user_id' => array(),
            'status_id' => 50,
            'type_id' => 10,
            'effective_date' => TTDate::getAPIDate('DATE', TTDate::getTime())
        );

        return $this->returnHandler($data);
    }

    /**
     * Export data to csv
     * @param array $data filter data
     * @param string $format file format (csv)
     * @return array
     */
    public function exportPayStubAmendment($format = 'csv', $data = null, $disable_paging = true)
    {
        $result = $this->stripReturnHandler($this->getPayStubAmendment($data, $disable_paging));
        return $this->exportRecords($format, 'export_pay_stub_amendment', $result, ((isset($data['filter_columns'])) ? $data['filter_columns'] : null));
    }

    /**
     * Get branch data for one or more branches.
     * @param array $data filter data
     * @return array
     */
    public function getPayStubAmendment($data = null, $disable_paging = false, $format = false)
    {
        if (!$this->getPermissionObject()->Check('pay_stub_amendment', 'enabled')
            or !($this->getPermissionObject()->Check('pay_stub_amendment', 'view') or $this->getPermissionObject()->Check('pay_stub_amendment', 'view_own') or $this->getPermissionObject()->Check('pay_stub_amendment', 'view_child'))
        ) {
            return $this->getPermissionObject()->PermissionDenied();
        }
        $data = $this->initializeFilterAndPager($data, $disable_paging);

        $data['filter_data']['permission_children_ids'] = $this->getPermissionObject()->getPermissionChildren('pay_stub_amendment', 'view');

        $blf = TTnew('PayStubAmendmentListFactory');
        $blf->getAPISearchByCompanyIdAndArrayCriteria($this->getCurrentCompanyObject()->getId(), $data['filter_data'], $data['filter_items_per_page'], $data['filter_page'], null, $data['filter_sort']);
        Debug::Text('Record Count: ' . $blf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);

        $format = Misc::trimSortPrefix($format);
        if ($format != '') {
            $export_options = Misc::trimSortPrefix($blf->getOptions('export_type'));
            if (isset($export_options[$format])) {
                if ($blf->getRecordCount() > 0) {
                    $this->getProgressBarObject()->setDefaultKey($this->getAMFMessageID());
                    $this->getProgressBarObject()->start($this->getAMFMessageID(), $blf->getRecordCount());
                    $blf->setProgressBarObject($this->getProgressBarObject()); //Expose progress bar object to pay stub object.

                    $output = $blf->exportPayStubAmendment($blf, $format);

                    $this->getProgressBarObject()->stop($this->getAMFMessageID());

                    if (stristr($format, 'cheque')) {
                        return Misc::APIFileDownload('checks_' . str_replace(array('/', ',', ' '), '_', TTDate::getDate('DATE', time())) . '.pdf', 'application/pdf', $output);
                    } else {
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
                        return Misc::APIFileDownload('eft_' . $file_creation_number . '_' . str_replace(array('/', ',', ' '), '_', TTDate::getDate('DATE', time())) . '.txt', 'application/pdf', $output);
                    }
                }
            } else {
                Debug::Text('Invalid format ' . $format, __FILE__, __LINE__, __METHOD__, 10);
            }
        } else {
            if ($blf->getRecordCount() > 0) {
                $this->getProgressBarObject()->start($this->getAMFMessageID(), $blf->getRecordCount());

                $this->setPagerObject($blf);

                $retarr = array();
                foreach ($blf as $b_obj) {
                    $retarr[] = $b_obj->getObjectAsArray($data['filter_columns'], $data['filter_data']['permission_children_ids']);

                    $this->getProgressBarObject()->set($this->getAMFMessageID(), $blf->getCurrentRow());
                }

                $this->getProgressBarObject()->stop($this->getAMFMessageID());

                return $this->returnHandler($retarr);
            }
        }

        return $this->returnHandler(true); //No records returned.
    }

    /**
     * Get only the fields that are common across all records in the search criteria. Used for Mass Editing of records.
     * @param array $data filter data
     * @return array
     */
    public function getCommonPayStubAmendmentData($data)
    {
        return Misc::arrayIntersectByRow($this->stripReturnHandler($this->getPayStubAmendment($data, true)));
    }

    /**
     * Validate branch data for one or more branches.
     * @param array $data branch data
     * @return array
     */
    public function validatePayStubAmendment($data)
    {
        return $this->setPayStubAmendment($data, true);
    }

    /**
     * Set branch data for one or more branches.
     * @param array $data branch data
     * @return array
     */
    public function setPayStubAmendment($data, $validate_only = false, $ignore_warning = true)
    {
        $validate_only = (bool)$validate_only;
        $ignore_warning = (bool)$ignore_warning;

        if (!is_array($data)) {
            return $this->returnHandler(false);
        }

        if (!$this->getPermissionObject()->Check('pay_stub_amendment', 'enabled')
            or !($this->getPermissionObject()->Check('pay_stub_amendment', 'edit') or $this->getPermissionObject()->Check('pay_stub_amendment', 'edit_own') or $this->getPermissionObject()->Check('pay_stub_amendment', 'edit_child') or $this->getPermissionObject()->Check('pay_stub_amendment', 'add'))
        ) {
            return $this->getPermissionObject()->PermissionDenied();
        }

        if ($validate_only == true) {
            Debug::Text('Validating Only!', __FILE__, __LINE__, __METHOD__, 10);
        }

        extract($this->convertToMultipleRecords($data));
        Debug::Text('Received data for: ' . $total_records . ' PayStubAmendments', __FILE__, __LINE__, __METHOD__, 10);
        Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

        $validator_stats = array('total_records' => $total_records, 'valid_records' => 0);
        $validator = $save_result = false;
        if (is_array($data) and $total_records > 0) {
            $this->getProgressBarObject()->start($this->getAMFMessageID(), $total_records);

            foreach ($data as $key => $row) {
                $primary_validator = new Validator();
                $lf = TTnew('PayStubAmendmentListFactory');
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
                                $this->getPermissionObject()->Check('pay_stub_amendment', 'edit')
                                or ($this->getPermissionObject()->Check('pay_stub_amendment', 'edit_own') and $this->getPermissionObject()->isOwner($lf->getCurrent()->getCreatedBy(), $lf->getCurrent()->getID()) === true)
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
                    $primary_validator->isTrue('permission', $this->getPermissionObject()->Check('pay_stub_amendment', 'add'), TTi18n::gettext('Add permission denied'));
                }
                Debug::Arr($row, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

                if ($validate_only == true) {
                    $lf->Validator->setValidateOnly($validate_only);
                }

                $is_valid = $primary_validator->isValid($ignore_warning);
                if ($is_valid == true) { //Check to see if all permission checks passed before trying to save data.
                    Debug::Text('Setting object data...', __FILE__, __LINE__, __METHOD__, 10);

                    $lf->setObjectFromArray($row);

                    //Force Company ID to current company.
                    //$lf->setCompany( $this->getCurrentCompanyObject()->getId() );

                    $is_valid = $lf->isValid($ignore_warning);
                    if ($is_valid == true) {
                        Debug::Text('Saving data...', __FILE__, __LINE__, __METHOD__, 10);
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

            return $this->handleRecordValidationResults($validator, $validator_stats, $key, $save_result);
        }

        return $this->returnHandler(false);
    }

    /**
     * Delete one or more branchs.
     * @param array $data branch data
     * @return array
     */
    public function deletePayStubAmendment($data)
    {
        if (is_numeric($data)) {
            $data = array($data);
        }

        if (!is_array($data)) {
            return $this->returnHandler(false);
        }

        if (!$this->getPermissionObject()->Check('pay_stub_amendment', 'enabled')
            or !($this->getPermissionObject()->Check('pay_stub_amendment', 'delete') or $this->getPermissionObject()->Check('pay_stub_amendment', 'delete_own') or $this->getPermissionObject()->Check('pay_stub_amendment', 'delete_child'))
        ) {
            return $this->getPermissionObject()->PermissionDenied();
        }

        Debug::Text('Received data for: ' . count($data) . ' PayStubAmendments', __FILE__, __LINE__, __METHOD__, 10);
        Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

        $total_records = count($data);
        $validator = $save_result = false;
        $validator_stats = array('total_records' => $total_records, 'valid_records' => 0);
        if (is_array($data) and $total_records > 0) {
            $this->getProgressBarObject()->start($this->getAMFMessageID(), $total_records);

            foreach ($data as $key => $id) {
                $primary_validator = new Validator();
                $lf = TTnew('PayStubAmendmentListFactory');
                $lf->StartTransaction();
                if (is_numeric($id)) {
                    //Modifying existing object.
                    //Get branch object, so we can only modify just changed data for specific records if needed.
                    $lf->getByIdAndCompanyId($id, $this->getCurrentCompanyObject()->getId());
                    if ($lf->getRecordCount() == 1) {
                        //Object exists, check edit permissions
                        if ($this->getPermissionObject()->Check('pay_stub_amendment', 'delete')
                            or ($this->getPermissionObject()->Check('pay_stub_amendment', 'delete_own') and $this->getPermissionObject()->isOwner($lf->getCurrent()->getCreatedBy(), $lf->getCurrent()->getID()) === true)
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
     * Copy one or more branches.
     * @param array $data branch IDs
     * @return array
     */
    public function copyPayStubAmendment($data)
    {
        if (is_numeric($data)) {
            $data = array($data);
        }

        if (!is_array($data)) {
            return $this->returnHandler(false);
        }

        Debug::Text('Received data for: ' . count($data) . ' PayStubAmendments', __FILE__, __LINE__, __METHOD__, 10);
        Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

        $src_rows = $this->stripReturnHandler($this->getPayStubAmendment(array('filter_data' => array('id' => $data)), true));
        if (is_array($src_rows) and count($src_rows) > 0) {
            Debug::Arr($src_rows, 'SRC Rows: ', __FILE__, __LINE__, __METHOD__, 10);
            foreach ($src_rows as $key => $row) {
                unset($src_rows[$key]['id']); //Clear fields that can't be copied
            }
            unset($row); //code standards
            //Debug::Arr($src_rows, 'bSRC Rows: ', __FILE__, __LINE__, __METHOD__, 10);

            return $this->setPayStubAmendment($src_rows); //Save copied rows
        }

        return $this->returnHandler(false);
    }

    /**
     * Calculate the PS Amendment amount based on the user, rate and units.
     * @param int $user_id User ID
     * @param float $rate Rate
     * @param float $units Units
     * @return float
     */
    public function calcAmount($user_id, $rate, $units)
    {
        $psf = TTnew('PayStubAmendmentFactory');
        $psf->setUser($user_id);
        $psf->setRate($rate);
        $psf->setUnits($units);
        return $this->returnHandler($psf->calcAmount());
    }
}
