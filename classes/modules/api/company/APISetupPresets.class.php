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
 * @package API\Company
 */
class APISetupPresets extends APIFactory {
	protected $main_class = 'SetupPresets';

	/**
	 * APISetupPresets constructor.
	 */
	public function __construct() {
		parent::__construct(); //Make sure parent constructor is always called.

		return TRUE;
	}

	/**
	 * @param $location_data
	 * @param $legal_entity_id
	 * @return bool
	 */
	function createPresets( $location_data, $legal_entity_id ) {
		if ( !$this->getPermissionObject()->Check('pay_period_schedule', 'enabled')
				OR !( $this->getPermissionObject()->Check('pay_period_schedule', 'edit') OR $this->getPermissionObject()->Check('pay_period_schedule', 'edit_own') OR $this->getPermissionObject()->Check('pay_period_schedule', 'edit_child') OR $this->getPermissionObject()->Check('pay_period_schedule', 'add') ) ) {
			return	$this->getPermissionObject()->PermissionDenied();
		}

		if ( is_array( $location_data) ) {
			$this->getProgressBarObject()->start( $this->getAMFMessageID(), ( count( $location_data) + 1 ), NULL, TTi18n::getText( 'Creating policies...') );

			$sp = $this->getMainClassObject();
			$sp->setCompany( $this->getCurrentCompanyObject()->getId() );
			$sp->setUser( $this->getCurrentUserObject()->getId() );

			$sp->createPresets();

			$already_processed_country = array();
			$i = 1;
			if ( $legal_entity_id == TTUUID::getZeroID() ) {
				$legal_entity_id = NULL;
			}

			foreach ( $location_data as $location ) {
				if ( isset( $location['country'] ) AND isset( $location['province'] ) ) {
					if ( $location['province'] == '00' ) {
						$location['province'] = NULL;
					}

					if ( !in_array( $location['country'], $already_processed_country ) ) {
						$sp->createPresets( $location['country'], NULL, NULL, NULL, NULL, $legal_entity_id );
					}

					$sp->createPresets( $location['country'], $location['province'], NULL, NULL, NULL, $legal_entity_id );
					Debug::text( 'Creating presets for Country: ' . $location['country'] . ' Province: ' . $location['province'], __FILE__, __LINE__, __METHOD__, 9 );

					$already_processed_country[] = $location['country'];
				}

				$this->getProgressBarObject()->set( $this->getAMFMessageID(), $i );
				$i++;
			}

			$this->getProgressBarObject()->set( $this->getAMFMessageID(), $i++, TTi18n::getText('Creating Permissions...') );
			$sp->Permissions();
			$sp->UserDefaults( $this->getCurrentUserObject()->getLegalEntity() );

			//Assign the current user to the only existing pay period schedule.
			$ppslf = TTnew('PayPeriodScheduleListFactory'); /** @var PayPeriodScheduleListFactory $ppslf */
			$ppslf->getByCompanyId( $this->getCurrentCompanyObject()->getId() );
			if ( $ppslf->getRecordCount() == 1 ) {
				$pps_obj = $ppslf->getCurrent();

				//In case the user runs the quick start wizard after they are already setup, assign all users to the only existing pay period schedule.
				$user_ids = array();
				$ulf = TTNew('UserListFactory'); /** @var UserListFactory $ulf */
				$ulf->getByCompanyId(  $this->getCurrentCompanyObject()->getId() );
				if ( $ulf->getRecordCount() > 0 ) {
					foreach( $ulf as $u_obj ) {
						$user_ids[] = $u_obj->getId();
					}
				}
				$pps_obj->setUser( $user_ids );
				unset($user_ids);

				Debug::text('Assigning current user to pay period schedule: '. $pps_obj->getID(), __FILE__, __LINE__, __METHOD__, 9);
				if ( $pps_obj->isValid() ) {
					$pps_obj->Save();
				}
			}

			$this->getCurrentCompanyObject()->setSetupComplete( TRUE );
			if ( $this->getCurrentCompanyObject()->isValid() ) {
				$this->getCurrentCompanyObject()->Save();
			}

			$this->getProgressBarObject()->stop( $this->getAMFMessageID() );
		}

		return TRUE;
	}
}
?>
