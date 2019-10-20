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
class ImportPayPeriod extends Import {

	public $class_name = 'APIPayPeriod';

	public $pay_period_schedule_options = FALSE;

	/**
	 * @param $name
	 * @param null $parent
	 * @return array|null
	 */
	function _getFactoryOptions( $name, $parent = NULL ) {

		$retval = NULL;
		switch( $name ) {
			case 'columns':
				$ppf = TTNew('PayPeriodFactory'); /** @var PayPeriodFactory $ppf */
				$retval = Misc::arrayIntersectByKey( array('pay_period_schedule', 'start_date', 'end_date', 'transaction_date'), Misc::trimSortPrefix( $ppf->getOptions('columns') ) );
				break;
			case 'column_aliases':
				//Used for converting column names after they have been parsed.
				$retval = array(
								'pay_period_schedule' => 'pay_period_schedule_id',
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
								'start_date' => $upf->getOptions('date_format'),
								'end_date' => $upf->getOptions('date_format'),
								'transaction_date' => $upf->getOptions('date_format'),
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
		$retval = $this->getObject()->stripReturnHandler( $this->getObject()->getPayPeriodDefaultData() );

		return $retval;
	}

	/**
	 * @param $row_number
	 * @param $raw_row
	 * @return mixed
	 */
	function _postParseRow( $row_number, $raw_row ) {
		//$raw_row['user_id'] = $this->getUserIdByRowData( $raw_row );
		//if ( $raw_row['user_id'] == FALSE ) {
		//	unset($raw_row['user_id']);
		//}

		//If its a salary type, make sure average weekly time is always specified and hourly rate.
		return $raw_row;
	}

	/**
	 * @param int $validate_only EPOCH
	 * @return mixed
	 */
	function _import( $validate_only ) {
		return $this->getObject()->setPayPeriod( $this->getParsedData(), $validate_only );
	}

	//
	// Generic parser functions.
	//

	/**
	 * @return bool
	 */
	function getPayPeriodScheduleOptions() {
		//Get job titles
		$ppslf = TTNew('PayPeriodScheduleListFactory'); /** @var PayPeriodScheduleListFactory $ppslf */
		$ppslf->getByCompanyId( $this->company_id );
		$this->pay_period_schedule_options = (array)$ppslf->getArrayByListFactory( $ppslf, FALSE );
		unset($ppslf);

		return TRUE;
	}

	/**
	 * @param $input
	 * @param null $default_value
	 * @param null $parse_hint
	 * @return array|bool|int|mixed
	 */
	function parse_pay_period_schedule( $input, $default_value = NULL, $parse_hint = NULL ) {
		if ( !is_array( $this->pay_period_schedule_options ) ) {
			$this->getPayPeriodScheduleOptions();
		}

		if ( trim($input) == '' AND count($this->pay_period_schedule_options) == 1 ) {
			return key($this->pay_period_schedule_options); //Use first pay period schedule.
		}

		$retval = $this->findClosestMatch( $input, $this->pay_period_schedule_options );
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
	function parse_start_date( $input, $default_value = NULL, $parse_hint = NULL, $raw_row = NULL ) {
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
	 * @return bool|false|int
	 */
	function parse_end_date( $input, $default_value = NULL, $parse_hint = NULL, $raw_row = NULL ) {
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
	 * @return bool|false|int
	 */
	function parse_transaction_date( $input, $default_value = NULL, $parse_hint = NULL, $raw_row = NULL ) {
		if ( isset($parse_hint) AND $parse_hint != '' ) {
			TTDate::setDateFormat( $parse_hint );
			return TTDate::parseDateTime( $input );
		} else {
			return TTDate::strtotime( $input );
		}
	}
}
?>
