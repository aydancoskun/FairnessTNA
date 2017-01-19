<?php
/*********************************************************************************
 * This file is part of "Fairness", a Payroll and Time Management program.
 * Fairness is Copyright 2013 Aydan Coskun (aydan.ayfer.coskun@gmail.com)
 * Portions of this software are Copyright (C) 2003 - 2013 TimeTrex Software Inc.
 * because Fairness is a fork of "TimeTrex Workforce Management" Software.
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
/*
 * $Revision: 2095 $
 * $Id: PolicyGroupAccrualPolicyListFactory.class.php 2095 2008-09-01 07:04:25Z ipso $
 * $Date: 2008-09-01 00:04:25 -0700 (Mon, 01 Sep 2008) $
 */

/**
 * @package Modules\Policy
 */
class CompanyGenericMapListFactory extends CompanyGenericMapFactory implements IteratorAggregate {

	function getAll($limit = NULL, $page = NULL, $where = NULL, $order = NULL) {
		$query = '
					select 	*
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
					'id' => $id,
					);

		$query = '
					select 	*
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
					'id' => $id,
					);

		$query = '
					select 	*
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

		$ph = array( 'company_id' => $company_id);

		$query = '
					select 	a.*
					from	'. $this->getTable() .' as a
					where	a.company_id = ?
						AND a.object_type_id in ('. $this->getListSQL($id, $ph) .')
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

		$ph = array( 'company_id' => $company_id);

		$query = '
					select 	a.*
					from	'. $this->getTable() .' as a
					where	a.company_id = ?
						AND a.object_type_id in ('. $this->getListSQL($object_type_id, $ph) .')
						AND a.object_id in ('. $this->getListSQL($id, $ph) .')
					';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );

		$this->ExecuteSQL( $query, $ph );

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

		$ph = array( 'company_id' => $company_id);

		$query = '
					select 	a.*
					from	'. $this->getTable() .' as a
					where	a.company_id = ?
						AND a.object_type_id in ('. $this->getListSQL($object_type_id, $ph) .')
						AND a.map_id in ('. $this->getListSQL($id, $ph) .')
					';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );
		$this->ExecuteSQL( $query, $ph );

		return $this;
	}

	function getByCompanyIDAndObjectTypeAndObjectIDAndMapID($company_id, $object_type_id, $id, $map_id,  $where = NULL, $order = NULL) {
		if ( $company_id == '') {
			return FALSE;
		}

		if ( $object_type_id == '') {
			return FALSE;
		}

		if ( $id == '') {
			return FALSE;
		}

		$ph = array( 'company_id' => $company_id);

		$query = '
					select 	a.*
					from	'. $this->getTable() .' as a
					where	a.company_id = ?
						AND a.object_type_id in ('. $this->getListSQL($object_type_id, $ph) .')
						AND a.object_id in ('. $this->getListSQL($id, $ph) .')
						AND a.map_id in ('. $this->getListSQL($map_id, $ph) .')
					';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );

		$this->ExecuteSQL( $query, $ph );

		return $this;
	}

	function getByCompanyIDAndObjectTypeAndObjectIDAndNotMapID($company_id, $object_type_id, $id, $map_id,  $where = NULL, $order = NULL) {
		if ( $company_id == '') {
			return FALSE;
		}

		if ( $object_type_id == '') {
			return FALSE;
		}

		if ( $id == '') {
			return FALSE;
		}

		$ph = array( 'company_id' => $company_id);

		$query = '
					select 	a.*
					from	'. $this->getTable() .' as a
					where	a.company_id = ?
						AND a.object_type_id in ('. $this->getListSQL($object_type_id, $ph) .')
						AND a.object_id in ('. $this->getListSQL($id, $ph) .')
						AND a.map_id not in ('. $this->getListSQL($map_id, $ph) .')
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
					select 	a.*
					from	'. $this->getTable() .' as a
					where	a.object_type_id in ('. $this->getListSQL($id, $ph) .')
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
					select 	a.*
					from	'. $this->getTable() .' as a
					where	a.object_type_id in ('.  $this->getListSQL($object_type_id, $ph) .')
						AND a.object_id in ('.  $this->getListSQL($id, $ph) .')
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

		foreach ($lf as $obj) {
			$list[] = $obj->getMapId();
		}

		if ( isset($list) ) {
			return $list;
		}

		return FALSE;
	}
	
	static function getArrayByCompanyIDAndObjectTypeIDAndObjectID( $company_id, $object_type_id, $object_id ) {
		$cgmlf = new CompanyGenericMapListFactory();
		$lf = $cgmlf->getByCompanyIDAndObjectTypeAndObjectID( $company_id, $object_type_id, $object_id );
		return $cgmlf->getArrayByListFactory( $lf );
	}
}
?>