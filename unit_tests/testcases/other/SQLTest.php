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
class SQLTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        global $dd;
        Debug::text('Running setUp(): ', __FILE__, __LINE__, __METHOD__, 10);

        $dd = new DemoData();
        $dd->setEnableQuickPunch(false); //Helps prevent duplicate punch IDs and validation failures.
        $dd->setUserNamePostFix('_' . uniqid(null, true)); //Needs to be super random to prevent conflicts and random failing tests.
        $this->company_id = $dd->createCompany();
        Debug::text('Company ID: ' . $this->company_id, __FILE__, __LINE__, __METHOD__, 10);
        $this->assertGreaterThan(0, $this->company_id);

        //$dd->createPermissionGroups( $this->company_id, 40 ); //Administrator only.

        $dd->createCurrency($this->company_id, 10);

        $this->branch_id = $dd->createBranch($this->company_id, 10); //NY

        //$dd->createPayStubAccount( $this->company_id );
        //$dd->createPayStubAccountLink( $this->company_id );

        $dd->createUserWageGroups($this->company_id);

        $this->user_id = $dd->createUser($this->company_id, 100);
        $this->assertGreaterThan(0, $this->user_id);

        return true;
    }

    public function tearDown()
    {
        Debug::text('Running tearDown(): ', __FILE__, __LINE__, __METHOD__, 10);

        return true;
    }

    public function getListFactoryClassList($equal_parts = 1)
    {
        global $global_class_map;

        $retarr = array();

        //Get all ListFactory classes
        foreach ($global_class_map as $class_name => $class_file_name) {
            if (strpos($class_name, 'ListFactory') !== false) {
                $retarr[] = $class_name;
            }
        }

        $chunk_size = ceil((count($retarr) / $equal_parts));

        return array_chunk($retarr, $chunk_size);
    }
}
