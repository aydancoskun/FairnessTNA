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
 * $Revision: 8371 $
 * $Id: PremiumPolicyBranchFactory.class.php 8371 2012-11-22 21:18:57Z ipso $
 * $Date: 2012-11-22 13:18:57 -0800 (Thu, 22 Nov 2012) $
 */

/**
 * @package Modules\Policy
 */
class PremiumPolicyBranchFactory extends Factory {
	protected $table = 'premium_policy_branch';
	protected $pk_sequence_name = 'premium_policy_branch_id_seq'; //PK Sequence name

	protected $branch_obj = NULL;

	function getBranchObject() {
		if ( is_object($this->branch_obj) ) {
			return $this->branch_obj;
		} else {
			$lf = TTnew( 'BranchListFactory' );
			$lf->getById( $this->getBranch() );
			if ( $lf->getRecordCount() == 1 ) {
				$this->branch_obj = $lf->getCurrent();
				return $this->branch_obj;
			}

			return FALSE;
		}
	}

	function getPremiumPolicy() {
		if ( isset($this->data['premium_policy_id']) ) {
			return $this->data['premium_policy_id'];
		}
	}
	function setPremiumPolicy($id) {
		$id = trim($id);

		$pplf = TTnew( 'PremiumPolicyListFactory' );

		if (	$id == 0
				OR
				$this->Validator->isNumeric(	'premium_policy',
													$id,
													TTi18n::gettext('Selected Premium Policy is invalid')
/*
				$this->Validator->isResultSetWithRows(	'premium_policy',
													$pplf->getByID($id),
													TTi18n::gettext('Selected Premium Policy is invalid')
*/
															)
			) {

			$this->data['premium_policy_id'] = $id;

			return TRUE;
		}

		return FALSE;
	}

	function getBranch() {
		if ( isset($this->data['branch_id']) ) {
			return $this->data['branch_id'];
		}

		return FALSE;
	}
	function setBranch($id) {
		$id = trim($id);

		$blf = TTnew( 'BranchListFactory' );

		if ( $this->Validator->isResultSetWithRows(	'branch',
													$blf->getByID($id),
													TTi18n::gettext('Selected Branch is invalid')
													) ) {
			$this->data['branch_id'] = $id;

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
		$obj = $this->getBranchObject();
		if ( is_object($obj) ) {
			return TTLog::addEntry( $this->getPremiumPolicy(), $log_action,  TTi18n::getText('Branch').': '. $obj->getName(), NULL, $this->getTable() );
		}
	}
}
?>
