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
 * @package Modules\Import
 */
class ImportUserWage extends Import
{
    public $class_name = 'APIUserWage';

    public $wage_group_options = false;

    public function _getFactoryOptions($name, $parent = null)
    {
        $retval = null;
        switch ($name) {
            case 'columns':
                $uwf = TTNew('UserWageFactory');
                $retval = Misc::prependArray($this->getUserIdentificationColumns(), Misc::arrayIntersectByKey(array('wage_group', 'type', 'wage', 'effective_date', 'hourly_rate', 'labor_burden_percent', 'weekly_time', 'note'), Misc::trimSortPrefix($uwf->getOptions('columns'))));

                break;
            case 'column_aliases':
                //Used for converting column names after they have been parsed.
                $retval = array(
                    'type' => 'type_id',
                    'wage_group' => 'wage_group_id',
                );
                break;
            case 'import_options':
                $retval = array(
                    '-1010-fuzzy_match' => TTi18n::getText('Enable smart matching.'),
                );
                break;
            case 'parse_hint':
            case 'parse_hint':
                $upf = TTnew('UserPreferenceFactory');

                $retval = array(
                    'effective_date' => $upf->getOptions('date_format'),
                    'weekly_time' => $upf->getOptions('time_unit_format'),
                );
                break;
        }

        return $retval;
    }


    public function _preParseRow($row_number, $raw_row)
    {
        $user_id = $this->getUserIdByRowData($raw_row); //Try to get user_id of row so we set default effective_date to the employees hire_date.
        $user_wage_default_data = $this->getObject()->stripReturnHandler($this->getObject()->getUserWageDefaultData($user_id));
        $user_wage_default_data['effective_date'] = TTDate::parseDateTime($user_wage_default_data['effective_date']); //Effective Date is formatted for user to see, so convert it to epoch for importing.

        $retval = $user_wage_default_data;

        return $retval;
    }

    public function _postParseRow($row_number, $raw_row)
    {
        $raw_row['user_id'] = $this->getUserIdByRowData($raw_row);
        if ($raw_row['user_id'] == false) {
            unset($raw_row['user_id']);
        }

        //If its a salary type, make sure average weekly time is always specified and hourly rate.
        return $raw_row;
    }

    public function _import($validate_only)
    {
        return $this->getObject()->setUserWage($this->getParsedData(), $validate_only);
    }

    //
    // Generic parser functions.
    //

    public function parse_wage_group($input, $default_value = null, $parse_hint = null)
    {
        if (trim($input) == '' or trim(strtolower($input)) == 'default') {
            return 0; //Default Wage Group
        }

        if (!is_array($this->wage_group_options)) {
            $this->getWageGroupOptions();
        }

        $retval = $this->findClosestMatch($input, $this->wage_group_options);
        if ($retval === false) {
            $retval = -1; //Make sure this fails.
        }

        return $retval;
    }

    public function getWageGroupOptions()
    {
        //Get job titles
        $wglf = TTNew('WageGroupListFactory');
        $wglf->getByCompanyId($this->company_id);
        $this->wage_group_options = (array)$wglf->getArrayByListFactory($wglf, false, true);
        unset($wglf);

        return true;
    }

    public function parse_effective_date($input, $default_value = null, $parse_hint = null, $raw_row = null)
    {
        if (isset($parse_hint) and $parse_hint != '') {
            TTDate::setDateFormat($parse_hint);
            return TTDate::parseDateTime($input);
        } else {
            return TTDate::strtotime($input);
        }
    }

    public function parse_type($input, $default_value = null, $parse_hint = null, $raw_row = null)
    {
        $uwf = TTnew('UserWageFactory');
        $options = Misc::trimSortPrefix($uwf->getOptions('type'));

        if (isset($options[$input])) {
            $retval = $input;
        } else {
            if ($this->getImportOptions('fuzzy_match') == true) {
                $retval = $this->findClosestMatch($input, $options, 50);
            } else {
                $retval = array_search(strtolower($input), array_map('strtolower', $options));
            }
        }

        if ($retval === false) {
            if (strtolower($input) == 'salary' or strtolower($input) == 'salaried' or strtolower($input) == 's' or strtolower($input) == 'annual') {
                $retval = 20;
            } elseif (strtolower($input) == 'month' or strtolower($input) == 'monthly') {
                $retval = 15;
            } elseif (strtolower($input) == 'biweekly' or strtolower($input) == 'bi-weekly') {
                $retval = 13;
            } elseif (strtolower($input) == 'week' or strtolower($input) == 'weekly') {
                $retval = 12;
            } else {
                $retval = 10;
            }
        }

        return $retval;
    }

    public function parse_weekly_time($input, $default_value = null, $parse_hint = null, $raw_row = null)
    {
        if (isset($parse_hint) and $parse_hint != '') {
            TTDate::setTimeUnitFormat($parse_hint);
        }

        $retval = TTDate::parseTimeUnit($input);

        return $retval;
    }

    public function parse_wage($input, $default_value = null, $parse_hint = null, $raw_row = null)
    {
        $val = new Validator();
        $retval = $val->stripNonFloat($input);

        return $retval;
    }
}
