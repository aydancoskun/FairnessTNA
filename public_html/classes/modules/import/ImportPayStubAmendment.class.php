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
class ImportPayStubAmendment extends Import
{
    public $class_name = 'APIPayStubAmendment';

    public $pay_stub_account_options = false;

    public function _getFactoryOptions($name, $parent = null)
    {
        $retval = null;
        switch ($name) {
            case 'columns':
                $psaf = TTNew('PayStubAmendmentFactory');
                $retval = Misc::prependArray($this->getUserIdentificationColumns(), Misc::arrayIntersectByKey(array('status', 'type', 'pay_stub_entry_name', 'effective_date', 'amount', 'rate', 'units', 'description', 'ytd_adjustment'), Misc::trimSortPrefix($psaf->getOptions('columns'))));

                break;
            case 'column_aliases':
                //Used for converting column names after they have been parsed.
                $retval = array(
                    'status' => 'status_id',
                    'type' => 'type_id',
                    'pay_stub_entry_name' => 'pay_stub_entry_name_id',
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
                    //'amount' => $upf->getOptions('time_unit_format'),
                );
                break;
        }

        return $retval;
    }


    public function _preParseRow($row_number, $raw_row)
    {
        $retval = $this->getObject()->stripReturnHandler($this->getObject()->getPayStubAmendmentDefaultData());

        return $retval;
    }

    public function _postParseRow($row_number, $raw_row)
    {
        $raw_row['user_id'] = $this->getUserIdByRowData($raw_row);
        if ($raw_row['user_id'] == false) {
            unset($raw_row['user_id']);
        }

        return $raw_row;
    }

    public function _import($validate_only)
    {
        return $this->getObject()->setPayStubAmendment($this->getParsedData(), $validate_only);
    }

    //
    // Generic parser functions.
    //

    public function parse_pay_stub_entry_name($input, $default_value = null, $parse_hint = null)
    {
        if (trim($input) == '') {
            return 0; //Default Wage Group
        }

        if (!is_array($this->pay_stub_account_options)) {
            $this->getPayStubAccountOptions();
        }

        $retval = $this->findClosestMatch($input, $this->pay_stub_account_options);
        //Debug::Arr( $this->pay_stub_account_options, 'aAttempting to find PS Account with long name: '. $input, __FILE__, __LINE__, __METHOD__, 10);
        if ($retval === false) {
            $retval = $this->findClosestMatch($input, $this->pay_stub_account_short_options);
            //Debug::Arr( $this->pay_stub_account_short_options, 'bAttempting to find PS Account with short name: '. $input, __FILE__, __LINE__, __METHOD__, 10);
            if ($retval === false) {
                $retval = -1; //Make sure this fails.
            }
        }

        return $retval;
    }

    public function getPayStubAccountOptions()
    {
        //Get accrual policies
        $psealf = TTNew('PayStubEntryAccountListFactory');
        $psealf->getByCompanyIdAndTypeId($this->company_id, array(10, 20, 30, 50, 80));

        //Get names with types in front, ie: "Earning - Commission"
        $this->pay_stub_account_options = (array)$psealf->getArrayByListFactory($psealf, false, true, true);

        //Get names without types in front, ie: "Commission"
        $this->pay_stub_account_short_options = (array)$psealf->getArrayByListFactory($psealf, false, true, false, false);
        unset($psealf);

        return true;
    }

    public function parse_effective_date($input, $default_value = null, $parse_hint = null)
    {
        return $this->parse_date($input, $default_value, $parse_hint);
    }

    public function parse_status($input, $default_value = null, $parse_hint = null)
    {
        $psaf = TTnew('PayStubAmendmentFactory');
        $options = Misc::trimSortPrefix($psaf->getOptions('status'));

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

    public function parse_type($input, $default_value = null, $parse_hint = null)
    {
        $psaf = TTnew('PayStubAmendmentFactory');
        $options = Misc::trimSortPrefix($psaf->getOptions('type'));

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
        $retval = $val->stripNonFloat($input);

        return $retval;
    }

    public function parse_rate($input, $default_value = null, $parse_hint = null, $raw_row = null)
    {
        $val = new Validator();
        $retval = $val->stripNonFloat($input);

        return $retval;
    }

    public function parse_units($input, $default_value = null, $parse_hint = null, $raw_row = null)
    {
        $val = new Validator();
        $retval = $val->stripNonFloat($input);

        return $retval;
    }
}
