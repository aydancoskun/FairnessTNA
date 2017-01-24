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
 * @package API\Message
 */
class APIMessageControl extends APIFactory
{
    protected $main_class = 'MessageControlFactory';

    public function __construct()
    {
        parent::__construct(); //Make sure parent constructor is always called.

        return true;
    }

    /**
     * Get default message_control data for creating new message_controles.
     * @return array
     */
    public function getMessageControlDefaultData()
    {
        $company_obj = $this->getCurrentCompanyObject();

        Debug::Text('Getting message_control default data...', __FILE__, __LINE__, __METHOD__, 10);

        $next_available_manual_id = MessageControlListFactory::getNextAvailableManualId($company_obj->getId());

        $data = array(
            'company_id' => $company_obj->getId(),
            'status_id' => 10,
            'manual_id' => $next_available_manual_id,
            'city' => $company_obj->getCity(),
            'country' => $company_obj->getCountry(),
            'province' => $company_obj->getProvince(),
            'work_phone' => $company_obj->getWorkPhone(),
            'fax_phone' => $company_obj->getFaxPhone(),
        );

        return $this->returnHandler($data);
    }

    /**
     * @param string $format
     * @param null $data
     * @param bool $disable_paging
     * @return array|bool
     */
    public function exportMessageControl($format = 'csv', $data = null, $disable_paging = true)
    {
        $result = $this->stripReturnHandler($this->getMessageControl($data, $disable_paging));
        return $this->exportRecords($format, 'export_message', $result, ((isset($data['filter_columns'])) ? $data['filter_columns'] : null));
    }

    /**
     * Get message_control data for one or more message_controles.
     * @param array $data filter data
     * @return array
     */
    public function getMessageControl($data = null, $disable_paging = false)
    {
        if (!$this->getPermissionObject()->Check('message', 'enabled')
            or !($this->getPermissionObject()->Check('message', 'view') or $this->getPermissionObject()->Check('message', 'view_own') or $this->getPermissionObject()->Check('message', 'view_child'))
        ) {
            return $this->getPermissionObject()->PermissionDenied();
        }
        $data = $this->initializeFilterAndPager($data, $disable_paging);

        //No need to check for permission_children, as the logged in user can only view their own messages anyways.
        $data['filter_data']['current_user_id'] = $this->getCurrentUserObject()->getId();

        $blf = TTnew('MessageControlListFactory');
        $blf->getAPISearchByCompanyIdAndArrayCriteria($this->getCurrentCompanyObject()->getId(), $data['filter_data'], $data['filter_items_per_page'], $data['filter_page'], null, $data['filter_sort']);
        Debug::Text('Record Count: ' . $blf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
        if ($blf->getRecordCount() > 0) {
            $this->getProgressBarObject()->start($this->getAMFMessageID(), $blf->getRecordCount());

            $this->setPagerObject($blf);

            $retarr = array();
            foreach ($blf as $b_obj) {
                $retarr[] = $b_obj->getObjectAsArray($data['filter_columns']);

                $this->getProgressBarObject()->set($this->getAMFMessageID(), $blf->getCurrentRow());
            }

            $this->getProgressBarObject()->stop($this->getAMFMessageID());

            return $this->returnHandler($retarr);
        }

        return $this->returnHandler(true); //No records returned.
    }

    /**
     * Get message data for one message or thread.
     * @param array $data filter data
     * @return array
     */
    public function getMessage($data = null, $disable_paging = false)
    {
        if (!$this->getPermissionObject()->Check('message', 'enabled')
            or !($this->getPermissionObject()->Check('message', 'view') or $this->getPermissionObject()->Check('message', 'view_own') or $this->getPermissionObject()->Check('message', 'view_child'))
        ) {
            return $this->getPermissionObject()->PermissionDenied();
        }
        $data = $this->initializeFilterAndPager($data, $disable_paging);

        if (!isset($data['filter_data']['id'])) {
            return $this->returnHandler(true); //No records returned.
        }

        $data['filter_data']['current_user_id'] = $this->getCurrentUserObject()->getId();

        $blf = TTnew('MessageControlListFactory');
        $blf->getAPIMessageByCompanyIdAndArrayCriteria($this->getCurrentCompanyObject()->getId(), $data['filter_data'], $data['filter_items_per_page'], $data['filter_page'], null, $data['filter_sort']);
        Debug::Text('Record Count: ' . $blf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
        if ($blf->getRecordCount() > 0) {
            $this->getProgressBarObject()->start($this->getAMFMessageID(), $blf->getRecordCount());

            $this->setPagerObject($blf);

            $retarr = array();
            foreach ($blf as $b_obj) {
                $retarr[] = $b_obj->getObjectAsArray($data['filter_columns']);

                $this->getProgressBarObject()->set($this->getAMFMessageID(), $blf->getCurrentRow());
            }

            $this->getProgressBarObject()->stop($this->getAMFMessageID());

            return $this->returnHandler($retarr);
        }

        return $this->returnHandler(true); //No records returned.
    }

    /**
     * Get message data attached to a single object.
     * @param array $data filter data
     * @return array
     */
    public function getEmbeddedMessage($data = null, $disable_paging = false)
    {
        if (!$this->getPermissionObject()->Check('message', 'enabled')
            or !($this->getPermissionObject()->Check('message', 'view') or $this->getPermissionObject()->Check('message', 'view_own') or $this->getPermissionObject()->Check('message', 'view_child'))
        ) {
            return $this->getPermissionObject()->PermissionDenied();
        }
        $data = $this->initializeFilterAndPager($data, $disable_paging);

        if (isset($data['filter_data']['object_type_id']) and $data['filter_data']['object_type_id'] == 5) {
            Debug::Text('ERROR: Emails cant be embedded!', __FILE__, __LINE__, __METHOD__, 10);
            return $this->getPermissionObject()->PermissionDenied();
        }

        $type_to_api_map = $this->getOptions('type_to_api_map');
        if (isset($data['filter_data']['object_type_id']) and isset($type_to_api_map[$data['filter_data']['object_type_id']])
            and isset($data['filter_data']['object_id'])
        ) {
            $tmp_apif = TTnew($type_to_api_map[$data['filter_data']['object_type_id']]);
            $get_function = 'get' . str_replace('API', '', $type_to_api_map[$data['filter_data']['object_type_id']]);
            Debug::Text('API Class Name: ' . $type_to_api_map[$data['filter_data']['object_type_id']] . ' GET Function: ' . $get_function, __FILE__, __LINE__, __METHOD__, 10);

            if (method_exists($tmp_apif, $get_function)) {
                $result = $this->stripReturnHandler($tmp_apif->$get_function(array('filter_data' => array('id' => $data['filter_data']['object_id']), 'filter_items_per_page' => 1, 'filter_columns' => array('id' => true))));
                if (!(isset($result[0]) and count($result[0]) > 0)) {
                    Debug::Text('ERROR: Permission denied, unable to find record for supplied object_id...', __FILE__, __LINE__, __METHOD__, 10);
                    return $this->getPermissionObject()->PermissionDenied();
                }
            } else {
                Debug::Text('ERROR: Object Type ID is invalid...', __FILE__, __LINE__, __METHOD__, 10);
                return $this->returnHandler(false); //No records returned.
            }
        } else {
            Debug::Text('Object Type ID not defined...', __FILE__, __LINE__, __METHOD__, 10);
            //MyAccount -> Document doesn't send object_type_id
            //return $this->returnHandler( FALSE ); //No records returned.
        }


        $blf = TTnew('MessageControlListFactory');
        $blf->getByCompanyIDAndUserIdAndObjectTypeAndObject($this->getCurrentCompanyObject()->getId(), $this->getCurrentUserObject()->getId(), $data['filter_data']['object_type_id'], $data['filter_data']['object_id']);
        Debug::Text('Record Count: ' . $blf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
        if ($blf->getRecordCount() > 0) {
            $this->getProgressBarObject()->start($this->getAMFMessageID(), $blf->getRecordCount());

            $this->setPagerObject($blf);

            $retarr = array();
            foreach ($blf as $b_obj) {
                $retarr[] = $b_obj->getObjectAsArray($data['filter_columns']);

                $this->getProgressBarObject()->set($this->getAMFMessageID(), $blf->getCurrentRow());
            }

            $this->getProgressBarObject()->stop($this->getAMFMessageID());

            return $this->returnHandler($retarr);
        }

        return $this->returnHandler(true); //No records returned.
    }

    /**
     * Get options for dropdown boxes.
     * @param string $name Name of options to return, ie: 'columns', 'type', 'status'
     * @param mixed $parent Parent name/ID of options to return if data is in hierarchical format. (ie: Province)
     * @return array
     */
    public function getOptions($name = false, $parent = null)
    {
        if ($name == 'user_columns') {
            $uf = TTnew('UserFactory');
            if ($this->getPermissionObject()->Check('user', 'enabled') and $this->getPermissionObject()->Check('user', 'view')) {
                $retarr = $uf->getOptions('columns');
            } elseif ($this->getPermissionObject()->Check('user', 'enabled') and $this->getPermissionObject()->Check('user', 'view_child')) {
                $retarr = $uf->getOptions('user_child_secure_columns');
            } else {
                $retarr = $uf->getOptions('user_secure_columns');
            }
            return $retarr;
        }

        return parent::getOptions($name, $parent);
    }

    /**
     * Get only the fields that are common across all records in the search criteria. Used for Mass Editing of records.
     * @param array $data filter data
     * @return array
     */
    public function getCommonMessageControlData($data)
    {
        return Misc::arrayIntersectByRow($this->stripReturnHandler($this->getMessageControl($data, true)));
    }

    /**
     * Validate message_control data for one or more message_controles.
     * @param array $data message_control data
     * @return array
     */
    public function validateMessageControl($data)
    {
        return $this->setMessageControl($data, true);
    }

    /**
     * Set message_control data for one or more message_controls.
     * @param array $data message_control data
     * @return array
     */
    public function setMessageControl($data, $validate_only = false, $ignore_warning = true)
    {
        $validate_only = (bool)$validate_only;
        $ignore_warning = (bool)$ignore_warning;

        if (!is_array($data)) {
            return $this->returnHandler(false);
        }

        if (!$this->getPermissionObject()->Check('message', 'enabled')
            or !($this->getPermissionObject()->Check('message', 'edit') or $this->getPermissionObject()->Check('message', 'edit_own') or $this->getPermissionObject()->Check('message', 'edit_child') or $this->getPermissionObject()->Check('message', 'add'))
        ) {
            return $this->getPermissionObject()->PermissionDenied();
        }

        if ($validate_only == true) {
            Debug::Text('Validating Only!', __FILE__, __LINE__, __METHOD__, 10);
        }

        extract($this->convertToMultipleRecords($data));
        Debug::Text('Received data for: ' . $total_records . ' MessageControls', __FILE__, __LINE__, __METHOD__, 10);
        Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

        $validator_stats = array('total_records' => $total_records, 'valid_records' => 0);
        $validator = $save_result = false;
        if (is_array($data) and $total_records > 0) {
            $this->getProgressBarObject()->start($this->getAMFMessageID(), $total_records);

            foreach ($data as $key => $row) {
                $primary_validator = new Validator();
                $lf = TTnew('MessageControlListFactory');
                $lf->StartTransaction();
                if (isset($row['id']) and $row['id'] > 0) {
                    $primary_validator->isTrue('permission', false, TTi18n::gettext('Edit permission denied'));
                } else {
                    //Adding new object, check ADD permissions.
                    $primary_validator->isTrue('permission', $this->getPermissionObject()->Check('message', 'add'), TTi18n::gettext('Add permission denied'));

                    //Security check, make sure any data passed as to_user_id is within the list of users available.
                    if (!isset($row['to_user_id']) or is_array($row['to_user_id']) and count($row['to_user_id']) == 0) {
                        $row['to_user_id'] = false;
                    }

                    if (!isset($row['object_id'])) {
                        $row['object_id'] = false;
                    }

                    if (isset($row['object_type_id']) and $row['object_type_id'] != 5) {
                        Debug::Text('Adding message to request, determining our own to_user_ids...', __FILE__, __LINE__, __METHOD__, 10);
                        //When replying to a request, find all users who have contributed messages to the request and make those the to_user_ids.
                        $mslf = TTNew('MessageSenderListFactory');
                        $mslf->getByCompanyIdAndObjectTypeAndObjectAndNotUser($this->getCurrentCompanyObject()->getId(), (int)$row['object_type_id'], (int)$row['object_id'], $this->getCurrentUserObject()->getId());
                        if ($mslf->getRecordCount() > 0) {
                            $row['to_user_id'] = array();
                            foreach ($mslf as $ms_obj) {
                                $row['to_user_id'][] = $ms_obj->getUser();
                            }
                            $row['to_user_id'] = array_unique($row['to_user_id']);
                            Debug::Arr($row['to_user_id'], 'New Recipients: ', __FILE__, __LINE__, __METHOD__, 10);
                        } else {
                            $hlf = TTnew('HierarchyListFactory');
                            $rlf = TTnew('RequestListFactory');
                            $rlf->getByIdAndCompanyId((int)$row['object_id'], $this->getCurrentCompanyObject()->getId());
                            if ($rlf->getRecordCount() == 1) {
                                $object_type_id = $rlf->getHierarchyTypeId((int)$rlf->getCurrent()->getType());
                                $row['to_user_id'] = $hlf->getHierarchyParentByCompanyIdAndUserIdAndObjectTypeID($this->getCurrentCompanyObject()->getId(), $this->getCurrentUserObject()->getId(), $object_type_id, true, false); //Immediate parents only.
                                Debug::Arr($row['to_user_id'], 'No one has replied yet, send to immediate superiors again...', __FILE__, __LINE__, __METHOD__, 10);
                            } else {
                                $row['to_user_id'] = array();
                            }
                            unset($hlf, $rlf, $object_type_id);
                        }
                    } else {
                        Debug::Text('Sending regular message, filter to_user_ids based on permissions...', __FILE__, __LINE__, __METHOD__, 10);
                        $row['to_user_id'] = Misc::arrayColumn($this->stripReturnHandler($this->getUser(array('filter_data' => array('id' => (array)$row['to_user_id']), 'filter_columns' => array('id' => true)), true)), 'id');
                    }
                }
                Debug::Arr($row, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

                if ($validate_only == true) {
                    $lf->Validator->setValidateOnly($validate_only);
                }

                $is_valid = $primary_validator->isValid($ignore_warning);
                if ($is_valid == true) { //Check to see if all permission checks passed before trying to save data.
                    Debug::Text('Setting object data...', __FILE__, __LINE__, __METHOD__, 10);

                    $lf->setObjectFromArray($row);

                    //Force current User ID as the FROM user.
                    $lf->setFromUserId($this->getCurrentUserObject()->getId());

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
     * Get limited (first/last name) user data for sending messages
     * @param array $data filter data
     * @param boolean $disable_paging disables paging and returns all records.
     * @return array
     */
    public function getUser($data, $disable_paging = false)
    {
        $data = $this->initializeFilterAndPager($data, $disable_paging);

        if ($this->getPermissionObject()->Check('message', 'send_to_any')) {
            //Show all employees
            $data['filter_data']['permission_children_ids'] = null;
        } else {
            //Only allow sending to supervisors OR children.
            $hlf = TTnew('HierarchyListFactory');

            //FIXME: For supervisors, we may need to include supervisors at the same level
            // Also how to handle cases where there are no To: recipients to select from.

            //Get Parents
            $request_parent_level_user_ids = $hlf->getHierarchyParentByCompanyIdAndUserIdAndObjectTypeID($this->getCurrentCompanyObject()->getId(), $this->getCurrentUserObject()->getId(), array(1010, 1020, 1030, 1040, 1100), false, false);
            //Debug::Arr( $request_parent_level_user_ids, 'Request Parent Level Ids', __FILE__, __LINE__, __METHOD__, 10);

            //Get Children, in case the current user is a superior.
            $request_child_level_user_ids = $hlf->getHierarchyChildrenByCompanyIdAndUserIdAndObjectTypeID($this->getCurrentCompanyObject()->getId(), $this->getCurrentUserObject()->getId(), array(1010, 1020, 1030, 1040, 1100));
            //Debug::Arr( $request_child_level_user_ids, 'Request Child Level Ids', __FILE__, __LINE__, __METHOD__, 10);

            $request_user_ids = array_merge((array)$request_parent_level_user_ids, (array)$request_child_level_user_ids);
            //Debug::Arr( $request_user_ids, 'User Ids', __FILE__, __LINE__, __METHOD__, 10);

            $data['filter_data']['permission_children_ids'] = $request_user_ids;
            //Debug::Arr($data['filter_data']['permission_children_ids'], 'Permission Section: '. $permission_section .' Child IDs: ', __FILE__, __LINE__, __METHOD__, 10);
        }

        $data['filter_data']['status_id'] = 10; //Only include active employees.

        //Make sure the columns being asked for are available.
        $data['filter_columns'] = Misc::arrayIntersectByKey(array_merge(array('id'), array_keys(Misc::trimSortPrefix($this->getOptions('user_columns')))), $data['filter_columns']);

        if (count($data['filter_columns']) == 0) { //Make sure we always default to some columns.
            Debug::Text('Overriding Filter Columns...', __FILE__, __LINE__, __METHOD__, 10);
            $data['filter_columns'] = array('id' => true, 'first_name' => true, 'last_name' => true);
        }

        //Debug::Arr($this->getOptions('user_columns'), 'Final User Columns: ', __FILE__, __LINE__, __METHOD__, 10);
        //Debug::Arr($data['filter_columns'], 'Final Filter Columns: ', __FILE__, __LINE__, __METHOD__, 10);

        $ulf = TTnew('UserListFactory');
        $ulf->getAPISearchByCompanyIdAndArrayCriteria($this->getCurrentCompanyObject()->getId(), $data['filter_data'], $data['filter_items_per_page'], $data['filter_page'], null, $data['filter_sort']);
        Debug::Text('Record Count: ' . $ulf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
        if ($ulf->getRecordCount() > 0) {
            $this->getProgressBarObject()->start($this->getAMFMessageID(), $ulf->getRecordCount());

            $this->setPagerObject($ulf);

            $retarr = array();
            foreach ($ulf as $u_obj) {
                $user_data = $u_obj->getObjectAsArray($data['filter_columns'], $data['filter_data']['permission_children_ids']);

                $retarr[] = $user_data;

                $this->getProgressBarObject()->set($this->getAMFMessageID(), $ulf->getCurrentRow());
            }

            $this->getProgressBarObject()->stop($this->getAMFMessageID());

            //Debug::Arr($retarr, 'User Data: ', __FILE__, __LINE__, __METHOD__, 10);

            return $this->returnHandler($retarr);
        }

        return $this->returnHandler(false);
    }

    /**
     * Delete one or more message_controls.
     * @param array $data message_control data
     * @return array
     */
    public function deleteMessageControl($data, $folder_id = false)
    {
        if (is_numeric($data)) {
            $data = array($data);
        }

        if (!is_array($data)) {
            return $this->returnHandler(false);
        }

        if ($folder_id == '') {
            return $this->returnHandler(false);
        }

        if (!$this->getPermissionObject()->Check('message', 'enabled')
            or !($this->getPermissionObject()->Check('message', 'delete') or $this->getPermissionObject()->Check('message', 'delete_own') or $this->getPermissionObject()->Check('message', 'delete_child'))
        ) {
            return $this->getPermissionObject()->PermissionDenied();
        }

        Debug::Text('Received data for: ' . count($data) . ' MessageControls', __FILE__, __LINE__, __METHOD__, 10);
        Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

        $total_records = count($data);
        $validator = $save_result = false;
        $validator_stats = array('total_records' => $total_records, 'valid_records' => 0);
        if (is_array($data) and $total_records > 0) {
            $this->getProgressBarObject()->start($this->getAMFMessageID(), $total_records);

            foreach ($data as $key => $id) {
                $primary_validator = new Validator();

                if ($folder_id == 10) { //Inbox
                    $lf = TTnew('MessageRecipientListFactory');
                } else { //Sent
                    $lf = TTnew('MessageSenderListFactory');
                }
                $lf->StartTransaction();
                if (is_numeric($id)) {
                    //Modifying existing object.
                    //Get message_control object, so we can only modify just changed data for specific records if needed.
                    if ($folder_id == 10) { //Inbox
                        $lf->getByCompanyIdAndUserIdAndMessageSenderId($this->getCurrentCompanyObject()->getId(), $this->getCurrentUserObject()->getId(), $id);
                    } else { //Sent
                        $lf->getByCompanyIdAndUserIdAndId($this->getCurrentCompanyObject()->getId(), $this->getCurrentUserObject()->getId(), $id);
                    }
                    //$lf->getByIdAndCompanyId( $id, $this->getCurrentCompanyObject()->getId() );

                    if ($lf->getRecordCount() == 1) {
                        //Object exists, check edit permissions
                        if ($this->getPermissionObject()->Check('message', 'delete')
                            or ($this->getPermissionObject()->Check('message', 'delete_own'))
                        ) { //Remove is_owner() checks, as the list factory filter it for us.
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
     * Copy one or more message_controles.
     * @param array $data message_control IDs
     * @return array
     */
    public function copyMessageControl($data)
    {
        if (is_numeric($data)) {
            $data = array($data);
        }

        if (!is_array($data)) {
            return $this->returnHandler(false);
        }

        Debug::Text('Received data for: ' . count($data) . ' MessageControls', __FILE__, __LINE__, __METHOD__, 10);
        Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);

        $src_rows = $this->stripReturnHandler($this->getMessageControl(array('filter_data' => array('id' => $data)), true));
        if (is_array($src_rows) and count($src_rows) > 0) {
            Debug::Arr($src_rows, 'SRC Rows: ', __FILE__, __LINE__, __METHOD__, 10);
            foreach ($src_rows as $key => $row) {
                unset($src_rows[$key]['id']); //Clear fields that can't be copied
                //$src_rows[$key]['name'] = Misc::generateCopyName( $row['name'] ); //Generate unique name
            }
            unset($row); //code standards
            //Debug::Arr($src_rows, 'bSRC Rows: ', __FILE__, __LINE__, __METHOD__, 10);

            return $this->setMessageControl($src_rows); //Save copied rows
        }

        return $this->returnHandler(false);
    }

    /**
     * Check if there are unread messages for the current user.
     * @return number of unread messages.
     */
    public function isNewMessage()
    {
        $mclf = new MessageControlListFactory();
        $unread_messages = $mclf->getNewMessagesByCompanyIdAndUserId($this->getCurrentCompanyObject()->getId(), $this->getCurrentUserObject()->getId());
        Debug::text('UnRead Messages: ' . $unread_messages, __FILE__, __LINE__, __METHOD__, 10);

        return $this->returnHandler($unread_messages);
    }

    public function markRecipientMessageAsRead($mark_read_message_ids)
    {
        return $this->returnHandler(MessageControlFactory::markRecipientMessageAsRead($this->getCurrentCompanyObject()->getId(), $this->getCurrentUserObject()->getId(), $mark_read_message_ids));
    }
}
