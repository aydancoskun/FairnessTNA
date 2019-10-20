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
 * @package Modules\Payroll Agency
 */
class RemittanceSourceAccountFactory extends Factory {
	protected $table = 'remittance_source_account';
	protected $pk_sequence_name = 'remittance_source_account_id_seq'; //PK Sequence name

	protected $legal_entity_obj = NULL;
	protected $currency_obj = NULL;

	/**
	 * @param bool $name
	 * @param null $params
	 * @return array|bool|null
	 */
	function _getFactoryOptions( $name = FALSE, $params = NULL ) {

		$retval = NULL;
		switch( $name ) {
			case 'status':
				$retval = array(
					10 => TTi18n::gettext('Enabled'),
					20 => TTi18n::gettext('Disabled')
				);
				break;
			case 'country':
				$cf = TTNew('CompanyFactory'); /** @var CompanyFactory $cf */
				$retval = $cf->getOptions('country');
				break;
			case 'type':
				$retval = array(
					//1000 => TTi18n::gettext('FairnessTNA EFT'), //See Formats instead
					//1010 => TTi18n::gettext('FairnessTNA Check'), //See Formats instead
					2000 => TTi18n::gettext('Check'),
					3000 => TTi18n::gettext('EFT/ACH'),
					//9000 => TTi18n::gettext('Bitcoin'),
				);
				break;
			case 'data_format_eft_form': //data_format ID to EFT class name mapping.
				$retval = array(
						10 => 'ACH',
						20 => '1464',
						30 => '1464', //CIBC
						50 => '105',
						70 => 'BEANSTREAM'
				);
				break;
			case 'data_format_check_form': //data_format ID to CHECK class name mapping.
				$retval = array(
						10 => '9085', //cheque_9085
						20 => '9209P', //cheque_9209p
						30 => 'DLT103', //cheque_dlt103
						40 => 'DLT104', //cheque_dlt104
				);
				break;
			case 'data_format':
				$retval = array(
					0 => TTi18n::gettext('-- None --'),
				);

				if ( isset($params['type_id'])
						AND isset($params['country'])
						AND $params['country'] != FALSE ) {
					$tmp_retval = array();
					$valid_keys = array();
					switch( $params['type_id'] ) {
						case 2000: //Check
							$tmp_retval = array(
								//5  => TTi18n::gettext('FairnessTNA Checks'),
								10 => TTi18n::gettext('Top Check (Sage) [9085]'), //cheque_9085 // SS9085 (still current for Sage 50 & Accpac)  https://www.nebs.ca/canEcat/products/product_detail.jsp?pc=SS9085
								20 => TTi18n::gettext('Top Check (QuickBooks) [9209P]'), //cheque_9209p // SS9209 (still current for Quickbooks)  https://www.nebs.ca/canEcat/products/product_detail.jsp?pc=SS9209
								30 => TTi18n::gettext('Top Check Lined (QuickBooks) [DLT103]'), //cheque_dlt103 // DLT103 (fill-in lines on cheques)  https://www.deluxe.com/shopdeluxe/pd/laser-top-checks-lined/_/A-DLT103
								40 => TTi18n::gettext('Top Check (QuickBooks) [DLT104]'), //cheque_dlt104 // DLT104 ("$" & "Dollar" on cheques) https://www.deluxe.com/shopdeluxe/pd/laser-top-checks-lined/_/A-DLT104
							);
							$valid_keys = array_keys($tmp_retval);
							break;
						case 3000: //EFT
							$tmp_retval = array(
								5 => TTi18n::gettext( 'FairnessTNA Payment Services' ),
								10 => TTi18n::gettext( 'United States - ACH (94-Byte)' ),
								20 => TTi18n::gettext( 'Canada - EFT (1464-Byte)' ),
								30 => TTi18n::gettext( 'Canada - EFT CIBC (1464-Byte)'),
								//40 => TTi18n::gettext('Canada - EFT RBC (1464-Byte)'),
								50 => TTi18n::gettext( 'Canada - EFT (105-Byte)' ),
								//60 => TTi18n::gettext('Canada - HSBC EFT-PC (CSV)'),
								70 => TTi18n::gettext( 'Bambora (CSV)' )
							);

							if ( $params['country'] == 'US' ) {
								$valid_keys = array(5, 10);
							}elseif ( $params['country'] == 'CA' ) {
								$valid_keys = array(5, 20, 30, 50, 70);
							}
							break;
					}

					if( count($valid_keys) > 0 ) {
						unset($retval[0]); //remove "-- None --"
						foreach ( $valid_keys as $key ) {
							$retval[$key] = $tmp_retval[$key];
						}
					}
				}
				break;
			case 'columns':
				$retval = array(
					'-1010-status' => TTi18n::gettext('Status'),
					'-1020-type' => TTi18n::gettext('Type'),
					'-1030-legal_name' => TTi18n::gettext('Legal Entity Name'),
					'-1040-name' => TTi18n::gettext('Name'),
					'-1050-description' => TTi18n::gettext('Description'),
					'-1060-country' => TTi18n::gettext('Country'),
					'-1150-data_format' => TTi18n::gettext('Data Format'),
					'-1160-last_transaction_number' => TTi18n::gettext('Last Transaction Number'),

					'-1500-value1' => TTi18n::gettext('Institution'),
					'-1510-value2' => TTi18n::gettext('Transit/Routing'),
					'-1520-value3' => TTi18n::gettext('Account'),

					'-1900-in_use' => TTi18n::gettext('In Use'),
					'-2000-created_by' => TTi18n::gettext('Created By'),
					'-2010-created_date' => TTi18n::gettext('Created Date'),
					'-2020-updated_by' => TTi18n::gettext('Updated By'),
					'-2030-updated_date' => TTi18n::gettext('Updated Date'),
				);
				break;
			case 'list_columns':
				$retval = Misc::arrayIntersectByKey( $this->getOptions('default_display_columns'), Misc::trimSortPrefix( $this->getOptions('columns') ) );
				break;
			case 'default_display_columns': //Columns that are displayed by default.
				$retval = array(
					'status',
					'type',
					'legal_name',
					'name',
					'description',
					'country', //This is needed by JS to determine which fields to show, so users without access to view remittance source accounts doesn't break the UI.
				);
				break;
			case 'unique_columns': //Columns that are unique, and disabled for mass editing.
				$retval = array(
					'name',
				);
				break;
		}

		return $retval;
	}

	/**
	 * @param $data
	 * @return array
	 */
	function _getVariableToFunctionMap( $data ) {
		$variable_function_map = array(
			'id' => 'ID',
			'company_id' => 'Company',
			'legal_entity_id' => 'LegalEntity',
			'status_id' => 'Status',
			'status' => FALSE,
			'type_id' => 'Type',
			'type'	=> FALSE,
			'legal_name' => FALSE,
			'name' => 'Name',
			'description' => 'Description',
			'country' => 'Country',
			'currency_id' => 'Currency',
			'currency' => FALSE,
			'data_format_id' => 'DataFormat',
			'data_format' => FALSE,
			'last_transaction_number' => 'LastTransactionNumber',
			'value1' => 'Value1',
			'value2' => 'Value2',
			'value3' => 'Value3',
			'value4' => 'Value4',
			'value5' => 'Value5',
			'value6' => 'Value6',
			'value7' => 'Value7',
			'value8' => 'Value8',
			'value9' => 'Value9',
			'value10' => 'Value10',
			'value11' => 'Value11',
			'value12' => 'Value12',
			'value13' => 'Value13',
			'value14' => 'Value14',
			'value15' => 'Value15',
			'value16' => 'Value16',
			'value17' => 'Value17',
			'value18' => 'Value18',
			'value19' => 'Value19',
			'value20' => 'Value20',
			'value21' => 'Value21',
			'value22' => 'Value22',
			'value23' => 'Value23',
			'value24' => 'Value24',
			'value25' => 'Value25',
			'value26' => 'Value26',
			'value27' => 'Value27',
			'value28' => 'Value28',
			'value29' => 'Value29',
			'value30' => 'Value30',
			'in_use' => FALSE,
			'deleted' => 'Deleted',
		);
		return $variable_function_map;
	}

	/**
	 * @return bool
	 */
	function getCompanyObject() {
		return $this->getGenericObject( 'CompanyListFactory', $this->getCompany(), 'company_obj' );
	}

	/**
	 * @return bool
	 */
	function getCurrencyObject() {
		return $this->getGenericObject( 'CurrencyListFactory', $this->getCurrency(), 'currency_obj' );
	}

	/**
	 * @return bool
	 */
	function getLegalEntityObject() {
		return $this->getGenericObject( 'LegalEntityListFactory', $this->getLegalEntity(), 'legal_entity_obj' );
	}

	/**
	 * @return bool|mixed
	 */
	function getCompany() {
		return $this->getGenericDataValue( 'company_id' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setCompany( $value) {
		$value = trim($value);
		$value = TTUUID::castUUID( $value );
		return $this->setGenericDataValue( 'company_id', $value );
	}

	/**
	 * @return bool|mixed
	 */
	function getLegalEntity() {
		return $this->getGenericDataValue( 'legal_entity_id' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setLegalEntity( $value) {
		$value = TTUUID::castUUID( $value );
		return $this->setGenericDataValue( 'legal_entity_id', $value );
	}

	/**
	 * @return bool|mixed
	 */
	function getCurrency() {
		return $this->getGenericDataValue( 'currency_id' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setCurrency( $value) {
		$value = TTUUID::castUUID( $value );
		Debug::Text('Currency ID: '. $value, __FILE__, __LINE__, __METHOD__, 10);
		return $this->setGenericDataValue( 'currency_id', $value );
	}

	/**
	 * @return int
	 */
	function getStatus() {
		return $this->getGenericDataValue( 'status_id' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setStatus( $value) {
		$value = (int)trim($value);
		return $this->setGenericDataValue( 'status_id', $value );
	}

	/**
	 * @return int
	 */
	function getType() {
		return $this->getGenericDataValue( 'type_id' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setType( $value ) {
		$value = (int)trim($value);
		return $this->setGenericDataValue( 'type_id', $value );
	}

	/**
	 * @return bool|mixed
	 */
	function getCountry() {
		return $this->getGenericDataValue( 'country' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setCountry( $value ) {
		$value = trim($value);
		return $this->setGenericDataValue( 'country', $value );
	}

	/**
	 * @return int
	 */
	function getDataFormat() {
		return $this->getGenericDataValue( 'data_format_id' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setDataFormat( $value ) {
		$value = (int)trim($value);
		return $this->setGenericDataValue( 'data_format_id', $value );
	}

	/**
	 * @param $name
	 * @return bool
	 */
	function isUniqueName( $name) {
		$name = trim($name);

		$company_id = $this->getCompany();

		if ( $name == '' ) {
			return FALSE;
		}

		if ( $company_id == '' ) {
			return FALSE;
		}

		$ph = array(
			'company_id' => TTUUID::castUUID($company_id),
			'type_id' => (int)$this->getType(),
			'name' => $name,
		);

		$query = 'SELECT a.id
					FROM '. $this->getTable() .' as a
					WHERE a.company_id = ?
					    AND a.type_id = ?
					    AND LOWER(a.name) = LOWER(?)
						AND a.deleted = 0';

		$name_id = $this->db->GetOne($query, $ph);
		Debug::Arr($name_id, 'Unique Name: '. $name, __FILE__, __LINE__, __METHOD__, 10);

		if ( $name_id === FALSE ) {
			return TRUE;
		} else {
			if ($name_id == $this->getId() ) {
				return TRUE;
			}
		}

		return FALSE;
	}

	/**
	 * @return bool|mixed
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
	 * @return bool|mixed
	 */
	function getDescription() {
		return $this->getGenericDataValue( 'description' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setDescription( $value) {
		$value = trim($value);
		return $this->setGenericDataValue( 'description', $value );
	}

	/**
	 * @return bool|mixed
	 */
	function getLastTransactionNumber() {
		return $this->getGenericDataValue( 'last_transaction_number' );
	}

	/**
	 * @return int
	 */
	function getNextTransactionNumber() {
		return ( $this->getLastTransactionNumber() + 1 );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setLastTransactionNumber( $value) {
		$value = trim($value);

		//Pull out only digits
//		$value = $this->Validator->stripNonNumeric($value);
//
//		if (	$this->Validator->isFloat(	'last_transaction_number',
//											$value,
//											TTi18n::gettext('Incorrect transaction number')) ) {
//
//			$this->setGenericDataValue( 'last_transaction_number', $value );
//
//			return TRUE;
//		}
//
//		return FALSE;

		$this->setGenericDataValue( 'last_transaction_number', $value );

		return TRUE;
	}

	/**
	 * @return bool|mixed
	 */
	function getValue1() {
		return $this->getGenericDataValue( 'value1' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setValue1( $value) {
		$value = trim($value);
		return $this->setGenericDataValue( 'value1', $value );
	}

	/**
	 * @return bool|mixed
	 */
	function getValue2() {
		return $this->getGenericDataValue( 'value2' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setValue2( $value) {
		$value = trim($value);
		return $this->setGenericDataValue( 'value2', $value );
	}

	/**
	 * VALUE 3 is the account number. It needs to be encrypted.
	 * @param null $account
	 * @return bool|string
	 */
	function getSecureValue3( $account = NULL ) {
		if ( $account == NULL ) {
			$account = $this->getValue3();
		}

		return Misc::censorString( $account, 'X', 1, 2, 1, 4 );
	}

	/**
	 * VALUE 3 is the account number. It needs to be encrypted.
	 * @return bool|string
	 */
	function getValue3() {
		$value = $this->getGenericDataValue( 'value3' );
		if ( $value !== FALSE ) {
			$retval = Misc::decrypt( $value );
			if ( is_numeric( $retval ) ) {
				return $retval;
			}
		}
		return FALSE;
	}

	/**
	 * VALUE 3 is the account number. It needs to be encrypted.
	 * @param $value
	 * @return bool
	 */
	function setValue3($value) {
		//If X's are in the account number, skip setting it
		// Also if a colon is in the account number, its likely an encrypted string, also skip.
		//This allows them to change other data without seeing the account number.
		if ( stripos( $value, 'X') !== FALSE OR stripos( $value, ':') !== FALSE OR ctype_digit( trim($value) ) == FALSE ) { //Use ctype_digit to confirm bank account number is DIGITS only, so we don't accept scientific notation "5.18E+11".
			return FALSE;
		}

		$value = trim($value);
		if ( $value != '' ) { //Make sure we can clear out the value if needed. Misc::encypt() will return FALSE on a blank value.
			$encrypted_value = Misc::encrypt( $value );
			if ( $encrypted_value === FALSE ) {
				return FALSE;
			}
		} else {
			$encrypted_value = $value;
		}

		return $this->setGenericDataValue( 'value3', $encrypted_value );
	}

	/**
	 * @return bool|mixed
	 */
	function getValue4() {
		return $this->getGenericDataValue( 'value4' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setValue4( $value) {
		$value = trim($value);
		return $this->setGenericDataValue( 'value4', $value );
	}

	/**
	 * @return bool|mixed
	 */
	function getValue5() {
		return $this->getGenericDataValue( 'value5' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setValue5( $value) {
		$value = trim($value);
		return $this->setGenericDataValue( 'value5', $value );
	}


	/**
	 * @return bool|mixed
	 */
	function getValue6() {
		return $this->getGenericDataValue( 'value6' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setValue6( $value) {
		$value = trim($value);
		return $this->setGenericDataValue( 'value6', $value );
	}

	/**
	 * @return bool|mixed
	 */
	function getValue7() {
		return $this->getGenericDataValue( 'value7' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setValue7( $value) {
		$value = trim($value);
		return $this->setGenericDataValue( 'value7', $value );
	}

	/**
	 * @return bool|mixed
	 */
	function getValue8() {
		return $this->getGenericDataValue( 'value8' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setValue8( $value) {
		$value = trim($value);
		return $this->setGenericDataValue( 'value8', $value );
	}

	/**
	 * @return bool|mixed
	 */
	function getValue9() {
		return $this->getGenericDataValue( 'value9' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setValue9( $value) {
		$value = trim($value);
		return $this->setGenericDataValue( 'value9', $value );
	}

	/**
	 * @return bool|mixed
	 */
	function getValue10() {
		return $this->getGenericDataValue( 'value10' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setValue10( $value) {
		$value = trim($value);
		return $this->setGenericDataValue( 'value10', $value );
	}

	/**
	 * @return bool|mixed
	 */
	function getValue11() {
		return $this->getGenericDataValue( 'value11' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setValue11( $value) {
		$value = trim($value);
		return $this->setGenericDataValue( 'value11', $value );
	}

	/**
	 * @return bool|mixed
	 */
	function getValue12() {
		return $this->getGenericDataValue( 'value12' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setValue12( $value) {
		$value = trim($value);
		return $this->setGenericDataValue( 'value12', $value );
	}

	/**
	 * @return bool|mixed
	 */
	function getValue13() {
		return $this->getGenericDataValue( 'value13' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setValue13( $value) {
		$value = trim($value);
		return $this->setGenericDataValue( 'value13', $value );
	}

	/**
	 * @return bool|mixed
	 */
	function getValue14() {
		return $this->getGenericDataValue( 'value14' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setValue14( $value) {
		$value = trim($value);
		return $this->setGenericDataValue( 'value14', $value );
	}

	/**
	 * @return bool|mixed
	 */
	function getValue15() {
		return $this->getGenericDataValue( 'value15' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setValue15( $value) {
		$value = trim($value);
		return $this->setGenericDataValue( 'value15', $value );
	}


	/**
	 * @return bool|mixed
	 */
	function getValue16() {
		return $this->getGenericDataValue( 'value16' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setValue16( $value) {
		$value = trim($value);
		return $this->setGenericDataValue( 'value16', $value );
	}

	/**
	 * @return bool|mixed
	 */
	function getValue17() {
		return $this->getGenericDataValue( 'value17' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setValue17( $value) {
		$value = trim($value);
		return $this->setGenericDataValue( 'value17', $value );
	}

	/**
	 * @return bool|mixed
	 */
	function getValue18() {
		return $this->getGenericDataValue( 'value18' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setValue18( $value) {
		$value = trim($value);
		return $this->setGenericDataValue( 'value18', $value );
	}

	/**
	 * @return bool|mixed
	 */
	function getValue19() {
		return $this->getGenericDataValue( 'value19' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setValue19( $value) {
		$value = trim($value);
		return $this->setGenericDataValue( 'value19', $value );
	}

	/**
	 * @return bool|mixed
	 */
	function getValue20() {
		return $this->getGenericDataValue( 'value20' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setValue20( $value) {
		$value = trim($value);
		return $this->setGenericDataValue( 'value20', $value );
	}

	/**
	 * @return bool|mixed
	 */
	function getValue21() {
		return $this->getGenericDataValue( 'value21' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setValue21( $value) {
		$value = trim($value);
		return $this->setGenericDataValue( 'value21', $value );
	}

	/**
	 * @return bool|mixed
	 */
	function getValue22() {
		return $this->getGenericDataValue( 'value22' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setValue22( $value) {
		$value = trim($value);
		return $this->setGenericDataValue( 'value22', $value );
	}

	/**
	 * @return bool|mixed
	 */
	function getValue23() {
		return $this->getGenericDataValue( 'value23' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setValue23( $value) {
		$value = trim($value);
		return $this->setGenericDataValue( 'value23', $value );
	}

	/**
	 * @return bool|mixed
	 */
	function getValue24() {
		return $this->getGenericDataValue( 'value24' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setValue24( $value) {
		$value = trim($value);
		return $this->setGenericDataValue( 'value24', $value );
	}

	/**
	 * @return bool|mixed
	 */
	function getValue25() {
		return $this->getGenericDataValue( 'value25' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setValue25( $value) {
		$value = trim($value);
		return $this->setGenericDataValue( 'value25', $value );
	}


	/**
	 * @return bool|mixed
	 */
	function getValue26() {
		return $this->getGenericDataValue( 'value26' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setValue26( $value) {
		$value = trim($value);
		return $this->setGenericDataValue( 'value26', $value );
	}

	/**
	 * @return bool|mixed
	 */
	function getValue27() {
		return $this->getGenericDataValue( 'value27' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setValue27( $value) {
		$value = trim($value);
		return $this->setGenericDataValue( 'value27', $value );
	}

	/**
	 * VALUE 28 is the return account number. It needs to be encrypted.
	 * @param null $account
	 * @return bool|string
	 */
	function getSecureValue28( $account = NULL ) {
		if ( $account == NULL ) {
			$account = $this->getValue28();
		}

		return Misc::censorString( $account, 'X', 1, 2, 1, 4 );
	}

	/**
	 * VALUE 28 is the return account number. It needs to be encrypted.
	 * @return bool|string
	 */
	function getValue28() {
		$value = $this->getGenericDataValue( 'value28' );
		if ( $value !== FALSE ) {
			$retval = Misc::decrypt( $value );
			if ( is_numeric( $retval ) ) {
				return $retval;
			}
		}
		return FALSE;
	}

	/**
	 * VALUE 28 is the return account number. It needs to be encrypted.
	 * @param $value
	 * @return bool
	 */
	function setValue28($value) {
		//If X's are in the account number, skip setting it
		// Also if a colon is in the account number, its likely an encrypted string, also skip.
		//This allows them to change other data without seeing the account number.
		if ( stripos( $value, 'X') !== FALSE OR stripos( $value, ':') !== FALSE ) {
			return FALSE;
		}

		$value = trim($value);
		if ( $value != '' ) { //Make sure we can clear out the value if needed. Misc::encypt() will return FALSE on a blank value.
			$encrypted_value = Misc::encrypt( $value );
			if ( $encrypted_value === FALSE ) {
				return FALSE;
			}
		} else {
			$encrypted_value = $value;
		}

		return $this->setGenericDataValue( 'value28', $encrypted_value );
	}

	/**
	 * @return bool|mixed
	 */
	function getValue29() {
		return $this->getGenericDataValue( 'value29' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setValue29( $value) {
		$value = trim($value);
		return $this->setGenericDataValue( 'value29', $value );
	}

	/**
	 * @return bool|mixed
	 */
	function getValue30() {
		return $this->getGenericDataValue( 'value30' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setValue30( $value) {
		$value = trim($value);
		return $this->setGenericDataValue( 'value30', $value );
	}


	/**
	 * @return bool
	 */
	function isSignatureExists() {
		return file_exists( $this->getSignatureFileName() );
	}

	/**
	 * @param string $company_id UUID
	 * @param string $id UUID
	 * @return bool|string
	 */
	function getSignatureFileName( $company_id = NULL, $id = NULL ) {
		if ( $id == NULL ) {
			$id = $this->getId();
		}

		//Test for both jpg and png
		$base_name = $this->getStoragePath( $company_id ) . DIRECTORY_SEPARATOR . $id;
		if ( file_exists( $base_name.'.img') ) {
			$signature_file_name = $base_name.'.img';
		} else {
			$signature_file_name = FALSE;
		}

		//Debug::Text('Logo File Name: '. $signature_file_name .' Base Name: '. $base_name .' User ID: '. $user_id .' Include Default: '. (int)$include_default_signature, __FILE__, __LINE__, __METHOD__, 10);
		return $signature_file_name;
	}

	/**
	 * @param string $company_id UUID
	 * @param string $id UUID
	 * @return bool
	 */
	function cleanStoragePath( $company_id = NULL, $id = NULL ) {
		if ( $company_id == '' ) {
			if ( is_object( $this->getLegalEntityObject() ) ) {
				$company_id = $this->getLegalEntityObject()->getCompany();
			}
		}

		if ( $company_id == '' ) {
			return FALSE;
		}

		$dir = $this->getStoragePath( $company_id ) . DIRECTORY_SEPARATOR;
		if ( $dir != '' ) {
			if ( $id != '' ) {
				@unlink( $this->getSignatureFileName( $company_id, $id ) ); //Delete just signature.
			} else {
				//Delete tmp files.
				foreach(glob($dir.'*') as $filename) {
					unlink($filename);
					Misc::deleteEmptyDirectory( dirname( $filename ), 0 ); //Recurse to $user_id parent level and remove empty directories.
				}
			}
		}

		return TRUE;
	}

	/**
	 * @param string $company_id UUID
	 * @param string $id UUID
	 * @return bool|string
	 */
	function getStoragePath( $company_id = NULL, $id = NULL ) {
		if ( $company_id == '' ) {
			if ( is_object( $this->getLegalEntityObject() ) ) {
				$company_id = $this->getLegalEntityObject()->getCompany();
			}
		}

		if ( $company_id == '' ) {
			return FALSE;
		}

		return Environment::getStorageBasePath() . DIRECTORY_SEPARATOR .'remittance_source_account'. DIRECTORY_SEPARATOR . $company_id;
	}

	/**
	 * @param bool $ignore_warning
	 * @return bool
	 */
	function Validate( $ignore_warning = TRUE ) {
		//
		// BELOW: Validation code moved from set*() functions.
		//
		// Legal entity
		if ( $this->getLegalEntity() !== FALSE AND $this->getLegalEntity() != TTUUID::getNotExistID() ) {
			$llf = TTnew( 'LegalEntityListFactory' ); /** @var LegalEntityListFactory $llf */
			$this->Validator->isResultSetWithRows( 'legal_entity_id',
												   $llf->getByID( $this->getLegalEntity() ),
												   TTi18n::gettext( 'Legal entity is invalid' )
			);
		}

		//When using FairnessTNA EFT service, all source accounts must be directly assigned to a legal entity.
		if ( $this->getType() == 3000 AND $this->getDataFormat() == 5 AND $this->getLegalEntity() == TTUUID::getNotExistID() ) {
			$this->Validator->isTrue(		'legal_entity_id',
											 FALSE,
											 TTi18n::gettext('Legal Entity must be specified')
			);
		}


		// Currency
		if ( $this->getCurrency() !== FALSE ) {
			$culf = TTnew( 'CurrencyListFactory' ); /** @var CurrencyListFactory $culf */
			$this->Validator->isResultSetWithRows(	'currency_id',
															$culf->getByID($this->getCurrency()),
															TTi18n::gettext('Invalid Currency')
														);
		}
		// Status
		if ( $this->getStatus() !== FALSE ) {
			$this->Validator->inArrayKey( 'status_id',
										  $this->getStatus(),
										  TTi18n::gettext( 'Incorrect Status' ),
										  $this->getOptions( 'status' )
			);
		}
		// Type
		if ( $this->getType() !== FALSE ) {
			$this->Validator->inArrayKey( 'type_id',
										  $this->getType(),
										  TTi18n::gettext( 'Incorrect Type' ),
										  $this->getOptions( 'type' )
			);
		}
		// Country
		if ( $this->getCountry() !== FALSE ) {
			$this->Validator->inArrayKey(	'country',
													$this->getCountry(),
													TTi18n::gettext('Incorrect Country'),
													$this->getOptions('country')
												);
		}
		// Data format
		if ( $this->getDataFormat() !== FALSE ) {
			$this->Validator->inArrayKey(	'data_format_id',
													$this->getDataFormat(),
													TTi18n::gettext('Incorrect data format'),
													$this->getOptions('data_format', array( 'type_id' => $this->getType(), 'country' => $this->getCountry() ) )
												);
		}
		// Name
		if ( $this->getName() !== FALSE AND $this->getName() != '' ) {
			$this->Validator->isLength(	'name',
												$this->getName(),
												TTi18n::gettext('Name is too short or too long'),
												2,
												100
											);
			if ( $this->Validator->isError('name') == FALSE ) {
				$this->Validator->isTrue(		'name',
														$this->isUniqueName( $this->getName() ),
														TTi18n::gettext('Name already exists')
													);
			}
		}
		// Description
		$this->Validator->isLength(	'description',
											$this->getDescription(),
											TTi18n::gettext('Description is invalid'),
											0, 255
										);
		// Value 1
		if ( $this->getValue1() != '' ) {
			$this->Validator->isLength(	'value1',
											$this->getValue1(),
											TTi18n::gettext('Value 1 is invalid'),
											1, 255
										);
		}
		// Value 2
		if ( $this->getValue2() != '' ) {
			$this->Validator->isLength(	'value2',
											$this->getValue2(),
											TTi18n::gettext('Value 2 is invalid'),
											1, 255
										);
		}
		// Value 4
		if ( $this->getValue4() != '' ) {
			$this->Validator->isLength(	'value4',
											$this->getValue4(),
											TTi18n::gettext('Value 4 is invalid'),
											1, 255
										);
		}
		// Value 5
		if ( $this->getValue5() != '' ) {
			$this->Validator->isLength(	'value5',
											$this->getValue5(),
											TTi18n::gettext('Value 5 is invalid'),
											1, 255
										);
		}
		// Value 6
		if ( $this->getValue6() != '' ) {
			$this->Validator->isLength(	'value6',
											$this->getValue6(),
											TTi18n::gettext('Value 6 is invalid'),
											1, 255
										);
		}
		// Value 7
		if ( $this->getValue7() != '' ) {
			$this->Validator->isLength(	'value7',
											$this->getValue7(),
											TTi18n::gettext('Value 7 is invalid'),
											1, 255
										);
		}
		// Value 8
		if ( $this->getValue8() != '' ) {
			$this->Validator->isLength(	'value8',
											$this->getValue8(),
											TTi18n::gettext('Value 8 is invalid'),
											1, 255
										);
		}
		// Value 9
		if ( $this->getValue9() != '' ) {
			$this->Validator->isLength(	'value9',
											$this->getValue9(),
											TTi18n::gettext('Value 9 is invalid'),
											1, 255
										);
		}
		// Value 10
		if ( $this->getValue10() != '' ) {
			$this->Validator->isLength(	'value10',
											$this->getValue10(),
											TTi18n::gettext('Value 10 is invalid'),
											1, 255
										);
		}
		// Value 11
		if ( $this->getValue11() != '' ) {
			$this->Validator->isLength(	'value11',
											$this->getValue11(),
											TTi18n::gettext('Value 11 is invalid'),
											1, 255
										);
		}
		// Value 12
		if ( $this->getValue12() != '' ) {
			$this->Validator->isLength(	'value12',
											$this->getValue12(),
											TTi18n::gettext('Value 12 is invalid'),
											1, 255
										);
		}
		// Value 13
		if ( $this->getValue13() != '' ) {
			$this->Validator->isLength(	'value13',
											$this->getValue13(),
											TTi18n::gettext('Value 13 is invalid'),
											1, 255
										);
		}
		// Value 14
		if ( $this->getValue14() != '' ) {
			$this->Validator->isLength(	'value14',
											$this->getValue14(),
											TTi18n::gettext('Value 14 is invalid'),
											1, 255
										);
		}
		// Value 15
		if ( $this->getValue15() != '' ) {
			$this->Validator->isLength(	'value15',
											$this->getValue15(),
											TTi18n::gettext('Value 15 is invalid'),
											1, 255
										);
		}
		// Value 16
		if ( $this->getValue16() != '' ) {
			$this->Validator->isLength(	'value16',
											$this->getValue16(),
											TTi18n::gettext('Value 16 is invalid'),
											1, 255
										);
		}
		// Value 17
		if ( $this->getValue17() != '' ) {
			$this->Validator->isLength(	'value17',
											$this->getValue17(),
											TTi18n::gettext('Value 17 is invalid'),
											1, 255
										);
		}
		// Value 18
		if ( $this->getValue18() != '' ) {
			$this->Validator->isLength(	'value18',
											$this->getValue18(),
											TTi18n::gettext('Value 18 is invalid'),
											1, 255
										);
		}
		// Value 19
		if ( $this->getValue19() != '' ) {
			$this->Validator->isLength(	'value19',
											$this->getValue19(),
											TTi18n::gettext('Value 19 is invalid'),
											1, 255
										);
		}
		// Value 20
		if ( $this->getValue20() != '' ) {
			$this->Validator->isLength(	'value20',
											$this->getValue20(),
											TTi18n::gettext('Value 20 is invalid'),
											1, 255
										);
		}
		// Value 21
		if ( $this->getValue21() != '' ) {
			$this->Validator->isLength(	'value21',
											$this->getValue21(),
											TTi18n::gettext('Value 21 is invalid'),
											1, 255
										);
		}
		// Value 22
		if ( $this->getValue22() != '' ) {
			$this->Validator->isLength(	'value22',
											$this->getValue22(),
											TTi18n::gettext('Value 22 is invalid'),
											1, 255
										);
		}
		// Value 23
		if ( $this->getValue23() != '' ) {
			$this->Validator->isLength(	'value23',
											$this->getValue23(),
											TTi18n::gettext('Value 23 is invalid'),
											1, 255
										);
		}
		// Value 24
		if ( $this->getValue24() != '' ) {
			$this->Validator->isLength(	'value24',
											$this->getValue24(),
											TTi18n::gettext('Value 24 is invalid'),
											1, 255
										);
		}
		// Value 25
		if ( $this->getValue25() != '' ) {
			$this->Validator->isLength(	'value25',
											$this->getValue25(),
											TTi18n::gettext('Value 25 is invalid'),
											1, 255
										);
		}
		// Value 26
		if ( $this->getValue26() != '' ) {
			$this->Validator->isLength(	'value26',
											$this->getValue26(),
											TTi18n::gettext('Value 26 is invalid'),
											1, 255
										);
		}
		// Value 27
		if ( $this->getValue27() != '' ) {
			$this->Validator->isLength(	'value27',
											$this->getValue27(),
											TTi18n::gettext('Value 27 is invalid'),
											1, 255
										);
		}
		// Value 28
		if ( $this->getValue28() != '' ) {
			$this->Validator->isLength(	'value28',
											$this->getValue28(),
											TTi18n::gettext('Value 28 is invalid'),
											1, 255
										);
		}
		// Value 29
		if ( $this->getValue29() != '' ) {
			$this->Validator->isLength(	'value29',
											$this->getValue29(),
											TTi18n::gettext('Value 29 is invalid'),
											1, 255
										);
		}
		// Value 30
		if ( $this->getValue30() != '' ) {
			$this->Validator->isLength(	'value30',
											$this->getValue30(),
											TTi18n::gettext('Value 30 is invalid'),
											1, 255
										);
		}
		//
		// ABOVE: Validation code moved from set*() functions.
		//

		//Linked remittance destination records need to be checked in multiple places.
		$linked_remittance_destination_records = 0;
		$rdalf = TTnew( 'RemittanceDestinationAccountListFactory'); /** @var RemittanceDestinationAccountListFactory $rdalf */
		$rdalf->getByRemittanceSourceAccountId( $this->getId(), 1 ); //limit 1.
		if ( $rdalf->getRecordCount() > 0 ) {
			$linked_remittance_destination_records = $rdalf->getRecordCount();
		}
		unset($rdalf);

		$data_diff = $this->getDataDifferences();

		//$this->setProvince( $this->getProvince() ); //Not sure why this was there, but it causes duplicate errors if the province is incorrect.
		if ( $this->getDeleted() == TRUE ) {
			if ( $linked_remittance_destination_records > 0 ) {
				$this->Validator->isTRUE(	'in_use',
											FALSE,
											TTi18n::gettext('This remittance source account is currently in use') .' '. TTi18n::gettext('by employee payment methods') );
			}

			$pralf = TTnew( 'PayrollRemittanceAgencyListFactory' ); /** @var PayrollRemittanceAgencyListFactory $pralf */
			$pralf->getByRemittanceSourceAccountId( $this->getId(), 1 ); //limit 1.
			if ( $pralf->getRecordCount() > 0 ) {
				$this->Validator->isTRUE(	'in_use',
											FALSE,
											TTi18n::gettext('This remittance source account is currently in use') .' '. TTi18n::gettext('by remittance agencies') );
			}

			$pstlf = TTnew( 'PayStubTransactionListFactory' ); /** @var PayStubTransactionListFactory $pstlf */
			$pstlf->getByRemittanceSourceAccountId( $this->getId(), 1 ); //limit 1.
			if ( $pstlf->getRecordCount() > 0 ) {
				$this->Validator->isTRUE(	'in_use',
											FALSE,
											TTi18n::gettext('This remittance source account is currently in use') .' '. TTi18n::gettext('by pay stub transactions') );
			}
		}

		if ( $this->getStatus() == 10 ) { //10=Enabled - Only validate when status is enabled, so records that are invalid but used in the past can always be disabled.
			if ( $this->getType() == 2000 ) {
				// when type is CHECK
				if ( $this->getLastTransactionNumber() !== FALSE ) {
					$value = $this->Validator->stripNonNumeric( $this->getLastTransactionNumber() );
					$this->Validator->isFloat(
							'last_transaction_number',
							$value,
							TTi18n::gettext( 'Incorrect last check number' ) );
				}

			} elseif ( $this->getType() == 3000 AND $this->getCountry() == 'US' ) {
				// when type is ACH
				if ( $this->getLastTransactionNumber() !== FALSE ) {
					$value = $this->Validator->stripNonNumeric( $this->getLastTransactionNumber() );
					$this->Validator->isFloat(
							'last_transaction_number',
							$value,
							TTi18n::gettext( 'Incorrect last batch number' ) );
				}
				// Routing number
				if ( $this->getValue2() !== FALSE ) {
					if ( strlen( $this->getValue2() ) != 9 ) {
						$this->Validator->isTrue( 'value2',
												  FALSE,
												  TTi18n::gettext( 'Invalid routing number length' ) );
					} else {
						$this->Validator->isDigits( 'value2',
													 $this->getValue2(),
													 TTi18n::gettext( 'Invalid routing number, must be digits only' ) );
					}
				}
				// Account number
				if ( $this->getValue3() !== FALSE ) {
					if ( strlen( $this->getValue3() ) < 3 OR strlen( $this->getValue3() ) > 17 ) {
						$this->Validator->isTrue( 'value3',
												  FALSE,
												  TTi18n::gettext( 'Invalid account number length' ) );
					} else {
						$this->Validator->isDigits( 'value3',
													 $this->getValue3(),
													 TTi18n::gettext( 'Invalid account number, must be digits only' ) );
					}
				}

				//Not all companies have this specified and it causes problems during upgrade.
//			if ( $this->getValue4() == '' ) {
//				$this->Validator->isTrue(		'value4',
//												FALSE,
//												TTi18n::gettext('Business Number not specified'));
//			}
//
//			if ( $this->getValue5() == '' ) {
//				$this->Validator->isTrue(		'value5',
//												FALSE,
//												TTi18n::gettext('Immediate origin not specified'));
//			}
//
//			if ( $this->getValue7() == '' ) {
//				$this->Validator->isTrue(		'value7',
//												FALSE,
//												TTi18n::gettext('Immediate destination not specified'));
//			}
			} elseif ( $this->getType() == 3000 AND $this->getCountry() == 'CA' ) {
				// when type is EFT
				if ( $this->getLastTransactionNumber() !== FALSE ) {
					$this->Validator->isFloat(
							'last_transaction_number',
							$this->Validator->stripNonNumeric( $this->getLastTransactionNumber() ),
							TTi18n::gettext( 'Incorrect last batch number' ) );
				}
				// Institution number
				if ( $this->getValue1() !== FALSE ) {
					if ( strlen( $this->getValue1() ) != 3 ) {
						$this->Validator->isTrue( 'value1',
												  FALSE,
												  TTi18n::gettext( 'Invalid institution number length' ) );
					} else {
						$this->Validator->isDigits( 'value1',
													$this->getValue1(),
													TTi18n::gettext( 'Invalid institution number, must be digits only' ) );
					}
				}
				// Transit number
				if ( $this->getValue2() !== FALSE ) {
					if ( strlen( $this->getValue2() ) != 5 ) {
						$this->Validator->isTrue( 'value2',
												  FALSE,
												  TTi18n::gettext( 'Invalid transit number length' ) );
					} else {
						$this->Validator->isDigits( 'value2',
													 $this->getValue2(),
													 TTi18n::gettext( 'Invalid transit number, must be digits only' ) );
					}
				}
				// Account number
				if ( $this->getValue3() !== FALSE ) {
					if ( strlen( $this->getValue3() ) < 3 OR strlen( $this->getValue3() ) > 12 ) {
						$this->Validator->isTrue( 'value3',
												  FALSE,
												  TTi18n::gettext( 'Invalid account number length' ) );
					} else {
						$this->Validator->isDigits( 'value3',
													 $this->getValue3(),
													 TTi18n::gettext( 'Invalid account number, must be digits only' ) );
					}
				}

				//Not all companies have this specified and it causes problems during upgrade.
//			if ( $this->getValue5() == '' ) {
//				$this->Validator->isTrue(		'value5',
//												FALSE,
//												TTi18n::gettext('Originator ID not specified'));
//			}
//
//			if ( $this->getValue7() == '' ) {
//				$this->Validator->isTrue(		'value7',
//												FALSE,
//												TTi18n::gettext('Data center not specified'));
//			}
			}
		}

		//Make sure the name does not contain the account number for security reasons.
		$this->Validator->isTrue(		'name',
				( ( stripos( $this->Validator->stripNonNumeric( $this->getName() ), $this->getValue3() ) !== FALSE ) ? FALSE : TRUE ),
										 TTi18n::gettext('Account number must not be a part of the Name') );

		//Make sure the description does not contain the account number for security reasons.
		$this->Validator->isTrue(		'description',
				( ( stripos( $this->Validator->stripNonNumeric( $this->getDescription() ), $this->getValue3() ) !== FALSE ) ? FALSE : TRUE ),
										 TTi18n::gettext('Account number must not be a part of the Description') );

		//Don't allow type to be changed if its already in use. It also prevents further errors when trying to edit/delete destination records where a type mismatch occurs.
		if ( is_array($data_diff) AND $this->isDataDifferent( 'type_id', $data_diff ) AND $linked_remittance_destination_records > 0 ) { //Type has changed
			$this->Validator->isTRUE(	'type_id',
										 FALSE,
										 TTi18n::gettext( 'This remittance source account is currently in use by employee payment methods of a different type' ) );
		}

		if ( is_array($data_diff) AND $this->isDataDifferent( 'legal_entity_id', $data_diff ) ) { //Legal entity has changed
			//Cases to handle:
			//  Always allow going from a specific legal entity to ANY without any additional validation checks.
			//  Switching from a specific legal entity to another specific legal entity should check that destination accounts aren't assigned.
			//  Switching from ANY legal enity to any specific legal entity, should ensure that all destination accounts are assigned to the same legal entity.
			$rdalf = TTnew( 'RemittanceDestinationAccountListFactory' ); /** @var RemittanceDestinationAccountListFactory $rdalf */

			if ( $this->getLegalEntity() != TTUUID::getNotExistID() AND $data_diff['legal_entity_id'] != TTUUID::getNotExistID() ) { //Switching from any specific legal entity to any other specific legal entity.
				$rdalf->getByRemittanceSourceAccountId( $this->getId(), 1 ); //Limit 1.
				if ( $rdalf->getRecordCount() > 0 ) {
					$this->Validator->isTrue( 'legal_entity_id',
											  FALSE,
											  TTi18n::gettext( 'This remittance source account is currently in use by employee payment methods' ) );
				}
			} elseif ( $this->getLegalEntity() != TTUUID::getNotExistID() AND $data_diff['legal_entity_id'] == TTUUID::getNotExistID() ) { //Switching from ANY legal entity to a specific legal entity.
				//Make sure all destination accounts users are assigned to the same legal entity and they are trying to switch to.
				$rdalf->getByRemittanceSourceAccountIdAndNotUserLegalEntityId( $this->getId(), $this->getLegalEntity(), 1 ); //Limit 1
				if ( $rdalf->getRecordCount() > 0 ) {
					foreach( $rdalf as $rda_obj ) {
							$this->Validator->isTrue( 'legal_entity_id',
													  FALSE,
													  TTi18n::gettext( 'This remittance source account is currently in use by employee payment methods assigned to a different legal entity. (%1)', $rda_obj->getUserObject()->getFullName() ) );
							break;
					}
				}
			}

			unset( $rdalf );
		}

		if ( is_array($data_diff) AND $this->isDataDifferent( 'country', $data_diff ) ) { //Country has changed
			//Cases to handle:
			//  Don't allow changing the country if destination accounts are linked to it already, as that will change the bank account validations and such.
			$rdalf = TTnew( 'RemittanceDestinationAccountListFactory' ); /** @var RemittanceDestinationAccountListFactory $rdalf */
			$rdalf->getByRemittanceSourceAccountId( $this->getId(), 1 ); //Limit 1.
			if ( $rdalf->getRecordCount() > 0 ) {
				$this->Validator->isTrue( 'country',
										  FALSE,
										  TTi18n::gettext( 'This remittance source account is currently in use by employee payment methods in a different country' ) );
			}
			unset( $rdalf );
		}

		//Make sure these fields are always specified, but don't break mass edit.
		if ( $this->Validator->getValidateOnly() == FALSE AND $this->getLegalEntity() != TTUUID::getNotExistID() ) {
			if ( $this->getLegalEntity() == FALSE AND $this->Validator->hasError('legal_entity_id') == FALSE ) {
				$this->Validator->isTrue(		'legal_entity_id',
												FALSE,
												TTi18n::gettext('Please specify a legal entity'));
			}

			if ( $this->getCurrency() == FALSE AND $this->Validator->hasError('currency_id') == FALSE ) {
				$this->Validator->isTrue(		'currency_id',
												FALSE,
												TTi18n::gettext('Please specify a currency'));
			}

			if ( $this->getStatus() == FALSE AND $this->Validator->hasError('status_id') == FALSE ) {
				$this->Validator->isTrue(		'status_id',
												FALSE,
												TTi18n::gettext('Please specify status'));
			}

			if ( $this->getType() == FALSE AND $this->Validator->hasError('type_id') == FALSE ) {
				$this->Validator->isTrue(		'type_id',
												FALSE,
												TTi18n::gettext('Please specify type'));
			}
		}

		if ( $this->getDeleted() != TRUE AND $this->Validator->getValidateOnly() == FALSE ) { //Don't check the below when mass editing.
			if ( $this->getName() == FALSE AND $this->Validator->hasError('name') == FALSE ) {
				$this->Validator->isTrue(		'name',
												FALSE,
												TTi18n::gettext('Please specify a name'));
			}

			if ( $this->getDataFormat() == FALSE ) {
				$this->Validator->isTrue(		'data_format_id',
												FALSE,
												TTi18n::gettext('Please specify data format'));
			}
		}

		if ( $ignore_warning == FALSE AND $this->getDeleted() == FALSE AND $this->getStatus() == 10 AND $this->getType() == 3000 AND $this->getDataFormat() == 5 AND is_object( $this->getLegalEntityObject() ) ) { //3000=EFT/ACH, 5=FairnessTNA EFT
			$le_obj = $this->getLegalEntityObject();

			if ( $le_obj->getPaymentServicesStatus() == 10 ) {
				$this->Validator->isTrue( 'data_format_id',
										  $le_obj->checkPaymentServicesCredentials(),
										  TTi18n::gettext( 'Payment Services User Name or API Key is incorrect, or service not activated' ) );
			} else {
				$this->Validator->isTrue( 'data_format_id',
										  FALSE,
										  TTi18n::gettext( 'Payment Services are not enabled for this Legal Entity' ) );
			}

			if ( PRODUCTION == TRUE AND is_object( $le_obj ) AND $le_obj->getPaymentServicesStatus() == 10 AND $le_obj->getPaymentServicesUserName() != '' AND $le_obj->getPaymentServicesAPIKey() != '' ) { //10=Enabled
				try {
					$tt_ps_api = $le_obj->getPaymentServicesAPIObject();
					$retval = $tt_ps_api->validateBankAccount( $tt_ps_api->convertRemittanceSourceAccountObjectToBankAccountArray( $this ) );
					if ( is_object($retval) AND $retval->isValid() === FALSE ) {
						Debug::Text( 'ERROR! Unable to validate remittance destination account data through Payment Services API... (a)', __FILE__, __LINE__, __METHOD__, 10 );
						$api_f = new APIRemittanceDestinationAccount();
						$validation_arr = $api_f->convertAPIReturnHandlerToValidatorObject( $retval->getResultData() );

						$this->Validator->merge( $validation_arr );
					}
				} catch ( Exception $e ) {
					Debug::Text( 'ERROR! Unable to validate remittance destination account  data... (b) Exception: ' . $e->getMessage(), __FILE__, __LINE__, __METHOD__, 10 );
				}
			} else {
				Debug::Text( 'Payment Services not enabled in legal entity...', __FILE__, __LINE__, __METHOD__, 10 );
			}
		}

		return TRUE;
	}

	/**
	 * @return bool
	 */
	function preSave() {
		return TRUE;
	}

	/**
	 * @return bool
	 */
	function postSave() {
		$this->removeCache( $this->getId() );

		if ( $this->getType() == 3000 AND $this->getDataFormat() == 5 ) { //3000=EFT/ACH, 5=FairnessTNA EFT
			//Send data to FairnessTNA Payment Services.
			$le_obj = $this->getLegalEntityObject();
			if ( PRODUCTION == TRUE AND is_object( $le_obj ) AND $le_obj->getPaymentServicesStatus() == 10 AND $le_obj->getPaymentServicesUserName() != '' AND $le_obj->getPaymentServicesAPIKey() != '' ) { //10=Enabled
				try {
					$tt_ps_api = $le_obj->getPaymentServicesAPIObject();
					$retval = $tt_ps_api->setRemittanceSourceAccount( $tt_ps_api->convertRemittanceSourceAccountObjectToBankAccountArray( $this ) );
					if ( $retval === FALSE ) {
						Debug::Text( 'ERROR! Unable to upload remittance source account data... (a)', __FILE__, __LINE__, __METHOD__, 10 );

						return FALSE;
					}
				} catch ( Exception $e ) {
					Debug::Text( 'ERROR! Unable to upload remittance source account data... (b) Exception: ' . $e->getMessage(), __FILE__, __LINE__, __METHOD__, 10 );
				}
			} else {
				Debug::Text( 'ERROR! Payment Services not enable in legal entity!', __FILE__, __LINE__, __METHOD__, 10 );
			}
		}

		return TRUE;
	}

	/**
	 * @param $data
	 * @return bool
	 */
	function setObjectFromArray( $data ) {
		if ( is_array( $data ) ) {
			$variable_function_map = $this->getVariableToFunctionMap();
			foreach( $variable_function_map as $key => $function ) {
				if ( isset($data[$key]) ) {

					$function = 'set'.$function;
					switch( $key ) {
						default:
							if ( method_exists( $this, $function ) ) {
								$this->$function( $data[$key] );
							}
							break;
					}
				}
			}

			$this->setCreatedAndUpdatedColumns( $data );

			return TRUE;
		}

		return FALSE;
	}

	/**
	 * @param null $include_columns
	 * @return array
	 */
	function getObjectAsArray( $include_columns = NULL ) {
		$variable_function_map = $this->getVariableToFunctionMap();
		$data = array();
		if ( is_array( $variable_function_map ) ) {
			foreach( $variable_function_map as $variable => $function_stub ) {
				if ( $include_columns == NULL OR ( isset($include_columns[$variable]) AND $include_columns[$variable] == TRUE ) ) {

					$function = 'get'.$function_stub;
					switch( $variable ) {
						case 'legal_name':
						case 'currency':
							$data[$variable] = $this->getColumn( $variable );
							break;
						case 'type':
						case 'status':
							$function = 'get'.$variable;
							if ( method_exists( $this, $function ) ) {
								$data[$variable] = Option::getByKey( $this->$function(), $this->getOptions( $variable ) );
							}
							break;
						case 'data_format':
							$data[$variable] = Option::getByKey( $this->getDataFormat(), $this->getOptions( $variable, array( 'type_id' => $this->getType() ) ) );
							break;
						case 'in_use':
							$data[$variable] = $this->getColumn( $variable );
							break;
						case 'value3': //Account Number
							$data[$variable] = $this->getSecureValue3();
							break;
						case 'value28': //Return Account Number
							$data[$variable] = $this->getSecureValue28();
							break;
						default:
							if ( method_exists( $this, $function ) ) {
								$data[$variable] = $this->$function();
							}
							break;
					}

				}
			}
			$this->getCreatedAndUpdatedColumns( $data, $include_columns );
		}

		return $data;
	}

	/**
	 * @param $lf
	 * @param bool $include_blank
	 * @return array|bool
	 */
	function getArrayByListFactory( $lf, $include_blank = TRUE ) {
		if ( !is_object($lf) ) {
			return FALSE;
		}

		$list = array();
		if ( $include_blank == TRUE ) {
			$list[TTUUID::getZeroID()] = '--';
		}

		foreach ($lf as $obj) {
			$list[$obj->getID()] = $obj->getName();
		}

		if ( empty($list) == FALSE ) {
			return $list;
		}

		return FALSE;
	}

	/**
	 * @param $log_action
	 * @return bool
	 */
	function addLog( $log_action ) {
		return TTLog::addEntry( $this->getId(), $log_action, TTi18n::getText('Remittance source account') .': '. $this->getName(), NULL, $this->getTable(), $this );
	}

}
?>
