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
 * @package Modules\Install
 */
class InstallSchema_1055A extends InstallSchema_Base {

	/**
	 * @return bool
	 */
	function preInstall() {
		Debug::text('preInstall: '. $this->getVersion(), __FILE__, __LINE__, __METHOD__, 9);

		return TRUE;
	}

	/**
	 * @return bool
	 */
	function postInstall() {
		Debug::text('postInstall: '. $this->getVersion(), __FILE__, __LINE__, __METHOD__, 9);

		//Make sure Medicare Employer uses the same include/exclude accounts as Medicare Employee.
		$clf = TTnew( 'CompanyListFactory' ); /** @var CompanyListFactory $clf */
		$clf->getAll();
		if ( $clf->getRecordCount() > 0 ) {
			foreach( $clf as $c_obj ) {
				Debug::text('Company: '. $c_obj->getName(), __FILE__, __LINE__, __METHOD__, 9);
				if ( $c_obj->getStatus() != 30 AND $c_obj->getCountry() == 'US' ) {
					//Get PayStub Link accounts
					$pseallf = TTnew( 'PayStubEntryAccountLinkListFactory' ); /** @var PayStubEntryAccountLinkListFactory $pseallf */
					$pseallf->getByCompanyId( $c_obj->getID() );
					if	( $pseallf->getRecordCount() > 0 ) {
						$psea_obj = $pseallf->getCurrent();
					} else {
						// @codingStandardsIgnoreStart
						Debug::text('Failed getting PayStubEntryLink for Company ID: '. $c_obj->getId(), __FILE__, __LINE__, __METHOD__, 10);
						//leaving  debugging in place.
						// @codingStandardsIgnoreEnd
						continue;
					}

					$include_pay_stub_accounts = FALSE;
					$exclude_pay_stub_accounts = FALSE;

					$cdlf = TTnew( 'CompanyDeductionListFactory' ); /** @var CompanyDeductionListFactory $cdlf */
					$cdlf->getByCompanyIdAndName($c_obj->getID(), 'Medicare - Employee' );
					if ( $cdlf->getRecordCount() == 1 ) {
						$cd_obj = $cdlf->getCurrent();
						Debug::text('Found Medicare Employee Tax / Deduction, ID: '. $c_obj->getID(), __FILE__, __LINE__, __METHOD__, 9);
						$include_pay_stub_accounts = $cd_obj->getIncludePayStubEntryAccount();
						$exclude_pay_stub_accounts = $cd_obj->getExcludePayStubEntryAccount();
					} else {
						Debug::text('Failed to find Medicare Employee Tax / Deduction for Company: '. $c_obj->getName(), __FILE__, __LINE__, __METHOD__, 9);
					}
					unset($cdlf, $cd_obj);

					//Debug::Arr($include_pay_stub_accounts, 'Include Pay Stub Accounts: '. $c_obj->getName(), __FILE__, __LINE__, __METHOD__, 9);
					//Debug::Arr($exclude_pay_stub_accounts, 'Exclude Pay Stub Accounts: '. $c_obj->getName(), __FILE__, __LINE__, __METHOD__, 9);

					$cdlf = TTnew( 'CompanyDeductionListFactory' ); /** @var CompanyDeductionListFactory $cdlf */
					$cdlf->getByCompanyIdAndName($c_obj->getID(), 'Medicare - Employer' );
					if ( $cdlf->getRecordCount() == 1 ) {
						$cd_obj = $cdlf->getCurrent();
						Debug::text('Found Medicare Employer Tax / Deduction, ID: '. $c_obj->getID(), __FILE__, __LINE__, __METHOD__, 9);

						Debug::text('Medicare Employer Tax / Deduction Matches... Adjusting specific formula Percent...', __FILE__, __LINE__, __METHOD__, 9);
						if ( $include_pay_stub_accounts !== FALSE ) {
							Debug::text('Matching Include/Exclude accounts with Medicare Employee Entry...', __FILE__, __LINE__, __METHOD__, 9);
							//Match include accounts with employee entry.
							$cd_obj->setIncludePayStubEntryAccount( $include_pay_stub_accounts );
							$cd_obj->setExcludePayStubEntryAccount( $exclude_pay_stub_accounts );
						} else {
							Debug::text('NOT Matching Include/Exclude accounts with Medicare Employee Entry...', __FILE__, __LINE__, __METHOD__, 9);
							$cd_obj->setIncludePayStubEntryAccount( array( $psea_obj->getTotalGross() ));
						}

						$cd_obj->ignore_column_list = TRUE; //Prevents SQL errors due to new columns being added later on.
						if ( $cd_obj->isValid() ) {
							$cd_obj->Save();
						}
					} else {
						Debug::text('Failed to find Medicare Employer Tax / Deduction for Company: '. $c_obj->getName(), __FILE__, __LINE__, __METHOD__, 9);
					}
				}
			}
		}

		return TRUE;
	}
}
?>
