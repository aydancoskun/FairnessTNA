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
 * @package Modules\Help
 */
class HelpListFactory extends HelpFactory implements IteratorAggregate {

	/**
	 * @param int $limit Limit the number of records returned
	 * @param int $page Page number of records to return for pagination
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return $this
	 */
	function getAll( $limit = NULL, $page = NULL, $where = NULL, $order = NULL) {

		$strict_order = TRUE;
		if ( $order == NULL ) {
			$order = array('created_date' => 'desc');
			//$strict_order = FALSE;
		}

		$query = '
					select	*
					from	'. $this->getTable() .'
					WHERE deleted=0';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order, $strict_order );

		$this->rs = $this->ExecuteSQL( $query, NULL, $limit, $page );

		return $this;
	}

	/**
	 * @param string $id UUID
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool|HelpListFactory
	 */
	function getById( $id, $where = NULL, $order = NULL) {
		if ( $id == '' ) {
			return FALSE;
		}

		$ph = array(
					'id' => TTUUID::castUUID($id),
					);


		$query = '
					select	*
					from	'. $this->getTable() .'
					where	id = ?
						AND deleted=0';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );

		$this->rs = $this->ExecuteSQL( $query, $ph );

		return $this;
	}

	/**
	 * @param $script_name
	 * @param null $group_name
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool|HelpListFactory
	 */
	function getByScriptNameAndGroupName( $script_name, $group_name = NULL, $where = NULL, $order = NULL) {
		if ( $script_name == '' AND $group_name == '' ) {
			return FALSE;
		}

		$hgcf = new HelpGroupControlFactory();
		$hg = new HelpGroupFactory();

		$ph = array(
					'script_name' => $script_name,
					'group_name' => $group_name,
					);

		$query = '
					select	a.*
					from	'. $this->getTable() .' as a,
							'. $hgcf->getTable() .' as b,
							'. $hg->getTable() .' as c
					where	b.id = c.help_group_control_id
							AND c.help_id = a.id
						';
		//if ( $script_name != '' ) {
			$query .= ' AND b.script_name = ?';
		//}

		//if ( $group_name != '') {
			$query .= ' AND b.name = ?';
		//}

		$query .= ' AND a.deleted=0
					AND b.deleted=0
					ORDER BY c.order_value asc';

		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );

		$this->rs = $this->ExecuteSQL( $query, $ph );

		return $this;
	}

	/**
	 * @param $script_name
	 * @param $type
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool|HelpListFactory
	 */
	function getByScriptNameAndType( $script_name, $type, $where = NULL, $order = NULL) {
		if ( $script_name == '') {
			return FALSE;
		}

		if ( $type == '') {
			return FALSE;
		}

		$type_key = Option::getByValue($type, $this->getOptions('type') );
		if ($type_key !== FALSE) {
			$type = $type_key;
		}

		$hgcf = new HelpGroupControlFactory();
		$hg = new HelpGroupFactory();

		$ph = array(
					'script_name' => $script_name,
					'type_id' => $type,
					);

		$query = '
					select	a.*, b.name as group_name
					from	'. $this->getTable() .' as a,
							'. $hgcf->getTable() .' as b,
							'. $hg->getTable() .' as c
					where	b.id = c.help_group_control_id
							AND c.help_id = a.id
							AND b.script_name = ?
							AND a.type_id = ?
							AND a.deleted=0
							AND b.deleted=0
					ORDER BY c.order_value asc
						';

		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );

		$this->rs = $this->ExecuteSQL( $query, $ph );

		return $this;
	}

	/**
	 * @param $script_name
	 * @param $type
	 * @param $status
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool|HelpListFactory
	 */
	function getByScriptNameAndTypeAndStatus( $script_name, $type, $status, $where = NULL, $order = NULL) {
		if ( $script_name == '') {
			return FALSE;
		}

		if ( $type == '') {
			return FALSE;
		}

		if ( $status == '') {
			return FALSE;
		}

		$type_key = Option::getByValue($type, $this->getOptions('type') );
		if ($type_key !== FALSE) {
			$type = $type_key;
		}

		$hgcf = new HelpGroupControlFactory();
		$hg = new HelpGroupFactory();

		$ph = array(
					'script_name' => $script_name,
					'type_id' => $type,
					'status_id' => $status,
					);

		$query = '
					select	a.*, b.name as group_name
					from	'. $this->getTable() .' as a,
							'. $hgcf->getTable() .' as b,
							'. $hg->getTable() .' as c
					where	b.id = c.help_group_control_id
							AND c.help_id = a.id
							AND b.script_name = ?
							AND a.type_id = ?
							AND a.status_id = ?
							AND a.deleted=0
							AND b.deleted=0
					ORDER BY c.order_value asc
						';

		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );

		$this->rs = $this->ExecuteSQL( $query, $ph );

		return $this;
	}

	/**
	 * @param $script_name
	 * @param $status
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool|HelpListFactory
	 */
	function getByScriptNameAndStatus( $script_name, $status, $where = NULL, $order = NULL) {
		if ( $script_name == '') {
			return FALSE;
		}

		if ( $status == '') {
			return FALSE;
		}

		$status_key = Option::getByValue($status, $this->getOptions('status') );
		if ($status_key !== FALSE) {
			$status = $status_key;
		}

		$hgcf = new HelpGroupControlFactory();
		$hg = new HelpGroupFactory();

		$ph = array(
					'script_name' => $script_name,
					'status_id' => $status,
					);

		$query = '
					select	a.*, b.name as group_name
					from	'. $this->getTable() .' as a,
							'. $hgcf->getTable() .' as b,
							'. $hg->getTable() .' as c
					where	b.id = c.help_group_control_id
							AND c.help_id = a.id
							AND b.script_name = ?
							AND a.status_id = ?
							AND a.deleted=0
							AND b.deleted=0
					ORDER BY a.type_id desc, c.order_value asc
						';

		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );

		$this->rs = $this->ExecuteSQL( $query, $ph );

		return $this;
	}

	/**
	 * @return mixed
	 */
	function getAllArray() {
		$hlf = new HelpListFactory();
		$hlf->getAll();

		$help_list[TTUUID::getZeroID()] = '--';

		foreach ($hlf as $help) {
			$help_list[$help->getID()] = '('. $help->getID() .') ['. Option::getByKey($help->getType(), $help->getOptions('type') ) .'] '. $help->getHeading();
		}

		return $help_list;
	}
}
?>
