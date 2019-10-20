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
 * @package Core
 */
class TTLog {
	/**
	 * @param string $object_id UUID
	 * @param int $action_id
	 * @param $description
	 * @param string $user_id UUID
	 * @param $table
	 * @param null $object
	 * @return bool
	 */
	static function addEntry( $object_id, $action_id, $description, $user_id, $table, $object = NULL ) {
		global $config_vars;

		if ( isset($config_vars['other']['disable_audit_log']) AND $config_vars['other']['disable_audit_log'] == TRUE ) {
			return TRUE;
		}

		if ( $object_id == ''  ) {
			return FALSE;
		}

		if ( $action_id == '' ) {
			return FALSE;
		}

		if ( $user_id == '' ) {
			global $current_user;
			if ( is_object( $current_user ) AND is_a( $current_user, 'UserFactory' ) ) { //Make sure we ignore Portal users.
				Debug::text('User Class: '. get_class( $current_user ) .' Full Name: '. $current_user->getFullName(), __FILE__, __LINE__, __METHOD__, 10);
				$user_id = $current_user->getId();
			} else {
				$user_id = TTUUID::getZeroID();
			}
		}

		if ( $table == '' ) {
			return FALSE;
		}

		$lf = TTnew( 'LogFactory' ); /** @var LogFactory $lf */

		$lf->setObject( $object_id );
		$lf->setAction( $action_id );
		$lf->setTableName( $table );
		$lf->setUser( TTUUID::castUUID($user_id) );
		$lf->setDescription( $description );
		$lf->setDate( time() );

		//Debug::text('Object ID: '. $object_id .' Action ID: '. $action_id .' Table: '. $table .' Description: '. $description, __FILE__, __LINE__, __METHOD__, 10);
		if ( $lf->isValid() === TRUE ) {
			$insert_id = $lf->Save();

			if (	(
					!isset($config_vars['other']['disable_audit_log_detail'])
						OR ( isset($config_vars['other']['disable_audit_log_detail']) AND $config_vars['other']['disable_audit_log_detail'] != TRUE )
					)
					AND is_object($object) AND $object->getEnableSystemLogDetail() == TRUE ) {

				$ldf = TTnew( 'LogDetailFactory' ); /** @var LogDetailFactory $ldf */
				$ldf->addLogDetail( $action_id, $insert_id, $object );
			} else {
				Debug::text('LogDetail Disabled... Object ID: '. $object_id .' Action ID: '. $action_id .' Table: '. $table .' Description: '. $description.' User ID: '. $user_id, __FILE__, __LINE__, __METHOD__, 10);
				//Debug::text('LogDetail Disabled... Config: '. (int)$config_vars['other']['disable_audit_log_detail'] .' Function: '. (int)$object->getEnableSystemLogDetail(), __FILE__, __LINE__, __METHOD__, 10);
			}

			return TRUE;
		}

		return FALSE;
	}
}
?>
