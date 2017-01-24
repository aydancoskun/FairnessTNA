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
 * @package Modules\PayStub
 */
class PayStubListFactory extends PayStubFactory implements IteratorAggregate
{
    public static function getCurrentPayRun($company_id, $pay_period_ids)
    {
        if (!is_array($pay_period_ids) and is_numeric($pay_period_ids)) {
            $pay_period_ids = (array)$pay_period_ids;
        }

        $retval = 1;
        if (is_array($pay_period_ids) and count($pay_period_ids) > 0) {
            $pp_retval = $retval;
            foreach ($pay_period_ids as $pay_period_id) {
                $pslf = TTnew('PayStubListFactory');
                $pslf->getPayRunStatusByCompanyIdAndPayPeriodId($company_id, $pay_period_id);
                if ($pslf->getRecordCount() > 0) {
                    //Current Pay Run is the highest run with open pay stubs.
                    //If no open pay stubs exist, move on to the next run.
                    foreach ($pslf as $ps_obj) {
                        Debug::Text('Pay Period ID: ' . $pay_period_id . ' Run ID: ' . $ps_obj->getColumn('run_id') . ' Status ID: ' . $ps_obj->getColumn('status_id') . ' Total Pay Stubs: ' . $ps_obj->getColumn('total_pay_stubs'), __FILE__, __LINE__, __METHOD__, 10);
                        if ($ps_obj->getColumn('status_id') == 25) {
                            $pp_retval = (int)$ps_obj->getColumn('run_id');
                            break;
                        } elseif ($ps_obj->getColumn('status_id') == 40) {
                            $pp_retval = ((int)$ps_obj->getColumn('run_id') + 1);
                            break;
                        }
                    }
                }

                if (isset($pp_retval) and $pp_retval > $retval) {
                    $retval = $pp_retval;
                } else {
                    Debug::Text('  Skipping Run ID: ' . $pp_retval, __FILE__, __LINE__, __METHOD__, 10);
                }
            }
        }

        Debug::Text('  Current Run ID: ' . $retval, __FILE__, __LINE__, __METHOD__, 10);
        return $retval;
    }

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

        $this->rs = $this->getCache($id);
        if ($this->rs === false) {
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

            $this->saveCache($this->rs, $id);
        }

        return $this;
    }

    public function getByIdAndCompanyIdAndIgnoreDeleted($id, $company_id, $limit = null, $page = null, $where = null, $order = null)
    {
        if ($company_id == '') {
            return false;
        }

        if ($id == '') {
            return false;
        }

        $strict_order = true;
        if ($order == null) {
            $order = array('a.transaction_date' => 'desc', 'a.run_id' => 'desc', 'b.last_name' => 'asc');
            $strict_order = false;
        }

        $ulf = new UserListFactory();
        $pplf = new PayPeriodListFactory();

        $ph = array(
            'company_id' => (int)$company_id
        );

        //Include deleted pay stubs, for re-calculating YTD amounts?
        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a,
							' . $ulf->getTable() . ' as b,
							' . $pplf->getTable() . ' as c
					where	a.user_id = b.id
						AND a.pay_period_id = c.id
						AND b.company_id = ?
						AND a.id in (' . $this->getListSQL($id, $ph, 'int') . ')
						';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict_order);

        $this->ExecuteSQL($query, $ph, $limit, $page);

        return $this;
    }

    public function getByIdAndCompanyId($id, $company_id, $where = null, $order = null)
    {
        if ($id == '') {
            return false;
        }

        if ($company_id == '') {
            return false;
        }

        $ulf = new UserListFactory();

        $ph = array(
            'id' => (int)$id,
            'company_id' => (int)$company_id,
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a,
							' . $ulf->getTable() . ' as b
					where	a.user_id = b.id
						AND a.id = ?
						AND b.company_id = ?
						AND ( a.deleted = 0 AND b.deleted = 0 )';

        $query .= $this->getWhereSQL($where);
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
            $order = array('a.transaction_date' => 'desc', 'a.run_id' => 'desc');
            $strict_order = false;
        }

        $ulf = new UserListFactory();
        $pplf = new PayPeriodListFactory();

        $ph = array(
            'company_id' => (int)$company_id,
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a,
							' . $ulf->getTable() . ' as b,
							' . $pplf->getTable() . ' as c
					where	a.user_id = b.id
						AND a.pay_period_id = c.id
						AND b.company_id = ?
						AND a.user_id in (' . $this->getListSQL($user_id, $ph, 'int') . ')
						AND a.deleted = 0';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict_order);

        $this->ExecuteSQL($query, $ph, $limit, $page);

        return $this;
    }

    public function getByUserIdAndCompanyIdAndPayPeriodId($user_id, $company_id, $pay_period_id, $limit = null, $page = null, $where = null, $order = null)
    {
        if ($user_id == '') {
            return false;
        }

        if ($company_id == '') {
            return false;
        }

        if ($pay_period_id == '') {
            return false;
        }

        $strict_order = true;
        if ($order == null) {
            $order = array('a.transaction_date' => 'desc', 'a.run_id' => 'desc', 'a.user_id' => 'asc');
            $strict_order = false;
        }

        $ulf = new UserListFactory();
        $pplf = new PayPeriodListFactory();

        $ph = array(
            'company_id' => (int)$company_id,
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a,
							' . $ulf->getTable() . ' as b,
							' . $pplf->getTable() . ' as c
					where	a.user_id = b.id
						AND a.pay_period_id = c.id
						AND b.company_id = ?
						AND a.user_id in (' . $this->getListSQL($user_id, $ph, 'int') . ')
						';

        if ($pay_period_id != '' and isset($pay_period_id[0]) and !in_array(-1, (array)$pay_period_id)) {
            $query .= ' AND a.pay_period_id in (' . $this->getListSQL($pay_period_id, $ph, 'int') . ') ';
        }

        $query .= '
						AND a.deleted = 0';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict_order);

        $this->ExecuteSQL($query, $ph, $limit, $page);

        return $this;
    }

    public function getByUserIdAndPayStubAmendmentId($user_id, $pay_stub_amendment_id, $limit = null, $page = null, $where = null, $order = null)
    {
        if ($user_id == '') {
            return false;
        }

        if ($pay_stub_amendment_id == '') {
            return false;
        }

        $ulf = new UserListFactory();
        $pself = new PayStubEntryListFactory();

        $ph = array(
            'user_id' => (int)$user_id,
            'psa_id' => (int)$pay_stub_amendment_id,
        );

        $query = '
					select	distinct a.*
					from	' . $this->getTable() . ' as a
						LEFT JOIN ' . $ulf->getTable() . ' as b ON ( a.user_id = b.id )
						LEFT JOIN ' . $pself->getTable() . ' as c ON ( a.id = c.pay_stub_id )
					where a.user_id = ?
						AND c.pay_stub_amendment_id = ?
						';

        $query .= '
						AND ( a.deleted = 0 AND b.deleted = 0 AND c.deleted = 0)';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph, $limit, $page);

        return $this;
    }

    public function getByUserIdAndUserExpenseId($user_id, $user_expense_id, $limit = null, $page = null, $where = null, $order = null)
    {
        if ($user_id == '') {
            return false;
        }

        if ($user_expense_id == '') {
            return false;
        }

        $ulf = new UserListFactory();
        $pself = new PayStubEntryListFactory();

        $ph = array(
            'user_id' => (int)$user_id,
            'user_expense_id' => (int)$user_expense_id,
        );

        $query = '
					select	distinct a.*
					from	' . $this->getTable() . ' as a
						LEFT JOIN ' . $ulf->getTable() . ' as b ON ( a.user_id = b.id )
						LEFT JOIN ' . $pself->getTable() . ' as c ON ( a.id = c.pay_stub_id )
					where a.user_id = ?
						AND c.user_expense_id = ?
						';

        $query .= '
						AND ( a.deleted = 0 AND b.deleted = 0 AND c.deleted = 0)';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph, $limit, $page);

        return $this;
    }

    public function getLastPayStubByUserIdAndStartDateAndRun($user_id, $start_date, $run_id, $where = null, $order = null)
    {
        if ($user_id == '') {
            return false;
        }

        if ($run_id == '') {
            return false;
        }

        $strict_order = true;
        if ($order == null) {
            $order = array('a.start_date' => 'desc', 'a.run_id' => 'desc');
            $strict_order = false;
        }

        $ulf = new UserListFactory();
        $pplf = new PayPeriodListFactory();

        $ph = array(
            'start_date' => $this->db->BindTimeStamp($start_date),
            'run_id' => (int)$run_id,
            'start_date2' => $this->db->BindTimeStamp($start_date),
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a,
							' . $ulf->getTable() . ' as b,
							' . $pplf->getTable() . ' as c
					where	a.user_id = b.id
						AND a.pay_period_id = c.id
						AND ( ( a.start_date = ? AND a.run_id < ? ) OR a.start_date < ? )
						AND a.user_id in (' . $this->getListSQL($user_id, $ph, 'int') . ')
						AND ( a.deleted = 0 AND c.deleted = 0)
					';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict_order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getNextPayStubByUserIdAndTransactionDateAndRun($user_id, $transaction_date, $run_id, $where = null, $order = null)
    {
        if ($user_id == '') {
            return false;
        }

        if ($run_id == '') {
            return false;
        }

        $strict_order = true;
        if ($order == null) {
            $order = array('a.transaction_date' => 'asc', 'a.run_id' => 'asc'); //Sort in ASC order as its getting the NEXT pay stub. This is required for PayStubFactory->reCalculateYTD()
            $strict_order = false;
        }

        $ulf = new UserListFactory();
        $pplf = new PayPeriodListFactory();

        $ph = array(
            'transaction_date' => $this->db->BindTimeStamp($transaction_date),
            'run_id' => (int)$run_id,
            'transaction_date2' => $this->db->BindTimeStamp(TTDate::getEndDayEpoch($transaction_date)),
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a,
							' . $ulf->getTable() . ' as b,
							' . $pplf->getTable() . ' as c
					where	a.user_id = b.id
						AND a.pay_period_id = c.id
						AND ( ( a.transaction_date = ? AND a.run_id > ? ) OR a.transaction_date > ? )
						AND a.user_id in (' . $this->getListSQL($user_id, $ph, 'int') . ')
						AND ( a.deleted = 0 AND c.deleted = 0)
					';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict_order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByUserIdAndStartDateAndEndDate($user_id, $start_date, $end_date, $where = null, $order = null)
    {
        if ($user_id == '') {
            return false;
        }

        $strict_order = true;
        if ($order == null) {
            $order = array('a.transaction_date' => 'asc', 'a.run_id' => 'asc');
            $strict_order = false;
        }

        $ulf = new UserListFactory();
        $pplf = new PayPeriodListFactory();

        $ph = array(
            'start_date' => $this->db->BindTimeStamp($start_date),
            'end_date' => $this->db->BindTimeStamp($end_date),
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a,
							' . $ulf->getTable() . ' as b,
							' . $pplf->getTable() . ' as c
					where	a.user_id = b.id
						AND a.pay_period_id = c.id
						AND a.transaction_date >= ?
						AND a.transaction_date <= ?
						AND a.user_id in (' . $this->getListSQL($user_id, $ph, 'int') . ')
						AND ( a.deleted = 0 AND c.deleted = 0)
					';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict_order);

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
            $order = array('a.transaction_date' => 'desc', 'a.run_id' => 'desc', 'b.last_name' => 'asc');
            $strict_order = false;
        }

        $ulf = new UserListFactory();
        $pplf = new PayPeriodListFactory();

        $ph = array(
            'company_id' => (int)$company_id
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a,
							' . $ulf->getTable() . ' as b,
							' . $pplf->getTable() . ' as c
					where	a.user_id = b.id
						AND a.pay_period_id = c.id
						AND b.company_id = ?
						AND a.deleted = 0';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict_order);

        $this->ExecuteSQL($query, $ph, $limit, $page);

        return $this;
    }

    public function getByCompanyIdAndId($company_id, $id, $limit = null, $page = null, $where = null, $order = null)
    {
        if ($company_id == '') {
            return false;
        }

        if ($id == '') {
            return false;
        }

        $strict_order = true;
        if ($order == null) {
            $order = array('a.transaction_date' => 'desc', 'a.run_id' => 'desc', 'b.last_name' => 'asc');
            $strict_order = false;
        }

        $ulf = new UserListFactory();
        $pplf = new PayPeriodListFactory();

        $ph = array(
            'company_id' => (int)$company_id
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a,
							' . $ulf->getTable() . ' as b,
							' . $pplf->getTable() . ' as c
					where	a.user_id = b.id
						AND a.pay_period_id = c.id
						AND b.company_id = ?
						AND a.id in (' . $this->getListSQL($id, $ph, 'int') . ')
						AND a.deleted = 0
						';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict_order);

        $this->ExecuteSQL($query, $ph, $limit, $page);

        return $this;
    }

    public function getByUserIdAndId($user_id, $id, $limit = null, $page = null, $where = null, $order = null)
    {
        if ($user_id == '') {
            return false;
        }

        if ($id == '') {
            return false;
        }

        $strict_order = true;
        if ($order == null) {
            $order = array('a.transaction_date' => 'desc', 'a.run_id' => 'desc');
            $strict_order = false;
        }

        $ulf = new UserListFactory();
        $pplf = new PayPeriodListFactory();

        $ph = array(
            'user_id' => (int)$user_id,
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a,
							' . $ulf->getTable() . ' as b,
							' . $pplf->getTable() . ' as c
					where	a.user_id = b.id
						AND a.pay_period_id = c.id
						AND b.id = ?
						AND a.id in (' . $this->getListSQL($id, $ph, 'int') . ')
						AND a.deleted = 0';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict_order);

        $this->ExecuteSQL($query, $ph, $limit, $page);

        return $this;
    }

    public function getByPayPeriodId($id, $where = null, $order = null)
    {
        if ($id == '') {
            return false;
        }

        $ulf = new UserListFactory();

        $ph = array(
            'id' => (int)$id
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a
					LEFT JOIN ' . $ulf->getTable() . ' as uf ON ( a.user_id = uf.id )
					where	a.pay_period_id = ?
						AND ( a.deleted = 0 AND uf.deleted = 0 )';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, false);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByCurrencyId($id, $where = null, $order = null)
    {
        if ($id == '') {
            return false;
        }

        $ph = array(
            'id' => (int)$id
        );

        $query = '
					select	*
					from	' . $this->getTable() . '
					where	currency_id = ?
						AND deleted = 0';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, false);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByCompanyIdAndPayPeriodId($company_id, $pay_period_id, $limit = null, $page = null, $where = null, $order = null)
    {
        if ($company_id == '') {
            return false;
        }

        if ($pay_period_id == '') {
            return false;
        }

        $strict_order = true;
        if ($order == null or !is_array($order)) {
            $order = array('a.transaction_date' => 'desc', 'a.run_id' => 'desc', 'b.last_name' => 'asc');
            $strict_order = false;
        }

        $ulf = new UserListFactory();
        $pplf = new PayPeriodListFactory();

        $ph = array(
            'company_id' => (int)$company_id
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a,
							' . $ulf->getTable() . ' as b,
							' . $pplf->getTable() . ' as c
					where	a.user_id = b.id
						AND a.pay_period_id = c.id
						AND b.company_id = ?
						AND a.pay_period_id in (' . $this->getListSQL($pay_period_id, $ph, 'int') . ')
						AND a.deleted = 0';

        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict_order);

        $this->ExecuteSQL($query, $ph, $limit, $page);

        return $this;
    }

    public function getByCompanyIdAndPayPeriodIdAndRun($company_id, $pay_period_id, $run, $limit = null, $page = null, $where = null, $order = null)
    {
        if ($company_id == '') {
            return false;
        }

        if ($pay_period_id == '') {
            return false;
        }

        if ($run == '') {
            return false;
        }

        $strict_order = true;
        if ($order == null or !is_array($order)) {
            $order = array('a.transaction_date' => 'desc', 'a.run_id' => 'desc', 'b.last_name' => 'asc');
            $strict_order = false;
        }

        $ulf = new UserListFactory();
        $pplf = new PayPeriodListFactory();

        $ph = array(
            'company_id' => (int)$company_id,
            'pay_period_id' => (int)$pay_period_id,
            'run' => (int)$run,
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a,
							' . $ulf->getTable() . ' as b,
							' . $pplf->getTable() . ' as c
					where	a.user_id = b.id
						AND a.pay_period_id = c.id
						AND b.company_id = ?
						AND a.pay_period_id = ?
						AND a.run_id = ?
						AND a.deleted = 0';

        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict_order);

        $this->ExecuteSQL($query, $ph, $limit, $page);

        return $this;
    }

    public function getByCompanyIdAndPayPeriodIdAndStatusIdAndTransactionDateBeforeDate($company_id, $pay_period_id, $status_id, $date, $limit = null, $page = null, $where = null, $order = null)
    {
        if ($company_id == '') {
            return false;
        }

        if ($pay_period_id == '') {
            return false;
        }

        if ($status_id == '') {
            return false;
        }

        if ($date == '') {
            return false;
        }

        $strict_order = true;
        if ($order == null or !is_array($order)) {
            $order = array('a.transaction_date' => 'desc', 'a.run_id' => 'desc', 'b.last_name' => 'asc');
            $strict_order = false;
        }

        $ulf = new UserListFactory();
        $pplf = new PayPeriodListFactory();

        $ph = array(
            'company_id' => (int)$company_id,
            'pay_period_id' => (int)$pay_period_id,
            'transaction_date' => $this->db->BindTimeStamp($date),
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a,
							' . $ulf->getTable() . ' as b,
							' . $pplf->getTable() . ' as c
					where	a.user_id = b.id
						AND a.pay_period_id = c.id
						AND b.company_id = ?
						AND a.pay_period_id = ?
						AND a.transaction_date < ?
						AND a.status_id in (' . $this->getListSQL($status_id, $ph, 'int') . ')
						AND ( a.deleted = 0 AND b.deleted = 0 AND c.deleted = 0 )';

        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict_order);

        $this->ExecuteSQL($query, $ph, $limit, $page);

        //Debug::Arr($ph, 'Query: '. $query, __FILE__, __LINE__, __METHOD__, 10);

        return $this;
    }

    public function getByCompanyIdAndPayPeriodIdAndStatusIdAndNotRun($company_id, $pay_period_id, $status_id, $run_id, $limit = null, $page = null, $where = null, $order = null)
    {
        if ($company_id == '') {
            return false;
        }

        if ($pay_period_id == '') {
            return false;
        }

        if ($status_id == '') {
            return false;
        }

        if ($run_id == '') {
            return false;
        }

        $strict_order = true;
        if ($order == null or !is_array($order)) {
            $order = array('a.transaction_date' => 'desc', 'a.run_id' => 'desc', 'b.last_name' => 'asc');
            $strict_order = false;
        }

        $ulf = new UserListFactory();
        $pplf = new PayPeriodListFactory();

        $ph = array(
            'company_id' => (int)$company_id,
            'run_id' => (int)$run_id,
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a,
							' . $ulf->getTable() . ' as b,
							' . $pplf->getTable() . ' as c
					where	a.user_id = b.id
						AND a.pay_period_id = c.id
						AND b.company_id = ?
						AND a.run_id != ?
						AND a.pay_period_id in (' . $this->getListSQL($pay_period_id, $ph, 'int') . ')
						AND a.status_id in (' . $this->getListSQL($status_id, $ph, 'int') . ')
						AND a.deleted = 0';

        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict_order);

        $this->ExecuteSQL($query, $ph, $limit, $page);

        //Debug::Arr($ph, 'Query: '. $query, __FILE__, __LINE__, __METHOD__, 10);

        return $this;
    }

    public function getPayRunStatusByCompanyIdAndPayPeriodId($company_id, $pay_period_id, $limit = null, $page = null, $where = null, $order = null)
    {
        if ($company_id == '') {
            return false;
        }

        if ($pay_period_id == '') {
            return false;
        }

        $strict_order = true;
        if ($order == null or !is_array($order)) {
            $order = array('a.run_id' => 'desc', 'a.status_id' => 'asc');
            $strict_order = false;
        }

        $ulf = new UserListFactory();
        $pplf = new PayPeriodListFactory();

        $ph = array(
            'company_id' => (int)$company_id
        );

        $query = '
					select	a.run_id,a.status_id,count(*) as total_pay_stubs
					from	' . $this->getTable() . ' as a,
							' . $ulf->getTable() . ' as b,
							' . $pplf->getTable() . ' as c
					where	a.user_id = b.id
						AND a.pay_period_id = c.id
						AND b.company_id = ?
						AND a.pay_period_id in (' . $this->getListSQL($pay_period_id, $ph, 'int') . ')
						AND a.deleted = 0
						GROUP BY a.run_id, a.status_id
						';

        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict_order);

        //Debug::Arr($ph, 'Query: '. $query, __FILE__, __LINE__, __METHOD__, 10);

        $this->ExecuteSQL($query, $ph, $limit, $page);

        return $this;
    }

    public function getByUserIdAndPayPeriodId($user_id, $pay_period_id, $where = null, $order = null)
    {
        if ($user_id == '') {
            return false;
        }

        if ($pay_period_id == '') {
            return false;
        }

        $ph = array(
            'pay_period_id' => (int)$pay_period_id,
            'user_id' => (int)$user_id,
        );

        $query = '
					select	*
					from	' . $this->getTable() . '
					where	pay_period_id = ?
						AND user_id = ?
						AND deleted = 0';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
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
        Debug::Arr($order, 'aOrder Data:', __FILE__, __LINE__, __METHOD__, 10);

        $additional_order_fields = array('b.last_name', 'b.first_name');
        if ($order == null) {
            $order = array('a.transaction_date' => 'desc', 'b.last_name' => 'asc');
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
            if (isset($order['status'])) {
                $order['status_id'] = $order['status'];
                unset($order['status']);
            }

            if (isset($order['transaction_date'])) {
                $order['last_name'] = 'asc';
            } else {
                $order['transaction_date'] = 'desc';
            }

            $strict = true;
        }

        if (isset($filter_data['exclude_user_ids'])) {
            $filter_data['exclude_id'] = $filter_data['exclude_user_ids'];
        }
        if (isset($filter_data['include_user_ids'])) {
            $filter_data['user_id'] = $filter_data['include_user_ids'];
        }
        if (isset($filter_data['user_status_ids'])) {
            $filter_data['status_id'] = $filter_data['user_status_ids'];
        }
        if (isset($filter_data['user_title_ids'])) {
            $filter_data['title_id'] = $filter_data['user_title_ids'];
        }
        if (isset($filter_data['group_ids'])) {
            $filter_data['group_id'] = $filter_data['group_ids'];
        }
        if (isset($filter_data['branch_ids'])) {
            $filter_data['default_branch_id'] = $filter_data['branch_ids'];
        }
        if (isset($filter_data['department_ids'])) {
            $filter_data['default_department_id'] = $filter_data['department_ids'];
        }
        if (isset($filter_data['pay_period_ids'])) {
            $filter_data['pay_period_id'] = $filter_data['pay_period_ids'];
        }
        if (isset($filter_data['currency_ids'])) {
            $filter_data['currency_id'] = $filter_data['currency_ids'];
        }

        //Debug::Arr($order, 'bOrder Data:', __FILE__, __LINE__, __METHOD__, 10);
        //Debug::Arr($filter_data, 'Filter Data:', __FILE__, __LINE__, __METHOD__, 10);

        $uf = new UserFactory();

        $ph = array(
            'company_id' => (int)$company_id,
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a
						LEFT JOIN ' . $uf->getTable() . ' as b ON a.user_id = b.id
					where	b.company_id = ?
					';

        if (isset($filter_data['id']) and isset($filter_data['id'][0]) and !in_array(-1, (array)$filter_data['id'])) {
            $query .= ' AND a.id in (' . $this->getListSQL($filter_data['id'], $ph) . ') ';
        }
        if (isset($filter_data['permission_children_ids']) and isset($filter_data['permission_children_ids'][0]) and !in_array(-1, (array)$filter_data['permission_children_ids'])) {
            $query .= ' AND b.id in (' . $this->getListSQL($filter_data['permission_children_ids'], $ph) . ') ';
        }
        if (isset($filter_data['user_id']) and isset($filter_data['user_id'][0]) and !in_array(-1, (array)$filter_data['user_id'])) {
            $query .= ' AND b.id in (' . $this->getListSQL($filter_data['user_id'], $ph) . ') ';
        }
        if (isset($filter_data['exclude_id']) and isset($filter_data['exclude_id'][0]) and !in_array(-1, (array)$filter_data['exclude_id'])) {
            $query .= ' AND b.id not in (' . $this->getListSQL($filter_data['exclude_id'], $ph) . ') ';
        }
        if (isset($filter_data['status_id']) and isset($filter_data['status_id'][0]) and !in_array(-1, (array)$filter_data['status_id'])) {
            $query .= ' AND b.status_id in (' . $this->getListSQL($filter_data['status_id'], $ph) . ') ';
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
        if (isset($filter_data['currency_id']) and isset($filter_data['currency_id'][0]) and !in_array(-1, (array)$filter_data['currency_id'])) {
            $query .= ' AND a.currency_id in (' . $this->getListSQL($filter_data['currency_id'], $ph) . ') ';
        }
        if (isset($filter_data['pay_period_id']) and isset($filter_data['pay_period_id'][0]) and !in_array(-1, (array)$filter_data['pay_period_id'])) {
            $query .= ' AND a.pay_period_id in (' . $this->getListSQL($filter_data['pay_period_id'], $ph) . ') ';
        }
        if (isset($filter_data['pay_stub_status_id']) and isset($filter_data['pay_stub_status_id'][0]) and !in_array(-1, (array)$filter_data['pay_stub_status_id'])) {
            $query .= ' AND a.status_id in (' . $this->getListSQL($filter_data['pay_stub_status_id'], $ph) . ') ';
        }

        if (isset($filter_data['transaction_start_date']) and !is_array($filter_data['transaction_start_date']) and trim($filter_data['transaction_start_date']) != '') {
            $ph[] = $this->db->BindTimeStamp(strtolower(trim($filter_data['transaction_start_date'])));
            $query .= ' AND a.transaction_date >= ?';
        }
        if (isset($filter_data['transaction_end_date']) and !is_array($filter_data['transaction_end_date']) and trim($filter_data['transaction_end_date']) != '') {
            $ph[] = $this->db->BindTimeStamp(strtolower(trim($filter_data['transaction_end_date'])));
            $query .= ' AND a.transaction_date <= ?';
        }
        if (isset($filter_data['transaction_date']) and !is_array($filter_data['transaction_date']) and trim($filter_data['transaction_date']) != '') {
            $ph[] = $this->db->BindTimeStamp(strtolower(trim($filter_data['transaction_date'])));
            $query .= ' AND a.transaction_date = ?';
        }

        $query .= '
						AND ( a.deleted = 0 AND b.deleted = 0 )
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

        if (isset($filter_data['pay_stub_status_id'])) {
            $filter_data['status_id'] = $filter_data['pay_stub_status_id'];
        }

        if (isset($filter_data['pay_stub_run_id'])) {
            $filter_data['run_id'] = $filter_data['pay_stub_run_id'];
        }
        if (isset($filter_data['pay_stub_type_id'])) {
            $filter_data['type_id'] = $filter_data['pay_stub_type_id'];
        }

        if (isset($filter_data['title_id'])) {
            $filter_data['user_title_id'] = $filter_data['title_id'];
        }

        if (isset($filter_data['group_id'])) {
            $filter_data['user_group_id'] = $filter_data['group_id'];
        }

        $additional_order_fields = array('user_status_id', 'last_name', 'first_name', 'default_branch', 'default_department', 'user_group', 'title', 'country', 'province', 'currency');

        $sort_column_aliases = array(
            'user_status' => 'user_status_id',
            'status' => 'status_id',
        );
        $order = $this->getColumnsFromAliases($order, $sort_column_aliases);
        if ($order == null) {
            //Sort by end_date after run_id, so all else being equal later end dates come first.
            $order = array('a.transaction_date' => 'desc', 'a.run_id' => 'desc', 'a.end_date' => 'desc', 'a.start_date' => 'desc', 'b.last_name' => 'asc');
            $strict = false;
        } else {
            if (isset($order['transaction_date'])) {
                $order['last_name'] = 'asc';
            } else {
                $order['transaction_date'] = 'desc';
            }
            $order['run_id'] = 'desc';

            $strict = true;
        }
        //Debug::Arr($order, 'Order Data:', __FILE__, __LINE__, __METHOD__, 10);
        //Debug::Arr($filter_data, 'Filter Data:', __FILE__, __LINE__, __METHOD__, 10);

        $uf = new UserFactory();
        $bf = new BranchFactory();
        $df = new DepartmentFactory();
        $ugf = new UserGroupFactory();
        $utf = new UserTitleFactory();
        $cf = new CurrencyFactory();

        $ph = array(
            'company_id' => (int)$company_id,
        );

        $query = '
					select	a.*,
							b.first_name as first_name,
							b.last_name as last_name,
							b.status_id as user_status_id,
							b.city as city,
							b.province as province,
							b.country as country,

							b.default_branch_id as default_branch_id,
							bf.name as default_branch,
							b.default_department_id as default_department_id,
							df.name as default_department,
							b.group_id as group_id,
							ugf.name as user_group,
							b.title_id as title_id,
							utf.name as title,

							cf.name as currency,

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
						LEFT JOIN ' . $cf->getTable() . ' as cf ON ( a.currency_id = cf.id AND cf.deleted = 0 )

						LEFT JOIN ' . $uf->getTable() . ' as y ON ( a.created_by = y.id AND y.deleted = 0 )
						LEFT JOIN ' . $uf->getTable() . ' as z ON ( a.updated_by = z.id AND z.deleted = 0 )
					where	b.company_id = ?
					';

        $query .= (isset($filter_data['permission_children_ids'])) ? $this->getWhereClauseSQL('b.id', $filter_data['permission_children_ids'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['id'])) ? $this->getWhereClauseSQL('a.id', $filter_data['id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['user_id'])) ? $this->getWhereClauseSQL('b.id', $filter_data['user_id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['include_user_id'])) ? $this->getWhereClauseSQL('b.id', $filter_data['include_user_id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['exclude_user_id'])) ? $this->getWhereClauseSQL('b.id', $filter_data['exclude_user_id'], 'not_numeric_list', $ph) : null;
        $query .= (isset($filter_data['user_status_id'])) ? $this->getWhereClauseSQL('b.status_id', $filter_data['user_status_id'], 'numeric_list', $ph) : null;

        if (isset($filter_data['include_subgroups']) and (bool)$filter_data['include_subgroups'] == true) {
            $uglf = new UserGroupListFactory();
            $filter_data['user_group_id'] = $uglf->getByCompanyIdAndGroupIdAndSubGroupsArray($company_id, $filter_data['user_group_id'], true);
        }
        $query .= (isset($filter_data['user_group_id'])) ? $this->getWhereClauseSQL('b.group_id', $filter_data['user_group_id'], 'numeric_list', $ph) : null;

        $query .= (isset($filter_data['default_branch_id'])) ? $this->getWhereClauseSQL('b.default_branch_id', $filter_data['default_branch_id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['default_department_id'])) ? $this->getWhereClauseSQL('b.default_department_id', $filter_data['default_department_id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['user_title_id'])) ? $this->getWhereClauseSQL('b.title_id', $filter_data['user_title_id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['sex_id'])) ? $this->getWhereClauseSQL('b.sex_id', $filter_data['sex_id'], 'numeric_list', $ph) : null;

        $query .= (isset($filter_data['currency_id'])) ? $this->getWhereClauseSQL('b.currency_id', $filter_data['currency_id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['pay_period_id'])) ? $this->getWhereClauseSQL('a.pay_period_id', $filter_data['pay_period_id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['country'])) ? $this->getWhereClauseSQL('b.country', $filter_data['country'], 'upper_text_list', $ph) : null;
        $query .= (isset($filter_data['province'])) ? $this->getWhereClauseSQL('b.province', $filter_data['province'], 'upper_text_list', $ph) : null;
        $query .= (isset($filter_data['city'])) ? $this->getWhereClauseSQL('b.city', $filter_data['city'], 'text', $ph) : null;

        //Pay Stub Status.
        $query .= (isset($filter_data['status_id'])) ? $this->getWhereClauseSQL('a.status_id', $filter_data['status_id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['type_id'])) ? $this->getWhereClauseSQL('a.type_id', $filter_data['type_id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['run_id'])) ? $this->getWhereClauseSQL('a.run_id', $filter_data['run_id'], 'numeric_list', $ph) : null;

        if (isset($filter_data['start_date']) and !is_array($filter_data['start_date']) and trim($filter_data['start_date']) != '') {
            $ph[] = $this->db->BindTimeStamp((int)$filter_data['start_date']);
            $query .= ' AND a.transaction_date >= ?';
        }
        if (isset($filter_data['end_date']) and !is_array($filter_data['end_date']) and trim($filter_data['end_date']) != '') {
            $ph[] = $this->db->BindTimeStamp((int)$filter_data['end_date']);
            $query .= ' AND a.transaction_date <= ?';
        }

        $query .= (isset($filter_data['created_by'])) ? $this->getWhereClauseSQL(array('a.created_by', 'y.first_name', 'y.last_name'), $filter_data['created_by'], 'user_id_or_name', $ph) : null;
        $query .= (isset($filter_data['updated_by'])) ? $this->getWhereClauseSQL(array('a.updated_by', 'z.first_name', 'z.last_name'), $filter_data['updated_by'], 'user_id_or_name', $ph) : null;

        $query .= ' AND a.deleted = 0 ';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict, $additional_order_fields);

        //Debug::Arr($ph, 'Query: '. $query, __FILE__, __LINE__, __METHOD__, 10);

        $this->ExecuteSQL($query, $ph, $limit, $page);

        return $this;
    }
}
