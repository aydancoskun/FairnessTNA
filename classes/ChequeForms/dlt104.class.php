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

include_once( dirname(__FILE__) . DIRECTORY_SEPARATOR . 'ChequeForms_Base.class.php' );
class ChequeForms_DLT104 extends ChequeForms_Base {

	public function getTemplateSchema( $name = NULL ) {
		$template_schema = array(
								//Initialize page1, replace date label on template.

								// date label
								array(
									'page' => 1,
									'template_page' => 1,
									'value' => TTi18n::gettext('Date').': ',
									'coordinates' => array(
												'x' => 167,
												'y' => 23,
												'h' => 10,
												'w' => 10,
												'halign' => 'C',
									),
									'font' => array(
												'size' => 10,
												'type' => ''
									)
								),

								// full name
								'full_name' => array(
										'coordinates' => array(
														'x' => 25,
														'y' => 38,
														'h' => 5,
														'w' => 100,
														'halign' => 'L',
													),
										'font' => array(
														'size' => 10,
														'type' => ''
													)
								),

								// amount words
								'amount_words' => array(
										'function' => array('filterAmountWords', 'drawNormal'),
										'coordinates' => array(
														'x' => 15,
														'y' => 42,
														'h' => 10,
														'w' => 100,
														'halign' => 'L',
													),
										'font' => array(
														'size' => 10,
														'type' => ''
													)
								),
								// amount cents
								'amount_cents' => array(
										'function' => array('filterAmountCents', 'drawNormal'),
										'coordinates' => array(
														'x' => 120,
														'y' => 42,
														'h' => 10,
														'w' => 15,
														'halign' => 'L',
													),
										'font' => array(
														'size' => 10,
														'type' => ''
													)
								),

								// date
								'date' => array(
										'function' => array('filterDate', 'drawNormal'),
										'coordinates' => array(
														'x' => 177,
														'y' => 23,
														'h' => 10,
														'w' => 25,
														'halign' => 'C',
													),
										'font' => array(
														'size' => 10,
														'type' => ''
													)
								),
								//date format label
								array(
									'function' => array('getDisplayDateFormat', 'drawNormal'),
									'coordinates' => array(
												'x' => 177,
												'y' => 25.5,
												'h' => 10,
												'w' => 25,
												'halign' => 'C',
									),
									'font' => array(
												'size' => 6,
												'type' => ''
									)
								),

								'alignment_grid' => array( //Print alignment grid around the dollar amount, as it usually needs most fine tuning.
														   'function' => array('drawAlignmentGrid'),
														   'coordinates' => array(
																   'x' => 150,
																   'y' => 15,
																   'h' => 36,
																   'w' => 52,
														   ),
								),

								// amount padded
								'amount_padded' => array(
										'function' => array('filterAmountPadded', 'drawNormal'),
										'coordinates' => array(
														'x' => 167,
														'y' => 38,
														'h' => 5,
														'w' => 25,
														'halign' => 'C',
													),
										'font' => array(
														'size' => 10,
														'type' => ''
													)
								),

								// Full mailing address.
								'full_address' => array(
										'coordinates' => array(
												'x' => 20,
												'y' => 55,
												'h' => 5,
												'w' => 100,
												'halign' => 'L',
										),
										'font' => array(
												'size' => 10,
												'type' => ''
										),
										'multicell' => TRUE,
								),

								// Signature
								'signature' => array(
										'function' => array('drawImage'),
										'coordinates' => array(
												'x' => 150,
												'y' => 60,
												'h' => 18,
												'w' => 50,
										),
								),

								// left column
								'stub_left_column' => array(
										'function' => 'drawPiecemeal',
										'coordinates' => array(
															array(
																'x' => 15,
																'y' => 94,
																'h' => 75,
																'w' => 92,
																'halign' => 'L',
															),
															array(
																'x' => 15,
																'y' => 182,
																'h' => 75,
																'w' => 92,
																'halign' => 'L',
															),
													),
										'font' => array(
														'size' => 10,
														'type' => ''
													),
										'multicell' => TRUE,
								),
								// right column
								'stub_right_column' => array(
										'function' => 'drawPiecemeal',
										'coordinates' => array(
															array(
																'x' => 107,
																'y' => 94,
																'h' => 75,
																'w' => 91,
																'halign' => 'R',
															),
															array(
																'x' => 107,
																'y' => 182,
																'h' => 75,
																'w' => 91,
																'halign' => 'R',
															),
													),
										'font' => array(
														'size' => 10,
														'type' => '',
													),
										'multicell' => TRUE,
								),

					);

		if ( isset($template_schema[$name]) ) {
			return $name;
		} else {
			return $template_schema;
		}
	}
}
?>