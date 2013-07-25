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
 * $Revision: 2286 $
 * $Id: CA.class.php 2286 2008-12-12 23:12:41Z ipso $
 * $Date: 2008-12-12 15:12:41 -0800 (Fri, 12 Dec 2008) $
 */

/**
 * @package ChequeForms
 */

include_once( dirname(__FILE__) . DIRECTORY_SEPARATOR . 'ChequeForms_Base.class.php' );
class ChequeForms_DLT103 extends ChequeForms_Base {

	public function getTemplateSchema( $name = NULL ) {
		$template_schema = array(


                                //Initialize page1, replace date label on template.

                                // date label
                                array(
                                    'page' => 1,
                                    'template_page' => 1,
                                    'value' => TTi18n::gettext('Date:').' ',
                                    'coordinates' => array(
                                                'x' => 172,
                                                'y' => 28,
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
                                                        'y' => 41,
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
                                                        'x' => 25,
                                                        'y' => 49,
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
                                                        'x' => 125,
                                                        'y' => 49,
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
                                                        'x' => 182,
                                                        'y' => 28,
                                                        'h' => 10,
                                                        'w' => 25,
                                                        'halign' => 'C',
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
                                                        'x' => 172,
                                                        'y' => 41,
                                                        'h' => 10,
                                                        'w' => 35,
                                                        'halign' => 'C',
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
                                                                'h' => 100,
                                                                'w' => 96,
                                                                'halign' => 'L',
                                                            ),
                                                            array(
                                                                'x' => 15,
                                                                'y' => 205,
                                                                'h' => 100,
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
                                                                'h' => 100,
                                                                'w' => 96,
                                                                'halign' => 'R',
                                                            ),
                                                            array(
                                                                'x' => 111,
                                                                'y' => 205,
                                                                'h' => 100,
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