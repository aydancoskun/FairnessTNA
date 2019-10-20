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
 * @package Module_Install
 */
class InstallSchema_1065A extends InstallSchema_Base {

	/**
	 * @return bool
	 */
	function preInstall() {
		Debug::text('preInstall: '. $this->getVersion(), __FILE__, __LINE__, __METHOD__, 9);

		// If a Community/Professional Edition upgrades to latest version (10.1.1) then upgrade to Corporate edition,
		// user_date_total_job_id index will try to be created twice, in 2023C and in 1065A.
		// We will remove it from 1023C so its just created here instead, but we have to drop it first if it exists just in case it was already created.
		$user_date_total_indexes = array_keys( $this->db->MetaIndexes('user_date_total') );
		if ( is_array($user_date_total_indexes) ) {
			if ( array_search( 'user_date_total_job_id', $user_date_total_indexes ) !== FALSE ) {
				Debug::text('Dropping already existing index: user_date_total_job_id', __FILE__, __LINE__, __METHOD__, 9);
				$this->db->Execute('DROP INDEX user_date_total_job_id ON user_date_total');
			} else {
				Debug::text('NOT Dropping already existing index: user_date_total_job_id', __FILE__, __LINE__, __METHOD__, 9);
			}
		}
		unset($user_date_total_indexes);

		return TRUE;
	}

	/**
	 * @return bool
	 */
	function postInstall() {
		Debug::text('postInstall: '. $this->getVersion(), __FILE__, __LINE__, __METHOD__, 9);

		//Delete dummy pay codes created in 1064A schema.
		$pclf = TTnew('PayCodeListFactory'); /** @var PayCodeListFactory $pclf */
		$pclf->getAll();
		if ( $pclf->getRecordCount() > 0 ) {
			foreach( $pclf as $pc_obj ) {
				if ( trim( strtolower( $pc_obj->getCode() ) ) == 'dummy' ) {
					$pc_obj->setDeleted( TRUE );
					$pc_obj->Save(); //Don't call isValid() as that causes the slow SQL query check to see if this is in use to run twice.
				}
			}
		}

		return TRUE;
	}
}
?>
