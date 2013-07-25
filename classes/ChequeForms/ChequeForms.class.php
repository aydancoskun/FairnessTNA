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
 * $Revision: 2095 $
 * $Id: PayrollDeduction.class.php 2095 2008-09-01 07:04:25Z ipso $
 * $Date: 2008-09-01 00:04:25 -0700 (Mon, 01 Sep 2008) $
 */

/**
 * @package ChequeForms
 */
class ChequeForms {
	var $objs = NULL;

	var $tcpdf_dir = '../tcpdf/'; //TCPDF class directory.
	var $fpdi_dir = '../fpdi/'; //FPDI class directory.

	function __construct() {
		return TRUE;
	}

	function getFormObject( $form ) {
		$class_name = 'ChequeForms';
		$class_name .= '_'.$form;
		
        $class_directory = dirname( __FILE__ );
		$class_file_name = $class_directory . DIRECTORY_SEPARATOR . strtolower($form) .'.class.php';

		Debug::text('Class Directory: '. $class_directory, __FILE__, __LINE__, __METHOD__, 10);
		Debug::text('Class File Name: '. $class_file_name, __FILE__, __LINE__, __METHOD__, 10);
		Debug::text('Class Name: '. $class_name, __FILE__, __LINE__, __METHOD__, 10);

		if ( file_exists( $class_file_name ) ) {
			include_once( $class_file_name );

			$obj = new $class_name;
			$obj->setClassDirectory( $class_directory );
			$obj->default_font = TTi18n::getPDFDefaultFont();

			return $obj;
		} else {
			Debug::text('Class File does not exist!', __FILE__, __LINE__, __METHOD__, 10);
		}

		return FALSE;
	}

	function addForm( $obj ) {
		if ( is_object( $obj ) ) {
			$this->objs[] = $obj;

			return TRUE;
		}

		return FALSE;
	}

	function Output( $type ) {
		$type = strtolower($type);

		//Initialize PDF object so all subclasses can access it.
		//Loop through all objects and combine the output from each into a single document.
		if ( $type == 'pdf' ) {
            $pdf = new TTPDF();
			$pdf->setMargins(0,0,0,0);
			$pdf->SetAutoPageBreak(FALSE);
			//$pdf->setFontSubsetting(FALSE);

			foreach( (array)$this->objs as $obj ) {
				$obj->setPDFObject( $pdf );
				$obj->Output( $type );
			}

			return $pdf->Output('','S');
		}
	}
}
?>
