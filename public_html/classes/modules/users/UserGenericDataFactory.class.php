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
 * @package Modules\Users
 */
class UserGenericDataFactory extends Factory
{
    protected $table = 'user_generic_data';
    protected $pk_sequence_name = 'user_generic_data_id_seq'; //PK Sequence name

    public static function getSearchFormData($saved_search_id, $sort_column)
    {
        global $current_user;

        $retarr = array();

        $ugdlf = TTnew('UserGenericDataListFactory');
        if (isset($saved_search_id) and $saved_search_id != 0 and $saved_search_id != '') {
            $ugdlf->getByUserIdAndId($current_user->getId(), $saved_search_id);
        } else {
            $ugdlf->getByUserIdAndScriptAndDefault($current_user->getId(), self::handleScriptName($_SERVER['SCRIPT_NAME']));
        }

        if ($ugdlf->getRecordCount() > 0) {
            $ugd_obj = $ugdlf->getCurrent();
            Debug::Text('Found Search Criteria for Saved Search ID: ' . $ugd_obj->getId() . ' Sort Column: ' . $sort_column, __FILE__, __LINE__, __METHOD__, 10);

            $retarr['saved_search_id'] = $ugd_obj->getId();
            $retarr['filter_data'] = $ugd_obj->getData();
            //Debug::Arr($retarr['filter_data'], 'Filter Data: ', __FILE__, __LINE__, __METHOD__, 10);
            unset($ugd_obj);

            Debug::Text('aSort Column: ' . $sort_column, __FILE__, __LINE__, __METHOD__, 10);
            if ($sort_column == '' and isset($retarr['filter_data']['sort_column']) and $retarr['filter_data']['sort_column'] != '') {
                $retarr['sort_column'] = Misc::trimSortPrefix($retarr['filter_data']['sort_column']);
                $retarr['sort_order'] = $retarr['filter_data']['sort_order'];
                Debug::Text('bSort Column: ' . $retarr['sort_column'], __FILE__, __LINE__, __METHOD__, 10);
            }
        }

        return $retarr;
    }

    public static function searchFormDataHandler($action, $filter_data, $redirect_url)
    {
        global $current_company, $current_user;

        if ($action == '') {
            return false;
        }

        if (!is_array($filter_data)) {
            return false;
        }

        $saved_search_id = false;

        $ugdlf = TTnew('UserGenericDataListFactory');
        $ugdf = TTnew('UserGenericDataFactory');
        if ($action == 'search_form_update' or $action == 'search_form_save') {
            Debug::Text('Save Report!', __FILE__, __LINE__, __METHOD__, 10);

            if ($action == 'search_form_update' and isset($filter_data['saved_search_id']) and $filter_data['saved_search_id'] != '' and $filter_data['saved_search_id'] != 0) {
                $ugdlf->getByUserIdAndId($current_user->getId(), $filter_data['saved_search_id']);
                if ($ugdlf->getRecordCount() > 0) {
                    $ugdf = $ugdlf->getCurrent();
                }
                $ugdf->setID($filter_data['saved_search_id']);
            }

            $ugdf->setCompany($current_company->getId());
            $ugdf->setUser($current_user->getId());
            $ugdf->setScript(self::handleScriptName($_SERVER['SCRIPT_NAME']));

            if (isset($filter_data['saved_search_name']) and $filter_data['saved_search_name'] != '') {
                $ugdf->setName($filter_data['saved_search_name']);
            }

            $ugdf->setData($filter_data);
            $ugdf->setDefault(false);
        } elseif ($action == 'search_form_clear' or $action == 'search_form_search') {
            Debug::Text('Search!', __FILE__, __LINE__, __METHOD__, 10);

            //When they click search it saves the criteria as the default, so it always loads from then on.
            //Unless cleared.
            $ugdlf->getByUserIdAndScriptAndDefault($current_user->getId(), self::handleScriptName($_SERVER['SCRIPT_NAME']), true);
            if ($ugdlf->getRecordCount() > 0) {
                $ugdf = $ugdlf->getCurrent();
                $saved_search_id = $filter_data['saved_search_id'] = $ugdf->getId();
            }
            $ugdf->setCompany($current_company->getId());
            $ugdf->setUser($current_user->getId());
            $ugdf->setScript(self::handleScriptName($_SERVER['SCRIPT_NAME']));
            $ugdf->setName(TTi18n::gettext('-Default-'));
            $ugdf->setData($filter_data);
            $ugdf->setDefault(true);
        } elseif (isset($filter_data['saved_search_id']) and $filter_data['saved_search_id'] != '') {
            $ugdlf->getByUserIdAndId($current_user->getId(), $filter_data['saved_search_id']);
            if ($ugdlf->getRecordCount() > 0) {
                $ugd_obj = $ugdlf->getCurrent();

                $ugd_obj->setDeleted(true);
                $ugd_obj->Save();
            }

            Redirect::Page($redirect_url);

            return true;
        }

        if (is_object($ugdf) and $ugdf->isValid()) {
            $ugf_id = $ugdf->Save();

            if (is_numeric($ugf_id)) {
                $saved_search_id = $ugf_id;
            } elseif ($ugf_id === true) {
                $saved_search_id = $filter_data['saved_search_id'];
            }
            unset($ugf_id);
        }

        return $saved_search_id;
    }

    public static function getReportFormData($saved_search_id)
    {
        global $current_user;

        $retarr = array();

        $ugdlf = TTnew('UserGenericDataListFactory');
        if (isset($saved_search_id) and $saved_search_id != 0 and $saved_search_id != '') {
            $ugdlf->getByUserIdAndId($current_user->getId(), $saved_search_id);
        } else {
            $ugdlf->getByUserIdAndScriptAndDefault($current_user->getId(), self::handleScriptName($_SERVER['SCRIPT_NAME']));
        }

        if ($ugdlf->getRecordCount() > 0) {
            $ugd_obj = $ugdlf->getCurrent();
            Debug::Text('Found Search Criteria for Saved Search ID: ' . $ugd_obj->getId(), __FILE__, __LINE__, __METHOD__, 10);

            $retarr['saved_search_id'] = $ugd_obj->getId();
            $retarr['filter_data'] = $ugd_obj->getData();
            //Debug::Arr($retarr['filter_data'], 'Filter Data: ', __FILE__, __LINE__, __METHOD__, 10);
            unset($ugd_obj);
        }

        return $retarr;
    }

    public static function reportFormDataHandler($action, $filter_data, $generic_data, $redirect_url)
    {
        global $current_company, $current_user;

        if ($action == '') {
            return false;
        }

        if (!is_array($generic_data)) {
            return false;
        }

        $saved_report_id = false;

        $ugdlf = TTnew('UserGenericDataListFactory');
        $ugdf = TTnew('UserGenericDataFactory');
        if ($action == 'save' or $action == 'update') {
            Debug::Text('Save Report!', __FILE__, __LINE__, __METHOD__, 10);

            if (isset($generic_data['id']) and $generic_data['id'] != '' and $generic_data['id'] != 0) {
                $ugdlf->getByUserIdAndId($current_user->getId(), $generic_data['id']);
                if ($ugdlf->getRecordCount() > 0) {
                    $ugdf = $ugdlf->getCurrent();
                }
                $ugdf->setID($generic_data['id']);
            }

            $ugdf->setCompany($current_company->getId());
            $ugdf->setUser($current_user->getId());
            $ugdf->setScript(self::handleScriptName($_SERVER['SCRIPT_NAME']));

            if (isset($generic_data['name']) and $generic_data['name'] != '') {
                $ugdf->setName($generic_data['name']);
            }

            $ugdf->setData($filter_data);
            if (isset($generic_data['is_default'])) {
                $ugdf->setDefault(true);
            }
        } elseif ($action == 'delete' and isset($generic_data['id']) and $generic_data['id'] != '') {
            $ugdlf->getByUserIdAndId($current_user->getId(), $generic_data['id']);
            if ($ugdlf->getRecordCount() > 0) {
                $ugd_obj = $ugdlf->getCurrent();

                $ugd_obj->setDeleted(true);
                $ugd_obj->Save();
            }

            Redirect::Page($redirect_url);

            return true;
        }

        if (is_object($ugdf) and $ugdf->isValid()) {
            $ugf_id = $ugdf->Save();

            if (is_numeric($ugf_id)) {
                $saved_report_id = $ugf_id;
            } elseif ($ugf_id === true) {
                $saved_report_id = $generic_data['id'];
            }
            unset($ugf_id);
        }

        return $saved_report_id;
    }

    public function _getVariableToFunctionMap($data)
    {
        $variable_function_map = array(
            'id' => 'ID',
            'company_id' => 'Company',
            'user_id' => 'User',
            'script' => 'Script',
            'name' => 'Name',
            'is_default' => 'Default',
            'data' => 'Data',
            'deleted' => 'Deleted',
        );
        return $variable_function_map;
    }

    public function setCompany($id)
    {
        $id = trim($id);

        $clf = TTnew('CompanyListFactory');

        if ($this->Validator->isResultSetWithRows('company',
            $clf->getByID($id),
            TTi18n::gettext('Invalid Company')
        )
        ) {
            $this->data['company_id'] = $id;

            return true;
        }

        return false;
    }

    public function setScript($value)
    {
        //Strip out double slashes, as sometimes those occur and they cause the saved settings to not appear.
        $value = self::handleScriptName(trim($value));
        if ($this->Validator->isLength('script',
            $value,
            TTi18n::gettext('Invalid script'),
            1, 250)
        ) {
            $this->data['script'] = $value;

            return true;
        }

        return false;
    }

    public static function handleScriptName($script_name)
    {
        return str_replace('//', '/', $script_name);
    }

    public function setName($name)
    {
        $name = trim($name);

        if ($this->Validator->isLength('name',
                $name,
                TTi18n::gettext('Invalid name'),
                1, 100)
            and
            $this->Validator->isTrue('name',
                $this->isUniqueName($name),
                TTi18n::gettext('Name already exists'))

        ) {
            $this->data['name'] = $name;

            return true;
        }

        return false;
    }

    public function isUniqueName($name)
    {
        if ($this->getCompany() == false) {
            return false;
        }

        //Allow no user_id to be set yet, as that would be company generic data.

        if ($this->getScript() == false) {
            return false;
        }

        $name = trim($name);
        if ($name == '') {
            return false;
        }

        $ph = array(
            'company_id' => (int)$this->getCompany(),
            'script' => $this->getScript(),
            'name' => TTi18n::strtolower($name),
        );

        $query = 'select id from ' . $this->getTable() . '
					where
						company_id = ?
						AND script = ?
						AND lower(name) = ? ';
        if ($this->getUser() != '') {
            $query .= ' AND user_id = ' . (int)$this->getUser();
        } else {
            $query .= ' AND ( user_id = 0 OR user_id is NULL )';
        }
        $query .= ' AND deleted = 0';

        $name_id = $this->db->GetOne($query, $ph);
        Debug::Arr($name_id, 'Unique Name: ' . $name, __FILE__, __LINE__, __METHOD__, 10);

        if ($name_id === false) {
            return true;
        } else {
            if ($name_id == $this->getId()) {
                return true;
            }
        }

        return false;
    }

    public function getCompany()
    {
        if (isset($this->data['company_id'])) {
            return (int)$this->data['company_id'];
        }

        return false;
    }

    public function getScript()
    {
        if (isset($this->data['script'])) {
            return $this->data['script'];
        }

        return false;
    }

    public function getUser()
    {
        if (isset($this->data['user_id'])) {
            return (int)$this->data['user_id'];
        }

        return false;
    }

    public function setDefault($bool)
    {
        $this->data['is_default'] = $this->toBool($bool);

        return true;
    }

    public function getData()
    {
        $retval = @unserialize($this->data['data']); //If the data is corrupted, stop any PHP warning.
        if ($retval !== false) {
            return $retval;
        }

        Debug::Text('Failed to unserialize data: "' . $this->data['data'] . '"', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function setData($value)
    {
        $value = serialize($value);

        $this->data['data'] = $value;

        return true;
    }

    /*
        //Disable this for now, as it bombards the log with messages that are mostly useless.
        function addLog( $log_action ) {
            if ( $this->getUser() == FALSE AND $this->getDefault() == TRUE ) {
                //Bypass logging on Company Default Save.
                return TRUE;
            }

            return TTLog::addEntry( $this->getId(), $log_action, TTi18n::getText('Employee/Company Generic Data'), NULL, $this->getTable() );
        }
    */

    public function Validate($ignore_warning = true)
    {
        if ($this->getName() == '') {
            $this->Validator->isTRUE('name',
                false,
                TTi18n::gettext('Invalid name'));
        }

        return true;
    }

    public function getName()
    {
        if (isset($this->data['name'])) {
            return $this->data['name'];
        }

        return false;
    }

    public function preSave()
    {
        if ($this->getUser() == '') {
            $this->setUser(0); //Use 0 instead of NULL;
        }

        if ($this->getDefault() == true) {
            //Remove default flag from all other entries.
            $ugdlf = TTnew('UserGenericDataListFactory');
            if ($this->getUser() == false) {
                $ugdlf->getByCompanyIdAndScriptAndDefault($this->getUser(), $this->getScript(), true);
            } else {
                $ugdlf->getByUserIdAndScriptAndDefault($this->getUser(), $this->getScript(), true);
            }
            if ($ugdlf->getRecordCount() > 0) {
                foreach ($ugdlf as $ugd_obj) {
                    Debug::Text('Removing Default Flag From: ' . $ugd_obj->getId(), __FILE__, __LINE__, __METHOD__, 10);
                    $ugd_obj->setDefault(false);
                    if ($ugd_obj->isValid()) {
                        $ugd_obj->Save();
                    }
                }
            }
        }

        return true;
    }

    public function setUser($id)
    {
        $id = (int)trim($id);

        $ulf = TTnew('UserListFactory');

        if ($id == 0
            or
            $this->Validator->isResultSetWithRows('user',
                $ulf->getByID($id),
                TTi18n::gettext('Invalid User')
            )
        ) {
            $this->data['user_id'] = $id;

            return true;
        }

        return false;
    }

    public function getDefault()
    {
        if (isset($this->data['is_default'])) {
            return $this->fromBool($this->data['is_default']);
        }

        return false;
    }

    //Support setting created_by, updated_by especially for importing data.

    public function setObjectFromArray($data)
    {
        if (is_array($data)) {
            $variable_function_map = $this->getVariableToFunctionMap();
            foreach ($variable_function_map as $key => $function) {
                if (isset($data[$key])) {
                    $function = 'set' . $function;
                    switch ($key) {
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

    public function getObjectAsArray($include_columns = null)
    {
        $data = array();
        $variable_function_map = $this->getVariableToFunctionMap();
        if (is_array($variable_function_map)) {
            foreach ($variable_function_map as $variable => $function_stub) {
                if ($include_columns == null or (isset($include_columns[$variable]) and $include_columns[$variable] == true)) {
                    $function = 'get' . $function_stub;
                    switch ($variable) {
                        default:
                            if (method_exists($this, $function)) {
                                $data[$variable] = $this->$function();
                            }
                            break;
                    }
                }
            }
            $this->getCreatedAndUpdatedColumns($data, $include_columns);
        }

        return $data;
    }
}
