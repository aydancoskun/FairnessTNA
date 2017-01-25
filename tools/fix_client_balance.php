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

require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'global.inc.php');
require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'CLI.inc.php');

if (isset($argv[1]) and in_array($argv[1], array('--help', '-help', '-h', '-?'))) {
    $help_output = "Usage: fix_client_balance.php -company_id [company_id] -client_id [client_id]\n";
    echo $help_output;
} else {
    if (in_array('-company_id', $argv)) {
        $company_id = trim($argv[array_search('-company_id', $argv) + 1]);
    }

    if (in_array('-client_id', $argv)) {
        $client_id = trim($argv[array_search('-client_id', $argv) + 1]);
    }

    //Force flush after each output line.
    ob_implicit_flush(true);
    ob_end_flush();

    $clf = new CompanyListFactory();
    if (isset($company_id) and $company_id > 0) {
        $clf->getByCompanyId($company_id);
    } else {
        $clf->getAll();
    }
    if ($clf->getRecordCount() > 0) {
        foreach ($clf as $c_obj) {
            echo 'Company: ' . $c_obj->getName() . "...\n";

            $cbf = new ClientBalanceFactory();
            $cbf->StartTransaction();

            $tmp_clf = new ClientListFactory();
            if (isset($client_id) and $client_id > 0) {
                $tmp_clf->getByIdAndCompanyId($client_id, $c_obj->getId());
            } else {
                $tmp_clf->getByCompanyId($c_obj->getId());
            }

            $max = $tmp_clf->getRecordCount();
            $i = 0;
            foreach ($tmp_clf as $tmp_c_obj) {
                //if ( !in_array( $tmp_c_obj->getId(), array(195,1249,1800) ) ) {
                //	continue;
                //}

                echo '  ' . $i . '/' . $max . ' Recalculating: ' . $tmp_c_obj->getCompanyName() . "...\n";
                $cbf->reCalculateBalance($tmp_c_obj->getId(), $tmp_c_obj->getCompany());

                $i++;
            }

            //$cbf->FailTransaction();
            $cbf->CommitTransaction();
        }
    }
}
//Debug::Display();;
