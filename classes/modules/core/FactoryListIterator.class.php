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
 * @package Core
 */
class FactoryListIterator implements Iterator {
	private $template_obj;
	private $template_validator_obj;
	private $obj;
	private $rs;
	private $class_name;

	/**
	 * FactoryListIterator constructor.
	 * @param object $obj
	 */
	function __construct( $obj) {
		$this->class_name = get_class($obj);

		//Save a cleanly instantiated object in memory so we can simply clone it rather than instantiate a new one every loop iteration in current()
		//  It appears this doesn't work for the sub-objects like Validator. If one iteration in a loop has a validation error, all the rest will too.
		$this->template_obj = new $this->class_name();
		$this->template_validator_obj = new Validator();

		if ( isset($obj->rs) ) {
			$this->rs = $obj->rs;
		}

		$this->obj = $obj;
	}

	/**
	 * @return bool
	 */
	function rewind() {
		if ( isset($this->obj->rs) ) {
			$this->obj->rs->MoveFirst();
		}

		return FALSE;
	}

	/**
	 * @return bool
	 */
	function valid() {
		if ( isset($this->obj->rs) ) {
			return !$this->obj->rs->EOF;
		}

		return FALSE;
	}

	/**
	 * @return mixed
	 */
	function key() {
		return $this->obj->rs->_currentRow;
	}

	/**
	 * @return mixed
	 */
	function current() {
		if ( isset($this->obj->rs) ) { //Stop some warnings from coming up?
			//This automatically resets the object during each iteration in a foreach()
			//Without this, data can persist and cause undesirable results.

			//  It appears this doesn't work for the sub-objects like Validator. If one iteration in a loop has a validation error, all the rest will too.
			//  Tested in: FactoryTest.php->testFactoryListIteratorA()
			$this->obj = clone $this->template_obj; //Copy the template object to avoid having to instantiate it each loop iteration. This is about 30% faster.
			$this->obj->tmp_data = array();
			$this->obj->Validator = clone $this->template_validator_obj; //Clone sub-objects here, rather than in the __clone function, as it seems to be about 10% faster here.
			$this->obj->is_valid = FALSE;
//			$this->obj = new $this->class_name();

			$this->obj->rs = $this->rs;

			//Set old_data at the same time as data, so we can check to see what fields have changed by using getDataDifferences() in any other function (ie: Validate,preSave,postSave)
			//This used to be done in getUpdateQuery(), but that was too late for Validate/preSave().
			$this->obj->data = $this->obj->old_data = $this->obj->rs->fields; //Orignal
		}

		return $this->obj;
	}

	function next() {
		$this->obj->rs->MoveNext();
	}
}
?>
