<?php
/*********************************************************************************
 * This file is part of "Fairness", a Payroll and Time Management program.
 * Fairness is Copyright 2013 Aydan Coskun (aydan.ayfer.coskun@gmail.com)
 * Portions of this software are Copyright (C) 2003 - 2013 TimeTrex Software Inc.
 * because Fairness is a fork of "TimeTrex Workforce Management" Software.
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
/*
 * $Revision: 2196 $
 * $Id: APIAbout.class.php 2196 2008-10-14 16:08:54Z ipso $
 * $Date: 2008-10-14 09:08:54 -0700 (Tue, 14 Oct 2008) $
 */

/**
 * @package API\Core
 */
class APIAbout extends APIFactory {
	protected $main_class = FALSE;

	public function __construct() {
		parent::__construct(); //Make sure parent constructor is always called.

		return TRUE;
	}
    /**
	 * Get about data .
     *
	 */
    function getAboutData( $ytd = 0, $all_companies = FALSE ) {
        global $config_vars;

        $clf = new CompanyListFactory();
        $sslf = new SystemSettingListFactory();
				$system_settings = $sslf->getAllArray();
        $clf->getByID( PRIMARY_COMPANY_ID );
				if ( $clf->getRecordCount() == 1 ) {
					$primary_company = $clf->getCurrent();
				}
        $current_user = $this->getCurrentUserObject();
        if ( isset($primary_company) AND PRIMARY_COMPANY_ID == $current_user->getCompany() ) {
					$current_company = $primary_company;
				} else {
					$current_company = $clf->getByID( $current_user->getCompany() )->getCurrent();
				}

        //$current_user_prefs = $current_user->getUserPreferenceObject();
        $data = $system_settings;

        if ( isset($data['new_version']) AND $data['new_version'] == TRUE  ) {
            $data['new_version'] = TRUE;
        } else {
            $data['new_version'] = FALSE;
        }

// aydancoskun
        $data['product_edition'] = Option::getByKey( ( SOFTWARE_AS_SERVICE == TRUE ) ? $current_company->getProductEdition() : getTTProductEdition(), $current_company->getOptions('product_edition') );

        $data['application_name'] = APPLICATION_NAME;

        $data['organization_url'] = ORGANIZATION_URL;
        //Get Employee counts for this month, and last month
		$month_of_year_arr = TTDate::getMonthOfYearArray();

		//This month
		if ( isset($ytd) AND $ytd == 1 ) {
			$begin_month_epoch = strtotime( '-2 years' );
		} else {
			$begin_month_epoch = TTDate::getBeginMonthEpoch(TTDate::getBeginMonthEpoch(time())-86400);
		}
        $cuclf = TTnew( 'CompanyUserCountListFactory' );
		if ( isset($config_vars['other']['primary_company_id']) AND $current_company->getId() == $config_vars['other']['primary_company_id'] AND $all_companies == TRUE ) {
			$cuclf->getTotalMonthlyMinAvgMaxByCompanyStatusAndStartDateAndEndDate( 10, $begin_month_epoch, TTDate::getEndMonthEpoch( time() ), NULL, NULL, NULL, array('date_stamp' => 'desc') );
		} else {
			$cuclf->getMonthlyMinAvgMaxByCompanyIdAndStartDateAndEndDate( $current_company->getId(), $begin_month_epoch, TTDate::getEndMonthEpoch( time() ), NULL, NULL, NULL, array('date_stamp' => 'desc') );
		}
		Debug::Text('Company User Count Rows: '. $cuclf->getRecordCount(), __FILE__, __LINE__, __METHOD__,10);

		$cjlf = TTnew( 'CronJobListFactory' );
		$cjlf->getMostRecentlyRun();
		if ( $cjlf->getRecordCount() > 0 ) {
			$cj_obj = $cjlf->getCurrent();
			$data['cron'] = array(
								'last_run_date' => ( $cj_obj->getLastRunDate() == FALSE ) ? TTi18n::getText('Never') : TTDate::getDate('DATE+TIME', $cj_obj->getLastRunDate() ),
								);
		}

		Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__,10);
        return $this->returnHandler( $data );
    }

    function isNewVersionAvailable( $ytd = 0, $all_companies = FALSE ) {
        Debug::Text('Check For Update!', __FILE__, __LINE__, __METHOD__,10);

        $current_company = $this->getCurrentCompanyObject();

        $data = $this->getAboutData( $ytd, $all_companies );
				$data['new_version'] = FALSE;
				$latest_version = false;
				$handle = @fopen("https://raw.github.com/aydancoskun/fairness/master/VERSION", "r");
				if ($handle) {
		    	$latest_version = trim(fgets($handle, 4096));
    			fclose($handle);
					Debug::Text('Github says latest version is '.$latest_version, __FILE__, __LINE__, __METHOD__,10);
				}

				$sslf = TTnew( 'SystemSettingListFactory' );
				$sslf->getByName('new_version');
				if ( $sslf->getRecordCount() == 1 ) {
					$obj = $sslf->getCurrent();
				} else {
					$obj = TTnew( 'SystemSettingListFactory' );
				}

				$obj->setName( 'new_version' );


				if ( $latest_version AND version_compare( APPLICATION_VERSION, $latest_version, '<') === TRUE ) {
					Debug::Text('Checking For updates => new_version = TRUE', __FILE__, __LINE__, __METHOD__,10);
					$obj->setValue( 1 );
					$data['new_version'] = TRUE;
				} else {
					Debug::Text('Checking For updates => FALSE', __FILE__, __LINE__, __METHOD__,10);
					$obj->setValue( 0 );
					$data['new_version'] = FALSE;
				}

				if ( $obj->isValid() ) {
						$obj->Save();
				}

        return $this->returnHandler( $data );

    }
}
?>
