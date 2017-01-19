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
 * @package Modules\Policy
 */
class PayFormulaPolicyListFactory extends PayFormulaPolicyFactory implements IteratorAggregate {

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

		$this->rs = $this->getCache($id);
		if ( $this->rs === FALSE ) {
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

			$this->saveCache($this->rs, $id);
		}

		return $this;
	}

	function getByIdAndCompanyId($id, $company_id, $where = NULL, $order = NULL) {
		if ( $id == '') {
			return FALSE;
		}

		if ( $company_id == '') {
			return FALSE;
		}

		if ( $order == NULL ) {
			$order = array( 'name' => 'asc' );
			$strict = FALSE;
		} else {
			$strict = TRUE;
		}

		$ph = array(
					'company_id' => (int)$company_id,
					);

		$query = '
					select	*
					from	'. $this->getTable() .'
					where
						company_id = ?
						AND id in ('. $this->getListSQL( $id, $ph, 'int' ) .')
						AND deleted = 0';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order, $strict );

		$this->ExecuteSQL( $query, $ph );

		return $this;
	}

	function getByCompanyId($id, $where = NULL, $order = NULL) {
		if ( $id == '') {
			return FALSE;
		}

		if ( $order == NULL ) {
			$order = array( 'a.name' => 'asc' );
			$strict = FALSE;
		} else {
			$strict = TRUE;
		}

		$ph = array(
					'id' => (int)$id,
					);


		$query = '
					select	a.*
					from	'. $this->getTable() .' as a
					where	a.company_id = ?
						AND a.deleted = 0';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order, $strict );

		$this->ExecuteSQL( $query, $ph );

		return $this;
	}

	function getByCompanyIdAndContributingShiftPolicyId($id, $contributing_shift_policy_id, $where = NULL, $order = NULL) {
		if ( $id == '') {
			return FALSE;
		}

		if ( $contributing_shift_policy_id == '') {
			return FALSE;
		}

		if ( $order == NULL ) {
			$order = array( 'a.name' => 'asc' );
			$strict = FALSE;
		} else {
			$strict = TRUE;
		}

		$ph = array(
				'id' => (int)$id,
		);


		$query = '
					select	*
					from	'. $this->getTable() .' as a
					where	company_id = ?
						AND ( wage_source_contributing_shift_policy_id in ('. $this->getListSQL( $contributing_shift_policy_id, $ph, 'int' ) .') 
								OR time_source_contributing_shift_policy_id in ('. $this->getListSQL( $contributing_shift_policy_id, $ph, 'int' ) .') )
						AND deleted = 0';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order, $strict );

		$this->ExecuteSQL( $query, $ph );

		return $this;
	}

	function getByCompanyIdAndPayTypeId($id, $pay_type_id, $where = NULL, $order = NULL) {
		if ( $id == '') {
			return FALSE;
		}

		if ( $pay_type_id == '') {
			return FALSE;
		}

		if ( $order == NULL ) {
			$order = array( 'a.name' => 'asc' );
			$strict = FALSE;
		} else {
			$strict = TRUE;
		}

		$ph = array(
					'id' => (int)$id,
					'pay_type_id' => (int)$pay_type_id,
					);

		$query = '
					select	a.*
					from	'. $this->getTable() .' as a
					where	a.company_id = ?
						AND a.pay_type_id = ?
						AND a.deleted = 0';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order, $strict );

		$this->ExecuteSQL( $query, $ph );

		return $this;
	}

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

		$additional_order_fields = array('in_use');

		$sort_column_aliases = array(
									);

		$order = $this->getColumnsFromAliases( $order, $sort_column_aliases );

		if ( $order == NULL ) {
			$order = array( 'name' => 'asc');
			$strict = FALSE;
		} else {
			//Always sort by name after other columns
			if ( !isset($order['name']) ) {
				$order['name'] = 'asc';
			}
			$strict = TRUE;
		}
		//Debug::Arr($order, 'Order Data:', __FILE__, __LINE__, __METHOD__, 10);
		//Debug::Arr($filter_data, 'Filter Data:', __FILE__, __LINE__, __METHOD__, 10);

		$uf = new UserFactory();
		$pcf = new PayCodeFactory();
		$rtpf = new RegularTimePolicyFactory();
		$otpf = new OverTimePolicyFactory();
		$ppf = new PremiumPolicyFactory();
		$apf = new AbsencePolicyFactory();
		$mpf = new MealPolicyFactory();
		$bpf = new BreakPolicyFactory();

		$ph = array(
					'company_id' => (int)$company_id,
					);

		$query = '
					select	a.*,
							_ADODB_COUNT
							(
								CASE WHEN EXISTS
									( select 1 from '. $pcf->getTable() .' as x where x.pay_formula_policy_id = a.id and x.deleted = 0)
								THEN 1
								ELSE
									CASE WHEN EXISTS
										( select 1 from '. $rtpf->getTable() .' as x where x.pay_formula_policy_id = a.id and x.deleted = 0)
									THEN 1
									ELSE
										CASE WHEN EXISTS
											( select 1 from '. $otpf->getTable() .' as x where x.pay_formula_policy_id = a.id and x.deleted = 0)
										THEN 1
										ELSE
											CASE WHEN EXISTS
												( select 1 from '. $ppf->getTable() .' as y where y.pay_formula_policy_id = a.id and y.deleted = 0)
											THEN 1
											ELSE
												CASE WHEN EXISTS
													( select 1 from '. $apf->getTable() .' as z where z.pay_formula_policy_id = a.id and z.deleted = 0)
												THEN 1
												ELSE
													CASE WHEN EXISTS
														( select 1 from '. $mpf->getTable() .' as x where x.pay_formula_policy_id = a.id and x.deleted = 0)
													THEN 1
													ELSE
														CASE WHEN EXISTS
															( select 1 from '. $bpf->getTable() .' as x where x.pay_formula_policy_id = a.id and x.deleted = 0)
														THEN 1
														ELSE 0
														END
													END
												END
											END
										END
									END
								END
							) as in_use,
							y.first_name as created_by_first_name,
							y.middle_name as created_by_middle_name,
							y.last_name as created_by_last_name,
							z.first_name as updated_by_first_name,
							z.middle_name as updated_by_middle_name,
							z.last_name as updated_by_last_name
							_ADODB_COUNT
					from	'. $this->getTable() .' as a
						LEFT JOIN '. $uf->getTable() .' as y ON ( a.created_by = y.id AND y.deleted = 0 )
						LEFT JOIN '. $uf->getTable() .' as z ON ( a.updated_by = z.id AND z.deleted = 0 )
					where	a.company_id = ?
					';

		$query .= ( isset($filter_data['permission_children_ids']) ) ? $this->getWhereClauseSQL( 'a.created_by', $filter_data['permission_children_ids'], 'numeric_list', $ph ) : NULL;
		$query .= ( isset($filter_data['id']) ) ? $this->getWhereClauseSQL( 'a.id', $filter_data['id'], 'numeric_list', $ph ) : NULL;
		$query .= ( isset($filter_data['exclude_id']) ) ? $this->getWhereClauseSQL( 'a.id', $filter_data['exclude_id'], 'not_numeric_list', $ph ) : NULL;

		$query .= ( isset($filter_data['name']) ) ? $this->getWhereClauseSQL( 'a.name', $filter_data['name'], 'text', $ph ) : NULL;
		$query .= ( isset($filter_data['description']) ) ? $this->getWhereClauseSQL( 'a.description', $filter_data['description'], 'text', $ph ) : NULL;

		$query .= ( isset($filter_data['pay_type_id']) ) ? $this->getWhereClauseSQL( 'a.pay_type_id', $filter_data['pay_type_id'], 'numeric_list', $ph ) : NULL;
		$query .= ( isset($filter_data['wage_source_type_id']) ) ? $this->getWhereClauseSQL( 'a.wage_source_type_id', $filter_data['wage_source_type_id'], 'numeric_list', $ph ) : NULL;

		$query .= ( isset($filter_data['wage_group_id']) ) ? $this->getWhereClauseSQL( 'a.wage_group_id', $filter_data['wage_group_id'], 'numeric_list', $ph ) : NULL;
		$query .= ( isset($filter_data['accrual_policy_account_id']) ) ? $this->getWhereClauseSQL( 'a.accrual_policy_account_id', $filter_data['accrual_policy_account_id'], 'numeric_list', $ph ) : NULL;

		$query .= ( isset($filter_data['created_by']) ) ? $this->getWhereClauseSQL( array('a.created_by', 'y.first_name', 'y.last_name'), $filter_data['created_by'], 'user_id_or_name', $ph ) : NULL;
		$query .= ( isset($filter_data['updated_by']) ) ? $this->getWhereClauseSQL( array('a.updated_by', 'z.first_name', 'z.last_name'), $filter_data['updated_by'], 'user_id_or_name', $ph ) : NULL;

		$query .=	' AND a.deleted = 0 ';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order, $strict, $additional_order_fields );

		$this->ExecuteSQL( $query, $ph, $limit, $page );

		return $this;
	}
}
?>
