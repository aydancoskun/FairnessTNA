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
 * @package Modules\PayPeriod
 */
class PayPeriodScheduleUserListFactory extends PayPeriodScheduleUserFactory implements IteratorAggregate
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

        $ppsf = new PayPeriodScheduleFactory();

        $ph = array(
            'company_id' => (int)$company_id,
        );

        $query = '
					SELECT a.*
					FROM ' . $this->getTable() . ' as a
						LEFT JOIN ' . $ppsf->getTable() . ' as b ON a.pay_period_schedule_id = b.id
					WHERE
							b.company_id = ?
							AND ( b.deleted = 0 )
					';
        $this->ExecuteSQL($query, $ph);

        return $this;
    }


    public function getByCompanyIDAndPayPeriodScheduleId($company_id, $id, $where = null, $order = null)
    {
        if ($company_id == '') {
            return false;
        }

        if ($id == '') {
            return false;
        }

        if ($order == null) {
            $order = array('a.user_id' => 'asc');
            $strict = false;
        } else {
            $strict = true;
        }

        $ppsf = new PayPeriodScheduleFactory();

        $ph = array(
            'company_id' => (int)$company_id,
            'id' => (int)$id,
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a,
							' . $ppsf->getTable() . ' as b
					where	b.id = a.pay_period_schedule_id
						AND b.company_id = ?
						AND a.pay_period_schedule_id = ?
					';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByCompanyIDAndPayPeriodScheduleIdAndUserID($company_id, $id, $user_id, $where = null, $order = null)
    {
        if ($company_id == '') {
            return false;
        }

        if ($id == '') {
            return false;
        }

        if ($user_id == '') {
            return false;
        }

        $ppsf = new PayPeriodScheduleFactory();

        $ph = array(
            'company_id' => (int)$company_id,
            'id' => (int)$id,
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a,
							' . $ppsf->getTable() . ' as b
					where	b.id = a.pay_period_schedule_id
						AND b.company_id = ?
						AND a.pay_period_schedule_id = ?
						AND a.user_id in ( ' . $this->getListSQL($user_id, $ph, 'int') . ' )
					';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByPayPeriodScheduleIdAndUserID($id, $user_id, $where = null, $order = null)
    {
        if ($id == '') {
            return false;
        }

        if ($user_id == '') {
            return false;
        }

        $ppsf = new PayPeriodScheduleFactory();

        $ph = array(
            'id' => (int)$id,
            'user_id' => (int)$user_id,
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a,
							' . $ppsf->getTable() . ' as b
					where	b.id = a.pay_period_schedule_id
						AND pay_period_schedule_id = ?
						AND user_id = ?
					';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByPayPeriodScheduleIdArray($id)
    {
        $ppsulf = new PayPeriodScheduleUserListFactory();

        $ppsulf->getByPayPeriodScheduleId($id);

        $user_list = array();
        foreach ($ppsulf as $user) {
            $user_list[$user->getUser()] = null;
        }

        if (empty($user_list) == false) {
            return $user_list;
        }

        return array();
    }

    public function getByPayPeriodScheduleId($id, $where = null, $order = null)
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

        $ppsf = new PayPeriodScheduleFactory();

        $ph = array(
            'id' => (int)$id,
        );


        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a,
							' . $ppsf->getTable() . ' as b
					where	b.id = a.pay_period_schedule_id
						AND pay_period_schedule_id = ?
					';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }
}
