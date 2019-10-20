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
 * @package Modules\Install
 */
class InstallSchema_1007A extends InstallSchema_Base {

	/**
	 * @return bool
	 */
	function preInstall() {
		Debug::text('preInstall: '. $this->getVersion(), __FILE__, __LINE__, __METHOD__, 9);

		return TRUE;
	}


	/**
	 * @return bool
	 */
	function postInstall() {
		Debug::text('postInstall: '. $this->getVersion(), __FILE__, __LINE__, __METHOD__, 9);

		//New Pay Period Schedule format, update any current schedules.
		$ppslf = TTnew( 'PayPeriodScheduleListFactory' ); /** @var PayPeriodScheduleListFactory $ppslf */
		$ppslf->getAll();
		Debug::text('Found Pay Period Schedules: '. $ppslf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 9);
		if ( $ppslf->getRecordCount() > 0 ) {
			foreach( $ppslf as $pps_obj ) {
				if ( $pps_obj->getType() == 10 OR $pps_obj->getType() == 20 ) {
					$pps_obj->setStartDayOfWeek( TTDate::getDayOfWeek( TTDate::strtotime( $pps_obj->getColumn('anchor_date') ) ) );
					$pps_obj->setTransactionDate( ( floor( (TTDate::strtotime( $pps_obj->getColumn('primary_transaction_date') ) - TTDate::strtotime( $pps_obj->getColumn('primary_date') ) ) / 86400 ) + 1 ) );
				} elseif (	$pps_obj->getType() == 30 ) {
					$pps_obj->setPrimaryDayOfMonth( ( TTDate::getDayOfMonth( TTDate::strtotime( $pps_obj->getColumn('anchor_date') ) ) + 1 ) );
					if ( $pps_obj->getColumn('primary_transaction_date_ldom') == 1 ) {
						$pps_obj->setPrimaryTransactionDayOfMonth( -1 );
					} else {
						$pps_obj->setPrimaryTransactionDayOfMonth( TTDate::getDayOfMonth( TTDate::strtotime( $pps_obj->getColumn('primary_transaction_date') ) ) );
					}

					$pps_obj->setSecondaryDayOfMonth( TTDate::getDayOfMonth( TTDate::strtotime( $pps_obj->getColumn('primary_date') ) ) );
					if ( $pps_obj->getColumn('secondary_transaction_date_ldom') == 1 ) {
						$pps_obj->setSecondaryTransactionDayOfMonth( -1 );
					} else {
						$pps_obj->setSecondaryTransactionDayOfMonth( TTDate::getDayOfMonth( TTDate::strtotime( $pps_obj->getColumn('secondary_transaction_date') ) ) );
					}
				} elseif ( $pps_obj->getType() == 50 ) {
					$pps_obj->setPrimaryDayOfMonth( TTDate::getDayOfMonth( TTDate::strtotime( $pps_obj->getColumn('anchor_date') ) ) );
					if ( $pps_obj->getColumn('primary_transaction_date_ldom') == 1 ) {
						$pps_obj->setPrimaryTransactionDayOfMonth( -1 );
					} else {
						$pps_obj->setPrimaryTransactionDayOfMonth( TTDate::getDayOfMonth( TTDate::strtotime( $pps_obj->getColumn('primary_transaction_date') ) ) );
					}
				}

				if ( $pps_obj->getColumn('transaction_date_bd') == 1 OR $pps_obj->getColumn('secondary_transaction_date_bd') == 1 ) {
					$pps_obj->setTransactionDateBusinessDay( TRUE );
				}

				if ( $pps_obj->isValid() ) {
					$pps_obj->Save();
				}
			}
		}

		return TRUE;

	}
}
?>
