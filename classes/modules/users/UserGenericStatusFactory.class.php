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
class UserGenericStatusFactory extends Factory {
	protected $table = 'user_generic_status';
	protected $pk_sequence_name = 'user_generic_status_id_seq'; //PK Sequence name
	protected $batch_sequence_name = 'user_generic_status_batch_id_seq'; //PK Sequence name

	protected $batch_id = NULL;
	protected $queue = NULL;
	static protected $static_queue = NULL;


	/**
	 * @param $name
	 * @param null $parent
	 * @return array|null
	 */
	function _getFactoryOptions( $name, $parent = NULL ) {

		$retval = NULL;
		switch( $name ) {
			case 'status':
				$retval = array(
										10 => TTi18n::gettext('Failed'),
										20 => TTi18n::gettext('Warning'),
										//25 => TTi18n::gettext('Notice'), //Friendly than a warning.
										30 => TTi18n::gettext('Success'),
									);
				break;
			case 'columns':
				$retval = array(
										'-1010-label' => TTi18n::gettext('Label'),
										'-1020-status' => TTi18n::gettext('Status'),
										'-1030-description' => TTi18n::gettext('Description'),

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
								'label',
								'status',
								'description',
								);
				break;

		}

		return $retval;
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
	 * @return null|string
	 */
	function getNextBatchID() {
		$this->batch_id = TTUUID::generateUUID();

		return $this->batch_id;
	}

	/**
	 * @return bool|mixed
	 */
	function getBatchID() {
		return $this->getGenericDataValue( 'batch_id' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setBatchID( $value ) {
		//$val = trim($val);
		return $this->setGenericDataValue( 'batch_id', $value );
	}

	/**
	 * @return bool|int
	 */
	function getStatus() {
		return $this->getGenericDataValue( 'status_id' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setStatus( $value ) {
		$value = (int)trim($value);
		return $this->setGenericDataValue( 'status_id', $value );
	}

	/**
	 * @return bool|mixed
	 */
	function getLabel() {
		return $this->getGenericDataValue( 'label' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setLabel( $value ) {
		$value = trim($value);
		return $this->setGenericDataValue( 'label', $value );
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
	function setDescription( $value ) {
		$value = trim($value);
		return $this->setGenericDataValue( 'description', $value );
	}

	/**
	 * @return bool|mixed
	 */
	function getLink() {
		return $this->getGenericDataValue( 'link' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setLink( $value ) {
		$value = trim($value);
		return $this->setGenericDataValue( 'link', $value );
	}

	//Static Queue functions

	/**
	 * @return bool
	 */
	static function isStaticQueue() {
		if ( is_array( self::$static_queue ) AND count(self::$static_queue) > 0 ) {
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * @return null
	 */
	static function getStaticQueue() {
		return self::$static_queue;
	}

	/**
	 * @return bool
	 */
	static function clearStaticQueue() {
		self::$static_queue = NULL;

		return TRUE;
	}

	/**
	 * @param $label
	 * @param $status
	 * @param null $description
	 * @param null $link
	 * @return bool
	 */
	static function queueGenericStatus( $label, $status, $description = NULL, $link = NULL ) {
		Debug::Text('Add Generic Status row to queue... Label: '. $label .' Status: '. $status, __FILE__, __LINE__, __METHOD__, 10);
		$arr = array(
					'label' => $label,
					'status' => $status,
					'description' => $description,
					'link' => $link
					);

		self::$static_queue[] = $arr;

		return TRUE;
	}


	//Non-Static Queue functions

	/**
	 * @param $queue
	 * @return bool
	 */
	function setQueue( $queue ) {
		$this->queue = $queue;

		UserGenericStatusFactory::clearStaticQueue();

		return TRUE;
	}

	/**
	 * @return bool
	 */
	function saveQueue() {
		if ( is_array($this->queue) ) {
			Debug::Arr($this->queue, 'Generic Status Queue', __FILE__, __LINE__, __METHOD__, 10);
			foreach( $this->queue as $key => $queue_data ) {

				$ugsf = TTnew( 'UserGenericStatusFactory' ); /** @var UserGenericStatusFactory $ugsf */
				$ugsf->setUser( $this->getUser() );
				if ( TTUUID::isUUID( $this->getBatchId() ) AND $this->getBatchID() != TTUUID::getZeroID() AND $this->getBatchID() != TTUUID::getNotExistID() ) {
					$ugsf->setBatchID( $this->getBatchID() );
				} else {
					$this->setBatchId( $this->getNextBatchId() );
				}

				$ugsf->setLabel( $queue_data['label'] );
				$ugsf->setStatus( $queue_data['status'] );
				$ugsf->setDescription( $queue_data['description'] );
				$ugsf->setLink( $queue_data['link'] );

				if ( $ugsf->isValid() ) {
					$ugsf->Save();

					unset($this->queue[$key]);
				}
			}

			return TRUE;
		}

		Debug::Text('Generic Status Queue Empty', __FILE__, __LINE__, __METHOD__, 10);
		return FALSE;
	}

	/*
	function addGenericStatus($label, $status, $description = NULL, $link = NULL ) {
		$this->setLabel( $label );
		$this->setStatus( $status );
		$this->setDescription( $description );
		$this->setLink( $link );

		$batch_id = $this->getBatchId();
		$user_id = $this->getUser();

		if ( $this->isValid() ) {
			$this->Save();

			$this->setBatchId( $batch_id );
			$this->setUser( $user_id );

			return TRUE;
		}

		return FALSE;
	}
	*/
	/**
	 * @return bool
	 */
	function Validate() {
		//
		// BELOW: Validation code moved from set*() functions.
		//
		// User
		$ulf = TTnew( 'UserListFactory' ); /** @var UserListFactory $ulf */
		$this->Validator->isResultSetWithRows(	'user',
														$ulf->getByID($this->getUser()),
														TTi18n::gettext('Invalid Employee')
													);
		// Batch ID
		$this->Validator->isUUID(	'batch_id',
											$this->getBatchID(),
											TTi18n::gettext('Invalid Batch ID')
										);
		// Status
		$this->Validator->inArrayKey(	'status',
											$this->getStatus(),
											TTi18n::gettext('Incorrect Status'),
											$this->getOptions('status')
										);
		// Label
		$this->Validator->isLength(	'label',
											$this->getLabel(),
											TTi18n::gettext('Invalid label'),
											1, 1024
										);
		// Description
		if ( $this->getDescription() != '' ) {
			$this->Validator->isLength(	'description',
												$this->getDescription(),
												TTi18n::gettext('Invalid description'),
												1, 1024
											);
		}
		// Link
		if ( $this->getLink() != '' ) {
			$this->Validator->isLength(	'link',
												$this->getLink(),
												TTi18n::gettext('Invalid link'),
												1, 1024
											);
		}

		//
		// ABOVE: Validation code moved from set*() functions.
		//
		return TRUE;
	}

	/**
	 * @return bool
	 */
	function preSave() {
		return TRUE;
	}
}
?>
