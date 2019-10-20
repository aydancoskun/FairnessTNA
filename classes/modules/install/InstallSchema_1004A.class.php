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
class InstallSchema_1004A extends InstallSchema_Base {

	/**
	 * @return bool
	 */
	function preInstall() {
		Debug::text('preInstall: '. $this->getVersion(), __FILE__, __LINE__, __METHOD__, 9);

		$tables = $this->getDatabaseConnection()->MetaTables();
		if ( in_array( 'log', $tables ) ) {
			//Make sure this only runs for PGSQL databases.

			if ( strncmp($this->getDatabaseConnection()->databaseType, 'postgres', 8) == 0 ) {
				//Upgrading, rename log file.
				$query = 'alter table "log" rename to "system_log"';
				$this->getDatabaseConnection()->Execute($query);

				$query = 'alter index "log_id" rename to "system_log_id"';
				$this->getDatabaseConnection()->Execute($query);

				$query = 'alter index "log_user_id_table_name_action_id" rename to "system_log_user_id_table_name_action_id";';
				$this->getDatabaseConnection()->Execute($query);

				$query = 'alter table "log_id_seq" rename to "system_log_id_seq";';
				$this->getDatabaseConnection()->Execute($query);
			} else {
				Debug::text('preInstall: Not a PGSQL database, skipping special commands', __FILE__, __LINE__, __METHOD__, 9);
			}
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
