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
class UserGenericStatusListFactory extends UserGenericStatusFactory implements IteratorAggregate {

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
	 * @return bool|UserGenericStatusListFactory
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
	 * @return bool|UserGenericStatusListFactory
	 */
	function getByCompanyId( $company_id, $where = NULL, $order = NULL) {
		if ( $company_id == '') {
			return FALSE;
		}

		$uf = new UserFactory();

		$ph = array(
					'company_id' => TTUUID::castUUID($company_id)
					);

		$query = '
					select	a.*
					from	'. $this->getTable() .' as a
						LEFT JOIN  '. $uf->getTable() .' as b on a.user_id = b.id
					where	b.company_id = ?
						AND a.deleted = 0';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );

		$this->rs = $this->ExecuteSQL($query, $ph);

		return $this;
	}

	/**
	 * @param string $id UUID
	 * @param string $company_id UUID
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool|UserGenericStatusListFactory
	 */
	function getByIdAndCompanyId( $id, $company_id, $where = NULL, $order = NULL) {
		if ( $id == '') {
			return FALSE;
		}

		if ( $company_id == '') {
			return FALSE;
		}

		$uf = new UserFactory();

		$ph = array(
					'id' => TTUUID::castUUID($id),
					'company_id' => TTUUID::castUUID($company_id)
					);

		$query = '
					select	a.*
					from	'. $this->getTable() .' as a
						LEFT JOIN  '. $uf->getTable() .' as b on a.user_id = b.id
					where	a.id = ?
						AND b.company_id = ?
						AND a.deleted = 0';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );

		$this->rs = $this->ExecuteSQL($query, $ph);

		return $this;
	}

	/**
	 * @param string $id UUID
	 * @param int $limit Limit the number of records returned
	 * @param int $page Page number of records to return for pagination
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool|UserGenericStatusListFactory
	 */
	function getByUserId( $id, $limit = NULL, $page = NULL, $where = NULL, $order = NULL) {
		if ( $id == '') {
			return FALSE;
		}

		$ph = array(
					'user_id' => TTUUID::castUUID($id),
					);

		$query = '
					select	*
					from	'. $this->getTable() .'
					where	user_id = ?
							AND deleted = 0';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );

		$this->rs = $this->ExecuteSQL( $query, $ph, $limit, $page );

		return $this;
	}

	/**
	 * @param string $user_id UUID
	 * @param string $batch_id UUID
	 * @param int $limit Limit the number of records returned
	 * @param int $page Page number of records to return for pagination
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool|UserGenericStatusListFactory
	 */
	function getByUserIdAndBatchId( $user_id, $batch_id, $limit = NULL, $page = NULL, $where = NULL, $order = NULL) {
		if ( $user_id == '') {
			return FALSE;
		}

		if ( $batch_id == '') {
			return FALSE;
		}

		if ( $order == NULL ) {
			$order = array( 'status_id' => 'asc', 'label' => 'asc' );
			$strict = FALSE;
		} else {
			$strict = TRUE;
		}

		$ph = array(
					'user_id' => TTUUID::castUUID($user_id),
					'batch_id' => TTUUID::castUUID($batch_id),
					);

		$query = '
					select	*
					from	'. $this->getTable() .'
					where	user_id = ?
						AND batch_id = ?
						AND deleted = 0';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order, $strict );

		$this->rs = $this->ExecuteSQL( $query, $ph, $limit, $page );

		return $this;
	}

	/**
	 * @param string $user_id UUID
	 * @param string $batch_id UUID
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return array|bool
	 */
	function getStatusCountArrayByUserIdAndBatchId( $user_id, $batch_id, $where = NULL, $order = NULL) {
		if ( $user_id == '') {
			return FALSE;
		}

		if ( $batch_id == '') {
			return FALSE;
		}

		$ph = array(
					'user_id' => TTUUID::castUUID($user_id),
					'batch_id' => TTUUID::castUUID($batch_id),
					);

		$query = '
					select	status_id, count(*) as total
					from	'. $this->getTable() .'
					where	user_id = ?
						AND batch_id = ?
						AND deleted = 0
					GROUP BY status_id';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );

		$result = $this->db->GetArray($query, $ph);

		$total = 0;
		foreach( $result as $row ) {
			$total = ( $total + $row['total'] );
		}
		$retarr = array();
		$retarr['total'] = $total;

		$retarr['status'] = array(
								10 => array('total' => 0, 'percent' => 0),
								20 => array('total' => 0, 'percent' => 0),
								30 => array('total' => 0, 'percent' => 0),
								);

		foreach( $result as $row ) {
			$retarr['status'][$row['status_id']] = array('total' => $row['total'], 'percent' => round( ( ($row['total'] / $total) * 100 ), 1 ) );
		}

		if ( empty($retarr) == FALSE ) {
			return $retarr;
		}

		return FALSE;
	}

}
?>
