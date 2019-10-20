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
class HierarchyFactory extends Factory {

	protected $table = 'hierarchy'; //Used for caching purposes only.

	protected $fasttree_obj = NULL;
	//protected $tmp_data = array(); //Tmp data.

	/**
	 * @return FastTree|null
	 */
	function getFastTreeObject() {

		if ( is_object($this->fasttree_obj) ) {
			return $this->fasttree_obj;
		} else {
			global $fast_tree_options;
			$this->fasttree_obj = new FastTree($fast_tree_options);

			return $this->fasttree_obj;
		}
	}

	/**
	 * @return bool|mixed
	 */
	function getId() {
		return $this->getGenericDataValue( 'id' );
	}

	/**
	 * @param string $value UUID
	 * @return bool
	 */
	function setId( $value) {
		$this->setGenericDataValue( 'id', TTUUID::castUUID( $value ) );

		return TRUE;
	}

	/**
	 * @return bool|mixed
	 */
	function getHierarchyControl() {
		return $this->getGenericDataValue( 'hierarchy_control_id' );
	}

	/**
	 * @param string $value UUID
	 * @return bool
	 */
	function setHierarchyControl( $value) {
		$this->setGenericDataValue( 'hierarchy_control_id', TTUUID::castUUID( $value ) );
		return TRUE;
	}

	/**
	 * Use this for completly editing a row in the tree
	 * Basically "old_id".
	 * @return bool|mixed
	 */
	function getPreviousUser() {
		return $this->getGenericDataValue( 'previous_user_id' );
	}

	/**
	 * @param string $value UUID
	 * @return bool
	 */
	function setPreviousUser( $value) {
		$this->setGenericDataValue( 'previous_user_id', TTUUID::castUUID( $value ) );
		return TRUE;
	}

	/**
	 * @return bool|mixed
	 */
	function getParent() {
		return $this->getGenericDataValue( 'parent_user_id' );
	}

	/**
	 * @param string $value UUID
	 * @return bool
	 */
	function setParent( $value) {
		$this->setGenericDataValue( 'parent_user_id', TTUUID::castUUID( $value ) );
		return TRUE;
	}

	/**
	 * @return bool|mixed
	 */
	function getUser() {
		return $this->getGenericDataValue( 'user_id' );
	}

	/**
	 * @param string $value UUID
	 * @return bool
	 */
	function setUser( $value) {
		$this->setGenericDataValue( 'user_id', TTUUID::castUUID( $value ) );
		return TRUE;
	}

	/**
	 * @return bool
	 */
	function getShared() {
		return $this->fromBool( $this->getGenericDataValue( 'shared' ) );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setShared( $value) {
		return $this->setGenericDataValue( 'shared', $this->toBool($value) );
	}


	/**
	 * @param bool $ignore_warning
	 * @return bool
	 */
	function Validate( $ignore_warning = TRUE ) {

		if ( $this->getUser() == $this->getParent() ) {
				$this->Validator->isTrue(	'parent',
											FALSE,
											TTi18n::gettext('User is the same as parent')
											);
		}

		//Make sure both user and parent belong to the same company
		$ulf = TTnew( 'UserListFactory' ); /** @var UserListFactory $ulf */
		$ulf->getById( $this->getUser() );
		$user = $ulf->getIterator()->current();
		unset($ulf);

		$ulf = TTnew( 'UserListFactory' ); /** @var UserListFactory $ulf */
		$ulf->getById( $this->getParent() );
		$parent = $ulf->getIterator()->current();
		unset($ulf);


		if ( $this->getUser() == TTUUID::getZeroID() AND $this->getParent() == TTUUID::getZeroID() ) {
			$parent_company_id = TTUUID::getZeroID();
			$user_company_id = TTUUID::getZeroID();
		} elseif ( $this->getUser() == TTUUID::getZeroID() ) {
			$parent_company_id = $parent->getCompany();
			$user_company_id = $parent->getCompany();
		} elseif ( $this->getParent() == TTUUID::getZeroID() ) {
			$parent_company_id = $user->getCompany();
			$user_company_id = $user->getCompany();
		} else {
			$parent_company_id = $parent->getCompany();
			$user_company_id = $user->getCompany();
		}

		if ( TTUUID::isUUID($user_company_id) AND $user_company_id != TTUUID::getZeroID() AND $user_company_id != TTUUID::getNotExistID() AND TTUUID::isUUID($parent_company_id) AND $parent_company_id != TTUUID::getZeroID() AND $parent_company_id != TTUUID::getNotExistID() ) {

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

	/**
	 * @param bool $reset_data
	 * @param bool $force_lookup
	 * @return bool
	 */
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

	/**
	 * @return bool
	 */
	function Delete( $disable_audit_log = FALSE ) {
		if ( $this->getUser() !== FALSE ) {
			return TRUE;
		}

		return FALSE;
	}

	//This table doesn't have any of these columns, so overload the functions.

	/**
	 * @return bool
	 */
	function getDeleted() {
		return FALSE;
	}

	/**
	 * @param $bool
	 * @return bool
	 */
	function setDeleted( $bool) {
		return FALSE;
	}

	/**
	 * @return bool
	 */
	function getCreatedDate() {
		return FALSE;
	}

	/**
	 * @param int $epoch EPOCH
	 * @return bool
	 */
	function setCreatedDate( $epoch = NULL) {
		return FALSE;
	}

	/**
	 * @return bool
	 */
	function getCreatedBy() {
		return FALSE;
	}

	/**
	 * @param string $id UUID
	 * @return bool
	 */
	function setCreatedBy( $id = NULL) {
		return FALSE;
	}

	/**
	 * @return bool
	 */
	function getUpdatedDate() {
		return FALSE;
	}

	/**
	 * @param int $epoch EPOCH
	 * @return bool
	 */
	function setUpdatedDate( $epoch = NULL) {
		return FALSE;
	}

	/**
	 * @return bool
	 */
	function getUpdatedBy() {
		return FALSE;
	}

	/**
	 * @param string $id UUID
	 * @return bool
	 */
	function setUpdatedBy( $id = NULL) {
		return FALSE;
	}


	/**
	 * @return bool
	 */
	function getDeletedDate() {
		return FALSE;
	}

	/**
	 * @param int $epoch EPOCH
	 * @return bool
	 */
	function setDeletedDate( $epoch = NULL) {
		return FALSE;
	}

	/**
	 * @return bool
	 */
	function getDeletedBy() {
		return FALSE;
	}

	/**
	 * @param string $id UUID
	 * @return bool
	 */
	function setDeletedBy( $id = NULL) {
		return FALSE;
	}

}
?>
