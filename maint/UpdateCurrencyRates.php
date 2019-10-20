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
 * Updates Currency Exchange Rates.
 * This file should run once a day.
 *
 */
require_once( dirname(__FILE__) . DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'includes'. DIRECTORY_SEPARATOR .'global.inc.php');
require_once( dirname(__FILE__) . DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'includes'. DIRECTORY_SEPARATOR .'CLI.inc.php');

//Debug::setVerbosity(11);

$clf = new CompanyListFactory();
$clf->getByStatusID( array(10, 20, 23), NULL, array('a.id' => 'asc') );
if ( $clf->getRecordCount() > 0 ) {
	foreach ( $clf as $c_obj ) {
		if ( in_array( $c_obj->getStatus(), array(10, 20, 23) ) ) { //10=Active, 20=Hold, 23=Expired
			CurrencyFactory::updateCurrencyRates( $c_obj->getId() );
		}
	}
}
Debug::writeToLog();
Debug::Display();
?>