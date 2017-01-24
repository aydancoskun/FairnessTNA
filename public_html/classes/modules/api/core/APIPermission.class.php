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
 * @package API\Core
 */
class APIPermission extends APIFactory
{
    protected $main_class = 'PermissionFactory';

    public function __construct()
    {
        parent::__construct(); //Make sure parent constructor is always called.

        return true;
    }

    public function getUniqueCountry()
    {
        global $current_company;
        $company_id = (int)$current_company->getId();

        $ulf = TTNew('UserListFactory');
        return $ulf->getUniqueCountryByCompanyId($company_id);
    }

    public function getPermissions($user_id = null, $company_id = null)
    {
        if ($user_id == null or $user_id == '') {
            global $current_user;

            $user_id = $current_user->getId();
        }

        if ($company_id == null or $company_id == '') {
            global $current_company;

            $company_id = $current_company->getId();
        }

        $permission = new Permission();
        return $this->returnHandler($permission->getPermissions($user_id, $company_id));
    }

    public function getSectionBySectionGroup($section_groups)
    {
        if (!is_array($section_groups)) {
            $section_groups = array($section_groups);
        }
        $section_groups = Misc::trimSortPrefix($section_groups, true);
        //Debug::Arr($section_groups, 'aSection Groups: ', __FILE__, __LINE__, __METHOD__, 10);

        $section_options = Misc::trimSortPrefix($this->getOptions('section'));
        $section_group_map = Misc::trimSortPrefix($this->getOptions('section_group_map'));

        if (in_array('all', $section_groups)) {
            //Debug::Text('Returning ALL section Groups: ', __FILE__, __LINE__, __METHOD__, 10);
            $section_groups = array_keys($this->getOptions('section_group'));
            unset($section_groups[0]);
        }

        //Debug::Arr($section_groups, 'bSection Groups: ', __FILE__, __LINE__, __METHOD__, 10);
        $retarr = array();
        foreach ($section_groups as $section_group) {
            $section_group = Misc::trimSortPrefix($section_group);
            if (isset($section_group_map[$section_group])) {
                foreach ($section_group_map[$section_group] as $tmp_section) {
                    $retarr[$tmp_section] = $section_options[$tmp_section];
                }
            }
        }

        if (count($retarr) > 0) {
            //Debug::Arr($retarr, 'Sections: ', __FILE__, __LINE__, __METHOD__, 10);
            return $this->returnHandler(Misc::trimSortPrefix($retarr, 1000));
        }

        return false;
    }

    public function filterPresetPermissions($preset, $filter_sections = false, $filter_permissions = false)
    {
        $pf = TTNew('PermissionFactory');
        return $this->returnHandler($pf->filterPresetPermissions($preset, $filter_sections, $filter_permissions));
    }
}
