<?php
/**********************************************************************************
 * This file is part of "FairnessTNA", a Payroll and Time Management program.
 * FairnessTNA is copyright 2013-2017 Aydan Coskun (aydan.ayfer.coskun@gmail.com)
 * others. For full attribution and copyrights details see the COPYRIGHT file.
 *
 * FairnessTNA is free software; you can redistribute it and/or modify it under the
 * terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation, either version 3 of the License, or (at you option )
 * any later version.
 *
 * FairnessTNA is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE.  See the GNU Affero General Public License for more
 * details.
 *
 * You should have received a copy of the GNU Affero General Public License along
 * with this program; if not, see http://www.gnu.org/licenses or write to the Free
 * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301 USA.
 *********************************************************************************/


/**
 * @package ChequeForms
 */

include_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'ChequeForms_Base.class.php');

class ChequeForms_CR_STANDARD_FORM_2 extends ChequeForms_Base
{
    public function getTemplateSchema($name = null)
    {
        $template_schema = array(


            //Initialize page1, replace date label on template.


            array(
                'page' => 1,
                'template_page' => 1,
            ),

            array(
                'value' => TTi18n::gettext('Recipient Copy:'),
                'coordinates' => array(
                    'x' => 65,
                    'y' => 95,
                    'h' => 5,
                    'w' => 75,
                    'halign' => 'C',
                ),
                'font' => array(
                    'size' => 14,
                    'type' => 'U',
                ),
            ),

            array(
                'function' => array('filterCompanyName', 'drawNormal'),
                'coordinates' => array(
                    'x' => 65,
                    'y' => 205,
                    'h' => 5,
                    'w' => 75,
                    'halign' => 'C',
                ),
                'font' => array(
                    'size' => 14,
                    'type' => 'U',
                ),
            ),
            array(
                'function' => 'drawPiecemeal',
                'value' => TTi18n::gettext('Date of Issue:'),
                'coordinates' => array(
                    array(
                        'x' => 75,
                        'y' => 105,
                        'h' => 5,
                        'w' => 30,
                        'halign' => 'J',
                    ),
                    array(
                        'x' => 75,
                        'y' => 215,
                        'h' => 5,
                        'w' => 30,
                        'halign' => 'J',
                    ),
                ),
                'font' => array(
                    'size' => 10,
                    'type' => 'B',
                ),
            ),
            array(
                'function' => 'drawPiecemeal',
                'value' => TTi18n::gettext('Recipient:'),
                'coordinates' => array(
                    array(
                        'x' => 75,
                        'y' => 115,
                        'h' => 5,
                        'w' => 30,
                        'halign' => 'J',
                    ),
                    array(
                        'x' => 75,
                        'y' => 225,
                        'h' => 5,
                        'w' => 30,
                        'halign' => 'J',
                    ),
                ),
                'font' => array(
                    'size' => 10,
                    'type' => 'B',
                ),
            ),
            array(
                'function' => 'drawPiecemeal',
                'value' => TTi18n::gettext('Amount:'),
                'coordinates' => array(
                    array(
                        'x' => 75,
                        'y' => 125,
                        'h' => 5,
                        'w' => 30,
                        'halign' => 'J',
                    ),
                    array(
                        'x' => 75,
                        'y' => 235,
                        'h' => 5,
                        'w' => 30,
                        'halign' => 'J',
                    ),
                ),
                'font' => array(
                    'size' => 10,
                    'type' => 'B',
                ),
            ),
            array(
                'function' => 'drawPiecemeal',
                'value' => TTi18n::gettext('Regarding:'),
                'coordinates' => array(
                    array(
                        'x' => 75,
                        'y' => 135,
                        'h' => 5,
                        'w' => 30,
                        'halign' => 'J',
                    ),
                    array(
                        'x' => 75,
                        'y' => 245,
                        'h' => 5,
                        'w' => 30,
                        'halign' => 'J',
                    ),
                ),
                'font' => array(
                    'size' => 10,
                    'type' => 'B',
                ),
            ),

            array(
                'function' => 'drawPiecemeal',
                'value' => TTDate::getDate('DATE+TIME', time()),
                'coordinates' => array(
                    array(
                        'x' => 105,
                        'y' => 105,
                        'h' => 5,
                        'w' => 130,
                        'halign' => 'J',
                    ),
                    array(
                        'x' => 105,
                        'y' => 215,
                        'h' => 5,
                        'w' => 130,
                        'halign' => 'J',
                    ),
                ),
                'font' => array(
                    'size' => 10,
                    'type' => '',
                ),

            ),
            array(
                'function' => array('filterRecipient', 'drawPiecemeal'),
                'coordinates' => array(
                    array(
                        'x' => 105,
                        'y' => 115,
                        'h' => 5,
                        'w' => 110,
                        'halign' => 'J',
                    ),
                    array(
                        'x' => 105,
                        'y' => 225,
                        'h' => 5,
                        'w' => 130,
                        'halign' => 'J',
                    ),
                ),
                'font' => array(
                    'size' => 10,
                    'type' => '',
                ),
            ),
            array(
                'function' => array('filterAmount', 'drawPiecemeal'),
                'coordinates' => array(
                    array(
                        'x' => 105,
                        'y' => 125,
                        'h' => 5,
                        'w' => 100,
                        'halign' => 'J',
                    ),
                    array(
                        'x' => 105,
                        'y' => 235,
                        'h' => 5,
                        'w' => 100,
                        'halign' => 'J',
                    ),
                ),
                'font' => array(
                    'size' => 10,
                    'type' => '',
                ),
            ),
            array(
                'function' => array('filterRegarding', 'drawPiecemeal'),
                'coordinates' => array(
                    array(
                        'x' => 105,
                        'y' => 135,
                        'h' => 5,
                        'w' => 100,
                        'halign' => 'J',
                    ),
                    array(
                        'x' => 105,
                        'y' => 245,
                        'h' => 5,
                        'w' => 100,
                        'halign' => 'J',
                    ),
                ),
                'font' => array(
                    'size' => 10,
                    'type' => ''
                ),
            ),
            array(
                'value' => TTi18n::gettext('Generated By') . "\n\n\n ",
                'coordinates' => array(
                    'x' => 30,
                    'y' => 255,
                    'h' => 4,
                    'w' => 25,
                    'halign' => 'C',
                    'border' => 1,
                    'fill_color' => array(255, 255, 255),
                ),
                'font' => array(
                    'size' => 8,
                    'type' => ''
                ),

                'multicell' => true,
            ),
            array(
                'value' => TTi18n::gettext('Signed By') . "\n\n\n ",
                'coordinates' => array(
                    'x' => 55,
                    'y' => 255,
                    'h' => 4,
                    'w' => 25,
                    'halign' => 'C',
                    'border' => 1,
                    'fill_color' => array(255, 255, 255),
                ),
                'font' => array(
                    'size' => 8,
                    'type' => ''
                ),

                'multicell' => true,
            ),
            array(
                'value' => TTi18n::gettext('Received By') . "\n\n\n ",
                'coordinates' => array(
                    'x' => 80,
                    'y' => 255,
                    'h' => 4,
                    'w' => 35,
                    'halign' => 'C',
                    'border' => 'T,L,B',
                    'fill_color' => array(255, 255, 255),
                ),
                'font' => array(
                    'size' => 8,
                    'type' => ''
                ),

                'multicell' => true,
            ),
            array(
                'value' => TTi18n::gettext('Date') . "\n\n\n ",
                'coordinates' => array(
                    'x' => 115,
                    'y' => 255,
                    'h' => 4,
                    'w' => 35,
                    'halign' => 'C',
                    'border' => 'T,B',
                    'fill_color' => array(255, 255, 255),
                ),
                'font' => array(
                    'size' => 8,
                    'type' => ''
                ),

                'multicell' => true,
            ),
            array(
                'value' => TTi18n::gettext('SIN / SSN') . "\n\n\n ",
                'coordinates' => array(
                    'x' => 150,
                    'y' => 255,
                    'h' => 4,
                    'w' => 35,
                    'halign' => 'C',
                    'border' => 'T,R,B',
                    'fill_color' => array(255, 255, 255),
                ),
                'font' => array(
                    'size' => 8,
                    'type' => ''
                ),

                'multicell' => true,
            ),

            // full name
            'full_name' => array(
                'coordinates' => array(
                    'x' => 20,
                    'y' => 28,
                    'h' => 5,
                    'w' => 100,
                    'halign' => 'L',
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


        );

        if (isset($template_schema[$name])) {
            return $name;
        } else {
            return $template_schema;
        }
    }

    public function filterRecipient($value)
    {
        if (isset($this->full_name)) {
            $value = $this->full_name;
        }
        return $value;
    }

    public function filterAmount($value)
    {
        if (isset($this->symbol)) {
            $value = ' ' . $this->symbol;
        }
        if (isset($this->amount)) {
            $value .= Misc::MoneyFormat($this->amount);
        }
        return $value;
    }

    public function filterRegarding($value)
    {
        return TTi18n::gettext('Payment from') . ' ' . TTDate::getDate('DATE', $this->start_date) . ' ' . TTi18n::gettext('to') . ' ' . TTDate::getDate('DATE', $this->end_date);
    }

    public function filterCompanyName($value)
    {
        return $this->company_name . ' ' . TTi18n::gettext('Copy:');
    }
}
