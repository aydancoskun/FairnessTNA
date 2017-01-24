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
 * @package Modules\PayStubAmendment
 */
class PayStubAmendmentListFactory extends PayStubAmendmentFactory implements IteratorAggregate
{
    public function getAll($limit = null, $page = null, $where = null, $order = null)
    {
        $query = '
					select	*
					from	' . $this->getTable() . '
					WHERE deleted = 0';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, null, $limit, $page);

        return $this;
    }

    public function getById($id, $where = null, $order = null)
    {
        if ($id == '') {
            return false;
        }

        $ph = array(
            'id' => (int)$id,
        );


        $query = '
					select	*
					from	' . $this->getTable() . '
					where	id = ?
						AND deleted = 0';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByIdAndStartDateAndEndDate($id, $start_date, $end_date, $where = null, $order = null)
    {
        if ($id == '') {
            return false;
        }

        $ph = array(
            'id' => (int)$id,
            'start_date' => $start_date,
            'end_date' => $end_date,
        );

        $query = '
					select	*
					from	' . $this->getTable() . '
					where	id = ?
						AND effective_date >= ?
						AND effective_date <= ?
						AND deleted = 0';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByCompanyId($company_id, $limit = null, $page = null, $where = null, $order = null)
    {
        if ($company_id == '') {
            return false;
        }

        $strict_order = true;
        if ($order == null) {
            //$order = array( 'a.effective_date' => 'desc', 'a.user_id' => 'asc' );
            $order = array('a.effective_date' => 'desc', 'a.status_id' => 'asc', 'b.last_name' => 'asc');
            $strict_order = false;
        }

        $ulf = new UserListFactory();

        $ph = array(
            'company_id' => (int)$company_id,
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a,
							' . $ulf->getTable() . ' as b
					where	a.user_id = b.id
						AND b.company_id = ?
						AND a.deleted = 0';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict_order);

        $this->ExecuteSQL($query, $ph, $limit, $page);

        return $this;
    }

    public function getByPayStubEntryNameID($psen_id, $limit = null, $page = null, $where = null, $order = null)
    {
        if ($psen_id == '') {
            return false;
        }

        $strict_order = true;
        if ($order == null) {
            $strict_order = false;
        }

        $ph = array(
            'psen_id' => (int)$psen_id,
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a
					where
						a.pay_stub_entry_name_id = ?
						AND a.deleted = 0';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict_order);

        $this->ExecuteSQL($query, $ph, $limit, $page);

        return $this;
    }

    public function getByIdAndCompanyId($id, $company_id, $order = null)
    {
        if ($id == '') {
            return false;
        }

        if ($company_id == '') {
            return false;
        }

        $uf = new UserFactory();

        $ph = array(
            'company_id' => (int)$company_id,
            'id' => (int)$id,
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a,
							' . $uf->getTable() . ' as b
					where	a.user_id = b.id
						AND	b.company_id = ?
						AND	a.id = ?
						AND a.deleted = 0';
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByIdAndUserId($id, $user_id, $where = null, $order = null)
    {
        if ($id == '') {
            return false;
        }

        if ($user_id == '') {
            return false;
        }

        $ph = array(
            'id' => (int)$id,
            'user_id' => (int)$user_id,
        );

        $query = '
					select	*
					from	' . $this->getTable() . '
					where	id = ?
						AND user_id = ?
						AND deleted = 0
						';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByUserId($id, $where = null, $order = null)
    {
        if ($id == '') {
            return false;
        }

        $ph = array(
            'id' => (int)$id,
        );


        $query = '
					select	*
					from	' . $this->getTable() . '
					where	user_id = ?
						AND deleted = 0';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByRecurringPayStubAmendmentId($recurring_ps_amendment_id, $limit = null, $page = null, $where = null, $order = null)
    {
        if ($recurring_ps_amendment_id == '') {
            return false;
        }

        $strict_order = true;
        if ($order == null) {
            $order = array('effective_date' => 'desc', 'user_id' => 'asc');
            $strict_order = false;
        }

        $ph = array(
            'recurring_ps_amendment_id' => (int)$recurring_ps_amendment_id,
        );

        $query = '
					select	*
					from	' . $this->getTable() . '
					where	recurring_ps_amendment_id = ?
						AND deleted = 0';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict_order);

        $this->ExecuteSQL($query, $ph, $limit, $page);

        return $this;
    }


    public function getByUserIdAndRecurringPayStubAmendmentIdAndStartDateAndEndDate($user_id, $recurring_ps_amendment_id, $start_date, $end_date, $limit = null, $page = null, $where = null, $order = null)
    {
        if ($user_id == '') {
            return false;
        }

        if ($recurring_ps_amendment_id == '') {
            return false;
        }

        if ($start_date == '') {
            return false;
        }

        if ($end_date == '') {
            return false;
        }

        $ph = array(
            'user_id' => (int)$user_id,
            'recurring_ps_amendment_id' => (int)$recurring_ps_amendment_id,
            'start_date' => $start_date,
            'end_date' => $end_date,
        );

        $query = '
					select	*
					from	' . $this->getTable() . '
					where	user_id = ?
						AND recurring_ps_amendment_id = ?
						AND effective_date >= ?
						AND effective_date <= ?
						AND deleted = 0';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph, $limit, $page);

        return $this;
    }

    public function getByUserIdAndCompanyId($user_id, $company_id, $limit = null, $page = null, $where = null, $order = null)
    {
        if ($user_id == '') {
            return false;
        }

        if ($company_id == '') {
            return false;
        }

        $strict_order = true;
        if ($order == null) {
            $order = array('a.effective_date' => 'desc', 'a.status_id' => 'asc', 'b.last_name' => 'asc');
            $strict_order = false;
        }

        $ulf = new UserListFactory();

        $ph = array(
            'company_id' => (int)$company_id,
            'user_id' => (int)$user_id,
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a,
							' . $ulf->getTable() . ' as b
					where	a.user_id = b.id
						AND b.company_id = ?
						AND a.user_id = ?
						AND a.deleted = 0';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict_order);

        $this->ExecuteSQL($query, $ph, $limit, $page);

        return $this;
    }

    public function getByCompanyIdAndUserIdAndStartDateAndEndDate($company_id, $user_id, $start_date = null, $end_date = null, $limit = null, $page = null, $where = null, $order = null)
    {
        if ($user_id == '') {
            return false;
        }

        if ($company_id == '') {
            return false;
        }

        /*
        if ( $start_date == '') {
            return FALSE;
        }

        if ( $end_date == '') {
            return FALSE;
        }
        */

        $strict_order = true;
        if ($order == null) {
            $order = array('a.effective_date' => 'desc', 'a.status_id' => 'asc', 'b.last_name' => 'asc');
            $strict_order = false;
        }

        $ulf = new UserListFactory();

        $ph = array(
            'company_id' => (int)$company_id,
            //'user_id' => (int)$user_id,
            //'start_date' => $start_date,
            //'end_date' => $end_date,
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a,
							' . $ulf->getTable() . ' as b
					where	a.user_id = b.id
						AND b.company_id = ?
					';

        if ($user_id != '' and isset($user_id[0]) and !in_array(-1, (array)$user_id)) {
            $query .= ' AND a.user_id in (' . $this->getListSQL($user_id, $ph, 'int') . ') ';
        }
        if ($start_date != '') {
            $ph[] = $start_date;
            $ph[] = $end_date;
            $query .= ' AND a.effective_date >= ? AND a.effective_date <= ? ';
        }

        $query .= '	AND a.deleted = 0';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict_order);

        $this->ExecuteSQL($query, $ph, $limit, $page);

        return $this;
    }

    public function getIsModifiedByUserIdAndStartDateAndEndDateAndDate($user_id, $start_date, $end_date, $date, $where = null, $order = null)
    {
        if ($user_id == '') {
            return false;
        }

        if ($start_date == '') {
            return false;
        }

        if ($end_date == '') {
            return false;
        }

        if ($date == '') {
            return false;
        }

        $ph = array(
            'user_id' => (int)$user_id,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'created_date' => $date,
            'updated_date' => $date
        );

        //INCLUDE Deleted rows in this query.
        $query = '
					select	*
					from	' . $this->getTable() . '
					where
							user_id = ?
						AND effective_date >= ?
						AND effective_date <= ?
						AND
							( created_date >= ? OR updated_date >= ? )
					LIMIT 1
					';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);
        if ($this->getRecordCount() > 0) {
            Debug::text('PS Amendment rows have been modified: ' . $this->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);

            return true;
        }
        Debug::text('PS Amendment rows have NOT been modified', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function getByCompanyIdAndAuthorizedAndStartDateAndEndDate($company_id, $authorized, $start_date, $end_date, $where = null, $order = null)
    {
        if ($company_id == '') {
            return false;
        }

        if ($authorized == '') {
            return false;
        }

        if ($start_date == '') {
            return false;
        }

        if ($end_date == '') {
            return false;
        }

        $uf = new UserFactory();

        $ph = array(
            'company_id' => (int)$company_id,
            'authorized' => $this->toBool($authorized),
            'start_date' => $start_date,
            'end_date' => $end_date,
        );

        //CalculatePayStub uses this to find PS amendments.
        //Because of percent amounts, make sure we order by effective date FIRST,
        //Then FIXED amounts, then percents.


        //Pay period end dates never equal the start start date, so >= and <= are proper.

        //06-Oct-06: Start including YTD_adjustment entries for the new pay stub calculation system.
        //						AND ytd_adjustment = 0
        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a,
							' . $uf->getTable() . ' as uf
					where
						a.user_id = uf.id
						AND uf.company_id = ?
						AND a.authorized = ?
						AND a.effective_date >= ?
						AND a.effective_date <= ?
						AND a.deleted = 0
					ORDER BY a.effective_date asc, a.type_id asc
				';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByUserIdAndAuthorizedAndStartDateAndEndDate($user_id, $authorized, $start_date, $end_date, $where = null, $order = null)
    {
        if ($user_id == '') {
            return false;
        }

        if ($authorized == '') {
            return false;
        }

        if ($start_date == '') {
            return false;
        }

        if ($end_date == '') {
            return false;
        }

        $psealf = new PayStubEntryAccountListFactory();

        $ph = array(
            'status_id' => (int)50, //ACTIVE
            'authorized' => $this->toBool($authorized),
            'start_date' => $start_date,
            'end_date' => $end_date,
        );

        //CalculatePayStub uses this to find PS amendments.
        //Because of percent amounts, make sure we order by effective date FIRST,
        //Then FIXED amounts, then percents.

        //Pay period end dates never equal the start start date, so >= and <= are proper.

        //06-Oct-06: Start including YTD_adjustment entries for the new pay stub calculation system.
        //						AND ytd_adjustment = 0

        //Make sure we ignore any pay stub amendments that happen to belong to deleted pay stub accounts.
        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a,
							' . $psealf->getTable() . ' as psea
					where
						a.pay_stub_entry_name_id = psea.id
						AND a.status_id = ?
						AND a.authorized = ?
						AND a.effective_date >= ?
						AND a.effective_date <= ?
						AND a.user_id in (' . $this->getListSQL($user_id, $ph, 'int') . ')
						AND ( a.deleted = 0 AND psea.deleted = 0 )
					ORDER BY a.effective_date asc, a.type_id asc, psea.ps_order asc, a.id asc
				';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByUserIdAndAuthorizedAndYTDAdjustmentAndStartDateAndEndDate($user_id, $authorized, $ytd_adjustment, $start_date, $end_date, $where = null, $order = null)
    {
        if ($user_id == '') {
            return false;
        }

        if ($authorized == '') {
            return false;
        }

        if ($ytd_adjustment == '') {
            return false;
        }

        if ($start_date == '') {
            return false;
        }

        if ($end_date == '') {
            return false;
        }

        $ph = array(
            'authorized' => $this->toBool($authorized),
            'start_date' => $start_date,
            'end_date' => $end_date,
            'ytd_adjustment' => $this->toBool($ytd_adjustment),
        );

        $query = '
					select	*
					from	' . $this->getTable() . '
					where
						authorized = ?
						AND effective_date >= ?
						AND effective_date <= ?
						AND ytd_adjustment = ?
						AND user_id in (' . $this->getListSQL($user_id, $ph, 'int') . ')
						AND deleted = 0';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getAmountSumByUserIdAndTypeIdAndAuthorizedAndStartDateAndEndDate($user_id, $type_id, $authorized, $start_date, $end_date, $where = null, $order = null)
    {
        $psalf = new PayStubAmendmentListFactory();
        $psalf->getByUserIdAndTypeIdAndAuthorizedAndStartDateAndEndDate($user_id, $type_id, $authorized, $start_date, $end_date, $where, $order);
        if ($psalf->getRecordCount() > 0) {
            $sum = 0;
            Debug::text('Record Count: ' . $psalf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);

            foreach ($psalf as $psa_obj) {
                $amount = $psa_obj->getCalculatedAmount();
                Debug::text('PS Amendment Amount: ' . $amount, __FILE__, __LINE__, __METHOD__, 10);
                $sum += $amount;
            }

            return $sum;
        }

        Debug::text('No PS Amendments found...', __FILE__, __LINE__, __METHOD__, 10);

        return false;
    }

    public function getByUserIdAndTypeIdAndAuthorizedAndStartDateAndEndDate($user_id, $type_id, $authorized, $start_date, $end_date, $where = null, $order = null)
    {
        if ($user_id == '') {
            return false;
        }

        if ($type_id == '') {
            return false;
        }

        if ($start_date == '') {
            return false;
        }

        if ($end_date == '') {
            return false;
        }

        $psealf = new PayStubEntryAccountListFactory();

        $ph = array(
            'user_id' => (int)$user_id,
            'type_id' => (int)$type_id,
            'authorized' => $this->toBool($authorized),
            'start_date' => $start_date,
            'end_date' => $end_date,
        );

        //select	sum(amount)
        //						AND a.tax_exempt = \''. $this->toBool($tax_exempt) .'\'
        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a,
							' . $psealf->getTable() . ' as b
					where	a.pay_stub_entry_name_id = b.id
						AND	a.user_id = ?
						AND b.type_id = ?
						AND a.authorized = ?
						AND a.effective_date >= ?
						AND a.effective_date <= ?
						AND a.ytd_adjustment = 0
						AND a.deleted = 0';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getAmountSumByUserIdAndNameIdAndAuthorizedAndStartDateAndEndDate($user_id, $name_id, $authorized, $start_date, $end_date, $where = null, $order = null)
    {
        if ($user_id == '') {
            return false;
        }

        if ($name_id == '') {
            return false;
        }

        if ($start_date == '') {
            return false;
        }

        if ($end_date == '') {
            return false;
        }

        $psealf = new PayStubEntryAccountListFactory();

        $ph = array(
            'user_id' => (int)$user_id,
            'authorized' => $this->toBool($authorized),
            'start_date' => $start_date,
            'end_date' => $end_date,
        );

        $query = '
					select	sum(amount)
					from	' . $this->getTable() . ' as a,
							' . $psealf->getTable() . ' as b
					where	a.pay_stub_entry_name_id = b.id
						AND	a.user_id = ?
						AND a.authorized = ?
						AND a.effective_date >= ?
						AND a.effective_date <= ?
						AND b.id in (' . $this->getListSQL($name_id, $ph, 'int') . ')
						AND a.ytd_adjustment = 0
						AND a.deleted = 0
						';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $sum = $this->db->GetOne($query, $ph);

        if ($sum !== false or $sum !== null) {
            Debug::text('Amount Sum: ' . $sum, __FILE__, __LINE__, __METHOD__, 10);
            return $sum;
        }

        Debug::text('Amount Sum is NULL', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function getSearchByCompanyIdAndArrayCriteria($company_id, $filter_data, $limit = null, $page = null, $where = null, $order = null)
    {
        if ($company_id == '') {
            return false;
        }

        if (!is_array($order)) {
            //Use Filter Data ordering if its set.
            if (isset($filter_data['sort_column']) and $filter_data['sort_order']) {
                $order = array(Misc::trimSortPrefix($filter_data['sort_column']) => $filter_data['sort_order']);
            }
        }

        $additional_order_fields = array('b.last_name', 'b.first_name');
        if ($order == null) {
            $order = array('a.effective_date' => 'desc', 'a.status_id' => 'asc', 'b.last_name' => 'asc');
            $strict = false;
        } else {
            //Always try to order by status first so UNPAID employees go to the bottom.
            if (isset($order['last_name'])) {
                $order['b.last_name'] = $order['last_name'];
                unset($order['last_name']);
            }
            if (isset($order['first_name'])) {
                $order['b.first_name'] = $order['first_name'];
                unset($order['first_name']);
            }
            if (isset($order['type'])) {
                $order['type_id'] = $order['type'];
                unset($order['type']);
            }
            if (isset($order['status'])) {
                $order['status_id'] = $order['status'];
                unset($order['status']);
            }

            if (isset($order['effective_date'])) {
                $order['b.last_name'] = 'asc';
            } else {
                $order['a.effective_date'] = 'desc';
            }

            $strict = true;
        }
        //Debug::Arr($order, 'bOrder Data:', __FILE__, __LINE__, __METHOD__, 10);
        //Debug::Arr($filter_data, 'Filter Data:', __FILE__, __LINE__, __METHOD__, 10);

        $uf = new UserFactory();
        $psealf = new PayStubEntryAccountListFactory();


        $ph = array(
            'company_id' => (int)$company_id,
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a
						LEFT JOIN ' . $uf->getTable() . ' as b ON a.user_id = b.id
						LEFT JOIN ' . $psealf->getTable() . ' as c ON a.pay_stub_entry_name_id  = c.id
					where	b.company_id = ?
					';

        if (isset($filter_data['permission_children_ids']) and isset($filter_data['permission_children_ids'][0]) and !in_array(-1, (array)$filter_data['permission_children_ids'])) {
            $query .= ' AND b.id in (' . $this->getListSQL($filter_data['permission_children_ids'], $ph) . ') ';
        }
        if (isset($filter_data['id']) and isset($filter_data['id'][0]) and !in_array(-1, (array)$filter_data['id'])) {
            $query .= ' AND a.id in (' . $this->getListSQL($filter_data['id'], $ph) . ') ';
        }

        if (isset($filter_data['user_id']) and isset($filter_data['user_id'][0]) and !in_array(-1, (array)$filter_data['user_id'])) {
            $query .= ' AND b.id in (' . $this->getListSQL($filter_data['user_id'], $ph) . ') ';
        }
        if (isset($filter_data['status_id']) and isset($filter_data['status_id'][0]) and !in_array(-1, (array)$filter_data['status_id'])) {
            $query .= ' AND a.status_id in (' . $this->getListSQL($filter_data['status_id'], $ph) . ') ';
        }
        if (isset($filter_data['group_id']) and isset($filter_data['group_id'][0]) and !in_array(-1, (array)$filter_data['group_id'])) {
            if (isset($filter_data['include_subgroups']) and (bool)$filter_data['include_subgroups'] == true) {
                $uglf = new UserGroupListFactory();
                $filter_data['group_id'] = $uglf->getByCompanyIdAndGroupIdAndSubGroupsArray($company_id, $filter_data['group_id'], true);
            }
            $query .= ' AND b.group_id in (' . $this->getListSQL($filter_data['group_id'], $ph) . ') ';
        }
        if (isset($filter_data['default_branch_id']) and isset($filter_data['default_branch_id'][0]) and !in_array(-1, (array)$filter_data['default_branch_id'])) {
            $query .= ' AND b.default_branch_id in (' . $this->getListSQL($filter_data['default_branch_id'], $ph) . ') ';
        }
        if (isset($filter_data['default_department_id']) and isset($filter_data['default_department_id'][0]) and !in_array(-1, (array)$filter_data['default_department_id'])) {
            $query .= ' AND b.default_department_id in (' . $this->getListSQL($filter_data['default_department_id'], $ph) . ') ';
        }
        if (isset($filter_data['title_id']) and isset($filter_data['title_id'][0]) and !in_array(-1, (array)$filter_data['title_id'])) {
            $query .= ' AND b.title_id in (' . $this->getListSQL($filter_data['title_id'], $ph) . ') ';
        }
        if (isset($filter_data['recurring_ps_amendment_id']) and isset($filter_data['recurring_ps_amendment_id'][0]) and !in_array(-1, (array)$filter_data['recurring_ps_amendment_id'])) {
            $query .= ' AND a.recurring_ps_amendment_id in (' . $this->getListSQL($filter_data['recurring_ps_amendment_id'], $ph) . ') ';
        }

        if (isset($filter_data['start_date']) and !is_array($filter_data['start_date']) and trim($filter_data['start_date']) != '') {
            $ph[] = strtolower(trim($filter_data['start_date']));
            $query .= ' AND a.effective_date >= ?';
        }
        if (isset($filter_data['end_date']) and !is_array($filter_data['end_date']) and trim($filter_data['end_date']) != '') {
            $ph[] = strtolower(trim($filter_data['end_date']));
            $query .= ' AND a.effective_date <= ?';
        }
        if (isset($filter_data['effective_date']) and !is_array($filter_data['effective_date']) and trim($filter_data['effective_date']) != '') {
            $ph[] = strtolower(trim($filter_data['effective_date']));
            $query .= ' AND a.effective_date = ?';
        }

        $query .= '
						AND ( a.deleted = 0 AND b.deleted = 0 AND c.deleted = 0 )
					';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict, $additional_order_fields);

        $this->ExecuteSQL($query, $ph, $limit, $page);

        return $this;
    }

    public function getAPISearchByCompanyIdAndArrayCriteria($company_id, $filter_data, $limit = null, $page = null, $where = null, $order = null)
    {
        if ($company_id == '') {
            return false;
        }

        if (!is_array($order)) {
            //Use Filter Data ordering if its set.
            if (isset($filter_data['sort_column']) and $filter_data['sort_order']) {
                $order = array(Misc::trimSortPrefix($filter_data['sort_column']) => $filter_data['sort_order']);
            }
        }

        $additional_order_fields = array('pay_stub_entry_name', 'user_status_id', 'last_name', 'first_name', 'default_branch', 'default_department', 'user_group', 'title');

        $sort_column_aliases = array(
            'user_status' => 'user_status_id',
            'status' => 'status_id',
            'type' => 'type_id',
        );
        $order = $this->getColumnsFromAliases($order, $sort_column_aliases);
        if ($order == null) {
            $order = array('effective_date' => 'desc', 'last_name' => 'asc');
            $strict = false;
        } else {
            //Always sort by effective_date, last name after other columns
            if (!isset($order['effective_date'])) {
                $order['effective_date'] = 'desc';
            }

            if (!isset($order['last_name'])) {
                $order['last_name'] = 'asc';
            }
            $strict = true;
        }
        //Debug::Arr($order, 'Order Data:', __FILE__, __LINE__, __METHOD__, 10);
        //Debug::Arr($filter_data, 'Filter Data:', __FILE__, __LINE__, __METHOD__, 10);

        $uf = new UserFactory();
        $bf = new BranchFactory();
        $df = new DepartmentFactory();
        $ugf = new UserGroupFactory();
        $utf = new UserTitleFactory();
        $pseaf = new PayStubEntryAccountFactory();
        $ppf = new PayPeriodFactory();
        $ppsf = new PayPeriodScheduleFactory();
        $ppsuf = new PayPeriodScheduleUserFactory();

        $ph = array(
            'company_id' => (int)$company_id,
        );

        $query = '
					select	a.*,
							b.first_name as first_name,
							b.last_name as last_name,
							b.status_id as user_status_id,

							b.default_branch_id as default_branch_id,
							bf.name as default_branch,
							b.default_department_id as default_department_id,
							df.name as default_department,
							b.group_id as group_id,
							ugf.name as user_group,
							b.title_id as title_id,
							utf.name as title,

							pseaf.name as pay_stub_entry_name,

							y.first_name as created_by_first_name,
							y.middle_name as created_by_middle_name,
							y.last_name as created_by_last_name,
							z.first_name as updated_by_first_name,
							z.middle_name as updated_by_middle_name,
							z.last_name as updated_by_last_name
					from	' . $this->getTable() . ' as a
						LEFT JOIN ' . $uf->getTable() . ' as b ON ( a.user_id = b.id AND b.deleted = 0 )
						LEFT JOIN ' . $bf->getTable() . ' as bf ON ( b.default_branch_id = bf.id AND bf.deleted = 0)
						LEFT JOIN ' . $df->getTable() . ' as df ON ( b.default_department_id = df.id AND df.deleted = 0)
						LEFT JOIN ' . $ugf->getTable() . ' as ugf ON ( b.group_id = ugf.id AND ugf.deleted = 0 )
						LEFT JOIN ' . $utf->getTable() . ' as utf ON ( b.title_id = utf.id AND utf.deleted = 0 )

						LEFT JOIN ' . $pseaf->getTable() . ' as pseaf ON ( a.pay_stub_entry_name_id = pseaf.id AND pseaf.deleted = 0 )
						LEFT JOIN ' . $ppsuf->getTable() . ' as ppsuf ON ( a.user_id = ppsuf.user_id )
						LEFT JOIN ' . $ppsf->getTable() . ' as ppsf ON ( ppsuf.pay_period_schedule_id = ppsf.id AND ppsf.deleted = 0 )
						LEFT JOIN ' . $ppf->getTable() . ' as ppf ON ( ppsuf.pay_period_schedule_id = ppf.pay_period_schedule_id AND ' . $this->getSQLToTimeStampFunction() . '(a.effective_date) >= ppf.start_date AND ' . $this->getSQLToTimeStampFunction() . '(a.effective_date) <= ppf.end_date AND ppf.deleted = 0 )

						LEFT JOIN ' . $uf->getTable() . ' as y ON ( a.created_by = y.id AND y.deleted = 0 )
						LEFT JOIN ' . $uf->getTable() . ' as z ON ( a.updated_by = z.id AND z.deleted = 0 )
					where	b.company_id = ?
					';

        $query .= (isset($filter_data['permission_children_ids'])) ? $this->getWhereClauseSQL('b.id', $filter_data['permission_children_ids'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['id'])) ? $this->getWhereClauseSQL('a.id', $filter_data['id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['exclude_id'])) ? $this->getWhereClauseSQL('a.id', $filter_data['exclude_id'], 'not_numeric_list', $ph) : null;

        $query .= (isset($filter_data['pay_stub_entry_name_id'])) ? $this->getWhereClauseSQL('a.pay_stub_entry_name_id', $filter_data['pay_stub_entry_name_id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['user_id'])) ? $this->getWhereClauseSQL('b.id', $filter_data['user_id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['pay_period_id'])) ? $this->getWhereClauseSQL('ppf.id', $filter_data['pay_period_id'], 'numeric_list', $ph) : null;

        $query .= (isset($filter_data['group_id'])) ? $this->getWhereClauseSQL('b.group_id', $filter_data['group_id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['default_branch_id'])) ? $this->getWhereClauseSQL('b.default_branch_id', $filter_data['default_branch_id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['default_department_id'])) ? $this->getWhereClauseSQL('b.default_department_id', $filter_data['default_department_id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['title_id'])) ? $this->getWhereClauseSQL('b.title_id', $filter_data['title_id'], 'numeric_list', $ph) : null;

        $query .= (isset($filter_data['status_id'])) ? $this->getWhereClauseSQL('a.status_id', $filter_data['status_id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['recurring_ps_amendment_id'])) ? $this->getWhereClauseSQL('a.recurring_ps_amendment_id', $filter_data['recurring_ps_amendment_id'], 'numeric_list', $ph) : null;

        $query .= (isset($filter_data['start_date'])) ? $this->getWhereClauseSQL('a.effective_date', $filter_data['start_date'], 'start_date', $ph) : null;
        $query .= (isset($filter_data['end_date'])) ? $this->getWhereClauseSQL('a.effective_date', $filter_data['end_date'], 'end_date', $ph) : null;
        $query .= (isset($filter_data['effective_date'])) ? $this->getWhereClauseSQL('a.effective_date', $filter_data['effective_date'], 'end_date', $ph) : null;

        $query .= (isset($filter_data['created_by'])) ? $this->getWhereClauseSQL(array('a.created_by', 'y.first_name', 'y.last_name'), $filter_data['created_by'], 'user_id_or_name', $ph) : null;
        $query .= (isset($filter_data['updated_by'])) ? $this->getWhereClauseSQL(array('a.updated_by', 'z.first_name', 'z.last_name'), $filter_data['updated_by'], 'user_id_or_name', $ph) : null;

        //Need to account for employees being assigned to deleted pay period schedules.
        $query .= ' AND ( ppsuf.id IS NULL OR ppsf.id IS NOT NULL ) AND a.deleted = 0 ';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict, $additional_order_fields);

        //Debug::Arr($query, 'Query: ', __FILE__, __LINE__, __METHOD__, 10);
        $this->ExecuteSQL($query, $ph, $limit, $page);

        return $this;
    }
}
