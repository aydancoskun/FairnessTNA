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
class ChequeForms_CR_STANDARD_FORM_1 extends ChequeForms_Base {

	public function getTemplateSchema( $name = NULL ) {
		$template_schema = array(


                                //Initialize page1, replace date label on template.

                                // date label
                                array(
                                    'page' => 1,
                                    'template_page' => 1,
                                ),

                                // lines
                                array(
                                    'function' => 'drawPiecemeal',
                                    'value' => '_____________________________',
                                    'coordinates' => array(
                                            array(
                                                'x' => 47,
                                                'y' => 245,
                                                'h' => 5,
                                                'w' => 60,
                                                'halign' => 'L',
                                            ),
                                            array(
                                                'x' => 147,
                                                'y' => 245,
                                                'h' => 5,
                                                'w' => 60,
                                                'halign' => 'L',
                                            ),
                                            array(
                                                'x' => 147,
                                                'y' => 255,
                                                'h' => 5,
                                                'w' => 60,
                                                'halign' => 'C',
                                            ),
                                    ),
                                    'font' => array(
                                            'size' => 10,
                                            'type' => ''
                                    ),
                                ),

                                array(
                                    'function' => 'drawSegments',
                                    'value' => array(TTi18n::gettext('Employee Signature').':', TTi18n::gettext('Supervisor Signature').':', TTi18n::gettext('(print name)')),
                                    'coordinates' => array(
                                            array(
                                                'x' => 7,
                                                'y' => 245,
                                                'h' => 5,
                                                'w' => 40,
                                                'halign' => 'L',
                                            ),
                                            array(
                                                'x' => 107,
                                                'y' => 245,
                                                'h' => 5,
                                                'w' => 40,
                                                'halign' => 'R',
                                            ),
                                            array(
                                                'x' => 140,
                                                'y' => 260,
                                                'h' => 5,
                                                'w' => 60,
                                                'halign' => 'C',
                                            ),
                                    ),
                                    'font' => array(
                                            'size' => 10,
                                            'type' => '',
                                    ),
                                ),

								// full name
								'full_name' => array(
                                        'function' => 'drawPiecemeal',
										'coordinates' => array(
                                                            array(
                                                                'x' => 20,
                                                                'y' => 28,
                                                                'h' => 5,
                                                                'w' => 100,
                                                                'halign' => 'L',
                                                            ),
                                                            array(
                                                                'x' => 40,
                                                                'y' => 250,
                                                                'h' => 5,
                                                                'w' => 60,
                                                                'halign' => 'C',
                                                            ),
												    ),
										'font' => array(
														'size' => 10,
                                                        'type' => ''
                                                    )
								),

                                // amount words and cents
                                'amount_words_cents' => array(
                                        'function' => array('filterAmountWordsCents', 'drawNormal'),
                                        'coordinates' => array(
                                                        'x' => 20,
                                                        'y' => 36,
                                                        'h' => 5,
                                                        'w' => 100,
                                                        'halign' => 'J',
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
                                                        'x' => 100,
                                                        'y' => 18,
                                                        'h' => 5,
                                                        'w' => 38,
                                                        'halign' => 'L',
                                                    ),
                                        'font' => array(
                                                        'size' => 10,
                                                        'type' => ''
                                                    )
                                ),
                                // amount padded
                                'amount_padded' => array(
                                        'function' => array('filterAmountPadded', 'drawNormal'),
                                        'coordinates' => array(
                                                        'x' => 136,
                                                        'y' => 27,
                                                        'h' => 5,
                                                        'w' => 24,
                                                        'halign' => 'L',
                                                    ),
                                        'font' => array(
                                                        'size' => 10,
                                                        'type' => ''
                                                    )
                                ),
                                // left column
                                'stub_left_column' => array(
                                        'function' => 'drawPiecemeal',
                                        'coordinates' => array(
                                                            array(
                                                                'x' => 15,
                                                                'y' => 105,
                                                                'h' => 95,
                                                                'w' => 96,
                                                                'halign' => 'L',
                                                            ),
                                                            array(
                                                                'x' => 15,
                                                                'y' => 200,
                                                                'h' => 45,
                                                                'w' => 96,
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
                                                                'x' => 111,
                                                                'y' => 105,
                                                                'h' => 95,
                                                                'w' => 96,
                                                                'halign' => 'R',
                                                            ),
                                                            array(
                                                                'x' => 111,
                                                                'y' => 200,
                                                                'h' => 45,
                                                                'w' => 96,
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