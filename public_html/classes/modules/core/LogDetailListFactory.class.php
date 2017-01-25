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
class LogDetailListFactory extends LogDetailFactory implements IteratorAggregate
{
    public function getAll($limit = null, $page = null, $where = null, $order = null)
    {
        $query = '
					select	*
					from	' . $this->getTable() . '
				';
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

    public function getByIdAndCompanyId($id, $company_id, $where = null, $order = null)
    {
        if ($id == '') {
            return false;
        }

        if ($company_id == '') {
            return false;
        }

        $uf = new UserFactory();
        $lf = new LogFactory();

        $ph = array(
            'id' => (int)$id,
            'company_id' => (int)$company_id
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a
						LEFT JOIN  ' . $lf->getTable() . ' as lf on a.system_log_id = lf.id
						LEFT JOIN  ' . $uf->getTable() . ' as uf on lf.user_id = uf.id
					where	a.id = ?
						AND uf.company_id = ?';
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
        $lf = new LogFactory();

        $ph = array(
            'company_id' => (int)$company_id
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a
						LEFT JOIN  ' . $lf->getTable() . ' as lf on a.system_log_id = lf.id
						LEFT JOIN  ' . $uf->getTable() . ' as uf on lf.user_id = uf.id
					where	uf.company_id = ?
						AND ( uf.deleted = 0 )';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getBySystemLogIdAndCompanyId($id, $company_id, $where = null, $order = null)
    {
        if ($id == '') {
            return false;
        }

        if ($company_id == '') {
            return false;
        }

        if ($order == null) {
            $order = array('a.field' => 'asc');
            $strict = false;
        } else {
            $strict = true;
        }

        $uf = new UserFactory();
        $lf = new LogFactory();

        $ph = array(
            'id' => (int)$id,
            'company_id' => (int)$company_id
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a
						LEFT JOIN  ' . $lf->getTable() . ' as lf on a.system_log_id = lf.id
						LEFT JOIN  ' . $uf->getTable() . ' as uf on lf.user_id = uf.id
					where	a.system_log_id = ?
						AND uf.company_id = ?';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }
}
