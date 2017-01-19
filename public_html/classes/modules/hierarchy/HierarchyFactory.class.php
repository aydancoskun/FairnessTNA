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
 * @package Modules\Hierarchy
 */
class HierarchyFactory extends Factory {

	protected $table = 'hierarchy'; //Used for caching purposes only.

	protected $fasttree_obj = NULL;
	//protected $tmp_data = array(); //Tmp data.
	function getFastTreeObject() {

		if ( is_object($this->fasttree_obj) ) {
			return $this->fasttree_obj;
		} else {
			global $fast_tree_options;
			$this->fasttree_obj = new FastTree($fast_tree_options);

			return $this->fasttree_obj;
		}
	}

	function getId() {
		if ( isset($this->data['id']) ) {
			return $this->data['id'];
		}

		return FALSE;
	}
	function setId($id) {

		$this->data['id'] = $id;

		return TRUE;
	}

	function getHierarchyControl() {
		if ( isset($this->data['hierarchy_control_id']) ) {
			return (int)$this->data['hierarchy_control_id'];
		}

		return FALSE;
	}
	function setHierarchyControl($id) {

		$this->data['hierarchy_control_id'] = $id;

		return TRUE;
	}

	//Use this for completly editing a row in the tree
	//Basically "old_id".
	function getPreviousUser() {
		if ( isset($this->data['previous_user_id']) ) {
			return (int)$this->data['previous_user_id'];
		}

		return FALSE;
	}
	function setPreviousUser($id) {

		$this->data['previous_user_id'] = $id;

		return TRUE;
	}

	function getParent() {
		if ( isset($this->data['parent_user_id']) ) {
			return (int)$this->data['parent_user_id'];
		}

		return FALSE;
	}
	function setParent($id) {

		$this->data['parent_user_id'] = $id;

		return TRUE;
	}

	function getUser() {
		if ( isset($this->data['user_id']) ) {
			return (int)$this->data['user_id'];
		}

		return FALSE;
	}
	function setUser($id) {

		$this->data['user_id'] = $id;

		return TRUE;
	}

	function getShared() {
		if ( isset( $this->data['shared'] ) ) {
			return $this->fromBool( $this->data['shared'] );
		}

		return FALSE;
	}
	function setShared($bool) {
		$this->data['shared'] = $this->toBool($bool);

		return TRUE;
	}


	function Validate( $ignore_warning = TRUE ) {

		if ( $this->getUser() == $this->getParent() ) {
				$this->Validator->isTrue(	'parent',
											FALSE,
											TTi18n::gettext('User is the same as parent')
											);
		}

		//Make sure both user and parent belong to the same company
		$ulf = TTnew( 'UserListFactory' );
		$ulf->getById( $this->getUser() );
		$user = $ulf->getIterator()->current();
		unset($ulf);

		$ulf = TTnew( 'UserListFactory' );
		$ulf->getById( $this->getParent() );
		$parent = $ulf->getIterator()->current();
		unset($ulf);


		if ( $this->getUser() == 0 AND $this->getParent() == 0 ) {
			$parent_company_id = 0;
			$user_company_id = 0;
		} elseif ( $this->getUser() == 0 ) {
			$parent_company_id = $parent->getCompany();
			$user_company_id = $parent->getCompany();
		} elseif ( $this->getParent() == 0 ) {
			$parent_company_id = $user->getCompany();
			$user_company_id = $user->getCompany();
		} else {
			$parent_company_id = $parent->getCompany();
			$user_company_id = $user->getCompany();
		}

		if ( $user_company_id > 0 AND $parent_company_id > 0 ) {

			Debug::Text(' User Company: '. $user_company_id .' Parent Company: '. $parent_company_id, __FILE__, __LINE__, __METHOD__, 10);
			if ( $user_company_id != $parent_company_id ) {
					$this->Validator->isTrue(	'parent',
												FALSE,
												TTi18n::gettext('User or parent has incorrect company')
												);
			}

			$this->getFastTreeObject()->setTree( $this->getHierarchyControl() );
			$children_arr = $this->getFastTreeObject()->getAllChildren( $this->getUser(), 'RECURSE' );
			if ( is_array($children_arr) ) {
				$children_ids = array_keys( $children_arr );

				if ( isset($children_ids) AND is_array($children_ids) AND in_array( $this->getParent(), $children_ids) == TRUE ) {
					Debug::Text(' Objects cant be re-parented to their own children...', __FILE__, __LINE__, __METHOD__, 10);
					$this->Validator->isTrue(	'parent',
												FALSE,
												TTi18n::gettext('Unable to change parent to a child of itself')
												);
				}
			}
		}

		return TRUE;
	}

	function Save( $reset_data = TRUE, $force_lookup = FALSE ) {
		$this->StartTransaction();

		$this->getFastTreeObject()->setTree( $this->getHierarchyControl() );

		$retval = TRUE;
		if ( $this->getId() === FALSE ) {
			Debug::Text(' Adding Node ', __FILE__, __LINE__, __METHOD__, 10);
			$log_action = 10;

			//Add node to tree
			if ( $this->getFastTreeObject()->add( $this->getUser(), $this->getParent() ) === FALSE ) {
				Debug::Text(' Failed adding Node ', __FILE__, __LINE__, __METHOD__, 10);

				$this->Validator->isTrue(	'user',
											FALSE,
											TTi18n::gettext('Employee is already assigned to this hierarchy')
											);
				$retval = FALSE;
			}
		} else {
			Debug::Text(' Editing Node ', __FILE__, __LINE__, __METHOD__, 10);
			$log_action = 20;

			//Edit node.
			if ( $this->getFastTreeObject()->edit( $this->getPreviousUser(), $this->getUser() ) === TRUE ) {
				$retval = $this->getFastTreeObject()->move( $this->getUser(), $this->getParent() );
			} else {
				Debug::Text(' Failed editing Node ', __FILE__, __LINE__, __METHOD__, 10);

				//$retval = FALSE;
				$retval = TRUE;
			}
		}

		TTLog::addEntry( $this->getUser(), $log_action, TTi18n::getText('Hierarchy Tree - Control ID').': '.$this->getHierarchyControl(), NULL, $this->getTable() );

		$this->CommitTransaction();
		//$this->FailTransaction();

		$cache_id = $this->getHierarchyControl().$this->getParent();
		$this->removeCache( $cache_id );

		return $retval;
	}

	function Delete() {
		if ( $this->getUser() !== FALSE ) {
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

}
?>
