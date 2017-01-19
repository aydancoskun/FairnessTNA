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
 * @package Modules\Accrual
 */
class AccrualListFactory extends AccrualFactory implements IteratorAggregate {

	function getAll($limit = NULL, $page = NULL, $where = NULL, $order = NULL) {
		$query = '
					select	*
					from	'. $this->getTable() .'
					WHERE deleted = 0';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );

		$this->ExecuteSQL( $query, NULL, $limit, $page );

		return $this;
	}

	function getById($id, $where = NULL, $order = NULL) {
		if ( $id == '') {
			return FALSE;
		}

		$ph = array(
					'id' => (int)$id,
					);

		$query = '
					select	*
					from	'. $this->getTable() .'
					where	id = ?
						AND deleted = 0';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );

		$this->ExecuteSQL( $query, $ph );

		return $this;
	}


	function getByAccrualPolicyId( $id, $limit = NULL, $page = NULL, $where = NULL, $order = NULL ) {
		if ( $id == '') {
			return FALSE;
		}

		$ph = array(
			'accrual_policy_id' => (int)$id,
		);

		$query = '
					select	*
					from	'. $this->getTable() .'
					where	accrual_policy_id = ?
						AND deleted = 0';

		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );

		$this->ExecuteSQL( $query, $ph, $limit, $page );
						
		return $this;
	}

	function getByIdAndCompanyId($id, $company_id, $where = NULL, $order = NULL) {
		if ( $id == '') {
			return FALSE;
		}

		if ( $company_id == '') {
			return FALSE;
		}

		$uf = new UserFactory();

		$ph = array(
					'id' => (int)$id,
					'company_id' => (int)$company_id
					);

		$query = '
					select	a.*
					from	'. $this->getTable() .' as a
						LEFT JOIN  '. $uf->getTable() .' as b on a.user_id = b.id
					where	a.id = ?
						AND b.company_id = ?
						AND a.deleted = 0';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );

		$this->ExecuteSQL( $query, $ph );

		return $this;
	}

	function getByCompanyId($company_id, $where = NULL, $order = NULL) {
		if ( $company_id == '') {
			return FALSE;
		}

		$uf = new UserFactory();

		$ph = array(
					'company_id' => (int)$company_id
					);

		$query = '
					select	a.*
					from	'. $this->getTable() .' as a
						LEFT JOIN  '. $uf->getTable() .' as b on a.user_id = b.id
					where	b.company_id = ?
						AND a.deleted = 0';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );

		$this->ExecuteSQL( $query, $ph );

		return $this;
	}

	function getByUserIdAndCompanyId($user_id, $company_id, $where = NULL, $order = NULL) {
		if ( $user_id == '') {
			return FALSE;
		}

		if ( $company_id == '') {
			return FALSE;
		}

		$ph = array(
					'user_id' => (int)$user_id,
					'company_id' => (int)$company_id
					);

		$uf = new UserFactory();

		$query = '
					select	a.*
					from	'. $this->getTable() .' as a
						LEFT JOIN '. $uf->getTable() .' as b ON a.user_id = b.id
					where	a.user_id = ?
						AND b.company_id = ?
						AND a.deleted = 0';

		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );

		$this->ExecuteSQL( $query, $ph );

		return $this;
	}

	function getByCompanyIdAndUserIdAndAccrualPolicyAccount($company_id, $user_id, $accrual_policy_account_id, $limit = NULL, $page = NULL, $where = NULL, $order = NULL) {
		if ( $company_id == '') {
			return FALSE;
		}

		if ( $user_id == '') {
			return FALSE;
		}

		if ( $accrual_policy_account_id == '') {
			return FALSE;
		}

		$strict_order = TRUE;
		if ( $order == NULL ) {
			$order = array('c.date_stamp' => 'desc', 'a.time_stamp' => 'desc');
			$strict_order = FALSE;
		}

		$uf = new UserFactory();
		$udtf = new UserDateTotalFactory();

		$ph = array(
					'user_id' => (int)$user_id,
					'company_id' => (int)$company_id,
					'accrual_policy_account_id' => (int)$accrual_policy_account_id,
					);

		$query = '
					select	a.*,
							c.date_stamp as date_stamp
					from	'. $this->getTable() .' as a
							LEFT JOIN '. $uf->getTable() .' as b ON a.user_id = b.id
							LEFT JOIN '. $udtf->getTable() .' as c ON a.user_date_total_id = c.id
					where
						a.user_id = ?
						AND b.company_id = ?
						AND a.accrual_policy_account_id = ?
						AND ( a.user_date_total_id IS NULL OR ( a.user_date_total_id IS NOT NULL AND c.deleted = 0 ) )
						AND ( a.deleted = 0 AND b.deleted = 0 )';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order, $strict_order );

		$this->ExecuteSQL( $query, $ph, $limit, $page );

		return $this;
	}

	function getByCompanyIdAndUserIdAndAccrualPolicyAccountAndTimeStampAndAmount($company_id, $user_id, $accrual_policy_account_id, $time_stamp, $amount, $limit = NULL, $page = NULL, $where = NULL, $order = NULL) {
		if ( $company_id == '') {
			return FALSE;
		}

		if ( $user_id == '') {
			return FALSE;
		}

		if ( $accrual_policy_account_id == '') {
			return FALSE;
		}

		if ( $time_stamp == '') {
			return FALSE;
		}

		if ( $amount == '') {
			return FALSE;
		}

		$strict_order = TRUE;
		if ( $order == NULL ) {
			$order = array('c.date_stamp' => 'desc', 'a.time_stamp' => 'desc');
			$strict_order = FALSE;
		}

		$uf = new UserFactory();
		$udtf = new UserDateTotalFactory();

		$ph = array(
					'user_id' => (int)$user_id,
					'company_id' => (int)$company_id,
					'accrual_policy_account_id' => (int)$accrual_policy_account_id,
					'time_stamp' => $this->db->BindTimeStamp( $time_stamp ),
					'amount' => $amount,
					);

		$query = '
					select	a.*,
							c.date_stamp as date_stamp
					from	'. $this->getTable() .' as a
							LEFT JOIN '. $uf->getTable() .' as b ON a.user_id = b.id
							LEFT JOIN '. $udtf->getTable() .' as c ON a.user_date_total_id = c.id
					where
						a.user_id = ?
						AND b.company_id = ?
						AND a.accrual_policy_account_id = ?
						AND a.time_stamp = ?
						AND a.amount = ?
						AND a.deleted = 0';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order, $strict_order );

		$this->ExecuteSQL( $query, $ph, $limit, $page );

		return $this;
	}

	function getByCompanyIdAndUserIdAndAccrualPolicyAccountAndTypeIDAndTimeStamp($company_id, $user_id, $accrual_policy_account_id, $type_id, $time_stamp, $limit = NULL, $page = NULL, $where = NULL, $order = NULL) {
		if ( $company_id == '') {
			return FALSE;
		}

		if ( $user_id == '') {
			return FALSE;
		}

		if ( $accrual_policy_account_id == '') {
			return FALSE;
		}

		if ( $type_id == '') {
			return FALSE;
		}

		if ( $time_stamp == '') {
			return FALSE;
		}

		$strict_order = TRUE;
		if ( $order == NULL ) {
			$order = array('c.date_stamp' => 'desc', 'a.time_stamp' => 'desc');
			$strict_order = FALSE;
		}

		$uf = new UserFactory();
		$udtf = new UserDateTotalFactory();

		$ph = array(
					'user_id' => (int)$user_id,
					'company_id' => (int)$company_id,
					'accrual_policy_account_id' => (int)$accrual_policy_account_id,
					'type_id' => (int)$type_id,
					'time_stamp' => $this->db->BindTimeStamp( $time_stamp ),
					);

		$query = '
					select	a.*,
							c.date_stamp as date_stamp
					from	'. $this->getTable() .' as a
							LEFT JOIN '. $uf->getTable() .' as b ON a.user_id = b.id
							LEFT JOIN '. $udtf->getTable() .' as c ON a.user_date_total_id = c.id
					where
						a.user_id = ?
						AND b.company_id = ?
						AND a.accrual_policy_account_id = ?
						AND a.type_id = ?
						AND a.time_stamp = ?
						AND a.deleted = 0';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order, $strict_order );

		$this->ExecuteSQL( $query, $ph, $limit, $page );

		return $this;
	}

	function getByUserIdAndAccrualPolicyAccountAndUserDateTotalID($user_id, $accrual_policy_account_id, $user_date_total_id, $where = NULL, $order = NULL) {
		if ( $user_id == '') {
			return FALSE;
		}

		if ( $accrual_policy_account_id == '') {
			return FALSE;
		}

		if ( $user_date_total_id == '') {
			return FALSE;
		}

		$ph = array(
					'user_id' => (int)$user_id,
					'accrual_policy_account_id' => (int)$accrual_policy_account_id,
					'user_date_total_id' => (int)$user_date_total_id,
					);

		$query = '
					select	*
					from	'. $this->getTable() .'
					where	user_id = ?
						AND accrual_policy_account_id = ?
						AND user_date_total_id = ?
						AND deleted = 0';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );

		$this->ExecuteSQL( $query, $ph );

		return $this;
	}

	function getByUserIdAndAccrualPolicyAccountAndAccrualPolicyAndUserDateTotalID($user_id, $accrual_policy_account_id, $accrual_policy_id, $user_date_total_id, $where = NULL, $order = NULL) {
		if ( $user_id == '') {
			return FALSE;
		}

		if ( $accrual_policy_account_id == '') {
			return FALSE;
		}

		if ( $user_date_total_id == '') {
			return FALSE;
		}

		//Allow accrual_policy_id to be 0. Because if the user does a manual override on UserDateTotal record, there may not be a accrual policy ID specified.
		//This fixed bug with duplicate accrual entries that occurred when UDT records are overridden too.
		//if ( $accrual_policy_id == '') {
		//	return FALSE;
		//}

		$ph = array(
					'user_id' => (int)$user_id,
					'accrual_policy_account_id' => (int)$accrual_policy_account_id,
					'accrual_policy_id' => (int)$accrual_policy_id,
					'user_date_total_id' => (int)$user_date_total_id,
					);

		$query = '
					select	*
					from	'. $this->getTable() .'
					where	user_id = ?
						AND accrual_policy_account_id = ?
						AND accrual_policy_id = ?
						AND user_date_total_id = ?
						AND deleted = 0';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );

		$this->ExecuteSQL( $query, $ph );

		//Debug::Arr($ph, 'Query: '. $query, __FILE__, __LINE__, __METHOD__, 10);

		return $this;
	}

	function getByUserIdAndUserDateTotalID($user_id, $user_date_total_id, $where = NULL, $order = NULL) {
		if ( $user_id == '') {
			return FALSE;
		}

		if ( $user_date_total_id == '') {
			return FALSE;
		}

		$ph = array(
					'user_id' => (int)$user_id,
					'user_date_total_id' => (int)$user_date_total_id,
					);

		$query = '
					select	*
					from	'. $this->getTable() .'
					where	user_id = ?
						AND user_date_total_id = ?
						AND deleted = 0';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );

		$this->ExecuteSQL( $query, $ph );

		return $this;
	}

	function getOrphansByUserId($user_id, $where = NULL, $order = NULL) {
		if ( $user_id == '') {
			return FALSE;
		}

		$apaf = new AccrualPolicyAccountFactory();
		$udtf = new UserDateTotalFactory();

		//
		// getOrphansByUserIdAndDate() AND getOrphansByUserId() are similar, may need to modify both!
		// Also check UserDateTotalListFactory->getAccrualOrphansByPayCodeIdAndStartDateAndEndDate()
		//

		$ph = array(
					'user_id' => $user_id,
					);

		//**IMPORTANT: This function is different than getOrphansByUserIDAndDate() as it should only be called from fix_accrual_balance
		//and it should only return *REAL* orphans that link to UDT records that don't exist.
		//getOrphansByUserIDAndDate() also seems to return any UDT record from an absence so it can be deleted and recreated.
		$query = '
					select	a.*
					from	'. $this->getTable() .' as a
					LEFT JOIN '. $udtf->getTable() .' as b ON a.user_date_total_id = b.id
					LEFT JOIN '. $apaf->getTable() .' as d ON a.accrual_policy_account_id = d.id
					where	a.user_id = ?
						AND ( b.id is NULL OR b.deleted = 1 )
						AND ( a.type_id = 10 OR a.type_id = 20 OR a.type_id = 76 )
						AND a.deleted = 0';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );

		//Debug::Arr($ph, 'Query: '. $query, __FILE__, __LINE__, __METHOD__, 10);

		$this->ExecuteSQL( $query, $ph );

		return $this;
	}

	function getOrphansByUserIdAndDate($user_id, $date_stamp, $where = NULL, $order = NULL) {
		if ( $user_id == '') {
			return FALSE;
		}

		if ( $date_stamp == '' ) {
			return FALSE;
		}

		$apaf = new AccrualPolicyAccountFactory();
		$udtf = new UserDateTotalFactory();

		//
		// getOrphansByUserIdAndDate() AND getOrphansByUserId() are similar, may need to modify both!
		// Also check UserDateTotalListFactory->getAccrualOrphansByPayCodeIdAndStartDateAndEndDate()		
		//

		$ph = array(
					'user_id' => (int)$user_id,
					//Filter the accrual rows to one day before and one day after as an optimization.
					//This causes problems when only calculating one day, or the last day of the week though, it will delete
					//accruals for the previous day and not put them back if needed.
					//'date_stamp1' => $this->db->BindDate( TTDate::getBeginDayEpoch( (TTDate::getMiddleDayEpoch( $date_stamp ) - 86400) ) ),
					//'date_stamp2' => $this->db->BindDate( TTDate::getBeginDayEpoch( (TTDate::getMiddleDayEpoch( $date_stamp ) + 86400) ) ),

					//Only handle accruals on the day we are actually going to recalculate.
					//Since this uses time_stamps, make sure we cover the entire day (all 24hrs)
					'date_stamp1' => $this->db->BindTimeStamp( TTDate::getBeginDayEpoch( $date_stamp )  ),
					'date_stamp2' => $this->db->BindTimeStamp( TTDate::getEndDayEpoch( $date_stamp ) ),
					);
					
		//*Also consider UDT records assigned to object_type_id=25,50 records to be orphans. As they should only be assigned to object_type_id=25,50.
		$query = '
					select	a.*
					from	'. $this->getTable() .' as a
					LEFT JOIN '. $udtf->getTable() .' as b ON a.user_date_total_id = b.id
					LEFT JOIN '. $apaf->getTable() .' as d ON a.accrual_policy_account_id = d.id
					where	a.user_id = ?
						AND ( a.time_stamp >= ? AND a.time_stamp <= ? )
						AND ( b.id is NULL OR b.deleted = 1 OR b.object_type_id in ( 25, 50 ) )
						AND ( a.type_id = 10 OR a.type_id = 20 OR a.type_id = 76 )
						AND a.deleted = 0';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );

		//Debug::Arr($ph, 'Query: '. $query, __FILE__, __LINE__, __METHOD__, 10);

		$this->ExecuteSQL( $query, $ph );

		return $this;
	}

	function getSumByUserIdAndAccrualPolicyAccount($user_id, $accrual_policy_account_id) {
		if ( $user_id == '') {
			return FALSE;
		}

		if ( $accrual_policy_account_id == '') {
			return FALSE;
		}

		$udtf = new UserDateTotalFactory();

		$ph = array(
					'user_id' => (int)$user_id,
					'accrual_policy_account_id' => (int)$accrual_policy_account_id,
					);

		//Make sure orphaned records that slip through are not counted in the balance.
		$query = '
					select	sum(amount) as amount
					from	'. $this->getTable() .' as a
					LEFT JOIN '. $udtf->getTable() .' as b ON a.user_date_total_id = b.id
					where	a.user_id = ?
						AND a.accrual_policy_account_id = ?
						AND ( ( a.user_date_total_id is NOT NULL AND b.id is NOT NULL AND b.deleted = 0 )
								OR ( a.user_date_total_id IS NULL AND b.id is NULL ) )
						AND a.deleted = 0';

		$total = $this->db->GetOne($query, $ph);

		if ($total === FALSE ) {
			$total = 0;
		}
		Debug::text('Balance: '. $total, __FILE__, __LINE__, __METHOD__, 10);

		return $total;
	}

	function getSumByUserIdAndAccrualPolicyAccountAndAfterDate($user_id, $accrual_policy_account_id, $epoch ) {
		if ( $user_id == '') {
			return FALSE;
		}

		if ( $epoch == '') {
			return FALSE;
		}

		if ( $accrual_policy_account_id == '') {
			return FALSE;
		}

		$udtf = new UserDateTotalFactory();

		$ph = array(
					'user_id' => (int)$user_id,
					'accrual_policy_account_id' => (int)$accrual_policy_account_id,
					'time_stamp' => $this->db->BindTimeStamp( $epoch ),
					);

		//Make sure orphaned records that slip through are not counted in the balance.
		$query = '
					select	sum(amount) as amount
					from	'. $this->getTable() .' as a
					LEFT JOIN '. $udtf->getTable() .' as b ON a.user_date_total_id = b.id
					where	a.user_id = ?
						AND a.accrual_policy_account_id = ?
						AND ( ( a.user_date_total_id is NOT NULL AND b.id is NOT NULL AND b.deleted = 0 )
								OR ( a.user_date_total_id IS NULL AND b.id is NULL ) )
						AND a.time_stamp >= ?
						AND a.deleted = 0';

		$total = $this->db->GetOne($query, $ph);

		if ($total === FALSE ) {
			$total = 0;
		}
		Debug::text('Balance After Date: '. $total, __FILE__, __LINE__, __METHOD__, 10);

		return $total;
	}

	function getSumByUserIdAndAccrualPolicyAccountAndTypeAndStartDateAndEndDate($user_id, $accrual_policy_account_id, $type_id, $start_date, $end_date ) {
		if ( $user_id == '') {
			return FALSE;
		}

		if ( $accrual_policy_account_id == '') {
			return FALSE;
		}

		if ( $start_date == '') {
			return FALSE;
		}
		if ( $end_date == '') {
			return FALSE;
		}

		$udtf = new UserDateTotalFactory();

		$ph = array(
					'user_id' => (int)$user_id,
					'accrual_policy_account_id' => (int)$accrual_policy_account_id,
					'start_date' => $this->db->BindTimeStamp( $start_date ),
					'end_date' => $this->db->BindTimeStamp( $end_date ),
					);

		//Make sure orphaned records that slip through are not counted in the balance.
		$query = '
					select	sum(amount) as amount
					from	'. $this->getTable() .' as a
					LEFT JOIN '. $udtf->getTable() .' as b ON a.user_date_total_id = b.id
					where	a.user_id = ?
						AND a.accrual_policy_account_id = ?
						AND ( ( a.user_date_total_id is NOT NULL AND b.id is NOT NULL AND b.deleted = 0 )
								OR ( a.user_date_total_id IS NULL AND b.id is NULL ) )
						AND a.time_stamp >= ?
						AND a.time_stamp <= ?
						AND a.type_id in ('. $this->getListSQL( $type_id, $ph, 'int' ) .')
						AND a.deleted = 0';

		$total = $this->db->GetOne($query, $ph);

		if ($total === FALSE ) {
			$total = 0;
		}
		Debug::text('Balance After Date: '. $total, __FILE__, __LINE__, __METHOD__, 10);

		return $total;
	}

	function getByAccrualPolicyAccount($accrual_policy_account_id, $where = NULL, $order = NULL) {
		if ( $accrual_policy_account_id == '') {
			return FALSE;
		}

		$ph = array(
					'accrual_policy_account_id' => (int)$accrual_policy_account_id,
					);

		$uf = new UserFactory();
		$udtf = new UserDateTotalFactory();

		//Make sure we handle orphaned records attached to UDT records.
		$query = '
					select	a.*
					from	'. $this->getTable() .' as a
							LEFT JOIN '. $uf->getTable() .' as b ON a.user_id = b.id
							LEFT JOIN '. $udtf->getTable() .' as udtf ON ( a.user_date_total_id = udtf.id AND udtf.deleted = 0 )
					where	a.accrual_policy_account_id = ?
						AND ( ( a.user_date_total_id is NOT NULL AND udtf.id is NOT NULL AND udtf.deleted = 0 ) OR ( a.user_date_total_id IS NULL AND udtf.id is NULL ) )
						AND ( a.deleted = 0 AND b.deleted = 0 )
					LIMIT 1
				';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );

		$this->ExecuteSQL( $query, $ph );

		//Debug::Arr($ph, 'Query: '. $query, __FILE__, __LINE__, __METHOD__, 10);

		return $this;
	}

	function getAPISearchByCompanyIdAndArrayCriteria( $company_id, $filter_data, $limit = NULL, $page = NULL, $where = NULL, $order = NULL ) {
		if ( $company_id == '') {
			return FALSE;
		}

		if ( isset($filter_data['user_status_id']) ) {
			$filter_data['status_id'] = $filter_data['user_status_id'];
		}
		if ( isset($filter_data['user_group_id']) ) {
			$filter_data['group_id'] = $filter_data['user_group_id'];
		}
		if ( isset($filter_data['user_title_id']) ) {
			$filter_data['title_id'] = $filter_data['user_title_id'];
		}
		if ( isset($filter_data['include_user_id']) ) {
			$filter_data['user_id'] = $filter_data['include_user_id'];
		}
		if ( isset($filter_data['exclude_user_id']) ) {
			$filter_data['exclude_id'] = $filter_data['exclude_user_id'];
		}
		if ( isset($filter_data['accrual_type_id']) ) {
			$filter_data['type_id'] = $filter_data['accrual_type_id'];
		}

		if ( !is_array($order) ) {
			//Use Filter Data ordering if its set.
			if ( isset($filter_data['sort_column']) AND $filter_data['sort_order']) {
				$order = array(Misc::trimSortPrefix($filter_data['sort_column']) => $filter_data['sort_order']);
			}
		}

		if ( isset( $filter_data['accrual_type_id'] ) ) {
			$filter_data['type_id'] = $filter_data['accrual_type_id'];
		}

		$additional_order_fields = array( 'accrual_policy_account', 'accrual_policy', 'accrual_policy_type_id', 'date_stamp' );
		$sort_column_aliases = array(
									'accrual_policy_type' => 'accrual_policy_type_id',
									'type' => 'type_id',
									);
		$order = $this->getColumnsFromAliases( $order, $sort_column_aliases );
		if ( $order == NULL ) {
			$order = array( 'accrual_policy_account_id' => 'asc', 'date_stamp' => 'desc');
			$strict = FALSE;
		} else {
			$strict = TRUE;
		}
		//Debug::Arr($order, 'Order Data:', __FILE__, __LINE__, __METHOD__, 10);
		//Debug::Arr($filter_data, 'Filter Data:', __FILE__, __LINE__, __METHOD__, 10);

		$uf = new UserFactory();
		$udtf = new UserDateTotalFactory();
		$bf = new BranchFactory();
		$df = new DepartmentFactory();
		$ugf = new UserGroupFactory();
		$utf = new UserTitleFactory();
		$apaf = new AccrualPolicyAccountFactory();
		$apf = new AccrualPolicyFactory();

		$ph = array(
					'company_id' => (int)$company_id,
					);

		//If changes are made to UDTF joining, also update getByAccrualPolicyAccount().
		$query = '
					select	a.*,
							ab.name as accrual_policy_account,
							apf.name as accrual_policy,
							apf.type_id as accrual_policy_type_id,
							CASE WHEN udtf.date_stamp is NOT NULL THEN udtf.date_stamp ELSE a.time_stamp END as date_stamp,
							b.first_name as first_name,
							b.last_name as last_name,
							b.country as country,
							b.province as province,

							c.id as default_branch_id,
							c.name as default_branch,
							d.id as default_department_id,
							d.name as default_department,
							e.id as group_id,
							e.name as "user_group",
							f.id as title_id,
							f.name as title,
							y.first_name as created_by_first_name,
							y.middle_name as created_by_middle_name,
							y.last_name as created_by_last_name,
							z.first_name as updated_by_first_name,
							z.middle_name as updated_by_middle_name,
							z.last_name as updated_by_last_name
					from	'. $this->getTable() .' as a
						LEFT JOIN '. $apaf->getTable() .' as ab ON ( a.accrual_policy_account_id = ab.id AND ab.deleted = 0 )
						LEFT JOIN '. $apf->getTable() .' as apf ON ( a.accrual_policy_id = apf.id AND apf.deleted = 0 )
						LEFT JOIN '. $uf->getTable() .' as b ON ( a.user_id = b.id AND b.deleted = 0 )

						LEFT JOIN '. $udtf->getTable() .' as udtf ON ( a.user_date_total_id = udtf.id AND udtf.deleted = 0 )

						LEFT JOIN '. $bf->getTable() .' as c ON ( b.default_branch_id = c.id AND c.deleted = 0)
						LEFT JOIN '. $df->getTable() .' as d ON ( b.default_department_id = d.id AND d.deleted = 0)
						LEFT JOIN '. $ugf->getTable() .' as e ON ( b.group_id = e.id AND e.deleted = 0 )
						LEFT JOIN '. $utf->getTable() .' as f ON ( b.title_id = f.id AND f.deleted = 0 )
						LEFT JOIN '. $uf->getTable() .' as y ON ( a.created_by = y.id AND y.deleted = 0 )
						LEFT JOIN '. $uf->getTable() .' as z ON ( a.updated_by = z.id AND z.deleted = 0 )

					where	b.company_id = ?
					';

		$query .= ( isset($filter_data['permission_children_ids']) ) ? $this->getWhereClauseSQL( 'a.user_id', $filter_data['permission_children_ids'], 'numeric_list', $ph ) : NULL;
		$query .= ( isset($filter_data['user_id']) ) ? $this->getWhereClauseSQL( 'a.user_id', $filter_data['user_id'], 'numeric_list', $ph ) : NULL;
		$query .= ( isset($filter_data['id']) ) ? $this->getWhereClauseSQL( 'a.id', $filter_data['id'], 'numeric_list', $ph ) : NULL;
		$query .= ( isset($filter_data['exclude_id']) ) ? $this->getWhereClauseSQL( 'a.user_id', $filter_data['exclude_id'], 'not_numeric_list', $ph ) : NULL;

		if ( isset($filter_data['type']) AND !is_array($filter_data['type']) AND trim($filter_data['type']) != '' AND !isset($filter_data['type_id']) ) {
			$filter_data['type_id'] = Option::getByFuzzyValue( $filter_data['type'], $this->getOptions('type') );
		}
		$query .= ( isset($filter_data['type_id']) ) ? $this->getWhereClauseSQL( 'a.type_id', $filter_data['type_id'], 'numeric_list', $ph ) : NULL;

		if ( isset($filter_data['status']) AND !is_array($filter_data['status']) AND trim($filter_data['status']) != '' AND !isset($filter_data['status_id']) ) {
			$filter_data['status_id'] = Option::getByFuzzyValue( $filter_data['status'], $this->getOptions('status') );
		}

		//$query .= ( isset($filter_data['accrual_policy_type_id']) ) ? $this->getWhereClauseSQL( 'ab.type_id', $filter_data['accrual_policy_type_id'], 'numeric_list', $ph ) : NULL;
		$query .= ( isset($filter_data['accrual_policy_account_id']) ) ? $this->getWhereClauseSQL( 'a.accrual_policy_account_id', $filter_data['accrual_policy_account_id'], 'numeric_list', $ph ) : NULL;

		$query .= ( isset($filter_data['status_id']) ) ? $this->getWhereClauseSQL( 'b.status_id', $filter_data['status_id'], 'numeric_list', $ph ) : NULL;
		$query .= ( isset($filter_data['group_id']) ) ? $this->getWhereClauseSQL( 'b.group_id', $filter_data['group_id'], 'numeric_list', $ph ) : NULL;
		$query .= ( isset($filter_data['default_branch_id']) ) ? $this->getWhereClauseSQL( 'b.default_branch_id', $filter_data['default_branch_id'], 'numeric_list', $ph ) : NULL;
		$query .= ( isset($filter_data['default_department_id']) ) ? $this->getWhereClauseSQL( 'b.default_department_id', $filter_data['default_department_id'], 'numeric_list', $ph ) : NULL;
		$query .= ( isset($filter_data['title_id']) ) ? $this->getWhereClauseSQL( 'b.title_id', $filter_data['title_id'], 'numeric_list', $ph ) : NULL;
		$query .= ( isset($filter_data['country']) ) ? $this->getWhereClauseSQL( 'b.country', $filter_data['country'], 'upper_text_list', $ph ) : NULL;
		$query .= ( isset($filter_data['province']) ) ? $this->getWhereClauseSQL( 'b.province', $filter_data['province'], 'upper_text_list', $ph ) : NULL;

		$query .= ( isset($filter_data['pay_period_id']) ) ? $this->getWhereClauseSQL( 'udtf.pay_period_id', $filter_data['pay_period_id'], 'numeric_list', $ph ) : NULL;

		if ( isset($filter_data['start_date']) AND !is_array($filter_data['start_date']) AND trim($filter_data['start_date']) != '' ) {
			$ph[] = $this->db->BindTimeStamp( (int)$filter_data['start_date'] );
			$ph[] = $this->db->BindDate( (int)$filter_data['start_date'] );
			$query	.=	' AND ( ( udtf.date_stamp is NULL AND a.time_stamp >= ? ) OR ( udtf.date_stamp is NOT NULL AND udtf.date_stamp >= ? ) )';
		}
		if ( isset($filter_data['end_date']) AND !is_array($filter_data['end_date']) AND trim($filter_data['end_date']) != '' ) {
			$ph[] = $this->db->BindTimeStamp( (int)$filter_data['end_date'] );
			$ph[] = $this->db->BindDate( (int)$filter_data['end_date'] );
			$query	.=	' AND ( ( udtf.date_stamp is NULL AND a.time_stamp <= ? ) OR ( udtf.date_stamp is NOT NULL AND udtf.date_stamp <= ? ) )';
		}

		$query .= ( isset($filter_data['created_by']) ) ? $this->getWhereClauseSQL( array('a.created_by', 'y.first_name', 'y.last_name'), $filter_data['created_by'], 'user_id_or_name', $ph ) : NULL;
		$query .= ( isset($filter_data['updated_by']) ) ? $this->getWhereClauseSQL( array('a.updated_by', 'z.first_name', 'z.last_name'), $filter_data['updated_by'], 'user_id_or_name', $ph ) : NULL;

		//Make sure we exclude delete user_date_total records, so we match the accrual balances.
		$query .=	' 	AND ( ( a.user_date_total_id is NOT NULL AND udtf.id is NOT NULL AND udtf.deleted = 0 ) OR ( a.user_date_total_id IS NULL AND udtf.id is NULL ) )
						AND a.deleted = 0 ';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order, $strict, $additional_order_fields );

		//Debug::Arr($ph, 'Query: '. $query, __FILE__, __LINE__, __METHOD__, 10);

		$this->ExecuteSQL( $query, $ph, $limit, $page );

		return $this;
	}
}
?>
