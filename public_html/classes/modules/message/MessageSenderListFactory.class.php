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
 * @package Modules\Message
 */
class MessageSenderListFactory extends MessageSenderFactory implements IteratorAggregate
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
            'company_id' => (int)$company_id,
        );

        $query = '
					SELECT a.*
					FROM ' . $this->getTable() . ' as a
						LEFT JOIN ' . $uf->getTable() . ' as b ON a.user_id = b.id
					WHERE
							b.company_id = ?
							AND ( a.deleted = 0 AND b.deleted = 0 )
					';
        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByCompanyIdAndId($company_id, $id, $where = null, $order = null)
    {
        if ($company_id == '') {
            return false;
        }

        if ($id == '') {
            return false;
        }

        $uf = new UserFactory();

        $ph = array(
            'company_id' => (int)$company_id,
        );

        $query = '
					SELECT a.*
					FROM ' . $this->getTable() . ' as a
						LEFT JOIN ' . $uf->getTable() . ' as b ON a.user_id = b.id
					WHERE
							b.company_id = ?
							AND a.id in (' . $this->getListSQL($id, $ph, 'int') . ')
							AND a.deleted = 0
					';
        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByCompanyIdAndRecipientId($company_id, $id, $where = null, $order = null)
    {
        if ($company_id == '') {
            return false;
        }

        if ($id == '') {
            return false;
        }

        $mrf = new MessageRecipientFactory();
        $uf = new UserFactory();

        $ph = array(
            'company_id' => (int)$company_id,
        );

        //Ignore deleted message_sender rows, as the sender could have deleted the original message.
        $query = '
					SELECT a.*
					FROM ' . $this->getTable() . ' as a
						LEFT JOIN ' . $mrf->getTable() . ' as b ON a.id = b.message_sender_id
						LEFT JOIN ' . $uf->getTable() . ' as c ON a.user_id = c.id
					WHERE
							c.company_id = ?
							AND b.id in (' . $this->getListSQL($id, $ph, 'int') . ')
							AND ( b.deleted = 0 )
					';
        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByCompanyIdAndObjectTypeAndObjectAndNotUser($company_id, $object_type_id, $object_id, $user_id = 0, $where = null, $order = null)
    {
        if ($company_id == '') {
            return false;
        }

        if ($object_type_id == '') {
            return false;
        }

        if ($object_id == '') {
            return false;
        }


        $uf = new UserFactory();
        $mcf = new MessageControlFactory();

        $ph = array(
            'company_id' => (int)$company_id,
            'object_type_id' => (int)$object_type_id,
            'object_id' => (int)$object_id,
            'user_id' => (int)$user_id,
        );

        $query = '
					SELECT a.*
					FROM ' . $this->getTable() . ' as a
						LEFT JOIN ' . $mcf->getTable() . ' as b ON a.message_control_id = b.id
						LEFT JOIN ' . $uf->getTable() . ' as c ON a.user_id = c.id
					WHERE
							c.company_id = ?
							AND ( b.object_type_id = ? AND b.object_id = ? )
							AND a.user_id != ?
							AND ( b.deleted = 0 AND c.deleted = 0 )
					';
        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByCompanyIdAndUserIdAndId($company_id, $user_id, $id, $where = null, $order = null)
    {
        if ($company_id == '') {
            return false;
        }

        if ($user_id == '') {
            return false;
        }

        if ($id == '') {
            return false;
        }

        $uf = new UserFactory();

        $ph = array(
            'company_id' => (int)$company_id,
            'user_id' => (int)$user_id,
        );

        $query = '
					SELECT a.*
					FROM ' . $this->getTable() . ' as a
						LEFT JOIN ' . $uf->getTable() . ' as b ON a.user_id = b.id
					WHERE
							b.company_id = ?
							AND a.user_id = ?
							AND a.id in (' . $this->getListSQL($id, $ph, 'int') . ')
							AND a.deleted = 0
					';
        $this->ExecuteSQL($query, $ph);

        return $this;
    }
}
