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


include_once('US.class.php');

/**
 * @package GovernmentForms
 */
class GovernmentForms_US_W3 extends GovernmentForms_US
{
    public $pdf_template = 'w3.pdf';

    public $template_offsets = array(0, 0);

    public function getFilterFunction($name)
    {
        $variable_function_map = array(
            'year' => 'isNumeric',
            'ein' => array('stripNonNumeric', 'isNumeric'),
        );

        if (isset($variable_function_map[$name])) {
            return $variable_function_map[$name];
        }

        return false;
    }

    public function filterCompanyAddress($value)
    {
        //Debug::Text('Filtering company address: '. $value, __FILE__, __LINE__, __METHOD__, 10);

        //Combine company address for multicell display.
        $retarr[] = $this->company_address1;
        if ($this->company_address2 != '') {
            $retarr[] = $this->company_address2;
        }
        $retarr[] = $this->company_city . ', ' . $this->company_state . ' ' . $this->company_zip_code;

        return implode("\n", $retarr);
    }

    public function filterControlNumber($value)
    {
        $value = str_pad($value, 4, 0, STR_PAD_LEFT);
        return $value;
    }

    public function _outputPDF()
    {
        //Initialize PDF with template.
        $pdf = $this->getPDFObject();

        if ($this->getShowBackground() == true) {
            $pdf->setSourceFile($this->getTemplateDirectory() . DIRECTORY_SEPARATOR . $this->pdf_template);

            $this->template_index[2] = $pdf->ImportPage(2);
        }

        if ($this->year == '') {
            $this->year = $this->getYear();
        }

        //Get location map, start looping over each variable and drawing
        $template_schema = $this->getTemplateSchema();
        if (is_array($template_schema)) {
            $template_page = null;

            foreach ($template_schema as $field => $schema) {
                //Debug::text('Drawing Cell... Field: '. $field, __FILE__, __LINE__, __METHOD__, 10);
                $this->Draw($this->$field, $schema);
            }
        }

        return true;
    }

    public function getTemplateSchema($name = null)
    {
        $template_schema = array(
            array(
                'page' => 1,
                'template_page' => 2,
                'value' => $this->year,
                'on_background' => true,
                'coordinates' => array(
                    'x' => 360,
                    'y' => 410,
                    'h' => 20,
                    'w' => 120,
                    'halign' => 'C',
                    'fill_color' => array(255, 255, 255),
                ),
                'font' => array(
                    'size' => 18,
                    'type' => 'B')
            ),
            array(
                'value' => $this->year,
                'on_background' => true,
                'coordinates' => array(
                    'x' => 151,
                    'y' => 481,
                    'h' => 10,
                    'w' => 20,
                    'halign' => 'C',
                    'fill_color' => array(255, 255, 255),
                ),
                'font' => array(
                    'size' => 8,
                    'type' => '')
            ),
            array(
                'value' => ($this->year + 1),
                'on_background' => true,
                'coordinates' => array(
                    'x' => 533,
                    'y' => 589,
                    'h' => 10,
                    'w' => 20,
                    'halign' => 'C',
                    'fill_color' => array(255, 255, 255),
                ),
                'font' => array(
                    'size' => 8,
                    'type' => '')
            ),

            //Finish initializing page 1.

            'control_number' => array(
                'function' => array('filterControlNumber', 'drawNormal'),
                'coordinates' => array(
                    'x' => 100,
                    'y' => 45,
                    'h' => 15,
                    'w' => 110,
                    'halign' => 'C',
                ),
            ),
            'kind_of_payer' => array(
                'function' => 'drawCheckBox',
                'coordinates' => array(
                    '941' => array(
                        'x' => 122,
                        'y' => 70,
                        'h' => 10,
                        'w' => 11,
                        'halign' => 'C',
                    ),
                    'military' => array(
                        'x' => 158,
                        'y' => 70,
                        'h' => 10,
                        'w' => 11,
                        'halign' => 'C',
                    ),
                    '943' => array(
                        'x' => 194,
                        'y' => 70,
                        'h' => 10,
                        'w' => 11,
                        'halign' => 'C',
                    ),
                    '944' => array(
                        'x' => 230,
                        'y' => 70,
                        'h' => 10,
                        'w' => 11,
                        'halign' => 'C',
                    ),

                    'ct-1' => array(
                        'x' => 122,
                        'y' => 96,
                        'h' => 10,
                        'w' => 11,
                        'halign' => 'C',
                    ),
                    'hshld' => array(
                        'x' => 158,
                        'y' => 96,
                        'h' => 10,
                        'w' => 11,
                        'halign' => 'C',
                    ),
                    'medicare' => array(
                        'x' => 194,
                        'y' => 96,
                        'h' => 10,
                        'w' => 11,
                        'halign' => 'C',
                    ),
                ),
                'font' => array(
                    'size' => 10,
                    'type' => 'B')
            ),
            'kind_of_employer' => array(
                'function' => array('strtolower', 'drawCheckBox'),
                'coordinates' => array(
                    'n' => array(
                        'x' => 367,
                        'y' => 70,
                        'h' => 10,
                        'w' => 11,
                        'halign' => 'C',
                    ),
                    't' => array(
                        'x' => 418,
                        'y' => 70,
                        'h' => 10,
                        'w' => 11,
                        'halign' => 'C',
                    ),
                    's' => array(
                        'x' => 367,
                        'y' => 96,
                        'h' => 10,
                        'w' => 11,
                        'halign' => 'C',
                    ),
                    'y' => array(
                        'x' => 418,
                        'y' => 96,
                        'h' => 10,
                        'w' => 11,
                        'halign' => 'C',
                    ),
                    'f' => array(
                        'x' => 475,
                        'y' => 96,
                        'h' => 10,
                        'w' => 11,
                        'halign' => 'C',
                    ),
                ),
                'font' => array(
                    'size' => 10,
                    'type' => 'B')
            ),
            'third_party_sick_pay' => array(
                'function' => 'drawCheckBox',
                'coordinates' => array(
                    array(
                        'x' => 540,
                        'y' => 96,
                        'h' => 10,
                        'w' => 11,
                        'halign' => 'C',
                    ),
                ),
                'font' => array(
                    'size' => 10,
                    'type' => 'B')
            ),

            'lc' => array( //Total W2 forms
                'coordinates' => array(
                    'x' => 38,
                    'y' => 117,
                    'h' => 15,
                    'w' => 110,
                    'halign' => 'C',
                ),
            ),
            'ld' => array( //Establishment Number
                'coordinates' => array(
                    'x' => 152,
                    'y' => 117,
                    'h' => 15,
                    'w' => 110,
                    'halign' => 'C',
                ),
            ),
            'ein' => array(
                'function' => array('formatEIN', 'drawNormal'),
                'coordinates' => array(
                    'x' => 38,
                    'y' => 140,
                    'h' => 15,
                    'w' => 220,
                    'halign' => 'L',
                ),
            ),
            'trade_name' => array(
                'coordinates' => array(
                    'x' => 38,
                    'y' => 165,
                    'h' => 15,
                    'w' => 220,
                    'halign' => 'L',
                ),
            ),
            'company_address' => array(
                'function' => array('filterCompanyAddress', 'drawNormal'),
                'coordinates' => array(
                    'x' => 38,
                    'y' => 182,
                    'h' => 48,
                    'w' => 220,
                    'halign' => 'L',
                ),
                'font' => array(
                    'size' => 8,
                    'type' => ''),
                'multicell' => true,
            ),
            'other_ein' => array(
                'coordinates' => array(
                    'x' => 38,
                    'y' => 260,
                    'h' => 15,
                    'w' => 220,
                    'halign' => 'L',
                ),
            ),
            'company_state' => array(
                'coordinates' => array(
                    'x' => 38,
                    'y' => 285,
                    'h' => 15,
                    'w' => 40,
                    'halign' => 'L',
                ),
            ),
            'company_state' => array(
                'coordinates' => array(
                    'x' => 38,
                    'y' => 285,
                    'h' => 15,
                    'w' => 40,
                    'halign' => 'L',
                ),
            ),
            'state_id1' => array(
                'coordinates' => array(
                    'x' => 80,
                    'y' => 285,
                    'h' => 15,
                    'w' => 180,
                    'halign' => 'L',
                ),
            ),

            'contact_name' => array(
                'coordinates' => array(
                    'x' => 38,
                    'y' => 332,
                    'h' => 15,
                    'w' => 220,
                    'halign' => 'L',
                ),
            ),
            'contact_phone' => array(
                'coordinates' => array(
                    'x' => 270,
                    'y' => 332,
                    'h' => 15,
                    'w' => 150,
                    'halign' => 'L',
                ),
            ),
            'contact_email' => array(
                'coordinates' => array(
                    'x' => 38,
                    'y' => 356,
                    'h' => 15,
                    'w' => 220,
                    'halign' => 'L',
                ),
            ),
            'contact_fax' => array(
                'coordinates' => array(
                    'x' => 270,
                    'y' => 356,
                    'h' => 15,
                    'w' => 150,
                    'halign' => 'L',
                ),
            ),
            'l1' => array(
                'function' => array('MoneyFormat', 'drawNormal'),
                'coordinates' => array(
                    'x' => 267,
                    'y' => 117,
                    'h' => 15,
                    'w' => 154,
                    'halign' => 'R',
                ),
            ),
            'l2' => array(
                'function' => array('MoneyFormat', 'drawNormal'),
                'coordinates' => array(
                    'x' => 422,
                    'y' => 117,
                    'h' => 15,
                    'w' => 154,
                    'halign' => 'R',
                ),
            ),
            'l3' => array(
                'function' => array('MoneyFormat', 'drawNormal'),
                'coordinates' => array(
                    'x' => 267,
                    'y' => 140,
                    'h' => 15,
                    'w' => 154,
                    'halign' => 'R',
                ),
            ),
            'l4' => array(
                'function' => array('MoneyFormat', 'drawNormal'),
                'coordinates' => array(
                    'x' => 422,
                    'y' => 140,
                    'h' => 15,
                    'w' => 154,
                    'halign' => 'R',
                ),
            ),
            'l5' => array(
                'function' => array('MoneyFormat', 'drawNormal'),
                'coordinates' => array(
                    'x' => 267,
                    'y' => 165,
                    'h' => 15,
                    'w' => 154,
                    'halign' => 'R',
                ),
            ),
            'l6' => array(
                'function' => array('MoneyFormat', 'drawNormal'),
                'coordinates' => array(
                    'x' => 422,
                    'y' => 165,
                    'h' => 15,
                    'w' => 154,
                    'halign' => 'R',
                ),
            ),
            'l7' => array(
                'function' => array('MoneyFormat', 'drawNormal'),
                'coordinates' => array(
                    'x' => 267,
                    'y' => 189,
                    'h' => 15,
                    'w' => 154,
                    'halign' => 'R',
                ),
            ),
            'l8' => array(
                'function' => array('MoneyFormat', 'drawNormal'),
                'coordinates' => array(
                    'x' => 422,
                    'y' => 189,
                    'h' => 15,
                    'w' => 154,
                    'halign' => 'R',
                ),
            ),
            'l9' => array(
                'function' => array('MoneyFormat', 'drawNormal'),
                'coordinates' => array(
                    'x' => 267,
                    'y' => 213,
                    'h' => 15,
                    'w' => 154,
                    'halign' => 'R',
                ),
            ),
            'l10' => array(
                'function' => array('MoneyFormat', 'drawNormal'),
                'coordinates' => array(
                    'x' => 422,
                    'y' => 213,
                    'h' => 15,
                    'w' => 154,
                    'halign' => 'R',
                ),
            ),
            'l11' => array(
                'function' => array('MoneyFormat', 'drawNormal'),
                'coordinates' => array(
                    'x' => 267,
                    'y' => 236,
                    'h' => 15,
                    'w' => 154,
                    'halign' => 'R',
                ),
            ),
            'l12a' => array(
                'function' => array('MoneyFormat', 'drawNormal'),
                'coordinates' => array(
                    'x' => 422,
                    'y' => 236,
                    'h' => 15,
                    'w' => 154,
                    'halign' => 'R',
                ),
            ),
            'l12b' => array(
                'function' => array('MoneyFormat', 'drawNormal'),
                'coordinates' => array(
                    'x' => 422,
                    'y' => 261,
                    'h' => 15,
                    'w' => 154,
                    'halign' => 'R',
                ),
            ),
            'l13' => array(
                'function' => array('MoneyFormat', 'drawNormal'),
                'coordinates' => array(
                    'x' => 267,
                    'y' => 261,
                    'h' => 15,
                    'w' => 154,
                    'halign' => 'R',
                ),
            ),
            'l14' => array(
                'function' => array('MoneyFormat', 'drawNormal'),
                'coordinates' => array(
                    'x' => 267,
                    'y' => 284,
                    'h' => 15,
                    'w' => 309,
                    'halign' => 'R',
                ),
            ),
            'l16' => array(
                'function' => array('MoneyFormat', 'drawNormal'),
                'coordinates' => array(
                    'x' => 38,
                    'y' => 309,
                    'h' => 15,
                    'w' => 113,
                    'halign' => 'R',
                ),
            ),
            'l17' => array(
                'function' => array('MoneyFormat', 'drawNormal'),
                'coordinates' => array(
                    'x' => 152,
                    'y' => 309,
                    'h' => 15,
                    'w' => 113,
                    'halign' => 'R',
                ),
            ),
            'l18' => array(
                'function' => array('MoneyFormat', 'drawNormal'),
                'coordinates' => array(
                    'x' => 267,
                    'y' => 309,
                    'h' => 15,
                    'w' => 154,
                    'halign' => 'R',
                ),
            ),
            'l19' => array(
                'function' => array('MoneyFormat', 'drawNormal'),
                'coordinates' => array(
                    'x' => 422,
                    'y' => 309,
                    'h' => 15,
                    'w' => 154,
                    'halign' => 'R',
                ),
            ),
        );

        if (isset($template_schema[$name])) {
            return $name;
        } else {
            return $template_schema;
        }
    }
}
