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
 * @package Core
 */
class TTLog
{
    public static function addEntry($object_id, $action_id, $description, $user_id, $table, $object = null)
    {
        global $config_vars;

        if (isset($config_vars['other']['disable_audit_log']) and $config_vars['other']['disable_audit_log'] == true) {
            return true;
        }

        if (!is_numeric($object_id)) {
            return false;
        }

        if ($action_id == '') {
            return false;
        }

        if ($user_id == '') {
            global $current_user;
            if (is_object($current_user)) {
                $user_id = $current_user->getId();
            } else {
                $user_id = 0;
            }
        }

        if ($table == '') {
            return false;
        }

        $lf = TTnew('LogFactory');

        $lf->setObject($object_id);
        $lf->setAction($action_id);
        $lf->setTableName($table);
        $lf->setUser((int)$user_id);
        $lf->setDescription($description);

        //Debug::text('Object ID: '. $object_id .' Action ID: '. $action_id .' Table: '. $table .' Description: '. $description, __FILE__, __LINE__, __METHOD__, 10);
        if ($lf->isValid() === true) {
            $insert_id = $lf->Save();

            if ((
                    !isset($config_vars['other']['disable_audit_log_detail'])
                    or (isset($config_vars['other']['disable_audit_log_detail']) and $config_vars['other']['disable_audit_log_detail'] != true)
                )
                and is_object($object) and $object->getEnableSystemLogDetail() == true
            ) {
                $ldf = TTnew('LogDetailFactory');
                $ldf->addLogDetail($action_id, $insert_id, $object);
            } else {
                Debug::text('LogDetail Disabled... Object ID: ' . $object_id . ' Action ID: ' . $action_id . ' Table: ' . $table . ' Description: ' . $description, __FILE__, __LINE__, __METHOD__, 10);
                //Debug::text('LogDetail Disabled... Config: '. (int)$config_vars['other']['disable_audit_log_detail'] .' Function: '. (int)$object->getEnableSystemLogDetail(), __FILE__, __LINE__, __METHOD__, 10);
            }

            return true;
        }

        return false;
    }
}
