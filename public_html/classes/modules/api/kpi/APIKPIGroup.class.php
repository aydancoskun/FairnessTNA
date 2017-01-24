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
 * @package API\KPI
 */
class APIKPIGroup extends APIFactory
{
    protected $main_class = 'KPIGroupFactory';

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
            and (!$this->getPermissionObject()->Check('kpi', 'enabled')
                or !($this->getPermissionObject()->Check('kpi', 'view') or $this->getPermissionObject()->Check('kpi', 'view_own') or $this->getPermissionObject()->Check('kpi', 'view_child')))
        ) {
            $name = 'list_columns';
        }

        return parent::getOptions($name, $parent);
    }

    /**
     * Get default KPIGroup data for creating new KPIGroupes.
     * @return array
     */
    public function getKPIGroupDefaultData()
    {
        $company_obj = $this->getCurrentCompanyObject();

        Debug::Text('Getting KPIGroup default data...', __FILE__, __LINE__, __METHOD__, 10);

        $data = array(
            'company_id' => $company_obj->getId(),
            'parent_id' => 0,
            'name' => null,
        );

        return $this->returnHandler($data);
    }

    /**
     * Get only the fields that are common across all records in the search criteria. Used for Mass Editing of records.
     * @param array $data filter data
     * @return array
     */
    public function getCommonKPIGroupData($data)
    {
        return Misc::arrayIntersectByRow($this->stripReturnHandler($this->getKPIGroup($data, true)));
    }

    /**
     * Get KPIGroup data for one or more KPIGroupes.
     * @param array $data filter data
     * @return array
     */
    public function getKPIGroup($data = null, $disable_paging = false, $mode = 'flat')
    {
        if (!$this->getPermissionObject()->Check('kpi', 'enabled')
            or !($this->getPermissionObject()->Check('kpi', 'view') or $this->getPermissionObject()->Check('kpi', 'view_own') or $this->getPermissionObject()->Check('kpi', 'view_child'))
        ) {
            return $this->getPermissionObject()->PermissionDenied();
        }
        $data = $this->initializeFilterAndPager($data, $disable_paging);

        $data['filter_data']['permission_children_ids'] = $this->getPermissionObject()->getPermissionChildren('kpi', 'view');

        $qglf = TTnew('KPIGroupListFactory');

        if ($mode == 'flat') {
            $qglf->getAPISearchByCompanyIdAndArrayCriteria($this->getCurrentCompanyObject()->getId(), $data['filter_data'], $data['filter_items_per_page'], $data['filter_page'], null, $data['filter_sort']);
            Debug::Text('Record Count: ' . $qglf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);

            if ($qglf->getRecordCount() > 0) {
                $this->getProgressBarObject()->start($this->getAMFMessageID(), $qglf->getRecordCount());

                $this->setPagerObject($qglf);

                $retarr = array();
                foreach ($qglf as $ug_obj) {
                    $retarr[] = $ug_obj->getObjectAsArray($data['filter_columns'], $data['filter_data']['permission_children_ids']);

                    $this->getProgressBarObject()->set($this->getAMFMessageID(), $qglf->getCurrentRow());
                }

                $this->getProgressBarObject()->stop($this->getAMFMessageID());

                return $this->returnHandler($retarr);
            }
        } else {
            $nodes = $qglf->getByCompanyIdArray($this->getCurrentCompanyObject()->getId());
            //Debug::Arr($nodes, ' Nodes: ', __FILE__, __LINE__, __METHOD__, 10);
            Debug::Text('Record Count: ' . count($nodes), __FILE__, __LINE__, __METHOD__, 10);
            if (isset($nodes)) {
                //$retarr = $uglf->getArrayByNodes( FastTree::FormatArray( $nodes, 'PLAIN_TEXT', TRUE) );
                $retarr = FastTree::FormatFlexArray($nodes);
                Debug::Arr($retarr, ' Data: ', __FILE__, __LINE__, __METHOD__, 10);

                //There seems to be a bug with Flex here that if getKPI() and getKPIGroup() are called at the same time
                //if this function returns an array with the keys out of order (1, 5, 10, rather then 0, 1, 3, 4, 5) Flex just sees
                //some empty object.
                //Not sure why this is happening with just this function, but the workaround for now is to call getKPIGroup()
                //in a separate call to the server.
                //This could have something to do with the array having 0 => ... as the first entry, which we ran into a issue
                //in ExceptionPolicyFactory with getOptions('email_notification')

                return $this->returnHandler($retarr);
            }
        }

        return $this->returnHandler(true); //No records returned.
    }

    /**
     * Validate KPIGroup data for one or more KPIGroupes.
     * @param array $data KPIGroup data
     * @return array
     */
    public function validateKPIGroup($data)
    {
        return $this->setKPIGroup($data, true);
    }

    /**
     * Set KPIGroup data for one or more KPIGroupes.
     * @param array $data KPIGroup data
     * @return array
     */
    public function setKPIGroup($data, $validate_only = false, $ignore_warning = true)
    {
        $validate_only = (bool)$validate_only;
        $ignore_warning = (bool)$ignore_warning;

        if (!is_array($data)) {
            return $this->returnHandler(false);
        }

        if (!$this->getPermissionObject()->Check('kpi', 'enabled')
            or !($this->getPermissionObject()->Check('kpi', 'edit') or $this->getPermissionObject()->Check('kpi', 'edit_own') or $this->getPermissionObject()->Check('kpi', 'edit_child') or $this->getPermissionObject()->Check('kpi', 'add'))
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

        Debug::Text('Received data for: ' . $total_records . ' KPIGroups', __FILE__, __LINE__, __METHOD__, 10);
        Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

        $validator_stats = array('total_records' => $total_records, 'valid_records' => 0);
        $validator = $save_result = false;
        if (is_array($data) and $total_records > 0) {
            foreach ($data as $key => $row) {
                $primary_validator = new Validator();
                $lf = TTnew('KPIGroupListFactory');
                $lf->StartTransaction();

                if (isset($row['id']) and $row['id'] > 0) {
                    //Modifying existing object.
                    //Get KPIGroup object, so we can only modify just changed data for specific records if needed.
                    $lf->getByIdAndCompanyId($row['id'], $this->getCurrentCompanyObject()->getId());

                    if ($lf->getRecordCount() == 1) {
                        //Object exists, check edit permissions
                        if (
                            $validate_only == true
                            or
                            (
                                $this->getPermissionObject()->Check('kpi', 'edit')
                                or ($this->getPermissionObject()->Check('kpi', 'edit_own') and $this->getPermissionObject()->isOwner($lf->getCurrent()->getCreatedBy()) === true)
                                or ($this->getPermissionObject()->Check('kpi', 'edit_child') and $this->getPermissionObject()->isChild($lf->getCurrent()->getCreatedBy(), $permission_children_ids) === true)
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
                    $primary_validator->isTrue('permission', $this->getPermissionObject()->Check('kpi', 'add'), TTi18n::gettext('Add permission denied'));
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
            }

            return $this->handleRecordValidationResults($validator, $validator_stats, $key, $save_result);
        }

        return $this->returnHandler(false);
    }

    /**
     * Delete one or more KPIGroups.
     * @param array $data KPIGroup data
     * @return array
     */
    public function deleteKPIGroup($data)
    {
        if (is_numeric($data)) {
            $data = array($data);
        }

        if (!is_array($data)) {
            return $this->returnHandler(false);
        }

        if (!$this->getPermissionObject()->Check('kpi', 'enabled')
            or !($this->getPermissionObject()->Check('kpi', 'delete') or $this->getPermissionObject()->Check('kpi', 'delete_own') or $this->getPermissionObject()->Check('kpi', 'delete_child'))
        ) {
            return $this->getPermissionObject()->PermissionDenied();
        }

        $permission_children_ids = $this->getPermissionChildren();

        Debug::Text('Received data for: ' . count($data) . ' KPIGroups', __FILE__, __LINE__, __METHOD__, 10);
        Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

        $total_records = count($data);
        $validator = $save_result = false;
        $validator_stats = array('total_records' => $total_records, 'valid_records' => 0);
        if (is_array($data) and $total_records > 0) {
            foreach ($data as $key => $id) {
                $primary_validator = new Validator();
                $lf = TTnew('KPIGroupListFactory');
                $lf->StartTransaction();
                if (is_numeric($id)) {
                    //Modifying existing object.
                    //Get KPIGroup object, so we can only modify just changed data for specific records if needed.
                    $lf->getByIdAndCompanyId($id, $this->getCurrentCompanyObject()->getId());
                    if ($lf->getRecordCount() == 1) {
                        //Object exists, check edit permissions
                        if ($this->getPermissionObject()->Check('kpi', 'delete')
                            or ($this->getPermissionObject()->Check('kpi', 'delete_own') and $this->getPermissionObject()->isOwner($lf->getCurrent()->getCreatedBy()) === true)
                            or ($this->getPermissionObject()->Check('kpi', 'delete_child') and $this->getPermissionObject()->isChild($lf->getCurrent()->getCreatedBy(), $permission_children_ids) === true)
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
            }

            return $this->handleRecordValidationResults($validator, $validator_stats, $key, $save_result);
        }

        return $this->returnHandler(false);
    }

    /**
     * Copy one or more KPIGroupes.
     * @param array $data KPIGroup IDs
     * @return array
     */
    public function copyKPIGroup($data)
    {
        if (is_numeric($data)) {
            $data = array($data);
        }

        if (!is_array($data)) {
            return $this->returnHandler(false);
        }

        Debug::Text('Received data for: ' . count($data) . ' KPIGroups', __FILE__, __LINE__, __METHOD__, 10);
        Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

        $src_rows = $this->stripReturnHandler($this->getKPIGroup(array('filter_data' => array('id' => $data)), true));
        if (is_array($src_rows) and count($src_rows) > 0) {
            Debug::Arr($src_rows, 'SRC Rows: ', __FILE__, __LINE__, __METHOD__, 10);
            foreach ($src_rows as $key => $row) {
                unset($src_rows[$key]['id'], $src_rows[$key]['manual_id']); //Clear fields that can't be copied
                $src_rows[$key]['name'] = Misc::generateCopyName($row['name']); //Generate unique name
            }
            //Debug::Arr($src_rows, 'bSRC Rows: ', __FILE__, __LINE__, __METHOD__, 10);

            return $this->setKPIGroup($src_rows); //Save copied rows
        }

        return $this->returnHandler(false);
    }

    /**
     * Change parent of one or more groups to another group.
     * @param array $src_id source Group ID
     * @param int $dst_id destination Group ID
     * @return array
     */
    public function dragNdropKPIGroup($src_id, $dst_id)
    {
        if (!is_array($src_id)) {
            $src_id = array($src_id);
        }

        if (is_array($dst_id)) {
            return $this->returnHandler(false);
        }

        Debug::Arr($src_id, 'Src ID: Data: ', __FILE__, __LINE__, __METHOD__, 10);
        Debug::Arr($dst_id, 'Dst ID: Data: ', __FILE__, __LINE__, __METHOD__, 10);

        $src_rows = $this->stripReturnHandler($this->getKPIGroup(array('filter_data' => array('id' => $src_id)), true, 'flat'));
        if (is_array($src_rows) and count($src_rows) > 0) {
            Debug::Arr($src_rows, 'SRC Rows: ', __FILE__, __LINE__, __METHOD__, 10);
            foreach ($src_rows as $key => $row) {
                $src_rows[$key]['parent_id'] = $dst_id;
            }
            unset($row); //code standards
            Debug::Arr($src_rows, 'bSRC Rows: ', __FILE__, __LINE__, __METHOD__, 10);

            return $this->setKPIGroup($src_rows); //Save copied rows
        }

        return $this->returnHandler(false);
    }
}
