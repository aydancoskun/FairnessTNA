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
 * $Revision: 3387 $
 * $Id: ImportBranch.class.php 3387 2010-03-04 17:42:17Z ipso $
 * $Date: 2010-03-04 09:42:17 -0800 (Thu, 04 Mar 2010) $
 */


/**
 * @package Modules\Import
 */
class ImportPayStubAmendment extends Import {

	public $class_name = 'APIPayStubAmendment';

	public $pay_stub_account_options = FALSE;

	function _getFactoryOptions( $name, $parent = NULL ) {

		$retval = NULL;
		switch( $name ) {
			case 'columns':
				$psaf = TTNew('PayStubAmendmentFactory');
				$retval = Misc::prependArray( $this->getUserIdentificationColumns(), Misc::arrayIntersectByKey( array('status','type','pay_stub_entry_name','effective_date','amount','rate','units','description','ytd_adjustment'), Misc::trimSortPrefix( $psaf->getOptions('columns') ) ) );

				break;
			case 'column_aliases':
				//Used for converting column names after they have been parsed.
				$retval = array(
								'status' => 'status_id',
								'type' => 'type_id',
								'pay_stub_entry_name' => 'pay_stub_entry_name_id',
								);
				break;
			case 'import_options':
				$retval = array(
								'-1010-fuzzy_match' => TTi18n::getText('Enable smart matching.'),
								);
				break;
			case 'parse_hint':
			case 'parse_hint':
				$upf = TTnew('UserPreferenceFactory');

				$retval = array(
								'effective_date' => $upf->getOptions('date_format'),
								//'amount' => $upf->getOptions('time_unit_format'),
								);
				break;
		}

		return $retval;
	}


	function _preParseRow( $row_number, $raw_row ) {
		$retval = $this->getObject()->getPayStubAmendmentDefaultData();

		return $retval;
	}

	function _postParseRow( $row_number, $raw_row ) {
		$raw_row['user_id'] = $this->getUserIdByRowData( $raw_row );
		if ( $raw_row['user_id'] == FALSE ) {
			unset($raw_row['user_id']);
		}

		return $raw_row;
	}

	function _import( $validate_only ) {
		return $this->getObject()->setPayStubAmendment( $this->getParsedData(), $validate_only );
	}

	//
	// Generic parser functions.
	//
	function getPayStubAccountOptions() {
		//Get accrual policies
		$psealf = TTNew('PayStubEntryAccountListFactory');
		$psealf->getByCompanyId( $this->company_id );
		$this->pay_stub_account_options = (array)$psealf->getArrayByListFactory( $psealf, FALSE, TRUE );
		unset($aplf);

		return TRUE;
	}
	function parse_pay_stub_entry_name( $input, $default_value = NULL, $parse_hint = NULL ) {
		if ( trim($input) == '' ) {
			return 0; //Default Wage Group
		}

		if ( !is_array( $this->pay_stub_account_options ) ) {
			$this->getPayStubAccountOptions();
		}

		$retval = $this->findClosestMatch( $input, $this->pay_stub_account_options );
		if ( $retval === FALSE ) {
			$retval = -1; //Make sure this fails.
		}

		return $retval;
	}

	function parse_effective_date( $input, $default_value = NULL, $parse_hint = NULL ) {
		return $this->parse_date( $input, $default_value, $parse_hint );
	}

	function parse_status( $input, $default_value = NULL, $parse_hint = NULL ) {
		$psaf = TTnew('PayStubAmendmentFactory');
		$options = Misc::trimSortPrefix( $psaf->getOptions( 'status' ) );

		if ( isset($options[$input]) ) {
			return $input;
		} else {
			if ( $this->getImportOptions('fuzzy_match') == TRUE ) {
				return $this->findClosestMatch( $input, $options, 50 );
			} else {
				return array_search( strtolower($input), array_map('strtolower', $options) );
			}
		}
	}

	function parse_type( $input, $default_value = NULL, $parse_hint = NULL ) {
		$psaf = TTnew('PayStubAmendmentFactory');
		$options = Misc::trimSortPrefix( $psaf->getOptions( 'type' ) );

		if ( isset($options[$input]) ) {
			return $input;
		} else {
			if ( $this->getImportOptions('fuzzy_match') == TRUE ) {
				return $this->findClosestMatch( $input, $options, 50 );
			} else {
				return array_search( strtolower($input), array_map('strtolower', $options) );
			}
		}
	}

	function parse_amount( $input, $default_value = NULL, $parse_hint = NULL, $raw_row = NULL ) {
		$val = new Validator();
		$retval = $val->stripNonFloat($input);

		return $retval;
	}

	function parse_rate( $input, $default_value = NULL, $parse_hint = NULL, $raw_row = NULL ) {
		$val = new Validator();
		$retval = $val->stripNonFloat($input);

		return $retval;
	}
	function parse_units( $input, $default_value = NULL, $parse_hint = NULL, $raw_row = NULL ) {
		$val = new Validator();
		$retval = $val->stripNonFloat($input);

		return $retval;
	}
}
?>
