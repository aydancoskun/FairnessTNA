<?php
/*********************************************************************************
 * FairnessTNA is a Workforce Management program forked from TimeTrex in 2013,
 * copyright Aydan Coskun. Original code base is copyright TimeTrex Software Inc.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * You can contact Aydan Coskun via issue tracker on github.com/aydancoskun
 ********************************************************************************/


/**
 * @package Modules\Message
 */
class MessageListFactory extends MessageFactory implements IteratorAggregate {

	/**
	 * @param int $limit Limit the number of records returned
	 * @param int $page Page number of records to return for pagination
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return $this
	 */
	function getAll( $limit = NULL, $page = NULL, $where = NULL, $order = NULL) {
		$query = '
					select	*
					from	'. $this->getTable() .'
					WHERE deleted = 0';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );

		$this->rs = $this->ExecuteSQL( $query, NULL, $limit, $page );

		return $this;
	}

	/**
	 * @param string $id UUID
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool|MessageListFactory
	 */
	function getById( $id, $where = NULL, $order = NULL) {
		if ( $id == '') {
			return FALSE;
		}

		$ph = array(
					'id' => TTUUID::castUUID($id),
					);


		$query = '
					select	*
					from	'. $this->getTable() .'
					where	id = ?
					AND deleted = 0';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );

		$this->rs = $this->ExecuteSQL( $query, $ph );

		return $this;
	}

	/**
	 * @param string $company_id UUID
	 * @param int $limit Limit the number of records returned
	 * @param int $page Page number of records to return for pagination
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool|MessageListFactory
	 */
	function getByCompanyId( $company_id, $limit = NULL, $page = NULL, $where = NULL, $order = NULL) {
		if ( $company_id == '') {
			return FALSE;
		}

		$uf = new UserFactory();

		$ph = array(
					'company_id' => TTUUID::castUUID($company_id),
					);

		$query = '
					SELECT a.*
					FROM '. $this->getTable() .' as a
						LEFT JOIN '. $uf->getTable() .' as b ON a.created_by = b.id
					WHERE
							b.company_id = ? AND a.deleted = 0
					';

		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );

		$this->rs = $this->ExecuteSQL( $query, $ph, $limit, $page );

		return $this;
	}

	/**
	 * @param string $company_id UUID
	 * @param string $user_id UUID
	 * @param string $id UUID
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool|MessageListFactory
	 */
	function getByCompanyIdAndUserIdAndId( $company_id, $user_id, $id, $where = NULL, $order = NULL) {
		if ( $company_id == '') {
			return FALSE;
		}

		if ( $user_id == '') {
			return FALSE;
		}

		if ( $id == '') {
			return FALSE;
		}

		$uf = new UserFactory();

		$ph = array(
					'id' => TTUUID::castUUID($id),
					'user_id' => TTUUID::castUUID($user_id),
					'company_id' => TTUUID::castUUID($company_id),
					);

		$query = '
					SELECT a.*
					FROM '. $this->getTable() .' as a
						LEFT JOIN '. $uf->getTable() .' as b ON a.created_by = b.id
					WHERE
							a.object_type_id in (5, 50)
							AND a.id = ?
							AND a.created_by = ?
							AND b.company_id = ?
							AND a.deleted = 0
					';
		$this->rs = $this->ExecuteSQL( $query, $ph );

		return $this;
	}

	/**
	 * @param string $id UUID
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool|MessageListFactory
	 */
	function getMessagesInThreadById( $id, $where = NULL, $order = NULL ) {

		if ( $id == '') {
			return FALSE;
		}


		$ph = array(
					'id' => TTUUID::castUUID($id),
					'id2' => TTUUID::castUUID($id),
					'id3' => TTUUID::castUUID($id),
					);

		$query = '
					SELECT a.*
					FROM '. $this->getTable() .' as a
					WHERE
							a.object_type_id in (5, 50)
							AND ( a.id = ?
									OR a.parent_id = ( select z.parent_id from '. $this->getTable() .' as z where z.id = ? AND z.parent_id != 0 )
									OR a.id = ( select z.parent_id from '. $this->getTable() .' as z where z.id = ? )
								)
							AND a.deleted = 0
					';
		$this->rs = $this->ExecuteSQL( $query, $ph );

		return $this;
	}

	/**
	 * @param string $user_id UUID
	 * @return bool|int|mixed
	 */
	function getNewMessagesByUserId( $user_id ) {
		if ( $user_id == '') {
			return FALSE;
		}

		$rf = new RequestFactory();
		$uf = new UserFactory();
		$pptsvf = new PayPeriodTimeSheetVerifyFactory();

		//Need to include all threads that user has posted to.
		$this->setCacheLifeTime( 600 );
		$unread_messages = $this->getCache($user_id);
		if ( $unread_messages === FALSE ) {
			$ph = array(
						'user_id' => TTUUID::castUUID($user_id),
						'id' => $user_id,
						'created_by1' => $user_id,
						'created_by2' => $user_id,
						'created_by3' => $user_id,
						'created_by4' => $user_id,
						);

			$query = '
						SELECT count(*)
						FROM '. $this->getTable() .' as a
							LEFT JOIN '. $uf->getTable() .' as d ON a.object_type_id = 5 AND a.object_id = d.id
							LEFT JOIN '. $uf->getTable() .' as f ON a.created_by = f.id
							LEFT JOIN '. $rf->getTable() .' as b ON a.object_type_id = 50 AND a.object_id = b.id
							LEFT JOIN '. $pptsvf->getTable() .' as e ON a.object_type_id = 90 AND a.object_id = e.id
						WHERE
								a.object_type_id in (5, 50, 90)
								AND a.status_id = 10
								AND
								(
									(
										b.user_id = ?
										OR d.id = ?
										OR e.user_id = ?
										OR a.parent_id in ( select parent_id FROM '. $this->getTable() .' WHERE created_by = ? AND parent_id != \''. TTUUID::getZeroID() .'\' )
										OR a.parent_id in ( select id FROM '. $this->getTable() .' WHERE created_by = ? AND parent_id = \''. TTUUID::getZeroID() .'\' )
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

	/**
	 * @param string $user_id UUID
	 * @param $folder
	 * @param int $limit Limit the number of records returned
	 * @param int $page Page number of records to return for pagination
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool|MessageListFactory
	 */
	function getByUserIdAndFolder( $user_id, $folder, $limit = NULL, $page = NULL, $where = NULL, $order = NULL ) {
		if ( $user_id == '') {
			return FALSE;
		}

		$strict = TRUE;
		if ( $order == NULL ) {
			$strict = FALSE;
			$order = array( 'a.status_id' => '= 10 desc', 'a.created_date' => 'desc' );
		}

		//Folder is: INBOX, SENT

		$rf = new RequestFactory();
		$uf = new UserFactory();
		$pptsvf = new PayPeriodTimeSheetVerifyFactory();

		$ph = array(
					'user_id' => TTUUID::castUUID($user_id),
					//'id' => $user_id,
					);

		$folder_sent_query = NULL;
		$folder_inbox_query = NULL;
		$folder_inbox_query_a = NULL;
		$folder_inbox_query_ab = NULL;
		$folder_inbox_query_b = NULL;
		$folder_inbox_query_c = NULL;

		if ( $folder == 10 ) {
			$ph['id'] = $user_id;
			$ph['created_by1'] = $user_id;
			$ph['created_by2'] = $user_id;
			$ph['created_by3'] = $user_id;
			$ph['created_by4'] = $user_id;

			$folder_inbox_query = ' AND a.created_by != ?';
			$folder_inbox_query_a = ' OR d.id = ?';
			$folder_inbox_query_ab = ' OR e.user_id = ?';
			//$folder_inbox_query_b = ' OR a.parent_id in ( select parent_id FROM '. $this->getTable() .' WHERE created_by = '. $user_id .' ) ';
			$folder_inbox_query_b = ' OR a.parent_id in ( select parent_id FROM '. $this->getTable() .' WHERE created_by = ? AND parent_id != \''. TTUUID::getZeroID() .'\' ) ';
			$folder_inbox_query_c = ' OR a.parent_id in ( select id FROM '. $this->getTable() .' WHERE created_by = ? AND parent_id = \''. TTUUID::getZeroID() .'\' ) ';
		} elseif ( $folder == 20 ) {
			$ph['created_by4'] = $user_id;

			$folder_sent_query = ' OR a.created_by = ?';
		}

		//Need to include all threads that user has posted to.
		$query = '
					SELECT a.*,
							CASE WHEN a.object_type_id = 5 THEN d.id WHEN a.object_type_id = 50 THEN b.user_id WHEN a.object_type_id = 90 THEN e.user_id END as sent_to_user_id
					FROM '. $this->getTable() .' as a
						LEFT JOIN '. $uf->getTable() .' as d ON a.object_type_id = 5 AND a.object_id = d.id
						LEFT JOIN '. $uf->getTable() .' as f ON a.created_by = f.id
						LEFT JOIN '. $rf->getTable() .' as b ON a.object_type_id = 50 AND a.object_id = b.id
						LEFT JOIN '. $pptsvf->getTable() .' as e ON a.object_type_id = 90 AND a.object_id = e.id
					WHERE
							a.object_type_id in (5, 50, 90)
							AND
							(

								(
									(
										b.user_id = ?
										'. $folder_sent_query .'
										'. $folder_inbox_query_a .'
										'. $folder_inbox_query_ab .'
										'. $folder_inbox_query_b .'
										'. $folder_inbox_query_c .'
									)
									'. $folder_inbox_query .'
								)
							)

						AND ( a.deleted = 0 AND f.deleted = 0
								AND ( b.id IS NULL OR ( b.id IS NOT NULL AND b.deleted = 0 ) )
								AND ( d.id IS NULL OR ( d.id IS NOT NULL AND d.deleted = 0 ) )
								AND ( e.id IS NULL OR ( e.id IS NOT NULL AND e.deleted = 0 ) )
								AND NOT ( b.id IS NULL AND d.id IS NULL AND e.id IS NULL )
							)
					';

		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order, $strict, array('sent_to_user_id') );

		//Debug::text('Query: '. $query, __FILE__, __LINE__, __METHOD__, 9);

		$this->rs = $this->ExecuteSQL( $query, $ph, $limit, $page );

		return $this;
	}


	/**
	 * @param $object_type
	 * @param string $object_id UUID
	 * @param string $id UUID
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool|MessageListFactory
	 */
	function getByObjectTypeAndObjectAndId( $object_type, $object_id, $id, $where = NULL, $order = NULL) {
		if ( $object_type == '' OR $object_id == '' OR $id == '' ) {
			return FALSE;
		}

		$ph = array(
					'object_type' => $object_type,
					'object_id' => TTUUID::castUUID($object_id),
					'id' => TTUUID::castUUID($id),
					'parent_id' => TTUUID::castUUID($id),
					);

		$query = '
					select	*
					from	'. $this->getTable() .'
					where	object_type_id = ?
						AND object_id = ?
						AND ( id = ? OR parent_id = ? )
						AND deleted = 0
					ORDER BY id';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );

		$this->rs = $this->ExecuteSQL( $query, $ph );

		return $this;
	}

	/**
	 * @param $object_type
	 * @param string $object_id UUID
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool|MessageListFactory
	 */
	function getByObjectTypeAndObject( $object_type, $object_id, $where = NULL, $order = NULL) {
		if ( !isset($object_type) OR !isset($object_id) ) {
			return FALSE;
		}

		$ph = array(
					'object_type' => $object_type,
					'object_id' => TTUUID::castUUID($object_id),
					);

		$query = '
					select	*
					from	'. $this->getTable() .'
					where	object_type_id = ?
						AND object_id = ?
						AND deleted = 0
					ORDER BY id';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );

		$this->rs = $this->ExecuteSQL( $query, $ph );

		return $this;
	}

}
?>
