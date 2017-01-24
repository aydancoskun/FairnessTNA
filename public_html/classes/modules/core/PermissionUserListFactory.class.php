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
 * @package Core
 */
class PermissionUserListFactory extends PermissionUserFactory implements IteratorAggregate
{
    public function getAll($limit = null, $page = null, $where = null, $order = null)
    {
        $query = '
					select	*
					from	' . $this->getTable();
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
					';
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

        $pcf = new PermissionControlFactory();

        $ph = array(
            'company_id' => (int)$company_id,
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a,
							' . $pcf->getTable() . ' as b
					where	a.permission_control_id = b.id
						AND b.company_id = ?
						AND b.deleted = 0
					';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByCompanyIdAndUserIdAndNotPermissionControlId($company_id, $user_id, $permission_control_id, $where = null, $order = null)
    {
        if ($company_id == '') {
            return false;
        }

        if ($user_id == '') {
            return false;
        }

        if ($permission_control_id == '') {
            return false;
        }

        $pcf = new PermissionControlFactory();

        $ph = array(
            'company_id' => (int)$company_id,
            'permission_control_id' => (int)$permission_control_id,
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a,
							' . $pcf->getTable() . ' as b
					where	a.permission_control_id = b.id
						AND b.company_id = ?
						AND a.permission_control_id != ?
						AND a.user_id in (' . $this->getListSQL($user_id, $ph, 'int') . ')
						AND b.deleted = 0
					';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByCompanyIdAndDateAndValidIDs($company_id, $date = null, $valid_ids = array(), $where = null, $order = null)
    {
        if ($company_id == '') {
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
					select	a.*,
							b.updated_date as updated_date,
							b.updated_by as updated_by
					from	' . $this->getTable() . ' as a,
							' . $pcf->getTable() . ' as b
					where	b.id = a.permission_control_id
						AND b.company_id = ?
						AND (
								(
								1=1 ';

        if (isset($date) and $date > 0) {
            //Append the same date twice for created and updated.
            $ph[] = (int)$date;
            $ph[] = (int)$date;
            $query .= '		AND ( b.created_date >= ? OR b.updated_date >= ? ) )';
        } else {
            $query .= ' ) ';
        }

        if (isset($valid_ids) and is_array($valid_ids) and count($valid_ids) > 0) {
            $query .= ' OR a.id in (' . $this->getListSQL($valid_ids, $ph, 'int') . ') ';
        }

        $query .= '	)
						AND ( b.deleted = 0)';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByPermissionControlId($id, $where = null, $order = null)
    {
        if ($id == '') {
            return false;
        }

        if ($order == null) {
            $order = array('a.user_id' => 'asc');
            $strict = false;
        } else {
            $strict = true;
        }

        $ph = array(
            'id' => (int)$id,
        );


        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a
					where	a.permission_control_id = ?
					';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByPermissionControlIdAndUserID($id, $user_id, $where = null, $order = null)
    {
        if ($id == '') {
            return false;
        }

        if ($user_id == '') {
            return false;
        }

        $ph = array(
            'id' => (int)$id,
            'user_id' => (int)$user_id,
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a
					where	a.permission_control_id = ?
						AND a.user_id = ?
					';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }


    public function getByPermissionControlIdArray($id)
    {
        $pculf = new PermissionControlUserListFactory();

        $pculf->getByPayPermissionControlId($id);
        $user_list = array();
        foreach ($pculf as $user) {
            $user_list[$user->getUser()] = null;
        }

        if (empty($user_list) == false) {
            return $user_list;
        }

        return array();
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

        $query = '
					select	a.*,
							b.updated_date as updated_date,
							b.updated_by as updated_by
					from	' . $this->getTable() . ' as a,
							' . $pcf->getTable() . ' as b
					where	b.id = a.permission_control_id
						AND b.company_id = ?
						AND
							( b.created_date >=	 ? OR b.updated_date >= ? OR ( b.deleted = 1 AND b.deleted_date >= ? ) )
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
