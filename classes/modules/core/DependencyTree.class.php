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
class DependencyTree {
	/*
		Take a look at PEAR: Structures_Graph
	*/

	var $raw_data = NULL;
	var $raw_data_order = array();

	protected $provide_id_raw_data = array();
	protected $require_id_raw_data = array();

	protected $provide_ids = NULL;

	protected $tree = NULL;


	// set this flag to true to enable tree ordering, eg, the final output will have whole trees in contiguous array slice.
	protected $tree_ordering = FALSE; // faster without tree ordering.

	/**
	 * @return bool
	 */
	function getTreeOrdering() {
		return $this->tree_ordering;
	}

	/**
	 * @param $bool
	 */
	function setTreeOrdering( $bool ) {
		$this->tree_ordering = $bool;
	}

	/**
	 * @param string $id ID of node
	 * @param string|int|array $requires array of IDs this node requires
	 * @param string|int|array $provides array of IDs this node provides
	 * @param int $order integer to help resolve circular dependencies, lower order comes first.
	 * @return bool
	 */
	function addNode( $id, $requires, $provides, $order = 0 ) {
		if ( $id == '' ) {
			return FALSE;
		}

		if ( isset( $this->raw_data[ $id ] ) ) {
			//ID already exists.
			return FALSE;
		}

		$dtn = new DependencyTreeNode();
		$dtn->setId( $id );
		$dtn->setRequires( $requires );
		$dtn->setProvides( $provides );
		$dtn->setOrder( $order );
		$dtn->removeCircularDependency();

		$this->addProvideIDs( $dtn->getProvides() );
		$this->addObjectByProvideIDs( $dtn->getProvides(), $dtn );
		$this->addObjectByRequireIDs( $dtn->getRequires(), $dtn );

		$this->raw_data[ $id ] = $dtn;
		if ( $this->tree_ordering ) {
			array_push( $this->raw_data_order, $dtn );
		}

		unset( $dtn );


		return TRUE;
	}

	/**
	 * @param string $provide_ids UUID
	 * @param object $obj
	 * @return bool
	 */
	private function addObjectByProvideIDs( $provide_ids, $obj ) {
		if ( is_array( $provide_ids ) ) {
			foreach ( $provide_ids as $provide_id ) {
				$this->provide_id_raw_data[ $provide_id ][] = $obj;
			}
		}

		return TRUE;
	}

	/**
	 * @param string $requires_ids UUID
	 * @param object $obj
	 * @return bool
	 */
	private function addObjectByRequireIDs( $requires_ids, $obj ) {
		if ( is_array( $requires_ids ) ) {
			foreach ( $requires_ids as $require_id ) {
				$this->require_id_raw_data[ $require_id ][] = $obj;
			}
		}

		return TRUE;
	}

	/**
	 * @return bool|null
	 */
	private function getProvideIDs() {
		if ( isset( $this->provide_ids ) ) {
			return $this->provide_ids;
		}

		return FALSE;
	}

	/**
	 * @param $provide_arr
	 * @return bool
	 */
	private function addProvideIDs( $provide_arr ) {
		if ( is_array( $provide_arr ) ) {
			foreach ( $provide_arr as $provide_id ) {
				$this->provide_ids[] = $provide_id;
			}
		}

		return TRUE;
	}

	/**
	 * @return bool
	 */
	private function deleteOrphanRequireIDs() {
		if ( is_array( $this->raw_data ) ) {
			foreach ( $this->raw_data as $obj ) {
				if ( is_array( $obj->getRequires() ) ) {
					$valid_require_ids = array();
					foreach ( $obj->getRequires() as $require_id ) {
						if ( in_array( $require_id, (array)$this->getProvideIDs() ) ) {
							$valid_require_ids[] = $require_id;
						}
					}
					$obj->setRequires( $valid_require_ids );
				}
			}
		}

		//Debug::Arr($this->raw_data, 'With Valid Require Ids', __FILE__, __LINE__, __METHOD__, 10);

		return TRUE;
	}

	/**
	 * 02-Nov-2006: changing the sort functionality to depth-based
	 * @param $a
	 * @param $b
	 * @return int
	 */
	private function sort( $a, $b ) {
		//Debug::Arr($a, 'A: ', __FILE__, __LINE__, __METHOD__, 10);
		//Debug::Arr($b, 'B: ', __FILE__, __LINE__, __METHOD__, 10);

		// first compare if nodes are in the same tree
		if ( $this->tree_ordering ) {
			if ( $a->getTreeNumber() < $b->getTreeNumber() ) {
				return -1;
			} elseif ( $a->getTreeNumber() > $b->getTreeNumber() ) {
				return 1;
			}
		}

		// sort by depth first
		if ( $a->getDepth() < $b->getDepth() ) {
			return -1;
		}
		if ( $a->getDepth() > $b->getDepth() ) {
			return 1;
		}

		// if depth is the same, then they are either: different graphs, same graph but in a circular reference loop (or just another branch.)
		// sort by order, if ==, then sort by id.
		$order_cmp = strnatcasecmp( $a->getOrder(), $b->getOrder() );
		if ( $order_cmp !== 0 ) {
			return $order_cmp;
		}

		// nothing left, sort by id, but use strnatcasecmp to handle UUIDs.
		return strnatcasecmp( $a->getId(), $b->getId() );

		// should probably never reach here, but if the ids are the same, they might as well be equal.
		return 0;
	}

	/**
	 * Traverse a tree starting with a node.
	 * @param $node
	 * @param $tree_number
	 * @param array $marked_edges
	 */
	function markTreeNumber( $node, $tree_number, $marked_edges = array() ) {
		// mark the node. but should we check to see if it was marked under another tree number?
		if ( $node->getTreeNumber() !== NULL ) {
			return;
		}
		$node->setTreeNumber( $tree_number );

		// first look to see if any other node gives what this node requires
		if ( is_array( $node->getRequires() ) ) {
			foreach ( $node->getRequires() as $require_id ) {
				if ( isset( $this->provide_id_raw_data[ $require_id ] ) ) {
					foreach ( $this->provide_id_raw_data[ $require_id ] as $obj ) { // (we already know obj provides this req id...)
						if ( $node->getId() != $obj->getId() ) {
							if ( !isset( $marked_edges[ $node->getId() ][ $obj->getId() ] ) ) {
								$marked_edges[ $node->getId() ][ $obj->getId() ] = TRUE;
								$marked_edges[ $obj->getId() ][ $node->getId() ] = TRUE;
								$this->markTreeNumber( $obj, $tree_number, $marked_edges );
							}
						}
					}
				}
			}
		}

		// now vice versa
		if ( is_array( $node->getProvides() ) ) {
			foreach ( $node->getProvides() as $provide_id ) {
				if ( isset( $this->require_id_raw_data[ $provide_id ] ) ) {
					foreach ( $this->require_id_raw_data[ $provide_id ] as $obj ) { // (we already know obj provides this req id...)
						if ( $node->getId() != $obj->getId() ) {
							if ( !isset( $marked_edges[ $node->getId() ][ $obj->getId() ] ) ) {
								$marked_edges[ $node->getId() ][ $obj->getId() ] = TRUE;
								$marked_edges[ $obj->getId() ][ $node->getId() ] = TRUE;
								$this->markTreeNumber( $obj, $tree_number, $marked_edges );
							}
						}
					}
				}
			}
		}
		// we're done if after all the recursion we end up here.
	}

	/**
	 * Get an object's depth by traversing all its parents (recursively) until there are no edges left. The count of edges is the 'depth'.
	 * @param object $obj
	 * @param array $marked_edges
	 * @param int $depth
	 * @return int
	 */
	function _findDepth( $obj, &$marked_edges = array(), $depth = 0 ) {
		if ( is_array( $obj->getRequires() ) ) {
			foreach ( $obj->getRequires() as $req_id ) {
				if ( isset( $this->provide_id_raw_data[ $req_id ] ) ) {
					foreach ( $this->provide_id_raw_data[ $req_id ] as $node ) { // (we already know obj provides this req id...)
						if ( !isset( $marked_edges[ $node->getId() ][ $obj->getId() ] ) ) {
							$marked_edges[ $node->getId() ][ $obj->getId() ] = TRUE;
							$this->_findDepth( $node, $marked_edges, ( $depth + 1 ) );
						}
					}
				}
			}
		}

		if ( $depth == 0 ) {
			return count( $marked_edges );
		}

		return FALSE;
	}

	/**
	 * @return array|bool
	 */
	function _buildTree() {
		if ( !is_array( $this->raw_data ) ) {
			return FALSE;
		}

		$this->deleteOrphanRequireIDs();

		if ( $this->tree_ordering ) {
			// now number the trees so that the algorithm knows how to sort them properly
			// eg the list of nodes might have 5 in one tree, and another unconnected tree with 3 nodes.
			// this needs to be handled properly.
			$treenumber = 0;
			foreach ( $this->raw_data_order as $obj ) {
				if ( $obj->getTreeNumber() === NULL ) {
					$this->markTreeNumber( $obj, $treenumber++ );
				}
			}
		}

		//Debug::Arr($this, 'Before - Raw Data: ', __FILE__, __LINE__, __METHOD__, 10);

		// mark all depths first.
		foreach ( $this->raw_data as $obj ) {
			$obj->setDepth( $this->_findDepth( $obj ) );
		}

		usort( $this->raw_data, array($this, 'sort') );

		//Debug::Arr($this->provide_id_raw_data, 'provides, raw', __FILE__, __LINE__, __METHOD__, 10);
		//Debug::Arr($this, 'After - Raw Data: ', __FILE__, __LINE__, __METHOD__, 10);

		$retarr = array();
		foreach ( $this->raw_data as $obj ) {
			$retarr[] = $obj->getId();
		}

		//Debug::Arr($retarr, 'Dependency Tree Final Result!!', __FILE__, __LINE__, __METHOD__, 10);

		return $retarr;
	}

	/**
	 * @return array
	 */
	function getAllNodesInOrder() {
		return $this->_buildTree();
	}
}


/**
 * @package Core
 */
class DependencyTreeNode {
	protected $data;

	/**
	 * @param string $id UUID
	 * @return bool
	 */
	function setId( $id ) {
		if ( $id != '' ) {
			$this->data['id'] = $id;
		}

		return FALSE;
	}

	/**
	 * @return bool
	 */
	function getId() {
		if ( isset( $this->data['id'] ) ) {
			return $this->data['id'];
		}

		return FALSE;
	}

	/**
	 * @param $arg
	 * @return bool
	 */
	function setDepth( $arg ) {
		$this->data['depth'] = (int)$arg;

		return TRUE;
	}

	/**
	 * @return null
	 */
	function getDepth() {
		if ( isset( $this->data['depth'] ) ) {
			return $this->data['depth'];
		}

		return NULL;
	}

	/**
	 * @param $arr
	 * @return bool
	 */
	function setRequires( $arr ) {
		if ( $arr != '' ) {
			if ( !is_array( $arr ) ) {
				$arr = array($arr);
			}

			$this->data['requires'] = array_unique( $arr );
		}

		return FALSE;
	}

	/**
	 * @return bool
	 */
	function getRequires() {
		if ( isset( $this->data['requires'] ) ) {
			return $this->data['requires'];
		}

		return FALSE;
	}

	/**
	 * @param $arr
	 * @return bool
	 */
	function setProvides( $arr ) {
		if ( $arr != '' ) {
			if ( !is_array( $arr ) ) {
				$arr = array($arr);
			}

			$this->data['provides'] = array_unique( $arr );
		}

		return FALSE;
	}


	/**
	 * Removes circular dependencies within the same node. Must be run after both requires and provides are defined.
	 * @return bool
	 */
	function removeCircularDependency() {
		//Check to see if any item in $requires also appears in $provides, if so strip them out as it creates a circular dependency within the same node.
		if ( is_array( $this->getRequires() ) AND is_array( $this->getProvides() ) ) {
			$this->data['requires'] = array_diff( $this->getRequires(), $this->getProvides() );
		}

		return TRUE;
	}

	/**
	 * @return bool
	 */
	function getProvides() {
		if ( isset( $this->data['provides'] ) ) {
			return $this->data['provides'];
		}

		return FALSE;
	}

	/**
	 * @param $treenumber
	 * @return bool
	 */
	function setTreeNumber( $treenumber ) {
		$this->data['treenumber'] = (int)$treenumber;

		return TRUE;
	}

	/**
	 * @return null
	 */
	function getTreeNumber() {
		if ( isset( $this->data['treenumber'] ) ) {
			return $this->data['treenumber'];
		}

		return NULL;
	}

	/**
	 * @param $order
	 * @return bool
	 */
	function setOrder( $order ) {
		$this->data['order'] = $order; //Allow int/strings.

		return TRUE;
	}

	/**
	 * @return int
	 */
	function getOrder() {
		if ( isset( $this->data['order'] ) ) {
			return $this->data['order'];
		}

		return 0;
	}
}

?>