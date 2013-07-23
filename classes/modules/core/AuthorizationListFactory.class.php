<?php
/*********************************************************************************
 * TimeTrex is a Payroll and Time Management program developed by
 * TimeTrex Software Inc. Copyright (C) 2003 - 2013 TimeTrex Software Inc.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License version 3 as published by
 * the Free Software Foundation with the addition of the following permission
 * added to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED
 * WORK IN WHICH THE COPYRIGHT IS OWNED BY TIMETREX, TIMETREX DISCLAIMS THE
 * WARRANTY OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE.  See the GNU Affero General Public License for more
 * details.
 *
 * You should have received a copy of the GNU Affero General Public License along
 * with this program; if not, see http://www.gnu.org/licenses or write to the Free
 * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301 USA.
 *
 * You can contact TimeTrex headquarters at Unit 22 - 2475 Dobbin Rd. Suite
 * #292 Westbank, BC V4T 2E9, Canada or at email address info@timetrex.com.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License
 * version 3, these Appropriate Legal Notices must retain the display of the
 * "Powered by TimeTrex" logo. If the display of the logo is not reasonably
 * feasible for technical reasons, the Appropriate Legal Notices must display
 * the words "Powered by TimeTrex".
 ********************************************************************************/
/*
 * $Revision: 9521 $
 * $Id: AuthorizationListFactory.class.php 9521 2013-04-08 23:09:52Z ipso $
 * $Date: 2013-04-08 16:09:52 -0700 (Mon, 08 Apr 2013) $
 */

/**
 * @package Core
 */
class AuthorizationListFactory extends AuthorizationFactory implements IteratorAggregate {

	function getAll($limit = NULL, $page = NULL, $where = NULL, $order = NULL) {
		$query = '
					select 	*
					from	'. $this->getTable() .'
						WHERE deleted = 0';
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
					select 	*
					from	'. $this->table .'
					where	id = ?
						AND deleted = 0';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );

		$this->ExecuteSQL( $query, $ph );

		return $this;
	}

	function getByIdAndCompanyId($id, $company_id, $limit = NULL, $page = NULL, $where = NULL, $order = NULL) {
		if ( $id == '') {
			return FALSE;
		}

		if ( $company_id == '') {
			return FALSE;
		}

		if ( $order == NULL ) {
			$order = array( 'created_date' => 'asc' );
			$strict = FALSE;
		} else {
			$strict = TRUE;
		}

		$uf = new UserFactory();

		$ph = array(
					'company_id' => $company_id,
					);

		$query = '
					select 	a.*
					from 	'. $this->getTable() .' as a
					LEFT JOIN '. $uf->getTable() .' as uf ON (a.created_by = uf.id )
					where	uf.company_id = ?
						AND	a.id in ('. $this->getListSQL($id, $ph) .')
						AND ( a.deleted = 0 AND uf.deleted = 0 )';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order, $strict );

		$this->ExecuteSQL( $query, $ph, $limit, $page );

		$this->ExecuteSQL( $query, $ph );

		return $this;
	}

	function getByObjectTypeAndObjectId($object_type_id, $object_id, $where = NULL, $order = NULL) {
		if ( $object_type_id == '') {
			return FALSE;
		}

		if ( $object_id == '') {
			return FALSE;
		}

		$ph = array(
					'object_type_id' => $object_type_id,
					'object_id' => $object_id,
					);

		/*
		$key = Option::getByValue($object_type, $this->getOptions('object_type') );
		if ($key !== FALSE) {
			$object_type_id = $key;
		}
		*/
		$query = '
					select 	*
					from	'. $this->table .'
					where	object_type_id = ?
						AND object_id = ?
						AND deleted = 0';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );

		$this->ExecuteSQL( $query, $ph );

		return $this;
	}

	function getByObjectTypeAndObjectIdAndCreatedBy($object_type_id, $object_id, $created_by, $where = NULL, $order = NULL) {
		if ( $object_type_id == '') {
			return FALSE;
		}

		if ( $object_id == '') {
			return FALSE;
		}

		if ( $created_by == '') {
			return FALSE;
		}

		$ph = array(
					'object_type_id' => $object_type_id,
					'object_id' => $object_id,
					'created_by' => $created_by,
					);

		/*
		$key = Option::getByValue($object_type, $this->getOptions('object_type') );
		if ($key !== FALSE) {
			$object_type_id = $key;
		}
		*/
		$query = '
					select 	*
					from	'. $this->table .'
					where	object_type_id = ?
						AND object_id = ?
						AND created_by = ?
						AND deleted = 0';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );

		$this->ExecuteSQL( $query, $ph );

		return $this;
	}

	function getAPISearchByCompanyIdAndArrayCriteria( $company_id, $filter_data, $limit = NULL, $page = NULL, $where = NULL, $order = NULL ) {
		if ( $company_id == '') {
			return FALSE;
		}

		if ( !is_array($order) ) {
			//Use Filter Data ordering if its set.
			if ( isset($filter_data['sort_column']) AND $filter_data['sort_order']) {
				$order = array(Misc::trimSortPrefix($filter_data['sort_column']) => $filter_data['sort_order']);
			}
		}

		$additional_order_fields = array();

		$sort_column_aliases = array();

		$order = $this->getColumnsFromAliases( $order, $sort_column_aliases );
		if ( $order == NULL ) {
			$order = array( 'created_date' => 'desc');
			$strict = FALSE;
		} else {
			//Always try to order by status first so INACTIVE employees go to the bottom.
			if ( !isset($order['created_date']) ) {
				$order = Misc::prependArray( array('created_date' => 'desc'), $order );
			}
			$strict = TRUE;
		}
		//Debug::Arr($order,'Order Data:', __FILE__, __LINE__, __METHOD__,10);
		//Debug::Arr($filter_data,'Filter Data:', __FILE__, __LINE__, __METHOD__,10);

		$uf = new UserFactory();
		$rf = new RequestFactory();
		$udf = new UserDateFactory();
		$pptsvf = new PayPeriodTimeSheetVerifyListFactory();
        $uef = new UserExpenseFactory();

		$ph = array(
					'company_id' => $company_id,
					);

		$query = '
					select 	a.*,
							CASE WHEN a.object_type_id = 90 THEN pptsvf.user_id ELSE ud.user_id END as user_id,
							y.first_name as created_by_first_name,
							y.middle_name as created_by_middle_name,
							y.last_name as created_by_last_name,
							z.first_name as updated_by_first_name,
							z.middle_name as updated_by_middle_name,
							z.last_name as updated_by_last_name
					from 	'. $this->getTable() .' as a
						LEFT JOIN '. $rf->getTable() .' as rf ON ( a.object_type_id in (1010,1020,1030,1040,1100) AND a.object_id = rf.id )
						LEFT JOIN '. $udf->getTable() .' as ud ON ( rf.user_date_id = ud.id )
						LEFT JOIN '. $pptsvf->getTable() .' as pptsvf ON ( a.object_type_id = 90 AND a.object_id = pptsvf.id )
                        LEFT JOIN '. $uef->getTable() .' as uef ON ( a.object_type_id = 200 AND a.object_id = uef.id )
						LEFT JOIN '. $uf->getTable() .' as y ON ( a.created_by = y.id AND y.deleted = 0 )
						LEFT JOIN '. $uf->getTable() .' as z ON ( a.updated_by = z.id AND z.deleted = 0 )
					where	y.company_id = ?';
		$user_id_column = 'a.created_by';
		if ( isset($filter_data['object_type_id']) AND in_array( $filter_data['object_type_id'], array(1010,1020,1030,1040,1100) ) ) { //Requests
			$user_id_column = 'ud.user_id';
		} elseif ( isset($filter_data['object_type_id']) AND in_array( $filter_data['object_type_id'], array(90) ) ) { //TimeSheet
			$user_id_column = 'pptsvf.user_id';
		} elseif ( isset($filter_data['object_type_id']) AND in_array( $filter_data['object_type_id'], array(200) ) ) { //Expense
		    $user_id_column = 'uef.user_id';
		}
		if ( isset($filter_data['permission_children_ids']) AND isset($filter_data['permission_children_ids'][0]) AND !in_array(-1, (array)$filter_data['permission_children_ids']) ) {
			$query  .=	' AND '. $user_id_column .' in ('. $this->getListSQL($filter_data['permission_children_ids'], $ph) .') ';
		}

		if ( isset($filter_data['id']) AND isset($filter_data['id'][0]) AND !in_array(-1, (array)$filter_data['id']) ) {
			$query  .=	' AND a.id in ('. $this->getListSQL($filter_data['id'], $ph) .') ';
		}
		if ( isset($filter_data['exclude_id']) AND isset($filter_data['exclude_id'][0]) AND !in_array(-1, (array)$filter_data['exclude_id']) ) {
			$query  .=	' AND a.id not in ('. $this->getListSQL($filter_data['exclude_id'], $ph) .') ';
		}
		if ( isset($filter_data['object_type_id']) AND isset($filter_data['object_type_id'][0]) AND !in_array(-1, (array)$filter_data['object_type_id']) ) {
			$query  .=	' AND a.object_type_id in ('. $this->getListSQL($filter_data['object_type_id'], $ph) .') ';
		}
		if ( isset($filter_data['object_id']) AND isset($filter_data['object_id'][0]) AND !in_array(-1, (array)$filter_data['object_id']) ) {
			$query  .=	' AND a.object_id in ('. $this->getListSQL($filter_data['object_id'], $ph) .') ';
		}
		$query .= ( isset($filter_data['created_by']) ) ? $this->getWhereClauseSQL( array('a.created_by','y.first_name','y.last_name'), $filter_data['created_by'], 'user_id_or_name', $ph ) : NULL;
        
        $query .= ( isset($filter_data['updated_by']) ) ? $this->getWhereClauseSQL( array('a.updated_by','z.first_name','z.last_name'), $filter_data['updated_by'], 'user_id_or_name', $ph ) : NULL;
        

		$query .= 	'
						AND a.deleted = 0
					';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order, $strict, $additional_order_fields );

		$this->ExecuteSQL( $query, $ph, $limit, $page );

		return $this;
	}

}
?>
