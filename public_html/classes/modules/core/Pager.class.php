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
 * @package Core
 */
class Pager {
	protected $rs = NULL;
	protected $count_rows = TRUE; //Specify if we count the total rows or not.

	function __construct($arr) {
		if ( isset($arr->rs) ) {
			//If there is no RS to return, something is seriously wrong. Check interface.inc.php?
			//Make sure the ListFactory function is doing a pageselect
			$this->rs = $arr->rs;

			$this->count_rows = $arr->db->pageExecuteCountRows;

			return TRUE;
		}

		return FALSE;
	}

	function getPreviousPage() {
		if ( is_object($this->rs) ) {
			return (int)( $this->rs->absolutepage() - 1 );
		}

		return FALSE;
	}

	function getCurrentPage() {
		if ( is_object($this->rs) ) {
			return (int)$this->rs->absolutepage();
		}

		return FALSE;
	}

	function getNextPage() {
		if ( is_object($this->rs) ) {
			return (int)( $this->rs->absolutepage() + 1 );
		}

		return FALSE;
	}

	function isFirstPage() {
		if ( is_object($this->rs) ) {
			return (bool)$this->rs->atfirstpage();
		}

		return TRUE;
	}

	function isLastPage() {
		//If the first page is also the last, return true.
		if ( $this->isFirstPage() AND $this->LastPageNumber() == 1) {
			return TRUE;
		}

		if ( is_object($this->rs) ) {
			return (bool)$this->rs->atlastpage();
		}

		return TRUE;
	}

	function LastPageNumber() {
		if ( is_object($this->rs) ) {
			if ( $this->count_rows === FALSE ) {
				if ( $this->getCurrentPage() < 0 ) {
					//Only one page in result set.
					return (int)$this->rs->lastpageno();
				} else {
					//More than one page in result set.
					if ( $this->rs->atlastpage() == TRUE ) {
						return (int)$this->getCurrentPage();
					} else {
						//Since we don't know what the actual last page is, just add 100 pages to the current one.
						//The user may need to click this several times if there are more than 100 pages.
						return (int)( $this->getCurrentPage() + 99 );
					}
				}
			} else {
				return (int)$this->rs->lastpageno();
			}
		}

		return FALSE;
	}

	//Return maximum rows per page
	function getRowsPerPage() {
		if ( is_object($this->rs) ) {
			if ( isset($this->rs->rowsPerPage) ) {
				return (int)$this->rs->rowsPerPage;
			} else {
				return (int)$this->rs->recordcount();
			}
		}

		return FALSE;
	}

	function getTotalRows() {
		if ( is_object($this->rs) ) {
			if ( $this->count_rows === FALSE ) {
				if ( $this->isLastPage() === TRUE ) {
					return (int)( ( $this->getPreviousPage() * $this->getRowsPerPage() ) + $this->rs->recordcount() );
				} else {
					return FALSE;
				}
			} else {
				return (int)$this->rs->maxrecordcount();
			}
		}

		return FALSE;
	}

	function getPageVariables() {
		//Make sure the ListFactory function is doing a pageselect
		$paging_data = array(
							'previous_page'		=> $this->getPreviousPage(),
							'current_page'		=> $this->getCurrentPage(),
							'next_page'			=> $this->getNextPage(),
							'is_first_page'		=> $this->isFirstPage(),
							'is_last_page'		=> $this->isLastPage(),
							'last_page_number'	=> $this->LastPageNumber(),
							'rows_per_page'		=> $this->getRowsPerPage(),
							'total_rows'		=> $this->getTotalRows(),
							);
		//Debug::Arr($paging_data, ' Paging Data: Count Rows: '. (int)$this->count_rows, __FILE__, __LINE__, __METHOD__, 10);
		return $paging_data;
	}
}
?>
