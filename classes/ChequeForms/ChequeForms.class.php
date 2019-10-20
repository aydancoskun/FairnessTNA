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
			$pdf = new TTPDF( 'P', 'mm', array( 207, 268 ) ); //US Letter size (215.9mm x 279.4mm) reduced by 4% (8.9mm x 11.4mm) and rounded to nearest mm. This should avoid the 96%/97% resize issue with 1" margins.

			$pdf->setMargins(0, 0, 0, 0); //Margins seem to only affect TCPDF, not actually how things are printed.

			$pdf->SetAutoPageBreak(FALSE);
			//$pdf->setFontSubsetting(FALSE);

			foreach( (array)$this->objs as $obj ) {
				$obj->setPDFObject( $pdf );
				$obj->Output( $type );
			}

			return $pdf->Output('', 'S');
		}
	}
}
?>
