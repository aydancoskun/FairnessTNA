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
class ExceptionListFactory extends ExceptionFactory implements IteratorAggregate {

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
	 * @return bool|ExceptionListFactory
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
	 * @param string $user_id UUID
	 * @param int $date_stamp EPOCH
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool|ExceptionListFactory
	 */
	function getByUserIdAndDateStamp( $user_id, $date_stamp, $where = NULL, $order = NULL) {
		if ( $user_id == '') {
			return FALSE;
		}

		if ( $date_stamp == '') {
			return FALSE;
		}

		$ph = array(
					'user_id' => TTUUID::castUUID($user_id),
					'date_stamp' => $this->db->BindDate( $date_stamp ),
					);

		$query = '
					select	*
					from	'. $this->getTable() .'
					where	user_id = ?
						AND date_stamp = ?
						AND deleted = 0';
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
	 * @return bool|ExceptionListFactory
	 */
	function getByIdAndCompanyId( $id, $company_id, $where = NULL, $order = NULL) {
		if ( $id == '') {
			return FALSE;
		}

		if ( $company_id == '') {
			return FALSE;
		}

		if ( $order == NULL ) {
			//$order = array( 'type_id' => 'asc', 'trigger_time' => 'desc' );
			$strict = FALSE;
		} else {
			$strict = TRUE;
		}

		$epf = new ExceptionPolicyFactory();
		$epcf = new ExceptionPolicyControlFactory();

		$ph = array(
					'company_id' => TTUUID::castUUID($company_id),
					);

		$query = '
					select	a.*
					from	'. $this->getTable() .' as a
					LEFT JOIN '. $epf->getTable() .' as epf ON a.exception_policy_id = epf.id
					LEFT JOIN '. $epcf->getTable() .' as epcf ON epf.exception_policy_control_id = epcf.id
					where
						epcf.company_id = ?
						AND a.id in ('. $this->getListSQL( $id, $ph, 'uuid' ) .')
						AND ( a.deleted = 0 AND epf.deleted = 0 AND epcf.deleted = 0 )';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order, $strict );

		$this->rs = $this->ExecuteSQL( $query, $ph );

		return $this;
	}

	/**
	 * @param string $company_id UUID
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool|ExceptionListFactory
	 */
	function getByCompanyId( $company_id, $where = NULL, $order = NULL) {
		if ( $company_id == '') {
			return FALSE;
		}

		$epf = new ExceptionPolicyFactory();
		$epcf = new ExceptionPolicyControlFactory();

		$ph = array(
					'company_id' => TTUUID::castUUID($company_id),
					);

		$query = '
					select	a.*
					from	'. $this->getTable() .' as a
					LEFT JOIN '. $epf->getTable() .' as epf ON a.exception_policy_id = epf.id
					LEFT JOIN '. $epcf->getTable() .' as epcf ON epf.exception_policy_control_id = epcf.id
					where
						epcf.company_id = ?
						AND ( a.deleted = 0 AND epf.deleted = 0 AND epcf.deleted = 0 )';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );

		$this->rs = $this->ExecuteSQL( $query, $ph );

		return $this;
	}

	/**
	 * @param string $company_id UUID
	 * @param string $user_id UUID
	 * @param int $start_date EPOCH
	 * @param int $end_date EPOCH
	 * @return bool|ExceptionListFactory
	 */
	function getByCompanyIDAndUserIdAndStartDateAndEndDate( $company_id, $user_id, $start_date, $end_date) {
		if ( $company_id == '' ) {
			return FALSE;
		}

		if ( $user_id == '' ) {
			return FALSE;
		}

		if ( $start_date == '' ) {
			return FALSE;
		}

		if ( $end_date == '' ) {
			return FALSE;
		}

		$uf = new UserFactory();
		$epf = new ExceptionPolicyFactory();

		$ph = array(
					'company_id' => TTUUID::castUUID($company_id),
					'user_id' => TTUUID::castUUID($user_id),
					'start_date' => $this->db->BindDate( $start_date ),
					'end_date' => $this->db->BindDate( $end_date )
					);

		$query = '
					select	a.*,
							a.date_stamp as user_date_stamp,
							d.severity_id as severity_id,
							d.type_id as exception_policy_type_id
					from	'. $this->getTable() .' as a
					LEFT JOIN '. $uf->getTable() .' as c ON a.user_id = c.id
					LEFT JOIN '. $epf->getTable() .' as d ON a.exception_policy_id = d.id
					where
						c.company_id = ?
						AND	a.user_id = ?
						AND a.date_stamp >= ?
						AND a.date_stamp <= ?
						AND ( a.deleted = 0 )
					ORDER BY a.date_stamp asc, d.type_id
					';

		$this->rs = $this->ExecuteSQL( $query, $ph );

		return $this;
	}

	/**
	 * @param string $company_id UUID
	 * @param string $user_id UUID
	 * @param string $pay_period_id UUID
	 * @param string $severity_id UUID
	 * @param int $exception_type_id ID
	 * @return bool|ExceptionListFactory
	 */
	function getByCompanyIDAndUserIdAndPayPeriodIdAndSeverityAndNotTypeID( $company_id, $user_id, $pay_period_id, $severity_id, $exception_type_id = NULL ) {
		if ( $company_id == '' ) {
			return FALSE;
		}

		if ( $user_id == '' ) {
			return FALSE;
		}

		if ( $pay_period_id == '' ) {
			return FALSE;
		}

		$uf = new UserFactory();
		$epf = new ExceptionPolicyFactory();

		$ph = array(
					'company_id' => TTUUID::castUUID($company_id),
					'user_id' => TTUUID::castUUID($user_id),
					'pay_period_id' => TTUUID::castUUID($pay_period_id),
					);

		$query = '
					select	a.*,
							a.date_stamp as user_date_stamp,
							d.severity_id as severity_id,
							d.type_id as exception_policy_type_id
					from	'. $this->getTable() .' as a
					LEFT JOIN '. $uf->getTable() .' as c ON a.user_id = c.id
					LEFT JOIN '. $epf->getTable() .' as d ON a.exception_policy_id = d.id
					where
						c.company_id = ?
						AND	a.user_id = ?
						AND a.pay_period_id = ?
						AND d.severity_id IN ('. $this->getListSQL( $severity_id, $ph, 'int' ) .')
						AND d.type_id NOT IN ('. $this->getListSQL( $exception_type_id, $ph, 'string' ) .')
						AND ( a.deleted = 0 )
					ORDER BY a.date_stamp asc, d.type_id
					';

		$this->rs = $this->ExecuteSQL( $query, $ph );

		return $this;
	}

	/**
	 * @param string $user_id UUID
	 * @param $pay_period_status
	 * @return bool|ExceptionListFactory
	 */
	function getFlaggedExceptionsByUserIdAndPayPeriodStatus( $user_id, $pay_period_status) {
		if ( $user_id == '' ) {
			return FALSE;
		}

		if ( $pay_period_status == '' ) {
			return FALSE;
		}

		$epf = new ExceptionPolicyFactory();
		$ppf = new PayPeriodFactory();
		$rf = new RequestFactory();

		$ph = array(
					'user_id' => TTUUID::castUUID($user_id),
					'date_stamp1' => $this->db->BindDate( TTDate::getBeginDayEpoch( ( TTDate::getTime() - (32 * 86400) ) ) ), //Narrow down the date range as a performance optimizaiton.
					'date_stamp2' => $this->db->BindDate( TTDate::getBeginDayEpoch( TTDate::getTime() ) ), //Exclude any exceptions after today, but we must include todays exceptions because missing IN punches could exist.
					'status_id' => $pay_period_status,
					);

		$query = '
					select	d.severity_id as severity_id,
							count(*) as total
					from	'. $this->getTable() .' as a
					LEFT JOIN '. $epf->getTable() .' as d ON a.exception_policy_id = d.id
					LEFT JOIN '. $ppf->getTable() .' as e ON a.pay_period_id = e.id
					where
						a.user_id = ?
						AND a.type_id = 50
						AND ( a.date_stamp >= ? AND a.date_stamp <= ? )
						AND e.status_id = ?
						AND NOT EXISTS ( select z.id from '. $rf->getTable() .' as z where z.date_stamp = a.date_stamp AND z.status_id = 30 )
						AND ( a.deleted = 0 AND e.deleted = 0)
					GROUP BY d.severity_id
					ORDER BY d.severity_id desc
					';

		$this->rs = $this->ExecuteSQL( $query, $ph );

		return $this;
	}

	/**
	 * @param string $pay_period_id UUID
	 * @param int $before_epoch EPOCH
	 * @return bool|ExceptionListFactory
	 */
	function getSumExceptionsByPayPeriodIdAndBeforeDate( $pay_period_id, $before_epoch) {
		if ( $pay_period_id == '' ) {
			return FALSE;
		}

		if ( $before_epoch == '' ) {
			return FALSE;
		}

		$uf = new UserFactory();
		$epf = new ExceptionPolicyFactory();
		$ppf = new PayPeriodFactory();

		$ph = array(
					'pay_period_id' => TTUUID::castUUID($pay_period_id),
					'date_stamp' => $this->db->BindDate( TTDate::getBeginDayEpoch( $before_epoch  ) ),
					);

		//Ignore pre-mature exceptions when counting exceptions.
		$query = '
					select	d.severity_id as severity_id,
							count(*) as count
					from	'. $this->getTable() .' as a
					LEFT JOIN '. $uf->getTable() .' as c ON a.user_id = c.id
					LEFT JOIN '. $epf->getTable() .' as d ON a.exception_policy_id = d.id
					LEFT JOIN '. $ppf->getTable() .' as e ON a.pay_period_id = e.id
					where
						e.id = ?
						AND a.type_id = 50
						AND a.date_stamp <= ?
						AND ( a.deleted = 0 AND e.deleted=0)
					GROUP BY d.severity_id
					ORDER BY d.severity_id desc
					';

		$this->rs = $this->ExecuteSQL( $query, $ph );

		return $this;
	}

	/**
	 * @param string $company_id UUID
	 * @param $status
	 * @param int $limit Limit the number of records returned
	 * @param int $page Page number of records to return for pagination
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool|ExceptionListFactory
	 */
	function getByCompanyIDAndPayPeriodStatus( $company_id, $status, $limit = NULL, $page = NULL, $where = NULL, $order = NULL) {
		if ( $company_id == '' ) {
			return FALSE;
		}

		if ( $status == '' ) {
			return FALSE;
		}

		if ( $order == NULL ) {
			$order = array( 'd.severity_id' => 'desc', 'a.user_id' => 'asc', 'a.date_stamp' => 'asc' );
			$strict = FALSE;
		} else {
			//$strict = TRUE;
			$strict = FALSE;
		}

		$uf = new UserFactory();
		$ppf = new PayPeriodFactory();
		$epf = new ExceptionPolicyFactory();

		$ph = array(
					'company_id' => TTUUID::castUUID($company_id),
					);

		$query = '
					select	a.*,
							a.date_stamp as user_date_stamp,
							d.severity_id as severity_id,
							d.type_id as exception_policy_type_id,
							a.user_id as user_id
					from	'. $this->getTable() .' as a
					LEFT JOIN '. $uf->getTable() .' as c ON a.user_id = c.id
					LEFT JOIN '. $epf->getTable() .' as d ON a.exception_policy_id = d.id
					LEFT JOIN '. $ppf->getTable() .' as e ON a.pay_period_id = e.id
					where
						c.company_id = ?
						AND e.status_id in ('. $this->getListSQL( $status, $ph, 'int' ) .')
						AND ( a.deleted = 0 AND e.deleted = 0 )
					';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order, $strict );

		$this->rs = $this->ExecuteSQL( $query, $ph, $limit, $page );

		return $this;
	}

	/**
	 * @param string $company_id UUID
	 * @param $type
	 * @param $status
	 * @param int $min_date_stamp EPOCH
	 * @param int $limit Limit the number of records returned
	 * @param int $page Page number of records to return for pagination
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool|ExceptionListFactory
	 */
	function getByCompanyIDAndTypeAndPayPeriodStatusAndMinimumDateStamp( $company_id, $type, $status, $min_date_stamp = NULL, $limit = NULL, $page = NULL, $where = NULL, $order = NULL) {
		if ( $company_id == '' ) {
			return FALSE;
		}

		if ( $status == '' ) {
			return FALSE;
		}

		if ( $type == '' ) {
			return FALSE;
		}

		if ( $order == NULL ) {
			$order = array( 'd.severity_id' => 'desc', 'a.user_id' => 'asc', 'a.date_stamp' => 'asc' );
			$strict = FALSE;
		} else {
			//$strict = TRUE;
			$strict = FALSE;
		}

		$uf = new UserFactory();
		$ppf = new PayPeriodFactory();
		$epf = new ExceptionPolicyFactory();

		$ph = array(
					'company_id' => TTUUID::castUUID($company_id),
					'min_date_stamp' => $this->db->BindDate( $min_date_stamp ),
					);

		$query = '
					select	a.*,
							a.date_stamp as user_date_stamp,
							d.severity_id as severity_id,
							d.type_id as exception_policy_type_id,
							a.user_id as user_id
					from	'. $this->getTable() .' as a
					LEFT JOIN '. $uf->getTable() .' as c ON a.user_id = c.id
					LEFT JOIN '. $epf->getTable() .' as d ON a.exception_policy_id = d.id
					LEFT JOIN '. $ppf->getTable() .' as e ON a.pay_period_id = e.id
					where
						c.company_id = ?
						AND a.date_stamp >= ?						
						AND a.type_id in ('. $this->getListSQL( $type, $ph, 'int' ) .')
						AND e.status_id in ('. $this->getListSQL( $status, $ph, 'int' ) .')
						AND ( a.deleted = 0 AND c.deleted = 0 AND d.deleted = 0 AND e.deleted = 0 )
					';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order, $strict );

		$this->rs = $this->ExecuteSQL( $query, $ph, $limit, $page );

		return $this;
	}

	/**
	 * @param string $company_id UUID
	 * @param string $user_id UUID
	 * @param $status
	 * @param int $limit Limit the number of records returned
	 * @param int $page Page number of records to return for pagination
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool|ExceptionListFactory
	 */
	function getByCompanyIDAndUserIDAndPayPeriodStatus( $company_id, $user_id, $status, $limit = NULL, $page = NULL, $where = NULL, $order = NULL) {
		if ( $company_id == '' ) {
			return FALSE;
		}

		if ( $user_id == '' ) {
			return FALSE;
		}

		if ( $status == '' ) {
			return FALSE;
		}

		if ( $order == NULL ) {
			$order = array( 'd.severity_id' => 'desc', 'a.user_id' => 'asc', 'a.date_stamp' => 'asc', 'd.type_id' => 'asc' );
			$strict = FALSE;
		} else {
			//$strict = TRUE;
			$strict = FALSE;
		}

		$uf = new UserFactory();
		$ppf = new PayPeriodFactory();
		$epf = new ExceptionPolicyFactory();

		$ph = array(
					'company_id' => TTUUID::castUUID($company_id),
					'user_id' => TTUUID::castUUID($user_id),
					);

		$query = '
					select	a.*,
							a.date_stamp as user_date_stamp,
							d.severity_id as severity_id,
							d.type_id as exception_policy_type_id,
							a.user_id as user_id
					from	'. $this->getTable() .' as a
					LEFT JOIN '. $uf->getTable() .' as c ON a.user_id = c.id
					LEFT JOIN '. $epf->getTable() .' as d ON a.exception_policy_id = d.id
					LEFT JOIN '. $ppf->getTable() .' as e ON a.pay_period_id = e.id
					where
						c.company_id = ?
						AND a.user_id = ?
						AND e.status_id in ('. $this->getListSQL( $status, $ph, 'int' ) .')
						AND ( a.deleted = 0 AND e.deleted = 0 )
					';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order, $strict );

		$this->rs = $this->ExecuteSQL( $query, $ph, $limit, $page );

		return $this;
	}

	/**
	 * @param string $company_id UUID
	 * @param $filter_data
	 * @param int $limit Limit the number of records returned
	 * @param int $page Page number of records to return for pagination
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool|ExceptionListFactory
	 */
	function getAPISearchByCompanyIdAndArrayCriteria( $company_id, $filter_data, $limit = NULL, $page = NULL, $where = NULL, $order = NULL ) {
		if ( $company_id == '') {
			return FALSE;
		}

		if ( !is_array($order) ) {
			//Use Filter Data ordering if its set.
			if ( isset($filter_data['sort_column']) AND $filter_data['sort_order']) {
				$order = array(Misc::trimSortPrefix($filter_data['sort_column']) => $filter_data['sort_order']);
			}
		}

		if ( isset( $filter_data['user_group_id'] ) ) {
			$filter_data['group_id'] = $filter_data['user_group_id'];
		}
		if ( isset( $filter_data['user_title_id'] ) ) {
			$filter_data['title_id'] = $filter_data['user_title_id'];
		}
		if ( isset( $filter_data['include_user_id'] ) ) {
			$filter_data['user_id'] = $filter_data['include_user_id'];
		}
		if ( isset( $filter_data['exception_policy_severity_id'] ) ) {
			$filter_data['severity_id'] = $filter_data['exception_policy_severity_id'];
		}
//		if ( isset($filter_data['show_pre_mature']) ) {
//			$filter_data['type_id'] = 5; // Pre-Mature type.
//		}

		$additional_order_fields = array('d.name', 'e.name', 'f.name', 'g.name', 'h.status_id', 'group', 'i.severity_id', 'i.type_id', 'c.first_name', 'c.last_name', 'c.country', 'c.province', 'a.date_stamp', 'pgf.name', 'pscf.name', 'ppsf.name');

		$sort_column_aliases = array(
									'status' => 'status_id',
									'type' => 'type_id',
									);
		$order = $this->getColumnsFromAliases( $order, $sort_column_aliases );

		if ( $order == NULL ) {
			//Show most recent exceptions by severity first. Critical exceptions are still at the top, but this allows them to see
			//most recent activity, especially useful when dealing with In Late exceptions where older ones may be valid, but newer ones are not.
			$order = array( 'i.severity_id' => 'desc', 'a.date_stamp' => 'desc', 'c.last_name' => 'asc', 'i.type_id' => 'asc' );

			$strict = FALSE;
		} else {
			//Do order by column conversions, because if we include these columns in the SQL
			//query, they contaminate the data array.
			if ( isset($order['default_branch']) ) {
				$order['d.name'] = $order['default_branch'];
				unset($order['default_branch']);
			}
			if ( isset($order['default_department']) ) {
				$order['e.name'] = $order['default_department'];
				unset($order['default_department']);
			}
			if ( isset($order['user_group']) ) {
				$order['f.name'] = $order['user_group'];
				unset($order['user_group']);
			}
			if ( isset($order['title']) ) {
				$order['g.name'] = $order['title'];
				unset($order['title']);
			}
			if ( isset($order['exception_policy_type_id']) ) {
				$order['i.type_id'] = $order['exception_policy_type_id'];
				unset($order['exception_policy_type_id']);
			}
			if ( isset($order['severity_id']) ) {
				$order['i.severity_id'] = $order['severity_id'];
				unset($order['severity_id']);
			}
			if ( isset($order['severity']) ) {
				$order['i.severity_id'] = $order['severity'];
				unset($order['severity']);
			}
			if ( isset($order['exception_policy_type']) ) {
				$order['i.type_id'] = $order['exception_policy_type'];
				unset($order['exception_policy_type']);
			}
			if ( isset($order['exception_policy_type_id']) ) {
				$order['i.type_id'] = $order['exception_policy_type_id'];
				unset($order['exception_policy_type_id']);
			}

			if ( isset($order['first_name']) ) {
				$order['c.first_name'] = $order['first_name'];
				unset($order['first_name']);
			}
			if ( isset($order['last_name']) ) {
				$order['c.last_name'] = $order['last_name'];
				unset($order['last_name']);
			}
			if ( isset($order['country']) ) {
				$order['c.country'] = $order['country'];
				unset($order['country']);
			}
			if ( isset($order['province']) ) {
				$order['c.province'] = $order['province'];
				unset($order['province']);
			}
			if ( isset($order['date_stamp']) ) {
				$order['a.date_stamp'] = $order['date_stamp'];
				unset($order['date_stamp']);
			}
			if ( isset($order['policy_group']) ) {
				$order['pgf.name'] = $order['policy_group'];
				unset($order['policy_group']);
			}
			if ( isset($order['permission_group']) ) {
				$order['pscf.name'] = $order['permission_group'];
				unset($order['permission_group']);
			}
			if ( isset($order['pay_period_schedule']) ) {
				$order['ppsf.name'] = $order['pay_period_schedule'];
				unset($order['pay_period_schedule']);
			}

			//Always sort by last name, first name after other columns
			if ( !isset($order['c.last_name']) ) {
				$order['c.last_name'] = 'asc';
			}
			if ( !isset($order['c.first_name']) ) {
				$order['c.first_name'] = 'asc';
			}
			if ( !isset($order['a.date_stamp']) ) {
				$order['a.date_stamp'] = 'desc';
			}
			if ( !isset($order['i.severity_id']) ) {
				$order['i.severity_id'] = 'desc';
			}

			$strict = TRUE;
		}
		//Debug::Arr($order, 'Order Data:', __FILE__, __LINE__, __METHOD__, 10);
		//Debug::Arr($filter_data, 'Filter Data:', __FILE__, __LINE__, __METHOD__, 10);

		$uf = new UserFactory();
		$bf = new BranchFactory();
		$df = new DepartmentFactory();
		$ugf = new UserGroupFactory();
		$utf = new UserTitleFactory();
		$ppf = new PayPeriodFactory();
		$ppsf = new PayPeriodScheduleFactory();
		$epf = new ExceptionPolicyFactory();
		$epcf = new ExceptionPolicyControlFactory();
		$pguf = new PolicyGroupUserFactory();
		$pgf = new PolicyGroupFactory();
		$pf = new PunchFactory();
		$pcf = new PunchControlFactory();
		$pscf = new PermissionControlFactory();
		$puf = new PermissionUserFactory();

		$ph = array(
					'company_id' => TTUUID::castUUID($company_id),
					);

		$query = '
					select	a.*,
							a.date_stamp as date_stamp,
							a.pay_period_id as pay_period_id,
							h.pay_period_schedule_id as pay_period_schedule_id,
							i.severity_id as severity_id,
							i.type_id as exception_policy_type_id,
							a.user_id as user_id,
							h.start_date as pay_period_start_date,
							h.end_date as pay_period_end_date,
							h.transaction_date as pay_period_transaction_date,
							c.first_name as first_name,
							c.last_name as last_name,
							c.country as country,
							c.province as province,
							c.status_id as user_status_id,
							c.group_id as group_id,
							f.name as "group",
							c.title_id as title_id,
							g.name as title,
							c.default_branch_id as default_branch_id,
							d.name as default_branch,
							c.default_department_id as default_department_id,
							e.name as default_department,

							pcf.branch_id as branch_id,
							bf.name as branch,
							pcf.department_id as department_id,
							df.name as department,
							pgf.name as policy_group,
							pscf.name as permission_group,
							ppsf.name as pay_period_schedule,

							y.first_name as created_by_first_name,
							y.middle_name as created_by_middle_name,
							y.last_name as created_by_last_name,
							z.first_name as updated_by_first_name,
							z.middle_name as updated_by_middle_name,
							z.last_name as updated_by_last_name
					from	'. $this->getTable() .' as a
						LEFT JOIN '. $epf->getTable() .' as i ON a.exception_policy_id = i.id
						LEFT JOIN '. $epcf->getTable() .' as epcf ON epcf.id = i.exception_policy_control_id
						LEFT JOIN '. $uf->getTable() .' as c ON a.user_id = c.id
						LEFT JOIN '. $bf->getTable() .' as d ON c.default_branch_id = d.id
						LEFT JOIN '. $df->getTable() .' as e ON c.default_department_id = e.id
						LEFT JOIN '. $ugf->getTable() .' as f ON c.group_id = f.id
						LEFT JOIN '. $utf->getTable() .' as g ON c.title_id = g.id
						LEFT JOIN '. $ppf->getTable() .' as h ON a.pay_period_id = h.id
						LEFT JOIN '. $ppsf->getTable() .' as ppsf ON ppsf.id = h.pay_period_schedule_id
						LEFT JOIN '. $pguf->getTable() .' as pguf ON a.user_id = pguf.user_id
						LEFT JOIN '. $pgf->getTable() .' as pgf ON pguf.policy_group_id = pgf.id
						LEFT JOIN '. $puf->getTable() .' as puf ON c.id = puf.user_id
						LEFT JOIN '. $pscf->getTable() .' as pscf ON pscf.id = puf.permission_control_id
						LEFT JOIN '. $pf->getTable() .' as pf ON ( a.punch_id IS NOT NULL AND a.punch_id = pf.id AND pf.deleted = 0)
						LEFT JOIN '. $pcf->getTable() .' as pcf ON ( ( ( pf.id IS NOT NULL AND pf.punch_control_id = pcf.id ) OR ( a.punch_control_id is NOT NULL AND a.punch_control_id = pcf.id ) ) AND pcf.deleted = 0)
						LEFT JOIN '. $bf->getTable() .' as bf ON pcf.branch_id = bf.id
						LEFT JOIN '. $df->getTable() .' as df ON pcf.department_id = df.id
						LEFT JOIN '. $uf->getTable() .' as y ON ( a.created_by = y.id AND y.deleted = 0 )
						LEFT JOIN '. $uf->getTable() .' as z ON ( a.updated_by = z.id AND z.deleted = 0 )
					where	c.company_id = ? ';

		$query .= ( isset($filter_data['permission_children_ids']) ) ? $this->getWhereClauseSQL( 'c.id', $filter_data['permission_children_ids'], 'uuid_list', $ph ) : NULL;
		$query .= ( isset($filter_data['id']) ) ? $this->getWhereClauseSQL( 'a.id', $filter_data['id'], 'uuid_list', $ph ) : NULL;
		$query .= ( isset($filter_data['exclude_id']) ) ? $this->getWhereClauseSQL( 'a.id', $filter_data['exclude_id'], 'not_uuid_list', $ph ) : NULL;

		$query .= ( isset($filter_data['user_id']) ) ? $this->getWhereClauseSQL( 'c.id', $filter_data['user_id'], 'uuid_list', $ph ) : NULL;
		$query .= ( isset($filter_data['exclude_user_id']) ) ? $this->getWhereClauseSQL( 'c.id', $filter_data['exclude_user_id'], 'not_uuid_list', $ph ) : NULL;

		$query .= ( isset($filter_data['user_status_id']) ) ? $this->getWhereClauseSQL( 'c.status_id', $filter_data['user_status_id'], 'numeric_list', $ph ) : NULL;
		$query .= ( isset($filter_data['legal_entity_id']) ) ? $this->getWhereClauseSQL( 'c.legal_entity_id', $filter_data['legal_entity_id'], 'uuid_list', $ph ) : NULL;
		$query .= ( isset($filter_data['type_id']) ) ? $this->getWhereClauseSQL( 'a.type_id', $filter_data['type_id'], 'numeric_list', $ph ) : NULL;
		$query .= ( isset($filter_data['severity_id']) ) ? $this->getWhereClauseSQL( 'i.severity_id', $filter_data['severity_id'], 'numeric_list', $ph ) : NULL;

		$query .= ( isset($filter_data['exception_policy_type_id']) ) ? $this->getWhereClauseSQL( 'i.type_id', $filter_data['exception_policy_type_id'], 'upper_text_list', $ph ) : NULL;
		$query .= ( isset($filter_data['pay_period_id']) ) ? $this->getWhereClauseSQL( 'a.pay_period_id', $filter_data['pay_period_id'], 'uuid_list', $ph ) : NULL;
		$query .= ( isset($filter_data['pay_period_status_id']) ) ? $this->getWhereClauseSQL( 'h.status_id', $filter_data['pay_period_status_id'], 'numeric_list', $ph ) : NULL;

		$query .= ( isset($filter_data['group_id']) ) ? $this->getWhereClauseSQL( 'c.group_id', $filter_data['group_id'], 'uuid_list', $ph ) : NULL;

		$query .= ( isset($filter_data['default_branch_id']) ) ? $this->getWhereClauseSQL( 'c.default_branch_id', $filter_data['default_branch_id'], 'uuid_list', $ph ) : NULL;
		$query .= ( isset($filter_data['default_department_id']) ) ? $this->getWhereClauseSQL( 'c.default_department_id', $filter_data['default_department_id'], 'uuid_list', $ph ) : NULL;
		$query .= ( isset($filter_data['title_id']) ) ? $this->getWhereClauseSQL( 'c.title_id', $filter_data['title_id'], 'uuid_list', $ph ) : NULL;

		$query .= ( isset($filter_data['branch_id']) ) ? $this->getWhereClauseSQL( 'pcf.branch_id', $filter_data['branch_id'], 'uuid_list', $ph ) : NULL;
		$query .= ( isset($filter_data['department_id']) ) ? $this->getWhereClauseSQL( 'pcf.department_id', $filter_data['department_id'], 'uuid_list', $ph ) : NULL;

		if ( isset($filter_data['start_date']) AND !is_array($filter_data['start_date']) AND trim($filter_data['start_date']) != '' ) {
			$ph[] = $this->db->BindDate( (int)$filter_data['start_date'] );
			$query	.=	' AND a.date_stamp >= ?';
		}
		if ( isset($filter_data['end_date']) AND !is_array($filter_data['end_date']) AND trim($filter_data['end_date']) != '' ) {
			$ph[] = $this->db->BindDate( (int)$filter_data['end_date'] );
			$query	.=	' AND a.date_stamp <= ?';
		}

		$query .= ( isset($filter_data['created_by']) ) ? $this->getWhereClauseSQL( array('a.created_by', 'y.first_name', 'y.last_name'), $filter_data['created_by'], 'user_id_or_name', $ph ) : NULL;
		$query .= ( isset($filter_data['updated_by']) ) ? $this->getWhereClauseSQL( array('a.updated_by', 'z.first_name', 'z.last_name'), $filter_data['updated_by'], 'user_id_or_name', $ph ) : NULL;

		//Make sure we accept exception rows assign to pay_period_id = 0 (no pay period), as this can happen when punches exist in the future.
		$query .=	' AND ( a.deleted = 0 AND c.deleted = 0 AND pgf.deleted = 0 AND ( h.deleted = 0 OR h.deleted is NULL ) ) ';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order, $strict, $additional_order_fields );

		//Debug::Arr($ph, 'Query: '. $query, __FILE__, __LINE__, __METHOD__, 10);

		$this->rs = $this->ExecuteSQL( $query, $ph, $limit, $page );

		return $this;
	}

}
?>
