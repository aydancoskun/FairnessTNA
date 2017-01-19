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
 * $Revision: 2196 $
 * $Id: User.class.php 2196 2008-10-14 16:08:54Z ipso $
 * $Date: 2008-10-14 09:08:54 -0700 (Tue, 14 Oct 2008) $
 */

/**
 * @package API\Core
 */
class APITest extends APIFactory {
	protected $main_class = '';

	public function __construct() {
		parent::__construct(); //Make sure parent constructor is always called.

		return TRUE;
	}

	function HelloWorld( $test ) {
		return "You said: $test";
	}

	function delay( $seconds = 10 ) {
		Debug::text('delay: '. $seconds, __FILE__, __LINE__, __METHOD__,9);

		sleep( $seconds );
		return TRUE;
	}

	function getDataGridData() {
		$retarr = array(
						array(
							'first_name' => 'Jane',
							'last_name' => 'Doe',
						),
						array(
							'first_name' => 'John',
							'last_name' => 'Doe',
						),
						array(
							'first_name' => 'Ben',
							'last_name' => 'Smith',
						),

						);

		return $retarr;

	}

	//Return large dataset to test performance.
	function getLargeDataSet( $max_size = 100, $delay = 100000, $progress_bar_id = NULL) {
		if ( $max_size > 9999 ) {
			$max_size = 9999;
		}

		if ( $progress_bar_id == '' ) {
			$progress_bar_id = $this->getAMFMessageID();
		}

		$this->getProgressBarObject()->start( $progress_bar_id, $max_size );

		$retarr = array();
		for($i=1; $i <= $max_size; $i++ ) {
			$retarr[] = array('foo1' => 'bar1', 'foo2' => 'bar2', 'foo3' => 'bar3');
			usleep( $delay );
			$this->getProgressBarObject()->set( $progress_bar_id, $i );
		}

		$this->getProgressBarObject()->stop( $progress_bar_id );
		return $retarr;
	}

	//Date test, since Flex doesn't handle timezones very well, run tests to ensure things are working correctly.
	function dateTest( $test = 1 ) {

		switch ( $test ) {
			case 1:
				$retarr = array(
								strtotime('30-Oct-09 5:00PM') => TTDate::getDBTimeStamp( strtotime('30-Oct-09 5:00PM') ),
								strtotime('31-Oct-09 5:00PM') => TTDate::getDBTimeStamp( strtotime('31-Oct-09 5:00PM') ),
								strtotime('01-Nov-09 5:00PM') => TTDate::getDBTimeStamp( strtotime('01-Nov-09 5:00PM') ),
								strtotime('02-Nov-09 5:00PM') => TTDate::getDBTimeStamp( strtotime('02-Nov-09 5:00PM') ),
								);

				break;
			case 2:
				$retarr = array(
								strtotime('30-Oct-09 5:00PM') => TTDate::getFlexTimeStamp( strtotime('30-Oct-09 5:00PM') ),
								strtotime('31-Oct-09 5:00PM') => TTDate::getFlexTimeStamp( strtotime('31-Oct-09 5:00PM') ),
								strtotime('01-Nov-09 5:00PM') => TTDate::getFlexTimeStamp( strtotime('01-Nov-09 5:00PM') ),
								strtotime('02-Nov-09 5:00PM') => TTDate::getFlexTimeStamp( strtotime('02-Nov-09 5:00PM') ),
								);

				break;
		}

		return $retarr;
	}
}
?>
