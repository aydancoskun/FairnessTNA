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
class MessageControlListFactory extends MessageControlFactory implements IteratorAggregate {

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
	 * @return bool|MessageControlListFactory
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
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool|MessageControlListFactory
	 */
	function getByCompanyId( $company_id, $where = NULL, $order = NULL) {
		if ( $company_id == '') {
			return FALSE;
		}

		$mrf = new MessageRecipientFactory();
		$uf = new UserFactory();

		$ph = array(
					'company_id' => TTUUID::castUUID($company_id)
					);

		$query = '
					select	a.*
					from	'. $this->getTable() .' as a
					LEFT JOIN '. $mrf->getTable() .'	as mrf ON a.id = mrf.message_sender_id
					LEFT JOIN '. $uf->getTable() .'		as uf ON mrf.user_id = uf.id
					where	uf.company_id = ?
						AND ( a.deleted = 0 AND uf.deleted = 0 )';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );

		$this->rs = $this->ExecuteSQL( $query, $ph );

		return $this;
	}

	/**
	 * @param string $company_id UUID
	 * @param string $user_id UUID
	 * @return bool|int|mixed
	 */
	function getNewMessagesByCompanyIdAndUserId( $company_id, $user_id ) {
		if ( $company_id == '') {
			return FALSE;
		}

		if ( $user_id == '') {
			return FALSE;
		}

		$this->setCacheLifeTime( 600 );
		$unread_messages = $this->getCache($user_id);
		if ( $unread_messages === FALSE ) {
			$mrf = new MessageRecipientFactory();
			$msf = new MessageSenderFactory();
			$uf = new UserFactory();
			$rf = new RequestFactory();
			$pptsvf = new PayPeriodTimeSheetVerifyFactory();

			$ph = array(
						'user_id' => TTUUID::castUUID($user_id),
						'company_id' => TTUUID::castUUID($company_id),
						);

			//Need to include all threads that user has posted to.
			$query = '
						SELECT count(*)
						FROM '. $mrf->getTable() .' as a
							LEFT JOIN '. $msf->getTable() .'	as b ON a.message_sender_id = b.id
							LEFT JOIN '. $uf->getTable() .'		as bb ON b.user_id = bb.id
							LEFT JOIN '. $this->getTable() .'	as c ON b.message_control_id = c.id
							LEFT JOIN '. $uf->getTable() .'		as d ON c.object_type_id = 5 AND c.object_id = d.id
							LEFT JOIN '. $rf->getTable() .'		as f ON c.object_type_id = 50 AND c.object_id = f.id
							LEFT JOIN '. $pptsvf->getTable() .' as h ON c.object_type_id = 90 AND c.object_id = h.id
						WHERE
								a.user_id = ?
								AND bb.company_id = ?
								AND c.object_type_id in (5, 50, 90)
								AND a.status_id = 10
								AND ( ( a.deleted = 0 AND c.deleted = 0 )
										AND ( CASE WHEN c.object_type_id = 5 THEN d.deleted = 0 ELSE d.id IS NULL END )
										AND ( CASE WHEN c.object_type_id = 50 THEN f.deleted = 0 ELSE f.id IS NULL END )
										AND ( CASE WHEN c.object_type_id = 90 THEN h.deleted = 0 ELSE h.id IS NULL END )
									)
						';
			//Debug::Arr($ph, ' Query: '. $query, __FILE__, __LINE__, __METHOD__, 10);

			$unread_messages = (int)$this->db->GetOne($query, $ph);
			$this->saveCache($unread_messages, $user_id);
		}

		return $unread_messages;
	}

	/**
	 * @param string $company_id UUID
	 * @param string $user_id UUID
	 * @param $folder
	 * @param int $limit Limit the number of records returned
	 * @param int $page Page number of records to return for pagination
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool|MessageControlListFactory
	 */
	function getByCompanyIDAndUserIdAndFolder( $company_id, $user_id, $folder, $limit = NULL, $page = NULL, $where = NULL, $order = NULL ) {
		if ( $company_id == '') {
			return FALSE;
		}

		if ( $user_id == '') {
			return FALSE;
		}


		$strict = TRUE;
		if ( $order == NULL ) {
			$strict = FALSE;
			$order = array( 'a.status_id' => '= 10 desc', 'a.created_date' => 'desc' );
		}

		//Folder is: INBOX, SENT

		$mrf = new MessageRecipientFactory();
		$msf = new MessageSenderFactory();
		$rf = new RequestFactory();
		$uf = new UserFactory();
		//$udf = new UserDateFactory();
		$pptsvf = new PayPeriodTimeSheetVerifyFactory();

		$ph = array(
					'user_id' => TTUUID::castUUID($user_id),
					'company_id' => TTUUID::castUUID($company_id),
					);

		if ( $folder == 10 ) { //Inbox
			$additional_order_fields = array('from_last_name');

			//Need to include all threads that user has posted to.
			$query = '
						SELECT
								c.*,
								a.*,
								b.id as id,
								b.user_id as from_user_id,
								bb.first_name as from_first_name,
								bb.middle_name as from_middle_name,
								bb.last_name as from_last_name
						FROM '. $mrf->getTable() .' as a
							LEFT JOIN '. $msf->getTable() .'	as b ON a.message_sender_id = b.id
							LEFT JOIN '. $uf->getTable() .'		as bb ON b.user_id = bb.id
							LEFT JOIN '. $this->getTable() .'	as c ON b.message_control_id = c.id
							LEFT JOIN '. $uf->getTable() .'		as d ON c.object_type_id = 5 AND c.object_id = d.id
							LEFT JOIN '. $rf->getTable() .'		as f ON c.object_type_id = 50 AND c.object_id = f.id
							LEFT JOIN '. $pptsvf->getTable() .' as h ON c.object_type_id = 90 AND c.object_id = h.id
						WHERE
								a.user_id = ?
								AND bb.company_id = ?
								AND c.object_type_id in (5, 50, 90)
								AND ( a.deleted = 0 AND c.deleted = 0
										AND ( CASE WHEN c.object_type_id = 5 THEN d.deleted = 0 ELSE d.id IS NULL END )
										AND ( CASE WHEN c.object_type_id = 50 THEN f.deleted = 0 ELSE f.id IS NULL END )
										AND ( CASE WHEN c.object_type_id = 90 THEN h.deleted = 0 ELSE h.id IS NULL END )																
									)
						';
		} else {  //Sent
			//Need to include all threads that user has posted to.
			$additional_order_fields = array('to_last_name');

			$query = '
						SELECT
								c.*,
								a.*,
								b.id as id,
								a.user_id as to_user_id,
								bb.first_name as to_first_name,
								bb.middle_name as to_middle_name,
								bb.last_name as to_last_name
						FROM '. $mrf->getTable() .' as a
							LEFT JOIN '. $msf->getTable() .'	as b ON a.message_sender_id = b.id
							LEFT JOIN '. $uf->getTable() .'		as bb ON a.user_id = bb.id
							LEFT JOIN '. $this->getTable() .'	as c ON b.message_control_id = c.id
							LEFT JOIN '. $uf->getTable() .'		as d ON c.object_type_id = 5 AND c.object_id = d.id
							LEFT JOIN '. $rf->getTable() .'		as f ON c.object_type_id = 50 AND c.object_id = f.id
							LEFT JOIN '. $pptsvf->getTable() .' as h ON c.object_type_id = 90 AND c.object_id = h.id
						WHERE
								b.user_id = ?
								AND bb.company_id = ?
								AND c.object_type_id in (5, 50, 90)
								AND ( b.deleted = 0 AND c.deleted = 0
										AND ( CASE WHEN c.object_type_id = 5 THEN d.deleted = 0 ELSE d.id IS NULL END )
										AND ( CASE WHEN c.object_type_id = 50 THEN f.deleted = 0 ELSE f.id IS NULL END )
										AND ( CASE WHEN c.object_type_id = 90 THEN h.deleted = 0 ELSE h.id IS NULL END )																
									)
						';
		}

		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order, $strict, $additional_order_fields );

		$this->rs = $this->ExecuteSQL( $query, $ph, $limit, $page );

		return $this;
	}

	/**
	 * @param string $company_id UUID
	 * @param string $user_id UUID
	 * @param string $id UUID
	 * @param $folder
	 * @param int $limit Limit the number of records returned
	 * @param int $page Page number of records to return for pagination
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool|MessageControlListFactory
	 */
	function getByCompanyIDAndUserIdAndIdAndFolder( $company_id, $user_id, $id, $folder, $limit = NULL, $page = NULL, $where = NULL, $order = NULL ) {
		if ( $company_id == '') {
			return FALSE;
		}

		if ( $user_id == '') {
			return FALSE;
		}

		if ( $id == '') {
			return FALSE;
		}

		$strict = TRUE;
		if ( $order == NULL ) {
			$strict = FALSE;
			$order = array( 'c.status_id' => '= 10 desc', 'a.created_date' => 'desc' );
		}

		$mrf = new MessageRecipientFactory();
		$msf = new MessageSenderFactory();
		$uf = new UserFactory();

		$ph = array(
					'company_id' => TTUUID::castUUID($company_id),

					'id' => TTUUID::castUUID($id),
					'id_b' => TTUUID::castUUID($id),
					'id_c' => TTUUID::castUUID($id),
					'id_d' => TTUUID::castUUID($id),

					'user_id' => TTUUID::castUUID($user_id),
					'user_id_b' => TTUUID::castUUID($user_id),
					//'id_b' => $id,
					//'parent_id' => TTUUID::castUUID($id),
					);

		//Need to include all threads that user has posted to.
		$query = '
					SELECT	a.*,
							b.id as id,
							c.status_id as status_id,
							b.user_id as from_user_id,
							bb.first_name as from_first_name,
							bb.middle_name as from_middle_name,
							bb.last_name as from_last_name,
							c.user_id as to_user_id,
							cb.first_name as to_first_name,
							cb.middle_name as to_middle_name,
							cb.last_name as to_last_name
					FROM '. $this->getTable() .' as a
						LEFT JOIN '. $msf->getTable() .'	as b ON a.id = b.message_control_id
						LEFT JOIN '. $uf->getTable() .'		as bb ON b.user_id = bb.id
						LEFT JOIN '. $mrf->getTable() .'	as c ON b.id = c.message_sender_id
						LEFT JOIN '. $uf->getTable() .'		as cb ON c.user_id = cb.id
					WHERE
							cb.company_id = ? AND cb.company_id = bb.company_id
							AND ( b.id = ?
									OR b.id = ( select parent_id from '. $msf->getTable() .' where id = ? AND parent_id != \''. TTUUID::getZeroID() .'\' )
									OR b.parent_id = ( select parent_id from '. $msf->getTable() .' where id = ? AND parent_id != \''. TTUUID::getZeroID() .'\' )
									OR ( b.parent_id = ? )
								)
							AND ( b.user_id = ? OR c.user_id = ? )
							AND ( a.deleted = 0 AND c.deleted = 0 )
					';

		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order, $strict, array('from_last_name', 'to_last_name') );

		$this->rs = $this->ExecuteSQL( $query, $ph, $limit, $page );

		return $this;
	}

	/**
	 * @param string $company_id UUID
	 * @param string $user_id UUID
	 * @param int $object_type_id
	 * @param string $object_id UUID
	 * @param int $limit Limit the number of records returned
	 * @param int $page Page number of records to return for pagination
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool|MessageControlListFactory
	 */
	function getByCompanyIDAndUserIdAndObjectTypeAndObject( $company_id, $user_id, $object_type_id, $object_id, $limit = NULL, $page = NULL, $where = NULL, $order = NULL ) {
		if ( $company_id == '') {
			return FALSE;
		}

		if ( $user_id == '') {
			return FALSE;
		}

		if ( $object_type_id == '') {
			return FALSE;
		}

		if ( $object_id == '') {
			return FALSE;
		}

		$additional_order_fields = array('from_last_name', 'to_last_name', 'subject', 'object_type_id');

		$strict = TRUE;
		if ( $order == NULL ) {
			$strict = FALSE;
			$order = array( 'a.created_date' => 'desc' );
		}

		$msf = new MessageSenderFactory();
		//$mrf = new MessageRecipientFactory();
		$uf = new UserFactory();

		$ph = array(
					//'user_id' => TTUUID::castUUID($user_id),
					//'user_id_b' => $user_id,
					//'user_id_c' => $user_id,
					'company_id' => TTUUID::castUUID($company_id),
					'object_type_id' => (int)$object_type_id,
					'object_id' => TTUUID::castUUID($object_id),
					);

		//Return status_id column so we can optimize marking messages as read or not.
		//Make sure we don't display duplicate messages when it was sent to multiple superiors.
		//Include messages even if sender/recipeints have deleted theirs.
		//The sub-selects are required so we attempt to return message_ids that were sent to the user currently viewing the messages, that way we can mark them as read.
		//	without this we are unable to mark messages as read, because we are returning essentially random message_recipient id's.
		//	Because PostgreSQL/MySQL don't come with first() aggregate functions, this is pretty much the fastest way to work around it.
		//  NOTE: Need to list all columns in "a." table otherwise MySQL gets a duplicate column error.


		$query = '
					SELECT	DISTINCT a.object_type_id,
							a.object_id,
							a.require_ack,
							a.priority_id,
							a.subject,
							a.body,
							(SELECT xx.id FROM message_recipient as zz LEFT JOIN message_sender as xx ON zz.message_sender_id = xx.id where xx.message_control_id = a.id ORDER BY zz.user_id = \''. TTUUID::castUUID($user_id) .'\' DESC LIMIT 1 ) as id,
							(SELECT zz.status_id FROM message_recipient as zz LEFT JOIN message_sender as xx ON zz.message_sender_id = xx.id where xx.message_control_id = a.id ORDER BY zz.user_id = \''. TTUUID::castUUID($user_id) .'\' DESC LIMIT 1 ) as status_id,
							b.user_id as from_user_id,
							bb.first_name as from_first_name,
							bb.middle_name as from_middle_name,
							bb.last_name as from_last_name,
							a.created_date,
							a.created_by,
							a.updated_date,
							a.updated_by,
							a.deleted_date,
							a.deleted
					FROM '. $this->getTable() .' as a
						LEFT JOIN '. $msf->getTable() .'        as b ON a.id = b.message_control_id
						LEFT JOIN '. $uf->getTable() .'         as bb ON b.user_id = bb.id
					WHERE
							bb.company_id = ?
							AND ( a.object_type_id = ? AND a.object_id = ? )
							AND ( a.deleted = 0 )
					';

//		$query = '
//					SELECT	a.object_type_id,
//							a.object_id,
//							a.require_ack,
//							a.priority_id,
//							a.subject,
//							a.body,
//							(SELECT xx.id FROM message_recipient as zz LEFT JOIN message_sender as xx ON zz.message_sender_id = xx.id where xx.message_control_id = a.id ORDER BY zz.user_id = \''. TTUUID::castUUID($user_id) .'\' DESC LIMIT 1 ) as id,
//							(SELECT zz.status_id FROM message_recipient as zz LEFT JOIN message_sender as xx ON zz.message_sender_id = xx.id where xx.message_control_id = a.id ORDER BY zz.user_id = \''. TTUUID::castUUID($user_id) .'\' DESC LIMIT 1 ) as status_id,
//							b.user_id as from_user_id,
//							bb.first_name as from_first_name,
//							bb.middle_name as from_middle_name,
//							bb.last_name as from_last_name,
//							a.created_date,
//							a.created_by,
//							a.updated_date,
//							a.updated_by,
//							a.deleted_date,
//							a.deleted
//					FROM '. $this->getTable() .' as a
//						LEFT JOIN ( select message_control_id, min(cast(a.id AS VARCHAR(36))) as message_sender_id from '. $msf->getTable() .' as a group by a.message_control_id ) as z ON a.id = z.message_control_id
//						LEFT JOIN '. $msf->getTable() .'	as b ON b.id = cast(z.message_sender_id as UUID)
//						LEFT JOIN '. $uf->getTable() .'		as bb ON b.user_id = bb.id
//						LEFT JOIN '. $mrf->getTable() .'	as c ON b.id = c.message_sender_id
//					WHERE
//							bb.company_id = ?
//							AND ( a.object_type_id = ? AND a.object_id = ? )
//							AND ( a.deleted = 0 )
//					';

		/*
		//This query works as well, but is about twice as slow interestingly enough.
		$query = '
					SELECT	a.*,
							b.id,
							c.status_id,
							b.user_id as from_user_id,
							bb.first_name as from_first_name,
							bb.middle_name as from_middle_name,
							bb.last_name as from_last_name
					FROM '. $this->getTable() .' as a
						LEFT JOIN	(
									select a.message_control_id, CASE WHEN min(CASE WHEN b.user_id = ? THEN b.id*-1 ELSE b.id END) < 0 THEN min(CASE WHEN b.user_id = ? THEN b.id*-1 ELSE b.id END)*-1 ELSE min(CASE WHEN b.user_id = ? THEN b.id*-1 ELSE b.id END) END as message_sender_id from message_sender as a LEFT JOIN message_recipient as b ON a.id = b.message_sender_id group by message_control_id
									) as z ON a.id = z.message_control_id
						LEFT JOIN '. $msf->getTable() .'	as b ON b.id = z.message_sender_id
						LEFT JOIN '. $uf->getTable() .'		as bb ON b.user_id = bb.id
						LEFT JOIN '. $mrf->getTable() .'	as c ON b.id = c.message_sender_id
					WHERE
							bb.company_id = ?
							AND ( a.object_type_id = ? AND a.object_id = ? )
							AND ( a.deleted = 0 )
					';
		*/

		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order, $strict, $additional_order_fields );

		//Debug::Arr($ph, $query, __FILE__, __LINE__, __METHOD__, 10);
		$this->rs = $this->ExecuteSQL( $query, $ph, $limit, $page );

		return $this;
	}

//	function getByCompanyIDAndObjectTypeAndObject( $company_id, $object_type_id, $object_id, $limit = NULL, $page = NULL, $where = NULL, $order = NULL ) {
//		if ( $company_id == '') {
//			return FALSE;
//		}
//
//		if ( $object_type_id == '') {
//			return FALSE;
//		}
//
//		if ( $object_id == '') {
//			return FALSE;
//		}
//
//		$additional_order_fields = array('from_last_name', 'to_last_name', 'subject', 'object_type_id');
//
//		$strict = TRUE;
//		if ( $order == NULL ) {
//			$strict = FALSE;
//			$order = array( 'a.created_date' => 'desc' );
//		}
//
//		$msf = new MessageSenderFactory();
//		$mrf = new MessageRecipientFactory();
//		$uf = new UserFactory();
//
//		$ph = array(
//					'company_id' => TTUUID::castUUID($company_id),
//					'object_type_id' => (int)$object_type_id,
//					'object_id' => TTUUID::castUUID($object_id),
//					);
//
//		//Return status_id column so we can optimize marking messages as read or not.
//		//Make sure we don't display duplicate messages when it was sent to multiple superiors.
//		//Include messages even if sender/recipeints have deleted theirs.
//		$query = '
//					SELECT	a.object_type_id,
//							a.object_id,
//							a.require_ack,
//							a.priority_id,
//							a.subject,
//							a.body,
//							b.id as id,
//							c.status_id as status_id,
//							b.user_id as from_user_id,
//							bb.first_name as from_first_name,
//							bb.middle_name as from_middle_name,
//							bb.last_name as from_last_name,
//							a.created_date,
//							a.created_by,
//							a.updated_date,
//							a.updated_by,
//							a.deleted_date,
//							a.deleted
//					FROM '. $this->getTable() .' as a
//						LEFT JOIN ( select message_control_id, MIN(CAST(a.id AS VARCHAR(36))) as message_sender_id from '. $msf->getTable() .' as a group by a.message_control_id ) as z ON a.id = z.message_control_id
//						LEFT JOIN '. $msf->getTable() .'	as b ON b.id = CAST(z.message_sender_id AS UUID)
//						LEFT JOIN '. $uf->getTable() .'		as bb ON b.user_id = bb.id
//						LEFT JOIN '. $mrf->getTable() .'	as c ON b.id = c.message_sender_id
//					WHERE
//							bb.company_id = ?
//							AND ( a.object_type_id = ? AND a.object_id = ? )
//							AND ( a.deleted = 0 )
//					';
//
//		$query .= $this->getWhereSQL( $where );
//		$query .= $this->getSortSQL( $order, $strict, $additional_order_fields );
//
//		$this->rs = $this->ExecuteSQL( $query, $ph, $limit, $page );
//
//		return $this;
//	}

	/**
	 * Returns all parties involved in a thread, for finding out who "Reply All" should go to.
	 * @param string $company_id UUID
	 * @param int $object_type_id
	 * @param string $object_id UUID
	 * @param int $user_id
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return array|bool
	 */
	function getByCompanyIdAndObjectTypeAndObjectAndNotUser( $company_id, $object_type_id, $object_id, $user_id = 0, $where = NULL, $order = NULL) {
		if ( $company_id == '') {
			return FALSE;
		}

		if ( $object_type_id == '') {
			return FALSE;
		}

		if ( $object_id == '') {
			return FALSE;
		}

		if ( $user_id === 0 ) {
			$user_id = TTUUID::getZeroID();
		}


		$msf = new MessageSenderFactory();
		$mrf = new MessageRecipientFactory();
		$uf = new UserFactory();

		$ph = array(
					'company_id' => TTUUID::castUUID($company_id),
					'object_type_id' => (int)$object_type_id,
					'object_id' => TTUUID::castUUID($object_id),
					);

		$query = '
					SELECT b.user_id as from_user_id,
							c.user_id as to_user_id
					FROM '. $this->getTable() .' as a
						LEFT JOIN '. $msf->getTable() .' as b ON a.id = b.message_control_id
						LEFT JOIN '. $uf->getTable() .' as bb ON b.user_id = bb.id
						LEFT JOIN '. $mrf->getTable() .' as c ON c.message_sender_id = b.id
						LEFT JOIN '. $uf->getTable() .' as cc ON c.user_id = cc.id

					WHERE
							bb.company_id = ? AND bb.company_id = cc.company_id
							AND ( a.object_type_id = ? AND a.object_id = ? )
							AND ( a.deleted = 0 AND bb.deleted = 0 AND cc.deleted = 0 )
					';

		$rs = $this->ExecuteSQL($query, $ph);

		$retarr = array();
		if ( $rs->RecordCount() > 0 ) {
			foreach( $rs as $row ) {
				if ( $user_id != $row['from_user_id'] ) {
					$retarr[] = $row['from_user_id'];
				}
				if ( $user_id != $row['to_user_id'] ) {
					$retarr[] = $row['to_user_id'];
				}
			}

			$retarr = array_unique($retarr);
			//Debug::Arr($retarr, ' Retarr: ', __FILE__, __LINE__, __METHOD__, 10);

			return $retarr;
		}

		return FALSE;
	}

	/**
	 * @param string $company_id UUID
	 * @param $filter_data
	 * @param int $limit Limit the number of records returned
	 * @param int $page Page number of records to return for pagination
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool|MessageControlListFactory
	 */
	function getAPISearchByCompanyIdAndArrayCriteria( $company_id, $filter_data, $limit = NULL, $page = NULL, $where = NULL, $order = NULL ) {
		if ( $company_id == '') {
			return FALSE;
		}

		if ( !isset( $filter_data['current_user_id'] ) ) {
			return FALSE;
		}

		if ( !is_array($order) ) {
			//Use Filter Data ordering if its set.
			if ( isset($filter_data['sort_column']) AND $filter_data['sort_order']) {
				$order = array(Misc::trimSortPrefix($filter_data['sort_column']) => $filter_data['sort_order']);
			}
		}

		$additional_order_fields = array('status_id');

		$sort_column_aliases = array(
									'status' => 'a.status_id',
									'created_date' => 'c.created_date',
									'created_by' => 'c.created_by',
									);

		$order = $this->getColumnsFromAliases( $order, $sort_column_aliases );

		if ( $order == NULL ) {
			$order = array( 'a.status_id' => '= 10 desc', 'a.created_date' => 'desc' );
			$strict = FALSE;
		} else {
			$strict = TRUE;
		}
		//Debug::Arr($order, 'Order Data:', __FILE__, __LINE__, __METHOD__, 10);
		//Debug::Arr($filter_data, 'Filter Data:', __FILE__, __LINE__, __METHOD__, 10);

		if ( !isset($filter_data['folder_id']) ) {
			$filter_data['folder_id'] = 10; //Inbox.
		}

		$mrf = new MessageRecipientFactory();
		$msf = new MessageSenderFactory();
		$rf = new RequestFactory();
		$uf = new UserFactory();
		$pptsvf = new PayPeriodTimeSheetVerifyFactory();

		$ph = array(
					'user_id' => $filter_data['current_user_id'],
					'company_id' => TTUUID::castUUID($company_id),
					);

		if ( $filter_data['folder_id'] == 10 ) { //Inbox
			$additional_order_fields = array('from_last_name');

			//Need to include all threads that user has posted to.
			$query = '
						SELECT
								c.*,
								a.*,
								c.created_date as created_date,
								c.created_by as created_by,
								b.id as id,
								a.user_id as to_user_id,
								aa.first_name as to_first_name,
								aa.middle_name as to_middle_name,
								aa.last_name as to_last_name,
								b.user_id as from_user_id,
								bb.first_name as from_first_name,
								bb.middle_name as from_middle_name,
								bb.last_name as from_last_name
						FROM '. $mrf->getTable() .' as a
							LEFT JOIN '. $uf->getTable() .'		as aa ON a.user_id = aa.id
							LEFT JOIN '. $msf->getTable() .'	as b ON a.message_sender_id = b.id
							LEFT JOIN '. $uf->getTable() .'		as bb ON b.user_id = bb.id
							LEFT JOIN '. $this->getTable() .'	as c ON b.message_control_id = c.id
							LEFT JOIN '. $uf->getTable() .'		as d ON c.object_type_id = 5 AND c.object_id = d.id
							LEFT JOIN '. $rf->getTable() .'		as f ON c.object_type_id = 50 AND c.object_id = f.id
							LEFT JOIN '. $pptsvf->getTable() .' as h ON c.object_type_id = 90 AND c.object_id = h.id
						WHERE
								a.user_id = ?
								AND ( bb.company_id = ? AND aa.company_id = bb.company_id )
								AND c.object_type_id in (5, 50, 90)';

			$query .= ( isset($filter_data['id']) ) ? $this->getWhereClauseSQL( 'b.id', $filter_data['id'], 'uuid_list', $ph ) : NULL;
			$query .= ( isset($filter_data['object_type_id']) ) ? $this->getWhereClauseSQL( 'c.object_type_id', $filter_data['object_type_id'], 'numeric_list', $ph ) : NULL;
			$query .= ( isset($filter_data['status_id']) ) ? $this->getWhereClauseSQL( 'a.status_id', $filter_data['status_id'], 'numeric_list', $ph ) : NULL;
			$query .= ( isset($filter_data['user_id']) ) ? $this->getWhereClauseSQL( 'b.user_id', $filter_data['user_id'], 'uuid_list', $ph ) : NULL;

			$query .= ( isset($filter_data['subject']) ) ? $this->getWhereClauseSQL( 'c.subject', $filter_data['subject'], 'text', $ph ) : NULL;
			$query .= ( isset($filter_data['body']) ) ? $this->getWhereClauseSQL( 'c.body', $filter_data['body'], 'text', $ph ) : NULL;

			$query .= '			AND ( a.deleted = 0 AND c.deleted = 0
										AND ( CASE WHEN c.object_type_id = 5 THEN d.deleted = 0 ELSE d.id IS NULL END )
										AND ( CASE WHEN c.object_type_id = 50 THEN f.deleted = 0 ELSE f.id IS NULL END )
										AND ( CASE WHEN c.object_type_id = 90 THEN h.deleted = 0 ELSE h.id IS NULL END )
									)
						';
		} else {  //Sent
			//Need to include all threads that user has posted to.
			$additional_order_fields = array('to_last_name');

			$query = '
						SELECT
								c.*,
								a.*,
								b.id as id,
								a.user_id as to_user_id,
								aa.first_name as to_first_name,
								aa.middle_name as to_middle_name,
								aa.last_name as to_last_name,
								b.user_id as from_user_id,
								bb.first_name as from_first_name,
								bb.middle_name as from_middle_name,
								bb.last_name as from_last_name
						FROM '. $mrf->getTable() .' as a
							LEFT JOIN '. $uf->getTable() .'		as aa ON a.user_id = aa.id
							LEFT JOIN '. $msf->getTable() .'	as b ON a.message_sender_id = b.id
							LEFT JOIN '. $uf->getTable() .'		as bb ON b.user_id = bb.id
							LEFT JOIN '. $this->getTable() .'	as c ON b.message_control_id = c.id
							LEFT JOIN '. $uf->getTable() .'		as d ON c.object_type_id = 5 AND c.object_id = d.id
							LEFT JOIN '. $rf->getTable() .'		as f ON c.object_type_id = 50 AND c.object_id = f.id
							LEFT JOIN '. $pptsvf->getTable() .' as h ON c.object_type_id = 90 AND c.object_id = h.id
						WHERE
								b.user_id = ?
								AND ( bb.company_id = ? AND aa.company_id = bb.company_id )
								AND c.object_type_id in (5, 50, 90)';

			$query .= ( isset($filter_data['id']) ) ? $this->getWhereClauseSQL( 'b.id', $filter_data['id'], 'uuid_list', $ph ) : NULL;
			$query .= ( isset($filter_data['object_type_id']) ) ? $this->getWhereClauseSQL( 'c.object_type_id', $filter_data['object_type_id'], 'numeric_list', $ph ) : NULL;
			$query .= ( isset($filter_data['status_id']) ) ? $this->getWhereClauseSQL( 'a.status_id', $filter_data['status_id'], 'numeric_list', $ph ) : NULL;
			$query .= ( isset($filter_data['user_id']) ) ? $this->getWhereClauseSQL( 'a.user_id', $filter_data['user_id'], 'uuid_list', $ph ) : NULL;

			$query .= ( isset($filter_data['subject']) ) ? $this->getWhereClauseSQL( 'c.subject', $filter_data['subject'], 'text', $ph ) : NULL;
			$query .= ( isset($filter_data['body']) ) ? $this->getWhereClauseSQL( 'c.body', $filter_data['body'], 'text', $ph ) : NULL;

			$query .= '			AND ( b.deleted = 0 AND c.deleted = 0
										AND ( CASE WHEN c.object_type_id = 5 THEN d.deleted = 0 ELSE d.id IS NULL END )
										AND ( CASE WHEN c.object_type_id = 50 THEN f.deleted = 0 ELSE f.id IS NULL END )
										AND ( CASE WHEN c.object_type_id = 90 THEN h.deleted = 0 ELSE h.id IS NULL END )
									)
						';
		}

		$query .= ( isset($filter_data['created_by']) ) ? $this->getWhereClauseSQL( 'a.created_by', $filter_data['created_by'], 'uuid_list', $ph ) : NULL;
		$query .= ( isset($filter_data['updated_by']) ) ? $this->getWhereClauseSQL( 'a.updated_by', $filter_data['updated_by'], 'uuid_list', $ph ) : NULL;

		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order, $strict, $additional_order_fields );

		$this->rs = $this->ExecuteSQL( $query, $ph, $limit, $page );

		//Debug::Arr($ph, ' Query: '. $query, __FILE__, __LINE__, __METHOD__, 10);

		return $this;
	}

	/**
	 * @param string $company_id UUID
	 * @param $filter_data
	 * @param int $limit Limit the number of records returned
	 * @param int $page Page number of records to return for pagination
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool|MessageControlListFactory
	 */
	function getAPIMessageByCompanyIdAndArrayCriteria( $company_id, $filter_data, $limit = NULL, $page = NULL, $where = NULL, $order = NULL ) {
		if ( $company_id == '') {
			return FALSE;
		}

		if ( !is_array($order) ) {
			//Use Filter Data ordering if its set.
			if ( isset($filter_data['sort_column']) AND $filter_data['sort_order']) {
				$order = array(Misc::trimSortPrefix($filter_data['sort_column']) => $filter_data['sort_order']);
			}
		}

		$strict = TRUE;
		if ( $order == NULL ) {
			$strict = FALSE;
			$order = array( 'a.created_date' => 'asc' );
		}

		$mrf = new MessageRecipientFactory();
		$msf = new MessageSenderFactory();
		$uf = new UserFactory();

		$ph = array(
					'company_id' => TTUUID::castUUID($company_id),

					'id' => $filter_data['id'],
					'id_b' => $filter_data['id'],
					'id_c' => $filter_data['id'],
					'id_d' => $filter_data['id'],

					'user_id' => $filter_data['current_user_id'],
					'user_id_b' => $filter_data['current_user_id'],
					//'id_b' => $id,
					//'parent_id' => TTUUID::castUUID($id),
					);

		//Need to include all threads that user has posted to.
		$query = '
					SELECT	a.*,
							b.id as id,
							c.status_id as status_id,
							b.user_id as from_user_id,
							bb.first_name as from_first_name,
							bb.middle_name as from_middle_name,
							bb.last_name as from_last_name,
							c.user_id as to_user_id,
							cb.first_name as to_first_name,
							cb.middle_name as to_middle_name,
							cb.last_name as to_last_name
					FROM '. $this->getTable() .' as a
						LEFT JOIN '. $msf->getTable() .'	as b ON a.id = b.message_control_id
						LEFT JOIN '. $uf->getTable() .'		as bb ON b.user_id = bb.id
						LEFT JOIN '. $mrf->getTable() .'	as c ON b.id = c.message_sender_id
						LEFT JOIN '. $uf->getTable() .'		as cb ON c.user_id = cb.id
					WHERE
							cb.company_id = ? AND cb.company_id = bb.company_id
							AND ( b.id = ?
									OR b.id = ( select parent_id from '. $msf->getTable() .' where id = ? AND parent_id != \''. TTUUID::getZeroID() .'\' )
									OR b.parent_id = ( select parent_id from '. $msf->getTable() .' where id = ? AND parent_id != \''. TTUUID::getZeroID() .'\' )
									OR ( b.parent_id = ? )
								)
							AND ( b.user_id = ? OR c.user_id = ? )
							AND ( a.deleted = 0 )
					'; //Don't check c.deleted = 0 (message recipient table), as the recipient could delete the message and that would cause the sender to no longer be able to view it.

		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order, $strict, array('from_last_name', 'to_last_name') );

		$this->rs = $this->ExecuteSQL( $query, $ph, $limit, $page );

		return $this;
	}

}
?>
