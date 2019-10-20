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
class TTTree {
	/**
	 * Format flat array for JS tree grid.
	 * @param $nodes
	 * @param bool $include_root
	 * @return array
	 */
	static function FormatArray( $nodes, $include_root = TRUE ) {
		Debug::Text(' Formatting Array...', __FILE__, __LINE__, __METHOD__, 10);

		$nodes = self::createNestedArrayWithDepth( $nodes );

		if ( $include_root == TRUE ) {
			return array( 0 => array(	 'id' => '00000000-0000-0000-0000-000000000000',
										 'name' => TTi18n::getText('Root'),
										 'level' => 0,
										 'children' => $nodes )
			);
		} else {
			return $nodes;
		}

		return $nodes;
	}

	/**
	 * Flatten a nested array.
	 * @param $nodes
	 * @return array
	 */
	static function flattenArray( $nodes ) {
		$retarr = array();
		foreach ($nodes as $key => $node) {
			if ( isset($node['children']) ) {
				$retarr = array_merge( $retarr, self::flattenArray($node['children']) );
            	unset($node['children']);
            	$retarr[] = $node;
        	} else {
				$retarr[] = $node;
			}
		}

		return $retarr;
	}

	/**
	 * Get one specific element from all nodes in nested array.
	 * @param $nodes
	 * @param string $key
	 * @return array
	 */
	static function getElementFromNodes( $nodes, $key = 'id' ) {
		$retarr = array();
		if ( is_array($nodes ) ) {
			foreach( $nodes as $node ) {
				$retarr[] = $node[$key];
				if ( isset($node['children']) ) {
					$retarr[] = self::getElementFromNodes( $node['children'] );
				}
			}
		}

		return $retarr;
	}

	/**
	 * Get just the children of a specific parent.
	 * @param $nodes
	 * @param string $parent_id
	 * @return array
	 */
	static function getAllChildren( $nodes, $parent_id = '00000000-0000-0000-0000-000000000000' ) {
		$nodes = self::createNestedArrayWithDepth( $nodes, $parent_id );

		return $nodes;
	}

	/**
	 * @param $a
	 * @param $b
	 * @return int
	 */
	static function sortByName( $a, $b ) {
		if ( $a['name'] == $b['name'] ) {
			return 0;
		}
		return ( $a['name'] < $b['name'] ) ? -1 : 1;
	}

	/**
	 * Takes a flat array of nodes typically straight from the database and converts into a nested array with depth/level values.
	 * @param $nodes
	 * @param string $parent_id
	 * @param int $depth
	 * @return array
	 */
	static function createNestedArrayWithDepth( $nodes, $parent_id = '00000000-0000-0000-0000-000000000000', $depth = 1 ) {
		$retarr = array();

		if ( is_array($nodes ) ) {
			uasort( $nodes, array( 'self', 'sortByName' ) );
			foreach ( $nodes as $element ) {
				$element['level'] = $depth;
				if ( isset($element['parent_id']) AND isset($element['id']) AND $element['parent_id'] == $parent_id ) {
					$children = self::createNestedArrayWithDepth( $nodes, $element['id'], ( $depth + 1 ) );
					if ( $children ) {
						uasort( $children, array( 'self', 'sortByName' ) );
						$element['children'] = $children;
					}

					$retarr[] = $element;
				}
			}
		}

		return $retarr;
	}
}
?>
