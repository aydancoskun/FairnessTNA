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
 * @package Modules\Policy
 */
class CompanyGenericMapListFactory extends CompanyGenericMapFactory implements IteratorAggregate {

	function getAll($limit = NULL, $page = NULL, $where = NULL, $order = NULL) {
		$query = '
					select	*
					from	'. $this->getTable();
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );

		$this->ExecuteSQL( $query, NULL, $limit, $page );

		return $this;
	}

	function getById($id, $where = NULL, $order = NULL) {
		if ( $id == '') {
			return FALSE;
		}

		$ph = array(
					'id' => (int)$id,
					);


		$query = '
					select	*
					from	'. $this->getTable() .'
					where	id = ?
					';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );

		$this->ExecuteSQL( $query, $ph );

		return $this;
	}

	function getByCompanyId($id, $where = NULL, $order = NULL) {
		if ( $id == '') {
			return FALSE;
		}

		$ph = array(
					'id' => (int)$id,
					);


		$query = '
					select	*
					from	'. $this->getTable() .'
					where	company_id = ?
					';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );

		$this->ExecuteSQL( $query, $ph );

		return $this;
	}

	function getByCompanyIDAndObjectType($company_id, $id, $where = NULL, $order = NULL) {
		if ( $company_id == '') {
			return FALSE;
		}

		if ( $id == '') {
			return FALSE;
		}

		$ph = array(
						'company_id' => (int)$company_id
					);

		$query = '
					select	a.*
					from	'. $this->getTable() .' as a
					where	a.company_id = ?
						AND a.object_type_id in ('. $this->getListSQL( $id, $ph, 'int' ) .')
					';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );

		$this->ExecuteSQL( $query, $ph );

		return $this;
	}

	function getByCompanyIDAndObjectTypeAndObjectID($company_id, $object_type_id, $id, $where = NULL, $order = NULL) {
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
							'company_id' => (int)$company_id
						);


			$query = '
						select	a.*
						from	'. $this->getTable() .' as a
						where	a.company_id = ?
							AND a.object_type_id in ('. $this->getListSQL( $object_type_id, $ph, 'int' ) .')
							AND a.object_id in ('. $this->getListSQL( $id, $ph, 'int' ) .')
						';
			$query .= $this->getWhereSQL( $where );
			$query .= $this->getSortSQL( $order );

			$this->ExecuteSQL( $query, $ph );

			$this->saveCache($this->rs, $cache_id );
		}

		return $this;
	}

	function getByCompanyIDAndObjectTypeAndMapID($company_id, $object_type_id, $id, $where = NULL, $order = NULL) {
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
						'company_id' => (int)$company_id
					);

		$query = '
					select	a.*
					from	'. $this->getTable() .' as a
					where	a.company_id = ?
						AND a.object_type_id in ('. $this->getListSQL( $object_type_id, $ph, 'int' ) .')
						AND a.map_id in ('. $this->getListSQL( $id, $ph, 'int' ) .')
					';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );
		$this->ExecuteSQL( $query, $ph );

		return $this;
	}

	function getByCompanyIDAndObjectTypeAndObjectIDAndMapID($company_id, $object_type_id, $id, $map_id, $where = NULL, $order = NULL) {
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
						'company_id' => (int)$company_id
					);

		$query = '
					select	a.*
					from	'. $this->getTable() .' as a
					where	a.company_id = ?
						AND a.object_type_id in ('. $this->getListSQL( $object_type_id, $ph, 'int' ) .')
						AND a.object_id in ('. $this->getListSQL( $id, $ph, 'int' ) .')
						AND a.map_id in ('. $this->getListSQL( $map_id, $ph, 'int' ) .')
					';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );

		$this->ExecuteSQL( $query, $ph );

		return $this;
	}

	function getByCompanyIDAndObjectTypeAndObjectIDAndNotMapID($company_id, $object_type_id, $id, $map_id, $where = NULL, $order = NULL) {
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
						'company_id' => (int)$company_id
					);

		$query = '
					select	a.*
					from	'. $this->getTable() .' as a
					where	a.company_id = ?
						AND a.object_type_id in ('. $this->getListSQL( $object_type_id, $ph, 'int' ) .')
						AND a.object_id in ('. $this->getListSQL( $id, $ph, 'int' ) .')
						AND a.map_id not in ('. $this->getListSQL( $map_id, $ph, 'int' ) .')
					';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );

		$this->ExecuteSQL( $query, $ph );

		return $this;
	}

	function getByObjectType($id, $where = NULL, $order = NULL) {
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

		$this->ExecuteSQL( $query, $ph );

		return $this;
	}

	function getByObjectTypeAndObjectID($object_type_id, $id, $where = NULL, $order = NULL) {
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
						AND a.object_id in ('.	$this->getListSQL( $id, $ph, 'int' ) .')
					';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );

		$this->ExecuteSQL( $query, $ph );

		return $this;
	}

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
	
	static function getArrayByCompanyIDAndObjectTypeIDAndObjectID( $company_id, $object_type_id, $object_id ) {
		$cgmlf = new CompanyGenericMapListFactory();
		return $cgmlf->getArrayByListFactory( $cgmlf->getByCompanyIDAndObjectTypeAndObjectID( $company_id, $object_type_id, $object_id ) );
	}
}
?>