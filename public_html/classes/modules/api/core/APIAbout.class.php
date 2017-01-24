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
class APIAbout extends APIFactory
{
    protected $main_class = false;

    public function __construct()
    {
        parent::__construct(); //Make sure parent constructor is always called.

        return true;
    }

    /**
     * Get about data .
     *
     */
    public function getAboutData($ytd = 0, $all_companies = false)
    {
        global $config_vars;

        $clf = new CompanyListFactory();
        $sslf = new SystemSettingListFactory();
        $system_settings = $sslf->getAllArray();
        $clf->getByID(PRIMARY_COMPANY_ID);
        if ($clf->getRecordCount() == 1) {
            $primary_company = $clf->getCurrent();
        }
        $current_user = $this->getCurrentUserObject();
        if (isset($primary_company) and PRIMARY_COMPANY_ID == $current_user->getCompany()) {
            $current_company = $primary_company;
        } else {
            $current_company = $clf->getByID($current_user->getCompany())->getCurrent();
        }

        //$current_user_prefs = $current_user->getUserPreferenceObject();
        $data = $system_settings;

        $data['application_name'] = APPLICATION_NAME;

        $data['organization_url'] = ORGANIZATION_URL;

        //Get Employee counts for this month, and last month
        $month_of_year_arr = TTDate::getMonthOfYearArray();

        //This month
        if (isset($ytd) and $ytd == 1) {
            $begin_month_epoch = strtotime('-2 years');
        } else {
            $begin_month_epoch = TTDate::getBeginMonthEpoch((TTDate::getBeginMonthEpoch(time()) - 86400));
        }
        $cuclf = TTnew('CompanyUserCountListFactory');
        if (isset($config_vars['other']['primary_company_id']) and $current_company->getId() == $config_vars['other']['primary_company_id'] and $all_companies == true) {
            $cuclf->getTotalMonthlyMinAvgMaxByCompanyStatusAndStartDateAndEndDate(10, $begin_month_epoch, TTDate::getEndMonthEpoch(time()), null, null, null, array('date_stamp' => 'desc'));
        } else {
            $cuclf->getMonthlyMinAvgMaxByCompanyIdAndStartDateAndEndDate($current_company->getId(), $begin_month_epoch, TTDate::getEndMonthEpoch(time()), null, null, null, array('date_stamp' => 'desc'));
        }
        Debug::Text('Company User Count Rows: ' . $cuclf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);

        if ($cuclf->getRecordCount() > 0) {
            foreach ($cuclf as $cuc_obj) {
                $data['user_counts'][] = array(
                    //'label' => $month_of_year_arr[TTDate::getMonth( $begin_month_epoch )] .' '. TTDate::getYear($begin_month_epoch),
                    'label' => $month_of_year_arr[TTDate::getMonth(TTDate::strtotime($cuc_obj->getColumn('date_stamp')))] . ' ' . TTDate::getYear(TTDate::strtotime($cuc_obj->getColumn('date_stamp'))),
                    'max_active_users' => $cuc_obj->getColumn('max_active_users'),
                    'max_inactive_users' => $cuc_obj->getColumn('max_inactive_users'),
                    'max_deleted_users' => $cuc_obj->getColumn('max_deleted_users'),
                );
            }
        }

        if (isset($data['user_counts']) == false) {
            $data['user_counts'] = array();
        }

        $cjlf = TTnew('CronJobListFactory');
        $cjlf->getMostRecentlyRun();
        if ($cjlf->getRecordCount() > 0) {
            $cj_obj = $cjlf->getCurrent();
            $data['cron'] = array(
                'last_run_date' => ($cj_obj->getLastRunDate() == false) ? TTi18n::getText('Never') : TTDate::getDate('DATE+TIME', $cj_obj->getLastRunDate()),
            );
        }

        //Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);
        return $this->returnHandler($data);
    }
}
