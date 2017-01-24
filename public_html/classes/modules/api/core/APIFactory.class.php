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
 * @package API\Core
 */
abstract class APIFactory
{
    public $data = array();

    protected $main_class_obj = null;

    protected $AMF_message_id = null;

    protected $pager_obj = null;

    protected $current_company = null;
    protected $current_user = null;
    protected $current_user_prefs = null;
    protected $permission = null;

    protected $progress_bar_obj = null;

    public function __construct()
    {
        global $current_company, $current_user, $current_user_prefs;

        $this->current_company = $current_company;
        $this->current_user = $current_user;
        $this->current_user_prefs = $current_user_prefs;

        $this->permission = new Permission();
        return true;
    }

    public function setPagerObject($lf)
    {
        if (is_object($lf)) {
            $this->pager_obj = new Pager($lf);
        }

        return true;
    }

    //Returns the AMF messageID for each individual call.

    public function setMainClassObject($obj)
    {
        if (is_object($obj)) {
            $this->main_class_obj = $obj;
            return true;
        }

        return false;
    }

    public function initializeFilterAndPager($data, $disable_paging = false)
    {
        //If $data is not an array, it will trigger PHP errors, so force it that way and report an error so we can troubleshoot if needed.
        //This will avoid the PHP fatal errors that look like the below, but it doesn't actually fix the root cause, which is currently unknown.
        //		DEBUG [L0228] [00014ms] Array: [Function](): Arguments: (Size: 114)
        //		array(4) {
        //					["POST_/api/json/api_php?Class"]=> string(18) "APIUserGenericData"
        //					["Method"]=> string(18) "getUserGenericData"
        //					["v"]=> string(1) "2"
        //					["MessageID"]=> string(26) "5dd90933-f97c-9001-9efe-e2"
        //		}
        //		DEBUG [L0139] [00030ms] Array: Debug::ErrorHandler(): Raw POST Request:
        //		string(114) "POST /api/json/api.php?Class=APIUserGenericData&Method=getUserGenericData&v=2&MessageID=5dd90933-f97c-9001-9efe-e2"
        if (is_array($data) == false) {
            Debug::Arr($data, 'ERROR: Input data is not an array: ', __FILE__, __LINE__, __METHOD__, 10);
            $data = array();
        }

        //Preset values for LF search function.
        $data = Misc::preSetArrayValues($data, array('filter_data', 'filter_columns', 'filter_items_per_page', 'filter_page', 'filter_sort'), null);

        if ($disable_paging == false and (int)$data['filter_items_per_page'] <= 0) { //Used to check $data['filter_items_per_page'] === NULL
            $data['filter_items_per_page'] = $this->getCurrentUserPreferenceObject()->getItemsPerPage();
        }

        if ($disable_paging == true) {
            $data['filter_items_per_page'] = $data['filter_page'] = false;
        }

        //Debug::Arr($data, 'Getting Data: ', __FILE__, __LINE__, __METHOD__, 10);

        return $data;
    }

    public function getCurrentUserPreferenceObject()
    {
        if (is_object($this->current_user_prefs)) {
            return $this->current_user_prefs;
        }
        return false;
    }

    public function handlePermissionFilterColumns($filter_columns, $allowed_columns)
    {
        //Always allow these columns to be returned.
        $allowed_columns['id'] = true;
        $allowed_columns['is_owner'] = true;
        $allowed_columns['is_child'] = true;

        if (is_array($filter_columns)) {
            $retarr = Misc::arrayIntersectByKey($allowed_columns, $filter_columns);
        } else {
            $retarr = $allowed_columns;
        }

        //If no valid columns are being returned, revert back to allowed columns.
        //Never return *NULL* or a blank array from here, as that will allow all columns to be displayed.
        if (!is_array($retarr)) {
            //Return all allowed columns
            $retarr = $allowed_columns;
        }

        return $retarr;
    }

    public function convertToSingleRecord($data)
    {
        if (isset($data[0]) and !isset($data[1])) {
            return $data[0];
        } else {
            return $data;
        }
    }

    public function convertToMultipleRecords($data)
    {
        if (isset($data[0]) and is_array($data[0])) {
            $retarr = array(
                'data' => $data,
                'total_records' => count($data)
            );
        } else {
            $retarr = array(
                'data' => array(0 => $data),
                'total_records' => 1
            );
        }

        //Debug::Arr($retarr, 'Array: ', __FILE__, __LINE__, __METHOD__, 10);

        return $retarr;
    }

    /**
     * downloaded a result_set as a csv.
     * @param $format
     * @param $file_name
     * @param $result
     * @return array|bool
     */
    public function exportRecords($format, $file_name, $result, $filter_columns)
    {
        if (isset($result[0]) and is_array($result[0]) and is_array($filter_columns) and count($filter_columns) > 0) {
            $columns = Misc::arrayIntersectByKey(array_keys($filter_columns), Misc::trimSortPrefix($this->getOptions('columns')));

            $file_extension = $format;
            $mime_type = 'application/' . $format;
            $output = '';

            if ($format == 'csv') {
                $output = Misc::Array2CSV($result, $columns, false);
            }

            $this->getProgressBarObject()->stop($this->getAMFMessageID());
            if ($output !== false) {
                Misc::APIFileDownload($file_name . '.' . $file_extension, $mime_type, $output);
            } else {
                return $this->returnHandler(false, 'VALIDATION', TTi18n::getText('ERROR: No data to export...'));
            }
        }

        return $this->returnHandler(true); //No records returned.
    }

    public function getOptions($name = false, $parent = null)
    {
        if ($name != '') {
            if (method_exists($this->getMainClassObject(), 'getOptions')) {
                return $this->getMainClassObject()->getOptions($name, $parent);
            } else {
                Debug::Text('getOptions() function does not exist for object: ' . get_class($this->getMainClassObject()), __FILE__, __LINE__, __METHOD__, 10);
            }
        } else {
            Debug::Text('ERROR: Name not provided, unable to return data...', __FILE__, __LINE__, __METHOD__, 10);
        }

        return false;
    }

    public function getMainClassObject()
    {
        if (!is_object($this->main_class_obj)) {
            $this->main_class_obj = new $this->main_class;
            return $this->main_class_obj;
        } else {
            return $this->main_class_obj;
        }

        return false;
    }

    //Allow storing the main class object persistently in memory, so we can build up other variables to help out things like getOptions()
    //Mainly used for the APIReport class.

    public function getProgressBarObject()
    {
        if (!is_object($this->progress_bar_obj)) {
            $this->progress_bar_obj = new ProgressBar();
        }

        return $this->progress_bar_obj;
    }

    public function getAMFMessageID()
    {
        if ($this->AMF_message_id != null) {
            return $this->AMF_message_id;
        }
        return false;
    }

    public function setAMFMessageID($id)
    {
        if ($id != '') {
            global $amf_message_id; //Make this global so Debug() class can reference it on Shutdown()
            $this->AMF_message_id = $amf_message_id = $id;
            return true;
        }

        return false;
    }

    //In cases where data can be displayed in just a list_view (dropdown boxes), ie: branch, department, job, task in In/Out punch view
    //restrict the dropdown box to just a subset of columns, so not all data is shown.

    public function returnHandler($retval = true, $code = false, $description = false, $details = false, $validator_stats = false, $user_generic_status_batch_id = false, $request_data = false)
    {
        if ($this->getProtocolVersion() == 1) {
            if ($retval === false or ($retval === true and $code !== false) or ($user_generic_status_batch_id !== false)) {
                if ($retval === false) {
                    if ($code == '') {
                        $code = 'GENERAL';
                    }
                    if ($description == '') {
                        $description = 'Insufficient data to carry out action';
                    }
                } elseif ($retval === true) {
                    if ($code == '') {
                        $code = 'SUCCESS';
                    }
                }

                $validator_stats = Misc::preSetArrayValues($validator_stats, array('total_records', 'valid_records', 'invalids_records'), 0);

                $retarr = array(
                    'api_retval' => $retval,
                    'api_details' => array(
                        'code' => $code,
                        'description' => $description,
                        'record_details' => array(
                            'total' => $validator_stats['total_records'],
                            'valid' => $validator_stats['valid_records'],
                            'invalid' => ($validator_stats['total_records'] - $validator_stats['valid_records'])
                        ),
                        'user_generic_status_batch_id' => $user_generic_status_batch_id,
                        'details' => $details,
                    )
                );

                if ($retval === false) {
                    Debug::Arr($retarr, 'returnHandler v1 ERROR: ' . (int)$retval, __FILE__, __LINE__, __METHOD__, 10);
                }

                //Handle progress bar here, make sure they are stopped and if an error occurs display the error.
                if ($retval === false) {
                    $this->getProgressBarObject()->error($this->getAMFMessageID(), $description);
                } else {
                    $this->getProgressBarObject()->stop($this->getAMFMessageID());
                }

                return $retarr;
            }

            //No errors, or additional information, return unmodified data.
            return $retval;
        } else {
            if ($retval === false) {
                if ($code == '') {
                    $code = 'GENERAL';
                }
                if ($description == '') {
                    $description = 'Insufficient data to carry out action';
                }
            } elseif ($retval === true) {
                if ($code == '') {
                    $code = 'SUCCESS';
                }
            }

            $validator_stats = Misc::preSetArrayValues($validator_stats, array('total_records', 'valid_records', 'invalids_records'), 0);

            $retarr = array(
                'api_retval' => $retval,
                'api_details' => array(
                    'code' => $code,
                    'description' => $description,
                    'record_details' => array(
                        'total' => $validator_stats['total_records'],
                        'valid' => $validator_stats['valid_records'],
                        'invalid' => ($validator_stats['total_records'] - $validator_stats['valid_records'])
                    ),
                    'user_generic_status_batch_id' => $user_generic_status_batch_id,
                    //Allows the API to modify the original request data to send back to the UI for notifying the user.
                    //We would like to implement validation on non-set*() calls as well perhaps?
                    'request' => $request_data,
                    'pager' => $this->getPagerData(),
                    'details' => $details,
                )
            );

            if ($retval === false) {
                Debug::Arr($retarr, 'returnHandler v2 ERROR: ' . (int)$retval, __FILE__, __LINE__, __METHOD__, 10);
            }

            //Handle progress bar here, make sure they are stopped and if an error occurs display the error.
            if ($retval === false) {
                $this->getProgressBarObject()->start($this->getAMFMessageID(), 9999, 9999, $description);
            } else {
                $this->getProgressBarObject()->stop($this->getAMFMessageID());
            }

            //Debug::Arr($retarr, 'returnHandler: '. (int)$retval, __FILE__, __LINE__, __METHOD__, 10);
            return $retarr;
        }
    }

    public function getProtocolVersion()
    {
        if (isset($_GET['v']) and $_GET['v'] != '') {
            return (int)$_GET['v'];     //1=Initial, 2=Always return detailed
        }

        return 1;
    }

    public function getPagerData()
    {
        if (is_object($this->pager_obj)) {
            return $this->pager_obj->getPageVariables();
        }

        return false;
    }

    public function getNextInsertID()
    {
        return $this->getMainClassObject()->getNextInsertId();
    }

    public function getPermissionChildren()
    {
        return $this->getPermissionObject()->getPermissionHierarchyChildren($this->getCurrentCompanyObject()->getId(), $this->getCurrentUserObject()->getId());
        /*
        $hlf = TTnew( 'HierarchyListFactory' );
        $permission_children_ids = $hlf->getHierarchyChildrenByCompanyIdAndUserIdAndObjectTypeID( $this->getCurrentCompanyObject()->getId(), $this->getCurrentUserObject()->getId(), 100 );

        Debug::Arr($permission_children_ids, 'Permission Child IDs: ', __FILE__, __LINE__, __METHOD__, 10);

        return $permission_children_ids;
        */
    }

    public function getPermissionObject()
    {
        if (is_object($this->permission)) {
            return $this->permission;
        }
        return false;
    }

    //Controls returning information to client in a standard format.
    //FIXME: Need to return the original request (with any modified values due to restrictions/validation issues)
    //		 Also need to return paging data variables here too, as JSON can't make multiple calls.
    //		 In order to do this we need to always return a special data structure that includes this information.
    //		 static function returnHandler( $retval = TRUE, $args = array( 'code' => FALSE, 'description' => FALSE, 'details' = FALSE, 'validator_stats' => FALSE, 'user_generic_status_batch_id' => FALSE ) ) {
    //		 The above will require too many changes, just add two more variables at the end, as it will only really be used by API->get*() functions.
    //FIXME: Use a requestHandler() to handle all input requests, so we can parse out things like validate_only, ignore_warning (for user acknowledgable warnings) and handling all parameter parsing in a central place.
    //		 static function returnHandler( $retval = TRUE, $code = FALSE, $description = FALSE, $details = FALSE, $validator_stats = FALSE, $user_generic_status_batch_id = FALSE, $request = FALSE, $pager = FALSE ) {

    public function getCurrentCompanyObject()
    {
        if (is_object($this->current_company)) {
            return $this->current_company;
        }
        return false;
    }

    public function getCurrentUserObject()
    {
        if (is_object($this->current_user)) {
            return $this->current_user;
        }
        return false;
    }

    //Bridge to main class getOptions factory.

    public function stripReturnHandler($retarr)
    {
        if (isset($retarr['api_retval'])) {
            return $retarr['api_retval'];
        }

        return $retarr;
    }

    //Bridge to main class getVariableToFunctionMap factory.

    public function getVariableToFunctionMap($name, $parent = null)
    {
        return $this->getMainClassObject()->getVariableToFunctionMap($name, $parent);
    }

    //Take a API ReturnHandler array and pulls out the Validation errors/warnings to be merged back into another Validator
    //This is useful for calling one API function from another one when their are sub-classes.
    public function convertAPIReturnHandlerToValidatorObject($api_retarr, $validator_obj = false)
    {
        if (is_object($validator_obj)) {
            $validator = $validator_obj;
        } else {
            $validator = new Validator;
        }

        if (isset($api_retarr['api_retval']) and $api_retarr['api_retval'] === false and isset($api_retarr['api_details']['details'])) {
            foreach ($api_retarr['api_details']['details'] as $tmp_validation_error_label => $validation_row) {
                if (isset($validation_row['error'])) {
                    foreach ($validation_row['error'] as $validation_error_label => $validation_error_msg) {
                        $validator->Error($validation_error_label, $validation_error_msg[0]);
                    }
                }

                if (isset($validation_row['warning'])) {
                    foreach ($validation_row['warning'] as $validation_warning_label => $validation_warning_msg) {
                        $validator->Warning($validation_warning_label, $validation_warning_msg[0]);
                    }
                }

                //Before warnings were added, validation errors were just directly in the details array, so try to handle those here.
                if (!isset($validation_row['error']) and !isset($validation_row['warning'])) {
                    foreach ($validation_row as $validation_error_msg) {
                        $validator->Error($tmp_validation_error_label, $validation_error_msg);
                    }
                }
            }
        }

        return $validator;
    }

    public function setValidationArray($primary_validator, $secondary_validator, $tertiary_validator = false)
    {
        //Handle primary validator first
        $validator = array();

        if ($this->getProtocolVersion() == 1) { //Don't return any warnings and therefore don't put errors in its own array element.
            if ($primary_validator->isError() === true) {
                $validator = $primary_validator->getErrorsArray();
            } else {
                //Check secondary validator for errors.
                if ($secondary_validator->Validator->isError() === true) {
                    $validator = $secondary_validator->Validator->getErrorsArray();
                } else {
                    //Check tertiary validator for errors.
                    if ($tertiary_validator->isError() === true) {
                        $validator = $tertiary_validator->getErrorsArray();
                    }
                }
            }
        } else {
            if ($primary_validator->isError() === true) {
                $validator['error'] = $primary_validator->getErrorsArray();
            } else {
                //Check for primary validator warnings next.
                if ($primary_validator->isWarning() === true) {
                    $validator['warning'] = $primary_validator->Validator->getWarningsArray();
                } else {
                    //Check secondary validator for errors.
                    if ($secondary_validator->Validator->isError() === true) {
                        $validator['error'] = $secondary_validator->Validator->getErrorsArray();
                    } else {
                        //Check secondary validator for warnings.
                        if ($secondary_validator->Validator->isWarning() === true) {
                            $validator['warning'] = $secondary_validator->Validator->getWarningsArray();
                        } else {
                            //Check tertiary validator for errors.
                            if ($tertiary_validator->isError() === true) {
                                $validator['error'] = $tertiary_validator->getErrorsArray();
                            } else {
                                //Check tertiary validator for warnings.
                                if ($tertiary_validator->isWarning() === true) {
                                    $validator['warning'] = $tertiary_validator->getWarningsArray();
                                }
                            }
                        }
                    }
                }
            }
        }

        if (count($validator) > 0) {
            return $validator;
        }

        return false;
    }


    public function handleRecordValidationResults($validator, $validator_stats, $key, $save_result)
    {
        if ($validator_stats['valid_records'] > 0 and $validator_stats['total_records'] == $validator_stats['valid_records']) {
            if ($validator_stats['total_records'] == 1) {
                return $this->returnHandler($save_result[$key]); //Single valid record
            } else {
                return $this->returnHandler(true, 'SUCCESS', TTi18n::getText('MULTIPLE RECORDS SAVED'), $save_result, $validator_stats); //Multiple valid records
            }
        } else {
            return $this->returnHandler(false, 'VALIDATION', TTi18n::getText('INVALID DATA'), $validator, $validator_stats);
        }
    }
}
