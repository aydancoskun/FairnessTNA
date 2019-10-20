<?php
/*********************************************************************************
 * FairnessTNA is a Workforce Management program forked from TimeTrex in 2013,
 * copyright Aydan Coskun. Original code base is copyright TimeTrex Software Inc.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * You can contact Aydan Coskun via issue tracker on github.com/aydancoskun
 ********************************************************************************/


/**
 * @package API\Core
 */
class APIAbout extends APIFactory {
	protected $main_class = FALSE;

	/**
	 * APIAbout constructor.
	 */
	public function __construct() {
		parent::__construct(); //Make sure parent constructor is always called.

		return TRUE;
	}

	/**
	 * Get about data .
	 * @param int $ytd
	 * @param bool $all_companies
	 * @return array
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

		$data['new_version'] = FALSE;

		$data['product_edition'] = Option::getByKey( $current_company->getProductEdition(), $current_company->getOptions('product_edition') );

		$data['application_name'] = APPLICATION_NAME;
		$data['organization_url'] = ORGANIZATION_URL;

		$data['operating_system'] = PHP_OS;
		$data['php_version'] = PHP_VERSION;

		//Get Employee counts for this month, and last month
		$month_of_year_arr = TTDate::getMonthOfYearArray();

		//This month
		if ( isset($ytd) AND $ytd == 1 ) {
			$begin_month_epoch = strtotime( '-2 years' );
		} else {
			$begin_month_epoch = TTDate::getBeginMonthEpoch( ( TTDate::getBeginMonthEpoch( time() ) - 86400 ) );
		}
		$cuclf = TTnew( 'CompanyUserCountListFactory' ); /** @var CompanyUserCountListFactory $cuclf */
		if ( isset($config_vars['other']['primary_company_id']) AND $current_company->getId() == $config_vars['other']['primary_company_id'] AND $all_companies == TRUE ) {
			$cuclf->getTotalMonthlyMinAvgMaxByCompanyStatusAndStartDateAndEndDate( 10, $begin_month_epoch, TTDate::getEndMonthEpoch( time() ), NULL, NULL, NULL, array('date_stamp' => 'desc') );
		} else {
			$cuclf->getMonthlyMinAvgMaxByCompanyIdAndStartDateAndEndDate( $current_company->getId(), $begin_month_epoch, TTDate::getEndMonthEpoch( time() ), NULL, NULL, NULL, array('date_stamp' => 'desc') );
		}
		Debug::Text('Company User Count Rows: '. $cuclf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);

		if ( $cuclf->getRecordCount() > 0 ) {
			foreach( $cuclf as $cuc_obj ) {
				$data['user_counts'][] = array(
																//'label' => $month_of_year_arr[TTDate::getMonth( $begin_month_epoch )] .' '. TTDate::getYear($begin_month_epoch),
																'label' => $month_of_year_arr[TTDate::getMonth( TTDate::strtotime( $cuc_obj->getColumn('date_stamp') ) )] .' '. TTDate::getYear( TTDate::strtotime( $cuc_obj->getColumn('date_stamp') ) ),
																'max_active_users' => $cuc_obj->getColumn('max_active_users'),
																'max_inactive_users' => $cuc_obj->getColumn('max_inactive_users'),
																'max_deleted_users' => $cuc_obj->getColumn('max_deleted_users'),
																);
			}
		}

		if ( isset($data['user_counts']) == FALSE ) {
			$data['user_counts'] = array();
		}

		$cjlf = TTnew( 'CronJobListFactory' ); /** @var CronJobListFactory $cjlf */
		$cjlf->getMostRecentlyRun();
		if ( $cjlf->getRecordCount() > 0 ) {
			$cj_obj = $cjlf->getCurrent();
			$data['cron'] = array(
								'last_run_date' => ( $cj_obj->getLastRunDate() == FALSE ) ? TTi18n::getText('Never') : TTDate::getDate('DATE+TIME', $cj_obj->getLastRunDate() ),
								);
		}
		$data['show_license_data'] = FALSE;
		$data['license_data'] = FALSE;

        $data['agreement']=false;
        $file = Environment::getBasePath() . 'LICENSE';
        if (is_readable($file)) {
            $data['agreement'] = file_get_contents($file);
            $data['agreement'] = str_replace("!APPLICATION_NAME!",strtoupper(APPLICATION_NAME),$data['agreement']);
            $data['agreement'] = str_replace("APPLICATION_NAME",APPLICATION_NAME,$data['agreement']);
        }

        $data['credits']=false;
        $file = Environment::getBasePath() . '3rd_party_credits.txt';
        if (is_readable($file)) {
            $data['credits'] = file_get_contents($file);
            $data['credits'] = str_replace("!APPLICATION_NAME!",strtoupper(APPLICATION_NAME),$data['credits']);
            $data['credits'] = str_replace("APPLICATION_NAME",APPLICATION_NAME,$data['credits']);
        }

        $data['copyright']=false;
        $file = Environment::getBasePath() . 'COPYRIGHT';
        if (is_readable($file)) {
            $data['copyright'] = file_get_contents($file);
            $data['copyright'] = str_replace("!APPLICATION_NAME!",strtoupper(APPLICATION_NAME),$data['copyright']);
            $data['copyright'] = str_replace("APPLICATION_NAME",APPLICATION_NAME,$data['copyright']);
			// Find the separator
			$separator_pos = strpos($data['copyright'],"===");
            $data['copyright'] = substr($data['copyright'], 0, $separator_pos);
        }

		$data['hardware_id'] = FALSE;
		$data['registration_key']=FALSE;
        $data['product_edition']=FALSE;
        if ( !isset($system_settings['license']) ) {
			$system_settings['license'] = NULL;
		}

		$data['system_version'] = $data['system_version'].' ( '.TTDate::getDate('DATE+TIME', $data['system_version_install_date']) . ' )';

		//Debug::Arr($data, 'Data: ', __FILE__, __LINE__, __METHOD__, 10);
//		$data['schema_version_group_A']=false;
		$data['schema_version_group_B']=false;
		$data['schema_version_group_C']=false;
		$data['schema_version_group_D']=false;
//		$data['tax_data_version']=false;
//		$data['tax_engine_version']=false;
		
		
		return $this->returnHandler( $data );
	}

	/**
	 * @param int $ytd
	 * @param bool $all_companies
	 * @return array
	 */
	function isNewVersionAvailable( $ytd = 0, $all_companies = FALSE ) {
		Debug::Text('Check For Update!', __FILE__, __LINE__, __METHOD__, 10);

		$current_company = $this->getCurrentCompanyObject();

		$data = $this->stripReturnHandler( $this->getAboutData( $ytd, $all_companies ) );


		$latest_version = Misc::isLatestVersion( $current_company->getId() );
		if( $latest_version == FALSE ) {
			SystemSettingFactory::setSystemSetting( 'new_version', 1 );
			$data['new_version'] = TRUE;
		} else {
			SystemSettingFactory::setSystemSetting( 'new_version', 0 );
			$data['new_version'] = FALSE;
		}
		return $this->returnHandler( $data );

	}

}
?>
