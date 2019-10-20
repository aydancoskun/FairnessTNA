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
 * @package Modules\Install
 */
class InstallSchema_1041A extends InstallSchema_Base {

	/**
	 * @return bool
	 */
	function preInstall() {
		Debug::text('preInstall: '. $this->getVersion(), __FILE__, __LINE__, __METHOD__, 9);

		return TRUE;
	}

	/**
	 * @return bool
	 */
	function postInstall() {
		Debug::text('postInstall: '. $this->getVersion(), __FILE__, __LINE__, __METHOD__, 9);

		//Modify all hierarchies with the request object type included, to add new request object types.
		$hclf = TTnew( 'HierarchyControlListFactory' ); /** @var HierarchyControlListFactory $hclf */
		$hclf->getAll();
		if ( $hclf->getRecordCount() > 0 ) {
			foreach( $hclf as $hc_obj ) {
				$src_object_types = $hc_obj->getObjectType();
				$request_key = array_search( 50, $src_object_types );
				if ( $request_key !== FALSE ) {
					Debug::Text('Found request object type, ID: '. $hc_obj->getId() .' Company ID: '. $hc_obj->getCompany(), __FILE__, __LINE__, __METHOD__, 10);
					unset($src_object_types[$request_key]);

					$src_object_types[] = 1010;
					$src_object_types[] = 1020;
					$src_object_types[] = 1030;
					$src_object_types[] = 1040;
					$src_object_types[] = 1100;
					$src_object_types = array_unique( $src_object_types );

					$hc_obj->setObjectType( $src_object_types );
					if ( $hc_obj->isValid() ) {
						$hc_obj->Save();
					}
				} else {
					Debug::Text('Request object type not found for ID: '. $hc_obj->getId() .' Company ID: '. $hc_obj->getCompany(), __FILE__, __LINE__, __METHOD__, 10);
				}
			}
		}

		return TRUE;
	}
}
?>
