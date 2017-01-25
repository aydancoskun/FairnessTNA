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
 * @package Modules\Department
 */
class DepartmentFactory extends Factory
{
    protected $table = 'department';
    protected $pk_sequence_name = 'department_id_seq'; //PK Sequence name

    public static function getNextAvailableManualId($company_id = null)
    {
        global $current_company;

        if ($company_id == '' and is_object($current_company)) {
            $company_id = $current_company->getId();
        } elseif ($company_id == '' and isset($this) and is_object($this)) {
            $company_id = $this->getCompany();
        }

        $dlf = TTnew('DepartmentListFactory');
        $dlf->getHighestManualIDByCompanyId($company_id);
        if ($dlf->getRecordCount() > 0) {
            $next_available_manual_id = ($dlf->getCurrent()->getManualId() + 1);
        } else {
            $next_available_manual_id = 1;
        }

        return $next_available_manual_id;
    }

    public function getCompany()
    {
        return (int)$this->data['company_id'];
    }

    public function _getFactoryOptions($name, $parent = null)
    {
        $retval = null;
        switch ($name) {
            case 'status':
                $retval = array(
                    10 => TTi18n::gettext('ENABLED'),
                    20 => TTi18n::gettext('DISABLED')
                );
                break;
            case 'columns':
                $retval = array(
                    '-1010-status' => TTi18n::gettext('Status'),
                    '-1020-manual_id' => TTi18n::gettext('Code'),
                    '-1030-name' => TTi18n::gettext('Name'),

                    '-1300-tag' => TTi18n::gettext('Tags'),

                    '-2000-created_by' => TTi18n::gettext('Created By'),
                    '-2010-created_date' => TTi18n::gettext('Created Date'),
                    '-2020-updated_by' => TTi18n::gettext('Updated By'),
                    '-2030-updated_date' => TTi18n::gettext('Updated Date'),
                );
                break;
            case 'list_columns':
                $retval = Misc::arrayIntersectByKey($this->getOptions('default_display_columns'), Misc::trimSortPrefix($this->getOptions('columns')));
                break;
            case 'default_display_columns': //Columns that are displayed by default.
                $retval = array(
                    'manual_id',
                    'name',
                );
                break;
            case 'unique_columns': //Columns that are unique, and disabled for mass editing.
                $retval = array(
                    'name',
                    'manual_id'
                );
        }

        return $retval;
    }

    public function _getVariableToFunctionMap($data)
    {
        $variable_function_map = array(
            'id' => 'ID',
            'company_id' => 'Company',
            'status_id' => 'Status',
            'status' => false,
            'manual_id' => 'ManualID',
            'name' => 'Name',
            'name_metaphone' => 'NameMetaphone',
            'other_id1' => 'OtherID1',
            'other_id2' => 'OtherID2',
            'other_id3' => 'OtherID3',
            'other_id4' => 'OtherID4',
            'other_id5' => 'OtherID5',
            'geo_fence_ids' => 'GEOFenceIds',
            'tag' => 'Tag',
            'deleted' => 'Deleted',
        );
        return $variable_function_map;
    }

    public function setCompany($id)
    {
        $id = trim($id);

        $clf = TTnew('CompanyListFactory');

        if ($id == 0
            or $this->Validator->isResultSetWithRows('company',
                $clf->getByID($id),
                TTi18n::gettext('Company is invalid')
            )
        ) {
            $this->data['company_id'] = $id;

            return true;
        }

        return false;
    }

    public function setName($name)
    {
        $name = trim($name);

        if ($this->Validator->isLength('name',
                $name,
                TTi18n::gettext('Department name is too short or too long'),
                2,
                100)
            and
            $this->Validator->isTrue('name',
                $this->isUniqueName($name),
                TTi18n::gettext('Department already exists'))

        ) {
            $this->data['name'] = $name;
            $this->setNameMetaphone($name);

            return true;
        }

        return false;
    }

    public function isUniqueName($name)
    {
        if ($this->getCompany() == false) {
            return false;
        }

        $name = trim($name);
        if ($name == '') {
            return false;
        }

        $ph = array(
            'company_id' => (int)$this->getCompany(),
            'name' => TTi18n::strtolower($name),
        );

        $query = 'select id from ' . $this->table . '
					where company_id = ?
						AND lower(name) = ?
						AND deleted = 0';
        $name_id = $this->db->GetOne($query, $ph);
        //Debug::Arr($name_id, 'Unique Name: '. $name, __FILE__, __LINE__, __METHOD__, 10);

        if ($name_id === false) {
            return true;
        } else {
            if ($name_id == $this->getId()) {
                return true;
            }
        }

        return false;
    }

    public function setNameMetaphone($value)
    {
        $value = metaphone(trim($value));

        if ($value != '') {
            $this->data['name_metaphone'] = $value;

            return true;
        }

        return false;
    }

    public function getNameMetaphone()
    {
        if (isset($this->data['name_metaphone'])) {
            return $this->data['name_metaphone'];
        }

        return false;
    }

    public function getBranch()
    {
        $branch_list = array();
        $dblf = TTnew('DepartmentBranchListFactory');
        $dblf->getByDepartmentId($this->getId());
        foreach ($dblf as $department_branch) {
            $branch_list[] = $department_branch->getBranch();
        }

        if (empty($branch_list) == false) {
            return $branch_list;
        }

        return false;
    }

    public function setBranch($ids)
    {
        if (is_array($ids) and count($ids) > 0) {
            //If needed, delete mappings first.
            $dblf = TTnew('DepartmentBranchListFactory');
            $dblf->getByDepartmentId($this->getId());

            $branch_ids = array();
            foreach ($dblf as $department_branch) {
                $branch_id = $department_branch->getBranch();
                Debug::text('Department ID: ' . $department_branch->getDepartment() . ' Branch: ' . $branch_id, __FILE__, __LINE__, __METHOD__, 10);

                //Delete branches that are not selected.
                if (!in_array($branch_id, $ids)) {
                    Debug::text('Deleting DepartmentBranch: ' . $branch_id, __FILE__, __LINE__, __METHOD__, 10);
                    $department_branch->Delete();
                } else {
                    //Save branch ID's that need to be updated.
                    Debug::text('NOT Deleting DepartmentBranch: ' . $branch_id, __FILE__, __LINE__, __METHOD__, 10);
                    $branch_ids[] = $branch_id;
                }
            }

            //Insert new mappings.
            $dbf = TTnew('DepartmentBranchFactory');
            foreach ($ids as $id) {
                if (!in_array($id, $branch_ids)) {
                    $dbf->setDepartment($this->getId());
                    $dbf->setBranch($id);

                    if ($this->Validator->isTrue('branch',
                        $dbf->Validator->isValid(),
                        TTi18n::gettext('Branch selection is invalid'))
                    ) {
                        $dbf->save();
                    }
                }
            }

            return true;
        }

        return false;
    }

    public function getOtherID1()
    {
        if (isset($this->data['other_id1'])) {
            return $this->data['other_id1'];
        }

        return false;
    }

    public function setOtherID1($value)
    {
        $value = trim($value);

        if ($value == ''
            or
            $this->Validator->isLength('other_id1',
                $value,
                TTi18n::gettext('Other ID 1 is invalid'),
                1, 255)
        ) {
            $this->data['other_id1'] = $value;

            return true;
        }

        return false;
    }

    public function getOtherID2()
    {
        if (isset($this->data['other_id2'])) {
            return $this->data['other_id2'];
        }

        return false;
    }

    public function setOtherID2($value)
    {
        $value = trim($value);

        if ($value == ''
            or
            $this->Validator->isLength('other_id2',
                $value,
                TTi18n::gettext('Other ID 2 is invalid'),
                1, 255)
        ) {
            $this->data['other_id2'] = $value;

            return true;
        }

        return false;
    }

    public function getOtherID3()
    {
        if (isset($this->data['other_id3'])) {
            return $this->data['other_id3'];
        }

        return false;
    }

    public function setOtherID3($value)
    {
        $value = trim($value);

        if ($value == ''
            or
            $this->Validator->isLength('other_id3',
                $value,
                TTi18n::gettext('Other ID 3 is invalid'),
                1, 255)
        ) {
            $this->data['other_id3'] = $value;

            return true;
        }

        return false;
    }

    public function getOtherID4()
    {
        if (isset($this->data['other_id4'])) {
            return $this->data['other_id4'];
        }

        return false;
    }

    public function setOtherID4($value)
    {
        $value = trim($value);

        if ($value == ''
            or
            $this->Validator->isLength('other_id4',
                $value,
                TTi18n::gettext('Other ID 4 is invalid'),
                1, 255)
        ) {
            $this->data['other_id4'] = $value;

            return true;
        }

        return false;
    }

    public function getOtherID5()
    {
        if (isset($this->data['other_id5'])) {
            return $this->data['other_id5'];
        }

        return false;
    }

    public function setOtherID5($value)
    {
        $value = trim($value);

        if ($value == ''
            or
            $this->Validator->isLength('other_id5',
                $value,
                TTi18n::gettext('Other ID 5 is invalid'),
                1, 255)
        ) {
            $this->data['other_id5'] = $value;

            return true;
        }

        return false;
    }

    public function getGEOFenceIds()
    {
        return CompanyGenericMapListFactory::getArrayByCompanyIDAndObjectTypeIDAndObjectID($this->getCompany(), 4010, $this->getID());
    }

    public function setGEOFenceIds($ids)
    {
        Debug::text('Setting GEO Fence IDs : ', __FILE__, __LINE__, __METHOD__, 10);
        return CompanyGenericMapFactory::setMapIDs($this->getCompany(), 4010, $this->getID(), (array)$ids);
    }

    public function setTag($tags)
    {
        $tags = trim($tags);

        //Save the tags in temporary memory to be committed in postSave()
        $this->tmp_data['tags'] = $tags;

        return true;
    }

    public function preSave()
    {
        if ($this->getStatus() == false) {
            $this->setStatus(10);
        }

        if ($this->getManualID() == false) {
            $this->setManualID(DepartmentListFactory::getNextAvailableManualId($this->getCompany()));
        }

        return true;
    }

    public function getStatus()
    {
        //Have to return the KEY because it should always be a drop down box.
        //return Option::getByKey($this->data['status_id'], $this->getOptions('status') );
        return (int)$this->data['status_id'];
    }

    public function setStatus($status)
    {
        $status = trim($status);

        if ($this->Validator->inArrayKey('status',
            $status,
            TTi18n::gettext('Incorrect Status'),
            $this->getOptions('status'))
        ) {
            $this->data['status_id'] = $status;

            return true;
        }

        return false;
    }

    public function getManualID()
    {
        if (isset($this->data['manual_id'])) {
            return (int)$this->data['manual_id'];
        }

        return false;
    }

    public function setManualID($value)
    {
        $value = $this->Validator->stripNonNumeric(trim($value));

        if ($this->Validator->isNumeric('manual_id',
                $value,
                TTi18n::gettext('Code is invalid'))
            and
            $this->Validator->isLength('manual_id',
                $value,
                TTi18n::gettext('Code has too many digits'),
                0,
                10)
            and
            $this->Validator->isTrue('manual_id',
                ($this->Validator->stripNon32bitInteger($value) === 0) ? false : true,
                TTi18n::gettext('Code is invalid, maximum value exceeded'))
            and
            $this->Validator->isTrue('manual_id',
                $this->isUniqueManualID($value),
                TTi18n::gettext('Code is already in use, please enter a different one'))
        ) {
            $this->data['manual_id'] = $value;

            return true;
        }

        return false;
    }

    public function isUniqueManualID($id)
    {
        if ($this->getCompany() == false) {
            return false;
        }

        $ph = array(
            'manual_id' => $id,
            'company_id' => $this->getCompany(),
        );

        $query = 'select id from ' . $this->getTable() . ' where manual_id = ? AND company_id = ? AND deleted=0';
        $id = $this->db->GetOne($query, $ph);
        Debug::Arr($id, 'Unique Department: ' . $id, __FILE__, __LINE__, __METHOD__, 10);

        if ($id === false) {
            return true;
        } else {
            if ($id == $this->getId()) {
                return true;
            }
        }

        return false;
    }

    public function postSave()
    {
        $this->removeCache($this->getId());

        if ($this->getDeleted() == false) {
            CompanyGenericTagMapFactory::setTags($this->getCompany(), 120, $this->getID(), $this->getTag());
        }

        if ($this->getDeleted() == true) {
            Debug::Text('UnAssign Hours from Department: ' . $this->getId(), __FILE__, __LINE__, __METHOD__, 10);
            //Unassign hours from this department.
            $pcf = TTnew('PunchControlFactory');
            $udtf = TTnew('UserDateTotalFactory');
            $uf = TTnew('UserFactory');
            $sf = TTnew('StationFactory');
            $sdf = TTnew('StationDepartmentFactory');
            $sf_b = TTnew('ScheduleFactory');
            $udf = TTnew('UserDefaultFactory');
            $rstf = TTnew('RecurringScheduleTemplateFactory');

            $query = 'update ' . $pcf->getTable() . ' set department_id = 0 where department_id = ' . (int)$this->getId();
            $this->db->Execute($query);

            $query = 'update ' . $udtf->getTable() . ' set department_id = 0 where department_id = ' . (int)$this->getId();
            $this->db->Execute($query);

            $query = 'update ' . $sf_b->getTable() . ' set department_id = 0 where department_id = ' . (int)$this->getId();
            $this->db->Execute($query);

            $query = 'update ' . $uf->getTable() . ' set default_department_id = 0 where company_id = ' . (int)$this->getCompany() . ' AND default_department_id = ' . (int)$this->getId();
            $this->db->Execute($query);

            $query = 'update ' . $udf->getTable() . ' set default_department_id = 0 where company_id = ' . (int)$this->getCompany() . ' AND default_department_id = ' . (int)$this->getId();
            $this->db->Execute($query);

            $query = 'update ' . $sf->getTable() . ' set department_id = 0 where company_id = ' . (int)$this->getCompany() . ' AND department_id = ' . (int)$this->getId();
            $this->db->Execute($query);

            $query = 'delete from ' . $sdf->getTable() . ' where department_id = ' . (int)$this->getId();
            $this->db->Execute($query);

            $query = 'update ' . $rstf->getTable() . ' set department_id = 0 where department_id = ' . (int)$this->getId();
            $this->db->Execute($query);
        }

        return true;
    }

    public function getTag()
    {
        //Check to see if any temporary data is set for the tags, if not, make a call to the database instead.
        //postSave() needs to get the tmp_data.
        if (isset($this->tmp_data['tags'])) {
            return $this->tmp_data['tags'];
        } elseif ($this->getCompany() > 0 and $this->getID() > 0) {
            return CompanyGenericTagMapListFactory::getStringByCompanyIDAndObjectTypeIDAndObjectID($this->getCompany(), 120, $this->getID());
        }

        return false;
    }

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

    //Support setting created_by, updated_by especially for importing data.
    //Make sure data is set based on the getVariableToFunctionMap order.

    public function getObjectAsArray($include_columns = null)
    {
        $data = array();
        $variable_function_map = $this->getVariableToFunctionMap();
        if (is_array($variable_function_map)) {
            foreach ($variable_function_map as $variable => $function_stub) {
                if ($include_columns == null or (isset($include_columns[$variable]) and $include_columns[$variable] == true)) {
                    $function = 'get' . $function_stub;
                    switch ($variable) {
                        case 'status':
                            $function = 'get' . $variable;
                            if (method_exists($this, $function)) {
                                $data[$variable] = Option::getByKey($this->$function(), $this->getOptions($variable));
                            }
                            break;
                        case 'name_metaphone':
                            break;
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

    public function addLog($log_action)
    {
        return TTLog::addEntry($this->getId(), $log_action, TTi18n::getText('Department') . ': ' . $this->getName(), null, $this->getTable(), $this);
    }

    public function getName()
    {
        if (isset($this->data['name'])) {
            return $this->data['name'];
        }
        return false;
    }
}
