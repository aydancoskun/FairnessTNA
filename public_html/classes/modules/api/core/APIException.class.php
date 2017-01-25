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
 * @package API\Core
 */
class APIException extends APIFactory
{
    protected $main_class = 'ExceptionFactory';

    public function __construct()
    {
        parent::__construct(); //Make sure parent constructor is always called.

        return true;
    }

    /**
     * Get default exception data for creating new exceptiones.
     * @return array
     */
    public function getExceptionDefaultData()
    {
        $company_obj = $this->getCurrentCompanyObject();

        Debug::Text('Getting exception default data...', __FILE__, __LINE__, __METHOD__, 10);

        $data = array(
            'company_id' => $company_obj->getId(),
        );

        return $this->returnHandler($data);
    }

    /**
     * @param string $format
     * @param null $data
     * @param bool $disable_paging
     * @return array|bool
     */
    public function exportException($format = 'csv', $data = null, $disable_paging = true)
    {
        $result = $this->stripReturnHandler($this->getException($data, $disable_paging));
        return $this->exportRecords($format, 'export_exceptions', $result, ((isset($data['filter_columns'])) ? $data['filter_columns'] : null));
    }

    /**
     * Get exception data for one or more exceptiones.
     * @param array $data filter data
     * @return array
     */
    public function getException($data = null, $disable_paging = false)
    {
        if (!$this->getPermissionObject()->Check('punch', 'enabled')
            or !($this->getPermissionObject()->Check('punch', 'view') or $this->getPermissionObject()->Check('punch', 'view_own') or $this->getPermissionObject()->Check('punch', 'view_child'))
        ) {
            //return $this->getPermissionObject()->PermissionDenied();
            $data['filter_columns'] = $this->handlePermissionFilterColumns((isset($data['filter_columns'])) ? $data['filter_columns'] : null, Misc::trimSortPrefix($this->getOptions('list_columns')));
        }
        $data = $this->initializeFilterAndPager($data, $disable_paging);

        $data['filter_data']['permission_children_ids'] = $this->getPermissionObject()->getPermissionChildren('punch', 'view');

        //If no pay period is specified, force to showing exceptions only in non-closed pay periods. This is a performance optimization too.
        if (!isset($data['filter_data']['pay_period_status_id']) and !isset($data['filter_data']['pay_period_id'])) {
            $data['filter_data']['pay_period_status_id'] = array(10, 12, 30); //All but closed
        }

        $blf = TTnew('ExceptionListFactory');
        $blf->getAPISearchByCompanyIdAndArrayCriteria($this->getCurrentCompanyObject()->getId(), $data['filter_data'], $data['filter_items_per_page'], $data['filter_page'], null, $data['filter_sort']);
        Debug::Text('Record Count: ' . $blf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
        if ($blf->getRecordCount() > 0) {
            $this->getProgressBarObject()->start($this->getAMFMessageID(), $blf->getRecordCount());

            $this->setPagerObject($blf);

            $retarr = array();
            foreach ($blf as $b_obj) {
                $retarr[] = $b_obj->getObjectAsArray($data['filter_columns'], $data['filter_data']['permission_children_ids']);

                $this->getProgressBarObject()->set($this->getAMFMessageID(), $blf->getCurrentRow());
            }

            $this->getProgressBarObject()->stop($this->getAMFMessageID());

            return $this->returnHandler($retarr);
        }

        return $this->returnHandler(true); //No records returned.
    }

    /**
     * Get options for dropdown boxes.
     * @param string $name Name of options to return, ie: 'columns', 'type', 'status'
     * @param mixed $parent Parent name/ID of options to return if data is in hierarchical format. (ie: Province)
     * @return array
     */
    public function getOptions($name = false, $parent = null)
    {
        if ($name == 'columns'
            and (!$this->getPermissionObject()->Check('punch', 'enabled')
                or !($this->getPermissionObject()->Check('punch', 'view') or $this->getPermissionObject()->Check('punch', 'view_child')))
        ) {
            $name = 'list_columns';
        }

        return parent::getOptions($name, $parent);
    }
}
