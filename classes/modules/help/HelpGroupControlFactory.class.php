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
 * @package Modules\Help
 */
class HelpGroupControlFactory extends Factory {
	protected $table = 'help_group_control';
	protected $pk_sequence_name = 'help_group_control_id_seq'; //PK Sequence name

	/**
	 * @return mixed
	 */
	function getScriptName() {
		return $this->getGenericDataValue( 'script_name' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setScriptName( $value) {
		$value = trim($value);
		return $this->setGenericDataValue( 'script_name', $value );
	}

	/**
	 * @return mixed
	 */
	function getName() {
		return $this->getGenericDataValue( 'name' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setName( $value) {
		$value = trim($value);
		return $this->setGenericDataValue( 'name', $value );
	}

	/**
	 * @return array|bool
	 */
	function getHelp() {
		$hglf = TTnew( 'HelpGroupListFactory' ); /** @var HelpGroupListFactory $hglf */
		$hglf->getByHelpGroupControlId( $this->getId() );
		foreach ($hglf as $help_group_obj) {
			$help_list[] = $help_group_obj->getHelp();
		}

		if ( isset($help_list) ) {
			return $help_list;
		}

		return FALSE;
	}

	/**
	 * @param string $ids UUID
	 * @return bool
	 */
	function setHelp( $ids) {
		//If needed, delete mappings first.
		$hglf = TTnew( 'HelpGroupListFactory' ); /** @var HelpGroupListFactory $hglf */
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
			$hgf = TTnew( 'HelpGroupFactory' ); /** @var HelpGroupFactory $hgf */
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

	/**
	 * @return bool
	 */
	function Validate() {
		//
		// BELOW: Validation code moved from set*() functions.
		//
		// Script Name
		$this->Validator->isLength(	'script_name',
											$this->getScriptName(),
											TTi18n::gettext('Incorrect Script Name'),
											2, 255
										);
		// Name
		$this->Validator->isLength(	'name',
											$this->getName(),
											TTi18n::gettext('Incorrect Name'),
											2, 255
										);

		//
		// ABOVE: Validation code moved from set*() functions.
		//
		return TRUE;
	}

}
?>
