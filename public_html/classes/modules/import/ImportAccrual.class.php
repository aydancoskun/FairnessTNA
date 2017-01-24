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
 * @package Modules\Import
 */
class ImportAccrual extends Import
{
    public $class_name = 'APIAccrual';

    public $accrual_policy_account_options = false;

    public function _getFactoryOptions($name, $parent = null)
    {
        $retval = null;
        switch ($name) {
            case 'columns':
                $apf = TTNew('AccrualFactory');
                $retval = Misc::prependArray($this->getUserIdentificationColumns(), Misc::arrayIntersectByKey(array('accrual_policy_account', 'type', 'amount', 'date_stamp'), Misc::trimSortPrefix($apf->getOptions('columns'))));

                break;
            case 'column_aliases':
                //Used for converting column names after they have been parsed.
                $retval = array(
                    'type' => 'type_id',
                    'accrual_policy_account' => 'accrual_policy_account_id',
                );
                break;
            case 'import_options':
                $retval = array(
                    '-1010-fuzzy_match' => TTi18n::getText('Enable smart matching.'),
                );
                break;
            case 'parse_hint':
                $upf = TTnew('UserPreferenceFactory');

                $retval = array(
                    'date_stamp' => $upf->getOptions('date_format'),
                    'amount' => $upf->getOptions('time_unit_format'),
                );
                break;
        }

        return $retval;
    }


    public function _preParseRow($row_number, $raw_row)
    {
        $retval = $this->getObject()->stripReturnHandler($this->getObject()->getAccrualDefaultData());

        return $retval;
    }

    public function _postParseRow($row_number, $raw_row)
    {
        $raw_row['user_id'] = $this->getUserIdByRowData($raw_row);
        if ($raw_row['user_id'] == false) {
            unset($raw_row['user_id']);
        }

        if (isset($raw_row['date_stamp'])) {
            $raw_row['time_stamp'] = $raw_row['date_stamp']; //AcrualFactory wants time_stamp column not date_stamp, so convert that here.
        }

        return $raw_row;
    }

    public function _import($validate_only)
    {
        return $this->getObject()->setAccrual($this->getParsedData(), $validate_only);
    }

    //
    // Generic parser functions.
    //

    public function parse_accrual_policy_account($input, $default_value = null, $parse_hint = null)
    {
        if (trim($input) == '') {
            return 0; //Default Wage Group
        }

        if (!is_array($this->accrual_policy_account_options)) {
            $this->getAccrualPolicyAccountOptions();
        }

        $retval = $this->findClosestMatch($input, $this->accrual_policy_account_options);
        if ($retval === false) {
            $retval = -1; //Make sure this fails.
        }

        return $retval;
    }

    public function getAccrualPolicyAccountOptions()
    {
        //Get accrual policies
        $aplf = TTNew('AccrualPolicyAccountListFactory');
        $aplf->getByCompanyId($this->company_id);
        $this->accrual_policy_account_options = (array)$aplf->getArrayByListFactory($aplf, false, true);
        unset($aplf);

        return true;
    }

    public function parse_date_stamp($input, $default_value = null, $parse_hint = null)
    {
        return $this->parse_date($input, $default_value, $parse_hint);
    }

    public function parse_type($input, $default_value = null, $parse_hint = null)
    {
        $af = TTnew('AccrualFactory');
        $options = $af->getOptions('user_type');

        if (isset($options[$input])) {
            return $input;
        } else {
            if ($this->getImportOptions('fuzzy_match') == true) {
                return $this->findClosestMatch($input, $options, 50);
            } else {
                return array_search(strtolower($input), array_map('strtolower', $options));
            }
        }
    }

    public function parse_amount($input, $default_value = null, $parse_hint = null, $raw_row = null)
    {
        $val = new Validator();

        TTDate::setTimeUnitFormat($parse_hint);

        $retval = TTDate::parseTimeUnit($val->stripNonTimeUnit($input));

        return $retval;
    }
}
