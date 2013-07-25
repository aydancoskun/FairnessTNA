<?php
/*********************************************************************************
 * This file is part of "Fairness", a Payroll and Time Management program.
 * Fairness is Copyright 2013 Aydan Coscun (aydan.ayfer.coskun@gmail.com)
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
 * $Revision: 7017 $
 * $Id: Exception.class.php 7017 2012-06-13 17:06:43Z ipso $
 * $Date: 2012-06-13 10:06:43 -0700 (Wed, 13 Jun 2012) $
 */

/**
 * @package Core
 */
class DBError extends Exception {
   function __construct($e, $code = 'DBError' ) {
      global $db, $skip_db_error;

      if ( isset($skip_db_error_exception) AND $skip_db_error_exception === TRUE ) { //Used by system_check script.
         return TRUE;
      }

      $db->FailTrans();

      //print_r($e);
      //adodb_pr($e);

      Debug::Text('Begin Exception...', __FILE__, __LINE__, __METHOD__,10);
      Debug::Arr( Debug::backTrace(), ' BackTrace: ', __FILE__, __LINE__, __METHOD__,10);

      //Log database error
      if ( isset($e->message) ) {
         if ( stristr( $e->message, 'statement timeout' ) !== FALSE ) {
            $code = 'DBTimeout';
         }
         Debug::Text($e->message, __FILE__, __LINE__, __METHOD__,10);
      }

      if ( isset($e->trace) ) {
         $e = strip_tags( adodb_backtrace($e->trace) );
         Debug::Arr( $e, 'Exception...', __FILE__, __LINE__, __METHOD__,10);
      }

      Debug::Text('End Exception...', __FILE__, __LINE__, __METHOD__,10);
      //Dump debug buffer.
      Debug::Display();
      Debug::writeToLog();
      Debug::emailLog();

      Redirect::Page( URLBuilder::getURL( array('exception' => $code ), Environment::getBaseURL().'DownForMaintenance.php') );

      ob_flush();
      ob_clean();

      exit;
   }
}


/**
 * @package Core
 */
class GeneralError extends Exception {
   function __construct($message) {
      global $db;

      //debug_print_backtrace();
      $db->FailTrans();

      echo "======================================================================<br>\n";
      echo "EXCEPTION!<br>\n";
      echo "======================================================================<br>\n";
      echo "<b>Error message: </b>".$message ."<br>\n";
      echo "<b>Error code: </b>".$this->getCode()."<br>\n";
      echo "<b>Script Name: </b>".$this->getFile()."<br>\n";
      echo "<b>Line Number: </b>".$this->getLine()."<br>\n";
      echo "======================================================================<br>\n";
      echo "EXCEPTION!<br>\n";
      echo "======================================================================<br>\n";

      Debug::Arr( Debug::backTrace(), ' BackTrace: ', __FILE__, __LINE__, __METHOD__,10);

      //Dump debug buffer.
      Debug::Display();
      Debug::writeToLog();
      Debug::emailLog();
      ob_flush();
      ob_clean();

      Redirect::Page( URLBuilder::getURL( array('exception' => 'GeneralError'), Environment::getBaseURL().'DownForMaintenance.php') );

      exit;
   }
}
?>
