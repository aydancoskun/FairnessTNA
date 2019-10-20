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
 * @package Modules\Company
 */
class CompanyListFactory extends CompanyFactory implements IteratorAggregate {

	/**
	 * @param int $limit Limit the number of records returned
	 * @param int $page Page number of records to return for pagination
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return $this
	 */
	function getAll( $limit = NULL, $page = NULL, $where = NULL, $order = NULL) {
		if ( $order == NULL ) {
			$order = array( 'status_id' => 'asc', 'name' => 'asc');
			$strict = FALSE;
		} else {
			$strict = TRUE;
		}

		$additional_order_fields = array('last_login_date');

		$query = '
					select	a.*
					from	'. $this->getTable() .' as a
					WHERE a.deleted = 0';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order, $strict, $additional_order_fields	);

		$this->rs = $this->ExecuteSQL( $query, NULL, $limit, $page );

		return $this;
	}

	/**
	 * @param int $limit Limit the number of records returned
	 * @param int $page Page number of records to return for pagination
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return $this
	 */
	function getAllAndLastLoginDate( $limit = NULL, $page = NULL, $where = NULL, $order = NULL) {
		if ( $order == NULL ) {
			$order = array( 'status_id' => 'asc', 'name' => 'asc');
			$strict = FALSE;
		} else {
			$strict = TRUE;
		}

		$additional_order_fields = array('last_login_date');

		$uf = new UserFactory();

		$query = '
					select	_ADODB_COUNT
						a.*,
							(select max(last_login_date) from '. $uf->getTable() .' as uf where uf.company_id = a.id ) as last_login_date
						_ADODB_COUNT
					from	'. $this->getTable() .' as a
					WHERE a.deleted = 0';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order, $strict, $additional_order_fields	);

		$this->rs = $this->ExecuteSQL( $query, NULL, $limit, $page );

		return $this;
	}

	/**
	 * @param int $limit Limit the number of records returned
	 * @param int $page Page number of records to return for pagination
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return $this
	 */
	function getAllByInValidContacts( $limit = NULL, $page = NULL, $where = NULL, $order = NULL) {
		if ( $order == NULL ) {
			$order = array( 'status_id' => 'asc', 'name' => 'asc');
			$strict = FALSE;
		} else {
			$strict = TRUE;
		}

		$additional_order_fields = array();

		$uf = new UserFactory();

		$query = '
					select	distinct a.*
					from	'. $this->getTable() .' as a
					LEFT JOIN '. $uf->getTable() .' as uf_a ON ( a.admin_contact = uf_a.id )
					LEFT JOIN '. $uf->getTable() .' as uf_b ON ( a.billing_contact = uf_b.id )
					LEFT JOIN '. $uf->getTable() .' as uf_c ON ( a.support_contact = uf_c.id )
					WHERE
						a.status_id in (10, 20, 23)
						AND (
								( uf_a.id is NULL OR uf_b.id is NULL OR uf_c.id is NULL )
								OR ( uf_a.deleted = 1 OR uf_b.deleted = 1 OR uf_c.deleted = 1 )
								OR ( uf_a.status_id != 10 OR uf_b.status_id != 10 OR uf_b.status_id != 10 )
								OR (
										( ( uf_a.work_email is NULL OR uf_a.work_email = \'\' ) AND ( uf_a.home_email is NULL OR uf_a.home_email = \'\' ) )
										OR ( ( uf_b.work_email is NULL OR uf_b.work_email = \'\' ) AND ( uf_b.home_email is NULL OR uf_b.home_email = \'\' ) )
										OR ( ( uf_c.work_email is NULL OR uf_c.work_email = \'\' ) AND ( uf_c.home_email is NULL OR uf_c.home_email = \'\' ) )
									)
							)
						AND a.deleted = 0
				';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order, $strict, $additional_order_fields	);

		$this->rs = $this->ExecuteSQL( $query, NULL, $limit, $page );

		return $this;
	}

	/**
	 * @param string $id UUID
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool|CompanyListFactory
	 */
	function getById( $id, $where = NULL, $order = NULL) {
		if ( $id == '' ) {
			return FALSE;
		}

		$this->rs = $this->getCache($id);
		if ( $this->rs === FALSE ) {
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

			$this->saveCache($this->rs, $id);
		}

		return $this;
	}

	/**
	 * @param string $company_id UUID
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool|CompanyListFactory
	 */
	function getByCompanyId( $company_id, $where = NULL, $order = NULL) {
		return self::getById( $company_id, $where, $order);
	}

	/**
	 * @param string $id UUID
	 * @param string $company_id UUID
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool|CompanyListFactory
	 */
	function getByIdAndCompanyId( $id, $company_id, $where = NULL, $order = NULL) {
		if ( $id == '' ) {
			return FALSE;
		}

		if ( $company_id == '' ) {
			return FALSE;
		}

		$ph = array(
					'id' => TTUUID::castUUID($id),
					'company_id' => TTUUID::castUUID($company_id),
					);

		$query = '
					select	*
					from	'. $this->getTable() .'
					where	id = ?
						AND id = ?
						AND deleted = 0';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );

		$this->rs = $this->ExecuteSQL( $query, $ph );

		return $this;
	}

	/**
	 * @param int $status_id
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool|CompanyListFactory
	 */
	function getByStatusID( $status_id, $where = NULL, $order = NULL) {
		if ( $status_id == '' ) {
			return FALSE;
		}

		$ph = array();

		$query = '
					select	a.*
					from	'. $this->getTable() .' as a
					where a.status_id in ('. $this->getListSQL( $status_id, $ph, 'int' ) .')
						AND ( a.deleted = 0 )';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );

		$this->rs = $this->ExecuteSQL( $query, $ph );

		return $this;
	}

	/**
	 * @param $short_name
	 * @param $status_id
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool|CompanyListFactory
	 * @throws DBError
	 */
	function getByShortNameAndStatus($short_name, $status_id, $where = NULL, $order = NULL) {
		if ( $short_name == '' ) {
			return FALSE;
		}

		$ph = array(
					'short_name' => strtolower($short_name),
					'status_id' => (int)$status_id,
					);

		$query = '
					select	*
					from	'. $this->getTable() .'
					where	lower(short_name) = ?
						AND status_id = ?
						AND deleted = 0';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );

		$this->rs = $this->ExecuteSQL( $query, $ph );

		return $this;
	}

	/**
	 * @param $user_name
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool|CompanyListFactory
	 */
	function getByUserName( $user_name, $where = NULL, $order = NULL) {
		if ( $user_name == '' ) {
			return FALSE;
		}

		$uf = new UserFactory();

		if ( preg_match( $uf->username_validator_regex, $user_name ) === 0 ) { //This helps prevent invalid byte sequences on unicode strings.
			Debug::Text('Username doesnt match regex: '. $user_name, __FILE__, __LINE__, __METHOD__, 10);
			return FALSE; //No company by that user name.
		}

		$ph = array(
					'user_name' => TTi18n::strtolower( $user_name ),
					);

		$query = '
					select	a.*
					from	'. $this->getTable() .' as a, '. $uf->getTable() .' as b
					where	a.id = b.company_id
						AND b.status_id = 10
						AND b.user_name = ?
						AND ( a.deleted = 0 AND b.deleted = 0 )';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );

		$this->rs = $this->ExecuteSQL( $query, $ph );

		return $this;
	}

	/**
	 * @param string $phone_id UUID
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool|CompanyListFactory
	 */
	function getByPhoneID( $phone_id, $where = NULL, $order = NULL) {
		if ( $phone_id == '' ) {
			return FALSE;
		}

		$uf = new UserFactory();

		if ( preg_match( $uf->phoneid_validator_regex, $phone_id ) === 0 ) { //This helps prevent invalid byte sequences on unicode strings.
			Debug::Text('PhoneID doesnt match regex: '. $phone_id, __FILE__, __LINE__, __METHOD__, 10);
			return FALSE; //No company by that user name.
		}

		$ph = array(
					'phone_id' => (string)$phone_id,
					);

		$query = '
					select	a.*
					from	'. $this->getTable() .' as a, '. $uf->getTable() .' as b
					where	a.id = b.company_id
						AND b.status_id = 10
						AND b.phone_id = ?
						AND ( a.deleted = 0 AND b.deleted = 0 )';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );

		$this->rs = $this->ExecuteSQL( $query, $ph );

		return $this;
	}
	/**
	 * @param $lf
	 * @param bool $include_blank
	 * @param bool $include_disabled
	 * @return array|bool
	 */
	function getArrayByListFactory($lf, $include_blank = TRUE, $include_disabled = TRUE ) {
		if ( !is_object($lf) ) {
			return FALSE;
		}

		$list = array();
		if ( $include_blank == TRUE ) {
			$list[TTUUID::getZeroID()] = '--';
		}

		foreach ($lf as $obj) {
			if ( $obj->getStatus() != 10 ) {
				$status = '('.Option::getByKey($obj->getStatus(), $obj->getOptions('status') ).') ';
			} else {
				$status = NULL;
			}

			if ( $include_disabled == TRUE OR ( $include_disabled == FALSE AND $obj->getStatus() == 10 ) ) {
				$list[$obj->getID()] = $status.$obj->getName();
			}
		}

		if ( empty($list) == FALSE ) {
			return $list;
		}

		return FALSE;
	}


	/**
	 * @return array
	 */
	static function getAllArray() {
		$clf = new CompanyListFactory();
		$clf->getAll();

		$company_list = array( TTUUID::getZeroID() => '--' );
		foreach ($clf as $company) {
			$company_list[$company->getID()] = $company->getName();
		}

		return $company_list;
	}

	/**
	 * @param string $company_id UUID
	 * @param $filter_data
	 * @param int $limit Limit the number of records returned
	 * @param int $page Page number of records to return for pagination
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool|CompanyListFactory
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

		$additional_order_fields = array('status_id', 'last_login_date', 'total_active_days', 'last_login_days', 'this_month_max_active_users', 'this_month_avg_active_users', 'this_month_min_active_users', 'last_month_max_active_users', 'last_month_avg_active_users', 'last_month_min_active_users', 'regular_user_feedback_rating', 'supervisor_user_feedback_rating', 'admin_user_feedback_rating', 'all_user_feedback_rating' );

		$sort_column_aliases = array(
									'status' => 'status_id',
									'product_edition' => 'product_edition_id',
									);

		$order = $this->getColumnsFromAliases( $order, $sort_column_aliases );

		if ( $order == NULL ) {
			$order = array( 'status_id' => 'asc', 'name' => 'asc');
			$strict = FALSE;
		} else {
			//Always try to order by status first so INACTIVE employees go to the bottom.
			if ( !isset($order['status_id']) ) {
				$order = Misc::prependArray( array('status_id' => 'asc'), $order );
			}
			//Always sort by last name, first name after other columns
			if ( !isset($order['name']) ) {
				$order['name'] = 'asc';
			}
			$strict = TRUE;
		}
		//Debug::Arr($order, 'Order Data:', __FILE__, __LINE__, __METHOD__, 10);
		//Debug::Arr($filter_data, 'Filter Data:', __FILE__, __LINE__, __METHOD__, 10);

		$uf = new UserFactory();
		$cuff = new CompanyUserCountFactory();
		$puf = new PermissionUserFactory();
		$pcf = new PermissionControlFactory();

		$ph = array();

		$query = '
					SELECT	
							_ADODB_COUNT
							a.*,
							user_last_login.last_login_date as last_login_date,
							user_last_login.total_active_days as total_active_days,
							user_last_login.last_login_days as last_login_days,

							this_month_company_user_count.min_active_users as this_month_min_active_users,
							this_month_company_user_count.avg_active_users as this_month_avg_active_users,
							this_month_company_user_count.max_active_users as this_month_max_active_users,
							last_month_company_user_count.min_active_users as last_month_min_active_users,
							last_month_company_user_count.avg_active_users as last_month_avg_active_users,
							last_month_company_user_count.max_active_users as last_month_max_active_users,

							feedback_rating.regular_user_feedback_rating as regular_user_feedback_rating,
							feedback_rating.supervisor_user_feedback_rating as supervisor_user_feedback_rating,
							feedback_rating.admin_user_feedback_rating as admin_user_feedback_rating,
							feedback_rating.all_user_feedback_rating as all_user_feedback_rating,

							y.first_name as created_by_first_name,
							y.middle_name as created_by_middle_name,
							y.last_name as created_by_last_name,
							z.first_name as updated_by_first_name,
							z.middle_name as updated_by_middle_name,
							z.last_name as updated_by_last_name
							_ADODB_COUNT
					FROM 	'. $this->getTable() .' as a
						LEFT JOIN (
									SELECT
									company_id,
									max(last_login_date) as last_login_date,
									max(last_login_date)-min(cf.created_date) as total_active_days,
									( '. time() .'-max(last_login_date) ) as last_login_days
									FROM
									'. $uf->getTable() .' as uf
									LEFT JOIN '. $this->getTable() .' as cf ON ( uf.company_id = cf.id )
									AND uf.deleted = 0 AND cf.deleted = 0
									GROUP BY uf.company_id
						) as user_last_login ON ( a.id = user_last_login.company_id )
						LEFT JOIN (
									SELECT
									company_id,
									min(active_users) as min_active_users,
									avg(active_users) as avg_active_users,
									max(active_users) as max_active_users
									FROM '. $cuff->getTable() .' as cuf
									WHERE
									cuf.date_stamp >= '. $this->db->qstr( $this->db->BindDate( TTDate::getBeginMonthEpoch() ) ) .'
									AND cuf.date_stamp <= '. $this->db->qstr( $this->db->BindDate( time() ) ) .'
									GROUP BY company_id
						) as this_month_company_user_count ON ( a.id = this_month_company_user_count.company_id )
						LEFT JOIN (
									SELECT
									company_id,
									min(active_users) as min_active_users,
									avg(active_users) as avg_active_users,
									max(active_users) as max_active_users
									FROM '. $cuff->getTable() .' as cuf
									WHERE
									cuf.date_stamp >= '. $this->db->qstr( $this->db->BindDate( TTDate::getBeginMonthEpoch( (TTDate::getBeginMonthEpoch() - 86400) ) ) ) .'
									AND cuf.date_stamp <= '. $this->db->qstr( $this->db->BindDate( TTDate::getEndMonthEpoch( (TTDate::getBeginMonthEpoch() - 86400) ) ) ) .'
									GROUP BY company_id
						) as last_month_company_user_count ON ( a.id = last_month_company_user_count.company_id )
						LEFT JOIN (
									SELECT
									company_id,
									avg(regular_user_feedback_rating) as regular_user_feedback_rating,
									avg(supervisor_user_feedback_rating) as supervisor_user_feedback_rating,
									avg(admin_user_feedback_rating) as admin_user_feedback_rating,
									avg(all_user_feedback_rating) as all_user_feedback_rating
									FROM (
										SELECT
										uf.company_id as company_id,
										CASE WHEN pcf.level < 10 THEN feedback_rating ELSE NULL END as regular_user_feedback_rating,
										CASE WHEN pcf.level >= 10 AND pcf.level < 20 THEN feedback_rating ELSE NULL END as supervisor_user_feedback_rating,
										CASE WHEN pcf.level >= 20 THEN feedback_rating ELSE NULL END as admin_user_feedback_rating,
										feedback_rating as all_user_feedback_rating
										FROM '. $uf->getTable() .' as uf
										LEFT JOIN '. $puf->getTable() .' as puf ON ( uf.id = puf.user_id )
										LEFT JOIN '. $pcf->getTable() .' as pcf ON ( puf.permission_control_id = pcf.id )
										WHERE feedback_rating IS NOT NULL AND ( uf.deleted = 0 AND pcf.deleted = 0 )
									) as feedback_rating
									GROUP BY company_id
						) as feedback_rating ON ( a.id = feedback_rating.company_id )
						LEFT JOIN '. $uf->getTable() .' as y ON ( a.created_by = y.id AND y.deleted = 0 )
						LEFT JOIN '. $uf->getTable() .' as z ON ( a.updated_by = z.id AND z.deleted = 0 )
					WHERE	1=1
					';

		$query .= ( isset($filter_data['permission_children_ids']) ) ? $this->getWhereClauseSQL( 'a.created_by', $filter_data['permission_children_ids'], 'uuid_list', $ph ) : NULL;
		$query .= ( isset($filter_data['id']) ) ? $this->getWhereClauseSQL( 'a.id', $filter_data['id'], 'uuid_list', $ph ) : NULL;
		$query .= ( isset($filter_data['exclude_id']) ) ? $this->getWhereClauseSQL( 'a.id', $filter_data['exclude_id'], 'not_uuid_list', $ph ) : NULL;

		if ( isset($filter_data['status']) AND !is_array($filter_data['status']) AND trim($filter_data['status']) != '' AND !isset($filter_data['status_id']) ) {
			$filter_data['status_id'] = Option::getByFuzzyValue( $filter_data['status'], $this->getOptions('status') );
		}
		$query .= ( isset($filter_data['status_id']) ) ? $this->getWhereClauseSQL( 'a.status_id', $filter_data['status_id'], 'numeric_list', $ph ) : NULL;
		$query .= ( isset($filter_data['name']) ) ? $this->getWhereClauseSQL( 'a.name', $filter_data['name'], 'text_metaphone', $ph ) : NULL;
		$query .= ( isset($filter_data['short_name']) ) ? $this->getWhereClauseSQL( 'a.short_name', $filter_data['short_name'], 'text', $ph ) : NULL;

		$query .= ( isset($filter_data['product_edition_id']) ) ? $this->getWhereClauseSQL( 'a.product_edition_id', $filter_data['product_edition_id'], 'numeric_list', $ph ) : NULL;

		$query .= ( isset($filter_data['country']) ) ? $this->getWhereClauseSQL( 'a.country', $filter_data['country'], 'upper_text_list', $ph ) : NULL;
		$query .= ( isset($filter_data['province']) ) ? $this->getWhereClauseSQL( 'a.province', $filter_data['province'], 'upper_text_list', $ph ) : NULL;
		$query .= ( isset($filter_data['city']) ) ? $this->getWhereClauseSQL( 'a.city', $filter_data['city'], 'text', $ph ) : NULL;
		$query .= ( isset($filter_data['address1']) ) ? $this->getWhereClauseSQL( 'a.address1', $filter_data['address1'], 'text', $ph ) : NULL;
		$query .= ( isset($filter_data['address2']) ) ? $this->getWhereClauseSQL( 'a.address2', $filter_data['address2'], 'text', $ph ) : NULL;
		$query .= ( isset($filter_data['postal_code']) ) ? $this->getWhereClauseSQL( 'a.postal_code', $filter_data['postal_code'], 'text', $ph ) : NULL;
		$query .= ( isset($filter_data['work_phone']) ) ? $this->getWhereClauseSQL( 'a.work_phone', $filter_data['work_phone'], 'phone', $ph ) : NULL;
		$query .= ( isset($filter_data['fax_phone']) ) ? $this->getWhereClauseSQL( 'a.fax_phone', $filter_data['fax_phone'], 'phone', $ph ) : NULL;
		$query .= ( isset($filter_data['business_number']) ) ? $this->getWhereClauseSQL( 'a.business_number', $filter_data['business_number'], 'text', $ph ) : NULL;

		$query .= ( isset($filter_data['created_by']) ) ? $this->getWhereClauseSQL( array('a.created_by', 'y.first_name', 'y.last_name'), $filter_data['created_by'], 'user_id_or_name', $ph ) : NULL;
		$query .= ( isset($filter_data['updated_by']) ) ? $this->getWhereClauseSQL( array('a.updated_by', 'z.first_name', 'z.last_name'), $filter_data['updated_by'], 'user_id_or_name', $ph ) : NULL;

		$query .=	' AND a.deleted = 0 ';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order, $strict, $additional_order_fields );

		$this->rs = $this->ExecuteSQL( $query, $ph, $limit, $page );

		//Debug::Arr($ph, 'Query: '. $query, __FILE__, __LINE__, __METHOD__, 10);

		return $this;
	}
}
?>
