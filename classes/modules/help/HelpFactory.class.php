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
class HelpFactory extends Factory {
	protected $table = 'help';
	protected $pk_sequence_name = 'help_id_seq'; //PK Sequence name


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
										10 => TTi18n::gettext('Form'),
										20 => TTi18n::gettext('Page')
									);
				break;
			case 'status':
				$retval = array(
										10 => TTi18n::gettext('NEW'),
										15 => TTi18n::gettext('Pending Approval'),
										20 => TTi18n::gettext('ACTIVE')
									);
				break;

		}

		return $retval;
	}


	/**
	 * @return int
	 */
	function getType() {
		return (int)$this->getGenericDataValue( 'type_id' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setType( $value) {
		$value = trim($value);

		$key = Option::getByValue($value, $this->getOptions('type') );
		if ($key !== FALSE) {
			$value = $key;
		}

		Debug::Text('bType: '. $value, __FILE__, __LINE__, __METHOD__, 10);
		return $this->setGenericDataValue( 'type_id', $value );
	}

	/**
	 * @return int
	 */
	function getStatus() {
		return (int)$this->getGenericDataValue( 'status_id' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setStatus( $value) {
		$value = trim($value);

		$key = Option::getByValue($value, $this->getOptions('status') );
		if ($key !== FALSE) {
			$value = $key;
		}
		return $this->setGenericDataValue( 'status_id', $value );
	}

	/**
	 * @return mixed
	 */
	function getHeading() {
		return $this->getGenericDataValue( 'heading' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setHeading( $value) {
		$value = trim($value);
		return $this->setGenericDataValue( 'heading', $value );
	}

	/**
	 * @return mixed
	 */
	function getBody() {
		return $this->getGenericDataValue( 'body' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setBody( $value) {
		$value = trim($value);
		return $this->setGenericDataValue( 'body', $value );
	}

	/**
	 * @return mixed
	 */
	function getKeywords() {
		return $this->getGenericDataValue( 'keywords' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setKeywords( $value) {
		$value = trim($value);
		return $this->setGenericDataValue( 'keywords', $value );
	}

	/**
	 * @return bool
	 */
	function getPrivate() {
		return $this->fromBool( $this->getGenericDataValue( 'private' ) );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setPrivate( $value) {
		return $this->setGenericDataValue( 'private', $this->toBool($value) );
	}

	/**
	 * @return bool
	 */
	function Validate() {
		//
		// BELOW: Validation code moved from set*() functions.
		//
		// Type
		$this->Validator->inArrayKey(	'type',
												$this->getType(),
												TTi18n::gettext('Incorrect Type'),
												$this->getOptions('type')
											);
		// Status
		$this->Validator->inArrayKey(	'status',
												$this->getStatus(),
												TTi18n::gettext('Incorrect Status'),
												$this->getOptions('status')
											);
		// Heading length
		if ( $this->getHeading() != NULL ) {
			$this->Validator->isLength(	'heading',
												$this->getHeading(),
												TTi18n::gettext('Incorrect Heading length'),
												2, 255
											);
		}
		// Body
		if ( $this->getBody() != NULL ) {
			$this->Validator->isLength(	'body',
												$this->getBody(),
												TTi18n::gettext('Incorrect Body length'),
												2, 2048
											);
		}
		// Keywords
		if ( $this->getKeywords() != NULL ) {
			$this->Validator->isLength(	'keywords',
												$this->getKeywords(),
												TTi18n::gettext('Incorrect Keywords length'),
												2, 1024
											);
		}

		//
		// ABOVE: Validation code moved from set*() functions.
		//
		return TRUE;
	}

}
?>
