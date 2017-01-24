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
 * @package Modules\Request
 */
class RequestFactory extends Factory
{
    public $user_date_obj = null;
        protected $table = 'request'; //PK Sequence name
protected $pk_sequence_name = 'request_id_seq';

    public function _getFactoryOptions($name, $parent = null)
    {
        $retval = null;
        switch ($name) {
            case 'type':
                $retval = array(
                    10 => TTi18n::gettext('Missed Punch'),                //request_punch
                    20 => TTi18n::gettext('Punch Adjustment'),            //request_punch_adjust
                    30 => TTi18n::gettext('Absence (incl. Vacation)'),    //request_absence
                    40 => TTi18n::gettext('Schedule Adjustment'),        //request_schedule
                    100 => TTi18n::gettext('Other'),                    //request_other
                );
                break;
            case 'status':
                $retval = array(
                    10 => TTi18n::gettext('INCOMPLETE'),
                    20 => TTi18n::gettext('OPEN'),
                    30 => TTi18n::gettext('PENDING'), //Used to be "Pending Authorizion"
                    40 => TTi18n::gettext('AUTHORIZATION OPEN'),
                    50 => TTi18n::gettext('AUTHORIZED'), //Used to be "Active"
                    55 => TTi18n::gettext('DECLINED'), //Used to be "AUTHORIZATION DECLINED"
                    60 => TTi18n::gettext('DISABLED')
                );
                break;
            case 'columns':
                $retval = array(

                    '-1010-first_name' => TTi18n::gettext('First Name'),
                    '-1020-last_name' => TTi18n::gettext('Last Name'),
                    '-1060-title' => TTi18n::gettext('Title'),
                    '-1070-user_group' => TTi18n::gettext('Group'),
                    '-1080-default_branch' => TTi18n::gettext('Branch'),
                    '-1090-default_department' => TTi18n::gettext('Department'),

                    '-1110-date_stamp' => TTi18n::gettext('Date'),
                    '-1120-status' => TTi18n::gettext('Status'),
                    '-1130-type' => TTi18n::gettext('Type'),

                    '-2000-created_by' => TTi18n::gettext('Created By'),
                    '-2010-created_date' => TTi18n::gettext('Created Date'),
                    '-2020-updated_by' => TTi18n::gettext('Updated By'),
                    '-2030-updated_date' => TTi18n::gettext('Updated Date'),
                );
                break;
            case 'list_columns':
                $retval = Misc::arrayIntersectByKey(array('date_stamp', 'status', 'type'), Misc::trimSortPrefix($this->getOptions('columns')));
                break;
            case 'default_display_columns': //Columns that are displayed by default.
                $retval = array(
                    'first_name',
                    'last_name',
                    'type',
                    'date_stamp',
                    'status',
                );
                break;
            case 'unique_columns': //Columns that are unique, and disabled for mass editing.
                $retval = array();
                break;
            case 'linked_columns': //Columns that are linked together, mainly for Mass Edit, if one changes, they all must.
                $retval = array();
                break;
        }

        return $retval;
    }

    public function _getVariableToFunctionMap($data)
    {
        $variable_function_map = array(
            'id' => 'ID',
            //'user_date_id' => 'UserDateID',
            'user_id' => 'User',
            'date_stamp' => 'DateStamp',
            'pay_period_id' => 'PayPeriod',

            //'user_id' => FALSE,

            'first_name' => false,
            'last_name' => false,
            'default_branch' => false,
            'default_department' => false,
            'user_group' => false,
            'title' => false,

            'date_stamp' => 'DateStamp',
            'type_id' => 'Type',
            'type' => false,
            'hierarchy_type_id' => 'HierarchyTypeId',
            'status_id' => 'Status',
            'status' => false,
            'authorized' => 'Authorized',
            'authorization_level' => 'AuthorizationLevel',
            'message' => 'Message',

            'request_schedule' => false,

            'deleted' => 'Deleted',
        );
        return $variable_function_map;
    }

    public function setUser($id)
    {
        $id = trim($id);

        $ulf = TTnew('UserListFactory');

        //Need to be able to support user_id=0 for open shifts. But this can cause problems with importing punches with user_id=0.
        if ($this->Validator->isResultSetWithRows('user',
            $ulf->getByID($id),
            TTi18n::gettext('Invalid User')
        )
        ) {
            $this->data['user_id'] = $id;

            return true;
        }

        return false;
    }

    public function getPayPeriod()
    {
        if (isset($this->data['pay_period_id'])) {
            return (int)$this->data['pay_period_id'];
        }

        return false;
    }

    public function getTypeIdFromHierarchyTypeId($type_id)
    {
        //Make sure we support an array of type_ids.
        if (is_array($type_id)) {
            foreach ($type_id as $request_type_id) {
                $retval[] = ($request_type_id >= 1000 and $request_type_id < 2000) ? ((int)$request_type_id - 1000) : (int)$request_type_id;
            }
        } else {
            $retval = ($request_type_id >= 1000 and $request_type_id < 2000) ? ((int)$type_id - 1000) : (int)$type_id;
            Debug::text('Hierarchy Type ID: ' . $type_id . ' Request Type ID: ' . $retval, __FILE__, __LINE__, __METHOD__, 10);
        }

        return $retval;
    }

    public function setType($value)
    {
        $value = trim($value);

        if ($this->Validator->inArrayKey('type',
            $value,
            TTi18n::gettext('Incorrect Type'),
            $this->getOptions('type'))
        ) {
            $this->data['type_id'] = $value;

            return true;
        }

        return false;
    }

    public function setAuthorized($bool)
    {
        $this->data['authorized'] = $this->toBool($bool);

        return true;
    }

    public function getAuthorizationLevel()
    {
        if (isset($this->data['authorization_level'])) {
            return $this->data['authorization_level'];
        }

        return false;
    }

    public function setMessage($text)
    {
        $text = trim($text);

        //Flex interface validates the message too soon, make it skip a 0 length message when only validating.
        if ($this->Validator->getValidateOnly() == true and $text == '') {
            $minimum_length = 0;
        } else {
            $minimum_length = 5;
        }

        if ($this->Validator->isLength('message',
            $text,
            TTi18n::gettext('Invalid message length'),
            $minimum_length,
            1024)
        ) {
            $this->tmp_data['message'] = htmlspecialchars($text);

            return true;
        }

        return false;
    }

    //Convert hierarchy type_ids back to request type_ids.

    public function Validate($ignore_warning = true)
    {
        if ($this->isNew() == true
            and $this->Validator->hasError('message') == false
            and $this->getMessage() == false
            and $this->Validator->getValidateOnly() == false
        ) {
            $this->Validator->isTRUE('message',
                false,
                TTi18n::gettext('Invalid message length'));
        }

        if ($this->getDateStamp() == false
            and $this->Validator->hasError('date_stamp') == false
        ) {
            $this->Validator->isTRUE('date_stamp',
                false,
                TTi18n::gettext('Incorrect Date') . ' (c)');
        }

        if (!is_object($this->getUserObject())) {
            $this->Validator->isTRUE('user_id',
                false,
                TTi18n::gettext('Invalid Employee'));
        }

        //Check to make sure this user has superiors to send a request too, otherwise we can't save the request.
        if (is_object($this->getUserObject())) {
            $hlf = TTnew('HierarchyListFactory');
            $request_parent_level_user_ids = $hlf->getHierarchyParentByCompanyIdAndUserIdAndObjectTypeID($this->getUserObject()->getCompany(), $this->getUser(), $this->getHierarchyTypeId(), true, false); //Request - Immediate parents only.
            Debug::Arr($request_parent_level_user_ids, 'Check for Superiors: ', __FILE__, __LINE__, __METHOD__, 10);

            if (!is_array($request_parent_level_user_ids) or count($request_parent_level_user_ids) == 0) {
                $this->Validator->isTRUE('message',
                    false,
                    TTi18n::gettext('No supervisors are assigned to you at this time, please try again later'));
            }
        }

        if ($this->getDeleted() == true and in_array($this->getStatus(), array(50, 55))) {
            $this->Validator->isTRUE('status_id',
                false,
                TTi18n::gettext('Unable to delete requests after they have been authorized/declined'));
        }

        return true;
    }

    public function getMessage()
    {
        if (isset($this->tmp_data['message'])) {
            return $this->tmp_data['message'];
        }

        return false;
    }

    public function getDateStamp($raw = false)
    {
        if (isset($this->data['date_stamp'])) {
            if ($raw === true) {
                return $this->data['date_stamp'];
            } else {
                return TTDate::strtotime($this->data['date_stamp']);
            }
        }

        return false;
    }

    public function getUserObject()
    {
        return $this->getGenericObject('UserListFactory', $this->getUser(), 'user_obj');
    }

    public function getUser()
    {
        if (isset($this->data['user_id'])) {
            return (int)$this->data['user_id'];
        }

        return false;
    }

    public function getHierarchyTypeId($type_id = null)
    {
        if ($type_id == '') {
            $type_id = $this->getType();
        }

        if ($type_id == false) {
            Debug::text('ERROR: Type ID is FALSE', __FILE__, __LINE__, __METHOD__, 10);
            return false;
        }

        //Make sure we support an array of type_ids.
        if (is_array($type_id)) {
            foreach ($type_id as $request_type_id) {
                $retval[] = ((int)$request_type_id + 1000);
            }
        } else {
            $retval = ((int)$type_id + 1000);
            Debug::text('Request Type ID: ' . $type_id . ' Hierarchy Type ID: ' . $retval, __FILE__, __LINE__, __METHOD__, 10);
        }

        return $retval;
    }

    public function getType()
    {
        if (isset($this->data['type_id'])) {
            return (int)$this->data['type_id'];
        }

        return false;
    }

    public function getStatus()
    {
        if (isset($this->data['status_id'])) {
            return (int)$this->data['status_id'];
        }

        return false;
    }

    public function preSave()
    {
        //If this is a new request, find the current authorization level to assign to it.
        // isNew should be a force check due to request schedule child table
        if ($this->isNew(true) == true) {
            if ($this->getStatus() == false) {
                $this->setStatus(30); //Pending Auth.
            }

            $hierarchy_highest_level = AuthorizationFactory::getInitialHierarchyLevel((is_object($this->getUserObject()) ? $this->getUserObject()->getCompany() : 0), (is_object($this->getUserObject()) ? $this->getUserObject()->getID() : 0), $this->getHierarchyTypeId());
            $this->setAuthorizationLevel($hierarchy_highest_level);
        }
        if ($this->getAuthorized() == true) {
            $this->setAuthorizationLevel(0);
        }

        return true;
    }

    public function setStatus($value)
    {
        $value = trim($value);

        if ($this->Validator->inArrayKey('status',
            $value,
            TTi18n::gettext('Incorrect Status'),
            $this->getOptions('status'))
        ) {
            $this->data['status_id'] = $value;

            return true;
        }

        return false;
    }

    public function setAuthorizationLevel($value)
    {
        $value = (int)trim($value);

        if ($value < 0) {
            $value = 0;
        }

        if ($this->Validator->isNumeric('authorization_level',
            $value,
            TTi18n::gettext('Incorrect authorization level'))
        ) {
            $this->data['authorization_level'] = $value;

            return true;
        }

        return false;
    }

    public function getAuthorized()
    {
        if (isset($this->data['authorized']) and $this->data['authorized'] !== null) {
            return $this->fromBool($this->data['authorized']);
        }

        return null;
    }

    public function postSave()
    {
        //Save message here after we have the request_id.
        if ($this->getMessage() !== false) {
            $mcf = TTnew('MessageControlFactory');
            $mcf->StartTransaction();

            $hlf = TTnew('HierarchyListFactory');
            $request_parent_level_user_ids = $hlf->getHierarchyParentByCompanyIdAndUserIdAndObjectTypeID($this->getUserObject()->getCompany(), $this->getUser(), $this->getHierarchyTypeId(), true, false); //Request - Immediate parents only.
            Debug::Arr($request_parent_level_user_ids, 'Sending message to current direct Superiors: ', __FILE__, __LINE__, __METHOD__, 10);

            $mcf = TTnew('MessageControlFactory');
            $mcf->setFromUserId($this->getUser());
            $mcf->setToUserId($request_parent_level_user_ids);
            $mcf->setObjectType(50); //Messages don't break out request types like hierarchies do.
            $mcf->setObject($this->getID());
            $mcf->setParent(0);
            $mcf->setSubject(Option::getByKey($this->getType(), $this->getOptions('type')) . ' ' . TTi18n::gettext('request from') . ': ' . $this->getUserObject()->getFullName(true));
            $mcf->setBody($this->getMessage());
            $mcf->setEnableEmailMessage(false); //Dont email message notification, send authorization notice instead.

            if ($mcf->isValid()) {
                $mcf->Save();
                $mcf->CommitTransaction();
            } else {
                $mcf->FailTransaction();
            }

            //Send initial Pending Authorization email to superiors. -- This should only happen on first save by the regular employee.
            AuthorizationFactory::emailAuthorizationOnInitialObjectSave($this->getUser(), $this->getHierarchyTypeId(), $this->getId());
        }


        if ($this->getDeleted() == true) {
            Debug::Text('Delete authorization history for this request...' . $this->getId(), __FILE__, __LINE__, __METHOD__, 10);
            $alf = TTnew('AuthorizationListFactory');
            $alf->getByObjectTypeAndObjectId($this->getHierarchyTypeId(), $this->getId());
            foreach ($alf as $authorization_obj) {
                Debug::Text('Deleting authorization ID: ' . $authorization_obj->getID(), __FILE__, __LINE__, __METHOD__, 10);
                $authorization_obj->setDeleted(true);
                $authorization_obj->Save();
            }
        }

        return true;
    }

    public function setObjectFromArray($data)
    {
        if (is_array($data)) {
            /*
            if ( isset($data['user_id']) AND $data['user_id'] != ''
                    AND isset($data['date_stamp']) AND $data['date_stamp'] != '' ) {
                Debug::text('Setting User Date ID based on User ID:'. $data['user_id'] .' Date Stamp: '. $data['date_stamp'], __FILE__, __LINE__, __METHOD__, 10);
                $this->setUserDate( $data['user_id'], TTDate::parseDateTime( $data['date_stamp'] ) );
            } elseif ( isset( $data['user_date_id'] ) AND $data['user_date_id'] > 0 ) {
                Debug::text(' Setting UserDateID: '. $data['user_date_id'], __FILE__, __LINE__, __METHOD__, 10);
                $this->setUserDateID( $data['user_date_id'] );
            } else {
                Debug::text(' NOT CALLING setUserDate or setUserDateID!', __FILE__, __LINE__, __METHOD__, 10);
            }
            */

            if (isset($data['status_id']) and $data['status_id'] == '') {
                unset($data['status_id']);
                $this->setStatus(30); //Pending authorization
            }
            if (isset($data['user_date_id']) and $data['user_date_id'] == '') {
                unset($data['user_date_id']);
            }

            $variable_function_map = $this->getVariableToFunctionMap();
            foreach ($variable_function_map as $key => $function) {
                if (isset($data[$key])) {
                    $function = 'set' . $function;
                    switch ($key) {
                        case 'date_stamp':
                            $this->setDateStamp(TTDate::parseDateTime($data['date_stamp']));
                            break;
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

    public function setDateStamp($epoch)
    {
        $epoch = (int)$epoch;

        if ($this->Validator->isDate('date_stamp',
            $epoch,
            TTi18n::gettext('Incorrect date') . ' (a)')
        ) {
            if ($epoch > 0) {
                $this->data['date_stamp'] = $epoch;

                $this->setPayPeriod(); //Force pay period to be set as soon as the date is.
                return true;
            } else {
                $this->Validator->isTRUE('date_stamp',
                    false,
                    TTi18n::gettext('Incorrect date') . ' (b)');
            }
        }

        return false;
    }

    public function setPayPeriod($id = null)
    {
        $id = trim($id);

        if ($id == null) {
            $id = (int)PayPeriodListFactory::findPayPeriod($this->getUser(), $this->getDateStamp());
        }

        $pplf = TTnew('PayPeriodListFactory');

        //Allow NULL pay period, incase its an absence or something in the future.
        //Cron will fill in the pay period later.
        if (
            $id == 0
            or
            $this->Validator->isResultSetWithRows('pay_period',
                $pplf->getByID($id),
                TTi18n::gettext('Invalid Pay Period')
            )
        ) {
            $this->data['pay_period_id'] = $id;

            return true;
        }

        return false;
    }

    public function getObjectAsArray($include_columns = null, $permission_children_ids = false)
    {
        $variable_function_map = $this->getVariableToFunctionMap();
        if (is_array($variable_function_map)) {
            foreach ($variable_function_map as $variable => $function_stub) {
                if ($include_columns == null or (isset($include_columns[$variable]) and $include_columns[$variable] == true)) {
                    $function = 'get' . $function_stub;
                    switch ($variable) {
                        case 'first_name':
                        case 'last_name':
                        case 'title':
                        case 'user_group':
                        case 'default_branch':
                        case 'default_department':
                        case 'user_id':
                            $data[$variable] = $this->getColumn($variable);
                            break;
                        case 'message': //Message is attached in the message factory, so we can't return it here.
                            break;
                        case 'status':
                        case 'type':
                            $function = 'get' . $variable;
                            if (method_exists($this, $function)) {
                                $data[$variable] = Option::getByKey($this->$function(), $this->getOptions($variable));
                            }
                            break;
                        case 'date_stamp':
                            $data[$variable] = TTDate::getAPIDate('DATE', $this->getDateStamp());
                            break;
                        default:
                            if (method_exists($this, $function)) {
                                $data[$variable] = $this->$function();
                            }
                            break;
                    }
                }
            }
            $this->getPermissionColumns($data, $this->getColumn('user_id'), $this->getCreatedBy(), $permission_children_ids, $include_columns);
            $this->getCreatedAndUpdatedColumns($data, $include_columns);
        }

        return $data;
    }

    public function addLog($log_action)
    {
        return TTLog::addEntry($this->getId(), $log_action, TTi18n::getText('Request - Type') . ': ' . Option::getByKey($this->getType(), $this->getOptions('type')), null, $this->getTable(), $this);
    }
}
