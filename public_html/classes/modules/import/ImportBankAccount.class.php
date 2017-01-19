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
 * @package Modules\Import
 */
class ImportBankAccount extends Import {

	public $class_name = 'APIBankAccount';

	public $wage_group_options = FALSE;

	function _getFactoryOptions( $name, $parent = NULL ) {

		$retval = NULL;
		switch( $name ) {
			case 'columns':
				$baf = TTNew('BankAccountFactory');
				$retval = Misc::prependArray( $this->getUserIdentificationColumns(), Misc::arrayIntersectByKey( array('transit', 'institution', 'account'), Misc::trimSortPrefix( $baf->getOptions('columns') ) ) );

				break;
			case 'column_aliases':
				//Used for converting column names after they have been parsed.
				$retval = array(
								//'type' => 'type_id',
								//'wage_group' => 'wage_group_id',
								);
				break;
			case 'import_options':
				$retval = array(
								'-1010-fuzzy_match' => TTi18n::getText('Enable smart matching.'),
								);
				break;
			case 'parse_hint':
			case 'parse_hint':
				//$upf = TTnew('UserPreferenceFactory');

				$retval = array(
								//'effective_date' => $upf->getOptions('date_format'),
								//'weekly_time' => $upf->getOptions('time_unit_format'),
								);
				break;
		}

		return $retval;
	}


	function _preParseRow( $row_number, $raw_row ) {
		$retval = $this->getObject()->stripReturnHandler( $this->getObject()->getBankAccountDefaultData() );

		return $retval;
	}

	function _postParseRow( $row_number, $raw_row ) {
		$raw_row['user_id'] = $this->getUserIdByRowData( $raw_row );
		if ( $raw_row['user_id'] == FALSE ) {
			$raw_row['user_id'] = -1;
			//unset($raw_row['user_id']);
		}

		return $raw_row;
	}

	function _import( $validate_only ) {
		return $this->getObject()->setBankAccount( $this->getParsedData(), $validate_only );
	}

	//
	// Generic parser functions.
	//
	function parse_institution( $input, $default_value = NULL, $parse_hint = NULL ) {
		$val = new Validator();
		$retval = str_pad( $val->stripNonNumeric($input), 3, 0, STR_PAD_LEFT );

		return $retval;
	}
	function parse_transit( $input, $default_value = NULL, $parse_hint = NULL ) {
		$val = new Validator();
		$retval = $val->stripNonNumeric($input);

		return $retval;
	}
	function parse_account( $input, $default_value = NULL, $parse_hint = NULL ) {
		$val = new Validator();
		$retval = $val->stripNonNumeric($input);

		return $retval;
	}

}
?>
