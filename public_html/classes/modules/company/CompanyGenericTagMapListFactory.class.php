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
class CompanyGenericTagMapListFactory extends CompanyGenericTagMapFactory implements IteratorAggregate {

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



		$additional_order_fields = array( 'cgtf.name' );

		if ( $order == NULL ) {
			$order = array( 'cgtf.name' => 'asc');
			$strict = FALSE;
		} else {
			$strict = TRUE;
		}

		$cgtf = new CompanyGenericTagFactory();

		$ph = array(
						'company_id' => (int)$company_id
					);

		//This should be a list of just distinct
		$query = '
					select
							a.*,
							cgtf.name as name
					from	'. $this->getTable() .' as a
					LEFT JOIN '. $cgtf->getTable() .' as cgtf ON ( a.object_type_id = cgtf.object_type_id AND a.tag_id = cgtf.id AND cgtf.company_id = ?)
					where
						a.object_type_id in ('. $this->getListSQL( $object_type_id, $ph, 'int' ) .')
						AND a.object_id in ('. $this->getListSQL( $id, $ph, 'int' ) .')
					';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order, $strict, $additional_order_fields );

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
			$list[] = $obj->getColumn('name');
		}

		if ( empty($list) == FALSE ) {
			return $list;
		}

		return FALSE;
	}

	static function getArrayByCompanyIDAndObjectTypeIDAndObjectID( $company_id, $object_type_id, $object_id ) {
		$cgtmlf = new CompanyGenericTagMapListFactory();

		$lf = $cgtmlf->getByCompanyIDAndObjectTypeAndObjectID( $company_id, $object_type_id, $object_id );
		return $cgtmlf->getArrayByListFactory( $lf );
	}

	static function getStringByCompanyIDAndObjectTypeIDAndObjectID( $company_id, $object_type_id, $object_id ) {
		$cgtmlf = new CompanyGenericTagMapListFactory();

		$lf = $cgtmlf->getByCompanyIDAndObjectTypeAndObjectID( $company_id, $object_type_id, $object_id );
		return implode(',', (array)$cgtmlf->getArrayByListFactory( $lf ) );
	}

}
?>
