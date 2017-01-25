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
 * @package Modules\Policy
 */
class CompanyGenericMapListFactory extends CompanyGenericMapFactory implements IteratorAggregate
{
    public static function getArrayByCompanyIDAndObjectTypeIDAndObjectID($company_id, $object_type_id, $object_id)
    {
        $cgmlf = new CompanyGenericMapListFactory();
        return $cgmlf->getArrayByListFactory($cgmlf->getByCompanyIDAndObjectTypeAndObjectID($company_id, $object_type_id, $object_id));
    }

    public function getArrayByListFactory($lf)
    {
        if (!is_object($lf)) {
            return false;
        }
        $list = array();
        foreach ($lf as $obj) {
            $list[] = $obj->getMapId();
        }

        if (empty($list) == false) {
            return $list;
        }

        return false;
    }

    public function getByCompanyIDAndObjectTypeAndObjectID($company_id, $object_type_id, $id, $where = null, $order = null)
    {
        if ($company_id == '') {
            return false;
        }

        if ($object_type_id == '') {
            return false;
        }

        if ($id == '') {
            return false;
        }

        $cache_id = md5($company_id . serialize($object_type_id) . serialize($id));
        //Debug::Text('Cache ID: '. $cache_id .' Company ID: '. $company_id .' Object Type: '. $object_type_id .' ID: '. $id, __FILE__, __LINE__, __METHOD__, 10);

        $this->rs = $this->getCache($cache_id);
        if ($this->rs === false) {
            $ph = array(
                'company_id' => (int)$company_id
            );


            $query = '
						select	a.*
						from	' . $this->getTable() . ' as a
						where	a.company_id = ?
							AND a.object_type_id in (' . $this->getListSQL($object_type_id, $ph, 'int') . ')
							AND a.object_id in (' . $this->getListSQL($id, $ph, 'int') . ')
						';
            $query .= $this->getWhereSQL($where);
            $query .= $this->getSortSQL($order);

            $this->ExecuteSQL($query, $ph);

            $this->saveCache($this->rs, $cache_id);
        }

        return $this;
    }

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

    public function getByCompanyId($id, $where = null, $order = null)
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
					where	company_id = ?
					';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByCompanyIDAndObjectType($company_id, $id, $where = null, $order = null)
    {
        if ($company_id == '') {
            return false;
        }

        if ($id == '') {
            return false;
        }

        $ph = array(
            'company_id' => (int)$company_id
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a
					where	a.company_id = ?
						AND a.object_type_id in (' . $this->getListSQL($id, $ph, 'int') . ')
					';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByCompanyIDAndObjectTypeAndMapID($company_id, $object_type_id, $id, $where = null, $order = null)
    {
        if ($company_id == '') {
            return false;
        }

        if ($object_type_id == '') {
            return false;
        }

        if ($id == '') {
            return false;
        }

        $ph = array(
            'company_id' => (int)$company_id
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a
					where	a.company_id = ?
						AND a.object_type_id in (' . $this->getListSQL($object_type_id, $ph, 'int') . ')
						AND a.map_id in (' . $this->getListSQL($id, $ph, 'int') . ')
					';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);
        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByCompanyIDAndObjectTypeAndObjectIDAndMapID($company_id, $object_type_id, $id, $map_id, $where = null, $order = null)
    {
        if ($company_id == '') {
            return false;
        }

        if ($object_type_id == '') {
            return false;
        }

        if ($id == '') {
            return false;
        }

        $ph = array(
            'company_id' => (int)$company_id
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a
					where	a.company_id = ?
						AND a.object_type_id in (' . $this->getListSQL($object_type_id, $ph, 'int') . ')
						AND a.object_id in (' . $this->getListSQL($id, $ph, 'int') . ')
						AND a.map_id in (' . $this->getListSQL($map_id, $ph, 'int') . ')
					';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByCompanyIDAndObjectTypeAndObjectIDAndNotMapID($company_id, $object_type_id, $id, $map_id, $where = null, $order = null)
    {
        if ($company_id == '') {
            return false;
        }

        if ($object_type_id == '') {
            return false;
        }

        if ($id == '') {
            return false;
        }

        $ph = array(
            'company_id' => (int)$company_id
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a
					where	a.company_id = ?
						AND a.object_type_id in (' . $this->getListSQL($object_type_id, $ph, 'int') . ')
						AND a.object_id in (' . $this->getListSQL($id, $ph, 'int') . ')
						AND a.map_id not in (' . $this->getListSQL($map_id, $ph, 'int') . ')
					';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByObjectType($id, $where = null, $order = null)
    {
        if ($id == '') {
            return false;
        }

        $ph = array();

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a
					where	a.object_type_id in (' . $this->getListSQL($id, $ph, 'int') . ')
					';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByObjectTypeAndObjectID($object_type_id, $id, $where = null, $order = null)
    {
        if ($object_type_id == '') {
            return false;
        }

        if ($id == '') {
            return false;
        }

        $ph = array();

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a
					where	a.object_type_id in (' . $this->getListSQL($object_type_id, $ph, 'int') . ')
						AND a.object_id in (' . $this->getListSQL($id, $ph, 'int') . ')
					';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }
}
