<?php
/*********************************************************************************
 * This file is part of "Fairness", a Payroll and Time Management program.
 * Fairness is Copyright 2013 Aydan Coscun (aydan.ayfer.coskun@gmail.com)
 * Portions of this software are Copyright (C) 2003 - 2013 TimeTrex Software Inc.
 * because Fairness is a fork of "TimeTrex Workforce Management" Software.
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
/*
 * $Revision: 7121 $
 * $Id: SystemSettingListFactory.class.php 7121 2012-06-22 22:55:40Z ipso $
 * $Date: 2012-06-22 15:55:40 -0700 (Fri, 22 Jun 2012) $
 */

/**
 * @package Core
 */
class SystemSettingListFactory extends SystemSettingFactory implements IteratorAggregate {

	function getAll($limit = NULL, $page = NULL, $where = NULL, $order = NULL) {
		$query = '
					select 	*
					from	'. $this->getTable() .'
					';
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
					'id' => $id,
					);

		$query = '
					select 	*
					from	'. $this->getTable() .'
					where	id = ?
					';
		$query .= $this->getWhereSQL( $where );
		$query .= $this->getSortSQL( $order );

		$this->ExecuteSQL( $query, $ph );

		return $this;
	}

	function getByName($name, $where = NULL, $order = NULL) {
		if ( $name == '') {
			return FALSE;
		}

		$ph = array(
					'name' => $name,
					);

		$this->rs = $this->getCache($name);
		if ( $this->rs === FALSE ) {
			$query = '
						select 	*
						from	'. $this->getTable() .'
						where	name = ?
						';
			$query .= $this->getWhereSQL( $where );
			$query .= $this->getSortSQL( $order );

			$this->ExecuteSQL( $query, $ph );

			$this->saveCache($this->rs,$name);
		}
		
		return $this;
	}

	function getAllArray() {
		$id = 'all';

		$retarr = $this->getCache($id);
		if ( $retarr === FALSE ) {
			$sslf = new SystemSettingListFactory();
			$sslf->getAll();
			if ( $sslf->getRecordCount() > 0 ) {
				foreach( $sslf as $ss_obj ) {
					$retarr[$ss_obj->getName()] = $ss_obj->getValue();
				}

				$this->saveCache($retarr,$id);

				return $retarr;
			} else {
				return FALSE;
			}
		}

		return $retarr;
	}
}
?>
