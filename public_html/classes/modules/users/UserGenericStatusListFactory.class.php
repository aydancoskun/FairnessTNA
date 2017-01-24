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
class UserGenericStatusListFactory extends UserGenericStatusFactory implements IteratorAggregate
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

        $uf = new UserFactory();

        $ph = array(
            'company_id' => (int)$company_id
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a
						LEFT JOIN  ' . $uf->getTable() . ' as b on a.user_id = b.id
					where	b.company_id = ?
						AND a.deleted = 0';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByIdAndCompanyId($id, $company_id, $where = null, $order = null)
    {
        if ($id == '') {
            return false;
        }

        if ($company_id == '') {
            return false;
        }

        $uf = new UserFactory();

        $ph = array(
            'id' => (int)$id,
            'company_id' => (int)$company_id
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a
						LEFT JOIN  ' . $uf->getTable() . ' as b on a.user_id = b.id
					where	a.id = ?
						AND b.company_id = ?
						AND a.deleted = 0';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByUserId($id, $limit = null, $page = null, $where = null, $order = null)
    {
        if ($id == '') {
            return false;
        }

        $ph = array(
            'user_id' => (int)$id,
        );

        $query = '
					select	*
					from	' . $this->getTable() . '
					where	user_id = ?
							AND deleted = 0';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph, $limit, $page);

        return $this;
    }

    public function getByUserIdAndBatchId($user_id, $batch_id, $limit = null, $page = null, $where = null, $order = null)
    {
        if ($user_id == '') {
            return false;
        }

        if ($batch_id == '') {
            return false;
        }

        if ($order == null) {
            $order = array('status_id' => 'asc', 'label' => 'asc');
            $strict = false;
        } else {
            $strict = true;
        }

        $ph = array(
            'user_id' => (int)$user_id,
            'batch_id' => (int)$batch_id,
        );

        $query = '
					select	*
					from	' . $this->getTable() . '
					where	user_id = ?
						AND batch_id = ?
						AND deleted = 0';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict);

        $this->ExecuteSQL($query, $ph, $limit, $page);

        return $this;
    }

    public function getStatusCountArrayByUserIdAndBatchId($user_id, $batch_id, $where = null, $order = null)
    {
        if ($user_id == '') {
            return false;
        }

        if ($batch_id == '') {
            return false;
        }

        $ph = array(
            'user_id' => (int)$user_id,
            'batch_id' => (int)$batch_id,
        );

        $query = '
					select	status_id, count(*) as total
					from	' . $this->getTable() . '
					where	user_id = ?
						AND batch_id = ?
						AND deleted = 0
					GROUP BY status_id';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $result = $this->db->GetArray($query, $ph);

        $total = 0;
        foreach ($result as $row) {
            $total = ($total + $row['total']);
        }
        $retarr = array();
        $retarr['total'] = $total;

        $retarr['status'] = array(
            10 => array('total' => 0, 'percent' => 0),
            20 => array('total' => 0, 'percent' => 0),
            30 => array('total' => 0, 'percent' => 0),
        );

        foreach ($result as $row) {
            $retarr['status'][$row['status_id']] = array('total' => $row['total'], 'percent' => round((($row['total'] / $total) * 100), 1));
        }

        if (empty($retarr) == false) {
            return $retarr;
        }

        return false;
    }
}
