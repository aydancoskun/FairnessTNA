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
class UnitConvert {
	/*
		This class is used to convert units, ie:
		pounds (lbs) to grams (g)
		inches (in) to meters (m)
		miles (mi) to kiliometers (km)
	*/

	//Convert weight units to grams, first.
	//Convert dimension units to mm, first.
	//Handle square and cubic (exponent) calculations as well.
	static $units = array(
						// 1 Unit = X G
						'oz' => 28.349523125,
						'lb' => 453.59237,
						'lbs' => 453.59237,
						'g'	 => 1,
						'kg' => 1000,

						//1 Unit = X MM
						'mm' => 1,
						'in' => 25.4,
						'cm' => 10,
						'ft' => 304.8,
						'm' => 1000,
						'km' => 1000000,
						'mi' => 1609344,
					);

	//Only units in the same array can be converted to one another.
	static $valid_unit_groups = array(
									'g' => array('g', 'oz', 'lb', 'lbs', 'kg'),
									'mm' => array('mm', 'in', 'cm', 'ft', 'm', 'km', 'mi')
									);

	/**
	 * @param $src_unit
	 * @param $dst_unit
	 * @param $measurement
	 * @param int $exponent
	 * @return bool|float|int
	 */
	static function convert( $src_unit, $dst_unit, $measurement, $exponent = 1 ) {
		$src_unit = strtolower($src_unit);
		$dst_unit = strtolower($dst_unit);

		if ( !isset(self::$units[$src_unit]) ) {
			return FALSE;
		}
		if ( !isset(self::$units[$dst_unit]) ) {
			return FALSE;
		}

		if (  $src_unit == $dst_unit ) {
			return $measurement;
		}

		//Make sure we can convert from one unit to another.
		$valid_conversion = FALSE;
		foreach( self::$valid_unit_groups as $valid_units ) {
			if ( in_array($src_unit, $valid_units) AND in_array($dst_unit, $valid_units) ) {
				//Valid conversion
				$valid_conversion = TRUE;
			}
		}

		if ( $valid_conversion == FALSE ) {
			return FALSE;
		}

		$base_measurement = ( pow( self::$units[$src_unit], $exponent) * $measurement );
		//Debug::Text(' Base Measurement: '. $base_measurement, __FILE__, __LINE__, __METHOD__, 10);
		if ( $base_measurement != 0 ) {
			$retval = ( (1 / pow(self::$units[$dst_unit], $exponent) ) * $base_measurement );

			return $retval;
		}

		return FALSE;
	}
}
?>