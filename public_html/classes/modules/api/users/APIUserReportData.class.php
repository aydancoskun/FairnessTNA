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
 * @package API\Users
 */
class APIUserReportData extends APIFactory
{
    protected $main_class = 'UserReportDataFactory';

    public function __construct()
    {
        parent::__construct(); //Make sure parent constructor is always called.

        return true;
    }

    /**
     * Validate user data for one or more users.
     * @param array $data user data
     * @return array
     */
    public function validateUserReportData($data)
    {
        return $this->setUserReportData($data, true);
    }

    /**
     * Set user data for one or more users.
     * @param array $data user data
     * @return array
     */
    public function setUserReportData($data, $validate_only = false, $ignore_warning = true)
    {
        if (!is_array($data)) {
            return $this->returnHandler(false);
        }
        $validate_only = (bool)$validate_only;
        $ignore_warning = (bool)$ignore_warning;

        extract($this->convertToMultipleRecords($data));
        Debug::Text('Received data for: ' . $total_records . ' Users', __FILE__, __LINE__, __METHOD__, 10);
        Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

        if ($validate_only == true) {
            Debug::Text('Validating Only!', __FILE__, __LINE__, __METHOD__, 10);
            $permission_children_ids = false;
        } else {
            //Get Permission Hierarchy Children first, as this can be used for viewing, or editing.
            $permission_children_ids = $this->getPermissionChildren();
        }

        $validator_stats = array('total_records' => $total_records, 'valid_records' => 0);
        $validator = $save_result = false;
        if (is_array($data) and $total_records > 0) {
            foreach ($data as $key => $row) {
                $row['company_id'] = $this->getCurrentUserObject()->getCompany();

                if (!isset($row['user_id'])
                    or !($this->getPermissionObject()->Check('user', 'view') or ($this->getPermissionObject()->Check('user', 'view_child') and $this->getPermissionObject()->isChild($row['user_id'], $permission_children_ids) === true))
                ) {
                    //Force user_id to currently logged in user.
                    Debug::Text('Forcing user_id...', __FILE__, __LINE__, __METHOD__, 10);
                    $row['user_id'] = $this->getCurrentUserObject()->getId();
                }

                $primary_validator = new Validator();
                $lf = TTnew('UserReportDataListFactory');
                $lf->StartTransaction();
                if (isset($row['id'])) {
                    //Modifying existing object.
                    //Get user object, so we can only modify just changed data for specific records if needed.
                    $lf->getByUserIdAndId($row['user_id'], $row['id']);
                    if ($lf->getRecordCount() == 1) {
                        //Object exists, check edit permissions
                        $row = array_merge($lf->getCurrent()->getObjectAsArray(), $row);
                    } else {
                        //Object doesn't exist.
                        $primary_validator->isTrue('id', false, TTi18n::gettext('Edit permission denied, employee does not exist'));
                    }
                } //else {
                //Adding new object, check ADD permissions.
                //$primary_validator->isTrue( 'permission', $this->getPermissionObject()->Check('user', 'add'), TTi18n::gettext('Add permission denied') );
                //}
                Debug::Arr($row, 'User Report Data: ', __FILE__, __LINE__, __METHOD__, 10);

                $is_valid = $primary_validator->isValid($ignore_warning);
                if ($is_valid == true) { //Check to see if all permission checks passed before trying to save data.
                    Debug::Text('Attempting to save User Report Data...', __FILE__, __LINE__, __METHOD__, 10);

                    //Force Company ID to current company.
                    $row['company_id'] = $this->getCurrentCompanyObject()->getId();

                    $lf->setObjectFromArray($row);

                    //$lf->setUser( $this->getCurrentUserObject()->getId() ); //Need to be able support copying reports to other users.

                    $lf->Validator->setValidateOnly($validate_only);

                    $is_valid = $lf->isValid($ignore_warning);
                    if ($is_valid == true) {
                        Debug::Text('Saving User Data...', __FILE__, __LINE__, __METHOD__, 10);
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
                    //Always fail transaction when valididate only is used, as	is saved to different tables immediately.
                    $lf->FailTransaction();
                }

                $lf->CommitTransaction();
            }

            return $this->handleRecordValidationResults($validator, $validator_stats, $key, $save_result);
        }

        return $this->returnHandler(false);
    }

    /**
     * Delete one or more users.
     * @param array $data user data
     * @return array
     */
    public function deleteUserReportData($data)
    {
        Debug::Arr($data, 'DataA: ', __FILE__, __LINE__, __METHOD__, 10);

        if (is_numeric($data)) {
            $data = array($data);
        }

        if (!is_array($data)) {
            return $this->returnHandler(false);
        }

        Debug::Text('Received data for: ' . count($data) . ' Users', __FILE__, __LINE__, __METHOD__, 10);
        Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

        $total_records = count($data);
        $validator = $save_result = false;
        $validator_stats = array('total_records' => $total_records, 'valid_records' => 0);
        if (is_array($data) and $total_records > 0) {
            foreach ($data as $key => $id) {
                $primary_validator = new Validator();
                $lf = TTnew('UserReportDataListFactory');
                $lf->StartTransaction();
                if (is_numeric($id)) {
                    //Modifying existing object.
                    //Get user object, so we can only modify just changed data for specific records if needed.
                    $lf->getByUserIdAndId($this->getCurrentUserObject()->getId(), $id);
                    if ($lf->getRecordCount() == 1) {
                        //Object exists
                        Debug::Text('User Report Data Exists, deleting record: ', $id, __FILE__, __LINE__, __METHOD__, 10);
                        $lf = $lf->getCurrent();
                    } else {
                        //Object doesn't exist.
                        $primary_validator->isTrue('id', false, TTi18n::gettext('Delete permission denied, report data does not exist'));
                    }
                } else {
                    $primary_validator->isTrue('id', false, TTi18n::gettext('Delete permission denied, report data does not exist'));
                }

                //Debug::Arr($lf, 'AData: ', __FILE__, __LINE__, __METHOD__, 10);

                $is_valid = $primary_validator->isValid();
                if ($is_valid == true) { //Check to see if all permission checks passed before trying to save data.
                    Debug::Text('Attempting to delete user report data...', __FILE__, __LINE__, __METHOD__, 10);
                    $lf->setDeleted(true);

                    $is_valid = $lf->isValid();
                    if ($is_valid == true) {
                        Debug::Text('User Deleted...', __FILE__, __LINE__, __METHOD__, 10);
                        $save_result[$key] = $lf->Save();
                        $validator_stats['valid_records']++;
                    }
                }

                if ($is_valid == false) {
                    Debug::Text('User Report Data is Invalid...', __FILE__, __LINE__, __METHOD__, 10);

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
     * Share or copy report to other users.
     * @param array $user_report_data_ids User Report Data row IDs
     * @param array $destination_user_ids User IDs to copy reports to
     * @return bool
     */
    public function shareUserReportData($user_report_data_ids, $destination_user_ids)
    {
        if (is_numeric($user_report_data_ids)) {
            $user_report_data_ids = array($user_report_data_ids);
        }

        if (is_numeric($destination_user_ids)) {
            $destination_user_ids = array($destination_user_ids);
        }

        Debug::Arr($user_report_data_ids, 'User Report Data IDs: ', __FILE__, __LINE__, __METHOD__, 10);
        Debug::Arr($destination_user_ids, 'Destination User IDs: ', __FILE__, __LINE__, __METHOD__, 10);

        $src_rows = $this->stripReturnHandler($this->getUserReportData(array('filter_data' => array('id' => $user_report_data_ids)), true));
        if (is_array($src_rows) and count($src_rows) > 0) {
            Debug::Arr($src_rows, 'SRC Rows: ', __FILE__, __LINE__, __METHOD__, 10);
            $dst_rows = array();

            $x = 0;
            foreach ($src_rows as $key => $row) {
                unset($src_rows[$key]['id'], $src_rows[$key]['created_date'], $src_rows[$key]['created_by']); //Clear fields that can't be copied
                $src_rows[$key]['name'] = Misc::generateShareName($this->getCurrentUserObject()->getFullName(), $row['name']); //Generate unique name

                $description = null;
                if (isset($row['description']) and $row['description'] != '') {
                    $description = $row['description'] . "\n";
                }
                $src_rows[$key]['description'] = $description . TTi18n::getText('Report shared by') . ' ' . $this->getCurrentUserObject()->getFullName() . ' ' . TTi18n::getText('on') . ' ' . TTDate::getDate('DATE+TIME', time());

                //Should we always disable the default setting?
                //Should we copy any schedules that go along with each saved report? This could cause a lot of issues with mass emails being sent out without intention.

                //Copy to destination users.
                if (is_array($destination_user_ids)) {
                    foreach ($destination_user_ids as $destination_user_id) {
                        $dst_rows[$x] = $src_rows[$key];
                        $dst_rows[$x]['user_id'] = $destination_user_id;
                        $x++;
                    }
                }
            }
            unset($src_rows);
            Debug::Arr($dst_rows, 'DST Rows: ', __FILE__, __LINE__, __METHOD__, 10);

            return $this->setUserReportData($dst_rows); //Save copied rows
        }

        return $this->returnHandler(false);
    }

    /**
     * Get user data for one or more users.
     * @param array $data filter data
     * @return array
     */
    public function getUserReportData($data = null)
    {
        $data = $this->initializeFilterAndPager($data);

        //Only allow getting report data for currently logged in user.
        $data['filter_data']['user_id'] = $this->getCurrentUserObject()->getId();

        Debug::Arr($data, 'Getting User Report Data: ', __FILE__, __LINE__, __METHOD__, 10);

        $ugdlf = TTnew('UserReportDataListFactory');
        $ugdlf->getAPISearchByCompanyIdAndArrayCriteria($this->getCurrentCompanyObject()->getId(), $data['filter_data'], $data['filter_items_per_page'], $data['filter_page'], null, $data['filter_sort']);
        Debug::Text('Record Count: ' . $ugdlf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
        if ($ugdlf->getRecordCount() > 0) {
            $this->setPagerObject($ugdlf);

            $retarr = array();
            foreach ($ugdlf as $ugd_obj) {
                $retarr[] = $ugd_obj->getObjectAsArray($data['filter_columns']);
            }

            return $this->returnHandler($retarr);
        }

        return $this->returnHandler(true);
    }
}
