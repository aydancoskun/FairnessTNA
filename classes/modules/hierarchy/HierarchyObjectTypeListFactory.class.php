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
 * @package Modules\Hierarchy
 */
class HierarchyObjectTypeListFactory extends HierarchyObjectTypeFactory implements IteratorAggregate {

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
				';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );

		$this->rs = $this->ExecuteSQL( $query, NULL, $limit, $page );

		return $this;
	}

	/**
	 * @param string $id UUID
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool|HierarchyObjectTypeListFactory
	 */
	function getById( $id, $where = NULL, $order = NULL) {
		if ( $id == '' ) {
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
	 * @return bool|HierarchyObjectTypeListFactory
	 */
	function getByHierarchyControlId( $id, $where = NULL, $order = NULL) {
		if ( $id == '' ) {
			return FALSE;
		}

		$ph = array(
					'id' => TTUUID::castUUID($id),
					);


		$query = '
					select	*
					from	'. $this->getTable() .'
					where	hierarchy_control_id = ?
				';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );

		$this->rs = $this->ExecuteSQL( $query, $ph );

		return $this;
	}

	/**
	 * @param string $id UUID
	 * @param int $object_type_id
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool|HierarchyObjectTypeListFactory
	 */
	function getByCompanyIdAndObjectTypeId( $id, $object_type_id, $where = NULL, $order = NULL) {
		if ( $id == '' ) {
			return FALSE;
		}

		if ( $object_type_id == '' ) {
			return FALSE;
		}

		$strict_order = TRUE;
		if ( $order == NULL ) {
			//$order = array('b.last_name' => 'asc');
			$strict_order = FALSE;
		}

		$cache_id = $id.$object_type_id;

		$hcf = new HierarchyControlFactory();
		$hotf = new HierarchyObjectTypeFactory();

		$this->rs = $this->getCache($cache_id);
		if ( $this->rs === FALSE ) {
			$ph = array(
						'id' => TTUUID::castUUID($id),
						'object_type_id' => (int)$object_type_id,
						);

			$query = '
						select	*
						from	'. $this->getTable() .' as a,
								'. $hcf->getTable() .' as b,
								'. $hotf->getTable() .' as c

						where	a.hierarchy_control_id = b.id
							AND a.hierarchy_control_id = c.hierarchy_control_id
							AND b.company_id = ?
							AND c.object_type_id = ?
							AND b.deleted = 0
					';
			$query .= $this->getWhereSQL( $where );
			$query .= $this->getSortSQL( $order, $strict_order );

			$this->rs = $this->ExecuteSQL( $query, $ph );

			$this->saveCache($this->rs, $cache_id);
		}

		return $this;
	}

	/**
	 * @param string $id UUID
	 * @param int $limit Limit the number of records returned
	 * @param int $page Page number of records to return for pagination
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool|HierarchyObjectTypeListFactory
	 */
	function getByCompanyId( $id, $limit = NULL, $page = NULL, $where = NULL, $order = NULL) {
		if ( $id == '' ) {
			return FALSE;
		}

		$strict_order = TRUE;
		if ( $order == NULL ) {
			//$order = array('b.last_name' => 'asc');
			$strict_order = FALSE;
		}

		$hcf = new HierarchyControlFactory();

		$ph = array(
					'id' => TTUUID::castUUID($id),
					);


		$query = '
					select	*
					from	'. $this->getTable() .' as a,
							'. $hcf->getTable() .' as b

					where	a.hierarchy_control_id = b.id
						AND b.company_id = ?
						AND b.deleted = 0
				';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order, $strict_order );

		$this->rs = $this->ExecuteSQL( $query, $ph, $limit, $page );

		return $this;
	}


	/**
	 * @param string $id UUID
	 * @return array
	 */
	function getByCompanyIdArray( $id) {

		$hotlf = new HierarchyObjectTypeListFactory();
		$hotlf->getByCompanyId( $id ) ;

		$object_types = array();
		foreach ($hotlf as $object_type) {
			$object_types[] = $object_type->getObjectType();
		}

		return $object_types;
	}
}
?>
