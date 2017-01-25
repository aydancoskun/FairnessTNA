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
 * @package Modules\Help
 */
class HelpGroupControlListFactory extends HelpGroupControlFactory implements IteratorAggregate
{
    public function getAll($limit = null, $page = null, $where = null, $order = null)
    {
        $strict_order = true;
        if ($order == null) {
            $order = array('created_date' => 'desc');
            //$strict_order = FALSE;
        }

        $query = '
					select	*
					from	' . $this->getTable() . '
					WHERE deleted=0
				';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict_order);

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
						AND deleted=0';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByScriptAndName($script, $name = null, $where = null, $order = null)
    {
        if ($script == '') {
            return false;
        }

        if ($name == '') {
            $name = null;
        }

        $ph = array(
            'script' => $script,
            'name' => $name,
        );

        $query = '
					select	*
					from	' . $this->getTable() . '
					where	script_name = ?
						AND name = ?
						AND deleted=0';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }
}
