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
class PayrollRemittanceAgencyListFactory extends PayrollRemittanceAgencyFactory implements IteratorAggregate {

	/**
	 * @param int $limit Limit the number of records returned
	 * @param int $page Page number of records to return for pagination
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return $this
	 */
	function getAll( $limit = NULL, $page = NULL, $where = NULL, $order = NULL ) {
		$query = '
					select	*
					from	' . $this->getTable() . '
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
	 * @return bool|PayrollRemittanceAgencyListFactory
	 */
	function getById( $id, $where = NULL, $order = NULL ) {
		if ( $id == '' ) {
			return FALSE;
		}

		$this->rs = $this->getCache( $id );
		if ( $this->rs === FALSE ) {
			$ph = array(
					'id' => TTUUID::castUUID( $id ),
			);

			$query = '
						select	*
						from	' . $this->getTable() . '
						where	id = ?
							AND deleted = 0';
			$query .= $this->getWhereSQL( $where );
			$query .= $this->getSortSQL( $order );

			$this->rs = $this->ExecuteSQL( $query, $ph );

			$this->saveCache( $this->rs, $id );
		}

		return $this;
	}

	/**
	 * @param string $id UUID
	 * @param int $limit Limit the number of records returned
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool|PayrollRemittanceAgencyListFactory
	 */
	function getByRemittanceSourceAccountId( $id, $limit = NULL, $where = NULL, $order = NULL ) {
		if ( $id == '' ) {
			return FALSE;
		}

		$ph = array(
				'remittance_source_account_id' => TTUUID::castUUID( $id ),
		);

		$query = '
						select	*
						from	' . $this->getTable() . '
						where	remittance_source_account_id = ?
							AND deleted = 0';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );

		$this->rs = $this->ExecuteSQL( $query, $ph, $limit );

		return $this;
	}


	/**
	 * @param string $company_id UUID
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool|PayrollRemittanceAgencyListFactory
	 * @throws DBError
	 */
	function getByCompanyId( $company_id, $where = NULL, $order = NULL ) {

		if ( $company_id == '' ) {
			return FALSE;
		}

		$lef = new LegalEntityFactory();

		$ph = array(
				'company_id' => TTUUID::castUUID( $company_id ),
		);

		$query = '
					select	a.*
					from	' . $this->getTable() . ' as a
						LEFT JOIN	' . $lef->getTable() . ' as b ON ( a.legal_entity_id = b.id )
					where	b.company_id = ?
						AND ( a.deleted = 0 AND b.deleted = 0 )';
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
	 * @return bool|PayrollRemittanceAgencyListFactory
	 */
	function getByIdAndCompanyId( $id, $company_id, $where = NULL, $order = NULL ) {
		if ( $id == '' ) {
			return FALSE;
		}

		if ( $company_id == '' ) {
			return FALSE;
		}

		$lef = new LegalEntityFactory();

		$ph = array(
				'id'         => TTUUID::castUUID( $id ),
				'company_id' => TTUUID::castUUID( $company_id ),
		);

		$query = '
					select	a.*
					from	' . $this->getTable() . ' as a
						LEFT JOIN	' . $lef->getTable() . ' as b ON ( a.legal_entity_id = b.id )
					where	a.id = ?
						AND b.company_id = ?
						AND ( a.deleted = 0 AND b.deleted = 0 )';
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
	 * @return bool|PayrollRemittanceAgencyListFactory
	 */
	function getByLegalEntityIdAndCompanyId( $id, $company_id, $where = NULL, $order = NULL ) {
		if ( $id == '' ) {
			return FALSE;
		}

		if ( $company_id == '' ) {
			return FALSE;
		}

		$lef = new LegalEntityFactory();

		$ph = array(
				'legal_entity_id' => TTUUID::castUUID( $id ),
				'company_id'      => TTUUID::castUUID( $company_id ),
		);

		$query = '
					select	a.*
					from	' . $this->getTable() . ' as a
						LEFT JOIN	' . $lef->getTable() . ' as b ON ( a.legal_entity_id = b.id )
					where	a.legal_entity_id = ?
						AND b.company_id = ?
						AND ( a.deleted = 0 AND b.deleted = 0 )';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );


		$this->rs = $this->ExecuteSQL( $query, $ph );

		return $this;
	}

	/**
	 * @param string $id UUID
	 * @param string $agency_id UUID
	 * @param string $company_id UUID
	 * @param array $where Additional SQL WHERE clause in format of array( $column => $filter, ... ). ie: array( 'id' => 1, ... )
	 * @param array $order Sort order passed to SQL in format of array( $column => 'asc', 'name' => 'desc', ... ). ie: array( 'id' => 'asc', 'name' => 'desc', ... )
	 * @return bool|PayrollRemittanceAgencyListFactory
	 */
	function getByLegalEntityIdAndAgencyIDAndCompanyId( $id, $agency_id, $company_id, $where = NULL, $order = NULL ) {
		if ( $id == '' ) {
			return FALSE;
		}

		if ( $agency_id == '' ) {
			return FALSE;
		}

		if ( $company_id == '' ) {
			return FALSE;
		}

		$lef = new LegalEntityFactory();

		$ph = array(
				'legal_entity_id' => TTUUID::castUUID( $id ),
				'agency_id'       => (string)$agency_id, //This is a string
				'company_id'      => TTUUID::castUUID( $company_id ),
		);

		$query = '
					select	a.*
					from	' . $this->getTable() . ' as a
						LEFT JOIN	' . $lef->getTable() . ' as b ON ( a.legal_entity_id = b.id )
					where	a.legal_entity_id = ?
						AND a.agency_id = ?
						AND b.company_id = ?
						AND ( a.deleted = 0 AND b.deleted = 0 )';
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
	 * @return bool|PayrollRemittanceAgencyListFactory
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

		$additional_order_fields = array('status_id', 'type_id', 'legal_entity_legal_name', 'agency');

		$sort_column_aliases = array(
				'status' => 'status_id',
				'type'   => 'type_id',
				'agency' => 'agency_id',
		);

		$order = $this->getColumnsFromAliases( $order, $sort_column_aliases );
		if ( $order == NULL ) {
			$order = array('status_id' => 'asc', 'legal_name' => 'asc', 'name' => 'asc');
			$strict = FALSE;
		} else {
			//Always try to order by status first so INACTIVE employees go to the bottom.
			if ( !isset( $order['status_id'] ) ) {
				$order = Misc::prependArray( array('status_id' => 'asc'), $order );
			}
			//Always sort by last name, first name after other columns
			if ( !isset( $order['name'] ) ) {
				$order['name'] = 'asc';
			}
			$strict = TRUE;
		}
		//Debug::Arr($order, 'Order Data:', __FILE__, __LINE__, __METHOD__, 10);
		//Debug::Arr($filter_data, 'Filter Data:', __FILE__, __LINE__, __METHOD__, 10);

		$uf = new UserFactory();
		$lef = new LegalEntityFactory();
		$rsaf = new RemittanceSourceAccountFactory();

		$ph = array(
				'company_id' => TTUUID::castUUID( $company_id ),
		);

//		(
//								CASE WHEN EXISTS ( select 1 from ' . $cdf->getTable() . ' as z where z.payroll_remittance_agency_id = a.id and z.deleted = 0 )
//										THEN 1 ELSE 0
//										END
//							) as in_use,
//
		//in_use doesn't make sense here, as several agencies are for reporting only (ie: New Hires, Social Security Administration) and will not have Tax/Deduction records assigned to them.
		$query = '
					select	
							_ADODB_COUNT
							a.*,
							uf.first_name,
							uf.last_name,
							lef.legal_name as legal_entity_legal_name,
							rsaf.name as remittance_source_account,
							y.first_name as created_by_first_name,
							y.middle_name as created_by_middle_name,
							y.last_name as created_by_last_name,
							z.first_name as updated_by_first_name,
							z.middle_name as updated_by_middle_name,
							z.last_name as updated_by_last_name
							_ADODB_COUNT
					from	' . $this->getTable() . ' as a
						LEFT JOIN ' . $lef->getTable() . ' as lef ON ( a.legal_entity_id = lef.id AND lef.deleted = 0 )
						LEFT JOIN ' . $rsaf->getTable() . ' as rsaf ON ( a.remittance_source_account_id = rsaf.id AND rsaf.deleted = 0 )
						LEFT JOIN ' . $uf->getTable() . ' as uf ON ( a.contact_user_id = uf.id AND uf.deleted = 0 )
						LEFT JOIN ' . $uf->getTable() . ' as y ON ( a.created_by = y.id AND y.deleted = 0 )
						LEFT JOIN ' . $uf->getTable() . ' as z ON ( a.updated_by = z.id AND z.deleted = 0 )
					where	lef.company_id = ?';

		$query .= ( isset( $filter_data['permission_children_ids'] ) ) ? $this->getWhereClauseSQL( 'a.created_by', $filter_data['permission_children_ids'], 'uuid_list', $ph ) : NULL;
		$query .= ( isset( $filter_data['id'] ) ) ? $this->getWhereClauseSQL( 'a.id', $filter_data['id'], 'uuid_list', $ph ) : NULL;
		$query .= ( isset( $filter_data['legal_entity_id'] ) ) ? $this->getWhereClauseSQL( 'a.legal_entity_id', $filter_data['legal_entity_id'], 'uuid_list', $ph ) : NULL;
		$query .= ( isset( $filter_data['agency_id'] ) ) ? $this->getWhereClauseSQL( 'a.agency_id', $filter_data['agency_id'], 'upper_text_list', $ph ) : NULL;
		$query .= ( isset( $filter_data['exclude_id'] ) ) ? $this->getWhereClauseSQL( 'a.id', $filter_data['exclude_id'], 'not_uuid_list', $ph ) : NULL;

		if ( isset( $filter_data['status'] ) AND !is_array( $filter_data['status'] ) AND trim( $filter_data['status'] ) != '' AND !isset( $filter_data['status_id'] ) ) {
			$filter_data['status_id'] = Option::getByFuzzyValue( $filter_data['status'], $this->getOptions( 'status' ) );
		}
		$query .= ( isset( $filter_data['status_id'] ) ) ? $this->getWhereClauseSQL( 'a.status_id', $filter_data['status_id'], 'numeric_list', $ph ) : NULL;
		$query .= ( isset( $filter_data['type_id'] ) ) ? $this->getWhereClauseSQL( 'a.type_id', $filter_data['type_id'], 'numeric_list', $ph ) : NULL;

		$query .= ( isset( $filter_data['name'] ) ) ? $this->getWhereClauseSQL( 'a.name', $filter_data['name'], 'text', $ph ) : NULL;

		$query .= ( isset( $filter_data['country'] ) ) ? $this->getWhereClauseSQL( 'a.country', $filter_data['country'], 'upper_text_list', $ph ) : NULL;
		$query .= ( isset( $filter_data['province'] ) ) ? $this->getWhereClauseSQL( 'a.province', $filter_data['province'], 'upper_text_list', $ph ) : NULL;


		$query .= ( isset( $filter_data['created_date'] ) ) ? $this->getWhereClauseSQL( 'a.created_date', $filter_data['created_date'], 'date_range', $ph ) : NULL;
		$query .= ( isset( $filter_data['updated_date'] ) ) ? $this->getWhereClauseSQL( 'a.updated_date', $filter_data['updated_date'], 'date_range', $ph ) : NULL;


		$query .= ( isset( $filter_data['created_by'] ) ) ? $this->getWhereClauseSQL( array('a.created_by', 'y.first_name', 'y.last_name'), $filter_data['created_by'], 'user_id_or_name', $ph ) : NULL;
		$query .= ( isset( $filter_data['updated_by'] ) ) ? $this->getWhereClauseSQL( array('a.updated_by', 'z.first_name', 'z.last_name'), $filter_data['updated_by'], 'user_id_or_name', $ph ) : NULL;

		$query .= ' AND a.deleted = 0 ';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order, $strict, $additional_order_fields );

		//Debug::Query($query, $ph, __FILE__, __LINE__, __METHOD__, 10);
		$this->rs = $this->ExecuteSQL( $query, $ph, $limit, $page );

		return $this;
	}

}

?>
