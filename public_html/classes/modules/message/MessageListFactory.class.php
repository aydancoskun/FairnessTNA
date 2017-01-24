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
 * @package Modules\Message
 */
class MessageListFactory extends MessageFactory implements IteratorAggregate
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

    public function getByCompanyId($company_id, $limit = null, $page = null, $where = null, $order = null)
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
						LEFT JOIN ' . $uf->getTable() . ' as b ON a.created_by = b.id
					WHERE
							b.company_id = ? AND a.deleted = 0
					';

        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph, $limit, $page);

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
            'id' => (int)$id,
            'user_id' => (int)$user_id,
            'company_id' => (int)$company_id,
        );

        $query = '
					SELECT a.*
					FROM ' . $this->getTable() . ' as a
						LEFT JOIN ' . $uf->getTable() . ' as b ON a.created_by = b.id
					WHERE
							a.object_type_id in (5, 50)
							AND a.id = ?
							AND a.created_by = ?
							AND b.company_id = ?
							AND a.deleted = 0
					';
        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getMessagesInThreadById($id, $where = null, $order = null)
    {
        if ($id == '') {
            return false;
        }


        $ph = array(
            'id' => (int)$id,
            'id2' => $id,
            'id3' => $id,
        );

        $query = '
					SELECT a.*
					FROM ' . $this->getTable() . ' as a
					WHERE
							a.object_type_id in (5, 50)
							AND ( a.id = ?
									OR a.parent_id = ( select z.parent_id from ' . $this->getTable() . ' as z where z.id = ? AND z.parent_id != 0 )
									OR a.id = ( select z.parent_id from ' . $this->getTable() . ' as z where z.id = ? )
								)
							AND a.deleted = 0
					';
        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getNewMessagesByUserId($user_id)
    {
        if ($user_id == '') {
            return false;
        }

        $rf = new RequestFactory();
        $uf = new UserFactory();
        $pptsvf = new PayPeriodTimeSheetVerifyFactory();

        //Need to include all threads that user has posted to.
        $this->setCacheLifeTime(600);
        $unread_messages = $this->getCache($user_id);
        if ($unread_messages === false) {
            $ph = array(
                'user_id' => (int)$user_id,
                'id' => $user_id,
                'created_by1' => $user_id,
                'created_by2' => $user_id,
                'created_by3' => $user_id,
                'created_by4' => $user_id,
            );

            $query = '
						SELECT count(*)
						FROM ' . $this->getTable() . ' as a
							LEFT JOIN ' . $uf->getTable() . ' as d ON a.object_type_id = 5 AND a.object_id = d.id
							LEFT JOIN ' . $uf->getTable() . ' as f ON a.created_by = f.id
							LEFT JOIN ' . $rf->getTable() . ' as b ON a.object_type_id = 50 AND a.object_id = b.id
							LEFT JOIN ' . $pptsvf->getTable() . ' as e ON a.object_type_id = 90 AND a.object_id = e.id
						WHERE
								a.object_type_id in (5, 50, 90)
								AND a.status_id = 10
								AND
								(
									(
										b.user_id = ?
										OR d.id = ?
										OR e.user_id = ?
										OR a.parent_id in ( select parent_id FROM ' . $this->getTable() . ' WHERE created_by = ? AND parent_id != 0 )
										OR a.parent_id in ( select id FROM ' . $this->getTable() . ' WHERE created_by = ? AND parent_id = 0 )
									)
									AND a.created_by != ?
								)

							AND ( a.deleted = 0 AND f.deleted = 0
								AND ( b.id IS NULL OR ( b.id IS NOT NULL AND b.deleted = 0 ) )
								AND ( d.id IS NULL OR ( d.id IS NOT NULL AND d.deleted = 0 ) )
								AND ( e.id IS NULL OR ( e.id IS NOT NULL AND e.deleted = 0 ) )
								AND NOT ( b.id IS NULL AND d.id IS NULL AND e.id IS NULL )
							)

						';
            $unread_messages = (int)$this->db->GetOne($query, $ph);

            $this->saveCache($unread_messages, $user_id);
        }
        return $unread_messages;
    }

    public function getByUserIdAndFolder($user_id, $folder, $limit = null, $page = null, $where = null, $order = null)
    {
        if ($user_id == '') {
            return false;
        }

        $strict = true;
        if ($order == null) {
            $strict = false;
            $order = array('a.status_id' => '= 10 desc', 'a.created_date' => 'desc');
        }

        //Folder is: INBOX, SENT

        $rf = new RequestFactory();
        $uf = new UserFactory();
        $pptsvf = new PayPeriodTimeSheetVerifyFactory();

        $ph = array(
            'user_id' => (int)$user_id,
            //'id' => $user_id,
        );

        $folder_sent_query = null;
        $folder_inbox_query = null;
        $folder_inbox_query_a = null;
        $folder_inbox_query_ab = null;
        $folder_inbox_query_b = null;
        $folder_inbox_query_c = null;

        if ($folder == 10) {
            $ph['id'] = $user_id;
            $ph['created_by1'] = $user_id;
            $ph['created_by2'] = $user_id;
            $ph['created_by3'] = $user_id;
            $ph['created_by4'] = $user_id;

            $folder_inbox_query = ' AND a.created_by != ?';
            $folder_inbox_query_a = ' OR d.id = ?';
            $folder_inbox_query_ab = ' OR e.user_id = ?';
            //$folder_inbox_query_b = ' OR a.parent_id in ( select parent_id FROM '. $this->getTable() .' WHERE created_by = '. $user_id .' ) ';
            $folder_inbox_query_b = ' OR a.parent_id in ( select parent_id FROM ' . $this->getTable() . ' WHERE created_by = ? AND parent_id != 0 ) ';
            $folder_inbox_query_c = ' OR a.parent_id in ( select id FROM ' . $this->getTable() . ' WHERE created_by = ? AND parent_id = 0 ) ';
        } elseif ($folder == 20) {
            $ph['created_by4'] = $user_id;

            $folder_sent_query = ' OR a.created_by = ?';
        }

        //Need to include all threads that user has posted to.
        $query = '
					SELECT a.*,
							CASE WHEN a.object_type_id = 5 THEN d.id WHEN a.object_type_id = 50 THEN b.user_id WHEN a.object_type_id = 90 THEN e.user_id END as sent_to_user_id
					FROM ' . $this->getTable() . ' as a
						LEFT JOIN ' . $uf->getTable() . ' as d ON a.object_type_id = 5 AND a.object_id = d.id
						LEFT JOIN ' . $uf->getTable() . ' as f ON a.created_by = f.id
						LEFT JOIN ' . $rf->getTable() . ' as b ON a.object_type_id = 50 AND a.object_id = b.id
						LEFT JOIN ' . $pptsvf->getTable() . ' as e ON a.object_type_id = 90 AND a.object_id = e.id
					WHERE
							a.object_type_id in (5, 50, 90)
							AND
							(

								(
									(
										b.user_id = ?
										' . $folder_sent_query . '
										' . $folder_inbox_query_a . '
										' . $folder_inbox_query_ab . '
										' . $folder_inbox_query_b . '
										' . $folder_inbox_query_c . '
									)
									' . $folder_inbox_query . '
								)
							)

						AND ( a.deleted = 0 AND f.deleted = 0
								AND ( b.id IS NULL OR ( b.id IS NOT NULL AND b.deleted = 0 ) )
								AND ( d.id IS NULL OR ( d.id IS NOT NULL AND d.deleted = 0 ) )
								AND ( e.id IS NULL OR ( e.id IS NOT NULL AND e.deleted = 0 ) )
								AND NOT ( b.id IS NULL AND d.id IS NULL AND e.id IS NULL )
							)
					';

        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict, array('sent_to_user_id'));

        //Debug::text('Query: '. $query, __FILE__, __LINE__, __METHOD__, 9);

        $this->ExecuteSQL($query, $ph, $limit, $page);

        return $this;
    }


    public function getByObjectTypeAndObjectAndId($object_type, $object_id, $id, $where = null, $order = null)
    {
        if ($object_type == '' or $object_id == '' or $id == '') {
            return false;
        }

        $ph = array(
            'object_type' => $object_type,
            'object_id' => (int)$object_id,
            'id' => (int)$id,
            'parent_id' => (int)$id,
        );

        $query = '
					select	*
					from	' . $this->getTable() . '
					where	object_type_id = ?
						AND object_id = ?
						AND ( id = ? OR parent_id = ? )
						AND deleted = 0
					ORDER BY id';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByObjectTypeAndObject($object_type, $object_id, $where = null, $order = null)
    {
        if (!isset($object_type) or !isset($object_id)) {
            return false;
        }

        $ph = array(
            'object_type' => $object_type,
            'object_id' => (int)$object_id,
        );

        $query = '
					select	*
					from	' . $this->getTable() . '
					where	object_type_id = ?
						AND object_id = ?
						AND deleted = 0
					ORDER BY id';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }
}
