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
 * $Revision: 6446 $
 * $Id: PermissionListFactory.class.php 6446 2012-03-23 23:25:32Z ipso $
 * $Date: 2012-03-23 16:25:32 -0700 (Fri, 23 Mar 2012) $
 */

/**
 * @package Core
 */
class PermissionListFactory extends PermissionFactory implements IteratorAggregate {
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
					'id' => $id,
					);

		$query = '
					select 	*
					from	'. $this->getTable() .'
					where	id = ?
						AND deleted = 0';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );

		$this->ExecuteSQL( $query, $ph );

		return $this;
	}

	function getByCompanyIdAndPermissionControlId($company_id, $permission_control_id, $where = NULL, $order = NULL) {
		if ( $company_id == '') {
			return FALSE;
		}

		if ( $permission_control_id == '') {
			return FALSE;
		}

		$ph = array(
					'company_id' => $company_id,
					'permission_control_id' => $permission_control_id,
					);

		$pcf = new PermissionControlFactory();

		$query = '
					select 	a.*
					from	'. $this->getTable() .' as a,
							'. $pcf->getTable() .' as b
					where 	b.id = a.permission_control_id
						AND b.company_id = ?
						AND a.permission_control_id = ?
						AND ( a.deleted = 0 AND b.deleted = 0 )';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );

		$this->ExecuteSQL( $query, $ph );

		return $this;
	}

	function getByCompanyIdAndPermissionControlIdAndSectionAndName($company_id, $permission_control_id, $section, $name, $where = NULL, $order = NULL) {
		if ( $company_id == '') {
			return FALSE;
		}

		if ( $permission_control_id == '') {
			return FALSE;
		}

		if ( $section == '') {
			return FALSE;
		}

		if ( $name == '') {
			return FALSE;
		}

		$ph = array(
					'company_id' => $company_id,
					'permission_control_id' => $permission_control_id,
					'section' => $section,
					//'name' => $name, //Allow a list of names.
					);

		$pcf = new PermissionControlFactory();

		$query = '
					select 	a.*
					from	'. $this->getTable() .' as a,
							'. $pcf->getTable() .' as b
					where 	b.id = a.permission_control_id
						AND b.company_id = ?
						AND a.permission_control_id = ?
						AND a.section = ?
						AND a.name in ('. $this->getListSQL($name, $ph) .')
						AND ( a.deleted = 0 AND b.deleted = 0)';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );

		$this->ExecuteSQL( $query, $ph );

		return $this;
	}

	function getAllPermissionsByCompanyIdAndUserId($company_id, $user_id) {
		if ( $company_id == '') {
			return FALSE;
		}

		if ( $user_id == '') {
			return FALSE;
		}

		$ph = array(
					'company_id' => $company_id,
					'user_id' => $user_id,
					);

		$pcf = new PermissionControlFactory();
		$puf = new PermissionUserFactory();

		$query = '
					select  a.*,
							b.level as level
					from	'. $this->getTable() .' as a,
							'. $pcf->getTable() .' as b,
							'. $puf->getTable() .' as c
					where b.id = a.permission_control_id
						AND b.id = c.permission_control_id
						AND b.company_id = ?
						AND	c.user_id = ?
						AND ( a.deleted = 0 AND b.deleted = 0 )
				';

		$this->ExecuteSQL( $query, $ph );

		return $this;
	}

/*
	function getByUserIdAndSectionAndName($user_id,$section, $name, $where = NULL, $order = NULL) {
		if ( $user_id == '') {
			return FALSE;
		}

		if ( $section == '') {
			return FALSE;
		}

		if ( $name == '') {
			return FALSE;
		}

		$ph = array(
					'user_id' => $user_id,
					'section' => $section,
					'name' => $name,
					);

		$query = '
					select 	*
					from	'. $this->getTable() .'
					where	user_id = ?
						AND section = ?
						AND name = ?
						AND deleted = 0';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );

		$this->ExecuteSQL( $query, $ph );

		return $this;
	}

	function getByCompanyIdAndUserIdAndSectionAndName($company_id,$user_id,$section, $name, $where = NULL, $order = NULL) {
		if ( $company_id == '') {
			return FALSE;
		}

		if ( $user_id == '') {
			return FALSE;
		}

		if ( $section == '') {
			return FALSE;
		}

		if ( $name == '') {
			return FALSE;
		}

		$ph = array(
					'company_id' => $company_id,
					'user_id' => $user_id,
					'section' => $section,
					'name' => $name,
					);

		$query = '
					select 	*
					from	'. $this->getTable() .'
					where 	company_id = ?
						AND user_id = ?
						AND section = ?
						AND name = ?
						AND deleted = 0';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );

		$this->ExecuteSQL( $query, $ph );

		return $this;
	}

	function getByCompanyIdAndUserId($company_id,$user_id,$where = NULL, $order = NULL) {
		if ( $company_id == '') {
			return FALSE;
		}

		if ( $user_id == '') {
			return FALSE;
		}

		$ph = array(
					'company_id' => $company_id,
					'user_id' => $user_id,
					);

		$query = '
					select 	*
					from	'. $this->getTable() .'
					where 	company_id = ?
						AND user_id = ?
						AND deleted = 0';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );

		$this->ExecuteSQL( $query, $ph );

		return $this;
	}

	function getBySectionAndNameAndUserIdAndCompanyId($section, $name, $user_id, $company_id) {
		if ( $company_id == '') {
			return FALSE;
		}

		if ( $user_id == '') {
			return FALSE;
		}

		if ( $section == '') {
			return FALSE;
		}

		if ( $name == '') {
			return FALSE;
		}

		$ph = array(
					'company_id' => $company_id,
					'user_id' => $user_id,
					'section' => $section,
					'name' => $name,
					);

		$query = '
					select 	*
					from	'. $this->getTable() .'
					where	company_id = ?
						AND	user_id in (-1, ? )
						AND section = ?
						AND name = ?
						AND deleted = 0
					ORDER BY company_id DESC, user_id DESC
					LIMIT 1';

		Debug::Text('Query: '. $query , __FILE__, __LINE__, __METHOD__,9);

		$this->ExecuteSQL( $query, $ph );

		return $this;
	}
*/
}
?>
