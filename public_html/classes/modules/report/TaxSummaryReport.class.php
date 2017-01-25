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
class TaxSummaryReport extends Report
{
    public function __construct()
    {
        $this->title = TTi18n::getText('Tax Summary Report');
        $this->file_name = 'tax_summary_report';

        parent::__construct();

        return true;
    }

    public function _getData($format = null)
    {
        $this->tmp_data = array('pay_stub_entry' => array(), 'user' => array());

        $columns = $this->getColumnDataConfig();
        $filter_data = $this->getFilterConfig();

        $filter_data['permission_children_ids'] = $this->getPermissionObject()->getPermissionChildren('pay_stub', 'view', $this->getUserObject()->getID(), $this->getUserObject()->getCompany());

        $ulf = TTnew('UserListFactory');
        $ulf->getSearchByCompanyIdAndArrayCriteria($this->getUserObject()->getCompany(), $filter_data);
        if ($ulf->getRecordCount() > 0) {
            if (isset($filter_data['company_deduction_id']) == false) {
                $filter_data['company_deduction_id'] = '';
            }

            if (is_array($filter_data['company_deduction_id']) and count($filter_data['company_deduction_id']) > 0 and isset($columns['company_deduction_name'])) {
                Debug::Text('Multiple Tax/Deductions selected along with Tax/Deduction name', __FILE__, __LINE__, __METHOD__, 10);
                $enable_split_tax_deduction_data = true;
            } else {
                $enable_split_tax_deduction_data = false;

                $deduction_include_psea_ids = array();
                $deduction_exclude_psea_ids = array();
                $tax_withheld_psea_ids = array();
                Debug::Text('Multiple Tax/Deductions selected WITHOUT Tax/Deduction name', __FILE__, __LINE__, __METHOD__, 10);
                $cdlf = TTnew('CompanyDeductionListFactory');
                $cdlf->getByCompanyIdAndId($this->getUserObject()->getCompany(), $filter_data['company_deduction_id']);
                if ($cdlf->getRecordCount() > 0) {
                    Debug::Text('aFound Company Deductions...', __FILE__, __LINE__, __METHOD__, 10);
                    foreach ($cdlf as $cd_obj) {
                        $deduction_include_psea_ids = array_merge($deduction_include_psea_ids, $cd_obj->getExpandedPayStubEntryAccountIDs($cd_obj->getIncludePayStubEntryAccount()));
                        $deduction_exclude_psea_ids = array_merge($deduction_exclude_psea_ids, $cd_obj->getExpandedPayStubEntryAccountIDs($cd_obj->getExcludePayStubEntryAccount()));
                        $tax_withheld_psea_ids[] = $cd_obj->getPayStubEntryAccount();
                    }
                    $deduction_include_psea_ids = array_unique($deduction_include_psea_ids);
                    $deduction_exclude_psea_ids = array_unique($deduction_exclude_psea_ids);
                    $tax_withheld_psea_ids = array_unique($tax_withheld_psea_ids);
                }
                //Debug::Arr( array($deduction_include_psea_ids, $deduction_exclude_psea_ids), 'Include/Exclude PSEA IDs: ', __FILE__, __LINE__, __METHOD__, 10);
            }

            //To help determine MaximumTaxableWages, we need to get the UserDeduction records and call getMaximumPayStubEntryAccountAmount().
            $user_deduction_data = array();
            $udlf = TTnew('UserDeductionListFactory');
            $udlf->getByCompanyIdAndCompanyDeductionId($this->getUserObject()->getCompany(), $filter_data['company_deduction_id']);
            if ($udlf->getRecordCount() > 0) {
                foreach ($udlf as $ud_obj) {
                    if ($ud_obj->getMaximumPayStubEntryAccountAmount() != false or $ud_obj->getRate() != false) {
                        $user_deduction_data[$ud_obj->getCompanyDeduction()][$ud_obj->getUser()] = array('maximum_pay_stub_entry_amount' => $ud_obj->getMaximumPayStubEntryAccountAmount(), 'rate' => $ud_obj->getRate());
                    }
                }
            }
            //Debug::Arr($user_deduction_data, 'User Deduction Maximum Amount Data: ', __FILE__, __LINE__, __METHOD__, 10);

            if (!isset($filter_data['exclude_ytd_adjustment'])) {
                $filter_data['exclude_ytd_adjustment'] = false;
            }

            $pself = TTnew('PayStubEntryListFactory');
            $pself->getAPIReportByCompanyIdAndArrayCriteria($this->getUserObject()->getCompany(), $filter_data);
            if ($enable_split_tax_deduction_data == false) {
                $this->getProgressBarObject()->start($this->getAMFMessageID(), $pself->getRecordCount(), null, TTi18n::getText('Retrieving Data...'));
            }
            if ($pself->getRecordCount() > 0) {
                $x = 0;

                if ($enable_split_tax_deduction_data == true) {
                    $cdlf = TTnew('CompanyDeductionListFactory');
                    $cdlf->getByCompanyIdAndId($this->getUserObject()->getCompany(), $filter_data['company_deduction_id']);
                    $this->getProgressBarObject()->start($this->getAMFMessageID(), ($pself->getRecordCount() * $cdlf->getRecordCount()), null, TTi18n::getText('Retrieving Data...'));
                    if ($cdlf->getRecordCount() > 0) {
                        Debug::Text('bFound Company Deductions...', __FILE__, __LINE__, __METHOD__, 10);
                        foreach ($cdlf as $cd_obj) {
                            $company_deduction_id = $cd_obj->getId();

                            $deduction_include_psea_ids = $cd_obj->getExpandedPayStubEntryAccountIDs($cd_obj->getIncludePayStubEntryAccount());
                            $deduction_exclude_psea_ids = $cd_obj->getExpandedPayStubEntryAccountIDs($cd_obj->getExcludePayStubEntryAccount());
                            $tax_withheld_psea_ids = array($cd_obj->getPayStubEntryAccount());

                            foreach ($pself as $key => $pse_obj) {
                                $user_id = $pse_obj->getColumn('user_id');
                                $date_stamp = TTDate::strtotime($pse_obj->getColumn('pay_stub_transaction_date'));
                                $pay_stub_entry_name_id = $pse_obj->getPayStubEntryNameId();

                                if (!isset($this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id])) {
                                    $this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id] = array(
                                        'pay_period_start_date' => strtotime($pse_obj->getColumn('pay_stub_start_date')),
                                        'pay_period_end_date' => strtotime($pse_obj->getColumn('pay_stub_end_date')),
                                        'pay_period_transaction_date' => strtotime($pse_obj->getColumn('pay_stub_transaction_date')),
                                        'pay_period' => strtotime($pse_obj->getColumn('pay_stub_transaction_date')),
                                        'pay_period_id' => $pse_obj->getColumn('pay_period_id'),
                                    );
                                }

                                if (!isset($this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['PA' . $pay_stub_entry_name_id])) {
                                    $this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['PA' . $pay_stub_entry_name_id] = 0;
                                }
                                $this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['PA' . $pay_stub_entry_name_id] = bcadd($this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['PA' . $pay_stub_entry_name_id], $pse_obj->getColumn('amount'));

                                if (!isset($this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['PY' . $pay_stub_entry_name_id])) {
                                    $this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['PY' . $pay_stub_entry_name_id] = 0;
                                }
                                $this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['PY' . $pay_stub_entry_name_id] = bcadd($this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['PY' . $pay_stub_entry_name_id], $pse_obj->getColumn('ytd_amount'));


                                if (!isset($this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['subject_wages'])) {
                                    $this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['subject_wages'] = 0;
                                }
                                $this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['subject_wages'] = bcadd($this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['subject_wages'], Misc::calculateIncludeExcludeAmount($pse_obj->getColumn('amount'), $pay_stub_entry_name_id, $deduction_include_psea_ids, $deduction_exclude_psea_ids));

                                if (!isset($this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['subject_wages_ytd'])) {
                                    $this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['subject_wages_ytd'] = 0;
                                }
                                $this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['subject_wages_ytd'] = bcadd($this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['subject_wages_ytd'], Misc::calculateIncludeExcludeAmount($pse_obj->getColumn('ytd_amount'), $pay_stub_entry_name_id, $deduction_include_psea_ids, $deduction_exclude_psea_ids));

                                if (!isset($this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['subject_units'])) {
                                    $this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['subject_units'] = 0;
                                }
                                $this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['subject_units'] = bcadd($this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['subject_units'], Misc::calculateIncludeExcludeAmount($pse_obj->getColumn('units'), $pay_stub_entry_name_id, $deduction_include_psea_ids, $deduction_exclude_psea_ids));

                                if (!isset($this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['subject_rate'])) {
                                    $this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['subject_rate'] = 0;
                                }
                                $this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['subject_rate'] = ($this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['subject_units'] > 0) ? bcdiv($this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['subject_wages'], $this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['subject_units']) : 0;


                                if (!isset($this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['taxable_wages'])) {
                                    $this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['taxable_wages'] = 0;
                                }
                                $this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['taxable_wages'] = bcadd($this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['taxable_wages'], Misc::calculateIncludeExcludeAmount($pse_obj->getColumn('amount'), $pay_stub_entry_name_id, $deduction_include_psea_ids, $deduction_exclude_psea_ids));

                                if (!isset($this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['taxable_wages_ytd'])) {
                                    $this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['taxable_wages_ytd'] = 0;
                                }
                                $this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['taxable_wages_ytd'] = bcadd($this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['taxable_wages_ytd'], Misc::calculateIncludeExcludeAmount($pse_obj->getColumn('ytd_amount'), $pay_stub_entry_name_id, $deduction_include_psea_ids, $deduction_exclude_psea_ids));

                                if (empty($tax_withheld_psea_ids) == false and in_array($pay_stub_entry_name_id, $tax_withheld_psea_ids)) {
                                    if (!isset($this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['tax_withheld'])) {
                                        $this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['tax_withheld'] = 0;
                                    }
                                    $this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['tax_withheld'] = bcadd($this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['tax_withheld'], $pse_obj->getColumn('amount'));
                                }

                                $pay_period_weeks = round(TTDate::getWeeks((TTDate::getEndDayEpoch(TTDate::strtotime($pse_obj->getColumn('pay_stub_end_date'))) - TTDate::getBeginDayEpoch(TTDate::strtotime($pse_obj->getColumn('pay_stub_start_date'))))), 2);

                                //For unemployment reports, we need to know the weeks where renumeration was received, so count weeks between start/end date of pay period
                                //Set pay period weeks once per transaction date (pay period)
                                if (!isset($this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['pay_period_weeks'])) {
                                    $this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['pay_period_weeks'] = $pay_period_weeks;
                                }

                                if (!isset($this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['pay_period_taxable_wages_weeks'])
                                    and isset($this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['taxable_wages'])
                                    and $this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['taxable_wages'] > 0
                                ) {
                                    $this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['pay_period_taxable_wages_weeks'] = $pay_period_weeks;
                                }
                                if (!isset($this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['pay_period_tax_withheld_weeks'])
                                    and isset($this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['tax_withheld'])
                                    and $this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['tax_withheld'] > 0
                                ) {
                                    $this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['pay_period_tax_withheld_weeks'] = $pay_period_weeks;
                                }

                                if (isset($user_deduction_data[$company_deduction_id][$user_id]) and $user_deduction_data[$company_deduction_id][$user_id]['rate'] != false) {
                                    $this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['company_deduction_rate'] = $user_deduction_data[$company_deduction_id][$user_id]['rate'];
                                }

                                $this->getProgressBarObject()->set($this->getAMFMessageID(), $x);

                                $x++;
                            }
                        }
                    }
                } else {
                    if (is_array($filter_data['company_deduction_id']) and count($filter_data['company_deduction_id']) == 1 and isset($filter_data['company_deduction_id'][0])) {
                        $company_deduction_id = $filter_data['company_deduction_id'][0];
                    } else {
                        $company_deduction_id = 0;
                    }
                    Debug::Text('Company Deduction ID: ' . $company_deduction_id, __FILE__, __LINE__, __METHOD__, 10);
                    foreach ($pself as $key => $pse_obj) {
                        $user_id = $pse_obj->getColumn('user_id');
                        $date_stamp = TTDate::strtotime($pse_obj->getColumn('pay_stub_transaction_date'));
                        $pay_stub_entry_name_id = $pse_obj->getPayStubEntryNameId();

                        if (!isset($this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id])) {
                            $this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id] = array(
                                'pay_period_start_date' => strtotime($pse_obj->getColumn('pay_stub_start_date')),
                                'pay_period_end_date' => strtotime($pse_obj->getColumn('pay_stub_end_date')),
                                'pay_period_transaction_date' => strtotime($pse_obj->getColumn('pay_stub_transaction_date')),
                                'pay_period' => strtotime($pse_obj->getColumn('pay_stub_transaction_date')),
                                'pay_period_id' => $pse_obj->getColumn('pay_period_id'),
                            );
                        }

                        if (!isset($this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['PA' . $pay_stub_entry_name_id])) {
                            $this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['PA' . $pay_stub_entry_name_id] = 0;
                        }
                        $this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['PA' . $pay_stub_entry_name_id] = bcadd($this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['PA' . $pay_stub_entry_name_id], $pse_obj->getColumn('amount'));

                        if (!isset($this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['PY' . $pay_stub_entry_name_id])) {
                            $this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['PY' . $pay_stub_entry_name_id] = 0;
                        }
                        $this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['PY' . $pay_stub_entry_name_id] = bcadd($this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['PY' . $pay_stub_entry_name_id], $pse_obj->getColumn('ytd_amount'));


                        if (!isset($this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['subject_wages'])) {
                            $this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['subject_wages'] = 0;
                        }
                        $this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['subject_wages'] = bcadd($this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['subject_wages'], Misc::calculateIncludeExcludeAmount($pse_obj->getColumn('amount'), $pay_stub_entry_name_id, $deduction_include_psea_ids, $deduction_exclude_psea_ids));

                        if (!isset($this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['subject_wages_ytd'])) {
                            $this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['subject_wages_ytd'] = 0;
                        }
                        $this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['subject_wages_ytd'] = bcadd($this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['subject_wages_ytd'], Misc::calculateIncludeExcludeAmount($pse_obj->getColumn('ytd_amount'), $pay_stub_entry_name_id, $deduction_include_psea_ids, $deduction_exclude_psea_ids));

                        if (!isset($this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['subject_units'])) {
                            $this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['subject_units'] = 0;
                        }
                        $this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['subject_units'] = bcadd($this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['subject_units'], Misc::calculateIncludeExcludeAmount($pse_obj->getColumn('units'), $pay_stub_entry_name_id, $deduction_include_psea_ids, $deduction_exclude_psea_ids));

                        if (!isset($this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['subject_rate'])) {
                            $this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['subject_rate'] = 0;
                        }
                        $this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['subject_rate'] = ($this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['subject_units'] > 0) ? bcdiv($this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['subject_wages'], $this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['subject_units']) : 0;


                        if (!isset($this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['taxable_wages'])) {
                            $this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['taxable_wages'] = 0;
                        }
                        $this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['taxable_wages'] = bcadd($this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['taxable_wages'], Misc::calculateIncludeExcludeAmount($pse_obj->getColumn('amount'), $pay_stub_entry_name_id, $deduction_include_psea_ids, $deduction_exclude_psea_ids));

                        if (!isset($this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['taxable_wages_ytd'])) {
                            $this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['taxable_wages_ytd'] = 0;
                        }
                        $this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['taxable_wages_ytd'] = bcadd($this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['taxable_wages_ytd'], Misc::calculateIncludeExcludeAmount($pse_obj->getColumn('ytd_amount'), $pay_stub_entry_name_id, $deduction_include_psea_ids, $deduction_exclude_psea_ids));

                        if (empty($tax_withheld_psea_ids) == false and in_array($pay_stub_entry_name_id, $tax_withheld_psea_ids)) {
                            if (!isset($this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['tax_withheld'])) {
                                $this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['tax_withheld'] = 0;
                            }
                            $this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['tax_withheld'] = bcadd($this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['tax_withheld'], $pse_obj->getColumn('amount'));
                        }
                        $pay_period_weeks = round(TTDate::getWeeks((TTDate::getEndDayEpoch(TTDate::strtotime($pse_obj->getColumn('pay_stub_end_date'))) - TTDate::getBeginDayEpoch(TTDate::strtotime($pse_obj->getColumn('pay_stub_start_date'))))), 2);

                        //For unemployment reports, we need to know the weeks where renumeration was received, so count weeks between start/end date of pay period
                        //Set pay period weeks once per transaction date (pay period)
                        if (!isset($this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['pay_period_weeks'])) {
                            $this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['pay_period_weeks'] = $pay_period_weeks;
                        }

                        if (!isset($this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['pay_period_taxable_wages_weeks'])
                            and isset($this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['taxable_wages'])
                            and $this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['taxable_wages'] > 0
                        ) {
                            $this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['pay_period_taxable_wages_weeks'] = $pay_period_weeks;
                        }
                        if (!isset($this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['pay_period_tax_withheld_weeks'])
                            and isset($this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['tax_withheld'])
                            and $this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['tax_withheld'] > 0
                        ) {
                            $this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['pay_period_tax_withheld_weeks'] = $pay_period_weeks;
                        }

                        if (isset($user_deduction_data[$company_deduction_id][$user_id]) and $user_deduction_data[$company_deduction_id][$user_id]['rate'] != false) {
                            $this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['company_deduction_rate'] = $user_deduction_data[$company_deduction_id][$user_id]['rate'];
                        }

                        $this->getProgressBarObject()->set($this->getAMFMessageID(), $x);

                        $x++;
                    }
                }

                //Loop through all records and handle maximum amounts.
                //  Do this down here, since with include/exclude amounts, we could reach the maximum amount,
                //	then the next row has an excluded (negative) amount, so now we've capped the amount at a maximum, and its reduced by -XX.XX.
                //  Instead just handle all the include/exclude amount above first, then once that is done go through and cap any necessary amounts.
                if (count($user_deduction_data) > 0) {
                    foreach ($this->tmp_data['pay_stub_entry'] as $company_deduction_id => $level1) {
                        foreach ($level1 as $date_stamp => $level2) {
                            foreach ($level2 as $user_id => $row) {
                                //Debug::Text('Before Current Taxable Wages: '. $this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['taxable_wages'] .' YTD: '. $this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['taxable_wages_ytd'], __FILE__, __LINE__, __METHOD__, 10);
                                if (isset($this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['taxable_wages'])
                                    and isset($user_deduction_data[$company_deduction_id][$user_id])
                                    and $user_deduction_data[$company_deduction_id][$user_id]['maximum_pay_stub_entry_amount'] > 0
                                    and $this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['taxable_wages_ytd'] > $user_deduction_data[$company_deduction_id][$user_id]['maximum_pay_stub_entry_amount']
                                ) {
                                    //Make sure taxable wages abides by maximum amount properly.
                                    $tmp_taxable_wages_ytd_diff = ($this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['taxable_wages_ytd'] - $this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['taxable_wages']);
                                    $tmp_taxable_wages_max_diff = ($user_deduction_data[$company_deduction_id][$user_id]['maximum_pay_stub_entry_amount'] - $tmp_taxable_wages_ytd_diff);
                                    //Debug::Text('  Taxable Wages YTD Diff: '. $tmp_taxable_wages_ytd_diff .' Max Diff: '. $tmp_taxable_wages_max_diff, __FILE__, __LINE__, __METHOD__, 10);
                                    if ($tmp_taxable_wages_ytd_diff < $user_deduction_data[$company_deduction_id][$user_id]['maximum_pay_stub_entry_amount']) {
                                        $this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['taxable_wages'] = $tmp_taxable_wages_max_diff;
                                    } else {
                                        $this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['taxable_wages'] = 0;
                                    }

                                    $this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['taxable_wages_ytd'] = $user_deduction_data[$company_deduction_id][$user_id]['maximum_pay_stub_entry_amount'];
                                    unset($tmp_taxable_wages_ytd_diff, $tmp_taxable_wages_max_diff);
                                }
                                //Debug::Text('After Current Taxable Wages: '. $this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['taxable_wages'] .' YTD: '. $this->tmp_data['pay_stub_entry'][$company_deduction_id][$date_stamp][$user_id]['taxable_wages_ytd'], __FILE__, __LINE__, __METHOD__, 10);
                            }
                        }
                    }
                    unset($level1, $level2, $company_deduction_id, $date_stamp, $user_id, $row);
                }
            }
        }
        //Debug::Arr($this->tmp_data['pay_stub_entry'], 'Pay Stub Entry Data: ', __FILE__, __LINE__, __METHOD__, 10);

        //Get user data for joining.
        $ulf = TTnew('UserListFactory');
        $ulf->getAPISearchByCompanyIdAndArrayCriteria($this->getUserObject()->getCompany(), $filter_data);
        Debug::Text(' User Total Rows: ' . $ulf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
        $this->getProgressBarObject()->start($this->getAMFMessageID(), $ulf->getRecordCount(), null, TTi18n::getText('Retrieving Data...'));
        foreach ($ulf as $key => $u_obj) {
            $this->tmp_data['user'][$u_obj->getId()] = (array)$u_obj->getObjectAsArray($this->getColumnDataConfig());
            $this->getProgressBarObject()->set($this->getAMFMessageID(), $key);
        }

        //Company Deduction data for joining...
        $cdlf = TTnew('CompanyDeductionListFactory');
        $cdlf->getByCompanyIdAndId($this->getUserObject()->getCompany(), $filter_data['company_deduction_id']);
        $this->getProgressBarObject()->start($this->getAMFMessageID(), $cdlf->getRecordCount(), null, TTi18n::getText('Retrieving Data...'));
        if ($cdlf->getRecordCount() > 0) {
            foreach ($cdlf as $key => $cd_obj) {
                $this->tmp_data['company_deduction'][$cd_obj->getId()] = Misc::addKeyPrefix('company_deduction_', (array)$cd_obj->getObjectAsArray(array('id' => true, 'name' => true)));
                $this->getProgressBarObject()->set($this->getAMFMessageID(), $key);
            }
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
            //foreach( $this->tmp_data['pay_stub_entry'] as $date_stamp => $level_1 ) {
            foreach ($this->tmp_data['pay_stub_entry'] as $company_deduction_id => $level_1) {
                foreach ($level_1 as $date_stamp => $level_2) {
                    foreach ($level_2 as $user_id => $row) {
                        if (isset($this->tmp_data['user'][$user_id])) {
                            $date_columns = TTDate::getReportDates('transaction', $date_stamp, false, $this->getUserObject(), array('pay_period_start_date' => $row['pay_period_start_date'], 'pay_period_end_date' => $row['pay_period_end_date'], 'pay_period_transaction_date' => $row['pay_period_transaction_date']));
                            $processed_data = array(
                                //'pay_period' => array('sort' => $row['pay_period_start_date'], 'display' => TTDate::getDate('DATE', $row['pay_period_start_date'] ).' -> '. TTDate::getDate('DATE', $row['pay_period_end_date'] ) ),
                                //'pay_stub' => array('sort' => $row['pay_stub_transaction_date'], 'display' => TTDate::getDate('DATE', $row['pay_stub_transaction_date'] ) ),
                            );
                            //Need to make sure PSEA IDs are strings not numeric otherwise array_merge will re-key them.

                            if (isset($this->tmp_data['company_deduction'][$company_deduction_id])) {
                                $tmp_company_deduction = $this->tmp_data['company_deduction'][$company_deduction_id];
                            } else {
                                $tmp_company_deduction = array();
                            }
                            $this->data[] = array_merge($this->tmp_data['user'][$user_id], $tmp_company_deduction, $row, $date_columns, $processed_data);

                            $this->getProgressBarObject()->set($this->getAMFMessageID(), $key);
                            $key++;
                        }
                    }
                }
            }

            unset($this->tmp_data, $row, $date_columns, $processed_data, $level_1);
        }
        //Debug::Arr($this->data, 'preProcess Data: ', __FILE__, __LINE__, __METHOD__, 10);

        return true;
    }

    //Get raw data for report

    protected function _checkPermissions($user_id, $company_id)
    {
        if ($this->getPermissionObject()->Check('report', 'enabled', $user_id, $company_id)
            and $this->getPermissionObject()->Check('report', 'view_generic_tax_summary', $user_id, $company_id)
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
                    '-2000-company_deduction_id' => TTi18n::gettext('Tax'),
                    '-2010-user_status_id' => TTi18n::gettext('Employee Status'),
                    '-2020-user_group_id' => TTi18n::gettext('Employee Group'),
                    '-2030-user_title_id' => TTi18n::gettext('Employee Title'),
                    '-2035-user_tag' => TTi18n::gettext('Employee Tags'),
                    '-2040-include_user_id' => TTi18n::gettext('Employee Include'),
                    '-2050-exclude_user_id' => TTi18n::gettext('Employee Exclude'),
                    '-2060-default_branch_id' => TTi18n::gettext('Default Branch'),
                    '-2070-default_department_id' => TTi18n::gettext('Default Department'),
                    '-3000-custom_filter' => TTi18n::gettext('Custom Filter'),

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
                $retval = TTDate::getReportDateOptions('transaction', TTi18n::getText('Transaction Date'), 13, true);
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
                    '-1055-city' => TTi18n::gettext('City'),
                    '-1060-province' => TTi18n::gettext('Province/State'),
                    '-1070-country' => TTi18n::gettext('Country'),
                    '-1080-user_group' => TTi18n::gettext('Group'),
                    '-1090-default_branch' => TTi18n::gettext('Default Branch'),
                    '-1100-default_department' => TTi18n::gettext('Default Department'),
                    '-1102-default_job' => TTi18n::gettext('Default Job'),
                    '-1104-default_job_item' => TTi18n::gettext('Default Task'),
                    '-1110-currency' => TTi18n::gettext('Currency'),
                    '-1200-permission_control' => TTi18n::gettext('Permission Group'),
                    '-1210-pay_period_schedule' => TTi18n::gettext('Pay Period Schedule'),
                    '-1220-policy_group' => TTi18n::gettext('Policy Group'),

                    //Handled in date_columns above.
                    //'-1250-pay_period' => TTi18n::gettext('Pay Period'),

                    '-1280-sin' => TTi18n::gettext('SIN/SSN'),
                    '-1290-note' => TTi18n::gettext('Note'),
                    '-1295-tag' => TTi18n::gettext('Tags'),

                    '-1298-hire_date_age' => TTi18n::gettext('Length of Service'),
                    '-1299-birth_date_age' => TTi18n::gettext('Age'),

                    '-1410-company_deduction_name' => TTi18n::gettext('Tax/Deduction Name'),

                );

                $retval = array_merge($retval, $this->getOptions('date_columns'), (array)$this->getOptions('report_static_custom_column'));
                ksort($retval);
                break;
            case 'dynamic_columns':
                $retval = array(
                    //Dynamic - Aggregate functions can be used

                    //Take into account wage groups. However hourly_rates for the same hour type, so we need to figure out an average hourly rate for each column?
                    //'-2010-hourly_rate' => TTi18n::gettext('Hourly Rate'),
                    '-2030-subject_wages' => TTi18n::gettext('Subject Wages'),
                    '-2040-taxable_wages' => TTi18n::gettext('Taxable Wages'),
                    '-2050-tax_withheld' => TTi18n::gettext('Tax Withheld'),

                    '-2110-subject_wages_ytd' => TTi18n::gettext('Subject Wages YTD'),
                    '-2120-subject_units' => TTi18n::gettext('Subject Units'),
                    '-2130-subject_rate' => TTi18n::gettext('Subject Hourly Rate'),

                    '-2150-taxable_wages_ytd' => TTi18n::gettext('Taxable Wages YTD'),

                    '-2210-pay_period_taxable_wages_weeks' => TTi18n::gettext('Taxable Wages Weeks'),
                    '-2220-pay_period_tax_withheld_weeks' => TTi18n::gettext('Tax Withheld Weeks'),
                    '-2230-pay_period_weeks' => TTi18n::gettext('Pay Period Weeks'),

                    '-3010-company_deduction_rate' => TTi18n::gettext('Tax/Deduction Rate'),

                );

                $retval = array_merge($retval, $this->getOptions('pay_stub_account_amount_columns'));
                ksort($retval);

                break;
            case 'pay_stub_account_amount_columns':
                //Get all pay stub accounts
                $retval = array();

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
                        if ($column == 'subject_units' or strpos($column, '_weeks') !== false or substr($column, 0, 2) == 'PU') {
                            $retval[$column] = 'numeric';
                        } elseif (strpos($column, '_wage') !== false or strpos($column, '_hourly_rate') !== false
                            or substr($column, 0, 2) == 'PA' or substr($column, 0, 2) == 'PY' or substr($column, 0, 2) == 'PR'
                            or strpos($column, '_ytd') !== false
                            or $column == 'tax_withheld' or $column == 'subject_rate'
                        ) {
                            $retval[$column] = 'currency';
                        } elseif (strpos($column, '_time') or strpos($column, '_policy')) {
                            $retval[$column] = 'time_unit';
                        } elseif ($column == 'company_deduction_rate') {
                            $retval[$column] = 'percent';
                        }
                    }
                }
                $retval['verified_time_sheet_date'] = 'time_stamp';
                break;
            case 'grand_total_metadata':
                //Make sure all jobs are sum'd
                $retval['aggregate'] = array();
                $dynamic_columns = array_keys(Misc::trimSortPrefix($this->getOptions('dynamic_columns')));
                if (is_array($dynamic_columns)) {
                    foreach ($dynamic_columns as $column) {
                        switch ($column) {
                            default:
                                if (strpos($column, '_hourly_rate') !== false or strpos($column, '_rate') !== false or substr($column, 0, 2) == 'PR' or $column == 'company_deduction_rate') {
                                    $retval['aggregate'][$column] = 'avg';
                                } elseif (strpos($column, '_ytd') !== false) {
                                    $retval['aggregate'][$column] = 'sum';
                                } else {
                                    $retval['aggregate'][$column] = 'sum';
                                }
                        }
                    }
                }
                break;
            case 'sub_total_by_metadata':
                //Make sure task estimates are sum'd.
                $retval['aggregate'] = array();
                $dynamic_columns = array_keys(Misc::trimSortPrefix($this->getOptions('dynamic_columns')));
                if (is_array($dynamic_columns)) {
                    foreach ($dynamic_columns as $column) {
                        switch ($column) {
                            default:
                                if (strpos($column, '_hourly_rate') !== false or strpos($column, '_rate') !== false or substr($column, 0, 2) == 'PR' or $column == 'company_deduction_rate') {
                                    $retval['aggregate'][$column] = 'avg';
                                } elseif (strpos($column, '_ytd') !== false) {
                                    $retval['aggregate'][$column] = 'sum';
                                } else {
                                    $retval['aggregate'][$column] = 'sum';
                                }
                        }
                    }
                }
                break;
            case 'group_by_metadata':
                $retval['aggregate'] = array();
                $dynamic_columns = array_keys(Misc::trimSortPrefix($this->getOptions('dynamic_columns')));
                if (is_array($dynamic_columns)) {
                    foreach ($dynamic_columns as $column) {
                        switch ($column) {
                            default:
                                if (strpos($column, '_hourly_rate') !== false or strpos($column, '_rate') !== false or substr($column, 0, 2) == 'PR' or $column == 'company_deduction_rate') {
                                    $retval['aggregate'][$column] = 'avg';
                                } elseif (strpos($column, '_ytd') !== false) {
                                    $retval['aggregate'][$column] = 'max';
                                } else {
                                    $retval['aggregate'][$column] = 'sum';
                                }
                        }
                    }
                }

                $retval['verified_time_sheet'] = 'first';
                $retval['verified_time_sheet_date'] = 'first';
                break;
            case 'templates':
                $retval = array(
                    '-1010-by_employee+taxes' => TTi18n::gettext('Tax by Employee'),
                    '-1010-by_company_deduction_by_employee+taxes' => TTi18n::gettext('Tax by Tax/Employee'),
                );

                break;
            case 'template_config':
                $template = strtolower(Misc::trimSortPrefix($params['template']));
                if (isset($template) and $template != '') {
                    switch ($template) {
                        default:
                            Debug::Text(' Parsing template name: ' . $template, __FILE__, __LINE__, __METHOD__, 10);
                            $retval['-1010-time_period']['time_period'] = 'last_quarter';

                            //Parse template name, and use the keywords separated by '+' to determine settings.
                            $template_keywords = explode('+', $template);
                            if (is_array($template_keywords)) {
                                foreach ($template_keywords as $template_keyword) {
                                    Debug::Text(' Keyword: ' . $template_keyword, __FILE__, __LINE__, __METHOD__, 10);

                                    switch ($template_keyword) {
                                        //Columns
                                        case 'taxes':
                                            //$retval['columns'][] = 'PA'.$default_linked_columns[0];
                                            $retval['columns'][] = 'subject_wages'; //Basically Total Gross.
                                            $retval['columns'][] = 'taxable_wages';
                                            $retval['columns'][] = 'tax_withheld';
                                            break;
                                        //Filter
                                        //Group By
                                        //SubTotal
                                        //Sort
                                        case 'by_employee':
                                            $retval['columns'][] = 'first_name';
                                            $retval['columns'][] = 'last_name';
                                            $retval['columns'][] = 'sin';

                                            $retval['-2000-company_deduction_id'][] = 0;

                                            $retval['group'][] = 'first_name';
                                            $retval['group'][] = 'last_name';
                                            $retval['group'][] = 'sin';

                                            $retval['sort'][] = array('last_name' => 'asc');
                                            $retval['sort'][] = array('first_name' => 'asc');
                                            $retval['sort'][] = array('sin' => 'asc');
                                            break;
                                        case 'by_company_deduction_by_employee':
                                            $retval['columns'][] = 'company_deduction_name';
                                            $retval['columns'][] = 'first_name';
                                            $retval['columns'][] = 'last_name';
                                            $retval['columns'][] = 'sin';

                                            $retval['-2000-company_deduction_id'][] = 0;

                                            $retval['group'][] = 'company_deduction_name';
                                            $retval['group'][] = 'first_name';
                                            $retval['group'][] = 'last_name';
                                            $retval['group'][] = 'sin';

                                            $retval['sub_total'][] = 'company_deduction_name';

                                            $retval['sort'][] = array('company_deduction_name' => 'asc');
                                            $retval['sort'][] = array('last_name' => 'asc');
                                            $retval['sort'][] = array('first_name' => 'asc');
                                            $retval['sort'][] = array('sin' => 'asc');
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
