<?php
/*********************************************************************************
 * This file is part of "Fairness", a Payroll and Time Management program.
 * Fairness is Copyright 2013 Aydan Coskun (aydan.ayfer.coskun@gmail.com)
 * Portions of this software are Copyright of T i m e T r e x Software Inc.
 * Fairness is a fork of "T i m e T r e x Workforce Management" Software.
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


/**
 * @package ChequeForms
 */
class ChequeForms_Base
{
    public $debug = false;
    public $data = null; //Form data is stored here in an array.
    public $records = array(); //Store multiple records here to process on a single form. ie: T4's where two employees can be on a single page.

    public $class_directory = null;

    /*
     * PDF related variables
     */
    public $pdf_object = null;
    public $template_index = array();
    public $current_template_index = null;
    public $page_offsets = array(0, 0); //x, y
    public $template_offsets = array(0, 0); //x, y
    public $show_background = false; //Do not show the PDF background
    public $default_font = 'helvetica'; // helvetica

    public function setClassDirectory($dir)
    {
        $this->class_directory = $dir;
    }

    public function Output($type)
    {
        switch (strtolower($type)) {
            case 'pdf':
                return $this->_outputPDF($type);
                break;
            case 'xml':
                return $this->_outputXML($type);
                break;
        }
    }

    public function _outputPDF()
    {
        //Initialize PDF with template.
        $pdf = $this->getPDFObject();

        //Get location map, start looping over each variable and drawing
        $records = $this->getRecords();

        if (is_array($records) and count($records) > 0) {
            $template_schema = $this->getTemplateSchema();

            $e = 0;
            foreach ($records as $employee_data) {
                //Debug::Arr($employee_data, 'Employee Data: ', __FILE__, __LINE__, __METHOD__,10);

                $this->arrayToObject($employee_data); //Convert record array to object

                $template_page = null;

                foreach ($template_schema as $field => $schema) {
                    $this->Draw($this->$field, $schema);
                }

                $this->resetTemplatePage();

                $e++;
            }
        }

        $this->clearRecords();

        return true;
    }

    public function getPDFObject()
    {
        return $this->pdf_object;
    }

    public function setPDFObject(&$obj)
    {
        $this->pdf_object = $obj;
        return true;
    }

    public function getRecords()
    {
        return $this->records;
    }

    public function setRecords($data)
    {
        $this->records = $data;
        return true;
    }

    public function arrayToObject($array)
    {
        if (is_array($array)) {
            foreach ($array as $key => $value) {
                $this->$key = $value;
            }
        }

        return true;
    }

    public function Draw($value, $schema)
    {
        if (!is_array($schema)) {
            return false;
        }

        //If its set, use the static value from the schema.
        if (isset($schema['value'])) {
            $value = $schema['value'];
            unset($schema['value']);
        }

        //If custom function is defined, pass off to that immediate.
        //Else, try the generic drawing method.
        if (isset($schema['function'])) {
            if (!is_array($schema['function'])) {
                $schema['function'] = (array)$schema['function'];
            }
            foreach ($schema['function'] as $function) {
                if (method_exists($this, $function)) {
                    $value = $this->$function($value, $schema);
                }
            }
            unset($function);

            return $value;
        }

        $pdf = $this->getPDFObject();

        //Make sure we don't load the same template more than once.
        if (isset($schema['template_page']) and $schema['template_page'] != $this->current_template_index) {
            //Debug::text('Adding new page: '. $schema .' Template Page: '. $schema['template_page'], __FILE__, __LINE__, __METHOD__, 10);
            $this->addPage($schema);
        } else {
            //Debug::text('Skipping template... Value: '. $value, __FILE__, __LINE__, __METHOD__, 10);
        }

        //on_background flag forces that item to only be shown if the background is as well.
        //This has to go below any addPage() call, otherwise pages won't be added if the first cell is only to be shown on the background.
        if (isset($schema['on_background']) and $schema['on_background'] == true and $this->getShowBackground() == false) {
            return false;
        }

        if (isset($schema['font'])) {
            if (!isset($schema['font']['font'])) {
                $schema['font']['font'] = $this->default_font;
            }
            if (!isset($schema['font']['type'])) {
                $schema['font']['type'] = '';
            }
            if (!isset($schema['font']['size'])) {
                $schema['font']['size'] = 8;
            }

            $pdf->SetFont($schema['font']['font'], $schema['font']['type'], $schema['font']['size']);
        } else {
            $pdf->SetFont($this->default_font, '', 8);
        }

        if (isset($schema['coordinates'])) {
            $coordinates = $schema['coordinates'];
            //var_dump( Debug::BackTrace() );

            if (isset($coordinates['text_color']) and is_array($coordinates['text_color'])) {
                $pdf->setTextColor($coordinates['text_color'][0], $coordinates['text_color'][1], $coordinates['text_color'][2]);
            } else {
                $pdf->setTextColor(0, 0, 0); //Black text.
            }

            if (isset($coordinates['fill_color']) and is_array($coordinates['fill_color'])) {
                $pdf->setFillColor($coordinates['fill_color'][0], $coordinates['fill_color'][1], $coordinates['fill_color'][2]);
                $coordinates['fill'] = 1;
            } else {
                $pdf->setFillColor(255, 255, 255); //White
                $coordinates['fill'] = 0;
            }

            $pdf->setXY($coordinates['x'] + $this->getPageOffsets('x'), $coordinates['y'] + $this->getPageOffsets('y'));

            if ($this->getDebug() == true) {
                $pdf->setDrawColor(0, 0, 255);
                $coordinates['border'] = 1;
            } else {
                if (!isset($coordinates['border'])) {
                    $coordinates['border'] = 0;
                }
            }

            if (isset($schema['multicell']) and $schema['multicell'] == true) {
                //Debug::text('Drawing MultiCell... Value: '. $value, __FILE__, __LINE__, __METHOD__, 10);
                $pdf->MultiCell($coordinates['w'], $coordinates['h'], $value, $coordinates['border'], strtoupper($coordinates['halign']), $coordinates['fill']);
            } else {
                //Debug::text('Drawing Cell... Value: '. $value, __FILE__, __LINE__, __METHOD__, 10);
                $pdf->Cell($coordinates['w'], $coordinates['h'], $value, $coordinates['border'], 0, strtoupper($coordinates['halign']), $coordinates['fill']);
            }
            unset($coordinates);
        } else {
            Debug::text('NOT Drawing Cell... Value: ' . $value, __FILE__, __LINE__, __METHOD__, 10);
        }


        return true;
    }

    public function addPage($schema = null)
    {
        $pdf = $this->getPDFObject();

        $pdf->AddPage();
        if ($this->getShowBackground() == true and isset($this->template_index[$schema['template_page']])) {
            if (isset($schema['combine_templates']) and is_array($schema['combine_templates'])) {
                $template_schema = $this->getTemplateSchema();

                //Handle combining multiple template together with a X,Y offset.
                foreach ($schema['combine_templates'] as $combine_template) {
                    Debug::text('Combining Template Pages... Template: ' . $combine_template['template_page'] . ' Y: ' . $combine_template['y'], __FILE__, __LINE__, __METHOD__, 10);
                    $pdf->useTemplate($this->template_index[$combine_template['template_page']], $combine_template['x'] + $this->getTemplateOffsets('x'), $combine_template['y'] + $this->getTemplateOffsets('y'));

                    $this->setPageOffsets($combine_template['x'], $combine_template['y']);
                    $this->current_template_index = $schema['template_page'];
                    $this->initPage($template_schema);
                }
                unset($combine_templates);
                $this->setPageOffsets(0, 0); //Reset page offsets after each template is initialized.
            } else {
                $pdf->useTemplate($this->template_index[$schema['template_page']], $this->getTemplateOffsets('x'), $this->getTemplateOffsets('y'));
            }
        }
        $this->current_template_index = $schema['template_page'];


        return true;
    }

    public function getShowBackground()
    {
        return $this->show_background;
    }

    public function setShowBackground($bool)
    {
        $this->show_background = $bool;
        return true;
    }

    /*
    *
    *
    * Automatically calculate the amount_words
    *
    */

    public function getTemplateOffsets($type = null)
    {
        switch (strtolower($type)) {
            case 'x':
                return $this->template_offsets[0];
                break;
            case 'y':
                return $this->template_offsets[1];
                break;
            default:
                return $this->template_offsets;
                break;
        }
    }

    /*
    *
    *
    * Automatically calculate the amount_cents
    *
    */

    public function setTemplateOffsets($x, $y)
    {
        $this->template_offsets = array($x, $y);
        return true;
    }

    public function initPage($template_schema)
    {
        if (is_array($template_schema)) {
            foreach ($template_schema as $field => $init_schema) {
                if (is_numeric($field)) {
                    //Debug::text(' Initializing Template Page... Field: '. $field, __FILE__, __LINE__, __METHOD__, 10);
                    $this->Draw($this->$field, $init_schema);
                }
            }
            unset($template_schema, $field, $init_schema);

            return true;
        }

        return false;
    }

    /*
    *
    *
    * Automatically calculate the amount_padded
    *
    */

    public function getPageOffsets($type = null)
    {
        switch (strtolower($type)) {
            case 'x':
                return $this->page_offsets[0];
                break;
            case 'y':
                return $this->page_offsets[1];
                break;
            default:
                return $this->page_offsets;
                break;
        }
    }

    // Format date as the country

    public function setPageOffsets($x, $y)
    {
        $this->page_offsets = array($x, $y);
        return true;
    }

    public function getDebug()
    {
        return $this->debug;
    }

    public function setDebug($bool)
    {
        $this->debug = $bool;
    }

    /*
     *
     * PDF helper functions
     *
     */

    public function resetTemplatePage()
    {
        $this->current_template_index = null;
        return true;
    }

    public function clearRecords()
    {
        $this->records = array();
    }

    public function addRecord($data)
    {
        $this->records[] = $data;
        return true;
    }

    public function countRecords()
    {
        return count($this->records);
    }

    public function getDisplayDateFormat()
    {
        $formats = array(
            'd/m/Y' => 'd  /   m  /  y        ',
            'm/d/Y' => 'm  /   d  /  y        ',
        );

        if (isset($formats[$this->getDateFormat()])) {
            return $formats[$this->getDateFormat()];
        }

        return false;
    }

    public function getDateFormat()
    {
        if (isset($this->country) and strtolower($this->country) == 'ca') {
            $date_format = 'd/m/Y';
        } else {
            $date_format = 'm/d/Y';
        }

        return $date_format;
    }

    public function filterAmountWordsCents($value)
    {
        return $this->filterAmountWords($value) . TTi18n::gettext(' and ') . $this->filterAmountCents($value) . ' *****';
    }

    public function filterAmountWords($value)
    {
        if (isset($this->amount)) {
            $numbers_words = new Numbers_Words();
            $value = str_pad(ucwords($numbers_words->toWords(floor($this->amount), 'en_US')) . ' ', 65, "-", STR_PAD_RIGHT);
        }
        return $value;
    }


    //This gives the same affect of adding a new page on the next time Draw() is called.
    //Can be used when multiple records are processed for a single form.

    public function filterAmountCents($value)
    {
        if (isset($this->amount)) {
            $value = Misc::getAfterDecimal($this->amount) . '/100';
        }
        return $value;
    }

    public function filterAmountPadded($value)
    {
        if (isset($this->amount)) {
            $value = str_pad(Misc::MoneyFormat($this->amount), 12, '*', STR_PAD_LEFT);
        }
        if (get_class($this) === 'ChequeForms_9085') {
            return ' ' . $this->symbol . $value;
        }
        if (get_class($this) === 'ChequeForms_FORM1') {
            return '  ' . $this->symbol . $value;
        }
        if (get_class($this) === 'ChequeForms_FORM2') {
            return $this->symbol . '  ' . $value;
        }
        return $value;
    }

    // Draw the same data at different locations
    // value should be string

    public function filterDate($epoch)
    {
        return date($this->getDateFormat(), $epoch);
    }
    //Draw each element of an array at different locations.
    //Value must be an array.

    public function filterAddress($value)
    {
        if (isset($this->address1)) {
            $value = $this->address1 . ' ';
        }
        if (isset($this->address2)) {
            $value .= $this->address2;
        }

        return $value;
    }

    public function filterProvince($value)
    {
        if (isset($this->city)) {
            $value = $this->city;
        }
        if (isset($this->province)) {
            $value .= ', ' . $this->province;
        }
        if (isset($this->postal_code)) {
            $value .= ' ' . $this->postal_code;
        }

        return $value;
    }

    public function drawPiecemeal($value, $schema)
    {
        unset($schema['function']);
        foreach ($schema['coordinates'] as $key => $coordinates) {
            if (is_array($coordinates)) {
                $mode['coordinates'] = $coordinates;
                if (isset($schema['font'])) {
                    $mode['font'] = $schema['font'];
                }
                if (isset($schema['multicell'])) {
                    $mode['multicell'] = $schema['multicell'];
                }

                $this->Draw($value, $mode);
            }
        }
        return true;
    }

    public function drawSegments($value, $schema)
    {
        if (is_array($value)) {
            $i = 0;
            foreach ($value as $segment) {
                $this->Draw($segment, $this->getSchemaSpecificCoordinates($schema, $i));
                $i++;
            }
        }
        return true;
    }

    //Generic draw function that works strictly off the coordinate map.
    //It checks for a variable specific function before running though, so we can handle more complex
    //drawing functionality.

    public function getSchemaSpecificCoordinates($schema, $key, $sub_key1 = null)
    {
        unset($schema['function']);

        if ($sub_key1 !== null) {
            if (isset($schema['coordinates'][$key][$sub_key1])) {
                return array('coordinates' => $schema['coordinates'][$key][$sub_key1]);
            }
        } else {
            if (isset($schema['coordinates'][$key])) {
                /*
                $tmp = $schema['coordinates'][$key];
                unset($schema['coordinates']);
                $schema['coordinates'] = $tmp;

                return $schema;
                */
                return array('coordinates' => $schema['coordinates'][$key], 'font' => $schema['font']);
            }
        }

        return false;
    }

    //Make sure we pass *ALL* data to this function, as it will overwrite existing data, but if one record has a field and another one doesn't,
    //we need to send blank fields so the data is overwritten correctly.

    public function drawNormal($value, $schema)
    {
        if ($value !== false) { //If value is FALSE don't draw anything, this prevents a blank cell from being drawn overtop of other text.
            unset($schema['function']); //Strip off the function element to prevent infinite loop
            $this->Draw($value, $schema);
            return true;
        }

        return false;
    }

    public function __get($name)
    {
        if (isset($this->data[$name])) {
            return $this->data[$name];
        }

        return false;
    }

    /*
     *
     * Magic functions.
     *
     */

    public function __set($name, $value)
    {
        $filter_function = $this->getFilterFunction($name);
        if ($filter_function != '') {
            if (!is_array($filter_function)) {
                $filter_function = (array)$filter_function;
            }

            foreach ($filter_function as $function) {
                //Call function
                if (method_exists($this, $function)) {
                    $value = $this->$function($value);

                    if ($value === false) {
                        return false;
                    }
                }
            }
            unset($function);
        }

        $this->data[$name] = $value;

        return true;
    }

    public function getFilterFunction($name)
    {
        $variable_function_map = array();

        if (isset($variable_function_map[$name])) {
            return $variable_function_map[$name];
        }

        return false;
    }

    public function __isset($name)
    {
        return isset($this->data[$name]);
    }

    public function __unset($name)
    {
        unset($this->data[$name]);
    }
}
