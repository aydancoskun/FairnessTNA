<?php
require_once('../../../../../includes/global.inc.php');

require_once('../../../../other/forge_fdf.php');
$ffdf = new ForgeFDF();

//Dump PDF form field names: pdftk classes/GovernmentForms/country/us/templates/941.orig.pdf dump_data_fields

$pdf_form_url = "http://demo.fairnesstna.com/classes/GovernmentForms/country/us/941.pdf";
$fdf_data_strings = array( 'topmostSubform[0].Page1[0].Header[0].EntityArea[0].f1_01[0]' => 'z2z' );

//var_dump( $ffdf->forge_fdf( $pdf_form_url, $fdf_data_strings, $fdf_data_names, $fields_hidden, $fields_readonly ) );
file_put_contents( '941.fdf', $ffdf->forge_fdf( $pdf_form_url, $fdf_data_strings, array(), array(), array() ) );

Debug::writeToLog();
?>

