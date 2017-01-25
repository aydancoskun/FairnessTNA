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
 * @package API\Qualification
 */
class APIQualification extends APIFactory
{
    protected $main_class = 'QualificationFactory';

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
            and (!$this->getPermissionObject()->Check('qualification', 'enabled')
                or !($this->getPermissionObject()->Check('qualification', 'view') or $this->getPermissionObject()->Check('qualification', 'view_own') or $this->getPermissionObject()->Check('qualification', 'view_child')))
        ) {
            $name = 'list_columns';
        }

        return parent::getOptions($name, $parent);
    }

    /**
     * Get default qualification data for creating new qualifications.
     * @return array
     */
    public function getQualificationDefaultData()
    {
        $company_obj = $this->getCurrentCompanyObject();

        Debug::Text('Getting qualification default data...', __FILE__, __LINE__, __METHOD__, 10);

        $data = array(
            'company_id' => $company_obj->getId()
        );

        return $this->returnHandler($data);
    }

    /**
     * @param string $format
     * @param null $data
     * @param bool $disable_paging
     * @return array|bool
     */
    public function exportQualification($format = 'csv', $data = null, $disable_paging = true)
    {
        $result = $this->stripReturnHandler($this->getQualification($data, $disable_paging));
        return $this->exportRecords($format, 'export_qualification', $result, ((isset($data['filter_columns'])) ? $data['filter_columns'] : null));
    }

    /**
     * Get qualification data for one or more qualifications.
     * @param array $data filter data
     * @return array
     */
    public function getQualification($data = null, $disable_paging = false)
    {
        if (!$this->getPermissionObject()->Check('qualification', 'enabled')
            or !($this->getPermissionObject()->Check('qualification', 'view') or $this->getPermissionObject()->Check('qualification', 'view_own') or $this->getPermissionObject()->Check('qualification', 'view_child'))
        ) {
            return $this->getPermissionObject()->PermissionDenied();
        }
        $data = $this->initializeFilterAndPager($data, $disable_paging);

        $data['filter_data']['permission_children_ids'] = $this->getPermissionObject()->getPermissionChildren('qualification', 'view');

        $qlf = TTnew('QualificationListFactory');

        $qlf->getAPISearchByCompanyIdAndArrayCriteria($this->getCurrentCompanyObject()->getId(), $data['filter_data'], $data['filter_items_per_page'], $data['filter_page'], null, $data['filter_sort']);

        Debug::Text('Record Count: ' . $qlf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
        if ($qlf->getRecordCount() > 0) {
            $this->getProgressBarObject()->start($this->getAMFMessageID(), $qlf->getRecordCount());

            $this->setPagerObject($qlf);

            $retarr = array();
            foreach ($qlf as $q_obj) {
                $retarr[] = $q_obj->getObjectAsArray($data['filter_columns'], $data['filter_data']['permission_children_ids']);

                $this->getProgressBarObject()->set($this->getAMFMessageID(), $qlf->getCurrentRow());
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
    public function getCommonQualificationData($data)
    {
        return Misc::arrayIntersectByRow($this->stripReturnHandler($this->getQualification($data, true)));
    }

    /**
     * Validate qualification data for one or more qualifications.
     * @param array $data qualification data
     * @return array
     */
    public function validateQualification($data)
    {
        return $this->setQualification($data, true);
    }

    /**
     * Set qualification data for one or more qualifications.
     * @param array $data qualification data
     * @return array
     */
    public function setQualification($data, $validate_only = false, $ignore_warning = true)
    {
        $validate_only = (bool)$validate_only;
        $ignore_warning = (bool)$ignore_warning;

        if (!is_array($data)) {
            return $this->returnHandler(false);
        }

        if (!$this->getPermissionObject()->Check('qualification', 'enabled')
            or !($this->getPermissionObject()->Check('qualification', 'edit') or $this->getPermissionObject()->Check('qualification', 'edit_own') or $this->getPermissionObject()->Check('qualification', 'edit_child') or $this->getPermissionObject()->Check('qualification', 'add'))
        ) {
            return $this->getPermissionObject()->PermissionDenied();
        }

        if ($validate_only == true) {
            Debug::Text('Validating Only!', __FILE__, __LINE__, __METHOD__, 10);
            $permission_children_ids = false;
        } else {
            //Get Permission Hierarchy Children first, as this can be used for viewing, or editing.
            $permission_children_ids = $this->getPermissionChildren();
        }

        extract($this->convertToMultipleRecords($data));
        Debug::Text('Received data for: ' . $total_records . ' Qualifications', __FILE__, __LINE__, __METHOD__, 10);
        Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

        $validator_stats = array('total_records' => $total_records, 'valid_records' => 0);
        $validator = $save_result = false;
        if (is_array($data) and $total_records > 0) {
            $this->getProgressBarObject()->start($this->getAMFMessageID(), $total_records);

            foreach ($data as $key => $row) {
                $primary_validator = new Validator();
                $lf = TTnew('QualificationListFactory');
                $lf->StartTransaction();
                if (isset($row['id']) and $row['id'] > 0) {
                    //Modifying existing object.
                    //Get qualification object, so we can only modify just changed data for specific records if needed.
                    $lf->getByIdAndCompanyId($row['id'], $this->getCurrentCompanyObject()->getId());

                    if ($lf->getRecordCount() == 1) {
                        //Object exists, check edit permissions
                        if (
                            $validate_only == true
                            or
                            (
                                $this->getPermissionObject()->Check('qualification', 'edit')
                                or ($this->getPermissionObject()->Check('qualification', 'edit_own') and $this->getPermissionObject()->isOwner($lf->getCurrent()->getCreatedBy()) === true)
                                or ($this->getPermissionObject()->Check('qualification', 'edit_child') and $this->getPermissionObject()->isChild($lf->getCurrent()->getCreatedBy(), $permission_children_ids) === true)
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
                    $primary_validator->isTrue('permission', $this->getPermissionObject()->Check('qualification', 'add'), TTi18n::gettext('Add permission denied'));
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
     * Delete one or more qualifications.
     * @param array $data qualification data
     * @return array
     */
    public function deleteQualification($data)
    {
        if (is_numeric($data)) {
            $data = array($data);
        }

        if (!is_array($data)) {
            return $this->returnHandler(false);
        }

        if (!$this->getPermissionObject()->Check('qualification', 'enabled')
            or !($this->getPermissionObject()->Check('qualification', 'delete') or $this->getPermissionObject()->Check('qualification', 'delete_own') or $this->getPermissionObject()->Check('qualification', 'delete_child'))
        ) {
            return $this->getPermissionObject()->PermissionDenied();
        }
        $permission_children_ids = $this->getPermissionChildren();
        Debug::Text('Received data for: ' . count($data) . ' Qualifications', __FILE__, __LINE__, __METHOD__, 10);
        Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

        $total_records = count($data);
        $validator = $save_result = false;
        $validator_stats = array('total_records' => $total_records, 'valid_records' => 0);
        if (is_array($data) and $total_records > 0) {
            $this->getProgressBarObject()->start($this->getAMFMessageID(), $total_records);

            foreach ($data as $key => $id) {
                $primary_validator = new Validator();
                $lf = TTnew('QualificationListFactory');
                $lf->StartTransaction();
                if (is_numeric($id)) {
                    //Modifying existing object.
                    //Get qualification object, so we can only modify just changed data for specific records if needed.
                    $lf->getByIdAndCompanyId($id, $this->getCurrentCompanyObject()->getId());
                    //$lf->getById($id);

                    if ($lf->getRecordCount() == 1) {
                        //Object exists, check edit permissions
                        if ($this->getPermissionObject()->Check('qualification', 'delete')
                            or ($this->getPermissionObject()->Check('qualification', 'delete_own') and $this->getPermissionObject()->isOwner($lf->getCurrent()->getCreatedBy()) === true)
                            or ($this->getPermissionObject()->Check('qualification', 'delete_child') and $this->getPermissionObject()->isChild($lf->getCurrent()->getCreatedBy(), $permission_children_ids) === true)
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
     * Copy one or more qualifications.
     * @param array $data qualification IDs
     * @return array
     */
    public function copyQualification($data)
    {
        if (is_numeric($data)) {
            $data = array($data);
        }

        if (!is_array($data)) {
            return $this->returnHandler(false);
        }

        Debug::Text('Received data for: ' . count($data) . ' Qualifications', __FILE__, __LINE__, __METHOD__, 10);
        Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

        $src_rows = $this->stripReturnHandler($this->getQualification(array('filter_data' => array('id' => $data)), true));

        if (is_array($src_rows) and count($src_rows) > 0) {
            Debug::Arr($src_rows, 'SRC Rows: ', __FILE__, __LINE__, __METHOD__, 10);
            foreach ($src_rows as $key => $row) {
                unset($src_rows[$key]['id']); //Clear fields that can't be copied
                $src_rows[$key]['name'] = Misc::generateCopyName($row['name']); //Generate unique name
            }
            //Debug::Arr($src_rows, 'bSRC Rows: ', __FILE__, __LINE__, __METHOD__, 10);

            return $this->setQualification($src_rows); //Save copied rows
        }

        return $this->returnHandler(false);
    }
}
