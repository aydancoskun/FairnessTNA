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
 * @package API\PayStubEntryAccountLink
 */

class APIPayStubEntryAccountLink extends APIFactory {
	protected $main_class = 'PayStubFactoryAccountLink';

	/**
	 * APIPayStubEntryAccountLink constructor.
	 */
	public function __construct() {
		parent::__construct(); //Make sure parent constructor is always called.

		return TRUE;
	}

	/**
	 * Return pay stub entry accounts that are linked to total gross, net pay, etc
	 * @return array|bool
	 */
	public function getPayStubEntryAccountLink() {
		if ( !$this->getPermissionObject()->Check('pay_stub', 'enabled')
				OR !( $this->getPermissionObject()->Check('pay_stub', 'view') OR $this->getPermissionObject()->Check('pay_stub', 'view_child')	) ) {
			return $this->getPermissionObject()->PermissionDenied();
		}

		$pseallf = TTnew( 'PayStubEntryAccountLinkListFactory' ); /** @var PayStubEntryAccountLinkListFactory $pseallf */
		$pseallf->getByCompanyId($this->getCurrentUserObject()->getCompany());
		Debug::Text('PayStubEntryAccountLink Record Count: '. $pseallf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);

		if ( $pseallf->getRecordCount() > 0 ) {
			$this->setPagerObject( $pseallf );

			$prev_type = NULL;
			$retarr = array();

			foreach( $pseallf as $pseal_obj ) {
				$retarr[] = $pseal_obj->data; //FIXME: whip up an objectToArray function
			}

			return $this->returnHandler( $retarr );
		}

		return $this->returnHandler( TRUE ); //No records returned.
	}

}