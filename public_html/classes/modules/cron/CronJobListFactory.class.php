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
 * @package Modules\Cron
 */
class CronJobListFactory extends CronJobFactory implements IteratorAggregate
{
    public function getAll($limit = null, $page = null, $where = null, $order = null)
    {
        if ($order == null) {
            $order = array('id' => 'asc');
            $strict = false;
        } else {
            $strict = true;
        }

        $query = '
					select	*
					from	' . $this->getTable() . '
					WHERE deleted = 0';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict);

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

    public function getByIdAndStatus($id, $status_id, $where = null, $order = null)
    {
        if ($id == '') {
            return false;
        }

        if ($status_id == '') {
            return false;
        }

        $ph = array(
            'id' => (int)$id,
            'status_id' => (int)$status_id,
        );

        $query = '
					select	*
					from	' . $this->getTable() . '
					where	id = ?
						AND status_id = ?
						AND deleted = 0';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByName($name, $where = null, $order = null)
    {
        if ($name == '') {
            return false;
        }

        $ph = array(
            'name' => $name,
        );

        $query = '
					select	*
					from	' . $this->getTable() . '
					where	name = ?
						AND deleted = 0';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getMostRecentlyRun()
    {
        $query = '
					select	*
					from	' . $this->getTable() . '
					WHERE deleted = 0
					ORDER BY last_run_date DESC
					LIMIT 1';
        //$query .= $this->getWhereSQL( $where );
        //$query .= $this->getSortSQL( $order );

        $this->rs = $this->db->Execute($query);

        return $this;
    }

    public function getArrayByListFactory($lf)
    {
        if (!is_object($lf)) {
            return false;
        }

        $list = array();
        foreach ($lf as $obj) {
            $list[$obj->getID()] = $obj->getName(true);
        }

        if (empty($list) == false) {
            return $list;
        }

        return false;
    }
}
