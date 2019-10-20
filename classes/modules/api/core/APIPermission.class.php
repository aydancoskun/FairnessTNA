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
 * @package API\Core
 */
class APIPermission extends APIFactory {
	protected $main_class = 'PermissionFactory';

	/**
	 * APIPermission constructor.
	 */
	public function __construct() {
		parent::__construct(); //Make sure parent constructor is always called.

		return TRUE;
	}

	/**
	 * @return mixed
	 */
	function getUniqueCountry() {
		global $current_company;
		$company_id = TTUUID::castUUID($current_company->getId());

		$ulf = TTNew('UserListFactory'); /** @var UserListFactory $ulf */
		return $ulf->getUniqueCountryByCompanyId( $company_id );
	}

	/**
	 * @param string $user_id UUID
	 * @param string $company_id UUID
	 * @return array|bool
	 */
	function getPermissions( $user_id = NULL, $company_id = NULL ) {
		if ( $user_id == NULL OR $user_id == '' ) {
			global $current_user;

			$user_id = $current_user->getId();
		}

		if ( $company_id == NULL OR $company_id == '' ) {
			global $current_company;

			$company_id = $current_company->getId();
		}

		$permission = new Permission();
		return $this->returnHandler( $permission->getPermissions( $user_id, $company_id ) );
	}

	/**
	 * @param $section_groups
	 * @return array|bool
	 */
	function getSectionBySectionGroup( $section_groups ) {
		if ( !is_array($section_groups) ) {
			$section_groups = array( $section_groups );
		}
		$section_groups = Misc::trimSortPrefix( $section_groups, TRUE );
		//Debug::Arr($section_groups, 'aSection Groups: ', __FILE__, __LINE__, __METHOD__, 10);

		$section_options = Misc::trimSortPrefix( $this->getOptions('section') );
		$section_group_map = Misc::trimSortPrefix( $this->getOptions('section_group_map') );

		if ( in_array( 'all', $section_groups ) ) {
			//Debug::Text('Returning ALL section Groups: ', __FILE__, __LINE__, __METHOD__, 10);
			$section_groups = array_keys( (array)$this->getOptions('section_group') );
			unset($section_groups[0]);
		}

		//Debug::Arr($section_groups, 'bSection Groups: ', __FILE__, __LINE__, __METHOD__, 10);
		$retarr = array();
		foreach( $section_groups as $section_group ) {
			$section_group = Misc::trimSortPrefix( $section_group );
			if ( isset($section_group_map[$section_group]) ) {
				foreach( $section_group_map[$section_group] as $tmp_section ) {
					$retarr[$tmp_section] = $section_options[$tmp_section];
				}
			}
		}

		if ( count($retarr) > 0 ) {
			//Debug::Arr($retarr, 'Sections: ', __FILE__, __LINE__, __METHOD__, 10);
			return $this->returnHandler( Misc::trimSortPrefix( $retarr, 1000 ) );
		}

		return FALSE;
	}

	/**
	 * @param $preset
	 * @param bool $filter_sections
	 * @param bool $filter_permissions
	 * @return array|bool
	 */
	function filterPresetPermissions( $preset, $filter_sections = FALSE, $filter_permissions = FALSE ) {
		$pf = TTNew('PermissionFactory'); /** @var PermissionFactory $pf */
		return $this->returnHandler( $pf->filterPresetPermissions( $preset, $filter_sections, $filter_permissions ) );
	}
}
?>
