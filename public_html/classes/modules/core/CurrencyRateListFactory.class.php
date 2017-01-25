<?php
/**********************************************************************************
 * This file is part of "FairnessTNA", a Payroll and Time Management program.
 * FairnessTNA is copyright 2013-2017 Aydan Coskun (aydan.ayfer.coskun@gmail.com)
 * others. For full attribution and copyrights details see the COPYRIGHT file.
 *
 * FairnessTNA is free software; you can redistribute it and/or modify it under the
 * terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation, either version 3 of the License, or (at you option )
 * any later version.
 *
 * FairnessTNA is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE.  See the GNU Affero General Public License for more
 * details.
 *
 * You should have received a copy of the GNU Affero General Public License along
 * with this program; if not, see http://www.gnu.org/licenses or write to the Free
 * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301 USA.
 *********************************************************************************/


/**
 * @package Core
 */
class CurrencyRateListFactory extends CurrencyRateFactory implements IteratorAggregate
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

    public function getByCurrencyId($id, $limit = null, $page = null, $where = null, $order = null)
    {
        if ($id == '') {
            return false;
        }

        if ($order == null) {
            $order = array('date_stamp' => 'asc');
            $strict = false;
        } else {
            $strict = true;
        }

        $ph = array(
            'id' => (int)$id,
        );


        $query = '
					select	*
					from	' . $this->getTable() . '
					where	currency_id = ?
						AND deleted = 0';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict);

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

        $cf = new CurrencyFactory();

        $ph = array(
            'id' => (int)$id,
            'company_id' => (int)$company_id,
        );

        $query = '
					select	a.*
					from	' . $this->getTable() . ' as a
					LEFT JOIN ' . $cf->getTable() . ' as cf ON ( a.currency_id = cf.id )
					where	a.id = ?
						AND cf.company_id = ?
						AND ( a.deleted = 0 AND cf.deleted = 0 )';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByCurrencyIdAndDateStamp($id, $date_stamp)
    {
        if ($id == '') {
            return false;
        }

        if ($date_stamp == '') {
            return false;
        }

        $ph = array(
            'id' => (int)$id,
            'date_stamp' => $this->db->bindDate($date_stamp),
        );

        $query = '
					select	*
					from	' . $this->getTable() . '
					where	currency_id = ?
						AND date_stamp = ?
						AND deleted = 0';

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByCurrencyIdAndStartDateAndEndDate($id, $start_date, $end_date)
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
            'id' => (int)$id,
            'start_date' => $this->db->bindDate($start_date),
            'end_date' => $this->db->bindDate($end_date),
        );

        $query = '
					select	*
					from	' . $this->getTable() . '
					where	currency_id = ?
						AND date_stamp >= ?
						AND date_stamp <= ?
						AND deleted = 0';

        $this->ExecuteSQL($query, $ph);

        return $this;
    }

    public function getByIdAndCurrencyId($id, $currency_id, $order = null)
    {
        if ($id == '') {
            return false;
        }

        if ($currency_id == '') {
            return false;
        }

        $ph = array(
            'currency_id' => (int)$currency_id,
            'id' => (int)$id,
        );

        $query = '
					select	*
					from	' . $this->getTable() . '
					where	currency_id = ?
						AND	id = ?
						AND deleted = 0';
        $query .= $this->getSortSQL($order);

        $this->ExecuteSQL($query, $ph);

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

        $additional_order_fields = array();
        if ($order == null) {
            $order = array('date_stamp' => 'desc');
            $strict = false;
        } else {
            //Always sort by last name, first name after other columns
            if (!isset($order['date_stamp'])) {
                $order['date_stamp'] = 'desc';
            }
            $strict = true;
        }
        //Debug::Arr($order, 'Order Data:', __FILE__, __LINE__, __METHOD__, 10);
        //Debug::Arr($filter_data, 'Filter Data:', __FILE__, __LINE__, __METHOD__, 10);

        $cf = new CurrencyFactory();
        $uf = new UserFactory();

        $ph = array(
            'company_id' => (int)$company_id,
        );

        $query = '
					select	a.*,
							y.first_name as created_by_first_name,
							y.middle_name as created_by_middle_name,
							y.last_name as created_by_last_name,
							z.first_name as updated_by_first_name,
							z.middle_name as updated_by_middle_name,
							z.last_name as updated_by_last_name
					from	' . $this->getTable() . ' as a
					LEFT JOIN ' . $cf->getTable() . ' as cf ON ( a.currency_id = cf.id AND cf.deleted = 0 )
						LEFT JOIN ' . $uf->getTable() . ' as y ON ( a.created_by = y.id AND y.deleted = 0 )
						LEFT JOIN ' . $uf->getTable() . ' as z ON ( a.updated_by = z.id AND z.deleted = 0 )
					where	cf.company_id = ?
					';

        $query .= (isset($filter_data['permission_children_ids'])) ? $this->getWhereClauseSQL('cf.created_by', $filter_data['permission_children_ids'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['id'])) ? $this->getWhereClauseSQL('a.id', $filter_data['id'], 'numeric_list', $ph) : null;
        $query .= (isset($filter_data['exclude_id'])) ? $this->getWhereClauseSQL('a.id', $filter_data['exclude_id'], 'not_numeric_list', $ph) : null;

        $query .= (isset($filter_data['currency_id'])) ? $this->getWhereClauseSQL('a.currency_id', $filter_data['currency_id'], 'numeric_list', $ph) : null;

        if (isset($filter_data['iso_code']) and isset($filter_data['iso_code'][0]) and !in_array(-1, (array)$filter_data['iso_code'])) {
            $query .= (isset($filter_data['iso_code'])) ? $this->getWhereClauseSQL('cf.iso_code', $filter_data['iso_code'], 'numeric_list', $ph) : null;
        }
        if (isset($filter_data['iso_code']) and !is_array($filter_data['iso_code']) and trim($filter_data['iso_code']) != '' and !is_array($filter_data['iso_code'])) {
            $query .= (isset($filter_data['iso_code'])) ? $this->getWhereClauseSQL('cf.iso_code', $filter_data['iso_code'], 'text', $ph) : null;
        }

        $query .= (isset($filter_data['name'])) ? $this->getWhereClauseSQL('cf.name', $filter_data['name'], 'text', $ph) : null;

        if (isset($filter_data['status']) and !is_array($filter_data['status']) and trim($filter_data['status']) != '' and !isset($filter_data['status_id'])) {
            $filter_data['status_id'] = Option::getByFuzzyValue($filter_data['status'], $this->getOptions('status'));
        }
        $query .= (isset($filter_data['status_id'])) ? $this->getWhereClauseSQL('cf.status_id', $filter_data['status_id'], 'numeric_list', $ph) : null;

        $query .= (isset($filter_data['created_by'])) ? $this->getWhereClauseSQL(array('a.created_by', 'y.first_name', 'y.last_name'), $filter_data['created_by'], 'user_id_or_name', $ph) : null;
        $query .= (isset($filter_data['updated_by'])) ? $this->getWhereClauseSQL(array('a.updated_by', 'z.first_name', 'z.last_name'), $filter_data['updated_by'], 'user_id_or_name', $ph) : null;

        $query .= ' AND a.deleted = 0 ';
        $query .= $this->getWhereSQL($where);
        $query .= $this->getSortSQL($order, $strict, $additional_order_fields);

        $this->ExecuteSQL($query, $ph, $limit, $page);

        return $this;
    }
}
