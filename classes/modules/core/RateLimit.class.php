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
 * @package Core
 */

class RateLimit {
	protected $sleep = FALSE; //When rate limit is reached, do we sleep or return FALSE?

	protected $id = 1;
	protected $group = 'rate_limit';

	protected $allowed_calls = 25;
	protected $time_frame = 60; //1 minute.

	protected $memory = NULL;

	/**
	 * RateLimit constructor.
	 */
	function __construct() {
		try {
			$this->memory = new SharedMemory();
			return TRUE;
		} catch ( Exception $e ) {
			Debug::text('ERROR: Caught Exception: '. $e->getMessage(), __FILE__, __LINE__, __METHOD__, 9);
			return FALSE;
		}
	}

	/**
	 * @return int
	 */
	function getID() {
		return $this->id;
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setID( $value) {
		if ( $value != '' ) {
			$this->id = $value;

			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Define the number of calls to check() allowed over a given time frame.
	 * @return int
	 */
	function getAllowedCalls() {
		return $this->allowed_calls;
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setAllowedCalls( $value) {
		if ( $value != '' ) {
			$this->allowed_calls = $value;

			return TRUE;
		}

		return FALSE;
	}

	/**
	 * @return int
	 */
	function getTimeFrame() {
		return $this->time_frame;
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setTimeFrame( $value) {
		if ( $value != '' ) {
			$this->time_frame = $value;

			return TRUE;
		}

		return FALSE;
	}

	/**
	 * @param $data
	 * @return bool
	 */
	function setRateData( $data ) {
		if ( is_object($this->memory) ) {
			try {
				return $this->memory->set( $this->group.$this->getID(), $data );
			} catch ( Exception $e ) {
				Debug::text('ERROR: Caught Exception: '. $e->getMessage(), __FILE__, __LINE__, __METHOD__, 9);
				return FALSE;
			}
		}

		return FALSE;
	}

	/**
	 * @return bool
	 */
	function getRateData() {
		if ( is_object($this->memory) ) {
			try {
				$retarr = $this->memory->get( $this->group.$this->getID() );

				if ( is_object( $retarr ) ) { //Fail OPEN in cases where the user may have deleted the cache directory. This also prevents HTTP 500 errors on windows which are difficult to diagnose.
					Debug::Text( 'ERROR: Shared Memory Failed: ' . $retarr->message .' Cache directory may not exist or has incorrect read/write permissions.', __FILE__, __LINE__, __METHOD__, 10 );
					$retarr = FALSE;
				}

				return $retarr;
			} catch ( Exception $e ) {
				Debug::text('ERROR: Caught Exception: '. $e->getMessage(), __FILE__, __LINE__, __METHOD__, 9);
				return FALSE;
			}
		}

		return FALSE;
	}

	/**
	 * @return bool
	 */
	function getAttempts() {
		$rate_data = $this->getRateData();
		if ( isset($rate_data['attempts']) ) {
			return $rate_data['attempts'];
		}

		return FALSE;
	}

	/**
	 * @return bool
	 */
	function check() {
		if ( $this->getID() != '' ) {
			$rate_data = $this->getRateData();
			//Debug::Arr($rate_data, 'Failed Attempt Data: ', __FILE__, __LINE__, __METHOD__, 10);
			if ( !isset($rate_data['attempts']) ) {
				$rate_data = array(
											'attempts' => 0,
											'first_date' => microtime(TRUE),
											);
			} elseif ( isset($rate_data['attempts']) ) {
				if ( $rate_data['attempts'] > $this->getAllowedCalls() AND $rate_data['first_date'] >= ( microtime(TRUE) - $this->getTimeFrame() ) ) {
					return FALSE;
				} elseif ( $rate_data['first_date'] < ( microtime(TRUE) - $this->getTimeFrame() ) ) {
					$rate_data['attempts'] = 0;
					$rate_data['first_date'] = microtime(TRUE);
				}
			}

			$rate_data['attempts']++;
			$this->setRateData( $rate_data );
			return TRUE; //Don't return result of setRateData() so if it can't write the data to shared memory it fails "OPEN".
		}

		return TRUE; //Return TRUE is no ID is specified, so it fails "OPEN".
	}

	/**
	 * @return bool
	 */
	function delete() {
		if ( is_object($this->memory) ) {
			try {
				return $this->memory->delete( $this->group.$this->getID() );
			} catch ( Exception $e ) {
				Debug::text('ERROR: Caught Exception: '. $e->getMessage(), __FILE__, __LINE__, __METHOD__, 9);
				return FALSE;
			}
		}

		return FALSE;
	}
}
?>