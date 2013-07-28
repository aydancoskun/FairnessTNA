<?php
/*********************************************************************************
 * This file is part of "Fairness", a Payroll and Time Management program.
 * Fairness is Copyright 2013 Aydan Coscun (aydan.ayfer.coskun@gmail.com)
 * Portions of this software are Copyright (C) 2003 - 2013 TimeTrex Software Inc.
 * because Fairness is a fork of "TimeTrex Workforce Management" Software.
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
/*
 * $Revision: 2196 $
 * $Id: APICompanySetting.class.php 2196 2008-10-14 16:08:54Z ipso $
 * $Date: 2008-10-14 09:08:54 -0700 (Tue, 14 Oct 2008) $
 */

/**
 * @package API\Company
 */
class APICompanySetting extends APIFactory {
	protected $main_class = 'CompanySettingFactory';

	public function __construct() {
		parent::__construct(); //Make sure parent constructor is always called.

		return TRUE;
	}

    function getCompanySetting( $name ) {
        $retarr = CompanySettingFactory::getCompanySetting( $this->getCurrentCompanyObject()->getId(), $name );
        if ( $retarr == TRUE ) {
            return $this->returnHandler( $retarr );
        }

        return $this->returnHandler( TRUE);
    }

    function setCompanySetting( $name, $value, $type_id = 10 ) {
        $retval = CompanySettingFactory::setCompanySetting( $this->getCurrentCompanyObject()->getId(), $name, $value, $type_id );
        return $this->returnHandler($retval);
    }

    function deleteCompanySetting( $name ) {
        $retval = CompanySettingFactory::deleteCompanySetting( $this->getCurrentCompanyObject()->getId(), $name );
        return $this->returnHandler($retval);
    }

}
?>
