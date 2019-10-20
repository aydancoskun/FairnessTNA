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
 * @package Modules\Users
 */
class UserIdentificationFactory extends Factory {
	protected $table = 'user_identification';
	protected $pk_sequence_name = 'user_identification_id_seq'; //PK Sequence name

	var $user_obj = NULL;

	/**
	 * @param $name
	 * @param null $parent
	 * @return array|null
	 */
	function _getFactoryOptions( $name, $parent = NULL ) {

		$retval = NULL;
		switch( $name ) {
			case 'type':
				$retval = array(
											1	=> TTi18n::gettext('Employee Sequence'), //Company specific employee sequence number, primarily for timeclocks. Should be less than 65535.
											5	=> TTi18n::gettext('Password History'), //Web interface password history
											10	=> TTi18n::gettext('iButton'),
											20	=> TTi18n::gettext('USB Fingerprint'), //Biometric Data -- This is purged when employees are terminated.
											30	=> TTi18n::gettext('Barcode'), //For barcode readers and USB proximity card readers.
											35	=> TTi18n::gettext('QRcode'), //For cameras to read QR code badges.
											40	=> TTi18n::gettext('Proximity Card'), //Mainly for proximity cards on timeclocks.

											//
											//Biometric data -- This is purged when employees are terminated.
											//
											70	=> TTi18n::gettext('Face Image (v1)'), //Raw image of cropped face in as high of quality as possible, and cropped 10-20% larger than the face itself.
											71	=> TTi18n::gettext('Face Image (v2)'), //Raw image of cropped face in as high of quality as possible, and cropped 10-20% larger than the face itself.
											75	=> TTi18n::gettext('Facial Recognition'), //Luxand v5 SDK templates.
											76	=> TTi18n::gettext('Facial Recognition (v2)'), //Luxand v6.1 SDK templates, App v4.0+
											77	=> TTi18n::gettext('Facial Recognition (v3)'), //Luxand v7 SDK templates, App v4.5+
											78	=> TTi18n::gettext('Facial Recognition (v4)'), //Luxand vX SDK templates, App vX.X+ -- Future use.
											//79-90 -- Luxand SDK versions.

											100	=> TTi18n::gettext('TimeClock FingerPrint (v9)'), //TimeClocks v9 algo
											101	=> TTi18n::gettext('TimeClock FingerPrint (v10)'), //TimeClocks v10 algo
											//
											//Biometric data -- This is purged when employees are terminated.
											//

									);
				break;

		}

		return $retval;
	}

	/**
	 * @return null
	 */
	function getUserObject() {
		if ( is_object($this->user_obj) ) {
			return $this->user_obj;
		} else {
			$ulf = TTnew( 'UserListFactory' ); /** @var UserListFactory $ulf */
			$this->user_obj = $ulf->getById( $this->getUser() )->getCurrent();

			return $this->user_obj;
		}
	}

	/**
	 * @return bool|mixed
	 */
	function getUser() {
		return $this->getGenericDataValue( 'user_id' );
	}

	/**
	 * @param string $value UUID
	 * @return bool
	 */
	function setUser( $value ) {
		$value = TTUUID::castUUID( $value );
		return $this->setGenericDataValue( 'user_id', $value );
	}

	/**
	 * @return bool|int
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
		//This needs to be stay as FairnessTNA Client application still uses names rather than IDs.
		$key = Option::getByValue($value, $this->getOptions('type') );
		if ($key !== FALSE) {
			$value = $key;
		}
		return $this->setGenericDataValue( 'type_id', $value );
	}

	/*
		For fingerprints,
			10 = Fingerprint 1	Pass 0.
			11 = Fingerprint 1	Pass 1.
			12 = Fingerprint 1	Pass 2.

			20 = Fingerprint 2	Pass 0.
			21 = Fingerprint 2	Pass 1.
			...
	*/
	/**
	 * @return bool|mixed
	 */
	function getNumber() {
		return $this->getGenericDataValue( 'number' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setNumber( $value) {
		$value = trim($value);
		//Pull out only digits
		$value = $this->Validator->stripNonNumeric($value);
		return $this->setGenericDataValue( 'number', $value );
	}

	/**
	 * @param string $user_id UUID
	 * @param int $type_id
	 * @param $value
	 * @return bool
	 */
	function isUniqueValue( $user_id, $type_id, $value) {
		$ph = array(
					'user_id' => TTUUID::castUUID($user_id),
					'type_id' => (int)$type_id,
					'value' => (string)$value,
					);

		$uf = TTnew( 'UserFactory' ); /** @var UserFactory $uf */

		$query = 'select a.id
					from '. $this->getTable() .' as a,
						'. $uf->getTable() .' as b
					where a.user_id = b.id
						AND b.company_id = ( select z.company_id from '. $uf->getTable() .' as z where z.id = ? and z.deleted = 0 )
						AND a.type_id = ?
						AND a.value = ?
						AND ( a.deleted = 0 AND b.deleted = 0 )';
		$id = $this->db->GetOne($query, $ph);
		//Debug::Arr($id, 'Unique Value: '. $value, __FILE__, __LINE__, __METHOD__, 10);

		if ( $id === FALSE ) {
			return TRUE;
		} else {
			if ($id == $this->getId() ) {
				return TRUE;
			}
		}

		return FALSE;
	}

	/**
	 * @return bool|mixed
	 */
	function getValue() {
		return $this->getGenericDataValue( 'value' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setValue( $value) {
		$value = trim($value);
		return $this->setGenericDataValue( 'value', $value );
	}

	/**
	 * @return bool|mixed
	 */
	function getExtraValue() {
		return $this->getGenericDataValue( 'extra_value' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setExtraValue( $value) {
		$value = trim($value);
		return $this->setGenericDataValue( 'extra_value', $value );
	}

	/**
	 * @param bool $ignore_warning
	 * @return bool
	 */
	function Validate( $ignore_warning = TRUE ) {
		//
		// BELOW: Validation code moved from set*() functions.
		//
		// User
		if ( $this->getUser() != TTUUID::getZeroID() ) {
			$ulf = TTnew( 'UserListFactory' ); /** @var UserListFactory $ulf */
			$this->Validator->isResultSetWithRows(	'user',
															$ulf->getByID($this->getUser()),
															TTi18n::gettext('Invalid Employee')
														);
		}
		// Type
		$this->Validator->inArrayKey(	'type',
												$this->getType(),
												TTi18n::gettext('Incorrect Type'),
												$this->getOptions('type')
											);
		// Number
		$this->Validator->isFloat(	'number',
											$this->getNumber(),
											TTi18n::gettext('Incorrect Number')
										);
		// Value
		$this->Validator->isLength(			'value',
													$this->getValue(),
													TTi18n::gettext('Value is too short or too long'),
													1,
													1024000); //Need relatively large face images.
		// Extra Value
		if ( $this->getExtraValue() !== FALSE ) {
			$this->Validator->isLength(			'extra_value',
														$this->getExtraValue(),
														TTi18n::gettext('Extra Value is too long'),
														1,
												   		1024000
													);
		}

		//
		// ABOVE: Validation code moved from set*() functions.
		//
		if ( $this->getValue() == FALSE ) {
				$this->Validator->isTRUE(			'value',
													FALSE,
													TTi18n::gettext('Value is not defined') );

		} else {
			$this->Validator->isTrue(		'value',
											$this->isUniqueValue( $this->getUser(), $this->getType(), $this->getValue() ),
											TTi18n::gettext('Value is already in use, please enter a different one'));
		}
		return TRUE;
	}

	/**
	 * @return bool
	 */
	function preSave() {
		if (  $this->getNumber() == '' ) {
			$this->setNumber( 0 );
		}

		return TRUE;
	}

	/**
	 * @return bool
	 */
	function postSave() {
		$this->removeCache( $this->getId() );

		return TRUE;
	}

	/**
	 * @param $log_action
	 * @return bool
	 */
	function addLog( $log_action ) {
		//Don't do detail logging for this, as it will store entire figerprints in the log table.
		return TTLog::addEntry( $this->getId(), $log_action, TTi18n::getText('Employee Identification - Employee'). ': '. UserListFactory::getFullNameById( $this->getUser() ) .' '. TTi18n::getText('Type') . ': '. Option::getByKey($this->getType(), $this->getOptions('type') ), NULL, $this->getTable() );
	}
}
?>
