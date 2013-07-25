<?php
/*********************************************************************************
 * This file is part of "Fairness", a Payroll and Time Management program.
 * Fairness is Copyright 2013 Aydan Coscun (aydan.ayfer.coskun@gmail.com)
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
 * $Id: PolicyGroupUserFactory.class.php 2095 2008-09-01 07:04:25Z ipso $
 * $Date: 2008-09-01 00:04:25 -0700 (Mon, 01 Sep 2008) $
 */

/**
 * @package Modules\Hierarchy
 */
class HierarchyUserFactory extends Factory {
	protected $table = 'hierarchy_user';
	protected $pk_sequence_name = 'hierarchy_user_id_seq'; //PK Sequence name

	var $hierarchy_control_obj = NULL;
	var $user_obj = NULL;

	function getHierarchyControlObject() {
		if ( is_object($this->hierarchy_control_obj) ) {
			return $this->hierarchy_control_obj;
		} else {
			$hclf = TTnew( 'HierarchyControlListFactory' );
			$this->hierarchy_control_obj = $hclf->getById( $this->getHierarchyControl() )->getCurrent();

			return $this->hierarchy_control_obj;
		}
	}

	function getUserObject() {
		if ( is_object($this->user_obj) ) {
			return $this->user_obj;
		} else {
			$ulf = TTnew( 'UserListFactory' );
			$ulf->getById( $this->getUser() );
			if ( $ulf->getRecordCount() == 1 ) {
				$this->user_obj = $ulf->getCurrent();
				return $this->user_obj;
			}

			return FALSE;
		}
	}

	function getHierarchyControl() {
		if ( isset($this->data['hierarchy_control_id']) ) {
			return $this->data['hierarchy_control_id'];
		}

		return FALSE;
	}
	function setHierarchyControl($id) {
		$id = trim($id);

		$hclf = TTnew( 'HierarchyControlListFactory' );

		//This is a sub-class, need to support setting HierachyControlID before its created.
		if ( $id != 0
				OR $this->Validator->isResultSetWithRows(	'hierarchy_control_id',
														$hclf->getByID($id),
														TTi18n::gettext('Invalid Hierarchy Control')
														)
			) {
			$this->data['hierarchy_control_id'] = $id;

			return TRUE;
		}

		return FALSE;
	}

	function isUniqueUser($id, $exclude_id = 0 ) {
		$hcf = TTnew( 'HierarchyControlFactory' );
		$hotf = TTnew( 'HierarchyObjectTypeFactory' );

		$ph = array(
					'hierarchy_control_id' => $this->getHierarchyControl(),
					'id' => $id,
					'exclude_id' => $exclude_id,
					);

		//$query = 'select a.id from '. $this->getTable() .' as a, '. $pglf->getTable() .' as b where a.hierarchy_control_id = b.id AND a.user_id = ? AND b.deleted=0';
		$query = '
					select *
					from '. $hotf->getTable() .' as a
					LEFT JOIN '. $this->getTable() .' as b ON a.hierarchy_control_id = b.hierarchy_control_id
					LEFT JOIN '. $hcf->getTable() .' as c ON a.hierarchy_control_id = c.id
					WHERE a.object_type_id in (
							select object_type_id
							from hierarchy_object_type
							where hierarchy_control_id = ? )
					AND b.user_id = ?
					AND a.hierarchy_control_id != ?
					AND c.deleted = 0
				';
		//Debug::Arr($ph,'Query: '. $query, __FILE__, __LINE__, __METHOD__,10);
		$user_id = $this->db->GetOne($query, $ph);

		if ( $user_id === FALSE ) {
			return TRUE;
		}

		return FALSE;
	}
	function getUser() {
		if ( isset($this->data['user_id']) ) {
			return $this->data['user_id'];
		}
	}
	function setUser($id) {
		$id = trim($id);

		$ulf = TTnew( 'UserListFactory' );
		$hllf = TTnew( 'HierarchyLevelListFactory' );

		if ( $id != 0
				AND $this->Validator->isResultSetWithRows(	'user',
															$ulf->getByID($id),
															TTi18n::gettext('Selected Employee is invalid')
															)
				AND
				$this->Validator->isNotResultSetWithRows(	'user',
															$hllf->getByHierarchyControlIdAndUserId( $this->getHierarchyControl(), $id ),
															TTi18n::gettext('Selected employee is assigned as both a superior and subordinate')
															)
				AND	$this->Validator->isTrue(		'user',
													$this->isUniqueUser($id),
													TTi18n::gettext('Selected Employee is already assigned to another hierarchy')
													)
			) {

			$this->data['user_id'] = $id;

			return TRUE;
		}

		return FALSE;
	}

	//This table doesn't have any of these columns, so overload the functions.
	function getDeleted() {
		return FALSE;
	}
	function setDeleted($bool) {
		return FALSE;
	}

	function getCreatedDate() {
		return FALSE;
	}
	function setCreatedDate($epoch = NULL) {
		return FALSE;
	}
	function getCreatedBy() {
		return FALSE;
	}
	function setCreatedBy($id = NULL) {
		return FALSE;
	}

	function getUpdatedDate() {
		return FALSE;
	}
	function setUpdatedDate($epoch = NULL) {
		return FALSE;
	}
	function getUpdatedBy() {
		return FALSE;
	}
	function setUpdatedBy($id = NULL) {
		return FALSE;
	}


	function getDeletedDate() {
		return FALSE;
	}
	function setDeletedDate($epoch = NULL) {
		return FALSE;
	}
	function getDeletedBy() {
		return FALSE;
	}
	function setDeletedBy($id = NULL) {
		return FALSE;
	}

	function addLog( $log_action ) {
		$u_obj = $this->getUserObject();
		if ( is_object($u_obj) ) {
			return TTLog::addEntry( $this->getHierarchyControl(), $log_action, TTi18n::getText('Suborindate').': '. $u_obj->getFullName( FALSE, TRUE ) , NULL, $this->getTable() );
		}

		return FALSE;
	}
}
?>
