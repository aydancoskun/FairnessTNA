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
class PermissionListFactory extends PermissionFactory implements IteratorAggregate
{
    public function getAll($limit = null, $page = null, $where = null, $order = null)
    {
        $query = '
					select	*
					from	' . $this->getTable() . '
						WHERE deleted = 0';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, null, $limit, $page);

        return $this;
    }

    public function getById($id, $where = null, $order = null)
    {
        if ($id == '') {
            return false;
        }

        $ph = array(
            'id' => (int)$id,
        );


        $query = '
					select	*
					from	' . $this->getTable() . '
					where	id = ?
						AND deleted = 0';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByCompanyId($company_id, $where = null, $order = null)
    {
        if ($company_id == '') {
            return false;
        }

        $ph = array(
            'company_id' => (int)$company_id,
        );

        $pcf = new PermissionControlFactory();

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a,
							' . $pcf->getTable() . ' as b
					where	b.id = a.permission_control_id
						AND b.company_id = ?
						AND ( a.deleted = 0 AND b.deleted = 0 )';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByCompanyIdAndPermissionControlId($company_id, $permission_control_id, $where = null, $order = null)
    {
        if ($company_id == '') {
            return false;
        }

        if ($permission_control_id == '') {
            return false;
        }

        $ph = array(
            'company_id' => (int)$company_id,
            'permission_control_id' => (int)$permission_control_id,
        );

        $pcf = new PermissionControlFactory();

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a,
							' . $pcf->getTable() . ' as b
					where	b.id = a.permission_control_id
						AND b.company_id = ?
						AND a.permission_control_id = ?
						AND ( a.deleted = 0 AND b.deleted = 0 )';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByCompanyIdAndPermissionControlIdAndSectionAndName($company_id, $permission_control_id, $section, $name, $where = null, $order = null)
    {
        if ($company_id == '') {
            return false;
        }

        if ($permission_control_id == '') {
            return false;
        }

        if ($section == '') {
            return false;
        }

        if ($name == '') {
            return false;
        }

        $ph = array(
            'company_id' => (int)$company_id,
            'permission_control_id' => (int)$permission_control_id,
            'section' => $section,
            //'name' => $name, //Allow a list of names.
        );

        $pcf = new PermissionControlFactory();

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a,
							' . $pcf->getTable() . ' as b
					where	b.id = a.permission_control_id
						AND b.company_id = ?
						AND a.permission_control_id = ?
						AND a.section = ?
						AND a.name in (' . $this->getListSQL($name, $ph) . ')
						AND ( a.deleted = 0 AND b.deleted = 0)';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByCompanyIdAndPermissionControlIdAndSectionAndNameAndValue($company_id, $permission_control_id, $section, $name, $value, $where = null, $order = null)
    {
        if ($company_id == '') {
            return false;
        }

        if ($permission_control_id == '') {
            return false;
        }

        if ($section == '') {
            return false;
        }

        if ($name == '') {
            return false;
        }

        $ph = array(
            'company_id' => (int)$company_id,
            'permission_control_id' => (int)$permission_control_id,
            'section' => $section,
            'value' => (int)$value,
            //'name' => $name, //Allow a list of names.
        );

        $pcf = new PermissionControlFactory();

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a,
							' . $pcf->getTable() . ' as b
					where	b.id = a.permission_control_id
						AND b.company_id = ?
						AND a.permission_control_id = ?
						AND a.section = ?
						AND a.value = ?
						AND a.name in (' . $this->getListSQL($name, $ph) . ')
						AND ( a.deleted = 0 AND b.deleted = 0)';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByCompanyIdAndSectionAndDateAndValidIDs($company_id, $section, $date = null, $valid_ids = array(), $where = null, $order = null)
    {
        if ($company_id == '') {
            return false;
        }

        if ($section == '') {
            return false;
        }

        if ($date == '') {
            $date = 0;
        }

        $ph = array(
            'company_id' => (int)$company_id,
        );

        $pcf = new PermissionControlFactory();

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a,
							' . $pcf->getTable() . ' as b
					where	b.id = a.permission_control_id
						AND b.company_id = ?
						AND (
								(
								a.section in (' . $this->getListSQL($section, $ph) . ') ';

        //When the Mobile App/TimeClock are doing a reload database, $date should always be 0. That forces the query to just send data for $valid_user_ids.
        //  All other cases it will send data for all current users always, or records that were recently created/updated.
        if (isset($date) and $date > 0) {
            //Append the same date twice for created and updated.
            $ph[] = (int)$date;
            $ph[] = (int)$date;
            $query .= '		AND ( a.created_date >= ? OR a.updated_date >= ? ) ) ';
        } else {
            $query .= ' ) ';
        }

        if (isset($valid_ids) and is_array($valid_ids) and count($valid_ids) > 0) {
            $query .= ' OR a.id in (' . $this->getListSQL($valid_ids, $ph, 'int') . ') ';
        }

        $query .= '	)
						AND ( a.deleted = 0 AND b.deleted = 0)';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getAllPermissionsByCompanyIdAndUserId($company_id, $user_id)
    {
        if ($company_id == '') {
            return false;
        }

        if ($user_id == '') {
            return false;
        }

        $ph = array(
            'company_id' => (int)$company_id,
            'user_id' => (int)$user_id,
        );

        $pcf = new PermissionControlFactory();
        $puf = new PermissionUserFactory();

        $query = '
					select	a.*,
							b.level as level
					from	' . $this->getTable() . ' as a,
							' . $pcf->getTable() . ' as b,
							' . $puf->getTable() . ' as c
					where b.id = a.permission_control_id
						AND b.id = c.permission_control_id
						AND b.company_id = ?
						AND	c.user_id = ?
						AND ( a.deleted = 0 AND b.deleted = 0 )
				';

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getIsModifiedByCompanyIdAndDate($company_id, $date, $where = null, $order = null)
    {
        if ($company_id == '') {
            return false;
        }

        if ($date == '') {
            return false;
        }

        $ph = array(
            'company_id' => (int)$company_id,
            'created_date' => $date,
            'updated_date' => $date,
            'deleted_date' => $date,
        );

        $pcf = new PermissionControlFactory();

        //INCLUDE Deleted rows in this query.
        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a,
							' . $pcf->getTable() . ' as b
					where
							b.company_id = ?
						AND
							( a.created_date >=	 ? OR a.updated_date >= ? OR ( a.deleted = 1 AND a.deleted_date >= ? ) )
					';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->rs = $this->db->SelectLimit($query, 1, -1, $ph);
        if ($this->getRecordCount() > 0) {
            Debug::text('Rows have been modified: ' . $this->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);

            return true;
        }
        Debug::text('Rows have NOT been modified', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }
}
