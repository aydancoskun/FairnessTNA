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
class SystemSettingListFactory extends SystemSettingFactory implements IteratorAggregate
{
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

    public function getByName($name, $where = null, $order = null)
    {
        if ($name == '') {
            return false;
        }

        $ph = array(
            'name' => $name,
        );

        $this->rs = $this->getCache($name);
        if ($this->rs === false) {
            $query = '
						select	*
						from	' . $this->getTable() . '
						where	name = ?
						';
            $query .= $this->getWhereSQL($where);
            $query .= $this->getSortSQL($order);

            $this->ExecuteSQL($query, $ph);

            $this->saveCache($this->rs, $name);
        }

        return $this;
    }

    public function getAllArray()
    {
        $id = 'all';

        $retarr = $this->getCache($id);
        if ($retarr === false) {
            $sslf = new SystemSettingListFactory();
            $sslf->getAll();
            if ($sslf->getRecordCount() > 0) {
                foreach ($sslf as $ss_obj) {
                    $retarr[$ss_obj->getName()] = $ss_obj->getValue();
                }

                $this->saveCache($retarr, $id);

                return $retarr;
            } else {
                return false;
            }
        }

        return $retarr;
    }

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
}
