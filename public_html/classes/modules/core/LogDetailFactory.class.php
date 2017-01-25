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
 * @package Core
 */
class LogDetailFactory extends Factory
{
    protected $table = 'system_log_detail';
    protected $pk_sequence_name = 'system_log_detail_id_seq'; //PK Sequence name

    public function getSystemLog()
    {
        return (int)$this->data['system_log_id'];
    }

    public function setSystemLog($id)
    {
        $id = trim($id);

        //Allow NULL ids.
        if ($id == '' or $id == null) {
            $id = 0;
        }

        $llf = TTnew('LogListFactory');

        if ($id == 0
            or $this->Validator->isResultSetWithRows('user',
                $llf->getByID($id),
                TTi18n::gettext('System log is invalid')
            )
        ) {
            $this->data['system_log_id'] = $id;

            return true;
        }

        return false;
    }

    public function getField()
    {
        if (isset($this->data['field'])) {
            return $this->data['field'];
        }

        return false;
    }

    public function setField($value)
    {
        $value = trim($value);

        if ($this->Validator->isString('field',
            $value,
            TTi18n::gettext('Field is invalid'))
        ) {
            $this->data['field'] = $value;

            return true;
        }

        return false;
    }

    public function getOldValue()
    {
        if (isset($this->data['old_value'])) {
            return $this->data['old_value'];
        }

        return false;
    }

    public function setOldValue($text)
    {
        $text = trim($text);

        if (
        $this->Validator->isLength('old_value',
            $text,
            TTi18n::gettext('Old value is invalid'),
            0,
            1024)

        ) {
            $this->data['old_value'] = $text;

            return true;
        }

        return false;
    }

    public function getNewValue()
    {
        if (isset($this->data['new_value'])) {
            return $this->data['new_value'];
        }

        return false;
    }

    public function setNewValue($text)
    {
        $text = trim($text);

        if (
        $this->Validator->isLength('new_value',
            $text,
            TTi18n::gettext('New value is invalid'),
            0,
            1024)

        ) {
            $this->data['new_value'] = $text;

            return true;
        }

        return false;
    }

    //When comparing the two arrays, if there are sub-arrays, we need to *always* include those, as we can't actually
    //diff the two, because they are already saved by the time we get to this function, so there will never be any changes to them.
    //We don't want to include sub-arrays, as the sub-classes should handle the logging themselves.
    public function diffData($arr1, $arr2)
    {
        if (!is_array($arr1) or !is_array($arr2)) {
            return false;
        }

        $retarr = false;
        foreach ($arr1 as $key => $val) {
            if (!isset($arr2[$key]) or is_array($val) or is_array($arr2[$key]) or ($arr2[$key] != $val)) {
                $retarr[$key] = $val;
            }
        }

        return $retarr;
    }

    public function addLogDetail($action_id, $system_log_id, $object)
    {
        $start_time = microtime(true);

        //Only log detail records on add, edit, delete, undelete
        //Logging data on Add/Delete/UnDelete, or anything but Edit will greatly bloat the database, on the order of tens of thousands of entries
        //per day. The issue though is its nice to know exactly what data was originally added, then what was edited, and what was finally deleted.
        //We may need to remove logging for added data, but leave it for edit/delete, so we know exactly what data was deleted.
        if (!in_array($action_id, array(10, 20, 30, 31, 40))) {
            Debug::text('Invalid Action ID: ' . $action_id, __FILE__, __LINE__, __METHOD__, 10);
            return false;
        }

        if ($system_log_id > 0 and is_object($object)) {
            //Remove "Plugin" from the end of the class name incase plugins are enabled.
            $class = str_replace('Plugin', '', get_class($object));
            Debug::text('System Log ID: ' . $system_log_id . ' Class: ' . $class, __FILE__, __LINE__, __METHOD__, 10);
            //Debug::Arr($object->data, 'Object Data: ', __FILE__, __LINE__, __METHOD__, 10);
            //Debug::Arr($object->old_data, 'Object Old Data: ', __FILE__, __LINE__, __METHOD__, 10);

            //Only store raw data changes, don't convert *_ID fields to full text names, it bloats the storage and slows down the logging process too much.
            //We can do the conversion when someone actually looks at the audit logs, which will obviously be quite rare in comparison. Even though this will
            //require quite a bit more code to handle.
            //There are also translation issues if we convert IDs to text at this point. However there could be continuity problems if ID values change in the future.
            $new_data = $object->data;
            //Debug::Arr($new_data, 'New Data Arr: ', __FILE__, __LINE__, __METHOD__, 10);
            if ($action_id == 20) { //Edit
                if (method_exists($object, 'setObjectFromArray')) {
                    //Run the old data back through the objects own setObjectFromArray(), so any necessary values can be parsed.

                    if (isset($object->old_data) and isset($object->old_data['password'])) { //Password from old_data is encrypted, and if put back into the class always causes validation error.
                        $object->old_data['password'] = null;
                    }

                    $tmp_class = new $class;

                    //Since RecurringScheduleTemplates can change created_by,
                    //we need to make sure it doesn't appear in audit logs for classes that don't allow it to be changed.
                    if (isset($new_data['created_by']) and method_exists($tmp_class, '_getVariableToFunctionMap')) {
                        $variable_to_function_map = $tmp_class->getVariableToFunctionMap();
                        if (!isset($variable_to_function_map['created_by'])) {
                            $object->old_data['created_by'] = $new_data['created_by'];
                        }
                        unset($variable_to_function_map);
                    }

                    $tmp_class->setObjectFromArray($object->old_data);
                    $old_data = $tmp_class->data;
                    unset($tmp_class);
                } else {
                    $old_data = $object->old_data;
                }

                //We don't want to include any sub-arrays, as those classes should take care of their own logging, even though it may be slower in some cases.
                $diff_arr = array_diff_assoc((array)$new_data, (array)$old_data);
            } elseif ($action_id == 30) { //Delete
                $old_data = array();
                if (method_exists($object, 'setObjectFromArray')) {
                    //Run the old data back through the objects own setObjectFromArray(), so any necessary values can be parsed.
                    $tmp_class = new $class;
                    $tmp_class->setObjectFromArray($object->data);
                    $diff_arr = $tmp_class->data;
                    unset($tmp_class);
                } else {
                    $diff_arr = $object->data;
                }
            } else { //Add
                //Debug::text('Not editing, skipping the diff process...', __FILE__, __LINE__, __METHOD__, 10);
                //No need to store data that is added, as its already in the database, and if it gets changed or deleted we store it then.
                $old_data = array();
                $diff_arr = $object->data;
            }
            //Debug::Arr($old_data, 'Old Data Arr: ', __FILE__, __LINE__, __METHOD__, 10);

            //Handle class specific fields.
            switch ($class) {
                case 'UserFactory':
                case 'UserListFactory':
                    unset(
                        $diff_arr['labor_standard_industry'],
                        $diff_arr['password'],
                        $diff_arr['phone_password'],
                        $diff_arr['password_reset_key'],
                        $diff_arr['password_updated_date'],
                        $diff_arr['last_login_date'],
                        $diff_arr['full_name'],
                        $diff_arr['first_name_metaphone'],
                        $diff_arr['last_name_metaphone'],
                        $diff_arr['ibutton_id'],
                        $diff_arr['finger_print_1'],
                        $diff_arr['finger_print_2'],
                        $diff_arr['finger_print_3'],
                        $diff_arr['finger_print_4'],
                        $diff_arr['finger_print_1_updated_date'],
                        $diff_arr['finger_print_2_updated_date'],
                        $diff_arr['finger_print_3_updated_date'],
                        $diff_arr['finger_print_4_updated_date']
                    );
                    break;
                case 'PayPeriodScheduleFactory':
                case 'PayPeriodScheduleListFactory':
                    unset(
                        $diff_arr['primary_date_ldom'],
                        $diff_arr['primary_transaction_date_ldom'],
                        $diff_arr['primary_transaction_date_bd'],
                        $diff_arr['secondary_date_ldom'],
                        $diff_arr['secondary_transaction_date_ldom'],
                        $diff_arr['secondary_transaction_date_bd']
                    );
                    break;
                case 'PayPeriodFactory':
                case 'PayPeriodListFactory':
                    unset(
                        $diff_arr['is_primary']
                    );
                    break;
                case 'PayStubEntryFactory':
                case 'PayStubEntryListFactory':
                    unset(
                        $diff_arr['pay_stub_id']
                    );
                    break;
                case 'StationFactory':
                case 'StationListFactory':
                    unset(
                        $diff_arr['last_poll_date'],
                        $diff_arr['last_push_date'],
                        $diff_arr['last_punch_time_stamp'],
                        $diff_arr['last_partial_push_date'],
                        $diff_arr['mode_flag'], //This is changed often for some reason, would be nice to audit it though.
                        $diff_arr['work_code_definition'],
                        $diff_arr['allowed_date']
                    );
                    break;
                case 'ScheduleFactory':
                case 'ScheduleListFactory':
                    unset(
                        $diff_arr['recurring_schedule_template_control_id'],
                        $diff_arr['replaced_id']
                    );
                    break;
                case 'PunchFactory':
                case 'PunchListFactory':
                    unset(
                        $diff_arr['user_id'], //Set by PunchControlFactory instead.
                        $diff_arr['actual_time_stamp'],
                        $diff_arr['original_time_stamp'],
                        $diff_arr['punch_control_id'],
                        $diff_arr['station_id']
                    );
                    break;
                case 'PunchControlFactory':
                case 'PunchControlListFactory':
                    unset(
                        $diff_arr['date_stamp'], //Logged in Punch Factory instead.
                        $diff_arr['overlap'],
                        $diff_arr['actual_total_time']
                    );
                    break;
                case 'ExceptionPolicyFactory':
                case 'ExceptionPolicyListFactory':
                    unset(
                        $diff_arr['enable_authorization']
                    );
                    break;
                case 'GEOFenceFactory':
                case 'GEOFenceListFactory':
                    if ($this->getDatabaseType() === 'mysql') {
                        unset(
                            $diff_arr['geo_circle'],
                            $diff_arr['geo_polygon']
                        );
                    }
                    break;
                case 'AccrualFactory':
                case 'AccrualListFactory':
                    unset(
                        $diff_arr['user_date_total_id']
                    );
                    break;
                case 'JobItemFactory':
                case 'JobItemListFactory':
                    unset(
                        $diff_arr['type_id'],
                        $diff_arr['department_id']
                    );
                    break;
                case 'ClientContactFactory':
                case 'ClientContactListFactory':
                    unset(
                        $diff_arr['password'],
                        $diff_arr['password_reset_key'],
                        $diff_arr['password_reset_date']
                    );
                    break;
                case 'UserReviewFactory':
                case 'UserReviewListFactory':
                    unset(
                        $diff_arr['user_review_control_id']
                    );
                    break;
                case 'ClientPaymentFactory':
                case 'ClientPaymentListFactory':
                    break;
                case 'JobApplicantFactory':
                case 'JobApplicantListFactory':
                    unset(
                        $diff_arr['password'],
                        $diff_arr['password_reset_key'],
                        $diff_arr['password_reset_date'],
                        $diff_arr['first_name_metaphone'],
                        $diff_arr['last_name_metaphone']
                        //$diff_arr['longitude'],
                        //$diff_arr['latitude']
                    );
                    break;
            }

            //Ignore specific columns here, like updated_date, updated_by, etc...
            unset(
                //These fields should never change, and therefore don't need to be recorded.
                $diff_arr['id'],
                $diff_arr['company_id'],

                //UserDateID controls which user things like schedules are assigned too, which is critical in the audit log.
                $diff_arr['user_date_id'], //UserDateTotal, Schedule, PunchControl, etc...

                $diff_arr['name_metaphone'],

                //General fields to skip
                $diff_arr['created_date'],
                //$diff_arr['created_by'], //Need to audit created_by, because it can change on some records like RecurringScheduleTemplateControl
                //$diff_arr['created_by_id'],
                $diff_arr['updated_date'],
                $diff_arr['updated_by'],
                $diff_arr['updated_by_id'],
                $diff_arr['deleted_date'],
                $diff_arr['deleted_by'],
                $diff_arr['deleted_by_id'],
                $diff_arr['deleted']
            );

            //Debug::Arr($diff_arr, 'Array Diff: ', __FILE__, __LINE__, __METHOD__, 10);
            if (is_array($diff_arr) and count($diff_arr) > 0) {
                $ph = array();
                $data = array();
                foreach ($diff_arr as $field => $value) {
                    $old_value = null;
                    if (isset($old_data[$field])) {
                        $old_value = $old_data[$field];
                        if (is_bool($old_value) and $old_value === false) {
                            $old_value = null;
                        } elseif (is_array($old_value)) {
                            //$old_value = serialize($old_value);
                            //If the old value is an array, replace it with NULL because it will always match the NEW value too.
                            $old_value = null;
                        }
                    }

                    $new_value = $new_data[$field];
                    if (is_bool($new_value) and $new_value === false) {
                        $new_value = null;
                    } elseif (is_array($new_value)) {
                        $new_value = serialize($new_value);
                    }

                    //Debug::Text('Old Value: '. $old_value .' New Value: '. $new_value, __FILE__, __LINE__, __METHOD__, 10);
                    if (!($old_value == '' and $new_value == '')) {
                        $ph[] = (int)$system_log_id;
                        $ph[] = $field;
                        $ph[] = $new_value;
                        $ph[] = $old_value;
                        $data[] = '(?, ?, ?, ?)';
                    }
                }
                unset($value); //code standards

                if (empty($data) == false) {
                    //Save data in a single SQL query.
                    $query = 'INSERT INTO ' . $this->getTable() . '(SYSTEM_LOG_ID, FIELD, NEW_VALUE, OLD_VALUE) VALUES' . implode(',', $data);
                    //Debug::Text('Query: '. $query, __FILE__, __LINE__, __METHOD__, 10);
                    $this->db->Execute($query, $ph);

                    Debug::Text('Logged detail records in: ' . (microtime(true) - $start_time), __FILE__, __LINE__, __METHOD__, 10);

                    return true;
                }
            }
        }

        Debug::Text('Not logging detail records, likely no data changed in: ' . (microtime(true) - $start_time) . 's', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    //This table doesn't have any of these columns, so overload the functions.
    public function getDeleted()
    {
        return false;
    }

    public function setDeleted($bool)
    {
        return false;
    }

    public function getCreatedDate()
    {
        return false;
    }

    public function setCreatedDate($epoch = null)
    {
        return false;
    }

    public function getCreatedBy()
    {
        return false;
    }

    public function setCreatedBy($id = null)
    {
        return false;
    }

    public function getUpdatedDate()
    {
        return false;
    }

    public function setUpdatedDate($epoch = null)
    {
        return false;
    }

    public function getUpdatedBy()
    {
        return false;
    }

    public function setUpdatedBy($id = null)
    {
        return false;
    }


    public function getDeletedDate()
    {
        return false;
    }

    public function setDeletedDate($epoch = null)
    {
        return false;
    }

    public function getDeletedBy()
    {
        return false;
    }

    public function setDeletedBy($id = null)
    {
        return false;
    }

    public function preSave()
    {
        if ($this->getDate() === false) {
            $this->setDate();
        }

        return true;
    }
}
