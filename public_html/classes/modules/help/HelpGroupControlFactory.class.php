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
 * @package Modules\Help
 */
class HelpGroupControlFactory extends Factory {
	protected $table = 'help_group_control';
	protected $pk_sequence_name = 'help_group_control_id_seq'; //PK Sequence name
	function getScriptName() {
		return $this->data['script_name'];
	}
	function setScriptName($value) {
		$value = trim($value);

		if (	$this->Validator->isLength(	'script_name',
											$value,
											TTi18n::gettext('Incorrect Script Name'),
											2, 255) ) {

			$this->data['script_name'] = $value;

			return FALSE;
		}

		return FALSE;
	}

	function getName() {
		return $this->data['name'];
	}
	function setName($value) {
		$value = trim($value);

		if (	$value == ''
				OR
				$this->Validator->isLength(	'name',
											$value,
											TTi18n::gettext('Incorrect Name'),
											2, 255) ) {

			$this->data['name'] = $value;

			return FALSE;
		}

		return FALSE;
	}

	function getHelp() {
		$hglf = TTnew( 'HelpGroupListFactory' );
		$hglf->getByHelpGroupControlId( $this->getId() );
		foreach ($hglf as $help_group_obj) {
			$help_list[] = $help_group_obj->getHelp();
		}

		if ( isset($help_list) ) {
			return $help_list;
		}

		return FALSE;
	}
	function setHelp($ids) {
		//If needed, delete mappings first.
		$hglf = TTnew( 'HelpGroupListFactory' );
		$hglf->getByHelpGroupControlId( $this->getId() );

		$help_ids = array();
		foreach ($hglf as $help_group_entry) {
			$help_id = $help_group_entry->getHelp();
			Debug::text('Help ID: '. $help_group_entry->getHelp(), __FILE__, __LINE__, __METHOD__, 10);

			//Delete all items first.				
			$help_group_entry->Delete();
		}
		
		if (is_array($ids) AND count($ids) > 0) {

			//Insert new mappings.
			$hgf = TTnew( 'HelpGroupFactory' );
			$i = 0;
			foreach ($ids as $id) {
				//if ( !in_array($id, $help_ids) ) {
					$hgf->setHelpGroupControl( $this->getId() );
					$hgf->setOrder( $i );
					$hgf->setHelp( $id );
					

					if ($this->Validator->isTrue(		'help',
														$hgf->Validator->isValid(),
														TTi18n::gettext('Incorrect Help Entry'))) {
						$hgf->save();
					}
				//}
				$i++;
			}

			//return TRUE;
		}

		return TRUE;
	}

}
?>
