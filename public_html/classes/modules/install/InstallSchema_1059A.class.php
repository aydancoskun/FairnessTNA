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
 * @package Modules\Install
 */
class InstallSchema_1059A extends InstallSchema_Base
{
    public function preInstall()
    {
        Debug::text('preInstall: ' . $this->getVersion(), __FILE__, __LINE__, __METHOD__, 9);

        //For some reason some MySQL installs have duplicate indexes, so detect them here and try to delete them.
        if (strncmp($this->db->databaseType, 'mysql', 5) == 0) {
            $message_recipient_indexes = array_keys($this->db->MetaIndexes('message_recipient'));
            if (is_array($message_recipient_indexes)) {
                if (array_search('message_recipient_id', $message_recipient_indexes) !== false) {
                    Debug::text('Dropping already existing index: message_recipient_id', __FILE__, __LINE__, __METHOD__, 9);
                    $this->db->Execute('DROP INDEX message_recipient_id ON message_recipient');
                } else {
                    Debug::text('NOT Dropping already existing index: message_recipient_id', __FILE__, __LINE__, __METHOD__, 9);
                }
            }
            unset($message_recipient_indexes);

            $message_sender_indexes = array_keys($this->db->MetaIndexes('message_sender'));
            if (is_array($message_sender_indexes)) {
                if (array_search('message_sender_id', $message_sender_indexes) !== false) {
                    Debug::text('Dropping already existing index: message_sender_id', __FILE__, __LINE__, __METHOD__, 9);
                    $this->db->Execute('DROP INDEX message_sender_id ON message_sender');
                } else {
                    Debug::text('NOT Dropping already existing index: message_sender_id', __FILE__, __LINE__, __METHOD__, 9);
                }
            }
            unset($message_sender_indexes);

            $message_control_indexes = array_keys($this->db->MetaIndexes('message_control'));
            if (is_array($message_control_indexes)) {
                if (array_search('message_control_id', $message_control_indexes) !== false) {
                    Debug::text('Dropping already existing index: message_control_id', __FILE__, __LINE__, __METHOD__, 9);
                    $this->db->Execute('DROP INDEX message_control_id ON message_control');
                } else {
                    Debug::text('NOT Dropping already existing index: message_control_id', __FILE__, __LINE__, __METHOD__, 9);
                }
            }
            unset($message_control_indexes);

            $system_log_detail_indexes = array_keys($this->db->MetaIndexes('system_log_detail'));
            if (is_array($system_log_detail_indexes)) {
                if (array_search('system_log_detail_id', $system_log_detail_indexes) !== false) {
                    Debug::text('Dropping already existing index: system_log_detail_id', __FILE__, __LINE__, __METHOD__, 9);
                    $this->db->Execute('DROP INDEX system_log_detail_id ON system_log_detail');
                } else {
                    Debug::text('NOT Dropping already existing index: system_log_detail_id', __FILE__, __LINE__, __METHOD__, 9);
                }
            }
            unset($system_log_detail_indexes);
        }

        return true;
    }

    public function postInstall()
    {
        Debug::text('postInstall: ' . $this->getVersion(), __FILE__, __LINE__, __METHOD__, 9);

        return true;
    }
}
