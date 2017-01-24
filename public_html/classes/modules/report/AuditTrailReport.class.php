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
 * @package Modules\Report
 */
class AuditTrailReport extends Report
{
    public function __construct()
    {
        $this->title = TTi18n::getText('Audit Trail Report');
        $this->file_name = 'audit_trail_report';

        parent::__construct();

        return true;
    }

    public function _getData($format = null)
    {
        $this->tmp_data = array(
            'user' => array(),
            'log' => array(),
        );

        $columns = $this->getColumnDataConfig();
        $filter_data = $this->getFilterConfig();

        $filter_data['permission_children_ids'] = $this->getPermissionObject()->getPermissionChildren('user', 'view', $this->getUserObject()->getID(), $this->getUserObject()->getCompany());

        //Get user data for joining.
        $ulf = TTnew('UserListFactory');
        $ulf->getAPISearchByCompanyIdAndArrayCriteria($this->getUserObject()->getCompany(), $filter_data);
        Debug::Text(' User Rows: ' . $ulf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
        $this->getProgressBarObject()->start($this->getAMFMessageID(), $ulf->getRecordCount(), null, TTi18n::getText('Retrieving Data...'));
        foreach ($ulf as $key => $u_obj) {
            $this->tmp_data['user'][$u_obj->getId()] = (array)$u_obj->getObjectAsArray($columns);
            $this->tmp_data['user'][$u_obj->getId()]['user_status'] = Option::getByKey($u_obj->getStatus(), $u_obj->getOptions('status'));
            $this->getProgressBarObject()->set($this->getAMFMessageID(), $key);
        }

        //Debug::Arr($this->tmp_data['user'], 'TMP User Data: ', __FILE__, __LINE__, __METHOD__, 10);

        //Get system log data for joining.
        if (count($this->tmp_data['user']) > 0) {
            $filter_data['user_id'] = array_keys($this->tmp_data['user']); //Filter only selected users, otherwise too many rows can be returned that wont be displayed.

            $llf = TTnew('LogListFactory');
            $llf->getSearchByCompanyIdAndArrayCriteria($this->getUserObject()->getCompany(), $filter_data, 5000);

            Debug::Text(' Log Rows: ' . $llf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
            $this->getProgressBarObject()->start($this->getAMFMessageID(), $llf->getRecordCount(), null, TTi18n::getText('Retrieving Data...'));
            foreach ($llf as $key => $l_obj) {
                $this->tmp_data['log'][$l_obj->getUser()][] = array_merge((array)$l_obj->getObjectAsArray($columns), array('total_log' => 1));

                $this->getProgressBarObject()->set($this->getAMFMessageID(), $key);
            }
            //Debug::Arr($this->tmp_data['log'], 'TMP Log Data: ', __FILE__, __LINE__, __METHOD__, 10);
        }

        return true;
    }

    public function _preProcess()
    {
        $this->getProgressBarObject()->start($this->getAMFMessageID(), count($this->tmp_data['log']), null, TTi18n::getText('Pre-Processing Data...'));
        if (isset($this->tmp_data['user'])) {
            $key = 0;
            if (isset($this->tmp_data['log'])) {
                foreach ($this->tmp_data['log'] as $user_id => $level_2) {
                    if (isset($this->tmp_data['user'][$user_id])) {
                        foreach ($level_2 as $row) {
                            $this->data[] = array_merge($row, $this->tmp_data['user'][$user_id]);
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

    protected function _checkPermissions($user_id, $company_id)
    {
        if ($this->getPermissionObject()->Check('report', 'enabled', $user_id, $company_id)
            and $this->getPermissionObject()->Check('report', 'view_system_log', $user_id, $company_id)
        ) {
            return true;
        }

        return false;
    }

    //Get raw data for report

    protected function _validateConfig()
    {
        $config = $this->getConfig();

        //Make sure some time period is selected.
        if (!isset($config['filter']['time_period']) and !isset($config['filter']['pay_period_id'])) {
            $this->validator->isTrue('time_period', false, TTi18n::gettext('No time period defined for this report'));
        }

        return true;
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

                    //'-3500-pay_period_id' => TTi18n::gettext('Pay Period'),
                    '-3600-log_action_id' => TTi18n::gettext('Action'),
                    '-3700-log_table_name_id' => TTi18n::gettext('Object'),

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
                /*$retval = array_merge(
                                    TTDate::getReportDateOptions( 'start', TTi18n::getText('Start Date'), 16, FALSE ),
                                    TTDate::getReportDateOptions( 'end', TTi18n::getText('End Date'), 17, FALSE )
                                );*/
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

                    '-2000-date' => TTi18n::gettext('Date'),
                    '-2100-object' => TTi18n::gettext('Object'),
                    '-2150-action' => TTi18n::gettext('Action'),
                    '-2200-description' => TTi18n::gettext('Description'),
                    //'-2250-function' => TTi18n::gettext('Functions'),

                );

                //$retval = array_merge( $retval, $this->getOptions('date_columns') );
                $retval = array_merge($retval, (array)$this->getOptions('report_static_custom_column'));
                ksort($retval);
                break;
            case 'dynamic_columns':
                $retval = array(
                    //Dynamic - Aggregate functions can be used
                    '-2500-total_log' => TTi18n::gettext('Total'), //Group counter...
                );

                break;
            case 'columns':
                //$retval = array_merge( $this->getOptions('static_columns'), $this->getOptions('dynamic_columns') );
                $retval = array_merge($this->getOptions('static_columns'), $this->getOptions('dynamic_columns'), (array)$this->getOptions('report_dynamic_custom_column'));
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
                                $retval[$column] = 'sum';
                        }
                    }
                }
                break;
            case 'templates':
                $retval = array(
                    '-1200-by_date+audit' => TTi18n::gettext('Audit By Date'),
                    '-1210-by_employee+audit' => TTi18n::gettext('Audit By Employee'),
                    '-1220-by_object+audit' => TTi18n::gettext('Audit By Object'),
                    '-1230-by_action+audit' => TTi18n::gettext('Audit By Action'),
                    '-1240-by_object_by_action_by_employee+audit_total' => TTi18n::gettext('Audit Records By Object/Action/Employee'),
                );

                break;
            case 'template_config':
                $template = strtolower(Misc::trimSortPrefix($params['template']));
                if (isset($template) and $template != '') {
                    $retval['-1010-time_period']['time_period'] = 'last_7_days'; //Always default to the last 7 days to keep the report small and fast.

                    switch ($template) {
                        case 'by_date+audit':
                            $retval['columns'][] = 'date';

                            $retval['columns'][] = 'first_name';
                            $retval['columns'][] = 'last_name';

                            $retval['columns'][] = 'object';
                            $retval['columns'][] = 'action';
                            $retval['columns'][] = 'description';

                            $retval['sort'][] = array('date' => 'desc');
                            $retval['sort'][] = array('last_name' => 'asc');
                            $retval['sort'][] = array('first_name' => 'asc');
                            $retval['sort'][] = array('object' => 'asc');
                            $retval['sort'][] = array('action' => 'asc');
                            break;
                        case 'by_employee+audit':
                            $retval['columns'][] = 'first_name';
                            $retval['columns'][] = 'last_name';

                            $retval['columns'][] = 'date';
                            $retval['columns'][] = 'object';
                            $retval['columns'][] = 'action';
                            $retval['columns'][] = 'description';

                            $retval['sort'][] = array('last_name' => 'asc');
                            $retval['sort'][] = array('first_name' => 'asc');
                            $retval['sort'][] = array('date' => 'desc');
                            $retval['sort'][] = array('object' => 'asc');
                            $retval['sort'][] = array('action' => 'asc');
                            break;
                        case 'by_object+audit':
                            $retval['columns'][] = 'object';

                            $retval['columns'][] = 'date';

                            $retval['columns'][] = 'first_name';
                            $retval['columns'][] = 'last_name';

                            $retval['columns'][] = 'action';
                            $retval['columns'][] = 'description';

                            $retval['sort'][] = array('object' => 'asc');
                            $retval['sort'][] = array('date' => 'desc');
                            $retval['sort'][] = array('last_name' => 'asc');
                            $retval['sort'][] = array('first_name' => 'asc');
                            $retval['sort'][] = array('action' => 'asc');

                            break;
                        case 'by_action+audit':
                            $retval['columns'][] = 'action';
                            $retval['columns'][] = 'date';

                            $retval['columns'][] = 'first_name';
                            $retval['columns'][] = 'last_name';

                            $retval['columns'][] = 'object';
                            $retval['columns'][] = 'description';

                            $retval['sort'][] = array('action' => 'asc');
                            $retval['sort'][] = array('date' => 'desc');
                            $retval['sort'][] = array('last_name' => 'asc');
                            $retval['sort'][] = array('first_name' => 'asc');
                            $retval['sort'][] = array('object' => 'asc');

                            //$retval['filter']['-1050-log_action_id'] = array();
                            break;
                        case 'by_object_by_action_by_employee+audit_total':
                            $retval['columns'][] = 'object';
                            $retval['columns'][] = 'action';
                            $retval['columns'][] = 'first_name';
                            $retval['columns'][] = 'last_name';
                            $retval['columns'][] = 'total_log';

                            $retval['group'][] = 'object';
                            $retval['group'][] = 'action';
                            $retval['group'][] = 'first_name';
                            $retval['group'][] = 'last_name';

                            $retval['sub_total'][] = 'object';
                            $retval['sub_total'][] = 'action';

                            $retval['sort'][] = array('object' => 'asc');
                            $retval['sort'][] = array('action' => 'asc');
                            $retval['sort'][] = array('total_log' => 'desc');
                            $retval['sort'][] = array('last_name' => 'asc');
                            $retval['sort'][] = array('first_name' => 'asc');
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
