<?php
/*********************************************************************************
 * This file is part of "Fairness", a Payroll and Time Management program.
 * Fairness is Copyright 2013 Aydan Coskun (aydan.ayfer.coskun@gmail.com)
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
 * $Revision: 2095 $
 * $Id: FactoryListIterator.class.php 2095 2008-09-01 07:04:25Z ipso $
 * $Date: 2008-09-01 00:04:25 -0700 (Mon, 01 Sep 2008) $
 */

/**
 * @package Core
 */
class FactoryListIterator implements Iterator {
    private $obj;
	private $rs;
	private $class_name;

    function __construct($obj) {
		$this->class_name = get_class($obj);

		if ( isset($obj->rs) ) {
			$this->rs = $obj->rs;
		}

		$this->obj = $obj;
    }

    function rewind() {
		if ( isset($this->obj->rs) ) {
			$this->obj->rs->MoveFirst();
		}

		return FALSE;
    }

    function valid() {
		if ( isset($this->obj->rs) ) {
			return !$this->obj->rs->EOF;
		}

		return FALSE;
    }

    function key() {
        return $this->obj->rs->_currentRow;
    }

    function current() {
		if ( isset($this->obj->rs) ) { //Stop some warnings from coming up?

			//This automatically resets the object during each iteration in a foreach()
			//Without this, data can persist and cause undesirable results.

			$this->obj = new $this->class_name();

			$this->obj->rs = $this->rs;

			$this->obj->data = $this->obj->rs->fields; //Orignal
		}

		return $this->obj;
    }

    function next() {
        $this->obj->rs->MoveNext();
    }

}
?>
