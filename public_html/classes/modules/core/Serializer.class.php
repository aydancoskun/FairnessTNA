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
 * @package Core
 */
//Use this class to serializer arrays in PHP, XML, and JSON formats.
class Serializer
{
    protected $available_formats = array('PHP', 'XML', 'JSON');
    protected $format = null;

    protected $simple_xml_obj = null;

    public function __construct($format = 'XML')
    {
        $format = strtoupper($format);

        if (in_array($format, $this->available_formats) == true) {
            $this->format = $format;
        }

        return true;
    }

    public function PHPSerialize($data)
    {
        return serialize($data);
    }

    public function PHPDeSerialize($data)
    {
        return deserialize($data);
    }

    public function JSONSerialize($data)
    {
        return json_encode($data);
    }

    public function JSONDeSerialize($data)
    {
        return json_decode($data);
    }

    public function XMLArrayWalkCallBack(&$value, $key, $tmp_xml)
    {
        $tmp_xml->addChild($key, $value);
    }

    public function XMLSerialize($data)
    {
        if (is_array($data)) {

            //The first level should be the class name as a key.
            /*
            //Example array:
            array
            'UserFactory' =>
                array
                0 =>
                    array
                    'id' => string '6217' (length=4)
                    'company_id' => string '1064' (length=4)
            */
            foreach ($data as $class => $objects) {
                $this->simple_xml_obj = new SimpleXMLElement('<fairness></fairness>');

                foreach ($objects as $value) {
                    $tmp_xml = $this->simple_xml_obj->addChild($class, '');

                    array_walk_recursive($value, array($this, 'XMLArrayWalkCallBack'), $tmp_xml);
                }
            }
        }

        $retval = $this->simple_xml_obj->asXML();
        unset($this->simple_xml_obj);

        return $retval;
    }

    public function XMLDeSerialize($data)
    {
        $xml = simplexml_load_string($data);
        if ($xml) {
            return $this->extractXML($xml);
        }
    }

    public function extractXML($xml)
    {
        if (!($xml->children())) {
            return (string)$xml;
        }

        $element = array();
        foreach ($xml->children() as $child) {
            $name = $child->getName();
            if (count($xml->$name) == 1) {
                $element[$name] = $this->extractXML($child);
            } else {
                $element[$name][] = $this->extractXML($child);
            }
        }

        return $element;
    }

    public function serialize($data)
    {
        $function = $this->format . 'Serialize';

        return $this->$function($data);
    }

    public function deserialize($data)
    {
        $function = $this->format . 'DeSerialize';

        return $this->$function($data);
    }
}
