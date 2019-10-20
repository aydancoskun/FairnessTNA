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
class MessageSenderListFactory extends MessageSenderFactory implements IteratorAggregate {

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
	 * @return bool|MessageSenderListFactory
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
	 * @return bool|MessageSenderListFactory
	 */
	function getByCompanyId( $company_id, $where = NULL, $order = NULL) {
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
						LEFT JOIN '. $uf->getTable() .' as b ON a.user_id = b.id
					WHERE
							b.company_id = ?
							AND ( a.deleted = 0 AND b.deleted = 0 )
					';
		$this->rs = $this->ExecuteSQL( $query, $ph );

		return $this;
	}

	/**
	 * @param string $company_id UUID
	 * @param string $id UUID
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool|MessageSenderListFactory
	 */
	function getByCompanyIdAndId( $company_id, $id, $where = NULL, $order = NULL) {
		if ( $company_id == '') {
			return FALSE;
		}

		if ( $id == '') {
			return FALSE;
		}

		$uf = new UserFactory();

		$ph = array(
					'company_id' => TTUUID::castUUID($company_id),
					);

		$query = '
					SELECT a.*
					FROM '. $this->getTable() .' as a
						LEFT JOIN '. $uf->getTable() .' as b ON a.user_id = b.id
					WHERE
							b.company_id = ?
							AND a.id in ('. $this->getListSQL( $id, $ph, 'uuid' ) .')
							AND a.deleted = 0
					';
		$this->rs = $this->ExecuteSQL( $query, $ph );

		return $this;
	}

	/**
	 * @param string $company_id UUID
	 * @param string $id UUID
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool|MessageSenderListFactory
	 */
	function getByCompanyIdAndRecipientId( $company_id, $id, $where = NULL, $order = NULL) {
		if ( $company_id == '') {
			return FALSE;
		}

		if ( $id == '') {
			return FALSE;
		}

		$mrf = new MessageRecipientFactory();
		$uf = new UserFactory();

		$ph = array(
					'company_id' => TTUUID::castUUID($company_id),
					);

		//Ignore deleted message_sender rows, as the sender could have deleted the original message.
		$query = '
					SELECT a.*
					FROM '. $this->getTable() .' as a
						LEFT JOIN '. $mrf->getTable() .' as b ON a.id = b.message_sender_id
						LEFT JOIN '. $uf->getTable() .' as c ON a.user_id = c.id
					WHERE
							c.company_id = ?
							AND b.id in ('. $this->getListSQL( $id, $ph, 'uuid' ) .')
							AND ( b.deleted = 0 )
					';
		$this->rs = $this->ExecuteSQL( $query, $ph );

		return $this;
	}

	/**
	 * @param string $company_id UUID
	 * @param int $object_type_id
	 * @param string $object_id UUID
	 * @param int $user_id
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool|MessageSenderListFactory
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

		$uf = new UserFactory();
		$mcf = new MessageControlFactory();

		$ph = array(
					'company_id' => TTUUID::castUUID($company_id),
					'object_type_id' => (int)$object_type_id,
					'object_id' => TTUUID::castUUID($object_id),
					'user_id' => TTUUID::castUUID($user_id),
					);

		$query = '
					SELECT a.*
					FROM '. $this->getTable() .' as a
						LEFT JOIN '. $mcf->getTable() .' as b ON a.message_control_id = b.id
						LEFT JOIN '. $uf->getTable() .' as c ON a.user_id = c.id
					WHERE
							c.company_id = ?
							AND ( b.object_type_id = ? AND b.object_id = ? )
							AND a.user_id != ?
							AND ( b.deleted = 0 AND c.deleted = 0 )
					';
		$this->rs = $this->ExecuteSQL( $query, $ph );

		return $this;
	}

	/**
	 * @param string $company_id UUID
	 * @param string $user_id UUID
	 * @param string $id UUID
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool|MessageSenderListFactory
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
					'company_id' => TTUUID::castUUID($company_id),
					'user_id' => TTUUID::castUUID($user_id),
					);

		$query = '
					SELECT a.*
					FROM '. $this->getTable() .' as a
						LEFT JOIN '. $uf->getTable() .' as b ON a.user_id = b.id
					WHERE
							b.company_id = ?
							AND a.user_id = ?
							AND a.id in ('. $this->getListSQL( $id, $ph, 'uuid' ) .')
							AND a.deleted = 0
					';
		$this->rs = $this->ExecuteSQL( $query, $ph );

		return $this;
	}

}
?>
