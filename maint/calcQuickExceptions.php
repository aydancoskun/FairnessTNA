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

/*
 * Calculate Exceptions for the previous day. This helps especially for
 * the "Unscheuled Absence" exception.
 *
 * Run this once a day. AFTER AddUserDate
 */
require_once( dirname(__FILE__) . DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'includes'. DIRECTORY_SEPARATOR .'global.inc.php');
require_once( dirname(__FILE__) . DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'includes'. DIRECTORY_SEPARATOR .'CLI.inc.php');

//Debug::setVerbosity(5);
$execution_time = time();


//Calculate exceptions just for today and yesterday, because some shifts may start late in the day and need to be handled first thing in the morning.
//Make sure we also go one day in the future too, since the servers can be PST and if its 11:00PM, it will stop at midnight for that day, so
//shifts that would have already started in a different timezone (say EST) will not receive exceptions until we have moved into the next day for PST (3hrs late)
$start_date = TTDate::getBeginDayEpoch( ( TTDate::getMiddleDayEpoch( $execution_time ) - 86400 ) );
$end_date = TTDate::getEndDayEpoch( ( TTDate::getMiddleDayEpoch( $execution_time ) + 86400 ) );

$flags = array(
				'meal' => FALSE,
				'undertime_absence' => FALSE,
				'break' => FALSE,
				'holiday' => FALSE,
				'schedule_absence' => FALSE,
				'absence' => FALSE,
				'regular' => FALSE,
				'overtime' => FALSE,
				'premium' => FALSE,
				'accrual' => FALSE,

				'exception' => TRUE,
				//Exception options
				'exception_premature' => TRUE, //Calculates premature exceptions
				'exception_future' => FALSE, //Calculates exceptions in the future.

				//Calculate policies for future dates.
				'future_dates' => FALSE, //Calculates dates in the future.
				);

$udtlf = new UserDateTotalListFactory();
//Use optimized query to speed this process up significantly.
$udtlf->getMidDayExceptionsByStartDateAndEndDateAndPayPeriodStatus( $start_date, $end_date, array(10, 12, 15, 30) );
Debug::text(' calcQuickExceptions: Start Date: '. TTDate::getDate('DATE+TIME', $start_date ) .' End Date: '. TTDate::getDate('DATE+TIME', $end_date ) .' Rows: '. $udtlf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 5);
if ( $udtlf->getRecordCount() > 0 ) {
	$i = 0;
	foreach ($udtlf as $udt_obj) {
		Debug::text('('.$i.'). User: '. $udt_obj->getUser() .' Start Date: '. TTDate::getDate('DATE+TIME', strtotime( $udt_obj->getColumn('start_date') ) ) .' End Date: '. TTDate::getDate('DATE+TIME', strtotime( $udt_obj->getColumn('end_date') ) ), __FILE__, __LINE__, __METHOD__, 5);

		if ( is_object( $udt_obj->getUserObject() ) ) {
			//Calculate pre-mature exceptions, so pre-mature Missing Out Punch exceptions aren't made active until they are ready.
			//Don't calculate future exceptions though.
			$transaction_function = function() use ( $udt_obj, $flags ) {
				$cp = TTNew('CalculatePolicy'); /** @var CalculatePolicy $cp */
				$cp->setFlag( $flags );
				$cp->setUserObject( $udt_obj->getUserObject() );
				$cp->getUserObject()->setTransactionMode( 'REPEATABLE READ' );
				$cp->addPendingCalculationDate( strtotime( $udt_obj->getColumn('start_date') ), strtotime( $udt_obj->getColumn('end_date') ) );
				$cp->calculate( strtotime( $udt_obj->getColumn('start_date') ) ); //This sets timezone itself.
				$cp->Save();
				$cp->getUserObject()->setTransactionMode(); //Back to default isolation level.

				return TRUE;
			};

			$udt_obj->RetryTransaction( $transaction_function, 2, 3 ); //Set retry_sleep this fairly high so real-time punches have a chance to get saved between retries.

		} else {
			Debug::Arr( $udt_obj->getUserObject(), 'ERROR: Invalid UserObject: User ID: '. $udt_obj->getUser(), __FILE__, __LINE__, __METHOD__, 10);
		}

		$i++;
	}
}
Debug::text(' calcQuickExceptions: Done', __FILE__, __LINE__, __METHOD__, 5);

Debug::writeToLog();
Debug::Display();
?>