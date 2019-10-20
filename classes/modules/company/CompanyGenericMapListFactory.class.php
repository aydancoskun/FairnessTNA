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
 * @package Modules\Policy
 */
class CompanyGenericMapListFactory extends CompanyGenericMapFactory implements IteratorAggregate {

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
					from	'. $this->getTable();
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );

		$this->rs = $this->ExecuteSQL( $query, NULL, $limit, $page );

		return $this;
	}

	/**
	 * @param string $id UUID
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool|CompanyGenericMapListFactory
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
					';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );

		$this->rs = $this->ExecuteSQL( $query, $ph );

		return $this;
	}

	/**
	 * @param string $id UUID
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool|CompanyGenericMapListFactory
	 */
	function getByCompanyId( $id, $where = NULL, $order = NULL) {
		if ( $id == '') {
			return FALSE;
		}

		$ph = array(
					'id' => TTUUID::castUUID($id),
					);


		$query = '
					select	*
					from	'. $this->getTable() .'
					where	company_id = ?
					';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );

		$this->rs = $this->ExecuteSQL( $query, $ph );

		return $this;
	}

	/**
	 * @param string $company_id UUID
	 * @param string $id UUID
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool|CompanyGenericMapListFactory
	 */
	function getByCompanyIDAndObjectType( $company_id, $id, $where = NULL, $order = NULL) {
		if ( $company_id == '') {
			return FALSE;
		}

		if ( $id == '') {
			return FALSE;
		}

		$ph = array(
						'company_id' => TTUUID::castUUID($company_id)
					);

		$query = '
					select	a.*
					from	'. $this->getTable() .' as a
					where	a.company_id = ?
						AND a.object_type_id in ('. $this->getListSQL( $id, $ph, 'int' ) .')
					';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );

		$this->rs = $this->ExecuteSQL( $query, $ph );

		return $this;
	}

	/**
	 * @param string $company_id UUID
	 * @param int $object_type_id
	 * @param string $id UUID
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool|CompanyGenericMapListFactory
	 */
	function getByCompanyIDAndObjectTypeAndObjectID( $company_id, $object_type_id, $id, $where = NULL, $order = NULL) {
		if ( $company_id == '') {
			return FALSE;
		}

		if ( $object_type_id == '') {
			return FALSE;
		}

		if ( $id == '') {
			return FALSE;
		}

		$cache_id = md5( $company_id . serialize($object_type_id) . serialize($id) );
		//Debug::Text('Cache ID: '. $cache_id .' Company ID: '. $company_id .' Object Type: '. $object_type_id .' ID: '. $id, __FILE__, __LINE__, __METHOD__, 10);

		$this->rs = $this->getCache( $cache_id );
		if ( $this->rs === FALSE ) {
			$ph = array(
							'company_id' => TTUUID::castUUID($company_id)
						);


			$query = '
						select	a.*
						from	'. $this->getTable() .' as a
						where	a.company_id = ?
							AND a.object_type_id in ('. $this->getListSQL( $object_type_id, $ph, 'int' ) .')
							AND a.object_id in ('. $this->getListSQL( $id, $ph, 'uuid' ) .')
						';
			$query .= $this->getWhereSQL( $where );
			$query .= $this->getSortSQL( $order );

			$this->rs = $this->ExecuteSQL( $query, $ph );

			$this->saveCache($this->rs, $cache_id );
		}

		return $this;
	}

	/**
	 * @param string $company_id UUID
	 * @param int $object_type_id
	 * @param string $id UUID
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool|CompanyGenericMapListFactory
	 */
	function getByCompanyIDAndObjectTypeAndMapID( $company_id, $object_type_id, $id, $where = NULL, $order = NULL) {
		if ( $company_id == '') {
			return FALSE;
		}

		if ( $object_type_id == '') {
			return FALSE;
		}

		if ( $id == '') {
			return FALSE;
		}

		$ph = array(
						'company_id' => TTUUID::castUUID($company_id)
					);

		$query = '
					select	a.*
					from	'. $this->getTable() .' as a
					where	a.company_id = ?
						AND a.object_type_id in ('. $this->getListSQL( $object_type_id, $ph, 'int' ) .')
						AND a.map_id in ('. $this->getListSQL( $id, $ph, 'uuid' ) .')
					';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );
		$this->rs = $this->ExecuteSQL( $query, $ph );

		return $this;
	}

	/**
	 * @param string $company_id UUID
	 * @param int $object_type_id
	 * @param string $id UUID
	 * @param string $map_id UUID
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool|CompanyGenericMapListFactory
	 */
	function getByCompanyIDAndObjectTypeAndObjectIDAndMapID( $company_id, $object_type_id, $id, $map_id, $where = NULL, $order = NULL) {
		if ( $company_id == '') {
			return FALSE;
		}

		if ( $object_type_id == '') {
			return FALSE;
		}

		if ( $id == '') {
			return FALSE;
		}

		$ph = array(
						'company_id' => TTUUID::castUUID($company_id)
					);

		$query = '
					select	a.*
					from	'. $this->getTable() .' as a
					where	a.company_id = ?
						AND a.object_type_id in ('. $this->getListSQL( $object_type_id, $ph, 'int' ) .')
						AND a.object_id in ('. $this->getListSQL( $id, $ph, 'uuid' ) .')
						AND a.map_id in ('. $this->getListSQL( $map_id, $ph, 'uuid' ) .')
					';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );

		$this->rs = $this->ExecuteSQL( $query, $ph );

		return $this;
	}

	/**
	 * @param string $company_id UUID
	 * @param int $object_type_id
	 * @param string $id UUID
	 * @param string $map_id UUID
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool|CompanyGenericMapListFactory
	 */
	function getByCompanyIDAndObjectTypeAndObjectIDAndNotMapID( $company_id, $object_type_id, $id, $map_id, $where = NULL, $order = NULL) {
		if ( $company_id == '') {
			return FALSE;
		}

		if ( $object_type_id == '') {
			return FALSE;
		}

		if ( $id == '') {
			return FALSE;
		}

		$ph = array(
						'company_id' => TTUUID::castUUID($company_id)
					);

		$query = '
					select	a.*
					from	'. $this->getTable() .' as a
					where	a.company_id = ?
						AND a.object_type_id in ('. $this->getListSQL( $object_type_id, $ph, 'int' ) .')
						AND a.object_id in ('. $this->getListSQL( $id, $ph, 'uuid' ) .')
						AND a.map_id not in ('. $this->getListSQL( $map_id, $ph, 'uuid' ) .')
					';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );

		$this->rs = $this->ExecuteSQL( $query, $ph );

		return $this;
	}

	/**
	 * @param string $id UUID
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool|CompanyGenericMapListFactory
	 */
	function getByObjectType( $id, $where = NULL, $order = NULL) {
		if ( $id == '') {
			return FALSE;
		}

		$ph = array();

		$query = '
					select	a.*
					from	'. $this->getTable() .' as a
					where	a.object_type_id in ('. $this->getListSQL( $id, $ph, 'int' ) .')
					';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );

		$this->rs = $this->ExecuteSQL( $query, $ph );

		return $this;
	}

	/**
	 * @param int $object_type_id
	 * @param string $id UUID
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool|CompanyGenericMapListFactory
	 */
	function getByObjectTypeAndObjectID( $object_type_id, $id, $where = NULL, $order = NULL) {
		if ( $object_type_id == '') {
			return FALSE;
		}

		if ( $id == '') {
			return FALSE;
		}

		$ph = array();

		$query = '
					select	a.*
					from	'. $this->getTable() .' as a
					where	a.object_type_id in ('.	 $this->getListSQL( $object_type_id, $ph, 'int' ) .')
						AND a.object_id in ('.	$this->getListSQL( $id, $ph, 'uuid' ) .')
					';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );

		$this->rs = $this->ExecuteSQL( $query, $ph );

		return $this;
	}

	/**
	 * @param $lf
	 * @return array|bool
	 */
	function getArrayByListFactory( $lf ) {
		if ( !is_object($lf) ) {
			return FALSE;
		}
		$list = array();
		foreach ($lf as $obj) {
			$list[] = $obj->getMapId();
		}

		if ( empty($list) == FALSE ) {
			return $list;
		}

		return FALSE;
	}

	/**
	 * @param string $company_id UUID
	 * @param int $object_type_id
	 * @param string $object_id UUID
	 * @return array|bool
	 */
	static function getArrayByCompanyIDAndObjectTypeIDAndObjectID( $company_id, $object_type_id, $object_id ) {
		$cgmlf = new CompanyGenericMapListFactory();
		return $cgmlf->getArrayByListFactory( $cgmlf->getByCompanyIDAndObjectTypeAndObjectID( $company_id, $object_type_id, $object_id ) );
	}
}
?>