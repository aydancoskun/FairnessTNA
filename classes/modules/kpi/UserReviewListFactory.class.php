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
 * @package Modules\KPI
 */
class UserReviewListFactory extends UserReviewFactory implements IteratorAggregate
{

	/**
	 * @param int $limit Limit the number of records returned
	 * @param int $page Page number of records to return for pagination
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return $this
	 */
	function getAll( $limit = NULL, $page = NULL, $where = NULL, $order = NULL ) {

		$urcf = new UserReviewControlFactory();
		$query = '
					select	a.*
					from	' . $this->getTable() . ' as a
					LEFT JOIN ' . $urcf->getTable() . ' as urcf ON ( a.user_review_control_id = urcf.id )
					WHERE a.deleted = 0 AND urcf.deleted = 0';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );
		$this->rs = $this->ExecuteSQL( $query, NULL, $limit, $page );

		return $this;
	}

	/**
	 * @param string $id UUID
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool|UserReviewListFactory
	 */
	function getById( $id, $where = NULL, $order = NULL ) {

		if ( $id == '' ) {
			return FALSE;
		}
		$urcf = new UserReviewControlFactory();
		$this->rs = $this->getCache( $id );
		if ( $this->rs === FALSE ) {
			$ph = array( 'id' => TTUUID::castUUID($id), );
			$query = '
						select	a.*
						from	' . $this->getTable() . ' as a
						LEFT JOIN ' . $urcf->getTable() . ' as urcf ON ( a.user_review_control_id = urcf.id )
						where	a.id = ?
							AND a.deleted = 0 AND urcf.deleted = 0';
			$query .= $this->getWhereSQL( $where );
			$query .= $this->getSortSQL( $order );
			$this->rs = $this->ExecuteSQL( $query, $ph );
			$this->saveCache( $this->rs, $id );
		}

		return $this;
	}

	/**
	 * @param string $id UUID
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool|UserReviewListFactory
	 */
	function getByUserReviewControlId( $id, $order = NULL ) {

		if ( $id == '' ) {
			return FALSE;
		}
		$urcf = new UserReviewControlFactory();
		$ph = array( 'user_review_control_id' => TTUUID::castUUID($id), );
		$query = '
					select	a.*
					from	' . $this->getTable() . ' as a
					LEFT JOIN ' . $urcf->getTable() . ' as urcf ON ( a.user_review_control_id = urcf.id )
					where	a.user_review_control_id = ?
						AND a.deleted = 0 AND urcf.deleted = 0';
		$query .= $this->getSortSQL( $order );
		$this->rs = $this->ExecuteSQL( $query, $ph );

		return $this;
	}


	/**
	 * @param string $id UUID
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool|UserReviewListFactory
	 */
	function getByKpiId( $id, $order = NULL ) {

		if ( $id == '' ) {
			return FALSE;
		}
		$urcf = new UserReviewControlFactory();
		$ph = array( 'kpi_id' => TTUUID::castUUID($id), );
		$query = '
                    select  *
                    from    ' . $this->getTable() . ' as a
                    LEFT JOIN ' . $urcf->getTable() . ' as urcf ON ( a.user_review_control_id = urcf.id )
                    where  a.kpi_id = ?
                        AND a.deleted = 0 AND urcf.deleted = 0';
		$query .= $this->getSortSQL( $order );
		$this->rs = $this->ExecuteSQL( $query, $ph );

		return $this;
	}

	/**
	 * @param string $company_id UUID
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool|UserReviewListFactory
	 */
	function getByCompanyId( $company_id, $where = NULL, $order = NULL ) {

		if ( $company_id == '' ) {
			return FALSE;
		}
		$kf = new KPIFactory();
		$urcf = new UserReviewControlFactory();
		$ph = array( 'company_id' => TTUUID::castUUID($company_id), );
		$query = '
					select	a.*
					from	' . $this->getTable() . ' as a
					LEFT JOIN  ' . $kf->getTable() . ' as k ON ( a.kpi_id = k.id AND k.deleted = 0 )
					LEFT JOIN ' . $urcf->getTable() . ' as  urcf ON ( a.user_review_control_id = urcf.id )
					where	k.company_id = ?
						AND a.deleted = 0 AND urcf.deleted = 0';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );
		$this->rs = $this->ExecuteSQL( $query, $ph );

		return $this;
	}

	/**
	 * @param string $id UUID
	 * @param string $company_id UUID
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool|UserReviewListFactory
	 */
	function getByIdAndCompanyId( $id, $company_id, $where = NULL, $order = NULL ) {

		if ( $id == '' ) {
			return FALSE;
		}
		if ( $company_id == '' ) {
			return FALSE;
		}
		$kf = new KPIFactory();
		$urcf = new UserReviewControlFactory();
		$ph = array( 'id' => TTUUID::castUUID($id), 'company_id' => TTUUID::castUUID($company_id), );
		$query = '
					select	a.*
					from	' . $this->getTable() . ' as a
						LEFT JOIN  ' . $kf->getTable() . ' as k ON ( a.kpi_id = k.id AND k.deleted = 0 )
						LEFT JOIN ' . $urcf->getTable() . ' as urcf ON ( a.user_review_control_id = urcf.id )
					where	a.id = ?
						AND k.company_id = ?
						AND a.deleted = 0 AND urcf.deleted = 0';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );
		$this->rs = $this->ExecuteSQL( $query, $ph );

		return $this;
	}

	/**
	 * @param string $company_id UUID
	 * @param $filter_data
	 * @param int $limit Limit the number of records returned
	 * @param int $page Page number of records to return for pagination
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool|UserReviewListFactory
	 */
	function getAPISearchByCompanyIdAndArrayCriteria( $company_id, $filter_data, $limit = NULL, $page = NULL, $where = NULL, $order = NULL ) {
		if ( $company_id == '' ) {
			return FALSE;
		}

		if ( !is_array( $order ) ) {
			//Use Filter Data ordering if its set.
			if ( isset( $filter_data['sort_column'] ) AND $filter_data['sort_order'] ) {
				$order = array(Misc::trimSortPrefix( $filter_data['sort_column'] ) => $filter_data['sort_order']);
			}
		}

		$additional_order_fields = array();
		$sort_column_aliases = array();
		$order = $this->getColumnsFromAliases( $order, $sort_column_aliases );
		if ( $order == NULL ) {
			$order = array('kf.name' => 'asc');
			$strict = FALSE;
		} else {
			$strict = TRUE;
		}
		//Debug::Arr($order, 'Order Data:', __FILE__, __LINE__, __METHOD__, 10);
		//Debug::Arr($filter_data, 'Filter Data:', __FILE__, __LINE__, __METHOD__, 10);

		$uf = new UserFactory();
		$kf = new KPIFactory();
		$urcf = new UserReviewControlFactory();
		$ph = array('company_id' => TTUUID::castUUID( $company_id ),);
		$query = '
					select	a.*,
							kf.name,
							kf.type_id,
							kf.status_id,
							kf.minimum_rate,
							kf.maximum_rate,
							kf.description,
							y.first_name as created_by_first_name,
							y.middle_name as created_by_middle_name,
							y.last_name as created_by_last_name,
							z.first_name as updated_by_first_name,
							z.middle_name as updated_by_middle_name,
							z.last_name as updated_by_last_name
					from	' . $this->getTable() . ' as a
						LEFT JOIN ' . $kf->getTable() . ' as kf ON ( a.kpi_id = kf.id AND kf.deleted = 0 )
						LEFT JOIN ' . $urcf->getTable() . ' as urcf ON ( a.user_review_control_id = urcf.id )
						LEFT JOIN ' . $uf->getTable() . ' as y ON ( a.created_by = y.id AND y.deleted = 0 )
						LEFT JOIN ' . $uf->getTable() . ' as z ON ( a.updated_by = z.id AND z.deleted = 0 )
					where	kf.company_id = ? AND urcf.deleted = 0';
		$query .= ( isset( $filter_data['permission_children_ids'] ) ) ? $this->getWhereClauseSQL( 'urcf.user_id', $filter_data['permission_children_ids'], 'uuid_list', $ph ) : NULL;
		$query .= ( isset( $filter_data['id'] ) ) ? $this->getWhereClauseSQL( 'a.id', $filter_data['id'], 'uuid_list', $ph ) : NULL;
		//$query .= ( isset($filter_data['exclude_id']) ) ? $this->getWhereClauseSQL( 'a.id', $filter_data['exclude_id'], 'not_uuid_list', $ph ) : NULL;
		$query .= ( isset( $filter_data['user_review_control_id'] ) ) ? $this->getWhereClauseSQL( 'a.user_review_control_id', $filter_data['user_review_control_id'], 'uuid_list', $ph ) : NULL;
		$query .= ( isset( $filter_data['kpi_id'] ) ) ? $this->getWhereClauseSQL( 'a.kpi_id', $filter_data['kpi_id'], 'uuid_list', $ph ) : NULL;
		$query .= ( isset( $filter_data['term_id'] ) ) ? $this->getWhereClauseSQL( 'urcf.term_id', $filter_data['term_id'], 'numeric_list', $ph ) : NULL;
		$query .= ( isset( $filter_data['severity_id'] ) ) ? $this->getWhereClauseSQL( 'urcf.severity_id', $filter_data['severity_id'], 'numeric_list', $ph ) : NULL;
		$query .= ( isset( $filter_data['rating'] ) ) ? $this->getWhereClauseSQL( 'a.rating', $filter_data['rating'], 'numeric', $ph ) : NULL;
		$query .= ( isset( $filter_data['note'] ) ) ? $this->getWhereClauseSQL( 'a.note', $filter_data['note'], 'text', $ph ) : NULL;
		$query .= ( isset( $filter_data['tag'] ) ) ? $this->getWhereClauseSQL( 'a.id', array('company_id' => TTUUID::castUUID( $company_id ), 'object_type_id' => 330, 'tag' => $filter_data['tag']), 'tag', $ph ) : NULL;

		$query .= ( isset( $filter_data['created_date'] ) ) ? $this->getWhereClauseSQL( 'a.created_date', $filter_data['created_date'], 'date_range', $ph ) : NULL;
		$query .= ( isset( $filter_data['updated_date'] ) ) ? $this->getWhereClauseSQL( 'a.updated_date', $filter_data['updated_date'], 'date_range', $ph ) : NULL;
		$query .= ( isset( $filter_data['created_by'] ) ) ? $this->getWhereClauseSQL( array('a.created_by', 'y.first_name', 'y.last_name'), $filter_data['created_by'], 'user_id_or_name', $ph ) : NULL;
		$query .= ( isset( $filter_data['updated_by'] ) ) ? $this->getWhereClauseSQL( array('a.updated_by', 'z.first_name', 'z.last_name'), $filter_data['updated_by'], 'user_id_or_name', $ph ) : NULL;

		$query .= ' AND a.deleted = 0 ';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order, $strict, $additional_order_fields );
		$this->rs = $this->ExecuteSQL( $query, $ph, $limit, $page );

		return $this;
	}
}

?>
