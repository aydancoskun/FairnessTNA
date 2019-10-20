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
class PermissionListFactory extends PermissionFactory implements IteratorAggregate {
	/**
	 * @param int $limit Limit the number of records returned
	 * @param int $page Page number of records to return for pagination
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return $this
	 */
	function getAll( $limit = NULL, $page = NULL, $where = NULL, $order = NULL) {
		$query = '
					select	*
					from	'. $this->getTable() .'
						WHERE deleted = 0';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );

		$this->rs = $this->ExecuteSQL( $query, NULL, $limit, $page );

		return $this;
	}

	/**
	 * @param string $id UUID
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool|PermissionListFactory
	 */
	function getById( $id, $where = NULL, $order = NULL) {
		if ( $id == '') {
			return FALSE;
		}

		$ph = array(
					'id' => TTUUID::castUUID($id),
					);


		$query = '
					select	*
					from	'. $this->getTable() .'
					where	id = ?
						AND deleted = 0';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );

		$this->rs = $this->ExecuteSQL( $query, $ph );

		return $this;
	}

	/**
	 * @param string $company_id UUID
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool|PermissionListFactory
	 */
	function getByCompanyId( $company_id, $where = NULL, $order = NULL) {
		if ( $company_id == '') {
			return FALSE;
		}

		$ph = array(
					'company_id' => TTUUID::castUUID($company_id),
					);

		$pcf = new PermissionControlFactory();

		$query = '
					select	a.*
					from	'. $this->getTable() .' as a,
							'. $pcf->getTable() .' as b
					where	b.id = a.permission_control_id
						AND b.company_id = ?
						AND ( a.deleted = 0 AND b.deleted = 0 )';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );

		$this->rs = $this->ExecuteSQL( $query, $ph );

		return $this;
	}

	/**
	 * @param string $company_id UUID
	 * @param string $permission_control_id UUID
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool|PermissionListFactory
	 */
	function getByCompanyIdAndPermissionControlId( $company_id, $permission_control_id, $where = NULL, $order = NULL) {
		if ( $company_id == '') {
			return FALSE;
		}

		if ( $permission_control_id == '') {
			return FALSE;
		}

		$ph = array(
					'company_id' => TTUUID::castUUID($company_id),
					'permission_control_id' => TTUUID::castUUID($permission_control_id),
					);

		$pcf = new PermissionControlFactory();

		$query = '
					select	a.*
					from	'. $this->getTable() .' as a,
							'. $pcf->getTable() .' as b
					where	b.id = a.permission_control_id
						AND b.company_id = ?
						AND a.permission_control_id = ?
						AND ( a.deleted = 0 AND b.deleted = 0 )';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );

		$this->rs = $this->ExecuteSQL( $query, $ph );

		return $this;
	}

	/**
	 * @param string $company_id UUID
	 * @param string $permission_control_id UUID
	 * @param $section
	 * @param $name
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool|PermissionListFactory
	 */
	function getByCompanyIdAndPermissionControlIdAndSectionAndName( $company_id, $permission_control_id, $section, $name, $where = NULL, $order = NULL) {
		if ( $company_id == '') {
			return FALSE;
		}

		if ( $permission_control_id == '') {
			return FALSE;
		}

		if ( $section == '') {
			return FALSE;
		}

		if ( $name == '') {
			return FALSE;
		}

		$ph = array(
					'company_id' => TTUUID::castUUID($company_id),
					'permission_control_id' => TTUUID::castUUID($permission_control_id),
					'section' => $section,
					//'name' => $name, //Allow a list of names.
					);

		$pcf = new PermissionControlFactory();

		$query = '
					select	a.*
					from	'. $this->getTable() .' as a,
							'. $pcf->getTable() .' as b
					where	b.id = a.permission_control_id
						AND b.company_id = ?
						AND a.permission_control_id = ?
						AND a.section = ?
						AND a.name in ('. $this->getListSQL($name, $ph) .')
						AND ( a.deleted = 0 AND b.deleted = 0)';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );

		$this->rs = $this->ExecuteSQL( $query, $ph );

		return $this;
	}

	/**
	 * @param string $company_id UUID
	 * @param string $permission_control_id UUID
	 * @param $section
	 * @param $name
	 * @param $value
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool|PermissionListFactory
	 */
	function getByCompanyIdAndPermissionControlIdAndSectionAndNameAndValue( $company_id, $permission_control_id, $section, $name, $value, $where = NULL, $order = NULL) {
		if ( $company_id == '') {
			return FALSE;
		}

		if ( $permission_control_id == '') {
			return FALSE;
		}

		if ( $section == '') {
			return FALSE;
		}

		if ( $name == '') {
			return FALSE;
		}

		$ph = array(
					'company_id' => TTUUID::castUUID($company_id),
					'permission_control_id' => TTUUID::castUUID($permission_control_id),
					'section' => $section,
					'value' => (int)$value,
					//'name' => $name, //Allow a list of names.
					);

		$pcf = new PermissionControlFactory();

		$query = '
					select	a.*
					from	'. $this->getTable() .' as a,
							'. $pcf->getTable() .' as b
					where	b.id = a.permission_control_id
						AND b.company_id = ?
						AND a.permission_control_id = ?
						AND a.section = ?
						AND a.value = ?
						AND a.name in ('. $this->getListSQL($name, $ph) .')
						AND ( a.deleted = 0 AND b.deleted = 0)';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );

		$this->rs = $this->ExecuteSQL( $query, $ph );

		return $this;
	}

	/**
	 * @param string $company_id UUID
	 * @param $section
	 * @param int $date EPOCH
	 * @param array $valid_ids
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool|PermissionListFactory
	 */
	function getByCompanyIdAndSectionAndDateAndValidIDs( $company_id, $section, $date = NULL, $valid_ids = array(), $where = NULL, $order = NULL) {
		if ( $company_id == '') {
			return FALSE;
		}

		if ( $section == '') {
			return FALSE;
		}

		if ( $date == '') {
			$date = 0;
		}

		$ph = array(
					'company_id' => TTUUID::castUUID($company_id),
					);

		$pcf = new PermissionControlFactory();

		$query = '
					select	a.*
					from	'. $this->getTable() .' as a,
							'. $pcf->getTable() .' as b
					where	b.id = a.permission_control_id
						AND b.company_id = ?
						AND (
								(
								a.section in ('. $this->getListSQL($section, $ph) .') ';

		//When the Mobile App/TimeClock are doing a reload database, $date should always be 0. That forces the query to just send data for $valid_user_ids.
		//  All other cases it will send data for all current users always, or records that were recently created/updated.
		if ( isset($date) AND $date > 0 ) {
			//Append the same date twice for created and updated.
			$ph[] = (int)$date;
			$ph[] = (int)$date;
			$query	.=	'		AND ( a.created_date >= ? OR a.updated_date >= ? ) ) ';
		} else {
			$query	.=	' ) ';
		}

		if ( isset($valid_ids) AND is_array($valid_ids) AND count($valid_ids) > 0 ) {
			$query	.=	' OR a.id in ('. $this->getListSQL( $valid_ids, $ph, 'uuid') .') ';
		}

		$query .= '	)
						AND ( a.deleted = 0 AND b.deleted = 0)';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );

		$this->rs = $this->ExecuteSQL( $query, $ph );

		return $this;
	}

	/**
	 * @param string $company_id UUID
	 * @param string $user_id UUID
	 * @return bool|PermissionListFactory
	 */
	function getAllPermissionsByCompanyIdAndUserId( $company_id, $user_id) {
		if ( $company_id == '') {
			return FALSE;
		}

		if ( $user_id == '') {
			return FALSE;
		}

		$ph = array(
					'company_id' => TTUUID::castUUID($company_id),
					'user_id' => TTUUID::castUUID($user_id),
					);

		$pcf = new PermissionControlFactory();
		$puf = new PermissionUserFactory();

		$query = '
					select	a.*,
							b.level as level
					from	'. $this->getTable() .' as a,
							'. $pcf->getTable() .' as b,
							'. $puf->getTable() .' as c
					where b.id = a.permission_control_id
						AND b.id = c.permission_control_id
						AND b.company_id = ?
						AND	c.user_id = ?
						AND ( a.deleted = 0 AND b.deleted = 0 )
				';

		$this->rs = $this->ExecuteSQL( $query, $ph );

		return $this;
	}

	/**
	 * @param string $company_id UUID
	 * @param int $date EPOCH
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool
	 */
	function getIsModifiedByCompanyIdAndDate( $company_id, $date, $where = NULL, $order = NULL) {
		if ( $company_id == '') {
			return FALSE;
		}

		if ( $date == '') {
			return FALSE;
		}

		$ph = array(
					'company_id' => TTUUID::castUUID($company_id),
					'created_date' => $date,
					'updated_date' => $date,
					'deleted_date' => $date,
					);

		$pcf = new PermissionControlFactory();

		//INCLUDE Deleted rows in this query.
		$query = '
					select	a.*
					from	'. $this->getTable() .' as a,
							'. $pcf->getTable() .' as b
					where
							b.company_id = ?
						AND
							( a.created_date >=	 ? OR a.updated_date >= ? OR ( a.deleted = 1 AND a.deleted_date >= ? ) )
					';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );

		$this->rs = $this->db->SelectLimit($query, 1, -1, $ph);
		if ( $this->getRecordCount() > 0 ) {
			Debug::text('Rows have been modified: '. $this->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);

			return TRUE;
		}
		Debug::text('Rows have NOT been modified', __FILE__, __LINE__, __METHOD__, 10);
		return FALSE;
	}
}
?>
