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
 * $Revision: 8371 $
 * $Id: HelpGroupFactory.class.php 8371 2012-11-22 21:18:57Z ipso $
 * $Date: 2012-11-22 13:18:57 -0800 (Thu, 22 Nov 2012) $
 */

/**
 * @package Modules\Help
 */
class HelpGroupFactory extends Factory {
	protected $table = 'help_group';
	protected $pk_sequence_name = 'help_group_id_seq'; //PK Sequence name
	function getHelpGroupControl() {
		return $this->data['help_group_control_id'];
	}
	function setHelpGroupControl($id) {
		$id = trim($id);
		
		$hgclf = TTnew( 'HelpGroupControlListFactory' );
		
		if ( $this->Validator->isResultSetWithRows(	'help_group_control',
													$hgclf->getByID($id),
													TTi18n::gettext('Help Group Control is invalid')
													) ) {
			$this->data['help_group_control_id'] = $id;
		
			return TRUE;
		}

		return FALSE;
	}

	function getHelp() {
		return $this->data['help_id'];
	}
	function setHelp($id) {
		$id = trim($id);
		
		$hlf = TTnew( 'HelpListFactory' );
		
		if ( $this->Validator->isResultSetWithRows(	'help',
													$hlf->getByID($id),
													TTi18n::gettext('Help Entry is invalid')
															) ) {
			$this->data['help_id'] = $id;
		
			return TRUE;
		}

		return FALSE;
	}
	
	function getOrder() {
		return $this->data['order_value'];
	}
	function setOrder($value) {
		$value = trim($value);
				
		if ( $this->Validator->isNumeric(	'order',
											$value,
											TTi18n::gettext('Order is invalid')
													) ) {
			$this->data['order_value'] = $value;
		
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
