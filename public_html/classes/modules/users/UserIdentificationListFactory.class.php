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
 * @package Modules\Users
 */
class UserIdentificationListFactory extends UserIdentificationFactory implements IteratorAggregate
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

        $this->rs = $this->getCache($id);
        if ($this->rs === false) {
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

            $this->saveCache($this->rs, $id);
        }

        return $this;
    }

    public function getByTypeIdAndValue($type_id, $value, $order = null)
    {
        if ($type_id == '') {
            return false;
        }

        if ($value == '') {
            return false;
        }

        $ph = array(
            'type_id' => (int)$type_id,
            'value' => $value,
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a
					where	a.type_id = ?
						AND a.value = ?
						AND a.deleted = 0';
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByCompanyId($id, $limit = null, $page = null, $where = null, $order = null)
    {
        if ($id == '') {
            return false;
        }

        $uf = new UserFactory();

        $ph = array(
            'id' => (int)$id,
        );


        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a,
							' . $uf->getTable() . ' as b
					where	a.user_id = b.id
						AND	b.company_id = ?
						AND ( a.deleted = 0	 AND b.deleted = 0) ';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph, $limit, $page);

        return $this;
    }

    public function getByIdAndCompanyId($id, $company_id, $order = null)
    {
        if ($id == '') {
            return false;
        }

        if ($company_id == '') {
            return false;
        }

        $uf = new UserFactory();

        $ph = array(
            'company_id' => (int)$company_id,
            'id' => (int)$id,
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a,
							' . $uf->getTable() . ' as b
					where	a.user_id = b.id
						AND	b.company_id = ?
						AND	a.id = ?
						AND a.deleted = 0';
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByCompanyIdAndTypeId($company_id, $type_id, $order = null)
    {
        if ($company_id == '') {
            return false;
        }

        if ($type_id == '') {
            return false;
        }

        if ($order == null) {
            $order = array('a.user_id' => 'asc', 'a.type_id' => 'asc', 'a.number' => 'asc', 'a.id' => 'asc');
            $strict = false;
        } else {
            $strict = true;
        }

        $uf = new UserFactory();

        $ph = array(
            'company_id' => (int)$company_id,
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a,
							' . $uf->getTable() . ' as b
					where	a.user_id = b.id
						AND	b.company_id = ?
						AND type_id in (' . $this->getListSQL($type_id, $ph, 'int') . ')
						AND b.status_id = 10
						AND ( a.deleted = 0 AND b.deleted = 0 )';
        $query .= $this->getSortSQL($order, $strict);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByCompanyIdAndTypeIdAndDateAndValidUserIDs($company_id, $type_id, $date = null, $valid_user_ids = array(), $order = null)
    {
        if ($company_id == '') {
            return false;
        }

        if ($type_id == '') {
            return false;
        }

        if ($date == '') {
            $date = 0;
        }

        if ($order == null) {
            $order = array('a.user_id' => 'asc', 'a.type_id' => 'asc', 'a.number' => 'asc');
            $strict = false;
        } else {
            $strict = true;
        }

        $uf = new UserFactory();

        $ph = array(
            'company_id' => (int)$company_id,
        );

        //If the user record is modified, we have to consider the identification record to be modified as well,
        //otherwise a terminated employee re-hired will not have their old prox/fingerprint records put back on the clock.
        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a,
							' . $uf->getTable() . ' as b
					where	a.user_id = b.id
						AND	b.company_id = ?
						AND b.status_id = 10
						AND type_id in (' . $this->getListSQL($type_id, $ph, 'int') . ')
				';

        if ((isset($date) and $date > 0) or (isset($valid_user_ids) and is_array($valid_user_ids) and count($valid_user_ids) > 0)) {
            $query .= ' AND ( ';

            //When the Mobile App/TimeClock are doing a reload database, $date should always be 0. That forces the query to just send data for $valid_user_ids.
            //  All other cases it will send data for all current users always, or records that were recently created/updated.
            if (isset($date) and $date > 0) {
                //Append the same date twice for created and updated.
                $ph[] = (int)$date;
                $ph[] = (int)$date;
                $ph[] = (int)$date;
                $ph[] = (int)$date;
                $query .= '	( ( a.created_date >= ? OR a.updated_date >= ? ) OR ( b.created_date >= ? OR b.updated_date >= ? ) ) ';
            }

            //Valid USER IDs is an "OR", so if any IDs are specified they should *always* be included, regardless of the $date variable.
            if (isset($valid_user_ids) and is_array($valid_user_ids) and count($valid_user_ids) > 0) {
                if (isset($date) and $date > 0) {
                    $query .= ' OR ';
                }
                $query .= ' a.user_id in (' . $this->getListSQL($valid_user_ids, $ph, 'int') . ') ';
            }

            $query .= ' ) ';
        }

        $query .= ' AND ( a.deleted = 0 AND b.deleted = 0 )';

        $query .= $this->getSortSQL($order, $strict);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByCompanyIdAndTypeIdAndValue($company_id, $type_id, $value, $order = null)
    {
        if ($company_id == '') {
            return false;
        }

        if ($type_id == '') {
            return false;
        }

        if ($value == '') {
            return false;
        }

        $uf = new UserFactory();

        $ph = array(
            'company_id' => (int)$company_id,
            'type_id' => (int)$type_id,
            'value' => $value,
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a,
							' . $uf->getTable() . ' as b
					where	a.user_id = b.id
						AND	b.company_id = ?
						AND	a.type_id = ?
						AND a.value = ?
						AND a.deleted = 0';
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByUserId($user_id, $order = null)
    {
        if ($user_id == '') {
            return false;
        }

        $ph = array(
            'user_id' => (int)$user_id,
        );

        $query = '
					select	*
					from	' . $this->getTable() . '
					where	user_id = ?
						AND deleted = 0';
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByUserIdAndTypeId($user_id, $type_id, $order = null)
    {
        if ($user_id == '') {
            return false;
        }

        if ($type_id == '') {
            return false;
        }

        if ($order == null) {
            $order = array('user_id' => 'asc', 'type_id' => 'asc', 'number' => 'asc');
            $strict = false;
        } else {
            $strict = true;
        }

        $ph = array(
            'user_id' => (int)$user_id,
        );

        $query = '
					select	*
					from	' . $this->getTable() . '
					where	user_id = ?
						AND type_id in (' . $this->getListSQL($type_id, $ph, 'int') . ')
						AND deleted = 0';
        $query .= $this->getSortSQL($order, $strict);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByUserIdAndTypeIdAndNumber($user_id, $type_id, $number, $order = null)
    {
        if ($user_id == '') {
            return false;
        }

        if ($type_id == '') {
            return false;
        }

        if ($number === '') {
            return false;
        }

        if ($order == null) {
            $order = array('number' => 'asc', 'id' => 'asc');
            $strict = false;
        } else {
            $strict = true;
        }

        $ph = array(
            'user_id' => (int)$user_id,
            'type_id' => (int)$type_id,
            'number' => $number,
        );

        $query = '
					select	*
					from	' . $this->getTable() . '
					where	user_id = ?
						AND type_id = ?
						AND number = ?
						AND deleted = 0';
        $query .= $this->getSortSQL($order, $strict);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByCompanyIdAndUserIdAndTypeIdAndNumber($company_id, $user_id, $type_id, $number, $order = null)
    {
        if ($company_id == '') {
            return false;
        }

        if ($user_id == '') {
            return false;
        }

        if ($type_id == '') {
            return false;
        }

        if ($number === '') {
            return false;
        }

        if ($order == null) {
            $order = array('a.number' => 'asc', 'a.id' => 'asc');
            $strict = false;
        } else {
            $strict = true;
        }

        $uf = new UserFactory();

        $ph = array(
            'company_id' => (int)$company_id,
            'user_id' => (int)$user_id,
            'type_id' => (int)$type_id,
            'number' => $number,
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a,
							' . $uf->getTable() . ' as b
					where	a.user_id = b.id
						AND	b.company_id = ?
						AND a.user_id = ?
						AND a.type_id = ?
						AND a.number = ?
						AND a.deleted = 0';
        $query .= $this->getSortSQL($order, $strict);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByUserIdAndTypeIdAndValue($user_id, $type_id, $value, $order = null)
    {
        if ($user_id == '') {
            return false;
        }

        if ($type_id == '') {
            return false;
        }

        if ($value === '') {
            return false;
        }

        $ph = array(
            'user_id' => (int)$user_id,
            'type_id' => (int)$type_id,
            'value' => $value,
        );

        $query = '
					select	*
					from	' . $this->getTable() . '
					where	user_id = ?
						AND type_id = ?
						AND value = ?
						AND deleted = 0';
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getIsModifiedByUserIdAndDate($user_id, $date, $where = null, $order = null)
    {
        if ($user_id == '') {
            return false;
        }

        if ($date == '') {
            return false;
        }

        $ph = array(
            'user_id' => (int)$user_id,
            'created_date' => $date,
            'updated_date' => $date,
        );

        //INCLUDE Deleted rows in this query.
        $query = '
					select	*
					from	' . $this->getTable() . '
					where	user_id = ?
						AND
							( created_date >= ? OR updated_date >= ? )
						';
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);
        if ($this->getRecordCount() > 0) {
            Debug::text('User Identification rows have been modified: ' . $this->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
            return true;
        }
        Debug::text('User Identification rows have NOT been modified', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function getIsModifiedByCompanyIdAndDate($company_id, $date, $where = null, $order = null)
    {
        if ($company_id == '') {
            return false;
        }

        if ($date == '') {
            return false;
        }

        $uf = new UserFactory();

        $ph = array(
            'company_id' => (int)$company_id,
            'created_date' => $date,
            'updated_date' => $date,
            'user_created_date' => $date,
            'user_updated_date' => $date,
            'deleted_date' => $date,
            'user_deleted_date' => $date,
        );

        //INCLUDE Deleted rows in this query.
        //If the user record is modified, we have to consider the identification record to be modified as well,
        //otherwise a terminated employee re-hired will not have their old prox/fingerprint records put back on the clock.
        $query = '
					select	*
					from	' . $this->getTable() . ' as a
					LEFT JOIN ' . $uf->getTable() . ' as b ON a.user_id = b.id
					where	b.company_id = ?
						AND
							( ( a.created_date >= ? OR a.updated_date >= ? ) OR ( b.created_date >= ? OR b.updated_date >= ? )
								OR ( a.deleted = 1 AND a.deleted_date >= ? ) OR ( b.deleted = 1 AND b.deleted_date >= ? )  )
						';
        $query .= $this->getSortSQL($order);

        $this->rs = $this->db->SelectLimit($query, 1, -1, $ph);
        if ($this->getRecordCount() > 0) {
            Debug::text('User Identification rows have been modified: ' . $this->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
            return true;
        }
        Debug::text('User Identification rows have NOT been modified', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function getByUserIdAndCompanyId($user_id, $company_id, $limit = null, $page = null, $where = null, $order = null)
    {
        if (empty($user_id)) {
            return false;
        }

        if (empty($company_id)) {
            return false;
        }

        if ($order == null) {
            $strict = false;
        } else {
            $strict = true;
        }

        $uf = new UserFactory();

        $ph = array(
            'company_id' => (int)$company_id,
            'user_id' => (int)$user_id,
        );

        $query = '
					select	*
					from	' . $uf->getTable() . ' as a,
							' . $this->getTable() . ' as b
					where	a.id = b.user_id
						AND a.company_id = ?
						AND	b.user_id = ?
						AND b.deleted = 0';
        $query .= $this->getSortSQL($order, $strict);

        $this->ExecuteSQL($query, $ph, $limit, $page);

        return $this;
    }
}
