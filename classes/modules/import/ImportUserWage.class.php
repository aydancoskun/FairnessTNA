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
class ImportUserWage extends Import {

	public $class_name = 'APIUserWage';

	public $wage_group_options = FALSE;

	/**
	 * @param $name
	 * @param null $parent
	 * @return array|bool|null
	 */
	function _getFactoryOptions( $name, $parent = NULL ) {

		$retval = NULL;
		switch( $name ) {
			case 'columns':
				$uwf = TTNew('UserWageFactory'); /** @var UserWageFactory $uwf */
				$retval = Misc::prependArray( $this->getUserIdentificationColumns(), Misc::arrayIntersectByKey( array('wage_group', 'type', 'wage', 'effective_date', 'hourly_rate', 'labor_burden_percent', 'weekly_time', 'note'), Misc::trimSortPrefix( $uwf->getOptions('columns') ) ) );

				break;
			case 'column_aliases':
				//Used for converting column names after they have been parsed.
				$retval = array(
								'type' => 'type_id',
								'wage_group' => 'wage_group_id',
								);
				break;
			case 'import_options':
				$retval = array(
								'-1010-fuzzy_match' => TTi18n::getText('Enable smart matching.'),
								);
				break;
			case 'parse_hint':
				$upf = TTnew('UserPreferenceFactory'); /** @var UserPreferenceFactory $upf */

				$retval = array(
								'effective_date' => $upf->getOptions('date_format'),
								'weekly_time' => $upf->getOptions('time_unit_format'),
								);
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
		$user_id = $this->getUserIdByRowData( $raw_row ); //Try to get user_id of row so we set default effective_date to the employees hire_date.
		$user_wage_default_data = $this->getObject()->stripReturnHandler( $this->getObject()->getUserWageDefaultData( $user_id ) );
		$user_wage_default_data['effective_date'] = TTDate::parseDateTime( $user_wage_default_data['effective_date'] ); //Effective Date is formatted for user to see, so convert it to epoch for importing.

		$retval = $user_wage_default_data;

		return $retval;
	}

	/**
	 * @param $row_number
	 * @param $raw_row
	 * @return mixed
	 */
	function _postParseRow( $row_number, $raw_row ) {
		$raw_row['user_id'] = $this->getUserIdByRowData( $raw_row );
		if ( $raw_row['user_id'] == FALSE ) {
			unset($raw_row['user_id']);
		}

		//If its a salary type, make sure average weekly time is always specified and hourly rate.
		return $raw_row;
	}

	/**
	 * @param int $validate_only EPOCH
	 * @return mixed
	 */
	function _import( $validate_only ) {
		return $this->getObject()->setUserWage( $this->getParsedData(), $validate_only );
	}

	//
	// Generic parser functions.
	//

	/**
	 * @return bool
	 */
	function getWageGroupOptions() {
		//Get job titles
		$wglf = TTNew('WageGroupListFactory'); /** @var WageGroupListFactory $wglf */
		$wglf->getByCompanyId( $this->company_id );
		$this->wage_group_options = (array)$wglf->getArrayByListFactory( $wglf, FALSE );
		unset($wglf);

		return TRUE;
	}

	/**
	 * @param $input
	 * @param null $default_value
	 * @param null $parse_hint
	 * @return array|bool|int|mixed
	 */
	function parse_wage_group( $input, $default_value = NULL, $parse_hint = NULL ) {
		if ( trim($input) == '' OR trim(strtolower($input)) == 'default' ) {
			return TTUUID::getZeroID(); //Default Wage Group
		}

		if ( !is_array( $this->wage_group_options ) ) {
			$this->getWageGroupOptions();
		}

		$retval = $this->findClosestMatch( $input, $this->wage_group_options );
		if ( $retval === FALSE ) {
			$retval = -1; //Make sure this fails.
		}

		return $retval;
	}


	/**
	 * @param $input
	 * @param null $default_value
	 * @param null $parse_hint
	 * @param null $raw_row
	 * @return bool|false|int
	 */
	function parse_effective_date( $input, $default_value = NULL, $parse_hint = NULL, $raw_row = NULL ) {
		if ( isset($parse_hint) AND $parse_hint != '' ) {
			TTDate::setDateFormat( $parse_hint );
			return TTDate::parseDateTime( $input );
		} else {
			return TTDate::strtotime( $input );
		}
	}

	/**
	 * @param $input
	 * @param null $default_value
	 * @param null $parse_hint
	 * @param null $raw_row
	 * @return array|bool|int|mixed
	 */
	function parse_type( $input, $default_value = NULL, $parse_hint = NULL, $raw_row = NULL ) {
		$uwf = TTnew('UserWageFactory'); /** @var UserWageFactory $uwf */
		$options = Misc::trimSortPrefix( $uwf->getOptions( 'type' ) );

		if ( isset($options[$input]) ) {
			$retval = $input;
		} else {
			if ( $this->getImportOptions('fuzzy_match') == TRUE ) {
				$retval = $this->findClosestMatch( $input, $options, 50 );
			} else {
				$retval = array_search( strtolower($input), array_map('strtolower', $options) );
			}
		}

		if ( $retval === FALSE ) {
			if ( strtolower( $input ) == 'salary' OR strtolower( $input ) == 'salaried' OR strtolower( $input ) == 's' OR strtolower( $input ) == 'annual' ) {
				$retval = 20;
			} elseif ( strtolower( $input ) == 'month' OR strtolower( $input ) == 'monthly') {
				$retval = 15;
			} elseif ( strtolower( $input ) == 'biweekly' OR strtolower( $input ) == 'bi-weekly') {
				$retval = 13;
			} elseif ( strtolower( $input ) == 'week' OR strtolower( $input ) == 'weekly') {
				$retval = 12;
			} else {
				$retval = 10;
			}
		}

		return $retval;
	}

	/**
	 * @param $input
	 * @param null $default_value
	 * @param null $parse_hint
	 * @param null $raw_row
	 * @return bool|float|int|number|string
	 */
	function parse_weekly_time( $input, $default_value = NULL, $parse_hint = NULL, $raw_row = NULL ) {
		if ( isset($parse_hint) AND $parse_hint != '' ) {
			TTDate::setTimeUnitFormat( $parse_hint );
		}

		$retval = TTDate::parseTimeUnit( $input );

		return $retval;
	}

	/**
	 * @param $input
	 * @param null $default_value
	 * @param null $parse_hint
	 * @param null $raw_row
	 * @return mixed
	 */
	function parse_wage( $input, $default_value = NULL, $parse_hint = NULL, $raw_row = NULL ) {
		$val = new Validator();
		$retval = $val->stripNonFloat($input);

		return $retval;
	}
}
?>
