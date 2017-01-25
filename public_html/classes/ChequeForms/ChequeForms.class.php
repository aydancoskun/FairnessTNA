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
class ChequeForms
{
    public $objs = null;

    public $tcpdf_dir = '../tcpdf/'; //TCPDF class directory.
    public $fpdi_dir = '../fpdi/'; //FPDI class directory.

    public function __construct()
    {
        return true;
    }

    public function getFormObject($form)
    {
        $class_name = 'ChequeForms';
        $class_name .= '_' . $form;

        $class_directory = dirname(__FILE__);
        $class_file_name = $class_directory . DIRECTORY_SEPARATOR . strtolower($form) . '.class.php';

        Debug::text('Class Directory: ' . $class_directory, __FILE__, __LINE__, __METHOD__, 10);
        Debug::text('Class File Name: ' . $class_file_name, __FILE__, __LINE__, __METHOD__, 10);
        Debug::text('Class Name: ' . $class_name, __FILE__, __LINE__, __METHOD__, 10);

        if (file_exists($class_file_name)) {
            include_once($class_file_name);

            $obj = new $class_name;
            $obj->setClassDirectory($class_directory);
            $obj->default_font = TTi18n::getPDFDefaultFont();

            return $obj;
        } else {
            Debug::text('Class File does not exist!', __FILE__, __LINE__, __METHOD__, 10);
        }

        return false;
    }

    public function addForm($obj)
    {
        if (is_object($obj)) {
            $this->objs[] = $obj;

            return true;
        }

        return false;
    }

    public function Output($type)
    {
        $type = strtolower($type);

        //Initialize PDF object so all subclasses can access it.
        //Loop through all objects and combine the output from each into a single document.
        if ($type == 'pdf') {
            $pdf = new TTPDF();
            $pdf->setMargins(0, 0, 0, 0);
            $pdf->SetAutoPageBreak(false);
            //$pdf->setFontSubsetting(FALSE);

            foreach ((array)$this->objs as $obj) {
                $obj->setPDFObject($pdf);
                $obj->Output($type);
            }

            return $pdf->Output('', 'S');
        }
    }
}
