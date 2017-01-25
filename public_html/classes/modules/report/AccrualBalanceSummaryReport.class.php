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
 * @package Modules\Report
 */
class AccrualBalanceSummaryReport extends Report
{
    public function __construct()
    {
        $this->title = TTi18n::getText('Accrual Balance Summary Report');
        $this->file_name = 'accrual_balance_summary_report';

        parent::__construct();

        return true;
    }

    public function _getData($format = null)
    {
        $this->tmp_data = array(
            'user' => array(),
            'user_wage' => array(),
            'accrual' => array(),
            'accrual_policy' => array(),
        );

        $columns = $this->getColumnDataConfig();
        $filter_data = $this->getFilterConfig();

        $currency_convert_to_base = $this->getCurrencyConvertToBase();
        $base_currency_obj = $this->getBaseCurrencyObject();
        $this->handleReportCurrency($currency_convert_to_base, $base_currency_obj, $filter_data);
        $currency_options = $this->getOptions('currency');

        $filter_data['permission_children_ids'] = $this->getPermissionObject()->getPermissionChildren('user', 'view', $this->getUserObject()->getID(), $this->getUserObject()->getCompany());
        $wage_permission_children_ids = $this->getPermissionObject()->getPermissionChildren('wage', 'view', $this->getUserObject()->getID(), $this->getUserObject()->getCompany());
        $accrual_permission_children_ids = $this->getPermissionObject()->getPermissionChildren('accrual', 'view', $this->getUserObject()->getID(), $this->getUserObject()->getCompany());

        //Get user data for joining.
        $ulf = TTnew('UserListFactory');
        $ulf->getAPISearchByCompanyIdAndArrayCriteria($this->getUserObject()->getCompany(), $filter_data);
        Debug::Text(' User Rows: ' . $ulf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
        $this->getProgressBarObject()->start($this->getAMFMessageID(), $ulf->getRecordCount(), null, TTi18n::getText('Retrieving Data...'));
        foreach ($ulf as $key => $u_obj) {
            $this->tmp_data['user'][$u_obj->getId()] = (array)$u_obj->getObjectAsArray(array_merge((array)$columns, array('hire_date' => true)));
            $this->tmp_data['user'][$u_obj->getId()]['user_status'] = Option::getByKey($u_obj->getStatus(), $u_obj->getOptions('status'));

            $this->tmp_data['user_wage'][$u_obj->getId()] = array();

            $this->getProgressBarObject()->set($this->getAMFMessageID(), $key);
        }
        //Debug::Arr($this->tmp_data['user'], 'TMP User Data: ', __FILE__, __LINE__, __METHOD__, 10);

        //Get user wage data for joining.
        $filter_data['wage_group_id'] = array(0); //Use default wage groups only.
        $uwlf = TTnew('UserWageListFactory');
        $uwlf->getAPILastWageSearchByCompanyIdAndArrayCriteria($this->getUserObject()->getCompany(), $filter_data);
        Debug::Text(' User Wage Rows: ' . $uwlf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
        $this->getProgressBarObject()->start($this->getAMFMessageID(), $ulf->getRecordCount(), null, TTi18n::getText('Retrieving Data...'));
        foreach ($uwlf as $key => $uw_obj) {
            if ($this->getPermissionObject()->isPermissionChild($uw_obj->getUser(), $wage_permission_children_ids)) {
                $this->tmp_data['user_wage'][$uw_obj->getUser()] = (array)$uw_obj->getObjectAsArray(array('hourly_rate' => true, 'current_currency' => true, 'effective_date' => true)); //Force specific columns, otherwise if hourly_rate is not included wage cant be calculated.

                if ($currency_convert_to_base == true and is_object($base_currency_obj)) {
                    $this->tmp_data['user_wage'][$uw_obj->getUser()]['current_currency'] = Option::getByKey($base_currency_obj->getId(), $currency_options);
                    if (isset($this->tmp_data['user'][$uw_obj->getUser()]['currency_rate'])) {
                        $this->tmp_data['user_wage'][$uw_obj->getUser()]['hourly_rate'] = $base_currency_obj->getBaseCurrencyAmount($uw_obj->getHourlyRate(), $this->tmp_data['user'][$uw_obj->getUser()]['currency_rate'], $currency_convert_to_base);
                    }
                }

                $this->tmp_data['user_wage'][$uw_obj->getUser()]['effective_date'] = (isset($this->tmp_data['user_wage'][$uw_obj->getUser()]['effective_date'])) ? TTDate::parseDateTime($this->tmp_data['user_wage'][$uw_obj->getUser()]['effective_date']) : null;
            }
            $this->getProgressBarObject()->set($this->getAMFMessageID(), $key);
        }
        //Debug::Arr($this->tmp_data['user_wage'], 'TMP User Wage Data: ', __FILE__, __LINE__, __METHOD__, 10);

        //Get accrual data for joining .
        $alf = TTnew('AccrualListFactory');
        $alf->getAPISearchByCompanyIdAndArrayCriteria($this->getUserObject()->getCompany(), $filter_data);
        Debug::Text(' Accrual Rows: ' . $alf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
        $this->getProgressBarObject()->start($this->getAMFMessageID(), $alf->getRecordCount(), null, TTi18n::getText('Retrieving Data...'));

        if (!isset($columns['date_stamp'])) { //Always include the date_stamp column so other date columns can be calculated.
            $columns['date_stamp'] = true;
        }
        foreach ($alf as $key => $a_obj) {
            if ($this->getPermissionObject()->isPermissionChild($a_obj->getUser(), $accrual_permission_children_ids)) {
                $tmp_data = (array)$a_obj->getObjectAsArray($columns);
                if (isset($tmp_data['amount'])) {
                    if ($tmp_data['amount'] < 0) {
                        $tmp_data['negative_amount'] = $tmp_data['amount'];
                    } else {
                        $tmp_data['positive_amount'] = $tmp_data['amount'];
                    }
                }
                $this->tmp_data['accrual'][$a_obj->getUser()][$a_obj->getAccrualPolicyAccount()][] = $tmp_data;
            }
            $this->getProgressBarObject()->set($this->getAMFMessageID(), $key);
        }
        unset($tmp_data);

        //Debug::Arr($this->tmp_data['accrual'], 'TMP Accrual Data: ', __FILE__, __LINE__, __METHOD__, 10);

        return true;
    }

    public function _preProcess()
    {
        $this->getProgressBarObject()->start($this->getAMFMessageID(), count($this->tmp_data['accrual']), null, TTi18n::getText('Pre-Processing Data...'));
        if (isset($this->tmp_data['user'])) {
            $key = 0;
            if (isset($this->tmp_data['accrual'])) {
                foreach ($this->tmp_data['accrual'] as $user_id => $level_2) {
                    if (isset($this->tmp_data['user'][$user_id])) {
                        foreach ($level_2 as $rows) {
                            foreach ($rows as $row) {
                                if (isset($row['date_stamp'])) {
                                    $date_columns = TTDate::getReportDates(null, TTDate::parseDateTime($row['date_stamp']), false, $this->getUserObject());
                                } else {
                                    $date_columns = array();
                                }

                                if (isset($this->tmp_data['user'][$user_id]['hire_date'])) {
                                    $hire_date_columns = TTDate::getReportDates('hire', TTDate::parseDateTime($this->tmp_data['user'][$user_id]['hire_date']), false, $this->getUserObject());
                                } else {
                                    $hire_date_columns = array();
                                }

                                if (!isset($this->tmp_data['user_wage'][$user_id])) {
                                    $this->tmp_data['user_wage'][$user_id] = array();
                                }

                                if (isset($row['amount']) and isset($this->tmp_data['user_wage'][$user_id]['hourly_rate'])) {
                                    $this->tmp_data['user_wage'][$user_id]['accrual_wage'] = bcmul(bcdiv($row['amount'], 3600), $this->tmp_data['user_wage'][$user_id]['hourly_rate']);
                                } else {
                                    $this->tmp_data['user_wage'][$user_id]['accrual_wage'] = null;
                                }

                                //Merge $row after user_wage so user_wage.type column isn't passed through.
                                $this->data[] = array_merge($this->tmp_data['user'][$user_id], $this->tmp_data['user_wage'][$user_id], $row, $date_columns, $hire_date_columns);
                            }
                        }
                    }

                    $this->getProgressBarObject()->set($this->getAMFMessageID(), $key);
                    $key++;
                }
            }
            unset($this->tmp_data, $row);
        }
        //Debug::Arr($this->data, 'preProcess Data: ', __FILE__, __LINE__, __METHOD__, 10);
        return true;
    }

    //Get raw data for report

    protected function _checkPermissions($user_id, $company_id)
    {
        if ($this->getPermissionObject()->Check('report', 'enabled', $user_id, $company_id)
            and $this->getPermissionObject()->Check('report', 'view_accrual_balance_summary', $user_id, $company_id)
        ) {
            return true;
        }

        return false;
    }

    //PreProcess data such as calculating additional columns from raw data etc...

    protected function _getOptions($name, $params = null)
    {
        $retval = null;
        switch ($name) {
            case 'output_format':
                $retval = parent::getOptions('default_output_format');
                break;
            case 'default_setup_fields':
                $retval = array(
                    'template',
                    'time_period',
                    'columns',
                );
                break;
            case 'setup_fields':
                $retval = array(
                    //Static Columns - Aggregate functions can't be used on these.
                    '-1000-template' => TTi18n::gettext('Template'),
                    '-1010-time_period' => TTi18n::gettext('Time Period'),
                    '-2010-user_status_id' => TTi18n::gettext('Employee Status'),
                    '-2020-user_group_id' => TTi18n::gettext('Employee Group'),
                    '-2030-user_title_id' => TTi18n::gettext('Employee Title'),
                    '-2035-user_tag' => TTi18n::gettext('Employee Tags'),
                    '-2040-include_user_id' => TTi18n::gettext('Employee Include'),
                    '-2050-exclude_user_id' => TTi18n::gettext('Employee Exclude'),
                    '-2060-default_branch_id' => TTi18n::gettext('Default Branch'),
                    '-2070-default_department_id' => TTi18n::gettext('Default Department'),
                    '-2100-custom_filter' => TTi18n::gettext('Custom Filter'),

                    '-3000-accrual_policy_account_id' => TTi18n::gettext('Accrual Account'),
                    '-3050-accrual_type_id' => TTi18n::gettext('Accrual Type'),
                    '-3080-accrual_policy_type_id' => TTi18n::gettext('Accrual Policy Type'),

                    //'-4020-include_no_data_rows' => TTi18n::gettext('Include Blank Records'),

                    '-5000-columns' => TTi18n::gettext('Display Columns'),
                    '-5010-group' => TTi18n::gettext('Group By'),
                    '-5020-sub_total' => TTi18n::gettext('SubTotal By'),
                    '-5030-sort' => TTi18n::gettext('Sort By'),
                );
                break;
            case 'time_period':
                $retval = TTDate::getTimePeriodOptions();
                break;
            case 'date_columns':
                $retval = array_merge(
                    TTDate::getReportDateOptions('hire', TTi18n::getText('Hire Date'), 13, false),
                    TTDate::getReportDateOptions(null, TTi18n::getText('Date'), 19, true)
                );
                break;
            case 'custom_columns':
                //Get custom fields for report data.
                $oflf = TTnew('OtherFieldListFactory');
                //User and Punch fields conflict as they are merged together in a secondary process.
                $other_field_names = $oflf->getByCompanyIdAndTypeIdArray($this->getUserObject()->getCompany(), array(10), array(10 => ''));
                if (is_array($other_field_names)) {
                    $retval = Misc::addSortPrefix($other_field_names, 9000);
                }
                break;
            case 'report_custom_column':
                break;
            case 'report_custom_filters':
                break;
            case 'report_dynamic_custom_column':
                break;
            case 'report_static_custom_column':
                break;
            case 'formula_columns':
                $retval = TTMath::formatFormulaColumns(array_merge(array_diff($this->getOptions('static_columns'), (array)$this->getOptions('report_static_custom_column')), $this->getOptions('dynamic_columns')));
                break;
            case 'filter_columns':
                $retval = TTMath::formatFormulaColumns(array_merge($this->getOptions('static_columns'), $this->getOptions('dynamic_columns'), (array)$this->getOptions('report_dynamic_custom_column')));
                break;
            case 'static_columns':
                $retval = array(
                    //Static Columns - Aggregate functions can't be used on these.
                    '-1000-first_name' => TTi18n::gettext('First Name'),
                    '-1001-middle_name' => TTi18n::gettext('Middle Name'),
                    '-1002-last_name' => TTi18n::gettext('Last Name'),
                    '-1005-full_name' => TTi18n::gettext('Full Name'),

                    '-1010-user_name' => TTi18n::gettext('User Name'),
                    '-1020-phone_id' => TTi18n::gettext('Quick Punch ID'),

                    '-1030-employee_number' => TTi18n::gettext('Employee #'),

                    '-1040-user_status' => TTi18n::gettext('Employee Status'),
                    '-1050-title' => TTi18n::gettext('Employee Title'),
                    '-1060-province' => TTi18n::gettext('Province/State'),
                    '-1070-country' => TTi18n::gettext('Country'),
                    '-1080-user_group' => TTi18n::gettext('Employee Group'),
                    '-1090-default_branch' => TTi18n::gettext('Branch'), //abbreviate for space
                    '-1100-default_department' => TTi18n::gettext('Department'), //abbreviate for space

                    '-1110-currency' => TTi18n::gettext('Currency'),
                    '-1112-current_currency' => TTi18n::gettext('Current Currency'),

                    '-1399-hire_date_age' => TTi18n::gettext('Length of Service'),

                    '-1820-accrual_policy_account' => TTi18n::gettext('Accrual Account'),
                    '-1830-type' => TTi18n::gettext('Accrual Type'),
                    //'-1160-date_stamp' => TTi18n::gettext('Date'), //Date stamp is combination of time_stamp and user_date.date_stamp columns.

                    '-1850-accrual_policy' => TTi18n::gettext('Accrual Policy'),
                    '-1860-accrual_policy_type' => TTi18n::gettext('Accrual Policy Type'),

                    '-3010-note' => TTi18n::gettext('Accrual Note'),
                );

                $retval = array_merge($retval, (array)$this->getOptions('date_columns'), (array)$this->getOptions('custom_columns'), (array)$this->getOptions('report_static_custom_column'));
                ksort($retval);
                break;
            case 'dynamic_columns':
                $retval = array(
                    //Dynamic - Aggregate functions can be used
                    '-2020-positive_amount' => TTi18n::gettext('Time Accrued'),
                    '-2022-negative_amount' => TTi18n::gettext('Time Taken'),

                    '-2050-amount' => TTi18n::gettext('Accrual Time'),
                    //'-2120-running_total_amount' => TTi18n::gettext('Running Total'), //Need to handle this in an aggregate?

                    '-2635-hourly_rate' => TTi18n::gettext('Hourly Rate'),
                    '-2640-accrual_wage' => TTi18n::gettext('Accrual Wage'),
                );

                break;
            case 'columns':
                $retval = array_merge($this->getOptions('static_columns'), $this->getOptions('dynamic_columns'), (array)$this->getOptions('report_dynamic_custom_column'));
                ksort($retval);
                break;
            case 'column_format':
                //Define formatting function for each column.
                $columns = array_merge($this->getOptions('dynamic_columns'), (array)$this->getOptions('report_custom_column'));
                if (is_array($columns)) {
                    foreach ($columns as $column => $name) {
                        if (strpos($column, 'wage') !== false or strpos($column, 'hourly_rate') !== false) {
                            $retval[$column] = 'currency';
                        }
                        if (strpos($column, 'amount') !== false) {
                            $retval[$column] = 'time_unit';
                        }
                    }
                }
                break;
            case 'aggregates':
                $retval = array();
                $dynamic_columns = array_keys(Misc::trimSortPrefix(array_merge($this->getOptions('dynamic_columns'), (array)$this->getOptions('report_dynamic_custom_column'))));
                if (is_array($dynamic_columns)) {
                    foreach ($dynamic_columns as $column) {
                        switch ($column) {
                            default:
                                if (strpos($column, 'hourly_rate') !== false) {
                                    $retval[$column] = 'avg';
                                } else {
                                    $retval[$column] = 'sum';
                                }
                        }
                    }
                }
                break;
            case 'templates':
                $retval = array(
                    '-1250-by_policy+accrual' => TTi18n::gettext('Accruals By Account'),
                    '-1260-by_type+accrual' => TTi18n::gettext('Accruals By Type'),
                    '-1270-by_type_by_employee+accrual' => TTi18n::gettext('Accruals By Type/Employee'),
                    '-1275-by_policy_by_employee+accrual' => TTi18n::gettext('Accruals By Account/Employee'),
                    '-1280-by_policy_by_type_by_employee+accrual' => TTi18n::gettext('Accruals By Account/Type/Employee'),
                    '-1290-by_employee_by_date+accrual' => TTi18n::gettext('Accruals By Account/Type/Employee/Date'),
                    '-1300-by_date+accrual' => TTi18n::gettext('Accruals By Account/Type/Date'),

                    '-1320-overall_balance_to_date' => TTi18n::gettext('Overall Balance To Date'),
                    '-1350-overall_balance' => TTi18n::gettext('Overall Balance'),
                );

                break;
            case 'template_config':
                $template = strtolower(Misc::trimSortPrefix($params['template']));
                if (isset($template) and $template != '') {
                    $retval['-1010-time_period']['time_period'] = 'all_years';

                    switch ($template) {
                        case 'by_policy+accrual':
                            $retval['columns'][] = 'accrual_policy_account';

                            $retval['columns'][] = 'type';
                            $retval['columns'][] = 'amount';

                            $retval['group'][] = 'accrual_policy_account';
                            $retval['sort'][] = array('accrual_policy_account' => 'asc');
                            break;
                        case 'by_type+accrual':
                            $retval['columns'][] = 'type';

                            $retval['columns'][] = 'accrual_policy_account';
                            $retval['columns'][] = 'amount';

                            $retval['group'][] = 'type';

                            $retval['sort'][] = array('type' => 'asc');
                            break;
                        case 'by_type_by_employee+accrual':
                            $retval['columns'][] = 'type';
                            $retval['columns'][] = 'first_name';
                            $retval['columns'][] = 'last_name';

                            $retval['columns'][] = 'accrual_policy_account';
                            $retval['columns'][] = 'amount';

                            $retval['group'][] = 'type';
                            $retval['group'][] = 'first_name';
                            $retval['group'][] = 'last_name';

                            $retval['sub_total'][] = 'type';

                            $retval['sort'][] = array('type' => 'asc');
                            $retval['sort'][] = array('last_name' => 'asc');
                            $retval['sort'][] = array('first_name' => 'asc');
                            break;
                        case 'by_policy_by_employee+accrual':
                            $retval['columns'][] = 'accrual_policy_account';
                            $retval['columns'][] = 'first_name';
                            $retval['columns'][] = 'last_name';

                            $retval['columns'][] = 'amount';

                            $retval['group'][] = 'accrual_policy_account';
                            $retval['group'][] = 'last_name';
                            $retval['group'][] = 'first_name';

                            $retval['sub_total'][] = 'accrual_policy_account';

                            $retval['sort'][] = array('accrual_policy_account' => 'asc');
                            $retval['sort'][] = array('last_name' => 'asc');
                            $retval['sort'][] = array('first_name' => 'asc');
                            break;
                        case 'by_policy_by_type_by_employee+accrual':
                            $retval['columns'][] = 'accrual_policy_account';
                            $retval['columns'][] = 'type';
                            $retval['columns'][] = 'first_name';
                            $retval['columns'][] = 'last_name';

                            $retval['columns'][] = 'amount';

                            $retval['group'][] = 'accrual_policy_account';
                            $retval['group'][] = 'type';
                            $retval['group'][] = 'last_name';
                            $retval['group'][] = 'first_name';

                            $retval['sub_total'][] = 'accrual_policy_account';
                            $retval['sub_total'][] = 'type';

                            $retval['sort'][] = array('accrual_policy_account' => 'asc');
                            $retval['sort'][] = array('type' => 'asc');
                            $retval['sort'][] = array('last_name' => 'asc');
                            $retval['sort'][] = array('first_name' => 'asc');
                            break;
                        case 'by_date+accrual':
                            $retval['columns'][] = 'accrual_policy_account';
                            $retval['columns'][] = 'type';
                            $retval['columns'][] = 'date_stamp';

                            $retval['columns'][] = 'amount';

                            $retval['group'][] = 'accrual_policy_account';
                            $retval['group'][] = 'type';
                            $retval['group'][] = 'date_stamp';

                            $retval['sub_total'][] = 'accrual_policy_account';
                            $retval['sub_total'][] = 'type';

                            $retval['sort'][] = array('accrual_policy_account' => 'asc');
                            $retval['sort'][] = array('type' => 'asc');
                            $retval['sort'][] = array('date_stamp' => 'asc');
                            break;
                        case 'by_employee_by_date+accrual':
                            $retval['columns'][] = 'accrual_policy_account';
                            $retval['columns'][] = 'type';
                            $retval['columns'][] = 'first_name';
                            $retval['columns'][] = 'last_name';
                            $retval['columns'][] = 'date_stamp';

                            $retval['columns'][] = 'amount';

                            $retval['group'][] = 'accrual_policy_account';
                            $retval['group'][] = 'type';
                            $retval['group'][] = 'first_name';
                            $retval['group'][] = 'last_name';
                            $retval['group'][] = 'date_stamp';

                            $retval['sub_total'][] = 'type';

                            $retval['sort'][] = array('accrual_policy_account' => 'asc');
                            $retval['sort'][] = array('type' => 'asc');
                            $retval['sort'][] = array('last_name' => 'asc');
                            $retval['sort'][] = array('first_name' => 'asc');
                            $retval['sort'][] = array('date_stamp' => 'asc');
                            break;
                        case 'overall_balance_to_date':
                            $retval['-1010-time_period']['time_period'] = 'to_today';

                            $retval['columns'][] = 'full_name';

                            $retval['columns'][] = 'accrual_policy_account';
                            $retval['columns'][] = 'amount';

                            $retval['group'][] = 'full_name';
                            $retval['group'][] = 'accrual_policy_account';

                            $retval['sub_total'][] = 'full_name';

                            $retval['sort'][] = array('full_name' => 'asc');
                            break;
                        case 'overall_balance':
                            $retval['-1010-time_period']['time_period'] = 'all_years';

                            $retval['columns'][] = 'full_name';

                            $retval['columns'][] = 'accrual_policy_account';
                            $retval['columns'][] = 'amount';

                            $retval['group'][] = 'full_name';
                            $retval['group'][] = 'accrual_policy_account';

                            $retval['sub_total'][] = 'full_name';

                            $retval['sort'][] = array('full_name' => 'asc');
                            break;
                        default:
                            Debug::Text(' Parsing template name: ' . $template, __FILE__, __LINE__, __METHOD__, 10);
                            break;
                    }
                }

                //Set the template dropdown as well.
                $retval['-1000-template'] = $template;

                //Add sort prefixes so Flex can maintain order.
                if (isset($retval['filter'])) {
                    $retval['-5000-filter'] = $retval['filter'];
                    unset($retval['filter']);
                }
                if (isset($retval['columns'])) {
                    $retval['-5010-columns'] = $retval['columns'];
                    unset($retval['columns']);
                }
                if (isset($retval['group'])) {
                    $retval['-5020-group'] = $retval['group'];
                    unset($retval['group']);
                }
                if (isset($retval['sub_total'])) {
                    $retval['-5030-sub_total'] = $retval['sub_total'];
                    unset($retval['sub_total']);
                }
                if (isset($retval['sort'])) {
                    $retval['-5040-sort'] = $retval['sort'];
                    unset($retval['sort']);
                }
                Debug::Arr($retval, ' Template Config for: ' . $template, __FILE__, __LINE__, __METHOD__, 10);

                break;
            default:
                //Call report parent class options function for options valid for all reports.
                $retval = $this->__getOptions($name);
                break;
        }

        return $retval;
    }
}
