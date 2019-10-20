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
 * @package Core
 */

//Use this class to serializer arrays in PHP, XML, and JSON formats.
class Serializer {
	protected $available_formats = array('PHP', 'XML', 'JSON');
	protected $format = NULL;

	protected $simple_xml_obj = NULL;

	/**
	 * Serializer constructor.
	 * @param string $format
	 */
	function __construct( $format = 'XML' ) {
		$format = strtoupper($format);

		if ( in_array($format, $this->available_formats) == TRUE ) {
			$this->format = $format;
		}

		return TRUE;
	}

	/**
	 * @param $data
	 * @return string
	 */
	function PHPSerialize( $data ) {
		return serialize( $data );
	}

	/**
	 * @param $data
	 * @return mixed
	 */
	function PHPDeSerialize( $data ) {
		return deserialize( $data );
	}

	/**
	 * @param $data
	 * @return string
	 */
	function JSONSerialize( $data ) {
		return json_encode( $data );
	}

	/**
	 * @param $data
	 * @return mixed
	 */
	function JSONDeSerialize( $data ) {
		return json_decode( $data );
	}

	/**
	 * @param $xml
	 * @return array|string
	 */
	function extractXML( $xml) {
		if (! ( $xml->children() ) ) {
			return (string)$xml;
		}

		$element = array();
		foreach ( $xml->children() as $child ) {
			$name = $child->getName();
			if ( count($xml->$name) == 1 ) {
				$element[$name] = $this->extractXML($child);
			} else {
				$element[$name][] = $this->extractXML($child);
			}
		}

		return $element;
	}

	/**
	 * @param $value
	 * @param $key
	 * @param $tmp_xml
	 */
	function XMLArrayWalkCallBack( &$value, $key, $tmp_xml ) {
		$tmp_xml->addChild( $key, $value );
	}

	/**
	 * @param $data
	 * @return mixed
	 */
	function XMLSerialize( $data ) {
		if ( is_array( $data ) ) {

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
			foreach( $data as $class => $objects ) {
				$this->simple_xml_obj = new SimpleXMLElement('<fairnesstna></fairnesstna>');

				foreach( $objects as $value ) {
					$tmp_xml = $this->simple_xml_obj->addChild( $class, '' );

					array_walk_recursive( $value, array( $this, 'XMLArrayWalkCallBack' ), $tmp_xml );
				}
			}
		}

		$retval = $this->simple_xml_obj->asXML();
		unset($this->simple_xml_obj);

		return $retval;
	}

	/**
	 * @param $data
	 * @return array|string
	 */
	function XMLDeSerialize( $data ) {
		$xml = simplexml_load_string( $data );
		if ( $xml ) {
			return $this->extractXML( $xml );
		}

		return FALSE;
	}

	/**
	 * @param $data
	 * @return mixed
	 */
	function serialize( $data ) {
		$function = $this->format.'Serialize';

		return $this->$function($data);
	}

	/**
	 * @param $data
	 * @return mixed
	 */
	function deserialize( $data ) {
		$function = $this->format.'DeSerialize';

		return $this->$function($data);
	}
}
?>
