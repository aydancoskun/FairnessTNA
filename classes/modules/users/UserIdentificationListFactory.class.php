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
 * @package Modules\Users
 */
class UserIdentificationListFactory extends UserIdentificationFactory implements IteratorAggregate {

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
	 * @return bool|UserIdentificationListFactory
	 */
	function getById( $id, $where = NULL, $order = NULL) {
		if ( $id == '') {
			return FALSE;
		}

		$this->rs = $this->getCache($id);
		if ( $this->rs === FALSE ) {
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

			$this->saveCache($this->rs, $id);
		}

		return $this;
	}

	/**
	 * @param int $type_id
	 * @param $value
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool|UserIdentificationListFactory
	 */
	function getByTypeIdAndValue( $type_id, $value, $order = NULL) {
		if ( $type_id == '') {
			return FALSE;
		}

		if ( $value == '') {
			return FALSE;
		}

		$ph = array(
					'type_id' => (int)$type_id,
					'value' => $value,
					);

		$query = '
					select	a.*
					from	'. $this->getTable() .' as a
					where	a.type_id = ?
						AND a.value = ?
						AND a.deleted = 0';
		$query .= $this->getSortSQL( $order );

		$this->rs = $this->ExecuteSQL( $query, $ph );

		return $this;
	}

	/**
	 * @param string $id UUID
	 * @param int $limit Limit the number of records returned
	 * @param int $page Page number of records to return for pagination
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool|UserIdentificationListFactory
	 */
	function getByCompanyId( $id, $limit = NULL, $page = NULL, $where = NULL, $order = NULL) {
		if ( $id == '') {
			return FALSE;
		}

		$uf = new UserFactory();

		$ph = array(
					'id' => TTUUID::castUUID($id),
					);


		$query = '
					select	a.*
					from	'. $this->getTable() .' as a,
							'. $uf->getTable() .' as b
					where	a.user_id = b.id
						AND	b.company_id = ?
						AND ( a.deleted = 0	 AND b.deleted = 0) ';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );

		$this->rs = $this->ExecuteSQL( $query, $ph, $limit, $page );

		return $this;
	}

	/**
	 * @param string $id UUID
	 * @param string $company_id UUID
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool|UserIdentificationListFactory
	 */
	function getByIdAndCompanyId( $id, $company_id, $order = NULL) {
		if ( $id == '') {
			return FALSE;
		}

		if ( $company_id == '') {
			return FALSE;
		}

		$uf = new UserFactory();

		$ph = array(
					'company_id' => TTUUID::castUUID($company_id),
					'id' => TTUUID::castUUID($id),
					);

		$query = '
					select	a.*
					from	'. $this->getTable() .' as a,
							'. $uf->getTable() .' as b
					where	a.user_id = b.id
						AND	b.company_id = ?
						AND	a.id = ?
						AND a.deleted = 0';
		$query .= $this->getSortSQL( $order );

		$this->rs = $this->ExecuteSQL( $query, $ph );

		return $this;
	}

	/**
	 * @param string $company_id UUID
	 * @param int[] $type_id
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool|UserIdentificationListFactory
	 */
	function getByCompanyIdAndTypeId( $company_id, $type_id, $order = NULL) {
		if ( $company_id == '') {
			return FALSE;
		}

		if ( $type_id == '') {
			return FALSE;
		}

		if ( $order == NULL ) {
			$order = array( 'a.user_id' => 'asc', 'a.type_id' => 'asc', 'a.number' => 'asc', 'a.id' => 'asc' );
			$strict = FALSE;
		} else {
			$strict = TRUE;
		}

		$uf = new UserFactory();

		$ph = array(
					'company_id' => TTUUID::castUUID($company_id),
					);

		$query = '
					select	a.*
					from	'. $this->getTable() .' as a,
							'. $uf->getTable() .' as b
					where	a.user_id = b.id
						AND	b.company_id = ?
						AND type_id in ('. $this->getListSQL( $type_id, $ph, 'int' ) .')
						AND b.status_id = 10
						AND ( a.deleted = 0 AND b.deleted = 0 )';
		$query .= $this->getSortSQL( $order, $strict );

		$this->rs = $this->ExecuteSQL( $query, $ph );

		return $this;
	}

	/**
	 * @param string $company_id UUID
	 * @param int[] $type_id
	 * @param int $date EPOCH
	 * @param array $valid_user_ids
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool|UserIdentificationListFactory
	 */
	function getByCompanyIdAndTypeIdAndDateAndValidUserIDs( $company_id, $type_id, $date = NULL, $valid_user_ids = array(), $order = NULL) {
		if ( $company_id == '') {
			return FALSE;
		}

		if ( $type_id == '') {
			return FALSE;
		}

		if ( $date == '') {
			$date = 0;
		}

		if ( $order == NULL ) {
			$order = array( 'a.user_id' => 'asc', 'a.type_id' => 'asc', 'a.number' => 'asc' );
			$strict = FALSE;
		} else {
			$strict = TRUE;
		}

		$uf = new UserFactory();

		$ph = array(
					'company_id' => TTUUID::castUUID($company_id),
					);

		//If the user record is modified, we have to consider the identification record to be modified as well,
		//otherwise a terminated employee re-hired will not have their old prox/fingerprint records put back on the clock.
		$query = '
					select	a.*
					from	'. $this->getTable() .' as a,
							'. $uf->getTable() .' as b
					where	a.user_id = b.id
						AND	b.company_id = ?
						AND b.status_id = 10
						AND type_id in ('. $this->getListSQL( $type_id, $ph, 'int' ) .')
				';

		if ( ( isset($date) AND $date > 0) OR ( isset($valid_user_ids) AND is_array($valid_user_ids) AND count($valid_user_ids) > 0 ) ) {
			$query .= ' AND ( ';

			//When the Mobile App/TimeClock are doing a reload database, $date should always be 0. That forces the query to just send data for $valid_user_ids.
			//  All other cases it will send data for all current users always, or records that were recently created/updated.
			if ( isset($date) AND $date > 0 ) {
				//Append the same date twice for created and updated.
				$ph[] = (int)$date;
				$ph[] = (int)$date;
				$ph[] = (int)$date;
				$ph[] = (int)$date;
				$query	.=	'	( ( a.created_date >= ? OR a.updated_date >= ? ) OR ( b.created_date >= ? OR b.updated_date >= ? ) ) ';
			}

			//Valid USER IDs is an "OR", so if any IDs are specified they should *always* be included, regardless of the $date variable.
			if ( isset($valid_user_ids) AND is_array($valid_user_ids) AND count($valid_user_ids) > 0 ) {
				if ( isset($date) AND $date > 0 ) {
					$query .= ' OR ';
				}
				$query	.=	' a.user_id in ('. $this->getListSQL( $valid_user_ids, $ph, 'uuid') .') ';
			}

			$query .= ' ) ';
		}

		$query .= ' AND ( a.deleted = 0 AND b.deleted = 0 )';

		$query .= $this->getSortSQL( $order, $strict );

		$this->rs = $this->ExecuteSQL( $query, $ph );

		return $this;
	}

	/**
	 * @param string $company_id UUID
	 * @param int $type_id
	 * @param $value
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool|UserIdentificationListFactory
	 */
	function getByCompanyIdAndTypeIdAndValue( $company_id, $type_id, $value, $order = NULL) {
		if ( $company_id == '') {
			return FALSE;
		}

		if ( $type_id == '') {
			return FALSE;
		}

		if ( $value == '') {
			return FALSE;
		}

		$uf = new UserFactory();

		$ph = array(
					'company_id' => TTUUID::castUUID($company_id),
					'type_id' => (int)$type_id,
					'value' => $value,
					);

		$query = '
					select	a.*
					from	'. $this->getTable() .' as a,
							'. $uf->getTable() .' as b
					where	a.user_id = b.id
						AND	b.company_id = ?
						AND	a.type_id = ?
						AND a.value = ?
						AND a.deleted = 0';
		$query .= $this->getSortSQL( $order );

		$this->rs = $this->ExecuteSQL( $query, $ph );

		return $this;
	}

	/**
	 * @param string $user_id UUID
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool|UserIdentificationListFactory
	 */
	function getByUserId( $user_id, $order = NULL) {
		if ( $user_id == '') {
			return FALSE;
		}

		$ph = array(
					'user_id' => TTUUID::castUUID($user_id),
					);

		$query = '
					select	*
					from	'. $this->getTable() .'
					where	user_id = ?
						AND deleted = 0';
		$query .= $this->getSortSQL( $order );

		$this->rs = $this->ExecuteSQL( $query, $ph );

		return $this;
	}

	/**
	 * @param string $user_id UUID
	 * @param int[] $type_id
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool|UserIdentificationListFactory
	 */
	function getByUserIdAndTypeId( $user_id, $type_id, $order = NULL) {
		if ( $user_id == '') {
			return FALSE;
		}

		if ( $type_id == '') {
			return FALSE;
		}

		if ( $order == NULL ) {
			$order = array( 'user_id' => 'asc', 'type_id' => 'asc', 'number' => 'asc' );
			$strict = FALSE;
		} else {
			$strict = TRUE;
		}

		$ph = array(
					'user_id' => TTUUID::castUUID($user_id),
					);

		$query = '
					select	*
					from	'. $this->getTable() .'
					where	user_id = ?
						AND type_id in ('. $this->getListSQL( $type_id, $ph, 'int' ) .')
						AND deleted = 0';
		$query .= $this->getSortSQL( $order, $strict );

		$this->rs = $this->ExecuteSQL( $query, $ph );

		return $this;
	}

	/**
	 * @param string $user_id UUID
	 * @param int $type_id
	 * @param $number
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool|UserIdentificationListFactory
	 */
	function getByUserIdAndTypeIdAndNumber( $user_id, $type_id, $number, $order = NULL) {
		if ( $user_id == '') {
			return FALSE;
		}

		if ( $type_id == '') {
			return FALSE;
		}

		if ( $number === '') {
			return FALSE;
		}

		if ( $order == NULL ) {
			$order = array( 'number' => 'asc', 'id' => 'asc' );
			$strict = FALSE;
		} else {
			$strict = TRUE;
		}

		$ph = array(
					'user_id' => TTUUID::castUUID($user_id),
					'type_id' => (int)$type_id,
					'number' => $number,
					);

		$query = '
					select	*
					from	'. $this->getTable() .'
					where	user_id = ?
						AND type_id = ?
						AND number = ?
						AND deleted = 0';
		$query .= $this->getSortSQL( $order, $strict );

		$this->rs = $this->ExecuteSQL( $query, $ph );

		return $this;
	}

	/**
	 * @param string $company_id UUID
	 * @param string $user_id UUID
	 * @param int $type_id
	 * @param $number
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool|UserIdentificationListFactory
	 */
	function getByCompanyIdAndUserIdAndTypeIdAndNumber( $company_id, $user_id, $type_id, $number, $order = NULL) {
		if ( $company_id == '') {
			return FALSE;
		}

		if ( $user_id == '') {
			return FALSE;
		}

		if ( $type_id == '') {
			return FALSE;
		}

		if ( $number === '') {
			return FALSE;
		}

		if ( $order == NULL ) {
			$order = array( 'a.number' => 'asc', 'a.id' => 'asc' );
			$strict = FALSE;
		} else {
			$strict = TRUE;
		}

		$uf = new UserFactory();

		$ph = array(
					'company_id' => TTUUID::castUUID($company_id),
					'user_id' => TTUUID::castUUID($user_id),
					'type_id' => (int)$type_id,
					'number' => $number,
					);

		$query = '
					select	a.*
					from	'. $this->getTable() .' as a,
							'. $uf->getTable() .' as b
					where	a.user_id = b.id
						AND	b.company_id = ?
						AND a.user_id = ?
						AND a.type_id = ?
						AND a.number = ?
						AND a.deleted = 0';
		$query .= $this->getSortSQL( $order, $strict );

		$this->rs = $this->ExecuteSQL( $query, $ph );

		return $this;
	}

	/**
	 * @param string $user_id UUID
	 * @param int $type_id
	 * @param $value
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool|UserIdentificationListFactory
	 */
	function getByUserIdAndTypeIdAndValue( $user_id, $type_id, $value, $order = NULL) {
		if ( $user_id == '') {
			return FALSE;
		}

		if ( $type_id == '') {
			return FALSE;
		}

		if ( $value === '') {
			return FALSE;
		}

		$ph = array(
					'user_id' => TTUUID::castUUID($user_id),
					'type_id' => (int)$type_id,
					'value' => $value,
					);

		$query = '
					select	*
					from	'. $this->getTable() .'
					where	user_id = ?
						AND type_id = ?
						AND value = ?
						AND deleted = 0';
		$query .= $this->getSortSQL( $order );

		$this->rs = $this->ExecuteSQL( $query, $ph );

		return $this;
	}

	/**
	 * @param string $user_id UUID
	 * @param int $date EPOCH
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool
	 */
	function getIsModifiedByUserIdAndDate( $user_id, $date, $where = NULL, $order = NULL) {
		if ( $user_id == '') {
			return FALSE;
		}

		if ( $date == '') {
			return FALSE;
		}

		$ph = array(
					'user_id' => TTUUID::castUUID($user_id),
					'created_date' => $date,
					'updated_date' => $date,
					);

		//INCLUDE Deleted rows in this query.
		$query = '
					select	*
					from	'. $this->getTable() .'
					where	user_id = ?
						AND
							( created_date >= ? OR updated_date >= ? )
						';
		$query .= $this->getSortSQL( $order );

		$this->rs = $this->ExecuteSQL( $query, $ph );
		if ( $this->getRecordCount() > 0 ) {
			Debug::text('User Identification rows have been modified: '. $this->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
			return TRUE;
		}
		Debug::text('User Identification rows have NOT been modified', __FILE__, __LINE__, __METHOD__, 10);
		return FALSE;
	}

	/**
	 * @param string $company_id UUID
	 * @param int $date EPOCH
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool
	 */
	function getIsModifiedByCompanyIdAndDate( $company_id, $date, $where = NULL, $order = NULL) {
		if ( $company_id == '') {
			return FALSE;
		}

		if ( $date == '') {
			return FALSE;
		}

		$uf = new UserFactory();

		$ph = array(
					'company_id' => TTUUID::castUUID($company_id),
					'created_date' => $date,
					'updated_date' => $date,
					'user_created_date' => $date,
					'user_updated_date' => $date,
					'deleted_date' => $date,
					'user_deleted_date' => $date,
					);

		//INCLUDE Deleted rows in this query.
		//If the user record is modified, we have to consider the identification record to be modified as well,
		//otherwise a terminated employee re-hired will not have their old prox/fingerprint records put back on the clock.
		$query = '
					select	*
					from	'. $this->getTable() .' as a
					LEFT JOIN '. $uf->getTable() .' as b ON a.user_id = b.id
					where	b.company_id = ?
						AND
							( ( a.created_date >= ? OR a.updated_date >= ? ) OR ( b.created_date >= ? OR b.updated_date >= ? )
								OR ( a.deleted = 1 AND a.deleted_date >= ? ) OR ( b.deleted = 1 AND b.deleted_date >= ? )  )
						';
		$query .= $this->getSortSQL( $order );

		$this->rs = $this->db->SelectLimit($query, 1, -1, $ph);
		if ( $this->getRecordCount() > 0 ) {
			Debug::text('User Identification rows have been modified: '. $this->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
			return TRUE;
		}
		Debug::text('User Identification rows have NOT been modified', __FILE__, __LINE__, __METHOD__, 10);
		return FALSE;
	}

	/**
	 * @param string $user_id UUID
	 * @param string $company_id UUID
	 * @param int $limit Limit the number of records returned
	 * @param int $page Page number of records to return for pagination
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool|UserIdentificationListFactory
	 */
	function getByUserIdAndCompanyId( $user_id, $company_id, $limit = NULL, $page = NULL, $where = NULL, $order = NULL) {
		if ( empty($user_id) OR $user_id == TTUUID::getZeroID() ) {
			return FALSE;
		}

		if ( empty($company_id) OR $company_id == TTUUID::getZeroID() ) {
			return FALSE;
		}

		if ( $order == NULL ) {
			$strict = FALSE;
		} else {
			$strict = TRUE;
		}

		$uf = new UserFactory();

		$ph = array(
					'company_id' => TTUUID::castUUID($company_id),
					'user_id' => TTUUID::castUUID($user_id),
					);

		$query = '
					select	*
					from	'. $uf->getTable() .' as a,
							'. $this->getTable() .' as b
					where	a.id = b.user_id
						AND a.company_id = ?
						AND	b.user_id = ?
						AND b.deleted = 0';
		$query .= $this->getSortSQL( $order, $strict );

		$this->rs = $this->ExecuteSQL( $query, $ph, $limit, $page );

		return $this;
	}
}
?>