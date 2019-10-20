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
class InstallSchema_1059A extends InstallSchema_Base {

	/**
	 * @return bool
	 */
	function preInstall() {
		Debug::text('preInstall: '. $this->getVersion(), __FILE__, __LINE__, __METHOD__, 9);

		//For some reason some MySQL installs have duplicate indexes, so detect them here and try to delete them.
		if ( strncmp($this->db->databaseType, 'mysql', 5) == 0 ) {
			$message_recipient_indexes = array_keys( $this->db->MetaIndexes('message_recipient') );
			if ( is_array($message_recipient_indexes) ) {
				if ( array_search( 'message_recipient_id', $message_recipient_indexes ) !== FALSE ) {
					Debug::text('Dropping already existing index: message_recipient_id', __FILE__, __LINE__, __METHOD__, 9);
					$this->db->Execute('DROP INDEX message_recipient_id ON message_recipient');
				} else {
					Debug::text('NOT Dropping already existing index: message_recipient_id', __FILE__, __LINE__, __METHOD__, 9);
				}
			}
			unset($message_recipient_indexes);

			$message_sender_indexes = array_keys( $this->db->MetaIndexes('message_sender') );
			if ( is_array($message_sender_indexes) ) {
				if ( array_search( 'message_sender_id', $message_sender_indexes ) !== FALSE ) {
					Debug::text('Dropping already existing index: message_sender_id', __FILE__, __LINE__, __METHOD__, 9);
					$this->db->Execute('DROP INDEX message_sender_id ON message_sender');
				} else {
					Debug::text('NOT Dropping already existing index: message_sender_id', __FILE__, __LINE__, __METHOD__, 9);
				}
			}
			unset($message_sender_indexes);

			$message_control_indexes = array_keys( $this->db->MetaIndexes('message_control') );
			if ( is_array($message_control_indexes) ) {
				if ( array_search( 'message_control_id', $message_control_indexes ) !== FALSE ) {
					Debug::text('Dropping already existing index: message_control_id', __FILE__, __LINE__, __METHOD__, 9);
					$this->db->Execute('DROP INDEX message_control_id ON message_control');
				} else {
					Debug::text('NOT Dropping already existing index: message_control_id', __FILE__, __LINE__, __METHOD__, 9);
				}
			}
			unset($message_control_indexes);

			$system_log_detail_indexes = array_keys( $this->db->MetaIndexes('system_log_detail') );
			if ( is_array($system_log_detail_indexes) ) {
				if ( array_search( 'system_log_detail_id', $system_log_detail_indexes ) !== FALSE ) {
					Debug::text('Dropping already existing index: system_log_detail_id', __FILE__, __LINE__, __METHOD__, 9);
					$this->db->Execute('DROP INDEX system_log_detail_id ON system_log_detail');
				} else {
					Debug::text('NOT Dropping already existing index: system_log_detail_id', __FILE__, __LINE__, __METHOD__, 9);
				}
			}
			unset($system_log_detail_indexes);
		}

		return TRUE;
	}

	/**
	 * @return bool
	 */
	function postInstall() {
		Debug::text('postInstall: '. $this->getVersion(), __FILE__, __LINE__, __METHOD__, 9);

		return TRUE;
	}
}
?>
