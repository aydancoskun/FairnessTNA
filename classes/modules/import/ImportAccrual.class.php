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
class ImportAccrual extends Import {

	public $class_name = 'APIAccrual';

	public $accrual_policy_account_options = FALSE;

	/**
	 * @param $name
	 * @param null $parent
	 * @return array|bool|null
	 */
	function _getFactoryOptions( $name, $parent = NULL ) {

		$retval = NULL;
		switch( $name ) {
			case 'columns':
				$apf = TTNew('AccrualFactory'); /** @var AccrualFactory $apf */
				$retval = Misc::prependArray( $this->getUserIdentificationColumns(), Misc::arrayIntersectByKey( array('accrual_policy_account', 'type', 'amount', 'date_stamp'), Misc::trimSortPrefix( $apf->getOptions('columns') ) ) );

				break;
			case 'column_aliases':
				//Used for converting column names after they have been parsed.
				$retval = array(
								'type' => 'type_id',
								'accrual_policy_account' => 'accrual_policy_account_id',
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
								'date_stamp' => $upf->getOptions('date_format'),
								'amount' => $upf->getOptions('time_unit_format'),
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
		$retval = $this->getObject()->stripReturnHandler( $this->getObject()->getAccrualDefaultData() );

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

		if ( isset($raw_row['date_stamp']) ) {
			$raw_row['time_stamp'] = $raw_row['date_stamp']; //AcrualFactory wants time_stamp column not date_stamp, so convert that here.
		}

		return $raw_row;
	}

	/**
	 * @param int $validate_only EPOCH
	 * @return mixed
	 */
	function _import( $validate_only ) {
		return $this->getObject()->setAccrual( $this->getParsedData(), $validate_only );
	}

	//
	// Generic parser functions.
	//

	/**
	 * @return bool
	 */
	function getAccrualPolicyAccountOptions() {
		//Get accrual policies
		$aplf = TTNew('AccrualPolicyAccountListFactory'); /** @var AccrualPolicyAccountListFactory $aplf */
		$aplf->getByCompanyId( $this->company_id );
		$this->accrual_policy_account_options = (array)$aplf->getArrayByListFactory( $aplf, FALSE );
		unset($aplf);

		return TRUE;
	}

	/**
	 * @param $input
	 * @param null $default_value
	 * @param null $parse_hint
	 * @return array|bool|int|mixed
	 */
	function parse_accrual_policy_account( $input, $default_value = NULL, $parse_hint = NULL ) {
		if ( trim($input) == '' ) {
			return TTUUID::getZeroID(); //Default Wage Group
		}

		if ( !is_array( $this->accrual_policy_account_options ) ) {
			$this->getAccrualPolicyAccountOptions();
		}

		$retval = $this->findClosestMatch( $input, $this->accrual_policy_account_options );
		if ( $retval === FALSE ) {
			$retval = -1; //Make sure this fails.
		}

		return $retval;
	}

	/**
	 * @param $input
	 * @param null $default_value
	 * @param null $parse_hint
	 * @return false|int
	 */
	function parse_date_stamp( $input, $default_value = NULL, $parse_hint = NULL ) {
		return $this->parse_date( $input, $default_value, $parse_hint );
	}

	/**
	 * @param $input
	 * @param null $default_value
	 * @param null $parse_hint
	 * @return array|bool|mixed
	 */
	function parse_type( $input, $default_value = NULL, $parse_hint = NULL ) {
		$af = TTnew('AccrualFactory'); /** @var AccrualFactory $af */
		$options = $af->getOptions( 'user_type' );

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

	/**
	 * @param $input
	 * @param null $default_value
	 * @param null $parse_hint
	 * @param null $raw_row
	 * @return bool|float|int|number|string
	 */
	function parse_amount( $input, $default_value = NULL, $parse_hint = NULL, $raw_row = NULL ) {
		$val = new Validator();

		TTDate::setTimeUnitFormat( $parse_hint );

		$retval = TTDate::parseTimeUnit( $val->stripNonTimeUnit($input) );

		return $retval;
	}
}
?>
