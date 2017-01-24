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
class PayStubSummaryReport extends Report
{
    public function __construct()
    {
        $this->title = TTi18n::getText('Pay Stub Summary Report');
        $this->file_name = 'paystub_summary_report';

        parent::__construct();

        return true;
    }

    public function _getData($format = null)
    {
        $this->tmp_data = array('pay_stub_entry' => array(), 'user' => array());

        $filter_data = $this->getFilterConfig();

        $currency_convert_to_base = $this->getCurrencyConvertToBase();
        $base_currency_obj = $this->getBaseCurrencyObject();
        $this->handleReportCurrency($currency_convert_to_base, $base_currency_obj, $filter_data);
        $currency_options = $this->getOptions('currency');

        //Don't need to process data unless we're preparing the report.
        $psf = TTnew('PayStubFactory');
        $export_type_options = Misc::trimSortPrefix($psf->getOptions('export_type'));
        if (isset($export_type_options[$format])) {
            Debug::Text('Skipping data retrieval for format: ' . $format, __FILE__, __LINE__, __METHOD__, 10);
            return true;
        }

        $filter_data['permission_children_ids'] = $this->getPermissionObject()->getPermissionChildren('pay_stub', 'view', $this->getUserObject()->getID(), $this->getUserObject()->getCompany());

        $psf = TTnew('PayStubFactory'); //For getOptions() below.

        $pself = TTnew('PayStubEntryListFactory');
        $pself->getAPIReportByCompanyIdAndArrayCriteria($this->getUserObject()->getCompany(), $filter_data);
        $this->getProgressBarObject()->start($this->getAMFMessageID(), $pself->getRecordCount(), null, TTi18n::getText('Retrieving Data...'));
        if ($pself->getRecordCount() > 0) {
            foreach ($pself as $key => $pse_obj) {
                $user_id = $pse_obj->getColumn('user_id');
                $date_stamp = TTDate::strtotime($pse_obj->getColumn('pay_period_transaction_date'));
                $run_id = $pse_obj->getColumn('pay_stub_run_id');
                $pay_stub_entry_name_id = $pse_obj->getPayStubEntryNameId();
                $currency_rate = $pse_obj->getColumn('currency_rate');
                $currency_id = $pse_obj->getColumn('currency_id');

                if (!isset($this->tmp_data['pay_stub_entry'][$user_id][$date_stamp][$run_id])) {
                    $this->tmp_data['pay_stub_entry'][$user_id][$date_stamp][$run_id] = array(
                        'pay_stub_status' => Option::getByKey($pse_obj->getColumn('pay_stub_status_id'), $psf->getOptions('status')),
                        'pay_stub_type' => Option::getByKey($pse_obj->getColumn('pay_stub_type_id'), $psf->getOptions('type')),

                        'pay_period_start_date' => strtotime($pse_obj->getColumn('pay_period_start_date')),
                        'pay_period_end_date' => strtotime($pse_obj->getColumn('pay_period_end_date')),
                        'pay_period_transaction_date' => strtotime($pse_obj->getColumn('pay_period_transaction_date')),
                        'pay_period' => strtotime($pse_obj->getColumn('pay_period_transaction_date')),

                        'pay_stub_start_date' => strtotime($pse_obj->getColumn('pay_stub_start_date')),
                        'pay_stub_end_date' => strtotime($pse_obj->getColumn('pay_stub_end_date')),
                        'pay_stub_transaction_date' => strtotime($pse_obj->getColumn('pay_stub_transaction_date')),
                        'pay_stub_run_id' => $run_id,
                    );
                }
                $this->tmp_data['pay_stub_entry'][$user_id][$date_stamp][$run_id]['currency_rate'] = $currency_rate;

                $this->tmp_data['pay_stub_entry'][$user_id][$date_stamp][$run_id]['currency'] = $this->tmp_data['pay_stub_entry'][$user_id][$date_stamp][$run_id]['current_currency'] = Option::getByKey($currency_id, $currency_options);

                if (isset($this->tmp_data['pay_stub_entry'][$user_id][$date_stamp][$run_id]['PA' . $pay_stub_entry_name_id])) {
                    $this->tmp_data['pay_stub_entry'][$user_id][$date_stamp][$run_id]['PA' . $pay_stub_entry_name_id] = bcadd($this->tmp_data['pay_stub_entry'][$user_id][$date_stamp][$run_id]['PA' . $pay_stub_entry_name_id], $pse_obj->getColumn('amount'));
                } else {
                    $this->tmp_data['pay_stub_entry'][$user_id][$date_stamp][$run_id]['PA' . $pay_stub_entry_name_id] = $pse_obj->getColumn('amount');
                }

                if (isset($this->tmp_data['pay_stub_entry'][$user_id][$date_stamp][$run_id]['PR' . $pay_stub_entry_name_id])) {
                    $this->tmp_data['pay_stub_entry'][$user_id][$date_stamp][$run_id]['PR' . $pay_stub_entry_name_id] = bcadd($this->tmp_data['pay_stub_entry'][$user_id][$date_stamp][$run_id]['PR' . $pay_stub_entry_name_id], $pse_obj->getColumn('rate'));
                } else {
                    $this->tmp_data['pay_stub_entry'][$user_id][$date_stamp][$run_id]['PR' . $pay_stub_entry_name_id] = $pse_obj->getColumn('rate');
                }

                if (isset($this->tmp_data['pay_stub_entry'][$user_id][$date_stamp][$run_id]['PU' . $pay_stub_entry_name_id])) {
                    $this->tmp_data['pay_stub_entry'][$user_id][$date_stamp][$run_id]['PU' . $pay_stub_entry_name_id] = bcadd($this->tmp_data['pay_stub_entry'][$user_id][$date_stamp][$run_id]['PU' . $pay_stub_entry_name_id], $pse_obj->getColumn('units'));
                } else {
                    $this->tmp_data['pay_stub_entry'][$user_id][$date_stamp][$run_id]['PU' . $pay_stub_entry_name_id] = $pse_obj->getColumn('units');
                }

                if (isset($this->tmp_data['pay_stub_entry'][$user_id][$date_stamp][$run_id]['PY' . $pay_stub_entry_name_id])) {
                    $this->tmp_data['pay_stub_entry'][$user_id][$date_stamp][$run_id]['PY' . $pay_stub_entry_name_id] = bcadd($this->tmp_data['pay_stub_entry'][$user_id][$date_stamp][$run_id]['PY' . $pay_stub_entry_name_id], $pse_obj->getColumn('ytd_amount'));
                } else {
                    $this->tmp_data['pay_stub_entry'][$user_id][$date_stamp][$run_id]['PY' . $pay_stub_entry_name_id] = $pse_obj->getColumn('ytd_amount');
                }

                if ($currency_convert_to_base == true and is_object($base_currency_obj)) {
                    $this->tmp_data['pay_stub_entry'][$user_id][$date_stamp][$run_id]['current_currency'] = Option::getByKey($base_currency_obj->getId(), $currency_options);
                }
                $this->getProgressBarObject()->set($this->getAMFMessageID(), $key);
            }
        }

        //Get user data for joining.
        $ulf = TTnew('UserListFactory');
        $ulf->getAPISearchByCompanyIdAndArrayCriteria($this->getUserObject()->getCompany(), $filter_data);
        Debug::Text(' User Total Rows: ' . $ulf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
        $this->getProgressBarObject()->start($this->getAMFMessageID(), $ulf->getRecordCount(), null, TTi18n::getText('Retrieving Data...'));
        foreach ($ulf as $key => $u_obj) {
            $this->tmp_data['user'][$u_obj->getId()] = (array)$u_obj->getObjectAsArray(array_merge((array)$this->getColumnDataConfig(), array('hire_date' => true, 'termination_date' => true, 'birth_date' => true)));
            $this->tmp_data['user'][$u_obj->getId()]['total_pay_stub'] = 1;
            $this->getProgressBarObject()->set($this->getAMFMessageID(), $key);
        }
        //Debug::Arr($this->tmp_data['user'], 'User Raw Data: ', __FILE__, __LINE__, __METHOD__, 10);
        //Debug::Arr($this->tmp_data, 'TMP Data: ', __FILE__, __LINE__, __METHOD__, 10);
        return true;
    }

    public function _preProcess()
    {
        $this->getProgressBarObject()->start($this->getAMFMessageID(), count($this->tmp_data['pay_stub_entry']), null, TTi18n::getText('Pre-Processing Data...'));

        //Merge time data with user data
        $key = 0;
        if (isset($this->tmp_data['pay_stub_entry'])) {
            foreach ($this->tmp_data['pay_stub_entry'] as $user_id => $level_1) {
                if (isset($this->tmp_data['user'][$user_id])) {
                    foreach ($level_1 as $date_stamp => $level_2) {
                        foreach ($level_2 as $row) {
                            $date_columns = TTDate::getReportDates('transaction', $date_stamp, false, $this->getUserObject(), array('pay_period_start_date' => $row['pay_period_start_date'], 'pay_period_end_date' => $row['pay_period_end_date'], 'pay_period_transaction_date' => $row['pay_period_transaction_date']));

                            if (isset($this->tmp_data['user'][$user_id]['hire_date'])) {
                                $hire_date_columns = TTDate::getReportDates('hire', TTDate::parseDateTime($this->tmp_data['user'][$user_id]['hire_date']), false, $this->getUserObject());
                            } else {
                                $hire_date_columns = array();
                            }

                            if (isset($this->tmp_data['user'][$user_id]['termination_date'])) {
                                $termination_date_columns = TTDate::getReportDates('termination', TTDate::parseDateTime($this->tmp_data['user'][$user_id]['termination_date']), false, $this->getUserObject());
                            } else {
                                $termination_date_columns = array();
                            }

                            if (isset($this->tmp_data['user'][$user_id]['birth_date'])) {
                                $birth_date_columns = TTDate::getReportDates('birth', TTDate::parseDateTime($this->tmp_data['user'][$user_id]['birth_date']), false, $this->getUserObject());
                            } else {
                                $birth_date_columns = array();
                            }

                            $processed_data = array(
                                //'pay_period' => array('sort' => $row['pay_period_start_date'], 'display' => TTDate::getDate('DATE', $row['pay_period_start_date'] ).' -> '. TTDate::getDate('DATE', $row['pay_period_end_date'] ) ),
                                //'pay_stub' => array('sort' => $row['pay_stub_transaction_date'], 'display' => TTDate::getDate('DATE', $row['pay_stub_transaction_date'] ) ),
                            );

                            //Need to make sure PSEA IDs are strings not numeric otherwise array_merge will re-key them.
                            $this->data[] = array_merge($this->tmp_data['user'][$user_id], $row, $date_columns, $hire_date_columns, $termination_date_columns, $birth_date_columns, $processed_data);

                            $this->getProgressBarObject()->set($this->getAMFMessageID(), $key);
                            $key++;
                        }
                    }
                }
            }
            unset($this->tmp_data, $row, $date_columns, $hire_date_columns, $termination_date_columns, $birth_date_columns, $processed_data, $level_1);
        }
        //Debug::Arr($this->data, 'preProcess Data: ', __FILE__, __LINE__, __METHOD__, 10);

        return true;
    }

    public function _output($format = null)
    {
        $psf = TTnew('PayStubFactory');
        $export_type_options = Misc::trimSortPrefix($psf->getOptions('export_type'));
        //Debug::Arr($export_type_options, 'Format: '. $format, __FILE__, __LINE__, __METHOD__, 10);
        if ($format == 'pdf_employee_pay_stub' or $format == 'pdf_employee_pay_stub_print'
            or $format == 'pdf_employer_pay_stub' or $format == 'pdf_employer_pay_stub_print'
        ) {
            return $this->_outputPDFPayStub($format);
        } elseif (strlen($format) >= 4 and isset($export_type_options[$format])) {
            return $this->_outputExportPayStub($format);
        } else {
            return parent::_output($format);
        }
    }

    //Get raw data for report

    public function _outputPDFPayStub($format)
    {
        Debug::Text(' Format: ' . $format, __FILE__, __LINE__, __METHOD__, 10);

        $filter_data = $this->getFilterConfig();

        if (!$this->getPermissionObject()->Check('pay_stub', 'enabled', $this->getUserObject()->getId(), $this->getUserObject()->getCompany())
            or !($this->getPermissionObject()->Check('pay_stub', 'view', $this->getUserObject()->getId(), $this->getUserObject()->getCompany()) or $this->getPermissionObject()->Check('pay_stub', 'view_own', $this->getUserObject()->getId(), $this->getUserObject()->getCompany()) or $this->getPermissionObject()->Check('pay_stub', 'view_child', $this->getUserObject()->getId(), $this->getUserObject()->getCompany()))
        ) {
            return $this->getPermissionObject()->PermissionDenied();
        }
        $filter_data['permission_children_ids'] = $this->getPermissionObject()->getPermissionChildren('pay_stub', 'view', $this->getUserObject()->getId(), $this->getUserObject()->getCompany());

        Debug::Arr($filter_data, 'Filter Data: ', __FILE__, __LINE__, __METHOD__, 10);
        $pslf = TTnew('PayStubListFactory');
        $pslf->getAPISearchByCompanyIdAndArrayCriteria($this->getUserObject()->getCompany(), $filter_data);
        Debug::Text('Record Count: ' . $pslf->getRecordCount() . ' Format: ' . $format, __FILE__, __LINE__, __METHOD__, 10);
        if ($pslf->getRecordCount() > 0) {
            $this->getProgressBarObject()->setDefaultKey($this->getAMFMessageID());
            $this->getProgressBarObject()->start($this->getAMFMessageID(), $pslf->getRecordCount());
            $pslf->setProgressBarObject($this->getProgressBarObject()); //Expose progress bar object to pay stub object.

            $filter_data['hide_employer_rows'] = true;
            if ($format == 'pdf_employer_pay_stub' or $format == 'pdf_employer_pay_stub_print') {
                //Must be false, because if it isn't checked it won't be set.
                $filter_data['hide_employer_rows'] = false;
            }

            $this->form_data = range(0, $pslf->getRecordCount()); //Set this so hasData() thinks there is data to report.
            $output = $pslf->getPayStub($pslf, (bool)$filter_data['hide_employer_rows']);

            return $output;
        }

        Debug::Text('No data to return...', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    //PreProcess data such as calculating additional columns from raw data etc...

    public function _outputExportPayStub($format)
    {
        Debug::Text(' Format: ' . $format, __FILE__, __LINE__, __METHOD__, 10);

        $filter_data = $this->getFilterConfig();

        if (!$this->getPermissionObject()->Check('pay_stub', 'enabled', $this->getUserObject()->getId(), $this->getUserObject()->getCompany())
            or !($this->getPermissionObject()->Check('pay_stub', 'view', $this->getUserObject()->getId(), $this->getUserObject()->getCompany()) or $this->getPermissionObject()->Check('pay_stub', 'view_own', $this->getUserObject()->getId(), $this->getUserObject()->getCompany()) or $this->getPermissionObject()->Check('pay_stub', 'view_child', $this->getUserObject()->getId(), $this->getUserObject()->getCompany()))
        ) {
            return $this->getPermissionObject()->PermissionDenied();
        }
        $filter_data['permission_children_ids'] = $this->getPermissionObject()->getPermissionChildren('pay_stub', 'view', $this->getUserObject()->getId(), $this->getUserObject()->getCompany());

        //Debug::Arr($filter_data, 'Filter Data: ', __FILE__, __LINE__, __METHOD__, 10);
        $pslf = TTnew('PayStubListFactory');
        $pslf->getAPISearchByCompanyIdAndArrayCriteria($this->getUserObject()->getCompany(), $filter_data);
        Debug::Text('Record Count: ' . $pslf->getRecordCount() . ' Format: ' . $format, __FILE__, __LINE__, __METHOD__, 10);
        if ($pslf->getRecordCount() > 0) {
            $this->getProgressBarObject()->setDefaultKey($this->getAMFMessageID());
            $this->getProgressBarObject()->start($this->getAMFMessageID(), $pslf->getRecordCount());
            $pslf->setProgressBarObject($this->getProgressBarObject()); //Expose progress bar object to pay stub object.

            $this->form_data = range(0, $pslf->getRecordCount()); //Set this so hasData() thinks there is data to report.
            $output = $pslf->exportPayStub($pslf, $format, $this->getUserObject()->getCompanyObject());

            if ($output != '') {
                if (stristr($format, 'cheque')) {
                    $file_name = 'checks_' . date('Y_m_d') . '.pdf';
                    $mime_type = 'application/pdf';
                } else {
                    //Include file creation number in the exported file name, so the user knows what it is without opening the file,
                    //and can generate multiple files if they need to match a specific number.
                    $ugdlf = TTnew('UserGenericDataListFactory');
                    $ugdlf->getByCompanyIdAndScriptAndDefault($this->getUserObject()->getCompany(), 'PayStubFactory', true);
                    if ($ugdlf->getRecordCount() > 0) {
                        $ugd_obj = $ugdlf->getCurrent();
                        $setup_data = $ugd_obj->getData();
                    }

                    if (isset($setup_data)) {
                        $file_creation_number = $setup_data['file_creation_number']++;
                    } else {
                        $file_creation_number = 0;
                    }

                    $file_name = 'eft_' . $file_creation_number . '_' . date('Y_m_d') . '.txt';
                    $mime_type = 'application/text';
                }

                return array('file_name' => $file_name, 'mime_type' => $mime_type, 'data' => $output);
            } else {
                return array(
                    'api_retval' => false,
                    'api_details' => array(
                        'code' => 'VALIDATION',
                        'description' => TTi18n::getText('ERROR: No data to export...'),
                    )
                );
            }
        }

        Debug::Text('No data to return...', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    protected function _checkPermissions($user_id, $company_id)
    {
        if ($this->getPermissionObject()->Check('report', 'enabled', $user_id, $company_id)
            and $this->getPermissionObject()->Check('report', 'view_pay_stub_summary', $user_id, $company_id)
        ) {
            return true;
        }

        return false;
    }

    protected function _validateConfig()
    {
        $config = $this->getConfig();

        //Make sure some time period is selected.
        if (!isset($config['filter']['time_period']) and !isset($config['filter']['pay_period_id'])) {
            $this->validator->isTrue('time_period', false, TTi18n::gettext('No time period defined for this report'));
        }

        return true;
    }

    protected function _getOptions($name, $params = null)
    {
        $retval = null;
        switch ($name) {
            case 'output_format':
                $psf = TTnew('PayStubFactory');
                $retval = array_merge(parent::getOptions('default_output_format'),
                    array(
                        '-1100-pdf_employee_pay_stub' => TTi18n::gettext('Employee Pay Stub'),
                        '-1110-pdf_employer_pay_stub' => TTi18n::gettext('Employer Pay Stub'),
                    ),
                    Misc::addSortPrefix(Misc::trimSortPrefix($psf->getOptions('export_type')), 1200)
                );

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
                    '-2080-currency_id' => TTi18n::gettext('Currency'),
                    '-2100-custom_filter' => TTi18n::gettext('Custom Filter'),

                    '-2200-pay_stub_status_id' => TTi18n::gettext('Pay Stub Status'),
                    '-2205-pay_stub_type_id' => TTi18n::gettext('Pay Stub Type'),
                    '-2210-pay_stub_run_id' => TTi18n::gettext('Payroll Run'),

                    '-4020-exclude_ytd_adjustment' => TTi18n::gettext('Exclude YTD Adjustments'),

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
                    TTDate::getReportDateOptions('termination', TTi18n::getText('Termination Date'), 14, false),
                    TTDate::getReportDateOptions('birth', TTi18n::getText('Birth Date'), 15, false),
                    TTDate::getReportDateOptions('transaction', TTi18n::getText('Transaction Date'), 27, true)
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
                    '-1030-employee_number' => TTi18n::gettext('Employee #'),
                    '-1040-status' => TTi18n::gettext('Status'),
                    '-1050-title' => TTi18n::gettext('Title'),
                    '-1052-ethnic_group' => TTi18n::gettext('Ethnicity'),
                    '-1053-sex' => TTi18n::gettext('Gender'),
                    '-1054-address1' => TTi18n::gettext('Address 1'),
                    '-1054-address2' => TTi18n::gettext('Address 2'),
                    '-1055-city' => TTi18n::gettext('City'),
                    '-1060-province' => TTi18n::gettext('Province/State'),
                    '-1070-country' => TTi18n::gettext('Country'),
                    '-1075-postal_code' => TTi18n::gettext('Postal Code'),
                    '-1080-user_group' => TTi18n::gettext('Group'),
                    '-1090-default_branch' => TTi18n::gettext('Default Branch'),
                    '-1100-default_department' => TTi18n::gettext('Default Department'),
                    '-1110-currency' => TTi18n::gettext('Currency'),
                    '-1131-current_currency' => TTi18n::gettext('Current Currency'),
                    '-1200-permission_control' => TTi18n::gettext('Permission Group'),
                    '-1210-pay_period_schedule' => TTi18n::gettext('Pay Period Schedule'),
                    '-1220-policy_group' => TTi18n::gettext('Policy Group'),
                    //Handled in date_columns above.
                    //'-1250-pay_period' => TTi18n::gettext('Pay Period'),

                    '-1280-sin' => TTi18n::gettext('SIN/SSN'),
                    '-1290-note' => TTi18n::gettext('Note'),
                    '-1295-tag' => TTi18n::gettext('Tags'),

                    '-2800-pay_stub_status' => TTi18n::gettext('Pay Stub Status'),
                    '-2810-pay_stub_type' => TTi18n::gettext('Pay Stub Type'),
                    '-2820-pay_stub_run_id' => TTi18n::gettext('Payroll Run'),
                );

                $retval = array_merge($retval, (array)$this->getOptions('date_columns'), (array)$this->getOptions('custom_columns'), (array)$this->getOptions('report_static_custom_column'));
                ksort($retval);
                break;
            case 'dynamic_columns':
                $retval = array(
                    //Dynamic - Aggregate functions can be used

                    //Take into account wage groups. However hourly_rates for the same hour type, so we need to figure out an average hourly rate for each column?
                    //'-2010-hourly_rate' => TTi18n::gettext('Hourly Rate'),
                    '-2900-total_pay_stub' => TTi18n::gettext('Total Pay Stubs'), //Group counter...

                );

                $retval = array_merge($retval, $this->getOptions('pay_stub_account_amount_columns'));
                ksort($retval);

                break;
            case 'pay_stub_account_amount_columns':
                //Get all pay stub accounts
                $retval = array();

                $pseallf = TTnew('PayStubEntryAccountLinkListFactory');
                $pseallf->getByCompanyId($this->getUserObject()->getCompany());
                if ($pseallf->getRecordCount() > 0) {
                    $pseal_obj = $pseallf->getCurrent();

                    $default_linked_columns = array(
                        $pseal_obj->getTotalGross(),
                        $pseal_obj->getTotalNetPay(),
                        $pseal_obj->getTotalEmployeeDeduction(),
                        $pseal_obj->getTotalEmployerDeduction());
                } else {
                    $default_linked_columns = array();
                }
                unset($pseallf, $pseal_obj);

                $psealf = TTnew('PayStubEntryAccountListFactory');
                $psealf->getByCompanyIdAndStatusIdAndTypeId($this->getUserObject()->getCompany(), 10, array(10, 20, 30, 40, 50, 60, 65, 80));
                if ($psealf->getRecordCount() > 0) {
                    $type_options = $psealf->getOptions('type');
                    foreach ($type_options as $key => $val) {
                        $type_options[$key] = str_replace(array('Employee', 'Employer', 'Deduction', 'Miscellaneous', 'Total'), array('EE', 'ER', 'Ded', 'Misc', ''), $val);
                    }

                    $i = 0;
                    foreach ($psealf as $psea_obj) {
                        //Need to make the PSEA_ID a string so we can array_merge it properly later.
                        if ($psea_obj->getType() == 40) { //Total accounts.
                            $prefix = null;
                        } else {
                            $prefix = $type_options[$psea_obj->getType()] . ' - ';
                        }

                        $retval['-3' . str_pad($i, 3, 0, STR_PAD_LEFT) . '-PA' . $psea_obj->getID()] = $prefix . $psea_obj->getName();

                        if ($psea_obj->getType() == 10) { //Earnings only can see units.
                            $retval['-4' . str_pad($i, 3, 0, STR_PAD_LEFT) . '-PR' . $psea_obj->getID()] = $prefix . $psea_obj->getName() . ' [' . TTi18n::getText('Rate') . ']';
                            $retval['-5' . str_pad($i, 3, 0, STR_PAD_LEFT) . '-PU' . $psea_obj->getID()] = $prefix . $psea_obj->getName() . ' [' . TTi18n::getText('Units') . ']';
                        }

                        //Add units for Total Gross so they can get a total number of hours/units that way too.
                        if ($psea_obj->getType() == 40 and isset($default_linked_columns[0]) and $default_linked_columns[0] == $psea_obj->getID()) {
                            $retval['-5' . str_pad($i, 3, 0, STR_PAD_LEFT) . '-PU' . $psea_obj->getID()] = $prefix . $psea_obj->getName() . ' [' . TTi18n::getText('Units') . ']';
                        }

                        if ($psea_obj->getType() == 50) { //Accruals, display balance/YTD amount.
                            $retval['-6' . str_pad($i, 3, 0, STR_PAD_LEFT) . '-PY' . $psea_obj->getID()] = $prefix . $psea_obj->getName() . ' [' . TTi18n::getText('Balance') . ']';
                        }

                        $i++;
                    }
                }
                break;
            case 'pay_stub_account_unit_columns':
                //Units are only good for earnings?
                break;
            case 'pay_stub_account_ytd_columns':
                break;
            case 'columns':
                $retval = array_merge($this->getOptions('static_columns'), $this->getOptions('dynamic_columns'), (array)$this->getOptions('report_dynamic_custom_column'));
                break;
            case 'column_format':
                //Define formatting function for each column.
                $columns = Misc::trimSortPrefix(array_merge($this->getOptions('dynamic_columns'), (array)$this->getOptions('report_custom_column')));
                if (is_array($columns)) {
                    foreach ($columns as $column => $name) {
                        if (substr($column, 0, 2) == 'PU') {
                            $retval[$column] = 'numeric';
                        } elseif (strpos($column, '_wage') !== false or strpos($column, '_hourly_rate') !== false
                            or substr($column, 0, 2) == 'PA' or substr($column, 0, 2) == 'PY' or substr($column, 0, 2) == 'PR'
                        ) {
                            $retval[$column] = 'currency';
                        } elseif (strpos($column, '_time') or strpos($column, '_policy')) {
                            $retval[$column] = 'time_unit';
                        } elseif (strpos($column, 'total_pay_stub') !== false) {
                            $retval[$column] = 'numeric';
                        }
                    }
                }
                $retval['verified_time_sheet_date'] = 'time_stamp';
                break;
            case 'aggregates':
                $retval = array();
                $dynamic_columns = array_keys(Misc::trimSortPrefix(array_merge($this->getOptions('dynamic_columns'), (array)$this->getOptions('report_dynamic_custom_column'))));
                if (is_array($dynamic_columns)) {
                    foreach ($dynamic_columns as $column) {
                        switch ($column) {
                            default:
                                if (strpos($column, '_hourly_rate') !== false or substr($column, 0, 2) == 'PR') {
                                    $retval[$column] = 'avg';
                                } else {
                                    $retval[$column] = 'sum';
                                }
                        }
                    }
                }
                $retval['verified_time_sheet'] = 'first';
                $retval['verified_time_sheet_date'] = 'first';
                break;
            case 'templates':
                $retval = array(
                    '-1000-open_pay_stubs' => TTi18n::gettext('Pay Stubs Pending Payment'),

                    '-1010-by_employee+totals' => TTi18n::gettext('Totals by Employee'),
                    '-1020-by_employee+earnings' => TTi18n::gettext('Earnings by Employee'),
                    '-1030-by_employee+employee_deductions' => TTi18n::gettext('Deductions by Employee'),
                    '-1040-by_employee+employer_deductions' => TTi18n::gettext('Employer Contributions by Employee'),
                    '-1050-by_employee+accruals' => TTi18n::gettext('Accruals by Employee'),
                    '-1060-by_employee+totals+earnings+employee_deductions+employer_deductions+accruals' => TTi18n::gettext('All Accounts by Employee'),

                    '-1110-by_title+totals' => TTi18n::gettext('Totals by Title'),
                    '-1120-by_group+totals' => TTi18n::gettext('Totals by Group'),
                    '-1130-by_branch+totals' => TTi18n::gettext('Totals by Branch'),
                    '-1140-by_department+totals' => TTi18n::gettext('Totals by Department'),
                    '-1150-by_branch_by_department+totals' => TTi18n::gettext('Totals by Branch/Department'),
                    '-1160-by_pay_period+totals' => TTi18n::gettext('Totals by Pay Period'),

                    '-1210-by_pay_period_by_employee+totals' => TTi18n::gettext('Totals by Pay Period/Employee'),
                    '-1220-by_employee_by_pay_period+totals' => TTi18n::gettext('Totals by Employee/Pay Period'),
                    '-1230-by_branch_by_pay_period+totals' => TTi18n::gettext('Totals by Branch/Pay Period'),
                    '-1240-by_department_by_pay_period+totals' => TTi18n::gettext('Totals by Department/Pay Period'),
                    '-1250-by_branch_by_department_by_pay_period+totals' => TTi18n::gettext('Totals by Branch/Department/Pay Period'),
                );

                break;
            case 'template_config':
                $template = strtolower(Misc::trimSortPrefix($params['template']));
                if (isset($template) and $template != '') {
                    $pseallf = TTnew('PayStubEntryAccountLinkListFactory');
                    $pseallf->getByCompanyId($this->getUserObject()->getCompany());
                    if ($pseallf->getRecordCount() > 0) {
                        $pseal_obj = $pseallf->getCurrent();

                        $default_linked_columns = array(
                            $pseal_obj->getTotalGross(),
                            $pseal_obj->getTotalNetPay(),
                            $pseal_obj->getTotalEmployeeDeduction(),
                            $pseal_obj->getTotalEmployerDeduction());
                    } else {
                        $default_linked_columns = array();
                    }
                    unset($pseallf, $pseal_obj);

                    switch ($template) {
                        case 'open_pay_stubs':
                            $retval['-1010-time_period']['time_period'] = 'last_pay_period';
                            $retval['-6000-pay_stub_status_id'] = 25;

                            $retval['columns'][] = 'transaction-date_stamp';
                            $retval['columns'][] = 'pay_stub_type';
                            $retval['columns'][] = 'pay_stub_run_id';
                            $retval['columns'][] = 'first_name';
                            $retval['columns'][] = 'last_name';

                            $retval['sort'][] = array('transaction-date_stamp' => 'asc');
                            $retval['sort'][] = array('pay_stub_type' => 'asc');
                            $retval['sort'][] = array('pay_stub_run_id' => 'asc');
                            $retval['sort'][] = array('last_name' => 'asc');
                            $retval['sort'][] = array('first_name' => 'asc');

                            //Total Columns.
                            $psealf = TTnew('PayStubEntryAccountListFactory');
                            $psealf->getByCompanyIdAndStatusIdAndTypeId($this->getUserObject()->getCompany(), 10, array(40));
                            if ($psealf->getRecordCount() > 0) {
                                foreach ($psealf as $psea_obj) {
                                    $retval['columns'][] = 'PA' . $psea_obj->getID();
                                }
                            }
                            break;
                        default:
                            Debug::Text(' Parsing template name: ' . $template, __FILE__, __LINE__, __METHOD__, 10);
                            $retval['-1010-time_period']['time_period'] = 'last_pay_period';

                            //Parse template name, and use the keywords separated by '+' to determine settings.
                            $template_keywords = explode('+', $template);
                            if (is_array($template_keywords)) {
                                foreach ($template_keywords as $template_keyword) {
                                    Debug::Text(' Keyword: ' . $template_keyword, __FILE__, __LINE__, __METHOD__, 10);

                                    switch ($template_keyword) {
                                        //Columns
                                        case 'earnings':
                                            $retval['columns'][] = 'PA' . $default_linked_columns[0]; //Total Gross
                                            $retval['columns'][] = 'PA' . $default_linked_columns[1]; //Net Pay

                                            $psealf = TTnew('PayStubEntryAccountListFactory');
                                            $psealf->getByCompanyIdAndStatusIdAndTypeId($this->getUserObject()->getCompany(), 10, array(10));
                                            if ($psealf->getRecordCount() > 0) {
                                                foreach ($psealf as $psea_obj) {
                                                    $retval['columns'][] = 'PA' . $psea_obj->getID();
                                                }
                                            }
                                            break;
                                        case 'employee_deductions':
                                            $retval['columns'][] = 'PA' . $default_linked_columns[2]; //Employee Deductions

                                            $psealf = TTnew('PayStubEntryAccountListFactory');
                                            $psealf->getByCompanyIdAndStatusIdAndTypeId($this->getUserObject()->getCompany(), 10, array(20));
                                            if ($psealf->getRecordCount() > 0) {
                                                foreach ($psealf as $psea_obj) {
                                                    $retval['columns'][] = 'PA' . $psea_obj->getID();
                                                }
                                            }
                                            break;
                                        case 'employer_deductions':
                                            $retval['columns'][] = 'PA' . $default_linked_columns[3]; //Employor Deductions

                                            $psealf = TTnew('PayStubEntryAccountListFactory');
                                            $psealf->getByCompanyIdAndStatusIdAndTypeId($this->getUserObject()->getCompany(), 10, array(30));
                                            if ($psealf->getRecordCount() > 0) {
                                                foreach ($psealf as $psea_obj) {
                                                    $retval['columns'][] = 'PA' . $psea_obj->getID();
                                                }
                                            }
                                            break;
                                        case 'totals':
                                            $psealf = TTnew('PayStubEntryAccountListFactory');
                                            $psealf->getByCompanyIdAndStatusIdAndTypeId($this->getUserObject()->getCompany(), 10, array(40));
                                            if ($psealf->getRecordCount() > 0) {
                                                foreach ($psealf as $psea_obj) {
                                                    $retval['columns'][] = 'PA' . $psea_obj->getID();
                                                }
                                            }
                                            break;
                                        case 'accruals':
                                            $psealf = TTnew('PayStubEntryAccountListFactory');
                                            $psealf->getByCompanyIdAndStatusIdAndTypeId($this->getUserObject()->getCompany(), 10, array(50));
                                            if ($psealf->getRecordCount() > 0) {
                                                foreach ($psealf as $psea_obj) {
                                                    $retval['columns'][] = 'PA' . $psea_obj->getID();
                                                }
                                            }
                                            break;
                                        //Filter
                                        //Group By
                                        //SubTotal
                                        //Sort
                                        case 'by_employee':
                                            $retval['columns'][] = 'first_name';
                                            $retval['columns'][] = 'last_name';

                                            $retval['group'][] = 'first_name';
                                            $retval['group'][] = 'last_name';

                                            $retval['sort'][] = array('last_name' => 'asc');
                                            $retval['sort'][] = array('first_name' => 'asc');
                                            break;
                                        case 'by_title':
                                            $retval['columns'][] = 'title';

                                            $retval['group'][] = 'title';

                                            $retval['sort'][] = array('title' => 'asc');
                                            break;
                                        case 'by_group':
                                            $retval['columns'][] = 'user_group';

                                            $retval['group'][] = 'user_group';

                                            $retval['sort'][] = array('user_group' => 'asc');
                                            break;
                                        case 'by_branch':
                                            $retval['columns'][] = 'default_branch';

                                            $retval['group'][] = 'default_branch';

                                            $retval['sort'][] = array('default_branch' => 'asc');
                                            break;
                                        case 'by_department':
                                            $retval['columns'][] = 'default_department';

                                            $retval['group'][] = 'default_department';

                                            $retval['sort'][] = array('default_department' => 'asc');
                                            break;
                                        case 'by_branch_by_department':
                                            $retval['columns'][] = 'default_branch';
                                            $retval['columns'][] = 'default_department';

                                            $retval['group'][] = 'default_branch';
                                            $retval['group'][] = 'default_department';

                                            $retval['sub_total'][] = 'default_branch';

                                            $retval['sort'][] = array('default_branch' => 'asc');
                                            $retval['sort'][] = array('default_department' => 'asc');
                                            break;
                                        case 'by_pay_period':
                                            $retval['columns'][] = 'transaction-pay_period';

                                            $retval['group'][] = 'transaction-pay_period';

                                            $retval['sort'][] = array('transaction-pay_period' => 'asc');
                                            break;
                                        case 'by_pay_period_by_employee':
                                            $retval['columns'][] = 'transaction-pay_period';
                                            $retval['columns'][] = 'first_name';
                                            $retval['columns'][] = 'last_name';

                                            $retval['group'][] = 'transaction-pay_period';
                                            $retval['group'][] = 'first_name';
                                            $retval['group'][] = 'last_name';

                                            $retval['sub_total'][] = 'transaction-pay_period';

                                            $retval['sort'][] = array('transaction-pay_period' => 'asc');
                                            $retval['sort'][] = array('last_name' => 'asc');
                                            $retval['sort'][] = array('first_name' => 'asc');
                                            break;
                                        case 'by_pay_period_by_branch':
                                            $retval['columns'][] = 'transaction-pay_period';
                                            $retval['columns'][] = 'default_branch';

                                            $retval['group'][] = 'transaction-pay_period';
                                            $retval['group'][] = 'default_branch';

                                            $retval['sub_total'][] = 'transaction-pay_period';

                                            $retval['sort'][] = array('transaction-pay_period' => 'asc');
                                            $retval['sort'][] = array('default_branch' => 'asc');
                                            break;
                                        case 'by_pay_period_by_department':
                                            $retval['columns'][] = 'transaction-pay_period';
                                            $retval['columns'][] = 'default_department';

                                            $retval['group'][] = 'transaction-pay_period';
                                            $retval['group'][] = 'default_department';

                                            $retval['sub_total'][] = 'transaction-pay_period';

                                            $retval['sort'][] = array('transaction-pay_period' => 'asc');
                                            $retval['sort'][] = array('default_department' => 'asc');
                                            break;
                                        case 'by_pay_period_by_branch_by_department':
                                            $retval['columns'][] = 'transaction-pay_period';
                                            $retval['columns'][] = 'default_branch';
                                            $retval['columns'][] = 'default_department';

                                            $retval['group'][] = 'transaction-pay_period';
                                            $retval['group'][] = 'default_branch';
                                            $retval['group'][] = 'default_department';

                                            $retval['sub_total'][] = 'transaction-pay_period';
                                            $retval['sub_total'][] = 'default_branch';

                                            $retval['sort'][] = array('transaction-pay_period' => 'asc');
                                            $retval['sort'][] = array('default_branch' => 'asc');
                                            $retval['sort'][] = array('default_department' => 'asc');
                                            break;
                                        case 'by_employee_by_pay_period':
                                            $retval['columns'][] = 'full_name';
                                            $retval['columns'][] = 'transaction-pay_period';

                                            $retval['group'][] = 'full_name';
                                            $retval['group'][] = 'transaction-pay_period';

                                            $retval['sub_total'][] = 'full_name';

                                            $retval['sort'][] = array('full_name' => 'asc');
                                            $retval['sort'][] = array('transaction-pay_period' => 'asc');
                                            break;
                                        case 'by_branch_by_pay_period':
                                            $retval['columns'][] = 'default_branch';
                                            $retval['columns'][] = 'transaction-pay_period';

                                            $retval['group'][] = 'default_branch';
                                            $retval['group'][] = 'transaction-pay_period';

                                            $retval['sub_total'][] = 'default_branch';

                                            $retval['sort'][] = array('default_branch' => 'asc');
                                            $retval['sort'][] = array('transaction-pay_period' => 'asc');
                                            break;
                                        case 'by_department_by_pay_period':
                                            $retval['columns'][] = 'default_department';
                                            $retval['columns'][] = 'transaction-pay_period';

                                            $retval['group'][] = 'default_department';
                                            $retval['group'][] = 'transaction-pay_period';

                                            $retval['sub_total'][] = 'default_department';

                                            $retval['sort'][] = array('default_department' => 'asc');
                                            $retval['sort'][] = array('transaction-pay_period' => 'asc');
                                            break;
                                        case 'by_branch_by_department_by_pay_period':
                                            $retval['columns'][] = 'default_branch';
                                            $retval['columns'][] = 'default_department';
                                            $retval['columns'][] = 'transaction-pay_period';

                                            $retval['group'][] = 'default_branch';
                                            $retval['group'][] = 'default_department';
                                            $retval['group'][] = 'transaction-pay_period';

                                            $retval['sub_total'][] = 'default_branch';
                                            $retval['sub_total'][] = 'default_department';

                                            $retval['sort'][] = array('default_branch' => 'asc');
                                            $retval['sort'][] = array('default_department' => 'asc');
                                            $retval['sort'][] = array('transaction-pay_period' => 'asc');
                                            break;
                                    }
                                }
                            }
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
