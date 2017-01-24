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
 * @package Modules\Company
 */
class CompanyUserCountListFactory extends CompanyUserCountFactory implements IteratorAggregate
{
    public function getAll($limit = null, $page = null, $where = null, $order = null)
    {
        $query = '
					select	*
					from	' . $this->getTable() . '
					';
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
						';
            $query .= $this->getWhereSQL($where);
            $query .= $this->getSortSQL($order);

            $this->ExecuteSQL($query, $ph);

            $this->saveCache($this->rs, $id);
        }

        return $this;
    }

    public function getByCompanyId($id, $limit = null, $page = null, $where = null, $order = null)
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
					where	company_id = ?
						';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph, $limit, $page);

        return $this;
    }

    public function getActiveUsers($limit = null, $page = null, $where = null, $order = null)
    {
        $uf = new UserFactory();

        $query = '
					select	company_id,
							count(*) as total
					from	' . $uf->getTable() . '
					where
						status_id = 10
						AND deleted = 0
					GROUP BY company_id
						';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, null, $limit, $page);

        return $this;
    }

    public function getInActiveUsers($limit = null, $page = null, $where = null, $order = null)
    {
        $uf = new UserFactory();

        $query = '
					select	company_id,
							count(*) as total
					from	' . $uf->getTable() . '
					where
						status_id != 10
						AND deleted = 0
					GROUP BY company_id
						';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, null, $limit, $page);

        return $this;
    }

    public function getDeletedUsers($limit = null, $page = null, $where = null, $order = null)
    {
        $uf = new UserFactory();

        $query = '
					select	company_id,
							count(*) as total
					from	' . $uf->getTable() . '
					where
						deleted = 1
					GROUP BY company_id
						';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, null, $limit, $page);

        return $this;
    }

    public function getMinAvgMaxByCompanyIdAndStartDateAndEndDate($id, $start_date, $end_date, $limit = null, $page = null, $where = null, $order = null)
    {
        if ($id == '') {
            return false;
        }

        if ($start_date == '') {
            return false;
        }

        if ($end_date == '') {
            return false;
        }

        $ph = array(
            'company_id' => (int)$id,
            'start_date' => $this->db->BindDate($start_date),
            'end_date' => $this->db->BindDate($end_date),
        );

        $query = '
					select
							min(active_users) as min_active_users,
							ceil(avg(active_users)) as avg_active_users,
							max(active_users) as max_active_users,

							min(inactive_users) as min_inactive_users,
							ceil(avg(inactive_users)) as avg_inactive_users,
							max(inactive_users) as max_inactive_users,

							min(deleted_users) as min_deleted_users,
							ceil(avg(deleted_users)) as avg_deleted_users,
							max(deleted_users) as max_deleted_users

					from	' . $this->getTable() . '
					where	company_id = ?
						AND date_stamp >= ?
						AND date_stamp <= ?
						';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph, $limit, $page);

        return $this;
    }

    //This function returns data for multiple companies, used by the API.
    public function getMinAvgMaxByCompanyIDsAndStartDateAndEndDate($id, $start_date, $end_date, $limit = null, $page = null, $where = null, $order = null)
    {
        if ($id == '') {
            return false;
        }

        if ($start_date == '') {
            return false;
        }

        if ($end_date == '') {
            return false;
        }

        $ph = array(
            //'company_id' => (int)$id,
            'start_date' => $this->db->BindDate($start_date),
            'end_date' => $this->db->BindDate($end_date),
        );

        $query = '
					select
							company_id,
							min(active_users) as min_active_users,
							ceil(avg(active_users)) as avg_active_users,
							max(active_users) as max_active_users,

							min(inactive_users) as min_inactive_users,
							ceil(avg(inactive_users)) as avg_inactive_users,
							max(inactive_users) as max_inactive_users,

							min(deleted_users) as min_deleted_users,
							ceil(avg(deleted_users)) as avg_deleted_users,
							max(deleted_users) as max_deleted_users

					from	' . $this->getTable() . '
					where
						date_stamp >= ?
						AND date_stamp <= ? ';

        if ($id != '' and (isset($id[0]) and !in_array(-1, (array)$id))) {
            $query .= ' AND company_id in (' . $this->getListSQL($id, $ph, 'int') . ') ';
        }

        $query .= ' group by company_id';

        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph, $limit, $page);

        return $this;
    }

    public function getMonthlyMinAvgMaxByCompanyIdAndStartDateAndEndDate($id, $start_date, $end_date, $limit = null, $page = null, $where = null, $order = null)
    {
        if ($id == '') {
            return false;
        }

        if ($start_date == '') {
            return false;
        }

        if ($end_date == '') {
            return false;
        }

        $ph = array(
            'company_id' => (int)$id,
            'start_date' => $this->db->BindDate($start_date),
            'end_date' => $this->db->BindDate($end_date),
        );

        if ($this->getDatabaseType() == 'mysql') {
            //$month_sql = '(month( date_stamp ))';
            $month_sql = '( date_format( date_stamp, \'%Y-%m-01\') )';
        } else {
            //$month_sql = '( date_part(\'month\', date_stamp) )';
            $month_sql = '( to_char(date_stamp, \'YYYY-MM\') || \'-01\' )'; //Concat -01 to end due to EnterpriseDB issue with to_char
        }

        $query = '
					select
							' . $month_sql . ' as date_stamp,
							min(active_users) as min_active_users,
							ceil(avg(active_users)) as avg_active_users,
							max(active_users) as max_active_users,

							min(inactive_users) as min_inactive_users,
							ceil(avg(inactive_users)) as avg_inactive_users,
							max(inactive_users) as max_inactive_users,

							min(deleted_users) as min_deleted_users,
							ceil(avg(deleted_users)) as avg_deleted_users,
							max(deleted_users) as max_deleted_users

					from	' . $this->getTable() . '
					where	company_id = ?
						AND date_stamp >= ?
						AND date_stamp <= ?
					GROUP BY ' . $month_sql . '
						';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph, $limit, $page);

        return $this;
    }

    public function getMonthlyMinAvgMaxByStartDateAndEndDate($start_date, $end_date, $limit = null, $page = null, $where = null, $order = null)
    {
        if ($start_date == '') {
            return false;
        }

        if ($end_date == '') {
            return false;
        }

        $ph = array(
            'start_date' => $this->db->BindDate($start_date),
            'end_date' => $this->db->BindDate($end_date),
        );

        if ($this->getDatabaseType() == 'mysql') {
            //$month_sql = '(month( date_stamp ))';
            $month_sql = '( date_format( date_stamp, \'%Y-%m-01\') )';
        } else {
            //$month_sql = '( date_part(\'month\', date_stamp) )';
            $month_sql = '( to_char(date_stamp, \'YYYY-MM-01\') )';
        }

        $query = '
					select
							company_id,
							' . $month_sql . ' as date_stamp,
							min(active_users) as min_active_users,
							ceil(avg(active_users)) as avg_active_users,
							max(active_users) as max_active_users,

							min(inactive_users) as min_inactive_users,
							ceil(avg(inactive_users)) as avg_inactive_users,
							max(inactive_users) as max_inactive_users,

							min(deleted_users) as min_deleted_users,
							ceil(avg(deleted_users)) as avg_deleted_users,
							max(deleted_users) as max_deleted_users

					from	' . $this->getTable() . '
					where
						date_stamp >= ?
						AND date_stamp <= ?
					GROUP BY company_id, ' . $month_sql . '
					ORDER BY company_id, ' . $month_sql . '
						';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph, $limit, $page);

        return $this;
    }

    //This gets the totals across all companies.
    public function getTotalMonthlyMinAvgMaxByCompanyStatusAndStartDateAndEndDate($status_id, $start_date, $end_date, $limit = null, $page = null, $where = null, $order = null)
    {
        if ($start_date == '') {
            return false;
        }

        if ($end_date == '') {
            return false;
        }

        $cf = TTNew('CompanyFactory');

        $ph = array(
            'status_id' => (int)$status_id,
            'start_date' => $this->db->BindDate($start_date),
            'end_date' => $this->db->BindDate($end_date),
        );

        if ($this->getDatabaseType() == 'mysql') {
            //$month_sql = '(month( date_stamp ))';
            $month_sql = '( date_format( a.date_stamp, \'%Y-%m-01\') )';
        } else {
            //$month_sql = '( date_part(\'month\', date_stamp) )';
            $month_sql = '( to_char(a.date_stamp, \'YYYY-MM-01\') )';
        }

        $query = '
					select
							date_stamp,
							sum(min_active_users) as min_active_users,
							sum(avg_active_users) as avg_active_users,
							sum(max_active_users) as max_active_users,

							sum(min_inactive_users) as min_inactive_users,
							sum(avg_inactive_users) as avg_inactive_users,
							sum(max_inactive_users) as max_inactive_users,

							sum(min_deleted_users) as min_deleted_users,
							sum(avg_deleted_users) as avg_deleted_users,
							sum(max_deleted_users) as max_deleted_users
					FROM (
							select
									company_id,
									' . $month_sql . ' as date_stamp,
									min(a.active_users) as min_active_users,
									ceil(avg(a.active_users)) as avg_active_users,
									max(a.active_users) as max_active_users,

									min(a.inactive_users) as min_inactive_users,
									ceil(avg(a.inactive_users)) as avg_inactive_users,
									max(a.inactive_users) as max_inactive_users,

									min(a.deleted_users) as min_deleted_users,
									ceil(avg(a.deleted_users)) as avg_deleted_users,
									max(a.deleted_users) as max_deleted_users

							from	' . $this->getTable() . ' as a
								LEFT JOIN ' . $cf->getTable() . ' as cf ON ( a.company_id = cf.id )
							where
								cf.status_id = ?
								AND a.date_stamp >= ?
								AND a.date_stamp <= ?
								AND ( cf.deleted = 0 )
							GROUP BY company_id, ' . $month_sql . '
						) as tmp
					GROUP BY date_stamp
						';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph, $limit, $page);

        return $this;
    }

    public function getLastDateByCompanyId($company_id, $order = null)
    {
        if ($company_id == '') {
            return false;
        }

        $ph = array(
            'company_id' => (int)$company_id,
        );

        $query = '
					select	*
					from	' . $this->getTable() . '
					where	company_id = ?
					ORDER BY date_stamp desc
					LIMIT 1
						';
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

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

        $ph = array(
            'company_id' => (int)$company_id,
            'id' => (int)$id,
        );

        $query = '
					select	*
					from	' . $this->getTable() . '
					where	company_id = ?
						AND	id = ?
						';
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }
}
