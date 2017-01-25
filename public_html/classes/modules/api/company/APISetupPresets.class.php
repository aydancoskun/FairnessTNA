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
 * @package API\Company
 */
class APISetupPresets extends APIFactory
{
    protected $main_class = 'SetupPresets';

    public function __construct()
    {
        parent::__construct(); //Make sure parent constructor is always called.

        return true;
    }

    public function createPresets($data)
    {
        if (!$this->getPermissionObject()->Check('pay_period_schedule', 'enabled')
            or !($this->getPermissionObject()->Check('pay_period_schedule', 'edit') or $this->getPermissionObject()->Check('pay_period_schedule', 'edit_own') or $this->getPermissionObject()->Check('pay_period_schedule', 'edit_child') or $this->getPermissionObject()->Check('pay_period_schedule', 'add'))
        ) {
            return $this->getPermissionObject()->PermissionDenied();
        }

        if (is_array($data)) {
            $this->getProgressBarObject()->start($this->getAMFMessageID(), (count($data) + 1), null, TTi18n::getText('Creating policies...'));

            $this->getMainClassObject()->setCompany($this->getCurrentCompanyObject()->getId());
            $this->getMainClassObject()->setUser($this->getCurrentUserObject()->getId());

            $this->getMainClassObject()->createPresets();

            $already_processed_country = array();
            $i = 1;
            foreach ($data as $location) {
                if (isset($location['country']) and isset($location['province'])) {
                    if ($location['province'] == '00') {
                        $location['province'] = null;
                    }

                    if (!in_array($location['country'], $already_processed_country)) {
                        $this->getMainClassObject()->createPresets($location['country']);
                    }

                    $this->getMainClassObject()->createPresets($location['country'], $location['province']);
                    Debug::text('Creating presets for Country: ' . $location['country'] . ' Province: ' . $location['province'], __FILE__, __LINE__, __METHOD__, 9);

                    $already_processed_country[] = $location['country'];
                }

                $this->getProgressBarObject()->set($this->getAMFMessageID(), $i);
                $i++;
            }

            $this->getProgressBarObject()->set($this->getAMFMessageID(), $i++, TTi18n::getText('Creating Permissions...'));
            $this->getMainClassObject()->Permissions();
            $this->getMainClassObject()->UserDefaults();

            //Assign the current user to the only existing pay period schedule.
            $ppslf = TTnew('PayPeriodScheduleListFactory');
            $ppslf->getByCompanyId($this->getCurrentCompanyObject()->getId());
            if ($ppslf->getRecordCount() == 1) {
                $pps_obj = $ppslf->getCurrent();

                //In case the user runs the quick start wizard after they are already setup, assign all users to the only existing pay period schedule.
                $user_ids = array();
                $ulf = TTNew('UserListFactory');
                $ulf->getByCompanyId($this->getCurrentCompanyObject()->getId());
                if ($ulf->getRecordCount() > 0) {
                    foreach ($ulf as $u_obj) {
                        $user_ids[] = $u_obj->getId();
                    }
                }
                $pps_obj->setUser($user_ids);
                unset($user_ids);

                Debug::text('Assigning current user to pay period schedule: ' . $pps_obj->getID(), __FILE__, __LINE__, __METHOD__, 9);
                if ($pps_obj->isValid()) {
                    $pps_obj->Save();
                }
            }

            $this->getCurrentCompanyObject()->setSetupComplete(true);
            if ($this->getCurrentCompanyObject()->isValid()) {
                $this->getCurrentCompanyObject()->Save();
            }

            $this->getProgressBarObject()->stop($this->getAMFMessageID());
        }

        return true;
    }
}
