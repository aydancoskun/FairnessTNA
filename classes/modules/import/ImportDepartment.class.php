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
 * @package Modules\Import
 */
class ImportDepartment extends Import {

	public $class_name = 'APIDepartment';

	/**
	 * @param $name
	 * @param null $parent
	 * @return array|null
	 */
	function _getFactoryOptions( $name, $parent = NULL ) {

		$retval = NULL;
		switch( $name ) {
			case 'columns':
				$bf = TTNew('DepartmentFactory'); /** @var DepartmentFactory $bf */
				$retval = $bf->getOptions('columns');
				break;
			case 'import_options':
				$retval = array(
								'-1010-fuzzy_match' => TTi18n::getText('Enable smart matching.'),
								);
				break;
			case 'parse_hint':
				$retval = array();
				break;
		}

		return $retval;
	}


	/**
	 * @param $row_number
	 * @param $raw_row
	 * @return mixed
	 */
	function _preParseRow( $row_number, $raw_row ) {
		$retval = $this->getObject()->stripReturnHandler( $this->getObject()->getDepartmentDefaultData() );
		$retval['manual_id'] += $row_number; //Auto increment manual_id automatically.

		return $retval;
	}

	/**
	 * @param int $validate_only EPOCH
	 * @return mixed
	 */
	function _import( $validate_only ) {
		return $this->getObject()->setDepartment( $this->getParsedData(), $validate_only );
	}

	//
	// Generic parser functions.
	//

	/**
	 * @param $input
	 * @param null $default_value
	 * @param null $parse_hint
	 * @param null $raw_row
	 * @return int
	 */
	function parse_status( $input, $default_value = NULL, $parse_hint = NULL, $raw_row = NULL ) {
		if ( strtolower( $input ) == 'e'
				OR strtolower( $input ) == 'enabled' ) {
			$retval = 10;
		} elseif ( strtolower( $input ) == 'd'
				OR strtolower( $input ) == 'disabled' ) {
			$retval = 20;
		} else {
			$retval = (int)$input;
		}

		return $retval;
	}

}
?>
