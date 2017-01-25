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
 * @package Core
 */
class CurrencyRateFactory extends Factory
{
    protected $table = 'currency_rate';
    protected $pk_sequence_name = 'currency_rate_id_seq'; //PK Sequence name

    protected $currency_obj = null;

    public function _getFactoryOptions($name, $parent = null)
    {
        $retval = null;
        switch ($name) {
            case 'columns':
                $retval = array(
                    //'-1010-iso_code' => TTi18n::gettext('ISO Code'),
                    '-1020-date_stamp' => TTi18n::gettext('Date'),
                    '-1030-conversion_rate' => TTi18n::gettext('Conversion Rate'),

                    '-2000-created_by' => TTi18n::gettext('Created By'),
                    '-2010-created_date' => TTi18n::gettext('Created Date'),
                    '-2020-updated_by' => TTi18n::gettext('Updated By'),
                    '-2030-updated_date' => TTi18n::gettext('Updated Date'),
                );
                break;
            case 'list_columns':
                $retval = Misc::arrayIntersectByKey(array('date_stamp', 'conversion_rate'), Misc::trimSortPrefix($this->getOptions('columns')));
                break;
            case 'default_display_columns': //Columns that are displayed by default.
                $retval = array(
                    'date_stamp',
                    'conversion_rate',
                );
                break;
            case 'unique_columns': //Columns that are unique, and disabled for mass editing.
                $retval = array(
                    'date_stamp',
                );
                break;

        }

        return $retval;
    }

    public function _getVariableToFunctionMap($data)
    {
        $variable_function_map = array(
            'id' => 'ID',
            'currency_id' => 'Currency',
            //'status_id' => FALSE,
            //'status' => FALSE,
            //'name' => FALSE,
            //'symbol' => FALSE,
            //'iso_code' => FALSE,
            'date_stamp' => 'DateStamp',
            'conversion_rate' => 'ConversionRate',
            'deleted' => 'Deleted',
        );
        return $variable_function_map;
    }

    public function setCurrency($id)
    {
        $id = trim($id);

        Debug::Text('Currency ID: ' . $id, __FILE__, __LINE__, __METHOD__, 10);
        $culf = TTnew('CurrencyListFactory');

        if (
        $this->Validator->isResultSetWithRows('currency_id',
            $culf->getByID($id),
            TTi18n::gettext('Invalid Currency')
        )
        ) {
            $this->data['currency_id'] = $id;

            return true;
        }

        return false;
    }

    public function setDateStamp($epoch)
    {
        $epoch = (!is_int($epoch)) ? trim($epoch) : $epoch; //Dont trim integer values, as it changes them to strings.

        if ($epoch != ''
            and
            $this->Validator->isDate('date_stamp',
                $epoch,
                TTi18n::gettext('Incorrect date'))
        ) {
            $this->data['date_stamp'] = $epoch;

            return true;
        }

        return false;
    }

    public function getReverseConversionRate()
    {
        $rate = $this->getConversionRate();
        if ($rate != 0) { //Prevent division by 0.
            return bcdiv(1, $rate);
        }

        return false;
    }

    public function getConversionRate()
    {
        if (isset($this->data['conversion_rate'])) {
            return $this->data['conversion_rate']; //Don't cast to (float) as it may strip some precision.
        }

        return false;
    }

    public function setConversionRate($value)
    {
        //Pull out only digits and periods.
        $value = $this->Validator->stripNonFloat($value);

        if ($this->Validator->isTrue('conversion_rate',
                $value,
                TTi18n::gettext('Conversion rate not specified'))
            and $this->Validator->isFloat('conversion_rate',
                $value,
                TTi18n::gettext('Incorrect Conversion Rate'))
            and $this->Validator->isLessThan('conversion_rate',
                $value,
                TTi18n::gettext('Conversion Rate is too high'),
                99999999)
            and $this->Validator->isGreaterThan('conversion_rate',
                $value,
                TTi18n::gettext('Conversion Rate is too low'),
                -99999999)
        ) {
            $this->data['conversion_rate'] = $value;

            return true;
        }

        return false;
    }

    public function Validate($ignore_warning = true)
    {
        if ($this->getDeleted() == false) {
            if ($this->Validator->getValidateOnly() == false and $this->getDateStamp() == false) {
                $this->Validator->isTrue('date_stamp',
                    false,
                    TTi18n::gettext('Date not specified'));
            } elseif ($this->Validator->getValidateOnly() == false and $this->isUnique() == false) {
                $this->Validator->isTrue('date_stamp',
                    false,
                    TTi18n::gettext('Currency rate already exists for this date'));
            }

            if ($this->getConversionRate() == false and $this->Validator->hasError('conversion_rate') == false) {
                $this->Validator->isTrue('conversion_rate',
                    false,
                    TTi18n::gettext('Conversion rate not specified'));
            }
        }

        return true;
    }

    public function getDateStamp($raw = false)
    {
        if (isset($this->data['date_stamp'])) {
            if ($raw === true) {
                return $this->data['date_stamp'];
            } else {
                //return $this->db->UnixTimeStamp( $this->data['start_date'] );
                //strtotime is MUCH faster than UnixTimeStamp
                //Must use ADODB for times pre-1970 though.
                return TTDate::strtotime($this->data['date_stamp']);
            }
        }

        return false;
    }

    public function isUnique()
    {
        $ph = array(
            'currency_id' => (int)$this->getCurrency(),
            'date_stamp' => $this->db->BindDate($this->getDateStamp()),
        );

        $query = 'select id from ' . $this->getTable() . ' where currency_id = ? AND date_stamp = ?';
        $id = $this->db->GetOne($query, $ph);
        Debug::Arr($id, 'Unique Currency Rate: ' . $id, __FILE__, __LINE__, __METHOD__, 10);

        if ($id === false) {
            return true;
        } else {
            if ($id == $this->getId()) {
                return true;
            }
        }

        return false;
    }

    public function getCurrency()
    {
        if (isset($this->data['currency_id'])) {
            return (int)$this->data['currency_id'];
        }

        return false;
    }

    public function preSave()
    {
        return true;
    }

    public function postSave()
    {
        $this->removeCache($this->getId());

        return true;
    }

    public function setObjectFromArray($data)
    {
        if (is_array($data)) {
            $variable_function_map = $this->getVariableToFunctionMap();

            foreach ($variable_function_map as $key => $function) {
                if (isset($data[$key])) {
                    $function = 'set' . $function;
                    switch ($key) {
                        case 'date_stamp':
                            $this->$function(TTDate::parseDateTime($data[$key]));
                            break;
//						case 'conversion_rate':
//							$this->$function( TTi18n::parseFloat( $data[$key] ) );
//							break;
                        default:
                            if (method_exists($this, $function)) {
                                $this->$function($data[$key]);
                            }
                            break;
                    }
                }
            }

            $this->setCreatedAndUpdatedColumns($data);

            return true;
        }

        return false;
    }

    //Support setting created_by, updated_by especially for importing data.
    //Make sure data is set based on the getVariableToFunctionMap order.

    public function getObjectAsArray($include_columns = null)
    {
        /*
        $include_columns = array(
                                'id' => TRUE,
                                'company_id' => TRUE,
                                ...
                                )

        */
        $data = array();
        $variable_function_map = $this->getVariableToFunctionMap();
        if (is_array($variable_function_map)) {
            foreach ($variable_function_map as $variable => $function_stub) {
                if ($include_columns == null or (isset($include_columns[$variable]) and $include_columns[$variable] == true)) {
                    $function = 'get' . $function_stub;
                    switch ($variable) {
                        case 'date_stamp':
                            $data[$variable] = $this->$function(true);
                            break;
//						case 'conversion_rate':
//							$data[$variable] = TTi18n::formatNumber( $this->$function(), TRUE, 10, 10 );
//							break;
                        default:
                            if (method_exists($this, $function)) {
                                $data[$variable] = $this->$function();
                            }
                            break;
                    }
                }
            }
            $this->getCreatedAndUpdatedColumns($data, $include_columns);
        }

        return $data;
    }

    public function addLog($log_action)
    {
        return TTLog::addEntry($this->getId(), $log_action, TTi18n::getText('Currency Rate') . ': ' . $this->getCurrencyObject()->getISOCode() . ' ' . TTi18n::getText('Rate') . ': ' . $this->getConversionRate(), null, $this->getTable(), $this);
    }

    public function getCurrencyObject()
    {
        return $this->getGenericObject('CurrencyListFactory', $this->getCurrency(), 'currency_obj');
    }
}
